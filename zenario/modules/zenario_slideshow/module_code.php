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

class zenario_slideshow extends zenario_plugin_nest {
	
	var $lastTabNum = 0;
	
	public function init() {
		//Flag that this plugin is actually a nest
		cms_core::$slotContents[$this->slotName]['is_nest'] = true;
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		//Revert to normal nest behaviour when showing one specific Egg for the showFloatingBox/showRSS methods
		if ($this->specificEgg()) {
			return zenario_plugin_nest::init();
		
		//When a Nest is first inserted, it will be empty.
		//If the Nest is empty, call the resyncNest function just in case being empty is not a valid state.
		} elseif (checkPriv() && !checkRowExists('nested_plugins', array('instance_id' => $this->instanceId))) {
			call_user_func(array($this->moduleClassName, 'resyncNest'), $this->instanceId);
		}
		
		
		$this->loadTabs();
		
		if (empty($this->tabs)) {
			$this->tabs = array(1 => array('id' => 0, 'tab' => 1, 'name_or_title' => ''));
		}
		
		foreach ($this->tabs as &$tab) {
			if ($this->loadTab($tab['tab'])) {
				$this->show = true;
				$this->tabNum =
				$this->lastTabNum = $tab['tab'];
			}
		}
		
		
		$firstTabNum = $this->editingTabNum? $this->editingTabNum : false;
		
		$tabOrd = 0;
		foreach ($this->tabs as &$tab) {
			++$tabOrd;
			
			if (($this->checkFrameworkSectionExists($section = 'Tab_'. $tab['tab']))
			 || ($section = 'Tab')) {
				
				$link = $this->tabLink($tabOrd);
				
				if (!isset($this->sections[$section])) {
					$this->sections[$section] = array();
				}
				
				$this->sections[$section][$tab['tab']] = array(
					'TAB_ORDINAL' => $tabOrd,
					'Class' => 'tab_'. $tabOrd. ' tab',
					'Tab_Link' => $link,
					'Tab_Name' => $this->formatTitleText($tab['name_or_title'], true)
				);
			}
			
			if (!$firstTabNum) {
				$firstTabNum = $tab['tab'];
			}
		}
		
		if ((isset($this->sections[$section = 'Tab'][$firstTabNum]['Class']))
		 || (isset($this->sections[$section = 'Tab_'. $firstTabNum][$firstTabNum]['Class']))) {
			$this->sections[$section][$firstTabNum]['Class'] .= '_on';
		}
		
		
		$this->setPrevNextLinks();
		$this->startSlideshow();
		
		$this->showInFloatingBox(false);
		
		if (!$this->isVersionControlled && checkPriv() && $this->setting('author_advice')) {
			$this->showInEditMode();
		}
		
		return $this->show;
	}
	
	protected function tabLink($tabOrd) {
		$link = 'href="#"';
		
		if ($this->setting('use_tab_clicks')) {
			$link .= ' onclick="return zenario_slideshow.page(this, '. ($tabOrd-1). ');"';
		} else {
			$link .= ' onclick="return false;"';
		}
		
		if ($this->setting('use_tab_hover')) {
			$link .= ' onmouseover="zenario_slideshow.page(this, '. ($tabOrd-1). ', true);"';
			
			if ($this->setting('use_timeout')) {
				$link .= ' onmouseout="zenario_slideshow.resume(this);"';
			}
		}
		
		return $link;
	}
	
	protected function setPrevNextLinks() {
		$this->mergeFields['Next_Link'] = 'href="#" onclick="return zenario_slideshow.next(this);"';
		$this->mergeFields['Next_Disabled'] = '';
		$this->mergeFields['Prev_Link'] = 'href="#" onclick="return zenario_slideshow.prev(this);"';
		$this->mergeFields['Prev_Disabled'] = '';
	}
	
