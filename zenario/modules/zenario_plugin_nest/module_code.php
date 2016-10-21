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

class zenario_plugin_nest extends module_base_class {
	
	protected $firstTab = false;
	protected $lastTab = false;
	protected $tabNum = false;
	protected $state = false;
	protected $usesConductor = false;
	protected $commands = array();
	protected $statesToTabs = array();
	protected $editingTabNum = false;
	protected $mergeFields = array();
	protected $sections = array();
	protected $tabs = array();
	protected $modules = array();
	protected $show = false;
	protected $minigrid = array();
	protected $minigridInUse = false;
	protected $usedColumns = 0;
	protected $groupingColumns = 0;
	protected $maxColumns = false;
	
	public $banner_canvas = false;
	public $banner_width = 0;
	public $banner_height = 0;
	public $banner__enlarge_image = false;
	public $banner__enlarge_canvas = false;
	public $banner__enlarge_width = 0;
	public $banner__enlarge_height = 0;

	public function getTabs() {
		return $this->tabs;
	}
	public function getTabNum() {
		return $this->tabNum;
	}
	
	
	public function init() {
		//Flag that this plugin is actually a nest
		cms_core::$slotContents[$this->slotName]['is_nest'] = true;
		
		$this->loadFramework();
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		if ($this->specificEgg()) {
			$this->tabNum = ifNull(getRow('nested_plugins', 'tab', $this->specificEgg()), 1);
			$this->tabId = getRow('nested_plugins', 'id', array('is_slide' => 1, 'tab' => $this->tabNum));
			$this->loadTabs();
		
		} else {
		
			if ($this->loadTabs()) {
				
				//CHeck to see if a tab or a state is requested in the URL
				$lookForState =
				$lookForTabId =
				$lookForTabNo = 
				$defaultState = false;
				
				if (setting('enable_conductor_for_nests')
				 && !empty($_REQUEST['state'])
				 && preg_match('/^[AB]?[A-Z]$/', $_REQUEST['state'])) {
					$lookForState = $_REQUEST['state'];
				
				} elseif ($lookForTabId = (int) request('tab')) {
				} elseif ($lookForTabNo = (int) request('tab_no')) {
				}
				
				
				$tabOrd = 0;
				foreach ($this->tabs as $tab) {
					++$tabOrd;
					$this->lastTab = $tab['id'];
					
					//By default, show the first tab that the visitor can see...
					if ($tabOrd == 1) {
						$this->firstTab = $tab['id'];
						$this->tabNum = $tab['tab'];
						$this->tabId = $tab['id'];
						$this->state = $tab['states'][0];
						$defaultState = $tab['states'][0];
					}
					
					//...but change this to the one mentioned in the request, if we see it
					if ($lookForState && in_array($lookForState, $tab['states'])) {
						$this->tabNum = $tab['tab'];
						$this->tabId = $tab['id'];
						$this->state = $lookForState;
						$defaultState = $tab['states'][0];
					
					} elseif ($lookForTabId == $tab['id']) {
						$this->tabNum = $tab['tab'];
						$this->tabId = $tab['id'];
						$this->state = $tab['states'][0];
						$defaultState = $tab['states'][0];
					
					} elseif ($lookForTabNo == $tab['tab']) {
						$this->tabNum = $tab['tab'];
						$this->tabId = $tab['id'];
						$this->state = $tab['states'][0];
						$defaultState = $tab['states'][0];
					}
					
					$tabIds[$tab['tab']] = $tab['id'];
					
					if (($this->checkFrameworkSectionExists($section = 'Tab_'. $tab['tab']))
					 || ($section = 'Tab')) {
						
						if (!isset($this->sections[$section])) {
							$this->sections[$section] = array();
						}
						
						$tabMergeFields = array(
							'TAB_ORDINAL' => $tabOrd);
						
						if (!$tab['invisible_in_nav']) {
							$tabMergeFields['Class'] = 'tab_'. $tabOrd. ' tab';
							$tabMergeFields['Tab_Link'] = $this->refreshPluginSlotTabAnchor('tab='. $tab['id'], false);
							$tabMergeFields['Tab_Name'] = htmlspecialchars($tab['name_or_title']);
						
							if ($this->inLibrary) {
								$tabMergeFields['Tab_Name'] = $this->phrase($tabMergeFields['Tab_Name']);
							}
						}
						
						$this->sections[$section][$tab['tab']] = $tabMergeFields;
					}
				}
				
				if ((isset($this->sections[$section = 'Tab'][$this->tabNum]['Class']))
				 || (isset($this->sections[$section = 'Tab_'. $this->tabNum][$this->tabNum]['Class']))) {
					$this->sections[$section][$this->tabNum]['Class'] .= '_on';
				}
				
				
				$nextTabId = false;
				if ($this->lastTab == $this->tabId) {
					if (!$this->setting('next_prev_buttons_loop')) {
						$this->mergeFields['Next_Disabled'] = '_disabled';
					} else {
						$nextTabId = $this->firstTab;
					}
				} else {
					foreach ($this->tabs as $tabNum => $tab) {
						if ($tabNum > $this->tabNum) {
							$nextTabId = $tab['id'];
							break;
						}
					}
				}
				
				if ($nextTabId) {
					$this->mergeFields['Next_Link'] = $this->refreshPluginSlotTabAnchor('tab='. $nextTabId, false);
				}
				
				
				$prevTabId = false;
				if ($this->firstTab == $this->tabId) {
					if (!$this->setting('next_prev_buttons_loop')) {
						$this->mergeFields['Prev_Disabled'] = '_disabled';
					} else {
						$prevTabId = $this->lastTab;
					}
				} else {
					foreach ($this->tabs as $tabNum => $tab) {
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
				
				$this->registerGetRequest('tab', $this->firstTab);
				$this->registerGetRequest('state', $defaultState);
			}
		}
		
		//Load all of the paths from the current state
		if (setting('enable_conductor_for_nests') && $this->state) {
			foreach (getRowsArray(
				'nested_paths',
				array('to_state', 'commands'),
				array('instance_id' => $this->instanceId, 'from_state' => $this->state),
				'to_state'
			) as $path) {
				foreach (explodeAndTrim($path['commands']) as $command) {
					if (!empty($this->statesToTabs[$path['to_state']])) {
						$this->commands[$command] = $path['to_state'];
					}
					$this->usesConductor = true;
				}
			}
			
			if ($this->usesConductor) {
				$this->callScript('zenario_conductor', 'setCommands', $this->slotName, $this->commands);
			}
		}
		
		
		//If all tabs were hidden, don't show anything
		if ($this->tabNum !== false && $this->loadTab($this->tabNum)) {
			$this->show = true;
		
		//...except if no tabs exist, don't hide anything
		} elseif (!checkRowExists('nested_plugins', array('instance_id' => $this->instanceId, 'is_slide' => 1)) && $this->loadTab($this->tabNum = 1)) {
			$this->show = true;
		}
		
		if (!$this->isVersionControlled && checkPriv() && $this->setting('author_advice')) {
			$this->showInEditMode();
		}
		
		if ($this->usesConductor) {
			$importantGetRequests = array();
			foreach(cms_core::$importantGetRequests as $getRequest => $defaultValue) {
				$importantGetRequests[$getRequest] = arrayKey($_GET, $getRequest);
			}

			$this->callScript('zenario_conductor', 'registerGetRequest', $importantGetRequests);
		}
		
		return $this->show;
	}

	
	public function showSlot() {
		$this->mergeFields['TAB_ORDINAL'] = $this->tabNum;
		
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
				$this->mergeFields['Tabs'][$this->tabNum]['Plugins'] = $this->modules[$this->tabNum];
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
			
			$this->frameworkHead(
				'Plugins',
				'Plugin',
				$this->mergeFields,
				$this->sections);
		
			if ($this->show) {
				$ord = 0;
				foreach ($this->modules[$this->tabNum] as $id => $slotNameNestId) {
					$this->mergeFields['PLUGIN_ORDINAL'] = ++$ord;
				
					$this->showPlugin($slotNameNestId);
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
		
		$sql = "
			SELECT
				id, tab, name_or_title,
				states,
				invisible_in_nav,
				visibility, smart_group_id, module_class_name, method_name, param_1, param_2
			FROM ". DB_NAME_PREFIX. "nested_plugins
			WHERE instance_id = ". (int) $this->instanceId. "
			  AND is_slide = 1
			ORDER BY tab";
		
		$result = sqlQuery($sql);
		$sqlNumRows = sqlNumRows($result);
		
		if (!$sqlNumRows) {
			//When a nest is first inserted, it will be empty.
			//This also sometimes happens after a site migration.
			//In this case, call the resyncNest function,
			//e.g. to ensure there is at least one slide and fix any other possibly invalid date
			call_user_func(array($this->moduleClassName, 'resyncNest'), $this->instanceId);
			$result = sqlQuery($sql);
			$sqlNumRows = sqlNumRows($result);
		}
		
		if (!$sqlNumRows) {
			return false;
		} else {
			while ($row = sqlFetchAssoc($result)) {
				$row['states'] = explode(',', $row['states']);
				
				$this->tabs[$row['tab']] = $row;
			}
			
			
			$this->mergeFields['Nest'] = '';
			
			$this->removeHiddenTabs($this->tabs, $this->cID, $this->cType, $this->cVersion, $this->instanceId);
			
			//Note down which states are on which tabs
			foreach ($this->tabs as $tab) {
				foreach ($tab['states'] as $state) {
					$this->statesToTabs[$state] = $tab['tab'];
				}
			}
			
			
			if ($this->setting('banner_canvas')
			 && $this->setting('banner_canvas') != 'unlimited') {
				$this->banner_canvas = $this->setting('banner_canvas');
				$this->banner_width = (int) $this->setting('banner_width');
				$this->banner_height = (int) $this->setting('banner_height');
			}
			
			if ($this->setting('enlarge_image')) {
				$this->banner__enlarge_image = true;
				$this->banner__enlarge_canvas = $this->setting('enlarge_canvas');
				$this->banner__enlarge_width = (int) $this->setting('enlarge_width');
				$this->banner__enlarge_height = (int) $this->setting('enlarge_height');
			}
			
			
			return !empty($this->tabs);
		}
	}
	
	
	protected function loadTab($tabNum) {
		
		$sql = "
			SELECT np.id, np.tab, np.ord, np.module_id, np.framework, np.css_class, np.cols, np.small_screens
			FROM ". DB_NAME_PREFIX. "nested_plugins AS np
			WHERE np.instance_id = ". (int) $this->instanceId. "
			  AND np.is_slide = 0
			  AND np.tab = ". (int) $tabNum;
		
		if ($this->specificEgg()) {
			$sql .= "
			  AND np.id = ". (int) $this->specificEgg();
		}
		
		$sql .= "
			ORDER BY np.ord";
		
		$this->modules[$tabNum] = array();
		$lastSlotNameNestId = false;
		
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)) {
			$missingPlugin = false;
			if (($details = getModuleDetails($row['module_id']))
			 && (includeModuleAndDependencies($details['class_name'], $missingPlugin))
			 && (method_exists($details['class_name'], 'showSlot'))) {
				
				$eggId = $row['id'];
				$baseCSSName = $details['css_class_name'];
				
				$this->modules[$tabNum][$eggId] = $slotNameNestId = $this->slotName. '-'. $eggId;
				
				cms_core::$slotContents[$slotNameNestId] = $details;
				cms_core::$slotContents[$slotNameNestId]['instance_id'] = $this->instanceId;
				cms_core::$slotContents[$slotNameNestId]['egg_id'] = $eggId;
				cms_core::$slotContents[$slotNameNestId]['framework'] = ifNull($row['framework'], $details['default_framework']);
				cms_core::$slotContents[$slotNameNestId]['css_class'] = $details['css_class_name'];
				
				
				if ($row['css_class']) {
					cms_core::$slotContents[$slotNameNestId]['css_class'] .= ' '. $row['css_class'];
				} else {
					cms_core::$slotContents[$slotNameNestId]['css_class'] .= ' '. $baseCSSName. '__default_style';
				}
				
				
				//Add a CSS class for this version controller plugin, or this library plugin
				if ($this->isVersionControlled) {
					if (cms_core::$cID !== -1) {
						cms_core::$slotContents[$slotNameNestId]['css_class'] .=
							' '. cms_core::$cType. '_'. cms_core::$cID. '_'. $this->slotName.
							'_'. $baseCSSName.
							'_'. $row['tab']. '_'. $row['ord'];
					}
				} else {
					cms_core::$slotContents[$slotNameNestId]['css_class'] .=
						' '. $baseCSSName.
						'_'. $this->instanceId.
						'_'. $eggId;
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
				
				
				//Read the minigrid information
				$row['cols'] = (int) $row['cols'];
				
				//If this plugin should be grouped with the previous plugin (-1)...
				if ($row['cols'] < 0) {
					if ($lastSlotNameNestId && isset($this->minigrid[$lastSlotNameNestId])) {
						//...flag it on the previous plugin so we know to open the grouping
						$this->minigrid[$lastSlotNameNestId]['group_with_next'] = true;
					} else {
						//...catch the case where there was no previous plugin by converting this to a full-width plugin
						$row['cols'] = 0;
					}
				}
				
				//If there are nothing but "full width" and "show on small screens" plugins,
				//then we don't actually need to use a grid and can just leave the HTML alone.
				//But as soon as we see a column that's not full width, or has responsive options,
				//then enable the grid!
				if (!$this->minigridInUse && ($row['cols'] > 0 || $row['small_screens'] != 'show')) {
					$this->minigridInUse = true;
					
					//Look up how many columns the current slot has, or just guess 12 if we can't find out
					$this->maxColumns = ifNull(
						(int) getRow('template_slot_link',
							'cols',
							array(
								'family_name' => cms_core::$templateFamily,
								'file_base_name' => cms_core::$templateFileBaseName,
								'slot_name' => $this->slotName)),
						12);
				}
				
				$this->minigrid[$slotNameNestId] = array(
					'cols' => min($row['cols'], $this->maxColumns),
					'small_screens' => $row['small_screens'],
					'group_with_next' => false
				);
				
				$lastSlotNameNestId = $slotNameNestId;
			}
		}
		
		$beingEdited =
		$showInMenuMode =
		$shownInEditMode =
		$addedJavaScript = false;
		foreach ($this->modules[$tabNum] as $id => $slotNameNestId) {
			cms_core::$slotContents[$slotNameNestId]['instance_id'] = $this->instanceId;
			setInstance(cms_core::$slotContents[$slotNameNestId], $this->cID, $this->cType, $this->cVersion, $this->slotName, true, $id, $this->tabId);
			
			if (initPluginInstance(cms_core::$slotContents[$slotNameNestId])) {
				
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
					$addedJavaScript = true;
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
		
		//If we're adding JavaScript, add a short delay to the tab switching to cover for the browser loading things in
		if ($addedJavaScript) {
			$this->callScriptBeforeAJAXReload('zenario_plugin_nest', 'sleep');
		}
		
		//Add any Plugin JavaScript calls
		foreach ($this->modules[$tabNum] as $id => $slotNameNestId) {
			if (!empty(cms_core::$slotContents[$slotNameNestId]['class'])) {
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
		if (!empty(cms_core::$slotContents[$slotNameNestId]['class']->zAPISettings[$setting])) {
			return cms_core::$slotContents[$slotNameNestId]['class']->zAPISettings[$setting];
		} else {
			return false;
		}
	}
	
	
	public function showPlugin($slotNameNestId) {
		
		//Flag that we're no longer running Twig code, if this was called from a Twig Framework
		if ($this->zAPIFrameworkIsTwig) {
			cms_core::$isTwig = false;
		}
		
		if ($this->minigridInUse) {
			$minigrid = $this->minigrid[$slotNameNestId];
			$cols = $minigrid['cols'];
			$groupWithNext = $minigrid['group_with_next'];
			
			//"-1" means group with the previous plugin
			$groupWithPrevious = $cols < 0;
			
			//"0" means max-width
			if ($cols == 0
			 || $cols > $this->maxColumns) {
				$cols = $this->maxColumns;
			}
			
			//If we are not in the grouping, or are just starting a grouping,
			//we need to output a grid-slot.
			if (!$groupWithPrevious) {
			
				//Was there a previous cell?
				if ($this->usedColumns) {
					//Is this cell too big to fit the line..?
					if ($this->usedColumns + $cols > $this->maxColumns) {
						//Put a line break in
						$this->usedColumns = 0;
						echo '
				<div class="grid_clear"></div>';
					}
				}
			
				//Output the div for this 
				echo '
				<div class="minigrid '. rationalNumberGridClass($cols, $this->maxColumns);
			
				//Add the "alpha" class for the first cell on a line
				if ($this->usedColumns == 0) {
					echo ' alpha';
				}
				
				//Increase the number of columns that we have used by the width of this plugin
				$this->usedColumns += $cols;
			
				//Add the "omega" class if the cell goes right up to the end of a line
				if ($this->usedColumns >= $this->maxColumns) {
					echo ' omega';
				}
				
				//Add responsive classes on max-width columns
				//(Unless this is the start of a grouping, in which case the classes should be
				// added on to the nested-grid-slot.)
				if (!$groupWithNext) {
					if ($cols == $this->maxColumns) {
						switch ($minigrid['small_screens']) {
							case 'hide':
								echo ' responsive';
								break;
							case 'only':
								echo ' responsive_only';
								break;
						}
					}
				
				//If this is the start of a grouping, note down how many columns it has
				} else {
					$this->groupingColumns = $cols;
				}
				echo '">';
			
			} else {
				//Nested slots in minigrids are always full-width,
				//so if we are in a grouping, always put a line break in between slots.
				echo '
					<div class="grid_clear"></div>';
			}
			
			//If we are in a grouping, output a nested grid-slot
			if ($groupWithPrevious || $groupWithNext) {
				echo '
					<div class="minigrid '. rationalNumberGridClass($this->groupingColumns, $this->groupingColumns);
				
				//Add responsive classes
				switch ($minigrid['small_screens']) {
					case 'hide':
						echo ' responsive';
						break;
					case 'only':
						echo ' responsive_only';
						break;
				}
				
				//At the moment, nested grid-slots in minigrids are always full width
				echo ' alpha omega">';
			}
		}
		
		
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
			//Backwards compatability for old Tribiq frameworks
			if (!$this->zAPIFrameworkIsTwig) {
				$this->frameworkHead(
					'Plugin',
					'Show_Slot',
					$this->mergeFields);
			}
			
			cms_core::$slotContents[$slotNameNestId]['class']->show(false);
			
			//Backwards compatability for old Tribiq frameworks
			if (!$this->zAPIFrameworkIsTwig) {
				$this->frameworkFoot(
					'Plugin',
					'Show_Slot',
					$this->mergeFields);
			}
		}
		
		if ($p) {
			echo '
				</span>';
		}
		
		
		if ($this->minigridInUse) {
			//We'll need various different closing divs, depending on whether this is the
			//end of a normal slot, the end of a nested slot, or the end of both.
			if ($groupWithPrevious || $groupWithNext) {
				echo '
				</div>';
			}
			
			if (!$groupWithNext) {
				echo '
			</div>';
			}
		}
		
		
		if ($this->needToAddCSSAndJS()) {
			//Add the script of a Nested Plugin to the Nest
			$scriptTypes = array();
			cms_core::$slotContents[$slotNameNestId]['class']->zAPICheckRequestedScripts($scriptTypes);
			
			foreach ($scriptTypes as $scriptType => &$scripts) {
				foreach ($scripts as &$script) {
					$this->zAPICallScriptWhenLoaded($scriptType, $script);
				}
			}
		}
		
		//Flag that we're going back to running Twig code, if this was called from a Twig Framework
		if ($this->zAPIFrameworkIsTwig) {
			cms_core::$isTwig = true;
		}
	}
	
	
	//Allow one specific Egg to be shown for the showFloatingBox/showRSS methods
	protected function specificEgg() {
		
		if (!empty($_REQUEST['method_call'])) {
			switch ($_REQUEST['method_call']) {
				case 'handlePluginAJAX':
				case 'showFloatingBox':
				case 'showRSS':
				case 'fillVisitorTUIX':
				case 'formatVisitorTUIX':
				case 'validateVisitorTUIX':
				case 'saveVisitorTUIX':
					return (int) request('eggId');
			}
		}
		
		return false;
	}
	
	//Version of refreshPluginSlotAnchor, that doesn't automatically set the tab id
	public function refreshPluginSlotTabAnchor($requests = '', $scrollToTopOfSlot = true, $fadeOutAndIn = false) {
		return
			$this->linkToItemAnchor($this->cID, $this->cType, $fullPath = false, '&slotName='. $this->slotName. urlRequest($requests)).
			' onclick="'.
				$this->refreshPluginSlotJS($requests, $scrollToTopOfSlot, $fadeOutAndIn).
				' return false;"';
	}
	
	
	public function showFloatingBox() {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->showFloatingBox();
		}
	}
	public function showRSS() {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->showRSS();
		}
	}
	public function handlePluginAJAX() {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->handlePluginAJAX();
		}
	}
	
	public function returnVisitorTUIXEnabled($path) {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->returnVisitorTUIXEnabled($path);
		}
	}
	
	public function fillVisitorTUIX($path, &$tags, &$fields, &$values) {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->fillVisitorTUIX($path, $tags, $fields, $values);
		}
	}
	
	public function formatVisitorTUIX($path, &$tags, &$fields, &$values, &$changes) {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->formatVisitorTUIX($path, $tags, $fields, $values, $changes);
		}
	}
	
