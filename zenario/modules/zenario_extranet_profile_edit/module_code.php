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
				$screenName = getRow('users', 'screen_name', userId());
				if (post('confirm_screen_name')) {
					updateRow('users', array('screen_name_confirmed' => 1), array('id' => userId()));
					$this->data['screen_name_confirmed_message'] = $this->phrase('You\'ve confirmed you\'re happy to use "[[screen_name]]" as your public screen name.', array('screen_name' => $screenName));
					$this->data['Screen_Name_Confirmed'] = true;
				} else {
					$this->data['screen_name_confirmed_info'] = $this->phrase('It looks like you\'ve not confirmed that you\'re happy with your screen name, "[[screen_name]]". This name will be shown in messages you post on this site. If you\'d like to change it please click the "Edit profile" button, or if you\'re happy with it please click here to confirm:', array('screen_name' => $screenName));
					$this->data['Screen_Name_Unconfirmed'] = true;
				}
			}
			$errors = array();
			$data = userId();
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
				$data = $_POST;
				$errors = zenario_user_forms::validateUserForm($userFormId, $data);
				
				if (empty($errors)) {
					$redirect = zenario_user_forms::saveUserForm($userFormId, $data, userId());
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
			$this->data['form_fields'] = zenario_user_forms::drawUserForm($userFormId, $data, !$this->data['Edit'], $errors, 0, $this->containerId);
			$this->data['edit_profile_link'] = $this->refreshPluginSlotAnchor('extranet_edit_profile=1');
			$this->data['back_link'] = $this->refreshPluginSlotAnchor('');
		} else {
			$this->data['No_User'] = true;
		}
		return true;
	}
	
	function showSlot() {
		$this->twigFramework($this->data);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				if ($formId = $values['first_tab/profile_form']) {
					$formProperties = getRow('user_forms', array('save_data', 'user_duplicate_email_action'), $formId);
					$fields['first_tab/form_warning']['hidden'] = $formProperties['save_data'] && ($formProperties['user_duplicate_email_action'] == 'overwrite');
					
				}
				break;
		}
	}

}
