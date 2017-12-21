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
		$form = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('name', 'type', 'title', 'translate_text'), $formId);
		
		$panel['title'] = adminPhrase('Form fields for "[[name]]"', $form);
		$panel['items'] = array();
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
		
		//Add a link to the users dataset panel
		$dataset = getDatasetDetails('users');
		$panel['link_to_dataset'] = absCMSDirURL() . 'zenario/admin/organizer.php#zenario__administration/panels/custom_datasets//' . $dataset['id'];
		
		//Get dataset tabs and fields list
		$panel['dataset_fields'] = $this->getPanelDatasetFields();
		
		//Check if CRM is enabled on this form
		$panel['crm_enabled'] = $this->isCRMEnabled($formId);
		
		
		//Get form fields
		$pageBreakCount = 0;
		$fields = static::getFormFields($formId);
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
		$pageFields = array();
		foreach ($fields as $fieldId => &$field) {
			//Make sure any number fields are passed as numbers not strings
			foreach ($field as &$prop) {
				if (is_numeric($prop)) {
					$prop = (int)$prop;
				}
			}
			unset($prop);
			
			if (!isset($pageFields[$field['page_id']])) {
				$pageFields[$field['page_id']] = array();
			}
			$pageFields[$field['page_id']][$fieldId] = 1;
			
			$field['field_label'] = $field['label'];
			
			//Get field LOV and CRM values
			if (in_array($field['type'], array('checkboxes', 'radios', 'select', 'centralised_radios', 'centralised_select'))) {
				$field['lov'] = array();
				if ($field['dataset_field_id']) {
					$lov = getDatasetFieldLOV($field['dataset_field_id'], false);
					$field['values_source'] = getRow('custom_dataset_fields', 'values_source', $field['dataset_field_id']);
					foreach ($lov as $valueId => $value) {
						$lov[$valueId]['id'] = $valueId;
					}
				} else {
					if ($field['type'] == 'centralised_radios' || $field['type'] == 'centralised_select') {
						$lov = array();
						$centralisedValues = getCentralisedListValues($field['values_source'], $field['values_source_filter']);
						$ord = 0;
						foreach ($centralisedValues as $valueId => $value) {
							$lov[$valueId] = array(
								'id' => $valueId,
								'label' => $value,
								'ord' => ++$ord
							);
						}
					} else {
						$lov = getRowsArray(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', array('id', 'label', 'ord', 'is_invalid'), array('form_field_id' => $field['id']), 'ord');
						$field['invalid_responses'] = array();
						if (in_array($field['type'], array('checkboxes', 'radios', 'select'))) {
							foreach ($lov as $valueId => $value) {
								if (!empty($value['is_invalid'])) {
									$field['invalid_responses'][] = (string)$valueId;
								}
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
			
			//Group repeat dataset fields
			if ($field['dataset_field_id'] && $field['repeat_start_id'] && isset($fields[$field['repeat_start_id']])) {
				$field['dataset_repeat_grouping'] = $fields[$field['repeat_start_id']]['dataset_field_id'];
			} elseif ($field['dataset_field_id'] && ($field['type'] == 'repeat_start')) {
				$field['dataset_repeat_grouping'] = $field['dataset_field_id'];
				$field['min_rows'] = $field['dataset_min_rows'];
				$field['max_rows'] = $field['dataset_max_rows'];
			}
			
			//Get readonly status
			$field['readonly_or_mandatory'] = 'none';
			if ($field['mandatory_if_visible']) {
				$field['readonly_or_mandatory'] = 'mandatory_if_visible';
			} elseif ($field['is_required']) {
				$field['readonly_or_mandatory'] = 'mandatory';
			} elseif ($field['is_readonly']) {
				$field['readonly_or_mandatory'] = 'readonly';
			} elseif ($field['mandatory_condition_field_id']) {
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
			
			$field['visible_condition_field_type'] = $field['visible_condition_invert'] ? 'visible_if_not' : 'visible_if';
			if ($field['visibility'] == 'visible_on_condition' && $field['visible_condition_field_id'] && isset($fields[$field['visible_condition_field_id']])) {
				$conditionFieldType = $fields[$field['visible_condition_field_id']]['type'];
				
				$values = explode(',', $field['visible_condition_field_value']);
				if (count($values) > 1 || $conditionFieldType == 'checkboxes') {
					$field['visible_condition_checkboxes_field_value'] = $values;
					if ($conditionFieldType != 'checkboxes') {
						$field['visible_condition_field_type'] = 'visible_if_one_of';
					}
				} elseif ($conditionFieldType == 'checkbox' || $conditionFieldType == 'group') {
					$field['visible_condition_field_value'] = $field['visible_condition_field_value'] ? 'checked' : 'unchecked';
				}
			}
			
			$field['mandatory_condition_field_type'] = $field['mandatory_condition_invert'] ? 'mandatory_if_not' : 'mandatory_if';
			if ($field['readonly_or_mandatory'] == 'conditional_mandatory' && $field['mandatory_condition_field_id'] && isset($fields[$field['mandatory_condition_field_id']])) {
				$conditionFieldType = $fields[$field['mandatory_condition_field_id']]['type'];
				if ($conditionFieldType == 'checkboxes') {
					$field['mandatory_condition_checkboxes_field_value'] = explode(',', $field['mandatory_condition_field_value']);
				} elseif ($conditionFieldType == 'checkbox' || $conditionFieldType == 'group') {
					$field['mandatory_condition_field_value'] = $field['mandatory_condition_field_value'] ? 'checked' : 'unchecked';
				}
			}
			
			foreach ($defaultValues as $defaultValueName => $defaultValue) {
				if (empty($field[$defaultValueName]) && (!isset($field[$defaultValueName]) || $field[$defaultValueName] !== 0)) {
					$field[$defaultValueName] = $defaultValue;
				}
			}
			
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
					
					$type = $row['field_type'] ? $row['field_type'] : $row['type'];
					
					//Get multi field CRM values
					if (in_array($type, array('checkboxes', 'select', 'radios', 'centralised_select', 'centralised_radios', 'checkbox', 'group'))) {
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
							if ($type == 'checkbox' || $type == 'group') {
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
		
		$pages = array();
		$pagesResult = getRows(ZENARIO_USER_FORMS_PREFIX . 'pages', true, array('form_id' => $formId), 'ord');
		while ($page = sqlFetchAssoc($pagesResult)) {
			$page['field_label'] = $page['label'];
			if ($page['visibility'] == 'visible_on_condition' && $page['visible_condition_field_id'] && isset($fields[$page['visible_condition_field_id']])) {
				$conditionFieldType = $fields[$page['visible_condition_field_id']]['type'];
				if ($conditionFieldType == 'checkboxes') {
					$page['visible_condition_checkboxes_field_value'] = explode(',', $page['visible_condition_field_value']);
				} elseif ($conditionFieldType == 'checkbox' || $conditionFieldType == 'group') {
					$page['visible_condition_field_value'] = $page['visible_condition_field_value'] ? 'checked' : 'unchecked';
				}
				$page['visible_condition_field_type'] = $page['visible_condition_invert'] ? 'visible_if_not' : 'visible_if';
			}
			$page['fields'] = $pageFields[$page['id']] ?? array();
			$pages[$page['id']] = $page;
		}
		
		$panel['fields'] = $fields;	
		$panel['items'] = $pages;
	}
	
	
	public function getPanelDatasetFields() {
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
		
		$this->getPanelDatasetFieldsByTab($dataset, $tabs);
		
		//Do not pass tabs with no fields
		foreach ($tabs as $tabName => $tab) {
			if (empty($tabs[$tabName]['fields'])) {
				unset($tabs[$tabName]);
			}
		}
		
		return $tabs;
	}
	
	private function getPanelDatasetFieldsByTab($dataset, &$tabs, $repeatStartId = false, $depth = 1) {
		if ($depth > 99) {
			return false;
		}
		
		$sql = '
			SELECT cdf.id, cdf.tab_name, cdf.is_system_field, cdf.fundamental, cdf.field_name, cdf.type, cdf.db_column, cdf.label, cdf.default_label, cdf.ord, cdf.values_source, cdf.values_source_filter
			FROM ' . DB_NAME_PREFIX . 'custom_dataset_fields cdf
			INNER JOIN ' . DB_NAME_PREFIX . 'custom_dataset_tabs cdt
				ON cdf.tab_name = cdt.name
				AND cdf.dataset_id = cdt.dataset_id
			WHERE cdf.dataset_id = ' . (int)$dataset['id'] . '
			AND cdf.field_name NOT IN ("identifier", "created_date", "modified_date", "last_login", "last_login_ip", "last_profile_update_in_frontend", "suspended_date", "email_verified", "status", "password", "password_needs_changing", "screen_name_confirmed", "send_activation_email_to_user", "terms_and_conditions_accepted")';
		if ($repeatStartId) {
			$sql .= '
				AND cdf.repeat_start_id = ' . (int)$repeatStartId;
		} else {
			$sql .= '
				AND cdf.type IN ("group", "checkbox", "checkboxes", "date", "editor", "radios", "centralised_radios", "select", "centralised_select", "text", "textarea", "url", "file_picker", "repeat_start")
			AND cdf.repeat_start_id = 0';
		} 
		$sql .= '
			ORDER BY cdt.ord, cdf.ord';
		
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			if (isset($tabs[$row['tab_name']])) {
				$row['ord'] = (int)$row['ord'];
				if (in_array($row['type'], array('checkboxes', 'radios', 'select', 'centralised_select', 'centralised_radios'))) {
					$row['lov'] = getDatasetFieldLOV($row['id'], false);
					foreach ($row['lov'] as $valueId => $value) {
						$row['lov'][$valueId]['id'] = $valueId;
					}
				}
				if (!$row['label'] && $row['default_label']) {
					$row['label'] = $row['default_label'];
				}
				
				if ($repeatStartId) {
					if (isset($tabs[$row['tab_name']]['fields'][$repeatStartId])) {
						$tabs[$row['tab_name']]['fields'][$repeatStartId]['fields'][$row['id']] = $row;
					}
				} else {
					$tabs[$row['tab_name']]['fields'][$row['id']] = $row;
				}
				
				//Nest dataset repeat fields into repeat start
				if ($row['type'] == 'repeat_start') {
					$this->getPanelDatasetFieldsByTab($dataset, $tabs, $row['id'], ++$depth);
				}
			}
		}
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		$formId = $refinerId;
		
		switch ($_POST['mode'] ?? false) {
			case 'save':
				$form = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('translate_text'), $formId);
				$languages = getLanguages(false, true, true);
				$crmEnabled = $this->isCRMEnabled($formId);
				
				$pagesJSON = $_POST['pages'] ?? false;
				$pages = json_decode($pagesJSON, true);
				$fieldsJSON = $_POST['fields'] ?? false;
				$fields = json_decode($fieldsJSON, true);
				
				$errors = array();
				$selectedFieldId = $_POST['selectedFieldId'] ?? false;
				$selectedPageId = $_POST['selectedPageId'] ?? false;
				$currentPageId = $_POST['currentPageId'] ?? false;
				$deletedPages = json_decode($_POST['deletedPages'] ?? false, true);
				$deletedFields = json_decode($_POST['deletedFields'] ?? false, true);
				$deletedValues = json_decode($_POST['deletedValues'] ?? false, true);
				
				$pagesReordered = isset($_POST['pagesReordered']) && $_POST['pagesReordered'] == 'true';
				$pageDeleted = false;
				$pageCreated = false;
				
				$existingPages = array();
				$result = getRows(ZENARIO_USER_FORMS_PREFIX . 'pages', array('id'), array('form_id' => $formId));
				while ($row = sqlFetchAssoc($result)) {
					$existingPages[$row['id']] = $row;
				}
				
				$existingFields = array();
				$result = getRows(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', array('id', 'page_id', 'repeat_start_id'), array('user_form_id' => $formId));
				while ($row = sqlFetchAssoc($result)) {
					$existingFields[$row['id']] = $row;
				}
				
				//Make sure requested changes are all valid before saving...
				$sortedData = array();
				if ($errors = $this->validateFormChanges($pages, $fields, $sortedData, $deletedPages, $deletedFields, $existingPages, $existingFields)) {
					exit(json_encode($errors));
				}
				
				//If valid, apply changes
				foreach ($deletedPages as $pageId) {
					if (isset($existingPages[$pageId])) {
						$pageDeleted = true;
						break;
					}
				}
				foreach ($deletedFields as $fieldId) {
					if (isset($existingFields[$fieldId])) {
						$existingPages[$existingFields[$fieldId]['page_id']]['_pageFieldRemoved'] = true;
					}
				}
				//Create new pages, fields, values
				$tempPageIdLink = array();
				$tempFieldIdLink = array();
				$tempValueIdLink = array();
				foreach ($sortedData as $pageIndex => &$page) {
					$pageId = $tempPageId = $page['id'];
					if (isset($page['_is_new'])) {
						$pageCreated = true;
						$page['_changed'] = true;
						$pageId = insertRow(ZENARIO_USER_FORMS_PREFIX . 'pages', array('form_id' => $formId));
					}
					$tempPageIdLink[$tempPageId] = $page['id'] = $pageId;
					
					foreach ($page['fields'] as $fieldIndex => $fieldId) {
						$field = &$fields[$fieldId];
						$fieldId = $tempFieldId = $field['id'];
						if (isset($field['_is_new'])) {
							if (isset($existingPages[$pageId])) {
								$existingPages[$pageId]['_pageFieldCreated'] = true;
							}
							$field['_changed'] = true;
							$fieldId = insertRow(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', array('user_form_id' => $formId, 'page_id' => $pageId));
						}
						$tempFieldIdLink[$tempFieldId] = $field['id'] = $fieldId;
						
						if (empty($field['dataset_field_id']) && ($field['type'] == 'checkboxes' || $field['type'] == 'radios' || $field['type'] == 'select')) {
							foreach ($field['lov'] as $valueIndex => $value) {
								$valueId = $tempValueId = $value['id'];
								if (isset($value['_is_new'])) {
									$valueId = insertRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', array('form_field_id' => $fieldId));
								}
								$tempValueIdLink[$tempValueId] = $valueId;
							}
						}
					}
					unset($field);
				}
				unset($page);
				
				$currentPageId = $tempPageIdLink[$currentPageId];
				if ($selectedPageId) {
					$selectedPageId = $tempPageIdLink[$selectedPageId];
				}
				if ($selectedFieldId) {
					$selectedFieldId = $tempFieldIdLink[$selectedFieldId];
				}
				
				//Update data
				$pageOrderChanged = $pagesReordered || $pageCreated || $pageDeleted;
				foreach ($sortedData as $pageIndex => $page) {
					$dPage = array();
					if (isset($existingPages[$page['id']])) {
						$dPage = $existingPages[$page['id']];
					}
					
					$values = array();
					if ($pageOrderChanged) {
						$values['ord'] = $pageIndex + 1;
					}
					if (isset($page['_changed'])) {
						$values['name'] = $this->sanitizeTextForSQL($page['name']);
						if (isset($page['label'])) {
							$values['label'] = $this->sanitizeTextForSQL($page['label']);
						}
						if (isset($page['next_button_text'])) {
							$values['next_button_text'] = $this->sanitizeTextForSQL($page['next_button_text']);
						}
						if (isset($page['previous_button_text'])) {
							$values['previous_button_text'] = $this->sanitizeTextForSQL($page['previous_button_text']);
						}
						$values['hide_in_page_switcher'] = !empty($page['hide_in_page_switcher']);
						$values = array_merge($values, $this->getVisibilityOptions($page, $fields, $tempFieldIdLink, $tempValueIdLink));
					}
					if ($values) {
						updateRow(ZENARIO_USER_FORMS_PREFIX . 'pages', $values, $page['id']);
					}
					
					$pageFieldOrderChanged = empty($dPage) || !empty($page['_pageFieldsReordered']) || !empty($dPage['_pageFieldCreated']) || !empty($dPage['_pageFieldRemoved']);
					
					$repeatStartField = false;
					foreach ($page['fields'] as $fieldIndex => $fieldId) {
						$field = $fields[$fieldId];
						$dField = array();
						if (isset($existingFields[$field['id']])) {
							$dField = $existingFields[$field['id']];
						}
						
						$values = array();
						if ($pageFieldOrderChanged) {
							$values['ord'] = $fieldIndex + 1;
							$values['page_id'] = $page['id'];
						}
						if (isset($field['_changed'])) {
							$fieldId = $field['id'];
							$values = array_merge(
								$values, 
								$this->getFormFieldOptions($field, $fields, $tempFieldIdLink, $tempValueIdLink), 
								$this->getVisibilityOptions($field, $fields, $tempFieldIdLink, $tempValueIdLink)
							);
							
							//Save translations
							if ($form['translate_text'] && !empty($field['_translations'])) {
								$this->updateFieldTranslations($form, $field, $languages);
							}
							//Save field CRM data
							if ($crmEnabled) {
								$this->updateFieldCRMData($formId, $fieldId, $field, $tempValueIdLink);
							}				
							//Update field values
							if (empty($field['dataset_field_id'])) {
								$this->updateFieldListOfValues($field, $tempValueIdLink);
							}
						}
						
						if ($repeatStartField || !empty($dField['repeat_start_id'])) {
							$values['repeat_start_id'] = $repeatStartField ? $repeatStartField['id'] : 0;
						}
						
						if ($values) {
							updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', $values, $field['id']);
						}
						
						//Migrate any data
						if (!empty($field['_migrate_responses_from']) && !empty($existingFields[$field['_migrate_responses_from']])) {
							//Delete existing responses
							deleteRow(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', array('form_field_id' => $field['id']));
							
							//Move responses
							updateRow(
								ZENARIO_USER_FORMS_PREFIX . 'user_response_data', 
								array('form_field_id' => $field['id']),
								array('form_field_id' => $field['_migrate_responses_from'])
							);
						}
						
						//Remember if we're in a repeat block or not
						if ($field['type'] == 'repeat_start') {
							$repeatStartField = $field;
						} elseif ($field['type'] == 'repeat_end') {
							$repeatStartField = false;
						}
					}
				}
				//Delete values
				foreach ($deletedValues as $valueId) {
					zenario_user_forms::deleteFormFieldValue($valueId);
				}
				//Delete fields
				foreach ($deletedFields as $fieldId) {
					if (isset($existingFields[$fieldId])) {
						zenario_user_forms::deleteFormField($fieldId);
					}
				}
				//Delete pages
				foreach ($deletedPages as $pageId) {
					if (isset($existingPages[$pageId])) {
						zenario_user_forms::deleteFormPage($pageId);
					}
				}
				
				echo json_encode(
					array(
						'errors' => $errors, 
						'currentPageId' => $currentPageId, 
						'selectedPageId' => $selectedPageId, 
						'selectedFieldId' => $selectedFieldId
					)
				);
				break;
			
			case 'get_centralised_lov':
				if ($method = $_POST['method'] ?? false) {
					if ($filter = $_POST['filter'] ?? false) {
						$mode = ZENARIO_CENTRALISED_LIST_MODE_FILTERED_LIST;
						$value = $filter;
					} else {
						$mode = ZENARIO_CENTRALISED_LIST_MODE_LIST;
						$value = false;
					}
					$lov = array();
					$params = explode('::', $method);
					if (inc($params[0])) {
						$result = call_user_func($_POST['method'] ?? false, $mode, $value);
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
	
	
	private function validateFormChanges($pages, &$fields, &$sortedData, $deletedPages, $deletedFields, $existingPages, $existingFields) {
		$errors = array();
		
		//Fields and pages that are saved on another field or pages details cannot be deleted without first removing them from the other field or page.
		$undeletableFields = array();
		$undeletablePages = array();
		//$undeletableFieldValues = array(); //TODO
		
		$sortedData = $pages;
		usort($sortedData, 'sortByOrd');
		foreach ($sortedData as $pageIndex => &$page) {
			if (isset($page['_changed']) || isset($page['_is_new'])) {
				//Check page details are valid
				//TODO
			}
			if (isset($page['visibility']) && $page['visibility'] == 'visible_on_condition' && !empty($page['visible_condition_field_id'])) {
				$undeletableFields[$page['visible_condition_field_id']] = true;
			}
			
			$page['fields'] = $page['fields'] ?? array();
			if (empty($page['fields'])) {
				continue;
			}
			$page['fields'] = array_keys($page['fields']);
			usort($page['fields'], function($a, $b) use($fields) {
				$a = $fields[$a];
				$b = $fields[$b];
				if ($a['ord'] == $b['ord']) {
					return 0;
				}
				return ($a['ord'] < $b['ord']) ? -1 : 1;
			});
			
			foreach ($page['fields'] as $fieldIndex => $fieldId) {
				$field = &$fields[$fieldId];
				
				//$undeletableFields[$fieldId] = true //TODO
				
				if (isset($field['_changed']) || isset($field['_is_new'])) {
					//Check field details are valid
					//TODO
				}
				
				if ($field['type'] == 'checkboxes' || $field['type'] == 'radios' || $field['type'] == 'select') {
					$field['lov'] = $field['lov'] ?? array();
					usort($field['lov'], 'sortByOrd');
				}
			}
		}
		return $errors;
	}
	
	private function sanitizeTextForSQL($text, $length = 250) {
		return mb_substr(trim($text), 0, $length, 'UTF-8');
	}
	
	private function getVisibilityOptions($item, $fields, $tempFieldIdLink, $tempValueIdLink) {
		$values = array();
		$values['visibility'] = $item['visibility'] ?? 'visible';
		$values['visible_condition_field_id'] = 0;
		$values['visible_condition_invert'] = 0;
		$values['visible_condition_checkboxes_operator'] = 'AND';
		$values['visible_condition_field_value'] = NULL;
		if ($values['visibility'] == 'visible_on_condition') {
			$values['visible_condition_field_id'] = (int)$tempFieldIdLink[$item['visible_condition_field_id']];
			$values['visible_condition_invert'] = ($item['visible_condition_field_type'] == 'visible_if_not');
			$conditionFieldType = $fields[$item['visible_condition_field_id']]['type'];
			
			if ($item['visible_condition_field_type'] == 'visible_if_one_of' || $conditionFieldType == 'checkboxes') {
				$tValues = array();
				foreach ($item['visible_condition_checkboxes_field_value'] as $tValue) {
					if (empty($fields[$item['visible_condition_field_id']]['dataset_field_id'])) {
						$tValue = $tempValueIdLink[$tValue] ?? '';
					}
					$tValues[] = $tValue;
				}
				$values['visible_condition_field_value'] = implode(',', $tValues);
				if ($conditionFieldType == 'checkboxes') {
					$values['visible_condition_checkboxes_operator'] = $item['visible_condition_checkboxes_operator'];
				}
			} else {
				switch ($conditionFieldType) {
					case 'select':
					case 'radios':
						$value = $item['visible_condition_field_value'];
						if (empty($fields[$item['visible_condition_field_id']]['dataset_field_id'])) {
							$value = $tempValueIdLink[$value] ?? '';
						}
						$values['visible_condition_field_value'] = $value;
						break;
					case 'checkbox':
					case 'group':
						$values['visible_condition_field_value'] = (!empty($item['visible_condition_field_value']) && $item['visible_condition_field_value'] == 'checked') ? 1 : 0;
						break;
					default:
						$values['visible_condition_field_value'] = $this->sanitizeTextForSQL($item['visible_condition_field_value']);
						break;
				}
			}
		}
		return $values;
	}
	
	
	
	private function isCRMEnabled($formId) {
		if (inc('zenario_crm_form_integration')) {
			if (checkRowExists(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_data', array('form_id' => $formId, 'enable_crm_integration' => 1))) {
				return true;
			}
		}
		return false;
	}
	
	private function updateFieldCRMData($formId, $fieldId, $field, $tempValueIdLink) {
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
			
			
			if (($field['type'] == 'checkbox' || $field['type'] == 'group') && !empty($field['_crm_data']['values'])) {
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
					
					foreach ($field['lov'] as $i => $lovValue) {
						$lovId = $lovValue['id'];
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
						} elseif (!empty($field['dataset_field_id'])) {
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
	
	private function updateFieldListOfValues($field, $tempValueIdLink) {
		if ($field['type'] == 'checkboxes' || $field['type'] == 'radios' || $field['type'] == 'select') {
			foreach ($field['lov'] as $valueIndex => $value) {
				$valueId = $tempValueIdLink[$value['id']];
				$values = array(
					'ord' => $valueIndex + 1,
					'label' => $this->sanitizeTextForSQL($value['label']),
					'is_invalid' => !empty($field['invalid_responses']) && in_array($valueId, $field['invalid_responses'])
				);
				updateRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', $values, $valueId);
			}
		}
	}
	
	private function updateFieldTranslations($form, $field, $languages) {
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
	
	private function getFormFieldOptions($field, $fields, $tempFieldIdLink, $tempValueIdLink) {
		$values = array();
		$values['user_field_id'] = 0;
		if (!empty($field['dataset_field_id'])) {
			$values['user_field_id'] = (int)$field['dataset_field_id'];
		} else {
			$values['field_type'] = $field['type'];
		}
		
		$values['name'] = $this->sanitizeTextForSQL($field['name']);
		if (isset($field['field_label'])) {
			$values['label'] = $this->sanitizeTextForSQL($field['field_label']);
		}
		
		$values['placeholder'] = null;
		if (isset($field['placeholder'])) {
			$values['placeholder'] = $this->sanitizeTextForSQL($field['placeholder']);
		}
		
		$readonlyOrMandatory = !empty($field['readonly_or_mandatory']) ? $field['readonly_or_mandatory'] : false;
		$values['is_readonly'] = ($readonlyOrMandatory == 'readonly');
		$values['is_required'] = ($readonlyOrMandatory == 'mandatory');
		$values['mandatory_if_visible'] = ($readonlyOrMandatory == 'mandatory_if_visible');
		
		$values['required_error_message'] = null;
		if ($readonlyOrMandatory == 'mandatory' || $readonlyOrMandatory == 'conditional_mandatory' || $readonlyOrMandatory == 'mandatory_if_visible') {
			$values['required_error_message'] = $this->sanitizeTextForSQL($field['required_error_message']);
		}
		
		$values['mandatory_condition_field_id'] = 0;
		$values['mandatory_condition_invert'] = 0;
		$values['mandatory_condition_checkboxes_operator'] = 'AND';
		$values['mandatory_condition_field_value'] = null;
		if ($readonlyOrMandatory == 'conditional_mandatory') {
			$values['mandatory_condition_field_id'] = (int)$tempFieldIdLink[$field['mandatory_condition_field_id']];
			$values['mandatory_condition_invert'] = ($field['mandatory_condition_field_type'] == 'mandatory_if_not');
			$conditionFieldType = $fields[$field['mandatory_condition_field_id']]['type'];
			switch ($conditionFieldType) {
				case 'select':
				case 'radios':
					$value = $field['mandatory_condition_field_value'];
					if (empty($fields[$field['mandatory_condition_field_id']]['dataset_field_id'])) {
						$value = $tempValueIdLink[$value] ?? '';
					}
					$values['mandatory_condition_field_value'] = $value;
					break;
				case 'checkboxes':
					$values['mandatory_condition_checkboxes_operator'] = $field['mandatory_condition_checkboxes_operator'];
					if ($field['mandatory_condition_checkboxes_field_value']) {
						$tValues = array();
						foreach ($field['mandatory_condition_checkboxes_field_value'] as $tValue) {
							if (empty($fields[$field['mandatory_condition_field_id']]['dataset_field_id'])) {
								$tValue = $tempValueIdLink[$tValue] ?? '';
							}
							$tValues[] = $tValue;
						}
						$values['mandatory_condition_field_value'] = implode(',', $tValues);
					}
					break;
				case 'checkbox':
				case 'group':
					$values['mandatory_condition_field_value'] = (!empty($field['mandatory_condition_field_value']) && $field['mandatory_condition_field_value'] == 'checked') ? 1 : 0;
					break;
				default:
					$values['mandatory_condition_field_value'] = $this->sanitizeTextForSQL($field['mandatory_condition_field_value']);
					break;
			}
		}
		
		$values['custom_code_name'] = null;
		if (!empty($field['custom_code_name'])) {
			$values['custom_code_name'] = $this->sanitizeTextForSQL($field['custom_code_name']);
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
				$values['default_value'] = $this->sanitizeTextForSQL($field['default_value_text']);
			}
			
		} elseif ($defaultValueMode == 'method') {
			if (!empty($field['default_value_class_name'])) {
				$values['default_value_class_name'] = $this->sanitizeTextForSQL($field['default_value_class_name']);
			}
			if (!empty($field['default_value_method_name'])) {
				$values['default_value_method_name'] = $this->sanitizeTextForSQL($field['default_value_method_name']);
			}
			if (isset($field['default_value_param_1'])) {
				$values['default_value_param_1'] = $this->sanitizeTextForSQL($field['default_value_param_1']);
			}
			if (isset($field['default_value_param_2'])) {
				$values['default_value_param_2'] = $this->sanitizeTextForSQL($field['default_value_param_2']);
			}
		}
		
		$values['autocomplete'] = 0;
		$values['autocomplete_no_filter_placeholder'] = null;
		if (!empty($field['autocomplete'])) {
			$values['autocomplete'] = 1;
			if (isset($field['autocomplete_no_filter_placeholder'])) {
				$values['autocomplete_no_filter_placeholder'] = $this->sanitizeTextForSQL($field['autocomplete_no_filter_placeholder']);
			}
		}
		
		$values['note_to_user'] = null;
		if (isset($field['note_to_user'])) {
			$values['note_to_user'] = $this->sanitizeTextForSQL($field['note_to_user']);
		}
		$values['css_classes'] = null;
		if (isset($field['css_classes'])) {
			$values['css_classes'] = $this->sanitizeTextForSQL($field['css_classes']);
		}
		$values['div_wrap_class'] = null;
		if (isset($field['div_wrap_class'])) {
			$values['div_wrap_class'] = $this->sanitizeTextForSQL($field['div_wrap_class']);
		}
		
		$values['validation'] = null;
		$values['validation_error_message'] = null;
		if (!empty($field['field_validation']) && $field['field_validation'] != 'none') {
			$values['validation'] = $field['field_validation'];
			$values['validation_error_message'] = $this->sanitizeTextForSQL($field['field_validation_error_message']);
		}
		
		$values['description'] = null;
		if (isset($field['description'])) {
			$values['description'] = $this->sanitizeTextForSQL($field['description'], 65535);
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
	 		$values['values_source'] = $this->sanitizeTextForSQL($field['values_source']);
	 		if (!empty($field['values_source_filter'])) {
	 			$values['values_source_filter'] = $this->sanitizeTextForSQL($field['values_source_filter']);
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
	 	
	 	$values['show_month_year_selectors'] = 0;
	 	if (!empty($field['show_month_year_selectors'])) {
	 		$values['show_month_year_selectors'] = (int)$field['show_month_year_selectors'];
	 	}
	 	
	 	$values['no_past_dates'] = 0;
	 	if (!empty($field['no_past_dates'])) {
	 		$values['no_past_dates'] = (int)$field['no_past_dates'];
	 	}
	 	
	 	$values['disable_manual_input'] = 0;
	 	if (!empty($field['disable_manual_input'])) {
	 		$values['disable_manual_input'] = (int)$field['disable_manual_input'];
	 	}
	 	
	 	$values['invalid_field_value_error_message'] = null;
	 	if (!empty($field['invalid_responses']) && !empty($field['invalid_field_value_error_message'])) {
	 		$values['invalid_field_value_error_message'] = $this->sanitizeTextForSQL($field['invalid_field_value_error_message']);
	 	}
	 	
	 	$values['word_count_max'] = null;
	 	if (!empty($field['word_count_max']) && (int)$field['word_count_max'] > 0) {
	 		$values['word_count_max'] = (int)$field['word_count_max'];
	 	}
	 	
	 	$values['word_count_min'] = null;
	 	if (!empty($field['word_count_min']) && (int)$field['word_count_min'] > 0) {
	 		$values['word_count_min'] = (int)$field['word_count_min'];
	 	}
	 	
	 	$values['combined_filename'] = null;
	 	if (!empty($field['combined_filename'])) {
	 		$values['combined_filename'] = preg_replace('/[^\w-]/', '_', $this->sanitizeTextForSQL($field['combined_filename']));
	 	}
	 	
	 	$values['calculation_code'] = '';
		if ($field['type'] == 'calculated') {
			if (!empty($field['calculation_code'])) {
				foreach ($field['calculation_code'] as $index => $step) {
					if ($step['type'] == 'field') {
						$field['calculation_code'][$index]['value'] = (int)$tempFieldIdLink[$field['calculation_code'][$index]['value']];
					}
				}
				$values['calculation_code'] = json_encode($field['calculation_code']);
			}
		}
		
		$values['restatement_field'] = 0;
		if (!empty($field['restatement_field'])) {
			$values['restatement_field'] = (int)$tempFieldIdLink[$field['restatement_field']];
		}
		
		$values['filter_on_field'] = !empty($field['filter_on_field']) ? (int)$tempFieldIdLink[$field['filter_on_field']] : 0;
	 	
	 	return $values;
	}
	
	private static function getFormFields($formId) {
		$fields = array();
		$sql = '
			SELECT 
				uff.id, 
				uff.user_form_id,
				uff.page_id,
				uff.ord, 
				uff.is_readonly, 
				uff.is_required,
				uff.mandatory_if_visible,
				uff.mandatory_condition_field_id,
				uff.mandatory_condition_invert,
				uff.mandatory_condition_checkboxes_operator,
				uff.mandatory_condition_field_value,
				uff.visibility,
				uff.visible_condition_field_id,
				uff.visible_condition_invert,
				uff.visible_condition_checkboxes_operator,
				uff.visible_condition_field_value,
				uff.label,
				uff.name,
				uff.placeholder,
				uff.preload_dataset_field_user_data,
				uff.default_value,
				uff.default_value_class_name,
				uff.default_value_method_name,
				uff.default_value_param_1,
				uff.default_value_param_2,
				uff.note_to_user,
				uff.css_classes,
				uff.div_wrap_class,
				uff.required_error_message,
				uff.validation AS field_validation,
				uff.validation_error_message AS field_validation_error_message,
				uff.field_type,
				uff.description,
				uff.calculation_code,
				uff.value_prefix,
				uff.value_postfix,
				uff.restatement_field,
				uff.values_source,
				uff.values_source_filter,
				uff.custom_code_name,
				uff.autocomplete,
				uff.autocomplete_no_filter_placeholder,
				uff.value_field_columns,
				uff.min_rows,
				uff.max_rows,
				uff.show_month_year_selectors,
				uff.no_past_dates,
				uff.disable_manual_input,
				uff.invalid_field_value_error_message,
				uff.word_count_max,
				uff.word_count_min,
				uff.combined_filename,
				uff.filter_on_field,
				uff.repeat_start_id,
				cdf.id AS dataset_field_id, 
				cdf.type, 
				cdf.db_column, 
				cdf.label AS dataset_field_label,
				cdf.default_label,
				cdf.is_system_field, 
				cdf.dataset_id, 
				cdf.validation AS dataset_field_validation, 
				cdf.validation_message AS dataset_field_validation_message,
				cdf.multiple_select,
				cdf.store_file,
				cdf.extensions,
				cdf.values_source AS dataset_values_source,
				cdf.min_rows AS dataset_min_rows,
				cdf.max_rows AS dataset_max_rows,
				cdf.repeat_start_id AS dataset_repeat_start_id
			FROM ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms AS uf
			INNER JOIN ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields AS uff
				ON uf.id = uff.user_form_id
			LEFT JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields AS cdf
				ON uff.user_field_id = cdf.id
			WHERE TRUE';
		if ($formId) {
			$sql .= '
				AND uff.user_form_id = ' . (int)$formId;
		}
		$sql .= '
			ORDER BY uff.ord';
		$result = sqlSelect($sql);
		$repeatBlockFields = array();
		while ($field = sqlFetchAssoc($result)) {
			if ($field['field_type']) {
				$field['type'] = $field['field_type'];
			}
			$fields[$field['id']] = $field;
		}
		
		return $fields;
	}
}