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


class zenario_extranet_change_password extends zenario_extranet {
	
	public function init() {
		
		$this->mode = 'modeChangePassword';
		if (!userId()) {
			return checkPriv();
		}
		
		$this->manageCookies();
		
		if (post('extranet_change_password')) {
			if ($this->changePassword()) {
				$this->message = $this->phrase('_PASSWORD_CHANGED');
				$this->mode = 'modeLoggedIn';
			}
		}
		
		return true;
	}

	//Display a form to let the user change their password
	protected function modeChangePassword() {
		$this->addLoggedInLinks();
		
		if (!userId()) {
			if (checkPriv()) {
				echo adminPhrase('You must be logged in as an Extranet User to see this Plugin.');
			}
			return;
		}
		
		$this->frameworkHead('Outer', 'Change_Password_Form', $this->objects, $this->subSections);
			echo $this->openForm();
					$this->framework('Change_Password_Form', $this->objects, $this->subSections);
			echo $this->closeForm();
		$this->frameworkFoot('Outer', 'Change_Password_Form', $this->objects, $this->subSections);
	}
	
	
	//Attempt to change a user's password
	protected function changePassword() {
		
		$errors = $this->validatePassword(post('extranet_new_password'),post('extranet_new_password_confirm'),post('extranet_password'),get_class($this),userId());
		
		if (count($errors)) {
			$this->errors = array_merge ($this->errors, $errors);
			return false;
		} else {
			setUsersPassword(userId(), post('extranet_new_password'));
			return true;
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	}	

}