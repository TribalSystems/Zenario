<?php
/*
 * Copyright (c) 2023, Tribal Limited
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
	
	protected $allowCloseAccount;
	
	public function init() {
		$this->registerPluginPage();
		
		$userId = ze\user::id();
		if (!$userId) {
			$this->data['extranet_no_user'] = true;
			return true;
		}
		
		//This plugin must have a form selected
		$formId = $this->setting('user_form');
		if (!$formId) {
			if (ze\admin::id()) {
				$this->data['form_HTML'] = '<p class="error">' . htmlspecialchars(ze\admin::phrase("No form has been selected, please edit plugin settings to select a form.")) . '</p>';
			}
			return true;
		}
		
		$rv = parent::init();
		
		if (!$this->form) {
			if (ze\admin::id()) {
				$this->data['form_HTML'] = '<p class="error">' . htmlspecialchars(ze\admin::phrase("The selected form could not be found, please edit plugin settings to select a different form.")) . '</p>';
			}
			return true;
		}
		
		$this->allowCloseAccount = $this->setting('allow_user_to_delete_their_account');
		
		$editing = false;
		if (!empty($_GET['extranet_edit_profile']) || !empty($this->errors)) {
			$this->data['extranet_profile_mode_class'] = 'extranet_edit_profile';
			$editing = true;
		} else {
			$this->data['extranet_profile_mode_class'] = 'extranet_view_profile';
			if (!empty($_POST['submitForm'])) {
				$editing = true;
			}
			
			if ($this->allowCloseAccount && ze::post('closeAccount')) {
				if (ze::post('confirmCloseAccount')) {
					$userId = ze\user::id();
					$userDetails = ze\user::userDetailsForEmails($userId);
				
					//Log the user out and then delete
					ze\user::logOut();
					ze\userAdm::delete($userId, ($this->setting('delete_account_options') == 'delete_all_data'));
					
					if ($this->setting('notify_admin_when_user_account_deleted')) {
						$adminNotificationEmailAddresses = $this->setting('user_account_deleted_admin_notification_addresses');
						
						$addressToOverriddenBy = false;
						$subject = 'User closed their account';
						$body = '
							<p>Dear admin,</p>
							<p>The user ' . $userDetails['first_name'] . ' ' . $userDetails['last_name'] . ' has closed their account.<p>
							<p>&nbsp;</p>
							<p>This is an auto-generated email from '.ze\link::absolute().'</p>';
						
						foreach (ze\ray::explodeAndTrim($adminNotificationEmailAddresses) as $adminEmail) {
							ze\server::sendEmailAdvanced($subject, $body, $adminEmail, $addressToOverriddenBy);
						}
					}
					
					$userId = $this->userId = false;
					
					//Redirect the user to the logout page if one exists, or the home page otherwise.
					if ($logoutPage = ze\row::get('special_pages', ['equiv_id', 'content_type'], ['page_type' => 'zenario_logout'])) {
						$cID = $logoutPage['equiv_id'];
						$cType = $logoutPage['content_type'];
					} else {
						$cID = ze::$homeEquivId;
						$cType = ze::$homeCType;
					}
					
					ze\content::langEquivalentItem($cID, $cType);
					$redirectURL = ze\link::toItem($cID, $cType);
					$this->headerRedirect($redirectURL);
					
					return true;
				} else {
					$this->data['confirm_close_account_error'] = $this->phrase('Please confirm that you wish to close your account.'); 
				}
			}
			
			//Screen name confirmed
			if (ze::setting('user_use_screen_name') && ze\row::exists('users', ['id' => $userId, 'screen_name_confirmed' => 0])) {
				$screenName = ze\row::get('users', 'screen_name', $userId);
				if (!empty($_POST['extranet_confirm_screen_name'])) {
					ze\row::update('users', ['screen_name_confirmed' => 1], ['id' => $userId]);
					$this->data['extranet_screen_name_confirmed_message'] = $this->phrase('You\'ve confirmed you\'re happy to use "[[screen_name]]" as your public screen name.', ['screen_name' => $screenName]);
				} else {
					$this->data['extranet_openForm'] = $this->openForm($onSubmit = '', $extraAttributes = '', $action = false, $scrollToTopOfSlot = true, $fadeOutAndIn = true);
					$this->data['extranet_closeForm'] = $this->closeForm();
					$this->data['extranet_screen_name_unconfirmed'] = true;
					$this->data['extranet_screen_name_confirmed_info'] = $this->phrase('It looks like you\'ve not confirmed that you\'re happy with your screen name, "[[screen_name]]". This name will be shown in messages you post on this site. If you\'d like to change it please click the "Edit profile" button, or if you\'re happy with it please click here to confirm:', ['screen_name' => $screenName]);
				}
			}
		}
		
		if ($this->allowCloseAccount && !$editing) {
			$this->data['show_errors_below_fields'] = $this->form['show_errors_below_fields'];
			
			$this->data['allow_user_to_delete_their_account'] = true;
			
			if (ze::get('extranet_delete_user_account') || (ze::post('closeAccount') && !empty($this->data['confirm_close_account_error']))) {
				$this->data['extranet_delete_user_account_confirmation_prompt'] = true;
				$this->data['delete_account_button_href_and_onclick'] = $this->refreshPluginSlotAnchor('extranet_delete_user_account=1');
				$this->data['cancel_delete_account_button_href_and_onclick'] = $this->refreshPluginSlotAnchor('');
				
				$this->data['extranet_openForm'] = $this->openForm($onSubmit = '', $extraAttributes = '', $action = false, $scrollToTopOfSlot = true, $fadeOutAndIn = true);
				$this->data['extranet_closeForm'] = $this->closeForm();
			} else {
				$this->data['delete_account_button_href_and_onclick'] = $this->refreshPluginSlotAnchor('extranet_delete_user_account=1');
			}
		}
		
		return $rv;
	}
	
	protected function getFormTitle($overwrite = false, $fallback = '') {
		//The $fallback parameter is to match the User Forms function definition.
		$title = '';
		if ($this->setting('show_title_message')) {
			$title .= '<h1>';
			if (!empty($_GET['extranet_edit_profile']) || !empty($this->errors)) {
				$title .= $this->phrase($this->setting('edit_profile_title'));
			} else {
				$title .= $this->phrase($this->setting('view_profile_title'));
			}
			$title .= '</h1>';
		}
		return $title;
	}
	
	protected function isFormReadonly() {
		return empty($_GET['extranet_edit_profile']) && empty($this->errors);
	}
	
	protected function showSubmitButton() {
		return !empty($_GET['extranet_edit_profile']) || !empty($this->errors);
	}
	
	protected function getCustomButtons($pageId, $onLastPage, $position) {
		$editing = !empty($_GET['extranet_edit_profile']) || !empty($this->errors);
		if ($position == 'first' && !$editing && $onLastPage && $this->setting('enable_edit_profile')) {
			return '<div class="extranet_links"><a ' . $this->refreshPluginSlotAnchor('extranet_edit_profile=1') . ' class="nice_button">' . $this->phrase($this->setting('edit_profile_button_text')) . '</a></div>';
		} elseif ($position == 'last' && $editing && $onLastPage) {
			return '<a ' . $this->refreshPluginSlotAnchor('') . ' class="nice_button cancel">' . $this->phrase($this->setting('cancel_button_text')) . '</a>';
		}
		return false;
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		//Get all the modules that will be deleting user data.
		$userIds = '';
		$allDataExplained = '';
		$moduleDataResponses = ze\module::sendSignal('deleteUserDataGetInfo', [$userIds]);
		if (!empty($moduleDataResponses)) {
			$allDataExplained .= '<p>' . ze\admin::phrase('If deleting all data, the following will be removed:') . '<p>';
			$allDataExplained .= implode('<br />', $moduleDataResponses);
		}
		
		ze\lang::applyMergeFields($box['tabs']['first_tab']['fields']['all_data_explained']['snippet']['html'], ['all_data_explained' => $allDataExplained]);
		
		$logoutPage = ze\row::get('special_pages', ['equiv_id', 'content_type'], ['page_type' => 'zenario_logout']);
		if (!empty($logoutPage)) {
			ze\lang::applyMergeFields(
				$fields['first_tab/allow_user_to_delete_their_account']['notices_below']['redirect_target_page_alias']['message'],
				['alias_string' => ze\admin::phrase('After closing their account, the user will be redirected to [[alias]].', ['alias' => ze\content::formatTag($logoutPage['equiv_id'], $logoutPage['content_type'])])]
			);
		} else {
			ze\lang::applyMergeFields(
				$fields['first_tab/allow_user_to_delete_their_account']['notices_below']['redirect_target_page_alias']['message'],
				['alias_string' => ze\admin::phrase('The Logout special page does not exist. After closing their account, the user will be redirected to the home page.')]
			);
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($path == 'plugin_settings') {
			$fields['first_tab/fields_which_dont_prepopulate_user_data']['hidden'] = true;
			
			if ($values['first_tab/user_form']) {
				//If a form is selected, check if every Users dataset field on it pre-populates the values
				//with the currently logged-in user's data.
				$fieldsWhichDontPrepopulateUserData = [];
				
				//Check if the form exists (e.g. has not been deleted)...
				$form = static::getForm($values['first_tab/user_form']);
				$usersDataset = ze\dataset::details('users');
				
				if ($form) {
					$formFields = self::getFormFieldsStatic($form['id']);
					
					//...check if it has fields...
					if (!empty($formFields)) {
						foreach ($formFields as $formField) {
							if ($formField['dataset_id'] && $formField['dataset_id'] == $usersDataset['id'] && ($formField['db_column'])) {
								if (!$formField['preload_dataset_field_user_data']) {
									//...and note all fields that don't pre-populate data correctly.
									$fieldsWhichDontPrepopulateUserData[] = $formField['name'];
								}
							}
						}
						
						if (!empty($fieldsWhichDontPrepopulateUserData)) {
							$fields['first_tab/fields_which_dont_prepopulate_user_data']['hidden'] = false;
							
							$fields['first_tab/fields_which_dont_prepopulate_user_data']['snippet']['html'] =
								'<div class="zenario_fbWarning">' . 
									ze\admin::phrase(
										"The following fields do not pre-populate with the logged in user's data:"
									)
								. '<ul>';
							
							foreach ($fieldsWhichDontPrepopulateUserData as $field) {
								$fields['first_tab/fields_which_dont_prepopulate_user_data']['snippet']['html'] .= '<li>' . $field . '</li>';
							}
							
							$fields['first_tab/fields_which_dont_prepopulate_user_data']['snippet']['html'] .= '
								</div>';
						}
					}
				}
			}
			
			$fields['first_tab/allow_user_to_delete_their_account']['notices_below']['signal_name']['hidden'] =
			$fields['first_tab/allow_user_to_delete_their_account']['notices_below']['redirect_target_page_alias']['hidden'] =
				!$values['first_tab/allow_user_to_delete_their_account'];
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($values['first_tab/allow_user_to_delete_their_account'] && $values['first_tab/notify_admin_when_user_account_deleted']) {
			$adminNotificationEmailAddresses = $values['first_tab/user_account_deleted_admin_notification_addresses'];
			
			if (!empty($adminNotificationEmailAddresses)) {
				foreach (ze\ray::explodeAndTrim($adminNotificationEmailAddresses) as $adminEmail) {
					if (!ze\ring::validateEmailAddress($adminEmail)) {
						$fields['first_tab/user_account_deleted_admin_notification_addresses']['error'] = ze\admin::phrase('Please enter one or more valid email addresses.');
						break;
					}
				}
			}
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path == 'zenario__email_template_manager/panels/other_email_addresses') {
			$moduleInfo = ze\module::details('zenario_extranet_profile_edit', $fetchBy = 'class');
			$pluginInstancesAndSettings = ze\module::getModuleInstancesAndPluginSettings('zenario_extranet_profile_edit');
			
			if (!empty($pluginInstancesAndSettings)) {
				foreach ($pluginInstancesAndSettings as $plugin) {
					if (!empty($plugin['settings']['allow_user_to_delete_their_account']) && !empty($plugin['settings']['notify_admin_when_user_account_deleted']) && !empty($plugin['settings']['user_account_deleted_admin_notification_addresses'])) {
						$panel['items']['extranet_edit_profile_' . $plugin['instance_id'] . '_' . $plugin['egg_id']] = [
							'id' => $plugin['instance_id'],
							'name' => '<a href="organizer.php#zenario__modules/panels/modules/item//' . $moduleInfo['module_id'] . '//' . $plugin['instance_id'] . '" target="_blank">' . ze\plugin::name($plugin['instance_id']) . '</a>',
							'email_address_value' => $plugin['settings']['user_account_deleted_admin_notification_addresses'],
							'type' => $this->phrase('Extranet Profile'),
							'css_class' => 'zenario_extranet_profile_edit__plugin'
						];
					}
				}
			}
		}
	}
}