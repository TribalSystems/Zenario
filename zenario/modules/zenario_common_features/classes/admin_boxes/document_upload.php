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


class zenario_common_features__admin_boxes__document_upload extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$folderDetails= getRow('documents', array('id','folder_name'), array('id' => $box['key']['id'],'type'=>'folder'));
		if ($folderDetails) {
			$box['title'] = 'Uploading document for the folder "'.$folderDetails['folder_name'].'"';
			$documentProperties['folder_id'] = $box['key']['id'];
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($values['upload_document/document__upload'] == "") {
			$box['tabs']['upload_document']['errors'][] = adminPhrase('Select a document');
		}
		
		$documentsUploaded = explode(',',$values['upload_document/document__upload']);
		$documentNameList=array();
		$found = false;
		foreach ($documentsUploaded  as $document) {
			$filename = basename(getPathOfUploadedFileInCacheDir($document));
			if ($documentNameList){
					if (in_array($filename,$documentNameList)){
						$found=true;
					}else{
						$documentNameList[]=$filename;
					}
			}else{
				$documentNameList[]=$filename;
			}
		}
		if ($found){
			$box['tabs']['upload_document']['errors'][] = adminPhrase('You cannot upload documents with the same name and extension in a folder');
		}
		
		//same name 
		if ($box['key']['id'] && $box['key']['id']!="id"){
			$parentfolderId = $box['key']['id'];
		}else{
			$parentfolderId = "0";
		}
		
		$sql="
			SELECT filename
			FROM ".DB_NAME_PREFIX."documents
			WHERE folder_id = ".(int)$parentfolderId;
			
		$result = sqlQuery($sql);
		while($row = sqlFetchAssoc($result)) {
			$fileNameList[] = $row['filename'];
		}
		
		if ($values['upload_document/document__upload'] && isset($fileNameList) && $fileNameList){
			foreach ($documentNameList as $name){
				if (array_search($name, $fileNameList) !== false) {
					$nameDetails = explode(".",$name);
					$box['tabs']['upload_document']['errors'][] = adminPhrase('A file named "[[filename]]" with extension ".[[extension]]" already exists in this folder!', array('filename' => $nameDetails[0],'extension'=>$nameDetails[1]));
					break;
				}
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$documentsUploaded = explode(',',$values['upload_document/document__upload']);
		$currentDateTime = date("Y-m-d H:i:s");
		$inFolder = getRow('documents', 'id', array('id' => $box['key']['id'], 'type' => 'folder'));
		$sql = '
			SELECT MAX(ordinal) + 1
			FROM ' . DB_NAME_PREFIX . 'documents
			WHERE folder_id = ' . (int)($inFolder ? $inFolder : 0);
		$result = sqlSelect($sql);
		$row = sqlFetchRow($result);
		$maxOrdinal = $row[0] ? $row[0] : 1;
		foreach ($documentsUploaded  as $document) {
			$filepath = getPathOfUploadedFileInCacheDir($document);
			$filename = basename(getPathOfUploadedFileInCacheDir($document));
			
			if ($filepath && $filename) {
				$fileId = addFileToDatabase('hierarchial_file', $filepath, $filename,false,false,true);
				
				$documentProperties = array(
					'type' => 'file',
					'file_id' => $fileId,
					'folder_id' => 0,
					'filename' => $filename,
					'file_datetime' => $currentDateTime,
					'ordinal' => $maxOrdinal++);
				
				//Copy privacy if a document with the same file already exists
				$docWithSameFile = getRow('documents', array('privacy', 'filename'), array('file_id' => $fileId));
				if ($docWithSameFile) {
					$documentProperties['privacy'] = $docWithSameFile['privacy'];
					$documentProperties['filename'] = $docWithSameFile['filename'];
				}
				
				//Delete any redirects that redirect the document to a different document
				$hasRedirect = false;
				$result = getRows('document_public_redirects', array('path'), array('file_id' => $fileId));
				while ($redirect = sqlFetchAssoc($result)) {
					$parts = explode('/', $redirect['path']);
					deleteCacheDir(CMS_ROOT . 'public/downloads/' . $parts[0]);
					$hasRedirect = true;
				}
				deleteRow('document_public_redirects', array('file_id' => $fileId));
				
				
				$extraProperties = zenario_common_features::addExtractToDocument($fileId);
				$documentProperties = array_merge($documentProperties, $extraProperties);
		
				if ($inFolder) {
					$documentProperties['folder_id'] = $box['key']['id'];
				}
				
				if ($documentId = insertRow('documents', $documentProperties)) {
					zenario_common_features::processDocumentRules($documentId);
					
					//If there was a redirect, this document should be public
					if ($hasRedirect) {
						zenario_common_features::generateDocumentPublicLink($documentId);
					}
				}
			}
		}
		$box['key']['id'] = $documentId;
	}
	
}
