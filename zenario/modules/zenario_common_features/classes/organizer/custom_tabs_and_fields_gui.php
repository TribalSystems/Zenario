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

class zenario_common_features__organizer__custom_tabs_and_fields_gui extends ze\moduleBaseClass {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$datasetId = $refinerId;
		$this->exitIfCannotEditDataset($datasetId);
		$dataset = ze\dataset::details($datasetId);
		
		$panel['title'] = ze\admin::phrase('Editing the dataset schema "[[label]]"', $dataset);
		$panel['dataset'] = $dataset;
		$panel['priv_protect'] = ze\priv::check('_PRIV_PROTECT_UNPROTECT_DATASET_FIELD');
		
		//Whether to allow adding fields of type "group"
		$panel['use_groups_field'] = ($dataset['system_table'] == 'users');
		
		//Whether to show the "Include in export" option for this dataset
		$panel['show_include_in_export_option'] = ($dataset['system_table'] == 'users' || (ze\module::inc('zenario_location_manager') && $dataset['system_table'] == ZENARIO_LOCATION_MANAGER_PREFIX . 'locations'));
		
		//Get centralised lists for fields of type "centralised_radios" and "centralised_select"
		$centralisedLists = ze\datasetAdm::centralisedLists();
		$panel['centralised_lists']['values'] = [];
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
				FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
				INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms uf
					ON uff.user_form_id = uf.id
				INNER JOIN ' . DB_PREFIX . 'custom_dataset_fields cdf
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
			['extends_organizer_panel' => ['!' => ''], 'label_field_id' => ['!' => 0]],
			'label'
		);
		$ord = 1;
		while ($row = ze\sql::fetchAssoc($result)) {
			$panel['datasets'][$row['id']] = ['label' => $row['label'], 'ord' => $ord++];
		}
		
		//Get keys from system table, these fields will be shown as having an index
		$systemKeys = [];
		if ($dataset['system_table']) {
			$sql = '
				SHOW KEYS
				FROM ' . DB_PREFIX . $dataset['system_table'] . '
				WHERE Key_name != "PRIMARY"';
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
				$systemKeys[$row['Column_name']] = true;
			}
		}
		
		
		//Get tabs and fields from TUIX
		$moduleFilesLoaded = [];
		$tags = [];
		ze\tuix::load(
			$moduleFilesLoaded, $tags, $type = 'admin_boxes', $dataset['extends_admin_box'],
			$settingGroup = '', $compatibilityClassNames = false, $runningModulesOnly = true, $exitIfError = true
		);
		$foundFieldsInTUIX = [];
		if (!empty($tags[$dataset['extends_admin_box']]['tabs'])
			&& is_array($tags[$dataset['extends_admin_box']]['tabs'])
		) {
			//Loop through system tabs in TUIX
			$tabOrdinal = 0;
			foreach ($tags[$dataset['extends_admin_box']]['tabs'] as $tabId => $tab) {
				//Only load tabs with labels
				if (empty($tab['label']) && empty($tab['default_label'])) {
					continue;
				}
				$foundFieldsInTUIX[$tabId] = [
					'ord' => ++$tabOrdinal,
					'label' => ze::ifNull($tab['dataset_label'] ?? false, $tab['label'] ?? false)
				];
				if (!empty($tab['fields'])
					&& is_array($tab['fields'])
				) {
					//Loop through system fields in TUIX
					foreach ($tab['fields'] as $fieldId => $field) {
						$foundFieldsInTUIX[$tabId]['fields'][$fieldId] = $field;
					}
				}
			}
		}
		
		
		//Get custom data for system tabs and custom tabs
		$panel['pages'] = [];
		$panel['items'] = [];
		$tabsResult = ze\row::query('custom_dataset_tabs', true, ['dataset_id' => $dataset['id']], 'ord');
		$tabCount = 0;
		while ($tab = ze\sql::fetchAssoc($tabsResult)) {
			//Only load system tabs found in tuix
			if ($tab['is_system_field'] && !isset($foundFieldsInTUIX[$tab['name']])) {
				continue;
			}
			++$tabCount;
			$tabProperties = [
				'id' => $tab['name'],
				'ord' => $tabCount,
				'label' => $tab['label'],
				'is_system_field' => 0,
				'parent_field_id' => (int)$tab['parent_field_id'],
				'fields' => []
			];
			if ($tab['is_system_field'] && isset($foundFieldsInTUIX[$tab['name']])) {
				$tabProperties['is_system_field'] = 1;
				if ($tab['default_label'] && !$tab['label']) {
					$tabProperties['label'] = $tab['default_label'];
				}
			}
			
			//First tab automatically loads it's fields record counts
			if ($tabCount == 1) {
				$tabProperties['record_counts_fetched'] = true;
			}
			$panel['pages'][$tab['name']] = $tabProperties;
			
			
			$fieldsResult = ze\row::query('custom_dataset_fields', true, ['dataset_id' => $dataset['id'], 'tab_name' => $tab['name']], 'ord');
			$fieldCount = 0;
			while ($field = ze\sql::fetchAssoc($fieldsResult)) {
				$tuixField = false;
				if (isset($foundFieldsInTUIX[$tab['name']]['fields'][$field['field_name']])) {
					$tuixField = $foundFieldsInTUIX[$tab['name']]['fields'][$field['field_name']];
					//Don't show this field if hide_in_dataset_editor is set 
					if (!empty($tuixField['hide_in_dataset_editor'])) {
						continue;
					}
					
				//Only load system fields found in tuix	
				} elseif ($field['is_system_field']) {
					continue;
				}
				++$fieldCount;
				
				$fieldProperties = [
					'id' => (int)$field['id'],
					'page_id' => $tab['name'],
					'parent_id' => (int)$field['parent_id'],
					'is_system_field' => (int)$field['is_system_field'],
					'is_protected' => (int)$field['protected'],
					'was_protected' => (int)$field['protected'],
					'is_readonly' => (int)$field['readonly'],
					'ord' => $fieldCount,
					'label' => $field['label'] ? $field['label'] : ($field['default_label'] ? $field['default_label'] : ''),
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
					'filterable' => (int)$field['filterable'],
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
				];
				
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
						$fieldProperties['lov'][$valueId] = $value;
					}
				}
				
				if ($field['is_system_field'] && $tuixField) {
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
								$fieldProperties['grouping_name'] = $tuixField['name'];
							}
						} elseif (isset($tuixField['snippet']['html'])) {
							$fieldProperties['tuix_type'] = 'html_snippet';
						} elseif (isset($tuixField['pick_items'])) {
							$fieldProperties['tuix_type'] = 'pick_items';
						} elseif (isset($tuixField['upload'])) {
							$fieldProperties['tuix_type'] = 'upload';
							$fieldProperties['label'] = $tuixField['upload']['upload_phrase'] ?? 'Upload...';
						}
					}
					//Look for groupings on system fields
					if (!empty($tuixField['grouping'])) {
						$fieldProperties['grouping'] = $tuixField['grouping'];
					}
				}
				
				$panel['pages'][$tab['name']]['fields'][$field['id']] = 1;
				$panel['items'][$field['id']] = $fieldProperties;
			}
		}
		
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		$datasetId = $refinerId;
		$this->exitIfCannotEditDataset($datasetId);
		$dataset = ze\dataset::details($datasetId);
		
		switch (ze::post('mode')) {
			case 'save':
				$errors = [];
				
				//$dataJSON = $_POST['data'] ?? false;
				//$data = json_decode($dataJSON, true);
				//$selectedFieldId = $_POST['selectedFieldId'] ?? false;
				//$selectedPageId = $_POST['selectedPageId'] ?? false;
				$pages = json_decode($_POST['pages'] ?? false, true);
				$fields = json_decode($_POST['fields'] ?? false, true);
				$fieldsTUIX = json_decode($_POST['fieldsTUIX'] ?? false, true);
				$editingThing = $_POST['editingThing'] ?? false;
				$editingThingId = $_POST['editingThingId'] ?? false;
				$currentPageId = $_POST['currentPageId'] ?? false;
				$deletedPages = json_decode($_POST['deletedPages'] ?? false, true);
				$deletedFields = json_decode($_POST['deletedFields'] ?? false, true);
				$deletedValues = json_decode($_POST['deletedValues'] ?? false, true);
				
				$pagesReordered = !empty($_POST['pagesReordered']);
				$existingPageDeleted = false;
				$pageCreated = false;
				
				$existingPages = [];
				$result = ze\row::query('custom_dataset_tabs', ['name', 'is_system_field'], ['dataset_id' => $datasetId]);
				while ($row = ze\sql::fetchAssoc($result)) {
					$existingPages[$row['name']] = $row;
				}
				
				$existingCols = [];
				$existingFields = [];
				$result = ze\row::query('custom_dataset_fields', ['id', 'tab_name', 'is_system_field', 'db_column', 'type', 'allow_admin_to_change_visibility', 'allow_admin_to_change_export', 'protected', 'min_rows', 'max_rows', 'repeat_start_id', 'create_index'], ['dataset_id' => $datasetId]);
				while ($row = ze\sql::fetchAssoc($result)) {
					$existingFields[$row['id']] = $row;
					
					if (!$row['is_system_field'] && $row['db_column']) {
						$existingCols[$row['db_column']] = true;
					}
				}
				
				//Make sure requested changes are all valid before saving...
				if ($errors = $this->validateDatasetChanges($pages, $fields, $deletedPages, $deletedFields, $existingPages, $existingFields)) {
					exit(json_encode(['errors' => $errors]));
				}
				$sortedData = $this->getSortedData($pages, $fields);
				
				//If valid, apply changes
				foreach ($deletedPages as $tabId) {
					if (isset($existingPages[$tabId])) {
						$existingPageDeleted = true;
						break;
					}
				}
				foreach ($deletedFields as $fieldId) {
					if (isset($existingFields[$fieldId])) {
						$existingPages[$existingFields[$fieldId]['tab_name']]['field_deleted'] = true;
					}
				}
				
				$tempPageIdLink = [];
				$tempFieldIdLink = [];
				$tempValueIdLink = [];
				foreach ($sortedData as $pageIndex => &$page) {
					
					//Create new pages
					$pageId = $tempPageId = $page['id'];
					if (!isset($existingPages[$pageId])) {
						$pageCreated = true;
						$page['_new'] = true;
						$sql = "
							SELECT
								IFNULL(MAX(CAST(REPLACE(name, '__custom_tab_', '') AS UNSIGNED)), 0) + 1
							FROM ". DB_PREFIX. "custom_dataset_tabs
							WHERE dataset_id = ". (int)$datasetId;
						$result = ze\sql::select($sql);
						$row = ze\sql::fetchRow($result);
						$pageId = '__custom_tab_'. $row[0];
						ze\row::insert('custom_dataset_tabs', ['name' => $pageId, 'dataset_id' => $datasetId]);
					}
					$tempPageIdLink[$tempPageId] = $page['id'] = $pageId;
					
					foreach ($page['fields'] as $fieldIndex => $fieldId) {
						$field = &$fields[$fieldId];
						
						//Create new fields
						$tempFieldId = $field['id'];
						if (!isset($existingFields[$fieldId])) {
							if (isset($existingPages[$pageId])) {
								$existingPages[$pageId]['field_created'] = true;
							}
							$field['_new'] = true;
							$fieldId = ze\row::insert('custom_dataset_fields', ['dataset_id' => $datasetId, 'tab_name' => $pageId, 'type' => $field['type']]);
							if ($field['type'] == 'repeat_start') {
								$columName = ze\dataset::repeatStartRowColumnName($fieldId);
								$field['db_column'] = $columName;
								ze\row::update('custom_dataset_fields', ['db_column' => $columName], $fieldId);
							}
						}
						$tempFieldIdLink[$tempFieldId] = $field['id'] = $fieldId;
						
						//Create new field values
						if (in_array($field['type'], ['checkboxes', 'radios', 'select']) && !empty($field['lov']) && empty($existingFields[$fieldId]['is_system_field'])) {
							foreach ($field['lov'] as $valueId => $value) {
								$tempValueId = $valueId;
								if (!is_numeric($valueId)) {
									$valueId = ze\row::insert('custom_dataset_field_values', ['field_id' => $fieldId, 'label' => '']);
								}
								$tempValueIdLink[$tempValueId] = $valueId;
							}
						}
					}
					unset($field);
				}
				unset($page);
				
				//Keep current page / field selected on reload
				$currentPageId = $tempPageIdLink[$currentPageId];
				if ($editingThing == 'page') {
					$editingThingId = $tempPageIdLink[$editingThingId];
				} elseif ($editingThing == 'field') {
					$editingThingId = $tempFieldIdLink[$editingThingId];
				}
				
				//Update data
				$pageOrderChanged = $pagesReordered || $pageCreated || $existingPageDeleted;
				foreach ($sortedData as $pageIndex => $page) {
					$existingPage = $existingPages[$page['id']] ?? false;
					
					$values = [];
					//Update page ordinals
					if ($pageOrderChanged) {
						$values['ord'] = $pageIndex + 1;
					}
					//Update page data
					if (isset($page['_changed']) || isset($page['_new'])) {
						$values = array_merge($values, $this->getDatasetPageOptions($page, $existingPage, $fields, $tempFieldIdLink));
					}
					if ($values) {
						ze\row::update('custom_dataset_tabs', $values, ['name' => $page['id'], 'dataset_id' => $datasetId]);
					}
					
					$pageFieldOrderChanged = !$existingPage || !empty($page['fields_reordered']) || !empty($existingPage['field_created']) || !empty($existingPage['field_deleted']);
					$repeatStartField = false;
					$columnUpdates = [];
					$columnIndex = 0;
					foreach ($page['fields'] as $fieldIndex => $fieldId) {
						$field = $fields[$fieldId];
						$existingField = $existingFields[$fieldId] ?? false;
						
						//Update field ordinal / page
						$values = [];
						if ($pageFieldOrderChanged) {
							$values['ord'] = $fieldIndex + 1;
							$values['tab_name'] = $page['id'];
						}
						//Update field data
						if ($repeatStartField || !empty($existingField['repeat_start_id'])) {
							$values['repeat_start_id'] = $repeatStartField ? $repeatStartField['id'] : 0;
						}
						$values = array_merge($values, $this->getDatasetFieldOptions($field, $existingField, $tempFieldIdLink));
						if ($values) {
							ze\row::update('custom_dataset_fields', $values, $field['id']);
						}
						
						$oldName = false;
						$newRows = false;
						$oldRows = false;
						if ($existingField) {
							$oldName = $existingField['db_column'];
							if ($existingField['repeat_start_id']) {
								$oldRows = $existingFields[$existingField['repeat_start_id']]['max_rows'];
							}
						}
						if ($repeatStartField) {
							$newRows = $repeatStartField['max_rows'];
						}
						
						//Update dataset field db columns
						if (empty($existingField['is_system_field'])
							&& !empty($field['db_column'])
							&& (($oldName !== $field['db_column'])
								|| ($oldRows != $newRows)
								|| ($existingField && isset($values['create_index']) && ($values['create_index'] != $existingField['create_index']))
							)
						) {
							$field['final_db_column_we_want'] = $field['db_column'];
							
							//The ze\datasetAdm::createFieldInDB() function was designed with making one change at a time in mind
							//There's a possible issue where if you make multiple changes at once, and rename columns to each
							//others' names on mass, there will be a collision and the update will fail.
							if (($existingField && $oldName !== $field['db_column'])
							 || (!$existingField && isset($existingCols[$field['db_column']]))) {
								$field['db_column'] = '__tmp_col_' . (++$columnIndex) . '_' . ze\ring::randomFromSet();
								ze\row::update('custom_dataset_fields', ['db_column' => $field['db_column']], $field['id']);
							}
							
							//Create and/or update the field with any changes
							ze\datasetAdm::createFieldInDB($field['id'], $oldName);
							
							//Note down each column we're updating, so we can finish the updates in a "part 2" later
							$columnUpdates[] = [$field, $newRows, $oldRows];
						}
						
						//Update field values
						if (in_array($field['type'], ['checkboxes', 'radios', 'select']) && !empty($field['lov']) && empty($existingFields[$fieldId]['is_system_field'])) {
							foreach ($field['lov'] as $valueId => $value) {
								$field['lov'][$valueId]['id'] = $valueId;
							}
							usort($field['lov'], 'ze\ray::sortByOrd');
							
							foreach ($field['lov'] as $valueIndex => $value) {
								$columns = [
									'ord' => $valueIndex + 1,
									'label' => mb_substr(trim($value['label']), 0, 250, 'UTF-8')
								];
								ze\row::update('custom_dataset_field_values', $columns, $tempValueIdLink[$value['id']]);
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
					
					//Continue with updating the columns, that we had to delay earlier
					foreach ($columnUpdates as $columnUpdate) {
						[$field, $newRows, $oldRows] = $columnUpdate;
						
						//Remove any temporary names we added earlier as a work-around for renaming columns
						if ($field['final_db_column_we_want'] !== $field['db_column']) {
							$oldName = $field['db_column'];
							$field['db_column'] = $field['final_db_column_we_want'];
							
							ze\row::update('custom_dataset_fields', ['db_column' => $field['db_column']], $field['id']);
							ze\datasetAdm::createFieldInDB($field['id'], $oldName);
						}
						
						//Create multiple columns for fields in a repeating section
						if ($newRows || $oldRows) {
							ze\datasetAdm::createFieldMultiRowsInDB($field['id'], $field['db_column'], $newRows, $oldRows);
						}
					}
				}
				
				//Delete values
				foreach ($deletedValues as $valueId) {
					ze\row::delete('custom_dataset_field_values', $valueId);
				}
				//Delete fields
				foreach ($deletedFields as $fieldId) {
					if (isset($existingFields[$fieldId])) {
						ze\row::update('custom_dataset_fields', ['protected' => false], $fieldId);
						ze\datasetAdm::deleteField($fieldId);
					}
				}
				//Delete tabs
				foreach ($deletedPages as $tabId) {
					if (isset($existingPages[$tabId])) {
						ze\row::delete('custom_dataset_tabs', ['dataset_id' => $datasetId, 'name' => $tabId]);
					}
				}
				
				ze\module::sendSignal('eventDatasetUpdated', ['datasetId' => $datasetId]);
				
				echo json_encode(
					[
						'currentPageId' => $currentPageId, 
						'editingThing' => $editingThing, 
						'editingThingId' => $editingThingId
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
				if ($tabId = $_POST['pageId'] ?? false) {
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
	
	private function getSortedData($pages, $fields) {
		$sortedData = $pages;
		usort($sortedData, 'ze\ray::sortByOrd');
		foreach ($sortedData as $i => &$page) {
			$page['fields'] = array_keys($page['fields']);
			usort($page['fields'], function($a, $b) use($fields) {
				return $fields[$a]['ord'] - $fields[$b]['ord'];
			});
		}
		return $sortedData;
	}
	
	private function validateDatasetChanges($pages, $fields, $deletedPages, $deletedFields, $existingPages, $existingFields) {
		$errors = [];
		
		//Get lists of pages/fields that cannot be deleted
		$undeletableFields = [];
		$undeletablePages = [];
		foreach ($pages as $pageId => $page) {
			if (!empty($page['parent_field_id'])) {
				$undeletableFields[$page['parent_field_id']] = true;
			}
		}
		foreach ($fields as $fieldId => $field) {
			if (!empty($field['parent_id'])) {
				$undeletableFields[$field['parent_id']] = true;
			}
		}
		
		//Make we don't try and delete anything we shouldn't
		foreach ($deletedPages as $pageId) {
			if (!empty($existingPages[$pageId]['is_system_field'])) {
				$errors[] = ze\admin::phrase("Unable to delete tab \"[[tabId]]\" because it's a system tab.", ['tabId' => $pageId]);
			} elseif (isset($undeletablePages[$pageId])) {
				$errors[] = ze\admin::phrase("Unable to delete tab \"[[tabId]]\" because it's being used by another tab or field.", ['tabId' => $pageId]);
			}
		}
		foreach ($deletedFields as $fieldId) {
			if (!empty($existingFields[$fieldId]['is_system_field'])) {
				$errors[] = ze\admin::phrase("Unable to delete field \"[[fieldId]]\" because it's a system field.", ['fieldId' => $fieldId]);
			} elseif (isset($undeletableFields[$fieldId])) {
				$errors[] = ze\admin::phrase("Unable to delete field \"[[fieldId]]\" because it's being used by another field or tab.", ['fieldId' => $fieldId]);
			}
		}
		
		//Note, all other validation is client-side. It was moved there in order to speed up editing so you don't 
		//have an ajax request every time it needed to run. In the future it may be nessesary to have server-side validation as well.
		
		return $errors;
	}
	
	private function getDatasetPageOptions($page, $existingPage, $fields, $tempFieldIdLink) {
		$values = [];
		$values['label'] = mb_substr(trim($page['label']), 0, 32);
		if (empty($existingPage['is_system_field'])) {
			if (!empty($page['parent_field_id']) && !empty($tempFieldIdLink[$page['parent_field_id']])) {
				$values['parent_field_id'] = $tempFieldIdLink[$page['parent_field_id']];
			} else {
				$values['parent_field_id'] = 0;
			}
		}
		return $values;
	}
	
	private function getDatasetFieldOptions($field, $existingField, $tempFieldIdLink) {
		$values = [];
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
		if ((isset($field['_changed']) || isset($field['_new'])) && (!$existingField || $existingField['type'] != 'other_system_field')) {
			if (empty($existingField['is_system_field'])) {
				// Check permission required to change protected status
				if (ze\priv::check('_PRIV_PROTECT_UNPROTECT_DATASET_FIELD')) {
					$values['protected'] = !empty($field['is_protected']);
				}
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
				$values['filterable'] = false;
				$values['sortable'] = false;
			
				if (!empty($field['show_in_organizer'])) {
					if (in_array($field['type'], ['checkbox', 'group', 'consent', 'radios', 'select', 'dataset_select', 'dataset_picker', 'file_picker', 'centralised_radios', 'centralised_select']) || (isset($field['create_index']) && $field['create_index'] == 'index')
					) {
						$values['create_index'] = true;
					}
				
					if (!empty($field['searchable'])) {
						$values['searchable'] = true;
					}
					if (!empty($field['filterable'])) {
						$values['filterable'] = true;
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
				if ((!$existingField && ($field['type'] == 'file_picker')) 
					|| ($existingField && ($existingField['type'] == 'file_picker'))
				) {
					$values['multiple_select'] = !empty($field['multiple_select']);
					$values['store_file'] = !empty($field['store_file']) ? $field['store_file'] : null;
					$values['extensions'] = !empty($field['extensions']) ? mb_substr(trim($field['extensions']), 0, 255) : '';
				}
			} elseif (!empty($existingField['allow_admin_to_change_visibility'])) {
				$values['organizer_visibility'] = empty($field['hide_in_organizer']) ? 'none' : 'hide';
			}
		
			$values['label'] = empty($field['label']) ? '' :  mb_substr(trim($field['label']), 0, 64);
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
		} elseif (!empty($existingField['allow_admin_to_change_export'])) {
			$values['include_in_export'] = !empty($field['include_in_export']);
		}
	
		//Note: pick_items fields can change visibility
		$values['parent_id'] = 0;
		$values['admin_box_visibility'] = !empty($field['admin_box_visibility']) ? $field['admin_box_visibility'] : 'show';
		if ($values['admin_box_visibility'] == 'show_on_condition' && !empty($field['parent_id'])) {
			$values['parent_id'] = $tempFieldIdLink[$field['parent_id']] ?? 0;
		}
		
		return $values;
	}
	
}
