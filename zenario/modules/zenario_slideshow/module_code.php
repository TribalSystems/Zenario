<?php
/*
 * Copyright (c) 2023, Tribal Limited
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

class zenario_slideshow extends zenario_plugin_nest {
	
	protected $aLib;
	
	public function init() {
		if (ze::$isTwig) return;
		
		
		//Flag that this plugin is actually a slideshow
		ze::$slotContents[$this->slotName]->flagAsNest();
		ze::$slotContents[$this->slotName]->flagAsSlideshow();
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		
		//Check if an animation library has been selected in the plugin settings (and it's actually a valid value).
		$this->aLib = $this->setting('animation_library');
		switch ($this->aLib) {
			case 'cycle2':
			case 'roundabout':
			case 'swiper':
				//Create a subclass for the library, so we can use different logic depending on which one was selected
				if ($this->subClass = $this->runSubClass('zenario_slideshow', 'animation_libraries', $this->aLib)) {
					//Pass control to the subclass
					return $this->subClass->initAnimationLibrarySubClass();
				}
		}
		
		return false;
	}
	
	public function initAnimationLibrarySubClass() {
		
		//When a Nest is first inserted, it will be empty.
		//If the Nest is empty, call the resyncNest function just in case being empty is not a valid state.
		if (ze\priv::check() && !ze\row::exists('nested_plugins', ['instance_id' => $this->instanceId])) {
			self::resyncNest($this->instanceId);
		}
		
		$this->loadTabs();
		
		//Don't show anything if not slides have been created
		if (empty($this->slides)) {
			return false;
		}
		
		foreach ($this->slides as &$slide) {
			if ($this->loadSlide($slide['slide_num'])) {
				$this->show = true;
				$this->slideNum = $slide['slide_num'];
			}
		}
		
		if (!$this->show) {
			return false;
		}
		
		$this->showInFloatingBox(false);
		
		return $this->initAnimationLibrary();
	}
	
	public function showSlot() {
		if (ze::$isTwig) return;
			
		
		if ($this->subClass) {
			return $this->subClass->showSlot();
		}
		return false;
	}
	
	
	
	public function fillAdminSlotControls(&$controls) {
		zenario_plugin_nest::fillAdminSlotControls($controls);
	}






	public function returnWhatThisEggIs() {
		return \ze\admin::phrase('This is a plugin in a slideshow');
	}
	
	public function returnWhatThisIs() {
		if (isset($this->parentNest)) {
			return $this->parentNest->returnWhatThisEggIs();
		
		//Don't show a description for the slideshow if there are already plugins in it
		} elseif (!empty($this->modules[$this->slideNum])) {
			return '';
		
		} elseif ($this->slotLevel == 2) {
			return \ze\admin::phrase('This is a slideshow on the layout');
		
		} else {
			return \ze\admin::phrase('This is a slideshow on the content item');
		}
	}
}
