<?php
/*
 * Copyright (c) 2022, Tribal Limited
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

class zenario_user_forms__organizer__form_fields_gui extends ze\moduleBaseClass {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$formId = $refinerId;
		$form = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['name', 'type', 'title', 'translate_text', 'enable_summary_page'], $formId);
		
		$panel['title'] = ze\admin::phrase('Form fields for "[[name]]"', $form);
		$panel['form'] = $form;
		$panel['link'] = ze\link::absolute();
		
		//Get the pages of this form
		$panel['pages'] = zenario_user_forms::getFormPages($formId);
		
		//Check if translations are enabled on this form
		if ($form['translate_text']) {
			$panel['languages'] = [];
			$languages = ze\lang::getLanguages(false, true, true);
			$ord = 0;
			foreach ($languages as $languageId => $language) {
				if ($language['translate_phrases']) {
					$panel['show_translation_tab'] = true;
				}
				$panel['languages'][$languageId] = $language;
				$panel['languages'][$languageId]['ord'] = ++$ord;
			}
		}
		$phraseFields = [];
		if (!empty($panel['show_translation_tab'])) {
			foreach ($panel['form_field_details']['tabs'] as $tuixTab) {
				foreach ($tuixTab['fields'] as $tuixFieldId => $tuixField) {
					if (!empty($tuixField['is_phrase'])) {
						$phraseFields[] = $tuixFieldId;
					}
				}
			}
		}
		
		//Get the fields of this form
		$panel['items'] = zenario_user_forms::getFormFieldsStatic($formId);
		foreach ($panel['items'] as $fieldId => &$field) {
			$panel['pages'][$field['page_id']]['fields'][$fieldId] = 1;
			
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
			
			//Get visibility values
			//$field['visible_condition_field_type'] = $field['visible_condition_invert'] ? 'visible_if_not' : 'visible_if';
			if($field['visible_condition_invert'] == 0){
			    $field['visible_condition_field_type'] = 'visible_if';
			    
			}else if($field['visible_condition_invert'] == 1){
			    $field['visible_condition_field_type'] = 'visible_if_not';
			} else {
			     $field['visible_condition_field_type'] = 'visible_if_one_of';
			}
			if ($field['visibility'] == 'visible_on_condition' && $field['visible_condition_field_id'] && isset($panel['items'][$field['visible_condition_field_id']])) {
				$conditionFieldType = $panel['items'][$field['visible_condition_field_id']]['type'];
				
				$values = explode(',', $field['visible_condition_field_value']);
				if (
					count($values) > 1
					|| (
						$conditionFieldType == 'checkboxes'
						|| $conditionFieldType == 'select'
						|| $conditionFieldType == 'centralised_select'
						|| $conditionFieldType == 'radios'
						|| $conditionFieldType == 'centralised_radios'
					)
				) {
					$field['visible_condition_checkboxes_field_value'] = $values;
			        if (count($values)>1 && $conditionFieldType != 'checkboxes') {
						$field['visible_condition_field_type'] = 'visible_if_one_of';
					}
				} elseif ($conditionFieldType == 'checkbox' || $conditionFieldType == 'group') {
					$field['visible_condition_field_value'] = $field['visible_condition_field_value'] ? 'checked' : 'unchecked';
				}
			}
			
			//Get readonly / mandatory values
			//$field['mandatory_condition_field_type'] = $field['mandatory_condition_invert'] ? 'mandatory_if_not' : 'mandatory_if';
			
			if($field['mandatory_condition_invert'] == 0){
			    $field['mandatory_condition_field_type'] = 'mandatory_if';
			    
			}else if($field['mandatory_condition_invert'] == 1){
			    $field['mandatory_condition_field_type'] = 'mandatory_if_not';
			} else {
			     $field['mandatory_condition_field_type'] = 'mandatory_if_one_of';
			}
			if ($field['readonly_or_mandatory'] == 'conditional_mandatory' && $field['mandatory_condition_field_id'] && isset($panel['items'][$field['mandatory_condition_field_id']])) {
				$conditionFieldType = $panel['items'][$field['mandatory_condition_field_id']]['type'];
				
				$values = explode(',', $field['mandatory_condition_field_value']); 
				if (count($values) > 1  || $conditionFieldType == 'checkboxes') {
					$field['mandatory_condition_checkboxes_field_value'] = $values;
					if (count($values) > 1 && $conditionFieldType != 'checkboxes') {
						$field['mandatory_condition_field_type'] = 'mandatory_if_one_of';
						$field['mandatory_condition_invert'] = 2;
					}
				} elseif ($conditionFieldType == 'checkbox' || $conditionFieldType == 'group') {
					$field['mandatory_condition_field_value'] = $field['mandatory_condition_field_value'] ? 'checked' : 'unchecked';
				}
			}
			
			//Get default value status
			$field['default_value_options'] = 'none';
			if ($field['default_value'] !== null && $field['default_value'] !== '') {
				$field['default_value_options'] = 'value';
				if (in_array($field['type'], ['checkbox', 'group'])) {
					$field['default_value_lov'] = $field['default_value'] ? 'checked' : 'unchecked';
				} elseif (in_array($field['type'], ['radios', 'centralised_radios', 'select', 'centralised_select'])) {
					$field['default_value_lov'] = $field['default_value'];
				} else {
					$field['default_value_text'] = $field['default_value'];
				}
			} elseif ($field['default_value_class_name'] && $field['default_value_method_name']) {
				$field['default_value_options'] = 'method';
			}
			
			if ($field['suggested_values']) {
				$field['enable_suggested_values'] = true;
				if ($field['suggested_values'] == 'pre_defined') {
					$field['suggested_values_source'] = $field['values_source'];
					$field['suggested_values_filter_on_field'] = $field['filter_on_field'];
				}
			}
			
			//Get field list of values
			if (in_array($field['type'], ['checkboxes', 'radios', 'select', 'centralised_radios', 'centralised_select']) || ($field['type'] == 'text' && $field['suggested_values'] == 'custom')) {
				$lov = zenario_user_forms::getFormFieldLOVStatic($field, $field['values_source_filter']);
				$field['lov'] = [];
				$ord = 0;
				foreach ($lov as $valueId => $label) {
					$field['lov'][$valueId] = [
						'label' => $label, 
						'ord' => ++$ord
					];
				}
				
				$field['invalid_responses'] = array_map('strval', array_values(ze\row::getAssocs(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', 'id', ['form_field_id' => $field['id'], 'is_invalid' => true], 'ord')));
				
			}
			
			if ($field['type'] == 'calculated') {
				$field['calculation_code'] = json_decode($field['calculation_code']);
			}
			
			//Group repeat dataset fields
			if ($field['dataset_field_id'] && $field['repeat_start_id'] && isset($panel['items'][$field['repeat_start_id']])) {
				$field['dataset_repeat_grouping'] = $panel['items'][$field['repeat_start_id']]['dataset_field_id'];
			} elseif ($field['dataset_field_id'] && ($field['type'] == 'repeat_start')) {
				$field['dataset_repeat_grouping'] = $field['dataset_field_id'];
				$field['min_rows'] = $field['dataset_min_rows'];
				$field['max_rows'] = $field['dataset_max_rows'];
			}
			
			if (!empty($panel['show_translation_tab'])) {
				$field['translations'] = [];
				foreach ($phraseFields as $tuixFieldId) {
					$field['translations'][$tuixFieldId] = [];
					if (isset($field[$tuixFieldId])) {
						$phrasesResult = ze\row::query(
							'visitor_phrases', 
							['local_text', 'language_id'], 
							['code' => $field[$tuixFieldId], 'module_class_name' => 'zenario_user_forms']
						);
						while ($row = ze\sql::fetchAssoc($phrasesResult)) {
							if (!empty($panel['languages'][$row['language_id']]['translate_phrases'])) {
								$field['translations'][$tuixFieldId][$row['language_id']] = $row['local_text'];
							}
						}
					}
				}
			}
		}
		unset($field);
		
		foreach ($panel['pages'] as $pageId => &$page) {
			//$page['visible_condition_field_type'] = $page['visible_condition_invert'] ? 'visible_if_not' : 'visible_if';
			if($page['visible_condition_invert'] == 0){
			    $page['visible_condition_field_type'] = 'visible_if';
			    
			}else if($page['visible_condition_invert'] == 1){
			    $page['visible_condition_field_type'] = 'visible_if_not';
			} else {
			     $page['visible_condition_field_type'] = 'visible_if_one_of';
			}
			if ($page['visibility'] == 'visible_on_condition' && $page['visible_condition_field_id'] && isset($panel['items'][$page['visible_condition_field_id']])) {
				$conditionFieldType = $panel['items'][$page['visible_condition_field_id']]['type'];
				$values = explode(',', $page['visible_condition_field_value']);
				if (count($values) > 1 || $conditionFieldType == 'checkboxes') {
					$page['visible_condition_checkboxes_field_value'] = $values;
					if ($conditionFieldType != 'checkboxes') {
						$page['visible_condition_field_type'] = 'visible_if_one_of';
						$page['visible_condition_invert'] = 2;
					}
				} elseif ($conditionFieldType == 'checkbox' || $conditionFieldType == 'group' || $conditionFieldType == 'consent') {
					$page['visible_condition_field_value'] = $page['visible_condition_field_value'] ? 'checked' : 'unchecked';
				}
			}
		}
		unset($page);
		
		//Get a link to the users dataset panel
		$dataset = ze\dataset::details('users');
		$panel['link_to_dataset'] = ze\link::absolute() . 'organizer.php#zenario__administration/panels/custom_datasets//' . $dataset['id'];
		
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
		
		//Get dataset tabs and fields
		$panel['dataset'] = $this->getPanelDatasetInfo();
		
		//Check if CRM is enabled on this form
		$panel['crm_enabled'] = zenario_user_forms::isFormCRMEnabled($formId, false);
		
		//Check if the form is not in use OR is on a pubic page so that if the "email" dataset field is missing from the form
		//and another dataset field is present, a warning can be displayed.
		$instanceIds = zenario_user_forms::getFormPlugins($formId);
		$panel['not_used_or_on_public_page'] =
			!ze\pluginAdm::usage($instanceIds)
			|| ze\pluginAdm::usage($instanceIds, $publishedOnly = false, $itemLayerOnly = false, $reportContentItems = false, $publicPagesOnly = true);
		
		
		//Get CRM data for form fields if crm module is running
		if ($panel['crm_enabled']) {
			$sql = '
				SELECT cf.form_field_id, cf.name AS field_crm_name, cf.send_condition, uff.user_field_id, uff.field_type, cdf.type
				FROM ' . DB_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields cf
				INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
					ON uff.user_form_id = ' . (int)$formId . '
					AND cf.form_field_id = uff.id
				LEFT JOIN ' . DB_PREFIX . 'custom_dataset_fields cdf
					ON uff.user_field_id = cdf.id';
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
				if (!isset($panel['items'][$row['form_field_id']])) {
					continue;
				}
				//Get CRM field name
				$panel['items'][$row['form_field_id']]['field_crm_name'] = $row['field_crm_name'];
				$panel['items'][$row['form_field_id']]['send_to_crm'] = true;
				$panel['items'][$row['form_field_id']]['crm_send_condition'] = $row['send_condition'];
				$type = $row['field_type'] ? $row['field_type'] : $row['type'];
				
				//Get multi field CRM values
				if (!in_array($type, ['checkboxes', 'select', 'radios', 'centralised_select', 'centralised_radios', 'checkbox', 'group', 'consent'])) {
					continue;
				}
				
				$crmValues = ze\row::query(
					ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_field_values', 
					[
						'form_field_value_dataset_id', 
						'form_field_value_unlinked_id', 
						'form_field_value_centralised_key', 
						'form_field_value_checkbox_state',
						'value'
					], 
					['form_field_id' => $row['form_field_id']]
				);
				while ($crmValue = ze\sql::fetchAssoc($crmValues)) {
					if ($type == 'checkbox' || $type == 'group' || $type == 'consent') {
						$state = $crmValue['form_field_value_checkbox_state'] ? 'checked' : 'unchecked';
						$panel['items'][$row['form_field_id']]['crm_lov'][$state] = $crmValue['value'];
					} elseif ($type == 'centralised_select' || $type == 'centralised_radios') {
						if (isset($panel['items'][$row['form_field_id']]['lov'][$crmValue['form_field_value_centralised_key']])) {
							$panel['items'][$row['form_field_id']]['crm_lov'][$crmValue['form_field_value_centralised_key']] = $crmValue['value'];
						}
					} elseif ($row['user_field_id']) {
						if (isset($panel['items'][$row['form_field_id']]['lov'][$crmValue['form_field_value_dataset_id']])) {
							$panel['items'][$row['form_field_id']]['crm_lov'][$crmValue['form_field_value_dataset_id']] = $crmValue['value'];
						}
					} else {
						if (isset($panel['items'][$row['form_field_id']]['lov'][$crmValue['form_field_value_unlinked_id']])) {
							$panel['items'][$row['form_field_id']]['crm_lov'][$crmValue['form_field_value_unlinked_id']] = $crmValue['value'];
						}
					}
				}
			}
		}
		
		//Hide salesforce validation button if not enabled on form
		$panel['form_field_details']['tabs']['crm']['fields']['crm_validate_test']['hidden'] =  !zenario_user_forms::isFormCRMEnabled($formId, 'salesforce');
	}
	
	
	public function getPanelDatasetInfo() {
		$info = [];
		$info['tabs'] = [];
		$info['fields'] = [];
		$dataset = ze\dataset::details('users');
		
		//Get dataset tabs
		$result = ze\row::query(
			'custom_dataset_tabs', 
			['is_system_field', 'name', 'label', 'default_label', 'ord'], 
			['dataset_id' => $dataset['id']]
		);
		while ($row = ze\sql::fetchAssoc($result)) {
			$row['ord'] = (int)$row['ord'];
			$row['fields'] = [];
			$info['tabs'][$row['name']] = $row;
		}
		
		//Get dataset fields
		$sql = '
			SELECT
				cdf.id, 
				cdf.tab_name, 
				cdf.is_system_field, 
				cdf.fundamental, 
				cdf.field_name, 
				cdf.type, 
				cdf.db_column, 
				cdf.label, 
				cdf.default_label, 
				cdf.ord, 
				cdf.values_source, 
				cdf.values_source_filter,
				cdf.repeat_start_id
			FROM ' . DB_PREFIX . 'custom_dataset_fields cdf
			WHERE cdf.dataset_id = ' . (int)$dataset['id'] . '
			AND (
				!cdf.is_system_field 
				OR cdf.field_name IN (	
					"email",
					"salutation",
					"first_name",
					"last_name",
					"screen_name",
					"terms_and_conditions_accepted"
				)
			)';
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$row['ord'] = (int)$row['ord'];
			if (!$row['label'] && $row['default_label']) {
				$row['label'] = $row['default_label'];
			}
			
			if (in_array($row['type'], ['checkboxes', 'radios', 'select', 'centralised_select', 'centralised_radios'])) {
				$row['lov'] = ze\dataset::fieldLOV($row['id'], false);
			}
			
			$info['tabs'][$row['tab_name']]['fields'][$row['id']] = 1;
			$info['fields'][$row['id']] = $row;
		}
		
		//Remove tabs with no fields
		foreach ($info['tabs'] as $tabName => $tab) {
			if (empty($tab['fields'])) {
				unset($tab);
			}
		}
		return $info;
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		$formId = $refinerId;
		
		switch ($_POST['mode'] ?? false) {
			//Note, the only validation done on this data is client-side. It was moved there in order to speed up editing so you don't 
			//have an ajax request every time it needed to run. In the future it may be nessesary to have server-side validation as well.
			case 'save':
				$form = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['translate_text'], $formId);
				$languages = ze\lang::getLanguages(false, true, true);
				$crmEnabled = zenario_user_forms::isFormCRMEnabled($formId, false);
				$errors = [];
						
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
				
				$existingPages = zenario_user_forms::getFormPages($formId);
				$existingFields = zenario_user_forms::getFormFieldsStatic($formId);
				
				$sortedData = $this->getSortedData($pages, $fields);
				
				foreach ($deletedPages as $pageId) {
					if (isset($existingPages[$pageId])) {
						$existingPageDeleted = true;
						break;
					}
				}
				foreach ($deletedFields as $fieldId) {
					if (isset($existingFields[$fieldId])) {
						$existingPages[$existingFields[$fieldId]['page_id']]['field_deleted'] = true;
					}
				}
				
				$tempPageIdLink = [];
				$tempFieldIdLink = [];
				$tempValueIdLink = [];
				foreach ($sortedData as $pageIndex => &$page) {
					
					//Create new pages
					$pageId = $tempPageId = $page['id'];
					if (!is_numeric($pageId)) {
						$pageCreated = true;
						$page['_new'] = true;
						$pageId = ze\row::insert(ZENARIO_USER_FORMS_PREFIX . 'pages', ['form_id' => $formId]);
					}
					$tempPageIdLink[$tempPageId] = $page['id'] = $pageId;
					
					foreach ($page['fields'] as $fieldIndex => $fieldId) {
						$field = &$fields[$fieldId];
						
						//Create new fields
						$tempFieldId = $fieldId;
						if (!is_numeric($fieldId)) {
							if (isset($existingPages[$pageId])) {
								$existingPages[$pageId]['field_created'] = true;
							}
							$field['_new'] = true;
							$fieldId = ze\row::insert(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', ['user_form_id' => $formId, 'page_id' => $pageId]);
						}
						$tempFieldIdLink[$tempFieldId] = $field['id'] = $fieldId;
						
						//Create new field values
						if (((in_array($field['type'], ['checkboxes', 'radios', 'select']) && empty($field['dataset_field_id'])) || $field['type'] == 'text') && !empty($field['lov'])) {
							foreach ($field['lov'] as $valueId => $value) {
								$tempValueId = $valueId;
								if (!is_numeric($valueId)) {
									$valueId = ze\row::insert(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', ['form_field_id' => $fieldId]);
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
				
				$phraseFields = [];
				foreach ($fieldsTUIX['tabs'] as $tuixTabId => $tuixTab) {
					foreach ($tuixTab['fields'] as $tuixFieldId => $tuixField) {
						if (!empty($tuixField['is_phrase'])) {
							$phraseFields[$tuixFieldId] = $tuixField['db_column'] ?? $tuixFieldId;
						}
					}
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
						$values = array_merge(
							$values, 
							$this->getFormPageOptions($page, $fields, $tempFieldIdLink, $tempValueIdLink),
							$this->getVisibilityOptions($page, $fields, $tempFieldIdLink, $tempValueIdLink)
						);
					}
					if ($values) {
						ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'pages', $values, $page['id']);
					}
					
					
					$pageFieldOrderChanged = !$existingPage || !empty($page['fields_reordered']) || !empty($existingPage['field_created']) || !empty($existingPage['field_deleted']);
					
					$repeatStartField = false;
					foreach ($page['fields'] as $fieldIndex => $fieldId) {
						$field = $fields[$fieldId];
						$existingField = $existingFields[$fieldId] ?? false;
						
						$values = [];
						
						//Update field ordinal / page
						if ($pageFieldOrderChanged) {
							$values['ord'] = $fieldIndex + 1;
							$values['page_id'] = $page['id'];
						}
						//Update field data
						if (isset($field['_changed']) || isset($field['_new'])) {
							$fieldId = $field['id'];
							$values = array_merge(
								$values, 
								$this->getFormFieldOptions($field, $fields, $tempFieldIdLink, $tempValueIdLink), 
								$this->getVisibilityOptions($field, $fields, $tempFieldIdLink, $tempValueIdLink)
							);
							
							//Save translations from tuix fields with "is_phrase" property set
							if ($form['translate_text'] && !empty($field['translations'])) {
								$this->updateFieldTranslations($field, $existingField, $languages, $phraseFields);
							}
							//Save field CRM data
							if ($crmEnabled) {
								$this->updateFieldCRMData($formId, $fieldId, $field, $tempValueIdLink);
							}
							//Update field values
							if (empty($field['dataset_field_id']) || ($field['type'] == 'text' && !empty($field['enable_suggested_values']) && $field['suggested_values'] == 'custom')) {
								$this->updateFieldListOfValues($field, $tempValueIdLink);
							}
							
							//Delete values
							if ($field['type'] == 'text' && empty($field['enable_suggested_values']) && !empty($field['lov'])) {
								$deletedValues = array_merge($deletedValues, array_keys($field['lov']));
								$field['lov'] = [];
							}
						}
						
						if ($repeatStartField || !empty($existingField['repeat_start_id'])) {
							$values['repeat_start_id'] = $repeatStartField ? $repeatStartField['id'] : 0;
						}
						
						if ($values) {
							ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', $values, $field['id']);
						}
						
						//Migrate any data
						if (!empty($field['_migrate_responses_from']) && !empty($existingFields[$field['_migrate_responses_from']])) {
							//Delete existing responses
							ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', ['form_field_id' => $field['id']]);
							
							//Move responses
							ze\row::update(
								ZENARIO_USER_FORMS_PREFIX . 'user_response_data', 
								['form_field_id' => $field['id']],
								['form_field_id' => $field['_migrate_responses_from']]
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
					[
						'errors' => $errors, 
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
						$result = call_user_func($_POST['method'] ?? false, $mode, $value);
						if ($result) {
							$ord = 0;
							foreach ($result as $id => $label) {
								$lov[$id] = [
									'id' => $id,
									'label' => $label,
									'ord' => ++$ord
								];
							}
						}
					}
					echo json_encode($lov);
				}
				break;
			
			case 'validate_salesforce_field':
				ze\module::inc('zenario_crm_form_integration');
				
				$item = json_decode($_POST['item'], true);
				$sObject = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'salesforce_data', 's_object', $formId);
				$name = $item['field_crm_name'] ?? false;
				$values = array_values($item['crm_lov'] ?? []);
				
				$result = zenario_crm_form_integration::validateSalesforceObjectField($sObject, $name, $values);
				echo json_encode($result);
				break;
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
	
	private function sanitizeTextForSQL($text, $length = 250) {
		return mb_substr(trim($text), 0, $length, 'UTF-8');
	}
	
	private function getVisibilityOptions($item, $fields, $tempFieldIdLink, $tempValueIdLink) {
		$values = [];
		$values['visibility'] = $item['visibility'] ?? 'visible';
		$values['visible_condition_field_id'] = 0;
		$values['visible_condition_invert'] = 0;
		$values['visible_condition_checkboxes_operator'] = 'AND';
		$values['visible_condition_field_value'] = NULL;
		if ($values['visibility'] == 'visible_on_condition') {
			$values['visible_condition_field_id'] = (int)$tempFieldIdLink[$item['visible_condition_field_id']];
			//$values['visible_condition_invert'] = ($item['visible_condition_field_type'] == 'visible_if_not');
			
			if($item['visible_condition_field_type'] == 'visible_if_not'){
			    $values['visible_condition_invert'] = 1;
			    
			}else if($item['visible_condition_field_type'] == 'visible_if'){
			    $values['visible_condition_invert'] = 0;
			} else {
			     $values['visible_condition_invert'] = 2;
			}
			$conditionFieldType = $fields[$item['visible_condition_field_id']]['type'];
			
			if ($item['visible_condition_field_type'] == 'visible_if_one_of' || $conditionFieldType == 'checkboxes') {
				$tValues = array();
				foreach ($item['visible_condition_checkboxes_field_value'] as $tValue) {
					if (empty($fields[$item['visible_condition_field_id']]['dataset_field_id']) && in_array($conditionFieldType, ['select', 'radios', 'checkboxes'])) {
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
	
	private function updateFieldCRMData($formId, $fieldId, $field, $tempValueIdLink) {
		if (!empty($field['send_to_crm']) && !empty($field['field_crm_name'])) {
			$formCRMValues = ['name' => $field['field_crm_name'], 'send_condition' => $field['crm_send_condition']];
			
			if (
				!ze\row::exists(
					ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields', 
					['form_field_id' => $fieldId, 'name' => $field['field_crm_name']]
				)
			) {
				//Get next ordinal
				$maxOrdinalSQL = '
					SELECT MAX(cf.ord)
					FROM ' . DB_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields cf
					INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
						ON uff.user_form_id = ' . (int)$formId . '
						AND cf.form_field_id = uff.id
					WHERE cf.name = "' . ze\escape::sql($field['field_crm_name']) . '"';
				$maxOrdinalResult = ze\sql::select($maxOrdinalSQL);
				$maxOrdinalRow = ze\sql::fetchRow($maxOrdinalResult);
				$formCRMValues['ord'] = $maxOrdinalRow[0] ? $maxOrdinalRow[0] + 1 : 1;
			}
			
			ze\row::set(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields', $formCRMValues, ['form_field_id' => $fieldId]);
			
			
			if (($field['type'] == 'checkbox' || $field['type'] == 'group') && !empty($field['crm_lov'])) {
				foreach ($field['crm_lov'] as $valueId => $crmValue) {
					$state = ($valueId == 'checked') ? 1 : 0;
					ze\row::set(
						ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_field_values',
						[
							'value' => $crmValue,
							'form_field_value_dataset_id' => null,
							'form_field_value_unlinked_id' => null,
							'form_field_value_centralised_key' => null
						],
						[
							'form_field_value_checkbox_state' => $state,
							'form_field_id' => $fieldId
						]
					);
				}
				
			} else {
				
				//Save values
				if (!empty($field['crm_lov'])) {
					foreach ($field['crm_lov'] as $valueId => $crmValue) {
						if ($field['type'] == 'centralised_select' || $field['type'] == 'centralised_radios') {
							ze\row::set(
								ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_field_values',
								[
									'value' => $crmValue,
									'form_field_value_dataset_id' => null,
									'form_field_value_unlinked_id' => null,
									'form_field_value_checkbox_state' => null
								],
								[
									'form_field_value_centralised_key' => $valueId,
									'form_field_id' => $fieldId
								]
							);
						} elseif (!empty($field['dataset_field_id'])) {
							ze\row::set(
								ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_field_values',
								[
									'value' => $crmValue,
									'form_field_value_centralised_key' => null,
									'form_field_value_unlinked_id' => null,
									'form_field_value_checkbox_state' => null
								],
								[
									'form_field_value_dataset_id' => $valueId,
									'form_field_id' => $fieldId
								]
							);
						} else {
							
							//Get actual ID if the value was using a temp ID e.g. t1
							if (isset($tempValueIdLink[$valueId])) {
								$valueId = $tempValueIdLink[$valueId];
								
								ze\row::set(
									ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_field_values',
									[
										'value' => $crmValue,
										'form_field_value_centralised_key' => null,
										'form_field_value_dataset_id' => null,
										'form_field_value_checkbox_state' => null
									],
									[
										'form_field_value_unlinked_id' => $valueId,
										'form_field_id' => $fieldId
									]
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
		if (in_array($field['type'], ['checkboxes', 'radios', 'select', 'text']) && !empty($field['lov'])) {
			foreach ($field['lov'] as $valueId => $value) {
				$field['lov'][$valueId]['id'] = $valueId;
			}
			usort($field['lov'], 'ze\ray::sortByOrd');
			
			foreach ($field['lov'] as $valueIndex => $value) {
				$columns = [
					'ord' => $valueIndex + 1,
					'label' => $this->sanitizeTextForSQL($value['label']),
					'is_invalid' => !empty($field['invalid_responses']) && in_array($value['id'], $field['invalid_responses'])
				];
				ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', $columns, $tempValueIdLink[$value['id']]);
			}
		}
	}
	
	private function updateFieldTranslations($field, $existingField, $languages, $phraseFields) {
		foreach ($phraseFields as $tuixFieldId => $dbColumn) {
			if (empty($field['translations'][$tuixFieldId])) {
				continue;
			}
			$oldPhraseCode = $existingField[$dbColumn] ?? false;
			//Check if old value has more than 1 entry in any translatable field
			$identicalPhraseFound = false;
			if ($oldPhraseCode) {
				$where = [];
				foreach ($phraseFields as $tuixFieldId2 => $dbColumn2) {
					$where[] = '`' . ze\escape::sql($dbColumn2) . '` = "' . ze\escape::sql($oldPhraseCode) . '"';
				}
				$sql = '
					SELECT id
					FROM ' . DB_PREFIX.ZENARIO_USER_FORMS_PREFIX . 'user_form_fields
					WHERE (' . implode(' OR ', $where) . ')';
				$result = ze\sql::select($sql);
				if (ze\sql::numRows($result) > 1) {
					$identicalPhraseFound = true;
				}
			}
			
			$newPhraseCode = $field[$tuixFieldId] ?? false;
			
			//If another field is using the same phrase code...
			if ($identicalPhraseFound) {
				//Leave as is
				
			//If nothing else is using the same phrase code...
			} elseif (!ze\row::exists('visitor_phrases', ['code' => $newPhraseCode, 'module_class_name' => 'zenario_user_forms'])) {
				ze\row::update(
					'visitor_phrases', 
					['code' => $newPhraseCode], 
					['code' => $oldPhraseCode, 'module_class_name' => 'zenario_user_forms']
				);
			
			//If code already exists, and nothing else is using the code, delete current phrases, and update/create new 	
			} else {
				ze\row::delete('visitor_phrases', ['code' => $oldPhraseCode, 'module_class_name' => 'zenario_user_forms']);
			}
			
			if ($newPhraseCode) {
				foreach ($field['translations'][$tuixFieldId] as $languageId => $phrase) {
					$phrase = $phrase === '' ? null : $phrase;
					ze\row::set(
						'visitor_phrases',
						['local_text' => $phrase], 
						['code' => $newPhraseCode, 'module_class_name' => 'zenario_user_forms', 'language_id' => $languageId]
					);
				}
			}
		}
	}
	
	private function getFormPageOptions($page, $fields, $tempFieldIdLink, $tempValueIdLink) {
		$values = [];
		
		$values['name'] = isset($page['name']) ? $this->sanitizeTextForSQL($page['name']) : null;
		$values['next_button_text'] = isset($page['next_button_text']) ? $this->sanitizeTextForSQL($page['next_button_text']) : '';
		$values['previous_button_text'] = isset($page['previous_button_text']) ? $this->sanitizeTextForSQL($page['previous_button_text']) : '';
		$values['hide_in_page_switcher'] = !empty($page['hide_in_page_switcher']);
		$values['show_in_summary'] = !empty($page['show_in_summary']);
		
		return $values;
	}
	
	private function getFormFieldOptions($field, $fields, $tempFieldIdLink, $tempValueIdLink) {
		$values = [];
		$values['user_field_id'] = 0;
		if (!empty($field['dataset_field_id'])) {
			$values['user_field_id'] = (int)$field['dataset_field_id'];
		} else {
			$values['field_type'] = $field['type'];
		}
		
		$values['name'] = isset($field['name']) ? $this->sanitizeTextForSQL($field['name']) : '';
		$values['label'] = isset($field['label']) ? $this->sanitizeTextForSQL($field['label']) : null;
		$values['placeholder'] = isset($field['placeholder']) ? $this->sanitizeTextForSQL($field['placeholder']) : null;
		
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
			if($field['mandatory_condition_field_type'] == 'mandatory_if_not'){
			    $values['mandatory_condition_invert'] = 1;
			} else if($field['mandatory_condition_field_type'] == 'mandatory_if_one_of'){
			    $values['mandatory_condition_invert'] = 2;
			}
			$conditionFieldType = $fields[$field['mandatory_condition_field_id']]['type'];
			if ($field['mandatory_condition_field_type'] == 'mandatory_if_one_of' || $conditionFieldType == 'checkboxes') {
				$tValues = array();
				$tValue="";
				foreach ($field['mandatory_condition_checkboxes_field_value'] as $tValue) {
					if (empty($fields[$field['mandatory_condition_field_id']]['dataset_field_id']) && in_array($conditionFieldType, ['select', 'radios', 'checkboxes'])) {
						$tValue = $tempValueIdLink[$tValue] ?? '';
					}
					$tValues[] = $tValue;
				}
				$values['mandatory_condition_field_value'] = (count($tValues)>1) ? implode(',', $tValues): $tValue;

				if ($conditionFieldType == 'checkboxes') {
					$values['mandatory_condition_checkboxes_operator'] = $field['mandatory_condition_checkboxes_operator'];
				}
			} else {
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
                            $tValues = [];
                            $tValue = "";
                            foreach ($field['mandatory_condition_checkboxes_field_value'] as $tValue) {
                                if (empty($fields[$field['mandatory_condition_field_id']]['dataset_field_id'])) {
                                    $tValue = $tempValueIdLink[$tValue] ?? '';
                                }
                                $tValues[] = $tValue;
                            }
                            $values['mandatory_condition_field_value'] = (count($tValues)>1) ? implode(',', $tValues): $tValue;
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
		}
		
		$values['custom_code_name'] = !empty($field['custom_code_name']) ? $this->sanitizeTextForSQL($field['custom_code_name']) : null;
		$values['preload_dataset_field_user_data'] = !empty($field['preload_dataset_field_user_data']);
		$values['split_first_name_last_name'] = !empty($field['split_first_name_last_name']);
		$values['allow_converting_multiple_images_to_pdf'] = !empty($field['allow_converting_multiple_images_to_pdf']);
		
		$defaultValueMode = !empty($field['default_value_options']) ? $field['default_value_options'] : false;
		$values['default_value'] = null;
		$values['default_value_class_name'] = null;
		$values['default_value_method_name'] = null;
		$values['default_value_param_1'] = null;
		$values['default_value_param_2'] = null;
		if ($defaultValueMode == 'value') {
			if (in_array($field['type'], ['checkbox', 'group']) && isset($field['default_value_lov'])) {
				$values['default_value'] = $field['default_value_lov'] == 'checked' ? 1 : 0;
			} else if (in_array($field['type'], ['radios', 'centralised_radios', 'select', 'centralised_select']) && isset($field['default_value_lov'])) {
				if (isset($tempValueIdLink[$field['default_value_lov']]) && empty($field['dataset_field_id']) && in_array($field['type'], ['radios', 'select'])) {
					$values['default_value'] = $tempValueIdLink[$field['default_value_lov']];
				} else {
					$values['default_value'] = $field['default_value_lov'];
				}
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
		
		$values['values_source'] = '';
	 	$values['values_source_filter'] = '';
	 	if (!empty($field['values_source'])) {
	 		$values['values_source'] = $this->sanitizeTextForSQL($field['values_source']);
	 		if (!empty($field['values_source_filter'])) {
	 			$values['values_source_filter'] = $this->sanitizeTextForSQL($field['values_source_filter']);
	 		}
	 	}
	 	$values['filter_on_field'] = !empty($field['filter_on_field']) ? (int)$tempFieldIdLink[$field['filter_on_field']] : 0;
		
		$values['suggested_values'] = null;
		$values['filter_placeholder'] = null;
		$values['force_suggested_values'] = !empty($field['enable_suggested_values']) && !empty($field['force_suggested_values']);
		if (!empty($field['enable_suggested_values']) && ($field['suggested_values'] ?? false)) {
			$values['suggested_values'] = $field['suggested_values'];
			
			if ($field['suggested_values'] == 'pre_defined') {
				if (isset($field['filter_placeholder'])) {
					$values['filter_placeholder'] = $this->sanitizeTextForSQL($field['filter_placeholder']);
				}
				if (!empty($field['suggested_values_source'])) {
					$values['values_source'] = $this->sanitizeTextForSQL($field['suggested_values_source']);
				}
				$values['filter_on_field'] = !empty($field['suggested_values_filter_on_field']) ? (int)$tempFieldIdLink[$field['suggested_values_filter_on_field']] : 0;
			}
		}
		
		$values['note_to_user'] = isset($field['note_to_user']) ? $this->sanitizeTextForSQL($field['note_to_user']) : null;
		$values['css_classes'] = isset($field['css_classes']) ? $this->sanitizeTextForSQL($field['css_classes']) : null;
		$values['div_wrap_class'] = isset($field['div_wrap_class']) ? $this->sanitizeTextForSQL($field['div_wrap_class']) : null;
		
		$values['validation'] = null;
		$values['validation_error_message'] = null;
		if (!empty($field['field_validation']) && $field['field_validation'] != 'none') {
			$values['validation'] = $field['field_validation'];
			$values['validation_error_message'] = $this->sanitizeTextForSQL($field['field_validation_error_message']);
		}
		
		$values['description'] = isset($field['description']) ? $this->sanitizeTextForSQL($field['description'], 65535) : null;
	 	$values['value_field_columns'] = !empty($field['value_field_columns']) ? (int)$field['value_field_columns'] : 0;
	 	
	 	$values['invert_dataset_result'] = !empty($field['invert_dataset_result']) ? (int)$field['invert_dataset_result'] : 0;
	 	$values['invalid_field_value_error_message'] = !empty($field['invalid_responses']) && !empty($field['invalid_field_value_error_message']) ? $this->sanitizeTextForSQL($field['invalid_field_value_error_message']) : null;
	 	
	 	
	 	if ($field['type'] == 'repeat_start') {
	 		$values['min_rows'] = !empty($field['min_rows']) ? (int)$field['min_rows'] : 0;
	 		$values['max_rows'] = !empty($field['max_rows']) ? (int)$field['max_rows'] : 0;
	 		$values['add_row_label'] = !empty($field['add_row_label']) ? $this->sanitizeTextForSQL($field['add_row_label']) : null;
	 		
	 	} elseif ($field['type'] == 'date') {
	 		$values['show_month_year_selectors'] = !empty($field['show_month_year_selectors']) ? (int)$field['show_month_year_selectors'] : 0;
	 		$values['no_past_dates'] = !empty($field['no_past_dates']) ? (int)$field['no_past_dates'] : 0;
	 		$values['no_future_dates'] = !empty($field['no_future_dates']) ? (int)$field['no_future_dates'] : 0;
	 		$values['disable_manual_input'] = !empty($field['disable_manual_input']) ? (int)$field['disable_manual_input']: 0;
	 		
	 	} elseif ($field['type'] == 'document_upload') {
	 		$values['stop_user_editing_filename'] = !empty($field['stop_user_editing_filename']) ? (int)$field['stop_user_editing_filename'] : 0;
	 		$values['combined_filename'] = !empty($field['combined_filename']) ? preg_replace('/[^\w-]/', '_', $this->sanitizeTextForSQL($field['combined_filename'])) : null;
	 		
	 	} elseif ($field['type'] == 'section_description') {
	 		$values['show_in_summary'] = !empty($field['show_in_summary']) ? (int)$field['show_in_summary'] : 0;
	 		
	 	} elseif ($field['type'] == 'textarea') {
	 		$values['word_count_max'] = (!empty($field['word_count_max']) && (int)$field['word_count_max'] > 0) ? (int)$field['word_count_max'] : null;
	 		$values['word_count_min'] = (!empty($field['word_count_min']) && (int)$field['word_count_min'] > 0) ? (int)$field['word_count_min'] : null;
	 		$values['rows'] = (!empty($field['rows']) && (int)$field['rows'] > 0) ? (int)$field['rows'] : null;

	 		
	 	} elseif ($field['type'] == 'restatement') {
	 		$values['restatement_field'] = !empty($field['restatement_field']) ? (int)$tempFieldIdLink[$field['restatement_field']] : 0;
	 		
	 	} elseif ($field['type'] == 'calculated') {
	 		$values['value_prefix'] = !empty($field['value_prefix']) ? $this->sanitizeTextForSQL($field['value_prefix']) : null;
			$values['value_postfix'] = !empty($field['value_postfix']) ? $this->sanitizeTextForSQL($field['value_postfix']) : null;
	 		$values['calculation_code'] = '';
			if (!empty($field['calculation_code'])) {
				foreach ($field['calculation_code'] as $index => $step) {
					if ($step['type'] == 'field') {
						$field['calculation_code'][$index]['value'] = (int)$tempFieldIdLink[$field['calculation_code'][$index]['value']];
					}
				}
				$values['calculation_code'] = json_encode($field['calculation_code']);
			}
	 	}
	 	
	 	return $values;
	}
}