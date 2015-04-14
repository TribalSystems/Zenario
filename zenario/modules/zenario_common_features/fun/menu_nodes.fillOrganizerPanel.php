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


$path = false;
$separator = ' -> ';
$sectionSeparator = ': ';
$hierarchalSearch = false;
$numLanguages = getNumLanguages();

$menuItem = $menuParent = false;
if ($refinerName == 'following_item_link') {
	$menuItem = getMenuNodeDetails($refinerId);

} elseif (get('refiner__children')) {
	$menuParent = getMenuNodeDetails(get('refiner__children'));
}

$panel['key']['languageId'] = ifNull(get('refiner__language'), ifNull(get('languageId'), get('language'), setting('default_language')));
$panel['key']['sectionId'] = menuSectionId(ifNull(arrayKey($menuItem, 'section_id'), arrayKey($menuParent, 'section_id'), get('refiner__section')));
$panel['key']['parentId'] =
$panel['key']['parentMenuID'] = arrayKey($menuParent, 'id');
$mrg = array('lang' => getLanguageName($panel['key']['languageId']), 'section' => menuSectionName($panel['key']['sectionId']));

if ($numLanguages < 2) {
	unset($panel['item_buttons']['citems_translations']);
}

if (isset($panel['collection_buttons']['tree_explorer']['popout']['href'])) {
	if ($panel['key']['parentId']) {
		$panel['collection_buttons']['tree_explorer']['popout']['href'] .= '?type=menu_node&id='. (int) $panel['key']['parentId'];
	} else {
		$panel['collection_buttons']['tree_explorer']['popout']['href'] .= '?type=section&id='. (int) $panel['key']['sectionId'];
	}
}


//Pass the Storekeeper Mode onto the create and edit buttons
if (isset($panel['item_buttons']['edit'])) {
	$panel['item_buttons']['edit']['admin_box']['key']['mode'] = $mode;
}
if (isset($panel['collection_buttons']['create'])) {
	$panel['collection_buttons']['create']['admin_box']['key']['mode'] = $mode;
}


$panel['item']['tooltip_when_link_is_active'] = adminPhrase('View child Menu Nodes of &quot;[[name]]&quot;.');
if (in($mode, 'full', 'quick') && checkPriv('_PRIV_REORDER_MENU_ITEM')) {
	$panel['item']['tooltip'] = adminPhrase('To reorder menu nodes, drag and drop. Changes will take effect after a further confirmation.');
	$panel['item']['tooltip_when_link_is_active'] .= '<br/>'. $panel['item']['tooltip'];
}

if ($panel['key']['parentId']) {
	$mrg['parent'] = getMenuName($panel['key']['parentId'], $panel['key']['languageId']);
	$mrg['n'] = getMenuItemLevel($panel['key']['parentId']) + 1;
	
	$panel['title'] = adminPhrase('"[[section]]" Section in [[lang]]: Child Menu Nodes of "[[parent]]" (Level [[n]])', $mrg);
	$panel['no_items_message'] = adminPhrase('The "[[parent]]" Menu Node has no children.', $mrg);
	$hierarchalSearch = isset($_GET['_search']);
	
	if ($hierarchalSearch) {
		$path = getMenuPath($panel['key']['parentId'], $panel['key']['languageId'], $separator);
	}

} elseif ($panel['key']['sectionId']) {
	$panel['title'] = adminPhrase('Menu Nodes in the "[[section]]" Section in [[lang]]', $mrg);
	$panel['no_items_message'] = adminPhrase('There are no Menu Nodes in the "[[section]]" section.', $mrg);
	$panel['no_items_in_search_message'] = adminPhrase('No Menu Nodes in the "[[section]]" section match your search.', $mrg);
	$hierarchalSearch = isset($_GET['_search']);

} else {
	unset($panel['reorder']);
	unset($panel['item']['tooltip']);
}


if ($hierarchalSearch) {
	$panel['force_view_mode'] = 'list';
	
	$panel['columns']['ordinal']['align_right'] = false;
	$panel['columns']['ordinal']['format'] = 'remove_zero_padding';
	
	unset($panel['reorder']);
	unset($panel['item']['link']);
	unset($panel['item']['tooltip']);
	foreach ($panel['item_buttons'] as $tagName => &$button) {
		if (!isInfoTag($tagName)) {
			$button['hidden'] = $tagName != 'go_to_menu_in_sk';
		}
	}
	foreach ($panel['collection_buttons'] as $tagName => &$button) {
		if (!isInfoTag($tagName)) {
			$button['hidden'] = true;
		}
	}

} else {
	unset($panel['item_buttons']['go_to_menu_in_sk']);
}


