<?php
/*
 * Copyright (c) 2014, Tribal Limited
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

function browserBodyClass() {
	$c = '';
	$a = $_SERVER['HTTP_USER_AGENT'];
	
	if (strpos($a, 'MSIE') !== false) {
		$c .= 'ie ';
		for ($i = 9; $i > 5; --$i) {
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

function funIncPath($filePath, $functionName) {
	$dir = dirname($filePath);
	return $dir. '/'. (basename($dir) != 'fun'? 'fun/' : ''). $functionName. '.php';
}


function get($n) {
	return isset($_GET[$n])? $_GET[$n] : false;
}

function hash64($text, $len = 28) {
	return substr(strtr(base64_encode(sha1($text, true)), ' +/=', '~-_,'), 0, $len);
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


//Return a path to a Module or a Module's sub-directory, given that the Module might be in one of two directories
function moduleDir($moduleName, $subDir = '', $checkExists = false, $checkFrameworks = false, $checkS2O = true) {
	$moduleName = preg_replace('/\W/', '', $moduleName);
	
	if ($subDir !== '') {
		//Catch the case where the tuix/storekeeper subdirectory was renamed to tuix/storekeeper
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

//Attempt to use gzip compression to send a page
//Note that if you use this, you can then no longer use ob_start()!
function useGZIP($useGZIP = true) {
	if ($useGZIP) {
		//Note: IE 6 frequently crashes if we try to use compression
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6') !== false) {
			ob_start();
		
		//If there has already been some output (e.g. an error) then that will also cause a problem
		} elseif (ob_get_length()) {
			ob_start();
		
		//In PHP versions 5.3 or earlier, use ob_gzhandler. We can't use this in 5.4 due to a bug in PHP.
		} elseif (version_compare(PHP_VERSION, '5.4.0', '<')) {
			ob_start('ob_gzhandler');
		
		//Otherwise try to use zlib
		} else {
			if (extension_loaded('zlib')) {
				ini_set('zlib.output_compression', 4096);
			}
			ob_start();
		}
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

function setCookieConsent() {
	setcookie('cookies_accepted', 1, time() + 60*60*24*365, '/');
	$_COOKIE['cookies_accepted'] = true;
	unset($_SESSION['cookies_rejected']);
}

function setCookieNoConsent() {
	if (isset($_COOKIE['cookies_accepted'])) {
		unset($_COOKIE['cookies_accepted']);
		setcookie('cookies_accepted', '', time()-3600, '/');
	}
	$_SESSION['cookies_rejected'] = true;
}

function deleteCacheDir($dir) {
	if (is_dir($dir)) {
		foreach (scandir($dir) as $file) {
			if ($file != '.' && $file != '..') {
				if (!is_file($dir. '/'. $file)
				 || !@unlink($dir. '/'. $file)) {
					return false;
				}
			}
		}
	}
	return @rmdir($dir);
}





class cms_core {
	public static $globalDB;
	public static $localDB;
	public static $lastDB;
	public static $lastDBHost;
	public static $lastDBName;
	public static $lastDBPrefix;
	
	public static $edition = '';
	public static $editionClass;
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
	public static $templatePath = '';
	public static $templateFamily = '';
	public static $templateFilename = '';
	public static $templateFileBaseName = '';
	public static $siteConfig = array();
	public static $specialPages = array();
	public static $slotContents = array();
	
	public static $homeCID = 0;
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
	public static $cookieConsent = '';
	public static $pageTitle = '';
	public static $description = '';
	public static $keywords = '';
	public static $moduleClassNameForPhrases = '';
	public static $translateLanguages = array();
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


	public static function initInstance(
		&$slotContents, $slotName,
		$cID, $cType, $cVersion,
		$layoutId, $templateFamily, $templateFileBaseName,
		$specificInstanceId, $specificSlotName, $ajaxReload,
		$runPlugins
	) {
		cms_core::$editionClass->initInstance(
			$slotContents, $slotName,
			$cID, $cType, $cVersion,
			$layoutId, $templateFamily, $templateFileBaseName,
			$specificInstanceId, $specificSlotName, $ajaxReload,
			$runPlugins);
	}
	
	public static function preSlot($slotName, $showPlaceholderMethod) {
		return cms_core::$editionClass->preSlot($slotName, $showPlaceholderMethod);
	}
	
	public static function postSlot($slotName, $showPlaceholderMethod) {
		return cms_core::$editionClass->postSlot($slotName, $showPlaceholderMethod);
	}
	
	public static function fillInfoBox(&$infoBox) {
		cms_core::$editionClass->fillInfoBox($infoBox);
	}
	
	public static function lookForMenuItems($parentMenuId, $language, $sectionId, $currentMenuId, $recurseCount, $showInvisibleMenuItems) {
		return cms_core::$editionClass->lookForMenuItems($parentMenuId, $language, $sectionId, $currentMenuId, $recurseCount, $showInvisibleMenuItems);
	}
	
	public static function poweredBy() {
		return cms_core::$editionClass->poweredBy();
	}
	
	public static function publishContent($cID, $cType, $cVersion, $prev_version, $adminId = false, $adminType = false) {
		if (cms_core::$edition) {
			cms_core::$editionClass->publishContent($cID, $cType, $cVersion, $prev_version, $adminId, $adminType);
		}
	}
	
	public static function reviewDatabaseQueryForChanges(&$sql, &$ids, &$values, $table = false, $runSql = false) {
		
		//Only do the review when Modules are running normally and we're connected to the local db
		if (cms_core::$edition
		 && cms_core::$lastDBHost
		 && cms_core::$lastDBHost == DBHOST
		 && cms_core::$lastDBName == DBNAME
		 && cms_core::$lastDBPrefix == DB_NAME_PREFIX) {
			return cms_core::$editionClass->reviewDatabaseQueryForChanges($sql, $ids, $values, $table, $runSql);
		
		} elseif ($runSql) {
			sqlUpdate($sql, false);
			return sqlAffectedRows();
		}
	}
	
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


//Set the error level if specified in the site configs, defaulting to (E_ALL & ~E_NOTICE | E_STRICT) if not defined or if the site configs have not yet been included
if (defined('ERROR_REPORTING_LEVEL')) {
	error_reporting(ERROR_REPORTING_LEVEL);
} else {
	error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
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