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


class zenario_document_envelopes_fea__visitor__edit_document_envelope extends zenario_document_envelopes_fea {
	
	protected $idVarName = 'envelopeId';
	protected $envelopeId = false;
	protected $customDatasetFieldIds = [];
	protected $dataset = [];
	protected $datasetAllCustomFields = [];
	
	public function init() {
		if (($this->envelopeId = $_REQUEST['envelopeId'] ?? false)
			&& ($this->envelopeData = ze\row::get(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes', true, ['id' => (int)$this->envelopeId]))
			&& !empty($userId = ze\user::id())
		) {
			$mode = $this->getMode();
			if (($mode == 'create_document_envelope' && ze\user::can('manage', $this->idVarName)) || ($mode == 'edit_document_envelope' && ze\user::can('manage', $this->idVarName, $this->envelopeId))) {
				//Dataset custom fields
				if ($this->setting('custom_field_1') || $this->setting('custom_field_2') || $this->setting('custom_field_3')) {
					$this->dataset = ze\dataset::details(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes');
					$this->datasetAllCustomFields = ze\datasetAdm::listCustomFields($this->dataset['id'], $flat = false);
				
					//Make sure the field exists in the dataset (e.g. hasn't been deleted) before using it.
					for ($i = 1; $i <= 3; $i++) {
						if ($this->setting('custom_field_' . $i) && isset($this->datasetAllCustomFields[$this->setting('custom_field_' . $i)])) {
							$this->customDatasetFieldIds['custom_field_' . $i] = $this->setting('custom_field_' . $i);
						}
					}
				}
				
				$this->runVisitorTUIX();
				return true;
			} else {
				return ZENARIO_403_NO_PERMISSION;
			}
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
		
		$mode = $this->getMode();
		if ($mode == 'create_document_envelope') {
			$tags['title'] = "Creating a document envelope";
			$fields['details/thumbnail_id']['hidden'] = true;
		} elseif ($mode == 'edit_document_envelope' && $this->envelopeId) {
			$this->loadDetails($path, $tags, $fields, $values);
			ze\lang::applyMergeFields($tags['title'], ['envelopeId' => $this->envelopeId]);
		}

		$fields['details/code']['hidden'] = !$this->setting('show_code');
		
		//Load envelope languages
		$fields['details/language_id']['values'] = ze\dataset::centralisedListValues('zenario_document_envelopes_fea::getEnvelopeLanguages');
		
		//Custom dataset fields
		if ($this->dataset && $this->datasetAllCustomFields && count($this->customDatasetFieldIds) > 0) {
			foreach ($this->customDatasetFieldIds as $customDatasetFieldIdKey => $customDatasetFieldId) {
				if (isset($this->datasetAllCustomFields[$customDatasetFieldId])) {
					$fields['details/' . $customDatasetFieldIdKey] = [
						'type' => $this->datasetAllCustomFields[$customDatasetFieldId]['type'],
						'label' => $this->datasetAllCustomFields[$customDatasetFieldId]['label'],
						'db_column' => $this->datasetAllCustomFields[$customDatasetFieldId]['db_column'],
						'hidden' => false
					];
				
					if ($this->datasetAllCustomFields[$customDatasetFieldId]['type'] == "text" || $this->datasetAllCustomFields[$customDatasetFieldId]['type'] == "textarea") {
						$fields['details/' . $customDatasetFieldIdKey]['maxlength'] = 255;
					}
				
					if (
						$this->datasetAllCustomFields[$customDatasetFieldId]['type'] == "select"
						|| $this->datasetAllCustomFields[$customDatasetFieldId]['type'] == "radios"
						|| $this->datasetAllCustomFields[$customDatasetFieldId]['type'] == "checkboxes"
					) {
						$fields['details/' . $customDatasetFieldIdKey]['values'] = ze\dataset::fieldLOV($customDatasetFieldId, $flat = false);
						$values['details/' . $customDatasetFieldIdKey] = $fields['details/' . $customDatasetFieldIdKey]['value'] = ze\dataset::fieldValue($this->dataset, $this->datasetAllCustomFields[$customDatasetFieldId], $this->envelopeId);
					}
				
					if ($mode == 'edit_document_envelope' && $this->envelopeId) {
						$values['details/' . $customDatasetFieldIdKey] = $fields['details/' . $customDatasetFieldIdKey]['value'] = ze\dataset::fieldValue($this->dataset, $this->datasetAllCustomFields[$customDatasetFieldId], $this->envelopeId);
					}
				}
			}
		}
	}
	
	public function loadDetails($path, &$tags, &$fields, &$values) {
		$values['details/code'] = $this->envelopeData['code'];
		$values['details/name'] = $this->envelopeData['name'];
		$values['details/description'] = $this->envelopeData['description'];
		$values['details/keywords'] = $this->envelopeData['keywords'];
		$values['details/thumbnail_id'] = $this->envelopeData['thumbnail_id'];
		$values['details/language_id'] = $this->envelopeData['language_id'];
		
		$values['details/last_updated'] = ze\user::formatLastUpdated($this->envelopeData);
	}
	
	public function formatVisitorTUIX($path, &$tags, &$fields, &$values, &$changes) {
		
	}

	public function validateVisitorTUIX($path, &$tags, &$fields, &$values, &$changes, $saving) {
		if ($this->setting('show_code') && $values['details/code']) {
			if (preg_match('/\s/', $values['details/code'])) {
				$fields['details/code']['error'] = $this->phrase('Envelope code must not contain spaces.');
			} else {
				//Check that the envelope code is unique.
				$sql = '
					SELECT COUNT(*)
					FROM ' . DB_PREFIX . ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes
					WHERE code = "' . ze\escape::sql($values['details/code']) . '"';
				
				if ($this->envelopeId) {
					$sql .= '
						AND id != ' . (int)$this->envelopeId;
				}

				$result = ze\sql::select($sql);
				$count = ze\sql::fetchValue($result);
				
				if ($count > 0) {
					$fields['details/code']['error'] = $this->phrase('Envelope code must be unique.');
				}
			}
		}
	}
	
	public function saveVisitorTUIX($path, &$tags, &$fields, &$values, &$changes) {
		$envelopeId = ($tags['key']['envelopeId'] ?? false);
		
		if (is_numeric($values['details/thumbnail_id'])) {
			$thumbnailFileId = $values['details/thumbnail_id'];
		} else {
			$thumbnailFilePath = ze\file::getPathOfUploadInCacheDir($values['details/thumbnail_id']);
			$thumbnailFileFilename = basename(ze\file::getPathOfUploadInCacheDir($values['details/thumbnail_id']));
			$thumbnailFileId = ze\file::addToDatabase('image', $thumbnailFilePath, $thumbnailFileFilename);
		}
		
		if ($this->setting('show_code')) {
			$code = $values['details/code'];
		} else {
			$code = NULL;
		}
		
		$cols = [
			'code' => $code,
			'name' => $values['details/name'],
			'description' => $values['details/description'],
			'keywords' => $values['details/keywords'],
			'thumbnail_id' => $thumbnailFileId,
			'language_id' => $values['details/language_id']
		];
		
		ze\user::setLastUpdated($cols, !$envelopeId);
		
		$envelopeId = ze\row::setAndMarkNew(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes', $cols, ($this->envelopeId ?? false));
		
		self::updateEnvelopeFileFormats($envelopeId);
		
		if ($this->dataset && count($this->customDatasetFieldIds) > 0) {
			$customCols = [];
			foreach ($this->customDatasetFieldIds as $customDatasetFieldIdKey => $customDatasetFieldId) {
				if (isset($this->datasetAllCustomFields[$customDatasetFieldId])) {
					if ($fields['details/' . $customDatasetFieldIdKey]) {
						if ($fields['details/' . $customDatasetFieldIdKey]['type'] == "checkboxes") {
							ze\dataset::updateCheckboxField($this->dataset['id'], $customDatasetFieldId, $envelopeId, $values['details/' . $customDatasetFieldIdKey]);
						} else {
							$customCols[$fields['details/' . $customDatasetFieldIdKey]['db_column']] = $values['details/' . $customDatasetFieldIdKey];
						}
					}
				}
			}
			
			if (count($customCols) > 0) {
				ze\row::set($this->dataset['table'], $customCols, $envelopeId);
			}
		}
		
		$tags['key']['envelopeId'] = $envelopeId;
		
		$tags['go'] = [
			'mode' => 'list_document_envelopes',
			'command' => ['submit', 'back'],
			$this->idVarName => $tags['key']['envelopeId']
		];
	}
	
	public function handlePluginAJAX() {
		if ($_REQUEST['fileUpload'] ?? false) {
			ze\fileAdm::exitIfUploadError($adminFacing = false);
			ze\fileAdm::putUploadFileIntoCacheDir($_FILES['Filedata']['name'], $_FILES['Filedata']['tmp_name']);
		}
	}
	
}
