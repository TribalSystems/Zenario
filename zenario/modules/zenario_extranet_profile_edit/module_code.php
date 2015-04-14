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

class zenario_extranet_profile_edit extends zenario_extranet {
	//protected $user_form = null;
	
	var $data = array();
	
	public function init() {
		if (userId()) {
			// Allow the user to confirm their screen name if not confirmed
			if (setting('user_use_screen_name') && checkRowExists('users', array('id' => userId(), 'screen_name_confirmed' => 0))) {
				if (post('confirm_screen_name')) {
					updateRow('users', array('screen_name_confirmed' => 1), array('id' => userId()));
					$this->data['Screen_Name_Confirmed'] = true;
				} else {
					$this->data['Screen_Name_Unconfirmed'] = true;
				}
			}
			$errors = array();
			$this->data['Edit'] = false;
			if (get('extranet_edit_profile')) {
				$this->data['Edit'] = true;
			} else {
				$this->data['View'] = true;
				$this->data['Edit_Permission'] = $this->setting('enable_edit_profile');
			}
			$this->data['Show_Title'] = $this->setting('show_title_message');
			
			$userFormId = $this->setting('user_form');
			$this->data['openForm'] = $this->openForm();
			$this->data['closeForm'] = $this->closeForm();
			
			if ($this->setting('enable_edit_profile') && post('extranet_update_profile')){
				$errors = zenario_user_forms::validateUserForm($userFormId, $_POST);
				
				if (empty($errors)) {
					$redirect = zenario_user_forms::saveUserForm($userFormId, $_POST, userId());
					if ($redirect) {
						$this->headerRedirect($redirect);
					}
					$this->data['message'] = $this->phrase('_PROFILE_UPDATED');
					unset($_POST['extranet_update_profile']);
				} else {
					$this->data['Edit'] = true;
					$this->data['View'] = false;
				}
			}
			$this->data['form_fields'] = zenario_user_forms::drawUserForm($userFormId, userId(), !$this->data['Edit'], $errors);
			$this->data['edit_profile_link'] = $this->refreshPluginSlotAnchor('extranet_edit_profile=1');
			$this->data['back_link'] = $this->refreshPluginSlotAnchor('');
		} else {
			$this->data['No_User'] = true;
		}
		
		/*
		$this->mode = 'modeViewProfile';			
		$this->manageCookies();
		
		if ($this->useScreenName) {
			$this->subSections['View_Screen_Name'] = true;
		}
		
		if (empty($_SESSION['extranetUserID'])) {
			return checkPriv();
			
		} elseif ($this->setting('enable_edit_profile') && get('extranet_edit_profile')) {
			$this->mode = 'modeEditProfile';
		
		} elseif ($this->setting('enable_edit_profile') && post('extranet_edit_profile')) {
			if ($this->validateProfile() && $this->updateProfile()) {
				$this->message = $this->phrase('_PROFILE_UPDATED');
				unset($_POST['extranet_update_profile']);
			} else {
				$this->mode = 'modeEditProfile';			
			}
		
		}
		
		$this->objects = getUserDetails($_SESSION['extranetUserID']);
		if ($this->mode == 'modeViewProfile') {	
			foreach ($this->objects as &$object) {
				$object = nl2br(htmlspecialchars($object));
			}
		} else {
			$this->objects['email'] = htmlspecialchars($this->objects['email']);
			$this->objects['screen_name'] = htmlspecialchars($this->objects['screen_name']);
		}
		
		*/
		return true;
	}
	
	function showSlot() {
		$this->twigFramework($this->data);
	}
	
	protected function modeViewProfile() {
		if (empty($_SESSION['extranetUserID'])) {
			if (checkPriv()) {
				echo adminPhrase('You must be logged in as an Extranet User to see this Plugin.');
			}
			return;
		}
		
		
		
		$this->subSections['View_Profile'] = true;
		
		if ($this->setting('enable_edit_profile')) {
			$this->subSections['Show_Edit_Profile'] = true;
			$this->objects['Edit_Profile_Link'] = $this->refreshPluginSlotAnchor('extranet_edit_profile=1');
		}
		
		if($this->setting('user_form')) {
			$this->showDynamicUserForm('View');
		} else {
			$this->framework('Outer', $this->objects, $this->subSections);
		}
	}
	
	protected function modeEditProfile() {
		if (empty($_SESSION['extranetUserID'])) {
			return false;
		}
		
		$this->objects['Back_Link'] = $this->refreshPluginSlotAnchor('');
		
		if($this->setting('user_form')) {
			$this->showDynamicUserForm('Edit');
		} else {
			$this->frameworkHead('Outer', 'Edit_Profile_Form', $this->objects, $this->subSections);
			echo $this->openForm(), $this->remember('extranet_edit_profile');
			$this->framework('Edit_Profile_Form', $this->objects, $this->subSections);
			echo $this->closeForm();
			$this->frameworkFoot('Outer', 'Edit_Profile_Form', $this->objects, $this->subSections);
		}
	}
	
	
	protected function validateProfile() {
		$user_form = $this->setting('user_form');
		$userId = $_SESSION['extranetUserID'];

		if($user_form && inc('zenario_user_forms')) {
			$required_fields = zenario_user_forms::validateUserformFields($_POST, $user_form, $userId);
			if(count($required_fields)) {
				$this->errors[] = array('Error' => $this->phrase('_FIELDS_REQUIRED') . ': ' . implode(', ', $required_fields));
				return false;
			}
		}
		return true;
	}
	
	protected function updateProfile() {
		
		$user_form = $this->setting('user_form');
		$userId = $_SESSION['extranetUserID'];

		$fields['last_profile_update_in_frontend'] = now();
		
		
		
		if($user_form && inc('zenario_user_forms')) {
			
			zenario_user_forms::saveUserformFields($_POST, $user_form, $userId);
		} else {
			if (!$fields = $this->validateFormFields('Edit_Profile_Form')) {
				return false;
			}
			$sql = "";
			foreach(getFields(DB_NAME_PREFIX, 'users') as $field => $details) {
				if ($field != 'screen_name' && $field != 'email' && $field != 'password') {
					if (isset($fields[$field])) {
						addFieldToSQL($sql, DB_NAME_PREFIX. 'users', $field, $fields, true, $details);
					}
				}
			}
			
			$sql .= "
			WHERE id = ". (int) $_SESSION['extranetUserID'];
			
			sqlQuery($sql);
		}
		$this->sendSignalFromForm('eventUserUpdatedProfile', (int) $userId);
		
		return true;
	}
}
