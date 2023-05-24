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


class zenario_common_features__admin_boxes__menu_text extends ze\moduleBaseClass {
	
	public static function getMenuText($menuId, $langId) {
		return 
			ze\row::get(
				'menu_text',
				'name',
				['menu_id' => $menuId, 'language_id' => $langId]);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($path != 'zenario_menu_text') return;
		
		//Try to get the Menu Id from the request
		$box['key']['id'] = ze::ifNull($box['key']['mID'] ?? false, ze::request('mID'), $box['key']['id']);
		
		if (!$box['key']['languageId'] = ze::ifNull($box['key']['languageId'], ze::request('target_language_id'), ze::request('languageId'))) {
			$box['key']['languageId'] = ze::$defaultLang;
		}
		
		if (!$menu = ze\menu::details($box['key']['id'], $box['key']['languageId'])) {
			exit;
		}
		
		$box['key']['sectionId'] = $menu['section_id'];
		$box['key']['parentMenuID'] = $menu['parent_id'];

		if (ze\menu::isUnique($menu['redundancy'], $menu['equiv_id'], $menu['content_type'])) {
			$menu['redundancy'] = 'unique';
		}
		
		$box['identifier']['css_class'] = ze\menuAdm::cssClass($menu);
		
		if ($box['key']['parentMenuID']) {
			$values['text/parent_path_of__menu_title'] = ze\menuAdm::path($box['key']['parentMenuID'], $box['key']['languageId']);
		}
		$values['text/menu_title'] = self::getMenuText($box['key']['id'], $box['key']['languageId']);
		
		if (ze::$defaultLang != $box['key']['languageId']
		 && ($values['text/text_in_default_language'] = self::getMenuText($box['key']['id'], ze::$defaultLang))) {
			$fields['text/text_in_default_language']['label'] =
				ze\admin::phrase('Menu text ([[english_name]]):',
					['english_name' => ze\lang::name(ze::$defaultLang, false)]);
			
			$values['text/parent_path_of__text_in_default_language'] = ze\menuAdm::path($box['key']['parentMenuID'], ze::$defaultLang);
			zenario_common_features::setMenuPath($box['tabs']['text']['fields'], 'text_in_default_language', 'value');
		
		} else {
			$fields['text/left_column']['hidden'] =
			$fields['text/right_column']['hidden'] =
			$fields['text/path_of__text_in_default_language']['hidden'] =
			$fields['text/text_in_default_language']['hidden'] = true;
			$fields['text/menu_title']['grouping'] =
			$fields['text/path_of__menu_title']['grouping'] = '';
		}
		
		zenario_common_features::setMenuPath($box['tabs']['text']['fields'], 'menu_title', 'value');
		
		//Don't show the links to the advanced panels if the admin doesn't have the _PRIV_EDIT_MENU_ITEM permissions
		if (!ze\priv::check('_PRIV_EDIT_MENU_ITEM')) {
			unset($box['extra_button_html']);
		}
		
		//Don't let this FAB be edited if the admin doesn't have permission to edit this menu node text
		if (!ze\priv::onMenuText('_PRIV_EDIT_MENU_TEXT', $box['key']['id'], $box['key']['languageId'], $box['key']['sectionId'])) {
			unset($box['tabs']['text']['edit_mode']['enabled']);
		}
		
		
		$multilingual = ze\lang::count() > 1;
		
		if ($multilingual) {
			$fields['text/menu_title']['label'] =
				ze\admin::phrase('Menu text ([[english_name]]):',
					['english_name' => ze\lang::name($box['key']['languageId'], false)]);
		}
		
		//For top-level menu modes, add a note to the "path" field to make it clear that it's
		//at the top level
		if (!$values['text/parent_path_of__menu_title']) {
			$fields['text/path_of__menu_title']['label'] = ze\admin::phrase('Menu path preview (top level):');
			$fields['text/path_of__text_in_default_language']['label'] = ze\admin::phrase('Menu path (top level):');
		}
		
		if ($box['key']['parentMenuID']) {
			$mpathArr = explode(' › ',$values['text/path_of__menu_title']);
			
			$fields['text/path_of__menu_title']['value'] = $values['text/path_of__menu_title']." [level ".count($mpathArr)."]"; 
		}
		else	
		{
			$fields['text/path_of__menu_title']['value'] = self::getMenuText($box['key']['id'], $box['key']['languageId'])." [level 1]";
		}

	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($path != 'zenario_menu_text') return;
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($path != 'zenario_menu_text') return;
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($path != 'zenario_menu_text') return;
		
		if (ze\priv::onMenuText('_PRIV_EDIT_MENU_TEXT', $box['key']['id'], $box['key']['languageId'])) {
			ze\row::set(
				'menu_text',
				['name' => $values['text/menu_title']],
				['menu_id' => $box['key']['id'], 'language_id' => $box['key']['languageId']]);
		}
	}
}
