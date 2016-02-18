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


/*
 *  This file can be used as a header file.
 *  If the CMS has been installed it will attempt to load the siteconfig file.
 *  It will include a few functions needed to compress a file or check the client's cache.
 *  
 *  It will not connect to the database.
 *  This header file is designed such that you may later include a different header file if you wish.
 */


//Standard include file Header
if (!defined('NOT_ACCESSED_DIRECTLY')) {
	if (extension_loaded('mbstring')) mb_internal_encoding('UTF-8');
	
	//Attempt to set the working directory to the CMS Root, if it is not already
	//Note that I am using $_SERVER['SCRIPT_FILENAME'] and not __DIR__ to do this
	//because I want to resolve the relative URL when symlinks are used.
	$isFilelimit = 0;
	$dirname = $_SERVER['SCRIPT_FILENAME'];
	
	do {
		if (is_null($dirname) 
		 || ++$isFilelimit > 9) {
			echo
				'Could not resolve where the "zenario/" directory is.', "\n",
				'This could be a problem with a symlink, ',
				'or could be because you are trying to call this file from terminal ',
				'(which it is not designed to do).', "\n";
			exit;
		} else {
			chdir($dirname = dirname($dirname));
		}
	} while (!is_file('zenario/visitorheader.inc.php'));
	
	if (!defined('CMS_ROOT')) {
		define('CMS_ROOT', $dirname. '/');
	}
	
	//Define a constant to mark than any further include files have been legitamately included
	define('THIS_FILE_IS_BEING_DIRECTLY_ACCESSED', false);
	define('NOT_ACCESSED_DIRECTLY', true);
	unset($isFilelimit);
	unset($dirname);
}

if (!is_file(CMS_ROOT. 'zenario/visitorheader.inc.php')) {
	echo 'Your CMS_ROOT value is not correctly set. \''. CMS_ROOT. '\' is not the correct path to the CMS.';
	exit;
}


function arrayKey(&$a, $k) {
	if ( is_array($a) && isset($a[$k])) {
		$result = &$a[$k];
		$count = func_num_args();
		for($i = 2; $i < $count; ++$i){
			if(!is_array($result)) return false;
			$arg = func_get_arg($i);
			if(!isset($result[$arg])) return false;
			$result = &$result[$arg];
		}
		return $result;
	}
	return false;
}

function httpUserAgent() {
	return isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT'] : '';
}

function browserBodyClass() {
	$c = '';
	$a = httpUserAgent();
	
	if (strpos($a, 'MSIE') !== false) {
		$c .= 'ie ';
		for ($i = 11; $i > 5; --$i) {
			if (strpos($a, 'MSIE '. $i) !== false) {
				$c .= 'ie'. $i. ' ';
				break;
			}
		}
	
	} elseif (strpos($a, 'WebKit') !== false) {
		$c .= 'webkit ';
		
		if (strpos($a, 'Chrome') !== false) {
			$c .= 'chrome ';
		} elseif (strpos($a, 'iPhone') !== false) {
			$c .= 'ios iphone ';
		} elseif (strpos($a, 'iPad') !== false) {
			$c .= 'ios ipad ';
		} elseif (strpos($a, 'Safari') !== false) {
			$c .= 'safari ';
		}
	
	} elseif (strpos($a, 'Firefox') !== false) {
		$c .= 'ff ';
	
	} elseif (strpos($a, 'Opera') !== false) {
		$c .= 'opera ';
	}
	
	return substr($c, 0, -1);
}

