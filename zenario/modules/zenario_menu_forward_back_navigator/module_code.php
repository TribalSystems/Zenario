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

class zenario_menu_forward_back_navigator extends zenario_menu {
	
	protected $data = [];
	
	public function init() {
		
		$isAdmin = !empty($_SESSION['admin_logged_into_site']) && \ze\priv::check();
		
		//Get the section id from the Plugin Settings.
		$sectionId = $this->setting('menu_section');
		
		//Catch the case where someone entered a section name, convert it to an id
		$sectionId = ze\menu::sectionId($sectionId);
		
		//Get the menu id of the current content item
		$currentMenuId = ze\menu::getIdFromContentItem(ze::$equivId, ze::$cType, $sectionId);
		
		//Don't show if there was no current menu node
		if (!$currentMenuId) {
			return false;
		}
		
		//Get the parent id of the current menu node
		$parentMenuId = (int) ze\menu::parentId($currentMenuId);
		
		//N.b. caching not implemented yet
		$cachingRestrictions = 0;
		
		
		//Check if we're showing an "up" link.
		//Admins should see unpublished drafts, non-admins should not.
		if ($this->setting('show_parent')
			//Get details of the menu node above
		 && $parentMenuId
		 && ($result = ze\menu::query(ze::$langId, $parentMenuId, $byParent = false, false, false, false, $isAdmin))
		 && ($row = ze\sql::fetchAssoc($result))
			//Check if it can be seen. Admins see everything, for non-admins it checks if they can.
		 && ($isAdmin || ze\menu::shouldShow($row, $cachingRestrictions, ze::$langId))) {
		
			\ze\menu::format($row);
			//Don't show the button if the target is unlinked
			//N.b. If the target is a linked page but has never been published before, the button also won't be shown.
			if(!empty($row['url'])) {
				$this->data['Up'] = true;
				$this->data['Up_Link'] = $this->drawMenuItem($row);
			}
		}
		
		
		//Check if we're showing a left or right link.
		//Admins should see unpublished drafts, non-admins should not.
		if ($this->setting('show_next') || $this->setting('show_previous')) {
			
			//Get details of all of the menu nodes below
			$prev = false;
			$next = false;
			$hadCurrent = false;
			$result = ze\menu::query(ze::$langId, $parentMenuId, $byParent = true, $sectionId, false, false, $isAdmin);
			while ($row = ze\sql::fetchAssoc($result)) {
			
				//Check if this is the current menu node
				if ($row['mID'] == $currentMenuId) {
					$hadCurrent = true;
				
				//Ignore any menu nodes we can't see
				} elseif (!($isAdmin || ze\menu::shouldShow($row, $cachingRestrictions, ze::$langId))) {
					continue;
					
				//Look out for the left-most one of the current menu node
				} elseif (!$hadCurrent) {
					$prev = $row;
				
				//Look out for the right-most one of the current menu node
				} else {
					$next = $row;
					
					//Stop as soon as we see this
					break;
				}
			}
			
			if ($prev && $this->setting('show_previous')) {
				\ze\menu::format($prev);
				//Don't show the button if the target is unlinked
				//N.b. If the target is a linked page but has never been published before, the button also won't be shown.
				if(!empty($prev['url'])) {
					$this->data['Previous'] = true;
					$this->data['Previous_Link'] = $this->drawMenuItem($prev);
				}
			}
			
			if ($next && $this->setting('show_next')) {
				\ze\menu::format($next);
				//Don't show the button if the target is unlinked
				//N.b. If the target is a linked page but has never been published before, the button also won't be shown.
				if(!empty($next['url'])) {
					$this->data['Next'] = true;
					$this->data['Next_Link'] = $this->drawMenuItem($next);
				}
			}
		}
		
		
		return !empty($this->data);
	}
	
	public function showSlot() {
		
		if (!empty($this->data)) {
			$this->twigFramework($this->data);
		}
	}
	
}