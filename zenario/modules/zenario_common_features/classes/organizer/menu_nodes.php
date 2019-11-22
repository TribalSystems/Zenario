<?php
/*
 * Copyright (c) 2019, Tribal Limited
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


class zenario_common_features__organizer__menu_nodes extends ze\moduleBaseClass {
	
	protected $numSyncAssistLangs = 0;
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__menu/panels/menu_nodes') return;
		
		if (!(($_GET['refiner__language'] ?? false) && ($_GET['refiner__language'] ?? false) != ze::$defaultLang)
		 && !($_GET['refiner__show_language_choice'] ?? false)
		 && !ze::in($mode, 'typeahead_search', 'get_item_name', 'get_item_links')) {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_no_missing_items'];
		}
		
		$numLanguages = ze\lang::count();
		if ($numLanguages < 2) {
			unset($panel['columns']['sync_assist']);
			unset($panel['columns']['translations']);
			unset($panel['item_buttons']['zenario_trans__view']);
			unset($panel['item_buttons']['citems_translations']);
		} else {
			$syncAssistLangs = ze\row::getValues('languages', 'id', ['sync_assist' => 1, 'id' => ['!' => ze::$defaultLang]]);
			if ($this->numSyncAssistLangs = count($syncAssistLangs)) {
				define('ZENARIO_SYNC_ASSIST_LANGS', ze\escape::in($syncAssistLangs, 'sql'));
			} else {
				unset($panel['columns']['sync_assist']);
			}
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__menu/panels/menu_nodes') return;
		
		$path = false;
		$separator = ' -> ';
		$sectionSeparator = ': ';
		$isFlatView = !isset($_REQUEST['_openToItemInHierarchy']) && !isset($_REQUEST['_openItemsInHierarchy']);
		$numLanguages = ze\lang::count();

		$menuItem = $menuParent = false;
		if ($refinerName == 'following_item_link') {
			$menuItem = ze\menu::details($refinerId);

		} elseif ($_GET['refiner__children'] ?? false) {
			$menuParent = ze\menu::details($_GET['refiner__children'] ?? false);
		}

		$panel['key']['languageId'] = ze::ifNull($_GET['refiner__language'] ?? false, ze::ifNull($_GET['languageId'] ?? false, ($_GET['language'] ?? false), ze::$defaultLang));
		$panel['key']['sectionId'] = ze\menu::sectionId(ze::ifNull($menuItem['section_id'] ?? false, $menuParent['section_id'] ?? false, ($_GET['refiner__section'] ?? false)));
		$panel['key']['parentId'] =
		$panel['key']['parentMenuID'] = $menuParent['id'] ?? false;
		
		$mrg = [
			'lang' => ze\lang::name($panel['key']['languageId']),
			'language_name' => ze\lang::name($panel['key']['languageId'], false),
			'section' => ze\menu::sectionName($panel['key']['sectionId'])];

		//Hide the "view content items under this menu node" if not showing the default language
		if ($panel['key']['languageId'] != ze::$defaultLang) {
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
			if($numLanguages==1){
				$panel['item_buttons']['edit_menu_text']['label'] = ze\admin::phrase('Edit menu text');
			}else{
				$panel['item_buttons']['edit_menu_text']['label'] =
					ze\admin::phrase('Edit menu text in [[language_name]]', $mrg);
			}
		}

		if (isset($panel['item_buttons']['define_menu_text'])) {
			$panel['item_buttons']['define_menu_text']['label'] =
				ze\admin::phrase('Create menu text in [[language_name]]', $mrg);
		}
			
			
		if (isset($panel['item_buttons']['duplicate'])) {
			$panel['item_buttons']['duplicate']['label'] =
				ze\admin::phrase('Create a translation in [[language_name]]', $mrg);
		}


		$panel['item']['tooltip_when_link_is_active'] = ze\admin::phrase('View child Menu Nodes of &quot;[[name]]&quot;.');
		
		if ($refinerName == 'menu_nodes_using_image') {
			$mrg = ze\row::get('files', ['filename'], $refinerId);
			$panel['title'] = ze\admin::phrase('Menu nodes using the image "[[filename]]"', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no menu nodes using the image "[[filename]]"', $mrg);

		} elseif ($panel['key']['parentId']) {
			$mrg['parent'] = ze\menu::name($panel['key']['parentId'], $panel['key']['languageId']);
			$mrg['n'] = ze\menuAdm::level($panel['key']['parentId']) + 1;
	
			$panel['title'] = ze\admin::phrase('"[[section]]" Section in [[lang]]: Child Menu Nodes of "[[parent]]" (Level [[n]])', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('The "[[parent]]" Menu Node has no children.', $mrg);
	
			if ($isFlatView) {
				$path = ze\menuAdm::path($panel['key']['parentId'], $panel['key']['languageId'], $separator);
			}

		} elseif ($panel['key']['sectionId']) {
			$panel['title'] = ze\admin::phrase('Menu Nodes in the "[[section]]" Section in [[lang]]', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no Menu Nodes in the "[[section]]" section.', $mrg);
			$panel['no_items_in_search_message'] = ze\admin::phrase('No Menu Nodes in the "[[section]]" section match your search.', $mrg);

		} else {
			unset($panel['reorder']);
			unset($panel['item']['tooltip']);
		}


		if ($isFlatView) {
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
		$checkSpecificPerms = ze::in($mode, 'full', 'quick', 'select') && ze\admin::hasSpecificPerms();
        
        $defaultMenuNodes = [];
        $contentTypesResult = ze\row::query('content_types', ['default_parent_menu_node'], []);
        while ($contentTypeRow = ze\sql::fetchAssoc($contentTypesResult)) {
            $defaultMenuNodes[$contentTypeRow['default_parent_menu_node']] = true;
        }
        
		foreach ($panel['items'] as &$item) {
	
			$id = $item['mid'];
			
			$internalTarget = $item['target_loc'] == 'int' && $item['equiv_id'];
	        
	        if (isset($defaultMenuNodes[$id])) {
	            $item['is_content_type_default_menu_node'] = true;
	        }
	        
	        $item['css_class'] = ze\menuAdm::cssClass($item);
	        
			if ($internalTarget) {
				if ($item['redundancy'] == 'unique') {
					$item['tooltip'] = ze\admin::phrase('This is a unique menu node. No other menu node links to this content item.');
				} elseif ($item['redundancy'] == 'primary') {
					$item['tooltip'] = ze\admin::phrase("This is a primary menu node. There are other secondary menu nodes linking to the same content item.");
				} else {
					$item['tooltip'] = ze\admin::phrase("This is a secondary menu node. There's a primary menu node that also links to this content item.");
				}
	
			} elseif ($item['target_loc'] == 'ext' && $item['target']) {
				$item['tooltip'] = ze\admin::phrase('This menu node links to an external URL.');
	
			} else {
				$item['tooltip'] = ze\admin::phrase('This menu node has no link. Unlinked menu nodes are hidden from visitors unless they have a child menu node that is visible.');
			}
	
			if ($item['children']) {
				$item['traits'] = ['has_children' => true];
			} else {
				$item['traits'] = ['childless' => true];
			}
	
			if ($item['name'] === null) {
				$item['css_class'] .= ' ghost';
		
				//Apply formatting for untranslated menu nodes
				foreach ($panel['columns'] as $columnName => &$column) {
					if (isset($column['title'])) {
						if (ze::in($columnName, 'ordinal', 'translations')) {
							//leave the column as is
				
						} elseif (ze::in($columnName, 'name', 'redundancy', 'language_id')) {
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
				if (!$item['target_lang'] || $item['target_lang'] != ($item['text_lang'] ?: $panel['key']['languageId'])) {
					$item['cell_css_classes'] = [];
					$item['cell_css_classes']['target'] = 'ghost';
			
					if ($internalTarget && !$item['target_content_id']) {
						$item['traits']['can_duplicate'] = true;
					}
				}
		
				$item['traits']['ghost'] = true;
		
				$item['name'] = ze\menu::name($id, $panel['key']['languageId'], '[[name]] [[[language_id]], untranslated]');
				$item['row_class'] = 'organizer_untranslated_menu_node';
				$item['tooltip'] = ze\admin::phrase('This menu node has not been translated into [[language_name]].', $mrg);
		
				if ($isFlatView) {
					if ($path) {
						$item['path'] = $path. $separator. $item['name'];
					} else {
						$item['path'] = ze\menuAdm::path($id, $panel['key']['languageId'], $separator);
					}
					$item['path'] = ze\admin::phrase('MISSING [[name]]', ['name' => $item['path']]);
				}
	
			} else {
				if ($isFlatView) {
					if ($path) {
						$item['path'] = $path. $separator. $item['name'];
		
					} else {
						$item['path'] = ze\menuAdm::path($id, $panel['key']['languageId'], $separator);
					}
				}
		
				//Missing Menu Nodes to Content Items can be created by duplicating the Content Item
				if (!$item['target_lang'] || $item['target_lang'] != ($item['text_lang'] ?: $panel['key']['languageId'])) {
					if ($internalTarget) {
						$item['traits']['can_duplicate'] = true;
					}
				}
		
				if ($panel['key']['languageId'] != ze::$defaultLang && !empty($item['translations']) && $item['translations'] > 1) {
					$item['traits']['removable'] = true;
				}
			}
	
			if (!empty($internalTarget)) {
				$item['frontend_link'] = ze\link::toItem($item['target_content_id'], $item['target_content_type'], false, 'zenario_sk_return=navigation_path');
				$item['traits']['content'] = true;
	
			} elseif (!empty($item['target'])) {
				$item['frontend_link'] = $item['target'];
			}
	
			if ($mode == 'get_item_links' || $isFlatView) {
				$item['navigation_path'] = ze\menuAdm::organizerLink($id, $panel['key']['languageId']);
			}
	
			if (isset($item['translations'])) {
				if ($item['translations'] == 1) {
					$item['translations'] = ze\admin::phrase('untranslated');
				} else {
					$item['translations'] .= ' / '. $numLanguages;
				}
			}
	
			if (isset($item['sync_assist'])
			 && $item['sync_assist'] < $this->numSyncAssistLangs) {
				
				$item['cell_css_classes'] = $item['cell_css_classes'] ?? [];
	
				if (isset($item['translations'])) {
					$item['cell_css_classes']['translations'] = 'orange';
				}
			}
	
			unset($item['target_loc']);
			unset($item['sync_assist']);
			unset($item['equiv_id']);
			unset($item['ext_url']);
			unset($item['target_content_id']);
			unset($item['target_content_type']);
	
			if ($isFlatView) {
				$item['ordinal'] = ze\menuAdm::path($id, $panel['key']['languageId'], $separator, true);
			}
			
			if ($checkSpecificPerms) {
				if (ze\priv::onMenuText('_PRIV_EDIT_MENU_TEXT', $id, $panel['key']['languageId'], $item['section_id'])) {
					$item['_specific_perms'] = true;
				}
			}
		}

		if (!$isFlatView) {
			$panel['columns']['path']['hidden'] = true;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__menu/panels/menu_nodes') return;
		
		// mass_add_to_menu used in both content and menu nodes
		if (($_POST['mass_add_to_menu'] ?? false) && ze\priv::check('_PRIV_ADD_MENU_ITEM')) {
			// Get tag ID from menu node ID
			$menuNodeDetails = ze\menu::details($ids);
			$ids = $menuNodeDetails['content_type'] . '_' . $menuNodeDetails['equiv_id'];
			ze\menuAdm::addContentItems($menuNodeDetails['content_type'] . '_' . $menuNodeDetails['equiv_id'], $ids2);
	
		//Unlink a Menu Node from its Content Item
		} elseif (($_POST['detach'] ?? false) && ze\priv::check('_PRIV_EDIT_MENU_ITEM')) {
	
			$submission = [
				'target_loc' => 'none'];
	
			ze\menuAdm::save($submission, ($_POST['mID'] ?? false));
			ze\menuAdm::ensureContentItemHasPrimaryNode(ze\content::equivId($_POST['cID'] ?? false, ($_POST['cType'] ?? false)), ($_POST['cType'] ?? false));
	
		//Move one or more Menu Nodes to a different parent and/or the top level
		} elseif (($_POST['move'] ?? false) && ze\priv::check('_PRIV_EDIT_MENU_ITEM')) {
	
			//By default, just move to the top level
			$languageId = $_POST['languageId'] ?? false;
			$newParentId = 0;
			$newSectionId = $_POST['child__refiner__section'] ?? false;
			$newNeighbourId = 0;
	
			//Look for a menu node in the request
			if ($ids2) {
				//If this is a numeric id, look up its details and move next to that Menu Node
				if (is_numeric($ids2) && $neighbour = ze\menu::details($ids2)) {
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
							$newParentId = ze\menu::parentId($menu_position[1]);
							$newNeighbourId = $menu_position[1];
						}
					}
				}
			}
	
			ze\menuAdm::moveMenuNode(
				$ids,
				$newSectionId,
				$newParentId,
				$newNeighbourId,
				$afterNeighbour = 0,
				$languageId);
	

		} elseif (($_POST['remove'] ?? false) && ze\priv::check('_PRIV_DELETE_MENU_ITEM') && ($_REQUEST['languageId'] ?? false) != ze::$defaultLang) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				//Only remove translation if another translation still exists
				if (($result = ze\row::query('menu_text', 'menu_id', ['menu_id' => $id]))
				 && (ze\sql::fetchRow($result))
				 && (ze\sql::fetchRow($result))) {
					ze\menuAdm::removeText($id, ($_REQUEST['languageId'] ?? false));
				}
			}

		} elseif (($_POST['delete'] ?? false) && ze\priv::check('_PRIV_DELETE_MENU_ITEM')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				ze\row::delete('inline_images', ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $id]);
				ze\menuAdm::delete($id);
			}

		//Move or reorder Menu Nodes
		} elseif ((($_POST['reorder'] ?? false) || ($_POST['hierarchy'] ?? false)) && ze\priv::check('_PRIV_REORDER_MENU_ITEM')) {
			$sectionIds = [];
	
			//Loop through each moved Menu Node
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				//Look up the current section, parent and ordinal
				if ($menuNode = ze\row::get('menu_nodes', ['section_id', 'parent_id', 'ordinal'], $id)) {
					$cols = [];
			
					//Update the ordinal if it is different
					if (isset($_POST['ordinals'][$id]) && $_POST['ordinals'][$id] != $menuNode['ordinal']) {
						$cols['ordinal'] = $_POST['ordinals'][$id];
					}
			
					//Update the parent id if it is different, and remember that we've done this
					if (isset($_POST['parent_ids'][$id]) && $_POST['parent_ids'][$id] != $menuNode['parent_id']) {
						$cols['parent_id'] = $_POST['parent_ids'][$id];
						$sectionIds[$menuNode['section_id']] = true;
					}
					ze\row::update('menu_nodes', $cols, $id);
				}
			}
	
			//Recalculate the Menu Hierarchy for any Menu Sections where parent ids have changed
			foreach ($sectionIds as $id => $dummy) {
				ze\menuAdm::recalcHierarchy($id);
			}
			
			//The top column is updated
			if(ze\module::inc('zenario_menu_multicolumn')){
				foreach (ze\ray::explodeAndTrim($ids) as $id) {
					if(ze\row::exists(ZENARIO_MENU_MULTICOLUMN_PREFIX. 'nodes_top_of_column', ['node_id' => $id])){
						$isEnabled = ze\row::get(ZENARIO_MENU_MULTICOLUMN_PREFIX. 'nodes_top_of_column','top_of_column', ['node_id' => $id]);
						if($isEnabled){
							$nodeLevel = self::getNodeLevel($id);
							if($nodeLevel == 2){
								ze\row::update(ZENARIO_MENU_MULTICOLUMN_PREFIX. 'nodes_top_of_column',['top_of_column' => 0],['node_id' => $id]);
							}
						}
					}
				}
			}
			
		} elseif ($_POST['make_primary'] ?? false) {
			$menuNodeDetails = ze\menu::details($ids);
			$submission = [
				'equiv_id' => $menuNodeDetails['equiv_id'],
				'target_loc' => 'int',
				'content_type' => $menuNodeDetails['content_type'],
				'redundancy' => 'primary'];
			ze\menuAdm::save($submission, $ids);
		}
	}
	
	
	public function getNodeLevel($nodeId,$i=1){
		if($parentId = ze\row::get('menu_nodes', 'parent_id', ['id'=>$nodeId])){
			self::getNodeLevel($parentId,++$i);
		}
		return $i;
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}