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

class zenario_user_forms__organizer__form_fields_gui extends module_base_class {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$formId = $refinerId;
		if ($formId) {
			// Get form details
			$form = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('name', 'type', 'title', 'translate_text', 'hide_final_page_in_page_switcher', 'page_end_name'), $formId);
			$panel['title'] = adminPhrase('Form fields for "[[name]]"', $form);
			$panel['form'] = array(
				'title' => $form['title'],
				'type' => $form['type']
			);
			// If this form is translatable, pass the languages and translatable fields
			if ($form['translate_text']) {
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
					'label' => array(
						'ord' => 1,
						'column' => 'label',
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
					'field_validation_error_message' => array(
						'ord' => 4,
						'column' => 'field_validation_error_message',
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
			
			// Get dataset tabs and fields list
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
			
			$sql = '
				SELECT id, tab_name, is_system_field, fundamental, field_name, type, db_column, label, default_label, ord, values_source, values_source_filter
				FROM ' . DB_NAME_PREFIX . 'custom_dataset_fields
				WHERE dataset_id = ' . (int)$dataset['id'] . '
				AND type IN ("group", "checkbox", "checkboxes", "date", "editor", "radios", "centralised_radios", "select", "centralised_select", "text", "textarea", "url", "file_picker")';
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
			// Do not pass tabs with no fields
			foreach ($tabs as $tabName => $tab) {
				if (empty($tabs[$tabName]['fields'])) {
					unset($tabs[$tabName]);
				}
			}
			$panel['dataset_fields'] = $tabs;
			
			// Check if CRM is enabled on this form
			$panel['crm_enabled'] = static::isCRMEnabled($formId);
			
			// Get form fields
			$pageBreakCount = 0;
			$fields = zenario_user_forms::getFields($formId);
			foreach ($fields as $fieldId => &$field) {
				// Make sure any number fields are passed as numbers not strings
				foreach ($field as &$prop) {
					if (is_numeric($prop)) {
						$prop = (int)$prop;
					}
				}
				unset($prop);
				
				// Get field type
				$field['type'] = zenario_user_forms::getFieldType($field);
				
				// Get field LOV and CRM values
				if (in_array($field['type'], array('checkboxes', 'radios', 'select', 'centralised_radios', 'centralised_select'))) {
					$field['lov'] = array();
					if ($field['dataset_field_id']) {
						$lov = getDatasetFieldLOV($field['dataset_field_id'], false);
					} else {
						$lov = zenario_user_forms::getUnlinkedFieldLOV($fieldId, false);
					}
					if ($panel['crm_enabled']) {
						foreach ($lov as $valueId => &$value) {
							if ($field['type'] == 'centralised_radios' || $field['type'] == 'centralised_select') {
								$value['crm_value'] = $valueId;
							} else {
								$value['crm_value'] = $value['label'];
							}
						}
						unset($value);
					}
					$field['lov'] = $lov;
					
				// Get count of page breaks to calculate next page_break name
				} elseif ($field['type'] == 'page_break') {
					$pageBreakCount++;
				} elseif ($field['type'] == 'calculated') {
					if ($field['calculation_code']) {
						$field['calculation_code'] = json_decode($field['calculation_code']);
					}
				}
				
				// Get readonly status
				$field['readonly_or_mandatory'] = 'none';
				if ($field['is_required']) {
					$field['readonly_or_mandatory'] = 'mandatory';
				} else if ($field['is_readonly']) {
					$field['readonly_or_mandatory'] = 'readonly';
				} else if ($field['mandatory_condition_field_id']) {
					$field['readonly_or_mandatory'] = 'conditional_mandatory';
				}
				
				// Get default value status
				$field['default_value_mode'] = 'none';
				if ($field['default_value'] !== null && $field['default_value'] !== '') {
					$field['default_value_mode'] = 'value';
				} elseif ($field['default_value_class_name'] && $field['default_value_method_name']) {
					$field['default_value_mode'] = 'method';
				}
				
				// Get field to filter on
				$sourceFieldId = getRow(
					ZENARIO_USER_FORMS_PREFIX . 'form_field_update_link', 
					'source_field_id', 
					array('target_field_id' => $fieldId)
				);
				$field['filter_on_field'] = (int)$sourceFieldId;
				
				// Set remove to false
				$field['remove'] = false;
				
				$field['_crm_data'] = array();
				$field['_translations'] = array();
				
				if (!empty($panel['show_translation_tab'])) {
					$field['_translations'] = array(
						'label' => array(
							'value' => $field['label']
						)
					);
					if ($field['type'] == 'section_description') {
						$field['_translations']['description'] = array(
							'value' => $field['description']
						);
					} elseif ($field['type'] == 'text' || $field['type'] == 'textarea') {
						if ($field['type'] == 'text') {
							$field['_translations']['field_validation_error_message'] = array(
								'value' => $field['field_validation_error_message']
							);
						}
						$field['_translations']['placeholder'] = array(
							'value' => $field['placeholder']
						);
					}
					if ($field['type'] != 'page_break' && $field['type'] != 'section_description') {
						$field['_translations']['note_to_user'] = array(
							'value' => $field['note_to_user']
						);
						$field['_translations']['required_error_message'] = array(
							'value' => $field['required_error_message']
						);
					}
					
					// Loop through translatable fields and get translations in all translatable languages
					foreach ($field['_translations'] as $fieldName => &$fieldDetails) {
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
			unset($field);
			
			$fields['page_end'] = array(
			    'id' => 'page_end',
			    'type' => 'page_end',
			    'name' => $form['page_end_name'],
			    'label' => 'Page end',
			    'hide_in_page_switcher' => $form['hide_final_page_in_page_switcher'],
			    'ord' => 9999
			);
			
			
			// Get page break count
			$panel['pageBreakCount'] = $pageBreakCount;
			
			// Get CRM data for form fields if crm module is running
			if ($panel['crm_enabled']) {
				$sql = '
					SELECT fcf.form_field_id, fcf.field_crm_name, uff.user_field_id, uff.field_type, cdf.type
					FROM ' . DB_NAME_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_fields fcf
					INNER JOIN ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
						ON uff.user_form_id = ' . (int)$formId . '
						AND fcf.form_field_id = uff.id
					LEFT JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields cdf
						ON uff.user_field_id = cdf.id';
				$result = sqlSelect($sql);
				while ($row = sqlFetchAssoc($result)) {
					if (isset($fields[$row['form_field_id']])) {
						// Get CRM field name
						$fields[$row['form_field_id']]['_crm_data']['field_crm_name'] = $row['field_crm_name'];
						$fields[$row['form_field_id']]['_crm_data']['send_to_crm'] = true;
						
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
									$fields[$row['form_field_id']]['_crm_data']['values'][$crmValue['form_field_value_checkbox_state']] = array(
										'label' => $crmValue['form_field_value_checkbox_state'],
										'crm_value' => $crmValue['value']
									);
								} elseif ($type == 'centralised_select' || $type == 'centralised_radios') {
									if (isset($fields[$row['form_field_id']]['lov'][$crmValue['form_field_value_centralised_key']])) {
										$fields[$row['form_field_id']]['lov'][$crmValue['form_field_value_centralised_key']]['crm_value'] = $crmValue['value'];
									}
								} else {
									if ($row['user_field_id']) {
										if (isset($fields[$row['form_field_id']]['lov'][$crmValue['form_field_value_dataset_id']])) {
											$fields[$row['form_field_id']]['lov'][$crmValue['form_field_value_dataset_id']]['crm_value'] = $crmValue['value'];
										}
									} else {
										if (isset($fields[$row['form_field_id']]['lov'][$crmValue['form_field_value_unlinked_id']])) {
											$fields[$row['form_field_id']]['lov'][$crmValue['form_field_value_unlinked_id']]['crm_value'] = $crmValue['value'];
										}
									}
								}
							}
						}
					}
				}
			}
			
			$panel['items'] = $fields;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		$formId = $refinerId;
		switch (post('mode')) {
			// Save a forms fields
			case 'save':
				if (post('data') && ($data = json_decode(post('data'), true))) {
					$form = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('translate_text'), $formId);
					$fields = zenario_user_forms::getFields($formId);
					
					$languages = getLanguages(false, true, true);
					$crmEnabled = static::isCRMEnabled($formId);
					// Link between temporary Ids and real Ids
					$tempFieldIdLink = array();
					$tempValueIdLink = array();
					// List of fields deleted 
					$fieldsToDelete = array();
					
					uasort($data, function($a, $b) {
						if (!empty($a['remove']) || !empty($b['remove'])) {
							return 0;
						}
						return ($a['ord'] < $b['ord']) ? -1 : 1;
					});
					
					// Make sure fields order is valid
					$inRepeatBlock = false;
					foreach ($data as $fieldId => $field) {
						if (empty($field['remove'])) {
							if ($inRepeatBlock && in_array($field['type'], array('attachment', 'page_break', 'repeat_start', 'calculated', 'restatement', 'file_picker'))) {
								echo json_encode(array('error' => array('type' => $field['type'], 'code' => 'invalid_type_in_repeat_block')));
								exit;
							}
							
							if ($field['type'] == 'repeat_start') {
								$inRepeatBlock = true;
							} elseif ($field['type'] == 'repeat_end') {
								if (!$inRepeatBlock) {
									echo json_encode(array('error' => array('code' => 'invalid_repeat_block_end')));
									exit;
								}
								$inRepeatBlock = false;
							}
						}
					}
					
					foreach ($data as $fieldId => $field) {
						if (is_array($field)) {
						    if ($fieldId == 'page_end') {
						        updateRow(
						            ZENARIO_USER_FORMS_PREFIX . 'user_forms', 
						            array(
						                'hide_final_page_in_page_switcher' => !empty($field['hide_in_page_switcher']),
						                'page_end_name' => $field['name']
						            ),
						            $formId
						        );
						        continue;
						    } elseif (!empty($field['remove'])) {
								$fieldsToDelete[] = $fieldId;
							} else {
								// Save phrases
								if ($form['translate_text'] && !empty($field['_translations'])) {
									// field values = $field['label'] (Email:)
									// translation values = $field['_translations']['label']['phrases']['zh-hant'] (Email (chinese):)
									
									$translatableFields = array('label', 'placeholder', 'note_to_user', 'required_error_message', 'validation_error_message', 'description');
									
									// Update phrase code if phrases are changed to keep translation chain
									$fieldsToTranslate = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', $translatableFields, $fieldId);
									
									foreach ($translatableFields as $index => $name) {
										$oldCode = '';
										if ($fieldsToTranslate) {
											$oldCode = $fieldsToTranslate[$name];
										}
										if ($name == 'validation_error_message') {
											$name = 'field_validation_error_message';
										}
										// Check if old value has more than 1 entry in any translatable field
										$identicalPhraseFound = false;
										if ($oldCode) {
											$sql = '
												SELECT 
													id
												FROM 
													'.DB_NAME_PREFIX.ZENARIO_USER_FORMS_PREFIX.'user_form_fields
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
												}
											}
										}
									}
								}
								
								$oldFieldId = $fieldId;
								$fieldId = static::saveFormField($formId, $fields, $field);
								
								if (!$fieldId) {
									continue;
								}
								
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
												INNER JOIN ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
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
					
					$currentFormFields = zenario_user_forms::getFields($formId);
					
					foreach ($tempFieldIdLink as $oldFieldId => $details) {
						
						$field = $details['field'];
						$id = $details['id'];
						
						$type = zenario_user_forms::getFieldType($field);
						
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
						
						$values['visible_condition_field_id'] = 0;
						$values['visible_condition_field_value'] = null;
						if ($field['visibility'] == 'visible_on_condition' && !empty($field['visible_condition_field_id'])) {
							$tempField = $tempFieldIdLink[$field['visible_condition_field_id']];
							
							$values['visible_condition_field_id'] = $tempField['id'];
							$tempFieldType = zenario_user_forms::getFieldType($tempField['field']);
							
							if (in_array($tempFieldType, array('select', 'radios', 'checkboxes'))) {
								if ($field['visible_condition_field_value']) {
									$values['visible_condition_field_value'] = $tempValueIdLink[$field['visible_condition_field_value']];
								} else {
									$values['visible_condition_field_value'] = '';
								}
							} else {
								$values['visible_condition_field_value'] = mb_substr($field['visible_condition_field_value'], 0, 255, 'UTF-8');
							}
						}
						
						$readonlyOrMandatory = !empty($field['readonly_or_mandatory']) ? $field['readonly_or_mandatory'] : false;
						$values['mandatory_condition_field_id'] = 0;
						$values['mandatory_condition_field_value'] = null;
						if ($readonlyOrMandatory == 'conditional_mandatory' && !empty($field['mandatory_condition_field_id'])) {
							$values['mandatory_condition_field_id'] = $tempFieldIdLink[$field['mandatory_condition_field_id']]['id'];
							$values['mandatory_condition_field_value'] = mb_substr($field['mandatory_condition_field_value'], 0, 255, 'UTF-8');
						}
						
						$values['calculation_code'] = '';
						if ($type == 'calculated') {
							if (!empty($field['calculation_code'])) {
								foreach ($field['calculation_code'] as $index => $step) {
									if ($step['type'] == 'field') {
										$field['calculation_code'][$index]['value'] = $tempFieldIdLink[$field['calculation_code'][$index]['value']]['id'];
									}
								}
								
								$values['calculation_code'] = json_encode($field['calculation_code']);
							}
						}
						
						$values['restatement_field'] = 0;
						if (!empty($field['restatement_field'])) {
							$values['restatement_field'] = $tempFieldIdLink[$field['restatement_field']]['id'];
						}
						
						setRow(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', $values, $id);
						
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
									if (empty($field['label'])) {
										$errors[] = adminPhrase('Please enter a label for this checkbox.');
									}
								} elseif ($field['type'] == 'repeat_start') {
									if (empty($field['min_rows'])) {
										$errors[] = adminPhrase('Please enter the minimum rows.');
									} elseif (($field['min_rows']+0 != $field['min_rows']) || !is_int($field['min_rows']+0) || !is_numeric($field['min_rows'])) {
										$errors[] = adminPhrase('Please a valid number for mininum rows.');
									} elseif ($field['min_rows'] < 1 || $field['min_rows'] > 10) {
										$errors[] = adminPhrase('Mininum rows must be between 1 and 10.');
									}
									
									if (empty($field['max_rows'])) {
										$errors[] = adminPhrase('Please enter the maximum rows.');
									} elseif (($field['max_rows']+0 != $field['max_rows']) || !is_int($field['max_rows']+0) || !is_numeric($field['max_rows'])) {
										$errors[] = adminPhrase('Please a valid number for maximum rows.');
									} elseif ($field['max_rows'] < 1 || $field['max_rows'] > 20) {
										$errors[] = adminPhrase('Maximum rows must be between 1 and 20.');
									}
									
									if (is_numeric($field['min_rows']) && is_numeric($field['max_rows']) && ($field['min_rows'] > $field['max_rows'])) {
										$errors[] = adminPhrase('Minimum rows cannot be greater than maximum rows.');
									}
								}
								
								if (isset($field['visibility'])) {
                                    if ($field['visibility'] == 'visible_on_condition') {
                                        $visibleOnConditionField = isset($items[$field['visible_condition_field_id']]) ? $items[$field['visible_condition_field_id']] : false;
                                    
                                        if (empty($field['visible_condition_field_id'])) {
                                            $errors[] = adminPhrase('Please select a visible on conditional form field.');
                                        } elseif ($field['visible_condition_field_value'] === '' && ($visibleOnConditionField['type'] == 'checkbox' || $visibleOnConditionField['type'] == 'group')) {
                                            $errors[] = adminPhrase('Please select a visible on condition form field value.');
                                        }
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
											$errors[] = adminPhrase('This field is always mandatory but is conditionally visible. You must change this field to be either visible all the time or conditionally mandatory with the same condition as it\'s visibility.');
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
									if (empty($field['field_validation_error_message'])) {
										$errors[] = adminPhrase('Please enter a validation error message for this field.');
									}
								}
								
								if ($field['type'] == 'restatement') {
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
								// Custom code name must be unique in the form
								if (!empty($field['custom_code_name'])) {
									foreach ($items as $itemId => $item) {
										if (($itemId != $id) && ($item['custom_code_name'] == $field['custom_code_name'])) {
											$errors[] = adminPhrase('Another field already has that code name on this form.');
										}
									}
								}
								if (!empty($field['autocomplete'])) {
									if ($field['autocomplete_options'] == 'method') {
										if (!$field['autocomplete_class_name']) {
											$errors[] = adminPhrase('Please enter a class name.');
										} elseif (!inc($field['autocomplete_class_name'])) {
											$errors[] = adminPhrase('Please enter a class name of a module that\'s running on this site.');
										}
										if (!$field['autocomplete_method_name']) {
											$errors[] = adminPhrase('Please enter the name of a static method.');
										} elseif (!method_exists($field['autocomplete_class_name'], $field['autocomplete_method_name'])) {
											$errors[] = adminPhrase('Please enter the name of an existing static method.');
										}
									} elseif ($field['autocomplete_options'] == 'centralised_list') {
										if (empty($field['values_source'])) {
											$errors[] = adminPhrase('Please select a value source for the autocomplete list.');
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
							$errors[] = adminPhrase('Please enter a name for this form field.');
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
							if ((!isset($field['field_validation']) || !in_array($field['field_validation'], array('number', 'integer', 'floating_point'))) 
								&& $fieldValues['type'] == 'calculated'
							) {
								if (!empty($fieldValues['calculation_code'])) {
									$calculationCode = $fieldValues['calculation_code'];
									foreach ($calculationCode as $step) {
										if ($step['type'] == 'field' && $step['value'] == $id && ($field['type'] == 'text')) {
											$errors[] = adminPhrase(
												'The field "[[name]]" requires this field to be numeric. You must first remove this from that field.', 
												array('name' => $fieldValues['name'])
											);
											break;
										}
									}
								}
							}
							
						} else {
							// Make sure another field doesnt depend on this field before deleting it
							$usedInCalcField = false;
							if ($fieldValues['type'] == 'calculated') {
								if ($fieldValues['calculation_code']) {
									$calculationCode = $fieldValues['calculation_code'];
									foreach ($calculationCode as $step) {
										if ($step['type'] == 'field' && $step['value'] == $id) {
											$usedInCalcField = true;
											break;
										}
									}
								}
							}
							
							if ((isset($fieldValues['visibility'])
									&& ($fieldValues['visibility'] == 'visible_on_condition')
									&& ((string)$fieldValues['visible_condition_field_id'] == $id)
								)
								|| (isset($fieldValues['readonly_or_mandatory'])
									&& ($fieldValues['readonly_or_mandatory'] == 'conditional_mandatory')
									&& ((string)$fieldValues['mandatory_condition_field_id'] == $id)
								)
								|| $usedInCalcField
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
	
	private static function isCRMEnabled($formId) {
		if (inc('zenario_crm_form_integration')) {
			$formCRMData = zenario_crm_form_integration::getFormCrmData($formId);
			if ($formCRMData['enable_crm_integration']) {
				return true;
			}
		}
		return false;
	}
	
	private static function saveFormField($formId, $fields, $field) {
		$values = array();
		$keys = array();
		
		if (!empty($field['is_new_field'])) {
			$values['user_form_id'] = $formId;
		} else {
			if (!isset($fields[$field['id']])) {
				return false;
			}
			$keys['id'] = $field['id'];
		}
		
		$values['user_field_id'] = 0;
		if (!empty($field['dataset_field_id'])) {
			$values['user_field_id'] = (int)$field['dataset_field_id'];
		} else {
			$values['field_type'] = $field['type'];
		}
		
		$values['ord'] = (int)$field['ord'];
		$values['name'] = mb_substr($field['name'], 0, 255, 'UTF-8');
		$values['label'] = mb_substr($field['label'], 0, 255, 'UTF-8');
		
		$values['placeholder'] = null;
		if (isset($field['placeholder'])) {
			$values['placeholder'] = mb_substr($field['placeholder'], 0, 255, 'UTF-8');
		}
		
		$readonlyOrMandatory = !empty($field['readonly_or_mandatory']) ? $field['readonly_or_mandatory'] : false;
		$values['is_readonly'] = ($readonlyOrMandatory == 'readonly');
		$values['is_required'] = ($readonlyOrMandatory == 'mandatory');
		
		$values['required_error_message'] = null;
		if ($readonlyOrMandatory == 'mandatory' || $readonlyOrMandatory == 'conditional_mandatory') {
			$values['required_error_message'] = mb_substr($field['required_error_message'], 0, 255, 'UTF-8');
		}
		
		$values['visibility'] = 'visible';
		if (!empty($field['visibility'])) {
			$values['visibility'] = $field['visibility'];
		}
		
		$values['custom_code_name'] = null;
		if (!empty($field['custom_code_name'])) {
			$values['custom_code_name'] = mb_substr($field['custom_code_name'], 0, 255, 'UTF-8');
		}
		
		$values['preload_dataset_field_user_data'] = !empty($field['preload_dataset_field_user_data']);
		
		$defaultValueMode = !empty($field['default_value_mode']) ? $field['default_value_mode'] : false;
		$values['default_value'] = null;
		$values['default_value_class_name'] = null;
		$values['default_value_method_name'] = null;
		$values['default_value_param_1'] = null;
		$values['default_value_param_2'] = null;
		if ($defaultValueMode == 'value') {
			if (isset($field['default_value'])) {
				$values['default_value'] = mb_substr($field['default_value'], 0, 255, 'UTF-8');
			}
		} elseif ($defaultValueMode == 'method') {
			if (!empty($field['default_value_class_name'])) {
				$values['default_value_class_name'] = mb_substr($field['default_value_class_name'], 0, 255, 'UTF-8');
			}
			if (!empty($field['default_value_method_name'])) {
				$values['default_value_method_name'] = mb_substr($field['default_value_method_name'], 0, 255, 'UTF-8');
			}
			if (isset($field['default_value_param_1'])) {
				$values['default_value_param_1'] = mb_substr($field['default_value_param_1'], 0, 255, 'UTF-8');
			}
			if (isset($field['default_value_param_2'])) {
				$values['default_value_param_2'] = mb_substr($field['default_value_param_2'], 0, 255, 'UTF-8');
			}
		}
		
		$values['autocomplete'] = 0;
		$values['autocomplete_class_name'] = null;
		$values['autocomplete_method_name'] = null;
		$values['autocomplete_param_1'] = null;
		$values['autocomplete_param_2'] = null;
		$values['autocomplete_no_filter_placeholder'] = null;
		if (!empty($field['autocomplete'])) {
			$values['autocomplete'] = 1;
			if (!empty($field['autocomplete_class_name'])) {
				$values['autocomplete_class_name'] = mb_substr($field['autocomplete_class_name'], 0, 255, 'UTF-8');
			}
			if (!empty($field['autocomplete_method_name'])) {
				$values['autocomplete_method_name'] = mb_substr($field['autocomplete_method_name'], 0, 255, 'UTF-8');
			}
			if (isset($field['autocomplete_param_1'])) {
				$values['autocomplete_param_1'] = mb_substr($field['autocomplete_param_1'], 0, 255, 'UTF-8');
			}
			if (isset($field['autocomplete_param_2'])) {
				$values['autocomplete_param_2'] = mb_substr($field['autocomplete_param_2'], 0, 255, 'UTF-8');
			}
			if (isset($field['autocomplete_no_filter_placeholder'])) {
				$values['autocomplete_no_filter_placeholder'] = mb_substr($field['autocomplete_no_filter_placeholder'], 0, 255, 'UTF-8');
			}
		}
		
		$values['note_to_user'] = null;
		if (isset($field['note_to_user'])) {
			$values['note_to_user'] = mb_substr($field['note_to_user'], 0, 255, 'UTF-8');
		}
		$values['css_classes'] = null;
		if (isset($field['css_classes'])) {
			$values['css_classes'] = mb_substr($field['css_classes'], 0, 255, 'UTF-8');
		}
		$values['div_wrap_class'] = null;
		if (isset($field['div_wrap_class'])) {
			$values['div_wrap_class'] = mb_substr($field['div_wrap_class'], 0, 255, 'UTF-8');
		}
		
		$values['validation'] = null;
		$values['validation_error_message'] = null;
		if (!empty($field['field_validation']) && $field['field_validation'] != 'none') {
			$values['validation'] = $field['field_validation'];
			$values['validation_error_message'] = mb_substr($field['field_validation_error_message'], 0, 255, 'UTF-8');
		}
		
		$values['next_button_text'] = null;
		if (isset($field['next_button_text'])) {
			$values['next_button_text'] = mb_substr($field['next_button_text'], 0, 255, 'UTF-8');
		}
		
		$values['previous_button_text'] = null;
		if (isset($field['previous_button_text'])) {
			$values['previous_button_text'] = mb_substr($field['previous_button_text'], 0, 255, 'UTF-8');
		}
		
		$values['description'] = null;
		if (isset($field['description'])) {
			$values['description'] = mb_substr($field['description'], 0, 65535, 'UTF-8');
		}
		
		$values['value_prefix'] = null;
		if (!empty($field['value_prefix'])) {
			$values['value_prefix'] = $field['value_prefix'];
		}
		
		$values['value_postfix'] = null;
		if (!empty($field['value_postfix'])) {
			$values['value_postfix'] = $field['value_postfix'];
		}
	 	
	 	$values['values_source'] = '';
	 	$values['values_source_filter'] = '';
	 	if (!empty($field['values_source'])) {
	 		$values['values_source'] = mb_substr($field['values_source'], 0, 255, 'UTF-8');
	 		if (!empty($field['values_source_filter'])) {
	 			$values['values_source_filter'] = mb_substr($field['values_source_filter'], 0, 255, 'UTF-8');
	 		}
	 	}
	 	
	 	$values['value_field_columns'] = 0;
	 	if (!empty($field['value_field_columns'])) {
	 		$values['value_field_columns'] = (int)$field['value_field_columns'];
	 	}
	 	
	 	$values['min_rows'] = 0;
	 	$values['max_rows'] = 0;
	 	if (!empty($field['min_rows'])) {
	 		$values['min_rows'] = (int)$field['min_rows'];
	 	}
	 	if (!empty($field['max_rows'])) {
	 		$values['max_rows'] = (int)$field['max_rows'];
	 	}
	 	
	 	$values['hide_in_page_switcher'] = 0;
	 	if (!empty($field['hide_in_page_switcher'])) {
	 		$values['hide_in_page_switcher'] = (int)$field['hide_in_page_switcher'];
	 	}
	 	
	 	$values['show_month_year_selectors'] = 0;
	 	if (!empty($field['show_month_year_selectors'])) {
	 		$values['show_month_year_selectors'] = (int)$field['show_month_year_selectors'];
	 	}
	 	
	 	$fieldId = setRow(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', $values, $keys);
		
		// Save field
		return $fieldId;
	}
}