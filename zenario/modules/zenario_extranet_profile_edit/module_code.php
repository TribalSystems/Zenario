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

class zenario_extranet_profile_edit extends zenario_user_forms {
	
	public function init() {
		$userId = userId();
		if (!$userId) {
			$this->data['extranet_no_user'] = true;
			return true;
		}
		
		$this->allowSave = $this->setting('enable_edit_profile');
		
		$rv = parent::init();
		if (get('extranet_edit_profile')) {
			$this->data['extranet_profile_mode_class'] = 'extranet_edit_profile';
		} else {
			$this->data['extranet_profile_mode_class'] = 'extranet_view_profile';
			// Screen name confirmed
			if (setting('user_use_screen_name') && checkRowExists('users', array('id' => $userId, 'screen_name_confirmed' => 0))) {
				
				$screenName = getRow('users', 'screen_name', $userId);
				if (post('extranet_confirm_screen_name')) {
					updateRow('users', array('screen_name_confirmed' => 1), array('id' => $userId));
					$this->data['extranet_screen_name_confirmed_message'] = $this->phrase('You\'ve confirmed you\'re happy to use "[[screen_name]]" as your public screen name.', array('screen_name' => $screenName));
				} else {
					$this->data['extranet_openForm'] = $this->openForm();
					$this->data['extranet_closeForm'] = $this->closeForm();
					$this->data['extranet_screen_name_unconfirmed'] = true;
					$this->data['extranet_screen_name_confirmed_info'] = $this->phrase('It looks like you\'ve not confirmed that you\'re happy with your screen name, "[[screen_name]]". This name will be shown in messages you post on this site. If you\'d like to change it please click the "Edit profile" button, or if you\'re happy with it please click here to confirm:', array('screen_name' => $screenName));
				}
			}
		}
		return $rv;
	}
	
	protected function successfulFormSubmit() {
		$this->messages['global_top'] = $this->phrase($this->setting('profile_updated'));
	}
	
	protected function getFormTitle($form) {
		$title = '';
		if ($this->setting('show_title_message')) {
			$title .= '<h1>';
			if (get('extranet_edit_profile')) {
				$title .= $this->phrase($this->setting('edit_profile_title'));
			} else {
				$title .= $this->phrase($this->setting('view_profile_title'));
			}
			$title .= '</h1>';
		}
		return $title;
	}
	
	protected function isFormReadonly($form) {
		return !get('extranet_edit_profile');
	}
	
	protected function showSubmitButton() {
		return get('extranet_edit_profile');
	}
	
	protected function getCustomButtons($page, $onLastPage, $position) {
		if ($position == 'first' && !get('extranet_edit_profile') && $onLastPage && $this->setting('enable_edit_profile')) {
			return '<div class="extranet_links"><a ' . $this->refreshPluginSlotAnchor('extranet_edit_profile=1') . ' class="nice_button">' . $this->phrase($this->setting('edit_profile_button_text')) . '</a></div>';
		} elseif ($position == 'last' && get('extranet_edit_profile') && $onLastPage) {
			return '<a ' . $this->refreshPluginSlotAnchor('') . ' class="nice_button">' . $this->phrase($this->setting('cancel_button_text')) . '</a>';
		}
		return false;
	}
	
}
