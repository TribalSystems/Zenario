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

if (ze::$canCache 
 && ze::$pluginsOnPage > 1
 && isset(ze::$cacheEnv)
 && ze::setting('caching_enabled') && ze::setting('cache_web_pages')
 && (!($pageType = ze\content::isSpecialPage(ze::$cID, ze::$cType))
   || ($pageType != 'zenario_not_found' && $pageType != 'zenario_no_access'))) {
	
	$pluginsFromCache = $pluginsCached = $pluginsTotal = 0;
	$canCache = $allowPageCaching = true;
	
	if ($userAccessLogged = $canCache && ze::$userAccessLogged) {
		$canCache = false;
	}
	
	foreach (ze::$slotContents as $slotName => &$slot) {
		
		if ($slot->instanceId() && $slot->class()) {
			$canCacheThis = true;
			
			if ($slot->disallowCaching()) {
				$allowPageCaching = false;
				$canCacheThis = $canCache = false;
			
			} else {
				$cacheIf = $slot->cacheIf();
				
				if (empty($cacheIf['a'])) {
					$allowPageCaching = false;
					$canCacheThis = $canCache = false;
				
				} else {
					foreach (ze::$saveEnv as $if => $set) {
						if (empty($cacheIf[$if])) {
							if (!empty(ze::$cacheEnv[$if])) {
								$canCacheThis = $canCache = false;
						
							} else {
								ze::$saveEnv[$if] = '';
							}
						}
					}
				}
			}
			
			if ($slot->servedFromCache()) {
				++$pluginsFromCache;
			} elseif ($canCacheThis) {
				++$pluginsCached;
			}
			++$pluginsTotal;
		}
	}
	
	if (ze::$locationDependant) {
		$canCache = false;
		ze::$cacheEnv['l'] = true;
	}
	
	if ($caching_debug_info = ze::setting('caching_debug_info')) {
		$limit_caching_debug_info_by_ip = ze::setting('limit_caching_debug_info_by_ip');
		
		$chSlots = [];
		foreach (ze::$slotContents as $slotName => &$slot) {
			if ($slot->instanceId() && $slot->class()) {
				$chSlots[$slotName] = [];
				
				$chSlots[$slotName]['cache_if'] = $slot->cacheIf();
				$chSlots[$slotName]['clear_cache_by'] = $slot->cacheClearBy();
				$chSlots[$slotName]['disallow_caching'] = $slot->disallowCaching();
				$chSlots[$slotName]['served_from_cache'] = $slot->servedFromCache();
			}
		}
		
		if ($userAccessLogged) {
			$chSlots['__misc__'] = $chSlots[$slotName];
			$chSlots['__misc__']['cache_if']['u'] = false;
		}
		
		ze\cache::writeDebugInfo($chSlots);
	}
	
	
	if ($canCache) {
		$clearCacheBy = [];
		foreach (ze::$slotContents as $slotName => &$slot) {
			$slotCacheClearBy = $slot->cacheClearBy();
			if (!empty($slotCacheClearBy)) {
				foreach ($slotCacheClearBy as $if => $set) {
					if ($set) {
						$clearCacheBy[$if] = true;
					}
				}
			}
		}
		
		$cacheStatusText = implode('', ze::$saveEnv);
		
		if (ze\cache::cleanDirs() && ($path = ze\cache::createDir(zenarioPageCacheDir(ze::$knownReq). $cacheStatusText, 'cache/pages', false))) {
			foreach ($clearCacheBy as $if => $set) {
				touch(CMS_ROOT. $path. $if);
				ze\cache::chmod(CMS_ROOT. $path. $if, 0666);
			}
			
			
			$html = str_replace('<body class="desktop no_js '. ze\cache::browserBodyClass(), '<body class="desktop no_js [[%browser%]] ', ob_get_contents());
			
			
			//Note down any images from the cache directory that are in the page
			$images = '';
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
			unset($i);
			unset($type);
			unset($dir);
			
			
			//Put a marker on the page to note that it came from the cache
			if ($caching_debug_info) {
				if ($limit_caching_debug_info_by_ip) {
					file_put_contents(CMS_ROOT. $path. 'show_cache_info', $limit_caching_debug_info_by_ip);
				} else {
					touch(CMS_ROOT. $path. 'show_cache_info');
				}
				ze\cache::chmod(CMS_ROOT. $path. 'show_cache_info', 0666);
			}
			
			file_put_contents(CMS_ROOT. $path. 'tag_id', ze::$cType. '_'. ze::$cID);
			file_put_contents(CMS_ROOT. $path. 'cached_files', $images);
			file_put_contents(CMS_ROOT. $path. 'page.html', $html);
			ze\cache::chmod(CMS_ROOT. $path. 'tag_id', 0666);
			ze\cache::chmod(CMS_ROOT. $path. 'cached_files', 0666);
			ze\cache::chmod(CMS_ROOT. $path. 'page.html', 0666);
			
			//When using implied consent, write a flag if the $_SESSION['cookies_accepted'] variable would have just been set.
			if (ze::setting('cookie_require_consent') == 'implied' && empty($_COOKIE['cookies_accepted'])) {
				file_put_contents(CMS_ROOT. $path. 'consent_implied', $images);
				ze\cache::chmod(CMS_ROOT. $path. 'consent_implied', 0666);
			}
			
			zenarioPageCacheLogStats(['writes', 'total']);
		} else {
			$canCache = false;
		}
	}
	
	if (!$canCache) {
		if ($pluginsFromCache) {
			zenarioPageCacheLogStats(['partial_hits', 'total']);
		} elseif ($pluginsCached) {
			zenarioPageCacheLogStats(['partial_writes', 'total']);
		} else {
			zenarioPageCacheLogStats(['misses', 'total']);
		}
	}
	
	if ($caching_debug_info && ze\cache::shouldSeeDebugInfo($limit_caching_debug_info_by_ip)) {
		ze\cache::showDebugInfo(false, $allowPageCaching, $canCache);
	}
}