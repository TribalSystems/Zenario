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

// Get fields on form
$formFields = static::getUserFormFields($userFormId);

// Get form properties
$formProperties = getRow(
	'user_forms',
	array(
		'name',
		'type',
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
	$userFormId
);

$values = array();
$userSystemFields = array();
$userCustomFields = array();
$unlinkedFields = array();
$checkBoxValues = array();
$filePickerValues = array();

$fieldIdValueLink = array();

// List of attachments to add to emails depending on site setting
$attachments = array();

foreach ($formFields as $fieldId => $field) {
	$userFieldId = $field['user_field_id'];
	$fieldName = static::getFieldName($field);
	$type = static::getFieldType($field);
	$ord = $field['ord'];
	
	if ($field['is_system_field']){
		$valueType = 'system';
		$values = &$userSystemFields;
	} elseif ($field['user_field_id']) {
		$valueType = 'custom';
		$values = &$userCustomFields;
	} else {
		$valueType = 'unlinked';
		$values = &$unlinkedFields;
	}
	
	// Array to store link between input name and file picker value
	$filePickerValueLink = array();
	
	// Get loaded data for field
	if ($type == 'checkboxes' || $type == 'file_picker') {
		$loadedFieldValue = $data;
	} else {
		$loadedFieldValue = isset($data[$fieldName]) ? $data[$fieldName] : null;
	}
	
	$fieldValue = static::getFormFieldValue($field, $type, true, $loadedFieldValue, false, false, $filePickerValueLink);
	
	switch ($type){
		case 'group':
		case 'checkbox':
			if (!empty($fieldValue)) {
				$checked = 1;
				$eng = adminPhrase('Yes');
			} else {
				$checked = 0;
				$eng = adminPhrase('No');
			}
			
			$values[$fieldId] = array('value' => $eng, 'internal_value' => $checked);
			$fieldIdValueLink[$fieldId] = $checked;
			break;
		case 'checkboxes':
			// Store checkbox values to save in record
			$checkBoxValues[$fieldId] = array(
				'internal_value' => $fieldValue['ids'], 
				'value' => $fieldValue['labels'], 
				'ord' => $ord, 
				'db_column' => $fieldName,
				'value_type' => $valueType,
				'user_field_id' => $userFieldId,
				'type' => $type,
				'readonly' => $field['is_readonly']
			);
			break;
		case 'date':
			$date = '';
			if ($fieldValue) {
				$date = $fieldValue;
			}
			$values[$fieldId] = array('value' => $date);
			$fieldIdValueLink[$fieldId] = $date;
			break;
		case 'radios':
		case 'select':
		case 'centralised_radios':
		case 'centralised_select':
			
			if ($userFieldId) {
				$valuesList = getDatasetFieldLOV($userFieldId);
			} else {
				$valuesList = static::getUnlinkedFieldLOV($fieldId);
			}
			$fieldIdValueLink[$fieldId] = array();
			if (!empty($fieldValue)) {
				
				$fieldIdValueLink[$fieldId][$fieldValue] = $valuesList[$fieldValue];
				$values[$fieldId] = array('value' => $valuesList[$fieldValue],  'internal_value' => $fieldValue);
			}
			break;
		
		case 'text':
		case 'url':
		case 'calculated':
			$value = $fieldValue ? $fieldValue : '';
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
					break;
			}
			$values[$fieldId] = array('value' => $value);
			if (isset($data[$fieldName . '_auto']) && $data[$fieldName . '_auto'] != '_UNKNOWN') {
				$values[$fieldId]['internal_value'] = $data[$fieldName . '_auto'];
			}
			
			$fieldIdValueLink[$fieldId] = $fieldValue;
			break;
		case 'editor':
		case 'textarea':
			$value = $fieldValue ? $fieldValue : '';
			$values[$fieldId] = array('value' => $value);
			$fieldIdValueLink[$fieldId] = $fieldValue;
			break;
		case 'attachment':
			$fileId = false;
			if (!empty($fieldValue) && file_exists(CMS_ROOT . $fieldValue)) {
				$filename = substr(basename($fieldValue), 0, -7);
				$fileId = addFileToDatabase('forms', CMS_ROOT . $fieldValue, $filename);
				$values[$fieldId] = array('value' => $filename, 'internal_value' => $fileId, 'attachment' => true);
				if (setting('zenario_user_forms_admin_email_attachments')) {
					$attachments[] = fileLink($fileId);
				}
			}
			$fieldIdValueLink[$fieldId] = $fileId;
			break;
		
		case 'file_picker':
			
			$labelValues = array();
			$internalValues = array();
			
			if ($fieldValue) {
				$fileIds = str_getcsv((string)$fieldValue, ',', '"', '//');
				foreach ($fileIds as $fileId) {
					
					//TODO check $userId here works!
					
					// If numeric file ID do nothing as this is already saved
					if (is_numeric($fileId) && checkRowExists('custom_dataset_files_link', array('dataset_id' => $dataset['id'], 'field_id' => $userFieldId, 'linking_id' => $userId, 'file_id' => $fileId))) {
						$filename = getRow('files', 'filename', $fileId);
						$labelValues[] = $filename;
						$internalValues[] = $fileId;
					} elseif (file_exists(CMS_ROOT . $fileId)) {
						
						// Validate file size (10MB)
						if (filesize(CMS_ROOT . $fileId) > (1024 * 1024 * 10)) {
							continue;
						}
						
						$filename = substr(basename($fileId), 0, -7);
						
						// Validate file extension
						if (trim($field['extensions'])) {
							$type = explode('.', $filename);
							$type = $type[count($type) - 1];
							$extensions = explode(',', $field['extensions']);
							foreach ($extensions as $index => $extension) {
								$extensions[$index] = trim(str_replace('.', '', $extension));
							}
							if (!in_array($type, $extensions)) {
								continue;
							}
						}
						if (!checkDocumentTypeIsAllowed($filename)) {
							continue;
						}
						
						// Just store file as a response attachment if not saving data, otherwise save as user file
						$usage = 'forms';
						if ($formProperties['save_data']) {
							$usage = 'dataset_file';
						}
						
						$postKey = arrayKey($filePickerValueLink, $fileId);
						
						$fileId = addFileToDatabase($usage, CMS_ROOT . $fileId, $filename);
						
						// Change post value to file ID from cache path now file is uploaded
						if ($postKey) {
							$data[$postKey] = $fileId;
						}
						
						if ($fileId) {
							if (setting('zenario_user_forms_admin_email_attachments')) {
								$attachments[] = fileLink($fileId);
							}
							$labelValues[] = $filename;
							$internalValues[] = $fileId;
						}
					}
				}
			}
			
			$filePickerValues[$fieldId] = array(
				'value' => implode(',', $labelValues), 
				'internal_value' => implode(',', $internalValues), 
				'db_column' => $fieldName, 
				'user_field_id' => $userFieldId, 
				'ord' => $ord
			);
			break;
		
	}
	
	if (isset($values[$fieldId])) {
		$values[$fieldId]['type'] = $type;
		$values[$fieldId]['readonly'] = $field['is_readonly'];
		$values[$fieldId]['db_column'] = $fieldName;
		$values[$fieldId]['ord'] = $ord;
	}
}
// Unset reference
unset($values);


