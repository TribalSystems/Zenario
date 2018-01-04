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
 *  This file will include the full library of functions in the CMS, except for admin functions.
 */


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
	unset($backtrace);
	unset($file);
	unset($self);
	unset($subdir);
}


//Add includes from the CMS
require CMS_ROOT. 'zenario/api/admin_functions.inc.php';
require CMS_ROOT. 'zenario/api/array_and_object_functions.inc.php';
require_once CMS_ROOT. 'zenario/api/cache_functions.inc.php';
require CMS_ROOT. 'zenario/api/content_item_functions.inc.php';
require_once CMS_ROOT. 'zenario/api/database_functions.inc.php';
require CMS_ROOT. 'zenario/api/link_path_and_url_core_functions.inc.php';
require CMS_ROOT. 'zenario/api/string_functions.inc.php';
require CMS_ROOT. 'zenario/api/system_functions.inc.php';
require CMS_ROOT. 'zenario/api/user_functions.inc.php';
require CMS_ROOT. 'zenario/includes/phrases.inc.php';


if (is_dir(CMS_ROOT. 'zenario_custom/modules')
 && count(scandir(CMS_ROOT. 'zenario_custom/modules')) > 2) {
	//These functions have been moved to an encapsulated class using a PSR-4 autoloader.
	//Some global functions pointing to them have been left here for now to not break backwards compatability
	//with any client or third-party modules that might still use them.
	require CMS_ROOT. 'zenario/api/file_functions.inc.php';
}



//These five functions are deprecated since php 7.0; please use the null coalescing operator (??) instead!
function get($n) {
	return $_GET[$n] ?? false;
}

function post($n) {
	return $_POST[$n] ?? false;
}

function request($n) {
	return $_REQUEST[$n] ?? false;
}

function session($n) {
	return $_SESSION[$n] ?? false;
}

