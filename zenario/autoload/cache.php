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

namespace ze;

class cache {

	//Formerly "useGZIP()"
	public static function start() {
	
		//As of Zenario 7.2, we now rely on people enabling compression in their php.ini or .htaccess files
		//The only purpose of this function is now to trigger ob_start() when page caching is enabled.
	
		//If caching is enabled, call ob_start() to start output buffering if it was not already done above.
		if (empty(\ze::$siteConfig) || \ze::setting('caching_enabled')) {
			ob_start();
		}
	}

	//Formerly "browserBodyClass()"
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
				$c .= ' safari';
			}
	
		} elseif (strpos($a, 'Firefox/')) {
			$c = 'ff';
	
		} elseif (strpos($a, 'Opera/')) {
			$c = 'opera';
	
		} elseif (strpos($a, 'MSIE ')) {
			$c = 'ie';
			for ($i = 10; $i > 5; --$i) {
				if (strpos($a, 'MSIE '. $i) !== false) {
					$c .= ' ie'. $i;
					break;
				}
			}
	
		} elseif (strpos($a, 'Trident/')) {
			$c = 'ie ie11';
		}
	
		return $c;
	}


	//Formerly "createCacheDir()"
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
	
		if (!is_dir($fullPath) || !is_writable($fullPath)) {
			return false;
	
		} else {
			$path .= $dir. '/';
			$fullPath .= $dir. '/';
		
			if (is_dir($fullPath)) {
				@touch($fullPath. 'accessed');
				return $path;
		
			} else {
				if (@mkdir($fullPath, 0777)) {
					@chmod($fullPath, 0777);
			
					if ($onlyForCurrentVisitor) {
						\ze\cache::htaccessFileForCurrentVisitor($fullPath, $ip);
					}
			
					touch($fullPath. 'accessed');
					@chmod($fullPath. 'accessed', 0666);
					return $path;
				} else {
					return false;
				}
			}
		}
	}



	//Formerly "createRandomDir()"
	public static function createRandomDir($length, $type = 'private/downloads', $onlyForCurrentVisitor = true, $ip = -1, $prefix = '') {
		return \ze\cache::createDir($prefix. \ze\ring::random($length), $type, $onlyForCurrentVisitor, $ip);
	}
	

	//Formerly "htaccessFileForCurrentVisitor()"
	public static function htaccessFileForCurrentVisitor($path, $ip = -1) {
		if ($ip === -1) {
			$ip = \ze\user::ip();
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
			@chmod($path. '/.htaccess', 0666);
			return true;
		} else {
			return false;
		}
	}

	//Formerly "deleteCacheDir()"
	public static function deleteDir($dir, $subDirLimit = 0) { 
	
		$allGone = true;
	
		if (!is_dir($dir)
		 || !is_writable($dir)) { 
			return false;
		}
	
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
	
		return $allGone && @rmdir($dir);
	}
	
	private static $cleanedCacheDir = null;
	
	//Formerly "cleanCacheDir()"
	public static function cleanDirs() {
		//Only allow this function to run at most once per page-load
		if (self::$cleanedCacheDir !== null) {
			return self::$cleanedCacheDir;
	
		} else {
			$time = time();
		
			//Check to see if anyone has done a "rm -rf" on the images directory
			//If so skip the "every 5 minutes rule" and run now.
			if (is_dir(CMS_ROOT. 'public/images')
			 && is_dir(CMS_ROOT. 'private/images')
			 && is_dir(CMS_ROOT. 'cache/frameworks')) {
			
				//Check if this function was last run within the last 30 minutes
				$lifetime = 30 * 60;
				if (file_exists($accessed = 'cache/stats/clean_downloads/accessed')) {
					$timeA = fileatime($accessed);
					$timeM = filemtime($accessed);
			
					if (!$timeA || $timeA < $timeM) {
						$timeA = $timeM;
					}
			
					if ($timeA > $time - $lifetime) {
						//If it was run in the last 30 minutes, don't run it again now...
						return self::$cleanedCacheDir = true;
					}
				}
			}
		
			//...otherwise, continue running \ze\cache::cleanDirs(), and call the \ze\cache::createDir() function to create/touch
			//the cache/stats/clean_downloads/accessed file so we know that we last ran \ze\cache::cleanDirs() at this current time
			\ze\cache::createDir('clean_downloads', 'stats', true, false);
		
			return require \ze::funIncPath(__FILE__, __FUNCTION__);
		}
	}


	//Formerly "incCSS()"
	public static function incCSS($file) {
		$file = CMS_ROOT. $file;
		if (file_exists($file. '.min.css')) {
			require $file. '.min.css';
		} elseif (file_exists($file. '.css')) {
			require $file. '.css';
		}
	
		echo "\n/**/\n";
	}

	//Formerly "incJS()"
	public static function incJS($file, $wrapWrappers = false) {
		echo "\n";
		$file = CMS_ROOT. $file;
		if ($wrapWrappers && file_exists($file. '.js.php')) {
			chdir(dirname($file));
			require $file. '.js.php';
			chdir(CMS_ROOT);
	
		} elseif (file_exists($file. '.pack.js')) {
			require $file. '.pack.js';
		} elseif (file_exists($file. '.min.js')) {
			require $file. '.min.js';
		} elseif (file_exists($file. '.js')) {
			require $file. '.js';
		} else {
			return;
		}
		echo "/**/";
	}


	//Attempt to use caching for a page, to avoid sending something a client already has cached
	//Formerly "useCache()"
	public static function useBrowserCache($ETag = false, $maxAge = false) {
	
		if (!empty($_REQUEST['no_cache'])) {
			$maxAge = 0;
	
		//Set a time-out of about a month
		} elseif ($maxAge === false) {
			$maxAge = 60*60*24*28;
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
}