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

namespace ze;


//N.b. the autoloader doesn't work properly for the different types of slots as they are
//defined in the same file as a different library
require_once CMS_ROOT. 'zenario/autoload/slot.php';



class plugin {
	
	public static function codeName($instanceId, $className = null) {
		
		if ($className === null) {
			$moduleId = \ze\row::get('plugin_instances', 'module_id', $instanceId);
			$className = \ze\row::get('modules', 'class_name', $moduleId);
		}
		
		switch ($className) {
			case 'zenario_plugin_nest':
				$p = 'N';
				break;
			case 'zenario_slideshow':
			case 'zenario_slideshow_simple':
				$p = 'S';
				break;
			default:
				$p = 'P';
		}
		
		return $p. str_pad((string) $instanceId, 2, '0', STR_PAD_LEFT);
	}
	
	
	//Check which plugins would be shown on a content item if it was displayed.
	//(This version of the function doesn't actually try to initialise and run the plugins.)
	public static function checkSlotContents(
		&$slotContents,
		$cID, $cType, $cVersion,
		$layoutId = false, $singleSlot = false, $specificSlotName = false
	) {
		\ze\plugin::runSlotContents(
			$slotContents,
			$cID, $cType, $cVersion,
			$layoutId, $singleSlot, $specificSlotName,
			false, false, false, false, false,
			false, false, false,
			false
		);
	}
	
	public static function getSlotVarsFromRequest() {
		
		if (isset($_REQUEST['slotName'])) {
			$slotName = \ze\ring::HTMLId($_REQUEST['slotName']);
		} else {
			$slotName = '';
		}
		
		$instanceId = (int) ($_REQUEST['instanceId'] ?? 0);
		$eggId = (int) ($_REQUEST['eggId'] ?? 0);
		$slideId = (int) ($_REQUEST['slideId'] ?? 0);
		$slideNum = (int) ($_REQUEST['slideNum'] ?? 0);
		$state = ($_REQUEST['state'] ?? '');
		$overrideSettings = $overrideFrameworkAndCSS = false;
		
		if ($slideId) {
			if ($slide = \ze\row::get('nested_plugins', ['instance_id', 'slide_num'], $slideId)) {
				$instanceId = $slide['instance_id'];
				$slideNum = $slide['slide_num'];
			} else {
				$slideId = 0;
			}
		}
		
		if (($instanceId || $slotName) && !empty($_REQUEST['overrideSettings']) && \ze\priv::check('_PRIV_EDIT_DRAFT')) {
			$overrideSettings = json_decode($_REQUEST['overrideSettings'], true);
		}
		if (!empty($_REQUEST['overrideFrameworkAndCSS']) && \ze\priv::check('_PRIV_EDIT_DRAFT')) {
			$overrideFrameworkAndCSS = json_decode($_REQUEST['overrideFrameworkAndCSS'], true);
		}
		
		return [$slotName, $instanceId, $slideId, $slideNum, $state, $eggId, $overrideSettings, $overrideFrameworkAndCSS];
	}

