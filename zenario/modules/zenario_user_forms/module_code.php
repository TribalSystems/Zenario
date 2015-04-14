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
	
	var $data = array();
	
	public function init() {
		$this->data['showForm'] = true;
		$formProperties = getRow('user_forms', 
			array('name', 'title', 'success_message', 'show_success_message', 'use_captcha', 'captcha_type', 'extranet_users_use_captcha', 'send_email_to_admin', 'admin_email_use_template', 'translate_text'), 
			$this->setting('user_form'));
			
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
		if (post('submit_form') && $this->instanceId == post('instanceId')) {
			$this->data['errors'] = self::validateUserForm($this->setting('user_form'), $_POST, $this->instanceId);
			
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
			
			
			if (empty($this->data['errors'])) {
				unset($_SESSION['captcha_passed__'. $this->instanceId]);
				$url = $this->linkToItem($this->cID, $this->cType, true, '', false, false, true);
				
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
			$this->data['formFields'] = self::drawUserForm($this->setting('user_form'), $_POST, false, $this->data['errors']);
		} else {
			$this->data['formFields'] = self::drawUserForm($this->setting('user_form'), userId());
		}
		$enctype = $this->getFormEncType($this->setting('user_form'));
		$this->data['openForm'] = $this->openForm('', $enctype);
		$this->data['closeForm'] = $this->closeForm();
		
		
		if ($this->setting('display_mode')=='in_modal_window') {
			if (!empty($_REQUEST['show_user_form']) || $this->checkPostIsMine()) {
				$this->showInFloatingBox();
			} else {
				$this->data['displayText'] = self::formPhrase($this->setting('display_text'), array(), $translate);
				$this->data['showForm'] = false;
				$this->data['showFormJS'] = $this->refreshPluginSlotAnchor('show_user_form=1', false, false);
			}
		}
		return true;
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
	
	private static function getFormEncType($formId) {
		if (checkRowExists('user_form_fields', array('field_type' => 'attachment', 'user_form_id' => $formId))) {
			return 'enctype="multipart/form-data"';
		}
		return '';
	}
	
	public static function getUserFormFields($userFormId) {
		$formFields = array();
		$sql = "
			SELECT 
				uff.id AS form_field_id, 
				uff.user_field_id, 
				uff.ordinal, 
				uff.is_readonly, 
				uff.is_required,
				uff.required_field,
				uff.required_value,
				uff.label AS field_label,
				uff.name,
				uff.placeholder,
				uff.size,
				uff.default_value,
				uff.note_to_user,
				uff.css_classes,
				uff.required_error_message,
				uff.validation AS field_validation,
				uff.validation_error_message,
				uff.field_type,
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
			WHERE uf.id = ". (int)$userFormId . "
			ORDER BY uff.ordinal";
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)){
			
			if ($row['type'] == 'checkboxes') {
				$values = getDatasetFieldLOV($row['field_id']);
				$row['field_values'] = $values;
			}
			
			$formFields[$row['form_field_id']] = $row;
		}
		return $formFields;
	}
	
	public static function drawUserForm($userFormId, $loadData = false, $readOnly = false, $errors = array()) {
		$html = '';
		$formFields = self::getUserFormFields($userFormId);
		$translate = getRow('user_forms', 'translate_text', array('id' => $userFormId));
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
		
		foreach($formFields as $fieldId => $field) {
			// Begin form field html and add any extra classes
			$html .= '<div class="form_field '. htmlspecialchars($field['css_classes']) .'">';
			
			$userFieldId = $field['user_field_id'];
			$fieldName = ($field['db_column'] ? $field['db_column'] : 'unlinked_'.$field['field_type'].'_'.$fieldId);
			$type = ($field['type'] ? $field['type'] : $field['field_type']);
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
			
			if ($type == 'checkbox' || $type == 'group') {
				$html .= $errorHTML;
				$html .= $labelHTML;
			} else {
				$html .= $labelHTML;
				$html .= $errorHTML;
			}
			
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
			
			switch ($type) {
				case 'group':
				case 'checkbox':
					if ($readOnly || $field['is_readonly']) {
						$html .= '<div class="field_data">';
						$html .= ((isset($data[$fieldName]) && $data[$fieldName] == 1) ? phrase('Yes') : phrase('No'));
						$html .= '</div>';
						
					} else {
						$html .= '<input type="checkbox" name="'. htmlspecialchars($fieldName) .'"';
						if (isset($data[$fieldName]) && (($data[$fieldName] == 1) || $data[$fieldName] == 'on')) {
							$html .= 'checked';
						}
						$html .= '/>';
					}
					
					break;
				case 'checkboxes':
					if ($userFieldId) {
						$valuesList = getDatasetFieldLOV($userFieldId);
					} else {
						$valuesList = self::getUnlinkedFieldLOV($fieldId);
					}
					
					if ($readOnly || $field['is_readonly']) {
						$html .= '<div class="field_data">';
							foreach ($valuesList as $valueId => $label) {
								$selected = isset($data[$valueId. '_'. $fieldName]);
								if ($selected){
									$html .= self::formPhrase($label, array(), $translate);
								}
							}
						$html .= '</div>';
					} else {
						foreach ($valuesList as $valueId => $label) {
							$selected = isset($data[$valueId. '_'. $fieldName]);
							$html .= self::formPhrase($label, array(), $translate);
							$html .= '<input type="checkbox" name="'. htmlspecialchars($valueId. '_'. $fieldName). '"';
							if ($selected) {
								$html .= ' checked="checked"';
							}
							$html .= '/>';
						}
					}
					break;
				case 'date':
					if ($readOnly || $field['is_readonly']) {
						$html .= '<div class="field_data">';
						if (isset($data[$fieldName])) {
							$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.htmlspecialchars($data[$fieldName]).'" />';
							$data[$fieldName] = formatDateNicely($data[$fieldName], 'vis_date_format_med');
							$html .= $data[$fieldName];
						}
						$html .= '</div>';
					} else {
						$html .= '<input type="text" name="'. htmlspecialchars($fieldName). '" class="jquery_datepicker" readonly ';
						if (isset($data[$fieldName])) {
							//$data[$fieldName] = formatDateNicely($data[$fieldName], 'vis_date_format_med');
							$html .= 'value="'. $data[$fieldName] .'"';
						}
						$html .='/>';
					}
					break;
				case 'editor':
					if ($readOnly || $field['is_readonly']) {
						$html .= '<div class="field_data">';
						if (isset($data[$fieldName])) {
							$html .= $data[$fieldName];
							$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.htmlspecialchars($data[$fieldName]).'" />';
						}
						$html .= '</div>';
					} else {
						$html .= '<textarea name="'. htmlspecialchars($fieldName). '" class="tinymce"/>';
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
					
					if ($readOnly || $field['is_readonly']) {
						$html .= '<div class="field_data">';
						if (isset($data[$fieldName])) {
							$label = getRow('custom_dataset_field_values', 'label', array('id' => $data[$fieldName]));
							$html .= self::formPhrase($label, array(), $translate);
							$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.htmlspecialchars($data[$fieldName]).'" />';
						}
						$html .= '</div>'; 
					} else {
						$i = 0;
						foreach ($valuesList as $valueId => $label) {
							if ($i != 0) {
								$html .= '<br />';
							}
							$i++;
							$html .= '<input type="radio" name="'. htmlspecialchars($fieldName). '" value="'. htmlspecialchars($valueId) .'"';
							if (isset($data[$fieldName]) && ($valueId == $data[$fieldName])) {
								$html .= 'checked="checked"';
							}
							$html .= '/>'. self::formPhrase($label, array(), $translate);
						}
					}
					break;
				case 'centralised_radios':
					if ($readOnly || $field['is_readonly']){
						$values = getDatasetFieldLOV($userFieldId);
						$html .= '<div class="field_data">';
						if (!empty($values[$data[$fieldName]])) {
							$html .= $values[$data[$fieldName]];
						}
						$html .= '</div>'; 
					} else {
						$i = 0;
						foreach (getDatasetFieldLOV($userFieldId) as $valueId => $label) {
							if ($i != 0) {
								$html .= '<br />';
							}
							$i++;
							$html .= '<input type="radio" name="'. htmlspecialchars($fieldName). '" value="'. htmlspecialchars($valueId) .'"';
							if (isset($data[$fieldName]) && $valueId == $data[$fieldName]) {
								$html .= 'checked="checked"';
							}
							$html .= '/>'. self::formPhrase($label, array(), $translate);
						}
					}
					break;
				case 'select':
					if ($userFieldId) {
						$valuesList = getDatasetFieldLOV($userFieldId);
					} else {
						$valuesList = self::getUnlinkedFieldLOV($fieldId);
					}
					
					if ($readOnly || $field['is_readonly']) {
						$html .= '<div class="field_data">';
						if (isset($data[$fieldName])) {
							$label = getRow('custom_dataset_field_values', 'label', array('id' => $data[$fieldName]));
							$html .= $label;
						}
						$html .= '</div>'; 
					} else {
						$html .= '<select name="'. htmlspecialchars($fieldName) .'">';
						$html .= '<option value="">'.self::formPhrase('-- Select --', array(), $translate).'</option>';
						foreach ($valuesList as $valueId => $label) {
							$html .= '<option value="'. htmlspecialchars($valueId) . '"';
							if (isset($data[$fieldName]) && $data[$fieldName] == $valueId) {
								$html .= ' selected="selected"';
							}
							$html .= '>'. self::formPhrase($label, array(), $translate) . '</option>';
						}
						$html .= '</select>';
					}
					break;
				case 'centralised_select':
					if ($readOnly || $field['is_readonly']) {
						$values = getDatasetFieldLOV($userFieldId);
						$html .= '<div class="field_data">';
						if (!empty($data[$fieldName])
						 && isset($values[$data[$fieldName]])) {
							$html .= $values[$data[$fieldName]];
						}
						$html .= '</div>';
					} else {
						$html .= '<select name="'. htmlspecialchars($fieldName) .'">';
						$html .= '<option value="">'.self::formPhrase('-- Select --', array(), $translate).'</option>';
						foreach (getDatasetFieldLOV($userFieldId) as $valueId => $label) {
							$html .= '<option value="'. htmlspecialchars($valueId). '"';
							if (isset($data[$fieldName]) && ($data[$fieldName] == $valueId)) {
								$html .= ' selected="selected"';
							}
							$html .= '>'. $label . '</option>';
						}
						$html .= '</select>';
					}
					break;
				case 'url':
				case 'text':
					if ($readOnly || $field['is_readonly']) {
						if (isset($data[$fieldName])) {
							$html .= '<div class="field_data">';
							if (!empty($data[$fieldName])) {
								$html .= htmlspecialchars($data[$fieldName]);
							} else {
								if (!empty($field['default_value'])) {
									$html .= htmlspecialchars(self::formPhrase($field['default_value'], array(), $translate));
								}
							}
							$html .= '</div>';
							$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.htmlspecialchars($data[$fieldName]) .'" />';
						}
					} else {
						$type = 'text';
						if ($field['field_validation'] == 'email') {
							$type = 'email';
						}
						$html .= '<input type="'.htmlspecialchars($type).'" name="'. htmlspecialchars($fieldName). '" size="'. htmlspecialchars($size) .'"';
						if (isset($data[$fieldName]) && $data[$fieldName] !== '' && $data[$fieldName] !== false) {
							$html .= ' value="'. htmlspecialchars($data[$fieldName]). '"';
						} else {
							if (!empty($field['default_value'])) {
								$html .= ' value="'. htmlspecialchars(self::formPhrase($field['default_value'], array(), $translate)) .'"';
							}
						}
						if (!empty($field['placeholder'])) {
							$html .= ' placeholder="'.htmlspecialchars(self::formPhrase($field['placeholder'], array(), $translate)) .'"';
						}
						$html .= '/>';
					}
					
					break;
				case 'textarea':
					if ($readOnly || $field['is_readonly']) {
						if (isset($data[$fieldName])) {
							$html .= '<div class="field_data">';
							$html .= htmlspecialchars($data[$fieldName]);
							$html .= '</div>';
							$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.htmlspecialchars($data[$fieldName]) .'" />';
						}
					} else {
						$html .= '<textarea name="'. htmlspecialchars($fieldName) .'" rows="4" cols="51"';
						if (!empty($field['placeholder'])) {
							$html .= ' placeholder="'.htmlspecialchars(self::formPhrase($field['placeholder'], array(), $translate)) .'"';
						}
						$html .= '/>';
						if (isset($data[$fieldName])) {
							$html .= htmlspecialchars($data[$fieldName]);
						}
						
						$html .= '</textarea>';
					}
					break;
				case 'attachment':
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
			}
			
			// Add a note at the bottom of the field to the user
			if (!empty($field['note_to_user'])) {
				$html .= '<div class="note_to_user">'. self::formPhrase($field['note_to_user'], array(), $translate) .'</div>';
			}
			// End form field html
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
	
	public static function validateUserForm($userFormId, $data, $instanceId = 0) {
		
		$formFields = self::getUserFormFields($userFormId);
		$translate = getRow('user_forms', 'translate_text', array('id' => $userFormId));
		$requiredFields = array();
		$fileFields = array();
		foreach ($formFields as $fieldId => $field) {
			$userFieldId = $field['user_field_id'];
			$fieldName = $field['db_column'] ? $field['db_column'] : 'unlinked_'.$field['field_type'].'_'.$fieldId;
			$type = $field['type'] ? $field['type'] : $field['field_type'];
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
							if (filter_var($data[$fieldName], FILTER_VALIDATE_URL) === false) {
								$valid = false;
							}
							break;
						case 'integer':
							if (!filter_var($data[$fieldName], FILTER_VALIDATE_INT)) {
								$valid = false;
							}
							break;
						case 'number':
							if (!is_numeric($data[$fieldName])) {
								$valid = false;
							}
							break;
						case 'floating_point':
							if (!filter_var($data[$fieldName], FILTER_VALIDATE_FLOAT)) {
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
					$requiredFields[$fieldId] = array('name' => $fieldName, 'message' => $validationMessage);
				}
				
			} elseif ($type == 'attachment') {
				$fileFields[] = $fieldName;
			}
			
			// If this field relies on another field, check if it should be set to mandatory
			if ($field['required_field'] && ($field['required_value'] !== null)) {
				$requiredFieldId = $field['required_field'];
				$requiredField = $formFields[$requiredFieldId];
				$requiredFieldName = $requiredField['db_column'] ? $requiredField['db_column'] : 'unlinked_'.$requiredField['field_type'].'_'.$requiredFieldId;
				$requiredFieldType = $requiredField['type'] ? $requiredField['type'] : $requiredField['field_type'];
				switch($requiredFieldType) {
					case 'checkbox':
						if ($field['required_value'] == 1) {
							if (isset($data[$requiredFieldName])) {
								$field['is_required'] = true;
							}
						} elseif ($field['required_value'] == 0) {
							if (!isset($data[$requiredFieldName])) {
								$field['is_required'] = true;
							}
						}
						break;
					case 'radios':
					case 'centralised_radios':
					case 'centralised_select':
					case 'select':
						if (isset($data[$requiredFieldName]) && $data[$requiredFieldName] === $field['required_value']) {
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
							$requiredFields[$fieldId] = array('label' => $field['label'], 'message' => self::formPhrase($field['required_error_message'], array(), $translate));
						}
						break;
					case 'checkboxes':
						$isChecked = false;
						foreach (getDatasetFieldLOV($userFieldId) as $valueId => $label) {
							if (isset($data[$valueId. '_' . $fieldName])) {
								$isChecked = true;
								break;
							}
						}
						
						if (!$isChecked) {
							$requiredFields[$fieldId] = array('label' => $field['label'], 'message' => self::formPhrase($field['required_error_message'], array(), $translate));
						}
						break;
					case 'text':
					case 'date':
					case 'editor':
					case 'textarea':
					case 'url':
						if ($data[$fieldName] === '' || $data[$fieldName] === false) {
							$requiredFields[$fieldId] = array('label' => $field['label'], 'message' => self::formPhrase($field['required_error_message'], array(), $translate));
						}
						break;
					case 'radios':
					case 'centralised_radios':
					case 'centralised_select':
					case 'select':
						if (!isset($data[$fieldName]) || $data[$fieldName] === '') {
							$requiredFields[$fieldId] = array('label' => $field['label'], 'message' => self::formPhrase($field['required_error_message'], array(), $translate));
						}
						break;
					case 'attachment':
						if ((!isset($_FILES[$fieldName]) && empty($_FILES[$fieldName]['tmp_name']))
							&& !isset($data[$fieldName]) && !empty($data[$fieldName])) {
							$requiredFields[$fieldId] = array('label' => $field['label'], 'message' => self::formPhrase($field['required_error_message'], array(), $translate));
						}
						break;
					
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
			$fieldName = ($field['db_column'] ? $field['db_column'] : 'unlinked_'. $field['field_type'].'_'.$fieldId);
			$type = ($field['type'] ? $field['type'] : $field['field_type']);
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
						'user_field_id' => $userFieldId);
					break;
				case 'date':
					$date = '';
					if ($data[$fieldName]) {
						$date = date('Y-m-d H:i:s', strtotime($data[$fieldName]));
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
				case 'editor':
				case 'text':
				case 'textarea':
				case 'url':
					$values[$fieldId] = array('value' => $data[$fieldName], 'db_column' => $fieldName, 'ordinal' => $ordinal);
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
		}
		// Save data against user record
		if ($formProperties['save_data']) {
			$fields = array();
			foreach ($user_fields as $fieldData) {
				$fields[$fieldData['db_column']] = $fieldData['value'];
			}
			
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
					logUserIn($userId);
				}
			}
		}
		
		// Save a record of the submission
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
		if ($formProperties['send_email_to_user'] && $formProperties['user_email_template'] && isset($data['email'])) {
			// Send email
			$values = $user_fields + $user_custom_fields + $checkBoxValues + $unlinked_fields;
			$emailMergeFields = self::getTemplateEmailMergeFields($values);
			zenario_email_template_manager::sendEmailsUsingTemplate($data['email'],$formProperties['user_email_template'],$emailMergeFields,array());
		}
		// Send an email to administrators
		if ($formProperties['send_email_to_admin'] && $formProperties['admin_email_addresses']) {
			// Set reply to address and name
			$replyToEmail = false;
			$replyToName = false;
			if ($formProperties['reply_to'] && $formProperties['reply_to_email_field'] && $formProperties['reply_to_first_name']) {
				if (isset($data[$formProperties['reply_to_email_field']]) && isset($data[$formProperties['reply_to_first_name']])) {
					$replyToEmail = $data[$formProperties['reply_to_email_field']];
					$replyToName = $data[$formProperties['reply_to_first_name']];
					if (isset($data[$formProperties['reply_to_last_name']])) {
						$replyToName .= ' '.$data[$formProperties['reply_to_last_name']];
					}
				}
			}
			// Send email
			if ($formProperties['admin_email_use_template'] && $formProperties['admin_email_template']) {
				if (!$emailMergeFields) {
					$values = $user_fields + $user_custom_fields + $checkBoxValues + $unlinked_fields;
					$emailMergeFields = self::getTemplateEmailMergeFields($values);
				}
				zenario_email_template_manager::sendEmailsUsingTemplate(
					$formProperties['admin_email_addresses'],
					$formProperties['admin_email_template'],
					$emailMergeFields,
					array(),
					array(),
					$replyToEmail,
					$replyToName);
			} else {
				$emailValues = array();
				$values = $user_fields + $user_custom_fields + $checkBoxValues + $unlinked_fields;
				foreach ($values as $fieldId => $fieldData) {
					if (isset($fieldData['attachment'])) {
						$fieldData['value'] = absCMSDirURL().fileLink($fieldData['internal_value']);
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
			sendSignal('eventUserFormSubmitted', array('data' => $formattedData, 'rawData' => $data, 'formProperties' => $formProperties, 'fieldIdValueLink' => $fieldIdValueLink));
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
			case 'zenario__user_forms/panels/zenario_user_forms__user_responses':
				$formFields = getRowsArray('user_form_fields', array('name', 'id'), array('user_form_id' => $refinerId), 'ordinal');
				foreach ($formFields as $key => $formField) {
					$panel['columns']['form_field_'.$formField['id']] = array(
						'title' => $formField['name'],
						'show_by_default' => true,
						'searchable' => true,
						'sortable' => true);
				}
				break;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__user_forms/panels/zenario_user_forms__forms':
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
						$refinerId;
				}
				$panel['item_buttons']['edit']['admin_box']['key']['form_id'] = $refinerId;
				
				
				
				break;
			case 'zenario__user_forms/panels/zenario_user_forms__user_responses':
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
						unset($formField['id']);
						$formField['user_form_id'] = $id;
						insertRow('user_form_fields', $formField);
					}
				}
				break;
			case 'zenario__user_forms/panels/zenario_user_forms__form_fields':
				if (post('reorder')) {
					$form_id = (int)post('refiner__user_form_id');
					foreach (explode(',', $ids) as $id) {
						if (post('item__'. $id)) {
							$sql = "
								UPDATE ". DB_NAME_PREFIX. "user_form_fields SET
									ordinal = ". (int) post('item__'. $id). "
								WHERE id = ". (int) $id . 
								" AND user_form_id=" . (int)$form_id;
							sqlUpdate($sql);
						}
					}
					return;
				}
				
				if (post('action') == 'delete') {
					$form_id = (int)post('refiner__user_form_id');
					// Remove deleted fields
					foreach (explode(',', $ids) as $id) {
						deleteRow('user_form_fields', array('id' =>$id, 'user_form_id' => $form_id));
					}
					// Update remaining field ordinals
					$formFieldIds = getRowsArray('user_form_fields', 'id', array('user_form_id' => $refinerId), 'ordinal');
					$ordinal = 0;
					foreach($formFieldIds as $id) {
						$ordinal++;
						updateRow('user_form_fields', array('ordinal' => $ordinal), array('id' => $id));
					}
				}
				break;
		}
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
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch($path) {
			case 'zenario_user_form_response':
				$box['title'] = adminPhrase('Form response [[id]]', array('id' => $box['key']['id']));
				$responseDateTime = getRow(ZENARIO_USER_FORMS_PREFIX. 'user_response', 'response_datetime', $box['key']['id']);
				$values['response_datetime'] = formatDateTimeNicely($responseDateTime, 'vis_date_format_med');
				
				$formFields = getRowsArray('user_form_fields', array('name', 'id', 'field_type', 'ordinal'), array('user_form_id' => request('refiner__form_id')), 'ordinal');
				$userResponse = array();
				$result = getRows(ZENARIO_USER_FORMS_PREFIX. 'user_response_data',
					array('form_field_id', 'value', 'internal_value'),
					array('user_response_id' => $box['key']['id']));
				while ($row = sqlFetchAssoc($result)) {
					$userResponse[$row['form_field_id']] = array('value' => $row['value'], 'internal_value' => $row['internal_value']);
				}
				
				foreach ($formFields as $formField) {
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
						$field['type'] = 'text';
					}
					$field['value'] = $responseValue;
					$box['tabs']['form_fields']['fields']['form_field_' . $formField['id']] = $field;
				}
				break;
			case 'zenario_user_dataset_field_picker':
				$box['key']['refinerId'] = get('refinerId');
				break;
			case 'zenario_user_admin_box_form':
				if (get('refinerName') == 'archived') {
					foreach($box['tabs'] as &$tab) {
						$tab['edit_mode']['enabled'] = false;
					}
				}
				$formTextFieldLabels = array();
				$formTextFieldLabels[''] = array('label' => '-- Select --');
				
				if ($id = $box['key']['id']) {
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
				} else {
					$box['title'] = adminPhrase('Creating a Form');
					$values['data/save_data'] = 
					$values['data/save_record'] = true;
					
				}
				// Set text field select lists (will just be -- Select -- if creating new form)
				$fields['data/reply_to_email_field']['values'] =
				$fields['data/reply_to_first_name']['values'] =
				$fields['data/reply_to_last_name']['values'] =
					$formTextFieldLabels;
				break;
			case 'zenario_user_admin_box_form_field':
				
				// If no conditional field types, hide conditional mandatory option
				$conditionalFields = self::getConditionalFields($box['key']['form_id']);
				if (empty($conditionalFields)) {
					unset($fields['details/readonly_or_mandatory']['values']['conditional_mandatory']);
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
					
					$formFieldValues = getRow('user_form_fields', 
						array(
							'user_field_id',
							'is_readonly', 
							'is_required',
							'required_field',
							'required_value',
							'name',
							'label', 
							'size', 
							'placeholder',
							'default_value', 
							'note_to_user', 
							'css_classes', 
							'required_error_message',
							'validation',
							'validation_error_message',
							'field_type'), 
						array('id' => $id));
					
					$values['details/field_type_picker'] = $formFieldValues['field_type'];
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
					
					if ($formProperties['translate_text']) {
						$languages = getLanguages(false, true, true);
						$translatableLanguage = false;
						foreach ($languages as $language) {
							if ($language['translate_phrases']) {
								$translatableLanguage = true;
							}
						}
						
						if ($translatableLanguage) {
							// Generate translations tab
							$fieldsToTranslate = array(
								'label' => $formFieldValues['label'],
								'note_to_user' => $formFieldValues['note_to_user'],
								'required_error_message' => $formFieldValues['required_error_message']);
							if ($fieldType == 'text') {
								$fieldsToTranslate['placeholder'] = $formFieldValues['placeholder'];
								$fieldsToTranslate['default_value'] = $formFieldValues['default_value'];
								$fieldsToTranslate['validation_error_message'] = $formFieldValues['validation_error_message'];
							} elseif ($fieldType == 'textarea') {
								$fieldsToTranslate['placeholder'] = $formFieldValues['placeholder'];
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
								
								foreach($languages as $language) {
									if ($language['translate_phrases']) {
										$value = '';
										if (isset($existingPhrases[$name]) && isset($existingPhrases[$name][$language['id']])) {
											$value = $existingPhrases[$name][$language['id']];
										}
										$box['tabs']['translations']['fields'][$name.'__'.$language['id']] = array(
											'class_name' => 'zenario_user_forms',
											'ord' => $ord,
											'label' => $language['english_name']. ':',
											'type' => 'text',
											'value' => $value,
											'read_only' => $readOnly);
									}
									$ord++;
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
					
					$dataset = getDatasetDetails('users');
					$systemFieldLabel = 
						getRow('custom_dataset_fields', 
							'label', 
							array('dataset_id' => $dataset['id'], 'id' => $formFieldValues['user_field_id']));
					$values['details/name'] = $formFieldValues['name'];
					$values['details/label'] = (($formFieldValues['label'] === null) ?  $systemFieldLabel : $formFieldValues['label']);
					
					if ($formFieldValues['is_readonly']) {
						$values['readonly_or_mandatory'] = 'readonly';
					} elseif($formFieldValues['is_required']) {
						$values['readonly_or_mandatory'] = 'mandatory';
					} elseif($formFieldValues['required_field']) {
						$values['readonly_or_mandatory'] = 'conditional_mandatory';
					} else {
						$values['readonly_or_mandatory'] = 'none';
					}
					
					
					
					$values['details/condition_field'] = $formFieldValues['required_field'];
					$values['details/mandatory_if'] = $formFieldValues['required_value'];
					
					
					
					$values['details/placeholder'] = $formFieldValues['placeholder'];
					$values['details/size'] = $formFieldValues['size'];
					$values['details/default_value'] = $formFieldValues['default_value'];
					$values['details/note_to_user'] = $formFieldValues['note_to_user'];
					$values['details/css_classes'] = $formFieldValues['css_classes'];
					$values['details/required_error_message'] = $formFieldValues['required_error_message'];
					$values['details/validation'] = (empty($formFieldValues['validation']) ? 'none' : $formFieldValues['validation']);
					$values['details/validation_error_message'] = $formFieldValues['validation_error_message'];
					
					$box['title'] = adminPhrase('Editing the Form field "[[name]]"', array('name' => $formFieldValues['name']));
				} else {
					unset($box['tabs']['translations']);
					$box['title'] = adminPhrase('Creating a new unlinked form field');
					$fields['details/field_type_picker']['hidden'] = false;
					$fields['details/unlinked_form_field_description']['hidden'] = false;
					$this->dynamicallyCreateValueFieldsFromTemplate($box, $fields, $values);
				}
				break;
			case 'zenario_email_template':
				$forms = getRowsArray('user_forms', 'name', array(), 'name');
				$fields['body/user_form']['values'] = $forms;
				break;
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_user_admin_box_form':
				
				$fields['details/translate_text']['hidden'] = !checkRowExists('languages', array('translate_phrases' => 1));
				
				$fields['captcha/captcha_type']['hidden'] =
				$fields['captcha/extranet_users_use_captcha']['hidden'] =
					!$values['captcha/use_captcha'];
				
				$fields['data/user_status']['hidden'] =
				$fields['data/email_html']['hidden'] =
				$fields['data/add_user_to_group']['hidden'] =
				$fields['data/duplicate_submission_html']['hidden'] =
				$fields['data/user_duplicate_email_action']['hidden'] =
				$fields['data/create_another_form_submission_record']['hidden'] =
					!$values['data/save_data'];
				
				$fields['data/log_user_in']['hidden'] =
					!($values['data/save_data'] && ($values['data/user_status'] == 'active'));
				
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
				
				$fields['data/create_another_form_submission_record']['disabled'] = !$values['data/save_record'];
				if (!$values['data/save_record']) {
					$values['data/create_another_form_submission_record'] = false;
				}
				if (empty($box['key']['id'])) {
					$values['data/create_another_form_submission_record'] = $values['data/save_record'];
				}
				
				break;
			case 'zenario_user_admin_box_form_field':
				$languages = getLanguages(false, true, true);
				$translatableFields = array('label', 'placeholder', 'default_value', 'note_to_user', 'required_error_message', 'validation_error_message');
				foreach($translatableFields as $fieldName) {
					$fields['translations/'.$fieldName]['snippet']['html'] = '<b>'.$fields['details/'.$fieldName]['label'].'</b>';
					if (!empty($values['details/'.$fieldName])) {
						$fields['translations/'.$fieldName]['snippet']['html'] .= ' "'.$values['details/'.$fieldName].'"';
						$readOnly = false;
					} else {
						$readOnly = true;
						$fields['translations/'.$fieldName]['snippet']['html'] .= ' (Value not set)';
					}
					foreach($languages as $language) {
						$fields['translations/'.$fieldName.'__'.$language['id']]['read_only'] = $readOnly;
					}
				}
				// Populate conditional fields list
				$conditionalFields = self::getConditionalFields($box['key']['form_id']);
				if ($values['details/readonly_or_mandatory'] == 'conditional_mandatory') {
					$fields['details/condition_field']['values'] = $conditionalFields;
				}
				$fields['details/condition_field']['hidden'] = 
					($values['details/readonly_or_mandatory'] != 'conditional_mandatory');
				
				// Populate manditory if value list for field
				if ($conditionalFieldId = $values['details/condition_field']) {
					$fieldValues = self::getConditionalFieldValuesList($conditionalFieldId);
					$fields['details/mandatory_if']['values'] = $fieldValues;
				}
				
				$fields['details/mandatory_if']['hidden'] = 
					($values['details/readonly_or_mandatory'] != 'conditional_mandatory') || (!$values['details/condition_field']);
				
				$fields['details/required_error_message']['hidden'] = 
					!(($values['details/readonly_or_mandatory'] == 'mandatory') || 
					($values['details/readonly_or_mandatory'] == 'conditional_mandatory'));
				
				$fields['details/validation_error_message']['hidden'] = 
					($values['details/validation'] == 'none');
				if (!$box['key']['id']) {
					$fields['details/label']['hidden'] = 
					$fields['details/name']['hidden'] = 
					$fields['details/readonly_or_mandatory']['hidden'] = 
					$fields['details/note_to_user']['hidden'] = 
					$fields['details/css_classes']['hidden'] = 
						!$values['details/field_type_picker'];
					
					$fields['details/default_value']['hidden'] = 
					$fields['details/size']['hidden'] = 
					$fields['details/validation']['hidden'] = 
						!($values['details/field_type_picker'] == 'text');
					
					$fields['details/placeholder']['hidden'] =
						!($values['details/field_type_picker'] == 'text' || $values['details/field_type_picker'] == 'textarea');
				}
				
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
				
				break;
			case 'zenario_email_template':
				if ($formId = $values['body/user_form']) {
					// Get list of form fields for form
					$fields['body/user_form_field']['hidden'] = false;
					$sql = '
						SELECT
							uff.id,
							IF(
								uff.label IS NULL or uff.label = "", 
								IFNULL(
									cdf.db_column, 
									CONCAT("unlinked_", uff.field_type, "_", uff.id)
								), 
								uff.label
							) AS label
						FROM '. DB_NAME_PREFIX. 'user_form_fields AS uff
						LEFT JOIN '. DB_NAME_PREFIX.'custom_dataset_fields AS cdf
							ON uff.user_field_id = cdf.id
						WHERE uff.user_form_id = '.(int)$formId. '
						ORDER BY uff.ordinal';
					$result = sqlSelect($sql);
					$formFields = array();
					$formFields['all'] = adminPhrase('Add all to template');
					while ($row = sqlFetchAssoc($result)) {
						$formFields[$row['id']] = trim($row['label'], " \t\n\r\0\x0B:");
					}
					$fields['body/user_form_field']['values'] = $formFields;
					
					
					if ($formFieldId = $values['body/user_form_field']) {
						// Add form field mergefield onto end of email template
						$sql = '
							SELECT IFNULL(uff.label, cdf.label) AS label, IFNULL(cdf.db_column, CONCAT(\'unlinked_\', uff.field_type, \'_\', uff.id)) AS mergefield
							FROM '.DB_NAME_PREFIX.'user_form_fields AS uff
							LEFT JOIN '.DB_NAME_PREFIX. 'custom_dataset_fields AS cdf
								ON uff.user_field_id = cdf.id';
						if ($formFieldId == 'all') {
							$sql .= ' WHERE uff.user_form_id = '.(int)$formId;
						} else {
							$sql .= ' WHERE uff.id = '.(int)$formFieldId;
						}
						
						$result = sqlSelect($sql);
						$mergeFields = '';
						while ($row = sqlFetchAssoc($result)) {
							$mergeFields .= '<p>';
							if ($row['label']) {
								$mergeFields .= trim($row['label'], " \t\n\r\0\x0B:"). ': ';
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
		$customFieldId = $fieldDetails['user_field_id'];
		if ($customFieldId) {
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
				if ($saving
					&& empty($values['data/save_data'])
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
				$record['user_duplicate_email_action'] = (empty($values['user_duplicate_email_action']) ? null : $values['user_duplicate_email_action']);
				$record['create_another_form_submission_record'] = (empty($values['create_another_form_submission_record']) ? 0 : 1);
				
				$record['translate_text'] = (empty($values['translate_text']) ? 0 : 1);
				
				if ($id = $box['key']['id']) {
					$newId = setRow('user_forms', $record, array('id' => $id));
				} else {
					$newId = setRow('user_forms', $record, array());
					$box['key']['id'] = $newId;
				}
				break;
			case 'zenario_user_admin_box_form_field':
				$formId = $box['key']['form_id'];
				if ($id = $box['key']['id']) {
					$formProperties = getRow('user_forms', array('translate_text'), array('id' => $formId));
					
					if ($formProperties['translate_text']) { 
						$translatableFields = array('label', 'placeholder', 'default_value', 'note_to_user', 'required_error_message', 'validation_error_message');
						
						// Update phrase code if phrases are changed to keep translation chain
						$fieldsToTranslate = getRow('user_form_fields', $translatableFields, $id);
						$languages = getLanguages(false, true, true);
						
						foreach($fieldsToTranslate as $name => $oldCode) {
							// Check if old value has more than 1 entry in any translatable field
							$identicalPhraseFound = false;
							if($oldCode) {
								$sql = '
									SELECT 
										label, placeholder, default_value, note_to_user, required_error_message, validation_error_message
									FROM 
										'.DB_NAME_PREFIX.'user_form_fields
									WHERE ( 
											label = "'.sqlEscape($oldCode).'"
										OR
											placeholder = "'.sqlEscape($oldCode).'"
										OR
											default_value = "'.sqlEscape($oldCode).'"
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
								} else {
									$count = 0;
									$row = sqlFetchAssoc($result);
									foreach($row as $value) {
										if ($value == $oldCode) {
											$count++;
										}
									}
									$identicalPhraseFound = ($count > 1);
								}
							}
							
							// If another field is using the same phrase code...
							if ($identicalPhraseFound) {
								foreach($languages as $language) {
									// Create or overwrite new phrases with the new english code
									$setArray = array('code' => $values['details/'.$name]);
									if (!empty($language['translate_phrases'])) {
										$setArray['local_text'] = $values['translations/'.$name.'__'.$language['id']];
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
													'local_text' => $values['translations/'.$name.'__'.$language['id']]), 
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
												$setArray['local_text'] = $values['translations/'.$name.'__'.$language['id']];
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
				}
				$record = array();
				$record['label'] = $values['details/label'];
				$record['name'] = $values['details/name'];
				$record['is_readonly'] = $values['details/readonly_or_mandatory'] == 'readonly';
				$record['is_required'] = $values['details/readonly_or_mandatory'] == 'mandatory';
				
				$record['required_field'] = $values['details/readonly_or_mandatory'] == 'conditional_mandatory' ? (int)$values['details/condition_field'] : 0;
				$record['required_value'] = (($values['details/readonly_or_mandatory'] == 'conditional_mandatory') && $values['details/condition_field'] && ($values['details/mandatory_if'] !== '')) ? $values['details/mandatory_if'] : null;
				
				$record['required_error_message'] = ($values['details/readonly_or_mandatory'] != 'mandatory' && $values['details/readonly_or_mandatory'] != 'conditional_mandatory') ? null : $values['details/required_error_message'];
				$record['note_to_user'] = $values['details/note_to_user'];
				$record['css_classes'] = $values['details/css_classes'];
				
				if ($id || (!$id && ($values['details/field_type_picker'] == 'text' || $values['details/field_type_picker'] == 'textarea'))) {
					$record['placeholder'] = $values['details/placeholder'];
					$record['size'] = (empty($values['details/size']) ? 'medium' : $values['details/size']);
					$record['default_value'] = $values['details/default_value'];
					$record['validation'] = (($values['details/validation'] == 'none') ? null : $values['details/validation']);
					$record['validation_error_message'] = ($values['details/validation'] == 'none') ? null : $values['details/validation_error_message'];
				}
				
				// If new unlinked form field
				if (!$id) {
					$record['field_type'] = $values['details/field_type_picker'];
					$record['user_form_id'] = $formId;
					$record['ordinal'] = self::getMaxOrdinalOfFormFields($formId) + 1;
				}
				
				$id = setRow('user_form_fields', $record, array('id' => $id));
				
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
}
