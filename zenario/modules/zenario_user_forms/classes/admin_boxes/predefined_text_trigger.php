<?php
/*
 * Copyright (c) 2022, Tribal Limited
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

class zenario_user_forms__admin_boxes__user_forms__predefined_text_trigger extends zenario_user_forms {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		//Load checkbox and checkboxes fields on this form
		$formId = $_GET['refiner__user_form_id'];
		$formFields = $this->getPredefinedTextFormFields($formId, $targets = false, $triggers = true);
		foreach ($formFields as $formField) {
			$pageId = 'p' . $formField['page_id'];
			$fields['details/form_field_id']['values'][$formField['id']] = ['label' => $formField['name'], 'parent' => $pageId];
			$fields['details/form_field_id']['values'][$pageId] = ['label' => $formField['page_name']];
		}
		
		//Load trigger data
		if ($triggerId = $box['key']['id']) {
			$trigger = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'predefined_text_triggers', true, $triggerId);
			$values['details/form_field_id'] = $trigger['form_field_id'];
			$values['details/form_field_value_id'] = $trigger['form_field_value_id'];
			$values['details/text'] = $trigger['text'];
						
			$field = $this->getPredefinedTextFormFields(false, false, false, $trigger['form_field_id']);
			if (strlen($field['name']) > 75) {
				$field['name'] = substr($field['name'], 0, 75) . '...';
			}
			$box['title'] = ze\admin::phrase('Editing the trigger field "[[name]]"', $field);
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Load field values if a "checkboxes" field is chosen
		$formFieldId = $values['details/form_field_id'];
		$fields['details/form_field_value_id']['values'] = [];
		$fields['details/form_field_value_id']['hidden'] = true;
		if ($formFieldId) {
			$formField = $this->getPredefinedTextFormFields(false, false, false, $formFieldId);
			if ($formField['type'] == 'checkboxes') {
				$fields['details/form_field_value_id']['hidden'] = false;
				$fields['details/form_field_value_id']['values'] = zenario_user_forms::getFormFieldLOVStatic($formFieldId);
			}
		}
	}

	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$triggerId = $box['key']['id'];
		
		$details = [
			'form_field_id' => $values['details/form_field_id'],
			'form_field_value_id' => $values['details/form_field_value_id'],
			'text' => $values['details/text']
		];
		
		//Place this trigger last if new
		if (!$triggerId) {
			$targetId = $_GET['refiner__target_form_field_id'];
			
			$ord = ze\row::count(ZENARIO_USER_FORMS_PREFIX . 'predefined_text_triggers', ['target_form_field_id' => $targetId]) + 1;
			
			$details['ord'] = $ord;
			$details['target_form_field_id'] = $targetId;
		}
		
		$box['key']['id'] = ze\row::set(ZENARIO_USER_FORMS_PREFIX . 'predefined_text_triggers', $details, $triggerId);
	}
}