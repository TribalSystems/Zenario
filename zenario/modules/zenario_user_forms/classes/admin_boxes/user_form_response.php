<?php
/*
 * Copyright (c) 2025, Tribal Limited
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

class zenario_user_forms__admin_boxes__user_form_response extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$responseId = $box['key']['id'];
		$box['title'] = ze\admin::phrase('Form response [[id]]', ['id' => $responseId]);
		$responseDetails = ze\row::get(ZENARIO_USER_FORMS_PREFIX. 'user_response', ['user_id', 'response_datetime', 'crm_response', 'form_id', 'allocated_to_admin_id', 'allocated_to_admin_datetime'], $responseId);
		$box['key']['form_id'] = $responseDetails['form_id'];
		
		if ($responseDetails['user_id']) {
			$userIdentifier = ze\row::get('users', 'identifier', ['id' => $responseDetails['user_id']]);
			
			if ($userIdentifier) {
				$usersPanelLink = ze\link::absolute() . 'organizer.php#zenario__users/panels/users//' . (int) $responseDetails['user_id'] . '~-' . $userIdentifier;
				$fields['form_fields/response_user_id']['snippet']['html'] = '<a href="' . $usersPanelLink . '" target="_blank">' . $userIdentifier . '</a>';
			} else {
				$fields['form_fields/response_user_id']['snippet']['html'] = ze\admin::phrase('(user account deleted)');
			}
		} else {
			$fields['form_fields/response_user_id']['snippet']['html'] = ze\admin::phrase('(visitor)');
		}
		
		$values['response_datetime'] = ze\admin::formatDateTime($responseDetails['response_datetime'], 'vis_date_format_med');
		
		$formWorkflowDetails = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['show_checkbox_for_allocating_form_responses', 'form_responses_allocate_checkbox_label'], ['id' => $box['key']['form_id']]);
		
		if (!empty($formWorkflowDetails['show_checkbox_for_allocating_form_responses'])) {
			$fields['form_fields/workflow_control_grouping']['hidden'] = false;
			$label = $formWorkflowDetails['form_responses_allocate_checkbox_label'];
			$labelMergeFields = [];
			
			if (!empty($responseDetails['allocated_to_admin_id']) && !empty($responseDetails['allocated_to_admin_datetime'])) {
				$fields['form_fields/workflow_control_allocated']['label'] = ze\admin::phrase($formWorkflowDetails['form_responses_allocate_checkbox_label']);
				$values['form_fields/workflow_control_allocated'] = true;
				
				$label .= ' [[allocated_relative_date]] by [[admin_name]]';
				
				$timestampAllocated = strtotime($responseDetails['allocated_to_admin_datetime']);
				$labelMergeFields = ['allocated_relative_date' => ze\date::formatRelativeDateTime($timestampAllocated), 'admin_name' => ze\admin::formatName($responseDetails['allocated_to_admin_id'])];
			} elseif (ze\priv::check('_PRIV_VIEW_FORM_RESPONSES')) {
				$box['tabs']['form_fields']['edit_mode']['enabled'] = true;
				$box['save_button_message'] = ze\admin::phrase('Save');
			}
			
			$fields['form_fields/workflow_control_allocated']['label'] = ze\admin::phrase($label, $labelMergeFields);
		}
		
		$crmEnabled = false;
		if (zenario_user_forms::isFormCRMEnabled($responseDetails['form_id'], false)) {
			$values['crm_response'] = $responseDetails['crm_response'];
		} else {
			unset($box['tabs']['form_fields']['fields']['crm_response']);
		}
		
		$html = zenario_user_forms::getFormSummaryHTML($responseId);
		
		$fields['form_fields/data']['snippet']['html'] = $html;
	}
	
	private function getFormDataFromResponse($responseId) {
		$result = ze\row::query(
			ZENARIO_USER_FORMS_PREFIX . 'user_response_data',
			['form_field_id', 'value', 'internal_value'],
			['user_response_id' => $responseId, 'field_row' => 0]
		);
		$responseData = [];
		$data = [];
		while ($row = ze\sql::fetchAssoc($result)) {
			$data[zenario_user_forms::getFieldName($row['form_field_id'])] = $row['internal_value'] !== null ? $row['internal_value'] : $row['value'];
		}
		return $data;
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_VIEW_FORM_RESPONSES');
		
		$formWorkflowDetails = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['show_checkbox_for_allocating_form_responses', 'form_responses_allocate_checkbox_label'], ['id' => $box['key']['form_id']]);
		
		if (!empty($formWorkflowDetails['show_checkbox_for_allocating_form_responses'])) {
			//Check if the form response is already allocated
			$responseDetails = ze\row::get(ZENARIO_USER_FORMS_PREFIX. 'user_response', ['allocated_to_admin_id', 'allocated_to_admin_datetime'], $box['key']['id']);
			if (!empty($responseDetails) && !empty($responseDetails['allocated_to_admin_id']) && !empty($responseDetails['allocated_to_admin_datetime'])) {
				//Do nothing, the form is already allocated.
			} elseif ($values['form_fields/workflow_control_allocated']) {
				$dateToday = ze\date::now();
				$adminId = ze\admin::id();
				
				if ($dateToday && $adminId) {
					ze\row::set(
						ZENARIO_USER_FORMS_PREFIX. 'user_response',
						['allocated_to_admin_id' => (int) $adminId, 'allocated_to_admin_datetime' => ze\escape::sql($dateToday)],
						['id' => $box['key']['id'], 'form_id' => $box['key']['form_id']]
					);
				}
			}
		}
	}
}