<?php
/*
 * Copyright (c) 2020, Tribal Limited
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
		$this->currentMenuId = ze\menu::getIdFromContentItem(ze::$equivId, ze::$cType, $this->sectionId, $mustBePrimary = true);
		
		return (bool) $this->currentMenuId;
	}
	
	function showSlot() {
		if ((bool) $this->currentMenuId) {
			zenario_menu::showSlot();
		}
	}
	
	public function shouldShowLayoutPreview() {
		//return false;
		return true;
	}
	
	public function showLayoutPreview() {
		
		$dummyMenuNode = [
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
			'url' => ze\link::absolute(),
			'visitor_version' => '',
			'invisible' => '',
			'accesskey' => '',
			'ordinal' => '',
			'rel_tag' => '',
			'image_id' => '',
			'rollover_image_id' => '',
			'on' => true,
			'children' => []];
		
		$menuArray = [$dummyMenuNode];
		$menuArray[0]['name'] = ze\admin::phrase('Bread');
		$menuArray[0]['children'] = [$dummyMenuNode];
		$menuArray[0]['children'][0]['name'] = ze\admin::phrase('Crumbs');
		
		$mergeFields = [
			'nodes' => $this->getMenuMergeFields($menuArray)
		];
		
		$this->twigFramework($mergeFields);
	}
	
	//Recursive function to draw Menu Nodes from the database
	function getMenuMergeFields(&$menuArray, $depth = 1) {
		
		
		//Convert from a nested arrays to a flat array
		$ni = -1;
		$nodes = [];
		$loopThrough = $menuArray;
		while (!empty($loopThrough)) {
			foreach ($loopThrough as &$menu) {
				
				if (empty($menu['children'])) {
					$loopThrough = [];
				} else {
					$loopThrough = $menu['children'];
					unset($menu['children']);
				}
				
				$nodes[++$ni] = $menu;
				break;
			}
		}
		unset($loopThrough);
		
		
		//Auto-add the home page to the start, depending on the plugin settings
		switch ($this->setting('breadcrumb_trail')) {
			case 'other_menu_node':
				$position = explode('_', $this->setting('breadcrumb_prefix_menu'));
				$menuNodeId = $position[1] ?? 0;
				$prefixPage = ze\menu::getContentItem($menuNodeId);
				$cID = $prefixPage['content_id'];
				$cType = $prefixPage['content_type'];
				ze\content::langEquivalentItem($cID, $cType);
				$page = $this->getSpecificPage($cID, $cType);
				break;
			
			case 'do_not_prefix':
				$page = false;
				break;
			
			case 'site_home_page':
			default:
				$page = $this->getHomepage(ze::$visLang);
				break;
		}
		
		//Only auto-add the home page if it's not the same as the first node
		if ($page && !empty($page['name'])) {
			if (!isset($nodes[0])
			 || ($nodes[0]['name'] != $page['name']
			  && $nodes[0]['url'] != ($page['url'] ?? ''))
			) {
				array_unshift($nodes, $page);
				++$ni;
			}
		}
		
		
		//Have an option to add the conductor's slides on to the end of the breadcrumb trail,
		//if this plugin is in a nest, the plugin setting is enabled, and there are some back-links
		if (isset($this->parentNest)
		 && $this->setting('add_conductor_slides')
		 && !empty($nodes)
		 && ($backs = $this->parentNest->getBackLinks())
		 && (!empty($backs))) {
			
			//Loop through each back link
			$copy = $nodes[$ni];
			$first = true;
			$nextName = null;
			$ci = 0;
			foreach ($backs as $state => $back) {
				
				//Prefer to use the name from the previous smart-breadcrumbs if we can,
				//but otherwise use the name from the slide.
				if ($nextName !== null) {
					$name = $nextName;
				} else {
					$name = $this->parentNest->formatTitleText($back['slide']['name_or_title']);
				}
				
				//Check if any smart breadcrumbs have been defined
				$smart = [];
				$nextName = null;
				if (!empty($back['smart'])) {
					foreach ($back['smart'] as $vbc) {
						$copy['name'] = $vbc['name'];
						$copy['current'] = $vbc['current'];
						$copy['css_class'] = $vbc['css_class'] ?? '';
						$copy['open_in_new_window'] = false;
						$copy['url'] = ze\link::toItem(
							ze::$cID, ze::$cType, false, $vbc['request'], ze::$alias,
							$autoAddImportantRequests = false
						);
						$smart[] = $copy;
						
						//Remember the name of the current smart breadrcumb for the next loop
						if ($vbc['current']) {
							$nextName = $vbc['name'];
						}
					}
				}
				
				$url = ze\link::toItem(
					ze::$cID, ze::$cType, false, $back['requests'], ze::$alias,
					$autoAddImportantRequests = false
				);
				
				//For the first conductor link, override the last breadcrumb rather than adding a second identical breadcrumb.
				if ($first) {
					$first = false;
					$nodes[$ni]['url'] = $url;
				
				} else {
					//Remove the "current" highlight from any previous links.
					for (; $ci <= $ni; ++$ci) {
						$nodes[$ci]['current'] = false;
					}
				
					//For all subsequent  links, create a new breadcrumb as a copy of the previous one,
					//with the name and link changed.
					$copy['open_in_new_window'] = false;
					$copy['name'] = $name;
					$copy['url'] = $url;
					$copy['current'] = true;
					
					$nodes[++$ni] = $copy;
				}
				
				$nodes[$ni]['smart'] = $smart;
			}
		}
		
		$mergeFields = [];
		foreach ($nodes as $ni => $node) {
			$mrg = $this->getMenuNodeMergeFields($ni + 1, 1, $node);
			
			if (!empty($node['smart'])) {
				$mrg['Smart'] = [];
				foreach ($node['smart'] as $vi => $vbc) {
					$mrg['Smart'][] = $this->getMenuNodeMergeFields($ni + 1, $vi + 1, $vbc);
				}
			}
			
			$mergeFields[] = $mrg;
		}
		
		return $mergeFields;
	}
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		// Overwrite zenario_menu fillAdminBox to do nothing
	}
}
