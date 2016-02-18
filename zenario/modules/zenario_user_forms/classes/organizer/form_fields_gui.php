<?php
/*
 * Copyright (c) 2016, Tribal Limited
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

class zenario_user_forms__organizer__form_fields_gui extends module_base_class {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$userFormId = $refinerId;
		if ($userFormId) {
			
			// Get details on this form
			$formDetails = getRow('user_forms', array('name'), $userFormId);
			$panel['title'] = adminPhrase('Form fields for "[[name]]"', $formDetails);
			
			// Get centralised lists
			$moduleFilesLoaded = array();
			$tags = array();
			loadTUIX(
				$moduleFilesLoaded, $tags, $type = 'admin_boxes', 'zenario_custom_field',
				$settingGroup = '', $compatibilityClassNames = false, $runningModulesOnly = true, $exitIfError = true
			);
			$centralisedLists = getCentralisedLists();
			$panel['centralised_lists']['values'] = array();
			$count = 1;
			foreach ($centralisedLists as $method => $listLabel) {
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
					$panel['centralised_lists']['values'][$method] = array('info' => $info, 'label' => $listLabel);
				}
			}
			
			// Get conditional fields
			$conditional_fields = zenario_user_forms::getConditionalFields($userFormId, true);
			$conditional_fields_values = array();
			foreach ($conditional_fields as $id => $field) {
				$conditional_fields_values[$id] = zenario_user_forms::getConditionalFieldValuesList($id, true);
			}
			$panel['conditional_fields'] = $conditional_fields;
			$panel['conditional_fields_values'] = $conditional_fields_values;
			
			// Get dataset fields list
			$dataset = getDatasetDetails('users');
			$tabs = array();
			$result = getRows(
				'custom_dataset_tabs', 
				array('is_system_field', 'name', 'label', 'default_label', 'ord'), 
				array('dataset_id' => $dataset['id'])
			);
			while ($row = sqlFetchAssoc($result)) {
				$row['ord'] = (int)$row['ord'];
				$tabs[$row['name']] = $row;
			}
			$fields = array();
			$result = getRows(
				'custom_dataset_fields', 
				array('id', 'tab_name', 'is_system_field', 'fundamental', 'field_name', 'type', 'db_column', 'label', 'default_label', 'ord'), 
				array(
					'dataset_id' => $dataset['id'],
					'type' => array(
						'!' => 'other_system_field'
					)
				)
			);
			while ($row = sqlFetchAssoc($result)) {
				if (isset($tabs[$row['tab_name']])) {
					$row['ord'] = (int)$row['ord'];
					$tabs[$row['tab_name']]['fields'][$row['id']] = $row;
				}
			}
			$panel['dataset_fields'] = $tabs;
			
			
			// Get form fields
			$formFields = zenario_user_forms::getUserFormFields($userFormId);
			foreach ($formFields as $id => &$formField) {
				$formField['type'] = zenario_user_forms::getFieldType($formField);
				if (in_array($formField['type'], array('checkboxes', 'radios', 'select', 'centralised_radios', 'centralised_select'))) {
					$formField['lov'] = array();
					if ($formField['user_field_id']) {
						$formField['lov'] = getDatasetFieldLOV($formField['user_field_id'], false);
					} else {
						$formField['lov'] = zenario_user_forms::getUnlinkedFieldLOV($id, false);
						
					}
				}
				// Set readonly status
				$formField['readonly_or_mandatory'] = 'none';
				if ($formField['is_required']) {
					$formField['readonly_or_mandatory'] = 'mandatory';
				} else if ($formField['is_readonly']) {
					$formField['readonly_or_mandatory'] = 'readonly';
				} else if ($formField['mandatory_condition_field_id']) {
					$formField['readonly_or_mandatory'] = 'conditional_mandatory';
				}
				
				// Set default value status
				$formField['default_value_mode'] = 'none';
				if ($formField['default_value']) {
					$formField['default_value_mode'] = 'value';
				} elseif ($formField['default_value_class_name'] && $formField['default_value_method_name']) {
					$formField['default_value_mode'] = 'method';
				}
				
				// Set remove to false
				$formField['remove'] = false;
			}
			unset($formField);
			
			$panel['items'] = $formFields;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		$formId = $refinerId;
		switch (post('mode')) {
			case 'save':
				if (post('data') && ($data = json_decode(post('data'), true))) {
					
					// Get form field properties
					$sql = 'DESCRIBE ' . DB_NAME_PREFIX . 'user_form_fields';
					$result = sqlSelect($sql);
					
					foreach ($data as $fieldId => $field) {
						if (is_array($field)) {
							if (!empty($field['remove'])) {
								deleteRow('user_form_fields', array('id' => $fieldId, 'user_form_id' => $formId));
							} else {
								// Get field properties
								$values = array();
								while ($row = sqlFetchAssoc($result)) {
									$db_column = $row['Field'];
									if (in_array($db_column, array('id', 'user_form_id'))) {
										continue;
									
									// Some fields use different names because the names are taken by fields from the custom data table
									} elseif ($db_column == 'user_field_id') {
										$values[$db_column] = empty($field['field_id']) ? 0 : $field['field_id'];
									} elseif ($db_column == 'validation') {
										$values[$db_column] = (empty($field['field_validation']) || ($field['field_validation'] == 'none')) ? null : $field['field_validation'];
									} elseif ($db_column == 'label') {
										$values[$db_column] = $field['field_label'];
									
									// Other values with identical names
									} elseif (isset($field[$db_column])) {
										$values[$db_column] = $field[$db_column];
									}
									
									// Limit length of text fields to 255 chars
									$isVarchar255 = in_array(
										$db_column, 
										array(
											'mandatory_condition_field_value',
											'visible_condition_field_value',
											'name',
											'label',
											'placeholder',
											'default_value',
											'default_value_class_name',
											'default_value_method_name',
											'default_value_param_1',
											'default_value_param_2',
											'note_to_user',
											'css_classes',
											'div_wrap_class',
											'required_error_message',
											'validation_error_message',
											'next_button_text',
											'previous_button_text',
											'description',
											'values_source',
											'values_source_filter'
										)
									);
									if ($isVarchar255 && isset($values[$db_column])) {
										$values[$db_column] = substr($values[$db_column], 0, 255);
									}
								}
								mysqli_data_seek($result, 0);
								
								
								// Corrections to data before saving to database
								if (empty($field['user_field_id'])) {
									$values['field_type'] = $field['type'];
								}
								
								$field['readonly_or_mandatory'] = isset($field['readonly_or_mandatory']) ? $field['readonly_or_mandatory'] : 'none';
								$values['is_readonly'] = ($field['readonly_or_mandatory'] == 'readonly');
								$values['is_required'] = ($field['readonly_or_mandatory'] == 'mandatory');
								
								$field['default_value_mode'] = isset($field['default_value_mode']) ? $field['default_value_mode'] : 'none';
								$values['default_value'] =
								$values['default_value_class_name'] =
								$values['default_value_method_name'] =
								$values['default_value_param_1'] =
								$values['default_value_param_2'] = null;
								if ($field['default_value_mode'] == 'value') {
									$values['default_value'] = $field['default_value'];
								} elseif ($field['default_value_mode'] == 'method') {
									$values['default_value_class_name'] = $field['default_value_class_name'];
									$values['default_value_method_name'] = $field['default_value_method_name'];
									$values['default_value_param_1'] = $field['default_value_param_1'];
									$values['default_value_param_2'] = $field['default_value_param_2'];
								}
								
								
								$keys = array();
								if (!empty($field['is_new_field'])) {
									$values['user_form_id'] = $formId;
								} else {
									$keys['id'] = $fieldId;
								}
								
								// Save field
								$fieldId = setRow('user_form_fields', $values, $keys);
								
								// Save field list values
								if (isset($field['lov']) 
									&& is_array($field['lov'])
									&& in_array($field['type'], array('select', 'radios', 'checkboxes'))
									&& !$field['field_id']
								) {
									foreach ($field['lov'] as $lovId => $lovValue) {
										if (!empty($lovValue['remove'])) {
											deleteRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', array('id' => $lovId));
										} else {
											$values = array(
												'form_field_id' => $fieldId,
												'ord' => $lovValue['ord'],
												'label' => $lovValue['label']
											);
											$keys = array();
											if (empty($lovValue['is_new_value'])) {
												$keys['id'] = $lovId;
											}
											setRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', $values, $keys);
										}
									}
								}
							}
						}
					}
				}
				break;
			
			// Validate a fields values
			//TODO tab by tab?
			//TODO validation for all tabs
			//TODO check with false, 0, etc
			case 'validate_field':
				$id = post('id');
				$removingField = post('removingField');
				$items = json_decode(post('items'), true);
				$errors = array();
				
				if ($id && $items) {
					
					// Validate field values
					if (!$removingField) {
						$field = $items[$id];
						
						// Validate details tab
						if ($field['name'] === '') {
							$errors[] = adminPhrase('Please enter a name for this form field');
						}
						if (isset($field['readonly_or_mandatory'])) {
							if ($field['readonly_or_mandatory'] == 'conditional_mandatory') {
								if (empty($field['mandatory_condition_field_id'])) {
									$errors[] = adminPhrase('Please select a mandatory on condition form field');
								} elseif ($field['mandatory_condition_field_value'] === '') {
									$errors[] = adminPhrase('Please select a mandatory on condition form field value');
								}
							} elseif ($field['readonly_or_mandatory'] == 'mandatory') {
								if (empty($field['required_error_message'])) {
									$errors[] = adminPhrase('Please enter an error message for this field');
								}
							}
						}
						if ($field['visibility'] == 'visible_on_condition') {
							if (empty($field['visible_condition_field_id'])) {
								$errors[] = adminPhrase('Please select a visible on conditional form field');
							} elseif ($field['visible_condition_field_value'] === '') {
								$errors[] = adminPhrase('Please select a visible on condition form field value');
							}
						}
						if ($field['type'] == 'calculated') {
							if (empty($field['numeric_field_1'])) {
								$errors[] = adminPhrase('Please select the first numeric field');
							}
							if (empty($field['numeric_field_2'])) {
								$errors[] = adminPhrase('Please select the second numeric field');
							}
							if (empty($field['calculation_type'])) {
								$errors[] = adminPhrase('Please select the a calculation type');
							}
						} elseif ($field['type'] == 'restatement') {
							if (empty($field['restatement_field'])) {
								$errors[] = adminPhrase('Please select the field to mirror');
							}
						}
						
						// Validate advanced tab
						if (isset($field['default_value_mode'])) {
							if ($field['default_value_mode'] == 'value') {
								if ($field['default_value'] === '') {
									$errors[] = adminPhrase('Please enter a default value.');
								}
							} elseif ($field['default_value_mode'] == 'method') {
								if (!$field['default_value_class_name']) {
									$errors[] = adminPhrase('Please enter a class name.');
								} elseif (!inc($field['default_value_class_name'])) {
									$errors[] = adminPhrase('Please enter a class name of a module that\'s running on this site.');
								}
								if (!$field['default_value_method_name']) {
									$errors[] = adminPhrase('Please enter the name of a static method.');
								} elseif (!method_exists($field['default_value_class_name'], $field['default_value_method_name'])) {
									$errors[] = adminPhrase('Please enter the name of an existing static method.');
								}
							}
						}
						
					}
					
					// Validate dependencies from other fields
					unset($items[$id]);
					foreach ($items as $fieldId => $fieldValues) {
						
						// Skip fields that have been removed
						if (!empty($fieldValues['remove'])) {
							continue;
						}
						
						if (!$removingField) {
							// Check a calculation field isn't using this value and the validation has been set to a non number type
							if ($fieldValues['type'] == 'calculated' 
								&& ($fieldValues['numeric_field_1'] == $id || $fieldValues['numeric_field_2'] == $id)
								&& !in_array($field['field_validation'], array('number', 'integer', 'floating_point'))
							) {
								$errors[] = adminPhrase(
									'The field "[[name]]" requires this field to be numeric. You must first remove this from that field.', 
									array('name' => $fieldValues['name'])
								);
							}
						} else {
							// Make sure another field doesnt depend on this field before deleting it
							
							if ((isset($fieldValues['visibility'])
									&& ($fieldValues['visibility'] == 'visible_on_condition')
									&& ((string)$fieldValues['visible_condition_field_id'] == $id)
								)
								|| (isset($fieldValues['readonly_or_mandatory'])
									&& ($fieldValues['readonly_or_mandatory'] == 'conditional_mandatory')
									&& ((string)$fieldValues['mandatory_condition_field_id'] == $id)
								)
								|| (isset($fieldValues['numeric_field_1'])
									&& ((string)$fieldValues['numeric_field_1'] == $id)
								)
								|| (isset($fieldValues['numeric_field_2']) 
									&& ((string)$fieldValues['numeric_field_2'] == $id)
								)
							) {
								$errors[] = adminPhrase(
									'Unable to delete field because the field "[[name]]" depends on it.',
									array('name' => $fieldValues['name'])
								);
							}
						}
					}
					
					echo json_encode($errors);
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