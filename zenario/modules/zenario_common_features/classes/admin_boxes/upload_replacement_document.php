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


class zenario_common_features__admin_boxes__upload_replacement_document extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$documentId = $box['key']['id'];
		$document = ze\row::get('documents', ['thumbnail_id', 'extract_wordcount'], $documentId);
		if (!$document['thumbnail_id']) {
			$fields['file/keep_thumbnail_image']['hidden'] = true;
		}
		if (!$document['extract_wordcount']) {
			$fields['file/keep_extract_text']['hidden'] = true;
		}
		
		if($documentId){
			$filename = ze\row::get('documents', 'filename', $documentId);
			if($filename){
				$sql="
					SELECT COUNT(filename) as number_of_files
					FROM ".DB_NAME_PREFIX."documents
					WHERE filename = '".ze\escape::sql($filename)."'";
				$result = ze\sql::select($sql);
				$row = ze\sql::fetchAssoc($result);
				if($row['number_of_files'] > 1){
					$numberOfFiles = (int)$row['number_of_files'] - 1;
					
					$fields['file/desc']['hidden'] = false;
					if($numberOfFiles == 1){
						$fields['file/desc']['snippet']['html'] = ze\admin::phrase('A replacement document cannot be uploaded because there is 1 more document with the name "[[filename]]".',['filename'=>$filename]);
					}else{
						$fields['file/desc']['snippet']['html'] = ze\admin::phrase('A replacement document cannot be uploadeded because there are [[number_of_files]] more documents with the name "[[filename]]".',['number_of_files'=>$numberOfFiles,'filename'=>$filename]);
					}
					
					$box['tabs']['file']['edit_mode']['enabled'] = 0;
				}
			}
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$location = ze\file::getPathOfUploadInCacheDir($values['file/upload']);
		$file = [];
		if (is_readable($location)
			 && is_file($location)
			 && ($file['size'] = filesize($location))
			 && ($file['checksum'] = md5_file($location))
			 && ($file['checksum'] = ze::base16To64($file['checksum']))
		) {
			$documentId = $box['key']['id'];
			$document = ze\row::get('documents', ['file_id'], $documentId);
			$key = ['checksum' => $file['checksum'], 'id' => $document['file_id']];
			if ($existingFile = ze\row::get('files', ['id', 'filename', 'location', 'path'], $key)) {
				if (ze\file::docstorePath($existingFile['id'], false)){
					$fields['file/upload']['error'] = ze\admin::phrase('The replacement document is the same as the current document.');
				}
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$documentId = $box['key']['id'];
		$document = ze\row::get('documents', ['file_id', 'filename'], $documentId);
		$replacementDocument = $values['file/upload'];
		$replacementDocumentPath = ze\file::getPathOfUploadInCacheDir($replacementDocument);
		$replacementDocumentName = basename(ze\file::getPathOfUploadInCacheDir($replacementDocument));
		
		if ($replacementDocumentPath && $replacementDocumentName) {
			//Find if old file has public link
			$oldFile = ze\row::get('files', ['id', 'filename', 'short_checksum'], $document['file_id']);
			$oldFilePath = CMS_ROOT . 'public/downloads/' . $oldFile['short_checksum'];
			$publicLink = is_link($oldFilePath . '/' . $document['filename']);
			
			//Upload new file
			$newFileId = ze\file::addToDatabase('hierarchial_file', $replacementDocumentPath, false, false, false, true);
			$newFile = ze\row::get('files', ['filename', 'short_checksum'], $newFileId);
			
			if (!$values['file/keep_meta_data']) {
				ze\row::update('documents', ['title' => ""], ['id' => $documentId]);
				ze\row::delete('documents_custom_data', $documentId);
			}
			
			$documentProperties = [
				'file_id' => $newFileId,
				'filename' => $replacementDocumentName,
				'file_datetime' => date("Y-m-d H:i:s")
			];
			
			//Copy privacy if a document with the same file already exists
			$docWithSameFile = ze\row::get('documents', ['privacy', 'filename'], ['file_id' => $newFileId]);
			if ($docWithSameFile) {
				$documentProperties['filename'] = $docWithSameFile['filename'];
				if ($docWithSameFile['privacy'] == 'public') {
					$documentProperties['privacy'] = $docWithSameFile['privacy'];
				} elseif ($publicLink) {
					ze\row::update('documents', ['privacy' => 'public'], ['file_id' => $newFileId]);
				}
			}
			
			if (!$values['file/keep_thumbnail_image'] || !$values['file/keep_extract_text']) {
				$extraProperties = ze\document::addExtract($newFileId);
				if (!$values['file/keep_thumbnail_image']) {
					$documentProperties['thumbnail_id'] = $extraProperties['thumbnail_id'] ?? 0;
				}
				if (!$values['file/keep_extract_text']) {
					$documentProperties['extract'] = $extraProperties['extract'];
					$documentProperties['extract_wordcount'] = $extraProperties['extract_wordcount'];
				}
			}
			ze\row::set('documents', $documentProperties, $documentId);
			//If the old file had a public link, create a new public link for the new file and remake all redirects to point to it including the old file.
			if ($publicLink && ze\cache::cleanDirs()) {
				$newRedirect = $oldFile['short_checksum'] . '/' . $document['filename'];
				$sql = '
					INSERT IGNORE INTO ' . DB_NAME_PREFIX . 'document_public_redirects (document_id, file_id, path)
					VALUES (
						'. (int) $documentId. ',
						'. (int) $oldFile['id']. ',
						\''. ze\escape::sql(mb_substr($newRedirect, 0, 255, 'UTF-8')). '\'
					)';
				ze\sql::update($sql);
				
				//Delete any redirects to the same document to stop infinite redirect shenanigans
				ze\row::delete('document_public_redirects', ['document_id' => $documentId, 'file_id' => $newFileId]);
				
				ze\document::remakeRedirectHtaccessFiles($documentId);
				ze\document::generatePublicLink($documentId);
			}
		}
	}
	
}
