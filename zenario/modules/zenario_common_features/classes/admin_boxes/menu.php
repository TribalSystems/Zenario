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


class zenario_common_features__admin_boxes__menu extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		//Catch the case where we open from the admin toolbar, and the id variable has a different name
		if (!empty($_REQUEST['mID'])) {
			$box['key']['id'] = $_REQUEST['mID'];
		}
		
		if (!$box['key']['languageId'] = ze::ifNull($box['key']['languageId'], ze::request('target_language_id'), ze::request('languageId'))) {
			$box['key']['languageId'] = ze::$defaultLang;
		}
		
		//When creating child nodes id is the parent node id
		if ($box['key']['id_is_parent_menu_node_id']) {
			$box['key']['parentMenuID'] = $box['key']['id'];
			$box['key']['id'] = false;
		}
		
		$menu = false;
		if ($box['key']['id'] && ($menu = ze\menu::details($box['key']['id'], ze::$defaultLang))) {
			$box['key']['sectionId'] = $menu['section_id'];
			$box['key']['parentMenuID'] = $menu['parent_id'];
	
			$box['identifier']['css_class'] = ze\menuAdm::cssClass($menu);
			
			$box['title'] = '';
	
			if ($menu['target_loc'] == 'int' && $menu['equiv_id'] && $menu['content_type']) {
				$values['text/target_loc'] = 'int';
				$values['text/hyperlink_target'] = $menu['content_type']. '_'. $menu['equiv_id'];
		
				$warning = '';
				$tag = $menu['content_type']. '_'. $menu['equiv_id'];
				$mergeFields = ['tag' => $tag];

				if ($menu['redundancy'] == 'primary') {
					$warning = 'This is a primary menu node. This means that there are other menu nodes that link to the same content item, [[tag]].';
					$mergeFields['div_start'] = '<div class="zenario_fbInfo">';
				} elseif ($menu['redundancy'] == 'secondary') {
					$primaryNodeId = ze\row::get('menu_nodes', 'id', ['equiv_id' => $menu['equiv_id'], 'content_type' => $menu['content_type'], 'redundancy' => 'primary']);
					$node_path = ze\menuAdm::path($primaryNodeId);
					$warning = 'This is a secondary menu node. This means that it links to the same content item ([[tag]]) as the primary menu node "[[node_path]]".';
					$mergeFields['node_path'] = $node_path;
					$mergeFields['div_start'] = '<div class="zenario_fbWarning">';
				}

				if ($warning) {
					//Add HTML tags around the warning to show "info" or "warning" icon.
					$warning = '[[div_start]]' . $warning . '[[div_end]]';
					$mergeFields['div_end'] = '</div>';

					$fields['text/warning']['snippet']['html'] = ze\admin::phrase($warning, $mergeFields);
				}
		
			} elseif ($menu['target_loc'] == 'doc' && $menu['document_id']) {
				$values['text/target_loc'] = 'doc';
				$values['text/document_id'] = $menu['document_id'];
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
			$values['advanced/restrict_child_content_types'] = $menu['restrict_child_content_types'];
	
			if ($values['advanced/call_static_method'] = (bool) $menu['module_class_name']) {
				$values['advanced/menu__module_class_name'] = $menu['module_class_name'];
				$values['advanced/menu__method_name'] = $menu['method_name'];
				$values['advanced/menu__param_1'] = $menu['param_1'];
				$values['advanced/menu__param_2'] = $menu['param_2'];
			}
	
			foreach ($box['tabs'] as $i => &$tab) {
				if (is_array($tab) && isset($tab['edit_mode'])) {
					$tab['edit_mode']['enabled'] = ze\priv::check('_PRIV_EDIT_MENU_ITEM');
				}
			}

			if (!empty($menu['restrict_child_content_types'])) {
				$box['identifier']['css_class'] .= ' node_suggest_on';
			}

		} else {
			ze\priv::exitIfNot('_PRIV_ADD_MENU_ITEM');
			//Convert the location requests from the old format
			if (!$box['key']['parentMenuID']) {
				$box['key']['parentMenuID'] = ze::ifNull($_REQUEST['target_menu_parent'] ?? false, ze::request('parentMenuID'));
			}
	
			if (!$box['key']['sectionId'] = ze::ifNull($box['key']['sectionId'], ze::request('target_menu_section'), ze::request('sectionId'))) {
				exit;
			}
			
			foreach ($box['tabs'] as $i => &$tab) {
				if (is_array($tab) && isset($tab['edit_mode'])) {
					$tab['edit_mode']['enabled'] = true;
					$tab['edit_mode']['on'] = true;
				}
			}
	
			$values['text/menu_title'] = $box['key']['suggestedName'] ?? false;
		}

		//For multilingual sites, add extra fields for each enabled language
		$langs = ze\lang::getLanguages($includeAllLanguages = false, $orderByEnglishName = true, $defaultLangFirst = true);
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
			$tmpValue = (string) $box['tabs']['text']['fields']['ext_url']['value'];
			unset($box['tabs']['text']['fields']['ext_url']['value']);
			$box['tabs']['text']['fields']['ext_url']['value'] = $tmpValue;
	
			$title = 'menu_title__'. $lang['id'];
			$pathCodename = 'path_of__menu_title__'. $lang['id'];
			$url = 'ext_url__'. $lang['id'];
	
			$box['tabs']['text']['fields'][$title] = $fields['text/menu_title'];
			$box['tabs']['text']['fields'][$pathCodename] = $fields['text/path_of__menu_title'];
			$box['tabs']['text']['fields'][$url] = $fields['text/ext_url'];
	
			// Remove unnecessary validation.
			unset($box['tabs']['text']['fields'][$url]['validation']);
	
			$box['tabs']['text']['fields'][$title]['ord'] = $ord;
			$box['tabs']['text']['fields'][$pathCodename]['ord'] = $ord + 1;
			$box['tabs']['text']['fields'][$url]['ord'] .= '.'. str_pad($ord, 5, '0', STR_PAD_LEFT);
	
			if ($box['key']['id']
			 && ($text = ze\menu::details($box['key']['id'], $lang['id']))
			 && ($text['name'])) {
				
				$text['section_name'] = ze\menu::sectionName($text['section_id']);
				
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
		
				//Set the title using the name of the menu node in the current language, or in the default language,
				//or in any language if neither of those present.
				//(Note that I'm relying on the default language being first in this loop for this logic to work.)
				if (!$box['title'] || $lang['id'] == $box['key']['languageId']) {
					$box['title'] = ze\admin::phrase('Editing the menu node "[[name]]" ("[[section_name]]" section)', $text);
				}
			}
			
			//Set the menu path preview
			if ($box['key']['id']) {
				//Set the menu position to the exsting node.
				//I'm using the "$isExistingNode" option here which is only supported by the ze\menuAdm::posToPathArray() function.
				//This option is not supported by position pickers nor the ze\menuAdm::addContentItems() function.
				$menuPos = $box['key']['sectionId']. '_'. $box['key']['id']. '_3';
			
			} else {
				//When creating a new node, use the "$underNode" option to display the path.
				$menuPos = $box['key']['sectionId']. '_'. ($box['key']['parentMenuID'] ?? 0). '_1';
			}

			ze\menuAdm::setupPathPreview($menuPos, $box['tabs']['text']['fields'][$pathCodename], $lang['id']);
	
			if ($numLangs > 1) {
				$box['tabs']['text']['fields'][$title]['label'] = ze\admin::phrase('Text ([[english_name]]):', $lang);
				$box['tabs']['text']['fields'][$url]['label'] = ze\admin::phrase('Link for text in [[english_name]]:', $lang);
			}
	
			//Set the existing Menu Path from the existing title and the parent path
			zenario_common_features::setMenuPath($box['tabs']['text']['fields'], $title, 'value');
			
		}

		$fields['text/menu_title']['hidden'] = true;
		$fields['text/path_of__menu_title']['hidden'] = true;

		if ($numLangs <= 1) {
			$fields['text/hyperlink_target']['note_below'] = '';
			$fields['text/target_loc']['values']['exts']['hidden'] = true;
		}

		//Attempt to load a list of CSS Class Names from an xml file description in the current Skin to add choices in for the CSS Class Picker
		$skinId = false;
		if ((ze::request('cID')
		  && ze::request('cType')
		  && $layoutId = ze\content::layoutId($_REQUEST['cID'] ?? false, ze::request('cType')))
		 || ($menu
		  && $menu['equiv_id']
		  && $menu['content_type']
		  && $layoutId = ze\content::layoutId($menu['equiv_id'], $menu['content_type']))
		 || ($box['key']['parentMenuID']
		  && ($menuParent = ze\menu::details($box['key']['parentMenuID']))
		  && ($menuParent['equiv_id'])
		  && ($menuParent['content_type'])
		  && ($layoutId = ze\content::layoutId($menuParent['equiv_id'], $menuParent['content_type'])))) {
			$skinId = ze\content::layoutSkinId($layoutId);
		}
		
		//If there are any custom GET requests, load them from the DB
		$values['advanced/add_custom_get_requests'] = (is_array($menu) && !empty($menu['custom_get_requests']));
		if ($box['key']['id'] && $values['advanced/add_custom_get_requests']) {
			$values['advanced/custom_get_requests'] = str_replace('&', ',', $menu['custom_get_requests']);
			
			//This code is needed to get the "allow_typing_anything" picker working if there should be
			//previously existing values
			ze\tuix::setupAllowTypingAnythingPicker($fields['advanced/custom_get_requests'], $values['advanced/custom_get_requests']);
		}
		
		$i = -1;
		$cTypes = [];
		foreach (ze\row::getAssocs('content_types', ['content_type_plural_en'], [], 'content_type_plural_en') as $cType => $cTypeDetails) {
			if ($cType == 'html') {
				$fields['advanced/restrict_child_content_types']['empty_value'] = 
					ze\admin::phrase("Don't suggest");
			} else {
				$fields['advanced/restrict_child_content_types']['values'][$cType] = 
					ze\admin::phrase('Suggest [[content_type_plural_en]] be created under this menu node', $cTypeDetails);
				
				if ($i > 0) {
					$cTypes[$i - 1] = ', ';
				}
				if ($i >= 0) {
					$cTypes[++$i] = ' and ';
				}
				$cTypes[++$i] = $cTypeDetails['content_type_plural_en'];
			}
		}
		
		if (empty($cTypes)) {
			$cTypes = [ze\admin::phrase('Blog entries and News articles')];
		}
		
		$mrg = [
			'link' => ze\link::absolute(). 'organizer.php#zenario__content/panels/content_types',
			'content_types' => implode('', $cTypes)];
		
		
		
		$fields['advanced/content_restriction_desc']['snippet']['html'] =
			ze\admin::phrase($fields['advanced/content_restriction_desc']['snippet']['html'], $mrg);
		
		//Images tab
		$nodeId = $box['key']['id'];
		$row = ze\row::get('menu_node_feature_image',
			['use_feature_image', 'image_id', 'use_rollover_image', 'rollover_image_id', 'title', 'text', 'link_type', 'link_visibility', 'dest_url', 'open_in_new_window', 'overwrite_alt_tag'],
			['node_id' => $nodeId]);
		
		if ($row && is_array($row)) {
			if ($row['image_id']) {
				$box['key']['feature_image_id'] = $row['image_id'];
				
				$file = ze\row::get('files', ['alt_tag'], $row['image_id']);
				if ($file){
					$fields['feature_image/promo__overwrite_alt_tag']['multiple_edit']['original_value'] = $file['alt_tag'];
				}
				
				if ($row['overwrite_alt_tag']) {
					$values['feature_image/promo__overwrite_alt_tag'] = $row['overwrite_alt_tag'];
					$fields['feature_image/promo__overwrite_alt_tag']['multiple_edit']['changed'] = true;
				} else {
					if ($file){
						$values['feature_image/promo__overwrite_alt_tag'] = $file['alt_tag'];
					}
				}
			}

			$values['feature_image/promo__feature_image_checkbox'] = $row['use_feature_image'];
			$values['feature_image/promo__feature_image'] = $row['image_id'];
			$values['feature_image/promo__use_rollover'] = $row['use_rollover_image'];
			$values['feature_image/promo__rollover_image'] = $row['rollover_image_id'];
			$values['feature_image/promo__title'] = $row['title'];
			$values['feature_image/promo__text'] = $row['text'];
			$values['feature_image/promo__link_type'] = empty($row['link_type']) ? 'no_link' : $row['link_type'];
			switch($row['link_type']) {
				case 'content_item':
					$values['feature_image/promo__hyperlink_target'] = $row['dest_url'];
					break;
				case 'external_url':
					$values['feature_image/promo__url'] = $row['dest_url'];
					break;
			}
			$values['feature_image/promo__hide_private_item'] = empty($row['link_visibility']) ? 'always_show' : $row['link_visibility'];
			$values['feature_image/promo__target_blank'] = $row['open_in_new_window'];
		} else {
			$box['key']['feature_image_id'] = '';
		}

		if ($box['key']['id'] && ($menu = ze\menu::details($box['key']['id']))) {
			if($menu['image_id']){
				$values['feature_image/show_image'] = true;
			}else{
				$menu['image_id']=false;
			}
			$values['feature_image/image_id'] = $menu['image_id'];
			$values['feature_image/use_rollover_image'] = (bool) $menu['rollover_image_id'];
			$values['feature_image/rollover_image_id'] = $menu['rollover_image_id'];
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
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
		 && (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $values['text/hyperlink_target']))
		 && ($cType == 'document')) {
			$fields['text/use_download_page']['hidden'] = false;
		} else {
			$fields['text/use_download_page']['current_value'] = false;
			$fields['text/use_download_page']['hidden'] = true;
		}

		$fields['text/document_privacy_error']['hidden'] = true;
		if ($values['text/target_loc'] == 'doc' && $values['text/document_id']) {
			$document = ze\row::get('documents', ['file_id', 'privacy'], ['id' => $values['text/document_id'], 'type' => 'file']);
			if ($document) {
				if (!ze\row::exists('files', ['id' => $document['file_id']])) {
					$fields['text/document_id']['error'] = ze\admin::phrase('Error: document file not found.');
				} elseif ($document['privacy'] != 'public') {
					$fields['text/document_privacy_error']['hidden'] = false;
				}
			}
		}

		$langs = ze\lang::getLanguages();
		$numLangs = count($langs);

		//For multilingal sites, add a note about using the Content Item in the default language if no translation is set.
		//(But use the ze\content::langEquivalentItem() function to work out what language will actually be used.)
		$fields['text/multilingual_description']['hidden'] = true;
		if ($cID && $cType && $numLangs > 1) {
			ze\content::langEquivalentItem($cID, $cType, $langId = true);
			$mainLang = ze\content::langId($cID, $cType);
	
			$fields['text/multilingual_description']['hidden'] = false;
			$fields['text/multilingual_description']['snippet']['html'] =
				' '.
				ze\admin::phrase(
					'If text is specified for a menu node but no translation of the content item exists, the menu node will link to the item in [[english_name]].',
					$langs[$mainLang]);
	
			$equivs = ze\content::equivalences($cID, $cType);
		}


		//When displaying the tab, update the menu preview path such that the current node
		//starts off set to what was last typed.
		foreach ($langs as $lang) {
			$title = 'menu_title__'. $lang['id'];
			$pathCodename = 'path_of__menu_title__'. $lang['id'];
			$values['text/'. $pathCodename] = $values['text/'. $title];
	
			$box['tabs']['text']['fields']['ext_url__'. $lang['id']]['disabled'] =
				empty($values['text/menu_title__'. $lang['id']]);
	
			$box['tabs']['text']['fields']['ext_url__'. $lang['id']]['hidden'] = 
				$values['text/target_loc'] != 'exts';
			
		}
		
		//Images tab
		$fields['feature_image/rollover_image_id']['hidden'] = 
			$fields['feature_image/use_rollover_image']['hidden'] =
			!$values['feature_image/show_image'];
		
		$imageId = $values['feature_image/promo__feature_image'];
		$rolloverImageId = $values['feature_image/promo__rollover_image'];
		
		if ($imageId != $box['key']['feature_image_id']) {
			$alt_tag = '';
			if ($imageDetails = ze\row::get('files', ['alt_tag'], $imageId)) {
				$alt_tag = $imageDetails['alt_tag'];
			}
			$fields['feature_image/promo__overwrite_alt_tag']['changed'] = false;
			$fields['feature_image/promo__overwrite_alt_tag']['multiple_edit']['original_value'] = 
			$values['feature_image/promo__overwrite_alt_tag'] = 
				$alt_tag;
		}
		
		$fields['feature_image/promo__feature_image']['hidden'] =
		$fields['feature_image/promo__use_rollover']['hidden'] =
		$fields['feature_image/promo__title']['hidden'] =
		$fields['feature_image/promo__text']['hidden'] =
		$fields['feature_image/promo__link_type']['hidden'] =
			!$values['feature_image/promo__feature_image_checkbox'];
		
		$fields['feature_image/promo__overwrite_alt_tag']['hidden'] =
		$hidden = 
			!($values['feature_image/promo__feature_image_checkbox'] && $imageId);
		
		$fields['feature_image/promo__rollover_image']['hidden'] =
			!empty($fields['feature_image/promo__use_rollover']['hidden']) || 
			!$values['feature_image/promo__use_rollover'];
		
		$fields['feature_image/promo__hyperlink_target']['hidden'] =
		$fields['feature_image/promo__hide_private_item']['hidden'] =
			!empty($fields['feature_image/promo__link_type']['hidden']) ||
			!($values['feature_image/promo__link_type'] == 'content_item');
		
		$fields['feature_image/promo__url']['hidden'] =
			!empty($fields['feature_image/promo__link_type']['hidden']) ||
			!($values['feature_image/promo__link_type'] == 'external_url');
		
		$fields['feature_image/promo__target_blank']['hidden'] =
			!empty($fields['feature_image/promo__link_type']['hidden']) ||
			!($values['feature_image/promo__link_type'] == 'external_url' ||
			$values['feature_image/promo__link_type'] == 'content_item');
			
		$cachingRestrictions = [];
		$fields['text/menu_node_will_appear_to_unauthorised_visitors_or_users']['hidden'] = true;
		if ($box['key']['id']) {
			//If this node links to either:
			//1) a private content item, and is supposed to obey the privacy setting of said content item, OR
			//2) a private/offline document,
			//AND has one or more public children, display a warning.
			if (
				($values['text/target_loc'] == 'int' && $values['text/hide_private_item'] == 1)
				|| (
					$values['text/target_loc'] == 'doc'
					&& $values['text/document_id']
					&& ($targetDocumentPrivacy = ze\row::get('documents', 'privacy', $values['text/document_id']))
					&& ze::in($targetDocumentPrivacy, 'private', 'offline')
				)
			) {
				$menuStructure = ze\menu::getStructure($cachingRestrictions, $box['key']['sectionId'], false, $box['key']['parentMenuID']);
		
				foreach ($menuStructure as $row) {
					if ($row['mID'] == $box['key']['id']) {
						if (
							($values['text/target_loc'] == 'int' && !ze\menu::shouldShow($row, $cachingRestrictions, ze::$defaultLang, false, $adminMode = false))
							|| ($values['text/target_loc'] == 'doc' && !empty($targetDocumentPrivacy) && ze::in($targetDocumentPrivacy, 'private', 'offline'))
						) {
							if ($row['children']) {
								if ($this->checkAnyNodeChildrenShouldShow($row['children'])) {
									$fields['text/menu_node_will_appear_to_unauthorised_visitors_or_users']['hidden'] = false;
								}
							}
						}
					}
				}
			}
		}
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//Images tab
		$langs = ze\lang::getLanguages();
		$numLangs = count($langs);

		$createdLanguages = 0;
		if (ze\ring::engToBoolean($box['tabs']['text']['edit_mode']['on'] ?? false)) {
			foreach ($langs as $lang) {
				if ($values['text/menu_title__'. $lang['id']]) {
					++$createdLanguages;
				}
			}
	
			if (!$createdLanguages) {
				if ($numLangs > 1) {
					$box['tabs']['text']['errors'][] = ze\admin::phrase('Please enter text in at least one language.');
				} else {
					$box['tabs']['text']['errors'][] = ze\admin::phrase('_ERROR_MUST_ENTER_TITLE_FOR_MENU_ITEM');
				}
			}
		}


		if ($values['text/target_loc'] == 'exts') {
			if (ze\ring::engToBoolean($box['tabs']['text']['edit_mode']['on'] ?? false)
			 || ze\ring::engToBoolean($box['tabs']['text']['edit_mode']['on'] ?? false)) {
				foreach ($langs as $lang) {
					if ($values['text/menu_title__'. $lang['id']]
					 && !$values['text/ext_url__'. $lang['id']]) {
						$box['tabs']['text']['errors'][] = ze\admin::phrase('Please set an external URL for [[english_name]].', $lang);
					}
				}
			}
		}

		if (ze\ring::engToBoolean($box['tabs']['advanced']['edit_mode']['on'] ?? false)) {
			if (!empty($values['advanced/accesskey'])) {
				$ord = ord($values['advanced/accesskey']);
				if ($ord < 48 || ($ord > 57 && $ord < 65) || $ord > 90) {
					$box['tabs']['advanced']['errors'][] = ze\admin::phrase('Access Keys may only be the capital letters A-Z, or the digits 0-9.');
		
				} else {
					$sql = "
						SELECT id
						FROM ". DB_PREFIX ."menu_nodes
						WHERE accesskey = '" . ze\escape::sql($values['advanced/accesskey']) . "'";
			
					if ($box['key']['id']) {
						$sql .= "
						  AND id != ". (int) $box['key']['id'];
					}
			
					if (($result = ze\sql::select($sql)) && ($row = ze\sql::fetchAssoc($result))) {
						$box['tabs']['advanced']['errors'][] =
							ze\admin::phrase('The access key "[[accesskey]]" is in use! It is currently assigned to the menu node "[[menuitem]]".',
								['accesskey' => $values['advanced/accesskey'], 'menuitem' => ze\menu::name($row['id'], $box['key']['languageId'])]);
					}
				}
			}
			if (!empty($values['advanced/call_static_method'])) {
				if (!$values['advanced/menu__module_class_name']) {
					$box['tabs']['advanced']['errors'][] = ze\admin::phrase("Please enter a module's class name");
		
				} elseif (!ze\module::inc($values['advanced/menu__module_class_name'])) {
					$box['tabs']['advanced']['errors'][] = ze\admin::phrase('No module with the class name of [[advanced/menu__module_class_name]] is running on this site', $values);
		
				} elseif ($values['advanced/menu__method_name']
					&& !method_exists(
							$values['advanced/menu__module_class_name'],
							$values['advanced/menu__method_name'])
				) {
					$box['tabs']['advanced']['errors'][] = ze\admin::phrase('The [[advanced/menu__module_class_name]] module does not have a static method called [[advanced/menu__method_name]]', $values);
				}
		
				if (!$values['advanced/menu__method_name']) {
					$box['tabs']['advanced']['errors'][] = ze\admin::phrase('Please enter the name of a static method.');
				}
			}
			
			//If the menu node uses custom GET requests, check for any invalid characters or missing "=" signs.
			if ($values['advanced/add_custom_get_requests'] && !empty($values['advanced/custom_get_requests'])) {
				$invalidCharErrors = $missingEqualsSigns = '';
				$invalidCharacters = ['\;', '\:', ' ', '\'', '\"', '\\', '\/', '`', '~', '{', '}', '[', ']', ',', '.', '&', '?'];
				
				foreach (explode(',', $values['advanced/custom_get_requests']) as $request) {
					
					$badChar = strpbrk($request, '\;\: \'\"\\\/`~{}[],.&?');
					
					if ($badChar !== false) {
						$invalidCharErrors .= '"' . $request . '",';
					}
					
					if (strpos($request, '=') === false) {
						$missingEqualsSigns .= '"' . $request . '",';
					}
				}
				if (!empty($invalidCharErrors) || !empty($missingEqualsSigns)) {
					$fields['advanced/custom_get_requests']['error'] = '';
					if (!empty($invalidCharErrors)) {
						$fields['advanced/custom_get_requests']['error'] .= ze\admin::phrase('Invalid characters in: [[invalid_chars]]. ', ['invalid_chars' => substr($invalidCharErrors, 0, -1)]);
					}
					
					if (!empty($missingEqualsSigns)) {
						$fields['advanced/custom_get_requests']['error'] .= ze\admin::phrase('Missing "=" sign in: [[missing_equals_sign]]. ', ['missing_equals_sign' => substr($missingEqualsSigns, 0, -1)]);
					}
				}
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$id = $box['key']['id'] ?? false;
		$parent_menu_id = $box['key']['parentMenuID'] ?? false;

		if ($id) {
			if (ze\row::get('menu_nodes', 'parent_id', $id)) {
				ze\priv::exitIfNot('_PRIV_EDIT_MENU_ITEM');
			} else {
				ze\priv::exitIfNot('_PRIV_EDIT_MENU_ITEM');
			}
		} else {
			if ($parent_menu_id) {
				ze\priv::exitIfNot('_PRIV_ADD_MENU_ITEM');
			} else {
				ze\priv::exitIfNot('_PRIV_ADD_MENU_ITEM');
			}
		}

		$cID = $cType = false;
		ze\content::getCIDAndCTypeFromTagId($cID, $cType, $values['text/hyperlink_target']);

		$submission = [];
		if (!$id) {
			$submission['section_id'] = ze\menu::sectionId($box['key']['sectionId']);
			$submission['parent_id'] = $parent_menu_id;
		}

		if (ze\ring::engToBoolean($box['tabs']['text']['edit_mode']['on'] ?? false)) {
			$submission['target_loc'] = $values['text/target_loc'];
	
	
			if ($submission['target_loc'] == 'exts') {
				$submission['target_loc'] = 'ext';
			}
	
			$submission['content_id'] = $cID;
			$submission['content_type'] = $cType;
			$submission['hide_private_item'] = $values['text/hide_private_item'];
			$submission['use_download_page'] = $values['text/use_download_page'];
			$submission['open_in_new_window'] = $values['text/open_in_new_window'];
			$submission['anchor'] = ($values['text/target_loc'] == 'int' && $values['text/link_to_anchor']) ? $values['text/hyperlink_anchor'] : '';
			$submission['document_id'] = $values['text/document_id'];
		}

		if (ze\ring::engToBoolean($box['tabs']['advanced']['edit_mode']['on'] ?? false)) {
			$submission['accesskey'] = $values['advanced/accesskey'];
			$submission['rel_tag'] = $values['advanced/rel_tag'];
			$submission['css_class'] = $values['advanced/css_class'];
			$submission['add_registered_get_requests'] = $values['advanced/add_registered_get_requests'];
	
			$call_static_method = $values['advanced/call_static_method'];
			$submission['module_class_name'] = $call_static_method ? $values['advanced/menu__module_class_name'] : '';
			$submission['method_name'] = $call_static_method ? $values['advanced/menu__method_name'] : '';
			$submission['param_1'] = $call_static_method ? $values['advanced/menu__param_1'] : '';
			$submission['param_2'] = $call_static_method ? $values['advanced/menu__param_2'] : '';
			
			if ($values['add_custom_get_requests']) {
				$customGetRequests = explode(',', $values['custom_get_requests']);
				$customGetRequests = implode('&', $customGetRequests);
				$submission['custom_get_requests'] = $customGetRequests;
			}
			
			if ($values['advanced/restrict_child_content_types']) {
				$submission['restrict_child_content_types'] = $values['advanced/restrict_child_content_types'];
			} else {
				$submission['restrict_child_content_types'] = '';
			}
		}

		$box['key']['id'] = ze\menuAdm::save($submission, $id);


		$langs = ze\lang::getLanguages();
		$numLangs = count($langs);

		foreach ($langs as $lang) {
	
			if (ze\ring::engToBoolean($box['tabs']['text']['edit_mode']['on'] ?? false)) {
				$submission = [];
		
				//Remove a menu node without any text.
				if (!$values['text/menu_title__'. $lang['id']]) {
					ze\menuAdm::removeText($box['key']['id'], $lang['id']);
					continue;
		
				} else {
					$submission['name'] = $values['text/menu_title__'. $lang['id']];
				}
		
				ze\menuAdm::saveText($box['key']['id'], $lang['id'], $submission);
			}
	
			$submission = [];
	
			if (ze\ring::engToBoolean($box['tabs']['text']['edit_mode']['on'] ?? false)) {
				if ($values['text/target_loc'] == 'exts') {
					$submission['ext_url'] = $values['text/ext_url__'. $lang['id']];
				} else {
					$submission['ext_url'] = $values['text/ext_url'];
				}
			}
	
			if (!empty($submission)) {
				ze\menuAdm::saveText($box['key']['id'], $lang['id'], $submission, $neverCreate = true);
			}
		}

		//For Menu Items in the Front End, navigate to that page if it's an internal link.
		//Always recalculate the link from the chosen destination, as this may just have been changed
		if ($cID) {
			$box['key']['cID'] = $cID;
			$box['key']['cType'] = $cType;
	
			if (!empty($box['key']['languageId'])) {
				ze\content::langEquivalentItem($box['key']['cID'], $box['key']['cType'], $box['key']['languageId']);
			}
		}
		
		//Images tab
		$id = $box['key']['id'];
		
		if ($imageId = $values['feature_image/image_id']) {
			if ($path = ze\file::getPathOfUploadInCacheDir($imageId)) {
				$imageId = ze\file::addToDatabase('image', $path);
			}
		}
		
		if (!$values['feature_image/show_image']) {
			$imageId = 0;
		} 
		
		
		if ($imageId) {
			ze\row::set('inline_images', ['image_id' => $imageId, 'in_use' => 1], ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $id, 'foreign_key_char' => 'image']);
		} else {
			ze\row::delete('inline_images', ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $id, 'foreign_key_char' => 'image']);
		}
		$submission['image_id'] = $imageId;

		if ($values['show_image']
			&& $values['use_rollover_image']
			&& $rolloverImageId = $values['feature_image/rollover_image_id']
			) {
				if ($path = ze\file::getPathOfUploadInCacheDir($rolloverImageId)) {
					$rolloverImageId = ze\file::addToDatabase('image', $path);
	
				}
				$submission['rollover_image_id'] = $rolloverImageId;
				ze\row::set('inline_images', ['image_id' => $rolloverImageId, 'in_use' => 1], ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $id, 'foreign_key_char' => 'rollover_image']);
		} else {
			$submission['rollover_image_id'] = 0;
			ze\row::delete('inline_images', ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $id, 'foreign_key_char' => 'rollover_image']);
		}
		ze\menuAdm::save($submission, $id);
		
		$row = [];
		$nodeId = $box['key']['id'];
		
		$featureImage = [
			'use_feature_image' => 0,
			'image_id' => 0,
			'use_rollover_image' => 0,
			'rollover_image_id' => 0,
			'title' => '',
			'text' => '',
			'link_type' => 'no_link',
			'link_visibility' => 'always_show',
			'dest_url' => '',
			'open_in_new_window' => 0
		];
		
		if ($values['feature_image/promo__feature_image_checkbox'] ) {
			$featureImage['use_feature_image'] = 1;
			$featureImage['image_id'] = $values['feature_image/promo__feature_image'];
			if ($location = ze\file::getPathOfUploadInCacheDir($values['feature_image/promo__feature_image'])) {
				$featureImage['image_id'] = ze\file::addToDatabase('image', $location);
			}
			
			
			if ($values['feature_image/promo__use_rollover']) {
				$featureImage['use_rollover_image'] = 1;
				$featureImage['rollover_image_id'] = $values['feature_image/promo__rollover_image'];
				if ($location = ze\file::getPathOfUploadInCacheDir($values['feature_image/promo__rollover_image'])) {
					$featureImage['rollover_image_id'] = ze\file::addToDatabase('image', $location);
				}
			}
			
			
			$featureImage['title'] = $values['feature_image/promo__title'];
			$featureImage['text'] = ze\ring::sanitiseWYSIWYGEditorHTML($values['feature_image/promo__text']);
			$featureImage['link_type'] = $values['feature_image/promo__link_type'];
			switch($featureImage['link_type']) {
				case 'content_item':
					$featureImage['dest_url'] = $values['feature_image/promo__hyperlink_target'];
					break;
				case 'external_url':
					$featureImage['dest_url'] = $values['feature_image/promo__url'];
					break;
			}
			$featureImage['link_visibility'] = $values['feature_image/promo__hide_private_item'];
			$featureImage['open_in_new_window'] = $values['feature_image/promo__target_blank'];
		}
		
		if ($featureImage['image_id']) {
			ze\row::set('inline_images', ['image_id' => $featureImage['image_id'], 'in_use' => 1], ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $nodeId, 'foreign_key_char' => 'feature_image']);
		} else {
			ze\row::delete('inline_images', ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $nodeId, 'foreign_key_char' => 'feature_image']);
		}
		
		if ($featureImage['rollover_image_id']) {
			ze\row::set('inline_images', ['image_id' => $featureImage['rollover_image_id'], 'in_use' => 1], ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $nodeId, 'foreign_key_char' => 'rollover_feature_image']);
		} else {
			ze\row::delete('inline_images', ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $nodeId, 'foreign_key_char' => 'rollover_feature_image']);
		}
		

		$featureImage['overwrite_alt_tag'] = $values['feature_image/promo__overwrite_alt_tag'];

		ze\row::set('menu_node_feature_image', $featureImage, ['node_id' => $nodeId]);
	}
	
	private function checkAnyNodeChildrenShouldShow ($nodeArray) {
		$shouldShow = false;
		$cachingRestrictions = [];
		foreach ($nodeArray as $node) {
			if ($node['children']) {
				$shouldShow = $this->checkAnyNodeChildrenShouldShow($node['children']);
			} elseif ($node['target_loc'] == 'int' && $node['equiv_id'] && $node['cType']) {
				$havePerm = ze\content::checkPerm($node['cID'],  $node['cType'], false, false, false, false);
				if ($havePerm) {
					$shouldShow = true;
					break;
				}
			} elseif ($node['target_loc'] == 'doc' && $node['document_id']) {
				$targetDocumentPrivacy = ze\row::get('documents', 'privacy', $node['document_id']);
				if ($targetDocumentPrivacy == 'public') {
					$shouldShow = true;
					break;
				}
			}
		}
		
		return $shouldShow;
	}
}