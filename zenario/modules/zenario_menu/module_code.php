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

class zenario_menu extends ze\moduleBaseClass {

	var $currentMenuId = false;
	var $sectionId = false;
	var $startFrom;
	var $numLevels;
	var $maxLevel1MenuItems;
	var $language;
	var $onlyFollowOnLinks;
	var $onlyIncludeOnLinks;
	var $showInvisibleMenuItems;
	var $showMissingMenuNodes;
	var $requests = false;
	
	protected $headerObjects = [];
	protected $subSections = [];
	protected $mergeFields = [];
	protected $parentMenuId = 0;
	
	//Load settings from the instance of this plugin
	function init() {
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = true, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		//Get the section id from the Plugin Settings, but allow for other Modules to overwrite this logic by setting $this->sectionId
		//to something other than "false".
		if ($this->sectionId === false) {
			$this->sectionId = $this->setting('menu_section');
		}
		
		$this->sectionId = ze\menu::sectionId($this->sectionId);
		
		$this->startFrom				= $this->setting('menu_start_from');

		if ($this->moduleClassName == 'zenario_menu_responsive_push_pull') {
			$numLevels = $this->setting('menu_number_of_levels');
			if (!$numLevels || $numLevels == 'all') {
				$this->numLevels = 0;
			} else {
				$this->numLevels = (int) $numLevels;
			}

			//In push-pull menu plugin, always show distant branches
			//instead of trying to load the value of a hidden checkbox menu_show_all_branches.
			$this->onlyFollowOnLinks = !(!$numLevels || $numLevels == 'all' || $numLevels > 1);
		} else {
			$this->numLevels = (int) $this->setting('menu_number_of_levels');
			$this->onlyFollowOnLinks = !($this->setting('menu_show_all_branches') && ($this->setting('menu_number_of_levels') > 1));
		}
		
		$this->maxLevel1MenuItems		= 999;
		$this->language					= false;
		$this->onlyIncludeOnLinks		= false;
		$this->showInvisibleMenuItems	= false;
		$this->showMissingMenuNodes		= $this->setting('show_missing_menu_nodes');
		
		$this->showInMenuMode();
		
		if(ze::setting('zenario_menu__allow_overriding_of_invisible_flag_on_menu_nodes') && $this->setting('show_invisible_menu_nodes')){
			$this->showInvisibleMenuItems=true;
		}
		
		$this->setting('show_missing_menu_nodes');
		
		//Get the Menu Node for this content item
		$this->currentMenuId = ze\menu::getIdFromContentItem(ze::$equivId, ze::$cType, $this->sectionId);
		
		return $this->currentMenuId || !$this->setting('hide_if_current_item_not_in_menu') || $this->methodCallIs('handlePluginAJAX');
	}
	
	
	public function getUserMergeFields() {
		if ($this->setting('change_welcome_message') && ($id = ze\user::id()) && ($userDetails = ze\user::details($id))) {
			$userDetails['welcome_message'] = $this->phrase($this->setting('welcome_message'), $userDetails);
			
			if ($this->setting('show_group_name_when_user_is_in_groups') && $this->setting('user_groups')) {
				$groupsToShow = [];
				
				$groupsUserIsIn = ze\user::groups($id, $flat = true, $getLabelWhenFlat = true);
				foreach (explode(",", $this->setting('user_groups')) as $groupId) {
					if (array_key_exists($groupId, $groupsUserIsIn)) {
						$groupsToShow[$groupId] = $groupsUserIsIn[$groupId];
					}
				}
				
				if ($groupsToShow) {
					$userDetails['groups'] = implode(", ", $groupsToShow);
				}
			}
			
			return $userDetails;
		}
		return false;
	}
	
