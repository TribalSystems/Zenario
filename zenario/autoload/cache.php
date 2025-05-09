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

namespace ze;

class cache {

	public static function start() {
	
		//As of Zenario 7.2, we now rely on people enabling compression in their php.ini or .htaccess files
		//The only purpose of this function is now to trigger ob_start() when page caching is enabled.
	
		//If caching is enabled, call ob_start() to start output buffering if it was not already done above.
		if (empty(\ze::$siteConfig) || \ze::setting('caching_enabled')) {
			ob_start();
		}
	}
	
	//Get rid of any all caching of the output
	public static function end() {
		$l = (int) ob_get_level();
		
		while ($l-- > 0) {
			ob_end_flush();
		}
	}

	public static function browserBodyClass() {
		$c = '';
		$a = ($_SERVER['HTTP_USER_AGENT'] ?? '');
	
		if (strpos($a, 'Edge/')) {
			$c = 'edge';
	
		} elseif (strpos($a, 'WebKit/')) {
			$c = 'webkit';
		
			if (strpos($a, 'OPR/')) {
				$c .= ' opera';
			} elseif (strpos($a, 'Chrome/')) {
				$c .= ' chrome';
			} elseif (strpos($a, 'iPhone')) {
				$c .= ' ios iphone';
			} elseif (strpos($a, 'iPad')) {
				$c .= ' ios ipad';
			} elseif (strpos($a, 'Safari/')) {
				$c .= ' safari no_webp';
			}
	
		} elseif (strpos($a, 'Firefox/')) {
			$c = 'ff';
	
		} elseif (strpos($a, 'Opera/')) {
			$c = 'opera';
		}
	
		return $c;
	}


	//Check for the dreaded Internet Explorer.
	public static function browserIsIE() {
		//I'm running a call to browserBodyClass() first, and if this is a known browser
		//then I'll wave it through. I'm writing the code this was to try and avoid any
		//false positives.
		$c = \ze\cache::browserBodyClass();
		$a = ($_SERVER['HTTP_USER_AGENT'] ?? '');
		
		//If this browser looks like Internet Explorer, reject it.
		return $c === '' && (strpos($a, 'MSIE ') || strpos($a, 'Trident/'));
	}


	public static function createDir($dir, $type = 'private/downloads', $onlyForCurrentVisitor = true, $ip = -1) {
	
		switch ($type) {
			//Migrate some old formats of the $type input
			case 'frameworks':
			case 'pages':
			case 'stats':
			case 'tuix':
				$path = 'cache/'. $type. '/';
				break;
		
			case 'downloads':
			case 'uploads':
			case 'images':
			case 'files':
				$path = 'private/'. $type. '/';
				break;
		
			default:
				$path = $type. '/';
		}
	
		$fullPath = CMS_ROOT. $path;
		
		//Catch the case where a sysadmin as deleted a subdirectory.
		//Just try to restore that directory
		if (!is_dir($fullPath) && !file_exists($fullPath)) {
			\ze::ignoreErrors();
				if (@mkdir($fullPath, 0777)) {
					@chmod($fullPath, 0777);
				}
			\ze::noteErrors();
		}
		
		if (!is_dir($fullPath) || !is_writable($fullPath)) {
			return false;
	
		} else {
			$path .= $dir. '/';
			$fullPath .= $dir. '/';
			
			\ze::ignoreErrors();
				if (is_dir($fullPath)) {
					@touch($fullPath. 'accessed');
		
				} else {
					if (@mkdir($fullPath, 0777)) {
						@chmod($fullPath, 0777);
			
						if ($onlyForCurrentVisitor) {
							\ze\cache::htaccessFileForCurrentVisitor($fullPath, $ip);
						}
			
						touch($fullPath. 'accessed');
						@chmod($fullPath. 'accessed', 0666);
					} else {
						$path = false;
					}
				}
			\ze::noteErrors();
			return $path;
		}
	}
	
