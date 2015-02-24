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
	case 'zenario__menu/panels/menu_nodes':
		return require funIncPath(__FILE__, 'menu_nodes.handleOrganizerPanelAJAX');

	
	case 'zenario__content/panels/slots':
		//Most of the logic to handle changing slots is in handleAJAX(), so call that
		
		//A little hack: set the requests up in the same way that handleAJAX() expects
		$_GET['slotName'] = $_REQUEST['slotName'] = $_POST['slotName'] = $ids;
		if (request('addPluginInstance') && $ids2) {
			$_GET['addPluginInstance'] = $_REQUEST['addPluginInstance'] = $_POST['addPluginInstance'] = $ids2;
		}
		
		$this->handleAJAX();
		return;
	
	
	case 'zenario__layouts/panels/layouts':
		//Delete a template if it is not in use
		if (post('delete') && checkPriv('_PRIV_EDIT_TEMPLATE')) {
			foreach (explode(',', $ids) as $id) {
				if (!checkRowExists('content_types', array('default_layout_id' => $id))
				 && !checkRowExists('versions', array('layout_id' => $id))) {
					deleteLayout($id, true);
				}
			}
			checkForChangesInCssJsAndHtmlFiles($forceScan = true);
		
		//Archive a template
		} elseif (post('archive') && checkPriv('_PRIV_EDIT_TEMPLATE')) {
			foreach (explode(',', $ids) as $id) {
				if (!checkRowExists('content_types', array('default_layout_id' => $id))) {
					updateRow('layouts', array('status' => 'suspended'), $id);
				}
			}
		
		//Restore a template
		} elseif (post('restore') && checkPriv('_PRIV_EDIT_TEMPLATE')) {
			foreach (explode(',', $ids) as $id) {
				updateRow('layouts', array('status' => 'active'), $id);
			}
		}
		
		break;
	
	
	case 'zenario__layouts/panels/skins':
		if (post('make_default') && checkPriv('_PRIV_EDIT_TEMPLATE_FAMILY')) {
			updateRow('template_families', array('skin_id' => $ids), decodeItemIdForStorekeeper(request('refiner__template_family')));
		}
		
		break;
	
	
	case 'zenario__content/panels/content':
	case 'zenario__content/panels/chained':
	case 'zenario__content/panels/language_equivs':
		return require funIncPath(__FILE__, 'content.handleOrganizerPanelAJAX');
	
	case 'zenario__content/panels/documents':
		if (post('reorder') || post('hierarchy')) {
			//Loop through each moved files
			//var_dump($_POST);
			foreach (explode(',', $ids) as $id) {
				//Look up the current id, folder_id and ordinal
				if ($file = getRow('documents', array('id', 'folder_id', 'ordinal'), $id)) {
					$cols = array();
					
					//Update the ordinal if it is different
					if (isset($_POST['ordinal__'. $id]) && $_POST['ordinal__'. $id] != $file['ordinal']) {
						$cols['ordinal'] = $_POST['ordinal__'. $id];
					}
	
					//Update the folder id if it is different, and remember that we've done this
					if (isset($_POST['parent_id__'. $id]) && $_POST['parent_id__'. $id] != $file['folder_id']) {
						$cols['folder_id'] = $_POST['parent_id__'. $id];
						$folder = getRow('documents', array('id', 'type'), $_POST['parent_id__'. $id]);
						if ($folder['type'] == "file") {
							echo '<!--Message_Type:Error-->';
							echo adminPhrase('Files may not be moved under other files, files can only be placed under folders.');
							exit;
						}
					}
					
					
					updateRow('documents', $cols, $id);
				}
			}
		} elseif (post('upload')) {
			if (!checkDocumentTypeIsAllowed($_FILES['Filedata']['name'])) {
				echo
					adminPhrase('You must select a known file format, for example .doc, .docx, .jpg, .pdf, .png or .xls.'), 
					"\n\n",
					adminPhrase('To add a file format to the known file format list, go to "Configuration -> Uploadable file types" in Organizer.'),
					"\n\n",
					adminPhrase('Please also check that your filename does not contain any of the following characters: ' . "\n" . '\\ / : * ? " < > |');
				exit;
			}
			
			exitIfUploadError();
			$file_id = addFileToDatabase('hierarchial_file', $_FILES['Filedata']['tmp_name'], preg_replace('/([^.a-z0-9\s_]+)/i', '-',$_FILES['Filedata']['name']), false, false, true);
			$existingFile = getRow('documents', array('id'), array('file_id' => $file_id));
			if ($existingFile) {
				echo "This file has already been uploaded to the files directory!";
				return $existingFile['id'];
			}
			
			$documentProperties = array(
				'type' =>'file',
				'file_id' => $file_id,
				'folder_id' => 0,
				'ordinal' => 0);
				
			$extraProperties = self::addExtractToDocument($file_id);
			$documentProperties = array_merge($documentProperties, $extraProperties);
			
			if ($ids) {
				$documentProperties['folder_id'] = $ids;
			}
			
			if ($documentId = insertRow('documents', $documentProperties)) {
				self::processDocumentRules($documentId);
			}
			
			return $documentId;
			
		} elseif (post('rescan')) {
			$file_id = getRow('documents', 'file_id', array('id' => $ids));
			$documentProperties = self::addExtractToDocument($file_id);
			if (empty($documentProperties['extract']) || empty($documentProperties['thumbnail_id'])) {
				echo "<!--Message_Type:Error-->";
			} else {
				echo "<!--Message_Type:Success-->";
			}
			if (empty($documentProperties['extract'])) {
				echo "<p>Unable to update document text extract.</p>";
			} else {
				echo "<p>Successfully updated document text extract.</p>";
			}
			if (empty($documentProperties['thumbnail_id'])) {
				echo "<p>Unable to update document image.</p>";
			} else {
				echo "<p>Successfully updated document image.</p>";
			}
			if (!((plainTextExtract(moduleDir('zenario_common_features', 'fun/test_files/test.doc'), $extract))
				 && ($extract == 'Test'))) {
				echo "<p>antiword or pdftotext do not appear to be working.</p>";
			}
			if (!createPpdfFirstPageScreenshotPng(moduleDir('zenario_common_features', 'fun/test_files/test.pdf'))) {
				echo "<p>ghostscript does not appear to be working.</p>";
			}
			updateRow('documents', $documentProperties, array('id' => $ids));
			
		}elseif(post('rescanText')){ 
			$file_id = getRow('documents', 'file_id', array('id' => $ids));
			$documentProperties = self::addExtractToDocument($file_id);
			if (empty($documentProperties['extract'])) {
				echo "<!--Message_Type:Error-->";
			} else {
				echo "<!--Message_Type:Success-->";
			}
			if (empty($documentProperties['extract'])) {
				echo "<p>Unable to update document text extract.</p>";
			} else {
				echo "<p>Successfully updated document text extract.</p>";
			}
			if (!((plainTextExtract(moduleDir('zenario_common_features', 'fun/test_files/test.doc'), $extract))
				 && ($extract == 'Test'))) {
				echo "<p>antiword or pdftotext do not appear to be working.</p>";
			}
			if (!createPpdfFirstPageScreenshotPng(moduleDir('zenario_common_features', 'fun/test_files/test.pdf'))) {
				echo "<p>ghostscript does not appear to be working.</p>";
			}
			updateRow('documents', array('extract'=>$documentProperties['extract']), array('id' => $ids));
		}elseif (post('autoset')) {
			self::processDocumentRules($ids);
			
		} elseif (post('dont_autoset_metadata')) {
			foreach (explode(',', $ids) as $id) {
				updateRow('documents', array('dont_autoset_metadata' => 1), $id);
			}
			
		} elseif (post('allow_autoset_metadata')) {
			foreach (explode(',', $ids) as $id) {
				updateRow('documents', array('dont_autoset_metadata' => 0), $id);
			}
		
		//Remove all of the custom data from a document
		} elseif (post('remove_metadata')) {
			if ($dataset = getDatasetDetails('documents')) {
				foreach (explode(',', $ids) as $id) {
					deleteRow('documents_custom_data', $id);
					deleteRow('custom_dataset_values_link', array('dataset_id' => $dataset['id'], 'linking_id' => $id));
				}
			}
			
		} elseif (post('delete')) {
			foreach (explode(',', $ids) as $id) {
				self::deleteHierarchicalDocument($id);
			}
		} elseif (post('generate_public_link')){
			foreach (explode(',', $ids) as $id) {
				echo "<!--Message_Type:Success-->";
				$fileId = getRow('documents', 'file_id', $id);
				$file = getRow('files', 
								array('id', 'filename', 'path', 'created_datetime'),
								$fileId);
				if($file['filename']) {
					$symPath = CMS_ROOT . 'public' . '/' . $file['path'] . '/' . $file['filename'];
					$symFolder =  CMS_ROOT . 'public' . '/' . $file['path'];
					$frontLink = 'public' . '/' . $file['path'] . '/' . $file['filename'];
					if (!windowsServer() && ($path = docstoreFilePath($file['id'], false))) {
						if (!file_exists($symPath)) {
							if(!file_exists($symFolder)) {
								mkdir($symFolder);
							}
							symlink($path, $symPath);
						}
				
						$baseURL = absCMSDirURL();
						$message="<h3>The hyperlinks to your document are shown below:</h3>";
						
						$fullLink = $baseURL.$frontLink;
						$normalLink =$frontLink;
				
						$link = $message."Full hyperlink: <br>" . "<input type='text' style='width: 488px;' value = '".$fullLink."'/><br>Internal hyperlink:<br><input type='text' style='width: 488px;' value = '". $normalLink . "'/>";
				
				
						echo $link;
					}
				}
			}
		}elseif(post('delete_public_link')){
		
				foreach (explode(',', $ids) as $id) {
				echo "<!--Message_Type:Success-->";
				$fileId = getRow('documents', 'file_id', $id);
				$file = getRow('files', 
								array('id', 'filename', 'path', 'created_datetime'),
								$fileId);
				if($file['filename']) {
					$symPath = CMS_ROOT . 'public' . '/' . $file['path'] . '/' . $file['filename'];
					$symFolder =  CMS_ROOT . 'public' . '/' . $file['path'];
					$frontLink = 'public' . '/' . $file['path'] . '/' . $file['filename'];
					if (!windowsServer() && ($path = docstoreFilePath($file['id'], false))) {
							if(is_link($symPath)) {
								$target = readlink($symPath);
								unlink($symPath);
								rmdir($symFolder);
								echo "Public link was deleted successfully.";
							}else{
							echo 'Does not have public link to delete';
							}
					}
				}
			}
		
		
		
		}
		
		break;
		
	case 'zenario__content/panels/document_tags':
		if (post('delete')) {
			foreach (explode(',', $ids) as $id) {
				self::deleteDocumentTag($id);
			}
		}
		break;
	

	case 'editor_temp_file':
	case 'zenario__content/panels/email_images_for_email_templates':
	case 'zenario__content/panels/email_images_shared':
	case 'zenario__content/panels/inline_images_for_content':
	case 'zenario__content/panels/inline_images_for_reusable_plugins':
	case 'zenario__content/panels/inline_images_shared':
		
		$key = false;
		$usage = 'inline';
		$privCheck = checkPriv('_PRIV_MANAGE_MEDIA');
		
		if ($path == 'editor_temp_file') {
			$usage = 'editor_temp_file';
			$privCheck = true;
			
		} elseif (in($path, 'zenario__content/panels/inline_images_for_content')) {
			if (!$content = getRow('content', array('id', 'type', 'admin_version'), array('tag_id' => $refinerId))) {
				exit;
			}
			
			if (post('make_sticky') && checkPriv('_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE')) {
				updateVersion($content['id'], $content['type'], $content['admin_version'], array('sticky_image_id' => $ids));
				syncInlineFileContentLink($content['id'], $content['type'], $content['admin_version']);
				return;
			
			} elseif (post('make_unsticky') && checkPriv('_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE')) {
				updateVersion($content['id'], $content['type'], $content['admin_version'], array('sticky_image_id' => 0));
				syncInlineFileContentLink($content['id'], $content['type'], $content['admin_version']);
				return;
			}
			
			$privCheck = checkPriv('_PRIV_EDIT_DRAFT', $content['id'], $content['type']);
			$key = array(
				'foreign_key_to' => 'content',
				'foreign_key_id' => $content['id'],
				'foreign_key_char' => $content['type'],
				'foreign_key_version' => $content['admin_version']);
		
		} elseif ($path == 'zenario__content/panels/email_images_shared') {
			$usage = 'email';
		
		} elseif ($path == 'zenario__content/panels/email_images_for_email_templates') {
			$usage = 'email';
			
			if (!inc('zenario_email_template_manager')) {
				exit;
			}
		
			$privCheck = checkPriv('_PRIV_MANAGE_EMAIL_TEMPLATE');
			if ($refinerId && $details = zenario_email_template_manager::getTemplateByCode($refinerId)) {
				$key = array(
					'foreign_key_to' => 'email_template',
					'foreign_key_id' => $details['id'],
					'foreign_key_char' => $details['code']);
			
			} else {
				$key = array(
					'foreign_key_to' => 'email_template',
					'foreign_key_id' => 0,
					'foreign_key_char' => '0');
			}
		
		} elseif (in($path, 'zenario__content/panels/inline_images_for_reusable_plugins')) {
			$key = array(
				'foreign_key_to' => 'reusable_plugin');
		}
		
		return require funIncPath(__FILE__, 'media.handleOrganizerPanelAJAX');
	
	
	case 'zenario__content/panels/categories':
		if (post('delete') && checkPriv('_PRIV_MANAGE_CATEGORY')) {
			foreach (explode(',', $ids) as $id) {
				$this->deleteCategory($id);
			}
		}
		
		break;
	
	
	case 'zenario__modules/panels/modules':
		return require funIncPath(__FILE__, 'modules.handleOrganizerPanelAJAX');

	
	case 'zenario__modules/panels/plugins':
		if (post('delete') && checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			foreach (explode(',', $ids) as $id) {
				if (!checkInstancesUsage($id)) {
					deletePluginInstance($id);
				}
			}
		}
		
		break;

	
	case 'zenario__languages/panels/languages':
		if (post('import') && checkPriv('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
			
			if (documentMimeType($_FILES['Filedata']['name']) == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
			 && !extension_loaded('zip')) {
				echo adminPhrase('Importing or exporting .xlsx files requires the php_zip extension. Please ask your server administrator to enable it.');
				exit;
			
			} else {
				$languageId = $ids;
				$numberOf = importVisitorLanguagePack($_FILES['Filedata']['tmp_name'], $languageId, false, false, false, $_FILES['Filedata']['name']);
				$this->languageImportResults($numberOf);
				return $languageId;
			}
		
		} elseif (post('delete') && checkPriv('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
			$sql = "
				DELETE
				FROM " . DB_NAME_PREFIX . "visitor_phrases
				WHERE language_id = '" . sqlEscape($_POST['id']) . "'
				  AND '" . sqlEscape($_POST['id']) . "' NOT IN (
					SELECT id
					FROM " . DB_NAME_PREFIX . "languages
				)";
			sqlQuery($sql);
		}
		
		break;

	
	case 'zenario__languages/panels/phrases':
	case 'zenario__languages/nav/vlp/vlp_chained/panel':
		return require funIncPath(__FILE__, 'vlp.handleOrganizerPanelAJAX');

	
	case 'zenario__users/panels/administrators':
		if (post('trash') && checkPriv('_PRIV_DELETE_ADMIN')) {
			foreach (explode(',', $ids) as $id) {
				if ($id != session('admin_userid')) {
					deleteAdmin($id);
				}
			}
		
		} elseif (post('restore') && checkPriv('_PRIV_DELETE_ADMIN')) {
			foreach (explode(',', $ids) as $id) {
				deleteAdmin($id, true);
			}
		}
		
		break;
		
		
	case 'zenario__administration/panels/file_types':
		if (post('delete') && checkPriv('_PRIV_EDIT_CONTENT_TYPE')) {
			foreach (explode(',', $ids) as $id) {
				deleteRow('document_types', array('type' => decodeItemIdForStorekeeper($id), 'custom' => 1));
			}
		}
		

		//****
		
		//******
		
		break;
		



	case 'zenario__administration/panels/backups':
		//Check to see if we can proceed
		if ($errors = initialiseBackupFunctions(false)) {
			exit;
		}
		
		if ($ids) {
			$filename = setting('backup_dir') . '/'. decodeItemIdForStorekeeper($ids);
		}
		
		if (post('create') && checkPriv('_PRIV_BACKUP_SITE')) {
			//Create a new file in the backup directory, and write the backup into it
			$backupPath = setting('backup_dir'). '/' . ($fileName = generateFilenameForBackups());
			
			$g = gzopen($backupPath, 'wb');
			createDatabaseBackupScript($g);
			gzclose($g);
			
			return encodeItemIdForStorekeeper($fileName);
		
		} elseif (post('delete') && checkPriv('_PRIV_RESTORE_SITE')) {
			unlink($filename);
		
		} elseif (post('upload_and_restore') && checkPriv('_PRIV_RESTORE_SITE')) {
			exitIfUploadError();
			
			//Call restoreDatabaseFromBackup on the temp file
			$failures = array();
			if (restoreDatabaseFromBackup(
							$_FILES['Filedata']['tmp_name'],
							//Attempt to check whether gzip compression has not been used
							strtolower(substr($_FILES['Filedata']['name'], -3)) != '.gz',
							DB_NAME_PREFIX, $failures
			)) {
				echo '<!--Reload_Storekeeper-->';
			} else {
				foreach ($failures as $text) {
					echo $text;
				}
			}
		
		} elseif (post('restore') && checkPriv('_PRIV_RESTORE_SITE')) {
			//Restore a backup from the file system
			$failures = array();
			if (restoreDatabaseFromBackup(
					$filename,
					//Attempt to check whether gzip compression has been used, or if this is a plain sql file
					strtolower(substr($filename, -3)) != '.gz',
					DB_NAME_PREFIX, $failures
			)) {
				echo '<!--Reload_Storekeeper-->';
			} else {
				foreach ($failures as $text) {
					echo $text;
				}
			}
		}
		
		
		break;
		
	//For debug
	default:
		print_r($_GET);
		print_r($_POST);
}

return false;