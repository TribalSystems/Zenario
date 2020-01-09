<?php
/*
 * Copyright (c) 2020, Tribal Limited
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


class zenario_common_features__admin_boxes__content_layout extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		$tagSQL = "";
		$cID = $cType = false;
		$canEdit = true;
		
		if (($_REQUEST['cID'] ?? false) && ($_REQUEST['cType'] ?? false)) {
			$total = 1;
			$cID = $box['key']['cID'] = $_REQUEST['cID'] ?? false;
			$cType = $box['key']['cType'] = $_REQUEST['cType'] ?? false;
			$tagSQL = "'". ze\escape::sql($box['key']['id'] = $cType. '_'. $cID). "'";
			$canEdit = ze\priv::check('_PRIV_EDIT_DRAFT', $cID, $cType);
		
		} else {
			$tagIds = ze\ray::explodeAndTrim($box['key']['id']);
			
			foreach ($tagIds as $tagId) {
				if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
					
					if (!ze\priv::check('_PRIV_EDIT_DRAFT', $cID, $cType)) {
						$canEdit = false;
					}
					
					$tagSQL .= ($tagSQL? ", " : ""). "'". ze\escape::sql($tagId). "'";
				}
			}
			
			if (!$tagSQL) {
				exit;
			}
			$total = count($tagIds);
			
			$box['key']['cType'] = false;
			foreach ($tagIds as $tagId) {
				if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
					if (!$box['key']['cType']) {
						$box['key']['cType'] = $cType;
					} elseif ($cType != $box['key']['cType']) {
						$box['key']['cType'] = false;
						break;
					}
				}
			}
		}
		
		//Set up the primary key from the requests given
		if ($box['key']['id'] && !$box['key']['cID']) {
			ze\content::getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $box['key']['id']);
		}
		
		$content =
			ze\row::get(
				'content_items',
				['id', 'type', 'tag_id', 'language_id', 'equiv_id', 'alias', 'visitor_version', 'admin_version', 'status'],
				['id' => $box['key']['cID'], 'type' => $box['key']['cType']]);
		
		$box['identifier']['css_class'] = ze\contentAdm::getItemIconClass($content['id'], $content['type'], true, $content['status']);
		
		if (!$canEdit) {
			$box['tabs']['cant_edit']['hidden'] = false;
		
		} elseif (!$box['key']['cType']) {
			$box['tabs']['mix_of_types']['hidden'] = false;
		
		} else {
			$box['tabs']['layout']['hidden'] = false;
			$box['tabs']['layout']['edit_mode']['enabled'] = true;
			
			$box['tabs']['layout']['fields']['layout_id']['pick_items']['path'] =
				'zenario__layouts/panels/layouts/refiners/content_type//'. $box['key']['cType']. '//';
			
			//Run a SQL query to check how many distinct values this column has for each Content Item.
			//If there is only one unique value then populate it, otherwise show the field as blank.
			$sql = "
				SELECT DISTINCT v.layout_id
				FROM ". DB_PREFIX. "content_items AS c
				INNER JOIN ". DB_PREFIX. "content_item_versions AS v
				   ON c.id = v.id
				  AND c.type = v.type
				  AND c.admin_version = v.version
				WHERE c.tag_id IN (". $tagSQL. ")
				LIMIT 2";
			$result = ze\sql::select($sql);
			
			if (($row1 = ze\sql::fetchRow($result)) && !($row2 = ze\sql::fetchRow($result))) {
				$fields['layout_id']['value'] = $row1[0];
			}
		}
		
		if ($total > 1) {
			$box['title'] =
				ze\admin::phrase('Changing the layout of [[count]] content items',
					['count' => $total]);
		} else {
			$box['title'] =
				ze\admin::phrase('Changing the layout of the content item "[[tag]]"',
					['tag' => ze\content::formatTag($cID, $cType)]);
		}
		
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$box['tabs']['layout']['notices']['archived_template']['show'] = false;
		
		if (!$values['layout_id']) {
			$fields['skin_id']['hidden'] = true;
		} else {
			$fields['skin_id']['hidden'] = false;
			
			$fields['skin_id']['value'] =
			$fields['skin_id']['current_value'] =
				ze\content::layoutSkinId($values['layout_id']);
			
			if (ze\row::get('layouts', 'status', $values['layout_id']) != 'active') {
				$box['tabs']['layout']['notices']['archived_template']['show'] = true;
			}
		}
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		$box['confirm']['message'] = '';
		
		if (empty($values['layout_id'])) {
			$box['tabs']['layout']['errors'][] = ze\admin::phrase('Please select a layout.');
		
		} else {
			//Are we saving one or multiple items..?
			$cID = $cType = false;
			if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $box['key']['id'])) {
				//Just one item in the id
				$cVersion = ze\content::latestVersion($cID, $cType);
				
				//If changing the layout of one content item, warn the administrator if plugins
				//will be moved/lost, but still allow them to do the change.
				ze\layoutAdm::validateChangeSingleLayout($box, $cID, $cType, $cVersion, $values['layout/layout_id'], $saving);
				
			} else {
				//Multiple comma-seperated items
				$mrg = [
					'draft' => 0,
					'hidden' => 0,
					'published' => 0,
					'trashed' => 0];
				
				$tagIds = ze\ray::explodeAndTrim($box['key']['id']);
				foreach ($tagIds as $tagId) {
					if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
				
						//If changing the layout of multiple content items, don't warn the administrator if plugins
						//will be moved, but don't allow them to do the change if plugins will be lost.
						$warnings = ze\layoutAdm::changeContentItemLayout(
							$cID, $cType, ze\content::latestVersion($cID, $cType), $values['layout/layout_id'],
							$check = true, $warnOnChanges = false
						);
						
						if ($warnings) {
							$box['tabs']['layout']['errors'][] = ze\admin::phrase('Your new layout lacks one or more Banners, Content Summary Lists, Raw HTML Snippets or WYSIWYG editors from the content items\' current layout.');
							return;
						}
						
						if ($status = ze\content::status($cID, $cType)) {
					
							if ($status == 'hidden') {
								++$mrg['hidden'];
					
							} elseif ($status == 'trashed') {
								++$mrg['trashed'];
					
							} elseif (ze\content::isDraft($status)) {
								++$mrg['draft'];
					
							} else {
								++$mrg['published'];
							}
					
						}
					}
				}
				
				
				$box['confirm']['button_message'] = ze\admin::phrase('Save');
				if ($mrg['published'] || $mrg['hidden'] || $mrg['trashed']) {
					$box['confirm']['button_message'] = ze\admin::phrase('Make new Drafts');
					$box['confirm']['message'] .= '<p>'. ze\admin::phrase('This will create a new Draft for:'). '</p>';
			
					if ($mrg['published']) {
						$box['confirm']['message'] .= '<p> &nbsp; &bull; '. ze\admin::phrase('[[published]] Published Content Item(s)', $mrg). '</p>';
					}
			
					if ($mrg['hidden']) {
						$box['confirm']['message'] .= '<p> &nbsp; &bull; '. ze\admin::phrase('[[hidden]] Hidden Content Item(s)', $mrg). '</p>';
					}
			
					if ($mrg['trashed']) {
						$box['confirm']['message'] .= '<p> &nbsp; &bull; '. ze\admin::phrase('[[trashed]] Archived Content Item(s)', $mrg). '</p>';
					}
			
					if ($mrg['draft']) {
						$box['confirm']['message'] .= '<p>'. ze\admin::phrase('and will update [[draft]] Draft Content Item(s).', $mrg);
					}
				} else {
					$box['confirm']['message'] .= '<p>'. ze\admin::phrase('This will update [[draft]] Draft Content Item(s).', $mrg);
				}
				
				//print_r($box['confirm']);
			}
		}
		
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//Loop through each Content Item, saving each
		$cID = $cType = $cVersion = false;
		$tagIds = ze\ray::explodeAndTrim($box['key']['id']);
		foreach ($tagIds as $tagId) {
			if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
				
				if (!ze\priv::check('_PRIV_EDIT_CONTENT_ITEM_TEMPLATE', $cID, $cType)) {
					continue;
				}
				
				//Create a draft if needed
				ze\contentAdm::createDraft($cID, $cID, $cType, $cVersion, ze\content::latestVersion($cID, $cType));
				
				//Update the layout
				ze\layoutAdm::changeContentItemLayout($cID, $cType, $cVersion, $values['layout_id']);
				
				//Mark this version as updated
				ze\contentAdm::updateVersion($cID, $cType, $cVersion, $version = [], $forceMarkAsEditsMade = true);
			}
		}
		
	}
}