function arrayKey(&$a, $k) {
	if (is_array($a) && isset($a[$k])) {
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


//Deprecated, please just use LATEST_REVISION_NO now
function getLatestRevisionNumber() {
	return LATEST_REVISION_NO;
}

//Get the CMS version number from latest_revision_no.inc.php
//Also attempt to guess whether this is a build or an on-demand site
function getCMSVersionNumber($revision = false, $addNoteAboutSVN = true) {
	
	$thisIsABuild = is_numeric(ZENARIO_REVISION);
	
	$versionNumber = ZENARIO_VERSION;
	
	if ($revision) {
		$versionNumber .= '.'. $revision;
	
	} elseif ($revision === false && $thisIsABuild) {
		$versionNumber .= '.'. ZENARIO_REVISION;
	}
	
	if ($addNoteAboutSVN && !$thisIsABuild) {
		$versionNumber .= (ZENARIO_IS_HEAD? ' (svn HEAD)' : ' (svn branch)');
	}
	
	return $versionNumber;
}




//Write the URLBasePath, and other related JavaScript variables, to the page
function CMSWritePageHead($prefix, $mode = false, $includeOrganizer = false, $overrideFrameworkAndCSS = false) {
	require funIncPath(__FILE__, __FUNCTION__);
}

function CMSWritePageBody($extraClassNames = '', $attributes = '', $showSitewideBodySlot = false, $includeAdminToolbar = false) {
	require funIncPath(__FILE__, __FUNCTION__);
}

function CMSWritePageBodyAdminClass(&$class, &$toolbars) {
	require funIncPath(__FILE__, __FUNCTION__);
}

function CMSWritePageBodyAdminToolbar(&$toolbars, $toolbarAttr = '') {
	require funIncPath(__FILE__, __FUNCTION__);
}

//Write the URLBasePath, and other related JavaScript variables, to the page
function CMSWritePageFoot($prefix, $mode = false, $includeOrganizer = true, $includeAdminToolbar = true, $defer = false) {
	require funIncPath(__FILE__, __FUNCTION__);
}




function showCookieConsentBox() {
		
	//Add the login link for admins if this looks like a logged out admin
	if (isset($_COOKIE['COOKIE_LAST_ADMIN_USER'])
	 && !checkPriv()
	 && !adminDomainIsPrivate()) {
		
		$url =
			(setting('admin_use_ssl')? 'https://' : httpOrhttps()).
			adminDomain(). SUBDIRECTORY.
			'zenario/admin/welcome.php?';
		$importantGetRequests = importantGetRequests(true);
		
		//If this is a 401/403/404 page, include the requested cID and cType,
		//not the actual cID/cType of the 401/403/404 page
		switch (isSpecialPage(cms_core::$cID, cms_core::$cType)) {
			case 'zenario_login':
			case 'zenario_no_access':
			case 'zenario_not_found':
				$importantGetRequests['cID'] = $_REQUEST['cID'] ?? false;
				if (!($importantGetRequests['cType'] = $_REQUEST['cType'] ?? false)) {
					unset($importantGetRequests['cType']);
				}
		}
	
		//Add the logo
		$logoURL = $logoWidth = $logoHeight = false;
		if (setting('admin_link_logo') == 'custom'
		 && (Ze\File::imageLink($logoWidth, $logoHeight, $logoURL, setting('admin_link_custom_logo'), 50, 50, $mode = 'resize', $offset = 0, $retina = true))) {
	
			if (strpos($logoURL, '://') === false) {
				$logoURL = absCMSDirURL(). $logoURL;
			}
		} else {
			$logoURL = absCMSDirURL(). 'zenario/admin/images/zenario_admin_link_logo.png';
			$logoWidth = 25;
			$logoHeight = 19;
		}
		
		echo '
			<div class="admin_login_link">
				<a
					class="clear_admin_cookie"
					href="zenario/cookies.php?clear_admin_cookie=1"
					onclick="
						return confirm(\'', (adminPhrase('Are you sure you wish to remove the admin login link?\n\nGo to /admin to login if the admin link is not visible.')), '\');
					"
				></a>
				<a class="admin_login_link" href="', htmlspecialchars($url. http_build_query($importantGetRequests)), '">
					<img src="', htmlspecialchars($logoURL), '" width="', (int) $logoWidth, '" height="', (int) $logoHeight, '" alt="', adminPhrase('Admin login logo'), '"/><br/>
					', adminPhrase('Login'), '
				</a>
			</div>';
		
		//Never allow a page with an "Admin" link to be cached...
		cms_core::$canCache = false;
		
		//Note that this should override showing the cookie consent box, no matter the settings
		return;
	}

	switch (setting('cookie_require_consent')) {
		case 'implied':
			//Implied consent - show the cookie message, just once. Continuing to use the site counts as acceptance.
			if (!empty($_COOKIE['cookies_accepted']) || ($_SESSION['cookies_accepted'] ?? false)) {
				return;
			}
			
			echo '
<!--googleoff: all-->
	<script type="text/javascript" src="zenario/cookie_message.php?type=implied"></script>
<!--googleon: all-->';
			
			$_SESSION['cookies_accepted'] = true;
			break;
			
			
		case 'explicit':
			//Explicit consent - show the cookie message until it is accepted or rejected, if the reject button is enabled.
			if (cms_core::$cookieConsent == 'hide'
			 || canSetCookie()
			 || (cms_core::$cookieConsent != 'require') && ($_SESSION['cookies_rejected'] ?? false)) {
				return;
			}
			
			if (setting('cookie_consent_type') == 'message_accept_reject' && cms_core::$cookieConsent != 'require') {
				echo '
<!--googleoff: all-->
	<script type="text/javascript" src="zenario/cookie_message.php?type=accept_reject"></script>
<!--googleon: all-->';
			} else {
				echo '
<!--googleoff: all-->
	<script type="text/javascript" src="zenario/cookie_message.php?type=accept"></script>
<!--googleon: all-->';
			}
			
			break;
	}
	
	
	return;

	switch (setting('cookie_require_consent')) {
		case 'implied':
			//Implied consent - show the cookie message, just once. Continuing to use the site counts as acceptance.
			if (!empty($_COOKIE['cookies_accepted']) || ($_SESSION['cookies_accepted'] ?? false)) {
				return;
			}
			
			echo '
<!--googleoff: all-->
	<div class="zenario_cookie_consent">
		<div class="zenario_cookie_consent_wrap">
			<div class="zenario_cc_message">', phrase('_COOKIE_CONSENT_IMPLIED_MESSAGE'), '</div>
			<div class="zenario_cc_buttons">
				<div class="zenario_cc_continue">
					<a href="" onclick="$(\'div.zenario_cookie_consent\').slideUp(\'slow\'); return false;">', phrase('_COOKIE_CONSENT_CONTINUE'), '</a>
				</div>
			</div>
		</div>
	</div>
<!--googleon: all-->';
			
			$_SESSION['cookies_accepted'] = true;
			break;
			
			
		case 'explicit':
			//Explicit consent - show the cookie message until it is accepted or rejected, if the reject button is enabled.
			if (cms_core::$cookieConsent == 'hide'
			 || canSetCookie()
			 || (cms_core::$cookieConsent != 'require') && ($_SESSION['cookies_rejected'] ?? false)) {
				return;
			}
			
			echo '
<!--googleoff: all-->
	<div class="zenario_cookie_consent">
		<div class="zenario_cookie_consent_wrap">
		<div class="zenario_cc_message">', phrase('_COOKIE_CONSENT_MESSAGE'), '</div>
		<div class="zenario_cc_close">
			<a href="#" onclick="$(\'div.zenario_cookie_consent\').fadeOut(\'slow\'); return false;">', phrase('_COOKIE_CONSENT_CLOSE'), '</a>
		</div>
		<div class="zenario_cc_buttons">';
	
	if (setting('cookie_consent_type') == 'message_accept_reject' && cms_core::$cookieConsent != 'require') {
		echo '
			<div class="zenario_cc_reject">
				<a href="zenario/cookies.php?accept_cookies=0">', phrase('_COOKIE_CONSENT_REJECT'), '</a>
			</div>';
	}
	
	echo '
			<div class="zenario_cc_accept">
				<a href="zenario/cookies.php?accept_cookies=1">', phrase('_COOKIE_CONSENT_ACCEPT'), '</a>
			</div>
		</div>
		</div>
	</div>
<!--googleon: all-->';
			break;
	}
}


//Display a Plugin in a slot
function slot($slotName, $mode = false) {
	//Replacing anything non-alphanumeric with an underscore
	$slotName = HTMLId($slotName);
	
	//Start the plugin if it is there, then return it to the Layout
	if (!empty(cms_core::$slotContents[$slotName])
	 && !empty(cms_core::$slotContents[$slotName]['class'])
	 && empty(cms_core::$slotContents[$slotName]['error'])) {
		++cms_core::$pluginsOnPage;
		cms_core::$slotContents[$slotName]['used'] = true;
		cms_core::$slotContents[$slotName]['found'] = true;
		
		cms_core::$slotContents[$slotName]['class']->start();
		
		$slot = cms_core::$slotContents[$slotName]['class'];
	
	//If we didn't find a plugin, but we're in admin mode, 
	//return an "empty" plugin derrived from the base class so that the controls are still displayed to the admin
	} elseif (checkPriv()) {
		//Mark that we've found this slot
		setupNewBaseClassPlugin($slotName);
		cms_core::$slotContents[$slotName]['found'] = true;
		
		cms_core::$slotContents[$slotName]['class']->start();
		
		$slot = cms_core::$slotContents[$slotName]['class'];
	
	} else {
		$slot = false;
	}
	
	if ($mode == 'grid' || $mode == 'outside_of_grid') {
		//New functionality for grids - output the whole slot, don't use a return value
		if ($slot) {
			$slot->show();
			$slot->end();
		}
		//Add some padding for empty grid slots so they don't disappear and break the grid
		if ($mode == 'grid' && (!$slot || checkPriv())) {
			echo '<span class="pad_slot pad_tribiq_slot">&nbsp;</span>';
			//Note: "pad_tribiq_slot" was the old class name.
			//I'm leaving it in for a while as any old Grid Layouts might still be using that name
			//and they won't be updated until the next time someone edits them.
		}
		
	} else {
		//Old functionality - return the class object
		return $slot;
	}
}

function setupNewBaseClassPlugin($slotName) {
	if (!isset(cms_core::$slotContents[$slotName])) {
		cms_core::$slotContents[$slotName] = array();
	}
	
	if (!isset(cms_core::$slotContents[$slotName]['class']) || empty(cms_core::$slotContents[$slotName]['class'])) {
		cms_core::$slotContents[$slotName]['class'] = new module_base_class;
		cms_core::$slotContents[$slotName]['class']->setInstance(
			array(cms_core::$cID, cms_core::$cType, cms_core::$cVersion, $slotName, false, false, false, false, false, false, false, false, false, false));
	}
}

function showPluginError($slotName) {
	echo ifNull(arrayKey(cms_core::$slotContents, $slotName, 'error'), adminPhrase('[Empty Slot]'));
}

//Did we use all of our slots..?
function checkSlotsWereUsed() {
	//Only run this in admin mode
	if (checkPriv()) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
}






//Return the a name of a CSS class to use, depending on whether a field is set or not
function mandatory($filled) {
	return $filled? 'class="mandatory_correct"' : 'class="mandatory"';
}

//There is no way to target another frame with a header('location: www.blah.com'); call.
//This function is as close as we're going to get, alas
function topHeaderLocation($url, $prefix = 'top') {
	echo 
'<html>
	<body>
		<script type="text/javascript">
			', $prefix, '.location.href = "', $url, '";
		</script>
		<a href="', $url, '" target="_main">Please click here to continue to the correct location</a>
	</body>
</html>';
	exit;
}


/*
 *   function fillOptions
 *	 returns the rows of a table
 *   as a string ready for use
 *   inside a select tag
 */
function fillOptions( $table, $value, $text, $default = '', $refiner = '', $useGetPhrase = true ) {
	$options = '';
	if ( ! empty( $refiner ) ) {
		$refiner = 'where ' . $refiner;
	}
	$sql = '
		select ' . $value . ', ' . $text . '
		from ' . $table . '
		' . $refiner . ' 
		order by ' . $text;
	$result = sqlQuery($sql);
	while ( list( $id, $text ) = sqlFetchRow( $result ) ) {
		if ( $id == $default ) {
			$selected = ' selected = "selected"';
		} else {
			$selected = '';
		}
		if ( $useGetPhrase ) {
			$options .= '<option value="' . $id . '"' . $selected.'>' . adminPhrase( $text ) . '</option>';
		} else {
			$options .= '<option value="' . $id . '"' . $selected.'>' .  $text . '</option>';
		}
	}
	return $options;
}
/*
 *   function enumToOption
 *	 returns the options of enum field
 *   as a string ready for use
 *   inside a select tag
 */

function enumToOption( $table, $field, $defaultValue = '') {
	$result = sqlSelect( 'describe ' . $table . ' ' . $field );
	$row =  sqlFetchArray( $result );
	#removes starting 'enum(' and the closing ')'
	$enumValues = substr ( $row['Type'] , 5, -1 );
	#removes any single quotes from the string
	$enumValues = str_replace( '\'', '', $enumValues );
	#makes an array for processing options
	$enumValues = explode ( ',', $enumValues );
	$options = '';
	foreach ( $enumValues as $value ) {
		if ( $value == $defaultValue ) {
			$options .= '<option value="' . $value .'" selected="selected">' . adminPhrase( '_' . $value ) . '</option>';
		} else { 
			$options .= '<option value="' . $value .'">' . adminPhrase( '_' . $value ) . '</option>';
		}
	}
	return $options;
}


function maskString($str,$char_mask="*") {
	$mask = "";
	for ($i=0; $i < strlen($str); $i++) {
		$mask .= $char_mask;
	}
	return $mask;
}


//Handle error pages

/* Special case for an zenario/admin/Author creating a new site */
function showStartSitePageIfNeeded($reportDBOutOfDate = false) {
	return require funIncPath(__FILE__, __FUNCTION__);
}












//Some functions for loading a YAML file
function tuixCacheDir($path) {
	
	$path = str_replace(['/tuix/', '/zenario/'], '/', chopPrefixOffString(CMS_ROOT, $path, true));
	
	$dir = str_replace('%', ' ', rawurlencode(dirname($path)));
	$file = explode('.', basename($path), 2);
	$file = $file[0];
	
	cleanCacheDir();
	return createCacheDir($dir, $type = 'tuix', $onlyForCurrentVisitor = true, $ip = false). $file. '.json';
}


function zenarioReadTUIXFile($path, $useCache = true, $updateCache = true) {
	$type = explode('.', $path);
	$type = $type[count($type) - 1];
	
	if (!file_exists($path)) {
		echo 'Could not find file '. $path;
		exit;
	}
	
	//Attempt to use a cached copy of this TUIX file
		//JSON is a lot faster to read than the other formats, so for speed purposes we create cached JSON copies of files
	$filemtime = false;
	$cachePath = false;
	if ($useCache || $updateCache) {
		$cachePath = tuixCacheDir($path);
	}
	if ($useCache && $cachePath
	 && ($filemtime = filemtime($path))
	 && (file_exists($cachePath))
	 && (filemtime($cachePath) == $filemtime)
	 && ($tags = json_decode(file_get_contents($cachePath), true))) {
		return $tags;
	}
	
	switch ($type) {
		case 'xml':
			//If this is admin mode, allow an old xml file to be loaded and read as a yaml file
			$tags = array();
			if (function_exists('zenarioReadTUIXFileR')) {
				$xml = simplexml_load_file($path);
				zenarioReadTUIXFileR($tags, $xml);
			}
			
			break;
			
		case 'yml':
		case 'yaml':
			
			//Check to see if the file is actually there
			if (!file_exists($path)) {
				//T10201: Add a workaround to fix an occasional bug where the tuix_file_contents table is out of date
				//Try to catch the case where the file was deleted in the filesystem but
				//not from the tuix_file_contents table, and we've not noticed this yet
				if (cms_core::$lastDB
				 && cms_core::$lastDB == cms_core::$localDB) {
				 	
				 	//Look for bad rows from the table
					$sql = "
						DELETE FROM ". DB_NAME_PREFIX. "tuix_file_contents
						WHERE '". sqlEscape($path). "' LIKE CONCAT('%modules/', module_class_name, '/tuix/', type, '/', filename)";
					
					//If we found any, delete them and flag that the cache table might be out of date
					if ($affectedRows = sqlUpdate($sql, false, false)) {
						setSetting('yaml_files_last_changed', '');
						
						//Attempt to continue normally
						return array();
					}
				}
			}
			
			$contents = file_get_contents($path);
			
			//If it was missing or unreadable, display an error and then exit.
			if ($contents === false) {
				echo 'Could not read file '. $path;
				exit;
			
			//Check for a byte order mark at the start of the file.
			//Also use PREG's parser to check that the file was UTF8
			} else
			if (pack('CCC', 0xef, 0xbb, 0xbf) === substr($contents, 0, 3)
			 || preg_match('/./u', $contents) === false) {
				echo $path. ' was not saved using UTF-8 encoding. You must change it to UFT-8, and you must not use a Byte Order Mark.';
				exit;
			
			} else
			if ((preg_match("/[\n\r](\t* +\t|\t+ {4})/", "\n". $contents) !== 0)
			 || (preg_match("/[\n\r](\t+[^\t])/", "\n". $contents) === 1
			 &&  preg_match("/[\n\r]( +[^ ])/", "\n". $contents) === 1)) {
				echo 'The YAML file '. $path. ' contains a mixture of tabs and spaces for indentation and cannot be read';
				exit;
			}
			
			if (defined('USE_NATIVE_YAML_EXTENSION') && function_exists('yaml_parse')) {
				
				$parsedContents = '';
				foreach (preg_split("/([\n\r][ \t]+)/", $contents, -1, PREG_SPLIT_DELIM_CAPTURE) as $i => $line) {
					if ($i % 2) {
						$parsedContents .= str_replace("\t", '    ', $line);
					} else {
						$parsedContents .= $line;
					}
				}
				
				$tags = yaml_parse($parsedContents);
				unset($parsedContents);
				
			} else {
				require_once CMS_ROOT. 'zenario/libraries/mit/spyc/Spyc.php';
				try {
					$tags = Spyc::YAMLLoad($path);
				} catch (Exception $e) {
					echo 'Could not parse file '. $path, "\n", htmlspecialchars($e->getMessage());
					throw $e;
				}
			}
			unset($contents);
			
			break;
			
		default:
			$tags = array();
	}
	
	if (!is_array($tags) || $tags === NULL) {
		echo 'Error in file '. $path;
		exit;
	}
	
	//Backwards compatability hack so that Modules created before we moved the
	//site settings don't immediately break!
	if (!empty($tags['zenario__administration']['nav']['configure_settings']['panel']['items']['settings']['panel'])
	 && empty($tags['zenario__administration']['panels']['site_settings'])) {
		$tags['zenario__administration']['panels']['site_settings'] =
			$tags['zenario__administration']['nav']['configure_settings']['panel']['items']['settings']['panel'];
		unset($tags['zenario__administration']['nav']['configure_settings']['panel']['items']['settings']['panel']);
	}
	
	//Save this array in the cache as a JSON file, for faster loading next time
	if ($updateCache && $cachePath) {
		@file_put_contents($cachePath, json_encode($tags));
		@chmod($cachePath, 0666);
		
		if ($filemtime) {
			@touch($cachePath, $filemtime);
		}
	}
	
	return $tags;
}

function siteDescription($settingName = false) {
	//Load the site description if it's not already loaded
	if (empty(cms_core::$siteDesc)) {
		//Look for a customised site description file:
		if (is_file($path = CMS_ROOT. 'zenario_custom/site_description.yaml')) {
			cms_core::$siteDesc = zenarioReadTUIXFile($path);
		}
		
		//If we didn't find one, try to load one of the templates
		//(Check to see which modules are in the system to try and work out which!)
		if (empty(cms_core::$siteDesc)) {
			$path = CMS_ROOT. 'zenario/api/sample_site_descriptions/';
			
			if (!moduleDir('zenario_pro_features', '', true)) {
				cms_core::$siteDesc = zenarioReadTUIXFile($path. 'community/site_description.yaml');
			
			} elseif (!moduleDir('zenario_scheduled_task_manager', '', true) || !moduleDir('zenario_user_documents', '', true)) {
				cms_core::$siteDesc = zenarioReadTUIXFile($path. 'pro/site_description.yaml');
			
			} elseif (!moduleDir('zenario_geo_landing_pages', '', true) || !moduleDir('zenario_user_timers', '', true)) {
				cms_core::$siteDesc = zenarioReadTUIXFile($path. 'probusiness/site_description.yaml');
			
			} else {
				cms_core::$siteDesc = zenarioReadTUIXFile($path. 'enterprise/site_description.yaml');
			}
		
		} else {
			//Some backwards compatability checks for some old names
			foreach (array(
				'require_security_code_on_admin_login' => 'enable_two_factor_security_for_admin_logins',
				'security_code_by_ip' => 'apply_two_factor_security_by_ip',
				'security_code_timeout' => 'two_factor_security_timeout'
			) as $old => $new) {
				if (isset(cms_core::$siteDesc[$old])
				 && !isset(cms_core::$siteDesc[$new])) {
					cms_core::$siteDesc[$new] = cms_core::$siteDesc[$old];
				}
			}
		}
	}
	
	if ($settingName) {
		if (isset(cms_core::$siteDesc[$settingName])) {
			return cms_core::$siteDesc[$settingName];
		} else {
			return false;
		}
	} else {
		return cms_core::$siteDesc;
	}
}






//Deprecated function that has since been renamed
function getSiteConfig($settingName) {
	return setting($settingName);
}

function cookieFreeDomain() {
	if (httpOrhttps() == 'http://' && setting('use_cookie_free_domain') && setting('cookie_free_domain') && !checkPriv()) {
		return 'http://'. setting('cookie_free_domain'). SUBDIRECTORY;
	} else {
		return false;
	}
}


function importantGetRequests($includeCIDAndType = false) {

	$importantGetRequests = array();
	foreach(cms_core::$importantGetRequests as $getRequest => $defaultValue) {
		if (isset($_GET[$getRequest]) && $_GET[$getRequest] != $defaultValue) {
			$importantGetRequests[$getRequest] = $_GET[$getRequest];
		}
	}
	
	if ($includeCIDAndType && cms_core::$cID && cms_core::$cType) {
		$importantGetRequests['cID'] = cms_core::$cID;
		$importantGetRequests['cType'] = cms_core::$cType;
	}
	
	return $importantGetRequests;
}


//Permission functions

//Check to see if an Admin has a certain privilege
function checkPriv($action = false, $editCID = false, $editCType = false, $editCVersion = 'latest', $welcomePage = false) {
	
	//If the Admin is not logged in to this site, then they shouldn't have Admin rights here
	if (!empty($_SESSION['admin_userid'])
	 && !empty($_SESSION['admin_permissions'])
	 && !empty($_SESSION['admin_logged_into_site'])
	 && $_SESSION['admin_logged_into_site'] == COOKIE_DOMAIN. SUBDIRECTORY. setting('site_id')
	
	//If an admin looks at an embedded link, show them what it would look like if they were logged out.
	//(N.b. if we didn't do this, they'd see a security error due to the X-Frame-Options logic in index.php.)
	 && !isset($_REQUEST['zembedded'])
	
	//If the Admin hasn't passed the welcome page, then they shouldn't be able to use their
	//Admin rights anywhere except the welcome page
	 && ($welcomePage || !empty($_SESSION['admin_logged_in']))) {
		
		//If this is a check to edit a Content Item, also check to see if it is unlocked to
		//the current admin and able to be edited
		if (!$welcomePage
		 && $editCID && $editCType
		
		//Permissions to view languages, menu nodes, plugins and export content items are exceptions and
		//should be granted even if the admin could not edit a content item
		 && $action != '_PRIV_VIEW_LANGUAGE'
		 && $action != '_PRIV_VIEW_MENU_ITEM'
		 && $action != '_PRIV_EXPORT_CONTENT_ITEM'
		 && $action != '_PRIV_VIEW_REUSABLE_PLUGIN') {
			
			//If this is a check on the current content item, there's no need to query the
			//database for info we already have
			if ($editCID === cms_core::$cID
			 && $editCType === cms_core::$cType) {
				$status = cms_core::$status;
				$equivId = cms_core::$equivId;
				$adminVersion = cms_core::$adminVersion;
				$langId = cms_core::$langId;
				$locked = cms_core::$locked;
			
			//Otherwise look up the details from the database
			} else {
				if (!$content = getRow(
					'content_items',
					array('equiv_id', 'language_id', 'status', 'admin_version', 'lock_owner_id'),
					array('id' => $editCID, 'type' => $editCType)
				)) {
					return false;
				}
				
				$status = $content['status'];
				$equivId = $content['equiv_id'];
				$adminVersion = $content['admin_version'];
				$langId = $content['language_id'];
				$locked = $content['lock_owner_id'] && $content['lock_owner_id'] != $_SESSION['admin_userid'];
			}
			
			//Deleted or locked content items cannot be edited
			if ($status == 'deleted' || $locked) {
				return false;
		
			//If a specific version is given, check that version is a draft
			} elseif ($editCVersion !== 'latest') {
				if (($editCVersion !== true && $editCVersion != $adminVersion)
				 || $status == 'published'
				 || $status == 'hidden'
				 || $status == 'trashed') {
					return false;
				}
			}
			
			switch ($_SESSION['admin_permissions']) {
				
				//If this admin can only edit specific content items,
				//or can only edit content items of a specific language,
				//check that this is one of those content items.
				case 'specific_languages':
					if (empty($_SESSION['admin_specific_languages'][$langId])
					 && empty($_SESSION['admin_specific_content_items'][$editCType. '_'. $editCID])) {
						return false;
					}
					
					break;
					
				//If this admin can only edit specific areas of the menu, check that
				//this content item is in one of those areas
				case 'specific_menu_areas':
					if (!empty($_SESSION['admin_specific_menu_sections'])) {
						$sql = "
							SELECT 1
							FROM ". DB_NAME_PREFIX. "menu_nodes AS mn
							WHERE mn.section_id IN (". inEscape($_SESSION['admin_specific_menu_sections'], 'numeric'). ")
							  AND mn.equiv_id = ". (int) $equivId. "
							  AND mn.content_type = '". sqlEscape($editCType). "'
							LIMIT 1";
						
						$result = sqlSelect($sql);
						if (sqlFetchRow($result)) {
							break;
						}
					}
					
					if (!empty($_SESSION['admin_specific_menu_nodes'])) {
						$sql = "
							SELECT 1
							FROM ". DB_NAME_PREFIX. "menu_nodes AS mn
							INNER JOIN ". DB_NAME_PREFIX. "menu_hierarchy AS mh
							   ON mh.child_id = mn.id
							  AND mh.ancestor_id IN (". inEscape($_SESSION['admin_specific_menu_nodes'], 'numeric'). ")
							WHERE mn.equiv_id = ". (int) $equivId. "
							  AND mn.content_type = '". sqlEscape($editCType). "'
							LIMIT 1";
						
						$result = sqlSelect($sql);
						if (sqlFetchRow($result)) {
							break;
						}
					}
					
					return false;
			}
		}
		
		//No action specified? Just check if the admin is logged in.
		if ($action === false) {
			return true;
		
		//Otherwise run different logic depending on this admin's permissions
		} else {
			switch ($_SESSION['admin_permissions']) {
				//Always return true if the admin has every permission
				case 'all_permissions':
					return true;
				
				//If the admin has specific actions, check they have the specified action
				case 'specific_actions':
					return !empty($_SESSION['privs'][$action]);
				
				//Translators/microsite admins can only have a few set permissions,
				//anything else should be denied.
				case 'specific_languages':
				case 'specific_menu_areas':
					switch ($action) {
						case 'perm_author':
						case 'perm_editmenu':
						case 'perm_publish':
						case '_PRIV_VIEW_SITE_SETTING':
						case '_PRIV_VIEW_CONTENT_ITEM_SETTINGS':
						case '_PRIV_VIEW_MENU_ITEM':
						case '_PRIV_EDIT_MENU_TEXT':
						case '_PRIV_CREATE_TRANSLATION_FIRST_DRAFT':
						case '_PRIV_EDIT_DRAFT':
						case '_PRIV_CREATE_REVISION_DRAFT':
						case '_PRIV_DELETE_DRAFT':
						case '_PRIV_EDIT_CONTENT_ITEM_TEMPLATE':
						case '_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE':
						case '_PRIV_IMPORT_CONTENT_ITEM':
						case '_PRIV_EXPORT_CONTENT_ITEM':
						case '_PRIV_HIDE_CONTENT_ITEM':
						case '_PRIV_PUBLISH_CONTENT_ITEM':
						case '_PRIV_TRASH_CONTENT_ITEM':
						case '_PRIV_VIEW_LANGUAGE':
						case '_PRIV_MANAGE_LANGUAGE_PHRASE':
							return true;
					}
			}
		}
	}
	
	return false;
}

function adminPermissionsForTranslators() {
	return array(
		'perm_author' => true,
		'perm_editmenu' => true,
		'perm_publish' => true,
		'_PRIV_VIEW_SITE_SETTING' => true,
		'_PRIV_VIEW_CONTENT_ITEM_SETTINGS' => true,
		'_PRIV_VIEW_MENU_ITEM' => true,
		'_PRIV_EDIT_MENU_TEXT' => true,
		'_PRIV_CREATE_TRANSLATION_FIRST_DRAFT' => true,
		'_PRIV_EDIT_DRAFT' => true,
		'_PRIV_CREATE_REVISION_DRAFT' => true,
		'_PRIV_DELETE_DRAFT' => true,
		'_PRIV_EDIT_CONTENT_ITEM_TEMPLATE' => true,
		'_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE' => true,
		'_PRIV_IMPORT_CONTENT_ITEM' => true,
		'_PRIV_EXPORT_CONTENT_ITEM' => true,
		'_PRIV_HIDE_CONTENT_ITEM' => true,
		'_PRIV_PUBLISH_CONTENT_ITEM' => true,
		'_PRIV_TRASH_CONTENT_ITEM' => true,
		'_PRIV_VIEW_LANGUAGE' => true,
		'_PRIV_MANAGE_LANGUAGE_PHRASE' => true);
}

function adminHasSpecificPerms() {
	return !empty($_SESSION['admin_permissions'])
	 && ($_SESSION['admin_permissions'] == 'specific_languages'
	  || $_SESSION['admin_permissions'] == 'specific_menu_areas');
}

//A more agressive version of check priv that stops all execution if an admin does not have the requested privilege
function exitIfNotCheckPriv($action = false, $editCID = false, $editCType = false, $editCVersion = 'latest', $welcomePage = false) {
	if (!checkPriv($action, $editCID, $editCType, $editCVersion, $welcomePage)) {
		exit;
	}
	return true;
}

//Check to see if an admin can edit a specific menu node
function checkPrivForMenuText($action, $menuNodeId, $langId, $sectionId = false) {
	
	//Run the usual checkPriv() function first
	if (checkPriv($action)) {
		switch ($_SESSION['admin_permissions']) {
			case 'all_permissions':
			case 'specific_actions':
				//Most normal administrators can edit menu text if checkPriv() says they can
				return true;
			
			case 'specific_languages':
				//If an admin can only edit certain languages, allow them to edit the menu text if it
				//is specificially for this language
				if (!empty($_SESSION['admin_specific_languages'][$langId])) {
					return true;
				}
				
				//If an admin can only edit certain content items, allow them to edit the menu text if it
				//is for this language
				if (!empty($_SESSION['admin_specific_content_items'])) {
					$sql = "
						SELECT 1
						FROM ". DB_NAME_PREFIX. "menu_nodes AS mn
						INNER JOIN ". DB_NAME_PREFIX. "content_items AS c
						   ON c.equiv_id = mn.equiv_id
						  AND c.type = mn.content_type
						  AND c.tag_id IN (". inEscape($_SESSION['admin_specific_content_items']). ")
						  AND c.language_id = '". sqlEscape($langId). "'
						WHERE mn.id = ". (int) $menuNodeId. "
						LIMIT 1";
				
					$result = sqlSelect($sql);
					if (sqlFetchRow($result)) {
						return true;
					}
				}
		
			case 'specific_menu_areas':
			
				if ($menuNodeId && !empty($_SESSION['admin_specific_menu_nodes'][$menuNodeId])) {
					return true;
				}
			
				if (!empty($_SESSION['admin_specific_menu_sections'])) {
					if (!$sectionId) {
						$sectionId = getRow('menu_nodes', 'section_id', $menuNodeId);
					}
					if (!empty($_SESSION['admin_specific_menu_sections'][$sectionId])) {
						return true;
					}
				}
			
				if (!empty($_SESSION['admin_specific_menu_nodes'])) {
					$sql = "
						SELECT 1
						FROM ". DB_NAME_PREFIX. "menu_hierarchy AS mh
						WHERE mh.child_id = ". (int) $menuNodeId. "
						  AND mh.ancestor_id IN (". inEscape($_SESSION['admin_specific_menu_nodes'], 'numeric'). ")
						LIMIT 1";
				
					$result = sqlSelect($sql);
					if (sqlFetchRow($result)) {
						return true;
					}
				}
		}
	}
	
	return false;
}

//Check to see if an admin can edit a specific language
function checkPrivForLanguage($action, $langId) {
	
	//Run the usual checkPriv() function first
	if (checkPriv($action)) {
		switch ($_SESSION['admin_permissions']) {
			case 'all_permissions':
			case 'specific_actions':
				//Most normal administrators can edit menu text if checkPriv() says they can
				return true;
			
			case 'specific_languages':
				//If an admin can only edit certain languages, allow them to edit the menu text if it
				//is specificially for this language
				if (!empty($_SESSION['admin_specific_languages'][$langId])) {
					return true;
				}
		}
	}
	
	return false;
}


function loadAdminPerms($adminId) {
	return arrayValuesToKeys(getRowsArray('action_admin_link', 'action_name', array('admin_id' => $adminId)));
}

//Set an admin's session
function setAdminSession($adminIdL, $adminIdG = false) {
	return require funIncPath(__FILE__, __FUNCTION__);
}


//Log an Admin Out
function unsetAdminSession($destorySession = true) {
	
	unset($_SESSION['admin_first_name']);
	unset($_SESSION['admin_last_name']);
	unset($_SESSION['admin_logged_in']);
	unset($_SESSION['admin_logged_into_site']);
	unset($_SESSION['admin_server_host']);
	unset($_SESSION['admin_userid']);
	unset($_SESSION['admin_global_id']);
	unset($_SESSION['admin_username']);
	unset($_SESSION['admin_box_sync']);
	unset($_SESSION['admin_copied_contents']);

	unset($_SESSION['admin_permissions']);
	unset($_SESSION['admin_specific_content_items']);
	unset($_SESSION['admin_specific_languages']);
	unset($_SESSION['admin_specific_menu_nodes']);
	unset($_SESSION['admin_specific_menu_sections']);
	unset($_SESSION['privs']);
	
	if ($destorySession) {
		if (($_SESSION['admin_logged_into_site'] ?? false) == COOKIE_DOMAIN. SUBDIRECTORY. setting('site_id')) {
			if (isset($_COOKIE[session_name()])) {
				clearCookie(session_name());
			}
		}
		
		session_destroy();
	}
}


//When doing database updates, we need to log an admin.
//This means we may need to deal with old versions of the Admin Table
//This function will check to see how up to date the table is, and which columns we can access
function checkAdminTableColumnsExist() {
	if (!defined('CHECK_ADMIN_TABLE_COLUMNS_EXIST')) {
		if ((checkTableDefinition(DB_NAME_PREFIX. 'admins', 'specific_languages') && ($level = 4)) //Added in version 7.1
		 || (checkTableDefinition(DB_NAME_PREFIX. 'admins', 'reset_password') && ($level = 3)) //Added in revision #19495
		 || (checkTableDefinition(DB_NAME_PREFIX. 'admins', 'authtype') && ($level = 2)) //Added in revision #14000
		 || (checkTableDefinition(DB_NAME_PREFIX. 'admins', 'password_salt') && ($level = 1))) { //Added in revision #2521
		} else {
			$level = 0;
		}
		define('CHECK_ADMIN_TABLE_COLUMNS_EXIST', $level);
	}
	
	return CHECK_ADMIN_TABLE_COLUMNS_EXIST;
}





//Some password functions for users/admins

function hashPassword($salt, $password) {
	if ($hash = hashPasswordSha2($salt, $password)) {
		return $hash;
	} else {
		return hashPasswordSha1($salt, $password);
	}
}

function hashPasswordSha2($salt, $password) {
	if ($hash = @hash('sha256', $salt. $password, true)) {
		return 'sha256'. base64_encode($hash);
	} else {
		return false;
	}
}

//Old sha1 function for passwords created before version 6.0.5. Or if sha2 is not enabled on a server.
function hashPasswordSha1($salt, $password) {
	$result = sqlSelect(
		"SELECT SQL_NO_CACHE SHA('". sqlEscape($salt. $password). "')");
	$row = sqlFetchRow($result);
	return $row[0];
}


/*
	Functions to help make dynamic forms
*/


//Get a description of a table
function getFields($prefix, $tableName, $addPasswordConfirm = false) {
	$fields = array();
	$sql = "DESC ". sqlEscape($prefix. $tableName);
	$result = sqlQuery($sql);

	while($field = sqlFetchAssoc($result)) {
		$field['Table'] = $tableName;
		
		$field['Date'] = strpos($field['Type'], 'date') !== false;
		
		$field['Numeric'] = strpos($field['Type'], 'enum') === false
						&& (strpos($field['Type'], 'int') !== false
						 || strpos($field['Type'], 'double') !== false
						 || strpos($field['Type'], 'float') !== false);
		
		$fields[$field['Field']] = $field;
		
		if ($field['Field'] == 'password' && $addPasswordConfirm) {
			$fields[$field['Field']]['Type'] = 'password';
			$fields['password_reconfirm'] = $fields[$field['Field']];
			$fields['password_reconfirm']['Table'] = '';
			$fields['password_reconfirm']['Type'] = 'password_reconfirm';
		}
	}
	
	return $fields;
}

	
//A function to help with saving the returns from these fields
function addFieldToSQL(&$sql, $table, $field, $values, $editing, $details = array()) {
	
	if ($sql) {
		$sql .= ",";
	} elseif ($editing) {
		$sql = "
		UPDATE ". $table. " SET";
	} else {
		$sql = "
		REPLACE INTO ". $table. " SET";
	}
	
	$sql .= "
			". $field. " = ";
	
	//Attempt to save empty dates correctly in strict mode
	if ($details['Date'] && strlen((string) $values[$field] < 8)) {
		if ($details['Null'] == 'Yes') {
			$values[$field] = '';
		} else {
			$values[$field] = '0000-00-00';
		}
	}
	
	//Convert empty strings to NULLs if possible
	if ($values[$field] === '' && $details['Null'] == 'Yes') {
		$sql .= "NULL";
	
	//Otherwise convert empty strings to 0s for non-string fields
	} elseif (!$values[$field] && $details['Numeric']) {
		$sql .= "0";
	
	//Make sure Numeric values are actually numeric
	} elseif ($details['Numeric']) {
		$sql .= (int) $values[$field];
	
	//Otherwise use sqlEscape
	} else {
		$sql .= "'". sqlEscape($values[$field]). "'";
	}
}




function getContentTypes($contentType = false, $onlyCreatable = false) {
	
	$key = array();
	if ($contentType) {
		$key['content_type_id'] = $contentType;
	}
	if ($onlyCreatable) {
		$key['is_creatable'] = true;
	}
	return getRowsArray('content_types', array('content_type_id', 'content_type_name_en', 'default_layout_id'), $key, 'content_type_id');
}

function getContentTypeName($cType) {
	return getRow('content_types', 'content_type_name_en', $cType);
}

function getTemplateDetails($layoutId) {
	$sql = "
		SELECT
			layout_id,
			family_name,
			file_base_name,
			CONCAT(file_base_name, '.tpl.php') AS filename,
			CONCAT('L', IF (layout_id < 10, LPAD(CAST(layout_id AS CHAR), 2, '0'), CAST(layout_id AS CHAR)), ' ', name) AS id_and_name,
			name,
			content_type,
			status,
			skin_id,
			css_class,
			bg_image_id,
			bg_color,
			bg_position,
			bg_repeat
		FROM ". DB_NAME_PREFIX. "layouts
		WHERE layout_id = ". (int) $layoutId;
	$result = sqlQuery($sql);
	return sqlFetchAssoc($result);
}


/*	Content Functions	*/

function isSpecialPage($cID, $cType) {
	$specialPage = array_search($cType. '_'. $cID, cms_core::$specialPages);
	
	if ($specialPage !== false) {
		$specialPage = explode('`', $specialPage, 2);
		return $specialPage[0];
	} else {
		return false;
	}
}

function getContentLang($cID, $cType = false) {
	return getRow('content_items', 'language_id', array('id' => $cID, 'type' => ifNull($cType, 'html')));
}

//Try to work out what content item is being accessed
//n.b. linkToItem() and resolveContentItemFromRequest() are essentially opposites of each other...
function resolveContentItemFromRequest(&$cID, &$cType, &$redirectNeeded, &$aliasInURL, $get, $request, $post) {
	$aliasInURL = '';
	$equivId = $cID = $cType = $reqLangId = $redirectNeeded = $languageSpecificDomain = $hierarchicalAliasInURL = false;
	$adminMode = !empty($_SESSION['admin_logged_into_site']) && checkPriv();
	
	//Check that we're on the domain we're expecting.
	//If not, flag that any links we generate should contain the full path and domain name.
	if (!empty($_SERVER['HTTP_HOST'])) {
		if ($adminMode) {
			if (adminDomain() != $_SERVER['HTTP_HOST']) {
				cms_core::$wrongDomain =
				cms_core::$mustUseFullPath = true;
			}
		} else {
			if (primaryDomain() != $_SERVER['HTTP_HOST']) {
				cms_core::$wrongDomain =
				cms_core::$mustUseFullPath = true;
			}
		}
	}
	
	//If there is a menu id in the request, try to get the Content Item from that
	if (!empty($request['mID']) && ($menu = getContentFromMenu($request['mID'], 2))) {
		$cID = $menu['equiv_id'];
		$cType = $menu['content_type'];
		langEquivalentItem($cID, $cType);
		
		//Visitors shouldn't see this type of link, so redirect them to the correct URL
		if (!$adminMode) {
			$redirectNeeded = 301;
		}
		return;
	}
	
	$multilingual = getNumLanguages() > 1;
	
	//Check for a language-specific domain. If it is being used, get the language from that.
	if ($multilingual) {
		foreach (cms_core::$langs as $langId => $lang) {
			if ($lang['domain']
			 && $lang['domain'] == $_SERVER['HTTP_HOST']) {
				$languageSpecificDomain = true;
				$reqLangId = $langId;
				break;
			}
		}
	}
	
	//Check for a requested page in the GET request
	if (!empty($get['cID'])) {
		$aliasInURL = $get['cID'];
		
		//If we see any slashes in the alias used in the URL, any links we generate will need to have the full path.
		if (strpos($aliasInURL, '/') !== false) {
			cms_core::$mustUseFullPath = true;
		}
	}
	//Also check the POST request; use this instead if we see it
	if (!empty($post['cID'])) {
		$aliasInURL = $post['cID'];
	}
	
	//Attempt to work out what content item we're on, and break out of this logic as soon as it's resolved
	do {
	
		if (!$reqLangId && !$aliasInURL) {
			//Show one of the home pages if there's nothing in the request and no language specific domain
			$equivId = cms_core::$homeEquivId;
			$cType = cms_core::$homeCType;
	
		} else {
		
			//Check for slashes in the alias
			if (strpos($aliasInURL, '/') !== false) {
			
				$hierarchicalAliasInURL = trim($aliasInURL, '/');
				$slashes = explode('/', $hierarchicalAliasInURL);
			
				//For multilingual sites, check the first part of the URL for the requested language code.
				//(Except if a language-specific domain was used above, in which case skip this.)
				if ($multilingual
				 && !$reqLangId
				 && !empty($slashes[0])
				 && isset(cms_core::$langs[$slashes[0]])) {
					$reqLangId = array_shift($slashes);
				}
			
				//Use the last bit of the URL to find the page.
				$aliasInURL = array_pop($slashes);
			
				//Anything in the middle are the other aliases in the menu tree; currently these are just visual
				//and are ignored.
		
			} else {
				//Check the request for a numeric cID, a string alias, and a language code separated by a comma.
				$aliasInURL = explode(',', $aliasInURL);
		
				if (!empty($aliasInURL[1])) {
					//Don't allow a language specific domain name *and* the language code in a comma
					if ($languageSpecificDomain) {
						$redirectNeeded = 301;
					}
				
					$reqLangId = $aliasInURL[1];
				}
			
				$aliasInURL = $aliasInURL[0];
			}
		
			//Catch the case where the language id is in the URL, and nothing else.
			//This should be a link to the home page in that language.
			if ($aliasInURL && isset(cms_core::$langs[$aliasInURL])) {
				$reqLangId = $aliasInURL;
				$aliasInURL = false;
			
				//N.b. this is not a valid URL is there is only one language on a site!
				if (count(cms_core::$langs) < 2) {
					$redirectNeeded = 301;
				}
			}
		
			//Language codes with no alias means the home page for that language
			if ($reqLangId && !$aliasInURL) {
			
				langSpecialPage('zenario_home', $cID, $cType, $reqLangId, $languageMustMatch = true, $skipPermsCheck = true);
			
				//Slightly different logic depending on whether we are allowed slashes in the alias or not
					//If so, this is a valid URL and we don't need to change it
					//If not, it's not a valid URL, and we should rewrite it to show the alias.
				//Also, language specific domains should trigger the same logic.
				if (!$languageSpecificDomain && !setting('mod_rewrite_slashes')) {
					$redirectNeeded = 301;
				}
			
				break;
		
			//Link by numeric cID
			} elseif (is_numeric($aliasInURL)) {
				$cID = (int) $aliasInURL;
			
				if (!empty($request['cType'])) {
					$cType = $request['cType'];
				} else {
					$cType = 'html';
				}
			
				//Allow numeric cIDs with language codes, but redirect them to the correct URL
				if ($reqLangId) {
					langEquivalentItem($cID, $cType, $reqLangId);
					$redirectNeeded = 301;
				}
			
				//We know both the Content Item and language from the numeric id,
				//so we can stop straight away without looking up anything else.
				break;
		
			//Link by tag id
			} elseif (getCIDAndCTypeFromTagId($cID, $cType, $aliasInURL)) {
				//Allow tag ids with language codes, but redirect them to the correct URL
				if ($reqLangId && !$languageSpecificDomain) {
					langEquivalentItem($cID, $cType, $reqLangId);
					$redirectNeeded = 301;
				}
			
				//Again we can stop straight away as we know the specific Content Item
				break;
		
			//Link by an alias
			} else {
				//Attempt to look up a page with this alias
				$sql = "
					SELECT id, type, equiv_id, language_id
					FROM ". DB_NAME_PREFIX. "content_items
					WHERE alias = '". sqlEscape($aliasInURL). "'";
			
				//If an admin is logged in, any drafts/hidden content items should effect which language they get directed to
				//If not, only published pages should effect the logic.
				if ($adminMode) {
					$sql .= "
					  AND status NOT IN ('trashed', 'deleted')";
				} else {
					$sql .= "
					  AND status IN ('published_with_draft', 'published')";
				}
			
				$sql .= "
					ORDER BY language_id";
			
				//If there was a language code in the URL, focus on the language that we're looking for
				if ($reqLangId) {
					$sql .= " = '". sqlEscape($reqLangId). "' DESC, language_id";
				}
			
				//Get two rows, so we can tell if this alias was unique
				$sql .= "
					LIMIT 2";
			
				$result = sqlQuery($sql);
				if ($row = sqlFetchAssoc($result)) {
					$row2 = sqlFetchAssoc($result);
				}
			
				//If the alias was not found, then we can't resolve it to a Content Item
				if (!$row) {
					$cID = false;
					$cType = false;
					break;
			
				//If there was only one result for that alias, we can use this straight away
				//If there was a language specified and there was only one match for that language, we're also good to go
				} elseif ($row && (
					!$row2
				 || ($reqLangId && $reqLangId == $row['language_id'] && $reqLangId != $row2['language_id'])
				)) {
					$cID = $row['id'];
					$cType = $row['type'];
				
					//Redirect the case where we resolved a match, but the alias didn't actually match the language code
					if ($reqLangId && $reqLangId != $row['language_id']) {
						langEquivalentItem($cID, $cType, $reqLangId);
						$redirectNeeded = 301;
				
					//If this was a hierarchical URL, but hierarchical URLs are disabled,
					//we should redirect back to a page with a flat URL
					} elseif ($hierarchicalAliasInURL !== false && !setting('mod_rewrite_slashes')) {
						$redirectNeeded = 301;
				
					//If this was a hierarchical URL, check the URL was correct and redirect if not
					} elseif ($hierarchicalAliasInURL !== false) {
						$hierarchicalAlias = addHierarchicalAlias($row['equiv_id'], $row['type'], $row['language_id'], $aliasInURL);
				
						if ($hierarchicalAliasInURL != $hierarchicalAlias
						 && $hierarchicalAliasInURL != $row['language_id']. '/'. $hierarchicalAlias) {
							$redirectNeeded = 301;
						}
					}
			
					break;
			
				} else {
					//Otherwise, just note down which translation chain was found, and resolve to the correct language below
					$cID = false;
					$equivId = $row['equiv_id'];
					$cType = $row['type'];
				}
			}
		}
	
	
		//If we reach this point, we've found a translation to show, but don't know which language to show it in.
		$acptLangId = $acptLangId2 = false;
		if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			//Get the Visitor's preferred languae from their browser.
				//Note: as of 6.1 we only look at the first choice.
			$acptLangId = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'], 2);
			$acptLangId = explode(';', $acptLangId[0], 2);
			$acptLangId = strtolower(trim($acptLangId[0]));
	
			//Also look for the first part of the language code (before the hyphen) as a fallback
			$acptLangId2 = explode('-', $acptLangId, 2);
			$acptLangId2 = $acptLangId2[0];
		}
	
		//Look at the languages that we have for the requested translation.
		$sql = "
			SELECT c.id, c.type, c.equiv_id, c.language_id, c.alias, l.detect, l.detect_lang_codes
			FROM ". DB_NAME_PREFIX. "content_items AS c
			INNER JOIN ". DB_NAME_PREFIX. "languages AS l
			   ON c.language_id = l.id
			WHERE c.equiv_id = ". (int) $equivId. "
			  AND c.type = '". sqlEscape($cType). "'";
			
			//If an admin is logged in, any drafts/hidden content items should effect which language they get directed to
			//If not, only published pages should effect the logic.
			if ($adminMode) {
				$sql .= "
				  AND c.status NOT IN ('trashed', 'deleted')";
			} else {
				$sql .= "
				  AND c.status IN ('published_with_draft', 'published')";
			}
			
			$sql .= "
			ORDER BY
				c.language_id = '". sqlEscape(cms_core::$defaultLang). "' DESC,
				c.language_id";
	
		$match = false;
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)) {
			//If this language should be auto-detected, get a list of language codes that it matches to
			if ($row['detect']) {
				$langCodes = array_flip(explodeAndTrim($row['language_id']. ','. $row['detect_lang_codes']));
			}
		
			//If there is a match, use that and stop here
			if ($row['detect'] && $acptLangId && !empty($langCodes[$acptLangId])) {
				$match = $row;
				break;
		
			//If there is a match on the first part of the language code, remember this one as a fallback
			} elseif ($row['detect'] && $acptLangId2 && !empty($langCodes[$acptLangId2])) {
				$match = $row;
		
			//If nothing else matches, make sure we go to the default language
			//(or the first found language if there was no default) as a fallback.
			} elseif (!$match) {
				$match = $row;
			}
		}
	
		if ($match) {
			$cID = $match['id'];
		
			//If there was a requested alias, which was different than the resolved alias, we should do a redirect.
			if ($aliasInURL && $aliasInURL != $match['alias']) {
				$redirectNeeded = 301;
		
			//If there was a requested language, which was different than the resolved language, we should do a redirect.
			} elseif ($reqLangId && $reqLangId != $match['language_id']) {
				$redirectNeeded = 301;
		
			//For multilingual sites, if the language code was not in the URL and we had an ambiguous link, we should do a redirect.
			//But make it a 302 redirect, as we don't want to discourage Search Engines from listing the URLs of landing pages.
			} elseif (!$reqLangId && $multilingual) {
				$redirectNeeded = 302;
			}
	
		} else {
			$cID = false;
			$cType = false;
		}
	} while (false);
	
	
	//Check to see if the user requested the page shown using a different language
	if ($cID !== false
	 && isset($_GET['visLang'])
	 && cms_core::$visLang === null) {
		$visLang = $_GET['visLang'];
		
		//Don't allow this if the language requested is not used on the site,
		//or if there's a real translation for the page in the language requested that's now visible.
		if (!isset(cms_core::$langs[$visLang])
		 || langEquivalentItem($cID, $cType, $visLang, true)) {
			unset($_GET['visLang']);
			$redirectNeeded = 301;
		
		} else {
			cms_core::$visLang = $visLang;
		}
	}
}

