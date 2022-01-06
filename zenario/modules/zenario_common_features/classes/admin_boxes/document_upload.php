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


class zenario_common_features__admin_boxes__document_upload extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (!ze\module::isRunning('zenario_extranet')) {
			$fields['upload_document/privacy']['values']['private']['disabled'] = true;
		}
		
		$folderDetails= ze\row::get('documents', ['id','folder_name'], ['id' => $box['key']['id'],'type'=>'folder']);
		if ($folderDetails) {
			$box['title'] = 'Uploading document for the folder "'.$folderDetails['folder_name'].'"';
			$documentProperties['folder_id'] = $box['key']['id'];
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($values['upload_document/document__upload'] == "") {
			$box['tabs']['upload_document']['errors'][] = ze\admin::phrase('Select a document.');
		}
		
		$documentsUploaded = explode(',',$values['upload_document/document__upload']);
		$documentNameList = [];
		$found = false;
		foreach ($documentsUploaded  as $document) {
			if (is_numeric($document)) {
				$filename = ze\row::get('files', 'filename', $document);
			} else {
				$location = ze\file::getPathOfUploadInCacheDir($document);
				$filename = basename($location);
				
				$fileCheck = ze\file::check($location);
				if (ze::isError($fileCheck) && $document) {
					$box['tabs']['upload_document']['errors'][] = $fileCheck->__toString();
				}
			}
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
			$box['tabs']['upload_document']['errors'][] = ze\admin::phrase('You cannot upload documents with the same name and extension in a folder');
		}
		
		//same name 
		if ($box['key']['id'] && $box['key']['id']!="id"){
			$parentfolderId = $box['key']['id'];
		}else{
			$parentfolderId = "0";
		}
		
		$sql="
			SELECT filename
			FROM ".DB_PREFIX."documents
			WHERE folder_id = ".(int)$parentfolderId;
			
		$result = ze\sql::select($sql);
		while($row = ze\sql::fetchAssoc($result)) {
			$fileNameList[] = $row['filename'];
		}
		
		if ($values['upload_document/document__upload'] && isset($fileNameList) && $fileNameList){
			foreach ($documentNameList as $name){
				if (array_search($name, $fileNameList) !== false) {
					$nameDetails = explode(".",$name);
					$box['tabs']['upload_document']['errors'][] = ze\admin::phrase('A file named "[[filename]]" with extension ".[[extension]]" already exists in this folder!', ['filename' => $nameDetails[0],'extension'=>$nameDetails[1]]);
					break;
				}
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$inFolder = ze\row::get('documents', 'id', ['id' => $box['key']['id'], 'type' => 'folder']);
		$folderId = $inFolder ? $box['key']['id'] : false;
		
		//Get selected privacy setting
		$privacy = $box['tabs']['upload_document']['fields']['privacy']['current_value'];
		
		$documentsUploaded = explode(',',$values['upload_document/document__upload']);
		$documentId = false;
		foreach ($documentsUploaded as $document) {
			$filepath = ze\file::getPathOfUploadInCacheDir($document);
			$filename = basename(ze\file::getPathOfUploadInCacheDir($document));
			
			if ($filepath && $filename) {
				$documentId = ze\document::upload($filepath, $filename, $folderId, $privacy);
			}
		}
		$box['key']['id'] = $documentId;
		
	}
	
}
