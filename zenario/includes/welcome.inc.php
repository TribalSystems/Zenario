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


function directoryIsWritable($dir) {
	
	//Check to see if the directory is flagged as writable
	if (!@is_writable($dir)) {
		return false;
	}
	
	//Check to see if the directory is flagged as listable/executable
	if (substr($dir, -1) == '/') {
		$dir .= '.';
	} else {
		$dir .= '/.';
	}
	
	return @file_exists($dir);
}



function quickValidateWelcomePage(&$values, &$rowClasses, &$snippets, $tab) {
	if ($tab == 5 || $tab == 'change_password') {
		$strength = passwordValuesToStrengths(checkPasswordStrength($values['password'], true));
		$snippets['password_strength'] = '<div class="password_'. $strength. '"><span>'. adminPhrase($strength). '</span></div>';
	
	} elseif ($tab == 45) {
		if (is_file($values['path'])) {
			if (substr($values['path'], -7) != '.sql.gz' && substr($values['path'], -4) != '.sql') {
				$snippets['path_status'] = adminPhrase('Please select a backup file.');
			} elseif (is_readable($values['path'])) {
				$snippets['path_status'] = adminPhrase('File exists.');
			} else {
				$snippets['path_status'] = adminPhrase('File exists but cannot be read.');
			}
		} elseif (@is_dir($values['path'])) {
			$snippets['path_status'] = adminPhrase('Directory exists, please enter a filename.');
		} else {
			$snippets['path_status'] = adminPhrase('No file or directory with that name.');
		}
	}

}




//This file includes common functionality for running SQL scripts

function runSQL($prefix = false, $file, &$error, $patterns = false, $replacements = false) {
	$error = false;
	
	//Attempt to work out the location of the installer scripts, if not provided
	if (!$prefix) {
		$prefix = CMS_ROOT. 'zenario/admin/db_install/';
	}
	
	if (!file_exists($prefix. $file)) {
		$error = 'SQL Template File '. $file. ' does not exist.';
		return false;
	}
	
	//Build up a list of pattern replacements
	if (!$patterns) {
		//If no patterns have been set, go in with a few default patterns. Note I am assuming that the CMS
		//is running here...
		$from = array("\r", '[[DB_NAME_PREFIX]]',	'[[LATEST_REVISION_NO]]',	'[[INSTALLER_REVISION_NO]]',	'[[THEME]]');
		$to =	array('',	DB_NAME_PREFIX,			LATEST_REVISION_NO,			INSTALLER_REVISION_NO,			INSTALLER_DEFAULT_THEME);
	} else {
		$from = array("\r");
		$to = array('');
		foreach($patterns as $pattern => $replacement) {
			
			//Accept $patterns and $replacements in two different arrays with numeric keys
			if (is_array($replacements)) {
				$pattern = $replacement;
				$replacement = $replacements[$pattern];
			}
			
			$from[] = '[['. $pattern. ']]';
			if ($pattern == 'DB_NAME_PREFIX') {
				$to[] = $replacement;
			} else {
				$to[] = sqlEscape($replacement);
			}
		}
	}
	
	//Get the contents of the script, do the replacements, and split up into statements
	$sqls = explode(";\n", str_replace($from, $to, file_get_contents($prefix. $file)));


	//Get the number of sql statements, check if the last one is empty and exclude it if so
	$count = count($sqls);
	trim($sqls[$count-1])? null: --$count;
	
	//Loop through and execute each statement
	for ($i = 0; $i < $count; ++$i) {
		$query = $sqls[$i];
		
		if (!$result = sqlSelect($query)) {
			$errno = sqlErrno();
			$error = sqlError();
			$error = '(Error '. $errno. ': '. $error. "). \n\n". $query. "\nFile: ". $file;
			return false;
		}
	}
	
	return true;
}


function readSampleConfigFile($patterns) {
	$searches = $replaces = array();
	foreach ($patterns as $pattern => $value) {
		$searches[] = '[['. $pattern. ']]';
		$replaces[] = $value;
	}
	
	return str_replace($searches, $replaces, file_get_contents(CMS_ROOT. 'zenario/admin/db_install/zenario_siteconfig.sample.php'));
}

//Check whether the config file exists.
//Note: if it doesn't exist, return false
//Note: if exists but is empty, return 0
function checkConfigFileExists() {
	if (!@file_exists(CMS_ROOT. 'zenario_siteconfig.php')) {
		return false;
	}
	$filesize = @filesize(CMS_ROOT. 'zenario_siteconfig.php');
	if ($filesize && $filesize >= 20) {
		return true;
	} else {
		return 0;
	}
}

function compareVersionNumber($actual, $required) {
	return version_compare(preg_replace('@[^\d\.]@', '', $actual), $required, '>=');
}

function getSVNInfo() {
	if (!windowsServer() && execEnabled()) {
		$output = array();
		$svninfo = array();
		$realDir = realpath($logicalDir = CMS_ROOT. 'zenario');
		
		if (is_dir($realDir. '/.svn')) {
			@exec('svn info '. escapeshellcmd($realDir. '/'), $output);
	
		} elseif (is_dir(dirname($realDir). '/.svn')) {
			@exec('svn info '. escapeshellcmd(dirname($realDir). '/'), $output);
	
		} elseif (is_dir('.svn')) {
			@exec('svn info .', $output);
		}
		
		if (!empty($output)) {
			foreach ($output as $line) {
				$line = explode(': ', $line, 2);
		
				if (!empty($line[1])) {
					$svninfo[$line[0]] = $line[1];
				}
			}
			
			if (!empty($svninfo)
			 && !empty($svninfo['Revision'])) {
				return $svninfo;
			}
		}
	}
	
	return false;
}


function installerReportError() {
	return "\n". adminPhrase('(Error [[errno]]: [[error]])', array('errno' => sqlErrno(), 'error' => sqlError()));
}


function getDiamondPath() {
	foreach (array('enterprise', 'probusiness', 'pro') as $edition) {
		if ($path = moduleDir('zenario_'. $edition. '_features', 'tuix/organizer/diamond-'. $edition. '.gif', true)) {
			return $path;
		}
	}
	return moduleDir('zenario_common_features', 'tuix/organizer/diamond.gif');
}

function refreshAdminSession() {
	if (adminId()) {
		setAdminSession(adminId(), session('admin_global_id'));
	}
}

function prepareAdminWelcomeScreen(&$box, &$tags, $path) {
	if (arrayKey($box, 'path') != $path) {
		$box = array('path' => $path);
	}

	checkBoxDefinition($box, $tags[$path]);
}


//Old code for sample sites, commented out as we don't currently use them
//function listSampleSites() {
//	$sampleSites = array();
//	
//	foreach (moduleDirs('sample_installations/') as $mdir) {
//		foreach (scandir($path = CMS_ROOT. $mdir) as $dir) {
//			if (substr($dir, 0, 1) != '.'
//			 && is_file($path. $dir. '/description.txt')
//			 && is_file($path. $dir. '/backup.sql.gz')) {
//				$sampleSites[$dir] = $path. $dir. '/';
//			}
//		}
//	}
//	
//	return $sampleSites;
//}

function listSampleThemes() {
	$themes = array();
	$sDir = zenarioTemplatePath(). 'grid_templates/skins/';
	
	if (is_dir($path = CMS_ROOT. $sDir)) {
		foreach (scandir($path) as $dir) {
			if (substr($dir, 0, 1) != '.'
			 && is_file($path. $dir. '/installer/thumbnail.png')) {
				$themes[$dir] = $sDir. $dir. '/installer/thumbnail.png';
			}
		}
	}
	
	if (empty($themes)) {
		$themes[INSTALLER_DEFAULT_THEME] = $sDir. INSTALLER_DEFAULT_THEME. '/installer/thumbnail.png';
	}
	
	return $themes;
}


