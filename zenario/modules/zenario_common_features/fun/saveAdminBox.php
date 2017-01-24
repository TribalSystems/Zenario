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
	
	
	case 'zenario_content':
	case 'zenario_quick_create':
		return require funIncPath(__FILE__, 'content.saveAdminBox');
	
	
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
			$box['key']['id'] = 
				setRow(
					'documents',
					array(
						'type' => 'folder',
						'folder_name' => $values['details/folder_name'],
						'folder_id' => $box['key']['id'],
						'ordinal' => 0));
		} else {
			if($box['key']['id']) {
				updateRow(
					'documents',
					array(
						'type' => 'folder',
						'folder_name' => $values['details/folder_name']),
					$box['key']['id']);
			} else {
				$box['key']['id'] = 
				setRow(
					'documents',
					array(
						'type' => 'folder',
						'folder_name' => $values['details/folder_name'],
						'ordinal' => 0));
			}
		}
		break;
	
	case 'zenario_reorder_documents':
	
		$folderId = $box['key']['id'];
		$radioOrderBy = $values['details/reorder'];
		$radioSortBy =  $values['details/sort'];
		
		$sql = "
				SELECT d.id
				FROM ".DB_NAME_PREFIX."documents AS d
				LEFT JOIN ".DB_NAME_PREFIX."files as f ON d.file_id = f.id";
		if($radioOrderBy && $radioSortBy){
			if ($radioOrderBy=='file_name'){
				if ($folderId){
					$sql.=" WHERE d.folder_id = '".sqlEscape($folderId)."'";
				}else{
					$sql.=" WHERE d.folder_id = 0";
				}
				$sql.= " ORDER BY f.filename";
			}elseif($radioOrderBy=='uploading_date'){
				if ($folderId){
					$sql.=" WHERE d.folder_id = '".sqlEscape($folderId)."'";
				}else{
					$sql.=" WHERE d.folder_id = 0";
				}
				$sql.= " ORDER BY f.created_datetime";
			}else{
			// Custom data set
				$sql.=' INNER JOIN '.DB_NAME_PREFIX.'documents_custom_data AS zdcd 
					ON zdcd.document_id = d.id';
				
				if ($folderId){
					$sql.=' WHERE d.folder_id = "'.sqlEscape($folderId).'"';
				}else{
					$sql.=" WHERE d.folder_id = 0";
				}
				
				$dbColumn = getRowsArray('custom_dataset_fields',
									'db_column',
									array('id' => $radioOrderBy)
									);
				$sql.= " ORDER BY zdcd.".$dbColumn[$radioOrderBy];
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
			$i=1;
			foreach ($datasetResult as $result){
				setRow('documents', array('ordinal' => $i),array('id' => $result['id']));
				$i++;
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
	
	case 'zenario_document_properties':
	
		$id = (int)$box['key']['id'];
		$documentId = $box['key']['id'];
		$documentTitle = $values['details/document_title'];
		
		$document = getRow('documents', array('filename', 'file_id', 'title'), array('id' => $documentId));
		$file = getRow('files', 
						array('id', 'filename', 'path', 'created_datetime', 'short_checksum'),
						$document['file_id']);
		
		$oldDocumentName = $document['filename'];
		if($box['key']['id']){
			$documentName = trim($values['details/document_name']).'.'.trim($values['details/document_extension']);
		}else{
			$documentName = trim($values['details/document_name']);
		}
		
		// Rename public files directory and update filename if different
		if ($oldDocumentName != $documentName) {
			$dirPath = 'public' . '/downloads/' . $file['short_checksum'];
			$symFolder =  CMS_ROOT . $dirPath;
			$oldSymPath = $symFolder . '/' . $oldDocumentName;
			$newSymPath = $symFolder . '/' . $documentName;
			if(!windowsServer() && is_link($oldSymPath)) {
				rename($oldSymPath, $newSymPath);
			}
			
			updateRow('documents', array('filename' => $documentName), array('id' => $documentId));
		}
		
		if($document['title'] != $documentTitle) {
			updateRow('documents', array('title' => $documentTitle), array('id' => $documentId));
		}
		
		// Save document thumbnail image
		$old_image = getRowsArray('documents',
									'file_id',
									array('id' => $id)
									);
		$new_image = $values['zenario_common_feature__upload'];
		
		if ($new_image) {
			if (!in_array($new_image, $old_image)) {
				if ($path = getPathOfUploadedFileInCacheDir($new_image)) {
					$fileId = addFileToDocstoreDir('document_thumbnail', $path);
					$fileDetails = array();
					$fileDetails['thumbnail_id'] = $fileId;
					//update thumbnail
					setRow('documents', $fileDetails, $id);
				}
			}
		} elseif ($box['key']['delete_thumbnail']) {
			updateRow('documents', array('thumbnail_id' => 0), array('id' => $documentId));
		}
	
		// Save document tags
		deleteRow('document_tag_link', array('document_id' => $documentId));
		$tagIds = explodeAndTrim($values['details/tags']);
		foreach ($tagIds as $tagId) {
			setRow('document_tag_link', 
				array('tag_id' => $tagId, 'document_id' => $documentId), 
				array('tag_id' => $tagId, 'document_id' => $documentId));
		}
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
		
		
	case 'zenario_document_upload':
			$documentsUploaded = explode(',',$values['upload_document/document__upload']);
			$currentDateTime = date("Y-m-d H:i:s");
			$isFolder = getRow('documents', 'id', array('id' => $box['key']['id'], 'type' => 'folder'));
			$sql = '
				SELECT MAX(ordinal) + 1
				FROM ' . DB_NAME_PREFIX . 'documents
				WHERE folder_id = ' . (int)($isFolder ? $isFolder : 0);
			$result = sqlSelect($sql);
			$row = sqlFetchRow($result);
			$maxOrdinal = $row[0] ? $row[0] : 1;
			foreach ($documentsUploaded  as $document) {
				$filepath = getPathOfUploadedFileInCacheDir($document);
				$filename = basename(getPathOfUploadedFileInCacheDir($document));
				
				if ($filepath && $filename) {
					$documentId = addFileToDatabase('hierarchial_file', $filepath, $filename,false,false,true);
				
					$documentProperties = array(
						'type' =>'file',
						'file_id' => $documentId,
						'folder_id' => 0,
						'filename' => $filename,
						'file_datetime' => $currentDateTime,
						'ordinal' => $maxOrdinal++);
					
					$extraProperties = self::addExtractToDocument($documentId);
					$documentProperties = array_merge($documentProperties, $extraProperties);
			
					if ($isFolder) {
						$documentProperties['folder_id'] = $box['key']['id'];
					}
					
					if ($documentId = insertRow('documents', $documentProperties)) {
						self::processDocumentRules($documentId);
					}
				}
			}
			$box['key']['id'] = $documentId;
		break;
		
}

return false;