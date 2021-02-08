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


class zenario_extranet_change_password extends zenario_extranet {
	
	public function init() {
		$this->registerPluginPage();
		
		$this->mode = 'modeChangePassword';
		if (!ze\user::id()) {
			return ze\priv::check();
		}
		
		$this->manageCookies();
		
		if ($_POST['extranet_change_password'] ?? false) {
		
			if ($this->changePassword()) {
			
				$this->message = $this->phrase('_PASSWORD_CHANGED');
				$this->mode = 'modeLoggedIn';
				//send change password notification
				if ($this->setting('zenario_extranet_change_password__send_notification_email') && $this->setting('zenario_extranet_change_password__notification_email_template')
		             && ze\module::inc('zenario_email_template_manager')) {
			         
			         $userId = ze\user::id();
			         $userDetails = ze\row::get("users", ['email', 'first_name', 'last_name'], ['id'=> $userId]);
			         $userDetails['cms_url'] = ze\link::absolute();

			         //Send the chosen email template using the Email Template Manager
			         zenario_email_template_manager::sendEmailsUsingTemplate(
				        $userDetails['email'],
				        $this->setting('zenario_extranet_change_password__notification_email_template'),
				        $userDetails);
				
				}
			}
		}
		
		return true;
	}

	//Display a form to let the user change their password
	protected function modeChangePassword() {
		$this->addLoggedInLinks();
		
		if (!ze\user::id()) {
			if (ze\priv::check()) {
				echo ze\admin::phrase('You must be logged in as an extranet user to see this plugin.');
			}
			return;
		}
		
		$this->objects['Password_Requirements'] = ze\user::displayPasswordRequirementsNoteVisitor();
		
		echo $this->openForm($onSubmit = '', $extraAttributes = '', $action = false, $scrollToTopOfSlot = true, $fadeOutAndIn = true);
			$this->subSections['Change_Password_Form'] = true;
			$this->framework('Outer', $this->objects, $this->subSections);
		echo $this->closeForm();
		
		$this->callScript('zenario', 'updatePasswordNotifier', '#extranet_new_password', '#password_message');
	}
	
	
	//Attempt to change a user's password
	protected function changePassword() {
		
		$errors = $this->validatePassword($_POST['extranet_new_password'] ?? false,($_POST['extranet_new_password_confirm'] ?? false),($_POST['extranet_password'] ?? false),get_class($this),ze\user::id());
		
		if (count($errors)) {
			$this->errors = array_merge ($this->errors, $errors);
			return false;
		} else {
			ze\userAdm::setPassword(ze\user::id(), ($_POST['extranet_new_password'] ?? false));
			return true;
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	}	

}
