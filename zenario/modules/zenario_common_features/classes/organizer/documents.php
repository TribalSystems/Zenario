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

class zenario_common_features__organizer__documents extends ze\moduleBaseClass {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		if (!ze::setting('enable_document_tags')) {
			unset($panel['collection_buttons']['document_tags']);
		}
		
		foreach ($panel['items'] as &$item) {
			$filePath = "";
			$fileId = "";
			if ($item['type'] == 'folder') {
				$tempArray = [];
				$item['css_class'] = 'zenario_folder_item';
				$item['traits']['is_folder'] = true;
				$tempArray = ze\row::getValues('documents', 'id', ['folder_id' => $item['id']]);
				$item['folder_file_count'] = count($tempArray);
				
				if (!$item['folder_file_count']) {
					$item['traits']['is_empty_folder'] = true;
				}
				$item['extract_wordcount'] =  $item['privacy'] = '';
				
			} else {
				//change icon
				$item['css_class'] = 'zenario_file_item';
				
				if ($item['filesize']) {
					$item['filesize'] = ze\file::fileSizeConvert($item['filesize']);
				}
				
				$item['css_class'] .= ' zenario_document_privacy_' . $item['privacy'];
				
				$privacyPhraseOffline = ze\admin::phrase('[[name]] is Offline. (will become Public when a link to it is made from a public content item, or Private when a link is made on a private content item)', $item);
				$privacyPhrasePrivate = ze\admin::phrase('[[name]] is Private. (only a logged-in extranet user can access this document via an internal link; URL will change from time to time)', $item);
				$privacyPhrasePublic = ze\admin::phrase('[[name]] is Public. (any visitor who knows the public link can access it)', $item);
				
				if ($item['privacy'] == 'offline') {
					$item['tooltip'] = $privacyPhraseOffline;
					$item['traits']['offline'] = true;
				} elseif ($item['privacy'] == 'private') {
					$item['tooltip'] = $privacyPhrasePrivate;
					$item['traits']['private'] = true;
				} elseif ($item['privacy'] == 'public') {
					$item['tooltip'] = $privacyPhrasePublic;
					$item['traits']['public'] = true;
					
					$dirPath = 'public' . '/downloads/' . $item['short_checksum'];
					$frontLink = $dirPath . '/' . $item['filename'];
					$item['frontend_link'] = $frontLink;
				}
				
				if (!empty($item['extract_wordcount'])) {
					$item['plaintext_extract_details'] = 'Word count: '.$item['extract_wordcount'].", ".$item['extract_snippet'];
				}
				
				if ($item['date_uploaded']) {
					$item['date_uploaded'] = ze\admin::phrase('Uploaded [[date]]', ['date' => ze\admin::formatDateTime($item['date_uploaded'], '_MEDIUM', \ze::$defaultLang)]);
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
			
			if (mb_strlen($item['name']) > 50) {
				$item['name'] = mb_substr($item['name'], 0, 25) . "..." .  mb_substr($item['name'], -25);
			}
			
		}
		
		if (count($panel['items']) <= 0) {
			unset($panel['collection_buttons']['reorder_root']);
		}
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		$externalProgramError = false;
		if (($_POST['reorder'] ?? false) || ($_POST['hierarchy'] ?? false)) {
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
						echo '<!--Message_Type:Error-->';
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
				if ($file = ze\row::get('documents', ['id', 'folder_id', 'ordinal'], $id)) {
					$cols = [];
					
					//Update the ordinal if it is different
					if (isset($_POST['ordinals'][$id]) && $_POST['ordinals'][$id] != $file['ordinal']) {
						$cols['ordinal'] = $_POST['ordinals'][$id];
					}
	
					//Update the folder id if it is different, and remember that we've done this
					if (isset($_POST['parent_ids'][$id]) && $_POST['parent_ids'][$id] != $file['folder_id']) {
						$cols['folder_id'] = $_POST['parent_ids'][$id];
						$folder = ze\row::get('documents', ['id', 'type'], $_POST['parent_ids'][$id]);
						if ($folder['type'] == "file") {
							echo '<!--Message_Type:Error-->';
							echo ze\admin::phrase('Files may not be moved under other files, files can only be placed under folders.');
							exit;
						}
					}
					
					
					ze\row::update('documents', $cols, $id);
				}
			}
		} elseif ($_POST['upload'] ?? false) {
			ze\priv::exitIfNot('_PRIV_EDIT_DOCUMENTS');
			if (!ze\file::isAllowed($_FILES['Filedata']['name'])) {
				echo
					ze\admin::phrase('You must select a known file format, for example .doc, .docx, .jpg, .pdf, .png or .xls.'), 
					"\n\n",
					ze\admin::phrase('To add a file format to the known file format list, go to "Configuration -> Uploadable file types" in Organizer.'),
					"\n\n",
					ze\admin::phrase('Please also check that your filename does not contain any of the following characters: ' . "\n" . '\\ / : * ? " < > |');
				exit;
			}
			
			ze\fileAdm::exitIfUploadError();
			$file_id = ze\file::addToDatabase('hierarchial_file', $_FILES['Filedata']['tmp_name'], preg_replace('/([^.a-z0-9\s_]+)/i', '-',$_FILES['Filedata']['name']), false, false, true);
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
			
		} elseif ($_POST['rescan'] ?? false) {
			$file_id = ze\row::get('documents', 'file_id', ['id' => $ids]);
			$documentProperties = ze\document::addExtract($file_id);
			if (empty($documentProperties['extract']) || empty($documentProperties['thumbnail_id'])) {
				echo "<!--Message_Type:Error-->";
			} else {
				echo "<!--Message_Type:Success-->";
			}
			
			if (empty($documentProperties['extract'])) {
				echo '<p>', ze\admin::phrase('Unable to update document text extract.'), '</p>';
				
				if (!((ze\file::plainTextExtract(ze::moduleDir('zenario_common_features', 'fun/test_files/test.doc'), $extract))
					 && ($extract == 'Test'))) {
					echo '<p>', ze\admin::phrase('<code>antiword</code> or <code>pdftotext</code> do not appear to be working.'), '</p>';
					$externalProgramError = true;
				}
			} else {
				echo '<p>', ze\admin::phrase('Successfully updated document text extract.'), '</p>';
			}
			
			if (empty($documentProperties['thumbnail_id'])) {
				echo '<p>', ze\admin::phrase('Unable to update document image.'), '</p>';
				
				if (!ze\file::createPpdfFirstPageScreenshotPng(ze::moduleDir('zenario_common_features', 'fun/test_files/test.pdf'))) {
					echo '<p>', ze\admin::phrase('<code>ghostscript</code> does not appear to be working.'), '</p>';
					$externalProgramError = true;
				}
			} else {
				echo '<p>', ze\admin::phrase('Successfully updated document image.'), '</p>';
			}
			
			ze\row::update('documents', $documentProperties, ['id' => $ids]);
			
		} elseif ($_POST['rescan_image'] ?? false) {
			$file_id = ze\row::get('documents', 'file_id', ['id' => $ids]);
			$documentProperties = [];
			$extract = [];
			$thumbnailId = false;
			ze\file::updateDocumentPlainTextExtract($file_id, $extract, $thumbnailId);
			
			if ($thumbnailId) {
				$documentProperties['thumbnail_id'] = $thumbnailId;
				ze\row::update('documents', $documentProperties, ['id' => $ids]);
			}
			
		} elseif ($_POST['rescan_text'] ?? false) { 
			$file_id = ze\row::get('documents', 'file_id', ['id' => $ids]);
			$documentProperties = ze\document::addExtract($file_id);
			if (empty($documentProperties['extract'])) {
				echo "<!--Message_Type:Error-->";
			} else {
				echo "<!--Message_Type:Success-->";
			}
			if (empty($documentProperties['extract'])) {
				echo '<p>', ze\admin::phrase('Unable to update document text extract.'), '</p>';
				
				if (!((ze\file::plainTextExtract(ze::moduleDir('zenario_common_features', 'fun/test_files/test.doc'), $extract))
					 && ($extract == 'Test'))) {
					echo '<p>', ze\admin::phrase('<code>antiword</code> or <code>pdftotext</code> do not appear to be working.'), '</p>';
					$externalProgramError = true;
				}
			} else {
				echo "<p>Successfully updated document text extract.</p>";
				ze\row::update('documents', ['extract'=>$documentProperties['extract']], ['id' => $ids]);
			}
		
		//Remove all of the custom data from a document
		} elseif ($_POST['remove_metadata'] ?? false) {
			ze\priv::exitIfNot('_PRIV_EDIT_DOCUMENTS');
			if ($dataset = ze\dataset::details('documents')) {
				foreach (explode(',', $ids) as $id) {
					ze\row::delete('documents_custom_data', $id);
					ze\row::delete('custom_dataset_values_link', ['dataset_id' => $dataset['id'], 'linking_id' => $id]);
				}
			}
			
		} elseif ($_POST['delete'] ?? false) {
			ze\priv::exitIfNot('_PRIV_EDIT_DOCUMENTS');
			foreach (explode(',', $ids) as $id) {
				ze\document::delete($id);
			}
		} elseif ($_POST['generate_public_link'] ?? false) {
			$messageType = 'Success';
			$html = '';
			$idsArray = explode(',', $ids);
			$count = count($idsArray);
			
			foreach ($idsArray as $id) {
				$result = ze\document::generatePublicLink($id);
				
				if (ze::isError($result)) {
					
					//Show error message only if 1 item was selected
					if($count == 1) {
						$html .= $result->errors['message'] . '<br/>';
						$messageType = 'Error';
					}
				} else {
					
					//Show success message only if 1 item was selected
					if($count == 1) {
						$result = str_replace(' ', '%20', $result);
						$fullLink = ze\link::absolute() . $result;
						$internalLink = $result;
						$html .= '
							<div class="document_hyperlinks_message">
							<h3>The hyperlinks to your document are shown below:</h3>
							<div class="document_hyperlinks_content">
								<div class="document_hyperlinks_label">Full hyperlink:
									<span class="note"></span>
								</div>
								<div class="document_hyperlinks_field">
									<input type="text" style="width: 488px;" value="'. htmlspecialchars($fullLink). '"/>
								</div>
							</div>
							<div class="document_hyperlinks_content">
								<div class="document_hyperlinks_label">Internal hyperlink:
									<span class="note"></span>
								</div>
								<div class="document_hyperlinks_field">
									<input type="text" style="width: 488px;" value="'. htmlspecialchars($internalLink). '"/>
								</div>
							</div>
							</div>';
					}
				}			
			}
			
			if($count == 1) {
				echo '<!--Message_Type:' . $messageType . '-->' . $html;
			}
			
		} elseif ($_POST['make_document_private'] ?? false) {
			$idsArray = explode(',', $ids);
			$count = count($idsArray);
			
			foreach ($idsArray as $id) {
				ze\row::set('documents', ['privacy' => 'private'], ['id' => $id]);
				
				//Check if the file had a public link before, and remove it if necessary
				ze\document::deletePubliclink($id, $documentDeleted = false, $privacy = 'private');
			}
			
		} elseif($_POST['make_offline'] ?? false) {
			ze\priv::exitIfNot('_PRIV_EDIT_DOCUMENTS');
			$idsArray = explode(',', $ids);
			$count = count($idsArray);
			
			foreach ($idsArray as $id) {
				$result = ze\document::deletePubliclink($id);
				
				if ($result === true) {
					
					//Show success message only if 1 item was selected
					if($count == 1) {
						echo "<!--Message_Type:Success-->";
						echo 'Public link was deleted successfully.';
					}
				} else {
					
					//Show error message only if 1 item was selected
					if($count == 1) {
						echo '<!--Message_Type:Error-->';
						echo $result;
					}
				}
			}
		} elseif ($_POST['regenerate_public_links'] ?? false) {
			ze\priv::exitIfNot('_PRIV_REGENERATE_DOCUMENT_PUBLIC_LINKS');
			$errors = $exampleFile = false;
			ze\document::checkAllPublicLinks($forceRemake = true, $errors, $exampleFile);
		}
		
		if ($externalProgramError) {
			echo
				'<p>', ze\admin::phrase('Please go to <a href="[[href]]">Configuration->Site Settings->External</a> programs for help to remedy this.',
					['href' => '#zenario__administration/panels/site_settings//external_programs']
				), '</p>';
		}
	}
	
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		$document = ze\row::get('documents', ['file_id', 'filename'], $ids);
		ze\file::stream($document['file_id'], $document['filename']);
		exit;
	}
	
}