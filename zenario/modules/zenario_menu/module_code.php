<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

class zenario_menu extends module_base_class {

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
	
	protected $headerObjects = array();
	protected $subSections = array();
	
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
		
		$this->sectionId = menuSectionId($this->sectionId);
		
		$this->startFrom				= $this->setting('menu_start_from');
		$this->numLevels				= $this->setting('menu_number_of_levels');
		$this->maxLevel1MenuItems		= 999;
		$this->language					= false;
		$this->onlyFollowOnLinks		= !$this->setting('menu_show_all_branches');
		$this->onlyIncludeOnLinks		= false;
		$this->showInvisibleMenuItems	= false;
		$this->showMissingMenuNodes		= $this->setting('show_missing_menu_nodes');
		
		$this->showInMenuMode();
		
		if(setting('zenario_menu__allow_overriding_of_invisible_flag_on_menu_nodes') && $this->setting('show_invisible_menu_nodes')){
			$this->showInvisibleMenuItems=true;
		}
		
		$this->setting('show_missing_menu_nodes');
		
		//Get the Menu Node for this content item
		$this->currentMenuId = getSectionMenuItemFromContent(cms_core::$equivId, cms_core::$cType, $this->sectionId);
		
		return $this->currentMenuId || !$this->setting('hide_if_current_item_not_in_menu');
	}
	
	//Main Display function for the slot
	function showSlot() {
		
		if (!$this->currentMenuId && $this->setting('hide_if_current_item_not_in_menu')) {
			return;
		}
		
		//Load Settings
		//Work out where to start from
		$parentMenuId = $this->getStartNode();
		
		$this->subSections['Title'] = true;
		$this->subSections['User_Names'] = true;
		
		//Set default values for this->maxLevel1MenuItems and the this->language
		if (!$this->maxLevel1MenuItems) {
			$this->maxLevel1MenuItems = 9;
		}
		
		if ($this->language === false) {
			$this->language = cms_core::$langId;
		}
		
		$userDetails = false;
		
		// Display a users first and last name, only used on vertical menus.
		if ($this->checkFrameworkSectionExists('User_Names')) {
			if ($id = userId()) {
				$userDetails = getUserDetails($id);
				$this->headerObjects['first_name'] = $userDetails['first_name'];
				$this->headerObjects['last_name'] = $userDetails['last_name'];
			}
		}
		
		$welcome_message_text = $this->setting('welcome_message');
		
		$this->headerObjects['welcome_message'] = 
			$this->phrase($welcome_message_text, 
				array('first_name' => htmlspecialchars($userDetails['first_name']), 
					'last_name' => htmlspecialchars($userDetails['last_name'])));
		
		// Display a title, usually only used for vertical side menus
		if ($this->checkFrameworkSectionExists('Title')) {
			if ($parentMenuId && $parentMenuDetails = getMenuInLanguage($parentMenuId, $this->language)) {
				$this->headerObjects['Parent_Name'] = htmlspecialchars($parentMenuDetails['name']);
				if ($parentMenuDetails['cID']) {
					$this->headerObjects['Parent_Link'] = htmlspecialchars(
						$this->linkToItem($parentMenuDetails['cID'], $parentMenuDetails['cType'], false, $this->requests, $parentMenuDetails['alias'])
					);
				}
			} else {
				$homepage = $this->getHomepage($_SESSION['user_lang']);
				$this->headerObjects['Parent_Name'] = htmlspecialchars($homepage['name']);
				$this->headerObjects['Parent_Link'] = htmlspecialchars($homepage['url']);
			}
		}
		
		//Get the menu structure from the database.
		$cachingRestrictions = false;
		$menuArray =
			getMenuStructure($cachingRestrictions,
							 $this->sectionId, $this->currentMenuId, $parentMenuId,
							 $this->numLevels, $this->maxLevel1MenuItems, $this->language,
							 $this->onlyFollowOnLinks, $this->onlyIncludeOnLinks, 
							 $this->showInvisibleMenuItems,
							 $this->showMissingMenuNodes,$recurseCount = 0,$this->requests);
							 
		switch ($cachingRestrictions) {
			case 'privateItemsExist':
				$this->allowCaching(
					$atAll = true, $ifUserLoggedIn = false, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
				break;
			case 'staticFunctionCalled':
				$this->allowCaching(false);
				break;
		}
		
		//Draw the Menu Nodes we found
		$this->drawMenu($menuArray, 0, $this->headerObjects, $this->subSections);
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
			$parentMenuId = getMenuParent($currentMenuId);
			
		} elseif ($this->startFrom == '_MENU_LEVEL_TWO_ABOVE') {
			$parentMenuId = getMenuParent(getMenuParent($currentMenuId));
			
		} elseif ($this->startFrom == 'Menu Level 2' || $this->startFrom == '_MENU_LEVEL_2') {
			$this->getMenuAncestor($currentMenuId, $ancestor, $ancestorButOne);
			$parentMenuId = $ancestor;
			
		} elseif ($this->startFrom == 'Menu Level 3' || $this->startFrom == '_MENU_LEVEL_3') {
			$this->getMenuAncestor($currentMenuId, $ancestor, $ancestorButOne);
			$parentMenuId = $ancestorButOne;
			
		} elseif ($this->startFrom == 'Menu Level 4' || $this->startFrom == '_MENU_LEVEL_4') {
			$branch = $this->getMenuBranch($currentMenuId);
			$parentMenuId = arrayKey($branch,2)?arrayKey($branch,2):$currentMenuId;
			
		} elseif ($this->startFrom == 'Menu Level 5' || $this->startFrom == '_MENU_LEVEL_5') {
			$branch = $this->getMenuBranch($currentMenuId);
			$parentMenuId = arrayKey($branch,3)?arrayKey($branch,3):$currentMenuId;
			
		} 

		return $parentMenuId;
	}
	
	//Recursive function to draw Menu Nodes from the database
	function drawMenu(&$menuArray, $recurseCount = 0, $headerObjects = array(), $subSections = array()) {
		
		if ($this->setting('reverse_order')) {
			$menuArray = array_reverse($menuArray, true);
		}
		
		if (!$this->checkFrameworkSectionExists($theme = 'Level_'. ++$recurseCount)) {
			$theme = 'Level_X';
		}
		
		if (checkPriv() && is_array($menuArray) && count($menuArray) == 1) {
			foreach ($menuArray as $i => &$row) {
				if (checkPriv() && !empty($row['is_admin_add_menu_item'])) {
					$headerObjects['Admin_Button_Class'] = ' class="pluginAdminMenuButton"';
				}
			}
		}
		
		$this->frameworkHead($theme, true, $headerObjects, $subSections);
			
		if (is_array($menuArray)) {
			$i = 0;
			$maxI = count($menuArray);
			foreach ($menuArray as &$row) {
				$this->drawMenuLink(++$i, $row, $recurseCount, $maxI);
			}
		}
			
		$this->frameworkFoot($theme, true, $headerObjects, $subSections);
	
		if ($recurseCount>1000) {
			echo "Function aborted due to infinite recursion loop";
			exit;
		}
	}
	
	//Draw the wrapping around a Menu Node, then draw the Menu Node,
	//then call drawMenu() on its children
	function drawMenuLink($i, &$row, $recurseCount, $maxI) {
		
		if ((!$this->checkFrameworkSectionExists($theme = 'Level_'. $recurseCount. '_Link_'. $i))
		 && (!$this->checkFrameworkSectionExists($theme = 'Level_'. $recurseCount. '_Link'))) {
				$theme = 'Level_X_Link';
		}
		
		$on = !empty($row['on'])? '_on' : '';
		
		$objects = array();
		$objects['Class'] = 'level'. $recurseCount. $on. ' level'. $recurseCount. '_'. $i. $on;
		$objects['Hyperlink'] = $this->drawMenuItem($row);
		
		if (!empty($row['name'])) {
			$objects['Name'] = htmlspecialchars($row['name']);
		}
		if (!empty($row['id'])) {
			$objects['id'] = $row['id'];
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
		
		if (arrayKey($row, 'equiv_id') == cms_core::$equivId && arrayKey($row, 'cType') == $this->cType) {
			$objects['Class'] .= ' current';
		}
		
		if (checkPriv() && !empty($row['is_admin_add_menu_item'])) {
			$objects['Class'] .= ' pluginAdminMenuButton';
		}
		
		
		$sections = array();
		if (!empty($row['descriptive_text'])) {
			if ($this->checkFrameworkSectionExists('Sub_Text')) {
				$sections['Sub_Text'] = array('Sub_Text' => htmlspecialchars($row['descriptive_text']));
			}
		}
		
		$width = $height = $url = false;
		if (!empty($row['image_id']) && imageLink($width, $height, $url, $row['image_id'])) {
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
			if (!empty($row['rollover_image_id']) && imageLink($width2, $height2, $url2, $row['rollover_image_id'])) {
				
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
		
		//A function that can be overwritten to allow extra functionality
		if ($this->addExtraMergeFields($row, $objects, $recurseCount, $i, $maxI)) {
		
			$this->frameworkHead($theme, true, $objects, $sections);
			
			if (!empty($row['children']) && is_array($row['children'])) {
				$this->drawMenu($row['children'], $recurseCount);
			}
			
			$this->frameworkFoot($theme, true, $objects, $sections);
		}
	}
	
	//A function that can be overwritten to allow extra functionality
	function addExtraMergeFields(&$menuItem, &$mergeFields, $recurseCount, $i, $maxI) {
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
		
		$menuItem .= '</a>';
		
		if (!isset($row['active']) || !$row['active']) {
			$menuItem = '<em class="zenario_inactive">'. $menuItem. '</em>';
		}
		
		return $menuItem;
	}
	
	
	function getHomepage($langId) {
		$cID = $cType = false;
		langSpecialPage('zenario_home', $cID, $cType, $langId, true);
		
		return $this->getSpecificPage($cID, $cType);
	}
		
	function getSpecificPage($cID, $cType) {
		$page = array('id' => $cID, 'type' => $cType, 'name' => false);
		
		if ($menu = getMenuItemFromContent($cID, $cType)) {
			
			$cachingRestrictions = false;
			$rows = getMenuStructure(
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
				$recurseCount = 0,
				$this->requests
				);
			
			if (!empty($rows[$menu['id']])) {
				return $rows[$menu['id']];
			}
		}
		
		$page['name'] = getItemTitle($cID, $cType);
		$page['url'] = linkToItem($cID, $cType, false, $this->requests, false, false);
		$page['active'] = true;
		
		return $page;
	}
	
	
	function getMenuAncestor($mID, &$ancestor, &$ancestorButOne) {
		$ancestor = $ancestorButOne = $mID;
		$recurseLimit = 100;
		while ((--$recurseLimit) && ($newMID = getMenuParent($ancestor))) 	{
			$ancestorButOne = $ancestor;
			$ancestor = $newMID;
		}
	}
	
	function getMenuBranch($mID) {
		$rv = array($mID);
		$fuse = 100;
		while ((--$fuse) && ($mID = getMenuParent($mID))) {
			array_push($rv,$mID); 			
		}
		return array_reverse($rv);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case "plugin_settings":
				if (isset($box['tabs']['first_tab']['fields']['specific_menu_node'])) {
					$box['tabs']['first_tab']['fields']['specific_menu_node']['hidden'] = !($values['first_tab/menu_generation_current_or_specific']=="_SPECIFIC");
					
					if ($values['first_tab/menu_generation_current_or_specific']=="_SPECIFIC") {
						$box['tabs']['first_tab']['fields']['specific_menu_node']['pick_items']['path'] = "zenario__menu/panels/sections/item//" . $values['first_tab/menu_section'] . "//";
						$box['tabs']['first_tab']['fields']['menu_start_from']['hidden'] = true;
					} else {
						$box['tabs']['first_tab']['fields']['menu_start_from']['hidden'] = false;
					}
				}
				
				break;
		}
	}
	
	
	//This method can be called from the Advanced tab of the menu node properties.
	//It looks for requests in the URL/POST, and adds them on to the menu nodes so that they are not lost
	public static function rememberRequests($requests) {
		
		$extra_requests = array();
		
		foreach (explodeAndTrim($requests) as $var) {
			if (isset($_REQUEST[$var])) {
				$extra_requests[$var] = $_REQUEST[$var];
			}
		}
		
		if (!empty($extra_requests)) {
			return array('extra_requests' => $extra_requests);
		} else {
			return true;
		}
	}
}