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


class zenario_common_features__admin_boxes__document_move extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		//...
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$fields['details/move_to']['hidden'] = $values['details/move_to_root'];
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
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
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		// Move documents to another folder
		$documentIds = ze\ray::explodeAndTrim($box['key']['id']);
		$folderId = !$values['details/move_to_root'] ? $values['details/move_to'] : 0;
		
		// Set ordinals as last in selected folder
		$sql = '
			SELECT MAX(ordinal) + 1
			FROM ' . DB_PREFIX . 'documents
			WHERE folder_id = ' . (int)$folderId;
		$result = ze\sql::select($sql);
		$row = ze\sql::fetchRow($result);
		$ordinal = $row[0] ? $row[0] : 1;
		
		foreach ($documentIds as $documentId) {
			ze\row::set('documents', ['folder_id' => $folderId, 'ordinal' => $ordinal++], $documentId);
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
}
