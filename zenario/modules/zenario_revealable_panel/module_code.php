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

class zenario_revealable_panel extends zenario_plugin_nest {

	protected function needToAddCSSAndJS() {
		return false;
	}
	
	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
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
			$this->tabs = array(1 => array('id' => 1, 'tab' => 1, 'name_or_title' => ''));
		}
		
		foreach ($this->tabs as &$tab) {
			if ($this->loadTab($tab['tab'])) {
				$this->show = true;
				$this->tabNum = $tab['tab'];
			}
		}
		
		$this->callScript('zenario_revealable_panel', 'js');
		return $this->show;
	}

	
	public function showSlot() {
		
		if ($this->checkShowInFloatingBoxVar()) {
			if ($this->show) {
				foreach ($this->tabs as $tabNum => &$tab) {
					foreach ($this->modules[$tab['tab']] as $id => $slotNameNestId) {
						if (!empty(cms_core::$slotContents[$slotNameNestId]['class'])) {
							if (cms_core::$slotContents[$slotNameNestId]['class']->checkShowInFloatingBoxVar()) {
								$this->showPlugin($id, $slotNameNestId);
							}
						}
					}
				}
			}
		
		} else {
			$tabOrd = 0;
			$tabSections = array();
			foreach ($this->tabs as $tabNum => &$tab) {
				++$tabOrd;
				
				$tabSections[$tabNum] = array();
				$tabSections[$tabNum]['Class'] = 'tab_'. $tabOrd. ' tab';
				$tabSections[$tabNum]['Tab'] = 'id="'. $this->containerId. '__tab'. $tabOrd. '"';
				$tabSections[$tabNum]['Panel'] = 'id="'. $this->containerId. '__panel'. $tabOrd. '"';
				$tabSections[$tabNum]['Tab_Name'] = htmlspecialchars($tab['name_or_title']);
				$this->replacePhraseCodesInString($tabSections[$tabNum]['Tab_Name']);
				
				$tabSections[$tabNum]['TAB_ORDINAL'] = $tabOrd;
				//Old merge field name for backwards compatability
				$tabSections[$tabNum]['Tab_Num'] = $tabOrd;
				
				$tabSections[$tabNum]['Tab_Link'] = 'href="#"';
				if ($this->setting('trigger') == 'click') {
					$tabSections[$tabNum]['Tab_Link'] .= ' onclick="'. $this->jsFunction('click', $tabOrd). ' return false;"';
				} else {
					$tabSections[$tabNum]['Tab_Link'] .= ' onclick="return false;"';
				}
				
				if ($this->setting('trigger') != 'click') {
					$tabSections[$tabNum]['Tab'] .= ' onmouseover="'. $this->jsFunction('over', $tabOrd). '" onmouseout="'. $this->jsFunction('out', $tabOrd). '"';
				}
			}
			
			
			//Draw Panels seperate to Tabs (note - currently I've removed the framework with this behavious as it doesn't work without JavaScript)
			if ($this->checkFrameworkSectionExists('Panel')) {
				$halfwayPoint = 'Panel';
				$this->sections['Tab'] = $tabSections;
			
			//Draw Panels next to Tabs
			} else {
				$halfwayPoint = 'Tab';
			}
				
			$this->frameworkHead(
				'Outer',
				$halfwayPoint,
				$this->mergeFields,
				$this->sections);
					
					$tabOrd = 0;
					foreach ($this->tabs as $tabNum => &$tab) {
						$this->mergeFields['TAB_ORDINAL'] = ++$tabOrd;
						
						if (!$this->checkFrameworkSectionExists($section = 'Tab_'. $tabNum)) {
							$section = $halfwayPoint;
						}
						
						$this->frameworkHead(
							$section,
							'Plugin',
							$tabSections[$tabNum]);
								
								$ord = 0;
								foreach ($this->modules[$tab['tab']] as $id => $slotNameNestId) {
									$this->mergeFields['PLUGIN_ORDINAL'] = ++$ord;
									
									$this->showPlugin($id, $slotNameNestId);
								}
						
						$this->frameworkFoot(
							$section,
							'Plugin',
							$tabSections[$tabNum]);
					}
		
			$this->frameworkFoot(
				'Outer',
				$halfwayPoint,
				$this->mergeFields,
				$this->sections);
		}
	}
	
	protected function jsFunction($name, $tabOrd) {
		return
			"if (!window.zenario_revealable_panel) return false; ".
			"zenario_revealable_panel.". $name. "(this, '". htmlspecialchars($this->setting('fx')). "', ". (int) $this->setting('speed'). ", ". $tabOrd. ");";
	}
	
	
	
	
	
	
	
	
	static protected function resyncNest($instanceId, $mode = 'at_least_one_tab') {
		zenario_plugin_nest::resyncNest($instanceId, $mode);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
	}
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				$box['tabs']['first_tab']['fields']['speed']['hidden'] = 
					$values['first_tab/fx'] != 'fade'
				 && $values['first_tab/fx'] != 'slide'
				 && $values['first_tab/fx'] != 'slide_and_scroll';
				
				zenario_plugin_nest::formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
				
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	}
}

?>