	public function validateVisitorTUIX($path, &$tags, &$fields, &$values, &$changes, $saving) {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->validateVisitorTUIX($path, $tags, $fields, $values, $changes, $saving);
		}
	}
	
	public function saveVisitorTUIX($path, &$tags, &$fields, &$values, &$changes) {
		if ($class = $this->getSpecificEgg($class)) {
			return $class->saveVisitorTUIX($path, $tags, $fields, $values, $changes);
		}
	}
	
	protected function getSpecificEgg(&$class) {
		if ($this->show
		 && ($eggId = $this->specificEgg())
		 && ($slotNameNestId = arrayKey($this->modules[$this->tabNum], $eggId))
		 && (!empty(cms_core::$slotContents[$slotNameNestId]['init']))) {
			return cms_core::$slotContents[$slotNameNestId]['class'];
		}
		return false;
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
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->preFillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->fillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__, 'organizer', $path)) {
			return $c->handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId);
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__, 'organizer', $path)) {
			return $c->organizerPanelDownload($path, $ids, $refinerName, $refinerId);
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
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->adminBoxSaveCompleted($path, $settingGroup, $box, $fields, $values, $changes);
		}
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
	
	
	protected static function addPluginInstance($addPluginInstance, $instanceId, $tab = false, $tabIsTabId = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	protected static function addPlugin($addPlugin, $instanceId, $tab = false, $displayName = false, $tabIsTabId = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected static function addBanner($imageId, $instanceId, $tab = false, $tabIsTabId = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected static function addTwigSnippet($moduleClassName, $snippetName, $instanceId, $tab = false, $tabIsTabId = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	//Create a new, empty tab at the end of the nest
	protected static function addTab($instanceId, $title = false, $tabNo = false) {
		
		if ($tabNo === false) {
			$tabNo = 1 + (int) self::maxTab($instanceId);
		}
		
		if ($title === false) {
			$title = adminPhrase('Slide [[num]]', array('num' => $tabNo));
		}
		
		return insertRow(
			'nested_plugins',
			array(
				'instance_id' => $instanceId,
				'tab' => $tabNo,
				'ord' => 0,
				'module_id' => 0,
				'is_slide' => 1,
				'name_or_title' => $title));
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
	
	

	public static function reorderNest($ids) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function resyncNest($instanceId) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	protected static function maxTab($instanceId) {
		$sql = "
			SELECT MAX(tab) AS tab
			FROM ". DB_NAME_PREFIX. "nested_plugins
			WHERE is_slide = 1
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
			  AND is_slide = 0
			  AND instance_id = ". (int) $instanceId;
		$result = sqlQuery($sql);
		$row = sqlFetchAssoc($result);
		return arrayKey($row, 'ord');
	}
	
	
	
	
	protected function removeHiddenTabs(&$tabs, $cID, $cType, $cVersion, $instanceId) {
		$unsets = array();
		foreach ($tabs as $tabNum => $tab) {
			if (!checkPriv()) {
				//Remove tabs based on the settings chosen
				if ($tab['visibility'] == 'call_static_method' ) {
					
					$this->allowCaching(false);
					
					if (!(inc($tab['module_class_name']))
					 || !(method_exists($tab['module_class_name'], $tab['method_name']))
					 || !(call_user_func(
							array($tab['module_class_name'], $tab['method_name']),
								$tab['param_1'], $tab['param_2'])
					)) {
						$unsets[] = $tabNum;
					}
					
				} elseif ($userId = userId()) {
					switch ($tab['visibility']) {
						case 'in_smart_group':
							if (!checkUserIsInSmartGroup($tab['smart_group_id'], $userId)) {
								$unsets[] = $tabNum;
							}
							break;
							
						case 'logged_in_not_in_smart_group':
							if (checkUserIsInSmartGroup($tab['smart_group_id'], $userId)) {
								$unsets[] = $tabNum;
							}
							break;
							
						case 'logged_out':
							$unsets[] = $tabNum;
					}
				} else {
					switch ($tab['visibility']) {
						case 'in_smart_group':
						case 'logged_in_not_in_smart_group':
						case 'logged_in':
							$unsets[] = $tabNum;
					}
				}
			}
		}
		
		foreach ($unsets as $unset) {
			unset($tabs[$unset]);
		}
	}
	
	
	
	public function cEnabled() {
		return $this->usesConductor;
	}
	
	public function cCommandEnabled($command) {
		return !empty($this->commands[$command]);
	}
	
	public function cLink($command, $requests) {
		if (empty($this->commands[$command])) {
			return false;
		} else {
			$requests['state'] = $this->commands[$command];
			unset($requests['tab']);
			unset($requests['tab_no']);
			return linkToItem(cms_core::$cID, cms_core::$cType, false, $requests, cms_core::$alias);
		}
	}
	
	protected static function deletePath($instanceId, $from, $to = false) {
		
		//If a from & to are both specified, delete that specific path
		if ($to) {
			deleteRow('nested_paths', array('instance_id' => $instanceId, 'from_state' => $from, 'to_state' => $to));
		
		//If just one state is specified, delete all paths from and to that state
		} else {
			deleteRow('nested_paths', array('instance_id' => $instanceId, 'from_state' => $from));
			deleteRow('nested_paths', array('instance_id' => $instanceId, 'to_state' => $from));
		}
		
	}
	
}
