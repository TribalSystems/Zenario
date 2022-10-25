<?php
/*
 * Copyright (c) 2022, Tribal Limited
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

class zenario_users__admin_boxes__user__welcome_email extends zenario_users {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$userIds = explode(',', $box['key']['id']);
		if (count($userIds) == 1) {
			$userDetails = ze\user::details($userIds[0]);
			$box['title'] = "Sending activation email to the user \"" . $userDetails["identifier"] . "\"";
		} else {
			$box['title'] = "Sending activation emails to " . count($userIds) . " users";
		}
		
		$fields['details/email_to_send']['value'] = ze::setting('default_activation_email_template');

		$siteSettingsLink = "<a href='zenario/admin/organizer.php#zenario__administration/panels/site_settings//users~.site_settings~tactivation_email_template~k{\"id\"%3A\"users\"}' target='_blank'>site settings</a>";
		$fields['details/email_to_send']['note_below'] = ze\admin::phrase(
			'The default activation email template can be changed in the [[site_settings_link]].',
			['site_settings_link' => $siteSettingsLink]
		);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$userIds = explode(',', $box['key']['id']);
		foreach ($userIds as $userId) {
			$user = ze\row::get('users', ['identifier', 'email'], $userId);
			if (!$user['email']) {
				$box['tabs']['details']['errors'][] = ze\admin::phrase('The user "[[identifier]]" must have an email address to send a activation email.', $user);
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$userIds = explode(',', $box['key']['id']);
		if ($userIds) {
			if ($values['details/email_to_send'] && (ze\module::inc('zenario_email_template_manager'))) {
				foreach ($userIds as $userId) {
					$mergeFields = ze\user::details($userId);
					if (isset($values['details/reset_password']) && $values['details/reset_password']) {
						$mergeFields['password'] = ze\userAdm::createPassword();
						$cols = [
							'last_edited_admin_id' => ze\admin::id(),
							'last_edited_user_id' => null,
							'last_edited_username' => null,
							'password' => $mergeFields['password']
						];
						ze\userAdm::save($cols, $userId);
					} elseif (isset($values['details/include_password']) && $values['details/include_password']) {
						//show plain text password
					} else {
						$mergeFields['password'] = "********* (not shown)";
					}
					
					$mergeFields['cms_url'] = ze\link::absolute();
					
					zenario_email_template_manager::sendEmailsUsingTemplate($mergeFields['email'], $values['details/email_to_send'], $mergeFields);
				}
			}
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}
}
