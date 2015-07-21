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

class zenario_breadcrumbs extends zenario_menu {
	
	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = true, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$this->sectionId				= $this->setting('menu_section');
		$this->startFrom				= '_MENU_LEVEL_1';
		$this->numLevels				= 999;
		$this->maxLevel1MenuItems		= 999;
		$this->language					= false;
		$this->showAdminAddMenuItem		= false;
		$this->onlyFollowOnLinks		= true;
		$this->onlyIncludeOnLinks		= true;
		$this->showInvisibleMenuItems	= true;
		
		$this->showInMenuMode();
		
		//Get the Menu Node for this content item
		$this->currentMenuId = getSectionMenuItemFromContent(cms_core::$equivId, cms_core::$cType, $this->sectionId, $mustBePrimary = true);
		
		return (bool) $this->currentMenuId;
	}
	
	function showSlot() {
		if ((bool) $this->currentMenuId) {
			zenario_menu::showSlot();
		}
	}
	
	function addExtraMergeFields(&$menuItem, &$mergeFields, $recurseCount, $i, $maxI) {
		$mergeFields['Separator'] = $this->setting('breadcrumb_trail_separator');
		return true;
	}
	
	//Recursive function to draw Menu Nodes from the database
	function drawMenu(&$menuArray, $recurseCount = 0, $headerObjects = array(), $subSections = array()) {
		
		if ($recurseCount == 0) {
			switch ($this->setting('breadcrumb_trail')) {
				case 'other_menu_node':
					$prefixPage = (getContentFromMenu($this->setting('breadcrumb_prefix_menu')));
					$cID = $prefixPage['content_id'];
					$cType = $prefixPage['content_type'];
					langEquivalentItem($cID, $cType);
					$page = $this->getSpecificPage($cID, $cType);
					break;
				
				case 'do_not_prefix':
					$page = false;
					break;
				
				case 'site_home_page':
				default:
					$page = $this->getHomepage($_SESSION['user_lang']);
					break;
			}
			
			if ($page) {
				foreach ($menuArray as &$menu) {
					if (arrayKey($page, 'url') != $menu['url'] && $page['name'] && $page['name'] != $menu['name']) {
						$page['children'] = $menuArray;
						$menuArrayHome = array($page);
						zenario_menu::drawMenu($menuArrayHome, $recurseCount);
						return;
					}
					break;
				}
			}
		}
		
		zenario_menu::drawMenu($menuArray, $recurseCount);
	}
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		// Overwrite zenario_menu fillAdminBox to do nothing
	}
}
