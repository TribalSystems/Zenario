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

class zenario_common_features__organizer__custom_tabs_and_fields_gui extends module_base_class {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		//Load details on this data-set
		if ($dataset = getDatasetDetails($refinerId)) {
			$panel['title'] = adminPhrase('Managing the dataset "[[label]]"', $dataset);
			$panel['dataset_label'] = $dataset['label'];
			$panel['items'] = array();
			
			// Load centralised list
			$moduleFilesLoaded = array();
			$tags = array();
			loadTUIX(
				$moduleFilesLoaded, $tags, $type = 'admin_boxes', 'zenario_custom_field',
				$settingGroup = '', $compatibilityClassNames = false, $runningModulesOnly = true, $exitIfError = true
			);
			$centralisedLists = $tags['zenario_custom_field']['tabs']['details']['fields']['values_source']['values'];
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
			
			// Get system field indexes
			$systemKeys = array();
			$sql = '
				SHOW KEYS
				FROM ' . DB_NAME_PREFIX . $dataset['system_table'] . '
				WHERE Key_name != "PRIMARY"';
			$result = sqlSelect($sql);
			while ($row = sqlFetchAssoc($result)) {
				$systemKeys[$row['Column_name']] = true;
			}
			
			//If this extends a system admin box, load the tabs and fields
			if ($dataset['extends_admin_box']) {
				$tabOrdinal = 0;
				$panel['existing_db_columns'] = array();
				$tabs = getRowsArray('custom_dataset_tabs', true, array('dataset_id' => $dataset['id']), 'ord');
				foreach ($tabs as $tab) {
					$tab['ord'] = ++$tabOrdinal;
					$tab['label'] = $tab['label'] ? $tab['label'] : ($tab['default_label'] ? $tab['default_label'] : '');
					
					$panel['items'][$tab['name']] = $tab;
					$fieldOrdinal = 0;
					$fields = getRowsArray('custom_dataset_fields', true, array('dataset_id' => $dataset['id'], 'tab_name' => $tab['name']), 'ord');
					foreach ($fields as $id => $field) {
						if (in_array($field['type'], array('checkboxes', 'radios', 'select', 'centralised_radios', 'centralised_select'))) {
							$fieldValueOrdinal = 0;
							$fieldValues = getDatasetFieldLOV($field, false);
							
							$field['lov'] = array();
							foreach ($fieldValues as $valueId => $value) {
								$value['ord'] = ++$fieldValueOrdinal;
								$value['id'] = $valueId;
								$field['lov'][$valueId] = $value;
							}
						} elseif ($field['type'] == 'editor') {
							$field['field_placeholder'] = 'images/form_builder/editor_placeholder.png';
						}
						
						if ($field['is_system_field']) {
							$field['css_classes'] = 'system_field';
							if (!empty($systemKeys[$field['db_column']])) {
								$field['create_index'] = true;
							}
						}
						if ($field['db_column']) {
							$panel['existing_db_columns'][$id] = $field['db_column'];
						}
						// Clean boolean values for javascript
						$field['always_show'] = (int)$field['always_show'];
						$field['create_index'] = (int)$field['create_index'];
						$field['include_in_export'] = (int)$field['include_in_export'];
						$field['is_protected'] = (int)$field['protected'];
						unset($field['protected']);
						$field['is_system_field'] = (int)$field['is_system_field'];
						$field['required'] = (int)$field['required'];
						$field['searchable'] = (int)$field['searchable'];
						$field['show_by_default'] = (int)$field['show_by_default'];
						$field['show_in_organizer'] = (int)$field['show_in_organizer'];
						$field['sortable'] = (int)$field['sortable'];
						
						$field['ord'] = ++$fieldOrdinal;
						$field['label'] = $field['label'] ? $field['label'] : ($field['default_label'] ? $field['default_label'] : '');
						$panel['items'][$tab['name']]['fields'][$id] = $field;
					}
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
						foreach ($data as $tabName => $tab) {
							if (is_array($tab)) {
								
								if (!empty($tab['remove'])) {
									// Delete tab and all fields and field values
									if (deleteRow('custom_dataset_tabs', array('dataset_id' => $dataset['id'], 'name' => $tabName, 'is_system_field' => 0)) > 0) {
										$fieldsToDelete = getRowsArray('custom_dataset_fields', 'id', array('dataset_id' => $dataset['id'], 'tab_name' => $tabName, 'is_system_field' => 0));
										if (deleteRow('custom_dataset_fields', array('dataset_id' => $dataset['id'], 'tab_name' => $tabName, 'is_system_field' => 0))) {
											foreach ($fieldsToDelete as $fieldId => $field) {
												deleteRow('custom_dataset_field_values', array('field_id' => $fieldId));
											}
										}
									}
								} else {
									$values = array(
										'ord' => $tab['ord'],
										'label' => $tab['label']
									);
									$ids = array(
										'dataset_id' => $dataset['id']
									);
									if (empty($tab['is_system_field'])) {
										$values['parent_field_id'] = $tab['parent_field_id'];
									}
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
									$tabName = $ids['name'];
									
									// Save tab
									setRow('custom_dataset_tabs', $values, $ids);
									if (!empty($tab['fields'])) {
										foreach ($tab['fields'] as $fieldId => $field) {
											if (is_array($field)) {
												if (!empty($field['remove'])) {
													if (deleteRow('custom_dataset_fields', array('id' => $fieldId, 'is_system_field' => 0)) > 0) {
														deleteRow('custom_dataset_field_values', array('field_id' => $fieldId));
													}
												} else {
													$values = array(
														'tab_name' => $tabName,
														'ord' => $field['ord'],
														'label' => $field['label'],
														'protected' => $field['is_protected'],
														'type' => $field['type'],
														'dataset_id' => $dataset['id'],
														'note_below' => $field['note_below'],
														'include_in_export' => !empty($field['include_in_export']) ? 1 : 0
													);
													$ids = array();
													if (!$field['is_system_field']) {
														$values['db_column'] = $field['db_column'];
														$values['height'] = !empty($field['height']) ? $field['height'] : 0;
														$values['width'] = !empty($field['width']) ? $field['width'] : 0;
														$values['parent_id'] = $field['parent_id'];
														$values['required'] = $field['required'];
														$values['required_message'] = $field['required'] ? $field['required_message'] : NULL;
														$values['validation'] = $field['validation'];
														$values['validation_message'] = ($field['validation'] != 'none') ? $field['validation_message'] : NULL;
														$values['show_in_organizer'] = $field['show_in_organizer'];
														$values['create_index'] = !empty($field['create_index']);
														$values['searchable'] = !empty($field['searchable']) && $values['show_in_organizer'];
														$values['sortable'] = !empty($field['sortable']) && $values['show_in_organizer'] && $values['create_index'];
														$values['show_by_default'] = !empty($field['show_by_default']) && $values['show_in_organizer'];
														$values['always_show'] = !empty($field['always_show']) && $values['show_in_organizer'];
														$values['values_source'] = $field['values_source'] ? $field['values_source'] : '';
														$values['values_source_filter'] = $field['values_source'] ? $field['values_source_filter'] : '';
													}
													
													$oldName = false;
													if (empty($field['is_new_field'])) {
														$ids['id'] = $field['id'];
														$oldField = getDatasetFieldDetails($field['id']);
														$oldName = $oldField['db_column'];
													}
													
													// Save field
													$fieldId = setRow('custom_dataset_fields', $values, $ids);
													
													if (!$field['is_system_field']) {
														createDatasetFieldInDB($fieldId, $oldName);
													}
													
													// Save values
													if (!empty($field['lov'])) {
														foreach ($field['lov'] as $valueId => $value) {
															if (is_array($value)) {
																if (!empty($value['remove'])) {
																	// Delete value
																	deleteRow('custom_dataset_field_values', $valueId);
																} else {
																	$values = array(
																		'ord' => $value['ord'],
																		'label' => $value['label'],
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
							foreach ($result as $id => $label) {
								$lov[] = array(
									'id' => $id,
									'label' => $label
								);
							}
						}
						echo json_encode($lov);
					}
					break;
			}
		}
	}
}