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

switch ($path) {
	
	case 'plugin_settings':
	case 'plugin_css_and_framework':
		return require ze::funIncPath(__FILE__, 'plugin_settings.validateAdminBox');
	
	case 'zenario_document_folder':
		
		if ($values['details/folder_name'] == "") {
			$box['tabs']['details']['errors'][] = ze\admin::phrase('You must give the folder a name.');
		}
		$folderId = $box['key']['id'];
		if(!$folderId ){
			//create a new folder
			$folderNameSaved=ze\row::get('documents', 'folder_name', ['folder_name' => $values['details/folder_name'], 'type' => 'folder', 'folder_id' => 0]);
			if ($folderNameSaved){
				$box['tabs']['details']['errors'][] = ze\admin::phrase('The folder name “[[folder_name]]” is already taken. Please choose a different name.', ['folder_name' => $values['details/folder_name']]);
			}
		}else{
			//create a subfolder
			$subFolderNameSaved=ze\row::get('documents', 'folder_name', ['folder_name' => $values['details/folder_name'], 'type' => 'folder','folder_id' => $folderId]);
			//$folderParentName=ze\row::get('documents', 'folder_name', ['type' => 'folder','id' => $folderId]);
			if ($subFolderNameSaved){
				$box['tabs']['details']['errors'][] = ze\admin::phrase('The folder name “[[subfolder_name]]” is already taken. Please choose a different name.', ['subfolder_name' => $values['details/folder_name']]);
				//$box['tabs']['details']['errors'][] = ze\admin::phrase('The folder name “[[subfolder_name]]” is already taken in the selected folder "[[folder_parent_name]]". Please choose a different name.', ['subfolder_name' => $values['details/folder_name'],'folder_parent_name'=>$folderParentName]);
			}
		}
		
		break;
	
	case 'zenario_document_move':
		
		if (!$values['details/move_to'] && !$values['details/move_to_root']) {
			$box['tabs']['details']['errors'][] = ze\admin::phrase('You must select a target folder.');
		}
		$ids = explode(',', $box['key']['id']);
		if (in_array($values['details/move_to'], $ids)) {
			$box['tabs']['details']['errors'][] = ze\admin::phrase('You can not move a folder inside itself.');
		}
		
		//avoid duplicate folder
		//folder validation
		$documentId = $box['key']['id'];
		$isfolder=ze\row::get('documents', 'type', ['type' => 'folder','id' => $documentId]);
		if ($isfolder){
			$moveToRoot = $values['details/move_to_root'];
			$moveToFolderId = $values['details/move_to'];
	
			$folderId = $box['key']['id'];
			$folderName=ze\row::get('documents', 'folder_name', ['type' => 'folder','id' => $folderId]);
	
			if($moveToRoot){
				$parentfolderId=ze\row::get('documents', 'folder_id', ['type' => 'folder','id' => $folderId]);
				if (!$parentfolderId){
					$box['tabs']['details']['errors'][] = ze\admin::phrase('The folder “[[folder_name]]” is already in root.', ['folder_name' => $folderName]);
				}else{
					$folderNameSaved=ze\row::get('documents', 'folder_name', ['folder_name' => $folderName, 'type' => 'folder','folder_id' => 0]);
					if ($folderNameSaved){
						$box['tabs']['details']['errors'][] = ze\admin::phrase('The folder name “[[folder_name]]” is already taken. Please choose a different name.', ['folder_name' => $folderName]);
						//$box['tabs']['details']['errors'][] = ze\admin::phrase('The folder name “[[subfolder_name]]” is already taken in the selected folder "[[folder_parent_name]]". Please choose a different name.', ['subfolder_name' => $values['details/folder_name'],'folder_parent_name'=>$folderParentName]);
					}
				}
			}elseif($moveToFolderId){
				$parentfolderId=ze\row::get('documents', 'folder_id', ['type' => 'folder','id' => $folderId]);
				if ($moveToFolderId == $parentfolderId){
					$box['tabs']['details']['errors'][] = ze\admin::phrase('The folder “[[subfolder_name]]” is already in the target folder selected.', ['subfolder_name' => $folderName]);
				}else{
					$folderNameSaved=ze\row::get('documents', 'folder_name', ['folder_name' => $folderName, 'type' => 'folder','folder_id' => $folderId]);
					if ($folderNameSaved){
						$box['tabs']['details']['errors'][] = ze\admin::phrase('The folder name “[[subfolder_name]]” is already taken. Please choose a different name.', ['subfolder_name' => $folderName]);
						//$box['tabs']['details']['errors'][] = ze\admin::phrase('The folder name “[[subfolder_name]]” is already taken in the selected folder "[[folder_parent_name]]". Please choose a different name.', ['subfolder_name' => $values['details/folder_name'],'folder_parent_name'=>$folderParentName]);
					}
				}
	
			}
		}else{
			//it is not a folder.
		}
	
		break;
		
		
	case 'zenario_document_rename':
	
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
	break;
	
}

return false;