	public function getTitleMergeFields() {
		
		$mrg = [];
		
		if ($this->setting('show_parent_menu_node_text')) {
			if ($this->parentMenuId && $parentMenuDetails = ze\menu::getInLanguage($this->parentMenuId, $this->language)) {
				$mrg['Parent_Name'] = htmlspecialchars($parentMenuDetails['name']);
				if ($parentMenuDetails['cID']) {
					//If there is a link in the target language, display it...
					$mrg['Parent_Link'] = htmlspecialchars(
						$this->linkToItem($parentMenuDetails['cID'], $parentMenuDetails['cType'], false, $this->requests, $parentMenuDetails['alias'])
					);
				} else {
					//...otherwise, try to get a link in the default language.
					$parentMenuDetails = ze\menu::getInLanguage($this->parentMenuId, ze::$defaultLang);
					if ($parentMenuDetails['cID']) {
						if (empty($this->requests)) {
							$this->requests = ['visLang' => ze::$visLang];
						}
						$mrg['Parent_Link'] = htmlspecialchars(
							$this->linkToItem($parentMenuDetails['cID'], $parentMenuDetails['cType'], false, $this->requests, $parentMenuDetails['alias'])
						);
					}
				}
			} else {
				$homepage = $this->getHomepage(ze\content::visitorLangId());
				$mrg['Parent_Name'] = htmlspecialchars($homepage['name']);
				$mrg['Parent_Link'] = htmlspecialchars($homepage['url']);
			}
		}
		
		return $mrg;
	}
	
