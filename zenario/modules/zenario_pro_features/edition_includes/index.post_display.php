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

if (cms_core::$canCache 
 && cms_core::$pluginsOnPage > 1
 && isset($chToLoadStatus)
 && setting('caching_enabled') && setting('cache_web_pages')
 && (!($pageType = isSpecialPage(cms_core::$cID, cms_core::$cType))
   || ($pageType != 'zenario_not_found' && $pageType != 'zenario_no_access'))) {
	
	$pluginsFromCache = $pluginsCached = $pluginsTotal = 0;
	$canCache = $allowPageCaching = true;
	
	if ($userAccessLogged = $canCache && cms_core::$userAccessLogged) {
		$canCache = false;
	}
	
	foreach (cms_core::$slotContents as $slotName => &$instance) {
		
		if (!empty($instance['instance_id']) && !empty($instance['class'])) {
			$canCacheThis = true;
			
			if (!empty($instance['disallow_caching'])) {
				$allowPageCaching = false;
				$canCacheThis = $canCache = false;
			
			} elseif (isset($instance['cache_if'])) {
				if (empty($instance['cache_if']['a'])) {
					$allowPageCaching = false;
					$canCacheThis = $canCache = false;
				} else {
					foreach ($chToSaveStatus as $if => $set) {
						if (empty($instance['cache_if'][$if])) {
							if (!empty($chToLoadStatus[$if])) {
								$canCacheThis = $canCache = false;
						
							} else {
								$chToSaveStatus[$if] = '';
							}
						}
					}
				}
			}
			
			if (!empty($instance['served_from_cache'])) {
				++$pluginsFromCache;
			} elseif ($canCacheThis) {
				++$pluginsCached;
			}
			++$pluginsTotal;
		}
	}
	
	if (cms_core::$locationDependant) {
		$canCache = false;
		$chToLoadStatus['l'] = true;
	}
	
	$caching_debug_info = setting('caching_debug_info');
	if ($caching_debug_info && setting('limit_caching_debug_info_by_ip')) {
		$caching_debug_info = (setting('limit_caching_debug_info_by_ip') == visitorIP());
	}
	
	if ($caching_debug_info) {
		$chSlots = array();
		foreach (cms_core::$slotContents as $slotName => &$instance) {
			if (!empty($instance['instance_id']) && !empty($instance['class'])) {
				$chSlots[$slotName] = array();
				foreach (array('cache_if', 'clear_cache_by', 'disallow_caching', 'served_from_cache') as $detail) {
					if (isset($instance[$detail])) {
						$chSlots[$slotName][$detail] = $instance[$detail];
					}
				}
			}
		}
		
		if ($userAccessLogged) {
			$chSlots['__misc__'] = $chSlots[$slotName];
			$chSlots['__misc__']['cache_if']['u'] = false;
		}
		
		$css = 'zenario_cache_in_use';
		if (!$allowPageCaching) {
			$css = 'zenario_cache_disabled';
		
		} elseif (!$canCache) {
			$css = 'zenario_not_cached';
		}
		
		echo '
			<link rel="stylesheet" type="text/css" media="screen" href="', moduleDir('zenario_pro_features', 'adminstyles/cache_info.css'), '?v=', setting('css_js_version'), '"/>
			<div id="zenario_cache_info" class="zenario_cache_info"><div class="', $css, '" title="', adminPhrase('Click to see caching information for this page.'), '" onclick="
				if (window.zenario) {
					if (!window.zenarioCI) {
						$.getScript(\'', moduleDir('zenario_pro_features', 'js/cache_info.min.js'), '?v=', setting('css_js_version'), '\', function() {zenarioCI.init(', (int) $allowPageCaching, '); });
					} else {
						zenarioCI.init(', (int) $allowPageCaching, ');
					}
				}
			"></div></div>
			<script type="text/javascript">
				window.zenarioCD = {load:', json_encode($chToLoadStatus), ', slots: ', json_encode($chSlots), ', cache_plugins: ', (int) setting('cache_plugins'), '};
				zenario.tooltips(\'#zenario_cache_info div\');
			</script>';
	}
	
	
	if ($canCache) {
		$clearCacheBy = array();
		foreach (cms_core::$slotContents as $slotName => &$instance) {
			if (!empty($instance['clear_cache_by'])) {
				foreach ($instance['clear_cache_by'] as $if => $set) {
					if ($set) {
						$clearCacheBy[$if] = true;
					}
				}
			}
		}
		
		$cacheStatusText = implode('', $chToSaveStatus);
		
		if (cleanDownloads() && ($path = createCacheDir(pageCacheDir($chKnownRequests). $cacheStatusText, 'pages', false))) {
			foreach ($clearCacheBy as $if => $set) {
				touch(CMS_ROOT. $path. $if);
				chmod(CMS_ROOT. $path. $if, 0666);
			}
			
			
			$html = str_replace('<body class="no_js '. browserBodyClass(), '<body class="no_js [[%browser%]] ', ob_get_contents());
			
			
			//Note down any images from the cache directory that are in the page
			$images = '';
			foreach(preg_split('@cache/(\w+)(/[\w~_,-]+/)@', $html, -1,  PREG_SPLIT_DELIM_CAPTURE) as $i => $dir) {
				switch ($i % 3) {
					case 1:
						$type = $dir;
						break;
					
					case 2:
						if (in($type, 'images', 'files', 'downloads')) {
							$images .= 'cache/'. $type. $dir. "\n";
						}
				}
			}
			unset($i);
			unset($type);
			unset($dir);
			
			
			//Put a marker on the page to note that it came from the cache
			if ($caching_debug_info) {
				touch(CMS_ROOT. $path. 'show_cache_info');
				chmod(CMS_ROOT. $path. 'show_cache_info', 0666);
				$html = str_replace('<div class="zenario_cache_in_use"', '<div class="zenario_from_cache"', $html);
			
			} else {
				$html .= "\n</body>\n</html>";
			}
			
			file_put_contents(CMS_ROOT. $path. 'tag_id', cms_core::$cType. '_'. cms_core::$cID);
			file_put_contents(CMS_ROOT. $path. 'cached_files', $images);
			file_put_contents(CMS_ROOT. $path. 'page.html', $html);
			chmod(CMS_ROOT. $path. 'tag_id', 0666);
			chmod(CMS_ROOT. $path. 'cached_files', 0666);
			chmod(CMS_ROOT. $path. 'page.html', 0666);
			
			zenario_page_caching__logStats(array('writes', 'total'));
			return;
		}
	}
	
	
	if ($pluginsFromCache) {
		zenario_page_caching__logStats(array('partial_hits', 'total'));
	} elseif ($pluginsCached) {
		zenario_page_caching__logStats(array('partial_writes', 'total'));
	} else {
		zenario_page_caching__logStats(array('misses', 'total'));
	}
}

require moduleDir('zenario_common_features', 'edition_includes/index.post_display.php', true);