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


class zenario_common_features__admin_boxes__document_folder extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (isset($box['key']['add_folder']) && $box['key']['add_folder']) {
			$parentFolderDetails = 
				ze\row::get(
					'documents',
					['folder_name'], $box['key']['id']);
			$box['title'] = ze\admin::phrase('Create a subfolder inside "[[folder_name]]".', $parentFolderDetails);
		} elseif ($folderDetails = ze\row::get('documents', ['folder_name'], $box['key']['id'])) {
			$values['details/folder_name'] = $folderDetails['folder_name'];
			$box['title'] = ze\admin::phrase('Editing folder "[[folder_name]]".', $folderDetails);
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
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
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (isset($box['key']['add_folder']) && $box['key']['add_folder']) {
			$box['key']['id'] = ze\document::createFolder($values['details/folder_name'], $box['key']['id']);
		} else {
			if($box['key']['id']) {
				ze\row::update(
					'documents',
					[
						'type' => 'folder',
						'folder_name' => $values['details/folder_name']],
					$box['key']['id']);
			} else {
				$box['key']['id'] = ze\document::createFolder($values['details/folder_name']);
			}
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
}