function engToBoolean($text) {
	if (is_object($text) && get_class($text) == 'zenario_error') {
		return 0;
	} else if (is_array($text)) {
		return 1;
	} else {
		return (int) ($text && filter_var((string) $text, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== false);
	}
}

function funIncPath($filePathOrModuleClassName, $functionName) {
	if (strpos($filePathOrModuleClassName, '/') === false
	 && strpos($filePathOrModuleClassName, '\\') === false) {
		$dir = CMS_ROOT. moduleDir($filePathOrModuleClassName);
	} else {
		$dir = dirname($filePathOrModuleClassName);
	}
	return $dir. '/'. (basename($dir) != 'fun'? 'fun/' : ''). $functionName. '.php';
}


function get($n) {
	return isset($_GET[$n])? $_GET[$n] : false;
}

function hash64($text, $len = 28) {
	return substr(rtrim(strtr(base64_encode(sha1($text, true)), '+/', '-_'), '='), 0, $len);
}

function in($needle) {
	$haystack = func_get_args();
	array_splice($haystack, 0, 1);
	return in_array($needle, $haystack);
}

function isError($object) {
	return is_object($object) && get_class($object) == 'zenario_error';
}

function post($n) {
	return isset($_POST[$n])? $_POST[$n] : false;
}

function request($n) {
	return isset($_REQUEST[$n])? $_REQUEST[$n] : false;
}

function session($n) {
	return isset($_SESSION[$n])? $_SESSION[$n] : false;
}


function incCSS($file) {
	if (file_exists($file. '.min.css')) {
		require $file. '.min.css';
	} elseif (file_exists($file. '.css')) {
		require $file. '.css';
	}
	
	echo "\n/**/\n";
}

function incJS($file, $wrapWrappers = false) {
	
	if ($wrapWrappers && file_exists($file. '.js.php')) {
		chdir(dirname($file));
		require CMS_ROOT. $file. '.js.php';
		chdir(CMS_ROOT);
	
	} elseif (file_exists($file. '.pack.js')) {
		require $file. '.pack.js';
	} elseif (file_exists($file. '.min.js')) {
		require $file. '.min.js';
	} elseif (file_exists($file. '.js')) {
		require $file. '.js';
	}
	
	echo "\n/**/\n";
}


//Return a path to a Module or a Module's sub-directory, given that the Module might be in one of two directories
function moduleDir($moduleName, $subDir = '', $checkExists = false, $checkFrameworks = false, $checkS2O = true) {
	$moduleName = preg_replace('/\W/', '', $moduleName);
	
	if ($subDir !== '') {
		//Catch the case where the tuix/storekeeper subdirectory wasn't yet renamed to tuix/organizer
		if ($checkS2O
		 && ((($len = 14) && (substr($subDir, 0, $len) == 'tuix/organizer'))
		  || (($len = 16) && (substr($subDir, 0, $len) == 'tuix/storekeeper')))) {
			
			//Catch either form of the request, and then run a check for both!
			if ($dir = moduleDir($moduleName, 'tuix/organizer'. substr($subDir, $len), true, $checkFrameworks, false)) {
				return $dir;
			} else {
				return moduleDir($moduleName, 'tuix/storekeeper'. substr($subDir, $len), $checkExists, $checkFrameworks, false);
			}
		}
		
		$find = $return = '/'. $moduleName. '/'. $subDir;
	
	} else {
		$find = '/'. $moduleName. '/module_code.php';
		$return = '/'. $moduleName. '/';
	}
	
	//For frameworks, check zenario_custom/frameworks.
	if ($checkFrameworks && file_exists(CMS_ROOT. 'zenario_custom/frameworks'. $find)) {
		return 'zenario_custom/frameworks'. $return;
	
	//Look for a module in zenario_custom/modules
	} elseif (file_exists(CMS_ROOT. 'zenario_custom/modules'. $find)) {
		return 'zenario_custom/modules'. $return;
	
	//Look for a module in zenario_extra_modules
	} elseif (file_exists(CMS_ROOT. 'zenario_extra_modules'. $find)) {
		return 'zenario_extra_modules'. $return;
	
	//Check the normal location in the zenario/modules directory
	//If the $checkExists flag is not set, be lazy and just assume this is the right answer without checking.
	} elseif (!$checkExists || file_exists(CMS_ROOT. 'zenario/modules'. $find)) {
		return 'zenario/modules'. $return;
	
	//We moved the description.xml and latest_revision_no.inc.php files out of the db_update directory
	//in version 6.1, but add a catch just in case someone has a Module where they've not yet been moved.
	} elseif ($subDir == 'description.xml' || $subDir == 'latest_revision_no.inc.php') {
		return moduleDir($moduleName, 'db_updates/'. $subDir, $checkExists);
	
	} else {
		return false;
	}
}

function moduleDirs($tests = 'module_code.php') {
	$dirs = array();
	
	if (!is_array($tests)) {
		$tests = array($tests);
	}
	
	foreach (array(
		'zenario/modules/',
		'zenario_extra_modules/',
		'zenario_custom/modules/'
	) as $path) {
		if (is_dir($path)) {
			foreach (scandir($path) as $dir) {
				if (substr($dir, 0, 1) != '.') {
					foreach ($tests as $test) {
						if (file_exists(CMS_ROOT. $path. $dir. '/'. $test)) {
							$dirs[$dir] = $path. $dir. '/'. $test;
						}
					}
				}
			}
		}
	}
	
	return $dirs;
}

//Get the value of a site setting from the Core
function setting($settingName) {
	if (isset(cms_core::$siteConfig[$settingName])) {
		return cms_core::$siteConfig[$settingName];
	}
	
	if (cms_core::$lastDB) {
		$sql = "
			SELECT IFNULL(value, default_value)
			FROM ". DB_NAME_PREFIX. "site_settings
			WHERE name = '". sqlEscape($settingName). "'";
		if (($result = @sqlSelect($sql))
		 && ($row = sqlFetchRow($result))) {
			return cms_core::$siteConfig[$settingName] = $row[0];
		} else {
			cms_core::$siteConfig[$settingName] = false;
		}
	}
	
	return false;
}

//Attempt to use caching for a page, to avoid sending something a client already has cached
function useCache($ETag = false, $maxAge = false) {
	
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

//Attempt to enable compression, if it's not already enabled
function useGZIP($useGZIP = true) {
	
	if ($useGZIP
	 	//Note: IE 6 frequently crashes if we try to use compression
	 && strpos(httpUserAgent(), 'MSIE 6') === false
	 	//If there has already been some output (e.g. an error) then that will also cause a problem
	 && !ob_get_length()) {
	
		//In PHP versions 5.3 or earlier, use ob_gzhandler.
		//(We can't use this in later versions of PHP due to a bug introduced in version 5.4)
		if (version_compare(PHP_VERSION, '5.4.0', '<')) {
			ob_start('ob_gzhandler');
			return;
	
		//Otherwise try to use zlib
		} elseif (extension_loaded('zlib') && checkFunctionEnabled('ini_set')) {
			ini_set('zlib.output_compression', 4096);
		}
	}
	
	//If caching is enabled, call ob_start() to start output buffering if it was not already done above.
	if (empty(cms_core::$siteConfig) || setting('caching_enabled')) {
		ob_start();
	}
}

function visitorIP() {
	if (defined('USE_FORWARDED_IP')
	 && constant('USE_FORWARDED_IP')
	 && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	
	} elseif (!empty($_SERVER['REMOTE_ADDR'])) {
		return $_SERVER['REMOTE_ADDR'];
	
	} else {
		return false;
	}
}

function setCookieOnCookieDomain($name, $value, $expire = COOKIE_TIMEOUT) {
	
	if ($expire > 1) {
		$expire += time();
	}
	
	if (COOKIE_DOMAIN) {
		setcookie($name, $value, $expire, SUBDIRECTORY, COOKIE_DOMAIN);
	} else {
		setcookie($name, $value, $expire, SUBDIRECTORY);
	}
	
	$_COOKIE[$name] = $value;
}

function clearCookie($name) {
	setCookieOnCookieDomain($name, '', 1);
	
	//Attempt to fix a bug where any cookies that were set with the wrong domain and/or path
	//will stay still stay on the visitor's browser.
	if (function_exists('httpHostWithoutPort')) {
		setcookie($name, '', 1, '/', '.'. httpHostWithoutPort());
	}
	setcookie($name, '', 1, '/');
	setcookie($name, '', 1);
	
	unset($_COOKIE[$name]);
}

function setCookieConsent() {
	setCookieOnCookieDomain('cookies_accepted', 1);
	unset($_SESSION['cookies_rejected']);
}

function setCookieNoConsent() {
	if (isset($_COOKIE['cookies_accepted'])) {
		clearCookie('cookies_accepted');
	}
	$_SESSION['cookies_rejected'] = true;
}

function deleteCacheDir($dir, $subDirLimit = 0) { 
	
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
			$allGone = deleteCacheDir($dir. '/'. $file, $subDirLimit - 1) && $allGone;
		
		} else {
			$allGone = false;
		}
	}
	
	return $allGone && @rmdir($dir);
}

