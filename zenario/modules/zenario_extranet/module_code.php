<?php
/*
 * Copyright (c) 2018, Tribal Limited
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





class zenario_extranet extends ze\moduleBaseClass {
	
	var $mode;
	var $reloadPage = false;
	var $errors = [];
	var $message = false;
	var $lang = [];
	var $useScreenName = true;
		
	var $objects = [];
	var $subSections = [];
	
	var $user_id = false;
	var $old_password = false;
	
	var $showTermsAndConditionsCheckbox = false;
	protected $hasLoginForm = true;
	
	public function init() {
		
		//This is a little hack to try and work around the fact that the checkFrameworkSectionExists() method does not exist for Twig-based frameworks
		if ($this->framework == 'login_logout_box' ) {
			$this->hasLoginForm = false;
		}
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetSet = true, $ifPostSet = !$this->hasLoginForm, $ifSessionSet = true, $ifCookieSet = false);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$passwordNeedsChanging = ze\row::get('users', 'password_needs_changing', ($_POST['user_id'] ?? false));
		if (($_POST['extranet_change_password'] ?? false) && $passwordNeedsChanging){
			
			$this->errors = $this->validatePassword($_POST['extranet_new_password'] ?? false, ($_POST['extranet_new_password_confirm'] ?? false), ($_POST['old_password'] ?? false), get_class($this), ($_POST['user_id'] ?? false));
			
			if (!empty($this->errors)) {
				$this->mode = 'modeChangePassword';
			} else {
				ze\userAdm::setPassword($_POST['user_id'] ?? false, ($_POST['extranet_new_password'] ?? false), false);
				$this->logUserIn($_POST['user_id'] ?? false);
				$this->redirectToPage();
			}
		} else {
			
			$this->mode = 'modeLogin';
			$manageCookies = true;
			
			
			if (ze\user::id()) {
				$this->mode = 'modeLoggedIn';
			} else {
				if (http_response_code() == 401) {
					$this->objects['Redirect_Message'] = $this->phrase("You've requested a page for which access requires an account on this site. If you have an account, please log in using the form below.");
				}
				if ($this->hasLoginForm) {
					if (!ze\cookie::canSet() && ze::setting('cookie_consent_for_extranet') == 'required') {
						ze\cookie::requireConsent();
						$this->message = $this->phrase('_PLEASE_ACCEPT_COOKIES');
						$this->mode = 'modeCookiesNotEnabled';
						$manageCookies = false;
					
					} else {
						if (ze::setting('cookie_consent_for_extranet') == 'granted') {
							ze\cookie::hideConsent();
						}
						
						if ($this->setting('enable_remember_me') && (ze\cookie::canSet() || ze::setting('cookie_consent_for_extranet') == 'granted')) {
							$this->subSections['Remember_Me_Section'] = true;
						}
						if ($this->setting('enable_log_me_in') && (ze\cookie::canSet() || ze::setting('cookie_consent_for_extranet') == 'granted')) {
							$this->subSections['Log_Me_In_Section'] = true;
						}
						
						if ($this->setting('requires_terms_and_conditions') == "always") {
							$this->subSections['Ts_And_Cs_Section'] = true;
							$cID = $cType = false;
							$this->getCIDAndCTypeFromSetting($cID, $cType, 'terms_and_conditions_page');
							ze\content::langEquivalentItem($cID, $cType);
							$TCLink = array( 'TCLink' =>$this->linkToItem($cID, $cType, true));
							$this->objects['Ts_And_Cs_Link'] =  $this->phrase('_T_C_LINK', $TCLink);
						}
						
						if (!$this->useScreenName || $this->setting('login_with') == 'Email') {
							$this->subSections['Login_with_email'] = true;
							$this->objects['extranet_email'] = $_COOKIE['COOKIE_LAST_EXTRANET_EMAIL'] ?? false;
						} else {
							$this->subSections['Login_with_screen_name'] = true;
							$this->objects['extranet_screen_name'] = $_COOKIE['COOKIE_LAST_EXTRANET_SCREEN_NAME'] ?? false;
						}
					}
				}
				
				if ($_POST['extranet_login'] ?? false) {
					//Check if the login was successful and redirect the user if so, or move the user to a change password page
					if ($this->checkLogin()) {
						if (ze::setting('cookie_consent_for_extranet') == 'granted') {
							ze\cookie::setConsent();
						}
						
						$this->redirectToPage();
					}
					if ($this->hasLoginForm) {
						if ($this->setting('requires_terms_and_conditions') == 1 && $this->setting('terms_and_conditions_page') && $this->showTermsAndConditionsCheckbox) {
							$this->subSections['Ts_And_Cs_Section'] = true;
							$cID = $cType = false;
							$this->getCIDAndCTypeFromSetting($cID, $cType, 'terms_and_conditions_page');
							ze\content::langEquivalentItem($cID, $cType);
							$TCLink = [ 'TCLink' =>$this->linkToItem($cID, $cType, true)];
							$this->objects['Ts_And_Cs_Link'] =  $this->phrase('_T_C_LINK', $TCLink);
						}
					}
				}
				
			}
			
			if ($manageCookies) {
				$this->manageCookies();
			}
		
		}
		return true;
	}
	
	
	public function showSlot() {
		
		//A message if given
		if ($this->message) {
			$this->subSections['Message_Display'] = true;
			$this->objects['Message'] = $this->message;
		}
		
		//Display any errors we encountered
		if (!empty($this->errors)) {
			$this->subSections['Error_Display'] = $this->errors;
		}
		
		$this->getTitleAndLabelMergeFields();
		
		$mode = $this->mode;
		$this->$mode();
	}
	
	// Display a change password form
	protected function modeChangePassword(){
		$mergeFields = $this->getTitleAndLabelMergeFields();
		
		$subSections = [];
		$subSections['Password_Error_Display'] = $this->errors;
		$old_password = !empty($this->old_password) ? $this->old_password : ($_POST['old_password'] ?? false);
		
		echo $this->openForm();
			echo $this->remember('user_id', $this->user_id);
			echo $this->remember('old_password', $old_password);
			$this->framework('Change_Password_Form', $mergeFields, $subSections);
		echo $this->closeForm();
	}
	
	
	//Display a login form
	protected function modeLogin() {
		$this->addLoginLinks();
		
		$cID = $cType = false;
		if ($this->hasLoginForm
		 && ze\content::langSpecialPage('zenario_login', $cID, $cType)) {
			$this->subSections['Login_title_section'] = true;
			$this->subSections['Login_Form'] = true;
			
			if (($this->cID == $cID && $this->cType == $cType) || !$this->setting('redirect_to_login_page_on_submit')) {
				$this->objects['openForm'] = $this->openForm(
					'',' class="form-horizontal"',
					$action = false,
					$scrollToTopOfSlot = false, $fadeOutAndIn = true,
					$usePost = true, $autoAddRequests = false
				);
			} else {
				$this->objects['openForm'] = 
					'<form action="'.
						htmlspecialchars($this->linkToItem($cID, $cType)).
					'" method="post">';
			}
				
			$this->framework('Outer', $this->objects, $this->subSections);
		
		} else {
			$this->subSections['Login'] = true;
			$this->framework('Outer', $this->objects, $this->subSections);
		}
	}
	
	protected function addLoginLinks() {
		$cID = $cType = false;
		
		if (ze\content::langSpecialPage('zenario_login', $cID, $cType)
		 && (!ze\user::id())) {
			$this->subSections['Login_Link_Section'] = true;
			$this->objects['Login_Link'] = $this->linkToItemAnchor($cID, $cType);
		}
		
		if (ze\content::langSpecialPage('zenario_password_reminder', $cID, $cType)) {
			$this->subSections['Forget_Password_Link_Section'] = true;
			$this->objects['Forget_Password_Link'] = $this->linkToItemAnchor($cID, $cType);
		}
		
		if (ze\content::langSpecialPage('zenario_password_reset', $cID, $cType)) {
			$this->subSections['Reset_Password_Link_Section'] = true;
			$this->objects['Reset_Password_Link'] = $this->linkToItemAnchor($cID, $cType);
		}
		
		if (ze\content::langSpecialPage('zenario_registration', $cID, $cType)) {
			$this->subSections['Registration_Link_Section'] = true;
			$this->objects['Registration_Link'] = $this->linkToItemAnchor($cID, $cType);
		}
		
		if (ze\content::langSpecialPage('zenario_registration', $cID, $cType)) {
			$this->subSections['Resend_Link_Section'] = true;
			$this->objects['Resend_Link'] = $this->linkToItemAnchor($cID, $cType, false, '&extranet_resend=1');
		}
		
		
		//Update the link to the registration page according to plugin settings
		if ($this->setting('show_link_to_registration_page')) {
			if ($tagId = $this->setting('registration_page')) {
				$cID = $cType = false;
				ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
				$this->objects['Registration_Link'] = $this->linkToItemAnchor($cID, $cType);
				$this->objects['Resend_Link'] = $this->linkToItemAnchor($cID, $cType, false, '&extranet_resend=1');
			}
		} else {
			$this->subSections['Registration_Link_Section'] = false;
			$this->subSections['Resend_Link_Section'] = false;
		}
		
		//Update the link to the login page according to plugin settings
		if ($this->setting('show_link_to_login_page')) {
			if ($tagId = $this->setting('login_page')) {
				$cID = $cType = false;
				ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
				$this->objects['Login_Link'] = $this->linkToItemAnchor($cID, $cType);
			}
		} else {
			$this->subSections['Login_Link_Section'] = false;
		}
	}
	
	
	//Display a welcome message when the user is logged in
	protected function modeLoggedIn() {
		$this->addLoggedInLinks();
		
		$this->subSections['Logged_In'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}
	
	protected function addLoggedInLinks() {
		$this->subSections['Welcome_Message_Section'] = true;
		$this->objects['Welcome_Message'] = $this->phrase('_WELCOME', ['user' => htmlspecialchars($_SESSION['extranetUser_firstname'] ?? false)]);
		
		if (ze\content::langSpecialPage('zenario_change_password', $cID, $cType)) {
			$this->subSections['Change_Password_Link_Section'] = true;
			$this->objects['Change_Password_Link'] = $this->linkToItemAnchor($cID, $cType);
		}
		
		if (ze\content::langSpecialPage('zenario_logout', $cID, $cType)) {
			$this->subSections['Logout_Link_Section'] = true;
			$this->objects['Logout_Link'] = $this->linkToItemAnchor($cID, $cType);
		}
		
		if (($_SESSION['destURL'] ?? false) && ($this->cID != ($_SESSION['destCID'] ?? false) || $this->cType != $_SESSION['destCType'] ?? false)) {
			$this->subSections['Destination_url_section'] = true;
			$this->objects['destURL_Title'] = htmlspecialchars($_SESSION['destTitle']);
			$this->objects['destURL_Link'] = htmlspecialchars($_SESSION['destURL']);
		}
	}
	
	protected function modeCookiesNotEnabled() {
		//Don't show anything other than the error message defined above
		$this->framework('Outer', $this->objects, $this->subSections);
	}
	
	
	public static function manageUserCookies($useScreenName, $login_with) {
		
		//Set the User's email/screenname cookie on their local machine if requested
		if (isset($_SESSION['SET_EXTRANET_LOGIN_COOKIE'])) {
			if (!$useScreenName || $login_with == 'Email') {
				ze\cookie::set('COOKIE_LAST_EXTRANET_EMAIL', $_SESSION['SET_EXTRANET_LOGIN_COOKIE']);
			} else {
				ze\cookie::set('COOKIE_LAST_EXTRANET_SCREEN_NAME', $_SESSION['SET_EXTRANET_LOGIN_COOKIE']);
			}
			unset($_SESSION['SET_EXTRANET_LOGIN_COOKIE']);
		
		//Remove the User's email/screenname cookie on their local machine if requested
		} elseif (isset($_SESSION['FORGET_EXTRANET_LOGIN_COOKIE'])) {
			if (!$useScreenName || $login_with == 'Email') {
				ze\cookie::clear('COOKIE_LAST_EXTRANET_EMAIL');
			} else {
				ze\cookie::clear('COOKIE_LAST_EXTRANET_SCREEN_NAME');
			}
			unset($_SESSION['FORGET_EXTRANET_LOGIN_COOKIE']);
		}
		
		//Set a hash of the User's details in a cookie on their local machine if requested, so they can be logged in automatically
		if (isset($_SESSION['SET_EXTRANET_LOG_ME_IN_COOKIE'])) {
			ze\cookie::set('LOG_ME_IN_COOKIE', $_SESSION['SET_EXTRANET_LOG_ME_IN_COOKIE']);
			unset($_SESSION['SET_EXTRANET_LOG_ME_IN_COOKIE']);
		
		//Remove the hash of the User's details if requested
		} elseif (isset($_SESSION['FORGET_EXTRANET_LOG_ME_IN_COOKIE'])) {
			ze\cookie::clear('LOG_ME_IN_COOKIE');
			unset($_SESSION['FORGET_EXTRANET_LOG_ME_IN_COOKIE']);
		}
	}
	
	
	protected function manageCookies() {
		$this->useScreenName = (bool) ze::setting('user_use_screen_name');
		self::manageUserCookies($this->useScreenName, $this->setting('login_with'));
	}
	
	
	protected function checkPermsOnDestURL() {
		return !empty($_SESSION['destCID']) && !empty($_SESSION['destCType']) && ze\content::checkPerm($_SESSION['destCID'], $_SESSION['destCType']);
	}
	
	protected function redirectToPage($showWelcomePage = true, $redirectBackIfPossible = true, $redirectRegardlessOfPerms = true) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected final function checkRequiredField(&$field) {
		$name = $field['name'] ?? false;
		if (ze\ring::engToBoolean($field['required'] ?? false) && !($_POST[$name] ?? false) ) {
			if (($field['type'] ?? false) == 'checkbox'){
				
				$sub = $name . '__';
				$len = strlen($sub);
				$match = false;
				foreach ($_POST as $K=>$var){
					if (substr($K, 0, $len) == $sub && is_numeric(substr($K, $len))) {
						$match = true;
						break;
					}
				}
				return $match;
			}
			return false;
		} else {
			return true;
		}
	}
	
	protected function validateFormFields($section, $contactsCountAsUnregistered = false) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				$defaultLangId = ze::$defaultLang;
				
				foreach ([
					'error_messages/invalid_email_error_text' => '_ERROR_INVALID_EXTRANET_EMAIL',		
					'error_messages/screen_name_required_error_text' => '_ERROR_EXTRANET_SCREEN_NAME',		
					'error_messages/email_address_required_error_text' => '_ERROR_EXTRANET_EMAIL',			
					'error_messages/password_required_error_text' => '_ERROR_EXTRANET_PASSWORD',		
					'error_messages/no_new_password_error_text' => '_ERROR_NEW_PASSWORD',
					'error_messages/no_new_repeat_password_error_text' => '_ERROR_REPEAT_NEW_PASSWORD'
				] as $fieldName => $code) {
					if (isset($fields[$fieldName])) {
						ze\row::set('visitor_phrases', ['local_text' => $values[$fieldName]], ['code' => $code, 'language_id' => $defaultLangId]);
					}
				}
				
				break;
		}
	}
	
	//Log a user in after successful validation
	function logUserIn($userId) {
		$user = ze\user::logIn($userId);
		
		if ($this->setting('enable_remember_me')) {
			if ($_POST['extranet_remember_me'] ?? false) {
				if (!$this->useScreenName || $this->setting('login_with') == 'Email') {
					$_SESSION['SET_EXTRANET_LOGIN_COOKIE'] = $user['email'];
				} else {
					$_SESSION['SET_EXTRANET_LOGIN_COOKIE'] = $user['screen_name'];
				}
			} elseif ($this->setting('enable_remember_me')) {
				$_SESSION['FORGET_EXTRANET_LOGIN_COOKIE'] = true;
			}
		}
			
		if ($this->setting('enable_log_me_in')) {
			if ($_POST['extranet_log_me_in'] ?? false) {
				$_SESSION['SET_EXTRANET_LOG_ME_IN_COOKIE'] = $user['login_hash'];
			}
		}
	}
	
	//Handle actions
	protected function checkLogin() {
		if ($this->validateFormFields('Login_Form')) {
			//check if email address has been entered
			//Check if this user exists, their password is correct, and they are active. Only log them in if so.
			$sql = "
				SELECT
					id, password_needs_changing, terms_and_conditions_accepted, `status`,
					(
							password_needs_changing
						AND last_login IS NULL
						AND created_date <= DATE_SUB(NOW(), INTERVAL ". ((int) ze::setting('temp_password_timeout') ?: 14). " DAY)
					) AS password_expired
				FROM [users as u]";
			
			if (!$this->useScreenName || $this->setting('login_with') == 'Email') {
				$sql .= "
				WHERE [u.email == extranet_email]";
			} else {
				$sql .= "
				WHERE [u.screen_name == extranet_screen_name]";
			}
			
			$values = ['extranet_email' => $_POST['extranet_email'] ?? false, 'extranet_screen_name' => $_POST['extranet_screen_name'] ?? false];
			$result = ze\sql::select($sql, $values);
			
			if ($user = ze\sql::fetchAssoc($result)) {
				if ($user['status'] != "contact") {
					if ($user['password_expired']) {
						$errorMessage = $this->setting('password_expired_message');
						$this->errors[] = ['Error' => $this->phrase($errorMessage)];
					
					} elseif (ze\user::checkPassword($user['id'], ($_POST['extranet_password'] ?? false))) {
						//password correct
						if($_REQUEST['extranet_terms_and_conditions'] ?? false){
							ze\row::set('users', ['terms_and_conditions_accepted'=>1], ['id' => $user['id']]);
							$user['terms_and_conditions_accepted'] = true;
						}
						if ($user['status'] == 'active') {
							if ($user['password_needs_changing']) {
								//user password needs changing
								$this->mode = 'modeChangePassword';
								$this->user_id = $user['id'];
								$this->old_password = $_POST['extranet_password'] ?? false;
			
							} elseif ($this->setting('requires_terms_and_conditions') && $this->setting('terms_and_conditions_page') 
								&& (!$user['terms_and_conditions_accepted'] 
									|| ($this->setting('requires_terms_and_conditions') == 'always' && !$_REQUEST['extranet_terms_and_conditions']))) {
								//show terms and conditions checkbox
								$errorMessage = $this->setting('accept_terms_and_conditions_message');
								$this->errors[] = ['Error' => $errorMessage];
								$this->showTermsAndConditionsCheckbox = true;
			
							} else {
								//all conditions meet, log user in
								$this->logUserIn($user['id']);
								return true;
							}
						} else {
							if ($user['status'] == 'suspended') {
								//user is suspended, show error
								$errorMessage = $this->setting('account_suspended_message');
								$this->errors[] = ['Error' => $this->phrase($errorMessage)];
							} elseif ($user['status'] == 'pending') {
								//user is pending, show error
								if(ze\row::get('users', 'email_verified', ['email' => ($_POST['extranet_email'] ?? false)])) {
									//user has verified email, wanting on admin to activate there account.
									$errorMessage = $this->setting('account_pending_message');
									$this->errors[] = ['Error' => $this->phrase($errorMessage)];
								} else {
									//email address still needs to be verified
									$errorMessage = $this->setting('account_not_verified_message');
									$cType = $cId = false;
									ze\content::langSpecialPage('zenario_registration', $cID, $cType);
									$this->errors[] = ['Error' => $this->phrase($errorMessage, ['resend_verification_email' => $this->linkToItemAnchor($cID, $cType, true, 'extranet_resend=1' )])];
								}
							}
						}
					} else {
						//password incorrect
						$errorMessage = $this->setting('wrong_password_message');
						$this->errors[] = ['Error' => $this->phrase($errorMessage)];
					}
				} else {
					//User is not externet user just a contact
					$errorMessage = $this->setting('contact_not_extranet_message');
					$this->errors[] = ['Error' => $this->phrase($errorMessage)];
				}
			} else {
				//email address or screen name not in DB
				if (!$this->useScreenName || $this->setting('login_with') == 'Email') {
					//email
					$errorMessage = $this->setting('email_not_in_db_message');
					$this->errors[] = ['Error' => $this->phrase($errorMessage)];
				} else {
					//screen name
					$errorMessage = $this->setting('screen_name_not_in_db_message');
					$this->errors[] = ['Error' => $this->phrase($errorMessage)];
				}
			
			}
		}
		
		return false;
	}
	
	
	protected function logout() {
		ze\user::logOut();
	}
	
	
	protected function sendSignalFromForm($signalName, $userId) {
		
		$fields = [];
		foreach ($_POST as $name => &$value) {
			switch ($name) {
				case 'cID':
				case 'cType':
				case 'extranet_edit_profile':
				case 'extranet_register':
				case 'extranet_update_profile':
				case 'instanceId':
				case 'slotName':
				case 'tab':
					continue;
				default:
					$fields[$name] = $value;
			}
		}
		
		return ze\module::sendSignal($signalName, ['userId' => $userId, 'instanceId' => $this->instanceId, 'fields' => $fields]);
	}
	
	public static function getFirstName_framework($mergeFields){
		if (ze\user::id()){
			return htmlspecialchars(ze\row::get('users','first_name',['id'=>userId()]));
		}
	}

	public static function getLastName_framework($mergeFields){
		if (ze\user::id()){
			return htmlspecialchars(ze\row::get('users','last_name',['id'=>userId()]));
		}
	}

	public static function getEmailAddress_framework($mergeFields){
		if (ze\user::id()){
			return htmlspecialchars(ze\row::get('users','email',['id'=>userId()]));
		}
	}
	
	protected function getDetailsFromEmail($email) {
		return ze\row::get('users', ['id', 'first_name', 'last_name', 'screen_name', 'password', 'password_salt', 'email', 'hash'], ['email' => $email]);
	}
	
	
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				if (isset($box['tabs']['welcome_back_page']['fields']['welcome_page'])
				 && isset($box['tabs']['welcome_back_page']['fields']['show_welcome_page'])) {
					$box['tabs']['welcome_back_page']['fields']['welcome_page']['hidden'] = 
						$values['welcome_back_page/show_welcome_page'] != '_ALWAYS'
					 && $values['welcome_back_page/show_welcome_page'] != '_IF_NO_PREVIOUS_PAGE';
				}
				if (isset($box['tabs']['welcome_back_page']['fields']['terms_and_conditions_page'])
				 && isset($box['tabs']['welcome_back_page']['fields']['requires_terms_and_conditions'])) {
					$box['tabs']['welcome_back_page']['fields']['terms_and_conditions_page']['hidden'] = 
						!$values['welcome_back_page/requires_terms_and_conditions'];
				}

				break;
		}
	}
	
	function validatePassword($newPassword,$confirmation,$oldPassword=false,$vlpClass=false,$userId = false) {
		$errors = [];
	
		//Look up what their current password is
		if (trim($oldPassword)==='' && $oldPassword!==false)  {
			//no password entered
			$errorMessage = $this->setting('no_password_entered_message') ? $this->setting('no_password_entered_message') : '_ERROR_OLD_PASSWORD';
			$errors[] = ['Error' => $this->phrase($errorMessage)];
		} elseif ($oldPassword && !ze\user::checkPassword($userId, $oldPassword)) {
			//password incorrect
			$errorMessage = $this->setting('wrong_password_message') ? $this->setting('wrong_password_message') : '_ERROR_PASS_NOT_VERIFIED';
			$errors[] = ['Error' => $this->phrase($errorMessage)];
		}
	
		if (!$newPassword) {
			$errors[] = ['Error' => ze\lang::phrase('_ERROR_NEW_PASSWORD', false, $vlpClass)];
		
		} elseif ($oldPassword && ($newPassword === $oldPassword)) {
			//new password is the same as old
			$errorMessage = $this->setting('new_password_same_as_old_message') ? $this->setting('new_password_same_as_old_message') : '_ERROR_NEW_PASSWORD_SAME_AS_OLD';
			$errors[] = ['Error' => $this->phrase($errorMessage)];
		
		} elseif (strlen($newPassword) < ze::setting('min_extranet_user_password_length')) {
			//password not long enough
			$errorMessage = $this->setting('new_password_length_message') ? $this->setting('new_password_length_message') : '_ERROR_NEW_PASSWORD_LENGTH';
			$errors[] = ['Error' => $this->phrase($errorMessage, ['min_password_length' => ze::setting('min_extranet_user_password_length')])];
		
		} elseif (!$confirmation) {
			//no repeat password
			$errors[] = ['Error' => ze\lang::phrase('_ERROR_REPEAT_NEW_PASSWORD', false, $vlpClass)];
		
		} elseif ($newPassword !== $confirmation) {
			//passwords don't match
			$errorMessage = $this->setting('new_passwords_do_not_match') ? $this->setting('new_passwords_do_not_match') : '_ERROR_NEW_PASSWORD_MATCH';
			$errors[] = ['Error' => $this->phrase($errorMessage)];
	
		} elseif (!ze\user::checkPasswordStrength($newPassword,ze::setting('min_extranet_user_password_strength'))) {
			//password not strong enough
			$errorMessage = $this->setting('new_password_not_strong_enough_message') ? $this->setting('new_password_not_strong_enough_message') : '_ERROR_NEW_PASSWORD_NOT_STRONG_ENOUGH';
			$errors[] = ['Error' => $this->phrase($errorMessage)];
		
		}
	
		return $errors;
	}
	
	
	protected function getTitleAndLabelMergeFields() {
		
		$mergeFields = [];
		
		$this->objects['main_login_heading'] = $mergeFields['main_login_heading'] = $this->phrase($this->setting('main_login_heading'));
		$this->objects['email_field_label'] = $mergeFields['email_field_label'] = $this->phrase($this->setting('email_field_label'));
		$this->objects['screen_name_field_label'] = $mergeFields['screen_name_field_label'] = $this->phrase($this->setting('screen_name_field_label'));
		$this->objects['password_field_label'] = $mergeFields['password_field_label'] = $this->phrase($this->setting('password_field_label'));
		$this->objects['login_button_text'] = $mergeFields['login_button_text'] = $this->phrase($this->setting('login_button_text'));
		
		return $mergeFields;
		
	}
	
	
	

	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__users/nav/sign_in_log/panel':
				ze\tuix::flagEncryptedColumns($panel, 'u', 'users');
				ze\tuix::flagEncryptedColumns($panel, 'usl', 'user_signin_log');
		}
	}
}