	//Main Display function for the slot
	function showSlot() {
		
		if (!$this->currentMenuId && $this->setting('hide_if_current_item_not_in_menu')) {
			return;
		}
		
		//Load Settings
		//Work out where to start from
		$this->parentMenuId = $this->getStartNode();
		
		$this->subSections['Title'] = true;
		$this->subSections['User_Names'] = true;
		
		//Set default values for this->maxLevel1MenuItems and the this->language
		if (!$this->maxLevel1MenuItems) {
			$this->maxLevel1MenuItems = 9;
		}
		
		if ($this->language === false) {
			$this->language = ze::$visLang;
		}
		
		
		
		
		//Get the menu structure from the database.
		$cachingRestrictions = 0;
		$menuArray =
			ze\menu::getStructure(
				$cachingRestrictions,
				$this->sectionId, $this->currentMenuId, $this->parentMenuId,
				$this->numLevels, $this->maxLevel1MenuItems, $this->language,
				$this->onlyFollowOnLinks, $this->onlyIncludeOnLinks, 
				$this->showInvisibleMenuItems,
				$this->showMissingMenuNodes,
				$this->requests,
				ze\content::showUntranslatedContentItems()
			);
							 
		switch ($cachingRestrictions) {
			case ze\menu::privateItemsExist:
				$this->allowCaching(
					$atAll = true, $ifUserLoggedIn = false, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
				break;
			case ze\menu::staticFunctionCalled:
				$this->allowCaching(false);
				break;
		}
		
		
		//Draw the Menu Nodes we found
		$this->mergeFields['nodes'] = $this->getMenuMergeFields($menuArray);

		//The features below are currently only used in Menu (Vertical) module,
		//which extends this one.
		
		//1) Custom title feature
		$this->mergeFields['show_custom_title'] = $this->setting('show_custom_title');
		$this->mergeFields['title_tags'] = $this->setting('title_tags');
		$this->mergeFields['custom_title'] = $this->setting('custom_title');

		//2) Open/close menu
		if ($this->setting('enable_open_close')) {
			$this->mergeFields['enable_open_close'] = true;
			//Check if the menu is supposed to be open, or closed.
			//Check the session variables, or fall back on the plugin setting.
			
			//Check if the state has already been set before...
            if (!isset($_SESSION['vertical_menu_open_closed_state']) || !ze::in($_SESSION['vertical_menu_open_closed_state'], 'open', 'closed')) {
                $_SESSION['vertical_menu_open_closed_state'] = $this->setting('open_close_initial_state');
            }
			
			$this->mergeFields['open_closed_state'] = $_SESSION['vertical_menu_open_closed_state'];
			$this->mergeFields['ajax_link'] = $this->pluginAJAXLink();
		}

		//3) Full width view with 1-5 columns
		if ($this->setting('menu_number_of_levels') == '1_full_width') {
			$this->mergeFields['full_width_view'] = true;
			$this->mergeFields['num_columns'] = ($this->setting('number_of_columns_full_width') ?: 1);
			$this->mergeFields['menu_node_count'] = count($this->mergeFields['nodes']);
			$this->mergeFields['num_items_per_column'] = (int) ceil($this->mergeFields['menu_node_count'] / $this->mergeFields['num_columns']);
		}

		$this->mergeFields['containerId'] = $this->containerId;

		if ((ze::in($this->setting('menu_number_of_levels'), '1', '2', '3')) && $this->setting('limit_initial_level_1_menu_nodes_checkbox')) {
			$this->mergeFields['limit_initial_level_1_menu_nodes'] = $this->setting('limit_initial_level_1_menu_nodes');
			$this->mergeFields['menu_max_number_of_levels'] = $this->setting('menu_number_of_levels');
			$this->mergeFields['text_for_more_button'] = $this->setting('text_for_more_button');
		}
		
		if ($this->moduleClassName != 'zenario_menu_responsive_push_pull') {
			$this->twigFramework($this->mergeFields);
		}
	}
	
	function getStartNode() {
		$currentMenuId = $this->setting("specific_menu_node") ? $this->setting("specific_menu_node") : $this->currentMenuId;
		$this->startFrom = $this->setting("specific_menu_node") ? '_MENU_CURRENT_LEVEL' : $this->startFrom;
	
		$parentMenuId = 0;
		
		if ($this->startFrom == 'Menu Level 1' || $this->startFrom == '_MENU_LEVEL_1') {
			$parentMenuId = 0;
			
		} elseif ($this->startFrom == 'Current Menu Level' || $this->startFrom == '_MENU_CURRENT_LEVEL') {
			$parentMenuId = $currentMenuId;
			
		} elseif ($this->startFrom == 'Menu Level Above' || $this->startFrom == '_MENU_LEVEL_ABOVE') {
			$parentMenuId = ze\menu::parentId($currentMenuId);
			
		} elseif ($this->startFrom == '_MENU_LEVEL_TWO_ABOVE') {
			$parentMenuId = ze\menu::parentId(ze\menu::parentId($currentMenuId));
			
		} elseif ($this->startFrom == 'Menu Level 2' || $this->startFrom == '_MENU_LEVEL_2') {
			$this->getMenuAncestor($currentMenuId, $ancestor, $ancestorButOne);
			$parentMenuId = $ancestor;
			
		} elseif ($this->startFrom == 'Menu Level 3' || $this->startFrom == '_MENU_LEVEL_3') {
			$this->getMenuAncestor($currentMenuId, $ancestor, $ancestorButOne);
			$parentMenuId = $ancestorButOne;
			
		} elseif ($this->startFrom == 'Menu Level 4' || $this->startFrom == '_MENU_LEVEL_4') {
			$branch = $this->getMenuBranch($currentMenuId);
			$parentMenuId = ($branch[2] ?? false)?($branch[2] ?? false):$currentMenuId;
			
		} elseif ($this->startFrom == 'Menu Level 5' || $this->startFrom == '_MENU_LEVEL_5') {
			$branch = $this->getMenuBranch($currentMenuId);
			$parentMenuId = ($branch[3] ?? false)?($branch[3] ?? false):$currentMenuId;
			
		} 

		return $parentMenuId;
	}
	
	//Recursive function to draw Menu Nodes from the database
	function getMenuMergeFields(&$menuArray, $depth = 1, $parentId = false) {
	
		if ($depth>1000) {
			echo "Function aborted due to infinite recursion loop";
			exit;
		}
		
		if ($this->setting('reverse_order')) {
			$menuArray = array_reverse($menuArray, true);
		}
		
		
		$menuMergeFields = [];
		
		if (is_array($menuArray)) {
			$i = 0;
			foreach ($menuArray as &$row) {
				if ($menuNodeMergeFields = $this->getMenuNodeMergeFields($depth, ++$i, $row)) {
					
					if (!empty($row['children']) && is_array($row['children'])) {
						$parentId = $row['mID'] ?? 0;
						$menuNodeMergeFields['children'] = $this->getMenuMergeFields($row['children'], $depth + 1, $parentId);
						
						$menuNodeMergeFields['All_Children_Are_Hidden'] = true;
						foreach ($menuNodeMergeFields['children'] as $child) {
							if (empty($child['Conditionally_Hidden'])) {
								$menuNodeMergeFields['All_Children_Are_Hidden'] = false;
								break;
							}
						}
					}

					if ($parentId) {
						$menuNodeMergeFields['parentId'] = (int) $parentId;
					}
					
					$menuMergeFields[] = $menuNodeMergeFields;
				}
			}
		}
		
		return $menuMergeFields;
	}
	
	function getMenuNodeMergeFields($depth, $i, &$row) {
		
		$theme = 'Level_X_Link';
		
		$objects = [];
		$objects['depth'] = (int) $depth;
		$objects['mID'] = $row['mID'] ?? 0;
		$objects['Hyperlink'] = $this->drawMenuItem($row);
		
		$objects['Class'] = 'level'. $depth. ' level'. $depth. '_'. $i;
		
		if (!empty($row['on'])) {
			$objects['Class'] .= ' level'. $depth. '_on level'. $depth. '_'. $i. '_on';
		}
		
		if (!empty($row['name'])) {
			$objects['Name'] = htmlspecialchars($row['name']);
		} else {
			$objects['Name'] = '';
		}

		if (!empty($row['id'])) {
			$objects['id'] = $row['id'];
		}
		
		if (!empty($row['ext_url'])) {
			$objects['Class'] .= ' link_external';
		}

		if (!empty($row['privacy'])) {
			$objects['privacy'] = $row['privacy'];

			if ($objects['privacy'] != 'public') {
				$objects['Class'] .= ' private';
			}
		}

		if (!empty($row['accesskey'])) {
			$objects['Access_Key'] = htmlspecialchars($row['accesskey']);
		}
	
		if (!empty($row['children'])) {
			$objects['Class'] .= ' has_child';
		}
		
		if (!empty($row['css_class'])) {
			$objects['Class'] .= ' '. $row['css_class'];
		}
		
		if (!isset($row['current'])) {
			$row['current'] =
				!empty($row['cType'])
			 && !empty($row['equiv_id'])
			 && $row['cType'] == ze::$cType
			 && $row['equiv_id'] == ze::$equivId;
		}
		if ($row['current']) {
			$objects['Class'] .= ' current';
		}
		
		$width = $height = $url = false;
		if (!empty($row['image_id']) && ze\file::imageLink($width, $height, $url, $row['image_id'])) {
			$menuItemImageLink = '';
			if (!empty($row['url'])) {
				$menuItemImageLink .= '<a';
				$menuItemImageLink .= ' href="'. htmlspecialchars($row['url']). '"';
				if (!empty($row['onclick'])) {
					$menuItemImageLink .= ' onclick="'. htmlspecialchars($row['onclick']). '"';
				}
				if (!empty($row['accesskey'])) {
					$menuItemImageLink .= ' accesskey="'. htmlspecialchars($row['accesskey']). '"';
				}
				if (!empty($row['rel_tag'])) {
					$menuItemImageLink .= ' rel="'. htmlspecialchars($row['rel_tag']). '"';
				}
				if (!empty($row['target'])) {
					$menuItemImageLink .= ' target="'. $row['target']. '"';
				}
				if (!empty($row['title'])) {
					$menuItemImageLink .= ' title="'. $row['title']. '"';
				}
				$menuItemImageLink .= '>';
			}
			
			$width2 = $height2 = $url2 = false;
			$onMouseOver = $onMouseOut = '';
			if (!empty($row['rollover_image_id']) && ze\file::imageLink($width2, $height2, $url2, $row['rollover_image_id'])) {
				
				$onMouseOver = ' onmouseover="{this.src=\''. htmlspecialchars($url2) .'\'};" ';
				if ($row['on']) {
					$url = $url2;
				} else {
					$onMouseOut = ' onmouseout="{this.src=\''. htmlspecialchars($url) .'\'};" ';
				}
			}
			
			
			$menuItemImageLink .= '<img src="'. htmlspecialchars($url). '" alt="'. htmlspecialchars($row['name']). '" class="zenario_menu_image" style="width: '. $width. 'px; height: '. $height. 'px;"';
			$menuItemImageLink .= $onMouseOver;
			$menuItemImageLink .= $onMouseOut;
			$menuItemImageLink .= '/>';
			
			
			if (!empty($row['url'])) {
				$menuItemImageLink .= '</a>';
			}
			$objects['Image'] = $menuItemImageLink;
		}
		
		if (!empty($row['conditionally_hidden'])) {
			$objects['Conditionally_Hidden'] = true;
		} elseif (empty($row['active'])) {
			$objects['Inactive_Open_Tag'] = '<em class="zenario_inactive">';
			$objects['Inactive_Close_Tag'] = '</em>';
		}
		
		//A function that can be overwritten to allow extra functionality
		if ($this->addExtraMenuNodeMergeFields($row, $objects, $i)) {
			
			return $objects;
		}
	}
	
	//A function that can be overwritten to allow extra functionality
	function addExtraMenuNodeMergeFields(&$menuItem, &$mergeFields, $i) {
		return true;
	}
	
	
	//Draw a Menu Node
	function drawMenuItem(&$row) {
		$menuItem = '<a';
		
		if (!empty($row['url'])) {
			$menuItem .= ' href="'. htmlspecialchars($row['url']). '"';
		} else {
			$menuItem .= ' class="unlinked_menu_item"';
		}

		if (!empty($row['onclick'])) {
			$menuItem .= ' onclick="'. htmlspecialchars($row['onclick']). '"';
		}

		if (!empty($row['accesskey'])) {
			$menuItem .= ' accesskey="'. htmlspecialchars($row['accesskey']). '"';
		}

		if (!empty($row['rel_tag'])) {
			$menuItem .= ' rel="'. htmlspecialchars($row['rel_tag']). '"';
		}

		if (!empty($row['target'])) {
			$menuItem .= ' target="'. $row['target']. '"';
		}
		
		if (!empty($row['title'])) {
			$menuItem .= ' title="'. $row['title']. '"';
		}
		
		$menuItem .= '>';
		
		if (!empty($row['name'])) {
			$menuItem .= htmlspecialchars($row['name']);
		
		} elseif (!empty($row['img'])) {
			$menuItem .= $row['img'];
		}

		if (empty($row['url'])) {
			if (!empty($row['document_privacy_error']) || !empty($row['document_file_not_found'])) {
				$menuItem .= ' <span class="link_error">(' . ze\admin::phrase('Link error') . ')</span>';
			}
		}
		
		$menuItem .= '</a>';
		
		return $menuItem;
	}
	
	
	function getHomepage($langId) {
		$cID = $cType = false;
		ze\content::langSpecialPage('zenario_home', $cID, $cType, $langId, true);
		
		return $this->getSpecificPage($cID, $cType);
	}
		
	function getSpecificPage($cID, $cType) {
		$page = ['id' => $cID, 'type' => $cType, 'name' => false];
		
		if ($menu = ze\menu::getFromContentItem($cID, $cType)) {
			
			$cachingRestrictions = 0;
			$rows = ze\menu::getStructure(
				$cachingRestrictions,
				$menu['section_id'],
				$menu['id'],
				$parentMenuId = 0,
				$numLevels = 0,
				$maxLevel1MenuItems = 1,
				$language = false,
				$onlyFollowOnLinks = true,
				$onlyIncludeOnLinks = true,
				$showInvisibleMenuItems = true,
				$showMissingMenuNodes = false,
				$this->requests,
				ze\content::showUntranslatedContentItems()
				);
			
			if (!empty($rows[$menu['id']])) {
				return $rows[$menu['id']];
			}
		}
		
		$page['name'] = ze\content::title($cID, $cType);
		$page['url'] = ze\link::toItem($cID, $cType, false, $this->requests, false, false);
		$page['active'] = true;
		
		return $page;
	}
	
	
	function getMenuAncestor($mID, &$ancestor, &$ancestorButOne) {
		$ancestor = $ancestorButOne = $mID;
		$recurseLimit = 100;
		while ((--$recurseLimit) && ($newMID = ze\menu::parentId($ancestor))) 	{
			$ancestorButOne = $ancestor;
			$ancestor = $newMID;
		}
	}
	
	function getMenuBranch($mID) {
		$rv = [$mID];
		$fuse = 100;
		while ((--$fuse) && ($mID = ze\menu::parentId($mID))) {
			array_push($rv,$mID); 			
		}
		return array_reverse($rv);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case "plugin_settings":
				if (isset($box['tabs']['first_tab']['fields']['specific_menu_node'])) {
					$box['tabs']['first_tab']['fields']['specific_menu_node']['hidden'] = !($values['first_tab/menu_generation_current_or_specific'] == "_SPECIFIC");
					
					if ($values['first_tab/menu_generation_current_or_specific']=="_SPECIFIC") {
						$box['tabs']['first_tab']['fields']['specific_menu_node']['pick_items']['path'] = "zenario__menu/panels/sections/item//" . $values['first_tab/menu_section'] . "//";
						$box['tabs']['first_tab']['fields']['menu_start_from']['hidden'] = true;
					} else {
						$box['tabs']['first_tab']['fields']['menu_start_from']['hidden'] = false;
					}
				}

				if ($box['module_class_name'] == 'zenario_menu') {
					if ($values['first_tab/menu_number_of_levels'] == 1) {
						$values['first_tab/menu_show_all_branches'] = false;
						$fields['first_tab/menu_show_all_branches']['note_below'] = ze\admin::phrase('Only available when the number of levels to display is set to more than 1.');
					} else {
						unset($fields['first_tab/menu_show_all_branches']['note_below']);
					}

					if (ze::in($values['first_tab/menu_number_of_levels'], 1, 2, 3)) {
						unset($fields['first_tab/limit_initial_level_1_menu_nodes_checkbox']['note_below']);
					} else {
						$values['first_tab/limit_initial_level_1_menu_nodes_checkbox'] = false;
						$fields['first_tab/limit_initial_level_1_menu_nodes_checkbox']['note_below'] = ze\admin::phrase('Only available when the number of levels to display is set to 1, 2 or 3.');
					}

					if (!$values['first_tab/text_for_more_button']) {
						$values['first_tab/text_for_more_button'] = 'More';
					}
				}
				
				break;
		}
	}
	
	
	//This method can be called from the Advanced tab of the menu node properties.
	//It looks for requests in the URL/POST, and adds them on to the menu nodes so that they are not lost
	public static function rememberRequests($requests) {
		
		$extra_requests = [];
		
		foreach (ze\ray::explodeAndTrim($requests) as $var) {
			if (isset($_REQUEST[$var])) {
				$extra_requests[$var] = $_REQUEST[$var];
			}
		}
		
		if (!empty($extra_requests)) {
			return ['extra_requests' => $extra_requests];
		} else {
			return true;
		}
	}
	
