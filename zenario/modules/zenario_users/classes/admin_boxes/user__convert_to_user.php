<?php
/*
 * Copyright (c) 2023, Tribal Limited
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

class zenario_users__admin_boxes__user__convert_to_user extends zenario_users {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$userDetails = ze\user::details($box["key"]["id"] ?? false);
		$values['details/email'] = $userDetails['email'];
		$values['details/salutation'] = $userDetails['salutation'];
		$values['details/first_name'] = $userDetails['first_name'];
		$values['details/last_name'] = $userDetails['last_name'];
	
		$box['title'] = "Converting the contact \"" . ($userDetails["identifier"] ?? false) . "\"";
	
		$fields['details/email_to_send']['value'] = ze::setting('default_activation_email_template');

		$siteSettingsLink = "<a href='organizer.php#zenario__administration/panels/site_settings//users~.site_settings~tactivation_email_template~k{\"id\"%3A\"users\"}' target='_blank'>site settings</a>";
		$fields['details/email_to_send']['note_below'] = ze\admin::phrase(
			'The default activation email template can be changed in the [[site_settings_link]].',
			['site_settings_link' => $siteSettingsLink]
		);
		
		$fields['details/password_needs_changing']['note_below'] =
			htmlspecialchars(
				ze\admin::nPhrase('This will flag the user\'s password as "needs changing". If it is not changed within 1 day, it will expire and need to be reset.',
					'This will flag the user\'s password as "needs changing". If it is not changed within [[count]] days, it will expire and need to be reset.',
					(int) ze::setting('temp_password_timeout') ?: 14
				)
			);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (!ze::setting('user_use_screen_name')) {
			$fields['details/screen_name']['hidden'] = true;
			$fields['details/screen_name']['validation'] = false;
			$fields['details/suggest_screen_name']['hidden'] = true;
		}
		if (!$values['details/email']) {
			$fields['details/email']['readonly'] =  false;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$cols = [
			'email' => $values['details/email'],
			'salutation' => $values['details/salutation'],
			'first_name' => $values['details/first_name'],
			'last_name' => $values['details/last_name'],
			'terms_and_conditions_accepted' => $values['details/terms_and_conditions_accepted']
		];
	
		if (ze::setting('user_use_screen_name')) {
			$cols['screen_name'] = $values['details/screen_name'];
		}
		
		if ($e = ze\userAdm::isInvalid($cols, $box['key']['id'])) {
			//If there are errors, add them to the first tab
			foreach ($e->errors as $error) {
				$box['tabs']['details']['errors'][] = ze\admin::phrase($error);
			}
		}

		if (!$fields['details/send_activation_email_to_user']['hidden'] && $values['details/send_activation_email_to_user']) {
			if (!$values['details/email']) {
				$fields['details/send_activation_email_to_user']['error'] = ze\admin::phrase('Please enter an email address in the "Email" field above.');
				$fields['details/email']['error'] = ze\admin::phrase('Please enter an email address.');
			} elseif (!empty($fields['details/email']['error'])) {
				$fields['details/send_activation_email_to_user']['error'] = $fields['details/email']['error'];
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (($id = (int)$box['key']['id']) && ze\priv::check('_PRIV_EDIT_USER')) {
			$cols = [
				'email' => $values['details/email'],
				'salutation' => $values['details/salutation'],
				'first_name' => $values['details/first_name'],
				'last_name' => $values['details/last_name'],
				'status' => 'active',
				'terms_and_conditions_accepted' => $values['details/terms_and_conditions_accepted']
			];
	
			if (ze::setting('user_use_screen_name')) {
				$cols['screen_name'] = $values['details/screen_name'];
			}
			
			$cols['password'] = $values['details/password'];
			$cols['password_needs_changing'] = $values['details/password_needs_changing'];
			$cols['reset_password_time'] = ze\date::now();
			$cols['last_edited_admin_id'] = ze\admin::id();
			$cols['last_edited_user_id'] = null;
			$cols['last_edited_username'] = null;
			
			ze\userAdm::save($cols, $box['key']['id']);
			
			ze\module::sendSignal("eventUserStatusChange", ["userId" => $id, "status" => "active"]);
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (ze\priv::check('_PRIV_EDIT_USER')) {
			if (isset($values['details/send_activation_email_to_user']) &&  $values['details/send_activation_email_to_user']
				&& ze\ray::issetArrayKey($values,'details/email_to_send') && (ze\module::inc('zenario_email_template_manager'))) {
				$mergeFields=ze\user::details($box['key']['id']);
				$mergeFields['username'] = $mergeFields['screen_name'];
				$mergeFields['password'] = $values['password'];
				$mergeFields['cms_url'] = ze\link::absolute();
				
				zenario_email_template_manager::sendEmailsUsingTemplate($mergeFields['email'] ?? false,($values['details/email_to_send'] ?? false),$mergeFields);
			}
		}
	}
}
