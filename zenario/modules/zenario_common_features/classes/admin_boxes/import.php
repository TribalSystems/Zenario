<?php
/*
 * Copyright (c) 2023, Tribal Limited
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


class zenario_common_features__admin_boxes__import extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		//Load activation email templates for new users
		$fields['actions/email_to_send']['value'] = ze::setting('default_activation_email_template');

		$siteSettingsLink = "<a href='organizer.php#zenario__administration/panels/site_settings//users~.site_settings~tactivation_email_template~k{\"id\"%3A\"users\"}' target='_blank'>site settings</a>";
		$fields['actions/email_to_send']['note_below'] = ze\admin::phrase(
			'The default activation email template can be changed in the [[site_settings_link]].',
			['site_settings_link' => $siteSettingsLink]
		);
		
		//Load list of dataset fields
		$datasetId = $box['key']['dataset'];
		$dataset = ze\dataset::details($datasetId);
		$datasetFields = ze\datasetAdm::listCustomFields($datasetId, $flat = false, $filter = false, $customOnly = false, $useOptGroups = true);

		//Roles should not be listed as a potential option.
		if (isset($datasetFields['tab__zenario_organization_manager__roles'])) {
			unset($datasetFields['tab__zenario_organization_manager__roles']);
		}

		//Special fields for users dataset
		if ($dataset['system_table'] == 'users') {
			$datasetFields['name_split_on_first_space'] = [
				'ord' => 0.1, 
				'label' => ze\admin::phrase('Name -> First Name, Last Name, split on first space')
			];
			$datasetFields['name_split_on_last_space'] = [
				'ord' => 0.2, 
				'label' => ze\admin::phrase('Name -> First Name, Last Name, split on last space')
			];
		}

		foreach ($datasetFields as $datasetFieldId => $datasetField) {
			if (!empty($datasetField['field_name']) && ze::in($datasetField['field_name'], "status", "screen_name_confirmed", "created_date", "modified_date", "last_login", "last_profile_update_in_frontend", "suspended_date")) {
				$datasetFields[$datasetFieldId]['disabled'] = true;
			}
		}
		$box['lovs']['dataset_fields'] = $datasetFields;
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//Validate stages and navigation
		$errors = &$box['tabs'][$box['tab']]['errors'];
		switch ($box['tab']) {
			case 'file':
				if (!empty($fields['file/next']['pressed'])) {
					//Check that CSV file is UTF-8 encoded
					if ($values['file/file']) {
						$path = ze\file::getPathOfUploadInCacheDir($values['file/file']);
						if (pathinfo($path, PATHINFO_EXTENSION) == 'csv') {
							$string = file_get_contents($path);
							$isUTF8 = mb_detect_encoding($string, 'UTF-8', true);
							if ($isUTF8 === false) {
								$errors[] = ze\admin::phrase('CSV files must be UTF-8 encoded to be imported.');
							}
						}
						if (empty($errors)) {
							$box['tab'] = 'headers';
						}
					}
				}
				break;
			case 'headers':
				if (!empty($fields['headers/next']['pressed'])) {
					
					if (($updateMode = ($values['file/type'] == 'update_data'))) {
						//Check key fields is selected if updating
						if ($values['headers/update_key_field'] === '') {
							$errors[] = ze\admin::phrase('Please select a field name to uniquely identify each line.');
						} elseif (empty($values['headers/file_column_match_' . $values['headers/update_key_field']])) {
							$errors[] = ze\admin::phrase('You must match the unique field to a dataset field.');
						}
					} else {
						//Check required dataset fields are imported in insert mode
						$requiredDatasetFields = [];
						$datasetFields = ze\dataset::fieldsDetails($box['key']['dataset']);
						foreach ($datasetFields as $datasetField) {
							if (!empty($datasetField['required'])) {
								$requiredDatasetFields[$datasetField['id']] = $datasetField['db_column'];
							}
						}
						foreach ($box['tabs']['headers']['fields'] as $name => $field) {
							if (strpos($name, 'file_column_match') === 0) {
								unset($requiredDatasetFields[$values['headers/' . $name]]);
							}
						}
						if (!empty($requiredDatasetFields)) {
							$missingFields = implode(', ', $requiredDatasetFields);
							$errors[] = ze\admin::phrase('The following required fields are missing: [[missingFields]].', ['missingFields' => $missingFields]);
						}
						
						
						//Check email field is being imported if updating data based on email in insert mode (users dataset only)
						$dataset = ze\dataset::details($box['key']['dataset']);
						if ($dataset['system_table'] == 'users' && ($values['headers/insert_options'] != 'no_update')) {
							$importingEmailField = false;
							$emailDatasetField = ze\dataset::fieldDetails('email', $dataset['id']);
							foreach ($box['tabs']['headers']['fields'] as $name => $field) {
								if (strpos($name, 'file_column_match') === 0 && $values['headers/' . $name] == $emailDatasetField['id']) {
									$importingEmailField = true;
									break;
								}
							}
							if (!$importingEmailField) {
								$errors[] = ze\admin::phrase('You must include the email column to update matching fields on email.');
							}
						}
						
					}
					//Check at least one header has been matched to a dataset field
					$fieldMatched = false;
					foreach ($box['tabs']['headers']['fields'] as $name => $field) {
						if (strpos($name, 'file_column_match') === 0 && $values['headers/' . $name]) {
							$fieldMatched = true;
							break;
						}
					}
					if (!$fieldMatched) {
						$errors[] = ze\admin::phrase('You need to match at least one field to continue.');
					}
					
					if (empty($errors)) {
						$box['tab'] = 'preview';
					}
				} elseif (!empty($fields['headers/previous']['pressed'])) {
					$box['tab'] = 'file';
				}
				break;
			case 'preview':
				if (!empty($fields['preview/next']['pressed'])) {
					$box['tab'] = 'actions';
				} elseif (!empty($fields['preview/previous']['pressed'])) {
					$box['tab'] = 'headers';
				}
				break;
			case 'actions':
				if ($saving) {
					$datasetId = $box['key']['dataset'];
					$dataset = ze\dataset::details($datasetId);
					if ($dataset['system_table'] == 'users') {
						//If importing users then force admin to choose a status
						$statusDatasetField = ze\dataset::fieldDetails('status', $datasetId);
						if (isset($values['actions/dataset_field_value_' . $statusDatasetField['id']])
							&& !$values['actions/dataset_field_value_' . $statusDatasetField['id']]
							&& $values['file/type'] == 'insert_data'
							&& $box['key']['new_records']
						) {
							$errors[] = ze\admin::phrase('You must select a status when creating new user records.');
						}
					}
				}
				if (!empty($fields['actions/next']['pressed'])) {
					//Finish
				} elseif (!empty($fields['actions/previous']['pressed'])) {
					$box['tab'] = 'preview';
				}
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Set title
		$box['title'] = ze\admin::phrase('Dataset Import Wizard - Step [[n]] of 4', ['n' => $box['tabs'][$box['tab']]['_step']]);
		//Hide save button until final step
		$box['css_class'] = ($box['tab'] == 'actions') ? '' : 'zenario_fab_default_style zenario_fab_hide_save_button';
		
		if ($box['tab'] == 'file') {
			
			if ($box['old_values']['file'] != $values['file/file']) {
				$box['old_values']['file'] = $values['file/file'];
				//Actions when import file is changed
				$box['key']['reset_key_line'] = 1;
				$box['key']['create_header_fields'] = 1;
				$box['key']['guess_key_line'] = 1;
				$box['key']['update_preview'] = 1;
			}
			
		} elseif ($box['tab'] == 'headers') {
			if ($box['old_values']['key_line'] != $values['headers/key_line']) {
				$box['old_values']['key_line'] = $values['headers/key_line'];
				//Actions when key line is changed
				$box['key']['create_header_fields'] = 1;
				$box['key']['update_preview'] = 1;
			}
			
			//Show/hide insert/update options
			$datasetId = $box['key']['dataset'];
			$dataset = ze\dataset::details($datasetId);
			$fields['headers/insert_desc']['hidden'] = 
			$fields['headers/insert_options']['hidden'] = 
				($values['file/type'] != 'insert_data') || ($dataset['system_table'] != 'users');
			
			$fields['headers/update_desc']['hidden'] = 
			$fields['headers/update_key_field']['hidden'] = 
				$values['file/type'] != 'update_data';
			
			if ($box['old_values']['update_key_field'] != $values['headers/update_key_field']) {
				$box['old_values']['update_key_field'] = $values['headers/update_key_field'];
				//Actions when key line is changed
				$box['key']['update_preview'] = 1;
			}
			
			//Show ID column as an option when updating data
			if ($values['file/type'] == 'update_data') {
				$box['lovs']['dataset_fields']['id'] = ['ord' => 0, 'label' => ze\admin::phrase('ID Column')];
			} else {
				unset($box['lovs']['dataset_fields']['id']);
			}
			
			if ($box['key']['reset_key_line']) {
				$box['key']['reset_key_line'] = 0;
				$values['headers/key_line'] = 1;
			}
			
			//Loop through headers in uploaded file and create inputs for them
			if ($box['key']['create_header_fields']) {
				$box['key']['create_header_fields'] = 0;
				
				//Delete previously generated fields
				foreach ($box['tabs']['headers']['fields'] as $name => $field) {
					foreach ($box['tabs']['headers']['template_fields'] as $templateFieldName => $templateField) {
						if (strpos($name, $templateFieldName) === 0) {
							unset($box['tabs']['headers']['fields'][$name]);
							break;
						}
					}
				}
				
				//Get headers
				$headers = [];
				$previewString = '';
				$currentLineHeaders = [];
				$path = ze\file::getPathOfUploadInCacheDir($values['file/file']);
				if (pathinfo($path, PATHINFO_EXTENSION) == 'csv') {
					$file = fopen($path, 'r');
					if ($file) {
						$lineNumber = 0;
						while ($line = fgets($file)) {
							++$lineNumber;

							if ($lineNumber > 5) {
								break;
							}
							
							$previewString .= $line;
							$headers = str_getcsv($line);

							//If this line has headers, remember them now using a temporary variable...
							if ($lineNumber == $values['headers/key_line']) {
								$currentLineHeaders = $headers;
							}

							//Try and automatically find the headers if the first few lines are blank
							if ($box['key']['guess_key_line']
								&& $lineNumber == $values['headers/key_line']
								&& count($headers) == 1
								&& trim($headers[0]) == false 
								&& isset($fields['headers/key_line']['values'][$values['headers/key_line'] + 1])
							) {
								$values['headers/key_line']++;
							}

							$headers = [];
						}

						//... and use the temporary variable to restore them here.
						//The $headers variable will be looped through a few lines below.
						$headers = $currentLineHeaders;
					}
				} else {
					$csv = fopen('php://temp/', 'r+');
					
					require_once CMS_ROOT . 'zenario/libs/manually_maintained/lgpl/PHPExcel/Classes/PHPExcel.php';
					//Get file type
					$inputFileType = PHPExcel_IOFactory::identify($path);
					//Create reader object
					$objReader = PHPExcel_IOFactory::createReader($inputFileType);
					$objReader->setReadDataOnly(true);
					//Load spreadsheet
					$objPHPExcel = $objReader->load($path);
					$worksheet = $objPHPExcel->getSheet(0);
					
					$lineNumber = 0;
					$blankLimit = 5;
					$blankCount = 0;
					$currentLineHeaders = [];
					foreach ($worksheet->getRowIterator() as $row) {
						++$lineNumber;
						if ($lineNumber > 5) {
							break;
						}
						$cellIterator = $row->getCellIterator();
						foreach ($cellIterator as $cell) {
							$cellValue = trim($cell->getCalculatedValue() ?: '');
							$headers[] = $cellValue;
							$blankCount = ($cellValue == false) ? $blankCount + 1 : 0;
							if ($blankCount >= $blankLimit) {
								break;
							}
						}
						if ($blankCount) {
							$headers = array_splice($headers, 0, -$blankCount);
							$blankCount = 0;
						}

						fputcsv($csv, $headers);

						//If this line has headers, remember them now using a temporary variable...
						if ($lineNumber == $values['headers/key_line']) {
							$currentLineHeaders = $headers;
						}

						//Try and automatically find the headers if the first few lines are blank
						if ($box['key']['guess_key_line']
							&& $lineNumber == $values['headers/key_line']
							&& empty($headers)
							&& isset($fields['headers/key_line']['values'][$values['headers/key_line'] + 1])
						) {
							$values['headers/key_line']++;
						}

						$headers = [];
					}
					rewind($csv);
					$previewString = stream_get_contents($csv);

					//... and use the temporary variable to restore them here.
					//The $headers variable will be looped through a few lines below.
					$headers = $currentLineHeaders;
				}
				$values['headers/key_lines_preview'] = $previewString;
				$box['key']['guess_key_line'] = 0;
				$box['old_values']['key_line'] = $values['headers/key_line'];
				
				$datasetFieldColumns = [];
				foreach ($box['lovs']['dataset_fields'] as $datasetFieldId => $datasetField) {
					if (isset($datasetField['db_column'])) {
						$datasetFieldColumns[$datasetField['db_column']] = $datasetFieldId;
					}
				}
				
				$fields['headers/update_key_field']['values'] = [];
				
				//Create inputs
				$ord = 500;
				foreach ($headers as $i => $header) {
					$header = trim($header);
					if ($blankHeader = !$header) {
						$header = ze\admin::phrase('[Header field empty]');
					}
					foreach ($box['tabs']['headers']['template_fields'] as $name => $field) {
						$fieldName = $name . '_' . $i;
						$field['ord'] = ++$ord;
						if ($name == 'file_column_name') {
							//Left-hand side column: "Field name in file"
							$field['value'] = (string)$header;
						} elseif ($name == 'file_column_match') {
							//Middle column: "Database column"
							$field['readonly'] = $blankHeader;
							//Prefill this field if it matches any database column names
							if (ze::in($header, "status", "screen_name_confirmed", "created_date", "modified_date", "last_login", "last_profile_update_in_frontend", "suspended_date")) {
								$field['disabled'] = true;
								$field['empty_value'] = $this->phrase('Cannot be imported');
							} else {
								$field['empty_value'] = $this->phrase('-- Omit field --');
								$field['value'] = $field['current_value'] = (string)($datasetFieldColumns[strtolower($header)] ?? false);
							}
						}
						$box['tabs']['headers']['fields'][$fieldName] = $field;
					}
					
					//Set "update key field" values
					if (!$blankHeader) {
						$fields['headers/update_key_field']['values'][$i] = ['label' => $header];
					}
				}
				$box['key']['header_count'] = count($headers);
				ze\tuix::readValues($box, $fields, $values, $changes, $filling = false, $resetErrors = false);
			}
			
			//Update dataset field descriptions
			for ($i = 0; $i < $box['key']['header_count']; $i++) {
				$desc = '';
				if (($datasetFieldId = $values['headers/file_column_match_' . $i])
					&& ($datasetField = $box['lovs']['dataset_fields'][$datasetFieldId] ?? false)
					&& isset($datasetField['type'])
				) {
					$englishTypeName = ze\dataset::englishTypeName($datasetField['type']);
					$desc = $englishTypeName;
					switch ($datasetField['type']) {
						case 'checkbox':
						case 'group':
							$desc .= ', values 0 or 1';
							break;
						case 'select':
						case 'radios':
							$desc .= ', internal value ID';
							break;
						case 'date':
							$desc .= ', MySQL format (2019-01-01)';
							break;
						case 'centralised_radios':
						case 'centralised_select':
							$desc .= ', value ID';
							break;
						case 'editor': 
						case 'text':
						case 'textarea': 
						case 'url':
							$table = $datasetField['is_system_field'] ? $dataset['system_table'] : $dataset['table'];
							$sql = '
								SELECT COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH
								FROM information_schema.columns
								WHERE table_schema = "' . DBNAME . '"
								AND table_name = "' . ze\escape::sql(DB_PREFIX . $table) . '"
								AND COLUMN_NAME = "' . ze\escape::sql($datasetField['db_column']) . '"';
							$result = ze\sql::select($sql);
							$row = ze\sql::fetchRow($result);
							if ($row && $row[1]) {
								$desc .= ', max ' . $row[1] . ' characters';
							}
							break;
					}
				}
				$fields['headers/file_column_description_' . $i]['snippet']['html'] = $desc;
			}
			
			
		} elseif ($box['tab'] == 'preview') {
			$headerList = [];
			foreach ($box['tabs']['headers']['fields'] as $name => $field) {
				if (strpos($name, 'file_column_match') === 0) {
					$headerList[] = $values['headers/' . $name];
				}
			}
			$headerList = implode(',', $headerList);
			if ($box['old_values']['header_list'] != $headerList) {
				$box['old_values']['header_list'] = $headerList;
				//Actions when header list is changed
				$box['key']['update_preview'] = 1;
			}
			
			//Show a preview of the import file
			if ($box['key']['update_preview']) {
				$box['key']['update_preview'] = 0;
				$box['key']['update_actions'] = 1;
				
				$dataset = ze\dataset::details($box['key']['dataset']);
				$lineDatasetFields = [];
				foreach ($box['tabs']['headers']['fields'] as $name => $field) {
					if (strpos($name, 'file_column_match') === 0) {
						$value = false;
						if ($values['headers/' . $name]) {
							if (is_numeric($values['headers/' . $name])) {
								$value = ze\dataset::fieldDetails($values['headers/' . $name]);
							} else {
								$value = $values['headers/' . $name];
							}
						}
						$lineDatasetFields[] = $value;
					}
				}
								
				$previewLinesLimit = 200;
				$previewString = '';
				$totalReadableLinesWithoutErrors = 0;
				$totalUpdateCount = 0;
				$totalErrorCount = 0;
				$values['preview/problems'] = '';
				
				$linesToSkip = [];
				
				$path = ze\file::getPathOfUploadInCacheDir($values['file/file']);
				if (pathinfo($path, PATHINFO_EXTENSION) == 'csv') {
					$file = fopen($path, 'r');
					if ($file) {
						$lineNumber = 0;
						while ($line = fgets($file)) {
							$lineIsEmpty = true;
							
							if (trim(str_replace([',', ' '], '', $line)) != '') {
								$lineIsEmpty = false;
							}

							++$lineNumber;
							//Add line to preview
							if ($lineNumber <= $previewLinesLimit && !$lineIsEmpty) {
								$previewString .= $line;
							}

							//Validate line
							if ($lineNumber > $values['headers/key_line'] && !$lineIsEmpty) {
								$line = str_getcsv($line);
								$errorCount = $this->validateImportRecord($lineNumber, $line, $lineDatasetFields, $dataset, $values, $totalUpdateCount);
								
								//Make sure the line has the correct number of fields
								if (count($line) < count($lineDatasetFields)) {
									$error = ze\admin::phrase('Too few fields');
									$this->writeError($error, $errorCount, $values, $lineNumber);
								} elseif (count($line) > count($lineDatasetFields)) {
									$error = ze\admin::phrase('Too many fields');
									$this->writeError($error, $errorCount, $values, $lineNumber);
								}
								
								$totalErrorCount += $errorCount;
								if (!$lineIsEmpty && !$errorCount) {
									++$totalReadableLinesWithoutErrors;
								} else {
									$linesToSkip[] = $lineNumber;
								}
							} else {
								$linesToSkip[] = $lineNumber;
							}
						}
					}
				} else {
					$csv = fopen('php://temp/', 'r+');
					
					require_once CMS_ROOT . 'zenario/libs/manually_maintained/lgpl/PHPExcel/Classes/PHPExcel.php';
					//Get file type
					$inputFileType = PHPExcel_IOFactory::identify($path);
					//Create reader object
					$objReader = PHPExcel_IOFactory::createReader($inputFileType);
					$objReader->setReadDataOnly(true);
					//Load spreadsheet
					$objPHPExcel = $objReader->load($path);
					$worksheet = $objPHPExcel->getSheet(0);
				
					$lineNumber = 0;
					foreach ($worksheet->getRowIterator() as $row) {
						++$lineNumber;
						$cellIterator = $row->getCellIterator();
						$line = [];
						$lineIsEmpty = true;
						foreach ($cellIterator as $cell) {
							$cellValue = $cell->getCalculatedValue();
							$line[] = $cellValue;

							if (!empty($cellValue)) {
								$lineIsEmpty = false;
							}
						}
						//Add line to preview
						if ($lineNumber <= $previewLinesLimit && !$lineIsEmpty) {
							fputcsv($csv, $line);
						}

						//Validate line
						if ($lineNumber > $values['headers/key_line'] && !$lineIsEmpty) {
							$errorCount = $this->validateImportRecord($lineNumber, $line, $lineDatasetFields, $dataset, $values, $totalUpdateCount);
							$totalErrorCount += $errorCount;
							if (!$lineIsEmpty && !$errorCount) {
								++$totalReadableLinesWithoutErrors;
							} else {
								$linesToSkip[] = $lineNumber;
							}
						} else {
							$linesToSkip[] = $lineNumber;
						}
					}
					rewind($csv);
					$previewString = stream_get_contents($csv);
				}

				$totalReadableLines = $totalReadableLinesWithoutErrors + count($linesToSkip);
				$values['preview/csv_preview'] = $previewString;
				$fields['preview/problems']['label'] = ze\admin::nphrase('Problems (1 error)', 'Problems ([[n]] errors):', $totalErrorCount, ['n' => $totalErrorCount]);
				$fields['preview/total_readable_lines']['snippet']['html'] = '<b>' . ze\admin::phrase('Total readable lines (header and data):') . '</b> '. $totalReadableLines;
				
				$box['key']['lines_to_skip'] = implode(',', $linesToSkip);
				
				//Update record statement on actions tab
				$html = '';
				if ($values['file/type'] == 'insert_data') {
					$created = $totalReadableLinesWithoutErrors - $totalUpdateCount;
					$box['key']['new_records'] = $created;
					$html = ze\admin::nphrase('<b>1</b> new record will be inserted.', '<b>[[n]]</b> new records will be inserted.', $created, ['n' => $created]);
					if ($totalUpdateCount) {
						$html .= ' ' . ze\admin::nphrase('<b>1</b> record will be updated.', '<b>[[n]]</b> record will be updated.', $totalUpdateCount, ['n' => $totalUpdateCount]);
					}
				} else {
					$html = ze\admin::nphrase('<b>1</b> record will be updated.', '<b>[[n]]</b> records will be updated.', $totalReadableLinesWithoutErrors, ['n' => $totalReadableLinesWithoutErrors]);
				}
				$fields['actions/records_statement']['snippet']['html'] = $html;
			}
		} elseif ($box['tab'] == 'actions') {
			
			$datasetId = $box['key']['dataset'];
			$dataset = ze\dataset::details($datasetId);
			
			if ($box['key']['update_actions']) {
				$box['key']['update_actions'] = 0;
				
				
				$datasetFields = $box['lovs']['dataset_fields'];
				
				$datasetTabs = [];
				$result = ze\row::query('custom_dataset_tabs', true, ['dataset_id' => $datasetId], 'ord');
				while ($row = ze\sql::fetchAssoc($result)) {
					$datasetTabs[$row['name']] = $row;
				}
				
				//Order dataset fields correctly
				uasort($datasetFields, function($a, $b) use($datasetTabs) {
					if (!empty($a['tab_name']) && !empty($b['tab_name'])) {
						if ($a['tab_name'] == $b['tab_name']) {
							return $a['ord'] > $b['ord'] ? 1 : -1;
						} else {
							return $datasetTabs[$a['tab_name']]['ord'] > $datasetTabs[$b['tab_name']]['ord'] ? 1 : -1;
						}
					}
					return 0;
				});
				
				
				//Remove dataset fields set in step 2
				foreach ($box['tabs']['headers']['fields'] as $name => $field) {
					if (strpos($name, 'file_column_match') === 0 && $values['headers/' . $name]) {
						unset($datasetFields[$values['headers/' . $name]]);
					}
				}
				
				//Delete all previously generated data fields
				foreach ($box['tabs']['actions']['fields'] as $name => $field) {
					if (strpos($name, 'dataset_field_name') === 0 || strpos($name, 'dataset_field_value') === 0) {
						unset($box['tabs']['actions']['fields'][$name]);
					}
				}
				
				//Ordinals 500 and 501 are reserved for the status field label and value, respectively.
				//This will make the status field appear at the top.
				$ord = 502;
				$statusDatasetField = ze\dataset::fieldDetails('status', $dataset);
				foreach ($datasetFields as $datasetFieldId => $datasetField) {	
					if (empty($datasetField['db_column']) || empty($datasetField['tab_name'])) {
						continue;
					}
					
					//Hide certain fields when importing users
					if ($dataset['system_table'] == 'users') {
						if (in_array($datasetField['db_column'], ['screen_name_confirmed', 'created_date', 'modified_date', 'last_login', 'last_profile_update_in_frontend', 'suspended_date'])) {
							continue;
						}
					}
					
					foreach ($box['tabs']['actions']['template_fields'] as $name => $field) {
						$fieldName = $name . '_' . $datasetFieldId;
						
						if ($name == 'dataset_field_name') {
							if ($datasetFieldId == $statusDatasetField['id']) {
								//Status field should be at the top: logic for the label
								$field['ord'] = 500;
								$field['css_class'] = 'bold';
							} else {
								$field['ord'] = $ord - 1;
							}
							$field['value'] = $datasetField['db_column'];
						} elseif ($name == 'dataset_field_value') {
							if ($datasetFieldId == $statusDatasetField['id']) {
								//Status field should be at the top: logic for the field value
								$field['ord'] = 501;
							} else {
								$field['ord'] = $ord += 2;
							}
							$field['value'] = $values['actions/' . $fieldName] ?? false;
							switch ($datasetField['type']) {
								case 'group':
								case 'checkbox':
									$field['type'] = 'checkbox';
									break;
								case 'date':
									$field['type'] = 'date';
									break;
								case 'editor':
								case 'textarea':
								case 'url':
									$field['maxlength'] = 255;
									break;
								case 'text':
									//If importing users don't show system text fields for auto complete list
									if ($dataset['system_table'] == 'users' && $datasetField['is_system_field']) {
										continue 3;
									}
									$field['maxlength'] = 255;
									break;
								case 'radios':
								case 'select':
								case 'centralised_radios':
								case 'centralised_select':
									$field['values'] = ze\dataset::fieldLOV($datasetFieldId);
									$field['type'] = 'select';
									if ($dataset['system_table'] == 'users' && $datasetField['db_column'] == 'status') {
										$field['empty_value'] = '-- Select --';
										$field['format_onchange'] = true;

										//Never allow setting suspended status when importing users.
										unset($field['values']['suspended']);

										//In addition to never allowing suspended status,
										//if Extranet Base Module is not running,
										//only allow setting "Contact" status.
										if (!ze\module::inc('zenario_extranet')) {
											unset($field['values']['pending']);
											unset($field['values']['active']);
										}
									} else {
										$field['empty_value'] = "-- Don't import --";
									}
									break;
								default:
									continue 3;
							}
						}
						$box['tabs']['actions']['fields'][$fieldName] = $field;
					}
				}
				ze\tuix::readValues($box, $fields, $values, $changes, $filling = false, $resetErrors = false);
			}
			
			if ($dataset['system_table'] == 'users') {
				//Send welcome email visibility
				$statusDatasetField = ze\dataset::fieldDetails('status', $dataset);
				$fieldName = 'dataset_field_value_' . $statusDatasetField['id'];
				$fields['actions/send_welcome_email']['hidden'] = ($values['file/type'] != 'insert_data') || !isset($values['actions/' . $fieldName]) || ($values['actions/' . $fieldName] != 'active');
				
				//Show a warning if importing contacts with screen_name set
				$screenNameSelected = false;
				$screenNameDatastField = ze\dataset::fieldDetails('screen_name', $dataset);
				foreach (explode(',', $box['old_values']['header_list']) as $datasetFieldId) {
					if ($screenNameDatastField['id'] == $datasetFieldId) {
						$screenNameSelected = true;
						break;
					}
				}
				$box['tabs']['actions']['notices']['screen_name_for_contacts']['show'] = $values['actions/' . $fieldName] == 'contact' && $screenNameSelected;
			}
		}
	}
	
	private $unqiueIds = [];
	private $uniqueEmails = [];
	private $uniqueScreenNames = [];
	
	private $centralisedLists = [];
	
	private function validateImportRecord($lineNumber, $line, $lineDatasetFields, $dataset, &$values, &$totalUpdateCount) {
		$errorCount = 0;
		$userSystemFields = [];
		$columnNumberLink = [];
		$mergeOrOverwriteRecord = false;
		
		
		//Validate fields in record
		foreach ($lineDatasetFields as $index => $datasetField) {
			if (isset($line[$index])) {
				$value = trim($line[$index]);
			} else {
				$value = '';
			}
			$columnNumber = $index + 1;
			
			//If updating, ensure there is a matching field, and not multiple entries
			if ($values['file/type'] == 'update_data' && $index == $values['headers/update_key_field']) {
				if (!$value) {
					$error = ze\admin::phrase('ID field is blank');
					$this->writeError($error, $errorCount, $values, $lineNumber, $columnNumber);
				} else {
					//Record duplicates of chosen ID field in import
					if ($lineNumbers = $this->checkUniqueValue($lineNumber, $value, $this->unqiueIds)) {
						$error = ze\admin::phrase('More than one line in the file has a matching ID column ([[lines]])', ['lines' => implode(', ', $lineNumbers)]);
						$this->writeError($error, $errorCount, $values, $lineNumber, $columnNumber);
					}
					
					//Check single matching record exists
					if ($datasetField == 'id') {
						$table = !empty($dataset['system_table']) ? $dataset['system_table'] : $dataset['table'];
						$idColumn = ze\row::idColumnOfTable($table);
						$existingRecordCount = ze\row::count($dataset['system_table'], [$idColumn => $value]);
					} elseif ($datasetField['is_system_field']) {
						$existingRecordCount = ze\row::count($dataset['system_table'], [$datasetField['db_column'] => $value]);
					} else {
						$existingRecordCount = ze\row::count($dataset['table'], [$datasetField['db_column'] => $value]);
					}
					if ($existingRecordCount == 0) {
						$error = ze\admin::phrase('No existing record found for ID column "[[db_column]]"', $datasetField);
						$this->writeError($error, $errorCount, $values, $lineNumber, $columnNumber);
					} elseif ($existingRecordCount > 1) {
						$error = ze\admin::phrase('More than one existing record found for ID column "[[db_column]]"', $datasetField);
						$this->writeError($error, $errorCount, $values, $lineNumber, $columnNumber);
					}
				}
			}
			
			if (!is_array($datasetField)) {
				continue;
			}
			
			$columnNumberLink[$datasetField['db_column']] = $columnNumber;
			
			//Custom users validation
			if ($dataset['system_table'] == 'users' && $datasetField['is_system_field']) {
				$userSystemFields[$datasetField['db_column']] = $value;
				if ($datasetField['db_column'] == 'email') {
					//Check email is unique
					if ($lineNumbers = $this->checkUniqueValue($lineNumber, $value, $this->uniqueEmails)) {
						$error = ze\admin::phrase('More than one line in the file has the same email address ([[lines]])', ['lines' => implode(', ', $lineNumbers)]);
						$this->writeError($error, $errorCount, $values, $lineNumber, $columnNumber);
					} elseif ($values['file/type'] == 'insert_data' && $values['headers/insert_options'] != 'no_update') {
						$count = ze\row::count($dataset['system_table'], [$datasetField['db_column'] => $value]);
						if ($count >= 1) {
							$mergeOrOverwriteRecord = true;
						}
					}
				} elseif ($datasetField['db_column'] == 'screen_name') {
					//Check screen_name is unique
					if ($lineNumbers = $this->checkUniqueValue($lineNumber, $value, $this->uniqueScreenNames)) {
						$error = ze\admin::phrase('More than one line in the file has the same screen name ([[lines]])', ['lines' => implode(', ', $lineNumbers)]);
						$this->writeError($error, $errorCount, $values, $lineNumber, $columnNumber);
					}
				}
			}
			
			//Check dataset field validation rules
			if ($value !== '') {
				switch ($datasetField['validation']) {
					case 'email':
						//Ignore this for users email as it's validated with ze\userAdm::isInvalid()
						if (!($dataset['system_table'] == 'users' && $datasetField['db_column'] == 'email')) {
							if (!ze\ring::validateEmailAddress($value)) {
								if (!$error = $datasetField['validation_message'])  {
									$error = 'Value is in incorrect format for email';
								}
								$this->writeError($error, $errorCount, $values, $lineNumber, $columnNumber);
							}
						}
						break;
					case 'emails':
						if (!ze\ring::validateEmailAddress($value, true)) {
							if (!$error = $datasetField['validation_message']) {
								$error = 'Value is in incorrect format for emails';
							}
							$this->writeError($error, $errorCount, $values, $lineNumber, $columnNumber);
						}
						break;
					case 'no_spaces':
						if (preg_replace('/\S/', '', $value)) {
							if (!$error = $datasetField['validation_message']) {
								$error = 'Value cannot contain spaces';
							}
							$this->writeError($error, $errorCount, $values, $lineNumber, $columnNumber);
						}
						break;
					case 'numeric':
						if (!is_numeric($value)) {
							if (!$error = $datasetField['validation_message']) {
								$error = 'Value must be numeric';
							}
							$this->writeError($error, $errorCount, $values, $lineNumber, $columnNumber);
						}
						break;
					case 'screen_name':
						if (!ze\ring::validateScreenName($value)) {
							if (!$error = $datasetField['validation_message']) {
								$error = 'Screen name is invalid';
							}
							$this->writeError($error, $errorCount, $values, $lineNumber, $columnNumber);
						}
						break;
				}
			}
			
			//Validate required fields
			if ($datasetField['required'] && ($value === '')) {
				if (!$error = $datasetField['required_message']) {
					$error = 'Value is required but missing';
				}
				$this->writeError($error, $errorCount, $values, $lineNumber, $columnNumber);
			}
			
			//Validate fields with a values source
			if ($datasetField['values_source']) {
				if (!isset($this->centralisedLists[$datasetField['values_source']])) {
					$this->centralisedLists[$datasetField['values_source']] = ze\dataset::centralisedListValues($datasetField['values_source']);
				}
				$lov = $this->centralisedLists[$datasetField['values_source']];
				
				if (!isset($lov[$value])) {
					$cannotImport = true;
					//If this is a centralised list of countries, allow user to enter country names
					if ($datasetField['values_source'] == 'zenario_country_manager::getActiveCountries') {
						$searchArray = array_map('strtolower', $lov);
						if (in_array(strtolower($value), $searchArray)) {
							$cannotImport = false;
						}
					}
					if ($cannotImport) {
						$displayValue = $value;
						if (strlen($value) >= 33) {
							$displayValue = substr($value, 0, 30) . '...';
						}
						$error = ze\admin::phrase('Unknown list value "[[value]]"', ['value' => $displayValue]);
						$this->writeError($error, $errorCount, $values, $lineNumber, $columnNumber);
					}
				}
			}
		}
		
		if ($values['file/type'] == 'insert_data' && $dataset['system_table'] == 'users' && !$mergeOrOverwriteRecord) {
			$result = ze\userAdm::isInvalid($userSystemFields);
			if (ze::isError($result)) {
				foreach ($result->errors as $dbColumn => $error) {
					$columnNumber = false;
					if (isset($columnNumberLink[$dbColumn])) {
						$columnNumber = $columnNumberLink[$dbColumn];
					}
					switch ($error) {
						case '_ERROR_SCREEN_NAME_INVALID':
							$error = ze\admin::phrase('Screen name invalid');
							break;
						case '_ERROR_SCREEN_NAME_IN_USE':
							$error = ze\admin::phrase('Screen name in use');
							break;
						case '_ERROR_EMAIL_INVALID':
							$error = ze\admin::phrase('Value is in incorrect format for email');
							break;
						case '_ERROR_EMAIL_NAME_IN_USE':
							$error = ze\admin::phrase('Email is duplicate');
							break;
					}
					$this->writeError($error, $errorCount, $values, $lineNumber, $columnNumber);
				}
			}
		}
		
		if (!$errorCount && $mergeOrOverwriteRecord) {
			++$totalUpdateCount;
		}
		
		return $errorCount;
	}
	
	private function writeError($error, &$errorCount, &$values, $lineNumber, $columnNumber = false) {
		++$errorCount;
		if ($columnNumber) {
			$error = ze\admin::phrase('Error (Line [[line]], Value [[value]]): [[message]]', ['line' => $lineNumber, 'value' => $columnNumber, 'message' => $error]);
		} else {
			$error = ze\admin::phrase('Error (Line [[line]]): [[message]]', ['line' => $lineNumber, 'message' => $error]);
		}
		$values['preview/problems'] .= $error . PHP_EOL;
	}
	
	private function checkUniqueValue($lineNumber, $value, $uniqueValues) {
		if (!isset($uniqueValues[$value])) {
			$uniqueValues[$value] = $lineNumber;
			return false;
		} elseif (!is_array($uniqueValues[$value])) {
			$uniqueValues[$value] = [$uniqueValues[$value], $lineNumber];
			return $uniqueValues[$value];
		} else {
			$uniqueValues[$value][] = $lineNumber;
			return $uniqueValues[$value];
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$admin = ze\row::get('admins', ['username', 'authtype'], ze\admin::id());
		
		//Include required modules
		$dataset = ze\dataset::details($box['key']['dataset']);
		$systemIdCol = !empty($dataset['system_table']) ? ze\row::idColumnOfTable($dataset['system_table']) : false;
		$customIdCol = !empty($dataset['table']) ? ze\row::idColumnOfTable($dataset['table']) : false;
		if ($dataset['label'] == 'Locations') {
			ze\module::inc('zenario_location_manager');
		}
		
		//Load lines to skip
		$linesToSkip = [];
		if ($box['key']['lines_to_skip']) {
			$linesToSkip = array_flip(explode(',', $box['key']['lines_to_skip']));
		}
		
		//Load what dataset field is on each record column
		$datasetFields = [];
		$lineDatasetFields = [];
		foreach ($box['tabs']['headers']['fields'] as $name => $field) {
			if (strpos($name, 'file_column_match') === 0) {
				$value = false;
				if ($values['headers/' . $name]) {
					$value = $values['headers/' . $name];
					$datasetFields[$value] = ze\dataset::fieldDetails($value);
				}
				$lineDatasetFields[] = $value;
			}
		}
		
		
		//Load dataset fields used when importing special custom fields
		$extraDatasetFields = [];
		if ($dataset['system_table'] == 'users') {
			foreach (['first_name', 'last_name'] as $dbColumn) {
				$datasetField = ze\dataset::fieldDetails($dbColumn, $dataset);
				$extraDatasetFields[$dbColumn] = $datasetField['id'];
				$datasetFields[$datasetField['id']] = $datasetField;
			}
		}
		
		//Load constants from "actions" tab
		$importBaseRecord = [];
		foreach ($box['tabs']['actions']['fields'] as $name => $field) {
			$prefix = 'dataset_field_value';
			if (strpos($name, $prefix) === 0 && $values['actions/' . $name]) {
				$datasetFieldId = (int)substr($name, strlen($prefix . '_'));
				$importBaseRecord[$datasetFieldId] = $values['actions/' . $name];
				$datasetFields[$datasetFieldId] = ze\dataset::fieldDetails($datasetFieldId);
			}
		}
		
		$importRecords = [];
		
		$path = ze\file::getPathOfUploadInCacheDir($values['file/file']);
		if (pathinfo($path, PATHINFO_EXTENSION) == 'csv') {
			$file = fopen($path, 'r');
			if ($file) {
				$lineNumber = 0;
				while ($line = fgets($file)) {
					++$lineNumber;
					if ($lineNumber > $values['headers/key_line'] && !isset($linesToSkip[$lineNumber])) {
						$line = str_getcsv($line);
						$importRecords[$lineNumber] = $this->addRecordToList($line, $importBaseRecord, $lineDatasetFields, $extraDatasetFields);
					}
				}
			}
		} else {
			require_once CMS_ROOT . 'zenario/libs/manually_maintained/lgpl/PHPExcel/Classes/PHPExcel.php';
			//Get file type
			$inputFileType = PHPExcel_IOFactory::identify($path);
			//Create reader object
			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
			$objReader->setReadDataOnly(true);
			//Load spreadsheet
			$objPHPExcel = $objReader->load($path);
			$worksheet = $objPHPExcel->getSheet(0);
		
			$lineNumber = 0;
			foreach ($worksheet->getRowIterator() as $row) {
				++$lineNumber;
				if ($lineNumber > $values['headers/key_line'] && !isset($linesToSkip[$lineNumber])) {
					$cellIterator = $row->getCellIterator();
					$line = [];
					foreach ($cellIterator as $cell) {
						$cellValue = $cell->getCalculatedValue();
						$line[] = $cellValue;
					}
					$importRecords[$lineNumber] = $this->addRecordToList($line, $importBaseRecord, $lineDatasetFields, $extraDatasetFields);
				}
			}
		}
		
		$countryList = [];
		$countryListFlipped = [];
		
		foreach ($importRecords as $lineNumber => $importRecord) {
			$data = [];
			$customData = [];
			$recordId = false;
			//Sort data into system and custom
			foreach ($importRecord as $datasetFieldId => $value) {
				if ($datasetFieldId == 'id') {
					$recordId = $value;
				} elseif ($datasetField = $datasetFields[$datasetFieldId] ?? false) {
					//When importing country, try and match text to Id
					if (!empty($datasetField['values_source']) && $datasetField['values_source'] == 'zenario_country_manager::getActiveCountries') {
						if (!$countryList) {
							$countryList = ze\dataset::centralisedListValues($fieldIdDetails[$fieldId]['values_source']);
							$countryList = array_map('strtolower', $countryList);
							$countryListFlipped = array_flip($countryList);
						}
						if (!isset($countryList[$value]) && isset($countryListFlipped[strtolower($value)])) {
							$value = $countryListFlipped[strtolower($value)];
						}
					}
					
					if ($datasetField['is_system_field']) {
						$data[$datasetField['db_column']] = $value;
					} else {
						$customData[$datasetField['db_column']] = $value;
					}
				}
			}
			
			//Create or update records
			if ($values['file/type'] == 'insert_data') {
				
				//Custom insert for users
				if ($dataset['system_table'] == 'users') {
					
					//Attempt to update fields on email
					if (!empty($data['email'])) {
						$recordId = ze\row::get($dataset['system_table'], $systemIdCol, ['email' => $data['email']]);
					}
					if ($values['headers/insert_options'] == 'overwrite' && $recordId) {
						$data['last_edited_admin_id'] = ze\admin::id();
						ze\userAdm::save($data, $recordId);
						ze\row::set($dataset['table'], $customData, $recordId);
						continue;
					} elseif ($values['headers/insert_options'] == 'merge' && $recordId) {
						$systemKeys = array_keys($data);
						if ($systemKeys) {
							$foundData = ze\row::get($dataset['system_table'], $systemKeys, $recordId);
							foreach ($foundData as $col => $val) {
								if ($val) {
									unset($data[$col]);
								}
							}
							
							$data['last_edited_admin_id'] = ze\admin::id();
							ze\userAdm::save($data, $recordId);
						}
						
						$customkeys = array_keys($customData);
						if ($customkeys) {
							$foundData = ze\row::get($dataset['table'], $customkeys, $recordId);
							foreach ($foundData as $col => $val) {
								if ($val) {
									unset($customData[$col]);
								}
							}
							ze\row::set($dataset['table'], $customData, $recordId);
						}
						continue;
					}
					
					if (!isset($data['email'])) {
						$data['email'] = '';
					}
					if (!isset($data['first_name'])) {
						$data['first_name'] = '';
					}
					if (!isset($data['last_name'])) {
						$data['last_name'] = '';
					}
					$sendWelcomeEmail = false;
					if (!$recordId) {
						if (!empty($data['status'])) {
							if ($data['status'] != 'contact' && empty($data['password'])) {
								$data['password'] = ze\userAdm::createPassword();
							}
							if ($data['status'] == 'active' && $values['actions/send_welcome_email'] && $values['actions/email_to_send']) {
								//Send a welcome email
								$sendWelcomeEmail = true;
							}
						}
						$data['creation_method'] = 'admin';
						$data['creation_method_note'] = 'Imported by admin ' . $admin['username'];
					}
					
					//Do not allow screen names to be imported to sites that don't use screen names
					if (!ze::setting('user_use_screen_name')) {
						unset($data['screen_name']);
					}
					
					$data['created_admin_id'] = ze\admin::id();
					$recordId = ze\userAdm::save($data);
					
					if (!ze::isError($recordId)) {
						if ($sendWelcomeEmail && !empty($data['email'])) {
							$mergeFields = $data;
							$mergeFields['cms_url'] = ze\link::absolute();
							zenario_email_template_manager::sendEmailsUsingTemplate($data['email'], $values['actions/email_to_send'], $mergeFields);
						}
					
						//If site uses screen names and no screen name is imported, use the identifier as a screen name
						if (empty($data['screen_name']) && ze::setting('user_use_screen_name')) {
							$identifier = ze\user::identifier($recordId);
							ze\row::update('users', ['screen_name' => $identifier], $recordId);
						}
					}
					
				//Custom insert for locations
				} elseif ($dataset['system_table'] == 'locations') {
					$data['last_updated_via_import'] = ze\date::now();
					$recordId = zenario_location_manager::createLocation($data);
				//Other datasets
				} else {
					if ($dataset['system_table']) {
						$recordId = ze\row::insert($dataset['system_table'], $data);
					}
				}
				
				if ($dataset['table']) {
					$recordId = ze\row::set($dataset['table'], $customData, $recordId);
				}
				
				
			} elseif ($values['file/type'] == 'update_data') {
				//Get Id to update
				if (!$recordId && ($keyDatasetField = $datasetFields[$lineDatasetFields[$values['headers/update_key_field']]] ?? false)) {
					if ($keyDatasetField['is_system_field']) {
						$recordId = ze\row::get($dataset['system_table'], $systemIdCol, [$keyDatasetField['db_column'] => $data[$keyDatasetField['db_column']]]);
					} else {
						$recordId = ze\row::get($dataset['table'], $customIdCol, [$keyDatasetField['db_column'] => $customData[$keyDatasetField['db_column']]]);
					}
				}
				
				//Update records
				if ($recordId) {
					//Custom dataset update rules
					if ($dataset['system_table'] == 'users') {
						if (!ze::setting('user_use_screen_name')) {
							unset($data['screen_name']);
						}
						$data['modified_date'] = ze\date::now();
					} elseif (defined('ZENARIO_LOCATION_MANAGER_PREFIX') && $dataset['system_table'] == ZENARIO_LOCATION_MANAGER_PREFIX . 'locations') {
						$data['last_updated_via_import'] = ze\date::now();
					}
					
					if ($dataset['system_table'] && $data) {
						if ($dataset['system_table'] == 'users') {
							
							$data['last_edited_admin_id'] = ze\admin::id();
							ze\userAdm::save($data, $recordId);
						} else {
							ze\row::update($dataset['system_table'], $data, $recordId);
						}
					}
					if ($dataset['table'] && $customData) {
						ze\row::set($dataset['table'], $customData, $recordId);
					}
				}
			}
		}
	}
	
	private function addRecordToList($line, $importBaseRecord, $lineDatasetFields, $extraDatasetFields) {
		$importRecord = $importBaseRecord;
		foreach ($line as $i => $value) {
			if (!empty($lineDatasetFields[$i])) {
				$value = trim($value ?: '');
				//Special fields
				if ($lineDatasetFields[$i] == 'name_split_on_first_space') {
					if (($pos = strpos($value, ' ')) !== false) {
						$importRecord[$extraDatasetFields['first_name']] = substr($value, 0, $pos);
						$importRecord[$extraDatasetFields['last_name']] =  substr($value, $pos + 1);
					} else {
						$importRecord[$extraDatasetFields['first_name']] = $value;
					}
				} elseif ($lineDatasetFields[$i] == 'name_split_on_last_space') {
					if (($pos = strrpos($value, ' ')) !== false) {
						$importRecord[$extraDatasetFields['first_name']] = substr($value, 0, $pos);
						$importRecord[$extraDatasetFields['last_name']] = substr($value, $pos + 1);
					} else {
						$importRecord[$extraDatasetFields['first_name']] = $value;
					}
				//Dataset fields
				} else {
					$importRecord[$lineDatasetFields[$i]] = $value;
				}
			}
		}
		return $importRecord;
	}
}