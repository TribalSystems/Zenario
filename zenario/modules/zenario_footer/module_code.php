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




class zenario_footer extends zenario_menu {
	
	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = true, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$this->sectionId				= $this->setting('menu_section');
		$this->startFrom				= '_MENU_LEVEL_1';
		$this->numLevels				= 1;
		$this->maxLevel1MenuItems		= 40;
		$this->language					= false;
		$this->onlyFollowOnLinks		= true;
		$this->onlyIncludeOnLinks		= false;
		$this->showInvisibleMenuItems	= false;
		$this->showMissingMenuNodes		= $this->setting('show_missing_menu_nodes');
		
		$this->showInMenuMode();
		
		//Get the Menu Node for this content item
		$this->currentMenuId = ze\menu::getIdFromContentItem(ze::$equivId, ze::$cType, $this->sectionId);
		
		return true;
	}

	function showSlot() {
		if ($this->setting('show_visitor_cookie_management_link') && ze::setting('cookie_require_consent') == 'explicit') {
			$this->mergeFields['Visitor_cookie_management'] = true;
			$this->mergeFields['Manage_cookies_phrase'] = ze\lang::phrase($this->setting('manage_cookies_phrase'));
		}
		
		$this->mergeFields['Separator_character_setting'] = $this->setting('separate_menu_nodes_with');
		$this->mergeFields['Separator_character'] = $this->setting('separator_character');

		parent::showSlot();
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($path == 'plugin_settings') {
			$defaultLangName = ze\lang::name(ze::$defaultLang);
			$fields['first_tab/show_missing_menu_nodes']['side_note'] = ze\admin::phrase(
				"Show the menu node in the site's default language ([[default_lang_name]]) if a translation is not available. Recommended to ensure important links (e.g. Privacy info) are always visible.",
				['default_lang_name' => $defaultLangName]
			);
			
			if (ze\lang::count() == 1) {
				$fields['first_tab/show_missing_menu_nodes']['hidden'] = true;
			}
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($path == 'plugin_settings') {
			if ($values['first_tab/show_visitor_cookie_management_link'] && ze::setting('cookie_require_consent') != 'explicit') {
				$fields['first_tab/show_visitor_cookie_management_link']['notices_below']['cookie_policy_not_set_to_explicit']['hidden'] = false;
			} else {
				$fields['first_tab/show_visitor_cookie_management_link']['notices_below']['cookie_policy_not_set_to_explicit']['hidden'] = true;
			}
		}
	}
}