	//Clear any files out of a directory made by the createDir() function above, but keep
	//the actual directory.
	public static function tidyDir($dir, $type) {
		
		$fullPath = CMS_ROOT. $type. '/'. $dir. '/';
		
		foreach (scandir($fullPath) as $file) {
			if ($file != '.' && $file != '..' && $file != 'accessed' && is_file($fullPath. $file)) {
				@unlink($fullPath. $file);
			}
		}
	}


	public static function chmod($filename, $mode = 0666) {
		$return = false;
		\ze::ignoreErrors();
			if ((fileperms($filename) & 0777) !== $mode) {
				$return = @chmod($filename, $mode);
			}
		\ze::noteErrors();
		return $return;
	}



	public static function createRandomDir($length, $type = 'private/downloads', $onlyForCurrentVisitor = true, $ip = -1, $prefix = '') {
		return \ze\cache::createDir($prefix. \ze\ring::random($length), $type, $onlyForCurrentVisitor, $ip);
	}
	

	public static function htaccessFileForCurrentVisitor($path, $ip = -1) {
		if ($ip === -1) {
			$ip = \ze\cache::visitorIP();
		}
	
		if (!$ip) {
			$file  = "deny from all\n";
	
		} elseif (defined('USE_FORWARDED_IP') && constant('USE_FORWARDED_IP')) {
			$file  = 'RewriteEngine on'. "\n";
			$file .= 'RewriteCond %{HTTP:X-Forwarded-For} !^'. str_replace(',', '\\,', str_replace(' ', '\\ ', preg_quote($ip))). '$'. "\n";
			$file .= 'RewriteRule . - [F,NC]'. "\n";
	
		} else {
			$file  = "deny from all\n";
			$file .= "allow from ". $ip. "\n";
		}
	
		$file .= "RemoveType .php\n";
	
		if (file_put_contents($path. '/.htaccess', $file)) {
			\ze\cache::chmod($path. '/.htaccess', 0666);
			return true;
		} else {
			return false;
		}
	}

	public static function deleteDir($dir, $subDirLimit = 0) { 
	
		$allGone = true;
	
		if (!is_dir($dir)
		 || !is_writable($dir)) { 
			return false;
		}
	
		\ze::ignoreErrors();
			foreach (scandir($dir) as $file) { 
				if ($file == '.'
				 || $file == '..') {
					continue;
		
				} else
				if (is_file($dir. '/'. $file)) {
					$allGone = @unlink($dir. '/'. $file) && $allGone;
		
				} else
				if ($subDirLimit > 0
				 && is_dir($dir. '/'. $file)
				 && !is_link($dir. '/'. $file)) {
					$allGone = \ze\cache::deleteDir($dir. '/'. $file, $subDirLimit - 1) && $allGone;
		
				} else {
					$allGone = false;
				}
			}
	
			$return = $allGone && @rmdir($dir);
		\ze::noteErrors();
		
		return $return;
	}
	
	private static $cleanedCacheDir = null;
	
	public static function cleanDirs($forceRun = false) {
		
		//Only allow this function to run at most once per page-load
		if (!$forceRun && self::$cleanedCacheDir !== null) {
			return self::$cleanedCacheDir;
		}
		
		//Check to see if anyone has done a "rm -rf" on the images directory
		//If so ignore the throttling rule, don't wait for the scheduled task, and run now.
		$forceRun = $forceRun
			|| !is_dir(CMS_ROOT. 'public/images')
			|| !is_dir(CMS_ROOT. 'private/images')
			|| !is_dir(CMS_ROOT. 'cache/frameworks');
		
		$time = time();
		
		//Check if this function was last run within the last 60 minutes
		if (!$forceRun) {
			$lifetime = 60 * 60;
			if (file_exists($accessed = 'cache/stats/clean_downloads/accessed')) {
				$timeM = filemtime($accessed);
		
				if ($timeM > $time - $lifetime) {
					//If it was run in the last 60 minutes, don't run it again now...
					return self::$cleanedCacheDir = true;
				}
			}
		}
		
		//Call call the \ze\cache::createDir() function to create/touch the cache/stats/clean_downloads/accessed file,
		//so we know that we last ran \ze\cache::cleanDirs() at this current time.
		\ze\cache::createDir('clean_downloads', 'cache/stats', true, false);
		
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}


