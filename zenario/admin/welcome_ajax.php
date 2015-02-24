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


/*  
 *  Standard welcome page header.
 *  It will include the full library of functions in the CMS, including admin functions.
 *  It will only connect to the database if the CMS has been installed.
 */

header('Content-Type: text/javascript; charset=UTF-8');

session_start();
require '../cacheheader.inc.php';
require CMS_ROOT. 'zenario/includes/welcome.inc.php';
require CMS_ROOT. 'zenario/api/database_functions.inc.php';

//Check to see if the CMS is installed
$freshInstall = $adminId = false;
$installStatus = 0;
$installed =
	checkConfigFileExists()
 && (@include_once CMS_ROOT. 'zenario_siteconfig.php')
 && ($installStatus = 1)
 && (cms_core::$lastDB = cms_core::$localDB = @connectToDatabase(DBHOST, DBNAME, DBUSER, DBPASS, false))
 && ($result = @cms_core::$localDB->query("SHOW TABLES LIKE '". DB_NAME_PREFIX. "site_settings'"))
 && ($installStatus = 2)
 && ($result->num_rows)
 && ($installStatus = 3);

if (!$installed) {
	cms_core::$lastDB = cms_core::$localDB = '';
}

if (!defined('ERROR_REPORTING_LEVEL')) {
	define('ERROR_REPORTING_LEVEL', E_ALL & ~E_NOTICE & ~E_STRICT);
}
error_reporting(ERROR_REPORTING_LEVEL);

require_once CMS_ROOT. 'zenario/includes/cms.inc.php';
require_once CMS_ROOT. 'zenario/includes/admin.inc.php';


if (request('quickValidate')) {
	
	if ($installStatus > 2 && empty($_SESSION['admin_logged_into_site'])) {
		exit;
	}
	
	$values = json_decode($_POST['values'], true);
	$rowClasses = json_decode($_POST['row_classes'], true);
	$snippets = array();
	
	quickValidateWelcomePage($values, $rowClasses, $snippets, post('tab'));
	
	echo json_encode(array('row_classes' => $rowClasses, 'snippets' => $snippets));
	exit;
}









//Include any xml files in the install directory
$box = array();
$tags = array();
$dummy = array();
loadTUIX($dummy, $tags, 'welcome');
$removedColumns = array();
zenarioParseTUIX2($tags, $removedColumns, 'welcome');


if (post('_format') || post('_validate')) {
	$box = json_decode($_POST['_box'], true);
}
$getRequest = json_decode($_GET['get'], true);
$task = get('task');


//Run the installer if the CMS is not installed
if (!$installed) {
	if (arrayKey($box, 'path') != 'install') {
		$box = array('path' => 'install');
	}
	checkBoxDefinition($box, $tags['install']);
	
	$installed = installerAJAX($tags, $box, $task, $installStatus, $freshInstall, $adminId);
	
	if ($installed) {
		@include_once CMS_ROOT. 'zenario_siteconfig.php';
	}
}


