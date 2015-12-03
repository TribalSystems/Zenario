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
				if (empty($_FILES[$fieldName]['tmp_name']) && empty($data[$fieldName])) {
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
		 || !preg_match('@^cache/uploads/[\w\-\_]+/[\w\.-]+\.upload$@', $_POST[$fieldName])) {
			unset($_POST[$fieldName]);
		}
	}
	unset($_GET[$fieldName]);
}

return $requiredFields;