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

class zenario_document_container extends ze\moduleBaseClass {
	
	public function init() {
		$this->privacy = ze\row::get('translation_chains', 'privacy', array('equiv_id' => ze::$equivId, 'type' => ze::$cType));
		return true;
	}
	
	public function showSlot() {
		$userId = ze\user::id();
		$documentId = $this->setting('document_source');
		$mergeFields = array();
		
		if ($this->setting('container_mode') == 'user_documents' && ze\module::inc('zenario_user_documents')) {
			// Private user documents
			if (ze\priv::check()) {
				$this->showUserDocumentPluginAdminInfo();
			}
			$mergeFields = $this->getDocumentContainerUserDocuments($userId);
		} else {
			// Standard documents
			if (ze\priv::check()) {
				$this->showDocumentPluginAdminInfo($documentId);
			}
			$mergeFields = $this->getDocumentContainerDocuments($documentId);
			
			if ($mergeFields === false) {
				return false;
			}
		}
		
		$mergeFields['Title_Tags'] = $this->setting('title_tags') ? $this->setting('title_tags') : 'h1';
		$mergeFields['main_folder_title'] = false;
		if ($this->setting('show_folder_name_as_title')) {
			$mergeFields['main_folder_title'] = ze\row::get('documents', 'folder_name', $documentId);
		}
		
		// Display the Plugin
		$this->framework('Outer', $mergeFields);
	}
	
