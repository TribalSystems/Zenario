<?php
/*
 * Copyright (c) 2016, Tribal Limited
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

$dataset = getDatasetDetails('users');
$formProperties = getRow(
	'user_forms', 
	array('translate_text', 'default_next_button_text', 'default_previous_button_text'), 
	array('id' => $userFormId)
);
$formFields = self::getUserFormFields($userFormId);
$translate = $formProperties['translate_text'];

$submitted = false;
$preloadUserData = false;
$data = array();

//If $loadData is an array, use that as the data
if ($loadData && is_array($loadData)) {
	$data = $loadData;
	$submitted = true;
//If $loadData is a number, try to load data for that user
} elseif ($loadData && is_numeric($loadData)) {
	$preloadUserData = $loadData;
}

// Begin form field HTML
$html = '';

// Add inputs so the form can tell when it needs to filter lists
$html .= '<input type="hidden" name="filter_list" id="' . $containerId . '_filter_list"/>';
$html .= '<input type="hidden" name="filter_list_id" id="' . $containerId . '_filter_list_id"/>';
$html .= '<input type="hidden" name="filter_list_value" id="' . $containerId . '_filter_list_value"/>';

// Variables to handle wrapper divs
$currentDivWrapClass = false;
$wrapDivOpen = false;

// Check whether this is a multi page form
$pageBreakFields = getRowsArray('user_form_fields', 'id', array('field_type' => 'page_break', 'user_form_id' => $userFormId), array('ord'));
if ($pageBreakFields) {
	$html .= '<fieldset id="'.$containerId.'_page_1" class="page_1">';
	$page = 1;
}

// Start drawing form fields
foreach ($formFields as $fieldId => $field) {
	
	$type = self::getFieldType($field);
	$fieldName = self::getFieldName($field);
	$userFieldId = $field['user_field_id'];
	$fieldIsReadonly = ($readOnly || $field['is_readonly']);
	
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
	
	// Add page breaks and navigation buttons
	if ($type == 'page_break') {
		if ($fieldId != reset($pageBreakFields)) {
			$previousButtonText = $field['previous_button_text'] ? $field['previous_button_text'] : $formProperties['default_previous_button_text'];
			$html .= '<input type="button" name="previous" value="' . self::formPhrase($previousButtonText, array(), $translate) . '" class="previous"/>';
		}
		$nextButtonText = $field['next_button_text'] ? $field['next_button_text'] : $formProperties['default_next_button_text'];
		$html .= '<input type="button" name="next" value="' . self::formPhrase($nextButtonText, array(), $translate) . '" class="next"/>';
		$html .= '</fieldset><fieldset id="' . $containerId . '_page_' . ++$page . '" class="page_' . $page . '" style="display:none;">';
		continue;
	}
	
	// Get ID of inputs for mirror fields and label elements
	$id = $containerId . '_field_value_' . $fieldId;
	$mirrorId = false;
	$mirrorFieldId = false;
	
	// Use form field label over dataset label
	if ($field['field_label'] !== null) {
		$field['label'] = $field['field_label'];
	}
	
	// Field error
	$errorHTML = '';
	if (isset($errors[$fieldId])) {
		$errorHTML = '<div class="form_error">' . $errors[$fieldId]['message'] . '</div>';
	}
	// Field label
	$labelHTML = '';
	if (!empty($field['label'])) {
		$labelHTML = '<div class="field_title">' . self::formPhrase($field['label'], array(), $translate) . '</div>';
	}
	
	$html .= '<div id="' . $containerId . '_field_' . htmlspecialchars($fieldId) . '" data-id="' . htmlspecialchars($fieldId) . '"';
	
	// Calculated fields should be readonly text fields showing 0 as default
	if ($type == 'calculated') {
		if (empty($data[$fieldName])) {
			$data[$fieldName] = 0;
		}
		$type = 'text';
		$field['is_readonly'] = true;
	
	// Restatement (mirror) fields should be readonly fields the same type as their target field
	} elseif ($type == 'restatement') {
		if (isset($formFields[$field['restatement_field']])) {
			$mirrorId = $containerId . '_field_value_' . $field['restatement_field'];
			$mirrorFieldId = $fieldId;
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
	
	// Field CSS classes
	$html .= ' class="form_field field_' . htmlspecialchars($type) . ' ' . htmlspecialchars($field['css_classes']);
	if ($fieldIsReadonly) {
		$html .= ' readonly ';
	}
	$html .= '"';
	
	// Handle hiding a field
	$hidden = false;
	if ($field['visibility'] == 'hidden') {
		$hidden = true;
	
	} elseif (($field['visibility'] == 'visible_on_condition') 
		&& $field['visible_condition_field_id'] 
		&& isset($formFields[$field['visible_condition_field_id']])
	) {
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
			$conditionFieldValue = '';
			if (isset($data[$visibleConditionFieldName])) {
				$conditionFieldValue = $data[$visibleConditionFieldName];
			}
			
			$hidden = ($conditionFieldValue !== $field['visible_condition_field_value']);
			
			if (!$field['visible_condition_field_value'] && $conditionFieldValue) {
				$hidden = false;
			}
			
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
	
	// Hide hidden fields
	if ($hidden) {
		$html .= ' style="display:none;" ';
	}
	$html .= '>';
	
	// Position errors and labels for checkbox type fields
	if (!in_array($type, array('checkbox', 'group'))) {
		$html .= $labelHTML . $errorHTML;
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
	
	// Get loaded data for field
	if ($type == 'checkboxes' || $type == 'file_picker') {
		$loadedFieldValue = $data;
	} else {
		$loadedFieldValue = isset($data[$fieldName]) ? $data[$fieldName] : null;
	}
	
	// Get this form fields current value
	$fieldValue = self::getFormFieldValue($field, $type, $submitted, $loadedFieldValue, $preloadUserData, $dataset);
	
	// Draw fields
	switch ($type) {
		case 'group':
		case 'checkbox':
			$html .= $errorHTML . '<input type="checkbox" ';
			if (!empty($fieldValue)) {
				$html .= ' checked="checked" ';
			}
			if ($fieldIsReadonly) {
				$html .= ' disabled ';
			}
			if ($field['field_type'] == 'restatement') {
				$html .= ' data-mirror-of="' . htmlspecialchars($mirrorId) . '" ';
			} else {
				$html .= ' name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($id) . '" onchange="zenario_user_forms.updateRestatementFields(this.id, \'checkbox\');" ';
			}
			$html .= '/>';
			if ($fieldIsReadonly && !empty($fieldValue) && $field['field_type'] != 'restatement') {
				$html .= '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($fieldValue) . '"/>';
			}
			$html .= '<label class="field_title" for="' . htmlspecialchars($id) . '">'. self::formPhrase($field['label'], array(), $translate) .'</label>';
			break;
		case 'checkboxes':
			
			// Get list of values
			if ($userFieldId) {
				$valuesList = getDatasetFieldLOV($userFieldId);
			} else {
				$valuesList = self::getUnlinkedFieldLOV($fieldId);
			}
			
			$html .= '<div class="checkboxes_wrap';
			if ($checkboxColumns > 1) {
				$items = count($valuesList);
				$cols = (int)$checkboxColumns;
				$rows = ceil($items/$cols);
				$currentRow = $currentCol = 1;
				$html .= ' columns_'.$checkboxColumns;
			}
			$html .= '">';
			
			$fieldValues = array();
			if ($fieldValue) {
				$fieldValues = explode(',', $fieldValue['ids']);
			}
			
			foreach ($valuesList as $valueId => $label) {
				
				$checkBoxHtml = '';
				$name = $valueId . '_' . $fieldName; 
				$multiFieldId = $id . '_' . $valueId;
				
				
				$selected = in_array($valueId, $fieldValues);
				$checkBoxHtml .= '<div class="field_checkbox"><input type="checkbox" ';
				if ($selected) {
					$checkBoxHtml .= ' checked="checked"';
				}
				if ($fieldIsReadonly) {
					$checkBoxHtml .= ' disabled ';
				}
				if ($field['field_type'] == 'restatement') {
					$checkBoxHtml .= ' data-mirror-of="' . htmlspecialchars($multiFieldId) . '" ';
					
					// Stop mirror field labels selecting target field checkboxes
					$multiFieldId = '';
					
				} else {
					$checkBoxHtml .= ' name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($multiFieldId) . '" onchange="zenario_user_forms.updateRestatementFields(this.id, \'checkbox\');" ';
				}
				$checkBoxHtml .= '/>';
				if ($fieldIsReadonly && $selected && $field['field_type'] != 'restatement') {
					$checkBoxHtml .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . $selected . '" />';
				}
				$checkBoxHtml .= '<label for="'.$multiFieldId.'">' . self::formPhrase($label, array(), $translate) . '</label></div>';
				
				
				if (($checkboxColumns > 1) && ($currentRow > $rows)) {
					$currentRow = 1;
					$currentCol++;
				}
				if (($checkboxColumns > 1) && ($currentRow == 1)) {
					$html .= '<div class="col_'.$currentCol.' column">';
				}
				$html .= $checkBoxHtml;
				if (($checkboxColumns > 1) && ($currentRow++ == $rows)) {
					$html .= '</div>';
				}
			}
			$html .= '</div>';
			break;
		case 'date':
			$html .= '<input type="text" readonly class="jquery_form_datepicker" ';
			if ($fieldIsReadonly) {
				$html .= ' disabled ';
			}
			if ($field['field_type'] == 'restatement') {
				$html .= ' data-mirror-of="' . htmlspecialchars($mirrorId) . '" ';
				$html .= '/>';
			} else {
				$html .= ' id="' . htmlspecialchars($id) . '" onchange="zenario_user_forms.updateRestatementFields(this.id);" ';
				$html .= '/>';
				$html .= '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" id="' . $id . '__0"';
				if (!empty($fieldValue)) {
					$html .= ' value="' . htmlspecialchars($fieldValue) . '" ';
				}
				$html .= '/>';
			}
			
			break;
		case 'radios':
			
			// Get list of values
			if ($userFieldId) {
				$valuesList = getDatasetFieldLOV($userFieldId);
			} else {
				$valuesList = self::getUnlinkedFieldLOV($fieldId);
			}
			
			foreach ($valuesList as $valueId => $label) {
				
				$multiFieldId = $id . '_' . $valueId;
				
				$html .= '<div class="field_radio"><input type="radio"  value="' . htmlspecialchars($valueId) . '"';
				if ($valueId == $fieldValue) {
					$html .= ' checked="checked" ';
				}
				if ($fieldIsReadonly) {
					$html .= ' disabled ';
				}
				if ($field['field_type'] == 'restatement') {
					$html .= ' name="' . htmlspecialchars($fieldName . '_' . $field['form_field_id']) . '" data-mirror-of="' . htmlspecialchars($multiFieldId) . '" ';
					
					// Stop mirror field labels selecting target field radios
					$multiFieldId = '';
					
				} else {
					$html .= ' name="'. htmlspecialchars($fieldName). '" id="' . htmlspecialchars($multiFieldId) . '" onclick="zenario_user_forms.updateRestatementFields(this.id, \'radio\');" ';
				}
				$html .= '/><label for="'.$multiFieldId.'">' . self::formPhrase($label, array(), $translate) . '</label></div>'; 
			}
			
			if ($fieldIsReadonly && !empty($fieldValue) && $field['field_type'] != 'restatement') {
				$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.htmlspecialchars($fieldValue).'" />';
			}
			
			break;
		case 'centralised_radios':
			
			// Get list of values
			if ($userFieldId) {
				$values = getDatasetFieldLOV($userFieldId);
				$valuesSource = $field['dataset_values_source'];
			} else {
				$values = self::getUnlinkedFieldLOV($fieldId);
				$valuesSource = $field['values_source'];
			}
			
			// If this field looks like it's using the countries list, get phrases from country manager
			$countryList = ($valuesSource == 'zenario_country_manager::getActiveCountries');
			
			$count = 1;
			foreach ($values as $valueId => $label) {
				
				$multiFieldId = $id . '_' . $count++;
				
				$html .= '<div class="field_radio"><input type="radio" value="' . htmlspecialchars($valueId) . '"';
				if ($valueId == $fieldValue) {
					$html .= 'checked="checked"';
				}
				if ($fieldIsReadonly) {
					$html .= ' disabled ';
				}
				if ($field['field_type'] == 'restatement') {
					$html .= ' name="' . htmlspecialchars($fieldName . '_' . $field['form_field_id']) . '" data-mirror-of="' . htmlspecialchars($multiFieldId) . '" ';
					
					// Stop mirror field labels selecting target field radios
					$multiFieldId = '';
					
				} else {
					$html .= ' name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($multiFieldId) . '" onclick="zenario_user_forms.updateRestatementFields(this.id, \'radio\');" ';
				}
				$html .= '/><label for="' . htmlspecialchars($multiFieldId) . '">';
				if ($countryList && $translate) {
					$html .=  phrase('_COUNTRY_NAME_'.$valueId, array(), 'zenario_country_manager');
				} else {
					$html .= self::formPhrase($label, array(), $translate);
				}
				$html .= '</label></div>';
			}
			
			if ($fieldIsReadonly && isset($fieldValue) && $field['field_type'] != 'restatement') {
				$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.htmlspecialchars($fieldValue).'" />';
			}
			
			break;
		case 'select':
			
			// Get list of values
			if ($userFieldId) {
				$valuesList = getDatasetFieldLOV($userFieldId);
				$valuesSource = $field['dataset_values_source'];
			} else {
				$valuesList = self::getUnlinkedFieldLOV($fieldId);
				$valuesSource = $field['values_source'];
			}
			
			// If this field looks like it's using the countries list, get phrases from country manager
			$countryList = ($valuesSource == 'zenario_country_manager::getActiveCountries');
			
			$html .= '<select ';
			if ($fieldIsReadonly) {
				$html .= ' disabled ';
			}
			if ($field['field_type'] == 'restatement') {
				$html .= ' data-mirror-of="' . htmlspecialchars($mirrorId) . '" ';
			} else {
				$html .= ' name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($id) . '" onchange="zenario_user_forms.updateRestatementFields(this.id);" ';
			}
			$html .= '><option value="">' . self::formPhrase('-- Select --', array(), $translate) . '</option>';
			
			foreach ($valuesList as $valueId => $label) {
				$html .= '<option value="' . htmlspecialchars($valueId) . '"';
				if ($valueId == $fieldValue) {
					$html .= ' selected="selected" ';
				}
				$html .= '>';
				if ($countryList && $translate) {
					phrase('_COUNTRY_NAME_'.$valueId, array(), 'zenario_country_manager');
				} else {
					$html .= self::formPhrase($label, array(), $translate);
				}
				$html .= '</option>';
			}
			$html .= '</select>';
			
			if ($fieldIsReadonly && ($field['field_type'] != 'restatement')) {
				$html .= '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($fieldValue) . '" />';
			}
			
			break;
		case 'centralised_select':
			
			// Filter this list if a source field has been changed
			$filtered = false;
			if (post('filter_list') && post('filter_list_id') && post('filter_list_value')) {
				$sourceField = getRow(
					ZENARIO_USER_FORMS_PREFIX . 'form_field_update_link', 
					'source_field_id',
					array('target_field_id' => $field['form_field_id'])
				);
				if ($sourceField && ($sourceField == post('filter_list_id'))) {
					$filtered = true;
					$values = getCentralisedListValues($field['values_source'], post('filter_list_value'));
				}
			}
			
			// Get full list of values
			if (!$filtered) {
				if ($userFieldId) {
					$values = getDatasetFieldLOV($userFieldId);
				} else {
					$unlinkedFieldId = $mirrorFieldId ? $mirrorFieldId : $fieldId;
					$values = self::getUnlinkedFieldLOV($unlinkedFieldId);
				}
			}
			
			$html .= '<select ';
			if ($fieldIsReadonly) {
				$html .= ' disabled ';
			}
			if ($field['field_type'] == 'restatement') {
				$html .= ' data-mirror-of="' . htmlspecialchars($mirrorId) . '" ';
			} else {
				$html .= ' name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($id) . '" onchange="zenario_user_forms.updateRestatementFields(this.id);';
				
				// Check if this field is a source field for other centralised select lists
				$targetFields = getRowsArray(
					ZENARIO_USER_FORMS_PREFIX . 'form_field_update_link', 
					'target_field_id', 
					array('source_field_id' => $field['form_field_id'])
				);
				
				if (!empty($targetFields)) {
					$html .= '
						$(\'#' . $containerId . '_filter_list\').val(1);
						$(\'#' . $containerId . '_filter_list_id\').val(' . $field['form_field_id'] . ');
						$(\'#' . $containerId . '_filter_list_value\').val(this.value);
						this.form.onsubmit();
					';
				}
				$html .= '
					return false;" 
				';
			}
			$html .= '><option value="">'.self::formPhrase('-- Select --', array(), $translate).'</option>';
			
			
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
			
			if ($fieldIsReadonly && ($field['field_type'] != 'restatement')) {
				$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.$fieldValue.'" />';
			}
			
			break;
		case 'url':
		case 'text':
			$type = 'text';
			if ($field['field_validation'] == 'email') {
				$type = 'email';
			}
			$html .= '<input type="' . $type . '" size="' . htmlspecialchars($size) . '"';
			
			if ($fieldIsReadonly) {
				$html .= ' readonly ';
			}
			if ($field['field_type'] == 'restatement') {
				$html .= ' data-mirror-of="' . htmlspecialchars($mirrorId) . '" ';
			} else {
				$html .= ' name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($id) . '" onkeyup="zenario_user_forms.updateRestatementFields(this.id);" ';
			}
			
			if ($fieldValue !== false) {
				$html .= ' value="'. htmlspecialchars($fieldValue). '"';
			}
			if (isset($field['placeholder']) && $field['placeholder'] !== '' && $field['placeholder'] !== null) {
				$html .= ' placeholder="' . htmlspecialchars(self::formPhrase($field['placeholder'], array(), $translate)) . '"';
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
			$html .= ' maxlength="' . $maxlength . '" />';
			
			break;
		case 'textarea':
			
			$html .= '<textarea name="' . htmlspecialchars($fieldName) . '" rows="4" cols="51"';
			if (isset($field['placeholder']) && $field['placeholder'] !== '' && $field['placeholder'] !== null) {
				$html .= ' placeholder="' . htmlspecialchars(self::formPhrase($field['placeholder'], array(), $translate)) . '"';
			}
			if ($fieldIsReadonly) {
				$html .= ' readonly ';
			}
			if ($field['field_type'] == 'restatement') {
				$html .= ' data-mirror-of="' . htmlspecialchars($mirrorId) . '" ';
			} else {
				$html .= ' name="'. htmlspecialchars($fieldName).'" id="' . htmlspecialchars($id) . '" onkeyup="zenario_user_forms.updateRestatementFields(this.id);" ';
			}
			$html .= '>';
			
			if ($fieldValue !== false) {
				$html .= htmlspecialchars($fieldValue);
			}
			$html .= '</textarea>';
			
			break;
		case 'attachment':
			if ($fieldIsReadonly) {
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
		case 'file_picker':
			
			if ($fieldIsReadonly) {
				
				$html .= '<div class="files">';
				if ($fieldValue) {
					$fileIds = str_getcsv((string)$fieldValue, ',', '"', '//');
					
					$count = 0;
					foreach ($fileIds as $fileId) {
						if (is_numeric($fileId) 
							&& checkRowExists(
								'custom_dataset_files_link', 
								array(
									'dataset_id' => $dataset['id'], 
									'field_id' => $userFieldId, 
									'linking_id' => userId(), 
									'file_id' => $fileId
								)
							)
							&& ($fileLink = fileLink($fileId))
						) {
							
							$file = getRow('files', array('filename'), $fileId);
							$name = $file['filename'];
							
							$html .= '<div class="file_row">';
							$html .= '<p><a href="' . $fileLink . '" target="_blank">' . $file['filename'] . '</a></p>';
							$html .= '<input name="file_picker_' . $fieldId . '_' . (++$count) . '" type="hidden" value="' . $fileId . '" />';
							$html .= '</div>';
						}
					}
				} else {
					$html .= self::formPhrase('No file found...', array(), $translate);
				}
				$html .= '</div>';
				
				$html .= '<input type="hidden" name="'.htmlspecialchars($fieldName).'" value="'.htmlspecialchars($fieldValue) .'" />';
			} else {
				$filesJSON = array();
				if ($fieldValue) {
					$fileIds = str_getcsv((string)$fieldValue, ',', '"', '//');
					
					foreach ($fileIds as $fileId) {
						
						$fileData = array(
							'id' => $fileId
						);
						
						// If numeric file ID make sure this is linked to the user to prevent someone seeing someone elses file details
						if (is_numeric($fileId) 
							&& checkRowExists(
								'custom_dataset_files_link', 
								array(
									'dataset_id' => $dataset['id'], 
									'field_id' => $userFieldId, 
									'linking_id' => userId(), 
									'file_id' => $fileId
								)
							)
						) {
							$file = getRow('files', array('filename'), $fileId);
							$name = $file['filename'];
							
							$fileData['download_link'] = fileLink($fileId);
							
						// Otherwise show filename from cache path
						} else {
							$name = substr(basename($fileId), 0, -7);
						}
						
						$fileData['name'] = $name;
						
						$filesJSON[] = $fileData;
					}
				}
				$html .= '<div class="loaded_files" style="display:none;">' . json_encode($filesJSON) . '</div>';
				$html .= '<div class="files"></div>';
				$html .= '<div class="progress_bar" style="display:none;"></div>';
				$html .= '<div class="file_upload_button"><span>' . self::formPhrase('Upload file', array(), $translate) . '</span>';
				$html .= '<input class="file_picker_field" type="file" name="' . htmlspecialchars($fieldName) . '"';
				$fileCount = 1;
				if ($field['multiple_select']) {
					$html .= ' multiple';
					$fileCount = 5;
				}
				$html .= ' data-limit="' . $fileCount . '"';
				if ($field['extensions']) {
					$html .= 'data-extensions="' . htmlspecialchars($field['extensions']) . '"';
				}
				$html .= '/></div>';
			}
			break;
		
		case 'section_description':
			$html .= '<div class="description"><p>' . htmlspecialchars($field['description']) . '</p></div>';
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