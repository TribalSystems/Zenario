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

$formFields = self::getUserFormFields($userFormId);

// Get form extra properties
$formProperties = getRow(
	'user_forms',
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
		'extranet_users_use_captcha',
		'profanity_filter_text'
	),
	array('id' => $userFormId)
);

$values = array();
$checkBoxValues = array();
$user_fields = array();
$user_custom_fields = array();
$unlinked_fields = array();
$user_multi_value_fields = false;
$email_field = false;
$duplicate_email_found = false;
$fieldIdValueLink = array();

// List of attachments to add to the email depending on site setting
$attachments = array();

foreach ($formFields as $fieldId => $field) {
	$userFieldId = $field['user_field_id'];
	$fieldName = self::getFieldName($field);
	$type = self::getFieldType($field);
	$ordinal = $field['ordinal'];
	
	
	// Ignore field if readonly 
	if ($field['is_readonly'] && $type != 'text') {
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
			$values[$fieldId] = array('value' => $value, 'db_column' => $fieldName, 'ordinal' => $ordinal, 'readonly' => $field['is_readonly']);
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
				if (setting('zenario_user_forms_admin_email_attachments')) {
					$attachments[] = fileLink($fileId);
				}
			}
			$fieldIdValueLink[$fieldId] = $fileId;
			break;
	}
	if (isset($values[$fieldId])) {
		$values[$fieldId]['type'] = $type;
	}
}
// Unset reference
unset($values);

$zenario_extranet = inc('zenario_extranet');

// Save data against user record
if ($formProperties['save_data'] && $zenario_extranet) {
	$fields = array();
	foreach ($user_fields as $fieldData) {
		if (empty($fieldData['readonly'])) {
			$fields[$fieldData['db_column']] = $fieldData['value'];
		}
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
			self::saveUserMultiCheckboxData($checkBoxValues, $userId);
		}
		if ($userId) {
			addUserToGroup($userId, $formProperties['add_user_to_group']);
			// Log user in
			if ($formProperties['log_user_in']) {
				
				$user = logUserIn($userId);
				
				if($formProperties['log_user_in_cookie'] && canSetCookie()) {
					setCookieOnCookieDomain('LOG_ME_IN_COOKIE', $user['login_hash']);
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

// Send emails
// Profanity check
$profanityFilterEnabled = setting('zenario_user_forms_set_profanity_filter');
$profanityToleranceLevel = setting('zenario_user_forms_set_profanity_tolerence');

if ($formProperties['profanity_filter_text']) {
	
	$profanityValuesToCheck = $user_fields + $user_custom_fields + $unlinked_fields;
	$wordsCount = 0;
	$allValuesFromText = "";
	
	foreach ($profanityValuesToCheck as $text) {
		$wordsCount = $wordsCount + str_word_count($text['value']);
		$isSpace = substr($text['value'], -1);
		
		if ($isSpace != " ") {
			$allValuesFromText .= $text['value'] . " ";
		} else {
			$allValuesFromText .= $text['value'];
		}
	}
	
	$profanityRating = zenario_user_forms::scanTextForProfanities($allValuesFromText);
}

if (!$formProperties['profanity_filter_text'] || ($profanityRating < $profanityToleranceLevel)) {
	
	$sendEmailToUser = ($formProperties['send_email_to_user'] && $formProperties['user_email_template'] && isset($data['email']));
	$sendEmailToAdmin = ($formProperties['send_email_to_admin'] && $formProperties['admin_email_addresses']);
	$values = array();
	$userEmailMergeFields = 
	$adminEmailMergeFields = false;
	
	if ($sendEmailToUser || $sendEmailToAdmin) {
		$values = $user_fields + $user_custom_fields + $checkBoxValues + $unlinked_fields;
	}
	
	// Send an email to the user
	if ($sendEmailToUser) {
		
		// Get merge fields
		$userEmailMergeFields = self::getTemplateEmailMergeFields($values, $userId);
		
		// Send email
		zenario_email_template_manager::sendEmailsUsingTemplate($data['email'], $formProperties['user_email_template'], $userEmailMergeFields, array());
	}

	// Send an email to administrators
	if ($sendEmailToAdmin) {
		
		// Get merge fields
		$adminEmailMergeFields = self::getTemplateEmailMergeFields($values, $userId, true);
		
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
				$adminEmailMergeFields,
				$attachments,
				array(),
				false,
				$replyToEmail,
				$replyToName
			);
		} else {
			$emailValues = array();
			foreach ($values as $fieldId => $fieldData) {
				if (isset($fieldData['attachment'])) {
					$fieldData['value'] = absCMSDirURL() . 'zenario/file.php?adminDownload=1&id=' . $fieldData['internal_value'];
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
				$attachments,
				array(),
				0,
				false,
				$replyToEmail,
				$replyToName
			);
		}
	}
} else {
	// Update if profanity filter was set in responses
	if($profanityFilterEnabled) {
		updateRow(ZENARIO_USER_FORMS_PREFIX. 'user_response', array('blocked_by_profanity_filter' => 1), array('id' => $user_response_id));
	}
}

//Set default values for this form submission for profanity filter
if ($formProperties['profanity_filter_text']) {
	updateRow(ZENARIO_USER_FORMS_PREFIX. 'user_response', 
		array(
			'profanity_filter_score' => $profanityRating, 
			'profanity_tolerance_limit' => $profanityToleranceLevel
		), 
		array('id' => $user_response_id)
	);
}

// Send a signal if specified
if ($formProperties['send_signal']) {
	$formProperties['user_form_id'] = $userFormId;
	$values = $user_fields + $user_custom_fields + $checkBoxValues + $unlinked_fields;
	$formattedData = self::getTemplateEmailMergeFields($values, $userId);
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
