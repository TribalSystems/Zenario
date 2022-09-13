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

class zenario_user_forms__admin_boxes__delete_form_field extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$fieldId = $box['key']['id'];
		if ($fieldId) {
			$box['title'] = ze\admin::phrase('Deleting "[[field_name]]"', $box['key']);
			if ($box['key']['field_type'] == 'page_break' || $box['key']['field_type'] == 'section_description') {
				$fields['details/warning_message']['snippet']['html'] = 
					'<p>' . ze\admin::phrase('Are you sure you want to delete this [[field_name]]?', ['field_name' => strtolower($box['key']['field_english_type'])]) . '</p>';
			} elseif ($box['key']['field_type'] == 'restatement') {
				$fields['details/warning_message']['snippet']['html'] = 
					'<p>' . ze\admin::phrase('Are you sure you want to delete this mirror field?') . '</p>';
			} else {
				$responseCount = (int)ze\row::count(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', ['form_field_id' => $fieldId]);
				
				// If no responses delete field normally
				if ($responseCount <= 0) {
					$fields['details/warning_message']['snippet']['html'] = 
						'<p>' . ze\admin::phrase('There are no user responses for this field. Delete this form field?') . '</p>';
				} else {
					$fields['details/delete_field_options']['hidden'] = false;
					
					$responsesTransferFields = json_decode($values['details/dummy_field'], true);
					$responsesTransferFieldsCount = count($responsesTransferFields);
					
					// If no compatible fields disable migration and show message but otherwise delete normally
					if ($responsesTransferFieldsCount <= 0) {
						
						$fields['details/warning_message']['snippet']['html'] = 
							'<p>' . 
							ze\admin::nPhrase(
								'This field has [[count]] response recorded against it, but there are no fields of the same type on the form. If you want to migrate this fields data to another field then create a new field of type "[[type]]".',
								'This field has [[count]] responses recorded against it, but there are no fields of the same type on the form. If you want to migrate this fields data to another field then create a new field of type "[[type]]".',
								$responseCount,
								['count' => $responseCount, 'type' => $box['key']['field_english_type']]
							) . 
							'</p>';
						
						$fields['details/delete_field_options']['values']['delete_field_but_migrate_data']['disabled'] = true;
					} else {
						$fields['details/warning_message']['snippet']['html'] = 
							'<p>' . 
							ze\admin::nPhrase(
								'This field has [[count]] response recorded against it.',
								'This field has [[count]] responses recorded against it.',
								$responseCount,
								['count' => $responseCount]
							) . 
							'</p>';
						
						$fields['details/migration_field']['values'] = $responsesTransferFields;
					}
				}
			}
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$fieldId = $box['key']['id'];
		$fields['details/migration_field']['hidden'] = $values['details/delete_field_options'] != 'delete_field_but_migrate_data';
		$responseCount = (int)ze\row::count(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', ['form_field_id' => $fieldId]);
		
		// If migrating data show warning if selected field has existing responses
		if ($values['details/delete_field_options'] == 'delete_field_but_migrate_data') {
			
			$box['save_button_message'] = ze\admin::phrase('Migrate and delete');
			$fields['details/data_migration_warning_message']['snippet']['html'] = '<p>' . ze\admin::phrase('Response data stored in this form field will be migrated when you save changes to this form.') . '</p>';
			
			if ($values['details/migration_field'] && is_numeric($values['details/migration_field'])) {
			
				$otherFieldResponseCount = (int)ze\row::count(
					ZENARIO_USER_FORMS_PREFIX . 'user_response_data', 
					['form_field_id' => $values['details/migration_field']]
				);
				
				if ($otherFieldResponseCount >= 1) {
					$fields['details/data_migration_warning_message']['snippet']['html'] .= 
						'<p>' . 
						ze\admin::nPhrase(
							'That field already has received data in 1 previous form response. The receiving field on previous form responses will be overwritten with the data (but user/contact records will not be affected).',
							'That field already has received data in [[n]] previous form responses. The receiving field on previous form responses will be overwritten with the data (but user/contact records will not be affected).',
							$otherFieldResponseCount, ['n' => $otherFieldResponseCount]
						) . 
						'</p>';
				}
			}
		} elseif ($values['details/delete_field_options'] == 'delete_field_and_data') {
			$box['save_button_message'] = ze\admin::phrase('Delete');
			$fields['details/data_migration_warning_message']['snippet']['html'] = '<p>' . ze\admin::phrase('Response data stored in this form field will be deleted when you save changes to this form.') . '</p>';
			
		}
		
		$fields['details/data_migration_warning_message']['hidden'] = ($responseCount == 0);
		
	}
	
}
