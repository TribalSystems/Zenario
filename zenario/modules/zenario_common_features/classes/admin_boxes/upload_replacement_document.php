<?php
/*
 * Copyright (c) 2018, Tribal Limited
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
		$document = getRow('documents', array('thumbnail_id', 'extract_wordcount'), $documentId);
		if (!$document['thumbnail_id']) {
			$fields['file/keep_thumbnail_image']['hidden'] = true;
		}
		if (!$document['extract_wordcount']) {
			$fields['file/keep_extract_text']['hidden'] = true;
		}
		
		if($documentId){
			$filename = getRow('documents', 'filename', $documentId);
			if($filename){
				$sql="
					SELECT COUNT(filename) as number_of_files
					FROM ".DB_NAME_PREFIX."documents
					WHERE filename = '".sqlEscape($filename)."'";
				$result = sqlSelect($sql);
				$row = sqlFetchAssoc($result);
				if($row['number_of_files'] > 1){
					$numberOfFiles = (int)$row['number_of_files'] - 1;
					
					if($numberOfFiles == 1){
						$fields['file/desc']['snippet']['html'] = adminPhrase('A replacement document cannot be uploaded because there is 1 more document with the name "[[filename]]".',array('filename'=>$filename));
					}else{
						$fields['file/desc']['snippet']['html'] = adminPhrase('A replacement document cannot be uploadeded because there are [[number_of_files]] more documents with the name "[[filename]]".',array('number_of_files'=>$numberOfFiles,'filename'=>$filename));
					}
					
					$box['tabs']['file']['edit_mode']['enabled'] = 0;
				}
			}
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$location = Ze\File::getPathOfUploadedInCacheDir($values['file/upload']);
		$file = array();
		if (is_readable($location)
			 && is_file($location)
			 && ($file['size'] = filesize($location))
			 && ($file['checksum'] = md5_file($location))
			 && ($file['checksum'] = base16To64($file['checksum']))
		) {
			$documentId = $box['key']['id'];
			$document = getRow('documents', array('file_id'), $documentId);
			$key = array('checksum' => $file['checksum'], 'id' => $document['file_id']);
			if ($existingFile = getRow('files', array('id', 'filename', 'location', 'path'), $key)) {
				if (Ze\File::docstorePath($existingFile['id'], false)){
					$fields['file/upload']['error'] = adminPhrase('The replacement document is the same as the current document.');
				}
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$documentId = $box['key']['id'];
		$document = getRow('documents', array('file_id', 'filename'), $documentId);
		$replacementDocument = $values['file/upload'];
		$replacementDocumentPath = Ze\File::getPathOfUploadedInCacheDir($replacementDocument);
		$replacementDocumentName = basename(Ze\File::getPathOfUploadedInCacheDir($replacementDocument));
		
		if ($replacementDocumentPath && $replacementDocumentName) {
			//Find if old file has public link
			$oldFile = getRow('files', array('id', 'filename', 'short_checksum'), $document['file_id']);
			$oldFilePath = CMS_ROOT . 'public/downloads/' . $oldFile['short_checksum'];
			$publicLink = is_link($oldFilePath . '/' . $document['filename']);
			
			//Upload new file
			$newFileId = Ze\File::addToDatabase('hierarchial_file', $replacementDocumentPath, false, false, false, true);
			$newFile = getRow('files', array('filename', 'short_checksum'), $newFileId);
			
			if (!$values['file/keep_meta_data']) {
				updateRow('documents', array('title' => ""), array('id' => $documentId));
				deleteRow('documents_custom_data', $documentId);
			}
			
			$documentProperties = array(
				'file_id' => $newFileId,
				'filename' => $replacementDocumentName,
				'file_datetime' => date("Y-m-d H:i:s")
			);
			
			//Copy privacy if a document with the same file already exists
			$docWithSameFile = getRow('documents', array('privacy', 'filename'), array('file_id' => $newFileId));
			if ($docWithSameFile) {
				$documentProperties['filename'] = $docWithSameFile['filename'];
				if ($docWithSameFile['privacy'] == 'public') {
					$documentProperties['privacy'] = $docWithSameFile['privacy'];
				} elseif ($publicLink) {
					updateRow('documents', array('privacy' => 'public'), array('file_id' => $newFileId));
				}
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
			//If the old file had a public link, create a new public link for the new file and remake all redirects to point to it including the old file.
			if ($publicLink && cleanCacheDir()) {
				$newRedirect = $oldFile['short_checksum'] . '/' . $document['filename'];
				$sql = '
					INSERT IGNORE INTO ' . DB_NAME_PREFIX . 'document_public_redirects (document_id, file_id, path)
					VALUES (
						'. (int) $documentId. ',
						'. (int) $oldFile['id']. ',
						\''. sqlEscape(mb_substr($newRedirect, 0, 255, 'UTF-8')). '\'
					)';
				sqlQuery($sql);
				zenario_common_features::remakeDocumentRedirectHtaccessFiles($documentId);
				zenario_common_features::generateDocumentPublicLink($documentId);
			}
		}
	}
	
}
