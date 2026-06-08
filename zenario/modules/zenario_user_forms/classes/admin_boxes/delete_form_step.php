<?php
/*
 * Copyright (c) 2026, Tribal Limited
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

class zenario_user_forms__admin_boxes__delete_form_step extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$stepId = $box['key']['id'];
		$formId = $box['key']['form_id'];
		$formStepName = $box['key']['step_name'];
		ze\lang::applyMergeFields($box['title'], ['form_step_name' => $formStepName]);
		
		$box['key']['step_fields'] = json_decode($box['key']['step_fields'], true);
		
		if ($stepId && $formId) {
			//Get a list of fields on this step
			$fieldIds = [];
			$atLeastOneResponseExists = false;
			$responseCount = 0;
			
			$sql = '
				SELECT 
					uff.id
				FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms AS uf
				INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields AS uff
					ON uf.id = uff.user_form_id
				INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'pages p
					ON uff.page_id = p.id
				LEFT JOIN ' . DB_PREFIX . 'custom_dataset_fields AS cdf
					ON uff.user_field_id = cdf.id
				WHERE uf.id = ' . (int) $formId . '
				AND p.id = ' . (int) $stepId . '
				AND uff.field_type NOT IN("repeat_end", "section_description", "section_spacer")
				ORDER BY p.ord, uff.ord';
			$result = ze\sql::select($sql);
			while ($fieldId = ze\sql::fetchValue($result)) {
				$fieldIds[] = $fieldId;
			}
			
			if (count($fieldIds) > 0 || count($box['key']['step_fields']) > 0) {
				foreach ($fieldIds as $fieldId) {
					$responseCount = (int) ze\row::count('user_response_data', ['form_field_id' => $fieldId]);
					
					if ($responseCount) {
						$atLeastOneResponseExists = true;
						break;
					}
				}
			}
			
			if (count($fieldIds) == 0 && count($box['key']['step_fields']) == 0) {
				$message = ze\admin::phrase('There are no fields on this step.');
			} elseif ($atLeastOneResponseExists) {
				unset($box['max_height']);
				
				$message = ze\admin::phrase('This step has user responses against its fields:') . '</p>';
				
				$message .= '<ul>';
				
				foreach ($fieldIds as $fieldId) {
					$fieldName = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', 'name', ['id' => $fieldId]);
					$responseCount = (int) ze\row::count('user_response_data', ['form_field_id' => $fieldId]);
					
					$message .= '<li>' . ze\admin::nPhrase(
						'[[field_name]] has 1 response',
						'[[field_name]] has [[count]] responses',
						$responseCount,
						['field_name' => $fieldName, 'count' => $responseCount]
					) . '</li>';
				}
				
				$message .= '</ul><br /><p>' . ze\admin::phrase('(Each response listed above may or may not contain data.)') . '</p>';
				
				$message .= '<br /><p>' . ze\admin::phrase('Deleting the step will delete its fields and their response data.');
			} else {
				$message = ze\admin::phrase('There are no user responses for any fields on this step.');
			}
			
			$fields['details/warning_message']['snippet']['html'] = '<p>' . $message . '</p><br /><p>' . ze\admin::phrase('Delete this step?') . '</p>';
		} else {
			//Catch the case where an admin is trying to delete a step which has not been saved yet.
			if (count($box['key']['step_fields']) == 0) {
				$message = ze\admin::phrase('There are no fields on this step.');
			} else {
				$message = ze\admin::phrase('There are no user responses for any fields on this step.');
			}
			
			$fields['details/warning_message']['snippet']['html'] = '<p>' . $message . '</p><br /><p>' . ze\admin::phrase('Delete this step?') . '</p>';
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
}