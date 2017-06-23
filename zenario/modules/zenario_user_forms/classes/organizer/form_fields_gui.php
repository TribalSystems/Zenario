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
		if ($formId
			&& ($form = getRow(
				ZENARIO_USER_FORMS_PREFIX . 'user_forms', 
				array('name', 'type', 'title', 'translate_text', 'hide_final_page_in_page_switcher', 'default_previous_button_text', 'page_end_name'), 
				$formId
			))
		) {
			$panel['title'] = adminPhrase('Form fields for "[[name]]"', $form);
			$panel['form_title'] =  $form['title'];
			
			//If this form is translatable, pass the languages and translatable fields
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
			}
			
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
			
			//Get dataset tabs and fields list
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
			
			//Do not pass tabs with no fields
			foreach ($tabs as $tabName => $tab) {
				if (empty($tabs[$tabName]['fields'])) {
					unset($tabs[$tabName]);
				}
			}
			$panel['dataset_fields'] = $tabs;
			
			//Check if CRM is enabled on this form
			$panel['crm_enabled'] = static::isCRMEnabled($formId);
			
			//Get form fields
			$pageBreakCount = 0;
			$fields = zenario_user_forms::getFields($formId);
			$defaultValues = array();
			foreach ($panel['field_details']['tabs'] as $tabName => $tab) {
				if (!empty($tab['fields'])) {
					foreach ($tab['fields'] as $fieldName => $field) {
						if (isset($field['value'])) {
							$defaultValues[$fieldName] = $field['value'];
						}	
					}
				}
			}
			foreach ($fields as $fieldId => &$field) {
				//Make sure any number fields are passed as numbers not strings
				foreach ($field as &$prop) {
					if (is_numeric($prop)) {
						$prop = (int)$prop;
					}
				}
				unset($prop);
				
				$field['field_label'] = $field['label'];
				
				//Get field type
				$field['type'] = zenario_user_forms::getFieldType($field);
				
				//Get field LOV and CRM values
				if (in_array($field['type'], array('checkboxes', 'radios', 'select', 'centralised_radios', 'centralised_select'))) {
					$field['lov'] = array();
					if ($field['dataset_field_id']) {
						$lov = getDatasetFieldLOV($field['dataset_field_id'], false);
						$field['values_source'] = getRow('custom_dataset_fields', 'values_source', $field['dataset_field_id']);
						
					} else {
						$lov = zenario_user_forms::getUnlinkedFieldLOV($fieldId, false);
						$field['invalid_responses'] = array();
						if (in_array($field['type'], array('checkboxes', 'radios', 'select'))) {
							foreach ($lov as $valueId => $value) {
								if (!empty($value['is_invalid'])) {
									$field['invalid_responses'][] = (string)$valueId;
								}
							}
						}
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
					
				//Get count of page breaks to calculate next page_break name
				} elseif ($field['type'] == 'page_break') {
					$pageBreakCount++;
				} elseif ($field['type'] == 'calculated') {
					if ($field['calculation_code']) {
						$field['calculation_code'] = json_decode($field['calculation_code']);
					}
				}
				
				//Get readonly status
				$field['readonly_or_mandatory'] = 'none';
				if ($field['is_required']) {
					$field['readonly_or_mandatory'] = 'mandatory';
				} else if ($field['is_readonly']) {
					$field['readonly_or_mandatory'] = 'readonly';
				} else if ($field['mandatory_condition_field_id']) {
					$field['readonly_or_mandatory'] = 'conditional_mandatory';
				}
				
				//Get default value status
				$field['default_value_options'] = 'none';
				if ($field['default_value'] !== null && $field['default_value'] !== '') {
					$field['default_value_options'] = 'value';
					if (in_array($field['type'], array('checkbox', 'group'))) {
						$field['default_value_lov'] = $field['default_value'] ? 'checked' : 'unchecked';
					} elseif (in_array($field['type'], array('radios', 'centralised_radios', 'select', 'centralised_select'))) {
						$field['default_value_lov'] = $field['default_value'];
					} else {
						$field['default_value_text'] = $field['default_value'];
					}
				} elseif ($field['default_value_class_name'] && $field['default_value_method_name']) {
					$field['default_value_options'] = 'method';
				}
				
				if ($field['visibility'] == 'visible_on_condition' && $field['visible_condition_field_id'] && isset($fields[$field['visible_condition_field_id']])) {
					$conditionFieldType = zenario_user_forms::getFieldType($fields[$field['visible_condition_field_id']]);
					if ($conditionFieldType == 'checkboxes') {
						$field['visible_condition_checkboxes_field_value'] = explode(',', $field['visible_condition_field_value']);
					} elseif ($conditionFieldType == 'checkbox' || $conditionFieldType == 'group') {
						$field['visible_condition_field_value'] = $field['visible_condition_field_value'] ? 'checked' : 'unchecked';
					}
				}
				
				if ($field['readonly_or_mandatory'] == 'conditional_mandatory' && $field['mandatory_condition_field_id'] && isset($fields[$field['mandatory_condition_field_id']])) {
					$conditionFieldType = zenario_user_forms::getFieldType($fields[$field['mandatory_condition_field_id']]);
					if ($conditionFieldType == 'checkboxes') {
						$field['mandatory_condition_checkboxes_field_value'] = explode(',', $field['mandatory_condition_field_value']);
					} elseif ($conditionFieldType == 'checkbox' || $conditionFieldType == 'group') {
						$field['mandatory_condition_field_value'] = $field['mandatory_condition_field_value'] ? 'checked' : 'unchecked';
					}
				}
				
				//autocomplete_options
				if ($field['autocomplete_class_name']) {
					$field['autocomplete_options'] = 'method';
				}
				
				$field['visible_condition_field_type'] = $field['visible_condition_invert'] ? 'visible_if_not' : 'visible_if';
				$field['mandatory_condition_field_type'] = $field['mandatory_condition_invert'] ? 'mandatory_if_not' : 'mandatory_if';
				
				foreach ($defaultValues as $defaultValueName => $defaultValue) {
					if (empty($field[$defaultValueName])) {
						$field[$defaultValueName] = $defaultValue;
					}
				}
				
				//Get field to filter on
				$sourceFieldId = getRow(
					ZENARIO_USER_FORMS_PREFIX . 'form_field_update_link', 
					'source_field_id', 
					array('target_field_id' => $fieldId)
				);
				$field['filter_on_field'] = (int)$sourceFieldId;
				
				
				$field['_crm_data'] = array();
				$field['_translations'] = array();
				if (!empty($panel['show_translation_tab'])) {
					$field['_translations'] = array();
					foreach ($panel['field_details']['tabs']['translations']['translatable_fields'] as $fieldName) {
						$field['_translations'][$fieldName] = array('value' => $field[$fieldName], 'phrases' => array());
						if ($field[$fieldName]) {
							$phrases = getRows(
								'visitor_phrases', 
								array('local_text', 'language_id'), 
								array('code' => $field[$fieldName], 'module_class_name' => 'zenario_user_forms')
							);
							while ($row = sqlFetchAssoc($phrases)) {
								if (!empty($languages[$row['language_id']]['translate_phrases'])) {
									$field['_translations'][$fieldName]['phrases'][$row['language_id']] = $row['local_text'];
								}
							}
							
						}
					}
				}
			}
			unset($field);
			
			$fields['form_end'] = array(
			    'id' => 'form_end',
			    'type' => 'page_break',
			    'name' => $form['page_end_name'],
			    'previous_button_text' => $form['default_previous_button_text'],
			    'hide_in_page_switcher' => $form['hide_final_page_in_page_switcher'],
			    'ord' => 9999
			);
			
			//Get page break count
			$panel['pageBreakCount'] = $pageBreakCount;
			
			//Get CRM data for form fields if crm module is running
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
						//Get CRM field name
						$fields[$row['form_field_id']]['field_crm_name'] = $row['field_crm_name'];
						$fields[$row['form_field_id']]['send_to_crm'] = true;
						
						$type = zenario_user_forms::getFieldType($row);
						
						//Get multi field CRM values
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
									$state = $crmValue['form_field_value_checkbox_state'] ? 'checked' : 'unchecked';
									$fields[$row['form_field_id']]['_crm_data']['values'][$state] = array(
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
			
			//Sort fields into tabs based on page break locations (fields should already be in order)
			$tabs = array();
			$tabFields = array();
			foreach ($fields as $fieldId => $field) {
				if ($field['type'] == 'page_break') {
					$field['fields'] = $tabFields;
					$tabs[$fieldId] = $field;
					$tabFields = array();
				} else {
					$tabFields[$fieldId] = $field;
				}
			}
			
			$panel['items'] = $tabs;
		}
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		$formId = $refinerId;
		switch (post('mode')) {
			//Save a forms fields
			case 'save':
				if (post('data') && ($data = json_decode(post('data'), true))) {
					$errors = array();
					$selectedFieldId = post('selectedFieldId');
					$selectedTabId = post('selectedTabId');
					$currentTabId = post('currentTabId');
					$deletedFields = json_decode(post('deletedFields'), true);
					
					//Order fields by ordinals
					$sortedData = $data;
					usort($sortedData, 'static::sortByOrd');
					$sortedDataCount = count($sortedData);
					
					$tempFieldIdLink = array();
					$tempValueIdLink = array();
					
					$form = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('translate_text'), $formId);
					$fields = zenario_user_forms::getFields($formId);
					$languages = getLanguages(false, true, true);
					$crmEnabled = static::isCRMEnabled($formId);
					
					$ord = 0;
					foreach ($sortedData as $tabIndex => $tab) {
						if ($tab && is_array($tab)) {
							$isFinalPageBreak = ($tabIndex == ($sortedDataCount - 1));
							
							$tabFields = isset($tab['fields']) ? $tab['fields'] : array();
							usort($tabFields, 'static::sortByOrd');
							//Save page break in position after its fields
							if (!$isFinalPageBreak) {
								$tabFields[] = $tab;
							}
							
							foreach ($tabFields as $fieldIndex => $field) {
								$fieldId = $field['id'];
								//Save translations
								static::saveFieldTranslations($form, $field, $languages);
								
								//Save field details					
								$oldFieldId = $fieldId;
								$fieldId = static::saveFormField($formId, $fields, $field, ++$ord);
								
								if ($oldFieldId == $selectedFieldId) {
									$selectedFieldId = $fieldId;
								} elseif ($oldFieldId == $selectedTabId) {
									$selectedTabId = $fieldId;
								}
								if ($oldFieldId == $currentTabId) {
									$currentTabId = $fieldId;
								}
								
								//Store link between temp ids and real ids
								$tempFieldIdLink[$oldFieldId] = array(
									'id' => $fieldId,
									'field' => $field
								);
								
								//Save field values
								static::saveFieldListOfValues($fieldId, $field, $tempValueIdLink);
								
								//Save field CRM data
								if ($crmEnabled) {
									static::saveFieldCRMData($formId, $fieldId, $field, $tempValueIdLink);
								}
							}
							
							if ($isFinalPageBreak) {
								$deletedFields[] = $tab['id'];
								
								$formValues = array(
						            'hide_final_page_in_page_switcher' => !empty($tab['hide_in_page_switcher']),
						            'page_end_name' => $tab['name']
								);
								if (isset($tab['previous_button_text'])) {
									$formValues['default_previous_button_text'] = $tab['previous_button_text'];
								}
								updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $formValues, $formId);
								
								if ($tab['id'] == $selectedTabId) {
									$selectedTabId = 'form_end';
								}
								if ($tab['id'] == $currentTabId) {
									$currentTabId = 'form_end';
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
								array('source_field_id' => $tempFieldIdLink[$field['filter_on_field']]['id']), 
								array('target_field_id' => $id)
							);
						} else {
							deleteRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_update_link', array('target_field_id' => $id));
						}
						
						//Save conditional field values
						$values = array();
						
						$values['visible_condition_field_id'] = 0;
						$values['visible_condition_invert'] = 0;
						$values['visible_condition_field_value'] = null;
						if (isset($field['visibility']) && $field['visibility'] == 'visible_on_condition' && !empty($field['visible_condition_field_id'])) {
							$tempField = $tempFieldIdLink[$field['visible_condition_field_id']];
							$values['visible_condition_invert'] = ($field['visible_condition_field_type'] == 'visible_if_not');
							$values['visible_condition_field_id'] = $tempField['id'];
							$tempFieldType = zenario_user_forms::getFieldType($tempField['field']);
							
							if (in_array($tempFieldType, array('select', 'radios'))) {
								if ($field['visible_condition_field_value']) {
									$values['visible_condition_field_value'] = $tempValueIdLink[$field['visible_condition_field_value']];
								} else {
									$values['visible_condition_field_value'] = '';
								}
							} elseif ($tempFieldType == 'checkboxes') {
								$values['visible_condition_checkboxes_operator'] = $field['visible_condition_checkboxes_operator'];
								if ($field['visible_condition_checkboxes_field_value']) {
									$tValues = array();
									foreach ($field['visible_condition_checkboxes_field_value'] as $tValue) {
										$tValues[] = $tempValueIdLink[$tValue];
									}
									$values['visible_condition_field_value'] = implode(',', $tValues);
								}
							} elseif (in_array($tempFieldType, array('checkbox', 'group'))) {
								$values['visible_condition_field_value'] = (!empty($field['visible_condition_field_value']) && $field['visible_condition_field_value'] == 'checked') ? 1 : 0;
							} else {
								$values['visible_condition_field_value'] = mb_substr($field['visible_condition_field_value'], 0, 250, 'UTF-8');
							}
						}
						
						$readonlyOrMandatory = !empty($field['readonly_or_mandatory']) ? $field['readonly_or_mandatory'] : false;
						$values['mandatory_condition_field_id'] = 0;
						$values['mandatory_condition_invert'] = 0;
						$values['mandatory_condition_field_value'] = null;
						if ($readonlyOrMandatory == 'conditional_mandatory' && !empty($field['mandatory_condition_field_id'])) {
							$tempField = $tempFieldIdLink[$field['mandatory_condition_field_id']];
							$values['mandatory_condition_invert'] = ($field['mandatory_condition_field_type'] == 'mandatory_if_not');
							$values['mandatory_condition_field_id'] = $tempField['id'];
							$tempFieldType = zenario_user_forms::getFieldType($tempField['field']);
							
							if (in_array($tempFieldType, array('select', 'radios'))) {
								if ($field['mandatory_condition_field_value']) {
									$values['mandatory_condition_field_value'] = $tempValueIdLink[$field['mandatory_condition_field_value']];
								} else {
									$values['mandatory_condition_field_value'] = '';
								}
							} elseif ($tempFieldType == 'checkboxes') {
								$values['mandatory_condition_checkboxes_operator'] = $field['mandatory_condition_checkboxes_operator'];
								if ($field['mandatory_condition_checkboxes_field_value']) {
									$tValues = array();
									foreach ($field['mandatory_condition_checkboxes_field_value'] as $tValue) {
										$tValues[] = $tempValueIdLink[$tValue];
									}
									$values['mandatory_condition_field_value'] = implode(',', $tValues);
								}
								
							} elseif (in_array($tempFieldType, array('checkbox', 'group'))) {
								$values['mandatory_condition_field_value'] = (!empty($field['mandatory_condition_field_value']) && $field['mandatory_condition_field_value'] == 'checked') ? 1 : 0;
							} else {
								$values['mandatory_condition_field_value'] = mb_substr($field['mandatory_condition_field_value'], 0, 250, 'UTF-8');
							}
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
						
						//Migrate any data
						if (!empty($details['field']['_migrate_responses_from'])) {
							//Check fields are the same type
							if ($currentFormFields[$details['id']] 
								&& $currentFormFields[$details['field']['_migrate_responses_from']]
								&& (zenario_user_forms::getFieldType($currentFormFields[$details['id']]) == zenario_user_forms::getFieldType($currentFormFields[$details['field']['_migrate_responses_from']]))
							) {
								//Delete existing responses
								deleteRow(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', array('form_field_id' => $details['id']));
								
								//Move responses
								updateRow(
									ZENARIO_USER_FORMS_PREFIX . 'user_response_data', 
									array('form_field_id' => $details['id']),
									array('form_field_id' => $details['field']['_migrate_responses_from'])
								);
							}
						}
					}
					
					//Delete removed fields
					foreach ($deletedFields as $fieldId) {
						zenario_user_forms::deleteFormField($fieldId);
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
	
	private static function saveFieldCRMData($formId, $fieldId, $field, $tempValueIdLink) {
		if (!empty($field['send_to_crm']) && !empty($field['field_crm_name'])) {
			$formCRMValues = array('field_crm_name' => $field['field_crm_name']);
			
			if (
				!checkRowExists(
					ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_fields', 
					array('form_field_id' => $fieldId, 'field_crm_name' => $field['field_crm_name'])
				)
			) {
				//Get next ordinal
				$maxOrdinalSQL = '
					SELECT MAX(fcf.ordinal)
					FROM ' . DB_NAME_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_fields fcf
					INNER JOIN ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
						ON uff.user_form_id = ' . (int)$formId . '
						AND fcf.form_field_id = uff.id
					WHERE fcf.field_crm_name = "' . sqlEscape($field['field_crm_name']) . '"';
				$maxOrdinalResult = sqlSelect($maxOrdinalSQL);
				$maxOrdinalRow = sqlFetchRow($maxOrdinalResult);
				$formCRMValues['ordinal'] = $maxOrdinalRow[0] ? $maxOrdinalRow[0] + 1 : 1;
			}
			
			setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_fields', $formCRMValues, array('form_field_id' => $fieldId));
			
			
			if ($field['type'] == 'checkbox' && !empty($field['_crm_data']['values'])) {
				foreach ($field['_crm_data']['values'] as $lovId => $lovValue) {
					$state = ($lovId == 'checked') ? 1 : 0;
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
				//Save values
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
							//Get actual ID if the value was using a temp ID e.g. t1
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
			//Delete CRM data
			zenario_crm_form_integration::deleteFieldCRMData($fieldId);
		}
	}
	
	private static function saveFieldListOfValues($fieldId, $field, &$tempValueIdLink) {
		//Save field list values
		if (!empty($field['lov']) 
			&& !empty($field['type'])
			&& in_array($field['type'], array('checkboxes', 'radios', 'select'))
			&& empty($field['field_id'])
		) {
			if (!empty($field['_deleted_lov'])) {
				foreach ($field['_deleted_lov'] as $valueId) {
					zenario_user_forms::deleteFormFieldValue($valueId);
				}
			}
			foreach ($field['lov'] as $valueId => $value) {
				if (is_array($value)) {
					$values = array(
						'form_field_id' => $fieldId,
						'ord' => $value['ord'],
						'label' => substr($value['label'], 0, 250),
						'is_invalid' => !empty($field['invalid_responses']) && in_array($valueId, $field['invalid_responses'])
					);
					$ids = array();
					if (empty($value['is_new_value'])) {
						$ids['id'] = $valueId;
					}
					$trueValueId = setRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', $values, $ids);
					$tempValueIdLink[$valueId] = $trueValueId;
				}
			}
		}
	}
	
	private static function saveFieldTranslations($form, $field, $languages) {
		if ($form['translate_text'] && !empty($field['_translations'])) {
			$fieldId = $field['id'];
			$translatableFields = array('label', 'placeholder', 'note_to_user', 'required_error_message', 'validation_error_message', 'description');
			$fieldsToTranslate = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', $translatableFields, $fieldId);
	
			//Update phrase code if phrases are changed to keep translation chain
			foreach ($translatableFields as $index => $name) {
				$oldCode = '';
				if ($fieldsToTranslate) {
					$oldCode = $fieldsToTranslate[$name];
				}
				if ($name == 'validation_error_message') {
					$name = 'field_validation_error_message';
				} else if ($name == 'label') {
					$name = 'field_label';
				}
				//Check if old value has more than 1 entry in any translatable field
				$identicalPhraseFound = false;
				if ($oldCode) {
					$sql = '
						SELECT id
						FROM ' . DB_NAME_PREFIX.ZENARIO_USER_FORMS_PREFIX . 'user_form_fields
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

				//If another field is using the same phrase code...
				if ($identicalPhraseFound) {
					foreach ($languages as $language) {
						//Create or overwrite new phrases with the new english code
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
					//If nothing else is using the same phrase code...
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
					//If code already exists, and nothing else is using the code, delete current phrases, and update/create new translations
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
	}
	
	private static function saveFormField($formId, $fields, $field, $ord) {
		$values = array();
		$keys = array();
		//If the extra "form_end" field has been moved and is no longer at the end, save it as a new field
		if (!empty($field['is_new_field']) || $field['id'] == 'form_end') {
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
		
		$values['ord'] = $ord;
		$values['name'] = mb_substr($field['name'], 0, 250, 'UTF-8');
		if (isset($field['field_label'])) {
			$values['label'] = mb_substr($field['field_label'], 0, 250, 'UTF-8');
		}
		
		$values['placeholder'] = null;
		if (isset($field['placeholder'])) {
			$values['placeholder'] = mb_substr($field['placeholder'], 0, 250, 'UTF-8');
		}
		
		$readonlyOrMandatory = !empty($field['readonly_or_mandatory']) ? $field['readonly_or_mandatory'] : false;
		$values['is_readonly'] = ($readonlyOrMandatory == 'readonly');
		$values['is_required'] = ($readonlyOrMandatory == 'mandatory');
		
		$values['required_error_message'] = null;
		if ($readonlyOrMandatory == 'mandatory' || $readonlyOrMandatory == 'conditional_mandatory') {
			$values['required_error_message'] = mb_substr($field['required_error_message'], 0, 250, 'UTF-8');
		}
		
		$values['visibility'] = 'visible';
		if (!empty($field['visibility'])) {
			$values['visibility'] = $field['visibility'];
		}
		
		$values['custom_code_name'] = null;
		if (!empty($field['custom_code_name'])) {
			$values['custom_code_name'] = mb_substr($field['custom_code_name'], 0, 250, 'UTF-8');
		}
		
		$values['preload_dataset_field_user_data'] = !empty($field['preload_dataset_field_user_data']);
		
		$defaultValueMode = !empty($field['default_value_options']) ? $field['default_value_options'] : false;
		$values['default_value'] = null;
		$values['default_value_class_name'] = null;
		$values['default_value_method_name'] = null;
		$values['default_value_param_1'] = null;
		$values['default_value_param_2'] = null;
		if ($defaultValueMode == 'value') {
			if (in_array($field['type'], array('checkbox', 'group')) && isset($field['default_value_lov'])) {
				$values['default_value'] = $field['default_value_lov'] == 'checked' ? 1 : 0;
			} else if (in_array($field['type'], array('radios', 'centralised_radios', 'select', 'centralised_select')) && isset($field['default_value_lov'])) {
				$values['default_value'] = $field['default_value_lov'];
			} elseif (isset($field['default_value_text'])) {
				$values['default_value'] = mb_substr($field['default_value_text'], 0, 250, 'UTF-8');
			}
			
		} elseif ($defaultValueMode == 'method') {
			if (!empty($field['default_value_class_name'])) {
				$values['default_value_class_name'] = mb_substr($field['default_value_class_name'], 0, 250, 'UTF-8');
			}
			if (!empty($field['default_value_method_name'])) {
				$values['default_value_method_name'] = mb_substr($field['default_value_method_name'], 0, 250, 'UTF-8');
			}
			if (isset($field['default_value_param_1'])) {
				$values['default_value_param_1'] = mb_substr($field['default_value_param_1'], 0, 250, 'UTF-8');
			}
			if (isset($field['default_value_param_2'])) {
				$values['default_value_param_2'] = mb_substr($field['default_value_param_2'], 0, 250, 'UTF-8');
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
			
			if (!empty($field['autocomplete_options']) && $field['autocomplete_options'] == 'method') {
				if (!empty($field['autocomplete_class_name'])) {
					$values['autocomplete_class_name'] = mb_substr($field['autocomplete_class_name'], 0, 250, 'UTF-8');
				}
				if (!empty($field['autocomplete_method_name'])) {
					$values['autocomplete_method_name'] = mb_substr($field['autocomplete_method_name'], 0, 250, 'UTF-8');
				}
				if (isset($field['autocomplete_param_1'])) {
					$values['autocomplete_param_1'] = mb_substr($field['autocomplete_param_1'], 0, 250, 'UTF-8');
				}
				if (isset($field['autocomplete_param_2'])) {
					$values['autocomplete_param_2'] = mb_substr($field['autocomplete_param_2'], 0, 250, 'UTF-8');
				}
			} else {
				if (isset($field['autocomplete_no_filter_placeholder'])) {
					$values['autocomplete_no_filter_placeholder'] = mb_substr($field['autocomplete_no_filter_placeholder'], 0, 250, 'UTF-8');
				}
			}
		}
		
		$values['note_to_user'] = null;
		if (isset($field['note_to_user'])) {
			$values['note_to_user'] = mb_substr($field['note_to_user'], 0, 250, 'UTF-8');
		}
		$values['css_classes'] = null;
		if (isset($field['css_classes'])) {
			$values['css_classes'] = mb_substr($field['css_classes'], 0, 250, 'UTF-8');
		}
		$values['div_wrap_class'] = null;
		if (isset($field['div_wrap_class'])) {
			$values['div_wrap_class'] = mb_substr($field['div_wrap_class'], 0, 250, 'UTF-8');
		}
		
		$values['validation'] = null;
		$values['validation_error_message'] = null;
		if (!empty($field['field_validation']) && $field['field_validation'] != 'none') {
			$values['validation'] = $field['field_validation'];
			$values['validation_error_message'] = mb_substr($field['field_validation_error_message'], 0, 250, 'UTF-8');
		}
		
		$values['next_button_text'] = null;
		if (isset($field['next_button_text'])) {
			$values['next_button_text'] = mb_substr($field['next_button_text'], 0, 250, 'UTF-8');
		}
		
		$values['previous_button_text'] = null;
		if (isset($field['previous_button_text'])) {
			$values['previous_button_text'] = mb_substr($field['previous_button_text'], 0, 250, 'UTF-8');
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
	 		$values['values_source'] = mb_substr($field['values_source'], 0, 250, 'UTF-8');
	 		if (!empty($field['values_source_filter'])) {
	 			$values['values_source_filter'] = mb_substr($field['values_source_filter'], 0, 250, 'UTF-8');
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
	 	
	 	$values['invalid_field_value_error_message'] = null;
	 	if (!empty($field['invalid_responses']) && !empty($field['invalid_field_value_error_message'])) {
	 		$values['invalid_field_value_error_message'] = mb_substr($field['invalid_field_value_error_message'], 0, 250, 'UTF-8');
	 	}
	 	
	 	$values['word_limit'] = null;
	 	if (!empty($field['word_limit']) && (int)$field['word_limit'] > 0) {
	 		$values['word_limit'] = (int)$field['word_limit'];
	 	}
	 	
	 	$fieldId = setRow(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', $values, $keys);
		
		//Save field
		return $fieldId;
	}
	
	public static function sortByOrd($a, $b) {
		if ($a['ord'] == $b['ord']) {
			return 0;
		}
		return ($a['ord'] < $b['ord']) ? -1 : 1;
	}
}