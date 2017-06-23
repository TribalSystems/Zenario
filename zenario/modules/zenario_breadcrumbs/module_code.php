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
	
	public function showLayoutPreview() {
		
		$dummyMenuNode = array(
			'mID' => '',
			'name' => '',
			'target_loc' => 'ext',
			'open_in_new_window' => 0,
			'anchor' => '',
			'module_class_name' => '',
			'method_name' => '',
			'param_1' => '',
			'param_2' => '',
			'equiv_id' => '',
			'cID' => '',
			'cType' => '',
			'alias' => '',
			'use_download_page' => '',
			'hide_private_item' => '',
			'url' => absCMSDirURL(),
			'visitor_version' => '',
			'invisible' => '',
			'accesskey' => '',
			'ordinal' => '',
			'rel_tag' => '',
			'image_id' => '',
			'rollover_image_id' => '',
			'on' => true,
			'children' => array());
		
		$menuArray = array($dummyMenuNode);
		$menuArray[0]['name'] = adminPhrase('Bread');
		$menuArray[0]['children'] = array($dummyMenuNode);
		$menuArray[0]['children'][0]['name'] = adminPhrase('Crumbs');
		
		$this->drawMenu($menuArray, $recurseCount = 0, $headerObjects = array(), $subSections = array());
	}

	
	function addExtraMergeFields(&$menuItem, &$mergeFields, $recurseCount, $i, $maxI) {
		$mergeFields['Separator'] = $this->setting('breadcrumb_trail_separator');
		return true;
	}
	
	//Recursive function to draw Menu Nodes from the database
	function drawMenu(&$menuArray, $recurseCount = 0, $headerObjects = array(), $subSections = array()) {
		
		if ($recurseCount == 0) {
			
			//Have an option to add the conductor's slides on to the end of the breadcrumb trail,
			//if this plugin is in a nest, the plugin setting is enabled, and there are some back-links
			if (isset($this->parentNest)
			 && $this->setting('add_conductor_slides')
			 && !empty($menuArray)
			 && ($backs = $this->parentNest->getBackLinks())
			 && (!empty($backs))) {
				
				//Create a reference to the last child in the breadcrumb trail.
				//This is a bit complicated due to the recursive nature of the data; we need to
				//keep drilling down into the child arrays until we get to the bottom node.
				$lastNode = false;
				foreach ($menuArray as &$menu) {
					$lastNode = &$menu;
					$lastNode['current'] = false;
					break;
				}
				unset($menu);
				while (!empty($lastNode['children']) && is_array($lastNode['children'])) {
					foreach($lastNode['children'] as &$child) {
						$lastNode = &$child;
						$lastNode['current'] = false;
					}
				}
				unset($child);
				
				
				//Loop through each back link
				$first = true;
				foreach ($backs as $state => $details) {
					
					$name = $this->parentNest->formatTitleText($this->phrase($details['slide']['name_or_title']));
					$url = linkToItem(
						cms_core::$cID, cms_core::$cType, false, $details['requests'], cms_core::$alias,
						$autoAddImportantRequests = false
					);
					
					//For the first conductor link, override the lasty breadcrumb
					if ($first) {
						$first = false;
						//$lastNode['name'] = $name;
						$lastNode['url'] = $url;
					
					} else {
						//For all subsequent  links, create a new breadcrumb as a copy of the previous one
						$child = $lastNode;
					
						//Change the name and link
						$child['open_in_new_window'] = false;
						$child['name'] = $name;
						$child['url'] = $url;
					
						//Set the new breadcrumb as a child of the previous one
						$lastNode['children'] = [$child];
						unset($child);
					
						//Update the pointer to point to the thing we just made
						$lastNode = &$lastNode['children'][0];
					}
				}
				$lastNode['current'] = true;
			}
			
			
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
					$page = $this->getHomepage(cms_core::$langId);
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
