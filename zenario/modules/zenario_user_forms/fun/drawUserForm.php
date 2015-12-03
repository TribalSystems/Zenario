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

$formFields = self::getUserFormFields($userFormId);
$formProperties = getRow('user_forms', array('translate_text', 'default_next_button_text', 'default_previous_button_text'), array('id' => $userFormId));
$translate = $formProperties['translate_text'];
$submitted = false;


//If $loadData is an array, use that as the data
if ($loadData && is_array($loadData)) {
	$data = $loadData;
	$submitted = true;
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

$pageBreakFields = getRowsArray('user_form_fields', 'id', array('field_type' => 'page_break', 'user_form_id' => $userFormId), array('ord'));

// Begin form field HTML
$html = '';

// Add inputs so the form can tell when it needs to filter lists
$html .= '<input type="hidden" name="filter_list" id="' . $containerId . '_filter_list"/>';
$html .= '<input type="hidden" name="filter_list_id" id="' . $containerId . '_filter_list_id"/>';
$html .= '<input type="hidden" name="filter_list_value" id="' . $containerId . '_filter_list_value"/>';

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
	if (in_array($type, array('radios', 'centralised_radios', 'select', 'centralised_select', 'text', 'textarea', 'checkbox', 'group'))) {
		
		if ($submitted) {
			if (isset($data[$fieldName])) {
				$fieldValue = $data[$fieldName];
			}
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
			if ($fieldValue && (($fieldValue == 1) || $fieldValue == 'on')) {
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
			if ($userFieldId) {
				$values = getDatasetFieldLOV($userFieldId);
				$valuesSource = getRow('custom_dataset_fields', 'values_source', array('id' => $field['user_field_id']));
			} else {
				$values = self::getUnlinkedFieldLOV($fieldId);
				$valuesSource = $field['values_source'];
			}
			
			// If this field looks like it's using the countries list, get phrases from country manager
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
				$valuesSource = getRow('custom_dataset_fields', 'values_source', array('id' => $field['user_field_id']));
			} else {
				$valuesList = self::getUnlinkedFieldLOV($fieldId);
				$valuesSource = $field['values_source'];
			}
			
			// If this field looks like it's using the countries list, get phrases from country manager
			$countryList = ($valuesSource == 'zenario_country_manager::getActiveCountries');
			
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
				$html .= '>';
				
				if ($countryList && $translate) {
					phrase('_COUNTRY_NAME_'.$valueId, array(), 'zenario_country_manager');
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
					$values = self::getUnlinkedFieldLOV($fieldId);
				}
			}
			
			$html .= '<select ';
			if ($readOnly || $field['is_readonly']) {
				$html .= ' disabled ';
			}
			if ($field['field_type'] == 'restatement') {
				$html .= ' data-mirror-of="'.$id.'" ';
			} else {
				$html .= ' name="'. htmlspecialchars($fieldName).'" id="'.$id.'" onchange="
					zenario_user_forms.updateRestatementFields(this.id);';
				
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