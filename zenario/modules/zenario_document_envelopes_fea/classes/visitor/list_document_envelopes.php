<?php
/*
 * Copyright (c) 2021, Tribal Limited
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


class zenario_document_envelopes_fea__visitor__list_document_envelopes extends zenario_document_envelopes_fea {
	
	protected $idVarName = 'envelopeId';
	protected $envelopeId = false;
	protected $customDatasetFieldIds = [];
	protected $dataset = [];
	protected $datasetAllCustomFields = [];
	protected $customDataFieldValuesAndLabels = [];
	
	public function init() {
		//Handle custom dataset fields.
		if ($this->setting('custom_field_1') || $this->setting('custom_field_2') || $this->setting('custom_field_3')) {
			$this->dataset = ze\dataset::details(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes');
			$this->datasetAllCustomFields = ze\datasetAdm::listCustomFields($this->dataset['id'], $flat = false);
			
			//Make sure the field exists in the dataset (e.g. hasn't been deleted) before using it.
			for ($i = 1; $i <= 3; $i++) {
				if ($this->setting('custom_field_' . $i) && $this->setting('make_custom_field_' . $i . '_searchable') && isset($this->datasetAllCustomFields[$this->setting('custom_field_' . $i)])) {
					$this->customDatasetFieldIds['custom_field_' . $i] = $this->setting('custom_field_' . $i);
				}
			}
		}
		
		$this->runVisitorTUIX();
		return true;
	}
	
	public function returnVisitorTUIXEnabled($path) {
		return true;
	}
	
	protected function populateItemsIdCol($path, &$tags, &$fields, &$values) {
		return 'id';
	}
	
	protected function populateItemsIdColDB($path, &$tags, &$fields, &$values) {
		return 'de.id';
	}
	
	protected function populateItemsSelectCount($path, &$tags, &$fields, &$values) {
		return '
			SELECT COUNT(DISTINCT de.id)';
	}
	
	protected function populateItemsSelect($path, &$tags, &$fields, &$values) {
		$sql = '
			SELECT
				de.thumbnail_id,
				de.id,
				de.code,
				de.name,
				de.description,
				de.keywords,
				de.created,
				de.file_formats,
				del.label AS language_id';
		
		if (count($this->customDatasetFieldIds) > 0) {
			foreach ($this->customDatasetFieldIds as $customDatasetFieldIdKey => $customDatasetFieldId) {
				$datasetField = $this->datasetAllCustomFields[$customDatasetFieldId];
				if ($datasetField && $datasetField['db_column']) {
					if ($datasetField['is_system_field']) {
						$sql .= ',
							de.' . ze\escape::sql($datasetField['db_column']) . ' AS ' . ze\escape::sql($customDatasetFieldIdKey) . ' ';
					} elseif ($datasetField['type'] == "select" || $datasetField['type'] == "radios") {
						$sql .= ',
							decd.`' . ze\escape::sql($datasetField['db_column']) . '` AS `' . ze\escape::sql($customDatasetFieldIdKey) . '` ';
					} elseif ($datasetField['type'] == "checkboxes") {
						$sql .= ',
							(
								SELECT GROUP_CONCAT(cdvl.value_id SEPARATOR ",")
								FROM ' . DB_PREFIX . 'custom_dataset_values_link cdvl
								WHERE cdvl.linking_id = de.id
							) AS `' . ze\escape::sql($customDatasetFieldIdKey) . '` ';
					} else {
						$sql .= ',
							decd.' . ze\escape::sql($datasetField['db_column']) . ' ';
					}
				}
			}
		}
		
		return $sql;
	}
	
	protected function populateItemsFrom($path, &$tags, &$fields, &$values) {
		$sql = '
			FROM ' . DB_PREFIX . ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes de
			LEFT JOIN ' . DB_PREFIX . ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes_custom_data decd
				ON decd.envelope_id = de.id
			LEFT JOIN ' . DB_PREFIX . ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelope_languages del
				ON del.language_id = de.language_id
			LEFT JOIN ' . DB_PREFIX . ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'documents_in_envelope die
				ON die.envelope_id = de.id
			LEFT JOIN ' . DB_PREFIX . 'custom_dataset_values_link decdvl
				ON decdvl.linking_id = de.id';
		
		return $sql;
	}
	
	protected function populateItemsWhere($path, &$tags, &$fields, &$values) {
		$sql = '
			WHERE TRUE';
		
		//Search box
		$search = ze::request('search');
		$fileFormat = ze::request('file_format');
		if ($this->checkThingEnabled('search_box') && (((count($this->customDatasetFieldIds) > 0)) || $search || $tags['key']['language_id'])) {
			$searchSubquery = $languageSubquery = $fileFormatSubquery = $customFiltersSubquery = '';
			
			$or = $and = '';
			if ($search) {
				$searchSubquery .= '(';
				
				//Split the search string along word boundaries
				$searchTerms = preg_split("/[\s,]+/", $search);
				foreach ($searchTerms as $searchTerm) {
					foreach (['id', 'code', 'name', 'description', 'keywords'] as $columnName) {
						$searchSubquery .= '
							' . $or . 'de.' . ze\escape::sql($columnName) . ' LIKE "%' . ze\escape::sql($searchTerm) . '%"';
				
						$or = 'OR ';
					}
				}
				
				$searchSubquery .= ')';
				$and = 'AND ';
			}
			
			//Show envelopes in the specified language, but also multilingual ones.
			if ($tags['key']['language_id']) {
				$languagesIn = '"' . ze\escape::sql($tags['key']['language_id']) . '"';
				
				$languageFlaggedAsMultipleLanguages = ze\row::get(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelope_languages', 'language_id', ['multiple_languages_flag' => true]);
				if ($languageFlaggedAsMultipleLanguages && $tags['key']['language_id'] != $languageFlaggedAsMultipleLanguages) {
					$languagesIn .= ', "' . ze\escape::sql($languageFlaggedAsMultipleLanguages) . '"';
				}
				$languageSubquery .= '
					' . $and . 'de.language_id IN (' . $languagesIn . ')';
				
				$and = 'AND ';
				$or = 'OR ';
			}
			
			if ($fileFormat) {
				$fileFormatSubquery .= '
					' . $and . 'die.file_format = "' . ze\escape::sql($fileFormat) . '"';
		
				$and = 'AND ';
				$or = 'OR ';
			}
			
			if (count($this->customDatasetFieldIds) > 0
			 && (
				 ($tags['key']['custom_field_1'] && $this->setting('make_custom_field_1_searchable'))
				 || ($tags['key']['custom_field_2'] && $this->setting('make_custom_field_2_searchable'))
				 || ($tags['key']['custom_field_3'] && $this->setting('make_custom_field_3_searchable'))
			 )
			) {
				$customFiltersSubquery .= "
					" . $and;
				$and = '';
				$or = '';
				foreach ($this->customDatasetFieldIds as $customDatasetFieldIdKey => $customDatasetFieldId) {
					if (!empty($tags['key'][$customDatasetFieldIdKey]) && $this->setting('make_' . $customDatasetFieldIdKey . '_searchable')) {
						$customFiltersSubquery .= $or;
						$datasetField = $this->datasetAllCustomFields[$customDatasetFieldId];
						if ($datasetField && $datasetField['db_column']) {
							if ($datasetField['is_system_field']) {
								$customFiltersSubquery .= 'de.' . ze\escape::sql($datasetField['db_column']) . ' LIKE "%' . ze\escape::sql($tags['key'][$customDatasetFieldIdKey]) . '%"';
							} elseif ($datasetField['type'] == "checkboxes") {
								$customFiltersSubquery .= 'decdvl.value_id IN (' . ze\escape::sql($tags['key'][$customDatasetFieldIdKey]) . ')';
							} else {
								$customFiltersSubquery .= 'decd.' . ze\escape::sql($datasetField['db_column']) . ' = "' . ze\escape::sql($tags['key'][$customDatasetFieldIdKey]) . '"';
							}
						}
						$customFiltersSubquery .= ' ';
						$and = 'AND ';
						$or = 'OR ';
					}
				}
			}
			
			if ($searchSubquery || $languageSubquery || $fileFormatSubquery || $customFiltersSubquery) {
				$sql .= '
					AND (' . $searchSubquery . $languageSubquery . $fileFormatSubquery . $customFiltersSubquery . ')';
			}
		}
		
		return $sql;
	}
	
	protected function populateItemsGroupBy($path, &$tags, &$fields, &$values) {
		return '
			GROUP BY de.id';
	}
	
	protected function populateItemsOrderBy($path, &$tags, &$fields, &$values) {
		$sql = '
			ORDER BY ';
		
		$desc = $this->sortDesc($tags);
		switch ($this->sortCol($tags)) {
			case 'created':
				$sql .= 'de.created';
				break;
			case 'name':
				$sql .= 'de.name';
				break;
			default:
				$sql .= 'de.name';
		}
		
		if ($desc) {
			$sql .= ' DESC';
		}
		
		return $sql;
	}
	
	protected function populateItemsPageSize($path, &$tags, &$fields, &$values) {
		return (int)$this->setting('page_size');
	}
	
	protected function formatItemRow(&$item, $path, &$tags, &$fields, &$values) {
		$imageId = $anchor = false;
		
		$item['created'] = ze\date::formatDateTime($item['created'], '_MEDIUM');
		
		if ($item['thumbnail_id']) {
			$item['thumbnail_id'] = $this->getImageAnchor($item['thumbnail_id']);
		} elseif ($this->setting('use_fallback_image')) {
			$item['thumbnail_id'] = $this->getImageAnchor($this->setting('fallback_image'));
		} else {
			unset($item['thumbnail_id']);
		}
		
		if (!$item['file_formats']) {
			$item['file_formats'] = $this->phrase("Not set");
		}
		
		if (!$item['language_id']) {
			$item['language_id'] = $this->phrase("Not set");
		}
		
		if (!$item['keywords']) {
			unset($item['keywords']);
		}
		
		if (count($this->customDatasetFieldIds) > 0) {
			foreach ($this->customDatasetFieldIds as $columnName => $fieldId) {
				$fieldDetails = $this->datasetAllCustomFields[$fieldId];
				$type = $fieldDetails['type'];
				if ($type == 'radios' || $type == 'centralised_radios' || $type == 'select' || $type == 'centralised_select' || $type == 'checkboxes') {
					if ($type == 'centralised_radios') {
						$type = 'radios';
					} elseif ($type == 'centralised_select') {
						$type = 'select';
					}

					if ($item[$columnName]) {
						if ($type == 'checkboxes') {
							$valuesFormattedNicely = [];
							foreach (explode(',', $item[$columnName]) as $value) {
								$valuesFormattedNicely[] = $this->customDataFieldValuesAndLabels[$value];
							}
					
							$item[$columnName] = implode(', ', $valuesFormattedNicely);
						} else {
							$item[$columnName] = $this->customDataFieldValuesAndLabels[$item[$columnName]];
						}
					} else {
						$item[$columnName] = $this->phrase("Not set");
					}
				}
			}
		}
	}
	
	public function fillVisitorTUIX($path, &$tags, &$fields, &$values) {
		if ($this->setting('show_title')) {
			$tags['title_tags'] = $this->setting('title_tags') ?: 'h2';
		} else {
			unset($tags['title']);
		}
		
		$tags['columns']['created']['hidden'] = !$this->setting('show_created_date');

		//On the first load, if sort options are not yet set, default to sorting by date submitted descending
		if (!$tags['key']['sortCol']) {
			if ($tags['columns']['created']['hidden']) {
				$tags['key']['sortCol'] = 'name';
				unset($tags['columns']['created']['sort_desc']);
			} else {
				$tags['key']['sortCol'] = 'created';
				$tags['key']['sortDesc'] = true;
			}
		}
		
		//For select lists/checkboxes/radiogroups, load the labels of the possible options
		foreach (ze\row::getAssocs('custom_dataset_field_values', ['id', 'label'], ['field_id' => array_values($this->customDatasetFieldIds)]) as $option) {
			$this->customDataFieldValuesAndLabels[$option['id']] = $option['label'];
		}
		
		zenario_abstract_fea::fillVisitorTUIX($path, $tags, $fields, $values);
		$this->translatePhrasesInTUIX($tags, $path);
		$this->checkNewThing(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes');
		$this->populateItems($path, $tags, $fields, $values);
		
		//Apply hide search bar setting
		$this->applySearchBarSetting($tags);
		
		//Permissions
		$tags['perms'] = [
			'manage' => ze\user::can('manage', $this->idVarName, $tags['items'], $multiple = true),
			'create' => ze\user::can('manage', $this->idVarName)
		];
		
		//Column visibility
		$tags['columns']['thumbnail_id']['hidden'] = !$this->setting('show_thumbnail_image');
		$tags['columns']['id']['hidden'] = !$this->setting('show_id');
		$tags['columns']['code']['hidden'] = !$this->setting('show_code');
		$tags['columns']['name']['hidden'] = !$this->setting('show_name');
		$tags['columns']['description']['hidden'] = !$this->setting('show_description');
		$tags['columns']['keywords']['hidden'] = !$this->setting('show_keywords');
		$tags['columns']['file_formats']['hidden'] = !$this->setting('show_file_formats');
		
		//Show "view envelope" as a link on the envelope code
		if (isset($tags['item_buttons']['view_document_envelope'])
			&& $this->setting('enable.view_document_envelope')
			&& $this->setting('view_document_envelope.show_as_link')
		) {
			$tags['item_buttons']['view_document_envelope']['show_as_link_on_column'] = 'code';
			unset($tags['item_buttons']['view_document_envelope']['css_class']);
		}
		
		//Populate the file format values. Scan any existing documents in any envelopes to get the file types
		if ($this->setting('show_file_formats_search')) {
			$result = ze\row::distinctQuery(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'documents_in_envelope', 'file_format', []);
			$fileFormatsFromDB = ze\sql::fetchValues($result);
			if (count($fileFormatsFromDB) > 0) {
				foreach ($fileFormatsFromDB as $format) {
					$formatEscaped = htmlspecialchars($format);
					$tags['custom_search_fields']['file_format']['values'][$formatEscaped] = ['label' => $formatEscaped];
				}
			}
		} else {
			$tags['custom_search_fields']['file_format']['hidden'] = true;
		}
		
		//Populate the language values. Hide the language marked as multi
		if ($this->setting('show_languages_search')) {
			$tags['custom_search_fields']['language_id']['values'] = ze\dataset::centralisedListValues('zenario_document_envelopes_fea::getEnvelopeLanguages');
			$languageFlaggedAsMultipleLanguages = ze\row::get(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelope_languages', 'language_id', ['multiple_languages_flag' => true]);
			if ($languageFlaggedAsMultipleLanguages) {
				unset($tags['custom_search_fields']['language_id']['values'][$languageFlaggedAsMultipleLanguages]);
			}
		} else {
			$tags['custom_search_fields']['language_id']['hidden'] = true;
		}
		
		//If custom dataset fields have been selected in the plugin settings,
		//draw the columns and the search filters.
		if (count($this->customDatasetFieldIds) > 0) {
			foreach ($this->customDatasetFieldIds as $columnName => $fieldId) {
				$type = $label = $emptyValue = $placeholder = '';
				$tags['custom_search_fields'][$columnName]['hidden'] = false;
				
				$fieldDetails = $this->datasetAllCustomFields[$fieldId];
				
				//Columns on the list
				$tags['columns'][$columnName]['title'] = $fieldDetails['label'];
				unset($tags['columns'][$columnName]['hidden']);
				
				//Search filters
				$type = $fieldDetails['type'];
				if ($type == 'radios' || $type == 'centralised_radios' || $type == 'select' || $type == 'centralised_select' || $type == 'checkboxes') {
					if ($type == 'centralised_radios') {
						$type = 'radios';
					} elseif ($type == 'centralised_select') {
						$type = 'select';
					}
					
					//The field label is used in the empty value, e.g. "By Language".
					//Catch the case where the label has a colon at the end and remove it.
					$emptyValue = $fieldDetails['label'];
					if (substr($emptyValue, -1) == ":") {
						$emptyValue = substr($emptyValue, 0, -1);
					}
				
					$emptyValue = '-- By ' . $emptyValue . ' --';
					
					$tags['custom_search_fields'][$columnName]['values'] = ze\dataset::fieldLOV($fieldDetails, $flat = false);
				} elseif ($type == 'checkbox') {
					$label = $fieldDetails['label'];
				} elseif ($type == 'text' || $type == 'url') {
					if ($type == 'url') {
						$type = 'text';
					}
					
					$placeholder = $fieldDetails['label'];
					if (substr($placeholder, -1) == ":") {
						$placeholder = substr($placeholder, 0, -1);
					}
				}
				
				$tags['custom_search_fields'][$columnName]['type'] = $type;
				
				$tags['custom_search_fields'][$columnName]['onchange'] = 'lib.parentLib.doSearch(event, undefined, {\'' . ze\escape::js($columnName) . '\': lib.readField(\'' . ze\escape::js($columnName) . '\')});';
				
				if (!empty($emptyValue)) {
					$tags['custom_search_fields'][$columnName]['empty_value'] = $emptyValue;
				}
				
				if (!empty($placeholder)) {
					$tags['custom_search_fields'][$columnName]['placeholder'] = $placeholder;
				}
				
				if (!empty($label)) {
					$tags['custom_search_fields'][$columnName]['label'] = $label;
				}
			}
		}
		
		if (!$this->setting('enable_mass_actions')) {
			foreach ($tags['item_buttons'] as $key => $itemButton) {
				unset($tags['item_buttons'][$key]['multiple_select']);
				unset($tags['item_buttons'][$key]['multiple_select_max_items']);
			}
		}
		
		//Merge custom tuix AFTER the custom fields have been created to allow hiding unwanted values
		$this->mergeCustomTUIX($tags);
	}
	
	public function formatVisitorTUIX($path, &$tags, &$fields, &$values, &$changes) {
		$tags['custom_search_fields']['custom_field_1']['current_value'] = $tags['key']['custom_field_1'];
		$tags['custom_search_fields']['custom_field_2']['current_value'] = $tags['key']['custom_field_2'];
		$tags['custom_search_fields']['custom_field_3']['current_value'] = $tags['key']['custom_field_3'];
	}
	
	public function handlePluginAJAX() {
		$command = ze::request('command');
		$envelopeIds = ze::request('envelopeId');
		$userId = ze\user::id();
		
		if ($envelopeIds && $userId) {
			switch ($command) {
				case 'delete_document_envelope':
					if (ze\user::can('manage', $this->idVarName)) {
						foreach (explode(',', $envelopeIds) as $envelopeId) {
							self::deleteDocumentsInEnvelope($envelopeId);
							$sql = '
								DELETE FROM ' . DB_PREFIX . ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes
								WHERE id IN (' . ze\escape::in($envelopeId) . ')';
							ze\sql::update($sql);
						}
					}
				
				default:
					return false;
			}
		}
	}
}