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

class zenario_salesforce_api_form_integration extends ze\moduleBaseClass {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__user_forms/panels/salesforce_response_log':
				if ($refinerName == 'form_id' && ($formId = $refinerId)) {
					$form = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['name'], $formId);
					$panel['title'] = ze\admin::phrase('Salesforce response log for the form "[[name]]"', $form);
				}
				break;
		}
    }
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		switch ($path){
			case 'zenario_user_form':
				$fields['data/send_signal']['note_below'] .= '<br> The checkbox is automatically checked when "Salesforce integration" is enabled';
				$formId = $box['key']['id'];
				if ($formId) {
					$row = ze\row::get(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX. 'form_crm_link', array('enable_crm_integration', 's_object'), array('form_id' => $formId));
					$values['salesforce_integration/enable_crm_integration'] = $row['enable_crm_integration'];
					$values['salesforce_integration/s_object'] = $row['s_object'];
					
					$result = ze\row::query(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX . 'form_crm_static_inputs', array('name', 'value', 'ord'), array('form_id' => $formId), 'ord');
					while ($row = ze\sql::fetchAssoc($result)) {
						$values['salesforce_integration/name' . $row['ord']] = $row['name'];
						$values['salesforce_integration/value' . $row['ord']] = $row['value'];
					}
				}
				break;
			case 'salesforce_api_form_integration__salesforce_response':
				if ($logId = $box['key']['id']) {
					$log = ze\row::get(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX . 'salesforce_response_log', true, $logId);
					$fields['details/datetime']['snippet']['html'] = ze\date::formatDateTime($log['datetime'], '_MEDIUM');
					
					$box['title'] = ze\admin::phrase('Salesforce response [[id]]', $log);
					
					$fields['details/oauth_status']['snippet']['html'] = $log['oauth_status'];
					if ($log['oauth_response']) {
						$value = '<pre>' . json_encode(json_decode($log['oauth_response'], true), JSON_PRETTY_PRINT) . '</pre>';
					} else {
						$value = ze\admin::phrase('No response.');
					}
					$fields['details/oauth_response']['snippet']['html'] = $value;
					
					$fields['details/salesforce_status']['snippet']['html'] = $log['salesforce_status'];
					if ($log['salesforce_response']) {
						$value = '<pre>' . json_encode(json_decode($log['salesforce_response'], true), JSON_PRETTY_PRINT) . '</pre>';
					} else {
						$value = ze\admin::phrase('No response.');
					}
					$fields['details/salesforce_response']['snippet']['html'] = $value;
				}
				break;
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_user_form':
				$formId = $box['key']['id'];
				if ($formId) {
					ze\row::set(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX. 'form_crm_link', array('enable_crm_integration' => $values['salesforce_integration/enable_crm_integration'], 's_object' => $values['salesforce_integration/s_object']), array('form_id' => $formId));
					
					ze\row::delete(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX . 'form_crm_static_inputs', array('form_id' => $formId));
					for ($i = 1; $i <= 10; $i++) {
						if ($values['salesforce_integration/name' . $i]) {
							ze\row::set(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX . 'form_crm_static_inputs', array('name' => trim($values['salesforce_integration/name' . $i]), 'value' => trim($values['salesforce_integration/value' . $i])), array('form_id' => $formId, 'ord' => $i));
						}
					}
					
					//All CRM forms must send a signal
					if ($values['salesforce_integration/enable_crm_integration']) {
						ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('send_signal' => true), $formId);
					}
				}
				break;
		}
	}
	
	public static function eventUserFormSubmitted($data, $formProperties, $fieldIdValueLink, $responseId = false) {
		$formId = $formProperties['id'];
		$formCrmData = ze\row::get(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX . 'form_crm_link', array('enable_crm_integration', 's_object'), array('form_id' => $formId));
		//check to see if form has salesforce integration enabled
		if (empty($formCrmData['enable_crm_integration']) || empty($formCrmData['s_object'])) {
			return false;
		}
		$data=array();
		$multiValueFields = array();
		
		//Get static crm data to send from form
		$result = ze\row::query(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX . 'form_crm_static_inputs', array('name', 'value'), array('form_id' => $formId), 'ord');
		while ($row = ze\sql::fetchAssoc($result)) {
			//Allow responseId to be sent to CRM via merge field
			if ($responseId) {
				ze\lang::applyMergeFields($row['value'], array('responseId' => $responseId));
			}
			$data[$row['name']] = $row['value'];
			$multiValueFields[$row['name']]['m'] = array('name' => '', 'value' => $row['value']);
			
		}
		
		//Get field crm data to send from submission
		$formFields = zenario_user_forms::getFormFieldsStatic($formId);
		$sql = '
			SELECT uff.id, uff.name, fcf.field_crm_name, fcf.ordinal
			FROM ' . DB_NAME_PREFIX . ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX . 'form_crm_fields fcf
			INNER JOIN ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
				ON fcf.form_field_id = uff.id
				AND uff.user_form_id = ' . (int)$formId;
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$fieldId = $row['id'];
			if (!isset($formFields[$fieldId]) || !isset($fieldIdValueLink[$fieldId])) {
				continue;
			}
			$field = $formFields[$fieldId];
			$fieldCRMName = $row['field_crm_name'];
			$value = zenario_user_forms::getFieldValueFromStored($field, $fieldIdValueLink[$fieldId]);
			
			//Get crm field value
			$fieldCRMValue = null;
			$hasSingleValue = false;
			switch ($field['type']) {
				case 'checkbox':
				case 'group':
					$fieldCRMValue = ze\row::get(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', 'value', array('form_field_id' => $fieldId, 'form_field_value_checkbox_state' => (int)$value));
					break;
				case 'checkboxes':
					$fieldCRMValue = array();
					if ($value) {
						$column = $field['dataset_field_id'] ? 'form_field_value_dataset_id' : 'form_field_value_unlinked_id';
						foreach ($value as $valueId) {
							$fieldCRMValue[] = ze\row::get(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', 'value', array('form_field_id' => $fieldId, $column => $valueId));
						}
					}
					$fieldCRMValue = implode(';', $fieldCRMValue);
					break;
				case 'radios':
				case 'select':
					$column = $field['dataset_field_id'] ? 'form_field_value_dataset_id' : 'form_field_value_unlinked_id';
					$fieldCRMValue = ze\row::get(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', 'value', array('form_field_id' => $fieldId, $column => $value));
					break;
				case 'centralised_radios':
				case 'centralised_select':
					$fieldCRMValue = ze\row::get(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', 'value', array('form_field_id' => $fieldId, 'form_field_value_centralised_key' => $value));
					break;
				case 'text':
				case 'textarea':
				case 'date':
				case 'url':
				case 'calculated':
					$fieldCRMValue = $value;
					$hasSingleValue = true;
					break;
			}
			
			if ($fieldCRMName && $fieldCRMValue !== null) {
				//Send multiple values from a single field if there is a comma in the crm field name
				$names = explode(',', $fieldCRMName);
				$hasSingleName = count($names) == 1;
				$values = array();
				if (!$hasSingleValue) {
					$values = explode(',', (string)$fieldCRMValue);
				}
				foreach ($names as $index => $name) {
					$value = null;
					if ($hasSingleValue || $hasSingleName) {
						$value = $fieldCRMValue;
					} elseif (isset($values[$index])) {
						$value = $values[$index];
					}
					if ($value !== null) {
						$data[trim($name)] = trim($value);
						$multiValueFields[trim($name)][] = array('name' => $row['name'], 'value' => trim($value));
					}
				}
			}
		}
		
		//Group field crm data with the same crm name together
		foreach ($multiValueFields as $fieldCRMName => $fields) {
			if (count($fields) > 1) {
				ksort($fields);
				$fieldValue = '';
				foreach ($fields as $ordinal => $fieldDetails) {
					$fieldValue .= rtrim($fieldDetails['name']," \t\n\r\0\x0B:").': '.$fieldDetails['value'].', ';
				}
				$data[$fieldCRMName] = trim($fieldValue, " \t\n\r\0\x0B,");
			}
		}
		
		self::submitCrmCustomValues($formId, $data, $responseId, $formCrmData['s_object']);
		return true;
	}
	
	
	public static function submitCrmCustomValues($formId, $data, $responseId, $sObject){
		$logId = false;
		
		//Get OAuth2 access token
		$url = ze::setting('zenario_salesforce_api_form_integration__login_uri') . '/services/oauth2/token';
		$params = [
			'grant_type' => 'password',
			'client_id' => ze::setting('zenario_salesforce_api_form_integration__client_id'),
			'client_secret' => ze::setting('zenario_salesforce_api_form_integration__client_secret'),
			'username' => ze::setting('zenario_salesforce_api_form_integration__username'),
			'password' => ze::setting('zenario_salesforce_api_form_integration__password') . ze::setting('zenario_salesforce_api_form_integration__security_token')
		];
		$params = http_build_query($params);
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		
		$resultJSON = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		
		//Log result
		$logId = ze\row::set(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX . 'salesforce_response_log', ['datetime' => date('Y-m-d H:i:s'), 'form_id' => $formId, 'response_id' => $responseId, 'oauth_status' => $status, 'oauth_response' => $resultJSON], $logId);
		//Delete old log entries
		if ($logDays = ze::setting('zenario_salesforce_api_form_integration__log_expiry_time')) {
			$sql = '
				DELETE FROM ' . DB_NAME_PREFIX . ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX . 'salesforce_response_log
				WHERE datetime < DATE_SUB(NOW(), INTERVAL ' . (int)$logDays . ' DAY)';
			ze\sql::update($sql);
		}
		if ($status != 201 && $status != 200) {
			return false;
		}
		
		//Send response to Salesforce 
		$result = json_decode($resultJSON, true);
		$accessToken = $result['access_token'];
		$instanceURL = $result['instance_url'];
		$url = $instanceURL . '/services/data/v40.0/sobjects/' . $sObject . '/' ;
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: OAuth ' . $accessToken, 'Content-type: application/json']);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

		$resultJSON = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		//Log result
		$logId = ze\row::set(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX . 'salesforce_response_log', ['salesforce_status' => $status, 'salesforce_response' => $resultJSON], $logId);
		
		$result = json_decode($resultJSON, true);
		return $result;
	}
	
	public static function deleteFieldCRMData($fieldId) {
		ze\row::delete(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX . 'form_crm_fields', array('form_field_id' => $fieldId));
		ze\row::delete(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX . 'form_crm_field_values', array('form_field_id' => $fieldId));
	}
	
	
	
	
	
	//Signal when a form is deleted
	public static function eventFormDeleted($formId) {
		//Delete crm data stored against form fields
		$fields = ze\row::query(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', array('id'), array('user_form_id' => $formId));
		while ($field = ze\sql::fetchAssoc($fields)) {
			self::deleteFieldCRMData($field['id']);
		}
		//Delete crm static inputs
		ze\row::delete(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX . 'form_crm_static_inputs', array('form_id' => $formId));
	}
	
	//Signal when a form field is deleted
	public static function eventFormFieldDeleted($fieldId) {
		self::deleteFieldCRMData($fieldId);
	}
	
	//Signal when an individual form field value is deleted
	public static function eventFormFieldValueDeleted($valueId) {
		ze\row::delete(ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX . 'form_crm_field_values', array('form_field_value_unlinked_id' => $valueId));
	}
}