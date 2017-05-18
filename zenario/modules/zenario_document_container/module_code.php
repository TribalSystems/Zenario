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

class zenario_document_container extends module_base_class {
	
	public function init(){
		return true;
	}
	
	public function showSlot() {
		$userId = userId();
		$documentId = $this->setting('document_source');
		$mergeFields = array();
		
		// Standard documents
		if ($this->setting('container_mode') == 'documents') {
			if (checkPriv()) {
				$this->showDocumentPluginAdminInfo($documentId);
			}
			$mergeFields = $this->getDocumentContainerDocuments($documentId);
			
			if ($mergeFields === false) {
				return false;
			}
		// Private user documents
		} else {
			if (checkPriv()) {
				$this->showUserDocumentPluginAdminInfo();
			}
			$mergeFields = $this->getDocumentContainerUserDocuments($userId);
		}
		
		$mergeFields['Title_Tags'] = $this->setting('title_tags') ? $this->setting('title_tags') : 'h1';
		$mergeFields['main_folder_title'] = false;
		if ($this->setting('show_folder_name_as_title')) {
			$mergeFields['main_folder_title'] = getRow('documents', 'folder_name', $documentId);
		}
		
		// Display the Plugin
		$this->framework('Outer', $mergeFields);
	}
	
	private function showDocumentPluginAdminInfo($documentId) {
		if ($documentId && $document = getRow('documents', array('file_id', 'type', 'folder_name','filename','title'), $documentId)) {
			if ($document['type'] == 'file') {
				if ($file = getRow('files', array('id', 'filename'), $document['file_id'])) {
					 $showingText = 'Document ' . '"' . $document['filename'] . '"';
				} else {
					$showingText = 'Missing document with file id "' . $document['file_id'] . '"';
				}
				$pluginSettingsForEditView = array("Showing: " => $showingText);
			} else {
				if ($this->setting('show_folders_in_results')) {
					$showingText = 'Documents and sub-folder names as headings in folder "' .  $document['folder_name'] . '"';
				} else {
					$showingText = 'Documents in folder "' .  $document['folder_name'] . '"';
				}
				switch ($this->setting('show_files_in_folders')) {
					case 'all':
						$showingText .= ", documents from all sub-folders (all levels) will be shown";
						break;
					case 'sub-folders':
						$showingText .= ", documents in selected folders sub-folders (1 level down) will be shown";
						break;
					case 'folder':
						$showingText .= ", documents in sub-folders will not be shown";
						break;
				}
				if ($this->setting('show_folder_name_as_title')) {
					$showingText .= ", select folder name will be shown as main title";
				}
				if (setting('enable_document_tags') && $this->setting('document_tags')) {
					$documentTagText = "Only showing documents with one of the following tags:";
					$documentTagsArray = explode(',', $this->setting('document_tags'));
					$tagNamesArray = getRowsArray('document_tags', 'tag_name', array('id' => $documentTagsArray));
					foreach ($tagNamesArray as $tagName) {
						$documentTagText .= " " . $tagName . ",";
					}
					$documentTagText = rtrim($documentTagText, ",");
					$pluginSettingsForEditView = array("Showing: " => $showingText, "Tag filter:" => $documentTagText);
				} else {
					$pluginSettingsForEditView = array("Showing: " => $showingText);
				}
			}
		} else {
			$showingText = 'No document or folder selected.';
			if ($documentId) {
				$showingText = 'Missing document with id "' . $documentId . '"';
			}
			$pluginSettingsForEditView = array("Showing: " => $showingText);
		}
		
		//use $this->zAPISettings for array of plugin settings
		$this->twigFramework(
			array(
				'Heading' => 'This is a Document Container plugin', 
				'Sub_Heading' => 'Automatically shows a list of documents according to its settings (blank if nothing set):', 
				'Settings' => $pluginSettingsForEditView
			), 
			false, 
			false, 
			'zenario/frameworks/show_plugin_settings.twig.html'
		);
	}
	
	private function showUserDocumentPluginAdminInfo() {
		$this->twigFramework(
			array(
				'Heading' => 'This is a Document container.', 
				'Sub_Heading' => 'This slot is auto populated on the basic:', 
				'Settings' => array(
					'Showing: ' => 'User documents for logged in user'
				)
			), 
			false, 
			false, 
			'zenario/frameworks/show_plugin_settings.twig.html'
		);
	}
	
