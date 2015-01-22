<?php
/*
 * Copyright (c) 2014, Tribal Limited
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

class zenario_plugin_nest extends module_base_class {
	
	var $firstTab = false;
	var $lastTab = false;
	var $tabNum = false;
	var $editingTabNum = false;
	var $mergeFields = array();
	var $sections = array();
	var $tabs = array();
	var $modules = array();
	var $show = false;
	
	public function init() {
		//Flag that this plugin is actually a nest
		cms_core::$slotContents[$this->slotName]['is_nest'] = true;
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		if ($this->specificEgg()) {
			$this->tabNum = ifNull(getRow('nested_plugins', 'tab', $this->specificEgg()), 1);
			$this->tabId = getRow('nested_plugins', 'id', array('is_tab' => 1, 'tab' => $this->tabNum));
			$this->loadTabs();
		
		} else {
			//When a Nest is first inserted, it will be empty.
			//If the Nest is empty, call the resyncNest function just in case being empty is not a valid state.
			if (checkPriv() && !checkRowExists('nested_plugins', array('instance_id' => $this->instanceId))) {
				call_user_func(array($this->moduleClassName, 'resyncNest'), $this->instanceId);
			}
		
			if ($this->loadTabs()) {
				$this->sections['Tabs'] = true;
				
				$tabOrd = 0;
				foreach ($this->tabs as $tab) {
					++$tabOrd;
					
					if ($tabOrd == 1) {
						$this->firstTab = $tab['id'];
						$this->tabNum = $tab['tab'];
						$this->tabId = $tab['id'];
					}
					$this->lastTab = $tab['id'];
					
					if (request('tab') == $tab['id']) {
						$this->tabNum = $tab['tab'];
						$this->tabId = $tab['id'];
					
					} elseif (!request('tab') && request('tab_no') == $tab['tab']) {
						$this->tabNum = $tab['tab'];
						$this->tabId = $tab['id'];
					}
					
					$tabIds[$tab['tab']] = $tab['id'];
					
					if (($this->checkFrameworkSectionExists($section = 'Tab_'. $tab['tab']))
					 || ($this->checkFrameworkSectionExists($section = 'Tab'))) {
						
						if (!isset($this->sections[$section])) {
							$this->sections[$section] = array();
						}
						
						$this->sections[$section][$tab['tab']] = array(
							'TAB_ORDINAL' => $tabOrd,
							'Class' => 'tab_'. $tabOrd. ' tab',
							'Tab_Link' => $this->refreshPluginSlotTabAnchor('tab='. $tab['id'], false),
							'Tab_Name' => htmlspecialchars($tab['name_or_title']));
						$this->replacePhraseCodesInString($this->sections[$section][$tab['tab']]['Tab_Name']);
					}
				}
				
				if ((isset($this->sections[$section = 'Tab_'. $this->tabNum][$this->tabNum]['Class']))
				 || (isset($this->sections[$section = 'Tab'][$this->tabNum]['Class']))) {
					$this->sections[$section][$this->tabNum]['Class'] .= '_on';
				}
				
				if ($this->checkFrameworkSectionExists('Next')) {
					$this->sections['Next'] = true;
					
					$nextTabId = false;
					if ($this->lastTab == $this->tabId) {
						if (!$this->setting('next_prev_buttons_loop')) {
							$this->mergeFields['Next_Disabled'] = '_disabled';
						} else {
							$nextTabId = $this->firstTab;
						}
					} else {
						foreach ($this->tabs as $tabNum => &$tab) {
							if ($tabNum > $this->tabNum) {
								$nextTabId = $tab['id'];
								break;
							}
						}
					}
					
					if ($nextTabId) {
						$this->mergeFields['Next_Link'] = $this->refreshPluginSlotTabAnchor('tab='. $nextTabId, false);
					}
				}
				
				if ($this->checkFrameworkSectionExists('Prev')) {
					$this->sections['Prev'] = true;
					
					$prevTabId = false;
					if ($this->firstTab == $this->tabId) {
						if (!$this->setting('next_prev_buttons_loop')) {
							$this->mergeFields['Prev_Disabled'] = '_disabled';
						} else {
							$prevTabId = $this->lastTab;
						}
					} else {
						foreach ($this->tabs as $tabNum => &$tab) {
							if ($tabNum >= $this->tabNum) {
								break;
							} else {
								$prevTabId = $tab['id'];
							}
						}
					}
					
					if ($prevTabId) {
						$this->mergeFields['Prev_Link'] = $this->refreshPluginSlotTabAnchor('tab='. $prevTabId, false);
					}
				}
				
				$this->registerGetRequest('tab', $this->firstTab);
			}
		}
		
		
		//If all tabs were hidden, don't show anything
		if ($this->tabNum !== false && $this->loadTab($this->tabNum)) {
			$this->show = true;
		
		//...except if no tabs exist, don't hide anything
		} elseif (!checkRowExists('nested_plugins', array('instance_id' => $this->instanceId, 'is_tab' => 1)) && $this->loadTab($this->tabNum = 1)) {
			$this->show = true;
		}
		
		if (!$this->isVersionControlled && checkPriv() && $this->setting('author_advice')) {
			$this->showInEditMode();
		}
		
		return $this->show;
	}

	
	public function showSlot() {
		
		$this->mergeFields['TAB_ORDINAL'] = $this->tabNum;

		if ($this->checkShowInFloatingBoxVar()) {
			if ($this->show) {
				
				$ord = 0;
				foreach ($this->modules[$this->tabNum] as $id => $slotNameNestId) {
					$this->mergeFields['PLUGIN_ORDINAL'] = ++$ord;
					
					if (!empty(cms_core::$slotContents[$slotNameNestId]['class'])) {
						if (cms_core::$slotContents[$slotNameNestId]['class']->checkShowInFloatingBoxVar()) {
							$this->showPlugin($id, $slotNameNestId);
						}
					}
				}
			}
		
		} else {
			$this->frameworkHead(
				'Outer',
				'Plugins',
				$this->mergeFields,
				$this->sections);
				
				$this->frameworkHead(
					'Plugins',
					'Plugin',
					$this->mergeFields,
					$this->sections);
						
						if ($this->show) {
							
							$ord = 0;
							foreach ($this->modules[$this->tabNum] as $id => $slotNameNestId) {
								$this->mergeFields['PLUGIN_ORDINAL'] = ++$ord;
								
								$this->showPlugin($id, $slotNameNestId);
							}
						}
				
				$this->frameworkFoot(
					'Plugins',
					'Plugin',
					$this->mergeFields,
					$this->sections);
			
			$this->frameworkFoot(
				'Outer',
				'Plugins',
				$this->mergeFields,
				$this->sections);
		}
	}
	
	
	protected function loadTabs() {
	
		// Replace phrase codes with phrases in heading text
		if ($this->sections['Show_Title'] = (bool) $this->setting('show_heading')) {
			$heading_text = $this->setting('heading_text');
			if ($this->setting('use_phrases')) {
				$this->replacePhraseCodesInString($heading_text);
			}
			$this->mergeFields['Title'] = htmlspecialchars($heading_text);
		}
		
		$sql = "
			SELECT id, tab, name_or_title
			FROM ". DB_NAME_PREFIX. "nested_plugins
			WHERE instance_id = ". (int) $this->instanceId. "
			  AND is_tab = 1
			ORDER BY tab";
		
		$result = sqlQuery($sql);
		
		if (sqlNumRows($result)) {
			while ($row = sqlFetchAssoc($result)) {
				$this->tabs[$row['tab']] = $row;
			}
			
			
			$this->mergeFields['Nest'] = '';
			if ($this->setting('set_max_height')) {
				$this->mergeFields['Nest'] =
					'style="height: '. htmlspecialchars($this->setting('max_height')). '; max-height: '. htmlspecialchars($this->setting('max_height')). ';"';
			}
			
			return true;
		} else {
			return false;
		}
	}
	
	
	protected function loadTab($tabNum) {
		
		$sql = "
			SELECT np.id, np.tab, np.module_id, np.framework, np.css_class
			FROM ". DB_NAME_PREFIX. "nested_plugins AS np
			WHERE np.instance_id = ". (int) $this->instanceId. "
			  AND np.is_tab = 0
			  AND np.tab = ". (int) $tabNum;
		
		if ($this->specificEgg()) {
			$sql .= "
			  AND np.id = ". (int) $this->specificEgg();
		}
		
		$sql .= "
			ORDER BY np.ord";
		
		$this->modules[$tabNum] = array();
		
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)) {
			$missingPlugin = false;
			if (($details = getModuleDetails($row['module_id']))
			 && (includeModuleAndDependencies($details['class_name'], $missingPlugin))
			 && (method_exists($details['class_name'], 'showSlot'))) {
				
				$this->modules[$tabNum][$row['id']] = $slotNameNestId = $this->slotName. '-'. $row['id'];
				
				cms_core::$slotContents[$slotNameNestId] = $details;
				cms_core::$slotContents[$slotNameNestId]['instance_id'] = $this->instanceId;
				cms_core::$slotContents[$slotNameNestId]['egg_id'] = $row['id'];
				cms_core::$slotContents[$slotNameNestId]['framework'] = ifNull($row['framework'], $details['default_framework']);
				cms_core::$slotContents[$slotNameNestId]['css_class'] = $details['css_class_name'];
				
				if ($row['css_class']) {
					cms_core::$slotContents[$slotNameNestId]['css_class'] .= ' '. $row['css_class'];
				}
				
				if ($this->isVersionControlled) {
					cms_core::$slotContents[$slotNameNestId]['content_id'] = $this->cID;
					cms_core::$slotContents[$slotNameNestId]['content_type'] = $this->cType;
					cms_core::$slotContents[$slotNameNestId]['content_version'] = $this->cVersion;
					cms_core::$slotContents[$slotNameNestId]['slot_name'] = $this->slotName;
				} else {
					cms_core::$slotContents[$slotNameNestId]['content_id'] = 0;
					cms_core::$slotContents[$slotNameNestId]['content_type'] = '';
					cms_core::$slotContents[$slotNameNestId]['content_version'] = 0;
					cms_core::$slotContents[$slotNameNestId]['slot_name'] = '';
				}
				
				cms_core::$slotContents[$slotNameNestId]['cache_if'] = array();
				cms_core::$slotContents[$slotNameNestId]['clear_cache_by'] = array();
			}
		}
		
		$beingEdited = false;
		$showInMenuMode = false;
		$shownInEditMode = false;
		foreach ($this->modules[$tabNum] as $id => $slotNameNestId) {
			cms_core::$slotContents[$slotNameNestId]['instance_id'] = $this->instanceId;
			setInstance(cms_core::$slotContents[$slotNameNestId], $this->cID, $this->cType, $this->cVersion, $this->slotName, true, $id, $this->tabId);
			
			//Have some options to set nest-wide size-restrictions on Banner Images
			//However, note that any specific rule that the Banners may have always takes priority over the general rules
			if (cms_core::$slotContents[$slotNameNestId]['class_name'] == 'zenario_banner') {
				if (($overrideCanvas = $this->setting('banner_canvas')) && ($overrideCanvas != 'unlimited')) {
					
					$inheritDimensions = true;
					$eggCanvas = $this->eggSetting($slotNameNestId, 'canvas');
					
					//fixed_width/fixed_height/fixed_width_and_height settings can be merged together
					if ($eggCanvas == 'fixed_width_and_height'
					 || $overrideCanvas == 'fixed_width_and_height'
					 || ($overrideCanvas == 'fixed_width' && $eggCanvas == 'fixed_height')
					 || ($overrideCanvas == 'fixed_height' && $eggCanvas == 'fixed_width')) {
						cms_core::$slotContents[$slotNameNestId]['class']->tApiSettings['canvas'] = 'fixed_width_and_height';
					
					//fixed_width/fixed_height/fixed_width_and_height settings on the nest should not be combined with
					//resize_and_crop settings on the banner, and vice versa. So do an XOR and only update the settings if
					//they're not both different
					} else
					if (!$eggCanvas
					 || $eggCanvas == 'unlimited'
					 || !(($overrideCanvas == 'resize_and_crop')
						 ^ ($eggCanvas == 'resize_and_crop'))) {
						cms_core::$slotContents[$slotNameNestId]['class']->tApiSettings['canvas'] = $overrideCanvas;
					
					} else {
						$inheritDimensions = false;
					}
					
					if ($inheritDimensions && $this->setting('banner_width')) {
						if (!$this->eggSetting($slotNameNestId, 'width')
						 || !in($eggCanvas, 'fixed_width', 'fixed_width_and_height', 'resize_and_crop')) {
							cms_core::$slotContents[$slotNameNestId]['class']->tApiSettings['width'] = $this->setting('banner_width');
						}
					}
					
					if ($inheritDimensions && $this->setting('banner_height')) {
						if (!$this->eggSetting($slotNameNestId, 'height')
						 || !in($eggCanvas, 'fixed_height', 'fixed_width_and_height', 'resize_and_crop')) {
							cms_core::$slotContents[$slotNameNestId]['class']->tApiSettings['height'] = $this->setting('banner_height');
						}
					}
				}
				
				//Also have some nest-wide options to enable colorbox popups, and to set restrictions there too
				if ($this->setting('enlarge_image') && !in($this->eggSetting($slotNameNestId, 'link_type'), '_CONTENT_ITEM', '_EXTERNAL_URL')) {
					cms_core::$slotContents[$slotNameNestId]['class']->tApiSettings['link_type'] = '_ENLARGE_IMAGE';
					
					$inheritDimensions = true;
					$eggCanvas = $this->eggSetting($slotNameNestId, 'enlarge_canvas');
					$overrideCanvas = $this->setting('enlarge_canvas');
					
					//fixed_width/fixed_height/fixed_width_and_height settings can be merged together
					if ($eggCanvas == 'fixed_width_and_height'
					 || $overrideCanvas == 'fixed_width_and_height'
					 || ($overrideCanvas == 'fixed_width' && $eggCanvas == 'fixed_height')
					 || ($overrideCanvas == 'fixed_height' && $eggCanvas == 'fixed_width')) {
						cms_core::$slotContents[$slotNameNestId]['class']->tApiSettings['enlarge_canvas'] = 'fixed_width_and_height';
					
					//fixed_width/fixed_height/fixed_width_and_height settings on the nest should not be combined with
					//resize_and_crop settings on the banner, and vice versa. So do an XOR and only update the settings if
					//they're not both different
					} else
					if (!$eggCanvas
					 || $eggCanvas == 'unlimited'
					 || !(($overrideCanvas == 'resize_and_crop')
						 ^ ($eggCanvas == 'resize_and_crop'))) {
						cms_core::$slotContents[$slotNameNestId]['class']->tApiSettings['enlarge_canvas'] = $overrideCanvas;
					
					} else {
						$inheritDimensions = false;
					}
					
					if ($inheritDimensions && $this->setting('enlarge_width')) {
						if (!$this->eggSetting($slotNameNestId, 'enlarge_width')
						 || !in($eggCanvas, 'fixed_width', 'fixed_width_and_height', 'resize_and_crop')) {
							cms_core::$slotContents[$slotNameNestId]['class']->tApiSettings['enlarge_width'] = $this->setting('enlarge_width');
						}
					}
					
					if ($inheritDimensions && $this->setting('enlarge_height')) {
						if (!$this->eggSetting($slotNameNestId, 'enlarge_height')
						 || !in($eggCanvas, 'fixed_height', 'fixed_width_and_height', 'resize_and_crop')) {
							cms_core::$slotContents[$slotNameNestId]['class']->tApiSettings['enlarge_height'] = $this->setting('enlarge_height');
						}
					}
				}
			}
			
			
			if (initInstance(cms_core::$slotContents[$slotNameNestId])) {
				
				//Check for the forcePageReload and headerRedirect options in modules
				if ($reload = cms_core::$slotContents[$slotNameNestId]['class']->checkForcePageReloadVar()) {
					$this->forcePageReload($reload);
				}
				if ($url = cms_core::$slotContents[$slotNameNestId]['class']->checkHeaderRedirectLocation()) {
					$this->headerRedirect($url);
				}
				
				//Ensure that the JavaScript libraries is there for modules on reloads
				if ($this->needToAddCSSAndJS()) {
					$this->callScriptBeforeAJAXReload('zenario_plugin_nest', 'addJavaScript', cms_core::$slotContents[$slotNameNestId]['class_name'], cms_core::$slotContents[$slotNameNestId]['module_id']);
				}
			}
			
			if (checkPriv() && !empty(cms_core::$slotContents[$slotNameNestId]['class'])) {
				if (!$beingEdited) {
					if ($beingEdited = cms_core::$slotContents[$slotNameNestId]['class']->beingEdited()) {
						$this->editingTabNum = $tabNum;
					}
				}
				if (!$showInMenuMode) {
					$showInMenuMode = cms_core::$slotContents[$slotNameNestId]['class']->shownInMenuMode();
				}
				if (!$shownInEditMode) {
					$shownInEditMode = cms_core::$slotContents[$slotNameNestId]['class']->shownInEditMode();
				}
			}
		}
		
		//If we're adding Swatches or JavaScript, add a short delay to the tab switching to cover for the browser loading things in
		$this->callScriptBeforeAJAXReload('zenario_plugin_nest', 'sleep');
		
		//Add any Plugin JavaScript calls
		foreach ($this->modules[$tabNum] as $id => $slotNameNestId) {
			if (!empty(cms_core::$slotContents[$slotNameNestId]['class'])) {
				if ($this->needToAddCSSAndJS()) {
					//Add the script of a Nested Plugin to the Nest
					$scripts = array();
					$scriptsBefore = array();
					cms_core::$slotContents[$slotNameNestId]['class']->tApiCheckRequestedScripts($scripts, $scriptsBefore);
					
					foreach ($scripts as &$script) {
						$this->tApiCallScriptWhenLoaded(false, $script);
					}
					foreach ($scriptsBefore as &$script) {
						$this->tApiCallScriptWhenLoaded(true, $script);
					}
				}
				
				//Check to see if any Eggs want to scroll to the top of the slot
				$scrollToTop = cms_core::$slotContents[$slotNameNestId]['class']->checkScrollToTopVar();
				if ($scrollToTop !== null) {
					$this->scrollToTopOfSlot($scrollToTop);
				}
				
				//Check to see if any Eggs want to show themselves in a Floating Box, or stop showing themselves in a Floating Box
				if (cms_core::$slotContents[$slotNameNestId]['class']->checkShowInFloatingBoxVar()) {
					$this->showInFloatingBox(true);
				}
			}
		}
		
		//If an Egg wanted to show themselves in a Floating Box, hide the ones that didn't want this.
		if ($this->checkShowInFloatingBoxVar()) {
			$unsets = array();
			foreach ($this->modules[$tabNum] as $id => $slotNameNestId) {
				if (!empty(cms_core::$slotContents[$slotNameNestId]['class'])) {
					if (!cms_core::$slotContents[$slotNameNestId]['class']->checkShowInFloatingBoxVar()) {
						unset(cms_core::$slotContents[$slotNameNestId]);
						$unsets[] = $id;
					}
				}
			}
			foreach ($unsets as $id) {
				unset($this->modules[$tabNum][$id]);
			}
		}
		
		
		if (checkPriv()) {
			$this->markSlotAsBeingEdited($beingEdited);
			$this->showInMenuMode($showInMenuMode);
			$this->showInEditMode($shownInEditMode);
		}
		
		return true;
	}
	
	protected function eggSetting($slotNameNestId, $setting) {
		if (!empty(cms_core::$slotContents[$slotNameNestId]['class']->tApiSettings[$setting])) {
			return cms_core::$slotContents[$slotNameNestId]['class']->tApiSettings[$setting];
		} else {
			return false;
		}
	}
	
	
	protected function showPlugin($id, &$slotNameNestId) {
		$p = checkPriv();
		$i = !empty(cms_core::$slotContents[$slotNameNestId]['init']);
		
		if ($p) {
			echo '
				<span class="',
					$i?
						'zenario_slotWithContents'
					 :	'zenario_slotWithNoContents',
				'">';
		}
		
		if ($i || $p) {
			$this->frameworkHead(
				'Plugin',
				'Show_Slot',
				$this->mergeFields);
			
					cms_core::$slotContents[$slotNameNestId]['class']->show(false);
			
			$this->frameworkFoot(
				'Plugin',
				'Show_Slot',
				$this->mergeFields);
		}
		
		if ($p) {
			echo '
				</span>';
		}
	}
	
	
	//Allow one specific Egg to be shown for the showFloatingBox/showIframe/showRSS methods
	protected function specificEgg() {
		return
			request('method_call') == 'handlePluginAJAX'
		 || request('method_call') == 'showFloatingBox'
		 || request('method_call') == 'showIframe'
		 || request('method_call') == 'showRSS'?
				(int) request('eggId')
			 :	false;
	}
	
	//Version of refreshPluginSlotAnchor, that doesn't automatically set the tab id
	protected function refreshPluginSlotTabAnchor($requests = '', $scrollToTopOfSlot = true, $fadeOutAndIn = false) {
		return
			$this->linkToItemAnchor($this->cID, $this->cType, $fullPath = false, '&slotName='. $this->slotName. urlRequest($requests)).
			' onclick="'.
				$this->refreshPluginSlotJS($requests, $scrollToTopOfSlot, $fadeOutAndIn).
				' return false;"';
	}
	
	
	public function showFloatingBox() {
		$this->showMethod('showFloatingBox');
	}
	public function showRSS() {
		$this->showMethod('showRSS');
	}
	public function handlePluginAJAX() {
		$this->showMethod('handlePluginAJAX');
	}
	
	protected function showMethod($method) {
		if ($this->show) {
			foreach ($this->modules[$this->tabNum] as $id => $slotNameNestId) {
				if ($id == $this->specificEgg()) {
					if (!empty(cms_core::$slotContents[$slotNameNestId]['init']) || checkPriv()) {
						cms_core::$slotContents[$slotNameNestId]['class']->$method();
					}
				}
			}
		}
	}
	
	
	protected function needToAddCSSAndJS() {
		return request('method_call') == 'refreshPlugin';
	}
	
	
	public function handleAJAX() {
		
		if (get('sleep')) {
			//If we're adding Swatches or JavaScript, add a short delay to the tab switching to cover for the browser loading things in
			
			//We could use usleep for a longer delay, but usually the usual delay for the AJAX request is enough to cover
			//usleep(10000);
			
			echo 1;
			exit;
		}
	}
	
	public function fillAdminSlotControls(&$controls) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	
	
	protected function setupConversionAdminBox($instanceId, &$fields, &$instance, &$nestable, &$numPlugins, &$moduleId, &$onlyOneModule, &$onlyBanners) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	
	
	
	protected function addPluginConfirm($addId, $instanceId, $copyingInstance = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected function removePluginConfirm($eggIds, $instanceId) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected function duplicatePluginConfirm($eggId) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected function removeTabConfirm($eggIds, $instanceId) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	protected static function addPluginInstance($addPluginInstance, $instanceId, $tab = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	protected static function addPlugin($addPlugin, $instanceId, $tab = false, $displayName = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected static function addBanner($imageId, $instanceId, $addTab = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	//Create a new, empty tab at the end of the nest
	protected static function addTab($instanceId, $title = false, $tabNo = false) {
		
		if ($tabNo === false) {
			$tabNo = 1 + (int) self::maxTab($instanceId);
		}
		
		if ($title === false) {
			$title = adminPhrase('Tab [[num]]', array('num' => $tabNo));
		}
		
		return insertRow(
			'nested_plugins',
			array(
				'instance_id' => $instanceId,
				'tab' => $tabNo,
				'ord' => 0,
				'module_id' => 0,
				'is_tab' => 1,
				'name_or_title' => $title));
	}
	
	protected function updateTab($title, $nestedItemId) {
		updateRow('nested_plugins', array('name_or_title' => $title), $nestedItemId);
	}
	
	public static function duplicatePlugin($nestedItemId, $instanceId) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function removePlugin($className, $nestedItemId, $instanceId) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected function removeTab($className, $nestedItemId, $instanceId) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	

	static protected function reorderNest($ids, $keepTabsOneToOneWithPlugins = false) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	static protected function resyncNest($instanceId, $mode = 'no_tabs') {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	protected static function maxTab($instanceId) {
		$sql = "
			SELECT MAX(tab) AS tab
			FROM ". DB_NAME_PREFIX. "nested_plugins
			WHERE is_tab = 1
			  AND instance_id = ". (int) $instanceId;
		$result = sqlQuery($sql);
		$row = sqlFetchAssoc($result);
		return arrayKey($row, 'tab');
	}
	
	protected static function maxOrd($instanceId, $tab) {
		$sql = "
			SELECT MAX(ord) AS ord
			FROM ". DB_NAME_PREFIX. "nested_plugins
			WHERE tab = ". (int) $tab. "
			  AND is_tab = 0
			  AND instance_id = ". (int) $instanceId;
		$result = sqlQuery($sql);
		$row = sqlFetchAssoc($result);
		return arrayKey($row, 'ord');
	}
	
	
	
	
}