//Check whether we are allowed to call exec()
function execEnabled() {
	
	if (is_null(cms_core::$execEnabled)) {
		cms_core::$execEnabled = checkFunctionEnabled('exec');
	}
	
	return cms_core::$execEnabled;
}

function checkFunctionEnabled($name, $checkSafeMode = true) {
	try {
		return @is_callable($name)
			&& !($checkSafeMode && version_compare(PHP_VERSION, '5.4.0', '<') && ini_get('safe_mode'))
			&& !(($disable_functions = ini_get('disable_functions')) && (preg_match('/\b'. $name. '\b/i', $disable_functions) !== 0));
	} catch (Exception $e) {
		return false;
	}
}

function zenarioSessionName() {
	return 'PHPSESSID'. (COOKIE_DOMAIN? '-'. preg_replace('@\W@', '_', COOKIE_DOMAIN) : '');
}

function startSession() {
	if (!isset($_SESSION)) {
		session_name(zenarioSessionName());
		
		if (COOKIE_DOMAIN) {
			session_set_cookie_params(SESSION_TIMEOUT, SUBDIRECTORY, COOKIE_DOMAIN);
		} else {
			session_set_cookie_params(SESSION_TIMEOUT, SUBDIRECTORY);
		}
		session_start();
		
		//Fix for a bug with the $lifetime option in session_set_cookie_params()
		//as mentioned on http://php.net/manual/en/function.session-set-cookie-params.php
		setCookieOnCookieDomain(session_name(), session_id(), SESSION_TIMEOUT);
	}
}

