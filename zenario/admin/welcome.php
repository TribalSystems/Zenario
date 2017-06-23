<?php
/*
 * Copyright (c) 2017, Tribal Limited
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
 *  Standard welcome page header.
 *  It will include the full library of functions in Zenario, including admin functions.
 *  It will only connect to the database if Zenario has been installed.
 */

require '../basicheader.inc.php';
require CMS_ROOT. 'zenario/includes/cms.inc.php';
require CMS_ROOT. 'zenario/includes/admin.inc.php';
require CMS_ROOT. 'zenario/includes/welcome.inc.php';
header('Content-Type: text/html; charset=UTF-8');

//Check for a few files/directories using the old names from version 6.
//If we see any, show a message explain what needs to be done to upgrade to version 7.
if (file_exists('tribiq_siteconfig.php')
 || is_dir('my_tribiq_frameworks')
 || is_dir('my_tribiq_modules')
 || is_dir('my_tribiq_templates')) {
	echo '
		<p>
			Thank you for updating to version 7!
		</p><p>
			Tribiq CMS is now called &ldquo;Zenario&rdquo;. To complete your update, you will need to:
		</p>
		
		<ul>
			<li>
				Create a directory called <code>', htmlspecialchars(CMS_ROOT), 'zenario_custom/</code>
			</li>
			<li>
				Move the <code>', htmlspecialchars(CMS_ROOT), 'my_tribiq_frameworks/</code> directory
				to <code>', htmlspecialchars(CMS_ROOT), 'zenario_custom/frameworks/</code>
			</li>
			<li>
				Move the <code>', htmlspecialchars(CMS_ROOT), 'my_tribiq_modules/</code> directory
				to <code>', htmlspecialchars(CMS_ROOT), 'zenario_custom/modules/</code>
			</li>
			<li>
				Move the <code>', htmlspecialchars(CMS_ROOT), 'my_tribiq_templates/</code> directory
				to <code>', htmlspecialchars(CMS_ROOT), 'zenario_custom/templates/</code>
			</li>
			<li>
				Rename the <code>', htmlspecialchars(CMS_ROOT), 'tribiq_siteconfig.php</code> file
				to <code>', htmlspecialchars(CMS_ROOT), 'zenario_siteconfig.php</code>
			</li>
			<li>
				Replace the word <code>tribiq</code> with
				the word <code>zenario</code>
				in any custom modules or skins you have created
			</li>
		</ul>';
	exit;
}

//Check to see if Zenario is installed, and connect to the database if so
$freshInstall = false;
$installStatus = 0;
$installed =
	checkConfigFileExists()
 && ($installStatus = 1)
 && (defined('DBHOST'))
 && (defined('DBNAME'))
 && (defined('DBUSER'))
 && (defined('DBPASS'))
 && (cms_core::$lastDB = cms_core::$localDB = @connectToDatabase(DBHOST, DBNAME, DBUSER, DBPASS, DBPORT, false))
 && ($result = @cms_core::$localDB->query("SHOW TABLES LIKE '". DB_NAME_PREFIX. "site_settings'"))
 && ($installStatus = 2)
 && ($result->num_rows)
 && ($installStatus = 3);

if (!$installed) {
	cms_core::$lastDB = cms_core::$localDB = '';
}


//If it is defined, check that the SUBDIRECTORY is correct and warn the admin if not
//(Note this is the same logic from the top of cms.inc.php)
if (defined('SUBDIRECTORY')) {
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
	
	if (SUBDIRECTORY != $subdir) {
		echo htmlspecialchars(
			'The SUBDIRECTORY constant is not correctly defined in the zenario_siteconfig.php file. It is set to "'. SUBDIRECTORY. '"; it should be set to "'. $subdir. '".');
		exit;
	}
}

