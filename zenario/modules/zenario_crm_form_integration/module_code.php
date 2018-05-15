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


class zenario_crm_form_integration extends ze\moduleBaseClass {
	
	public static function eventUserFormSubmitted($data, $form, $fieldIdValueLink, $responseId = false) {
		$formId = $form['id'];
		
		$result = ze\row::query(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', ['id', 'enable', 'crm_id'], ['form_id' => $formId]);
		while ($link = ze\sql::fetchAssoc($result)) {
			if ($link['enable']) {
				
				//Send to a CRM
				if ($link['crm_id'] == 'generic') {
					$data = static::formatCRMData($link['id'], $data, $fieldIdValueLink, $responseId);
					static::sendDataToCRM($link['id'], $data, $responseId);
				}
				
				//Send to Salesforce
				if ($link['crm_id'] == 'salesforce' && ze::setting('zenario_salesforce_api_form_integration__enable')) {
					$data = static::formatCRMData($link['id'], $data, $fieldIdValueLink, $responseId);
					static::sendDataToSalesforce($link['id'], $data, $responseId);
				}
				
				//Send to MailChimp
				if ($link['crm_id'] == 'mailchimp' && ze::setting('zenario_crm_form_integration__enable_mailchimp')) {
					$data = static::formatCRMData($link['id'], $data, $fieldIdValueLink, $responseId);
					static::sendDataToMailChimp($link['id'], $data, $responseId);
				}
				
				//Send to 360Lifecycle
				if ($link['crm_id'] == '360lifecycle' && ze::setting('zenario_crm_form_integration__enable_360lifecycle')) {
					$data = static::formatCRMData($link['id'], $data, $fieldIdValueLink, $responseId);
					static::sendDataTo360LifeCycle($link['id'], $data, $responseId);
				}
			}
		}
	}
	
	
	protected static function formatCRMData($linkId, $data, $fieldIdValueLink, $responseId) {
		$data = [];
		
		//Add static values
		$result = ze\row::query(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'static_crm_values', ['name', 'value'], ['link_id' => $linkId], 'ord');
		while ($row = ze\sql::fetchAssoc($result)) {
			//Allow responseId to be sent to CRM via merge field
			if ($responseId) {
				ze\lang::applyMergeFields($row['value'], ['responseId' => $responseId]);
			}
			$data[$row['name']] = $row['value'];
		}
		
		//Add form field values
		$multiValueFields = [];
		$link = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', ['form_id'], $linkId);
		$formFields = zenario_user_forms::getFormFieldsStatic($link['form_id']);
		$sql = '
			SELECT uff.id, uff.name, cf.name AS crm_name, cf.ord
			FROM ' . DB_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields cf
			INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
				ON cf.form_field_id = uff.id
			WHERE uff.user_form_id = ' . (int)$link['form_id'];
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$fieldId = $row['id'];
			if (!isset($formFields[$fieldId]) || !isset($fieldIdValueLink[$fieldId])) {
				continue;
			}
			
			$field = $formFields[$fieldId];
			$value = zenario_user_forms::getFieldValueFromStored($field, $fieldIdValueLink[$fieldId]);
			
			//Get CRM field details
			//A single field can send multiple values if there is a comma in the name and value to be sent
			
			$fieldCRMNames = explode(',', $row['crm_name']);
			$fieldCRMValues = static::getCRMFieldValues($field, $value);
			
			if ($fieldCRMValues) {
				$ord = $row['ord'];
				foreach ($fieldCRMNames as $index => $name) {
					$name = trim($name);
					$value = null;
					if (count($fieldCRMValues) == 1 || count($fieldCRMNames) == 1) {
						$value = $fieldCRMValues[0];
					} elseif (isset($fieldCRMValues[$index])) {
						$value = $fieldCRMValues[$index];
					}
					if ($value !== null) {
						$value = trim($value);
						$data[$name] = $value;
						$multiValueFields[$name][$ord++] = ['name' => $row['name'], 'value' => $value];
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
		
		return $data;
	}
	
	protected static function getCRMFieldValues($field, $value) {
		$crmValue = false;
		$hasSingleValue = false;
		switch ($field['type']) {
			case 'checkbox':
			case 'group':
				$crmValue = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'crm_field_values', 'value', ['form_field_id' => $field['id'], 'form_field_value_checkbox_state' => (int)$value]);
				break;
			case 'checkboxes':
				$crmValue = [];
				if ($value) {
					$column = $field['dataset_field_id'] ? 'form_field_value_dataset_id' : 'form_field_value_unlinked_id';
					foreach ($value as $valueId) {
						$crmValue[] = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'crm_field_values', 'value', ['form_field_id' => $field['id'], $column => $valueId]);
					}
				}
				$crmValue = implode(';', $crmValue);
				break;
			case 'radios':
			case 'select':
				$column = $field['dataset_field_id'] ? 'form_field_value_dataset_id' : 'form_field_value_unlinked_id';
				$crmValue = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'crm_field_values', 'value', ['form_field_id' => $field['id'], $column => $value]);
				break;
			case 'centralised_radios':
			case 'centralised_select':
				$crmValue = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'crm_field_values', 'value', ['form_field_id' => $field['id'], 'form_field_value_centralised_key' => $value]);
				break;
			case 'text':
			case 'textarea':
			case 'date':
			case 'url':
			case 'calculated':
				$crmValue = $value;
				$hasSingleValue = true;
				break;
		}
		
		if ($hasSingleValue) {
			$crmValues = [$value];
		} elseif (!is_null($crmValue)) {
			$crmValues = explode(',', (string)$crmValue);
		}
		return $crmValues;
	}
	
	
	protected static function sendDataToCRM($linkId, $data, $responseId) {
		$link = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', ['url'], $linkId);
		$request = http_build_query($data);
		$options = [
			'http' => [
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => $request,
			],
		];
		
		static::recordLastFormCRMRequest($linkId, $link['url'], $request);
		
		$context  = stream_context_create($options);
		$result = @file_get_contents($link['url'], false, $context);
		
		if ($responseId && ($result !== false)) {
			ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_response', ['crm_response' => $result], $responseId);
		}
	}
	
