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





class zenario_extranet extends module_base_class {
	
	var $mode;
	var $reloadPage = false;
	var $errors = array();
	var $message = false;
	var $lang = array();
	var $useScreenName = true;
		
	var $objects = array();
	var $subSections = array();
	
	var $user_id = false;
	var $old_password = false;
	
	var $showTermsAndConditionsCheckbox = false;
	
	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetSet = true, $ifPostSet = !$this->checkFrameworkSectionExists('Login_Form'), $ifSessionSet = true, $ifCookieSet = false);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$passwordNeedsChanging = getRow('users', 'password_needs_changing', post('user_id'));
		if (post('extranet_change_password') && $passwordNeedsChanging){
			
			$this->errors = $this->validatePassword(post('extranet_new_password'), post('extranet_new_password_confirm'), post('old_password'), get_class($this), post('user_id'));
			
			if (!empty($this->errors)) {
				$this->mode = 'modeChangePassword';
			} else {
				setUsersPassword(post('user_id'), post('extranet_new_password'), false);
				$this->logUserIn(post('user_id'));
				$this->redirectToPage();
			}
		} else {
			
			$this->mode = 'modeLogin';
			$manageCookies = true;
			
			
			if (userId()) {
				$this->mode = 'modeLoggedIn';
			} else {
				if ($this->checkFrameworkSectionExists('Login_Form')) {
					
					if (!canSetCookie() && setting('cookie_consent_for_extranet') == 'required') {
						requireCookieConsent();
						$this->message = $this->phrase('_PLEASE_ACCEPT_COOKIES');
						$this->mode = 'modeCookiesNotEnabled';
						$manageCookies = false;
					
					} else {
						if (setting('cookie_consent_for_extranet') == 'granted') {
							hideCookieConsent();
						}
						
						if ($this->setting('enable_remember_me') && (canSetCookie() || setting('cookie_consent_for_extranet') == 'granted')) {
							$this->subSections['Remember_Me_Section'] = true;
						}
						if ($this->setting('enable_log_me_in') && (canSetCookie() || setting('cookie_consent_for_extranet') == 'granted')) {
							$this->subSections['Log_Me_In_Section'] = true;
						}
						
						if (!$this->useScreenName || $this->setting('login_with') == 'Email') {
							$this->subSections['Login_with_email'] = true;
							$this->objects['extranet_email'] = arrayKey($_COOKIE, 'COOKIE_LAST_EXTRANET_EMAIL');
						} else {
							$this->subSections['Login_with_screen_name'] = true;
							$this->objects['extranet_screen_name'] = arrayKey($_COOKIE, 'COOKIE_LAST_EXTRANET_SCREEN_NAME');
						}
					}
				}
				
				if (post('extranet_login')) {
					//Check if the login was successful and redirect the user if so, or move the user to a change password page
					if ($this->checkLogin()) {
						if (setting('cookie_consent_for_extranet') == 'granted') {
							setCookieConsent();
						}
						
						$this->redirectToPage();
					}
					if ($this->checkFrameworkSectionExists('Login_Form')) {
						if ($this->setting('requires_terms_and_conditions') && $this->setting('terms_and_conditions_page') && $this->showTermsAndConditionsCheckbox) {
							$this->subSections['Ts_And_Cs_Section'] = true;
							$cID = $cType = false;
							$this->getCIDAndCTypeFromSetting($cID, $cType, 'terms_and_conditions_page');
							langEquivalentItem($cID, $cType);
							$TCLink = array( 'TCLink' =>$this->linkToItem($cID, $cType, true));
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
		
		$subSections = array();
		$subSections['Password_Error_Display'] = $this->errors;
		$old_password = !empty($this->old_password) ? $this->old_password : post('old_password');
		
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
		if ($this->checkFrameworkSectionExists('Login_Form')
		 && langSpecialPage('zenario_login', $cID, $cType)) {
			$this->subSections['Login_title_section'] = true;
			
			$this->frameworkHead('Outer', 'Login_Form', $this->objects, $this->subSections);
				
				if ($this->cID == $cID && $this->cType == $cType) {
					echo $this->openForm('',' class="form-horizontal"');
				} else {
					echo
						'<form action="'.
							htmlspecialchars($this->linkToItem($cID, $cType)).
						'" method="post">';
				}
				
						$this->framework('Login_Form', $this->objects, $this->subSections);
				echo $this->closeForm();
			$this->frameworkFoot('Outer', 'Login_Form', $this->objects, $this->subSections);
		
		} else {
			$this->subSections['Login'] = true;
			$this->framework('Outer', $this->objects, $this->subSections);
		}
	}
	
	protected function addLoginLinks() {
		$cID = $cType = false;
		
		if (langSpecialPage('zenario_login', $cID, $cType)
		 && $this->checkFrameworkSectionExists('Login_Link_Section')
		 && (!userId())) {
			$this->subSections['Login_Link_Section'] = true;
			$this->objects['Login_Link'] = $this->linkToItemAnchor($cID, $cType);
		}
		
		if (langSpecialPage('zenario_password_reminder', $cID, $cType)
		 && $this->checkFrameworkSectionExists('Forget_Password_Link_Section')) {
			$this->subSections['Forget_Password_Link_Section'] = true;
			$this->objects['Forget_Password_Link'] = $this->linkToItemAnchor($cID, $cType);
		}
		
		if (langSpecialPage('zenario_password_reset', $cID, $cType)
		 && $this->checkFrameworkSectionExists('Reset_Password_Link_Section')) {
			$this->subSections['Reset_Password_Link_Section'] = true;
			$this->objects['Reset_Password_Link'] = $this->linkToItemAnchor($cID, $cType);
		}
		
		if (langSpecialPage('zenario_registration', $cID, $cType)
		 && $this->checkFrameworkSectionExists('Registration_Link_Section')) {
			$this->subSections['Registration_Link_Section'] = true;
			$this->objects['Registration_Link'] = $this->linkToItemAnchor($cID, $cType);
		}
		
		if (langSpecialPage('zenario_registration', $cID, $cType)
		 && $this->checkFrameworkSectionExists('Resend_Link_Section')) {
			$this->subSections['Resend_Link_Section'] = true;
			$this->objects['Resend_Link'] = $this->linkToItemAnchor($cID, $cType, false, '&extranet_resend=1');
		}
	}
	
	
	//Display a welcome message when the user is logged in
	protected function modeLoggedIn() {
		$this->addLoggedInLinks();
		
		$this->subSections['Logged_In'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}
	
	protected function addLoggedInLinks() {
		if ($this->checkFrameworkSectionExists('Welcome_Message_Section')) {
			$this->subSections['Welcome_Message_Section'] = true;
			$this->objects['Welcome_Message'] = $this->phrase('_WELCOME', array('user' => htmlspecialchars(arrayKey($_SESSION, 'extranetUser_firstname'))));
		}
		
		if (langSpecialPage('zenario_change_password', $cID, $cType)
		 && $this->checkFrameworkSectionExists('Change_Password_Link_Section')) {
			$this->subSections['Change_Password_Link_Section'] = true;
			$this->objects['Change_Password_Link'] = $this->linkToItemAnchor($cID, $cType);
		}
		
		if (langSpecialPage('zenario_logout', $cID, $cType)
		 && $this->checkFrameworkSectionExists('Logout_Link_Section')) {
			$this->subSections['Logout_Link_Section'] = true;
			$this->objects['Logout_Link'] = $this->linkToItemAnchor($cID, $cType);
		}
		
		if (session('destURL') && ($this->cID != session('destCID') || $this->cType != session('destCType'))) {
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
				setCookieOnCookieDomain('COOKIE_LAST_EXTRANET_EMAIL', $_SESSION['SET_EXTRANET_LOGIN_COOKIE']);
			} else {
				setCookieOnCookieDomain('COOKIE_LAST_EXTRANET_SCREEN_NAME', $_SESSION['SET_EXTRANET_LOGIN_COOKIE']);
			}
			unset($_SESSION['SET_EXTRANET_LOGIN_COOKIE']);
		
		//Remove the User's email/screenname cookie on their local machine if requested
		} elseif (isset($_SESSION['FORGET_EXTRANET_LOGIN_COOKIE'])) {
			if (!$useScreenName || $login_with == 'Email') {
				clearCookie('COOKIE_LAST_EXTRANET_EMAIL');
			} else {
				clearCookie('COOKIE_LAST_EXTRANET_SCREEN_NAME');
			}
			unset($_SESSION['FORGET_EXTRANET_LOGIN_COOKIE']);
		}
		
		//Set a hash of the User's details in a cookie on their local machine if requested, so they can be logged in automatically
		if (isset($_SESSION['SET_EXTRANET_LOG_ME_IN_COOKIE'])) {
			setCookieOnCookieDomain('LOG_ME_IN_COOKIE', $_SESSION['SET_EXTRANET_LOG_ME_IN_COOKIE']);
			unset($_SESSION['SET_EXTRANET_LOG_ME_IN_COOKIE']);
		
		//Remove the hash of the User's details if requested
		} elseif (isset($_SESSION['FORGET_EXTRANET_LOG_ME_IN_COOKIE'])) {
			clearCookie('LOG_ME_IN_COOKIE');
			unset($_SESSION['FORGET_EXTRANET_LOG_ME_IN_COOKIE']);
		}
	}
	
	
	protected function manageCookies() {
		$this->useScreenName = (bool) setting('user_use_screen_name');
		self::manageUserCookies($this->useScreenName, $this->setting('login_with'));
	}
	
	
	protected function checkPermsOnDestURL() {
		return !empty($_SESSION['destCID']) && !empty($_SESSION['destCType']) && checkPerm($_SESSION['destCID'], $_SESSION['destCType']);
	}
	
	protected function redirectToPage($showWelcomePage = true, $redirectBackIfPossible = true, $redirectRegardlessOfPerms = true) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected function validateFormFields($section) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				$defaultLangId = setting('default_language');
				if (isset($values['email_address_required_error_text'])) {
					setRow("visitor_phrases", array("local_text" => $values['email_address_required_error_text']), array("code" => "_ERROR_EXTRANET_EMAIL", "language_id" => $defaultLangId));
				}
				if (isset($values['password_required_error_text'])) {
					setRow("visitor_phrases", array("local_text" => $values['password_required_error_text']), array("code" => "_ERROR_EXTRANET_PASSWORD", "language_id" => $defaultLangId));
				}
				if (isset($values['invalid_email_error_text'])) {
					setRow("visitor_phrases", array("local_text" => $values['invalid_email_error_text']), array("code" => "_ERROR_INVALID_EXTRANET_EMAIL", "language_id" => $defaultLangId));
				}
				if (isset($values['screen_name_required_error_text'])) {
					setRow("visitor_phrases", array("local_text" => $values['screen_name_required_error_text']), array("code" => "_ERROR_EXTRANET_SCREEN_NAME", "language_id" => $defaultLangId));
				}
				if (isset($values['no_new_password_error_text'])) {
					setRow("visitor_phrases", array("local_text" => $values['no_new_password_error_text']), array("code" => "_ERROR_NEW_PASSWORD", "language_id" => $defaultLangId));
				}
				if (isset($values['no_new_repeat_password_error_text'])) {
					setRow("visitor_phrases", array("local_text" => $values['no_new_repeat_password_error_text']), array("code" => "_ERROR_REPEAT_NEW_PASSWORD", "language_id" => $defaultLangId));
				}
				break;
		}
	}
	
	//Log a user in after successful validation
	function logUserIn($userId) {
		$user = logUserIn($userId);
		
		if ($this->setting('enable_remember_me')) {
			if (post('extranet_remember_me')) {
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
			if (post('extranet_log_me_in')) {
				$_SESSION['SET_EXTRANET_LOG_ME_IN_COOKIE'] = $user['login_hash'];
			}
		}
	}
	
	//Handle actions
	protected function checkLogin() {
		if ($this->validateFormFields('Login_Form')) {
			//check if email adrres has been entered
			//Check if this user exists, their password is correct, and they are active. Only log them in if so.
			if (!$this->useScreenName || $this->setting('login_with') == 'Email') {
				$user = getRow('users', array('id', 'password_needs_changing', 'terms_and_conditions_accepted', 'status'), array('email' => post('extranet_email')));
			} else {
				$user = getRow('users', array('id', 'password_needs_changing', 'terms_and_conditions_accepted', 'status'), array('screen_name' => post('extranet_screen_name')));
			}
		
			if ($user) {
				if ($user['status'] != "contact") {
					if (checkUsersPassword($user['id'], post('extranet_password'))) {
						//password correct
						if(request('extranet_terms_and_conditions')){
							setRow('users', array('terms_and_conditions_accepted'=>1), array('id' => $user['id']));
							$user['terms_and_conditions_accepted'] = true;
						}
						if ($user['status'] == 'active') {
							if ($user['password_needs_changing']) {
								//user password needs changing
								$this->mode = 'modeChangePassword';
								$this->user_id = $user['id'];
								$this->old_password = post('extranet_password');
			
							} elseif ($this->setting('requires_terms_and_conditions') && $this->setting('terms_and_conditions_page') && !$user['terms_and_conditions_accepted']) {
								//show terms and conditions checkbox
								$errorMessage = $this->setting('accept_terms_and_conditions_message');
								$this->errors[] = array('Error' => $errorMessage);
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
								$this->errors[] = array('Error' => $this->phrase($errorMessage));
							} elseif ($user['status'] == 'pending') {
								//user is pending, show error
								if(getRow('users', 'email_verified', array('email' => post('extranet_email')))) {
									//user has verified email, wanting on admin to activate there account.
									$errorMessage = $this->setting('account_pending_message');
									$this->errors[] = array('Error' => $this->phrase($errorMessage));
								} else {
									//email address still needs to be verified
									$errorMessage = $this->setting('account_not_verified_message');
									$cType = $cId = false;
									langSpecialPage('zenario_registration', $cID, $cType);
									$this->errors[] = array('Error' => $this->phrase($errorMessage, array('resend_verification_email' => $this->linkToItemAnchor($cID, $cType, true, 'extranet_resend=1' ))));
								}
							}
						}
					} else {
						//password incorrect
						$errorMessage = $this->setting('wrong_password_message');
						$this->errors[] = array('Error' => $this->phrase($errorMessage));
					}
				} else {
					//User is not externet user just a contact
					$errorMessage = $this->setting('contact_not_extranet_message');
					$this->errors[] = array('Error' => $this->phrase($errorMessage));
				}
			} else {
				//email address or screen name not in DB
				if (!$this->useScreenName || $this->setting('login_with') == 'Email') {
					//email
					$errorMessage = $this->setting('email_not_in_db_message');
					$this->errors[] = array('Error' => $this->phrase($errorMessage));
				} else {
					//screen name
					$errorMessage = $this->setting('screen_name_not_in_db_message');
					$this->errors[] = array('Error' => $this->phrase($errorMessage));
				}
			
			}
		}
		
		return false;
	}
	
	
	protected function logout() {
		logUserOut();
	}
	
	
	protected function sendSignalFromForm($signalName, $userId) {
		
		$fields = array();
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
		
		return sendSignal($signalName, array('userId' => $userId, 'instanceId' => $this->instanceId, 'fields' => $fields));
	}
	
	public static function getFirstName_framework($mergeFields){
		if (userId()){
			return htmlspecialchars(getRow('users','first_name',array('id'=>userId())));
		}
	}

	public static function getLastName_framework($mergeFields){
		if (userId()){
			return htmlspecialchars(getRow('users','last_name',array('id'=>userId())));
		}
	}

	public static function getEmailAddress_framework($mergeFields){
		if (userId()){
			return htmlspecialchars(getRow('users','email',array('id'=>userId())));
		}
	}
	
	protected function getDetailsFromEmail($email) {
		$sql = "
			SELECT
				id AS id,
				first_name AS first_name,
				last_name AS last_name,
				screen_name AS screen_name,
				password AS password,
				password_salt AS password_salt,
				email AS email,
				hash AS hash
			FROM ". DB_NAME_PREFIX. "users
			WHERE email = '". sqlEscape($email). "'";
		
		$result = sqlQuery($sql);
		return sqlFetchArray($result);
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
		$errors = array();
		
		
		//var_dump($this->setting('new_passwords_do_not_match'));
		//Look up what their current password is
		if (trim($oldPassword)==='' && $oldPassword!==false)  {
			//no password entered
			$errorMessage = $this->setting('no_password_entered_message');
			$errors[] = array('Error' => $this->phrase($errorMessage));
		} elseif ($oldPassword && !checkUsersPassword($userId, $oldPassword)) {
			//password incorrect
			$errorMessage = $this->setting('wrong_password_message');
			$errors[] = array('Error' => $this->phrase($errorMessage));
		}
	
		if (!$newPassword) {
			$errors[] = array('Error' => phrase('_ERROR_NEW_PASSWORD', false, $vlpClass));
		
		} elseif ($oldPassword && ($newPassword === $oldPassword)) {
			//new password is the same as old
			$errorMessage = $this->setting('new_password_same_as_old_message');
			$errors[] = array('Error' => $this->phrase($errorMessage));
		
		} elseif (strlen($newPassword) < setting('min_extranet_user_password_length')) {
			//password not long enough
			$errorMessage = $this->setting('new_password_length_message');
			$errors[] = array('Error' => $this->phrase($errorMessage, array('min_password_length' => setting('min_extranet_user_password_length'))));
		
		} elseif (!$confirmation) {
			//no repeat password
			$errors[] = array('Error' => phrase('_ERROR_REPEAT_NEW_PASSWORD', false, $vlpClass));
		
		} elseif ($newPassword !== $confirmation) {
			//passwords don't match
			$errorMessage = $this->setting('new_passwords_do_not_match');
			$errors[] = array('Error' => $this->phrase($errorMessage));
	
		} elseif (!checkPasswordStrength($newPassword,setting('min_extranet_user_password_strength'))) {
			//password not strong enough
			$errorMessage = $this->setting('new_password_not_strong_enough_message');
			$errors[] = array('Error' => $this->phrase($errorMessage));
		
		} 
		
		//if($newPassword && !$confirmation) {
		//	$errorMessage = $this->setting('no_new_repeat_password_error_text');
		//	$errors[] = array('Error' => $this->phrase($errorMessage));
		//}
	
		return $errors;
	}
	
	
	protected function getTitleAndLabelMergeFields() {
		
		$mergeFields = array();
		
		$this->objects['main_login_heading'] = $mergeFields['main_login_heading'] = $this->phrase($this->setting('main_login_heading'));
		$this->objects['email_field_label'] = $mergeFields['email_field_label'] = $this->phrase($this->setting('email_field_label'));
		$this->objects['screen_name_field_label'] = $mergeFields['screen_name_field_label'] = $this->phrase($this->setting('screen_name_field_label'));
		$this->objects['password_field_label'] = $mergeFields['password_field_label'] = $this->phrase($this->setting('password_field_label'));
		$this->objects['login_button_text'] = $mergeFields['login_button_text'] = $this->phrase($this->setting('login_button_text'));
		
		return $mergeFields;
		
	}
	
	
	
}