function installerAJAX(&$tags, &$box, &$task, $installStatus, &$freshInstall, &$adminId) {
	$merge = array();
	$merge['SUBDIRECTORY'] = SUBDIRECTORY;
	
	//If the database prefix is already set in the config file, look it up and default the field to it.
	if (!isset($box['tabs'][3]['fields']['prefix']['value'])) {
		if (defined('DB_NAME_PREFIX') && DB_NAME_PREFIX && strpos(DB_NAME_PREFIX, '[') === false) {
			$box['tabs'][3]['fields']['prefix']['value'] = DB_NAME_PREFIX;
		} else {
			$box['tabs'][3]['fields']['prefix']['value'] = 'zenario_';
		}
	}
	
	//Validation for Step 1: Check if the Admin has accepted the license
	$licenseAccepted = !empty($box['tabs'][1]['fields']['i_agree']['current_value']);
	
	if (!empty($box['tabs'][1]['fields']['fresh_install']['pressed'])) {
		$task = 'install';
	} elseif (!empty($box['tabs'][1]['fields']['restore']['pressed'])) {
		$task = 'restore';
	}
	
	//Validation for Step 2: Check if the server meets the requirements
	if ($box['tab'] > 1 || !empty($box['tabs'][1]['fields']['restore']['pressed']) || !empty($box['tabs'][1]['fields']['fresh_install']['pressed'])) {
		//Get the server phpinfo
		//(Note this section of code may not work properly if compression has been enabled)
		$search = array();
		$replace = array();
		
		$search[] = '~';
		$replace[] = '--';
		
		$search[] = '`';
		$replace[] = '---';
		
		$search[] = '<h2';
		$replace[] = '~<h2';
		
		$search[] = '</h2>';
		$replace[] = '</h2>~';
		
		$search[] = '<td';
		$replace[] = '`<td';
		
		ob_start();
		phpinfo();
		$info = strip_tags(str_replace($search, $replace, ob_get_contents()));
		ob_end_clean();
		
		$phpinfo = array();
		$sectionName = 'PHP';
		foreach (explode('~', $info) as $i => $section) {
			if ($i % 2) {
				$phpinfo[$sectionName = $section] = array();
			} else {
				foreach (explode("\n", $section) as $line) {
					foreach (explode('`', $line) as $j => $cell) {
						if ($j == 1) {
							$setting = trim($cell);
						} else if ($j == 2) {
							$phpinfo[$sectionName][$setting] = trim($cell);
						}
					}
				}
			}
		}
		unset($info);
		unset($search);
		unset($replace);
		
		
		$serverRequirementsMet = false;
		$box['tabs'][2]['fields']['server_1']['post_field_html'] =
			adminPhrase('&nbsp;(<em>you have [[software]]</em>)', array('software' => htmlspecialchars($_SERVER['SERVER_SOFTWARE'])));
		
		if (stripos($_SERVER['SERVER_SOFTWARE'], 'apache') === false) {
			$box['tabs'][2]['fields']['server_1']['row_class'] = 'invalid';
		
		} else {
			$box['tabs'][2]['fields']['server_1']['row_class'] = 'valid';
			$serverRequirementsMet = true;
		}
		
		
		$phpRequirementsMet = false;
		$phpVersion = phpversion();
		$box['tabs'][2]['fields']['php_1']['post_field_html'] =
			adminPhrase('&nbsp;(<em>you have version [[version]]</em>)', array('version' => htmlspecialchars($phpVersion)));
		
		if (!compareVersionNumber($phpVersion, '5.3.0')) {
			$box['tabs'][2]['fields']['php_1']['row_class'] = 'invalid';
		
		} else {
			$box['tabs'][2]['fields']['php_1']['row_class'] = 'valid';
			$phpRequirementsMet = true;
		}
		
		
		$mysqlRequirementsMet = false;
		if (!extension_loaded('mysqli')) {
			$box['tabs'][2]['fields']['mysql_1']['row_class'] = 'invalid';
			$box['tabs'][2]['fields']['mysql_2']['row_class'] = 'invalid';
			$box['tabs'][2]['fields']['mysql_2']['post_field_html'] = '';
		
		} else {
			$box['tabs'][2]['fields']['mysql_1']['row_class'] = 'valid';
			
			$mysqlVersion = ifNull(
				arrayKey($phpinfo, 'mysql', 'Client API version'),
				arrayKey($phpinfo, 'mysqli', 'Client API library version'));
			
			if ($mysqlVersion) {
				$box['tabs'][2]['fields']['mysql_2']['post_field_html'] =
					adminPhrase('&nbsp;(<em>you have version [[version]]</em>)', array('version' => htmlspecialchars($mysqlVersion)));
			}
			
			if (!$mysqlVersion
			 || !compareVersionNumber($mysqlVersion, '5.0.0')) {
				$box['tabs'][2]['fields']['mysql_2']['row_class'] = 'invalid';
			} else {
				$box['tabs'][2]['fields']['mysql_2']['row_class'] = 'valid';
				$mysqlRequirementsMet = true;
			}
		}
		
		
		$mbRequirementsMet = true;
		if (!extension_loaded('ctype')) {
			$mbRequirementsMet = false;
			$box['tabs'][2]['fields']['mb_1']['row_class'] = 'invalid';
		
		} else {
			$box['tabs'][2]['fields']['mb_1']['row_class'] = 'valid';
		}
		if (!extension_loaded('mbstring')) {
			$mbRequirementsMet = false;
			$box['tabs'][2]['fields']['mb_2']['row_class'] = 'invalid';
		
		} else {
			$box['tabs'][2]['fields']['mb_2']['row_class'] = 'valid';
		}
		
		
		$gdRequirementsMet = true;
		if (arrayKey($phpinfo, 'gd', 'GD Support') != 'enabled') {
			$gdRequirementsMet = false;
			$box['tabs'][2]['fields']['gd_1']['row_class'] = 'invalid';
			$box['tabs'][2]['fields']['gd_2']['row_class'] = 'invalid';
			$box['tabs'][2]['fields']['gd_3']['row_class'] = 'invalid';
			$box['tabs'][2]['fields']['gd_4']['row_class'] = 'invalid';
		
		} else {
			$box['tabs'][2]['fields']['gd_1']['row_class'] = 'valid';
			
			if (arrayKey($phpinfo, 'gd', 'GIF Read Support') != 'enabled') {
				$gdRequirementsMet = false;
				$box['tabs'][2]['fields']['gd_2']['row_class'] = 'invalid';
			} else {
				$box['tabs'][2]['fields']['gd_2']['row_class'] = 'valid';
			}
			
			if (arrayKey($phpinfo, 'gd', 'JPG Support') != 'enabled' && arrayKey($phpinfo, 'gd', 'JPEG Support') != 'enabled') {
				$gdRequirementsMet = false;
				$box['tabs'][2]['fields']['gd_3']['row_class'] = 'invalid';
			} else {
				$box['tabs'][2]['fields']['gd_3']['row_class'] = 'valid';
			}
			
			if (arrayKey($phpinfo, 'gd', 'PNG Support') != 'enabled') {
				$gdRequirementsMet = false;
				$box['tabs'][2]['fields']['gd_4']['row_class'] = 'invalid';
			} else {
				$box['tabs'][2]['fields']['gd_4']['row_class'] = 'valid';
			}
		}
	}
	
	
	//Validation for Step 3: Validate the Database Connection
	if ($box['tab'] > 3 || ($box['tab'] == 3 && !empty($box['tabs'][3]['fields']['next']['pressed']))) {
		$box['tabs'][3]['errors'] = array();
		
		$merge['DBHOST'] = $box['tabs'][3]['fields']['host']['current_value'];
		$merge['DBNAME'] = $box['tabs'][3]['fields']['name']['current_value'];
		$merge['DBUSER'] = $box['tabs'][3]['fields']['user']['current_value'];
		$merge['DBPASS'] = $box['tabs'][3]['fields']['password']['current_value'];
		$merge['DBPORT'] = $box['tabs'][3]['fields']['port']['current_value'];
		$merge['DB_NAME_PREFIX'] = $box['tabs'][3]['fields']['prefix']['current_value'];
		
		if (!$merge['DBHOST']) {
			$box['tabs'][3]['errors'][] = adminPhrase('Please enter your hostname.');
		}
		
		if (!$merge['DBNAME']) {
			$box['tabs'][3]['errors'][] = adminPhrase('Please enter your database name.');
		} elseif (preg_match('/[^a-zA-Z0-9_-]/', $merge['DBNAME'])) {
			$box['tabs'][3]['errors'][] = adminPhrase('The Database Name should only contain [a-z, A-Z, 0-9, _ and -].');
		}
		
		if (!$merge['DBUSER']) {
			$box['tabs'][3]['errors'][] = adminPhrase('Please enter the database username.');
		} elseif (preg_match('/[^a-zA-Z0-9_-]/', $merge['DBUSER'])) {
			$box['tabs'][3]['errors'][] = adminPhrase('The database username should only contain [a-z, A-Z, 0-9, _ and -].');
		}
		
		if ($merge['DBPORT'] !== '' && !is_numeric($merge['DBPORT'])) {
			$box['tabs'][3]['errors'][] = adminPhrase('The database port must be a number.');
		}
		
		if (preg_match('/[^a-zA-Z0-9_]/', $merge['DB_NAME_PREFIX'])) {
			$box['tabs'][3]['errors'][] = adminPhrase('The table prefix should only contain the characters [a-z, A-Z, 0-9 and _].');
		}
		
		if (empty($box['tabs'][3]['errors'])) {
			
			if ($merge['DBPORT']) {
				$testConnect = @mysqli_connect($merge['DBHOST'], $merge['DBUSER'], $merge['DBPASS'], $merge['DBNAME'], $merge['DBPORT']);
			} else {
				$testConnect = @mysqli_connect($merge['DBHOST'], $merge['DBUSER'], $merge['DBPASS'], $merge['DBNAME']);
			}
			
			if (!$testConnect) {
				$box['tabs'][3]['errors'][] = 
					adminPhrase('The database name, username and/or password are invalid.');
			
			} else {
				
				cms_core::$localDB =
				cms_core::$lastDB = $testConnect;
				cms_core::$lastDBHost = $merge['DBHOST'];
				cms_core::$lastDBName = $merge['DBNAME'];
				cms_core::$lastDBPrefix = $merge['DB_NAME_PREFIX'];			
				
				if (!($result = @sqlSelect("SELECT VERSION()"))
					   || !($version = @sqlFetchRow($result))
					   || !($result = @sqlSelect("SHOW TABLES"))) {
					$box['tabs'][3]['errors'][] = 
						adminPhrase('You do not have access rights to the database [[DBNAME]].', $merge);
			
				} elseif (!compareVersionNumber($version[0], '5.0.0')) {
					$box['tabs'][3]['errors'][] = 
						adminPhrase('Sorry, your MySQL server is "[[version]]". Version 5.0 or later is required.', array('version' => $version[0]));
			
				} elseif (!(@sqlUpdate("CREATE TABLE IF NOT EXISTS `zenario_priv_test` (`id` TINYINT(1) NOT NULL )", false))
					   || !(@sqlUpdate("DROP TABLE `zenario_priv_test`", false))) {
					$box['tabs'][3]['errors'][] = 
						adminPhrase('Cannot verify database privileges. Please ensure the MySQL user [[DBUSER]] has CREATE TABLE and DROP TABLE privileges. You may need to contact your MySQL administrator to have these privileges enabled.',
							$merge['DBUSER']).
						installerReportError();
			
				} else {
					while ($tables = sqlFetchRow($result)) {
						if ($merge['DB_NAME_PREFIX'] == '' || substr($tables[0], 0, strlen($merge['DB_NAME_PREFIX'])) == $merge['DB_NAME_PREFIX']) {
							$box['tabs'][3]['errors'][] = adminPhrase('There are already tables in this database that match your chosen table prefix. Please choose a different table prefix, or a different database.');
							break;
						}
					}
				}
			}
		}
	}
	
	
	//No validation for Step 4, but remember the theme and language chosen
	if ($box['tab'] > 4 || ($box['tab'] == 4 && !empty($box['tabs'][4]['fields']['next']['pressed']))) {
		$merge['LANGUAGE_ID'] = $box['tabs'][4]['fields']['language_id']['current_value'];
		$merge['VIS_DATE_FORMAT_SHORT'] = $box['tabs'][4]['fields']['vis_date_format_short']['current_value'];
		$merge['VIS_DATE_FORMAT_MED'] = $box['tabs'][4]['fields']['vis_date_format_med']['current_value'];
		$merge['VIS_DATE_FORMAT_LONG'] = $box['tabs'][4]['fields']['vis_date_format_long']['current_value'];
		$merge['THEME'] = preg_replace('/\W/', '', $box['tabs'][4]['fields']['theme']['current_value']);
	}
	
	
	if (empty($box['tabs'][1]['fields']['restore']['pressed'])) {
		//Validation for Step 5: Validate new Admin's details
		if (($box['tab'] > 5 || ($box['tab'] == 5 && !empty($box['tabs'][5]['fields']['next']['pressed'])))) {
			$box['tabs'][5]['errors'] = array();
			
			if (!$merge['admin_first_name'] = $box['tabs'][5]['fields']['first_name']['current_value']) {
				$box['tabs'][5]['errors'][] = adminPhrase('Please enter your first name.');
			}
	
			if (!$merge['admin_last_name'] = $box['tabs'][5]['fields']['last_name']['current_value']) {
				$box['tabs'][5]['errors'][] = adminPhrase('Please enter your last name.');
			}
	
			if (!$merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'] = $box['tabs'][5]['fields']['email']['current_value']) {
				$box['tabs'][5]['errors'][] = adminPhrase('Please enter your email address.');
			
			} elseif (!validateEmailAddress($merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'])) {
				$box['tabs'][5]['errors'][] = adminPhrase('Please enter a valid email address.');
			}
	
			if (!$merge['USERNAME'] = $box['tabs'][5]['fields']['username']['current_value']) {
				$box['tabs'][5]['errors'][] = adminPhrase('Please choose an administrator username.');
			
			} elseif (!validateScreenName($merge['USERNAME'])) {
				$box['tabs'][5]['errors'][] = adminPhrase('Your username may not contain any special characters.');
			
			} elseif (preg_replace('/\S/', '', $merge['USERNAME'])) {
				$box['tabs'][5]['errors'][] = adminPhrase('Your username may not contain any spaces.');
			}
	
			if (!$merge['PASSWORD'] = $box['tabs'][5]['fields']['password']['current_value']) {
				$box['tabs'][5]['errors'][] = adminPhrase('Please choose a password.');
			
			} elseif ($merge['PASSWORD'] != $box['tabs'][5]['fields']['re_password']['current_value']) {
				$box['tabs'][5]['errors'][] = adminPhrase('The password fields do not match.');
			}
		}
	} else {
		//Validation for Step 45: Check a backup file exists
		if ((($box['tab'] != 45 && $box['tab'] > 5)
		  || ($box['tab'] == 45 && !empty($box['tabs'][45]['fields']['next']['pressed'])))) {
			$box['tabs'][45]['errors'] = array();
			
			if (!is_file($box['tabs'][45]['fields']['path']['current_value'])) {
				$box['tabs'][45]['errors'][] = adminPhrase('Please enter a path to a file.');
			
			} elseif (substr($box['tabs'][45]['fields']['path']['current_value'], -7) != '.sql.gz'
				   && substr($box['tabs'][45]['fields']['path']['current_value'], -4) != '.sql') {
				$box['tabs'][45]['errors'][] = adminPhrase('Please select a backup file.');
			
			} elseif (!is_readable($box['tabs'][45]['fields']['path']['current_value'])) {
				$box['tabs'][45]['errors'][] = adminPhrase('Please enter a path to a file with read-access set.');
			}
	
			if (!$merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'] = $box['tabs'][45]['fields']['email']['current_value']) {
				$box['tabs'][45]['errors'][] = adminPhrase('Please enter a support email address.');
			
			} elseif (!validateEmailAddress($merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'])) {
				$box['tabs'][45]['errors'][] = adminPhrase('Please enter a valid email address.');
			}
		}
	}
	
	
	//Validation for Step 6: Attempt to create (if requested) and then validate the siteconfig files
	if ($box['tab'] == 6 && (!empty($box['tabs'][6]['fields']['ive_done_it']['pressed']) || !empty($box['tabs'][6]['fields']['do_it_for_me']['pressed']))) {
		$box['tabs'][6]['errors'] = array();
		
		$checkConfigFileExists = checkConfigFileExists();
		if (!empty($box['tabs'][6]['fields']['do_it_for_me']['pressed'])) {
			$permErrors = false;
			if (!$checkConfigFileExists) {
				if (!file_exists(CMS_ROOT. 'zenario_siteconfig.php')) {
					$box['tabs'][6]['errors'][] =
						adminPhrase('Please create a file called zenario_siteconfig.php. If you want this installer to populate it, it can be empty but writable.');
				
				} elseif (!@file_put_contents(CMS_ROOT. 'zenario_siteconfig.php', readSampleConfigFile($merge))) {
					$box['tabs'][6]['errors'][] =
						adminPhrase('Could not write to file zenario_siteconfig.php');
					$permErrors = true;
				}
			}
			
			if ($permErrors && !stristr(php_uname('s'), 'win')) {
				$box['tabs'][6]['errors'][] = adminPhrase('To correct the file permissions: chmod 666 zenario_siteconfig.php');
			}
		
		} else {
			if ($checkConfigFileExists === false) {
				$box['tabs'][6]['errors'][] = adminPhrase('Please create a file named zenario_siteconfig.php in the location shown below.');
			
			} elseif ($checkConfigFileExists === 0) {
				$box['tabs'][6]['errors'][] = adminPhrase('Please please enter the text as shown into your zenario_siteconfig.php file.');
			}
		}
		
		if ($checkConfigFileExists) {
			if (!@include_once CMS_ROOT. 'zenario_siteconfig.php') {
				$box['tabs'][6]['errors'][] = adminPhrase('There is a syntax error in zenario_siteconfig.php');
			} else {
				foreach (array('DBHOST', 'DBNAME', 'DBUSER', 'DBPASS', 'DBPORT', 'DB_NAME_PREFIX') as $constant) {
					if (!defined($constant) || constant($constant) !== $merge[$constant]) {
						$box['tabs'][6]['errors'][] = adminPhrase('The constants in zenario_siteconfig.php are not set as below.');
						break;
					}
				}
			}
		}
		
		if (!empty($box['tabs'][6]['errors'])) {
			$box['tabs'][6]['fields']['ive_done_it']['pressed'] = false;
			$box['tabs'][6]['fields']['do_it_for_me']['pressed'] = false;
		}
	}
	
	
	
	//Handle navigation
	switch ($box['tab']) {
		case 0:
			if (!empty($box['tabs'][0]['fields']['next']['pressed'])) {
				$box['tab'] = 1;
			}
			
			break;
		
		
		case 1:
			if (!empty($box['tabs'][1]['fields']['restore']['pressed']) || !empty($box['tabs'][1]['fields']['fresh_install']['pressed'])) {
				$box['tab'] = 2;
			}
			
			break;
		
		
		case 2:
			if (!empty($box['tabs'][2]['fields']['previous']['pressed'])) {
				unset($box['tabs'][2]['errors']);
				$box['tab'] = 1;
			
			} elseif (!empty($box['tabs'][2]['fields']['next']['pressed'])) {
				$box['tab'] = 3;
			}
			
			break;
		
		
		case 3:
			if (!empty($box['tabs'][3]['fields']['previous']['pressed'])) {
				unset($box['tabs'][3]['errors']);
				$box['tab'] = 2;
			
			} elseif (!empty($box['tabs'][3]['fields']['next']['pressed'])) {
				if (!empty($box['tabs'][1]['fields']['restore']['pressed'])) {
					$box['tab'] = 45;
				} else {
					$box['tab'] = 4;
				}
			}
			
			break;
		
		
		case 4:
			if (!empty($box['tabs'][4]['fields']['previous']['pressed'])) {
				unset($box['tabs'][4]['errors']);
				$box['tab'] = 3;
			
			} elseif (!empty($box['tabs'][4]['fields']['next']['pressed'])) {
				$box['tab'] = 5;
			}
			
			break;
		
		
		case 5:
			if (!empty($box['tabs'][5]['fields']['previous']['pressed'])) {
				unset($box['tabs'][5]['errors']);
				$box['tab'] = 4;
			
			} elseif (!empty($box['tabs'][5]['fields']['next']['pressed'])) {
				$box['tab'] = 6;
			}
			
			break;
			
			
		case 45:
			if (!empty($box['tabs'][45]['fields']['previous']['pressed'])) {
				unset($box['tabs'][45]['errors']);
				$box['tab'] = 3;
			
			} elseif (!empty($box['tabs'][45]['fields']['next']['pressed'])) {
				$box['tab'] = 6;
			}
			
			break;
		
		
		case 6:
			if (!empty($box['tabs'][6]['fields']['previous']['pressed'])) {
				unset($box['tabs'][6]['errors']);
				
				if (!empty($box['tabs'][1]['fields']['restore']['pressed'])) {
					$box['tab'] = 45;
				} else {
					$box['tab'] = 5;
				}
			
			} elseif (!empty($box['tabs'][6]['fields']['do_it_for_me']['pressed']) || !empty($box['tabs'][6]['fields']['ive_done_it']['pressed'])) {
				$box['tab'] = 7;
			}
			
			break;
			
			
		case 7:
			if (!empty($box['tabs'][7]['fields']['previous']['pressed'])) {
				unset($box['tabs'][7]['errors']);
				$box['tab'] = 6;
			}
			
			break;
	}
	
	
	//Don't let the Admin proceed from Step 1 without accepting the license
	if ($box['tab'] > 0 && !$licenseAccepted) {
		$box['tab'] = 1;
	}
	
	if ($box['tab'] > 1) {
		
		//Hide the "ignore" checkbox if the requirements are both met
		$box['tabs'][2]['fields']['ignore_apache_mysql_requirements']['hidden'] =
			$serverRequirementsMet && $mysqlRequirementsMet;
		
		//Mark the Apache and MySQL requirements as met if the ignore checkbox is set
		if (!empty($box['tabs'][2]['fields']['ignore_apache_mysql_requirements']['current_value'])) {
			$serverRequirementsMet = $mysqlRequirementsMet = true;
		}
		
		//Did the Server meets the requirements?
		if ($serverRequirementsMet && $phpRequirementsMet
		 && $mysqlRequirementsMet && $mbRequirementsMet && $gdRequirementsMet) {
			
			$box['tabs'][2]['fields']['next']['hidden'] = false;
			$box['tabs'][2]['fields']['check_again']['hidden'] = true;
			$box['tabs'][2]['fields']['description_met']['hidden'] = false;
			$box['tabs'][2]['fields']['description_unmet']['hidden'] = true;
		} else {
			$box['tabs'][2]['fields']['next']['hidden'] = true;
			$box['tabs'][2]['fields']['check_again']['hidden'] = false;
			$box['tabs'][2]['fields']['description_met']['hidden'] = true;
			$box['tabs'][2]['fields']['description_unmet']['hidden'] = false;
			
			//If not, don't let the Admin proceed from Step 2
			$box['tab'] = 2;
		}
	}
	
	//Don't let the Admin proceed from Step 3 without a valid database connection
	if ($box['tab'] > 3 && !empty($box['tabs'][3]['errors'])) {
		$box['tab'] = 3;
	}
	
	if (empty($box['tabs'][1]['fields']['restore']['pressed'])) {
		//Don't let the Admin proceed from Step 5 without entering their admin details
		if ($box['tab'] > 5 && !empty($box['tabs'][5]['errors'])) {
			$box['tab'] = 5;
		}
	} else {
		//Don't let the Admin proceed from Step 45 without selecting a backup
		if ($box['tab'] > 45 && !empty($box['tabs'][45]['errors'])) {
			$box['tab'] = 45;
		}
	}
	
	//Don't let the Admin proceed from Step 6 without setting the siteconfig up correctly
	if ($box['tab'] > 6 && !empty($box['tabs'][6]['errors'])) {
		$box['tab'] = 6;
	}
	
	
	//Display the current step
	switch ($box['tab']) {
		case 0:
			
			if ($installStatus == 0) {
				$box['tabs'][0]['fields']['reason']['snippet']['html'] = 
					adminPhrase('The config file "[[file]]" is empty.',
						array('file' => '<code>zenario_siteconfig.php</code>'));
			
			} elseif ($installStatus == 1) {
				$box['tabs'][0]['fields']['reason']['snippet']['html'] = 
					adminPhrase('A database connection could not be established.');
			
			} elseif ($installStatus == 2) {
				$box['tabs'][0]['fields']['reason']['snippet']['html'] = 
					adminPhrase('The database is empty, or contains no tables with the specified prefix.');
			}
			
			break;
		
		
		case 1:
			if (is_file(CMS_ROOT. 'license.txt')) {
				$box['tabs'][1]['fields']['license']['snippet']['url'] = 'license.txt';
			
			} else {
				echo adminPhrase('The license file "license.txt" could not be found. This installer cannot proceed.');
				exit;
			}
			
			break;
		
		
		case 2:
			if (!$serverRequirementsMet) {
				$box['tabs'][2]['fields']['show_server']['pressed'] = true;
				$box['tabs'][2]['fields']['server']['row_class'] = 'section_invalid';
			} else {
				$box['tabs'][2]['fields']['server']['row_class'] = 'section_valid';
			}
			
			if (!$phpRequirementsMet) {
				$box['tabs'][2]['fields']['show_php']['pressed'] = true;
				$box['tabs'][2]['fields']['php']['row_class'] = 'section_invalid';
			} else {
				$box['tabs'][2]['fields']['php']['row_class'] = 'section_valid';
			}
			
			if (!$mysqlRequirementsMet) {
				$box['tabs'][2]['fields']['show_mysql']['pressed'] = true;
				$box['tabs'][2]['fields']['mysql']['row_class'] = 'section_invalid';
			} else {
				$box['tabs'][2]['fields']['mysql']['row_class'] = 'section_valid';
			}
			
			if (!$mbRequirementsMet) {
				$box['tabs'][2]['fields']['show_mb']['pressed'] = true;
				$box['tabs'][2]['fields']['mb']['row_class'] = 'section_invalid';
			} else {
				$box['tabs'][2]['fields']['mb']['row_class'] = 'section_valid';
			}
			
			if (!$gdRequirementsMet) {
				$box['tabs'][2]['fields']['show_gd']['pressed'] = true;
				$box['tabs'][2]['fields']['gd']['row_class'] = 'section_invalid';
			} else {
				$box['tabs'][2]['fields']['gd']['row_class'] = 'section_valid';
			}
			
			break;
		
		
		case 3:
			//Nothing doing for step 3
			
			break;
		
		
		case 4:
			
			//Old code for sample sites, commented out as we don't currently use them
			//$box['tabs'][4]['fields']['sample']['values'] = array(
			//	0 => array(
			//		'label' => adminPhrase('Blank Site:'),
			//		'note_below' => adminPhrase('An empty site, ready for you to choose a language.')));
			//
			//foreach (listSampleSites() as $dir => $path) {
			//	$box['tabs'][4]['fields']['sample']['values'][$dir] = array(
			//		'label' => $dir. ': ',
			//		'note_below' => file_get_contents($path. 'description.txt'));
			//}
			
			//Format the labels for the date format picker fields
			if (empty($box['tabs'][4]['fields']['language_id']['values'])) {
				$box['tabs'][4]['fields']['language_id']['values'] = array();
				
				$ord = 0;
				$languages = require CMS_ROOT. 'zenario/includes/language_names.inc.php';
				foreach ($languages as $langId => $phrases) {
					$box['tabs'][4]['fields']['language_id']['values'][$langId] = $phrases[0]. ' ('. $langId. ')';
				}
				
				formatDateFormatSelectList($box['tabs'][4]['fields']['vis_date_format_short'], true);
				formatDateFormatSelectList($box['tabs'][4]['fields']['vis_date_format_med']);
				formatDateFormatSelectList($box['tabs'][4]['fields']['vis_date_format_long']);
			}
			
			//Add logic if English is not chosen..?
			//if (!empty($box['tabs'][4]['fields']['language_id']['current_value'])
			// && !chopPrefixOffOfString($box['tabs'][4]['fields']['language_id']['current_value'], 'en-')) {
			//	
			//}
			
			if (empty($box['tabs'][4]['fields']['theme']['values'])) {
				$box['tabs'][4]['fields']['theme']['values'] = array();
				foreach (listSampleThemes() as $dir => $imageSrc) {
					$box['tabs'][4]['fields']['theme']['values'][$dir] = array(
						'label' => '',
						'post_field_html' =>
							'<label for="theme___'. htmlspecialchars($dir). '">
								<img src="'. htmlspecialchars('../../'. $imageSrc). '"/>
							</label>'
					);
				}
			}
			
			break;
		
		
		case 5:
			
			$strength = passwordValuesToStrengths(checkPasswordStrength(arrayKey($box['tabs'][5]['fields']['password'], 'current_value'), true));
			$box['tabs'][5]['fields']['password_strength']['snippet']['html'] =
				'<div class="password_'. $strength. '"><span>'. adminPhrase($strength). '</span></div>';
			
			
			break;
		
		
		case 45:
			$box['tabs'][45]['fields']['path']['value'] = CMS_ROOT;
			$box['tabs'][45]['fields']['path_status']['snippet']['html'] = '&nbsp;';
			
			
			break;
		
		
		case 6:
			$box['tabs'][6]['fields']['zenario_siteconfig']['pre_field_html'] =
				'<pre>'. CMS_ROOT. 'zenario_siteconfig.php'. ':</pre>';
			$box['tabs'][6]['fields']['zenario_siteconfig']['value'] = readSampleConfigFile($merge);
			unset($box['tabs'][6]['fields']['zenario_siteconfig']['current_value']);
			
			
			break;
		
		
		case 7:
			$box['tabs'][7]['errors'] = array();
			
			if (!defined('SHOW_SQL_ERRORS_TO_VISITORS')) {
				define('SHOW_SQL_ERRORS_TO_VISITORS', true);
			}
			
			$merge['URL'] = httpOrhttps(). $_SERVER['HTTP_HOST'];
			$merge['HTTP_HOST'] = $_SERVER['HTTP_HOST'];
			$merge['SUBDIRECTORY'] = SUBDIRECTORY;
			
			//Set the latest revision number
			$merge['LATEST_REVISION_NO'] = LATEST_REVISION_NO;
			$merge['INSTALLER_REVISION_NO'] = INSTALLER_REVISION_NO;
			
			
			//Install to the database
			sqlSelect('SET NAMES "UTF8"');
			sqlSelect("SET collation_connection='utf8_general_ci'");
			sqlSelect("SET collation_server='utf8_general_ci'");
			sqlSelect("SET character_set_client='utf8'");
			sqlSelect("SET character_set_connection='utf8'");
			sqlSelect("SET character_set_results='utf8'");
			sqlSelect("SET character_set_server='utf8'");
			
			if (!defined('DB_NAME_PREFIX')) {
				define('DB_NAME_PREFIX', $merge['DB_NAME_PREFIX']);
			}
			
			
			//Here we restore a backup, and/or do a fresh install, depending on the choices in the installer.
			//(Installing a sample site is done by restoring a backup, then immediately doing a fresh install.)
			
			$doFreshInstall = !empty($box['tabs'][1]['fields']['fresh_install']['pressed']);
			$restoreBackup = !$doFreshInstall && !empty($box['tabs'][1]['fields']['restore']['pressed']);
			
			//Old code for sample sites, commented out as we don't currently use them
			//$installSampleSite = $doFreshInstall && !empty($box['tabs'][4]['fields']['sample']['current_value']);
			
			//Try to restore a backup file
			if ($restoreBackup) {
				//Set the primary_domain to the current domain, reguardless of what it is in the backup
				cms_core::$siteConfig['primary_domain'] = $_SERVER['HTTP_HOST'];
				
				//Restore a backup, using either the specified path or the sample install path
				
				//Old code for sample sites, commented out as we don't currently use them
				//if ($installSampleSite) {
				//	$backupPath = '';
				//	foreach (listSampleSites() as $dir => $path) {
				//		if ($dir == $box['tabs'][4]['fields']['sample']['current_value']) {
				//			$backupPath = $path. 'backup.sql.gz';
				//		}
				//	}
				//} else {
					$backupPath = $box['tabs'][45]['fields']['path']['current_value'];
				//}
				
				$failures = array();
				if (!restoreDatabaseFromBackup(
						$backupPath,
						//Attempt to check whether gzip compression has not been used
						strtolower(substr($backupPath, -3)) != '.gz',
						DB_NAME_PREFIX, $failures
				)) {
					foreach ($failures as $text) {
						$box['tabs'][7]['errors'][] = $text;
					}
				}
			}
			
			//Set up tables in a fresh installation
			if ($doFreshInstall && empty($box['tabs'][7]['errors'])) {
				//Old code for sample sites, commented out as we don't currently use them
				//if ($installSampleSite) {
				//	$files = array('local-admin-CREATE.sql', 'local-sample-INSERT.sql');
				//} else {
					set_time_limit(60 * 10);
					$files = array('local-CREATE.sql', 'local-admin-CREATE.sql', 'local-INSERT.sql');
				//}
				
				$error = false;
				foreach ($files as $file) {
					if (!runSQL('zenario/admin/db_install/', $file, $error, $merge)) {
						$box['tabs'][7]['errors'][] = $error;
						break;
					}
				}
				
				//Was the install successful?
				if (empty($box['tabs'][7]['errors'])) {
					
					//Define the main email address for the remaining run of this script
					//(This is just needed this once; the next time a script run this should
					// be defined by the config file.)
					if (!defined('EMAIL_ADDRESS_GLOBAL_SUPPORT')) {
						define('EMAIL_ADDRESS_GLOBAL_SUPPORT', $merge['EMAIL_ADDRESS_GLOBAL_SUPPORT']);
					}
					
					//Populate the Modules table with all of the Modules in the system,
					//and install and run any Modules that should running by default.
					addNewModules($skipIfFilesystemHasNotChanged = false, $runModulesOnInstall = true, $dbUpdateSafeMode = true);
					
					//If a language is picked, enable that language by default,
					//import any phrase packs and create special pages such as the home page.
					if ($merge['LANGUAGE_ID']
					 && ($languages = require CMS_ROOT. 'zenario/includes/language_names.inc.php')
					 && (!empty($languages[$merge['LANGUAGE_ID']]))) {
						
						$translatePhrases = 1;
						$searchType = 'full_text';
						switch ($merge['LANGUAGE_ID']) {
							case 'en-gb':
							case 'en-us':
								$translatePhrases = 0;
								break;
							case 'zh-hans':
							case 'zh-hant':
							case 'ko':
							case 'ja':
							case 'vi':
								$searchType = 'simple';
								break;
						}
						
						setRow('languages', array(
							'detect' => 0,
							'detect_lang_codes' => '',
							'translate_phrases' => $translatePhrases,
							'search_type'=> $searchType
							), 
							$merge['LANGUAGE_ID']);
						
						//Set this language as the default language
						setSetting('default_language', $merge['LANGUAGE_ID']);

						//Import any phrases for Modules that use phrases
						importPhrasesForModules($merge['LANGUAGE_ID']);
					}
					
					setSetting('vis_date_format_short', $merge['VIS_DATE_FORMAT_SHORT']);
					setSetting('vis_date_format_med', $merge['VIS_DATE_FORMAT_MED']);
					setSetting('vis_date_format_long', $merge['VIS_DATE_FORMAT_LONG']);
					setSetting('vis_date_format_datepicker', convertMySQLToJqueryDateFormat($merge['VIS_DATE_FORMAT_SHORT']));
					setSetting('organizer_date_format', convertMySQLToJqueryDateFormat($merge['VIS_DATE_FORMAT_MED']));
					
					//Set a random key that is linked to the site.
					setSetting('site_id', generateRandomSiteIdentifierKey());
		
					//Set a random first data-revision number
					//(If someone is doing repeated fresh installs, this stops data from an old one getting cached and used in another)
					setRow('local_revision_numbers', array('revision_no' => rand(1, 32767)), array('path' => 'data_rev', 'patchfile' => 'data_rev'));
					
					
					$freshInstall = true;
					
					//Create an Admin, and give them all of the core permissions
					$details = array(
						'username' => $merge['USERNAME'],
						'first_name' => $merge['admin_first_name'],
						'last_name' => $merge['admin_last_name'],
						'email' => $merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'],
						'created_date' => now(),
						'status' => 'active');
					
					$adminId = insertRow('admins', $details);
					setPasswordAdmin($adminId, $merge['PASSWORD']);
					saveAdminPerms($adminId, 'all_permissions');
					setAdminSession($adminId);
					
					
					//Prepare email to the installing person
					$message = $tags['email_templates']['installed_cms']['body'];
					
					$subject = $tags['email_templates']['installed_cms']['subject'];
					
					foreach ($merge as $pattern => $replacement) {
						$message = str_replace('[['. $pattern. ']]', $replacement, $message);
					}
					
					$addressToOverriddenBy = false;
					sendEmail(
						$subject, $message,
						$merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'],
						$addressToOverriddenBy,
						$nameTo = false,
						$addressFrom = false,
						$nameFrom = $tags['email_templates']['installed_cms']['from'],
						false, false, false,
						$isHTML = false);
					
					
					//Apply database updates
					checkIfDBUpdatesAreNeeded($andDoUpdates = true);
					
					//Fix a bug where sample sites might not have a default language set, by setting a default language if any content has been created
					if (!setting('default_language') && ($langId = getRow('content_items', 'language_id', array()))) {
						setSetting('default_language', $langId);
					}
					
					//Populate the menu_hierarchy and the menu_positions tables
					recalcAllMenuHierarchy();
					
					//Update the special pages, creating new ones if needed
					addNeededSpecialPages();
				}
			}
			
			if (!empty($box['tabs'][7]['errors'])) {
				//Did something go wrong? Remove any tables that were created.
				runSQL('zenario/admin/db_install/', 'local-DROP.sql', $error, $merge);
				runSQL('zenario/admin/db_install/', 'local-admin-DROP.sql', $error, $merge);
				
				foreach (lookupExistingCMSTables($dbUpdateSafeMode = true) as $table) {
					if ($table['reset'] == 'drop') {
						sqlSelect("DROP TABLE `". sqlEscape($table['actual_name']). "`");
					}
				}
			} else {
				return true;
			}
			
			break;
	}
	
	
	
	return false;
}


function loginAJAX(&$tags, &$box, $getRequest) {
	$passwordReset = false;
	$box['tabs']['login']['errors'] = array();
	$box['tabs']['forgot']['errors'] = array();
	
	if ($box['tab'] == 'login' && !empty($box['tabs']['login']['fields']['previous']['pressed'])) {
		$box['go_to_url'] = redirectAdmin($getRequest);
		return false;
	
	//Go between the login and the forgot password screens
	} elseif ($box['tab'] == 'login' && !empty($box['tabs']['login']['fields']['forgot']['pressed'])) {
		$box['tab'] = 'forgot';
	
	} elseif ($box['tab'] == 'forgot' && !empty($box['tabs']['forgot']['fields']['previous']['pressed'])) {
		$box['tab'] = 'login';
	
	//Check a login attempt
	} elseif ($box['tab'] == 'login' && !empty($box['tabs']['login']['fields']['login']['pressed'])) {
		
		if (!$box['tabs']['login']['fields']['username']['current_value']) {
			$box['tabs']['login']['errors'][] = adminPhrase('Please enter your Username.');
		}
		
		if (!$box['tabs']['login']['fields']['password']['current_value']) {
			$box['tabs']['login']['errors'][] = adminPhrase('Please enter your Password.');
		}
		
		if (empty($box['tabs']['login']['errors'])) {
			$details = array();
			
			$adminIdL = checkPasswordAdmin($box['tabs']['login']['fields']['username']['current_value'], $details, $box['tabs']['login']['fields']['password']['current_value'], $checkViaEmail = false);
			
			if (!$adminIdL) {
				$box['tabs']['login']['errors']['details_wrong'] =
					adminPhrase('Your username and password combination was not recognised. Please check and try again.');
			
			} elseif (isError($adminIdL)) {
				$box['tabs']['login']['errors']['details_wrong'] =
					adminPhrase($adminIdL->__toString());
			
			} else {
				if ($box['tabs']['login']['fields']['remember_me']['current_value']) {
					setCookieOnCookieDomain('COOKIE_LAST_ADMIN_USER', $box['tabs']['login']['fields']['username']['current_value']);
					clearCookie('COOKIE_DONT_REMEMBER_LAST_ADMIN_USER');
				} else {
					setCookieOnCookieDomain('COOKIE_DONT_REMEMBER_LAST_ADMIN_USER', '1');
					clearCookie('COOKIE_LAST_ADMIN_USER');
				}
				
				if ($details['type'] == 'global') {
					setAdminSession($adminIdL, $details['id']);
				} else {
					setAdminSession($adminIdL);
				}
				setCookieConsent();
				
				//Note the time this admin last logged in
					//This might fail if this site needs a db_update and the last_login_ip column does not exist.
				
				require_once CMS_ROOT. 'zenario/libraries/mit/browser/lib/browser.php';
				$browser = new Browser();
				
				$sql = "
					UPDATE ". DB_NAME_PREFIX. "admins SET
						last_login = NOW(),
						last_login_ip = '". sqlEscape(visitorIP()). "',
						last_browser = '". sqlEscape($browser->getBrowser()). "',
						last_browser_version = '". sqlEscape($browser->getVersion()). "',
						last_platform = '". sqlEscape($browser->getPlatform()). "'
					WHERE id = ". (int) $adminIdL;
				@sqlSelect($sql);
				
				// Update last domain, so primaryDomain can return a domain name if the primary domain site setting is not set.
				if (setting('admin_domain_is_public')) {
					setRow('site_settings', array('value' => primaryDomain()), array('name' => 'last_primary_domain'));
				}
				
				//Don't offically mark the admin as "logged in" until they've passed all of the
				//checks in the admin login screen
				$_SESSION['admin_logged_in'] = false;
				
				return true;
			}
		}
	
	//Reset a password
	} elseif ($box['tab'] == 'forgot' && !empty($box['tabs']['forgot']['fields']['reset']['pressed'])) {
		
		if (!$box['tabs']['forgot']['fields']['email']['current_value']) {
			$box['tabs']['forgot']['errors'][] = adminPhrase('Please enter your email address.');
		
		} elseif (!validateEmailAddress($box['tabs']['forgot']['fields']['email']['current_value'])) {
			$box['tabs']['forgot']['errors'][] = adminPhrase('Please enter a valid email address.');
		
		//Check for the user by email address, without specifying their password.
		//This will return 0 if the user wasn't found, otherwise it should return
		//false (for user found but password didn't match)
		} elseif (!$admin = getRow('admins', array('id', 'authtype', 'username', 'email', 'first_name', 'last_name', 'status'), array('email' => $box['tabs']['forgot']['fields']['email']['current_value']))) {
			$box['tabs']['forgot']['errors'][] = adminPhrase("Sorry, we could not find that email address associated with an active Administrator on this site's database.");
		
		//Super Admins shouldn't be trying to change their passwords on a local site
		} elseif (isset($admin['authtype']) && $admin['authtype'] != 'local') {
			$box['tabs']['forgot']['errors'][] = adminPhrase("Please go to the Controller site to reset the password for a Superadmin account.");
		
		//Trashed admins should not be able to trigger password resets
		} elseif ($admin['status'] == 'deleted') {
			$box['tabs']['forgot']['errors'][] = adminPhrase("Sorry, we could not find that email address associated with an active Administrator on this site's database.");
		
		} else {
			
			//Prepare email to the mail with the reset password
			$merge = array();
			$merge['NAME'] = ifNull(trim($admin['first_name']. ' '. $admin['last_name']), $admin['username']);
			$merge['USERNAME'] = $admin['username'];
			$merge['PASSWORD'] = resetPasswordAdmin($admin['id']);
			$merge['URL'] = httpOrhttps(). $_SERVER['HTTP_HOST'];
			$merge['SUBDIRECTORY'] = SUBDIRECTORY;
			$merge['IP'] = preg_replace('[^W\.\:]', '', visitorIP());
			
			$emailTemplate = checkAdminTableColumnsExist() >= 3? 'new_reset_password' : 'reset_password';
			$message = $tags['email_templates'][$emailTemplate]['body'];
			
			$subject = $tags['email_templates'][$emailTemplate]['subject'];
			
			foreach ($merge as $pattern => $replacement) {
				$message = str_replace('[['. $pattern. ']]', $replacement, $message);
			}
			
			$addressToOverriddenBy = false;
			sendEmail(
				$subject, $message,
				$admin['email'],
				$addressToOverriddenBy,
				$nameTo = $merge['NAME'],
				$addressFrom = false,
				$nameFrom = $tags['email_templates'][$emailTemplate]['from'],
				false, false, false,
				$isHTML = false);
			
			$passwordReset = true;
			$box['tab'] = 'login';
		}
	}
	
	
	//Format the login screen
	if ($box['tab'] == 'login') {
		if (!empty($_COOKIE['COOKIE_LAST_ADMIN_USER'])) {
			$box['tabs']['login']['fields']['username']['value'] = $_COOKIE['COOKIE_LAST_ADMIN_USER'];
		}
		$box['tabs']['login']['fields']['remember_me']['value'] = empty($_COOKIE['COOKIE_DONT_REMEMBER_LAST_ADMIN_USER']);
		
		//Don't show the note about the admin login link if it is turned off in the site settings
		if (!setting('admin_domain_is_public')) {
			$box['tabs']['login']['fields']['remember_me']['note_below'] = '';
		}
		
		if ($passwordReset) {
			$box['tabs']['login']['fields']['reset']['hidden'] = false;
			$box['tabs']['login']['fields']['description']['hidden'] = true;
		} else {
			$box['tabs']['login']['fields']['reset']['hidden'] = true;
			$box['tabs']['login']['fields']['description']['hidden'] = false;
		}
	}
	
	return false;
}



function updateNoPermissionsAJAX(&$tags, &$box, &$task, $getRequest) {
	//Handle the "back to site" button
	if (!empty($box['tabs'][0]['fields']['previous']['pressed'])) {
		logoutAdminAJAX($box, $getRequest);
		return;
	
	} else {
		$admins = getRowsArray(
			'admins',
			array('first_name', 'last_name', 'username', 'authtype'),
			array(
				'status' => 'active',
				'id' => getRowsArray(
					'action_admin_link',
					'admin_id',
					array('action_name' => array('_ALL', '_PRIV_APPLY_DATABASE_UPDATES')))),
			array('first_name', 'last_name', 'username', 'authtype')
		);
		
		if (!empty($admins)) {
			$html =
				'<p>'. adminPhrase('The following administrators are able to apply updates:'). '</p><ul>';
			
			foreach ($admins as $admin) {
				$html .= '<li>'. htmlspecialchars(formatAdminName($admin)). '</li>';
			}
			
			$html .= '</ul>';
			
			$box['tabs'][0]['fields']['admins_who_can_do_updates']['hidden'] = false;
			$box['tabs'][0]['fields']['admins_who_can_do_updates']['snippet']['html'] = $html;
		}
	}
}



function updateAJAX(&$tags, &$box, &$task) {
	$box['tab'] = 1;
	
	//Apply updates without prompting when installing, restoring backups or doing site resets
	if ($task == 'install' || $task == 'restore' || $task == 'site_reset' || !empty($box['tabs'][1]['fields']['apply']['pressed'])) {
		
		//Update to the latest version
		set_time_limit(60 * 10);
		$dbUpToDate = checkIfDBUpdatesAreNeeded($andDoUpdates = true);
		
		if ($dbUpToDate) {
			return true;
		}
	}
	
	
	$box['tabs'][1]['fields']['description']['snippet']['html'] =
		adminPhrase('We need to update your database (<code>[[database]]</code>) to match.', array('database' => htmlspecialchars(DBNAME)));
	
	
	if ($box['tab'] == 1 && !empty($box['tabs'][1]['fields']['why']['pressed'])) {
		$modules = array();
		$revisions = array();
		$currentRevisionNumber = false;
		$latestRevisionNumber = LATEST_REVISION_NO;
	
		//Get details on any database updates needed
		if ($updates = checkIfDBUpdatesAreNeeded(false, false, false)) {
			list($currentRevisionNumber, $modules) = $updates;
		}
		
		if (empty($modules)
		 && empty($currentRevisionNumber)) {
			
			$html =
				'<table class="revisions"><tr class="even"><td>'.
					adminPhrase('A major CMS update needs to be applied.').
				'</td></tr></table>';
		
		} else {
			if ($currentRevisionNumber === false) {
				$currentRevisionNumber = $latestRevisionNumber;
			}
		
			if ($currentRevisionNumber != $latestRevisionNumber) {
				$revisions['core'] = array('name' => ' '. adminPhrase('Core files'), 'from' => $currentRevisionNumber, 'to' => $latestRevisionNumber);
			}
		
			if (!empty($modules)) {
				foreach($modules as $module => $pluginRevisions) {
					$sql = "
						SELECT display_name
						FROM ". DB_NAME_PREFIX. "modules
						WHERE class_name = '". sqlEscape($module). "'";
				
					if (($result = @sqlSelect($sql)) && ($row = sqlFetchAssoc($result))) {
						$moduleName = ifNull($row['display_name'], $module);
					} else {
						$moduleName = $module;
					}
				
					$revisions[$module] = array('name' => $moduleName, 'from' => $pluginRevisions[0] + 1, 'to' => $pluginRevisions[1]);
				}
			}
		
			sqlArraySort($revisions);
		
			$html = '<table class="revisions"><tr><th>'. adminPhrase('Module'). '</th><th>'. adminPhrase('Revision Numbers to Apply'). '</th></tr>';
		
			$oddOrEven = 'even';
			foreach ($revisions as $revision) {
				$oddOrEven = $oddOrEven == 'even'? 'odd' : 'even';
				$html .= '<tr class="'. $oddOrEven. '"><td>'. htmlspecialchars($revision['name']). '</td><td>';
			
				if ($revision['from'] == $revision['to']) {
					$html .= adminPhrase('Revision #[[to]]', $revision);
			
				} elseif ($revision['from'] == 0) {
					$revision['version'] = getCMSVersionNumber();
					$html .= adminPhrase('Updates from [[version]]', $revision);
			
				} else {
					$html .= adminPhrase('Revision #[[from]] through to Revision #[[to]]', $revision);
				}
			
				$html .= '</tr>';
			}
		
			$html .= '</table>';
		}
		
		$box['tabs'][1]['fields']['updates']['snippet']['html'] = $html;
		$box['tabs'][1]['fields']['updates']['hidden'] = false;
	} else {
		$box['tabs'][1]['fields']['updates']['hidden'] = true;
	}
	
	return false;
}


//Log the current admin out
function logoutAdminAJAX(&$box, $getRequest) {
	unsetAdminSession();
	$box['_clear_local_storage'] = true;
	$box['go_to_url'] = redirectAdmin($getRequest, true);
}

//Return the UNIX time, $offset ago, converted to a string
function zenarioSecurityCodeTime($offset = 0) {
	return str_pad(time() - (int) $offset * 86400, 16, '0', STR_PAD_LEFT);
}

//This returns the name that the cookie for the security code should have.
//This is in the form "COOKIE_ADMIN_SECURITY_CODE_[[ADMIN_ID]]"
function zenarioSecurityCodeCookieName() {
	return 'COOKIE_ADMIN_SECURITY_CODE_'. adminId();
}

//Get the value of the cookie above
function zenarioSecurityCodeCookieValue() {
	if (isset($_COOKIE[zenarioSecurityCodeCookieName()])) {
		return preg_replace('@[^-_=\w]@', '', $_COOKIE[zenarioSecurityCodeCookieName()]);
	} else {
		return '';
	}
}

//Looks for a security code cookie with the above name,
//then returns the corresponding name that a site setting should have.
//This is in the form "COOKIE_ADMIN_SECURITY_CODE_[[COOKIE_VALUE]]", or
//"COOKIE_ADMIN_SECURITY_CODE_[[COOKIE_VALUE]]_[[IP_ADDRESS]]", depending on
//whether the security_code_by_ip option is set in the site_description.yaml file.
function zenarioSecurityCodeSettingName() {
	
	if ('' == ($sccn = zenarioSecurityCodeCookieValue())) {
		 return false;
	
	} elseif (siteDescription('security_code_by_ip')) {
		return 'COOKIE_ADMIN_SECURITY_CODE_'. $sccn. '_'. visitorIP();

	} else {
		return 'COOKIE_ADMIN_SECURITY_CODE_'. $sccn;
	}
}

//Tidy up outdated codes
function zenarioTidySecurityCodes() {
	$sql = "
		DELETE FROM ". DB_NAME_PREFIX. "admin_settings
		WHERE name LIKE 'COOKIE_ADMIN_SECURITY_CODE_%'
		  AND value < '". sqlEscape(zenarioSecurityCodeTime(siteDescription('security_code_timeout'))). "'";
	sqlUpdate($sql, false);
}






function securityCodeAJAX(&$tags, &$box, &$task, $getRequest) {
	
	$firstTimeHere = empty($_SESSION['COOKIE_ADMIN_SECURITY_CODE']);
	$resend = !empty($box['tabs']['security_code']['fields']['resend']['pressed']);
	$box['tabs']['security_code']['notices']['email_resent']['show'] = false;
	$box['tabs']['security_code']['errors'] = array();
	
	//Handle the "back to site" button
	if (!empty($box['tabs']['security_code']['fields']['previous']['pressed'])) {
		logoutAdminAJAX($box, $getRequest);
		return false;
	
	//If we've not sent the email yet, or the user presses the retry button, then send the email
	} elseif ($firstTimeHere || $resend) {
		
		if ($firstTimeHere) {
			$_SESSION['COOKIE_ADMIN_SECURITY_CODE'] = randomStringFromSet(5, 'ABCDEFGHIJKLMNPPQRSTUVWXYZ');
		}
		
		$admin = getRow('admins', array('id', 'authtype', 'username', 'email', 'first_name', 'last_name'), adminId());
		
		//Prepare email to the mail with the code
		$merge = array();
		$merge['NAME'] = ifNull(trim($admin['first_name']. ' '. $admin['last_name']), $admin['username']);
		$merge['USERNAME'] = $admin['username'];
		$merge['URL'] = httpOrhttps(). $_SERVER['HTTP_HOST'];
		$merge['SUBDIRECTORY'] = SUBDIRECTORY;
		$merge['IP'] = preg_replace('[^W\.\:]', '', visitorIP());
		$merge['CODE'] = $_SESSION['COOKIE_ADMIN_SECURITY_CODE'];
		
		$emailTemplate = 'security_code';
		$message = $tags['email_templates'][$emailTemplate]['body'];
		
		$subject = $tags['email_templates'][$emailTemplate]['subject'];
		
		foreach ($merge as $pattern => $replacement) {
			$subject = str_replace('[['. $pattern. ']]', $replacement, $subject);
			$message = str_replace('[['. $pattern. ']]', $replacement, $message);
		}
		
		$addressToOverriddenBy = false;
		$emailSent = sendEmail(
			$subject, $message,
			$admin['email'],
			$addressToOverriddenBy,
			$nameTo = $merge['NAME'],
			$addressFrom = false,
			$nameFrom = $tags['email_templates'][$emailTemplate]['from'],
			false, false, false,
			$isHTML = false);
		
		if (!$emailSent) {
			$box['tabs']['security_code']['errors'][] =
				adminPhrase('Warning! The system could not send an email. Please contact your server administrator.');
			
		} elseif ($resend) {
			$box['tabs']['security_code']['notices']['email_resent']['show'] = true;
		}
	
	//Check the code if someone is trying to submit it
	} else
	if (!empty($_SESSION['COOKIE_ADMIN_SECURITY_CODE'])
	 && !empty($box['tabs']['security_code']['fields']['submit']['pressed'])) {
		
		$code = $box['tabs']['security_code']['fields']['code']['current_value'];
		
		if ($code == '') {
			$box['tabs']['security_code']['errors'][] =
				adminPhrase('Please enter the code that was sent to your email address.');
		
		} elseif (trim($code) !== $_SESSION['COOKIE_ADMIN_SECURITY_CODE']) {
			$box['tabs']['security_code']['errors'][] =
				adminPhrase('The code you have entered is not correct.');
		
		} else {
			
			//If the code is correct, save a cookie...
			setCookieOnCookieDomain(
				zenarioSecurityCodeCookieName(),
				randomString(),
				(int) siteDescription('security_code_timeout') * 86400);
			
			//...and an admin setting to remember it next time!
			$scsn = zenarioSecurityCodeSettingName();
			$time = zenarioSecurityCodeTime();
			setAdminSetting($scsn, $time);
			
			return true;
		}
	}
	
	
	return false;
}





function changePasswordAJAX(&$tags, &$box, &$task) {
	
	//Skip this screen if the Admin presses the "Skip" button
	if (!empty($box['tabs']['change_password']['fields']['skip']['pressed'])) {
		cancelPasswordChange(session('admin_userid'));
		
		if ($task == 'change_password') {
			$task = 'password_changed';
		}
		
		return true;
	
	//Change the password if the Admin presses the change password
	} elseif (!empty($box['tabs']['change_password']['fields']['change_password']['pressed'])) {
		$details = array();
		$box['tabs']['change_password']['errors'] = array();
		$currentPassword = $box['tabs']['change_password']['fields']['current_password']['current_value']; 
		$newPassword = $box['tabs']['change_password']['fields']['password']['current_value']; 
		$newPasswordConfirm = $box['tabs']['change_password']['fields']['re_password']['current_value']; 
		
		if (!$currentPassword) {
			$box['tabs']['change_password']['errors'][] = adminPhrase('Please enter your current password.');
		
		} elseif (!engToBoolean(checkPasswordAdmin(session('admin_username'), $details, $currentPassword))) {
			$box['tabs']['change_password']['errors'][] = adminPhrase('_MSG_PASS_WRONG');
		}
		
		if (!$newPassword) {
			$box['tabs']['change_password']['errors'][] = adminPhrase('Please enter your new password.');
		
		} elseif ($newPassword == $currentPassword) {
			$box['tabs']['change_password']['errors'][] = adminPhrase('_MSG_PASS_NOT_CHANGED');
		
		} elseif (!checkPasswordStrength($newPassword)) {
			$box['tabs']['change_password']['errors'][] = adminPhrase('_MSG_PASS_STRENGTH');
		
		} elseif (!$newPasswordConfirm) {
			$box['tabs']['change_password']['errors'][] = adminPhrase('Please repeat your New Password.');
		
		} elseif ($newPassword != $newPasswordConfirm) {
			$box['tabs']['change_password']['errors'][] = adminPhrase('_MSG_PASS_2');
		}
		
		//If no errors with validation, then save new password
		if (empty($box['tabs']['change_password']['errors'])) {
			setPasswordAdmin(session('admin_userid'), $newPassword, 0);
			
			if ($task == 'change_password') {
				$task = 'password_changed';
			}
			
			return true;
		}
	}
	
	
	//Show the password strength box
	$strength = passwordValuesToStrengths(checkPasswordStrength(arrayKey($box['tabs']['change_password']['fields']['password'], 'current_value'), true));
	$box['tabs']['change_password']['fields']['password_strength']['snippet']['html'] =
		'<div class="password_'. $strength. '"><span>'. adminPhrase($strength). '</span></div>';
	
	
	return false;
}



function diagnosticsAJAX(&$tags, &$box, $freshInstall) {
	
	$showCheckAgainButton = false;
	$showCheckAgainButtonIfDirsAreEditable = false;
	
	//If the directories aren't set at all, set them now so at least they're set to something
	if (!setting('backup_dir')) {
		setSetting('backup_dir', suggestDir('backup'));
	}
	if (!setting('docstore_dir')) {
		setSetting('docstore_dir', suggestDir('docstore'));
	}
	
	
	$box['tabs'][0]['fields']['backup_dir']['value']	= (string) setting('backup_dir');
	$box['tabs'][0]['fields']['docstore_dir']['value']	= (string) setting('docstore_dir');
	$box['tabs'][0]['fields']['template_dir']['value']	= $tdir = CMS_ROOT. 'zenario_custom/templates/grid_templates';
	$box['tabs'][0]['fields']['cache_dir']['value']	= CMS_ROOT. 'cache';
	$box['tabs'][0]['fields']['private_dir']['value']	= CMS_ROOT. 'private';
	$box['tabs'][0]['fields']['public_dir']['value']	= CMS_ROOT. 'public';
	
	if (empty($box['tabs'][0]['fields']['backup_dir']['current_value'])) {
		$box['tabs'][0]['fields']['backup_dir']['current_value'] =
			$box['tabs'][0]['fields']['backup_dir']['value'];
	}
	
	if (empty($box['tabs'][0]['fields']['docstore_dir']['current_value'])) {
		$box['tabs'][0]['fields']['docstore_dir']['current_value'] =
			$box['tabs'][0]['fields']['docstore_dir']['value'];
	}
	
	
	$box['tabs'][0]['fields']['dirs']['row_class'] = 'section_valid';
	
	
	$mrg = array(
		'dir' => $dir = $box['tabs'][0]['fields']['backup_dir']['current_value'],
		'basename' => $dir? htmlspecialchars(basename($dir)) : '');
	
	if (!$dir) {
		$box['tabs'][0]['fields']['backup_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['backup_dir_status']['snippet']['html'] = adminPhrase('Please enter a directory.');
	
	} elseif (!@is_dir($dir)) {
		$box['tabs'][0]['fields']['backup_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['backup_dir_status']['snippet']['html'] = adminPhrase('The directory &quot;[[basename]]&quot; does not exist.', $mrg);
	
	} elseif (false !== chopPrefixOffOfString(realpath($dir), realpath(CMS_ROOT))) {
		$box['tabs'][0]['fields']['backup_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['backup_dir_status']['snippet']['html'] = adminPhrase('Zenario is installed this directory. Please choose a different directory.', $mrg);
	
	} elseif (!directoryIsWritable($dir)) {
		$box['tabs'][0]['fields']['backup_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['backup_dir_status']['snippet']['html'] = adminPhrase('The directory &quot;[[basename]]&quot; is not writable.', $mrg);
	
	} else {
		$box['tabs'][0]['fields']['dir_1']['row_class'] = 'sub_section_valid';
		$box['tabs'][0]['fields']['backup_dir_status']['row_class'] = 'sub_valid';
		$box['tabs'][0]['fields']['backup_dir_status']['snippet']['html'] = adminPhrase('The directory &quot;[[basename]]&quot; exists and is writable.', $mrg);
	}
	
	
	$mrg = array(
		'dir' => $dir = $box['tabs'][0]['fields']['docstore_dir']['current_value'],
		'basename' => $dir? htmlspecialchars(basename($dir)) : '');
	
	if (!$dir) {
		$box['tabs'][0]['fields']['docstore_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['docstore_dir_status']['snippet']['html'] = adminPhrase('Please enter a directory.');
	
	} elseif (!@is_dir($dir)) {
		$box['tabs'][0]['fields']['docstore_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['docstore_dir_status']['snippet']['html'] = adminPhrase('The directory &quot;[[basename]]&quot; does not exist.', $mrg);
	
	} elseif (false !== chopPrefixOffOfString(realpath($dir), realpath(CMS_ROOT))) {
		$box['tabs'][0]['fields']['docstore_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['docstore_dir_status']['snippet']['html'] = adminPhrase('Zenario is installed this directory. Please choose a different directory.', $mrg);
	
	} elseif (!directoryIsWritable($dir)) {
		$box['tabs'][0]['fields']['docstore_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['docstore_dir_status']['snippet']['html'] = adminPhrase('The directory &quot;[[basename]]&quot; is not writable.', $mrg);
	
	} else {
		$box['tabs'][0]['fields']['dir_2']['row_class'] = 'sub_section_valid';
		$box['tabs'][0]['fields']['docstore_dir_status']['row_class'] = 'sub_valid';
		$box['tabs'][0]['fields']['docstore_dir_status']['snippet']['html'] = adminPhrase('The directory &quot;[[basename]]&quot; exists and is writable.', $mrg);
	}
	
	
	//Check to see if the templates & grid templates directories exist,
	//and that the grid templates directory and all of the files inside are writable.
	//(A site setting can be set to stop this check.)
	$mrg = array(
		'dir' => $dir = $tdir,
		'basename' => $dir? htmlspecialchars(basename($dir)) : '');
	
	if (!is_dir($tdir)) {
		$box['tabs'][0]['fields']['template_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['template_dir_status']['snippet']['html'] = adminPhrase('The directory &quot;[[basename]]&quot; does not exist.', $mrg);
	
	} elseif (!directoryIsWritable($tdir)) {
		$box['tabs'][0]['fields']['template_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['template_dir_status']['snippet']['html'] = adminPhrase('The directory &quot;[[basename]]&quot; is not writable.', $mrg);

	} else {
		$fileWritable = false;
		$fileNotWritable = false;
		foreach (scandir($tdir) as $sdir) {
			if (is_file($tdir. '/'. $sdir)) {
				if (is_writable($tdir. '/'. $sdir)) {
					$fileWritable = true;
				} else {
					if ($fileNotWritable === false) {
						$fileNotWritable = $tdir. '/'. $sdir;
					} else {
						$fileNotWritable = true;
					}
				}
			}
		}
		
		if ($fileNotWritable === true) {
			if ($fileWritable) {
				$box['tabs'][0]['fields']['template_dir_status']['row_class'] = 'sub_invalid';
				$box['tabs'][0]['fields']['template_dir_status']['snippet']['html'] = adminPhrase('Some of the files in the &quot;[[basename]]&quot; directory are not writable by the web server (e.g. use &quot;chmod 666 *.tpl.php *.css&quot;).', $mrg);
			} else {
				$box['tabs'][0]['fields']['template_dir_status']['row_class'] = 'sub_invalid';
				$box['tabs'][0]['fields']['template_dir_status']['snippet']['html'] = adminPhrase('The files in the &quot;[[basename]]&quot; directory are not writable by the web server, please make them writable (e.g. use &quot;chmod 666 *.tpl.php *.css&quot;).', $mrg);
			}
		
		} elseif ($fileNotWritable !== false) {
			$box['tabs'][0]['fields']['template_dir_status']['row_class'] = 'sub_invalid';
			$box['tabs'][0]['fields']['template_dir_status']['snippet']['html'] =
				adminPhrase('"[[file]]" is not writable, please make it writable (e.g. use &quot;chmod 666 [[file]]&quot;).',
					array('file' => str_replace('/', '<wbr>/', htmlspecialchars($fileNotWritable))));
		
		} else {
			$box['tabs'][0]['fields']['template_dir_status']['row_class'] = 'sub_valid';
			$box['tabs'][0]['fields']['template_dir_status']['snippet']['html'] = adminPhrase('The directory &quot;[[basename]]&quot; exists and is writable.', $mrg);
		}
	}
	
	//Loop through all of the skins in the system (max 9) and check their editable_css directories
	$i = 0;
	$maxI = 9;
	$skinDirsValid = true;
	foreach (getRowsArray(
		'skins',
		array('family_name', 'name', 'display_name'),
		array('missing' => 0, 'family_name' => 'grid_templates', 'enable_editable_css' => 1)
	) as $skin) {
		if ($i == $maxI) {
			break;
		} else {
			++$i;
		}
		
		$skinWritableDir = CMS_ROOT. getSkinPath($skin['family_name'], $skin['name']). 'editable_css/';
		
		$box['tabs'][0]['fields']['skin_dir_'. $i]['value'] =
		$box['tabs'][0]['fields']['skin_dir_'. $i]['current_value'] = $skinWritableDir;
		
		$mrg = array(
			'dir' => $dir = $tdir,
			'basename' => $dir? htmlspecialchars(basename($skinWritableDir)) : '');
		
		if (!is_dir($skinWritableDir)) {
			$skinDirsValid = false;
			$box['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_warning';
			$box['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] = adminPhrase('The directory &quot;[[basename]]&quot; does not exist.', $mrg);
	
		} elseif (!directoryIsWritable($skinWritableDir)) {
			$skinDirsValid = false;
			$box['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_warning';
			$box['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] = adminPhrase('The directory &quot;[[basename]]&quot; is not writable.', $mrg);

		} else {
			$fileWritable = false;
			$fileNotWritable = false;
			foreach (scandir($skinWritableDir) as $sdir) {
				if (is_file($skinWritableDir. '/'. $sdir)) {
					if (is_writable($skinWritableDir. '/'. $sdir)) {
						$fileWritable = true;
					} else {
						if ($fileNotWritable === false) {
							$fileNotWritable = $skinWritableDir. '/'. $sdir;
						} else {
							$fileNotWritable = true;
						}
					}
				}
			}
		
			if ($fileNotWritable === true) {
				if ($fileWritable) {
					$skinDirsValid = false;
					$box['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_warning';
					$box['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] = adminPhrase('Some of the files in the &quot;[[basename]]&quot; directory are not writable by the web server (e.g. use &quot;chmod 666 *.css&quot;).', $mrg);
				} else {
					$skinDirsValid = false;
					$box['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_warning';
					$box['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] = adminPhrase('The files in the &quot;[[basename]]&quot; directory are not writable by the web server, please make them writable (e.g. use &quot;chmod 666 *.css&quot;).', $mrg);
				}
		
			} elseif ($fileNotWritable !== false) {
				$skinDirsValid = false;
				$box['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_warning';
				$box['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] =
					adminPhrase('"[[file]]" is not writable, please make it writable (e.g. use &quot;chmod 666 [[file]]&quot;).',
						array('file' => str_replace('/', '<wbr>/', htmlspecialchars($fileNotWritable))));
		
			} else {
				$box['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_valid';
				$box['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] = adminPhrase('The directory &quot;[[basename]]&quot; exists and is writable.', $mrg);
			}
		}
	}
	
	$box['tabs'][0]['fields']['dir_4']['hidden'] =
	$box['tabs'][0]['fields']['show_dir_4']['hidden'] =
	$box['tabs'][0]['fields']['dir_4_blurb']['hidden'] = $i == 0;
	
	for ($j = 0; $j <= $maxI; ++$j) {
		$box['tabs'][0]['fields']['skin_dir_'. $j]['hidden'] =
		$box['tabs'][0]['fields']['skin_dir_status_'. $j]['hidden'] = $j > $i;
	}
	
	if ($i == 1) {
		$box['tabs'][0]['fields']['dir_4_blurb']['snippet']['html'] =
			adminPhrase('CSS for plugins may be edited by an administrator, and Zenario writes CSS files to the following directory. Please ensure it exists and is writable by the web server:');
	} elseif ($i > 1) {
		$box['tabs'][0]['fields']['dir_4_blurb']['snippet']['html'] =
			adminPhrase('CSS for plugins may be edited by an administrator, and Zenario writes CSS files to the following directories. Please ensure they exist and are writable by the web server:');
	}
	
	
	if (!is_dir(CMS_ROOT. 'cache')) {
		$box['tabs'][0]['fields']['cache_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['cache_dir_status']['snippet']['html'] =
			adminPhrase('The &quot;cache&quot; directory does not exist.');
	
	} elseif (!directoryIsWritable(CMS_ROOT. 'cache')) {
		$box['tabs'][0]['fields']['cache_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['cache_dir_status']['snippet']['html'] =
			adminPhrase('The &quot;cache&quot; directory is not writable.');
	
	} else {
		$box['tabs'][0]['fields']['dir_5']['row_class'] = 'sub_section_valid';
		$box['tabs'][0]['fields']['cache_dir_status']['row_class'] = 'sub_valid';
		$box['tabs'][0]['fields']['cache_dir_status']['snippet']['html'] =
			adminPhrase('The &quot;cache&quot; directory exists and is writable.');
	}
	
	if (!is_dir(CMS_ROOT. 'private')) {
		$box['tabs'][0]['fields']['private_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['private_dir_status']['snippet']['html'] =
			adminPhrase('The &quot;private&quot; directory does not exist.');
	
	} elseif (!directoryIsWritable(CMS_ROOT. 'private')) {
		$box['tabs'][0]['fields']['private_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['private_dir_status']['snippet']['html'] =
			adminPhrase('The &quot;private&quot; directory is not writable.');
	
	} else {
		$box['tabs'][0]['fields']['dir_6']['row_class'] = 'sub_section_valid';
		$box['tabs'][0]['fields']['private_dir_status']['row_class'] = 'sub_valid';
		$box['tabs'][0]['fields']['private_dir_status']['snippet']['html'] =
			adminPhrase('The &quot;private&quot; directory exists and is writable.');
	}
	
	if (!is_dir(CMS_ROOT. 'public')) {
		$box['tabs'][0]['fields']['public_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['public_dir_status']['snippet']['html'] =
			adminPhrase('The &quot;public&quot; directory does not exist.');
	
	} elseif (!directoryIsWritable(CMS_ROOT. 'public')) {
		$box['tabs'][0]['fields']['public_dir_status']['row_class'] = 'sub_invalid';
		$box['tabs'][0]['fields']['public_dir_status']['snippet']['html'] =
			adminPhrase('The &quot;public&quot; directory is not writable.');
	
	} else {
		$box['tabs'][0]['fields']['dir_7']['row_class'] = 'sub_section_valid';
		$box['tabs'][0]['fields']['public_dir_status']['row_class'] = 'sub_valid';
		$box['tabs'][0]['fields']['public_dir_status']['snippet']['html'] =
			adminPhrase('The &quot;public&quot; directory exists and is writable.');
	}
	
	
	if ($box['tabs'][0]['fields']['backup_dir_status']['row_class'] == 'sub_invalid') {
		$showCheckAgainButtonIfDirsAreEditable =
		$box['tabs'][0]['fields']['show_dirs']['pressed'] =
		$box['tabs'][0]['fields']['show_dir_1']['pressed'] = true;
		$box['tabs'][0]['fields']['dirs']['row_class'] = 'section_invalid';
		$box['tabs'][0]['fields']['dir_1']['row_class'] = 'sub_section_invalid';
	}
	
	if ($box['tabs'][0]['fields']['docstore_dir_status']['row_class'] == 'sub_invalid') {
		$showCheckAgainButtonIfDirsAreEditable =
		$box['tabs'][0]['fields']['show_dirs']['pressed'] =
		$box['tabs'][0]['fields']['show_dir_2']['pressed'] = true;
		$box['tabs'][0]['fields']['dirs']['row_class'] = 'section_invalid';
		$box['tabs'][0]['fields']['dir_2']['row_class'] = 'sub_section_invalid';
	}
	
	if ($box['tabs'][0]['fields']['template_dir_status']['row_class'] == 'sub_invalid') {
		$showCheckAgainButton =
		$box['tabs'][0]['fields']['show_dirs']['pressed'] =
		$box['tabs'][0]['fields']['show_dir_3']['pressed'] = true;
		$box['tabs'][0]['fields']['dirs']['row_class'] = 'section_invalid';
		$box['tabs'][0]['fields']['dir_3']['row_class'] = 'sub_section_invalid';
	} else {
		$box['tabs'][0]['fields']['dir_3']['row_class'] = 'sub_section_valid';
	}
	
	if ($box['tabs'][0]['fields']['cache_dir_status']['row_class'] == 'sub_invalid') {
		$showCheckAgainButton =
		$box['tabs'][0]['fields']['show_dirs']['pressed'] =
		$box['tabs'][0]['fields']['show_dir_5']['pressed'] = true;
		$box['tabs'][0]['fields']['dirs']['row_class'] = 'section_invalid';
		$box['tabs'][0]['fields']['dir_5']['row_class'] = 'sub_section_invalid';
	}
	
	if ($box['tabs'][0]['fields']['private_dir_status']['row_class'] == 'sub_invalid') {
		$showCheckAgainButton =
		$box['tabs'][0]['fields']['show_dirs']['pressed'] =
		$box['tabs'][0]['fields']['show_dir_6']['pressed'] = true;
		$box['tabs'][0]['fields']['dirs']['row_class'] = 'section_invalid';
		$box['tabs'][0]['fields']['dir_6']['row_class'] = 'sub_section_invalid';
	}
	
	if ($box['tabs'][0]['fields']['public_dir_status']['row_class'] == 'sub_invalid') {
		$showCheckAgainButton =
		$box['tabs'][0]['fields']['show_dirs']['pressed'] =
		$box['tabs'][0]['fields']['show_dir_7']['pressed'] = true;
		$box['tabs'][0]['fields']['dirs']['row_class'] = 'section_invalid';
		$box['tabs'][0]['fields']['dir_7']['row_class'] = 'sub_section_invalid';
	}
	
	if (!$skinDirsValid) {
		$showCheckAgainButton =
		$box['tabs'][0]['fields']['show_dirs']['pressed'] =
		$box['tabs'][0]['fields']['show_dir_4']['pressed'] = true;
		if ($box['tabs'][0]['fields']['dirs']['row_class'] != 'section_invalid') {
			$box['tabs'][0]['fields']['dirs']['row_class'] = 'section_warning';
		}
		$box['tabs'][0]['fields']['dir_4']['row_class'] = 'sub_section_warning';
	} else {
		$box['tabs'][0]['fields']['dir_4']['row_class'] = 'sub_section_valid';
	}
	
	$box['tabs'][0]['fields']['site']['row_class'] = 'section_valid';
	
	//Don't show the "site" section yet if we've just finished an install
	if ($freshInstall || !checkRowExists('languages', array())) {
		foreach ($box['tabs'][0]['fields'] as $fieldName => &$field) {
			if (!empty($field['hide_on_install'])) {
				$field['hidden'] = true;
			}
		}
	
	} else {
		
		$show_warning = false;
		
		if (!setting('site_enabled')) {
			$show_warning = true;
			$box['tabs'][0]['fields']['site_disabled']['row_class'] = 'warning';
			$box['tabs'][0]['fields']['site_disabled']['snippet']['html'] =
				adminPhrase('Your site is not enabled, so visitors will not be able to see it. Go to <em>Configuration -&gt; Site settings -&gt; Site Disabled</em> in Organizer, then click on the &quot;Enable or Disable this Site&quot; button to enable your site.');
		} else {
			$box['tabs'][0]['fields']['site_disabled']['row_class'] = 'valid';
			$box['tabs'][0]['fields']['site_disabled']['snippet']['html'] = adminPhrase('Your site is enabled.');
		}
		
		$sql = "
			SELECT 1
			FROM ". DB_NAME_PREFIX. "special_pages AS sp
			INNER JOIN ". DB_NAME_PREFIX. "content_items AS c
			   ON c.id = sp.equiv_id
			  AND c.type = sp.content_type
			WHERE c.status NOT IN ('published_with_draft','published')";
		
		if (($result = sqlQuery($sql)) && (sqlFetchRow($result))) {
			$show_warning = true;
			$box['tabs'][0]['fields']['site_special_pages_unpublished']['row_class'] = 'warning';
			$box['tabs'][0]['fields']['site_special_pages_unpublished']['snippet']['html'] =
				setting('site_enabled')?
					adminPhrase("Zenario identifies some web pages as &quot;special pages&quot; to perform Not Found, Login and other functions. Some of these pages are not published, so visitors may not be able to access some important functions.")
				:	adminPhrase("Zenario identifies some web pages as &quot;special pages&quot; to perform Not Found, Login and other functions. Some of these pages are not published. Before enabling your site, please remember to publish them.");
		
		} else {
			$box['tabs'][0]['fields']['site_special_pages_unpublished']['row_class'] = 'valid';
			$box['tabs'][0]['fields']['site_special_pages_unpublished']['snippet']['html'] = adminPhrase("All of your site's Special Pages are published.");
		}
		
		
		if (!setting('check_automated_backups')) {
			$box['tabs'][0]['fields']['site_automated_backups']['row_class'] = 'valid';
			$box['tabs'][0]['fields']['site_automated_backups']['hidden'] = true;
		
		} elseif (!setting('automated_backup_log_path')) {
			$show_warning = true;
			$box['tabs'][0]['fields']['site_automated_backups']['row_class'] = 'warning';
			$box['tabs'][0]['fields']['site_automated_backups']['snippet']['html'] =
				adminPhrase('Automated backup monitoring is not set up. Please go to <a href="[[link]]" target="_blank"><em>Backups and document storage</em> in the site settings</a> to change this.',
					array('link' => 'organizer.php#zenario__administration/panels/site_settings//dirs'));
		
		} elseif (!is_file(setting('automated_backup_log_path'))) {
			$show_warning = true;
			$box['tabs'][0]['fields']['site_automated_backups']['row_class'] = 'warning';
			$box['tabs'][0]['fields']['site_automated_backups']['snippet']['html'] =
				adminPhrase("Automated backup monitoring is not running properly: the log file ([[path]]) could not be found.",
					array('path' => setting('automated_backup_log_path')));
		
		} elseif (!is_readable(setting('automated_backup_log_path'))) {
			$show_warning = true;
			$box['tabs'][0]['fields']['site_automated_backups']['row_class'] = 'warning';
			$box['tabs'][0]['fields']['site_automated_backups']['snippet']['html'] =
				adminPhrase("Automated backup monitoring is not running properly: the log file ([[path]]) exists but could not be read.",
					array('path' => setting('automated_backup_log_path')));
		
		} else {
			//Attempt to get the date of the last backup from
			$timestamp = 0;
			ini_set('auto_detect_line_endings', true);
			if ($f = fopen(setting('automated_backup_log_path'), 'r')) {
				while ($line = fgets($f)) {
					if (trim($line) != ''
					 && ($lineValues = str_getcsv($line))
					 && ($lineValues[0])
					 && ($lineValues[2]? DBNAME == $lineValues[2] : DBNAME == $lineValues[1])
					 && ($time = new DateTime($lineValues[0]))) {
						$timestamp = max($timestamp, $time->getTimestamp());
					}
				}
			}
			
			if (!$timestamp) {
				$show_warning = true;
				$box['tabs'][0]['fields']['site_automated_backups']['row_class'] = 'warning';
				$box['tabs'][0]['fields']['site_automated_backups']['snippet']['html'] =
					adminPhrase("This site is not being backed-up using the automated process.");
			
			} else {
				$days = (int) floor((time() - (int) $timestamp) / 86400);
				
				if ($days >= (int) setting('automated_backup_days')) {
					$show_warning = true;
					$box['tabs'][0]['fields']['site_automated_backups']['row_class'] = 'warning';
					$box['tabs'][0]['fields']['site_automated_backups']['snippet']['html'] =
						nAdminPhrase("Automated backups have not been run in the last day.",
							"Automated backups have not been run in [[days]] days.",
							$days, array('days' => $days));
		
				} else {
					$box['tabs'][0]['fields']['site_automated_backups']['row_class'] = 'valid';
					$box['tabs'][0]['fields']['site_automated_backups']['snippet']['html'] =
						adminPhrase("Automated backups are running.");
				}
			}
		}
		
		
		//Check for a missing site description file.
		if (!is_file(CMS_ROOT. 'zenario_custom/site_description.yaml')) {
			$show_warning = true;
			$box['tabs'][0]['fields']['site_description_missing']['row_class'] = 'warning';
		} else {
			//Note: only show this message if it's in the error state; hide it otherwise
			$box['tabs'][0]['fields']['site_description_missing']['hidden'] = true;
		}
		
		//Check to see if there are spare domains without a primary domain
		if (!setting('primary_domain') && checkRowExists('spare_domain_names', array())) {
			$show_warning = true;
			$box['tabs'][0]['fields']['spare_domains_without_primary_domain']['row_class'] = 'warning';
		} else {
			//Note: only show this message if it's in the error state; hide it otherwise
			$box['tabs'][0]['fields']['spare_domains_without_primary_domain']['hidden'] = true;
		}
		
		//Check to see if this is a developer installation...
		if (setting('site_mode') == 'development') {
			
			//Check to see if any developers are not showing errors/warnings
			if (!(ERROR_REPORTING_LEVEL & E_ALL)
			 || !(ERROR_REPORTING_LEVEL & E_NOTICE)
			 || !(ERROR_REPORTING_LEVEL & E_STRICT)) {
				$show_warning = true;
				$box['tabs'][0]['fields']['errors_not_shown']['row_class'] = 'warning';
			} else {
				//Note: only show this message if it's in the error state; hide it otherwise
				$box['tabs'][0]['fields']['errors_not_shown']['hidden'] = true;
			}
			
			////Check to see if any developers are missing the debug_override_enable setting
			//if (!setting('debug_override_enable')) {
			//	$show_warning = true;
			//	$box['tabs'][0]['fields']['email_addresses_not_overridden']['row_class'] = 'warning';
			//} else {
			//	//Note: only show this message if it's in the error state; hide it otherwise
			//	$box['tabs'][0]['fields']['email_addresses_not_overridden']['hidden'] = true;
			//}
		} else {
			//$box['tabs'][0]['fields']['email_addresses_not_overridden']['hidden'] = true;
			
			$box['tabs'][0]['fields']['errors_not_shown']['hidden'] = true;
		}
		
		//Check to see if production sites have the debug_override_enable setting enabled
		if (setting('debug_override_enable')) {
			$show_warning = true;
			$box['tabs'][0]['fields']['email_addresses_overridden']['row_class'] = 'warning';
			$box['tabs'][0]['fields']['email_addresses_overridden']['snippet']['html'] =
				adminPhrase('You have &ldquo;Email debug mode&rdquo; enabled under <em>Configuration -&gt; Site Settings-&gt; Email -&gt; Debug</em> in Organizer. Email sent by this site will be redirected to &quot;[[email]]&quot;.',
					array('email' => htmlspecialchars(setting('debug_override_email_address'))));
		} else {
			$box['tabs'][0]['fields']['email_addresses_overridden']['hidden'] = true;
		}
		
		$badSymlinks = array();
		if (is_dir($dir = CMS_ROOT. 'zenario_extra_modules/')) {
			foreach (scandir($dir) as $mDir) {
				if ($mDir != '.'
				 && $mDir != '..'
				 && is_link($dir. $mDir)
				 && ($rp = realpath($dir. $mDir))
				 && ($rp = dirname($rp))
				 && (is_dir($rp. '/zenario') || ($rp = dirname($rp)))
				 && (is_dir($rp. '/zenario') || ($rp = dirname($rp)))
				 && (is_dir($rp = $rp. '/zenario/admin/db_updates/copy_over_top_check/'))
				 && (!file_exists($rp. ZENARIO_MAJOR_VERSION. '.'. ZENARIO_MINOR_VERSION. '.txt'))) {
					
					$badSymlinks[] = $mDir;
				}
			}
		}
		
		if (!$box['tabs'][0]['fields']['bad_extra_module_symlinks']['hidden'] = empty($badSymlinks)) {
			$box['tabs'][0]['fields']['bad_extra_module_symlinks']['row_class'] = 'warning';
			$show_warning = true;
			
			$mrg = array('module' => array_pop($badSymlinks), 'version' => ZENARIO_MAJOR_VERSION. '.'. ZENARIO_MINOR_VERSION);
			if (empty($badSymlinks)) {
				$box['tabs'][0]['fields']['bad_extra_module_symlinks']['snippet']['html'] =
					adminPhrase('The <code>[[module]]</code> symlink in the <code>zenario_extra_modules/</code> directory is linked to the wrong version of Zenario. It should be linked to version [[version]].', $mrg);
			} else {
				$mrg['modules'] = implode('</code>, <code>', $badSymlinks);
				$box['tabs'][0]['fields']['bad_extra_module_symlinks']['snippet']['html'] =
					adminPhrase('The <code>[[modules]]</code> and <code>[[module]]</code> symlinks in the <code>zenario_extra_modules/</code> directory are linked to the wrong version of Zenario. They should be linked to version [[version]].', $mrg);
			}
		}
		
		
		if ($show_warning) {
			$box['tabs'][0]['fields']['show_site']['pressed'] = true;
			$box['tabs'][0]['fields']['site']['row_class'] = 'section_warning';
		}
	}
	
	
	
	
	//Web server warnings
	$show_warning = false;
	
	if (!$box['tabs'][0]['fields']['opcache_misconfigured']['hidden'] =
		!checkFunctionEnabled('ini_get')
	 || !engToBoolean(ini_get('opcache.enable'))
	 || engToBoolean(ini_get('opcache.dups_fix'))
	) {
		$box['tabs'][0]['fields']['opcache_misconfigured']['row_class'] = 'warning';
		$show_warning = true;
	}
	
	
	if ($show_warning) {
		$box['tabs'][0]['fields']['show_server']['pressed'] = true;
		$box['tabs'][0]['fields']['server']['row_class'] = 'section_warning';
		$box['tabs'][0]['fields']['show_server']['hidden'] =
		$box['tabs'][0]['fields']['server']['hidden'] = false;
	} else {
		$box['tabs'][0]['fields']['server']['row_class'] = 'section_valid';
		$box['tabs'][0]['fields']['show_server']['hidden'] =
		$box['tabs'][0]['fields']['server']['hidden'] = true;
	}
	
	
	
	
	//Strip any trailing slashes off of a directory path
	$box['tabs'][0]['fields']['backup_dir']['current_value'] = preg_replace('/[\\\\\\/]+$/', '', $box['tabs'][0]['fields']['backup_dir']['current_value']);
	$box['tabs'][0]['fields']['docstore_dir']['current_value'] = preg_replace('/[\\\\\\/]+$/', '', $box['tabs'][0]['fields']['docstore_dir']['current_value']);
	
	
	//On multisite sites, don't allow local Admins to change the directory paths
	if (globalDBDefined() && !session('admin_global_id')) {
		$box['disallow_changes_to_dirs'] = true;
	
	//Only allow changes to the directories if they were not correctly set to start with
	} elseif (!isset($box['disallow_changes_to_dirs'])) {
		$box['disallow_changes_to_dirs'] =
			$box['tabs'][0]['fields']['backup_dir_status']['row_class'] == 'sub_valid'
		 && $box['tabs'][0]['fields']['docstore_dir_status']['row_class'] == 'sub_valid';
	}
	
	if ($box['disallow_changes_to_dirs']) {
		$box['tabs'][0]['fields']['backup_dir']['read_only'] = true;
		$box['tabs'][0]['fields']['docstore_dir']['read_only'] = true;
	
	} else {
		if ($box['tabs'][0]['fields']['backup_dir_status']['row_class'] == 'sub_valid') {
			setSetting('backup_dir', $box['tabs'][0]['fields']['backup_dir']['current_value']);
		}
		
		if ($box['tabs'][0]['fields']['docstore_dir_status']['row_class'] == 'sub_valid') {
			setSetting('docstore_dir', $box['tabs'][0]['fields']['docstore_dir']['current_value']);
		}
	}
	
	
	//If all of the directory info valid (or uneditable due to not being a super-admin),
	//only show one button as there is nothing to save or recheck
	$box['tabs'][0]['fields']['check_again']['hidden'] =
		!$showCheckAgainButton
	 && (!$showCheckAgainButtonIfDirsAreEditable || $box['disallow_changes_to_dirs']);
	
	
	//If everything is valid, do not show this screen unless it was shown previously
	if ($box['tabs'][0]['fields']['dirs']['row_class'] == 'section_valid'
	 && $box['tabs'][0]['fields']['site']['row_class'] == 'section_valid'
	 && $box['tabs'][0]['fields']['server']['row_class'] == 'section_valid'
	 && empty($box['tabs'][0]['fields']['check_again']['pressed'])) {
	 	unset($box['disallow_changes_to_dirs']);
		return true;
	
	//Continue on from this step if the "Continue" button is pressed
	} elseif (!empty($box['tabs'][0]['fields']['continue']['pressed'])) {
		unset($box['disallow_changes_to_dirs']);
		return true;
	
	} else {
		//If the Admin has pressed the "Save and Continue" button...
		if (!empty($box['tabs'][0]['fields']['check_again']['pressed'])) {
			//If the directory section is valid, also don't show this screen unless it was shown previously
			if ($box['tabs'][0]['fields']['dirs']['row_class'] == 'section_valid'
			 && empty($box['tabs'][0]['fields']['check_again']['pressed'])) {
				unset($box['disallow_changes_to_dirs']);
				return true;
			
			//Otherwise shake the screen
			} else {
				$box['shake'] = true;
			}
		}
		
		return false;
	}
}

function protectBackupAndDocstoreDirsIfPossible() {
	foreach (array(setting('backup_dir'), setting('docstore_dir')) as $dir) {
		if ($dir
		 && is_dir($dir)
		 && is_writeable($dir)
		 && !file_exists($file = $dir. '/.htaccess')) {
			@file_put_contents($file, 
'Options -Indexes

<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteRule .* - [F,NC]
</IfModule>'
			);
		}
	}
}

function licenseExpiryDate($file) {
	$return = false;
	
	if ($f = fopen($file, 'r')) {
		if (($expires = trim(fgets($f)))
		 && ($expires = explode('Expires: ', $expires, 2))
		 && (!empty($expires[1]))) {
			$return = $expires[1];
		}
		fclose($f);
	}
	
	return $return;
}

function congratulationsAJAX(&$tags, &$box) {
	$box['tabs'][0]['fields']['link']['snippet']['html'] =
		'<a href="'. httpOrhttps(). $_SERVER['HTTP_HOST']. SUBDIRECTORY. '">'. httpOrhttps(). $_SERVER['HTTP_HOST']. SUBDIRECTORY. '</a>';
}




function redirectAdmin($getRequest, $useAliasInAdminMode = false) {
	$cID = $cType = false;
	$request = arrayKey($getRequest, 'cID');
	
	$domain = ($useAliasInAdminMode || !checkPriv())? primaryDomain() : adminDomain();
	
	if (!empty($getRequest['og']) && checkPriv()) {
		return
			'zenario/admin/organizer.php'.
			(isset($getRequest['fromCID']) && isset($getRequest['fromCType'])? '?fromCID='. $getRequest['fromCID']. '&fromCType='. $getRequest['fromCType'] : '').
			'#'. $getRequest['og'];
		
	} elseif (!empty($getRequest['desturl']) && checkPriv()) {
		return httpOrhttps(). $domain. $getRequest['desturl'];
		
	} elseif ($cID = (int) $request) {
		$cType = ifNull(preg_replace('/\W/', '', arrayKey($getRequest, 'cType')), 'html');
	
	} elseif (getCIDAndCTypeFromTagId($cID, $cType, $request)) {
		$cType = ifNull(preg_replace('/\W/', '', $cType), 'html');
	
	} elseif ($request && ($content = getRow('content_items', array('id', 'type'), array('alias' => $request)))) {
		$cID = $content['id'];
		$cType = $content['type'];
	
	} elseif ($cID = session('destCID')) {
		$cType = session('destCType');
	}
	
	if ($cID && checkPerm($cID, $cType)) {
		
		unset($getRequest['task']);
		unset($getRequest['cID']);
		unset($getRequest['cType']);
		unset($getRequest['cVersion']);
		
		return linkToItem($cID, $cType, true, http_build_query($getRequest), false, false, $useAliasInAdminMode);
	} else {
		return ifNull(DIRECTORY_INDEX_FILENAME, SUBDIRECTORY);
	}
}





function deleteNamedPluginSetting($moduleClassName, $settingName) {
	
	$sql = "
		DELETE ps.*
		FROM `". DB_NAME_PREFIX. "modules` AS m
		INNER JOIN `". DB_NAME_PREFIX. "plugin_instances` AS pi
		   ON m.id = pi.module_id
		INNER JOIN `". DB_NAME_PREFIX. "plugin_settings` AS ps
		   ON pi.id = ps.instance_id
		  AND ps.nest = 0
		  AND ps.name = '". sqlEscape($settingName). "'
		WHERE m.class_name = '". sqlEscape($moduleClassName). "'";
	sqlUpdate($sql);

	$sql = "
		DELETE ps.*
		FROM `". DB_NAME_PREFIX. "modules` AS m
		INNER JOIN `". DB_NAME_PREFIX. "nested_plugins` AS np
		   ON m.id = np.module_id
		INNER JOIN `". DB_NAME_PREFIX. "plugin_settings` AS ps
		   ON ps.nest = np.id
		  AND ps.name = '". sqlEscape($settingName). "'
		WHERE m.class_name = '". sqlEscape($moduleClassName). "'";
	sqlUpdate($sql);
}