	protected static function sendDataToSalesforce($linkId, $data, $responseId) {
		$link = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', ['form_id'], $linkId);
		$crmData = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'salesforce_data', ['s_object'], $link['form_id']);
		
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
		
		//Record request (in case OAuth2 fails)
		static::recordLastFormCRMRequest($linkId, $url, $params);
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		
		$resultJSON = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		
		//Log result
		$logId = ze\row::set(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'salesforce_response_log', ['datetime' => date('Y-m-d H:i:s'), 'form_id' => $link['form_id'], 'response_id' => $responseId, 'oauth_status' => $status, 'oauth_response' => $resultJSON], $logId);
		//Delete old log entries
		if ($logDays = ze::setting('zenario_salesforce_api_form_integration__log_expiry_time')) {
			$sql = '
				DELETE FROM ' . DB_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'salesforce_response_log
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
		$url = $instanceURL . '/services/data/v40.0/sobjects/' . urlencode($crmData['s_object']) . '/' ;
		$dataJSON = json_encode($data);
		
		//Record request
		static::recordLastFormCRMRequest($linkId, $url, $dataJSON);
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: OAuth ' . $accessToken, 'Content-type: application/json']);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $dataJSON);

		$resultJSON = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		//Log result
		$logId = ze\row::set(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'salesforce_response_log', ['salesforce_status' => $status, 'salesforce_response' => $resultJSON], $logId);
		
		if ($responseId) {
			ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_response', ['crm_response' => $resultJSON], $responseId);
		}
		
		$result = json_decode($resultJSON, true);
		return $result;
	}
	
	protected static function sendDataToMailChimp($linkId, $data, $responseId) {
		//Send even if there is no email address so we get an error back to record
		if (!isset($data['EMAIL'])) {
			$data['EMAIL'] = '';
		}
		
		$link = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', ['form_id'], $linkId);
		$crmData = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'mailchimp_data', ['mailchimp_list_id'], $link['form_id']);
		
