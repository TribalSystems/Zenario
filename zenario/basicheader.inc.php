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

function httpUserAgent() {
	return isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT'] : '';
}

function engToBoolean($text) {
	if (is_object($text) && get_class($text) == 'zenario_error') {
		return 0;
	
	} elseif (is_bool($text) || is_numeric($text)) {
		return (int) ((bool) $text);
	
	} else {
		return (int) (false !== filter_var($text, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
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


function hash64($text, $len = 28) {
	return substr(rtrim(strtr(base64_encode(sha1($text, true)), '+/', '-_'), '='), 0, $len);
}

function base64($text) {
	return rtrim(strtr(base64_encode($text), '+/', '-_'), '=');
}

function base16To64($text) {
	return base64(pack('H*', $text));
}

function in($needle) {
	$haystack = func_get_args();
	array_splice($haystack, 0, 1);
	return in_array($needle, $haystack);
}

function isError($object) {
	return is_object($object) && get_class($object) == 'zenario_error';
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

//Get the value of a site setting
function setting($settingName) {
	if (isset(cms_core::$siteConfig[$settingName])) {
		return cms_core::$siteConfig[$settingName];
	}
	
	if (cms_core::$lastDB) {
		$sql = "
			SELECT IFNULL(value, default_value), ". (isset(cms_core::$dbCols[DB_NAME_PREFIX. 'site_settings']['encrypted'])? 'encrypted' : '0'). "
			FROM ". DB_NAME_PREFIX. "site_settings
			WHERE name = '". sqlEscape($settingName). "'";
		if ($row = sqlFetchRow($sql)) {
			if ($row[1]) {
				loadZewl();
				return cms_core::$siteConfig[$settingName] = zewl::decrypt($row[0]);
			} else {
				return cms_core::$siteConfig[$settingName] = $row[0];
			}
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

function useGZIP() {
	
	//As of Zenario 7.2, we now rely on people enabling compression in their php.ini or .htaccess files
	//The only purpose of this function is now to trigger ob_start() when page caching is enabled.
	
	//If caching is enabled, call ob_start() to start output buffering if it was not already done above.
	if (empty(cms_core::$siteConfig) || setting('caching_enabled')) {
		ob_start();
	}
}

function visitorIP() {
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

//Check if this is https
function isHttps() {
	return
		(isset($_SERVER['HTTPS']) && engToBoolean($_SERVER['HTTPS']))
	 || (defined('USE_FORWARDED_IP')
	  && constant('USE_FORWARDED_IP')
	  && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
	  && substr($_SERVER['HTTP_X_FORWARDED_PROTO'], 0, 5) == 'https')
	 || (!empty($_SERVER['SCRIPT_URI'])
	  && substr($_SERVER['SCRIPT_URI'], 0, 5) == 'https');
}

function requireJsLib($lib, $stylesheet = null, $cacheWrappers = true) {
	cms_core::$jsLibs[$lib] = [$stylesheet, $cacheWrappers];
}

function setCookieOnCookieDomain($name, $value, $expire = COOKIE_TIMEOUT) {
	
	if ($expire > 1) {
		$expire += time();
	}
	
	setcookie($name, $value, $expire, SUBDIRECTORY, COOKIE_DOMAIN, isHttps(), true);
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
	return 'PHPSESSID'.
		(COOKIE_DOMAIN? ('-'. preg_replace('@\W@', '_', COOKIE_DOMAIN)) : '').
		(SUBDIRECTORY && SUBDIRECTORY != '/'? ('-'. preg_replace('@\W@', '_', str_replace('/', '', SUBDIRECTORY))) : '');
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

function editionInclude($name, $continueFrom = false) {
	
	foreach (cms_core::$editions as $className) {
	
		if (!$continueFrom && $editionInclude = moduleDir($className, 'edition_includes/'. $name. '.php', true)) {
			return CMS_ROOT. $editionInclude;
		} elseif ($continueFrom == $className) {
			$continueFrom = false;
		}
	}
	
	return CMS_ROOT. 'zenario/includes/dummy_include.php';
}







class cms_core {
	public static $globalDB;
	public static $localDB;
	public static $lastDB;
	public static $lastDBHost;
	public static $lastDBName;
	public static $lastDBPrefix;
	public static $mongoDB;
	
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
	public static $langId = null;
	public static $visLang = null;
	public static $defaultLang = null;
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
	public static $modulesLoaded = array();
	public static $modulesOnPage = array();
	public static $pluginsOnPage = 0;
	
	public static $homeCID = 0;
	public static $homeEquivId = 0;
	public static $homeCType = '';
	public static $pkCols = array();
	public static $dbCols = array();
	public static $groups = '';
	public static $signalsCurrentlyTriggered = array();
	public static $importantGetRequests = array();
	public static $locationDependant = false;
	public static $canCache;
	public static $cachingInUse = false;
	public static $cacheWrappers = false;
	public static $cacheCoreVars = ['cID' => '', 'cType' => 'T', 'visLang' => 'L', 'slotName' => 'S', 'instanceId' => 'I', 'method_call' => 'M'];
	public static $userAccessLogged = false;
	public static $mustUseFullPath = false;
	public static $wrongDomain = false;
	public static $cookieConsent = '';
	public static $menuTitle = false;
	public static $pageTitle = '';
	public static $pageDesc = '';
	public static $pageImage = 0;
	public static $pageKeywords = '';
	public static $pageOGType = 'webpage';
	public static $moduleClassNameForPhrases = '';
	public static $langs = array();
	public static $timezone = null;
	public static $date = false;
	public static $rss = array();
	public static $rss1st = true;
	public static $pluginJS = '';
	public static $jsLibs = array();
	public static $itemCSS = '';
	public static $templateCSS = '';
	public static $frameworkFile = '';
	public static $twig;
	public static $isTwig = false;
	public static $twigModules = array();
	public static $whitelist = array('print_r', 'var_dump', 'json_encode');
	public static $vars = array();

	public static $skPath = '';
	public static $skType = '';
	public static $dbupPath = false;
	public static $dbupUpdateFile = false;
	public static $dbupCurrentRevision = false;
	public static $dbupUninstallPluginOnFail = false;
	public static $pq = array();
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

//Attempt to calculate the SUBDIRECTORY, if not set already
//(Note that similar logic is used to validate the SUBDIRECTORY at the top of admin/welcome.php)
if (!defined('SUBDIRECTORY')) {
	//Get the original included filepath
	$file = substr($_SERVER['SCRIPT_FILENAME'], strlen(CMS_ROOT));

	//Get the included location
	$self = $_SERVER['PHP_SELF'];

	//If the two don't match up, try chopping the filenames off the ends of the path
	if (substr($self, -strlen($file)) != $file) {
		$pos = max(strrpos($file, '/'), strrpos($file, '\\'), -1);
		$file = substr($file, 0, $pos? $pos + 1 : 0);
	
		$pos = max(strrpos($self, '/'), strrpos($self, '\\'), -1);
		$self = substr($self, 0, $pos? $pos + 1 : 0);
	
		unset($pos);
	}

	//Trim the included location by the filepath to get the current SUBDIRECTORY
	if (strlen($file)) {
		$subdir = substr($self, 0, -strlen($file));
	} elseif ($self) {
		$subdir = $self;
	} else {
		$subdir = '/';
	}

	define('SUBDIRECTORY', $subdir);
	unset($file, $self, $subdir);
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

if (defined('DBHOST') && !defined('DBPORT')) {
	define('DBPORT', '');
}
if (defined('DBHOST_GLOBAL') && !defined('DBPORT_GLOBAL')) {
	define('DBPORT_GLOBAL', '');
}

//Set the timezone to UTC if it's not defined on the server, to avoid a PHP error
//(N.b. as soon as we get a database connection we'll set it properly.)
if ($tz = @date_default_timezone_get()) {
	date_default_timezone_set($tz);
} else {
	date_default_timezone_set('UTC');
}




require CMS_ROOT. 'zenario/admin/db_updates/latest_revision_no.inc.php';
require CMS_ROOT. 'zenario/includes/stripslashes.inc.php';
require CMS_ROOT. 'zenario/libraries/by_vendor/autoload.php';
require CMS_ROOT. 'zenario/editions.inc.php';