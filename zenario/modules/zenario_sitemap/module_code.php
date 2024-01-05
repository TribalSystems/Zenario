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

//This Plugin is used to create a Sitemap

//Rather than start from the ground up, the Sitemap Plugin uses code from an existing Plugin called zenario_menu: Note that the
//showSlot method is not included at all, as the version in the Menu Plugin already does everything the Sitemap Plugin needs.

//However the Sitemap Plugin does have its own version of the init method, which it uses to set up sitemap-specific settings
//to change the way that the showSlot method will behave
class zenario_sitemap extends zenario_menu {
	
	//The init method is called by the CMS lets Plugin Developers run code before the Plugin and the page it is on are displayed.
	//In visitor mode, the Plugin is only displayed if this method returns true.
	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = true, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		//The only setting used is which Menu Section to display
		//(This will usually be set to "main")
		$this->sectionId				= $this->setting('menu_section');
		
		//Start from the top of the menu tree, and have no real limit on the number of menu items.
		//However, as of 02/08/2021, there should be a limit on the depth of menu items.
		$this->startFrom				= '_MENU_LEVEL_1';
		$this->numLevels				= (int) $this->setting('menu_number_of_levels');
		$this->maxLevel1MenuItems		= 999;
		
		//Auto-detect the visitors current language
		$this->language					= false;
		
		//Show every branch of the menu tree
		$this->onlyFollowOnLinks		= false;
		$this->onlyIncludeOnLinks		= false;
		$this->showInvisibleMenuItems	= $this->setting('show_invisible_menu_items');
		
		//Get the menu item for this content item
		$this->currentMenuId = ze\menu::getIdFromContentItem(ze::$equivId, ze::$cType, $this->sectionId);
		
		//Always display this Plugin
		return true;
	}
}