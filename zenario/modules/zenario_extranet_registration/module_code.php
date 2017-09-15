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

class zenario_extranet_registration extends zenario_extranet {
	
	protected $customFormErrors = array();
	protected $customFormExtraErrors = array();
	
	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = false, $ifCookieSet = false);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		
		$this->mode = 'modeRegistration';
		$this->registerGetRequest('extranet_resend');
		
		// Set the title
		if ($this->setting('registration_title')) {
			$this->subSections['Registration_Title_Section'] = true;
			$this->objects['Registration_Title'] = $this->phrase($this->setting('registration_title'));
		}
		
		// Set other text
		$this->objects['Register_Button_Text'] = $this->phrase($this->setting('register_button_text'));
		$this->objects['Resend_Verification_Email_Link_Text'] = $this->phrase($this->setting('resend_verification_email_link_text'));
		$this->objects['Resend_Verification_Email_Link_Description'] = $this->phrase($this->setting('resend_verification_email_link_description'));
		$this->objects['Go_Back_To_Login_Text'] = $this->phrase($this->setting('go_back_to_login_text'));
		$this->objects['Thank_You_Verify_Email_Text'] = $this->phrase(nl2br($this->setting('register_thank_you_verify_email_text')));
		$this->objects['Thank_You_Wait_For_Activation_Text'] = $this->phrase(nl2br($this->setting('register_thank_you_wait_for_activation_text')));
		$this->objects['Thank_You_Verify_Email_Resent_Text'] = $this->phrase(nl2br($this->setting('register_thank_you_verify_email_resent_text')));
		
		if (!canSetCookie() && setting('cookie_consent_for_extranet') == 'required') {
			requireCookieConsent();
			$this->message = $this->phrase('_PLEASE_ACCEPT_COOKIES');
			$this->mode = 'modeCookiesNotEnabled';
			return true;
		
		} else {
			if (setting('cookie_consent_for_extranet') == 'granted') {
				hideCookieConsent();
			}
			$this->manageCookies();
			
			
			if ($this->useScreenName) {
				$this->subSections['Choose_Screen_Name'] = true;
			}
			
			if ($this->setting('user_email_verification')) {
				$this->subSections['Second_Email'] = true;
			}
			
			if ($this->setting('requires_terms_and_conditions') && $this->setting('terms_and_conditions_page')) {
				$this->subSections['Ts_And_Cs_Section'] = true;
				$cID = $cType = false;
				$this->getCIDAndCTypeFromSetting($cID, $cType, 'terms_and_conditions_page');
				langEquivalentItem($cID, $cType);
				$TCLink = array( 'TCLink' =>$this->linkToItem($cID, $cType, true));
				$this->objects['Ts_And_Cs_Link'] =  $this->phrase('_T_C_LINK', $TCLink);
			}
			
			if (!empty($_SESSION['extranetUserID'])) {
				if (($_GET['confirm_email'] ?? false) && $this->isEmailAddressVerified($_SESSION['extranetUserID'])) {
					$this->mode = 'modeVerificationAlreadyDone';
				} else {
					$this->mode = 'modeLoggedIn';
				}
			} elseif (($_POST['extranet_resend'] ?? false) && ($this->setting('initial_email_address_status')=='not_verified')) {
				$this->validateFormFields('Resend_Form');
				$user = $this->getDetailsFromEmail($_POST['email'] ?? false);
				if ((!$this->errors) && (!empty($user['id'])) ) {
					$this->sendVerificationEmail($user['id'] ?? false);
					$this->mode = 'modeResent';
				} else {
					$this->mode = 'modeResend';
				}
			} elseif (($_GET['extranet_resend'] ?? false) && ($this->setting('initial_email_address_status')=='not_verified')) {
				$this->mode = 'modeResend';
			} elseif ($_POST['extranet_register'] ?? false){
				$this->scrollToTopOfSlot();
				
				if ($_POST['screen_name'] ?? false) {
					$_POST['screen_name'] = trim($_POST['screen_name']);
				}
				
				if ($userId = $this->addUserRecord()){
					$this->handleUserRegistration($userId);
				} else {
					$this->mode = 'modeRegistration';
				}
				
			} elseif (($_GET['confirm_email'] ?? false) && ($this->setting('initial_email_address_status')=='not_verified')) { 
				if ($userId = $this->getUserIdFromHashCode($_GET['hash'] ?? false)){
					if (!$this->isEmailAddressVerified($userId)){
						$this->setEmailVerified($userId);
						$this->applyAccountActivationPolicy($userId);
						if ($this->isActive($userId)){
							if (setting('cookie_consent_for_extranet') == 'granted') {
								setCookieConsent();
							}
							$this->logUserIn($userId);
							$this->mode = 'modeLoggedIn';
							$this->redirectToPage();
						} else {
							$this->mode = 'modeRegisteredVerifiedNotActivated';
						}
					} else {
						$this->mode = 'modeVerificationAlreadyDone';
					}
				} else {
					$this->mode = 'modeVerificationFailed';
				}
			}
			
			if ($this->mode == 'modeRegistration') {
				if ($this->setting('use_captcha') && empty($_SESSION['captcha_passed__'. $this->instanceId])) {
					$this->objects['Captcha'] = $this->captcha();
					$this->subSections['Captcha'] = true;
				}
			}
			return true;
		}
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		switch($path) {
			case 'plugin_settings':
				$fields['set_timer_on_new_users']['hidden'] = !inc('zenario_user_timers');
				
				$customFields = listCustomFields('users', $flat = false, array('checkbox', 'checkboxes'), $customOnly = true, $useOptGroups = true);
				if($options = self::removeEmptyTabs($customFields)){
					$box['tabs']['first_tab']['fields']['select_characteristics_for_new_users']['values'] = $options;
				}

				$customFields = listCustomFields('users', $flat = false, 'groups_only', $customOnly = true, $useOptGroups = true);
				if($options = self::removeEmptyTabs($customFields)){
					$box['tabs']['first_tab']['fields']['select_group_for_new_users']['values'] = $options;
				}
					
				break;
			case 'site_settings':
				if ($settingGroup == 'users') {
					$times = array();
					for ($i = 0; $i <= 23; ++$i) {
						$time = sprintf('%02d', $i) . ':00';
						$times[$time] = array('label' => $time);
					}
					$fields['registration/delayed_registration_email_time_of_day']['values'] = $times;
				}
				break;
		}
			
		return parent::fillAdminBox($path, $settingGroup, $box, $fields, $values);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				$fields['first_tab/select_group_for_new_users']['hidden'] = !$values['first_tab/add_user_to_group'];
				$fields['first_tab/verification_email_template']['hidden'] = $values['first_tab/initial_email_address_status']=='verified';
				$fields['first_tab/user_signup_notification_email_template']['hidden'] = !$values['first_tab/enable_notifications_on_user_signup'];
				$fields['first_tab/user_signup_notification_email_address']['hidden'] = !$values['first_tab/enable_notifications_on_user_signup'];
				$fields['first_tab/select_characteristics_for_new_users']['hidden'] = !$values['first_tab/set_characteristics_on_new_users'];
				$fields['first_tab/select_characteristic_values_for_new_users']['hidden'] = !$values['first_tab/set_characteristics_on_new_users']
					|| !$values['first_tab/select_characteristics_for_new_users'];
				$fields['first_tab/timer_for_new_users']['hidden'] = !$values['first_tab/set_timer_on_new_users'];
				$fields['first_tab/terms_and_conditions_page']['hidden'] = !$values['first_tab/requires_terms_and_conditions'];
				if ($values['first_tab/select_characteristics_for_new_users']) {
					$fieldType = getRow('custom_dataset_fields', 'type', $values['first_tab/select_characteristics_for_new_users']);
					if ($fieldType == 'checkboxes') {
						$fields['first_tab/select_characteristic_values_for_new_users']['hidden'] = !$values['first_tab/set_characteristics_on_new_users'];
						$fields['first_tab/select_characteristic_values_for_new_users']['values'] = getDatasetFieldLOV($values['first_tab/select_characteristics_for_new_users']);
					} else {
						$fields['first_tab/select_characteristic_values_for_new_users']['hidden'] = true;
					}
				}
				
				$fields['user_activation/welcome_email_template']['hidden'] = !($values['user_activation/verified_account_status'] == 'active' 
					|| $values['user_activation/verified_account_status'] == 'contact');
				$fields['user_activation/trusted_email_domains']['hidden'] = $values['user_activation/verified_account_status'] != 'check_trusted';
	
				$fields['user_activation/user_activation_notification_email_template']['hidden'] = !$values['user_activation/user_activation_notification_email_enable'];
				$fields['user_activation/user_activation_notification_email_address']['hidden'] = !$values['user_activation/user_activation_notification_email_enable'];
				
				$fields['user_activation/welcome_page']['hidden'] = $values['user_activation/show_welcome_page'] != '_ALWAYS' 
					&& $values['user_activation/show_welcome_page'] != '_IF_NO_PREVIOUS_PAGE';
				
				
				// Screen name error hidden if screen names not enabled and no user forms or user forms and screen name on form
				$fields['error_messages/screen_name_in_use']['hidden'] = !setting('user_use_screen_name');
				break;
			
			case 'site_settings':
				$showWarningMessage = $values['registration/send_delayed_registration_email'] && !checkScheduledTaskRunning('jobSendDelayedRegistrationEmails');
				$fields['registration/warning_message']['hidden'] = !$showWarningMessage;
				break;
		}
	}
	
	
	
	
	public function removeEmptyTabs($customFields){
		$tabs=array();
		if(is_array($customFields) && $customFields){
			foreach($customFields as $field){
				if(isset($field['parent'])){
					$tabs[$field['parent']]=$field['parent'];
				}
			}
		}
		
		if(is_array($tabs) && $tabs){
			foreach($customFields as $key=>$field){
				if(!isset($field['parent'])){
					if(!isset($tabs[$key])){
						unset($customFields[$key]);
					}
				}
			}
		}
		
		if(!$tabs){
			return false;
		}else{
			return $customFields;
		}
	}

	protected function validateFormFields($section, $contactsCountAsUnregistered = false) {
		$fields = parent::validateFormFields($section, $contactsCountAsUnregistered);
		if ($section=='Registration_Form') {
			if ($this->setting('user_password')=='user_to_choose_password'){
				$errors = $this->validatePassword($_POST['extranet_new_password'] ?? false,($_POST['extranet_new_password_confirm'] ?? false),false,get_class($this));
				if (count($errors)) {
					$this->errors = array_merge ($this->errors, $errors);
					return false;
				}
			}
			if ($this->setting('use_captcha') && empty($_SESSION['captcha_passed__'. $this->instanceId])) {
				if ($this->checkCaptcha()) {
					$_SESSION['captcha_passed__'. $this->instanceId] = true;
				} else {
					$this->errors[] = array('Error' => $this->phrase('_CAPTCHA_INVALID'));
				}
			}
		}
		return $fields;
	}

	protected function addUserRecord(){
		
		//Depending on the settings, allow contacts to register as if they haven't already made an account.
		switch ($this->setting('verified_account_status')) {
			case 'active':
				$contactsCountAsUnregistered = true;
				break;
			case 'contact':
				$contactsCountAsUnregistered = false;
				break;
			default:
				$contactsCountAsUnregistered = $this->setting('initial_account_status') == 'pending';
		}
		
		$fields = $this->validateFormFields('Registration_Form', $contactsCountAsUnregistered);
		
		if ($this->setting('user_email_verification')) {
			if (!($_POST['email_confirm'] ?? false)) {
				$this->errors[] = array('Error' => $this->phrase('Please re-enter your email address.'));
			} elseif (($_POST['email'] ?? false) != $_POST['email_confirm'] ?? false) {
				$this->errors[] = array('Error' => $this->phrase('The email addresses you entered do not match.'));
			}
		}
		
		if ($this->errors){
			return false;
		}
		
		if ($this->useScreenName) {
			if ($_POST['screen_name'] ?? false){
				$fields['screen_name'] = $_POST['screen_name'] ?? false;
				$fields['screen_name_confirmed'] = 1;
			} else {
				$this->errors[] = array('Error' => $this->phrase('_ERROR_SCREEN_NAME'));
				return false;
			}
		}
		
		$fields['password_needs_changing'] = 0;
		if ($this->setting('user_password')=='user_to_have_random_password' && $this->setting('password_needs_changing')) {
			$fields['password_needs_changing'] = 1;
		}
		
		
		
		$fields['email_verified'] = 0;
		if ($this->setting('initial_account_status')=='pending'){
			$fields['status'] = 'pending';
			if ($this->setting('user_password')=='user_to_choose_password'){
				$fields['password'] = $_POST['extranet_new_password'] ?? false;
			} else {
				$fields['password'] = createPassword();
			}
		} else {
			$fields['status'] = 'contact';
		}
		$fields['created_date'] = date("Y-m-d H:i:s");
		if ($this->setting('user_password')=='user_to_choose_password'){
			$fields['password'] = $_POST['extranet_new_password'] ?? false;
		} else {
			$fields['password'] = createPassword();
		}
		$fields['ip'] = visitorIP();
		
		if(($_REQUEST['extranet_terms_and_conditions'] ?? false) && $this->setting('requires_terms_and_conditions') && $this->setting('terms_and_conditions_page')){
			$fields['terms_and_conditions_accepted'] = 1;
		}
		
		if (isset($fields['%attributes%'])) {
			unset($fields['%attributes%']);
		}
		
		// Temp code to remove extra values from custom frameworks. Module should eventually be changed to user_forms.
		$fields2 = array();
		$sql = 'SHOW COLUMNS FROM '. DB_NAME_PREFIX. 'users';
		$result = sqlQuery($sql);
		while ($column = sqlFetchAssoc($result)) {
			if (isset($fields[$column['Field']]) && ($column['Key'] != 'PRI')) {
				$fields2[$column['Field']] = $fields[$column['Field']];
			}
		}
		
		//Allow contacts to register, turning their contact account into a user account
		$userId = getRow('users', 'id', ['email' => ($_POST['email'] ?? false), 'status' => 'contact']);
		
		$userId = saveUser($fields2, $userId);
		
		if (isError($userId)) {
			return false;
		} else {
			
			// Save custom fields from frameworks
			$details = array();
			checkTableDefinition($tableName = DB_NAME_PREFIX . 'users_custom_data');
			foreach (cms_core::$dbCols[$tableName] as $col => $colDef) {
				if ($col != 'user_id' && isset($fields[$col])) {
					$details[$col] = $fields[$col];
				}
			}
			
			if (!empty($details)) {
				setRow('users_custom_data', $details, $userId);
			}
			
			return $userId;
		}
	}
	
	
	protected function applyEmailVerificationPolicy($userId){
		if ($this->setting('enable_notifications_on_user_signup') && ($this->setting('user_signup_notification_email_template')) && ($this->setting('user_signup_notification_email_address'))){
			$this->sendSignupNotification($userId);
		}
		if ($this->setting('initial_email_address_status')=='verified'){
			$this->setEmailVerified($userId);
		} elseif ($this->setting('initial_email_address_status')=='not_verified'){
			$this->sendVerificationEmail($userId);
		}
	}

	protected function setEmailVerified($userId){
		if ($userId){
			$sql ="
					UPDATE "
						. DB_NAME_PREFIX . "users 
					SET email_verified = 1";
			
			$sql .= " WHERE id = " . (int) $userId;
			sqlQuery($sql);
		}
	}
	
	protected function getUserIdFromHashCode($hash){
		if ($hash && ($userId = (int) getRow("users","id",array('hash'=>$hash)))){
			return $userId;
		} else {
			return 0;
		}
	}
	
	protected function sendVerificationEmail($userId) {
		updateUserHash($userId);
		$emailMergeFields = getUserDetails($userId);
		if (!empty($emailMergeFields['email']) && $this->setting('verification_email_template')) {
			$emailMergeFields['ip_address'] = visitorIP();
			$emailMergeFields['cms_url'] = absCMSDirURL();
			$emailMergeFields['email_confirmation_link'] = $this->linkToItem($this->cID, $this->cType, $fullPath = true, $request = '&confirm_email=1&hash='. $emailMergeFields['hash']);
						
			$emailMergeFields['user_groups'] = getUserGroupsNames($userId);
			
			if (inc('zenario_users')) {
				foreach (getUserDetails($userId) as $cn => $cv){
					$emailMergeFields[$cn] = htmlspecialchars($cv);
				}
			}
			
			
			zenario_email_template_manager::sendEmailsUsingTemplate($emailMergeFields['email'] ?? false,$this->setting('verification_email_template'),$emailMergeFields,array());
		}
	}

	protected function sendSignupNotification($userId){
		if ($this->setting('user_signup_notification_email_address') && $this->setting('user_signup_notification_email_template')) {
			updateUserHash($userId);
			$emailMergeFields = getUserDetails($userId);
			$emailMergeFields['ip_address'] = visitorIP();
			$emailMergeFields['cms_url'] = absCMSDirURL();
			$emailMergeFields['email_confirmation_link'] = $this->linkToItem($this->cID, $this->cType, $fullPath = true, $request = '&confirm_email=1&hash='. $emailMergeFields['hash']);
			$emailMergeFields['organizer_link'] = httpOrhttps(). adminDomain(). SUBDIRECTORY. 'zenario/admin/organizer.php#zenario__users/panels/users//'. $emailMergeFields['id'];
			
			$emailMergeFields['user_groups'] = getUserGroupsNames($userId);
			
			if (inc('zenario_users')) {
				foreach (getUserDetails($userId) as $cn => $cv){
					$emailMergeFields[$cn] = htmlspecialchars($cv);
				}
			}
			
	
			zenario_email_template_manager::sendEmailsUsingTemplate($this->setting('user_signup_notification_email_address'),$this->setting('user_signup_notification_email_template'),$emailMergeFields,array());
		}
	}

	protected function applyAccountActivationPolicy($userId){
		if ($this->setting('user_activation_notification_email_enable') && ($this->setting('user_activation_notification_email_template')) && ($this->setting('user_activation_notification_email_address'))){
			$this->sendActivationNotification($userId);
		}
		
		switch ($this->setting('verified_account_status')) {
			case 'contact':
				$this->setAccountContact($userId);
				$this->sendWelcomeEmail($userId);
				break;
			case 'active':
				$this->setAccountActive($userId);
				$this->sendWelcomeEmail($userId);
				break;
			case 'check_trusted':
				$userDetails = getUserDetails($userId);
				$userEmail = $userDetails['email'];
				$domains = explode(',', $this->setting('trusted_email_domains'));
				$domains = array_map('trim', $domains);
				if (in_array(substr($userEmail, strpos($userEmail, '@')), $domains)) {
					$this->setAccountActive($userId);
					$this->sendWelcomeEmail($userId);
				}
				break;
		}
	}
	
	protected function setAccountContact($userId){
		if ($userId) {
			$sql = '
				UPDATE '
					 .DB_NAME_PREFIX . 'users
				SET 
					status = "contact"
				WHERE 
					id = '.(int)$userId;
			sqlQuery($sql);
		}
	}

	protected function setAccountActive($userId){
		if ($userId){
			$sql ="
					UPDATE " 
						. DB_NAME_PREFIX . "users
					SET 
						status='active'
					WHERE 
						id = " . (int) $userId;
			sqlQuery($sql);
		}				
	}
	
	protected function sendWelcomeEmail($userId){
		$emailMergeFields = getUserDetails($userId);
		if (!empty($emailMergeFields['email']) && $this->setting('welcome_email_template')) {
			
			$emailMergeFields['ip_address'] = visitorIP();
			$emailMergeFields['cms_url'] = absCMSDirURL();
			$emailMergeFields['user_groups'] = getUserGroupsNames($userId);
			
			if (inc('zenario_users')) {
				foreach (getUserDetails($userId) as $cn => $cv){
					$emailMergeFields[$cn] = htmlspecialchars($cv);
				}
			}
			
			// If passwords are encrypted
			if (!setting('plaintext_extranet_user_passwords')) {
				// If user chose password, show ****
				if ($this->setting('user_password')=='user_to_choose_password'){
					$emailMergeFields['password'] = '********';
				// Otherwise generate a new password and show it
				} else {
					$password = createPassword();
					$emailMergeFields['password'] = $password;
					setUsersPassword($userId, $password);
				}
			}
			zenario_email_template_manager::sendEmailsUsingTemplate($emailMergeFields['email'] ?? false,$this->setting('welcome_email_template'),$emailMergeFields,array());
		}
	}

	protected function sendActivationNotification($userId){
		if ($this->setting('user_activation_notification_email_address') && $this->setting('user_activation_notification_email_template')) {
			$emailMergeFields = getUserDetails($userId);
			$emailMergeFields['ip_address'] = visitorIP();
			$emailMergeFields['cms_url'] = absCMSDirURL();
			$emailMergeFields['organizer_link'] = httpOrhttps(). adminDomain(). SUBDIRECTORY. 'zenario/admin/organizer.php#zenario__users/panels/users//'. $emailMergeFields['id'];
			
			$emailMergeFields['user_groups'] = getUserGroupsNames($userId);
			
			if (inc('zenario_users')) {
				foreach (getUserDetails($userId) as $cn => $cv){
					$emailMergeFields[$cn] = htmlspecialchars($cv);
				}
			}
			
			
			zenario_email_template_manager::sendEmailsUsingTemplate($this->setting('user_activation_notification_email_address'),$this->setting('user_activation_notification_email_template'),$emailMergeFields,array());
		}
	}
	
	protected function handleUserRegistration($userId) {
		//Set a flag for a user
		if ($this->setting('set_characteristics_on_new_users') && $this->setting('select_characteristics_for_new_users')) {
			$datasetField = getDatasetFieldDetails($this->setting('select_characteristics_for_new_users'));
			if ($datasetField['type'] == 'checkbox') {
				setRow('users_custom_data', array($datasetField['db_column'] => 1), $userId);
			} elseif ($datasetField['type'] == 'checkboxes') {
				if ($this->setting('select_characteristic_values_for_new_users')) {
					foreach (explode(',', $this->setting('select_characteristic_values_for_new_users')) as $value) {
						setRow(
							'custom_dataset_values_link', 
							array(),  
							array('linking_id'=> $userId, 'value_id'=> $value, 'dataset_id' => $datasetField['dataset_id'])
						);
					}
				}
			}
		}
		
		//Add user to group
		if ($this->setting('add_user_to_group') && (int)$this->setting('select_group_for_new_users')) {
			addUserToGroup($userId, (int)$this->setting('select_group_for_new_users'));
		}
		
		//Create user timer
		if ($this->setting('timer_for_new_users') && inc('zenario_user_timers')) {
			zenario_user_timers::addTimerToUser($userId, $this->setting('timer_for_new_users'));
		}
		
		if (setting('send_delayed_registration_email')) {
			updateRow('users', ['send_delayed_registration_email' => 1], $userId);
		}
		
		//Send signal
		$this->sendSignalFromForm('eventUserRegistered', $userId);
		
		unset($_SESSION['captcha_passed__'. $this->instanceId]);
		if (setting('cookie_consent_for_extranet') == 'granted') {
			setCookieConsent();
		}
		$this->applyEmailVerificationPolicy($userId);
		if ($this->isEmailAddressVerified($userId)){
			$this->applyAccountActivationPolicy($userId);
			if ($this->isActive($userId)){
				$this->logUserIn($userId);
				$this->mode = 'modeLoggedIn';
				$this->redirectToPage();
			} else {
				$this->mode = 'modeRegisteredVerifiedNotActivated';
			}
		} else {
			$this->mode = 'modeRegisteredNotVerified';
		}
	}
	
	protected function isEmailAddressVerified($userId){
		return (boolean) ((int) getRow('users','email_verified',array('id'=>$userId)));
	}

	protected function isActive($userId){
		return (getRow('users','status',array('id'=>$userId))=='active');
	}


	protected function modeRegistration() {
		
		$this->addLoginLinks();
		
		//Overwrite the Resend_Link from the addLoginLinks() function
		if ($this->setting('initial_email_address_status')=='not_verified'){
			$this->subSections['Resend_Link_Section'] = true;
			$this->objects['Resend_Link'] = $this->refreshPluginSlotAnchor('&extranet_resend=1');
		} else {
			$this->subSections['Resend_Link_Section'] = false;
			unset($this->objects['Resend_Link']);
		}
		
		
		$this->subSections['User_passwords'] = false;
		if ($this->setting('user_password')=='user_to_choose_password'){
			$this->subSections['User_passwords'] = true;
		}
		
		$this->frameworkHead('Outer', 'Registration_Form', $this->objects, $this->subSections);
		echo $this->openForm('',' class="form-horizontal"');
		
		$this->framework('Registration_Form', $this->objects, $this->subSections);
		
		echo $this->closeForm();
		$this->frameworkFoot('Outer', 'Registration_Form', $this->objects, $this->subSections);
	}

	protected function modeResend() {
		$this->addLoginLinks();
		
		$this->objects['Registration_Link'] = $this->refreshPluginSlotAnchor();
		
		$this->frameworkHead('Outer', 'Resend_Form', $this->objects, $this->subSections);
			echo $this->openForm('',' class="form-horizontal"'),
					$this->remember('extranet_resend');
					$this->framework('Resend_Form', $this->objects, $this->subSections);
			echo $this->closeForm();
		$this->frameworkFoot('Outer', 'Resend_Form', $this->objects, $this->subSections);
	}

	protected function modeVerificationFailed(){
		$this->subSections['Verification_Failed'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}
	
	protected function modeVerificationAlreadyDone(){
		$this->subSections['Verification_Already_Done'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}
	
	protected function modeRegisteredNotVerified(){
		$this->subSections['Registered_Not_Verified'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}

	protected function modeRegisteredVerifiedNotActivated(){
		$this->subSections['Registered_Verified_Not_Activated'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}

	protected function modeRegistered() {
		$this->subSections['Registered'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}

	protected function modeResent() {
		$this->subSections['Registered_Email_Resent'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case "zenario__users/panels/zenario_extranet_registration__code_groups":
				if (($_POST['action'] ?? false) == 'add_group_to_code') {
					setRow(
						ZENARIO_EXTRANET_REGISTRATION_PREFIX. 'code_groups',
						array(),
						array('code_id' => $refinerId, 'group_id' => ($_POST['group_id'] ?? false)));
				}
	
				if (($_POST['action'] ?? false) == 'remove_group_from_code') {
					foreach (explode(',', $ids) as $id) {
						deleteRow(
							ZENARIO_EXTRANET_REGISTRATION_PREFIX. 'code_groups',
							array('id' => (int)$id));
					}
				}
				break;
			case "zenario__users/panels/zenario_extranet_registration__codes":
				if (($_POST['action'] ?? false) == 'delete_code') {
					foreach (explode(',', $ids) as $id) {
						deleteRow(
							ZENARIO_EXTRANET_REGISTRATION_PREFIX. 'codes',
							array('id' => (int) $id));
						deleteRow(
							ZENARIO_EXTRANET_REGISTRATION_PREFIX. 'code_groups',
							array('code_id' => (int) $id));
					}
				}
				
				break;
		}
	}
	
	public function checkCodeValid ($code) {
		return checkRowExists(ZENARIO_EXTRANET_REGISTRATION_PREFIX . "codes",array("code" => $code));
	}
	
	public function getCodeIdFromCode ($code) {
		return getRow(ZENARIO_EXTRANET_REGISTRATION_PREFIX . "codes","id",array("code" => $code));
	}
	
	public function getCodeGroups ($codeId) {
		$result = getRows(ZENARIO_EXTRANET_REGISTRATION_PREFIX . "code_groups","group_id",array("code_id" => $codeId));
		
		$groupIds = array();
		
		if (sqlNumRows($result)>0) {
			while ($row = sqlFetchAssoc($result)) { 
				$groupIds[] = $row['group_id'];
			}
		}
		return $groupIds;
	}
	
	public static function jobSendDelayedRegistrationEmails() {
		$return = false;
		$date = new DateTime();
		$hour = (int)$date->format('H');
		$hourToSend = setting('delayed_registration_email_time_of_day');
		if ($hour == $hourToSend) {
			
			$delay = setting('delayed_registration_email_days_delayed');
			$template = setting('delayed_registration_email_template');
			$sql = '
				SELECT u.id, u.identifier, u.first_name, u.last_name, u.email
				FROM ' . DB_NAME_PREFIX. 'users AS u
				WHERE u.send_delayed_registration_email = 1
				  AND u.status = "active"
				  AND DATE_ADD(u.created_date, INTERVAL '. (int) $delay. ' DAY) <= NOW()';
			
			$result = sqlSelect($sql);
			while ($user = sqlFetchAssoc($result)) {
				$mergeFields = $user;
				$mergeFields['cms_url'] = absCMSDirURL();
				zenario_email_template_manager::sendEmailsUsingTemplate($user['email'], $template, $mergeFields);
				updateRow('users', ['send_delayed_registration_email' => 0], $user['id']);
				
				echo "Sent delayed registration email to user " . $user['identifier'] . "\n";
				$return = true;
			}
		}
		return $return;
	}
}
