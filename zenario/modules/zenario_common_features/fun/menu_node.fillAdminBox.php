<?php
/*
 * Copyright (c) 2015, Tribal Limited
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


//Try to get the Menu Id from the request
$box['key']['id'] = ifNull(arrayKey($box['key'], 'mID'), request('mID'), $box['key']['id']);

if (!$box['key']['languageId'] = ifNull($box['key']['languageId'], request('target_language_id'), request('languageId'))) {
	$box['key']['languageId'] = setting('default_language');
}

//If we're in Storekeeper Quick, enable the "Save and Continue" button
if ($box['key']['mode'] == 'quick') {
	$box['save_button_message'] = adminPhrase('Save & Close');
	$box['save_and_continue_button_message'] = adminPhrase('Save & Continue');
}


$menu = false;
if ($box['key']['id'] && ($menu = getMenuNodeDetails($box['key']['id']))) {
	exitIfNotCheckPriv('_PRIV_VIEW_MENU_ITEM');
	
	$box['key']['sectionId'] = $menu['section_id'];
	$box['key']['parentMenuID'] = $menu['parent_id'];
	
	$box['title'] = '';
	
	if ($menu['target_loc'] == 'int' && $menu['equiv_id'] && $menu['content_type']) {
		$box['tabs']['text']['fields']['target_loc']['value'] = 'int';
		$box['tabs']['text']['fields']['hyperlink_target']['value'] = $menu['content_type']. '_'. $menu['equiv_id'];
	
	} elseif ($menu['target_loc'] == 'ext') {
		$box['tabs']['text']['fields']['target_loc']['value'] = 'ext';
	}
	$box['tabs']['text']['fields']['use_download_page']['value'] = $menu['use_download_page'];
	$box['tabs']['text']['fields']['hide_private_item']['value'] = $menu['hide_private_item'];
	$box['tabs']['text']['fields']['open_in_new_window']['value'] = $menu['open_in_new_window'];
	$box['tabs']['text']['fields']['hyperlink_anchor']['value'] = $menu['anchor'];
	
	$box['tabs']['advanced']['fields']['accesskey']['value'] = $menu['accesskey'];
	$box['tabs']['advanced']['fields']['rel_tag']['value'] = $menu['rel_tag'];
	$box['tabs']['advanced']['fields']['image_id']['value'] = $menu['image_id'];
	$box['tabs']['advanced']['fields']['use_rollover_image']['value'] = (bool) $menu['rollover_image_id'];
	$box['tabs']['advanced']['fields']['rollover_image_id']['value'] = $menu['rollover_image_id'];
	$box['tabs']['advanced']['fields']['css_class']['value'] = $menu['css_class'];
	
	$box['tabs']['advanced']['fields']['hide_by_static_method']['value'] = (bool)$menu['module_class_name'];
	$box['tabs']['advanced']['fields']['menu__module_class_name']['value'] = $menu['module_class_name'];
	$box['tabs']['advanced']['fields']['menu__method_name']['value'] = $menu['method_name'];
	$box['tabs']['advanced']['fields']['menu__param_1']['value'] = $menu['param_1'];
	$box['tabs']['advanced']['fields']['menu__param_2']['value'] = $menu['param_2'];
	
	$box['tabs']['advanced']['fields']['overwrite_menu_text_by_static_method']['value'] = (bool)$menu['menu_text_module_class_name'];
	$box['tabs']['advanced']['fields']['menu_text__module_class_name']['value'] = $menu['menu_text_module_class_name'];
	$box['tabs']['advanced']['fields']['menu_text__method_name']['value'] = $menu['menu_text_method_name'];
	$box['tabs']['advanced']['fields']['menu_text__param_1']['value'] = $menu['menu_text_param_1'];
	$box['tabs']['advanced']['fields']['menu_text__param_2']['value'] = $menu['menu_text_param_2'];
	
	foreach ($box['tabs'] as $i => &$tab) {
		if (is_array($tab) && isset($tab['edit_mode'])) {
			$tab['edit_mode']['enabled'] =
				$box['key']['parentMenuID']?
					checkPriv('_PRIV_EDIT_MENU_ITEM')
				:	checkPriv('_PRIV_EDIT_MENU_ITEM');
		}
	}

} else {
	//Convert the location requests from either the old format, or the format that <zenario_quick_create> uses, if provided in one of those formats
	if (!$box['key']['parentMenuID']) {
		$box['key']['parentMenuID'] = ifNull(request('target_menu_parent'), request('parentMenuID'));
	}
	
	if (!$box['key']['sectionId'] = ifNull($box['key']['sectionId'], request('target_menu_section'), request('sectionId'))) {
		exit;
	}
	
	if ($box['key']['parentMenuID']) {
		exitIfNotCheckPriv('_PRIV_ADD_MENU_ITEM');
	} else {
		exitIfNotCheckPriv('_PRIV_ADD_MENU_ITEM');
	}
	
	foreach ($box['tabs'] as $i => &$tab) {
		if (is_array($tab) && isset($tab['edit_mode'])) {
			$tab['edit_mode']['enabled'] = true;
			$tab['edit_mode']['on'] = true;
			$tab['edit_mode']['always_on'] = true;
		}
	}
	
	$box['tabs']['text']['fields']['menu_title']['value'] = arrayKey($box['key'], 'suggestedName');
}

$box['tabs']['text']['fields']['section_id']['value'] = menuSectionName($box['key']['sectionId']);


//For multilingual sites, add extra fields for each enabled language
$langs = getLanguages($includeAllLanguages = false, $orderByEnglishName = true, $defaultLangFirst = true);
$lastMenuURL = false;
$numLangs = count($langs);
$ord = 10;
foreach ($langs as $lang) {
	$ord += 10;
	
	//Workaround for some bugs with references in PHP
	//I need to unset these keys, otherwise they seem to create lots of references in my arrays, linking places
	//I don't want to be linked!
	unset($box['tabs']['text']['fields']['menu_title']['value']);
	unset($box['tabs']['text']['fields']['path_of__menu_title']['value']);
	unset($box['tabs']['text']['fields']['parent_path_of__menu_title']['value']);
	$tmpValue = (string) $box['tabs']['text']['fields']['ext_url']['value'];
	unset($box['tabs']['text']['fields']['ext_url']['value']);
	$box['tabs']['text']['fields']['ext_url']['value'] = $tmpValue;
	
	$title = 'menu_title__'. $lang['id'];
	$path = 'path_of__menu_title__'. $lang['id'];
	$parentPath = 'parent_path_of__menu_title__'. $lang['id'];
	$url = 'ext_url__'. $lang['id'];
	
	$box['tabs']['text']['fields'][$title] = $box['tabs']['text']['fields']['menu_title'];
	$box['tabs']['text']['fields'][$path] = $box['tabs']['text']['fields']['path_of__menu_title'];
	$box['tabs']['text']['fields'][$parentPath] = $box['tabs']['text']['fields']['parent_path_of__menu_title'];
	$box['tabs']['text']['fields'][$url] = $box['tabs']['text']['fields']['ext_url'];
	
	// Remove unnecessary validation.
	unset($box['tabs']['text']['fields'][$url]['validation']);
	
	$box['tabs']['text']['fields'][$title]['ord'] = $ord;
	$box['tabs']['text']['fields'][$path]['ord'] = $ord + 1;
	$box['tabs']['text']['fields'][$parentPath]['ord'] = $ord + 2;
	$box['tabs']['text']['fields'][$url]['ord'] .= '.'. str_pad($ord, 5, '0', STR_PAD_LEFT);
	
	if ($box['key']['id']
	 && ($text = getMenuNodeDetails($box['key']['id'], $lang['id']))
	 && ($text['name'])) {
		
		$box['tabs']['text']['fields'][$title]['value'] = $text['name'];
		$box['tabs']['text']['fields'][$url]['value'] = $text['ext_url'];
		
		if (empty($box['tabs']['text']['fields']['ext_url']['value']) && $text['ext_url']) {
			$box['tabs']['text']['fields']['ext_url']['value'] = $text['ext_url'];
		}
		
		if ($text['ext_url'] !== null) {
			if ($lastMenuURL !== false && $text['ext_url'] != $lastMenuURL) {
				if ($box['tabs']['text']['fields']['target_loc']['value'] == 'ext') {
					$box['tabs']['text']['fields']['target_loc']['value'] = 'exts';
				}
			}
			
			$lastMenuURL = $text['ext_url'];
		}
		
		//Set the title using the name of the Menu Node in the current language, or in the default language,
		//or in any language if neither of those present.
		//(Note that I'm relying on the default language being first in this loop for this logic to work.)
		if (!$box['title'] || $lang['id'] == $box['key']['languageId']) {
			$box['title'] = adminPhrase('Editing the menu node "[[name]]"', $text);
		}
	}
	
	if ($box['key']['parentMenuID']) {
		$box['tabs']['text']['fields'][$parentPath]['value'] = getMenuPath($box['key']['parentMenuID'], $lang['id']);
	}
	
	if ($numLangs > 1) {
		$box['tabs']['text']['fields'][$title]['label'] = adminPhrase('Text ([[english_name]]):', $lang);
		$box['tabs']['text']['fields'][$url]['label'] = adminPhrase('Link for text in [[english_name]]:', $lang);
	}
	
	//Set the existing Menu Path from the existing title and the parent path
	zenario_common_features::setMenuPath($box['tabs']['text']['fields'], $title, 'value');
}


$box['tabs']['text']['fields']['menu_title']['hidden'] = true;
$box['tabs']['text']['fields']['path_of__menu_title']['hidden'] = true;
$box['tabs']['text']['fields']['parent_path_of__menu_title']['hidden'] = true;

if ($numLangs <= 1) {
	$box['tabs']['text']['fields']['hyperlink_target']['note_below'] = '';
	$box['tabs']['text']['fields']['target_loc']['values']['exts']['hidden'] = true;
}


//Attempt to load a list of CSS Class Names from an xml file description in the current Skin to add choices in for the CSS Class Picker
$skinId = false;
if ((request('cID')
  && request('cType')
  && $layoutId = contentItemTemplateId(request('cID'), request('cType')))
 || ($menu
  && $menu['equiv_id']
  && $menu['content_type']
  && $layoutId = contentItemTemplateId($menu['equiv_id'], $menu['content_type']))
 || ($box['key']['parentMenuID']
  && ($menuParent = getMenuNodeDetails($box['key']['parentMenuID']))
  && ($menuParent['equiv_id'])
  && ($menuParent['content_type'])
  && ($layoutId = contentItemTemplateId($menuParent['equiv_id'], $menuParent['content_type'])))) {
	$skinId = templateSkinId($layoutId);
}


return false;