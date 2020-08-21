<?php
/*
 * Copyright (c) 2020, Tribal Limited
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

class zenario_user_forms extends ze\moduleBaseClass {
	
	protected $data = false;
	protected $inFullScreen = false;
	protected $dataset = false;
	protected $form = false;
	protected $pages = [];
	protected $fields = [];
	protected $errors = [];
	protected $datasetFieldsLink = [];
	protected $datasetFieldsColumnLink = [];
	protected $formPageHash = false;
	protected $displayMode = false;
	protected $reloaded = false;
	protected $userId = false;
	
	public function init() {
		ze::requireJsLib('zenario/libs/manually_maintained/mit/jquery/jquery-ui.datepicker.min.js');
		ze::requireJsLib('zenario/libs/manually_maintained/mit/jquery/jquery-ui.sortable.min.js');
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = false, $ifCookieSet = false);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = true, $clearByFile = false, $clearByModuleData = true);
		
		$formId = $this->setting('user_form');
		
		//This plugin must have a form selected
		if (!$formId) {
			if (ze\admin::id()) {
				$this->data['form_HTML'] = '<p class="error">' . htmlspecialchars(ze\admin::phrase('You must select a form for this plugin.')) . '</p>';
			}
			return true;
		}
		
		$this->userId = $this->getUserId();
		
		$this->dataset = ze\dataset::details('users');
		$this->form = static::getForm($formId);
		$t = $this->form['translate_text'];
		
		//Email verification for registration forms
		if ($this->form['type'] == 'registration') {
			if (isset($_GET['confirm_email']) && isset($_GET['hash'])) {
				$user = ze\row::get('users', ['id', 'email_verified'], ['hash' => $_GET['hash']]);
				if (!$user) {
					$this->data['form_HTML'] = '<p class="error">' . htmlspecialchars(static::fPhrase('We are sorry, but we were unable to find your registration. Please check whether the verification link is correct.', [], $t)) . '</p>';
				} elseif ($user['email_verified']) {
					$this->data['form_HTML'] = '<p class="success">' . htmlspecialchars(static::fPhrase('This email address has already been verified.', [], $t)) . '</p>';
				} else {
					ze\row::update('users', ['email_verified' => 1, 'status' => 'active'], $user['id']);
					$this->sendWelcomeEmail($user['id']);
					$this->logUserIn($user['id']);
					
					if ($this->form['welcome_redirect_location']) {
						$cID = $cType = false;
						ze\content::getCIDAndCTypeFromTagId($cID, $cType, $this->form['welcome_redirect_location']);
						ze\content::langEquivalentItem($cID, $cType);
						$redirectURL = ze\link::toItem($cID, $cType);
						$this->headerRedirect($redirectURL);
						return true;
					} else {
						$this->data['form_HTML'] = '<p class="success">' . static::fPhrase($this->form['welcome_message'], [], $t) . '</p>';
					}
				}
				return true;
			//Form for resending verification email
			} elseif (isset($_REQUEST['extranet_resend'])) {
				$error = false;
				$value = $_REQUEST['email'] ?? false;
				$sent = false;
				if (isset($_REQUEST['email'])) {
					if ($value === '') {
						$error = 'Please enter your email address.';
					} elseif (!ze\ring::validateEmailAddress($value)) {
						$error = 'Please enter a valid email address.';
					} elseif (!ze\row::exists('users', ['email' => $value])) {
						$error = 'This email address is not associated with any account.';
					} elseif (ze\row::get('users', 'email_verified', ['email' => $value])) {
						$error = 'This email address has already been verified.';
					} elseif (ze\row::get('users', 'status', ['email' => $value]) == 'contact') {
						$error = 'The email address entered is associated with a contact, not an extranet user. Please contact the administrator for more assistance.';
					} elseif (ze\row::get('users', 'status', ['email' => $value]) == 'suspended') {
						$error = 'Your account is suspended. Please contact the site administrator for assistance.';
					} else {
						$user = ze\row::get('users', ['id'], ['email' => $value]);
						$this->sendVerificationEmail($user['id']);
						
						$this->data['form_HTML'] = '<p class="success">' . htmlspecialchars(static::fPhrase('Your verification email has been resent. Please be sure to check your spam/bulk mail folder.', [], $t)) . '</p>';
						return true;
					}
				}
				$html = $this->openForm();
				$html .= $this->remember('extranet_resend');
				$html .= $this->getFormTitle('Resend verification email');
				$html .= '
					<div class="form_fields">
						<div class="form_field">
						<div class="field_title">' . htmlspecialchars(static::fPhrase('Email:', [], $t)) . '</div>';
				if ($error && !$this->form['show_errors_below_fields']) {
					$html .= '
						<div class="form_error">' . htmlspecialchars(static::fPhrase($error, [], $t)) . '</div>';
				}
				$html .= '
						<input type="text" name="email" value="' . htmlspecialchars($value) . '">';
				if ($error && $this->form['show_errors_below_fields']) {
					$html .= '
						<div class="form_error">' . htmlspecialchars(static::fPhrase($error, [], $t)) . '</div>';
				}
				$html .= '
						</div>
					</div>';
				$html .= '<div class="form_buttons">';
				$html .= '<input type="submit" class="next submit" value="' . htmlspecialchars(static::fPhrase('Resend verification email', [], $t)) . '"/>';
				$html .= '</div>';
				
				$html .= $this->closeForm();
				$html .= $this->getExtranetLinksHTML(['register' => true, 'login' => true]);
				$this->data['form_HTML'] = $html;
				return true;
			}
			if ($this->userId) {
				$html = $this->getWelcomePageHTML();
				$html  .= $this->getExtranetLinksHTML(['change_password' => true, 'logout' => true]);
				$this->data['form_HTML'] = $html;
				return true;
				
			}
		}
		
		//Partial completion forms must be placed on a private page and have a user logged in
		if ($this->form['allow_partial_completion']) {
			$equivId = ze\content::equivId($this->cID, $this->cType);
			$privacy = ze\row::get('translation_chains', 'privacy', ['equiv_id' => $equivId, 'type' => $this->cType]);
			if ($privacy == 'public') {
				if (ze\admin::id()) {
					$this->data['form_HTML'] = '<p class="error">' . htmlspecialchars(ze\admin::phrase('This form has the "save and complete later" feature enabled and so must be placed on a password-protected page.')) . '</p>';
				}
				return true;
			}
			if (!$this->userId) {
				if (ze\admin::id()) {
					$this->data['form_HTML'] = '<p class="error">' . htmlspecialchars(ze\admin::phrase('You must be logged in as an Extranet User to see this Plugin.')) . '</p>';
				}
				return true;
			}
		}
		
		if ($this->methodCallIs('handlePluginAJAX')) {
			return true;
		}
		
		$this->reloaded = ($_POST['reloaded'] ?? false) && ($this->instanceId == ($_POST['instanceId'] ?? false));
		$reloadedWithAjax = $this->reloaded && $this->methodCallIs('refreshPlugin');
		
		//Decide whether to display plugin contents in a modal window
		$showInFloatingBox = false;
		$loadContentInColorbox = false;
		$this->displayMode = $this->setting('display_mode');
		if ($this->displayMode == 'in_modal_window') {
			if ($reloadedWithAjax || ($_GET['showInFloatingBox'] ?? false)) {
				$showInFloatingBox = true;
				$floatingBoxParams = [
					'escKey' => false, 
					'overlayClose' => false, 
					'closeConfirmMessage' => static::fPhrase('Are you sure you want to close this window? You will lose any changes.', [], $t),
				];
				$this->showInFloatingBox(true, $floatingBoxParams);
			//If colorbox was forced to close e.g. there was a file input field on the form, reopen it in JS.
			} elseif ($this->reloaded && !$reloadedWithAjax) {
				$loadContentInColorbox = true;
			} else {
				$this->data['form_HTML'] = $this->getModalWindowButtonHTML();
				return true;
			}
		} elseif ($this->displayMode == 'inline_popup') {
			if ($this->reloaded) {
				$this->cssClass .= ' show';
			} else {
				$this->cssClass .= ' hide';
			}
		}
		
		//Logged in user duplicate submission check
		if ($this->userId && $this->form['no_duplicate_submissions']) {
			if (ze\row::exists(ZENARIO_USER_FORMS_PREFIX . 'user_response', ['user_id' => $this->userId, 'form_id' => $formId])) {
				$html = '<p class="info">' . htmlspecialchars(static::fPhrase($this->form['duplicate_submission_message'], [], $t)) . '</p>';
				$html .= $this->getCloseButtonHTML();
				$this->cssClass .= ' no_title';
				$this->data['form_HTML'] = $html;
				
				return true;
			}
		}
		
		if (!empty($_GET['formPageHash']) && empty($_POST) && empty($_FILES) && !empty($_SERVER['CONTENT_LENGTH'])) {
			$max = $this->getMaxPostSize();
			$this->errors['global_top'] = static::fPhrase('Exceeded form size limit of [[max]].', ['max' => $max], $t);
			$this->formPageHash = $_GET['formPageHash'];
			
		} elseif (!$this->reloaded) {
			//Each form has a unique hash in case multiple windows/tabs are opened of the same form
			$this->formPageHash = md5(time() + rand());
			
			//Check if there is a part completed submission for this user to load initially
			if ($this->form['allow_partial_completion']) {
				$getRequestValue = null;
				if ($this->form['partial_completion_get_request']) {
					$getRequestValue = $_REQUEST[$this->form['partial_completion_get_request']] ?? 0;
				}
				$partialSaveFound = ze\row::exists(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response', ['user_id' => $this->userId, 'form_id' => $formId, 'get_request_value' => $getRequestValue]);
				if ($partialSaveFound) {
					if (!$this->form['allow_clear_partial_data'] || ($_POST['resume'] ?? false)) {
						$this->loadPartialSaveData($this->userId, $formId, $getRequestValue);
					} elseif ($this->form['allow_clear_partial_data'] && ($_POST['clear'] ?? false)) {
						static::deleteOldPartialResponse($formId, $this->userId, $getRequestValue);
					} else {
						$html = $this->getPartialSaveResumeFormHTML();
						$html .= $this->getCloseButtonHTML();
						$this->cssClass .= ' no_title';
						$this->data['form_HTML'] = $html;
						return true;
					}
				}
			}
		} else {
			$this->formPageHash = $_POST['formPageHash'];
		}
				
		if (!isset($_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash])) {
			$ord = isset($_SESSION['custom_form_data'][$this->instanceId]) ? count($_SESSION['custom_form_data'][$this->instanceId]) : 0;
			$_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash] = ['date_created' => date('Y-m-d H:i:s'), 'ord' => $ord];
			//Limit stored form sessions
			$limit = 5;
			if (isset($_SESSION['custom_form_data'][$this->instanceId]) && $ord >= $limit) {
				uasort($_SESSION['custom_form_data'][$this->instanceId], function($a, $b) {
					return (isset($a['ord']) && isset($b['ord']) && $a['ord'] < $b['ord']) ? 1 : -1;
				});
				foreach ($_SESSION['custom_form_data'][$this->instanceId] as $hash => $data) {
					unset($_SESSION['custom_form_data'][$this->instanceId][$hash]);
					if (count($_SESSION['custom_form_data'][$this->instanceId]) <= $limit) {
						break;
					}
				}
			}
		}
		
		//Get page
		$this->pages = static::getFormPages($formId);
		if ($this->form['enable_summary_page']) {
			$summaryPageId = 'summary';
			$this->pages[$summaryPageId] = [
				'id' => $summaryPageId,
				'form_id' => $formId,
				'ord' => end($this->pages)['ord'] + 100,
				'name' => $summaryPageId,
				'label' => $summaryPageId,
				'visibility' => 'visible',
				'previous_button_text' => static::fPhrase('Previous', [], $t),
				'hide_in_page_switcher' => true,
				'fields' => []
			];
			
		}
		if (!$this->pages) {
			return false;
		}
		$pageId = reset($this->pages)['id'];
		$currentPageId = $_POST['current_page'] ?? $pageId;
		
		//Get fields per page
		$this->fields = $this->getFormFields($formId);
		foreach ($this->fields as $fieldId => $field) {
			if ($field['dataset_field_id']) {
				if ($field['db_column'] && is_numeric($fieldId)) {
					$this->datasetFieldsColumnLink[$field['db_column']] = $fieldId;
				}
				$this->datasetFieldsLink[$fieldId] = $field['dataset_field_id'];
			}
			$this->pages[$field['page_id']]['fields'][] = $fieldId;
		}
		
		//Save data from previous page if changing
		if ($this->reloaded) {
			$this->savePageData($currentPageId, $_POST);
		}
		
		//Load current values for each field
		foreach ($this->fields as $fieldId => $field) {
			$this->fields[$fieldId]['value'] = $this->getFieldCurrentValue($fieldId);
		}
		
		//Load form data
		$this->preloadCustomData();
		$formFinalSubmitSuccessfull = false;
		if ($this->reloaded) {
			//Change page
			$pageId = $this->getNextFormPage($currentPageId);
			if (!isset($_POST['filter'])) {
				$submitted = !empty($_POST['submitForm']);
				$moveToHigherPage = $this->pages[$pageId]['ord'] > $this->pages[$currentPageId]['ord'];
				$saveToCompleteLaterButtonPressed = $this->form['allow_partial_completion'] && !empty($_POST['saveLater']);
				$saveToCompleteLaterPageNav = $this->form['allow_partial_completion'] && in_array($this->form['partial_completion_mode'], ['auto', 'auto_and_button']) && $currentPageId != $pageId;
				
				$valid = true;
				if ($submitted || $moveToHigherPage || $saveToCompleteLaterButtonPressed) {
					$valid = $this->validateForm($currentPageId, $validateAllFields = $submitted, $ignoreRequiredFields = $saveToCompleteLaterButtonPressed);
				}
				if (!$valid) {
					if ($submitted) {
						foreach ($this->errors as $fieldId => $error) {
							if (isset($this->fields[$fieldId])) {
								$pageId = $this->fields[$fieldId]['page_id'];
								break;
							}
						}
					} else {
						$pageId = $currentPageId;
					}	
				} elseif ($saveToCompleteLaterButtonPressed || $saveToCompleteLaterPageNav) {
					$this->createFormPartialResponse();
					if ($saveToCompleteLaterButtonPressed) {
						if ($this->form['partial_completion_message']) {
							$successMessage = static::fPhrase($this->form['partial_completion_message'], [], $t);
						} elseif (ze\admin::id()) {
							$successMessage = ze\admin::phrase('Your partial completion message will go here when you set it.');
						} else {
							$successMessage = 'Your data will be here the next time you open this form.';
						}
						$html = '<div class="success">' . $successMessage . '</div>';
						$html .= $this->getCloseButtonHTML();
						$this->cssClass .= ' no_title';
						$this->data['form_HTML'] = $html;
						return true;
					}
				} elseif ($submitted) {
					$responseId = $this->saveForm();
					$this->successfulFormSubmit($responseId);
					$formFinalSubmitSuccessfull = true;
					
					unset($_SESSION['captcha_passed__' . $this->instanceId]);
					
					//After submitting form redirect to SIMPLE_ACCESS request page
					if ($this->form['simple_access_cookie_override_redirect']) {
						//check if we can set cookies
						if(!empty($_COOKIE['SIMPLE_ACCESS']) && isset($_REQUEST['rci'])) {
							$cID = $cType = false;
							ze\content::getCIDAndCTypeFromTagId($cID, $cType, $_REQUEST['rci']);
							ze\content::langEquivalentItem($cID, $cType);
							$redirectURL = ze\link::toItem($cID, $cType);
							$this->headerRedirect($redirectURL);
							return true;
						}
					}
					
					//After submitting the form, show a success message.
					//Check the settings to determine whether to keep showing the form HTML too.
					if ($this->form['show_success_message'] || $this->form['show_success_message_and_the_form']) {
						
						//If this is the "Extranet Profile" plugin, it will have its own success message (the "profile_updated" plugin setting).
						//It will be added later on as a global success message if not blank.
						//Otherwise, the setting check will return false or an empty string, and the form success message setting will be used instead.
						if (!empty($this->setting('profile_updated'))) {
							$html = '<div class="success">' . $this->setting('profile_updated') . '</div>';
						} else {
							$html = $this->getSuccessMessageHTML();
						}
						
						$html .= $this->getCloseButtonHTML();
						$this->cssClass .= ' no_title';
						
						if ($this->form['show_success_message_and_the_form']) {
							$html .= $this->getFormHTML($pageId);
						}
						
						$this->data['form_HTML'] = $html;
						return true;
					//Or redirect to another page
					} elseif ($this->form['redirect_after_submission'] && $this->form['redirect_location']) {
						$cID = $cType = false;
						ze\content::getCIDAndCTypeFromTagId($cID, $cType, $this->form['redirect_location']);
						ze\content::langEquivalentItem($cID, $cType);
						$redirectURL = ze\link::toItem($cID, $cType);
						$this->headerRedirect($redirectURL);
						return true;
					}
					//Or stay on the form
					$pageId = reset($this->pages)['id'];
					unset($_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]);
				}
			}
		}
		
		foreach ($this->pages as $tPageId => &$tPage) {
			$tPage['hidden'] = $this->isPageHidden($tPage);
		}
		$this->inFullScreen = !empty($_POST['inFullScreen']);
		
		//Get form HTML
		$colorboxFormHTML = false;
		$html = $this->getFormHTML($pageId);
		
		$this->data['form_HTML'] = $html;
		
		//Init form JS
		$allowProgressBarNavigation = $this->form['show_page_switcher'] && ($this->form['page_switcher_navigation'] == 'only_visited_pages');
		$isErrors = (bool)$this->errors;
		$maxPageReached = $_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data']['max_page_reached'] ?? false;
		
		$extraPhrases = [
			'delete' => static::fPhrase('Delete', [], $t),
			'delete_file' => static::fPhrase('Are you sure you want to delete this file?', [], $t),
			'are_you_sure_message' => static::fPhrase('Are you sure? Any unsaved changes will be lost.', [], $t),
			'combine' => static::fPhrase('Combine', [], $t),
			'combining' => static::fPhrase('Combining...', [], $t),
			'set_predefined_text_warning' => static::fPhrase('This will override the existing content, are you sure?', [], $t)
		];
		
		$this->callScript('zenario_user_forms', 'initForm', $this->containerId, $this->slotName, $this->pluginAJAXLink(), $colorboxFormHTML, $formFinalSubmitSuccessfull, $this->inFullScreen, $allowProgressBarNavigation, $pageId, $maxPageReached, $showLeavingPageMessage = true, $isErrors, json_encode($extraPhrases));
		return true;
	}
	
	protected function getExtranetLinksHTML($links) {
		$html = '';
		$t = $this->form['translate_text'];
		if ($this->form['type'] == 'registration') {
			$html .= '<div class="extranet_links">';
			$cID = $cType = false;
			if (!empty($links['resend'])) {
				$html .= '<div><a ' . $this->refreshPluginSlotAnchor('&extranet_resend=1') . '>' . htmlspecialchars(static::fPhrase('Resend verification email', [], $t)) . '</a></div>';
				$html .= '<div class="extranet_link_desc">' . htmlspecialchars(static::fPhrase('Use this if you have previously registered but not received your verification email.', [], $t)) . '</div>';
			}
			if (!empty($links['register'])) {
				if ($link = ze\link::toPluginPage('zenario_extranet_registration')) {
					$html .= '<div><a href="'. htmlspecialchars($link). '">' . htmlspecialchars(static::fPhrase('Register', [], $t)) . '</a></div>';
				}
			}
			if (!empty($links['login'])) {
				if (ze\content::langSpecialPage('zenario_login', $cID, $cType) && !$this->userId) {
					$html .= '<div><a ' . $this->linkToItemAnchor($cID, $cType) . '>' . htmlspecialchars(static::fPhrase('Go back to Login', [], $t)) . '</a></div>';
				}
			}
			if (!empty($links['change_password'])) {
				if ($link = ze\link::toPluginPage('zenario_extranet_change_password')) {
					$html .= '<div><a href="'. htmlspecialchars($link). '">' . htmlspecialchars(static::fPhrase('Change your password', [], $t)) . '</a></div>';
				}
			}
			if (!empty($links['logout'])) {
				if ($link = ze\link::toPluginPage('zenario_extranet_logout')) {
					$html .= '<div><a href="'. htmlspecialchars($link). '">' . htmlspecialchars(static::fPhrase('Logout', [], $t)) . '</a></div>';
				}
			}
			$html .= '</div>';
		}
		return $html;
	}
	
	public function showSlot() {
		$this->twigFramework($this->data);
	}
	
	public function handlePluginAJAX() {
		if (isset($_GET['fileUpload'])) {
			$data = ['files' => []];
			foreach ($_FILES as $fieldName => $file) {
				if ($file && !empty($file['tmp_name'])) {
					//Handle single and multiple file inputs
					if (!is_array($file['tmp_name'])) {
						$file['tmp_name'] = [$file['tmp_name']];
						$file['name'] = [$file['name']];
					}
					//Upload each file and return its name and filepath
					$fileCount = count($file['tmp_name']);
					for ($j = 0; $j < $fileCount; $j++) {
						if (!empty($file['tmp_name'][$j]) && ze\fileAdm::isUploadedFile($file['tmp_name'][$j]) && ze\cache::cleanDirs()) {
							$randomDir = ze\cache::createRandomDir(30, 'uploads');
							$newName = $randomDir. ze\file::safeName($file['name'][$j], true);
							
							if (!$randomDir) {
								exit('Could not create cache directory in private/uploads');
							}
							
							if (move_uploaded_file($file['tmp_name'][$j], CMS_ROOT. $newName)) {
								$cacheFile = ['name' => urldecode($file['name'][$j]), 'path' => $newName];
								
								//If requested, make thumbnails
								if ($_GET['thumbnail'] ?? false) {
									$imageString = file_get_contents($newName);
									$imageMimeType = ze\file::mimeType($newName);
									$imageSize = getimagesize($newName);
									$imageWidth = $cropWidth = $imageSize[0];
									$imageHeight = $cropHeight = $imageSize[1];
									$widthLimit = $newWidth = $cropNewWidth = 64;
									$heightLimit = $newHeight = $cropNewHeight = 64;
									
									$mode = 'resize';
									ze\file::resizeImageByMode(
										$mode, $imageWidth, $imageHeight,
										$widthLimit, $heightLimit,
										$newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight,
										$imageMimeType
									);
									
									ze\file::resizeImageStringToSize($imageString, $imageMimeType, $imageWidth, $imageHeight, $newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight);
									
									$privateCacheDir = ze\cache::createRandomDir(15, 'private/images');
									$thumbnailPath = $privateCacheDir . 'thumbnail-' . $file['name'][$j];
									file_put_contents(CMS_ROOT . $thumbnailPath, $imageString);
									\ze\cache::chmod(CMS_ROOT . $thumbnailPath, 0666);
									
									$cacheFile['thumbnail_path'] = $thumbnailPath;
								}
								
								$data['files'][] = $cacheFile;
								\ze\cache::chmod(CMS_ROOT. $newName, 0666);
							}
						}
					}
				}
			}
			echo json_encode($data);
		} elseif (isset($_GET['combineFiles'])) {
			$data = [];
			if ($filesJSON = $_POST['files'] ?? false) {
				$files = json_decode($filesJSON, true);
				
				//To do..
				//programPathForExec?
				
				//Step 1: convert all images into pdfs
				$pdfs = [];
				foreach ($files as $file) {
					//Validation, only accept jpg, png, gif
					$mimeType = ze\file::mimeType($file['path']);
					if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'])) {
						//Rotate images
						if (!empty($file['rotate'])) {
							$imageString = file_get_contents($file['path']);
							$imageResource = imagecreatefromstring($imageString);
							$newImageResource = imagerotate($imageResource, 360 - $file['rotate'], 0);
							
							if ($newImageResource) {
							$rotatedImagePath = $file['path'];
								if ($mimeType == 'image/jpeg') {
									imagejpeg($newImageResource, $rotatedImagePath);
								} elseif ($mimeType == 'image/png') {
									imagepng($newImageResource, $rotatedImagePath);
								} elseif ($mimeType == 'image/gif') {
									imagegif($newImageResource, $rotatedImagePath);
								}
							}
						}
						
						$nameWithoutExtension = substr($file['name'], 0, strrpos($file['name'], '.'));
						$pathWithoutFile = substr($file['path'], 0, strrpos($file['path'], '/') + 1);
						$pdfPath = $pathWithoutFile . $nameWithoutExtension . '.pdf';
						exec('convert ' . escapeshellarg($file['path']) . ' ' . escapeshellarg($pdfPath));
					
						$pdfs[] = escapeshellarg($pdfPath);
					}
				}
				
				//Step 2: combine all pdfs into a single file
				$rawUserFullPDFFileName = (($_POST['name'] ?? false) ? ($_POST['name'] ?? false) : 'my-combined-file');
				$fullPDFName = preg_replace('/[^\w-\.]/', '_', $rawUserFullPDFFileName);
				
				if (substr($fullPDFName, -4) != '.pdf') {
					$fullPDFName .= '.pdf';
				}
				
				$fullPDFDir = ze\cache::createRandomDir(30, 'uploads');
				if (!$fullPDFDir) {
					exit('Could not create cache directory in private/uploads');
				}
				
				//Embed a PDFmarks file to set meta data
				$pdfMarksPath = $fullPDFDir . 'pdfmarks';
				file_put_contents($pdfMarksPath, '[ /Title (' . substr($fullPDFName, 0, -4) . ') /DOCINFO pdfmark');
				$pdfs[] = $pdfMarksPath;
				
				$fullPDFPath =  $fullPDFDir . $fullPDFName;
				exec('gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=' . escapeshellarg($fullPDFPath) . ' ' . implode(' ', $pdfs));
				
				$data['path'] = $fullPDFPath;
				$data['name'] = $fullPDFName;
			}
			echo json_encode($data);
			
		}
	}
	
	
	
	
	
	public static function getForm($formId) {
		return ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', true, $formId);
	}
	public static function getFormName($formId) {
		return ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', 'name', $formId);
	}
	
	public static function getFormPages($formId) {
		$pages = [];
		$result = ze\row::query(ZENARIO_USER_FORMS_PREFIX . 'pages', true, ['form_id' => $formId], 'ord');
		$ord = 0;
		while ($page = ze\sql::fetchAssoc($result)) {
			$page['fields'] = [];
			$page['ord'] = ++$ord;
			$pages[$page['id']] = $page;
		}
		return $pages;
	}
	
	private function isPageHidden($page) {
		return $this->isFieldHidden($page);
	}
	
	private function isFieldHidden($field, $ignoreRepeat = false) {
		return static::isFieldHiddenStatic($field, $this->fields, $ignoreRepeat);
	}
	
	public static function isPageHiddenStatic($page, $fields) {
		return static::isFieldHiddenStatic($page, $fields);
	}
	
	public static function isFieldHiddenStatic($field, $fields, $ignoreRepeat = false) {
		//If the field is in a hidden repeat block, it is also hidden
		if (!empty($field['repeat_start_id']) && isset($fields[$field['repeat_start_id']]) && !$ignoreRepeat) {
			if (static::isFieldHiddenStatic($fields[$field['repeat_start_id']], $fields, true)) {
				return true;
			}
		}
		
		$visible = false;
		if ($field['visibility'] == 'visible') {
			return false;
		} elseif ($field['visibility'] == 'hidden') {
			return true;
		} elseif ($field['visibility'] == 'visible_on_condition'
			&& !empty($field['visible_condition_field_id'])
			&& isset($fields[$field['visible_condition_field_id']])
		) {
			$conditionList = static::getConditionList($field, $fields);
			foreach ($conditionList as $condition) {
				switch ($condition['type']) {
					case 'checkbox':
					case 'group':
						$visible = ($condition['value'] == $condition['current_value']);
						break;
					case 'radios':
					case 'select':
					case 'centralised_radios':
					case 'centralised_select':
						$conditionValues = explode(',', $condition['value']);
						if (count($conditionValues) > 1) {
							$visible = in_array($condition['current_value'], $conditionValues);
						} else {
							$visible = ($condition['value'] && ($condition['value'] == $condition['current_value'])) || (!$condition['value'] && $condition['current_value']);
						}
						break;
					case 'checkboxes':
						$visible = false;
						if (!$condition['current_value']) {
							$condition['current_value'] = [];
						}
						$values = $condition['value'] ? explode(',', $condition['value']) : [];
						if ($condition['operator'] == 'AND') {
							$sharedValues = array_intersect($condition['current_value'], $values);
							$selectedRequiredValues = array_intersect($condition['current_value'], $sharedValues);
				
							$visible = ($selectedRequiredValues == $values);
						} else {
							foreach ($condition['current_value'] as $fieldValue) {
								if (in_array($fieldValue, $values)) {
									$visible = true;
									break;
								}
							}
						}
						break;
				}
				if ($condition['invert'] == 1) {
					$visible = !$visible;
				} 
				
				if (!$visible) {
					return true;
				}
			}
			return false;
		}
		return !$visible;
	}
	
	public static function getConditionList($field, $fields) {
		$conditionList = [];
		$fieldsAdded = [];
		$initialField = $field;
		while (!empty($field['visible_condition_field_id'])) {
			$conditionFieldId = $field['visible_condition_field_id'];
			if ($conditionFieldId) {
				//Make sure we don't get into an infinite loop if the condition list loops forever
				if (isset($fieldsAdded[$conditionFieldId]) || ($conditionFieldId == $initialField['id']) || count($conditionList) > 500) {
					break;
				}
				$conditionField = $fields[$conditionFieldId];
				$condition = [
					'id' => $conditionFieldId, 
					'value' => $field['visible_condition_field_value'], 
					'invert' => (int)$field['visible_condition_invert'],
					'type' => $conditionField['type'],
					'current_value' => $conditionField['value'] ?? false
				];
				if ($conditionField['type'] == 'checkboxes') {
					$condition['operator'] = $field['visible_condition_checkboxes_operator'];
				}
				$conditionList[] = $condition;
				$fieldsAdded[$conditionFieldId] = true;
				$field = $conditionField;
			}
		}
		$conditionList = array_reverse($conditionList);
		return $conditionList;
	}
	
	
	
	public static function deleteOldPartialResponse($formId = false, $userId = false, $getRequestValue = false) {
		if ($formId || $userId) {
			$keys = [];
			if ($formId) {
				$keys['form_id'] = $formId;
			}
			if ($userId) {
				$keys['user_id'] = $userId;
			}
			if ($getRequestValue) {
				$keys['get_request_value'] = $getRequestValue;
			}
			$result = ze\row::query(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response', ['id'], $keys);
			while ($response = ze\sql::fetchAssoc($result)) {
				ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response', ['id' => $response['id']]);
				ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response_data', ['user_partial_response_id' => $response['id']]);
			}
		} else {
			ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response', []);
			ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response_data', []);
		}
	}
	//Get visible form field data in email
	private function sendVisibleFieldsFormEmail($startLine, $email, $mergeFields = [],$responseId, $attachments = [], 		$replyToEmail = false, $replyToName = false, $adminDownloadLinks = false) {
		$formName = $this->form['name'] ? trim($this->form['name']) : '[blank name]';
		$formId = $this->form['id'];
		//var_dump($this->form['id']);
		$subject = 'New form submission for: ' . $formName;
		$addressFrom = ze::setting('email_address_from');
		$nameFrom = ze::setting('email_name_from');
		$url = ze\link::toItem(ze::$cID, ze::$cType, true, '', false, false, true);
		if (!$url) {
			$url = ze\link::absolute();
		}
		
		
			$body = $startLine;
		
			$body .=$this->getFormSummaryHTML($responseId);
		
		$body .= '<p>This is an auto-generated email from ' . htmlspecialchars($url) . '</p>';
		zenario_email_template_manager::putBodyInTemplate($body);
		zenario_email_template_manager::sendEmails($email, $subject, $addressFrom, $nameFrom, $body, [], $attachments, [], 0, false, $replyToEmail, $replyToName);
	}
	
	public static function getFormSummaryHTML($responseId, $formId = false, $data = false, $repeatRows = []) {
		$html = '<table>';
		
		//Get form if loading from a responseId
		if ($responseId) {
			$response = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_response', ['form_id'], $responseId);
			$formId = $response['form_id'];
		}
		
		//Get pages on form
		$pages = static::getFormPages($formId);
		$currentPageId = false;
		$pageIsHidden = false;
		
		//Get fields and either load data from a response or use passed data
		$fields = static::getFormFieldsStatic($formId, $repeatRows, $loadFromResponseId = $responseId);
		if ($data) {
			foreach ($data as $fieldId => $value) {
				$fields[$fieldId]['value'] = $value;
			}
		}
		
		foreach ($fields as $fieldId => $field) {
			//Do not display these field types
			if ($field['type'] == 'repeat_start' 
				|| $field['type'] == 'repeat_end' 
				|| ($field['type'] == 'section_description' && !$field['show_in_summary'])
			) {
				continue;
			}
			
			//Show page as header if visible
			if (count($pages) > 1 && (!$currentPageId || $currentPageId != $field['page_id'])) {
				$currentPageId = $field['page_id'];
				$page = $pages[$field['page_id']];
				$pageIsHidden = static::isPageHiddenStatic($page, $fields);
				if (!$pageIsHidden && $page['show_in_summary']) {
					$html .= '<tr><th colspan="2" class="header">' . htmlspecialchars($pages[$currentPageId]['name']) . '</th></tr>';
				}
			}
			
			//Show field if not hidden
			if (!$pageIsHidden && !static::isFieldHiddenStatic($field, $fields)) {
				$label = $field['label'] ? $field['label'] : $field['name'];
				if ($field['type'] == 'section_description') {
					$html .= '<tr><th colspan="2" class="subheader">' . htmlspecialchars($label) . '</th></tr>';
					if ($field['description']) {
						$html .= '<tr><td colspan="2" class="subheader_description">' . $field['description'] . '</td></tr>';
					}
				} else {
					$displayHTML = '';
					if (isset($field['value'])) {
						$displayHTML = static::getFieldDisplayValue($field, $field['value'], true, $includeDownloadLinks = 'admin');
					}
					if (isset($field['row']) && !empty($field['firstRepeatBlockField'])) {
						$rows = count($fields[$field['repeat_start_id']]['rows']);
						$html .= '<tr><th colspan="2" class="subheader">Repeating section ' . (int)$field['row'] . ' of ' . (int)$rows . '</th></tr>';
					}
					if($displayHTML)
						$html .= '<tr><td>' . htmlspecialchars($label) . '</td><td>' . $displayHTML . '</td></tr>';
					else
						$html .= '<tr><td>' . htmlspecialchars($label) . '</td><td>[Empty]</td></tr>';
						
				}
			}
		}
		$html .= '</table>';
		return $html;
	}
	
	public static function deleteFormResponse($responseId) {
		ze\module::sendSignal('eventFormResponseDeleted', [$responseId]);
		ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', ['user_response_id' => $responseId]);
		ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'user_response', $responseId);
	}
	
	public static function deletePredefinedTextTarget($fieldId) {
		ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'predefined_text_targets', $fieldId);
		static::deletePredefinedTextTrigger(false, $fieldId);
	}
	
	public static function deletePredefinedTextTrigger($triggerId, $targetFieldId = false) {
		if ($targetFieldId) {
			ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'predefined_text_triggers', ['target_form_field_id' => $targetFieldId]);
		} else {
			$targetFieldId = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'predefined_text_triggers', 'target_form_field_id', $triggerId);
			ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'predefined_text_triggers', $triggerId);
		
			//Tidy up ordinals
			$ord = 0;
			foreach (ze\row::getValues(ZENARIO_USER_FORMS_PREFIX . 'predefined_text_triggers', 'id', ['target_form_field_id' => $targetFieldId], 'ord') as $id) {
				ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'predefined_text_triggers', ['ord' => ++$ord], $id);
			}
		}
	}
	
	public static function deleteFormField($fieldId, $updateOrdinals = true, $formExists = true) {
		$formField = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', true, $fieldId);
		
		//Send signal that the form field is now deleted (sent before actual delete in case modules need to look at any metadata or field values)
		ze\module::sendSignal('eventFormFieldDeleted', [$fieldId]);
		
		//Delete form field
		ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', $fieldId);
		
		//Update remaining field ordinals
		if ($updateOrdinals && !empty($formField)) {
			$result = ze\row::query(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', ['id'], ['user_form_id' => $formField['user_form_id']], 'ord');
			$ord = 0;
			while ($row = ze\sql::fetchAssoc($result)) {
				ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', ['ord' => ++$ord], $row['id']);
			}
		}
		
		//Delete any field values
		ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', ['form_field_id' => $fieldId]);
		
		//Delete any response data
		ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', ['form_field_id' => $fieldId]);
		
		//Delete predefined text target and trigger records (if exists)
		static::deletePredefinedTextTarget($fieldId);
		$result = ze\row::query(ZENARIO_USER_FORMS_PREFIX . 'predefined_text_triggers', ['id'], ['form_field_id' => $fieldId]);
		while ($row = ze\sql::fetchAssoc($result)) {
			static::deletePredefinedTextTrigger($row['id']);
		}
		
		return true;
	}
	
	public static function deleteFormPage($pageId) {
		ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'pages', $pageId);
	}
	
	public static function deleteForm($formId) {
		//Send signal that the form is now deleted (sent before actual delete in case modules need to look at any metadata or form fields)
		ze\module::sendSignal('eventFormDeleted', [$formId]);
		
		//Delete form
		ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $formId);
		
		//Delete form fields
		$result = ze\row::query(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', ['id'], ['user_form_id' => $formId]);
		while ($row = ze\sql::fetchAssoc($result)) {
			static::deleteFormField($row['id'], false, false);
		}
		
		//Delete form pages
		$result = ze\row::query(ZENARIO_USER_FORMS_PREFIX . 'pages', ['id'], ['form_id' => $formId]);
		while ($row = ze\sql::fetchAssoc($result)) {
			static::deleteFormPage($row['id']);
		}
		
		//Delete responses
		$result = ze\row::query(ZENARIO_USER_FORMS_PREFIX . 'user_response', ['id'], ['form_id' => $formId]);
		while ($row = ze\sql::fetchAssoc($result)) {
			static::deleteFormResponse($row['id']);
		}
		return true;
	}
	
	public static function deleteFormFieldValue($valueId) {
		ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', ['id' => $valueId]);
		ze\module::sendSignal('eventFormFieldValueDeleted', [$valueId]);
	}
	
	private function getFormRepeatRows($formId) {
		$repeatRows = [];
		$sql = '
			SELECT f.id, f.min_rows, f.max_rows, d.id AS dataset_field_id, IFNULL(f.field_type, d.type) AS type
			FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields f
			LEFT JOIN ' . DB_PREFIX . 'custom_dataset_fields d
				ON f.user_field_id = d.id
			WHERE f.user_form_id = ' . (int)$formId . '
			AND (f.field_type = "repeat_start" OR d.type = "repeat_start")';
		$result = ze\sql::select($sql);
		while ($field = ze\sql::fetchAssoc($result)) {
			$repeatRows[$field['id']] = $this->loadRepeatRows($field);
		}
		return $repeatRows;
	}
	
	public static function getFormRepeatRowsFromSource($responseId = false, $partialResponseId = false) {
		$repeatRows = [];
		if ($responseId || $partialResponseId) {
			if ($responseId) {
				$sql = '
					SELECT d.form_field_id, d.field_row, d.value, f.field_type, c.type
					FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response_data d
					INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields f
						ON d.form_field_id = f.id
					LEFT JOIN ' . DB_PREFIX . 'custom_dataset_fields c
						ON f.user_field_id = c.id
					WHERE d.user_response_id = ' . (int)$responseId;
			} elseif ($partialResponseId) {
				$sql = '
					SELECT d.form_field_id, d.field_row, d.value, f.field_type, c.type
					FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_partial_response_data d
					INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields f
						ON d.form_field_id = f.id
					LEFT JOIN ' . DB_PREFIX . 'custom_dataset_fields c
						ON f.user_field_id = c.id
					WHERE d.user_partial_response_id = ' . (int)$partialResponseId;
			}
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
				$fieldId = static::getRepeatFieldId($row['form_field_id'], $row['field_row']);
				//Load row counts for repeat start fields
				if ($row['field_type'] == 'repeat_start' || $row['type'] == 'repeat_start') {
					$rows = [];
					for ($i = 1; $i <= $row['value']; $i++) {
						$rows[] = $i;
					}
					$repeatRows[$fieldId] = $rows;
				}
			}
		}
		
		return $repeatRows;
	}
	
	public static function loadFormFieldValuesFromSource(&$fields, $responseId = false, $partialResponseId = false) {
		$data = [];
		if ($responseId) {
			$result = ze\row::query(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', ['form_field_id', 'field_row', 'value'], ['user_response_id' => $responseId]);
			while ($row = ze\sql::fetchAssoc($result)) {
				$fieldId = static::getRepeatFieldId($row['form_field_id'], $row['field_row']);
				if (isset($fields[$fieldId])) {
					$fields[$fieldId]['value'] = static::getFieldValueFromStored($fields[$fieldId], $row['value']);
				}	
			}
		} elseif ($partialResponseId) {
			$result = ze\row::query(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response_data', ['form_field_id', 'field_row', 'value'], ['user_partial_response_id' => $partialResponseId]);
			while ($row = ze\sql::fetchAssoc($result)) {
				$fieldId = static::getRepeatFieldId($row['form_field_id'], $row['field_row']);
				if (isset($fields[$fieldId])) {
					$fields[$fieldId]['value'] = static::getFieldValueFromStored($fields[$fieldId], $row['value']);
				}
			}
		}
	}
	
	private function getFormFields($formId) {
		$repeatRows = $this->getFormRepeatRows($formId);
		return static::getFormFieldsStatic($formId, $repeatRows);
	}
	
	public static function getFormFieldsStatic($formId, $repeatRows = [], $loadFromResponseId = false, $loadFromPartialResponseId = false, $fieldId = false, $codeName = false) {
		//Load repeat block rows from an external source if asked
		if ($loadFromResponseId) {
			$repeatRows = static::getFormRepeatRowsFromSource($loadFromResponseId);
		} elseif ($loadFromPartialResponseId) {
			$repeatRows = static::getFormRepeatRowsFromSource(false, $loadFromPartialResponseId);
		}
		
		$fields = [];
		$sql = '
			SELECT 
				uff.id, 
				uff.user_form_id,
				uff.page_id,
				uff.ord, 
				uff.is_readonly, 
				uff.is_required,
				uff.mandatory_if_visible,
				uff.mandatory_condition_field_id,
				uff.mandatory_condition_invert,
				uff.mandatory_condition_checkboxes_operator,
				uff.mandatory_condition_field_value,
				uff.visibility,
				uff.visible_condition_field_id,
				uff.visible_condition_invert,
				uff.visible_condition_checkboxes_operator,
				uff.visible_condition_field_value,
				uff.label,
				uff.name,
				uff.custom_code_name,
				uff.placeholder,
				uff.preload_dataset_field_user_data,
				uff.split_first_name_last_name,
				uff.default_value,
				uff.default_value_class_name,
				uff.default_value_method_name,
				uff.default_value_param_1,
				uff.default_value_param_2,
				uff.note_to_user,
				uff.css_classes,
				uff.div_wrap_class,
				uff.required_error_message,
				uff.validation AS field_validation,
				uff.validation_error_message AS field_validation_error_message,
				uff.field_type,
				uff.description,
				uff.calculation_code,
				uff.value_prefix,
				uff.value_postfix,
				uff.restatement_field,
				uff.values_source,
				uff.values_source_filter,
				uff.filter_placeholder,
				uff.suggested_values,
				uff.force_suggested_values,
				uff.value_field_columns,
				uff.min_rows,
				uff.max_rows,
				uff.add_row_label,
				uff.show_month_year_selectors,
				uff.no_past_dates,
				uff.no_future_dates,
				uff.disable_manual_input,
				uff.invalid_field_value_error_message,
				uff.word_count_max,
				uff.word_count_min,
				uff.rows,
				uff.combined_filename,
				uff.stop_user_editing_filename,
				uff.show_in_summary,
				uff.filter_on_field,
				uff.repeat_start_id,
				uff.invert_dataset_result,
				uff.allow_converting_multiple_images_to_pdf,
				cdf.id AS dataset_field_id, 
				cdf.type, 
				cdf.db_column, 
				cdf.label AS dataset_field_label,
				cdf.default_label,
				cdf.is_system_field, 
				cdf.dataset_id, 
				cdf.validation AS dataset_field_validation, 
				cdf.validation_message AS dataset_field_validation_message,
				cdf.multiple_select,
				cdf.store_file,
				cdf.extensions,
				cdf.values_source AS dataset_values_source,
				cdf.min_rows AS dataset_min_rows,
				cdf.max_rows AS dataset_max_rows,
				cdf.repeat_start_id AS dataset_repeat_start_id,
				ptt.form_field_id AS predefined_text_target_id,
				ptt.button_label AS predefined_text_button_label
			FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms AS uf
			INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields AS uff
				ON uf.id = uff.user_form_id
			INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'pages p
				ON uff.page_id = p.id
			LEFT JOIN ' . DB_PREFIX . 'custom_dataset_fields AS cdf
				ON uff.user_field_id = cdf.id
			LEFT JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'predefined_text_targets ptt
				ON uff.id = ptt.form_field_id
			WHERE TRUE';
		if ($formId) {
			$sql .= '
				AND uff.user_form_id = ' . (int)$formId;
			if ($codeName) {
				$sql .= '
					AND uff.custom_code_name = "' . ze\escape::sql($codeName) . '"';
			}
		} elseif ($fieldId) {
			$sql .= '
				AND uff.id = ' . (int)$fieldId;
		}
		$sql .= '
			ORDER BY p.ord, uff.ord';
		$result = ze\sql::select($sql);
		$repeatStartField = false;
		$repeatBlockFields = [];
		while ($field = ze\sql::fetchAssoc($result)) {
			if ($field['field_type']) {
				$field['type'] = $field['field_type'];
			}
			if ($field['type'] == 'consent') {
				$field['type'] = 'checkbox';
				$field['is_consent'] = true;
			}
			
			if ($field['type'] == 'repeat_start') {
				$repeatBlockFields = [];
				$field['rows'] = $repeatRows[$field['id']] ?? [1];
				if ($field['dataset_field_id']) {
					$field['min_rows'] = $field['dataset_min_rows'];
					$field['max_rows'] = $field['dataset_max_rows'];
				}
				$repeatStartField = $field;
			} elseif ($field['type'] == 'repeat_end') {
				//Add repeat fields
				
				$firstRepeatBlockField = reset($repeatBlockFields);
				$lastRepeatBlockField = end($repeatBlockFields);
				
				if (!$firstRepeatBlockField) {
					continue;
				}
				
				foreach ($repeatStartField['rows'] as $row) {
					foreach ($repeatBlockFields as $rFieldId => $rField) {
						$rFieldNewId = static::getRepeatFieldId($rFieldId, $row);
						$rField['row'] = $row;
						if ($rFieldId == $firstRepeatBlockField['id']) {
							$rField['firstRepeatBlockField'] = true;
						}
						if ($rFieldId == $lastRepeatBlockField['id']) {
							$rField['lastRepeatBlockField'] = true;
							if ($row > $repeatStartField['min_rows']) {
								$rField['repeatBlockDeleteButton'] = true;
							}
						}
						
						//If stored field Ids are in the same repeat block, use the repeated field in the same block rather than the original field.
						$storedFieldIdNames = ['visible_condition_field_id', 'mandatory_condition_field_id', 'restatement_field', 'filter_on_field'];
						foreach ($storedFieldIdNames as $name) {
							if ($rField[$name] && isset($repeatBlockFields[$rField[$name]])) {
								$rField[$name] = static::getRepeatFieldId($rField[$name], $row);
							}
						}
						
						$fields[$rFieldNewId] = $rField;
					}
				}
			//Copy how unlinked repeats work for dataset repeats
			} elseif ($field['dataset_repeat_start_id']) {
				$field['repeat_start_id'] = $repeatStartField['id'];
				$repeatBlockFields[$field['id']] = $field;
			} elseif ($field['repeat_start_id']) {
				$repeatBlockFields[$field['id']] = $field;
			}
			$fields[$field['id']] = $field;
		}
		
		if ($loadFromResponseId) {
			static::loadFormFieldValuesFromSource($fields, $loadFromResponseId);
		} elseif ($loadFromPartialResponseId) {
			static::loadFormFieldValuesFromSource($fields, false, $loadFromPartialResponseId);
		}
		
		if ($fieldId || $codeName) {
			return reset($fields);
		}
		
		return $fields;
	}
	
	public static function getRepeatFieldId($fieldId, $row) {
		if ($row <= 1) {
			return $fieldId;
		}
		return $fieldId . '_' . $row;
	}
	
	private function getFormHTML($pageId) {
		$t = $this->form['translate_text'];
		$html = '';
		$html .= '<div id="' . htmlspecialchars($this->containerId) . '_form_wrapper" class="form_wrapper';
		if ($this->inFullScreen) {
			$html .= ' in_fullscreen';
		}
		$html .= '">';
		$html .= $this->getFormTitle();
		
		
		//Buttons at top of form
		$topButtonsHTML = '';
		if ($this->displayMode == 'inline_in_page') {
			$topButton = $this->getCustomTopButtons();
			if ($topButton) {
				$topButtonsHTML .= $topButton;
			}
			
			if ($this->setting('partial_completion_button_position') == 'top') {
				$topButtonsHTML .= $this->getPartialSaveButtonHTML();
			}
			
			//Print page
			if ($this->setting('show_print_page_button')) {
				$printButtonPages = $this->setting('print_page_button_pages');
				if ($printButtonPages) {
					$printButtonPages = explode(',', $printButtonPages);
					if (in_array($this->pages[$pageId]['ord'], $printButtonPages)) {
						$topButtonsHTML .= '<div id="' . htmlspecialchars($this->containerId) . '_print_page" class="print_page">' . htmlspecialchars(static::fPhrase('Print', [], $t)) . '</div>';
						
					}
				}
			}
			//Fullscreen
			if ($this->setting('show_fullscreen_button')) {
				$topButtonsHTML .= '<div id="' . htmlspecialchars($this->containerId) . '_fullscreen" class="fullscreen_button"';
				if ($this->inFullScreen) {
					$topButtonsHTML .= ' style="display:none;"';
				}
				$topButtonsHTML .= '>' . htmlspecialchars(static::fPhrase('Fullscreen', [], $t)) . '</div>';
			}
		}
		if ($topButtonsHTML) {
			$html .= '<div class="top_buttons">' . $topButtonsHTML . '</div>';
		}
		
		//Page switcher
		if ($this->form['show_page_switcher']) {
			$switcherHTML = '';
			$hasPageVisibleOnSwitcher = false;
			$switcherHTML .= '<div class="page_switcher"><ul class="progress_bar">';
			$page = $this->pages[$pageId];
			
			$maxPageReached = $_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data']['max_page_reached'] ?? $pageId;
			
			$step = 1;
			foreach ($this->pages as $tPageId => $tPage) {
				if ($tPage['hide_in_page_switcher']) {
					continue;
				}
				
				$nextVisiblePage = false;
				$previousVisiblePage = false;
				$passed = false;
				foreach ($this->pages as $t2PageId => $t2Page) {
					if ($t2Page['hide_in_page_switcher']) {
						continue;
					}
					if ($t2PageId == $tPageId) {
						$passed = true;
					} elseif ($passed && !$nextVisiblePage && !$t2Page['hidden']) {
						$nextVisiblePage = $t2Page;
					} elseif (!$passed && !$t2Page['hidden']) {
						$previousVisiblePage = $t2Page;
					}
				}
				
				$hasPageVisibleOnSwitcher = true;
				$switcherHTML .= '<li data-page="' . htmlspecialchars($tPageId) . '" ';
				if ($tPage['hidden']) {
					$switcherHTML .= ' style="display:none;"';
				}
				$extraClasses = '';
				$isCurrent = false;
				
				//Current if on the section or the current page is between this one and the next one
				if ($pageId == $tPageId
					|| ($nextVisiblePage && ($page['ord'] < $nextVisiblePage['ord']) && ($page['ord'] > $tPage['ord']))
					|| (!$nextVisiblePage && ($page['ord'] > $tPage['ord']))
					|| (!$previousVisiblePage && ($page['ord'] < $tPage['ord']))
				) {
					$isCurrent = true;
					$extraClasses .= ' current';
				}
				
				//Complete if we are on a further on section
				if ($page['ord'] > $tPage['ord'] && $nextVisiblePage && ($nextVisiblePage['ord'] <= $page['ord'])) {
					$extraClasses .= ' complete';
				}
				
				//Available if we are not on this section and its less than the max page we reached
				if (!$isCurrent && $this->pages[$maxPageReached]['ord'] >= $tPage['ord']) {
					$extraClasses .= ' available';
				}
				
				if ($tPage['visibility'] == 'visible_on_condition') {
					$switcherHTML .= $this->getVisibleConditionDataValuesHTML($tPage, $pageId);
					$extraClasses .= ' visible_on_condition';
				}
				$switcherHTML .= 'class="step step_' . (int)($step++) . ' ' . htmlspecialchars($extraClasses) . '">' . htmlspecialchars($tPage['name']) . '</li>';
			}
			$switcherHTML .= '</ul></div>';
			if ($hasPageVisibleOnSwitcher) {
				$html .= $switcherHTML;
			}
		}
		
		$html .= '<div id="' . htmlspecialchars($this->containerId) . '_user_form" class="user_form">';
		$html .= $this->openForm(
			$onSubmit = '', 
			$extraAttributes = 'enctype="multipart/form-data"', 
			$action = ze\link::toItem(ze::$cID, ze::$cType, false, ['formPageHash' => $this->formPageHash], ze::$alias, true), 
			$scrollToTopOfSlot = true
		);
		
		//Hidden input for SIMPLE_ACCESS cookie rediection
		if ($this->form['simple_access_cookie_override_redirect'] && isset($_REQUEST['rci'])) {
			$html .= '<input type="hidden" name="rci" value="' . htmlspecialchars($_REQUEST['rci']) . '"/>';
		}
		if ($this->form['partial_completion_get_request']) {
			$html .= '<input type="hidden" name="' . htmlspecialchars($this->form['partial_completion_get_request']) . '" value="' . (int)($_REQUEST[$this->form['partial_completion_get_request']] ?? false) . '"/>';
		}
		$html .= '<input type="hidden" name="formPageHash" value="' . htmlspecialchars($this->formPageHash) . '"/>';
		//Hidden input to tell whether the form has been submitted
		$html .= '<input type="hidden" name="reloaded" value="1"/>';
		//Hidden input to tell whether the form is in fullscreen or not
		$html .= '<input type="hidden" name="inFullScreen" value="' . (int)$this->inFullScreen . '"/>';
		//Add any extra requests
		$extraRequests = $this->getCustomRequests();
		if ($extraRequests) {
			foreach ($extraRequests as $name => $value) {
				$html .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '"/>';
			}
		}
		
		//Global errors and messages
		if (isset($this->errors['global_top'])) {
			$html .= '<div class="form_error global top">' . htmlspecialchars(static::fPhrase($this->errors['global_top'], [], $t)) . '</div>';
		} elseif (isset($this->messages['global_top'])) {
			$html .= '<div class="success global top">' . htmlspecialchars(static::fPhrase($this->messages['global_top'], [], $t)) . '</div>';
		}
		
		$html .= $this->getFieldsHTML($pageId, $this->isFormReadonly($this->form));
		$html .= $this->closeForm();
		$html .= '</div>';
		$html .= $this->getCloseButtonHTML();
		$html .= '</div>';
		
		//Only show extranet links on the first page of a form
		if ($this->pages[$pageId]['ord'] == 1 && !$this->setting('hide_extranet_links')) {
			$html .= $this->getExtranetLinksHTML(['resend' => true, 'login' => true]);
		}
		return $html;
	}
	
	protected function getFieldsHTML($pageId, $readonly) {
		$html = '';
		$t = $this->form['translate_text'];
		$isMultiPageForm = count($this->pages) > 1;
		if ($isMultiPageForm) {
			$html .= '<fieldset id="' . htmlspecialchars($this->containerId) . '_page_' . htmlspecialchars($pageId) . '" class="page_' . (int)$this->pages[$pageId]['ord'] . '">';
		}
		
		$onLastPage = ($pageId == end($this->pages)['id']);
		$button = $this->getCustomButtons($pageId, $onLastPage, 'top');
		if ($button) {
			$html .= $button;
		}
		$html .= '<div class="form_fields">';
		
		//Variables to handle wrapper divs
		$currentDivWrapClass = false;
		$wrapDivOpen = false;
		$repeatFieldCurrentDivWrapClass = false;
		$repeatFieldWrapDivOpen = false;
		
		if ($this->form['enable_summary_page'] && $onLastPage) {
			$html .= '<p>' . htmlspecialchars(static::fPhrase("You're nearly done, please check your details before submitting.", [], $t)) . '</p>';
			$data = [];
			foreach ($this->fields as $fieldId => $field) {
				$data[$fieldId] = $this->getFieldCurrentValue($fieldId);
			}
			$repeatRows = $this->getFormRepeatRows($this->form['id']);
			$html .= static::getFormSummaryHTML(false, $this->form['id'], $data, $repeatRows);
			
			if ($this->form['summary_page_lower_text']) {
				$html .= '<p>' . nl2br(static::fPhrase($this->form['summary_page_lower_text'], [], $t)) . '</p>';
			}
			
			if ($this->form['enable_summary_page_required_checkbox']) {
				if (isset($this->errors['summary_required_checkbox']) && !$this->form['show_errors_below_fields']) {
					$html .= '<div class="form_error">' . htmlspecialchars($this->errors['summary_required_checkbox']) . '</div>';
				}
				$html .= '
					<div class="form_field field_checkbox">
						<input id="' . htmlspecialchars($this->containerId) . '_summary_required_checkbox" type="checkbox" name="summary_required_checkbox">
						<label class="field_title" for="' . htmlspecialchars($this->containerId) . '_summary_required_checkbox">' . static::fPhrase($this->form['summary_page_required_checkbox_label'], [], $t) . '</label>
					</div>';
				if (isset($this->errors['summary_required_checkbox']) && $this->form['show_errors_below_fields']) {
					$html .= '<div class="form_error">' . $this->errors['summary_required_checkbox'] . '</div>';
				}
			}
		} else {
			foreach ($this->pages[$pageId]['fields'] as $i => $fieldId) {
				$field = $this->fields[$fieldId];
				if ($field['type'] == 'repeat_start') {
					$html .= $this->getWrapperDivHTML($field, $wrapDivOpen, $currentDivWrapClass);
					//Repeat start div
					$html .= '<div id="' . htmlspecialchars($this->containerId) . '_repeat_block_' . (int)$fieldId . '" data-id="' . (int)$fieldId . '" class="repeat_block repeat_block_' . (int)$fieldId;
					if ($field['css_classes']) {
						$html .= ' ' . htmlspecialchars($field['css_classes']);
					}
					if ($field['visibility'] == 'visible_on_condition') {
						$html .= ' visible_on_condition';
					}
					$html .= '"';
					if ($this->isFieldHidden($field)) {
						$html .= ' style="display:none;"';
					}
					if ($field['visibility'] == 'visible_on_condition') {
						$html .= $this->getVisibleConditionDataValuesHTML($field, $pageId);
					}
					$html .= '>';
					$html .= '<input type="hidden" name="' . htmlspecialchars(static::getFieldName($fieldId, $field['custom_code_name'])) . '" value="' . htmlspecialchars(implode(',', $field['rows'])) . '">';
				
					//Repeat start title
					if ($field['label']) {
						$html .= '<div class="field_title">' . htmlspecialchars(static::fPhrase($field['label'], [], $t)) . '</div>';
					}
				
					$html .= '<div class="repeat_rows">';
				
				} elseif ($field['type'] == 'repeat_end') {
					$repeatStartField = $this->fields[$field['repeat_start_id']];
					if (is_array($repeatStartField['rows']) && count($repeatStartField['rows']) < $repeatStartField['max_rows']) {
						$addRowLabel = $repeatStartField['add_row_label'] ? $repeatStartField['add_row_label'] : 'Add +';
						$html .= '<div class="repeat_block_buttons"><div class="add">' . htmlspecialchars(static::fPhrase($addRowLabel, [], $t)) . '</div></div>';
					}
					//End start and repeat_rows divs
					$html .= '</div></div>';
				} else {
					if (!empty($field['firstRepeatBlockField'])) {
						$html .= '<div class="repeat_row row_' . (int)$field['row'] . '"><div class="repeat_fields">';
					}
				
					//Seperate div wraps for fields in a repeat block
					if (!empty($field['repeat_start_id'])) {
						$fieldWrapDivOpen = &$repeatFieldWrapDivOpen;
						$fieldCurrentDivWrapClass = &$repeatFieldCurrentDivWrapClass;
					} else {
						$fieldWrapDivOpen = &$wrapDivOpen;
						$fieldCurrentDivWrapClass = &$currentDivWrapClass;
					}
				
					$html .= $this->getWrapperDivHTML($field, $fieldWrapDivOpen, $fieldCurrentDivWrapClass);
					$html .= $this->getFieldHTML($fieldId, $pageId, $readonly);
				
				
					if (!empty($field['lastRepeatBlockField'])) {
						if ($fieldWrapDivOpen) {
							$html .= $this->getWrapperDivHTML($field, $fieldWrapDivOpen, $fieldCurrentDivWrapClass, true);
						}
						$html .= '</div>';
						if (!empty($field['repeatBlockDeleteButton'])) {
							$html .= '<div class="delete" data-row="' . (int)$field['row'] . '">' . static::fPhrase('Delete', [], $t) . '</div>';
						}
						$html .= '</div>';
					}
				}
			}
		}
		
		//Close final wrapper div
		if ($wrapDivOpen) {
			$html .= $this->getWrapperDivHTML($field, $wrapDivOpen, $currentDivWrapClass, true);
		}
		
		//Captcha
		if ($onLastPage) {
			if ($this->enableCaptcha()) {
				$html .= $this->getCaptchaHTML();
			}
			if (!empty($this->form['use_honeypot'])) {
			    $html .= $this->getHoneypotHTML();
			}
		}
		
		$html .= '</div>';
		
		if (isset($this->errors['global_bottom'])) {
			$html .= '<div class="form_error global bottom">' . htmlspecialchars(static::fPhrase($this->errors['global_bottom'], [], $t)) . '</div>';
		}
		
		$html .= '<div class="form_buttons">';
		
		$button = $this->getCustomButtons($pageId, $onLastPage, 'first');
		if ($button) {
			$html .= $button;
		}
		
		//Previous page button
		if ($isMultiPageForm && $this->pages[$pageId]['ord'] > 1) {
			$html .= '<input type="button" value="' . htmlspecialchars(static::fPhrase($this->pages[$pageId]['previous_button_text'], [], $t)) . '" class="previous"/>';
		}
		
		$button = $this->getCustomButtons($pageId, $onLastPage, 'center');
		if ($button) {
			$html .= $button;
		}
		
		//Next page button
		if ($isMultiPageForm && !$onLastPage) {
			$html .= '<input type="button" value="' . htmlspecialchars(static::fPhrase($this->pages[$pageId]['next_button_text'], [], $t)) . '" class="next"/>';
		}
		//Final submit button
		if ($this->showSubmitButton() && $onLastPage) {
			$html .= '<input type="button" class="next submit" value="' . htmlspecialchars(static::fPhrase($this->form['submit_button_text'], [], $t)) . '"/>';
		}
		
		$button = $this->getCustomButtons($pageId, $onLastPage, 'last');
		if ($button) {
			$html .= $button;
		}
		
		if ($this->setting('partial_completion_button_position') == 'bottom' || !$this->setting('partial_completion_button_position')) {
			$html .= $this->getPartialSaveButtonHTML();
		}
		
		$html .= '</div>';
		
		if ($isMultiPageForm) {
			$html .= '<input type="hidden" name="current_page" value="' . htmlspecialchars($pageId) . '"/>';
			$html .= '</fieldset>';
		}
		return $html;
	}
	
	private function getPartialSaveButtonHTML() {
		$html = '';
		if ($this->form['allow_partial_completion'] && ($this->form['partial_completion_mode'] == 'button' || $this->form['partial_completion_mode'] == 'auto_and_button')) {
			$t = $this->form['translate_text'];
			$html .= '<div class="complete_later"><input type="button" class="saveLater" value="' . htmlspecialchars(static::fPhrase('Save and complete later', [], $t)) . '" data-message="' . htmlspecialchars(static::fPhrase('You are about to save this part-completed form, so that you can return to it later. Save now?', [], $t)) . '"/></div>';
		}
		return $html;
	}
	
	private function getWrapperDivHTML($field, &$wrapDivOpen, &$currentDivWrapClass, $end = false) {
		$html = '';
		if ($end) {
			$html .= '</div>';
			$wrapDivOpen = false;
			$currentDivWrapClass = false;
		} else {
			if ($wrapDivOpen && ($currentDivWrapClass != $field['div_wrap_class'])) {
				$wrapDivOpen = false;
				$html .= '</div>';
			}
			if (!$wrapDivOpen && $field['div_wrap_class']) {
				$html .= '<div class="' . htmlspecialchars($field['div_wrap_class']) . '">';
				$wrapDivOpen = true;
			}
			$currentDivWrapClass = $field['div_wrap_class'];
		}
		return $html;
	}
	
	public function addToPageHead() {
		if ($this->enableCaptcha() && $this->form['captcha_type'] == 'pictures') {
			$this->loadCaptcha2Lib();
		}
	}
	
	private function getHoneypotHTML() {
	    $t = $this->form['translate_text'];
	    $html = '<div class="form_field honeypot" style="display:none;">';
	    if ($this->form['honeypot_label']) {
	        $html .= '<div class="field_title">' . htmlspecialchars(static::fPhrase($this->form['honeypot_label'], [], $t)) . '</div>';
	    }
	    if (isset($this->errors['honeypot'])) {
	        $html .= '<div class="form_error">' . htmlspecialchars(static::fPhrase($this->errors['honeypot'], [], $t)) . '</div>';
	    }
	    $html .= '<input type="text" name="field_hp" value="' . htmlspecialchars($_POST['field_hp'] ?? '') . '" maxlength="100"/>';
	    $html .= '</div>';
	    return $html;
	}
	
	private function getFieldHTML($fieldId, $pageId, $readonly) {
		$t = $this->form['translate_text'];
		$field = $this->fields[$fieldId];
		$fieldName = static::getFieldName($fieldId, $field['custom_code_name']);
		$fieldElementId = $this->containerId . '__' . $fieldName;
		$value = $this->getFieldCurrentValue($fieldId);
		$readonly = $readonly || $field['is_readonly'];
		$hidden = $this->isFieldHidden($field, $ignoreRepeat = true);
		
		$html = '';
		$errorHTML = '';
		$extraClasses = '';
		if (isset($this->errors[$fieldId])) {
			$errorHTML = '<div class="form_error">' . htmlspecialchars($this->errors[$fieldId]) . '</div>';
		}
		if ($value) {
			$extraClasses .= ' has_value';
		}
		if ($field['is_required']) {
			$extraClasses .= ' mandatory';
		}
		
		//Label
		if ($field['type'] != 'group' && $field['type'] != 'checkbox') {
			$html .= '<div class="field_title">' . htmlspecialchars(static::fPhrase($field['label'], [], $t)) . '</div>';
			if (!$this->form['show_errors_below_fields']) {
				$html .= $errorHTML;
			}
		}
		
		switch ($field['type']) {
			case 'group':
			case 'checkbox':
				if (!$this->form['show_errors_below_fields']) {
					$html .= $errorHTML;
				}
				$html .= '<input type="checkbox"';
				if ($value) {
					$html .= ' checked="checked"';
				}
				if ($readonly) {
					$html .= ' disabled="disabled"';
				}
				if ($hidden) {
					$html .= ' autocomplete="hidden-field"';
				}
				$html .= ' name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($fieldElementId) . '"/>';
				$html .= '<label class="field_title" for="' . htmlspecialchars($fieldElementId) . '">' . htmlspecialchars(static::fPhrase($field['label'], [], $t)) . '</label>';
				break;
			
			case 'restatement':
			case 'calculated':
			case 'url':
			case 'text':
				if ($field['type'] == 'restatement') {
					$readonly = true;
					$extraClasses .= ' restatement'; 
				//Calculated fields are readonly text fields
				} elseif ($field['type'] == 'calculated') {
					$readonly = true;
					$extraClasses .= ' calculated'; 
					
					$calculationCodeJSON = $this->expandCalculatedFieldsInCalculationCode($field['calculation_code']);
					$calculationCode = json_decode($calculationCodeJSON, true);
					
					if ($calculationCode) {
						foreach ($calculationCode as $stepIndex => $step) {
							if ($step['type'] == 'field') {
								$inputFieldValue = $this->getFieldCurrentValue($calculationCode[$stepIndex]['value']);
								if (!static::validateNumericInput($inputFieldValue)) {
									$inputFieldValue = 'NaN';
								} else {
									$inputFieldValue = (float)$inputFieldValue;
								}
								$calculationCode[$stepIndex]['v'] = $inputFieldValue;
							}
						}
					}
					
					$html .= '<div id="' . htmlspecialchars($this->containerId) . '_field_' . htmlspecialchars($fieldId) . '_calculation_code" style="display:none;">';
					$html .= htmlspecialchars(json_encode($calculationCode));
					$html .= '</div>';
				}
				
				//Suggested value options for text fields
				$suggestedValuesHTML = '';
				$useTextFieldName = true;
				if (!$readonly) {
					
					if ($field['suggested_values']) {
						$fieldLOV = $this->getFieldCurrentLOV($fieldId);
						$lov = [];
						foreach ($fieldLOV as $listValueId => $listValue) {
							$lov[] = ['v' => $field['suggested_values'] == 'pre_defined' ? $listValueId : $listValue, 'l' => $listValue];
						}
						$suggestedValuesHTML .= '<div class="suggested_values_json" data-id="' . htmlspecialchars($fieldId) . '" style="display:none;" ';
						
						//Add data attribute for JS events if other fields need to update when this field changes
						if (ze\row::exists(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', ['filter_on_field' => $fieldId])) {
							$suggestedValuesHTML .= 'data-source_field="1" ';
						}
						
						//Add data attribute for JS event to update placeholder if no values in list after click
						if ($field['filter_on_field'] && $field['filter_placeholder']) {
							$suggestedValuesHTML .= 'data-filter_placeholder="' . htmlspecialchars(static::fPhrase($field['filter_placeholder'], [], $t)) . '" ';
						}
						
						if ($field['force_suggested_values']) {
							$suggestedValuesHTML .= 'data-force_suggested_values="1"';
						}
						
						$suggestedValuesHTML .= '>';
						$suggestedValuesHTML .= htmlspecialchars(json_encode($lov));
						$suggestedValuesHTML .= '</div>';
						
						if ($field['suggested_values'] == 'pre_defined') {
							$suggestedValuesHTML .= '<input type="hidden" name="' . htmlspecialchars($fieldName)  . '" ';
							
							if ($field['force_suggested_values']) {
								if (isset($fieldLOV[$value])) {
									$suggestedValuesHTML .= 'value="' . htmlspecialchars($value) . '" ';
									$value = $fieldLOV[$value];
								} else {
									$value = '';
								}
							} else {
								$suggestedValuesHTML .= 'value="' . htmlspecialchars($value) . '" ';
								$value = $fieldLOV[$value] ?? '';
							}
							
							$suggestedValuesHTML .= '/>';
							$useTextFieldName = false;
						}
						
						if ($field['force_suggested_values'] && empty($lov)) {
							$readonly = true;
						}
					}
				}
				
				//Set type to "email" if validation is for an email address
				$fieldInputType = 'text';
				if ($field['field_validation'] == 'email') {
					$fieldInputType = 'email';
				}
				$html .= '<input type="' . htmlspecialchars($fieldInputType) . '"';
				if ($readonly) {
					$html .= ' readonly ';
				}
				if ($hidden) {
					$html .= ' autocomplete="hidden-field" ';
				}
				if ($useTextFieldName) {
					$html .= ' name="' . htmlspecialchars($fieldName) . '"';
				}
				$html .= ' id="' . htmlspecialchars($fieldElementId) . '"';
				//Data vars to help caculated fields
				if ($field['repeat_start_id']) {
					$html .= ' data-repeated="1" data-repeated_row="' . htmlspecialchars($field['row']) . '" data-repeat_id="' . htmlspecialchars($field['repeat_start_id']) . '"';
				}
				if ($value !== false) {
					$html .= ' value="' . htmlspecialchars($value) . '"';
				}
				if ($field['placeholder'] !== '' && $field['placeholder'] !== null) {
					$html .= ' placeholder="' . htmlspecialchars(static::fPhrase($field['placeholder'], [], $t)) . '"';
				}
				//Set maxlength to 255, or shorter for system field special cases
				$maxlength = 250;
				switch ($field['db_column']) {
					case 'salutation':
						$maxlength = 25;
						break;
					case 'screen_name':
					case 'password':
						$maxlength = 50;
						break;
					case 'first_name':
					case 'last_name':
					case 'email':
						$maxlength = 100;
						break;
				}	
				$html .= ' maxlength="' . htmlspecialchars($maxlength) . '" />';
				$html .= $suggestedValuesHTML;
				break;
				
			case 'date':
				$html .= '<input type="text" class="jquery_form_datepicker" ';
				if ($readonly) {
					$html .= ' disabled ';
				}
				if ($hidden) {
					$html .= ' autocomplete="hidden-field" ';
				}
				if ($field['show_month_year_selectors']) {
					$html .= ' data-selectors="1"';
				}
				if ($field['no_past_dates']) {
					$html .= ' data-no_past_dates="1"';
				}
				if ($field['no_future_dates']) {
					$html .= ' data-no_future_dates="1"';
				}
				if ($field['disable_manual_input']) {
					$html .= ' readonly';
				}
				
				$html .= ' id="' . htmlspecialchars($fieldElementId) . '"/>';
				$html .= '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($fieldElementId) . '__0"';
				if ($value !== false) {
					$html .= ' value="' . htmlspecialchars($value) . '"';
				}
				$html .= '/>';
				$html .= '<input type="button" class="clear_date" value="x" id="' . htmlspecialchars($fieldElementId) . '__clear"/>';
				break;
				
			case 'textarea':
				if ($field['predefined_text_target_id'] && ze::setting('zenario_user_forms_enable_predefined_text')) {
					$html .= '<input type="button" class="set_predefined_text" value="' . htmlspecialchars($field['predefined_text_button_label']) . '" data-id="' . htmlspecialchars($fieldId) . '"/>';
				}
				if (!$field['rows']) $field['rows'] = 4;
				$html .= '<textarea rows="'.$field['rows'].'" cols="51"';
				if ($field['placeholder'] !== '' && $field['placeholder'] !== null) {
					$html .= ' placeholder="' . htmlspecialchars(static::fPhrase($field['placeholder'], [], $t)) . '"';
				}
				if ($readonly) {
					$html .= ' readonly ';
				}
				if ($hidden) {
					$html .= ' autocomplete="hidden-field" ';
				}
				$html .= ' name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($fieldElementId) . '">';
				if ($value !== false) {
					$html .= htmlspecialchars($value);
				}
				$html .= '</textarea>';
				break;
				
			case 'section_description':
				$descriptionHTML = static::fPhrase($field['description'], [], $t);
				//If no tags...
				if ($descriptionHTML == strip_tags($descriptionHTML)) {
					$descriptionHTML = nl2br('<p>' . $descriptionHTML . '</p>');
				}
				$html .= '<div class="description">' . $descriptionHTML . '</div>';
				break;
				
			case 'radios':
				$fieldLOV = $this->getFieldCurrentLOV($fieldId);
				
				$cols = (int)$field['value_field_columns'];
				$html .= '<div class="values_wrap';
				if ($cols > 1) {
					$items = count($fieldLOV);
					$rows = ceil($items/$cols);
					$currentRow = $currentCol = 1;
					$html .= ' columns_' . (int)$cols;
					$keys = array_keys($fieldLOV);
					$lastValue = end($keys);
				}
				$html .= '">';
				
				foreach ($fieldLOV as $valueId => $label) {
					$radioElementId = $fieldElementId . '_' . $valueId;
					$valueHTML = '<div class="field_radio">';
					$valueHTML .= '<input type="radio"  value="' . htmlspecialchars($valueId) . '"';
					if ($valueId == $value) {
						$valueHTML .= ' checked="checked" ';
					}
					if ($readonly) {
						$valueHTML .= ' disabled ';
					}
					if ($hidden) {
						$valueHTML .= ' autocomplete="hidden-field" ';
					}
					$valueHTML .= ' name="'. htmlspecialchars($fieldName). '" id="' . htmlspecialchars($radioElementId) . '"/>';
					$valueHTML .= '<label for="' . htmlspecialchars($radioElementId) . '">' . htmlspecialchars(static::fPhrase($label, [], $t)) . '</label></div>'; 
					
					if (($cols > 1) && ($currentRow > $rows)) {
						$currentRow = 1;
						$currentCol++;
					}
					if (($cols > 1) && ($currentRow == 1)) {
						$html .= '<div class="col_' . (int)$currentCol . ' column">';
					}
					$html .= $valueHTML;
					if (($cols > 1) && (($currentRow++ == $rows) || ($lastValue == $valueId))) {
						$html .= '</div>';
					}
				}
				$html .= '</div>';
				
				if ($readonly && !empty($value)) {
					$html .= '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="'.htmlspecialchars($value).'" />';
				}
				break;
				
			case 'centralised_radios':
				$fieldLOV = $this->getFieldCurrentLOV($fieldId);
				$isCountryList = $this->isCentralisedListOfCountries($field);
				$radioCount = 0;
				foreach ($fieldLOV as $valueId => $label) {
					$valueId = (string)$valueId;
					$radioElementId = $fieldElementId . '_' . ++$radioCount;
					$html .= '<div class="field_radio">';
					$html .= '<input type="radio"  value="' . htmlspecialchars($valueId) . '"';
					if ($valueId === $value) {
						$html .= ' checked="checked" ';
					}
					if ($readonly) {
						$html .= ' disabled ';
					}
					if ($hidden) {
						$html .= ' autocomplete="hidden-field" ';
					}
					$html .= ' name="'. htmlspecialchars($fieldName). '" id="' . htmlspecialchars($radioElementId) . '"/>';
					$html .= '<label for="' . htmlspecialchars($radioElementId) . '">';
					//Make sure to use system country phrases if showing a list of countries
					if ($isCountryList && $t) {
						$html .= htmlspecialchars(ze\lang::phrase('_COUNTRY_NAME_' . $valueId, [], 'zenario_country_manager'));
					} else {
						$html .= htmlspecialchars(static::fPhrase($label, [], $t));
					}
					$html .= '</label>';
					$html .= '</div>'; 
				}
				if ($readonly && !empty($value)) {
					$html .= '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="'.htmlspecialchars($value).'" />';
				}
				break;
			
			case 'select':
				$fieldLOV = $this->getFieldCurrentLOV($fieldId);
				$html .= '<select ';
				if ($readonly) {
					$html .= 'disabled ';
				}
				if ($hidden) {
					$html .= ' autocomplete="hidden-field" ';
				}
				$html .= ' name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($fieldElementId) . '">';
				$html .= '<option value="">' . htmlspecialchars(static::fPhrase('-- Select --', [], $t)) . '</option>';
				foreach ($fieldLOV as $valueId => $label) {
					$html .= '<option value="' . htmlspecialchars($valueId) . '"';
					if ($valueId == $value) {
						$html .= ' selected="selected" ';
					}
					
					//If dataset fields are processed, an array with the label and an ordinal might be passed instead of a label string.
					//The code below will account for that.
					if (is_array($label)) {
						$html .= '>' . htmlspecialchars(static::fPhrase($label['label'], [], $t)) . '</option>';
					} else {
						$html .= '>' . htmlspecialchars(static::fPhrase($label, [], $t)) . '</option>';
					}
				}
				$html .= '</select>';
				if ($readonly) {
					$html .= '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($value) . '"/>';
				}
				break;
				
			case 'centralised_select':
				$fieldLOV = $this->getFieldCurrentLOV($fieldId);
				$isCountryList = $this->isCentralisedListOfCountries($field);
				$html .= '<select ';
				if ($readonly) {
					$html .= 'disabled ';
				}
				if ($hidden) {
					$html .= ' autocomplete="hidden-field" ';
				}
				$html .= ' name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($fieldElementId) . '"';
				//Add class for JS events if other fields need to update when this field changes
				if (ze\row::exists(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', ['filter_on_field' => $fieldId])) {
					$html .= ' class="source_field"';
				}
				$html .= '>';
				$html .= '<option value="">' . htmlspecialchars(static::fPhrase('-- Select --', [], $t)) . '</option>';
				foreach ($fieldLOV as $valueId => $label) {
					$valueId = (string)$valueId;
					$html .= '<option value="' . htmlspecialchars($valueId) . '"';
					if ($valueId === $value) {
						$html .= ' selected="selected" ';
					}
					$html .= '>';
					//Make sure to use system country phrases if showing a list of countries
					if ($isCountryList && $t) {
						$html .= htmlspecialchars(ze\lang::phrase('_COUNTRY_NAME_' . $valueId, [], 'zenario_country_manager'));
					} else {
						$html .= htmlspecialchars(static::fPhrase($label, [], $t));
					}
					$html .= '</option>';
				}
				$html .= '</select>';
				if ($readonly) {
					$html .= '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($value) . '"/>';
				}
				break;
			
			case 'checkboxes':
				$fieldLOV = $this->getFieldCurrentLOV($fieldId);
				
				$cols = (int)$field['value_field_columns'];
				$html .= '<div class="checkboxes_wrap';
				if ($cols > 1) {
					$items = count($fieldLOV);
					$rows = ceil($items/$cols);
					$currentRow = $currentCol = 1;
					$html .= ' columns_' . (int)$cols;
					$keys = array_keys($fieldLOV);
					$lastValue = end($keys);
				}
				$html .= '">';
				foreach ($fieldLOV as $valueId => $label) {
					$checkBoxHtml = '';
					$name = $fieldName . '_' . $valueId; 
					$checkboxElementId = $fieldElementId . '_' . $valueId;
					
					$selected = $value && in_array($valueId, $value);
					$checkBoxHtml .= '<div class="field_checkbox ' . ($selected ? 'checked' : '') . '"><input type="checkbox" data-value="' . htmlspecialchars($valueId) . '"';
					if ($selected) {
						$checkBoxHtml .= ' checked="checked"';
					}
					if ($readonly) {
						$checkBoxHtml .= ' disabled ';
					}
					if ($hidden) {
						$checkBoxHtml .= ' autocomplete="hidden-field" ';
					}
					$checkBoxHtml .= ' name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($checkboxElementId) . '"/>';
					
					if ($readonly && $selected) {
						$checkBoxHtml .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($selected) . '" />';
					}
					$checkBoxHtml .= '<label for="' . htmlspecialchars($checkboxElementId) . '">' . htmlspecialchars(static::fPhrase($label, [], $t)) . '</label></div>';
					
					
					if (($cols > 1) && ($currentRow > $rows)) {
						$currentRow = 1;
						$currentCol++;
					}
					if (($cols > 1) && ($currentRow == 1)) {
						$html .= '<div class="col_' . (int)$currentCol . ' column">';
					}
					$html .= $checkBoxHtml;
					if (($cols > 1) && (($currentRow++ == $rows) || ($lastValue == $valueId))) {
						$html .= '</div>';
					}
				}
				$html .= '</div>';
				break;
			
			case 'attachment':
				if ($value || $readonly) {
					$filename = basename($value);
					$html .= '<div class="field_data">' . htmlspecialchars($filename) . '</div>';
					$html .= '<input type="button" data-id="' . htmlspecialchars($fieldId) . '" value="' . htmlspecialchars(static::fPhrase('Remove', [], $t)) . '" class="remove_attachment">';
					$html .= '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($value) . '" />';
				} else {
					$html .= '<input type="file" name="' . htmlspecialchars($fieldName) . '"/>';
				}
				break;
			
			case 'file_picker':
				if ($readonly) {
					$json = json_encode($value, JSON_FORCE_OBJECT);
					$html .= '<div class="files">';
					$html .= '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($json) . '"/>';
					if ($value) {
						foreach ($value as $fileId => $file) {
							$html .= '<div class="file_row">';
							$html .= '<p><a href="' . htmlspecialchars($file['path']) . '" target="_blank">' . htmlspecialchars($file['name']) . '</a></p>';
							$html .= '</div>';
						}
					} else {
						$html .= htmlspecialchars(static::fPhrase('No file found', [], $t));
					}
					$html .= '</div>';
				} else {
					$json = json_encode($value, JSON_FORCE_OBJECT);
					$html .= '
						<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($json) . '"/>
						<div class="files"></div>
						<div class="progress" style="display:none;">
							<div class="progress_bar" style="background:green;height:5px;"></div>
						</div>
						<div class="file_upload_button"><span>' . htmlspecialchars(static::fPhrase('Upload file', [], $t)) . '</span>
							<input class="file_picker_field" type="file" name="file_upload[]" ' . htmlspecialchars($field['multiple_select'] ? 'multiple' : '') . '>
						</div>';
				}
				break;
			
			case 'document_upload':
				$previewHTML = '';
				
				if ($value) {
					$fileList = [];
					foreach ($value as $file) {
						$fileList[] = '<a href="' . htmlspecialchars($file['path']) . '" target="_blank">' . htmlspecialchars($file['name']) . '</a>';
					}
					$previewHTML = implode(', ', $fileList);
				}
				
				$fileNameReadonly = '';
				if ($field['combined_filename']) {
					$fileName = $field['combined_filename'];
					if ($field['stop_user_editing_filename']) {
						$fileNameReadonly = 'readonly';
					}
				} else {
					$fileName = static::fPhrase('my-combined-file', [], $t);
				}
				
				$json = json_encode($value, JSON_FORCE_OBJECT);
				
				$html .= '
					<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($json) . '"/>
					<div class="files_preview">' . $previewHTML . '</div>
					<input type="button" class="open_popup_1" value="' . htmlspecialchars(static::fPhrase('Select...', [], $t)) . '">
					<div class="overlay_1" style="display:none;">
						<div class="popup_1">
							<span class="close">Cancel</span>
							<div class="header">
								<h3>' . htmlspecialchars(static::fPhrase('Drag files into the box below', [], $t)) . '</h3>
							</div>
							<div class="content">
								<div class="files" style="min-height:200px;min-width:500px;border-style:dashed;">
									...
								</div>
								<div class="progress" style="display:none;">
									<div class="progress_bar" style="background:green;height:5px;"></div>
								</div>
							</div>
							<div class="footer">
								<div class="section_wrap">
									<label>' . htmlspecialchars(static::fPhrase('Or select files from your device:', [], $t)) . '</label>
									<div class="button">
										<input class="upload_complete_files" type="file" name="file_upload[]" multiple>
									</div>
								</div>';
				if ($field['allow_converting_multiple_images_to_pdf']) {
					$html .= '
								<div class="section_wrap">
									<label>' . htmlspecialchars(static::fPhrase('Upload multiple images as a single PDF', [], $t)) . '</label>
									<input type="button" class="open_popup_2" value="' . htmlspecialchars(static::fPhrase('Start...', [], $t)) . '">
								</div>';
				}
				
				$html .= '
								<div class="section_wrap save">
									<input type="button" class="save" value="' . htmlspecialchars(static::fPhrase('Save', [], $t)) . '">
								</div>
							</div>
						</div>
					</div>
					<div class="overlay_2" style="display:none;">
						<div class="popup_2">
							<span class="close">×</span>
							<div class="header">
								<h3>' . htmlspecialchars(static::fPhrase('PDF creator', [], $t)) . '</h3>
							</div>
							
							<p>' . htmlspecialchars(static::fPhrase('Click the button or drag to upload images. You\'re able to drag to re-order and rotate the images. When you\'re happy, click "combine" to make a PDF.', [], $t)) . '</p>
							
							<div class="content">
								<div class="files" style="min-height:200px;min-width:500px;border-style:solid;">
									...
								</div>
								<div class="progress" style="display:none;">
									<div class="progress_bar" style="background:green;height:5px;"></div>
								</div>
							</div>
							<div class="footer">
								<div class="section_wrap">
									<label>' . htmlspecialchars(static::fPhrase('Upload multiple images', [], $t)) . '</label>
									<div class="button">
										<span>' . htmlspecialchars(static::fPhrase('Browse files', [], $t)) . '</span>
										<input class="upload_file_fragments" type="file" name="file_upload[]" multiple accept="image/jpeg,image/gif,image/png">
									</div>
								</div>
								<div class="section_wrap">
									<label>' . htmlspecialchars(static::fPhrase('Filename', [], $t)) . '</label>
									<input type="text" class="filename" value="' . htmlspecialchars($fileName) . '" ' . htmlspecialchars($fileNameReadonly) . '>.pdf
								</div>
								<div class="section_wrap save">
									<input type="button" class="combine" value="' . htmlspecialchars(static::fPhrase('Combine', [], $t)) . '">
								</div>
							</div>
						</div>
					</div>';
				break;
		}
		
		if ($this->form['show_errors_below_fields']) {
			$html .= $errorHTML;
		}
		
		if (!empty($field['note_to_user'])) {
			$html .= '<div class="note_to_user">'. static::fPhrase($field['note_to_user'], [], $t) .'</div>'; //can be HTML
		}
		
		//Field containing div open
		$containerHTML = '<div id="' . htmlspecialchars($this->containerId) . '_field_' . htmlspecialchars($fieldId) . '" data-id="' . htmlspecialchars($fieldId) . '" ';
		if ($field['visibility'] == 'visible_on_condition') {
			$containerHTML .= $this->getVisibleConditionDataValuesHTML($field, $pageId);
		}
		if ($field['type'] == 'restatement') {
			$containerHTML .= ' data-fieldid="' . htmlspecialchars($field['restatement_field']) . '"';
		} elseif ($field['type'] == 'calculated') {
			if ($field['value_prefix']) {
				$containerHTML .= ' data-prefix="' . htmlspecialchars($field['value_prefix']) . '"';
			}
			if ($field['value_postfix']) {
				$containerHTML .= ' data-postfix="' . htmlspecialchars($field['value_postfix']) . '"';
			}
		} elseif ($field['type'] == 'document_upload') {
			if ($field['combined_filename'] && $field['stop_user_editing_filename']) {
				$containerHTML .= ' data-filename="' . htmlspecialchars($field['combined_filename']) . '"';
			}
		}
		//Check if field is hidden (ignoring the repeat)
		if ($hidden) {
			$containerHTML .= ' style="display:none;"';
		}
		
		//Containing div css classes
		$containerHTML .= ' class="form_field field_' . htmlspecialchars($field['type']) . ' ' . htmlspecialchars($field['css_classes']);
		if ($field['repeat_start_id']) {
			$idParts = explode('_', $fieldId);
			if (count($idParts) == 2) {
				$parentFieldId = $idParts[0];
				$containerHTML .= ' field_' . htmlspecialchars($parentFieldId) . '_repeat';
			}
		}
		if ($readonly) {
			$containerHTML .= ' readonly';
		}
		if ($field['visibility'] == 'visible_on_condition') {
			$containerHTML .= ' visible_on_condition';
		}
		if (isset($this->errors[$fieldId])) {
			$containerHTML .= ' has_error';
		}
		$containerHTML .= ' ' . htmlspecialchars($extraClasses);
		$containerHTML .= '">';
		
		$html = $containerHTML . $html . '</div>';
		return $html;
	}
	
	protected function getFieldCurrentValue($fieldId, $recursionCount = 1) {
		if (!isset($this->fields[$fieldId]) || $recursionCount > 999) {
			return false;
		}
		$value = false;
		$field = $this->fields[$fieldId];
		
		if ($field['type'] == 'calculated') {
			return $this->getCalculatedFieldCurrentValue($fieldId, $recursionCount);
		} elseif ($field['type'] == 'restatement') {
			$value = $this->getFieldCurrentValue($field['restatement_field'], ++$recursionCount);
			return static::getFieldDisplayValue($this->fields[$field['restatement_field']] ?? false, $value);
		}
		
		//Check if value has been saved before
		if (isset($_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'][$fieldId])) {
			$value = $_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'][$fieldId];
		//..Otherwise see if we can load from the  dataset
		} elseif ($field['preload_dataset_field_user_data'] && $this->userId && $field['db_column']) {
			$this->allowCaching(false);
			
			$row = false;
			if (isset($field['row'])) {
				$row = $field['row'];
			}
			$datasetStoredValue = ze\dataset::fieldValue($this->dataset, $field['dataset_field_id'], $this->userId, true, false, $row);
			$value = static::getFieldValueFromStored($field, $datasetStoredValue);
			
			//Special case for first_name and last_name dataset fields where they have an option to show both names in one
			if ($field['split_first_name_last_name']) {
				if ($field['db_column'] == 'first_name') {
					$lastName = ze\dataset::fieldValue($this->dataset, 'last_name', $this->getUserId(), true, false, $row);
					$value = $value . ' ' . $lastName;
				} elseif ($field['db_column'] == 'last_name') {
					$firstName = ze\dataset::fieldValue($this->dataset, 'first_name', $this->getUserId(), true, false, $row);
					$value = $firstName . ' ' . $value;
				}
			}
			
			//Hack to allow dataset fields to have a default value of 0 for calculated fields
			if (!$value && $field['dataset_field_validation'] == 'numeric') {
				$value = 0;
			}
			
			if ($field['invert_dataset_result']) {
				$value = !$value;
			}
			
		//..Otherwise look for a default value
		} elseif ($field['default_value'] !== null) {
			$value = $field['default_value'];
		} elseif ($field['default_value_class_name'] !== null && $field['default_value_method_name'] !== null) {
			$this->allowCaching(false);
			
			ze\module::inc($field['default_value_class_name']);
			$value = call_user_func(
				[
					$field['default_value_class_name'], 
					$field['default_value_method_name']
				],
				$field['default_value_param_1'], 
				$field['default_value_param_2']
			);
		}
		
		//There is also the option to externaly load a value with a post request
		$mergeName = $this->getFormFieldMergeName($field);
		if (!empty($_POST['preload_from_post']) && $mergeName && isset($_POST[$mergeName])) {
			$value = $_POST[$mergeName];
		}
		
		$value = is_null($value) ? false : $value;
		return $value;
	}
	
	private function getCalculatedFieldCurrentValue($fieldId, $recursionCount) {
		$field = $this->fields[$fieldId];
		$value = 0;
		$maxNumberSize = 999999999999999;
		$minNumberSize = -1 * $maxNumberSize;
		
		$calculationCodeJSON = $this->expandCalculatedFieldsInCalculationCode($field['calculation_code']);
		$calculationCode = json_decode($calculationCodeJSON, true);
		
		$equation = '';
		$isNaN = false;
		
		if ($calculationCode) {
			foreach ($calculationCode as $step) {
				switch ($step['type']) {
					case 'static_value':
						$fieldValue = (float)$step['value'];
						$fieldValue = sprintf('%f', $fieldValue);
						$equation .= $fieldValue;
						break;
					case 'field':
						$fieldValue = false;
						if ($step['value']) {
							$fieldValue = $this->getFieldCurrentValue($step['value'], ++$recursionCount);
						}
						if (!$fieldValue) {
							$fieldValue = 0;
						}
						if (!static::validateNumericInput($fieldValue)) {
							$isNaN = true;
							break 2;
						} else {
							$fieldValue = sprintf('%f', (float)$fieldValue);
							if (!empty($this->fields[$step['value']]['repeat_start_id'])) {
								$repeatFieldValues = [$fieldValue];
								$rows = $this->fields[$this->fields[$step['value']]['repeat_start_id']]['rows'];
								
								//Target field and calculated field are in the same repeat block
								if (!empty($field['repeat_start_id']) && $field['repeat_start_id'] == $this->fields[$step['value']]['repeat_start_id']) {
									if ($field['row'] > 1) {
										$repeatFieldValues = [];
										$rows = [$field['row']];
									} else {
										$rows = [];
									}
								}
								
								foreach ($rows as $row) {
									if ($row != 1) {
										$repeatFieldId = static::getRepeatFieldId($step['value'], $row);
										$repeatFieldValue = $this->getFieldCurrentValue($repeatFieldId, ++$recursionCount);
										if (!$repeatFieldValue) {
											$repeatFieldValue = 0;
										}
										if (!static::validateNumericInput($repeatFieldValue)) {
											$isNaN = true;
											break 3;
										} else {
											$repeatFieldValue = sprintf('%f', (float)$repeatFieldValue);
										}
										$repeatFieldValues[] = $repeatFieldValue;
									}
								}
								
								$fieldValue = '(0+' . implode('+', $repeatFieldValues) . ')';
							}
							$equation .= $fieldValue;
						}
						$equation = '0+(' . $equation . ')';
						break;
					case 'parentheses_open':
						$equation .= '(';
						break;
					case 'parentheses_close':
						$equation .= ')';
						break;
					case 'operation_addition':
						$equation .= '+';
						break;
					case 'operation_subtraction':
						$equation .= '-';
						break;
					case 'operation_multiplication':
						$equation .= '*';
						break;
					case 'operation_division':
						$equation .= '/';
						break;
				}
			}
			
			if (!$isNaN && $equation) {
				$calculator = new calculator();
				try {
					$value = $calculator->calculate($equation);
					if ($value === false || ($value > $maxNumberSize) || ($value < $minNumberSize)) {
						$isNaN = true;
					}
				} catch (\Exception $e) {
					$isNaN = true;
				}
			}
		}
		
		if ($isNaN) {
			$value = 'NaN';
		} else {
			if (!$value) {
				$value = 0;
			} else {
				$value = rtrim(rtrim(sprintf('%0.2f', $value), '0'), '.');
			} 
			
			if ($field['value_prefix']) {
				$value = $field['value_prefix'] . $value;
			}
			if ($field['value_postfix']) {
				$value .= $field['value_postfix'];
			}
		}
		
		return $value;
	}
	
	private function getFieldCurrentLOV($fieldId) {
		$values = [];
		$field = $this->fields[$fieldId];
		switch ($field['type']) {
			case 'radios':
			case 'centralised_radios':
			case 'select':
			case 'checkboxes':
				return $this->getFormFieldLOV($fieldId);
			//Where field lists can depend on another fields value (text fields can have autocomplete lists)
			case 'centralised_select':
			case 'text':
				//Check if this field has a source field to filter the list
				$filter = false;
				if ($field['filter_on_field'] && isset($this->fields[$field['filter_on_field']])) {
					$filter = $this->getFieldCurrentValue($field['filter_on_field']);
				}
				//Handle the case where a static filter is set but the field is also being dynamically filtered by another field
				$showValues = true;
				$datasetField = false;
				if ($field['dataset_field_id']) {
					$datasetField = ze\dataset::fieldDetails($field['dataset_field_id']);
					if ($filter && $datasetField['values_source_filter'] && $filter != $datasetField['values_source_filter']) {
						$showValues = false;
					}
				} else {
					if ($filter && $field['values_source_filter'] && $filter != $field['values_source_filter']) {
						$showValues = false;
					}
				}
				//If this field is filtered by another field but the value of that field is empty, show no values
				if ($showValues && (!$field['filter_on_field'] || $filter)) {
					return $this->getFormFieldLOV($fieldId, $filter);
				}
				break;
		}
		
		return $values;
	}
	
	private function getFormFieldLOV($fieldId, $filter = false) {
		$field = $this->fields[$fieldId];
		return static::getFormFieldLOVStatic($field, $filter);
	}
	
	public static function getFormFieldLOVStatic($field, $filter = false) {
		if (is_numeric($field)) {
			$field = static::getFormFieldsStatic(false, [], false, false, $field);
		}
		if ($field['dataset_field_id'] && $field['type'] != 'text') {
			return ze\dataset::fieldLOV($field['dataset_field_id'], true, $filter);
		}
		
		$values = [];
		
		//Load a values list for a text field depending on the type of suggested values
		if ($field['type'] == 'text') {
			if ($field['suggested_values'] == 'custom') {
				$field['type'] = 'select';
			} elseif ($field['suggested_values'] == 'pre_defined') {
				$field['type'] = 'centralised_select';
			}
		}
	
		switch ($field['type']) {
			case 'centralised_radios':
			case 'centralised_select':
			case 'restatement':
				if (!empty($field['values_source_filter'])) {
					$filter = $field['values_source_filter'];
				}
				return ze\dataset::centralisedListValues($field['values_source'], $filter);
			case 'select':
			case 'radios':
			case 'checkboxes':
				return ze\row::getValues(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', 'label', ['form_field_id' => $field['id']], 'ord');
		}
		return $values;
	}
	
	public static function getFormFieldValueLabel($datasetFieldId, $valueId) {
		if ($datasetFieldId) {
			return ze\row::get('custom_dataset_field_values', 'label', $valueId);
		} else {
			return ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', 'label', $valueId);
		}
	}
	
	private function loadRepeatRows($repeatStartField) {
		$fieldId = $repeatStartField['id'];
		$fieldName = static::getFieldName($fieldId, static::getFieldCustomCodeName($fieldId) ?: false);
		if (isset($_POST[$fieldName])) {
			$rows = explode(',', $_POST[$fieldName]);
		} elseif (isset($_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'][$fieldId])) {
			$rows = $_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'][$fieldId];
		} elseif ($repeatStartField['dataset_field_id']) {
			$datasetStoredValue = ze\dataset::fieldValue($this->dataset, $repeatStartField['dataset_field_id'], $this->userId);
			$rows = static::getFieldValueFromStored($repeatStartField, $datasetStoredValue);
		} else {
			$rows = static::getFieldValueFromStored($repeatStartField, $repeatStartField['min_rows']);
		}
		
		if (isset($_POST['add_repeat_row']) && $_POST['add_repeat_row'] == $fieldId) {
			$rows[] = end($rows) + 1;
		} elseif (isset($_POST['delete_repeat_row']) && $_POST['delete_repeat_row'] == $fieldId) {
			foreach ($rows as $i => $row) {
				if ($row == $_POST['row']) {
					unset($rows[$i]);
					break;
				}
			}
		}
		if (count($rows) < $repeatStartField['min_rows']) {
			$rows = static::getFieldValueFromStored($repeatStartField, $repeatStartField['min_rows']);
		} elseif (count($rows) > $repeatStartField['max_rows']) {
			$rows = array_slice($rows, 0, $repeatStartField['max_rows']);
		}
		
		//Save new rows here since this function runs before savePageData
		$_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'][$fieldId] = $rows;
		
		return $rows;
	}
	
	private function savePageData($pageId, $post) {
		$t = $this->form['translate_text'];
		$setPreDefinedTextFieldId = false;
		foreach ($this->pages[$pageId]['fields'] as $i => $fieldId) {
			$field = $this->fields[$fieldId];
			$name = static::getFieldName($fieldId, $field['custom_code_name']);
			switch ($field['type']) {
				case 'checkbox':
				case 'group':
					$_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'][$fieldId] = !empty($post[$name]);
					break;
				case 'calculated':
				case 'url':
				case 'text':
				case 'date':
				case 'radios':
				case 'centralised_radios':
				case 'select':
				case 'centralised_select':
					$_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'][$fieldId] = $post[$name] ?? false;
					break;
				case 'textarea':
					if (isset($post['set_predefined_text_' . $fieldId])) {
						$setPreDefinedTextFieldId = $fieldId;
					}
					$_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'][$fieldId] = $post[$name] ?? false;
					break;
				case 'checkboxes':
					$lov = $this->getFieldCurrentLOV($fieldId);
					$values = [];
					foreach ($lov as $valueId => $label) {
						if (!empty($post[$name . '_' . $valueId])) {
							$values[] = $valueId;
						}
					}
					$_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'][$fieldId] = $values;
					break;
				case 'attachment':
					if (!empty($_FILES[$name]) && isset($_FILES[$name]['size']) && $_FILES[$name]['size'] > 0 && ze\cache::cleanDirs()) {
						ze\fileAdm::exitIfUploadError(true, true, true, $fileVar = $name);
						try {
							//Undefined | Multiple Files | $_FILES Corruption Attack
							//If this request falls under any of them, treat it invalid.
							if (!isset($_FILES[$name]['error']) || is_array($_FILES[$name]['error'])) {
								throw new RuntimeException(static::fPhrase('Invalid parameters.', [], $t));
							}
							
							//Check $_FILES[$name]['error'] value.
							switch ($_FILES[$name]['error']) {
								case UPLOAD_ERR_OK:
									break;
								case UPLOAD_ERR_NO_FILE:
									//Handled by validateFormField
									//throw new RuntimeException(static::fPhrase('No file sent.', [], $t));
									break;
								case UPLOAD_ERR_INI_SIZE:
								case UPLOAD_ERR_FORM_SIZE:
									$max = $this->getMaxUploadSize();
									throw new RuntimeException(static::fPhrase('Exceeded filesize limit of [[max]].', ['max' => $max], $t));
								default:
									throw new RuntimeException(static::fPhrase('Unknown errors.', [], $t));
							}
							
							//File is valid, add to cache and remember the location
							$randomDir = ze\cache::createRandomDir(30, 'uploads');
							if (!$randomDir) {
								exit('Could not create cache directory in private/uploads');
							}
							$cacheDir = $randomDir. ze\file::safeName($_FILES[$name]['name'], true);
							if (\ze\fileAdm::moveUploadedFile($_FILES[$name]['tmp_name'], CMS_ROOT. $cacheDir)) {
								\ze\cache::chmod(CMS_ROOT. $cacheDir, 0666);
								$_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'][$fieldId] = $cacheDir;
							}
							
						} catch (RuntimeException $e) {
							$this->errors[$fieldId] = $e->getMessage();
						}
					} elseif (isset($post['remove_attachment_' . $fieldId])) {
						$_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'][$fieldId] = false;
					} else {
						$_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'][$fieldId] = $post[$name] ?? false;
					}
					break;
				case 'file_picker':
				case 'document_upload':
					$files = json_decode($post[$name], true);
					$_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'][$fieldId] = $files ? $files : [];
					break;
			}
		}
		//Set pre-defined text from trigger fields that have been set.
		if ($setPreDefinedTextFieldId) {
			$result = ze\row::query(ZENARIO_USER_FORMS_PREFIX . 'predefined_text_triggers', ['form_field_id', 'form_field_value_id', 'text'], ['target_form_field_id' => $setPreDefinedTextFieldId], 'ord');
			$text = '';
			while ($row = ze\sql::fetchAssoc($result)) {
				$triggered = false;
				$triggerField = $this->fields[$row['form_field_id']] ?? false;
				
				if ($triggerField) {
					$triggerFieldValue = $this->getFieldCurrentValue($triggerField['id']);
					if ($triggerField['type'] == 'checkbox' || $triggerField['type'] == 'group') {
						$triggered = $triggerFieldValue;
					} elseif ($triggerField['type'] == 'checkboxes' && $row['form_field_value_id']) {
						$triggered = in_array($row['form_field_value_id'], $triggerFieldValue);
					}
					if ($triggered) {
						$text .= $row['text'] . "\n\n";
					}
				}
			}
			$_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'][$setPreDefinedTextFieldId] = $text;
		}
	}
	
	private function getMaxUploadSize() {
		$bytes = $this->convertPHPSizeToBytes(ini_get('upload_max_filesize'));
		return ze\file::fileSizeConvert($bytes);
	}
	
	private function getMaxPostSize() {
		$bytes = $this->convertPHPSizeToBytes(ini_get('post_max_size'));
		return ze\file::fileSizeConvert($bytes);
	}
	
	private function convertPHPSizeToBytes($size) {
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
		$size = preg_replace('/[^0-9\.]/', '', $size);
		if ($unit) {
			//Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		} else {
			return round($size);
		}
	}
	
	private function getNextFormPage($currentPageId) {
		$pageId = $currentPageId;
		//Select a specific page
		if (isset($_POST['target_page'])) {
			$pageId = $_POST['target_page'];
		//Go forawrds to the next visible page
		} elseif (isset($_POST['next'])) {
			$orderedPages = $this->pages;
			usort($orderedPages, 'ze\ray::sortByOrd');
			$passed = false;
			for ($i = 0; $i < count($orderedPages); $i++) {
				if ($orderedPages[$i]['id'] == $currentPageId) {
					$passed = true;
				} elseif ($passed && !$this->isPageHidden($orderedPages[$i])) {
					$pageId = $orderedPages[$i]['id'];
					break;
				}
			}
			
		//Go backwards to the next visible page
		} elseif (isset($_POST['previous'])) {
			$orderedPages = $this->pages;
			usort($orderedPages, 'ze\ray::sortByOrd');
			$passed = false;
			for ($i = count($orderedPages) - 1; $i >= 0; $i--) {
				if ($orderedPages[$i]['id'] == $pageId) {
					$passed = true;
				} elseif ($passed && !$this->isPageHidden($orderedPages[$i])) {
					$pageId = $orderedPages[$i]['id'];
					break;
				}
			}
		}
		
		//Remember max page reached
		$maxPageReached = $_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data']['max_page_reached'] ?? false;
		if ($pageId != 'summary' && (!$maxPageReached || !isset($this->pages[$maxPageReached]) || (isset($this->pages[$pageId]) && ($this->pages[$pageId]['ord'] > $this->pages[$maxPageReached]['ord'])))) {
			$_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data']['max_page_reached'] = $pageId;
		}
		
		return $pageId;
	}
	
	private function loadPartialSaveData($userId, $formId, $getRequestValue = null) {
		//Load max_page_reached
		$partialSave = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response', ['id', 'max_page_reached', 'form_id'], ['user_id' => $userId, 'form_id' => $formId, 'get_request_value' => $getRequestValue]);
		$_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'] = [];
		$_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data']['max_page_reached'] = $partialSave['max_page_reached'];
		
		//Load field values
		$fields = static::getFormFieldsStatic($partialSave['form_id'], [], false, $loadFromPartialResponseId = $partialSave['id']);
		foreach ($fields as $fieldId => $field) {
			if (isset($field['value'])) {
				$_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data'][$fieldId] = $field['value'];
			}
		}
	}
	
	private function validateForm($pageId, $validateAllFields, $ignoreRequiredFields) {
		$t = $this->form['translate_text'];
		if ($pageId == 'summary') {
			if ($this->form['enable_summary_page_required_checkbox'] && empty($_POST['summary_required_checkbox'])) {
				$this->errors['summary_required_checkbox'] = static::fPhrase($this->form['summary_page_required_checkbox_error_message'], [], $t);
			}
			return empty($this->errors);;
		}
		
		if ($validateAllFields) {
			$fields = array_keys($this->fields);
		} else {
			$fields = $this->pages[$pageId]['fields'];
		}
		
		$pageVisibility = [];
		foreach ($fields as $i => $fieldId) {
			$field = $this->fields[$fieldId];
			switch ($field['type']) {
				case 'repeat_start':
				case 'repeat_end':
				case 'section_description':
				case 'restatement':
					continue 2;
			}
			//Don't validate fields on hidden pages
			if (!isset($pageVisibility[$field['page_id']])) {
				$pageVisibility[$field['page_id']] = !$this->isPageHidden($this->pages[$field['page_id']]);
			}
			
			if ($pageVisibility[$field['page_id']]) {
				$error = $this->validateFormField($fieldId, $ignoreRequiredFields);
				if ($error) {
					$this->errors[$fieldId] = $error;
					$this->errors['global_top'] = ze\lang::phrase('Please check below for errors.');
				}
			}
		}
		
		//Validate honeypot field
		if ($this->form['use_honeypot'] && isset($_POST['field_hp']) && $_POST['field_hp'] !== '') {
		    $this->errors['honeypot'] = static::fPhrase('This field must be left blank.', [], $t);
		}
		
		//Validate captcha
		$error = $this->getCaptchaError();
		if ($error) {
			$this->errors['captcha'] = $error;
		}
		
		//Custom messages by modules extending this
		$customErrors = $this->getCustomErrors();
		if (is_array($customErrors)) {
			foreach ($customErrors as $fieldId => $error) {
				if (!isset($this->errors[$fieldId])) {
					$this->errors[$fieldId] = $error;
					$this->errors['global_top'] = ze\lang::phrase('Please check below for errors.');
				}
			}
		}
		return empty($this->errors);
	}
	
	private function validateFormField($fieldId, $ignoreRequiredFields) {
		//Don't overwrite existing error (e.g. file upload errors are assigned before this function is called)
		if (isset($this->errors[$fieldId])) {
			return false;
		}
		$field = $this->fields[$fieldId];
		$value = $this->getFieldCurrentValue($fieldId);
		$t = $this->form['translate_text'];
		
		//If mandatory if visible, copy visibility settings into mandatory settings
		if ($field['mandatory_if_visible']) {
			if (!$this->isFieldHidden($field)) {
				$field['is_required'] = true;
			}
		}
		
		//If this field is conditionally mandatory, see if the condition is met
		if ($field['mandatory_condition_field_id']) {
			$requiredFieldId = $field['mandatory_condition_field_id'];
			$requiredField = $this->fields[$requiredFieldId];
			$requiredFieldValue = $this->getFieldCurrentValue($requiredFieldId);
			switch ($requiredField['type']) {
				case 'checkbox':
				case 'group':
					$field['is_required'] = ($field['mandatory_condition_field_value'] == $requiredFieldValue);
					break;
				case 'radios':
				case 'select':
				case 'centralised_radios':
				case 'centralised_select':
					if (($field['mandatory_condition_field_value'] && ($field['mandatory_condition_field_value'] == $requiredFieldValue))
						|| (!$field['mandatory_condition_field_value'] && $requiredFieldValue)
					) {
						$field['is_required'] = true;
					}
					break;
				case 'checkboxes':
					if (!$requiredFieldValue) {
						$requiredFieldValue = [];
					}
					$values = $field['mandatory_condition_field_value'] ? explode(',', $field['mandatory_condition_field_value']) : [];
					
					if ($field['mandatory_condition_checkboxes_operator'] == 'AND') {
						$sharedValues = array_intersect($requiredFieldValue, $values);
						$selectedRequiredValues = array_intersect($requiredFieldValue, $sharedValues);
						
						$field['is_required'] = ($selectedRequiredValues == $values);
					} else {
						foreach ($requiredFieldValue as $requiredFieldValue) {
							if (in_array($requiredFieldValue, $values)) {
								$field['is_required'] = true;
								break;
							}
						}
					}
					break;
			}
			
			if ($field['mandatory_condition_invert'] == 1) {
				$field['is_required'] = !$field['is_required'];
			} else if ($field['mandatory_condition_invert'] == 2) {
				$field['is_required'] = false;
				if(in_array($requiredFieldValue, explode(',', $field['mandatory_condition_field_value']))){
				    $field['is_required'] = true;
				}
			}
		}
		
		//Check if field is required but has no data
		if ($field['is_required'] && !$ignoreRequiredFields) {
			switch ($field['type']) {
				case 'group':
				case 'checkbox':
				case 'radios':
				case 'select':
				case 'checkboxes':
				case 'attachment':
				case 'file_picker':
				case 'document_upload':
					if (!$value) {
						return static::fPhrase($field['required_error_message'], [], $t);
					}
					break;
				case 'centralised_radios':
				case 'centralised_select':
				case 'text':
				case 'date':
				case 'textarea':
				case 'url':
					if ($value === null || $value === '' || $value === false) {
						return static::fPhrase($field['required_error_message'], [], $t);
					}
					break;
			}
		}
		
		//Check if user is allowed more than one submission
		if (!$this->userId
			&& $field['db_column'] == 'email' 
			&& $this->form['save_data']
			&& $this->form['user_duplicate_email_action'] == 'stop'
			&& $this->form['duplicate_email_address_error_message']
		) {
			$userId = ze\row::get('users', 'id', ['email' => $value]);
			if ($userId) {
				$responseExists = ze\row::exists(
					ZENARIO_USER_FORMS_PREFIX. 'user_response', 
					['user_id' => $userId, 'form_id' => $this->form['id']]
				);
				if ($responseExists) {
					return static::fPhrase($this->form['duplicate_email_address_error_message'], [], $t);
				}
			}
		}
		
		//Text field validation
		if ($field['type'] == 'text' && $field['field_validation'] && $value !== '' && $value !== false) {
			switch ($field['field_validation']) {
				case 'email':
					if (!ze\ring::validateEmailAddress($value)) {
						return static::fPhrase($field['field_validation_error_message'], [], $t);
					}
					break;
				case 'URL':
					if (filter_var($value, FILTER_VALIDATE_URL) === false) {
						return static::fPhrase($field['field_validation_error_message'], [], $t);
					}
					break;
				case 'integer':
					if (filter_var($value, FILTER_VALIDATE_INT) === false) {
						return static::fPhrase($field['field_validation_error_message'], [], $t);
					}
					break;
				case 'number':
					if (!static::validateNumericInput($value)) {
						return static::fPhrase($field['field_validation_error_message'], [], $t);
					}
					break;
				case 'floating_point':
					if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
						return static::fPhrase($field['field_validation_error_message'], [], $t);
					}
					break;
			}
		}
		
		//Multiple values invalid response validation
		if ($field['invalid_field_value_error_message'] && ($field['type'] == 'checkboxes' || $field['type'] == 'radios' || $field['type'] == 'select') && $value) {
			$valueArray = is_array($value) ? $value : [$value];
			foreach ($valueArray as $valueId) {
				$isInvalid = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', 'is_invalid', ['id' => $valueId, 'form_field_id' => $fieldId]);
				if ($isInvalid) {
					return static::fPhrase($field['invalid_field_value_error_message'], [], $t);
				}
			}
		}
		
		//Dataset field validation
		if ($field['dataset_field_id'] && $field['dataset_field_validation'] && $value !== '') {
			switch ($field['dataset_field_validation']) {
				case 'email':
					if (!ze\ring::validateEmailAddress($value)) {
						return static::fPhrase('Please enter a valid email address', [], $t);
					}
					break;
				case 'emails':
					if (!ze\ring::validateEmailAddress($value, true)) {
						return static::fPhrase('Please enter a valid list of email addresses', [], $t);
					}
					break;
				case 'no_spaces':
					if (preg_replace('/\S/', '', $value)) {
						return static::fPhrase('This field cannot contain spaces', [], $t);
					}
					break;
				case 'numeric':
					if (($value !== '' && $value !== false) && !static::validateNumericInput($value)) {
						return static::fPhrase('This field must be numeric', [], $t);
					}
					break;
				case 'screen_name':
					if (empty($value)) {
						$validationMessage = static::fPhrase('Please enter a screen name', [], $t);
					} elseif (!ze\ring::validateScreenName($value)) {
						$validationMessage = static::fPhrase('Please enter a valid screen name', [], $t);
					} elseif (($this->userId && ze\row::exists('users', ['screen_name' => $value, 'id' => ['!' => $this->userId]])) 
						|| (!$this->userId && ze\row::exists('users', ['screen_name' => $value]))
					) {
						return static::fPhrase('The screen name you entered is in use', [], $t);
					}
					break;
			}
		}
		
		//Textarea wordlimit validation
		if ($field['type'] == 'textarea') {
			
			//Catch the case where the user has entered more characters than SQL type TEXT allows.
			$maxColumnLength = 65535;
			$currentLength = strlen($value);
			if ($currentLength > $maxColumnLength){
				return static::fPhrase('Cannot be more than [[n]] characters. Current character count: [[currentCharCount]].', ['n' => $maxColumnLength, 'currentCharCount' => $currentLength], $t);
			} else {
				//Otherwise, follow the min and max word count settings (if specified).
				if ($field['word_count_max'] && $value && str_word_count($value) > $field['word_count_max']) {
					return static::fNPhrase('Cannot be more than [[n]] word.', 'Cannot be more than [[n]] words.', $field['word_count_max'], ['n' => $field['word_count_max']], $t);
				} elseif ($field['word_count_min'] && $value && str_word_count($value) < $field['word_count_min']) {
					return static::fNPhrase('Cannot be less than [[n]] word.', 'Cannot be less than [[n]] words.', $field['word_count_min'], ['n' => $field['word_count_min']], $t);
				}
			}
		}
		
		//Date "not in past" validation
		if ($field['type'] == 'date' && $value) {
			if ($field['no_past_dates']) {
				$time = strtotime($value);
				$minTime = strtotime(date('Y-m-d')) - (60 * 60 * 24);
				if ($time < $minTime) {
					return static::fPhrase('This field cannot be in the past.', [], $t);
				}
			}
			if ($field['no_future_dates']) {
				$time = strtotime($value);
				$maxTime = strtotime(date('Y-m-d')) + (60 * 60 * 24);
				if ($time > $maxTime) {
					return static::fPhrase('This field cannot be in the future.', [], $t);
				}
			}
		}
		
		return false;
	}
	
	private function saveForm() {
		$userId = $this->userId;
		
		$getRequestValue = null;
		if ($this->form['allow_partial_completion'] && $this->form['partial_completion_get_request']) {
			$getRequestValue = $_REQUEST[$this->form['partial_completion_get_request']] ?? 0;
		}
		static::deleteOldPartialResponse($this->form['id'], $userId, $getRequestValue);
		
		$consentFields = [];
		$fieldIdValueLink = [];
		foreach ($this->fields as $fieldId => $field) {
			$this->fields[$fieldId]['value'] = $this->getFieldCurrentValue($fieldId);
			$fieldIdValueLink[$fieldId] = static::getFieldStorableValue($field, $this->fields[$fieldId]['value']);
			
			if (!empty($field['is_consent'])) {
				$consentFields[] = $fieldId;
			}
		}
		
		//Updating users
		if ($userId) {
			if ($this->form['update_linked_fields']) {
				$this->saveUserLinkedFields($userId);
			}
			if ($this->form['add_logged_in_user_to_group']) {
				ze\user::addToGroup($userId, $this->form['add_logged_in_user_to_group']);
			}
			if (($templateId = $this->form['active_timers']) && ze\module::inc('zenario_user_timers')) {
				if ($template = ze\row::get(ZENARIO_USER_TIMERS_PREFIX . 'user_timer_templates', ['type', 'start_date'], $templateId)) {
					$startDate = '';
					if ($template['type'] == 'fixed_dates') {
						
						$startDate = $template['start_date'];
					}
					$dates = zenario_user_timers::extractDatesFromTimerTemplate($templateId, $startDate);
					$result = zenario_user_timers::canCreateTimer($templateId, $userId, $startDate);
					if (ze::isError($result)) {
						
						//$box['tabs']['timer_link']['errors'][] = ze\admin::phrase((string)$result);
					} else {
					
						$timerId = zenario_user_timers::createTimer($templateId, $userId, $startDate);
					
					}
					
				}
				
			}
		//Creating users
		} elseif ($this->form['save_data']) {
		
		    /**
		        $this->form['save_data'] = 1 Always send
		        $this->form['save_data'] = 2 Send on condition of consent field
		    **/
		    if(($this->form['save_data'] == 2 && $this->fields[$this->form['consent_field']]['value']) || ($this->form['save_data'] == 1)){
			    if (isset($this->datasetFieldsColumnLink['email'])) {
				    $email = $this->getFieldCurrentValue($this->datasetFieldsColumnLink['email']);
				    $userId = ze\row::get('users', 'id', ['email' => $email]);
				    if ($userId) {
					    if ($this->form['user_duplicate_email_action'] != 'ignore') {
						    $this->saveUserLinkedFields($userId, [], $this->form['user_duplicate_email_action'] == 'merge');
					    }
				    } elseif ($email && ze\ring::validateEmailAddress($email)) {
					    //Set new user fields
					    $details = [];
					    $details['email'] = $email;
					    $details['status'] = $this->form['type'] == 'registration' ? 'pending' : $this->form['user_status'];
					
					    if ($details['status'] == 'contact') {
						    $details['creation_method_note'] = 'Contact signup';
					    } else {
						    $details['creation_method_note'] = 'User signup';
					    }
					
					    $details['password'] = ze\userAdm::createPassword();
					    if (isset($this->datasetFieldsColumnLink['screen_name'])) {
						    $details['screen_name_confirmed'] = true;
					    }
					    $userId = $this->saveUserLinkedFields($userId, $details);
					    if ($this->form['type'] == 'registration') {
						    $this->sendVerificationEmail($userId);
					    }
				    }
				    if ($userId) {
					    ze\user::addToGroup($userId, $this->form['add_user_to_group']);
					    //Log user in
					    if ($this->form['log_user_in']) {
						    $this->logUserIn($userId);
					    }
				    }
			    }
		    }
		}
		
		//Record consent if the terms and conditions checkbox was present and checked or there was a consent field on the form
		if (!empty($consentFields)) {
			$email = $firstName = $lastName = false;
			
			//Get information about the user consenting (look for details in database if none found on form submission)
			if ($fieldId = ($this->datasetFieldsColumnLink['email'] ?? false)) {
				$email = $this->fields[$fieldId]['value'];
			} else {
			    $email = ze\row::get('users', 'email', $userId);
			}
			if ($fieldId = ($this->datasetFieldsColumnLink['first_name'] ?? false)) {
				$firstName = $this->fields[$fieldId]['value'];
				if ($this->fields[$fieldId]['split_first_name_last_name']) {
					$lastName = trim(substr($firstName, strpos($firstName, ' ')));
					$firstName = trim(substr($firstName, 0, strpos($firstName, ' ')));
				}
			} else {
			    $firstName = ze\row::get('users', 'first_name', $userId);
			}
			if ($fieldId = ($this->datasetFieldsColumnLink['last_name'] ?? false)) {
				$lastName = $this->fields[$fieldId]['value'];
				if ($this->fields[$fieldId]['split_first_name_last_name']) {
					$firstName = trim(substr($lastName, 0, strpos($lastName, ' ')));
					$lastName = trim(substr($lastName, strpos($lastName, ' ')));
				}
			} else {
			    $lastName = ze\row::get('users', 'last_name', $userId);
			}
			
			foreach ($consentFields as $fieldId) {
				if ($this->fields[$fieldId]['value']) {
					$label = $this->fields[$fieldId]['label'];
					ze\user::recordConsent('form', $this->form['id'], $userId, $email, $firstName, $lastName, $label);
				}
			}
		}
		
		//Profanity levels check
		$canSendEmails = true;
		$rating = 0;
		$tolerence = 0;
		if (ze::setting('zenario_user_forms_set_profanity_filter') && $this->form['profanity_filter_text']) {
			$rating = $this->scanTextForProfanities();
			$tolerence = (int)ze::setting('zenario_user_forms_set_profanity_tolerence');
			$canSendEmails = $rating < $tolerence;
		}
		
		//Save form response
		$responseId = false;
		if ($this->form['save_record']) {
			$responseId = $this->createFormResponse($userId, $rating, $tolerence, !$canSendEmails);
		}
		$url = ze\link::toItem(ze::$cID, ze::$cType, true, '', false, false, true);
					if (!$url) {
						$url = ze\link::absolute();
					}
		//Emails
		if ($canSendEmails) {
			$sendEmailToUser = ($this->form['send_email_to_logged_in_user'] || $this->form['send_email_to_email_from_field']);
			$sendEmailToAdmin = ($this->form['send_email_to_admin'] && $this->form['admin_email_addresses']);
			$userEmailMergeFields = false;
			$adminEmailMergeFields = false;
			
			//Send an email to the user
			if ($sendEmailToUser) {
				$startLine = '<p>Dear user,</p>';
				$startLine .= '<p>The form was submitted from '.$url.' with the following data:</p>';
				$userEmailMergeFields = $this->getTemplateEmailMergeFields($userId);
				$emails = [];
				if ($this->form['send_email_to_logged_in_user'] && $userId) {
					$email = ze\row::get('users', 'email', $userId);
					if ($email) {
						if ($this->form['user_email_use_template_for_logged_in_user']==1) {
							if ($this->form['user_email_template_logged_in_user']) {
								zenario_email_template_manager::sendEmailsUsingTemplate($email, $this->form['user_email_template_logged_in_user'], $userEmailMergeFields, $attachments = [], $attachmentFilenameMappings = [], $disableHTMLEscaping = true);
							}
						}
						elseif($this->form['user_email_use_template_for_logged_in_user']==2) {
							$this->sendVisibleFieldsFormEmail($startLine, $email, $userEmailMergeFields,$responseId);
						}
						else {
							$this->sendUnformattedFormEmail($startLine, $email, $userEmailMergeFields);
						}
					}
				}
				if ($this->form['send_email_to_email_from_field'] && $this->form['user_email_field'] && isset($this->fields[$this->form['user_email_field']])) {
					$email = $this->fields[$this->form['user_email_field']]['value'];
					
					if ($email) {
						if ($this->form['user_email_use_template_for_email_from_field']==1) {
							if ($this->form['user_email_template_from_field']) {
								zenario_email_template_manager::sendEmailsUsingTemplate($email, $this->form['user_email_template_from_field'], $userEmailMergeFields);
								
							}
						}
						elseif($this->form['user_email_use_template_for_email_from_field']==2) {
							$this->sendVisibleFieldsFormEmail($startLine, $email, $userEmailMergeFields,$responseId);
						}
						else {
							$this->sendUnformattedFormEmail($startLine, $email, $userEmailMergeFields, $attachments = [], $attachmentFilenameMappings = [], $disableHTMLEscaping = true);
						}
						
					}
				}
			}
			
			//Send an email to administrators
			//If there is a condition, make sure the email is only sent if the correct
			//checkbox is selected.
			if ($sendEmailToAdmin) {
				$conditionFieldId = $this->form['send_email_to_admin_condition_field'];
				print($conditionFieldId);
				if ($this->form['send_email_to_admin_condition'] == 'always_send'
					|| ($this->form['send_email_to_admin_condition'] == 'send_on_condition'
						&& $conditionFieldId
						&& $this->fields[$conditionFieldId]['value'] === true
						)
				) {
					$sendOrganizerLink = $allowVisitorUploadedAttachments = true;
					if ($this->form['admin_email_use_template'] && $this->form['admin_email_template']) {
						$template = ze\row::get('email_templates', ['when_sending_attachments', 'allow_visitor_uploaded_attachments'], ['code' => $this->form['admin_email_template']]);
						if ($template['when_sending_attachments'] == 'send_organizer_link') {
							$sendOrganizerLink = 'admin';
						} elseif ($template['when_sending_attachments'] == 'send_actual_file') {
							$sendOrganizerLink = false;
						}
						
						if (!$template['allow_visitor_uploaded_attachments']) {
							$allowVisitorUploadedAttachments = false;
							$sendOrganizerLink = false;
						}
					}
					
					$adminEmailMergeFields = $this->getTemplateEmailMergeFields($userId, true, $sendOrganizerLink);
				
					//Set reply to address and name
					$replyToEmail = false;
					$replyToName = false;
					if ($this->form['reply_to'] && $this->form['reply_to_email_field'] && isset($this->fields[$this->form['reply_to_email_field']])) {
						$replyToEmail = $this->fields[$this->form['reply_to_email_field']]['value'];
						$replyToName = '';
						if (isset($this->fields[$this->form['reply_to_first_name']])) {
							$replyToName .= $this->fields[$this->form['reply_to_first_name']]['value'];
						}
						if (isset($this->fields[$this->form['reply_to_last_name']])) {
							$replyToName .= ' ' . $this->fields[$this->form['reply_to_last_name']]['value'];
						}
						if (!$replyToName) {
							$replyToName = $replyToEmail;
						}
					}
				
					//If the form uses an email template, and the template is set to send form attachments to admins...
					$allowVisitorUploadedAttachments = true;
					if ($this->form['admin_email_use_template']) {
						$allowVisitorUploadedAttachments = ze\row::get('email_templates', 'allow_visitor_uploaded_attachments', ['code' => $this->form['admin_email_template']]);
					}
					
					$attachments = [];
					//... or the site setting for sending attachments is enabled, add any attachments submitted with this form.
					if ($allowVisitorUploadedAttachments) {
						foreach ($this->fields as $fieldId => $field) {
							switch ($field['type']) {
								case 'attachment':
									if ($field['value']) {
										$attachments[] = $field['value'];
									}
									break;
								case 'file_picker':
								case 'document_upload':
									foreach ($field['value'] as $fileId => $file) {
										$attachments[] = $file['path'];
									}
									break;
							}
						}
					}
					
		
			
					if ($this->form['admin_email_use_template']==1 && $this->form['admin_email_template']) {
						zenario_email_template_manager::sendEmailsUsingTemplate($this->form['admin_email_addresses'], $this->form['admin_email_template'], $adminEmailMergeFields, $attachments, [], $disableHTMLEscaping = true, $replyToEmail, $replyToName);
					}
					elseif($this->form['admin_email_use_template']==2) {
						$startLine = '<p>Dear admin,<p>';
						$startLine .= '<p>The form "' . htmlspecialchars($this->form['name']) . '" (form ID '.htmlspecialchars($this->form['id']).') was submitted from '. $url .' with the following data:</p>';
						
						$this->sendVisibleFieldsFormEmail($startLine, $this->form['admin_email_addresses'], $adminEmailMergeFields,$responseId, $attachments, $replyToEmail, $replyToName, $adminDownloadLinks = true);
					}
					else {
						$startLine = 'Dear admin,';
						$startLine .= '<p>The form "' . htmlspecialchars($this->form['name']) . '" (form ID '.htmlspecialchars($this->form['id']).') was submitted from '. $url .' with the following data:</p>';
						$this->sendUnformattedFormEmail($startLine, $this->form['admin_email_addresses'], $adminEmailMergeFields, $attachments, $replyToEmail, $replyToName, $adminDownloadLinks = true);
					}
				}
			}
		}
		
		//Set simple access COOKIE
		if ($this->form['set_simple_access_cookie']) {
			//check if we can set cookies
			if (ze\cookie::canSet('functionality')) {
				ze\cookie::set('SIMPLE_ACCESS', '1');
			}
		}
		
		//Send a signal if specified
		if ($this->form['send_signal']) {
			ze\module::sendSignal(
				'eventUserFormSubmitted', 
				[
					'data' => $this->getTemplateEmailMergeFields($userId),
					'formProperties' => $this->form,
					'fieldIdValueLink' => $fieldIdValueLink,
					'responseId' => $responseId
				]
			);
		}
		return $responseId;
	}
	
	private function logUserIn($userId) {
		$user = ze\user::logIn($userId);
		if ($this->form['log_user_in_cookie'] && ze\cookie::canSet('functionality')) {
			ze\cookie::set('LOG_ME_IN_COOKIE', $user['login_hash']);
		}
	}
	
	private function sendVerificationEmail($userId) {
		ze\userAdm::updateHash($userId);
		
		$emailMergeFields = ze\user::details($userId);
		if (!empty($emailMergeFields['email']) && $this->form['verification_email_template']) {
			$emailMergeFields['cms_url'] = ze\link::absolute();
			$emailMergeFields['email_confirmation_link'] = $this->linkToItem($this->cID, $this->cType, $fullPath = true, $request = '&confirm_email=1&hash='. $emailMergeFields['hash']);
			$emailMergeFields['user_groups'] = ze\user::getUserGroupsNames($userId);
			zenario_email_template_manager::sendEmailsUsingTemplate($emailMergeFields['email'] ?? false, $this->form['verification_email_template'], $emailMergeFields, []);
		}
	}
	
	private function sendWelcomeEmail($userId) {
		$emailMergeFields = ze\user::details($userId);
		
		if (!empty($emailMergeFields['email']) && $this->form['welcome_email_template']) {
			$emailMergeFields['cms_url'] = ze\link::absolute();
			$emailMergeFields['user_groups'] = ze\user::getUserGroupsNames($userId);
			
			//Deal with encrypted passwords by resetting it
			$password = ze\userAdm::createPassword();
			$emailMergeFields['password'] = $password;
			ze\userAdm::setPassword($userId, $password);
			
			zenario_email_template_manager::sendEmailsUsingTemplate($emailMergeFields['email'] ?? false, $this->form['welcome_email_template'], $emailMergeFields ,[]);
		}
	}
	
	private function getFormFieldMergeName($field) {
		return $field['db_column'] ? $field['db_column'] : 'unlinked_' . $field['type'] . '_' . $field['id'];
	}
	
	private function getTemplateEmailMergeFields($userId, $toAdmin = false, $sendOrganizerLink = false) {
		$mergeFields = [];
		//User merge fields
		if ($userId) {
			$user = ze\user::details($userId);
			$mergeFields['salutation'] = $user['salutation'];
			$mergeFields['first_name'] = $user['first_name'];
			$mergeFields['last_name'] = $user['last_name'];
			$mergeFields['user_id'] = $userId;
		}
		
		//Data merge fields (after user merge fields so form merge fields are not overridden)
		foreach ($this->fields as $fieldId => $field) {
			
			$column = $this->getFormFieldMergeName($field);
			$displayHTML = static::getFieldDisplayValue($field, $field['value'], $html = true, $sendOrganizerLink);
			
			if ($field['split_first_name_last_name']) {
				$mergeFields['first_name'] = trim(substr($field['value'], 0, strpos($field['value'], ' ')));
				$mergeFields['last_name'] = trim(substr($field['value'], strpos($field['value'], ' ')));
			} else {
				$mergeFields[$column] = $displayHTML;
				
				if ($field['custom_code_name']) {
					$mergeFields[$field['custom_code_name']] = $displayHTML;
				}
			}
		}
		
		//Other merge fields
		$mergeFields['cms_url'] = ze\link::absolute();
		$mergeFields['users_datetime'] = ze\admin::formatDateTime(time(), '_MEDIUM');
		$mergeFields['datetime'] = ze\admin::formatDateTime(date('Y-m-d H:i:s'), '_MEDIUM');
		
		$menuNodes = [];
		$currentMenuNode = ze\menu::getFromContentItem(ze::$cID, ze::$cType);
		if ($currentMenuNode && isset($currentMenuNode['mID']) && !empty($currentMenuNode['mID'])) {
			$menuNodes = static::drawMenu($currentMenuNode['mID'], ze::$cID, ze::$cType);
			if ($this->parentNest) {
				$backs = $this->parentNest->getBackLinks();
				foreach ($backs as $state => $back) {
					$menuNodes[] = $this->parentNest->formatTitleText(ze\lang::phrase($back['slide']['name_or_title'], [], 'zenario_breadcrumbs'));
				}
			}
		}
		$mergeFields['breadcrumbs'] = implode(' » ', $menuNodes);
		
		return $mergeFields;
	}
	
	private function sendUnformattedFormEmail($startLine, $email, $mergeFields = [], $attachments = [], $replyToEmail = false, $replyToName = false, $adminDownloadLinks = false) {
		$formName = $this->form['name'] ? trim($this->form['name']) : '[blank name]';
		$subject = 'New form submission for: ' . $formName;
		$addressFrom = ze::setting('email_address_from');
		$nameFrom = ze::setting('email_name_from');
		
		$body =
			'<p>' . htmlspecialchars($startLine) . '</p>
			<p>The form "' . htmlspecialchars($formName) . '" was submitted with the following data:</p>';
		
		if ($this->form['send_email_to_admin'] && !$this->form['admin_email_use_template']) {
			if (!empty($mergeFields['breadcrumbs'])) {
				$body .= '<p>Page submitted from: ' . htmlspecialchars($mergeFields['breadcrumbs']) . '</p>';
			}
		}
		
		foreach ($this->fields as $fieldId => $field) {
			switch ($field['type']) {
				case 'repeat_start':
				case 'repeat_end':
				case 'section_description':
				case 'restatement':
					continue 2;
			}
			
			
			$includeDownloadLinks = $adminDownloadLinks ? 'admin' : false;
			$displayHTML = static::getFieldDisplayValue($field, $field['value'], $html = true, $includeDownloadLinks);
			if ($field['type'] != 'attachment') {
				if ($field['type'] == 'textarea' && $displayHTML) {
					$displayHTML = '<br>' . $displayHTML;
				}
				$body .= '<p>' . htmlspecialchars(trim($field['name'], " \t\n\r\0\x0B:")) . ': ' . $displayHTML . '</p>';
			}
		}
		
		$url = ze\link::toItem(ze::$cID, ze::$cType, true, '', false, false, true);
		if (!$url) {
			$url = ze\link::absolute();
		}
		$body .= '<p>This is an auto-generated email from ' . htmlspecialchars($url) . '</p>';
		zenario_email_template_manager::putBodyInTemplate($body);
		zenario_email_template_manager::sendEmails($email, $subject, $addressFrom, $nameFrom, $body, [], $attachments, [], 0, false, $replyToEmail, $replyToName);
	}
	
	
	
	public static function drawMenu($nodeId, $cID, $cType) {
		$nodes = [];
		do {
			$text = ze\row::get('menu_text', 'name', ['menu_id' => $nodeId, 'language_id' => ze::setting('default_language')]);
			$nodes[] = $text;
			$nodeId = ze\menu::parentId($nodeId);
		} while ($nodeId != 0);
		
		$homeCID = $homeCType = false;
		ze\content::langSpecialPage('zenario_home', $homeCID, $homeCType);
		if (!($cID == $homeCID && $cType == $homeCType)) {
			$equivId = ze\content::equivId($homeCID, $homeCType);
			$sectionId = ze\menu::sectionId('Main');
			$menuId = ze\row::get('menu_nodes', 'id', ['section_id' => $sectionId, 'equiv_id' => $equivId, 'content_type' => $homeCType]);
			if ($menuId) {
				$nodes[] = ze\row::get('menu_text', 'name', ['menu_id' => $menuId, 'language_id' => ze::setting('default_language')]);
			}
		}
		return array_reverse($nodes);
	}
	
	private function scanTextForProfanities() {
		$path = CMS_ROOT . 'zenario/libs/not_to_redistribute/profanity-filter/profanities.csv';
		$file = fopen($path,"r");
		
		$text = '';
		foreach ($this->fields as $fieldId => $field) {
			if (($field['type'] == 'text' || $field['type'] == 'textarea') && $field['value']) {
				$text .= $field['value'] . ' ';
			}
		}
		
		$rating = 0;
		while(!feof($file)) {
			$line = fgetcsv($file);
			$word = str_replace('-', '\\W*', $line[0]);
			$level = $line[1];
			
			preg_match_all("#\b". $word ."(?:es|s)?\b#si", $text, $matches, PREG_SET_ORDER);
			$rating += count($matches) * $level;
		}
		
		fclose($file);
		return $rating;
	}
	
	//Save user data from form. If merging, saving is only allowed if no data has been saved in the field already.
	private function saveUserLinkedFields($userId, $userData = [], $merge = false) {
		$userDetails = ze\user::details($userId);
		$dataset = ze\dataset::details('users');
		$userCustomData = [];
		$userCustomCheckboxValues = [];
		$userCustomFilePickerValues = [];
		foreach ($this->datasetFieldsLink as $fieldId => $datasetFieldId) {
			$field = $this->fields[$fieldId];
			
			//Only save linked fields when the field is not readonly, the "email" system field, and not hidden with "visible_on_condition"
			if (!$field['is_readonly'] && $field['db_column'] != 'email' && ($field['visibility'] != 'visible_on_condition' || !$this->isFieldHidden($field))) {
				$value = $this->getFieldCurrentValue($fieldId);
				
				if ($field['type'] == 'checkboxes') {
					$lov = ze\dataset::fieldLOV($datasetFieldId);
					$canSave = true;
					if ($merge) {
						foreach ($lov as $valueId => $valueDetails) {
							if (ze\row::exists('custom_dataset_values_link', ['dataset_id' => $dataset['id'], 'value_id' => $valueId, 'linking_id' => $userId])) {
								$canSave = false;
								break;
							}
						}
					}
					if ($canSave) {
						$userCustomCheckboxValues[$datasetFieldId] = ['all' => $lov, 'set' => []];
						foreach ($value as $valueId) {
							if (isset($lov[$valueId])) {
								$userCustomCheckboxValues[$datasetFieldId]['set'][] = $valueId;
							}
						}
					}
				} elseif ($field['type'] == 'file_picker') {
					$canSave = true;
					if ($merge) {
						$canSave = !ze\row::exists('custom_dataset_files_link', ['dataset_id' => $dataset['id'], 'field_id' => $datasetFieldId, 'linking_id' => $userId]);
					}
					if ($canSave) {
						$value = static::getFieldStorableValue($field, $value);
						$values = static::getFieldValueFromStored($field, $value);
						$userCustomFilePickerValues[$datasetFieldId] = [];
						foreach ($values as $fileId => $file) {
							$userCustomFilePickerValues[$datasetFieldId][] = $fileId;
						}
					}
				} elseif ($field['db_column'] && (!$merge || empty($userDetails[$field['db_column']]))) {
					$dbColumn = $field['db_column'];
					if (!empty($field['repeat_start_id']) && isset($field['row'])) {
						$rows = $this->fields[$field['repeat_start_id']]['rows'];
						$row = array_search($field['row'], $rows);
						if ($row === false) {
							continue;
						} else {
							$row++;
						}
						$dbColumn = ze\dataset::repeatRowColumnName($dbColumn, $row);
					}
					
					if ($field['type'] == 'repeat_start') {
						$value = static::getFieldStorableValue($field, $value);
					}
					
					if ($field['invert_dataset_result']) {
						$value = !$value;
					}
					
					if ($field['is_system_field']) {
						//Special case for first_name and last_name dataset fields where they have an option to show both names in one
						if ($field['split_first_name_last_name']) {
							$userData['first_name'] = trim(substr($value, 0, strpos($value, ' ')));
							$userData['last_name'] = trim(substr($value, strpos($value, ' ')));
						
						//All other fields
						} else {
							$userData[$dbColumn] = $value;
						}
					} else {
						$userCustomData[$dbColumn] = $value;
					}
				}
			}
		}
		
		//Save system fields
		$newUserId = ze\userAdm::save($userData, $userId);
		if ($userId) {
			ze\module::sendSignal('eventUserModified', ['id' => $userId]);
		} else {
			ze\module::sendSignal('eventUserCreated', ['id' => $newUserId]);
		}
		$userId = $newUserId;
		
		if ($userId) {
			//Save custom fields
			if ($userCustomData) {
				ze\row::set('users_custom_data', $userCustomData, ['user_id' => $userId]);
			}
			//Save custom multi-checkbox fields
			foreach ($userCustomCheckboxValues as $datasetFieldId => $values) {
				//Remove existing values
				foreach ($values['all'] as $valueId => $value) {
					ze\row::delete('custom_dataset_values_link', ['dataset_id' => $dataset['id'], 'value_id' => $valueId, 'linking_id' => $userId]);
				}
				//Save new
				foreach ($values['set'] as $valueId) {
					ze\row::insert('custom_dataset_values_link', ['dataset_id' => $dataset['id'], 'value_id' => $valueId, 'linking_id' => $userId]);
				}
			}
			//Save custom form picker fields
			foreach ($userCustomFilePickerValues as $datasetFieldId => $fileIds) {
				//Remove existing values
				ze\row::delete('custom_dataset_files_link', ['dataset_id' => $dataset['id'], 'field_id' => $datasetFieldId, 'linking_id' => $userId]);
				//Save new
				foreach ($fileIds as $fileId) {
					ze\row::insert('custom_dataset_files_link', ['dataset_id' => $dataset['id'], 'field_id' => $datasetFieldId, 'linking_id' => $userId, 'file_id' => $fileId]);
				}
			}
		}
		
		return $userId;
	}
	
	
	private function createFormResponse($userId, $rating = 0, $tolerence = 0, $blocked = 0) {
		static::clearOldData();
		
		if ($this->form['period_to_delete_response_headers'] === '0'
			|| ($this->form['period_to_delete_response_headers'] === '' && ze::setting('period_to_delete_the_form_response_log_headers') === '0')
		) {
			return false;
		}
		
		$responseId = ze\row::insert(ZENARIO_USER_FORMS_PREFIX. 'user_response', ['user_id' => $userId, 'form_id' => $this->form['id'], 'response_datetime' => ze\date::now(), 'profanity_filter_score' => $rating, 'profanity_tolerance_limit' => $tolerence, 'blocked_by_profanity_filter' => $blocked]);
		
		if ($this->form['period_to_delete_response_content'] === '0'
			|| ($this->form['period_to_delete_response_content'] === '' && ze::setting('period_to_delete_the_form_response_log_content') === '0')
		) {
			//.. Do not record content
		} else {
			foreach ($this->fields as $fieldId => $field) {
				switch ($field['type']) {
					case 'repeat_end':
					case 'section_description':
						continue 2;
				}
				$row = 0;
				if (!empty($field['repeat_start_id']) && isset($field['row'])) {
					$rows = $this->fields[$field['repeat_start_id']]['rows'];
					$row = array_search($field['row'], $rows);
					if ($row === false) {
						continue;
					} else {
						$row++;
					}
				}
			
				$value = static::getFieldStorableValue($field, $field['value']);
				ze\row::insert(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', ['user_response_id' => $responseId, 'form_field_id' => $field['id'], 'value' => $value, 'field_row' => $row]);
			}
		}
		return $responseId;
	}
	
	public static function getResponseFieldValue($fieldId, $responseId, $row = 0, $codeName = false) {
		//Set value to null if no fieldId is found
		$value = null;
		//Get fieldId if not passed from codeName and responseId
		if (!$fieldId && $codeName) {
			$sql = '
				SELECT uff.id
				FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
				INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response ur
					ON ur.form_id = uff.user_form_id
				WHERE ur.id = ' . (int)$responseId . '
				AND uff.custom_code_name = "' . ze\escape::sql($codeName) . '"';
			$result = ze\sql::select($sql);
			$row = ze\sql::fetchAssoc($result);
			$fieldId = $row['id'];
		}
		//Get value from response
		if ($fieldId) {
			$value = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', 'value', ['user_response_id' => $responseId, 'field_row' => $row, 'form_field_id' => $fieldId]);
		}
		return $value;
	}
	
	//A shortcut function to get a fields display value from a response
	public static function getResponseFieldDisplayValue($fieldId, $responseId, $row = 0, $codeName = false) {
		$formId = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_response', 'form_id', $responseId);
		$field = static::getFormFieldsStatic($formId, [], false, false, $fieldId, $codeName);
		$storedValue = static::getResponseFieldValue($field['id'], $responseId);
		$loadedValue = static::getFieldValueFromStored($field, $storedValue);
		$displayValue = static::getFieldDisplayValue($field, $loadedValue);
		return $displayValue;
	}
	
	//A shortcut function to get a display value from a stored value
	public static function getFieldDisplayValueFromStored($field, $storedValue) {
		$loadedValue = static::getFieldValueFromStored($field, $storedValue);
		return static::getFieldDisplayValue($field, $loadedValue);
	}
	
	
	public static function getFieldStorableValue($field, $value) {
		switch ($field['type']) {
			case 'checkboxes':
				return $value ? implode(',', $value) : '';
			case 'attachment':
				if ($value && file_exists(CMS_ROOT . $value)) {
					$fileId = ze\file::addToDatabase('forms', CMS_ROOT . $value);
					return $fileId;
				}
				return false;
			case 'file_picker':
			case 'document_upload':
				$fileIds = [];
				if ($value) {
					usort($value, 'ze\ray::sortByOrd');
					foreach ($value as $i => $file) {
						$fileId = ze\file::addToDatabase('forms', CMS_ROOT . $file['path']);
						$fileIds[] = $fileId;
					}
				}
				return implode(',', $fileIds);
			case 'repeat_start':
				return count($value);
			default:
				return $value;
		}
		return false;
	}
	
	public static function getFieldValueFromStored($field, $value) {
		switch ($field['type']) {
			case 'checkboxes':
				return $value ? explode(',', $value) : false;
			case 'attachment':
				return $value ? ze\file::link($value) : false;
			case 'file_picker':
			case 'document_upload':
				$fileIds = $value ? explode(',', $value) : [];
				$values = [];
				foreach ($fileIds as $i => $fileId) {
					$values[$fileId] = ['id' => $fileId, 'name' => ze\row::get('files', 'filename', $fileId), 'path' => ze\file::link($fileId), 'ord' => $i];
				}
				return $values;
			case 'repeat_start':
				$rows = [];
				for ($i = 1; $i <= $value; $i++) {
					$rows[] = $i;
				}
				return $rows;
			default:
				return $value;
		}
		return false;
	}
	
	
	//Forms deal with data in 3 formats.
	// - Stored (values stored in the MySQL database)
	// - Loaded (values used in the form module code)
	// - Display (Presentable format for viewing data)
	//This function takes loaded data for the $value parameter
	public static function getFieldDisplayValue($field, $value, $html = false, $includeDownloadLinks = false) {
		$display = '';
		switch ($field['type']) {
			case 'checkbox':
			case 'group':
				$display = $value ? 'Yes' : 'No';
				break;
			case 'radios':
			case 'select':
				$display = $value ? static::getFormFieldValueLabel($field['dataset_field_id'], $value) : '';
				break;
			case 'checkboxes':
				if ($value) {
					$labels = [];
					foreach ($value as $valueId) {
						$labels[] = static::getFormFieldValueLabel($field['dataset_field_id'], $valueId);
					}
					$display = implode(', ', $labels);
				}
				break;
			case 'date':
				$display = ze\date::format($value, '_MEDIUM');
				break;
			case 'centralised_radios':
			case 'centralised_select':
				if ($field['dataset_field_id']) {
					$lov = ze\dataset::fieldLOV($field['dataset_field_id']);
				} else {
					$lov = ze\dataset::centralisedListValues($field['values_source'], $field['values_source_filter']);
				}
				$display = $lov[$value] ?? '';
				break;
			case 'attachment':
				if ($value && ($fileId = static::getFieldStorableValue($field, $value))) {
					$file = ze\row::get('files', ['filename'], $fileId);
					if ($file) {
						if ($html && $includeDownloadLinks == 'visitor') {
							return '<a href="' . htmlspecialchars(ze\file::link($fileId)) . '" target="_blank">' . htmlspecialchars($file['filename']) . '</a>';
						} elseif ($html && $includeDownloadLinks == 'admin') {
							return '<a href="' . htmlspecialchars(ze\link::absolute()) . 'zenario/file.php?adminDownload=1&id=' . (int)$fileId . '" target="_blank">' . htmlspecialchars($file['filename']) . '</a>';
						} else {
							$display = $file['filename'];
						}
					} else {
						$display = 'Unknown file';
					}
				}
				break;
			case 'file_picker':
			case 'document_upload':
				if ($value && ($fileIds = static::getFieldStorableValue($field, $value))) {
					$fileList = [];
					foreach (explode(',', $fileIds) as $fileId) {
						$file = ze\row::get('files', ['filename'], $fileId);
						if ($html && $includeDownloadLinks == 'visitor') {
							$fileList[] = '<a href="' . htmlspecialchars(ze\file::link($fileId)) . '" target="_blank">' . htmlspecialchars($file['filename']) . '</a>';
						} elseif ($html && $includeDownloadLinks == 'admin') {
							$fileList[] = '<a href="' . htmlspecialchars(ze\link::absolute()) . 'zenario/file.php?adminDownload=1&id=' . (int)$fileId . '" target="_blank">' . htmlspecialchars($file['filename']) . '</a>';
						} else {
							$fileList[] = $file['filename'];
						}
					}
					$display = implode(', ', $fileList);
					if ($html) {
						return $display;
					}
				}
				break;
			case 'repeat_start':
				$display = is_array($value) ? count($value) : 0;
				break;
			default:
				$display = $value;
				break;
		}
		if ($html) {
			$display = htmlspecialchars($display);
			if ($field['type'] == 'textarea') {
				$display = nl2br($display);
			}
		}
		return $display;
	}
	
	private function createFormPartialResponse() {
		$userId = $this->userId;
		$getRequestValue = null;
		if ($this->form['partial_completion_get_request']) {
			$getRequestValue = $_REQUEST[$this->form['partial_completion_get_request']] ?? 0;
		}
		
		static::deleteOldPartialResponse($this->form['id'], $userId, $getRequestValue);
		
		$maxPageReached = $_SESSION['custom_form_data'][$this->instanceId][$this->formPageHash]['data']['max_page_reached'];
		
		$responseId = ze\row::insert(
			ZENARIO_USER_FORMS_PREFIX . 'user_partial_response',
			['user_id' => $userId, 'form_id' => $this->form['id'], 'response_datetime' => ze\date::now(), 'max_page_reached' => $maxPageReached, 'get_request_value' => $getRequestValue]
		); 
		
		foreach ($this->fields as $fieldId => $field) {
			switch ($field['type']) {
				case 'repeat_end':
				case 'section_description':
				case 'restatement':
					continue 2;
			}
			$row = 0;
			if (!empty($field['repeat_start_id']) && isset($field['row'])) {
				$rows = $this->fields[$field['repeat_start_id']]['rows'];
				$row = array_search($field['row'], $rows) + 1;
			}
			$value = static::getFieldStorableValue($field, $field['value']);
			
			ze\row::insert(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response_data', ['user_partial_response_id' => $responseId, 'form_field_id' => $field['id'], 'value' => $value, 'field_row' => $row]);
		}
		return $responseId;
	}
	
	
	private function expandCalculatedFieldsInCalculationCode($calculationCodeJSON, $recursionCount = 1) {
		$calculationCode = json_decode($calculationCodeJSON, true);
		
		if ($recursionCount > 99) {
			return false;
		}
		
		if ($calculationCode) {
			foreach ($calculationCode as $index => $step) {
				if ($step['type'] == 'field' && $step['value'] && $this->fields[$step['value']] && ($this->fields[$step['value']]['field_type'] == 'calculated')) {
					$nestedCalculationCode = json_decode($this->fields[$step['value']]['calculation_code']);
					if ($nestedCalculationCode) {
						//Surround nested calculation code with parentheses
						$openParentheses = ['type' => 'parentheses_open'];
						$closeParentheses = ['type' => 'parentheses_close'];
						array_unshift($nestedCalculationCode, $openParentheses);
						array_push($nestedCalculationCode, $closeParentheses);
						
						//Strip square brackets
						$nestedCalculationCodeJSON = substr(json_encode($nestedCalculationCode), 1, -1);
						
						//Place into original calculation code
						$calculationCodeJSON = str_replace(json_encode($step), $nestedCalculationCodeJSON, $calculationCodeJSON);
						
						//Keep calling until all calculated fields are expanded or max recursion limit reached
						return $this->expandCalculatedFieldsInCalculationCode($calculationCodeJSON, $recursionCount + 1);
					}
				}
			}
		}
		return $calculationCodeJSON;
	}
	
	//Check if a centralised list is a list of countries
	private function isCentralisedListOfCountries($field) {
		$source = $field['dataset_field_id'] ? $field['dataset_values_source'] : $field['values_source'];
		return ($source == 'zenario_country_manager::getActiveCountries');
	}
	
	private function enableCaptcha() {
		if ($this->form['use_captcha']
			&& empty($_SESSION['captcha_passed__' . $this->instanceId])
			&& (!$this->userId || $this->form['extranet_users_use_captcha'])
		) {
			if ($this->form['captcha_type'] == 'pictures') {
				return ze::setting('google_recaptcha_site_key') && ze::setting('google_recaptcha_secret_key');
			} elseif ($this->form['captcha_type'] == 'math') {
				return true;
			}
		}
		return false;
	}
	
	private function getCaptchaError() {
		if ($this->enableCaptcha() && ($_POST['submitForm'] ?? false) && $this->instanceId == ($_POST['instanceId'] ?? false)) {
			$error = false;
			$t = $this->form['translate_text'];
			if ($this->form['captcha_type'] == 'math') {
				if ($this->checkMathCaptcha()) {
					$_SESSION['captcha_passed__' . $this->instanceId] = true;
				} else {
					$error = true;
				}
			} elseif ($this->form['captcha_type'] == 'pictures') {
				if ($this->checkCaptcha2()) {
					$_SESSION['captcha_passed__'. $this->instanceId] = true;
				} else {
					$error = true;
				}
			}
			if ($error) {
				return static::fPhrase('Please verify that you are human.', [], $t);
			}
		}
		return false;
	}
	
	private function getCaptchaHTML() {
		$t = $this->form['translate_text'];
		$html = '<div class="form_field captcha">';
		if (isset($this->errors['captcha']) && !$this->form['show_errors_below_fields']) {
			$html .= '<div class="form_error">' . htmlspecialchars(static::fPhrase($this->errors['captcha'], [], $t)) . '</div>';
		}
		if ($this->form['captcha_type'] == 'math') {
			$html .= $this->mathCaptcha();
		} elseif ($this->form['captcha_type'] == 'pictures') {
			$html .= $this->captcha2();
		}
		if (isset($this->errors['captcha']) && $this->form['show_errors_below_fields']) {
			$html .= '<div class="form_error">' . htmlspecialchars(static::fPhrase($this->errors['captcha'], [], $t)) . '</div>';
		}
		$html .= '</div>';
		return $html;
	}
	
	
	
	
	
	
	protected function getWelcomePageHTML() {
		$t = $this->form['translate_text'];
		$user = ze\row::get('users', ['first_name', 'last_name'], $this->userId);
		return '<p class="success">' . htmlspecialchars(static::fPhrase('Welcome, [[first_name]] [[last_name]]', $user, $t)) . '</p>';
	}
	
	protected function getSuccessMessageHTML() {
		$t = $this->form['translate_text'];
		
		if ($this->form['type'] == 'registration') {
			$successMessageHTML = static::fPhrase('Thank you for registering.<br> You have been sent an email with a verification link. You should check your spam/bulk mail if you do not see it soon. Please click the link in the email to verify your account.', [], $t);

		} elseif ($this->form['success_message']) {
			$successMessageHTML = nl2br(static::fPhrase($this->form['success_message'], [], $t));
		} elseif (ze\admin::id()) {
			$successMessageHTML = ze\admin::phrase('Your success message will go here when you set it.');
		} else {
			$successMessageHTML = 'Form submission successful!';
		}
		return '<div class="success">' . $successMessageHTML . '</div>';
	}
	
	protected function getModalWindowButtonHTML() {
		$t = $this->form['translate_text'];
		$requests = 'showInFloatingBox=1';
		$buttonJS = $this->refreshPluginSlotAnchor($requests, false, false);
		
		$html  = '<div class="user_form_click_here" ' . $buttonJS . '>';
		$html .= '<h3>' . static::fPhrase($this->setting('display_text'), [], $t) . '</h3>';
		$html .= '</div>';
		return $html;
	}
	
	protected function getPartialSaveResumeFormHTML() {
		$t = $this->form['translate_text'];
		
		$html  = '<div class="resume_box">';
		$html .= $this->openForm('if (this.submited && !confirm("' . htmlspecialchars($this->phrase("Are you sure you want to clear all your data?")) . '")) { return false; }');
		$html .= '<p>' . static::fPhrase($this->form['clear_partial_data_message'], [], $t) . '</p>';
		$html .= '<input type="submit" onclick="this.form.submited = false" name="resume" value="' . htmlspecialchars(static::fPhrase('Resume', [], $t)) . '">';
		$html .= '<input type="submit" onclick="this.form.submited = true" name="clear" value="' . htmlspecialchars(static::fPhrase('Clear', [], $t)) . '">';
		$html .= $this->closeForm();
		$html .= '</div>';
		return $html;
	}
	
	protected function getVisibleConditionDataValuesHTML($field, $pageId) {
		$html = '';
		$conditionList = static::getConditionList($field, $this->fields);
		
		if($conditionList[0]["invert"] == 2){
		    $conditionList[0]["invert"] = 0;
		}
		
		$html .= ' data-cfields="' . htmlspecialchars(json_encode($conditionList)) . '"';
		return $html;
	}
	
	protected function getCloseButtonHTML() {
		$html = '';
		if ($this->displayMode == 'inline_popup') {
			$html .= '<div class="close" onclick="zenario_user_forms.toggleForm(\'' . htmlspecialchars($this->containerId) . '\')">Close</div>';
		}
		return $html;
	}
	
	
	
	
	
	//An overwritable method when a form is successfully submitted
	protected function successfulFormSubmit($responseId) {
		
	}
	
	//An overwritable method to preload custom data for the form fields
	protected function preloadCustomData() {
		
	}
	
	//An overwritable method to parse an array of requests which are added as hidden inputs on the form
	protected function getCustomRequests() {
		return false;
	}
	
	//An overwritable method to add custom HTML to the buttons area
	//Position can be "first", "center", "last", "top"
	protected function getCustomButtons($pageId, $onLastPage, $position) {
		return false;
	}
	
	//An overwritable method to add custom HTML to the top buttons area
	protected function getCustomTopButtons() {
		return false;
	}
	
	//An overwritable method to set the form title
	protected function getFormTitle($overwrite = false) {
		$t = $this->form['translate_text'];
		$title = $overwrite ? $overwrite : $this->form['title'];
		if ($title && !empty($this->form['title_tag'])) {
			$html = '<' . htmlspecialchars($this->form['title_tag']);
			if ($this->displayMode == 'inline_popup') {
				$html .= ' onclick="zenario_user_forms.toggleForm(\'' . htmlspecialchars($this->containerId) . '\')"';
			}
			$html .= '>';
			$html .= static::fPhrase($title, [], $t);
			$html .= '</' . htmlspecialchars($this->form['title_tag']) . '>';
			return $html;
		}
		return '';
	}
	
	//An overwritable method to set the entire form as readonly
	protected function isFormReadonly() {
		return false;
	}
	
	//An overwritable method to show the submit button or not
	protected function showSubmitButton() {
		return true;
	}
	
	//An overwritable method for custom validation on form fields
	protected function getCustomErrors() {
		return false;
	}
	
	//An overwritable method to get the user of this form
	protected function getUserId() {
	    return ze\user::id();
	}
	
	
	
	
	
	
	//Get the name of the field input on the form
	public static function getFieldName($fieldId, $customCodeName = false) {
		
		if ($customCodeName) {
			return 'field_' . $fieldId . '_' . $customCodeName;
		} else {
			return 'field_' . $fieldId;
		}
	}
	
	//Get the name of the field input on the form
	public static function getFieldCustomCodeName($fieldId) {
		return ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', 'custom_code_name', $fieldId);
	}
	
	public static function validateNumericInput($input) {
		if (!is_numeric($input)) {
			return false;
		}
		$input = (string)$input;
		if (strpos($input, '.') !== false) {
			$input = str_replace('.', '', $input);
		}
		if (!ctype_digit($input)) {
			return false;
		}
		return true;
	}
	
	public static function fPhrase($text, $replace, $translate) {
		if ($translate) {
			return ze\lang::phrase($text, $replace, 'zenario_user_forms');
		}
		ze\lang::applyMergeFields($text, $replace);
		return $text;
	}
	
	public static function fNPhrase($text, $pluralText, $n, $replace, $translate) {
		if ($translate) {
			return ze\lang::phrase($text, $pluralText, $n, $replace, 'zenario_user_forms');
		} elseif ($n == 1) {
			ze\lang::applyMergeFields($text, $replace);
			return $text;
		} else {
			ze\lang::applyMergeFields($pluralText, $replace);
			return $pluralText;
		}
	}
	
	public static function isFormCRMEnabled($formId, $type = 'generic') {
		$where = ['enable' => true, 'form_id' => $formId];
		if ($type) {
			$where['crm_id'] = $type;
		}
		return ze\module::inc('zenario_crm_form_integration') 
			&& ze\row::exists(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', $where);
	}
	
	public static function getTextFormFields($formId) {
		$formFields = [];
		$sql = "
			SELECT uff.id, uff.ord, uff.name, uff.validation AS field_validation, cdf.validation AS dataset_field_validation
			FROM ". DB_PREFIX. ZENARIO_USER_FORMS_PREFIX . "user_form_fields AS uff
			LEFT JOIN ". DB_PREFIX. "custom_dataset_fields AS cdf
				ON uff.user_field_id = cdf.id
			WHERE uff.user_form_id = ". (int)$formId. "
				AND (cdf.type = 'text' || uff.field_type = 'text')
			ORDER BY uff.ord";
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$formFields[] = $row;
		}
		return $formFields;
	}
	
	//Get the ids of modules that can use forms
	public static function getFormModuleIds() {
		$ids = [];
		$formModuleClassNames = ['zenario_user_forms', 'zenario_extranet_profile_edit'];
		foreach($formModuleClassNames as $moduleClassName) {
			if ($id = ze\module::id($moduleClassName)) {
				$ids[] = $id;
			}
		}
		return $ids;
	}
	
	public static function getFormPlugins($formId = false, $pns = null) {
		
		$sql = "
			SELECT DISTINCT ps.instance_id
			FROM ". DB_PREFIX. "plugin_settings AS ps
			INNER JOIN ". DB_PREFIX. "plugin_instances AS pi
			   ON ps.instance_id = pi.id
			WHERE ps.foreign_key_to = 'user_form'
			  AND pi.content_id = 0";
		
		if ($formId) {
			$sql .= "
			  AND ps.foreign_key_id = ". (int) $formId;
		}
		
		switch ($pns) {
			case 'nests':
				$sql .= "
			  AND pi.module_id = ". (int) ze\module::id('zenario_plugin_nest');
				break;
			
			case 'slideshows':
				$sql .= "
			  AND pi.module_id = ". (int) ze\module::id('zenario_slideshow');
				break;
			
			case 'plugins':
				$sql .= "
			  AND pi.module_id NOT IN (". (int) ze\module::id('zenario_slideshow'). ", ". (int) ze\module::id('zenario_plugin_nest'). ")";
		}
		
		return ze\sql::fetchValues($sql);
	}
	
	
	
	
	public static function getFormJSON($formId) {
		$formJSON['form'] = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', true, $formId);
		$formJSON['pages'] = ze\row::getAssocs(ZENARIO_USER_FORMS_PREFIX . 'pages', true, ['form_id' => $formId]);
		$formJSON['fields'] = [];
		$formJSON['values'] = [];
		
		//Get CRM settings if applicable
		$formCRMIntegrationModuleRunning = ze\module::inc('zenario_crm_form_integration');
		if ($formCRMIntegrationModuleRunning) {
			$formJSON['crm_fields'] = [];
			$formJSON['crm_field_values'] = [];
			$formJSON['salesforce_data'] = [];
			$formJSON['mailchimp_data'] = [];
			$formJSON['360lifecycle_data'] = [];
			$formJSON['static_crm_values'] = [];
			$crmFieldValues = [];
			
			$formJSON['crm_link'] = ze\row::getAssocs(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', true, ['form_id' => $formId]);
			
			if (!empty($formJSON['crm_link'])) {
				foreach ($formJSON['crm_link'] as $crmLink) {
					if ($crmLink['crm_id'] == 'salesforce' && !empty($crmLink['enable'])) {
						$formJSON['salesforce_data'] = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'salesforce_data', true, ['form_id' => $formId]);
					}
					
					if ($crmLink['crm_id'] == 'mailchimp' && !empty($crmLink['enable'])) {
						$formJSON['mailchimp_data'] = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'mailchimp_data', true, ['form_id' => $formId]);
					}
					
					if ($crmLink['crm_id'] == '360lifecycle' && !empty($crmLink['enable'])) {
						$formJSON['360lifecycle_data'] = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . '360lifecycle_data', true, ['form_id' => $formId]);
					}
					
					$formJSON['static_crm_values'][$crmLink['id']] = ze\row::getAssocs(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'static_crm_values', true, ['link_id' => $crmLink['id']]);
				}
			}
			
			$allCRMFieldsData = ze\row::getAssocs(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields', true, []);
			if (!empty($allCRMFieldsData)) {
				$allCRMFieldsDataArrayKeys = array_keys($allCRMFieldsData);
			}
		}
		
		$fieldsResult = ze\row::query(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', true, ['user_form_id' => $formId]);
		while ($field = ze\sql::fetchAssoc($fieldsResult)) {
			if ($field['user_field_id']) {
				$datasetField = ze\row::get('custom_dataset_fields', ['db_column', 'type'], $field['user_field_id']);
				$field['_db_column'] = $datasetField['db_column'];
				$field['_type'] = $datasetField['type'];
			}
			
			$formJSON['fields'][$field['id']] = $field;
			
			$formValuesResult = ze\row::query(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', true, ['form_field_id' => $field['id']]);
			while ($value = ze\sql::fetchAssoc($formValuesResult)) {
				$formJSON['values'][$value['id']] = $value;
			}
			
			if ($formCRMIntegrationModuleRunning && !empty($allCRMFieldsDataArrayKeys) && in_array($field['id'], $allCRMFieldsDataArrayKeys)) {
				$formJSON['crm_fields'][$field['id']] = $allCRMFieldsData[$field['id']];
				$crmFieldValues = ze\row::getAssocs(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_field_values', true, ['form_field_id' => $field['id']]);
				if (!empty($crmFieldValues) && count($crmFieldValues) >= 1) {
					$formJSON['crm_field_values'][$field['id']] = $crmFieldValues;
				}
			}
		}
		return $formJSON;
	}
	
	
	public static function validateImportForms($formJSONString) {
		return static::importForms($formJSONString, $validate = true);
	}
	
	public static function importForms($formJSONString, $validate = false) {
		//Decode form json
		$formJSON = json_decode($formJSONString, true);
		
		//Validate import file
		if ($formJSON === null) {
			return new ze\error(ze\admin::phrase('Invalid JSON object parsed.'));
		} elseif (!isset($formJSON['major_version']) || !isset($formJSON['minor_version']) || !isset($formJSON['forms'])) {
			return new ze\error(ze\admin::phrase('Invalid forms import file.'));
		} elseif ($formJSON['major_version'] != ZENARIO_MAJOR_VERSION || $formJSON['minor_version'] != ZENARIO_MINOR_VERSION) {
			return new ze\error(
				ze\admin::phrase(
					'Forms to import are from CMS version [[form_major_version]].[[form_minor_version]]. Your current CMS version is [[cms_major_version]].[[cms_minor_version]]. Please make sure the forms are from the same software version as this site.', 
					[
						'form_major_version' => $formJSON['major_version'], 
						'form_minor_version' => $formJSON['minor_version'], 
						'cms_major_version' => ZENARIO_MAJOR_VERSION, 
						'cms_minor_version' => ZENARIO_MINOR_VERSION
					]
				)
			);
		}
		//Make sure each form in the import file can be imported and get errors and warnings
		$errors = [];
		$warnings = [];
		foreach ($formJSON['forms'] as $index => &$data) {
			$form = &$data['form'];
			
			//Check form name is unique
			$formNameExists = ze\row::exists(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['name' => $form['name']]);
			if ($formNameExists) {
				$errors[$index][] = ze\admin::phrase('A form called "[[name]]" already exists', ['name' => $form['name']]);
				continue;
			}
			
			//Only keep email templates that have a matching template name and not a numeric id
			$emailTemplateFields = ['user_email_template_from_field', 'user_email_template_logged_in_user', 'admin_email_template', 'verification_email_template', 'welcome_email_template'];
			foreach ($emailTemplateFields as $fieldName) {
				if ($form[$fieldName]) {
					if (!is_numeric($form[$fieldName]) && ze\row::exists('email_templates', ['code' => $form[$fieldName]])) {
						$warnings[$index][] = ze\admin::phrase('Email template found in field "[[name]]" with value "[[value]]". A matching template was found on this site however it is not guaranteed to be identical.', ['name' => $fieldName, 'value' => $form[$fieldName]]);
					} else {
						$warnings[$index][] = ze\admin::phrase('Unable to import property "[[name]]" with value "[[value]]".', ['name' => $fieldName, 'value' => $form[$fieldName]]);
						$form[$fieldName] = null;
					}
				}
			}
			
			//Remove any content items since we cannot guess what they should be on this site
			$contentItemFields = ['redirect_location', 'welcome_redirect_location'];
			foreach ($contentItemFields as $fieldName) {
				if ($form[$fieldName]) {
					$warnings[$index][] = ze\admin::phrase('Unable to import property "[[name]]" with value "[[value]]".', ['name' => $fieldName, 'value' => $form[$fieldName]]);
				}
				$form[$fieldName] = null;
			}
			
			//Remove any dataset fields since we cannot guess what they should be on this site
			$datasetFieldFields = ['add_user_to_group', 'add_logged_in_user_to_group'];
			foreach ($datasetFieldFields as $fieldName) {
				if ($form[$fieldName]) {
					$warnings[$index][] = ze\admin::phrase('Unable to import property "[[name]]" with value "[[value]]".', ['name' => $fieldName, 'value' => $form[$fieldName]]);
				}
				$form[$fieldName] = null;
			}
			
			//Check if any fields cannot be imported
			$dataset = ze\dataset::details('users');
			$invalidFields = [];
			foreach ($data['fields'] as $fieldId => &$field) {
				//Try and import dataset fields if there are fields with matching db_column and type
				if ($field['user_field_id']) {
					if (!empty($field['_db_column']) 
						&& !empty($field['_type'])
						&& ($datasetField = ze\row::get('custom_dataset_fields', ['id'], ['dataset_id' => $dataset['id'], 'db_column' => $field['_db_column'], 'type' => $field['_type'], 'repeat_start_id' => 0]))
					) {
						unset($field['_db_column']);
						unset($field['_type']);
						$field['user_field_id'] = $datasetField['id'];
					} else {
						$warnings[$index][] = ze\admin::phrase('Unable to import form field "[[name]]". A dataset field with db_column "[[_db_column]]" and type "[[_type]]" does not exist.', $field);
						$invalidFields[$fieldId] = true;
					}
				}
			}
			unset($field);
			
			//Check if any fields saved on the form have issues
			$formFieldIdProperties = ['user_email_field', 'reply_to_email_field', 'reply_to_first_name', 'reply_to_last_name'];
			foreach ($formFieldIdProperties as $fieldName) {
				if ($form[$fieldName] && isset($invalidFields[$form[$fieldName]])) {
					$form[$fieldName] = 0;
				}
			}
			
			//Check if any fields that can be imported have issues
			$fieldFieldIdProperties = ['mandatory_condition_field_id', 'visible_condition_field_id', 'restatement_field', 'filter_on_field', 'repeat_start_id'];
			foreach ($data['fields'] as $fieldId => &$field) {
				foreach ($fieldFieldIdProperties as $fieldName) {
					if ($field[$fieldName] && isset($invalidFields[$field[$fieldName]])) {
						$warnings[$index][] = ze\admin::phrase('Form field "[[field]]" property "[[name]]" with value "[[value]]" cannot be imported. The target field is invalid.', ['field' => $field['name'], 'name' => $fieldName, 'value' => $field[$fieldName]]);
						unset($field[$fieldName]);
					}
				}
				if (!empty($field['calculation_code'])) {
					$calculationCode = json_decode($field['calculation_code'], true);
					foreach ($calculationCode as $step) {
						if ($step['type'] == 'field' && $step['value'] && isset($invalidFields[$step['value']])) {
							$warnings[$index][] = ze\admin::phrase('Form field "[[field]]" property "calculation_code" with cannot be imported because it contains the invalid field "[[value]]".', ['field' => $field['name'], 'value' => $step['value']]);
							unset($field['calculation_code']);
							break;
						}
					}
				}
			}
			unset($field);
			
			//Check if any pages have issues
			$pageFieldIdProperties = ['visible_condition_field_id'];
			foreach($data['pages'] as $pageId => &$page) {
				foreach ($pageFieldIdProperties as $fieldName) {
					if ($page[$fieldName] && isset($invalidFields[$page[$fieldName]])) {
						$warnings[$index][] = ze\admin::phrase('Form page "[[page]]" property "[[name]]" with value "[[value]]" cannot be imported.', ['page' => $page['name'], 'name' => $fieldName, 'value' => $page[$fieldName]]);
						unset($page[$fieldName]);
					}	
				}
			}
			unset($page);
		}
		unset($data);
		
		//If only validating return any errors and warnings.
		if ($validate) {
			return ['errors' => $errors, 'warnings' => $warnings];
		//Otherwise if there were errors, don't import.
		} elseif (!empty($errors)) {
			return false;
		}
		
		//Check if CRM form integration module is running
		$formCRMIntegrationModuleRunning = ze\module::inc('zenario_crm_form_integration');
		
		//Start import
		$firstNewFormId = false;
		$pageIdLink = [];
		$fieldIdLink = [];
		$valueIdLink = [];
		foreach ($formJSON['forms'] as $index => &$data) {
			//Create forms
			$form = $data['form'];
			$oldFormId = $form['id'];
			unset($form['id']);
			$newFormId = ze\row::insert(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $form);
			
			if (!$firstNewFormId) {
				$firstNewFormId = $newFormId;
			}
			
			//Create pages
			foreach ($data['pages'] as &$page) {
				$oldPageId = $page['id'];
				unset($page['id']);
				$page['form_id'] = $newFormId;
				$newPageId = ze\row::insert(ZENARIO_USER_FORMS_PREFIX . 'pages', $page);
				$pageIdLink[$oldPageId] = $newPageId;
			}
			unset($page);
			
			//Create fields
			foreach ($data['fields'] as &$field) {
				$oldFieldId = $field['id'];
				if (isset($invalidFields[$oldFieldId])) {
					continue;
				}
				unset($field['id']);
				$field['user_form_id'] = $newFormId;
				$field['page_id'] = $pageIdLink[$field['page_id']];
				$newFieldId = ze\row::insert(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', $field);
				$fieldIdLink[$oldFieldId] = $newFieldId;
				
				if ($formCRMIntegrationModuleRunning) {
					if (!empty($data['crm_fields'])) {
						$data['crm_fields'][$oldFieldId]['form_field_id'] = $newFieldId;
						ze\row::insert(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields', $data['crm_fields'][$oldFieldId]);
					}
					
					if (!empty($data['crm_field_values'][$oldFieldId])) {
						foreach ($data['crm_field_values'][$oldFieldId] as $crmField) {
							$crmField['form_field_id'] = $newFieldId;
							unset($crmField['id']);
							ze\row::insert(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_field_values', $crmField);
						}
					}
				}
			}
			unset($field);
			
			//Create values
			foreach ($data['values'] as &$value) {
				$oldValueId = $value['id'];
				unset($value['id']);
				$value['form_field_id'] = $fieldIdLink[$value['form_field_id']];
				$newValueId = ze\row::insert(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', $value);
				$valueIdLink[$oldValueId] = $newValueId;
			}
			unset($value);
			
			//Update form saved Ids
			$update = [];
			foreach ($formFieldIdProperties as $fieldName) {
				if ($form[$fieldName]) {
					$update[$fieldName] = $fieldIdLink[$form[$fieldName]];
				}
			}
			if ($update) {
				ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $update, $newFormId);
			}
			
			//Update page saved Ids
			foreach ($pageIdLink as $oldPageId => $newPageId) {
				$page = $data['pages'][$oldPageId];
				$update = [];
				foreach ($pageFieldIdProperties as $fieldName) {
					if ($page[$fieldName]) {
						$update[$fieldName] = $fieldIdLink[$page[$fieldName]];
					}
				}
				if ($page['visible_condition_field_id'] && $page['visible_condition_field_value']) {
					switch ($data['fields'][$page['visible_condition_field_id']]['field_type']) {
						case 'select':
						case 'radios':
							$update['visible_condition_field_value'] = $valueIdLink[$page['visible_condition_field_value']];
							break;
						case 'checkboxes':
							$newValueIds = [];
							$oldValueIds = explode(',', $page['visible_condition_field_value']);
							foreach ($oldValueIds as $oldValueId) {
								$newValueIds[] = $valueIdLink[$oldValueId];
							}
							$update['visible_condition_field_value'] = implode(',', $newValueIds);
							break;
					}
				}
				if ($update) {
					ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'pages', $update, $newPageId);
				}
			}
			
			//Update field saved Ids
			foreach ($fieldIdLink as $oldFieldId => $newFieldId) {
				$field = $data['fields'][$oldFieldId];
				$update = [];
				foreach ($fieldFieldIdProperties as $fieldName) {
					if ($field[$fieldName]) {
						$update[$fieldName] = $fieldIdLink[$field[$fieldName]];
					}
				}
				// To fix bug when importing the form, the checkboxes that were selected are no longer selected.
				if ($field['visible_condition_field_id'] && $field['visible_condition_field_value']) {
					if (strpos($field['visible_condition_field_value'], ',') !== false) {
						$newValueIds = [];
							$oldValueIds = explode(',', $field['visible_condition_field_value']);
							foreach ($oldValueIds as $oldValueId) {
								$newValueIds[] = $valueIdLink[$oldValueId];
							}
							
							$update['visible_condition_field_value'] = implode(',', $newValueIds);
					}
					else
					{
						$update['visible_condition_field_value'] = $valueIdLink[$field['visible_condition_field_value']];
					}
				}
				if ($field['mandatory_condition_field_id'] && $field['mandatory_condition_field_value']) {
					switch ($data['fields'][$field['mandatory_condition_field_id']]['field_type']) {
						case 'select':
						case 'radios':
							$update['mandatory_condition_field_value'] = $valueIdLink[$field['mandatory_condition_field_value']];
							break;
						case 'checkboxes':
							$newValueIds = [];
							$oldValueIds = explode(',', $field['mandatory_condition_field_value']);
							foreach ($oldValueIds as $oldValueId) {
								$newValueIds[] = $valueIdLink[$oldValueId];
							}
							$update['mandatory_condition_field_value'] = implode(',', $newValueIds);
							break;
							
					}
				}
				if ($field['default_value']) {
					switch ($field['field_type']) {
						case 'select':
						case 'radios':
							$update['default_value'] = $valueIdLink[$field['default_value']];
							break;
						case 'checkboxes':
							$newValueIds = [];
							$oldValueIds = explode(',', $field['default_value']);
							foreach ($oldValueIds as $oldValueId) {
								$newValueIds[] = $valueIdLink[$oldValueId];
							}
							$update['default_value'] = implode(',', $newValueIds);
							break;
					}
				}
				if ($field['calculation_code']) {
					$calculationCode = json_decode($field['calculation_code'], true);
					foreach ($calculationCode as &$step) {
						if ($step['type'] == 'field' && $step['value']) {
							$step['value'] = $fieldIdLink[$step['value']];
						}
					}
					unset($step);
					$update['calculation_code'] = json_encode($calculationCode);
				}
				
				if ($update) {
					ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', $update, $newFieldId);
				}
			}
			
			if ($formCRMIntegrationModuleRunning) {
				if (!empty($data['crm_link'])) {
					foreach ($data['crm_link'] as $crmLink) {
						$crmLink['form_id'] = $newFormId;
						$oldCrmLinkId = $crmLink['id'];
						unset($crmLink['id']);
						$newCrmLinkId = ze\row::insert(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', $crmLink);
						
						if (!empty($data['static_crm_values'])) {
							$staticCrmRows = $data['static_crm_values'][$oldCrmLinkId];
							if (!empty($staticCrmRows)) {
								foreach ($staticCrmRows as $staticCrmRow) {
									$staticCrmRow['link_id'] = $newCrmLinkId;
									ze\row::insert(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'static_crm_values', $staticCrmRow);
								}
							}
						}
					}
					
					if (!empty($data['salesforce_data'])) {
						$data['salesforce_data']['form_id'] = $newFormId;
						ze\row::insert(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'salesforce_data', $data['salesforce_data']);
					}
				
					if (!empty($data['mailchimp_data'])) {
						$data['mailchimp_data']['form_id'] = $newFormId;
						ze\row::insert(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'mailchimp_data', $data['mailchimp_data']);
					}
				
					if (!empty($data['360lifecycle_data'])) {
						$data['360lifecycle_data']['form_id'] = $newFormId;
						ze\row::insert(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . '360lifecycle_data', $data['360lifecycle_data']);
					}
				}
			}
		}
		unset($data);
		return $firstNewFormId;
	}
	
	protected function getPredefinedTextFormFields($formId, $targets, $triggers, $fieldId = false) {
		$fields = [];
		$sql = '
			SELECT 
				uff.id, 
				uff.name, 
				p.id AS page_id, 
				p.name AS page_name,
				IFNULL(cdf.type, uff.field_type) AS type
			FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
			INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'pages p
				ON uff.page_id = p.id
			LEFT JOIN ' . DB_PREFIX . 'custom_dataset_fields AS cdf
				ON uff.user_field_id = cdf.id
			WHERE TRUE';
		if ($fieldId) {
			$sql .= '
				AND uff.id = ' . (int)$fieldId;
		} elseif ($targets || $triggers) {
			$sql .= '
				AND uff.user_form_id = ' . (int)$formId;
			if ($targets) {
				$sql .= '
					AND (uff.field_type = "textarea" || cdf.type = "textarea")';
			} else {
				$sql .= '
					AND (uff.field_type IN ("checkbox", "checkboxes") || cdf.type IN ("group", "checkbox", "checkboxes"))';
			}
		}
		
		$sql .= '
			ORDER BY p.ord, uff.ord';
		
		$result = ze\sql::select($sql);
		if ($fieldId) {
			return ze\sql::fetchAssoc($result);
		}
		while ($row = ze\sql::fetchAssoc($result)) {
			$fields[] = $row;
		}
		return $fields;
	}
	
	public static function clearOldData() {
		$cleared = 0;
		
		//Delete form response headers
		
		//Clear form responses for forms with individual settings
		$sql = '
			SELECT id, period_to_delete_response_headers
			FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms
			WHERE period_to_delete_response_headers != ""';
		$formsResult = ze\sql::select($sql);
		while ($form = ze\sql::fetchAssoc($formsResult)) {
			$days = $form['period_to_delete_response_headers'];
			
			if (is_numeric($days)) {
				$sql = '
					SELECT id
					FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response
					WHERE form_id = ' . (int)$form['id'];
				
				if ($days && ($date = date('Y-m-d', strtotime('-'.$days.' day', strtotime(date('Y-m-d')))))) {
					$sql .= '
						AND response_datetime < "' . ze\escape::sql($date) . '"';
				}
			
				$result = ze\sql::select($sql);
				while ($row = ze\sql::fetchAssoc($result)) {
					static::deleteFormResponse($row['id']);
				}
			}
		}
		
		//Clear form responses for the rest
		$days = ze::setting('period_to_delete_the_form_response_log_headers');
		if (is_numeric($days)) {
			$sql = '
				SELECT ur.id
				FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response ur
				INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms uf
					ON ur.form_id = uf.id
				WHERE uf.period_to_delete_response_headers = ""';
			
			if ($days && ($date = date('Y-m-d', strtotime('-'.$days.' day', strtotime(date('Y-m-d')))))) {
				$sql .= '
					AND response_datetime < "' . ze\escape::sql($date) . '"';
			}
			
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
				static::deleteFormResponse($row['id']);
			}
			$cleared += ze\sql::numRows($result);
		}
		
		
		//Delete form response content
		
		//Clear form responses for forms with individual settings
		$sql = '
			SELECT id, period_to_delete_response_content
			FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms
			WHERE period_to_delete_response_content != ""';
		$formsResult = ze\sql::select($sql);
		while ($form = ze\sql::fetchAssoc($formsResult)) {
			$days = $form['period_to_delete_response_content'];
			
			if (is_numeric($days)) {
				$date = date('Y-m-d', strtotime('-'.$days.' day', strtotime(date('Y-m-d'))));
				$sql = '
					DELETE urd.*
					FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response_data urd
					INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response ur
						ON urd.user_response_id = ur.id
					WHERE ur.form_id = ' . (int)$form['id'];
				if ($days && $date) {
					$sql .= '
						AND ur.response_datetime < "' . ze\escape::sql($date) . '"';
				}
				ze\sql::update($sql);
				
				$sql = '
					UPDATE ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response
					SET data_deleted = 1
					WHERE form_id = ' . (int)$form['id'];
				if ($days && $date) {
					$sql .= '
						AND response_datetime < "' . ze\escape::sql($date) . '"';
				}
				ze\sql::update($sql);
				$cleared += ze\sql::affectedRows();
			}
		}
		
		//Clear form responses for the rest
		$days = ze::setting('period_to_delete_the_form_response_log_content');
		if (is_numeric($days)) {
			$date = date('Y-m-d', strtotime('-'.$days.' day', strtotime(date('Y-m-d'))));
			
			$sql = '
				DELETE urd.*
				FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response_data urd
				INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response ur
					ON urd.user_response_id = ur.id
				INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms uf
					ON ur.form_id = uf.id
				WHERE uf.period_to_delete_response_content = ""';
			if ($days && $date) {
				$sql .= '
					AND ur.response_datetime < "' . ze\escape::sql($date) . '"';
			}
			ze\sql::update($sql);
			
			$sql = '
				UPDATE ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response ur
				INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms uf
					ON ur.form_id = uf.id
				SET ur.data_deleted = 1
				WHERE uf.period_to_delete_response_content = ""';
			if ($days && $date) {
				$sql .= '
					AND ur.response_datetime < "' . ze\escape::sql($date) . '"';
			}
			ze\sql::update($sql);

			$cleared += ze\sql::affectedRows();
		}
		
		return $cleared;
	}
	
	public static function getFormTypeEnglish($type) {
		switch ($type) {
			case 'standard':
			default:
				return 'Standard Form';
			case 'profile':
				return 'User Profile Form';
			case 'registration':
				return 'Registration Form';
		}
	}
	
	public static function eventUserDeleted($userId, $deleteAllData) {
		//When deleting all data about a user, delete the content of their form respones but keep the header
		if ($deleteAllData) {
			$sql = '
				DELETE urd.*
				FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response_data urd
				INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response ur
					ON urd.user_response_id = ur.id
				WHERE ur.user_id = ' . (int)$userId;
			ze\sql::update($sql);
			
			$sql = '
				UPDATE ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response
				SET user_id = 0, user_deleted = 1, data_deleted = 1
				WHERE user_id = ' . (int)$userId;
			ze\sql::update($sql);
		}
	}
	
}

class calculator {
    const PATTERN = '/(?:\-?\d+(?:\.?\d+)?[\+\-\*\/])+\-?\d+(?:\.?\d+)?/';

    const PARENTHESIS_DEPTH = 10;

    public function calculate($input){
    	
    	set_error_handler(function($errno, $errstr, $errfile, $errline) {
    		throw new \Exception($errstr);
    	});
    	
    	$result = $input;
        if(strpos($input, '+') != null || strpos($input, '-') != null || strpos($input, '/') != null || strpos($input, '*') != null){
            // Remove white spaces and invalid math chars
            $input = str_replace(',', '.', $input);
            $input = preg_replace('[^0-9\.\+\-\*\/\(\)]', '', $input);
            // Calculate each of the parenthesis from the top
            $i = 0;
            while(strpos($input, '(') || strpos($input, ')')){
                $input = preg_replace_callback('/\(([^\(\)]+)\)/', 'self::callback', $input);

                $i++;
                if($i > self::PARENTHESIS_DEPTH){
                    break;
                }
            }
            // Calculate the result
            if(preg_match(self::PATTERN, $input, $match)){
                $result = $this->compute($match[0]);
            } else {
            	$result = 0;
            }
        }
        
        restore_error_handler();
        
        return $result;
    }

    private function compute($input){
        $compute = create_function('', 'return '.$input.';');
        return 0 + $compute();
    }

    private function callback($input){
        if(is_numeric($input[1])){
            return $input[1];
        }
        elseif(preg_match(self::PATTERN, $input[1], $match)){
            return $this->compute($match[0]);
        }
        return 0;
    }
}

