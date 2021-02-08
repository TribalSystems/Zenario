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


class zenario_extranet_change_email extends zenario_extranet {
	
	
	public function init() {
		$this->registerPluginPage();
		
		if (($_POST['action'] ?? false)=='prepare_email_change'){
			if (ze\user::id()) {
				if ($this->prepareEmailChange()){
					$this->mode = 'modeLoggedIn';
				} else {
					$this->mode = 'modeChangeEmailForm';
				}
			} else {
				$this->mode = 'modeLogin';
			}
		} elseif (($_GET['action'] ?? false)=='confirm_email'){
			if ($this->changeEmailAndLogUserIn()){
				$this->mode = 'modeLoggedIn';
			} else {
				$this->mode = 'modeLogin';
			}
		} else {
			if (ze\user::id()) {
				$this->mode = 'modeChangeEmailForm';
			} else {
				$this->mode = 'modeLogin';
			}
		} 
		return true;
	}

	public function modeLogin(){
		echo $this->phrase("_PLEASE_LOGIN");
	}

	public function changeEmailAndLogUserIn(){
		if (!empty($_GET['hash'])){
			$sql = "SELECT 
						user_id,
						new_email,
						hash
					FROM " 
						. DB_PREFIX . ZENARIO_EXTRANET_CHANGE_EMAIL_PREFIX . "new_user_emails 
					WHERE
						hash ='" . ze\escape::sql($_GET['hash'] ?? false) . "'";
			
			$result = ze\sql::select($sql);
			if ($row = ze\sql::fetchAssoc($result)){
				$userDetails = ze\user::details($row['user_id']);
			
				ze\row::update(
					"users",
					[
						'last_profile_update_in_frontend' => ze\date::now(),
						'email' => $row['new_email']
					],
					['id' => (int)$row['user_id']]
				);
				
				if (!$userDetails['first_name'] && !$userDetails['last_name']) {
					ze\userAdm::generateIdentifier($row['user_id']);
				}
				
				ze\module::sendSignal("eventUserEmailChanged", ["user_id" => $row['user_id'], "old_email" => $userDetails['email'], "new_email" => $row['new_email']]);
				
				//Send a message to both the old and the new email, so the user can see there was a change.
				if ($emailTemplate = $this->setting('email_change_successful_email_template')) {
					$mergeFields = [
						'cms_url' => ze\link::absolute(),
						'first_name' => $userDetails['first_name'],
						'last_name' => $userDetails['last_name'],
						'previous_email' => $userDetails['email'],
						'new_email' => $row['new_email']
					];
				
					//Old
					zenario_email_template_manager::sendEmailsUsingTemplate($userDetails['email'], $emailTemplate, $mergeFields);
					//New
					zenario_email_template_manager::sendEmailsUsingTemplate($row['new_email'], $emailTemplate, $mergeFields);
				}
				
				$sql = "DELETE FROM " 
						. DB_PREFIX . ZENARIO_EXTRANET_CHANGE_EMAIL_PREFIX . "new_user_emails 
					WHERE
						hash ='" . ze\escape::sql($_GET['hash'] ?? false) . "'";
				ze\sql::update($sql);
				
				ze\user::logIn($row['user_id']);
				$this->message = $this->phrase('_EMAIL_CHANGED');
				//Set email verified flag
				ze\row::update('users', ['email_verified' => 1], ['id' => $row['user_id']]);
				return true;
			} else {
				$this->message = $this->phrase('_INVALID_LINK');
				return true;
			}
		}
		return false;
	}

	public function modeChangeEmailForm(){
		$this->addLoggedInLinks();
		
		$loggedInUserData = ze\user::details(ze\user::id());
		
		echo $this->openForm($onSubmit = '', $extraAttributes = '', $action = false, $scrollToTopOfSlot = true, $fadeOutAndIn = true);
			$this->subSections['Change_Email_Form'] = true;
			$this->objects['Current_Email_Phrase'] = $this->phrase('Your current email address is "[[current_email]]".', ['current_email' => $loggedInUserData['email']]);
			$this->framework('Outer', $this->objects, $this->subSections);
		echo $this->closeForm();
	}

	private function prepareEmailChange(){

		$loggedInUserData = ze\user::details(ze\user::id());
		
		$this->validateFormFields('Change_Email_Form');
		if (!$this->errors){
			
			if (!ze\user::checkPassword(ze\user::id(), ($_POST['extranet_password'] ?? false))) {
				$this->errors[] = ['Error'=>$this->phrase('_INCORRECT_PASSWORD')];
			}


			if (ze\row::exists('users', ['email' => ($_POST['extranet_email'] ?? false)])) {
				if ($loggedInUserData['email'] == $_POST['extranet_email']) {
					$this->errors[] = ['Error'=>$this->phrase('Error. The new email address is the same as the current one.')];
				} else {
					$this->errors[] = ['Error'=>$this->phrase('_EMAIL_ALREADY_IN_USE')];
				}
			}

			if(!$this->errors){
				if ($this->setting('confirmation_email_template') && ze\module::inc('zenario_email_template_manager')){
					$hash = md5(($_POST['extranet_email'] ?? false) . ze\link::host() . time()) . time();
					$sql = "REPLACE INTO " 
								. DB_PREFIX . ZENARIO_EXTRANET_CHANGE_EMAIL_PREFIX . "new_user_emails 
							SET 
								user_id = " . (int) ze\user::id() . ",
								new_email = '" . ze\escape::sql($_POST['extranet_email'] ?? false) . "',
								hash = '" . $hash . "'";
					ze\sql::update($sql);

					$userDetails = ze\user::details(ze\user::id());
					$userDetails['new_email'] =  $_POST['extranet_email'] ?? false;
					$userDetails['hash'] =  $hash;
					$userDetails['cms_url'] = ze\link::absolute();
					$userDetails['email_confirmation_link'] = $this->linkToItem($this->cID, $this->cType, $fullPath = true, $request = '&action=confirm_email&hash='. $hash);
					
					if (!zenario_email_template_manager::sendEmailsUsingTemplate($_POST['extranet_email'] ?? false,$this->setting('confirmation_email_template'),$userDetails,[])){
						$this->errors[] = ['Error'=>$this->phrase('_COULD_NOT_SEND_CONFIRMATION_EMAIL')];
						return false;
					}

					$this->message = $this->phrase('_EMAIL_CHANGE_PREPARED');
					$this->mode = 'modeLoggedIn';
					return true;
				} else {
					$this->errors[] = ['Error'=>$this->phrase('_COULD_NOT_SEND_CONFIRMATION_EMAIL')];
					return false;
				}
			} else {
				return false;
			}
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	}	
	
}