//Check to see if a Content Item exists, and the current visitor/user/admin can see a Content Item
//(Admins can see all Content Items that exist)
function checkPerm($cID, $cType = 'html', $requestVersion = false) {
	$content = false;
	return (bool) checkPermAndGetShowableContent($content, $cID, $cType, $requestVersion);
}

//Gets the correct version of a Content Item to show someone, or false if the do not have any access.
//(Works exactly like checkPerm() above, except it will return a version number.)
function getShowableVersion($cID, $cType = 'html') {
	$content = false;
	return checkPermAndGetShowableContent($content, $cID, $cType, $requestVersion = false);
}

function requestVarMergeField($name) {
	if (empty(cms_core::$vars)) {
		require editionInclude('checkRequestVars');
	}
	return require editionInclude('requestVarMergeField');
}

//Check to see if a Content Item exists, and the current visitor/user/admin can see a Content Item
//Works like checkPerm() above, except that it will return a permissions error code
//It also looks up some details on the Content Item
function getShowableContent(&$content, &$version, $cID, $cType = 'html', $requestVersion = false, $checkRequestVars = false, $adminsSee400Errors = false) {

	if ($checkRequestVars) {
		//Look variables such as userId, locationId, etc., in the request
		if (!require editionInclude('checkRequestVars')) {
			if ($adminsSee400Errors || !checkPriv()) {
				//Handle the case where the current visitor does not have the rights to see something requested in the core variables
				if (empty($_SESSION['extranetUserID'])) {
					//If the current visitor is not logged in, sent a 401 error
					return ZENARIO_401_NOT_LOGGED_IN;
				} else {
					//If there is a visitor not logged in, sent a 403 error
					return ZENARIO_403_NO_PERMISSION;
				}
			}
		}
	}
	
	$versionNumber = checkPermAndGetShowableContent($content, $cID, $cType, $requestVersion, $adminsSee400Errors);
	
	if ($versionNumber && is_numeric($versionNumber)) {
		$versionColumns = array(
			'version',
			'title', 'description', 'keywords',
			'layout_id', 'css_class', 'feature_image_id',
			'publication_date', 'published_datetime', 'created_datetime',
			'rss_slot_name', 'rss_nest');
		
		$version = getRow('content_item_versions', $versionColumns, array('id' => $content['id'], 'type' => $content['type'], 'version' => $versionNumber));
		$versionNumber = true;
	}
	
	return $versionNumber;
}


