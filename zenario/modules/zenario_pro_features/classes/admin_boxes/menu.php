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

				
class zenario_pro_features__admin_boxes__menu extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		//For multilingual sites, add extra fields for each enabled language
		$langs = ze\lang::getLanguages($includeAllLanguages = false, $orderByEnglishName = true, $defaultLangFirst = true);
		$numLangs = count($langs);
		$ord = 0;
		foreach ($langs as $lang) {
			
			$field = $box['tabs']['advanced']['custom_fields']['descriptive_text'];
			
			$field['ord'] = ++$ord;
			
			if ($box['key']['id'] && ($text = ze\menu::details($box['key']['id'], $lang['id']))) {
				$field['value'] = $text['descriptive_text'];
			}
			
			if ($numLangs > 1) {
				$field['label'] = ze\admin::phrase('Additional text ([[english_name]]):', $lang);
			}
			
			$box['tabs']['advanced']['fields']['descriptive_text__'. $lang['id']] = $field;
		}

		if ($box['key']['id'] && $menu = ze\menu::details($box['key']['id'], $box['key']['languageId'])) {
			$box['tabs']['text']['fields']['zenario_pro_features__invisible']['value'] = $menu['invisible'];
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Disable the descriptive text fields in any language that the Menu is not translated into
		$langs = ze\lang::getLanguages();
		foreach ($langs as $lang) {
			$box['tabs']['advanced']['fields']['descriptive_text__'. $lang['id']]['disabled'] =
				empty($values['text/menu_title__'. $lang['id']]);
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (ze\ring::engToBoolean($box['tabs']['advanced']['edit_mode']['on'] ?? false)) {
			$langs = ze\lang::getLanguages();
			foreach ($langs as $lang) {
				ze\menuAdm::saveText(
					$box['key']['id'],
					$lang['id'],
					[
						'descriptive_text' => $values['advanced/descriptive_text__'. $lang['id']]],
					$neverCreate = true);
			}
		}

		if (ze\ring::engToBoolean($box['tabs']['text']['edit_mode']['on'] ?? false) && ze\priv::check('_PRIV_EDIT_MENU_ITEM')) {
			ze\menuAdm::save(
				[
					'invisible' => $values['text/zenario_pro_features__invisible']],
				$box['key']['id']);
		}
	}

}
