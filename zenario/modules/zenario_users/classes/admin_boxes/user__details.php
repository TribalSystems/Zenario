<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

class zenario_users__admin_boxes__user__details extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id']) {
			exitIfNotCheckPriv('_PRIV_VIEW_USER');
			
			if (!checkPriv('_PRIV_VIEW_USER')) {
				unset($box['tabs']['details']['edit_mode']);
			}
			
			$user = getUserDetails($box['key']['id']);
			
			$values['details/status'] = $user['status'];
			$values['details/email'] = $user['email'];
			$values['details/email_verified'] = $user['email_verified'];
			$values['details/salutation'] = $user['salutation'];
			$values['details/first_name'] = $user['first_name'];
			$values['details/last_name'] = $user['last_name'];
			$values['details/screen_name'] = $user['screen_name'];
			$values['details/screen_name_confirmed'] = $user['screen_name_confirmed'];
			$values['details/terms_and_conditions_accepted'] = $user['terms_and_conditions_accepted'];
			
			switch($user['status']) {
				case 'contact':
					$fields['details/status']['side_note'] = 
						adminPhrase('This person is a Contact. Contacts are stored in the database and can be sent emails via Zenario\'s Newsletter system. To convert to an Extranet User, close this box and select an action in the Actions drop-down.');
					break;
				case 'pending':
					$fields['details/status']['side_note'] = 
						adminPhrase('This person is a Pending User, meaning that the person cannot log in to a password-protected area of the site until the account is made Active. To do this, close this box and select an action in the Actions drop-down.');
					break;
				case 'active':
					$fields['details/status']['side_note'] = 
						adminPhrase('This person is an Extranet User, with a status of Active, meaning the person can log in to a password-protected area of the site. if you should need to suspend or delete this user\'s account, close this box and select an action in the Actions drop-down.');
					break;
				case 'suspended':
					$fields['details/status']['side_note'] = 
						adminPhrase('This person is a Extranet User, with a status of Suspended, meaning that the person cannot log in to a password-protected area of the site until the account is re-activated. To do this, close this box and select an action in the Actions drop-down.');
					break;
			}
			if (checkPriv('_PRIV_CHANGE_USER_PASSWORD')) {
				if (!setting('plaintext_extranet_user_passwords')) {
					$fields['details/password']['label'] = adminPhrase('New password:');
					$fields['details/password']['side_note'] =
						adminPhrase('Password encryption is enabled. You can set a user password for this user but not view the existing one.');
				
				} elseif (!is_null($user['password_salt'])) {
					$fields['details/password']['label'] = adminPhrase('New password:');
				
				} else {
					$values['details/password'] = $user['password'];
				}
				
				$values['details/password_needs_changing'] = $user['password_needs_changing'];
			}
			
			$fields['details/status']['read_only'] = true;
			$fields['details/status']['values'] = array($user['status'] => $fields['details/status']['values'][$user['status']]);
			
			$values['dates/ip'] = $user['ip'];
			$values['dates/last_login'] = $user['last_login'];
			$values['dates/created_date'] = $user['created_date'];
			$values['dates/modified_date'] = $user['modified_date'];
			$values['dates/last_profile_update_in_frontend'] = $user['last_profile_update_in_frontend'];
			
			$box['title'] = adminPhrase('Editing the user "[[identifier]]"', $user);
			
		} else {
			exitIfNotCheckPriv('_PRIV_CREATE_USER');
			
			unset($fields['details/status']['values']['suspended']);
			unset($fields['details/status']['values']['pending']);
			$fields['details/email_verified']['hidden'] = true;
			
			$box['title'] = adminPhrase('Creating a user or contact');
			
			$fields['details/password_needs_changing']['label'] = "Ask user to change password when first logging in";
			$fields['details/send_activation_email_to_user']['hidden'] = false;
			$fields['details/email_to_send']['hidden'] = false;
			$layouts = zenario_email_template_manager::getTemplatesByNameIndexedByCode('User Activated',false);
	
			if (count($layouts)==0) {
				$layouts = zenario_email_template_manager::getTemplatesByNameIndexedByCode('Account Activated',false);
			}
	
			if (count($layouts)){
				$template = current($layouts);
				$fields['details/email_to_send']['value'] = arrayKey($template,'code');
			}
		}
		
		if (checkPriv('_PRIV_CHANGE_USER_PASSWORD')) {
			if (!$box['key']['id']) {
				unset($fields['details/password_needs_changing']['side_note']);
				
				$fields['details/password_needs_changing']['note_below'] =
					htmlspecialchars(
						nAdminPhrase('This will flag the user\'s password as "needs changing". If it is not changed within 1 day, it will expire and need to be reset.',
							'This will flag the user\'s password as "needs changing". If it is not changed within [[count]] days, it will expire and need to be reset.',
							ifNull((int) setting('temp_password_timeout'), 14)
						)
					);
			
			} elseif (empty($user['last_login'])) {
				unset($fields['details/password_needs_changing']['side_note']);
				
				$fields['details/password_needs_changing']['note_below'] =
					htmlspecialchars(
						nAdminPhrase('This will flag the user\'s password as "needs changing". If it is not changed within 1 day of being set, it will expire and need to be reset.',
							'This will flag the user\'s password as "needs changing". If it is not changed within [[count]] days of being set, it will expire and need to be reset.',
							ifNull((int) setting('temp_password_timeout'), 14)
						)
					);
			}
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$fields['details/password']['type'] = empty($fields['details/reveal_password']['pressed'])? 'password' : 'text';
		$fields['details/reveal_password']['value'] = empty($fields['details/reveal_password']['pressed'])? 'Reveal' : 'Hide';
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (!empty($box['tabs']['details']['edit_mode']['on'])) {
			
			if (!$values['details/status']) {
				$box['tabs']['details']['errors'][] = adminPhrase('Please select a status for this user.');
			}
			
			if (!($values['details/email'] || $values['details/first_name'] || $values['details/last_name'])) {
				$box['tabs']['details']['errors'][] = adminPhrase('Please enter an email, a first name or a last name.');
			}
			
			//Call the isInvalidUser() function to get any errors in the submission
			$cols = array(
				'email' => $values['details/email'],
				'email_verified' => $values['details/email_verified'],
				'salutation' => $values['details/salutation'],
				'first_name' => $values['details/first_name'],
				'last_name' => $values['details/last_name'],
				'terms_and_conditions_accepted' => $values['details/terms_and_conditions_accepted']
			);
	
			if (setting('user_use_screen_name')) {
				$cols['screen_name'] = $values['details/screen_name'];
			}
	
			if (!$box['key']['id']) {
				$cols['status'] = $values['details/status'];
				$cols['creation_method'] = 'admin';
			}
		
			if ($values['details/status'] != 'contact') {
				if (!$box['key']['id']
				 || (!empty($fields['details/change_password']['pressed']) && checkPriv('_PRIV_CHANGE_USER_PASSWORD'))) {
					$cols['password'] = $values['details/password'];
				}
				if (checkPriv('_PRIV_CHANGE_USER_PASSWORD')) {
					$cols['password_needs_changing'] = $values['details/password_needs_changing'];
				}
			}
			
			if ($e = isInvalidUser($cols, $box['key']['id'])) {
				
				//If there are errors, add them to the first tab
				foreach ($e->errors as $error) {
					$box['tabs']['details']['errors'][] = adminPhrase($error);
				}
			}
			
			if ($values['details/status']
			 && $values['details/status'] != 'contact') {
				if (!$box['key']['id']
				 || (!empty($fields['details/change_password']['pressed']) && checkPriv('_PRIV_CHANGE_USER_PASSWORD'))) {
					if (!$values['details/password']) {
						$box['tabs']['details']['errors'][] = adminPhrase('Please enter a password.');
					}
				}
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		if (!empty($box['tabs']['details']['edit_mode']['on'])) {
			if ($box['key']['id']) {
				exitIfNotCheckPriv('_PRIV_EDIT_USER');
			} else {
				exitIfNotCheckPriv('_PRIV_CREATE_USER');
			}
			
			$cols = array(
				'email' => $values['details/email'],
				'email_verified' => $values['details/email_verified'],
				'salutation' => $values['details/salutation'],
				'first_name' => $values['details/first_name'],
				'last_name' => $values['details/last_name'],
				'terms_and_conditions_accepted' => $values['details/terms_and_conditions_accepted']
			);
	
			if (setting('user_use_screen_name')) {
				$cols['screen_name'] = $values['details/screen_name'];
			}
	
			if (!$box['key']['id']) {
				$cols['status'] = $values['details/status'];
				$cols['creation_method'] = 'admin';
			}
		
			if ($values['details/status'] != 'contact') {
				if (!$box['key']['id']
				 || (!empty($fields['details/change_password']['pressed']) && checkPriv('_PRIV_CHANGE_USER_PASSWORD'))) {
					$cols['password'] = $values['details/password'];
					$cols['reset_password_time'] = now();
				}
				if (checkPriv('_PRIV_CHANGE_USER_PASSWORD')) {
					$cols['password_needs_changing'] = $values['details/password_needs_changing'];
				}
			}
			$box['key']['id'] = saveUser($cols, $box['key']['id']);
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($box['key']['id']) {
			exitIfNotCheckPriv('_PRIV_EDIT_USER');
		} else {
			exitIfNotCheckPriv('_PRIV_CREATE_USER');
		}
		if (isset($values['details/send_activation_email_to_user']) &&  $values['details/send_activation_email_to_user']
			&& issetArrayKey($values,'details/email_to_send') && (inc('zenario_email_template_manager'))) {
			$mergeFields=getUserDetails($box['key']['id']);
			$mergeFields['password'] = $values['password'];
			
			$mergeFields['cms_url'] = absCMSDirURL();
			
			zenario_email_template_manager::sendEmailsUsingTemplate($mergeFields['email'], $values['details/email_to_send'], $mergeFields);
		}
	}
	
	protected function getUserColsFromDetailsTab(&$box, &$fields, &$values) {
		$cols = array(
			'email' => $values['details/email'],
			'email_verified' => $values['details/email_verified'],
			'salutation' => $values['details/salutation'],
			'first_name' => $values['details/first_name'],
			'last_name' => $values['details/last_name'],
			'terms_and_conditions_accepted' => $values['details/terms_and_conditions_accepted']
		);
	
		if (setting('user_use_screen_name')) {
			$cols['screen_name'] = $values['details/screen_name'];
		}
	
		if (!$box['key']['id']) {
			$cols['status'] = $values['details/status'];
			$cols['creation_method'] = 'admin';
		}
		
		if ($values['details/status'] != 'contact') {
			if (!$box['key']['id']
			 || (!empty($fields['details/change_password']['pressed']) && checkPriv('_PRIV_CHANGE_USER_PASSWORD'))) {
				$cols['password'] = $values['details/password'];
			}
			if (checkPriv('_PRIV_CHANGE_USER_PASSWORD')) {
				$cols['password_needs_changing'] = $values['details/password_needs_changing'];
			}
		}
		
		return $cols;
	}
}
