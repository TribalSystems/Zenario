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


class zenario_common_features__admin_boxes__hide extends ze\moduleBaseClass {
	
	protected $totalRowNum = 0;
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id_is_menu_node_id'] && ($menuContentItem = ze\menu::getContentItem($box['key']['id']))) {
			//Hide an existing Content Item based on its Menu Node:
			//Work out what the content item should be, then proceed with the rest of the logic
			$box['key']['cID'] = $menuContentItem['equiv_id'];
			$box['key']['cType'] = $menuContentItem['content_type'];
			
			$box['key']['menu_node_id'] = $box['key']['id'];
			$box['key']['id'] = $box['key']['cType'] . '_' . $box['key']['cID'];
		}
		
        $ids = ze\ray::explodeAndTrim($box['key']['id']);
		$contentItemsCount = count($ids);

		if ($contentItemsCount > 1) {
			$box['tabs']['hide']['notices']['hide_items']['show'] = true;
		} else {
			$box['tabs']['hide']['notices']['hide_item']['show'] = true;
		}
		
		//Look for any access codes in use
		ze\contentAdm::checkForAccessCodes($box, $fields['hide/access_codes_warning'], $ids, $contentItemsCount,
			'This content item has an access code ([[access_code]]). This will be removed when it is hidden.',
			'One content item has an access code ([[access_code]]). This will be removed when it is hidden.',
			'[[count]] content items have an access code. These will be removed when it is hidden.'
		);
		
		ze\module::incSubclass('zenario_common_features');
        zenario_common_features::getTranslationsAndPluginsLinkingToThisContentItem($ids, $box, $fields, $values, 'hide', $this->totalRowNum, $getPlugins = true, $getTranslations = false);

		if (!empty($fields['hide/links_warning']['snippet']['html'])) {
			$fields['hide/links_warning']['snippet']['html'] .= '<br /><p>' . ze\admin::nPhrase(
				'The above plugins/nests/slideshows will have their links to this item removed, but they will be restored when this content item is re-published.',
				'The above plugins/nests/slideshows will have their links to these items removed, but they will be restored when these content items are re-published.',
				$contentItemsCount
			) . '</p>';
		}

		$fields['hide/links_warning']['snippet']['html'] .= '<br /><p>' . ze\admin::nPhrase(
			'Hide this content item?',
			'Hide these content items?',
			$contentItemsCount
		) . '</p>';
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
        
        foreach ($ids as $id) {
            $cID = $cType = false;
            if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $id)) {
                if (ze\contentAdm::allowHide($cID, $cType) && ze\priv::check('_PRIV_PUBLISH_CONTENT_ITEM', $cID, $cType)) {
                    ze\contentAdm::hideContent($cID, $cType);
                }
            }
        }
        
        if ($box['key']['id_is_menu_node_id']) {
			$box['key']['id'] = $box['key']['menu_node_id'];
		}

		//If it looks like this was opened from the front-end
		//(i.e. there's no sign of any of Organizer's variables)
		//then try to redirect the admin to whatever the visitor URL should be
		if ($goToContentItem) {
			$flags = [];
			$flags['go_to_url'] = $goToContentItem;
			$flags['toast_next_pageload'] = ze\admin::phrase("Content item hidden!");
			ze\tuix::closeWithFlags($flags);
		}
	}
}