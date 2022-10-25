<?php
/*
 * Copyright (c) 2022, Tribal Limited
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

class zenario_user_documents extends ze\moduleBaseClass {
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__content/hidden_nav/user_documents/panel':
				if (($_POST['reorder'] ?? false) || ($_POST['hierarchy'] ?? false)) {
					//Loop through each moved files
					//var_dump($_POST);
					foreach (explode(',', $ids) as $id) {
						//Look up the current id, folder_id and ordinal
						if ($file = ze\row::get(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', ['id', 'folder_id', 'ordinal'], $id)) {
							$cols = [];
							//var_dump($file);
							//Update the ordinal if it is different
							if (isset($_POST['ordinals'][$id]) && $_POST['ordinals'][$id] != $file['ordinal']) {
								$cols['ordinal'] = $_POST['ordinals'][$id];
							}
							
							//*Update the folder id if it is different, and remember that we've done this
							if (isset($_POST['parent_ids'][$id]) && $_POST['parent_ids'][$id] != $file['folder_id']) {
								$cols['folder_id'] = $_POST['parent_ids'][$id];
								$folder = ze\row::get(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', ['id', 'type'], $_POST['parent_ids'][$id]);
								if ($folder['type'] == "file") {
									echo '<!--Message_Type:Error-->';
									echo ze\admin::phrase('Files may not be moved under other files, files can only be placed under folders.');
									exit;
								}
							}
							
							ze\row::update(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', $cols, $id);
						}
					}
				} elseif ($_POST['upload'] ?? false) {
			
					ze\fileAdm::exitIfUploadError(true, true, false, 'Filedata');
					$file_id = ze\file::addToDatabase('user_file', $_FILES['Filedata']['tmp_name'], preg_replace('/([^.a-z0-9]+)/i', '_',$_FILES['Filedata']['name']), false, false, true);
					$existingUserFile = ze\row::get(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', ['id'], ['file_id' => $file_id, 'user_id' => $refinerId]);
					if ($existingUserFile) {
						echo "This file has already been uploaded to this users files";
						return $existingUserFile['id'];
					}
					/*if ($ids) {
						return ze\row::insert(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents',['type' =>'file', 'file_id' => $file_id, 'user_id' => $refinerId, 'folder_id' => $ids, 'ordinal' => 0]);
					} else {
						return ze\row::insert(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents',['type' =>'file', 'file_id' => $file_id, 'user_id' => $refinerId, 'folder_id' => 0, 'ordinal' => 0]);
					}*/
					
					return ze\row::insert(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents',['type' =>'file', 'file_id' => $file_id, 'user_id' => $refinerId, 'folder_id' => 0, 'ordinal' => 0, 'document_datetime' => date('Y-m-d H:i:s')]);
					
				} elseif ($_POST['delete'] ?? false) {
					foreach (explode(',', $ids) as $id) {
						self::deleteUserDocument($id);
					}
				}
				break;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__content/hidden_nav/user_documents/panel':
				$panel['title'] = 'Confidential documents for user "' . ze\user::email($refinerId) . '"';
				foreach ($panel['items'] as &$item) {
					$filePath = "";
					$fileId = "";
					if (isset($item['type']) && $item['type'] == 'folder') {
						//folders not enabled as of yet
						$tempArray = [];
						$item['css_class'] = 'zenario_folder_item';
						$item['traits']['is_folder'] = true;
						$tempArray = ze\row::getValues(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', 'id', ['folder_id' => $item['id']]);
						$item['folder_file_count'] = count($tempArray);
					} else {
						$item['css_class'] = 'zenario_file_item';
						$fileId = ze\row::get(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', 'file_id', $item['id']);
						if ($fileId) {
							$filePath = ze\file::link($fileId);
							$item['frontend_link'] = $filePath;
						}
						$filenameInfo = pathinfo($item['name']);
						$item['type'] = $filenameInfo['extension'];
					}
					$item['tooltip'] = $item['name'];
					if (strlen($item['name']) > 30) {
						$item['name'] = substr($item['name'], 0, 10) . "..." .  substr($item['name'], -15);
					}
					if ($fileId && ze\file::docstorePath($fileId)) {
						$item['filesize'] = ze\file::fileSizeConvert(filesize(ze\file::docstorePath($fileId)));
					}
				}
				break;
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__content/hidden_nav/user_documents/panel':
				ze\file::stream(ze\row::get(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', 'file_id', $ids));
		
				break;
		}
	}
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'zenario_user_documents__user_document_properties':
				$documentTagsString = '';
				$documentTags = ze\row::getValues(ZENARIO_USER_DOCUMENTS_PREFIX.'user_document_tag_link', 'tag_id', ['user_document_id' => $box['key']['id']]);
				$fileId = ze\row::get(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents','file_id',  $box['key']['id']);
				$documentTitle = ze\row::get(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents','title',  $box['key']['id']);
				$documentName = ze\row::get('files', ['filename'], $fileId);
				$box['title'] = ze\admin::phrase('Editing metadata for document "[[filename]]".', $documentName);
				foreach ($documentTags as $tag) {
					$documentTagsString .= $tag . ",";
				}
				if($documentTitle) {
					$values['details/document_title'] = $documentTitle;
				}
		
				$fields['details/tags']['value'] = $documentTagsString;
				$fields['details/link_to_add_tags']['snippet']['html'] = 
						ze\admin::phrase('To add or edit document tags click <a[[link]]>this link</a>.',
							['link' => ' href="'. htmlspecialchars(ze\link::absolute(). 'zenario/admin/organizer.php#zenario__content/panels/document_tags'). '" target="_blank"']);
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_user_documents__user_document_properties':
				ze\row::delete(ZENARIO_USER_DOCUMENTS_PREFIX.'user_document_tag_link', ['user_document_id' => $box['key']['id']]);
				$tagIds = explode(',', $values['details/tags']);
				foreach ($tagIds as $tagId) {
					ze\row::set(ZENARIO_USER_DOCUMENTS_PREFIX.'user_document_tag_link', ['tag_id' => $tagId, 'user_document_id' => $box['key']['id']], ['tag_id' => $tagId, 'user_document_id' => $box['key']['id']]);
				}
				ze\row::update(ZENARIO_USER_DOCUMENTS_PREFIX . 'user_documents', ['title' => $values['details/document_title']], ['id' => $box['key']['id']]);

			break;
		}
	}
	
	public static function deleteUserDocument($userDocumentId) {
		$details = ze\row::get(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', ['type', 'file_id'], $userDocumentId);
		ze\row::delete(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', ['id' => $userDocumentId]);
		if ($details && $details['type'] == 'folder') {
			$children = ze\row::getAssocs(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', ['id', 'type'], ['folder_id' => $userDocumentId]);
			foreach ($children as $row) {
				self::deleteUserDocument($row['id']);
			}
		} elseif ($details && $details['type'] == 'file') {
			//delete document tags?
			if (!(ze\row::get(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', ['id', 'file_id'], ['file_id'=>$details['file_id']]))) {
				if ($details['file_id']) {
					$fileDetails = ze\row::get('files', ['path', 'filename', 'location'], $details['file_id']);
					ze\row::delete('files', ['id' => $details['file_id']]);
					if ($fileDetails['location'] == 'docstore' &&  $fileDetails['path']) {
						unlink(ze::setting('docstore_dir') . '/'. $fileDetails['path'] . '/' . $fileDetails['filename']);
						rmdir(ze::setting('docstore_dir') . '/'. $fileDetails['path']);
					}
					$symPath = CMS_ROOT . 'public' . '/' .  $fileDetails['filename'];
					if (file_exists($symPath)) {
						unlink($symPath);
					}
				}
			}
		}
	}
}
