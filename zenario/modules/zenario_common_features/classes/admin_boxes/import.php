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


class zenario_common_features__admin_boxes__import extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$layouts = zenario_email_template_manager::getTemplatesByNameIndexedByCode('User Activated',false);
		if (count($layouts)==0) {
			$layouts = zenario_email_template_manager::getTemplatesByNameIndexedByCode('Account Activated',false);
		}
		if (count($layouts)){
			$template = current($layouts);
			$fields['actions/email_to_send']['value'] = arrayKey($template,'code');
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		// Handle tab naviagtion and validation
		$errors = &$box['tabs'][$box['tab']]['errors'];
		switch ($box['tab']) {
			case 'file':
				// --- Validate file tab --- 
				if (!empty($box['tabs']['file']['fields']['next']['pressed']) && $values['file/file']) {
					$path = getPathOfUploadedFileInCacheDir($values['file/file']);
					if (pathinfo($path, PATHINFO_EXTENSION) == 'csv') {
						$string = file_get_contents($path);
						$isUTF8 = mb_detect_encoding($string, 'UTF-8', true);
						if ($isUTF8 === false) {
							$errors[] = adminPhrase('CSV files must be UTF-8 encoded to be imported.');
						}
					}
					if (empty($errors)) {
						$box['tab'] = 'headers';
					}
				}
				break;
			case 'headers':
				// --- Validate headers tab --- 
				if (!empty($box['tabs']['headers']['fields']['next']['pressed'])) {
					$updateMode = ($values['file/type'] == 'update_data');
					
					// Validate Key line
					if (!$values['headers/key_line']) {
						$errors[] = adminPhrase('Please select the key containing field names');
					
					// If updating ensure key field is selected
					} elseif ($updateMode) {
						if (!$values['headers/update_key_field']) {
							$errors[] = adminPhrase('Please select a field name to uniquely identify each line');
						}
					}
					if (!$errors) {
						$datasetId = $box['key']['dataset'];
						$datasetFieldDetails = self::getAllDatasetFieldDetails($datasetId);
						$requiredFieldsIncludedInImport = array();
						$datasetColumns = array();
						foreach ($datasetFieldDetails as $fieldId => $details) {
							$datasetColumns[$fieldId] = $details['db_column'];
							if ($details['required']) {
								$requiredFieldsIncludedInImport[$fieldId] = false;
							}
						}
						$noFieldsMatched = true;
						$IDColumn = false;
						$emailColumn = false;
						foreach ($box['tabs']['headers']['fields'] as $name => $field) {
							if (self::isGeneratedField($name, $field) && isset($field['type']) && ($field['type'] == 'select')) {
								$selectListFieldId = $values['headers/' . $name];
								if ($selectListFieldId) {
									$noFieldsMatched = false;
									if (isset($datasetFieldDetails[$selectListFieldId]) && ($datasetFieldDetails[$selectListFieldId]['db_column'] == 'email')) {
										$emailColumn = true;
									}
								}
								if ($updateMode && $selectListFieldId && ($name == ('database_column__' . $values['headers/update_key_field']))) {
									$IDColumn = $values['headers/' . $name];
								}
								$currentMatchedFields[$name] = $selectListFieldId;
								if (isset($requiredFieldsIncludedInImport[$selectListFieldId])) {
									$requiredFieldsIncludedInImport[$selectListFieldId] = true;
								}
							}
						}
						
						// Ensure at least one field is being imported
						if ($noFieldsMatched) {
							$errors[] = adminPhrase('You need to match at least one field to continue');
						} else {
							// If updating ensure key field is selected
							if ($updateMode) {
								if (!$IDColumn) {
									$errors[] = adminPhrase('You must match the key field to a field name');
								}
							}
							// Validate required fields
							if (!$updateMode) {
								$missingRequiredFields = array();
								foreach ($requiredFieldsIncludedInImport as $fieldId => $found) {
									if ($found === false) {
										$missingRequiredFields[] = $datasetColumns[$fieldId];
									}
								}
								if ($missingRequiredFields) {
									$missingFields = implode(', ', $missingRequiredFields);
									$errors[] = adminPhrase('The following required fields are missing: [[missingFields]]', array('missingFields' => $missingFields));
								}
								$datasetDetails = getDatasetDetails($datasetId);
								if (!$emailColumn && ($datasetDetails['system_table'] == 'users') && ($values['headers/insert_options'] != 'no_update')) {
									$errors[] = adminPhrase('You must include the email column to update matching fields on email');
								}
							}
						}
					}
					if (empty($errors)) {
						$box['tab'] = 'preview';
					}
				} elseif (!empty($box['tabs']['headers']['fields']['previous']['pressed'])) {
					$box['tab'] = 'file';
				}
				break;
			case 'preview':
				// --- Validate preview tab --- 
				if (!empty($box['tabs']['preview']['fields']['previous']['pressed'])) {
					$box['tab'] = 'headers';
				} elseif (!empty($box['tabs']['preview']['fields']['next']['pressed'])) {
					$box['tab'] = 'actions';
				} 
				break;
			case 'actions':
				// --- Validate actions tab --- 
				if ($saving) {
					$datasetId = $box['key']['dataset'];
					$datasetDetails = getDatasetDetails($datasetId);
					
					if ($datasetDetails['extends_organizer_panel'] == 'zenario__users/panels/users') {
						// If importing users then force admin to choose a status
						$statusField = getDatasetFieldDetails('status', $datasetId);
						if (isset($values['actions/value__' . $statusField['id']]) 
							&& !$values['actions/value__' . $statusField['id']] 
							&& $box['key']['new_records']
						) {
							$errors[] = adminPhrase('You must select a status when creating new user records.');
						}
					}
					$datasetFieldDetails = self::getAllDatasetFieldDetails($datasetId);
					$childFields = array();
					foreach ($datasetFieldDetails as $fieldId => $details) {
						
						// Child fields validation 1. record all fields with parents
						if ($details['parent_id']) {
							$childFields[$fieldId] = $details['parent_id'];
						}
					}
					$foundChildFields = array();
					foreach ($box['tabs']['headers']['fields'] as $name => $field) {
						if (self::isGeneratedField($name, $field) && isset($field['type']) && ($field['type'] == 'select')) {
							$selectListFieldId = $values['headers/' . $name];
							$currentMatchedFields[$name] = $selectListFieldId;
							
							// Child fields validation 2. record child fields currently selected
							if (isset($childFields[$selectListFieldId])) {
								$foundChildFields[$selectListFieldId] = true;
							}
						}
					}
					
					// Child fields validation 3. make sure parent is also selected
					foreach ($foundChildFields as $childId => $val) {
						$parentId = $childFields[$childId];
						
						if (!in_array($parentId, $currentMatchedFields) && empty($values['value__'.$parentId])) {
							$errors[] = adminPhrase('You must include the column "[[parent]]" because the column "[[child]]" depends on it.', 
								array(
									// This looks really confusing
									'child' => $datasetFieldDetails[$childId]['db_column'],
									'parent' => $datasetFieldDetails[$parentId]['db_column'])
								);
						}
					}
					
				} elseif (!empty($box['tabs']['actions']['fields']['previous']['pressed'])) {
					$box['tab'] = 'preview';
				}
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		// Get dataset details
		$datasetId = $box['key']['dataset'];
		$datasetDetails = getDatasetDetails($datasetId);
		$datasetFieldDetails = self::getAllDatasetFieldDetails($datasetId);
		
		// Handle navigation
		$step = 1;
		switch ($box['tab']) {
			case 'headers':
				$fields['headers/update_desc']['hidden'] = 
				$fields['headers/update_key_field']['hidden'] = 
					$values['file/type'] != 'update_data';
				
				$fields['headers/insert_desc']['hidden'] = 
				$fields['headers/insert_options']['hidden'] = 
					($values['file/type'] != 'insert_data') || ($datasetDetails['system_table'] != 'users');
				$step = 2;
				break;
			case 'preview':
				$step = 3;
				break;
			case 'actions':
				$step = 4;
				break;
		}
		
		// Set title
		$box['title'] = 'Dataset Import Wizard - Step ' . $step . ' of 4';
		
		// Set CSS class
		if ($box['tab'] == 'actions') {
			$box['css_class'] = '';
		} else {
			$box['css_class'] = 'zenario_fab_default_style zenario_fab_hide_save_button';
		}
		
		// Stop if step 1
		if ($box['tab'] == 'file' || !$values['file/file']) {
			return;
		}
		
		
		$path = getPathOfUploadedFileInCacheDir($values['file/file']);
		$newFileUploaded = ($path != $box['key']['file_path']);
		$box['key']['file_path'] = $path;
		
		
		// Include modules if needed
		switch ($datasetDetails['extends_organizer_panel']) {
			case 'zenario__locations/panel':
				inc('zenario_location_manager');
				break;
		}
		
		
		// Clear old generated fields from fields tab
		$currentMatchedFields = array();
		foreach ($box['tabs']['headers']['fields'] as $name => $field) {
			if (self::isGeneratedField($name, $field)) {
				if (isset($fields['headers/'.$name]['type']) && ($fields['headers/'.$name]['type'] == 'select')) {
					$selectListFieldId = $values['headers/'.$name];
					$currentMatchedFields[$name] = $selectListFieldId;
				}
				unset($box['tabs']['headers']['fields'][$name]);
			}
		}
		
		// Get list of values for header to DB column matching.
		$DBColumnSelectListValues = listCustomFields($datasetDetails, $flat = false, $filter = false, $customOnly = false, $useOptGroups = true);
		// Show an ID field for updates
		$update = $values['file/type'] == 'update_data';
		if ($update) {
			$DBColumnSelectListValues['id'] = array('ord' => 0, 'label' => 'ID Column');
		}
		// Show special fields for users
		if ($datasetDetails['system_table'] == 'users') {
			$DBColumnSelectListValues['name_split_on_first_space'] = array('ord' => 0.1, 'label' => 'Name -> First Name, Last Name, split on first space');
			$DBColumnSelectListValues['name_split_on_last_space'] = array('ord' => 0.2, 'label' => 'Name -> First Name, Last Name, split on last space');
		}
		$box['lovs']['dataset_fields'] = $DBColumnSelectListValues;
		
		
		// Create an array of field IDs to database columns to use when trying to autoset headers to DB columns
		$datasetColumns = array();
		foreach ($datasetFieldDetails as $fieldId => $details) {
			$datasetColumns[$fieldId] = $details['db_column'];
		}
		
		
		$keyLine = ($values['headers/key_line'] && !$newFileUploaded) ? $values['headers/key_line'] : 0;
		
		
		$header = false;
		$headerCount = 0;
		
		$previewLinesLimit = 200;
		$filePreviewString = '';
		$problems = '';
		
		// Track error number and lines with errors
		$warningCount = 0;
		$errorCount = 0;
		$updateCount = 0;
		$warningLines = array();
		$errorLines = array();
		$blankLines = array();
		
		$IDColumnIndex = false;
		
		// Link between row number and field ID
		$rowFieldIdLink = array();
		if (pathinfo($path, PATHINFO_EXTENSION) == 'csv') {
			ini_set('auto_detect_line_endings', true);
			$f = fopen($path, 'r');
			if ($f) {
				$lineNumber = 0;
				// Loop through each line
				while ($line = fgets($f)) {
					$lineNumber++;
					if (trim($line) != '') {
						$lineValues = str_getcsv($line);
						
						$thisIsKeyLine = (!$keyLine && !$header) || ($keyLine && $keyLine == $lineNumber);
						if ($thisIsKeyLine) {
							$headerCount = count($lineValues);
							$keyLine = $lineNumber;
						}
						$warning = false;
						$error = false;
						// Loop through each value
						foreach ($lineValues as $dataCount => $value) {
							$value = trim($value);
							if ($thisIsKeyLine) {
								// Attempt to autoset db_columns on fields tab
								$fieldId = array_search(strtolower($value), $datasetColumns);
								if ($value == 'id') { 
									$fieldId = $value;
								}
								// Fill row field link
								if (isset($currentMatchedFields['database_column__'.$value]) && !empty($currentMatchedFields['database_column__'.$value])) {
									$rowFieldIdLink[$dataCount] = $currentMatchedFields['database_column__'.$value];
								}
								// Set column headers
								if ($dataCount == 0) {
									self::generateFieldHeaders($box, 'csv');
								}
								
								// Set columns table
								self::generateFieldRow($box, $dataCount, $value, $currentMatchedFields, $fieldId);
								
								// Get key field index
								if ($update && ($value == $values['headers/update_key_field'])) {
									$IDColumnIndex = $dataCount;
								}
								
								// Set key field values
								if ($update) {
									$fields['headers/update_key_field']['values'][$value] = array(
										'label' => $value,
										'ord' => $dataCount
									);
								}
							} elseif ($header) {
								/*
								// Field errors
								if (!empty($value) && isset($rowFieldIdLink[$dataCount]) && 
									!self::validateImportValue($problems, $datasetDetails['system_table'], $datasetFieldDetails, $rowFieldIdLink[$dataCount], $dataCount, $value, $lineNumber)) {
									$error = true;
								}
								*/
							}
						}
						if ($thisIsKeyLine) {
							$header = true;
							if ($box['tab'] == 'headers') {
								break;
							}
							if ($IDColumnIndex !== false) {
								$box['key']['ID_column'] = $rowFieldIdLink[$IDColumnIndex];
							}
						} elseif ($header) {
							
							// Validate import line
							$lineProblems = '';
							$lineErrorCount = self::validateImportLine($lineProblems, $datasetDetails, $datasetFieldDetails, $rowFieldIdLink, $lineValues, $lineNumber, $IDColumnIndex, $update, $values['headers/insert_options'], $updateCount);
							$problems .= $lineProblems;
							$error = ($lineErrorCount > 0);
							
							// Line errors
							$dataCount = count($lineValues);
							if ($dataCount != $headerCount) {
								if ($dataCount < $headerCount) {
									$problems .= 'Error (Line '. $lineNumber. '): Too few fields';
								} else {
									$problems .= 'Error (Line '. $lineNumber. '): Too many fields';
								}
								$error = true;
								$problems .= "\r\n";
							}
							// Record lines with warnings and errors
							if ($error) {
								$errorCount++;
								$errorLines[] = $lineNumber;
							} elseif ($warning) {
								$warningCount++;
								$warningLines[] = $lineNumber;
							}
							if ($warning && $error) {
								$warningCount++;
							}
							
						} else {
							$blankLines[] = $lineNumber;
						}
					} else {
						$blankLines[] = $lineNumber;
					}
					if ($lineNumber <= $previewLinesLimit) {
						$filePreviewString .= $line;
					}
					// Stop if keyline isn't on the first 5 lines
					if (!$header && $lineNumber >= 5) {
						break;
					}
				}
			}
		} else {
			require_once CMS_ROOT . 'zenario/libraries/lgpl/PHPExcel/Classes/PHPExcel.php';
			
			// Get file type
			$inputFileType = PHPExcel_IOFactory::identify($path);
			
			// Create reader object
			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
			$objReader->setReadDataOnly(true);
			
			// Load spreadsheet
			$objPHPExcel = $objReader->load($path);
			$worksheet = $objPHPExcel->getSheet(0);
			
			// Columns that first and last headers are stored in
			$startingColumn = 0;
			$endingColumn = 0;
			foreach ($worksheet->getRowIterator() as $row) {
				$line = array();
				$lineNumber = $row->getRowIndex();
				$cellIterator = $row->getCellIterator();
				$cellIterator->setIterateOnlyExistingCells(false);
				$started = false;
				$dataCount = 0;
				$thisIsKeyLine = (!$keyLine && !$header) || ($keyLine && $keyLine == $lineNumber);
				$warning = false;
				$error = false;
				foreach ($cellIterator as $cell) {
					$value = $cell->getCalculatedValue();
					$columnIndex = PHPExcel_Cell::columnIndexFromString($cell->getColumn());
					// Check for blank rows
					if (!empty($value) && !$started) {
						$started = true;
					}
					// Set header columns if not set
					if ($thisIsKeyLine) {
						if (!is_null($value) && !$startingColumn) {
							$startingColumn = $endingColumn = $columnIndex;
							// Set column headers
							self::generateFieldHeaders($box, 'excel');
						}
						if ($startingColumn) {
							if (empty($value)) {
								break;
							}
							// Include headers in CSV preview
							$line[] = $value;
							if ($startingColumn != $columnIndex) {
								$endingColumn++;
							}
							// Attempt to autoset db_columns on fields tab
							$fieldId = array_search(strtolower($value), $datasetColumns);
							if ($value == 'id') {
								$fieldId = $value;
							}
							// Fill row field link
							if (isset($currentMatchedFields['database_column__'.$value]) && !empty($currentMatchedFields['database_column__'.$value])) {
								$rowFieldIdLink[$dataCount] = $currentMatchedFields['database_column__'.$value];
							}
							
							// Get key field index
							if ($update && ($value == $values['headers/update_key_field'])) {
								$IDColumnIndex = $dataCount;
							}
							
							// Set columns table
							self::generateFieldRow($box, $dataCount, $value, $currentMatchedFields, $fieldId);
							if ($update) {
								$fields['headers/update_key_field']['values'][$value] = array(
									'label' => $value,
									'ord' => $dataCount
								);
							}
							$dataCount++;
						}
					} elseif ($header && ($columnIndex >= $startingColumn) && ($columnIndex <= $endingColumn)) {
						
						// Field errors
						/*
						$dataCount++;
						if (!empty($value) && isset($rowFieldIdLink[$dataCount]) && 
							!self::validateImportValue($problems, $datasetDetails['system_table'], $datasetFieldDetails, $rowFieldIdLink[$dataCount], $dataCount, $value, $lineNumber)) {
							$error = true;
						}
						*/
						// Make CSV of line for preview
						$line[] = $value;
					}
				}
				
				if ($started) {
					if ($thisIsKeyLine) {
						$keyLine = $lineNumber;
						$header = true;
						if ($box['tab'] == 'headers') {
							break;
						}
						if ($IDColumnIndex !== false) {
							$box['key']['ID_column'] = $rowFieldIdLink[$IDColumnIndex];
						}
					} elseif ($header) {
						// Validate import line
						$lineProblems = '';
						$lineErrorCount = self::validateImportLine($lineProblems, $datasetDetails, $datasetFieldDetails, $rowFieldIdLink, $line, $lineNumber, $IDColumnIndex, $update, $values['headers/insert_options'], $updateCount);
						$problems .= $lineProblems;
						$error = ($lineErrorCount > 0);
						
						// Record lines with warnings and errors
						if ($error) {
							$errorCount++;
							$errorLines[] = $lineNumber;
						} elseif ($warning) {
							$warningCount++;
							$warningLines[] = $lineNumber;
						}
						if ($warning && $error) {
							$warningCount++;
						}
					}
				}
				if (!$header || !$started) {
					$blankLines[] = $lineNumber;
				}
				if ($lineNumber <= $previewLinesLimit) {
					foreach ($line as $key => $value) {
						if (strpos($value, ',') !== false) {
							$value = '"'.$value.'"';
						}
						$filePreviewString .= $value.', ';
					}
					$filePreviewString = rtrim($filePreviewString, ', ');
					$filePreviewString .= "\n";
				}
			}
		}
		
		// Try to autoset keyline field
		if (!$values['headers/key_line'] || $newFileUploaded) {
			$values['headers/key_line'] = $keyLine;
		}
		
		// Set preview text
		$values['preview/csv_preview'] = $filePreviewString;
		
		// Set preview errors text
		$totalLines = $lineNumber;
		$totalWarnings = $warningCount;
		$totalErrors = $errorCount;
		$totalBlanks = count($blankLines);
		
		$fields['preview/total_readable_lines']['snippet']['html'] = '<b>Total readable lines:</b> '. ($totalLines - $totalErrors - $totalBlanks - 1);
		$plural = ($totalErrors == 1) ? '' : 's';
		$errorsText = $totalErrors. ' error'.$plural;
		$plural = ($totalWarnings == 1) ? '' : 's';
		$warningsText = $totalWarnings. ' warning'.$plural;
		$fields['preview/problems']['label'] = 'Problems ('.$errorsText.', '.$warningsText.'):';
		$values['preview/problems'] = $problems;
		
		$fields['preview/error_options']['hidden'] = $fields['preview/desc2']['hidden'] = (count($warningLines) == 0);
		
		$effectedRecords = $totalLines - $totalErrors - $totalBlanks - 1 - $updateCount;
		
		if ($values['preview/error_options'] == 'skip_warning_lines') {
			$effectedRecords -= $totalWarnings;
		}
		
		// Record warning lines for when saving data
		$box['key']['warning_lines'] = implode(',', $warningLines);
		$box['key']['error_lines'] = implode(',', $errorLines);
		$box['key']['blank_lines'] = implode(',', $blankLines);
		
		
		// Set step 4 update/insert statement
		$plural = ($effectedRecords == 1) ? '' : 's';
		if ($values['file/type'] == 'insert_data') {
			$recordStatement = '<b>'.$effectedRecords. '</b> new record'.$plural.' will be created.';
			$box['key']['new_records'] = $effectedRecords;
		} else {
			$recordStatement = '<b>'.$effectedRecords. '</b> record'.$plural.' will be updated.';
		}
		if ($updateCount) {
			$plural = ($updateCount == 1) ? '' : 's';
			$recordStatement .= ' <b>'.$updateCount.'</b> record'.$plural.' will be updated.';
		}
		$fields['actions/records_statement']['snippet']['html'] = $recordStatement;
		
		
		
		
		
		if ($box['tab'] == 'actions') {
			$userImport = ($datasetDetails['extends_organizer_panel'] == 'zenario__users/panels/users');
			
			// Remove previously generated fields
			foreach($box['tabs']['actions']['fields'] as $name => $field) {
				if (!in_array($name, array('records_statement', 'email_report', 'line_break', 'previous', 'send_welcome_email', 'email_to_send'))) {
					unset($box['tabs']['actions']['fields'][$name]);
				}
			}
			
			// Remove fields set in step 2
			foreach ($rowFieldIdLink as $index => $fieldId) {
				unset($datasetFieldDetails[$fieldId]);
			}
			
			// Create a field for each unset dataset field
			$ord = 1;
			foreach ($datasetFieldDetails as $fieldId => $datasetField) {
				// Hide certain fields when importing users
				if ($userImport) {
					if ($datasetField['is_system_field'] && $datasetField['db_column'] === 'screen_name_confirmed') {
						continue;
					}
				}
				
				// Create field value picker TUIX
				$ord++;
				$valueFieldName = 'value__'.$fieldId;
				$fieldValuePicker = array(
					'ord' => $ord + 500.5,
					'same_row' => true,
					'post_field_html' => '<br/>',
					'type' => 'text',
					'style' => 'width: 20em;'
				);
				if (!empty($values[$valueFieldName])) {
					$fieldValuePicker['value'] = $values[$valueFieldName];
				}
				$valuesArray = false;
				switch ($datasetField['type']) {
					case 'group':
					case 'checkbox':
						$fieldValuePicker['type'] = 'checkbox';
						break;
					case 'date':
						$fieldValuePicker['type'] = 'date';
						break;
					case 'checkboxes':
						$fieldValuePicker['readonly'] = true;
						$fieldValuePicker['value'] = '"Multi-Checkboxes" cannot be imported';
					case 'editor':
					case 'textarea':
					case 'url':
						$fieldValuePicker['type'] = 'text';
						$fieldValuePicker['maxlength'] = 255;
						break;
					case 'text':
						
						// If importing users don't show system text fields for auto complete list
						if (($datasetDetails['system_table'] == 'users') && $datasetField['is_system_field']) {
							continue 2;
						}
						
						$fieldValuePicker['type'] = 'text';
						$fieldValuePicker['maxlength'] = 255;
						break;
					case 'radios':
					case 'select':
					case 'centralised_radios':
					case 'centralised_select':
						$valuesArray = getDatasetFieldLOV($datasetField);
						$fieldValuePicker['type'] = 'select';
						if ($userImport && $datasetField['db_column'] == 'status') {
							$fieldValuePicker['empty_value'] = "-- Select --";
							$fieldValuePicker['format_onchange'] = true;
							
							$fields['actions/send_welcome_email']['hidden'] = ($values['file/type'] != 'insert_data') || !isset($values['actions/' . $valueFieldName]) || ($values['actions/' . $valueFieldName] != 'active');
							$fields['actions/email_to_send']['hidden'] = ($fields['actions/send_welcome_email']['hidden'] || !$values['actions/send_welcome_email']);
						} else {
							$fieldValuePicker['empty_value'] = "-- Don't import --";
						}
						$fieldValuePicker['values'] = $valuesArray;
						break;
				}
				
				// Add dataset field validation
				$validationArray = false;
				switch ($datasetField['validation']) {
					case 'email':
						$validationArray = array('email' => '"'.$datasetField['db_column'].'" is in incorrect format for email');
						break;
					case 'emails':
						$validationArray = array('emails' => '"'.$datasetField['db_column'].'" is in incorrect format for emails');
						break;
					case 'no_spaces':
						$validationArray = array('no_spaces' => '"'.$datasetField['db_column'].'" cannot contain spaces');
						break;
					case 'numeric':
						$validationArray = array('numeric' => '"'.$datasetField['db_column'].'" must be numeric');
						break;
					case 'screen_name':
						$validationArray = array('screen_name' => '"'.$datasetField['db_column'].'" is an invalid screen name');
						break;
				}
				if ($validationArray) {
					$fieldValuePicker['validation'] = $validationArray;
				}
				
				// Set field label and value picker
				$box['tabs']['actions']['fields']['label__'.$fieldId] = array(
					'ord' => $ord + 500,
					'same_row' => true,
					'readonly' => true,
					'type' => 'text',
					'value' => $datasetField['db_column'],
					'style' => 'width: 15em;');
				$box['tabs']['actions']['fields'][$valueFieldName] = $fieldValuePicker;
			}
		}
		
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$datasetDetails = getDatasetDetails($box['key']['dataset']);
		
		// Include modules if needed
		switch ($datasetDetails['extends_organizer_panel']) {
			case 'zenario__locations/panel':
				inc('zenario_location_manager');
				break;
		}
		
		// Get link between fields IDs and column index
		$keyValues = array();
		$dataCount = 0;
		$firstNameFieldDetails = $lastNameFieldDetails = false;
		foreach ($box['tabs']['headers']['fields'] as $fieldName => $field) {
			if (isset($field['type']) && ($field['type'] == 'select') && chopPrefixOffString('database_column__', $fieldName)) {
				if (!empty($field['value'])) {
					
					// Look for any custom columns that have been set
					if (($field['value'] == 'name_split_on_first_space' || $field['value'] == 'name_split_on_last_space') && !$firstNameFieldDetails && !$lastNameFieldDetails) {
						$firstNameFieldDetails = getDatasetFieldDetails('first_name', $datasetDetails);
						$lastNameFieldDetails = getDatasetFieldDetails('last_name', $datasetDetails);
					}
					
					$keyValues[$dataCount] = $field['value'];
				}
				$dataCount++;
			}
		}
		
		// Get the details of dataset fields
		$datasetFieldDetails = self::getAllDatasetFieldDetails($box['key']['dataset']);
		
		// Get the values of fields set in step 4 which are the same for all values
		$constantValues = array();
		foreach ($datasetFieldDetails as $fieldId => $fieldDetails) {
			$fieldName = 'actions/value__'.$fieldId;
			if (isset($values[$fieldName]) && ($values[$fieldName] != '') && ($fieldDetails['type'] != 'checkboxes')) {
				$constantValues[$fieldId] = $values[$fieldName];
			}
		}
		
		// Load lines to ignore
		$errorLines = $box['key']['error_lines'] ? explode(',', $box['key']['error_lines']) : array();
		$blankLines = $box['key']['blank_lines'] ? explode(',', $box['key']['blank_lines']) : array();
		$warningLines = array();
		if ($values['preview/error_options'] == 'skip_warning_lines') {
			$warningLines = $box['key']['warning_lines'] ? explode(',', $box['key']['warning_lines']) : array();
		}
		$linesToSkip = array_merge($errorLines, $blankLines, $warningLines);
		
		
		$keyLine = $values['headers/key_line'];
		$unexpectedErrors = array();
		if ($file = $values['file/file']) {
			$path = getPathOfUploadedFileInCacheDir($file);
			$mode = ($values['file/type'] == 'insert_data') ? 'insert' : 'update';
			$importValues = array();
			if (pathinfo($path, PATHINFO_EXTENSION) == 'csv') {
				ini_set('auto_detect_line_endings', true);
				$f = fopen($path, 'r');
				$lineNumber = 0;
				while ($line = fgets($f)) {
					$lineNumber++;
					
					// Skip key line and any with errors
					if (in_array($lineNumber, $linesToSkip) || $lineNumber == $keyLine) {
						continue;
					}
					
					// Add step 4 values
					$importValues[$lineNumber] = $constantValues;
					
					$data = str_getcsv($line);
					for ($dataCount = 0; $dataCount < count($data); $dataCount++) {
						if (isset($keyValues[$dataCount])) {
							$data[$dataCount] = trim($data[$dataCount]);
							
							// Add special cases
							if ($keyValues[$dataCount] == 'name_split_on_first_space') {
								if (($pos = strpos($data[$dataCount], ' ')) !== false) {
									$importValues[$lineNumber][$firstNameFieldDetails['id']] = substr($data[$dataCount], 0, $pos);
									$importValues[$lineNumber][$lastNameFieldDetails['id']] =  substr($data[$dataCount], $pos + 1);
								} else {
									$importValues[$lineNumber][$firstNameFieldDetails['id']] = $data[$dataCount];
								}
								
							} elseif ($keyValues[$dataCount] == 'name_split_on_last_space') {
								if (($pos = strrpos($data[$dataCount], ' ')) !== false) {
									$importValues[$lineNumber][$firstNameFieldDetails['id']] = substr($data[$dataCount], 0, $pos);
									$importValues[$lineNumber][$lastNameFieldDetails['id']] = substr($data[$dataCount], $pos + 1);
								} else {
									$importValues[$lineNumber][$firstNameFieldDetails['id']] = $data[$dataCount];
								}
							
							// Add normal data
							} else {
								$importValues[$lineNumber][$keyValues[$dataCount]] = $data[$dataCount];
							}
						}
					}
				}
				
			} else {
				require_once CMS_ROOT.'zenario/libraries/lgpl/PHPExcel/Classes/PHPExcel.php';
				$inputFileType = PHPExcel_IOFactory::identify($path);
				$objReader = PHPExcel_IOFactory::createReader($inputFileType);
				$objReader->setReadDataOnly(true);
				$objPHPExcel = $objReader->load($path);
				$worksheet = $objPHPExcel->getSheet(0);
				$startingColumn = 0;
				$endingColumn = 0;
				foreach ($worksheet->getRowIterator() as $row) {
					$lineNumber = $row->getRowIndex();
					// Skip errors, blanks and/or warning lines
					if (in_array($lineNumber, $linesToSkip)) {
						continue;
					}
					$dataCount = 0;
					// Get the list of matched column headers and db_columns
					$cellIterator = $row->getCellIterator();
					$cellIterator->setIterateOnlyExistingCells(false);
					// Set constant values
					if ($lineNumber > $keyLine) {
						$importValues[$lineNumber] = $constantValues;
					}
					foreach ($cellIterator as $cell) {
						$value = $cell->getCalculatedValue();
						$columnIndex = PHPExcel_Cell::columnIndexFromString($cell->getColumn());
						if ($lineNumber == $keyLine) {
							if (!is_null($value) && !$startingColumn) {
								$startingColumn = $endingColumn = $columnIndex;
							}
							if ($startingColumn) {
								if (empty($value)) {
									break;
								}
								if ($startingColumn != $columnIndex) {
									$endingColumn++;
								}
							}
						} else {
							if (($columnIndex >= $startingColumn) && ($columnIndex <= $endingColumn)) {
								if (!is_null($value) && isset($keyValues[$dataCount])) {
									
									// Add special cases
									if ($keyValues[$dataCount] == 'name_split_on_first_space') {
										if (($pos = strpos(trim($value), ' ')) !== false) {
											$importValues[$lineNumber][$firstNameFieldDetails['id']] = substr(trim($value), 0, $pos);
											$importValues[$lineNumber][$lastNameFieldDetails['id']] =  substr(trim($value), $pos + 1);
										} else {
											$importValues[$lineNumber][$firstNameFieldDetails['id']] = trim($value);
										}
										
									} elseif ($keyValues[$dataCount] == 'name_split_on_last_space') {
										if (($pos = strrpos(trim($value), ' ')) !== false) {
											$importValues[$lineNumber][$firstNameFieldDetails['id']] = substr(trim($value), 0, $pos);
											$importValues[$lineNumber][$lastNameFieldDetails['id']] = substr(trim($value), $pos + 1);
										} else {
											$importValues[$lineNumber][$firstNameFieldDetails['id']] = trim($value);
										}
										
									// Add normal data
									} else {
										$importValues[$lineNumber][$keyValues[$dataCount]] = trim($value);
									}
								}
								$dataCount++;
							}
						}
					}
				}
			}
			// Import data
			$unexpectedErrors = self::setImportData($values, $box['key']['dataset'], $importValues, $mode, $values['headers/insert_options'], $box['key']['ID_column']);
			
		}
		
		
		// Send report email
		if ($values['actions/email_report']) {
			$adminDetails = getAdminDetails(adminId());
			$path = getPathOfUploadedFileInCacheDir($values['file/file']);
			$filename = pathinfo($path, PATHINFO_BASENAME);
			$createOrUpdate = 'create';
			if ($values['file/type'] == 'update_data') {
				$createOrUpdate = 'update';
			}
			$body = "Import settings \n\n";
			$body .= 'File: '.$filename."\n";
			$body .= 'Mode: '.$createOrUpdate."\n";
			$body .= 'Key line: '.$values['headers/key_line']."\n";
			$body .= strip_tags($fields['actions/records_statement']['snippet']['html'])."\n\n";
			$body .= "Error log: \n\n";
			$errorLog = ($values['preview/problems'] ? $values['preview/problems'] : 'No errors or warnings');
			$body .= $errorLog;
			/*
			if ($unexpectedErrors) {
				$body .= "\n\nUnexpected Errors:\n\n";
				$body .= $unexpectedErrors;
			}
			*/
			sendEmail('Dataset Import Report', $body, $adminDetails['email'], $addressToOverriddenBy, false, false, false, array(), array(), 'bulk', false);
		}
	}
	
	
	
	
	
	
	private static function isGeneratedField($name, $field) {
		return !in_array($name, array('key_line', 'desc', 'desc2', 'insert_desc', 'insert_options', 'update_desc', 'update_key_field', 'next', 'previous'));
	}
	
	private static $step2FieldWidth = 20;
	private static function generateFieldHeaders(&$box, $type) {
		$value = 'Field names';
		if ($type === 'csv') {
			$value .= ' (from CSV file)';
		} else {
			$value .= ' (from spreadsheet)';
		}
		
		$box['tabs']['headers']['fields']['file_column_headers'] = array(
			'ord' => 3,
			'snippet' => array(
				'html' => '
					<div style="width:' . (self::$step2FieldWidth + 1) . 'em;float:left;"><b>' . $value . '</b></div>
					<div><b>Database columns</b></div>
				'
			),
			'post_field_html' => '<br/>'
		);
	}
	
	private static function generateFieldRow(&$box, $ord, $value, $currentMatchedFields, $fieldId) {
		$databaseColumnName = 'database_column__'.$value;
		if (isset($currentMatchedFields[$databaseColumnName])) {
			$fieldId = $currentMatchedFields[$databaseColumnName];
		}
		$box['tabs']['headers']['fields']['file_column__'.$value] = array(
			'ord' => $ord + 500,
			'same_row' => true,
			'readonly' => true,
			'type' => 'text',
			'value' => $value,
			'style' => 'width: ' . self::$step2FieldWidth . 'em;'
		);
		$box['tabs']['headers']['fields'][$databaseColumnName] = array(
			'ord' => $ord + 500.5,
			'same_row' => true,
			'post_field_html' => '<br/>',
			'type' => 'select',
			'empty_value' => "-- Don't import --",
			'values' => 'dataset_fields',
			'value' => $fieldId
		);
	}
	
	
	private static $ids = array();
	private static $emails = array();
	private static $screenNames = array();
	private static $systemDataIDColumn = false;
	
	
	private static function validateImportLine(&$problems, $datasetDetails, $datasetFieldDetails, $rowFieldIdLink, $lineValues, $lineNumber, $IDColumnIndex, $update, $insertOption, &$updateCount) {
		
		$userSystemFields = array();
		$DBColumnValueIndexLink = array();
		$errorCount = 0;
		
		$userImport = ($datasetDetails['extends_organizer_panel'] == 'zenario__users/panels/users');
		$mergeOrOverwriteRow = false;
		
		foreach ($rowFieldIdLink as $ord => $fieldId) {
			$field = false;
			$columnIndex = $ord + 1;
			
			if (isset($datasetFieldDetails[$fieldId])) {
				$field = $datasetFieldDetails[$fieldId];
				$DBColumnValueIndexLink[$field['db_column']] = $columnIndex;
			}
			
			// Validate fields
			if ($field && $field['db_column'] && isset($lineValues[$ord])) {
				
				$value = trim($lineValues[$ord]);
				
				// If updating, ensure there is a matching field, and not multiple entries
				if (($IDColumnIndex !== false) && ($ord == $IDColumnIndex)) {
					if ($value === '') {
						$errorMessage = 'ID field is blank';
						self::addErrorMessage($problems, $errorCount, $errorMessage, $lineNumber, $columnIndex);
					} else {
						// Record duplicates of chosen ID field in import
						if ($errorLines = self::recordUniqueImportValue(self::$ids, $value, $lineNumber)) {
							$errorMessage = 'More than one line in the file has a matching ID column ('.implode(', ',$errorLines).')';
							self::addErrorMessage($problems, $errorCount, $errorMessage, $lineNumber, $columnIndex);
						}
						
						// Find matching records to update by DB Column
						$matchingRows = array();
						if ($field['is_system_field']) {
							$matchingRows = getRows($datasetDetails['system_table'], $field['db_column'], array($field['db_column'] => $value));
						} else {
							$matchingRows = getRows($datasetDetails['table'], $field['db_column'], array($field['db_column'] => $value));
						}
						
						$rowCount = sqlNumRows($matchingRows);
						if ($rowCount == 0) {
							$errorMessage = 'No existing record found for ID column '.$field['db_column'];
							self::addErrorMessage($problems, $errorCount, $errorMessage, $lineNumber, $columnIndex);
						} elseif ($rowCount > 1) {
							// TODO this should be a warning, not an error.
							$errorMessage = 'More than one existing record found for ID column '.$field['db_column'];
							self::addErrorMessage($problems, $errorCount, $errorMessage, $lineNumber, $columnIndex);
						}
					}
				}
				
				// Custom users validation
				if ($userImport && $field['is_system_field']) {
					$userSystemFields[$field['db_column']] = $value;
					if (($field['db_column'] == 'email') && ($value !== '')) {
						if ($errorLines = self::recordUniqueImportValue(self::$emails, $value, $lineNumber)) {
							$errorMessage = 'More than one line in the file has the same email address ('.implode(', ',$errorLines).')';
							self::addErrorMessage($problems, $errorCount, $errorMessage, $lineNumber, $columnIndex);
						} else {
							// Check if this should be merged/overwrite an existing user
							if (!$update && ($insertOption != 'no_update')) {
								$sql = '
									SELECT COUNT(*)
									FROM ' . DB_NAME_PREFIX . 'users
									WHERE email = "' . sqlEscape($value) . '"';
								$result = sqlSelect($sql);
								$row = sqlFetchRow($result);
								$count = $row[0];
								
								if ($count == 1) {
									$mergeOrOverwriteRow = true;
								} elseif ($count > 1) {
									$errorMessage = 'More than one user has the same email address';
									self::addErrorMessage($problems, $errorCount, $errorMessage, $lineNumber, $columnIndex);
								}
							}
						}
					} elseif ($field['db_column'] == 'screen_name') {
						self::recordUniqueImportValue(self::$screenNames, $value, $lineNumber);
					}
				}
				
				// Validate fields with validation rules
				$validationError = false;
				if ($value !== '') {
					switch ($field['validation']) {
						case 'email':
							// Ignore this for users email as it's validated with isInvalidUser()
							if (!($userImport && ($field['db_column'] == 'email'))) {
								if (!validateEmailAddress($value)) {
									$validationError = true;
									if (!$field['validation_message'])  {
										$validationErrorMessages[] = 'Value is in incorrect format for email';
									} else {
										$validationErrorMessages[] = $field['validation_message'];
									}
								}
							}
							break;
						case 'emails':
							if (!validateEmailAddress($value, true)) {
								$validationError = true;
								if (!$field['validation_message']) {
									$validationErrorMessages[] = 'Value is in incorrect format for emails';
								} else {
									$validationErrorMessages[] = $field['validation_message'];
								}
							}
							break;
						case 'no_spaces':
							if (preg_replace('/\S/', '', $value)) {
								$validationError = true;
								if (!$field['validation_message']) {
									$validationErrorMessages[] = 'Value cannot contain spaces';
								} else {
									$validationErrorMessages[] = $field['validation_message'];
								}
							}
							break;
						case 'numeric':
							if (!is_numeric($value)) {
								$validationError = true;
								if (!$field['validation_message']) {
									$validationErrorMessages[] = 'Value must be numeric';
								} else {
									$validationErrorMessages[] = $field['validation_message'];
								}
							}
							break;
						case 'screen_name':
							if (!validateScreenName($value)) {
								$validationError = true;
								if (!$field['validation_message']) {
									$validationErrorMessages[] = 'Screen name is invalid';
								} else {
									$validationErrorMessages[] = $field['validation_message'];
								}
							}
							break;
					}
					if ($validationError) {
						foreach ($validationErrorMessages as $key => $errorMessage) {
							self::addErrorMessage($problems, $errorCount, $errorMessage, $lineNumber, $columnIndex);
						}
					}
				}
				
				// Validate required fields
				if ($field['required'] && ($value === '')) {
					if (!$errorMessage = $field['required_message']) {
						$errorMessage = 'Value is required but missing';
					}
					self::addErrorMessage($problems, $errorCount, $errorMessage, $lineNumber, $columnIndex);
				}
				
				// Validate fields with a values source
				if ($field['values_source']) {
					$lov = getCentralisedListValues($field['values_source']);
					if (!isset($lov[$value])) {
						$cannotImport = true;
						// If this is a centralised list of countries, allow user to enter country names
						if ($field['values_source'] == 'zenario_country_manager::getActiveCountries') {
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
							$errorMessage = 'Unknown list value "' . $displayValue . '"';
							self::addErrorMessage($problems, $errorCount, $errorMessage, $lineNumber, $columnIndex);
						}
					}
				}
				
			
			} elseif (($fieldId == 'id') && isset($lineValues[$ord])) {
				
				if ($value = trim($lineValues[$ord])) {
					if (!self::$systemDataIDColumn) {
						self::$systemDataIDColumn = getIdColumnOfTable($datasetDetails['system_table']);
					}
					$currentRow = getRowsArray($datasetDetails['system_table'], self::$systemDataIDColumn, array(self::$systemDataIDColumn => $value));
					$rowCount = count($currentRow);
					if ($rowCount == 0) {
						$errorMessage = 'No existing record found for ID column '.self::$systemDataIDColumn;
						self::addErrorMessage($problems, $errorCount, $errorMessage, $lineNumber, $columnIndex);
					}
					
					if (($IDColumnIndex !== false) && ($ord == $IDColumnIndex)) {
						if ($value === '') {
							$errorMessage = 'ID field is blank';
							self::addErrorMessage($problems, $errorCount, $errorMessage, $lineNumber, $columnIndex);
						} else {
							// Record duplicates of chosen ID field in import
							if ($errorLines = self::recordUniqueImportValue(self::$ids, $value, $lineNumber)) {
								$errorMessage = 'More than one line in the file has a matching ID column ('.implode(', ',$errorLines).')';
								self::addErrorMessage($problems, $errorCount, $errorMessage, $lineNumber, $columnIndex);
							}
						}
					}
				}
				
			}
		}
		
		if (!$update && $userImport && !$mergeOrOverwriteRow) {
			
			$userErrors = isInvalidUser($userSystemFields);
			if (is_object($userErrors) && $userErrors->errors) {
				
				foreach ($userErrors->errors as $db_column => $errorMessage) {
					$columnIndex = false;
					if (!empty($DBColumnValueIndexLink[$db_column])) {
						$columnIndex = $DBColumnValueIndexLink[$db_column];
					}
					switch ($errorMessage) {
						case '_ERROR_SCREEN_NAME_INVALID':
							$errorMessage = 'Screen name invalid';
							break;
						case '_ERROR_SCREEN_NAME_IN_USE':
							$errorMessage = 'Screen name in use';
							break;
						case '_ERROR_EMAIL_INVALID':
							$errorMessage = 'Value is in incorrect format for email';
							break;
						case '_ERROR_EMAIL_NAME_IN_USE':
							$errorMessage = 'Email in use';
							break;
					}
					self::addErrorMessage($problems, $errorCount, $errorMessage, $lineNumber, $columnIndex);
				}
			}
		}
		
		if ($errorCount == 0 && $mergeOrOverwriteRow) {
			++$updateCount;
		}
		
		return $errorCount;
	}
	
	private static function recordUniqueImportValue(&$array, $value, $lineNumber) {
		if (!isset($array[$value])) {
			$array[$value] = $lineNumber;
		} elseif (!is_array($array[$value])) {
			$array[$value] = array($array[$value], $lineNumber);
			return $array[$value];
		} else {
			$array[$value][] = $lineNumber;
			return $array[$value];
		}
		return false;
	}
	
	private static function addErrorMessage(&$problems, &$errorCount, $errorMessage, $lineNumber, $columnNumber) {
		if ($columnNumber) {
			$message = 'Error (Line [[line]], Value [[value]]): [[message]][[EOL]]';
		} else {
			$message = 'Error (Line [[line]]): [[message]][[EOL]]';
		}
		$problems .= adminPhrase($message, array(
			'line' => $lineNumber,
			'value' => $columnNumber,
			'message' => $errorMessage,
			'EOL' => PHP_EOL
		));
		++$errorCount;
	}
	
	private static function getAllDatasetFieldDetails($dataset) {
		$datasetFieldDetails = array();
		$sql = '
			SELECT 
				f.id, 
				f.is_system_field,
				f.db_column, 
				f.validation, 
				f.validation_message, 
				f.required, 
				f.required_message, 
				f.type, 
				f.values_source, 
				f.parent_id,
				f.is_system_field
			FROM '.DB_NAME_PREFIX.'custom_dataset_fields f
			INNER JOIN '.DB_NAME_PREFIX.'custom_dataset_tabs t
				ON (f.dataset_id = t.dataset_id) AND (f.tab_name = t.name)
			WHERE f.dataset_id = '.(int)$dataset. '
			AND f.db_column != ""
			ORDER BY t.ord, f.ord';
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			$datasetFieldDetails[$row['id']] = $row;
		}
		return $datasetFieldDetails;
	}
	
	private static function setImportData($values, $datasetId, $importData, $mode, $insertMode, $keyFieldID) {
		$datasetDetails = getDatasetDetails($datasetId);
		$systemDataIDColumn = !empty($datasetDetails['system_table']) ? getIdColumnOfTable($datasetDetails['system_table']) : false;
		$customDataIDColumn = !empty($datasetDetails['table']) ? getIdColumnOfTable($datasetDetails['table']) : false;
		
		$fieldIdDetails = array();
		$errorMessage = '';
		
		$countryList = array();
		
		foreach ($importData as $i => $record) {
			$error = false;
			$message = 'Line: '.($i+1)."\n";
			
			// Sort data into custom and non-custom
			$customData = array();
			$data = array();
			$id = false;
			
			foreach($record as $fieldId => $value) {
				if ($fieldId) {
					if ($fieldId == 'id') {
						//
					} else {
						if (!isset($fieldIdDetails[$fieldId])) {
							$fieldIdDetails[$fieldId] = getRow('custom_dataset_fields', array('is_system_field', 'db_column', 'values_source'), $fieldId);
						}
						
						if ($fieldIdDetails[$fieldId]['values_source'] && $fieldIdDetails[$fieldId]['values_source'] == 'zenario_country_manager::getActiveCountries') {
							if (!$countryList) {
								$countryList = getCentralisedListValues($fieldIdDetails[$fieldId]['values_source']);
								$countryList = array_map('strtolower', $countryList);
							}
							if (!isset($countryList[$value])) {
								$value = array_search(strtolower($value), $countryList);
							}
						}
						
						if ($fieldIdDetails[$fieldId]['is_system_field']) {
							$data[$fieldIdDetails[$fieldId]['db_column']] = $value;
						} else {
							$customData[$fieldIdDetails[$fieldId]['db_column']] = $value;
						}
						$message .= $fieldIdDetails[$fieldId]['db_column'].': '.$value. "\n";
					}
				}
			}
			
			
			// Create or update records
			if ($mode == 'insert') {
				
				// Custom logic to save users
				if ($datasetDetails['extends_organizer_panel'] == 'zenario__users/panels/users') {
					
					$userId = false;
					if (!empty($data['email'])) {
						$userId = getRow($datasetDetails['system_table'], $systemDataIDColumn, array('email' => $data['email']));
					}
					
					// Attempt to update fields on email
					if ($insertMode != 'no_update') {
						if ($userId) {
							// Overwrite data
							if ($insertMode == 'overwrite') {
								saveUser($data, $userId);
								setRow($datasetDetails['table'], $customData, $userId);
							
							// Merge data (only update blank fields)
							} elseif ($insertMode == 'merge') {
								$systemKeys = array_keys($data);
								if ($systemKeys) {
									$foundData = getRow($datasetDetails['system_table'], $systemKeys, $userId);
									foreach ($foundData as $col => $val) {
										if ($val) {
											unset($data[$col]);
										}
									}
									saveUser($data, $userId);
								}
								
								$customkeys = array_keys($customData);
								if ($customkeys) {
									$foundData = getRow($datasetDetails['table'], $customkeys, $userId);
									foreach ($foundData as $col => $val) {
										if ($val) {
											unset($customData[$col]);
										}
									}
									setRow($datasetDetails['table'], $customData, $userId);
								}
							}
							continue;
						}
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
					if (!$userId && !empty($data['status'])) {
						if ($data['status'] != 'contact' && empty($data['password'])) {
							$data['password'] = createPassword();
						}
						if ($data['status'] == 'active' && $values['actions/send_welcome_email'] && $values['actions/email_to_send']) {
							// Send a welcome email
							$sendWelcomeEmail = true;
						}
					}
					
					// Do not allow screen names to be imported to sites that don't use screen names
					if (!setting('user_use_screen_name')) {
						unset($data['screen_name']);
					}
					
					$id = saveUser($data);
					
					if ($sendWelcomeEmail && !empty($data['email'])) {
						$mergeFields = $data;
						$mergeFields['cms_url'] = absCMSDirURL();
						zenario_email_template_manager::sendEmailsUsingTemplate($data['email'], $values['actions/email_to_send'], $mergeFields);
					}
					
					// If site uses screen names and no screen name is imported, use the identifier as a screen name
					if (empty($data['screen_name']) && setting('user_use_screen_name')) {
						$identifier = getUserIdentifier($id);
						updateRow('users', ['screen_name' => $identifier], $id);
					}
				
				// Custom logic to save locations
				} elseif ($datasetDetails['extends_organizer_panel'] == 'zenario__locations/panel') {
					$data['last_updated_via_import'] = now();
					$id = zenario_location_manager::createLocation($data);
				
				// Other datasets
				} else {
					if ($datasetDetails['system_table']) {
						$id = insertRow($datasetDetails['system_table'], $data);
					}
				}
				
				// Add custom data
				if ($datasetDetails['table']) {
					$ids = array();
					if ($id) {
						$ids[$customDataIDColumn] = $id;
					}
					$id = setRow($datasetDetails['table'], $customData, $ids);
				}
				
				if (is_object($id) && get_class($id) == 'zenario_error') {
					foreach ($id->errors as $errorField => $error) {
						$message .= 'Error code: '. phrase($error);
					}
					$error = true;
				}
				
				$message .= "\n\n";
			
			// Update records
			} elseif ($mode == 'update') {
				
				// List of IDs to update (just for saftey, should normaly only be 1)
				$idsToUpdate = array();
				
				if ($keyFieldID == 'id') {
					$idsToUpdate[] = $record['id'];
				} else {
					if (!empty($fieldIdDetails[$keyFieldID]['is_system_field'])) {
						$idsToUpdate = getRowsArray(
							$datasetDetails['system_table'], 
							$systemDataIDColumn, 
							array($fieldIdDetails[$keyFieldID]['db_column'] => $data[$fieldIdDetails[$keyFieldID]['db_column']])
						);
					} else {
						$idsToUpdate = getRowsArray(
							$datasetDetails['table'], 
							$customDataIDColumn, 
							array($fieldIdDetails[$keyFieldID]['db_column'] => $customData[$fieldIdDetails[$keyFieldID]['db_column']])
						);
					}
				}
				
				// Custom logic to update users
				if ($datasetDetails['extends_organizer_panel'] == 'zenario__users/panels/users') {
					if (!setting('user_use_screen_name')) {
						unset($data['screen_name']);
					}
					$data['modified_date'] = now();
					
				} elseif ($datasetDetails['extends_organizer_panel'] == 'zenario__locations/panel') {
					$data['last_updated_via_import'] = now();
				}
				
				// Update records
				if (!empty($idsToUpdate)) {
					foreach ($idsToUpdate as $recordId) {
						
						if ($datasetDetails['system_table'] && !empty($data)) {
							if ($datasetDetails['extends_organizer_panel'] == 'zenario__users/panels/users') {
								saveUser($data, $recordId);
							} else {
								updateRow($datasetDetails['system_table'], $data, array($systemDataIDColumn => $recordId));
							}
						}
						if ($datasetDetails['table'] && !empty($customData)) {
							setRow($datasetDetails['table'], $customData, array($customDataIDColumn => $recordId));
						}
					}
				}
			}
			if ($error) {
				$errorMessage .= $message;
			}
		}
		return $errorMessage;
	}
}
