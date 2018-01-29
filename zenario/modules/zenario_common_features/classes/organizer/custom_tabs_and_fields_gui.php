<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

class zenario_common_features__organizer__custom_tabs_and_fields_gui extends ze\moduleBaseClass {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$datasetId = $refinerId;
		$this->exitIfCannotEditDataset($datasetId);
		$dataset = ze\dataset::details($datasetId);
		
		$panel['title'] = ze\admin::phrase('Managing the dataset "[[label]]"', $dataset);
		$panel['items'] = [];
		$panel['dataset'] = $dataset;
		
		//Whether to allow adding fields of type "group"
		$panel['use_groups_field'] = ($dataset['system_table'] == 'users');
		
		//Load centralised lists for fields of type "centralised_radios" and "centralised_select"
		$centralisedLists = ze\datasetAdm::centralisedLists();
		$panel['centralised_lists']['values'] = [];
		$count = 1;
		foreach ($centralisedLists as $method => $label) {
			$params = explode('::', $method);
			if (ze\module::inc($params[0])) {
				$info = call_user_func($method, ze\dataset::LIST_MODE_INFO);
				$panel['centralised_lists']['values'][$method] = ['info' => $info, 'label' => $label];
			}
		}
		
		//Load a list of dataset fields used in forms so a warning can be displayed if they're deleted
		$panel['forms_with_dataset_fields'] = [];
		$panel['dataset_fields_in_forms'] = [];
		$panel['dataset_repeat_fields_in_forms'] = [];
		if (ze\module::inc('zenario_user_forms')) {
			$sql = '
				SELECT cdf.id, uf.name, uf.id AS form_id, cdf.type, cdf.repeat_start_id
				FROM ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
				INNER JOIN ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms uf
					ON uff.user_form_id = uf.id
				INNER JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields cdf
					ON uff.user_field_id = cdf.id
					AND cdf.is_system_field = 0';
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
				if (!isset($panel['dataset_fields_in_forms'][$row['id']])) {
					$panel['dataset_fields_in_forms'][$row['id']] = [];
				}
				if (!isset($panel['forms_with_dataset_fields'][$row['form_id']])) {
					$panel['forms_with_dataset_fields'][$row['form_id']] = $row['name'];
				}
				$panel['dataset_fields_in_forms'][$row['id']][] = $row['form_id'];
			}
			
		}
		
		//Load pickable datasets for fields of type "dataset_select" and "dataset_picker"
		$panel['datasets'] = [];
		$result = ze\row::query(
			'custom_datasets',
			['id', 'label'],
			array('extends_organizer_panel' => ['!' => ''], 'label_field_id' => ['!' => 0]),
			'label'
		);
		$ord = 1;
		while ($row = ze\sql::fetchAssoc($result)) {
			$panel['datasets'][$row['id']] = ['label' => $row['label'], 'ord' => $ord++];
		}
		
		$systemKeys = [];
		if ($dataset['system_table']) {
			$sql = '
				SHOW KEYS
				FROM ' . DB_NAME_PREFIX . $dataset['system_table'] . '
				WHERE Key_name != "PRIMARY"';
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
				$systemKeys[$row['Column_name']] = true;
			}
		}
		
		
		$moduleFilesLoaded = [];
		$tags = [];
		ze\tuix::load(
			$moduleFilesLoaded, $tags, $type = 'admin_boxes', $dataset['extends_admin_box'],
			$settingGroup = '', $compatibilityClassNames = false, $runningModulesOnly = true, $exitIfError = true
		);
		
		$foundFieldsInTUIX = [];
		//Get tabs and fields from TUIX
		if (!empty($tags[$dataset['extends_admin_box']]['tabs'])
			&& is_array($tags[$dataset['extends_admin_box']]['tabs'])
		) {
			//Loop through system tabs in TUIX
			$tabOrdinal = 0;
			foreach ($tags[$dataset['extends_admin_box']]['tabs'] as $tabName => $tab) {
				//Only load tabs with labels
				if (empty($tab['label']) && empty($tab['default_label'])) {
					continue;
				}
				$foundFieldsInTUIX[$tabName] = array(
					'ord' => ++$tabOrdinal,
					'label' => ze::ifNull($tab['dataset_label'] ?? false, $tab['label'] ?? false),
					'fields' => []
				);
				if (!empty($tab['fields'])
					&& is_array($tab['fields'])
				) {
					//Loop through system fields in TUIX
					$fieldOrdinal = 0;
					foreach ($tab['fields'] as $fieldName => $field) {
						$foundFieldsInTUIX[$tabName]['fields'][$fieldName] = $field;
					}
				}
			}
		}
		
		//Get custom data for system tabs and custom tabs
		$tabsResult = ze\row::query('custom_dataset_tabs', true, ['dataset_id' => $dataset['id']], 'ord');
		$tabCount = 0;
		while ($tab = ze\sql::fetchAssoc($tabsResult)) {
			if ($tab['is_system_field'] && !isset($foundFieldsInTUIX[$tab['name']])) {
				continue;
			}
			++$tabCount;
			$tabProperties = array(
				'ord' => $tabCount,
				'name' => $tab['name'],
				'tab_label' => $tab['label'],
				'is_system_field' => 0,
				'parent_field_id' => (int)$tab['parent_field_id'],
				'fields' => []
			);
			if ($tab['is_system_field'] && isset($foundFieldsInTUIX[$tab['name']])) {
				$tabProperties['is_system_field'] = 1;
				if ($tab['default_label'] && !$tab['label']) {
					$tabProperties['tab_label'] = $tab['default_label'];
				}
			}
			//First tab automatically loads it's fields
			if ($tabCount == 1) {
				$tabProperties['record_counts_fetched'] = true;
			}
			$panel['items'][$tab['name']] = $tabProperties;
			
			
			$fieldsResult = ze\row::query('custom_dataset_fields', true, ['dataset_id' => $dataset['id'], 'tab_name' => $tab['name']], 'ord');
			$fieldCount = 0;
			$groupings = [];
			while ($field = ze\sql::fetchAssoc($fieldsResult)) {
				$tuixField = false;
				if (isset($foundFieldsInTUIX[$tab['name']]['fields'][$field['field_name']])) {
					$tuixField = $foundFieldsInTUIX[$tab['name']]['fields'][$field['field_name']];
				} elseif ($field['is_system_field']) {
					continue;
				}
				++$fieldCount;
				
				$fieldProperties = array(
					'id' => (int)$field['id'],
					'parent_id' => (int)$field['parent_id'],
					'is_system_field' => (int)$field['is_system_field'],
					'is_protected' => (int)$field['protected'],
					'was_protected' => (int)$field['protected'],
					'is_readonly' => (int)$field['readonly'],
					'ord' => $fieldCount,
					'field_label' => $field['label'] ? $field['label'] : ($field['default_label'] ? $field['default_label'] : ''),
					'type' => $field['type'],
					'width' => (int)$field['width'],
					'height' => (int)$field['height'],
					'values_source' => $field['values_source'],
					'values_source_filter' => $field['values_source_filter'],
					'dataset_foreign_key_id' => (int)$field['dataset_foreign_key_id'],
					'required' => (int)$field['required'],
					'required_message' => $field['required_message'],
					'validation' => $field['validation'],
					'validation_message' => $field['validation_message'],
					'note_below' => $field['note_below'],
					'side_note' => $field['side_note'],
					'db_column' => $field['db_column'],
					'show_in_organizer' => (int)($field['organizer_visibility'] != 'none'),
					'create_index' => $field['create_index'] ? 'index' : 'no_index',
					'searchable' => (int)$field['searchable'],
					'sortable' => (int)$field['sortable'],
					'include_in_export' => (int)$field['include_in_export'],
					'autocomplete' => (int)$field['autocomplete'],
					'indent' => (int)$field['indent'],
					'multiple_select' => (int)$field['multiple_select'],
					'store_file' => $field['store_file'],
					'extensions' => $field['extensions'],
					'admin_box_visibility' => $field['admin_box_visibility'],
					'organizer_visibility' => $field['organizer_visibility'],
					'allow_admin_to_change_visibility' => (int)$field['allow_admin_to_change_visibility'],
					'allow_admin_to_change_export' => (int)$field['allow_admin_to_change_export'],
					'hide_in_organizer' => ($field['organizer_visibility'] == 'hide'),
					'min_rows' => (int)$field['min_rows'],
					'max_rows' => (int)$field['max_rows'],
					'repeat_start_id' => (int)$field['repeat_start_id']
				);
				
				//Get record count for fields on first tab. Other tab fields are loaded as their tab is clicked
				if (($tabCount == 1) && $field['db_column']) {
					$fieldProperties['record_count'] = (int)ze\dataset::countDatasetFieldRecords($field['id']);
				}
				
				//Add LOV for multi value field types
				if (in_array($field['type'], ['checkboxes', 'radios', 'select', 'centralised_radios', 'centralised_select'])) {
					$fieldValueOrdinal = 0;
					$fieldValues = ze\dataset::fieldLOV($field, false);
					$fieldProperties['lov'] = [];
					foreach ($fieldValues as $valueId => $value) {
						$value['ord'] = ++$fieldValueOrdinal;
						$value['id'] = $valueId;
						$fieldProperties['lov'][$valueId] = $value;
					}
				}
				
				if ($field['is_system_field'] && $tuixField) {
					$tuixField['id'] = $field['id'];
					//dataset_label always overrides label
					if (isset($tuixField['dataset_label'])) {
						$fieldProperties['label'] = $tuixField['dataset_label'];
					}
					//Always show key fields as having an index
					if (!empty($systemKeys[$field['db_column']])) {
						$fieldProperties['create_index'] = true;
					}
					//Try to get field type for other_system_fields
					if ($field['type'] == 'other_system_field') {
						if (!empty($tuixField['type'])) {
							$fieldProperties['tuix_type'] = $tuixField['type'];
							if ($fieldProperties['tuix_type'] == 'grouping') {
								$groupings[$field['field_name']] = $field;
								$fieldProperties['grouping_name'] = $tuixField['name'];
							}
						} elseif (isset($tuixField['snippet']['html'])) {
							$fieldProperties['tuix_type'] = 'html_snippet';
						} elseif (isset($tuixField['pick_items'])) {
							$fieldProperties['tuix_type'] = 'pick_items';
						}
					}
					//Look for groupings on system fields
					if (!empty($tuixField['grouping'])) {
						$fieldProperties['grouping'] = $tuixField['grouping'];
					}
				}
				
				$panel['items'][$tab['name']]['fields'][$field['id']] = $fieldProperties;
			}
			
		}
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		$datasetId = $refinerId;
		$this->exitIfCannotEditDataset($datasetId);
		$dataset = ze\dataset::details($datasetId);
		
		switch ($_POST['mode'] ?? false) {
			case 'save':
				$dataJSON = $_POST['data'] ?? false;
				$data = json_decode($dataJSON, true);
				
				$errors = [];
				$selectedFieldId = $_POST['selectedFieldId'] ?? false;
				$selectedPageId = $_POST['selectedPageId'] ?? false;
				$currentPageId = $_POST['currentPageId'] ?? false;
				$deletedPages = json_decode($_POST['deletedPages'] ?? false, true);
				$deletedFields = json_decode($_POST['deletedFields'] ?? false, true);
				
				$tabsReordeded = isset($_POST['tabsReordered']) && $_POST['tabsReordered'] == 'true';
				$tabDeleted = false;
				$tabCreated = false;
				
				$existingDatasetTabs = [];
				$result = ze\row::query('custom_dataset_tabs', ['name', 'is_system_field'], ['dataset_id' => $datasetId]);
				while ($row = ze\sql::fetchAssoc($result)) {
					$existingDatasetTabs[$row['name']] = $row;
				}
				
				$existingDatasetFields = [];
				$result = ze\row::query('custom_dataset_fields', ['id', 'tab_name', 'is_system_field', 'db_column', 'type', 'allow_admin_to_change_visibility', 'allow_admin_to_change_export', 'protected', 'min_rows', 'max_rows', 'repeat_start_id', 'create_index'], ['dataset_id' => $datasetId]);
				while ($row = ze\sql::fetchAssoc($result)) {
					$existingDatasetFields[$row['id']] = $row;
				}
				
				//Make sure requested changes are all valid before saving...
				$sortedData = [];
				if ($errors = $this->validateDatasetChanges($data, $sortedData, $deletedPages, $deletedFields, $existingDatasetTabs, $existingDatasetFields)) {
					exit(json_encode($errors));
				}
				
				//If valid, apply changes
				//Delete tabs
				foreach ($deletedPages as $tabId) {
					if (isset($existingDatasetTabs[$tabId])) {
						$tabDeleted = true;
						ze\row::delete('custom_dataset_tabs', ['dataset_id' => $datasetId, 'name' => $tabId]);
					}
				}
				//Delete fields
				foreach ($deletedFields as $fieldId) {
					if (isset($existingDatasetFields[$fieldId])) {
						$existingDatasetTabs[$existingDatasetFields[$fieldId]['tab_name']]['_tabFieldRemoved'] = true;
						ze\row::update('custom_dataset_fields', ['protected' => false], $fieldId);
						ze\datasetAdm::deleteField($fieldId);
					}
				}
				//Create new fields
				$oldTabNameLink = [];
				$oldFieldIdLink = [];
				foreach ($sortedData as $tabIndex => &$tab) {
					$name = $tab['name'];
					if (isset($tab['_is_new'])) {
						$tabCreated = true;
						$tab['_changed'] = true;
						$sql = "
							SELECT
								IFNULL(MAX(CAST(REPLACE(name, '__custom_tab_', '') AS UNSIGNED)), 0) + 1
							FROM ". DB_NAME_PREFIX. "custom_dataset_tabs
							WHERE dataset_id = ". (int)$datasetId;
						$result = ze\sql::select($sql);
						$row = ze\sql::fetchRow($result);
						$name = '__custom_tab_'. $row[0];
						ze\row::insert('custom_dataset_tabs', ['name' => $name, 'dataset_id' => $datasetId]);
					}
					$oldTabNameLink[$tab['name']] = $name;
					$tab['name'] = $name;
					
					foreach ($tab['fields'] as $fieldIndex => &$field) {
						$fieldId = $field['id'];
						if (isset($field['_is_new'])) {
							if (isset($existingDatasetTabs[$name])) {
								$existingDatasetTabs[$name]['_tabFieldCreated'] = true;
							}
							$field['_changed'] = true;
							$fieldId = ze\row::insert('custom_dataset_fields', ['dataset_id' => $datasetId, 'tab_name' => $tab['name'], 'type' => $field['type']]);
							if ($field['type'] == 'repeat_start') {
								ze\row::update('custom_dataset_fields', array('db_column' => ze\dataset::repeatStartRowColumnName($fieldId)), $fieldId);
							}
						}
						$oldFieldIdLink[$field['id']] = $fieldId;
						$field['id'] = $fieldId;
					}
					unset($field);
				}
				unset($tab);
				
				$currentPageId = $oldTabNameLink[$currentPageId];
				if ($selectedPageId) {
					$selectedPageId = $oldTabNameLink[$selectedPageId];
				}
				if ($selectedFieldId) {
					$selectedFieldId = $oldFieldIdLink[$selectedFieldId];
				}
				
				//Update data
				$tabOrderChanged = $tabsReordeded || $tabCreated || $tabDeleted;
				foreach ($sortedData as $tabIndex => $tab) {
					$dTab = [];
					if (isset($existingDatasetTabs[$tab['name']])) {
						$dTab = $existingDatasetTabs[$tab['name']];
					}
					
					$values = [];
					if ($tabOrderChanged) {
						$values['ord'] = $tabIndex + 1;
					}
					if (isset($tab['_changed'])) {
						$values['label'] = mb_substr(trim($tab['tab_label']), 0, 32);
						if (empty($dTab['is_system_field'])) {
							if (!empty($tab['parent_field_id']) && !empty($oldFieldIdLink[$tab['parent_field_id']])) {
								$values['parent_field_id'] = $oldFieldIdLink[$tab['parent_field_id']];
							} else {
								$values['parent_field_id'] = 0;
							}
						}
					}
					if ($values) {
						ze\row::update('custom_dataset_tabs', $values, ['name' => $tab['name'], 'dataset_id' => $datasetId]);
					}
					
					$tabFieldOrderChanged = empty($dTab) || !empty($tab['_tabFieldsReordered']) || !empty($dTab['_tabFieldCreated']) || !empty($dTab['_tabFieldRemoved']);
					
					$repeatStartField = false;
					$columnUpdates = [];
					$columnIndex = 0;
					foreach ($tab['fields'] as $fieldIndex => $field) {
						$dField = [];
						if (isset($existingDatasetFields[$field['id']])) {
							$dField = $existingDatasetFields[$field['id']];
						}
						
						$values = [];
						if ($tabFieldOrderChanged) {
							$values['ord'] = $fieldIndex + 1;
							$values['tab_name'] = $tab['name'];
						}
						if ($field['type'] == 'repeat_start') {
							$minRows = !empty($field['min_rows']) ? (int)$field['min_rows'] : 1;
							if ($minRows < 1) {
								$minRows = 1;
							} elseif ($minRows > 10) {
								$minRows = 10;
							}
							$maxRows = !empty($field['max_rows']) ? (int)$field['max_rows'] : 5;
							if ($maxRows < 2) {
								$maxRows = 2;
							} elseif ($maxRows > 20) {
								$maxRows = 20;
							}
							if ($minRows > $maxRows) {
								$minRows = $maxRows;
							}
							$values['min_rows'] = $minRows;
							$values['max_rows'] = $maxRows;
						}
						
						//Do not allow other_system_fields to be edited other than ordinal
						if (isset($field['_changed']) && (!$dField || $dField['type'] != 'other_system_field')) {
							if (empty($dField['is_system_field'])) {
								$values['protected'] = !empty($field['is_protected']);
								$values['readonly'] = !empty($field['is_readonly']);
								if ($field['type'] != 'repeat_start') {
									$values['db_column'] = empty($field['db_column']) ? '' : mb_substr(trim($field['db_column']), 0, 64);
								}
								$values['height'] = empty($field['height']) ? 0 : (int)$field['height'];
								$values['width'] = empty($field['width']) ? 0 : $field['width'];
								$values['required'] = !empty($field['required']);
								$values['required_message'] = null;
								if ($values['required']) {
									$values['required_message'] = mb_substr(trim($field['required_message']), 0, 255);
								}
								$values['validation'] = 'none';
								$values['validation_message'] = null;
								if (!empty($field['validation'])) {
									$values['validation'] = $field['validation'];
									if ($field['validation'] != 'none' && !empty($field['validation_message'])) {
										$values['validation_message'] = mb_substr(trim($field['validation_message']), 0, 255);
									}
								}
								$values['organizer_visibility'] = 'none';
								if (!empty($field['show_in_organizer'])) {
									$values['organizer_visibility'] = $field['organizer_visibility'];
								}
								$values['create_index'] = false;
								$values['searchable'] = false;
								$values['sortable'] = false;
								
								if (!empty($field['show_in_organizer'])) {
									if (in_array($field['type'], ['checkbox', 'group', 'radios', 'select', 'dataset_select', 'dataset_picker', 'file_picker', 'centralised_radios', 'centralised_select']) || (isset($field['create_index']) && $field['create_index'] == 'index')
									) {
										$values['create_index'] = true;
									}
									
									if (!empty($field['searchable'])) {
										$values['searchable'] = true;
									}
									if (!empty($field['sortable']) && $values['create_index']) {
										$values['sortable'] = true;
									}
								}
								
								$values['values_source'] = empty($field['values_source']) ? '' : $field['values_source'];
								$values['values_source_filter'] = !empty($field['values_source']) && isset($field['values_source_filter']) ? mb_substr(trim($field['values_source_filter']), 0, 255) : '';
								if ($field['type']) {
									if ($field['type'] == 'dataset_select' || $field['type'] == 'dataset_picker') {
										$values['dataset_foreign_key_id'] = !empty($field['dataset_foreign_key_id']) ? $field['dataset_foreign_key_id'] : 0;
									}
								}
								if ((!$dField && ($field['type'] == 'file_picker')) 
									|| ($dField && ($dField['type'] == 'file_picker'))
								) {
									$values['multiple_select'] = !empty($field['multiple_select']);
									$values['store_file'] = !empty($field['store_file']) ? $field['store_file'] : null;
									$values['extensions'] = !empty($field['extensions']) ? mb_substr(trim($field['extensions']), 0, 255) : '';
								}
							} elseif (!empty($dField['allow_admin_to_change_visibility'])) {
								$values['organizer_visibility'] = empty($field['hide_in_organizer']) ? 'none' : 'hide';
							}
							
							$values['label'] = empty($field['field_label']) ? '' :  mb_substr(trim($field['field_label']), 0, 64);
							$values['include_in_export'] = !empty($field['include_in_export']);
							$values['autocomplete'] = !empty($field['autocomplete']);
							
							$values['note_below'] = '';
							if (!empty($field['note_below'])) {
								$values['note_below'] = mb_substr(trim($field['note_below']), 0, 255);
							}
							$values['side_note'] = '';
							if (!empty($field['side_note'])) {
								$values['side_note'] = mb_substr(trim($field['side_note']), 0, 255);
							}
						} elseif (!empty($dField['allow_admin_to_change_export'])) {
							$values['include_in_export'] = !empty($field['include_in_export']);
						}
						
						//Note: pick_items fields can change visibility
						$values['parent_id'] = 0;
						$values['admin_box_visibility'] = !empty($field['admin_box_visibility']) ? $field['admin_box_visibility'] : 'show';
						if ($values['admin_box_visibility'] == 'show_on_condition' && !empty($field['parent_id'])) {
							$values['parent_id'] = $oldFieldIdLink[$field['parent_id']];
						}
						
						if ($repeatStartField || !empty($dField['repeat_start_id'])) {
							$values['repeat_start_id'] = $repeatStartField ? $repeatStartField['id'] : 0;
						}
						if ($values) {
							ze\row::update('custom_dataset_fields', $values, $field['id']);
						}
						
						$oldName = false;
						$newRows = false;
						$oldRows = false;
						
						if ($dField) {
							$oldName = $dField['db_column'];
							if ($dField['repeat_start_id']) {
								$oldRows = $existingDatasetFields[$dField['repeat_start_id']]['max_rows'];
							}
						}
						if ($repeatStartField) {
							$newRows = $repeatStartField['max_rows'];
						}
						
						
						//Update dataset field db columns
						if (empty($dField['is_system_field'])
							&& $field['db_column']
							&& (($oldName !== $field['db_column'])
								|| ($oldRows != $newRows)
								|| ($dField && isset($values['create_index']) && ($values['create_index'] != $dField['create_index']))
							)
						) {
							//Make sure there are no clashes by renaming columns to unique name and then to new name later
							$field['temp_db_column'] = '__tmp_col_' . (++$columnIndex) . '_' . ze\ring::randomFromSet();
							ze\row::update('custom_dataset_fields', ['db_column' => $field['temp_db_column']], $field['id']);
							
							
							$columnUpdates[$field['id']] = ['db_column' => $field['db_column'], 'temp_db_column' => $field['temp_db_column'], 'new_rows' => $newRows, 'old_rows' => $oldRows];
							ze\datasetAdm::createFieldInDB($field['id'], $oldName, $newRows, $oldRows);
						}
						
						//Save field values
						if (!empty($field['lov'])
							&& !empty($field['type'])
							&& in_array($field['type'], ['checkboxes', 'radios', 'select'])
							&& empty($dField['is_system_field'])
						) {
							if (!empty($field['_deleted_lov'])) {
								foreach ($field['_deleted_lov'] as $valueId) {
									ze\row::delete('custom_dataset_field_values', $valueId);
								}
							}
							$sortedValues = $field['lov'];
							usort($sortedValues, 'ze\ray::sortByOrd');
							foreach ($sortedValues as $valueIndex => $value) {
								$lovValues = array(
									'field_id' => $field['id'],
									'ord' => $valueIndex + 1,
									'label' => mb_substr(trim($value['label']), 0, 250)
								);
								$ids = [];
								if (empty($value['_is_new'])) {
									$ids['id'] = $value['id'];
								}
								ze\row::set('custom_dataset_field_values', $lovValues, $ids);
							}
						}
						
						//Remember if we're in a repeat block or not
						if ($field['type'] == 'repeat_start') {
							$field['max_rows'] = $values['max_rows'];
							$repeatStartField = $field;
						} elseif ($field['type'] == 'repeat_end') {
							$repeatStartField = false;
						}
					}
					
					foreach ($columnUpdates as $fieldId => $update) {
						ze\row::update('custom_dataset_fields', ['db_column' => $update['db_column']], $fieldId);
						ze\datasetAdm::createFieldInDB($fieldId, $update['temp_db_column'], $update['new_rows'], $update['old_rows']);
					}
				}
				
				ze\module::sendSignal('eventDatasetUpdated', ['datasetId' => $datasetId]);
				
				echo json_encode(
					[
						'currentPageId' => $currentPageId, 
						'selectedPageId' => $selectedPageId, 
						'selectedFieldId' => $selectedFieldId
					]
				);
				break;	
				
			case 'get_centralised_lov':
				if ($method = $_POST['method'] ?? false) {
					if ($filter = $_POST['filter'] ?? false) {
						$mode = ze\dataset::LIST_MODE_FILTERED_LIST;
						$value = $filter;
					} else {
						$mode = ze\dataset::LIST_MODE_LIST;
						$value = false;
					}
					$lov = [];
					$params = explode('::', $method);
					if (ze\module::inc($params[0])) {
						$result = call_user_func($method, $mode, $value);
						$ord = 0;
						foreach ($result as $id => $label) {
							$lov[$id] = [
								'id' => $id,
								'label' => $label,
								'ord' => ++$ord
							];
						}
					}
					echo json_encode($lov);
				}
				break;
				
				
			case 'get_tab_field_record_counts':
				$recordCounts = [];
				$datasetId = $refinerId;
				if ($tabId = $_POST['tabId'] ?? false) {
					$fieldsResult = ze\row::query('custom_dataset_fields', ['id', 'db_column'], ['dataset_id' => $datasetId, 'tab_name' => $tabId]);
					while ($field = ze\sql::fetchAssoc($fieldsResult)) {
						if ($field['db_column']) {
							$recordCounts[$field['id']] = (int)ze\dataset::countDatasetFieldRecords($field['id']);
						}
					}
				}
				echo json_encode($recordCounts);
				break;
		}
	}
	
	private function exitIfCannotEditDataset($datasetId) {
		if (!ze\priv::check('_PRIV_MANAGE_DATASET')) {
			exit;
		}
		$dataset = ze\dataset::details($datasetId);
		if (!$dataset || !$dataset['extends_admin_box']) {
			exit;
		}
	}
	
	private function validateDatasetChanges($data, &$sortedData, $deletedPages, $deletedFields, $existingDatasetTabs, $existingDatasetFields) {
		$errors = [];
		
		//Fields and tabs that are saved on another field or tabs details cannot be deleted without first removing them from the other field or tab.
		$undeletableFields = [];
		$undeletableTabs = [];
		
		$sortedData = $data;
		usort($sortedData, 'ze\ray::sortByOrd');
		foreach ($sortedData as $tabIndex => $tab) {
			if (isset($tab['_changed']) || isset($tab['_is_new'])) {
				//Check tab details are valid
				//Nothing to validate for now..
			}
			if (!empty($tab['parent_field_id'])) {
				$undeletableFields[$tab['parent_field_id']] = true;
			}
			
			if (empty($tab['fields'])) {
				continue;
			}
			usort($sortedData[$tabIndex]['fields'], 'ze\ray::sortByOrd');
			foreach ($sortedData[$tabIndex]['fields'] as $fieldIndex => $field) {
				if (!empty($field['parent_id'])) {
					$undeletableFields[$field['parent_id']] = true;
				}
				
				if (isset($field['_changed']) || isset($field['_is_new'])) {
					//Check field details are valid
					//To do.. redo validation from javascript here
				}
				//Check field order is valid
				//To do.. redo validation from javascript here
			}
		}
		
		//Check tabs can be deleted
		foreach ($deletedPages as $tabId) {
			if (isset($datasetTabs[$tabId]) && !empty($datasetTabs[$tabId]['is_system_field'])) {
				$errors[] = ze\admin::phrase("Unable to delete tab \"[[tabId]]\" because it's a system tab.", ['tabId' => $tabId]);
			} elseif (isset($undeletableTabs[$tabId])) {
				$errors[] = ze\admin::phrase("Unable to delete tab \"[[tabId]]\" because it's being used by another tab or field.", ['tabId' => $tabId]);
			}
		}
		
		//Check fields can be deleted
		foreach ($deletedFields as $fieldId) {
			if (isset($datasetFields[$fieldId]) && !empty($datasetFields[$fieldId]['is_system_field'])) {
				$errors[] = ze\admin::phrase("Unable to delete field \"[[fieldId]]\" because it's a system field.", ['fieldId' => $fieldId]);
			} elseif (isset($undeletableFields[$fieldId])) {
				$errors[] = ze\admin::phrase("Unable to delete field \"[[fieldId]]\" because it's being used by another field or tab.", ['tabId' => $tabId]);
			}
		}
		
		return $errors;
	}
	
}