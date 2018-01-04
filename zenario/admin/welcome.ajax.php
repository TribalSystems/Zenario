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
 *  Standard welcome page header.
 *  It will include the full library of functions in the CMS, including admin functions.
 *  It will only connect to the database if the CMS has been installed.
 */

header('Content-Type: text/javascript; charset=UTF-8');

require '../basicheader.inc.php';
require CMS_ROOT. 'zenario/includes/cms.inc.php';
startSession();
require CMS_ROOT. 'zenario/includes/admin.inc.php';
require CMS_ROOT. 'zenario/includes/tuix.inc.php';
require CMS_ROOT. 'zenario/includes/welcome.inc.php';

//Check to see if the CMS is installed
$freshInstall = $adminId = false;
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

if (!defined('ERROR_REPORTING_LEVEL')) {
	define('ERROR_REPORTING_LEVEL', E_ALL & ~E_NOTICE & ~E_STRICT);
}
error_reporting(ERROR_REPORTING_LEVEL);



if ($_REQUEST['quickValidate'] ?? false) {
	
	if ($installStatus > 2 && empty($_SESSION['admin_logged_into_site'])) {
		exit;
	}
	
	$values = json_decode($_POST['values'], true);
	$rowClasses = json_decode($_POST['row_classes'], true);
	$snippets = array();
	
	quickValidateWelcomePage($values, $rowClasses, $snippets, ($_POST['tab'] ?? false));
	
	echo json_encode(array('row_classes' => $rowClasses, 'snippets' => $snippets));
	exit;
}









//Include all of the yaml files in the install directory
$clientTags = array();
$tags = array();
$source = array();
$dummy = array();
$fields = array();
$values = array();
$changes = array();
loadTUIX($dummy, $source, 'welcome');

$removedColumns = array();
zenarioParseTUIX2($source, $removedColumns, 'welcome');


if (($_POST['_format'] ?? false) || ($_POST['_validate'] ?? false)) {
	$clientTags = $tags = json_decode($_POST['_box'], true);
}
$getRequest = json_decode($_GET['get'], true);
$task = $_GET['task'] ?? false;




//Check system requirements
if (!$systemRequirementsMet = !empty($_SESSION['zenario_system_requirements_met'])) {
	prepareAdminWelcomeScreen('system_requirements', $source, $tags, $fields, $values, $changes);
	$_SESSION['zenario_system_requirements_met'] = $systemRequirementsMet =
		systemRequirementsAJAX($source, $tags, $fields, $values, $changes);
}

//Run the installer if the CMS is not installed
if ($systemRequirementsMet && !$installed) {
	prepareAdminWelcomeScreen('install', $source, $tags, $fields, $values, $changes);
	$installed = installerAJAX($source, $tags, $fields, $values, $changes, $task, $installStatus, $freshInstall, $adminId);
	
	if ($installed) {
		@include_once CMS_ROOT. 'zenario_siteconfig.php';
	}
}


