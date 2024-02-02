<?php
/*
 * Copyright (c) 2024, Tribal Limited
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


class zenario_common_features__admin_boxes__content_categories extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id_is_menu_node_id']) {
			$box['key']['menu_node_id'] = $box['key']['id'];
			
			$menuContentItem = ze\menu::getContentItem($box['key']['id']);
			$box['key']['id'] = $menuContentItem['content_type'] . '_' . $menuContentItem['content_id'];
		}

		$box['key']['originalId'] = $box['key']['id'];
		
		$total = 0;
		$tagSQL = "";
		$tagIds = [];
		$equivId = $cType = false;
		
		if (ze::request('equivId') && ze::request('cType')) {
			$box['key']['id'] = ze::request('cType'). '_'. ze::request('equivId');
		
		} elseif (ze::request('cID') && ze::request('cType')) {
			$box['key']['id'] = ze::request('cType'). '_'. ze::request('cID');
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
		
		
		ze\categoryAdm::setupFABCheckboxes($fields['categories/categories'], true);
		
		if (empty($fields['categories/categories']['values'])) {
			unset($box['tabs']['categories']['edit_mode']);
			$fields['categories/categories']['hidden'] = true;
		
		} else {
			
			$fields['categories/no_categories']['hidden'] = true;
			$box['tabs']['categories']['fields']['desc']['snippet']['html'] = 
				ze\admin::phrase('You can put content item(s) into one or more categories. (<a[[link]]>Define categories</a>.)',
					['link' => ' href="'. htmlspecialchars(ze\link::absolute(). 'organizer.php#zenario__library/panels/categories'). '" target="_blank"']);
			
			
			$inCats = [];
			$sql = "
				SELECT l.category_id, COUNT(DISTINCT c.tag_id) AS cnt
				FROM ". DB_PREFIX. "content_items AS c
				INNER JOIN ". DB_PREFIX. "category_item_link AS l
				   ON c.equiv_id = l.equiv_id
				  AND c.type = l.content_type
				WHERE c.tag_id IN (". ze\escape::in($tagIds, 'asciiInSQL'). ")
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
										' '. ze\admin::phrase('(all [[total]] selected content items are in this category)', $row);
									} else {
										$fields['categories/categories']['values'][$row['category_id']]['label'] .=
										' '. ze\admin::phrase('([[cnt]] of [[total]] selected content items are in this category)', $row);
										}
								}
						}		
				}
	
				$values['categories/categories'] = ze\escape::in($inCats, false);
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
				ze\admin::phrase('The selected content items will be set to the categories you selected. (If translated, the translations will be set those categories too.)');
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//...
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_EDIT_DRAFT');
		
		$cID = $cType = false;
		
		$tagIds = ze\ray::explodeAndTrim($box['key']['id']);
		
		foreach ($tagIds as $tagId) {
			if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
				ze\categoryAdm::setContentItemCategories($cID, $cType, ze\ray::explodeAndTrim($values['categories/categories']));
			}
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($box['key']['id_is_menu_node_id']) {
			$box['key']['id'] = $box['key']['menu_node_id'];
		}
	}
}
