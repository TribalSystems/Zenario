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


class zenario_document_envelopes_fea__visitor__upload_file_to_document_envelope extends zenario_document_envelopes_fea {
	
	protected $idVarName = 'envelopeId';
	protected $envelopeId = false;
	
	public function init() {
		$this->envelopeId = ze::request('envelopeId');
		if ($this->envelopeId && !empty(ze\user::id()) && ze\user::can('manage', $this->idVarName)) {
			$this->envelope = ze\row::get(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes', true, ['id' => $this->envelopeId]);
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
		zenario_abstract_fea::fillVisitorTUIX($path, $tags, $fields, $values);
		$this->translatePhrasesInTUIX($tags, $path);
		
		if ($this->setting('show_title')) {
			$tags['title_tags'] = $this->setting('title_tags') ?: 'h2';
		} else {
			unset($tags['title']);
		}
		
		ze\lang::applyMergeFields($tags['title'], ['envelope_name' => $this->envelope['name']]);
		
		if ($this->setting('filenames_must_begin_with_envelope_code')) {
			$fields['details/file_id']['label'] =
				$this->phrase(
					"One or more documents (filename must begin with envelope code [[envelope_code]]):",
					['envelope_code' => $this->envelope['code']]
				);
		} else {
			$fields['details/file_id']['label'] = $this->phrase("One or more documents:");
		}
	}
	
	public function formatVisitorTUIX($path, &$tags, &$fields, &$values, &$changes) {
		
	}
	
	public function validateVisitorTUIX($path, &$tags, &$fields, &$values, &$changes, $saving) {
		if ($this->setting('filenames_must_begin_with_envelope_code')) {
			$invalidFileNames = [];
			if (!empty($values['details/file_id'])) {
				foreach (explode(',', $values['details/file_id']) as $fileId) {
					if (is_numeric($fileId)) {
						$documentFileId = $fileId;
						$documentFilename = ze\row::get('files', 'filename', ['id' => $fileId]);
					} else {
						$documentFilePath = ze\file::getPathOfUploadInCacheDir($fileId);
						$documentFilename = basename($documentFilePath);
					}
					
					if (!preg_match('/^' . $this->envelope['code'] . '\b/', $documentFilename)) {
						$invalidFileNames[] = $documentFilename;
					}
				}
				
				if (count($invalidFileNames) > 0) {
					$fields['details/file_id']['error'] = $this->phrase('Filenames must start with the envelope code: "[[envelope_code]]"', ['envelope_code' => $this->envelope['code']]);
				}
			}
		}
	}
	
	public function saveVisitorTUIX($path, &$tags, &$fields, &$values, &$changes) {
		if ($this->envelopeId) {
			$envelopeThumbnailId = $this->envelope['thumbnail_id'];
			foreach (explode(',', $values['details/file_id']) as $fileId) {
				if (is_numeric($fileId)) {
					$documentFileId = $fileId;
					$documentFilename = ze\row::get('files', 'filename', ['id' => $fileId]);
				} else {
					$documentFilePath = ze\file::getPathOfUploadInCacheDir($fileId);
					$documentFilename = basename($documentFilePath);
					$documentFileId = ze\file::addToDocstoreDir('document_in_envelope', $documentFilePath, $documentFilename);
				}
			
				$parts = explode('.', $documentFilename);
				$fileFormat = $parts[count($parts) - 1];
				$fileMimeType = ze\file::mimeType($fileFormat);
				$fileIsImageOrSVG = ze\file::isImageOrSVG($fileMimeType);
		
				$cols = [
					'file_id' => $documentFileId,
					'file_format' => $fileFormat,
					'envelope_id' => $this->envelopeId
				];
				
				if (!$envelopeThumbnailId && ($fileFormat == 'pdf' || $fileIsImageOrSVG)) {
					if ($fileFormat == 'pdf') {
						$thumbnailFilePath = rawurldecode(CMS_ROOT . ze\file::link($documentFileId));
						if ($thumbnailFilePath = ze\file::createPpdfFirstPageScreenshotPng($thumbnailFilePath)) {
							$thumbnailBaseName = basename($thumbnailFilePath) . '.png';
							$thumbnailFileId = ze\file::addToDatabase('document_in_envelope_thumbnail', $thumbnailFilePath, $thumbnailBaseName, true, true);
						
							$envelopeNewThumbnailId = $thumbnailFileId;
						}
					} else {
						$envelopeNewThumbnailId = $documentFileId;
					}
					
					ze\row::set(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes', ['thumbnail_id' => $envelopeNewThumbnailId], ['id' => $this->envelopeId]);
					$envelopeThumbnailId = $envelopeNewThumbnailId;
				}
		
				ze\user::setLastUpdated($cols, true);
		
				ze\row::insert(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'documents_in_envelope', $cols);
			}
			
			self::updateEnvelopeFileFormats($this->envelopeId);
		}
		
		$tags['go'] = [
			'mode' => 'list_files_in_document_envelope',
			'command' => ['submit', 'back'],
			$this->idVarName => $this->envelopeId
		];
	}
	
	public function handlePluginAJAX() {
		if ($_REQUEST['fileUpload'] ?? false) {
			ze\fileAdm::exitIfUploadError($adminFacing = false);
			ze\fileAdm::putUploadFileIntoCacheDir($_FILES['Filedata']['name'], $_FILES['Filedata']['tmp_name']);
		}
	}
}
