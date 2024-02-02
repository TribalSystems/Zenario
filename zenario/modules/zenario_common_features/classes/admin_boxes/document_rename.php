<?php
/*
 * Copyright (c) 2024, Tribal Limited
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


class zenario_common_features__admin_boxes__document_rename extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$documentId = $box['key']['id'];
		
		$isfolder=ze\row::get('documents', 'type', ['type' => 'folder','id' => $documentId]);
		
		if ($isfolder){
			$documentName=ze\row::get('documents', 'folder_name', ['type' => 'folder','id' => $documentId]);
			$box['title'] = 'Renaming the folder "'.$documentName.'"';
		}else{
			$documentName=ze\row::get('documents', 'filename', ['type' => 'file','id' => $documentId]);
			$box['title'] = 'Renaming the file "'.$documentName.'"';
		}
		$values['details/document_name'] = $documentName;
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
	
		if ($values['details/document_name'] == "") {
			$box['tabs']['details']['errors'][] = ze\admin::phrase('Please enter a name.');
		}else{
			$documentId = $box['key']['id'];
			$isfolder=ze\row::get('documents', 'type', ['type' => 'folder','id' => $documentId]);
			$parentfolderId=ze\row::get('documents', 'folder_id', ['type' => 'folder','id' => $documentId]);
			$newDocumentName = trim($values['details/document_name']);
			if ($isfolder){
				$folderNameAlreadyExists=ze\row::exists('documents', ['type' => 'folder','folder_id' => $parentfolderId,'folder_name'=>$newDocumentName]);
				$sql =  "
					SELECT id
					FROM ".DB_PREFIX."documents
					WHERE type = 'folder' 
					AND folder_id =".(int)$parentfolderId."
					AND folder_name = '".ze\escape::sql($newDocumentName)."' 
					AND id != ". (int)$documentId;
	
				$documentIdList = [];
				$result = ze\sql::select($sql);
				while($row = ze\sql::fetchAssoc($result)) {
						$documentIdList[] = $row;
				}
				$numberOfIds = count($documentIdList);

				if ($numberOfIds > 0){
					$box['tabs']['details']['errors'][] = ze\admin::phrase('The folder name “[[folder_name]]” is already taken. Please choose a different name.', ['folder_name' => $newDocumentName]);
				}
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$documentId = $box['key']['id'];
		$documentName = trim($values['details/document_name']);
		$isfolder=ze\row::get('documents', 'type', ['type' => 'folder','id' => $documentId]);
		if ($isfolder){
			ze\row::update('documents', ['folder_name' => $documentName], ['id' => $documentId]);
		}else{
			//file
			ze\row::update('documents', ['filename' => $documentName], ['id' => $documentId]);
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
}
