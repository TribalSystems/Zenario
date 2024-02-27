<?php
/*
 * Copyright (c) 2024, Tribal Limited
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
	
	protected $mode;
	protected $errors = [];
	protected $objects = [];
	protected $subSections = [];
	protected $message = false;
	
	protected $userLoggedIn;
	protected $idOfUserTryingToLogIn;

	public function init() {
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetOrPostVarIsSet = false, $ifSessionVarOrCookieIsSet = false);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByFile = false, $clearByModuleData = false);
		
		
		//Check if a user is currently logged in.
		//Note that the core already has some logic in visitorheader.inc.php for rejecting bad sessions,
		//or deactivated users, which will have already run by this point. All we need to do is check
		//the session ID is set.
		if (isset($_SESSION['extranetUserID'])) {
			$this->userLoggedIn = true;
			$this->idOfUserTryingToLogIn = $_SESSION['extranetUserID'];
		
		//Otherwise check if we stored a user ID in the session.
		//(Note that the getIdOfUserTryingToLogIn() method has a copy of the same logic for rejecting
		// bad sessions that the core has.)
		} else {
			$this->userLoggedIn = false;
			$this->idOfUserTryingToLogIn = $this->getIdOfUserTryingToLogIn();
		}
		
		
		//Handle the flow of the logic as the user forfills the requirements of each step.
		//As each step is cleared, allow the user to advance to the next step, until they
		//are fully logged in.
		if (!$this->checkCookieStep()) {
			$this->setupCookieStep();
			$this->mode = 'modeCookiesNotEnabled';
	
		} elseif (!$this->checkLoginStep()) {
			$this->setupLoginStep();
			$this->mode = 'modeLogin';
	
		} elseif (!$this->checkTwoFactorStep()) {
			$this->setupTwoFactorStep();
			$this->mode = 'mode2FA';
	
		} elseif (!$this->checkPasswordChangeStep()) {
			$this->setupPasswordChangeStep();
			$this->mode = 'modeChangePassword';
	
		} elseif (!$this->checkTermsStep()) {
			$this->setupTermsStep();
			$this->mode = 'modeTermsAndConditions';
	
		} else {
			$this->setupLogin();
			$this->mode = 'modeLoggedIn';
		}
		
		
		$this->manageCookies();
		
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
	
	
	
	protected function checkCookieStep() {
		
		//Check to see if the user has accepted functional cookies.
		$cookiesAccepted = ze::setting('cookie_require_consent') != 'explicit' || ze\cookie::canSet('functionality');
		
		//If they can, let them past this step.
		return $cookiesAccepted;
	}
	
	protected function setupCookieStep() {
		$this->message = $this->phrase(
			'This site needs to place a cookie on your computer before you can log in. Please accept cookies from this site to continue. [[manage_cookies_link_start]]Manage cookies[[manage_cookies_link_end]]',
			[
				'manage_cookies_link_start' => '<a onclick="zenario.manageCookies();">',
				'manage_cookies_link_end' => '</a>'
			]
		);
	}
	
	protected function modeCookiesNotEnabled() {
		$this->add401MessageIfNeeded();
		
		//Don't show anything other than the error message defined above
		$this->framework('Outer', $this->objects, $this->subSections);
	}
	
	protected function checkLoginStep() {
		
		//If the user has fully logged in, skip this step
		if ($this->userLoggedIn) {
			return true;
		}
		
		//If the user has already entered their username and password, don't ask them to repeat it
		if ($this->idOfUserTryingToLogIn) {
			return true;
		}
		
		//If this isn't a form submission of the login form... show the login form
		if (empty($_POST['extranet_login'])) {
			return false;
		}
		
		if ($this->setting('requires_terms_and_conditions') == "always") {
			if (empty($_POST['extranet_terms_and_conditions'])) {
				$this->errors[] = ['Error' => $this->setting('accept_terms_and_conditions_message')];
			}
		}

		
		//Do some basic validation on the form fields on the login form
		//(N.b. this is very old legacy code, first written when we used our own framework system rather than Twig.)
		if ($this->validateFormFields('Login_Form') && empty($this->errors)) {
			//check if email address has been entered
			//Check if this user exists, their password is correct, and they are active. Only log them in if so.
			$userCols = ['id', 'status', 'password_needs_changing', 'terms_and_conditions_accepted', 'first_name', 'last_name', 'email'];
			if ($this->signInUsingEmailAddress()) {
				$user = ze\row::get('users', $userCols, ['email' => $_POST['extranet_email'] ?? '']);
			} else {
				$user = ze\row::get('users', $userCols, ['screen_name' => $_POST['extranet_screen_name'] ?? '']);
			}
			
			if ($user) {
				if ($user['status'] != "contact") {
					if (ze\user::isPasswordExpired($this->idOfUserTryingToLogIn)) {
						$errorMessage = $this->setting('password_expired_message');
						$this->errors[] = ['Error' => $this->phrase($errorMessage)];

						$this->holdFailureAgainstCaptcha();
					
					} elseif (ze\user::checkPassword($user['id'], ze::post('extranet_password'))) {
						//password correct
						if ($user['status'] == 'active') {
							unset($_SESSION['extranet_user_failed_logins_count']);
							unset($_SESSION['captcha_passed__'. $this->instanceId]);
							
							$this->setIdOfUserTryingToLogIn($user['id']);
							
							if ($this->setting('requires_terms_and_conditions') == "always") {
								$this->acceptTsAndCs($user);
							}
							
							return true;
						
						} else {
							if ($user['status'] == 'suspended') {
								//user is suspended, show error
								$errorMessage = $this->setting('account_suspended_message');
								$this->errors[] = ['Error' => $this->phrase($errorMessage)];
							} elseif ($user['status'] == 'pending') {
								$emailVerifiedStatus = ze\row::get('users', 'email_verified', ['email' => ze::post('extranet_email')]);
								
								//user is pending, show error
								if ($emailVerifiedStatus == 'verified') {
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

						$this->holdFailureAgainstCaptcha();
					}
				} else {
					//User is not externet user just a contact
					$errorMessage = $this->setting('contact_not_extranet_message');
					$this->errors[] = ['Error' => $this->phrase($errorMessage)];
				}
			} else {
				//email address or screen name not in DB
				if ($this->signInUsingEmailAddress()) {
					//email
					$errorMessage = $this->setting('email_not_in_db_message');
					$this->errors[] = ['Error' => $this->phrase($errorMessage)];
				} else {
					//screen name
					$errorMessage = $this->setting('screen_name_not_in_db_message');
					$this->errors[] = ['Error' => $this->phrase($errorMessage)];
				}
				
				//As this is a failed login, if Captcha is enabled, do not treat it as a pass.
				$this->holdFailureAgainstCaptcha();
			}
		}
		
		return false;
	}
	
	protected function setupLoginStep() {
		
		ze\cookie::hideConsent();
		
		if ($this->setting('enable_remember_me') && ze\cookie::canSet('functionality')) {
			$this->subSections['Remember_Me_Section'] = true;
		}

		if ($this->setting('enable_log_me_in') && ze\cookie::canSet('functionality')) {
			$this->subSections['Log_Me_In_Section'] = true;
		}
		
		if ($this->setting('requires_terms_and_conditions') == "always") {
			$this->addTsAndCsMergeFields();
		}
		
		if ($this->signInUsingEmailAddress()) {
			$this->objects['extranet_email'] = $_COOKIE['COOKIE_LAST_EXTRANET_EMAIL'] ?? false;
		} else {
			$this->objects['extranet_screen_name'] = $_COOKIE['COOKIE_LAST_EXTRANET_SCREEN_NAME'] ?? false;
		}
		
		if ($this->enableCaptcha(true)) {
			$captchaSetting = ze::setting('captcha_status_and_version');

			if ($captchaSetting == 'enabled_v2') {
				$this->subSections['Captcha'] = true;
				$this->objects['Captcha'] = $this->captcha2();
			}
		}
	}
	
	
	
	
	//Display a login form
	protected function modeLogin() {
		$this->addLoginLinks();
		$this->add401MessageIfNeeded();
		
		$this->subSections['Login_title_section'] = true;
		$this->subSections['Login_Form'] = true;
		
		$this->objects['openForm'] = $this->getLoginOpenForm();
			
		$this->framework('Outer', $this->objects, $this->subSections);
	}
	
	protected function checkTwoFactorStep() {
		//Not yet implemented
		return true;
	}
	
	protected function setupTwoFactorStep() {
		//...
	}
	
	protected function mode2FA() {
		//...
	}
	
	protected function checkPasswordChangeStep() {
		
		//If the user has fully logged in, skip this step
		if ($this->userLoggedIn) {
			return true;
		}
		
		//If the user who is trying to log in doesn't need to change their password, skip this step
		if (!ze\row::get('users', 'password_needs_changing', $this->idOfUserTryingToLogIn)) {
			return true;
		}

		if (ze::post('extranet_change_password')) {
			
			$newPassword = $_POST['extranet_new_password'] ?? '';
			$confirmation = $_POST['extranet_new_password_confirm'] ?? '';

			if (!$newPassword) {
				$errorMessage = $this->setting('no_new_password_error_text') ? $this->setting('no_new_password_error_text') : 'Please enter a new password.';
				$this->errors[] = ['Error' => $this->phrase($errorMessage)];
		
			} elseif (ze\user::checkPassword($this->idOfUserTryingToLogIn, $newPassword)) {
				//new password is the same as old
				$errorMessage = $this->setting('new_password_same_as_old_message') ? $this->setting('new_password_same_as_old_message') : 'Your new password is the same as your old password.';
				$this->errors[] = ['Error' => $this->phrase($errorMessage)];
		
			} elseif (!$confirmation) {
				//no repeat password
				$this->errors[] = ['Error' => ze\lang::phrase('Please repeat your new password.', false, $vlpClass)];
		
			} elseif ($newPassword !== $confirmation) {
				//passwords don't match
				$errorMessage = $this->setting('new_passwords_do_not_match') ? $this->setting('new_passwords_do_not_match') : 'Your repeated password does not match.';
				$this->errors[] = ['Error' => $this->phrase($errorMessage)];
	
			//checkPasswordStrength now returns an array instead of just a boolean.
			} else {
				$passwordValidation = ze\user::checkPasswordStrength($newPassword, $checkIfEasilyGuessable = true);
				if (!$passwordValidation['password_matches_requirements']) {
					if ($passwordValidation['password_is_too_easy_to_guess']) {
						//password too easy to guess
						$errorMessage = ze\lang::phrase('Password is too easy to guess.');
						$this->errors[] = ['Error' => $errorMessage];
					} else {
						//password not strong enough: not enough characters
						$errorMessage = ze\lang::phrase('Password does not match the requirements.');
						$this->errors[] = ['Error' => $errorMessage];
					}
				}
			}
			
			if (empty($this->errors)) {
				ze\userAdm::setPassword($this->idOfUserTryingToLogIn, $newPassword, false);
				return true;
			}
		}
		
		return false;
	}
	
	protected function setupPasswordChangeStep() {
		$this->getTitleAndLabelMergeFields();
		
		$this->subSections = [];
		$this->subSections['Password_Error_Display'] = $this->errors;

	}
	
	// Display a change password form
	protected function modeChangePassword(){
		
		echo $this->openForm($onSubmit = '', $extraAttributes = '', $action = false, $scrollToTopOfSlot = true, $fadeOutAndIn = true);
			$this->framework('Change_Password_Form', $this->objects, $this->subSections);
		echo $this->closeForm();
	}
	
	protected function checkTermsStep() {
		
		$needTsAndCs = $this->setting('requires_terms_and_conditions');
		
		//If the setting to check the T&C is not enabled, we don't need to show this step.
		if (!$needTsAndCs) {
			return true;
		}
		
		//If the T&C was checked as part of the login, we also don't need this step again
		if ($needTsAndCs === 'always') {
			return true;
		}
		
		$user = ze\row::get('users', ['first_name', 'last_name', 'email', 'terms_and_conditions_accepted'], $this->idOfUserTryingToLogIn);
		
		//We also don't need it if the user has already accepted the T&C.
		if (!empty($user['terms_and_conditions_accepted'])) {
			return true;
		}
		
		//Check if we have a form submission from the T&C form.
		if (isset($_POST['accept_terms_and_conditions'])) {
			
			//Validate the form.
			//(I.e. "did they check the checkbox?")
			$this->errors = [];
			if (empty($_POST['extranet_terms_and_conditions'])) {
				$this->errors[] = ['Error' => $this->setting('accept_terms_and_conditions_message')];
			}
			
			if (empty($this->errors)) {
				$this->acceptTsAndCs($user);
				return true;
			}
		}
		
		return false;
	}
	
	protected function acceptTsAndCs($user) {
		//Record consent
		//Please note: if an admin is impersonating a user, there will be no consents entry.
		if (!ze\admin::id()) {
			$userContentItem = $this->setting('terms_and_conditions_page');
			$useExternalLink = $this->setting('url');
			if ($userContentItem || $useExternalLink) {
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
			ze\user::recordConsent('extranet_login', $this->instanceId, $this->idOfUserTryingToLogIn, $user['email'], $user['first_name'], $user['last_name'], $this->phrase("I have read and accept the [[link_start]]Terms and Conditions[[link_end]].", ['link_start' => $linkStart, 'link_end' => $linkEnd]));
		}
		
		ze\row::set('users', ['terms_and_conditions_accepted' => 1], $this->idOfUserTryingToLogIn);
	}
	
	protected function setupTermsStep() {
		$this->addTsAndCsMergeFields();
	}
	
	protected function addTsAndCsMergeFields() {
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
	
	protected function modeTermsAndConditions() {
		$this->getTitleAndLabelMergeFields();
		$this->objects['login_button_text'] = $this->phrase('Continue');
		$this->objects['Welcome_Message'] = $this->getWelcomeUserString();
		$this->objects['Is_Admin'] = ze\admin::id();
		
		echo $this->getLoginOpenForm($onSubmit = '', $extraAttributes = '', $action = false, $scrollToTopOfSlot = true, $fadeOutAndIn = true);
			echo $this->remember('accept_terms_and_conditions', 1);
			$this->framework('Terms_And_Conditions_Form',  $this->objects, $this->subSections);
		echo $this->closeForm();
	}
	
	protected function setupLogin() {
		
		//Log the user in if they're passed all of the steps
		if (!$this->userLoggedIn) {
			$this->logUserIn($this->idOfUserTryingToLogIn);
			$this->redirectToPage();
		}
	}
	
	
	//Display a welcome message when the user is logged in
	protected function modeLoggedIn() {
		$this->addLoggedInLinks();
		
		$this->subSections['Logged_In'] = true;
		$this->framework('Outer', $this->objects, $this->subSections);
	}
	
	
	
	
	
	
	
	
	
	
	protected function setIdOfUserTryingToLogIn($userId) {
		
		$this->idOfUserTryingToLogIn =
		$_SESSION['zenario_loggingInUserID'] = $userId;
		$_SESSION['zenario_loggingInUserSite'] = COOKIE_DOMAIN. SUBDIRECTORY. \ze::setting('site_id');
	}
	
	protected function getIdOfUserTryingToLogIn() {
		
		if (isset($_SESSION['zenario_loggingInUserID'], $_SESSION['zenario_loggingInUserSite'])
		 && ze\user::idAndSessionIsValid($_SESSION['zenario_loggingInUserID'], $_SESSION['zenario_loggingInUserSite'])) {
			
			return $_SESSION['zenario_loggingInUserID'];
		
		} else {
			return false;
		}
	}

	protected function add401MessageIfNeeded() {
		if (http_response_code() == 401) {
			$this->objects['Redirect_Message'] = $this->phrase("You've requested a page for which access requires an account on this site. If you have an account, please log in using the form below.");
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
				ze\content::langEquivalentItem($cID, $cType);
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
				ze\content::langEquivalentItem($cID, $cType);
				$this->objects['Login_Link'] = $this->linkToItemAnchor($cID, $cType);
			}
		} else {
			$this->subSections['Login_Link_Section'] = false;
		}
	}
	
	protected function addLoggedInLinks() {
		$this->subSections['Welcome_Message_Section'] = true;
		
		$this->objects['Welcome_Message'] = $this->getWelcomeUserString();
		
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
			$this->objects['destURL_Link'] = htmlspecialchars($_SESSION['destURL']);
			
			if (isset($_SESSION['destTitle'])) {
				$this->objects['destURL_Title'] = htmlspecialchars($_SESSION['destTitle']);

			} elseif (isset($_SESSION['destCID'])) {
				$this->objects['destURL_Title'] = htmlspecialchars(ze\content::title($_SESSION['destCID'], $_SESSION['destCType']));

			} else {
				$this->objects['destURL_Title'] = htmlspecialchars($this->phrase('Click here to be redirected back to where you just came from.'));
			}
		}
	}
	
	
	
	
	public function signInUsingEmailAddress() {
		return !ze::setting('user_use_screen_name') || $this->setting('login_with') == 'Email';
	}
	
	
	protected function manageCookies() {
		
		//Set the User's email/screenname cookie on their local machine if requested
		if (isset($_SESSION['SET_EXTRANET_LOGIN_COOKIE'])) {
			if (ze\cookie::canSet('functionality')) {
				if ($this->signInUsingEmailAddress()) {
					ze\cookie::set('COOKIE_LAST_EXTRANET_EMAIL', $_SESSION['SET_EXTRANET_LOGIN_COOKIE']);
				} else {
					ze\cookie::set('COOKIE_LAST_EXTRANET_SCREEN_NAME', $_SESSION['SET_EXTRANET_LOGIN_COOKIE']);
				}
			}
			unset($_SESSION['SET_EXTRANET_LOGIN_COOKIE']);
		
		//Remove the User's email/screenname cookie on their local machine if requested
		} elseif (isset($_SESSION['FORGET_EXTRANET_LOGIN_COOKIE'])) {
			if ($this->signInUsingEmailAddress()) {
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
			if (ze::post('extranet_remember_me')) {
				if ($this->signInUsingEmailAddress()) {
					$_SESSION['SET_EXTRANET_LOGIN_COOKIE'] = $user['email'];
				} else {
					$_SESSION['SET_EXTRANET_LOGIN_COOKIE'] = $user['screen_name'];
				}
			} elseif ($this->setting('enable_remember_me')) {
				$_SESSION['FORGET_EXTRANET_LOGIN_COOKIE'] = true;
			}
		}
			
		if ($this->setting('enable_log_me_in')) {
			if (ze::post('extranet_log_me_in')) {
				$_SESSION['SET_EXTRANET_LOG_ME_IN_COOKIE'] = $user['login_hash'];
			}
		}
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
	
	protected function getDetailsFromEmail($email) {
		return ze\row::get('users', ['id', 'first_name', 'last_name', 'screen_name', 'password', 'password_salt', 'email', 'hash', 'status'], ['email' => $email]);
	}
	
	function validatePassword($newPassword, $confirmation, $oldPassword = false, $vlpClass = false, $userId = false) {
		$errors = [];
	
		//Look up what their current password is
		if (trim($oldPassword)==='' && $oldPassword!==false)  {
			//no password entered
			$errorMessage = $this->setting('no_password_entered_message') ? $this->setting('no_password_entered_message') : 'Please enter your old password.';
			$errors[] = ['Error' => $this->phrase($errorMessage)];
		} elseif ($oldPassword && !ze\user::checkPassword($userId, $oldPassword)) {
			//password incorrect
			$errorMessage = $this->setting('wrong_password_message') ? $this->setting('wrong_password_message') : 'You did not enter your old password correctly.';
			$errors[] = ['Error' => $this->phrase($errorMessage)];
		}
	
		if (!$newPassword) {
			$errorMessage = $this->setting('no_new_password_error_text') ? $this->setting('no_new_password_error_text') : 'Please enter a new password.';
			$errors[] = ['Error' => ze\lang::phrase($errorMessage, false, $vlpClass)];
		
		} elseif ($oldPassword && ($newPassword === $oldPassword)) {
			//new password is the same as old
			$errorMessage = $this->setting('new_password_same_as_old_message') ? $this->setting('new_password_same_as_old_message') : 'Your new password is the same as your old password.';
			$errors[] = ['Error' => $this->phrase($errorMessage)];
		
		} elseif (!$confirmation) {
			//no repeat password
			$errors[] = ['Error' => ze\lang::phrase('Please repeat your new password.', false, $vlpClass)];
		
		} elseif ($newPassword !== $confirmation) {
			//passwords don't match
			$errorMessage = $this->setting('new_passwords_do_not_match') ? $this->setting('new_passwords_do_not_match') : 'Your repeated password does not match.';
			$errors[] = ['Error' => $this->phrase($errorMessage)];
	
		//checkPasswordStrength now returns an array instead of just a boolean.
		} else {
			$passwordValidation = ze\user::checkPasswordStrength($newPassword, $checkIfEasilyGuessable = true);
			if (!$passwordValidation['password_matches_requirements']) {
				if ($passwordValidation['password_is_too_easy_to_guess']) {
					//password too easy to guess
					$errorMessage = ze\lang::phrase('Password is too easy to guess.');
					$errors[] = ['Error' => $errorMessage];
				} else {
					//password not strong enough: not enough characters
					$errorMessage = ze\lang::phrase('Password does not match the requirements.');
					$errors[] = ['Error' => $errorMessage];
				}
			}
		}
	
		return $errors;
	}
	
	protected function getTitleAndLabelMergeFields() {
		$this->objects['main_login_heading'] = $this->phrase('Sign in');
		$this->objects['email_field_label'] = $this->phrase('Your email:');
		$this->objects['screen_name_field_label'] = $this->phrase('Your screen name:');
		$this->objects['password_field_label'] = $this->phrase('Your password:');
		$this->objects['login_button_text'] = $this->phrase('Login');
	}
	
	protected function getWelcomeUserString() {
		if ($this->idOfUserTryingToLogIn) {
			$userId = $this->idOfUserTryingToLogIn;
		} else {
			$userId = ze\user::id();
		}
		
		$user = ze\row::get('users', ['first_name', 'last_name'], $userId);
		
		$userNameString = '';
		
		if (!empty($user['first_name'])) {
			$userNameString .= $user['first_name'];
		}
		
		//This code is currently commented out but will be in use in the future.
		// if (!empty($user['first_name'])) {
// 			if ($userNameString) {
// 				$userNameString .= ' ';
// 			}
// 			
// 			$userNameString .= $user['last_name'];
// 		}
		
		return $this->phrase('Welcome, [[user]]!', ['user' => htmlspecialchars($userNameString)]);
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

	protected function enableCaptcha($checkCaptchaFrequency = false) {
		if ($checkCaptchaFrequency) {
			switch ($this->setting('captcha_frequency')) {
				case 'after_1_failed_login_attempt':
					$failedLoginsRequired = 1;
					break;
				case 'after_2_failed_login_attempts':
					$failedLoginsRequired = 2;
					break;
				case 'after_3_failed_login_attempts':
					$failedLoginsRequired = 3;
					break;
				case 'always':
				default:
					$failedLoginsRequired = 0;
					break;
			}

			if (empty($_SESSION['extranet_user_failed_logins_count'])) {
				$_SESSION['extranet_user_failed_logins_count'] = 0;
			}

			return $this->setting('use_captcha') && empty($_SESSION['captcha_passed__'. $this->instanceId]) && ze::setting('captcha_status_and_version') == 'enabled_v2' && ze::setting('google_recaptcha_site_key') && ze::setting('google_recaptcha_secret_key') && $_SESSION['extranet_user_failed_logins_count'] >= $failedLoginsRequired;
		} else {
			return $this->setting('use_captcha') && empty($_SESSION['captcha_passed__'. $this->instanceId]) && ze::setting('captcha_status_and_version') == 'enabled_v2' && ze::setting('google_recaptcha_site_key') && ze::setting('google_recaptcha_secret_key');
		}
	}

	protected function holdFailureAgainstCaptcha() {
		
		if (isset($_SESSION['extranet_user_failed_logins_count'])) {
			$_SESSION['extranet_user_failed_logins_count']++;
		} else {
			$_SESSION['extranet_user_failed_logins_count'] = 1;
		}
		
		switch ($this->setting('captcha_frequency') ?? false) {
			case 'after_1_failed_login_attempt':
				$failedLoginsRequired = 1;
				break;
			case 'after_2_failed_login_attempts':
				$failedLoginsRequired = 2;
				break;
			case 'after_3_failed_login_attempts':
				$failedLoginsRequired = 3;
				break;
			case 'always':
			default:
				$failedLoginsRequired = 0;
				break;
		}

		if ($_SESSION['extranet_user_failed_logins_count'] >= $failedLoginsRequired) {
			unset($_SESSION['captcha_passed__'. $this->instanceId]);
		}
	}
	
	public function addToPageHead() {
		if ($this->enableCaptcha(true)) {
			$this->loadCaptcha2Lib();
		}
	}
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				if (isset($fields['first_tab/login_with'])) {
					$fields['first_tab/login_with']['readonly'] = !ze::setting('user_use_screen_name');
				}
		
				$defaultLangId = ze::$defaultLang;
				foreach ([
					'error_messages/invalid_email_error_text' => 'Please enter a valid email address.',		
					'error_messages/screen_name_required_error_text' => 'Please enter your screen name.',		
					'error_messages/email_address_required_error_text' => 'Please enter your email address.',			
					'error_messages/password_required_error_text' => 'Please enter your password.',		
					'error_messages/no_new_password_error_text' => 'Please enter a new password.',
					'error_messages/no_new_repeat_password_error_text' => 'Please repeat your password.'
				] as $fieldName => $code) {
					if (isset($fields[$fieldName])) {
						$visitorPhrase = ze\row::get('visitor_phrases', 'local_text', ['code' => $code, 'language_id' => $defaultLangId]);
						
						//If the lookup returns nothing, don't override the default phrase.
						if ($visitorPhrase) {
							$values[$fieldName] = $visitorPhrase;
						}
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
				
				
				//Load lists for redirect rules and disable "role" based rules if Organization Manager is not running.
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

				//Disable Captcha feature if not set up in the API keys
				if (ze::setting('captcha_status_and_version') != 'enabled_v2' || !ze::setting('google_recaptcha_site_key') || !ze::setting('google_recaptcha_secret_key')) {
				    //Show warning
					$recaptchaLink = "<a href='organizer.php#zenario__administration/panels/site_settings//api_keys~.site_settings~tcaptcha_picture~k{\"id\"%3A\"api_keys\"}' target='_blank'>site settings</a>";
					$fields['use_captcha']['side_note'] = $this->phrase(
						"Recaptcha keys are not set. To show a captcha you must set the recaptcha [[recaptcha_link]].",
						['recaptcha_link' => $recaptchaLink]
					);
					$fields['use_captcha']['readonly'] = true;
                    $fields['use_captcha']['value'] = 0;

					unset($fields['captcha_frequency']);
				}
				
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

			ze\tuix::flagEncryptedColumns($panel, 'u', 'users');
			ze\tuix::flagEncryptedColumns($panel, 'usl', 'user_signin_log');

			if ($refinerName == 'user' && $refinerId) {
				$identifier = ze\row::get('users', 'identifier', $refinerId);
				if (!$identifier) {
					$identifier = ze\admin::phrase('(user deleted)');
				}
				
				$panel['title'] = ze\admin::phrase('User sign-in log for user [[identifier]]', ['identifier' => $identifier]);
			}
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__users/nav/sign_in_log/panel':
				
				//If it looks like a site is supposed to be using encryption, but it's not set up properly,
				//show an error message.
				ze\pdeAdm::showNoticeOnPanelIfConfIsBad($panel);
				
				if (ze::$dbL->columnIsEncrypted('users', 'first_name') || ze::$dbL->columnIsEncrypted('users', 'last_name')) {
					$panel['columns']['User_Name']['encrypted'] = [];
					
					if (ze::$dbL->columnIsHashed('users', 'first_name') || ze::$dbL->columnIsHashed('users', 'last_name')) {
						$panel['columns']['User_Name']['encrypted']['hashed'] = true;
					}
				}
				
				foreach ($panel['items'] as $id => &$item) {
					$fullName = '';
					
					if ($item['First_Name']) {
						$fullName .= $item['First_Name'];
					}
					
					if ($item['Last_Name']) {
						if ($item['First_Name']) {
							$fullName .= ' ';
						}
						
						$fullName .= $item['Last_Name'];
					}
					
					$item['User_Name'] = $fullName;
				}
			break;
		}
	}
}