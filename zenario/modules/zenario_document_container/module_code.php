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
	
	protected $data = [];
	protected $privacy = false;
	protected $useDocumentTags = false;
	
	protected $dataset = false;
	protected $datasetFields = [];
	
	public function init() {
		return true;
	}
	
	public function showSlot() {
		$this->privacy = ze\row::get('translation_chains', 'privacy', ['equiv_id' => ze::$equivId, 'type' => ze::$cType]);
		$mode = $this->setting('container_mode');
		if ($mode == 'documents') {
			$documentId = $this->setting('document_source');
			//Show info in admin mode
			if (ze\priv::check()) {
				//$this->showDocumentPluginAdminInfo($documentId);
			}
			$this->useDocumentTags = ze::setting('enable_document_tags') && $this->setting('document_tags');
			$this->dataset = ze\dataset::details('documents');
			$this->datasetFields = ze\dataset::fieldsDetails($this->dataset['id']);
			$this->getDocumentContainerDocuments($documentId);
			
			//Title
			$this->data['Title_Tags'] = $this->setting('title_tags') ? $this->setting('title_tags') : 'h1';
			$this->data['main_folder_title'] = false;
			if ($this->setting('show_folder_name_as_title')) {
				$this->data['main_folder_title'] = ze\row::get('documents', 'folder_name', $documentId);
			}
			
		} elseif ($mode == 'user_documents') {
			$userId = ze\user::id();
			//User documents must be running
			if (!ze\module::inc('zenario_user_documents')) {
				return false;
			}
			//Show info in admin mode
			if (ze\priv::check()) {
				//$this->showUserDocumentPluginAdminInfo();
			}
			$this->dataset = ze\dataset::details(ZENARIO_USER_DOCUMENTS_PREFIX . 'user_documents');
			$this->datasetFields = ze\dataset::fieldsDetails($this->dataset['id']);
			$this->getDocumentContainerUserDocuments($userId);
		}
		
		if ($this->setting('offer_download_as_zip')) {
			$documentIds = array_keys($this->data['Documents']);
			$this->data['Download_Archive'] = true;
			$this->getArchiveDownloadLink($this->data, $documentIds);
		}
		
		$this->twigFramework($this->data);
	}
	
	
	public function getDocumentContainerDocuments($documentId) {
		$this->data['Documents'] = [];
		
		if (!$document = ze\row::get('documents', ['id', 'file_id', 'type', 'thumbnail_id', 'folder_name', 'filename', 'privacy', 'file_datetime', 'title'], $documentId)) {
			$this->data['error'] = 'no_file';
			return false;
		}
		
		if ($document['type'] == 'file') {
			$this->addToDocuments($document);
		} elseif ($document['type'] == 'folder') {
			$filesToLoad = $this->setting('show_files_in_folders');
			if ($filesToLoad == 'folder') {
				$this->addFilesInFolderToDocuments($documentId);
			} elseif ($filesToLoad == 'sub-folders') {
				$this->addFilesInFolderToDocuments($documentId, true, $maxLevel = 2);
			} elseif ($filesToLoad == 'all') {
				$this->addFilesInFolderToDocuments($documentId, true);
			}
		}
		return $this->data['Documents'];
	}
	
	
	private function addToDocuments($document, $isUserDocument = false, $level = 1) {
		//Add mergefields
		$document['Document_Level'] = $level;
		
		if ($document['type'] == 'folder') {
			$document['Document_Type'] = 'folder';
			$document['Document_Mime'] = 'folder';
			$document['Document_Title'] = $document['folder_name'];
			$document['Document_Link_Text'] = $document['folder_name'];
			if ($this->setting('offer_download_as_zip')) {
				//Add mergefields for archive downloads folder by folder for custom frameworks
				$document['Download_Archive'] = true;
				$documents = $this->getFilesInFolder($document['id'], $includeFolders = false);
				$this->getArchiveDownloadLink($document, array_keys($documents));
			}
			
		} elseif ($document['type'] == 'file') {
			$file = ze\row::get('files', ['filename', 'created_datetime', 'size', 'mime_type'], $document['file_id']);
			$document['Document_Created'] = $file['created_datetime'];
			if (!$isUserDocument) {
				$document['Document_Title'] = $document['filename'];
				$document['Document_Link_Text'] = $document['filename'];
				$document['Document_Link'] = ze\file::getDocumentFrontEndLink($document['id']);
				//TODO should this be the same link as Document_Link?
				$fileURL = static::getGoogleAnalyticsDocumentLink($document['file_id'], $this->privacy);
				$document['Google_Analytics_Link'] = ze\file::trackDownload($fileURL);
				$document['Document_Type'] = $document['type'];
			} else {
				$document['Document_Title'] = $file['filename'];
				$document['Document_Link_Text'] = htmlspecialchars($file['filename']);
				$document['Document_Link'] = ze\file::createPrivateLink($document['file_id'], true);
				$document['Document_Type'] = 'file';
				$document['file_datetime'] = $document['document_datetime'];
			}
			$document['Document_Mime'] = str_replace('/', '_', ze\file::mimeType($document['Document_Link']));
			
			if ($this->setting('show_filename')) {
				//Nothing to do...
			} elseif ($this->setting('show_title')) {
				if ($document['title']) {
					$document['Document_Link_Text'] = $document['title'];
				} elseif (!$this->setting('show_filename_if_no_title')) {
					unset($document['Document_Link_Text']);
				}
			}
			
			if ($this->setting('show_file_size')) {
				$document['File_Size'] = ze\file::fileSizeConvert($file['size']);
			}
			if ($this->setting('show_upload_date')) {
				$uploadDate = ze\date::formatDateTime($document['file_datetime'], '_MEDIUM');
				$document['Upload_Date'] = $this->phrase('Uploaded: [[date]]', ['date' => $uploadDate]);
			}
			
			//Add Thumbnail
			if ($this->setting('show_thumbnails')) {
				$thumbnailId = $document['thumbnail_id'];
				//Use file if image
				if (!$thumbnailId && ze\file::isImageOrSVG($file['mime_type'])) {
					$thumbnailId = $document['file_id'];
				}
				if ($thumbnailId) {
					$document['Thumbnail'] = static::createThumbnailHtml($thumbnailId, $this->setting('width'),  $this->setting('height'), $this->setting('canvas'), $this->setting('lazy_load_images'));
				}
			}
			
			//Make document dataset fields available in framework
			foreach ($this->datasetFields as $dbColumn => $datasetField) {
				$document[$dbColumn] = ze\dataset::fieldDisplayValue($this->dataset, $datasetField, $document['id']);
			}
		}
		$this->data['Documents'][$document['id']] = $document;
	}
	
	public function getFilesInFolder($folderId, $includeFolders = false) {
		$files = [];
		$sql = '
			SELECT d.id, d.file_id, d.type, d.thumbnail_id, d.folder_name, d.filename, d.privacy, d.file_datetime, d.title
			FROM ' . DB_NAME_PREFIX . 'documents d';
		if ($this->useDocumentTags) {
			$sql .= '
				LEFT JOIN ' . DB_NAME_PREFIX . 'document_tag_link dtl
					ON d.id = dtl.document_id';
		}
		$sql .= '
			WHERE d.folder_id = ' . (int)$folderId;
		if ($this->useDocumentTags) {
			$sql .= '
				AND (d.type = "folder" OR dtl.tag_id IN (' . ze\escape::sql($this->setting('document_tags')) . '))';
		}
		if (!$includeFolders) {
			$sql .= '
				AND d.type = "file"';
		}
		$sql .= '
			GROUP BY d.id
			ORDER BY d.ordinal';
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$files[$row['id']] = $row;
		}
		return $files;
	}
	
	private function addFilesInFolderToDocuments($folderId, $recursive = false, $maxLevel = false, $level = 1) {
		$documents = $this->getFilesInFolder($folderId, $includeFolders = true);
		
		foreach ($documents as $document) {
			if ($document['type'] == 'file') {
				$this->addToDocuments($document, false, $level);
			} elseif ($document['type'] == 'folder' && $recursive && (!$maxLevel || $maxLevel > $level)) {
				//Add an entry for the folder if showing folders
				if ($this->setting('show_folders_in_results')) {
					$this->addToDocuments($document, false, $level);
				}
				
				$this->addFilesInFolderToDocuments($document['id'], true, $maxLevel, $level + 1);
			}
		}
	}
	
	private function getDocumentContainerUserDocuments($userId) {
		$this->data['Documents'] = [];
		//Must be a user
		if (!$userId) {
			$this->data['error'] = 'no_user';
			return false;
		}
		
		$result = ze\row::query(ZENARIO_USER_DOCUMENTS_PREFIX . 'user_documents', ['id', 'type', 'file_id', 'title', 'folder_name', 'thumbnail_id', 'document_datetime'], ['user_id' => $userId], 'ordinal');
		//Must be at least one file
		if (!ze\sql::numRows($result)) {
			$this->data['error'] = 'no_files';
			return false;
		}
		
		while ($document = ze\sql::fetchAssoc($result)) {
			$this->addToDocuments($document, $isUserDocument = true);
		}
		return $this->data['Documents'];
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
				$sqlSelect = 'd.id, d.file_id, f.filename, f.path, f.filename as doc_filename';
				$sqlTable = DB_NAME_PREFIX . ZENARIO_USER_DOCUMENTS_PREFIX . 'user_documents';
				$sqlWhere = 'AND user_id = ' . (int)ze\user::id();
			} else {
				$sqlSelect = 'd.id, d.file_id, f.filename, f.path, d.filename as doc_filename';
				$sqlTable = DB_NAME_PREFIX . 'documents';
				$sqlWhere = '';
			}
			$paths = '';
			$sql = '
				SELECT ' . $sqlSelect . '
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
		if ($documentId && $document = ze\row::get('documents', ['file_id', 'type', 'folder_name','filename','title'], $documentId)) {
			if ($document['type'] == 'file') {
				if ($file = ze\row::get('files', ['id', 'filename'], $document['file_id'])) {
					 $showingText = 'Document ' . '"' . $document['filename'] . '"';
				} else {
					$showingText = 'Missing document with file id "' . $document['file_id'] . '"';
				}
				$pluginSettingsForEditView = ["Showing: " => $showingText];
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
					$tagNamesArray = ze\row::getArray('document_tags', 'tag_name', ['id' => $documentTagsArray]);
					foreach ($tagNamesArray as $tagName) {
						$documentTagText .= " " . $tagName . ",";
					}
					$documentTagText = rtrim($documentTagText, ",");
					$pluginSettingsForEditView = ["Showing: " => $showingText, "Tag filter:" => $documentTagText];
				} else {
					$pluginSettingsForEditView = ["Showing: " => $showingText];
				}
			}
		} else {
			$showingText = 'No document or folder selected.';
			if ($documentId) {
				$showingText = 'Missing document with id "' . $documentId . '"';
			}
			$pluginSettingsForEditView = ["Showing: " => $showingText];
		}
		
		//use $this->zAPISettings for array of plugin settings
		$this->twigFramework(
			[
				'Heading' => 'This is a Document Container plugin', 
				'Sub_Heading' => 'Automatically shows a list of documents according to its settings (blank if nothing set):', 
				'Settings' => $pluginSettingsForEditView
			], 
			false, 
			false, 
			'zenario/frameworks/show_plugin_settings.twig.html'
		);
	}
	
	private function showUserDocumentPluginAdminInfo() {
		$this->twigFramework(
			[
				'Heading' => 'This is a Document container.', 
				'Sub_Heading' => 'Automatically shows a list of documents according to its settings (blank if nothing set):', 
				'Settings' => [
					'Showing: ' => 'User documents for logged in user'
				]
			], 
			false, 
			false, 
			'zenario/frameworks/show_plugin_settings.twig.html'
		);
	}
	
	private static function getGoogleAnalyticsDocumentLink($fileId, $privacyLevel = false, $docFilename = false) {
		$path = 'File not found';
		if (is_numeric($fileId)) {
			$file = ze\row::get('files', ['id', 'filename', 'path', 'created_datetime', 'short_checksum'], $fileId);
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
	
	private function getArchiveDownloadLink(&$mergeFields, $documentIds) {
		//Show a message if there are no files in the archive
		$emptyArchive = $this->archiveIsEmpty($documentIds);
		if ($emptyArchive) {
			$mergeFields['Empty_Archive'] = true;
		//Otherwise show a download link
		} else {
			$requests = [];
			$requests['build'] = $this->instanceId;
			$requests['ids'] = implode(',', $documentIds);
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
			$archiveName = ze\row::get('documents', 'folder_name', ['id' => $this->setting('document_source'), 'type' => 'folder']);
		}
		//Otherwise default to "files"
		if (!$archiveName) {
			$archiveName = 'files';
		}
		return $archiveName;
	}
	
	private function archiveIsEmpty($documentIds) {
		//Check there is at least 1 non-folder document in the list of ids of the archive
		foreach ($documentIds as $documentId) {
			$fileInArchive = ze\row::exists('documents', ['id' => $documentId, 'type' => 'file']);
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
	
	public static function createThumbnailHtml($thumbnailFileId, $widthIn, $heightIn, $canvas, $lazyload = false) {
		$thumbnail = ze\row::get('files', ['id', 'filename', 'path'], $thumbnailFileId);
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
	
	
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				if (!ze\module::inc('zenario_user_documents')) {
					$fields['first_tab/container_mode']['values']['user_documents']['disabled'] = true;
					$fields['first_tab/container_mode']['note_below'] = ze\admin::phrase('The "Confidential User Documents" module must be running to show private documents.');
				}
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				//Default value
				if (!$values['first_tab/show_files_in_folders']) {
					$values['first_tab/show_files_in_folders'] = 'folder';
				}
				
				//Show/hide folder specific options
				$isFolder = false;
				if ($values['first_tab/container_mode'] == 'documents' && $values['first_tab/document_source']) {
					$isFolder = ze\row::get('documents', 'type', $values['first_tab/document_source']) == 'folder';
				}
				$fields['first_tab/show_files_in_folders']['hidden'] = !$isFolder;
				$fields['first_tab/show_folders_in_results']['hidden'] = !$isFolder || ($values['first_tab/show_files_in_folders'] == 'folder');
				$fields['first_tab/document_tags']['hidden'] = !$isFolder 
					|| !ze::setting('enable_document_tags') 
					|| ($values['first_tab/container_mode'] != 'documents');
				
				if (!ze\row::count('document_tags', [])) {
					$fields['first_tab/document_tags']['snippet']['html'] = ze\admin::phrase('There are no document tags');
				}
				
				$fields['first_tab/show_folder_name_as_title']['hidden'] = !$isFolder;
				
				//Show/hide thumbnail image options
				$hidden = !$values['first_tab/show_thumbnails'];
				$this->showHideImageOptions($fields, $values, 'first_tab', $hidden);
				
				//Autofill zip name
				if (empty($values['first_tab/zip_file_name']) && !empty($values['first_tab/document_source'])) {
					$values['first_tab/zip_file_name'] = ze\row::get('documents', 'folder_name', $values['document_source']);
				}
				break;
		}
	}
}
