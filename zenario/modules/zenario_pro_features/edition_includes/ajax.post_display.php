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


if (ze::$canCache 
 && isset($chToLoadStatus)
 && ze::setting('caching_enabled') && ze::setting('cache_ajax')
 && ze::in($_REQUEST['method_call'] ?? false, 'refreshPlugin', 'showFloatingBox', 'showRSS', 'showSlot')) {
	
	$chToSaveStatus = array();
	$chToSaveStatus['u'] = 'u';
	$chToSaveStatus['g'] = 'g';
	$chToSaveStatus['p'] = 'p';
	$chToSaveStatus['s'] = 's';
	$chToSaveStatus['c'] = 'c';
	
	foreach ($_GET as $request => &$value) {
		if (!ze::in($request, 'cID', 'cType', 'visLang', 'cVersion', 'slotName', 'instanceId', 'method_call')) {
			if (isset(ze::$importantGetRequests[$request])) {
				$chKnownRequests[$request] = $value;
			
			} else {
				$chToLoadStatus['g'] = 'g';
			}
		}
	}
	
	
	if ($canCache = !ze::$locationDependant) {
		foreach (ze::$slotContents as $slotName => &$instance) {
			if (!empty($instance['disallow_caching'])) {
				$canCache = false;
				break;
			
			} elseif (isset($instance['cache_if'])) {
				foreach ($chToSaveStatus as $if => $set) {
					if (empty($instance['cache_if'][$if])) {
						if ($if == 'a' || !empty($chToLoadStatus[$if])) {
							$canCache = false;
							break 2;
						
						} else {
							$chToSaveStatus[$if] = '';
						}
					}
				}
			}
		}
	}
	
	if ($canCache) {
		$clearCacheBy = array();
		foreach (ze::$slotContents as $slotName => &$instance) {
			if (!empty($instance['clear_cache_by'])) {
				foreach ($instance['clear_cache_by'] as $if => $set) {
					if ($set) {
						$clearCacheBy[$if] = true;
					}
				}
			}
		}
		
		$cacheStatusText = implode('', $chToSaveStatus);
		
		if (ze\cache::cleanDirs() && ($path = ze\cache::createDir(pageCacheDir($chKnownRequests). $cacheStatusText, 'pages', false))) {
			foreach ($clearCacheBy as $if => $set) {
				touch(CMS_ROOT. $path. $if);
				@chmod(CMS_ROOT. $path. $if, 0666);
			}
			
			
			$html = ob_get_contents();
			
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
			
			
			file_put_contents(CMS_ROOT. $path. 'tag_id', ze::$cType. '_'. ze::$cID);
			file_put_contents(CMS_ROOT. $path. 'cached_files', $images);
			file_put_contents(CMS_ROOT. $path. 'plugin.html', $html);
			@chmod(CMS_ROOT. $path. 'tag_id', 0666);
			@chmod(CMS_ROOT. $path. 'cached_files', 0666);
			@chmod(CMS_ROOT. $path. 'plugin.html', 0666);
		}
	}
}