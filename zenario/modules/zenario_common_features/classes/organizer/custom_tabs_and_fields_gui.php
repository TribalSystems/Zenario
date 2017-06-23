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

class zenario_common_features__organizer__custom_tabs_and_fields_gui extends module_base_class {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if (($dataset = getDatasetDetails($refinerId))
			&& $dataset['extends_admin_box']
		) {
			$panel['title'] = adminPhrase('Managing the dataset "[[label]]"', $dataset);
			$panel['items'] = array();
			$panel['dataset'] = $dataset;
			
			//Whether to allow adding fields of type "group"
			$panel['use_groups_field'] = ($dataset['system_table'] == 'users');
			
			//Load centralised lists for fields of type "centralised_radios" and "centralised_select"
			$centralisedLists = getCentralisedLists();
			$panel['centralised_lists']['values'] = array();
			$count = 1;
			foreach ($centralisedLists as $method => $label) {
				$params = explode('::', $method);
				if (inc($params[0])) {
					$info = call_user_func($method, ZENARIO_CENTRALISED_LIST_MODE_INFO);
					$panel['centralised_lists']['values'][$method] = array('info' => $info, 'label' => $label);
				}
			}
			
			
			//Load pickable datasets for fields of type "dataset_select" and "dataset_picker"
			$panel['datasets'] = array();
			$result = getRows(
				'custom_datasets',
				array('id', 'label'),
				array('extends_organizer_panel' => array('!' => ''), 'label_field_id' => array('!' => 0)),
				'label'
			);
			$ord = 1;
			while ($row = sqlFetchAssoc($result)) {
				$panel['datasets'][$row['id']] = array('label' => $row['label'], 'ord' => $ord++);
			}
			
			$systemKeys = array();
			if ($dataset['system_table']) {
				$sql = '
					SHOW KEYS
					FROM ' . DB_NAME_PREFIX . $dataset['system_table'] . '
					WHERE Key_name != "PRIMARY"';
				$result = sqlSelect($sql);
				while ($row = sqlFetchAssoc($result)) {
					$systemKeys[$row['Column_name']] = true;
				}
			}
			
			
			$moduleFilesLoaded = array();
			$tags = array();
			loadTUIX(
				$moduleFilesLoaded, $tags, $type = 'admin_boxes', $dataset['extends_admin_box'],
				$settingGroup = '', $compatibilityClassNames = false, $runningModulesOnly = true, $exitIfError = true
			);
			
			$foundFieldsInTUIX = array();
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
						'label' => ifNull(arrayKey($tab, 'dataset_label'), arrayKey($tab, 'label')),
						'fields' => array()
					);
					if (!empty($tab['fields'])
						&& is_array($tab['fields'])
					) {
						// Loop through system fields in TUIX
						$fieldOrdinal = 0;
						foreach ($tab['fields'] as $fieldName => $field) {
							$foundFieldsInTUIX[$tabName]['fields'][$fieldName] = $field;
						}
					}
				}
			}
			
			//Get custom data for system tabs and custom tabs
			$tabsResult = getRows('custom_dataset_tabs', true, array('dataset_id' => $dataset['id']), 'ord');
			$tabCount = 0;
			while ($tab = sqlFetchAssoc($tabsResult)) {
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
					'fields' => array()
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
				
				
				$fieldsResult = getRows('custom_dataset_fields', true, array('dataset_id' => $dataset['id'], 'tab_name' => $tab['name']), 'ord');
				$fieldCount = 0;
				$groupings = array();
				while ($field = sqlFetchAssoc($fieldsResult)) {
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
						'hide_in_organizer' => ($field['organizer_visibility'] == 'hide'),
					);
					
					//Get record count for fields on first tab. Other tab fields are loaded as their tab is clicked
					if (($tabCount == 1) && $field['db_column']) {
						$fieldProperties['record_count'] = (int)countDatasetFieldRecords($field['id']);
					}
					
					//Add LOV for multi value field types
					if (in_array($field['type'], array('checkboxes', 'radios', 'select', 'centralised_radios', 'centralised_select'))) {
						$fieldValueOrdinal = 0;
						$fieldValues = getDatasetFieldLOV($field, false);
						$fieldProperties['lov'] = array();
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
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		//Load details on this data-set
		$datasetId = $refinerId;
		if (($dataset = getDatasetDetails($datasetId)) && checkPriv('_PRIV_MANAGE_DATASET')) {
			
			switch (post('mode')) {
				case 'save':
					if (post('data') && ($data = json_decode(post('data'), true))) {
						$errors = array();
						$selectedFieldId = post('selectedFieldId');
						$selectedTabId = post('selectedTabId');
						$currentTabId = post('currentTabId');
						$deletedTabs = json_decode(post('deletedTabs'), true);
						$deletedFields = json_decode(post('deletedFields'), true);
						
						$datasetTabs = array();
						$datasetTabsResult = getRows('custom_dataset_tabs', array('name', 'is_system_field'), array('dataset_id' => $datasetId));
						while ($datasetTab = sqlFetchAssoc($datasetTabsResult)) {
							$datasetTabs[$datasetTab['name']] = $datasetTab;
						}
						//Delete tabs
						foreach ($deletedTabs as $tabId) {
							if (isset($datasetTabs[$tabId])) {
								if (empty($datasetTabs[$tabId]['is_system_field'])) {
									deleteRow('custom_dataset_tabs', array('dataset_id' => $datasetId, 'name' => $tabId));
								} else {
									$errors[] = adminPhrase("Unable to delete tab \"[[tabId]]\" because it's a system tab.", array('tabId' => $tabId));
								}
							}
						}
						
						$datasetFields = array();
						$datasetFieldsResult = getRows('custom_dataset_fields', array('id', 'is_system_field', 'db_column', 'type', 'allow_admin_to_change_visibility', 'protected'), array('dataset_id' => $datasetId));
						while ($datasetField = sqlFetchAssoc($datasetFieldsResult)) {
							$datasetFields[$datasetField['id']] = $datasetField;
						}
						//Delete fields
						foreach ($deletedFields as $fieldId) {
							if (isset($datasetFields[$fieldId])) {
								if (empty($datasetFields[$fieldId]['is_system_field'])) {
									if ($datasetFields[$fieldId]['protected']) {
										updateRow('custom_dataset_fields', array('protected' => false), $fieldId);
									}
									deleteDatasetField($fieldId);
								} else {
									$errors[] = adminPhrase("Unable to delete field \"[[fieldId]]\" because it's a system field.", array('fieldId' => $fieldId));
								}
							}
						}
						
						$sortedData = $data;
						//Order tabs and fields by ordinals
						usort($sortedData, 'static::sortByOrd');
						foreach ($sortedData as $tabIndex => $tab) {
							if (!empty($tab['fields'])) {
								usort($sortedData[$tabIndex]['fields'], 'static::sortByOrd');
							}
						}
						
						$tempTabIdLink = array();
						$tempFieldIdLink = array();
						
						$tabOrd = 0;
						foreach ($sortedData as $tabIndex => $tab) {
							$tabId = $tab['name'];
							if ($tab && is_array($tab)) {
								$label = mb_substr(trim($tab['tab_label']), 0, 255);
								if ($label === '') {
									$label = 'Untitled';
								}
								$values = array(
									'ord' => ++$tabOrd,
									'label' => $label
								);
								$ids = array(
									'dataset_id' => $datasetId
								);
								if (!empty($tab['is_new_tab'])) {
									$sql = "
										SELECT
											IFNULL(MAX(CAST(REPLACE(name, '__custom_tab_', '') AS UNSIGNED)), 0) + 1
										FROM ". DB_NAME_PREFIX. "custom_dataset_tabs
										WHERE dataset_id = ". (int)$dataset['id'];
									$result = sqlQuery($sql);
									$row = sqlFetchRow($result);
									$ids['name'] = '__custom_tab_'. $row[0];
								} else {
									$ids['name'] = $tab['name'];
								}
								
								$oldTabId = $tabId;
								$tabId = $ids['name'];
								$tempTabIdLink[$oldTabId] = array('name' => $tabId, 'fields' => array());
								
								//Save tab details
								setRow('custom_dataset_tabs', $values, $ids);
								
								if (!empty($tab['fields'])) {
									$fieldOrd = 0;
									foreach ($tab['fields'] as $fieldIndex => $field) {
										$fieldId = $field['id'];
										if ($field && is_array($field)) {
											//Do not allow other_system_fields to be edited other than ordinal
											if (!empty($datasetFields[$fieldId]) && $datasetFields[$fieldId]['type'] == 'other_system_field') {
												setRow('custom_dataset_fields', array('ord' => ++$fieldOrd), $fieldId);
												continue;
											}
											
											$values = array(
												'tab_name' => $tabId,
												'ord' => ++$fieldOrd,
												'label' => empty($field['field_label']) ? '' : mb_substr($field['field_label'], 0, 255),
												'protected' => !empty($field['is_protected']),
												'note_below' => empty($field['note_below']) ? '' : mb_substr($field['note_below'], 0, 255),
												'side_note' => empty($field['side_note']) ? '' : mb_substr($field['side_note'], 0, 255),
												'include_in_export' => !empty($field['include_in_export']),
												'autocomplete' => !empty($field['autocomplete'])
											);
													
											$ids = array();
											if (empty($field['is_system_field']) && empty($datasetFields[$fieldId]['is_system_field'])) {
												$values['db_column'] = mb_substr($field['db_column'], 0, 255);
												$values['dataset_id'] = $dataset['id'];
												$values['type'] = $field['type'];
												$values['height'] = empty($field['height']) ? 0 : $field['height'];
												$values['width'] = empty($field['width']) ? 0 : $field['width'];
												$values['note_below'] = ($field['admin_box_visibility'] == 'hidden') ? '' : $values['note_below'];
												$values['side_note'] = ($field['admin_box_visibility'] == 'hidden') ? '' : $values['side_note'];
												$values['required'] = !empty($field['required']);
												$values['required_message'] = null;
												if ($values['required'] && !empty($field['required_message'])) {
													$values['required_message'] = mb_substr($field['required_message'], 0, 255);
												} 
												
												$values['validation'] = 'none';
												$values['validation_message'] = null;
												if (!empty($field['validation'])) {
													$values['validation'] = $field['validation'];
													if ($field['validation'] != 'none' && !empty($field['validation_message'])) {
														$values['validation_message'] = mb_substr($field['validation_message'], 0, 255);
													}
												}
												
												$values['organizer_visibility'] = 'none';
												if (!empty($field['show_in_organizer'])) {
													$values['organizer_visibility'] = $field['organizer_visibility'];
												}
												
												$values['create_index'] = false;
												$values['searchable'] = false;
												$values['sortable'] = false;
												if (in_array($field['type'], array('checkbox', 'group', 'radios', 'select', 'dataset_select', 'dataset_picker', 'file_picker', 'centralised_radios', 'centralised_select'))
													|| (isset($field['create_index']) && $field['create_index'] == 'index')
												) {
													$values['create_index'] = true;
												}
												if (!empty($field['show_in_organizer'])) {
													if (!empty($field['searchable'])) {
														$values['searchable'] = true;
													}
													if (!empty($field['sortable']) && $values['create_index']) {
														$values['sortable'] = true;
													}
												}
												
												$values['values_source'] = empty($field['values_source']) ? '' : $field['values_source'];
												$values['values_source_filter'] = !empty($field['values_source']) && isset($field['values_source_filter']) ? mb_substr($field['values_source_filter'], 0, 255) : '';
												if ($field['type']) {
													if ($field['type'] == 'dataset_select' || $field['type'] == 'dataset_picker') {
														$values['dataset_foreign_key_id'] = !empty($field['dataset_foreign_key_id']) ? $field['dataset_foreign_key_id'] : 0;
													}
												}
												if ((empty($datasetFields[$fieldId]) && ($field['type'] == 'file_picker')) 
													|| (!empty($datasetFields[$fieldId]) && ($datasetFields[$fieldId]['type'] == 'file_picker'))
												) {
													$values['multiple_select'] = !empty($field['multiple_select']);
													$values['store_file'] = !empty($field['store_file']) ? $field['store_file'] : null;
													$values['extensions'] = !empty($field['extensions']) ? mb_substr($field['extensions'], 0, 255) : '';
												}
											// Save a system fields admin box visibility if flag is set
											} elseif (!empty($datasetFields[$fieldId]['allow_admin_to_change_visibility'])) {
												$values['organizer_visibility'] = 'none';
												if (!empty($field['hide_in_organizer'])) {
													$values['organizer_visibility'] = 'hide';
												}
											}
											
											// Get old name for createDatasetFieldInDB so it can be renamed if db_column is different
											$oldName = false;
											if (empty($field['is_new_field']) && !empty($datasetFields[$fieldId])) {
												$ids['id'] = $field['id'];
												if (!empty($datasetFields[$fieldId]['db_column'])) {
													$oldName = $datasetFields[$fieldId]['db_column'];
												}
											}
											
											$oldFieldId = $fieldId;
											
											// Save field
											$fieldId = setRow('custom_dataset_fields', $values, $ids);
											
											$tempFieldIdLink[$oldFieldId] = $fieldId;
											$tempTabIdLink[$oldTabId]['fields'][$oldFieldId] = $fieldId;
											$tempFieldIdLink[$oldFieldId] = $fieldId;
											
											if (empty($field['is_system_field'])
												&& empty($datasetFields[$fieldId]['is_system_field'])
												&& (!empty($field['is_new_field']) || ($oldName && $oldName !== $field['db_column']))
											) {
												createDatasetFieldInDB($fieldId, $oldName);
											}
											
											// Save values
											if (!empty($field['lov'])
												&& !empty($field['type'])
												&& in_array($field['type'], array('checkboxes', 'radios', 'select'))
												&& empty($datasetFields[$fieldId]['is_system_field'])
											) {
												if (!empty($field['_deleted_lov'])) {
													foreach ($field['_deleted_lov'] as $valueId) {
														deleteRow('custom_dataset_field_values', $valueId);
													}
												}
												foreach ($field['lov'] as $valueId => $value) {
													if (is_array($value)) {
														$values = array(
															'field_id' => $fieldId,
															'ord' => $value['ord'],
															'label' => mb_substr($value['label'], 0, 255)
														);
														$ids = array();
														if (empty($value['is_new_value'])) {
															$ids['id'] = $value['id'];
														}
														// Save value
														setRow('custom_dataset_field_values', $values, $ids);
													}
												}
											}
										}
									}
								}
							}
						}
						
						
						// Get saved dataset fields
						$datasetFields = getRowsArray(
							'custom_dataset_fields', 
							array('db_column', 'type', 'is_system_field', 'allow_admin_to_change_visibility'), 
							array('dataset_id' => $dataset['id'])
						);
						
						foreach ($tempTabIdLink as $tempTabName => $tempTab) {
							if ($tempTabName == $currentTabId) {
								$currentTabId = $tempTab['name'];
							}
							if ($tempTabName == $selectedTabId) {
								$selectedTabId = $tempTab['name'];
							}
							
							$tab = $data[$tempTabName];
							$ids = array(
								'dataset_id' => $dataset['id'],
								'name' => $tempTab['name']
							);
							$values = array(
								'parent_field_id' => 0
							);
							if (empty($tab['is_system_field']) && !empty($tab['parent_field_id']) && !empty($tempFieldIdLink[$tab['parent_field_id']])) {
								$values['parent_field_id'] = $tempFieldIdLink[$tab['parent_field_id']];
							}
							updateRow('custom_dataset_tabs', $values, $ids);
							
							foreach ($tempTab['fields'] as $tempFieldId => $fieldId) {
								if ($tempFieldId == $selectedFieldId) {
									$selectedFieldId = $fieldId;
								}
								
								$field = $data[$tempTabName]['fields'][$tempFieldId];
								
								$ids = array(
									'id' => $fieldId
								);
								$values = array(
									'admin_box_visibility' => 'show', 
									'parent_id' => 0
								);
								if (empty($datasetFields[$fieldId]['is_system_field']) || !empty($datasetFields[$fieldId]['allow_admin_to_change_visibility'])) {
									$values['admin_box_visibility'] = $field['admin_box_visibility'];
									if ($field['admin_box_visibility'] == 'show_on_condition' && !empty($tempFieldIdLink[$field['parent_id']])) {
										$values['parent_id'] = $tempFieldIdLink[$field['parent_id']];
									}
								}
								updateRow('custom_dataset_fields', $values, $ids);
							}
						}
						
						echo json_encode(
							array(
								'errors' => $errors, 
								'currentTabId' => $currentTabId, 
								'selectedTabId' => $selectedTabId, 
								'selectedFieldId' => $selectedFieldId
							)
						);
						
					}
					break;
				
				case 'get_centralised_lov':
					if ($method = post('method')) {
						if ($filter = post('filter')) {
							$mode = ZENARIO_CENTRALISED_LIST_MODE_FILTERED_LIST;
							$value = $filter;
						} else {
							$mode = ZENARIO_CENTRALISED_LIST_MODE_LIST;
							$value = false;
						}
						$lov = array();
						$params = explode('::', $method);
						if (inc($params[0])) {
							$result = call_user_func(post('method'), $mode, $value);
							$ord = 0;
							foreach ($result as $id => $label) {
								$lov[$id] = array(
									'id' => $id,
									'label' => $label,
									'ord' => ++$ord
								);
							}
						}
						echo json_encode($lov);
					}
					break;
				
				
				case 'get_tab_field_record_counts':
					$recordCounts = array();
					$datasetId = $refinerId;
					if ($tabId = post('tabId')) {
						$fieldsResult = getRows('custom_dataset_fields', array('id', 'db_column'), array('dataset_id' => $datasetId, 'tab_name' => $tabId));
						while ($field = sqlFetchAssoc($fieldsResult)) {
							if ($field['db_column']) {
								$recordCounts[$field['id']] = (int)countDatasetFieldRecords($field['id']);
							}
						}
					}
					echo json_encode($recordCounts);
					break;
			}
		}
	}
	
	public static function sortByOrd($a, $b) {
		if ($a['ord'] == $b['ord']) {
			return 0;
		}
		return ($a['ord'] < $b['ord']) ? -1 : 1;
	}
}