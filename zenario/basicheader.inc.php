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


//	This header file includes the basic CMS library, but does not connect to the database,
//	or start the visitor's session.

//Set the CMS_ROOT if not already done by the calling script
if (!defined('CMS_ROOT')) {
	$dirname = dirname($cwd = $argv[0] ?? $_SERVER['SCRIPT_FILENAME'] ?? '.');
	
 	//MacOS or Linux	//Windows
	if ($cwd[0] === '/' || ($cwd[1] === ':' && $cwd[2] === '/')) {
		$cwd = $dirname. '/';
	} else {
		$cwd = getcwd(). '/'. ($dirname === '.'? '' : $dirname. '/');
	}
	
	for ($ci = 9; --$ci > 0 && !is_file($cwd. 'zenario/basicheader.inc.php');) {
		$cwd = dirname($cwd). '/';
	}

	define('CMS_ROOT', $cwd);
}
unset($ci, $cwd, $dirname);

if (!is_file(CMS_ROOT. 'zenario/basicheader.inc.php')) {
	echo 'Your CMS_ROOT value is not correctly set. \''. CMS_ROOT. '\' is not the correct path to the folder containing the Zenario index.php file.';
	exit;
}

chdir(CMS_ROOT);
define('NOT_ACCESSED_DIRECTLY', true);
define('THIS_FILE_IS_BEING_DIRECTLY_ACCESSED', false);

//Try to include the siteconfig file
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



class ze {
	//Databases used
	public static $dbD;	//Data archive, for asset data and other large storage
	public static $dbG;	//Global database, for multisite admins
	public static $dbL;	//Local database, for all site content
	
	public static $edition = '';
	public static $editions = [];
	
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
	public static $isPublic;
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
	public static $siteDesc = [];
	public static $adminSettings = [];
	public static $siteConfig = [];
	public static $specialPages = [];
	public static $nonSearchablePages = [];
	public static $slotContents = [];
	public static $modulesLoaded = [];
	public static $pluginsOnPage = 0;
	
	public static $homeCID = 0;
	public static $homeEquivId = 0;
	public static $homeCType = '';
	public static $groups = '';
	public static $signalsCurrentlyTriggered = [];
	public static $googleRecaptchaElements = [];
	public static $importantGetRequests = [];
	public static $locationDependant = false;
	
	public static $canCache;
	public static $cachingInUse = false;
	public static $cacheEnv;
	public static $saveEnv;
	public static $knownReq;
	public static $allReq;
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
	public static $pageOGType = 'website';
	public static $plugin;
	public static $langs = [];
	public static $trackPhrases = false;
	public static $timezone = null;
	public static $date = false;
	public static $rss = [];
	public static $rss1st = true;
	public static $pluginJS = '';
	public static $jsLibs = [];
	public static $itemCSS = '';
	public static $templateCSS = '';
	public static $frameworkFile = '';
	public static $twig;
	public static $isTwig = false;
	public static $twigModules = [];
	public static $vars = [];

	public static $tuixType = 'visitor';
	public static $tuixPath = '';
	public static $dbUpdating = false;
	public static $pq = [];
	public static $apcDirs = [];
	public static $apcFoundCodes = [];
	public static $execEnabled = null;
	
	public static $ers = [];
	private static $igEr = 0;
	private static $eSent = false;
	public static function error($errno, $errstr, $errfile, $errline) {
		
		if (ze::$igEr > 0) {
			ze::$ers[] = $errstr;
			return true;
		}
		
		if (!ze::$eSent) {
			$errorType = '';
			switch ($errno) {
				//"Error"
				case 1: //E_ERROR
				case 4: //E_PARSE
				case 16: //E_CORE_ERROR
				case 64: //E_COMPILE_ERROR
				case 256: //E_USER_ERROR
				case 4096: //E_RECOVERABLE_ERROR
					$errorType = "error";
					break;
				
				//"Warning"
				case 2: //E_WARNING
				case 32: //E_CORE_WARNING
				case 128: //E_COMPILE_WARNING
				case 512: //E_USER_WARNING
					$errorType = "warning";
					break;
				
				//"Notice"
				case 8: //E_NOTICE
				case 1024: //E_USER_NOTICE
				case 8192: //E_DEPRECATED
				case 16384: //E_USER_DEPRECATED
					$errorType = "notice";
					break;
				
				//"Strict"
				case 2048: //E_STRICT
					$errorType = "strict warning";
					break;
				
				default:
					$errorType = "error";
					break;
			}
			
			ze\db::reportError('PHP ' . $errorType . ' at', $errstr, 'in '. $errfile, 'at line '. $errline);
		}
		
		ze::$eSent = true;
		ze::$canCache = false;
		return false;
	}
	public static function ignoreErrors() {
		++ze::$igEr;
	}
	public static function noteErrors() {
		--ze::$igEr;
	}

	
	
