<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

class zenario_crm_form_integration extends module_base_class {

	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch($path){
			case 'zenario__user_forms/panels/zenario_crm_form_integration__field_names':
				$panel['item_buttons']['properties']['admin_box']['key']['form_id'] = $refinerId;
				$sql = '
					SELECT MIN(uff.id) as id, fcf.field_crm_name, COUNT(uff.id) as field_crm_name_count
					FROM '.DB_NAME_PREFIX.ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields fcf
					INNER JOIN '.DB_NAME_PREFIX.ZENARIO_USER_FORMS_PREFIX.'user_form_fields uff
						ON fcf.form_field_id = uff.id
					WHERE uff.user_form_id = '.(int)$refinerId.'
					GROUP BY fcf.field_crm_name';
				$result = sqlSelect($sql);
				while ($row = sqlFetchAssoc($result)) {
					$panel['items'][$row['id']] = array(
						'field_crm_name' => $row['field_crm_name'], 
						'field_crm_name_count' => $row['field_crm_name_count']);
				}
				
				$formName = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', 'name', $refinerId);
				$panel['title'] = adminPhrase('CRM field names for "[[name]]"', array('name' => $formName));
				break;
			
			case 'zenario__user_forms/panels/zenario_crm_form_integration__fields':
				$sql = '
					SELECT uf.name, fcf.field_crm_name
					FROM '.DB_NAME_PREFIX.ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields fcf
					INNER JOIN '.DB_NAME_PREFIX.ZENARIO_USER_FORMS_PREFIX.'user_form_fields uff
						ON fcf.form_field_id = uff.id
					INNER JOIN '.DB_NAME_PREFIX.ZENARIO_USER_FORMS_PREFIX.'user_forms uf
						ON uff.user_form_id = uf.id
					WHERE fcf.form_field_id = '.(int)$refinerId;
				$result = sqlSelect($sql);
				$row = sqlFetchArray($result);
				$panel['title'] = adminPhrase('Form fields for "[[form_name]]" with CRM field name "[[field_name]]"', array('form_name' => $row[0], 'field_name' => $row[1]));
				break;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__user_forms/panels/zenario_crm_form_integration__fields':
				if (post('reorder')) {
					// Update ordinals
					$ids = explode(',', $ids);
					
					foreach ($ids as $id) {
						if (!empty($_POST['ordinals'][$id])) {
							$sql = "
								UPDATE ".DB_NAME_PREFIX.ZENARIO_CRM_FORM_INTEGRATION_PREFIX."form_crm_fields SET
									ordinal = ". (int) $_POST['ordinals'][$id]. "
								WHERE form_field_id = ". (int) $id;
							sqlUpdate($sql);
						}
					}
				}
				break;
		}
	}


	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		switch ($path){
			case 'zenario_user_admin_box_form':
				$fields['data/send_signal']['note_below'] .= '<br> The checkbox is automatically checked when "CRM integration" is enabled';
				if($box['key']['id']){
					$formId = $box['key']['id'];
					$row = getRow(
										ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_data',
										array(	'crm_url',
												'custom_input_name_1',
												'custom_input_value_1',
												'custom_input_name_2',
												'custom_input_value_2',
												'custom_input_name_3',
												'custom_input_value_3',
												'custom_input_name_4',
												'custom_input_value_4',
												'custom_input_name_5',
												'custom_input_value_5',
												'enable_crm_integration',
												'form_id'
										),
										array('form_id' => $formId)
										);
					$values['crm_integration/crm_url'] = $row['crm_url'];
					$values['crm_integration/name1'] = $row['custom_input_name_1'];
					$values['crm_integration/value1'] = $row['custom_input_value_1'];
					$values['crm_integration/name2'] = $row['custom_input_name_2'];
					$values['crm_integration/value2'] = $row['custom_input_value_2'];
					$values['crm_integration/name3'] = $row['custom_input_name_3'];
					$values['crm_integration/value3'] = $row['custom_input_value_3'];
					$values['crm_integration/name4'] = $row['custom_input_name_4'];
					$values['crm_integration/value4'] = $row['custom_input_value_4'];
					$values['crm_integration/name5'] = $row['custom_input_name_5'];
					$values['crm_integration/value5'] = $row['custom_input_value_5'];
					$values['crm_integration/enable_crm_integration'] = $row['enable_crm_integration'];
				}
				break;
			case 'zenario_crm_form_integration__field_name':
				$formFieldId = $box['key']['id'];
				if ($formFieldId) {
					$name = getRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields', 'field_crm_name', array('form_field_id' => $formFieldId));
					$values['details/field_name'] = $name;
				}
				break;
			
			case 'zenario_crm_form_integration__last_crm_request':
				$formId = $box['key']['id'];
				if ($formId) {
					$form = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('name'), $formId);
					$box['title'] = adminPhrase('Last CRM request for the form "[[name]]"', $form);
					$lastCRMRequest = getRow(
						ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'last_crm_requests',
						array('url', 'datetime', 'request'),
						array('form_id' => $formId)
					);
					if ($lastCRMRequest) {
						$values['details/url'] = $lastCRMRequest['url'];
						$values['details/datetime'] = formatDateTimeNicely($lastCRMRequest['datetime'], '_MEDIUM');
						$values['details/request'] = $lastCRMRequest['request'];
					}
				}
				break;
		}
	}
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	
		switch ($path){
			case 'zenario_user_admin_box_form':
			
				$crmIntegration = $values['crm_integration/enable_crm_integration'];
				if ($crmIntegration){
					$fields['crm_integration/crm_url']['hidden'] = false;
					$fields['crm_integration/name1']['hidden'] = false;
					$fields['crm_integration/value1']['hidden'] = false;
					$fields['crm_integration/name2']['hidden'] = false;
					$fields['crm_integration/value2']['hidden'] = false;
					$fields['crm_integration/name3']['hidden'] = false;
					$fields['crm_integration/value3']['hidden'] = false;
					$fields['crm_integration/name4']['hidden'] = false;
					$fields['crm_integration/value4']['hidden'] = false;
					$fields['crm_integration/name5']['hidden'] = false;
					$fields['crm_integration/value5']['hidden'] = false;
					
					//enable sendsignal
					$values['data/send_signal'] = true;
					$fields['data/send_signal']['read_only'] = true;
					
				}else{
					$fields['crm_integration/crm_url']['hidden'] = true;
					$fields['crm_integration/name1']['hidden'] = true;
					$fields['crm_integration/value1']['hidden'] = true;
					$fields['crm_integration/name2']['hidden'] = true;
					$fields['crm_integration/value2']['hidden'] = true;
					$fields['crm_integration/name3']['hidden'] = true;
					$fields['crm_integration/value3']['hidden'] = true;
					$fields['crm_integration/name4']['hidden'] = true;
					$fields['crm_integration/value4']['hidden'] = true;
					$fields['crm_integration/name5']['hidden'] = true;
					$fields['crm_integration/value5']['hidden'] = true;
					$fields['data/send_signal']['read_only'] = false;
				}
			break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving){
		switch ($path){
			case 'zenario_user_admin_box_form':
				if (!$values['crm_integration/crm_url'] && $values['crm_integration/enable_crm_integration']) {
					$fields['crm_integration/crm_url']['error'] = adminPhrase('Please enter the CRM form action URL');
				}
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes){
		switch ($path){
			case 'zenario_crm_form_integration__field_name':
				// Update all fields using this name on this form
				$oldFieldName = getRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields', 'field_crm_name', $box['key']['id']);
				if ($oldFieldName !== '' && $oldFieldName !== null) {
					$sql = '
						SELECT form_field_id, field_crm_name
						FROM '.DB_NAME_PREFIX.ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields fcf
						INNER JOIN '.DB_NAME_PREFIX.ZENARIO_USER_FORMS_PREFIX.'user_form_fields uff
							ON fcf.form_field_id = uff.id
						WHERE uff.user_form_id = '.(int)$box['key']['form_id'].'
						AND fcf.field_crm_name = \''.$oldFieldName.'\'';
					$result = sqlSelect($sql);
					while ($row = sqlFetchArray($result)) {
						updateRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields', array('field_crm_name' => $values['details/field_name']), array('form_field_id' => $row['form_field_id']));
					}
				}
				break;
		}
	}
	
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_user_admin_box_form':
				$formId = $box['key']['id'];
				if ($formId) {
					$enable_crm_integration = $values['crm_integration/enable_crm_integration'];
					$crm_url = $values['crm_integration/crm_url'];
					$name1 = $values['crm_integration/name1'];
					$value1 = $values['crm_integration/value1'];
					$name2 = $values['crm_integration/name2'];
					$value2 = $values['crm_integration/value2'];
					$name3 = $values['crm_integration/name3'];
					$value3 = $values['crm_integration/value3'];
					$name4 = $values['crm_integration/name4'];
					$value4 = $values['crm_integration/value4'];
					$name5 = $values['crm_integration/name5'];
					$value5 = $values['crm_integration/value5'];
					//update db table
					self::updateFormCrmData($formId,$crm_url,$name1,$value1,$name2,$value2,$name3,$value3,$name4,$value4,$name5,$value5,$enable_crm_integration);
					
					// All CRM forms must send a signal
					if ($enable_crm_integration) {
						updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('send_signal' => true), $formId);
					}
				}
				break;
		}
	}
	
	public static function eventUserFormSubmitted($data, $rawData, $fromProperties, $fieldIdValueLink, $responseId = false) {
		$formId = $fromProperties['user_form_id'];
		$formCrmData = self::getFormCrmData($formId);
		$data=array();
		$multiValueFields = array();
		$url = $formCrmData['crm_url'];
		if ($formCrmData['custom_input_name_1']) {
			$data[$formCrmData['custom_input_name_1']] = $formCrmData['custom_input_value_1'];
			$multiValueFields[$formCrmData['custom_input_name_1']]['m'] = array('name' => '', 'value' => $formCrmData['custom_input_value_1']);
		}
		if ($formCrmData['custom_input_name_2']) {
			$data[$formCrmData['custom_input_name_2']] = $formCrmData['custom_input_value_2'];
			$multiValueFields[$formCrmData['custom_input_name_2']]['m'] = array('name' => '', 'value' => $formCrmData['custom_input_value_2']);
		}
		if ($formCrmData['custom_input_name_3']) {
			$data[$formCrmData['custom_input_name_3']] = $formCrmData['custom_input_value_3'];
			$multiValueFields[$formCrmData['custom_input_name_3']]['m'] = array('name' => '', 'value' => $formCrmData['custom_input_value_3']);
		}
		if ($formCrmData['custom_input_name_4']) {
			$data[$formCrmData['custom_input_name_4']] = $formCrmData['custom_input_value_4'];
			$multiValueFields[$formCrmData['custom_input_name_4']]['m'] = array('name' => '', 'value' => $formCrmData['custom_input_value_4']);
		}
		if ($formCrmData['custom_input_name_5']) {
			$data[$formCrmData['custom_input_name_5']] = $formCrmData['custom_input_value_5'];
			$multiValueFields[$formCrmData['custom_input_name_5']]['m'] = array('name' => '', 'value' => $formCrmData['custom_input_value_5']);
		}
		
		foreach($fieldIdValueLink as $fieldId => $value) {
			$fieldCrmDetails = getRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_fields', array('field_crm_name', 'ordinal'), array('form_field_id' => $fieldId));
			
			$fieldCrmName = $fieldCrmDetails['field_crm_name'];
			
			if ($fieldCrmName){
				//this is a select list, multi-checkboxes or radios
				//look up field value crm value
				if (is_array($value)) {
					$crmValue = '';
					foreach($value as $fieldValueId => $rawValue) {
						//Dataset values
						$isDataset = getRow(
							ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', 
							'value', 
							array(
								'form_field_id' => $fieldId,
								'form_field_value_dataset_id' => $fieldValueId, 
								'form_field_value_unlinked_id' => 0,
								'form_field_value_centralised_key' => null
							)
						);
						if ($isDataset) {
							$crmValue = $isDataset;
						} else {
							//Dataset centralised values
							$isDatasetCentralised = getRow(
								ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', 
								'value', 
								array(
									'form_field_id' => $fieldId,
									'form_field_value_dataset_id' => 0, 
									'form_field_value_unlinked_id' => 0, 
									'form_field_value_centralised_key' => $fieldValueId
								)
							);
							if ($isDatasetCentralised) {
								$crmValue = $isDatasetCentralised;
							} else {
								//Unlinked values
								$isUnlinked= getRow(
									ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', 
									'value', 
									array(
										'form_field_id' => $fieldId,
										'form_field_value_dataset_id' => 0,
										'form_field_value_unlinked_id' => $fieldValueId,
										'form_field_value_centralised_key' => null
									)
								);
								if ($isUnlinked) {
									$crmValue = $isUnlinked;
								}
							}
						}
						
						//this code avoid send empty data
						if(!$crmValue) {
							//if not crm value has been use for the field use raw value
							$crmValue = $rawValue;
						}
					}
					$value = $crmValue;
				// If this field is a checkbox
				} else {
					$sql = '
						SELECT cdf.type, uff.field_type
						FROM '.DB_NAME_PREFIX.ZENARIO_USER_FORMS_PREFIX.'user_form_fields uff
						LEFT JOIN '.DB_NAME_PREFIX.'custom_dataset_fields cdf
							ON uff.user_field_id = cdf.id
						WHERE uff.id = '.$fieldId;
					$result = sqlSelect($sql);
					$field = sqlFetchArray($result);
					$fieldType = $field[0] ? $field[0] : $field[1];
					
					if ($fieldType == 'checkbox') {
						
						$checkboxState = $value ? 1 : 0;
						
						$crmValue = getRow(
							ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', 
							'value', 
							array(
								'form_field_id' => $fieldId,
								'form_field_value_dataset_id' => 0, 
								'form_field_value_unlinked_id' => 0, 
								'form_field_value_checkbox_state' => $checkboxState
							)
						);
						
						if ($crmValue !== false) {
							$value = $crmValue;
						}
					}
					
				}
				// Record form field name and value in case a crm field name is used by multiple fields
				$formFieldName = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', 'name', $fieldId);
				$multiValueFields[$fieldCrmName][$fieldCrmDetails['ordinal']] = array('name' => $formFieldName, 'value' => $value);
				$data[$fieldCrmName] = $value;
			}
		}
		
		// If a crm field name is used by multiple fields update $data
		foreach ($multiValueFields as $fieldCrmName => $fields) {
			if (count($fields) > 1) {
				ksort($fields);
				$fieldValue = '';
				foreach ($fields as $ordinal => $fieldDetails) {
					$fieldValue .= rtrim($fieldDetails['name']," \t\n\r\0\x0B:").': '.$fieldDetails['value'].', ';
				}
				$data[$fieldCrmName] = trim($fieldValue, " \t\n\r\0\x0B,:");
			}
		}
		
		self::submitCrmCustomValues($formId, $url, $data, $responseId);
		return true;
	}
	
	
	public static function submitCrmCustomValues($formId, $url, $data, $responseId){
		
		$request = http_build_query($data);
		$options = array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => $request,
			),
		);
		
		static::recordLastFormCRMRequest($formId, $url, $request);
		
		$context  = stream_context_create($options);
		$result = @file_get_contents($url, false, $context);
		if ($responseId && ($result !== false)) {
			updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_response', array('crm_response' => $result), $responseId);
		}
	}
	
	private static function recordLastFormCRMRequest($formId, $url, $request) {
		setRow(
			ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'last_crm_requests',
			array('url' => $url, 'request' => $request, 'datetime' => date('Y-m-d H:i:s')),
			array('form_id' => $formId)
		);
	}
	
	//FormCrmData
	public static function getFormCrmData($formId){
		$formCrmData = getRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_data',
			array(
				'form_id',
				'crm_url',
				'custom_input_name_1',
				'custom_input_value_1',
				'custom_input_name_2',
				'custom_input_value_2',
				'custom_input_name_3',
				'custom_input_value_3',
				'custom_input_name_4',
				'custom_input_value_4',
				'custom_input_name_5',
				'custom_input_value_5',
				'enable_crm_integration'
			),
			array('form_id' => $formId)
		);
		return $formCrmData;
	}
	
	public static function updateFormCrmData($formId,$crm_url,$name1,$value1,$name2,$value2,$name3,$value3,$name4,$value4,$name5,$value5,$enable_crm_integration){
		$rowId = setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_data',
			array('crm_url' => $crm_url,
					'custom_input_name_1' =>$name1,
					'custom_input_value_1' => $value1,
					'custom_input_name_2' =>$name2,
					'custom_input_value_2' => $value2,
					'custom_input_name_3' =>$name3,
					'custom_input_value_3' => $value3,
					'custom_input_name_4' =>$name4,
					'custom_input_value_4' => $value4,
					'custom_input_name_5' =>$name5,
					'custom_input_value_5' => $value5,
					'enable_crm_integration' =>$enable_crm_integration,
					),
					array('form_id' => $formId)
					);
		return $rowId;
	}
	
	public static function saveFormCrmData($formId,$crm_url,$name1,$value1,$name2,$value2,$name3,$value3,$name4,$value4,$name5,$value5,$enable_crm_integration){
		$rowId = setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_data',
			array('crm_url' => $crm_url,
					'custom_input_name_1' =>$name1,
					'custom_input_value_1' => $value1,
					'custom_input_name_2' =>$name2,
					'custom_input_value_2' => $value2,
					'custom_input_name_3' =>$name3,
					'custom_input_value_3' => $value3,
					'custom_input_name_4' =>$name4,
					'custom_input_value_4' => $value4,
					'custom_input_name_5' =>$name5,
					'custom_input_value_5' => $value5,
					'enable_crm_integration' =>$enable_crm_integration,
					'form_id'=>$formId
					)
					);
		return $rowId;
	}
	
	// Form CRM Fields
	public static function getFormCrmField($formFieldId){
		$formCrmFieldsArray = getRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields',
			array('form_field_id','field_crm_name'),
			array('form_field_id'=>$formFieldId));
		return $formCrmFieldsArray;
	}
	
	public static function updateFormCrmField($formFieldId,$fieldCrmName){
		if ($fieldCrmName) {
			$values = array('field_crm_name' => $fieldCrmName);
			if (!checkRowExists(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields', array('form_field_id' => $formFieldId, 'field_crm_name' => $fieldCrmName))) {
				$sql = '
					SELECT MAX(ordinal) from '.DB_NAME_PREFIX.ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields
					WHERE field_crm_name = \''.sqlEscape($fieldCrmName).'\'';
				$result = sqlSelect($sql);
				$ordinal = sqlFetchRow($result);
				$values['ordinal'] = ($ordinal[0] ? ($ordinal[0]+1) : 1);
			}
			return setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_fields', $values, array('form_field_id'=>$formFieldId));
		}
		deleteRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_fields', array('form_field_id'=>$formFieldId));
		return false;
	}
	
	//Form CRM Field Values
	public static function getFormCrmFieldValues($formFieldId){
		$formCrmFieldValues = getRowsArray(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_field_values',
			array('form_field_value_dataset_id','form_field_value_unlinked_id','form_field_value_centralised_key', 'form_field_value_checkbox_state', 'form_field_id','value'),
			array('form_field_id' => $formFieldId));
		return $formCrmFieldValues;
	}
	
	public static function updateFormCrmFieldValues($formFieldId,$formFieldValueDatasetId,$formFieldValueUnlinkedId,$value){
		$rowId=setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values',
				array('form_field_value_dataset_id'=>$formFieldValueDatasetId,'form_field_value_unlinked_id'=>$formFieldValueUnlinkedId,'value'=>$value),
				array('form_field_id'=>$formFieldId));
		return $rowId;
	}
	
	public static function saveFormCrmFieldValues($formFieldId, $formFieldValueDatasetId, $formFieldValueUnlinkedId, $formFieldValueCentralisedKey, $formFieldValueCheckboxState, $value){
		$values = array('form_field_id'=>$formFieldId,
						'form_field_value_dataset_id'=>$formFieldValueDatasetId,
						'form_field_value_unlinked_id'=>$formFieldValueUnlinkedId,
						'value'=>$value);
		if ($formFieldValueCentralisedKey !== false) {
			$values['form_field_value_centralised_key'] = $formFieldValueCentralisedKey;
		}
		if ($formFieldValueCheckboxState !== false) {
			$values['form_field_value_checkbox_state'] = $formFieldValueCheckboxState;
		}
		
		// Save a linked fields options
		if ($formFieldValueDatasetId){
			$rowId = setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', $values, array('form_field_value_dataset_id'=>$formFieldValueDatasetId, 'form_field_id' => $formFieldId));
		// Save a unlinked fields options
		}elseif ($formFieldValueUnlinkedId){
			$rowId = setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', $values, array('form_field_value_unlinked_id'=>$formFieldValueUnlinkedId));
		// Save a checkbox fields 0 and 1 options
		} elseif ($formFieldValueCheckboxState !== false) {
			$rowId = setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', $values, array('form_field_id'=>$formFieldId, 'form_field_value_checkbox_state' => $formFieldValueCheckboxState));
		// Save a centralised linked fields options
		}else{
			$rowId = setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', $values, array('form_field_id'=>$formFieldId, 'form_field_value_centralised_key'=>$formFieldValueCentralisedKey));
		}
		return $rowId;
	}
	
	public static function getFormIdPassingFormFieldId($formFieldId){
		$formId = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields',
			array('user_form_id'),
			array('id'=>$formFieldId));
		return $formId['user_form_id'];
	}
	
	public static function cleanDbUnlinkedValues($ids){
		if ($ids){
			$stringOfIds = implode(',',$ids);
			$sql="
					SELECT form_field_value_unlinked_id 
					FROM ". DB_NAME_PREFIX.ZENARIO_CRM_FORM_INTEGRATION_PREFIX."form_crm_field_values
					WHERE form_field_value_unlinked_id NOT IN (".sqlEscape($stringOfIds).") 
					AND form_field_value_unlinked_id != 0
					AND form_field_value_dataset_id = 0";
			
			$result = sqlQuery($sql);
			$formFieldTypes = array();
			while($row = sqlFetchAssoc($result)) {
				$idsToDelete[] = $row['form_field_value_unlinked_id'];
			}
			if(isset($idsToDelete) && $idsToDelete){
				foreach ($idsToDelete as $id){
					deleteRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', array('form_field_value_unlinked_id' => $id));
				}
			}
		}
	}
	
	public static function deleteFieldCRMData($fieldId) {
		deleteRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_fields', array('form_field_id' => $fieldId));
		deleteRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_field_values', array('form_field_id' => $fieldId));
	}
	
	// Signal when a form is deleted
	public static function eventFormDeleted($formId) {
		// Delete crm data stored against form fields
		$fields = getRows(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', array('id'), array('user_form_id' => $formId));
		while ($field = sqlFetchAssoc($fields)) {
			self::deleteFieldCRMData($field['id']);
		}
		// Delete last crm request sent
		deleteRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'last_crm_requests', array('form_id' => $formId));
	}
	
	// Signal when a form field is deleted
	public static function eventFormFieldDeleted($fieldId) {
		self::deleteFieldCRMData($fieldId);
	}
	
	// Signal when an individual form field value is deleted
	public static function eventFormFieldValueDeleted($valueId) {
		deleteRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_field_values', array('form_field_value_unlinked_id' => $valueId));
	}
}