//Check to see that the Admin has not copied Zenario on-top of an older version
if (is_dir('zenario/admin/db_updates/copy_over_top_check/')) {
	foreach (scandir('zenario/admin/db_updates/copy_over_top_check/') as $file) {
		if (substr($file, 0, 1) != '.' && $file != ZENARIO_MAJOR_VERSION. '.'. ZENARIO_MINOR_VERSION. '.txt') {
			echo '
				<p>
					You are seeing this message because you have attempted to update Zenario
					by copying the new version over the top of your existing version.
				</p><p>
					This will not work, as there are some files in the older version that need to be removed.
					You should replace your <code>'. CMS_ROOT. 'zenario/</code> directory with the
					<code>zenario/</code> directory from the new copy of Zenario.
				</p><p>
					Please see the <a href="http://zenar.io/quick-upgrade.html">zenar.io/quick-upgrade.html</a> guide
					or the <a href="http://zenar.io/cautious-upgrade.html">zenar.io/cautious-upgrade.html</a> guide
					on <a href="http://zenar.io">zenar.io</a> for more information.
				</p>';
			exit;
		}
	}
}









if ($installed) {
	//If Zenario is installed, move on to the login check and then database updates
	if (!defined('SHOW_SQL_ERRORS_TO_VISITORS')) {
		define('SHOW_SQL_ERRORS_TO_VISITORS', true);
	}
	connectLocalDB();
	
	
	//From version 7.0.4 of Zenario, we'll only support updating from version 7.0.2 onwards.
	//Check for versions of Tribiq CMS/Zenario before 7.0.2
	$sql = "
		SELECT 1
		FROM ". DB_NAME_PREFIX. "local_revision_numbers
		WHERE path IN ('admin/db_updates/step_1_update_the_updater_itself', 'admin/db_updates/step_2_update_the_database_schema', 'admin/db_updates/step_4_migrate_the_data')
		  AND patchfile IN ('admin.inc.php', 'local.inc.php', 'user.inc.php')
		  AND revision_no < ". (26960). "
		LIMIT 1";

	if (sqlFetchRow(sqlQuery($sql))) {
		//If this looks like a very old version of Zenario, direct people to update to 7.0.2 first
		echo '
			<p>
				You are seeing this message because your database contains an installation
				of Tribiq CMS (either version 5 or version 6).
			</p><p>
				To update a Tribiq CMS site you must first download
				Zenario version 7.0.2 from our website at <a href="http://zenar.io">http://zenar.io</a>.
			</p><p>
				As soon as you have updated your site to version 7.0.2 you can then update to the
				latest version of Zenario.
			</p>';
		exit;
	}
}


echo
'<!DOCTYPE HTML>
<html>
<head>
	<title>', adminPhrase('Welcome to Zenario'), '</title>
	<meta name="viewport" content="initial-scale=0.5">';

$v = zenarioCodeVersion();
CMSWritePageHead('../', 'welcome');

echo '
	<link rel="stylesheet" type="text/css" href="../styles/admin_welcome.min.css?v=', $v, '" media="screen" />
	<style type="text/css">
		
		#welcome,
		#no_something,
		#no_cookies,
		#no_script {
			display: none;
		}
		
		body.no_js #no_something {
			display: block;
		}
		
		body.no_js #no_script {
			display: inline;
		}
	</style>
</head>';


CMSWritePageBody();
CMSWritePageFoot('../', 'welcome', false, false);

$logoURL = $logoWidth = $logoHeight = false;
if (cms_core::$lastDB
 && setting('brand_logo') == 'custom'
 && ($result = sqlSelect("SHOW COLUMNS IN ". DB_NAME_PREFIX. "files WHERE Field = 'thumbnail_64x64_width'"))
 && ($dbAtRecentRevision = sqlFetchRow($result))
 && (imageLink($logoWidth, $logoHeight, $logoURL, setting('custom_logo'), 500, 250, $mode = 'resize', $offset = 0, $retina = true))) {
	
	if (strpos($logoURL, '://') === false) {
		$logoURL = absCMSDirURL(). $logoURL;
	}
} else {
	$logoURL = 'images/zenario_logo.png';
	$logoWidth = 142;
	$logoHeight = 57;
}


$allowedTasks = array(
	'change_password' => 'change_password',
	'diagnostics' => 'diagnostics',
	'reload_sk' => 'reload_sk',
	'end' => 'logout',
	'logout' => 'logout',
	'restore' => 'restore',
	'site_reset' => 'site_reset');