	//Load and run plugins on a content item
	public static function runSlotContents(
		&$slotContents,
		$cID, $cType, $cVersion,
		$layoutId, $singleSlot = false, $specificSlotName = false,
		$specificInstanceId = false, $specificSlideId = false, $specificSlideNum = false, $specificState = false, $specificEggId = false,
		$overrideSettings = false, $overrideFrameworkAndCSS = false, $isAjaxReload = false,
		$runPlugins = true
	) {
	
		if ($layoutId === false) {
			$layoutId = \ze\content::layoutId($cID, $cType, $cVersion);
		}
	
	
		$slots = [];
		$modules = \ze\module::runningModules();
		
		//Allow admins to see that plugins from suspended modules exist
		if (\ze::isAdmin()) {
			$suspendedModules = \ze\module::modules(false, true, false, false);
		} else {
			$suspendedModules = [];
		}
		
		
	
		$whereSlotName = '';
		if ($singleSlot && $specificSlotName && !$specificInstanceId) {
			$whereSlotName = "
				  AND slot_name = '". \ze\escape::asciiInSQL($specificSlotName). "'";
		}
	
		//Look for every plugin instance on the current page, prioritising item level
		//over Layout level, and Layout level over Template Family level.
		$sql = "
			SELECT
				pi.slot_name,
				pi.module_id,
				pi.instance_id,
				vcpi.id AS vcpi_id,
				tsl.slot_name IS NOT NULL as `exists`,
				tsl.is_header,
				tsl.is_footer,
				pi.level
			FROM (
				SELECT slot_name, module_id, instance_id, 2 AS level
				FROM ". DB_PREFIX. "plugin_layout_link
				WHERE layout_id = ". (int) $layoutId.
				  $whereSlotName;
	
		if ($cID) {
			$sql .= "
			  UNION
				SELECT slot_name, module_id, instance_id, 1 AS level
				FROM ". DB_PREFIX. "plugin_item_link
				WHERE content_id = ". (int) $cID. "
				  AND content_type = '". \ze\escape::asciiInSQL($cType). "'
				  AND content_version = ". (int) $cVersion.
				  $whereSlotName;
		}
	
		if (\ze\row::get('layouts', 'header_and_footer', $layoutId)) {
			$sql .= "
			  UNION
				SELECT slot_name, module_id, instance_id, 3 AS level
				FROM ". DB_PREFIX. "plugin_sitewide_link
				WHERE TRUE".
				  $whereSlotName;
		}
	
		$sql .= "
			) AS pi";
	
		//Only show missing slots for Admins with the correct permissions
		$isAdmin = \ze::isAdmin();
		if ($isAdmin && (\ze\priv::check('_PRIV_MANAGE_ITEM_SLOT') || \ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT'))) {
			$sql .= "
			LEFT JOIN ". DB_PREFIX. "layout_slot_link AS tsl";
		} else {
			$sql .= "
			INNER JOIN ". DB_PREFIX. "layout_slot_link AS tsl";
		}
		
		$sql .= "
			   ON tsl.layout_id = '". \ze\escape::sql($layoutId). "'
			  AND tsl.slot_name = pi.slot_name
			LEFT JOIN ". DB_PREFIX. "plugin_instances AS vcpi
			   ON vcpi.module_id = pi.module_id
			  AND vcpi.content_id = ". (int) $cID. "
			  AND vcpi.content_type = '". \ze\escape::asciiInSQL($cType). "'
			  AND vcpi.content_version = ". (int) $cVersion. "
			  AND vcpi.slot_name = pi.slot_name
			  AND pi.instance_id = 0
			WHERE TRUE";
	
		if ($singleSlot && $specificInstanceId) {
			$sql .= "
			  AND IFNULL(vcpi.id, pi.instance_id) = ". (int) $specificInstanceId. "";
		}
		if ($singleSlot && $specificSlotName) {
			$sql .= "
			  AND pi.slot_name = '". \ze\escape::asciiInSQL($specificSlotName). "'";
		}
	
		$sql .= "
			ORDER BY
				tsl.slot_name IS NOT NULL DESC,
				tsl.ord,
				pi.level ASC,
				pi.slot_name";
		
		//In admin mode, if we're loading all slots, or loading the contents of a specific slot,
		//we'll need to display anything that was in a slot but overriden on the item layer.
		//We can't set a limit in this case.
		//If we're looking up a specific plugin, or in visitor mode if we're looking up a specific
		//slot and don't need to display diagnostic information on overriden contents,
		//we can set a limit of 1 row. Do this to slightly increase the efficiency of the query.
		if ($singleSlot && ($specificInstanceId || ($specificSlotName && !$isAdmin))) {
			$sql .= "
			LIMIT 1";
		}
	
	
		$result = \ze\sql::select($sql);
		while($row = \ze\sql::fetchAssoc($result)) {
			$slotName = $row['slot_name'];
			$moduleId = $row['module_id'];
			$instanceId = $row['instance_id'];
		
			//Don't allow Opaque missing slots to count as missing slots
			if (empty($moduleId) && !$row['exists']) {
				continue;
			}
			
		
			//Check if this is a version-controlled Plugin instance
			$isVersionControlled = false;
			if ($moduleId != 0 && $instanceId == 0) {
				$isVersionControlled = true;
			}
			
			
			//Catch the case where a slot on the layout has been overridden on this content item.
			if (isset($slotContents[$slotName])) {
				
				//In admin mode, store some details on the module that was overridden
				if ($isAdmin) {
					if ($module = $modules[$moduleId] ?? $suspendedModules[$moduleId] ?? false) {
						
						$slot = new \ze\normalSlot(
							$row['level'], $row['is_header'], $row['is_footer'],
							$isVersionControlled, $cID, $cType, $cVersion, $slotName,
							$instanceId, $moduleId, $module
						);
						
						switch ($slot->moduleClassName()) {
							case 'zenario_plugin_nest':
								$slot->flagAsNest();
								break;
							case 'zenario_slideshow':
							case 'zenario_slideshow_simple':
								$slot->flagAsNest();
								$slot->flagAsSlideshow();
								break;
						}
						
						if (!isset($modules[$moduleId])) {
							$slot->flagAsSuspended();
						}
						
						$slotContents[$slotName]->setOverriddenSlot($slot);
					}
			
					//If there is something hidden at the item layer and there is a plugin
					//at the layout layer, show a special message
					if ($slotContents[$slotName]->isOpaque()) {
						$slotContents[$slotName]->setErrorMessage(\ze\admin::phrase('[Slot set to show nothing on this content item]'));
					}
				}
				
				continue;
			}
			
			
			//Get an instanceId for version controlled plugins
			if ($isVersionControlled) {
				//Check if an instance has been inserted for this Content Item
				if ($row['vcpi_id']) {
					$instanceId = $row['vcpi_id'];
			
				//Otherwise, create and insert a new version controlled instance
				} elseif ($runPlugins) {
					$instanceId =
						\ze\plugin::vcId($cID, $cType, $cVersion, $slotName, $moduleId);
				}
			}
			
			//The "Opaque" option is a special case; let it through without an "is running" check
			if ($moduleId == 0) {
				//The "Opaque" option is used to hide plugins on the layout layer on specific pages.
				$slots[$slotName] = true;
				$slotContents[$slotName] = new \ze\opaqueSlot($row['level'], $row['is_header'], $row['is_footer'], $slotName);
		
			//Otherwise, if the instance is running, allow it to be added to the page
			} elseif (!empty($modules[$moduleId])) {
				$module = $modules[$moduleId];
				
				//If this is a nest/slideshow/conductor, check what slide, state and/or egg is being requested.
				//Several different options here:
					//1. Slideshows, show every plugin inside the slideshow.
					//2. Nests, show every plugin on the first slide of the nest.
					//3. Nests, show every plugin on the requested slide of the nest.
					//4. Show a specifically requested plugin. (Note: in this case we don't need to load the nest itself..?)
				
				$loadPlugin = true;
				$loadSlide =
				$loadOneSlide =
				$loadAllSlides =
				$loadOneEgg =
				$loadNestedThings = false;
				
				if ($runPlugins) {
					switch ($module['class_name']) {
						case 'zenario_plugin_nest':
							if ($specificInstanceId && $specificEggId) {
								$loadPlugin = false;
								$loadOneEgg =
								$loadNestedThings = true;
							} else {
								$loadSlide =
								$loadOneSlide =
								$loadNestedThings = true;
							}
							break;
						case 'zenario_slideshow':
						case 'zenario_slideshow_simple':
							$loadSlide =
							$loadAllSlides =
							$loadNestedThings = true;
							break;
					}
				}
				
				if ($loadPlugin) {
					$slots[$slotName] = true;
					$slotContents[$slotName] = new \ze\normalSlot(
						$row['level'], $row['is_header'], $row['is_footer'],
						$isVersionControlled, $cID, $cType, $cVersion, $slotName,
						$instanceId, $moduleId, $module
					);
					
					
					//If we're going to be displaying a plugin, we'll need to decide whether
					//it needs to be loaded or whether it can be displayed from the cache.
					//Try to see if we can find its output it from the cache/ directory.
					if ($runPlugins) {
						if (\ze\plugin::loadInstanceFromCache(
							$slotContents, $slotName,
							$cID, $cType, $cVersion
						)) {
							//If the nest is being displayed from the cache, don't try to load the nested plugins!
							$loadSlide =
							$loadOneSlide =
							$loadAllSlides =
							$loadOneEgg =
							$loadNestedThings = false;
						}
					}
				}
				
				
				//For the case where we're showing a nest or conductor that only displays one
				//slide at once, we'll need to work out which slide to show.
				$thisSlideNum = 0;
				if ($loadNestedThings && $loadOneSlide) {
					$loadNestedThings = false;
					
					$sql = "
						SELECT 
							id, id AS slide_id,
							slide_num, css_class, slide_label, set_page_title_with_conductor,
							states, show_back, no_choice_no_going_back, show_embed, show_refresh, show_auto_refresh, auto_refresh_interval,
							request_vars, hierarchical_var, global_command,
							privacy, at_location, smart_group_id, module_class_name, method_name, param_1, param_2, always_visible_to_admins
						FROM ". DB_PREFIX. "nested_plugins AS np
						WHERE np.instance_id = ". (int) $instanceId. "
						  AND np.is_slide = 1
						ORDER BY";
					$comma = '';
					
					if ($specificState) {
						$sql .= $comma. "
							FIND_IN_SET('". \ze\escape::asciiInSQL($specificState). "', np.states) DESC";
						$comma = ',';
					}
					
					if ($specificSlideId) {
						$sql .= $comma. "
							np.id = ". (int) $specificSlideId. " DESC";
						$comma = ',';
					}
					
					if ($specificSlideNum) {
						$sql .= $comma. "
							np.slide_num = ". (int) $specificSlideNum. " DESC";
						$comma = ',';
					}
					
					$sql .= $comma. "
						np.slide_num ASC";
					
					foreach (\ze\sql::select($sql) as $slide) {
						if (($slide['always_visible_to_admins'] && \ze::isAdmin())
						 || \ze\content::checkItemPrivacy($slide, $slide, $cID, $cType, $cVersion)) {
						
							$loadNestedThings = true;
							
							$thisSlideNum = $slide['slide_num'];
							
							$slotContents[$slotName]->setSlideNum($slide['slide_num']);
							$slotContents[$slotName]->setSlideId($slide['slide_id']);
							
							break;
						}
					}
				}
				
				
				if ($loadNestedThings) {
					
					//For each nest/slideshow, record a list of the nested plugins that are running inside.
					$eggs = [];
					
					
					//Very specific case.
					//The nest has an option to insert a "fake" breadcrumbs plugin on each slide.
					//Automatically add a breadcrumb plugin to every slide, if requested in the overall-nest's plugin settings
					if ($loadOneSlide
					 && \ze\plugin::setting('bc_add', $instanceId)
					 && ($bannerModuleId = \ze\row::get('modules', 'id', ['class_name' => 'zenario_breadcrumbs', 'status' => 'module_running']))) {
			
						$egg = [
							'id' => -1,
							'slide_num' => $thisSlideNum,
							'ord' => -1,
							'module_id' => $bannerModuleId,
							'framework' => 'standard',
							'css_class' => '',
							'cols' => (int) \ze\plugin::setting('bc_cols', $instanceId),
							'small_screens' => 'show'
						];
						$eggs[] = $egg;
					}
					
					
					//Look up every nested plugin in this slide
					$sql = "
						SELECT np.id, np.slide_num, np.ord, np.module_id, np.framework, np.css_class, np.cols, np.small_screens
						FROM ". DB_PREFIX. "nested_plugins AS np
						WHERE np.instance_id = ". (int) $instanceId. "
						  AND np.is_slide = 0";
					
					//If this is an AJAX request for a specific plugin, don't load any of the others
					if ($loadOneEgg) {
						$sql .= "
						  AND np.id = ". (int) $specificEggId;
					
					//Nests and conductors only load one slide at once
					} elseif ($loadOneSlide) {
						$sql .= "
						  AND np.slide_num = ". (int) $thisSlideNum;
					}
					
					//If showing all of the plugins on a slide, or multiple slides, 
					//exclude plugins with the "Hidden, breadcrumbs only" option set
					if (!$loadOneEgg) {
						$sql .= "
						  AND np.makes_breadcrumbs != 3";
					}
					
					$sql .= "
						ORDER BY np.ord";
					
					foreach (\ze\sql::select($sql) as $egg) {
						$eggs[] = $egg;
					}
					
					foreach ($eggs as $egg) {
						$eggId = $egg['id'];
						$eggModuleId = $egg['module_id'];
						$slideNum = $egg['slide_num'];
						
						//Come up with a container id for this nested plugin.
						$slotNameNestId = $slotName. '-'. $eggId;
						
						if (!empty($modules[$eggModuleId])) {
							$eggModule = $modules[$eggModuleId];
						
							//For each nest/slideshow, record a list of the nested plugins that are running inside.
							if ($loadPlugin) {
								$slotContents[$slotName]->recordEgg($slideNum, $eggId, $slotNameNestId);
							}
							
							//To do: how slideNum/slideId work and are passed around between functions need a review/tidy-up!
							$slideId =
								\ze\row::get('nested_plugins', 'id', [
									'instance_id' => $instanceId,
									'slide_num' => $egg['slide_num'],
									'is_slide' => 1
								]);
							
							$slots[$slotNameNestId] = true;
							$slotContents[$slotNameNestId] = new nestedSlot(
								$slotName, $slideId, $egg['slide_num'], $egg['ord'],
								$egg['framework'] ?: $eggModule['default_framework'], $egg['css_class'], $egg['cols'], $egg['small_screens'],
								$eggId, $instanceId, $eggModuleId, $eggModule
							);
						}
					}
				}
				
				
		
			//Suspended modules in admin mode
			} elseif (!empty($suspendedModules[$moduleId])) {
				$module = $suspendedModules[$moduleId];
				
				$slots[$slotName] = true;
				$slotContents[$slotName] = new \ze\normalSlot(
					$row['level'], $row['is_header'], $row['is_footer'],
					$isVersionControlled, $cID, $cType, $cVersion, $slotName,
					$instanceId, $moduleId, $module
				);
				
				$slotContents[$slotName]->flagAsSuspended();
			}
		}
	
		//Attempt to initialise each plugin on the page
		if ($runPlugins) {
				
			foreach ($slots as $slotName => $dummy) {
				
				//Logic for initialising nested plugins
				if (!empty($slotContents[$slotName]->eggId())) {
					$slotNameNestId = $slotName;
					$slotName = $slotContents[$slotNameNestId]->slotName();
					$slideId = $slotContents[$slotNameNestId]->slideId();
					$instanceId = $slotContents[$slotNameNestId]->instanceId();
					$eggId = $slotContents[$slotNameNestId]->eggId();
					
					//Very specific case.
					//The nest has an option to insert a "fake" breadcrumbs plugin on each slide.
					//Automatically add a breadcrumb plugin to every slide, if requested in the overall-nest's plugin settings
					$thisSettings = false;
					if ($eggId == -1) {
						$thisSettings = [
							'menu_section' => \ze\plugin::setting('bc_menu_section', $instanceId),
							'breadcrumb_trail' => \ze\plugin::setting('bc_breadcrumb_trail', $instanceId),
							'breadcrumb_prefix_menu' => \ze\plugin::setting('bc_breadcrumb_prefix_menu', $instanceId),
							'breadcrumb_trail_separator' => \ze\plugin::setting('bc_breadcrumb_trail_separator', $instanceId),
							'add_conductor_slides' => \ze\plugin::setting('nest_type', $instanceId) == 'conductor'
						];
					}
					
					$slotContents[$slotNameNestId]->setInstance(
						$cID, $cType, $cVersion,
						$slotName,
						$thisSettings,
						$eggId,
						$slideId
					);
		
					if ($slotContents[$slotNameNestId]->initInstance()) {
						if (!$isAjaxReload
						 && isset($slotContents[$slotName])
						 && ($location = $slotContents[$slotName]->headerRedirectLink())) {
							header("Location: ". $location);
							exit;
						}
					}
				
				//Logic for initialising plugins
				} else
				if (!empty($slotContents[$slotName]->moduleClassName())
				 && !empty($slotContents[$slotName]->instanceId())) {
					
					//No need to initialise plugins being served from the cache
					if (empty($slotContents[$slotName]->servedFromCache())) {
						
						//Show an error for suspended modules
						if (!empty($slotContents[$slotName]->isSuspended())) {
							\ze\plugin::setupNewBaseClass($slotName);
							$slotContents[$slotName]->setErrorMessage(\ze\admin::phrase('[This module is suspended]'));
						
						} else {
							$thisSettings = $thisFrameworkAndCSS = false;
							if ($overrideSettings !== false && $slotName == \ze::request('slotName')) {
								$thisSettings = $overrideSettings;
							}
							if ($overrideFrameworkAndCSS !== false && $slotName == \ze::request('slotName')) {
								$thisFrameworkAndCSS = $overrideFrameworkAndCSS;
							}
					
							$slotContents[$slotName]->loadInstance(
								$slotName,
								$cID, $cType, $cVersion,
								$layoutId,
								$specificInstanceId, $specificSlotName, $isAjaxReload,
								$runPlugins, $thisSettings, $thisFrameworkAndCSS
							);
						}
					}
				
				//Empty slots
				} elseif (!empty($slotContents[$slotName]->level())) {
					\ze\plugin::setupNewBaseClass($slotName);
				}
			}
		}
	}





	//Formerly "getVersionControlledPluginInstanceId()"
	public static function vcId($cID, $cType, $cVersion, $slotName, $moduleId) {
	
	
		if ($cID == 0 || $cID == -1) {
			return $cID;
		}
	
		$ids = ['module_id' => $moduleId, 'content_id' => $cID, 'content_type' => $cType, 'content_version' => $cVersion, 'slot_name' => $slotName];

		if (!$instanceId = \ze\row::get('plugin_instances', 'id', $ids)) {
			$instanceId = \ze\row::insert('plugin_instances', $ids);
		}
	
		return $instanceId;
	}
	
	

	
	private static $pluginPageHeadHTML = [];
	
	public static function loadInstanceFromCache(
		&$slotContents, $slotName,
		$cID, $cType, $cVersion
	) {
		if (!\ze::request('method_call')
		&& isset(\ze::$cacheEnv) && isset(\ze::$allReq) && isset(\ze::$knownReq)
		&& \ze::setting('caching_enabled') && \ze::setting('cache_web_pages')) {

			//Work out what cache-flags to use:
				//u = extranet user logged in
				//g = GET request present that is not registered using registerGetRequest() and is not a CMS variable
				//p = POST request present
				//s = SESSION variable present that is not in the exception list
				//c = COOKIE present that is not in the exception list
			//We can work all of these out exactly except for "g", as registerGetRequest() lets module developers register
			//anything dynamically. There's a bit of logic later that handles this by checking both cases.



			//Get two checksums from the GET requests.
			//$chDirAllRequests is a checksum of every GET request
			//$chDirKnownRequests is a checksum of just the CMS variable, e.g. cID, cType...
			$allReq = \ze::$allReq;
			$knownReq = \ze::$knownReq;

			$allReq['S'] = $slotName;
			$allReq['I'] = \ze::$slotContents[$slotName]->instanceId();
			$knownReq['S'] = $slotName;
			$knownReq['I'] = \ze::$slotContents[$slotName]->instanceId();


			$chDirAllRequests = zenarioPageCacheDir($allReq, 'plugin');
			$chDirKnownRequests = zenarioPageCacheDir($knownReq, 'plugin');

			//Loop through every possible combination of cache-flag
			//(I've tried to order this by the most common settings first,
			//to minimise the number of loops when we have a hit.)
			for ($chS = 's';; $chS = \ze::$cacheEnv['s']) {
				for ($chC = 'c';; $chC = \ze::$cacheEnv['c']) {
					for ($chP = 'p';; $chP = \ze::$cacheEnv['p']) {
						for ($chG = 'g';; $chG = \ze::$cacheEnv['g']) {
							for ($chU = 'u';; $chU = \ze::$cacheEnv['u']) {
					
								//Plugins can opt out of caching if there are any unrecognised or
								//unregistered $_GET requests.
								//If this is the case, then we must insist that the $_GET requests
								//of the cached page match the current $_GET request - i.e. we
								//must use $chDirAllRequests.
								//If this is not the case then we must check both $chDirAllRequests
								//and $chDirKnownRequests as we weren't exactly sure of the value of "g"
								//in index.pre_load.inc.php.
								if ((file_exists(($chPath = 'cache/pages/'. $chDirAllRequests. $chU. $chG. $chP. $chS. $chC. '/'). 'plugin.html'))
								 || ($chG && (file_exists(($chPath = 'cache/pages/'. $chDirKnownRequests. $chU. $chG. $chP. $chS. $chC. '/'). 'plugin.html')))) {
									
									if ((file_exists($chPath. 'vars'))
									&& ($slots = unserialize(file_get_contents($chPath. 'vars'), ['allowed_classes' => ['ze\\opaqueSlot', 'ze\\normalSlot', 'ze\\nestedSlot']/*, 'max_depth' => 3*/]))
									&& (!empty($slots[$slotName]['s']))) {
										touch($chPath. 'accessed');

										//If there are cached images on this page, mark that they've been accessed
										if (file_exists($chPath. 'cached_files')) {
											foreach (file($chPath. 'cached_files', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $cachedImage) {
												if (is_dir($cachedImage)) {
													touch($cachedImage. 'accessed');
												} else {
													//Delete the cached copy as its images are missing
													\ze\cache::deleteDir($chPath);
								
													//Continue the loop looking for any more cached copies of this plugin.
													//Most likely if any exist they will need deleting because their images will be missing too,
													//and it's a good idea to clean up.
													continue 2;
												}
											}
										}

										//Create an entry in the slotContents array, and a simple object, for this Slot.
										//Also do the same for any Nested Plugins.
										foreach ($slots as $slotNameNestId => &$vars) {
											if (!empty($vars['s'])) {
								
												$slotContents[$slotNameNestId] = $vars['s'];
												
												$slotContents[$slotNameNestId]->restoreFromCache(
													$cID, $cType, $cVersion, $slotName,
													$chPath,
													$vars['h'] ?? null,
													$vars['c']
												);
											}
							
											unset($vars);
										}

										return true;
									}
								}
					
								if ($chU == \ze::$cacheEnv['u']) break;
							}
							if ($chG == \ze::$cacheEnv['g']) break;
						}
						if ($chP == \ze::$cacheEnv['p']) break;
					}
					if ($chC == \ze::$cacheEnv['c']) break;
				}
				if ($chS == \ze::$cacheEnv['s']) break;
			}
		}
		
		return false;
	}
	
	
	
	
	

	
	public static function preSlot($slotName, $showPlaceholderMethod, $useOb = true) {
		if (\ze::$canCache
		&& !\ze::request('method_call')
		&& isset(\ze::$cacheEnv) && isset(\ze::$allReq) && isset(\ze::$knownReq)
		&& \ze::setting('caching_enabled') && \ze::setting('cache_web_pages')
		&& empty(\ze::$slotContents[$slotName]->servedFromCache())) {
				
			if ($showPlaceholderMethod == 'addToPageHead') {
				if ($useOb) ob_start();
					
			} elseif ($showPlaceholderMethod == 'addToPageFoot') {
				if ($useOb) ob_start();
					
			} elseif ($showPlaceholderMethod == 'showSlot') {
				if ($useOb) ob_start();
			}
		}
	}
	
	
	

	//Display a Plugin in a slot
	//Formerly "slot()"
	public static function slot($slotName, $mode = false, $eggId = false) {
		//Replacing anything non-alphanumeric with an underscore
		$slotName = \ze\ring::HTMLId($slotName);
		$eggId = (int) $eggId;
		
		$slotNameNestId = $slotName;
		if ($eggId) {
			$slotNameNestId .= '-'. $eggId;
		}
	
		//Start the plugin if it is there, then return it to the Layout
		if (!empty(\ze::$slotContents[$slotNameNestId])
		 && !empty(\ze::$slotContents[$slotNameNestId]->class())
		 && empty(\ze::$slotContents[$slotNameNestId]->error())) {
			$slot = \ze::$slotContents[$slotNameNestId];
			
			++\ze::$pluginsOnPage;
			
			$slot->flagAsUsed();
			$slot->flagAsFound();
		
			$pluginInstance = $slot->class();
			$pluginInstance->start();
	
		//If we didn't find a plugin, but we're in admin mode, 
		//return an "empty" plugin derrived from the base class so that the controls are still displayed to the admin
		} elseif (\ze\priv::check()) {
			//Mark that we've found this slot
			\ze\plugin::setupNewBaseClass($slotNameNestId);
			$slot = \ze::$slotContents[$slotNameNestId];
			
			$slot->flagAsFound();
		
			$pluginInstance = $slot->class();
			$pluginInstance->start();
	
		} else {
			$pluginInstance = false;
		}
	
		if ($mode == 'grid' || $mode == 'outside_of_grid') {
			//New functionality for grids - output the whole slot, don't use a return value
			if ($pluginInstance) {
				$pluginInstance->show();
				$pluginInstance->end();
			}
			//Add some padding for empty grid slots so they don't disappear and break the grid
			if ($mode == 'grid' && (!$pluginInstance || \ze\priv::check())) {
				echo '<span class="pad_slot pad_tribiq_slot">&nbsp;</span>';
				//Note: "pad_tribiq_slot" was the old class name.
				//I'm leaving it in for a while as any old Grid Layouts might still be using that name
				//and they won't be updated until the next time someone edits them.
			}
		
		} else {
			//Old functionality - return the class object
			return $pluginInstance;
		}
	}
	
	
	
	public static function postSlot($slotName, $showPlaceholderMethod, $useOb = true) {
		if (\ze::$canCache
		&& !\ze::request('method_call')
		&& isset(\ze::$cacheEnv) && isset(\ze::$allReq) && isset(\ze::$knownReq)
		&& \ze::setting('caching_enabled') && \ze::setting('cache_web_pages')
		&& empty(\ze::$slotContents[$slotName]->servedFromCache())) {
				
			if ($showPlaceholderMethod == 'addToPageHead') {
				//Note down any html added to the page head
				if ($useOb) {
					self::$pluginPageHeadHTML[$slotName] = ob_get_flush();
				}
					
			} elseif ($showPlaceholderMethod == 'addToPageFoot') {
				//Note down any html added to the page foot
				if ($useOb) {
					if ($html = ob_get_flush()) {
						\ze::$slotContents[$slotName]->class()->zAPICacheFoot($html);
					}
				}
					
			} elseif ($showPlaceholderMethod == 'showSlot') {
	
				\ze::$cacheEnv = \ze::$cacheEnv;
				$saveEnv = \ze::$saveEnv;
				$knownReq = \ze::$knownReq;
	
				$knownReq['S'] = $slotName;
				$knownReq['I'] = \ze::$slotContents[$slotName]->instanceId();
	
	
				//Look for this slot on the page, and check for any Nested Plugins in child-slots
				$eggsToCache = [];
				$len = strlen($slotName) + 1;
				foreach (\ze::$slotContents as $slotNameNestId => &$slot) {
					if ($slot->slotName() == $slotName) {
						$eggsToCache[$slotNameNestId] = [];
					}
				}
				unset($slot);
	
				//Loop through this slot and any child slots, coming up with the rules as to when we can and can't cache a Plugin
				//For nests with child slots, we should combine the rules
				$canCache = true;
				foreach ($eggsToCache as $slotNameNestId => &$cacheVars) {
					$slot = \ze::$slotContents[$slotNameNestId];
					
					if (!empty($slot->disallowCaching())) {
						$canCache = false;
						break;
							
					} else {
						$cacheIf = $slot->cacheIf();
						
						if (empty($cacheIf['a'])) {
							$canCache = false;
							break;
						
						} else {
							foreach ($saveEnv as $if => $set) {
								if (empty($cacheIf[$if])) {
									if (!empty(\ze::$cacheEnv[$if])) {
										$canCache = false;
										break 2;
	
									} else {
										$saveEnv[$if] = '';
									}
								}
							}
						}
					}
				}
				unset($slot, $cacheVars);
	
				if ($canCache) {
					$cacheStatusText = implode('', $saveEnv);
						
					if (\ze\cache::cleanDirs() && ($path = \ze\cache::createDir(zenarioPageCacheDir($knownReq, 'plugin'). $cacheStatusText, 'pages', false))) {						
						
						//Record the slot vars and class vars for this slot, and if this is a nest, any child-slots
						$temps = [];
						$setFiles = [];
						foreach ($eggsToCache as $slotNameNestId => &$cacheVars) {
							$slot = \ze::$slotContents[$slotNameNestId];
							$eggInstance = $slot->class();
							
							//Don't try and check the cache for a nested plugin that refused to load.
							if (is_null($eggInstance)) {
								continue;
							}
						
							//Loop through this slot and any child slots, coming up with the rules as to when we should clear the cache
							//For nests with child slots, we should combine the rules
							$slotCacheClearBy = $slot->cacheClearBy();
							if (!empty($slot->cacheClearBy())) {
								foreach ($slot->cacheClearBy() as $if => $set) {
									if ($set && !isset($setFiles[$if])) {
										$setFiles[$if] = true;
										touch(CMS_ROOT. $path. $if);
										\ze\cache::chmod(CMS_ROOT. $path. $if, 0666);
									}
								}
							}
							
							$cacheVars['c'] = $eggInstance->zAPIGetCachableVars();
							
							$temps[$slotNameNestId] = $slot->trimVarsBeforeCaching();
							$cacheVars['s'] = $slot;

							//Note down any html added to the page head
							if (!empty(self::$pluginPageHeadHTML[$slotNameNestId])) {
								$cacheVars['h'] = self::$pluginPageHeadHTML[$slotNameNestId];
								unset(self::$pluginPageHeadHTML[$slotNameNestId]);
							}
						}
						unset($slot, $cacheVars);
						
						file_put_contents(CMS_ROOT. $path. 'vars', serialize($eggsToCache));
						\ze\cache::chmod(CMS_ROOT. $path. 'vars', 0666);
						
						foreach ($temps as $slotNameNestId => $dummy) {
							$slot = \ze::$slotContents[$slotNameNestId];
							
							$slot->restoreTrimmedVarsAfterCaching($temps[$slotNameNestId]);
						}
						unset($slot, $temps);
	
	
						//If this Plugin is displayed and not hidden, cache its HTML
						$html = '';
						$images = '';
						if ($useOb && !empty(\ze::$slotContents[$slotName]->class()) && !empty(\ze::$slotContents[$slotName]->init())) {
							$html = ob_get_contents();
								
							//Note down any images from the cache directory that are in the page
							foreach(preg_split('@cache/(\w+)(/[\w~_,-]+/)@', $html, -1,  PREG_SPLIT_DELIM_CAPTURE) as $i => $dir) {
								switch ($i % 3) {
									case 1:
										$type = $dir;
										break;
											
									case 2:
										if (\ze::in($type, 'images', 'files', 'downloads')) {
											$images .= 'cache/'. $type. $dir. "\n";
										}
								}
							}
						}
	
	
						file_put_contents(CMS_ROOT. $path. 'plugin.html', $html);
						file_put_contents(CMS_ROOT. $path. 'tag_id', \ze::$cType. '_'. \ze::$cID);
						\ze\cache::chmod(CMS_ROOT. $path. 'plugin.html', 0666);
						\ze\cache::chmod(CMS_ROOT. $path. 'tag_id', 0666);
	
						if ($images) {
							file_put_contents(CMS_ROOT. $path. 'cached_files', $images);
							\ze\cache::chmod(CMS_ROOT. $path. 'cached_files', 0666);
						}
						
						\ze::$slotContents[$slotName]->setCachePath($path);
					}
				}
	
				if ($useOb) ob_end_flush();
			}
		}
	}
	

	//Did we use all of our slots..?
	//Formerly "checkSlotsWereUsed()"
	public static function checkSlotsWereUsed() {
		//Only run this in admin mode
		if (\ze\priv::check()) {
			require \ze::funIncPath(__FILE__, __FUNCTION__);
		}
	}

	
	
	

	//Formerly "getPluginInstanceDetails()"
	public static function details($instanceIdOrName, $useFullName = false) {
	
		$sql = "
			SELECT
				i.id AS instance_id,
				i.name,
				i.content_id,
				i.content_type,
				i.content_version,
				i.slot_name,
				IF(i.framework = '', m.default_framework, i.framework) AS framework,
				m.default_framework,
				m.css_class_name,
				i.css_class,
				i.module_id, i.is_nest, i.is_slideshow,
				m.class_name,
				m.display_name,
				m.vlp_class,
				m.status
			FROM ". DB_PREFIX. "plugin_instances AS i
			INNER JOIN ". DB_PREFIX. "modules AS m
			   ON m.id = i.module_id
			WHERE i.id = ". (int) $instanceIdOrName;
	
		$instance = \ze\sql::fetchAssoc($sql);
		
		if (!$instance) {
			return false;
		}
	
		if ($instance['content_id'] && \ze\priv::check()) {
			$instance['instance_name'] = $instance['display_name'];
		} else {
			$codeName = \ze\plugin::codeName($instance['instance_id'], $instance['class_name']);
			if ($useFullName) {
				$instance['instance_name'] = $codeName. ' '. $instance['name'];
			} else {
				$instance['instance_name'] = $codeName;
			}
		}
	
		unset($instance['display_name']);
		return $instance;
	}

	//Formerly "getPluginInstanceName()"
	public static function name($instanceId) {
		$instanceDetails = \ze\plugin::details($instanceId, true);
		return $instanceDetails['instance_name'];
	}

	public static function setting($name, $instanceId, $eggId = 0) {
		return \ze\row::get('plugin_settings', 'value', [
			'instance_id' => $instanceId, 'egg_id' => $eggId, 'name' => $name
		]);
	}

	//Formerly "getPluginInstanceInItemSlot()"
	public static function idInItemSlot($slotName, $cID, $cType = 'html', $cVersion = false, $getModuleId = false) {
	
		if (!$cVersion) {
			$cVersion = \ze\content::latestVersion($cID, $cType);
		}
	
		$sql = "
			SELECT ". ($getModuleId? 'module_id' : 'instance_id'). "
			FROM ". DB_PREFIX. "plugin_item_link
			WHERE slot_name = '". \ze\escape::asciiInSQL($slotName). "'
			  AND content_id = ". (int) $cID. "
			  AND content_type = '". \ze\escape::asciiInSQL($cType). "'
			  AND content_version = ". (int) $cVersion;
	
		$result = \ze\sql::select($sql);
		if ($row = \ze\sql::fetchRow($result)) {
			return $row[0];
		} else {
			return false;
		}
	}

	//Formerly "checkInstanceIsWireframeOnItemLayer()"
	public static function isVCOnItem($instanceId) {
		return
			($plugin = \ze\row::get('plugin_instances', ['content_id', 'content_type', 'content_version', 'slot_name', 'module_id'], $instanceId))
		 && (!($plugin['instance_id'] = 0))
		 && (\ze\row::exists('plugin_item_link', $plugin));
	}

	//Formerly "getPluginInstanceInTemplateSlot()"
	public static function idInLayoutSlot($slotName, $layoutId, $getModuleId = false) {
	
		$sql = "
			SELECT ". ($getModuleId? 'module_id' : 'instance_id'). "
			FROM ". DB_PREFIX. "plugin_layout_link
			WHERE slot_name = '". \ze\escape::asciiInSQL($slotName). "'
			  AND layout_id = ". (int) $layoutId;
	
		$result = \ze\sql::select($sql);
		if ($row = \ze\sql::fetchRow($result)) {
			return $row[0];
		} else {
			return false;
		}
	}



	public static function setupNewBaseClass($slotName) {
		
		//In admin mode, empty slots won't have meta informaiton preloaded.
		//Look this up from the database as we find them.
		if (!isset(\ze::$slotContents[$slotName])) {
			
			$level =
			$isHeader =
			$isFooter = false;

			if ($slotInfo = \ze\row::get('layout_slot_link', ['is_header', 'is_footer'], ['layout_id' => \ze::$layoutId, 'slot_name' => $slotName])) {
				$isHeader = $slotInfo['is_header'];
				$isFooter = $slotInfo['is_footer'];
			}
			
			\ze::$slotContents[$slotName] = new \ze\opaqueSlot($level, $isHeader, $isFooter, $slotName);
		}
		
		\ze::$slotContents[$slotName]->setupNewBaseClass($slotName);
	}


	//Attempt to find the path to a Framework
	//Formerly "frameworkPath()"
	public static function frameworkPath($framework, $className, $limit = 10) {
		if (!--$limit) {
			return false;
		}
	
		if ($path = \ze::moduleDir($className, 'frameworks/'. $framework. '/framework.twig.html', true, true)) {
			return $path;
		}
	
		$sql = "
			SELECT dependency_class_name
			FROM ". DB_PREFIX. "module_dependencies
			WHERE type = 'inherit_frameworks'
			  AND module_class_name = '". \ze\escape::asciiInSQL($className). "'
			LIMIT 1";
	
		if (($result = \ze\sql::select($sql))
		 && ($row = \ze\sql::fetchRow($result))) {
			return \ze\plugin::frameworkPath($framework, $row[0], $limit);
		} else {
			return false;
		}
	}
}