<?php
/*
 * Copyright (c) 2016, Tribal Limited
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
	
	
	case 'zenario_reusable_plugin':
		if (engToBooleanArray($box['tabs']['instance'], 'edit_mode', 'on') && checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			$nest = false;
			if ($box['key']['duplicate']) {
				renameInstance($box['key']['id'], $nest, $values['instance/name'], $createNewInstance = true);
			
			} else {
				renameInstance($box['key']['id'], $nest, $values['instance/name'], $createNewInstance = false);
			}
		}
		
		break;
	
	
	case 'zenario_alias':
		if (checkPriv('_PRIV_EDIT_DRAFT')) {
			$cols = array('alias' => tidyAlias($values['meta_data/alias']));
			$key = array('id' => $box['key']['cID'], 'type' => $box['key']['cType']);
			$equivKey = array('equiv_id' => equivId($box['key']['cID'], $box['key']['cType']), 'type' => $box['key']['cType']);
			
			if (getNumLanguages() > 1) {
				if ($values['meta_data/update_translations'] == 'update_all') {
					$key = $equivKey;
					$cols['lang_code_in_url'] = 'default';
				
				} else {
					$cols['lang_code_in_url'] = $values['meta_data/lang_code_in_url'];
				}
			}
			
			updateRow('content_items', $cols, $key);
		}
		
		break;
	
	
	case 'zenario_enable_site':
		if (checkPriv('_PRIV_EDIT_SITE_SETTING')) {
			setSetting('site_enabled', $values['site/enable_site']);
			setSetting('site_disabled_title', $values['site/site_disabled_title']);
			setSetting('site_disabled_message', $values['site/site_disabled_message']);
			
			$box['key']['id'] = $values['site/enable_site']? 'site_enabled' : 'site_disabled';
		}
		
		
		break;
	
	
	case 'zenario_delete_language':
		deleteLanguage($box['key']['id']);
		
		break;
	
	
	case 'zenario_site_reset':
		exitIfNotCheckPriv('_PRIV_RESET_SITE');
		
		resetSite();
		echo '<!--Reload_Storekeeper-->';
		exit;
	
	
	case 'zenario_menu':
		return require funIncPath(__FILE__, 'menu_node.saveAdminBox');
	
	
	case 'zenario_content':
	case 'zenario_quick_create':
		return require funIncPath(__FILE__, 'content.saveAdminBox');

	
	case 'zenario_content_layout':
		
		//Loop through each Content Item, saving each
		$cID = $cType = $cVersion = false;
		$tagIds = explode(',', $box['key']['id']);
		foreach ($tagIds as $tagId) {
			if (getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
				
				if (!checkPriv('_PRIV_EDIT_CONTENT_ITEM_TEMPLATE', $cID, $cType)) {
					continue;
				}
				
				//Create a draft if needed
				createDraft($cID, $cID, $cType, $cVersion, getLatestVersion($cID, $cType));
				
				//Update the layout
				changeContentItemLayout($cID, $cType, $cVersion, $values['layout_id']);
				
				//Mark this version as updated
				updateVersion($cID, $cType, $cVersion, $version = array(), $forceMarkAsEditsMade = true);
			}
		}
		
		break;
		
		
	case 'zenario_content_categories':
		exitIfNotCheckPriv('_PRIV_EDIT_CONTENT_ITEM_CATEGORIES');
		
		$cID = $cType = false;
		
		$tagIds = explode(',', $box['key']['id']);
		
		foreach ($tagIds as $tagId) {
			if (getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
				setContentItemCategories($cID, $cType, explode(',', $values['categories/categories']));
			}
		}
		
		break;
	
	case 'zenario_content_categories_add':
		exitIfNotCheckPriv('_PRIV_EDIT_CONTENT_ITEM_CATEGORIES');
		
		$cID = $cType = false;
		
		$tagIds = explode(',', $box['key']['id']);
		
		foreach ($tagIds as $tagId) {
			if (getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
				addContentItemToCategories($cID, $cType, explode(',', $values['categories_add/categories_add']));
				
				}
		}
		
		break;
	case 'zenario_content_categories_remove':
		exitIfNotCheckPriv('_PRIV_EDIT_CONTENT_ITEM_CATEGORIES');
		
		$cID = $cType = false;
		
		$tagIds = explode(',', $box['key']['id']);
		
		foreach ($tagIds as $tagId) {
			if (getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
				removeContentItemCategories($cID, $cType, explode(',', $values['categories_remove/categories_remove']));
				
				}
		}
		
		break;
	case 'site_settings':
		return require funIncPath(__FILE__, 'site_settings.saveAdminBox');
	
	
	case 'zenario_setup_language':
		return require funIncPath(__FILE__, 'setup_language.saveAdminBox');
	
	
	case 'zenario_setup_module':
		return require funIncPath(__FILE__, 'setup_module.saveAdminBox');
	
	
	case 'zenario_publish':
		$ids = (($box['key']['id']) ? $box['key']['id'] : $box['key']['cID']);
		
		foreach (explode(',', $ids) as $id) {
			$cID = $cType = false;
			if (!empty($box['key']['cID']) && !empty($box['key']['cType'])) {
				$cID = $box['key']['cID'];
				$cType = $box['key']['cType'];
			} else {
				getCIDAndCTypeFromTagId($cID, $cType, $id);
			}
			
			if ($cID && $cType && checkPriv('_PRIV_PUBLISH_CONTENT_ITEM', $cID, $cType)) {
				if ($values['publish/publish_options'] == 'immediately') {
					// Publish now
					publishContent($cID, $cType);
					if (session('last_item') == $cType. '_'. $cID) {
						$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'preview';
					}
				} else {
					// Publish at a later date
					$scheduled_publish_datetime = $values['publish/publish_date'].' '.$values['publish/publish_hours'].':'.$values['publish/publish_mins'].':00';
					$cVersion = getRow('content_items', 'admin_version', array('id' => $cID, 'type' => $cType));
					updateRow('content_item_versions', array('scheduled_publish_datetime'=>$scheduled_publish_datetime), array('id' =>$cID, 'type'=>$cType, 'version'=>$cVersion));
					
					// Lock content item
					$adminId = session('admin_userid');
					updateRow('content_items', array('lock_owner_id'=>$adminId, 'locked_datetime'=>date('Y-m-d H:i:s')), array('id' =>$cID, 'type'=>$cType));
				}
			}
		}
		
		break;
	
	
	case 'zenario_create_vlp':
		exitIfNotCheckPriv('_PRIV_MANAGE_LANGUAGE_CONFIG');
		
		setRow(
			'visitor_phrases',
			array(
				'local_text' => $values['details/english_name'],
				'protect_flag' => 1),
			array(
				'code' => '__LANGUAGE_ENGLISH_NAME__',
				'language_id' => $values['details/language_id'],
				'module_class_name' => 'zenario_common_features'));
		
		setRow(
			'visitor_phrases',
			array(
				'local_text' => $values['details/language_local_name'],
				'protect_flag' => 1),
			array(
				'code' => '__LANGUAGE_LOCAL_NAME__',
				'language_id' => $values['details/language_id'],
				'module_class_name' => 'zenario_common_features'));
		
		setRow(
			'visitor_phrases',
			array(
				'local_text' => decodeItemIdForStorekeeper($values['details/flag_filename']),
				'protect_flag' => 1),
			array(
				'code' => '__LANGUAGE_FLAG_FILENAME__',
				'language_id' => $values['details/language_id'],
				'module_class_name' => 'zenario_common_features'));
		
		$box['key']['id'] = $values['details/language_id'];
		
		$box['popout_message'] = '<!--Open_Admin_Box:zenario_setup_language//'. $values['details/language_id']. '-->';
		
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
		$documentName = trim($values['details/document_name']);
		
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
		$tagIds = explode(',', $values['details/tags']);
		foreach ($tagIds as $tagId) {
			setRow('document_tag_link', 
				array('tag_id' => $tagId, 'document_id' => $documentId), 
				array('tag_id' => $tagId, 'document_id' => $documentId));
		}
		break;
		
	case 'zenario_migrate_old_documents':
		
		$datasetDetails = getDatasetDetails('documents');
		$documentList = explode(',',$box['key']['id']);
		$documentData = array();
		$documentDatasetFieldDetails = getRowsArray('custom_dataset_fields', 'db_column', array('dataset_id' => $datasetDetails['id']));
		
		$sql = '
			SELECT MAX(ordinal)
			FROM '.DB_NAME_PREFIX.'documents
			WHERE folder_id = '.(int)$values['details/folder'];
		$result = sqlSelect($sql);
		$maxOrdinal = sqlFetchArray($result);
		$ordinal = $maxOrdinal[0] ? 1 : (int)$maxOrdinal[0] + 1;
		$failed = 0;
		$succeeded = 0;
		
		foreach($documentList as $tagId) {
			// Get old document details
			$documentData = array();
			$sql = '
				SELECT c.language_id, v.title, v.description, v.keywords, v.content_summary, v.file_id, v.created_datetime, v.filename
				FROM '.DB_NAME_PREFIX.'content_items AS c
				INNER JOIN '.DB_NAME_PREFIX.'content_item_versions AS v
					ON (c.tag_id = v.tag_id AND c.admin_version = v.version)
				WHERE c.tag_id = "'.sqlEscape($tagId).'"';
			$result = sqlSelect($sql);
			$documentData = sqlFetchAssoc($result);
			// If alreadly migrated, go to next document
			if (checkRowExists('documents', array('file_id' => $documentData['file_id']))) {
				$failed++;
				continue;
			}
			
			$documentProperties = array(
				'ordinal' => $ordinal,
				'type' => 'file', 
				'file_id' => $documentData['file_id'], 
				'folder_id' => $values['details/folder'],
				'file_datetime' => $documentData['created_datetime'],
				'filename' => $documentData['filename']);
			$extraProperties = self::addExtractToDocument($documentProperties['file_id']);
			
			$properties = array_merge($documentProperties, $extraProperties);
			// Create new document
			$documentId = insertRow('documents', $properties);
			
			// Get document custom data
			$customData = array();
			if ($values['details/title']) {
				$customData[$documentDatasetFieldDetails[$values['details/title']]] = $documentData['title'];
			}
			if ($values['details/language_id']) {
				$customData[$documentDatasetFieldDetails[$values['details/language_id']]] = $documentData['language_id'];
			}
			if ($values['details/description']) {
				$customData[$documentDatasetFieldDetails[$values['details/description']]] = $documentData['description'];
			}
			if ($values['details/keywords']) {
				$customData[$documentDatasetFieldDetails[$values['details/keywords']]] = $documentData['keywords'];
			}
			if ($values['details/content_summary']) {
				$customData[$documentDatasetFieldDetails[$values['details/content_summary']]] = $documentData['content_summary'];
			}
			// Save document custom data
			setRow('documents_custom_data', $customData, array('document_id' => $documentId));
			$succeeded++;
			
			// Hide document
			updateRow('content_items', array('status' => 'hidden'), array('tag_id' => $tagId));
			$ordinal++;
		}
		// Code to show success messages after migrating documents
		$box['popout_message'] = '';
		
		if ($failed && !$succeeded) {
			$box['popout_message'] .= "<!--Message_Type:Error-->";
			$box['popout_message'] .= '<p>';
			$box['popout_message'] .= nAdminPhrase(
				'[[failed]] file could not be migrated as a document with this file already exists',
				'[[failed]] files could not be migrated as a document with this file already exists',
				$failed,
				array('failed' => $failed));
			$box['popout_message'] .= '</p>';
		} elseif ($failed && $succeeded) {
			$box['popout_message'] .= "<!--Message_Type:Warning-->";
			$box['popout_message'] .= '<p>';
			$box['popout_message'] .= nAdminPhrase(
				'[[failed]] file could not be migrated as a document with this file already exists',
				'[[failed]] files could not be migrated as a document with this file already exists',
				$failed,
				array('failed' => $failed));
			$box['popout_message'] .= '</p>';
			
			$box['popout_message'] .= '<p>';
			$box['popout_message'] .= nAdminPhrase(
				'[[succeeded]] file was successfully migrated',
				'[[succeeded]] files were successfully migrated',
				$succeeded,
				array('succeeded' => $succeeded));
			$box['popout_message'] .= '</p>';
			
		} else {
			$box['popout_message'] .= "<!--Message_Type:Success-->";
			$box['popout_message'] .= '<p>';
			$box['popout_message'] .= nAdminPhrase(
				'[[succeeded]] file was successfully migrated',
				'[[succeeded]] files were successfully migrated',
				$succeeded,
				array('succeeded' => $succeeded));
			$box['popout_message'] .= '</p>';
		}
		
		break;
		
	case 'zenario_document_move':
		if ($values['details/move_to_root']) {
			$values['details/move_to'] = 0;
		}
		foreach (explode(',', $box['key']['id']) as $id) {
			setRow('documents', array('folder_id' => $values['details/move_to']), $id);
		}
		break;
		
	case 'zenario_file_type':
		exitIfNotCheckPriv('_PRIV_EDIT_CONTENT_TYPE');
		
		insertRow('document_types', array('type' => $values['details/type'], 'mime_type' => $values['details/mime_type']));
		$box['key']['id'] = $values['details/type'];
		
		break;
	
	
	case 'zenario_content_type_details':
		if (checkPriv('_PRIV_EDIT_CONTENT_TYPE')) {
			
			$vals = array(
				'content_type_name_en' => $values['details/content_type_name_en'],
				'description_field' => $values['details/description_field'],
				'keywords_field' => $values['details/keywords_field'],
				'writer_field' => $values['details/writer_field'],
				'summary_field' => $values['details/summary_field'],
				'release_date_field' => $values['details/release_date_field'],
				'enable_summary_auto_update' => $values['details/enable_summary_auto_update'],
				'enable_categories' => ($values['details/enable_categories'] == 'enabled') ? 1 : 0,
				'default_layout_id' => $values['details/default_layout_id']);
			
			if ($values['details/summary_field'] == 'hidden') {
				$vals['enable_summary_auto_update'] = 0;
			}
			
			switch ($box['key']['id']) {
				case 'document':
				case 'picture':
				case 'html':
					//HTML/Document/Picture fields cannot currently be mandatory
					foreach (array('description_field', 'keywords_field', 'summary_field', 'release_date_field') as $field) {
						if ($vals[$field] == 'mandatory') {
							$vals[$field] = 'optional';
						}
					}
					
					break;
					
				
				case 'event':
					//Event release dates must be hidden as it is overridden by another field
					$vals['release_date_field'] = 'hidden';
			}
			
			updateRow('content_types', $vals, $box['key']['id']);
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