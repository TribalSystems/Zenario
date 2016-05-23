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
			$formDetails = getRow('user_forms', array('name', 'title', 'translate_text'), $userFormId);
			$panel['form_title'] = $formDetails['title'];
			$panel['title'] = adminPhrase('Form fields for "[[name]]"', $formDetails);
			
			// Get whether fields on this form can have translatable text
			if ($formDetails['translate_text']) {
				$languages = getLanguages(false, true, true);
				$ord = 0;
				foreach ($languages as $languageId => $language) {
					if ($language['translate_phrases']) {
						$panel['show_translation_tab'] = true;
					}
					$panel['languages'][$languageId] = $language;
					$panel['languages'][$languageId]['ord'] = ++$ord;
				}
				$panel['translatable_fields'] = array(
					'field_label' => array(
						'ord' => 1,
						'column' => 'field_label',
						'label' => 'Label:',
					),
					'placeholder' => array(
						'ord' => 2,
						'column' => 'placeholder',
						'label' => 'Placeholder:',
					),
					'note_to_user' => array(
						'ord' => 3,
						'column' => 'note_to_user',
						'label' => 'Note below field:'
					),
					'validation_error_message' => array(
						'ord' => 4,
						'column' => 'validation_error_message',
						'label' => 'Error message when validation fails:'
					),
					'required_error_message' => array(
						'ord' => 5,
						'column' => 'required_error_message',
						'label' => 'Error message when field is incomplete:'
					),
					'description' => array(
						'ord' => 6,
						'column' => 'description',
						'label' => 'Description:'
					)
				);
			}
			
			// Get centralised lists
			$centralisedLists = getCentralisedLists();
			$panel['centralised_lists']['values'] = array();
			$count = 1;
			foreach ($centralisedLists as $method => $listLabel) {
				$params = explode('::', $method);
				if (inc($params[0])) {
					if ($count++ == 1) {
						if ($result = call_user_func($method, ZENARIO_CENTRALISED_LIST_MODE_LIST)) {
							$ord = 0;
							foreach ($result as $id => $fieldLabel) {
								$panel['centralised_lists']['initial_lov'][$id] = array(
									'id' => $id,
									'label' => $fieldLabel,
									'crm_value' => $id,
									'ord' => ++$ord
								);
							}
						}
					}
					$info = call_user_func($method, ZENARIO_CENTRALISED_LIST_MODE_INFO);
					$panel['centralised_lists']['values'][$method] = array('info' => $info, 'label' => $listLabel);
				}
			}
			
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
			$sql = '
				SELECT id, tab_name, is_system_field, fundamental, field_name, type, db_column, label, default_label, ord, values_source, values_source_filter
				FROM ' . DB_NAME_PREFIX . 'custom_dataset_fields
				WHERE dataset_id = ' . (int)$dataset['id'] . '
				AND type IN ("group", "checkbox", "checkboxes", "date", "editor", "radios", "centralised_radios", "select", "centralised_select", "text", "textarea", "url", "file_picker")
			';
			$result = sqlSelect($sql);
			while ($row = sqlFetchAssoc($result)) {
				if (isset($tabs[$row['tab_name']])) {
					$row['ord'] = (int)$row['ord'];
					if (in_array($row['type'], array('checkboxes', 'radios', 'select', 'centralised_select', 'centralised_radios'))) {
						$row['lov'] = getDatasetFieldLOV($row['id'], false);
					}
					if (!$row['label'] && $row['default_label']) {
						$row['label'] = $row['default_label'];
					}
					$tabs[$row['tab_name']]['fields'][$row['id']] = $row;
				}
			}
			foreach ($tabs as $tabName => $tab) {
				if (empty($tabs[$tabName]['fields'])) {
					unset($tabs[$tabName]);
				}
			}
			// Remove empty tabs
			$panel['dataset_fields'] = $tabs;
			
			// Check if CRM is enabled on this form
			$panel['crm_enabled'] = self::isCRMEnabled($userFormId);
			
			// Get form fields
			$pageBreakCount = 0;
			$formFields = zenario_user_forms::getUserFormFields($userFormId);
			foreach ($formFields as $id => &$formField) {
				$formField['id'] = $id;
				
				// Get field type
				$formField['type'] = zenario_user_forms::getFieldType($formField);
				
				// Get field LOV and CRM values
				if (in_array($formField['type'], array('checkboxes', 'radios', 'select', 'centralised_radios', 'centralised_select'))) {
					$formField['lov'] = array();
					if ($formField['user_field_id']) {
						$lov = getDatasetFieldLOV($formField['user_field_id'], false);
					} else {
						$lov = zenario_user_forms::getUnlinkedFieldLOV($id, false);
					}
					
					if ($panel['crm_enabled']) {
						foreach ($lov as $valueId => &$value) {
							if ($formField['type'] == 'centralised_radios' || $formField['type'] == 'centralised_select') {
								$value['crm_value'] = $valueId;
							} else {
								$value['crm_value'] = $value['label'];
							}
						}
						unset($value);
					}
					
					$formField['lov'] = $lov;
					
				// Get count of page breaks to calculate next page_break name
				} elseif ($formField['type'] == 'page_break') {
					$pageBreakCount++;
				}
				// Get readonly status
				$formField['readonly_or_mandatory'] = 'none';
				if ($formField['is_required']) {
					$formField['readonly_or_mandatory'] = 'mandatory';
				} else if ($formField['is_readonly']) {
					$formField['readonly_or_mandatory'] = 'readonly';
				} else if ($formField['mandatory_condition_field_id']) {
					$formField['readonly_or_mandatory'] = 'conditional_mandatory';
				}
				
				// Get default value status
				$formField['default_value_mode'] = 'none';
				if ($formField['default_value'] !== null && $formField['default_value'] !== '') {
					$formField['default_value_mode'] = 'value';
				} elseif ($formField['default_value_class_name'] && $formField['default_value_method_name']) {
					$formField['default_value_mode'] = 'method';
				}
				
				// Get field to filter on
				$sourceFieldID = getRow(
					ZENARIO_USER_FORMS_PREFIX . 'form_field_update_link', 
					'source_field_id', 
					array('target_field_id' => $id)
				);
				$formField['filter_on_field'] = (int)$sourceFieldID;
				
				// Set remove to false
				$formField['remove'] = false;
				
				$formField['_crm_data'] = array();
				$formField['_translations'] = array();
				
				if (!empty($panel['show_translation_tab'])) {
					$formField['_translations'] = array(
						'field_label' => array(
							'value' => $formField['field_label']
						)
					);
					if ($formField['type'] == 'section_description') {
						$formField['_translations']['description'] = array(
							'value' => $formField['description']
						);
					}
					if ($formField['type'] == 'text' || $formField['type'] == 'textarea') {
						if ($formField['type'] == 'text') {
							$formField['_translations']['validation_error_message'] = array(
								'value' => $formField['validation_error_message']
							);
						}
						$formField['_translations']['placeholder'] = array(
							'value' => $formField['placeholder']
						);
					}
					if ($formField['type'] != 'page_break' && $formField['type'] != 'section_description') {
						$formField['_translations']['note_to_user'] = array(
							'value' => $formField['note_to_user']
						);
						$formField['_translations']['required_error_message'] = array(
							'value' => $formField['required_error_message']
						);
					}
					
					// Loop through translatable fields and get translations in all translatable languages
					foreach ($formField['_translations'] as $fieldName => &$fieldDetails) {
						$fieldDetails['phrases'] = array();
						$phrases = getRows(
							'visitor_phrases', 
							array('local_text', 'language_id'), 
							array('code' => $fieldDetails['value'], 'module_class_name' => 'zenario_user_forms')
						);
						while ($row = sqlFetchAssoc($phrases)) {
							if (!empty($languages[$row['language_id']]['translate_phrases'])) {
								$fieldDetails['phrases'][$row['language_id']] = $row['local_text'];
							}
						}
					}
					unset($fieldDetails);
				}
			}
			unset($formField);
			
			// Get page break count
			$panel['pageBreakCount'] = $pageBreakCount;
			
			// Get CRM data for form fields if crm module is running
			if ($panel['crm_enabled']) {
				$sql = '
					SELECT fcf.form_field_id, fcf.field_crm_name, uff.user_field_id, uff.field_type, cdf.type
					FROM ' . DB_NAME_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_fields fcf
					INNER JOIN ' . DB_NAME_PREFIX . 'user_form_fields uff
						ON uff.user_form_id = ' . (int)$userFormId . '
						AND fcf.form_field_id = uff.id
					LEFT JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields cdf
						ON uff.user_field_id = cdf.id';
				$result = sqlSelect($sql);
				while ($row = sqlFetchAssoc($result)) {
					if (isset($formFields[$row['form_field_id']])) {
						
						// Get CRM field name
						$formFields[$row['form_field_id']]['_crm_data']['field_crm_name'] = $row['field_crm_name'];
						$formFields[$row['form_field_id']]['_crm_data']['send_to_crm'] = true;
						
						$type = zenario_user_forms::getFieldType($row);
						
						// Get multi field CRM values
						if (in_array($type, array('checkboxes', 'select', 'radios', 'centralised_select', 'centralised_radios', 'checkbox'))) {
							$foundCRMValues = array();
							
							$crmValues = getRows(
								ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_field_values', 
								array(
									'form_field_value_dataset_id', 
									'form_field_value_unlinked_id', 
									'form_field_value_centralised_key', 
									'form_field_value_checkbox_state',
									'value'
								), 
								array('form_field_id' => $row['form_field_id'])
							);
							
							while ($crmValue = sqlFetchAssoc($crmValues)) {
								if ($type == 'checkbox') {
									$formFields[$row['form_field_id']]['_crm_data']['values'][$crmValue['form_field_value_checkbox_state']] = array(
										'label' => $crmValue['form_field_value_checkbox_state'],
										'crm_value' => $crmValue['value']
									);
								} elseif ($type == 'centralised_select' || $type == 'centralised_radios') {
									if (isset($formFields[$row['form_field_id']]['lov'][$crmValue['form_field_value_centralised_key']])) {
										$formFields[$row['form_field_id']]['lov'][$crmValue['form_field_value_centralised_key']]['crm_value'] = $crmValue['value'];
									}
								} else {
									if ($row['user_field_id']) {
										if (isset($formFields[$row['form_field_id']]['lov'][$crmValue['form_field_value_dataset_id']])) {
											$formFields[$row['form_field_id']]['lov'][$crmValue['form_field_value_dataset_id']]['crm_value'] = $crmValue['value'];
										}
									} else {
										if (isset($formFields[$row['form_field_id']]['lov'][$crmValue['form_field_value_unlinked_id']])) {
											$formFields[$row['form_field_id']]['lov'][$crmValue['form_field_value_unlinked_id']]['crm_value'] = $crmValue['value'];
										}
									}
								}
							}
						}
					}
				}
			}
			
			$panel['items'] = $formFields;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		// Get form ID
		$formId = $refinerId;
		$formDetails = getRow('user_forms', array('translate_text'), $formId);
		$languages = getLanguages(false, true, true);
		
		switch (post('mode')) {
			case 'save':
				if (post('data') && ($data = json_decode(post('data'), true))) {
					
					// Check if CRM is enabled for this form
					$crmEnabled = self::isCRMEnabled($formId);
					
					// Link between temporary Ids and real Ids
					$tempFieldIdLink = array();
					$tempValuesIdLink = array();
					$fieldsToDelete = array();
					
					foreach ($data as $fieldId => $field) {
						if (is_array($field)) {
							if (!empty($field['remove'])) {
								$fieldsToDelete[] = $fieldId;
							} else {
								// Save phrases
								if ($formDetails['translate_text'] && !empty($field['_translations'])) {
									
									// field values = $field['field_label'] (Email:)
									// translation values = $field['_translations']['field_label']['phrases']['zh-hant'] (Email (chinese):)
									
									$translatableFields = array('label', 'placeholder', 'note_to_user', 'required_error_message', 'validation_error_message', 'description');
									
									// Update phrase code if phrases are changed to keep translation chain
									$fieldsToTranslate = getRow('user_form_fields', $translatableFields, $fieldId);
									
									foreach ($translatableFields as $index => $name) {
										
										$oldCode = '';
										if ($fieldsToTranslate) {
											$oldCode = $fieldsToTranslate[$name];
										}
										
										if ($name == 'label') {
											$name = 'field_label';
										}
										
										// Check if old value has more than 1 entry in any translatable field
										$identicalPhraseFound = false;
										if ($oldCode) {
											$sql = '
												SELECT 
													id
												FROM 
													'.DB_NAME_PREFIX.'user_form_fields
												WHERE ( 
														label = "'.sqlEscape($oldCode).'"
													OR
														placeholder = "'.sqlEscape($oldCode).'"
													OR
														note_to_user = "'.sqlEscape($oldCode).'"
													OR
														required_error_message = "'.sqlEscape($oldCode).'"
													OR
														validation_error_message = "'.sqlEscape($oldCode).'"
													OR
														description = "'.sqlEscape($oldCode).'"
												)';
											$result = sqlSelect($sql);
											if (sqlNumRows($result) > 1) {
												$identicalPhraseFound = true;
											}
										}
										
										$field[$name] = isset($field[$name]) ? $field[$name] : '';
										
										// If another field is using the same phrase code...
										if ($identicalPhraseFound) {
											foreach ($languages as $language) {
												// Create or overwrite new phrases with the new english code
												$setArray = array();
												if (!empty($language['translate_phrases'])) {
													$setArray['local_text'] = !empty($field['_translations'][$name]['phrases'][$language['id']]) ? $field['_translations'][$name]['phrases'][$language['id']] : null;
												}
												setRow('visitor_phrases', 
													$setArray,
													array(
														'code' => $field[$name],
														'module_class_name' => 'zenario_user_forms',
														'language_id' => $language['id']
													)
												);
											}
										} else {
											// If nothing else is using the same phrase code...
											if (!checkRowExists('visitor_phrases', array('code' => $field[$name], 'module_class_name' => 'zenario_user_forms'))) {
												updateRow(
													'visitor_phrases', 
													array('code' => $field[$name]), 
													array('code' => $oldCode, 'module_class_name' => 'zenario_user_forms')
												);
												foreach($languages as $language) {
													if ($language['translate_phrases'] && !empty($field['_translations'][$name]['phrases'][$language['id']])) {
														setRow('visitor_phrases',
															array(
																'local_text' => ($field['_translations'][$name]['phrases'][$language['id']] !== '' ) ? $field['_translations'][$name]['phrases'][$language['id']] : null), 
															array(
																'code' => $field[$name], 
																'module_class_name' => 'zenario_user_forms', 
																'language_id' => $language['id']));
													}
													
												}
											// If code already exists, and nothing else is using the code, delete current phrases, and update/create new translations
											} else {
												deleteRow('visitor_phrases', array('code' => $oldCode, 'module_class_name' => 'zenario_user_forms'));
												if (!empty($field[$name])) {
													foreach($languages as $language) {
														$setArray = array();
														if (!empty($language['translate_phrases'])) {
															$setArray['local_text'] = ($field['_translations'][$name]['phrases'][$language['id']] !== '' ) ? $field['_translations'][$name]['phrases'][$language['id']] : null;
														}
														setRow('visitor_phrases',
															$setArray,
															array(
																'code' => $field[$name], 
																'module_class_name' => 'zenario_user_forms', 
																'language_id' => $language['id']
															)
														);
													}
												}
											}
										}
									}
								}
								
								// Get field properties
								$values = array();
								$keys = array();
								
								
								if (!empty($field['is_new_field'])) {
									$values['user_form_id'] = $formId;
								} else {
									$keys['id'] = $fieldId;
								}
								
								$values['user_field_id'] =  empty($field['field_id']) ? 0 : $field['field_id'];
								$values['ord'] = (int)$field['ord'];
								
								if (empty($field['field_id'])) {
									$values['field_type'] = $field['type'];
								}
								
								$field['readonly_or_mandatory'] = isset($field['readonly_or_mandatory']) ? $field['readonly_or_mandatory'] : 'none';
								$values['is_readonly'] = isset($field['readonly_or_mandatory']) && ($field['readonly_or_mandatory'] == 'readonly');
								$values['is_required'] = isset($field['readonly_or_mandatory']) && ($field['readonly_or_mandatory'] == 'mandatory');
								
								$values['visibility'] = $field['visibility'] = !empty($field['visibility']) ? $field['visibility'] : 'visible';
								
								$values['name'] = substr($field['name'], 0, 255);
								$values['label'] = substr($field['field_label'], 0, 255);
								$values['size'] = !empty($field['size']) ? $field['size'] : 'medium';
								$values['placeholder'] = !empty($field['placeholder']) ? substr($field['placeholder'], 0, 255) : null;
								
								$field['default_value_mode'] = !empty($field['default_value_mode']) ? $field['default_value_mode'] : 'none';
								$values['default_value'] = ($field['default_value_mode'] == 'value' && isset($field['default_value'])) ? $field['default_value'] : null;
								$values['default_value_class_name'] = ($field['default_value_mode'] == 'method' && !empty($field['default_value_class_name'])) ? $field['default_value_class_name'] : null;
								$values['default_value_method_name'] = ($field['default_value_mode'] == 'method' && !empty($field['default_value_method_name'])) ? $field['default_value_method_name'] : null;
								$values['default_value_param_1'] = ($field['default_value_mode'] == 'method' && !empty($field['default_value_param_1'])) ? $field['default_value_param_1'] : null;
								$values['default_value_param_2'] = ($field['default_value_mode'] == 'method' && !empty($field['default_value_param_2'])) ? $field['default_value_param_2'] : null;
								
								$values['note_to_user'] = !empty($field['note_to_user']) ? substr($field['note_to_user'], 0, 255) : null;
								$values['css_classes'] = !empty($field['css_classes']) ? substr($field['css_classes'], 0, 255) : null;
								$values['div_wrap_class'] = !empty($field['div_wrap_class']) ? substr($field['div_wrap_class'], 0, 255) : null;
								
								$values['required_error_message'] = ($field['readonly_or_mandatory'] == 'mandatory' || $field['readonly_or_mandatory'] == 'conditional_mandatory') && !empty($field['required_error_message']) ? $field['required_error_message'] : null;
								
								$values['validation'] = (!empty($field['field_validation']) && ($field['field_validation'] != 'none')) ? $field['field_validation'] : null;
								$values['validation_error_message'] = $values['validation'] && !empty($field['validation_error_message']) ? $field['validation_error_message'] : null;
								
								$values['next_button_text'] = !empty($field['next_button_text']) ? substr($field['next_button_text'], 0, 255) : null;
								$values['previous_button_text'] = !empty($field['previous_button_text']) ? substr($field['previous_button_text'], 0, 255) : null;
								$values['description'] = !empty($field['description']) ? substr($field['description'], 0, 255) : null;
								
								$values['numeric_field_1'] = !empty($field['numeric_field_1']) ? $field['numeric_field_1'] : 0;
								$values['numeric_field_2'] = !empty($field['numeric_field_2']) ? $field['numeric_field_2'] : 0;
								$values['calculation_type'] = !empty($field['calculation_type']) ? $field['calculation_type'] : null;
								$values['restatement_field'] = !empty($field['restatement_field']) ? $field['restatement_field'] : 0;
								
								$values['values_source'] = !empty($field['values_source']) ? substr($field['values_source'], 0, 255) : '';
								$values['values_source_filter'] = $values['values_source'] && !empty($field['values_source_filter']) ? substr($field['values_source_filter'], 0, 255) : '';
								
								$oldFieldId = $fieldId;
								
								// Save field
								$fieldId = setRow('user_form_fields', $values, $keys);
								
								// Store link between temp ids and real ids
								$tempFieldIdLink[$oldFieldId] = array(
									'id' => $fieldId,
									'field' => $field
								);
								
								
								// Save field list values
								if (isset($field['lov']) 
									&& is_array($field['lov'])
									&& in_array($field['type'], array('select', 'radios', 'checkboxes'))
									&& empty($field['field_id'])
								) {
									foreach ($field['lov'] as $lovId => $lovValue) {
										if (!empty($lovValue['remove'])) {
											zenario_user_forms::deleteFormFieldValue($lovId);
										} else {
											$values = array(
												'form_field_id' => $fieldId,
												'ord' => $lovValue['ord'],
												'label' => substr($lovValue['label'], 0, 255)
											);
											$keys = array();
											if (empty($lovValue['is_new_value'])) {
												$keys['id'] = $lovId;
											}
											$trueLOVId = setRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', $values, $keys);
											
											$tempValueIdLink[$lovId] = $trueLOVId;
										}
									}
								}
								
								
								// Save field CRM data
								if ($crmEnabled) {
									if (!empty($field['_crm_data']['send_to_crm']) && !empty($field['_crm_data']['field_crm_name'])) {
										
										$formCRMValues = array('field_crm_name' => $field['_crm_data']['field_crm_name']);
										
										if (
											!checkRowExists(
												ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_fields', 
												array('form_field_id' => $fieldId, 'field_crm_name' => $field['_crm_data']['field_crm_name'])
											)
										) {
											// Get next ordinal
											$maxOrdinalSQL = '
												SELECT MAX(fcf.ordinal)
												FROM ' . DB_NAME_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_fields fcf
												INNER JOIN ' . DB_NAME_PREFIX . 'user_form_fields uff
													ON uff.user_form_id = ' . (int)$formId . '
													AND fcf.form_field_id = uff.id
												WHERE fcf.field_crm_name = "' . sqlEscape($field['_crm_data']['field_crm_name']) . '"';
											$maxOrdinalResult = sqlSelect($maxOrdinalSQL);
											$maxOrdinalRow = sqlFetchRow($maxOrdinalResult);
											$formCRMValues['ordinal'] = $maxOrdinalRow[0] ? $maxOrdinalRow[0] + 1 : 1;
										}
										
										setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_fields', $formCRMValues, array('form_field_id' => $fieldId));
										
										
										if ($field['type'] == 'checkbox' && !empty($field['_crm_data']['values'])) {
											foreach ($field['_crm_data']['values'] as $lovId => $lovValue) {
												$state = $lovId ? 1 : 0;
												setRow(
													ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_field_values',
													array(
														'value' => $lovValue['crm_value'],
														'form_field_value_dataset_id' => null,
														'form_field_value_unlinked_id' => null,
														'form_field_value_centralised_key' => null
													),
													array(
														'form_field_value_checkbox_state' => $state,
														'form_field_id' => $fieldId
													)
												);
											}
											
										} else {
											// Save values
											if (isset($field['lov'])) {
												
												foreach ($field['lov'] as $lovId => $lovValue) {
													
													if ($field['type'] == 'centralised_select' || $field['type'] == 'centralised_radios') {
														setRow(
															ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_field_values',
															array(
																'value' => $lovValue['crm_value'],
																'form_field_value_dataset_id' => null,
																'form_field_value_unlinked_id' => null,
																'form_field_value_checkbox_state' => null
															),
															array(
																'form_field_value_centralised_key' => $lovId,
																'form_field_id' => $fieldId
															)
														);
													} elseif (!empty($field['field_id'])) {
														setRow(
															ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_field_values',
															array(
																'value' => $lovValue['crm_value'],
																'form_field_value_centralised_key' => null,
																'form_field_value_unlinked_id' => null,
																'form_field_value_checkbox_state' => null
															),
															array(
																'form_field_value_dataset_id' => $lovId,
																'form_field_id' => $fieldId
															)
														);
													} else {
														// Get actual ID if the value was using a temp ID e.g. t1
														if (isset($tempValueIdLink[$lovId])) {
															$lovId = $tempValueIdLink[$lovId];
															
															setRow(
																ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_field_values',
																array(
																	'value' => $lovValue['crm_value'],
																	'form_field_value_centralised_key' => null,
																	'form_field_value_dataset_id' => null,
																	'form_field_value_checkbox_state' => null
																),
																array(
																	'form_field_value_unlinked_id' => $lovId,
																	'form_field_id' => $fieldId
																)
															);
														}
													}
												}
											}
										}
										
									} else {
										// Delete CRM data
										zenario_crm_form_integration::deleteFieldCRMData($fieldId);
									}
								}
							}
						}
					}
					
					$currentFormFields = zenario_user_forms::getUserFormFields($formId);
					
					foreach ($tempFieldIdLink as $oldFieldId => $details) {
						
						$field = $details['field'];
						$id = $details['id'];
						
						if (!empty($field['filter_on_field'])) {
							setRow(
								ZENARIO_USER_FORMS_PREFIX . 'form_field_update_link', 
								array('source_field_id' => $field['filter_on_field']), 
								array('target_field_id' => $id)
							);
						} else {
							deleteRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_update_link', array('target_field_id' => $id));
						}
						
						// Save conditional field values
						$values = array();
						
						$values['visible_condition_field_id'] = ($field['visibility'] == 'visible_on_condition') && !empty($field['visible_condition_field_id']) ? $tempFieldIdLink[$field['visible_condition_field_id']]['id'] : 0;
						$values['visible_condition_field_value'] = ($field['visibility'] == 'visible_on_condition') && !empty($field['visible_condition_field_id']) && $field['visible_condition_field_value'] !== '' ? $tempValueIdLink[$field['visible_condition_field_value']] : null;
						
						$values['mandatory_condition_field_id'] = !empty($field['mandatory_condition_field_id']) ? $tempFieldIdLink[$field['mandatory_condition_field_id']]['id'] : 0;
						$values['mandatory_condition_field_value'] = !empty($field['mandatory_condition_field_id']) ? $field['mandatory_condition_field_value'] : null;
						
						setRow('user_form_fields', $values, $id);
						
						// Migrate any data
						if (!empty($details['field']['_migrate_responses_from'])) {
							// Check fields are the same type
							if ($currentFormFields[$details['id']] 
								&& $currentFormFields[$details['field']['_migrate_responses_from']]
								&& (zenario_user_forms::getFieldType($currentFormFields[$details['id']]) == zenario_user_forms::getFieldType($currentFormFields[$details['field']['_migrate_responses_from']]))
							) {
								// Delete existing responses
								deleteRow(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', array('form_field_id' => $details['id']));
								
								// Move responses
								updateRow(
									ZENARIO_USER_FORMS_PREFIX . 'user_response_data', 
									array('form_field_id' => $details['id']),
									array('form_field_id' => $details['field']['_migrate_responses_from'])
								);
							}
						}
					}
					
					// Delete removed fields
					foreach ($fieldsToDelete as $fieldId) {
						zenario_user_forms::deleteFormField($fieldId);
					}
				}
				break;
			
			// Validate a fields values
			case 'validate_field':
				$id = post('id');
				$removingField = post('removingField');
				$field_tab = post('field_tab');
				$items = json_decode(post('items'), true);
				$errors = array();
				
				if ($id && $items) {
					
					// Validate field values
					if (!$removingField) {
						$field = $items[$id];
					
						switch ($field_tab) {
							case 'details':
								
								if ($field['type'] == 'checkbox' || $field['type'] == 'group') {
									if (empty($field['field_label'])) {
										$errors[] = adminPhrase('Please enter a label for this checkbox.');
									}
								}
								
								if ($field['visibility'] == 'visible_on_condition') {
									
									$visibleOnConditionField = isset($items[$field['visible_condition_field_id']]) ? $items[$field['visible_condition_field_id']] : false;
									
									if (empty($field['visible_condition_field_id'])) {
										$errors[] = adminPhrase('Please select a visible on conditional form field.');
									} elseif ($field['visible_condition_field_value'] === '' && ($visibleOnConditionField['type'] == 'checkbox' || $visibleOnConditionField['type'] == 'group')) {
										$errors[] = adminPhrase('Please select a visible on condition form field value.');
									}
								}
								
								if (isset($field['readonly_or_mandatory'])) {
									// Make sure conditional mandatory and conditional visible make sense
									if ($field['readonly_or_mandatory'] == 'mandatory' 
										|| $field['readonly_or_mandatory'] == 'conditional_mandatory'
									) {
										if (empty($field['required_error_message'])) {
											$errors[] = adminPhrase('Please enter an error message when this field is incomplete.');
										}
									}
									
									if ($field['readonly_or_mandatory'] == 'mandatory') {
										if ($field['visibility'] == 'hidden') {
											$errors[] = adminPhrase('A field cannot be mandatory while hidden.');
										} elseif ($field['visibility'] == 'visible_on_condition') {
											$errors[] = adminPhrase('A field cannot be mandatory while hidden. If you want the field to only be mandatory when hidden please set this field as "Mandatory on condition".');
										}
									} elseif ($field['readonly_or_mandatory'] == 'conditional_mandatory') {
										
										$mandatoryOnConditionField = isset($items[$field['mandatory_condition_field_id']]) ? $items[$field['mandatory_condition_field_id']] : false;
										
										if (empty($field['mandatory_condition_field_id'])) {
											$errors[] = adminPhrase('Please select a mandatory on condition form field.');
										} elseif ($field['mandatory_condition_field_value'] === '' && ($mandatoryOnConditionField['type'] == 'checkbox' || $mandatoryOnConditionField['type'] == 'group')) {
											$errors[] = adminPhrase('Please select a mandatory on condition form field value.');
										}
										
										if ($field['visibility'] == 'hidden') {
											$errors[] = adminPhrase('A field cannot be mandatory while hidden."');
										} elseif ($field['visibility'] == 'visible_on_condition'
											&& $field['mandatory_condition_field_id']
											&& $field['visible_condition_field_id']
											&& (($field['mandatory_condition_field_id'] != $field['visible_condition_field_id'])
												|| ($field['mandatory_condition_field_value'] != $field['visible_condition_field_value'])
											)
										) {
											$errors[] = adminPhrase('A field cannot be mandatory while hidden. If this field is both mandatory and visible on a condition, both fields and values must be the same.');
										}
									}
								}
								
								if (!empty($field['field_validation']) && $field['field_validation'] != 'none') {
									if (empty($field['validation_error_message'])) {
										$errors[] = adminPhrase('Please enter a validation error message for this field.');
									}
								}
								
								if ($field['type'] == 'calculated') {
									if (empty($field['numeric_field_1'])) {
										$errors[] = adminPhrase('Please select the first numeric field.');
									}
									if (empty($field['numeric_field_2'])) {
										$errors[] = adminPhrase('Please select the second numeric field.');
									}
									if (empty($field['calculation_type'])) {
										$errors[] = adminPhrase('Please select the a calculation type.');
									}
								} elseif ($field['type'] == 'restatement') {
									if (empty($field['restatement_field'])) {
										$errors[] = adminPhrase('Please select the field to mirror.');
									}
								}
								break;
							case 'advanced':
								
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
								break;
							case 'values':
								
								break;
							case 'crm':
								if (!empty($field['_crm_data']['send_to_crm'])) {
									if (empty($field['_crm_data']['field_crm_name'])) {
										$errors[] = adminPhrase('Please enter a CRM field name.');
									}
								}
								break;
						}
						
						// Always validate name since it isn't on a tab
						if (empty($field['field_id']) && $field['name'] === '') {
							$errors[] = adminPhrase('Please enter a name for this form field');
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
						$ord = 0;
						foreach ($result as $id => $label) {
							if (post('type') == 'object') {
								$lov[$id] = array(
									'id' => $id,
									'label' => $label,
									'ord' => ++$ord,
									'crm_value' => $id
								);
							} else {
								$lov[] = array(
									'id' => $id,
									'label' => $label
								);
							}
						}
					}
					echo json_encode($lov);
				}
				break;
			
			// Get a count of user responses saved by this field
			case 'get_field_response_count':
				$data = array();
				if ($fieldId = (int)post('id')) {
					$data['count'] = (int)selectCount(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', array('form_field_id' => $fieldId));
				}
				echo json_encode($data);
				break;
		}
	}
	
	public static function isCRMEnabled($formId) {
		if (inc('zenario_crm_form_integration')) {
			$formCRMData = zenario_crm_form_integration::getFormCrmData($formId);
			if ($formCRMData['enable_crm_integration']) {
				return true;
			}
		}
		return false;
	}
}