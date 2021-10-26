<?php
/*
 * Copyright (c) 2021, Tribal Limited
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
	
	var $user_password = false;
	
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
		
		} elseif (isset($_POST['accept_terms_and_conditions'])
			&& ($userId = $_POST['user_id'] ?? false)
			&& ze\user::checkPassword($userId, ($_POST['user_password'] ?? false))
		) {
			$this->errors = [];
			if (empty($_POST['extranet_terms_and_conditions'])) {
				$this->errors[] = ['Error' => $this->setting('accept_terms_and_conditions_message')];
			}
			
			if (!empty($this->errors)) {
				$this->mode = 'modeTermsAndConditions';
			} else {
				ze\row::set('users', ['terms_and_conditions_accepted' => 1], $userId);
				
				//Record consent
				$userDetails = ze\row::get('users', ['first_name', 'last_name', 'email'], ['id' => $_POST['user_id']]);
				
				$userContentItem = $this->setting('terms_and_conditions_page');
				$useExternalLink = $this->setting('url');
				if($userContentItem || $useExternalLink) {
					if ($userContentItem && $this->setting('link_type') == '_CONTENT_ITEM' ){
						$cID = $cType = false;
						$this->getCIDAndCTypeFromSetting($cID, $cType, 'terms_and_conditions_page');
						$TCLink = $this->linkToItem($cID, $cType, true);
					} elseif ($useExternalLink && $this->setting('link_type') == '_EXTERNAL_URL' ) {
						$TCLink = $this->setting('url');
					}
				$linkStart = '<a href ="'.$TCLink.'" target="_blank">';
				$linkEnd = '</a>';
				}
				ze\user::recordConsent('extranet_login', $this->instanceId, $userId, $userDetails['email'], $userDetails['first_name'] ?? false, $userDetails['last_name'] ?? false, $this->phrase("I have read and accept the [[link_start]]Terms and Conditions[[link_end]].", ['link_start' => $linkStart, 'link_end' => $linkEnd]));
				
				// Save custom fields from frameworks
				$details = [];
				ze::$dbL->checkTableDef($tableName = DB_PREFIX . 'users_custom_data');
				foreach (ze::$dbL->cols[$tableName] as $col => $colDef) {
					if ($col != 'user_id' && isset($_POST[$col])) {
						$details[$col] = $_POST[$col];
						$colType = ze\row::get('custom_dataset_fields', 'type', ["db_column" => $col]);
						//check if dataset feild is consent field
						if ($colType && $colType == "consent") {
							//ze\user::recordConsent('extranet_login', $this->instanceId, $userId, $userDetails['email'], $userDetails['first_name'] ?? false, $userDetails['last_name'] ?? false, strip_tags($this->phrase("_".$col)));
						}
					}
				}
				
				if (!empty($details)) {
					ze\row::set('users_custom_data', $details, $userId);
				}
				
				$this->logUserIn($userId);
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
					if (!ze\cookie::canSet('required') && ze::setting('cookie_consent_for_extranet') == 'required') {
						ze\cookie::requireConsent();
						$this->message = $this->phrase('_PLEASE_ACCEPT_COOKIES');
						$this->mode = 'modeCookiesNotEnabled';
						$manageCookies = false;
					
					} else {
						if (ze::setting('cookie_consent_for_extranet') == 'granted') {
							ze\cookie::hideConsent();
						}
						
						if ($this->setting('enable_remember_me') && (ze\cookie::canSet('functionality') || ze::setting('cookie_consent_for_extranet') == 'granted')) {
							$this->subSections['Remember_Me_Section'] = true;
						}
						if ($this->setting('enable_log_me_in') && (ze\cookie::canSet('functionality') || ze::setting('cookie_consent_for_extranet') == 'granted')) {
							$this->subSections['Log_Me_In_Section'] = true;
						}
						
						if ($this->setting('requires_terms_and_conditions') == "always") {
							$userContentItem = $this->setting('terms_and_conditions_page');
							$useExternalLink = $this->setting('url');
							if($userContentItem || $useExternalLink) {
								if ($userContentItem && $this->setting('link_type') == '_CONTENT_ITEM' ){
									$cID = $cType = false;
									$this->getCIDAndCTypeFromSetting($cID, $cType, 'terms_and_conditions_page');
									ze\content::langEquivalentItem($cID, $cType);
									$TCLink = $this->linkToItem($cID, $cType, true);
								} elseif ($useExternalLink && $this->setting('link_type') == '_EXTERNAL_URL' ) {
									$TCLink = $this->setting('url');
								}
								$this->subSections['Ts_And_Cs_Section'] = true;
								$linkStart = '<a href ="'.$TCLink.'" target="_blank">';
								$linkEnd = '</a>';

								$this->objects['Ts_And_Cs_Link'] =  $this->phrase ("I have read and accept the [[link_start]]Terms and Conditions[[link_end]].", ['link_start' => $linkStart, 'link_end' => $linkEnd]);
							}
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
						if ($this->setting('requires_terms_and_conditions') == 1 && ($this->setting('terms_and_conditions_page') || $this->setting('url')) && $this->showTermsAndConditionsCheckbox)
						{
							$userContentItem = $this->setting('terms_and_conditions_page');
							$useExternalLink = $this->setting('url');
							if($userContentItem || $useExternalLink) {
								if ($userContentItem && $this->setting('link_type') == '_CONTENT_ITEM' ){
									$cID = $cType = false;
									$this->getCIDAndCTypeFromSetting($cID, $cType, 'terms_and_conditions_page');
									ze\content::langEquivalentItem($cID, $cType);
									$TCLink = $this->linkToItem($cID, $cType, true);
								} elseif ($useExternalLink && $this->setting('link_type') == '_EXTERNAL_URL' ) {
									$TCLink = $this->setting('url');
								}
								$this->subSections['Ts_And_Cs_Section'] = true;
								$linkStart = '<a href ="'.$TCLink.'" target="_blank">';
								$linkEnd = '</a>';

								$this->objects['Ts_And_Cs_Link'] =  $this->phrase ("I have read and accept the [[link_start]]Terms and Conditions[[link_end]].", ['link_start' => $linkStart, 'link_end' => $linkEnd]);
							}
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
		if (!empty($mode) && is_string($mode)) {
			$this->$mode();
		}
	}
	
	// Display a change password form
	protected function modeChangePassword(){
		$mergeFields = $this->getTitleAndLabelMergeFields();
		
		$subSections = [];
		$subSections['Password_Error_Display'] = $this->errors;
		$old_password = !empty($this->old_password) ? $this->old_password : ($_POST['old_password'] ?? false);
		
		echo $this->openForm($onSubmit = '', $extraAttributes = '', $action = false, $scrollToTopOfSlot = true, $fadeOutAndIn = true);
			echo $this->remember('user_id', $this->user_id);
			echo $this->remember('old_password', $old_password);
			$this->framework('Change_Password_Form', $mergeFields, $subSections);
		echo $this->closeForm();
	}
	
	protected function modeTermsAndConditions() {
		$mergeFields = $this->getTitleAndLabelMergeFields();
		
		$subSections = [];
		$subSections['Error_Display'] = $this->errors;
		
		$userContentItem = $this->setting('terms_and_conditions_page');
		$useExternalLink = $this->setting('url');
		if($userContentItem || $useExternalLink) {
			if ($userContentItem && $this->setting('link_type') == '_CONTENT_ITEM' ){
				$cID = $cType = false;
				$this->getCIDAndCTypeFromSetting($cID, $cType, 'terms_and_conditions_page');
				ze\content::langEquivalentItem($cID, $cType);
				$TCLink = $this->linkToItem($cID, $cType, true);
			} elseif ($useExternalLink && $this->setting('link_type') == '_EXTERNAL_URL' ) {
				$TCLink = $this->setting('url');
			}
			$this->subSections['Ts_And_Cs_Section'] = true;
			$linkStart = '<a href ="'.$TCLink.'" target="_blank">';
			$linkEnd = '</a>';
			$mergeFields['Ts_And_Cs_Link'] =  $this->phrase ("I have read and accept the [[link_start]]Terms and Conditions[[link_end]].", ['link_start' => $linkStart, 'link_end' => $linkEnd]);
			
		}
		
		
		echo $this->getLoginOpenForm($onSubmit = '', $extraAttributes = '', $action = false, $scrollToTopOfSlot = true, $fadeOutAndIn = true);
			echo $this->remember('accept_terms_and_conditions', 1);
			echo $this->remember('user_id', $this->user_id);
			echo $this->remember('user_password', $this->user_password);
			$this->framework('Terms_And_Conditions_Form',  $mergeFields, $subSections);
		echo $this->closeForm();
	}
	
	
	//Display a login form
	protected function modeLogin() {
		$this->addLoginLinks();
		
		if ($this->hasLoginForm) {
			$this->subSections['Login_title_section'] = true;
			$this->subSections['Login_Form'] = true;
			
			$this->objects['openForm'] = $this->getLoginOpenForm();
				
			$this->framework('Outer', $this->objects, $this->subSections);
		
		} else {
			$this->subSections['Login'] = true;
			$this->framework('Outer', $this->objects, $this->subSections);
		}
	}
	
	protected function getLoginOpenForm() {
		$cID = $cType = false;
		ze\content::langSpecialPage('zenario_login', $cID, $cType);
		if (($this->cID == $cID && $this->cType == $cType) || !$this->setting('redirect_to_login_page_on_submit')) {
			return $this->openForm(
				'',' class="form-horizontal"',
				$action = false,
				$scrollToTopOfSlot = true, $fadeOutAndIn = true,
				$usePost = true, $autoAddRequests = false
			);
		} else {
			return 
				'<form action="'.
					htmlspecialchars($this->linkToItem($cID, $cType)).
				'" method="post">';
		}
	}
	
	protected function addLoginLinks() {
		$cID = $cType = false;
		
		if (ze\content::langSpecialPage('zenario_login', $cID, $cType)
		 && (!ze\user::id())) {
			$this->subSections['Login_Link_Section'] = true;
			$this->objects['Login_Link'] = $this->linkToItemAnchor($cID, $cType);
		}
		
		if ($link = ze\link::toPluginPage('zenario_extranet_password_reset')) {
			$this->subSections['Reset_Password_Link_Section'] = true;
			$this->objects['Reset_Password_Link'] = 'href="'. htmlspecialchars($link). '"';
		}
		
		if ($link = ze\link::toPluginPage('zenario_extranet_registration')) {
			$this->subSections['Registration_Link_Section'] = true;
			$this->objects['Registration_Link'] = 'href="'. htmlspecialchars($link). '"';
			
			$link = ze\link::toPluginPage('zenario_extranet_registration', '', false, false, '&extranet_resend=1');
			$this->subSections['Resend_Link_Section'] = true;
			$this->objects['Resend_Link'] = 'href="'. htmlspecialchars($link). '"';
		}
		
		
		//Update the link to the registration page according to plugin settings
		if ($this->setting('show_link_to_registration_page') && ze\module::isRunning('zenario_extranet_registration')) {
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
		
		if ($link = ze\link::toPluginPage('zenario_extranet_change_password')) {
			$this->subSections['Change_Password_Link_Section'] = true;
			$this->objects['Change_Password_Link'] = 'href="'. htmlspecialchars($link). '"';
		}
		
		if ($link = ze\link::toPluginPage('zenario_extranet_logout')) {
			$this->subSections['Logout_Link_Section'] = true;
			$this->objects['Logout_Link'] = 'href="'. htmlspecialchars($link). '"';
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
			if (ze\cookie::canSet('functionality')) {
				if (!$useScreenName || $login_with == 'Email') {
					ze\cookie::set('COOKIE_LAST_EXTRANET_EMAIL', $_SESSION['SET_EXTRANET_LOGIN_COOKIE']);
				} else {
					ze\cookie::set('COOKIE_LAST_EXTRANET_SCREEN_NAME', $_SESSION['SET_EXTRANET_LOGIN_COOKIE']);
				}
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
			if (ze\cookie::canSet('functionality')) {
				ze\cookie::set('LOG_ME_IN_COOKIE', $_SESSION['SET_EXTRANET_LOG_ME_IN_COOKIE']);
			}
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
			if (!$this->useScreenName || $this->setting('login_with') == 'Email') {
				$userId = ze\row::get('users', 'id', ['email' => $_POST['extranet_email'] ?? false]);
			} else {
				$userId = ze\row::get('users', 'id', ['screen_name' => $_POST['extranet_screen_name'] ?? false]);
			}
			
			if ($userId) {
				$sql = "
					SELECT
						id, password_needs_changing, terms_and_conditions_accepted, `status`
					FROM ". DB_PREFIX. "users as u
					WHERE id = ". (int) $userId;
				$user = ze\sql::fetchAssoc($sql);
				if ($user['status'] != "contact") {
					if (ze\user::isPasswordExpired($userId)) {
						$errorMessage = $this->setting('password_expired_message');
						$this->errors[] = ['Error' => $this->phrase($errorMessage)];
					
					} elseif (ze\user::checkPassword($user['id'], ($_POST['extranet_password'] ?? false))) {
						//password correct
						if($_REQUEST['extranet_terms_and_conditions'] ?? false){
							ze\row::set('users', ['terms_and_conditions_accepted' => 1],  $user['id']);
							$user['terms_and_conditions_accepted'] = true;
							
							//Record consent
							$userDetails = ze\row::get('users', ['first_name', 'last_name', 'email'], ['id' => $userId]);
							$userContentItem = $this->setting('terms_and_conditions_page');
							$useExternalLink = $this->setting('url');
							if($userContentItem || $useExternalLink) {
								if ($userContentItem && $this->setting('link_type') == '_CONTENT_ITEM' ){
									$cID = $cType = false;
									$this->getCIDAndCTypeFromSetting($cID, $cType, 'terms_and_conditions_page');
									ze\content::langEquivalentItem($cID, $cType);
									$TCLink = $this->linkToItem($cID, $cType, true);
								} elseif ($useExternalLink && $this->setting('link_type') == '_EXTERNAL_URL' ) {
									$TCLink = $this->setting('url');
								}
								$this->subSections['Ts_And_Cs_Section'] = true;
								$linkStart = '<a href ="'.$TCLink.'" target="_blank">';
								$linkEnd = '</a>';
							}
							ze\user::recordConsent('extranet_login', $this->instanceId, $userId, $userDetails['email'], $userDetails['first_name'] ?? false, $userDetails['last_name'] ?? false, $this->phrase("I have read and accept the [[link_start]]Terms and Conditions[[link_end]].", ['link_start' => $linkStart, 'link_end' => $linkEnd]));
						}
						if ($user['status'] == 'active') {
							if ($user['password_needs_changing']) {
								//user password needs changing
								$this->mode = 'modeChangePassword';
								$this->user_id = $user['id'];
								$this->old_password = $_POST['extranet_password'] ?? false;
			
							} elseif ($this->setting('requires_terms_and_conditions') && ($this->setting('terms_and_conditions_page') || $this->setting('url'))
								&& (!$user['terms_and_conditions_accepted'] 
									|| ($this->setting('requires_terms_and_conditions') == 'always' && empty($_REQUEST['extranet_terms_and_conditions'])))) {
								//show terms and conditions checkbox
								$errorMessage = $this->setting('accept_terms_and_conditions_message');
								$this->errors[] = ['Error' => $errorMessage];
								$this->showTermsAndConditionsCheckbox = true;
								
								//If the user attempting to login has not accepted the terms and conditions yet, show a single checkbox
								//that they must check in order to proceed.
								if ($this->setting('requires_terms_and_conditions') == 1) {
									$this->mode = 'modeTermsAndConditions';
									$this->user_id = $user['id'];
									$this->user_password = $_POST['extranet_password'];
								}
								
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
									
									$link = ze\link::toPluginPage('zenario_extranet_registration', '', false, $fullPath = true, '&extranet_resend=1');
									$this->errors[] = ['Error' => $this->phrase($errorMessage, ['resend_verification_email' => 'href="'. htmlspecialchars($link). '"'])];
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
					break;
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
		
		} elseif (!$confirmation) {
			//no repeat password
			$errors[] = ['Error' => ze\lang::phrase('_ERROR_REPEAT_NEW_PASSWORD', false, $vlpClass)];
		
		} elseif ($newPassword !== $confirmation) {
			//passwords don't match
			$errorMessage = $this->setting('new_passwords_do_not_match') ? $this->setting('new_passwords_do_not_match') : '_ERROR_NEW_PASSWORD_MATCH';
			$errors[] = ['Error' => $this->phrase($errorMessage)];
	
		//checkPasswordStrength now returns an array instead of just a boolean.
		} elseif (!ze\user::checkPasswordStrength($newPassword)['password_matches_requirements']) {
			//password not strong enough
			$errorMessage = ze\lang::phrase('Password does not match the requirements.');
			$errors[] = ['Error' => $errorMessage];
		
		}
	
		return $errors;
	}
	
	protected function getTitleAndLabelMergeFields() {
		$mergeFields = [];
		
		$this->objects['main_login_heading'] = $mergeFields['main_login_heading'] = $this->phrase('Sign in');
		$this->objects['email_field_label'] = $mergeFields['email_field_label'] = $this->phrase('Your email:');
		$this->objects['screen_name_field_label'] = $mergeFields['screen_name_field_label'] = $this->phrase('Your screen name:');
		$this->objects['password_field_label'] = $mergeFields['password_field_label'] = $this->phrase('Your password:');
		$this->objects['login_button_text'] = $mergeFields['login_button_text'] = $this->phrase('Login');
		
		return $mergeFields;
	}
	
	
	
	
	
	
	
	public function setupRedirectRuleRows(&$box, &$fields, &$values, $changes, $filling, $addRows = 0) {
		return ze\tuix::setupMultipleRows(
			$box, $fields, $values, $changes, $filling = false,
			$box['tabs']['action_after_login']['redirect_rule_template_fields'],
			$addRows,
			$minNumRows = 0,
			$tabName = 'action_after_login',
			$deleteButtonCodeName = 'remove__znz'
		);
	}
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				if (isset($fields['first_tab/login_with'])) {
					$fields['first_tab/login_with']['readonly'] = !ze::setting('user_use_screen_name');
				}
		
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
						$values[$fieldName] = ze\row::get('visitor_phrases', 'local_text', ['code' => $code, 'language_id' => $defaultLangId]);
					}
				}
		
				//Set the default value of the registration page selector to the special page.
				if (empty($values['first_tab/registration_page'])) {
					$cID = $cType = $state = false;
					if (ze\content::pluginPage($cID, $cType, $state, 'zenario_extranet_registration', '', true)) {
						$tagId = $cType. '_' . $cID;
						$values['first_tab/registration_page'] = $tagId;
					}
				}
				
				
				//Load lists for redirect rules and disable "role" based rules if organization manager is not running.
				$box['lovs']['groups'] = ze\datasetAdm::listCustomFields('users', $flat = false, 'groups_only', $customOnly = true, $useOptGroups = true, $hideEmptyOptGroupParents = true);
				if ($allowRoleRedirectRules = ze\module::inc('zenario_organization_manager')) {
					$box['lovs']['roles'] = ze\row::getValues(ZENARIO_ORGANIZATION_MANAGER_PREFIX . 'user_location_roles', 'name', [], 'name', 'id');
				} else {
					$box['tabs']['action_after_login']['redirect_rule_template_fields']['redirect_rule_type__znz']['values']['role']['disabled'] = true;
				}
				
				//Setup multi-rows for redirect rules.
				if (isset($fields['action_after_login/number_of_redirect_rules'])) {
					$addRows = (int)$values['action_after_login/number_of_redirect_rules'];
					$changes = [];
					$multiRows = $this->setupRedirectRuleRows($box, $fields, $values, $changes, $filling = true, $addRows);
					$values['action_after_login/number_of_redirect_rules'] = $multiRows['numRows'];
				
					$valuesInDB = [];
					ze\tuix::loadAllPluginSettings($box, $valuesInDB);
					for ($i = 1; $i <= $addRows; $i++) {
						$type = $valuesInDB['redirect_rule_type__' . $i] ?? false;
						if ($type && ($type != 'role' || $allowRoleRedirectRules)) {
							$values['action_after_login/redirect_rule_type__' . $i] = $type;
							$values['action_after_login/redirect_rule_group__' . $i] = $valuesInDB['redirect_rule_group__' . $i] ?? false;
							$values['action_after_login/redirect_rule_role__' . $i] = $valuesInDB['redirect_rule_role__' . $i] ?? false;
							$values['action_after_login/redirect_rule_content_item__' . $i] = $valuesInDB['redirect_rule_content_item__' . $i] ?? false;
						}
					}
				}
				
				//Select the home page as the default redirect page.
				$fields['action_after_login/welcome_page']['value'] = ze::$specialPages['zenario_home'] ?? '';

				$fields['first_tab/password_reset_page']['value'] = ze::$specialPages['zenario_password_reset'] ?? '';
				
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				if (isset($box['tabs']['action_after_login']['fields']['welcome_page'])
				 && isset($box['tabs']['action_after_login']['fields']['show_welcome_page'])) {
					$box['tabs']['action_after_login']['fields']['welcome_page']['hidden'] = 
						$values['action_after_login/show_welcome_page'] != '_ALWAYS'
					 && $values['action_after_login/show_welcome_page'] != '_IF_NO_PREVIOUS_PAGE';
				}
				if (isset($box['tabs']['action_after_login']['fields']['terms_and_conditions_page'])
				 && isset($box['tabs']['action_after_login']['fields']['requires_terms_and_conditions'])) {
					$box['tabs']['action_after_login']['fields']['terms_and_conditions_page']['hidden'] = 
						!$values['action_after_login/requires_terms_and_conditions'];
					$box['tabs']['action_after_login']['fields']['url']['hidden'] = 
						!$values['action_after_login/requires_terms_and_conditions'];
				}
				
				//Handle redirect rules multi-row updates
				if (isset($fields['action_after_login/number_of_redirect_rules'])) {
					$addRows = !empty($box['tabs']['action_after_login']['fields']['add_redirect_rule']['pressed']);
					$multiRows = $this->setupRedirectRuleRows($box, $fields, $values, $changes, $filling = false, $addRows);
					$values['action_after_login/number_of_redirect_rules'] = $multiRows['numRows'];
				}
				
				if (!ze\module::isRunning('zenario_extranet_registration')) {
					$fields['first_tab/registration_page']['note_below'] = ze\admin::phrase('Warning: Extranet Registration module is not running.<br />Registration page link will not be shown.');
				}
				
				break;
		}
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
	
	

	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
        
		//Information to view Data Protection settings
		if ($path == 'zenario__users/nav/sign_in_log/panel') {
			$accessLogDuration = '';
			switch (ze::setting('period_to_delete_sign_in_log')) {
				case 'never_delete':
					$accessLogDuration = ze\admin::phrase('Entries in the user sign-in log is stored forever.');
					break;
				case 0:
					$accessLogDuration = ze\admin::phrase('Entries in the user sign-in log is not stored.');
					break;
				case 1:
					$accessLogDuration = ze\admin::phrase('Entries in the user sign-in log are deleted after 1 day.');
					break;
				case 7:
					$accessLogDuration = ze\admin::phrase('Entries in the user sign-in log are deleted after 1 week.');
					break;
				case 30:
					$accessLogDuration = ze\admin::phrase('Entries in the user sign-in log are deleted after 1 month.');
					break;
				case 90:
					$accessLogDuration = ze\admin::phrase('Entries in the user sign-in log are deleted after 3 months.');
					break;
				case 365:
					$accessLogDuration = ze\admin::phrase('Entries in the user sign-in log are deleted after 1 year.');
					break;
				case 730:
					$accessLogDuration = ze\admin::phrase('Entries in the user sign-in log are deleted after 2 years.');
					break;
				
			}
			$link = ze\link::absolute() .'organizer.php#zenario__administration/panels/site_settings//data_protection~.site_settings~tdata_protection~k{"id"%3A"data_protection"}';
			$accessLogDuration .= ' ' . "<a target='_blank' href='" . $link . "'>View Data Protection settings</a>";
			$panel['notice']['show'] = true;
			$panel['notice']['message'] = $accessLogDuration.".";
			$panel['notice']['html'] = true;

		}
			switch ($path) {
				case 'zenario__users/nav/sign_in_log/panel':
					ze\tuix::flagEncryptedColumns($panel, 'u', 'users');
					ze\tuix::flagEncryptedColumns($panel, 'usl', 'user_signin_log');
			}
		
	}
}
