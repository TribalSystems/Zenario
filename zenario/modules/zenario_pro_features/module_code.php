<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

class zenario_pro_features extends zenario_common_features {
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId);
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
	
	
	
	//The Module Methods from the zenario_common_features class need to be overridden even if there is not extra functionality
	//in this case because we are inheriting from zenario_common_features instead of zenario_base_module
	//and because of that these functions if not declared here will end up calling zenario_common_features twice.
	
	public function showFile() {
		
		//...your PHP code...//
	}

	public function showImage() {
		
		//...your PHP code...//
	}
	
	
	public function fillAllAdminSlotControls(
		&$controls,
		$cID, $cType, $cVersion,
		$slotName, $containerId,
		$level, $moduleId, $instanceId, $isVersionControlled
	) {
		//...your PHP code...//
	}
	
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		//...your PHP code...//
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		//...your PHP code...//
	}
	
	public function lineStorekeeperCSV($path, &$columns, $refinerName, $refinerId) {
		
		//...your PHP code...//
	}
	
	public function formatStorekeeperCSV($path, &$item, $refinerName, $refinerId) {
		
		//...your PHP code...//
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
		//...your PHP code...//
	}
	
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
	
	
	
	
	
	
	
	public static function loadPluginInstance(
			&$slotContents, $slotName,
			$cID, $cType, $cVersion,
			$layoutId, $templateFamily, $templateFileBaseName,
			$specificInstanceId, $specificSlotName, $ajaxReload,
			$runPlugins, $overrideSettings = false, $overrideFrameworkAndCSS = false
	) {
	
		if (!($_REQUEST['method_call'] ?? false)
		&& isset($GLOBALS['chToLoadStatus']) && isset($GLOBALS['chAllRequests']) && isset($GLOBALS['chKnownRequests'])
		&& ze::setting('caching_enabled') && ze::setting('cache_plugins')) {
	
			//Work out what cache-flags to use:
				//u = extranet user logged in
				//g = GET request present that is not registered using registerGetRequest() and is not a CMS variable
				//p = POST request present
				//s = SESSION variable present that is not in the exception list
				//c = COOKIE present that is not in the exception list
			//We can work all of these out exactly except for "g", as registerGetRequest() lets module developers register
			//anything dynamically. There's a bit of logic later that handles this by checking both cases.
	
			$chToLoadStatus = $GLOBALS['chToLoadStatus'];
			
			
			//Get two checksums from the GET requests.
			//$chDirAllRequests is a checksum of every GET request
			//$chDirKnownRequests is a checksum of just the CMS variable, e.g. cID, cType...
			$chAllRequests = $GLOBALS['chAllRequests'];
			$chKnownRequests = $GLOBALS['chKnownRequests'];
				
			$chAllRequests['S'] = $slotName;
			$chAllRequests['I'] = ze::$slotContents[$slotName]['instance_id'];
			$chKnownRequests['S'] = $slotName;
			$chKnownRequests['I'] = ze::$slotContents[$slotName]['instance_id'];
				
				
			$chDirAllRequests = pageCacheDir($chAllRequests, 'plugin');
			$chDirKnownRequests = pageCacheDir($chKnownRequests, 'plugin');
				
			//Loop through every possible combination of cache-flag
			//(I've tried to order this by the most common settings first,
			//to minimise the number of loops when we have a hit.)
			for ($chS = 's';; $chS = $chToLoadStatus['s']) {
				for ($chC = 'c';; $chC = $chToLoadStatus['c']) {
					for ($chP = 'p';; $chP = $chToLoadStatus['p']) {
						for ($chG = 'g';; $chG = $chToLoadStatus['g']) {
							for ($chU = 'u';; $chU = $chToLoadStatus['u']) {
									
								//Plugins can opt out of caching if there are any unrecognised or
								//unregistered $_GET requests.
								//If this is the case, then we must insist that the $_GET requests
								//of the cached page match the current $_GET request - i.e. we
								//must use $chDirAllRequests.
								//If this is not the case then we must check both $chDirAllRequests
								//and $chDirKnownRequests as we weren't exactly sure of the value of "g"
								//in index.pre_load.php.
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
													ze\cache::deleteDir($chPath);
												
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
													$slotContents[$slotNameNestId]['class'] = new zenario_cached_plugin;
													$slotContents[$slotNameNestId]['class']->filePath = $chPath. 'plugin.html';
													
													if (isset($vars['h'])) {
														$slotContents[$slotNameNestId]['class']->pageHead = $vars['h'];
														unset($vars['h']);
													}
													if (isset($vars['f'])) {
														$slotContents[$slotNameNestId]['class']->pageFoot = $vars['f'];
														unset($vars['f']);
													}
													if (isset($vars['l'])) {
														foreach ($vars['l'] as $lib => $dummy) {
															ze::requireJsLib($lib);
														}
														unset($vars['l']);
													}
													
													$slotContents[$slotNameNestId]['class']->setInstanceVariables([
														ze::$cID, ze::$cType, ze::$cVersion, $slotName,
														($slotContents[$slotNameNestId]['instance_name'] ?? false), $slotContents[$slotNameNestId]['instance_id'],
														$slotContents[$slotNameNestId]['class_name'], $slotContents[$slotNameNestId]['vlp_class'],
														$slotContents[$slotNameNestId]['module_id'],
														$slotContents[$slotNameNestId]['default_framework'], $slotContents[$slotNameNestId]['framework'],
														$slotContents[$slotNameNestId]['css_class'],
														($slotContents[$slotNameNestId]['level'] ?? false), !empty($slotContents[$slotNameNestId]['content_id'])
													]);
	
													ze::$slotContents[$slotNameNestId]['class']->zAPISetCachableVars($vars['c']);
													
													if (isset(ze::$slotContents[$slotNameNestId]['page_title'])) {
														ze::$pageTitle = ze::$slotContents[$slotNameNestId]['page_title'];
													}
													if (isset(ze::$slotContents[$slotNameNestId]['page_desc'])) {
														ze::$pageDesc = ze::$slotContents[$slotNameNestId]['page_desc'];
													}
													if (isset(ze::$slotContents[$slotNameNestId]['page_image'])) {
														ze::$pageImage = ze::$slotContents[$slotNameNestId]['page_image'];
													}
													if (isset(ze::$slotContents[$slotNameNestId]['page_keywords'])) {
														ze::$pageKeywords = ze::$slotContents[$slotNameNestId]['page_keywords'];
													}
													if (isset(ze::$slotContents[$slotNameNestId]['page_og_type'])) {
														ze::$pageOGType = ze::$slotContents[$slotNameNestId]['page_og_type'];
													}
													if (isset(ze::$slotContents[$slotNameNestId]['menu_title'])) {
														ze::$menuTitle = ze::$slotContents[$slotNameNestId]['menu_title'];
													}
													
												} else {
													$slotContents[$slotNameNestId]['init'] =
													$slotContents[$slotNameNestId]['class'] = false;
												}
	
												$slotContents[$slotNameNestId]['served_from_cache'] = true;
												ze::$cachingInUse = true;
											}
											
											unset($vars);
										}
	
										return;
									}
								}
									
								if ($chU == $chToLoadStatus['u']) break;
							}
							if ($chG == $chToLoadStatus['g']) break;
						}
						if ($chP == $chToLoadStatus['p']) break;
					}
					if ($chC == $chToLoadStatus['c']) break;
				}
				if ($chS == $chToLoadStatus['s']) break;
			}
		}
		
		
		zenario_common_features::loadPluginInstance(
			$slotContents, $slotName,
			$cID, $cType, $cVersion,
			$layoutId, $templateFamily, $templateFileBaseName,
			$specificInstanceId, $specificSlotName, $ajaxReload,
			$runPlugins, $overrideSettings, $overrideFrameworkAndCSS);
	
	
		//If a Plugin refused to show itself, cache this refusal as well
		if (empty($slotContents[$slotName]['init'])) {
			zenario_pro_features::postSlot($slotName, 'showSlot', $useOb = false);
		}
	}
	
	
	
	public function pagSmart($currentPage, &$pages, &$html) {
		$this->pageNumbers($currentPage, $pages, $html, 'Smart', $showNextPrev = false, $showFirstLast = false, $alwaysShowNextPrev = false);
	}
	
	public function pagSmartWithNPIfNeeded($currentPage, &$pages, &$html, $links = [], $extraAttributes = []) {
		$this->pageNumbers($currentPage, $pages, $html, 'Smart', $showNextPrev = true, $showFirstLast = false, $alwaysShowNextPrev = false, $links, $extraAttributes);
	}
	
	public function pagSmartWithNP($currentPage, &$pages, &$html, $links = [], $extraAttributes = []) {
		$this->pageNumbers($currentPage, $pages, $html, 'Smart', $showNextPrev = true, $showFirstLast = false, $alwaysShowNextPrev = true, $links, $extraAttributes);
	}
	
	protected function smartPageNumbers($currentPos, $count, $showFirstLast, &$pagesPos, &$pages, &$html, $currentPage, $prevPage, $nextPage, $links = [], $extraAttributes = []) {
		//Have a set list of positions that will be displayed, if there
		$positions1 = [
				-999999,
				-100000, -70000, -40000, -20000,
				-10000, -7000, -4000, -2000,
				-1000, -700, -400, -200,
				-100, -70, -40, -20,
				-10, -7, -4, -2,
				-1, 0,
				1, 2, 4, 7,
				10, 20, 40, 70,
				100, 200, 400, 700,
				1000, 2000, 4000, 7000,
				10000, 20000, 40000, 70000,
				100000,
				999999
			];
		$positions2 = [];
		
		//Check if each is there, and include it if so
		foreach ($positions1 as $rel) {
			//Check if the set position is out of range, and replace it with the first/last page in range if needed
			$pos = $currentPos + $rel;
			if ($pos < 0) {
				if ($showFirstLast) {
					continue;
				}
				$pos = 0;
			} elseif ($pos >= $count) {
				if ($showFirstLast) {
					continue;
				}
				$pos = $count-1;
			} else {
				//Otherwise if the numbers are in range then round numbers, depending on how far away they are from the current page
				foreach ([100000, 10000, 1000, 100, 10] as $round) {
					if ($rel < -$round || $round < $rel) {
						$pos = $pos - ($currentPos % $round) - 1;
						break;
					}
				}
				
				if ($pos < 0) {
					$pos = 0;
				} elseif ($pos >= $count) {
					$pos = $count-1;
				}
			}
			
			$positions2[$pos] = true;
		}
		
		foreach ($positions2 as $pos => $dummy) {
			$page = $pagesPos[$pos];
			$html .= $this->drawPageLink($page, $pages[$page], $page, $currentPage, $prevPage, $nextPage, $css = 'pag_page', $links, $extraAttributes);
		}
	}
	
	
	
	
	
	
	
	
	//
	//	Admin functions
	//
	
	
	
	
	function handleAJAX() {
		
		if ($_POST['getBottomLeftInfo'] ?? false) {
		
			$compressed = ze::setting('compress_web_pages')? ze\admin::phrase('Compressed') : ze\admin::phrase('Not Compressed');
		
			if (ze::setting('caching_enabled')
			&& ze::setting('cache_css_js_wrappers')
			&& ze::setting('css_wrappers')
			&& (ze::setting('css_wrappers') == 'on' || ze::setting('css_wrappers') == 'visitors_only')) {
				echo '1';
			}
			
			
			$wrappers = ze\admin::phrase('On');
			switch (ze::setting('css_wrappers')) {
				case 'visitors_only':
					$wrappers = ze\admin::phrase('On for visitors only');
				case 'on':
					$wrappers .= ', ';
					$wrappers .= $compressed;
					$wrappers .= ', ';
					$wrappers .= ze::setting('caching_enabled') && ze::setting('cache_css_js_wrappers')?
									ze\admin::phrase('Cached') : ze\admin::phrase('Not Cached');
					break;
				
				default:
					$wrappers = ze\admin::phrase('Off');
			}
									
		
			echo
			'~',
			'<h3>',
				ze\admin::phrase('Optimisation'),
			'</h3>',
			'<p>',
				ze\admin::phrase('Web Pages:'),
				' ',
				$compressed,
				', ',
				ze::setting('caching_enabled') && ze::setting('cache_web_pages')? ze\admin::phrase('Cached') : ze\admin::phrase('Not Cached'),
			'</p>',
			'<p>',
				ze\admin::phrase('Plugins:'),
				' ',
				$compressed,
				', ',
				ze::setting('caching_enabled') && ze::setting('cache_plugins')? ze\admin::phrase('Cached') : ze\admin::phrase('Not Cached'),
			'</p>',
			'<p>',
				ze\admin::phrase('AJAX and RSS:'),
				' ',
				$compressed,
				', ',
				ze::setting('caching_enabled') && ze::setting('cache_ajax')? ze\admin::phrase('Cached') : ze\admin::phrase('Not Cached'),
			'</p>',
			'<p>',
				ze\admin::phrase('CSS File Wrappers:'),
				' ',
				$wrappers,
			'</p>',
			'<p>',
				ze\admin::phrase('Other Files:'),
				' ',
				'is_htaccess_working',
			'</p>',
			'<p>',
				ze\admin::phrase('Cookie-free Domain:'),
				' ',
				ze::setting('use_cookie_free_domain') && ze::setting('cookie_free_domain')?
				htmlspecialchars('http://'. ze::setting('cookie_free_domain'). SUBDIRECTORY)
				:	ze\admin::phrase('Not Used'),
			'</p>',
			'~';
		
			//Get the current server time
			if (ze\server::isWindows() || !ze\server::execEnabled()) {
				echo date('H~i~s');
		
			} else {
				echo trim(exec('date +"%H~%M~%S"'));
		
				//Check if the scheduled task manager is running
				if (!ze\module::inc('zenario_scheduled_task_manager')) {
					echo '~~', ze\admin::phrase('The Scheduled Tasks Manager is not installed.');
					return;
		
				} elseif (!zenario_scheduled_task_manager::checkScheduledTaskRunning($jobName = false, $checkPulse = false)) {
					echo '~jobs_not_running~', ze\admin::phrase('The Scheduled Tasks Manager is installed, but the master switch is not enabled.');
		
				} elseif (!zenario_scheduled_task_manager::checkScheduledTaskRunning($jobName = false, $checkPulse = true)) {
					echo '~jobs_not_running~', ze\admin::phrase('The Scheduled Tasks Manager is installed, but not correctly configured in your crontab');
		
				} else {
					echo '~jobs_running~', ze\admin::phrase('The Scheduled Tasks Manager is running');
				}
		
				if (ze\priv::check('_PRIV_VIEW_SCHEDULED_TASK')) {
					echo '~zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks';
				}
			}
		
		}
		
		zenario_common_features::handleAJAX();
	}
	
	
	
	
	
	var $categoryHierarchyOutput = "";
	var $categoryChildren = [];
	var $categoryAncestors = [];
	
	
	public function categoryHasChild ($id) {
		$sql = "SELECT id
				FROM " . DB_NAME_PREFIX . "categories
				WHERE parent_id = " . (int) $id;
	
		$result = ze\sql::select($sql);
	
		if (ze\sql::numRows($result)>0) {
			return true;
		} else {
			return false;
		}
	}
	
	public function getCategoryChildren ($id, $recurseCount = 0) {
		$recurseCount++;
	
		$sql = "SELECT id
				FROM " . DB_NAME_PREFIX . "categories
				WHERE parent_id = " . (int) $id;
	
		$result = ze\sql::select($sql);
	
		if (ze\sql::numRows($result)>0) {
			while ($row = ze\sql::fetchArray($result)) {
				$this->categoryChildren[] = $row['id'];
	
				if ($recurseCount<=10) {
					$this->getCategoryChildren($row['id'],$recurseCount);
				}
			}
		}
	}
	
	
	
	
	
	
	
	
	
	
	// Functionality for Page/Plugin Caching, and User Sync
	
	
	//protected static $debug = '';
	//protected static $debug2 = '';
	protected static $clearCacheBy = [];
	protected static $clearTags = [];
	protected static $clearCacheOnShutdownRegistered = false;
	protected static $syncUsersOnShutdownRegistered = false;
	protected static $localDB = false;
	
	private static $seenUserSyncSites = [];
	private static $userSyncSiteConfigSiteIsValid;
	
	private static $pluginPageHeadHTML = [];
	private static $pluginPageFootHTML = [];
	
	public static function preSlot($slotName, $showPlaceholderMethod, $useOb = true) {
		if (ze::$canCache
		&& !($_REQUEST['method_call'] ?? false)
		&& isset($GLOBALS['chToLoadStatus']) && isset($GLOBALS['chAllRequests']) && isset($GLOBALS['chKnownRequests'])
		&& ze::setting('caching_enabled') && ze::setting('cache_plugins')
		&& empty(ze::$slotContents[$slotName]['served_from_cache'])) {
				
			if ($showPlaceholderMethod == 'addToPageHead') {
				if ($useOb) ob_start();
					
			} elseif ($showPlaceholderMethod == 'addToPageFoot') {
				if ($useOb) ob_start();
					
			} elseif ($showPlaceholderMethod == 'showSlot') {
				if ($useOb) ob_start();
			}
		}
	}
	
	public static function postSlot($slotName, $showPlaceholderMethod, $useOb = true) {
		if (ze::$canCache
		&& !($_REQUEST['method_call'] ?? false)
		&& isset($GLOBALS['chToLoadStatus']) && isset($GLOBALS['chAllRequests']) && isset($GLOBALS['chKnownRequests'])
		&& ze::setting('caching_enabled') && ze::setting('cache_plugins')
		&& empty(ze::$slotContents[$slotName]['served_from_cache'])) {
				
			if ($showPlaceholderMethod == 'addToPageHead') {
				//Note down any html added to the page head
				if ($useOb) {
					zenario_pro_features::$pluginPageHeadHTML[$slotName] = ob_get_flush();
				}
					
			} elseif ($showPlaceholderMethod == 'addToPageFoot') {
				//Note down any html added to the page foot
				if ($useOb) {
					zenario_pro_features::$pluginPageFootHTML[$slotName] = ob_get_flush();
				}
					
			} elseif ($showPlaceholderMethod == 'showSlot') {
	
				$chToLoadStatus = $GLOBALS['chToLoadStatus'];
				$chToSaveStatus = $GLOBALS['chToSaveStatus'];
				$chKnownRequests = $GLOBALS['chKnownRequests'];
	
				$chKnownRequests['S'] = $slotName;
				$chKnownRequests['I'] = ze\ray::value(ze::$slotContents, $slotName, 'instance_id');
	
	
				//Look for this slot on the page, and check for any Nested Plugins in child-slots
				$slots = [];
				$len = strlen($slotName) + 1;
				foreach (ze::$slotContents as $slotNameNestId => &$instance) {
					if ($slotNameNestId == $slotName || substr($slotNameNestId, 0, $len) == $slotName. '-') {
						$slots[$slotNameNestId] = true;
					}
				}
	
				//Loop through this slot and any child slots, coming up with the rules as to when we can and can't cache a Plugin
				//For nests with child slots, we should combine the rules
				$canCache = true;
				foreach ($slots as $slotNameNestId => &$vars) {
					if (!empty(ze::$slotContents[$slotNameNestId]['disallow_caching'])) {
						$canCache = false;
						break;
							
					} elseif (isset(ze::$slotContents[$slotNameNestId]['cache_if'])) {
						if (empty(ze::$slotContents[$slotNameNestId]['cache_if']['a'])) {
							$canCache = false;
							break;
						} else {
							foreach ($chToSaveStatus as $if => $set) {
								if (empty(ze::$slotContents[$slotNameNestId]['cache_if'][$if])) {
									if ($if == 'a' || !empty($chToLoadStatus[$if])) {
										$canCache = false;
										break 2;
	
									} else {
										$chToSaveStatus[$if] = '';
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
					$cacheStatusText = implode('', $chToSaveStatus);
						
					if (ze\cache::cleanDirs() && ($path = ze\cache::createDir(pageCacheDir($chKnownRequests, 'plugin'). $cacheStatusText, 'pages', false))) {						
						
						//Record the slot vars and class vars for this slot, and if this is a nest, any child-slots
						$setFiles = [];
						foreach ($slots as $slotNameNestId => &$vars) {
						
							//Loop through this slot and any child slots, coming up with the rules as to when we should clear the cache
							//For nests with child slots, we should combine the rules
							if (!empty(ze::$slotContents[$slotNameNestId]['clear_cache_by'])) {
								foreach (ze::$slotContents[$slotNameNestId]['clear_cache_by'] as $if => $set) {
									if ($set && !isset($setFiles[$if])) {
										$setFiles[$if] = true;
										touch(CMS_ROOT. $path. $if);
										@chmod(CMS_ROOT. $path. $if, 0666);
									}
								}
							}
							
							$temps = ['class' => null, 'found' => null, 'used' => null];
							foreach ($temps as $temp => $dummy) {
								if (isset(ze::$slotContents[$slotNameNestId][$temp])) {
									$temps[$temp] = ze::$slotContents[$slotNameNestId][$temp];
								}
								unset(ze::$slotContents[$slotNameNestId][$temp]);
							}

							$slots[$slotNameNestId] = ['s' => ze::$slotContents[$slotNameNestId], 'c' => []];

							//Note down any html added to the page head
							if (!empty(zenario_pro_features::$pluginPageHeadHTML[$slotNameNestId])) {
								$slots[$slotNameNestId]['h'] = zenario_pro_features::$pluginPageHeadHTML[$slotNameNestId];
								unset(zenario_pro_features::$pluginPageHeadHTML[$slotNameNestId]);
							}

							if (!empty(zenario_pro_features::$pluginPageFootHTML[$slotNameNestId])) {
								$slots[$slotNameNestId]['f'] = zenario_pro_features::$pluginPageFootHTML[$slotNameNestId];
								unset(zenario_pro_features::$pluginPageFootHTML[$slotNameNestId]);
							}

							if (!empty(ze::$jsLibs)) {
								$slots[$slotNameNestId]['l'] = ze::$jsLibs;
							}

							foreach ($temps as $temp => $dummy) {
								if (isset($temps[$temp])) {
									ze::$slotContents[$slotNameNestId][$temp] = $temps[$temp];
								}
							}
							if (!empty(ze::$slotContents[$slotNameNestId]['class'])) {
								ze::$slotContents[$slotNameNestId]['class']->zAPIGetCachableVars($slots[$slotNameNestId]['c']);
							}
						}
						file_put_contents(CMS_ROOT. $path. 'vars', serialize($slots));
						@chmod(CMS_ROOT. $path. 'vars', 0666);
						unset($slots);
	
	
						//If this Plugin is displayed and not hidden, cache its HTML
						$html = '';
						$images = '';
						if ($useOb && !empty(ze::$slotContents[$slotName]['class']) && !empty(ze::$slotContents[$slotName]['init'])) {
							$html = ob_get_contents();
								
							//Note down any images from the cache directory that are in the page
							foreach(preg_split('@cache/(\w+)(/[\w~_,-]+/)@', $html, -1,  PREG_SPLIT_DELIM_CAPTURE) as $i => $dir) {
								switch ($i % 3) {
									case 1:
										$type = $dir;
										break;
											
									case 2:
										if (ze::in($type, 'images', 'files', 'downloads')) {
											$images .= 'cache/'. $type. $dir. "\n";
										}
								}
							}
						}
	
	
						file_put_contents(CMS_ROOT. $path. 'plugin.html', $html);
						file_put_contents(CMS_ROOT. $path. 'tag_id', ze::$cType. '_'. ze::$cID);
						@chmod(CMS_ROOT. $path. 'plugin.html', 0666);
						@chmod(CMS_ROOT. $path. 'tag_id', 0666);
	
						if ($images) {
							file_put_contents(CMS_ROOT. $path. 'cached_files', $images);
							@chmod(CMS_ROOT. $path. 'cached_files', 0666);
						}
					}
				}
	
				if ($useOb) ob_end_flush();
			}
		}
	}
	
	
	
	
	//Wrapper function for clearCacheForContentItem2() that adds a different database connection
	public static function clearCacheForContentItem($cID, $cType, $cVersion = false, $force = false, $clearEquivs = false) {
		
		//This function needs to run SQL queries, but doing that during/after another SQL query
		//would messi with the affected rows/insert id.
		//To avoid this, create a new connection.
		if (!zenario_pro_features::$localDB) {
			zenario_pro_features::$localDB = ze\db::connect(DBHOST, DBNAME, DBUSER, DBPASS, DBPORT);
		}
		ze::$lastDB = zenario_pro_features::$localDB;
		ze::$lastDBHost = DBHOST;
		ze::$lastDBName = DBNAME;
		ze::$lastDBPrefix = DB_NAME_PREFIX;
			
			//Run the checks for clearing the cache
			zenario_pro_features::clearCacheForContentItem2($cID, $cType, $cVersion, $force, $clearEquivs);
		
		//Connect back to the local database when done
		ze\db::connectLocal();
	}
	
	public static function clearCacheForContentItem2($cID, $cType, $cVersion, $force, $clearEquivs) {
		//Clear the cache for a specific Content Item
		if ($cID && $cType) {
			
			if ($clearEquivs) {
				foreach (ze\content::equivalences($cID, $cType) as $equiv) {
					zenario_pro_features::clearCacheForContentItem2($equiv['id'], $equiv['type'], false, $force, false);
				}
			
			} else {
				//If we've got exact information on the Content Item, clear the cache intelligently
				//(Note that if $cVersion was not set, this will check if any version of this Content Item is published)
				if ($force || ze\content::isPublished($cID, $cType, $cVersion)) {
					zenario_pro_features::$clearCacheBy['content'] = true;
					zenario_pro_features::$clearTags[$cType. '_'. $cID] = true;
					
					//Clear the Menu as well if there is a Menu Node linked to this Content Item
					$equivId = ze\content::equivId($cID, $cType);
					if (ze\row::exists('menu_nodes', ['target_loc' => 'int', 'equiv_id' => $equivId, 'content_type' => $cType])) {
						zenario_pro_features::$clearCacheBy['menu'] = true;
					}
				
					//if ($force)
						//zenario_pro_features::$debug2 .= "\nclearing ". $cType. '_'. $cID. ", forced\n";
					//else
						//zenario_pro_features::$debug2 .= "\nclearing ". $cType. '_'. $cID. "\n";
				}
			}
		}
	}
	
	//Attempt to check which table or tables are being changed, and clear the page cache accordingly.
	public static function reviewDatabaseQueryForChanges(&$sql, &$ids, &$values, $table = false, $runSql = false) {
		
		
		//For some queries, I'd like to run the cache logic before the rows are changed;
		//e.g. if a row is deleted then it's too late to see what was there afterwards.
		//However if there is no change in state after the query is run, I don't want the cache to change!
		//Note that setting $runSql to true should cause this function to return the results of a ze\sql::affectedRows() call.
		if ($runSql) {
			//If the $runSql flag is set, check the cache, then try the update, and revert back to the old values if nothing happened
			//$debug = zenario_pro_features::$debug;
			//$debug2 = zenario_pro_features::$debug2;
			$clearCacheBy = zenario_pro_features::$clearCacheBy;
			$clearTags = zenario_pro_features::$clearTags;
			
			zenario_pro_features::reviewDatabaseQueryForChanges($sql, $ids, $values, $table);
			
			ze\sql::update($sql, false, false);
			$affectedRows = ze\sql::affectedRows();
			
			if ($affectedRows == 0) {
				//zenario_pro_features::$debug = $debug;
				//zenario_pro_features::$debug2 = $debug2;
				zenario_pro_features::$clearCacheBy = $clearCacheBy;
				zenario_pro_features::$clearTags = $clearTags;
			}
			
			return $affectedRows;
		}
		
		//Check if we need to check the cache.
		//(Note that if we've already declared that we're wiping everything in the cache, then there's no need to keep checking it.)
		$checkCache = ze::setting('caching_enabled') && empty(zenario_pro_features::$clearCacheBy['all']);
		
		//If there's nothing we need to do, stop here.
		if (!$checkCache) {
			return;
		}
		
		//If this is a flat SQL statement, attempt to read the table name from it.
		//Alas we can't be sure of the ids, so the clearing of the cache may be more destructive than if we knew them
		if (!$table && $sql) {
			//Tables that are being changed must be listed before certain keywords in SQL, so there's no need to search the entire
			//SQL query, just the bit of the query before these words
			$matches = [];
			if (preg_match('/\b(LIMIT|ORDER|SELECT|SET|VALUE|VALUES|WHERE)\b/i', $sql, $matches, PREG_OFFSET_CAPTURE)) {
				$test = substr($sql, 0, $matches[0][1]);
			} else {
				$test = $sql;
			}
			
			//Loop through any words in the SQL query that start with the DB_NAME_PREFIX
			$matches = [];
			if (preg_match_all('/\b'. preg_quote(DB_NAME_PREFIX). '(\w+)\b/', $test, $matches)) {
				if (!empty($matches[1])) {
					
					foreach ($matches[1] as $table) {
						if ($table) {
							//Call this function with table name to continue to the logic below
							//Unfortunately we have no array of keys though, so we can't clear the cache for specific Content Items
							zenario_pro_features::reviewDatabaseQueryForChanges($sql, $ids, $values, $table);
						}
					}
				}
			}
			
			return;
		}
		
		//If we still couldn't find a table name, then there's nothing else we can do
		if (!$table) {
			return;
		}
		
		
		
		//Clear the cache according to the table that is being updated
		//Possibly we'll have an array of keys as well, which will help clear the cache more specifically for changes to Content Items
		if ($checkCache) {
			//zenario_pro_features::$debug .= ' '. $table;
			if (!empty($ids)) {
				//zenario_pro_features::$debug2 .= "\n\n". $table. print_r($ids, true);
			} else {
				//zenario_pro_features::$debug2 .= "\n\n". $table. "\n". $sql;
			}
			if (substr($table, 0, 3) == 'mod' && ($moduleId = (int) preg_replace('/mod(\d*)_.*/', '\1', $table))) {
				//Module table
				zenario_pro_features::$clearCacheBy['module'] = true;
				
			} else {
				switch ($table) {
					
					//Admin tables; ignore these as they don't effect the output
					case 'action_admin_link':
					case 'admins':
					case 'admin_actions':
					case 'admin_roles':
					case 'admin_organizer_prefs':
					
					//Tables for other types of cache; again ignore these
					case 'content_cache':
					case 'plugin_instance_store':
					
					//These tables are all used in Admin Mode, but not really used to display anything to Visitors; ignore these as well
					case 'document_types':
					case 'email_templates':
					case 'inline_images':
					case 'jobs':
					case 'job_logs':
					case 'local_revision_numbers':
					case 'menu_hierarchy':
					case 'menu_positions':
					case 'modules':
					case 'module_dependencies':
					case 'plugin_setting_defs':
					case 'signals':
					case 'skins':
					case 'spare_domain_names':
					case 'spare_aliases':
					case 'template_slot_link':
					case 'custom_datasets':
					case 'custom_dataset_tabs':
					case 'user_content_accesslog':
					
					//Anything that relies on group-membership or private items should never be cached, so we can ignore these tables too
					case 'group_link':
					case 'translation_chain_privacy':
						return;
					
					//File
					case 'files':
						zenario_pro_features::$clearCacheBy['file'] = true;
						continue;
					
					//Documents
					case 'documents':
						//If a document id changed, clear anything that links to a file
						zenario_pro_features::$clearCacheBy['file'] = true;
						//If we ever implement code snippets instead of links to documents, we will need
						//to clear the contents of WYSIWYG Editors as well
						//zenario_pro_features::$clearCacheBy['content'] = true;
						continue;
					
					//Menu
					case 'menu_nodes':
					case 'menu_sections':
					case 'menu_text':
						zenario_pro_features::$clearCacheBy['menu'] = true;
						continue;
					
					//User
					case 'custom_dataset_values_link':
					case 'groups':
					case 'users':
						zenario_pro_features::$clearCacheBy['user'] = true;
						continue;
					
					//These tables relate to Content, and should clear anything that ties into Content
					case 'categories':
						zenario_pro_features::$clearCacheBy['content'] = true;
						continue;
					
					
					//These tables can relate to specific Content Items
						//If this is a Content Item that is not published, don't clear anything.
						//If this is a published Content Item, clear the cache for that Content Item and and anything that ties into Content.
						//If this is not related to a Content Item, or we can't resolve which Content Item they link to, clear the entire cache
					case 'category_item_link':
						if (!empty($ids['equiv_id']) && !empty($ids['content_type'])
						 && !is_array($ids['equiv_id']) && !is_array($ids['content_type'])) {
							//If we've got exact information on the Content Item, clear the cache intelligently
							zenario_pro_features::clearCacheForContentItem($ids['equiv_id'], $ids['content_type'], false, false, $clearEquivs = true);
							zenario_pro_features::$clearCacheBy['content'] = true;
						
						} else {
							//Otherwise clear the whole cache
							zenario_pro_features::$clearCacheBy['all'] = true;
							//zenario_pro_features::$debug2 .= "\nclear all\n";
						}
						continue;
						
					case 'content_items':
						if (!empty($ids['id']) && !empty($ids['type'])
						 && !is_array($ids['id']) && !is_array($ids['type'])) {
							//If we've got exact information on the Content Item, clear the cache intelligently
							if ((isset($ids['status']) && $status = $ids['status'])
							 || (isset($values['status']) && $status = $values['status'])) {
								//Special case: if we are changing the status of a Content Item, there's no need to look the status up
								if (ze::in($status, 'published', 'hidden', 'trashed')) {
									//The live version is being changed to published, hidden, trashed
									zenario_pro_features::clearCacheForContentItem($ids['id'], $ids['type'], false, true);
								} else {
									//a draft is being created or deleted; no need to do anything with the cache
								}
							
							} else {
								zenario_pro_features::clearCacheForContentItem($ids['id'], $ids['type']);
							}
						
						} else {
							//Otherwise clear the whole cache
							zenario_pro_features::$clearCacheBy['all'] = true;
							//zenario_pro_features::$debug2 .= "\nclear all\n";
						}
						continue;
					
					case 'content_item_versions':
						if (!empty($ids['id']) && !empty($ids['type']) && !empty($ids['version'])
						 && !is_array($ids['id']) && !is_array($ids['type']) && !is_array($ids['version'])) {
							//If we've got exact information on the Content Item, clear the cache intelligently
							zenario_pro_features::clearCacheForContentItem($ids['id'], $ids['type'], $ids['version']);
						
						} else {
							//Otherwise clear the whole cache
							zenario_pro_features::$clearCacheBy['all'] = true;
							//zenario_pro_features::$debug2 .= "\nclear all\n";
						}
						continue;
					
					case 'nested_plugins':
					case 'plugin_settings':
						if (ze::in($table, 'nested_plugins', 'plugin_settings')) {
							//If we can get the instance id we'll continue into the logic for the plugin_instances table
							
							//Grab the instance id if it is in the array
							if (!empty($ids['instance_id'])
							 && !is_array($ids['instance_id'])) {
								$table = 'plugin_instances';
								$ids = ['id' => $ids['instance_id']];
							
							//Attempt to look up an instance id from a nested Plugin
							} else
							if ($table == 'plugin_settings'
							 && !empty($ids['id'])
							 && !is_array($ids['id'])) {
								$result = ze\sql::select("SELECT instance_id FROM ". DB_NAME_PREFIX. $table. " WHERE id = ". (int) $ids['id']);
								
								if ($row = ze\sql::fetchAssoc($result)) {
									$table = 'plugin_instances';
									$ids = ['id' => $row['instance_id']];
								
								} else {
									//If we couldn't find this setting/nested Plugin, then it may already have been deleted.
									//In this case there's no need to clear the cache again
									continue;
								}
							
							} else {
								//Otherwise don't use the logic for another table, and clear the whole cache instead
								zenario_pro_features::$clearCacheBy['all'] = true;
								//zenario_pro_features::$debug2 .= "\nclear all\n";
								continue;
							}
						}
						
					case 'plugin_instances':
					case 'plugin_item_link':
						//zenario_pro_features::$debug2 .= "\n=>\n". $table. print_r($ids, true);
						
						//If we have an instance or link id, but no idea of what Content Item this is, try to look this up from the instances table
						if ((!isset($ids['content_id']) || !isset($ids['content_type']) || !isset($ids['content_version']))
						 && !empty($ids['id'])
						 && !is_array($ids['id'])) {
							$result = ze\sql::select("
								SELECT id, content_id, content_type, content_version
								FROM ". DB_NAME_PREFIX. ($table == 'plugin_item_link'? 'plugin_item_link' : 'plugin_instances'). "
								WHERE id = ". (int) $ids['id']);
							
							if (!$ids = ze\sql::fetchAssoc($result)) {
								//If we couldn't find this setting/nested Plugin, then it may already have been deleted.
								//In this case there's no need to clear the cache again
								continue;
							}
						}
						
						if (!empty($ids['content_id']) && !empty($ids['content_type']) && !empty($ids['content_version'])
						 && !is_array($ids['content_id']) && !is_array($ids['content_type']) && !is_array($ids['content_version'])) {
							//If we've got exact information on the Content Item, clear the cache intelligently
							zenario_pro_features::clearCacheForContentItem($ids['content_id'], $ids['content_type'], $ids['content_version']);
						
						} else {
							//Otherwise clear the whole cache
							zenario_pro_features::$clearCacheBy['all'] = true;
							//zenario_pro_features::$debug2 .= "\nclear all\n";
						}
						
						continue;
					
					
					//Completely empty the cache if a Visitor Phrase changes
					case 'visitor_phrases':
					
					//Completely empty the cache if something changes on the Layout Layer
					case 'plugin_layout_link':
					case 'layouts':
					case 'template_families':
					
					//Completely clear the cache if any of these change, as there's no better way to handle things
					case 'content_types':
					case 'custom_dataset_fields':
					case 'languages':
					case 'site_settings':
					case 'special_pages':
					
					//Also clear the cache for anything we don't recognise
					default:
						zenario_pro_features::$clearCacheBy['all'] = true;
						//zenario_pro_features::$debug2 .= "\nclear all\n";
				}
			}
			
			if (!zenario_pro_features::$clearCacheOnShutdownRegistered && (!empty(zenario_pro_features::$clearCacheBy) || !empty(zenario_pro_features::$clearTags))) {
				register_shutdown_function(['zenario_pro_features', 'clearCacheOnShutdown']);
				zenario_pro_features::$clearCacheOnShutdownRegistered = true;
			}
		}
	}
	
	public static function clearCacheOnShutdown($clearAll = false) {
		
		if ($clearAll) {
			zenario_pro_features::$clearCacheBy['all'] = true;
		}
		
		//Loop through the page-cache directory
		if (is_dir(CMS_ROOT. 'cache/pages/')) {
			if ($dh = opendir(CMS_ROOT. 'cache/pages/')) {
				while (($file = readdir($dh)) !== false) {
					if (substr($file, 0, 1) != '.') {
						$dir = CMS_ROOT. 'cache/pages/'. $file. '/';
						
						//Remove any directory that is marked to be cleared by one of the types of thing that we are clearing by
						if (!$rmDir = !empty(zenario_pro_features::$clearCacheBy['all'])) {
							foreach (zenario_pro_features::$clearCacheBy as $clearBy => $notEmpty) {
								if ($clearBy != 'all' && file_exists($dir. $clearBy)) {
									$rmDir = true;
									break;
								}
							}
							
							if (!$rmDir) {
								//Remove any directory that is for a Content Item that we are clearing by
								if (file_exists($dir. 'tag_id')
								 && ($id = file_get_contents($dir. 'tag_id'))
								 && (!empty(zenario_pro_features::$clearTags[$id]))) {
									$rmDir = true;
								}
							}
						}
						
						if ($rmDir) {
							ze\cache::deleteDir($dir);
						}
					}
				}
				closedir($dh);
			}
		}
		
	}
	
	
	
	public static function eventContentDeleted($cID, $cType, $cVersion) {
		if (!ze\row::exists('content_item_versions', ['id' => $cID, 'type' => $cType])) {
			ze\row::delete('spare_aliases', ['content_id' => $cID, 'content_type' => $cType]);
		}
	}
	
	public static function eventContentTrashed($cID, $cType) {
		ze\row::delete('spare_aliases', ['content_id' => $cID, 'content_type' => $cType]);
	}
	
}




//This class defines a "fake" slotable Plugin, which when used will read HTML from the cache cache directory
class zenario_cached_plugin extends ze\moduleBaseClass {

	public $filePath = '';
	public $pageHead = '';
	public $pageFoot = '';

	public function showSlot() {
		readfile($this->filePath);
	}

	public function addToPageHead() {
		echo $this->pageHead;
	}

	public function addToPageFoot() {
		echo $this->pageFoot;
	}
}