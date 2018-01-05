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
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch($path){
			case 'zenario__user_forms/panels/zenario_crm_form_integration__field_names':
				$maxFieldCount = 0;
				$panel['item_buttons']['properties']['admin_box']['key']['form_id'] = $refinerId;
				$sql = '
					SELECT MIN(uff.id) as id, fcf.field_crm_name, COUNT(uff.id) as field_crm_name_count, uff.name
					FROM '.DB_NAME_PREFIX.ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields fcf
					INNER JOIN '.DB_NAME_PREFIX.ZENARIO_USER_FORMS_PREFIX.'user_form_fields uff
						ON fcf.form_field_id = uff.id
					WHERE uff.user_form_id = '.(int)$refinerId.'
					GROUP BY fcf.field_crm_name';
				$result = ze\sql::select($sql);
				while ($row = ze\sql::fetchAssoc($result)) {
					$panel['items'][$row['id']] = array(
						'field_name' => $row['name'],
						'field_crm_name' => $row['field_crm_name'], 
						'field_crm_name_count' => $row['field_crm_name_count']);
					$maxFieldCount = $maxFieldCount < $row['field_crm_name_count'] ? $row['field_crm_name_count'] : $maxFieldCount;
				}
				
				if ($maxFieldCount > 1) {
					unset($panel['columns']['field_name']);
				} else {
					unset($panel['item']['link']);
					unset($panel['columns']['field_crm_name_count']);
				}
				
				$formName = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', 'name', $refinerId);
				$panel['title'] = ze\admin::phrase('CRM field names for "[[name]]" (to make changes edit the form fields)', array('name' => $formName));
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
				$result = ze\sql::select($sql);
				$row = ze\sql::fetchArray($result);
				$panel['title'] = ze\admin::phrase('Form fields for "[[form_name]]" with CRM field name "[[field_name]]"', array('form_name' => $row[0], 'field_name' => $row[1]));
				break;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__user_forms/panels/zenario_crm_form_integration__fields':
				if ($_POST['reorder'] ?? false) {
					//Update ordinals
					$ids = explode(',', $ids);
					
					foreach ($ids as $id) {
						if (!empty($_POST['ordinals'][$id])) {
							$sql = "
								UPDATE ".DB_NAME_PREFIX.ZENARIO_CRM_FORM_INTEGRATION_PREFIX."form_crm_fields SET
									ordinal = ". (int) $_POST['ordinals'][$id]. "
								WHERE form_field_id = ". (int) $id;
							ze\sql::update($sql);
						}
					}
				}
				break;
		}
	}


	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		switch ($path){
			case 'zenario_user_form':
				$fields['data/send_signal']['note_below'] .= '<br> The checkbox is automatically checked when "CRM integration" is enabled';
				$formId = $box['key']['id'];
				if ($formId) {
					$row = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_data', array('crm_url','enable_crm_integration'), array('form_id' => $formId));
					$values['crm_integration/crm_url'] = $row['crm_url'];
					$values['crm_integration/enable_crm_integration'] = $row['enable_crm_integration'];
					
					$result = ze\row::query(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_static_inputs', array('name', 'value', 'ord'), array('form_id' => $formId), 'ord');
					while ($row = ze\sql::fetchAssoc($result)) {
						$values['crm_integration/name' . $row['ord']] = $row['name'];
						$values['crm_integration/value' . $row['ord']] = $row['value'];
					}
				}
				break;
			case 'zenario_crm_form_integration__field_name':
				$formFieldId = $box['key']['id'];
				if ($formFieldId) {
					$name = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields', 'field_crm_name', array('form_field_id' => $formFieldId));
					$values['details/field_name'] = $name;
				}
				break;
			
			case 'zenario_crm_form_integration__last_crm_request':
				$formId = $box['key']['id'];
				if ($formId) {
					$form = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('name'), $formId);
					$box['title'] = ze\admin::phrase('Last CRM request for the form "[[name]]"', $form);
					$lastCRMRequest = ze\row::get(
						ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'last_crm_requests',
						array('url', 'datetime', 'request'),
						array('form_id' => $formId)
					);
					if ($lastCRMRequest) {
						$values['details/url'] = $lastCRMRequest['url'];
						$values['details/datetime'] = ze\date::formatDateTime($lastCRMRequest['datetime'], '_MEDIUM');
						$values['details/request'] = $lastCRMRequest['request'];
					}
				}
				break;
		}
	}
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path){
			case 'zenario_user_form':
				$crmIntegration = $values['crm_integration/enable_crm_integration'];
				if ($crmIntegration) {
					$values['data/send_signal'] = true;
					$fields['data/send_signal']['readonly'] = true;
				} else {
					$fields['data/send_signal']['readonly'] = false;
				}
			break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path){
			case 'zenario_user_form':
				if (!$values['crm_integration/crm_url'] && $values['crm_integration/enable_crm_integration']) {
					$fields['crm_integration/crm_url']['error'] = ze\admin::phrase('Please enter the CRM form action URL');
				}
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path){
			case 'zenario_crm_form_integration__field_name':
				//Update all fields using this name on this form
				$oldFieldName = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields', 'field_crm_name', $box['key']['id']);
				if ($oldFieldName !== '' && $oldFieldName !== null) {
					$sql = '
						SELECT form_field_id, field_crm_name
						FROM '.DB_NAME_PREFIX.ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields fcf
						INNER JOIN '.DB_NAME_PREFIX.ZENARIO_USER_FORMS_PREFIX.'user_form_fields uff
							ON fcf.form_field_id = uff.id
						WHERE uff.user_form_id = '.(int)$box['key']['form_id'].'
						AND fcf.field_crm_name = \''.$oldFieldName.'\'';
					$result = ze\sql::select($sql);
					while ($row = ze\sql::fetchArray($result)) {
						ze\row::update(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields', array('field_crm_name' => $values['details/field_name']), array('form_field_id' => $row['form_field_id']));
					}
				}
				break;
		}
	}
	
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_user_form':
				$formId = $box['key']['id'];
				if ($formId) {
					ze\row::set(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_data', array('crm_url' => $values['crm_integration/crm_url'], 'enable_crm_integration' => $values['crm_integration/enable_crm_integration']), array('form_id' => $formId));
					
					ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_static_inputs', array('form_id' => $formId));
					for ($i = 1; $i <= 10; $i++) {
						if ($values['crm_integration/name' . $i]) {
							ze\row::set(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_static_inputs', array('name' => trim($values['crm_integration/name' . $i]), 'value' => trim($values['crm_integration/value' . $i])), array('form_id' => $formId, 'ord' => $i));
						}
					}
					
					//All CRM forms must send a signal
					if ($values['crm_integration/enable_crm_integration']) {
						ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('send_signal' => true), $formId);
					}
				}
				break;
		}
	}
	
	public static function eventUserFormSubmitted($data, $formProperties, $fieldIdValueLink, $responseId = false) {
		$formId = $formProperties['id'];
		$formCrmData = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_data', array('crm_url', 'enable_crm_integration'), array('form_id' => $formId));
		if (empty($formCrmData['enable_crm_integration'])) {
			return false;
		}
		$data=array();
		$multiValueFields = array();
		$url = $formCrmData['crm_url'];
		
		//Get static crm data to send from form
		$result = ze\row::query(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_static_inputs', array('name', 'value'), array('form_id' => $formId), 'ord');
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
			FROM ' . DB_NAME_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_fields fcf
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
					$fieldCRMValue = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', 'value', array('form_field_id' => $fieldId, 'form_field_value_checkbox_state' => (int)$value));
					break;
				case 'checkboxes':
					$fieldCRMValue = array();
					if ($value) {
						$column = $field['dataset_field_id'] ? 'form_field_value_dataset_id' : 'form_field_value_unlinked_id';
						foreach ($value as $valueId) {
							$fieldCRMValue[] = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', 'value', array('form_field_id' => $fieldId, $column => $valueId));
						}
					}
					$fieldCRMValue = implode(';', $fieldCRMValue);
					break;
				case 'radios':
				case 'select':
					$column = $field['dataset_field_id'] ? 'form_field_value_dataset_id' : 'form_field_value_unlinked_id';
					$fieldCRMValue = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', 'value', array('form_field_id' => $fieldId, $column => $value));
					break;
				case 'centralised_radios':
				case 'centralised_select':
					$fieldCRMValue = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', 'value', array('form_field_id' => $fieldId, 'form_field_value_centralised_key' => $value));
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
			ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_response', array('crm_response' => $result), $responseId);
		}
	}
	
	private static function recordLastFormCRMRequest($formId, $url, $request) {
		ze\row::set(
			ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'last_crm_requests',
			array('url' => $url, 'request' => $request, 'datetime' => date('Y-m-d H:i:s')),
			array('form_id' => $formId)
		);
	}
	
	public static function deleteFieldCRMData($fieldId) {
		ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_fields', array('form_field_id' => $fieldId));
		ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_field_values', array('form_field_id' => $fieldId));
	}
	
	//Signal when a form is deleted
	public static function eventFormDeleted($formId) {
		//Delete crm data stored against form fields
		$fields = ze\row::query(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', array('id'), array('user_form_id' => $formId));
		while ($field = ze\sql::fetchAssoc($fields)) {
			self::deleteFieldCRMData($field['id']);
		}
		//Delete last crm request sent
		ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'last_crm_requests', array('form_id' => $formId));
		//Delete crm static inputs
		ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_static_inputs', array('form_id' => $formId));
	}
	
	//Signal when a form field is deleted
	public static function eventFormFieldDeleted($fieldId) {
		self::deleteFieldCRMData($fieldId);
	}
	
	//Signal when an individual form field value is deleted
	public static function eventFormFieldValueDeleted($valueId) {
		ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_field_values', array('form_field_value_unlinked_id' => $valueId));
	}
}