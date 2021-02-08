<?php
/*
 * Copyright (c) 2021, Tribal Limited
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
		$responseDetails = ze\row::get(ZENARIO_USER_FORMS_PREFIX. 'user_response', ['response_datetime', 'crm_response', 'form_id'], $responseId);
		$formId = $responseDetails['form_id'];
		$values['response_datetime'] = ze\admin::formatDateTime($responseDetails['response_datetime'], 'vis_date_format_med');
		
		$crmEnabled = false;
		if (zenario_user_forms::isFormCRMEnabled($box['key']['form_id'], false)) {
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
	
}