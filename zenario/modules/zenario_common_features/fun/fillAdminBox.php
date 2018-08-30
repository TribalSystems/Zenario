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

switch ($path) {
	case 'plugin_settings':
	case 'plugin_css_and_framework':
		return require ze::funIncPath(__FILE__, 'plugin_settings.fillAdminBox');
	
	case 'zenario_content_categories':
		
		$box['key']['originalId'] = $box['key']['id'];
		
		$total = 0;
		$tagSQL = "";
		$tagIds = [];
		$equivId = $cType = false;
		
		if (($_REQUEST['equivId'] ?? false) && ($_REQUEST['cType'] ?? false)) {
			$box['key']['id'] = ($_REQUEST['cType'] ?? false). '_'. ($_REQUEST['equivId'] ?? false);
		
		} elseif (($_REQUEST['cID'] ?? false) && ($_REQUEST['cType'] ?? false)) {
			$box['key']['id'] = ($_REQUEST['cType'] ?? false). '_'. ($_REQUEST['cID'] ?? false);
		}
		
		//Given a list of tag ids using cID and cType, convert them to equivIds and cTypes
		foreach (ze\ray::explodeAndTrim($box['key']['id']) as $tagId) {
			if (ze\content::getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
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
		
		
		ze\categoryAdm::setupFABCheckboxes($fields['categories/categories'], false);
		//foreach ($fields['categories/categories']['values'] as $checkbox){
		
		if (empty($fields['categories/categories']['values'])) {
			unset($box['tabs']['categories']['edit_mode']);
			$fields['categories/categories']['hidden'] = true;
		
		}	
		else {
			
			$fields['categories/no_categories']['hidden'] = true;
			$box['tabs']['categories']['fields']['desc']['snippet']['html'] = 
				ze\admin::phrase('You can put content item(s) into one or more categories. (<a[[link]]>Define categories</a>.)',
					['link' => ' href="'. htmlspecialchars(ze\link::absolute(). 'zenario/admin/organizer.php#zenario__content/panels/categories'). '" target="_blank"']);
			
			
			$inCats = [];
			$sql = "
				SELECT l.category_id, COUNT(DISTINCT c.tag_id) AS cnt
				FROM ". DB_PREFIX. "content_items AS c
				INNER JOIN ". DB_PREFIX. "category_item_link AS l
				   ON c.equiv_id = l.equiv_id
				  AND c.type = l.content_type
				WHERE c.tag_id IN (". ze\escape::in($tagIds). ")
				GROUP BY l.category_id";
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
							
					
					
					if (isset($fields['categories/categories']['values'][$row['category_id']])) {
						$inCats[] = $row['category_id'];
					
							if($fields['categories/categories']['values'][$row['category_id']]){
								$fields['categories/categories']['values'][$row['category_id']]['disabled'] = false;
									if ($total > 1) {
										$row['total'] = $total;
										if ($row['cnt'] == $total) {
											$fields['categories/categories']['values'][$row['category_id']]['label'] .=
											' '. ze\admin::phrase('(all [[total]] in this category)', $row);
										} else {
											$fields['categories/categories']['values'][$row['category_id']]['label'] .=
											' '. ze\admin::phrase('([[cnt]] of [[total]] in this category)', $row);
											}
									}
							}		
					}
					
							
						
			$values['categories/categories'] = ze\escape::in($inCats, false);
			//$values['categories_add/categories_add'] = ze\escape::in($inCats, false);
			//$values['categories_remove/categories_remove'] = ze\escape::in($inCats, false);
		}
		}
		$numLanguages = ze\lang::count();
		if ($numLanguages > 1) {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					ze\admin::phrase('This will update the categories of [[count]] content items and their translations.',
						['count' => $total]);
				
				$box['title'] =
					ze\admin::phrase('Changing categories for [[count]] content items and their translations',
						['count' => $total]);
			} else {
				$box['title'] =
					ze\admin::phrase('Changing categories for the content item "[[tag]]" and its translations',
						['tag' => ze\content::formatTag($equivId, $cType)]);
			}
			
		} else {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					ze\admin::phrase('This will update the categories of [[count]] content items.',
						['count' => $total]);
				
				$box['title'] =
					ze\admin::phrase('Changing categories for [[count]] content items',
						['count' => $total]);
			} else {
				$box['title'] =
					ze\admin::phrase('Changing categories for the content item "[[tag]]"',
						['tag' => ze\content::formatTag($equivId, $cType)]);
			}
		}
		
		if ($total > 1) {
			$box['confirm']['message'] .=
				"\n\n".
				ze\admin::phrase('The content items in all selected translation chains will be set to the categories you selected.');
		}
		
		break;
		
	case 'zenario_content_categories_add':
		
		$box['key']['originalId'] = $box['key']['id'];
		
		$total = 0;
		$tagSQL = "";
		$tagIds = [];
		$equivId = $cType = false;
		
		if (($_REQUEST['equivId'] ?? false) && ($_REQUEST['cType'] ?? false)) {
			$box['key']['id'] = ($_REQUEST['cType'] ?? false). '_'. ($_REQUEST['equivId'] ?? false);
		
		} elseif (($_REQUEST['cID'] ?? false) && ($_REQUEST['cType'] ?? false)) {
			$box['key']['id'] = ($_REQUEST['cType'] ?? false). '_'. ($_REQUEST['cID'] ?? false);
		}
		
		//Given a list of tag ids using cID and cType, convert them to equivIds and cTypes
		foreach (ze\ray::explodeAndTrim($box['key']['id']) as $tagId) {
			if (ze\content::getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
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
		ze\categoryAdm::setupFABCheckboxes($fields['categories_add/categories_add'], false);
		//setup category boxes for removing categories
		
		
		if (empty($fields['categories_add/categories_add']['values'])) {
			unset($box['tabs']['categories_add']['edit_mode']);
			$fields['categories_add/categories_add']['hidden'] = true;
		
		}	
		else {
			$fields['categories_add/no_categories']['hidden'] = true;
			
			$box['tabs']['categories_add']['fields']['desc']['snippet']['html'] = 
				ze\admin::phrase('You can put content item(s) into one or more categories. (<a[[link]]>Define categories</a>.)',
					['link' => ' href="'. htmlspecialchars(ze\link::absolute(). 'zenario/admin/organizer.php#zenario__content/panels/categories'). '" target="_blank"']);
			
			
			$inCats = [];
			$sql = "
				SELECT l.category_id, COUNT(DISTINCT c.tag_id) AS cnt
				FROM ". DB_PREFIX. "content_items AS c
				INNER JOIN ". DB_PREFIX. "category_item_link AS l
				   ON c.equiv_id = l.equiv_id
				  AND c.type = l.content_type
				WHERE c.tag_id IN (". ze\escape::in($tagIds). ")
				GROUP BY l.category_id";
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
							
					if (isset($fields['categories_add/categories_add']['values'][$row['category_id']])) {
						$inCats[] = $row['category_id'];
					
							if($fields['categories_add/categories_add']['values'][$row['category_id']]){
								$fields['categories_add/categories_add']['values'][$row['category_id']]['disabled'] = false;
									if ($total > 1) {
										$row['total'] = $total;
										if ($row['cnt'] == $total) {
											$fields['categories_add/categories_add']['values'][$row['category_id']]['label'] .=
											' '. ze\admin::phrase('(all [[total]] in this category)', $row);
										} else {
											$fields['categories_add/categories_add']['values'][$row['category_id']]['label'] .=
											' '. ze\admin::phrase('([[cnt]] of [[total]] in this category)', $row);
											}
									}
							}		
					}
					
					
						
			//$values['categories/categories'] = ze\escape::in($inCats, false);
			//$values['categories_add/categories_add'] = ze\escape::in($inCats, false);
			//$values['categories_remove/categories_remove'] = ze\escape::in($inCats, false);
		}
		}
		$numLanguages = ze\lang::count();
		if ($numLanguages > 1) {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					ze\admin::phrase('This will update the categories of [[count]] content items and their translations.',
						['count' => $total]);
				
				$box['title'] =
					ze\admin::phrase('Changing categories for [[count]] content items and their translations',
						['count' => $total]);
			} else {
				$box['title'] =
					ze\admin::phrase('Changing categories for the content item "[[tag]]" and its translations',
						['tag' => ze\content::formatTag($equivId, $cType)]);
			}
			
		} else {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					ze\admin::phrase('This will update the categories of [[count]] content items.',
						['count' => $total]);
				
				$box['title'] =
					ze\admin::phrase('Changing categories for [[count]] content items',
						['count' => $total]);
			} else {
				$box['title'] =
					ze\admin::phrase('Changing categories for the content item "[[tag]]"',
						['tag' => ze\content::formatTag($equivId, $cType)]);
			}
		}
		
		if ($total > 1) {
			$box['confirm']['message'] .=
				"\n\n".
				ze\admin::phrase('The content items in all selected translation chains will be set to the categories you selected.');
		}
		
		break;
		
	case 'zenario_content_categories_remove':
		
		$box['key']['originalId'] = $box['key']['id'];
		
		$total = 0;
		$tagSQL = "";
		$tagIds = [];
		$equivId = $cType = false;
		
		if (($_REQUEST['equivId'] ?? false) && ($_REQUEST['cType'] ?? false)) {
			$box['key']['id'] = ($_REQUEST['cType'] ?? false). '_'. ($_REQUEST['equivId'] ?? false);
		
		} elseif (($_REQUEST['cID'] ?? false) && ($_REQUEST['cType'] ?? false)) {
			$box['key']['id'] = ($_REQUEST['cType'] ?? false). '_'. ($_REQUEST['cID'] ?? false);
		}
		
		//Given a list of tag ids using cID and cType, convert them to equivIds and cTypes
		foreach (ze\ray::explodeAndTrim($box['key']['id']) as $tagId) {
			if (ze\content::getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
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
		ze\categoryAdm::setupFABCheckboxes($fields['categories_remove/categories_remove'], false);
		
		//foreach ($fields['categories/categories']['values'] as $checkbox){
		
			$inCats = [];
			$sql = "
				SELECT l.category_id, COUNT(DISTINCT c.tag_id) AS cnt
				FROM ". DB_PREFIX. "content_items AS c
				INNER JOIN ". DB_PREFIX. "category_item_link AS l
				   ON c.equiv_id = l.equiv_id
				  AND c.type = l.content_type
				WHERE c.tag_id IN (". ze\escape::in($tagIds). ")
				GROUP BY l.category_id";
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
					
					if (isset($fields['categories_remove/categories_remove']['values'][$row['category_id']])) {
						$inCats[] = $row['category_id'];
								if ($total > 1) {
									$row['total'] = $total;
									if ($row['cnt'] == $total) {
										$fields['categories_remove/categories_remove']['values'][$row['category_id']]['label'] .=
										' '. ze\admin::phrase('(all [[total]] in this category)', $row);
									} else {
										$fields['categories_remove/categories_remove']['values'][$row['category_id']]['label'] .=
										' '. ze\admin::phrase('([[cnt]] of [[total]] in this category)', $row);
										}
								}
				}
			}
			
			foreach ($fields['categories_remove/categories_remove']['values'] as $key => $category) {
				if(!in_array($key, $inCats)) {
					$fields['categories_remove/categories_remove']['values'][$key]['hidden'] = true;
				}
			}
			
						
			//$values['categories/categories'] = ze\escape::in($inCats, false);
			//$values['categories_add/categories_add'] = ze\escape::in($inCats, false);
			//$values['categories_remove/categories_remove'] = ze\escape::in($inCats, false);
		
		
		$numLanguages = ze\lang::count();
		if ($numLanguages > 1) {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					ze\admin::phrase('This will update the categories of [[count]] content items and their translations.',
						['count' => $total]);
				
				$box['title'] =
					ze\admin::phrase('Changing categories for [[count]] content items and their translations',
						['count' => $total]);
			} else {
				$box['title'] =
					ze\admin::phrase('Changing categories for the content item "[[tag]]" and its translations',
						['tag' => ze\content::formatTag($equivId, $cType)]);
			}
			
		} else {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					ze\admin::phrase('This will update the categories of [[count]] content items.',
						['count' => $total]);
				
				$box['title'] =
					ze\admin::phrase('Changing categories for [[count]] content items',
						['count' => $total]);
			} else {
				$box['title'] =
					ze\admin::phrase('Changing categories for the content item "[[tag]]"',
						['tag' => ze\content::formatTag($equivId, $cType)]);
			}
		}
		
		if ($total > 1) {
			$box['confirm']['message'] .=
				"\n\n".
				ze\admin::phrase('The content items in all selected translation chains will be set to the categories you selected.');
		}
		
		break;
	
	
	case 'zenario_document_folder':
		if (isset($box['key']['add_folder']) && $box['key']['add_folder']) {
			$parentFolderDetails = 
				ze\row::get(
					'documents',
					['folder_name'], $box['key']['id']);
			$box['title'] = ze\admin::phrase('Create a subfolder inside "[[folder_name]]".', $parentFolderDetails);
		} elseif ($folderDetails = ze\row::get('documents', ['folder_name'], $box['key']['id'])) {
			$values['details/folder_name'] = $folderDetails['folder_name'];
			$box['title'] = ze\admin::phrase('Editing folder "[[folder_name]]".', $folderDetails);
		}
		break;
		
	case 'zenario_document_tag':
		if ($tagDetails = ze\row::get('document_tags', ['tag_name'], $box['key']['id'])) {
			$values['details/tag_name'] = $tagDetails['tag_name'];
			$box['title'] = ze\admin::phrase('Editing tag "[[tag_name]]".', $tagDetails);
		}
		break;
	
	case 'zenario_reorder_documents':
		if ($box['key']['id']){
			$folderId = $box['key']['id'];
			$folderName = ze\row::get('documents', 'folder_name', ['id' => $folderId]);
			//$box['title'] = ze\admin::phrase('Renaming/adding a title to the image "[[folder_name]]".', $folderName);
			$box['title'] = "Re-order documents for the folder: '".$folderName."'";
		}else{
			$box['title'] = "Re-order documents";
		}
	
		$datasetDetails = ze\dataset::details('documents');
		$datasetId = $datasetDetails['id'];
		$datesetFields = [];
		if ($datasetDetails = ze\row::getAssocs('custom_dataset_fields', true, ['dataset_id' => $datasetId, 'is_system_field' => 0])) {
			foreach ($datasetDetails as $details) {
				if($details['type'] == 'text' || $details['type'] == 'date') {
					$datesetFields[]= $details;
				}
				
			}
			$i = 3;
			foreach ($datesetFields as $dataset){
				$box['tabs']['details']['fields']['reorder']['values'][$dataset['id']] = 
					['label' => $dataset['label'] . " - (custom dataset field)", 'ord' => $i];
				$i++;
			}
		}
		
	break;
	
	case 'zenario_document_rename':
			$documentId = $box['key']['id'];
			
			$isfolder=ze\row::get('documents', 'type', ['type' => 'folder','id' => $documentId]);
			
			if ($isfolder){
				$documentName=ze\row::get('documents', 'folder_name', ['type' => 'folder','id' => $documentId]);
				$box['title'] = 'Renaming the folder "'.$documentName.'"';
			}else{
				$documentName=ze\row::get('documents', 'filename', ['type' => 'file','id' => $documentId]);
				$box['title'] = 'Renaming the file "'.$documentName.'"';
			}
			$values['details/document_name'] = $documentName;
		break;
	
	
}

return false;