	//Attempt to use caching for a page, to avoid sending something a client already has cached
	public static function useBrowserCache($ETag = false, $maxAge = false) {
	
		if (!empty($_REQUEST['no_cache'])) {
			$maxAge = 0;
	
		//Set a time-out of about a year
		} elseif ($maxAge === false) {
			$maxAge = 60*60*24*30*12;
		}
	
		session_cache_limiter(false);
		header('Cache-Control: maxage='. $maxAge);
		header('Expires: '. gmdate('D, d M Y H:i:s', time() + $maxAge). ' GMT');
	
		if (empty($_REQUEST['no_cache']) && $ETag) {
			//Set an ETag to identify this library
			header('ETag: "'. $ETag. '"');
		
			if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $ETag) {
				header('HTTP/1.1 304 Not Modified');
				exit;
			}
		}
	}
	
	
	//Functions for outputting microtemplates
	public static function esctick($text) {
		return str_replace(['`', '~'], ['`t', '`s'], $text);
	}
	
	
	//Returns the IP address of the current visitor.
	//I'm defining it here instead of in the user library so we can call it without the autoloader
	//having to load the user library when we want to serve pages from the cache.
	public static function visitorIP() {
		if (defined('USE_FORWARDED_IP')
		 && constant('USE_FORWARDED_IP')
		 && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	
		} elseif (!empty($_SERVER['REMOTE_ADDR'])) {
			$ip = $_SERVER['REMOTE_ADDR'];
	
		} else {
			return false;
		}
	
		$ip = explode(',', $ip, 2);
		return $ip[0];
	}
	
	
	public static function shouldSeeDebugInfo($limit_caching_debug_info_by_ip) {
		return !$limit_caching_debug_info_by_ip || $limit_caching_debug_info_by_ip == \ze\cache::visitorIP();
	}
	
	public static function writeDebugInfo($chSlots) {
		echo '
			<script type="text/javascript">
				window.zenarioCD = {slots: ', json_encode($chSlots), '};
			</script>';
	}
	
	
	public static function showDebugInfo($fromCache, $allowPageCaching = true, $canCache = true) {
		
		
		$phrase = 'Click to see caching information for this page.';
		
		$v = '1';
		$stylesheet = SUBDIRECTORY. 'zenario/styles/cache_info.min.css?v='. $v;
		$script = SUBDIRECTORY. 'zenario/js/cache_info.min.js?v='. $v;
		
		$onclick = 'zenarioCI.init('. (int) $allowPageCaching. ');';
		
		//Add a check to load the JS library the first time the debug info is clicked
		$onclick = '
			if (window.zenario) {
				if (!window.zenarioCI) {
					$.getScript(\''. htmlspecialchars($script). '\', function() {'. $onclick. '});
				} else {
					'. $onclick. '
				}
			}';
		
		if ($fromCache) {
			$class = 'zenario_from_cache';
		
		} elseif (!$allowPageCaching) {
			$class = 'zenario_cache_disabled';
		
		} elseif (!$canCache) {
			$class = 'zenario_not_cached';
		
		} else {
			$class = 'zenario_cache_in_use';
		}

		echo '
			<link rel="stylesheet" type="text/css" media="screen" href="', htmlspecialchars($stylesheet), '"/>
			<x-zenario-cache-info id="zenario_cache_info" class="zenario_cache_info">
				<x-zenario-cache-info class="', $class, '" title="', $phrase, '" onclick="', $onclick, '"></x-zenario-cache-info>
			</x-zenario-cache-info>
			<script type="text/javascript">
				window.zenarioCD.load = ', json_encode(\ze::$cacheEnv), ';';
		
		if ($fromCache) {
			echo '
				window.zenarioCD.served_from_cache = true;';
		}
		
		echo '
			</script>';
	}
	
}