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

class zenario_document_envelopes_fea extends zenario_abstract_fea {
	
	protected $subClass = false;
	protected $data = [];
	
	protected $idVarName = 'envelopeId';
	
	public function init() {
		if ($this->subClass || ($this->subClass = $this->runSubClass(__FILE__))) {
			ze::requireJsLib('zenario/js/tuix.wrapper.js.php');
			return $this->subClass->init();
		}
		return false;
	}

	public function showSlot() {
		if ($this->data) {
			$this->twigFramework($this->data);
		} elseif ($this->subClass) {
			return $this->subClass->showSlot();
		}
	}
	
	public function handlePluginAJAX() {
		if (ze::$isTwig) return;
		
		if ($this->subClass) {
			return $this->subClass->handlePluginAJAX();
		}
	}
	
	public function showFile() {
		if (ze::$isTwig) return;
		
		if ($this->subClass) {
			return $this->subClass->showFile();
		}
	}
	
	public function typeaheadSearchAJAX($path, $tab, $searchField, $searchTerm, &$searchResults) {
		if ($this->subClass) {
			return $this->subClass->typeaheadSearchAJAX($path, $tab, $searchField, $searchTerm, $searchResults);
		}
	}
	
	public static function getEnvelopeLanguages($mode, $value = false) {
		switch ($mode) {
			case ze\dataset::LIST_MODE_INFO:
				return ['can_filter' => false];
			
			case ze\dataset::LIST_MODE_LIST:
				$result = ze\row::getValues(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelope_languages', ['language_id', 'label', 'multiple_languages_flag'], [], 'label', 'language_id');
				$values = [];
				$multiLanguageRowId = $multiLanguageRow = false;
				if (count($result) > 0) {
					$i = 1;
					foreach ($result as $row) {
						//Multi-language option needs to be the last on the list.
						//This logic will work both in TUIX forms and Twig frameworks.
						if (!empty($row['multiple_languages_flag'])) {
							$multiLanguageRowId = $row['language_id'];
							$multiLanguageRow = ['label' => $row['label'], 'ord' => 9999];
							continue;
						}
						
						$ord = $i;
						$values[$row['language_id']] = ['label' => $row['label'], 'ord' => $ord];
					}
				}
				
				if ($multiLanguageRowId && $multiLanguageRow) {
					$values[$multiLanguageRowId] = $multiLanguageRow;
				}
				
				return $values;
			
			case ze\dataset::LIST_MODE_VALUE:
				return ze\row::get(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX. 'document_envelope_languages', 'label', ['language_id' => $value]);
		}
	}
	
	public static function deleteDocumentsInEnvelope($envelopeId = false, $documentIds = '') {
		if ($envelopeId) {
			if ($documentIds) {
				//If document IDs were passed, only delete these...
				$documentIds = explode(',', $documentIds);
			} else {
				//... otherwise delete all documents in the envelope.
				$result = ze\row::query(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'documents_in_envelope', 'id', ['envelope_id' => $envelopeId]);
				$documentIds = ze\sql::fetchValues($result);
			}
			
			if (count($documentIds) > 0) {
				$docstoreDir = ze::setting('docstore_dir');
				foreach ($documentIds as $documentId) {
					//Get the file ID
					$fileId = ze\row::get(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'documents_in_envelope', 'file_id', ['id' => $documentId]);
					$okToDelete = true;
			
					//Check if the file is used in any other envelope. Do not delete if even one other envelope uses the same file.
					$resultEnvelopes = ze\row::query(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'documents_in_envelope', 'file_id', ['file_id' => $fileId, 'id' => ['!' => $documentId]]);
					if ($row = ze\sql::fetchRow($resultEnvelopes)) {
						$okToDelete = false;
					}
				
					//If no other envelope uses this file, there is no need to check in the Zenario Documents table.
					//Zenario Documents system may use the same file, but the ID would always be different, because the usage column values are different
					//("hierarchial_file" vs "document_in_envelope").
				
					ze\row::delete(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'documents_in_envelope', ['id' => $documentId, 'file_id' => $fileId, 'envelope_id' => $envelopeId]);
				
					if ($okToDelete) {
						$fileDetails = ze\row::get('files', ['path', 'filename', 'location'], ['id' => $fileId, 'usage' => 'document_in_envelope']);
						if ($fileDetails['location'] == 'docstore') {
							$f = $docstoreDir . '/'. $fileDetails['path'] . '/' . $fileDetails['filename'];
							if(is_file($f)){
								unlink($f);
							}

							$dir = $docstoreDir . '/'. $fileDetails['path'];
						
							$emptyFolder = ze\document::isDirEmpty($dir);
							if(is_dir($dir) && $emptyFolder){
								rmdir($dir);
							}
						}
						ze\row::delete('files', ['id' => $fileId, 'usage' => 'document_in_envelope']);
					}
				}
				
				return true;
			}
		} else {
			return false;
		}
	}
	
	public static function updateEnvelopeFileFormats($envelopeId = false) {
		if ($envelopeId) {
			$sql = '
				UPDATE ' . DB_PREFIX . ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes
				SET file_formats = (
					IFNULL(
						(
							SELECT GROUP_CONCAT(DISTINCT die.file_format SEPARATOR ", ")
							FROM ' . DB_PREFIX . ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'documents_in_envelope die
							WHERE die.envelope_id = ' . (int)$envelopeId . '
							ORDER BY die.file_format ASC
						), ""
					)
				)
				WHERE id = '. (int)$envelopeId;
			ze\sql::update($sql);
		} else {
			return false;
		}
	}
	
	protected function getImageAnchor($imageId = false, $altTag = false) {
		$anchor = $width = $height = $url = $srcSet = false;
		
		if ($imageId) {
			$canvas = $this->setting('canvas');
			$retina = ($canvas != 'unlimited') || $this->setting('retina');
			$altTag = ze\row::get('files', 'alt_tag', ['id' => $imageId]);
			ze\file::imageLink($width, $height, $url, $imageId, $this->setting('width'), $this->setting('height'), $canvas, $offset = 0, $retina);
			$imageWidthAndHeight = 'width="' . (int)$width . '" height"' . (int)$height . '"';
		
			if ($retina) {
				$sWidth = $sHeight = $sURL = false;
				if (ze\file::imageLink($sWidth, $sHeight, $sURL, $imageId, $width, $height, $canvas == 'resize_and_crop'? 'resize_and_crop' : 'stretch', 0, false)) {
					if ($url != $sURL) {
						$srcSet = $url. ' 2x';
						$url = $sURL;
					}
					$imageWidthAndHeight = 'width="' . (int)$sWidth . '" height="' . (int)$sHeight . '"';
				}
			}
			
			$anchor = '<img src="' . htmlspecialchars($url) . '"';
			if ($altTag) {
				$anchor .= ' alt="' . htmlspecialchars($altTag) . '"';
			}
			
			$anchor .= ' ' . $imageWidthAndHeight;
			
			if ($srcSet) {
				$anchor .= ' srcset="' . htmlspecialchars($srcSet) . '"';
			}
			$anchor .= ' >';
		}
		
		return $anchor;
	}

	public static function requestVarDisplayName($name) {
		switch ($name) {
			case 'name':
				return 'Envelope name';
			case 'code':
				return 'Envelope code';
		}
	}

	public static function requestVarMergeField($field) {
		if ($field == 'name' || $field == 'code') {
			$envelopeDetails = ze\row::get(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX. 'document_envelopes', ['name', 'code'], ['id' => (int) ze::get('envelopeId')]);
			
			if ($field == 'name') {
				return $envelopeDetails['name'];
			} elseif ($field == 'code') {
				return $envelopeDetails['code'];
			}
		}
	}
}