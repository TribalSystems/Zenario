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

class zenario_common_features__organizer__translation_chains extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		//...
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		//Set the list of content types in the content types quick-filter
		$panel['columns']['type']['values'] = [];
		$ord = 1;
		foreach (ze\content::getContentTypes() as $cType) {
			$panel['columns']['type']['values'][$cType['content_type_id']] = $cType['content_type_name_en'];

			$panel['quick_filter_buttons'][$cType['content_type_id']] = [
				'ord' => ++$ord,
				'parent' => 'content_type',
				'column' => 'type',
				'label' => $cType['content_type_name_en'],
				'value' => $cType['content_type_id']
			];
		}

		//If a type is selected in the filter, make sure to change the label of the parent to what was chosen
		if (($typeFilter = zenario_organizer::filterValue('type'))
			&& (!empty($panel['quick_filter_buttons'][$typeFilter]['label']))
		) {
			$panel['quick_filter_buttons']['content_type']['label'] =
				$panel['quick_filter_buttons'][$typeFilter]['label'];
		}
		
		
		
		$showInOrganiser = ze::in($mode, 'full', 'quick', 'select');

		foreach ($panel['items'] as $id => &$item) {
			if ($showInOrganiser && ($privacy = $item['privacy'] ?? false)) {
				$item['row_class'] = ' privacy_'. $privacy;
			}
			
			$item['translations'] = 0;
			$equivs = ze\content::equivalences($item['id'], $item['type'], $includeCurrent = false, $item['equiv_id']);
			if (!empty($equivs)) {
				foreach(\ze::$langs as $lang) {
					if (!empty($equivs[$lang['id']])) {
						if ($lang['id'] != $item['language_id']) {
							++$item['translations'];
						}
					}
				}
			}
			
			if ($item['translations'] > 0) {
				$item['tag'] = ze\content::formatTag($item['id'], $item['type'], $item['alias'], $item['language_id']);
				$item['chain_desc'] = ze\admin::nphrase('[[tag]] and its translation', '[[tag]] and its [[count]] translations', $item['translations'], $item);
			
			} elseif ($item['language_id'] == ze::$defaultLang) {
				$item['tag'] = ze\content::formatTag($item['id'], $item['type'], $item['alias'], $item['language_id']);
				$item['chain_desc'] = ze\admin::phrase('[[tag]] (no translations)', $item);
				$item['css_class'] = 'translation_chain_no_translations';
			
			} else {
				$item['chain_desc'] = ze\admin::phrase('translation chain ID [[type]]_[[id]] (no content item in default language)', $item);
				$item['css_class'] = 'translation_chain_not_in_default';
			}
		}
	}
}
