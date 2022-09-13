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


class zenario_common_features__admin_boxes__trash extends ze\moduleBaseClass {
	
	protected $totalRowNum = 0;
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$ids = ze\ray::explodeAndTrim($box['key']['id']);
		if (count($ids) > 1) {
			$box['tabs']['trash']['notices']['trash_items']['show'] = true;
		} else {
			$box['tabs']['trash']['notices']['trash_item']['show'] = true;
		}
		
		ze\module::incSubclass('zenario_common_features');
		zenario_common_features::getTranslationsAndPluginsLinkingToThisContentItem($ids, $box, $fields, $values, 'trash', $this->totalRowNum);
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$errorsOnTab = false;
		foreach ($box['tabs']['trash']['fields'] as $field) {
			if (isset($field['error'])) {
				$errorsOnTab = true;
				break;
			}
		}
		
		if ($errorsOnTab) {
			$fields['trash/table_end']['error'] = ze\admin::phrase('Please select an action for each translation.');
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$ids = ze\ray::explodeAndTrim($box['key']['id']);

		$goToContentItem = '';
		if (!isset($_GET['refinerName']) && count($ids) == 1) {
			ze\content::getCIDAndCTypeFromTagId($cID, $cType, $ids[0]);
			$menu = ze\menu::getFromContentItem($cID, $cType);

			if (!empty($menu) && is_array($menu) && !empty($menu['parent_id'])) {
				$parentMenuContentItem = ze\menu::getContentItem($menu['parent_id']);

				if (!empty($parentMenuContentItem) && is_array($parentMenuContentItem)) {
					$goToContentItem = ze\link::toItem(
						$parentMenuContentItem['content_id'], $parentMenuContentItem['content_type'],
						$fullPath = true, '', false, false, $forceAliasInAdminMode = true
					);
				}
			} else {
				$goToContentItem = ze\link::toItem(
					ze::$homeEquivId, ze::$homeCType,
					$fullPath = true, '', false, false, $forceAliasInAdminMode = true
				);
			}
		}

		foreach ($ids as $tagId) {
			$cID = $cType = false;
			ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
			if (ze\contentAdm::allowTrash($cID, $cType) && ze\priv::check('_PRIV_HIDE_CONTENT_ITEM', $cID, $cType)) {
				ze\contentAdm::trashContent($cID, $cType, false, $values['trash/trash_options']);
			}
		}
		
		//Trash any translations flagged for trashing
		zenario_common_features::deleteOrTrashTranslations($fields, $values, $tabName = 'trash');

		//If it looks like this was opened from the front-end
		//(i.e. there's no sign of any of Organizer's variables)
		//then try to redirect the admin to whatever the visitor URL should be
		if ($goToContentItem) {
			ze\tuix::closeWithFlags(['go_to_url' => $goToContentItem]);
		}
	}
}