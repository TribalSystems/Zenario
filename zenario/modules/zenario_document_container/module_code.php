<?php
/*
 * Copyright (c) 2019, Tribal Limited
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
			
			$this->useDocumentTags = ze::setting('enable_document_tags') && $this->setting('document_tags');
			$this->dataset = ze\dataset::details('documents');
			$this->datasetFields = ze\dataset::fieldsDetails($this->dataset['id'], $indexById = true);
			$this->getDocumentContainerDocuments($documentId);
			
			//Title
			$this->data['Title_Tags'] = $this->setting('title_tags') ? $this->setting('title_tags') : 'h1';
			$this->data['main_folder_title'] = false;
			if ($this->setting('show_a_heading')) {
				if ($this->setting('show_folder_name_as_title')) {
					$this->data['main_folder_title'] = ze\row::get('documents', 'folder_name', $documentId);
				} else {
					$this->data['main_folder_title'] = $this->setting('heading');
				}
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
			$this->datasetFields = ze\dataset::fieldsDetails($this->dataset['id'], $indexById = true);
			$this->getDocumentContainerUserDocuments($userId);
		}
		
		if ($this->setting('offer_download_as_zip')) {
			$documentIds = array_keys($this->data['Documents']);
			$this->data['Download_Archive'] = true;
			$this->getArchiveDownloadLink($this->data, $documentIds);
		}
		
		if ($this->setting('show_view_button')) {
			$this->data['View_Button'] = true;
		}
		
		if ($this->setting('show_download_link')) {
			$this->data['Download_Link'] = true;
			$this->data['Download_Link_Phrase'] = $this->setting('download_link_phrase');
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
			$document['Document_Link_Text'] = htmlspecialchars($document['folder_name']);
			if ($this->setting('offer_download_as_zip')) {
				//Add mergefields for archive downloads folder by folder for custom frameworks
				$document['Download_Archive'] = true;
				$documents = $this->getFilesInFolder($document['id'], $includeFolders = false);
				$this->getArchiveDownloadLink($document, array_keys($documents));
			}
			
			//Folders should always be public.
			$document['privacy'] = "public";
			
		} elseif ($document['type'] == 'file') {
			$file = ze\row::get('files', ['filename', 'created_datetime', 'size', 'mime_type', 'privacy'], $document['file_id']);
			$document['Document_Created'] = $file['created_datetime'];
			if (!$isUserDocument) {
				$document['Document_Title'] = $document['filename'];
				$document['Document_Link_Text'] = htmlspecialchars($document['filename']);
				$document['Document_Link'] = ze\file::getDocumentFrontEndLink($document['id']);
				//TODO should this be the same link as Document_Link?
				$fileURL = static::getGoogleAnalyticsDocumentLink($document['file_id'], $this->privacy);
				$document['Google_Analytics_Link'] = ze\file::trackDownload($fileURL);
				$document['Document_Type'] = $document['type'];
			} else {
				$document['Document_Title'] = $document['filename'];
				$document['Document_Link_Text'] = htmlspecialchars($document['filename']);
				$document['Document_Link'] = ze\file::createPrivateLink($document['file_id'], $document['filename']);
				$document['Document_Type'] = 'file';
				$document['file_datetime'] = $document['document_datetime'];
			}
			$document['Document_Mime'] = str_replace('/', '_', ze\file::mimeType($document['Document_Link']));
			
			if ($this->setting('show_filename')) {
				//Nothing to do...
			} elseif ($this->setting('show_title')) {
				if ($document['title']) {
					$document['Document_Link_Text'] = htmlspecialchars($document['title']);
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
			foreach ($this->datasetFields as $datasetFieldId => $datasetField) {
				$document[$datasetField['db_column']] = ze\dataset::fieldDisplayValue($this->dataset, $datasetField, $document['id']);
			}
		}
		
		$contentItemPrivacy = ze\row::get('translation_chains', 'privacy', ['equiv_id' => ze::$equivId]);
		
		//Before adding document to data array, check for privacy error 
		if ($document['privacy'] == 'public' || ($document['privacy'] == 'private' && $contentItemPrivacy != 'public' && $contentItemPrivacy != 'logged_out')) {
			//Always show public documents,
			//Don't show private documents on public content items,
			//Never show offline documents.
			$this->data['Documents'][$document['id']] = $document;
		} else {
			//Privacy error: don't show private document on public content item. Don't show offline documents.
			if (ze\admin::id()) {
				//Allow admins to view private documents on public content item
				$this->data['Documents'][$document['id']] = $document;
				$this->data['Documents'][$document['id']]['privacy_warning'] = true;
				$this->data['Documents'][$document['id']]['Document_Link'] = "";
			} 
		}
	}
	
	public function getFilesInFolder($folderId, $includeFolders = false) {
		$files = [];
		$sql = '
			SELECT d.id, d.file_id, d.type, d.thumbnail_id, d.folder_name, d.filename, d.privacy, d.file_datetime, d.title
			FROM ' . DB_PREFIX . 'documents d
			LEFT JOIN ' . DB_PREFIX . 'documents_custom_data dcd
				ON d.id = dcd.document_id';
		if ($this->useDocumentTags) {
			$sql .= '
				LEFT JOIN ' . DB_PREFIX . 'document_tag_link dtl
					ON d.id = dtl.document_id';
		}
		$sql .= '
			WHERE d.folder_id = ' . (int)$folderId;
		if (!$includeFolders) {
			$sql .= '
				AND d.type = "file"';
		}
		
		//Filter the file results by document tags
		if ($this->useDocumentTags) {
			$sql .= '
				AND (d.type = "folder" OR dtl.tag_id IN (' . ze\escape::sql($this->setting('document_tags')) . '))';
		}
		
		//Filter the file results by a dataset value
		if ($filterFieldId = $this->setting('filter')) {
			$filterValues = $this->setting('filter_values');
			$filterField = $this->datasetFields[$filterFieldId];
			
			$sql .= '
				AND (d.type = "folder" OR (TRUE';
			
			if ($filterField && $filterValues) {
				if ($filterField['type'] == 'select' || $filterField['type'] == 'radios') {
					$sql .= '
						AND dcd.`' . ze\escape::sql($filterField['db_column']) . '` IN (' . ze\escape::in($filterValues) . ')';
				} elseif ($filterField['type'] == 'checkboxes') {
					$sql .= '
						AND (
							SELECT COUNT(*)
							FROM ' . DB_PREFIX . 'custom_dataset_values_link cdvl
							WHERE cdvl.dataset_id = ' . (int)$this->dataset['id'] . '
							AND cdvl.linking_id = d.id
							AND cdvl.value_id IN (' . ze\escape::in($filterValues) . ')
						)';
				}
			}
			
			$sql .= '
				))';
		}
		
		//Filter the file results by a dataset date
		if ($filterFieldId = $this->setting('date_filter')) {
			$filterField = $this->datasetFields[$filterFieldId];
			$filterType = $this->setting('date_filter_type');
			
			$tablePrefix = $filterField['is_system_field'] ? 'd' : 'dcd';
			
			$sql .= '
				AND (d.type = "folder" OR (TRUE';
			
			switch ($filterType) {
				case 'date_range':
					$sql .= '
						AND DATE(' . ze\escape::sql($tablePrefix) .  '.`' . ze\escape::sql($filterField['db_column']) . '`) BETWEEN "' . ze\escape::sql($this->setting('date_range_start')) . '" AND "' . ze\escape::sql($this->setting('date_range_end')) . '"';
					break;
				case 'relative_date_range':
					if ($this->setting('relative_date_range_operator')=='older') {
						$sqlOperator = '<';
					} else {
						$sqlOperator = '>=';
					}
					
					if ($this->setting('relative_date_range_units') == 'days') {
						$interval = 'DAY';
					} elseif ($this->setting('relative_date_range_units') == 'months') {
						$interval = 'MONTH';
					} else {
						$interval = 'YEAR';
					}
					
					$sql .= ' 
						AND DATE(' . ze\escape::sql($tablePrefix) .  '.`' . ze\escape::sql($filterField['db_column']) . '`) ' . $sqlOperator . ' DATE_SUB(NOW(), INTERVAL ' . (int)$this->setting('relative_date_range_value') . ' ' . $interval . ')';
					break;
				case 'prior_to_date':
					$sql .= '
						AND DATE(' . ze\escape::sql($tablePrefix) .  '.`' . ze\escape::sql($filterField['db_column']) . '`) < "' . ze\escape::sql($this->setting('prior_to_date')) . '"';
					break;
				case 'on_date':
					$sql .= '
						AND DATE(' . ze\escape::sql($tablePrefix) .  '.`' . ze\escape::sql($filterField['db_column']) . '`) = "' . ze\escape::sql($this->setting('on_date')) . '"';
					break;
				case 'after_date':
					$sql .= '
						AND DATE(' . ze\escape::sql($tablePrefix) .  '.`' . ze\escape::sql($filterField['db_column']) . '`) > "' . ze\escape::sql($this->setting('after_date')) . '"';
					break;
			}
			
			$sql .= '
				))';
		}
		
		$sql .= '
			GROUP BY d.id';
		
		$order = $this->setting('order_by');
		$sortSQL = $this->setting('order_by_sort') == 'descending' ? 'DESC' : '';
		switch ($order) {
			case 'filename':
				$sql .= '
					ORDER BY d.filename ' . ze\escape::sql($sortSQL);
				break;
			case 'title':
				$sql .= '
					ORDER BY d.title ' . ze\escape::sql($sortSQL);
				break;
			case 'filename_title':
				$sql .= '
					ORDER BY IFNULL(d.title, d.filename) ' . ze\escape::sql($sortSQL);
				break;
			case 'created_date':
				$sql .= '
					ORDER BY d.document_datetime ' . ze\escape::sql($sortSQL);
				break;
			case 'manual_order':
			default:
				$sql .= '
					ORDER BY d.ordinal';
				break;
		}
		
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
			$document['filename'] = ze\row::get('files', 'filename', ['id' => $document['file_id']]);
			$document['privacy'] = 'private';
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
				$sqlTable = DB_PREFIX . ZENARIO_USER_DOCUMENTS_PREFIX . 'user_documents';
				$sqlWhere = 'AND user_id = ' . (int)ze\user::id();
			} else {
				$sqlSelect = 'd.id, d.file_id, f.filename, f.path, d.filename as doc_filename';
				$sqlTable = DB_PREFIX . 'documents';
				$sqlWhere = '';
			}
			$paths = '';
			$sql = '
				SELECT ' . $sqlSelect . '
				FROM ' . ze\escape::sql($sqlTable) . ' d
				INNER JOIN '.DB_PREFIX.'files f
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
			
			$allowedDocumentIds = [];
			foreach ($documentIds as $documentId) {
				$privacy = ze\row::get('documents', 'privacy', ['id' => $documentId]);
				$contentItemPrivacy = ze\row::get('translation_chains', 'privacy', ['equiv_id' => ze::$equivId]);
				if ($privacy == 'public' || ($privacy == 'private' && $contentItemPrivacy != 'public' && $contentItemPrivacy != 'logged_out')) {
					//Only include private documents in the .zip file if the content item isn't public. Never include offline documents.
					$allowedDocumentIds[] = $documentId;
				}
			}
			
			$requests['ids'] = implode(',', $allowedDocumentIds);
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
	
	public function privacyWarning($documentId, $settings, $cID, $cType) {
		$warning = '';
		$equivId = ze\content::equivId($cID, $cType);
		$this->zAPISettings = $settings;
		
		$this->privacy = ze\row::get('translation_chains', 'privacy', ['equiv_id' => $equivId, 'type' => $cType]);
		$this->useDocumentTags = ze::setting('enable_document_tags') && $this->setting('document_tags');
		$this->dataset = ze\dataset::details('documents');
		$this->datasetFields = ze\dataset::fieldsDetails($this->dataset['id'], $indexById = true);
		
		$documentsInFolderPrivacy = [];
		$documentsInFolder = $this->getDocumentContainerDocuments($documentId);
		
		if (!empty($documentsInFolder)) {
			foreach ($documentsInFolder as $document) {
				$documentsInFolderPrivacy[] = $document['privacy'];
			}
		
			$privateDocsOnPublicContentItems = '';
			$offlineDocs = '';
		
			//Check if there are Private elements on Public content items...
			if (isset(array_count_values($documentsInFolderPrivacy)['private']) && ($this->privacy == 'public' || $this->privacy == 'logged_out')) {
				//$privateDocsOnPublicContentItems = '<p>Warning: content item is Public, the folder contains one or more Private documents, so these documents will not appear to visitors.</p>';
				//TODO: possible rewording?
				$privateDocsOnPublicContentItems = '<p>Warning: content item is Public, but one or more selected documents are Private. These documents will not appear to visitors.</p>';
			}
		
			//...check if there are Offline elements...
			if (isset(array_count_values($documentsInFolderPrivacy)['offline'])) {
				//$offlineDocs = '<p>Warning: the folder contains one or more Offline documents, which will not appear to visitors. Offline documents can be published at any time.</p>';
				//TODO: possible rewording?
				$offlineDocs = '<p>Warning: one or more selected documents are Offline. These documents will not appear to visitors. Offline documents can be published in the Organizer Documents section at any time.</p>';
			}
		
			//...and display a warning note if necessary. Put a line break if there are both types of privacy warning.
			if ($privateDocsOnPublicContentItems != '' && $offlineDocs != '') {
				$warning = $privateDocsOnPublicContentItems . '<br />' . $offlineDocs;
			} else {
				$warning = $privateDocsOnPublicContentItems . $offlineDocs;
			}
		
			return $warning;
		} else {
			return false;
		}
	}
	
	
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				//Disable user documents options if module is not running
				if (!ze\module::inc('zenario_user_documents')) {
					$fields['first_tab/container_mode']['values']['user_documents']['disabled'] = true;
					$fields['first_tab/container_mode']['note_below'] = ze\admin::phrase('The "Confidential User Documents" module must be running to show private documents.');
				}
				
				//Load list of filters (radios, select, checkboxes)
				$dataset = ze\dataset::details('documents');
				$fields['first_tab/filter']['values'] = ze\datasetAdm::listCustomFields($dataset, $flat = false, $filter = ['radios', 'select', 'checkboxes'], $customOnly = true, $useOptGroups = true, $hideEmptyOptGroupParents = true);
				if (empty($fields['first_tab/filter']['values'])) {
					$fields['first_tab/filter']['empty_value'] = ze\admin::phrase('-- No fields to filter on --');
					$fields['first_tab/filter']['readonly'] = true;
				}
				
				//Load list of filters (date)
				$fields['first_tab/date_filter']['values'] = ze\datasetAdm::listCustomFields($dataset, $flat = false, $filter = 'date', $customOnly = false, $useOptGroups = true, $hideEmptyOptGroupParents = true);
				if (empty($fields['first_tab/date_filter']['values'])) {
					$fields['first_tab/date_filter']['empty_value'] = ze\admin::phrase('-- No fields to filter on --');
					$fields['first_tab/date_filter']['readonly'] = true;
				}
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($values['first_tab/container_mode'] == 'documents' && !$values['first_tab/document_source']) {
			$fields['first_tab/document_source']['error'] = 'Please select a document or folder.';
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
				
				$fields['first_tab/show_a_heading']['hidden'] = !$isFolder;
				$fields['first_tab/filter']['hidden'] = !$isFolder;
				$fields['first_tab/date_filter']['hidden'] = !$isFolder;
				$fields['first_tab/order_by']['hidden'] = !$isFolder;
				
				//Show/hide thumbnail image options
				$hidden = !$values['first_tab/show_thumbnails'];
				$this->showHideImageOptions($fields, $values, 'first_tab', $hidden);
				
				//Autofill zip name
				if (empty($values['first_tab/zip_file_name']) && !empty($values['first_tab/document_source'])) {
					$values['first_tab/zip_file_name'] = ze\row::get('documents', 'folder_name', $values['document_source']);
				}
				
				//Load list of filter values
				if ($values['first_tab/filter']) {
					$fields['first_tab/filter_values']['values'] = [];
					$lov = ze\dataset::fieldLOV($values['first_tab/filter']);
					foreach ($lov as $valueId => $label) {
						$fields['first_tab/filter_values']['values'][$valueId] = ['label' => $label];
					}
				}
				
				if ($fields['first_tab/show_download_link'] == true
					&& empty($fields['first_tab/download_link_phrase']['value'])) {
					$fields['first_tab/download_link_phrase']['value'] = 'Download';
				}
								
				//Privacy warning
				if ($values['first_tab/container_mode'] == 'documents' && $documentId = $values['first_tab/document_source']) {
					$warning = $this->privacyWarning($documentId, $settings = $values, $box['key']['cID'], $box['key']['cType']);
				} else {
					$warning = '';
				}
				$fields['first_tab/privacy_warning']['note_below'] = $warning;
				
				break;
		}
	}
}
