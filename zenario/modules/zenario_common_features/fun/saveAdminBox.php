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

switch ($path) {
	case 'plugin_settings':
	case 'plugin_css_and_framework':
		return require funIncPath(__FILE__, 'plugin_settings.saveAdminBox');
	
	
	case 'zenario_content_categories':
		exitIfNotCheckPriv('_PRIV_EDIT_CONTENT_ITEM_CATEGORIES');
		
		$cID = $cType = false;
		
		$tagIds = explodeAndTrim($box['key']['id']);
		
		foreach ($tagIds as $tagId) {
			if (getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
				setContentItemCategories($cID, $cType, explodeAndTrim($values['categories/categories']));
			}
		}
		
		break;
	
	case 'zenario_content_categories_add':
		exitIfNotCheckPriv('_PRIV_EDIT_CONTENT_ITEM_CATEGORIES');
		
		$cID = $cType = false;
		
		$tagIds = explodeAndTrim($box['key']['id']);
		
		foreach ($tagIds as $tagId) {
			if (getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
				addContentItemToCategories($cID, $cType, explodeAndTrim($values['categories_add/categories_add']));
				
				}
		}
		
		break;
	case 'zenario_content_categories_remove':
		exitIfNotCheckPriv('_PRIV_EDIT_CONTENT_ITEM_CATEGORIES');
		
		$cID = $cType = false;
		
		$tagIds = explodeAndTrim($box['key']['id']);
		
		foreach ($tagIds as $tagId) {
			if (getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
				removeContentItemCategories($cID, $cType, explodeAndTrim($values['categories_remove/categories_remove']));
				
				}
		}
		
		break;
	
	
	case 'zenario_document_folder':
		if (isset($box['key']['add_folder']) && $box['key']['add_folder']) {
			$box['key']['id'] = zenario_common_features::createFolder($values['details/folder_name'], $box['key']['id']);
		} else {
			if($box['key']['id']) {
				updateRow(
					'documents',
					array(
						'type' => 'folder',
						'folder_name' => $values['details/folder_name']),
					$box['key']['id']);
			} else {
				$box['key']['id'] = zenario_common_features::createFolder($values['details/folder_name']);
			}
		}
		break;
	
	case 'zenario_reorder_documents':
	
		$folderId = $box['key']['id'];
		$radioOrderBy = $values['details/reorder'];
		$radioSortBy =  $values['details/sort'];
		
		$sql = "
			SELECT d.id
			FROM " . DB_NAME_PREFIX . "documents AS d
			LEFT JOIN " . DB_NAME_PREFIX . "files as f 
				ON d.file_id = f.id";
		if($radioOrderBy && $radioSortBy){
			if ($radioOrderBy=='file_name') {
				if ($folderId){
					$sql.=" WHERE d.folder_id = '".sqlEscape($folderId)."'";
				}else{
					$sql.=" WHERE d.folder_id = 0";
				}
				$sql.= " ORDER BY d.filename";
			} elseif ($radioOrderBy=='uploading_date') {
				if ($folderId) {
					$sql.=" WHERE d.folder_id = '".sqlEscape($folderId)."'";
				} else {
					$sql.=" WHERE d.folder_id = 0";
				}
				$sql.= " ORDER BY f.created_datetime";
			} else {
				//Custom data set
				$sql.=' INNER JOIN '.DB_NAME_PREFIX.'documents_custom_data AS zdcd 
					ON zdcd.document_id = d.id';
				
				if ($folderId) {
					$sql.=' WHERE d.folder_id = "'.sqlEscape($folderId).'"';
				} else {
					$sql.=" WHERE d.folder_id = 0";
				}
				
				$dbColumn = getRowsArray('custom_dataset_fields', 'db_column', $radioOrderBy);
				$sql.= " ORDER BY zdcd.`" . $dbColumn[$radioOrderBy] . "`";
			}
			// Sort order
			if($radioSortBy == 'ascending'){
				$sql .= ' ASC';
			}elseif($radioSortBy == 'descending'){
				$sql .= ' DESC';
			}
			$datasetResult = array();
			$result = sqlSelect($sql);
			while($row = sqlFetchAssoc($result)) {
				$datasetResult[] = $row;
			}
			//update ordinal in the db
			$i = 0;
			foreach ($datasetResult as $result){
				setRow('documents', array('ordinal' => ++$i), $result['id']);
			}
		}
		break;
	
	case 'zenario_document_tag':
		$box['key']['id'] = 
			setRow(
				'document_tags',
				array(
					'tag_name' => $values['details/tag_name']),
				$box['key']['id']);
		break;
		
	case 'zenario_document_move':
		// Move documents to another folder
		$documentIds = explodeAndTrim($box['key']['id']);
		$folderId = !$values['details/move_to_root'] ? $values['details/move_to'] : 0;
		
		// Set ordinals as last in selected folder
		$sql = '
			SELECT MAX(ordinal) + 1
			FROM ' . DB_NAME_PREFIX . 'documents
			WHERE folder_id = ' . (int)$folderId;
		$result = sqlSelect($sql);
		$row = sqlFetchRow($result);
		$ordinal = $row[0] ? $row[0] : 1;
		
		foreach ($documentIds as $documentId) {
			setRow('documents', array('folder_id' => $folderId, 'ordinal' => $ordinal++), $documentId);
		}
		break;
		
	case 'zenario_document_rename':
		$documentId = $box['key']['id'];
		$documentName = trim($values['details/document_name']);
		$isfolder=getRow('documents', 'type', array('type' => 'folder','id' => $documentId));
		if ($isfolder){
			updateRow('documents', array('folder_name' => $documentName), array('id' => $documentId));
		}else{
			//file
			updateRow('documents', array('filename' => $documentName), array('id' => $documentId));
		}
		break;
		
}

return false;