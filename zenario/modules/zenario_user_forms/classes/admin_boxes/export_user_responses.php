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

class zenario_user_forms__admin_boxes__export_user_responses extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		//Fill date ranges with recent dates
		$values['details/date_from'] =  date('Y-m-01');
		$values['details/date_to'] = date('Y-m-d');
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$errors = &$box['tabs']['details']['errors'];
		if ($values['details/responses_to_export'] === 'specific_date_range') {
			// Validate dates
			if (!$values['details/date_from']) {
				$errors[] = adminPhrase('Please choose a "from date" for the range.');
			} elseif (!$values['details/date_to']) {
				$errors[] = adminPhrase('Please choose a "to date" for the range.');
			} elseif (strtotime($values['details/date_to']) > strtotime($values['details/date_to'])) {
				$errors[] = adminPhrase('The "from date" cannot be before the "to date"	');
			}
		} elseif ($values['details/responses_to_export'] === 'from_id') {
		// Validate ID
			if (!$values['details/response_id']) {
				$errors[] = adminPhrase('Please enter a response ID.');
			} elseif (
				!checkRowExists(
					ZENARIO_USER_FORMS_PREFIX . 'user_response', 
					array('id' => $values['details/response_id'])
				)
			) {
				$errors[] = adminPhrase('Unable to find a response with that ID.');
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		exitIfNotCheckPriv('_PRIV_VIEW_FORM_RESPONSES');
		// Export responses
		
		// Create PHPExcel object
		require_once CMS_ROOT. 'zenario/libraries/lgpl/PHPExcel/Classes/PHPExcel.php';
		$objPHPExcel = new PHPExcel();
		$sheet = $objPHPExcel->getActiveSheet();
		
		// Get headers
		$typesNotToExport = array('page_break', 'section_description', 'restatement');
		$formFields = array();
		$sql = '
			SELECT id, name
			FROM ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields
			WHERE user_form_id = ' . (int)$box['key']['form_id'] . '
			AND (field_type NOT IN (' . inEscape($typesNotToExport) . ')
				OR field_type IS NULL
			)
			ORDER BY ord
		';
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			$formFields[$row['id']] = $row['name'];
		}
		
		$lastColumn = PHPExcel_Cell::stringFromColumnIndex(count($formFields) + 1);
		
		// Set columns to text type
		$sheet->getStyle('A:' . $lastColumn)
			->getNumberFormat()
			->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
		
		// Write headers
		$sheet->setCellValue('A1', 'Response ID');
		$sheet->setCellValue('B1', 'Date/Time Responded');
		$sheet->fromArray($formFields, NULL, 'C1');
		
		// Get data
		$responsesData = array();
		$sql = '
			SELECT urd.value, urd.internal_value, urd.form_field_id, uff.ord, ur.id, uff.field_type, cdf.type
			FROM '.DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX .'user_response AS ur
			LEFT JOIN '.DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX .'user_response_data AS urd
				ON ur.id = urd.user_response_id
			LEFT JOIN '.DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX . 'user_form_fields AS uff
				ON urd.form_field_id = uff.id
			LEFT JOIN '.DB_NAME_PREFIX. 'custom_dataset_fields cdf
				ON uff.user_field_id = cdf.id
			WHERE ur.form_id = '. (int)$box['key']['form_id'];
		
		// Add any filters
		switch ($values['details/responses_to_export']) {
			case 'today':
				$date = date('Y-m-d 00:00:00');
				$sql .= '
					AND ur.response_datetime >= "' . sqlEscape($date) . '"';
				break;
			case 'last_2_days':
				$date = date('Y-m-d 00:00:00', strtotime('-1 day'));
				$sql .= '
					AND ur.response_datetime >= "' . sqlEscape($date) . '"';
				break;
			case 'last_week':
				$sql .= '
					AND ur.response_datetime >= (CURDATE() - INTERVAL DAYOFWEEK(CURDATE()) - 1 DAY)';
				break;
			case 'specific_date_range':
				$from = $values['details/date_from'] . ' 00:00:00';
				$to = $values['details/date_to'] . ' 23:59:59';
				$sql .= ' AND ur.response_datetime BETWEEN "' . sqlEscape($from) . '" AND "' . sqlEscape($to) . '"'; 
				break;
			case 'from_id':
				$sql .= '
					AND ur.id >= ' . (int)$values['details/response_id'];
				break;
		}
		$sql .= '
			ORDER BY ur.response_datetime DESC, uff.ord';
		$result = sqlSelect($sql);
		
		while ($row = sqlFetchAssoc($result)) {
			$type = zenario_user_forms::getFieldType($row);
			if ($type == 'attachment' || $type == 'file_picker') {
				$row['value'] = array('value' => $row['value'], 'link' => adminFileLink($row['internal_value']));
			}
			
			if (!isset($responsesData[$row['id']])) {
				$responsesData[$row['id']] = array();
			}
			if (isset($formFields[$row['form_field_id']])) {
				$responsesData[$row['id']][$row['form_field_id']] = $row['value'];
			}
		}
		
		$responseDates = getRowsArray(
			ZENARIO_USER_FORMS_PREFIX. 'user_response', 
			'response_datetime', 
			array('form_id' => $box['key']['form_id']), 'response_datetime'
		);
		
		// Write data
		$rowPointer = 1;
		foreach ($responsesData as $responseId => $responseData) {
			
			$rowPointer++;
			$response = array();
			$response[0] = $responseId;
			$response[1] = formatDateTimeNicely($responseDates[$responseId], '_MEDIUM');
			
			$j = 1;
			foreach ($formFields as $formFieldId => $name) {
				$response[++$j] = '';
				if (isset($responseData[$formFieldId])) {
					$response[$j] = $responseData[$formFieldId];
				}
			}
			
			foreach ($response as $columnPointer => $value) {
				$link = false;
				if (is_array($value)) {
					$link = $value['link'];
					$value = $value['value'];
				}
				$sheet->setCellValueExplicitByColumnAndRow($columnPointer, $rowPointer, $value);
				
				if ($link) {
					$sheet->getCellByColumnAndRow($columnPointer, $rowPointer)->getHyperlink()->setUrl($link);
				}
				
			}
		}
		
		$formName = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', 'name', array('id' => $box['key']['form_id']));
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $formName . ' user responses.xls"');
		$objWriter->save('php://output');
		
		$box['key']['form_id'] = '';
		exit;
	}
	
}