	public function handlePluginAJAX() {
		//Download archive
		if (ze::get('build')) {
			//Make sure zip is enabled on server
			if (!static::canZIP()) {
				echo "Unable to create zip archives on this server.";
				return false;
			}
			//Archive must have at least 1 file
			if (!ze::get('ids') || $this->archiveIsEmpty(explode(',', ze::get('ids')))) {
				echo "Archive must contain at least 1 file.";
				return false;
			}
			
			if ($this->setting('container_mode') == 'user_documents' && ze\module::inc('zenario_user_documents')) {
				$sqlTable = DB_NAME_PREFIX . ZENARIO_USER_DOCUMENTS_PREFIX . 'user_documents';
				$sqlWhere = 'AND user_id = ' . (int)ze\user::id();
			} else {
				$sqlTable = DB_NAME_PREFIX . 'documents';
				$sqlWhere = '';
			}
			$paths = '';
			$sql = '
				SELECT d.id, d.file_id, f.filename, f.path, d.filename as doc_filename
				FROM ' . ze\escape::sql($sqlTable) . ' d
				INNER JOIN '.DB_NAME_PREFIX.'files f
					ON d.file_id = f.id
				WHERE d.id IN (' . ze\escape::in(ze::get('ids'), true) . ')' .
				$sqlWhere;
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
				if (file_exists(ze::setting("docstore_dir") . "/" . $row['path'] . "/" . $row['doc_filename'])) {
					$paths .= ' "' . ze::setting("docstore_dir") . "/" . $row['path'] . "/" . $row['doc_filename'] . '"';
				} else {
					$paths .= ' "' . ze::setting("docstore_dir") . "/" . $row['path'] . "/" . $row['filename'] . '"';
				}
			}
			
			$archiveName = $this->getArchiveName();
			
			//zip download headers
			header('Content-Type: application/zip');
			header('Content-disposition: attachment; filename="' . $archiveName . '.zip"');
			header('Content-Transfer-Encoding: binary');
			ob_clean();
			flush();
		
			//use popen to execute a unix command pipeline
			//and grab the stdout as a php stream
			$fp = popen('zip -r -j - ' . $paths, 'r');
		
			$bufsize = 8192;
			while (!feof($fp)) {
			   echo fread($fp, $bufsize);
			   ob_flush();
			   flush();
			}
			pclose($fp);
			exit;
		}
	}
	
	
	private function showDocumentPluginAdminInfo($documentId) {
		if ($documentId && $document = ze\row::get('documents', array('file_id', 'type', 'folder_name','filename','title'), $documentId)) {
			if ($document['type'] == 'file') {
				if ($file = ze\row::get('files', array('id', 'filename'), $document['file_id'])) {
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
				if (ze::setting('enable_document_tags') && $this->setting('document_tags')) {
					$documentTagText = "Only showing documents with one of the following tags:";
					$documentTagsArray = explode(',', $this->setting('document_tags'));
					$tagNamesArray = ze\row::getArray('document_tags', 'tag_name', array('id' => $documentTagsArray));
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
				'Sub_Heading' => 'Automatically shows a list of documents according to its settings (blank if nothing set):', 
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
		
		$document = ze\row::get(
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
				$this->getArchiveDownloadLink($mergeFields, $ids);
			}
			
			$mergeFields['Documents'] = $childFiles;
		}
		return $mergeFields;
	}
	
	private function getDocumentContainerUserDocuments($userId) {
		$mergeFields = array();
		
		if (!$userId) {
			$mergeFields['error'] = 'no_user';
			return $mergeFields;
		}
		
		$documents = ze\row::getArray(
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
			$this->getArchiveDownloadLink($mergeFields, $ids, $userId);
		}
		$fields = ze\dataset::fieldsDetails(ZENARIO_USER_DOCUMENTS_PREFIX. 'user_documents');
		foreach ($documents as &$document) {
			$file = ze\row::get('files', array('filename', 'created_datetime', 'size'), $document['file_id']);
			$link = ze\file::createPrivateLink($document['file_id'], true);
			$document['Document_Type'] =  'file';
			$document['Document_Link'] = $link;
			$document['Document_created_datetime'] = $file['created_datetime'];
			$document['Document_Mime'] = str_replace('/', '_', ze\file::mimeType($link));
			$document['Document_Title'] = $file['filename'];
			$document['Document_Link_Text'] = htmlspecialchars($file['filename']);
			$document['Document_Level'] = 1;
			
			if ($document['thumbnail_id'] && $this->setting('show_thumbnails')) {
				$thumbnailHtml= static::createThumbnailHtml($document['thumbnail_id'], $this->setting('width'), $this->setting('height'), $this->setting('canvas'), $this->setting('lazy_load_images'));
				$document['Thumbnail'] = $thumbnailHtml;
			} else {
				$document['Thumbnail'] = false;
			}
		
			if($this->setting('show_title')) {
				$document['Title_Exists'] = true;
			}
			
			if ($this->setting('show_file_size') && $file['size']) {
				$fileSize = ze\file::fileSizeConvert($file['size']);
				$document['File_Size'] = $fileSize;
			}
			if ($this->setting('show_upload_date') && $document['document_datetime']) {
				$uploadDate = ze\date::formatDateTime($document['document_datetime'], '_MEDIUM');
				$document['Upload_Date'] = $this->phrase('Uploaded: [[date]]', array('date' => $uploadDate));
			}
			
		
			$documentCustomData = ze\row::get(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents_custom_data', true, $document['id']);
			
			foreach ($fields as $fieldName => &$field) {
				$value = $displayValue = '';
			
				if ($documentCustomData && isset($documentCustomData[$fieldName])) {
					$value = $displayValue = $documentCustomData[$fieldName];
				
					switch ($field['type']) {
						case 'radios':
						case 'select':
							$displayValue = ze\dataset::fieldValueLabel($value);
							break;
						case 'centralised_radios':
						case 'centralised_select':
							if (!isset($field['lov'])) {
								$field['lov'] = ze\dataset::fieldLOV($field);
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
		return $mergeFields;
	}
	
	private function getDocumentThumbnail($documentThumbnailId, $fileId) {
		$thumbnailId = false;
		if ($this->setting('show_thumbnails')) {
			if ($documentThumbnailId) {
				$thumbnailId = $documentThumbnailId;
			} else {
				$mimeType = ze\row::get('files', 'mime_type', $fileId);
				if (ze\file::isImageOrSVG($mimeType)) {
					$thumbnailId = $fileId;
				}
			}
		}
		$thumbnailHTML = false;
		if ($thumbnailId) {
			$thumbnailHTML = static::createThumbnailHtml($thumbnailId, $this->setting('width'), $this->setting('height'), $this->setting('canvas'), $this->setting('lazy_load_images'));
		}
		return $thumbnailHTML;
	}
	
	private static function getGoogleAnalyticsDocumentLink($fileId, $privacyLevel = false, $docFilename = false) {
		$path = 'File not found';
		if (is_numeric($fileId)) {
			$file = ze\row::get('files', array('id', 'filename', 'path', 'created_datetime', 'short_checksum'), $fileId);
			if ($docFilename || $file['filename']) {
				if (!ze\server::isWindows() && $privacyLevel == 'public' && (ze\file::docstorePath($file['id'], false))) {
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
	
	private function getArchiveDownloadLink(&$mergeFields, $ids, $userId = false) {
		//Show a message if there are no files in the archive
		$emptyArchive = $this->archiveIsEmpty($ids);
		if ($emptyArchive) {
			$mergeFields['Empty_Archive'] = true;
		//Otherwise show a download link
		} else {
			$requests = array();
			$requests['build'] = $this->instanceId;
			$requests['ids'] = implode(',', $ids);
			if ($userId) {
				$requests['user_id'] = $userId;
			}
			$mergeFields['Anchor_Link'] = 'href="' . $this->pluginAJAXLink($requests) . '"';
			
			//Google analytics link
			$archiveName = $this->getArchiveName();
			$archiveURL = static::getGoogleAnalyticsDocumentLink($archiveName);
			$mergeFields['Google_Analytics_Link'] = ze\file::trackDownload($archiveURL);
		}
	}
	
	private function getArchiveName() {
		//Get archive name from settings
		$archiveName = $this->setting('zip_file_name');
		//Otherwise choose the build folder name
		if (!$archiveName) {
			$archiveName = ze\row::get('documents', 'folder_name', array('id' => $this->setting('document_source'), 'type' => 'folder'));
		}
		//Otherwise default to "files"
		if (!$archiveName) {
			$archiveName = 'files';
		}
		return $archiveName;
	}
	
	private function archiveIsEmpty($ids) {
		//Check there is at least 1 non-folder document in the list of ids of the archive
		foreach ($ids as $documentId) {
			$fileInArchive = ze\row::exists('documents', array('id' => $documentId, 'type' => 'file'));
			if ($fileInArchive) {
				return false;
			}
		}
		return true;
	}
	
	
	private static function getZIPExecutable() {
		$path = ze\server::programPathForExec(ze::setting('zip_path'), 'zip');
		return $path ? $path : 'zip';
	}
	
	private static function canZIP() {
		if (!ze\server::isWindows() && ze\server::execEnabled()) {
			exec(escapeshellarg(static::getZIPExecutable()) .' -v',$arr,$rv);
			return !(bool)$rv;
		} else {
			return false;
		}
	}
	
	
	public function addMergeFields($documents, $level) {
		foreach ($documents as $key => $childDoc) {
			$file = ze\row::get('files', array('created_datetime', 'size'), $childDoc['file_id']);
			$documents[$key]['id'] = $childDoc['id'];
			$documents[$key]['Document_Type'] = 'file';
			$documents[$key]['Document_Link'] = ze\file::getDocumentFrontEndLink($childDoc['id']);
			$fileURL = static::getGoogleAnalyticsDocumentLink($childDoc['file_id'], $this->privacy);
			$documents[$key]['Google_Analytics_Link'] = ze\file::trackDownload($fileURL);
			$documents[$key]['Document_Mime'] = str_replace('/', '_', ze\file::mimeType($documents[$key]['Document_Link']));
			$documents[$key]['Document_created_datetime'] = $file['created_datetime'];
			$documents[$key]['Document_Title'] = $childDoc['filename'];
			$documents[$key]['Document_Link_Text'] = $childDoc['filename'];
			$documents[$key]['Document_Level'] = $level;
			$documents[$key]['Thumbnail'] = $this->getDocumentThumbnail($childDoc['thumbnail_id'], $childDoc['file_id']);
			if ($this->setting('show_file_size') && $file['size']) {
				$fileSize = ze\file::fileSizeConvert($file['size']);
				$documents[$key]['File_Size'] = $fileSize;
			}
			if ($this->setting('show_upload_date') && $childDoc['file_datetime']) {
				$uploadDate = ze\date::formatDateTime($childDoc['file_datetime'], '_MEDIUM');
				$documents[$key]['Upload_Date'] = $this->phrase('Uploaded: [[date]]', array('date' => $uploadDate));
			}
			if($this->setting('show_title')) {
				$documents[$key]['Title_Folder_Exists'] = true;
			}
			$documents[$key]['Title_Exists'] =  $childDoc['title'];
			
			// Make document dataset fields available in framework
			$fields = ze\dataset::fieldsDetails('documents');
			$documentCustomData = ze\row::get('documents_custom_data', true, $childDoc['id']);
			foreach ($fields as $fieldName => &$field) {
				$value = $displayValue = '';
				if ($documentCustomData && isset($documentCustomData[$fieldName])) {
					$value = $displayValue = $documentCustomData[$fieldName];
				
					switch ($field['type']) {
						case 'radios':
						case 'select':
							$displayValue = ze\dataset::fieldValueLabel($value);
							break;
						case 'centralised_radios':
						case 'centralised_select':
							if (!isset($field['lov'])) {
								$field['lov'] = ze\dataset::fieldLOV($field);
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
	
	public function addFolderMergeFields($folder, $level) {
		$folder['Document_Type'] =  'folder';
		$folder['Document_Mime'] = 'folder';
		$folder['Document_Title'] = $folder['folder_name'];
		$folder['Document_Link_Text'] = $folder['folder_name'];
		$folder['Document_Level'] = $level;
		
		if ($this->setting('offer_download_as_zip')) {
			$childFiles= $this->getFilesInFolder($folder['id']);
			$ids = array_keys($childFiles);
			
			$folder['Download_Archive'] = true;
			$this->getArchiveDownloadLink($folder, $ids);
		}
		
		return $folder;
	}
	
	public function getFilesInFolder($folderId = false, $documentId = false) {
		$childFiles = array();
		$useDocumentTags = (ze::setting('enable_document_tags') && $this->setting('document_tags'));
		
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
				AND dtl.tag_id IN (" . ze\escape::sql($this->setting('document_tags')) . ")";
		}
		
		$sql .= "
				AND d.type = 'file'
			GROUP BY d.id
			ORDER BY d.ordinal" ;
		
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$childFiles[$row['id']] = $row;
		}
		
		return $childFiles;
	}
	
	public static function getFoldersInFolder($folderId) {
		return ze\row::getArray(
			'documents', 
			array('id', 'type', 'file_id', 'folder_name'), 
			array('folder_id' => $folderId, 'type' => 'folder'),
			'ordinal'
		);
	}
	
	public function addFilesToDocumentArray(&$documentArray, $foldersArray, $level) {
		foreach($foldersArray as $folder) {
			if($this->setting('show_folders_in_results')) {
				$folder = $this->addFolderMergeFields($folder, $level);
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
	
	public static function createThumbnailHtml($thumbnailFileId, $widthIn, $heightIn, $canvas, $lazyload = false) {
		$thumbnail = ze\row::get('files', array('id', 'filename', 'path'), $thumbnailFileId);
		$thumbnailLink = $width = $height = false;
		ze\file::imageLink($width, $height, $thumbnailLink, $thumbnailFileId, $widthIn, $heightIn, $canvas);
		$thumbnailHtml = '<img class="sticky_image ';
		if ($lazyload) {
			$thumbnailHtml .= 'lazy" data-src="'. htmlspecialchars($thumbnailLink). '"';
		} else {
			$thumbnailHtml .= '" src="'. htmlspecialchars($thumbnailLink). '"';
		}
			$thumbnailHtml .= ' style="width: '. $width. 'px; height: '. $height. 'px;"/>';
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
		$result =ze\sql::select($sql);
		while ($row = ze\sql::fetchRow($result)) {
			$childFiles[] = array('id' => $row[0], 'type' => $row[1], 'file_id' => $row[2], 'folder_name' => $row[3]);
		}
	}
	
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				if (!ze\module::inc('zenario_user_documents')) {
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
					if (ze\row::get('documents', 'type', $values['document_source']) == 'folder') {
						$fields['show_folder_name_as_title']['hidden'] = false;
						$fields['show_files_in_folders']['hidden'] = false;
						$fields['show_folders_in_results']['hidden'] = false;
						if (ze::setting('enable_document_tags')) {
							$sql = 'SELECT COUNT(*) FROM '.DB_NAME_PREFIX.'document_tags';
							$result = ze\sql::select($sql);
							$row = ze\sql::fetchRow($result);
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
					!$values['offer_download_as_zip'];
				
				if (empty($values['first_tab/zip_file_name']) && !empty($values['first_tab/document_source'])) {
					$values['first_tab/zip_file_name'] = ze\row::get('documents', 'folder_name', array('id' => $values['document_source']));
				}
				
				$fields['first_tab/title_tags']['hidden'] = !$values['first_tab/show_folder_name_as_title'];
				
				
				$hidden =  !$values['first_tab/show_thumbnails'];
				$this->showHideImageOptions($fields, $values, 'first_tab', $hidden);
				break;
		}
	}
}