//T9732, Admin login panel, show warning when a redirect from other URL has occurred
$refererHostWarning = false;
if (!empty($_SERVER['HTTP_REFERER'])
 && ($currentHost = httpHostWithoutPort())
 && ($refererHost = httpHostWithoutPort(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST)))
 && ($refererHost != $currentHost)) {
	$refererHostWarning =
		adminPhrase('Your URL has changed. This is the admin login page at "[[currentHost]]", you were previously at "[[refererHost]]".',
			array('refererHost' => $refererHost, 'currentHost' => $currentHost));
}


echo '
<script type="text/javascript" src="../js/admin_welcome.min.js?v=', $v, '"></script>
<script type="text/javascript">
	zenarioAW.task = "', arrayKey($allowedTasks, request('task')), '";
	zenarioAW.getRequest = ', json_encode($_GET), ';
	
	$(document).ready(function () {
		var msg = "', jsEscape('<!--Logged_Out-->'. adminPhrase('You have been logged out.')), '";
		
		if (!zenarioA.loggedOutIframeCheck(msg)) {
			try {
				zenarioA.checkCookiesEnabled().after(function(cookiesEnabled) {
					if (cookiesEnabled) {
						zenarioAW.start();
						zenarioAW.refererHostWarning(', json_encode($refererHostWarning), ');
					} else {
						get("no_something").style.display = "block";
						get("no_cookies").style.display = "inline";
					}
				});
			} catch (e) {
				get("no_something").style.display = "block";
				get("no_cookies").style.display = "inline";
			}
		}
	});
</script>';


if (strpos(httpUserAgent(), 'MSIE 6') !== false) {
	echo '
		<style type="text/css">
			html {
				overflow: hidden;
			}
		</style>';
}

$revision = false;
if (is_numeric(ZENARIO_REVISION)) {
	$revision = ZENARIO_REVISION;
} elseif ($svninfo = getSVNInfo()) {
	$revision = $svninfo['Revision'];
}


echo '
<div id="zenario_now_installing" class="zenario_now" style="display: none;">
	<h1 style="text-align: center;">', adminPhrase('Now Installing'), '
		<div class="bounce1"></div>
  		<div class="bounce2"></div>
  		<div class="bounce3"></div>
  	</h1>
</div>
<div id="welcome_outer">
	<div id="welcome" class="welcome">
		<div class="zenario_version"><p class="version">
			', adminPhrase('Zenario [[version]]', array('version' => getCMSVersionNumber($revision))), '
		</p></div>
		<div class="welcome_wrap">
			<div class="welcome_inner">
		
				<div class="welcome_header">
					<div class="welcome_header_logo">
						<img src="', htmlspecialchars($logoURL), '" width="', (int) $logoWidth, '" height="', (int) $logoHeight, '"/>
					</div>
				</div>
	
				<div>
					<div id="zenario_abtab"></div>
				</div>
			</div>
		</div>
	</div>
	<div id="no_something" class="welcome">
		<div class="zenario_version"><p class="version">
			', adminPhrase('Zenario [[version]]', array('version' => getCMSVersionNumber($revision))), '
		</p></div>
		<div class="welcome_wrap">
			<div class="welcome_inner">
		
				<div class="welcome_header">
					<div class="welcome_header_logo">
						<img src="', htmlspecialchars($logoURL), '" width="', (int) $logoWidth, '" height="', (int) $logoHeight, '"/>
					</div>
				</div>
	
				<div>
					<div class="problem">
						<h1>', adminPhrase('Welcome to Zenario'), '</h1>
						<p id="no_cookies">',
							adminPhrase("Unable to start a session! We cannot log you in at the moment.<br/><br/>Please check that cookies are enabled in your browser.<br/><br/>If you've enabled cookies and this message persists, please advise your system administrator to: <ul><li>Check the <code>COOKIE_DOMAIN</code> setting in the <code>zenario_siteconfig.php</code> file to ensure it is not referencing a different domain.</li><li>Check for any problems with caching or session storage on the server.</li></ul>"),
						'</p>
						<p id="no_script">',
							adminPhrase('Please enable JavaScript in your browser to continue.'),
						'</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>';


?>
</body>
</html>