if ($systemRequirementsMet && $installed) {
	//If the CMS is installed, move on to the login check and then database updates
	
	if ($freshInstall || $task == 'site_reset') {
		if (!defined('SHOW_SQL_ERRORS_TO_VISITORS')) {
			define('SHOW_SQL_ERRORS_TO_VISITORS', true);
		}
		connectLocalDB();
	} else {
		loadSiteConfig();
	}
	
	
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
	
	//Log the current admin out if they've clicked the logout button
	} else
	if ($task == 'logout') {
		logoutAdminAJAX($tags, $getRequest);
		$loggedIn = false;
	
	//If a specific admin domain is set, check that they are logging into the admin domain
	//Also, if the admin_use_ssl option is set, check that they are trying to log in correctly using ssl if it is requested.
	} else
	if ((setting('admin_domain') && setting('admin_domain') != $_SERVER['HTTP_HOST'])
	 || (setting('admin_use_ssl') && httpOrhttps() != 'https://')) {
		
		//Deny access and don't show the admin domain to people not on the admin domain,
		//if the admin_domain_is_public setting is set
		if (adminDomainIsPrivate()) {
			$tags['go_to_url'] = redirectAdmin($getRequest, true);
		
		} else {
			//Direct them to the correct domain if not
			$tags['go_to_url'] =
				(setting('admin_use_ssl')? 'https://' : httpOrhttps()).
				adminDomain(). SUBDIRECTORY.
				'zenario/admin/welcome.php?'. http_build_query($getRequest);
		}
		$loggedIn = false;
	
	//Otherwise, check if the Admin has been logged in, and show the log in section if not
	} elseif (!$loggedIn = checkPriv(false, false, false, false, $welcomePage = true)) {
		prepareAdminWelcomeScreen('login', $source, $tags, $fields, $values, $changes);	
		//Show the login screen
		$loggedIn = loginAJAX($source, $tags, $fields, $values, $changes, $getRequest);
	}
	
	if ($loggedIn) {
		//Check security tokens unless one of three things are true:
			//This is a new install
			//This is a migration from an old site, and the admin_settings table hasn't been created yet
			//Security tokens are not enabled in the site_description.yaml file
		if ($freshInstall
		 || !checkTableDefinition(DB_NAME_PREFIX. 'admin_settings', true)
		 || !siteDescription('enable_two_factor_security_for_admin_logins')) {
			$securityCodeChecked = true;
		
		//Try to get an existing admin cookie, and the corresponding admin setting.
		//If we find one, check that it's not too old.
		} else {
			//Load site settings if they've not already been loaded
			loadSiteConfig();
			
			//If there's a cookie with the right name, a corresponding admin setting
			//with the right name, and the time stored in the admin setting is in date,
			//allow the admin through without running the security code check.
			zenarioTidySecurityCodes();
			if (($scsn = zenarioSecurityCodeSettingName())
			 && ($time = adminSetting($scsn))
			 && ($time > zenarioSecurityCodeTime(siteDescription('two_factor_security_timeout')))) {
				$securityCodeChecked = true;
			
			//Otherwise we need to send the email with the security code and show the admin the form
			//to enter it
			} else {
				prepareAdminWelcomeScreen('security_code', $source, $tags, $fields, $values, $changes);		
				$securityCodeChecked = securityCodeAJAX($source, $tags, $fields, $values, $changes, $task, $getRequest);
			}
		}
	
		if ($securityCodeChecked) {
			//Check if database updates are needed
			$moduleErrors = '';
			$dbUpToDate = !checkIfDBUpdatesAreNeeded($moduleErrors, $andDoUpdates = false);
		
			if (!$dbUpToDate) {
				
				//Update the admin's permissions just before we do the priv check below
				//(Normally this happens automatically, but it doesn't on the login screen.)
				refreshAdminSession();
				
				//Check if this admin has the permissions to do a database update
				if (checkPriv('_PRIV_APPLY_DATABASE_UPDATES', false, false, false, $welcomePage = true)
					//Also let them through if there are no admins on this site that can do a database update
					//to avoid you getting stuck out of your site!
				 || !checkRowExists('action_admin_link', array('action_name' => array('_ALL', '_PRIV_APPLY_DATABASE_UPDATES')))
					//Also allow the admin to do the update if revision #33720 hasn't been applied yet, as
					//before then _PRIV_APPLY_DATABASE_UPDATES didn't exist.
				 || !($revision_no = getRow('local_revision_numbers', 'revision_no', array('path' => 'admin/db_updates/step_2_update_the_database_schema', 'patchfile' => 'admin_tables.inc.php')))
				 || ($revision_no < 33720)) {
					
					//If we can, show the screen to apply database updates
					prepareAdminWelcomeScreen('update', $source, $tags, $fields, $values, $changes);		
					$dbUpToDate = updateAJAX($source, $tags, $fields, $values, $changes, $task);
				
				} else {
					//Otherwise show a message
					prepareAdminWelcomeScreen('update_no_permission', $source, $tags, $fields, $values, $changes);				
					updateNoPermissionsAJAX($source, $tags, $fields, $values, $changes, $task, $getRequest);
				}
			}
		
			if ($dbUpToDate) {
				//Load site settings if they've not already been loaded
				loadSiteConfig();
			
				//Check if a password change is needed/requested
				$needToChangePassword = ($task == 'change_password' || getRow('admins', 'password_needs_changing', adminId()));
			
				if ($needToChangePassword) {
					prepareAdminWelcomeScreen('change_password', $source, $tags, $fields, $values, $changes);			
					$needToChangePassword = !changePasswordAJAX($source, $tags, $fields, $values, $changes, $task);
				}
			
			
				if (!$needToChangePassword) {
					//Allow the Admin to pass the welcome page at this point
					$_SESSION['admin_logged_in'] = true;
					unset($_SESSION['last_item']);
				
					//Don't show the diagnostics page if someone is performing the site reset,
					//reload_sk or change password tasks.
					//Also don't show it to people who don't have the permissions to see it
					if ($task == 'reload_sk'
					 || $task == 'password_changed'
					 || $task == 'site_reset'
					 || !checkPriv('_PRIV_VIEW_DIAGNOSTICS', false, false, false, $welcomePage = true)) {
						
						$doneWithDiagnostics = true;
					
					} else {
						//Otherwise show the diagnostics page if there are errors to display
						prepareAdminWelcomeScreen('diagnostics', $source, $tags, $fields, $values, $changes);				
						$doneWithDiagnostics = diagnosticsAJAX($source, $tags, $fields, $values, $changes, $task, $freshInstall);
					}
				
					if ($doneWithDiagnostics) {
						protectBackupAndDocstoreDirsIfPossible();
						
						prepareAdminWelcomeScreen('congratulations', $source, $tags, $fields, $values, $changes);				
						if ($task == 'install') {
							//If the CMS was just installed, show the congrats screen
							congratulationsAJAX($source, $tags, $fields, $values, $changes);
					
						} else {
							//Otherwise redirect the Admin away from this page
							$tags['go_to_url'] = redirectAdmin($getRequest);
						}
					}
				}
			}
		}
	}
}


$tags['_task'] = $task;

if (empty($clientTags)) {
	echo json_encode($tags);
} else {
	$output = array();
	syncAdminBoxFromServerToClient($tags, $clientTags, $output);
	echo json_encode($output);
}