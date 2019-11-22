<?php
/*
 * Copyright (c) 2019, Tribal Limited
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

namespace ze;

class dataset {
	
	//Formerly "ZENARIO_CENTRALISED_LIST_MODE_INFO"
	const LIST_MODE_INFO = 1;
	
	//Formerly "ZENARIO_CENTRALISED_LIST_MODE_LIST"
	const LIST_MODE_LIST = 2;
	
	//Formerly "ZENARIO_CENTRALISED_LIST_MODE_FILTERED_LIST"
	const LIST_MODE_FILTERED_LIST = 3;
	
	//Formerly "ZENARIO_CENTRALISED_LIST_MODE_VALUE"
	const LIST_MODE_VALUE = 4;

	//Formerly "getDatasetDetails()"
	public static function details($dataset, $cols = true) {
		if (is_array($dataset)) {
			return $dataset;
	
		} elseif (is_numeric($dataset)) {
			return \ze\row::get('custom_datasets', $cols, $dataset);
	
		} elseif ($out = \ze\row::get('custom_datasets', $cols, ['system_table' => $dataset])) {
			return $out;
	
		} elseif ($out = \ze\row::get('custom_datasets', $cols, ['system_table' => '', 'table' => $dataset])) {
			return $out;
		}
		return \ze\row::get('custom_datasets', $cols, ['label' => $dataset]);
	}

	//Formerly "getDatasetTabDetails()"
	public static function tabDetails($datasetId, $tabName) {
		return \ze\row::get('custom_dataset_tabs', true, ['dataset_id' => $datasetId, 'name' => $tabName]);
	}

	//Formerly "getDatasetFieldBasicDetails()"
	public static function fieldBasicDetails($fieldId) {
		$sql = "
			SELECT type, is_system_field, db_column, label, default_label
			FROM ". DB_PREFIX. "custom_dataset_fields
			WHERE id = ". (int) $fieldId;
		return \ze\sql::fetchAssoc($sql);
	}

	//Formerly "getDatasetFieldDetails()"
	public static function fieldDetails($field, $dataset = false, $cols = true) {
		if (is_numeric($field)) {
			return \ze\row::get('custom_dataset_fields', $cols, $field);
		} else {
			if (!is_numeric($dataset)) {
				$dataset = \ze\dataset::details($dataset, ['id']);
				$dataset = $dataset['id'];
			}
			return \ze\row::get('custom_dataset_fields', $cols, ['dataset_id' => $dataset, 'db_column' => $field]);
		}
	}

	//Formerly "getDatasetFieldsDetails()"
	public static function fieldsDetails($dataset, $indexById = false) {
		if (!is_numeric($dataset)) {
			$dataset = \ze\dataset::details($dataset, ['id']);
			$dataset = $dataset['id'];
		}
	
		$out = [];
		if ($fields = \ze\row::getAssocs('custom_dataset_fields', true, ['dataset_id' => $dataset, 'type' => ['!' => 'other_system_field']])) {
			$index = $indexById ? 'id' : 'db_column';
			foreach ($fields as $field) {
				$out[$field[$index]] = $field;
			}
		}
	
		return $out;
	}

	//Formerly "getDatasetFieldRepeatRowColumnName()"
	public static function repeatRowColumnName($dbColumn, $row) {
		return ($row > 1) ? $dbColumn . '___' . $row : $dbColumn;
	}

	//Formerly "getDatasetRepeatStartRowColumnName()"
	public static function repeatStartRowColumnName($fieldId) {
		return $fieldId . '___rows';
	}

	const fieldValueFromTwig = true;
	//Formerly "datasetFieldValue()"
	public static function fieldValue($dataset, $cfield, $recordId, $returnCSV = true, $forDisplay = false, $row = false) {
		if ($dataset && !is_array($dataset)) {
			$dataset = \ze\dataset::details($dataset, ['id', 'system_table', 'table']);
		}
		if (!is_array($cfield)) {
			$cfield = \ze\dataset::fieldDetails($cfield, $dataset, ['id', 'dataset_id', 'is_system_field', 'type', 'values_source', 'dataset_foreign_key_id', 'db_column']);
		}
		if (!$cfield) {
			return false;
		}
		if (!is_array($dataset)) {
			$dataset = \ze\dataset::details($cfield['dataset_id'], ['id', 'system_table', 'table']);
		}
		if (!$dataset) {
			return false;
		}
	
		if ($cfield['is_system_field']) {
			if ($cfield['db_column']) {
				$value = \ze\row::get($dataset['system_table'], $cfield['db_column'], $recordId);
				if ($forDisplay && in_array($cfield['type'], ['centralised_radios', 'centralised_select']) && $cfield['values_source']) {
					return \ze\dataset::centralisedListValue($cfield['values_source'], $value);
				}
				return $value;
			}
		} else {
			//Checkbox values are stored in the custom_dataset_values_link table
		
			switch ($cfield['type']) {
				case 'checkboxes':
				
					$sql = "
						SELECT cdvl.value_id
						FROM ". DB_PREFIX. "custom_dataset_values_link AS cdvl
						INNER JOIN ". DB_PREFIX. "custom_dataset_field_values AS cdfv
						   ON cdfv.id = cdvl.value_id
						  AND cdfv.field_id = ". (int) $cfield['id']. "
						WHERE cdvl.linking_id = ". (int) $recordId;
				
					$values = \ze\sql::fetchValues($sql);
			
					if ($forDisplay) {
						$values = \ze\row::getValues('custom_dataset_field_values', 'label', ['field_id' => $cfield['id'], 'id' => $values], 'label');
				
						if ($returnCSV) {
							return implode(', ', $values);
						} else {
							return $values;
						}
					} else {
						if ($returnCSV) {
							return \ze\escape::in($values, 'numeric');
						} else {
							return $values;
						}
					}
				
					break;
			
				case 'file_picker':
					$values = \ze\row::getAssocs(
						'custom_dataset_files_link',
						'file_id',
						[
							'dataset_id' => $dataset['id'],
							'field_id' => $cfield['id'],
							'linking_id' => $recordId]);
			
					if ($forDisplay) {
						$values = \ze\row::getValues('files', 'filename', ['id' => $values], 'filename');
				
						if ($returnCSV) {
							return implode(', ', $values);
						} else {
							return $values;
						}
					} else {
						if ($returnCSV) {
							return \ze\escape::in($values, 'numeric');
						} else {
							return $values;
						}
					}
				
					break;
			
				default:
					$dbColumn = $cfield['db_column'];
					if ($row) {
						$dbColumn = \ze\dataset::repeatRowColumnName($dbColumn, $row);
					}
					$value = \ze\row::get($dataset['table'], $dbColumn, $recordId);
				
					if ($forDisplay) {
						switch ($cfield['type']) {
							case 'radios':
							case 'select':
								return \ze\row::get('custom_dataset_field_values', 'label', ['field_id' => $cfield['id'], 'id' => $value]);

							case 'centralised_radios':
							case 'centralised_select':
								return \ze\dataset::centralisedListValue($cfield['values_source'], $value);
						
							case 'dataset_select':
							case 'dataset_picker':
								if ($labelDetails = \ze\dataset::labelFieldDetails($cfield['dataset_foreign_key_id'])) {
									return \ze\row::get($labelDetails['table'], $labelDetails['db_column'], $value);
								}
						}
					}
				
					return $value;
			}
		}
	
	}
	
	const fieldDisplayValueFromTwig = true;
	//Formerly "datasetFieldDisplayValue()"
	public static function fieldDisplayValue($dataset, $cfield, $recordId, $returnCSV = true) {
		return \ze\dataset::fieldValue($dataset, $cfield, $recordId, $returnCSV, true);
	}

	//Checkboxes are stored in the custom_dataset_values_link table as there could be more than one of them.
	//Given an array or comma-seperated list of the checked values, this function will set the value in the
	//database.
	//Formerly "updateDatasetCheckboxField()"
	public static function updateCheckboxField($datasetId, $fieldId, $linkingId, $values) {
		if (!is_array($values)) {
			$values = \ze\ray::explodeAndTrim($values);
		}
	
		//Loop through making sure that the selected values are in the database.
		$selectedIds = [];
		foreach ($values as $id) {
			if ($id) {
				$selectedIds[$id] = $id;
				\ze\row::set(
					'custom_dataset_values_link',
					[],
					['dataset_id' => $datasetId, 'value_id' => $id, 'linking_id' => $linkingId]);
			}
		}
	
		//Remove any values from the database that *weren't* selected
		$sql = "
			DELETE cdvl.*
			FROM ". DB_PREFIX. "custom_dataset_field_values AS cdfv
			INNER JOIN ". DB_PREFIX. "custom_dataset_values_link AS cdvl
			   ON cdvl.value_id = cdfv.id
			  AND cdvl.linking_id = ". (int) $linkingId. "
			WHERE cdfv.field_id = ". (int) $fieldId;
	
		if (!empty($selectedIds)) {
			$sql .= "
			  AND cdfv.id NOT IN (". \ze\escape::in($selectedIds, 'numeric'). ")";
		}
	
		\ze\sql::update($sql);
	}

	//As above, but for picked files
	//Formerly "updateDatasetFilePickerField()"
	public static function updateFilePickerField($datasetId, $cField, $linkingId, $values) {
		if (!is_array($values)) {
			$values = \ze\ray::explodeAndTrim($values);
		}
		if (!is_array($cField)) {
			$cField = \ze\row::get('custom_dataset_fields', ['id', 'store_file', 'multiple_select'], $cField);
		}
	
		//Loop through making sure that the selected values are in the database.
		$selectedIds = [];
		foreach ($values as $id) {
			if ($id) {
			
				if ($location = \ze\file::getPathOfUploadInCacheDir($id)) {
					$id = \ze\file::addToDatabase(
						'dataset_file', $location,
						$filename = false, $mustBeAnImage = false, $deleteWhenDone = false,
						$addToDocstoreDirIfPossible = $cField['store_file'] == 'in_docstore'
					);
				}
			
				$selectedIds[$id] = $id;
				\ze\row::set(
					'custom_dataset_files_link',
					[],
					[
						'dataset_id' => $datasetId,
						'field_id' => $cField['id'],
						'linking_id' => $linkingId,
						'file_id' => $id
				]);
			
				if (!$cField['multiple_select']) {
					break;
				}
			}
		}
	
		//Remove any values from the database that *weren't* selected
		$sql = "
			DELETE
			FROM ". DB_PREFIX. "custom_dataset_files_link
			WHERE dataset_id = ". (int) $datasetId. "
			  AND field_id = ". (int) $cField['id']. "
			  AND linking_id = ". (int) $linkingId;
	
		if (!empty($selectedIds)) {
			$sql .= "
			  AND file_id NOT IN (". \ze\escape::in($selectedIds, 'numeric'). ")";
		}
	
		if (\ze\sql::update($sql)) {
			\ze\dataset::removeUnusedFiles();
		}
	}

	//Delete any dataset files from the system that are now not used anywhere
	//Formerly "removeUnusedDatasetFiles()"
	public static function removeUnusedFiles() {

		$sql = "
			SELECT f.id
			FROM ". DB_PREFIX. "files AS f
			LEFT JOIN ". DB_PREFIX. "custom_dataset_files_link AS cdfl
			   ON cdfl.file_id = f.id
			WHERE f.`usage` = 'dataset_file'
			  AND cdfl.file_id IS NULL
			GROUP BY f.id";
	
		$result = \ze\sql::select($sql);
		while ($file = \ze\sql::fetchAssoc($result)) {
			\ze\file::delete($file['id']);
		}
	}


	//Formerly "datasetFieldDBColumn()"
	public static function fieldDBColumn($fieldId) {
		return \ze\row::get('custom_dataset_fields', 'db_column', $fieldId);
	}

	//Formerly "datasetFieldId()"
	public static function fieldId($fieldDbColumn) {
		return \ze\row::get('custom_dataset_fields', 'id', ['db_column' => $fieldDbColumn]);
	}

	//Formerly "getDatasetSystemFieldDetails()"
	public static function systemFieldDetails($datasetId, $tabName, $fieldName) {
		return \ze\row::get('custom_dataset_fields', true, ['dataset_id' => $datasetId, 'tab_name' => $tabName, 'field_name' => $fieldName, 'is_system_field' => 1]);
	}

	//Formerly "getDatasetFieldValueLabel()"
	public static function fieldValueLabel($valueId) {
		return \ze\row::get('custom_dataset_field_values', 'label', $valueId);
	}

	//Formerly "getDatasetFieldLOVFlatArrayToLabeled()"
	public static function fieldLOVFlatArrayToLabeled(&$value, $key) {
		if (!is_array($value)) {
			$value = ['label' => $value];
		}
	
		++self::$ord;
	
		if (empty($value['ord'])) {
			$value['ord'] = self::$ord;
		}
	}
	
	private static $ord;

	//Formerly "getDatasetFieldLOV()"
	public static function fieldLOV($field, $flat = true, $filter = false) {
		if (!is_array($field)) {
			$field = \ze\dataset::fieldDetails($field);
		}
	
		$lov = [];
		if (\ze\ring::chopPrefix('centralised_', $field['type'])) {
			if (!empty($field['values_source_filter'])) {
				$filter = $field['values_source_filter'];
			}
			if ($lov = \ze\dataset::centralisedListValues($field['values_source'], $filter)) {
				if (!$flat) {
					self::$ord = 0;
					array_walk($lov, 'ze\\dataset::fieldLOVFlatArrayToLabeled');
					self::$ord = false;
				}
			}
	
		} elseif (\ze\ring::chopPrefix('dataset_', $field['type'])) {
			if ($labelDetails = \ze\dataset::labelFieldDetails($field['dataset_foreign_key_id'])) {
			
				$lov = \ze\row::getAssocs($labelDetails['table'], $labelDetails['db_column'], [], $labelDetails['db_column']);
			
				if (!$flat) {
					$ord = 0;
					foreach ($lov as &$v) {
						$v = ['ord' => ++$ord, 'label' => $v];
					}
				}
			}
		} elseif ($field['type'] == 'text') {
			if ($field['db_column']) {
				$dataset = \ze\dataset::details($field['dataset_id']);
				$table = $field['is_system_field'] ? $dataset['system_table'] : $dataset['table'];
				$sql = '
					SELECT DISTINCT ' . \ze\escape::sql($field['db_column']) . '
					FROM ' . DB_PREFIX . $table . '
					ORDER BY ' . \ze\escape::sql($field['db_column']);
				$result = \ze\sql::select($sql);
				while ($row = \ze\sql::fetchRow($result)) {
					if ($row[0]) {
						$lov[$row[0]] = $row[0];
					}
				}
			}
		} else {
			if ($flat) {
				$cols = 'label';
			} else {
				$cols = ['ord', 'label', 'note_below'];
			}
		
			$lov = \ze\row::getAssocs('custom_dataset_field_values', $cols, ['field_id' => $field['id']], ['ord']);
		}
		return $lov;
	}

	//Formerly "countDatasetFieldRecords()"
	public static function countDatasetFieldRecords($field, $dataset = false) {
		if (!is_array($field)) {
			$field = \ze\dataset::fieldDetails($field);
		}
		if ($field && !is_array($dataset)) {
			$dataset = \ze\dataset::details($field['dataset_id']);
		}
	
		if ($field && $dataset) {
		
			if ($field['type'] == 'checkboxes') {
				$sql = "
					SELECT COUNT(DISTINCT vl.linking_id)
					FROM ". DB_PREFIX. "custom_dataset_field_values AS fv
					INNER JOIN ". DB_PREFIX. "custom_dataset_values_link AS vl
					ON vl.value_id = fv.id
					WHERE fv.field_id = ". (int) $field['id'];
		
			} elseif ($field['type'] == 'file_picker') {
				$sql = "
					SELECT COUNT(DISTINCT linking_id)
					FROM ". DB_PREFIX. "custom_dataset_files_link
					WHERE dataset_id = ". (int) $dataset['id']. "
					  AND field_id = ". (int) $field['id'];
		
			} elseif (\ze::in($field['type'], 'checkbox', 'group', 'radios', 'select')) {
				$sql = "
					SELECT COUNT(*)
					FROM `". DB_PREFIX. \ze\escape::sql($field['is_system_field']? $dataset['system_table'] : $dataset['table']). "`
					WHERE `". \ze\escape::sql($field['db_column']). "` != 0";
		
			} else {
				$sql = "
					SELECT COUNT(*)
					FROM `". DB_PREFIX. \ze\escape::sql($field['is_system_field']? $dataset['system_table'] : $dataset['table']). "`
					WHERE `". \ze\escape::sql($field['db_column']). "` IS NOT NULL
					  AND `". \ze\escape::sql($field['db_column']). "` != ''";
			}
		
			$result = \ze\sql::select($sql);
			$row = \ze\sql::fetchRow($result);
		
			return $row[0];
		} else {
			return false;
		}
	}

	//Formerly "getDatasetLabelFieldDetails()"
	public static function labelFieldDetails($otherDatasetId) {
	
		$details = [];
	
		if (($otherDatasetId)
		 && ($otherDataset = \ze\dataset::details($otherDatasetId))
		 && ($otherDataset['label_field_id'])
		 && ($otherLabelField = \ze\dataset::fieldBasicDetails($otherDataset['label_field_id']))
		 && ($details['db_column'] = $otherLabelField['db_column'])) {
		
			if ($otherLabelField['is_system_field']) {
				$details['table'] = $otherDataset['system_table'];
			} else {
				$details['table'] = $otherDataset['table'];
			}
		
			if ($details['table']
			 && ($details['id_column'] = \ze\row::idColumnOfTable($details['table'], true))) {
			
				return $details;
			}
		}
	
		return false;
	}




	//Formerly "getCentralisedListValues()"
	public static function centralisedListValues($valuesSource, $filter = false) {
		if ($valuesSource
			&& ($source = explode('::', $valuesSource, 3))
			&& (!empty($source[0]))
			&& (!empty($source[1]))
			&& (!isset($source[2]))
			&& (\ze\module::inc($source[0]))
		) {
			$listMode = \ze\dataset::LIST_MODE_LIST;
			if ($filter !== false && $filter !== '') {
				$listMode = \ze\dataset::LIST_MODE_FILTERED_LIST;
			}
			return call_user_func($source, $listMode, $filter);
		}
		return [];
	}


	//Formerly "getCentralisedListValue()"
	public static function centralisedListValue($valuesSource, $id) {
		if ($valuesSource
			&& ($source = explode('::', $valuesSource, 3))
			&& (!empty($source[0]))
			&& (!empty($source[1]))
			&& (!isset($source[2]))
			&& (\ze\module::inc($source[0]))
		) {
			return call_user_func($source, \ze\dataset::LIST_MODE_VALUE, $id);
		}
		return false;
	}
	
	public static function englishTypeName($type) {
		switch ($type) {
			case 'group': 
				return 'Group';
			case 'checkbox': 
				return 'Checkbox';
			case 'checkboxes': 
				return 'Checkboxes';
			case 'date': 
				return 'Date';
			case 'editor': 
				return 'Editor';
			case 'radios':
				return 'Radios';
			case 'centralised_radios': 
				return 'Centralised radios';
			case 'select': 
				return 'Select';
			case 'centralised_select': 
				return 'Centralised select';
			case 'text': 
				return 'Text';
			case 'textarea': 
				return 'Textarea';
			case 'url': 
				return 'URL';
			case 'other_system_field': 
				return 'Other system field';
			case 'dataset_select': 
				return 'Dataset select';
			case 'dataset_picker': 
				return 'Dataset picker';
			case 'file_picker': 
				return 'File picker';
			case 'repeat_start': 
				return 'Start of repeating section`';
			case 'repeat_end': 
				return 'End of repeating section';
			default: 
				return 'Unknown';
		}
	}
}