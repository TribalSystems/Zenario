<?php
/*
 * Copyright (c) 2021, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

class zenario_users__admin_boxes__user__activate extends zenario_users {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$userDetails = ze\user::details($box["key"]["id"] ?? false);
	
		$box['title'] = "Activating the user \"" . ($userDetails["identifier"] ?? false) . "\"";
	
		$fields['email/email_to_send']['value'] = ze::setting('default_activation_email_template');

		$siteSettingsLink = "<a href='organizer.php#zenario__administration/panels/site_settings//users~.site_settings~tactivation_email_template~k{\"id\"%3A\"users\"}' target='_blank'>site settings</a>";
		$fields['email/email_to_send']['note_below'] = ze\admin::phrase(
			'The default activation email template can be changed in the [[site_settings_link]].',
			['site_settings_link' => $siteSettingsLink]
		);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$fields['email_to_send']['hidden'] = !ze\ray::issetArrayKey($values,'email/send_email_to_user');
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (($userId = (int)$box['key']['id']) && ze\priv::check('_PRIV_EDIT_USER')) {
			$cols = [];
			ze\admin::setLastUpdated($cols, $creating = false);
			$cols['modified_date'] = $cols['last_edited'];
			$cols['suspended_date'] = NULL;
			unset($cols['last_edited']);
			$cols['status'] = 'active';
		
			ze\row::update('users', $cols, $userId);
			
			ze\module::sendSignal("eventUserStatusChange", ["userId" => $userId, "status" => "active"]);
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (ze\priv::check('_PRIV_EDIT_USER')) {
			if (ze\ray::issetArrayKey($values,'email/send_email_to_user') && ze\ray::issetArrayKey($values,'email/email_to_send') && (ze\module::inc('zenario_email_template_manager'))) {
				$mergeFields=ze\user::details($box['key']['id']);
				$mergeFields['cms_url'] = ze\link::absolute();
				
				//Encrypted password, show ****
				$mergeFields['password'] = '********';
				
				$result = ze\module::sendSignal("requestAdditionalEmailTemplateMergeFields",["userId" => $box['key']['id']]);
	
				if (is_array($result)) {
					foreach ($result as $moduleName => $moduleResult) {
						$mergeFields = array_merge($mergeFields,$moduleResult);
					}
				}
	
				zenario_email_template_manager::sendEmailsUsingTemplate($mergeFields['email'] ?? false,($values['email/email_to_send'] ?? false),$mergeFields);
			}
		}
	}
}