		$apiKey = ze::setting('zenario_crm_form_integration__mailchimp_api_key');
		$dc = ze::setting('zenario_crm_form_integration__mailchimp_data_center');
		$urlBase = 'https://' . $dc . '.api.mailchimp.com/3.0';
		$hash = md5(strtolower($data['EMAIL']));

		//Request - Subscribe an address (PUT - create or update)
		$url = $urlBase . '/lists/' . urlencode($crmData['mailchimp_list_id']) . '/members/' . urlencode($hash);
		$data = [
			'email_address' => $data['EMAIL'],
			'status' => 'subscribed', 
			'merge_fields' => $data
		];
		$dataJSON = json_encode($data);
		
		//Record request
		static::recordLastFormCRMRequest($linkId, $url, $dataJSON);
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERPWD, 'anystring:' . $apiKey);

		curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($curl, CURLOPT_POSTFIELDS, $dataJSON);

		$resultJSON = curl_exec($curl);
		curl_close($curl);
		
		if ($responseId) {
			ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_response', ['crm_response' => $resultJSON], $responseId);
		}
	}
	
	protected static function sendDataTo360LifeCycle($linkId, $data, $responseId) {
		require_once 'includes/360lifecycle.inc.php';
		
		$link = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', ['form_id'], $linkId);
		$crmData = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . '360lifecycle_data', true, $link['form_id']);
		$crmResponse = false;
		
		try {
			$url = "https://services.360lifecycle.co.uk/LeadService.svc?singlewsdl";
			$service = new SoapClient($url, array(
				'trace' => 1,
				'exception' => 0,
				'classmap' => array(
					'Lead' => 'My360Lead',
					'Address' => 'My360Address',
					'Auth' => 'My360Auth',
					'Client' => 'My360Client',
					'Contact' => 'My360Contact',
					'Opportunity' => 'My360Opportunity',
					'Appointment' => 'My360Appointment',
					'Ping' => 'My360Ping',
					'PingResponse' => 'My360PingResponse',
					'SaveLead' => 'My360SaveLead',
					'SaveLeadResponse' => 'My360SaveLeadResponse'
				),
				'style' => SOAP_DOCUMENT,
				'use' => SOAP_LITERAL
			));
			
			$lead                        = new My360Lead();
			//Set Address
			$lead->Address               = new My360Address();
			$lead->Address->AddressLine1 = $data['Address.AddressLine1'] ?? '';
			$lead->Address->AddressLine2 = $data['Address.AddressLine2'] ?? '';
			$lead->Address->County       = $data['Address.County'] ?? '';
			$lead->Address->MailingName  = $data['Address.MailingName'] ?? '';
			$lead->Address->Postcode     = $data['Address.Postcode'] ?? 'AA11 1XX';
			$lead->Address->Salutation   = $data['Address.Salutation'] ?? '';
			$lead->Address->Town         = $data['Address.Town'] ?? '';
			
			//Set Auth
			$lead->Auth      = new My360Auth();
			$lead->Auth->Key = ze::setting('zenario_crm_form_integration__360lifecycle_lead_handler_api_key');
			
			//Set Clients (up to 2)
			$lead->Clients = [];
			for ($i = 1; $i <= 2; $i++) {
				if (!isset($data['Client' . $i . '.Forename'])) {
					continue;
				}
				$client                   = new My360Client();
				$client->DateOfBirth      = $data['Client' . $i . '.DateOfBirth'] ?? '1900-01-01';
				$client->Dependants       = $data['Client' . $i . '.Dependants'] ?? 0;
				$client->EmploymentStatus = $data['Client' . $i . '.EmploymentStatus'] ?? '';
				$client->Forename         = $data['Client' . $i . '.Forename'] ?? '';
				$client->Gender           = $data['Client' . $i . '.Gender'] ?? '';
				$client->Income           = $data['Client' . $i . '.Income'] ?? 0;
				$client->Occupation       = $data['Client' . $i . '.Occupation'] ?? '';
				$client->Smoker           = $data['Client' . $i . '.Smoker'] ?? false;
				$client->Surname          = $data['Client' . $i . '.Surname'] ?? '';
				$client->Title            = $data['Client' . $i . '.Title'] ?? '';
			
				$client->Contact         = new My360Contact();
				$client->Contact->Email  = $data['Client' . $i . '.Contact.Email'] ?? '';
				$client->Contact->Mobile = $data['Client' . $i . '.Contact.Mobile'] ?? '';
				
				$lead->Clients[] = $client;
			}
			
			//Set Opportunity
			$lead->Opportunity             = new My360Opportunity();
			$lead->Opportunity->Advisor    = $crmData['opportunity_advisor'];
			$lead->Opportunity->LeadSource = $crmData['opportunity_lead_source'];
			$lead->Opportunity->LeadType   = $crmData['opportunity_lead_type'];
			
			$My360SaveLead        = new My360SaveLead();
			$My360SaveLead->value = $lead;
			
			//Record request
			static::recordLastFormCRMRequest($linkId, $url, json_encode($My360SaveLead));
			
			$response = $service->SaveLead($My360SaveLead);
			
			$crmResponse = $response->SaveLeadResult;
			
			//Debug code to ping
		    //$ping = new My360Ping();
		    //$something = $service->Ping($ping);
		    //echo $something->PingResult;
		}
		catch (SoapFault $exception) {
			$crmResponse = $exception->getMessage();
		}
		
		if ($responseId) {
			ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_response', ['crm_response' => $crmResponse], $responseId);
		}
	}
	
	
	protected static function recordLastFormCRMRequest($linkId, $url, $request) {
		ze\row::set(
			ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'last_crm_requests',
			['url' => $url, 'request' => $request, 'datetime' => date('Y-m-d H:i:s')],
			['link_id' => $linkId]
		);
	}
	
	public static function deleteFieldCRMData($fieldId) {
		ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields', ['form_field_id' => $fieldId]);
		ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_field_values', ['form_field_id' => $fieldId]);
	}
	
	//Signal when a form is deleted
	public static function eventFormDeleted($formId) {
		//Delete crm data stored against form fields
		$result = ze\row::query(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', ['id'], ['user_form_id' => $formId]);
		while ($field = ze\sql::fetchAssoc($result)) {
			static::deleteFieldCRMData($field['id']);
		}
		
		$result = ze\row::query(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', ['id'], ['form_id' => $formId]);
		while ($link = ze\sql::fetchAssoc($result)) {
			//Delete last crm request sent
			ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'last_crm_requests', ['link_id' => $link['id']]);
			
			//Delete crm static inputs
			ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'static_crm_values', ['link_id' => $link['id']]);
			
			//Delete crm link
			ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', $link['id']);
		}
		
		//Delete crm specific data
		ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'salesforce_data', $formId);
		ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'mailchimp_data', $formId);
		ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . '360lifecycle_data', $formId);
	}
	
	//Signal when a form field is deleted
	public static function eventFormFieldDeleted($fieldId) {
		static::deleteFieldCRMData($fieldId);
	}
	
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		if ($subClass = $this->runSubClass(__FILE__)) {
			return $subClass->fillAdminBox($path, $settingGroup, $box, $fields, $values);
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($subClass = $this->runSubClass(__FILE__)) {
			return $subClass->formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($subClass = $this->runSubClass(__FILE__)) {
			return $subClass->validateAdminBox($path, $settingGroup, $box, $fields, $values, $changes, $saving);
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($subClass = $this->runSubClass(__FILE__)) {
			return $subClass->saveAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($subClass = $this->runSubClass(__FILE__)) {
			return $subClass->adminBoxSaveCompleted($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($subClass = $this->runSubClass(__FILE__)) {
			return $subClass->fillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($subClass = $this->runSubClass(__FILE__)) {
			return $subClass->handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId);
		}
	}
}