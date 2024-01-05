<?php
/*
 * Copyright (c) 2024, Tribal Limited
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

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class zenario_user_forms__admin_boxes__export_user_responses extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (!$box['key']['form_id']) {
			exit;
		}
		//Fill date ranges with recent dates
		$values['details/date_from'] =  date('Y-m-01');
		$values['details/date_to'] = date('Y-m-d');
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$errors = &$box['tabs']['details']['errors'];
		if ($values['details/responses_to_export'] === 'specific_date_range') {
			//Validate dates
			if (!$values['details/date_from']) {
				$errors[] = ze\admin::phrase('Please choose a "from date" for the range.');
			} elseif (!$values['details/date_to']) {
				$errors[] = ze\admin::phrase('Please choose a "to date" for the range.');
			} elseif (strtotime($values['details/date_to']) > strtotime($values['details/date_to'])) {
				$errors[] = ze\admin::phrase('The "from date" cannot be before the "to date"	');
			}
		} elseif ($values['details/responses_to_export'] === 'from_id') {
			//Validate response Id
			if (!$values['details/response_id']) {
				$errors[] = ze\admin::phrase('Please enter a response ID.');
			} elseif (
				!ze\row::exists(
					ZENARIO_USER_FORMS_PREFIX . 'user_response', 
					['id' => $values['details/response_id']]
				)
			) {
				$errors[] = ze\admin::phrase('Unable to find a response with that ID.');
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_VIEW_FORM_RESPONSES');
		$formId = $box['key']['form_id'];
		$userId = $box['key']['user_id'];
		//Export responses
		
		$objPHPSpreadsheet = new Spreadsheet();
		$activeWorksheet = $objPHPSpreadsheet->getActiveSheet();
		
		//Get headers
		$typesNotToExport = ['section_description', 'restatement', 'repeat_start', 'repeat_end'];
		$fields = zenario_user_forms::getFormFieldsStatic($formId);
		$exportHeaders = [];
		foreach ($fields as $fieldId => $field) {
			if (!in_array($field['type'], $typesNotToExport)) {
				$exportHeaders[$fieldId] = $field['name'];
			}
		}
		
		$lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($exportHeaders) + 1);
		
		//Set columns to text type
		$activeWorksheet->getStyle('A:' . $lastColumn)
			->getNumberFormat()
			->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
		
		//Write headers
		$activeWorksheet->setCellValue('A1', 'Response ID');
		$activeWorksheet->setCellValue('B1', 'Date/Time Responded');
		$activeWorksheet->fromArray($exportHeaders, NULL, 'C1');
		
		//Get data
		$responsesData = [];
		$sql = '
			SELECT urd.value, urd.form_field_id, ur.id
			FROM '.DB_PREFIX. ZENARIO_USER_FORMS_PREFIX .'user_response AS ur
			LEFT JOIN '.DB_PREFIX. ZENARIO_USER_FORMS_PREFIX .'user_response_data AS urd
				ON ur.id = urd.user_response_id
			LEFT JOIN '.DB_PREFIX. ZENARIO_USER_FORMS_PREFIX . 'user_form_fields AS uff
				ON urd.form_field_id = uff.id
			WHERE ur.form_id = ' . (int)$formId;
		
		//Add any filters
		switch ($values['details/responses_to_export']) {
			case 'today':
				$date = date('Y-m-d 00:00:00');
				$sql .= '
					AND ur.response_datetime >= "' . ze\escape::sql($date) . '"';
				break;
			case 'last_2_days':
				$date = date('Y-m-d 00:00:00', strtotime('-1 day'));
				$sql .= '
					AND ur.response_datetime >= "' . ze\escape::sql($date) . '"';
				break;
			case 'last_week':
				$sql .= '
					AND ur.response_datetime >= (CURDATE() - INTERVAL DAYOFWEEK(CURDATE()) - 1 DAY)';
				break;
			case 'specific_date_range':
				$from = $values['details/date_from'] . ' 00:00:00';
				$to = $values['details/date_to'] . ' 23:59:59';
				$sql .= ' AND ur.response_datetime BETWEEN "' . ze\escape::sql($from) . '" AND "' . ze\escape::sql($to) . '"'; 
				break;
			case 'from_id':
				$sql .= '
					AND ur.id >= ' . (int)$values['details/response_id'];
				break;
		}
		$sql .= '
			ORDER BY ur.response_datetime DESC, uff.ord';
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			if (!isset($responsesData[$row['id']])) {
				$responsesData[$row['id']] = [];
			}
			if (isset($exportHeaders[$row['form_field_id']])) {
				$field = $fields[$row['form_field_id']];
				$displayValue = zenario_user_forms::getFieldDisplayValueFromStored($field, $row['value']);
				$responsesData[$row['id']][$row['form_field_id']] = [];
				if ($field['type'] == 'attachment' && $row['value']) {
					$responsesData[$row['id']][$row['form_field_id']]['link'] = ze\contentAdm::adminFileLink($row['value']);
				}
				$responsesData[$row['id']][$row['form_field_id']]['value'] = $displayValue;
			}
		}
		
		$responseDates = ze\row::getAssocs(
			ZENARIO_USER_FORMS_PREFIX. 'user_response', 
			'response_datetime', 
			['form_id' => $formId], 'response_datetime'
		);
		
		//Write data
		$rowPointer = 1;
		foreach ($responsesData as $responseId => $responseData) {
			$rowPointer++;
			$response = [];
			$response[0] = $responseId;
			$response[1] = ze\admin::formatDateTime($responseDates[$responseId], '_MEDIUM');
			
			$j = 1;
			foreach ($exportHeaders as $formFieldId => $name) {
				$response[++$j] = 'N/A';
				if (isset($responseData[$formFieldId])) {
					$response[$j] = $responseData[$formFieldId];
				}
			}
			
			foreach ($response as $columnPointer => $value) {
				if (!is_array($value)) {
					$value = ['value' => $value];
				}
				$activeWorksheet->setCellValueExplicit([$columnPointer + 1, $rowPointer], $value['value'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				if (!empty($value['link'])) {
					$activeWorksheet->getCell([$columnPointer + 1, $rowPointer])->getHyperlink()->setUrl($value['link']);
				}
				
			}
		}
		
		
		$formName = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', 'name', ['id' => $formId]);
		
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $formName . ' user responses.xls"');
		
		$writer = new Xls($objPHPSpreadsheet);
		$writer->save('php://output');
		
		$box['key']['form_id'] = '';
		exit;
	}
	
}