	public function getDocumentContainerDocuments($documentId) {
		$mergeFields = array();
		
		if (!$documentId) {
			$mergeFields['error'] = 'no_files';
			return $mergeFields;
		}
		
		$document = getRow(
			'documents', 
			array(
				'id', 
				'file_id', 
				'type', 
				'thumbnail_id', 
				'folder_name',
				'filename', 
				'privacy', 
				'file_datetime', 
				'title'
			),
			$documentId
		);
		
		// Return false if document does not exist
		if (!$document) {
			return false;
		}
		
		if ($document['type'] == 'file') {
			
			$childFiles = array();
			if ($childFiles = $this->getFilesInFolder(false, $documentId)) {
				$childFiles =  $this->addMergeFields($childFiles, $level = 1);
			}
			if (isset($childFiles[$documentId])){
				$mergeFields = $childFiles[$documentId];
			}
			
		} elseif ($document['type'] == 'folder') {
		
			$childFiles = array();
			$level = 1;
			
			// Get top level files
			if ($childFiles = $this->getFilesInFolder($documentId)) {
				$childFiles =  $this->addMergeFields($childFiles, $level);
			}
			
			// Get folders
			if ($this->setting('show_files_in_folders') != 'folder') {
				if ($childFolders = static::getFoldersInFolder($documentId)) {
					$this->addFilesToDocumentArray($childFiles, $childFolders, $level);
				}
			}
			
			if ($this->setting('offer_download_as_zip')) {
				$ids = array_keys($childFiles);
				
				$mergeFields['Download_Archive'] = true;
				if (get('build') == $this->instanceId) {
					$this->showLinkToFile($mergeFields, $document);
				} else {
					$this->showLinkToDownloadPage($mergeFields, $ids);
				}
			} else {
				$mergeFields['Documents'] = $childFiles;
			}
		}
		return $mergeFields;
	}
	
	private function getDocumentContainerUserDocuments($userId) {
		$mergeFields = array();
		
		if (!$userId) {
			$mergeFields['error'] = 'no_user';
			return $mergeFields;
		}
		
		$documents = getRowsArray(
			ZENARIO_USER_DOCUMENTS_PREFIX . 'user_documents', 
			array(
				'id', 
				'type', 
				'file_id', 
				'folder_name', 
				'thumbnail_id', 
				'document_datetime', 
				'title'
			), 
			array('user_id' => $userId), 
			'ordinal'
		);
		
		if (!$documents) {
			$mergeFields['error'] = 'no_files';
			return $mergeFields;
		}
		
		if ($this->setting('offer_download_as_zip')) {
			$ids = array_keys($documents);
			$mergeFields['Download_Archive'] = true;
			if (get('build') == $this->instanceId) {
				$this->showLinkToFile($mergeFields);
			} else {
				$this->showLinkToDownloadPage($mergeFields, $ids, $userId);
			}
		} else {
			$fields = getDatasetFieldsDetails(ZENARIO_USER_DOCUMENTS_PREFIX. 'user_documents');
			foreach ($documents as &$document) {
			
				$privacyLevel = getRow('translation_chains', 'privacy', array('equiv_id' => cms_core::$equivId, 'type' => cms_core::$cType));
				$file = getRow('files', array('id', 'filename', 'path', 'created_datetime', 'short_checksum', 'size'), $document['file_id']);
				
				$link = getDocumentFrontEndLink($documentId, true);
				$document['Document_Type'] =  'file';
				$document['Document_Link'] = $link;
				$document['Document_created_datetime'] = $file['created_datetime'];
				$document['Document_Mime'] = str_replace('/', '_', documentMimeType($link));
				$document['Document_Title'] = $file['filename'];
				$document['Document_Link_Text'] = htmlspecialchars($file['filename']);
				$document['Document_Level'] = 1;
				
				if ($document['thumbnail_id'] && $this->setting('show_thumbnails')) {
					$thumbnailHtml= static::createThumbnailHtml($document['thumbnail_id'], $this->setting('width'), $this->setting('height'), $this->setting('canvas'));
					$document['Thumbnail'] = $thumbnailHtml;
				} else {
					$document['Thumbnail'] = false;
				}
			
				if($this->setting('show_title')) {
					$document['Title_Exists'] = true;
				}
				
				if ($this->setting('show_file_size') && $file['size']) {
					$fileSize = fileSizeConvert($file['size']);
					$document['File_Size'] = $fileSize;
				}
				if ($this->setting('show_upload_date') && $document['document_datetime']) {
					$uploadDate = formatDateTimeNicely($document['document_datetime'], '_MEDIUM');
					$document['Upload_Date'] = $this->phrase('Uploaded: [[date]]', array('date' => $uploadDate));
				}
				
			
				$documentCustomData = getRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents_custom_data', true, $document['id']);
				
				foreach ($fields as $fieldName => &$field) {
					$value = $displayValue = '';
				
					if ($documentCustomData && isset($documentCustomData[$fieldName])) {
						$value = $displayValue = $documentCustomData[$fieldName];
					
						switch ($field['type']) {
							case 'radios':
							case 'select':
								$displayValue = getDatasetFieldValueLabel($value);
								break;
							case 'centralised_radios':
							case 'centralised_select':
								if (!isset($field['lov'])) {
									$field['lov'] = getDatasetFieldLOV($field);
								}
								if (isset($field['lov'][$value])) {
									$displayValue = $field['lov'][$value];
								}
								break;
						}
				
					} elseif ($field['type'] == 'checkboxes') {
						//Skip checkboxes for now...
					}
				
					$document[$fieldName] = $displayValue;
				}
			}
			$mergeFields['Documents'] = $documents;
		}
		return $mergeFields;
	}
	
