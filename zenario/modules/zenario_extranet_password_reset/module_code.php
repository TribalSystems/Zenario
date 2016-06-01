<?php
/*
 * Copyright (c) 2016, Tribal Limited
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


class zenario_extranet_password_reset extends zenario_extranet {
	
	public function init() {
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = false, $ifCookieSet = false);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$this->mode = 'modeResetPasswordStage1';
		
		if (post('extranet_send_reset_email')) {
			if ($this->sendResetEmail()) {
				$this->message = $this->phrase('You have been sent an email containing a link to reset your password.<br /><br />Please ensure you check your spam/bulk mail folder in case it is mis-filed.');
				$this->mode = 'modeLogin';
			}
		} elseif (request('extranet_reset_password') && ($userId = $this->getUserIdFromHashCode(request('hash')))) {
			if (!$this->checkResetPasswordTime($userId)) {
				// Clear hash and reset time
				updateRow('users', array('hash' => null, 'reset_password_time' => null), array('id' => $userId));
				$this->message = $this->phrase('This link has expired. To reset your password make a new request.');
				$this->mode = 'modeLogin';
			} else {
				$this->mode = 'modeResetPasswordStage2';
				if (post('extranet_change_password')) {
					if ($this->changePassword($userId)) {
						$this->message = $this->phrase('Your Password has been changed.');
						$this->mode = 'modeLoggedIn';
						// Clear hash and reset time
						updateRow('users', array('hash' => null, 'reset_password_time' => null), array('id' => $userId));
					}
				}
			}
		}
		return true;
	}
	
	// Display a form that lets the user enter the email address that they used to register,
	// and then have their password reset via email
	protected function modeResetPasswordStage1() {
		$this->addLoginLinks();
		
		$this->frameworkHead('Outer', 'Reset_Password_Form', $this->objects, $this->subSections);
			echo $this->openForm();
				$this->framework('Main_Title');
				$this->framework('Reset_Password_Form', $this->objects, $this->subSections);
			echo $this->closeForm();
		$this->frameworkFoot('Outer', 'Reset_Password_Form', $this->objects, $this->subSections);
	}
	
	// Display a form that lets the user enter a new password and confirmation
	protected function modeResetPasswordStage2() {
		$this->objects['hash'] = request('hash');
		$this->objects['extranet_reset_password'] = request('extranet_reset_password');
		$this->frameworkHead('Outer', 'Reset_Password_Form_Passwords', $this->objects, $this->subSections);
			echo $this->openForm();
				$this->framework('Main_Title');
				$this->framework('Reset_Password_Form_Passwords', $this->objects, $this->subSections);
			echo $this->closeForm();
		$this->frameworkFoot('Outer', 'Reset_Password_Form_Passwords', $this->objects, $this->subSections);
	}
	
	private function sendResetEmail() {
		if (!$this->validateFormFields('Reset_Password_Form')) {
			// Function displays error message so no action here
		} elseif (!$userDetails = $this->getDetailsFromEmail(post('extranet_email'))) {
			$this->errors[] = array('Error' => $this->phrase('Sorry, we couldn\'t find an account associated with that email address.'));
		} else {
			if (checkRowExists('users', array('email' => post('extranet_email'), 'status' => 'pending', 'email_verified' => false  ))) {
				$this->errors[] = array('Error' => $this->phrase('You have not yet verified your email address. Please click on the link in your verification email.'));
			} else {
				updateUserHash($userDetails['id']);
				updateRow('users', array('reset_password_time' => now()), array('id' => $userDetails['id']));
				$userDetails = $this->getDetailsFromEmail(post('extranet_email'));
				$userDetails['ip_address'] = visitorIP();
				$userDetails['cms_url'] = absCMSDirURL();
				$userDetails['reset_url'] = $this->linkToItem($this->cID, $this->cType, $fullPath = true, $request = '&extranet_reset_password=1&hash='. $userDetails['hash']);
				if (inc('zenario_email_template_manager')){
					if (zenario_email_template_manager::sendEmailsUsingTemplate($userDetails['email'],$this->setting('password_reset_email_template'),$userDetails,array())){
						return true;
					} else {
						$this->errors[] = array('Error' => $this->phrase('There appears to be a problem with our email system. Please try to retrieve your password again later.'));
					}
				} else {
					$this->errors[] = array('Error' => $this->phrase('There appears to be a problem with our email system. Please try to retrieve your password again later.'));
				}
			}
		}
		return false;
	}
	
	private function getUserIdFromHashCode($hash){
		if ($hash && ($userId = (int) getRow("users","id",array('hash'=>$hash)))){
			return $userId;
		} else {
			return 0;
		}
	}
	
	// Attempt to change a user's password
	private function changePassword($userId) {
		$errors = $this->validatePassword(post('extranet_new_password'),post('extranet_new_password_confirm'),post('extranet_password'),get_class($this),$userId);
		
		if (count($errors)) {
			$this->errors = array_merge ($this->errors, $errors);
			return false;
		} else {
			setUsersPassword($userId, post('extranet_new_password'));
			return true;
		}
	}
	
	private function checkResetPasswordTime($userId) {
		$userPasswordResetTimeStr = getRow('users', 'reset_password_time', $userId);
		if (!$userPasswordResetTimeStr) {
			return false;
		} else {
			$userPasswordResetTimePlusOneDay = strtotime($userPasswordResetTimeStr. '+ 1 day');
			$now = strtotime(now());
			return ($userPasswordResetTimePlusOneDay > $now);
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//overright extranet save
	}

}