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

class zenario_user_documents extends module_base_class {
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__content/hidden_nav/user_documents/panel':
				if (post('reorder') || post('hierarchy')) {
					//Loop through each moved files
					//var_dump($_POST);
					foreach (explode(',', $ids) as $id) {
						//Look up the current id, folder_id and ordinal
						if ($file = getRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', array('id', 'folder_id', 'ordinal'), $id)) {
							$cols = array();
							//var_dump($file);
							//Update the ordinal if it is different
							if (isset($_POST['ordinals'][$id]) && $_POST['ordinals'][$id] != $file['ordinal']) {
								$cols['ordinal'] = $_POST['ordinals'][$id];
							}
							
							//*Update the folder id if it is different, and remember that we've done this
							if (isset($_POST['parent_ids'][$id]) && $_POST['parent_ids'][$id] != $file['folder_id']) {
								$cols['folder_id'] = $_POST['parent_ids'][$id];
								$folder = getRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', array('id', 'type'), $_POST['parent_ids'][$id]);
								if ($folder['type'] == "file") {
									echo '<!--Message_Type:Error-->';
									echo adminPhrase('Files may not be moved under other files, files can only be placed under folders.');
									exit;
								}
							}
							
							updateRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', $cols, $id);
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
					$file_id = addFileToDatabase('user_file', $_FILES['Filedata']['tmp_name'], preg_replace('/([^.a-z0-9]+)/i', '_',$_FILES['Filedata']['name']), false, false, true);
					$existingUserFile = getRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', array('id'), array('file_id' => $file_id, 'user_id' => $refinerId));
					if ($existingUserFile) {
						echo "This file has already been uploaded to this users files";
						return $existingUserFile['id'];
					}
					/*if ($ids) {
						return insertRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents',array('type' =>'file', 'file_id' => $file_id, 'user_id' => $refinerId, 'folder_id' => $ids, 'ordinal' => 0));
					} else {
						return insertRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents',array('type' =>'file', 'file_id' => $file_id, 'user_id' => $refinerId, 'folder_id' => 0, 'ordinal' => 0));
					}*/
					return insertRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents',array('type' =>'file', 'file_id' => $file_id, 'user_id' => $refinerId, 'folder_id' => 0, 'ordinal' => 0));
				} elseif (post('delete')) {
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
				$panel['title'] = 'Confidential documents for user "' . userEmail($refinerId) . '"';
				foreach ($panel['items'] as &$item) {
					$filePath = "";
					$fileId = "";
					if (isset($item['type']) && $item['type'] == 'folder') {
						//folders not enabled as of yet
						$tempArray = array();
						$item['css_class'] = 'zenario_folder_item';
						$item['traits']['is_folder'] = true;
						$tempArray = getRowsArray(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', 'id', array('folder_id' => $item['id']));
						$item['folder_file_count'] = count($tempArray);
					} else {
						$item['css_class'] = 'zenario_file_item';
						$fileId = getRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', 'file_id', $item['id']);
						if ($fileId) {
							$filePath = fileLink($fileId);
							$item['frontend_link'] = $filePath;
						}
						$filenameInfo = pathinfo($item['name']);
						$item['type'] = $filenameInfo['extension'];
					}
					$item['tooltip'] = $item['name'];
					if (strlen($item['name']) > 30) {
						$item['name'] = substr($item['name'], 0, 10) . "..." .  substr($item['name'], -15);
					}
					if ($fileId && docstoreFilePath($fileId)) {
						$item['filesize'] = fileSizeConvert(filesize(docstoreFilePath($fileId)));
					}
				}
				break;
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__content/hidden_nav/user_documents/panel':
				//Redirect to the file script to do the download
				$file =  getRow('files', true, getRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', 'file_id', $ids));
				if ($file['path']) {
					header('Content-Description: File Transfer');
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename="'.basename(docstoreFilePath($file['id'])).'"'); //<<< Note the " " surrounding the file name
					header("Content-Type: application/force-download");
					header("Content-Type: application/octet-stream");
					header("Content-Type: application/download");
					header('Content-Transfer-Encoding: binary');
					header('Connection: Keep-Alive');
					header('Expires: 0');
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header('Pragma: public');
					header('Content-Length: ' . filesize(docstoreFilePath($file['id'])));
					readfile(docstoreFilePath($file['id']));
					exit;
				} else {
					header('location: '. absCMSDirURL(). 'zenario/file.php?adminDownload=1&download=1&id='. getRow('files', 'id', getRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', 'file_id', $ids)));
					exit;
				}
		
				break;
		}
	}
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'zenario_user_documents__user_document_properties':
				$documentTagsString = '';
				$documentTags = getRowsArray(ZENARIO_USER_DOCUMENTS_PREFIX.'user_document_tag_link', 'tag_id', array('user_document_id' => $box['key']['id']));
				$fileId = getRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents','file_id',  $box['key']['id']);
				$documentTitle = getRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents','title',  $box['key']['id']);
				$documentName = getRow('files', array('filename'), $fileId);
				$box['title'] = adminPhrase('Editing metadata for document "[[filename]]".', $documentName);
				foreach ($documentTags as $tag) {
					$documentTagsString .= $tag . ",";
				}
				if($documentTitle) {
					$values['details/document_title'] = $documentTitle;
				}
		
				$fields['details/tags']['value'] = $documentTagsString;
				$fields['details/link_to_add_tags']['snippet']['html'] = 
						adminPhrase('To add or edit document tags click <a[[link]]>this link</a>.',
							array('link' => ' href="'. htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__content/panels/document_tags'). '" target="_blank"'));
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_user_documents__user_document_properties':
				deleteRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_document_tag_link', array('user_document_id' => $box['key']['id']));
				$tagIds = explode(',', $values['details/tags']);
				foreach ($tagIds as $tagId) {
					setRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_document_tag_link', array('tag_id' => $tagId, 'user_document_id' => $box['key']['id']), array('tag_id' => $tagId, 'user_document_id' => $box['key']['id']));
				}
				updateRow(ZENARIO_USER_DOCUMENTS_PREFIX . 'user_documents', array('title' => $values['details/document_title']), array('id' => $box['key']['id']));

			break;
		}
	}
	
	public function deleteUserDocument($userDocumentId) {
		$details = getRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', array('type', 'file_id'), $userDocumentId);
		deleteRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', array('id' => $userDocumentId));
		if ($details && $details['type'] == 'folder') {
			$children = getRowsArray(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', array('id', 'type'), array('folder_id' => $userDocumentId));
			foreach ($children as $row) {
				self::deleteUserDocument($row['id']);
			}
		} elseif ($details && $details['type'] == 'file') {
			//delete document tags?
			if (!(getRow(ZENARIO_USER_DOCUMENTS_PREFIX.'user_documents', array('id', 'file_id'), array('file_id'=>$details['file_id'])))) {
				if ($details['file_id']) {
					$fileDetails = getRow('files', array('path', 'filename', 'location'), $details['file_id']);
					deleteRow('files', array('id' => $details['file_id']));
					if ($fileDetails['location'] == 'docstore' &&  $fileDetails['path']) {
						unlink(setting('docstore_dir') . '/'. $fileDetails['path'] . '/' . $fileDetails['filename']);
						rmdir(setting('docstore_dir') . '/'. $fileDetails['path']);
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
