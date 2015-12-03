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


class zenario_common_features__organizer__menu_nodes extends module_base_class {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__menu/panels/menu_nodes') return;
		
		if (!(get('refiner__language') && get('refiner__language') != setting('default_language'))
		 && !get('refiner__show_language_choice')
		 && !in($mode, 'get_item_name', 'get_item_links')) {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_no_missing_items'];
		}
		
		$numLanguages = getNumLanguages();
		if ($numLanguages < 2) {
			unset($panel['columns']['translations']);
			unset($panel['item_buttons']['zenario_trans__view']);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__menu/panels/menu_nodes') return;
		
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
		
		$mrg = array(
			'lang' => getLanguageName($panel['key']['languageId']),
			'language_name' => getLanguageName($panel['key']['languageId'], false),
			'section' => menuSectionName($panel['key']['sectionId']));

		if ($numLanguages < 2) {
			unset($panel['item_buttons']['citems_translations']);
		}
		
		//Hide the "view content items under this menu node" if not showing the default language
		if ($panel['key']['languageId'] != setting('default_language')) {
			unset($panel['item_buttons']['view_content']);
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

		if (isset($panel['item_buttons']['edit_menu_text'])) {
			$panel['item_buttons']['edit_menu_text']['label'] =
				adminPhrase('Edit menu text in [[language_name]]', $mrg);
		}

		if (isset($panel['item_buttons']['define_menu_text'])) {
			$panel['item_buttons']['define_menu_text']['label'] =
				adminPhrase('Create menu text in [[language_name]]', $mrg);
		}

		if (isset($panel['item_buttons']['duplicate'])) {
			$panel['item_buttons']['duplicate']['label'] =
				adminPhrase('Create a translation in [[language_name]]', $mrg);
		}


		$panel['item']['tooltip_when_link_is_active'] = adminPhrase('View child Menu Nodes of &quot;[[name]]&quot;.');

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
				if (is_array($button)) {
					$button['hidden'] = $tagName != 'go_to_menu_in_sk';
				}
			}
			foreach ($panel['collection_buttons'] as $tagName => &$button) {
				if (is_array($button)) {
					$button['hidden'] = true;
				}
			}

		} else {
			unset($panel['item_buttons']['go_to_menu_in_sk']);
		}
	
	
		//If this is full, quick or select mode, and the admin looking at this only has permissions
		//to edit specific menu items, we'll need to check if the current admin can edit each
		//item.
		$checkSpecificPerms = in($mode, 'full', 'quick', 'select') && adminHasSpecificPerms();

