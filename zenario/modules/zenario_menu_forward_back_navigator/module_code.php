<?php
/*
 * Copyright (c) 2016, Tribal Limited
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

class zenario_menu_forward_back_navigator extends zenario_menu {
	
	var $up = false;
	var $back = false;
	var $forward = false;
	
	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = true, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$this->sectionId				= $this->setting('menu_section');
		// If show parent, start from 2 levels up to also get the parent nodes
		$this->startFrom				= $this->setting('show_parent') ? '_MENU_LEVEL_TWO_ABOVE' : '_MENU_LEVEL_ABOVE';
		$this->numLevels				= 2;
		$this->maxLevel1MenuItems		= 999;
		$this->language					= false;
		$this->showAdminAddMenuItem		= false;
		$this->onlyFollowOnLinks		= true;
		$this->onlyIncludeOnLinks		= false;
		$this->showInvisibleMenuItems	= true;
		
		$this->showInMenuMode();
		
		//Get the menu node for this content item
		$this->currentMenuId = getSectionMenuItemFromContent(cms_core::$equivId, cms_core::$cType, $this->sectionId);
		
		//Have an option to hide the forward/back nav on top level nodes
		if ($this->setting('min_level')) {
			if ((int) $this->setting('min_level') > 1) {
				$sql = "
					SELECT 1
					FROM ". DB_NAME_PREFIX. "menu_nodes AS m
					INNER JOIN ". DB_NAME_PREFIX. "menu_hierarchy AS mh
					   ON mh.ancestor_id = m.id
					  AND mh.child_id = ". (int) $this->currentMenuId. "
					  AND mh.separation < ". (int) $this->setting('min_level'). "
					WHERE m.parent_id = 0
					LIMIT 1";
				$result = sqlQuery($sql);
				$show = !sqlFetchRow($result);
			} else {
				$show = getRow('menu_nodes', 'parent_id', $this->currentMenuId);
			}
			
			if (!$show) {
				$this->currentMenuId = false;
			}
		}
		
		return (bool) $this->currentMenuId;
	}

	function drawMenuItem(&$row) {
		$menuItem = '';
		
		if (isset($row['name']) && $row['name']) {
			$menuItem = htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
		} elseif (isset($row['img']) && $row['img']) {
			$menuItem = $row['img'];
		}
		
		$title = false;
		if ($this->setting('show_page_link_as_title')) {
			$title = getItemTitle($row['cID'], $row['cType']);
		}
		
		$menuItem =
			'<a href="'. $row['url']. '"'.
				(isset($row['onclick']) && $row['onclick']?
				  ' onclick="'. $row['onclick']. '"' : '').
				(isset($row['accesskey']) && $row['accesskey']?
				  ' accesskey="'. $row['accesskey']. '"' : '').
				(!$row['url']?
				  ' class="unlinked_menu_item"' : '').
				(isset($row['rel_tag']) && $row['rel_tag']?
				  ' rel="'. $row['rel_tag']. '"' : '').
				(isset($row['target']) && $row['target']?
				  ' target="'. $row['target']. '"' : '').
				($title && $title?
				  ' title="'. $title . '"' : '').
			'>'. $menuItem. '</a>';
		
		if (!isset($row['active']) || !$row['active']) {
			$menuItem = "<em>". $menuItem. "</em>";
		}
		
		return $menuItem;
	}

	
	
	//Recursive function to draw menu nodes from the database
	function drawMenu(&$menuArray, $recurseCount = 0, $headerObjects = array(), $subSections = array()) {
		
		if ($recurseCount > 10) {
			return;
		}
		
		$last = false;
		$hadCurrentMenuItem = false;
		
		foreach ($menuArray as $row) {
			
			if ($row['mID'] == $this->currentMenuId) {
				$this->back = $last;
				$hadCurrentMenuItem = true;
			} elseif ($row['children'] && is_array($row['children'])) {
				$this->up = $row;
				$this->drawMenu($row['children'], $recurseCount + 1);
				break;
			} elseif ($hadCurrentMenuItem) {
				$this->forward = $row;
				break;
			} else {
				$last = $row;
			}
		}
		
		if ($recurseCount == 0) {
			$mergeFields = array();
			$allowedSubSections = array();
			
			if ($this->back && $this->setting('show_previous')) {
				$allowedSubSections['Previous'] = true;
				$mergeFields['Previous_Link'] = $this->drawMenuItem($this->back);
			}
			
			if ($this->up && $this->setting('show_parent')) {
				$allowedSubSections['Up'] = true;
				$mergeFields['Up_Link'] = $this->drawMenuItem($this->up);
			}
			
			if ($this->forward && $this->setting('show_next')) {
				$allowedSubSections['Next'] = true;
				$mergeFields['Next_Link'] = $this->drawMenuItem($this->forward);
			}
			
			$this->framework('Outer', $mergeFields, $allowedSubSections);
		}
	}
}