	public static function currentMenuTitle($sectionId = 'Main') {
		$sectionId = ze\menu::sectionId(($sectionId ?: 'Main'));
		$currentMenuId = ze\menu::getIdFromContentItem(ze::$equivId, ze::$cType, $sectionId);
		return ze\row::get('menu_text', ['name'], ['menu_id' => $currentMenuId, 'language_id' => ze::$langId]);
	}

	public function handlePluginAJAX() {
        if (ze::post('action') == 'toggleOpenClosed') {
            $return = [];

			//Check if the state has already been set before...
            if (!isset($_SESSION['vertical_menu_open_closed_state']) || !ze::in($_SESSION['vertical_menu_open_closed_state'], 'open', 'closed')) {
                $_SESSION['vertical_menu_open_closed_state'] = $this->setting('open_close_initial_state');
            }

            //... and reverse it now.
			if ($_SESSION['vertical_menu_open_closed_state'] == 'open') {
                $return['previous_menu_state'] = 'open';
                $return['current_menu_state'] = 'closed';
                $return['phrase'] = $this->phrase('Open');
            } else {
                $return['previous_menu_state'] = 'closed';
                $return['current_menu_state'] = 'open';
                $return['phrase'] = $this->phrase('Close');
            }

            $_SESSION['vertical_menu_open_closed_state'] = $return['current_menu_state'];

            $return['containerId'] = $this->containerId;

            echo json_encode($return);
        }
	}
	
	//In admin mode, show the name of the menu section that the plugin has,
	//and add a link to Organizer.
	public function fillAdminSlotControls(&$controls) {
		if ($this->sectionId && isset($controls['info']['menu_section'])) {
			
			$text = ze\admin::phrase('"[[section]]" menu section', [
				'section' => ze\menu::sectionName($this->sectionId)
			]);
			$orgLink = ze\link::absolute() . 'organizer.php#'. ze\menuAdm::organizerLink(false, true, $this->sectionId);
			
			$controls['info']['menu_section']['hidden'] = false;
			$controls['info']['menu_section']['label'] = '
				<span
					class="zenario_slotControl_MsInfo"
				>'. htmlspecialchars($text). '</span><a
					href="'. htmlspecialchars($orgLink). '"
					target="_blank"
					onclick="zenarioA.closeSlotControls(); zenario.stop(event);"
					class="zenario_linkToNewTab"
				></a>';
		}
	}
}