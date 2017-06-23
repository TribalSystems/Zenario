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

class zenario_user_forms__admin_boxes__user_form_response extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$responseId = $box['key']['id'];
		$box['title'] = adminPhrase('Form response [[id]]', array('id' => $responseId));
		$responseDetails = getRow(ZENARIO_USER_FORMS_PREFIX. 'user_response', array('response_datetime', 'crm_response', 'form_id'), $responseId);
		$formId = $responseDetails['form_id'];
		$values['response_datetime'] = formatDateTimeNicely($responseDetails['response_datetime'], 'vis_date_format_med');
		
		$crmEnabled = false;
		if (zenario_user_forms::isFormCRMEnabled($box['key']['form_id'])) {
			$values['crm_response'] = $responseDetails['crm_response'];
		} else {
			unset($box['tabs']['form_fields']['fields']['crm_response']);
		}
		
		$ord = 100;
		
		//Get user response
		$result = getRows(
			ZENARIO_USER_FORMS_PREFIX . 'user_response_data',
			array('form_field_id', 'value', 'internal_value'),
			array('user_response_id' => $responseId, 'field_row' => 0)
		);
		$responseData = array();
		$data = array();
		while ($row = sqlFetchAssoc($result)) {
			$responseData[$row['form_field_id']] = $row;
			$data[zenario_user_forms::getFieldName($row['form_field_id'])] = $row['internal_value'] !== null ? $row['internal_value'] : $row['value'];
		}
		
		$fields = zenario_user_forms::getFields($formId);
		$form = zenario_user_forms::getForm($formId);
		
		$inRepeatBlock = false;
		$repeatBlockField = false;
		$repeatBlockFields = array();
		
		$sectionFields = array();
		$lastField = end($fields);
		foreach ($fields as $_fieldId => $_field) {
			$type = zenario_user_forms::getFieldType($_field);
			if ($type != 'page_break') {
				//Only show fields that the user would have been able to see.
				if (!zenario_user_forms::isFieldHidden($_field, $fields, $data, false, false) && $type != 'section_description' && $type != 'restatement') {
					$sectionFields[$_fieldId] = $_field;
				}
			} 
			//Also hide any pages and all fields that were on a hidden page.
			if (($type == 'page_break' && !$_field['hide_in_page_switcher'] && !zenario_user_forms::isFieldHidden($_field, $fields, $data, false, false))
				|| ($_fieldId == $lastField['id'])
			) {
				if ($form['show_page_switcher']) {
					if ($type == 'page_break') {
						//$this->addFieldResponse($box, ++$ord, $_fieldId, $type, $_field['name'], array());
					} elseif (!$form['hide_final_page_in_page_switcher']) {;
						//$this->addFieldResponse($box, ++$ord, 'page_end', 'page_break', $form['page_end_name'], array());
					}
				}
				foreach ($sectionFields as $fieldId => $field) {
					$type = zenario_user_forms::getFieldType($field);
					if ($type == 'repeat_start') {
						$inRepeatBlock = true;
						$repeatBlockField = $field;
						continue;
					} elseif ($type == 'repeat_end') {
						if ($repeatBlockFields) {
							//Draw repeat field values in response in order.
							$repeatResponses = array();
							$sql = '
								SELECT form_field_id, field_row, value, internal_value
								FROM ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response_data
								WHERE user_response_id = ' . (int)$responseId . '
								AND form_field_id IN (' . inEscape($repeatBlockFields) . ')
								ORDER BY field_row';
							$result = sqlSelect($sql);
							$maxRowCount = 0;
							while ($row = sqlFetchAssoc($result)) {
								if (empty($repeatResponses[$row['form_field_id']])) {
									$repeatResponses[$row['form_field_id']] = array();
								}
								$repeatResponses[$row['form_field_id']][] = $row;
				
								$count = count($repeatResponses[$row['form_field_id']]);	
								$maxRowCount =  $count > $maxRowCount ? $count : $maxRowCount;
							}
							for ($i = 0; $i < $maxRowCount; $i++) {
								foreach ($repeatBlockFields as $repeatBlockFieldId) {
									if (isset($repeatResponses[$repeatBlockFieldId][$i])) {
										$response = $repeatResponses[$repeatBlockFieldId][$i];
										$fieldId = $repeatBlockFieldId . '_' . $i;
										$type = zenario_user_forms::getFieldType($fields[$repeatBlockFieldId]);
										$label = $fields[$repeatBlockFieldId]['label'];
										if ($response['field_row'] > 1) {
											$label .= ' (' . ($i + 1) . ')';
										}
										$this->addFieldResponse($box, ++$ord, $fieldId, $type, $label, $response);
									}
								}
							}
						}
						$inRepeatBlock = false;
						$repeatBlockField = false;
						$repeatBlockFields = array();
						continue;
					}
					if ($inRepeatBlock) {
						$repeatBlockFields[] = $fieldId;
					} else {
						$response = isset($responseData[$fieldId]) ? $responseData[$fieldId] : array();
						$this->addFieldResponse($box, ++$ord, $fieldId, $type, $field['label'], $response);
					}				
				}
				$sectionFields = array();
			}
		}
	}
	
	public function addFieldResponse(&$box, $ord, $fieldId, $type, $label, $response, $i = 0) {
		$responseValues = array();
		$field = array(
			'label' => $label,
			'ord' => $ord,
			'readonly' => true
		);
		if ($type == 'attachment' || $type == 'file_picker') {
			$responseValue = isset($response['internal_value']) ? $response['internal_value'] : '';
			if ($type == 'file_picker') {
				$responseValues = explode(',', $responseValue);
				$responseValue = $responseValues[$i];
			}
			if ($responseValue && ($file = getRow('files', array('mime_type'), $responseValue))) {
				$link = 'zenario/file.php?adminDownload=1&download=1&id=' . $responseValue;
				$field['post_field_html'] = '<a href="' . $link . '">' . adminPhrase('Download') . '</a>';
			}
			$field['upload'] = array();
			$field['download'] = true;
		} else {
			$responseValue = isset($response['value']) ? $response['value'] : '';
			if ($type == 'textarea') {
				$field['type'] = 'textarea';
				$field['rows'] = 5;
			} else {
				$field['type'] = 'text';
			}
		}
		$field['value'] = $responseValue;
		$fieldName = 'form_field_' . $fieldId;
		if ($i > 0) {
			$fieldName .= '_' . $i;
		}
		$box['tabs']['form_fields']['fields'][$fieldName] = $field;
		
		if ($i < count($responseValues) - 1) {
			$this->addFieldResponse($box, $ord + 0.001, $fieldId, $type, '', $response, ++$i);
		}
	}
	
}