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


class zenario_common_features__admin_boxes__upload_replacement_document extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$documentId = $box['key']['id'];
		$documentDetails = getRow('documents', array('thumbnail_id', 'extract_wordcount'), $documentId);
		if (!$documentDetails['thumbnail_id']) {
			$fields['file/keep_thumbnail_image']['hidden'] = true;
		}
		if (!$documentDetails['extract_wordcount']) {
			$fields['file/keep_extract_text']['hidden'] = true;
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$documentId = $box['key']['id'];
		$documentDetails = getRow('documents', array('file_id', 'filename'), $documentId);
		
		$document = $values['file/upload'];
		$filepath = getPathOfUploadedFileInCacheDir($document);
		$filename = basename(getPathOfUploadedFileInCacheDir($document));
		if ($filepath && $filename) {
			
			// Find if old file has public link
			$oldFile = getRow('files', array('id', 'filename', 'short_checksum'), $documentDetails['file_id']);
			$dirPath = 'public' . '/downloads/' . $oldFile['short_checksum'];
			$frontLink = $dirPath . '/' . $documentDetails['filename'];
			$symPath = CMS_ROOT . $frontLink;
			$symFolder =  CMS_ROOT . $dirPath;
			$publicLink = false;
			if (!windowsServer() && docstoreFilePath($oldFile['id'], false)) {
				if (is_link($symPath)) {
					$publicLink = true;
				}
			}
			
			// Upload new file
			$newFileId = addFileToDatabase('hierarchial_file', $filepath, false, false, false, true);
			$newFile = getRow('files', array('filename', 'short_checksum'), $newFileId);
			
			// If there was a public link delete it and make a new one
			if ($publicLink) {
				zenario_common_features::deleteHierarchicalDocumentPubliclink($documentId);
				if (cleanDownloads() 
					&& ($dirPath = createCacheDir($newFile['short_checksum'], 'public/downloads', false))
				) {
					$symFolder =  CMS_ROOT . $dirPath;
					$symPath = $symFolder . $filename;
					
					if (!windowsServer() && ($path = docstoreFilePath($newFileId, false))) {
						if (!file_exists($symPath)) {
							if(!file_exists($symFolder)) {
								mkdir($symFolder);
							}
							symlink($path, $symPath);
						}
					}
				}
			}
			
			$documentProperties = array(
				'file_id' => $newFileId,
			);
			
			if (!$values['file/keep_meta_data']) {
				$documentProperties['filename'] = $filename;
				deleteRow('documents_custom_data', $documentId);
			}
			
			if (!$values['file/keep_thumbnail_image'] || !$values['file/keep_extract_text']) {
				$extraProperties = zenario_common_features::addExtractToDocument($newFileId);
				
				if (!$values['file/keep_thumbnail_image']) {
					$documentProperties['thumbnail_id'] = $extraProperties['thumbnail_id'];
				}
				if (!$values['file/keep_extract_text']) {
					$documentProperties['extract'] = $extraProperties['extract'];
					$documentProperties['extract_wordcount'] = $extraProperties['extract_wordcount'];
				}
			}
			setRow('documents', $documentProperties, $documentId);
		}
	}
}