if ($installed) {
	//If the CMS is installed, move on to the login check and then database updates
	if (!defined('SHOW_SQL_ERRORS_TO_VISITORS')) {
		define('SHOW_SQL_ERRORS_TO_VISITORS', true);
	}
	connectLocalDB();
	
	
	//Log the Admin in automatically if they've just done a fresh install
	if ($freshInstall) {
		//setAdminSession($adminId);
		$loggedIn = true;
	
	//Check that the local and global databases are not set to the same database and table prefix
	} else
	if (defined('DBHOST_GLOBAL') && DBHOST_GLOBAL == DBHOST
	 && defined('DBNAME_GLOBAL') && DBNAME_GLOBAL == DBNAME
	 && defined('DB_NAME_PREFIX_GLOBAL') && DB_NAME_PREFIX_GLOBAL == DB_NAME_PREFIX) {
		
		echo
			'<!--Message_Type:Error-->',
			'<!--Modal-->',
			'<!--Reload_Button:', adminPhrase('Retry and Resume'), '-->',
			adminPhrase('Your local and global databases are set to the same database and table prefix. Please edit your zenario_siteconfig.php and correct this!');
		exit;
	
	//Otherwise, check that they are trying to log in correctly using ssl if it is requested
	} elseif (httpOrhttps() != 'https://' && setting('admin_use_ssl')) {
		//Direct them to the admin domain under ssl if not
		$box['_location'] = getGlobalURL(true). SUBDIRECTORY. 'zenario/admin/welcome.php?'. http_build_query($getRequest);
		$loggedIn = false;
	
	//Check that they are logging into the admin domain
	} elseif (httpHost() != primaryDomain() && httpHostWithoutPort() != primaryDomain()) {
		//Direct them to the correct domain if not
		$box['_location'] = getGlobalURL(true). SUBDIRECTORY. 'zenario/admin/welcome.php?'. http_build_query($getRequest);
		$loggedIn = false;
	
	//Otherwise, check if the Admin has been logged in, and show the log in section if not
	} elseif (!$loggedIn = checkPriv(false, false, false, false, $welcomePage = true)) {
		if (arrayKey($box, 'path') != 'login') {
			$box = array('path' => 'login');
		}
		checkBoxDefinition($box, $tags['login']);
		
		//If the Admin is asking to log out, but has already logged out, just redirect them
		if ($task == 'logout') {
			$box['_location'] = redirectAdmin($getRequest);
		
		//Otherwise show the login screen
		} else {
			$loggedIn = loginAJAX($tags, $box, $getRequest);
		}
	}
	
	if ($loggedIn) {
		if ($task == 'logout') {
			//Log the current admin out
			unsetAdminSession();
			$box['_clear_local_storage'] = true;
			$box['_location'] = redirectAdmin($getRequest);
		
		} else {
			//Check if database updates are needed
			$dbUpToDate = !checkIfDBUpdatesAreNeeded($andDoUpdates = false);
			
			if (!$dbUpToDate) {
				if (arrayKey($box, 'path') != 'update') {
					$box = array('path' => 'update');
				}
				checkBoxDefinition($box, $tags['update']);
				
				$dbUpToDate = updateAJAX($tags, $box, $task);
			}
			
			if ($dbUpToDate) {
				//Load site settings if they've not already been loaded
				loadSiteConfig();
				
				//Check if a password change is needed/requested
				$needToChangePassword = ($task == 'change_password' || getRow('admins', 'password_needs_changing', adminId()));
				
				if ($needToChangePassword) {
					if (arrayKey($box, 'path') != 'change_password') {
						$box = array('path' => 'change_password');
					}
					checkBoxDefinition($box, $tags['change_password']);
					
					$needToChangePassword = !changePasswordAJAX($tags, $box, $task);
				}
				
				
				if (!$needToChangePassword) {
					//Allow the Admin to pass the welcome page at this point
					$_SESSION['admin_logged_in'] = true;
					unset($_SESSION['last_item']);
					
					if ($task == 'reload_sk' || $task == 'password_changed' || $task == 'site_reset') {
						//Don't show the diagnostics page if someone is performing the site reset, reload_sk or change password tasks
						$doneWithDiagnostics = true;
					} else {
						//Otherwise show the diagnostics page if there are errors to display
						if (arrayKey($box, 'path') != 'diagnostics') {
							$box = array('path' => 'diagnostics');
						}
						checkBoxDefinition($box, $tags['diagnostics']);
						
						$doneWithDiagnostics = diagnosticsAJAX($tags, $box, $freshInstall);
					}
					
					if ($doneWithDiagnostics) {
						if (arrayKey($box, 'path') != 'congratulations') {
							$box = array('path' => 'congratulations');
						}
						
						checkBoxDefinition($box, $tags['congratulations']);
						
						if ($task == 'install') {
							//If the CMS was just installed, show the congrats screen
							congratulationsAJAX($tags, $box);
						
						} else {
							//Otherwise redirect the Admin away from this page
							$box['_location'] = redirectAdmin($getRequest);
						}
					}
				}
			}
		}
	}
}


$box['_task'] = $task;
echo json_encode($box);