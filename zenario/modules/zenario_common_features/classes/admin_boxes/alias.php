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


class zenario_common_features__admin_boxes__alias extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		//Set up the primary key from the requests given
		if ($box['key']['id'] && !$box['key']['cID']) {
			ze\content::getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $box['key']['id']);
		}
		
		$box['key']['equivId'] = ze\content::equivId($box['key']['cID'], $box['key']['cType']);
		
		//Check the current admin is allowed to edit this content item
		if (!ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
			unset($box['tabs']['meta_data']['edit_mode']);
		}
		
		//Load the alias
		$values['meta_data/alias'] =
			ze\content::alias($box['key']['cID'], $box['key']['cType']);
		$values['meta_data/lang_code_in_url'] =
			ze\row::get('content_items', 'lang_code_in_url', ['id' => $box['key']['cID'], 'type' => $box['key']['cType']]);
		
		if (ze::setting('translations_different_aliases')) {
			$values['meta_data/update_translations'] = 'update_this';
			
			//If this is a translation, only allow the translation's alias to be changed
			if ($box['key']['equivId'] != $box['key']['cID']) {
				$fields['meta_data/update_translations']['readonly'] = true;
			}
			
		} else {
			$values['meta_data/update_translations'] = 'update_all';
			$fields['meta_data/update_translations']['readonly'] = true;
			
			//If translations share an alias with the main content item,
			//only allow the alias to be changed in the primary language.
			if ($box['key']['equivId'] != $box['key']['cID']) {
				unset($box['tabs']['meta_data']['edit_mode']);
			}
		}
		
		$fields['meta_data/lang_code_in_url']['values']['default']['label'] =
			ze::setting('translations_hide_language_code')?
				$fields['meta_data/lang_code_in_url']['values']['default']['label__hide']
			 :	$fields['meta_data/lang_code_in_url']['values']['default']['label__show'];
		
		
		//If every language has a specific domain name, there's no point in showing the
		//lang_code_in_url field as it will never be used.
		$langSpecificDomainsUsed = ze\row::exists('languages', ['domain' => ['!' => '']]);
		$langSpecificDomainsNotUsed = ze\row::exists('languages', ['domain' => '']);
		
		if ($langSpecificDomainsUsed && !$langSpecificDomainsNotUsed) {
			$fields['meta_data/lang_code_in_url']['hidden'] =
			$fields['meta_data/lang_code_in_url_dummy']['hidden'] = true;
		}
		
		//Hide the language if there is only 1 enabled.
		if (ze\lang::count() > 1) {
			ze\contentAdm::getLanguageSelectListOptions($fields['meta_data/language_id']);
			$values['meta_data/language_id'] = ze\content::langId($box['key']['cID'], $box['key']['cType']);
		} else {
			$fields['meta_data/language_id']['hidden'] = true;
		}
		
		$box['title'] =
			ze\admin::phrase('Editing the alias for content item "[[tag]]"',
				['tag' => ze\content::formatTag($box['key']['cID'], $box['key']['cType'])]);
		
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if (!empty($values['meta_data/alias'])) {
			if (is_array($errors = ze\contentAdm::validateAlias($values['meta_data/alias'], $box['key']['cID'], $box['key']['cType']))) {
				$box['tabs']['meta_data']['errors'] = array_merge($box['tabs']['meta_data']['errors'], $errors);
			}
		}
		
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
			
			$equivId = ze\content::equivId($box['key']['cID'], $box['key']['cType']);
			$cols = ['alias' => ze\contentAdm::tidyAlias($values['meta_data/alias'])];
			$key = ['id' => $box['key']['cID'], 'type' => $box['key']['cType']];
			$equivKey = ['equiv_id' => $equivId, 'type' => $box['key']['cType']];
			
			if (ze\lang::count() > 1) {
				if ($equivId == $box['key']['cID']
				 && $values['meta_data/update_translations'] == 'update_all'
				 && ze\priv::check('_PRIV_EDIT_DRAFT', $equivId, $box['key']['cType'])) {
					$key = $equivKey;
					$cols['lang_code_in_url'] = 'default';
				
				} else {
					$cols['lang_code_in_url'] = $values['meta_data/lang_code_in_url'];
				}
			}
			
			ze\row::update('content_items', $cols, $key);
		}
		
	}
}
