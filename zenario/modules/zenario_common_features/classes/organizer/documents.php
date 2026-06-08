<?php
/*
 * Copyright (c) 2026, Tribal Limited
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

class zenario_common_features__organizer__documents extends ze\moduleBaseClass {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$isFlatView = !isset($_REQUEST['_openToItemInHierarchy']) && !isset($_REQUEST['_openItemsInHierarchy']);
		
		if (!ze::setting('enable_document_tags')) {
			unset($panel['collection_buttons']['document_tags']);
		}
		
		foreach ($panel['items'] as &$item) {
			$filePath = "";
			$fileId = "";
			if ($item['type'] == 'folder') {
				$tempArray = [];
				$item['css_class'] = 'zenario_folder_item';
				$item['is_folder'] = true;
				$tempArray = ze\row::getValues('documents', 'id', ['folder_id' => $item['id']]);
				$item['folder_file_count'] = count($tempArray);
				
				if (!$item['folder_file_count']) {
					$item['is_empty_folder'] = true;
				}
				$item['extract_wordcount'] =  $item['privacy'] = '';
				
			} else {
				//change icon
				$item['css_class'] = 'zenario_file_item';
				
				if ($item['filesize']) {
					$item['filesize'] = ze\file::fileSizeConvert($item['filesize']);
				}
				
				$item['css_class'] .= ' zenario_document_privacy_' . $item['privacy'];
				
				$privacyPhraseOffline = ze\admin::phrase('[[name]] is offline. Neither visitors nor logged-in users can access this document.', $item);
				$privacyPhrasePrivate = ze\admin::phrase('[[name]] is private. (Only a logged-in extranet user can access this document via an internal link; URL will change from time to time.)', $item);
				$privacyPhrasePublic = ze\admin::phrase('[[name]] is public. Any visitor who knows the public link can access it.', $item);
				
				if ($item['privacy'] == 'offline') {
					$item['tooltip'] = $privacyPhraseOffline;
					$item['offline'] = true;
				} elseif ($item['privacy'] == 'private') {
					$item['tooltip'] = $privacyPhrasePrivate;
					$item['private'] = true;
				} elseif ($item['privacy'] == 'public') {
					$item['tooltip'] = $privacyPhrasePublic;
					$item['public'] = true;
					
					$dirPath = 'public' . '/downloads/' . $item['short_checksum'];
					$frontLink = $dirPath . '/' . $item['filename'];
					$item['frontend_link'] = $frontLink;
				}
				
				if (!empty($item['extract_wordcount'])) {
					$item['plaintext_extract_details'] = 'Word count: '.$item['extract_wordcount'].", ".$item['extract_snippet'];
				}
				
				if ($item['extract_wordcount']) {
					$item['extract_wordcount'] = ze\admin::nPhrase(
						'[[extract_wordcount]] word', 
						'[[extract_wordcount]] words',
						$item['extract_wordcount'],
						$item
					);
				} else {
					$item['extract_wordcount'] = '';
				}
				
				$fileId = $item['file_id'];
				if ($fileId && empty($item['frontend_link'])) {
					$filePath = ze\file::link($fileId);
					$item['frontend_link'] = $filePath;
				}
				
				$filenameInfo = pathinfo($item['name']);
				if(isset($filenameInfo['extension'])) {
					$item['type'] = $filenameInfo['extension'];
				}
			}
			
			if ($item['created']) {
				$item['created'] = ze\admin::phrase('Created on [[date]]', ['date' => ze\admin::formatDateTime($item['created'], '_MEDIUM', ze::$defaultLang)]);
			}
			
			//In FAB pickers, show the full folder path (including the names of any parent folders)
			if (($mode == 'get_item_name' || $mode == 'typeahead_search' || $mode == 'get_item_links' || $mode == 'select') && $item['id']) {
				$item['full_path_label'] = self::getDocumentFullPath($item);
			}
			
			if ($isFlatView) {
				$item['path'] = self::getDocumentFullPath($item, $forOrganizerPanel = true);
			}
			
			if (mb_strlen($item['name']) > 50) {
				$item['name'] = mb_substr($item['name'], 0, 25) . "..." .  mb_substr($item['name'], -25);
			}
		}
		
		if (count($panel['items']) <= 0) {
			unset($panel['collection_buttons']['reorder_root']);
		}
		
		if (!$isFlatView) {
			$panel['columns']['path']['hidden'] = true;
		}
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		$externalProgramError = false;
		if (ze::post('reorder') || ze::post('hierarchy')) {
			$idsArray = explode(',', $ids);
			$filenamesInFolder = [];
			$folderNamesInFolder = [];
			foreach ($idsArray as $id) {
				// Foreach moved file
				if (isset($_POST['parent_ids'][$id])
					&& ($documentDetails = ze\row::get('documents', ['filename', 'folder_name'], $id))
				) {
					$filename = $documentDetails['filename'];
					$folder_name = $documentDetails['folder_name'];
					$isFolder = (bool)$folder_name;
					
					// Check a file/folder with the same name exists in the database and hasn't moved into the same folder
					$duplicateNamesFound = false;
					$parent_id = $_POST['parent_ids'][$id];
					$sql = '
						SELECT id
						FROM ' . DB_PREFIX . 'documents
						WHERE 1=1 ';
					if ($isFolder) {
						$sql .= ' AND folder_name = "' . ze\escape::sql($folder_name) . '"';
					} else {
						$sql .= ' AND filename = "' . ze\escape::sql($filename) . '"';
					}
					$sql .= '
						AND folder_id = ' . (int)$parent_id . '
						AND id != ' . (int)$id;
					$result = ze\sql::select($sql);
					while ($row = ze\sql::fetchAssoc($result)) {
						$id2 = $row['id'];
						if (!isset($_POST['parent_ids'][$id2]) || ($_POST['parent_ids'][$id2] == $parent_id)) {
							$duplicateNamesFound = true;
						}
					}
					
					// Check identical named files/folders havn't been moved into the same folder at once
					if (!$duplicateNamesFound) { 
						if ($isFolder) {
							if (isset($folderNamesInFolder[$parent_id][$folder_name])) {
								$duplicateNamesFound = true;
							} else {
								$folderNamesInFolder[$parent_id][$folder_name] = true;
							}
						} else {
							if (isset($filenamesInFolder[$parent_id][$filename])) {
								$duplicateNamesFound = true;
							} else {
								$filenamesInFolder[$parent_id][$filename] = true;
							}
						}
					}
					
					if ($duplicateNamesFound) {
						if ($isFolder) {
							$type = 'folder';
							$name = $folder_name;
						} else {
							$type = 'file';
							$name = $filename;
						}
						ze\escape::bFlag('MESSAGE_TYPE', 'error');
						if ($parent_id == 0) {
							$error = ze\admin::phrase('You cannot have more than one [[type]] named "[[name]]" in the root directory', ['name' => $name, 'type' => $type]);
						} else {
							$problem_folder_name = ze\row::get('documents', 'folder_name', $parent_id);
							$error = ze\admin::phrase('You cannot have more than one [[type]] named "[[name]]" in the directory "[[folder_name]]"', ['name' => $name, 'folder_name' => $problem_folder_name, 'type' => $type]);
						}
						echo $error;
						exit;
					}
				}
			}
			
			
			//Loop through each moved files and save
			foreach ($idsArray as $id) {
				//Look up the current id, folder_id and ordinal
				if ($file = ze\row::get('documents', ['id', 'type', 'folder_id', 'ordinal'], $id)) {
					$cols = [];
					
					//Update the ordinal if it is different
					if (isset($_POST['ordinals'][$id]) && $_POST['ordinals'][$id] != $file['ordinal']) {
						$cols['ordinal'] = $_POST['ordinals'][$id];
					}
	
					//Update the folder id if it is different, and remember that we've done this
					if (isset($_POST['parent_ids'][$id]) && $_POST['parent_ids'][$id] != $file['folder_id']) {
						$folderId =
						$cols['folder_id'] = $_POST['parent_ids'][$id];
						
						if ($folderId
						 && ($folder = ze\row::get('documents', ['id', 'type'], $folderId))
						 && ($folder['type'] == 'file')) {
							if ($file['type'] == 'file') {
								ze\escape::bFlag('MESSAGE_TYPE', 'error');
								echo ze\admin::phrase('Files may not be moved under other files. They may only be placed under folders or at the top level.');
								exit;
							} elseif ($file['type'] == 'folder') {
								ze\escape::bFlag('MESSAGE_TYPE', 'error');
								echo ze\admin::phrase('Folders may not be moved under files.');
								exit;
							}
						}
					}
					
					
					ze\row::update('documents', $cols, $id);
				}
			}
		} elseif (ze::post('upload')) {
			ze\priv::exitIfNot('_PRIV_EDIT_DOCUMENTS');
			
			ze\fileAdm::exitIfUploadError(true, true, false, 'Filedata');
			
			$file_id = ze\fileAdm::addToDatabase('hierarchical_file', $_FILES['Filedata']['tmp_name'], preg_replace('/([^.a-z0-9\s_]+)/i', '-',$_FILES['Filedata']['name']), false, false, true);
			$existingFile = ze\row::get('documents', ['id'], ['file_id' => $file_id]);
			if ($existingFile) {
				echo "This file has already been uploaded to the files directory!";
				return $existingFile['id'];
			}
			
			$documentProperties = [
				'type' =>'file',
				'file_id' => $file_id,
				'folder_id' => 0,
				'ordinal' => 0];
				
			$extraProperties = ze\document::addExtract($file_id);
			$documentProperties = array_merge($documentProperties, $extraProperties);
			
			if ($ids) {
				$documentProperties['folder_id'] = $ids;
			}
			
			if ($documentId = ze\row::insert('documents', $documentProperties)) {
				ze\document::processRules($documentId);
			}
			
			return $documentId;
			
		} elseif (ze::post('rescan')) {
			$file_id = ze\row::get('documents', 'file_id', ['id' => $ids]);
			$documentProperties = ze\document::addExtract($file_id, $reScan = true);
			if (empty($documentProperties['extract']) || empty($documentProperties['thumbnail_id'])) {
				ze\escape::bFlag('MESSAGE_TYPE', 'error');
			} else {
				ze\escape::bFlag('MESSAGE_TYPE', 'success');
			}
			
			if (empty($documentProperties['extract'])) {
				echo '<p>', ze\admin::phrase('Unable to update document text extract.'), '</p>';
				
				if (!((ze\fileAdm::plainTextExtract(ze::moduleDir('zenario_common_features', 'fun/test_files/test.doc'), $extract))
					 && ($extract == 'Test'))) {
					echo '<p>', ze\admin::phrase('<code>antiword</code> or <code>pdftotext</code> do not appear to be working.'), '</p>';
					$externalProgramError = true;
				}
			} else {
				echo '<p>', ze\admin::phrase('Successfully updated document text extract.'), '</p>';
			}
			
			if (empty($documentProperties['thumbnail_id'])) {
				echo '<p>', ze\admin::phrase('Unable to update document image.'), '</p>';
				
				if (!ze\file::createPdfFirstPageScreenshotPng(ze::moduleDir('zenario_common_features', 'fun/test_files/test.pdf'))) {
					echo '<p>', ze\admin::phrase('<code>ghostscript</code> does not appear to be working.'), '</p>';
					$externalProgramError = true;
				}
			} else {
				echo '<p>', ze\admin::phrase('Successfully updated document image.'), '</p>';
			}
			
			$lastUpdated = [];
			ze\admin::setLastUpdated($lastUpdated, $creating = false);
	
			$documentProperties['last_edited'] = $lastUpdated['last_edited'];
			$documentProperties['last_edited_admin_id'] = $lastUpdated['last_edited_admin_id'];
			
			ze\row::update('documents', $documentProperties, ['id' => $ids]);
			
		} elseif (ze::post('rescan_image')) {
			$file_id = ze\row::get('documents', 'file_id', ['id' => $ids]);
			$documentProperties = [];
			$extract = [];
			$thumbnailId = false;
			ze\fileAdm::updateHierarchicalDocumentExtract($file_id, $extract, $thumbnailId);
			
			if ($thumbnailId) {
				$documentProperties['thumbnail_id'] = $thumbnailId;
				
				$lastUpdated = [];
				ze\admin::setLastUpdated($lastUpdated, $creating = false);
		
				$documentProperties['last_edited'] = $lastUpdated['last_edited'];
				$documentProperties['last_edited_admin_id'] = $lastUpdated['last_edited_admin_id'];
				
				ze\row::update('documents', $documentProperties, ['id' => $ids]);
			}
			
		} elseif (ze::post('rescan_text')) { 
			$file_id = ze\row::get('documents', 'file_id', ['id' => $ids]);
			$documentProperties = ze\document::addExtract($file_id, $reScan = true);
			if (empty($documentProperties['extract'])) {
				ze\escape::bFlag('MESSAGE_TYPE', 'error');
			} else {
				ze\escape::bFlag('MESSAGE_TYPE', 'success');
			}
			if (empty($documentProperties['extract'])) {
				echo '<p>', ze\admin::phrase('Unable to update document text extract.'), '</p>';
				
				if (!((ze\fileAdm::plainTextExtract(ze::moduleDir('zenario_common_features', 'fun/test_files/test.doc'), $extract))
					 && ($extract == 'Test'))) {
					echo '<p>', ze\admin::phrase('<code>antiword</code> or <code>pdftotext</code> do not appear to be working.'), '</p>';
					$externalProgramError = true;
				}
			} else {
				echo "<p>Successfully updated document text extract.</p>";
				
				$lastUpdated = [];
				\ze\admin::setLastUpdated($lastUpdated, $creating = false);
		
				$documentProperties['last_edited'] = $lastUpdated['last_edited'];
				$documentProperties['last_edited_admin_id'] = $lastUpdated['last_edited_admin_id'];
				
				ze\row::update(
					'documents',
					[
						'extract' => $documentProperties['extract'],
						'last_edited' => $documentProperties['last_edited'],
						'last_edited_admin_id' => $documentProperties['last_edited_admin_id']
					],
					['id' => $ids]
				);
			}
		
		//Remove all of the custom data from a document
		} elseif (ze::post('remove_metadata')) {
			ze\priv::exitIfNot('_PRIV_EDIT_DOCUMENTS');
			if ($dataset = ze\dataset::details('documents')) {
				foreach (explode(',', $ids) as $id) {
					ze\document::removeMetadata($id, $dataset);
				}
			}
			
		} elseif (ze::post('delete')) {
			ze\priv::exitIfNot('_PRIV_EDIT_DOCUMENTS');
			foreach (explode(',', $ids) as $id) {
				//This function will remove document metadata as well as the document itself.
				ze\document::delete($id);
			}
		} elseif (ze::post('generate_public_link')) {
			$idsArray = explode(',', $ids);
			$count = count($idsArray);
			
			$html = '';
			
			$successfullyMadePublic = 0;
			$errorsWhileMakingPublic = 0;
			
			if ($count) {
				foreach ($idsArray as $id) {
					$result = ze\document::generatePublicLink($id);
					
					if (ze::isError($result)) {
						$errorsWhileMakingPublic++;
						//Show error message only if 1 item was selected
						if ($count == 1) {
							$html .= $result->errors['message'] . '<br/>';
						}
					} else {
						$successfullyMadePublic++;
						
						$lastUpdated = [];
						$documentProperties = [];
						ze\admin::setLastUpdated($lastUpdated, $creating = false);
				
						$documentProperties['last_edited'] = $lastUpdated['last_edited'];
						$documentProperties['last_edited_admin_id'] = $lastUpdated['last_edited_admin_id'];
						\ze\row::update('documents', $documentProperties, ['id' => $id]);
					}
				}
				
				
				if ($errorsWhileMakingPublic) {
					if ($errorsWhileMakingPublic > 1) {
						$html .= ze\admin::phrase('[[count]] documents were not made public due to errors.', $errorsWhileMakingPublic, ['count' => $errorsWhileMakingPublic]);
					}
					
					ze\escape::bFlag('MESSAGE_TYPE', 'Error');
					echo $html;
				}
			}
			
		} elseif (ze::post('make_document_private')) {
			$idsArray = explode(',', $ids);
			$count = count($idsArray);
			
			foreach ($idsArray as $id) {
				ze\row::set('documents', ['privacy' => 'private'], ['id' => $id]);
				
				//Check if the file had a public link before, and remove it if necessary
				ze\document::deletePubliclink($id, $documentDeleted = false, $privacy = 'private');
			}
			
		} elseif (ze::post('make_offline')) {
			ze\priv::exitIfNot('_PRIV_EDIT_DOCUMENTS');
			$idsArray = explode(',', $ids);
			$count = count($idsArray);
			
			foreach ($idsArray as $id) {
				$result = ze\document::deletePubliclink($id);
				
				if ($result === true) {
					
					//Show success message only if 1 item was selected
					if($count == 1) {
						ze\escape::bFlag('MESSAGE_TYPE', 'success');
						echo 'This document is now offline.';
					}
				} else {
					
					//Show error message only if 1 item was selected
					if($count == 1) {
						ze\escape::bFlag('MESSAGE_TYPE', 'error');
						echo $result;
					}
				}
			}
		} elseif (ze::post('copy_to_document_content_items')) {
			$newIds = [];
			
			$idsArray = explode(',', $ids);
			$count = count($idsArray);
			$succeeded = 0;
			
			foreach ($idsArray as $id) {
				$languageId = ze::$defaultLang;
				$cType = 'document';
				$layoutId = ze\row::get('content_types', 'default_layout_id', ['content_type_id' => $cType]);
				
				$documentFileIdAndTitle = ze\row::get('documents', ['file_id', 'title'], ['id' => $id]);
				$filename = ze\row::get('files', 'filename', ['id' => $documentFileIdAndTitle['file_id']]);

				if (!empty($documentFileIdAndTitle['title'])) {
					$browserTitle = $documentFileIdAndTitle['title'];
				} else {
					$browserTitle = preg_replace('/([^.a-z0-9\-_\(\)\[\]\'\"]+)/i', ' ', $filename);
				}
				
				if ($fileId = ze\fileAdm::copyInDatabase('content', $documentFileIdAndTitle['file_id'], $filename, $mustBeAnImage = false, $addToDocstoreDirIfPossible = true)) {
					$cID = $cVersion = false;
					ze\contentAdm::createDraft($cID, false, $cType, $cVersion, false, $languageId);
					ze\row::set(
						'content_item_versions',
						['layout_id' => $layoutId, 'title' => $browserTitle, 'filename' => $filename, 'file_id' => $fileId],
						['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
					$newIds[] = $cType. '_'. $cID;
					
					ze\fileAdm::updateDocumentContentItemExtract($cID, $cType, $cVersion, $fileId, false, true);
					
					//If this document has been created from an image, create a thumbnail.
					$file = ze\row::get('files', ['usage', 'filename', 'location', 'path', 'image_credit'], ['id' => $fileId]);
					
					if (!empty($file) && $file['location'] == 'docstore' && ze\file::isImage(ze\file::mimeType($file['filename']))) {
						$location = ze\file::docstorePath($file['path']);
						$thumbnailId = ze\fileAdm::addToDatabase(
							'image', $location, $file['filename'], $mustBeAnImage = true, $deleteWhenDone = false, $addToDocstoreDirIfPossible = false,
							false, false, false, false, $file['image_credit']
						);
						
						ze\row::set('inline_images', [], [
							'image_id' => $thumbnailId,
							'foreign_key_to' => 'content',
							'foreign_key_id' => $cID,
							'foreign_key_char' => $cType,
							'foreign_key_version' => $cVersion
						]);
						ze\contentAdm::updateVersion($cID, $cType, $cVersion, ['feature_image_id' => $thumbnailId]);
						ze\contentAdm::updateContentItemCache($cID, $cType, $cVersion);
					}
					
					$succeeded++;
				}
			}
			
			$popoutMessage = "
				<p>" . 
					ze\admin::nPhrase(
						'[[succeeded]] document was successfully copied.',
						'[[succeeded]] documents were successfully copied.',
						$succeeded,
						['succeeded' => $succeeded]
					)
				. "</p>";
			
			ze\escape::bFlag('MESSAGE_TYPE', 'Success');
			echo $popoutMessage;
		}
		
		if ($externalProgramError) {
			echo
				'<p>', ze\admin::phrase('Please go to <a href="[[href]]">Configuration->Site Settings</a> and open the Other server programs interface to fix this.',
					['href' => '#zenario__administration/panels/site_settings//external_programs']
				), '</p>';
		}
	}
	
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		$document = ze\row::get('documents', ['file_id', 'filename'], $ids);
		ze\file::stream($document['file_id'], $document['filename']);
		exit;
	}
	
	public function getDocumentFullPath($item, $forOrganizerPanel = false) {
		$fullPathLabel = '';
		
		if (!empty($item)) {
			if ($item['folder_id']) {
				$currentParent = $item['folder_id'];
				$fullPathLabel = [];
				$iteration = 1;
				while ($currentParent) {
					$result = ze\row::get('documents', ['folder_id', 'folder_name'], ['id' => $currentParent]);
					if ($result) {
						$fullPathLabel[$iteration] = $result['folder_name'];
						$currentParent = $result['folder_id'];
					} else {
						$currentParent = false;
					}
					$iteration++;
					//Prevent infinite loops
					if ($iteration >= 500) {
						break;
					}
				}
				
				krsort($fullPathLabel);
				$fullPathLabel[] = $item['name'];
				$fullPathLabel = implode(' › ', $fullPathLabel);
			} else {
				if ($forOrganizerPanel) {
					$fullPathLabel = ze\admin::phrase('[[item_name]] [top level]', ['item_name' => $item['name']]);
				} else {
					$fullPathLabel = $item['name'];
				}
			}
		}
		
		return $fullPathLabel;
	}
}