// Save data against user record
if ($formProperties['save_data'] && inc('zenario_extranet')) {
	
	$fields = array();
	foreach ($userSystemFields as $fieldData) {
		if (empty($fieldData['readonly'])) {
			$fields[$fieldData['db_column']] = $fieldData['value'];
		}
	}
	
	// Try to save data if email field is on form
	if (isset($fields['email']) || $userId) { 
		// Duplicate email found
		if (($userId || ($userId = getRow('users', 'id', array('email' => $fields['email'])))) 
			&& ($formProperties['type'] != 'registration')
		) {
			switch ($formProperties['user_duplicate_email_action']) {
				// Don’t change previously populated fields
				case 'merge': 
					$fields['modified_date'] = now();
					static::mergeUserData($fields, $userId, $formProperties['log_user_in']);
					static::saveUserCustomData($userCustomFields, $userId, true);
					static::saveUserMultiCheckboxData($checkBoxValues, $userId, true);
					static::saveUserFilePickerData($filePickerValues, $userId, true);
					break;
				// Change previously populated fields
				case 'overwrite': 
					$fields['modified_date'] = now();
					$userId = static::saveUser($fields, $userId);
					static::saveUserCustomData($userCustomFields, $userId);
					static::saveUserMultiCheckboxData($checkBoxValues, $userId);
					static::saveUserFilePickerData($filePickerValues, $userId);
					break;
				// Don’t update any fields
				case 'ignore': 
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
			
			// Custom logic for creating users from a registration form
			if ($formProperties['type'] == 'registration') {
				$fields['status'] = 'active';
				// Email verification
				if (!empty($registrationOptions['initial_email_address_status'])) {
					if ($registrationOptions['initial_email_address_status'] == 'not_verified' 
						&& isset($registrationOptions['initial_account_status'])
					) {
						if ($registrationOptions['initial_account_status'] == 'pending') {
							$fields['status'] = 'pending';
						} else {
							$fields['status'] = 'contact';
						}
						$fields['email_verified'] = 0;
					} else {
						$fields['email_verified'] = 1;
					}
				}
			}
			
			// Create new user
			$userId = static::saveUser($fields);
			
			// Save new user custom data
			static::saveUserCustomData($userCustomFields, $userId);
			static::saveUserMultiCheckboxData($checkBoxValues, $userId);
			static::saveUserFilePickerData($filePickerValues, $userId);
			//TODO Check if file picker save is needed here!
		}
		if ($userId && ($formProperties['type'] != 'registration')) {
			
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
	if (!$userId 
		|| !$formProperties['save_data']
		|| !checkRowExists(ZENARIO_USER_FORMS_PREFIX. 'user_response', array('user_id' => $userId)) 
		|| (checkRowExists(ZENARIO_USER_FORMS_PREFIX. 'user_response', array('user_id' => $userId)) 
			&& $formProperties['create_another_form_submission_record']
		)
	) {
		// Create new response with values
		$user_response_id = insertRow(
			ZENARIO_USER_FORMS_PREFIX. 'user_response', 
			array('user_id' => $userId, 'form_id' => $userFormId, 'response_datetime' => now())
		);
		
		$values = $userSystemFields + $userCustomFields + $unlinkedFields + $filePickerValues;
		
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
			insertRow(
				ZENARIO_USER_FORMS_PREFIX. 'user_response_data', 
				array(
					'user_response_id' => $user_response_id, 
					'form_field_id' => $fieldId, 
					'internal_value' => $checkedBoxesList['internal_value'], 
					'value' => $checkedBoxesList['value']
				)
			);
		}
	}
}


// Send emails
// Profanity check
$profanityFilterEnabled = setting('zenario_user_forms_set_profanity_filter');
$profanityToleranceLevel = setting('zenario_user_forms_set_profanity_tolerence');

if ($formProperties['profanity_filter_text']) {
	
	$profanityValuesToCheck = $userSystemFields + $userCustomFields + $unlinkedFields;
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
		$values = $userSystemFields + $userCustomFields + $checkBoxValues + $unlinkedFields;
	}
	
	// Send an email to the user
	if ($sendEmailToUser) {
		
		// Get merge fields
		$userEmailMergeFields = static::getTemplateEmailMergeFields($values, $userId);
		
		// Send email
		zenario_email_template_manager::sendEmailsUsingTemplate($data['email'], $formProperties['user_email_template'], $userEmailMergeFields, array());
	}

	// Send an email to administrators
	if ($sendEmailToAdmin) {
		
		// Get merge fields
		$adminEmailMergeFields = static::getTemplateEmailMergeFields($values, $userId, true);
		
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
				$emailValues[$fieldData['ord']] = array($formFields[$fieldId]['name'], $fieldData['value']);
			}
			ksort($emailValues);
			
			$formName = trim($formProperties['name']);
			$formName = empty($formName) ? phrase('[blank name]', array(), 'zenario_user_forms') : $formProperties['name'];
			$body =
				'<p>Dear admin,</p>
				<p>The form "'.$formName.'" was submitted with the following data:</p>';
			
			
			// Get menu path of current page
			$menuNodeString = '';
			if ($formProperties['send_email_to_admin'] && !$formProperties['admin_email_use_template']) {
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
				$body .= '<p>Page submitted from: '. $menuNodeString .'</p>';
			}
			
			foreach ($emailValues as $ordinal => $value) {
				$body .= '<p>'.trim($value[0], " \t\n\r\0\x0B:").': '.$value[1].'</p>';
			}
			
			$url = linkToItem(cms_core::$cID, cms_core::$cType, true, '', false, false, true);
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
	$values = $userSystemFields + $userCustomFields + $checkBoxValues + $unlinkedFields;
	$formattedData = static::getTemplateEmailMergeFields($values, $userId);
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
	$cID = $cType = false;
	getCIDAndCTypeFromTagId($cID, $cType, $formProperties['redirect_location']);
	langEquivalentItem($cID, $cType);
	$redirectURL = linkToItem($cID, $cType);
}
return $userId;