foreach ($panel['items'] as &$item) {
	
	$id = $item['mid'];
	
	if ($item['target_loc'] == 'int' && $item['internal_target']) {
		if ($item['redundancy'] == 'primary') {
			$item['css_class'] = 'zenario_menunode_internal';
		} else {
			$item['css_class'] = 'zenario_menunode_internal_secondary';
		}
	
	} elseif ($item['target_loc'] == 'ext' && $item['target']) {
		$item['css_class'] = 'zenario_menunode_external';
	
	} else {
		$item['css_class'] = 'zenario_menunode_unlinked';
	}
	
	if (empty($item['parent_id'])) {
		$item['css_class'] .= ' zenario_menunode_toplevel';
	}
	if (!empty($item['children'])) {
		$item['css_class'] .= ' zenario_menunode_with_children';
	}
	
	if ($item['children']) {
		$item['traits'] = array('has_children' => true);
	} else {
		$item['traits'] = array('childless' => true);
	}
	
	if ($item['name'] === null) {
		$item['css_class'] .= ' ghost';
		
		//Apply formatting to the row for missing equivs
		foreach ($panel['columns'] as $columnName => &$column) {
			if (isset($column['title'])) {
				if (in($columnName, 'ordinal', 'translations', 'target')) {
					//leave the column as is
				
				} elseif (in($columnName, 'name', 'redundancy', 'language_id')) {
					//ghost the column
					$item[$columnName. '__css_class'] = 'ghost';
				} else {
					//Don't show anything in the column
					$item[$columnName] = '';
				}
			}
		}
		
		//Missing Menu Nodes should have their target faded,
		//with the exception of the rare case where a Content Item exists in a Language but the Menu Text does not
		if (!$item['target_lang'] || $item['target_lang'] != ifNull($item['text_lang'], $panel['key']['languageId'])) {
			$item['cell_css_classes'] = array();
			$item['cell_css_classes']['target'] = 'ghost';
			
			if ($item['internal_target'] && !$item['target_content_id']) {
				$item['traits']['can_duplicate'] = true;
			}
		}
		
		$item['traits']['ghost'] = true;
		
		$item['name'] = getMenuName($id, $panel['key']['languageId']);
		
		$item['name'] = adminPhrase('MISSING [[name]]', array('name' => $item['name']));
		
		if ($hierarchalSearch) {
			if ($path) {
				$item['path'] = $path. $separator. $item['name'];
			} else {
				$item['path'] = getMenuPath($id, $panel['key']['languageId'], $separator);
			}
			$item['path'] = adminPhrase('MISSING [[name]]', array('name' => $item['path']));
		}
	
	} else {
		if ($hierarchalSearch) {
			if ($path) {
				$item['path'] = $path. $separator. $item['name'];
		
			} else {
				$item['path'] = getMenuPath($id, $panel['key']['languageId'], $separator);
			}
		}
		
		//Missing Menu Nodes to Content Items can be created by duplicating the Content Item
		if (!$item['target_lang'] || $item['target_lang'] != ifNull($item['text_lang'], $panel['key']['languageId'])) {
			if ($item['internal_target']) {
				$item['traits']['can_duplicate'] = true;
			}
		}
		
		if ($panel['key']['languageId'] != setting('default_language') && !empty($item['translations']) && $item['translations'] > 1) {
			$item['traits']['removable'] = true;
		}
	}
	
	if (!empty($item['internal_target'])) {
		$item['frontend_link'] = linkToItem($item['target_content_id'], $item['target_content_type'], false, 'zenario_sk_return=navigation_path');
		$item['traits']['content'] = true;
	
	} elseif (!empty($item['target'])) {
		$item['frontend_link'] = $item['target'];
	}
	
	if ($mode == 'get_item_links' || $hierarchalSearch) {
		$item['navigation_path'] = getMenuItemStorekeeperDeepLink($id, $panel['key']['languageId']);
	}
	
	if (isset($item['translations'])) {
		if ($item['translations'] == 1) {
			$item['translations'] = adminPhrase('untranslated');
		} else {
			$item['translations'] .= ' / '. $numLanguages;
		}
	}
	
	unset($item['target_loc']);
	unset($item['internal_target']);
	unset($item['target_content_id']);
	unset($item['target_content_type']);
	
	if ($hierarchalSearch) {
		$item['ordinal'] = getMenuPath($id, $panel['key']['languageId'], $separator, true);
	}
}

if (!$hierarchalSearch) {
	$panel['columns']['path']['hidden'] = true;
}

return false;
