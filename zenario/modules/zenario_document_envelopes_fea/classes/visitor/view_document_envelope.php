<?php
/*
 * Copyright (c) 2020, Tribal Limited
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


class zenario_document_envelopes_fea__visitor__view_document_envelope extends zenario_document_envelopes_fea {
	
	protected $idVarName = 'envelopeId';
	protected $envelopeId = false;
	protected $customDatasetFieldIds = [];
	
	public function init() {
		if (($this->envelopeId = $_REQUEST['envelopeId'] ?? false)
			&& ($this->envelopeData = ze\row::get(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes', true, ['id' => (int)$this->envelopeId]))
		) {
			$this->runVisitorTUIX();
			return true;
		} else {
			return ZENARIO_403_NO_PERMISSION;
		}
	}
	
	public function returnVisitorTUIXEnabled($path) {
		return true;
	}
	
	public function fillVisitorTUIX($path, &$tags, &$fields, &$values) {
		$this->mergeCustomTUIX($tags);
		zenario_abstract_fea::fillVisitorTUIX($path, $tags, $fields, $values);
		$this->translatePhrasesInTUIX($tags, $path);

		if ($this->setting('show_title')) {
			$tags['title_tags'] = $this->setting('title_tags') ?: 'h2';
		} else {
			unset($tags['title']);
		}
		
		if ($this->envelopeId) {
			$tags['key']['envelopeId'] = (int)$this->envelopeId;
		
			if ($this->envelopeData) {
				ze\lang::applyMergeFields($tags['title'], ['envelope_name' => $this->envelopeData['name'], 'envelopeId' => $this->envelopeId]);
				
				//Populate the details fields
				$values['details/code'] = $this->envelopeData['code'];
				$values['details/name'] = $this->envelopeData['name'];
				$values['details/description'] = $this->envelopeData['description'];
				$values['details/keywords'] = $this->envelopeData['keywords'];
				$values['details/created'] = ze\date::formatDateTime($this->envelopeData['created'], '_MEDIUM');
				$fields['details/thumbnail_id']['snippet']['html'] = $this->envelopeData['thumbnail_id'];
				
				if ($this->envelopeData['thumbnail_id']) {
					$fields['details/thumbnail_id']['snippet']['html'] = $this->getImageAnchor($this->envelopeData['thumbnail_id']);
				} elseif ($this->setting('use_fallback_image')) {
					$fields['details/thumbnail_id']['snippet']['html'] = $this->getImageAnchor($this->setting('fallback_image'));
				}
				
				$envelopeLanguages = ze\dataset::centralisedListValues('zenario_document_envelopes_fea::getEnvelopeLanguages');;
				if ($this->envelopeData['language_id']) {
					$values['details/language_id'] = $envelopeLanguages[$this->envelopeData['language_id']]['label'];
				} else {
					$values['details/language_id'] = $this->phrase("Not set");
				}
		
				$values['details/last_updated'] = ze\user::formatLastUpdated($this->envelopeData);
				
				//Dataset custom fields
				if ($this->setting('custom_field_1')) {
					$this->customDatasetFieldIds['custom_field_1'] = $this->setting('custom_field_1');
				}
		
				if ($this->setting('custom_field_2')) {
					$this->customDatasetFieldIds['custom_field_2'] = $this->setting('custom_field_2');
				}
		
				if ($this->setting('custom_field_3')) {
					$this->customDatasetFieldIds['custom_field_3'] = $this->setting('custom_field_3');
				}
				
				$dataset = ze\dataset::details(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes');
				//Get all custom fields from this dataset. It will be useful for looking up data on the selected custom fields.
				$datasetAllCustomFields = ze\datasetAdm::listCustomFields($dataset['id'], $flat = false);
				if (!empty($datasetAllCustomFields) && count($this->customDatasetFieldIds) > 0) {
					foreach ($this->customDatasetFieldIds as $customDatasetFieldIdKey => $customDatasetFieldId) {
						if (isset($datasetAllCustomFields[$customDatasetFieldId])) {
							$fields['details/' . $customDatasetFieldIdKey]['hidden'] = false;
						
							if (
								$datasetAllCustomFields[$customDatasetFieldId]['type'] == "select"
								|| $datasetAllCustomFields[$customDatasetFieldId]['type'] == "radios"
								|| $datasetAllCustomFields[$customDatasetFieldId]['type'] == "checkboxes"
							) {
								$fieldListOfValues = ze\dataset::fieldLOV($customDatasetFieldId, $flat = false);
								$fieldListOfValuesFormattedNicely = [];
								foreach (explode(',', ze\dataset::fieldValue($dataset, $datasetAllCustomFields[$customDatasetFieldId], $this->envelopeId)) as $selectedOption) {
									if ($selectedOption) {
										$fieldListOfValuesFormattedNicely[] = $fieldListOfValues[$selectedOption]['label'];
									}
								}
								$fieldValue = implode(', ', $fieldListOfValuesFormattedNicely);
							} else {
								$fieldValue = ze\dataset::fieldValue($dataset, $datasetAllCustomFields[$customDatasetFieldId], $this->envelopeId);
							}
							
							if ($fieldValue) {
								if ($datasetAllCustomFields[$customDatasetFieldId]['type'] == 'url') {
									$fieldValue = '<a href="' . htmlspecialchars($fieldValue) . '" target="_blank">' . $fieldValue . '</a>';
								}
								$fields['details/' . $customDatasetFieldIdKey]['snippet']['html'] = $fieldValue;
							} else {
								$fields['details/' . $customDatasetFieldIdKey]['snippet']['html'] = $this->phrase("Not set");
							}
						}
					}
				}
			}
		}
		
		$tags['perms'] = [
			'manage' => ze\user::can('manage', $this->idVarName, $this->envelopeId)
		];

		$fields['details/code']['hidden'] = !$this->setting('show_code');
		$fields['details/keywords']['hidden'] = !$this->setting('show_keywords');
		
		if (!$this->setting('show_edit_button_as_icon')) {
			$tags['collection_buttons']['edit_document_envelope']['css_class'] = 'edit';
		}
		
		if (!$this->setting('show_delete_button_as_icon')) {
			$tags['collection_buttons']['delete_document_envelope']['css_class'] = 'delete';
		}
	}
	
	public function handlePluginAJAX() {
		$command = ze::request('command');
		$envelopeId = ze::request('envelopeId');
		$userId = ze\user::id();
		
		if ($envelopeId && $userId) {
			switch ($command) {
				case 'delete_document_envelope':
					if (ze\user::can('manage', $this->idVarName, $this->envelopeId)) {
						ze\row::delete(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes', ['id' => (int)$envelopeId]);
					}
					break;
				
				default:
					return false;
			}
		}
	}
}