		foreach ($panel['items'] as &$item) {
	
			$id = $item['mid'];
	
			if ($item['target_loc'] == 'int' && $item['internal_target']) {
				if ($item['redundancy'] == 'unique') {
					$item['tooltip'] = adminPhrase('This is a unique menu node. No other menu node links to this content item.');
					$item['css_class'] = 'zenario_menunode_internal_unique';
				} elseif ($item['redundancy'] == 'primary') {
					$item['tooltip'] = adminPhrase("This is a primary menu node. There are other secondary menu nodes linking to the same content item.");
					$item['css_class'] = 'zenario_menunode_internal';
				} else {
					$item['tooltip'] = adminPhrase("This is a secondary menu node. There's a primary menu node that also links to this content item.");
					$item['css_class'] = 'zenario_menunode_internal_secondary';
				}
	
			} elseif ($item['target_loc'] == 'ext' && $item['target']) {
				$item['tooltip'] = adminPhrase('This menu node links to an external URL.');
				$item['css_class'] = 'zenario_menunode_external';
	
			} else {
				$item['css_class'] = 'zenario_menunode_unlinked';
				$item['tooltip'] = adminPhrase('This menu node has no link. Unlinked menu nodes are hidden from visitors unless they have a child menu node that is visible.');
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
		
				//Apply formatting for untranslated menu nodes
				foreach ($panel['columns'] as $columnName => &$column) {
					if (isset($column['title'])) {
						if (in($columnName, 'ordinal', 'translations')) {
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
		
				$item['name'] = getMenuName($id, $panel['key']['languageId'], '[[name]] [[[language_id]], untranslated]');
				$item['row_css_class'] = 'organizer_untranslated_menu_node';
				$item['tooltip'] = adminPhrase('This menu node has not been translated into [[language_name]].', $mrg);
		
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
			
			if ($checkSpecificPerms) {
				if (checkPrivForMenuText('_PRIV_EDIT_MENU_TEXT', $id, $panel['key']['languageId'], $item['section_id'])) {
					$item['_specific_perms'] = true;
				}
			}
		}

		if (!$hierarchalSearch) {
			$panel['columns']['path']['hidden'] = true;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__menu/panels/menu_nodes') return;
		
		// mass_add_to_menu used in both content and menu nodes
		if (post('mass_add_to_menu') && checkPriv('_PRIV_ADD_MENU_ITEM')) {
			// Get tag ID from menu node ID
			$menuNodeDetails = getMenuNodeDetails($ids);
			$ids = $menuNodeDetails['content_type'] . '_' . $menuNodeDetails['equiv_id'];
			addContentItemsToMenu($menuNodeDetails['content_type'] . '_' . $menuNodeDetails['equiv_id'], $ids2);
		
		} else
		if (get('quick_create') && (
				(get('parentMenuID') && checkPriv('_PRIV_ADD_MENU_ITEM'))
			 || (!get('parentMenuID') && checkPriv('_PRIV_ADD_MENU_ITEM'))
		)) {
			$cID = $cType = false;
			$defaultName = '';
			if (get('parent__id') && getCIDAndCTypeFromTagId($cID, $cType, get('parent__id'))) {
				$defaultName = getItemTitle($cID, $cType);
	
			} elseif (get('parent__cID') && get('parent__cType')) {
				$defaultName = getItemTitle(get('parent__cID'), get('parent__cType'));
			}
	
			echo
				'<p>', 
					adminPhrase('Create a new Menu Node.'), 
				'</p><p>',
					adminPhrase('Name:'),
					' <input type="text" id="quick_create_name" name="quick_create_name" value="', htmlspecialchars($defaultName), '"/>',
				'</p>';

		} elseif (post('quick_create') && (
					(post('parentMenuID') && checkPriv('_PRIV_ADD_MENU_ITEM'))
				 || (!post('parentMenuID') && checkPriv('_PRIV_ADD_MENU_ITEM'))
		)) {
	
			if (!post('quick_create_name')) {
				echo adminPhrase('Please enter a name for your Menu Node.');
				return false;
	
			} else {
				$submission = array(
					'name' => post('quick_create_name'),
					'section_id' => post('sectionId'),
					'content_id' => 0,
					'target_loc' => 'none',
					'content_type' => '',
					'parent_id' => (int) post('parentMenuID'));
		
				 $menuId = saveMenuDetails($submission, post('languageId'));
				 saveMenuText($menuId, post('languageId'), $submission);
				 return $menuId;
			}
	
		//Unlink a Menu Node from its Content Item
		} elseif (post('detach') && checkPriv('_PRIV_EDIT_MENU_ITEM')) {
	
			$submission = array(
				'target_loc' => 'none');
	
			saveMenuDetails($submission, post('mID'));
			ensureContentItemHasPrimaryMenuItem(equivId(post('cID'), post('cType')), post('cType'));
	
		//Move one or more Menu Nodes to a different parent and/or the top level
		} elseif (post('move') && checkPriv('_PRIV_EDIT_MENU_ITEM')) {
	
			//By default, just move to the top level
			$languageId = post('languageId');
			$newParentId = 0;
			$newSectionId = post('child__refiner__section');
			$newNeighbourId = 0;
	
			//Look for a menu node in the request
			if ($ids2) {
				//If this is a numeric id, look up its details and move next to that Menu Node
				if (is_numeric($ids2) && $neighbour = getMenuNodeDetails($ids2)) {
					$newParentId = $neighbour['parent_id'];
					$newSectionId = $neighbour['section_id'];
					$newNeighbourId = $ids2;
		
				} else {
					//Check for a menu position, in the format CONCAT(section_id, '_', menu_id, '_', is_dummy_child)
					$menu_position = explode('_', $ids2);
					if (count($menu_position) == 3) {
				
						if ($menu_position[2]) {
							//Move the menu node to where a dummy placeholder is
							$newSectionId = $menu_position[0];
							$newParentId = $menu_position[1];
				
						} elseif ($menu_position[1]) {
							//Move the menu node to where another menu node is
							$newSectionId = $menu_position[0];
							$newParentId = getMenuParent($menu_position[1]);
							$newNeighbourId = $menu_position[1];
						}
					}
				}
			}
	
			moveMenuNode(
				$ids,
				$newSectionId,
				$newParentId,
				$newNeighbourId,
				$languageId);
	
			//Go to the location that we've just moved to, if this is Storekeeper Quick
			//if (request('parent__cID') && $ids) {
				//echo '<!--Go_To_Storekeeper_Panel:', getMenuItemStorekeeperDeepLink($ids, ifNull(request('languageId'), request('refiner__language'))), '-->';
			//}
	

		} elseif (post('remove') && checkPriv('_PRIV_DELETE_MENU_ITEM') && request('languageId') != setting('default_language')) {
			foreach (explodeAndTrim($ids) as $id) {
				//Only remove translation if another translation still exists
				if (($result = getRows('menu_text', 'menu_id', array('menu_id' => $id)))
				 && (sqlFetchRow($result))
				 && (sqlFetchRow($result))) {
					removeMenuText($id, request('languageId'));
				}
			}

		} elseif (post('delete') && checkPriv('_PRIV_DELETE_MENU_ITEM')) {
			foreach (explodeAndTrim($ids) as $id) {
				deleteRow('inline_images', array('foreign_key_to' => 'menu_node', 'foreign_key_id' => $id));
				deleteMenuNode($id);
			}

		//Move or reorder Menu Nodes
		} elseif ((post('reorder') || post('hierarchy')) && checkPriv('_PRIV_REORDER_MENU_ITEM')) {
			$sectionIds = array();
	
			//Loop through each moved Menu Node
			foreach (explodeAndTrim($ids) as $id) {
				//Look up the current section, parent and ordinal
				if ($menuNode = getRow('menu_nodes', array('section_id', 'parent_id', 'ordinal'), $id)) {
					$cols = array();
			
					//Update the ordinal if it is different
					if (isset($_POST['ordinals'][$id]) && $_POST['ordinals'][$id] != $menuNode['ordinal']) {
						$cols['ordinal'] = $_POST['ordinals'][$id];
					}
			
					//Update the parent id if it is different, and remember that we've done this
					if (isset($_POST['parent_ids'][$id]) && $_POST['parent_ids'][$id] != $menuNode['parent_id']) {
						$cols['parent_id'] = $_POST['parent_ids'][$id];
						$sectionIds[$menuNode['section_id']] = true;
					}
					updateRow('menu_nodes', $cols, $id);
				}
			}
	
			//Recalculate the Menu Hierarchy for any Menu Sections where parent ids have changed
			foreach ($sectionIds as $id => $dummy) {
				recalcMenuHierarchy($id);
			}
		} elseif (post('make_primary')) {
			$menuNodeDetails = getMenuNodeDetails($ids);
			$submission = array(
				'equiv_id' => $menuNodeDetails['equiv_id'],
				'target_loc' => 'int',
				'content_type' => $menuNodeDetails['content_type'],
				'redundancy' => 'primary');
			saveMenuDetails($submission, $ids);
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}