	public static function isAdmin() {
		return !empty($_SESSION['admin_logged_into_site']) && ze\priv::check();
	}

	//Formerly "funIncPath()"
	public static function funIncPath($filePathOrModuleClassName, $functionName) {
		if (strpos($filePathOrModuleClassName, '/') === false
		 && strpos($filePathOrModuleClassName, '\\') === false) {
			$dir = CMS_ROOT. ze::moduleDir($filePathOrModuleClassName);
		} else {
			$dir = dirname($filePathOrModuleClassName);
		}
		return $dir. '/'. (basename($dir) != 'fun'? 'fun/' : ''). $functionName. '.php';
	}


	const hash64FromTwig = true;
	//Formerly "hash64()"
	public static function hash64($text, $len = 28) {
		return substr(rtrim(strtr(base64_encode(sha1($text, true)), '+/', '-_'), '='), 0, $len);
	}

	//Formerly "base64()"
	public static function base64($text) {
		return rtrim(strtr(base64_encode($text), '+/', '-_'), '=');
	}

	//Formerly "base16To64()"
	public static function base16To64($text) {
		return ze::base64(pack('H*', $text));
	}

	//Formerly "isError()"
	public static function isError($object) {
		return is_object($object) && get_class($object) == 'ze\\error';
	}


	//Return a path to a Module or a Module's sub-directory, given that the Module might be in one of two directories
	const moduleDirFromTwig = true;
	//Formerly "moduleDir()"
	public static function moduleDir($moduleName, $subDir = '', $checkExists = false, $checkFrameworks = false, $checkS2O = true) {
		$moduleName = preg_replace('/\W/', '', $moduleName);
	
		if ($subDir !== '') {
			//Catch the case where the tuix/storekeeper subdirectory wasn't yet renamed to tuix/organizer
			if ($checkS2O
			 && ((($len = 14) && (substr($subDir, 0, $len) == 'tuix/organizer'))
			  || (($len = 16) && (substr($subDir, 0, $len) == 'tuix/storekeeper')))) {
			
				//Catch either form of the request, and then run a check for both!
				if ($dir = ze::moduleDir($moduleName, 'tuix/organizer'. substr($subDir, $len), true, $checkFrameworks, false)) {
					return $dir;
				} else {
					return ze::moduleDir($moduleName, 'tuix/storekeeper'. substr($subDir, $len), $checkExists, $checkFrameworks, false);
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
			return ze::moduleDir($moduleName, 'db_updates/'. $subDir, $checkExists);
	
		} else {
			return false;
		}
	}

	//Formerly "moduleDirs()"
	public static function moduleDirs($tests = 'module_code.php') {
		$dirs = [];
	
		if (!is_array($tests)) {
			$tests = [$tests];
		}
	
		foreach ([
			'zenario/modules/',
			'zenario_extra_modules/',
			'zenario_custom/modules/'
		] as $path) {
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
	//Formerly "setting()", "getSiteConfig()"
	public static function setting($settingName, $useCache = true, $default = false, $secret = false) {
		$secret = (int) $secret;
		
		if ($useCache && isset(ze::$siteConfig[$secret][$settingName])) {
			return ze::$siteConfig[$secret][$settingName];
		}
	
		if (ze::$dbL) {
			
			$secretColExists = isset(ze::$dbL->cols[DB_PREFIX. 'site_settings']['secret']);
			$encryptedColExists = isset(ze::$dbL->cols[DB_PREFIX. 'site_settings']['encrypted']);
			
			$sql = "
				SELECT IFNULL(value, default_value), ". ($encryptedColExists? 'encrypted' : '0'). "
				FROM ". DB_PREFIX. "site_settings
				WHERE name = '". ze\escape::sql($settingName). "'";
			
			if ($secretColExists) {
				$sql .= "
				  AND `secret` = ". (int) $secret;
			}
			
			if ($row = ze\sql::fetchRow($sql)) {
				if ($row[1]) {
					ze\zewl::init();
					return ze::$siteConfig[$secret][$settingName] = ze\zewl::decrypt($row[0]);
				} else {
					return ze::$siteConfig[$secret][$settingName] = $row[0];
				}
			} else {
				ze::$siteConfig[$secret][$settingName] = $default;
			}
		}
	
		return $default;
	}
	public static function secretSetting($settingName, $useCache = true) {
		return ze::setting($settingName, $useCache, false, true);
	}

	//Formerly "requireJsLib()"
	public static function requireJsLib($lib, $stylesheet = null) {
		ze::$jsLibs[$lib] = $stylesheet;
	}

	//Formerly "editionInclude()"
	public static function editionInclude($name, $continueFrom = false) {
	
		foreach (ze::$editions as $className) {
	
			if ($continueFrom === false && $editionInclude = ze::moduleDir($className, 'edition_includes/'. $name. '.php', true)) {
				return CMS_ROOT. $editionInclude;
			} elseif ($continueFrom == $className) {
				$continueFrom = false;
			}
		}
	
		return CMS_ROOT. 'zenario/includes/dummy_include.php';
	}



	//Returns the name of the currently running plugin, in upper-case.
	//Must be called from code within the plugin's own folder with the __FILE__ Magic Constant
	//The main reason for this function is to use in the latest_revision_no.inc.php files, to keep them
	//nice and tidy.
	//Formerly "moduleName()"
	public static function moduleName($file) {
		//Take the current path
			//Match up to and including the modules directory with .*[/\\]modules[/\\]
			//The next sequence of chars will be the modules directory
			//There may be another slash, or to the path - match with [/\\]?.*
		return strtoupper(preg_replace('#.*modules[/\\\\](\w*)[/\\\\]?\w*[/\\\\]?#', '\1', dirname($file)));
	}
	
	
	//Functions for magic variables
	//Consider using the null coalescing operator (??) instead of these functions...
	//Formerly "get()"
	public static function get($n) {
		return $_GET[$n] ?? false;
	}
	//Formerly "post()"
	public static function post($n) {
		return $_POST[$n] ?? false;
	}
	//Formerly "request()"
	public static function request($n) {
		return $_REQUEST[$n] ?? false;
	}
	//Formerly "session()"
	public static function session($n) {
		return $_SESSION[$n] ?? false;
	}
	//Formerly "ifNull()"
	public static function ifNull($a, $b, $c = null) {
		return $a ?: ($b ?: $c);
	}
	
	public static function isVal($a) {
		return $a !== null && $a !== false && $a !== '';
	}
	
	public static function define($a, $b) {
		if (!defined($a)) {
			define($a, $b);
		}
	}
	public static function defineBC($n, $o) {
		if (defined($n)) {
			ze::define($o, constant($n));
		} elseif (defined($o)) {
			define($n, constant($o));
		}
	}
	
	//A shortcut function to in_array, that looks similar to the MySQL IN() function
	//Formerly "in()"
	public static function in($needle, ...$haystack) {
		return in_array($needle, $haystack);
	}
	
	//Limit a value to a specific list. The first value in the list is returned if the value does not match.
	public static function oneOf($needle, ...$haystack) {
		return in_array($needle, $haystack)? $needle : $haystack[0];
	}
	
	//Returns true if a $_COOKIE variable does not affect caching (or is already covered by another existing category)
	public static function cacheFriendlyCookieVar($var) {
		return substr($var, 0, 2) == '__'
			|| substr($var, 0, 4) == '_ga_'
			|| substr($var, 0, 9) == 'PHPSESSID'
			|| substr($var, 0, 11) == 'can_cache__'
			|| in_array($var, ['cookies_accepted', '_ga', '_gat', 'is_returning']);
	}
	
	//Returns true if a $_SESSION variable does not affect caching (or is already covered by another existing category)
	public static function cacheFriendlySessionVar($var) {
		return substr($var, 0, 11) == 'can_cache__'
			|| in_array($var, ['unnecessary_cookies_rejected', 'extranetUserID', 'extranetUser_firstname', 'user_lang', 'destCID', 'destCType', 'destURL', 'destTitle']);
	}
	
	
	//Check that $_GET/$_POST/etc are flat arrays, and trim all of their values
	public static function trim(&$a, $twoDeep = false) {
		foreach ($a as &$v) {
			if (is_array($v)) {
				if ($twoDeep) {
					ze::trim($v);
				} else {
					$v = false;
				}
			} else {
				$v = trim($v);
			}
		}
	}
}

if (!empty($_GET)) ze::trim($_GET);
if (!empty($_POST)) ze::trim($_POST, true);
if (!empty($_COOKIE)) ze::trim($_COOKIE);
if (!empty($_REQUEST)) ze::trim($_REQUEST);






//Set the error level if specified in the site configs, defaulting to (E_ALL & ~E_NOTICE | E_STRICT) if not defined or if the site configs have not yet been included
ze::define('ERROR_REPORTING_LEVEL', E_ALL & ~E_NOTICE & ~E_STRICT);
error_reporting(ERROR_REPORTING_LEVEL);

//Also add a wrapper to the error handler that checks if a page has a visible error on it
set_error_handler(['ze', 'error'], ERROR_REPORTING_LEVEL);


//Putting "no_cache" in the request should always disable any caching
ze::$canCache = empty($_REQUEST['no_cache']);

//Set the timezone to UTC if it's not defined on the server, to avoid a PHP error
//(N.b. as soon as we get a database connection we'll set it properly.)
if ($tz = @date_default_timezone_get()) {
	date_default_timezone_set($tz);
} else {
	date_default_timezone_set('UTC');
}

//Make sure the the cookie/session constants are set
ze::define('COOKIE_DOMAIN', '');
ze::define('COOKIE_TIMEOUT', 8640000);
ze::define('SESSION_TIMEOUT', 0);
ze::define('DIRECTORY_INDEX_FILENAME', 'index.php');
ze::defineBC('DB_PREFIX', 'DB_NAME_PREFIX');
ze::defineBC('DB_PREFIX_GLOBAL', 'DB_NAME_PREFIX_GLOBAL');
if (defined('DBHOST')) ze::define('DBPORT', '');
if (defined('DBHOST_GLOBAL')) ze::define('DBPORT_GLOBAL', '');


//Some standard constant definitions

//Signal that you need to log in as an extranet user to see this page
//(N.b. we use an empty string for this, because it evaluates to false if someone isn't using a === check.)
define('ZENARIO_401_NOT_LOGGED_IN', '');

//Signal that the current extranet user does not have access to view the page.
//(N.b. we use a zero for this, because it evaluates to false if someone isn't using a === check.)
define('ZENARIO_403_NO_PERMISSION', 0);

//Signal that a page was not found.
define('ZENARIO_404_NOT_FOUND', false);






require CMS_ROOT. 'zenario/editions.inc.php';
if (file_exists(CMS_ROOT. 'zenario_custom/editions.inc.php')) {
	require CMS_ROOT. 'zenario_custom/editions.inc.php';
}

require CMS_ROOT. 'zenario/admin/db_updates/latest_revision_no.inc.php';

if (ZENARIO_IS_BUILD) {
	require CMS_ROOT. 'zenario/libs/composer_dist/autoload.php';
} else {
	require CMS_ROOT. 'zenario/libs/composer_no_dist/autoload.php';
}
