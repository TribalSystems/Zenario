<?php
/*
 * Copyright (c) 2021, Tribal Limited
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


class zenario_common_features__admin_boxes__reorder_documents extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id']) {
			$folderId = $box['key']['id'];
			$folderName = ze\row::get('documents', 'folder_name', ['id' => $folderId]);
			//$box['title'] = ze\admin::phrase('Renaming/adding a title to the image "[[folder_name]]".', $folderName);
			$box['title'] = "Re-order documents for the folder: '".$folderName."'";
		} else {
			$box['title'] = "Re-order documents";
		}
	
		$datasetDetails = ze\dataset::details('documents');
		$datasetId = $datasetDetails['id'];
		$datesetFields = [];
		if ($datasetDetails = ze\row::getAssocs('custom_dataset_fields', true, ['dataset_id' => $datasetId, 'is_system_field' => 0])) {
			foreach ($datasetDetails as $details) {
				if($details['type'] == 'text' || $details['type'] == 'date') {
					$datesetFields[]= $details;
				}
			}
			
			$i = 3;
			foreach ($datesetFields as $dataset){
				$box['tabs']['details']['fields']['reorder']['values'][$dataset['id']] = 
					['label' => $dataset['label'] . " - (custom dataset field)", 'ord' => $i];
				$i++;
			}
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//...
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	
		$folderId = $box['key']['id'];
		$radioOrderBy = $values['details/reorder'];
		$radioSortBy =  $values['details/sort'];
		
		$sql = "
			SELECT d.id
			FROM " . DB_PREFIX . "documents AS d
			LEFT JOIN " . DB_PREFIX . "files as f 
				ON d.file_id = f.id";
		if($radioOrderBy && $radioSortBy){
			if ($radioOrderBy=='file_name') {
				if ($folderId){
					$sql.=" WHERE d.folder_id = '".ze\escape::sql($folderId)."'";
				}else{
					$sql.=" WHERE d.folder_id = 0";
				}
				$sql.= " ORDER BY d.filename";
			} elseif ($radioOrderBy=='uploading_date') {
				if ($folderId) {
					$sql.=" WHERE d.folder_id = '".ze\escape::sql($folderId)."'";
				} else {
					$sql.=" WHERE d.folder_id = 0";
				}
				$sql.= " ORDER BY f.created_datetime";
			} else {
				//Custom data set
				$sql.=' INNER JOIN '.DB_PREFIX.'documents_custom_data AS zdcd 
					ON zdcd.document_id = d.id';
				
				if ($folderId) {
					$sql.=' WHERE d.folder_id = "'.ze\escape::sql($folderId).'"';
				} else {
					$sql.=" WHERE d.folder_id = 0";
				}
				
				$dbColumn = ze\row::getValues('custom_dataset_fields', 'db_column', $radioOrderBy);
				$sql.= " ORDER BY zdcd.`" . $dbColumn[$radioOrderBy] . "`";
			}
			// Sort order
			if($radioSortBy == 'ascending'){
				$sql .= ' ASC';
			}elseif($radioSortBy == 'descending'){
				$sql .= ' DESC';
			}
			$datasetResult = [];
			$result = ze\sql::select($sql);
			while($row = ze\sql::fetchAssoc($result)) {
				$datasetResult[] = $row;
			}
			//update ordinal in the db
			$i = 0;
			foreach ($datasetResult as $result){
				ze\row::set('documents', ['ordinal' => ++$i], $result['id']);
			}
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
}
