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
		//Load details on this data-set
		if ($dataset = getDatasetDetails($refinerId)) {
			$panel['title'] = adminPhrase('Managing the dataset "[[label]]"', $dataset);
			$panel['dataset_label'] = $dataset['label'];
			$panel['items'] = array();
			
			$panel['use_groups_field'] = ($dataset['system_table'] == 'users');
			
			// Load centralised list
			$centralisedLists = getCentralisedLists();
			$panel['centralised_lists']['values'] = array();
			$count = 1;
			foreach ($centralisedLists as $method => $label) {
				$params = explode('::', $method);
				if (inc($params[0])) {
					if ($count++ == 1) {
						if ($result = call_user_func($method, ZENARIO_CENTRALISED_LIST_MODE_LIST)) {
							foreach ($result as $id => $fieldLabel) {
								$panel['centralised_lists']['initial_lov'][] = array('id' => $id, 'label' => $fieldLabel);
							}
						}
					}
					$info = call_user_func($method, ZENARIO_CENTRALISED_LIST_MODE_INFO);
					$panel['centralised_lists']['values'][$method] = array('info' => $info, 'label' => $label);
				}
			}
			
			// Load pickable datasets
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
			
			
			// Get system field indexes
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
			
			
			//If this extends a system admin box, load the tabs and fields
			if ($dataset['extends_admin_box']) {
				$moduleFilesLoaded = array();
				$tags = array();
				loadTUIX(
					$moduleFilesLoaded, $tags, $type = 'admin_boxes', $dataset['extends_admin_box'],
					$settingGroup = '', $compatibilityClassNames = false, $runningModulesOnly = true, $exitIfError = true
				);
				
				// Get fields from TUIX
				if (!empty($tags[$dataset['extends_admin_box']]['tabs'])
					&& is_array($tags[$dataset['extends_admin_box']]['tabs'])
				) {
					// Loop through system tabs in TUIX
					$tabOrdinal = 0;
					foreach ($tags[$dataset['extends_admin_box']]['tabs'] as $tabName => $tab) {
						
						// Only load tabs with labels
						if (empty($tab['label']) && empty($tab['default_label'])) {
							continue;
						}
						
						$panel['items'][$tabName] = array(
							'ord' => ++$tabOrdinal,
							'label' => ifNull(arrayKey($tab, 'dataset_label'), arrayKey($tab, 'label')),
							'is_system_field' => 1,
							'name' => $tabName,
							'system_fields' => array()
						);
						
						if (!empty($tab['fields'])
							&& is_array($tab['fields'])
						) {
							// Loop through system fields in TUIX
							$fieldOrdinal = 0;
							foreach ($tab['fields'] as $fieldName => $field) {
								
								$panel['items'][$tabName]['system_fields'][$fieldName] = array(
									'ord' => (float)ifNull(arrayKey($field, 'ord'), ++$fieldOrdinal),
									'label' => ifNull(arrayKey($field, 'dataset_label'), arrayKey($field, 'label')),
									'is_system_field' => 1,
									'type' => arrayKey($field, 'type'),
									'grouping' => arrayKey($field, 'grouping')
								);
								
							}
						}
						
					}
				}
				
				// Get custom data for system tabs and custom tabs
				$tabs = getRowsArray('custom_dataset_tabs', true, array('dataset_id' => $dataset['id']), 'ord');
				$firstTab = true;
				foreach ($tabs as $tab) {
					if ($tab['is_system_field']) {
						if (isset($panel['items'][$tab['name']])) {
							$panel['items'][$tab['name']]['parent_field_id'] = $tab['parent_field_id'];
							if ($tab['ord']) {
								$panel['items'][$tab['name']]['ord'] = (int)$tab['ord'];
							}
							if ($tab['label']) {
								$panel['items'][$tab['name']]['label'] = $tab['label'];
							} elseif ($tab['default_label']) {
								$panel['items'][$tab['name']]['label'] = $tab['default_label'];
							}
							$panel['items'][$tab['name']]['fields'] = array();
						}
					} else {
						$panel['items'][$tab['name']] = array(
							'ord' => (int)$tab['ord'],
							'name' => $tab['name'],
							'label' => $tab['label'],
							'is_system_field' => 0,
							'parent_field_id' => (int)$tab['parent_field_id'],
							'fields' => array()
						);
					}
					if ($firstTab && isset($panel['items'][$tab['name']])) {
						$panel['items'][$tab['name']]['field_records_fetched'] = true;
					}
					
					$fields = getRowsArray('custom_dataset_fields', true, array('dataset_id' => $dataset['id'], 'tab_name' => $tab['name']));
					foreach ($fields as $fieldId => $field) {
						if ($field['is_system_field']) {
							if (!isset($panel['items'][$tab['name']]['system_fields'][$field['field_name']])) {
								continue;
							}
						}
						
						$fieldProperties = array(
							'id' => (int)$field['id'],
							'parent_id' => (int)$field['parent_id'],
							'is_system_field' => (int)$field['is_system_field'],
							'is_protected' => (int)$field['protected'],
							'ord' => (float)$field['ord'],
							'label' => $field['label'] ? $field['label'] : ($field['default_label'] ? $field['default_label'] : ''),
							'type' => $field['type'],
							'width' => $field['width'],
							'height' => $field['height'],
							'values_source' => $field['values_source'],
							'values_source_filter' => $field['values_source_filter'],
							'dataset_foreign_key_id' => $field['dataset_foreign_key_id'],
							'required' => (int)$field['required'],
							'required_message' => $field['required_message'],
							'validation' => $field['validation'],
							'validation_message' => $field['validation_message'],
							'note_below' => $field['note_below'],
							'side_note' => $field['side_note'],
							'db_column' => $field['db_column'],
							'show_in_organizer' => (int)($field['organizer_visibility'] != 'none'),
							'create_index' => (int)$field['create_index'],
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
							// Hiding system fields
							'hide_in_organizer' => ($field['organizer_visibility'] == 'hide'),
							
							'remove' => false,
						);
						//Get record count for fields on first tab. Other tab fields are loaded as their tab is clicked
						if ($firstTab && $field['db_column']) {
							$fieldProperties['record_count'] = countDatasetFieldRecords($fieldId);
						}
						
						if (in_array($field['type'], array('checkboxes', 'radios', 'select', 'centralised_radios', 'centralised_select'))) {
							// Add LOV for multi field types
							$fieldValueOrdinal = 0;
							$fieldValues = getDatasetFieldLOV($field, false);
							$fieldProperties['lov'] = array();
							foreach ($fieldValues as $valueId => $value) {
								$value['ord'] = ++$fieldValueOrdinal;
								$value['id'] = $valueId;
								$fieldProperties['lov'][$valueId] = $value;
							}
						} elseif ($field['type'] == 'editor') {
							// Show placeholder for editor types
							$fieldProperties['field_placeholder'] = 'images/admin_box_builder/editor_placeholder.png';
						}
						
						if ($field['is_system_field']) {
							$fieldProperties['css_classes'] = 'system_field';
							
							// Always show key fields as having an index
							if (!empty($systemKeys[$field['db_column']])) {
								$fieldProperties['create_index'] = true;
							}
							
							// Try to get field type for other_system_fields
							if ($field['type'] == 'other_system_field' 
								&& !empty($panel['items'][$tab['name']]['system_fields'][$field['field_name']]['type'])
							) {
								$fieldProperties['tuix_type'] = $panel['items'][$tab['name']]['system_fields'][$field['field_name']]['type'];
							}
							// Look for groupings on system fields
							if (!empty($panel['items'][$tab['name']]['system_fields'][$field['field_name']]['grouping'])) {
								$fieldProperties['grouping'] = $panel['items'][$tab['name']]['system_fields'][$field['field_name']]['grouping'];
							}
						}
						$panel['items'][$tab['name']]['fields'][$fieldId] = $fieldProperties;
					}
					unset($panel['items'][$tab['name']]['system_fields']);
					$firstTab = false;
				}
				
			}
			$panel['parent_select_list']['values'] = listCustomFields($dataset['id'], false, 'boolean_and_groups_only', false, true);
			
		}
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		//Load details on this data-set
		if (($dataset = getDatasetDetails($refinerId)) && checkPriv('_PRIV_MANAGE_DATASET')) {
			
			switch (post('mode')) {
				case 'save':
					if (post('data') && ($data = json_decode(post('data'), true))) {
						
						// Loop through fields and remove tabs and fields marked for removal. This is so a field created with the same db col as a deleted field will not have it's db column deleted if it's created before the old field is deleted.
						foreach ($data as $tabName => $tab) {
							if (!empty($tab['remove'])) {
								$tabsDeleted = deleteRow(
									'custom_dataset_tabs', 
									array('dataset_id' => $dataset['id'], 'name' => $tabName, 'is_system_field' => 0)
								);
								if ($tabsDeleted > 0) {
									$fieldsToDelete = getRowsArray(
										'custom_dataset_fields', 
										array('id'), 
										array('dataset_id' => $dataset['id'], 'tab_name' => $tabName, 'is_system_field' => 0)
									);
									
									foreach ($fieldsToDelete as $fieldId => $fieldDetails) {
										deleteDatasetField($fieldId);
									}
								}
							} else {
								if (!empty($tab['fields'])) {
									foreach ($tab['fields'] as $fieldId => $field) {
										if (!empty($field['remove'])) {
											updateRow('custom_dataset_fields', array('protected' => 0), $fieldId);
											deleteDatasetField($fieldId);
										}
									}
								}
							}
						}
						
						$tempIdLink = array();
						$tempFieldIdLink = array();
						
						// Get current dataset fields
						$datasetFields = getRowsArray(
							'custom_dataset_fields', 
							array('db_column', 'type', 'is_system_field', 'allow_admin_to_change_visibility'), 
							array('dataset_id' => $dataset['id'])
						);
						
						foreach ($data as $tabName => $tab) {
							if (is_array($tab)) {
								if (empty($tab['remove']))  {
									$values = array(
										'ord' => $tab['ord'],
										'label' => substr($tab['label'], 0, 255)
									);
									$ids = array(
										'dataset_id' => $dataset['id']
									);
									// Get next tab name for new tabs
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
									
									$oldTabName = $tabName;
									$tempIdLink[$oldTabName] = array('name' => $ids['name'], 'fields' => array());
									
									$tabName = $ids['name'];
									
									// Save tab details
									setRow('custom_dataset_tabs', $values, $ids);
									if (!empty($tab['fields'])) {
										foreach ($tab['fields'] as $fieldId => $field) {
											if (is_array($field)) {
												// Do not allow other_system_fields to be edited other than ordinal
												if (!empty($datasetFields[$fieldId]) && $datasetFields[$fieldId]['type'] == 'other_system_field') {
													setRow('custom_dataset_fields', array('ord' => $field['ord']), $fieldId);
													continue;
												}
												if (empty($field['remove'])) {
													$values = array(
														'tab_name' => $tabName,
														'ord' => $field['ord'],
														'label' => empty($field['label']) ? '' : substr($field['label'], 0, 255),
														'protected' => !empty($field['is_protected']),
														'note_below' => empty($field['note_below']) ? '' : substr($field['note_below'], 0, 255),
														'side_note' => empty($field['side_note']) ? '' : substr($field['side_note'], 0, 255),
														'include_in_export' => !empty($field['include_in_export']),
														'autocomplete' => !empty($field['autocomplete'])
													);
													
													$ids = array();
													if (!$field['is_system_field'] && empty($datasetFields[$fieldId]['is_system_field'])) {
														$values['dataset_id'] = $dataset['id'];
														$values['type'] = $field['type'];
														$values['db_column'] = substr($field['db_column'], 0, 255);
														$values['height'] = empty($field['height']) ? 0 : $field['height'];
														$values['width'] = empty($field['width']) ? 0 : $field['width'];
														
														$values['note_below'] = ($field['admin_box_visibility'] == 'hidden') ? '' : $values['note_below'];
														$values['side_note'] = ($field['admin_box_visibility'] == 'hidden') ? '' : $values['side_note'];
														
														$values['required'] = !empty($field['required']);
														$values['required_message'] = empty($field['required']) ? null : (empty($field['required_message']) ? null : substr($field['required_message'], 0, 255));
														
														$values['validation'] = $field['validation'];
														$values['validation_message'] = ($field['validation'] == 'none') ? null : substr($field['validation_message'], 0, 255);
														
														if (!empty($field['show_in_organizer'])) {
															$values['organizer_visibility'] = $field['organizer_visibility'];
														} else {
															$values['organizer_visibility'] = 'none';
														}
														
														
														
														$values['create_index'] = in_array($field['type'], array('checkbox', 'group', 'radios', 'select', 'dataset_select', 'dataset_picker', 'file_picker', 'centralised_radios', 'centralised_select')) || (in_array($field['type'], array('text', 'date')) && !empty($field['create_index'])) || (!empty($field['create_index']) && !empty($field['show_in_organizer']));
														$values['searchable'] = !empty($field['searchable']) && !empty($field['show_in_organizer']);
														$values['sortable'] = !empty($field['sortable']) && !empty($field['show_in_organizer']) && $values['create_index'];
														$values['values_source'] = empty($field['values_source']) ? '' : $field['values_source'];
														$values['values_source_filter'] = !empty($field['values_source']) && isset($field['values_source_filter']) ? substr($field['values_source_filter'], 0, 255) : '';
														
														if ($field['type']) {
															if ($field['type'] == 'dataset_select' || $field['type'] == 'dataset_picker') {
																$values['dataset_foreign_key_id'] = !empty($field['dataset_foreign_key_id']) ? $field['dataset_foreign_key_id'] : 0;
															}
														}
														
														$values['indent'] = empty($field['indent']) ? 0 : (int)$field['indent'];
														
														if ((empty($datasetFields[$fieldId]) && ($field['type'] == 'file_picker')) 
															|| (!empty($datasetFields[$fieldId]) && ($datasetFields[$fieldId]['type'] == 'file_picker'))
														) {
															$values['multiple_select'] = !empty($field['multiple_select']);
															$values['store_file'] = !empty($field['store_file']) ? $field['store_file'] : null;
															$values['extensions'] = !empty($field['extensions']) ? substr($field['extensions'], 0, 255) : '';
														}
													// Save a system fields admin box visibility if flag is set
													} elseif (!empty($datasetFields[$fieldId]['allow_admin_to_change_visibility'])) {
														if (!empty($field['hide_in_organizer'])) {
															$values['organizer_visibility'] = 'hide';
														} else {
															$values['organizer_visibility'] = 'none';
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
													$tempIdLink[$oldTabName]['fields'][$oldFieldId] = $fieldId;
													$tempFieldIdLink[$oldFieldId] = $fieldId;
													
													if (!$field['is_system_field'] 
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
														foreach ($field['lov'] as $valueId => $value) {
															if (is_array($value)) {
																
																// Delete value if remove is set
																if (!empty($value['remove'])) {
																	deleteRow('custom_dataset_field_values', $valueId);
																
																// Otherwise save value
																} else {
																	$values = array(
																		'ord' => $value['ord'],
																		'label' => substr($value['label'], 0, 255),
																		'field_id' => $fieldId
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
								}
							}
						}
						
						// Get saved dataset fields
						$datasetFields = getRowsArray(
							'custom_dataset_fields', 
							array('db_column', 'type', 'is_system_field', 'allow_admin_to_change_visibility'), 
							array('dataset_id' => $dataset['id'])
						);
						
						foreach ($tempIdLink as $tempTabName => $tempTab) {
							if (!isset($data[$tempTabName])) {
								continue;
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
								if (!isset($data[$tempTabName]['fields'][$tempFieldId])) {
									continue;
								}
								$field = $data[$tempTabName]['fields'][$tempFieldId];
								$ids = array('id' => $fieldId);
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
						
					}
					break;
				//(Handles validation)
				case 'ajaxRequestOnChange':
					$data = array();
					//Load field usage counts of fields on a tab if not loaded already
					if (post('loadingTab')) {
						$tabName = post('loadingTab');
						$fieldsResult = getRows('custom_dataset_fields', array('id', 'db_column'), array('dataset_id' => $dataset['id'], 'tab_name' => $tabName));
						while ($field = sqlFetchAssoc($fieldsResult)) {
							if ($field['db_column']) {
								$data['record_counts'][$field['id']] = (int)countDatasetFieldRecords($field['id']);
							}
						}
					}
					//Validate tab or field details
					if (post('validate')) {
						$items = json_decode(post('items'), true);
						if (post('type') == 'tab') {
							$tabName = post('id');
							if (!empty($items[$tabName]) && empty($items[$tabName]['remove'])) {
								//Check tab for errors
								if (empty($items[$tabName]['label'])) {
									$data['errors'][] = adminPhrase('Please enter a label');
								}
							}
						} elseif (post('type') == 'field') {
							$fieldId = post('id');
							$tabName = post('tab');
							$fieldTab = post('fieldTab');
							
							if (!empty($items[$tabName]['fields'][$fieldId]) && empty($items[$tabName]['fields'][$fieldId]['remove'])) {
								$field = $items[$tabName]['fields'][$fieldId];
								
								// Validate field details
								switch ($fieldTab) {
									case 'details':
										if (empty($field['is_system_field'])) {
											// Must have code name
											if (empty($field['db_column'])) {
												$data['errors'][] = adminPhrase('Please enter a code name');
											// Code name must be unique
											} elseif (preg_match('/[^a-z0-9_]/', $field['db_column'])) {
												$data['errors'][] = adminPhrase('Code name can only use characters a-z 0-9 _.');
											} else {
												unset($items[$tabName]['fields'][$fieldId]);
												$isUnique = true;
												foreach ($items as $tab2) {
													if (isset($tab2['fields']) && is_array($tab2['fields'])) {
														foreach ($tab2['fields'] as $field2) {
															if (isset($field2['db_column']) && substr($field2['db_column'], 0, 255) == substr($field['db_column'], 0, 255)) {
																$isUnique = false;
																break 2;
															}
														}
													}
												}
												if (!$isUnique) {
													$data['errors'][] = adminPhrase('The code name "[[db_column]]" is already in use in this dataset.', array('db_column' => $field['db_column']));
												}
											}
										}
										// Must choose condition if show_on_condition is chosen
										if (isset($field['admin_box_visibility']) && $field['admin_box_visibility'] == 'show_on_condition') {
											if (empty($field['parent_id'])) {
												$data['errors'][] = adminPhrase('Please select a conditional display field.');
											}
										}
										
										// Must select file storage option
										if ($field['type'] == 'file_picker') {
											if (empty($field['store_file'])) {
												$data['errors'][] = adminPhrase('Please select a file storage method.');
											}
										}
										break;
									case 'validation':
										if (empty($field['is_system_field'])) {
											
											if (!empty($field['required']) 
												&& (!isset($field['required_message']) 
													|| trim($field['required_message']) === '' 
													|| $field['required_message'] === null
												)
											) {
												$data['errors'][] = adminPhrase('Please enter a message if not complete.');
											}
											
											if ($field['validation'] != 'none' 
												&& (!isset($field['validation_message'])
													|| trim($field['validation_message']) === '' 
													|| $field['validation_message'] === null
												)
											) {
												$data['errors'][] = adminPhrase('Please enter a message if not valid.');
											}
										}
										break;
									case 'values':
										break;
								}
							}
						}
					}
					echo json_encode($data);
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
							foreach ($result as $id => $label) {
								$lov[] = array(
									'id' => $id,
									'label' => $label,
									'disabled' => true
								);
							}
						}
						echo json_encode($lov);
					}
					break;
				
				case 'get_field_record_count':
					$field = array();
					if ($fieldId = post('field_id')) {
						$field['record_count'] = countDatasetFieldRecords($fieldId);
					}
					echo json_encode($field);
					break;
			}
		}
	}
}