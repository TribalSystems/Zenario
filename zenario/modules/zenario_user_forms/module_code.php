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

class zenario_user_forms extends module_base_class {
	
	protected $data = false;
	protected $inFullScreen = false;
	protected $dataset = false;
	protected $form = false;
	protected $pages = array();
	protected $fields = array();
	protected $errors = array();
	protected $datasetFieldsLink = array();
	protected $datasetFieldsColumnLink = array();
	
	
	public function init() {
		requireJsLib('libraries/mit/jquery/jquery-ui.datepicker.min.js');
		requireJsLib('libraries/mit/jquery/jquery-ui.sortable.min.js');
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = false, $ifCookieSet = false);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = true, $clearByFile = false, $clearByModuleData = true);
		
		$formId = $this->setting('user_form');
		//This plugin must have a form selected
		if (!$formId) {
			if (adminId()) {
				$this->data['form_HTML'] = '<p class="error">' . adminPhrase('You must select a form for this plugin.') . '</p>';
			}
			return true;
		}
		
		$userId = userId();
		$this->dataset = getDatasetDetails('users');
		$this->form = static::getForm($formId);
		$t = $this->form['translate_text'];
		
		//Email verification for registration forms
		if ($this->form['type'] == 'registration') {
			if (isset($_GET['confirm_email']) && isset($_GET['hash'])) {
				$user = getRow('users', array('id', 'email_verified'), array('hash' => $_GET['hash']));
				if (!$user) {
					$this->data['form_HTML'] = '<p class="error">' . static::fPhrase('We are sorry, but we were unable to find your registration. Please check whether the verification link is correct.', array(), $t) . '</p>';
				} elseif ($user['email_verified']) {
					$this->data['form_HTML'] = '<p class="success">' . static::fPhrase('This email address has already been verified.', array(), $t) . '</p>';
				} else {
					updateRow('users', array('email_verified' => 1, 'status' => 'active'), $user['id']);
					$this->sendWelcomeEmail($user['id']);
					$this->logUserIn($user['id']);
					
					if ($this->form['welcome_redirect_location']) {
						$cID = $cType = false;
						getCIDAndCTypeFromTagId($cID, $cType, $this->form['welcome_redirect_location']);
						langEquivalentItem($cID, $cType);
						$redirectURL = linkToItem($cID, $cType);
						$this->headerRedirect($redirectURL);
						return true;
					} else {
						$this->data['form_HTML'] = '<p class="success">' . static::fPhrase($this->form['welcome_message'], array(), $t) . '</p>';
					}
				}
				return true;
			}
			if ($userId) {
				$this->data['form_HTML'] = $this->getWelcomePageHTML();
				return true;
			}
		}
		
		//Partial completion forms must be placed on a private page and have a user logged in
		if ($this->form['allow_partial_completion']) {
			$equivId = equivId($this->cID, $this->cType);
			$privacy = getRow('translation_chains', 'privacy', array('equiv_id' => $equivId, 'type' => $this->cType));
			if ($privacy == 'public') {
				if (adminId()) {
					$this->data['form_HTML'] = '<p class="error">' . adminPhrase('This form has the "save and complete later" feature enabled and so must be placed on a password-protected page.') . '</p>';
				}
				return true;
			}
			if (!$userId) {
				if (adminId()) {
					$this->data['form_HTML'] = '<p class="error">' . adminPhrase('You must be logged in as an Extranet User to see this Plugin.') . '</p>';
				}
				return true;
			}
		}
		
		if (($_GET['method_call'] ?? false) == 'handlePluginAJAX') {
			return true;
		}
		
		$reloaded = ($_POST['reloaded'] ?? false) && ($this->instanceId == ($_POST['instanceId'] ?? false));
		$reloadedWithAjax = $reloaded && (($_GET['method_call'] ?? false) == 'refreshPlugin');
		
		//Decide whether to display plugin contents in a modal window
		$showInFloatingBox = false;
		$loadContentInColorbox = false;
		if ($this->setting('display_mode') == 'in_modal_window') {
			if ($reloadedWithAjax || ($_GET['showInFloatingBox'] ?? false)) {
				$showInFloatingBox = true;
				$floatingBoxParams = array(
					'escKey' => false, 
					'overlayClose' => false, 
					'closeConfirmMessage' => static::fPhrase('Are you sure you want to close this window? You will lose any changes.', array(), $t),
				);
				$this->showInFloatingBox(true, $floatingBoxParams);
			//If colorbox was forced to close e.g. there was a file input field on the form, reopen it in JS.
			} elseif ($reloaded && !$reloadedWithAjax) {
				$loadContentInColorbox = true;
			} else {
				$this->data['form_HTML'] = $this->getModalWindowButtonHTML();
				return true;
			}
		}
		
		//Logged in user duplicate submission check
		if ($userId && $this->form['no_duplicate_submissions']) {
			if (checkRowExists(ZENARIO_USER_FORMS_PREFIX . 'user_response', array('user_id' => $userId, 'form_id' => $formId))) {
				$this->data['form_HTML'] = '<p class="info">' . static::fPhrase($this->form['duplicate_submission_message'], array(), $t) . '</p>';
				return true;
			}
		}
		
		if (!$reloaded) {
			//Delete any session data saved for this form
			unset($_SESSION['custom_form_data'][$this->instanceId]);
			
			//Check if there is a part completed submission for this user to load initially
			if ($this->form['allow_partial_completion']) {
				$partialSaveFound = checkRowExists(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response', array('user_id' => $userId, 'form_id' => $formId));
				if ($partialSaveFound) {
					if (!$this->form['allow_clear_partial_data'] || ($_POST['resume'] ?? false)) {
						$data = false;
						$this->loadPartialSaveData($userId, $formId);
					} elseif ($this->form['allow_clear_partial_data'] && ($_POST['clear'] ?? false)) {
						static::deleteOldPartialResponse($formId, $userId);
					} else {
						$this->data['form_HTML'] = $this->getPartialSaveResumeFormHTML();
						return true;
					}
				}
			}
		}
		//Get page
		$this->pages = static::getFormPages($formId);
		if ($this->form['enable_summary_page']) {
			$summaryPageId = 'summary';
			$this->pages[$summaryPageId] = array(
				'id' => $summaryPageId,
				'form_id' => $formId,
				'ord' => end($this->pages)['ord'] + 100,
				'name' => $summaryPageId,
				'label' => $summaryPageId,
				'visibility' => 'visible',
				'previous_button_text' => static::fPhrase('Previous', array(), $t),
				'hide_in_page_switcher' => true,
				'fields' => array()
			);
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
		if ($reloaded) {
			$this->savePageData($currentPageId, $_POST);
		}
		
		//Load current values for each field
		foreach ($this->fields as $fieldId => $field) {
			$this->fields[$fieldId]['value'] = $this->getFieldCurrentValue($fieldId);
		}
		
		//Load form data
		$this->preloadCustomData();
		$formFinalSubmitSuccessfull = false;
		if ($reloaded) {
			//Change page
			$pageId = $this->getNextFormPage($currentPageId);
			if (!isset($_POST['filter'])) {
				$submitted = !empty($_POST['submitForm']);
				$moveToHigherPage = $this->pages[$pageId]['ord'] > $this->pages[$currentPageId]['ord'];
				$saveToCompleteLaterButtonPressed = $this->form['allow_partial_completion'] && !empty($_POST['saveLater']);
				$saveToCompleteLaterPageNav = $this->form['allow_partial_completion'] && in_array($this->form['partial_completion_mode'], array('auto', 'auto_and_button')) && $currentPageId != $pageId;
				
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
							$successMessage = static::fPhrase($this->form['partial_completion_message'], array(), $t);
						} elseif (adminId()) {
							$successMessage = adminPhrase('Your partial completion message will go here when you set it.');
						} else {
							$successMessage = 'Your data will be here the next time you open this form.';
						}
						$this->data['form_HTML'] = '<div class="success">' . $successMessage . '</div>';
						return true;
					}
				} elseif ($submitted) {
					$this->saveForm();
					$this->successfulFormSubmit();
					$formFinalSubmitSuccessfull = true;
					
					unset($_SESSION['captcha_passed__' . $this->instanceId]);
					
					//After submitting form show a success message
					if ($this->form['show_success_message']) {
						$this->data['form_HTML'] = $this->getSuccessMessageHTML();
						return true;
					//Or redirect to another page
					} elseif ($this->form['redirect_after_submission'] && $this->form['redirect_location']) {
						$cID = $cType = false;
						getCIDAndCTypeFromTagId($cID, $cType, $this->form['redirect_location']);
						langEquivalentItem($cID, $cType);
						$redirectURL = linkToItem($cID, $cType);
						$this->headerRedirect($redirectURL);
						return true;
					}
					//Or stay on the form
					$pageId = reset($this->pages)['id'];
					unset($_SESSION['custom_form_data'][$this->instanceId]);
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
		if ($loadContentInColorbox) {
			$colorboxFormHTML = $html;
			$this->data['form_HTML'] = $this->getModalWindowButtonHTML();
		} else {
			$this->data['form_HTML'] = $html;
		}
		
		
		$this->data['form_HTML'] = $html;
		
		
		//Init form JS
		$allowProgressBarNavigation = $this->form['show_page_switcher'] && ($this->form['page_switcher_navigation'] == 'only_visited_pages');
		$isErrors = (bool)$this->errors;
		$maxPageReached = $_SESSION['custom_form_data'][$this->instanceId]['max_page_reached'] ?? false;
		
		$extraPhrases = array(
			'delete' => static::fPhrase('Delete', array(), $t),
			'delete_file' => static::fPhrase('Are you sure you want to delete this file?', array(), $t),
			'are_you_sure_message' => static::fPhrase('Are you sure? Any unsaved changes will be lost.', array(), $t),
			'combine' => static::fPhrase('Combine', array(), $t),
			'combining' => static::fPhrase('Combining...', array(), $t)
		);
		
		$this->callScript('zenario_user_forms', 'initForm', $this->containerId, $this->slotName, $this->pluginAJAXLink(), $colorboxFormHTML, $formFinalSubmitSuccessfull, $this->inFullScreen, $allowProgressBarNavigation, $pageId, $maxPageReached, $showLeavingPageMessage = true, $isErrors, json_encode($extraPhrases));
		return true;
	}
	
	public function showSlot() {
		$this->twigFramework($this->data);
	}
	
	public function handlePluginAJAX() {
		if (isset($_GET['fileUpload'])) {
			$data = array('files' => array());
			foreach ($_FILES as $fieldName => $file) {
				if ($file && !empty($file['tmp_name'])) {
					//Handle single and multiple file inputs
					if (!is_array($file['tmp_name'])) {
						$file['tmp_name'] = array($file['tmp_name']);
						$file['name'] = array($file['name']);
					}
					//Upload each file and return its name and filepath
					$fileCount = count($file['tmp_name']);
					for ($j = 0; $j < $fileCount; $j++) {
						if (!empty($file['tmp_name'][$j]) && is_uploaded_file($file['tmp_name'][$j]) && cleanCacheDir()) {
							$randomDir = createRandomDir(30, 'uploads');
							$newName = $randomDir. Ze\File::safeName($file['name'][$j], true);
							
							if (!$randomDir) {
								exit('Could not create cache directory in private/uploads');
							}
							
							if (move_uploaded_file($file['tmp_name'][$j], CMS_ROOT. $newName)) {
								$cacheFile = array('name' => urldecode($file['name'][$j]), 'path' => $newName);
								
								//If requested, make thumbnails
								if ($_GET['thumbnail'] ?? false) {
									$imageString = file_get_contents($newName);
									$imageMimeType = Ze\File::mimeType($newName);
									$imageSize = getimagesize($newName);
									$imageWidth = $cropWidth = $imageSize[0];
									$imageHeight = $cropHeight = $imageSize[1];
									$widthLimit = $newWidth = $cropNewWidth = 64;
									$heightLimit = $newHeight = $cropNewHeight = 64;
									
									$mode = 'resize';
									resizeImageByMode(
										$mode, $imageWidth, $imageHeight,
										$widthLimit, $heightLimit,
										$newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight,
										$imageMimeType
									);
									
									resizeImageStringToSize($imageString, $imageMimeType, $imageWidth, $imageHeight, $newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight);
									
									$privateCacheDir = createRandomDir(15, 'private/images');
									$thumbnailPath = $privateCacheDir . 'thumbnail-' . $file['name'][$j];
									file_put_contents(CMS_ROOT . $thumbnailPath, $imageString);
									@chmod(CMS_ROOT . $thumbnailPath, 0666);
									
									$cacheFile['thumbnail_path'] = $thumbnailPath;
								}
								
								$data['files'][] = $cacheFile;
								@chmod(CMS_ROOT. $newName, 0666);
							}
						}
					}
				}
			}
			echo json_encode($data);
		} elseif (isset($_GET['combineFiles'])) {
			$data = array();
			if ($filesJSON = $_POST['files'] ?? false) {
				$files = json_decode($filesJSON, true);
				
				//To do..
				//programPathForExec?
				
				//Step 1: convert all images into pdfs
				$pdfs = array();
				foreach ($files as $file) {
					//Validation, only accept jpg, png, gif
					$mimeType = Ze\File::mimeType($file['path']);
					if (in_array($mimeType, array('image/jpeg', 'image/png', 'image/gif'))) {
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
				
				$fullPDFDir = createRandomDir(30, 'uploads');
				
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
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->preFillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->fillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId);
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->organizerPanelDownload($path, $ids, $refinerName, $refinerId);
		}
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->fillAdminBox($path, $settingGroup, $box, $fields, $values);
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->validateAdminBox($path, $settingGroup, $box, $fields, $values, $changes, $saving);
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->saveAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	
	
	
	
	
	public static function getForm($formId) {
		return getRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', true, $formId);
	}
	
	public static function getFormPages($formId) {
		$pages = array();
		$result = getRows(ZENARIO_USER_FORMS_PREFIX . 'pages', true, array('form_id' => $formId), 'ord');
		while ($page = sqlFetchAssoc($result)) {
			$page['fields'] = array();
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
							$condition['current_value'] = array();
						}
						$values = $condition['value'] ? explode(',', $condition['value']) : array();
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
				if ($condition['invert']) {
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
		$conditionList = array();
		while (!empty($field['visible_condition_field_id'])) {
			$conditionFieldId = $field['visible_condition_field_id'];
			if ($conditionFieldId) {
				$conditionField = $fields[$conditionFieldId];
				$condition = array(
					'id' => $conditionFieldId, 
					'value' => $field['visible_condition_field_value'], 
					'invert' => (int)$field['visible_condition_invert'],
					'type' => $conditionField['type'],
					'current_value' => $conditionField['value'] ?? false
				);
				if ($conditionField['type'] == 'checkboxes') {
					$condition['operator'] = $field['visible_condition_checkboxes_operator'];
				}
				$conditionList[] = $condition;
				$field = $conditionField;
			}
		}
		$conditionList = array_reverse($conditionList);
		return $conditionList;
	}
	
	
	
	public static function deleteOldPartialResponse($formId = false, $userId = false) {
		if ($formId || $userId) {
			$keys = array();
			if ($formId) {
				$keys['form_id'] = $formId;
			}
			if ($userId) {
				$keys['user_id'] = $userId;
			}
			$result = getRows(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response', array('id'), $keys);
			while ($response = sqlFetchAssoc($result)) {
				deleteRow(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response', array('id' => $response['id']));
				deleteRow(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response_data', array('user_partial_response_id' => $response['id']));
			}
		} else {
			deleteRow(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response', array());
			deleteRow(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response_data', array());
		}
	}
	
	public static function getFormSummaryHTML($responseId, $formId = false, $data = false, $repeatRows = array()) {
		$html = '<table>';
		
		//Get form if loading from a responseId
		if ($responseId) {
			$response = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_response', array('form_id'), $responseId);
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
			if ($field['type'] == 'repeat_start' || $field['type'] == 'repeat_end' || $field['type'] == 'section_description') {
				continue;
			}
			
			//Show page as header if visible
			if (!$currentPageId || $currentPageId != $field['page_id']) {
				$currentPageId = $field['page_id'];
				$page = $pages[$field['page_id']];
				$pageIsHidden = static::isPageHiddenStatic($page, $fields);
				if (!$pageIsHidden && !$page['hide_in_page_switcher']) {
					$html .= '<tr><th colspan="2" class="header">' . htmlspecialchars($pages[$currentPageId]['name']) . '</th></tr>';
				}
			}
			
			//Show field if not hidden
			if (!$pageIsHidden && !static::isFieldHiddenStatic($field, $fields)) {
				$display = '';
				if (isset($field['value'])) {
					$display = static::getFieldDisplayValue($field, $field['value'], true);
				}
				$label = $field['label'];
				if (isset($field['row'])) {
					$label .= ' (' . $field['row'] . ')';
				}
				$html .= '<tr><td>' . htmlspecialchars($label) . '</td><td>' . $display . '</td></tr>';
			}
		}
		$html .= '</table>';
		return $html;
	}
	
	public static function deleteFormResponse($responseId) {
		sendSignal('eventFormResponseDeleted', array($responseId));
		deleteRow(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', array('user_response_id' => $responseId));
		deleteRow(ZENARIO_USER_FORMS_PREFIX . 'user_response', $responseId);
	}
	
	public static function deleteFormField($fieldId, $updateOrdinals = true, $formExists = true) {
		$error = new zenario_error();
		$formField = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', true, $fieldId);
		
		//Send signal that the form field is now deleted (sent before actual delete in case modules need to look at any metadata or field values)
		sendSignal('eventFormFieldDeleted', array($fieldId));
		
		//Delete form field
		deleteRow(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', $fieldId);
		
		//Update remaining field ordinals
		if ($updateOrdinals && !empty($formField)) {
			$result = getRows(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', array('id'), array('user_form_id' => $formField['user_form_id']), 'ord');
			$ord = 0;
			while ($row = sqlFetchAssoc($result)) {
				updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', array('ord' => ++$ord), $row['id']);
			}
		}
		
		//Delete any field values
		deleteRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', array('form_field_id' => $fieldId));
		
		//Delete any response data
		deleteRow(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', array('form_field_id' => $fieldId));
		return true;
	}
	
	public static function deleteFormPage($pageId) {
		deleteRow(ZENARIO_USER_FORMS_PREFIX . 'pages', $pageId);
	}
	
	public static function deleteForm($formId) {
		$error = new zenario_error();
		
		//Get form details
		$formDetails = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('name'), $formId);
		if ($formDetails === false) {
			$error->add(adminPhrase('Error. Form with ID "[[id]]" does not exist.', array('id' => $formId)));
			return $error;
		}
		
		//Don't delete forms used in plugins
		$moduleIds = static::getFormModuleIds();
		$instanceIds = array();
		$sql = '
			SELECT id
			FROM '.DB_NAME_PREFIX.'plugin_instances
			WHERE module_id IN ('. inEscape($moduleIds, 'numeric'). ')
			ORDER BY name';
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			$instanceIds[] = $row['id'];
		}
		$sql = "
            SELECT pi.id, pi.name, np.id AS egg_id
            FROM ". DB_NAME_PREFIX. "nested_plugins AS np
            INNER JOIN ". DB_NAME_PREFIX. "plugin_instances AS pi
               ON pi.id = np.instance_id
            WHERE np.module_id IN (". inEscape($moduleIds, 'numeric'). ")
            ORDER BY pi.name";
        $result = sqlSelect($sql);
        while ($row = sqlFetchAssoc($result)) {
            $instanceIds[] = $row['id'];
        }
		
		foreach ($instanceIds as $instanceId) {
			if (checkRowExists('plugin_settings', array('instance_id' => $instanceId, 'name' => 'user_form', 'value' => $formId))) {
				$error->add(adminPhrase('Error. Unable to delete form "[[name]]" as it is used in a plugin.', $formDetails));
				return $error;
			}
		}
		
		//Don't delete forms with logged responses
		if (checkRowExists(ZENARIO_USER_FORMS_PREFIX.'user_response', array('form_id' => $formId))) {
			$error->add(adminPhrase('Error. Unable to delete form "[[name]]" as it has logged user responses.', $formDetails));
			return $error;
		}
		
		//Send signal that the form is now deleted (sent before actual delete in case modules need to look at any metadata or form fields)
		sendSignal('eventFormDeleted', array($formId));
		
		//Delete form
		deleteRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $formId);
		
		//Delete form fields
		$result = getRows(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', array('id'), array('user_form_id' => $formId));
		while ($row = sqlFetchAssoc($result)) {
			static::deleteFormField($row['id'], false, false);
		}
		
		//Delete form pages
		$result = getRows(ZENARIO_USER_FORMS_PREFIX . 'pages', array('id'), array('form_id' => $formId));
		while ($row = sqlFetchAssoc($result)) {
			static::deleteFormPage($row['id']);
		}
		
		//Delete responses
		deleteRow(ZENARIO_USER_FORMS_PREFIX . 'user_response', array('form_id' => $formId));
		
		return true;
	}
	
	public static function deleteFormFieldValue($valueId) {
		deleteRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', array('id' => $valueId));
		sendSignal('eventFormFieldValueDeleted', array($valueId));
	}
	
	private function getFormRepeatRows($formId) {
		$repeatRows = array();
		$sql = '
			SELECT f.id, f.min_rows, f.max_rows, d.id AS dataset_field_id, IFNULL(f.field_type, d.type) AS type
			FROM ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields f
			LEFT JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields d
				ON f.user_field_id = d.id
			WHERE f.user_form_id = ' . (int)$formId . '
			AND (f.field_type = "repeat_start" OR d.type = "repeat_start")';
		$result = sqlSelect($sql);
		while ($field = sqlFetchAssoc($result)) {
			$repeatRows[$field['id']] = $this->loadRepeatRows($field);
		}
		return $repeatRows;
	}
	
	public static function getFormRepeatRowsFromSource($responseId = false, $partialResponseId = false) {
		$repeatRows = array();
		if ($responseId || $partialResponseId) {
			if ($responseId) {
				$sql = '
					SELECT d.form_field_id, d.field_row, d.value, f.field_type, c.type
					FROM ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response_data d
					INNER JOIN ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields f
						ON d.form_field_id = f.id
					LEFT JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields c
						ON f.user_field_id = c.id
					WHERE d.user_response_id = ' . (int)$responseId;
			} elseif ($partialResponseId) {
				$sql = '
					SELECT d.form_field_id, d.field_row, d.value, f.field_type, c.type
					FROM ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_partial_response_data d
					INNER JOIN ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields f
						ON d.form_field_id = f.id
					LEFT JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields c
						ON f.user_field_id = c.id
					WHERE d.user_partial_response_id = ' . (int)$partialResponseId;
			}
			$result = sqlSelect($sql);
			while ($row = sqlFetchAssoc($result)) {
				$fieldId = static::getRepeatFieldId($row['form_field_id'], $row['field_row']);
				//Load row counts for repeat start fields
				if ($row['field_type'] == 'repeat_start' || $row['type'] == 'repeat_start') {
					$rows = array();
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
		$data = array();
		if ($responseId) {
			$result = getRows(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', array('form_field_id', 'field_row', 'value'), array('user_response_id' => $responseId));
			while ($row = sqlFetchAssoc($result)) {
				$fieldId = static::getRepeatFieldId($row['form_field_id'], $row['field_row']);
				if (isset($fields[$fieldId])) {
					$fields[$fieldId]['value'] = static::getFieldValueFromStored($fields[$fieldId], $row['value']);
				}	
			}
		} elseif ($partialResponseId) {
			$result = getRows(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response_data', array('form_field_id', 'field_row', 'value'), array('user_partial_response_id' => $partialResponseId));
			while ($row = sqlFetchAssoc($result)) {
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
	
	public static function getFormFieldsStatic($formId, $repeatRows = array(), $loadFromResponseId = false, $loadFromPartialResponseId = false, $fieldId = false, $codeName = false) {
		//Load repeat block rows from an external source if asked
		if ($loadFromResponseId) {
			$repeatRows = static::getFormRepeatRowsFromSource($loadFromResponseId);
		} elseif ($loadFromPartialResponseId) {
			$repeatRows = static::getFormRepeatRowsFromSource(false, $loadFromPartialResponseId);
		}
		
		$fields = array();
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
				uff.placeholder,
				uff.preload_dataset_field_user_data,
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
				uff.custom_code_name,
				uff.autocomplete,
				uff.autocomplete_no_filter_placeholder,
				uff.value_field_columns,
				uff.min_rows,
				uff.max_rows,
				uff.show_month_year_selectors,
				uff.no_past_dates,
				uff.disable_manual_input,
				uff.invalid_field_value_error_message,
				uff.word_count_max,
				uff.word_count_min,
				uff.combined_filename,
				uff.filter_on_field,
				uff.repeat_start_id,
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
				cdf.repeat_start_id AS dataset_repeat_start_id
			FROM ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms AS uf
			INNER JOIN ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields AS uff
				ON uf.id = uff.user_form_id
			INNER JOIN ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'pages p
				ON uff.page_id = p.id
			LEFT JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields AS cdf
				ON uff.user_field_id = cdf.id
			WHERE TRUE';
		if ($formId) {
			$sql .= '
				AND uff.user_form_id = ' . (int)$formId;
			if ($codeName) {
				$sql .= '
					AND uff.custom_code_name = "' . sqlEscape($codeName) . '"';
			}
		} elseif ($fieldId) {
			$sql .= '
				AND uff.id = ' . (int)$fieldId;
		}
		$sql .= '
			ORDER BY p.ord, uff.ord';
		$result = sqlSelect($sql);
		$repeatStartField = false;
		$repeatBlockFields = array();
		while ($field = sqlFetchAssoc($result)) {
			if ($field['field_type']) {
				$field['type'] = $field['field_type'];
			}
			
			if ($field['type'] == 'repeat_start') {
				$repeatBlockFields = array();
				$field['rows'] = $repeatRows[$field['id']] ?? array(1);
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
						$storedFieldIdNames = array('visible_condition_field_id', 'mandatory_condition_field_id', 'restatement_field', 'filter_on_field');
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
		$html .= '<div id="' . $this->containerId . '_form_wrapper" class="form_wrapper ';
		if ($this->inFullScreen) {
			$html .= 'in_fullscreen';
		}
		$html .= '">';
		$html .= $this->getFormTitle();
		
		
		//Buttons at top of form
		$topButtonsHTML = '';
		if ($this->setting('display_mode') == 'inline_in_page') {
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
						$topButtonsHTML .= '<div id="' . $this->containerId . '_print_page" class="print_page">' . static::fPhrase('Print', array(), $t) . '</div>';
						
					}
				}
			}
			//Fullscreen
			if ($this->setting('show_fullscreen_button')) {
				$topButtonsHTML .= '<div id="' . $this->containerId . '_fullscreen" class="fullscreen_button"';
				if ($this->inFullScreen) {
					$topButtonsHTML .= ' style="display:none;"';
				}
				$topButtonsHTML .= '>' . static::fPhrase('Fullscreen', array(), $t) . '</div>';
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
			
			$maxPageReached = $_SESSION['custom_form_data'][$this->instanceId]['max_page_reached'] ?? $pageId;
			
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
				$switcherHTML .= '<li data-page="' . $tPageId . '" ';
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
				$switcherHTML .= 'class="step step_' . ($step++) . ' ' . $extraClasses . '">' . $tPage['name'] . '</li>';
			}
			$switcherHTML .= '</ul></div>';
			if ($hasPageVisibleOnSwitcher) {
				$html .= $switcherHTML;
			}
		}
		
		//Global errors and messages
		if (isset($this->errors['global_top'])) {
			$html .= '<div class="form_error global top">' . static::fPhrase($this->errors['global_top'], array(), $t) . '</div>';
		} elseif (isset($this->messages['global_top'])) {
			$html .= '<div class="success global top">' . static::fPhrase($this->messages['global_top'], array(), $t) . '</div>';
		}
		
		$html .= '<div id="' . $this->containerId . '_user_form" class="user_form">';
		$html .= $this->openForm($onSubmit = '', $extraAttributes = 'enctype="multipart/form-data"', $action = false, $scrollToTopOfSlot = true);
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
		
		$html .= $this->getFieldsHTML($pageId, $this->isFormReadonly($this->form));
		
		$html .= $this->closeForm();
		$html .= '</div></div>';
		return $html;
	}
	
	protected function getFieldsHTML($pageId, $readonly) {
		$html = '';
		$t = $this->form['translate_text'];
		$isMultiPageForm = count($this->pages) > 1;
		if ($isMultiPageForm) {
			$html .= '<fieldset id="' . $this->containerId . '_page_' . $pageId . '" class="page_' . $this->pages[$pageId]['ord'] . '">';
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
			$data = array();
			foreach ($this->fields as $fieldId => $field) {
				$data[$fieldId] = $this->getFieldCurrentValue($fieldId);
			}
			$repeatRows = $this->getFormRepeatRows($this->form['id']);
			$html .= static::getFormSummaryHTML(false, $this->form['id'], $data, $repeatRows);
		} else {
			foreach ($this->pages[$pageId]['fields'] as $i => $fieldId) {
				$field = $this->fields[$fieldId];
				if ($field['type'] == 'repeat_start') {
					$html .= $this->getWrapperDivHTML($field, $wrapDivOpen, $currentDivWrapClass);
					//Repeat start div
					$html .= '<div id="' . $this->containerId . '_repeat_block_' . $fieldId . '" data-id="' . $fieldId . '" class="repeat_block repeat_block_' . $fieldId;
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
					$html .= '<input type="hidden" name="' . static::getFieldName($fieldId) . '" value="' . implode(',', $field['rows']) . '">';
				
					//Repeat start title
					if ($field['label']) {
						$html .= '<div class="field_title">' . static::fPhrase($field['label'], array(), $t) . '</div>';
					}
				
					$html .= '<div class="repeat_rows">';
				
				} elseif ($field['type'] == 'repeat_end') {
					$repeatStartField = $this->fields[$field['repeat_start_id']];
					if (count($repeatStartField['rows']) < $repeatStartField['max_rows']) {
						$html .= '<div class="repeat_block_buttons"><div class="add">' . static::fPhrase('Add +', array(), $t) . '</div></div>';
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
							$html .= '<div class="delete" data-row="' . $field['row'] . '">' . static::fPhrase('Delete', array(), $t) . '</div>';
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
			if ($this->showCaptcha()) {
				$html .= $this->getCaptchaHTML();
			}
			if (!empty($this->form['use_honeypot'])) {
			    $html .= $this->getHoneypotHTML();
			}
		}
		
		$html .= '</div>';
		
		if (isset($this->errors['global_bottom'])) {
			$html .= '<div class="form_error global bottom">' . static::fPhrase($this->errors['global_bottom'], array(), $t) . '</div>';
		}
		
		$html .= '<div class="form_buttons">';
		
		$button = $this->getCustomButtons($pageId, $onLastPage, 'first');
		if ($button) {
			$html .= $button;
		}
		
		//Previous page button
		if ($isMultiPageForm && $this->pages[$pageId]['ord'] > 1) {
			$html .= '<input type="button" value="' . static::fPhrase($this->pages[$pageId]['previous_button_text'], array(), $t) . '" class="previous"/>';
		}
		
		$button = $this->getCustomButtons($pageId, $onLastPage, 'center');
		if ($button) {
			$html .= $button;
		}
		
		//Next page button
		if ($isMultiPageForm && !$onLastPage) {
			$html .= '<input type="button" value="' . static::fPhrase($this->pages[$pageId]['next_button_text'], array(), $t) . '" class="next"/>';
		}
		//Final submit button
		if ($this->showSubmitButton() && $onLastPage) {
			$html .= '<input type="button" class="next submit" value="' . static::fPhrase($this->form['submit_button_text'], array(), $t) . '"/>';
		}
		
		$button = $this->getCustomButtons($pageId, $onLastPage, 'last');
		if ($button) {
			$html .= $button;
		}
		
		if ($this->setting('partial_completion_button_position') == 'bottom') {
			$html .= $this->getPartialSaveButtonHTML();
		}
		
		$html .= '</div>';
		
		if ($isMultiPageForm) {
			$html .= '<input type="hidden" name="current_page" value="' . $pageId . '"/>';
			$html .= '</fieldset>';
		}
		return $html;
	}
	
	private function getPartialSaveButtonHTML() {
		$html = '';
		if ($this->form['allow_partial_completion'] && ($this->form['partial_completion_mode'] == 'button' || $this->form['partial_completion_mode'] == 'auto_and_button')) {
			$t = $this->form['translate_text'];
			$html .= '<div class="complete_later"><input type="button" class="saveLater" value="' . static::fPhrase('Save and complete later', array(), $t) . '" data-message="' . htmlspecialchars(static::fPhrase('You are about to save this part-completed form, so that you can return to it later. Save now?', array(), $t)) . '"/></div>';
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
		if ($this->form['captcha_type'] == 'pictures' && setting('google_recaptcha_site_key') && setting('google_recaptcha_secret_key')) {
			echo '<script>
				var recaptchaCallback = function() {
					if (document.getElementById("zenario_user_forms_google_recaptcha_section")) {
						grecaptcha.render("zenario_user_forms_google_recaptcha_section", {
							sitekey : "' . setting('google_recaptcha_site_key') . '",
							theme : "' . setting('google_recaptcha_widget_theme') . '"
						});
					}
				};
				</script>';
			echo '<script src="https://www.google.com/recaptcha/api.js?onload=recaptchaCallback&render=explicit"></script>';
		}
	}
	
	private function getHoneypotHTML() {
	    $t = $this->form['translate_text'];
	    $html = '<div class="form_field honeypot" style="display:none;">';
	    if ($this->form['honeypot_label']) {
	        $html .= '<div class="field_title">' . static::fPhrase($this->form['honeypot_label'], array(), $t) . '</div>';
	    }
	    if (isset($this->errors['honeypot'])) {
	        $html .= '<div class="form_error">' . static::fPhrase($this->errors['honeypot'], array(), $t) . '</div>';
	    }
	    $html .= '<input type="text" name="field_hp" value="' . ($_POST['field_hp'] ?? '') . '" maxlength="100"/>';
	    $html .= '</div>';
	    return $html;
	}
	
	private function getFieldHTML($fieldId, $pageId, $readonly) {
		$t = $this->form['translate_text'];
		$field = $this->fields[$fieldId];
		$fieldName = static::getFieldName($fieldId);
		$fieldElementId = $this->containerId . '__' . $fieldName;
		$value = $this->getFieldCurrentValue($fieldId);
		$readonly = $readonly || $field['is_readonly'];
		
		$html = '';
		$errorHTML = '';
		$extraClasses = '';
		if (isset($this->errors[$fieldId])) {
			$errorHTML = '<div class="form_error">' . $this->errors[$fieldId] . '</div>';
		}
		if ($value) {
			$extraClasses .= ' has_value';
		}
		if ($field['is_required']) {
			$extraClasses .= ' mandatory';
		}
		
		//Label
		if ($field['type'] != 'group' && $field['type'] != 'checkbox') {
			$html .= '<div class="field_title">' . static::fPhrase($field['label'], array(), $t) . '</div>';
			$html .= $errorHTML;
		}
		
		switch ($field['type']) {
			case 'group':
			case 'checkbox':
				$html .= $errorHTML;
				$html .= '<input type="checkbox"';
				if ($value) {
					$html .= ' checked="checked"';
				}
				if ($readonly) {
					$html .= ' disabled="disabled"';
				}
				$html .= ' name="' . $fieldName . '" id="' . $fieldElementId . '"/>';
				$html .= '<label class="field_title" for="' . $fieldElementId . '">' . static::fPhrase($field['label'], array(), $t) . '</label>';
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
					
					$html .= '<div id="' . $this->containerId . '_field_' . $fieldId . '_calculation_code" style="display:none;">';
					$html .= json_encode($calculationCode);
					$html .= '</div>';
				}
				
				//Autocomplete options for text fields
				$autocompleteHTML = '';
				$useTextFieldName = true;
				if ($field['autocomplete'] && $field['values_source']) {
					$fieldLOV = $this->getFieldCurrentLOV($fieldId);
					$autocompleteFieldLOV = array();
					foreach ($fieldLOV as $listValueId => $listValue) {
						$autocompleteFieldLOV[] = array('v' => $listValueId, 'l' => $listValue);
					}
					//Autocomplete fields with no values are readonly
					if (empty($autocompleteFieldLOV)) {
						$readonly = true;
					}
					
					$autocompleteHTML .= '<div class="autocomplete_json" data-id="' . $fieldId . '" style="display:none;"';
					//Add data attribute for JS events if other fields need to update when this field changes
					if (checkRowExists(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', array('filter_on_field' => $fieldId))) {
						$autocompleteHTML .= ' data-source_field="1"';
					}
					
					//Add data attribute for JS event to update placeholder if no values in list after click
					if ($field['filter_on_field'] && $field['autocomplete_no_filter_placeholder']) {
						$autocompleteHTML .= ' data-auto_placeholder="' . htmlspecialchars(static::fPhrase($field['autocomplete_no_filter_placeholder'], array(), $t)) . '"';
					}
					
					$autocompleteHTML .= '>';
					$autocompleteHTML .= json_encode($autocompleteFieldLOV);
					$autocompleteHTML .= '</div>';
					
					$autocompleteHTML .= '<input type="hidden" name="' . $fieldName  . '" ';
					if (isset($fieldLOV[$value])) {
						$autocompleteHTML .= ' value="' . $value . '"';
						$value = $fieldLOV[$value];
					} else {
						$value = '';
					}
					$autocompleteHTML .= '/>';
					//Use hidden field as whats submitted and the text field is only for display
					$useTextFieldName = false;
				}
				
				//Set type to "email" if validation is for an email address
				$fieldInputType = 'text';
				if ($field['field_validation'] == 'email') {
					$fieldInputType = 'email';
				}
				$html .= '<input type="' . $fieldInputType . '"';
				if ($readonly) {
					$html .= ' readonly ';
				}
				if ($useTextFieldName) {
					$html .= ' name="' . $fieldName . '"';
				}
				$html .= ' id="' . $fieldElementId . '"';
				//Data vars to help caculated fields
				if ($field['repeat_start_id']) {
					$html .= ' data-repeated="1" data-repeated_row="' . $field['row'] . '" data-repeat_id="' . $field['repeat_start_id'] . '"';
				}
				if ($value !== false) {
					$html .= ' value="' . htmlspecialchars($value) . '"';
				}
				if ($field['placeholder'] !== '' && $field['placeholder'] !== null) {
					$html .= ' placeholder="' . htmlspecialchars(static::fPhrase($field['placeholder'], array(), $t)) . '"';
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
				$html .= ' maxlength="' . $maxlength . '" />';
				$html .= $autocompleteHTML;
				break;
				
			case 'date':
				$html .= '<input type="text" class="jquery_form_datepicker" ';
				if ($readonly) {
					$html .= ' disabled ';
				}
				if ($field['show_month_year_selectors']) {
					$html .= ' data-selectors="1"';
				}
				if ($field['no_past_dates']) {
					$html .= ' data-no_past_dates="1"';
				}
				if ($field['disable_manual_input']) {
					$html .= ' readonly';
				}
				
				$html .= ' id="' . $fieldElementId . '"/>';
				$html .= '<input type="hidden" name="' . $fieldName . '" id="' . $fieldElementId . '__0"';
				if ($value !== false) {
					$html .= ' value="' . htmlspecialchars($value) . '"';
				}
				$html .= '/>';
				$html .= '<input type="button" class="clear_date" value="x" id="' . $fieldElementId . '__clear"/>';
				break;
				
			case 'textarea':
				$html .= '<textarea rows="4" cols="51"';
				if ($field['placeholder'] !== '' && $field['placeholder'] !== null) {
					$html .= ' placeholder="' . htmlspecialchars(static::fPhrase($field['placeholder'], array(), $t)) . '"';
				}
				if ($readonly) {
					$html .= ' readonly ';
				}
				$html .= ' name="' . $fieldName . '" id="' . $fieldElementId . '">';
				if ($value !== false) {
					$html .= htmlspecialchars($value);
				}
				$html .= '</textarea>';
				break;
				
			case 'section_description':
				$description = static::fPhrase($field['description'], array(), $t);
				//If no tags...
				if ($description == strip_tags($description)) {
					$description = nl2br('<p>' . $description . '</p>');
				}
				$html .= '<div class="description">' . $description . '</div>';
				break;
				
			case 'radios':
				$fieldLOV = $this->getFieldCurrentLOV($fieldId);
				
				$cols = (int)$field['value_field_columns'];
				$html .= '<div class="values_wrap';
				if ($cols > 1) {
					$items = count($fieldLOV);
					$rows = ceil($items/$cols);
					$currentRow = $currentCol = 1;
					$html .= ' columns_' . $cols;
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
					$valueHTML .= ' name="'. $fieldName. '" id="' . $radioElementId . '"/>';
					$valueHTML .= '<label for="' . $radioElementId . '">' . static::fPhrase($label, array(), $t) . '</label></div>'; 
					
					if (($cols > 1) && ($currentRow > $rows)) {
						$currentRow = 1;
						$currentCol++;
					}
					if (($cols > 1) && ($currentRow == 1)) {
						$html .= '<div class="col_' . $currentCol . ' column">';
					}
					$html .= $valueHTML;
					if (($cols > 1) && (($currentRow++ == $rows) || ($lastValue == $valueId))) {
						$html .= '</div>';
					}
				}
				$html .= '</div>';
				
				if ($readonly && !empty($value)) {
					$html .= '<input type="hidden" name="' . $fieldName . '" value="'.htmlspecialchars($value).'" />';
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
					$html .= ' name="'. $fieldName. '" id="' . $radioElementId . '"/>';
					$html .= '<label for="' . $radioElementId . '">';
					//Make sure to use system country phrases if showing a list of countries
					if ($isCountryList && $t) {
						$html .= phrase('_COUNTRY_NAME_' . $valueId, array(), 'zenario_country_manager');
					} else {
						$html .= static::fPhrase($label, array(), $t);
					}
					$html .= '</label>';
					$html .= '</div>'; 
				}
				if ($readonly && !empty($value)) {
					$html .= '<input type="hidden" name="' . $fieldName . '" value="'.htmlspecialchars($value).'" />';
				}
				break;
			
			case 'select':
				$fieldLOV = $this->getFieldCurrentLOV($fieldId);
				$html .= '<select ';
				if ($readonly) {
					$html .= 'disabled ';
				}
				$html .= ' name="' . $fieldName . '" id="' . $fieldElementId . '">';
				$html .= '<option value="">' . static::fPhrase('-- Select --', array(), $t) . '</option>';
				foreach ($fieldLOV as $valueId => $label) {
					$html .= '<option value="' . htmlspecialchars($valueId) . '"';
					if ($valueId == $value) {
						$html .= ' selected="selected" ';
					}
					$html .= '>' . static::fPhrase($label, array(), $t) . '</option>';
				}
				$html .= '</select>';
				if ($readonly) {
					$html .= '<input type="hidden" name="' . $fieldName . '" value="' . htmlspecialchars($value) . '"/>';
				}
				break;
				
			case 'centralised_select':
				$fieldLOV = $this->getFieldCurrentLOV($fieldId);
				$isCountryList = $this->isCentralisedListOfCountries($field);
				$html .= '<select ';
				if ($readonly) {
					$html .= 'disabled ';
				}
				$html .= ' name="' . $fieldName . '" id="' . $fieldElementId . '"';
				//Add class for JS events if other fields need to update when this field changes
				if (checkRowExists(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', array('filter_on_field' => $fieldId))) {
					$html .= ' class="source_field"';
				}
				$html .= '>';
				$html .= '<option value="">' . static::fPhrase('-- Select --', array(), $t) . '</option>';
				foreach ($fieldLOV as $valueId => $label) {
					$valueId = (string)$valueId;
					$html .= '<option value="' . htmlspecialchars($valueId) . '"';
					if ($valueId === $value) {
						$html .= ' selected="selected" ';
					}
					$html .= '>';
					//Make sure to use system country phrases if showing a list of countries
					if ($isCountryList && $t) {
						$html .= phrase('_COUNTRY_NAME_' . $valueId, array(), 'zenario_country_manager');
					} else {
						$html .= static::fPhrase($label, array(), $t);
					}
					$html .= '</option>';
				}
				$html .= '</select>';
				if ($readonly) {
					$html .= '<input type="hidden" name="' . $fieldName . '" value="' . htmlspecialchars($value) . '"/>';
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
					$html .= ' columns_' . $cols;
					$keys = array_keys($fieldLOV);
					$lastValue = end($keys);
				}
				$html .= '">';
				foreach ($fieldLOV as $valueId => $label) {
					$checkBoxHtml = '';
					$name = $fieldName . '_' . $valueId; 
					$checkboxElementId = $fieldElementId . '_' . $valueId;
					
					$selected = $value && in_array($valueId, $value);
					$checkBoxHtml .= '<div class="field_checkbox ' . ($selected ? 'checked' : '') . '"><input type="checkbox" data-value="' . $valueId . '"';
					if ($selected) {
						$checkBoxHtml .= ' checked="checked"';
					}
					if ($readonly) {
						$checkBoxHtml .= ' disabled ';
					}
					$checkBoxHtml .= ' name="' . $name . '" id="' . $checkboxElementId . '"/>';
					
					if ($readonly && $selected) {
						$checkBoxHtml .= '<input type="hidden" name="' . $name . '" value="' . $selected . '" />';
					}
					$checkBoxHtml .= '<label for="' . $checkboxElementId . '">' . static::fPhrase($label, array(), $t) . '</label></div>';
					
					
					if (($cols > 1) && ($currentRow > $rows)) {
						$currentRow = 1;
						$currentCol++;
					}
					if (($cols > 1) && ($currentRow == 1)) {
						$html .= '<div class="col_' . $currentCol . ' column">';
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
					$html .= '<input type="button" data-id="' . $fieldId . '" value="' . static::fPhrase('Remove', array(), $t) . '" class="remove_attachment">';
					$html .= '<input type="hidden" name="' . $fieldName . '" value="' . htmlspecialchars($value) . '" />';
				} else {
					$html .= '<input type="file" name="' . $fieldName . '"/>';
				}
				break;
			
			case 'file_picker':
				if ($readonly) {
					$json = json_encode($value, JSON_FORCE_OBJECT);
					$html .= '<div class="files">';
					$html .= '<input type="hidden" name="' . $fieldName . '" value="' . htmlspecialchars($json) . '"/>';
					if ($value) {
						foreach ($value as $fileId => $file) {
							$html .= '<div class="file_row">';
							$html .= '<p><a href="' . $file['path'] . '" target="_blank">' . $file['name'] . '</a></p>';
							$html .= '</div>';
						}
					} else {
						$html .= static::fPhrase('No file found...', array(), $t);
					}
					$html .= '</div>';
				} else {
					$json = json_encode($value, JSON_FORCE_OBJECT);
					$multiple = $field['multiple_select'] ? 'multiple' : '';
					$html .= '
						<input type="hidden" name="' . $fieldName . '" value="' . htmlspecialchars($json) . '"/>
						<div class="files"></div>
						<div class="progress" style="display:none;">
							<div class="progress_bar" style="background:green;height:5px;"></div>
						</div>
						<div class="file_upload_button"><span>' . static::fPhrase('Upload file', array(), $t) . '</span>
							<input class="file_picker_field" type="file" name="file_upload[]" ' . $multiple . '>
						</div>';
				}
				break;
			
			case 'document_upload':
				$previewHTML = '';
				
				if ($value) {
					$fileList = array();
					foreach ($value as $file) {
						$fileList[] = '<a href="' . htmlspecialchars($file['path']) . '" target="_blank">' . htmlspecialchars($file['name']) . '</a>';
					}
					$previewHTML = implode(', ', $fileList);
				}
				
				if ($field['combined_filename']) {
					$fileName = $field['combined_filename'];
					$fileNameReadonly = 'readonly';
				} else {
					$fileName = static::fPhrase('my-combined-file', array(), $t);
					$fileNameReadonly = '';
				}
				
				$json = json_encode($value, JSON_FORCE_OBJECT);
				
				$html .= '
					<input type="hidden" name="' . $fieldName . '" value="' . htmlspecialchars($json) . '"/>
					<div class="files_preview">' . $previewHTML . '</div>
					<input type="button" class="open_popup_1" value="' . static::fPhrase('Upload', array(), $t) . '">
					<div class="overlay_1" style="display:none;">
						<div class="popup_1">
							<span class="close">×</span>
							<div class="header">
								<h3>' . static::fPhrase('Upload files', array(), $t) . '</h3>
							</div>
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
									<label>' . static::fPhrase('Select files to upload', array(), $t) . '</label>
									<div class="button">
										<span>' . static::fPhrase('Browse files', array(), $t) . '</span>
										<input class="upload_complete_files" type="file" name="file_upload[]" multiple>
									</div>
								</div>
								<div class="section_wrap">
									<label>' . static::fPhrase('Upload multiple images as a single PDF', array(), $t) . '</label>
									<input type="button" class="open_popup_2" value="' . static::fPhrase('Start...', array(), $t) . '">
								</div>
								<div class="section_wrap save">
									<input type="button" class="save" value="' . static::fPhrase('Save', array(), $t) . '">
								</div>
							</div>
						</div>
					</div>
					<div class="overlay_2" style="display:none;">
						<div class="popup_2">
							<span class="close">×</span>
							<div class="header">
								<h3>' . static::fPhrase('PDF creator', array(), $t) . '</h3>
							</div>
							
							<p>' . static::fPhrase('Click the button or drag to upload images. You\'re able to drag to re-order and rotate the images. When you\'re happy, click "combine" to make a PDF.', array(), $t) . '</p>
							
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
									<label>' . static::fPhrase('Upload multiple images', array(), $t) . '</label>
									<div class="button">
										<span>' . static::fPhrase('Browse files', array(), $t) . '</span>
										<input class="upload_file_fragments" type="file" name="file_upload[]" multiple accept="image/jpeg,image/gif,image/png">
									</div>
								</div>
								<div class="section_wrap">
									<label>' . static::fPhrase('File name', array(), $t) . '</label>
									<input type="text" class="filename" value="' . $fileName . '" ' . $fileNameReadonly . '>
								</div>
								<div class="section_wrap save">
									<input type="button" class="combine" value="' . static::fPhrase('Combine', array(), $t) . '">
								</div>
							</div>
						</div>
					</div>';
				break;
		}
		
		if (!empty($field['note_to_user'])) {
			$html .= '<div class="note_to_user">'. static::fPhrase($field['note_to_user'], array(), $t) .'</div>';
		}
		
		//Field containing div open
		$containerHTML = '<div id="' . $this->containerId . '_field_' . $fieldId . '" data-id="' . $fieldId . '" ';
		if ($field['visibility'] == 'visible_on_condition') {
			$containerHTML .= $this->getVisibleConditionDataValuesHTML($field, $pageId);
		}
		if ($field['type'] == 'restatement') {
			$containerHTML .= ' data-fieldid="' . $field['restatement_field'] . '"';
		} elseif ($field['type'] == 'calculated') {
			if ($field['value_prefix']) {
				$containerHTML .= ' data-prefix="' . $field['value_prefix'] . '"';
			}
			if ($field['value_postfix']) {
				$containerHTML .= ' data-postfix="' . $field['value_postfix'] . '"';
			}
		} elseif ($field['type'] == 'document_upload') {
			if ($field['combined_filename']) {
				$containerHTML .= ' data-filename="' . $field['combined_filename'] . '"';
			}
		}
		//Check if field is hidden (ignoring the repeat)
		if ($this->isFieldHidden($field, $ignoreRepeat = true)) {
			$containerHTML .= ' style="display:none;"';
		}
		
		//Containing div css classes
		$containerHTML .= ' class="form_field field_' . $field['type'] . ' ' . htmlspecialchars($field['css_classes']);
		if ($field['repeat_start_id']) {
			$idParts = explode('_', $fieldId);
			if (count($idParts) == 2) {
				$parentFieldId = $idParts[0];
				$containerHTML .= ' field_' . $parentFieldId . '_repeat';
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
		$containerHTML .= ' ' . $extraClasses;
		$containerHTML .= '">';
		
		$html = $containerHTML . $html . '</div>';
		return $html;
	}
	
	private function getFieldCurrentValue($fieldId, $recursionCount = 1) {
		if ($recursionCount > 999) {
			return false;
		}
		$value = false;
		$field = $this->fields[$fieldId];
		
		if ($field['type'] == 'calculated') {
			return $this->getCalculatedFieldCurrentValue($fieldId, $recursionCount);
		} elseif ($field['type'] == 'restatement') {
			$value = $this->getFieldCurrentValue($field['restatement_field'], ++$recursionCount);
			return static::getFieldDisplayValue($this->fields[$field['restatement_field']], $value);
		}
		
		//Check if value has been saved before
		if (isset($_SESSION['custom_form_data'][$this->instanceId][$fieldId])) {
			$value = $_SESSION['custom_form_data'][$this->instanceId][$fieldId];
		//..Otherwise see if we can load from the  dataset
		} elseif ($field['preload_dataset_field_user_data'] && userId() && $field['db_column']) {
			$this->allowCaching(false);
			
			$row = false;
			if (isset($field['row'])) {
				$row = $field['row'];
			}
			$datasetStoredValue = datasetFieldValue($this->dataset, $field['dataset_field_id'], userId(), true, false, $row);
			$value = static::getFieldValueFromStored($field, $datasetStoredValue);
			
			//Hack to allow dataset fields to have a default value of 0 for calculated fields
			if (!$value && $field['dataset_field_validation'] == 'numeric') {
				$value = 0;
			}
			
		//..Otherwise look for a default value
		} elseif ($field['default_value'] !== null) {
			$value = $field['default_value'];
		} elseif ($field['default_value_class_name'] !== null && $field['default_value_method_name'] !== null) {
			$this->allowCaching(false);
			
			inc($field['default_value_class_name']);
			$value = call_user_func(
				array(
					$field['default_value_class_name'], 
					$field['default_value_method_name']
				),
				$field['default_value_param_1'], 
				$field['default_value_param_2']
			);
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
						$fieldValue = $this->getFieldCurrentValue($step['value'], ++$recursionCount);
						if (!$fieldValue) {
							$fieldValue = 0;
						}
						if (!static::validateNumericInput($fieldValue)) {
							$isNaN = true;
							break 2;
						} else {
							$fieldValue = sprintf('%f', (float)$fieldValue);
							if (!empty($this->fields[$step['value']]['repeat_start_id'])) {
								$repeatFieldValues = array($fieldValue);
								$rows = $this->fields[$this->fields[$step['value']]['repeat_start_id']]['rows'];
								
								//Target field and calculated field are in the same repeat block
								if (!empty($field['repeat_start_id']) && $field['repeat_start_id'] == $this->fields[$step['value']]['repeat_start_id']) {
									if ($field['row'] > 1) {
										$repeatFieldValues = array();
										$rows = array($field['row']);
									} else {
										$rows = array();
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
				} catch (Exception $e) {
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
		$values = array();
		$field = $this->fields[$fieldId];
		switch ($field['type']) {
			case 'radios':
			case 'centralised_radios':
			case 'select':
			case 'checkboxes':
				if ($field['dataset_field_id']) {
					return getDatasetFieldLOV($field['dataset_field_id']);
				} else {
					return $this->getFormFieldLOV($fieldId);
				}
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
					$datasetField = getDatasetFieldDetails($field['dataset_field_id']);
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
					if ($field['dataset_field_id']) {
						return getDatasetFieldLOV($field['dataset_field_id'], true, $filter);
					} else {
						return $this->getFormFieldLOV($fieldId, $filter);
					}
				}
				break;
		}
		
		return $values;
	}
	
	private function getFormFieldLOV($fieldId, $filter = false) {
		$values = array();
		$field = $this->fields[$fieldId];
		switch ($field['type']) {
			case 'centralised_radios':
			case 'centralised_select':
			case 'restatement':
			case 'text':
				if (!empty($field['values_source_filter'])) {
					$filter = $field['values_source_filter'];
				}
				return getCentralisedListValues($field['values_source'], $filter);
			case 'select':
			case 'radios':
			case 'checkboxes':
				return getRowsArray(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', 'label', array('form_field_id' => $field['id']), 'ord');
		}
		return $values;
	}
	
	public static function getFormFieldValueLabel($datasetFieldId, $valueId) {
		if ($datasetFieldId) {
			return getRow('custom_dataset_field_values', 'label', $valueId);
		} else {
			return getRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', 'label', $valueId);
		}
	}
	
	private function loadRepeatRows($repeatStartField) {
		$fieldId = $repeatStartField['id'];
		$fieldName = static::getFieldName($fieldId);
		if (isset($_POST[$fieldName])) {
			$rows = explode(',', $_POST[$fieldName]);
		} elseif (isset($_SESSION['custom_form_data'][$this->instanceId][$fieldId])) {
			$rows = $_SESSION['custom_form_data'][$this->instanceId][$fieldId];
		} elseif ($repeatStartField['dataset_field_id']) {
			$datasetStoredValue = datasetFieldValue($this->dataset, $repeatStartField['dataset_field_id'], userId());
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
		$_SESSION['custom_form_data'][$this->instanceId][$fieldId] = $rows;
		
		return $rows;
	}
	
	private function savePageData($pageId, $post) {
		$t = $this->form['translate_text'];
		foreach ($this->pages[$pageId]['fields'] as $i => $fieldId) {
			$field = $this->fields[$fieldId];
			$name = static::getFieldName($fieldId);
			switch ($field['type']) {
				case 'checkbox':
				case 'group':
					$_SESSION['custom_form_data'][$this->instanceId][$fieldId] = !empty($post[$name]);
					break;
				case 'calculated':
				case 'url':
				case 'text':
				case 'date':
				case 'textarea':
				case 'radios':
				case 'centralised_radios':
				case 'select':
				case 'centralised_select':
					$_SESSION['custom_form_data'][$this->instanceId][$fieldId] = $post[$name] ?? false;
					break;
				case 'checkboxes':
					$lov = $this->getFieldCurrentLOV($fieldId);
					$values = array();
					foreach ($lov as $valueId => $label) {
						if (!empty($post[$name . '_' . $valueId])) {
							$values[] = $valueId;
						}
					}
					$_SESSION['custom_form_data'][$this->instanceId][$fieldId] = $values;
					break;
				case 'attachment':
					if (!empty($_FILES[$name]['tmp_name']) && is_uploaded_file($_FILES[$name]['tmp_name']) && cleanCacheDir()) {
						try {
							//Undefined | Multiple Files | $_FILES Corruption Attack
							//If this request falls under any of them, treat it invalid.
							if (!isset($_FILES[$name]['error']) || is_array($_FILES[$name]['error'])) {
								throw new RuntimeException(static::fPhrase('Invalid parameters.', array(), $t));
							}
							
							//Check $_FILES[$name]['error'] value.
							switch ($_FILES[$name]['error']) {
								case UPLOAD_ERR_OK:
									break;
								case UPLOAD_ERR_NO_FILE:
									throw new RuntimeException(static::fPhrase('No file sent.', array(), $t));
								case UPLOAD_ERR_INI_SIZE:
								case UPLOAD_ERR_FORM_SIZE:
									throw new RuntimeException(static::fPhrase('Exceeded filesize limit.', array(), $t));
								default:
									throw new RuntimeException(static::fPhrase('Unknown errors.', array(), $t));
							}
							
							//Check filesize. 
							if ($_FILES[$name]['size'] > 1000000) {
								throw new RuntimeException(static::fPhrase('Exceeded filesize limit.', array(), $t));
							}
							
							//File is valid, add to cache and remember the location
							$randomDir = createRandomDir(30, 'uploads');
							$cacheDir = $randomDir. Ze\File::safeName($_FILES[$name]['name'], true);
							if (move_uploaded_file($_FILES[$name]['tmp_name'], CMS_ROOT. $cacheDir)) {
								@chmod(CMS_ROOT. $cacheDir, 0666);
								$_SESSION['custom_form_data'][$this->instanceId][$fieldId] = $cacheDir;
							}
							
						} catch (RuntimeException $e) {
							$this->errors[$fieldId] = $e->getMessage();
						}
					} elseif (isset($post['remove_attachment_' . $fieldId])) {
						$_SESSION['custom_form_data'][$this->instanceId][$fieldId] = false;
					} else {
						$_SESSION['custom_form_data'][$this->instanceId][$fieldId] = $post[$name] ?? false;
					}
					break;
				case 'file_picker':
				case 'document_upload':
					$files = json_decode($post[$name], true);
					$_SESSION['custom_form_data'][$this->instanceId][$fieldId] = $files ? $files : array();
					break;
			}
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
			usort($orderedPages, 'sortByOrd');
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
			usort($orderedPages, 'sortByOrd');
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
		$maxPageReached = $_SESSION['custom_form_data'][$this->instanceId]['max_page_reached'] ?? false;
		if ($pageId != 'summary' && (!$maxPageReached || !isset($this->pages[$maxPageReached]) || (isset($this->pages[$pageId]) && ($this->pages[$pageId]['ord'] > $this->pages[$maxPageReached]['ord'])))) {
			$_SESSION['custom_form_data'][$this->instanceId]['max_page_reached'] = $pageId;
		}
		
		return $pageId;
	}
	
	private function loadPartialSaveData($userId, $formId) {
		//Load max_page_reached
		$partialSave = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response', array('id', 'max_page_reached', 'form_id'), array('user_id' => $userId, 'form_id' => $formId));
		$_SESSION['custom_form_data'][$this->instanceId] = array();
		$_SESSION['custom_form_data'][$this->instanceId]['max_page_reached'] = $partialSave['max_page_reached'];
		
		//Load field values
		$fields = static::getFormFieldsStatic($partialSave['form_id'], array(), false, $loadFromPartialResponseId = $partialSave['id']);
		foreach ($fields as $fieldId => $field) {
			if (isset($field['value'])) {
				$_SESSION['custom_form_data'][$this->instanceId][$fieldId] = $field['value'];
			}
		}
	}
	
	private function validateForm($pageId, $validateAllFields, $ignoreRequiredFields) {
		$t = $this->form['translate_text'];
		if ($validateAllFields) {
			$fields = array_keys($this->fields);
		} else {
			$fields = $this->pages[$pageId]['fields'];
		}
		
		$pageVisibility = array();
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
				}
			}
		}
		
		//Validate honeypot field
		if ($this->form['use_honeypot'] && isset($_POST['field_hp']) && $_POST['field_hp'] !== '') {
		    $this->errors['honeypot'] = static::fPhrase('This field must be left blank.', array(), $t);
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
				}
			}
		}
		return empty($this->errors);
	}
	
	private function validateFormField($fieldId, $ignoreRequiredFields) {
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
						$requiredFieldValue = array();
					}
					$values = $field['mandatory_condition_field_value'] ? explode(',', $field['mandatory_condition_field_value']) : array();
					
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
			
			if ($field['mandatory_condition_invert']) {
				$field['is_required'] = !$field['is_required'];
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
						return static::fPhrase($field['required_error_message'], array(), $t);
					}
					break;
				case 'centralised_radios':
				case 'centralised_select':
				case 'text':
				case 'date':
				case 'textarea':
				case 'url':
					if ($value === null || $value === '' || $value === false) {
						return static::fPhrase($field['required_error_message'], array(), $t);
					}
					break;
			}
		}
		
		//Check if user is allowed more than one submission
		if (!userId()
			&& $field['db_column'] == 'email' 
			&& $this->form['save_data']
			&& $this->form['user_duplicate_email_action'] == 'stop'
			&& $this->form['duplicate_email_address_error_message']
		) {
			$userId = getRow('users', 'id', array('email' => $value));
			if ($userId) {
				$responseExists = checkRowExists(
					ZENARIO_USER_FORMS_PREFIX. 'user_response', 
					array('user_id' => $userId, 'form_id' => $this->form['id'])
				);
				if ($responseExists) {
					return static::fPhrase($this->form['duplicate_email_address_error_message'], array(), $t);
				}
			}
		}
		
		//Text field validation
		if ($field['type'] == 'text' && $field['field_validation'] && $value !== '' && $value !== false) {
			switch ($field['field_validation']) {
				case 'email':
					if (!validateEmailAddress($value)) {
						return static::fPhrase($field['field_validation_error_message'], array(), $t);
					}
					break;
				case 'URL':
					if (filter_var($value, FILTER_VALIDATE_URL) === false) {
						return static::fPhrase($field['field_validation_error_message'], array(), $t);
					}
					break;
				case 'integer':
					if (filter_var($value, FILTER_VALIDATE_INT) === false) {
						return static::fPhrase($field['field_validation_error_message'], array(), $t);
					}
					break;
				case 'number':
					if (!static::validateNumericInput($value)) {
						return static::fPhrase($field['field_validation_error_message'], array(), $t);
					}
					break;
				case 'floating_point':
					if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
						return static::fPhrase($field['field_validation_error_message'], array(), $t);
					}
					break;
			}
		}
		
		//Multiple values invalid response validation
		if ($field['invalid_field_value_error_message'] && ($field['type'] == 'checkboxes' || $field['type'] == 'radios' || $field['type'] == 'select') && $value) {
			$valueArray = is_array($value) ? $value : array($value);
			foreach ($valueArray as $valueId) {
				$isInvalid = getRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', 'is_invalid', array('id' => $valueId, 'form_field_id' => $fieldId));
				if ($isInvalid) {
					return static::fPhrase($field['invalid_field_value_error_message'], array(), $t);
				}
			}
		}
		
		//Dataset field validation
		if ($field['dataset_field_id'] && $field['dataset_field_validation'] && $value !== '') {
			switch ($field['dataset_field_validation']) {
				case 'email':
					if (!validateEmailAddress($value)) {
						return static::fPhrase('Please enter a valid email address', array(), $t);
					}
					break;
				case 'emails':
					if (!validateEmailAddress($value, true)) {
						return static::fPhrase('Please enter a valid list of email addresses', array(), $t);
					}
					break;
				case 'no_spaces':
					if (preg_replace('/\S/', '', $value)) {
						return static::fPhrase('This field cannot contain spaces', array(), $t);
					}
					break;
				case 'numeric':
					if (($value !== '' && $value !== false) && !static::validateNumericInput($value)) {
						return static::fPhrase('This field must be numeric', array(), $t);
					}
					break;
				case 'screen_name':
					if (empty($value)) {
						$validationMessage = static::fPhrase('Please enter a screen name', array(), $t);
					} elseif (!validateScreenName($value)) {
						$validationMessage = static::fPhrase('Please enter a valid screen name', array(), $t);
					} elseif ((userId() && checkRowExists('users', array('screen_name' => $value, 'id' => array('!' => userId())))) 
						|| (!userId() && checkRowExists('users', array('screen_name' => $value)))
					) {
						return static::fPhrase('The screen name you entered is in use', array(), $t);
					}
					break;
			}
		}
		
		//Textarea wordlimit validation
		if ($field['type'] == 'textarea') {
			if ($field['word_count_max'] && $value && str_word_count($value) > $field['word_count_max']) {
				return static::fNPhrase('Cannot be more than [[n]] word.', 'Cannot be more than [[n]] words.', $field['word_count_max'], array('n' => $field['word_count_max']), $t);
			} elseif ($field['word_count_min'] && $value && str_word_count($value) < $field['word_count_min']) {
				return static::fNPhrase('Cannot be less than [[n]] word.', 'Cannot be less than [[n]] words.', $field['word_count_min'], array('n' => $field['word_count_min']), $t);
			}
		}
		
		//Date "not in past" validation
		if ($field['type'] == 'date' && $field['no_past_dates'] && $value) {
			$time = strtotime($value);
			$minTime = strtotime(date('Y-m-d')) - (60 * 60 * 24);
			if ($time < $minTime) {
				return static::fPhrase('This field cannot be in the past', array(), $t);
			}
		}
		
		return false;
	}
	
	private function saveForm() {
		$userId = userId();
		static::deleteOldPartialResponse($this->form['id'], $userId);
		$fieldIdValueLink = array();
		
		foreach ($this->fields as $fieldId => $field) {
			$this->fields[$fieldId]['value'] = $this->getFieldCurrentValue($fieldId);
			$fieldIdValueLink[$fieldId] = $this->getFieldStorableValue($fieldId);
		}
		
		//Updating users
		if ($userId) {
			if ($this->form['update_linked_fields']) {
				$this->saveUserLinkedFields($userId);
			}
			if ($this->form['add_logged_in_user_to_group']) {
				addUserToGroup($userId, $this->form['add_logged_in_user_to_group']);
			}
		//Creating users
		} elseif ($this->form['save_data']) {
			if (isset($this->datasetFieldsColumnLink['email'])) {
				$email = $this->getFieldCurrentValue($this->datasetFieldsColumnLink['email']);
				$userId = getRow('users', 'id', array('email' => $email));
				if ($userId) {
					if ($this->form['user_duplicate_email_action'] != 'ignore') {
						$this->saveUserLinkedFields($userId, array(), $this->form['user_duplicate_email_action'] == 'merge');
					}
				} elseif ($email && validateEmailAddress($email)) {
					//Set new user fields
					$details = array();
					$details['email'] = $email;
					$details['status'] = $this->form['type'] == 'registration' ? 'pending' : $this->form['user_status'];
					$details['password'] = createPassword();
					$details['ip'] = visitorIP();
					if (isset($this->datasetFieldsColumnLink['screen_name'])) {
						$details['screen_name_confirmed'] = true;
					}
					$userId = $this->saveUserLinkedFields($userId, $details);
					if ($this->form['type'] == 'registration') {
						$this->sendVerificationEmail($userId);
					}
				}
				if ($userId) {
					addUserToGroup($userId, $this->form['add_user_to_group']);
					//Log user in
					if ($this->form['log_user_in']) {
						$this->logUserIn($userId);
					}
				}
			}
		}
		
		//Profanity levels check
		$canSendEmails = true;
		$rating = 0;
		$tolerence = 0;
		if (setting('zenario_user_forms_set_profanity_filter') && $this->form['profanity_filter_text']) {
			$rating = $this->scanTextForProfanities();
			$tolerence = (int)setting('zenario_user_forms_set_profanity_tolerence');
			$canSendEmails = $rating < $tolerence;
		}
		
		//Save form response
		$responseId = false;
		if ($this->form['save_record']) {
			$responseId = $this->createFormResponse($userId, $rating, $tolerence, !$canSendEmails);
		}
		
		//Emails
		if ($canSendEmails) {
			$sendEmailToUser = ($this->form['send_email_to_logged_in_user'] || $this->form['send_email_to_email_from_field']);
			$sendEmailToAdmin = ($this->form['send_email_to_admin'] && $this->form['admin_email_addresses']);
			$userEmailMergeFields = false;
			$adminEmailMergeFields = false;
			
			//Send an email to the user
			if ($sendEmailToUser) {
				$startLine = 'Dear user,';
				$userEmailMergeFields = $this->getTemplateEmailMergeFields($userId);
				$emails = array();
				if ($this->form['send_email_to_logged_in_user'] && $userId) {
					$email = getRow('users', 'email', $userId);
					if ($email) {
						if ($this->form['user_email_use_template_for_logged_in_user']) {
							if ($this->form['user_email_template_logged_in_user']) {
								zenario_email_template_manager::sendEmailsUsingTemplate($email, $this->form['user_email_template_logged_in_user'], $userEmailMergeFields);
							}
						} else {
							$this->sendUnformattedFormEmail($startLine, $email);
						}
					}
				}
				if ($this->form['send_email_to_email_from_field'] && $this->form['user_email_field'] && isset($this->fields[$this->form['user_email_field']])) {
					$email = $this->fields[$this->form['user_email_field']]['value'];
					if ($email) {
						if ($this->form['user_email_use_template_for_email_from_field']) {
							if ($this->form['user_email_template_from_field']) {
								zenario_email_template_manager::sendEmailsUsingTemplate($email, $this->form['user_email_template_from_field'], $userEmailMergeFields);
							}
						} else {
							$this->sendUnformattedFormEmail($startLine, $email);
						}
						
					}
				}
			}
			
			//Send an email to administrators
			if ($sendEmailToAdmin) {
				$adminEmailMergeFields = $this->getTemplateEmailMergeFields($userId, true);
				
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
				
				$attachments = array();
				if (setting('zenario_user_forms_admin_email_attachments')) {
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
				
				if ($this->form['admin_email_use_template'] && $this->form['admin_email_template']) {
					zenario_email_template_manager::sendEmailsUsingTemplate($this->form['admin_email_addresses'], $this->form['admin_email_template'], $adminEmailMergeFields, $attachments, array(), false, $replyToEmail, $replyToName);
				} else {
					$startLine = 'Dear admin,';
					$this->sendUnformattedFormEmail($startLine, $this->form['admin_email_addresses'], $attachments, $replyToEmail, $replyToName, $adminDownloadLinks = true);
				}
			}
		}
		
		//Send a signal if specified
		if ($this->form['send_signal']) {
			sendSignal(
				'eventUserFormSubmitted', 
				array(
					'data' => $this->getTemplateEmailMergeFields($userId),
					'formProperties' => $this->form,
					'fieldIdValueLink' => $fieldIdValueLink,
					'responseId' => $responseId
				)
			);
		}
	}
	
	private function logUserIn($userId) {
		$user = logUserIn($userId);
		if ($this->form['log_user_in_cookie'] && canSetCookie()) {
			setCookieOnCookieDomain('LOG_ME_IN_COOKIE', $user['login_hash']);
		}
	}
	
	private function sendVerificationEmail($userId) {
		updateUserHash($userId);
		$emailMergeFields = getUserDetails($userId);
		if (!empty($emailMergeFields['email']) && $this->form['verification_email_template']) {
			$emailMergeFields['ip_address'] = visitorIP();
			$emailMergeFields['cms_url'] = absCMSDirURL();
			$emailMergeFields['email_confirmation_link'] = $this->linkToItem($this->cID, $this->cType, $fullPath = true, $request = '&confirm_email=1&hash='. $emailMergeFields['hash']);
			$emailMergeFields['user_groups'] = getUserGroupsNames($userId);
			zenario_email_template_manager::sendEmailsUsingTemplate($emailMergeFields['email'] ?? false, $this->form['verification_email_template'], $emailMergeFields, array());
		}
	}
	
	private function sendWelcomeEmail($userId) {
		$emailMergeFields = getUserDetails($userId);
		if (!empty($emailMergeFields['email']) && $this->form['welcome_email_template']) {
			$emailMergeFields['ip_address'] = visitorIP();
			$emailMergeFields['cms_url'] = absCMSDirURL();
			$emailMergeFields['user_groups'] = getUserGroupsNames($userId);
			//If passwords are encrypted
			if (!setting('plaintext_extranet_user_passwords')) {
				$password = createPassword();
				$emailMergeFields['password'] = $password;
				setUsersPassword($userId, $password);
			}
			zenario_email_template_manager::sendEmailsUsingTemplate($emailMergeFields['email'] ?? false, $this->form['welcome_email_template'], $emailMergeFields ,array());
		}
	}
	
	private function getTemplateEmailMergeFields($userId, $toAdmin = false) {
		$mergeFields = array();
		foreach ($this->fields as $fieldId => $field) {
			$column = $field['db_column'] ? $field['db_column'] : 'unlinked_' . $field['type'] . '_' . $fieldId;
			$display = static::getFieldDisplayValue($field, $field['value']);
			$mergeFields[$column] = $display;
		}
		
		if ($userId) {
			$user = getUserDetails($userId);
			$mergeFields['salutation'] = $user['salutation'];
			$mergeFields['first_name'] = $user['first_name'];
			$mergeFields['last_name'] = $user['last_name'];
			if (setting('plaintext_extranet_user_passwords')) {
				$mergeFields['password'] = $userDetails['password'];
			}
			$mergeFields['user_id'] = $userId;
		}
		$mergeFields['cms_url'] = absCMSDirURL();
		return $mergeFields;
	}
	
	private function sendUnformattedFormEmail($startLine, $email, $attachments = array(), $replyToEmail = false, $replyToName = false, $adminDownloadLinks = false) {
		$formName = $this->form['name'] ? trim($this->form['name']) : '[blank name]';
		$subject = 'New form submission for: ' . $formName;
		$addressFrom = setting('email_address_from');
		$nameFrom = setting('email_name_from');
		
		$body =
			'<p>' . $startLine . '</p>
			<p>The form "' . $formName . '" was submitted with the following data:</p>';
		
		//Get menu path of current page
		$menuNodeString = '';
		if ($this->form['send_email_to_admin'] && !$this->form['admin_email_use_template']) {
			$currentMenuNode = getMenuItemFromContent(cms_core::$cID, cms_core::$cType);
			if ($currentMenuNode && isset($currentMenuNode['mID']) && !empty($currentMenuNode['mID'])) {
				$nodes = static::drawMenu($currentMenuNode['mID'], cms_core::$cID, cms_core::$cType);
				for ($i = count($nodes) - 1; $i >= 0; $i--) {
					$menuNodeString .= $nodes[$i].' ';
					if ($i > 0) {
						$menuNodeString .= '&#187; ';
					}
				}
			}
		}
		if ($menuNodeString) {
			$body .= '<p>Page submitted from: ' . $menuNodeString . '</p>';
		}
		
		foreach ($this->fields as $fieldId => $field) {
			switch ($field['type']) {
				case 'repeat_start':
				case 'repeat_end':
				case 'section_description':
				case 'restatement':
					continue 2;
			}
			$display = static::getFieldDisplayValue($field, $field['value']);
			if ($adminDownloadLinks) {
				switch ($field['type']) {
					case 'attachment':
						if ($field['value']) {
							$fileId = $this->getFieldStorableValue($fieldId);
							$display = '<a href="' . absCMSDirURL() . 'zenario/file.php?adminDownload=1&id=' . $fileId . '" target="_blank">' . $display . '</a>';
						}
						break;
					case 'file_picker':
					case 'document_upload':
						$display = array();
						if ($field['value']) {
							foreach ($field['value'] as $fileId => $file) {
								$display[] = '<a href="' . absCMSDirURL() . 'zenario/file.php?adminDownload=1&id=' . $fileId . '" target="_blank">' . $file['name'] . '</a>';
							}
						}
						$display = implode(', ', $display);
						break;
				}
			}
			if ($field['type'] == 'textarea' && $display) {
				$display = '<br>' . $display;
			}
			$body .= '<p>' . trim($field['name'], " \t\n\r\0\x0B:") . ': ' . $display . '</p>';
		}
		
		$url = linkToItem(cms_core::$cID, cms_core::$cType, true, '', false, false, true);
		if (!$url) {
			$url = absCMSDirURL();
		}
		$body .= '<p>This is an auto-generated email from ' . $url . '</p>';
		
		zenario_email_template_manager::sendEmails($email, $subject, $addressFrom, $nameFrom, $body, array(), $attachments, array(), 0, false, $replyToEmail, $replyToName);
	}
	
	public static function drawMenu($nodeId, $cID, $cType) {
		$nodes = array();
		do {
			$text = getRow('menu_text', 'name', array('menu_id' => $nodeId, 'language_id' => setting('default_language')));
			$nodes[] = $text;
			$nodeId = getMenuParent($nodeId);
		} while ($nodeId != 0);
		$homeCID = $homeCType = false;
		langSpecialPage('zenario_home', $homeCID, $homeCType);
		if (!($cID == $homeCID && $cType == $homeCType)) {
			$equivId = equivId($homeCID, $homeCType);
			$sectionId = menuSectionId('Main');
			$menuId = getRow('menu_nodes', 'id', array('section_id' => $sectionId, 'equiv_id' => $equivId, 'content_type' => $homeCType));
			$nodes[] = getRow('menu_text', 'name', array('menu_id' => $menuId, 'language_id' => setting('default_language')));
		}
		return $nodes;
	}
	
	private function scanTextForProfanities() {
		$path = CMS_ROOT . 'zenario/libraries/not_to_redistribute/profanity-filter/profanities.csv';
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
	private function saveUserLinkedFields($userId, $userData = array(), $merge = false) {
		$userDetails = getUserDetails($userId);
		$dataset = getDatasetDetails('users');
		$userCustomData = array();
		$userCustomCheckboxValues = array();
		$userCustomFilePickerValues = array();
		foreach ($this->datasetFieldsLink as $fieldId => $datasetFieldId) {
			$field = $this->fields[$fieldId];
			if (!$field['is_readonly'] && $field['db_column'] != 'email') {
				$value = $this->getFieldCurrentValue($fieldId);
				
				if ($field['type'] == 'checkboxes') {
					$lov = getDatasetFieldLOV($datasetFieldId);
					$canSave = true;
					if ($merge) {
						foreach ($lov as $valueId => $valueDetails) {
							if (checkRowExists('custom_dataset_values_link', array('dataset_id' => $dataset['id'], 'value_id' => $valueId, 'linking_id' => $userId))) {
								$canSave = false;
								break;
							}
						}
					}
					if ($canSave) {
						$userCustomCheckboxValues[$datasetFieldId] = array('all' => $lov, 'set' => array());
						foreach ($value as $valueId) {
							if (isset($lov[$valueId])) {
								$userCustomCheckboxValues[$datasetFieldId]['set'][] = $valueId;
							}
						}
					}
				} elseif ($field['type'] == 'file_picker') {
					$canSave = true;
					if ($merge) {
						$canSave = !checkRowExists('custom_dataset_files_link', array('dataset_id' => $dataset['id'], 'field_id' => $datasetFieldId, 'linking_id' => $userId));
					}
					if ($canSave) {
						$value = $this->getFieldStorableValue($fieldId);
						$values = static::getFieldValueFromStored($field, $value);
						$userCustomFilePickerValues[$datasetFieldId] = array();
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
						$dbColumn = getDatasetFieldRepeatRowColumnName($dbColumn, $row);
					}
					
					if ($field['type'] == 'repeat_start') {
						$value = $this->getFieldStorableValue($fieldId);
					}
					
					if ($field['is_system_field']) {
						$userData[$dbColumn] = $value;
					} else {
						$userCustomData[$dbColumn] = $value;
					}
				}
			}
		}
		
		//Save system fields
		$newUserId = saveUser($userData, $userId);
		if ($userId) {
			sendSignal('eventUserModified', array('id' => $userId));
		} else {
			sendSignal('eventUserCreated', array('id' => $newUserId));
		}
		$userId = $newUserId;
		
		if ($userId) {
			//Save custom fields
			if ($userCustomData) {
				setRow('users_custom_data', $userCustomData, array('user_id' => $userId));
			}
			//Save custom multi-checkbox fields
			foreach ($userCustomCheckboxValues as $datasetFieldId => $values) {
				//Remove existing values
				foreach ($values['all'] as $valueId => $value) {
					deleteRow('custom_dataset_values_link', array('dataset_id' => $dataset['id'], 'value_id' => $valueId, 'linking_id' => $userId));
				}
				//Save new
				foreach ($values['set'] as $valueId) {
					insertRow('custom_dataset_values_link', array('dataset_id' => $dataset['id'], 'value_id' => $valueId, 'linking_id' => $userId));
				}
			}
			//Save custom form picker fields
			foreach ($userCustomFilePickerValues as $datasetFieldId => $fileIds) {
				//Remove existing values
				deleteRow('custom_dataset_files_link', array('dataset_id' => $dataset['id'], 'field_id' => $datasetFieldId, 'linking_id' => $userId));
				//Save new
				foreach ($fileIds as $fileId) {
					insertRow('custom_dataset_files_link', array('dataset_id' => $dataset['id'], 'field_id' => $datasetFieldId, 'linking_id' => $userId, 'file_id' => $fileId));
				}
			}
		}
		
		return $userId;
	}
	
	
	private function createFormResponse($userId, $rating = 0, $tolerence = 0, $blocked = 0) {
		$responseId = insertRow(ZENARIO_USER_FORMS_PREFIX. 'user_response', array('user_id' => $userId, 'form_id' => $this->form['id'], 'response_datetime' => now(), 'profanity_filter_score' => $rating, 'profanity_tolerance_limit' => $tolerence, 'blocked_by_profanity_filter' => $blocked));
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
			
			$value = $this->getFieldStorableValue($fieldId);
			insertRow(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', array('user_response_id' => $responseId, 'form_field_id' => $field['id'], 'value' => $value, 'field_row' => $row));
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
				FROM ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
				INNER JOIN ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response ur
					ON ur.form_id = uff.user_form_id
				WHERE ur.id = ' . (int)$responseId . '
				AND uff.custom_code_name = "' . sqlEscape($codeName) . '"';
			$result = sqlSelect($sql);
			$row = sqlFetchAssoc($result);
			$fieldId = $row['id'];
		}
		//Get value from response
		if ($fieldId) {
			$value = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', 'value', array('user_response_id' => $responseId, 'field_row' => $row, 'form_field_id' => $fieldId));
		}
		return $value;
	}
	
	//A shortcut function to get a fields display value from a response
	public static function getResponseFieldDisplayValue($fieldId, $responseId, $row = 0, $codeName = false) {
		$formId = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_response', 'form_id', $responseId);
		$field = static::getFormFieldsStatic($formId, array(), false, false, $fieldId, $codeName);
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
	
	
	private function getFieldStorableValue($fieldId) {
		$field = $this->fields[$fieldId];
		$value = $this->getFieldCurrentValue($fieldId);
		
		switch ($field['type']) {
			case 'checkboxes':
				return $value ? implode(',', $value) : '';
			case 'attachment':
				if ($value && file_exists(CMS_ROOT . $value)) {
					$fileId = Ze\File::addToDatabase('forms', CMS_ROOT . $value);
					return $fileId;
				}
				return false;
			case 'file_picker':
			case 'document_upload':
				$fileIds = array();
				if ($value) {
					usort($value, 'sortByOrd');
					foreach ($value as $i => $file) {
						$fileId = Ze\File::addToDatabase('forms', CMS_ROOT . $file['path']);
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
				return $value ? Ze\File::link($value) : false;
			case 'file_picker':
			case 'document_upload':
				$fileIds = $value ? explode(',', $value) : array();
				$values = array();
				foreach ($fileIds as $i => $fileId) {
					$values[$fileId] = array('id' => $fileId, 'name' => getRow('files', 'filename', $fileId), 'path' => Ze\File::link($fileId), 'ord' => $i);
				}
				return $values;
			case 'repeat_start':
				$rows = array();
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
	public static function getFieldDisplayValue($field, $value, $html = false) {
		switch ($field['type']) {
			case 'checkbox':
			case 'group':
				return $value ? 'Yes' : 'No';
			case 'radios':
			case 'select':
				return $value ? static::getFormFieldValueLabel($field['dataset_field_id'], $value) : '';
			case 'checkboxes':
				if ($value) {
					$labels = array();
					foreach ($value as $valueId) {
						$labels[] = static::getFormFieldValueLabel($field['dataset_field_id'], $valueId);
					}
					return implode(', ', $labels);
				}
				return '';
			case 'date':
				return formatDateNicely($value, '_MEDIUM');
			case 'textarea':
				return nl2br($value);
			case 'centralised_radios':
			case 'centralised_select':
				if ($field['dataset_field_id']) {
					$lov = getDatasetFieldLOV($field['dataset_field_id']);
				} else {
					$lov = getCentralisedListValues($field['values_source']);
				}
				return $lov[$value] ?? '';
			case 'attachment':
				if ($value) {
					$fileId = Ze\File::addToDatabase('forms', CMS_ROOT . $value);
					$file = getRow('files', array('filename'), $fileId);
					if ($file) {
						if ($html) {
							return '<a target="_blank" href="' . Ze\File::link($fileId) . '">' . $file['filename'] . '</a>';
						} else {
							return $file['filename'];
						}
					} else {
						return 'Unknown file';
					}
				}
				return '';
			case 'file_picker':
			case 'document_upload':
				if ($value) {
					$fileList = array();
					foreach ($value as $fileId => $file) {
						$fileId = Ze\File::addToDatabase('forms', CMS_ROOT . $file['path']);
						if ($html) {
							$fileList[] = '<a target="_blank" href="' . Ze\File::link($fileId) . '">' . $file['name'] . '</a>';
						} else {
							$fileList[] = $file['name'];
						}
					}
					return implode(', ', $fileList);
				}
				return '';
			case 'repeat_start':
				return is_array($value) ? count($value) : 0;
			default:
				return $value;
		}
	}
	
	private function createFormPartialResponse() {
		$userId = userId();
		static::deleteOldPartialResponse($this->form['id'], $userId);
		
		$maxPageReached = $_SESSION['custom_form_data'][$this->instanceId]['max_page_reached'];
		
		$responseId = insertRow(
			ZENARIO_USER_FORMS_PREFIX . 'user_partial_response',
			array('user_id' => $userId, 'form_id' => $this->form['id'], 'response_datetime' => now(), 'max_page_reached' => $maxPageReached)
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
			$value = $this->getFieldStorableValue($fieldId);
			
			insertRow(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response_data', array('user_partial_response_id' => $responseId, 'form_field_id' => $field['id'], 'value' => $value, 'field_row' => $row));
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
				if ($step['type'] == 'field' && $this->fields[$step['value']] && ($this->fields[$step['value']]['field_type'] == 'calculated')) {
					$nestedCalculationCode = json_decode($this->fields[$step['value']]['calculation_code']);
					if ($nestedCalculationCode) {
						//Surround nested calculation code with parentheses
						$openParentheses = array('type' => 'parentheses_open');
						$closeParentheses = array('type' => 'parentheses_close');
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
	
	private function showCaptcha() {
		if ($this->form['use_captcha'] 
			&& empty($_SESSION['captcha_passed__' . $this->instanceId]) 
			&& (!userId()
				|| $this->form['extranet_users_use_captcha']
			)
		) {
			return true;
		}
		return false;
	}
	
	private function getCaptchaError() {
		if ($this->showCaptcha() && ($_POST['submitForm'] ?? false) && $this->instanceId == ($_POST['instanceId'] ?? false)) {
			$error = false;
			$t = $this->form['translate_text'];
			if ($this->form['captcha_type'] == 'word') {
				if ($this->checkCaptcha()) {
					$_SESSION['captcha_passed__' . $this->instanceId] = true;
				} else {
					$error = true;
				}
			} elseif ($this->form['captcha_type'] == 'math' && isset($_POST['captcha_code'])) {
				require_once CMS_ROOT. 'zenario/libraries/mit/securimage/securimage.php';
				$securimage = new Securimage();
				if ($securimage->check($_POST['captcha_code']) != false) {
					$_SESSION['captcha_passed__' . $this->instanceId] = true;
				} else {
					$error = true;
				}
			} elseif ($this->form['captcha_type'] == 'pictures' 
				&& isset($_POST['g-recaptcha-response']) 
				&& setting('google_recaptcha_site_key') 
				&& setting('google_recaptcha_secret_key')
			) {
				$recaptchaResponse = $_POST['g-recaptcha-response'];
				$secretKey = setting('google_recaptcha_secret_key');
				$URL = "https://www.google.com/recaptcha/api/siteverify?secret=".$secretKey."&response=".$recaptchaResponse;
				$request = file_get_contents($URL);
				$response = json_decode($request, true);
				if(is_array($response)){
					if(isset($response['success'])){
						if($response['success']){
							$_SESSION['captcha_passed__'. $this->instanceId] = true;
						}else{
							$error = true;
						}
					}
				}
			}
			if ($error) {
				return static::fPhrase('Please verify that you are human.', array(), $t);
			}
		}
		return false;
	}
	
	private function getCaptchaHTML() {
		$t = $this->form['translate_text'];
		$html = '<div class="form_field captcha">';
		if (isset($this->errors['captcha'])) {
			$html .= '<div class="form_error">' . static::fPhrase($this->errors['captcha'], array(), $t) . '</div>';
		}
		if ($this->form['captcha_type'] == 'word') {
			$html .= $this->captcha();
		} elseif ($this->form['captcha_type'] == 'math') {
			$html .= '<p>
						<img id="siimage" style="border: 1px solid #000; margin-right: 15px" src="zenario/libraries/mit/securimage/securimage_show.php?" alt="CAPTCHA Image" align="left">
						
						&nbsp;
						<a tabindex="-1" style="border-style: none;" href="#" title="Refresh Image" onclick="document.getElementById(\'siimage\').src = \'zenario/libraries/mit/securimage/securimage_show.php?\' + Math.random(); this.blur(); return false">
							<img src="zenario/libraries/mit/securimage/images/refresh.png" alt="Reload Image" onclick="this.blur()" align="bottom" border="0">
						</a><br />
						Do the maths:<br />
						<input type="text" name="captcha_code" size="12" maxlength="16" class="math_captcha_input" id="' . $this->containerId . '_math_captcha_input"/>
					</p>';
		} elseif ($this->form['captcha_type'] == 'pictures' && setting('google_recaptcha_site_key') && setting('google_recaptcha_secret_key')) {
			$html .= '<div id="zenario_user_forms_google_recaptcha_section"></div>';
			$this->callScript('zenario_user_forms', 'recaptchaCallback');
		}
		$html .= '</div>';
		return $html;
	}
	
	
	
	
	
	
	protected function getWelcomePageHTML() {
		$t = $this->form['translate_text'];
		$user = getRow('users', array('first_name', 'last_name'), userId());
		return '<p class="success">' . static::fPhrase('Welcome, [[first_name]] [[last_name]]', $user, $t) . '</p>';
	}
	
	protected function getSuccessMessageHTML() {
		$t = $this->form['translate_text'];
		
		if ($this->form['type'] == 'registration') {
			$successMessage = static::fPhrase('Thank you for registering.<br> You have been sent an email with a verification link. You should check your spam/bulk mail if you do not see it soon. Please click the link in the email to verify your account.', array(), $t);

		} elseif ($this->form['success_message']) {
			$successMessage = nl2br(static::fPhrase($this->form['success_message'], array(), $t));
		} elseif (adminId()) {
			$successMessage = adminPhrase('Your success message will go here when you set it.');
		} else {
			$successMessage = 'Form submission successful!';
		}
		return '<div class="success">' . $successMessage . '</div>';
	}
	
	protected function getModalWindowButtonHTML() {
		$t = $this->form['translate_text'];
		$requests = 'showInFloatingBox=1';
		$buttonJS = $this->refreshPluginSlotAnchor($requests, false, false);
		
		$html  = '<div class="user_form_click_here" ' . $buttonJS . '>';
		$html .= '<h3>' . static::fPhrase($this->setting('display_text'), array(), $t) . '</h3>';
		$html .= '</div>';
		return $html;
	}
	
	protected function getPartialSaveResumeFormHTML() {
		$t = $this->form['translate_text'];
		
		$html  = '<div class="resume_box">';
		$html .= $this->openForm('if (this.submited && !confirm("' . htmlspecialchars($this->phrase("Are you sure you want to clear all your data?")) . '")) { return false; }');
		$html .= '<p>' . static::fPhrase($this->form['clear_partial_data_message'], array(), $t) . '</p>';
		$html .= '<input type="submit" onclick="this.form.submited = false" name="resume" value="' . static::fPhrase('Resume', array(), $t) . '">';
		$html .= '<input type="submit" onclick="this.form.submited = true" name="clear" value="' . static::fPhrase('Clear', array(), $t) . '">';
		$html .= $this->closeForm();
		$html .= '</div>';
		return $html;
	}
	
	protected function getVisibleConditionDataValuesHTML($field, $pageId) {
		$html = '';
		$conditionList = static::getConditionList($field, $this->fields);
		$html .= ' data-cfields="' . htmlspecialchars(json_encode($conditionList)) . '"';
		return $html;
	}
	
	
	
	
	
	//An overwritable method when a form is successfully submitted
	protected function successfulFormSubmit() {
		
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
	protected function getFormTitle() {
		$t = $this->form['translate_text'];
		if (!empty($this->form['title']) && !empty($this->form['title_tag'])) {
			$html = '<' . htmlspecialchars($this->form['title_tag']) . '>';
			$html .= static::fPhrase($this->form['title'], array(), $t);
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
	
	
	
	
	
	
	
	
	//Get the name of the field input on the form
	public static function getFieldName($fieldId) {
		return 'field_' . $fieldId;
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
			return phrase($text, $replace, 'zenario_user_forms');
		}
		applyMergeFields($text, $replace);
		return $text;
	}
	
	public static function fNPhrase($text, $pluralText, $n, $replace, $translate) {
		if ($translate) {
			return phrase($text, $pluralText, $n, $replace, 'zenario_user_forms');
		} elseif ($n == 1) {
			applyMergeFields($text, $replace);
			return $text;
		} else {
			applyMergeFields($pluralText, $replace);
			return $pluralText;
		}
	}
	
	public static function isFormCRMEnabled($formId) {
		if (inc('zenario_crm_form_integration')) {
			$formCRMDetails = getRow(
				ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_data', 
				array('enable_crm_integration'), 
				array('form_id' => $formId)
			);
			if ($formCRMDetails['enable_crm_integration']) {
				return true;
			}
		}
		return false;
	}
	
	public static function getTextFormFields($formId) {
		$formFields = array();
		$sql = "
			SELECT uff.id, uff.ord, uff.name, uff.validation AS field_validation, cdf.validation AS dataset_field_validation
			FROM ". DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX . "user_form_fields AS uff
			LEFT JOIN ". DB_NAME_PREFIX. "custom_dataset_fields AS cdf
				ON uff.user_field_id = cdf.id
			WHERE uff.user_form_id = ". (int)$formId. "
				AND (cdf.type = 'text' || uff.field_type = 'text')
			ORDER BY uff.ord";
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)) {
			$formFields[] = $row;
		}
		return $formFields;
	}
	
	//Get the ids of modules that can use forms
	public static function getFormModuleIds() {
		$ids = array();
		$formModuleClassNames = array('zenario_user_forms', 'zenario_extranet_profile_edit');
		foreach($formModuleClassNames as $moduleClassName) {
			if ($id = getModuleIdByClassName($moduleClassName)) {
				$ids[] = $id;
			}
		}
		return $ids;
	}
	
	public static function getFormJSON($formId) {
		$formJSON['form'] = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', true, $formId);
		$formJSON['pages'] = getRowsArray(ZENARIO_USER_FORMS_PREFIX . 'pages', true, array('form_id' => $formId));
		$formJSON['fields'] = array();
		$formJSON['values'] = array();
		$fieldsResult = getRows(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', true, array('user_form_id' => $formId));
		while ($field = sqlFetchAssoc($fieldsResult)) {
			if ($field['user_field_id']) {
				$datasetField = getRow('custom_dataset_fields', array('db_column', 'type'), $field['user_field_id']);
				$field['_db_column'] = $datasetField['db_column'];
				$field['_type'] = $datasetField['type'];
			}
			
			$formJSON['fields'][$field['id']] = $field;
			
			$valuesResult = getRows(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', true, array('form_field_id' => $field['id']));
			while ($value = sqlFetchAssoc($valuesResult)) {
				$formJSON['values'][$value['id']] = $value;
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
			return new zenario_error(adminPhrase('Invalid JSON object parsed.'));
		} elseif (!isset($formJSON['major_version']) || !isset($formJSON['minor_version']) || !isset($formJSON['forms'])) {
			return new zenario_error(adminPhrase('Invalid forms import file.'));
		} elseif ($formJSON['major_version'] != ZENARIO_MAJOR_VERSION || $formJSON['minor_version'] != ZENARIO_MINOR_VERSION) {
			return new zenario_error(
				adminPhrase(
					'Forms to import are from CMS version [[form_major_version]].[[form_minor_version]]. Your current CMS version is [[cms_major_version]].[[cms_minor_version]]. Please make sure the forms are from the same software version as this site.', 
					array(
						'form_major_version' => $formJSON['major_version'], 
						'form_minor_version' => $formJSON['minor_version'], 
						'cms_major_version' => ZENARIO_MAJOR_VERSION, 
						'cms_minor_version' => ZENARIO_MINOR_VERSION
					)
				)
			);
		}
		//Make sure each form in the import file can be imported and get errors and warnings
		$errors = array();
		$warnings = array();
		foreach ($formJSON['forms'] as $index => &$data) {
			$form = &$data['form'];
			
			//Check form name is unique
			$formNameExists = checkRowExists(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('name' => $form['name']));
			if ($formNameExists) {
				$errors[$index][] = adminPhrase('A form called "[[name]]" already exists', array('name' => $form['name']));
				continue;
			}
			
			//Only keep email templates that have a matching template name and not a numeric id
			$emailTemplateFields = array('user_email_template_from_field', 'user_email_template_logged_in_user', 'admin_email_template', 'verification_email_template', 'welcome_email_template');
			foreach ($emailTemplateFields as $fieldName) {
				if ($form[$fieldName]) {
					if (!is_numeric($form[$fieldName]) && checkRowExists('email_templates', array('code' => $form[$fieldName]))) {
						$warnings[$index][] = adminPhrase('Email template found in field "[[name]]" with value "[[value]]". A matching template was found on this site however it is not guaranteed to be identical.', array('name' => $fieldName, 'value' => $form[$fieldName]));
					} else {
						$warnings[$index][] = adminPhrase('Unable to import property "[[name]]" with value "[[value]]".', array('name' => $fieldName, 'value' => $form[$fieldName]));
						$form[$fieldName] = null;
					}
				}
			}
			
			//Remove any content items since we cannot guess what they should be on this site
			$contentItemFields = array('redirect_location', 'welcome_redirect_location');
			foreach ($contentItemFields as $fieldName) {
				if ($form[$fieldName]) {
					$warnings[$index][] = adminPhrase('Unable to import property "[[name]]" with value "[[value]]".', array('name' => $fieldName, 'value' => $form[$fieldName]));
				}
				$form[$fieldName] = null;
			}
			
			//Remove any dataset fields since we cannot guess what they should be on this site
			$datasetFieldFields = array('add_user_to_group', 'add_logged_in_user_to_group');
			foreach ($datasetFieldFields as $fieldName) {
				if ($form[$fieldName]) {
					$warnings[$index][] = adminPhrase('Unable to import property "[[name]]" with value "[[value]]".', array('name' => $fieldName, 'value' => $form[$fieldName]));
				}
				$form[$fieldName] = null;
			}
			
			//Check if any fields cannot be imported
			$dataset = getDatasetDetails('users');
			$invalidFields = array();
			foreach ($data['fields'] as $fieldId => &$field) {
				//Try and import dataset fields if there are fields with matching db_column and type
				if ($field['user_field_id']) {
					if (!empty($field['_db_column']) 
						&& !empty($field['_type'])
						&& ($datasetField = getRow('custom_dataset_fields', array('id'), array('dataset_id' => $dataset['id'], 'db_column' => $field['_db_column'], 'type' => $field['_type'], 'repeat_start_id' => 0)))
					) {
						unset($field['_db_column']);
						unset($field['_type']);
						$field['user_field_id'] = $datasetField['id'];
					} else {
						$warnings[$index][] = adminPhrase('Unable to import form field "[[name]]". A dataset field with db_column "[[_db_column]]" and type "[[_type]]" does not exist.', $field);
						$invalidFields[$fieldId] = true;
					}
				}
			}
			
			//Check if any fields saved on the form have issues
			$formFieldIdProperties = array('user_email_field', 'reply_to_email_field', 'reply_to_first_name', 'reply_to_last_name');
			foreach ($formFieldIdProperties as $fieldName) {
				if ($form[$fieldName] && isset($invalidFields[$form[$fieldName]])) {
					$form[$fieldName] = 0;
				}
			}
			
			//Check if any fields that can be imported have issues
			$fieldFieldIdProperties = array('mandatory_condition_field_id', 'visible_condition_field_id', 'restatement_field', 'filter_on_field', 'repeat_start_id');
			foreach ($data['fields'] as $fieldId => &$field) {
				foreach ($fieldFieldIdProperties as $fieldName) {
					if ($field[$fieldName] && isset($invalidFields[$field[$fieldName]])) {
						$warnings[$index][] = adminPhrase('Form field "[[field]]" property "[[name]]" with value "[[value]]" cannot be imported. The target field is invalid.', array('field' => $field['name'], 'name' => $fieldName, 'value' => $field[$fieldName]));
						unset($field[$fieldName]);
					}
				}
				if (!empty($field['calculation_code'])) {
					$calculationCode = json_decode($field['calculation_code'], true);
					foreach ($calculationCode as $step) {
						if ($step['type'] == 'field' && $step['value'] && isset($invalidFields[$step['value']])) {
							$warnings[$index][] = adminPhrase('Form field "[[field]]" property "calculation_code" with cannot be imported because it contains the invalid field "[[value]]".', array('field' => $field['name'], 'value' => $step['value']));
							unset($field['calculation_code']);
							break;
						}
					}
				}
			}
			
			//Check if any pages have issues
			$pageFieldIdProperties = array('visible_condition_field_id');
			foreach($data['pages'] as $pageId => &$page) {
				foreach ($pageFieldIdProperties as $fieldName) {
					if ($field[$fieldName] && isset($invalidFields[$field[$fieldName]])) {
						$warnings[$index][] = adminPhrase('Form page "[[page]]" property "[[name]]" with value "[[value]]" cannot be imported.', array('page' => $page['name'], 'name' => $fieldName, 'value' => $page[$fieldName]));
						unset($page[$fieldName]);
					}	
				}
			}
		}
		
		//If only validating return any errors and warnings.
		if ($validate) {
			return array('errors' => $errors, 'warnings' => $warnings);
		//Otherwise if there were errors, don't import.
		} elseif (!empty($errors)) {
			return false;
		}
		
		//Start import
		$firstNewFormId = false;
		$pageIdLink = array();
		$fieldIdLink = array();
		$valueIdLink = array();
		foreach ($formJSON['forms'] as $index => &$data) {
			//Create forms
			$form = $data['form'];
			$oldFormId = $form['id'];
			unset($form['id']);
			$newFormId = insertRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $form);
			
			if (!$firstNewFormId) {
				$firstNewFormId = $newFormId;
			}
			
			//Create pages
			foreach ($data['pages'] as &$page) {
				$oldPageId = $page['id'];
				unset($page['id']);
				$page['form_id'] = $newFormId;
				$newPageId = insertRow(ZENARIO_USER_FORMS_PREFIX . 'pages', $page);
				$pageIdLink[$oldPageId] = $newPageId;
			}
			
			//Create fields
			foreach ($data['fields'] as &$field) {
				$oldFieldId = $field['id'];
				if (isset($invalidFields[$oldFieldId])) {
					continue;
				}
				unset($field['id']);
				$field['user_form_id'] = $newFormId;
				$field['page_id'] = $pageIdLink[$field['page_id']];
				$newFieldId = insertRow(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', $field);
				$fieldIdLink[$oldFieldId] = $newFieldId;
			}
			
			//Create values
			foreach ($data['values'] as &$value) {
				$oldValueId = $value['id'];
				unset($value['id']);
				$value['form_field_id'] = $fieldIdLink[$value['form_field_id']];
				$newValueId = insertRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', $value);
				$valueIdLink[$oldValueId] = $newValueId;
			}
			
			//Update form saved Ids
			$update = array();
			foreach ($formFieldIdProperties as $fieldName) {
				if ($form[$fieldName]) {
					$update[$fieldName] = $fieldIdLink[$form[$fieldName]];
				}
			}
			if ($update) {
				updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $update, $newFormId);
			}
			
			//Update page saved Ids
			foreach ($pageIdLink as $oldPageId => $newPageId) {
				$page = $data['pages'][$oldPageId];
				$update = array();
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
							$newValueIds = array();
							$oldValueIds = explode(',', $page['visible_condition_field_value']);
							foreach ($oldValueIds as $oldValueId) {
								$newValueIds[] = $valueIdLink[$oldValueId];
							}
							$update['visible_condition_field_value'] = implode(',', $newValueIds);
							break;
					}
				}
				if ($update) {
					updateRow(ZENARIO_USER_FORMS_PREFIX . 'pages', $update, $newPageId);
				}
			}
			
			//Update field saved Ids
			foreach ($fieldIdLink as $oldFieldId => $newFieldId) {
				$field = $data['fields'][$oldFieldId];
				$update = array();
				foreach ($fieldFieldIdProperties as $fieldName) {
					if ($field[$fieldName]) {
						$update[$fieldName] = $fieldIdLink[$field[$fieldName]];
					}
				}
				if ($field['visible_condition_field_id'] && $field['visible_condition_field_value']) {
					switch ($data['fields'][$field['visible_condition_field_id']]['field_type']) {
						case 'select':
						case 'radios':
							$update['visible_condition_field_value'] = $valueIdLink[$field['visible_condition_field_value']];
							break;
						case 'checkboxes':
							$newValueIds = array();
							$oldValueIds = explode(',', $field['visible_condition_field_value']);
							foreach ($oldValueIds as $oldValueId) {
								$newValueIds[] = $valueIdLink[$oldValueId];
							}
							$update['visible_condition_field_value'] = implode(',', $newValueIds);
							break;
					}
				}
				if ($field['mandatory_condition_field_id'] && $field['mandatory_condition_field_value']) {
					switch ($data['fields'][$field['mandatory_condition_field_id']]['field_type']) {
						case 'select':
						case 'radios':
							$update['mandatory_condition_field_value'] = $valueIdLink[$field['mandatory_condition_field_value']];
							break;
						case 'checkboxes':
							$newValueIds = array();
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
							$newValueIds = array();
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
					$update['calculation_code'] = json_encode($calculationCode);
				}
				
				if ($update) {
					updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', $update, $newFieldId);
				}
			}
		}
		return $firstNewFormId;
	}
	
}

class calculator {
    const PATTERN = '/(?:\-?\d+(?:\.?\d+)?[\+\-\*\/])+\-?\d+(?:\.?\d+)?/';

    const PARENTHESIS_DEPTH = 10;

    public function calculate($input){
    	
    	set_error_handler(function($errno, $errstr, $errfile, $errline) {
    		throw new Exception($errstr);
    	});
    	
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
                return $this->compute($match[0]);
            }
            return 0;
        }
        
        restore_error_handler();
        
        return $input;
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

