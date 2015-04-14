<?php
/*
 * Copyright (c) 2015, Tribal Limited
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
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$step = 1;
		$fieldsNextPressed = false;
		$previewNextPressed = false;
		// Handle navigation
		if (empty($box['errors'])) {
			switch ($box['tab']) {
				case 'file':
					if (!empty($box['tabs']['file']['fields']['next']['pressed']) && $values['file/file']) {
						$step = 2;
						$box['tab'] = 'headers';
					}
					break;
				case 'headers':
					if (!empty($box['tabs']['headers']['fields']['previous']['pressed'])) {
						$step = 1;
						$box['tab'] = 'file';
					} elseif (!empty($box['tabs']['headers']['fields']['next']['pressed'])) {
						$step = 3;
						$fieldsNextPressed = true;
						$box['tab'] = 'preview';
					} else {
						$step = 2;
					}
					if (!$values['headers/key_line']) {
						$step = 2;
					}
					break;
				case 'preview':
					if (!empty($box['tabs']['preview']['fields']['previous']['pressed'])) {
						$step = 2;
						$box['tab'] = 'headers';
					} elseif (!empty($box['tabs']['preview']['fields']['next']['pressed'])) {
						$step = 4;
						$box['tab'] = 'actions';
						$previewNextPressed = true;
					} 
					break;
				case 'actions':
					if (!empty($box['tabs']['actions']['fields']['previous']['pressed'])) {
						$step = 3;
						$box['tab'] = 'preview';
					} else {
						$step = 4;
					}
					break;
			}
		}
		
		if ($box['tab'] == 'actions') {
			$box['css_class'] = '';
		} else {
			$box['css_class'] = 'zab_hide_save_button';
		}
		
		$datasetId = $box['key']['dataset'];
		
		$datasetDetails = getDatasetDetails($datasetId);
		if ($file = $values['file/file']) {
			$path = getPathOfUploadedFileInCacheDir($file);
			$datasetValueDetails = self::getDatasetValueDetails($datasetId);
			
			$datasetColumns = array();
			$orderedFields = array();
			$requiredFields = array();
			$childFields = array();
			// Show an ID field for updates
			if ($values['file/type'] == 'update_data') {
				$orderedFields['id'] = array('ord' => 0, 'label' => 'ID Column');
			}
			// Show special fields for users
			$datasetDetails = getDatasetDetails($box['key']['dataset']);
			if ($datasetDetails['system_table'] == 'users') {
				$orderedFields['name_split_on_first_space'] = array('ord' => 1, 'label' => 'Name -> First Name, Last Name, split on first space');
				$orderedFields['name_split_on_last_space'] = array('ord' => 2, 'label' => 'Name -> First Name, Last Name, split on last space');
			}
			$i = 10;
			foreach ($datasetValueDetails as $fieldId => $details) {
				$datasetColumns[$fieldId] = $details['db_column'];
				$orderedFields[$fieldId] = array('ord' => $i++, 'label' => $details['db_column']);
				if ($details['required']) {
					$requiredFields[$fieldId] = false;
				}
				if ($details['parent_id']) {
					$childFields[$fieldId] = $details['parent_id'];
				}
			}
			
			$newFileUploaded = ($path != $box['key']['file_path']);
			
			$keyLine = ($values['headers/key_line'] && !$newFileUploaded) ? $values['headers/key_line'] : 0;
			$idColumnSet = false;
			$header = false;
			$headerCount = 0;
			
			$previewLinesLimit = 200;
			$filePreviewString = '';
			$problems = '';
			// Track error number and lines with errors
			$warningCount = 0;
			$errorCount = 0;
			$warningLines = array();
			$errorLines = array();
			$blankLines = array();
			
			// Clear old generated fields from fields tab
			$noFieldsMatched = true;
			$requiredFieldMissing = false;
			$currentMatchedFields = array();
			$foundChildFields = array();
			foreach ($box['tabs']['headers']['fields'] as $name => $field) {
				if (!in_array($name, array('key_line', 'desc', 'desc2', 'next', 'previous'))) {
					if ($fields['headers/'.$name]['type'] == 'select') {
						$selectListFieldId = $values['headers/'.$name];
						if ($selectListFieldId) {
							$noFieldsMatched = false;
						}
						$currentMatchedFields[$name] = $selectListFieldId;
						if ($selectListFieldId == 'id') {
							$idColumnSet = true;
						}
						if (isset($requiredFields[$selectListFieldId])) {
							$requiredFields[$selectListFieldId] = true;
						}
						// Find if a child field is included
						if (isset($childFields[$selectListFieldId])) {
							$foundChildFields[$selectListFieldId] = true;
						}
					}
					unset($box['tabs']['headers']['fields'][$name]);
				}
			}
			
			// Check if each found child fields parent field has been set
			if ($fieldsNextPressed) {
				foreach ($foundChildFields as $childId => $val) {
					if (!in_array($childFields[$childId], $currentMatchedFields)) {
						$box['tabs']['headers']['errors'][] = 'You must include the column "'.$datasetValueDetails[$childFields[$childId]]['db_column'].'" because the column "'.$datasetValueDetails[$childId]['db_column'].'" depends on it.';
						$box['tab'] = 'headers';
						$step = 2;
					}
				}
			}
			
			// If inserting data and a required column is missing from headers
			if ($fieldsNextPressed && ($values['file/type'] == 'insert_data')) {
				$missingRequiredFields = '';
				foreach ($requiredFields as $fieldId => $found) {
					if ($found === false) {
						$missingRequiredFields .= $datasetColumns[$fieldId].', ';
					}
				}
				$missingRequiredFields = rtrim($missingRequiredFields, ', ');
				if ($missingRequiredFields) {
					$box['tab'] = 'headers';
					$box['tabs']['headers']['errors'][] = 'The following required fields are missing: '.$missingRequiredFields;
				}
			}
			// If keyline not selected
			if ($fieldsNextPressed && !$values['headers/key_line']) {
				$step = 2;
				$box['tab'] = 'headers';
				$box['tabs']['headers']['errors'][] = 'Please select the key containing field names';
			// If no fields have been matched
			} elseif ($fieldsNextPressed && $noFieldsMatched) {
				$step = 2;
				$box['tab'] = 'headers';
				$box['tabs']['headers']['errors'][] = 'You need to match at least one field to continue';
			}
			// If no ID column set for update data
			if ($fieldsNextPressed && ($values['file/type'] == 'update_data') && !$idColumnSet) {
				$step = 2;
				$box['tab'] = 'headers';
				$box['tabs']['headers']['errors'][] = 'The ID column must be included to update records';
			}
			
			$box['title'] = 'Dataset Import Wizard - Step '.$step.' of 4';
			
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
										self::generateFieldHeaders($box);
									}
									// Set columns table
									self::generateFieldRow($box, $dataCount, $value, $currentMatchedFields, $orderedFields, $fieldId);
								} elseif ($header) {
									// Field errors
									if (!empty($value) && isset($rowFieldIdLink[$dataCount]) && 
										!self::validateImportValue($problems, $datasetDetails['system_table'], $datasetValueDetails, $rowFieldIdLink[$dataCount], $dataCount, $value, $lineNumber)) {
										$error = true;
									}
								}
							}
							if ($thisIsKeyLine) {
								$header = true;
								if ($box['tab'] == 'file' || $box['tab'] == 'headers') {
									break;
								}
							} elseif ($header) {
								$dataCount = count($lineValues);
								// Line errors
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
				require_once CMS_ROOT.'zenario/libraries/lgpl/PHPExcel_1_7_8/Classes/PHPExcel.php';
				$inputFileType = PHPExcel_IOFactory::identify($path);
				$objReader = PHPExcel_IOFactory::createReader($inputFileType);
				$objReader->setReadDataOnly(true);
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
								self::generateFieldHeaders($box);
							}
							if ($startingColumn) {
								$dataCount++;
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
								// Set columns table
								self::generateFieldRow($box, $dataCount, $value, $currentMatchedFields, $orderedFields, $fieldId);
							}
						} elseif ($header && ($columnIndex >= $startingColumn) && ($columnIndex <= $endingColumn)) {
							$dataCount++;
							// Field errors
							if (!empty($value) && isset($rowFieldIdLink[$dataCount]) && 
								!self::validateImportValue($problems, $datasetDetails['system_table'], $datasetValueDetails, $rowFieldIdLink[$dataCount], $dataCount, $value, $lineNumber)) {
								$error = true;
							}
							// Make CSV of line for preview
							if ($lineNumber <= $previewLinesLimit) {
								$line[] = $value;
							}
						}
					}
					if ($started) {
						if ($thisIsKeyLine) {
							$keyLine = $lineNumber;
							$header = true;
							if ($box['tab'] == 'file' || $box['tab'] == 'headers') {
								break;
							}
						} elseif ($header) {
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
			
			if ($box['tab'] == 'actions' && $previewNextPressed) {
				foreach($box['tabs']['actions']['fields'] as $name => $field) {
					if (!in_array($name, array('records_statement', 'email_report', 'line_break', 'previous'))) {
						unset($box['tabs']['actions']['fields'][$name]);
					}
				}
				foreach ($rowFieldIdLink as $index => $fieldId) {
					unset($datasetValueDetails[$fieldId]);
				}
				$ord = 1;
				foreach ($datasetValueDetails as $fieldId => $datasetField) {
					$ord++;
					$box['tabs']['actions']['fields']['label__'.$fieldId] = array(
						'ord' => $ord + 500,
						'same_row' => true,
						'read_only' => true,
						'type' => 'text',
						'value' => $datasetField['db_column'],
						'style' => 'width: 15em;');
					$valueFieldName = 'value__'.$fieldId;
					$fieldValuePicker = array(
						'ord' => $ord + 500.5,
						'same_row' => true,
						'post_field_html' => '<br/>',
						'type' => 'text',
						'style' => 'width: 20em;');
					if (isset($values[$valueFieldName]) && !empty($values[$valueFieldName])) {
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
						case 'text':
						case 'textarea':
						case 'url':
							$fieldValuePicker['type'] = 'text';
							$fieldValuePicker['maxlength'] = 255;
							break;
						case 'radios':
						case 'select':
						case 'centralised_radios':
						case 'centralised_select':
							$valuesArray = getDatasetFieldLOV($datasetField);
							$fieldValuePicker['type'] = 'select';
							break;
					}
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
					if ($valuesArray) {
						$fieldValuePicker['empty_value'] = '-- Select --';
						$fieldValuePicker['values'] = $valuesArray;
					}
					if ($validationArray) {
						$fieldValuePicker['validation'] = $validationArray;
					}
					$box['tabs']['actions']['fields'][$valueFieldName] = $fieldValuePicker;
				}
			}
			
			$values['preview/csv_preview'] = $filePreviewString;
			
			if (!$values['headers/key_line'] || $newFileUploaded) {
				$values['headers/key_line'] = $keyLine;
			}
			
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
			
			$effectedRecords = $totalLines - $totalErrors - $totalBlanks - 1;
			if ($values['preview/error_options'] == 'skip_warning_lines') {
				$effectedRecords -= $totalWarnings;
			}
			
			$plural = ($effectedRecords == 1) ? '' : 's';
			if ($values['file/type'] == 'insert_data') {
				$recordStatement = '<b>'.$effectedRecords. '</b> new record'.$plural.' will be created.';
			} else {
				$recordStatement = '<b>'.$effectedRecords. '</b> record'.$plural.' will be updated.';
			}
			$fields['actions/records_statement']['snippet']['html'] = $recordStatement;
			
			$box['key']['warning_lines'] = implode(',', $warningLines);
			$box['key']['error_lines'] = implode(',', $errorLines);
			$box['key']['blank_lines'] = implode(',', $blankLines);
			$box['key']['file_path'] = $path;
		}
	}
	
	private static function generateFieldHeaders(&$box) {
		$box['tabs']['headers']['fields']['file_column_headers'] = array(
			'ord' => 3,
			'type' => 'text',
			'same_row' => true,
			'value' => 'Field names',
			'read_only' => true,
			'style' => 'width: 15em;');
		$box['tabs']['headers']['fields']['database_columns'] = array(
			'ord' => 3.5,
			'type' => 'text',
			'same_row' => true,
			'value' => 'Database columns',
			'read_only' => true,
			'post_field_html' => '<br/>',
			'style' => 'width: 15em;');
	}
	
	private static function generateFieldRow(&$box, $ord, $value, $currentMatchedFields, $fields, $fieldId) {
		$databaseColumnName = 'database_column__'.$value;
		if (isset($currentMatchedFields[$databaseColumnName])) {
			$fieldId = $currentMatchedFields[$databaseColumnName];
		}
		$box['tabs']['headers']['fields']['file_column__'.$value] = array(
			'ord' => $ord + 500,
			'same_row' => true,
			'read_only' => true,
			'type' => 'text',
			'value' => $value,
			'style' => 'width: 15em;');
		$box['tabs']['headers']['fields'][$databaseColumnName] = array(
			'ord' => $ord + 500.5,
			'same_row' => true,
			'post_field_html' => '<br/>',
			'type' => 'select',
			'empty_value' => '-- Select --',
			'values' => $fields,
			'value' => $fieldId);
	}
	
	private static $screenNames = array();
	private static $emails = array();
	private static $id = 0;
	private static function validateImportValue(&$problems, $datasetSystemTable, $datasetValueDetails, $fieldId, $dataCount, $value, $lineNumber) {
		
		if ($fieldId == 'id') {
			self::$id = $value;
		}
		$validationErrorMessages = array();
		if (is_numeric($fieldId) && isset($datasetValueDetails[$fieldId])) {
			$fieldDetails = $datasetValueDetails[$fieldId];
			$validationError = false;
			switch ($fieldDetails['validation']) {
				case 'email':
					if (!validateEmailAddress($value)) {
						$validationError = true;
						if (!$fieldDetails['validation_message'])  {
							$validationErrorMessages[] = 'Value is in incorrect format for email';
						} else {
							$validationErrorMessages[] = $fieldDetails['validation_message'];
						}
					}
					if ($datasetSystemTable == 'users') {
						if (isset(self::$emails[strtolower($value)]) || checkRowExists($datasetSystemTable, array('email' => $value, 'id' => array('!' => self::$id)))) {
							$validationError = true;
							$validationErrorMessages[] = 'Duplicate email found';
						}
						$emails[strtolower($value)] = true;
					}
					break;
				case 'emails':
					if (!validateEmailAddress($value, true)) {
						$validationError = true;
						if (!$fieldDetails['validation_message']) {
							$validationErrorMessages[] = 'Value is in incorrect format for emails';
						} else {
							$validationErrorMessages[] = $fieldDetails['validation_message'];
						}
					}
					break;
				case 'no_spaces':
					if (preg_replace('/\S/', '', $value)) {
						$validationError = true;
						if (!$fieldDetails['validation_message']) {
							$validationErrorMessages[] = 'Value cannot contain spaces';
						} else {
							$validationErrorMessages[] = $fieldDetails['validation_message'];
						}
					}
					break;
				case 'numeric':
					if (!is_numeric($value)) {
						$validationError = true;
						if (!$fieldDetails['validation_message']) {
							$validationErrorMessages[] = 'Value must be numeric';
						} else {
							$validationErrorMessages[] = $fieldDetails['validation_message'];
						}
					}
					break;
				case 'screen_name':
					if (!validateScreenName($value)) {
						$validationError = true;
						if (!$fieldDetails['validation_message']) {
							$validationErrorMessages[] = 'Screen name is invalid';
						} else {
							$validationErrorMessages[] = $fieldDetails['validation_message'];
						}
					}
					if (isset(self::$screenNames[strtolower($value)]) || checkRowExists($datasetSystemTable, array('screen_name' => $value, 'id' => array('!' => self::$id)))) {
						$validationError = true;
						$validationErrorMessages[] = 'Duplicate screen name found';
					}
					self::$screenNames[strtolower($value)] = true;
					
					break;
			}
			if ($fieldDetails['required'] && empty($value)) {
				if (!$requiredErrorMessage = $fieldDetails['required_message']) {
					$requiredErrorMessage = 'Value is required but missing';
				}
				$problems .= 'Error (Line '.$lineNumber.', Value '.($dataCount+1).'): '.$requiredErrorMessage."\r\n";
			}
			
			if ($validationError) {
				foreach ($validationErrorMessages as $key => $message) {
					$problems .= 'Error (Line '.$lineNumber.', Value '.($dataCount+1).'): '.$message."\r\n";
				}
				return false;
			}
		} elseif ($fieldId == 'id') {
			if (!checkRowExists($datasetSystemTable, $value)) {
				$problems .= 'Error (Line '.$lineNumber.'): Could not find a matching ID for "'.$value."\"\r\n";
				return false;
			}
		}
		return true;
	}
	
	private static function getDatasetValueDetails($dataset) {
		$datasetValueDetails = array();
		$sql = '
			SELECT f.id, f.db_column, f.validation, f.validation_message, f.required, f.required_message, f.type, f.values_source, f.parent_id
			FROM '.DB_NAME_PREFIX.'custom_dataset_fields f
			INNER JOIN '.DB_NAME_PREFIX.'custom_dataset_tabs t
				ON (f.dataset_id = t.dataset_id) AND (f.tab_name = t.name)
			WHERE f.dataset_id = '.(int)$dataset. '
			AND f.db_column != ""
			ORDER BY t.ord, f.ord';
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			$datasetValueDetails[$row['id']] = $row;
		}
		return $datasetValueDetails;
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$datasetDetails = getDatasetDetails($box['key']['dataset']);
		
		// Get fieldIDs and column index
		$keyValues = array();
		$dataCount = 0;
		$firstNameFieldDetails = $lastNameFieldDetails = false;
		foreach ($box['tabs']['headers']['fields'] as $fieldName => $field) {
			if (isset($field['type']) && ($field['type'] == 'select') && chopPrefixOffOfString($fieldName, 'database_column__')) {
				if (!empty($field['value'])) {
					if (($field['value'] == 'name_split_on_first_space' || $field['value'] == 'name_split_on_last_space') && !$firstNameFieldDetails && !$lastNameFieldDetails) {
						$firstNameFieldDetails = getDatasetFieldDetails('first_name', $datasetDetails);
						$lastNameFieldDetails = getDatasetFieldDetails('last_name', $datasetDetails);
					}
					$keyValues[$dataCount] = $field['value'];
				}
				$dataCount++;
			}
		}
		
		$datasetValueDetails = self::getDatasetValueDetails($box['key']['dataset']);
		$constantValues = array();
		foreach ($datasetValueDetails as $fieldId => $fieldDetails) {
			$fieldName = 'actions/value__'.$fieldId;
			if (isset($values[$fieldName]) && ($values[$fieldName] != '') && ($fieldDetails['type'] != 'checkboxes')) {
				$constantValues[$fieldId] = $values[$fieldName];
			}
		}
		$errorLines = $box['key']['error_lines'] ? explode(',', $box['key']['error_lines']) : array();
		$blankLines = $box['key']['blank_lines'] ? explode(',', $box['key']['blank_lines']) : array();
		$keyLine = $values['headers/key_line'];
		$warningLines = array();
		if ($values['preview/error_options'] == 'skip_warning_lines') {
			$warningLines = $box['key']['warning_lines'] ? explode(',', $box['key']['warning_lines']) : array();
		}
		$linesToSkip = array_merge($errorLines, $blankLines, $warningLines);
		
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
					if (in_array($lineNumber, $linesToSkip) || $lineNumber == $keyLine) {
						continue;
					}
					$importValues[$lineNumber] = $constantValues;
					$data = str_getcsv($line);
					for ($dataCount = 0; $dataCount < count($data); $dataCount++) {
						if (isset($keyValues[$dataCount])) {
							$data[$dataCount] = trim($data[$dataCount]);
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
							} else {
								$importValues[$lineNumber][$keyValues[$dataCount]] = $data[$dataCount];
							}
						}
					}
				}
				
			} else {
				require_once CMS_ROOT.'zenario/libraries/lgpl/PHPExcel_1_7_8/Classes/PHPExcel.php';
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
								if (isset($keyValues[$dataCount])) {
									$importValues[$lineNumber][$keyValues[$dataCount]] = trim($value);
								}
								$dataCount++;
							}
						}
					}
				}
			}
			
			// Import data
			$unexpectedErrors = self::setImportData($box['key']['dataset'], $importValues, $mode);
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
			$body .= strip_tags($fields['actions/records_statement']['snippet']['html'])."\n";
			$body .= "Error log: \n\n";
			$errorLog = ($values['preview/problems'] ? $values['preview/problems'] : 'No errors or warnings');
			$body .= $errorLog;
			if ($unexpectedErrors) {
				$body .= "\n\nUnexpected Errors:\n\n";
				$body .= $unexpectedErrors;
			}
			sendEmail('Dataset Import Report', $body, $adminDetails['email'], $addressToOverriddenBy, false, false, false, array(), array(), 'bulk', false);
		}
	}
	
	private static function setImportData($datasetId, $importData, $mode) {
		
		$datasetDetails = getDatasetDetails($datasetId);
		$fieldIdDetails = array();
		$customDataIdColumn = 'id';
		$errorMessage = '';
		foreach ($importData as $i => $record) {
			$error = false;
			$message = 'Line: '.($i+1)."\n";
			// Sort data into custom and non-custom
			$customData = array();
			$data = array();
			$id = false;
			foreach($record as $fieldId => $value) {
				if ($fieldId == 'id') {
					$id = $value;
				} else {
					if (!isset($fieldIdDetails[$fieldId])) {
						$fieldIdDetails[$fieldId] = getRow('custom_dataset_fields', array('is_system_field', 'db_column'), $fieldId);
					}
					if ($fieldIdDetails[$fieldId]['is_system_field']) {
						$data[$fieldIdDetails[$fieldId]['db_column']] = $value;
					} else {
						$customData[$fieldIdDetails[$fieldId]['db_column']] = $value;
					}
					$message .= $fieldIdDetails[$fieldId]['db_column'].': '.$value. "\n";
				}
			}
			// Create or update records
			if ($mode == 'insert') {
				if ($datasetDetails['system_table'] == 'users') {
					if (!isset($data['email'])) {
						$data['email'] = '';
					}
					if (!isset($data['first_name'])) {
						$data['first_name'] = '';
					}
					if (!isset($data['last_name'])) {
						$data['last_name'] = '';
					}
					$customDataIdColumn = 'user_id';
					$id = saveUser($data);
					
				} elseif (inc('zenario_location_manager') && $datasetDetails['system_table'] = ZENARIO_LOCATION_MANAGER_PREFIX.'locations') {
					$customDataIdColumn = 'location_id';
					$id = insertRow($datasetDetails['system_table'], $data);
				} else {
					$id = insertRow($datasetDetails['system_table'], $data);
				}
				// If record was created successfully, add custom data
				if ($id && is_numeric($id)) {
					if (!empty($customData)) {
						$customData[$customDataIdColumn] = $id;
						insertRow($datasetDetails['table'], $customData);
					}
				} elseif (is_object($id) && get_class($id) == 'zenario_error') {
					foreach ($id->errors as $errorField => $error) {
						$message .= 'Error code: '. phrase($error);
					}
					$error = true;
				} else {
					$message .= "Error: could not import";
					$error = true;
				}
				$message .= "\n\n";
			} elseif ($mode == 'update') {
				updateRow($datasetDetails['system_table'], $data, array('id' => $id));
				updateRow($datasetDetails['table'], $customData, array());
			}
			if ($error) {
				$errorMessage .= $message;
			}
		}
		return $errorMessage;
	}
}