	private function getDocumentThumbnail($documentThumbnailId, $fileId) {
		$thumbnailId = false;
		if ($this->setting('show_thumbnails')) {
			if ($documentThumbnailId) {
				$thumbnailId = $documentThumbnailId;
			} else {
				$mimeType = getRow('files', 'mime_type', $fileId);
				if (isImageOrSVG($mimeType)) {
					$thumbnailId = $fileId;
				}
			}
		}
		$thumbnailHTML = false;
		if ($thumbnailId) {
			$thumbnailHTML = static::createThumbnailHtml($thumbnailId, $this->setting('width'), $this->setting('height'), $this->setting('canvas'));
		}
		return $thumbnailHTML;
	}
	
	private static function getGoogleAnalyticsDocumentLink($fileId, $privacyLevel = false, $docFilename = false) {
		$path = 'File not found';
		if (is_numeric($fileId)) {
			$file = getRow('files', array('id', 'filename', 'path', 'created_datetime', 'short_checksum'), $fileId);
			if ($docFilename || $file['filename']) {
				if (!windowsServer() && $privacyLevel == 'public' && (docstoreFilePath($file['id'], false))) {
					$path = 'public/downloads/' . $file['short_checksum'];
				} else {
					$path = 'private';
				}
				if($docFilename) {
					$path .= '/'.$docFilename;
				} else {
					$path .= '/'.$file['filename'];
				}
			}
		} else {
			$path = 'private/'.$fileId;
		}
		return $path;
	}
	
	private function showLinkToFile(&$mergeFields, $document = array()) {
		$archiveName = 'Documents';
		if ($this->setting('zip_file_name')) {
			$archiveName = $this->setting('zip_file_name');
		} elseif (isset($document['folder_name'])) {
			if ($document['folder_name']) {
				$archiveName = $document['folder_name'];
			} else {
				$archiveName = $document['filename'];
			}
		}
		$arr = explode('.', $archiveName);
		if ((count($arr) < 2) || ($arr[count($arr) - 1] != "zip")) {
			$archiveName .= ".zip";
		}
		$this->archiveName = $archiveName;
		
		$result = $this->build();
		if ($result[0]) {
			if ($result[1]) {
				$mergeFields['Download_Page'] = true;
				$mergeFields['Archive_Link'] = $result[1];
				$mergeFields['Archive_Filename'] = $result[2];
				$fileURL = static::getGoogleAnalyticsDocumentLink($result[2]);
				$mergeFields['Google_Analytics_Link'] = trackFileDownload($fileURL);
			} else {
				$mergeFields['Empty_Archive'] = true;
			}
		} else {
			$mergeFields['Archive_Error'] = nl2br($result[1]);
		}
	}
	