	protected function startSlideshow() {
		
		if ($mode = $this->setting('mode')) {
			
			$opt = array(
				'timeout' => $this->setting('use_timeout')? (int) $this->setting('timeout') : 0,
				'pause' => $this->setting('use_timeout')? (int) $this->setting('pause') : 0,
				'next_prev_buttons_loop' => (bool) $this->setting('next_prev_buttons_loop'),
				$this->editingTabNum !== false? $this->editingTabNum - 1 : 0
			);
			
			switch ($mode) {
				case 'cycle':
					$opt['fx'] = $this->setting('fx');
					$opt['sync'] = (bool) $this->setting('sync');
					$opt['speed'] = (int) $this->setting('speed');
					break;
				
				case 'roundabout':
					$opt['shape'] = $this->setting('shape');
					$opt['tilt'] = (float) $this->setting('tilt');
					$opt['speed'] = (int) $this->setting('roundabout_speed');
					break;
			}
				
				
			$this->callScript('zenario_slideshow', 'show',
				'zenario_'. $mode. '_interface',
				$this->containerId,
				$opt,
				$this->editingTabNum !== false? $this->editingTabNum - 1 : 0
			);
		}
	}

	
	public function showSlot() {
		
		//Show a single plugin in the nest
		if ($this->checkShowInFloatingBoxVar()) {
			if ($this->show) {
				
				$ord = 0;
				foreach ($this->modules[$this->tabNum] as $id => $slotNameNestId) {
					$this->mergeFields['PLUGIN_ORDINAL'] = ++$ord;
					
					if (!empty(cms_core::$slotContents[$slotNameNestId]['class'])) {
						if (cms_core::$slotContents[$slotNameNestId]['class']->checkShowInFloatingBoxVar()) {
							$this->showPlugin($slotNameNestId);
						}
					}
				}
			}
		
		//Show all of the plugins on this tab
		} elseif ($this->zAPIFrameworkIsTwig) {
			
			$this->mergeFields['Tabs'] = $this->sections['Tab'];
			
			if ($this->show) {
				$hide = false;
				foreach ($this->tabs as &$tab) {
					$tabNum = $tab['tab'];
					$this->mergeFields['Tabs'][$tabNum]['Plugins'] = $this->modules[$tabNum];
					$this->mergeFields['Tabs'][$tabNum]['Hidden'] = $hide;
					
					if ($mode = $this->setting('mode')) {
						//Hide the slides after slide one, until the jQuery slideshow Plugin kicks in and overrides this.
						$hide = true;
					}
				}
			}
			$this->twigFramework($this->mergeFields);
		
		//Backwards compatability for old Tribiq frameworks
		} else {
			$this->sections['Tabs'] = $this->setting('show_tabs');
			$this->sections['Next'] = true;
			$this->sections['Prev'] = true;
			
			// Replace phrase codes with phrases in heading text
			if ($this->sections['Show_Title'] = (bool) $this->setting('show_heading')) {
				$this->mergeFields['Title'] = htmlspecialchars($this->setting('heading_text'));
				if ($this->inLibrary) {
					$this->mergeFields['Title'] = $this->phrase($this->mergeFields['Title']);
				}
			}
			
			$this->frameworkHead(
				'Outer',
				'Plugins',
				$this->mergeFields,
				$this->sections);
			
			$tabOrd = 0;
			foreach ($this->tabs as &$tab) {
				$this->mergeFields['TAB_ORDINAL'] = ++$tabOrd;
				
				$this->frameworkHead(
					'Plugins',
					'Plugin',
					$this->mergeFields,
					$this->sections);
				
				if ($this->show) {
					
					$ord = 0;
					foreach ($this->modules[$tab['tab']] as $id => $slotNameNestId) {
						$this->mergeFields['PLUGIN_ORDINAL'] = ++$ord;
						
						$this->showPlugin($slotNameNestId);
					}
				}
				
				$this->frameworkFoot(
					'Plugins',
					'Plugin',
					$this->mergeFields,
					$this->sections);
				
				if ($mode = $this->setting('mode')) {
					//Hide the slides after slide one, until the jQuery slideshow Plugin kicks in and overrides this.
					if ($tabOrd == 1) {
						$this->mergeFields['Nest'] = 'style="display: none;"';
					}
				}
			}
			
			$this->frameworkFoot(
				'Outer',
				'Plugins',
				$this->mergeFields,
				$this->sections);
		}
	}
	
	
	
	public function fillAdminSlotControls(&$controls) {
		zenario_plugin_nest::fillAdminSlotControls($controls);

		if ($this->setting('author_advice')) {
			$controls['notes']['author_advice']['label'] = nl2br(htmlspecialchars($this->setting('author_advice')));
		}
	}

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->fillAdminBox($path, $settingGroup, $box, $fields, $values);
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->validateAdminBox($path, $settingGroup, $box, $fields, $values, $changes, $saving);
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->saveAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
}
