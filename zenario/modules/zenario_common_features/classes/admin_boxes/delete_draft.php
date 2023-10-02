<?php
/*
 * Copyright (c) 2023, Tribal Limited
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


class zenario_common_features__admin_boxes__delete_draft extends ze\moduleBaseClass {
	
	protected $totalRowNum = 0;
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$ids = ze\ray::explodeAndTrim($box['key']['id']);
		$contentItemsCount = count($ids);

		if ($contentItemsCount > 1) {
			$box['tabs']['delete_draft']['notices']['delete_items']['show'] = true;
		} else {
			$box['tabs']['delete_draft']['notices']['delete_item']['show'] = true;
		}
		
		//Look for any access codes in use
		ze\contentAdm::checkForAccessCodes($box, $fields['delete_draft/access_codes_warning'], $ids, $contentItemsCount,
			'This content item has a staging code ([[access_code]]). This will be removed when the draft is deleted.',
			'One content item has a staging code ([[access_code]]). This will be removed when the draft is deleted.',
			'[[count]] content items have a staging code. These will be removed when the draft is deleted.'
		);
		
		ze\module::incSubclass('zenario_common_features');
		zenario_common_features::getTranslationsAndPluginsLinkingToThisContentItem($ids, $box, $fields, $values, 'delete_draft', $this->totalRowNum, $getPlugins = true, $getTranslations = true);

		$fields['delete_draft/links_warning_part_2']['snippet']['html'] = '<br /><p>' . ze\admin::nPhrase(
			'Delete this draft?',
			'Delete these drafts?',
			$contentItemsCount
		) . '</p>';
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$errorsOnTab = false;
		foreach ($box['tabs']['delete_draft']['fields'] as $field) {
			if (isset($field['error'])) {
				$errorsOnTab = true;
				break;
			}
		}
		
		if ($errorsOnTab) {
			$fields['delete_draft/table_end']['error'] = ze\admin::phrase('Please select an action for each translation.');
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$ids = ze\ray::explodeAndTrim($box['key']['id']);
		foreach ($ids as $tagId) {
			$cID = $cType = false;
			ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
			if (ze\contentAdm::allowDelete($cID, $cType) && ze\priv::check('_PRIV_EDIT_DRAFT', $cID, $cType)) {
				ze\contentAdm::deleteDraft($cID, $cType, true, $values['delete_draft/delete_options']);
			}
		}
		
		//Delete any translations flagged for deletion
		zenario_common_features::deleteOrTrashTranslations($fields, $values, $tabName = 'delete_draft');
	}
}