	private function showLinkToDownloadPage(&$mergeFields, $ids, $userId = false) {
		if (is_array($ids)) {
			$ids = implode(',', $ids);
		}
		$uID = '';
		if ($userId) {
			$uID = '&user_id='.$userId;
		}
		$requests = $uID.'&build='.$this->instanceId.'&ids=' . $ids;
		$mergeFields['Requests'] = $requests;
		$mergeFields['Anchor_Link'] = $this->linkToItemAnchor(cms_core::$cID, cms_core::$cType, true, $requests);
	}
	
	private static function getZIPExecutable() {
		if (setting('zip_tool_path')){
			return setting('zip_tool_path') . "/zip";
		} else {
			return "zip";
		}
	}
	
	private static function canZIP() {
		if (!windowsServer() && execEnabled()) {
			exec(escapeshellarg(static::getZIPExecutable()) .' -v',$arr,$rv);
			return !(bool)$rv;
		} else {
			return false;
		}
		
	}
	
	private static function addToZipArchive($archiveName,$filenameToAdd) {
		exec(escapeshellarg(static::getZIPExecutable()) . ' -r '. escapeshellarg($archiveName) . ' ' . escapeshellarg($filenameToAdd),$arr,$rv);
		if ($rv) {
			return 'Error. Adding the file ' . basename($filenameToAdd) . ' to the archive ' . basename($archiveName) . ' failed.';
		}
		return "";
	}
	