function editionInclude($name) {
	foreach (cms_core::$editions as $className) {
		if ($editionInclude = moduleDir($className, 'edition_includes/'. $name. '.php', true)) {
			return $editionInclude;
		}
	}
	
	return 'zenario/includes/dummy_include.php';
}







class cms_core {
	public static $globalDB;
	public static $localDB;
	public static $lastDB;
	public static $lastDBHost;
	public static $lastDBName;
	public static $lastDBPrefix;
	
	public static $edition = '';
	public static $editions = array();
	
	public static $equivId = false;
	public static $cID = false;
	public static $cType = '';
	public static $cVersion = false;
	public static $adminVersion = false;
	public static $visitorVersion = false;
	public static $isDraft = false;
	public static $locked = false;
	public static $alias = '';
	public static $status = '';
	public static $adminId = false;
	public static $userId = false;
	public static $langId = false;
	public static $skinId = false;
	public static $skinName = '';
	public static $skinCSS = '';
	public static $layoutId = false;
	public static $cols = false;
	public static $minWidth = false;
	public static $maxWidth = false;
	public static $fluid = false;
	public static $responsive = false;
	public static $templatePath = '';
	public static $templateFamily = '';
	public static $templateFilename = '';
	public static $templateFileBaseName = '';
	public static $siteDesc = array();
	public static $adminSettings = array();
	public static $siteConfig = array();
	public static $specialPages = array();
	public static $slotContents = array();
	public static $pluginsOnPage = 0;
	
