<?php
/*
 * Copyright (c) 2019, Tribal Limited
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


class zenario_crm_form_integration__admin_boxes__user_form extends zenario_crm_form_integration {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//Add links to site-settings in information notice
		$linkToSalesforceTab = ze\link::absolute() . 'zenario/admin/organizer.php?#zenario__administration/panels/site_settings//zenario_user_forms__site_settings_group~.site_settings~tzenario_salesforce_api_form_integration~k' . urlencode('{"id":"zenario_user_forms__site_settings_group"}');
		$linkToMailChimpTab = ze\link::absolute() . 'zenario/admin/organizer.php?#zenario__administration/panels/site_settings//zenario_user_forms__site_settings_group~.site_settings~tmailchimp~k' . urlencode('{"id":"zenario_user_forms__site_settings_group"}');
		$linkTo360LifecycleTab = ze\link::absolute() . 'zenario/admin/organizer.php?#zenario__administration/panels/site_settings//zenario_user_forms__site_settings_group~.site_settings~t360lifecycle~k' . urlencode('{"id":"zenario_user_forms__site_settings_group"}');
		ze\lang::applyMergeFields($box['tabs']['crm_integration']['notices']['crm_info']['message'], ['link_to_salesforce_tab' => $linkToSalesforceTab, 'link_to_mailchimp_tab' => $linkToMailChimpTab]);
		
		//Load generic CRM details
		if ($formId = $box['key']['id']) {
			$crmLink = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_link', ['id', 'url', 'enable'], ['form_id' => $formId, 'crm_id' => 'generic']);
			$values['crm_integration/url'] = $crmLink['url'];
			$values['crm_integration/enable'] = $crmLink['enable'];
			
			$result = ze\row::query(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'static_crm_values', ['name', 'value', 'ord'], ['link_id' => $crmLink['id']], 'ord');
			while ($row = ze\sql::fetchAssoc($result)) {
				$values['crm_integration/name' . $row['ord']] = $row['name'];
				$values['crm_integration/value' . $row['ord']] = $row['value'];
			}
		}
		
		//Load Salesforce CRM details
		$fields['salesforce_integration/client_id']['snippet']['html'] = ze::setting('zenario_salesforce_api_form_integration__client_id');
		$fields['salesforce_integration/client_id']['post_field_html'] = '&nbsp<a href="' . $linkToSalesforceTab . '" target="_blank">' . ze\admin::phrase('Edit') . '</a>';
		if (!ze::setting('zenario_salesforce_api_form_integration__enable')) {
			//Hide salesforce tab if not enabled in site-settings
			$box['tabs']['salesforce_integration']['hidden'] = true;
		} elseif ($formId) {
			$crmLink = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', ['id', 'enable'], ['form_id' => $formId, 'crm_id' => 'salesforce']);
			$values['salesforce_integration/enable'] = $crmLink['enable'];
			
			$crmData = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'salesforce_data', ['s_object'], $formId);
			if ($crmData) {
				$values['salesforce_integration/s_object'] = $crmData['s_object'];
			}
			
			$result = ze\row::query(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'static_crm_values', ['name', 'value', 'ord'], ['link_id' => $crmLink['id']], 'ord');
			while ($row = ze\sql::fetchAssoc($result)) {
				$values['salesforce_integration/name' . $row['ord']] = $row['name'];
				$values['salesforce_integration/value' . $row['ord']] = $row['value'];
			}
		}
		
		//Load MailChimp CRM details
		$fields['mailchimp_integration/api_key']['snippet']['html'] = ze::setting('zenario_crm_form_integration__mailchimp_api_key');
		$fields['mailchimp_integration/api_key']['post_field_html'] = '&nbsp<a href="' . $linkToMailChimpTab . '" target="_blank">' . ze\admin::phrase('Edit') . '</a>';
		if (!ze::setting('zenario_crm_form_integration__enable_mailchimp')) {
			//Hide mailchimp tab if not enabled in site-settings
			$box['tabs']['mailchimp_integration']['hidden'] = true;
		} elseif ($formId) {
			$crmLink = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', ['id', 'enable'], ['form_id' => $formId, 'crm_id' => 'mailchimp']);
			$values['mailchimp_integration/enable'] = $crmLink['enable'];
			
			$crmData = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'mailchimp_data', ['mailchimp_list_id', 'consent_field'], $formId);
			if ($crmData) {
				$values['mailchimp_integration/mailchimp_list_id'] = $crmData['mailchimp_list_id'];
				$values['mailchimp_integration/consent_field'] = $crmData['consent_field'];
				if($values['mailchimp_integration/consent_field'] != 0){
				    $values['mailchimp_integration/send_api_request'] = 'send_on_condition';
				} else {
				
				    $values['mailchimp_integration/send_api_request'] = 'always_send';
				}
			}
			
			$result = ze\row::query(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'static_crm_values', ['name', 'value', 'ord'], ['link_id' => $crmLink['id']], 'ord');
			while ($row = ze\sql::fetchAssoc($result)) {
				$values['mailchimp_integration/name' . $row['ord']] = $row['name'];
				$values['mailchimp_integration/value' . $row['ord']] = $row['value'];
			}
		}
		$consentFields = [];
        $sql = "select f.id, f.name, df.dataset_id 
                    from ".DB_PREFIX.ZENARIO_USER_FORMS_PREFIX."user_form_fields f inner join ".DB_PREFIX."custom_dataset_fields df 
                    where df.type='consent' and f.user_form_id=".(int)$formId." and df.id= f.user_field_id";

        $result = ze\sql::select($sql);
        while($row = ze\sql::fetchAssoc($result)){
            $consentFields[$row['id']] = $row['name'];
        }
		$fields['mailchimp_integration/consent_field']['values'] = $consentFields;
		
		
		
		//Load 360Lifecycle CRM details
		$fields['360lifecycle_integration/api_key']['snippet']['html'] = ze::setting('zenario_crm_form_integration__360lifecycle_lead_handler_api_key');
		$fields['360lifecycle_integration/api_key']['post_field_html'] = '&nbsp<a href="' . $linkTo360LifecycleTab . '" target="_blank">' . ze\admin::phrase('Edit') . '</a>';
		if (!ze::setting('zenario_crm_form_integration__enable_360lifecycle')) {
			$box['tabs']['360lifecycle_integration']['hidden'] = true;
		} elseif ($formId) {
			$crmLink = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', ['id', 'enable'], ['form_id' => $formId, 'crm_id' => '360lifecycle']);
			$values['360lifecycle_integration/enable'] = $crmLink['enable'];
			
			$crmData = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . '360lifecycle_data', true, $formId);
			if ($crmData) {
				$values['360lifecycle_integration/opportunity_advisor'] = $crmData['opportunity_advisor'];
				$values['360lifecycle_integration/opportunity_lead_source'] = $crmData['opportunity_lead_source'];
				$values['360lifecycle_integration/opportunity_lead_type'] = $crmData['opportunity_lead_type'];
			}
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$fields['data/send_signal']['note_below'] = '';
		
		//Forms that send data to a CRM must send a signal
		$forceSendSignal = false;
		if ($values['crm_integration/enable']) {
			$forceSendSignal = true;
			$fields['data/send_signal']['note_below'] .= ze\admin::phrase('The checkbox is automatically checked when "CRM integration" is enabled');
		} elseif ($values['salesforce_integration/enable']) {
			$forceSendSignal = true;
			$fields['data/send_signal']['note_below'] .= ze\admin::phrase('The checkbox is automatically checked when "Salesforce integration" is enabled');
		} elseif ($values['mailchimp_integration/enable']) {
			$forceSendSignal = true;
			$fields['data/send_signal']['note_below'] .= ze\admin::phrase('The checkbox is automatically checked when "MailChimp integration" is enabled');
		} elseif ($values['360lifecycle_integration/enable']) {
			$forceSendSignal = true;
			$fields['data/send_signal']['note_below'] .= ze\admin::phrase('The checkbox is automatically checked when "360Lifecycle integration" is enabled');
		}
		
		if ($forceSendSignal) {
			$values['data/send_signal'] = true;
			$fields['data/send_signal']['readonly'] = true;
		} else {
			$fields['data/send_signal']['readonly'] = false;
		}
		
		
		//MailChimp: Show a warning notice if there is no form field with the special crm name "EMAIL"
		if ($formId = $box['key']['id']) {
			$missingEmailCRMField = false;
			$sql = '
				SELECT cf.form_field_id
				FROM ' . DB_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields cf
				INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
					ON cf.form_field_id = uff.id
					AND uff.user_form_id = ' . (int)$formId . '
				WHERE cf.name = "EMAIL"';
			$result = ze\sql::select($sql);
			if (ze\sql::numRows($result) < 1) {
				$missingEmailCRMField = true;
			}
			$box['tabs']['mailchimp_integration']['notices']['missing_email']['show'] = $missingEmailCRMField && $values['mailchimp_integration/enable'];
		}
		
		if($values['mailchimp_integration/send_api_request'] == 'send_on_condition'){//radio button for consent field condition
		    $fields['mailchimp_integration/consent_field']['hidden'] = false;
		    
		} else {
		    $fields['mailchimp_integration/consent_field']['hidden'] = true;
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($formId = $box['key']['id']) {
			$crmPreviouslyEnabled = ze\row::exists(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', ['form_id' => $formId, 'enable' => true]);
			
			//Save generic CRM details
			$linkId = ze\row::set(
				ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_link', 
				['url' => $values['crm_integration/url'], 'enable' => $values['crm_integration/enable']], 
				['form_id' => $formId, 'crm_id' => 'generic']
			);
			
			ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'static_crm_values', ['link_id' => $linkId]);
			for ($i = 1; $i <= 10; $i++) {
				if ($values['crm_integration/name' . $i]) {
					ze\row::insert(
						ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'static_crm_values', 
						[
							'name' => trim($values['crm_integration/name' . $i]), 
							'value' => trim($values['crm_integration/value' . $i]), 
							'ord' => $i, 
							'link_id' => $linkId
						]
					);
				}
			}
			
			//Save Salesforce CRM details
			if (ze::setting('zenario_salesforce_api_form_integration__enable')) {
				$linkId = ze\row::set(
					ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_link', 
					['enable' => $values['salesforce_integration/enable']], 
					['form_id' => $formId, 'crm_id' => 'salesforce']
				);
				
				ze\row::set(
					ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'salesforce_data', 
					['s_object' => $values['salesforce_integration/s_object']], 
					['form_id' => $formId]
				);
			
				ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'static_crm_values', ['link_id' => $linkId]);
				for ($i = 1; $i <= 10; $i++) {
					if ($values['salesforce_integration/name' . $i]) {
						ze\row::insert(
							ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'static_crm_values', 
							[
								'name' => trim($values['salesforce_integration/name' . $i]), 
								'value' => trim($values['salesforce_integration/value' . $i]), 
								'ord' => $i, 'link_id' => $linkId
							]
						);
					}
				}
			}
			
			//Save MailChimp CRM details
			if (ze::setting('zenario_crm_form_integration__enable_mailchimp')) {
				$linkId = ze\row::set(
					ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_link', 
					['enable' => $values['mailchimp_integration/enable']], 
					['form_id' => $formId, 'crm_id' => 'mailchimp']
				);
				
				  
				 
				if($values['mailchimp_integration/send_api_request'] == "send_on_condition" && $values['mailchimp_integration/consent_field']){
				   
				     ze\row::set(
					    ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'mailchimp_data', 
					    ['mailchimp_list_id' => $values['mailchimp_integration/mailchimp_list_id'], 'consent_field' => $values['mailchimp_integration/consent_field']], 
					    ['form_id' => $formId]
				    );
				    
				} else{
				    ze\row::set(
					    ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'mailchimp_data', 
					    ['mailchimp_list_id' => $values['mailchimp_integration/mailchimp_list_id'], 'consent_field' => 0], 
					    ['form_id' => $formId]
				    );
				}
				ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'static_crm_values', ['link_id' => $linkId]);
				for ($i = 1; $i <= 10; $i++) {
					if ($values['mailchimp_integration/name' . $i]) {
						ze\row::insert(
							ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'static_crm_values', 
							[
								'name' => trim($values['mailchimp_integration/name' . $i]), 
								'value' => trim($values['mailchimp_integration/value' . $i]), 
								'ord' => $i, 
								'link_id' => $linkId
							]
						);
					}
				}
			}
			
			//Save 360LifeCycle CRM details
			if (ze::setting('zenario_crm_form_integration__enable_360lifecycle')) {
				$linkId = ze\row::set(
					ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_link', 
					['enable' => $values['360lifecycle_integration/enable']], 
					['form_id' => $formId, 'crm_id' => '360lifecycle']
				);
				ze\row::set(
					ZENARIO_CRM_FORM_INTEGRATION_PREFIX . '360lifecycle_data', 
					[
						'opportunity_advisor' => $values['360lifecycle_integration/opportunity_advisor'],
						'opportunity_lead_source' => $values['360lifecycle_integration/opportunity_lead_source'],
						'opportunity_lead_type' => $values['360lifecycle_integration/opportunity_lead_type']
					], 
					['form_id' => $formId]
				);
			}
			
			$crmEnabled = $values['crm_integration/enable'] || $values['salesforce_integration/enable'] || $values['mailchimp_integration/enable'];
			if ($crmEnabled) {
				//All CRM forms must send a signal
				ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['send_signal' => true], $formId);
				
				//If CRM has been enabled for a form, automatically set a few fields up if they are already on the form.
				//(Only if it wasn't enabled before so it doesn't get annoying..)
				if (!$crmPreviouslyEnabled) {
					$crmNameMap = ['email' => 'EMAIL', 'first_name' => 'FNAME', 'last_name' => 'LNAME'];
					$fields = zenario_user_forms::getFormFieldsStatic($formId);
					foreach ($fields as $fieldId => $field) {
						if ($field['dataset_field_id'] 
							&& isset($crmNameMap[$field['db_column']])
							&& !ze\row::exists(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields', $fieldId)
						) {
							ze\row::insert(
								ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields', 
								['form_field_id' => $fieldId, 'name' => $crmNameMap[$field['db_column']]]
							);
						}
					}
				}
			}
			
		}
	}
}