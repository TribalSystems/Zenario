<?php
/*
 * Copyright (c) 2014, Tribal Limited
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
		$this->document_id = $this->setting('document_source');
		
		//return parent::init();
		return true;
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'site_settings':
				break;
				
			case 'plugin_settings':
				$fields['container_mode']['hidden'] = true;
				$fields['show_files_in_folders']['hidden'] = true;
				$fields['show_folders_in_results']['hidden'] = true;
				$fields['document_tags']['hidden'] = true;
				$fields['canvas']['hidden'] = true;
				$fields['width']['hidden'] = true;
				$fields['height']['hidden'] = true;
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'site_settings':
				break;
				
			case 'plugin_settings':
				if (inc('zenario_user_documents')) {
					$fields['container_mode']['hidden'] = false;
				}
				if ($fields['container_mode']['hidden'] || $values['container_mode'] == 'documents') {
					$fields['document_source']['hidden'] = false;
					if (getRow('documents', 'type', $values['document_source']) == 'folder') {
						$fields['show_folder_name_as_title']['hidden'] = false;
						$fields['show_files_in_folders']['hidden'] = false;
						$fields['show_folders_in_results']['hidden'] = false;
						$fields['document_tags']['hidden'] = false;
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
				
				if($values['show_thumbnails']) {
					$fields['canvas']['hidden'] = false;
					$fields['width']['hidden'] = false;
					$fields['height']['hidden'] = false;
				} else {
					$fields['canvas']['hidden'] = true;
					$fields['width']['hidden'] = true;
					$fields['height']['hidden'] = true;
				}
				
				$fields['width']['hidden'] = 
					$fields['canvas']['hidden'] || !in($values['canvas'], 'fixed_width', 'fixed_width_and_height', 'resize_and_crop');
				
				$fields['height']['hidden'] = 
					$fields['canvas']['hidden'] || !in($values['canvas'], 'fixed_height', 'fixed_width_and_height', 'resize_and_crop');
				
				if (isset($fields['canvas']) && empty($fields['canvas']['hidden'])) {
					if ($values['canvas'] == 'fixed_width') {
						$fields['width']['note_below'] =
							adminPhrase('Images may be scaled down maintaining aspect ratio, but will never be scaled up.');
					} else {
						unset($fields['width']['note_below']);
					}
			
					if ($values['canvas'] == 'fixed_height' || $values['canvas'] == 'fixed_width_and_height') {
						$fields['height']['note_below'] =
							adminPhrase('Images may be scaled down maintaining aspect ratio, but will never be scaled up.');
					} elseif ($values['canvas'] == 'resize_and_crop') {
						$fields['height']['note_below'] =
							adminPhrase('Images may be scaled up or down maintaining aspect ratio.');
					} else {
						unset($fields['height']['note_below']);
					}
				}
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
	}
	
	//The showSlot method is called by the CMS, and displays the Plugin on the page
	function showSlot() {
		if(!($this->setting('container_mode') == 'user_documents')) {
			if (checkPriv()) {
				if ($this->document_id && $document = getRow('documents', array('file_id', 'type', 'folder_name'), $this->document_id)) {
					if ($document['type'] == 'file') {
						if ($file = getRow('files', array('id', 'filename'), $document['file_id'])) {
							$showingText = 'Document ' . '"' . $file['filename'] . '"';
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
						if ($this->setting('document_tags')) {
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
					if ($this->document_id) {
						$showingText = 'Missing document with id "' . $this->document_id . '"';
						$pluginSettingsForEditView = array("Showing: " => $showingText);
					} else {
						$showingText = 'No document or folder selected.';
						$pluginSettingsForEditView = array("Showing: " => $showingText);
					}
				}
			
				$this->twigFramework(array('Heading' => 'This is a Document Container plugin', 'Sub_Heading' => 'Automatically shows a list of documents according to its settings (blank if nothing set):', 'Settings' => $pluginSettingsForEditView), false, CMS_ROOT. 'zenario/frameworks/show_plugin_settings.twig.html');
				//use $this->tApiSettings for array of plugin settings
			}
			if ($this->document_id) {
				$link = '';
				if (!$document = getRow('documents', array('file_id', 'type', 'thumbnail_id'), $this->document_id)) {
					return false;
				}
				if ($document['type'] == 'file') {
					$documentTagsArray = explode(',', $this->setting('document_tags'));
					$tagNamesArray = getRowsArray('document_tags', 'tag_name', array('id' => $documentTagsArray));
					$documentTagText = "";
					foreach ($tagNamesArray as $tagName) {
						$documentTagText .= " " . $tagName . ",";
					}
					$documentTagText = rtrim($documentTagText, ",");
					$privacyLevel = getRow('translation_chains', 'privacy', array('equiv_id' => cms_core::$equivId, 'type' => $this->cType));
					$file = getRow('files', array('id', 'filename', 'path', 'created_datetime'), $document['file_id']);
					$link = self::getFileLink($file, $privacyLevel);
					$this->mergeFields['Document_Link'] = $link;
					$this->mergeFields['Document_Tags'] = '';
					$this->mergeFields['Document_Mime'] = str_replace('/', '_', documentMimeType($link));
					$this->mergeFields['Document_created_datetime'] = $file['created_datetime'];
					$this->mergeFields['Document_Title'] =  $file['filename'];
					$this->mergeFields['Document_Link_Text'] =  $file['filename'];
					if ($document['thumbnail_id'] && $this->setting('show_thumbnails')) {
						$thumbnailHtml= self::createThumbnailHtml($document['thumbnail_id'], $this->setting('width'), $this->setting('height'), $this->setting('canvas'));
						$this->mergeFields['Thumbnail'] = $thumbnailHtml;
					} else {
						$this->mergeFields['Thumbnail'] = false;
					}
					$fields = getDatasetFieldsDetails('documents');
					$documentCustomData = getRow('documents_custom_data', true, $this->document_id);
				
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
				
						$this->mergeFields[$fieldName] = $displayValue;
					}
				} elseif ($document['type'] == 'folder') {
					$level = 0;
					$childFiles = array();
					if($childFiles = self::getFilesInFolder($this->document_id)) {
						$childFiles =  $this->addMergeFields($childFiles, $level);
					}
					if($this->setting('show_files_in_folders') != 'folder') {
						if($childFolders = self::getFoldersInFolder($this->document_id)) {
							self::addFilesToDocumentArray($childFiles, $childFolders, $level);
						}
					}
			
					$this->mergeFields['Documents'] = $childFiles;
				}
			} else {
				$this->mergeFields['error'] = 'no_files';
			}
		} else {
			if (checkPriv()) {
				$showingText = 'User documents for logged in user';
				$pluginSettingsForEditView = array("Showing: " => $showingText);
				$this->twigFramework(array('Heading' => 'This is a Document container.', 'Sub_Heading' => 'This slot is auto populated on the basic:', 'Settings' => $pluginSettingsForEditView), false, CMS_ROOT. 'zenario/frameworks/show_plugin_settings.twig.html');
			}
			$link = '';
			if (userId()) {
				if (!$documents = getRowsArray(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', array('id', 'type', 'file_id', 'folder_name', 'thumbnail_id'), array('user_id' => userId()), 'ordinal')) {
					$this->mergeFields['error'] = 'no_files';
				}
				
				$fields = getDatasetFieldsDetails(ZENARIO_USER_DOCUMENTS_PREFIX. 'user_documents');
				//echo '<pre>', htmlspecialchars(print_r($fields, true)), '</pre>';
				
				foreach ($documents as &$document) {
					$privacyLevel = getRow('translation_chains', 'privacy', array('equiv_id' => cms_core::$equivId, 'type' => $this->cType));
					$file = getRow('files', array('id', 'filename', 'path', 'created_datetime'), $document['file_id']);
					$link = self::getFileLink($file, 'private');
					$document['Document_Type'] =  'file';
					$document['Document_Link'] = $link;
					$document['Document_created_datetime'] = $file['created_datetime'];
					$document['Document_Mime'] = str_replace('/', '_', documentMimeType($link));
					$document['Document_Title'] =  $file['filename'];
					$document['Document_Link_Text'] =  htmlspecialchars($file['filename']);
					$document['Document_Level'] = 1;
					
					if ($document['thumbnail_id'] && $this->setting('show_thumbnails')) {
						$thumbnailHtml= self::createThumbnailHtml($document['thumbnail_id'], $this->setting('width'), $this->setting('height'), $this->setting('canvas'));
						$document['Thumbnail'] = $thumbnailHtml;
					} else {
						$document['Thumbnail'] = false;
					}
				
					$documentCustomData = getRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents_custom_data', true, $document['id']);
					
					foreach ($fields as $fieldName => &$field) {
					//var_dump($fieldName);
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
				$this->mergeFields['Documents'] = $documents;
			} else {
				$this->mergeFields['error'] = 'no_user';
			}
		}
		
		if($this->setting('show_folder_name_as_title')) {
			$this->mergeFields['main_folder_title'] = getRow('documents', 'folder_name', $this->document_id);
		} else {
			$this->mergeFields['main_folder_title'] = false;
		}
			//Display the Plugin
		$this->framework('Outer', $this->mergeFields);
	}
	
	public function getFileLink($file, $privacyLevel) {
		if($file['filename']) {
			$symPath = CMS_ROOT . 'public' . '/' . $file['path'] . '_' . $file['filename'];
			$frontLink = 'public' . '/' . $file['path'] . '_' . $file['filename'];
			if (!windowsServer() && $privacyLevel == 'public' && ($path = docstoreFilePath($file['id'], false))) {
				if (!file_exists($symPath)) {
					symlink($path, $symPath);
				}
				return $frontLink;
			} else {
				$link = fileLink($file['id']);
				if (file_exists($symPath)) {
					$subject = "Warning at " . $_SERVER['HTTP_HOST'];
					$body = "Private file found in public folder.\n\n File '" . $file['filename']
						. "'\n Was found with the public path '" . $frontLink 
						. "' and also with the private path '" . $link . "'\n\n"
						. "If you do not want the file to be publicly available remove the symlink from the public folder.";
					sendEmail($subject, $body, 
						EMAIL_ADDRESS_GLOBAL_SUPPORT,
						$addressToOverriddenBy,
						$nameTo = false,
						$addressFrom = false,
						$nameFrom = false,
						false, false, false,
						$isHTML = false);
					//unlink($symPath);
				}
				
				return $link;
			}
		} else {
			return false;
		}
	}
	
	function addMergeFields($documents, $level) {
		$privacyLevel = getRow('translation_chains', 'privacy', array('equiv_id' => cms_core::$equivId, 'type' => $this->cType));
		foreach ($documents as $key => $childDoc) {
			$file = getRow('files', array('id', 'filename', 'path', 'created_datetime'), $childDoc['file_id']);
			$documents[$key]['Document_Type'] =  'file';
			$documents[$key]['Document_Link'] =  self::getFileLink($file, $privacyLevel);
			$documents[$key]['Document_Mime'] = str_replace('/', '_', documentMimeType($documents[$key]['Document_Link']));
			$documents[$key]['Document_created_datetime'] = $file['created_datetime'];
			$documents[$key]['Document_Title'] = $file['filename'];
			$documents[$key]['Document_Link_Text'] = $file['filename'];
			$documents[$key]['Document_Level'] = $level;
			
			if ($childDoc['thumbnail_id'] && $this->setting('show_thumbnails')) {
				$thumbnailHtml= self::createThumbnailHtml($childDoc['thumbnail_id'], $this->setting('width'), $this->setting('height'), $this->setting('canvas'));
				$documents[$key]['Thumbnail'] = $thumbnailHtml;
			} else {
				$documents[$key]['Thumbnail'] = false;
			}
			
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
	
	public function addFolderMergeFields($folder, $level) {
		$privacyLevel = getRow('translation_chains', 'privacy', array('equiv_id' => cms_core::$equivId, 'type' => $this->cType));
		$folder['Document_Type'] =  'folder';
		$folder['Document_Mime'] = 'folder';
		$folder['Document_Title'] = $folder['folder_name'];
		$folder['Document_Link_Text'] = $folder['folder_name'];
		$folder['Document_Level'] = $level;
		return $folder;
	}
	
	public function getFilesInFolder($folderId) {
		$childFiles = array();
		if ($this->setting('document_tags')) {
			$sql = "SELECT d.id, d.type, d.file_id, d.folder_name, d.thumbnail_id 
					FROM " . DB_NAME_PREFIX . "documents AS d 
					LEFT JOIN " . DB_NAME_PREFIX . "document_tag_link AS dtl 
						ON d.id = dtl.document_id 
					WHERE dtl.tag_id IN (" . $this->setting('document_tags') . ") 
						AND d.folder_id = " . $folderId . " 
						AND d.type = 'file'
					GROUP BY d.id
					ORDER BY d.ordinal" ;
			$result = sqlQuery($sql);
			
			while ($row = sqlFetchRow($result)) {
				$childFiles[] = array('id' => $row[0], 'type' => $row[1], 'file_id' => $row[2], 'folder_name' => $row[3]);
			}
					
		} else {
			$childFiles = getRowsArray('documents', 
							array('id', 'type', 'file_id', 'folder_name','thumbnail_id'), 
							array('folder_id' => $folderId, 'type' => 'file'),
							'ordinal');
		}
		if ($childFiles) {
			return $childFiles;
		} else {
			return false;
		}
	}
	
	public function getFoldersInFolder($folderId) {
		$childFolders = getRowsArray('documents', 
							array('id', 'type', 'file_id', 'folder_name'), 
							array('folder_id' => $folderId, 'type' => 'folder'),
							'ordinal');
		if ($childFolders) {
			return $childFolders;
		} else {
			return false;
		}
	}
	
	public function addFilesToDocumentArray(&$documentArray, $foldersArray, &$level) {
		++$level;
		foreach($foldersArray as $folder) {
			if($this->setting('show_folders_in_results')) {
				$folder = self::addFolderMergeFields($folder, $level);
				$documentArray[] = $folder;
			}
			if($cFiles = self::getFilesInFolder($folder['id'])) {
				$cFiles =  $this->addMergeFields($cFiles, $level);
				foreach($cFiles as $file) {
					$documentArray[] = $file;
				}
			}
			if($this->setting('show_files_in_folders') == 'all' && $cFolders = self::getFoldersInFolder($folder['id'])) {
				self::addFilesToDocumentArray($documentArray, $cFolders, $level);
			}
		}
	}
	
	public function createThumbnailHtml($thumbnailFileId, $widthIn, $heightIn, $canvas) {
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