	private static function getUnpackedFilesSize($ids = '') {
		$filesize = 0;
		$documentDetails = array();
		$sql = '
			SELECT d.id, f.path, f.filename
			FROM '.DB_NAME_PREFIX.'documents d
			INNER JOIN '.DB_NAME_PREFIX.'files f
				ON d.file_id = f.id
			WHERE d.id IN('.sqlEscape($ids).')';
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			$documentDetails[$row['id']] = $row;
		}
		if ($documentIds = explode(",",$ids)) {
			foreach ($documentIds as $documentId) {
				if (isset($documentDetails[$documentId])) {
					if (($path = $documentDetails[$documentId]['path'])
						&& ($filename = $documentDetails[$documentId]['filename'])) {
						$filesize += filesize(setting("docstore_dir") . "/" . $path . "/" . $filename);
					}
				}
				
			}
		}
		return $filesize;
	}
	
	private static function getArchiveNameNoExtension($archiveName) {
		$arr = explode(".",$archiveName);
		if (count($arr) > 1) {
			unset($arr[count($arr) - 1]);
			return implode(".",$arr);
		} else {
			return $archiveName;
		}
	}
	
	private static function getNextFileName($fileName){
		$i = 1;
		if ($fileName) {
			$arr = explode(".", $fileName);
			if (count($arr > 1)) {
				$extension =  $arr[count($arr) - 1];
				unset($arr[count($arr) - 1]);
			} else {
				$extension =  "";
			}
			$file = implode(".", $arr);
			for ($i = 2; $i < 1000; $i++){
				$nextName =  $file . ($i ? "-".$i : "") . "." . $extension;
				if (!file_exists($nextName)){
					return $nextName;
				}
			}
		}
		return "";
	}
	
	private function build() {
		
		$archiveEmpty = true;
		$oldDir = getcwd();
		
		if (($maxUnpackedSize = (int)$this->setting('zip_size_limit')) <= 0) {
			$maxUnpackedSize = 10;
		}
		$maxUnpackedSize*=1048576;
		
		if (static::canZIP()) {
		
		
			if (static::getUnpackedFilesSize(get('ids')) <= $maxUnpackedSize) {
				if ($documentIds = explode(",",get('ids'))){
					
					
					if ((get('user_id') == userId()) && inc('zenario_user_documents') && ($this->setting('container_mode') == 'user_documents')) {
						$sqlTable = DB_NAME_PREFIX. ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents';
						$sqlWhere = 'AND user_id = '.sqlEscape(get('user_id'));
					} else {
						$sqlTable = DB_NAME_PREFIX.'documents';
						$sqlWhere = '';
					}
					
					$documentDetails = array();
					$sql = '
						SELECT d.id, d.file_id, f.filename, f.path, d.filename as doc_filename
						FROM '.sqlEscape($sqlTable).' d
						INNER JOIN '.DB_NAME_PREFIX.'files f
							ON d.file_id = f.id
						WHERE d.id IN ('.sqlEscape(get('ids')).')'. $sqlWhere;
					$result = sqlSelect($sql);
					while ($row = sqlFetchAssoc($result)) {
						$documentDetails[$row['id']] = $row;
					}
					
					$zipArchive = $this->archiveName;
					if ($contentSubdirectory = static::getArchiveNameNoExtension($zipArchive)) {
						cleanDownloads();
						$randomDir = createRandomDir(15, 'downloads', $onlyForCurrentVisitor = setting('restrict_downloads_by_ip'));
						if (mkdir($randomDir . '/' . $contentSubdirectory)) {
							foreach ($documentIds as $documentId) {
								if (isset($documentDetails[$documentId]) && ($docFilename = $documentDetails[$documentId]['doc_filename']) && ($filename = $documentDetails[$documentId]['filename'])) {
									chdir($randomDir);
									$nextFileName = static::getNextFileName($contentSubdirectory . '/' . $docFilename);
									if (($fileID = (int)$documentDetails[$documentId]['file_id']) && ($path = $documentDetails[$documentId]['path'])) {
										copy(setting("docstore_dir") . "/" . $path . "/" . $filename, $nextFileName);
										if (($err = static::addToZipArchive($zipArchive,$nextFileName)) == "") {
											$archiveEmpty = false;
										} else {
											$errors[] = $err;
										}
										unlink($nextFileName);
									}
									chdir($oldDir);
								}
							}
							rmdir($randomDir . '/' . $contentSubdirectory);
							if (isset($errors)){
								return array(false,implode('\n',$errors));
							} elseif($archiveEmpty){
								return array(true,array());
							} else {
								return array(true, $randomDir . $zipArchive,$zipArchive);
							}
						} else {
							return array(false,'Error. Cannot create the documents subdirectory. This can be caused by either: <br/> 1) Incorrect downloads folder write permissions.<br/> 2) Incorrect archive name.');
						}
					} else {
						return array(false,'Error. Archive filename was not specified.');
					}
				} else {
					return array(true,array());
				}
			} else {
				return array(false,'The size of the download exceeds the limit as specified in the Plugin settings.');
			}
		} else {
			return array(false,'Error. Cannot create ZIP archives using ' . static::getZIPExecutable() . '.');
		}
	}
	
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				if (!inc('zenario_user_documents')) {
					$fields['first_tab/container_mode']['hidden'] = true;
				}
				
				$fields['show_files_in_folders']['hidden'] = true;
				$fields['show_folders_in_results']['hidden'] = true;
				$fields['document_tags']['hidden'] = true;
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				
				if (!empty($fields['container_mode']['hidden']) || $values['container_mode'] == 'documents') {
					$fields['document_source']['hidden'] = false;
					if (getRow('documents', 'type', $values['document_source']) == 'folder') {
						$fields['show_folder_name_as_title']['hidden'] = false;
						$fields['show_files_in_folders']['hidden'] = false;
						$fields['show_folders_in_results']['hidden'] = false;
						if (setting('enable_document_tags')) {
							$sql = 'SELECT COUNT(*) FROM '.DB_NAME_PREFIX.'document_tags';
							$result = sqlSelect($sql);
							$row = sqlFetchRow($result);
							if ($row[0] > 0) {
								$fields['document_tags']['hidden'] = false;
							}
						}
					} else {
						$fields['show_files_in_folders']['hidden'] = true;
						$fields['show_folders_in_results']['hidden'] = true;
						$fields['document_tags']['hidden'] = true;
						$fields['show_folder_name_as_title']['hidden'] = true;
					}
					if ($values['show_files_in_folders'] == 'folder') {
						$fields['show_folders_in_results']['hidden'] = true;
					} else {
						$fields['show_folders_in_results']['hidden'] = false;
					}
				} else {
					$fields['document_source']['hidden'] = true;
					$fields['show_files_in_folders']['hidden'] = true;
					$fields['show_folders_in_results']['hidden'] = true;
					$fields['document_tags']['hidden'] = true;
				}
				
				if($values['first_tab/container_mode'] == 'user_documents') {
					$fields['first_tab/show_folder_name_as_title']['hidden'] = true;
					$fields['first_tab/title_tags']['hidden'] = true;
				} else {
					$fields['first_tab/show_folder_name_as_title']['hidden'] = false;
				}
				
				
				$fields['first_tab/zip_file_name']['hidden'] = 
				$fields['first_tab/zip_size_limit']['hidden'] = 
					!$values['offer_download_as_zip'];
				
				if (empty($values['first_tab/zip_file_name']) && !empty($values['first_tab/document_source'])) {
					$values['first_tab/zip_file_name'] = getRow('documents', 'folder_name', array('id' => $values['document_source']));
				}
				
				$fields['first_tab/title_tags']['hidden'] = !$values['first_tab/show_folder_name_as_title'];
				
				
				$hidden =  !$values['first_tab/show_thumbnails'];
				$this->showHideImageOptions($fields, $values, 'first_tab', $hidden);
				break;
		}
	}
	
	function addMergeFields($documents, $level) {
		
		$privacyLevel = getRow('translation_chains', 'privacy', array('equiv_id' => cms_core::$equivId, 'type' => cms_core::$cType));
		foreach ($documents as $key => $childDoc) {
			$file = getRow('files', array('id', 'filename', 'path', 'created_datetime', 'short_checksum', 'size'), $childDoc['file_id']);
			$file['filename'] = $childDoc['filename'];
			$documents[$key]['id'] = $childDoc['id'];
			$documents[$key]['Document_Type'] =  'file';
			$documents[$key]['Document_Link'] =  getDocumentFrontEndLink($childDoc['id']);
			$fileURL = static::getGoogleAnalyticsDocumentLink($childDoc['file_id'], $privacyLevel);
			$documents[$key]['Google_Analytics_Link'] = trackFileDownload($fileURL);
			$documents[$key]['Document_Mime'] = str_replace('/', '_', documentMimeType($documents[$key]['Document_Link']));
			$documents[$key]['Document_created_datetime'] = $file['created_datetime'];
			$documents[$key]['Document_Title'] = $childDoc['filename'];
			$documents[$key]['Document_Link_Text'] = $childDoc['filename'];
			$documents[$key]['Document_Level'] = $level;
			$documents[$key]['Thumbnail'] = $this->getDocumentThumbnail($childDoc['thumbnail_id'], $childDoc['file_id']);
			if ($this->setting('show_file_size') && $file['size']) {
				$fileSize = fileSizeConvert($file['size']);
				$documents[$key]['File_Size'] = $fileSize;
			}
			if ($this->setting('show_upload_date') && $childDoc['file_datetime']) {
				$uploadDate = formatDateTimeNicely($childDoc['file_datetime'], '_MEDIUM');
				$documents[$key]['Upload_Date'] = $this->phrase('Uploaded: [[date]]', array('date' => $uploadDate));
			}
			if($this->setting('show_title')) {
				$documents[$key]['Title_Folder_Exists'] = true;
			}
			$documents[$key]['Title_Exists'] =  $childDoc['title'];
			
			// Make document dataset fields available in framework
			$fields = getDatasetFieldsDetails('documents');
			$documentCustomData = getRow('documents_custom_data', true, $childDoc['id']);
			foreach ($fields as $fieldName => &$field) {
				$value = $displayValue = '';
				if ($documentCustomData && isset($documentCustomData[$fieldName])) {
					$value = $displayValue = $documentCustomData[$fieldName];
				
					switch ($field['type']) {
						case 'radios':
						case 'select':
							$displayValue = getDatasetFieldValueLabel($value);
							break;
						case 'centralised_radios':
						case 'centralised_select':
							if (!isset($field['lov'])) {
								$field['lov'] = getDatasetFieldLOV($field);
							}
							if (isset($field['lov'][$value])) {
								$displayValue = $field['lov'][$value];
							}
							break;
					}
			
				} elseif ($field['type'] == 'checkboxes') {
					//Skip checkboxes for now...
				}
			
				$documents[$key][$fieldName] = $displayValue;
			}
		}
		
		return $documents;
	}
	
	public static function addFolderMergeFields($folder, $level) {
		$privacyLevel = getRow('translation_chains', 'privacy', array('equiv_id' => cms_core::$equivId, 'type' => cms_core::$cType));
		$folder['Document_Type'] =  'folder';
		$folder['Document_Mime'] = 'folder';
		$folder['Document_Title'] = $folder['folder_name'];
		$folder['Document_Link_Text'] = $folder['folder_name'];
		$folder['Document_Level'] = $level;
		return $folder;
	}
	
	public function getFilesInFolder($folderId = false, $documentId = false) {
		$childFiles = array();
		$useDocumentTags = (setting('enable_document_tags') && $this->setting('document_tags'));
		
		$sql = "
			SELECT 
				d.id, 
				d.type, 
				d.file_id, 
				d.folder_name, 
				d.thumbnail_id, 
				d.filename, 
				d.privacy, 
				d.file_datetime, 
				d.title
			FROM " . DB_NAME_PREFIX . "documents AS d";
		
		if ($useDocumentTags) {
			$sql .= "
				LEFT JOIN " . DB_NAME_PREFIX . "document_tag_link AS dtl 
				ON d.id = dtl.document_id";
		}
		
		$sql .= "
			WHERE TRUE";
		
		if ($folderId) {
			$sql .= "
				AND d.folder_id = " . (int)$folderId;
		} elseif ($documentId) {
			$sql .= "
				AND d.id = " . (int)$documentId;
		}
		
		if ($useDocumentTags) {
			$sql .= "
				AND dtl.tag_id IN (" . sqlEscape($this->setting('document_tags')) . ")";
		}
		
		$sql .= "
				AND d.type = 'file'
			GROUP BY d.id
			ORDER BY d.ordinal" ;
		
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)) {
			$childFiles[$row['id']] = $row;
		}
		
		return $childFiles;
	}
	
	public static function getFoldersInFolder($folderId) {
		return getRowsArray(
			'documents', 
			array('id', 'type', 'file_id', 'folder_name'), 
			array('folder_id' => $folderId, 'type' => 'folder'),
			'ordinal'
		);
	}
	
	public function addFilesToDocumentArray(&$documentArray, $foldersArray, $level) {
		foreach($foldersArray as $folder) {
			if($this->setting('show_folders_in_results')) {
				$folder = static::addFolderMergeFields($folder, $level);
				$documentArray[$folder['id']] = $folder;
			}
			if($cFiles = $this->getFilesInFolder($folder['id'])) {
				$cFiles =  $this->addMergeFields($cFiles, $level + 1);
				
				foreach($cFiles as $file) {
					$documentArray[$file['id']] = $file;
				}
			}
			if($this->setting('show_files_in_folders') == 'all' && $cFolders = static::getFoldersInFolder($folder['id'])) {
				$this->addFilesToDocumentArray($documentArray, $cFolders, $level + 1);
			}
		}
	}
	
	public static function createThumbnailHtml($thumbnailFileId, $widthIn, $heightIn, $canvas) {
		$thumbnail = getRow('files', array('id', 'filename', 'path'), $thumbnailFileId);
		$thumbnailLink = $width = $height = false;
		imageLink($width, $height, $thumbnailLink, $thumbnailFileId, $widthIn, $heightIn, $canvas);
		$thumbnailHtml = '<img class="sticky_image"' .
			' src="'. htmlspecialchars($thumbnailLink). '"'.
			' style="width: '. $width. 'px; height: '. $height. 'px;"/>';
		return $thumbnailHtml;
	}
	
	///This function needs works 
	public function getDocumentTags($documentId, $returnTagNames = false) {
		$sql = "SELECT dt.tag_name 
					FROM " . DB_NAME_PREFIX . "documents AS d 
					LEFT JOIN " . DB_NAME_PREFIX . "document_tag_link AS dtl 
						ON d.id = dtl.document_id
					LEFT JOIN "  . DB_NAME_PREFIX . "document_tags AS dt 
						ON dtl.tag_id = dt.id
					WHERE d.id = " . (int)$documentId;
		$result =sqlQuery($sql);
		while ($row = sqlFetchRow($result)) {
			$childFiles[] = array('id' => $row[0], 'type' => $row[1], 'file_id' => $row[2], 'folder_name' => $row[3]);
		}
	}
	
}