function checkPermAndGetShowableContent(&$content, $cID, $cType, $requestVersion, $adminsSee400Errors = false) {
	// Returns the version of this content item which should normally be returned
	if ($cID
	 && $cType
	 && ($content = sqlFetchAssoc("
			SELECT
				equiv_id, id, type, language_id, alias,
				visitor_version, admin_version, status, lock_owner_id
			FROM ". DB_NAME_PREFIX. "content_items
			WHERE id = ". (int) $cID. "
			  AND type = '". sqlEscape($cType). "'")
		)
	 && ($chain = sqlFetchAssoc("
			SELECT equiv_id, type, privacy, smart_group_id
			FROM ". DB_NAME_PREFIX. "translation_chains
			WHERE equiv_id = ". (int) $content['equiv_id']. "
			  AND type = '". sqlEscape($cType). "'")
	)) {
		//If we are in admin mode, allow anything that exists to be shown
		if (checkPriv()) {
			
			//If no specific version was requested, use the admin version
			if (!(int) $requestVersion) {
				$requestVersion = (int) $content['admin_version'];
			}
			
			//Check the requested version exists
			if (!$requestVersion
			 || !checkRowExists('content_item_versions', array('id' => $content['id'], 'type' => $content['type'], 'version' => $requestVersion))) {
				return false;
			}
			
			//If the $adminsSee400Errors option is set, still check the privacy settings even though an admin is logged in
			$status = true;
			if ($adminsSee400Errors) {
				$privacySettings = false;
				
				switch ($chain['privacy']) {
					case 'call_static_method':
						$privacySettings =
							getRow('translation_chain_privacy', true, array(
								'equiv_id' => $content['equiv_id'],
								'content_type' => $cType));
				}
			
				$status = checkItemPrivacy($chain, $privacySettings, $cID, $cType, $requestVersion);
			}
			
			return $status? $requestVersion: $status;
		
		//If we are in visitor mode, only show a published version
		} elseif (isPublished($content['status']) && ($cVersion = (int) $content['visitor_version'])) {
			
			$privacySettings = false;
			
			switch ($chain['privacy']) {
				case 'call_static_method':
					$privacySettings =
						getRow('translation_chain_privacy', true, array(
							'equiv_id' => $content['equiv_id'],
							'content_type' => $cType));
				
				case 'send_signal':
					cms_core::$canCache = false;
			}
			
			$status = checkItemPrivacy($chain, $privacySettings, $cID, $cType, $cVersion);
			
			return $status? $cVersion: $status;
		}
	}
	
	return false;
}

function checkItemPrivacy($privacy, $privacySettings, $cID, $cType, $cVersion) {
	
	//Check if a user is logged in.
	$userId = false;
	if (!empty($_SESSION['extranetUserID'])
	 && ($userId = (int) $_SESSION['extranetUserID'])) {
	
	//If not, any permission that needs an account should fail with a 401 error.
	} else {
		switch ($privacy['privacy']) {
			case 'logged_in':
			case 'group_members':
			case 'with_role':
			case 'in_smart_group':
			case 'logged_in_not_in_smart_group':
				return ZENARIO_401_NOT_LOGGED_IN;
		}
	}

	switch ($privacy['privacy']) {
		case 'public':
		case 'logged_in': //Already checked above
			return true;
		
		case 'logged_out':
			return $userId? ZENARIO_403_NO_PERMISSION : true;
	
		case 'group_members':
		case 'with_role':
			
			if ($privacy['privacy'] == 'group_members') {
				//Try to get this user's groups
				$linkTo = 'group';
				$linkToIds = getUserGroups($userId);
			
			} elseif ($privacy['privacy'] == 'with_role' && ($ZENARIO_ORGANIZATION_MANAGER_PREFIX = getModulePrefix('zenario_organization_manager'))) {
				//Try to get this user's roles
				$linkTo = 'role';
				$linkToIds =
					arrayValuesToKeys(
						sqlFetchValues("
							SELECT DISTINCT role_id
							FROM ". DB_NAME_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link
							WHERE user_id = ". (int) $userId
						)
					);
			
			} else {
				return false;
			}
		
			//Look up all of the groups for this content item or slide.
			//If the user has one of the groups, allow access.
			if (!empty($linkToIds)) {
				
				$sql = "
					SELECT link_to_id
					FROM `". DB_NAME_PREFIX. "group_link`
					WHERE link_to = '". sqlEscape($linkTo). "'";
				
				if (!empty($privacy['equiv_id']) && !empty($privacy['type'])) {
					$sql .= "
						  AND link_from = 'chain'
						  AND link_from_id = ". (int) $privacy['equiv_id']. "
						  AND link_from_char = '". sqlEscape($cType). "'";
				
				} elseif (!empty($privacy['slide_id'])) {
					$sql .= "
						  AND link_from = 'slide'
						  AND link_from_id = ". (int) $privacy['slide_id'];
				
				} else {
					return false;
				}
				
				foreach (sqlFetchValues($sql) as $groupId) {
					if (!empty($linkToIds[$groupId])) {
						return true;
					}
				}		
			}
		
			//If they don't have access return a 403 error.
			return ZENARIO_403_NO_PERMISSION;
		
		case 'in_smart_group':
			return checkUserIsInSmartGroup($privacy['smart_group_id'], $userId)? true : ZENARIO_403_NO_PERMISSION;
		
		case 'logged_in_not_in_smart_group':
			return !checkUserIsInSmartGroup($privacy['smart_group_id'], $userId)? true : ZENARIO_403_NO_PERMISSION;
		
		//Call a module's static method, or send the eventCheckContentItemPermission() signal,
		//to decide whether the current user should see this content item
		case 'call_static_method':
		case 'send_signal':
			
			$status = ZENARIO_404_NOT_FOUND;
			
			if ($privacy['privacy'] == 'call_static_method') {
				if ($privacySettings) {
					if ((inc($privacySettings['module_class_name']))
					 && (method_exists($privacySettings['module_class_name'], $privacySettings['method_name']))) {
					
						$status = call_user_func(
							array($privacySettings['module_class_name'], $privacySettings['method_name']),
							$privacySettings['param_1'], $privacySettings['param_2']);
					}
				}
			
			} else {
				if ($results = sendSignal('eventCheckContentItemPermission', array('userId' => $userId, 'cID' => $cID, 'cType' => $cType, 'cVersion' => $cVersion))) {
					foreach ($results as $result) {
						if ($result !== false) {
							$status = $result;
						}
					}
				}
			}
			
			//Catch the case where the PHP script above sends a 401 error but a user is logged in,
			//and convert it to a 403 error
			if ($status === ZENARIO_401_NOT_LOGGED_IN && $userId) {
				return ZENARIO_403_NO_PERMISSION;
			}
			
			return $status? true: $status;
	}
	
	return false;
}

function setShowableContent(&$content, &$version) {
	cms_core::$equivId = $content['equiv_id'];
	cms_core::$cID = $content['id'];
	cms_core::$cType = $content['type'];
	cms_core::$alias = $content['alias'];
	cms_core::$status = $content['status'];
	cms_core::$langId = $content['language_id'];
 	
	//Set the visitor's language differently, depending on whether we're showing this
	//page for another language
	if (cms_core::$visLang !== null
	 && cms_core::$visLang != cms_core::$langId) {
		$_SESSION['user_lang'] = cms_core::$visLang;
	} else {
		$_SESSION['user_lang'] = cms_core::$visLang = cms_core::$langId;
	}
	
	cms_core::$cVersion = $version['version'];
	cms_core::$adminVersion = $content['admin_version'];
	cms_core::$visitorVersion = $content['visitor_version'];
	
	cms_core::$pageTitle = $version['title'];
	cms_core::$pageDesc = $version['description'];
	cms_core::$pageImage = $version['feature_image_id'];
	cms_core::$pageKeywords = $version['keywords'];
	
	cms_core::$itemCSS = $version['css_class'];
	cms_core::$date = ifNull($version['publication_date'], $version['published_datetime'], $version['created_datetime']);
	cms_core::$rss = $version['rss_nest']. '_'. $version['rss_slot_name'];
	
	cms_core::$isDraft =
		$version['version'] == $content['admin_version'] && (
			$content['status'] == 'first_draft'
		 || $content['status'] == 'published_with_draft'
		 || $content['status'] == 'hidden_with_draft'
		 || $content['status'] == 'trashed_with_draft');
	
	cms_core::$locked = $content['lock_owner_id'] && $content['lock_owner_id'] != $_SESSION['admin_userid'] ?? false;

	//Given what we know, find a Layout and a Template Family as best we can.
	//Give priority to matching Layout ids, matching family names, active Layouts,
	//and then html type Layouts in that order
	$sql = "
		SELECT
			family_name, layout_id, file_base_name, skin_id, css_class,
			cols, min_width, max_width, fluid, responsive
		FROM ". DB_NAME_PREFIX. "layouts
		ORDER BY
			content_type = '". sqlEscape(cms_core::$cType). "' DESC";
	
	if (($layoutId = $version['layout_id']) || ($layoutId = getRow('content_types', 'default_layout_id', array('content_type_id' => cms_core::$cType)))) {
		$sql .= ",
			layout_id = ". (int) $layoutId. " DESC";
	}
	
	$sql .= ",
			layout_id";
	
	$result = sqlQuery($sql);
	$template = sqlFetchAssoc($result);
	
	cms_core::$layoutId = $template['layout_id'];
	cms_core::$cols = (int) $template['cols'];
	cms_core::$minWidth = (int) $template['min_width'];
	cms_core::$maxWidth = (int) $template['max_width'];
	cms_core::$fluid = (bool) $template['fluid'];
	cms_core::$responsive = (bool) $template['responsive'];
	cms_core::$templateCSS = $template['css_class'];
	cms_core::$templateFamily = $template['family_name'];
	cms_core::$templateFileBaseName = $template['file_base_name'];
	cms_core::$templateFilename = $template['file_base_name']. '.tpl.php';
	cms_core::$templatePath = zenarioTemplatePath(cms_core::$templateFamily);
	
	//This constant was used in some old Template Files.
	define('TEMPLATE_PATH', cms_core::$templateFamily);
	
	if ((cms_core::$skinId = templateSkinId($template, true))
	 && ($skin = getSkinFromId(cms_core::$skinId))) {
		cms_core::$skinName = $skin['name'];
		cms_core::$skinCSS = $skin['css_class'];
	}
}

function contentItemAlias($cID, $cType) {
	return getRow('content_items', 'alias', array('id' => $cID, 'type' => $cType));
}

function contentItemTemplateId($cID, $cType, $cVersion = false) {
	
	if (!$cVersion) {
		$cVersion = getLatestVersion($cID, $cType);
	}
	
	return getRow('content_item_versions', 'layout_id', array('id' => $cID, 'type' => $cType, 'version' => $cVersion));
}

function templateSkinId($template, $fallback = false) {
	
	if (!is_array($template)) {
		$template = getRow('layouts', array('family_name', 'skin_id'), $template);
	}
	
	if ($template) {
		if ($template['skin_id']) {
			return $template['skin_id'];
		
		} elseif ($skinId = getRow('template_families', 'skin_id', array('family_name' => $template['family_name']))) {
			return $skinId;
		
		} elseif ($fallback) {
			return getRow('skins', 'id', array('family_name' => $template['family_name'], 'missing' => 0));
		}
	}
	return false;
}


function getLatestContentID($cType) {
	return (int) selectMax('content_items', 'id', array('type' => $cType));
}

function getPublishedVersion($cID, $cType) {
	return getRow('content_items', 'visitor_version', array('id' => $cID, 'type' => $cType));
}

function getLatestVersion($cID, $cType) {
	return getRow('content_items', 'admin_version', array('id' => $cID, 'type' => $cType));
}

function getAppropriateVersion($cID, $cType) {
	return getRow('content_items', checkPriv()? 'admin_version' : 'visitor_version', array('id' => $cID, 'type' => $cType));
}


function getItemTitle($cID, $cType, $cVersion = false) {
	
	if (!$cVersion) {
		$cVersion = getLatestVersion($cID, $cType);
	}
	
	if ($cID == cms_core::$cID && $cType == cms_core::$cType && $cVersion == cms_core::$cVersion) {
		return cms_core::$pageTitle;
	} else {
		return getRow('content_item_versions', 'title', array('id' => $cID, 'type' => $cType, 'version' => $cVersion));
	}
}

function getItemDescription($cID, $cType, $cVersion) {
	return getRow('content_item_versions', 'description', array('id' => $cID, 'type' => $cType, 'version' => $cVersion));
}

function formatTagFromTagId($tagId) {
	$cID = $cType = false;
	if (getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
		return formatTag($cID, $cType);
	} else {
		return false;
	}
}

function formatTag($cID, $cType, $alias = -1, $langId = false, $neverAddLanguage = false) {
	$content = false;
	$friendlyURL = '';
	
	if ($alias === -1) {
		$content = getRow('content_items', array('alias', 'language_id'), array('id' => $cID, 'type' => $cType));
		$alias = $content['alias'];
	}
	
	if ($alias) {
		$friendlyURL = '/'. $alias;
	}
	
	if (!$neverAddLanguage
	 && getNumLanguages() > 1) {
		if (!$langId) {
			if (!$content) {
				$content = getRow('content_items', array('alias', 'language_id'), array('id' => $cID, 'type' => $cType));
			}
			$langId = $content['language_id'];
		}
		
		$friendlyURL .= ','. $langId;
	}
	
	return $cType. '_'. $cID. $friendlyURL;
}


function cutTitle($title, $max_title_length = 20, $cutText = '...') {
	if (strlen($title) > $max_title_length) {
		return mb_substr($title, 0, floor($max_title_length/2)). $cutText. mb_substr($title, -floor($max_title_length/2));
	} else {
		return $title;
	}
}



function updateShortChecksums() {
	
	//Attempt to fill in any missing short checksums
	$sql = "
		UPDATE IGNORE ". DB_NAME_PREFIX. "files
		SET short_checksum = SUBSTR(checksum, 1, ". (int) setting('short_checksum_length'). ")
		WHERE short_checksum IS NULL";
	sqlUpdate($sql);	
	
	//Check for a unique key error (i.e. one or more short checksums were left as null)
	if (checkRowExists('files', array('short_checksum' => null))) {
		
		//Handle the problem by increasing the short checksum length and trying again
		setSetting('short_checksum_length', 1 + (int) setting('short_checksum_length'));
		updateShortChecksums();
	}
}


//Read a line from admin_phrase_codes/en.txt
//Either read the next full line after a given position, or if $pos is not specified, the next line from the last position
function adminPhraseLine($lang, &$f, &$code, &$text, $pos = false) {
	$code = $text = '';
	
	if ($pos !== false) {
		fseek($f, $pos);
		fgets($f);
	}
	
	if ($line = fgets($f)) {
		$line = explode('|', $line, 2);
		$code = $line[0];
		$text = trim($line[1], "\t\n\r\0\x0B");
		
		return true;
	} else {
		return false;
	}
}

function getAdminPhraseCode($target, $lang) {
	return require funIncPath(__FILE__, __FUNCTION__);
}

function getAdminDetails($admin_id) {
	
	if ($details = getRow('admins', true, $admin_id)) {
		//Old key/value format for backwards compatability with old code
		foreach ($details as $key => $value) {
			$details['admin_'. $key] = $value;
		}
		$details['disable'] = (int) empty($row['perm_manage']);
	}
	
	return $details;
}


//Old name for adminPhrase()
function getPhrase($code, $replace = false) {
	return adminPhrase($code, $replace);
}

function adminPhrase($code, $replace = false, $moduleClass = false) {
	
	if ($moduleClass) {
		return phrase($code, $replace, $moduleClass);
	}
	
	//No support for a multilingual admin interface yet.
	$lang = 'en';
	
	if ($replace === false) {
		$replace = array();
	}
	
	//Some phrases are now being simply hard-coded. However we still wish to call adminPhrase on these
	//hardcoded phrases for tracking purposes
	//Ignore anything that does not start with an underscore
	if ($lang == 'en' && substr($code, 0, 1) != '_') {
		$phrase = $code;
	} else {
		$phrase = getAdminPhraseCode($code, $lang);
	}
	
	if (empty($replace)) {
		return $phrase;
	} else {
		foreach ($replace as $key => &$replacement) {
			if (!is_array($replacement)) {
				$phrase = str_replace('[['. $key. ']]', $replacement, $phrase);
			}
		}
		return $phrase;
	}
}

function nAdminPhrase($text, $pluralText = false, $n = 1, $replace = array(), $zeroText = false) {
	
	if (!is_array($replace)) {
		$replace = array();
	}
	if (!isset($replace['count'])) {
		$replace['count'] = $n;
	}
	
	if ($zeroText !== false && $n === 0) {
		return adminPhrase($zeroText, $replace);
	
	} elseif ($pluralText !== false && $n !== 1) {
		return adminPhrase($pluralText, $replace);
	
	} else {
		return adminPhrase($text, $replace);
	}
}

function secondsToAdminPhrase($seconds) {
	if (!class_exists('DateTime')) {
		$a = array(
			array('second', '[[n]] seconds', (int) $seconds));
	} else {
		$zero = new DateTime('@0');
		$dt = $zero->diff(new DateTime('@'. (int) $seconds));
		$a = array(
			array('second', '[[n]] seconds', (int) $dt->format('%s')),
			array('minute', '[[n]] minutes', (int) $dt->format('%i')),
			array('hour', '[[n]] hours', (int) $dt->format('%h')), 
			array('day', '[[n]] days', (int) $dt->format('%a')));
	}
	
	$t = '';
	$s = '';
	foreach ($a as $v) {
		if ($v = nAdminPhrase($v[0], $v[1], $v[2], array('n' => $v[2]), '')) {
			$t = $v. $s. $t;
			
			if ($s === '') {
				$s = adminPhrase(' and ');
			} else {
				$s = adminPhrase(', ');
			}
		}
	}
	
	return $t;
}


function exitIfUploadError($moduleClass = false) {
	$error = $_FILES['Filedata']['error'] ?? false;
	switch ($error) {
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			echo adminPhrase('Your file was too large to be uploaded.', false, $moduleClass);
			exit;
		case UPLOAD_ERR_PARTIAL:
		case UPLOAD_ERR_NO_FILE:
			echo adminPhrase('There was a problem whilst uploading your file.', false, $moduleClass);
			exit;
		case UPLOAD_ERR_NO_TMP_DIR:
			echo 'UPLOAD_ERR_NO_TMP_DIR';
			exit;
		case UPLOAD_ERR_CANT_WRITE:
			echo 'UPLOAD_ERR_CANT_WRITE';
			exit;
		case UPLOAD_ERR_EXTENSION:
			echo 'UPLOAD_ERR_EXTENSION';
			exit;
	}
}

//Returns a Content Item that points to a Menu Node
//Note that this will probably be the Content Item in the Primary Language
function getContentFromMenu($mID, $recurseLimit = 0) {
	
	if ($menu = getRow('menu_nodes', array('id', 'equiv_id', 'content_type', 'parent_id'), $mID)) {
		if ($menu['equiv_id'] && $menu['content_type']) {
			$menu['content_id'] = $menu['equiv_id'];
			return $menu;
		
		} elseif ($recurseLimit) {
			return getContentFromMenu($menu['parent_id'], --$recurseLimit);
		}
	}
	
	return false;
}

function getMenuItemFromContent($cID, $cType, $fetchSecondaries = false, $sectionId = false, $allowGhosts = false, $fetchEverything = false) {
	if ($cID && $cType) {
		$sql = "
			SELECT
				m.id AS mID,";
		
		if ($fetchEverything) {
			$sql .= "
				m.*, t.*";
		
		} else {
			$sql .= "
				m.id,
				m.section_id,
				c.language_id,
				t.name,
				m.redundancy, 
				m.parent_id,
				t.ext_url, 
				m.ordinal,
				m.hide_private_item,
				m.invisible,
				m.rel_tag,
				m.css_class";
		}
		
		$sql .= "
			FROM ". DB_NAME_PREFIX. "content_items AS c
			INNER JOIN ". DB_NAME_PREFIX. "menu_nodes AS m
			   ON m.equiv_id = c.equiv_id
			  AND m.content_type = c.type
			  AND m.target_loc = 'int'
			". ($allowGhosts? "LEFT" : "INNER"). " JOIN ". DB_NAME_PREFIX. "menu_text AS t
			   ON t.menu_id = m.id
			  AND t.language_id = c.language_id
			INNER JOIN ". DB_NAME_PREFIX. "menu_text AS mt
			   ON mt.menu_id = m.id
			  AND mt.language_id = c.language_id
			WHERE c.id = ". (int) $cID. "
			  AND c.type = '" . sqlEscape($cType) . "'";
		
		if ($sectionId) {
			$sql .= "
			  AND m.section_id = ". (int) menuSectionId($sectionId);
		}
		
		$sql .= "
			ORDER BY m.redundancy = 'primary' DESC";
		
		if (!$fetchSecondaries) {
			$sql .= "
				LIMIT 1";
		}
		
		if ($fetchSecondaries) {
			return sqlFetchAssocs($sql);
		
		} else {
			return sqlFetchAssoc($sql);
		}
	} else {
		return false;
	}
}

//Get the content item in the menu above this one
function getContentItemAbove(&$cID, &$cType, $equivId, $langId = false) {
	
	if ($langId === false) {
		$langId = getContentLang($equivId, $cType);
	}
	
	$sql = "
		SELECT
			m2.equiv_id,
			m2.content_type
		FROM ". DB_NAME_PREFIX. "menu_nodes AS m1
		INNER JOIN ". DB_NAME_PREFIX. "menu_hierarchy AS mh
		   ON mh.child_id = m1.id
		INNER JOIN ". DB_NAME_PREFIX. "menu_nodes AS m2
		   ON m2.id = mh.ancestor_id
		  AND m2.target_loc = 'int'
		WHERE m1.target_loc = 'int'
		  AND m1.equiv_id = ". (int) $equivId. "
		  AND m1.content_type = '". sqlEscape($cType). "'
		ORDER BY m1.redundancy = 'primary' DESC, m2.redundancy = 'primary' DESC
		LIMIT 1";
	
	if ($row = sqlFetchRow($sql)) {
		$cID = $row[0];
		$cType = $row[1];
		return langEquivalentItem($cID, $cType, $langId);
	
	} else {
		return $cID = $cType = false;
	}
}

function menuSectionId($sectionIdOrName, $checkExists = false) {
	if (!is_numeric($sectionIdOrName)) {
		return getRow('menu_sections', 'id', array('section_name' => $sectionIdOrName));
	
	} elseif ($checkExists) {
		return getRow('menu_sections', 'id', array('id' => $sectionIdOrName));
	
	} else {
		return $sectionIdOrName;
	}
}

function menuSectionName($sectionIdOrName) {
	if (is_numeric($sectionIdOrName)) {
		return getRow('menu_sections', 'section_name', array('id' => $sectionIdOrName));
	} else {
		return $sectionIdOrName;
	}
}

function getMenuInLanguage($mID, $langId) {
	$sql = "
		SELECT
			m.id AS mID,
			t.name,
			m.target_loc,
			m.open_in_new_window,
			c.equiv_id,
			c.id AS cID,
			c.type AS cType,
			c.alias,
			m.use_download_page,
			m.hide_private_item,
			t.ext_url,
			c.visitor_version,
			m.invisible,
			m.accesskey,
			m.ordinal,
			m.rel_tag,
			m.css_class
		FROM ". DB_NAME_PREFIX. "menu_text AS t
		INNER JOIN ". DB_NAME_PREFIX. "menu_nodes AS m
		   ON m.id = t.menu_id
		LEFT JOIN ". DB_NAME_PREFIX. "content_items AS c
		   ON m.equiv_id = c.equiv_id
		  AND m.content_type = c.type
		  AND m.target_loc = 'int'
		  AND t.language_id = c.language_id
		WHERE t.language_id = '" . sqlEscape($langId) . "'
		  AND t.menu_id = ". (int) $mID;
	
	$result = sqlQuery($sql);
	return sqlFetchAssoc($result);
}

function getSectionMenuItemFromContent($equivId, $cType, $section, $mustBePrimary = false) {

	$sql = "
		SELECT id
		FROM ". DB_NAME_PREFIX. "menu_nodes
		WHERE equiv_id = ". (int) $equivId. "
		  AND content_type = '". sqlEscape($cType). "'
		  AND section_id = ". (int) menuSectionId($section). "
		  AND target_loc = 'int'";
	
	if ($mustBePrimary) {
		$sql .= "
		  AND redundancy = 'primary'";
	
	} else {
		$sql .= "
		ORDER BY redundancy = 'primary' DESC";
	}
	
	$sql .= "
		LIMIT 1";
	
	$result = sqlQuery($sql);
	if ($row = sqlFetchAssoc($result)) {
		return $row['id'];
	} else {
		return false;
	}
}

function getMenuNodeDetails($mID, $langId = false) {
	$row = getRow('menu_nodes', true, $mID);
	
	if ($row && $langId) {
		$row['mID'] = $row['id'];
		$row['name'] = null;
		$row['descriptive_text'] = null;
		$row['ext_url'] = null;
		
		if ($text = getRow('menu_text', array('name', 'descriptive_text', 'ext_url'), array('menu_id' => $mID, 'language_id' => $langId))) {
			$row['name'] = $text['name'];
			$row['descriptive_text'] = $text['descriptive_text'];
			$row['ext_url'] = $text['ext_url'];
		}
		
		if ($row['equiv_id'] && $row['content_type']) {
			$row['content_id'] = $row['equiv_id'];
			langEquivalentItem($row['content_id'], $row['content_type'], $langId);
			
			if (isMenuNodeUnique($row['redundancy'], $row['equiv_id'], $row['content_type'])) {
				$row['redundancy'] = 'unique';
			}
		}
	}
	
	return $row;
}

function isMenuNodeUnique($redundancy, $equiv_id, $content_type) {
	if ($redundancy == 'primary') {
		$sql = '
			SELECT COUNT(*)
			FROM ' . DB_NAME_PREFIX . 'menu_nodes
			WHERE equiv_id = ' . (int)$equiv_id . '
			AND content_type = "'. sqlEscape($content_type) . '"';
		$result = sqlSelect($sql);
		$row = sqlFetchRow($result);
		if ($row[0] == 1) {
			return true;
		}
	}
	return false;
}

function getMenuName($mID, $langId = false, $missingPhrase = '[[name]] ([[language_id]])') {
	
	$markFrom = false;
	if ($langId === false) {
		$langId = visitorLangId();
	
	} elseif ($langId === true) {
		$langId = cms_core::$defaultLang;
	
	} elseif (checkPriv()) {
		$markFrom = true;
	}
	
	$sql = "
		SELECT name, language_id
		FROM ". DB_NAME_PREFIX. "menu_text AS mt
		WHERE menu_id = ". (int) $mID. "
		ORDER BY
			language_id = '". sqlEscape($langId). "' DESC,
			language_id = '". sqlEscape(cms_core::$defaultLang). "' DESC
		LIMIT 1";
	
	$result = sqlQuery($sql);
	if ($row = sqlFetchAssoc($result)) {
		
		if ($markFrom && $row['language_id'] != $langId) {
			$row['name'] = adminPhrase($missingPhrase, $row);
		}
		
		return $row['name'];
	} else {
		return false;
	}
}

function isMenuItemAncestor($childId, $ancestorId) {
	return checkRowExists('menu_hierarchy', array('child_id' => $childId, 'ancestor_id' => $ancestorId));
}

function getMenuParent($mID) {
	return getRow('menu_nodes', 'parent_id', array('id' => $mID));
}


function shouldShowMenuItem(&$row, &$cachingRestrictions, $language, $getFullMenu, $adminMode) {
	
	// Hide menu node if static method is set
	if (!empty($row['module_class_name'])) {
		$cachingRestrictions = 'staticFunctionCalled';
		if (!(inc($row['module_class_name']))
		 || !(method_exists($row['module_class_name'], $row['method_name']))
		 || !($overrides = call_user_func(
				array($row['module_class_name'], $row['method_name']),
					$row['param_1'], $row['param_2'])
		)) {
			//A little hack - return null so we can tell the difference
			return null;
		
		} else {
			//If an array is returned, show the menu node but override any
			//of the options it had
			if (is_array($overrides)) {
				foreach ($overrides as $key => &$override) {
					$row[$key] = $override;
				}
			
			//If a string is returned, set the text of the menu node
			//This is an un-documented feature for backwards compatibility
			} elseif (is_string($overrides)) {
				$row['name'] = $overrides;
			}
		}
	}
	
	//Logic for menu nodes that point to content items 
	if ($row['target_loc'] == 'int') {
		
		
		
		//Check to see if the content item attached to the menu node was visible
		if (!$row['cID']) {
			
			//Try to show a placeholder page from the default language if the content item in this language
			//was missing or not published, and that option is enabled
			if ($getFullMenu
			 && $row['cType']
			 && $row['equiv_id']
			 && $language !== cms_core::$defaultLang) {
				
				$sql = '
					SELECT alias
					FROM '. DB_NAME_PREFIX. 'content_items
					WHERE id = '. (int) $row['equiv_id']. '
					  AND `type` = \''. sqlEscape($row['cType']). '\'';
					
				if ($adminMode) {
					$sql .= "
						AND status != 'deleted'";
				} else {
					$sql .= "
						AND status IN ('published', 'published_with_draft')";
				}
				
				if ($content = sqlFetchAssoc($sql)) {
					$row['cID'] = $row['equiv_id'];
					$row['alias'] = $content['alias'];
					$row['placeholder'] = true;
					
					return shouldShowMenuItem($row, $cachingRestrictions, $language, $getFullMenu, $adminMode);
				}
			}
			
			//Otherwise the menu node should be hidden
			return false;
		
		//Check for Menu Nodes that are only shown if logged in/out as an Extranet User
		//But note that this logic is not used in Admin Mode; Admins always see every Menu Node, and don't see any special markings for these Menu Nodes
		} elseif ($row['hide_private_item'] == 3) {
			$cachingRestrictions = 'privateItemsExist';
			return !empty($_SESSION['extranetUserID']) || $adminMode;
		
		} elseif ($row['hide_private_item'] == 2) {
			$cachingRestrictions = 'privateItemsExist';
			return empty($_SESSION['extranetUserID']) || $adminMode;
		
		} elseif ($row['hide_private_item']) {
			$cachingRestrictions = 'privateItemsExist';
			return checkPerm($row['cID'], $row['cType']);
		}
	}
	return $row['target_loc'] != 'none';
}


function lookForMenuItems($parentMenuId, $language, $sectionId, $currentMenuId, $recurseCount, $showInvisibleMenuItems, $getFullMenu, $adminMode) {
	
	$sql = "
		SELECT
			m.id AS mID,
			m.target_loc,
			m.open_in_new_window,
			m.anchor,
			m.module_class_name,
			m.method_name,
			m.param_1,
			m.param_2,
			m.equiv_id,
			c.id AS cID,
			m.content_type AS cType,
			c.alias,
			m.use_download_page,
			m.hide_private_item,
			m.add_registered_get_requests,
			m.invisible,
			m.accesskey,
			m.ordinal,
			m.rel_tag,
			m.image_id,
			m.rollover_image_id,
			m.css_class";
	
	if ($getFullMenu
	 && $language != cms_core::$defaultLang) {
		$sql .= ",
			IFNULL(t.name, d.name) AS name,
			IFNULL(t.ext_url, d.ext_url) AS ext_url,
			IFNULL(t.descriptive_text, d.descriptive_text) AS descriptive_text
		FROM ". DB_NAME_PREFIX. "menu_nodes AS m
		LEFT JOIN ". DB_NAME_PREFIX. "menu_text AS t
		   ON t.menu_id = m.id
		  AND t.language_id = '". sqlEscape($language). "'
		LEFT JOIN ". DB_NAME_PREFIX. "menu_text AS d
		   ON t.menu_id IS NULL
		  AND d.menu_id = m.id
		  AND d.language_id = '". sqlEscape(cms_core::$defaultLang). "'";
	
	} else {
		$sql .= ",
			t.name,
			t.ext_url,
			t.descriptive_text
		FROM ". DB_NAME_PREFIX. "menu_nodes AS m
		INNER JOIN ". DB_NAME_PREFIX. "menu_text AS t
		   ON t.menu_id = m.id
		  AND t.language_id = '". sqlEscape($language). "'";
	}
	
	$sql .= "
		LEFT JOIN ".DB_NAME_PREFIX."content_items AS c
		   ON m.target_loc = 'int'
		  AND m.equiv_id = c.equiv_id
		  AND m.content_type = c.type
		  AND c.language_id = '". sqlEscape($language). "'";
	
	if ($adminMode) {
		$sql .= "
			AND c.status != 'deleted'";
	} else {
		$sql .= "
			AND c.status IN ('published', 'published_with_draft')";
	}
	
	$sql .= "
		WHERE m.parent_id = ". (int) $parentMenuId. "
		  AND m.section_id = ". (int) $sectionId;
	
	if (!$showInvisibleMenuItems) {
		$sql .= "
		  AND m.invisible != 1";
	}
	
	$sql .= "
		ORDER BY m.ordinal";
	
	return sqlQuery($sql);
}


function getMenuStructure(
	&$cachingRestrictions,
	$sectionId,
	$currentMenuId = false,
	$parentMenuId = 0,
	$numLevels = 0,
	$maxLevel1MenuItems = 100,
	$language = false,
	$onlyFollowOnLinks = false,
	$onlyIncludeOnLinks = false,
	$showInvisibleMenuItems = false,
	$showMissingMenuNodes = false,
	$requests = false,
	$getFullMenu = false,
	$recurseCount = 0
) {
	if ($language === false) {
		$language = cms_core::$visLang ?? cms_core::$defaultLang;
	}
	
	if (++$recurseCount == 1) {
		$level1counter = 0;
	}
	
	$adminMode = !empty($_SESSION['admin_logged_into_site']) && checkPriv();
	
	//Look up all of the Menu Items on this level
	$edition = cms_core::$edition;
	$rows = array();
	if ($showMissingMenuNodes && $language != cms_core::$defaultLang) {
		$result = lookForMenuItems($parentMenuId, cms_core::$defaultLang, $sectionId, $currentMenuId, $recurseCount, $showInvisibleMenuItems, $getFullMenu, $adminMode);
		while ($row = sqlFetchAssoc($result)) {
			if (empty($row['css_class'])) {
				$row['css_class'] = 'missing';
			} else {
				$row['css_class'] .= ' missing';
			}
			
			$rows[$row['mID']] = $row;
		}
	}
	
	$result = lookForMenuItems($parentMenuId, $language, $sectionId, $currentMenuId, $recurseCount, $showInvisibleMenuItems, $getFullMenu, $adminMode);
	while ($row = sqlFetchAssoc($result)) {
		$rows[$row['mID']] = $row;
	}
	
	if (!empty($rows)) {
		$menuIds = '';
		$unsets = array();
		foreach ($rows as &$row) {
			$row['on'] = false;
			$row['children'] = false;
			$unsets[$row['mID']] = true;
			$menuIds .= ($menuIds? ',' : ''). $row['mID'];
		}
		unset($row);
		
		//Look for children of the Menu Nodes we will be displaying, so we know which Menu Nodes have no children
		$sql = "
			SELECT DISTINCT ancestor_id
			FROM ". DB_NAME_PREFIX. "menu_hierarchy
			WHERE ancestor_id IN (". $menuIds. ")
			  AND separation = 1";
		
		$result = sqlQuery($sql);
		while ($row = sqlFetchRow($result)) {
			$rows[$row[0]]['children'] = true;
		}
		
		//Look for Menu Nodes that are ancestors of the current Menu Node
		if ($currentMenuId) {
			$sql = "
				SELECT ancestor_id
				FROM ". DB_NAME_PREFIX. "menu_hierarchy
				WHERE ancestor_id IN (". $menuIds. ")
				  AND child_id = ". (int) $currentMenuId;
			
			$result = sqlQuery($sql);
			while ($row = sqlFetchRow($result)) {
				$rows[$row[0]]['on'] = true;
			}
		}
		
		//Loop through each found Menu Item
		foreach ($rows as $menuId => &$row) {
			
			if ($recurseCount == 1) {
				$level1counter++;
			}
			
			if ($onlyIncludeOnLinks && !$row['on']) {
				//Have a "breadcrumbs" option to only show the chain to the current content item
				continue;
			
			} else {
				$row['active'] = $showMenuItem = shouldShowMenuItem($row, $cachingRestrictions, $language, $getFullMenu, $adminMode);
				$row['conditionally_hidden'] = $showMenuItem === null;
				
				if ($adminMode) {
					//Always show an Admin a Menu Node
					$showMenuItem = true;
					$row['onclick'] = "if (!window.zenarioA) return true; return zenarioA.openMenuAdminBox({id: ". (int)  $row['mID']. "});";
					if (empty($row['css_class'])) {
						$row['css_class'] = 'zenario_menu_node';
					} else {
						$row['css_class'] .= ' zenario_menu_node';
					}
				}
			}
			
			if ($showMenuItem) {
				if ($row['target_loc'] == 'ext' && $row['ext_url']) {
					$row['url'] = $row['ext_url'];
				
					//Allow anyone writing a static method to easily add extra requests to the URL
					//by using the extra_requests property
					if (!empty($row['extra_requests'])) {
						if (is_array($row['extra_requests'])) {
							$row['url'] .= '&'. http_build_query($row['extra_requests']);
						} else {
							$row['url'] .= '&'. $row['extra_requests'];
						}
					}



						
				} else if ($row['target_loc'] == 'int' && $row['cID']) {
					$request = '';
					$downloadDocument = ($row['cType'] == 'document' && !$row['use_download_page']);
					if ($downloadDocument) {
						$request = '&download=1';
					}
					if (isset($row['placeholder'])) {
						$request .= '&visLang='. rawurlencode($language);
					}
					if ($requests) {
						$request .= addAmp($requests);
					}
					//Allow anyone writing a static method to easily add extra requests to the URL
					//by using the extra_requests property
					if (!empty($row['extra_requests'])) {
						if (is_array($row['extra_requests'])) {
							$request .= '&'. http_build_query($row['extra_requests']);
						} else {
							$request .= '&'. $row['extra_requests'];
						}
					}
					
					if (cms_core::$cID == $row['cID'] && cms_core::$cType == $row['cType'] && cms_core::$menuTitle !== false) {
						$row['name'] = cms_core::$menuTitle;
					}
					
					$link = linkToItem($row['cID'], $row['cType'], false, $request, $row['alias'], $row['add_registered_get_requests']);
					
					if ($downloadDocument) {
						$row['onclick'] = Ze\File::trackDownload($link);
					}
					
					$row['url'] = $link;
					if (!empty($row['anchor'])) {
						$row['url'] .= '#'.$row['anchor'];
					}
			
				} else {
					$row['url'] = '';
				}
				
				if ($row['accesskey']) {
					$row['title'] = adminPhrase('_ACCESS_KEY_EQUALS', array('key' => $row['accesskey']));
				}
		
				if ($row['open_in_new_window']) {
					$row['target'] = '_blank';
				}
			
			} else {
				$row['url'] = '';
				unset($row['onclick']);
			}
			
			if ($showMenuItem || $row['name']) {
				$goFurther = !$numLevels || $recurseCount < $numLevels;
				$followLink = !$onlyFollowOnLinks || $row['on'];
				
				//If this row has children...
				if ($row['children']) {
					//Recurse down into the child levels and display them, if needed
					if ($goFurther && $followLink) {
						$row['children'] = getMenuStructure(
												$cachingRestrictions,
												$sectionId, $currentMenuId, $row['mID'],
												$numLevels, $maxLevel1MenuItems, $language,
												$onlyFollowOnLinks, $onlyIncludeOnLinks,
												$showInvisibleMenuItems, $showMissingMenuNodes,
												$requests, $getFullMenu, $recurseCount);
						
						if ($row['target_loc'] == 'none' && $adminMode) {
							//Publishing a Content Item under an unlinked Menu Node will cause that to appear - mark this as so in Admin Mode
							foreach ($row['children'] as &$child) {
								if (!empty($child['active'])) {
									$row['active'] = true;
									break;
								}
							}
						}
					
					//Otherwise if we're not recursing, check that at least one of the children are in fact visible to the current Visitor
					} else {
						$row['children'] = false;
						$result2 = lookForMenuItems($row['mID'], $language, $sectionId, $currentMenuId, $recurseCount, $showInvisibleMenuItems, $getFullMenu, $adminMode);
						while ($row2 = sqlFetchAssoc($result2)) {
							if ($row2['target_loc'] != 'none' && (empty($row2['invisible']) || $showInvisibleMenuItems) && shouldShowMenuItem($row2, $cachingRestrictions, $language, $getFullMenu, $adminMode)) {
								$row['children'] = true;
								break;
							}
						}
					}
					
					//Unlinked Menu Items with visible children should still be shown to Visitors.
					//Unlinked Menu Items with no visible children should be shown but marked as inactive to Admins.
					if ($adminMode) {
						$showMenuItem = true;
					
					} elseif (!empty($row['children']) && $followLink && $goFurther) {
						$showMenuItem = true;
						$row['active'] = true;
					}
				}
				
			}
			
			
			
			if ($showMenuItem) {
				//Don't show unlinked Menu Nodes that have no immediate children to Visitors
				if ($row['target_loc'] != 'none' || $row['children'] || $adminMode || $getFullMenu) {
					//Ensure that we show this Menu Node!
					unset($unsets[$menuId]);
				}
				
				if ($recurseCount == 1) {
					if ($level1counter >= $maxLevel1MenuItems) {
						break;
					}
				}
			}
		}
		
		//Remove any Menu Items that we should not display
		foreach ($unsets as $menuId => $dummy) {
			unset($rows[$menuId]);
		}
	}
	
	if ($recurseCount>1000) {
		echo "Aborting; Menu Generation seems to be in an infinite recursion loop!";
		exit;
	}
	
	return $rows;
}


function linkToEquivalentItem(
	$cID, $cType = 'html', $languageId = false, $fullPath = false, $request = '', $useAliasInAdminMode = false
) {

	if (langEquivalentItem($cID, $cType, $languageId)) {
		return linkToItem(
			$cID, $cType, $fullPath, $request, false,
			false, $useAliasInAdminMode,
			false, $languageId
		);
	} else {
		return false;
	}
}


function linkToItemStayInCurrentLanguage(
	$cID, $cType = 'html', $fullPath = false, $request = '', $alias = false,
	$autoAddImportantRequests = false, $useAliasInAdminMode = false,
	$equivId = false, $languageId = false
) {
	
	$stayInCurrentLanguage =
		cms_core::$visLang != cms_core::$defaultLang
	 && (cms_core::$langs[cms_core::$visLang]['show_untranslated_content_items'] ?? false);

	return linkToItem(
		$cID, $cType, $fullPath, $request, $alias,
		$autoAddImportantRequests, $useAliasInAdminMode,
		$equivId, $languageId, $stayInCurrentLanguage
	);
}


//Build a link to a content item
//n.b. linkToItem() and resolveContentItemFromRequest() are essentially opposites of each other...
function linkToItem(
	$cID, $cType = 'html', $fullPath = false, $request = '', $alias = false,
	$autoAddImportantRequests = false, $useAliasInAdminMode = false,
	$equivId = false, $languageId = false, $stayInCurrentLanguage = false
) {
	
	//Catch the case where a tag id is entered, not a cID and cType
	if (!is_numeric($cID)) {
		$tagId = $cID;
		getCIDAndCTypeFromTagId($cID, $cType, $tagId);
	}
	
	//From version 6.1 onwards, we're no longer allowing this function to be called
	//by placing the alias in the $cID variable
	if (!$cID || !is_numeric($cID) || !$cType) {
		return false;
	}
	
	//If there are slashes in the alias, we need to make sure to return a full URL, not a relative one.
	//But let the caller specifically override this by passing NEVER in.
	if ($fullPath === 'never') {
		$fullPath = false;
	} elseif (cms_core::$mustUseFullPath) {
		$fullPath = true;
	}
	
	if (is_array($request)) {
		$request = http_build_query($request);
	}
	
	
	$adminMode = !empty($_SESSION['admin_logged_into_site']) && checkPriv();
	$mod_rewrite_enabled = setting('mod_rewrite_enabled');
	$mod_rewrite_slashes = setting('mod_rewrite_slashes');
	$mod_rewrite_suffix = setting('mod_rewrite_suffix');
	$useAlias = $useAliasInAdminMode || !$adminMode;
	$multilingual = getNumLanguages() > 1;
	$returnSlashForHomepage = false;
	$langSpecificDomain = false;
	$needToUseLangCode = false;
	$usingAlias = false;
	$content = false;
	$domain = false;
	
	
	//If this is a link to the current page, we can get some of the metadata from the cms_core variables
	//without needing to use the database to look it up
	if ($cID == cms_core::$cID
	 && $cType == cms_core::$cType) {
		$alias = cms_core::$alias;
		$equivId = cms_core::$equivId;
		$languageId = cms_core::$langId;
	
	} elseif (!$multilingual) {
		//For single-lingual sites, the cID is always equal to the alias
		$equivId = $cID;
	}
	
	$autoAddImportantRequests =
		$autoAddImportantRequests
	 && $cType == cms_core::$cType
	 && !empty(cms_core::$importantGetRequests)
	 && is_array(cms_core::$importantGetRequests);
	
	//Attempt to look up the alias/language if they weren't provided, and we might need them.
	if (($stayInCurrentLanguage && $multilingual && $languageId === false)
	 || (($useAlias || ($autoAddImportantRequests && !$equivId))
	  && ($multilingual
	   || $alias === false
	   || ($mod_rewrite_slashes && ($equivId === false || $languageId === false))
	))) {
		$result = sqlSelect("
			SELECT alias, equiv_id, language_id, lang_code_in_url
			FROM ". DB_NAME_PREFIX. "content_items
			WHERE id = ". (int) $cID. "
			  AND type = '". sqlEscape($cType). "'"
		);
		if ($content = sqlFetchRow($result)) {
			$alias = $content[0];
			$equivId = $content[1];
			$languageId = $content[2];
			$lang_code_in_url = $content[3];
		} else {
			return false;
		}
	}

	//Add important requests to the URL, if the content item being linked to is the current content item,
	//or a translation
	if ($autoAddImportantRequests
	 && $equivId == cms_core::$equivId) {
		foreach(cms_core::$importantGetRequests as $getRequest => $defaultValue) {
			if (isset($_GET[$getRequest]) && $_GET[$getRequest] != $defaultValue) {
				$request .= '&'. urlencode($getRequest). '='. urlencode($_GET[$getRequest]);
			}
		}
	}
	
	//On multi-lingual sites, use a language-specific domain if one is set up
	if ($useAlias && $multilingual && !empty(cms_core::$langs[$languageId]['domain'])) {
		$domain = cms_core::$langs[$languageId]['domain'];
	
		//If we're using a language specific domain, we don't need to add the language code into the URL later
		$langSpecificDomain = true;
	
	//Always try to use the admin domain in admin mode
	} elseif ($adminMode && !$useAlias && ($adminDomain = setting('admin_domain'))) {
		$domain = $adminDomain;

	} else {
		$domain = primaryDomain();
	}
	
	//If there is nothing in the request then links to the homepage
	//should always use just the domain name (with maybe the language code for multilingual sites)
	if ($useAlias
	 && $equivId == cms_core::$homeEquivId
	 && $cType == cms_core::$homeCType) {
		$fullPath = true;
		$returnSlashForHomepage = true;
	}
	
	//If this isn't the correct domain, use the full path to switch to it
	if ($fullPath
	 || empty($_SERVER['HTTP_HOST'])
	 || ($domain && $domain != $_SERVER['HTTP_HOST'])) {
		
		$fullPath = httpOrHttps(). $domain. SUBDIRECTORY;
	} else {
		$fullPath = '';
	}
	
	//If we're linking to a homepage, if possible, just use a slash and never show the alias
	if ($returnSlashForHomepage) {
		
		//If the site isn't multilingual, or if there is one domain per language, we can just use the domain and subdirectory
		if (!$multilingual || $langSpecificDomain) {
			return $fullPath. ($request? addQu($request) : '');
		
		//If slashes are enabled in the URL, we'll make a sub-directory on a per-language basis
		} elseif ($mod_rewrite_slashes) {
			return $fullPath. $languageId. '/'. ($request? addQu($request) : '');
		}
		
		//For any other cases we'll need to show the alias
	}

	
	//Link to the item using either the cID or the alias.
	if ($useAlias && $alias) {
		$aliasOrCID = $alias;
		$usingAlias = true;
		
		//If multiple languages are enabled on this site, check to see if we need to add the language code to the alias.
		if ($multilingual) {
			//We don't need to add the language code again if we've already used a language-specific domain
			if ($langSpecificDomain) {
				$needToUseLangCode = false;
				
			//Otherwise we will need to add the language code if the alias is used more than once,
			//the settings for that Content Item say so, or if the settings for the Content Item are left on
			//default and the Site Settings say so.
			} elseif ($lang_code_in_url == 'show' || ($lang_code_in_url == 'default' && !setting('translations_hide_language_code'))) {
				$needToUseLangCode = true;
			
			} else {
				$sql = "
					SELECT 1
					FROM ". DB_NAME_PREFIX. "content_items
					WHERE alias = '". sqlEscape($alias). "'
					LIMIT 2";
				$result = sqlQuery($sql);
				$needToUseLangCode = sqlFetchRow($result) && sqlFetchRow($result);
			}
			
			//If we're not allowed slashes in the URL, and we need to add the language code,
			//add it to the end after a comma.
			if ($needToUseLangCode && !$mod_rewrite_slashes) {
				$aliasOrCID .= ','. $languageId;
			}
		}
		
	} else {
		$aliasOrCID = $cType. '_'. $cID;
	}
	
	//If enabled in the site settings, attempt to add the full menu tree into the friendly URL
	if ($useAlias && $mod_rewrite_slashes) {
		$aliasOrCID = addHierarchicalAlias($equivId, $cType, $languageId, $aliasOrCID);
	}
	
	//If we're allowed slashes in the URL, and we need to add the language code,
	//then add it as a slash at the start of the URL
	if ($needToUseLangCode && $mod_rewrite_slashes) {
		$aliasOrCID = $languageId. '/'. $aliasOrCID;
	}
	
	//If a translation isn't available, have an option to show the page in the default
	//language
	if ($stayInCurrentLanguage
	 && cms_core::$visLang != $languageId
	 && cms_core::$visLang != cms_core::$defaultLang) {
		$request .= '&visLang='. rawurlencode(cms_core::$visLang);
	}
	
	//"Download now" format for old documents
	if ($useAlias
	 && $cType == 'document'
	 && $mod_rewrite_enabled) {
		
		switch (addAmp($request)) {
			case '&download=1':
			case '&download=true':
			case '&download=1&cType=document':
			case '&download=true&cType=document':
			case '&cType=document&download=1':
			case '&cType=document&download=true':
				return $fullPath. $aliasOrCID. '.download';
		}
	}
	
	//"RSS link" shortcut. Note that this only works if there is only one plugin on a page with an RSS feed.
	//If there are two, this link will link to the first one on the page that we found.
	if ($useAlias && $request === '&method_call=showRSS' && $mod_rewrite_enabled) {
		return $fullPath. $aliasOrCID. '.rss';
	
	} elseif ($useAlias && $mod_rewrite_enabled) {
		return $fullPath. $aliasOrCID. $mod_rewrite_suffix. ($request? addQu($request) : '');
	
	} else {
		$basePath = $fullPath. DIRECTORY_INDEX_FILENAME;
		if ($basePath === '') {
			$basePath = SUBDIRECTORY;
		}
		return $basePath. '?cID='. $aliasOrCID. ($request? addAmp($request) : '');
	}
}

function addHierarchicalAlias($equivId, $cType, $languageId, $alias) {
	
	//Try to get the menu node that this content item is for, and check if it has a parent to follow
	$sql = "
		SELECT id, parent_id, section_id
		FROM ". DB_NAME_PREFIX. "menu_nodes AS m
		WHERE m.equiv_id = ". (int) $equivId. "
		  AND m.content_type = '" . sqlEscape($cType) . "'
		  AND m.target_loc = 'int'
		ORDER BY m.redundancy = 'primary' DESC
		LIMIT 1";
	$result = sqlQuery($sql);
	
	if (($menu = sqlFetchAssoc($result))
	 && ($menu['parent_id'])) {
		
		//Loop through the menu structure above. Where a content item has an alias,
		//add it into the URL.
		//Note that we should not add the same alias twice in a row - this may happen
		//if a content item has a secondary menu node
		$sql = "
			SELECT c.alias
			FROM ". DB_NAME_PREFIX. "menu_hierarchy AS mh
			INNER JOIN ". DB_NAME_PREFIX. "menu_nodes AS m
			   ON m.id = mh.ancestor_id
			  AND m.target_loc = 'int'
			INNER JOIN ". DB_NAME_PREFIX. "content_items AS c
			   ON c.equiv_id = m.equiv_id
			  AND c.type = m.content_type
			  AND c.language_id = '" . sqlEscape($languageId) . "'
			WHERE mh.section_id = ". (int) $menu['section_id']. "
			  AND mh.child_id = ". (int) $menu['parent_id']. "
			ORDER BY mh.separation ASC";
		$result = sqlQuery($sql);
		
		$lastAlias = $alias;
		while ($menu = sqlFetchAssoc($result)) {
			if ($menu['alias'] != '') {
				if ($menu['alias'] != $lastAlias) {
					$alias = $menu['alias']. '/'. $alias;
					$lastAlias = $menu['alias'];
				}
			}
		}
	}
	
	return $alias;
}

function showAdminTitle($titleAdmin, $info="") {
	$text = "<tr style=\"background-color:#CCCCCC;\"><td class=\"panel_title\" style=\"text-align: left;\"><h5 style=\"text-align:left;\">";
	$text .= $titleAdmin;
	$text .="</h5>" . $info . "</td></tr>";
	return $text;
}

function getLanguages($includeAllLanguages = false, $orderByEnglishName = false, $defaultLangFirst = false) {
		
	$sql = "
		SELECT
			l.id,
			IFNULL(en.local_text, lo.local_text) AS english_name,
			IFNULL(lo.local_text, en.local_text) AS language_local_name,
			IFNULL(f.local_text, 'white') as flag,
			detect,
			translate_phrases,
			sync_assist,
			search_type";
	
	if ($includeAllLanguages) {
		$sql .= "
			FROM (
				SELECT DISTINCT language_id AS id
				FROM ". DB_NAME_PREFIX. "visitor_phrases
			) AS l
			LEFT JOIN ". DB_NAME_PREFIX. "languages el
			   ON l.id = el.id";
	
	} else {
		$sql .= "
			FROM ". DB_NAME_PREFIX. "languages AS l";
	}
	
	$sql .= "
		LEFT JOIN ". DB_NAME_PREFIX. "visitor_phrases AS en
		   ON en.module_class_name = 'zenario_common_features'
		  AND en.language_id = l.id
		  AND en.code = '__LANGUAGE_ENGLISH_NAME__'
		LEFT JOIN ". DB_NAME_PREFIX. "visitor_phrases AS lo
		   ON lo.module_class_name = 'zenario_common_features'
		  AND lo.language_id = l.id
		  AND lo.code = '__LANGUAGE_LOCAL_NAME__'
		LEFT JOIN ". DB_NAME_PREFIX. "visitor_phrases AS f
		   ON f.module_class_name = 'zenario_common_features'
		  AND f.language_id = l.id
		  AND f.code = '__LANGUAGE_FLAG_FILENAME__'
		ORDER BY ";
	
	if ($defaultLangFirst) {
		$sql .= "l.id = '". sqlEscape(cms_core::$defaultLang). "' DESC, ";
	}
	
	if ($orderByEnglishName) {
		$sql .= "IFNULL(en.local_text, lo.local_text)";
	} else {
		$sql .= "l.id";
	}
	
	$result = sqlQuery($sql);
	$langs = array();
	while ($row = sqlFetchAssoc($result)) {
		$langs[$row['id']] = $row;
	}
	
	return $langs;
}

function getLanguageName($languageId = false, $addIdInBracketsToEnd = true, $returnIdOnFailure = true, $localName = false) {
	
	if ($languageId === false) {
		$languageId = ifNull(cms_core::$visLang, cms_core::$defaultLang);
	}
	
	$name = getRow('visitor_phrases', 'local_text', array('code' => '__LANGUAGE_ENGLISH_NAME__', 'language_id' => $languageId, 'module_class_name' => 'zenario_common_features'));
	
	if ($name !== false) {
		if ($addIdInBracketsToEnd) {
			return $name. ' ('. $languageId. ')';
		} else {
			return $name;
		}
	} elseif ($returnIdOnFailure) {
		return $languageId;
	} else {
		return false;
	}

}

function getLanguageLocalName($languageId = false) {
	return getLanguageName($languageId, false, false, true);
}



//Signal that you need to log in as an extranet user to see this page
//(N.b. we use an empty string for this, because it evaluates to false if someone isn't using a === check.)
define('ZENARIO_401_NOT_LOGGED_IN', '');

//Signal that the current extranet user does not have access to view the page.
//(N.b. we use a zero for this, because it evaluates to false if someone isn't using a === check.)
define('ZENARIO_403_NO_PERMISSION', 0);

//Signal that a page was not found.
define('ZENARIO_404_NOT_FOUND', false);


define('ZENARIO_CENTRALISED_LIST_MODE_INFO', 1);
define('ZENARIO_CENTRALISED_LIST_MODE_LIST', 2);
define('ZENARIO_CENTRALISED_LIST_MODE_FILTERED_LIST', 3);
define('ZENARIO_CENTRALISED_LIST_MODE_VALUE', 4);

function getDatasetDetails($dataset, $cols = true) {
	if (is_array($dataset)) {
		return $dataset;
	
	} elseif (is_numeric($dataset)) {
		return getRow('custom_datasets', $cols, $dataset);
	
	} elseif ($out = getRow('custom_datasets', $cols, array('system_table' => array($dataset, DB_NAME_PREFIX. $dataset)))) {
		return $out;
	}
	return getRow('custom_datasets', $cols, array('label' => $dataset));
}

function getDatasetTabDetails($datasetId, $tabName) {
	return getRow('custom_dataset_tabs', true, array('dataset_id' => $datasetId, 'name' => $tabName));
}

function getDatasetFieldBasicDetails($fieldId) {
	$sql = "
		SELECT type, is_system_field, db_column, label, default_label
		FROM ". DB_NAME_PREFIX. "custom_dataset_fields
		WHERE id = ". (int) $fieldId;
	return sqlFetchAssoc($sql);
}

function getDatasetFieldDetails($field, $dataset = false, $cols = true) {
	if (is_numeric($field)) {
		return getRow('custom_dataset_fields', $cols, $field);
	} else {
		if (!is_numeric($dataset)) {
			$dataset = getDatasetDetails($dataset, array('id'));
			$dataset = $dataset['id'];
		}
		return getRow('custom_dataset_fields', $cols, array('dataset_id' => $dataset, 'db_column' => $field));
	}
}

function getDatasetFieldsDetails($dataset) {
	if (!is_numeric($dataset)) {
		$dataset = getDatasetDetails($dataset, array('id'));
		$dataset = $dataset['id'];
	}
	
	$out = array();
	if ($fields = getRowsArray('custom_dataset_fields', true, array('dataset_id' => $dataset, 'type' => array('!' => 'other_system_field')))) {
		foreach ($fields as $field) {
			$out[$field['db_column']] = $field;
		}
	}
	
	return $out;
}

function getDatasetFieldRepeatRowColumnName($dbColumn, $row) {
	return ($row > 1) ? $dbColumn . '___' . $row : $dbColumn;
}

function getDatasetRepeatStartRowColumnName($fieldId) {
	return $fieldId . '___rows';
}

function datasetFieldValue($dataset, $cfield, $recordId, $returnCSV = true, $forDisplay = false, $row = false) {
	if ($dataset && !is_array($dataset)) {
		$dataset = getDatasetDetails($dataset, array('id', 'system_table', 'table'));
	}
	if (!is_array($cfield)) {
		$cfield = getDatasetFieldDetails($cfield, $dataset, array('id', 'dataset_id', 'is_system_field', 'type', 'values_source', 'dataset_foreign_key_id', 'db_column'));
	}
	if (!$cfield) {
		return false;
	}
	if (!is_array($dataset)) {
		$dataset = getDatasetDetails($cfield['dataset_id'], array('id', 'system_table', 'table'));
	}
	if (!$dataset) {
		return false;
	}
	
	if ($cfield['is_system_field']) {
		if ($cfield['db_column']) {
			return getRow($dataset['system_table'], $cfield['db_column'], $recordId);
		}
	
	} else {
		//Checkbox values are stored in the custom_dataset_values_link table
		
		switch ($cfield['type']) {
			case 'checkboxes':
				
				$sql = "
					SELECT cdvl.value_id
					FROM ". cms_core::$lastDBPrefix. "custom_dataset_values_link AS cdvl
					INNER JOIN ". cms_core::$lastDBPrefix. "custom_dataset_field_values AS cdfv
					   ON cdfv.id = cdvl.value_id
					  AND cdfv.field_id = ". (int) $cfield['id']. "
					WHERE cdvl.linking_id = ". (int) $recordId;
				
				$values = sqlFetchValues($sql);
			
				if ($forDisplay) {
					$values = getRowsArray('custom_dataset_field_values', 'label', array('field_id' => $cfield['id'], 'id' => $values), 'label');
				
					if ($returnCSV) {
						return implode(', ', $values);
					} else {
						return $values;
					}
				} else {
					if ($returnCSV) {
						return inEscape($values, 'numeric');
					} else {
						return $values;
					}
				}
				
				break;
			
			case 'file_picker':
				$values = getRowsArray(
					'custom_dataset_files_link',
					'file_id',
					array(
						'dataset_id' => $dataset['id'],
						'field_id' => $cfield['id'],
						'linking_id' => $recordId));
			
				if ($forDisplay) {
					$values = getRowsArray('files', 'filename', array('id' => $values), 'filename');
				
					if ($returnCSV) {
						return implode(', ', $values);
					} else {
						return $values;
					}
				} else {
					if ($returnCSV) {
						return inEscape($values, 'numeric');
					} else {
						return $values;
					}
				}
				
				break;
			
			default:
				$dbColumn = $cfield['db_column'];
				if ($row) {
					$dbColumn = getDatasetFieldRepeatRowColumnName($dbColumn, $row);
				}
				$value = getRow($dataset['table'], $dbColumn, $recordId);
				
				if ($forDisplay) {
					switch ($cfield['type']) {
						case 'radios':
						case 'select':
							return getRow('custom_dataset_field_values', 'label', array('field_id' => $cfield['id'], 'id' => $value));

						case 'centralised_radios':
						case 'centralised_select':
							return getCentralisedListValue($cfield['values_source'], $value);
						
						case 'dataset_select':
						case 'dataset_picker':
							if ($labelDetails = getDatasetLabelFieldDetails($cfield['dataset_foreign_key_id'])) {
								return getRow($labelDetails['table'], $labelDetails['db_column'], $value);
							}
					}
				}
				
				return $value;
		}
	}
	
}

//Checkboxes are stored in the custom_dataset_values_link table as there could be more than one of them.
//Given an array or comma-seperated list of the checked values, this function will set the value in the
//database.
function updateDatasetCheckboxField($datasetId, $fieldId, $linkingId, $values) {
	if (!is_array($values)) {
		$values = explodeAndTrim($values);
	}
	
	//Loop through making sure that the selected values are in the database.
	$selectedIds = array();
	foreach ($values as $id) {
		if ($id) {
			$selectedIds[$id] = $id;
			setRow(
				'custom_dataset_values_link',
				array(),
				array('dataset_id' => $datasetId, 'value_id' => $id, 'linking_id' => $linkingId));
		}
	}
	
	//Remove any values from the database that *weren't* selected
	$sql = "
		DELETE cdvl.*
		FROM ". DB_NAME_PREFIX. "custom_dataset_field_values AS cdfv
		INNER JOIN ". DB_NAME_PREFIX. "custom_dataset_values_link AS cdvl
		   ON cdvl.value_id = cdfv.id
		  AND cdvl.linking_id = ". (int) $linkingId. "
		WHERE cdfv.field_id = ". (int) $fieldId;
	
	if (!empty($selectedIds)) {
		$sql .= "
		  AND cdfv.id NOT IN (". inEscape($selectedIds, 'numeric'). ")";
	}
	
	sqlQuery($sql);
}

//As above, but for picked files
function updateDatasetFilePickerField($datasetId, $cField, $linkingId, $values) {
	if (!is_array($values)) {
		$values = explodeAndTrim($values);
	}
	if (!is_array($cField)) {
		$cField = getRow('custom_dataset_fields', array('id', 'store_file', 'multiple_select'), $cField);
	}
	
	//Loop through making sure that the selected values are in the database.
	$selectedIds = array();
	foreach ($values as $id) {
		if ($id) {
			
			if ($location = Ze\File::getPathOfUploadedInCacheDir($id)) {
				$id = Ze\File::addToDatabase(
					'dataset_file', $location,
					$filename = false, $mustBeAnImage = false, $deleteWhenDone = false,
					$addToDocstoreDirIfPossible = $cField['store_file'] == 'in_docstore'
				);
			}
			
			$selectedIds[$id] = $id;
			setRow(
				'custom_dataset_files_link',
				array(),
				array(
					'dataset_id' => $datasetId,
					'field_id' => $cField['id'],
					'linking_id' => $linkingId,
					'file_id' => $id
			));
			
			if (!$cField['multiple_select']) {
				break;
			}
		}
	}
	
	//Remove any values from the database that *weren't* selected
	$sql = "
		DELETE
		FROM ". DB_NAME_PREFIX. "custom_dataset_files_link
		WHERE dataset_id = ". (int) $datasetId. "
		  AND field_id = ". (int) $cField['id']. "
		  AND linking_id = ". (int) $linkingId;
	
	if (!empty($selectedIds)) {
		$sql .= "
		  AND file_id NOT IN (". inEscape($selectedIds, 'numeric'). ")";
	}
	
	if (sqlQuery($sql)) {
		removeUnusedDatasetFiles();
	}
}

//Delete any dataset files from the system that are now not used anywhere
function removeUnusedDatasetFiles() {

	$sql = "
		SELECT f.id
		FROM ". DB_NAME_PREFIX. "files AS f
		LEFT JOIN ". DB_NAME_PREFIX. "custom_dataset_files_link AS cdfl
		   ON cdfl.file_id = f.id
		WHERE f.`usage` = 'dataset_file'
		  AND cdfl.file_id IS NULL
		GROUP BY f.id";
	
	$result = sqlSelect($sql);
	while ($file = sqlFetchAssoc($result)) {
		Ze\File::delete($file['id']);
	}
}


function datasetFieldDBColumn($fieldId) {
	return getRow('custom_dataset_fields', 'db_column', $fieldId);
}

function datasetFieldId($fieldDbColumn) {
	return getRow('custom_dataset_fields', 'id', array('db_column' => $fieldDbColumn));
}

function getDatasetSystemFieldDetails($datasetId, $tabName, $fieldName) {
	return getRow('custom_dataset_fields', true, array('dataset_id' => $datasetId, 'tab_name' => $tabName, 'field_name' => $fieldName, 'is_system_field' => 1));
}

function getDatasetFieldValueLabel($valueId) {
	return getRow('custom_dataset_field_values', 'label', $valueId);
}

function getDatasetFieldLOVFlatArrayToLabeled(&$value, $key) {
	if (!is_array($value)) {
		$value = array('label' => $value);
	}
	
	++cms_core::$dbupCurrentRevision;
	
	if (empty($value['ord'])) {
		$value['ord'] = cms_core::$dbupCurrentRevision;
	}
}

function getDatasetFieldLOV($field, $flat = true, $filter = false) {
	if (!is_array($field)) {
		$field = getDatasetFieldDetails($field);
	}
	
	$lov = array();
	if (chopPrefixOffString('centralised_', $field['type'])) {
		if (!empty($field['values_source_filter'])) {
			$filter = $field['values_source_filter'];
		}
		if ($lov = getCentralisedListValues($field['values_source'], $filter)) {
			if (!$flat) {
				cms_core::$dbupCurrentRevision = 0;
				array_walk($lov, 'getDatasetFieldLOVFlatArrayToLabeled');
				cms_core::$dbupCurrentRevision = false;
			}
		}
	
	} elseif (chopPrefixOffString('dataset_', $field['type'])) {
		if ($labelDetails = getDatasetLabelFieldDetails($field['dataset_foreign_key_id'])) {
			
			$lov = getRowsArray($labelDetails['table'], $labelDetails['db_column'], array(), $labelDetails['db_column']);
			
			if (!$flat) {
				$ord = 0;
				foreach ($lov as &$v) {
					$v = array('ord' => ++$ord, 'label' => $v);
				}
			}
		}
	} elseif ($field['type'] == 'text') {
		if ($field['db_column']) {
			$dataset = getDatasetDetails($field['dataset_id']);
			$table = $field['is_system_field'] ? $dataset['system_table'] : $dataset['table'];
			$sql = '
				SELECT DISTINCT ' . sqlEscape($field['db_column']) . '
				FROM ' . DB_NAME_PREFIX . $table . '
				ORDER BY ' . sqlEscape($field['db_column']);
			$result = sqlSelect($sql);
			while ($row = sqlFetchRow($result)) {
				if ($row[0]) {
					$lov[$row[0]] = $row[0];
				}
			}
		}
	} else {
		if ($flat) {
			$cols = 'label';
		} else {
			$cols = array('ord', 'label', 'note_below');
		}
		
		$lov = getRowsArray('custom_dataset_field_values', $cols, array('field_id' => $field['id']), array('ord'));
	}
	return $lov;
}

function countDatasetFieldRecords($field, $dataset = false) {
	if (!is_array($field)) {
		$field = getDatasetFieldDetails($field);
	}
	if ($field && !is_array($dataset)) {
		$dataset = getDatasetDetails($field['dataset_id']);
	}
	
	if ($field && $dataset) {
		
		if ($field['type'] == 'checkboxes') {
			$sql = "
				SELECT COUNT(DISTINCT vl.linking_id)
				FROM ". DB_NAME_PREFIX. "custom_dataset_field_values AS fv
				INNER JOIN ". DB_NAME_PREFIX. "custom_dataset_values_link AS vl
				ON vl.value_id = fv.id
				WHERE fv.field_id = ". (int) $field['id'];
		
		} elseif ($field['type'] == 'file_picker') {
			$sql = "
				SELECT COUNT(DISTINCT linking_id)
				FROM ". DB_NAME_PREFIX. "custom_dataset_files_link
				WHERE dataset_id = ". (int) $dataset['id']. "
				  AND field_id = ". (int) $field['id'];
		
		} elseif (in($field['type'], 'checkbox', 'group', 'radios', 'select')) {
			$sql = "
				SELECT COUNT(*)
				FROM `". DB_NAME_PREFIX. sqlEscape($field['is_system_field']? $dataset['system_table'] : $dataset['table']). "`
				WHERE `". sqlEscape($field['db_column']). "` != 0";
		
		} else {
			$sql = "
				SELECT COUNT(*)
				FROM `". DB_NAME_PREFIX. sqlEscape($field['is_system_field']? $dataset['system_table'] : $dataset['table']). "`
				WHERE `". sqlEscape($field['db_column']). "` IS NOT NULL
				  AND `". sqlEscape($field['db_column']). "` != ''";
		}
		
		$result = sqlQuery($sql);
		$row = sqlFetchRow($result);
		
		return $row[0];
	} else {
		return false;
	}
}

function getDatasetLabelFieldDetails($otherDatasetId) {
	
	$details = array();
	
	if (($otherDatasetId)
	 && ($otherDataset = getDatasetDetails($otherDatasetId))
	 && ($otherDataset['label_field_id'])
	 && ($otherLabelField = getDatasetFieldBasicDetails($otherDataset['label_field_id']))
	 && ($details['db_column'] = $otherLabelField['db_column'])) {
		
		if ($otherLabelField['is_system_field']) {
			$details['table'] = $otherDataset['system_table'];
		} else {
			$details['table'] = $otherDataset['table'];
		}
		
		if ($details['table']
		 && ($details['id_column'] = getIdColumnOfTable($details['table'], true))) {
			
			return $details;
		}
	}
	
	return false;
}


function getUserGroups($user_id, $flat = true) {
	$groups = array();
	
	//Look up a list of group names on the system
	if (!is_array(cms_core::$groups)) {
		cms_core::$groups = getRowsArray('custom_dataset_fields', array('id', 'label', 'db_column'), array('type' => 'group', 'is_system_field' => 0), 'db_column', 'db_column');
	}
	
	if (!empty(cms_core::$groups)) {
		//Get the row from the users_custom_data table for this user
		//(Note that the group names stored in cms_core::$groups are the column names)
		$inGroups = getRow('users_custom_data', array_keys(cms_core::$groups), $user_id);
		
		//Come up with a subsection of the groups that this user is in
		foreach (cms_core::$groups as $groupCol => $group) {
			if (!empty($inGroups[$groupCol])) {
				if ($flat) {
					$groups[$group['id']] = $groupCol;
				} else {
					$groups[$group['id']] = $group;
				}
			}
		}
	}
	
	return $groups;
}

function checkUserInGroup($groupId, $userId = 'session') {
	
	if ($userId === 'session') {
		if (empty($_SESSION['extranetUserID'])) {
			return false;
		} else {
			$userId = $_SESSION['extranetUserID'];
		}
	}
	
	if (!$userId || !((int) $groupId)) {
		return false;
	}
	
	$group_name = datasetFieldDBColumn($groupId);
	
	if(!$group_name) {
		return false;
	}
	
	return (bool) getRow('users_custom_data', $group_name, $userId);
}

function getUserGroupsNames( $userId ) {
	$groups = getUserGroups($userId);
	
	if (empty($groups)) {
		return adminPhrase('_NO_GROUP_MEMBERSHIPS');
	} else {
		return implode(', ', $groups);
	}
}

function getGroupLabel($group_id) {
	if ($group_id) {
		if(is_numeric($group_id)) {
			return getRow('custom_dataset_fields', 'label', $group_id);
		} else {
			return getRow('custom_dataset_fields', 'label', array('db_column' => $group_id));
		}
	} else {
		return adminPhrase("_ALL_EXTRANET_USERS");
	}

}



//CONTENT TYPE
function getContentStatus($cID, $cType) {
	return getRow('content_items', 'status', array('id' => $cID, 'type' => $cType));
}


//Given an image size and a target size, resize the image (maintaining aspect ratio).
function resizeImage($imageWidth, $imageHeight, $constraint_width, $constraint_height, &$width_out, &$height_out, $allowUpscale = false) {
	$width_out = $imageWidth;
	$height_out = $imageHeight;
	
	if ($imageWidth == $constraint_width && $imageHeight == $constraint_height) {
		return;
	}
	
	if (!$allowUpscale && ($imageWidth <= $constraint_width) && ($imageHeight <= $constraint_height)) {
		return;
	}

	if (($constraint_width / $imageWidth) < ($constraint_height / $imageHeight)) {
		$width_out = $constraint_width;
		$height_out = (int) ($imageHeight * $constraint_width / $imageWidth);
	} else {
		$height_out = $constraint_height;
		$width_out = (int) ($imageWidth * $constraint_height / $imageHeight);
	}

	return;
}

//Given an image size and a target size, resize the image by different conditions and return the values used in the calculations
function resizeImageByMode(
	&$mode, $imageWidth, $imageHeight, $maxWidth, $maxHeight,
	&$newWidth, &$newHeight, &$cropWidth, &$cropHeight, &$cropNewWidth, &$cropNewHeight,
	$mimeType = ''
) {
	
	$maxWidth = (int) $maxWidth;
	$maxHeight = (int) $maxHeight;
	$allowUpscale = $mimeType == 'image/svg+xml';
	
	if ($mode == 'unlimited') {
		$cropNewWidth = $cropWidth = $newWidth = $imageWidth;
		$cropNewHeight = $cropHeight = $newHeight = $imageHeight;
	
	} elseif ($mode == 'stretch') {
		$allowUpscale = true;
		$cropWidth = $imageWidth;
		$cropHeight = $imageHeight;
		$cropNewWidth = $newWidth = $maxWidth;
		$cropNewHeight = $newHeight = $maxHeight;
	
	} elseif ($mode == 'resize_and_crop') {
		
		if (($maxWidth / $imageWidth) < ($maxHeight / $imageHeight)) {
			$newWidth = (int) ($imageWidth * $maxHeight / $imageHeight);
			$newHeight = $maxHeight;
			$cropWidth = (int) ($maxWidth * $imageHeight / $maxHeight);
			$cropHeight = $imageHeight;
			$cropNewWidth = $maxWidth;
			$cropNewHeight = $maxHeight;
		
		} else {
			$newWidth = $maxWidth;
			$newHeight = (int) ($imageHeight * $maxWidth / $imageWidth);
			$cropWidth = $imageWidth;
			$cropHeight = (int) ($maxHeight * $imageWidth / $maxWidth);
			$cropNewWidth = $maxWidth;
			$cropNewHeight = $maxHeight;
		}
	
	} elseif ($mode == 'fixed_width') {
		$maxHeight = $allowUpscale? 999999 : $imageHeight;
		$mode = 'resize';
	
	} elseif ($mode == 'fixed_height') {
		$maxWidth = $allowUpscale? 999999 : $imageWidth;
		$mode = 'resize';
	
	} else {
		$mode = 'resize';
	}
	
	if ($mode == 'resize') {
		$newWidth = false;
		$newHeight = false;
		resizeImage($imageWidth, $imageHeight, $maxWidth, $maxHeight, $newWidth, $newHeight, $allowUpscale);
		$cropWidth = $imageWidth;
		$cropHeight = $imageHeight;
		$cropNewWidth = $newWidth;
		$cropNewHeight = $newHeight;
	}
	
	if ($newWidth < 1) {
		$newWidth = 1;
	}
	if ($cropWidth < 1) {
		$cropWidth = 1;
	}
	if ($cropNewWidth < 1) {
		$cropNewWidth = 1;
	}
	
	if ($newHeight < 1) {
		$newHeight = 1;
	}
	if ($cropHeight < 1) {
		$cropHeight = 1;
	}
	if ($cropNewHeight < 1) {
		$cropNewHeight = 1;
	}
}

function resizeImageString(&$image, $mime_type, &$imageWidth, &$imageHeight, $maxWidth, $maxHeight, $mode = 'resize', $offset = 0) {
	//Work out the new width/height of the image
	$newWidth = $newHeight = $cropWidth = $cropHeight = $cropNewWidth = $cropNewHeight = false;
	resizeImageByMode($mode, $imageWidth, $imageHeight, $maxWidth, $maxHeight, $newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight, $mime_type);
	
	resizeImageStringToSize($image, $mime_type, $imageWidth, $imageHeight, $newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight, $offset);
	
	if (!is_null($image)) {
		$imageWidth = $cropNewWidth;
		$imageHeight = $cropNewHeight;
	}
}

function resizeImageStringToSize(&$image, $mime_type, $imageWidth, $imageHeight, $newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight, $offset = 0) {
	//Check if the image needs to be resized
	if ($imageWidth != $cropNewWidth || $imageHeight != $cropNewHeight) {
		if (Ze\File::isImage($mime_type)) {
			//Load the original image into a canvas
			if ($image = @imagecreatefromstring($image)) {
				//Make a new blank canvas
				$trans = -1;
				$resized_image = imagecreatetruecolor($cropNewWidth, $cropNewHeight);
		
				//Transparent gifs need a few fixes. Firstly, we need to fill the new image with the transparent colour.
				if ($mime_type == 'image/gif' && ($trans = imagecolortransparent($image)) >= 0) {
					$colour = imagecolorsforindex($image, $trans);
					$trans = imagecolorallocate($resized_image, $colour['red'], $colour['green'], $colour['blue']);				
			
					imagefill($resized_image, 0, 0, $trans);				
					imagecolortransparent($resized_image, $trans);
		
				//Transparent pngs should also be filled with the transparent colour initially.
				} elseif ($mime_type == 'image/png') {
					imagealphablending($resized_image, false); // setting alpha blending on
					imagesavealpha($resized_image, true); // save alphablending setting (important)
					$trans = imagecolorallocatealpha($resized_image, 255, 255, 255, 127);
					imagefilledrectangle($resized_image, 0, 0, $cropNewWidth, $cropNewHeight, $trans);
				}
		
				$xOffset = 0;
				$yOffset = 0;
				if ($newWidth != $cropNewWidth) {
					$xOffset = (int) (((10 - $offset) / 20) * ($imageWidth - $cropWidth));
		
				} elseif ($newHeight != $cropNewHeight) {
					$yOffset = (int) ((($offset + 10) / 20) * ($imageHeight - $cropHeight));
				}
		
				//Place a resized copy of the original image on the canvas of the new image
				imagecopyresampled($resized_image, $image, 0, 0, $xOffset, $yOffset, $cropNewWidth, $cropNewHeight, $cropWidth, $cropHeight);
		
				//The resize algorithm doesn't always respect the transparent colour nicely for gifs.
				//Solve this by resizing using a different algorithm which doesn't do any anti-aliasing, then using
				//this to create a transparent mask. Then use the mask to update the new image, ensuring that any pixels
				//that should be transparent actually are.
				if ($mime_type == 'image/gif') {
					if ($trans >= 0) {
						$mask = imagecreatetruecolor($cropNewWidth, $cropNewHeight);
						imagepalettecopy($image, $mask);
				
						imagefill($mask, 0, 0, $trans);				
						imagecolortransparent($mask, $trans);
				
						imagetruecolortopalette($mask, true, 256); 
						imagecopyresampled($mask, $image, 0, 0, $xOffset, $yOffset, $cropNewWidth, $cropNewHeight, $cropWidth, $cropHeight);
				
						$maskTrans = imagecolortransparent($mask);
						for ($y = 0; $y < $cropNewHeight; ++$y) {
							for ($x = 0; $x < $cropNewWidth; ++$x) {
								if (imagecolorat($mask, $x, $y) === $maskTrans) {
									imagesetpixel($resized_image, $x, $y, $trans);
								}
							}
						}
					}
				}
		
		
				$temp_file = tempnam(sys_get_temp_dir(), 'Img');
					if ($mime_type == 'image/gif') imagegif($resized_image, $temp_file);
					if ($mime_type == 'image/png') imagepng($resized_image, $temp_file);
					if ($mime_type == 'image/jpeg') imagejpeg($resized_image, $temp_file, $jpeg_quality = 100);
			
					imagedestroy($resized_image);
					unset($resized_image);
					$image = file_get_contents($temp_file);
				unlink($temp_file);

				//$imageWidth = $cropNewWidth;
				//$imageHeight = $cropNewHeight;
			} else {
				$image = null;
			}
		} else {
			//$imageWidth = $cropNewWidth;
			//$imageHeight = $cropNewHeight;
		}
	}
}



/* Content specific functions */



function getSearchtermParts($searchString) {
	//Remove everything from the search terms except for word characters, single quotes (which can be part of words) and double quotes
	//Attempt to validate allowing UTF-8 characters through
	if (!function_exists('mb_ereg_replace')
	 || !$searchString = mb_ereg_replace('[^\w\s_\'"]', ' ', $searchString)) {
		//Fall back to traditional pattern matching if that fails
		$searchString = preg_replace('/[^\w\s_\'"]/', ' ', $searchString);
	}
	
	//Limit the search results to 100 chars
	$searchString = substr($searchString, 0, 100);
	
	//Break the search string up into tokens.
	//Normally we break by spaces, but you can use a pattern in double quotes to override this
	preg_match_all('/"([^"]+)"|(\S+)/', trim($searchString), $searchStrings, PREG_SET_ORDER);
	
	$quotesUsed = false;
	$searchWordsAndPhrases = array();
	
	foreach($searchStrings as $i => $string) {
		//Have a limit of 10 words
		if ($i >= 10) {
			break;
		}
		
		//Remove any double-quotes that might still be in the text
		$string = str_replace('"', '', $string);
		
		if (isset($string[2])) {
			$searchWordsAndPhrases[$string[2]] = 'word';
		} else {
			$searchWordsAndPhrases[$string[1]] = 'phrase';
			$quotesUsed = true;
		}
	}
	
	//Just in case the user doesn't know about the "using quotes to group words together" feature,
	//add the whole phrase in as a search term.
	//Also do this as a fallback in case nothing was matched
	if (empty($searchWordsAndPhrases)
	 || (!$quotesUsed && count($searchStrings) > 1)) {
		$searchWordsAndPhrases[$searchString] = 'whole phrase';
	}
	
	return $searchWordsAndPhrases;
}




/* Categories */

function getCategoryName($id) {
	return getRow('categories', 'name', ['id' => $id]);
}

function getContentItemLayout($cID, $cType, $cVersion) {
	return getRow('content_item_versions', 'layout_id', array('id' => $cID, 'type' => $cType, 'version' => $cVersion));
}

function getContentItemCategories($cID, $cType, $publicOnly = false, $langId = false, $sql = false) {
	
	$equivId = equivId($cID, $cType);

	if ($sql === false) {
		$sql = "
			SELECT 
				c.id,
				c.parent_id,
				c.name,
				c.public
			FROM " . DB_NAME_PREFIX . "categories AS c
			INNER JOIN " . DB_NAME_PREFIX . "category_item_link AS cil
				ON c.id = cil.category_id
			WHERE cil.equiv_id = " . (int) $equivId . "
				AND cil.content_type = '" . sqlEscape($cType) . "'";
	}
	
	if ($publicOnly) {
		$sql .= "
			AND c.public = 1";
	}
				
	$result = sqlQuery($sql);
	
	if (sqlNumRows($result)>0) {
		while ($row = sqlFetchAssoc($result)) {
			if (!$row['public']) {
				$row['public_name'] = false;
			} else {
				$row['public_name'] = phrase('_CATEGORY_'. $row['id'], false, '', $langId);
			}
			
			$categories[] = $row;
		}
		
		return $categories;
	} else {
		return false;
	}
}

function setContentItemCategories($cID, $cType, $categories) {
	$equivId = equivId($cID, $cType);
	
	deleteRow('category_item_link', array('equiv_id' => $equivId, 'content_type' => $cType));
	
	if (is_array($categories)) {
		foreach ($categories as $value) {
			if ($value) {
				insertRow('category_item_link', array('category_id' => $value, 'equiv_id' => $equivId, 'content_type' => $cType));
			}
		}
	}
}
function addSingleContentItemToCategories($cID, $cType, $categories) {
	$equivId = equivId($cID, $cType);
	
	if (!is_array($categories)) {
		$categories = explodeAndTrim($categories);
	}
	
	foreach ($categories as $id) {
		if ($id) {
			setRow('category_item_link', array(), array('category_id' => $id, 'equiv_id' => $equivId, 'content_type' => $cType));
		}
	}
}
function addContentItemToCategories($cID, $cType, $categories) {
	$equivId = equivId($cID, $cType);
	
	if (!is_array($categories)) {
		$categories = explodeAndTrim($categories);
	}
	
	foreach ($categories as $id) {
		if ($id) {
			setRow('category_item_link', array(), array('category_id' => $id, 'equiv_id' => $equivId, 'content_type' => $cType));
		}
	}
}

function removeContentItemCategories($cID, $cType, $categories) {
	$equivId = equivId($cID, $cType);
	foreach ($categories as $value) {
		if ($value) {
			deleteRow('category_item_link', array('category_id' => $value, 'equiv_id' => $equivId, 'content_type' => $cType));
		}
	}		
}


/* Miscellaneous */

function formatNicely($text,$limit) {
	
	$text = trim(strip_tags($text));
	
	if (!$text || mb_strlen($text) < $limit) {
		return $text;
	} else {
		$new_content = mb_substr($text,0,$limit);
		
		$new_content = mb_ereg_replace('[^\s,]+$','',$new_content);
		$new_content = mb_ereg_replace('[\s\t\n\r]+$','',$new_content);	
		$new_content = mb_ereg_replace(',+$','',$new_content);	
			
		if (preg_match('/[\.]$/',$new_content) || !$text) {
			return $new_content;
		} else {
			return $new_content. '...';
		}
		
		return $new_content;
	}
}

function languageIdForDatesInAdminMode() {
	return ifNull(
		getRow('visitor_phrases', 'language_id', array('code' => '_WEEKDAY_0', 'language_id' => array('en', 'en-gb', 'en-us'))),
		cms_core::$defaultLang,
		'en-us');
}

function configFileSize($size) {
	//Define labels to use
	$labels = array('', 'K', 'M', 'G', 'T');
	$precision = 0;
	
	//Work out which of the labels to use, based on how many powers of 1024 go into the size, and
	//how many labels we have
	$order = min(
				floor(
					log($size) / log(1024)
				),
			  count($labels)-1);
	
	return round($size / pow(1024, $order), $precision). $labels[$order];
}

function formatFilesizeNicely($size, $precision = 0, $adminMode = false, $vlpClass = '') {
	
	if (is_array($size)) {
		$size = $size['size'];
	}
	
	//Return 0 without formating if the size is 0.
	if ($size <= 0) {
		return '0';
	}
	
	//Define labels to use
	$labels = array('_BYTES', '_KBYTES', '_MBYTES', '_GBYTES', '_TBYTES');
	
	//Work out which of the labels to use, based on how many powers of 1024 go into the size, and
	//how many labels we have
	$order = min(
				floor(
					log($size) / log(1024)
				),
			  count($labels)-1);
	
	$mrg = 
		array('size' => 
			round($size / pow(1024, $order), $precision)
		);
	
	if ($adminMode) {
		return adminPhrase($labels[$order], $mrg);
	} else {
		return phrase($labels[$order], $mrg, $vlpClass);
	}
}

function formatFileTypeNicely($type, $vlpClass = '') {
	switch($type) {
	case 'image/jpeg': 
		$new_type = phrase('_JPEG_file', false, $vlpClass);
		break;
	case 'image/pjpeg': 
		$new_type = phrase('_JPEG_file', false, $vlpClass);
		break;
	case 'image/jpg': 
		$new_type = phrase('_JPG_file', false, $vlpClass);
		break;
	case 'image/gif': 
		$new_type = phrase('_GIF_file', false, $vlpClass);
		break;
	case 'image/png': 
		$new_type = phrase('_PNG_file', false, $vlpClass);
		break;
	default:
		$new_type = phrase('_UNDEFINED', false, $vlpClass);
	}
	return $new_type;
}



//Check if the random number generator has been seeded yet, and seed it if not
function seedRandomNumberGeneratorIfNeeded() {
	//Use whether the RANDOM_NUMBER_GENERATOR_IS_SEEDED constant is defined to remember
	//if we've seeded yet
	if (!defined('RANDOM_NUMBER_GENERATOR_IS_SEEDED')) {
		//If we need to seed the random number generator, get the number of microseconds
		//past the current second, and use that to seed
		$x = explode(' ', microtime());
		mt_srand((int) (1000000 * $x[0]));
		
		//Define the RANDOM_NUMBER_GENERATOR_IS_SEEDED constant so that we don't seed again
		define('RANDOM_NUMBER_GENERATOR_IS_SEEDED', true);
	}
}

//Generate a random string of numbers and letters
function randomString($requiredLength = 12) {
	
	seedRandomNumberGeneratorIfNeeded();
	
	$stringOut = '';
	//Loop while our output string is still too short
	while (strlen($stringOut) < $requiredLength) {
		$stringOut .= base64(pack('I', mt_rand()));
	}
	return substr($stringOut, 0, $requiredLength);
}

//Generate a string from a specific set of characters
	//By default I've stripped out vowels, just in case a swearword is randomly generated.
	//Also "1"s look too much like "l"s and "0"s look too much like "o"s so I've removed those too for clarity.
function randomStringFromSet($requiredLength = 12, $set = 'BCDFGHJKMNPQRSTVWXYZbcdfghjkmnpqrstvwxyz23456789') {
	$lettersToUse = str_split($set);
	$max = count($lettersToUse) - 1;
	
	$stringOut = '';
	for ($i = 0; $i < $requiredLength; ++$i) {
		$stringOut .= $lettersToUse[mt_rand(0, $max)];
	}
	
	return $stringOut;
}


//Returns an array of password strength names to scores
//Defined as a function so that it is only hardcoded here, rather
//than everywhere it is used.
function passwordStrengthsToValues($strength = false) {
	$a = array('_WEAK' => 0, '_MEDIUM' => 35, '_STRONG' => 70, '_VERY_STRONG' => 100);
	//Either return an array, or if $strength is set, return the score for that strength
	if ($strength) {
		return $a[$strength];
	} else {
		return $a;
	}
}

function passwordValuesToStrengths($value) {
	if ($value < 35) {
		return '_WEAK';
	} elseif ($value < 70) {
		return '_MEDIUM';
	} elseif ($value < 100) {
		return '_STRONG';
	} else {
		return '_VERY_STRONG';
	}
}


//Calculate the bonus for using different sets of characters
	//e.g. lower case, upper case, numeric, non-alphanumeric...
function calculateBonusFromUsage($n) {
	return min(2, $n);
}

//Check if a given password meets the strength requirements.
function checkPasswordStrength($pass, $passwordStrengthRequired = false) {
	return require funIncPath(__FILE__, __FUNCTION__);
}





function getEmailTemplate ($name) {
	$sql = "SELECT subject,
				body
			FROM " . DB_NAME_PREFIX . "email_templates
			WHERE template_name = '" . sqlEscape($name) . "'";
	
	$result = sqlQuery($sql);
	
	if (sqlNumRows($result)>0) {
		$row = sqlFetchArray($result);
		return $row;
	} else {
		return false;
	}
}



/*  modules  */

function getModuleDetails($idOrName, $fetchBy = 'id') {
	$sql = "
		SELECT
			id,
			id AS module_id,
			class_name,
			class_name AS name,
			display_name,
			vlp_class,
			status,
			default_framework,
			css_class_name,
			is_pluggable,
			can_be_version_controlled
		FROM " . DB_NAME_PREFIX . "modules";
	
	if ($fetchBy == 'class' || $fetchBy == 'name') {
		$sql .= "
			WHERE class_name = '" . sqlEscape($idOrName) . "'";
	
	} else {
		$sql .= "
			WHERE id = " . (int) $idOrName;
	}
	
	$result = sqlQuery($sql);
	
	if (!$module = sqlFetchAssoc($result)) {
		return false;
	} else {
		return $module;
	}
}

function getModuleName($id) {
	return getRow('modules', 'class_name', ['id' => $id]);
}

function getModuleDisplayName($id) {
	return getRow('modules', 'display_name', ['id' => $id]);
}

function getModuleDisplayNameByClassName($name) {
	return getRow('modules', 'display_name', ['class_name' => $name]);
}

function getNestedPluginName($id) {
	return getRow('nested_plugins', 'name_or_title', $id);
}

function getModuleClassName($id) {
	return getRow('modules', 'class_name', ['id' => $id]);
}

function getModuleId($name) {
	return getRow('modules', 'id', ['class_name' => $name]);
}

function getModuleStatus($id) {
	return getRow('modules', 'status', ['id' => $id]);
}

function getModuleStatusByName($name) {
	return getRow('modules', 'status', ['class_name' => $name]);
}

function getModuleStatusByClassName($name) {
	return getRow('modules', 'status', ['class_name' => $name]);
}

function getModuleIdByClassName($name) {
	return getRow('modules', 'id', ['class_name' => $name]);
}

function getModuleClassNameByName($name) {
	return $name;
}

function checkModuleRunning($className) {
	
	return (bool) sqlFetchRow("
		SELECT 1
		FROM [modules AS m]
		WHERE class_name = [0]
		  AND status IN ('module_running', 'module_is_abstract')
	", [$className]);
}


function canActivateModule($name, $fetchBy = 'name', $activate = false) {
	
	$error = array();
	$missingPlugin = false;
	
	if ($module = getModuleDetails($name, $fetchBy)) {
		if ($module['status'] != 'module_running'
		 && $module['status'] != 'module_is_abstract') {
			return false;
		
		} elseif ($activate && !includeModuleAndDependencies($module['class_name'], $missingPlugin)) {
			return false;
		}
	
	} else {
		return false;
	}
	
	
	if ($activate) {
		setModulePrefix($module);
		$useThisClassInstance = false;
		
		$class = new $module['class_name'];
		
		$class->setInstance(array(false, false, false, false, false, false, $module['class_name'], $module['vlp_class'], $module['id'], false, false, false, false, false));

		return $class;
	} else {
		return true;
	}
}

function activateModule($name) {
	return canActivateModule($name, 'class', true);
}


function inc($module) {
	
	//Don't allow this to be run whe running databse updates!
	if (!class_exists('module_base_class')) {
		return false;
	}
	
	if (!is_array($module)) {
		$module = sqlFetchAssoc("
			SELECT id, class_name, status
			FROM ". DB_NAME_PREFIX. "modules
			WHERE class_name = '". sqlEscape($module). "'
			LIMIT 1");
	}
	
	$missingPlugin = array();
	
	if ($module
	 && ($module['status'] == 'module_running' || $module['status'] == 'module_is_abstract')
	 && (includeModuleAndDependencies($module['class_name'], $missingPlugin))) {
		setModulePrefix($module);
		return true;
	} else {
		return false;
	}
}

function includeModuleSubclass($filePathOrModuleClassName, $type = false, $path = false, $raiseFileMissingErrors = false) {
	
	if (!$type) {
		$type = cms_core::$skType;
	}
	if (!$path) {
		$path = cms_core::$skPath;
	}
	
	//Catch a renamed variable
	if ($type == 'storekeeper') {
		$type = 'organizer';
	}
	
	if (strpos($filePathOrModuleClassName, '/') === false
	 && strpos($filePathOrModuleClassName, '\\') === false) {
		$basePath = CMS_ROOT. moduleDir($filePathOrModuleClassName);
		$moduleClassName = $filePathOrModuleClassName;
	} else {
		$basePath = dirname($filePathOrModuleClassName);
		$moduleClassName = basename($basePath);
	}
	
	//Modules use the owner/author name at the start of their name. Get this prefix.
	$prefix = explode('_', $moduleClassName, 2);
	if (!empty($prefix[1])) {
		$prefix = $prefix[0];
	} else {
		$prefix = '';
	}
	
	//Take the path, and try to get the name of the last tag in the tag path.
	//(But if the last tag is "panel", remove that as the second-last tag will be more helpful.)
	//Also try to remove the prefix from above.
	$matches = array();
	preg_match('@.*/_*(\w+)@', str_replace('/'. $prefix. '_', '/', str_replace('/panel', '', '/'. $path)), $matches);
	
	if (empty($matches[1])) {
		exit('Bad path: '. $path);
	}
	
	//From the logic above, create a standard filepath and class name
	$phpPath = $basePath. '/classes/'. $type. '/'. $matches[1]. '.php';
	$className = $moduleClassName. '__'. $type. '__'. $matches[1];
	
	//Also check for a filepath by stripping off the classname from the path
	//e.g. (zenario_example_manager__test could be called test.php)
	if (!is_file($phpPath)) {
		$matches = array();
		preg_match('@.*/_*(\w+)@', str_replace('/'. $moduleClassName. '_', '/', str_replace('/panel', '', '/'. $path)), $matches);
		$phpPathAlt = $basePath. '/classes/'. $type. '/'. $matches[1]. '.php';
		if (is_file($phpPathAlt)) {
			$phpPath = $phpPathAlt;
		}
	}
	
	//Check if the file is there
	if (is_file($phpPath)) {
		require_once $phpPath;
	
		if (class_exists($className)) {
			return $className;
		} else {
			exit('The class '. $className. ' was not defined in '. $phpPath);
		}
	
	} else {
		if ($raiseFileMissingErrors) {
			exit('The class '. $className. ' could not be loaded because the file at '. $phpPath. ' was not found.');
		} else {
			return false;
		}
	}
}

function getModuleDependencies($moduleName) {
	$sql = "
		SELECT
			d.dependency_class_name,
			d.`type`,
			m.class_name,
			m.id AS module_id,
			m.vlp_class
		FROM ". DB_NAME_PREFIX. "module_dependencies AS d
		LEFT OUTER JOIN ". DB_NAME_PREFIX. "modules AS m
		   ON d.dependency_class_name = m.class_name
		  AND m.status IN ('module_running', 'module_is_abstract')
		WHERE d.module_class_name = '". sqlEscape($moduleName). "'
		  AND `type` = 'dependency'";
	
	return sqlFetchAssocs($sql);
}


//Include all of a Module's Dependency files, then include the Module
//Note: you need to check to see if a Module is running first, before calling this
function includeModuleAndDependencies($moduleName, &$missingPlugin, $recurseCount = 9) {
	
	if (!$recurseCount) {
		return false;
	}
	
	//Check that this has not been included already - if so, our job has already been done
	if (isset(cms_core::$modulesLoaded[$moduleName])) {
		return cms_core::$modulesLoaded[$moduleName];
	}

	//Check for dependencies
	foreach (getModuleDependencies($moduleName) as $row) {
		//For each dependency, check if it is running and try to include it
		if ($row['module_id'] && includeModuleAndDependencies($row['class_name'], $missingPlugin, $recurseCount-1)) {
			setModulePrefix($row);
		} else {
			//Otherwise report a dependancy as missing, then stop
			$missingPlugin = array('module' => $row['dependency_class_name']);
			return false;
		}
	}
	
	$missingPlugin = false;
	
	$file = moduleDir($moduleName, 'module_code.php');
	if (cms_core::$modulesLoaded[$moduleName] = file_exists($file = moduleDir($moduleName, 'module_code.php'))) {
		require_once $file;
		return true;
	} else {
		return false;
	}
}


//Try to check the tuix_file_contents table to see which files we need to include
function modulesAndTUIXFiles(
	$type, $requestedPath = false, $settingGroup = '',
	$getIndividualFiles = true, $includeBaseFunctionalityWithSettingGroups = true,
	$compatibilityClassNames = false, $runningModulesOnly = true
) {
	return require funIncPath(__FILE__, __FUNCTION__);
}



/* Instances and plugin settings */

function getModuleInheritance($moduleClassName, $type) {
	$sql = "
		SELECT dependency_class_name
		FROM ".  DB_NAME_PREFIX. "module_dependencies
		WHERE module_class_name = '". sqlEscape($moduleClassName). "'
		  AND `type` = '". sqlEscape($type). "'
		LIMIT 1";
	
	$result = sqlQuery($sql);
	if ($row = sqlFetchAssoc($result)) {
		return $row['dependency_class_name'];
	} else {
		return false;
	}
}

function getModuleInheritances($moduleClassName, $type, $includeCurrent = true, $recurseLimit = 9) {
	$inheritances = array();
	
	if ($includeCurrent) {
		$inheritances[] = $moduleClassName;
	}
	
	while (--$recurseLimit && ($moduleClassName = getModuleInheritance($moduleClassName, $type))) {
		$inheritances[] = $moduleClassName;
	}
	
	return $inheritances;
}



//Returns the name of the currently running plugin, in upper-case.
//Must be called from code within the plugin's own folder with the __FILE__ Magic Constant
//The main reason for this function is to use in the latest_revision_no.inc.php files, to keep them
//nice and tidy.
function moduleName($file) {
	//Take the current path
		//Match up to and including the modules directory with .*[/\\]modules[/\\]
		//The next sequence of chars will be the modules directory
		//There may be another slash, or to the path - match with [/\\]?.*
	return strtoupper(preg_replace('#.*modules[/\\\\](\w*)[/\\\\]?\w*[/\\\\]?#', '\1', dirname($file)));
}


/* Plugin Slots */

//Get a list of every plugin instance currently running on a page
function getSlotContents(
	&$slotContents,
	$cID, $cType, $cVersion,
	$layoutId = false, $templateFamily = false, $templateFileBaseName = false,
	$specificInstanceId = false, $specificSlotName = false, $ajaxReload = false,
	$runPlugins = true, $exactMatch = false, $overrideSettings = false, $overrideFrameworkAndCSS = false
) {
	
	if ($layoutId === false) {
		$layoutId = contentItemTemplateId($cID, $cType, $cVersion);
	}
	
	if ($templateFamily === false) {
		$templateFamily = getRow('layouts', 'family_name', $layoutId);
	}
	
	if ($templateFileBaseName === false) {
		$templateFileBaseName = getRow('layouts', 'file_base_name', $layoutId);
	}
	
	
	$slots = array();
	$slotContents = array();
	$modules = getRunningModules();
	
	$whereSlotName = '';
	if ($specificSlotName && !$specificInstanceId) {
		$whereSlotName = "
			  AND slot_name = '". sqlEscape($specificSlotName). "'";
	}
	
	//Look for every plugin instance on the current page, prioritising item level
	//over Layout level, and Layout level over Template Family level.
	$sql = "
		SELECT
			pi.slot_name,
			pi.module_id,
			pi.instance_id,
			vcpi.id AS vcpi_id,
			tsl.slot_name IS NOT NULL as `exists`,
			pi.level
		FROM (
			SELECT slot_name, module_id, instance_id, id, 'template' AS type, 2 AS level
			FROM ". DB_NAME_PREFIX. "plugin_layout_link
			WHERE family_name = '". sqlEscape($templateFamily). "'
			  AND layout_id = ". (int) $layoutId.
			  $whereSlotName;
	
	if ($cID) {
		$sql .= "
		  UNION
			SELECT slot_name, module_id, instance_id, id, 'item' AS type, 1 AS level
			FROM ". DB_NAME_PREFIX. "plugin_item_link
			WHERE content_id = ". (int) $cID. "
			  AND content_type = '". sqlEscape($cType). "'
			  AND content_version = ". (int) $cVersion.
			  $whereSlotName;
	}
	
	$sql .= "
		) AS pi";
	
	//Don't show missing slots, except for Admins with the correct permissions
	if (!(checkPriv('_PRIV_MANAGE_ITEM_SLOT') || checkPriv('_PRIV_MANAGE_ITEM_SLOT')) && !($specificInstanceId || $specificSlotName)) {
		$sql .= "
		INNER JOIN ". DB_NAME_PREFIX. "template_slot_link AS tsl";
	} else {
		$sql .= "
		LEFT JOIN ". DB_NAME_PREFIX. "template_slot_link AS tsl";
	}
	
	$sql .= "
		   ON tsl.family_name = '". sqlEscape($templateFamily). "'
		  AND tsl.file_base_name = '". sqlEscape($templateFileBaseName). "'
		  AND tsl.slot_name = pi.slot_name";
	
	$sql .= "
		LEFT JOIN ". DB_NAME_PREFIX. "plugin_instances AS vcpi
		   ON vcpi.module_id = pi.module_id
		  AND vcpi.content_id = ". (int) $cID. "
		  AND vcpi.content_type = '". sqlEscape($cType). "'
		  AND vcpi.content_version = ". (int) $cVersion. "
		  AND vcpi.slot_name = pi.slot_name
		  AND pi.instance_id = 0
		WHERE TRUE";
	
	if ($exactMatch && $specificInstanceId) {
		$sql .= "
		  AND IFNULL(vcpi.id, pi.instance_id) = ". (int) $specificInstanceId. "";
	}
	if ($exactMatch && $specificSlotName) {
		$sql .= "
		  AND pi.slot_name = '". sqlEscape($specificSlotName). "'";
	}
	
	$sql .= "
		ORDER BY";
	
	if (!$exactMatch && $specificInstanceId) {
		$sql .= "
			IFNULL(vcpi.id, pi.instance_id) = ". (int) $specificInstanceId. " DESC,";
	}
	if (!$exactMatch && $specificSlotName) {
		$sql .= "
			pi.slot_name = '". sqlEscape($specificSlotName). "' DESC,";
	}
	
	if ($specificInstanceId || $specificSlotName) {
		$sql .= "
			pi.level ASC,
			pi.slot_name
		LIMIT 1";
		
		$checkOpaqueRulesAreValid = false;
	
	} else {
		$sql .= "
			pi.level DESC,
			pi.slot_name";
		
		$checkOpaqueRulesAreValid = true;
	}
	
	
	$result = sqlQuery($sql);
	while($row = sqlFetchAssoc($result)) {
		
		//Don't allow Opaque missing slots to count as missing slots
		if (empty($row['module_id']) && !$row['exists']) {
			continue;
		}
		
		//Check if this is a version-controlled Plugin instance
		$isVersionControlled = false;
		if ($row['module_id'] != 0 && $row['instance_id'] == 0) {
			$isVersionControlled = true;
			
			//Check if an instance has been inserted for this Content Item
			if ($row['vcpi_id']) {
				$row['instance_id'] = $row['vcpi_id'];
			
			//Otherwise, create and insert a new version controlled instance
			} elseif ($runPlugins) {
				$row['instance_id'] =
					getVersionControlledPluginInstanceId($cID, $cType, $cVersion, $row['slot_name'], $row['module_id']);
			}
		}
		
		//The "Opaque" option is a special case; let it through without an "is running" check
		if ($row['module_id'] == 0) {
			//The "Opaque" option is used to hide plugins on the layout layer on specific pages.
			//It's not valid if it's not actually covering anything up!
			if ($checkOpaqueRulesAreValid && empty($slotContents[$row['slot_name']])) {
				continue;
			}
			
			$slotContents[$row['slot_name']] = array('instance_id' => 0, 'module_id' => 0);
			$slotContents[$row['slot_name']]['error'] = adminPhrase('[Plugin hidden on this content item]');
			$slotContents[$row['slot_name']]['level'] = $row['level'];
			$slots[$row['slot_name']] = true;
		
		//Otherwise, if the instance is running, allow it to be added to the page
		} elseif (!empty($modules[$row['module_id']])) {
			$slotContents[$row['slot_name']] = $modules[$row['module_id']];
			$slotContents[$row['slot_name']]['level'] = $row['level'];
			$slotContents[$row['slot_name']]['module_id'] = $row['module_id'];
			$slotContents[$row['slot_name']]['instance_id'] = $row['instance_id'];
			$slotContents[$row['slot_name']]['css_class'] = $modules[$row['module_id']]['css_class_name'];
			
			if ($isVersionControlled) {
				$slotContents[$row['slot_name']]['content_id'] = $cID;
				$slotContents[$row['slot_name']]['content_type'] = $cType;
				$slotContents[$row['slot_name']]['content_version'] = $cVersion;
				$slotContents[$row['slot_name']]['slot_name'] = $row['slot_name'];
			}
			
			$slotContents[$row['slot_name']]['cache_if'] = array();
			$slotContents[$row['slot_name']]['clear_cache_by'] = array();
			
			$slots[$row['slot_name']] = true;
		}
	}
	
	$edition = cms_core::$edition;
	
	//Attempt to initialise each plugin on the page
	if ($runPlugins) {
		foreach ($slots as $slotName => $dummy) {
			if (!empty($slotContents[$slotName]['class_name']) && !empty($slotContents[$slotName]['instance_id'])) {
				$moduleClassName = $slotContents[$slotName]['class_name'];
		
				if (!isset(cms_core::$modulesOnPage[$moduleClassName])) {
					cms_core::$modulesOnPage[$moduleClassName] = array();
				}
				cms_core::$modulesOnPage[$moduleClassName][] = $slotName;
			}
		}
				
		foreach ($slots as $slotName => $dummy) {
			if (!empty($slotContents[$slotName]['class_name']) && !empty($slotContents[$slotName]['instance_id'])) {
				
				$thisSettings = $thisFrameworkAndCSS = false;
				if ($overrideSettings !== false && $slotName == request('slotName')) {
					$thisSettings = $overrideSettings;
				}
				if ($overrideFrameworkAndCSS !== false && $slotName == request('slotName')) {
					$thisFrameworkAndCSS = $overrideFrameworkAndCSS;
				}
				
				$edition::loadPluginInstance(
					$slotContents, $slotName,
					$cID, $cType, $cVersion,
					$layoutId, $templateFamily, $templateFileBaseName,
					$specificInstanceId, $specificSlotName, $ajaxReload,
					$runPlugins, $thisSettings, $thisFrameworkAndCSS);
		
			} elseif (!empty($slotContents[$slotName]['level'])) {
				setupNewBaseClassPlugin($slotName);
			
				//Treat the case of hidden (item layer) and empty (layout layer) as just empty,
				//but if there is something hidden at the item layer and there is a plugin
				//at the layout layer, show a special message
				if (!$checkOpaqueRulesAreValid
				 && $slotContents[$slotName]['level'] == 1
				 && $layoutId
				 && checkRowExists('plugin_layout_link', array('slot_name' => $slotName, 'layout_id' => $layoutId))) {
					$slotContents[$slotName]['error'] = adminPhrase('[Plugin hidden on this content item]');
				}
			}
		}
	}
}





function getVersionControlledPluginInstanceId($cID, $cType, $cVersion, $slotName, $moduleId) {
	
	
	if ($cID == 0 || $cID == -1) {
		return $cID;
	}
	
	$ids = array('module_id' => $moduleId, 'content_id' => $cID, 'content_type' => $cType, 'content_version' => $cVersion, 'slot_name' => $slotName);

	if (!$instanceId = getRow('plugin_instances', 'id', $ids)) {
		$instanceId = insertRow('plugin_instances', $ids);
	}
	
	return $instanceId;
}



//Activate and setup a plugin
//Note that the function canActivateModule() or equivalent should be called on the plugin's name before calling setInstance(), loadPluginInstance() or initPluginInstance()
function setInstance(&$instance, $cID, $cType, $cVersion, $slotName, $checkForErrorPages = false, $overrideSettings = false, $eggId = 0, $slideId = 0) {
	
	$missingPlugin = false;
	if (!includeModuleAndDependencies($instance['class_name'], $missingPlugin)) {
		$instance['class'] = false;
		return false;
	}
	
	$instance['class'] = new $instance['class_name'];
	
	$instance['class']->setInstance(
		array(
			$cID, $cType, $cVersion, $slotName,
			($instance['instance_name'] ?? false), $instance['instance_id'],
			$instance['class_name'], $instance['vlp_class'],
			$instance['module_id'],
			$instance['default_framework'], $instance['framework'],
			$instance['css_class'],
			($instance['level'] ?? false), !empty($instance['content_id'])
		), $overrideSettings, $eggId, $slideId
	);
}

//Work out whether we are displaying this Plugin.
//Run the plugin's own initalisation routine. If it returns true, then display the plugin.
//(But note that modules are always displayed in admin mode.)
function initPluginInstance(&$instance) {
	if (!($instance['init'] = $instance['class']->init()) && !(checkPriv())) {
		$instance['class'] = false;
		return false;
	} else {
		return true;
	}
}

function getPluginInstanceDetails($instanceIdOrName, $fetchBy = 'id') {
	
	if (!$instanceIdOrName) {
		return false;
	}
	
	$sql = "
		SELECT
			i.id AS instance_id,
			i.name AS instance_name,
			i.content_id,
			i.content_type,
			i.content_version,
			i.slot_name,
			IF(i.framework = '', m.default_framework, i.framework) AS framework,
			m.default_framework,
			m.css_class_name,
			i.css_class,
			i.module_id,
			m.class_name,
			m.display_name,
			m.vlp_class,
			m.status
		FROM ". DB_NAME_PREFIX. "plugin_instances AS i
		INNER JOIN ". DB_NAME_PREFIX. "modules AS m
		   ON m.id = i.module_id";
	
	if ($fetchBy == 'id') {
		$sql .= "
		WHERE i.id = ". (int) $instanceIdOrName;
	
	} elseif ($fetchBy == 'name') {
		$sql .= "
		WHERE i.name = '". sqlEscape($instanceIdOrName). "'";
	
	} else {
		return false;
	}
	
	$result = sqlQuery($sql);
	$instance = sqlFetchAssoc($result);
	
	if ($instance['content_id'] && checkPriv()) {
		$instance['instance_name'] = $instance['display_name'];
	}
	
	unset($instance['display_name']);
	return $instance;
}

function getPluginInstanceName($instanceId) {
	$instanceDetails = getPluginInstanceDetails($instanceId);
	return $instanceDetails['instance_name'];
}

function getPluginInItemSlot($slotName, $cID, $cType = 'html', $cVersion = false) {
	return getPluginInstanceInItemSlot($slotName, $cID, $cType, $cVersion, true);
}

function getPluginInstanceInItemSlot($slotName, $cID, $cType = 'html', $cVersion = false, $getModuleId = false) {
	
	if (!$cVersion) {
		$cVersion = getLatestVersion($cID, $cType);
	}
	
	$sql = "
		SELECT ". ($getModuleId? 'module_id' : 'instance_id'). "
		FROM ". DB_NAME_PREFIX. "plugin_item_link
		WHERE slot_name = '". sqlEscape($slotName). "'
		  AND content_id = ". (int) $cID. "
		  AND content_type = '". sqlEscape($cType). "'
		  AND content_version = ". (int) $cVersion;
	
	$result = sqlQuery($sql);
	if ($row = sqlFetchRow($result)) {
		return $row[0];
	} else {
		return false;
	}
}

function checkInstanceIsWireframeOnItemLayer($instanceId) {
	return
		($plugin = getRow('plugin_instances', array('content_id', 'content_type', 'content_version', 'slot_name', 'module_id'), $instanceId))
	 && (!($plugin['instance_id'] = 0))
	 && (checkRowExists('plugin_item_link', $plugin));
}

function getPluginInTemplateSlot($slotName, $templateFamily, $layoutId) {
	return getPluginInstanceInTemplateSlot($slotName, $templateFamily, $layoutId, true);
}

function getPluginInstanceInTemplateSlot($slotName, $templateFamily, $layoutId, $getModuleId = false) {
	
	$sql = "
		SELECT ". ($getModuleId? 'module_id' : 'instance_id'). "
		FROM ". DB_NAME_PREFIX. "plugin_layout_link
		WHERE slot_name = '". sqlEscape($slotName). "'
		  AND family_name = '". sqlEscape($templateFamily). "'
		  AND layout_id = ". (int) $layoutId;
	
	$result = sqlQuery($sql);
	if ($row = sqlFetchRow($result)) {
		return $row[0];
	} else {
		return false;
	}
}

//Attempt to find the path to a Framework
function frameworkPath($framework, $className, $includeFilename = false, $limit = 10) {
	if (!--$limit) {
		return false;
	}
	
	if ($path = moduleDir($className, 'frameworks/'. $framework. '/framework.twig.html', true, true)) {
		if ($includeFilename) {
			return $path;
		} else {
			return substr($path, 0, -19);
		}
	} elseif ($path = moduleDir($className, 'frameworks/'. $framework. '/framework.html', true, true)) {
		if ($includeFilename) {
			return $path;
		} else {
			return substr($path, 0, -14);
		}
	}
	
	$sql = "
		SELECT dependency_class_name
		FROM ". DB_NAME_PREFIX. "module_dependencies
		WHERE type = 'inherit_frameworks'
		  AND module_class_name = '". sqlEscape($className). "'
		LIMIT 1";
	
	if (($result = sqlQuery($sql))
	 && ($row = sqlFetchRow($result))) {
		return frameworkPath($framework, $row[0], $includeFilename, $limit);
	} else {
		return false;
	}
}

function frameworkCheckArrayOfArrays(&$mergeFields) {
	//Check if we have an array or an array of arrays
	$arrayOfArrays = false;
	if (!is_array($mergeFields)) {
		$mergeFields = array();
	
	} else {
		foreach($mergeFields as &$mergeFieldsRow) {
			$arrayOfArrays = is_array($mergeFieldsRow);
			break;
		}
	}
	
	//Allow an array of arrays to be passed as mergeFields. If this happens, then we'll
	//display the template once for each mergeField.
	if (!$arrayOfArrays) {
		//Otherwise just display the template once by turning the array into an
		//array of a single array
		$mergeFields = array($mergeFields);
	}
}





function sendSignal($signalName, $signalParams) {
	//Don't try to send a signal if we are in the Admin Login Screen applying Database Updates
	if (!class_exists('module_base_class')) {
		return false;
	}
	
	if (!empty(cms_core::$signalsCurrentlyTriggered[$signalName])) {
		return false;
	}
	
	cms_core::$signalsCurrentlyTriggered[$signalName] = true;
	
		$sql = "
			SELECT module_id, module_class_name, module_class_name AS class_name, static_method
			FROM ". cms_core::$lastDBPrefix. "signals
			WHERE signal_name = '". sqlEscape($signalName). "'
			  AND module_class_name NOT IN (
				SELECT suppresses_module_class_name
				FROM ". cms_core::$lastDBPrefix. "signals AS e
				INNER JOIN ". cms_core::$lastDBPrefix. "modules AS m
				   ON e.module_id = m.id
				WHERE e.signal_name = '". sqlEscape($signalName). "'
				  AND e.suppresses_module_class_name != ''
				  AND m.status IN ('module_running', 'module_is_abstract')
			  )
			ORDER BY signal_name, module_class_name";
	
		$returns = array();
		$result = sqlQuery($sql);
		while($row = sqlFetchAssoc($result)) {
			if (inc($row['class_name'])) {
				if ($row['static_method']) {
					$returns[$row['class_name']] = call_user_func_array(array($row['class_name'], $signalName), $signalParams);
				} else {
					$module = new $row['class_name'];
					$returns[$row['class_name']] = call_user_func_array(array($module, $signalName), $signalParams);
				}
			}
		}
	
	unset(cms_core::$signalsCurrentlyTriggered[$signalName]);
	return $returns;
}



/* Skins */

function getSkinFromId($skinId) {
	return getRow('skins', array('id', 'family_name', 'name', 'display_name', 'extension_of_skin', 'import', 'css_class', 'missing'), array('id' => $skinId));
}

function getSkinFromName($familyName, $skinName) {
	return getRow('skins', array('id', 'family_name', 'name', 'display_name', 'extension_of_skin', 'import', 'css_class', 'missing'), array('family_name' => $familyName, 'name' => $skinName));
}

function getSkinPath($templateFamily = false, $skinName = false) {
	return zenarioTemplatePath(ifNull($templateFamily, cms_core::$templateFamily)). 'skins/'. ifNull($skinName, cms_core::$skinName). '/';
}

function getSkinPathURL($templateFamily = false, $skinName = false) {
	return zenarioTemplatePath(ifNull($templateFamily, cms_core::$templateFamily)). 'skins/'. rawurlencode(ifNull($skinName, cms_core::$skinName)). '/';
}

function zenarioTemplatePath($templateFamily = false, $fileBaseName = false, $css = false) {
	return 'zenario_custom/templates/'. ($templateFamily? $templateFamily. '/'. ($fileBaseName? $fileBaseName. ($css? '.css' : '.tpl.php') : '') : '');
}



function stripBadCharsForXMLParser ($input) {
	return str_replace(chr(12),"",$input);
}




//Open a file and attempt to turn it into a SimpleXMLElement
function SimpleXML($file) {
	
	if (!(is_file($file)) || (!$string = file_get_contents($file))) {
		return false;
	}
	
	$xml = new SimpleXMLElement($string);
	
	return $xml;
}
	


function assocArrayToXML ($data) {
	$xml = new XmlWriter();
	$xml->openMemory();
	$xml->startDocument('1.0', 'UTF-8');
	$xml->startElement('root');
	
	function write(XMLWriter $xml, $data, $recurseCounter){
		$recurseCounter++;
		
		foreach($data as $key => $value){
			if ($recurseCounter==1) {
				$key = "row";
			}
		
			if (!is_numeric($key)) {
				if(is_array($value)){
					$xml->startElement($key);
					write($xml, $value, $recurseCounter);
					$xml->endElement();
					continue;
				}
				
				$xml->writeElement($key, $value);
			}
		}
	}
	
	write($xml, $data,$recurseCounter);
	$xml->endElement();
	return $xml->outputMemory(true);	
}





//
// Translation functionality
//


function equivId($cID, $cType) {
	return getRow('content_items', 'equiv_id', array('id' => $cID, 'type' => $cType));
}

function langEquivalentItem(&$cID, &$cType, $langId = false, $checkVisible = false) {
	
	//Catch the case where a tag id is entered, not a cID and cType
	if (!is_numeric($cID)) {
		$tagId = $cID;
		getCIDAndCTypeFromTagId($cID, $cType, $tagId);
	}
	
	if (!$cID) {
		return false;
	
	} elseif (!$cType) {
		if (!getCIDAndCTypeFromTagId($cID, $cType, $cID)) {
			return false;
		}
	}
	
	if ($langId === false) {
		$langId = visitorLangId();
	
	} elseif ($langId === true) {
		$langId = cms_core::$defaultLang;
	}
	
	$sql = "
		SELECT id, equiv_id, language_id
		FROM ". DB_NAME_PREFIX. "content_items
		WHERE id = ". (int) $cID. "
		  AND type = '". sqlEscape($cType). "'";
	$result = sqlQuery($sql);
	
	if ($row = sqlFetchAssoc($result)) {
		if ($langId != $row['language_id']) {
			$sql = "
				SELECT id
				FROM ". DB_NAME_PREFIX. "content_items
				WHERE equiv_id = ". (int) $row['equiv_id']. "
				  AND type = '". sqlEscape($cType). "'
				  AND language_id = '". sqlEscape($langId). "'";
			
			if ($checkVisible) {
				$adminMode = !empty($_SESSION['admin_logged_into_site']) && checkPriv();
				
				//If an admin is logged in, any drafts/hidden content items should effect which language they get directed to
				//If not, only published pages should effect the logic.
				if ($adminMode) {
					$sql .= "
					  AND status NOT IN ('trashed', 'deleted')";
				} else {
					$sql .= "
					  AND status IN ('published_with_draft', 'published')";
				}
			}
			
			$result = sqlQuery($sql);
			
			if ($row = sqlFetchAssoc($result)) {
				$cID = $row['id'];
				return true;
			}
		}
		
		if (!$checkVisible) {
			return true;
		}
	}
	
	return false;
}

function equivalences($cID, $cType, $includeCurrent = true, $equivId = false) {
	if ($equivId === false) {
		$equivId = equivId($cID, $cType);
	}
	
	$result = getRows(
		'content_items',
		array('id', 'type', 'language_id', 'equiv_id', 'status'),
		array('equiv_id' => $equivId, 'type' => $cType),
		'language_id');
	
	$equivs = array();
	while($equiv = sqlFetchAssoc($result)) {
		if ($includeCurrent || $equiv['id'] != $cID) {
			$equivs[$equiv['language_id']] = $equiv;
		}
	}
	
	return $equivs;
}

function getCentralisedListValues($valuesSource, $filter = false) {
	if ($valuesSource
		&& ($source = explode('::', $valuesSource, 3))
		&& (!empty($source[0]))
		&& (!empty($source[1]))
		&& (!isset($source[2]))
		&& (inc($source[0]))
	) {
		$listMode = ZENARIO_CENTRALISED_LIST_MODE_LIST;
		if ($filter !== false && $filter !== '') {
			$listMode = ZENARIO_CENTRALISED_LIST_MODE_FILTERED_LIST;
		}
		return call_user_func($source, $listMode, $filter);
	}
	return array();
}


function getCentralisedListValue($valuesSource, $id) {
	if ($valuesSource
		&& ($source = explode('::', $valuesSource, 3))
		&& (!empty($source[0]))
		&& (!empty($source[1]))
		&& (!isset($source[2]))
		&& (inc($source[0]))
	) {
		return call_user_func($source, ZENARIO_CENTRALISED_LIST_MODE_VALUE, $id);
	}
	return false;
}
