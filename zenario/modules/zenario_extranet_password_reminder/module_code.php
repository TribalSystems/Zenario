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


class zenario_extranet_password_reminder extends zenario_extranet {
	
	public function init() {
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = false, $ifCookieSet = false);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$this->mode = 'modeForgotPassword';
		
		// Do not show plugin if passwords are encrypted
		if (!setting('plaintext_extranet_user_passwords')) {
			if (!adminId()) {
				return false;
			} else {
				$this->subSections['Admin_Error'] = true;
				$this->objects['Admin_Message'] = $this->phrase('Extranet users are unable to see this plugin because passwords are not stored in plain text, so cannot be looked up.');
			}
		}
		
		$this->manageCookies();
		
		if (post('extranet_forgot_password')) {
			if ($this->forgotPassword()) {
				$this->message = $this->phrase('_EMAIL_SENT');
				$this->mode = 'modeLogin';
			}
		}
		
		return true;
	}

	
	//Display a form that lets the user enter the email address that they used to register,
	//and then have their password emailed to them
	public function modeForgotPassword() {
		$this->addLoginLinks();
		
		$this->frameworkHead('Outer', 'Forget_Password_Form', $this->objects, $this->subSections);
			echo $this->openForm();
					$this->framework('Forget_Password_Form', $this->objects, $this->subSections);
			echo $this->closeForm();
		$this->frameworkFoot('Outer', 'Forget_Password_Form', $this->objects, $this->subSections);

	}


	function forgotPassword() {
		if (!$this->validateFormFields('Forget_Password_Form')) {
		
		} elseif (!$userDetails = $this->getDetailsFromEmail(post('extranet_email'))) {
			$this->errors[] = array('Error' => $this->phrase('_ERROR_EMAIL_UNASSOCIATED'));
			
		} else {
			if (checkRowExists('users', array('email' => post('extranet_email'), 'status' => 'pending', 'email_verified' => false  ))) {
				$this->errors[] = array('Error' => $this->phrase('_ERROR_EMAIL_NOT_VERIFIED'));
			
			} else {
				// If user has an encrypted password, display error
				if ($userDetails['password_salt'] !== null) {
					$this->errors[] = array('Error' => $this->phrase('Your password is encrypted so we cannot send you a reminder.'));
				} else {
					$userDetails['ip_address'] = visitorIP();
					$userDetails['cms_url'] = absCMSDirURL();
					
					if (inc('zenario_email_template_manager')){
							if (zenario_email_template_manager::sendEmailsUsingTemplate($userDetails['email'],$this->setting('password_reminder_email_template'),$userDetails,array())){
								return true;
							} else {
								$this->errors[] = array('Error' => $this->phrase('_COULD_NOT_SEND_RECOVER_PASSWORD_EMAIL'));
							}
					} else {
						$this->errors[] = array('Error' => $this->phrase('_COULD_NOT_SEND_RECOVER_PASSWORD_EMAIL'));
					}
				}
			}
		}
		
		return false;
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	}

}