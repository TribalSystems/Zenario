<?php
/*
 * Copyright (c) 2024, Tribal Limited
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

use ZxcvbnPhp\Zxcvbn;

class zenario_users__admin_boxes__user__change_password extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//If it looks like a site is supposed to be using encryption, but it's not set up properly,
		//show an error message.
		ze\pdeAdm::showNoticeOnFABIfConfIsBad($box);
		
		
		if ($box['key']['id']) {
			ze\priv::exitIfNot('_PRIV_VIEW_USER');
			
			$user = ze\user::details($box['key']['id']);
			
			$userType = $user['status'] == 'contact' ? 'contact' : 'user';
			$box['title'] = ze\admin::phrase('Changing the password for [[user_type]] "[[identifier]]"', ['identifier' => $user['identifier'], 'user_type' => $userType]);
			
			$values['details/status'] = $user['status'];
			$values['details/first_name'] = $user['first_name'];
			$values['details/last_name'] = $user['last_name'];
			$values['details/screen_name'] = $user['screen_name'];
			
			$fields['details/password_reset_email']['value'] = ze::setting('default_password_reset_email_template');
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$fields['details/password']['type'] = empty($fields['details/reveal_password']['pressed'])? 'password' : 'text';
		$fields['details/reveal_password']['value'] = empty($fields['details/reveal_password']['pressed'])? 'Reveal' : 'Hide';
		
		if (empty($fields['details/password']['hidden'])) {
			//Validate password: show whether it matches the requirements or not,
			//but don't show an admin box error if it doesn't.
			//Errors will be shown when validating.

			$passwordMessageSnippet = '';
			if (!$values['details/password']) {
				$passwordMessageSnippet = 
					'<div>
						<span id="snippet_password_message" class="title_orange">' . ze\admin::phrase('Please enter a password') . '</span>
					</div>';
			} else {
				$passwordLengthValidation = ze\user::checkPasswordStrength($values['details/password']);
				if (!$passwordLengthValidation['password_matches_requirements']) {
					$passwordMessageSnippet = 
						'<div>
							<span id="snippet_password_message" class="title_red">' . ze\admin::phrase('Password does not match the requirements') . '</span>
						</div>';
				} else {
					$minScore = (int) ze::setting('min_extranet_user_password_score');

					$zxcvbn = new ZxcvbnPhp\Zxcvbn();
					$result = $zxcvbn->passwordStrength($values['details/password']);

					if ($result && isset($result['score'])) {
						switch ($result['score']) {
							case 4: //is very unguessable (guesses >= 10^10) and provides strong protection from offline slow-hash scenario
								if ($minScore < 4) {
									$phrase = 'Password is very strong and exceeds requirements (score 4, max)';
								} elseif ($minScore == 4) {
									$phrase = 'Password matches the requirements (score 4)';
								}

								$passwordMessageSnippet = 
									'<div>
										<span id="snippet_password_message" class="title_green">' . ze\admin::phrase($phrase) . '</span>
									</div>';
								break;
							case 3: //is safely unguessable (guesses < 10^10), offers moderate protection from offline slow-hash scenario
								if ($minScore == 4) {
									$passwordMessageSnippet = 
									'<div>
										<span id="snippet_password_message" class="title_red">' . ze\admin::phrase('Password is too easy to guess (score [[score]])', ['score' => (int) $result['score']]) . '</span>
									</div>';
								} elseif ($minScore < 4) {
									$passwordMessageSnippet = 
										'<div>
											<span id="snippet_password_message" class="title_green">' . ze\admin::phrase('Password matches the requirements (score 3)') . '</span>
										</div>';
								}
								break;
							case 2: //is somewhat guessable (guesses < 10^8), provides some protection from unthrottled online attacks
								if ($minScore == 2) {
									$passwordMessageSnippet = 
										'<div>
											<span id="snippet_password_message" class="title_orange">' . ze\admin::phrase('Password is too easy to guess (score [[score]])', ['score' => (int) $result['score']]) . '</span>
										</div>';
								} elseif ($minScore > 2) {
									$passwordMessageSnippet = 
										'<div>
											<span id="snippet_password_message" class="title_red">' . ze\admin::phrase('Password is too easy to guess (score [[score]])', ['score' => (int) $result['score']]) . '</span>
										</div>';
								}
								break;
							case 1: //is still very guessable (guesses < 10^6)
							case 0: //s extremely guessable (within 10^3 guesses)
							default:
								$passwordMessageSnippet = 
									'<div>
										<span id="snippet_password_message" class="title_red">' . ze\admin::phrase('Password is too easy to guess (score [[score]])', ['score' => (int) $result['score']]) . '</span>
									</div>';
								break;
						}
					}
				}
			}
			
			$box['tabs']['details']['fields']['password_message']['post_field_html'] = $passwordMessageSnippet;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (!empty($box['tabs']['details']['edit_mode']['on'])) {
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
							$minScore = (int) ze::setting('min_extranet_user_password_score');
							$zxcvbn = new ZxcvbnPhp\Zxcvbn();
							$result = $zxcvbn->passwordStrength($values['details/password']);

							if ($result && isset($result['score'])) {
								if ($result['score'] < $minScore) {
									$box['tabs']['details']['errors'][] = ze\admin::phrase('Password is too easy to guess (score [[score]]).', ['score' => (int) $result['score']]);
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
							}
						}
						$box['tabs']['details']['fields']['password_message']['post_field_html'] = $passwordMessageSnippet;
					}
				}
			}
			
			if (empty($fields['details/change_password']['pressed'])  && !$values['details/password_needs_changing']) {
				//Do not allow a situation where the admin does not set a password,
				//AND does not require the user to change their password.
				$box['tabs']['details']['errors']['choose_password_action'] = ze\admin::phrase('Please select an action, i.e. whether to set a new password, to require the user to change password on next login, or both.');
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		if (!empty($box['tabs']['details']['edit_mode']['on'])) {
			ze\priv::exitIfNot('_PRIV_EDIT_USER');
			
			$cols = [
				'first_name' => $values['details/first_name'],
				'last_name' => $values['details/last_name'],
				'screen_name' => $values['details/screen_name'],
			];
	
			ze\admin::setUserLastUpdated($cols, !$box['key']['id']);
	
			if ($values['details/status'] != 'contact') {
				if (!empty($fields['details/change_password']['pressed']) && ze\priv::check('_PRIV_EDIT_USER')) {
					$cols['password'] = $values['details/password'];
					$cols['reset_password_time'] = ze\date::now();
				}
				
				if (ze\priv::check('_PRIV_EDIT_USER')) {
					$cols['password_needs_changing'] = $values['details/password_needs_changing'];
				}
			}
			
			$box['key']['id'] = ze\userAdm::save($cols, $box['key']['id']);
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_EDIT_USER');
		
		if (!empty($fields['details/send_password_reset_email_upon_save']['pressed']) && $values['details/password_reset_email'] && ze\module::inc('zenario_email_template_manager')) {
			$mergeFields = ze\user::userDetailsForEmails($box['key']['id']);
			$mergeFields['cms_url'] = ze\link::absolute();
			
			if (!empty($fields['details/change_password']['pressed']) && $values['details/password']) {
				$mergeFields['password'] = $values['details/password'];
			} else {
				$mergeFields['password'] = '(' . ze\admin::phrase('password not changed') . ')';
			}
			
			//It's possible that an admin changes the password and sends an email,
			//but it's also possible to just send an email without changing the password.
			$mergeFields['password_reset_message'] = '';
			
			if (!empty($fields['details/change_password']['pressed']) && $values['details/password']) {
				$mergeFields['password_reset_message'] .= '<p>' . $this->phrase('Your account password has been reset.') . '</p>';
				$mergeFields['password_reset_message'] .= '<p>' . $this->phrase('Your new password is: [[password]]', ['password' => $mergeFields['password']]) . '</p>';
				
				if ($values['details/password_needs_changing']) {
					$mergeFields['password_reset_message'] .= '<p>' . $this->phrase('You will need to change this new password to one you can remember upon logging in.') . '</p>';
				}
			} else {
				$mergeFields['password_reset_message'] .= '<p>' . $this->phrase('Your account has been updated.') . '</p>';
				
				if ($values['details/password_needs_changing']) {
					$mergeFields['password_reset_message'] .= '<p>' . $this->phrase('You will need to change your password upon logging in.') . '</p>';
				}
			}
			
			
			zenario_email_template_manager::sendEmailsUsingTemplate($mergeFields['email'], $values['details/password_reset_email'], $mergeFields, [], [], $disableHTMLEscaping = true);
		}
	}
}