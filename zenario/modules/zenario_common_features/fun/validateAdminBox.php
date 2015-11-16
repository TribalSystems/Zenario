<?php
/*
 * Copyright (c) 2015, Tribal Limited
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
	case 'zenario_publish':
		// Make sure chosen time is not in the past
		if ($values['publish/publish_options'] == 'schedule') {
			if (!empty($values['publish/publish_date'])) {
				$now = strtotime('now');
				$scheduledDate = strtotime($values['publish/publish_date'].' '. $values['publish/publish_hours'].':'.$values['publish/publish_mins']);
				if ($now < $scheduledDate) {
					break;
				} else {
					$box['tabs']['publish']['errors'][] = 'The scheduled publishing time cannot be in the past.';
				}
			} else {
				$box['tabs']['publish']['errors'][] = 'Please enter a date.';
			}
			
		}
		
		break;
	
	case 'plugin_settings':
	case 'plugin_css_and_framework':
		return require funIncPath(__FILE__, 'plugin_settings.validateAdminBox');
	
	
	case 'zenario_reusable_plugin':
		if (engToBooleanArray($box['tabs']['instance'], 'edit_mode', 'on') && checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			if ($values['instance/name']) {
				//Check to see if an instance of that name already exists
				$sql = "
					SELECT 1
					FROM ". DB_NAME_PREFIX. "plugin_instances
					WHERE name =  '". sqlEscape($values['instance/name']). "'";
				
				if (!$box['key']['duplicate']) {
					$sql .= "
					  AND id != ". (int) $box['key']['id'];
				}
				
				$result = sqlQuery($sql);
				if (sqlNumRows($result)) {
					$box['tabs']['instance']['errors'][] = adminPhrase('A plugin with the name "[[name]]" already exists. Please choose a different name.', array('name' => $values['instance/name']));
				}
			}
		}
		
		break;
	
	
	case 'zenario_alias':
		if (!empty($values['meta_data/alias'])) {
			if (is_array($errors = validateAlias($values['meta_data/alias'], $box['key']['cID'], $box['key']['cType']))) {
				$box['tabs']['meta_data']['errors'] = array_merge($box['tabs']['meta_data']['errors'], $errors);
			}
		}
		
		break;
	
	
	case 'zenario_menu':
		return require funIncPath(__FILE__, 'menu_node.validateAdminBox');
	
	
	case 'zenario_setup_language':
		return require funIncPath(__FILE__, 'setup_language.validateAdminBox');
	
	
	case 'zenario_enable_site':
		if ($values['site/enable_site']) {
			if (checkIfDBUpdatesAreNeeded($andDoUpdates = false)) {
				$box['tabs']['site']['errors'][] =
					adminPhrase('You must apply database updates before you can enable your site.');
			}
			
			if (!checkRowExists('languages', array())) {
				$box['tabs']['site']['errors'][] =
					adminPhrase('You must enable a Language before you can enable your site.');
			} else {
				$tags = '';
				
				$result = getRows(
					'special_pages',
					array('equiv_id', 'content_type'),
					array('logic' => array('create_and_maintain_in_default_language','create_and_maintain_in_all_languages')),
					array('page_type'));
				
				while ($row = sqlFetchAssoc($result)) {
					if (!getRow('content_items', 'visitor_version', array('id' => $row['equiv_id'], 'type' => $row['content_type']))) {
						$tags .= ($tags? ', ' : ''). '"'. formatTag($row['equiv_id'], $row['content_type']). '"';
					}
				}
				
				if ($tags) {
					$box['tabs']['site']['errors'][] =
						adminPhrase('You must publish every Special Page needed by the CMS before you can enable your site. Please publish the following pages: [[tags]].', array('tags' => $tags));
				}
			}
		
		} else {
			if (!$values['site/site_disabled_title']) {
				$box['tabs']['site']['errors'][] =
					adminPhrase('Please enter a browser title.');
			}
			if (!$values['site/site_disabled_message']) {
				$box['tabs']['site']['errors'][] =
					adminPhrase('Please enter a message.');
			}
		}
		
		break;
	
	
	case 'zenario_delete_language':
		exitIfNotCheckPriv('_PRIV_MANAGE_LANGUAGE_CONFIG');
		$details = array();
		
		if (!allowDeleteLanguage($box['key']['id'])) {
			$box['tabs']['site']['errors'][] =
				adminPhrase('You cannot delete the default language of your site.');
		}
		
		if (!$values['site/password']) {
			$box['tabs']['site']['errors'][] =
				adminPhrase('Please enter your password.');
		
		} elseif (!checkPasswordAdmin(session('admin_username'), $details, $values['site/password'])) {
			$box['tabs']['site']['errors'][] =
				adminPhrase('Your password was not recognised. Please check and try again.');
		}
		
		break;
	
	
	case 'zenario_site_reset':
		exitIfNotCheckPriv('_PRIV_RESET_SITE');
		$details = array();
		
		if (!$values['site/password']) {
			$box['tabs']['site']['errors'][] =
				adminPhrase('Please enter your password.');
		
		} elseif (!checkPasswordAdmin(session('admin_username'), $details, $values['site/password'])) {
			$box['tabs']['site']['errors'][] =
				adminPhrase('Your password was not recognised. Please check and try again.');
		}
		
		break;
	
	
	case 'zenario_content':
	case 'zenario_quick_create':
		return require funIncPath(__FILE__, 'content.validateAdminBox');
	
	
	case 'zenario_content_layout':
		
		$box['confirm']['message'] = '';
		
		if (empty($values['layout_id'])) {
			$box['tabs']['layout']['errors'][] = adminPhrase('Please select a layout.');
		
		} else {
			//Are we saving one or multiple items..?
			$cID = $cType = false;
			if (getCIDAndCTypeFromTagId($cID, $cType, $box['key']['id'])) {
				//Just one item in the id
				$cVersion = getLatestVersion($cID, $cType);
				
				//If changing the layout of one content item, warn the administrator if plugins
				//will be moved/lost, but still allow them to do the change.
				$this->validateChangeSingleLayout($box, $cID, $cType, $cVersion, $values['layout/layout_id'], $saving);
				
			} else {
				//Multiple comma-seperated items
				$mrg = array(
					'draft' => 0,
					'hidden' => 0,
					'published' => 0,
					'trashed' => 0);
				
				$tagIds = explode(',', $box['key']['id']);
				foreach ($tagIds as $tagId) {
					if (getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
				
						//If changing the layout of multiple content items, don't warn the administrator if plugins
						//will be moved, but don't allow them to do the change if plugins will be lost.
						$warnings = changeContentItemLayout(
							$cID, $cType, getLatestVersion($cID, $cType), $values['layout/layout_id'],
							$check = true, $warnOnChanges = false
						);
						
						if ($warnings) {
							$box['tabs']['layout']['errors'][] = adminPhrase('Your new layout lacks one or more Banners, Content Summary Lists, Raw HTML Snippets or WYSIWYG editors from the content items\' current layout.');
							return;
						}
						
						if ($status = getContentStatus($cID, $cType)) {
					
							if ($status == 'hidden') {
								++$mrg['hidden'];
					
							} elseif ($status == 'trashed') {
								++$mrg['trashed'];
					
							} elseif (isDraft($status)) {
								++$mrg['draft'];
					
							} else {
								++$mrg['published'];
							}
					
						}
					}
				}
				
				
				$box['confirm']['button_message'] = adminPhrase('Save');
				if ($mrg['published'] || $mrg['hidden'] || $mrg['trashed']) {
					$box['confirm']['button_message'] = adminPhrase('Make new Drafts');
					$box['confirm']['message'] .= '<p>'. adminPhrase('This will create a new Draft for:'). '</p>';
			
					if ($mrg['published']) {
						$box['confirm']['message'] .= '<p> &nbsp; &bull; '. adminPhrase('[[published]] Published Content Item(s)', $mrg). '</p>';
					}
			
					if ($mrg['hidden']) {
						$box['confirm']['message'] .= '<p> &nbsp; &bull; '. adminPhrase('[[hidden]] Hidden Content Item(s)', $mrg). '</p>';
					}
			
					if ($mrg['trashed']) {
						$box['confirm']['message'] .= '<p> &nbsp; &bull; '. adminPhrase('[[trashed]] Archived Content Item(s)', $mrg). '</p>';
					}
			
					if ($mrg['draft']) {
						$box['confirm']['message'] .= '<p>'. adminPhrase('and will update [[draft]] Draft Content Item(s).', $mrg);
					}
				} else {
					$box['confirm']['message'] .= '<p>'. adminPhrase('This will update [[draft]] Draft Content Item(s).', $mrg);
				}
				
				//print_r($box['confirm']);
			}
		}
		
		break;
	
	
	case 'zenario_admin':
		return require funIncPath(__FILE__, 'admin.validateAdminBox');
	
	
	case 'zenario_export_vlp':
		if ($values['export/format'] == 'xlsx'
		 && !extension_loaded('zip')) {
			$box['tabs']['export']['errors'][] =
				adminPhrase('Importing or exporting .xlsx files requires the php_zip extension. Please ask your server administrator to enable it.');
		}
		
		break;
	
	
	case 'zenario_create_vlp':
		if (!$values['details/language_id']) {
			$box['tabs']['details']['errors'][] = adminPhrase('Please enter a Language Code.');
		
		} elseif ($values['details/language_id'] != preg_replace('/[^a-z0-9_-]/', '', $values['details/language_id'])) {
			$box['tabs']['details']['errors'][] = adminPhrase('The Language Code can only contain lower-case letters, numbers, underscores or hyphens.');
		
		} elseif (checkIfLanguageCanBeAdded($values['details/language_id'])) {
			$box['tabs']['details']['errors'][] = adminPhrase('The Language Code [[id]] already exists', array('id' => $values['details/language_id']));
		}
		
		break;
	
	case 'zenario_document_folder':
		
		if ($values['details/folder_name'] == "") {
			$box['tabs']['details']['errors'][] = adminPhrase('You must give the folder a name.');
		}
		$folderId = $box['key']['id'];
		if(!$folderId ){
			//create a new folder
			$folderNameSaved=getRow('documents', 'folder_name', array('folder_name' => $values['details/folder_name'], 'type' => 'folder', 'folder_id' => 0));
			if ($folderNameSaved){
				$box['tabs']['details']['errors'][] = adminPhrase('The folder name “[[folder_name]]” is already taken. Please choose a different name.', array('folder_name' => $values['details/folder_name']));
			}
		}else{
			//create a subfolder
			$subFolderNameSaved=getRow('documents', 'folder_name', array('folder_name' => $values['details/folder_name'], 'type' => 'folder','folder_id' => $folderId));
			//$folderParentName=getRow('documents', 'folder_name', array('type' => 'folder','id' => $folderId));
			if ($subFolderNameSaved){
				$box['tabs']['details']['errors'][] = adminPhrase('The folder name “[[subfolder_name]]” is already taken. Please choose a different name.', array('subfolder_name' => $values['details/folder_name']));
				//$box['tabs']['details']['errors'][] = adminPhrase('The folder name “[[subfolder_name]]” is already taken in the selected folder "[[folder_parent_name]]". Please choose a different name.', array('subfolder_name' => $values['details/folder_name'],'folder_parent_name'=>$folderParentName));
			}
		}
		
		break;
		
	case 'zenario_migrate_old_documents':
		if (!checkRowExists('documents', array('type' => 'folder', 'id' => $values['details/folder']))) {
			$box['tabs']['details']['errors'][] = adminPhrase('You must select a folder for the documents.');
		}
		break;
	

	
	case 'zenario_document_move':
		
		if (!$values['details/move_to'] && !$values['details/move_to_root']) {
			$box['tabs']['details']['errors'][] = adminPhrase('You must select a target folder.');
		}
		$ids = explode(',', $box['key']['id']);
		if (in_array($values['details/move_to'], $ids)) {
			$box['tabs']['details']['errors'][] = adminPhrase('You can not move a folder inside itself.');
		}
		
		//avoid duplicate folder
		//folder validation
		$documentId = $box['key']['id'];
		$isfolder=getRow('documents', 'type', array('type' => 'folder','id' => $documentId));
		if ($isfolder){
			$moveToRoot = $values['details/move_to_root'];
			$moveToFolderId = $values['details/move_to'];
	
			$folderId = $box['key']['id'];
			$folderName=getRow('documents', 'folder_name', array('type' => 'folder','id' => $folderId));
	
			if($moveToRoot){
				$parentfolderId=getRow('documents', 'folder_id', array('type' => 'folder','id' => $folderId));
				if (!$parentfolderId){
					$box['tabs']['details']['errors'][] = adminPhrase('The folder “[[folder_name]]” is already in root.', array('folder_name' => $folderName));
				}else{
					$folderNameSaved=getRow('documents', 'folder_name', array('folder_name' => $folderName, 'type' => 'folder','folder_id' => 0));
					if ($folderNameSaved){
						$box['tabs']['details']['errors'][] = adminPhrase('The folder name “[[folder_name]]” is already taken. Please choose a different name.', array('folder_name' => $folderName));
						//$box['tabs']['details']['errors'][] = adminPhrase('The folder name “[[subfolder_name]]” is already taken in the selected folder "[[folder_parent_name]]". Please choose a different name.', array('subfolder_name' => $values['details/folder_name'],'folder_parent_name'=>$folderParentName));
					}
				}
			}elseif($moveToFolderId){
				$parentfolderId=getRow('documents', 'folder_id', array('type' => 'folder','id' => $folderId));
				if ($moveToFolderId == $parentfolderId){
					$box['tabs']['details']['errors'][] = adminPhrase('The folder “[[subfolder_name]]” is already in the target folder selected.', array('subfolder_name' => $folderName));
				}else{
					$folderNameSaved=getRow('documents', 'folder_name', array('folder_name' => $folderName, 'type' => 'folder','folder_id' => $folderId));
					if ($folderNameSaved){
						$box['tabs']['details']['errors'][] = adminPhrase('The folder name “[[subfolder_name]]” is already taken. Please choose a different name.', array('subfolder_name' => $folderName));
						//$box['tabs']['details']['errors'][] = adminPhrase('The folder name “[[subfolder_name]]” is already taken in the selected folder "[[folder_parent_name]]". Please choose a different name.', array('subfolder_name' => $values['details/folder_name'],'folder_parent_name'=>$folderParentName));
					}
				}
	
			}
		}else{
			//it is not a folder.
		}
	
		break;
	
	case 'zenario_document_upload':
	
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
			$box['tabs']['upload_document']['errors'][] = adminPhrase('You cannot upload documents with the same name and extension in a directory');
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
			WHERE folder_id = ".$parentfolderId;
			
		$result = sqlQuery($sql);
		while($row = sqlFetchAssoc($result)) {
			$fileNameList[] = $row['filename'];
		}
		
		if ($values['upload_document/document__upload'] && isset($fileNameList) && $fileNameList){
			foreach ($documentNameList as $name){
				if (array_search($name, $fileNameList) !== false) {
					$nameDetails = explode(".",$name);
					$box['tabs']['upload_document']['errors'][] = adminPhrase('A file named "[[filename]]" with extension ".[[extension]]" already exists in this directory!', array('filename' => $nameDetails[0],'extension'=>$nameDetails[1]));
					break;
				}
			}
		}
		break;
	
	case 'zenario_document_properties':
		$documentId = $box['key']['id'];
		$parentfolderId=getRow('documents', 'folder_id', array('id' => $documentId));
		$newDocumentName = trim($values['details/document_name']);
		
		if (!$newDocumentName ){
			$box['tabs']['details']['errors'][] = adminPhrase('Please enter a filename.');
		} else {
			// Stop forward slashes being used in filenames
			$slashPos = strpos($newDocumentName, '/');
			if ($slashPos !== false) {
				$box['tabs']['details']['errors'][] = adminPhrase('Your filename cannot contain forward slashes (/).');
			}
		}
		
		//$fileNameAlreadyExists=checkRowExists('documents', array('type' => 'file','folder_id' => $parentfolderId,'filename'=>$newDocumentName));
		
		
		$sql =  "
			SELECT id
			FROM ".DB_NAME_PREFIX."documents
			WHERE type = 'file' 
			AND folder_id =".$parentfolderId."
			AND filename = '".$newDocumentName."' 
			AND id != ". $documentId;
		
		$documentIdList = array();
		$result = sqlSelect($sql);
		while($row = sqlFetchAssoc($result)) {
				$documentIdList[] = $row;
		}
		$numberOfIds = count($documentIdList);
		
		if ($numberOfIds > 0){
			$box['tabs']['details']['errors'][] = adminPhrase('The filename “[[folder_name]]” is already taken. Please choose a different name.', array('folder_name' => $newDocumentName));
		}
		break;
		
		
	case 'zenario_document_rename':
	
		if ($values['details/document_name'] == "") {
			$box['tabs']['details']['errors'][] = adminPhrase('Please enter a name.');
		}else{
			$documentId = $box['key']['id'];
			$isfolder=getRow('documents', 'type', array('type' => 'folder','id' => $documentId));
			$parentfolderId=getRow('documents', 'folder_id', array('type' => 'folder','id' => $documentId));
			$newDocumentName = trim($values['details/document_name']);
			if ($isfolder){
				$folderNameAlreadyExists=checkRowExists('documents', array('type' => 'folder','folder_id' => $parentfolderId,'folder_name'=>$newDocumentName));
				$sql =  "
					SELECT id
					FROM ".DB_NAME_PREFIX."documents
					WHERE type = 'folder' 
					AND folder_id =".$parentfolderId."
					AND folder_name = '".$newDocumentName."' 
					AND id != ". $documentId;
	
				$documentIdList = array();
				$result = sqlSelect($sql);
				while($row = sqlFetchAssoc($result)) {
						$documentIdList[] = $row;
				}
				$numberOfIds = count($documentIdList);
	
				if ($numberOfIds > 0){
					$box['tabs']['details']['errors'][] = adminPhrase('The folder name “[[folder_name]]” is already taken. Please choose a different name.', array('folder_name' => $newDocumentName));
				}
			}
		}
	break;
	
	case 'zenario_file_type':
		if (preg_replace('/[a-zA-Z0-9_\\.-]/', '', $values['details/type'])) {
			$box['tabs']['details']['errors'][] = adminPhrase('The Extension must not contain any special characters.');
		
		} elseif (checkDocumentTypeIsExecutable($values['details/type'])) {
			$box['tabs']['details']['errors'][] = adminPhrase('You may not register an executable file type.');
		
		} elseif (checkRowExists('document_types', array('type' => $values['details/type']))) {
			$box['tabs']['details']['errors'][] = adminPhrase('This extension is already registered in the CMS.');
		}
		
		break;
	
	
	case 'zenario_content_type_details':
		if (!$values['details/default_layout_id'] || !($template = getTemplateDetails($values['details/default_layout_id']))) {
			$box['tabs']['details']['errors'][] = adminPhrase('Please select a default layout.');
		
		} elseif ($template['status'] != 'active') {
			$box['tabs']['details']['errors'][] = adminPhrase('The default layout must be an active layout.');
		}
		
		break;
	
	
	case 'site_settings':
		return require funIncPath(__FILE__, 'site_settings.validateAdminBox');
	
}

return false;