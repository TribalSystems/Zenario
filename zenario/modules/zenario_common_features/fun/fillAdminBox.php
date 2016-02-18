<?php
/*
 * Copyright (c) 2016, Tribal Limited
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
	case 'plugin_settings':
	case 'plugin_css_and_framework':
		return require funIncPath(__FILE__, 'plugin_settings.fillAdminBox');
	
	
	case 'zenario_content':
	case 'zenario_quick_create':
		return require funIncPath(__FILE__, 'content.fillAdminBox');
	
	
	case 'zenario_content_categories':
		
		$box['key']['originalId'] = $box['key']['id'];
		
		$total = 0;
		$tagSQL = "";
		$tagIds = array();
		$equivId = $cType = false;
		
		if (request('equivId') && request('cType')) {
			$box['key']['id'] = request('cType'). '_'. request('equivId');
		
		} elseif (request('cID') && request('cType')) {
			$box['key']['id'] = request('cType'). '_'. request('cID');
		}
		
		//Given a list of tag ids using cID and cType, convert them to equivIds and cTypes
		foreach (explodeAndTrim($box['key']['id']) as $tagId) {
			if (getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
				$tagId = $cType. '_'. $equivId;
				if (!isset($tagIds[$tagId])) {
					$tagIds[$tagId] = $tagId;
					++$total;
				}
			}
		}
		
		if (empty($tagIds)) {
			exit;
		} else {
			$box['key']['id'] = implode(',', $tagIds);
		}
		
		
		setupCategoryCheckboxes($fields['categories/categories'], false);
		//foreach ($fields['categories/categories']['values'] as $checkbox){
		
		if (empty($fields['categories/categories']['values'])) {
			unset($box['tabs']['categories']['edit_mode']);
			$fields['categories/categories']['hidden'] = true;
		
		}	
		else {
			
			$fields['categories/no_categories']['hidden'] = true;
			$box['tabs']['categories']['fields']['desc']['snippet']['html'] = 
				adminPhrase('You can put content item(s) into one or more categories. (<a[[link]]>Define categories</a>.)',
					array('link' => ' href="'. htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__content/panels/categories'). '" target="_blank"'));
			
			
			$inCats = array();
			$sql = "
				SELECT l.category_id, COUNT(DISTINCT c.tag_id) AS cnt
				FROM ". DB_NAME_PREFIX. "content_items AS c
				INNER JOIN ". DB_NAME_PREFIX. "category_item_link AS l
				   ON c.equiv_id = l.equiv_id
				  AND c.type = l.content_type
				WHERE c.tag_id IN (". inEscape($tagIds). ")
				GROUP BY l.category_id";
			$result = sqlQuery($sql);
			while ($row = sqlFetchAssoc($result)) {
							
					
					
					if (isset($fields['categories/categories']['values'][$row['category_id']])) {
						$inCats[] = $row['category_id'];
					
							if($fields['categories/categories']['values'][$row['category_id']]){
								$fields['categories/categories']['values'][$row['category_id']]['disabled'] = false;
									if ($total > 1) {
										$row['total'] = $total;
										if ($row['cnt'] == $total) {
											$fields['categories/categories']['values'][$row['category_id']]['label'] .=
											' '. adminPhrase('(all [[total]] in this category)', $row);
										} else {
											$fields['categories/categories']['values'][$row['category_id']]['label'] .=
											' '. adminPhrase('([[cnt]] of [[total]] in this category)', $row);
											}
									}
							}		
					}
					
							
						
			$values['categories/categories'] = inEscape($inCats, false);
			//$values['categories_add/categories_add'] = inEscape($inCats, false);
			//$values['categories_remove/categories_remove'] = inEscape($inCats, false);
		}
		}
		$numLanguages = getNumLanguages();
		if ($numLanguages > 1) {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					adminPhrase('This will update the categories of [[count]] content items and their translations.',
						array('count' => $total));
				
				$box['title'] =
					adminPhrase('Changing categories for [[count]] content items and their translations',
						array('count' => $total));
			} else {
				$box['title'] =
					adminPhrase('Changing categories for the content item "[[tag]]" and its translations',
						array('tag' => formatTag($equivId, $cType)));
			}
			
		} else {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					adminPhrase('This will update the categories of [[count]] content items.',
						array('count' => $total));
				
				$box['title'] =
					adminPhrase('Changing categories for [[count]] content items',
						array('count' => $total));
			} else {
				$box['title'] =
					adminPhrase('Changing categories for the content item "[[tag]]"',
						array('tag' => formatTag($equivId, $cType)));
			}
		}
		
		if ($total > 1) {
			$box['confirm']['message'] .=
				"\n\n".
				adminPhrase('The content items in all selected translation chains will be set to the categories you selected.');
		}
		
		break;
		
	case 'zenario_content_categories_add':
		
		$box['key']['originalId'] = $box['key']['id'];
		
		$total = 0;
		$tagSQL = "";
		$tagIds = array();
		$equivId = $cType = false;
		
		if (request('equivId') && request('cType')) {
			$box['key']['id'] = request('cType'). '_'. request('equivId');
		
		} elseif (request('cID') && request('cType')) {
			$box['key']['id'] = request('cType'). '_'. request('cID');
		}
		
		//Given a list of tag ids using cID and cType, convert them to equivIds and cTypes
		foreach (explodeAndTrim($box['key']['id']) as $tagId) {
			if (getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
				$tagId = $cType. '_'. $equivId;
				if (!isset($tagIds[$tagId])) {
					$tagIds[$tagId] = $tagId;
					++$total;
				}
			}
		}
		
		if (empty($tagIds)) {
			exit;
		} else {
			$box['key']['id'] = implode(',', $tagIds);
		}
		
		
		
		//setupcategory boxes for adding categories
		setupCategoryCheckboxes($fields['categories_add/categories_add'], false);
		//setup category boxes for removing categories
		
		
		if (empty($fields['categories_add/categories_add']['values'])) {
			unset($box['tabs']['categories_add']['edit_mode']);
			$fields['categories_add/categories_add']['hidden'] = true;
		
		}	
		else {
			$fields['categories_add/no_categories']['hidden'] = true;
			
			$box['tabs']['categories_add']['fields']['desc']['snippet']['html'] = 
				adminPhrase('You can put content item(s) into one or more categories. (<a[[link]]>Define categories</a>.)',
					array('link' => ' href="'. htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__content/panels/categories'). '" target="_blank"'));
			
			
			$inCats = array();
			$sql = "
				SELECT l.category_id, COUNT(DISTINCT c.tag_id) AS cnt
				FROM ". DB_NAME_PREFIX. "content_items AS c
				INNER JOIN ". DB_NAME_PREFIX. "category_item_link AS l
				   ON c.equiv_id = l.equiv_id
				  AND c.type = l.content_type
				WHERE c.tag_id IN (". inEscape($tagIds). ")
				GROUP BY l.category_id";
			$result = sqlQuery($sql);
			while ($row = sqlFetchAssoc($result)) {
							
					if (isset($fields['categories_add/categories_add']['values'][$row['category_id']])) {
						$inCats[] = $row['category_id'];
					
							if($fields['categories_add/categories_add']['values'][$row['category_id']]){
								$fields['categories_add/categories_add']['values'][$row['category_id']]['disabled'] = false;
									if ($total > 1) {
										$row['total'] = $total;
										if ($row['cnt'] == $total) {
											$fields['categories_add/categories_add']['values'][$row['category_id']]['label'] .=
											' '. adminPhrase('(all [[total]] in this category)', $row);
										} else {
											$fields['categories_add/categories_add']['values'][$row['category_id']]['label'] .=
											' '. adminPhrase('([[cnt]] of [[total]] in this category)', $row);
											}
									}
							}		
					}
					
					
						
			//$values['categories/categories'] = inEscape($inCats, false);
			//$values['categories_add/categories_add'] = inEscape($inCats, false);
			//$values['categories_remove/categories_remove'] = inEscape($inCats, false);
		}
		}
		$numLanguages = getNumLanguages();
		if ($numLanguages > 1) {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					adminPhrase('This will update the categories of [[count]] content items and their translations.',
						array('count' => $total));
				
				$box['title'] =
					adminPhrase('Changing categories for [[count]] content items and their translations',
						array('count' => $total));
			} else {
				$box['title'] =
					adminPhrase('Changing categories for the content item "[[tag]]" and its translations',
						array('tag' => formatTag($equivId, $cType)));
			}
			
		} else {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					adminPhrase('This will update the categories of [[count]] content items.',
						array('count' => $total));
				
				$box['title'] =
					adminPhrase('Changing categories for [[count]] content items',
						array('count' => $total));
			} else {
				$box['title'] =
					adminPhrase('Changing categories for the content item "[[tag]]"',
						array('tag' => formatTag($equivId, $cType)));
			}
		}
		
		if ($total > 1) {
			$box['confirm']['message'] .=
				"\n\n".
				adminPhrase('The content items in all selected translation chains will be set to the categories you selected.');
		}
		
		break;
		
	case 'zenario_content_categories_remove':
		
		$box['key']['originalId'] = $box['key']['id'];
		
		$total = 0;
		$tagSQL = "";
		$tagIds = array();
		$equivId = $cType = false;
		
		if (request('equivId') && request('cType')) {
			$box['key']['id'] = request('cType'). '_'. request('equivId');
		
		} elseif (request('cID') && request('cType')) {
			$box['key']['id'] = request('cType'). '_'. request('cID');
		}
		
		//Given a list of tag ids using cID and cType, convert them to equivIds and cTypes
		foreach (explodeAndTrim($box['key']['id']) as $tagId) {
			if (getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
				$tagId = $cType. '_'. $equivId;
				if (!isset($tagIds[$tagId])) {
					$tagIds[$tagId] = $tagId;
					++$total;
				}
			}
		}
		
		if (empty($tagIds)) {
			exit;
		} else {
			$box['key']['id'] = implode(',', $tagIds);
		}
		
		
		
		//setup category boxes for removing categories
		setupCategoryCheckboxes($fields['categories_remove/categories_remove'], false);
		
		//foreach ($fields['categories/categories']['values'] as $checkbox){
		
			$inCats = array();
			$sql = "
				SELECT l.category_id, COUNT(DISTINCT c.tag_id) AS cnt
				FROM ". DB_NAME_PREFIX. "content_items AS c
				INNER JOIN ". DB_NAME_PREFIX. "category_item_link AS l
				   ON c.equiv_id = l.equiv_id
				  AND c.type = l.content_type
				WHERE c.tag_id IN (". inEscape($tagIds). ")
				GROUP BY l.category_id";
			$result = sqlQuery($sql);
			while ($row = sqlFetchAssoc($result)) {
					
					if (isset($fields['categories_remove/categories_remove']['values'][$row['category_id']])) {
						$inCats[] = $row['category_id'];
								if ($total > 1) {
									$row['total'] = $total;
									if ($row['cnt'] == $total) {
										$fields['categories_remove/categories_remove']['values'][$row['category_id']]['label'] .=
										' '. adminPhrase('(all [[total]] in this category)', $row);
									} else {
										$fields['categories_remove/categories_remove']['values'][$row['category_id']]['label'] .=
										' '. adminPhrase('([[cnt]] of [[total]] in this category)', $row);
										}
								}
				}
			}
			
			foreach ($fields['categories_remove/categories_remove']['values'] as $key => $category) {
				if(!in_array($key, $inCats)) {
					$fields['categories_remove/categories_remove']['values'][$key]['hidden'] = true;
				}
			}
			
						
			//$values['categories/categories'] = inEscape($inCats, false);
			//$values['categories_add/categories_add'] = inEscape($inCats, false);
			//$values['categories_remove/categories_remove'] = inEscape($inCats, false);
		
		
		$numLanguages = getNumLanguages();
		if ($numLanguages > 1) {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					adminPhrase('This will update the categories of [[count]] content items and their translations.',
						array('count' => $total));
				
				$box['title'] =
					adminPhrase('Changing categories for [[count]] content items and their translations',
						array('count' => $total));
			} else {
				$box['title'] =
					adminPhrase('Changing categories for the content item "[[tag]]" and its translations',
						array('tag' => formatTag($equivId, $cType)));
			}
			
		} else {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					adminPhrase('This will update the categories of [[count]] content items.',
						array('count' => $total));
				
				$box['title'] =
					adminPhrase('Changing categories for [[count]] content items',
						array('count' => $total));
			} else {
				$box['title'] =
					adminPhrase('Changing categories for the content item "[[tag]]"',
						array('tag' => formatTag($equivId, $cType)));
			}
		}
		
		if ($total > 1) {
			$box['confirm']['message'] .=
				"\n\n".
				adminPhrase('The content items in all selected translation chains will be set to the categories you selected.');
		}
		
		break;
	
	
	case 'zenario_document_folder':
		if (isset($box['key']['add_folder']) && $box['key']['add_folder']) {
			$parentFolderDetails = 
				getRow(
					'documents',
					array('folder_name'), $box['key']['id']);
			$box['title'] = adminPhrase('Create a subfolder inside "[[folder_name]]".', $parentFolderDetails);
		} elseif ($folderDetails = getRow('documents', array('folder_name'), $box['key']['id'])) {
			$values['details/folder_name'] = $folderDetails['folder_name'];
			$box['title'] = adminPhrase('Editing folder "[[folder_name]]".', $folderDetails);
		}
		break;
		
	case 'zenario_document_tag':
		if ($tagDetails = getRow('document_tags', array('tag_name'), $box['key']['id'])) {
			$values['details/tag_name'] = $tagDetails['tag_name'];
			$box['title'] = adminPhrase('Editing tag "[[tag_name]]".', $tagDetails);
		}
		break;
	
	case 'zenario_document_properties':
		if ($document_id = $box['key']['id']) {
			$documentTagsString = '';
			
			$documentDetails = getRow('documents',array('file_id', 'thumbnail_id', 'extract', 'extract_wordcount', 'title'),  $document_id);
			$documentName = getRow('documents', 'filename', array('type' => 'file','id' => $document_id));
			$box['title'] = adminPhrase('Editing metadata for document "[[filename]]".', array("filename"=>$documentName));
			
			$fields['details/document_title']['value'] = $documentDetails['title'];
			$fields['details/document_name']['value'] = $documentName;
			$fileDatetime=getRow('documents', 'file_datetime', array('type' => 'file','id' => $document_id));
			$fields['details/date_uploaded']['value'] = date('jS F Y H:i', strtotime($fileDatetime));
			
			if (setting('enable_document_tags')) {
				$documentTags = getRowsArray('document_tag_link', 'tag_id', array('document_id' => $document_id));
				foreach ($documentTags as $tag) {
					$documentTagsString .= $tag . ",";
				}
				$fields['details/tags']['value'] = $documentTagsString;
				$fields['details/link_to_add_tags']['snippet']['html'] = 
						adminPhrase('To add or edit document tags click <a[[link]]>this link</a>.',
							array('link' => ' href="'. htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__content/panels/document_tags'). '" target="_blank"'));
			} else {
				$fields['details/tags']['hidden'] = true;
			}
			
			$fields['extract/extract_wordcount']['value'] = $documentDetails['extract_wordcount'];
			$fields['extract/extract']['value'] = ($documentDetails['extract'] ? $documentDetails['extract']: 'No plain-text extract');
			
			// Add a preview image for JPEG/PNG/GIF images 
			if (!empty($documentDetails['thumbnail_id'])) {
				$this->getImageHtmlSnippet($documentDetails['thumbnail_id'], $fields['upload_image/thumbnail_image']['snippet']['html']);
			} else {
				$fields['upload_image/delete_thumbnail_image']['hidden'] = true;
				$mimeType = getRow('files', 'mime_type', $documentDetails['file_id']);
				if ($mimeType == 'image/gif' || $mimeType == 'image/png' || $mimeType == 'image/jpeg' || $mimeType == 'image/pjpeg') {
					$this->getImageHtmlSnippet($documentDetails['file_id'], $fields['upload_image/thumbnail_image']['snippet']['html']);
				} else {
					$fields['upload_image/thumbnail_image']['snippet']['html'] = adminPhrase('No thumbnail avaliable');
				}
			}
			
		}
		break;
		
	case 'zenario_migrate_old_documents':
		// Only show button if ctype_document module is running
		if (getModuleStatusByClassName('zenario_ctype_document') == 'module_running') {
			if ($box['key']['id']) {
				
				$fields['details/html']['snippet']['html'] = adminPhrase('For each document meta data field below, select a dataset field to migrate the data to. If no dataset field is chosen then the data won\'t be saved. (<a [[link]]>Edit dataset fields</a>)', array('link' => 'href="'. htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__administration/panels/custom_datasets'). '" target="_blank"'));
				
				// Set select lists for dataset fields
				$link = '';
				$datasetDetails = getDatasetDetails('documents');
				if ($details = getDatasetDetails('documents')) {
					$link = absCMSDirURL(). 'zenario/admin/organizer.php?#zenario__administration/panels/custom_datasets/item//'.$details['id'].'//';
				}
				$textDocumentDatasetFields = 
					getRowsArray('custom_dataset_fields', 'label', array('type' => 'text', 'dataset_id' => $datasetDetails['id']));
				if (empty($textDocumentDatasetFields)) {
					$fields['details/title']['hidden'] = $fields['details/language_id']['hidden'] = true;
					$fields['details/title_warning']['hidden'] = $fields['details/language_id_warning']['hidden'] = false;
					$fields['details/title_warning']['snippet']['html'] = 
					$fields['details/language_id_warning']['snippet']['html'] = 
						'No "Text" type fields found in the document dataset, go <a href="'.$link.'">here</a> to create one.';
				} else {
					$fields['details/title']['values'] = $fields['details/language_id']['values'] = $textDocumentDatasetFields;
				}
				$textAreaDocumentDatasetFields = 
					getRowsArray('custom_dataset_fields', 'label', array('type' => 'textarea', 'dataset_id' => $datasetDetails['id']));
				if (empty($textAreaDocumentDatasetFields)) {
					$fields['details/description']['hidden'] = $fields['details/keywords']['hidden'] = true;
					$fields['details/description_warning']['hidden'] = $fields['details/keywords_warning']['hidden'] = false;
					$fields['details/description_warning']['snippet']['html'] = 
					$fields['details/keywords_warning']['snippet']['html'] = 
						'No "Textarea" type fields found in the document dataset, go <a href="'.$link.'" target="_blank">here</a> to create one.';
				} else {
					$fields['details/description']['values'] = $fields['details/keywords']['values'] = $textAreaDocumentDatasetFields;
				}
				$editorDocumentDatasetFields = 
					getRowsArray('custom_dataset_fields', 'label', array('type' => 'editor', 'dataset_id' => $datasetDetails['id']));
				if (empty($editorDocumentDatasetFields)) {
					$fields['details/content_summary']['hidden'] = true;
					$fields['details/content_summary_warning']['hidden'] = false;
					$fields['details/content_summary_warning']['snippet']['html'] = 'No "Editor" type fields found in the document dataset, go <a href="'.$link.'">here</a> to create one.';
				} else {
					$fields['details/content_summary']['values'] = $editorDocumentDatasetFields;
				}
			}
		}
		break;
	
	
	case 'zenario_reorder_documents':
		if ($box['key']['id']){
			$folderId = $box['key']['id'];
			$folderName = getRow('documents', 'folder_name', array('id' => $folderId));
			//$box['title'] = adminPhrase('Renaming/adding a title to the image "[[folder_name]]".', $folderName);
			$box['title'] = "Re-order documents for the folder: '".$folderName."'";
		}else{
			$box['title'] = "Re-order documents";
		}
	
		$datasetDetails = getDatasetDetails('documents');
		$datasetId = $datasetDetails['id'];
		if ($datasetDetails = getRowsArray('custom_dataset_fields', true, array('dataset_id' => $datasetId, 'type' => array('!' => 'other_system_field')))) {
			foreach ($datasetDetails as $details) {
				if($details['type'] == 'text' || $details['type'] == 'date') {
					$datesetFields[]= $details;
				}
				
			}
			$i = 3;
			foreach ($datesetFields as $dataset){
				$box['tabs']['details']['fields']['reorder']['values'][$dataset['id']] = 
					array('label' => $dataset['label'] . " - (custom dataset field)", 'ord' => $i);
				$i++;
			}
		}
		
	break;
	
	case 'zenario_document_upload':
	
		$folderDetails= getRow('documents', array('id','folder_name'), array('id' => $box['key']['id'],'type'=>'folder'));
		if ($folderDetails) {
			$box['title'] = 'Uploading document for the folder "'.$folderDetails['folder_name'].'"';
			$documentProperties['folder_id'] = $box['key']['id'];
		}
		break;
	
	case 'zenario_document_rename':
			$documentId = $box['key']['id'];
			
			$isfolder=getRow('documents', 'type', array('type' => 'folder','id' => $documentId));
			
			if ($isfolder){
				$documentName=getRow('documents', 'folder_name', array('type' => 'folder','id' => $documentId));
				$box['title'] = 'Renaming the folder "'.$documentName.'"';
			}else{
				$documentName=getRow('documents', 'filename', array('type' => 'file','id' => $documentId));
				$box['title'] = 'Renaming the file "'.$documentName.'"';
			}
			$values['details/document_name'] = $documentName;
		break;
	
	
}

return false;