	public static $homeCID = 0;
	public static $homeEquivId = 0;
	public static $homeCType = '';
	public static $pkCols = array();
	public static $numericCols = array();
	public static $groups = '';
	public static $signalsCurrentlyTriggered = array();
	public static $importantGetRequests = array();
	public static $locationDependant = false;
	public static $canCache;
	public static $cachingInUse = false;
	public static $userAccessLogged = false;
	public static $mustUseFullPath = false;
	public static $cookieConsent = '';
	public static $pageTitle = '';
	public static $description = '';
	public static $keywords = '';
	public static $moduleClassNameForPhrases = '';
	public static $langs = array();
	public static $date = false;
	public static $menuTitle = false;
	public static $rss = array();
	public static $rss1st = true;
	public static $pluginJS = '';
	public static $itemCSS = '';
	public static $templateCSS = '';
	public static $frameworkFile = '';

	public static $skPath = '';
	public static $skType = '';
	public static $dbupPath = false;
	public static $dbupUpdateFile = false;
	public static $dbupCurrentRevision = false;
	public static $dbupUninstallPluginOnFail = false;
	public static $apcDirs = array();
	public static $apcFoundCodes = array();
	public static $execEnabled = null;
	
	
	public static function errorOnScreen($errno, $errstr, $errfile, $errline) {
		cms_core::$canCache = false;
		return false;
	}
}

//Putting "no_cache" in the request should always disable any caching
cms_core::$canCache = empty($_REQUEST['no_cache']);


class zenario_error {
	public $errors = array();
	
	public function __construct($a = null, $b = null) {
		if ($a !== null) {
			$this->add($a, $b);
		}
	}
	
	public function add($a, $b = null) {
		
		if (is_array($a)) {
			$this->errors = array_merge($this->errors, $a);
		} else if ($b !== null) {
			$this->errors[$a] = $b;
		} else {
			$this->errors[] = $a;
		}
	}
	
	public function __toString() {
		foreach ($this->errors as $error) {
			return $error;
		}
		return '';
	}
}


//Try to include the siteconfig file, and set up some constants
if (file_exists(CMS_ROOT. 'zenario_siteconfig.php') && filesize(CMS_ROOT. 'zenario_siteconfig.php') > 19) {
	require CMS_ROOT. 'zenario_siteconfig.php';
}


//Set the error level if specified in the site configs, defaulting to (E_ALL & ~E_NOTICE | E_STRICT) if not defined or if the site configs have not yet been included
if (defined('ERROR_REPORTING_LEVEL')) {
	error_reporting(ERROR_REPORTING_LEVEL);
} else {
	error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
}

//Make sure the the cookie/session constants are set
if (!defined('COOKIE_DOMAIN')) {
	define('COOKIE_DOMAIN', '');
}
if (!defined('COOKIE_TIMEOUT')) {
	define('COOKIE_TIMEOUT', 8640000);
}
if (!defined('SESSION_TIMEOUT')) {
	define('SESSION_TIMEOUT', 0);
}
if (!defined('DIRECTORY_INDEX_FILENAME')) {
	define('DIRECTORY_INDEX_FILENAME', 'index.php');
}

//Set the timezone to UTC if it's not defined, to avoid a PHP error
if ($tz = @date_default_timezone_get()) {
	date_default_timezone_set($tz);
} else {
	date_default_timezone_set('UTC');
}





require CMS_ROOT. 'zenario/admin/db_updates/latest_revision_no.inc.php';
require CMS_ROOT. 'zenario/includes/stripslashes.inc.php';
require CMS_ROOT. 'zenario/editions.inc.php';