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

class zenario_user_forms extends module_base_class {
	
	public $data = array();
	
	public function init() {
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = false, $ifCookieSet = false);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = true, $clearByFile = false, $clearByModuleData = true);
		
		$this->data['showForm'] = true;
		$formProperties = getRow('user_forms', 
			array('name', 'title', 'success_message', 'show_success_message', 'use_captcha', 'captcha_type', 'extranet_users_use_captcha', 'send_email_to_admin', 'admin_email_use_template', 'translate_text', 'submit_button_text', 'default_previous_button_text'), 
			$this->setting('user_form'));
		
		// Add a captcha to the form
		if ($formProperties['captcha_type'] == 'math') {
			require_once CMS_ROOT. 'zenario/libraries/mit/securimage/securimage.php';
		}
		if ($formProperties['use_captcha'] && empty($_SESSION['captcha_passed__'.$this->instanceId])) {
			if (!userId() || $formProperties['extranet_users_use_captcha']) {
				$this->data['captcha'] = true;
				if ($formProperties['captcha_type'] == 'word') {
					$this->data['captcha_html'] = $this->captcha();
				} elseif ($formProperties['captcha_type'] == 'math') {
					$this->data['captcha_html'] = '
						<p>
							<img id="siimage" style="border: 1px solid #000; margin-right: 15px" src="zenario/libraries/mit/securimage/securimage_show.php?sid=<?php echo md5(uniqid()) ?>" alt="CAPTCHA Image" align="left">
							
							&nbsp;
							<a tabindex="-1" style="border-style: none;" href="#" title="Refresh Image" onclick="document.getElementById(\'siimage\').src = \'zenario/libraries/mit/securimage/securimage_show.php?sid=\' + Math.random(); this.blur(); return false">
								<img src="zenario/libraries/mit/securimage/images/refresh.png" alt="Reload Image" onclick="this.blur()" align="bottom" border="0">
							</a><br />
							Enter Code:<br />
							<input type="text" name="captcha_code" size="12" maxlength="16" class="math_captcha_input"/>
						</p>';
				}
			}
		}
		$translate = $formProperties['translate_text'];
		if (!empty($formProperties['title'])) {
			$this->data['title'] = self::formPhrase($formProperties['title'], array(), $translate);
		}
		
		$this->data['submit_button_text'] = self::formPhrase($formProperties['submit_button_text'], array(), $translate);
		$this->data['identifier'] = $this->getFormIdentifier();
		$pageBreakFields = getRowsArray('user_form_fields', 'id', array('field_type' => 'page_break', 'user_form_id' => $this->setting('user_form')), array('ordinal'));
		if ($pageBreakFields) {
			$this->data['multiPageFormFinalBackButton'] = '<input type="button" name="previous" value="'.self::formPhrase($formProperties['default_previous_button_text'], array(), $translate).'" class="previous"/>';
			$this->data['multiPageFormFinalPageEnd'] = '</fieldset>';
			$this->callScript('zenario_user_forms', 'initMultiPageForm', $this->pluginAJAXLink(), $this->containerId, $this->data['identifier'], ($this->setting('display_mode') == 'in_modal_window'));
		}
		
		// Handle form submission
		if (post('submit_form') && $this->instanceId == post('instanceId')) {
			
			// Get form errors
			$this->data['errors'] = self::validateUserForm($this->setting('user_form'), $_POST);
			
			// Check captcha if used
			if ($formProperties['use_captcha'] && empty($_SESSION['captcha_passed__'.$this->instanceId])) {
				if (!userId() || $formProperties['extranet_users_use_captcha']) {
					if ($formProperties['captcha_type'] == 'word') {
						if ($this->checkCaptcha()) {
							$_SESSION['captcha_passed__'. $this->instanceId] = true;
						} else {
							$this->data['errors'][] = array('message' => self::formPhrase('Please correctly verify that you are human', array(), $translate));
						}
					} elseif ($formProperties['captcha_type'] == 'math') {
						$securimage = new Securimage();
						if ($securimage->check($_POST['captcha_code']) != false) {
							$_SESSION['captcha_passed__'. $this->instanceId] = true;
						} else {
							$this->data['errors'][] = array('message' => self::formPhrase('Please correctly verify that you are human', array(), $translate));
						}
					}
				}
			}
			
			// Save data if no errors
			if (empty($this->data['errors'])) {
				unset($_SESSION['captcha_passed__'. $this->instanceId]);
				$url = $this->linkToItem($this->cID, $this->cType, true, '', false, false, true);
				
				// Get menu path of current page
				$menuNodeString = '';
				if ($formProperties['send_email_to_admin'] && !$formProperties['admin_email_use_template']) {
					$currentMenuNode = getMenuItemFromContent(cms_core::$cID, cms_core::$cType);
					if ($currentMenuNode && isset($currentMenuNode['mID']) && !empty($currentMenuNode['mID'])) {
						$nodes = $this->drawMenu($currentMenuNode['mID']);
						for ($i = count($nodes) - 1; $i >= 0; $i--) {
							$menuNodeString .= $nodes[$i].' ';
							if ($i > 0) {
								$menuNodeString .= '&#187; ';
							}
						}
					}
				}
				
				$redirect = self::saveUserForm($this->setting('user_form'), $_POST, userId(), $url, $menuNodeString);
				if ($redirect) {
					$this->headerRedirect($redirect);
				} elseif ($this->data['showSuccessMessage'] = $formProperties['show_success_message']) {
					$this->data['successMessage'] = self::formPhrase($formProperties['success_message'], array(), $translate);
				} else {
					if (isset($_SESSION['destURL'])) {
						$link = $_SESSION['destURL'];
						$this->headerRedirect($link);
					}
				}
			}
			// $_POST for keeping data after submission error, userId() for preloading user data
			$this->data['formFields'] = self::drawUserForm($this->setting('user_form'), $_POST, false, $this->data['errors'], $this->setting('checkbox_columns'), $this->containerId);
		
		// Otherwise just draw form
		} else {
			$this->data['formFields'] = self::drawUserForm($this->setting('user_form'), userId(), false, array(), $this->setting('checkbox_columns'), $this->containerId);
		}
		$enctype = $this->getFormEncType($this->setting('user_form'));
		$this->data['openForm'] = $this->openForm('', $enctype.'id="'.$this->data['identifier'].'__form"');
		$this->data['closeForm'] = $this->closeForm();
		
		$formFields = self::getUserFormFields($this->setting('user_form'));
		foreach ($formFields as $fieldId => $field) {
			// Set form field initial visibility
			if (($field['visibility'] == 'visible_on_condition') && $field['visible_condition_field_id'] && isset($formFields[$field['visible_condition_field_id']])) {
				$visibleConditionField = $formFields[$field['visible_condition_field_id']];
				$visibleConditionFieldType = self::getFieldType($visibleConditionField);
				$this->callScript('zenario_user_forms', 'toggleFieldVisibility', $this->containerId, $fieldId, $field['visible_condition_field_id'], $field['visible_condition_field_value'], $visibleConditionFieldType);
			}
			
			// Init restatement field listeners
			if ($field['field_type'] == 'restatement' && $field['restatement_field'] && isset($formFields[$field['restatement_field']])) {
				$type = self::getFieldType($formFields[$field['restatement_field']]);
				$this->callScript('zenario_user_forms', 'initRestatementField', $this->data['identifier'], $fieldId, $field['restatement_field'], $type, $translate);
			}
			
			// Init calculate field listeners
			if ($field['field_type'] == 'calculated' && $field['numeric_field_1'] && $field['numeric_field_2'] && $field['calculation_type']) {
				// Get any fields that mirror this calculated field
				$mirrorFields = getRowsArray('user_form_fields', 'restatement_field', 
					array('user_form_id' => $this->setting('user_form'), 'field_type' => 'restatement', 'restatement_field' => $fieldId));
				
				$this->callScript('zenario_user_forms', 'initCalculateField', $this->containerId, $fieldId, $field['numeric_field_1'], $field['numeric_field_2'], $field['calculation_type'], $mirrorFields);
			}
		}
		
		// Handle modal window form
		if ($this->setting('display_mode') == 'in_modal_window') {
			if (!empty($_REQUEST['show_user_form']) || $this->checkPostIsMine()) {
				$this->showInFloatingBox();
			} else {
				$this->data['displayText'] = self::formPhrase($this->setting('display_text'), array(), $translate);
				$this->data['showForm'] = false;
				$requests = 'show_user_form=1';
				if (!empty(cms_core::$importantGetRequests)
				 && is_array(cms_core::$importantGetRequests)) {
					foreach(cms_core::$importantGetRequests as $getRequest => $defaultValue) {
						if (isset($_GET[$getRequest]) && $_GET[$getRequest] != $defaultValue) {
							$requests .= '&'. urlencode($getRequest). '='. urlencode($_GET[$getRequest]);
						}
					}
				}
				$this->data['showFormJS'] = $this->refreshPluginSlotAnchor($requests, false, false);
			}
		}
		return true;
	}
	
	public function getFormIdentifier() {
		return $this->containerId.'_user_form';
	}
	
	private function drawMenu($nodeId) {
		$nodes = array();
		do {
			$text = getRow('menu_text', 'name', array('menu_id' => $nodeId, 'language_id' => setting('default_language')));
			$nodes[] = $text;
			$nodeId = getMenuParent($nodeId);
		} while ($nodeId != 0);
		$cID = $cType = false;
		langSpecialPage('zenario_home', $cID, $cType);
		if (!($this->cID == $cID && $this->cType == $cType)) {
			$equivId = equivId($cID, $cType);
			$sectionId = menuSectionId('Main');
			$menuId = getRow('menu_nodes', 'id', array('section_id' => $sectionId, 'equiv_id' => $equivId, 'content_type' => $cType));
			$nodes[] = getRow('menu_text', 'name', array('menu_id' => $menuId, 'language_id' => setting('default_language')));
		}
		return $nodes;
	}
	
	public function showSlot() {
		$this->twigFramework($this->data);
	}
	
	public function handlePluginAJAX() {
		// Validate pages of multi-stage forms
		$errors = self::validateUserForm($this->setting('user_form'), $_POST, (int)post('_pageNo'));
		echo json_encode($errors);
	}
	
	private static function getFormEncType($formId) {
		if (checkRowExists('user_form_fields', array('field_type' => 'attachment', 'user_form_id' => $formId))) {
			return 'enctype="multipart/form-data"';
		}
		return '';
	}
	
	public static function getUserFormFields($userFormId, $formFieldId = false) {
		$formFields = array();
		$sql = "
			SELECT 
				uff.id AS form_field_id, 
				uff.user_field_id, 
				uff.ordinal, 
				uff.is_readonly, 
				uff.is_required,
				uff.mandatory_condition_field_id,
				uff.mandatory_condition_field_value,
				uff.visibility,
				uff.visible_condition_field_id,
				uff.visible_condition_field_value,
				uff.label AS field_label,
				uff.name,
				uff.placeholder,
				uff.size,
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
				uff.validation_error_message,
				uff.field_type,
				uff.next_button_text,
				uff.previous_button_text,
				uff.description,
				uff.numeric_field_1,
				uff.numeric_field_2,
				uff.calculation_type,
				uff.restatement_field,
				cdf.id AS field_id, 
				cdf.type, 
				cdf.db_column, 
				cdf.label, 
				cdf.is_system_field, 
				cdf.dataset_id, 
				cdf.validation, 
				cdf.validation_message
			FROM ". DB_NAME_PREFIX ."user_forms AS uf
			INNER JOIN ". DB_NAME_PREFIX ."user_form_fields AS uff
				ON uf.id = uff.user_form_id
			LEFT JOIN ". DB_NAME_PREFIX ."custom_dataset_fields AS cdf
				ON uff.user_field_id = cdf.id
			WHERE uf.id = ". (int)$userFormId;
		if ($formFieldId !== false) {
			$sql .= '
				AND uff.id = '.(int)$formFieldId;
		}
		$sql .= "
			ORDER BY uff.ordinal";
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)) {
			
			if ($row['type'] == 'checkboxes') {
				$values = getDatasetFieldLOV($row['field_id']);
				$row['field_values'] = $values;
			}
			
			$formFields[$row['form_field_id']] = $row;
		}
		return $formFields;
	}
	
	public static function drawUserForm($userFormId, $loadData = false, $readOnly = false, $errors = array(), $checkboxColumns = 1, $containerId = '') {
		$formFields = self::getUserFormFields($userFormId);
		$formProperties = getRow('user_forms', array('translate_text', 'default_next_button_text', 'default_previous_button_text'), array('id' => $userFormId));
		$translate = $formProperties['translate_text'];
		//If $loadData is an array, use that as the data
		if ($loadData && is_array($loadData)) {
			$data = $loadData;
		
		//If $loadData is a number, try to load data for that user
		} elseif ($loadData && is_numeric($loadData)) {
			$data = array();
			if ($dataset = getDatasetDetails('users')) {
				$sql ="
					SELECT *
					FROM ". DB_NAME_PREFIX. "users AS u
					LEFT JOIN ". DB_NAME_PREFIX. "users_custom_data AS ucd
					ON ucd.user_id = u.id
					WHERE u.id = ". (int) $loadData;
				$result = sqlQuery($sql);
				if ($row = sqlFetchAssoc($result)) {
					$data = $row;
				}
				
				$sql ="
					SELECT cdfv.field_id, cdf.db_column, cdvl.value_id
					FROM ". DB_NAME_PREFIX. "custom_dataset_values_link AS cdvl
					INNER JOIN ". DB_NAME_PREFIX. "custom_dataset_field_values AS cdfv
					ON cdvl.value_id = cdfv.id
					INNER JOIN ". DB_NAME_PREFIX. "custom_dataset_fields AS cdf
					ON cdfv.field_id = cdf.id
					WHERE cdvl.linking_id = ". (int) $loadData. "
					  AND cdvl.dataset_id = ".(int) $dataset['id'];
				$result = sqlQuery($sql);
				
				while ($row = sqlFetchAssoc($result)) {
					$data[$row['value_id'] . '_' . $row['db_column']] = true;
				}
				unset($row);
				
			}
		//If $loadData is not provided, start with no data loaded
		} else {
			$data = array();
		}
		
		$pageBreakFields = getRowsArray('user_form_fields', 'id', array('field_type' => 'page_break', 'user_form_id' => $userFormId), array('ordinal'));
		
		// Begin form field HTML
		$html = '';
		
		$currentDivWrapClass = false;
		$wrapDivOpen = false;
		
		if ($pageBreakFields) {
			$html .= '<fieldset id="'.$containerId.'_page_1" class="page_1">';
			$page = 1;
		}
		foreach ($formFields as $fieldId => $field) {
			
			$type = self::getFieldType($field);
			$userFieldId = $field['user_field_id'];
			$fieldName = self::getFieldName($field);
			
			
			// Create wrapper divs
			if ($wrapDivOpen && ($currentDivWrapClass != $field['div_wrap_class'])) {
				$wrapDivOpen = false;
				$html .= '</div>';
			}
			if (!$wrapDivOpen && $field['div_wrap_class']) {
				$html .= '<div class="'.htmlspecialchars($field['div_wrap_class']).'">';
				$wrapDivOpen = true;
			}
			$currentDivWrapClass = $field['div_wrap_class'];
			
			
			// Add page break and naviagation buttons
			if ($type == 'page_break') {
				if ($fieldId != reset($pageBreakFields)) {
					$previousButtonText = $field['previous_button_text'] ? $field['previous_button_text'] : $formProperties['default_previous_button_text'];
					$html .= '<input type="button" name="previous" value="'.self::formPhrase($previousButtonText, array(), $translate).'" class="previous"/>';
				}
				$nextButtonText = $field['next_button_text'] ? $field['next_button_text'] : $formProperties['default_next_button_text'];
				$html .= '<input type="button" name="next" value="'.self::formPhrase($nextButtonText, array(), $translate).'" class="next"/>';
				$html .= '</fieldset><fieldset id="'.$containerId.'_page_'.++$page.'" class="page_'.$page.'" style="display:none;">';
				continue;
			}
			
			if ($field['field_label'] !== null) {
				$field['label'] = $field['field_label'];
			}
			
			$errorHTML = '';
			if (isset($errors[$fieldId])) {
				$errorHTML = '<div class="form_error">'.$errors[$fieldId]['message'].'</div>';
			}
			$labelHTML = '';
			if (!empty($field['label'])) {
				$labelHTML = '<div class="field_title">'. self::formPhrase($field['label'], array(), $translate) .'</div>';
			}
			
			$html .= '<div';
			$html .= ' id="'.$containerId.'_field_'.htmlspecialchars($fieldId).'"';
			
			// For mirrored and calculated fields, use normal field type
			if ($type == 'calculated') {
				if (empty($data[$fieldName])) {
					$data[$fieldName] = 0;
				}
				$type = 'text';
				$field['is_readonly'] = true;
			} elseif ($type == 'restatement') {
				if (isset($formFields[$field['restatement_field']])) {
					// Set to text type if mirroring a calculated field, otherwise attempt to mimic restated field type
					$field['is_readonly'] = true;
					$restatementFieldType = self::getFieldType($formFields[$field['restatement_field']]);
					$type = ($formFields[$field['restatement_field']]['field_type'] == 'calculated') ? 'text' : $restatementFieldType;
					$restatementFieldName = self::getFieldName($formFields[$field['restatement_field']]);
					
					if ($type == 'calculated') {
						$data[$fieldName] = 0;
					} else {
						$userFieldId = $formFields[$field['restatement_field']]['user_field_id'];
						$fieldId = $field['restatement_field'];
						$fieldName = $restatementFieldName;
						if (isset($data[$restatementFieldName])) {
							$data[$fieldName] = $data[$restatementFieldName];
						}
					}
				}
			}
			$html .= ' class="form_field field_'.htmlspecialchars($type);
			
			// Handle hiding a field
			$hidden = false;
			if ($field['visibility'] == 'hidden') {
				$hidden = true;
			
			} elseif (($field['visibility'] == 'visible_on_condition') && $field['visible_condition_field_id'] && isset($formFields[$field['visible_condition_field_id']])) {
				
				$visibleConditionField = $formFields[$field['visible_condition_field_id']];
				$visibleConditionFieldName = self::getFieldName($visibleConditionField);
				
				// If condition field is checkbox, hide field if checkbox data does not match conditon field value
				if (self::getFieldType($visibleConditionField) == 'checkbox') {
					if (($field['visible_condition_field_value'] && !isset($data[$visibleConditionFieldName])) ||
						!$field['visible_condition_field_value'] && isset($data[$visibleConditionFieldName])) {
						$hidden = true;
					}
				// If condition field is select
				} else {
					
					$hidden = isset($data[$visibleConditionFieldName]) && ($data[$visibleConditionFieldName] != $field['visible_condition_field_value']);
					
					if (empty($data[$visibleConditionFieldName]) && !$hidden) {
						$default = false;
						if (!empty($visibleConditionField['default_value'])) {
							$default = $visibleConditionField['default_value'];
						} elseif (!empty($visibleConditionField['default_value_class_name']) && !empty($visibleConditionField['default_value_method_name'])) {
							if (inc($visibleConditionField['default_value_class_name'])) {
							$default = call_user_func(array($visibleConditionField['default_value_class_name'], $visibleConditionField['default_value_method_name']), $visibleConditionField['default_value_param_1'], $visibleConditionField['default_value_param_2']);
							}
						}
						$hidden = ($default !== false) && ($field['visible_condition_field_value'] != $default);
					}
				}
			}
			
			if ($readOnly || $field['is_readonly']) {
				$html .= ' readonly ';
			}
			
			// Add css classes
			$html .= ' '.htmlspecialchars($field['css_classes']).'"';
			
			// Hide hidden fields
			if ($hidden) {
				$html .= ' style="display:none;" ';
			}
			$html .= '>';
			
			// Position errors and labels for checkboxes
			if (!in_array($type, array('checkbox', 'group'))) {
				$html .= $labelHTML;
				$html .= $errorHTML;
			}
			
			// Set field size
			$size = 50;
			switch ($field['size']) {
				case 'small':
					$size = 25;
					break;
				case 'medium':
					$size = 50;
					break;
				case 'large':
					$size = 75;
					break;
			}
			
			// Get default value of field
			$fieldValue = false;
			if (in_array($type, array('radios', 'centralised_radios', 'select', 'centralised_select', 'text', 'textarea'))) {
				if (!empty($data[$fieldName])) {
					$fieldValue = $data[$fieldName];
				} elseif (!empty($field['default_value'])) {
					$fieldValue = $field['default_value'];
				} elseif (!empty($field['default_value_class_name']) && !empty($field['default_value_method_name'])) {
					
					inc($field['default_value_class_name']);
					$fieldValue = call_user_func(array($field['default_value_class_name'], $field['default_value_method_name']), $field['default_value_param_1'], $field['default_value_param_2']);
				}
			}
			
			// Get id of inputs for mirror fields and labels
			$id = $containerId.'_field_value_'.$fieldId;
			
			switch ($type) {
				case 'group':
				case 'checkbox':
					$html .= '<input type="checkbox" ';
					if (isset($data[$fieldName]) && (($data[$fieldName] == 1) || $data[$fieldName] == 'on')) {
						$html .= 'checked ';
					}
					if ($readOnly || $field['is_readonly']) {
						$html .= 'disabled ';
					}
					if ($field['field_type'] == 'restatement') {
						$html .= ' data-mirror-of="'.$id.'" ';
					} else {
						$html .= ' name="'. htmlspecialchars($fieldName).'" id="'.$id.'" onchange="zenario_user_forms.updateRestatementFields(this.id, \'checkbox\');" ';
					}
					$html .= '/>';
					if (($readOnly || $field['is_readonly']) && isset($data[$fieldName]) && $field['field_type'] != 'restatement') {
						$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.htmlspecialchars($data[$fieldName]).'" />';
					}
					$html .= $labelHTML;
					$html .= $errorHTML;
					break;
				case 'checkboxes':
					if ($userFieldId) {
						$valuesList = getDatasetFieldLOV($userFieldId);
					} else {
						$valuesList = self::getUnlinkedFieldLOV($fieldId);
					}
					
					$html .= '<div class="checkboxes_wrap';
					if ($sortIntoCols = !($checkboxColumns == 1) && $checkboxColumns) {
						$items = count($valuesList);
						$cols = (int)$checkboxColumns;
						$rows = ceil($items/$cols);
						$currentRow = $currentCol = 1;
						$html .= ' columns_'.$checkboxColumns;
					}
					$html .= '">';
					foreach ($valuesList as $valueId => $label) {
						$checkBoxHtml = '';
						$name = htmlspecialchars($valueId.'_'.$fieldName); 
						$multiFieldId = $id.'_'.$valueId;
						$selected = isset($data[$valueId. '_'. $fieldName]);
						$checkBoxHtml .= '<div class="field_checkbox"><input type="checkbox" ';
						if ($selected) {
							$checkBoxHtml .= ' checked="checked"';
						}
						if ($readOnly || $field['is_readonly']) {
							$checkBoxHtml .= ' disabled ';
						}
						if ($field['field_type'] == 'restatement') {
							$checkBoxHtml .= ' data-mirror-of="'.$multiFieldId.'" ';
							// Stop mirror field labels selecting target field checkboxes
							$multiFieldId = '';
						} else {
							$checkBoxHtml .= ' name="'.$name.'" id="'.$multiFieldId.'" onchange="zenario_user_forms.updateRestatementFields(this.id, \'checkbox\');" ';
						}
						$checkBoxHtml .= '/><label for="'.$multiFieldId.'">';
						$checkBoxHtml .= self::formPhrase($label, array(), $translate);
						$checkBoxHtml .= '</label></div>';
						
						
						if (($readOnly || $field['is_readonly']) && $selected && $field['field_type'] != 'restatement') {
							$checkBoxHtml .= '<input type="hidden" name="'.$name.'" value="'.$selected.'" />';
						}
						
						
						if (($sortIntoCols) && ($currentRow > $rows)) {
							$currentRow = 1;
							$currentCol++;
						}
						if (($sortIntoCols) && ($currentRow == 1)) {
							$html .= '<div class="col_'.$currentCol.' column">';
						}
						
						$html .= $checkBoxHtml;
						if (($sortIntoCols) && ($currentRow++ == $rows)) {
							$html .= '</div>';
						}
					}
					$html .= '</div>';
					break;
				case 'date':
					$html .= '<input type="text" readonly ';
					if (isset($data[$fieldName])) {
						$html .= ' value="'. $data[$fieldName] .'" ';
					}
					if (!($readOnly || $field['is_readonly'])) {
						$html .= ' class="jquery_datepicker" ';
					}
					if ($field['field_type'] == 'restatement') {
						$html .= ' data-mirror-of="'.$id.'" ';
					} else {
						$html .= ' name="'. htmlspecialchars($fieldName).'" id="'.$id.'" onchange="zenario_user_forms.updateRestatementFields(this.id);" ';
					}
					$html .= '/>';
					
					break;
				case 'editor':
					// TODO: Mirrored field for editors. (some way to use tinymce onchange event?)
					
					if ($readOnly || $field['is_readonly']) {
						$html .= '<div class="field_data" ';
						if ($field['field_type'] == 'restatement') {
							$html .= ' data-mirror-of="'.$id.'" ';
						}
						$html .= ' >';
						if (isset($data[$fieldName])) {
							$html .= $data[$fieldName];
						}
						$html .= '</div>';
					} else {
						$html .= '<textarea name="'. htmlspecialchars($fieldName). '" class="tinymce" id="'.$id.'" />';
						if (isset($data[$fieldName])) {
							$html .= $data[$fieldName];
						}
						$html .= '</textarea>';
					}
					
					break;
				case 'radios':
					if ($userFieldId) {
						$valuesList = getDatasetFieldLOV($userFieldId);
					} else {
						$valuesList = self::getUnlinkedFieldLOV($fieldId);
					}
					foreach ($valuesList as $valueId => $label) {
						
						$multiFieldId = $id.'_'.$valueId;
						
						$html .= '<div class="field_radio"><input type="radio"  value="'. htmlspecialchars($valueId) .'"';
						if ($valueId == $fieldValue) {
							$html .= ' checked="checked" ';
						}
						if ($readOnly || $field['is_readonly']) {
							$html .= ' disabled ';
						}
						if ($field['field_type'] == 'restatement') {
							$html .= ' name="'.htmlspecialchars($fieldName).'_'.$field['form_field_id'].'" data-mirror-of="'.$multiFieldId.'" ';
							// Stop mirror field labels selecting target field radios
							$multiFieldId = '';
						} else {
							$html .= ' name="'. htmlspecialchars($fieldName). '" id="'.$multiFieldId.'" onclick="zenario_user_forms.updateRestatementFields(this.id, \'radio\');" ';
						}
						$html .= '/><label for="'.$multiFieldId.'"/>';
						$html .= self::formPhrase($label, array(), $translate);
						$html .= '</label></div>'; 
					}
					if (($readOnly || $field['is_readonly']) && !empty($data[$fieldName]) && $field['field_type'] != 'restatement') {
						$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.htmlspecialchars($data[$fieldName]).'" />';
					}
					
					break;
				case 'centralised_radios':
					$values = getDatasetFieldLOV($userFieldId);
					
					// If this field looks like it's using the countries list, get phrases from country manager
					$valuesSource = getRow('custom_dataset_fields', 'values_source', array('id' => $field['user_field_id']));
					$countryList = ($valuesSource == 'zenario_country_manager::getActiveCountries');
					
					$count = 1;
					foreach ($values as $valueId => $label) {
						
						$multiFieldId = $id.'_'.$count++;
						
						$html .= '<div class="field_radio"><input type="radio" value="'. htmlspecialchars($valueId) .'"';
						if ($valueId == $fieldValue) {
							$html .= 'checked="checked"';
						}
						if ($readOnly || $field['is_readonly']) {
							$html .= ' disabled ';
						}
						if ($field['field_type'] == 'restatement') {
							$html .= ' name="'.htmlspecialchars($fieldName).'_'.$field['form_field_id'].'" data-mirror-of="'.$multiFieldId.'" ';
							// Stop mirror field labels selecting target field radios
							$multiFieldId = '';
						} else {
							$html .= ' name="'. htmlspecialchars($fieldName). '" id="'.$multiFieldId.'" onclick="zenario_user_forms.updateRestatementFields(this.id, \'radio\');" ';
						}
						$html .= '/><label for="'.$multiFieldId.'">';
						if ($countryList && $translate) {
							$html .=  phrase('_COUNTRY_NAME_'.$valueId, array(), 'zenario_country_manager');
						} else {
							$html .= self::formPhrase($label, array(), $translate);
						}
						$html .= '</label></div>';
					}
					if (($readOnly || $field['is_readonly']) && isset($data[$fieldName]) && $field['field_type'] != 'restatement') {
						$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.htmlspecialchars($data[$fieldName]).'" />';
					}
					break;
				case 'select':
					if ($userFieldId) {
						$valuesList = getDatasetFieldLOV($userFieldId);
					} else {
						$valuesList = self::getUnlinkedFieldLOV($fieldId);
					}
					
					$html .= '<select ';
					if ($readOnly || $field['is_readonly']) {
						$html .= ' disabled ';
					}
					if ($field['field_type'] == 'restatement') {
						$html .= ' data-mirror-of="'.$id.'" ';
					} else {
						$html .= ' name="'. htmlspecialchars($fieldName).'" id="'.$id.'" onchange="zenario_user_forms.updateRestatementFields(this.id);" ';
					}
					$html .= '>';
					$html .= '<option value="">'.self::formPhrase('-- Select --', array(), $translate).'</option>';
					foreach ($valuesList as $valueId => $label) {
						$html .= '<option value="'. htmlspecialchars($valueId) . '"';
						if ($valueId == $fieldValue) {
							$html .= ' selected="selected"';
						}
						$html .= '>'. self::formPhrase($label, array(), $translate) . '</option>';
					}
					$html .= '</select>';
					if (($readOnly || $field['is_readonly']) && ($field['field_type'] != 'restatement')) {
						$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.$fieldValue.'" />';
					}
					
					break;
				case 'centralised_select':
					$values = getDatasetFieldLOV($userFieldId);
					
					$html .= '<select ';
					if ($readOnly || $field['is_readonly']) {
						$html .= ' disabled ';
					}
					if ($field['field_type'] == 'restatement') {
						$html .= ' data-mirror-of="'.$id.'" ';
					} else {
						$html .= ' name="'. htmlspecialchars($fieldName).'" id="'.$id.'" onchange="zenario_user_forms.updateRestatementFields(this.id);" ';
					}
					$html .= '>';
					$html .= '<option value="">'.self::formPhrase('-- Select --', array(), $translate).'</option>';
					
					
					// If this field looks like it's using the countries list, get phrases from country manager
					$valuesSource = getRow('custom_dataset_fields', 'values_source', array('id' => $field['user_field_id']));
					$countryList = ($valuesSource == 'zenario_country_manager::getActiveCountries');
					
					
					foreach ($values as $valueId => $label) {
						$html .= '<option value="'. htmlspecialchars($valueId). '"';
						if ($valueId == $fieldValue) {
							$html .= ' selected="selected"';
						}
						$html .= '>';
						if ($countryList && $translate) {
							$html .=  phrase('_COUNTRY_NAME_'.$valueId, array(), 'zenario_country_manager');
						} else {
							$html .= self::formPhrase($label, array(), $translate);
						}
						$html .= '</option>';
					}
					$html .= '</select>';
					if (($readOnly || $field['is_readonly']) && ($field['field_type'] != 'restatement')) {
						$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.$fieldValue.'" />';
					}
					break;
				case 'url':
				case 'text':
					$type = 'text';
					if ($field['field_validation'] == 'email') {
						$type = 'email';
					}
					$html .= '<input type="'.$type.'" size="'. htmlspecialchars($size).'"';
					
					if ($readOnly || $field['is_readonly']) {
						$html .= ' readonly ';
					}
					if ($field['field_type'] == 'restatement') {
						$html .= ' data-mirror-of="'.$id.'" ';
					} else {
						$html .= ' name="'. htmlspecialchars($fieldName).'" id="'.$id.'" onkeyup="zenario_user_forms.updateRestatementFields(this.id);" ';
					}
					
					if (isset($data[$fieldName]) && $data[$fieldName] !== '' && $data[$fieldName] !== false) {
						$html .= ' value="'. htmlspecialchars($data[$fieldName]). '"';
					} elseif ($fieldValue) {
						$html .= ' value="'. htmlspecialchars($fieldValue). '"';
					}
					
					if (isset($field['placeholder']) && $field['placeholder'] !== '' && $field['placeholder'] !== null) {
						$html .= ' placeholder="'.htmlspecialchars(self::formPhrase($field['placeholder'], array(), $translate)) .'"';
					}
					
					$maxlength = 255;
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
					$html .= ' maxlength="'.$maxlength.'" ';
					$html .= '/>';
					break;
				case 'textarea':
					$html .= '<textarea name="'. htmlspecialchars($fieldName) .'" rows="4" cols="51"';
					if (isset($field['placeholder']) && $field['placeholder'] !== '' && $field['placeholder'] !== null) {
						$html .= ' placeholder="'.htmlspecialchars(self::formPhrase($field['placeholder'], array(), $translate)) .'"';
					}
					
					if ($readOnly || $field['is_readonly']) {
						$html .= ' readonly ';
					}
					if ($field['field_type'] == 'restatement') {
						$html .= ' data-mirror-of="'.$id.'" ';
					} else {
						$html .= ' name="'. htmlspecialchars($fieldName).'" id="'.$id.'" onkeyup="zenario_user_forms.updateRestatementFields(this.id);" ';
					}
					
					$html .= '>';
					if (isset($data[$fieldName]) && $data[$fieldName] !== '' && $data[$fieldName] !== false) {
						$html .= htmlspecialchars($data[$fieldName]);
					} elseif ($fieldValue) {
						$html .= htmlspecialchars($fieldValue);
					}
					
					$html .= '</textarea>';
					
					break;
				case 'attachment':
					// TODO: Mirrored field for attachment
					
					if ($readOnly || $field['is_readonly']) {
						$html .= '<div class="field_data">';
						$html .= htmlspecialchars($data[$fieldName]);
						$html .= '</div>';
						$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.htmlspecialchars($data[$fieldName]) .'" />';
					} else {
						if (isset($data[$fieldName])) {
							$html .= '<div class="field_data">';
							$html .= htmlspecialchars(substr(basename($data[$fieldName]), 0, -7));
							$html .= '</div>';
							$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.htmlspecialchars($data[$fieldName]) .'" />';
						} else {
							$html .= '<input type="file" name="'.htmlspecialchars($fieldName) .'"/>';
						}
					}
					break;
				case 'section_description':
					$html .= '<div class="description">';
					$html .= '<p>'.$field['description'].'</p>';
					$html .= '</div>';
					break;
			}
			
			// Add a note at the bottom of the field to the user
			if (!empty($field['note_to_user'])) {
				$html .= '<div class="note_to_user">'. self::formPhrase($field['note_to_user'], array(), $translate) .'</div>';
			}
			// End form field html
			$html .= '</div>';
		}
		// Make sure all wrapper divs are closed
		if ($wrapDivOpen) {
			$html .= '</div>';
		}
		return $html;
	}
	
	public static function formPhrase($phrase, $mergeFields = array(), $translate) {
		if ($translate) {
			return phrase($phrase, $mergeFields, 'zenario_user_forms');
		}
		return $phrase;
	}
	
	public static function getFieldType($field) {
		return ($field['type'] ? $field['type'] : $field['field_type']);
	}
	
	public static function getFieldName($field) {
		return ($field['db_column'] ? $field['db_column'] : 'unlinked_'. $field['field_type'].'_'.$field['form_field_id']);
	}
	
	public static function validateUserForm($userFormId, $data, $pageNo = 0) {
		
		$formFields = self::getUserFormFields($userFormId);
		$formProperties = getRow('user_forms', 
							array(
								'save_data',
								'user_duplicate_email_action', 
								'duplicate_email_address_error_message'), 
							array('id' => $userFormId));
		
		
		// Unset all fields except on the current page if form is multi-page
		if ($pageNo) {
			self::filterFormFieldsByPage($formFields, $pageNo);
		}
		
		$translate = getRow('user_forms', 'translate_text', array('id' => $userFormId));
		$requiredFields = array();
		$fileFields = array();
		foreach ($formFields as $fieldId => $field) {
			$userFieldId = $field['user_field_id'];
			$fieldName = self::getFieldName($field);
			$type = self::getFieldType($field);
			if ($field['field_label'] != null) {
				$field['label'] = $field['field_label'];
			}
			if ($type == 'text') {
				$validationMessage = '';
				$valid = true;
				// Look for form field validation
				if ($field['field_validation']) {
					switch ($field['field_validation']) {
						case 'email':
							if (!validateEmailAddress($data[$fieldName])) {
								$valid = false;
							}
							break;
						case 'URL':
							if (($data[$fieldName] !== '') && filter_var($data[$fieldName], FILTER_VALIDATE_URL) === false) {
								$valid = false;
							}
							break;
						case 'integer':
							if (($data[$fieldName] !== '') && filter_var($data[$fieldName], FILTER_VALIDATE_INT) === false) {
								$valid = false;
							}
							break;
						case 'number':
							if (($data[$fieldName] !== '') && !is_numeric($data[$fieldName])) {
								$valid = false;
							}
							break;
						case 'floating_point':
							if (($data[$fieldName] !== '') && filter_var($data[$fieldName], FILTER_VALIDATE_FLOAT) === false) {
								$valid = false;
							}
							break;
					}
					if (!$valid) {
						$validationMessage = $field['validation_error_message'];
					}
				}
				
				// Look for dataset field validation
				if ($field['validation'] && $field['user_field_id'] && $valid) {
					switch ($field['validation']) {
						case 'email':
							if (!validateEmailAddress($data[$fieldName])) {
								$validationMessage = self::formPhrase('Please enter a valid email address', array(), $translate);
							}
							break;
						case 'emails':
							if (!validateEmailAddress($data[$fieldName], true)) {
								$validationMessage = self::formPhrase('Please enter a valid list of email addresses', array(), $translate);
							}
							break;
						case 'no_spaces':
							if (preg_replace('/\S/', '', $data[$fieldName])) {
								$validationMessage = self::formPhrase('This field cannot contain spaces', array(), $translate);
							}
							break;
						case 'numeric':
							if (!is_numeric($data[$fieldName])) {
								$validationMessage = self::formPhrase('This field must be numeric', array(), $translate);
							}
							break;
						case 'screen_name':
							if (empty($data[$fieldName])) {
								$validationMessage = self::formPhrase('Please enter a screen name', array(), $translate);
							} elseif (!validateScreenName($data[$fieldName])) {
								$validationMessage = self::formPhrase('Please enter a valid screen name', array(), $translate);
							} elseif ((userId() && checkRowExists('users', array('screen_name' => $data[$fieldName], 'id' => array('!' => userId())))) || (!userId() && checkRowExists('users', array('screen_name' => $data[$fieldName])))) {
								$validationMessage = self::formPhrase('The screen name you entered is in use', array(), $translate);
							}
							break;
					}
					if ($validationMessage && $field['validation_message'] && ($field['validation'] != 'screen_name')) {
						$validationMessage = self::formPhrase($field['validation_message'], array(), $translate);
					}
				}
				if ($validationMessage) {
					$requiredFields[$fieldId] = array('name' => $fieldName, 'message' => $validationMessage, 'type' => $type);
				}
				
			} elseif ($type == 'attachment') {
				$fileFields[] = $fieldName;
			}
			
			// If this field relies on another field, check if it should be set to mandatory
			if ($field['mandatory_condition_field_id'] && isset($formFields[$field['mandatory_condition_field_id']]) && ($field['mandatory_condition_field_value'] !== null)) {
				$requiredFieldId = $field['mandatory_condition_field_id'];
				$requiredField = $formFields[$requiredFieldId];
				$requiredFieldName = self::getFieldName($requiredField);
				$requiredFieldType = self::getFieldType($requiredField);
				switch($requiredFieldType) {
					case 'checkbox':
						if ($field['mandatory_condition_field_value'] == 1) {
							if (isset($data[$requiredFieldName])) {
								$field['is_required'] = true;
							}
						} elseif ($field['mandatory_condition_field_value'] == 0) {
							if (!isset($data[$requiredFieldName])) {
								$field['is_required'] = true;
							}
						}
						break;
					case 'radios':
					case 'centralised_radios':
					case 'centralised_select':
					case 'select':
						if (isset($data[$requiredFieldName]) && $data[$requiredFieldName] === $field['mandatory_condition_field_value']) {
							$field['is_required'] = true;
						}
						break;
				}
			}
			if ($field['is_required']) {
				switch ($type){
					case 'group':
					case 'checkbox':
						if (!isset($data[$fieldName])) {
							$requiredFields[$fieldId] = array('label' => $field['label'], 'message' => self::formPhrase($field['required_error_message'], array(), $translate), 'type' => $type);
						}
						break;
					case 'checkboxes':
						$isChecked = false;
						if ($userFieldId) {
							$valuesList = getDatasetFieldLOV($userFieldId);
						} else {
							$valuesList = self::getUnlinkedFieldLOV($fieldId);
						}
						foreach ($valuesList as $valueId => $label) {
							if (isset($data[$valueId. '_' . $fieldName])) {
								$isChecked = true;
								break;
							}
						}
						
						if (!$isChecked) {
							$requiredFields[$fieldId] = array('label' => $field['label'], 'message' => self::formPhrase($field['required_error_message'], array(), $translate), 'type' => $type);
						}
						break;
					case 'text':
					case 'date':
					case 'editor':
					case 'textarea':
					case 'url':
						if ($data[$fieldName] === '' || $data[$fieldName] === false) {
							$requiredFields[$fieldId] = array('label' => $field['label'], 'message' => self::formPhrase($field['required_error_message'], array(), $translate), 'type' => $type);
						}
						break;
					case 'radios':
					case 'centralised_radios':
					case 'centralised_select':
					case 'select':
						if (!isset($data[$fieldName]) || $data[$fieldName] === '') {
							$requiredFields[$fieldId] = array('label' => $field['label'], 'message' => self::formPhrase($field['required_error_message'], array(), $translate), 'type' => $type);
						}
						break;
					case 'attachment':
						if ((!isset($_FILES[$fieldName]) && empty($_FILES[$fieldName]['tmp_name']))
							&& !isset($data[$fieldName]) && !empty($data[$fieldName])) {
							$requiredFields[$fieldId] = array('label' => $field['label'], 'message' => self::formPhrase($field['required_error_message'], array(), $translate), 'type' => $type);
						}
						break;
					
				}
			}
			
			// If form does not allow more than 1 submission per person, show error on email field
			if (($field['db_column'] == 'email') && $formProperties['save_data'] && ($formProperties['user_duplicate_email_action'] == 'stop') && $formProperties['duplicate_email_address_error_message']) {
				$userId = getRow('users', 'id', array('email' => $data[$fieldName]));
				if (checkRowExists(ZENARIO_USER_FORMS_PREFIX. 'user_response', array('user_id' => $userId, 'form_id' => $userFormId))) {
					$requiredFields[$fieldId] = array('label' => $field['label'], 'message' => self::formPhrase($formProperties['duplicate_email_address_error_message'], array(), $translate), 'type' => $type);
				}
			}
		}
		// If there are files and validation failed, save the file to cache and set in POST
		foreach ($fileFields as $key => $fieldName) {
			if (isset($_FILES[$fieldName]) && is_uploaded_file($_FILES[$fieldName]['tmp_name']) && cleanDownloads()) {
				$randomDir = createRandomDir(30, 'uploads');
				$newName = $randomDir. preg_replace('/\.\./', '.', preg_replace('/[^\w\.-]/', '', $_FILES[$fieldName]['name'])).'.upload';
				if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], CMS_ROOT. $newName)) {
					chmod(CMS_ROOT. $newName, 0666);
					$_POST[$fieldName] = $_REQUEST[$fieldName] = $newName;
				}
			}
			//Stop the user trying to trick the CMS into submitting a different file in a different location
			if (!empty($_POST[$fieldName])) {
				if (strpos($_POST[$fieldName], '..') !== false
				 || !preg_match('@^cache/uploads/\w+/[\w\.-]+\.upload$@', $_POST[$fieldName])) {
					unset($_POST[$fieldName]);
				}
			}
			unset($_GET[$fieldName]);
		}
		
		return $requiredFields;
	}
	
	public static function filterFormFieldsByPage(&$formFields, $pageNo) {
		$currentPageNo = 1;
		foreach ($formFields as $fieldId => $field) {
			if ($pageNo != $currentPageNo) {
				unset($formFields[$fieldId]);
			}
			if ($field['field_type'] == 'page_break') {
				unset($formFields[$fieldId]);
				$currentPageNo++;
			}
		}
	}
	
	public static function saveUserForm($userFormId, $data, $userId, $url = false, $breadCrumbs = false) {
		$formFields = self::getUserFormFields($userFormId);
		
		// Get form extra properties
		$formProperties = getRow('user_forms',
			array(
				'name',
				'send_email_to_user',
				'user_email_template',
				'send_email_to_admin',
				'admin_email_use_template',
				'admin_email_addresses',
				'admin_email_template',
				'reply_to',
				'reply_to_email_field',
				'reply_to_first_name',
				'reply_to_last_name',
				'save_data',
				'save_record',
				'send_signal',
				'redirect_after_submission',
				'redirect_location',
				'user_status',
				'log_user_in',
				'log_user_in_cookie' ,
				'add_user_to_group',
				'user_duplicate_email_action',
				'create_another_form_submission_record',
				'use_captcha',
				'captcha_type',
				'extranet_users_use_captcha'),
			array('id' => $userFormId));
		
		$values = array();
		$checkBoxValues = array();
		$user_fields = array();
		$user_custom_fields = array();
		$unlinked_fields = array();
		$user_multi_value_fields = false;
		$email_field = false;
		$duplicate_email_found = false;
		
		$fieldIdValueLink = array();
		
		foreach ($formFields as $fieldId => $field) {
			$userFieldId = $field['user_field_id'];
			$fieldName = self::getFieldName($field);
			$type = self::getFieldType($field);
			$ordinal = $field['ordinal'];
			// Ignore field if readonly
			if ($field['is_readonly']){
				continue;
			}
			
			if ($field['is_system_field']){
				$valueType = 'system';
				$values = &$user_fields;
			} elseif ($field['user_field_id']) {
				$valueType = 'custom';
				$values = &$user_custom_fields;
			} else {
				$valueType = 'unlinked';
				$values = &$unlinked_fields;
			}
			switch ($type){
				case 'group':
				case 'checkbox':
					if (isset($data[$fieldName])) {
						$checked = 1;
						$eng = adminPhrase('Yes');
					} else {
						$checked = 0;
						$eng = adminPhrase('No');
					}
					
					$values[$fieldId] = array('value' => $eng, 'internal_value' => $checked, 'db_column' => $fieldName, 'ordinal' => $ordinal);
					$fieldIdValueLink[$fieldId] = $checked;
					break;
				case 'checkboxes':
					$internal_values = array();
					$label_values = array();
					
					if ($userFieldId) {
						$valuesList = getDatasetFieldLOV($userFieldId);
					} else {
						$valuesList = self::getUnlinkedFieldLOV($fieldId);
					}
					$fieldIdValueLink[$fieldId] = array();
					foreach ($valuesList as $valueId => $label) {
						$selected = isset($data[$valueId. '_'. $fieldName]);
						if ($selected) {
							$internal_values[] = $valueId;
							$label_values[] = $label;
							$fieldIdValueLink[$fieldId][$valueId] = $label;
						}
					}
					// Store checkbox values to save in record
					$checkBoxValues[$fieldId] = array(
						'internal_value' => implode(',',$internal_values), 
						'value' => implode(',',$label_values), 
						'ordinal' => $ordinal, 
						'db_column' => $fieldName,
						'value_type' => $valueType,
						'user_field_id' => $userFieldId,
						'type' => $type);
					break;
				case 'date':
					$date = '';
					if ($data[$fieldName]) {
						$date = DateTime::createFromFormat('d/m/Y', $data[$fieldName]);
						$date = $date->format('Y-m-d');
					}
					$values[$fieldId] = array('value' => $date, 'db_column' => $fieldName, 'ordinal' => $ordinal);
					$fieldIdValueLink[$fieldId] = $date;
					break;
				case 'radios':
				case 'select':
				case 'centralised_radios':
				case 'centralised_select':
					if ($userFieldId) {
						$valuesList = getDatasetFieldLOV($userFieldId);
					} else {
						$valuesList = self::getUnlinkedFieldLOV($fieldId);
					}
					$fieldIdValueLink[$fieldId] = array();
					if (!empty($data[$fieldName])) {
						$fieldIdValueLink[$fieldId][$data[$fieldName]] = $valuesList[$data[$fieldName]];
						$values[$fieldId] = array(
							'value' => $valuesList[$data[$fieldName]], 
							'db_column' => $fieldName, 
							'internal_value' => $data[$fieldName], 
							'ordinal' => $ordinal);
					}
					break;
				
				case 'text':
				case 'url':
				case 'calculated':
					$value = $data[$fieldName] ? $data[$fieldName] : '';
					switch ($field['db_column']) {
						case 'salutation':
							$value = substr($value, 0, 25);
							break;
						case 'screen_name':
						case 'password':
							$value = substr($value, 0, 50);
							break;
						case 'first_name':
						case 'last_name':
						case 'email':
							$value = substr($value, 0, 100);
							break;
						default:
							$value = substr($value, 0, 255);
					}
					$values[$fieldId] = array('value' => $value, 'db_column' => $fieldName, 'ordinal' => $ordinal);
					$fieldIdValueLink[$fieldId] = $data[$fieldName];
					break;
				case 'editor':
				case 'textarea':
					$value = $data[$fieldName] ? $data[$fieldName] : '';
					$values[$fieldId] = array('value' => $value, 'db_column' => $fieldName, 'ordinal' => $ordinal);
					$fieldIdValueLink[$fieldId] = $data[$fieldName];
					break;
				case 'attachment':
					$fileId = false;
					if (!empty($data[$fieldName]) && file_exists(CMS_ROOT.$data[$fieldName])) {
						$filename = substr(basename($data[$fieldName]), 0, -7);
						$fileId = addFileToDatabase('forms', CMS_ROOT.$data[$fieldName], $filename);
						$values[$fieldId] = array('value' => $filename, 'internal_value' => $fileId, 'db_column' => $fieldName, 'ordinal' => $ordinal, 'attachment' => true);
					}
					$fieldIdValueLink[$fieldId] = $fileId;
					break;
			}
			if (isset($values[$fieldId])) {
				$values[$fieldId]['type'] = $type;
			}
		}
		
		$zenario_extranet = inc('zenario_extranet');
		
		// Save data against user record
		if ($formProperties['save_data'] && $zenario_extranet) {
			$fields = array();
			foreach ($user_fields as $fieldData) {
				$fields[$fieldData['db_column']] = $fieldData['value'];
			}
			
			// Try to save data if email field is on form
			if (isset($fields['email']) || $userId) { 
				// Duplicate email found
				if ($userId || $userId = getRow('users', 'id', array('email' => $fields['email']))) {
					$duplicate_email_found = true;
					switch ($formProperties['user_duplicate_email_action']) {
						case 'merge': // Don’t delete previously populated fields
							$fields['modified_date'] = now();
							self::mergeUserData($fields, $userId, $formProperties['log_user_in']);
							self::mergeUserCustomData($user_custom_fields, $userId);
							self::mergeUserMultiCheckboxData($checkBoxValues, $userId);
							break;
						case 'overwrite': // Delete previously populated fields
							$fields['modified_date'] = now();
							$userId = self::saveUser($fields, $userId);
							self::saveUserCustomData($user_custom_fields, $userId);
							self::saveUserMultiCheckboxData($checkBoxValues, $userId);
							break;
						case 'ignore': // Don’t update any fields
							break;
					}
				// No duplicate email found
				} elseif (!empty($fields['email']) && validateEmailAddress($fields['email'])) {
					// Set new user fields
					$fields['status'] = $formProperties['user_status'];
					$fields['password'] = createPassword();
					$fields['ip'] = visitorIP();
					if (!empty($fields['screen_name'])) {
						$fields['screen_name_confirmed'] = true;
					}
					// Create new user
					$userId = self::saveUser($fields);
					
					// Save new user custom data
					self::saveUserCustomData($user_custom_fields, $userId);
				}
				if ($userId) {
					addUserToGroup($userId, $formProperties['add_user_to_group']);
					// Log user in
					if ($formProperties['log_user_in']) {
						
						$user = logUserIn($userId);
						
						if($formProperties['log_user_in_cookie'] && canSetCookie()) {
							setcookie('LOG_ME_IN_COOKIE', $user['login_hash'], time()+8640000, '/', cookieDomain());
						}
					}
				}
			}
		}
		
		// Save a record of the submission
		$user_response_id = false;
		if ($formProperties['save_record']) {
			// Save record only if there is no duplicate response by the identified user
			// Or if there is a response but the appropriate options have been checked,
			// Or no user could be found from the data
			if (!$userId ||
				!$formProperties['save_data'] || 
				!checkRowExists(ZENARIO_USER_FORMS_PREFIX. 'user_response', array('user_id' => $userId)) || 
				(checkRowExists(ZENARIO_USER_FORMS_PREFIX. 'user_response', array('user_id' => $userId)) 
					&& $formProperties['create_another_form_submission_record'])) 
				{
				// Create new response with values
				$user_response_id = 
					insertRow(ZENARIO_USER_FORMS_PREFIX. 'user_response', 
						array('user_id' => $userId, 'form_id' => $userFormId, 'response_datetime' => now()));
				
				$values = $user_fields + $user_custom_fields + $unlinked_fields;
				
				// Single value form fields
				foreach ($values as $fieldId => $fieldData) {
					$response_record = array('user_response_id' => $user_response_id, 'form_field_id' => $fieldId, 'value' => $fieldData['value']);
					if (isset($fieldData['internal_value'])) {
						$response_record['internal_value'] = $fieldData['internal_value'];
					}
					insertRow(ZENARIO_USER_FORMS_PREFIX. 'user_response_data', $response_record);
				}
				// Multi value form fields (checkboxes)
				foreach ($checkBoxValues as $fieldId => $checkedBoxesList) {
					insertRow(ZENARIO_USER_FORMS_PREFIX. 'user_response_data', 
						array(
							'user_response_id' => $user_response_id, 
							'form_field_id' => $fieldId, 
							'internal_value' => $checkedBoxesList['internal_value'], 
							'value' => $checkedBoxesList['value']));
				}
			}
		}
		
		// Send an email to the user
		$emailMergeFields = false;
		if (($formProperties['send_email_to_user'] && $formProperties['user_email_template'] && isset($data['email'])) || ($formProperties['send_email_to_admin'] && $formProperties['admin_email_addresses'])) {
			
			$values = $user_fields + $user_custom_fields + $checkBoxValues + $unlinked_fields;
			$emailMergeFields = self::getTemplateEmailMergeFields($values);
			if ($userId) {
				if (setting('plaintext_extranet_user_passwords')) {
					$userDetails = getUserDetails($userId);
					$emailMergeFields['password'] = $userDetails['password'];
				}
				$emailMergeFields['user_id'] = $userId;
			}
		}
		if ($formProperties['send_email_to_user'] && $formProperties['user_email_template'] && isset($data['email'])) {
			// Send email
			zenario_email_template_manager::sendEmailsUsingTemplate($data['email'], $formProperties['user_email_template'], $emailMergeFields, array());
		}
		
		// Send an email to administrators
		if ($formProperties['send_email_to_admin'] && $formProperties['admin_email_addresses']) {
			// Set reply to address and name
			$replyToEmail = false;
			$replyToName = false;
			if ($formProperties['reply_to'] && $formProperties['reply_to_email_field']) {
				if (isset($data[$formProperties['reply_to_email_field']])) {
					$replyToEmail = $data[$formProperties['reply_to_email_field']];
					$replyToName = '';
					if (isset($data[$formProperties['reply_to_first_name']])) {
						$replyToName .= $data[$formProperties['reply_to_first_name']];
					}
					if (isset($data[$formProperties['reply_to_last_name']])) {
						$replyToName .= ' '.$data[$formProperties['reply_to_last_name']];
					}
					if (!$replyToName) {
						$replyToName = $replyToEmail;
					}
				}
			}
			
			// Send email
			if ($formProperties['admin_email_use_template'] && $formProperties['admin_email_template']) {
				zenario_email_template_manager::sendEmailsUsingTemplate(
					$formProperties['admin_email_addresses'],
					$formProperties['admin_email_template'],
					$emailMergeFields,
					array(),
					array(),
					false,
					$replyToEmail,
					$replyToName);
			} else {
				$emailValues = array();
				$values = $user_fields + $user_custom_fields + $checkBoxValues + $unlinked_fields;
				foreach ($values as $fieldId => $fieldData) {
					if (isset($fieldData['attachment'])) {
						$fieldData['value'] = absCMSDirURL().fileLink($fieldData['internal_value']);
					}
					if (!empty($fieldData['type']) && ($fieldData['type'] == 'textarea') && $fieldData['value']) {
						$fieldData['value'] = '<br/>' . nl2br($fieldData['value']);
					}
					$emailValues[$fieldData['ordinal']] = array($formFields[$fieldId]['name'], $fieldData['value']);
				}
				ksort($emailValues);
				
				$formName = trim($formProperties['name']);
				$formName = empty($formName) ? phrase('[blank name]', array(), 'zenario_user_forms') : $formProperties['name'];
				$body =
					'<p>Dear admin,</p>
					<p>The form "'.$formName.'" was submitted with the following data:</p>';
				if ($breadCrumbs) {
					$body .= '<p>Page submitted from: '. $breadCrumbs .'</p>';
				}
				foreach ($emailValues as $ordinal => $value) {
					$body .= '<p>'.trim($value[0], " \t\n\r\0\x0B:").': '.$value[1].'</p>';
				}
				if (!$url) {
					$url = absCMSDirURL();
				}
				
				$body .= '<p>This is an auto-generated email from '.$url.'</p>';
				$recipients = $formProperties['admin_email_addresses'];
				$subject = phrase('New form submission for: [[name]]', array('name' => $formName), 'zenario_user_forms');
				$addressFrom = setting('email_address_from');
				$nameFrom = setting('email_name_from');
			
				zenario_email_template_manager::sendEmails(
					$recipients,
					$subject,
					$addressFrom,
					$nameFrom,
					$body,
					array(),
					array(),
					array(),
					0,
					false,
					$replyToEmail,
					$replyToName);
			}
		}
		
		// Send a signal if specified
		if ($formProperties['send_signal']) {
			$formProperties['user_form_id'] = $userFormId;
			$values = $user_fields + $user_custom_fields + $checkBoxValues + $unlinked_fields;
			$formattedData = self::getTemplateEmailMergeFields($values);
			$params = array(
				'data' => $formattedData, 
				'rawData' => $data, 
				'formProperties' => $formProperties, 
				'fieldIdValueLink' => $fieldIdValueLink);
			if ($user_response_id) {
				$params['responseId'] = $user_response_id;
			}
			sendSignal('eventUserFormSubmitted', $params);
		}
		// Redirect to page if speficied
		if ($formProperties['redirect_after_submission'] && $formProperties['redirect_location']) {
			$cId = $cType = false;
			getCIDAndCTypeFromTagId($cId, $cType, $formProperties['redirect_location']);
			langEquivalentItem($cId, $cType);
			return linkToItem($cId, $cType);
		}
		return false;
	}
	
	protected static function getTemplateEmailMergeFields($values) {
		$emailMergeFields = array();
		foreach($values as $fieldId => $fieldData) {
			if (isset($fieldData['attachment'])) {
				$fieldData['value'] = absCMSDirURL().fileLink($fieldData['internal_value']);
			}
			if (!empty($fieldData['type']) && ($fieldData['type'] == 'textarea') && $fieldData['value']) {
				$fieldData['value'] = $fieldData['value'];
			}
			$emailMergeFields[$fieldData['db_column']] = $fieldData['value'];
		}
		$emailMergeFields['cms_url'] = absCMSDirURL();
		return $emailMergeFields;
	}
	
	protected static function mergeUserData($fields, $userId, $login) {
		$userDetails = getUserDetails($userId);
		$mergeFields = array();
		foreach ($fields as $fieldName => $value) {
			if (isset($userDetails[$fieldName]) && empty($userDetails[$fieldName])) {
				$mergeFields[$fieldName] = $value;
			}
		}
		if ($login) {
			$mergeFields['status'] = 'active';
		}
		saveUser($mergeFields, $userId);
	}
	
	protected static function mergeUserCustomData($fields, $userId) {
		$userDetails = getUserDetails($userId);
		$mergeFields = array();
		foreach($fields as $fieldId => $fieldData) {
			if (isset($userDetails[$fieldData['db_column']]) && empty($userDetails[$fieldData['db_column']])) {
				$mergeFields[$fieldData['db_column']] = ((isset($fieldData['internal_value'])) ? $fieldData['internal_value'] : $fieldData['value']);
			}
		}
		updateRow('users_custom_data', $mergeFields, array('user_id' => $userId));
	}
	
	protected static function mergeUserMultiCheckboxData($checkboxValues, $userId) {
		$dataset = getDatasetDetails('users');
		foreach ($checkboxValues as $fieldId => $fieldData) {
			if ($fieldData['value_type'] != 'unlinked') {
				$valuesList = getDatasetFieldLOV($fieldData['user_field_id']);
				$valueFound = false;
				// Find if this field has been previously completed
				foreach ($valuesList as $id => $label) {
					if (checkRowExists('custom_dataset_values_link', array('dataset_id' => $dataset['id'], 'value_id' => $id, 'linking_id' => $userId))) {
						$valueFound = true;
					}
				}
				// If no values found, save data
				if (!$valueFound && $fieldData['internal_value']) {
					$valuesList = explode(',', $fieldData['internal_value']);
					foreach ($valuesList as $value) {
						insertRow('custom_dataset_values_link', array('dataset_id' => $dataset['id'], 'value_id' => $value, 'linking_id' => $userId));
					}
				}
			}
		}
	}
	
	protected static function saveUser($fields, $userId = false) {
		$newId = saveUser($fields, $userId);
		
		if ($userId) {
			sendSignal(
				'eventUserModified',
				array('id' => $userId));
		} else {
			sendSignal(
				'eventUserCreated',
				array('id' => $newId));
		}
		return $newId;
	}
	
	protected static function saveUserCustomData($user_custom_fields, $userId) {
		
		$sql = "";
		foreach ($user_custom_fields as $fieldId => $fieldData) {
			if ($sql) {
				$sql .= ',';
			}
			$fieldValue = ((isset($fieldData['internal_value'])) ? $fieldData['internal_value'] : $fieldData['value']);
			$sql .= '`' . sqlEscape($fieldData['db_column']) . "`='" . sqlEscape($fieldValue) . "'";
		}
		if ($sql) {
			if (!checkRowExists('users_custom_data', array('user_id' => $userId))) {
				insertRow('users_custom_data', array('user_id' => $userId));
			}
			$sql = "UPDATE ". DB_NAME_PREFIX. "users_custom_data SET " . $sql .
			" WHERE user_id=" . (int)$userId;
			sqlQuery($sql);
		}
	}
	
	protected static function saveUserMultiCheckboxData($checkBoxValues, $userId) {
		$dataset = getDatasetDetails('users');
		foreach ($checkBoxValues as $fieldId => $fieldData) {
			if ($fieldData['value_type'] != 'unlinked') {
				$valuesList = getDatasetFieldLOV($fieldData['user_field_id']);
				// Delete current saved values
				foreach ($valuesList as $id => $label) {
					deleteRow('custom_dataset_values_link', array('dataset_id' => $dataset['id'], 'value_id' => $id, 'linking_id' => $userId));
				}
				// Save new values
				if ($fieldData['internal_value']) {
					$valuesList = explode(',', $fieldData['internal_value']);
					foreach ($valuesList as $value) {
						insertRow('custom_dataset_values_link', array('dataset_id' => $dataset['id'], 'value_id' => $value, 'linking_id' => $userId));
					}
				}
			}
		}
	}
	
	private static function getFormModuleIds() {
		$ids = array();
		$formModuleClassNames = array('zenario_user_forms', 'zenario_extranet_profile_edit');
		foreach($formModuleClassNames as $moduleClassName) {
			if ($id = getModuleIdByClassName($moduleClassName)) {
				$ids[] = $id;
			}
		}
		return $ids;
	}
	
	private static function getModuleClassNameByInstanceId($id) {
		$sql = '
			SELECT class_name
			FROM '.DB_NAME_PREFIX.'modules m
			INNER JOIN '.DB_NAME_PREFIX.'plugin_instances pi
				ON m.id = pi.module_id
			WHERE pi.id = '.(int)$id;
		$result = sqlSelect($sql);
		$row = sqlFetchRow($result);
		return $row[0];
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__content/panels/content':
				// Get plugins using this form
				$moduleIds = self::getFormModuleIds();
				
				$sqlJoin = '
					INNER JOIN '.DB_NAME_PREFIX.'plugin_item_link pil
						ON (c.id = pil.content_id) AND (c.type = pil.content_type) AND (c.admin_version = pil.content_version)
					INNER JOIN '.DB_NAME_PREFIX.'plugin_settings ps 
						ON (pil.instance_id = ps.instance_id) AND (ps.name = \'user_form\')';
				$sqlWhere = '
					pil.module_id IN ('. inEscape($moduleIds, 'numeric'). ')
						AND ps.value = '.(int)$refinerId;
						
				$panel['refiners']['form_id']['table_join'] = $sqlJoin;
				$panel['refiners']['form_id']['sql'] = $sqlWhere;
				break;
			case 'zenario__user_forms/panels/zenario_user_forms__forms':
				if ($refinerName == 'archived') {
					unset($panel['db_items']['where_statement']);
				}
				break;
			case 'zenario__user_forms/panels/zenario_user_forms__form_fields':
				$record = getRow('user_forms', array('name'), array('id' => $refinerId));
				$panel['title'] = 'Form fields for "' . $record['name'] . '"';
				break;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__user_forms/panels/zenario_user_forms__forms':
				
				if ($refinerName == 'email_address_setting') {
					unset($panel['collection_buttons']);
					$panel['title'] = adminPhrase('Summary of email addresses used by forms');
					$panel['no_items_message'] = adminPhrase('No forms send emails to a specific address.');
				} else {
					unset($panel['columns']['form_email_addresses']);
				}
				
				// Get plugins using a form
				$moduleIds = self::getFormModuleIds();
				$formPlugins = array();
				$sql = '
					SELECT id, name
					FROM '.DB_NAME_PREFIX.'plugin_instances
					WHERE module_id IN ('. inEscape($moduleIds, 'numeric'). ')
					ORDER BY name';
				
				$result = sqlSelect($sql);
				while ($row = sqlFetchAssoc($result)) {
					$formPlugins[$row['id']] = $row['name'];
				}
				
				// Get content items with a plugin using a form on
				$formUsage = array();
				$contentItemUsage = array();
				$sql = '
					SELECT pil.content_id, pil.content_type, pil.instance_id
					FROM '.DB_NAME_PREFIX.'plugin_item_link pil
					INNER JOIN '.DB_NAME_PREFIX.'content c
						ON (pil.content_id = c.id) AND (pil.content_type = c.type) AND (pil.content_version = c.admin_version)
					WHERE c.status NOT IN (\'trashed\',\'deleted\')
					AND pil.module_id IN ('. inEscape($moduleIds, 'numeric'). ')
					GROUP BY pil.content_id, pil.content_type';
				
				$result = sqlSelect($sql);
				while ($row = sqlFetchAssoc($result)) {
					$tagId = formatTag($row['content_id'], $row['content_type']);
					$contentItemUsage[$row['instance_id']][] = $tagId;
				}
				
				foreach($formPlugins as $instanceId => $pluginName) {
					$className = self::getModuleClassNameByInstanceId($instanceId);
					$moduleName = getModuleDisplayNameByClassName($className);
					
					if ($formId = getRow('plugin_settings', 'value', array('instance_id' => $instanceId, 'name' => 'user_form'))) {
						$details = array('pluginName' => $pluginName, 'moduleName' => $moduleName);
						if (isset($contentItemUsage[$instanceId])) {
							$details['contentItems'] = $contentItemUsage[$instanceId];
						}
						$formUsage[$formId][] = $details;
					}
				}
				
				foreach($panel['items'] as $id => &$item) {
					$pluginUsage = '';
					$contentUsage = '';
					$moduleNames = array();
					if (isset($formUsage[$id]) && !empty($formUsage[$id])) {
						$pluginUsage = '"'.$formUsage[$id][0]['pluginName'].'"';
						if (($count = count($formUsage[$id])) > 1) {
							$plural = (($count - 1) == 1) ? '' : 's';
							$pluginUsage .= ' and '.($count - 1).' other plugin'.$plural;
						}
						$count = 0;
						foreach($formUsage[$id] as $plugin) {
							$moduleNames[$plugin['moduleName']] = $plugin['moduleName'];
							if (isset($plugin['contentItems'])) {
								if (empty($contentUsage)) {
									$contentUsage = '"'.$plugin['contentItems'][0].'"';
								}
								$count += count($plugin['contentItems']);
							}
						}
						if ($count > 1) {
							$plural = (($count - 1) == 1) ? '' : 's';
							$contentUsage .= ' and '.($count - 1).' other item'.$plural;
							
						}
					}
					$item['plugin_module_name'] = implode(', ', $moduleNames);
					$item['plugin_usage'] = $pluginUsage;
					$item['plugin_content_items'] = $contentUsage;
				}
				break;
			case 'zenario__user_forms/panels/zenario_user_forms__form_fields':
				
				foreach ($panel['items'] as $id => &$item){
					
					$item['css_class'] = 'zenario_char_'. $item['field_type'];
					
					
					if (in_array($item['field_type'], array('checkboxes', 'radios', 'centralised_radios', 'select', 'centralised_select'))) {
						if ($item['user_field_id']) {
							$field_values = getDatasetFieldLOV($item['user_field_id']);
						} else {
							$field_values = self::getUnlinkedFieldLOV($item['id']);
						}
						$item['values_list'] = implode(', ', $field_values);
					} else {
						$item['values_list'] = 'n/a';
					}
				}
				
				$formStatus = getRow('user_forms', 'status', array('id' => $refinerId));
				if ($formStatus == 'archived') {
					unset($panel['collection_buttons']);
					unset($panel['item_buttons']['delete']);
				} else {
					$panel['collection_buttons']['add_field']['admin_box']['key']['form_id'] = 
					$panel['collection_buttons']['add_field_user_characteristic']['admin_box']['key']['form_id'] = 
					$panel['collection_buttons']['add_section_description']['admin_box']['key']['form_id'] = 
						$refinerId;
				}
				$panel['item_buttons']['edit']['admin_box']['key']['form_id'] = $refinerId;
				break;
			case 'zenario__user_forms/panels/zenario_user_forms__user_responses':
				
				if (!self::isFormCRMEnabled($refinerId)) {
					unset($panel['columns']['crm_response']);
				}
				
				$panel['item_buttons']['view_response']['admin_box']['key']['form_id'] = $refinerId;
				
				$sql = '
					SELECT id, name
					FROM '.DB_NAME_PREFIX.'user_form_fields
					WHERE user_form_id = '.(int)$refinerId.'
					AND (field_type NOT IN (\'page_break\', \'section_description\', \'restatement\') OR field_type IS NULL)
					ORDER BY ordinal';
				
				$result = sqlSelect($sql);
				while ($formField = sqlFetchAssoc($result)) {
					$panel['columns']['form_field_'.$formField['id']] = array(
						'title' => $formField['name'],
						'show_by_default' => true,
						'searchable' => true,
						'sortable' => true);
				}
				
				$responses = array();
				$sql = '
					SELECT urd.value, urd.form_field_id, ur.id
					FROM '. DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX .'user_response_data AS urd
					INNER JOIN '. DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX .'user_response AS ur
						ON urd.user_response_id = ur.id
					WHERE ur.form_id = '. (int)$refinerId;
				$result = sqlQuery($sql);
				while ($row = sqlFetchAssoc($result)) {
					$responses[] = $row;
					$panel['items'][$row['id']]['form_field_'.$row['form_field_id']] = $row['value'];
				}
				
				break;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__user_forms/panels/zenario_user_forms__forms':
				if (post('archive_form')) {
					foreach(explode(',', $ids) as $id) {
						updateRow('user_forms', array('status' => 'archived'), array('id' => $id));
					}
				}
				if (post('delete_form')) {
					$moduleIds = self::getFormModuleIds();
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
					
					foreach (explode(',', $ids) as $id) {
						// Delete form if no responses and not used in a plugin
						$formInUse = false;
						foreach ($instanceIds as $instanceId) {
							if (checkRowExists('plugin_settings', array('instance_id' => $instanceId, 'name' => 'user_form', 'value' => $id))) {
								$formInUse = true;
							}
						}
						if (checkRowExists(ZENARIO_USER_FORMS_PREFIX.'user_response', array('form_id' => $id)) || $formInUse) {
							echo 'Only forms not used in plugins and without any responses logged can be deleted!';
							return;
						}
						deleteRow('user_forms', $id);
					}
				}
				
				if (post('duplicate_form')) {
					$formProperties = getRow('user_forms', true, $ids);
					$formFields = getRowsArray('user_form_fields', true, array('user_form_id' => $ids));
					$formNameArray = explode(' ', $formProperties['name']);
					$formVersion = end($formNameArray);
					// Remove version number at end of field
					if (preg_match('/\((\d+)\)/', $formVersion, $matches)) {
						array_pop($formNameArray);
						$formProperties['name'] = implode(' ', $formNameArray);
					}
					for ($i = 2; $i < 1000; $i++) {
						$name = $formProperties['name'].' ('.$i.')';
						if (!checkRowExists('user_forms', array('name' => $name))) {
							$formProperties['name'] = $name;
							break;
						}
					}
					
					unset($formProperties['id']);
					$id = insertRow('user_forms', $formProperties);
					foreach ($formFields as $formField) {
						$formFieldValues = getRowsArray(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', true, array('form_field_id' => $formField['id']));
						unset($formField['id']);
						$formField['user_form_id'] = $id;
						$fieldId = insertRow('user_form_fields', $formField);
						// Duplicate form field values if any
						foreach ($formFieldValues as $field) {
							$field['form_field_id'] = $fieldId;
							unset($field['id']);
							insertRow(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', $field);
						}
					}
				}
				break;
			case 'zenario__user_forms/panels/zenario_user_forms__form_fields':
				$formId = (int)post('refiner__user_form_id');
				if (post('reorder')) {
					// Update ordinals
					$ids = explode(',', $ids);
					foreach ($ids as $id) {
						if (!empty($_POST['ordinals'][$id])) {
							$sql = "
								UPDATE ". DB_NAME_PREFIX. "user_form_fields SET
									ordinal = ". (int) $_POST['ordinals'][$id]. "
								WHERE id = ". (int) $id . 
								" AND user_form_id=" . (int)$formId;
							sqlUpdate($sql);
						}
					}
					// Update div wrapper class
					if ($droppedItemId = post('dropped_item')) {
						if (!empty($_POST['ordinals'][$droppedItemId])) {
							$ord = $_POST['ordinals'][$droppedItemId];
							$field = getRow('user_form_fields', array('div_wrap_class', 'field_type'), $droppedItemId);
							$fieldBelow = getRow('user_form_fields', array('div_wrap_class'), array('user_form_id' => $formId, 'ordinal' => $ord + 1));
							if ($field['field_type'] != 'page_break') {
								if ($fieldBelow && ($fieldBelow['div_wrap_class'] === $field['div_wrap_class'])) {
									// Keep current class
								} elseif ($fieldAbove = getRow('user_form_fields', array('div_wrap_class'), array('user_form_id' => $formId, 'ordinal' => $ord - 1))) {
									updateRow('user_form_fields', array('div_wrap_class' => $fieldAbove['div_wrap_class']), $droppedItemId);
								}
							}
						}
					}
					return;
				} elseif (post('delete')) {
					$ids = explode(',', $ids);
					foreach ($ids as $id) {
						$message = false;
						$targetName = '';
						$dependsField = '';
						// Remove deleted fields if not being used
						if (($dependsField = getRow('user_form_fields', array('id','name'), array('restatement_field' => $id, 'user_form_id' => $formId))) && !in_array($dependsField['id'], $ids)) {
							$message = true;
						} elseif (($dependsField = getRow('user_form_fields', array('id','name'), array('numeric_field_1' => $id, 'user_form_id' => $formId))) && !in_array($dependsField['id'], $ids)) {
							$message = true;
						} elseif (($dependsField = getRow('user_form_fields', array('id','name'), array('numeric_field_2' => $id, 'user_form_id' => $formId))) && !in_array($dependsField['id'], $ids)) {
							$message = true;
						} else {
							deleteRow('user_form_fields', array('id' =>$id, 'user_form_id' => $formId));
						}
						
						if ($message) {
							$targetName = getRow('user_form_fields', 'name', $id);
							echo 'Unable to delete the field "'.$targetName.'" as the field "'.$dependsField['name'].'" depends on it.<br/>';
						}
					}
					// Update remaining field ordinals
					
					$formFieldIds = getRowsArray('user_form_fields', 'id', array('user_form_id' => $refinerId), 'ordinal');
					$ordinal = 0;
					foreach($formFieldIds as $id) {
						$ordinal++;
						updateRow('user_form_fields', array('ordinal' => $ordinal), array('id' => $id));
					}
					
				} elseif (post('add_page_break')) {
					$record = array();
					$record['ordinal'] = self::getMaxOrdinalOfFormFields($formId) + 1;
					$record['name'] = 'Page break '.(self::getPageBreakCount($formId) + 1);
					$record['field_type'] = 'page_break';
					$record['user_form_id'] = $formId;
					$record['next_button_text'] = 'Next';
					$record['previous_button_text'] = 'Back';
					insertRow('user_form_fields', $record);
				}
				break;
		}
	}
	
	public static function getPageBreakCount($formId) {
		$sql = '
			SELECT COUNT(*)
			FROM '.DB_NAME_PREFIX.'user_form_fields
			WHERE field_type = \'page_break\'
			AND user_form_id = '.(int)$formId;
		$result = sqlSelect($sql);
		$row = sqlFetchRow($result);
		return $row[0];
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__user_forms/panels/zenario_user_forms__user_responses':
				if (post('download_excel')) {
					require_once CMS_ROOT. 'zenario/libraries/lgpl/PHPExcel_1_7_8/Classes/PHPExcel.php';
					$objPHPExcel = new PHPExcel();
					$formFields = getRowsArray('user_form_fields', 'name', array('user_form_id' => $refinerId), 'ordinal');
					// Write headers
					$sheet = $objPHPExcel->getActiveSheet();
					$sheet->setCellValueByColumnAndRow('A', 1, 'Date/Time Responded');
					$sheet->fromArray($formFields, NULL, 'B1');
					// Write data
					$responsesData = array();
					$sql = '
						SELECT urd.value, urd.form_field_id, uff.ordinal, ur.id
						FROM '.DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX .'user_response AS ur
						LEFT JOIN '.DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX .'user_response_data AS urd
							ON ur.id = urd.user_response_id
						LEFT JOIN '.DB_NAME_PREFIX. 'user_form_fields AS uff
							ON urd.form_field_id = uff.id
						WHERE ur.form_id = '. (int)$refinerId. '
						ORDER BY ur.response_datetime DESC';
					$result = sqlSelect($sql);
					
					while ($row = sqlFetchAssoc($result)) {
						if (!isset($responsesData[$row['id']])) {
							$responsesData[$row['id']] = array();
						}
						if (isset($formFields[$row['form_field_id']])) {
							$responsesData[$row['id']][$row['ordinal']] = $row['value'];
						}
					}
					
					$responses = getRowsArray(ZENARIO_USER_FORMS_PREFIX. 'user_response', 'response_datetime', array('form_id' => $refinerId), 'response_datetime');
					$i = 1;
					foreach ($responsesData as $responseId => &$response) {
						$i++;
						$response[0] = formatDateTimeNicely($responses[$responseId], '_MEDIUM');
						for ($j = 1; $j <= count($formFields); $j++) {
							if (!isset($response[$j])) {
								$response[$j] = '';
							}
							ksort($response);
						}
						$sheet->fromArray($response, NULL, 'A'.$i);
					}
					$formName = getRow('user_forms', 'name', array('id' => $refinerId));
					$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="'.$formName.' user responses.xls"');
					$objWriter->save('php://output');
				}
				break;
		}
	}
	
	private function isFormCRMEnabled($formId) {
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
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch($path) {
			case 'zenario_user_form_response':
				$box['title'] = adminPhrase('Form response [[id]]', array('id' => $box['key']['id']));
				$responseDetails = getRow(ZENARIO_USER_FORMS_PREFIX. 'user_response', array('response_datetime', 'crm_response'), $box['key']['id']);
				$values['response_datetime'] = formatDateTimeNicely($responseDetails['response_datetime'], 'vis_date_format_med');
				
				$crmEnabled = false;
				if (self::isFormCRMEnabled($box['key']['form_id'])) {
					$values['crm_response'] = $responseDetails['crm_response'];
				} else {
					unset($box['tabs']['form_fields']['fields']['crm_response']);
				}
				
				
				$formFields = getRowsArray('user_form_fields', array('name', 'id', 'field_type', 'ordinal'), array('user_form_id' => request('refiner__form_id')), 'ordinal');
				$userResponse = array();
				$result = getRows(ZENARIO_USER_FORMS_PREFIX. 'user_response_data',
					array('form_field_id', 'value', 'internal_value'),
					array('user_response_id' => $box['key']['id']));
				while ($row = sqlFetchAssoc($result)) {
					$userResponse[$row['form_field_id']] = array('value' => $row['value'], 'internal_value' => $row['internal_value']);
				}
				
				foreach ($formFields as $formField) {
					if ($formField['field_type'] == 'page_break' || $formField['field_type'] == 'section_description') {
						continue;
					}
					$field = array(
						'class_name' => 'zenario_user_forms',
						'label' => $formField['name'],
						'ord' => $formField['ordinal'] + 10);
					if ($formField['field_type'] == 'attachment') {
						$responseValue = isset($userResponse[$formField['id']]['internal_value']) ? $userResponse[$formField['id']]['internal_value'] : '';
						$field['upload'] = array();
						$field['download'] = true;
					} else {
						$responseValue = isset($userResponse[$formField['id']]['value']) ? $userResponse[$formField['id']]['value'] : '';
						if ($formField['field_type'] == 'textarea') {
							$field['type'] = 'textarea';
							$field['rows'] = 5;
						} else {
							$field['type'] = 'text';
						}
					}
					$field['value'] = $responseValue;
					$box['tabs']['form_fields']['fields']['form_field_' . $formField['id']] = $field;
				}
				break;
			case 'zenario_user_dataset_field_picker':
				$box['key']['refinerId'] = get('refinerId');
				$box['tabs']['dataset_fields']['fields']['dataset_fields']['values'] =
					listCustomFields('users', $flat = false, $filter = false, $customOnly = false, $useOptGroups = true);
				break;
			case 'zenario_user_admin_box_form':
				if (!inc('zenario_extranet')) {
					$fields['data/save_data']['hidden'] = 
					$fields['data/email_html']['hidden'] = 
					$fields['data/user_status']['hidden'] = 
					$fields['data/log_user_in']['hidden'] = 
					$fields['data/log_user_in_cookie']['hidden'] = 
					$fields['data/add_user_to_group']['hidden'] = 
					$fields['data/duplicate_submission_html']['hidden'] = 
					$fields['data/user_duplicate_email_action']['hidden'] = 
					$fields['data/duplicate_email_address_error_message']['hidden'] = 
					$fields['data/create_another_form_submission_record']['hidden'] = 
					$fields['data/line_br_2']['hidden'] = true;
				}
				$fields['data/add_user_to_group']['values'] = 
					listCustomFields('users', $flat = false, 'groups_only', $customOnly = true, $useOptGroups = true);
				
				if (get('refinerName') == 'archived') {
					foreach($box['tabs'] as &$tab) {
						$tab['edit_mode']['enabled'] = false;
					}
				}
				
				// Get default language english name
				$defaultLanguageName = false;
				$languages = getLanguages(false, true, true);
				foreach($languages as $language) {
					$defaultLanguageName = $language['english_name'];
					break;
				}
				if ($defaultLanguageName) {
					$fields['details/translate_text']['side_note'] = adminPhrase(
						'This will cause all displayable text from this form to be translated when used in a Forms plugin. This should be disabled if you enter non-[[default_language]] text into the form field admin boxes.', array('default_language' => $defaultLanguageName));
				}
				
				$formTextFieldLabels = array();
				$formTextFieldLabels[''] = array('label' => '-- Select --');
				if ($id = $box['key']['id']) {
					// Fill form fields
					$record = getRow('user_forms', true, $id);
					$this->fillFieldValues($fields, $record);
					
					$values['data/admin_email_options'] = ($record['admin_email_use_template'] ? 'use_template' : 'send_data');
					$box['title'] = adminPhrase('Editing the Form "[[name]]"', array('name' => $record['name']));
					
					if (!empty($record['redirect_after_submission'])) {
						$values['details/success_message_type'] = 'redirect_after_submission';
					} elseif (!empty($record['show_success_message'])) {
						$values['details/success_message_type'] = 'show_success_message';
					} else {
						$values['details/success_message_type'] = 'none';
					}
					
					// Find all text form fields from the selected form
					$formTextFields = $this->getTextFormFields($box['key']['id']);
					foreach ($formTextFields as $formTextField) {
						$formTextFieldLabels[$formTextField['db_column']] = array('label' => $formTextField['label']);
					}
					
					// Populate translations tab
					$translatableLanguage = false;
					foreach ($languages as $language) {
						if ($language['translate_phrases']) {
							$translatableLanguage = true;
						}
					}
					if ($translatableLanguage) {
						// Get translatable fields for this field type
						$fieldsToTranslate = array(
							'title' => $record['title'],
							'success_message' => $record['success_message'],
							'submit_button_text' => $record['submit_button_text'],
							'default_next_button_text' => $record['default_next_button_text'],
							'default_previous_button_text' => $record['default_previous_button_text'],
							'duplicate_email_address_error_message' => $record['duplicate_email_address_error_message']);
						
						// Get any existing phrases that translatable fields have
						$existingPhrases = array();
						foreach($fieldsToTranslate as $name => $value) {
							$phrases = getRows('visitor_phrases', 
								array('local_text', 'language_id'), 
								array('code' => $value, 'module_class_name' => 'zenario_user_forms'));
							while ($row = sqlFetchAssoc($phrases)) {
								$existingPhrases[$name][$row['language_id']] = $row['local_text'];
							}
						}
						$keys = array_keys($fieldsToTranslate);
						$lastKey = end($keys);
						$ord = 0;
						
						foreach($fieldsToTranslate as $name => $value) {
							
							// Create label for field with english translation (if set)
							$label = $fields[$name]['label'];
							$html = '<b>'.$label.'</b>';
							$readOnly = true;
							if (!empty($value)) {
								$html .= ' "'. $value .'"';
								$readOnly = false;
							} else {
								$html .= ' (Value not set)';
							}
							$box['tabs']['translations']['fields'][$name] = array(
								'class_name' => 'zenario_user_forms',
								'ord' => $ord,
								'snippet' => array(
									'html' =>  $html));
							
							// Create an input box for each translatable language and look for existing phrases
							foreach($languages as $language) {
								if ($language['translate_phrases']) {
									$value = '';
									if (isset($existingPhrases[$name]) && isset($existingPhrases[$name][$language['id']])) {
										$value = $existingPhrases[$name][$language['id']];
									}
									$box['tabs']['translations']['fields'][$name.'__'.$language['id']] = array(
										'class_name' => 'zenario_user_forms',
										'ord' => $ord++,
										'label' => $language['english_name']. ':',
										'type' => 'text',
										'value' => $value,
										'read_only' => $readOnly);
								}
							}
							
							// Add linebreak after each field
							if ($name != $lastKey) {
								$box['tabs']['translations']['fields'][$name.'_break'] = array(
									'class_name' => 'zenario_user_forms',
									'ord' => $ord,
									'snippet' => array(
										'html' => '<hr/>'));
							}
							$ord++;
							$box['tabs']['translations']['hidden'] = $record['translate_text'];
						}
					} else {
						unset($box['tabs']['translations']);
					}
				} else {
					unset($box['tabs']['translations']);
					$box['title'] = adminPhrase('Creating a Form');
					$values['data/save_data'] = 
					$values['data/save_record'] = true;
					$values['details/submit_button_text'] = 'Submit';
					$values['details/default_next_button_text'] = 'Next';
					$values['details/default_previous_button_text'] = 'Back';
					$values['data/duplicate_email_address_error_message'] = 'Sorry this form has already been completed with this email address';
				}
				// Set text field select lists (will just be -- Select -- if creating new form)
				$fields['data/reply_to_email_field']['values'] =
				$fields['data/reply_to_first_name']['values'] =
				$fields['data/reply_to_last_name']['values'] =
					$formTextFieldLabels;
				break;
			
			case 'zenario_user_admin_box_form_field':
				
				// If no conditional field types, hide conditional mandatory option and conditional visible option
				$conditionalFields = self::getConditionalFields($box['key']['form_id']);
				if (empty($conditionalFields)) {
					unset($fields['details/readonly_or_mandatory']['values']['conditional_mandatory']);
					unset($fields['details/visibility']['values']['visible_on_condition']);
				}
				
				if ($id = $box['key']['id']) {
					
					$formProperties = getRow('user_forms', 
						array('status', 'translate_text', 'send_email_to_user', 'send_email_to_admin', 'save_record'), 
						array('id' => $box['key']['form_id']));
					
					if ($formProperties['status'] == 'archived') {
						foreach($box['tabs'] as &$tab) {
							$tab['edit_mode']['enabled'] = false;
						}
					}
					// Get form field details
					$formFieldValues = self::getUserFormFields($box['key']['form_id'], $id);
					$formFieldValues = $formFieldValues[$id];
					
					$values['details/field_type_picker'] = $box['key']['type'] = self::getFieldType($formFieldValues);
					$fieldType = false;
					if ($fieldType = $formFieldValues['field_type']) {
						if (!$formProperties['send_email_to_user'] && !$formProperties['send_email_to_admin'] && !$formProperties['save_record']) {
							$fields['type/warning']['hidden'] =
							$fields['details/warning']['hidden'] = 
								false;
						}
						if (in($fieldType, 'checkboxes', 'radios', 'select')) {
							$lov = self::getUnlinkedFieldLOV($id, false);
							
							$numValues = $box['key']['numValues'] = count($lov);
							$this->dynamicallyCreateValueFieldsFromTemplate($box, $fields, $values);
							$i = 0;
							foreach ($lov as $lovId => $v) {
								++$i;
								$box['tabs']['lov']['fields']['id'. $i]['value'] = $lovId;
								$box['tabs']['lov']['fields']['label'. $i]['value'] = $v['label'];
							}
						}
					} else {
						$fieldType = getRow('custom_dataset_fields', 'type', $formFieldValues['user_field_id']);
					}
					
					// Populate translations tab
					if ($formProperties['translate_text']) {
						
						$languages = getLanguages(false, true, true);
						$translatableLanguage = false;
						foreach ($languages as $language) {
							if ($language['translate_phrases']) {
								$translatableLanguage = true;
							}
						}
						if ($translatableLanguage) {
							// Get translatable fields for this field type
							$fieldsToTranslate = array(
								'label' => $formFieldValues['field_label']);
							
							if ($fieldType == 'text') {
								$fieldsToTranslate['placeholder'] = $formFieldValues['placeholder'];
								$fieldsToTranslate['validation_error_message'] = $formFieldValues['validation_error_message'];
							} elseif ($fieldType == 'textarea') {
								$fieldsToTranslate['placeholder'] = $formFieldValues['placeholder'];
							} elseif ($fieldType == 'section_description') {
								$fieldsToTranslate['description'] = $formFieldValues['description'];
							}
							
							if ($fieldType != 'section_description') {
								$fieldsToTranslate['note_to_user'] = $formFieldValues['note_to_user'];
								$fieldsToTranslate['required_error_message'] = $formFieldValues['required_error_message'];
							}
							
							$existingPhrases = array();
							foreach($fieldsToTranslate as $name => $value) {
								$phrases = getRows('visitor_phrases', 
									array('local_text', 'language_id'), 
									array('code' => $value, 'module_class_name' => 'zenario_user_forms'));
								while ($row = sqlFetchAssoc($phrases)) {
									$existingPhrases[$name][$row['language_id']] = $row['local_text'];
								}
							}
							$keys = array_keys($fieldsToTranslate);
							$lastKey = end($keys);
							$ord = 0;
							
							foreach($fieldsToTranslate as $name => $value) {
								$label = $fields['details/'.$name]['label'];
								$html = '<b>'.$label.'</b>';
								
								$readOnly = true;
								if (!empty($value)) {
									$html .= ' "'. $value .'"';
									$readOnly = false;
								} else {
									$html .= ' (Value not set)';
								}
								
								$box['tabs']['translations']['fields'][$name] = array(
									'class_name' => 'zenario_user_forms',
									'ord' => $ord,
									'snippet' => array(
										'html' =>  $html));
								
								$type = in_array($name, array('note_to_user', 'description')) ? 'textarea' : 'text';
								
								foreach($languages as $language) {
									if ($language['translate_phrases']) {
										$value = '';
										if (isset($existingPhrases[$name]) && isset($existingPhrases[$name][$language['id']])) {
											$value = $existingPhrases[$name][$language['id']];
										}
										$box['tabs']['translations']['fields'][$name.'__'.$language['id']] = array(
											'class_name' => 'zenario_user_forms',
											'ord' => $ord++,
											'label' => $language['english_name']. ':',
											'type' => $type,
											'value' => $value,
											'read_only' => $readOnly);
									}
								}
								if ($name != $lastKey) {
									$box['tabs']['translations']['fields'][$name.'_break'] = array(
										'class_name' => 'zenario_user_forms',
										'ord' => $ord,
										'snippet' => array(
											'html' => '<hr/>'));
								}
								$ord++;
							}
						} else {
							unset($box['tabs']['translations']);
						}
					} else {
						unset($box['tabs']['translations']);
					}
					
					// Populate details tab
					$dataset = getDatasetDetails('users');
					$systemFieldLabel = 
						getRow('custom_dataset_fields', 
							'label', 
							array('dataset_id' => $dataset['id'], 'id' => $formFieldValues['user_field_id']));
					$values['details/name'] = $formFieldValues['name'];
					$values['details/label'] = (($formFieldValues['field_label'] === null) ?  $systemFieldLabel : $formFieldValues['field_label']);
					if ($formFieldValues['is_readonly']) {
						$values['readonly_or_mandatory'] = 'readonly';
					} elseif($formFieldValues['is_required']) {
						$values['readonly_or_mandatory'] = 'mandatory';
					} elseif($formFieldValues['mandatory_condition_field_id']) {
						$values['readonly_or_mandatory'] = 'conditional_mandatory';
					} else {
						$values['readonly_or_mandatory'] = 'none';
					}
					$values['details/mandatory_condition_field_id'] = $formFieldValues['mandatory_condition_field_id'];
					$values['details/mandatory_condition_field_value'] = $formFieldValues['mandatory_condition_field_value'];
					$values['details/visibility'] = $formFieldValues['visibility'];
					$values['details/visible_condition_field_id'] = $formFieldValues['visible_condition_field_id'];
					$values['details/visible_condition_field_value'] = $formFieldValues['visible_condition_field_value'];
					$values['details/placeholder'] = $formFieldValues['placeholder'];
					$values['details/size'] = $formFieldValues['size'];
					$values['details/note_to_user'] = $formFieldValues['note_to_user'];
					$values['details/css_classes'] = $formFieldValues['css_classes'];
					$values['details/required_error_message'] = $formFieldValues['required_error_message'];
					$values['details/validation'] = (empty($formFieldValues['field_validation']) ? 'none' : $formFieldValues['field_validation']);
					$values['details/validation_error_message'] = $formFieldValues['validation_error_message'];
					$values['details/div_wrap_class'] = $formFieldValues['div_wrap_class'];
					
					// Populate admin box for page breaks
					if ($formFieldValues['field_type'] == 'page_break') {
						$fields['details/label']['hidden'] = 
						$fields['details/readonly_or_mandatory']['hidden'] =
						$fields['details/visibility']['hidden'] = 
						$fields['details/note_to_user']['hidden'] = 
						$fields['details/css_classes']['hidden'] = 
						$fields['details/div_wrap_class']['hidden'] = true;
						$values['details/next_button_text'] = $formFieldValues['next_button_text'];
						$values['details/previous_button_text'] = $formFieldValues['previous_button_text'];
						unset($box['tabs']['translations']);
					
					} elseif ($formFieldValues['field_type'] == 'section_description') {
						$fields['details/readonly_or_mandatory']['hidden'] =
						$fields['details/visibility']['hidden'] = 
						$fields['details/note_to_user']['hidden'] = 
						$fields['details/css_classes']['hidden'] = 
						$fields['details/div_wrap_class']['hidden'] = true;
						$values['details/description'] = $formFieldValues['description'];
					
					} elseif ($formFieldValues['field_type'] == 'calculated') {
						$values['details/numeric_field_1'] = $formFieldValues['numeric_field_1'];
						$values['details/numeric_field_2'] = $formFieldValues['numeric_field_2'];
						$values['details/calculation_type'] = $formFieldValues['calculation_type'];
						
					} elseif ($formFieldValues['field_type'] == 'restatement') {
						$values['details/restatement_field'] = $formFieldValues['restatement_field'];
						
					} else {
						$fieldType = self::getFieldType($formFieldValues);
						$fields['details/dataset_field_description']['snippet']['html'] = '
							<p><b>Dataset:</b> <span>'.($formFieldValues['db_column'] ? $dataset['label'] : 'None').'</span></p>
							<p><b>Code name:</b> <span>'.self::getFieldName($formFieldValues).'</span></p>
							<p><b>Field type:</b> <span>'.$fieldType.'</span></p>';
					}
					
					$box['title'] = adminPhrase('Editing the Form field "[[name]]"', array('name' => $formFieldValues['name']));
				} else {
					// Create new unlinked field
					$fieldType = $values['details/field_type_picker'];
					unset($box['tabs']['translations']);
					if ($box['key']['type'] != 'section_description') {
						$box['title'] = adminPhrase('Creating a new unlinked form field');
						$fields['details/field_type_picker']['hidden'] = false;
						$fields['details/unlinked_form_field_description']['hidden'] = false;
						$this->dynamicallyCreateValueFieldsFromTemplate($box, $fields, $values);
					// Create new section description (special unlinked field)
					} else {
						$box['title'] = adminPhrase('Creating a new section description');
					}
				}
				// Populate advanced tab
				if (in_array($fieldType, array('radios', 'centralised_radios', 'select', 'centralised_select'))) {
					$fields['advanced/default_value_text']['hidden'] = true;
					$fields['advanced/default_value_lov']['values'] = $formFieldValues['db_column'] ? getDatasetFieldLOV($formFieldValues['field_id']) : self::getUnlinkedFieldLOV($id);
					if ($formFieldValues['default_value']) {
						$values['advanced/default_value_mode'] = 'value';
						$values['advanced/default_value_lov'] = $formFieldValues['default_value'];
					} elseif ($formFieldValues['default_value_class_name'] && $formFieldValues['default_value_method_name']) {
						$values['advanced/default_value_mode'] = 'method';
						$values['advanced/default_value_class_name'] = $formFieldValues['default_value_class_name'];
						$values['advanced/default_value_method_name'] = $formFieldValues['default_value_method_name'];
						$values['advanced/default_value_param_1'] = $formFieldValues['default_value_param_1'];
						$values['advanced/default_value_param_2'] = $formFieldValues['default_value_param_2'];
					} else {
						$values['advanced/default_value_mode'] = 'none';
					}
				} elseif (in_array($fieldType, array('text', 'textarea'))) {
					$fields['advanced/default_value_lov']['hidden'] = true;
					if ($formFieldValues['default_value']) {
						$values['advanced/default_value_mode'] = 'value';
						$values['advanced/default_value_text'] = $formFieldValues['default_value'];
					} elseif ($formFieldValues['default_value_class_name'] && $formFieldValues['default_value_method_name']) {
						$values['advanced/default_value_mode'] = 'method';
						$values['advanced/default_value_class_name'] = $formFieldValues['default_value_class_name'];
						$values['advanced/default_value_method_name'] = $formFieldValues['default_value_method_name'];
						$values['advanced/default_value_param_1'] = $formFieldValues['default_value_param_1'];
						$values['advanced/default_value_param_2'] = $formFieldValues['default_value_param_2'];
					} else {
						$values['advanced/default_value_mode'] = 'none';
					}
				} else {
					$box['tabs']['advanced']['hidden'] = true;
				}
				
				break;
			case 'zenario_email_template':
				$forms = getRowsArray('user_forms', 'name', array('status' => 'active'), 'name');
				$fields['body/user_form']['values'] = $forms;
				break;
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	
		switch ($path) {
			case 'zenario_user_admin_box_form':
				$fields['details/translate_text']['hidden'] = !checkRowExists('languages', array('translate_phrases' => 1));
				
				// Display translation boxes for translatable fields with a value entered
				$languages = getLanguages(false, true, true);
				$fieldsToTranslate = array('title', 'success_message', 'submit_button_text', 'default_next_button_text', 'default_previous_button_text', 'duplicate_email_address_error_message');
				foreach($fieldsToTranslate as $fieldName) {
					$fields['translations/'.$fieldName]['snippet']['html'] = '<b>'.$fields[$fieldName]['label'].'</b>';
					if (!empty($values[$fieldName])) {
						$fields['translations/'.$fieldName]['snippet']['html'] .= ' "'.$values[$fieldName].'"';
						$readOnly = false;
					} else {
						$readOnly = true;
						$fields['translations/'.$fieldName]['snippet']['html'] .= ' (Value not set)';
					}
					foreach($languages as $language) {
						$fields['translations/'.$fieldName.'__'.$language['id']]['read_only'] = $readOnly;
					}
				}
				
				$box['tabs']['translations']['hidden'] = !$values['details/translate_text'];
				
				$fields['captcha/captcha_type']['hidden'] =
				$fields['captcha/extranet_users_use_captcha']['hidden'] =
					!$values['captcha/use_captcha'];
				
				$zenario_extranet = inc('zenario_extranet');
				if ($zenario_extranet) {
					$fields['data/user_status']['hidden'] =
					$fields['data/email_html']['hidden'] =
					$fields['data/add_user_to_group']['hidden'] =
					$fields['data/duplicate_submission_html']['hidden'] =
					$fields['data/user_duplicate_email_action']['hidden'] =
						!$values['data/save_data'];
				
					$fields['data/create_another_form_submission_record']['hidden'] =
						!$values['data/save_data'] || ($values['data/user_duplicate_email_action'] == 'stop');
				
					$fields['data/duplicate_email_address_error_message']['hidden'] = 
						$fields['data/user_duplicate_email_action']['hidden']
						|| ($values['data/user_duplicate_email_action'] != 'stop');
					
					$fields['data/log_user_in_cookie']['hidden'] =
						!($values['data/save_data'] && ($values['data/log_user_in'] == 1) && ($values['data/user_status'] == 'active'));
				
					$fields['data/log_user_in']['hidden'] =
						!($values['data/save_data'] && ($values['data/user_status'] == 'active'));
				
					$fields['data/create_another_form_submission_record']['disabled'] = !$values['data/save_record'];
					if (!$values['data/save_record']) {
						$values['data/create_another_form_submission_record'] = false;
					}
				}
				
				$fields['data/user_email_template']['hidden'] = 
						!$values['data/send_email_to_user'];
				
				$fields['data/admin_email_addresses']['hidden'] = 
				$fields['data/admin_email_options']['hidden'] = 
				$fields['data/reply_to']['hidden'] = 
					!$values['data/send_email_to_admin'];  
				
				$fields['data/admin_email_template']['hidden'] = 
					!($values['data/send_email_to_admin'] && ($values['data/admin_email_options'] == 'use_template'));
				
				$fields['data/reply_to_email_field']['hidden'] = 
				$fields['data/reply_to_first_name']['hidden'] = 
				$fields['data/reply_to_last_name']['hidden'] = 
					!($values['data/reply_to'] && $values['data/send_email_to_admin']);
				
				$fields['details/redirect_location']['hidden'] = 
					$values['details/success_message_type'] != 'redirect_after_submission';
				
				$fields['details/success_message']['hidden'] = 
					$values['details/success_message_type'] != 'show_success_message';
				
				
				if (empty($box['key']['id'])) {
					$values['data/create_another_form_submission_record'] = $values['data/save_record'];
				} else {
					$box['title'] = adminPhrase('Editing the Form "[[name]]"', array('name' => $values['details/name']));
				}
				
				break;
			case 'zenario_user_admin_box_form_field':
				// Display translation boxes for translatable fields with a value entered
				$languages = getLanguages(false, true, true);
				$fieldsToTranslate = array('label', 'placeholder', 'note_to_user', 'required_error_message', 'validation_error_message');
				foreach($fieldsToTranslate as $fieldName) {
					$fields['translations/'.$fieldName]['snippet']['html'] = '<b>'.$fields[$fieldName]['label'].'</b>';
					if (!empty($values['details/'.$fieldName])) {
						$fields['translations/'.$fieldName]['snippet']['html'] .= ' "'.$values[$fieldName].'"';
						$readOnly = false;
					} else {
						$readOnly = true;
						$fields['translations/'.$fieldName]['snippet']['html'] .= ' (Value not set)';
					}
					foreach($languages as $language) {
						$fields['translations/'.$fieldName.'__'.$language['id']]['read_only'] = $readOnly;
					}
				}
				
				// Populate conditional mandatory fields list
				$conditionalFields = self::getConditionalFields($box['key']['form_id']);
				unset($conditionalFields[$box['key']['id']]);
				if ($values['details/readonly_or_mandatory'] == 'conditional_mandatory') {
					$fields['details/mandatory_condition_field_id']['values'] = $conditionalFields;
				}
				$fields['details/mandatory_condition_field_id']['hidden'] = 
					($values['details/readonly_or_mandatory'] != 'conditional_mandatory');
				
				// Populate conditional manditory if value list for field
				if ($conditionalFieldId = $values['details/mandatory_condition_field_id']) {
					$fieldValues = self::getConditionalFieldValuesList($conditionalFieldId);
					$fields['details/mandatory_condition_field_value']['values'] = $fieldValues;
				}
				$fields['details/mandatory_condition_field_value']['hidden'] = 
					($values['details/readonly_or_mandatory'] != 'conditional_mandatory') || (!$values['details/mandatory_condition_field_id']);
				$fields['details/required_error_message']['hidden'] = 
					!(($values['details/readonly_or_mandatory'] == 'mandatory') || 
					($values['details/readonly_or_mandatory'] == 'conditional_mandatory'));
				
				// Populate conditional visibility fields list
				if ($values['details/visibility'] == 'visible_on_condition') {
					$fields['details/visible_condition_field_id']['values'] = $conditionalFields;
				}
				$fields['details/visible_condition_field_id']['hidden'] = 
					($values['details/visibility'] != 'visible_on_condition');
				
				// Populate conditional visibility if value list for field
				if ($conditionalFieldId = $values['details/visible_condition_field_id']) {
					$fieldValues = self::getConditionalFieldValuesList($conditionalFieldId);
					$fields['details/visible_condition_field_value']['values'] = $fieldValues;
				}
				$fields['details/visible_condition_field_value']['hidden'] = 
					($values['details/visibility'] != 'visible_on_condition') || (!$values['details/visible_condition_field_id']);
				
				
				$fields['details/validation_error_message']['hidden'] = 
					($values['details/validation'] == 'none');
				
				if (!$box['key']['id']) {
					$fields['details/label']['hidden'] = 
					$fields['details/name']['hidden'] = 
					$fields['details/note_to_user']['hidden'] = 
					$fields['details/css_classes']['hidden'] = 
					$fields['details/visibility']['hidden'] = 
					$fields['details/div_wrap_class']['hidden'] = 
						!$values['details/field_type_picker'];
					
					if ($box['key']['type'] == 'section_description') {
						$fields['details/name']['hidden'] =
						$fields['details/label']['hidden'] = false;
					}
					
					$fields['details/size']['hidden'] = 
					$fields['details/validation']['hidden'] = 
						!($values['details/field_type_picker'] == 'text');
					
					$fields['details/placeholder']['hidden'] =
						!($values['details/field_type_picker'] == 'text' || $values['details/field_type_picker'] == 'textarea');
				}
				
				// Hide mandatory/read only from restatement, calculated, section description and page break fields
				$fields['details/readonly_or_mandatory']['hidden'] =
					in_array($values['details/field_type_picker'], array('restatement', 'calculated')) || 
					(isset($box['key']['type']) && in_array($box['key']['type'], array('restatement', 'calculated', 'section_description', 'page_break'))) || 
					(!$box['key']['id'] && !$values['details/field_type_picker']);
				
				
				
				// If field is calculated get list of numeric fields
				if ($values['details/field_type_picker'] == 'calculated') {
					$floatingPointFields = getRowsArray('user_form_fields', 'name', 
						array('user_form_id' => $box['key']['form_id'], 'validation' => array('integer', 'number', 'floating_point')));
					$fields['details/numeric_field_1']['values'] = 
					$fields['details/numeric_field_2']['values'] = 
						$floatingPointFields;
				
				// If field is calculated get list of fields
				} elseif ($values['details/field_type_picker'] == 'restatement') {
					$mirroredFields = array();
					$sql = '
						SELECT id, name, ordinal
						FROM '.DB_NAME_PREFIX.'user_form_fields
						WHERE user_form_id = '.$box['key']['form_id'].'
						AND (field_type NOT IN (\'page_break\', \'section_description\', \'restatement\')
						OR field_type IS NULL)';
					$result = sqlSelect($sql);
					while ($row = sqlFetchAssoc($result)) {
						$mirroredFields[$row['id']] = array('label' => $row['name'], 'ord' => $row['ordinal']);
					}
					if ($box['key']['id']) {
						unset($mirroredFields[$box['key']['id']]);
					}
					$fields['details/restatement_field']['values'] = $mirroredFields;
				}
				
				// Handle adding new options to multi value fields
				if (in($values['details/field_type_picker'], 'checkboxes', 'radios', 'select')) {
					$numValues = (int) $box['key']['numValues'];
					//save num of values in hidden field when the button is pressed
					$values['lov/number_of_fields'] = $numValues;
				
					//Add new blank fields when the Admin presses the "add" button
					if (!empty($box['tabs']['lov']['fields']['add']['pressed'])) {
						$box['key']['numValues'] = (int) $numValues + (int) $values['lov/add_num'];
						
						//save num of values in hidden field when the button is pressed
						$values['lov/number_of_fields'] = $box['key']['numValues'];
						
				
					} elseif ($numValues > 1) {
						//Watch out for the admin pressing the delete or nudge buttons
						for ($i = 1; $i <= $numValues; ++$i) {
							if (!empty($box['tabs']['lov']['fields']['delete'. $i]['pressed'])) {
							
								//If they press the delete button, loop through all the remaining values and nudge them up by one
								for ($j = $i; $j < $numValues; ++$j) {
									$this->nudge($box, $fields, $values, $j, $j + 1);
								}
							
								//Delete the last field (which will now contain the value we're deleting)
								unset($box['tabs']['lov']['fields']['id'. $numValues]);
								unset($box['tabs']['lov']['fields']['label'. $numValues]);
								unset($box['tabs']['lov']['fields']['delete'. $numValues]);
								unset($box['tabs']['lov']['fields']['nudge_up'. $numValues]);
								unset($box['tabs']['lov']['fields']['nudge_down'. $numValues]);
							
								//Reduce the field count by one
								$box['key']['numValues'] = --$numValues;
								
								$values['lov/number_of_fields'] = $box['key']['numValues'];
								
								break;
						
							//Handle nudge up or down
							} else
							if (!empty($box['tabs']['lov']['fields']['nudge_up'. $i]['pressed']) && $i > 1) {
								$this->nudge($box, $fields, $values, $i - 1, $i);
								break;
							} else
							if (!empty($box['tabs']['lov']['fields']['nudge_down'. $i]['pressed']) && $i < $numValues) {
								$this->nudge($box, $fields, $values, $i, $i + 1);
								break;
							}
						}
					}
					$this->dynamicallyCreateValueFieldsFromTemplate($box, $fields, $values);
				}
				// Advanced tab display
				if (!$box['tabs']['advanced']['hidden']) {
					if (in_array($values['details/field_type_picker'], array('radios', 'centralised_radios', 'select', 'centralised_select'))) {
						$fields['advanced/default_value_lov']['hidden'] = $values['advanced/default_value_mode'] != 'value';
					} elseif (in_array($values['details/field_type_picker'], array('text', 'textarea'))) {
						$fields['advanced/default_value_text']['hidden'] = $values['advanced/default_value_mode'] != 'value';
					}
					$fields['advanced/default_value_class_name']['hidden'] = 
					$fields['advanced/default_value_method_name']['hidden'] = 
					$fields['advanced/default_value_param_1']['hidden'] = 
					$fields['advanced/default_value_param_2']['hidden'] = 
						$values['advanced/default_value_mode'] != 'method';
				}
				
				break;
			case 'zenario_email_template':
				if ($formId = $values['body/user_form']) {
					// Get list of form fields for form
					$fields['body/user_form_field']['hidden'] = false;
					$sql = '
						SELECT
							uff.id,
							IF(
								uff.name IS NULL or uff.name = "", 
								IFNULL(
									cdf.db_column, 
									CONCAT("unlinked_", uff.field_type, "_", uff.id)
								), 
								uff.label
							) AS name,
							uff.field_type
						FROM '. DB_NAME_PREFIX. 'user_form_fields AS uff
						LEFT JOIN '. DB_NAME_PREFIX.'custom_dataset_fields AS cdf
							ON uff.user_field_id = cdf.id
						WHERE uff.user_form_id = '.(int)$formId. '
						ORDER BY uff.ordinal';
					
					$result = sqlSelect($sql);
					$formFields = array();
					$formFields['all'] = adminPhrase('Add all to template');
					while ($row = sqlFetchAssoc($result)) {
						if (self::fieldTypeCanRecordValue($row['field_type'])) {
							$formFields[$row['id']] = trim($row['name'], " \t\n\r\0\x0B:");
						}
					}
					$fields['body/user_form_field']['values'] = $formFields;
					
					
					if ($formFieldId = $values['body/user_form_field']) {
						// Add form field mergefield onto end of email template
						$sql = '
							SELECT 
								IFNULL(uff.name, cdf.label) AS name, 
								IFNULL(cdf.db_column, CONCAT(\'unlinked_\', uff.field_type, \'_\', uff.id)) AS mergefield
							FROM '.DB_NAME_PREFIX.'user_form_fields AS uff
							LEFT JOIN '.DB_NAME_PREFIX. 'custom_dataset_fields AS cdf
								ON uff.user_field_id = cdf.id
							WHERE (uff.field_type NOT IN ("page_break", "restatement", "section_description") 
								OR uff.field_type IS NULL)';
						
						if ($formFieldId == 'all') {
							$sql .= ' AND uff.user_form_id = '.(int)$formId;
						} else {
							$sql .= ' AND uff.id = '.(int)$formFieldId;
						}
						
						$result = sqlSelect($sql);
						$mergeFields = '';
						while ($row = sqlFetchAssoc($result)) {
							$mergeFields .= '<p>';
							if ($row['name']) {
								$mergeFields .= trim($row['name'], " \t\n\r\0\x0B:"). ': ';
							}
							$mergeFields .= '[['.$row['mergefield'].']]</p>';
						}
						$values['body/body'] .= $mergeFields;
						$values['body/user_form_field'] = '';
					}
				} else {
					$fields['body/user_form_field']['hidden'] = true;
				}
				break;
			case 'plugin_settings':
				$fields['first_tab/display_text']['hidden'] = ($values['first_tab/display_mode'] != 'in_modal_window');
				break;
		}
	}
	
	private static function getConditionalFields($formId) {
		$conditionalFields = array();
		$sql = '
			SELECT uff.id, uff.name, IFNULL(cdf.type, uff.field_type) AS type
			FROM '.DB_NAME_PREFIX.'user_form_fields uff
			LEFT JOIN '.DB_NAME_PREFIX.'custom_dataset_fields cdf
				ON uff.user_field_id = cdf.id
			WHERE uff.user_form_id = '.(int)$formId. '
			AND IFNULL(cdf.type, uff.field_type) IN 
				(\'checkbox\', \'radios\', \'select\', \'centralised_radios\', \'centralised_select\')';
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			$row['type'] = str_replace('_', ' ', ucfirst($row['type']));
			$conditionalFields[$row['id']] = $row['type'].': "'.$row['name'].'"';
		}
		return $conditionalFields;
	}
	
	private static function getConditionalFieldValuesList($fieldId) {
		$values = array();
		$fieldDetails = getRow('user_form_fields', array('user_field_id', 'field_type'), array('id' => $fieldId));
		if ($customFieldId = $fieldDetails['user_field_id']) {
			$fieldType = getRow('custom_dataset_fields', 'type', array('id' => $customFieldId));
			if ($fieldType == 'checkbox') {
				$values = array(0 => 'Unchecked', 1 => 'Checked');
			} else {
				$values = getDatasetFieldLOV($customFieldId);
			}
		} else {
			if ($fieldDetails['field_type'] == 'checkbox') {
				$values = array(0 => 'Unchecked', 1 => 'Checked');
			} else {
				$values = getRowsArray(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', 'label', array('form_field_id' => $fieldId));
			}
		}
		return $values;
	}

	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'zenario_user_dataset_field_picker':
				if ($values['dataset_fields/dataset_fields'] 
					&& checkRowExists('user_form_fields', 
						array('user_form_id' => $box['key']['refinerId'], 'user_field_id' => $values['dataset_fields/dataset_fields']))) {
					
					$box['tabs']['dataset_fields']['errors'][] = adminPhrase('You cannot add the same dataset field to a form more than once');
				}
				break;
			case 'zenario_user_admin_box_form':
				$errors = &$box['tabs']['details']['errors'];
				$record_id = arrayKey($box, 'key', 'id');
				if (empty($values['name'])) {
					$errors[] = adminPhrase('Please enter a name for this Form.');
				}
				else if(!self::checkAdminBoxFormNameUnique($values['name'], $record_id)) {
					$errors[] = adminPhrase('The name for the Form must be unique.');
				}
				
				$errors = &$box['tabs']['data']['errors'];
				// Create an error if the form is doing nothing with data
				$zenario_extranet = inc('zenario_extranet');
				if ($saving
					&& (!$zenario_extranet || empty($values['data/save_data']))
					&& empty($values['data/save_record'])
					&& empty($values['data/send_signal'])
					&& empty($values['data/send_email_to_user'])
					&& empty($values['data/send_email_to_admin'])) {
					$errors[] = adminPhrase('This form is currently not using the data submitted in any way. Please select at least one of the following options.');
				}
				break;
			case 'zenario_user_admin_box_form_field':
				$errors = &$box['tabs']['details']['errors'];
				if ($saving
				 && in($values['details/field_type_picker'], 'checkboxes', 'radios', 'select')) {
					
					$lovByLabel = array();
					$numValues = (int) $box['key']['numValues'];
					
					for ($i = 1; $i <= $numValues; ++$i) {
						if (isset($values['lov/label'. $i])
						 && $values['lov/label'. $i] !== '') {
							
							if (!isset($lovByLabel[$values['lov/label'. $i]])) {
								$lovByLabel[$values['lov/label'. $i]] = true;
							} else {
								$box['tabs']['lov']['errors'][] =
									adminPhrase('You have entered "[[label]]" more than once.', array('label' => $values['lov/label'. $i]));
							}
						}
					}
				}
				if (!$fields['details/mandatory_condition_field_value']['hidden'] && ($values['details/mandatory_condition_field_value'] === '')) {
					$box['tabs']['details']['errors'][] =
						adminPhrase('Please select a mandatory on condition form field value');	
				}
				if (!$fields['details/visible_condition_field_value']['hidden'] && ($values['details/visible_condition_field_value'] === '')) {
					$box['tabs']['details']['errors'][] =
						adminPhrase('Please select a visible on condition form field value');
				}
				
				// Validate advanced tab
				if (!$box['tabs']['advanced']['hidden']) {
					if ($values['advanced/default_value_mode'] == 'value' && !$values['advanced/default_value_lov'] && !$values['advanced/default_value_text']) {
						$box['tabs']['advanced']['errors'][] = adminPhrase('Please enter a default value.');
					} elseif ($values['advanced/default_value_mode'] == 'method') {
						if (!$values['advanced/default_value_class_name']) {
							$box['tabs']['advanced']['errors'][] = adminPhrase('Please enter a class name.');
						} elseif (!inc($values['advanced/default_value_class_name'])) {
							$box['tabs']['advanced']['errors'][] = adminPhrase('Please enter a class name of a module that\'s running on this site.');
						}
						if (!$values['advanced/default_value_method_name']) {
							$box['tabs']['advanced']['errors'][] = adminPhrase('Please enter the name of a static method.');
						} elseif (!method_exists($values['advanced/default_value_class_name'], $values['advanced/default_value_method_name'])) {
							$box['tabs']['advanced']['errors'][] = adminPhrase('Please enter the name of an existing static method.');
						}
					}
				}
				break;
			
		}
	}

	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		switch ($path) {
			case 'zenario_user_dataset_field_picker':
				if (($refinerId = $box['key']['refinerId']) && $values['dataset_fields']) {
					
					$last_ordinal = self::getMaxOrdinalOfFormFields($box['key']['refinerId']);
					$last_ordinal++;
					
					$field_id = (int)$values['dataset_fields'];
					
					$dataset = getDatasetDetails('users');
					$field = getDatasetFieldDetails($field_id, $dataset);
					
					if ($field['label']) {
						$label = $field['label'];
					} else {
						$label = $field['db_column'];
						if ($field['tab_name'] && $field['field_name']) {
							$boxPath = $dataset['extends_admin_box'];
							$moduleFilesLoaded = array();
							$tags = array();
							loadTUIX($moduleFilesLoaded, $tags, $type = 'admin_boxes', $boxPath);
							if (!empty($tags[$boxPath]['tabs'][$field['tab_name']]['fields'][$field['field_name']]['label'])) {
								$label = $tags[$boxPath]['tabs'][$field['tab_name']]['fields'][$field['field_name']]['label'];
							}
						}
					}
					insertRow('user_form_fields', array('label'=>$label, 'name'=>$label, 'user_form_id'=>$refinerId, 'user_field_id'=>$field_id, 'ordinal'=>$last_ordinal));
				}
				break;
			case 'zenario_user_admin_box_form':
				$record = array();
				$record['name'] = $values['name'];
				$record['title'] = $values['title'];
				$record['use_captcha'] = $values['use_captcha'];
				$record['captcha_type'] = ($values['use_captcha'] ? $values['captcha_type'] : 'word');
				$record['extranet_users_use_captcha'] = $values['extranet_users_use_captcha'];
				$record['admin_email_use_template'] = ($values['admin_email_options'] == 'use_template');
				$record['send_email_to_user'] = (empty($values['send_email_to_user']) ? 0 : 1);
				$record['user_email_template'] = (empty($values['send_email_to_user']) ? null : $values['user_email_template']);
				$record['send_email_to_admin'] = (empty($values['send_email_to_admin']) ? 0 : 1);
				$record['admin_email_addresses'] = (empty($values['send_email_to_admin']) ? null : $values['admin_email_addresses']);
				$record['admin_email_template'] = (empty($values['send_email_to_admin']) ? null : $values['admin_email_template']);
				$removeReplyToFields = empty($values['reply_to']) || empty($values['send_email_to_admin']);
				$record['reply_to'] = ($removeReplyToFields ? 0 : 1);
				$record['reply_to_email_field'] = ($removeReplyToFields ? null : $values['reply_to_email_field']);
				$record['reply_to_first_name'] = ($removeReplyToFields ? null : $values['reply_to_first_name']);
				$record['reply_to_last_name'] = ($removeReplyToFields ? null : $values['reply_to_last_name']);
				$record['save_data'] = $values['save_data'];
				$record['save_record'] = $values['save_record'];
				$record['add_user_to_group'] = (empty($values['save_data']) ? null : $values['add_user_to_group']);
				$record['send_signal'] = (empty($values['send_signal']) ? 0 : 1);
				$record['show_success_message'] = ($values['success_message_type'] == 'show_success_message');
				$record['redirect_after_submission'] = ($values['success_message_type'] == 'redirect_after_submission');
				$record['redirect_location'] = (($values['success_message_type'] != 'redirect_after_submission') ? null : $values['redirect_location']);
				$record['success_message'] = (($values['success_message_type'] != 'show_success_message') ? null : $values['success_message']);
				$record['user_status'] = (empty($values['save_data']) ? 'contact' : $values['user_status']);
				$record['log_user_in'] = (empty($values['log_user_in']) ? 0 : 1);
				if($record['log_user_in']) {
					$record['log_user_in_cookie'] = (empty($values['log_user_in_cookie']) ? 0 : 1);
					
				} else {
					$record['log_user_in_cookie'] = 0;
				}
				$record['user_duplicate_email_action'] = (empty($values['user_duplicate_email_action']) ? null : $values['user_duplicate_email_action']);
				$record['create_another_form_submission_record'] = (empty($values['create_another_form_submission_record']) ? 0 : 1);
				$record['translate_text'] = (empty($values['translate_text']) ? 0 : 1);
				$record['submit_button_text'] = (empty($values['submit_button_text']) ? 'Submit' : $values['submit_button_text']);
				$record['default_next_button_text'] = (empty($values['default_next_button_text']) ? 'Next' : $values['default_next_button_text']);
				$record['default_previous_button_text'] = (empty($values['default_previous_button_text']) ? 'Back' : $values['default_previous_button_text']);
				$record['duplicate_email_address_error_message'] = ($values['user_duplicate_email_action'] != 'stop') ? 'Sorry this form has already been completed with this email address' : $values['duplicate_email_address_error_message'];
				
				if ($id = $box['key']['id']) {
					setRow('user_forms', $record, array('id' => $id));
					
					$formProperties = getRow('user_forms', array('translate_text'), array('id' => $id));
					// Save translations
					if ($formProperties['translate_text']) { 
						$translatableFields = array('title', 'success_message', 'submit_button_text', 'default_next_button_text', 'default_previous_button_text', 'duplicate_email_address_error_message');
						
						// Update phrase code if phrases are changed to keep translation chain
						$fieldsToTranslate = getRow('user_forms', $translatableFields, $id);
						$languages = getLanguages(false, true, true);
						
						foreach($fieldsToTranslate as $name => $oldCode) {
							// Check if old value has more than 1 entry in any translatable field
							$identicalPhraseFound = false;
							if($oldCode) {
								$sql = '
									SELECT '
										.sqlEscape(implode(', ', $translatableFields)).'
									FROM 
										'.DB_NAME_PREFIX.'user_forms
									WHERE ( 
											title = "'.sqlEscape($oldCode).'"
										OR
											success_message = "'.sqlEscape($oldCode).'"
										OR
											submit_button_text = "'.sqlEscape($oldCode).'"
										OR
											default_next_button_text = "'.sqlEscape($oldCode).'"
										OR
											default_previous_button_text = "'.sqlEscape($oldCode).'"
										OR
											duplicate_email_address_error_message = "'.sqlEscape($oldCode).'"
										)';
								$result = sqlSelect($sql);
								if (sqlNumRows($result) > 1) {
									$identicalPhraseFound = true;
								}
							}
							
							// If another field is using the same phrase code...
							if ($identicalPhraseFound) {
								foreach($languages as $language) {
									// Create or overwrite new phrases with the new english code
									$setArray = array('code' => $values[$name]);
									if (!empty($language['translate_phrases'])) {
										$setArray['local_text'] = ($values['translations/'.$name.'__'.$language['id']] !== '') ? $values['translations/'.$name.'__'.$language['id']] : null;
									}
									setRow('visitor_phrases', 
										$setArray,
										array(
											'code' => $values[$name],
											'module_class_name' => 'zenario_user_forms',
											'language_id' => $language['id']));
								}
							} else {
								// If nothing else is using the same phrase code...
								if (!checkRowExists('visitor_phrases', array('code' => $values[$name], 'module_class_name' => 'zenario_user_forms'))) {
									updateRow('visitor_phrases', 
										array('code' => $values[$name]), 
										array('code' => $oldCode, 'module_class_name' => 'zenario_user_forms'));
									foreach($languages as $language) {
										if ($language['translate_phrases'] && !empty($values['translations/'.$name.'__'.$language['id']])) {
											setRow('visitor_phrases',
												array(
													'local_text' => ($values['translations/'.$name.'__'.$language['id']] !== '' ) ? $values['translations/'.$name.'__'.$language['id']] : null), 
												array(
													'code' => $values[$name], 
													'module_class_name' => 'zenario_user_forms', 
													'language_id' => $language['id']));
										}
										
									}
								// If code already exists, and nothing else is using the code, delete current phrases, and update/create new translations
								} else {
									deleteRow('visitor_phrases', array('code' => $oldCode, 'module_class_name' => 'zenario_user_forms'));
									if (isset($values[$name]) && !empty($values[$name])) {
										foreach($languages as $language) {
											$setArray = array('code' => $values[$name]);
											if (!empty($language['translate_phrases'])) {
												$setArray['local_text'] = ($values['translations/'.$name.'__'.$language['id']] !== '' ) ? $values['translations/'.$name.'__'.$language['id']] : null;
											}
											setRow('visitor_phrases',
												$setArray,
												array(
													'code' => $values[$name], 
													'module_class_name' => 'zenario_user_forms', 
													'language_id' => $language['id']));
										}
									}
								}
							}
						}
					}
					
				} else {
					$newId = setRow('user_forms', $record, array());
					$box['key']['id'] = $newId;
				}
				break;
			case 'zenario_user_admin_box_form_field':
				$record = array();
				$formId = $box['key']['form_id'];
				if ($id = $box['key']['id']) {
					$formProperties = getRow('user_forms', array('translate_text'), array('id' => $formId));
					
					// Save translations
					if ($formProperties['translate_text']) { 
						
						$translatableFields = array('label', 'placeholder', 'note_to_user', 'required_error_message', 'validation_error_message');
						
						// Update phrase code if phrases are changed to keep translation chain
						$fieldsToTranslate = getRow('user_form_fields', $translatableFields, $id);
						$languages = getLanguages(false, true, true);
						
						foreach($fieldsToTranslate as $name => $oldCode) {
							// Check if old value has more than 1 entry in any translatable field
							$identicalPhraseFound = false;
							if($oldCode) {
								$sql = '
									SELECT '
										.sqlEscape(implode(', ', $translatableFields)).'
									FROM 
										'.DB_NAME_PREFIX.'user_form_fields
									WHERE ( 
											label = "'.sqlEscape($oldCode).'"
										OR
											placeholder = "'.sqlEscape($oldCode).'"
										OR
											note_to_user = "'.sqlEscape($oldCode).'"
										OR
											required_error_message = "'.sqlEscape($oldCode).'"
										OR
											validation_error_message = "'.sqlEscape($oldCode).'"
										)';
								$result = sqlSelect($sql);
								if (sqlNumRows($result) > 1) {
									$identicalPhraseFound = true;
								}
							}
							
							// If another field is using the same phrase code...
							if ($identicalPhraseFound) {
								foreach($languages as $language) {
									// Create or overwrite new phrases with the new english code
									$setArray = array('code' => $values['details/'.$name]);
									if (!empty($language['translate_phrases'])) {
										$setArray['local_text'] = ($values['translations/'.$name.'__'.$language['id']] !== '' ) ? $values['translations/'.$name.'__'.$language['id']] : null;
									}
									setRow('visitor_phrases', 
										$setArray,
										array(
											'code' => $values['details/'.$name],
											'module_class_name' => 'zenario_user_forms',
											'language_id' => $language['id']));
								}
							} else {
								// If nothing else is using the same phrase code...
								if (!checkRowExists('visitor_phrases', array('code' => $values[$name], 'module_class_name' => 'zenario_user_forms'))) {
									
									updateRow('visitor_phrases', 
										array('code' => $values['details/'.$name]), 
										array('code' => $oldCode, 'module_class_name' => 'zenario_user_forms'));
									foreach($languages as $language) {
										if ($language['translate_phrases'] && !empty($values['translations/'.$name.'__'.$language['id']])) {
											setRow('visitor_phrases',
												array(
													'local_text' => ($values['translations/'.$name.'__'.$language['id']] !== '' ) ? $values['translations/'.$name.'__'.$language['id']] : null), 
												array(
													'code' => $values['details/'.$name], 
													'module_class_name' => 'zenario_user_forms', 
													'language_id' => $language['id']));
										}
										
									}
								// If code already exists, and nothing else is using the code, delete current phrases, and update/create new translations
								} else {
									deleteRow('visitor_phrases', array('code' => $oldCode, 'module_class_name' => 'zenario_user_forms'));
									if (isset($values['details/'.$name]) && !empty($values['details/'.$name])) {
										foreach($languages as $language) {
											$setArray = array('code' => $values['details/'.$name]);
											if (!empty($language['translate_phrases'])) {
												$setArray['local_text'] = ($values['translations/'.$name.'__'.$language['id']] !== '' ) ? $values['translations/'.$name.'__'.$language['id']] : null;
											}
											setRow('visitor_phrases',
												$setArray,
												array(
													'code' => $values['details/'.$name], 
													'module_class_name' => 'zenario_user_forms', 
													'language_id' => $language['id']));
										}
									}
								}
							}
						}
					}
				// If new field
				} else {
					if ($box['key']['type'] == 'section_description') {
						$fieldType = 'section_description';
					} else {
						$fieldType = $values['details/field_type_picker'];
					}
					$record['field_type'] = $fieldType;
					$record['user_form_id'] = $formId;
					$record['ordinal'] = self::getMaxOrdinalOfFormFields($formId) + 1;
				}
				$record['label'] = $values['details/label'];
				$record['name'] = $values['details/name'];
				$record['is_readonly'] = $values['details/readonly_or_mandatory'] == 'readonly';
				$record['is_required'] = $values['details/readonly_or_mandatory'] == 'mandatory';
				$record['mandatory_condition_field_id'] = $values['details/readonly_or_mandatory'] == 'conditional_mandatory' ? (int)$values['details/mandatory_condition_field_id'] : 0;
				$record['mandatory_condition_field_value'] = (($values['details/readonly_or_mandatory'] == 'conditional_mandatory') && $values['details/mandatory_condition_field_id'] && ($values['details/mandatory_condition_field_value'] !== '')) ? $values['details/mandatory_condition_field_value'] : null;
				$record['required_error_message'] = ($values['details/readonly_or_mandatory'] != 'mandatory' && $values['details/readonly_or_mandatory'] != 'conditional_mandatory') ? null : $values['details/required_error_message'];
				$record['visibility'] = $values['details/visibility'];
				$record['visible_condition_field_id'] = $values['details/visibility'] == 'visible_on_condition' ? (int)$values['details/visible_condition_field_id'] : 0;
				$record['visible_condition_field_value'] = (($values['details/visibility'] == 'visible_on_condition') && $values['details/visible_condition_field_id'] && ($values['details/visible_condition_field_value'] !== '')) ? $values['details/visible_condition_field_value'] : null;
				$record['note_to_user'] = $values['details/note_to_user'];
				$record['css_classes'] = $values['details/css_classes'];
				$record['next_button_text'] = $values['details/next_button_text'] ? $values['details/next_button_text'] : null;
				$record['previous_button_text'] = $values['details/previous_button_text'] ? $values['details/previous_button_text'] : null;
				$record['description'] = $values['details/description'] ? $values['details/description'] : null;
				$record['numeric_field_1'] = $values['details/numeric_field_1'] ? $values['details/numeric_field_1'] : null;
				$record['numeric_field_2'] = $values['details/numeric_field_2'] ? $values['details/numeric_field_2'] : null;
				$record['calculation_type'] = $values['details/calculation_type'] ? $values['details/calculation_type'] : null;
				$record['restatement_field'] = $values['details/restatement_field'] ? $values['details/restatement_field'] : null;
				
				// Save advanced tab
				if (!$box['tabs']['advanced']['hidden']) {
					if ($values['advanced/default_value_mode'] == 'none') {
						$record['default_value'] = 
						$record['default_value_class_name'] =
						$record['default_value_method_name'] = 
						$record['default_value_param_1'] = 
						$record['default_value_param_2'] = null;
					} elseif ($values['advanced/default_value_mode'] == 'value') {
						
						if (in_array($values['details/field_type_picker'], array('radios', 'centralised_radios', 'select', 'centralised_select'))) {
							$record['default_value'] = $values['advanced/default_value_lov'];
						} elseif (in_array($values['details/field_type_picker'], array('text', 'textarea'))) {
							$record['default_value'] = $values['advanced/default_value_text'];
						}
						$record['default_value_class_name'] =
						$record['default_value_method_name'] = 
						$record['default_value_param_1'] = 
						$record['default_value_param_2'] = null;
					} elseif ($values['advanced/default_value_mode'] == 'method') {
						$record['default_value'] = null;
						$record['default_value_class_name'] = $values['advanced/default_value_class_name'];
						$record['default_value_method_name'] = $values['advanced/default_value_method_name'];
						$record['default_value_param_1'] = $values['advanced/default_value_param_1'];
						$record['default_value_param_2'] = $values['advanced/default_value_param_2'];
					}
				}
				
				if ($id || (!$id && ($values['details/field_type_picker'] == 'text' || $values['details/field_type_picker'] == 'textarea'))) {
					$record['placeholder'] = $values['details/placeholder'];
					$record['size'] = (empty($values['details/size']) ? 'medium' : $values['details/size']);
					$record['validation'] = (($values['details/validation'] == 'none') ? null : $values['details/validation']);
					$record['validation_error_message'] = ($values['details/validation'] == 'none') ? null : $values['details/validation_error_message'];
				}
				
				
				$record['div_wrap_class'] = $values['details/div_wrap_class'] ? $values['details/div_wrap_class'] : null;
				$oldDivWrapClass = $id ? getRow('user_form_fields', 'div_wrap_class', $id) : null;
				
				// Save details
				$id = setRow('user_form_fields', $record, array('id' => $id));
				
				// Save wrapper divs for fields below
				$ordinal = getRow('user_form_fields', 'ordinal', $id);
				$sql = '
					SELECT id, div_wrap_class, field_type
					FROM '.DB_NAME_PREFIX.'user_form_fields
					WHERE user_form_id = '.(int)$formId.'
					AND ordinal > '.(int)$ordinal.'
					ORDER BY ordinal';
				$result = sqlSelect($sql);
				while ($field = sqlFetchAssoc($result)) {
					if (($record['div_wrap_class'] == $field['div_wrap_class']) || in_array($field['field_type'], array('page_break', 'section_description')) || !in_array($field['div_wrap_class'], array('', null, $oldDivWrapClass))) {
						break;
					}
					updateRow('user_form_fields', array('div_wrap_class' => $record['div_wrap_class']), $field['id']);
				}
				
				
				
				if (in($values['details/field_type_picker'], 'checkboxes', 'radios', 'select')) {
					$existingValues = array();
					$newValues = array();
					$numValues = (int) $box['key']['numValues'];
					
					for ($i = 1; $i <= $numValues; ++$i) {
						if (isset($values['lov/label'. $i])
						 && $values['lov/label'. $i] !== '') {
							if (!empty($values['lov/id'. $i])) {
								$existingValues[$values['lov/id'. $i]] =
									array('label' => $values['lov/label'. $i], 'ord' => $i);
							} else {
								$newValues[] =
									array('label' => $values['lov/label'. $i], 'ord' => $i, 'form_field_id' => $id);
							}
						}
					}
					
					//Delete any existing values that were removed
					if ($id) {
						$sql = "
							DELETE ffv.*
							FROM ". DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX. "form_field_values AS ffv
							WHERE ffv.form_field_id = ". (int) $id;
						if (!empty($existingValues)) {
							$sql .= "
								AND ffv.id NOT IN(". inEscape(array_keys($existingValues), 'numeric'). ")";
						}
						sqlQuery($sql);
					}
					//Update the existing values
					foreach ($existingValues as $lovId => $v) {
						updateRow(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', $v, $lovId);
					}
					//Add any new values
					foreach ($newValues as $v) {
						insertRow(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', $v);
					}
				}
				break;
		}
	}
	
	public static function getMaxOrdinalOfFormFields($formId) {
		$sql = '
			SELECT MAX(ordinal) from '.DB_NAME_PREFIX. 'user_form_fields
			WHERE user_form_id = '.(int)$formId;
		$result = sqlSelect($sql);
		$ordinal = sqlFetchRow($result);
		return $ordinal[0];
	}
	
	protected function getTextFormFields($formId) {
		$formFields = array();
		$sql = "
			SELECT uff.id, cdf.db_column, cdf.label
			FROM ". DB_NAME_PREFIX. "user_form_fields AS uff
			INNER JOIN ". DB_NAME_PREFIX. "custom_dataset_fields AS cdf
				ON uff.user_field_id = cdf.id
			WHERE uff.user_form_id = ". (int)$formId. "
				AND cdf.type = 'text'
				AND uff.is_readonly = 0";
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)) {
			$formFields[] = $row;
		}
		return $formFields;
	}
	
	protected function getFormEmailField($formId) {
		// Find the email field on the form here
	}
	
	protected function fillFieldValues(&$fields, &$rec){
		foreach($rec as $k => $v){
			$fields[$k]['value'] = $v;
		}
	}
	
	public static function checkAdminBoxFormNameUnique($name,$id=false) {
		$sql = "SELECT id
				FROM " . DB_NAME_PREFIX . "user_forms
				WHERE name = '" . sqlEscape($name) . "'";
		if ($id) {
			$sql .= " AND id <> " . (int) $id;
		}
		$result = sqlQuery($sql);
		return (sqlNumRows($result)>0) ? false : true;
	}
	
	protected function dynamicallyCreateValueFieldsFromTemplate(&$box, &$fields, &$values) {
		$numValues = (int) $box['key']['numValues'];
		
		for ($i = 1; $i <= $numValues; ++$i) {
			//Add new fields that should be there by copying the template fields
			if (!isset($box['tabs']['lov']['fields']['id'. $i])) {
				$box['tabs']['lov']['fields']['id'. $i] = $box['tabs']['lov']['custom__template_fields']['id'];
				$box['tabs']['lov']['fields']['label'. $i] = $box['tabs']['lov']['custom__template_fields']['label'];
				$box['tabs']['lov']['fields']['delete'. $i] = $box['tabs']['lov']['custom__template_fields']['delete'];
				$box['tabs']['lov']['fields']['nudge_up'. $i] = $box['tabs']['lov']['custom__template_fields']['nudge_up'];
				$box['tabs']['lov']['fields']['nudge_down'. $i] = $box['tabs']['lov']['custom__template_fields']['nudge_down'];
				$box['tabs']['lov']['fields']['id'. $i]['ord'] = 10*$i + 1;
				$box['tabs']['lov']['fields']['label'. $i]['ord'] = 10*$i + 2;
				$box['tabs']['lov']['fields']['delete'. $i]['ord'] = 10*$i + 3;
				$box['tabs']['lov']['fields']['nudge_up'. $i]['ord'] = 10*$i + 4;
				$box['tabs']['lov']['fields']['nudge_down'. $i]['ord'] = 10*$i + 5;
			}
			
			$box['tabs']['lov']['fields']['id'. $i]['hidden'] = false;
			$box['tabs']['lov']['fields']['label'. $i]['hidden'] = false;
			$box['tabs']['lov']['fields']['delete'. $i]['hidden'] = false;
			$box['tabs']['lov']['fields']['nudge_up'. $i]['hidden'] = false;
			$box['tabs']['lov']['fields']['nudge_down'. $i]['hidden'] = false;
		}
		
		//Don't show the first nudge-up button or the last nudge-down button
		if ($numValues > 0) {
			$box['tabs']['lov']['fields']['nudge_up'. 1]['hidden'] = true;
			$box['tabs']['lov']['fields']['nudge_down'. $numValues]['hidden'] = true;
		}
	}
	
	protected function nudge(&$box, &$fields, &$values, $i, $j) {
		$id = $values['lov/id'. $i];
		$label = $values['lov/label'. $i];
		$values['lov/id'. $i] = $values['lov/id'. $j];
		$values['lov/label'. $i] = $values['lov/label'. $j];
		$values['lov/id'. $j] = $id;
		$values['lov/label'. $j] = $label;
	}
	
	public static function getUnlinkedFieldLOV($formFieldId, $flat = true) {
		if ($flat) {
			$cols = 'label';
		} else {
			$cols = array('ord', 'label');
		}
		return getRowsArray(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', $cols, array('form_field_id' => $formFieldId), 'ord');
	}
	
	public static function fieldTypeCanRecordValue($type) {
		return !in_array($type, array('page_break', 'section_description', 'restatement'));
	}
}
