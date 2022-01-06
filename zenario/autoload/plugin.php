<?php
/*
 * Copyright (c) 2022, Tribal Limited
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






//This class defines a "fake" slotable Plugin, which when used will read HTML from the cache cache directory
class cached_plugin extends \ze\moduleBaseClass {

	public $filePath = '';
	public $pageHead = '';
	public $pageFoot = '';

	public function showSlot() {
		\ze::ignoreErrors();
			@readfile($this->filePath);
		\ze::noteErrors();
	}

	public function addToPageHead() {
		echo $this->pageHead;
	}

	public function addToPageFoot() {
		echo $this->pageFoot;
	}
}



class plugin {
	
	public static function codeName($instanceId, $className = '') {
		
		switch ($className) {
			case 'zenario_plugin_nest':
				$p = 'N';
				break;
			case 'zenario_slideshow':
				$p = 'S';
				break;
			default:
				$p = 'P';
		}
		
		return $p. str_pad((string) $instanceId, 2, '0', STR_PAD_LEFT);
	}




	//Get a list of every plugin instance currently running on a page
	//Formerly "getSlotContents()"
	public static function slotContents(
		&$slotContents,
		$cID, $cType, $cVersion,
		$layoutId = false,
		$specificInstanceId = false, $specificSlotName = false, $ajaxReload = false,
		$runPlugins = true, $exactMatch = false, $overrideSettings = false, $overrideFrameworkAndCSS = false
	) {
	
		if ($layoutId === false) {
			$layoutId = \ze\content::layoutId($cID, $cType, $cVersion);
		}
	
	
		$slots = [];
		$modules = \ze\module::runningModules();
	
		$whereSlotName = '';
		if ($specificSlotName && !$specificInstanceId) {
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
				pi.level
			FROM (
				SELECT slot_name, module_id, instance_id, id, 'template' AS type, 2 AS level
				FROM ". DB_PREFIX. "plugin_layout_link
				WHERE layout_id = ". (int) $layoutId.
				  $whereSlotName;
	
		if ($cID) {
			$sql .= "
			  UNION
				SELECT slot_name, module_id, instance_id, id, 'item' AS type, 1 AS level
				FROM ". DB_PREFIX. "plugin_item_link
				WHERE content_id = ". (int) $cID. "
				  AND content_type = '". \ze\escape::asciiInSQL($cType). "'
				  AND content_version = ". (int) $cVersion.
				  $whereSlotName;
		}
	
		$sql .= "
			) AS pi";
	
		//Only show missing slots for Admins with the correct permissions
		if (\ze::isAdmin() && (\ze\priv::check('_PRIV_MANAGE_ITEM_SLOT') || \ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT'))) {
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
	
		if ($exactMatch && $specificInstanceId) {
			$sql .= "
			  AND IFNULL(vcpi.id, pi.instance_id) = ". (int) $specificInstanceId. "";
		}
		if ($exactMatch && $specificSlotName) {
			$sql .= "
			  AND pi.slot_name = '". \ze\escape::asciiInSQL($specificSlotName). "'";
		}
	
		$sql .= "
			ORDER BY";
		
		if (!$exactMatch && $specificInstanceId) {
			$sql .= "
				IFNULL(vcpi.id, pi.instance_id) = ". (int) $specificInstanceId. " DESC,";
		}
		if (!$exactMatch && $specificSlotName) {
			$sql .= "
				pi.slot_name = '". \ze\escape::asciiInSQL($specificSlotName). "' DESC,";
		}
	
		$sql .= "
				tsl.slot_name IS NOT NULL DESC,
				tsl.ord,";
		
		if ($specificInstanceId || $specificSlotName) {
			$sql .= "
				pi.level ASC,
				pi.slot_name
			LIMIT 1";
		
			$checkOpaqueRulesAreValid = false;
	
		} else {
			$sql .= "
				pi.level DESC,
				pi.slot_name";
		
			$checkOpaqueRulesAreValid = true;
		}
	
	
		$result = \ze\sql::select($sql);
		while($row = \ze\sql::fetchAssoc($result)) {
		
			//Don't allow Opaque missing slots to count as missing slots
			if (empty($row['module_id']) && !$row['exists']) {
				continue;
			}
		
			//Check if this is a version-controlled Plugin instance
			$isVersionControlled = false;
			if ($row['module_id'] != 0 && $row['instance_id'] == 0) {
				$isVersionControlled = true;
			
				//Check if an instance has been inserted for this Content Item
				if ($row['vcpi_id']) {
					$row['instance_id'] = $row['vcpi_id'];
			
				//Otherwise, create and insert a new version controlled instance
				} elseif ($runPlugins) {
					$row['instance_id'] =
						\ze\plugin::vcId($cID, $cType, $cVersion, $row['slot_name'], $row['module_id']);
				}
			}
		
			//The "Opaque" option is a special case; let it through without an "is running" check
			if ($row['module_id'] == 0) {
				//The "Opaque" option is used to hide plugins on the layout layer on specific pages.
				//It's not valid if it's not actually covering anything up!
				if ($checkOpaqueRulesAreValid && empty($slotContents[$row['slot_name']])) {
					continue;
				}
			
				$slotContents[$row['slot_name']] = ['instance_id' => 0, 'module_id' => 0];
				$slotContents[$row['slot_name']]['error'] = \ze\admin::phrase('[Slot set to show nothing on this content item]');
				$slotContents[$row['slot_name']]['level'] = $row['level'];
				$slots[$row['slot_name']] = true;
		
			//Otherwise, if the instance is running, allow it to be added to the page
			} elseif (!empty($modules[$row['module_id']])) {
				$slotContents[$row['slot_name']] = $modules[$row['module_id']];
				$slotContents[$row['slot_name']]['level'] = $row['level'];
				$slotContents[$row['slot_name']]['module_id'] = $row['module_id'];
				$slotContents[$row['slot_name']]['instance_id'] = $row['instance_id'];
				$slotContents[$row['slot_name']]['css_class'] = $modules[$row['module_id']]['css_class_name'];
			
				if ($isVersionControlled) {
					$slotContents[$row['slot_name']]['content_id'] = $cID;
					$slotContents[$row['slot_name']]['content_type'] = $cType;
					$slotContents[$row['slot_name']]['content_version'] = $cVersion;
					$slotContents[$row['slot_name']]['slot_name'] = $row['slot_name'];
				}
			
				$slotContents[$row['slot_name']]['cache_if'] = [];
				$slotContents[$row['slot_name']]['clear_cache_by'] = [];
			
				$slots[$row['slot_name']] = true;
			}
		}
	
		//Attempt to initialise each plugin on the page
		if ($runPlugins) {
			foreach ($slots as $slotName => $dummy) {
				if (!empty($slotContents[$slotName]['class_name']) && !empty($slotContents[$slotName]['instance_id'])) {
					$moduleClassName = $slotContents[$slotName]['class_name'];
		
					if (!isset(\ze::$modulesOnPage[$moduleClassName])) {
						\ze::$modulesOnPage[$moduleClassName] = [];
					}
					\ze::$modulesOnPage[$moduleClassName][] = $slotName;
				}
			}
				
			foreach ($slots as $slotName => $dummy) {
				if (!empty($slotContents[$slotName]['class_name']) && !empty($slotContents[$slotName]['instance_id'])) {
					
					$thisSettings = $thisFrameworkAndCSS = false;
					if ($overrideSettings !== false && $slotName == \ze::request('slotName')) {
						$thisSettings = $overrideSettings;
					}
					if ($overrideFrameworkAndCSS !== false && $slotName == \ze::request('slotName')) {
						$thisFrameworkAndCSS = $overrideFrameworkAndCSS;
					}
					
					\ze\plugin::loadInstance(
						$slotContents, $slotName,
						$cID, $cType, $cVersion,
						$layoutId,
						$specificInstanceId, $specificSlotName, $ajaxReload,
						$runPlugins, $thisSettings, $thisFrameworkAndCSS);
		
				} elseif (!empty($slotContents[$slotName]['level'])) {
					\ze\plugin::setupNewBaseClass($slotName);
			
					//Treat the case of hidden (item layer) and empty (layout layer) as just empty,
					//but if there is something hidden at the item layer and there is a plugin
					//at the layout layer, show a special message
					if (!$checkOpaqueRulesAreValid
					 && $slotContents[$slotName]['level'] == 1
					 && $layoutId
					 && \ze\row::exists('plugin_layout_link', ['slot_name' => $slotName, 'layout_id' => $layoutId])) {
						$slotContents[$slotName]['error'] = \ze\admin::phrase('[Slot set to show nothing on this content item]');
					}
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
	
	//Activate and setup a plugin
	//Note that the function canActivateModule() or equivalent should be called on the plugin's name before calling \ze\plugin::setInstance(), loadPluginInstance() or \ze\plugin::initInstance()
	//Formerly "setInstance()"
	public static function setInstance(&$instance, $cID, $cType, $cVersion, $slotName, $checkForErrorPages = false, $overrideSettings = false, $eggId = 0, $slideId = 0, $beingDisplayed = true) {
	
		$missingPlugin = false;
		if (!\ze\module::incWithDependencies($instance['class_name'], $missingPlugin)) {
			$instance['class'] = false;
			return false;
		}
	
		$instance['class'] = new $instance['class_name'];
		
		$instance['class']->setInstance(
			[
				$cID, $cType, $cVersion, $slotName,
				($instance['instance_name'] ?? false), $instance['instance_id'],
				$instance['class_name'], $instance['vlp_class'],
				$instance['module_id'],
				$instance['framework'],
				$instance['css_class'],
				($instance['level'] ?? false), !empty($instance['content_id'])
			], $overrideSettings, $eggId, $slideId, $beingDisplayed
		);
	}
	
	
	public static function loadInstance(
			&$slotContents, $slotName,
			$cID, $cType, $cVersion,
			$layoutId,
			$specificInstanceId, $specificSlotName, $ajaxReload,
			$runPlugins, $overrideSettings = false, $overrideFrameworkAndCSS = false
	) {
	
		if (!($_REQUEST['method_call'] ?? false)
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
			$allReq['I'] = \ze::$slotContents[$slotName]['instance_id'];
			$knownReq['S'] = $slotName;
			$knownReq['I'] = \ze::$slotContents[$slotName]['instance_id'];
				
				
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
									&& ($slots = unserialize(file_get_contents($chPath. 'vars')))
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
	
												if (!empty($vars['c'])) {
													$slotContents[$slotNameNestId]['class'] = new cached_plugin;
													$slotContents[$slotNameNestId]['class']->filePath = $chPath. 'plugin.html';
													
													if (isset($vars['h'])) {
														$slotContents[$slotNameNestId]['class']->pageHead = $vars['h'];
														unset($vars['h']);
													}
													if (file_exists($chPath. 'foot.html')) {
														$slotContents[$slotNameNestId]['class']->pageFoot = file_get_contents($chPath. 'foot.html');
													}
													if (isset($vars['l'])) {
														foreach ($vars['l'] as $lib => $dummy) {
															\ze::requireJsLib($lib);
														}
														unset($vars['l']);
													}
													
													$slotContents[$slotNameNestId]['class']->setInstanceVariables([
														\ze::$cID, \ze::$cType, \ze::$cVersion, $slotName,
														($slotContents[$slotNameNestId]['instance_name'] ?? false), $slotContents[$slotNameNestId]['instance_id'],
														$slotContents[$slotNameNestId]['class_name'], $slotContents[$slotNameNestId]['vlp_class'],
														$slotContents[$slotNameNestId]['module_id'],
														$slotContents[$slotNameNestId]['framework'],
														$slotContents[$slotNameNestId]['css_class'],
														($slotContents[$slotNameNestId]['level'] ?? false), !empty($slotContents[$slotNameNestId]['content_id'])
													]);
	
													\ze::$slotContents[$slotNameNestId]['class']->zAPISetCachableVars($vars['c']);
													
													if (isset(\ze::$slotContents[$slotNameNestId]['page_title'])) {
														\ze::$pageTitle = \ze::$slotContents[$slotNameNestId]['page_title'];
													}
													if (isset(\ze::$slotContents[$slotNameNestId]['page_desc'])) {
														\ze::$pageDesc = \ze::$slotContents[$slotNameNestId]['page_desc'];
													}
													if (isset(\ze::$slotContents[$slotNameNestId]['page_image'])) {
														\ze::$pageImage = \ze::$slotContents[$slotNameNestId]['page_image'];
													}
													if (isset(\ze::$slotContents[$slotNameNestId]['page_keywords'])) {
														\ze::$pageKeywords = \ze::$slotContents[$slotNameNestId]['page_keywords'];
													}
													if (isset(\ze::$slotContents[$slotNameNestId]['page_og_type'])) {
														\ze::$pageOGType = \ze::$slotContents[$slotNameNestId]['page_og_type'];
													}
													if (isset(\ze::$slotContents[$slotNameNestId]['menu_title'])) {
														\ze::$menuTitle = \ze::$slotContents[$slotNameNestId]['menu_title'];
													}
													
												} else {
													$slotContents[$slotNameNestId]['init'] =
													$slotContents[$slotNameNestId]['class'] = false;
												}
	
												$slotContents[$slotNameNestId]['served_from_cache'] = true;
												\ze::$cachingInUse = true;
											}
											
											unset($vars);
										}
	
										return;
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
		
		
		
		$missingPlugin = false;
		$slot = &$slotContents[$slotName];
		
		if (\ze\module::incWithDependencies($slot['class_name'], $missingPlugin)
		 && method_exists($slot['class_name'], 'showSlot')) {
			
			//Fetch the name of the instance, and the name of the framework being used
			$sql = "
				SELECT name, framework, css_class
				FROM ". DB_PREFIX. "plugin_instances
				WHERE id = ". (int) $slot['instance_id'];
			$result = \ze\sql::select($sql);
			if ($row = \ze\sql::fetchAssoc($result)) {
				
				//If we found a plugin to display, activate it and set it up
				$slot['instance_name'] = $row['name'];
				
				
				//Set the framework for this plugin
				if ($overrideFrameworkAndCSS !== false
				 && !empty($overrideFrameworkAndCSS['framework_tab/framework'])) {
					$slot['framework'] = $overrideFrameworkAndCSS['framework_tab/framework'];
				
				} elseif (!empty($row['framework'])) {
					$slot['framework'] = $row['framework'];
				
				} else {
					$slot['framework'] = $slot['default_framework'];
				}
				
				
				//Set the CSS class for this plugin
				$baseCSSName = $slot['css_class_name'];
				
				if ($overrideFrameworkAndCSS !== false) {
					$row['css_class'] = $overrideFrameworkAndCSS['this_css_tab/css_class'] ?? $row['css_class'];
				}
				
				if ($row['css_class']) {
					$slot['css_class'] .= ' '. $row['css_class'];
				} else {
					$slot['css_class'] .= ' '. $baseCSSName. '__default_style';
				}
				
				
				//Add a CSS class for this version controller plugin, or this library plugin
				if (!empty($slot['content_id'])) {
					if ($cID !== -1) {
						$slot['css_class'] .=
							' '. $cType. '_'. $cID. '_'. $slotName.
							'_'. $baseCSSName;
					}
				} else {
					$slot['css_class'] .=
						' '. $baseCSSName.
						'_'. $slot['instance_id'];
				}
					
				
				if ($runPlugins) {
					\ze\plugin::setInstance($slot, $cID, $cType, $cVersion, $slotName, $checkForErrorPages = true, $overrideSettings);
					
					if (\ze\plugin::initInstance($slot)) {
						if (!$ajaxReload && ($location = $slot['class']->checkHeaderRedirectLocation())) {
							header("Location: ". $location);
							exit;
						}
					}
				}
				
			} else {
				$module = \ze\module::details($slot['module_id']);
				
				if ($runPlugins) {
					
					//If this is a layout preview, any version controlled plugin won't have an instance id
					//and can't be displayed properly, but set it up as best we can.
					if ($cID === -1
					 && \ze\module::inc($className = $module['class_name'])) {
						\ze::$slotContents[$slotName]['class'] = new $className;
						\ze::$slotContents[$slotName]['class']->setInstance([
							\ze::$cID, \ze::$cType, \ze::$cVersion, $slotName,
							false, false,
							$className, $module['vlp_class'],
							$module['id'],
							$module['default_framework'],
							$module['css_class_name'],
							false, true]);
					
					//Otherwise if this is a layout preview, then no instance id is an error!
					} else {
						\ze\plugin::setupNewBaseClass($slotName);
						$slot['error'] = \ze\admin::phrase('[Plugin Instance not found for the Module &quot;[[display_name|escape]]&quot;]', $module);
					}
				}
			}
		} else {
			$module = \ze\module::details($slot['module_id']);
			
			if ($runPlugins) {
				\ze\plugin::setupNewBaseClass($slotName);
				$slot['error'] = \ze\admin::phrase('[Selected Module "[[display_name|escape]]" not found, not running, or has missing dependencies]', $module);
			}
		}
		
		
		//If a Plugin refused to show itself, cache this refusal as well
		if (empty($slotContents[$slotName]['init'])) {
			self::postSlot($slotName, 'showSlot', $useOb = false);
		}
	}
	
	
	

	//Work out whether we are displaying this Plugin.
	//Run the plugin's own initalisation routine. If it returns true, then display the plugin.
	//(But note that modules are always displayed in admin mode.)
	//Formerly "initPluginInstance()"
	public static function initInstance(&$instance) {
		
		$status = $instance['class']->init();
		
		if (\ze::isError($status)) {
			$instance['error'] = $status->__toString();
			$status = false;
		}
		
		if (!($instance['init'] = $status) && !(\ze\priv::check())) {
			$instance['class'] = false;
			return false;
		} else {
			return true;
		}
	}
	
	public static function preSlot($slotName, $showPlaceholderMethod, $useOb = true) {
		if (\ze::$canCache
		&& !($_REQUEST['method_call'] ?? false)
		&& isset(\ze::$cacheEnv) && isset(\ze::$allReq) && isset(\ze::$knownReq)
		&& \ze::setting('caching_enabled') && \ze::setting('cache_web_pages')
		&& empty(\ze::$slotContents[$slotName]['served_from_cache'])) {
				
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
	public static function slot($slotName, $mode = false) {
		//Replacing anything non-alphanumeric with an underscore
		$slotName = \ze\ring::HTMLId($slotName);
	
		//Start the plugin if it is there, then return it to the Layout
		if (!empty(\ze::$slotContents[$slotName])
		 && !empty(\ze::$slotContents[$slotName]['class'])
		 && empty(\ze::$slotContents[$slotName]['error'])) {
			++\ze::$pluginsOnPage;
			\ze::$slotContents[$slotName]['used'] = true;
			\ze::$slotContents[$slotName]['found'] = true;
		
			\ze::$slotContents[$slotName]['class']->start();
		
			$slot = \ze::$slotContents[$slotName]['class'];
	
		//If we didn't find a plugin, but we're in admin mode, 
		//return an "empty" plugin derrived from the base class so that the controls are still displayed to the admin
		} elseif (\ze\priv::check()) {
			//Mark that we've found this slot
			\ze\plugin::setupNewBaseClass($slotName);
			\ze::$slotContents[$slotName]['found'] = true;
		
			\ze::$slotContents[$slotName]['class']->start();
		
			$slot = \ze::$slotContents[$slotName]['class'];
	
		} else {
			$slot = false;
		}
	
		if ($mode == 'grid' || $mode == 'outside_of_grid') {
			//New functionality for grids - output the whole slot, don't use a return value
			if ($slot) {
				$slot->show();
				$slot->end();
			}
			//Add some padding for empty grid slots so they don't disappear and break the grid
			if ($mode == 'grid' && (!$slot || \ze\priv::check())) {
				echo '<span class="pad_slot pad_tribiq_slot">&nbsp;</span>';
				//Note: "pad_tribiq_slot" was the old class name.
				//I'm leaving it in for a while as any old Grid Layouts might still be using that name
				//and they won't be updated until the next time someone edits them.
			}
		
		} else {
			//Old functionality - return the class object
			return $slot;
		}
	}
	
	
	
	public static function postSlot($slotName, $showPlaceholderMethod, $useOb = true) {
		if (\ze::$canCache
		&& !($_REQUEST['method_call'] ?? false)
		&& isset(\ze::$cacheEnv) && isset(\ze::$allReq) && isset(\ze::$knownReq)
		&& \ze::setting('caching_enabled') && \ze::setting('cache_web_pages')
		&& empty(\ze::$slotContents[$slotName]['served_from_cache'])) {
				
			if ($showPlaceholderMethod == 'addToPageHead') {
				//Note down any html added to the page head
				if ($useOb) {
					self::$pluginPageHeadHTML[$slotName] = ob_get_flush();
				}
					
			} elseif ($showPlaceholderMethod == 'addToPageFoot') {
				//Note down any html added to the page foot
				if ($useOb) {
					if ($html = ob_get_flush()) {
						\ze::$slotContents[$slotName]['class']->zAPICacheFoot($html);
					}
				}
					
			} elseif ($showPlaceholderMethod == 'showSlot') {
	
				\ze::$cacheEnv = \ze::$cacheEnv;
				$saveEnv = \ze::$saveEnv;
				$knownReq = \ze::$knownReq;
	
				$knownReq['S'] = $slotName;
				$knownReq['I'] = \ze\ray::value(\ze::$slotContents, $slotName, 'instance_id');
	
	
				//Look for this slot on the page, and check for any Nested Plugins in child-slots
				$slots = [];
				$len = strlen($slotName) + 1;
				foreach (\ze::$slotContents as $slotNameNestId => &$instance) {
					if ($slotNameNestId == $slotName || substr($slotNameNestId, 0, $len) == $slotName. '-') {
						$slots[$slotNameNestId] = true;
					}
				}
	
				//Loop through this slot and any child slots, coming up with the rules as to when we can and can't cache a Plugin
				//For nests with child slots, we should combine the rules
				$canCache = true;
				foreach ($slots as $slotNameNestId => &$vars) {
					if (!empty(\ze::$slotContents[$slotNameNestId]['disallow_caching'])) {
						$canCache = false;
						break;
							
					} elseif (isset(\ze::$slotContents[$slotNameNestId]['cache_if'])) {
						if (empty(\ze::$slotContents[$slotNameNestId]['cache_if']['a'])) {
							$canCache = false;
							break;
						} else {
							foreach ($saveEnv as $if => $set) {
								if (empty(\ze::$slotContents[$slotNameNestId]['cache_if'][$if])) {
									if ($if == 'a' || !empty(\ze::$cacheEnv[$if])) {
										$canCache = false;
										break 2;
	
									} else {
										$saveEnv[$if] = '';
									}
								}
							}
						}
							
					} else {
						$canCache = false;
						break;
					}
				}
	
				if ($canCache) {
					$cacheStatusText = implode('', $saveEnv);
						
					if (\ze\cache::cleanDirs() && ($path = \ze\cache::createDir(zenarioPageCacheDir($knownReq, 'plugin'). $cacheStatusText, 'pages', false))) {						
						
						//Record the slot vars and class vars for this slot, and if this is a nest, any child-slots
						$setFiles = [];
						foreach ($slots as $slotNameNestId => &$vars) {
						
							//Loop through this slot and any child slots, coming up with the rules as to when we should clear the cache
							//For nests with child slots, we should combine the rules
							if (!empty(\ze::$slotContents[$slotNameNestId]['clear_cache_by'])) {
								foreach (\ze::$slotContents[$slotNameNestId]['clear_cache_by'] as $if => $set) {
									if ($set && !isset($setFiles[$if])) {
										$setFiles[$if] = true;
										touch(CMS_ROOT. $path. $if);
										\ze\cache::chmod(CMS_ROOT. $path. $if, 0666);
									}
								}
							}
							
							$temps = ['class' => null, 'found' => null, 'used' => null];
							foreach ($temps as $temp => $dummy) {
								if (isset(\ze::$slotContents[$slotNameNestId][$temp])) {
									$temps[$temp] = \ze::$slotContents[$slotNameNestId][$temp];
								}
								unset(\ze::$slotContents[$slotNameNestId][$temp]);
							}

							$slots[$slotNameNestId] = ['s' => \ze::$slotContents[$slotNameNestId], 'c' => []];

							//Note down any html added to the page head
							if (!empty(self::$pluginPageHeadHTML[$slotNameNestId])) {
								$slots[$slotNameNestId]['h'] = self::$pluginPageHeadHTML[$slotNameNestId];
								unset(self::$pluginPageHeadHTML[$slotNameNestId]);
							}

							if (!empty(\ze::$jsLibs)) {
								$slots[$slotNameNestId]['l'] = \ze::$jsLibs;
							}

							foreach ($temps as $temp => $dummy) {
								if (isset($temps[$temp])) {
									\ze::$slotContents[$slotNameNestId][$temp] = $temps[$temp];
								}
							}
							if (!empty(\ze::$slotContents[$slotNameNestId]['class'])) {
								\ze::$slotContents[$slotNameNestId]['class']->zAPIGetCachableVars($slots[$slotNameNestId]['c']);
							}
						}
						file_put_contents(CMS_ROOT. $path. 'vars', serialize($slots));
						\ze\cache::chmod(CMS_ROOT. $path. 'vars', 0666);
						unset($slots);
	
	
						//If this Plugin is displayed and not hidden, cache its HTML
						$html = '';
						$images = '';
						if ($useOb && !empty(\ze::$slotContents[$slotName]['class']) && !empty(\ze::$slotContents[$slotName]['init'])) {
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
						
						\ze::$slotContents[$slotName]['cache_path'] = $path;
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



	//Formerly "setupNewBaseClassPlugin()"
	public static function setupNewBaseClass($slotName) {
		if (!isset(\ze::$slotContents[$slotName])) {
			\ze::$slotContents[$slotName] = [];
		}
	
		if (!isset(\ze::$slotContents[$slotName]['class']) || empty(\ze::$slotContents[$slotName]['class'])) {
			\ze::$slotContents[$slotName]['class'] = new \ze\moduleBaseClass;
			\ze::$slotContents[$slotName]['class']->setInstance(
				[\ze::$cID, \ze::$cType, \ze::$cVersion, $slotName, false, false, false, false, false, false, false, false, false]);
		}
	}

	//Formerly "showPluginError()"
	public static function showError($slotName) {
		echo \ze\ray::value(\ze::$slotContents, $slotName, 'error') ?: \ze\admin::phrase('[Empty Slot]');
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