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

class zenario_users__admin_boxes__user__details extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id']) {
			ze\priv::exitIfNot('_PRIV_VIEW_USER');
			
			if (!ze\priv::check('_PRIV_VIEW_USER')) {
				unset($box['tabs']['details']['edit_mode']);
			}
			
			$user = ze\user::details($box['key']['id']);
			
			$box['last_updated'] = ze\admin::formatUserLastUpdated($user);
			
			$values['details/status'] = $user['status'];
			$values['details/email'] = $user['email'];
			$values['details/email_verified'] = $user['email_verified'];
			$values['details/salutation'] = $user['salutation'];
			$values['details/first_name'] = $user['first_name'];
			$values['details/last_name'] = $user['last_name'];
			$values['details/screen_name'] = $user['screen_name'];
			$values['details/screen_name_confirmed'] = $user['screen_name_confirmed'];
			$values['details/terms_and_conditions_accepted'] = $user['terms_and_conditions_accepted'];
			
			//Look up the list of linked countries for this user
			$values['details/linked_countries'] =
				implode(',', ze\row::getValues('user_country_link', 'country_id', ['user_id' => $box['key']['id']]));
			
			$suspendedDate = date_format(new DateTime($user['suspended_date']), "d M Y H:i:s");
			switch($user['status']) {
				case 'contact':
					$fields['details/status']['note_below'] = 
						ze\admin::phrase('A contact cannot log in, but is stored as a customer enquiry record and/or for newsletter emailing.');
					$fields['details/status']['side_note'] = 
						ze\admin::phrase('To convert to an extranet user, close this box and select an action in the "Actions" drop-down.');
					break;
				case 'pending':
					$fields['details/status']['note_below'] = 
						ze\admin::phrase('A would-be extranet user who has not yet been activated. Depending on Extranet Registration plugin settings, someone may self-activate their account by verifying their email address, or an administrator may activate the account.');
					$fields['details/status']['side_note'] = 
						ze\admin::phrase('To activate this account, close this box and use "Actions".');
					break;
				case 'active':
					$fields['details/status']['note_below'] = 
						ze\admin::phrase('A user who can log in to this site\'s extranet area, if it has one.');
					$fields['details/status']['side_note'] = 
						ze\admin::phrase('To suspend this account, close this box and use "Actions".');
					break;
				case 'suspended':
					$fields['details/status']['note_below'] = 
						ze\admin::phrase('A previously activated extranet user. The account is suspended so they cannot log in. ');
					$fields['details/status']['side_note'] = 
						ze\admin::phrase('To re-activate, close this box and use "Actions", or "Delete" to delete.');
					
					$fields['details/status']['values']['suspended']['label'] .= ' (' . ze\admin::phrase('suspended on [[date]]', ['date' => $suspendedDate]) . ')';
					break;
			}
			if (ze\priv::check('_PRIV_EDIT_USER')) {
				$fields['details/password']['label'] = ze\admin::phrase('New password:');
				$fields['details/password']['side_note'] =
					ze\admin::phrase('Password encryption is enabled. You can set a user password for this user but not view the existing one.');
				
				$values['details/password_needs_changing'] = $user['password_needs_changing'];
			}
			
			$fields['details/status']['readonly'] = true;
			$fields['details/status']['values'] = [$user['status'] => $fields['details/status']['values'][$user['status']]];
			
			if ($user['last_login'] != NULL) {
			    $values['dates/last_login'] = date_format(new DateTime($user['last_login']),"d M Y H:i:s");
            } else {
            	$values['dates/last_login'] = ze\admin::phrase('Never logged in');
            }
            
			if ($user['suspended_date'] != NULL){
			    $values['dates/suspended_date'] =  $suspendedDate;
			}
			
			if($user['last_profile_update_in_frontend']!= NULL){
			    $values['dates/last_profile_update_in_frontend'] = date_format(new DateTime($user['last_profile_update_in_frontend']),"d M Y H:i:s");
			    $fields['dates/last_profile_update_in_frontend']['hidden'] = false;
			}else {
			    $fields['dates/last_profile_update_in_frontend']['hidden'] = true;
			
			}
			$userType = $user['status'] == 'contact' ? 'contact' : 'user';
			$box['title'] = ze\admin::phrase('Editing the [[user_type]] "[[identifier]]"', ['identifier' => $user['identifier'], 'user_type' => $userType]);
			
			//If password is expired then show a warning
			if (ze\user::isPasswordExpired($box['key']['id'])) {
				$box['tabs']['details']['notices']['password_expired']['show'] = true;
			}
			
			//Show a log of consents given by the user
			$result = ze\row::query('consents', true, ['user_id' => $box['key']['id']]);
			if (ze\sql::numRows($result) > 0) {
				$html = '
					<table class="basic_table" style="width:100%">
						<tr>
							<th>' . ze\admin::phrase('Date/time') . '</th>
							<th>' . ze\admin::phrase('IP address') . '</th>
							<th>' . ze\admin::phrase('Email') . '</th>
							<th>' . ze\admin::phrase('First name') . '</th>
							<th>' . ze\admin::phrase('Last name') . '</th>
						</tr>';
				while ($row = ze\sql::fetchAssoc($result)) {
					$html .= '
						<tr>
							<td>' . ze\admin::formatDateTime($row['datetime'], '_MEDIUM') . '</td>
							<td>' . $row['ip_address'] . '</td>
							<td>' . $row['email'] . '</td>
							<td>' . $row['first_name'] . '</td>
							<td>' . $row['last_name'] . '</td>
						</tr>';
				}
				$html .= '
					<table>';
				$fields['dates/consents_log']['snippet']['html'] = $html;
			}
			
		} else {
			ze\priv::exitIfNot('_PRIV_EDIT_USER');
			
			unset($fields['details/status']['values']['suspended']);
			unset($fields['details/status']['values']['pending']);
			$fields['details/email_verified']['hidden'] = true;
			
			$box['title'] = ze\admin::phrase('Creating a user or contact');
			
			$fields['details/password_needs_changing']['label'] = "Ask user to change password when first logging in";
			$fields['details/send_activation_email_to_user']['hidden'] = false;
			$fields['details/email_to_send']['hidden'] = false;
	
			$fields['details/email_to_send']['value'] = ze::setting('default_activation_email_template');

			$siteSettingsLink = "<a href='zenario/admin/organizer.php#zenario__administration/panels/site_settings//users~.site_settings~tactivation_email_template~k{\"id\"%3A\"users\"}' target='_blank'>site settings</a>";
			$fields['details/email_to_send']['note_below'] = ze\admin::phrase(
				'The default activation email template can be changed in the [[site_settings_link]].',
				['site_settings_link' => $siteSettingsLink]
			);
		}
		
		if (ze\priv::check('_PRIV_EDIT_USER')) {
			if (!$box['key']['id']) {
				unset($fields['details/password_needs_changing']['side_note']);
				
				$fields['details/password_needs_changing']['note_below'] =
					htmlspecialchars(
						ze\admin::nPhrase('This will flag the user\'s password as "needs changing". If it is not changed within 1 day, it will expire and need to be reset.',
							'This will flag the user\'s password as "needs changing". If it is not changed within [[count]] days, it will expire and need to be reset.',
							(int) ze::setting('temp_password_timeout') ?: 14
						)
					);
			
			} elseif (empty($user['last_login'])) {
				unset($fields['details/password_needs_changing']['side_note']);
				
				$fields['details/password_needs_changing']['note_below'] =
					htmlspecialchars(
						ze\admin::nPhrase('This will flag the user\'s password as "needs changing". If it is not changed within 1 day of being set, it will expire and need to be reset.',
							'This will flag the user\'s password as "needs changing". If it is not changed within [[count]] days of being set, it will expire and need to be reset.',
							(int) ze::setting('temp_password_timeout') ?: 14
						)
					);
			}
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$fields['details/password']['type'] = empty($fields['details/reveal_password']['pressed'])? 'password' : 'text';
		$fields['details/reveal_password']['value'] = empty($fields['details/reveal_password']['pressed'])? 'Reveal' : 'Hide';
		
		$fields['details/password']['side_note'] = ze\admin::displayPasswordRequirementsNoteAdmin($values['details/password']);
		if (empty($fields['details/password']['hidden'])) {
			//Validate password: show whether it matches the requirements or not,
			//but don't show an admin box error if it doesn't.
			//Errors will be shown when validating.
			$passwordValidation = ze\user::checkPasswordStrength($values['details/password']);
			if (!$passwordValidation['password_matches_requirements']) {
				//Set the post-html field to display "FAIL" highlighted in red.
				$passwordMessageSnippet = 
					'<div>
						<span id="snippet_password_message" class="title_red">' . ze\admin::phrase('Password does not match the requirements') . '</span>
					</div>';
			} else {
				//Set the post-html field to display "PASS" highlighted in green.
				$passwordMessageSnippet = 
					'<div>
						<span id="snippet_password_message" class="title_green">' . ze\admin::phrase('Password matches the requirements') . '</span>
					</div>';
			}
			$box['tabs']['details']['fields']['password_message']['post_field_html'] = $passwordMessageSnippet;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (!empty($box['tabs']['details']['edit_mode']['on'])) {
			
			if (!$values['details/status']) {
				$fields['details/status']['error'] = ze\admin::phrase('Please select an account type for this person.');
			}
			
			if (!($values['details/email'] || $values['details/first_name'] || $values['details/last_name'])) {
				$fields['details/email']['error'] = $fields['details/first_name']['error'] = $fields['details/last_name']['error'] = 
					ze\admin::phrase('Please enter at least one of: email address, first name, last name.');
			}
			
			//Call the ze\userAdm::isInvalid() function to get any errors in the submission
			$cols = [
				'email' => $values['details/email'],
				'email_verified' => $values['details/email_verified'],
				'salutation' => $values['details/salutation'],
				'first_name' => $values['details/first_name'],
				'last_name' => $values['details/last_name'],
				'terms_and_conditions_accepted' => $values['details/terms_and_conditions_accepted']
			];
	
			if (ze::setting('user_use_screen_name')) {
				$cols['screen_name'] = $values['details/screen_name'];
			}
	
			if (!$box['key']['id']) {
				$cols['status'] = $values['details/status'];
				$cols['creation_method'] = 'admin';
			}
		
			if ($values['details/status'] != 'contact') {
				if (!$box['key']['id']
				 || (!empty($fields['details/change_password']['pressed']) && ze\priv::check('_PRIV_EDIT_USER'))) {
					$cols['password'] = $values['details/password'];
				}
				if (ze\priv::check('_PRIV_EDIT_USER')) {
					$cols['password_needs_changing'] = $values['details/password_needs_changing'];
				}
			}
			
			if ($e = ze\userAdm::isInvalid($cols, $box['key']['id'])) {
				//If there are errors, add them to the first tab
				foreach ($e->errors as $key => $error) {
					$box['tabs']['details']['errors'][] = ze\admin::phrase($error);
					
					if ($key == 'email') {
						$fields['details/email']['error'] = ze\admin::phrase($error);
					}
				}
			}
			
			if ($values['details/status']
			 && $values['details/status'] != 'contact') {
				if (!$box['key']['id']
				 || (!empty($fields['details/change_password']['pressed']) && ze\priv::check('_PRIV_EDIT_USER'))) {
					if (!$values['details/password']) {
						$box['tabs']['details']['errors'][] = ze\admin::phrase('Please enter a password.');
						$fields['details/password']['error'] = true;
					} else {
						//Validate password
						$passwordValidation = ze\user::checkPasswordStrength($values['details/password']);
						if (!$passwordValidation['password_matches_requirements']) {
					
							$fields['details/password']['error'] = true;
					
							//Set the post-html field to display "FAIL" highlighted in red.
							$passwordMessageSnippet = 
								'<div>
									<span id="snippet_password_message" class="title_red">' . ze\admin::phrase('Password does not match the requirements') . '</span>
								</div>';
						} else {
							//Set the post-html field to display "PASS" highlighted in green.
							$passwordMessageSnippet = 
								'<div>
									<span id="snippet_password_message" class="title_green">' . ze\admin::phrase('Password matches the requirements') . '</span>
								</div>';
						}
						$box['tabs']['details']['fields']['password_message']['post_field_html'] = $passwordMessageSnippet;
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
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		if (!empty($box['tabs']['details']['edit_mode']['on'])) {
			ze\priv::exitIfNot('_PRIV_EDIT_USER');
			
			$cols = [
				'email' => $values['details/email'],
				'email_verified' => $values['details/email_verified'],
				'salutation' => $values['details/salutation'],
				'first_name' => $values['details/first_name'],
				'last_name' => $values['details/last_name'],
				'terms_and_conditions_accepted' => $values['details/terms_and_conditions_accepted']
			];
	
			if (ze::setting('user_use_screen_name')) {
				$cols['screen_name'] = $values['details/screen_name'];
			}
			
			ze\admin::setUserLastUpdated($cols, !$box['key']['id']);
	
			if (!$box['key']['id']) {
				$cols['status'] = $values['details/status'];
			}
		
			if ($values['details/status'] != 'contact') {
				if (!$box['key']['id']
				 || (!empty($fields['details/change_password']['pressed']) && ze\priv::check('_PRIV_EDIT_USER'))) {
					$cols['password'] = $values['details/password'];
					$cols['reset_password_time'] = ze\date::now();
				}
				if (ze\priv::check('_PRIV_EDIT_USER')) {
					$cols['password_needs_changing'] = $values['details/password_needs_changing'];
				}
			}
			
			if (empty($values['details/email'])) {
				$cols['email_verified'] = 0;
			}
			
			$box['key']['id'] = ze\userAdm::save($cols, $box['key']['id']);
			
			//Save the list on linked countries for this user
			//Get a list of country ids selected
			$countryIds = ze\ray::explodeAndTrim($values['details/linked_countries']);
			
			//Delete anything not selected
			ze\row::delete('user_country_link', ['user_id' => $box['key']['id'], 'country_id' => ['!' => $countryIds]]);
			
			foreach ($countryIds as $countryId) {
				ze\row::set('user_country_link', [], ['user_id' => $box['key']['id'], 'country_id' => $countryId]);
			}
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_EDIT_USER');
		
		if (isset($values['details/send_activation_email_to_user']) &&  $values['details/send_activation_email_to_user']
			&& ze\ray::issetArrayKey($values,'details/email_to_send') && (ze\module::inc('zenario_email_template_manager'))) {
			$mergeFields=ze\user::details($box['key']['id']);
			$mergeFields['password'] = $values['password'];
			
			$mergeFields['cms_url'] = ze\link::absolute();
			
			zenario_email_template_manager::sendEmailsUsingTemplate($mergeFields['email'], $values['details/email_to_send'], $mergeFields);
		}
	}
	
	protected function getUserColsFromDetailsTab(&$box, &$fields, &$values) {
		$cols = [
			'email' => $values['details/email'],
			'email_verified' => $values['details/email_verified'],
			'salutation' => $values['details/salutation'],
			'first_name' => $values['details/first_name'],
			'last_name' => $values['details/last_name'],
			'terms_and_conditions_accepted' => $values['details/terms_and_conditions_accepted']
		];
	
		if (ze::setting('user_use_screen_name')) {
			$cols['screen_name'] = $values['details/screen_name'];
		}
	
		if (!$box['key']['id']) {
			$cols['status'] = $values['details/status'];
			$cols['creation_method'] = 'admin';
		}
		
		if ($values['details/status'] != 'contact') {
			if (!$box['key']['id']
			 || (!empty($fields['details/change_password']['pressed']) && ze\priv::check('_PRIV_EDIT_USER'))) {
				$cols['password'] = $values['details/password'];
			}
			if (ze\priv::check('_PRIV_EDIT_USER')) {
				$cols['password_needs_changing'] = $values['details/password_needs_changing'];
			}
		}
		
		return $cols;
	}
}
