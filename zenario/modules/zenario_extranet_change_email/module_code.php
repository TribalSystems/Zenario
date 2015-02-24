<?php
/*
 * Copyright (c) 2015, Tribal Limited
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
		if (post('action')=='prepare_email_change'){
			if (userId()) {
				if ($this->prepareEmailChange()){
					$this->mode = 'modeLoggedIn';
				} else {
					$this->mode = 'modeChangeEmailForm';
				}
			} else {
				$this->mode = 'modeLogin';
			}
		} elseif (get('action')=='confirm_email'){
			if ($this->changeEmailAndLogUserIn()){
				$this->mode = 'modeLoggedIn';
			} else {
				$this->mode = 'modeLogin';
			}
		} else {
			if (userId()) {
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
						. DB_NAME_PREFIX . ZENARIO_EXTRANET_CHANGE_EMAIL_PREFIX . "new_user_emails 
					WHERE
						hash ='" . sqlEscape(arrayKey($_GET,'hash')) . "'";
			
			$result = sqlQuery($sql);
			if ($row = sqlFetchAssoc($result)){
				$userDetails = getUserDetails($row['user_id']);
			
				$sql = "UPDATE "
							. DB_NAME_PREFIX . "users 
						SET 
							last_profile_update_in_frontend = NOW(),
							screen_name = IF(email=screen_name,'" . $row['new_email'] . "',screen_name),
							email= '" . $row['new_email'] . "'
						WHERE 
							id = " . (int) $row['user_id'];
				sqlQuery($sql);
				
				sendSignal("eventUserEmailChanged",array("user_id" => $row['user_id'], "old_email" => $userDetails['email'], "new_email" => $row['new_email']));
				
				$sql = "DELETE FROM " 
						. DB_NAME_PREFIX . ZENARIO_EXTRANET_CHANGE_EMAIL_PREFIX . "new_user_emails 
					WHERE
						hash ='" . sqlEscape(arrayKey($_GET,'hash')) . "'";
				sqlQuery($sql);
				
				logUserIn($row['user_id']);
				$this->message = $this->phrase('_EMAIL_CHANGED');
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
		
		$this->frameworkHead('Outer', 'Change_Email_Form', $this->objects, $this->subSections);
			echo $this->openForm();
					$this->framework('Change_Email_Form', $this->objects, $this->subSections);
			echo $this->closeForm();
		$this->frameworkFoot('Outer', 'Change_Email_Form', $this->objects, $this->subSections);
	}

	private function prepareEmailChange(){

		$this->validateFormFields('Change_Email_Form');
		if (!$this->errors){
			
			if (!checkUsersPassword(userId(), arrayKey($_POST,'extranet_password'))) {
				$this->errors[] = array('Error'=>$this->phrase('_INCORRECT_PASSWORD'));
			}


			if (checkRowExists('users', array('email' => arrayKey($_POST,'extranet_email')))) {
				$this->errors[] = array('Error'=>$this->phrase('_EMAIL_ALREADY_IN_USE'));
			}

			if(!$this->errors){
				if ($this->setting('confirmation_email_template') && inc('zenario_email_template_manager')){
					$hash = md5(arrayKey($_POST,'extranet_email') . httpHost() . time()) . time();
					$sql = "REPLACE INTO " 
								. DB_NAME_PREFIX . ZENARIO_EXTRANET_CHANGE_EMAIL_PREFIX . "new_user_emails 
							SET 
								user_id = " . (int) userId() . ",
								new_email = '" . sqlEscape(arrayKey($_POST,'extranet_email')) . "',
								hash = '" . $hash . "'";
					sqlQuery($sql);

					$userDetails = getUserDetails(userId());
					$userDetails['new_email'] =  arrayKey($_POST,'extranet_email');
					$userDetails['hash'] =  $hash;
					$userDetails['ip_address'] = visitorIP();
					$userDetails['cms_url'] = absCMSDirURL();
					$userDetails['email_confirmation_link'] = $this->linkToItem($this->cID, $this->cType, $fullPath = true, $request = '&action=confirm_email&hash='. $hash);
					
					if (!zenario_email_template_manager::sendEmailsUsingTemplate(arrayKey($_POST,'extranet_email'),$this->setting('confirmation_email_template'),$userDetails,array())){
						$this->errors[] = array('Error'=>$this->phrase('_COULD_NOT_SEND_CONFIRMATION_EMAIL'));
						return false;
					}

					$this->message = $this->phrase('_EMAIL_CHANGE_PREPARED');
					$this->mode = 'modeLoggedIn';
					return true;
				} else {
					$this->errors[] = array('Error'=>$this->phrase('_COULD_NOT_SEND_CONFIRMATION_EMAIL'));
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