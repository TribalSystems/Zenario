<?php
/*
 * Copyright (c) 2025, Tribal Limited
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




class zenario_abstract_nest extends ze\moduleBaseClass {
	
	protected $firstTab = false;
	protected $lastTab = false;
	protected $showImagesOnTabs = false;
	protected $firstSlide;
	protected $styles = [];
	protected $slides = [];
	protected $slideNum = false;
	protected $slideId = false;
	protected $slideSetsTitle = '';
	protected $slideLayoutId = false;
	protected $state = false;
	protected $usesConductor = false;
	protected $commands = [];
	protected $forwardCommand = false;
	protected $statesToSlides = [];
	protected $mergeFields = [];
	protected $sections = [];
	protected $tabs = [];
	protected $show = false;
	protected $minigrid = [];
	protected $minigridInUse = false;
	protected $usedColumns = 0;
	protected $groupingColumns = 0;
	protected $maxColumns = false;
	protected $sameHeight = false;
	
	protected $currentRequests = [];

	public function getSlides() {
		return $this->slides;
	}
	public function getSlideNum() {
		return $this->slideNum;
	}
	public function getState() {
		return $this->state;
	}
	
	
	public function init() {
		return false;
	}
	
	public function addToPageHead() {
		if ($this->subClass) {
			return $this->subClass->addToPageHead();
		} else {
			$this->addStylesToPageHead($this->styles);
		}
	}
	
	public function formatTitleText($text, $htmlescape = false) {
		
		//The old Tribiq frameworks need things escaped, so put this case in for them.
		//(Note that for backwards compatability reasons the new Twig frameworks are also working like this)
		if ($htmlescape) {
			$text = htmlspecialchars($text);
		}
		
		//If this is a library plugin, and therefore multilingual, we need to translate the text here
		if ($this->inLibrary) {
			$text = $this->phrase($text);
		}
		
		//Break the title up by mergefields, using the [[merge_field_name]] syntax
		$frags = explode('[[', $text);
		$count = count($frags);
	
		if ($count > 1) {
			$text = $frags[0];
			for ($i = 1; $i < $count; ++$i) {
			
				$part = explode(']]', $frags[$i], 2);
			
				if (isset($part[1])) {
					
					
					//Look for variables from modules, using the syntax [[module_class_name:var_name]]
					$details = explode(':', $part[0], 2);
					
					if (isset($details[1])
					 && ze\module::inc($details[0])) {
						
						$val = call_user_func([$details[0], 'requestVarMergeField'], $details[1]);
					
					//Allow any id from the $_REQUEST or core vars to be displayed
					} elseif (isset(ze::$vars[$details[0]])) {
						$val = ze::$vars[$details[0]];
					
					} elseif (isset($_REQUEST[$details[0]])) {
						$val = $_REQUEST[$details[0]];
					
					} else {
						$val = '';
					}
				
					if ($htmlescape) {
						$text .= htmlspecialchars($val ?: '');
					} else {
						$text .= $val;
					}
					
					//Anything that's not a mergefield should be left as-is
					$text .= $part[1];
				} else {
					$text .= $part[0];
				}
			}
		}
		
		return $text;
	}
	
	public static function formatTitleTextAdmin($text, $htmlescape = false) {
		if ($htmlescape) {
			$text = htmlspecialchars($text);
		}
		
		$frags = explode('[[', $text);
		$count = count($frags);
	
		if ($count > 1) {
			$text = $frags[0];
			for ($i = 1; $i < $count; ++$i) {
			
				$part = explode(']]', $frags[$i], 2);
			
				if (isset($part[1])) {
					//Look for variables from modules, using the syntax [[module_class_name:var_name]]
					$details = explode(':', $part[0], 2);
					
					if (isset($details[1])
					 && ze\module::inc($details[0])) {
						
						$val = call_user_func([$details[0], 'requestVarDisplayName'], $details[1]);
						
						if (!is_string($val)) {
							$val = '';
						}
						
						if ($htmlescape) {
							$text .= '<i>'. htmlspecialchars($val). '</i>';
						} else {
							$text .= $val;
						}
							
					
					} else {
						$text .= $part[0];
					}
				
					
					//Anything that's not a mergefield should be left as-is
					$text .= $part[1];
				} else {
					$text .= $part[0];
				}
			}
		}
		
		return $text;
	}
	
	//Allow FABs to call the formatTitleTextAdmin() function via AJAX
	public function handleAJAX() {
		if (isset($_REQUEST['formatTitleTextAdmin']) && ze\priv::check()) {
			echo self::formatTitleTextAdmin($_REQUEST['formatTitleTextAdmin'], (bool) ze::request('htmlescape'));
		}
	}
	
	public function showSlot() {
		
		//...
	}
	
	
	protected function loadTabs() {
		
		$sql = "
			SELECT
				id, id AS slide_id,
				slide_num, css_class, slide_label, slide_link_image_id, set_page_title_with_conductor,
				states, show_back, no_choice_no_going_back, show_refresh, show_auto_refresh, auto_refresh_interval,
				request_vars, global_command,
				privacy, at_location, smart_group_id, module_class_name, method_name, param_1, param_2, always_visible_to_admins
			FROM ". DB_PREFIX. "nested_plugins
			WHERE instance_id = ". (int) $this->instanceId. "
			  AND is_slide = 1
			ORDER BY slide_num";
		
		$result = ze\sql::select($sql);
		$sqlNumRows = ze\sql::numRows($result);
		
		if (!$sqlNumRows) {
			//When a nest is first inserted, it will be empty.
			//This also sometimes happens after a site migration.
			//In this case, call the resyncNest function,
			//e.g. to ensure there is at least one slide and fix any other possibly invalid date
			self::resyncNest($this->instanceId);
			$result = ze\sql::select($sql);
			$sqlNumRows = ze\sql::numRows($result);
		}
		
		if (!$sqlNumRows) {
			return false;
		
		} else {
			while ($row = ze\sql::fetchAssoc($result)) {
				$row['states'] = explode(',', $row['states']);
				$row['request_vars'] = ze\ray::explodeAndTrim($row['request_vars']);
				
				$this->slides[$row['slide_num']] = $row;
			}
			
			$this->removeHiddenSlides($this->slides, $this->cID, $this->cType, $this->cVersion, $this->instanceId);
			
			return !empty($this->slides);
		}
	}
	
	protected function loadSlide($slideNum) {
	
		$eggs = ze::$slotContents[$this->slotName]->eggsOnSlideNum($slideNum);
		
		//Return false if there were no plugins on this slide
		if (empty($eggs)) {
			return false;
		}
		
		
		//Start getting information on all of the plugins we need to load
		$lastSlotNameNestId = false;
		
		foreach ($eggs as $eggid => $slotNameNestId) {
			
			//Read the minigrid information
			$cols = ze::$slotContents[$slotNameNestId]->minigridCols() ?? 0;
			$smallScreens = ze::$slotContents[$slotNameNestId]->minigridSmallScreens() ?? 'show';
			
		
			//If this plugin should be grouped with the previous plugin (-1)...
			if ($cols < 0) {
				if ($lastSlotNameNestId && isset($this->minigrid[$lastSlotNameNestId])) {
					//...flag it on the previous plugin so we know to open the grouping
					$this->minigrid[$lastSlotNameNestId]['group_with_next'] = true;
				} else {
					//...catch the case where there was no previous plugin by converting this to a full-width plugin
					$cols = 0;
				}
			}
		
			//If there are nothing but "full width" and "show on small screens" plugins,
			//then we don't actually need to use a grid and can just leave the HTML alone.
			//But as soon as we see a column that's not full width, or has responsive options,
			//then enable the grid!
			if (!$this->minigridInUse && ($cols > 0 || $smallScreens != 'show')) {
				$this->minigridInUse = true;
			
				//Look up how many columns the current slot has, or just guess 12 if we can't find out
				$this->maxColumns = 
					(int) ze\row::get('layout_slot_link',
						'cols',
						[
							'layout_id' => ze::$layoutId,
							'slot_name' => $this->slotName]
					) ?: 12;
			}
		
			$this->minigrid[$slotNameNestId] = [
				'cols' => min($cols, $this->maxColumns),
				'small_screens' => $smallScreens,
				'group_with_next' => false
			];
		
			$lastSlotNameNestId = $slotNameNestId;
		}
	
		
		return true;
	}
	
	
	
	
	
	public function showPlugin($slotNameNestId, $includeAdminControlsIfInAdminMode = false) {
		
		//Flag that we're no longer running Twig code, if this was called from a Twig Framework
		if ($wasTwig = ze::$isTwig) {
			ze::$isTwig = false;
			ze::noteErrors();
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
				<div class="minigrid '. ze\skin::rationalNumberGridClass($cols, $this->maxColumns);
			
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
					<div class="minigrid '. ze\skin::rationalNumberGridClass($this->groupingColumns, $this->groupingColumns);
				
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
		
		if ($this->sameHeight) {
			echo '
				<div class="nest_egg_equal_height tothesameheight">';
		}
		
		
		$slot = ze::$slotContents[$slotNameNestId];
		$initStatus = $slot->initStatus();
		
		$p = ze\priv::check();
		$noPermsMsg = !$initStatus && (!empty($slot->error()) || $initStatus === ZENARIO_401_NOT_LOGGED_IN || $initStatus === ZENARIO_403_NO_PERMISSION) && $p;
		
		if ($p) {
			if ($noPermsMsg) {
				$slot->class()->start('zenario_nestSlot zenario_slotWithNoPermission');
			} else {
				$slot->class()->start('zenario_nestSlot');
			}
		}
		
		if ($initStatus || $p) {
			$slot->class()->show($includeAdminControlsIfInAdminMode, 'showSlot');
		}
		
		if ($p) {
			echo '
				</x-zenario-admin-slot-wrapper>';
		}
		
		
		if ($this->sameHeight) {
			echo '
				</div>';
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
		
		
		if ($this->needToAddCSSAndJS()
		 && !empty($slot->class())) {
			//Add the script of a Nested Plugin to the Nest
			$scriptTypes = [];
			$slot->class()->zAPICheckRequestedScripts($scriptTypes);
			
			foreach ($scriptTypes as $scriptType => &$scripts) {
				foreach ($scripts as &$script) {
					$this->zAPICallScriptWhenLoaded($scriptType, $script);
				}
			}
		}
		
		//Flag that we're going back to running Twig code, if this was called from a Twig Framework
		if ($wasTwig) {
			ze::$isTwig = true;
			ze::ignoreErrors();
		}
	}
	
	
	
	
	public function addSlideImage(&$tabMergeFields, $slide, $tabOrd) {
		if ($tabMergeFields['Tab_Has_Image'] = $this->showImagesOnTabs && ($imageId = $slide['slide_link_image_id'])) {
			$tabMergeFields['Tab_Image_HTML_ID'] = $this->containerId. '_tab_image_'. $tabOrd;
			
			ze\image::html(
				$this->styles, $preferInlineStypes = false,
				$imageId,
				$this->setting('image_width'), $this->setting('image_height'), $this->setting('image_canvas'), $this->setting('image_retina'),
				$altTag = '', $htmlID = $tabMergeFields['Tab_Image_HTML_ID'], $cssClass = '', $styles = '', $attributes = '',
				$showAsBackgroundImage = true
			);
		}
	}
	
	
	
	
	public function initAnimationLibrarySubClass() {
		
		//When a Nest is first inserted, it will be empty.
		//If the Nest is empty, call the resyncNest function just in case being empty is not a valid state.
		if (ze::isAdmin() && !ze\row::exists('nested_plugins', ['instance_id' => $this->instanceId])) {
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
	
	
	
	
	//Version of refreshPluginSlotAnchor, that doesn't automatically set the slide id
	public function refreshPluginSlotTabAnchor($requests = '', $scrollToTopOfSlot = true, $fadeOutAndIn = false) {
		return
			$this->linkToItemAnchor($this->cID, $this->cType, $fullPath = false, '&slotName='. $this->slotName. ze\ring::urlRequest($requests)).
			' onclick="'.
				$this->refreshPluginSlotJS($requests, $scrollToTopOfSlot, $fadeOutAndIn).
				' return false;"';
	}
	//To show roles and sub-roles
	public static function getRoleTypesIndexedByIdOrderedByName(){
		$ZENARIO_ORGANIZATION_MANAGER_PREFIX = ze\module::prefix('zenario_organization_manager'); 
		$rv = [];
		$ord = 0;
		$sql = "SELECT 
					id,
					parent_id,
					name
				FROM " . 
					DB_PREFIX . $ZENARIO_ORGANIZATION_MANAGER_PREFIX . "user_location_roles
				ORDER BY name";
		$result = ze\sql::select($sql);
		while($row = ze\sql::fetchAssoc($result)){
			$rv[$row['id']] = ['label' => $row['name'], 'parent' => $row['parent_id'], 'ord' => ++$ord];
		}
		return $rv;
		
	
	}
	
	
	protected function needToAddCSSAndJS() {
		return $this->methodCallIs('refreshPlugin');
	}
	
	protected $imgsUsed = [];
	public function noteImage($imageId) {
		if ($imageId) {
			$this->imgsUsed[$imageId] = $imageId;
		}
		return $imageId;
	}
	
	public function fillAdminSlotControls(&$controls) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	
	protected function addPluginConfirm($addId, $instanceId, $copyingInstance = false) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected function removePluginConfirm($eggIds, $instanceId) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected function duplicatePluginConfirm($eggId) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected function removeSlideConfirm($eggIds, $instanceId) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	protected static function addPluginInstance($addPluginInstance, $instanceId, $slideNum = false, $inputIsSlideId = false) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	protected static function addPlugin($addPlugin, $instanceId, $slideNum = false, $displayName = false, $inputIsSlideId = false) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function addBanner($imageId, $instanceId, $slideNum = false, $inputIsSlideId = false) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected static function addTwigSnippet($moduleClassName, $snippetName, $instanceId, $slideNum = false, $inputIsSlideId = false) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	//Create a new, empty slide at the end of the nest
	public static function addSlide($instanceId, $title = false, $slideNum = false) {
		
		if ($slideNum === false) {
			$slideNum = 1 + (int) self::maxTab($instanceId);
		}
		
		if ($title === false) {
			$title = ze\admin::phrase('Slide [[num]]', ['num' => $slideNum]);
		}
		
		return ze\row::insert(
			'nested_plugins',
			[
				'instance_id' => $instanceId,
				'slide_num' => $slideNum,
				'ord' => 0,
				'module_id' => 0,
				'is_slide' => 1,
				'slide_label' => $title]);
	}
	
	public static function duplicatePlugin($eggId, $instanceId) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function copyPastePlugin($sourceId, $isEgg, $instanceId, $destId, $mustBeBanner) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function removePlugin($eggId, $instanceId, $resync = true) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected static function removeSlide($slideId, $instanceId, $resync = true) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	

	public static function reorderNest($instanceId, $ids, $ordinals, $parentIds, $instance = null) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function resyncNest($instanceId, $instance = null) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	protected static function maxTab($instanceId) {
		return ze\sql::fetchValue("
			SELECT MAX(slide_num) AS slide_num
			FROM ". DB_PREFIX. "nested_plugins
			WHERE is_slide = 1
			  AND instance_id = ". (int) $instanceId);
	}
	
	protected static function maxOrd($instanceId, $slideNum) {
		return ze\sql::fetchValue("
			SELECT MAX(ord) AS ord
			FROM ". DB_PREFIX. "nested_plugins
			WHERE slide_num = ". (int) $slideNum. "
			  AND is_slide = 0
			  AND instance_id = ". (int) $instanceId);
	}
	
	
	
	
	protected function removeHiddenSlides(&$tabs, $cID, $cType, $cVersion, $instanceId) {
		
		$unsets = [];
		foreach ($tabs as $slideNum => $slide) {
			
			//Slides need at least one plugin on them to be visible
			if (!ze\row::exists('nested_plugins', ['instance_id' => $instanceId, 'slide_num' => $slideNum, 'is_slide' => 0])) {
				$unsets[] = $slideNum;
				continue;
			}
			
			//Check the visibilty logic for each slide, if it is set
			if (!($slide['always_visible_to_admins'] && ze\priv::check())) {
				
				switch ($slide['privacy']) {
					case 'call_static_method':
					case 'send_signal':
						$this->allowCaching(false);
				}
				
				if (!ze\content::checkItemPrivacy($slide, $slide, ze::$cID, ze::$cType, ze::$cVersion)) {
					$unsets[] = $slideNum;
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
		return isset($this->commands[$command]) && !empty($this->commands[$command]->toState);
	}
	
	public function cLink($command, $requests = []) {
		if (isset($this->commands[$command]) && !empty($this->commands[$command]->toState)) {
			return $this->commands[$command]->link($requests);
		}
		return false;
	}
	
	public function cBackLink() {
		return $this->cLink('back');
	}
	
	protected static function deletePath($instanceId, $fromState, $toState = false, $equivId = 0, $contentType = '') {
		
		//If a from & to are both specified, delete that specific path
		if ($toState) {
			ze\row::delete('nested_paths', ['instance_id' => $instanceId, 'from_state' => $fromState, 'to_state' => $toState, 'equiv_id' => $equivId, 'content_type' => $contentType]);
		
		//If just one state is specified, delete all paths from and to that state
		} else {
			if (!$equivId) {
				$equivId = 0;
			}
			if (!$contentType) {
				$contentType = '';
			}
			
			ze\row::delete('nested_paths', ['instance_id' => $instanceId, 'from_state' => $fromState]);
			ze\row::delete('nested_paths', ['instance_id' => $instanceId, 'to_state' => $fromState]);
		}
		
	}
	
	
	
	

	
	public function returnWhatThisEggIs() {
		return \ze\admin::phrase('This is a plugin in a nest');
	}
	
	public function returnWhatThisIs() {
		if (isset($this->parentNest)) {
			return $this->parentNest->returnWhatThisEggIs();
		
		//Don't show a description for the nest if there are already plugins in it
		} elseif (!empty($this->modules[$this->slideNum])) {
			return '';
		
		} elseif ($this->slotLevel == 2) {
			return \ze\admin::phrase('This is a nest on the layout');
		
		} else {
			return \ze\admin::phrase('This is a nest on the content item');
		}
	}
}
