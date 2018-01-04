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


class zenario_common_features__admin_boxes__menu extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//Catch the case where we open from the admin toolbar, and the id variable has a different name
		if (!empty($_REQUEST['mID'])) {
			$box['key']['id'] = $_REQUEST['mID'];
		}
		
		if (!$box['key']['languageId'] = ifNull($box['key']['languageId'], ($_REQUEST['target_language_id'] ?? false), ($_REQUEST['languageId'] ?? false))) {
			$box['key']['languageId'] = cms_core::$defaultLang;
		}
		
		//When creating child nodes id is the parent node id
		if ($box['key']['id_is_parent_menu_node_id']) {
			$box['key']['parentMenuID'] = $box['key']['id'];
			$box['key']['id'] = false;
		}
		
		$menu = false;
		if ($box['key']['id'] && ($menu = getMenuNodeDetails($box['key']['id'], cms_core::$defaultLang))) {
			exitIfNotCheckPriv('_PRIV_VIEW_MENU_ITEM');
	
			$box['key']['sectionId'] = $menu['section_id'];
			$box['key']['parentMenuID'] = $menu['parent_id'];
	
			$box['title'] = '';
	
			if ($menu['target_loc'] == 'int' && $menu['equiv_id'] && $menu['content_type']) {
				$values['text/target_loc'] = 'int';
				$values['text/hyperlink_target'] = $menu['content_type']. '_'. $menu['equiv_id'];
		
				$warning = '';
				$tag = $menu['content_type']. '_'. $menu['equiv_id'];
				$mergeFields = array('tag' => $tag);
				if ($menu['redundancy'] == 'primary') {
					$warning = 'This is a primary menu node. This means that there are other menu nodes that link to the content item [[tag]].';
				} elseif ($menu['redundancy'] == 'secondary') {
					$primaryNodeId = getRow('menu_nodes', 'id', array('equiv_id' => $menu['equiv_id'], 'content_type' => $menu['content_type'], 'redundancy' => 'primary'));
					$node_path = getMenuPath($primaryNodeId);
					$warning = 'This is a secondary menu node. This means that it links to the same content item ([[tag]]) as the menu node "[[node_path]]"';
					$mergeFields['node_path'] = $node_path;
				}
				$fields['text/warning']['snippet']['html'] = adminPhrase($warning, $mergeFields);
		
			} elseif ($menu['target_loc'] == 'ext') {
				$values['text/target_loc'] = 'ext';
				$values['ext_url'] = $menu['ext_url'];
			}
			$values['text/use_download_page'] = $menu['use_download_page'];
			$values['text/hide_private_item'] = $menu['hide_private_item'];
			$values['text/open_in_new_window'] = $menu['open_in_new_window'];
			
			if ($menu['anchor'] != ''
			 && $menu['anchor'] != null) {
				$values['text/link_to_anchor'] = true;
				$values['text/hyperlink_anchor'] = $menu['anchor'];
			}
	
			$values['advanced/accesskey'] = $menu['accesskey'];
			$values['advanced/rel_tag'] = $menu['rel_tag'];
			$values['advanced/css_class'] = $menu['css_class'];
			$values['advanced/add_registered_get_requests'] = $menu['add_registered_get_requests'];
	
			if ($values['advanced/call_static_method'] = (bool) $menu['module_class_name']) {
				$values['advanced/menu__module_class_name'] = $menu['module_class_name'];
				$values['advanced/menu__method_name'] = $menu['method_name'];
				$values['advanced/menu__param_1'] = $menu['param_1'];
				$values['advanced/menu__param_2'] = $menu['param_2'];
			}
	
			foreach ($box['tabs'] as $i => &$tab) {
				if (is_array($tab) && isset($tab['edit_mode'])) {
					$tab['edit_mode']['enabled'] = checkPriv('_PRIV_EDIT_MENU_ITEM');
				}
			}

		} else {
			exitIfNotCheckPriv('_PRIV_ADD_MENU_ITEM');
			//Convert the location requests from the old format
			if (!$box['key']['parentMenuID']) {
				$box['key']['parentMenuID'] = ifNull($_REQUEST['target_menu_parent'] ?? false, ($_REQUEST['parentMenuID'] ?? false));
			}
	
			if (!$box['key']['sectionId'] = ifNull($box['key']['sectionId'], ($_REQUEST['target_menu_section'] ?? false), ($_REQUEST['sectionId'] ?? false))) {
				exit;
			}
			
			foreach ($box['tabs'] as $i => &$tab) {
				if (is_array($tab) && isset($tab['edit_mode'])) {
					$tab['edit_mode']['enabled'] = true;
					$tab['edit_mode']['on'] = true;
					$tab['edit_mode']['always_on'] = true;
				}
			}
	
			$values['text/menu_title'] = $box['key']['suggestedName'] ?? false;
		}


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
	
			$box['tabs']['text']['fields'][$title] = $fields['text/menu_title'];
			$box['tabs']['text']['fields'][$path] = $fields['text/path_of__menu_title'];
			$box['tabs']['text']['fields'][$parentPath] = $fields['text/parent_path_of__menu_title'];
			$box['tabs']['text']['fields'][$url] = $fields['text/ext_url'];
	
			// Remove unnecessary validation.
			unset($box['tabs']['text']['fields'][$url]['validation']);
	
			$box['tabs']['text']['fields'][$title]['ord'] = $ord;
			$box['tabs']['text']['fields'][$path]['ord'] = $ord + 1;
			$box['tabs']['text']['fields'][$parentPath]['ord'] = $ord + 2;
			$box['tabs']['text']['fields'][$url]['ord'] .= '.'. str_pad($ord, 5, '0', STR_PAD_LEFT);
	
			if ($box['key']['id']
			 && ($text = getMenuNodeDetails($box['key']['id'], $lang['id']))
			 && ($text['name'])) {
				
				$text['section_name'] = menuSectionName($text['section_id']);
				
				$box['tabs']['text']['fields'][$title]['value'] = $text['name'];
				$box['tabs']['text']['fields'][$url]['value'] = $text['ext_url'];
		
				if (empty($values['text/ext_url']) && $text['ext_url']) {
					$values['text/ext_url'] = $text['ext_url'];
				}
		
				if ($text['ext_url'] !== null) {
					if ($lastMenuURL !== false && $text['ext_url'] != $lastMenuURL) {
						if ($values['text/target_loc'] == 'ext') {
							$values['text/target_loc'] = 'exts';
						}
					}
			
					$lastMenuURL = $text['ext_url'];
				}
		
				//Set the title using the name of the Menu Node in the current language, or in the default language,
				//or in any language if neither of those present.
				//(Note that I'm relying on the default language being first in this loop for this logic to work.)
				if (!$box['title'] || $lang['id'] == $box['key']['languageId']) {
					$box['title'] = adminPhrase('Editing the menu node "[[name]]" ("[[section_name]]" section)', $text);
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


		$fields['text/menu_title']['hidden'] = true;
		$fields['text/path_of__menu_title']['hidden'] = true;
		$fields['text/parent_path_of__menu_title']['hidden'] = true;

		if ($numLangs <= 1) {
			$fields['text/hyperlink_target']['note_below'] = '';
			$fields['text/target_loc']['values']['exts']['hidden'] = true;
		}


		//Attempt to load a list of CSS Class Names from an xml file description in the current Skin to add choices in for the CSS Class Picker
		$skinId = false;
		if ((($_REQUEST['cID'] ?? false)
		  && ($_REQUEST['cType'] ?? false)
		  && $layoutId = contentItemTemplateId($_REQUEST['cID'] ?? false, ($_REQUEST['cType'] ?? false)))
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
		
		//For top-level menu modes, add a note to the "path" field to make it clear that it's
		//at the top level
		if (!$values['text/parent_path_of__menu_title']) {
			$fields['text/path_of__menu_title']['label'] = adminPhrase('Path preview (top level):');
		}

	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($path != 'zenario_menu') return;
		$fields['advanced/menu__module_class_name']['hidden'] = 
		$fields['advanced/menu__method_name']['hidden'] = 
		$fields['advanced/menu__param_1']['hidden'] = 
		$fields['advanced/menu__param_2']['hidden'] = 
			!$values['advanced/call_static_method'];

		$fields['text/hyperlink_target']['hidden'] = 
		$fields['text/hyperlink_anchor']['hidden'] = 
		$fields['text/link_to_anchor']['hidden'] = 
		$fields['text/hide_private_item']['hidden'] = 
		$fields['text/use_download_page']['hidden'] = 
			$values['text/target_loc'] != 'int';

		$fields['text/open_in_new_window']['hidden'] = 
			$values['text/target_loc'] != 'int'
		 && $values['text/target_loc'] != 'ext'
		 && $values['text/target_loc'] != 'exts';

		$fields['text/ext_url']['hidden'] = 
			$values['text/target_loc'] != 'ext';

		$equivs = $cID = $cType = false;
		if ($values['text/target_loc'] == 'int'
		 && (getCIDAndCTypeFromTagId($cID, $cType, $values['text/hyperlink_target']))
		 && ($cType == 'document')) {
			$fields['text/use_download_page']['hidden'] = false;
		} else {
			$fields['text/use_download_page']['current_value'] = false;
			$fields['text/use_download_page']['hidden'] = true;
		}


		$langs = getLanguages();
		$numLangs = count($langs);

		//For multilingal sites, add a note about using the Content Item in the default language if no translation is set.
		//(But use the langEquivalentItem() function to work out what language will actually be used.)
		$fields['text/multilingual_description']['hidden'] = true;
		if ($cID && $cType && $numLangs > 1) {
			langEquivalentItem($cID, $cType, $langId = true);
			$mainLang = getContentLang($cID, $cType);
	
			$fields['text/multilingual_description']['hidden'] = false;
			$fields['text/multilingual_description']['snippet']['html'] =
				' '.
				adminPhrase(
					'If text is specified for a menu node but no translation of the content item exists, the menu node will link to the item in [[english_name]].',
					$langs[$mainLang]);
	
			$equivs = equivalences($cID, $cType);
		}


		//Set the current Menu Path from the current title and the parent path
		
		foreach ($langs as $lang) {
			$path = $box['tabs']['text']['fields']['path_of__menu_title__'. $lang['id']]['value'];
			if($path){
				$nodes = explode('>',$path);
				if(is_array($nodes) && $nodes){
					$box['tabs']['text']['fields']['path_of__menu_title__'. $lang['id']]['value']= $path." (".count($nodes)." level)";	
				}
			}
			
			if (!empty($equivs) && empty($equivs[$lang['id']])) {
				$box['tabs']['text']['fields']['path_of__menu_title__'. $lang['id']]['note_below'] = adminPhrase('Translated content item missing.');
			} else {
				unset($box['tabs']['text']['fields']['path_of__menu_title__'. $lang['id']]['note_below']);
			}
	
			$box['tabs']['text']['fields']['ext_url__'. $lang['id']]['disabled'] =
				empty($values['text/menu_title__'. $lang['id']]);
	
			$box['tabs']['text']['fields']['ext_url__'. $lang['id']]['hidden'] = 
				$values['text/target_loc'] != 'exts';
			
		}
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($path != 'zenario_menu') return;
		
		
		$langs = getLanguages();
		$numLangs = count($langs);

		$createdLanguages = 0;
		if (engToBoolean($box['tabs']['text']['edit_mode']['on'] ?? false)) {
			foreach ($langs as $lang) {
				if ($values['text/menu_title__'. $lang['id']]) {
					++$createdLanguages;
				}
			}
	
			if (!$createdLanguages) {
				if ($numLangs > 1) {
					$box['tabs']['text']['errors'][] = adminPhrase('Please enter text in at least one language.');
				} else {
					$box['tabs']['text']['errors'][] = adminPhrase('_ERROR_MUST_ENTER_TITLE_FOR_MENU_ITEM');
				}
			}
		}


		if ($values['text/target_loc'] == 'exts') {
			if (engToBoolean($box['tabs']['text']['edit_mode']['on'] ?? false)
			 || engToBoolean($box['tabs']['text']['edit_mode']['on'] ?? false)) {
				foreach ($langs as $lang) {
					if ($values['text/menu_title__'. $lang['id']]
					 && !$values['text/ext_url__'. $lang['id']]) {
						$box['tabs']['text']['errors'][] = adminPhrase('Please set an external URL for [[english_name]].', $lang);
					}
				}
			}
		}

		if (engToBoolean($box['tabs']['advanced']['edit_mode']['on'] ?? false)) {
			if (!empty($values['advanced/accesskey'])) {
				$ord = ord($values['advanced/accesskey']);
				if ($ord < 48 || ($ord > 57 && $ord < 65) || $ord > 90) {
					$box['tabs']['advanced']['errors'][] = adminPhrase('Access Keys may only be the capital letters A-Z, or the digits 0-9.');
		
				} else {
					$sql = "
						SELECT id
						FROM ". DB_NAME_PREFIX ."menu_nodes
						WHERE accesskey = '" . sqlEscape($values['advanced/accesskey']) . "'";
			
					if ($box['key']['id']) {
						$sql .= "
						  AND id != ". (int) $box['key']['id'];
					}
			
					if (($result = sqlQuery($sql)) && ($row = sqlFetchAssoc($result))) {
						$box['tabs']['advanced']['errors'][] =
							adminPhrase('The access key "[[accesskey]]" is in use! It is currently assigned to the Menu Node "[[menuitem]]".',
								array('accesskey' => $values['advanced/accesskey'], 'menuitem' => getMenuName($row['id'], $box['key']['languageId'])));
					}
				}
			}
			if (!empty($values['advanced/call_static_method'])) {
				if (!$values['advanced/menu__module_class_name']) {
					$box['tabs']['advanced']['errors'][] = adminPhrase("Please enter a module's class name");
		
				} elseif (!inc($values['advanced/menu__module_class_name'])) {
					$box['tabs']['advanced']['errors'][] = adminPhrase('No module with the class name of [[advanced/menu__module_class_name]] is running on this site', $values);
		
				} elseif ($values['advanced/menu__method_name']
					&& !method_exists(
							$values['advanced/menu__module_class_name'],
							$values['advanced/menu__method_name'])
				) {
					$box['tabs']['advanced']['errors'][] = adminPhrase('The [[advanced/menu__module_class_name]] module does not have a static method called [[advanced/menu__method_name]]', $values);
				}
		
				if (!$values['advanced/menu__method_name']) {
					$box['tabs']['advanced']['errors'][] = adminPhrase('Please enter the name of a static method.');
				}
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($path != 'zenario_menu') return;
		
		$id = $box['key']['id'] ?? false;
		$parent_menu_id = $box['key']['parentMenuID'] ?? false;

		if ($id) {
			if (getRow('menu_nodes', 'parent_id', $id)) {
				exitIfNotCheckPriv('_PRIV_EDIT_MENU_ITEM');
			} else {
				exitIfNotCheckPriv('_PRIV_EDIT_MENU_ITEM');
			}
		} else {
			if ($parent_menu_id) {
				exitIfNotCheckPriv('_PRIV_ADD_MENU_ITEM');
			} else {
				exitIfNotCheckPriv('_PRIV_ADD_MENU_ITEM');
			}
		}

		$cID = $cType = false;
		getCIDAndCTypeFromTagId($cID, $cType, $values['text/hyperlink_target']);

		$submission = array();
		if (!$id) {
			$submission['section_id'] = menuSectionId($box['key']['sectionId']);
			$submission['parent_id'] = $parent_menu_id;
		}

		if (engToBoolean($box['tabs']['text']['edit_mode']['on'] ?? false)) {
			$submission['target_loc'] = $values['text/target_loc'];
	
	
			if ($submission['target_loc'] == 'exts') {
				$submission['target_loc'] = 'ext';
			}
	
			$submission['content_id'] = $cID;
			$submission['content_type'] = $cType;
			$submission['hide_private_item'] = $values['text/hide_private_item'];
			$submission['use_download_page'] = $values['text/use_download_page'];
			//$submission['ext_url'] = $values['destination/ext_url'];
			$submission['open_in_new_window'] = $values['text/open_in_new_window'];
			$submission['anchor'] = ($values['text/target_loc'] == 'int' && $values['text/link_to_anchor']) ? $values['text/hyperlink_anchor'] : '';
		}

		if (engToBoolean($box['tabs']['advanced']['edit_mode']['on'] ?? false)) {
			$submission['accesskey'] = $values['advanced/accesskey'];
			$submission['rel_tag'] = $values['advanced/rel_tag'];
			$submission['css_class'] = $values['advanced/css_class'];
			$submission['add_registered_get_requests'] = $values['advanced/add_registered_get_requests'];
	
			$call_static_method = $values['advanced/call_static_method'];
			$submission['module_class_name'] = $call_static_method ? $values['advanced/menu__module_class_name'] : '';
			$submission['method_name'] = $call_static_method ? $values['advanced/menu__method_name'] : '';
			$submission['param_1'] = $call_static_method ? $values['advanced/menu__param_1'] : '';
			$submission['param_2'] = $call_static_method ? $values['advanced/menu__param_2'] : '';
		}

		$box['key']['id'] = saveMenuDetails($submission, $id);


		$langs = getLanguages();
		$numLangs = count($langs);

		foreach ($langs as $lang) {
	
			if (engToBoolean($box['tabs']['text']['edit_mode']['on'] ?? false)) {
				$submission = array();
		
				//Remove a Menu Node without any text.
				if (!$values['text/menu_title__'. $lang['id']]) {
					removeMenuText($box['key']['id'], $lang['id']);
					continue;
		
				} else {
					$submission['name'] = $values['text/menu_title__'. $lang['id']];
				}
		
				saveMenuText($box['key']['id'], $lang['id'], $submission);
			}
	
			$submission = array();
	
			if (engToBoolean($box['tabs']['text']['edit_mode']['on'] ?? false)) {
				if ($values['text/target_loc'] == 'exts') {
					$submission['ext_url'] = $values['text/ext_url__'. $lang['id']];
				} else {
					$submission['ext_url'] = $values['text/ext_url'];
				}
			}
	
			if (!empty($submission)) {
				saveMenuText($box['key']['id'], $lang['id'], $submission, $neverCreate = true);
			}
		}

		//For Menu Items in the Front End, navigate to that page if it's an internal link.
		//Always recalculate the link from the chosen destination, as this may just have been changed
		if ($cID) {
			$box['key']['cID'] = $cID;
			$box['key']['cType'] = $cType;
	
			if (!empty($box['key']['languageId'])) {
				langEquivalentItem($box['key']['cID'], $box['key']['cType'], $box['key']['languageId']);
			}
		}
	}
}
