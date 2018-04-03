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

class welcome {


	//Formerly "directoryIsWritable()"
	public static function directoryIsWritable($dir) {
	
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



	//Formerly "quickValidateWelcomePage()"
	public static function quickValidateWelcomePage(&$values, &$rowClasses, &$snippets, $tab) {
		if ($tab == 5 || $tab == 'change_password' || $tab == 'new_admin') {
			$strength = \ze\user::passwordValuesToStrengths(\ze\user::checkPasswordStrength($values['password'], true));
			$snippets['password_strength'] = '<div class="password_'. $strength. '"><span>'. \ze\admin::phrase($strength). '</span></div>';
	
		} elseif ($tab == 6) {
			if (is_file($values['path'])) {
				if (substr($values['path'], -4) != '.sql'
				 && substr($values['path'], -7) != '.sql.gz'
				 && substr($values['path'], -14) != '.sql.encrypted'
				 && substr($values['path'], -17) != '.sql.gz.encrypted') {
					$snippets['path_status'] = \ze\admin::phrase('Please select a backup file.');
				} elseif (is_readable($values['path'])) {
					$snippets['path_status'] = \ze\admin::phrase('File exists.');
				} else {
					$snippets['path_status'] = \ze\admin::phrase('File exists but cannot be read.');
				}
			} elseif (@is_dir($values['path'])) {
				$snippets['path_status'] = \ze\admin::phrase('Directory exists, please enter a filename.');
			} else {
				$snippets['path_status'] = \ze\admin::phrase('No file or directory with that name.');
			}
		}

	}




	//This file includes common functionality for running SQL scripts

	//Formerly "runSQL()"
	public static function runSQL($prefix = false, $file, &$error, $patterns = false, $replacements = false) {
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
			$from = ["\r", '[[DB_NAME_PREFIX]]',	'[[LATEST_REVISION_NO]]',	'[[INSTALLER_REVISION_NO]]',	'[[THEME]]'];
			$to =	['',	DB_NAME_PREFIX,			LATEST_REVISION_NO,			INSTALLER_REVISION_NO,			INSTALLER_DEFAULT_THEME];
		} else {
			$from = ["\r"];
			$to = [''];
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
					$to[] = \ze\escape::sql($replacement);
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
		
			if (!$result = \ze\sql::select($query)) {
				$errno = \ze\sql::errno();
				$error = \ze\sql::error();
				$error = '(Error '. $errno. ': '. $error. "). \n\n". $query. "\nFile: ". $file;
				return false;
			}
		}
	
		return true;
	}


	//Formerly "readSampleConfigFile()"
	public static function readSampleConfigFile($patterns) {
		$searches = $replaces = [];
		foreach ($patterns as $pattern => $value) {
			$searches[] = '[['. $pattern. ']]';
			$replaces[] = $value;
		}
	
		return str_replace($searches, $replaces, file_get_contents(CMS_ROOT. 'zenario/admin/db_install/zenario_siteconfig.sample.php'));
	}

	//Check whether the config file exists.
	//Note: if it doesn't exist, return false
	//Note: if exists but is empty, return 0
	//Formerly "checkConfigFileExists()"
	public static function checkConfigFileExists() {
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

	//Formerly "compareVersionNumber()"
	public static function compareVersionNumber($actual, $required) {
		return version_compare(preg_replace('@[^\d\.]@', '', $actual), $required, '>=');
	}

	//Formerly "getSVNInfo()"
	public static function svnInfo() {
		if (!\ze\server::isWindows() && \ze\server::execEnabled()) {
			$output = [];
			$svninfo = [];
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


	//Formerly "installerReportError()"
	public static function installerReportError() {
		return "\n". \ze\admin::phrase('(Error [[errno]]: [[error]])', ['errno' => \ze\sql::errno(), 'error' => \ze\sql::error()]);
	}
	

	//Formerly "refreshAdminSession()"
	public static function refreshAdminSession() {
		if (\ze\admin::id()) {
			\ze\admin::setSession(\ze\admin::id(), ($_SESSION['admin_global_id'] ?? false));
		}
	}

	//Formerly "prepareAdminWelcomeScreen()"
	public static function prepareAdminWelcomeScreen($path, &$source, &$tags, &$fields, &$values, &$changes) {
	
		$resetErrors = true;
	
		//If this is the first time we're displaying something,
		//or we were displaying something different before and have now just switched paths,
		//then wipe any previous client tags and initialise every tag as they are defined in the .yaml files.
		if (($tags['path'] ?? false) != $path) {
			$filling = true;
		
			$tags = $source[$path];
			$tags['path'] = $path;
	
		//If we're re-displaying the same form, merge the tags on the client with the definitions from the .yaml files.
		} else {
			$filling = false;
		
			$clientTags = $tags;
			$tags = $source[$path];
			\ze\tuix::syncFromClientToServer($tags, $clientTags);
			$tags['path'] = $path;
		}
	

		$fields = [];
		$values = [];
		$changes = [];
		\ze\tuix::readValues($tags, $fields, $values, $changes, $filling, $resetErrors);
	}


	//Old code for sample sites, commented out as we don't currently use them
	//function listSampleSites() {
	//	$sampleSites = [];
	//	
	//	foreach (\ze::moduleDirs('sample_installations/') as $mdir) {
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

	//Formerly "listSampleThemes()"
	public static function listSampleThemes() {
		$themes = [];
		$sDir = \ze\content::templatePath(). 'grid_templates/skins/';
	
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


	//Formerly "systemRequirementsAJAX()"
	public static function systemRequirementsAJAX(&$source, &$tags, &$fields, &$values, $changes, $isDiagnosticsPage = false) {
	
		if ($isDiagnosticsPage) {
			$valid = 'sub_valid';
			$warning = 'sub_warning';
			$invalid = 'sub_invalid';
			$section_valid = 'sub_section_valid';
			$section_warning = 'sub_section_warning';
			$section_invalid = 'sub_section_invalid';
		} else {
			$valid = 'valid';
			$warning = 'warning';
			$invalid = 'invalid';
			$section_valid = 'section_valid';
			$section_warning = 'section_warning';
			$section_invalid = 'section_invalid';
		}
	
	
	
		//Check if the server meets the requirements
		//Get the server phpinfo
		//(Note this section of code may not work properly if compression has been enabled)
		$search = [];
		$replace = [];
	
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
	
		$phpinfo = [];
		$sectionName = 'PHP';
		foreach (explode('~', $info) as $i => $section) {
			if ($i % 2) {
				$phpinfo[$sectionName = $section] = [];
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
		unset($info, $search, $replace);
	
	
		//Check whether we're using Apache, and if so, which version?
		$apacheVer = [];
		$isApache = preg_match('@Apache(|/?[\d\.]+)@i', $_SERVER['SERVER_SOFTWARE'], $apacheVer);
		
		//PHP doesn't always get given the version number, try calling apache2 directly as a fallback
		if ($isApache && empty($apacheVer[1]) && \ze\server::execEnabled()) {
			exec('apache2 -v', $apacheVer[1]);
			
			if (is_array($apacheVer[1])) {
				$apacheVer[1] = implode(' ', $apacheVer[1]);
			}
		}
		
		//Show a warning if not running Apache.
		//If we could get the version number above, also check it's 2.4.7 or later
		if (!$isApache
		 || ($apacheVer[1] && !\ze\welcome::compareVersionNumber($apacheVer[1], '2.4.7'))) {
			$fields['0/server_1']['row_class'] = $warning;
			$apacheRecommendationMet = false;
	
		} else {
			$fields['0/server_1']['row_class'] = $valid;
			$apacheRecommendationMet = true;
		}
		
		if (empty($apacheVer[1])) {
			$fields['0/server_1']['post_field_html'] =
				\ze\admin::phrase('&nbsp;(<em>you have [[software]]</em>)', ['software' => htmlspecialchars($_SERVER['SERVER_SOFTWARE'])]);
		} else {
			$fields['0/server_1']['post_field_html'] =
				\ze\admin::phrase('&nbsp;(<em>you have version [[version]]</em>)', ['version' => htmlspecialchars($apacheVer[1])]);
		}
	
	
		$phpRequirementsMet = false;
		$phpVersion = phpversion();
		$fields['0/php_1']['post_field_html'] =
			\ze\admin::phrase('&nbsp;(<em>you have version [[version]]</em>)', ['version' => htmlspecialchars($phpVersion)]);
	
		if (!\ze\welcome::compareVersionNumber($phpVersion, '7.0.0')) {
			$fields['0/php_1']['row_class'] = $invalid;
	
		} else {
			$fields['0/php_1']['row_class'] = $valid;
			$phpRequirementsMet = true;
		}
	
		$phpWarning = false;
		if (!$fields['0/opcache_misconfigured']['hidden'] =
			\ze\welcome::compareVersionNumber(phpversion(), '7.0.0')
		 || !\ze\server::checkFunctionEnabled('ini_get')
		 || !\ze\ring::engToBoolean(ini_get('opcache.enable'))
		 || \ze\ring::engToBoolean(ini_get('opcache.dups_fix'))
		) {
			$fields['0/opcache_misconfigured']['row_class'] = $warning;
			$phpWarning = true;
		}
	
	
		$mysqlRequirementsMet = false;
		if (!extension_loaded('mysqli')) {
			$fields['0/mysql_1']['row_class'] = $invalid;
			$fields['0/mysql_2']['row_class'] = $invalid;
			$fields['0/mysql_2']['post_field_html'] = '';
	
		} else {
			$fields['0/mysql_1']['row_class'] = $valid;
		
			$mysqlVersion = \ze::ifNull(
				\ze\ray::value($phpinfo, 'mysql', 'Client API version'),
				\ze\ray::value($phpinfo, 'mysqli', 'Client API library version'));
		
			//Often the reported version of the client library can be behind the server version.
			//Try to check the server version if possible
			if (!\ze::setting('mysql_path')) {
				//If mysql isn't set up in the site settings, try to guess what the settings should be
				//and temporarily set it up noe
				if (\ze\server::programPathForExec('/usr/bin/', 'mysql', $checkExecutable = true)) {
					\ze\site::setSetting('mysql_path', '/usr/bin/', false, false, false);
	
				} elseif (\ze\server::programPathForExec('/usr/local/bin/', 'mysql', $checkExecutable = true)) {
					\ze\site::setSetting('mysql_path', '/usr/local/bin/', false, false, false);
				}
			}
			if (\ze::setting('mysql_path') && ($mysqlServerVersion = \ze\dbAdm::callMySQL(false, ' --version'))) {
				$mysqlServerVersion = \ze\ring::chopPrefix(\ze::setting('mysql_path'), $mysqlServerVersion, true);
				$matches = [];
				if ($matches = preg_split('@Distrib ([\d\.]+)@', $mysqlServerVersion, 2, PREG_SPLIT_DELIM_CAPTURE)) {
					if (!empty($matches[1])) {
						if (!\ze\welcome::compareVersionNumber($mysqlVersion, $matches[1])) {
							$mysqlVersion = $mysqlServerVersion;
						}
					}
				}
			}
		
		
			if ($mysqlVersion) {
				$fields['0/mysql_2']['post_field_html'] =
					\ze\admin::phrase('&nbsp;(<em>your client is version [[version]]</em>)', ['version' => htmlspecialchars($mysqlVersion)]);
			}
		
			if (!$mysqlVersion
			 || !\ze\welcome::compareVersionNumber($mysqlVersion, '5.5.3')) {
				$fields['0/mysql_2']['row_class'] = $invalid;
			} else {
				$fields['0/mysql_2']['row_class'] = $valid;
				$mysqlRequirementsMet = true;
			}
		}
	
	
		$mbRequirementsMet = true;
		if (!extension_loaded('ctype')) {
			$mbRequirementsMet = false;
			$fields['0/mb_1']['row_class'] = $invalid;
	
		} else {
			$fields['0/mb_1']['row_class'] = $valid;
		}
		if (!extension_loaded('mbstring')) {
			$mbRequirementsMet = false;
			$fields['0/mb_2']['row_class'] = $invalid;
	
		} else {
			$fields['0/mb_2']['row_class'] = $valid;
		}
	
	
		$gdRequirementsMet = true;
		if (\ze\ray::value($phpinfo, 'gd', 'GD Support') != 'enabled') {
			$gdRequirementsMet = false;
			$fields['0/gd_1']['row_class'] = $invalid;
			$fields['0/gd_2']['row_class'] = $invalid;
			$fields['0/gd_3']['row_class'] = $invalid;
			$fields['0/gd_4']['row_class'] = $invalid;
	
		} else {
			$fields['0/gd_1']['row_class'] = $valid;
		
			if (\ze\ray::value($phpinfo, 'gd', 'GIF Read Support') != 'enabled') {
				$gdRequirementsMet = false;
				$fields['0/gd_2']['row_class'] = $invalid;
			} else {
				$fields['0/gd_2']['row_class'] = $valid;
			}
		
			if (\ze\ray::value($phpinfo, 'gd', 'JPG Support') != 'enabled' && \ze\ray::value($phpinfo, 'gd', 'JPEG Support') != 'enabled') {
				$gdRequirementsMet = false;
				$fields['0/gd_3']['row_class'] = $invalid;
			} else {
				$fields['0/gd_3']['row_class'] = $valid;
			}
		
			if (\ze\ray::value($phpinfo, 'gd', 'PNG Support') != 'enabled') {
				$gdRequirementsMet = false;
				$fields['0/gd_4']['row_class'] = $invalid;
			} else {
				$fields['0/gd_4']['row_class'] = $valid;
			}
		}
	
	
		$optionalRequirementsMet = true;
		
		$fields['0/optional_mod_deflate']['row_class'] = $valid;
		$fields['0/optional_mod_expires']['row_class'] = $valid;
		$fields['0/optional_mod_rewrite']['row_class'] = $valid;
		
		if (!function_exists('apache_get_modules')) {
			$fields['0/optional_mod_deflate']['hidden'] = true;
			$fields['0/optional_mod_expires']['hidden'] = true;
			$fields['0/optional_mod_rewrite']['hidden'] = true;
		
		} else {
			$apacheModules = apache_get_modules();
		
			if (!in_array('mod_deflate', $apacheModules)) {
				$optionalRequirementsMet = false;
				$fields['0/optional_mod_deflate']['row_class'] = $warning;
			}
			if (!in_array('mod_expires', $apacheModules)) {
				$optionalRequirementsMet = false;
				$fields['0/optional_mod_expires']['row_class'] = $warning;
			}
			if (!in_array('mod_rewrite', $apacheModules)) {
				$optionalRequirementsMet = false;
				$fields['0/optional_mod_rewrite']['row_class'] = $warning;
			}
		}
		
		if (!extension_loaded('curl')) {
			$optionalRequirementsMet = false;
			$fields['0/optional_curl']['row_class'] = $warning;
	
		} else {
			$fields['0/optional_curl']['row_class'] = $valid;
		}
		if (!extension_loaded('zip')) {
			$optionalRequirementsMet = false;
			$fields['0/optional_zip']['row_class'] = $warning;
	
		} else {
			$fields['0/optional_zip']['row_class'] = $valid;
		}
	
	
		$overall = 'section_valid';
	
		if (!$apacheRecommendationMet) {
			$overall = 'section_warning';
			$fields['0/show_server']['pressed'] = true;
			$fields['0/server']['row_class'] = $section_warning;
		} else {
			$fields['0/server']['row_class'] = $section_valid;
		}
	
		if (!$optionalRequirementsMet) {
			$overall = 'section_warning';
			$fields['0/show_optional']['pressed'] = true;
			$fields['0/optional']['row_class'] = $section_warning;
		} else {
			$fields['0/optional']['row_class'] = $section_valid;
		}
	
		if (!$phpRequirementsMet) {
			$overall = 'section_invalid';
			$fields['0/show_php']['pressed'] = true;
			$fields['0/php']['row_class'] = $section_invalid;
		} elseif ($phpWarning) {
			$overall = 'section_warning';
			$fields['0/show_php']['pressed'] = true;
			$fields['0/php']['row_class'] = $section_warning;
		} else {
			$fields['0/php']['row_class'] = $section_valid;
		}
	
		if (!$mysqlRequirementsMet) {
			$overall = 'section_invalid';
			$fields['0/show_mysql']['pressed'] = true;
			$fields['0/mysql']['row_class'] = $section_invalid;
		} else {
			$fields['0/mysql']['row_class'] = $section_valid;
		}
	
		if (!$mbRequirementsMet) {
			$overall = 'section_invalid';
			$fields['0/show_mb']['pressed'] = true;
			$fields['0/mb']['row_class'] = $section_invalid;
		} else {
			$fields['0/mb']['row_class'] = $section_valid;
		}
	
		if (!$gdRequirementsMet) {
			$overall = 'section_invalid';
			$fields['0/show_gd']['pressed'] = true;
			$fields['0/gd']['row_class'] = $section_invalid;
		} else {
			$fields['0/gd']['row_class'] = $section_valid;
		}
	
	
		if ($isDiagnosticsPage) {
			if ($overall != 'section_valid') {
				$fields['0/show_system_requirements']['pressed'] = true;
			}
			$fields['0/system_requirements']['row_class'] = $overall;
	
		} else {
			$wasFirstViewing = $tags['key']['first_viewing'];
			$tags['key']['first_viewing'] = false;
	
			//Did the Server meets the requirements?
			if (!($phpRequirementsMet && $mysqlRequirementsMet && $mbRequirementsMet && $gdRequirementsMet)) {
				$fields['0/continue']['hidden'] = true;
				return false;
	
			} else {
				$fields['0/continue']['hidden'] = false;
				return $wasFirstViewing || !empty($fields['0/continue']['pressed']);
			}
		}
	}



	//Formerly "installerAJAX()"
	public static function installerAJAX(&$source, &$tags, &$fields, &$values, $changes, &$task, $installStatus, &$freshInstall, &$adminId) {
		$merge = [];
		$merge['SUBDIRECTORY'] = SUBDIRECTORY;
	
		//If the database prefix is already set in the config file, look it up and default the field to it.
		if (!isset($fields['3/prefix']['current_value'])) {
			if (defined('DB_NAME_PREFIX') && DB_NAME_PREFIX && strpos(DB_NAME_PREFIX, '[') === false) {
				$values['3/prefix'] = DB_NAME_PREFIX;
			} else {
				$values['3/prefix'] = 'zenario_';
			}
		}
	
		//Validation for Step 1: Check if the Admin has accepted the license
		$licenseAccepted = !empty($values['1/i_agree']);
	
		if (!empty($fields['1/fresh_install']['pressed'])) {
			$task = 'install';
		} elseif (!empty($fields['1/restore']['pressed'])) {
			$task = 'restore';
		}
	
	
		//Validation for Step 3: Validate the Database Connection
		if ($tags['tab'] > 3 || ($tags['tab'] == 3 && !empty($fields['3/next']['pressed']))) {
			$tags['tabs'][3]['errors'] = [];
		
			$merge['DBHOST'] = $values['3/host'];
			$merge['DBNAME'] = $values['3/name'];
			$merge['DBUSER'] = $values['3/user'];
			$merge['DBPASS'] = $values['3/password'];
			$merge['DBPORT'] = $values['3/port'];
			$merge['DB_NAME_PREFIX'] = $values['3/prefix'];
		
			if (!$merge['DBHOST']) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('Please enter your hostname.');
			}
		
			if (!$merge['DBNAME']) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('Please enter your database name.');
			} elseif (preg_match('/[^a-zA-Z0-9_-]/', $merge['DBNAME'])) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('The Database Name should only contain [a-z, A-Z, 0-9, _ and -].');
			}
		
			if (!$merge['DBUSER']) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('Please enter the database username.');
			} elseif (preg_match('/[^a-zA-Z0-9_-]/', $merge['DBUSER'])) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('The database username should only contain [a-z, A-Z, 0-9, _ and -].');
			}
		
			if ($merge['DBPORT'] !== '' && !is_numeric($merge['DBPORT'])) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('The database port must be a number.');
			}
		
			if (preg_match('/[^a-zA-Z0-9_]/', $merge['DB_NAME_PREFIX'])) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('The table prefix should only contain the characters [a-z, A-Z, 0-9 and _].');
			}
		
			if (empty($tags['tabs'][3]['errors'])) {
				
				$testConnect = \ze\db::connect($merge['DBHOST'], $merge['DBNAME'], $merge['DBUSER'], $merge['DBPASS'], $merge['DBPORT'], $reportErrors = false);
			
				if (!$testConnect) {
					$tags['tabs'][3]['errors'][] = 
						\ze\admin::phrase('The database name, username and/or password are invalid.');
			
				} else {
				
					\ze::$localDB =
					\ze::$lastDB = $testConnect;
					\ze::$lastDBHost = $merge['DBHOST'];
					\ze::$lastDBName = $merge['DBNAME'];
					\ze::$lastDBPrefix = $merge['DB_NAME_PREFIX'];			
				
					if (!($result = @\ze\sql::select("SELECT VERSION()"))
						   || !($version = @\ze\sql::fetchRow($result))
						   || !($result = @\ze\sql::select("SHOW TABLES"))) {
						$tags['tabs'][3]['errors'][] = 
							\ze\admin::phrase('You do not have access rights to the database [[DBNAME]].', $merge);
			
					} elseif (!\ze\welcome::compareVersionNumber($version[0], '5.5.3')) {
						$tags['tabs'][3]['errors'][] = 
							\ze\admin::phrase('Sorry, your MySQL server is "[[version]]". Version 5.5.3 or later is required.', ['version' => $version[0]]);
			
					} elseif (!(@\ze\sql::update("CREATE TABLE IF NOT EXISTS `zenario_priv_test` (`id` TINYINT(1) NOT NULL )", false, false))
						   || !(@\ze\sql::update("DROP TABLE `zenario_priv_test`", false, false))) {
						$tags['tabs'][3]['errors'][] = 
							\ze\admin::phrase('Cannot verify database privileges. Please ensure the MySQL user [[DBUSER]] has CREATE TABLE and DROP TABLE privileges. You may need to contact your MySQL administrator to have these privileges enabled.',
								$merge['DBUSER']).
							\ze\welcome::installerReportError();
			
					} else {
						while ($tables = \ze\sql::fetchRow($result)) {
							if ($merge['DB_NAME_PREFIX'] == '' || substr($tables[0], 0, strlen($merge['DB_NAME_PREFIX'])) == $merge['DB_NAME_PREFIX']) {
								$tags['tabs'][3]['errors'][] = \ze\admin::phrase('There are already tables in this database that match your chosen table prefix. Please choose a different table prefix, or a different database.');
								break;
							}
						}
					}
				}
			}
		}
	
	
		//No validation for Step 4, but remember the theme and language chosen
		if ($tags['tab'] > 4 || ($tags['tab'] == 4 && !empty($fields['4/next']['pressed']))) {
			$merge['LANGUAGE_ID'] = $values['4/language_id'];
			$merge['VIS_DATE_FORMAT_SHORT'] = $values['4/vis_date_format_short'];
			$merge['VIS_DATE_FORMAT_MED'] = $values['4/vis_date_format_med'];
			$merge['VIS_DATE_FORMAT_LONG'] = $values['4/vis_date_format_long'];
			$merge['THEME'] = preg_replace('/\W/', '', $values['4/theme']);
		}
	
	
		if (empty($fields['1/restore']['pressed'])) {
			//Validation for Step 5: Validate new Admin's details
			if (($tags['tab'] > 5 || ($tags['tab'] == 5 && !empty($fields['5/next']['pressed'])))) {
				$tags['tabs'][5]['errors'] = [];
			
				if (!$merge['admin_first_name'] = $values['5/first_name']) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Please enter your first name.');
				}
	
				if (!$merge['admin_last_name'] = $values['5/last_name']) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Please enter your last name.');
				}
	
				if (!$merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'] = $values['5/email']) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Please enter your email address.');
			
				} elseif (!\ze\ring::validateEmailAddress($merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'])) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Please enter a valid email address.');
				}
	
				if (!$merge['USERNAME'] = $values['5/username']) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Please choose an administrator username.');
			
				} elseif (!\ze\ring::validateScreenName($merge['USERNAME'])) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Your username may not contain any special characters.');
			
				} elseif (preg_replace('/\S/', '', $merge['USERNAME'])) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Your username may not contain any spaces.');
				}
	
				if (!$merge['PASSWORD'] = $values['5/password']) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Please choose a password.');
			
				} elseif ($merge['PASSWORD'] != $values['5/re_password']) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('The password fields do not match.');
				}
			}
		} else {
			//Validation for Step 4/5: Check a backup file exists
			if ((($tags['tab'] != 6 && $tags['tab'] > 5)
			  || ($tags['tab'] == 6 && !empty($fields['6/next']['pressed'])))) {
				$tags['tabs'][6]['errors'] = [];
			
				if (!is_file($values['6/path'])) {
					$tags['tabs'][6]['errors'][] = \ze\admin::phrase('Please enter a path to a file.');
			
				} else
				if (substr($values['6/path'], -4) != '.sql'
				 && substr($values['6/path'], -7) != '.sql.gz'
				 && substr($values['6/path'], -14) != '.sql.encrypted'
				 && substr($values['6/path'], -17) != '.sql.gz.encrypted') {
					$tags['tabs'][6]['errors'][] = \ze\admin::phrase('Please select a backup file.');
			
				} elseif (!is_readable($values['6/path'])) {
					$tags['tabs'][6]['errors'][] = \ze\admin::phrase('Please enter a path to a file with read-access set.');
				}
	
				if (!$merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'] = $values['6/email']) {
					$tags['tabs'][6]['errors'][] = \ze\admin::phrase('Please enter a support email address.');
			
				} elseif (!\ze\ring::validateEmailAddress($merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'])) {
					$tags['tabs'][6]['errors'][] = \ze\admin::phrase('Please enter a valid email address.');
				}
			}
		}
	
	
		//Validation for Step 6: Attempt to create (if requested) and then validate the siteconfig files
		if ($tags['tab'] == 7 && (!empty($fields['7/ive_done_it']['pressed']) || !empty($fields['7/do_it_for_me']['pressed']))) {
			$tags['tabs'][7]['errors'] = [];
		
			$checkConfigFileExists = \ze\welcome::checkConfigFileExists();
			if (!empty($fields['7/do_it_for_me']['pressed'])) {
				$permErrors = false;
				if (!$checkConfigFileExists) {
					if (!file_exists(CMS_ROOT. 'zenario_siteconfig.php')) {
						$tags['tabs'][7]['errors'][] =
							\ze\admin::phrase('Please create a file called zenario_siteconfig.php. If you want this installer to populate it, it can be empty but writable.');
				
					} elseif (!@file_put_contents(CMS_ROOT. 'zenario_siteconfig.php', \ze\welcome::readSampleConfigFile($merge))) {
						$tags['tabs'][7]['errors'][] =
							\ze\admin::phrase('Could not write to file zenario_siteconfig.php');
						$permErrors = true;
					}
				}
			
				if ($permErrors && !stristr(php_uname('s'), 'win')) {
					$tags['tabs'][7]['errors'][] = \ze\admin::phrase('To correct the file permissions: chmod 666 zenario_siteconfig.php');
				}
		
			} else {
				if ($checkConfigFileExists === false) {
					$tags['tabs'][7]['errors'][] = \ze\admin::phrase('Please create a file named zenario_siteconfig.php in the location shown below.');
			
				} elseif ($checkConfigFileExists === 0) {
					$tags['tabs'][7]['errors'][] = \ze\admin::phrase('Please please enter the text as shown into your zenario_siteconfig.php file.');
				}
			}
		
			if ($checkConfigFileExists) {
				if (!@include_once CMS_ROOT. 'zenario_siteconfig.php') {
					$tags['tabs'][7]['errors'][] = \ze\admin::phrase('There is a syntax error in zenario_siteconfig.php');
				} else {
					foreach (['DBHOST', 'DBNAME', 'DBUSER', 'DBPASS', 'DBPORT', 'DB_NAME_PREFIX'] as $constant) {
						if (!defined($constant) || constant($constant) !== $merge[$constant]) {
							$tags['tabs'][7]['errors'][] = \ze\admin::phrase('The constants in zenario_siteconfig.php are not set as below.');
							break;
						}
					}
				}
			}
		
			if (!empty($tags['tabs'][7]['errors'])) {
				$fields['7/ive_done_it']['pressed'] = false;
				$fields['7/do_it_for_me']['pressed'] = false;
			}
		}
	
	
	
		//Handle navigation
		switch ($tags['tab']) {
			case 0:
				if (!empty($fields['0/next']['pressed'])) {
					$tags['tab'] = 1;
				}
			
				break;
		
		
			case 1:
				if (!empty($fields['1/restore']['pressed']) || !empty($fields['1/fresh_install']['pressed'])) {
					$tags['tab'] = 3;
				}
			
				break;
		
		
			case 3:
				if (!empty($fields['3/previous']['pressed'])) {
					unset($tags['tabs'][3]['errors']);
					$tags['tab'] = 1;
			
				} elseif (!empty($fields['3/next']['pressed'])) {
					if (!empty($fields['1/restore']['pressed'])) {
						$tags['tab'] = 6;
					} else {
						$tags['tab'] = 4;
					}
				}
			
				break;
		
		
			case 4:
				if (!empty($fields['4/previous']['pressed'])) {
					unset($tags['tabs'][4]['errors']);
					$tags['tab'] = 3;
			
				} elseif (!empty($fields['4/next']['pressed'])) {
					$tags['tab'] = 5;
				}
			
				break;
		
		
			case 5:
				if (!empty($fields['5/previous']['pressed'])) {
					unset($tags['tabs'][5]['errors']);
					$tags['tab'] = 4;
			
				} elseif (!empty($fields['5/next']['pressed'])) {
					$tags['tab'] = 7;
				}
			
				break;
			
			
			case 6:
				if (!empty($fields['6/previous']['pressed'])) {
					unset($tags['tabs'][6]['errors']);
					$tags['tab'] = 3;
			
				} elseif (!empty($fields['6/next']['pressed'])) {
					$tags['tab'] = 7;
				}
			
				break;
		
		
			case 7:
				if (!empty($fields['7/previous']['pressed'])) {
					unset($tags['tabs'][7]['errors']);
				
					if (!empty($fields['1/restore']['pressed'])) {
						$tags['tab'] = 6;
					} else {
						$tags['tab'] = 5;
					}
			
				} elseif (!empty($fields['7/do_it_for_me']['pressed']) || !empty($fields['7/ive_done_it']['pressed'])) {
					$tags['tab'] = 8;
				}
			
				break;
			
			
			case 8:
				if (!empty($fields['8/previous']['pressed'])) {
					unset($tags['tabs'][8]['errors']);
					$tags['tab'] = 7;
				}
			
				break;
		}
	
	
		//Don't let the Admin proceed from Step 1 without accepting the license
		if ($tags['tab'] > 0 && !$licenseAccepted) {
			$tags['tab'] = 1;
		}
	
		//Don't let the Admin proceed from Step 3 without a valid database connection
		if ($tags['tab'] > 3 && !empty($tags['tabs'][3]['errors'])) {
			$tags['tab'] = 3;
		}
	
		if (empty($fields['1/restore']['pressed'])) {
			//Don't let the Admin proceed from Step 5 without entering their admin details
			if ($tags['tab'] > 5 && !empty($tags['tabs'][5]['errors'])) {
				$tags['tab'] = 5;
			}
		} else {
			//Don't let the Admin proceed from Step 4/5 without selecting a backup
			if ($tags['tab'] > 6 && !empty($tags['tabs'][6]['errors'])) {
				$tags['tab'] = 6;
			}
		}
	
		//Don't let the Admin proceed from Step 6 without setting the siteconfig up correctly
		if ($tags['tab'] > 7 && !empty($tags['tabs'][7]['errors'])) {
			$tags['tab'] = 7;
		}
	
	
		//Display the current step
		switch ($tags['tab']) {
			case 0:
			
				if ($installStatus == 0) {
					$fields['0/reason']['snippet']['html'] = 
						\ze\admin::phrase('The config file "[[file]]" is empty.',
							['file' => '<code>zenario_siteconfig.php</code>']);
			
				} elseif ($installStatus == 1) {
					$fields['0/reason']['snippet']['html'] = 
						\ze\admin::phrase('A database connection could not be established.');
			
				} elseif ($installStatus == 2) {
					$fields['0/reason']['snippet']['html'] = 
						\ze\admin::phrase('The database is empty, or contains no tables with the specified prefix.');
				}
			
				break;
		
		
			case 1:
				if (is_file(CMS_ROOT. 'license.txt')) {
					$fields['1/license']['snippet']['url'] = 'license.txt';
			
				} else {
					echo \ze\admin::phrase('The license file "license.txt" could not be found. This installer cannot proceed.');
					exit;
				}
			
				break;
		
		
			case 3:
				//Nothing doing for step 3
			
				break;
		
		
			case 4:
			
				//Old code for sample sites, commented out as we don't currently use them
				//$fields['4/sample']['values'] = [
				//	0 => [
				//		'label' => \ze\admin::phrase('Blank Site:'),
				//		'note_below' => \ze\admin::phrase('An empty site, ready for you to choose a language.')]];
				//
				//foreach (listSampleSites() as $dir => $path) {
				//	$fields['4/sample']['values'][$dir] = [
				//		'label' => $dir. ': ',
				//		'note_below' => file_get_contents($path. 'description.txt')];
				//}
			
				//Format the labels for the date format picker fields
				if (empty($fields['4/language_id']['values'])) {
					$fields['4/language_id']['values'] = [];
				
					$ord = 0;
					$languages = require CMS_ROOT. 'zenario/includes/language_names.inc.php';
					foreach ($languages as $langId => $phrases) {
						$fields['4/language_id']['values'][$langId] = $phrases[0]. ' ('. $langId. ')';
					}
				
					\ze\miscAdm::formatDateFormatSelectList($fields['4/vis_date_format_short'], true);
					\ze\miscAdm::formatDateFormatSelectList($fields['4/vis_date_format_med']);
					\ze\miscAdm::formatDateFormatSelectList($fields['4/vis_date_format_long']);
				}
			
				//Add logic if English is not chosen..?
				//if (!empty($values['4/language_id'])
				// && !\ze\ring::chopPrefix('en-', $values['4/language_id'])) {
				//	
				//}
			
				if (empty($fields['4/theme']['values'])) {
					$fields['4/theme']['values'] = [];
					foreach (\ze\welcome::listSampleThemes() as $dir => $imageSrc) {
						$fields['4/theme']['values'][$dir] = [
							'label' => '',
							'post_field_html' =>
								'<label for="theme___'. htmlspecialchars($dir). '">
									<img src="'. htmlspecialchars('../../'. $imageSrc). '"/>
								</label>'
						];
					}
				}
			
				break;
		
		
			case 5:
			
				$strength = \ze\user::passwordValuesToStrengths(\ze\user::checkPasswordStrength(($fields['5/password']['current_value'] ?? false), true));
				$fields['5/password_strength']['snippet']['html'] =
					'<div class="password_'. $strength. '"><span>'. \ze\admin::phrase($strength). '</span></div>';
			
			
				break;
		
		
			case 6:
				$fields['6/path']['value'] = CMS_ROOT;
				$fields['6/path_status']['snippet']['html'] = '&nbsp;';
			
			
				break;
		
		
			case 7:
				$fields['7/zenario_siteconfig']['pre_field_html'] =
					'<pre>'. CMS_ROOT. 'zenario_siteconfig.php'. ':</pre>';
				$fields['7/zenario_siteconfig']['value'] = \ze\welcome::readSampleConfigFile($merge);
				unset($values['7/zenario_siteconfig']);
			
			
				break;
		
		
			case 8:
				$tags['tabs'][8]['errors'] = [];
			
				if (!defined('SHOW_SQL_ERRORS_TO_VISITORS')) {
					define('SHOW_SQL_ERRORS_TO_VISITORS', true);
				}
			
				$merge['URL'] = \ze\link::protocol(). $_SERVER['HTTP_HOST'];
				$merge['HTTP_HOST'] = $_SERVER['HTTP_HOST'];
				$merge['SUBDIRECTORY'] = SUBDIRECTORY;
			
				//Set the latest revision number
				$merge['LATEST_REVISION_NO'] = LATEST_REVISION_NO;
				$merge['INSTALLER_REVISION_NO'] = INSTALLER_REVISION_NO;
			
			
				//Install to the database
				\ze\sql::select('SET NAMES "UTF8"');
				\ze\sql::select("SET collation_connection='utf8_general_ci'");
				\ze\sql::select("SET collation_server='utf8_general_ci'");
				\ze\sql::select("SET character_set_client='utf8'");
				\ze\sql::select("SET character_set_connection='utf8'");
				\ze\sql::select("SET character_set_results='utf8'");
				\ze\sql::select("SET character_set_server='utf8'");
			
				if (!defined('DB_NAME_PREFIX')) {
					define('DB_NAME_PREFIX', $merge['DB_NAME_PREFIX']);
				}
			
			
				//Here we restore a backup, and/or do a fresh install, depending on the choices in the installer.
				//(Installing a sample site is done by restoring a backup, then immediately doing a fresh install.)
			
				$doFreshInstall = !empty($fields['1/fresh_install']['pressed']);
				$restoreBackup = !$doFreshInstall && !empty($fields['1/restore']['pressed']);
			
				//Old code for sample sites, commented out as we don't currently use them
				//$installSampleSite = $doFreshInstall && !empty($values['4/sample']);
			
				//Try to restore a backup file
				if ($restoreBackup) {
					//Set the primary_domain to the current domain, reguardless of what it is in the backup
					\ze::$siteConfig['primary_domain'] = $_SERVER['HTTP_HOST'];
				
					//Restore a backup, using either the specified path or the sample install path
				
					//Old code for sample sites, commented out as we don't currently use them
					//if ($installSampleSite) {
					//	$backupPath = '';
					//	foreach (listSampleSites() as $dir => $path) {
					//		if ($dir == $values['4/sample']) {
					//			$backupPath = $path. 'backup.sql.gz';
					//		}
					//	}
					//} else {
						$backupPath = $values['6/path'];
					//}
				
					//Little hack - the mysql_path and mysqldump_path site settings won't be set,
					//so temporarily set  them to their default values just before we attempt the restore.
					\ze\site::setSetting('mysqldump_path', 'PATH', $updateDB = false);
					\ze\site::setSetting('mysql_path', 'PATH', $updateDB = false);
				
				
					$failures = [];
					if (!\ze\dbAdm::restoreFromBackup($backupPath, $failures)) {
						foreach ($failures as $text) {
							$tags['tabs'][8]['errors'][] = $text;
						}
					}
				}
			
				//Set up tables in a fresh installation
				if ($doFreshInstall && empty($tags['tabs'][8]['errors'])) {
					//Old code for sample sites, commented out as we don't currently use them
					//if ($installSampleSite) {
					//	$files = ['local-admin-CREATE.sql', 'local-sample-INSERT.sql'];
					//} else {
						set_time_limit(60 * 10);
						$files = ['local-CREATE.sql', 'local-admin-CREATE.sql', 'local-INSERT.sql'];
					//}
				
					$error = false;
					foreach ($files as $file) {
						if (!\ze\welcome::runSQL('zenario/admin/db_install/', $file, $error, $merge)) {
							$tags['tabs'][8]['errors'][] = $error;
							break;
						}
					}
				
					//Was the install successful?
					if (empty($tags['tabs'][8]['errors'])) {
					
						//Define the main email address for the remaining run of this script
						//(This is just needed this once; the next time a script run this should
						// be defined by the config file.)
						if (!defined('EMAIL_ADDRESS_GLOBAL_SUPPORT')) {
							define('EMAIL_ADDRESS_GLOBAL_SUPPORT', $merge['EMAIL_ADDRESS_GLOBAL_SUPPORT']);
						}
					
						//Populate the Modules table with all of the Modules in the system,
						//and install and run any Modules that should running by default.
						\ze\moduleAdm::addNew($skipIfFilesystemHasNotChanged = false, $runModulesOnInstall = true, $dbUpdateSafeMode = true);
					
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
						
							\ze\row::set('languages', [
								'detect' => 0,
								'detect_lang_codes' => '',
								'translate_phrases' => $translatePhrases,
								'search_type'=> $searchType
								], 
								$merge['LANGUAGE_ID']);
						
							//Set this language as the default language
							\ze\site::setSetting('default_language', \ze::$defaultLang = $merge['LANGUAGE_ID']);

							//Import any phrases for Modules that use phrases
							\ze\contentAdm::importPhrasesForModules($merge['LANGUAGE_ID']);
						}
					
						\ze\site::setSetting('vis_date_format_short', $merge['VIS_DATE_FORMAT_SHORT']);
						\ze\site::setSetting('vis_date_format_med', $merge['VIS_DATE_FORMAT_MED']);
						\ze\site::setSetting('vis_date_format_long', $merge['VIS_DATE_FORMAT_LONG']);
						\ze\site::setSetting('vis_date_format_datepicker', \ze\miscAdm::convertMySQLToJqueryDateFormat($merge['VIS_DATE_FORMAT_SHORT']));
						\ze\site::setSetting('organizer_date_format', \ze\miscAdm::convertMySQLToJqueryDateFormat($merge['VIS_DATE_FORMAT_MED']));
					
						//Set a random key that is linked to the site.
						\ze\site::setSetting('site_id', \ze\dbAdm::generateRandomSiteIdentifierKey());
		
						//Set a random first data-revision number
						//(If someone is doing repeated fresh installs, this stops data from an old one getting cached and used in another)
						\ze\row::set('local_revision_numbers', ['revision_no' => rand(1, 32767)], ['path' => 'data_rev', 'patchfile' => 'data_rev']);
					
					
						$freshInstall = true;
					
						//Create an Admin, and give them all of the core permissions
						$details = [
							'username' => $merge['USERNAME'],
							'first_name' => $merge['admin_first_name'],
							'last_name' => $merge['admin_last_name'],
							'email' => $merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'],
							'created_date' => \ze\date::now(),
							'status' => 'active'];
					
						$adminId = \ze\row::insert('admins', $details);
						\ze\adminAdm::setPassword($adminId, $merge['PASSWORD']);
						\ze\adminAdm::savePerms($adminId, 'all_permissions');
						\ze\admin::setSession($adminId);
					
					
						//Prepare email to the installing person
						$message = $source['email_templates']['installed_cms']['body'];
					
						$subject = $source['email_templates']['installed_cms']['subject'];
					
						foreach ($merge as $pattern => $replacement) {
							$message = str_replace('[['. $pattern. ']]', $replacement, $message);
						}
					
						$addressToOverriddenBy = false;
						\ze\server::sendEmail(
							$subject, $message,
							$merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'],
							$addressToOverriddenBy,
							$nameTo = false,
							$addressFrom = false,
							$nameFrom = $source['email_templates']['installed_cms']['from'],
							false, false, false,
							$isHTML = false);
					
					
						//Apply database updates
						$moduleErrors = '';
						\ze\dbAdm::checkIfUpdatesAreNeeded($moduleErrors, $andDoUpdates = true);
						
						//Reset the cached table details, in case any of the definitions are out of date
						\ze::$dbCols = [];
					
						//Fix a bug where sample sites might not have a default language set, by setting a default language if any content has been created
						if (!\ze::$defaultLang && ($langId = \ze\row::get('content_items', 'language_id', []))) {
							\ze\site::setSetting('default_language', \ze::$defaultLang = $langId);
						}
					
						//Populate the menu_hierarchy and the menu_positions tables
						\ze\menuAdm::recalcAllHierarchy();
					
						//Update the special pages, creating new ones if needed
						\ze\contentAdm::addNeededSpecialPages();
					}
				}
			
				if (!empty($tags['tabs'][8]['errors'])) {
					//Did something go wrong? Remove any tables that were created.
					\ze\welcome::runSQL('zenario/admin/db_install/', 'local-DROP.sql', $error, $merge);
					\ze\welcome::runSQL('zenario/admin/db_install/', 'local-admin-DROP.sql', $error, $merge);
				
					foreach (\ze\dbAdm::lookupExistingCMSTables($dbUpdateSafeMode = true) as $table) {
						if ($table['reset'] == 'drop') {
							\ze\sql::select("DROP TABLE `". \ze\escape::sql($table['actual_name']). "`");
						}
					}
				} else {
					return true;
				}
			
				break;
		}
	
	
	
		return false;
	}






	//Formerly "loginAJAX()"
	public static function loginAJAX(&$source, &$tags, &$fields, &$values, $changes, $getRequest) {
		$passwordReset = false;
		$tags['tabs']['login']['errors'] = [];
		$tags['tabs']['forgot']['errors'] = [];
	
		if ($tags['tab'] == 'login' && !empty($fields['login/previous']['pressed'])) {
			$tags['go_to_url'] = \ze\welcome::redirectAdmin($getRequest);
			return false;
	
		//Go between the login and the forgot password screens
		} elseif ($tags['tab'] == 'login' && !empty($fields['login/forgot']['pressed'])) {
			$tags['tab'] = 'forgot';
	
		} elseif ($tags['tab'] == 'forgot' && !empty($fields['forgot/previous']['pressed'])) {
			$tags['tab'] = 'login';
	
		//Check a login attempt
		} elseif ($tags['tab'] == 'login' && !empty($fields['login/login']['pressed'])) {
		
			if (!$values['login/username']) {
				$tags['tabs']['login']['errors'][] = \ze\admin::phrase('Please enter your administrator username.');
			}
		
			if (!$values['login/password']) {
				$tags['tabs']['login']['errors'][] = \ze\admin::phrase('Please enter your administrator password.');
			}
		
			if (empty($tags['tabs']['login']['errors'])) {
				$details = [];
			
				$adminIdL = \ze\admin::checkPassword($values['login/username'], $details, $values['login/password'], $checkViaEmail = false);
			
				if (!$adminIdL) {
					$tags['tabs']['login']['errors']['details_wrong'] =
						\ze\admin::phrase('Your administaror username and password were not recognised. Please check and try again.');
			
				} elseif (\ze::isError($adminIdL)) {
					$tags['tabs']['login']['errors']['details_wrong'] =
						\ze\admin::phrase($adminIdL->__toString());
			
				} else {
					\ze\admin::logIn($adminIdL, $values['login/remember_me']);
					return true;
				}
			}
	
		//Reset a password
		} elseif ($tags['tab'] == 'forgot' && !empty($fields['forgot/reset']['pressed'])) {
		
			if (!$values['forgot/email']) {
				$tags['tabs']['forgot']['errors'][] = \ze\admin::phrase('Please enter your email address.');
		
			} elseif (!\ze\ring::validateEmailAddress($values['forgot/email'])) {
				$tags['tabs']['forgot']['errors'][] = \ze\admin::phrase('Please enter a valid email address.');
		
			//Check for the user by email address, without specifying their password.
			//This will return 0 if the user wasn't found, otherwise it should return
			//false (for user found but password didn't match)
			} elseif (!$admin = \ze\row::get('admins', ['id', 'authtype', 'username', 'email', 'first_name', 'last_name', 'status'], ['email' => $values['forgot/email']])) {
				$tags['tabs']['forgot']['errors'][] = \ze\admin::phrase("Sorry, we could not find that email address associated with an active Administrator on this site's database.");
		
			//Super Admins shouldn't be trying to change their passwords on a local site
			} elseif (isset($admin['authtype']) && $admin['authtype'] != 'local') {
				$tags['tabs']['forgot']['errors'][] = \ze\admin::phrase("Please go to the Controller site to reset the password for a Superadmin account.");
		
			//Trashed admins should not be able to trigger password resets
			} elseif ($admin['status'] == 'deleted') {
				$tags['tabs']['forgot']['errors'][] = \ze\admin::phrase("Sorry, we could not find that email address associated with an active Administrator on this site's database.");
		
			} else {
			
				//Prepare email to the mail with the reset password
				$merge = [];
				$merge['NAME'] = \ze::ifNull(trim($admin['first_name']. ' '. $admin['last_name']), $admin['username']);
				$merge['USERNAME'] = $admin['username'];
				$merge['PASSWORD'] = \ze\admin::resetPassword($admin['id']);
				$merge['URL'] = \ze\link::protocol(). $_SERVER['HTTP_HOST'];
				$merge['SUBDIRECTORY'] = SUBDIRECTORY;
				$merge['IP'] = preg_replace('[^W\.\:]', '', \ze\user::ip());
			
				$emailTemplate = 'new_reset_password';
				$message = $source['email_templates'][$emailTemplate]['body'];
			
				$subject = $source['email_templates'][$emailTemplate]['subject'];
			
				foreach ($merge as $pattern => $replacement) {
					$message = str_replace('[['. $pattern. ']]', $replacement, $message);
				}
			
				$addressToOverriddenBy = false;
				\ze\server::sendEmail(
					$subject, $message,
					$admin['email'],
					$addressToOverriddenBy,
					$nameTo = $merge['NAME'],
					$addressFrom = false,
					$nameFrom = $source['email_templates'][$emailTemplate]['from'],
					false, false, false,
					$isHTML = false);
			
				$passwordReset = true;
				$tags['tab'] = 'login';
			}
		}
	
	
		//Format the login screen
		if ($tags['tab'] == 'login') {
			if (!empty($_COOKIE['COOKIE_LAST_ADMIN_USER'])) {
				$fields['login/username']['value'] = $_COOKIE['COOKIE_LAST_ADMIN_USER'];
			}
			$fields['login/remember_me']['value'] = empty($_COOKIE['COOKIE_DONT_REMEMBER_LAST_ADMIN_USER']);
		
			//Don't show the note about the admin login link if it is turned off in the site settings
			if (\ze\link::adminDomainIsPrivate()) {
				$fields['login/remember_me']['note_below'] = '';
			}
		
			if ($passwordReset) {
				$fields['login/reset']['hidden'] = false;
				$fields['login/description']['hidden'] = true;
			} else {
				$fields['login/reset']['hidden'] = true;
				$fields['login/description']['hidden'] = false;
			}
		}
	
		return false;
	}



	//Formerly "updateNoPermissionsAJAX()"
	public static function updateNoPermissionsAJAX(&$source, &$tags, &$fields, &$values, $changes, &$task, $getRequest) {
		//Handle the "back to site" button
		if (!empty($fields['0/previous']['pressed'])) {
			\ze\welcome::logoutAdminAJAX($tags, $getRequest);
			return;
	
		} else {
			$admins = \ze\row::getArray(
				'admins',
				['first_name', 'last_name', 'username', 'authtype'],
				[
					'status' => 'active',
					'id' => \ze\row::getArray(
						'action_admin_link',
						'admin_id',
						['action_name' => ['_ALL', '_PRIV_APPLY_DATABASE_UPDATES']])],
				['first_name', 'last_name', 'username', 'authtype']
			);
		
			if (!empty($admins)) {
				$html =
					'<p>'. \ze\admin::phrase('The following administrators are able to apply updates:'). '</p><ul>';
			
				foreach ($admins as $admin) {
					$html .= '<li>'. htmlspecialchars(\ze\admin::formatName($admin)). '</li>';
				}
			
				$html .= '</ul>';
			
				$fields['0/admins_who_can_do_updates']['hidden'] = false;
				$fields['0/admins_who_can_do_updates']['snippet']['html'] = $html;
			}
		}
	}



	//Formerly "updateAJAX()"
	public static function updateAJAX(&$source, &$tags, &$fields, &$values, $changes, &$task) {
		$tags['tab'] = 1;
	
		//Apply updates without prompting when installing, restoring backups or doing site resets
		if ($task == 'install' || $task == 'restore' || $task == 'site_reset' || !empty($fields['1/apply']['pressed'])) {
		
			//Update to the latest version
			set_time_limit(60 * 10);
			$moduleErrors = '';
			$dbUpToDate = \ze\dbAdm::checkIfUpdatesAreNeeded($moduleErrors, $andDoUpdates = true);
		
			if ($dbUpToDate) {
				return true;
			}
		}
	
	
		$fields['1/description']['snippet']['html'] =
			\ze\admin::phrase('We need to update your database (<code>[[database]]</code>) to match.', ['database' => htmlspecialchars(DBNAME)]);
	
	
		if ($tags['tab'] == 1 && !empty($fields['1/why']['pressed'])) {
			$modules = [];
			$revisions = [];
			$currentRevisionNumber = false;
			$latestRevisionNumber = LATEST_REVISION_NO;
	
			//Get details on any database updates needed
			$moduleErrors = '';
			if ($updates = \ze\dbAdm::checkIfUpdatesAreNeeded($moduleErrors, false, false, false)) {
				list($currentRevisionNumber, $modules) = $updates;
			}
		
			if (empty($modules)
			 && empty($currentRevisionNumber)) {
			
				$html =
					'<table class="revisions"><tr class="even"><td>'.
						\ze\admin::phrase('A major CMS update needs to be applied.').
					'</td></tr></table>';
		
			} else {
				if ($currentRevisionNumber === false) {
					$currentRevisionNumber = $latestRevisionNumber;
				}
		
				if ($currentRevisionNumber != $latestRevisionNumber) {
					$revisions['core'] = ['name' => ' '. \ze\admin::phrase('Core files'), 'from' => $currentRevisionNumber, 'to' => $latestRevisionNumber];
				}
		
				if (!empty($modules)) {
					foreach($modules as $module => $pluginRevisions) {
						$sql = "
							SELECT display_name
							FROM ". DB_NAME_PREFIX. "modules
							WHERE class_name = '". \ze\escape::sql($module). "'";
				
						if (($result = @\ze\sql::select($sql)) && ($row = \ze\sql::fetchAssoc($result))) {
							$moduleName = ($row['display_name'] ?: $module);
						} else {
							$moduleName = $module;
						}
				
						$revisions[$module] = ['name' => $moduleName, 'from' => $pluginRevisions[0] + 1, 'to' => $pluginRevisions[1]];
					}
				}
		
				\ze\ray::sqlSort($revisions);
		
				$html = '<table class="revisions"><tr><th>'. \ze\admin::phrase('Module'). '</th><th>'. \ze\admin::phrase('Revision Numbers to Apply'). '</th></tr>';
		
				$oddOrEven = 'even';
				foreach ($revisions as $revision) {
					$oddOrEven = $oddOrEven == 'even'? 'odd' : 'even';
					$html .= '<tr class="'. $oddOrEven. '"><td>'. htmlspecialchars($revision['name']). '</td><td>';
			
					if ($revision['from'] == $revision['to']) {
						$html .= \ze\admin::phrase('Revision #[[to]]', $revision);
			
					} elseif ($revision['from'] == 0) {
						$revision['version'] = \ze\site::versionNumber();
						$html .= \ze\admin::phrase('Updates from [[version]]', $revision);
			
					} else {
						$html .= \ze\admin::phrase('Revision #[[from]] through to Revision #[[to]]', $revision);
					}
			
					$html .= '</tr>';
				}
		
				$html .= '</table>';
			}
		
			$fields['1/updates']['snippet']['html'] = $html;
			$fields['1/updates']['hidden'] = false;
		} else {
			$fields['1/updates']['hidden'] = true;
		}
	
		return false;
	}


	//Log the current admin out
	//Formerly "logoutAdminAJAX()"
	public static function logoutAdminAJAX(&$tags, $getRequest) {
		\ze\admin::unsetSession();
		$tags['_clear_local_storage'] = true;
		$tags['go_to_url'] = \ze\welcome::redirectAdmin($getRequest, true);
	}

	//Return the UNIX time, $offset ago, converted to a string
	//Formerly "zenarioSecurityCodeTime()"
	public static function securityCodeTime($offset = 0) {
		return str_pad(time() - (int) $offset * 86400, 16, '0', STR_PAD_LEFT);
	}

	//This returns the name that the cookie for the security code should have.
	//This is in the form "COOKIE_ADMIN_SECURITY_CODE_[[ADMIN_ID]]"
	//Formerly "zenarioSecurityCodeCookieName()"
	public static function securityCodeCookieName() {
		return 'COOKIE_ADMIN_SECURITY_CODE_'. \ze::session('admin_userid');
	}

	//Get the value of the cookie above
	//Formerly "zenarioSecurityCodeCookieValue()"
	public static function securityCodeCookieValue() {
		if (isset($_COOKIE[\ze\welcome::securityCodeCookieName()])) {
			return preg_replace('@[^-_=\w]@', '', $_COOKIE[\ze\welcome::securityCodeCookieName()]);
		} else {
			return '';
		}
	}

	//Looks for a security code cookie with the above name,
	//then returns the corresponding name that a site setting should have.
	//This is in the form "COOKIE_ADMIN_SECURITY_CODE_[[COOKIE_VALUE]]", or
	//"COOKIE_ADMIN_SECURITY_CODE_[[COOKIE_VALUE]]_[[IP_ADDRESS]]", depending on
	//whether the apply_two_factor_authentication_by_ip option is set in the site_description.yaml file.
	//Formerly "zenarioSecurityCodeSettingName()"
	public static function securityCodeSettingName() {
	
		if ('' == ($sccn = \ze\welcome::securityCodeCookieValue())) {
			 return false;
	
		} elseif (\ze\site::description('apply_two_factor_authentication_by_ip')) {
			return 'COOKIE_ADMIN_SECURITY_CODE_'. $sccn. '_'. \ze\user::ip();

		} else {
			return 'COOKIE_ADMIN_SECURITY_CODE_'. $sccn;
		}
	}

	//Remove very old security codes
	//(But keep ones that have recently expired, so we can show a different message to the admin on the login screen.)
	//Formerly "zenarioTidySecurityCodes()"
	public static function tidySecurityCodes() {
		$sql = "
			DELETE FROM ". DB_NAME_PREFIX. "admin_settings
			WHERE name LIKE 'COOKIE_ADMIN_SECURITY_CODE_%'
			  AND value < '". \ze\escape::sql(\ze\welcome::securityCodeTime(2 * \ze\site::description('two_factor_authentication_timeout'))). "'";
		\ze\sql::update($sql, false, false);
	}






	//Formerly "securityCodeAJAX()"
	public static function securityCodeAJAX(&$source, &$tags, &$fields, &$values, $changes, &$task, $getRequest, $time) {
	
		$firstTimeHere = empty($_SESSION['COOKIE_ADMIN_SECURITY_CODE']);
		$resend = !empty($fields['security_code/resend']['pressed']);
		$tags['tabs']['security_code']['notices']['email_resent']['show'] = false;
		$tags['tabs']['security_code']['errors'] = [];
	
		//Handle the "back to site" button
		if (!empty($fields['security_code/previous']['pressed'])) {
			\ze\welcome::logoutAdminAJAX($tags, $getRequest);
			return false;
	
		//If we've not sent the email yet, or the user presses the retry button, then send the email
		} elseif ($firstTimeHere || $resend) {
		
			if ($firstTimeHere) {
				$_SESSION['COOKIE_ADMIN_SECURITY_CODE'] = \ze\ring::randomFromSet(5, 'ABCDEFGHIJKLMNPPQRSTUVWXYZ');
			}
		
			$admin = \ze\row::get('admins', ['id', 'authtype', 'username', 'email', 'first_name', 'last_name'], $_SESSION['admin_userid'] ?? false);
		
			//Prepare email to the mail with the code
			$merge = [];
			$merge['NAME'] = \ze::ifNull(trim($admin['first_name']. ' '. $admin['last_name']), $admin['username']);
			$merge['USERNAME'] = $admin['username'];
			$merge['URL'] = \ze\link::protocol(). $_SERVER['HTTP_HOST'];
			$merge['SUBDIRECTORY'] = SUBDIRECTORY;
			$merge['IP'] = preg_replace('[^W\.\:]', '', \ze\user::ip());
			$merge['CODE'] = $_SESSION['COOKIE_ADMIN_SECURITY_CODE'];
		
			$emailTemplate = 'security_code';
			$message = $source['email_templates'][$emailTemplate]['body'];
		
			$subject = $source['email_templates'][$emailTemplate]['subject'];
		
			foreach ($merge as $pattern => $replacement) {
				$subject = str_replace('[['. $pattern. ']]', $replacement, $subject);
				$message = str_replace('[['. $pattern. ']]', $replacement, $message);
			}
		
			$addressToOverriddenBy = false;
			$emailSent = \ze\server::sendEmail(
				$subject, $message,
				$admin['email'],
				$addressToOverriddenBy,
				$nameTo = $merge['NAME'],
				$addressFrom = false,
				$nameFrom = $source['email_templates'][$emailTemplate]['from'],
				false, false, false,
				$isHTML = false);
		
			if (!$emailSent) {
				$tags['tabs']['security_code']['errors'][] =
					\ze\admin::phrase('Warning! The system could not send an email. Please contact your server administrator.');
			
			} elseif ($resend) {
				$tags['tabs']['security_code']['notices']['email_resent']['show'] = true;
			}
	
		//Check the code if someone is trying to submit it
		} else
		if (!empty($_SESSION['COOKIE_ADMIN_SECURITY_CODE'])
		 && !empty($fields['security_code/submit']['pressed'])) {
		
			$code = $values['security_code/code'];
		
			if ($code == '') {
				$tags['tabs']['security_code']['errors'][] =
					\ze\admin::phrase('Please enter the code that was sent to your email address.');
		
			} elseif (trim($code) !== $_SESSION['COOKIE_ADMIN_SECURITY_CODE']) {
				$tags['tabs']['security_code']['errors'][] =
					\ze\admin::phrase('The code you have entered is not correct.');
		
			} else {
			
				//If the code is correct, save a cookie...
				\ze\cookie::set(
					\ze\welcome::securityCodeCookieName(),
					\ze\ring::random(),
					(int) \ze\site::description('two_factor_authentication_timeout') * 86400);
			
				//...and an admin setting to remember it next time!
				$scsn = \ze\welcome::securityCodeSettingName();
				$time = \ze\welcome::securityCodeTime();
				\ze\admin::setSetting($scsn, $time);
			
				return true;
			}
		}
		
		
		
		$fields['security_code/not_seen_you']['hidden'] =
		$fields['security_code/not_seen_you_ip']['hidden'] =
		$fields['security_code/timeout']['hidden'] = 
		$fields['security_code/timeout_ip']['hidden'] = true;
		
		if (\ze\site::description('apply_two_factor_authentication_by_ip')) {
			if ($time) {
				$fields['security_code/timeout_ip']['hidden'] = false;
			} else {
				$fields['security_code/not_seen_you_ip']['hidden'] = false;
			}
		} else {
			if ($time) {
				$fields['security_code/timeout']['hidden'] = false;
			} else {
				$fields['security_code/not_seen_you']['hidden'] = false;
			}
		}
	
	
		return false;
	}





	//Formerly "changePasswordAJAX()"
	public static function changePasswordAJAX(&$source, &$tags, &$fields, &$values, $changes, &$task) {
	
		//Skip this screen if the Admin presses the "Skip" button
		if (!empty($fields['change_password/skip']['pressed'])) {
			\ze\admin::cancelPasswordChange($_SESSION['admin_userid'] ?? false);
		
			if ($task == 'change_password') {
				$task = 'password_changed';
			}
		
			return true;
	
		//Change the password if the Admin presses the change password
		} elseif (!empty($fields['change_password/change_password']['pressed'])) {
			$details = [];
			$tags['tabs']['change_password']['errors'] = [];
			$currentPassword = $values['change_password/current_password']; 
			$newPassword = $values['change_password/password']; 
			$newPasswordConfirm = $values['change_password/re_password']; 
		
			if (!$currentPassword) {
				$tags['tabs']['change_password']['errors'][] = \ze\admin::phrase('Please enter your current password.');
		
			} elseif (!\ze\ring::engToBoolean(\ze\admin::checkPassword($_SESSION['admin_username'] ?? false, $details, $currentPassword))) {
				$tags['tabs']['change_password']['errors'][] = \ze\admin::phrase('_MSG_PASS_WRONG');
			}
		
			if (!$newPassword) {
				$tags['tabs']['change_password']['errors'][] = \ze\admin::phrase('Please enter your new password.');
		
			} elseif ($newPassword == $currentPassword) {
				$tags['tabs']['change_password']['errors'][] = \ze\admin::phrase('_MSG_PASS_NOT_CHANGED');
		
			} elseif (!\ze\user::checkPasswordStrength($newPassword)) {
				$tags['tabs']['change_password']['errors'][] = \ze\admin::phrase('_MSG_PASS_STRENGTH');
		
			} elseif (!$newPasswordConfirm) {
				$tags['tabs']['change_password']['errors'][] = \ze\admin::phrase('Please repeat your New Password.');
		
			} elseif ($newPassword != $newPasswordConfirm) {
				$tags['tabs']['change_password']['errors'][] = \ze\admin::phrase('_MSG_PASS_2');
			}
		
			//If no errors with validation, then save new password
			if (empty($tags['tabs']['change_password']['errors'])) {
				\ze\adminAdm::setPassword($_SESSION['admin_userid'] ?? false, $newPassword, 0);
			
				if ($task == 'change_password') {
					$task = 'password_changed';
				}
			
				return true;
			}
		}
	
	
		//Show the password strength box
		$strength = \ze\user::passwordValuesToStrengths(\ze\user::checkPasswordStrength(($fields['change_password/password']['current_value'] ?? false), true));
		$fields['change_password/password_strength']['snippet']['html'] =
			'<div class="password_'. $strength. '"><span>'. \ze\admin::phrase($strength). '</span></div>';
	
	
		return false;
	}
	
	
	public static function newAdminAJAX(&$source, &$tags, &$fields, &$values, $changes, $task, $adminId) { 
		//Set password if the Admin presses the save and login button
		if (!empty($fields['new_admin/save_password_and_login']['pressed'])) {
			$password = $values['new_admin/password'];
			$passwordConfirm = $values['new_admin/re_password'];
			
			if (!$password) {
				$tags['tabs']['new_admin']['errors'][] = \ze\admin::phrase('Please enter a password.');
				
			} elseif (!\ze\user::checkPasswordStrength($password)) {
				$tags['tabs']['new_admin']['errors'][] = \ze\admin::phrase('_MSG_PASS_STRENGTH');
				
			} elseif (!$passwordConfirm) {
				$tags['tabs']['new_admin']['errors'][] = \ze\admin::phrase('Please repeat your Password.');
				
			} elseif ($password != $passwordConfirm) {
				$tags['tabs']['new_admin']['errors'][] = \ze\admin::phrase('_MSG_PASS_2');
				
			}
			
			//If no errors with validation, then save password and login
			if (empty($tags['tabs']['new_admin']['errors'])) {
				\ze\adminAdm::setPassword($adminId, $password, 0);
				\ze\admin::logIn($adminId);
				return true;
			}
		}
		
		//Show the password strength box
		$strength = \ze\user::passwordValuesToStrengths(\ze\user::checkPasswordStrength(($fields['new_admin/password']['current_value'] ?? false), true));
		$fields['new_admin/password_strength']['snippet']['html'] =
			'<div class="password_'. $strength. '"><span>'. \ze\admin::phrase($strength). '</span></div>';
		
		return false;
	}



	//Formerly "diagnosticsAJAX()"
	public static function diagnosticsAJAX(&$source, &$tags, &$fields, &$values, $changes, $task, $freshInstall) {
	
		$showCheckAgainButton = false;
		$showCheckAgainButtonIfDirsAreEditable = false;
	
		//If the directories aren't set at all, set them now so at least they're set to something
		if (!\ze::setting('backup_dir')) {
			\ze\site::setSetting('backup_dir', \ze\dbAdm::suggestDir('backup'));
		}
		if (!\ze::setting('docstore_dir')) {
			\ze\site::setSetting('docstore_dir', \ze\dbAdm::suggestDir('docstore'));
		}
	
	
		$fields['0/template_dir']['value']	= $tdir = CMS_ROOT. 'zenario_custom/templates/grid_templates';
		$fields['0/cache_dir']['value']	= CMS_ROOT. 'cache';
		$fields['0/private_dir']['value']	= CMS_ROOT. 'private';
		$fields['0/public_dir']['value']	= CMS_ROOT. 'public';
	
		if (!$values['0/backup_dir']) {
			$values['0/backup_dir'] = (string) \ze::setting('backup_dir');
		}
	
		if (!$values['0/docstore_dir']) {
			$values['0/docstore_dir'] = (string) \ze::setting('docstore_dir');
		}
	
	
		$fields['0/dirs']['row_class'] = 'section_valid';
	
	
		$mrg = [
			'dir' => $dir = $values['0/backup_dir'],
			'basename' => $dir? htmlspecialchars(basename($dir)) : ''];
	
		if (!$dir) {
			$fields['0/backup_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/backup_dir_status']['snippet']['html'] = \ze\admin::phrase('Please enter a directory.');
	
		} elseif (!@is_dir($dir)) {
			$fields['0/backup_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/backup_dir_status']['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> does not exist.', $mrg);
	
		} elseif (false !== \ze\ring::chopPrefix(realpath(CMS_ROOT), realpath($dir))) {
			$fields['0/backup_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/backup_dir_status']['snippet']['html'] = \ze\admin::phrase('Zenario is installed this directory. Please choose a different directory.', $mrg);
	
		} elseif (!\ze\welcome::directoryIsWritable($dir)) {
			$fields['0/backup_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/backup_dir_status']['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> is not writable.', $mrg);
	
		} else {
			$fields['0/dir_1']['row_class'] = 'sub_section_valid';
			$fields['0/backup_dir_status']['row_class'] = 'sub_valid';
			$fields['0/backup_dir_status']['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> exists and is writable.', $mrg);
		}
	
	
		$mrg = [
			'dir' => $dir = $values['0/docstore_dir'],
			'basename' => $dir? htmlspecialchars(basename($dir)) : ''];
	
		if (!$dir) {
			$fields['0/docstore_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/docstore_dir_status']['snippet']['html'] = \ze\admin::phrase('Please enter a directory.');
	
		} elseif (!@is_dir($dir)) {
			$fields['0/docstore_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/docstore_dir_status']['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> does not exist.', $mrg);
	
		} elseif (false !== \ze\ring::chopPrefix(realpath(CMS_ROOT), realpath($dir))) {
			$fields['0/docstore_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/docstore_dir_status']['snippet']['html'] = \ze\admin::phrase('Zenario is installed this directory. Please choose a different directory.', $mrg);
	
		} elseif (!\ze\welcome::directoryIsWritable($dir)) {
			$fields['0/docstore_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/docstore_dir_status']['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> is not writable.', $mrg);
	
		} else {
			$fields['0/dir_2']['row_class'] = 'sub_section_valid';
			$fields['0/docstore_dir_status']['row_class'] = 'sub_valid';
			$fields['0/docstore_dir_status']['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> exists and is writable.', $mrg);
		}
	
	
		//Check to see if the templates & grid templates directories exist,
		//and that the grid templates directory and all of the files inside are writable.
		//(A site setting can be set to stop this check.)
		$mrg = [
			'dir' => $dir = $tdir,
			'basename' => $dir? htmlspecialchars(basename($dir)) : ''];
	
		if (!is_dir($tdir)) {
			$fields['0/template_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/template_dir_status']['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> does not exist.', $mrg);
	
		} elseif (!\ze\welcome::directoryIsWritable($tdir)) {
			$fields['0/template_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/template_dir_status']['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> is not writable.', $mrg);

		} else {
			$fileWritable = false;
			$fileNotWritable = false;
			foreach (scandir($tdir) as $sdir) {
				if (is_file($tdir. '/'. $sdir)) {
					if (is_writable($tdir. '/'. $sdir)) {
						$fileWritable = true;
					} else {
						if ($fileNotWritable === false) {
							$fileNotWritable = $sdir;
						} else {
							$fileNotWritable = true;
						}
					}
				}
			}
		
			if ($fileNotWritable === true) {
				if ($fileWritable) {
					$fields['0/template_dir_status']['row_class'] = 'sub_invalid';
					$fields['0/template_dir_status']['snippet']['html'] = \ze\admin::phrase('Some of the files in the <code>[[basename]]</code> directory are not writable by the web server (e.g. use &quot;chmod 666 *.tpl.php *.css&quot;).', $mrg);
				} else {
					$fields['0/template_dir_status']['row_class'] = 'sub_invalid';
					$fields['0/template_dir_status']['snippet']['html'] = \ze\admin::phrase('The files in the <code>[[basename]]</code> directory are not writable by the web server, please make them writable (e.g. use &quot;chmod 666 *.tpl.php *.css&quot;).', $mrg);
				}
		
			} elseif ($fileNotWritable !== false) {
				$fields['0/template_dir_status']['row_class'] = 'sub_invalid';
				$fields['0/template_dir_status']['snippet']['html'] =
					\ze\admin::phrase('<code>[[short_path]]</code> is not writable, please make it writable (e.g. use &quot;chmod 666 [[file]]&quot;).',
						['short_path' => htmlspecialchars('grid_templates/'. $fileNotWritable), 'file' => htmlspecialchars($fileNotWritable)]);
		
			} else {
				$fields['0/template_dir_status']['row_class'] = 'sub_valid';
				$fields['0/template_dir_status']['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> exists and is writable.', $mrg);
			}
		}
	
		//Loop through all of the skins in the system (max 9) and check their editable_css directories
		$i = 0;
		$maxI = 9;
		$skinDirsValid = true;
		foreach (\ze\row::getArray(
			'skins',
			['family_name', 'name', 'display_name'],
			['missing' => 0, 'family_name' => 'grid_templates', 'enable_editable_css' => 1]
		) as $skin) {
			if ($i == $maxI) {
				break;
			} else {
				++$i;
			}
		
			$skinWritableDir = CMS_ROOT. \ze\content::skinPath($skin['family_name'], $skin['name']). 'editable_css/';
		
			$tags['tabs'][0]['fields']['skin_dir_'. $i]['value'] =
			$tags['tabs'][0]['fields']['skin_dir_'. $i]['current_value'] = $skinWritableDir;
		
			$mrg = [
				'dir' => $dir = $tdir,
				'basename' => $dir? htmlspecialchars(basename($skinWritableDir)) : ''];
		
			if (!is_dir($skinWritableDir)) {
				$skinDirsValid = false;
				$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_warning';
				$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> does not exist.', $mrg);
	
			} elseif (!\ze\welcome::directoryIsWritable($skinWritableDir)) {
				$skinDirsValid = false;
				$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_warning';
				$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> is not writable.', $mrg);

			} else {
				$fileWritable = false;
				$fileNotWritable = false;
				foreach (scandir($skinWritableDir) as $sdir) {
					if (is_file($skinWritableDir. '/'. $sdir)) {
						if (is_writable($skinWritableDir. '/'. $sdir)) {
							$fileWritable = true;
						} else {
							if ($fileNotWritable === false) {
								$fileNotWritable = $sdir;
							} else {
								$fileNotWritable = true;
							}
						}
					}
				}
		
				if ($fileNotWritable === true) {
					if ($fileWritable) {
						$skinDirsValid = false;
						$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_warning';
						$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] = \ze\admin::phrase('Some of the files in the <code>[[basename]]</code> directory are not writable by the web server (e.g. use &quot;chmod 666 *.css&quot;).', $mrg);
					} else {
						$skinDirsValid = false;
						$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_warning';
						$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] = \ze\admin::phrase('The files in the <code>[[basename]]</code> directory are not writable by the web server, please make them writable (e.g. use &quot;chmod 666 *.css&quot;).', $mrg);
					}
		
				} elseif ($fileNotWritable !== false) {
					$skinDirsValid = false;
					$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_warning';
					$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] =
						\ze\admin::phrase('<code>[[short_path]]</code> is not writable, please make it writable (e.g. use &quot;chmod 666 [[file]]&quot;).',
							['short_path' => htmlspecialchars('editable_css/'. $fileNotWritable), 'file' => htmlspecialchars($fileNotWritable)]);
		
				} else {
					$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_valid';
					$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> exists and is writable.', $mrg);
				}
			}
		}
	
		$fields['0/dir_4']['hidden'] =
		$fields['0/show_dir_4']['hidden'] =
		$fields['0/dir_4_blurb']['hidden'] = $i == 0;
	
		for ($j = 0; $j <= $maxI; ++$j) {
			$tags['tabs'][0]['fields']['skin_dir_'. $j]['hidden'] =
			$tags['tabs'][0]['fields']['skin_dir_status_'. $j]['hidden'] = $j > $i;
		}
	
		if ($i == 1) {
			$fields['0/dir_4_blurb']['snippet']['html'] =
				\ze\admin::phrase('CSS for plugins may be edited by an administrator, and Zenario writes CSS files to the following directory. Please ensure it exists and is writable by the web server:');
		} elseif ($i > 1) {
			$fields['0/dir_4_blurb']['snippet']['html'] =
				\ze\admin::phrase('CSS for plugins may be edited by an administrator, and Zenario writes CSS files to the following directories. Please ensure they exist and are writable by the web server:');
		}
	
	
		if (!is_dir(CMS_ROOT. 'cache')) {
			$fields['0/cache_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/cache_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;cache&quot; directory does not exist.');
	
		} elseif (!\ze\welcome::directoryIsWritable(CMS_ROOT. 'cache')) {
			$fields['0/cache_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/cache_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;cache&quot; directory is not writable.');
	
		} else {
			$fields['0/dir_5']['row_class'] = 'sub_section_valid';
			$fields['0/cache_dir_status']['row_class'] = 'sub_valid';
			$fields['0/cache_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;cache&quot; directory exists and is writable.');
		}
	
		if (!is_dir(CMS_ROOT. 'private')) {
			$fields['0/private_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/private_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;private&quot; directory does not exist.');
	
		} elseif (!\ze\welcome::directoryIsWritable(CMS_ROOT. 'private')) {
			$fields['0/private_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/private_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;private&quot; directory is not writable.');
	
		} else {
			$fields['0/dir_6']['row_class'] = 'sub_section_valid';
			$fields['0/private_dir_status']['row_class'] = 'sub_valid';
			$fields['0/private_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;private&quot; directory exists and is writable.');
		}
	
		if (!is_dir(CMS_ROOT. 'public')) {
			$fields['0/public_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/public_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;public&quot; directory does not exist.');
	
		} elseif (!\ze\welcome::directoryIsWritable(CMS_ROOT. 'public')) {
			$fields['0/public_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/public_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;public&quot; directory is not writable.');
	
		} else {
			$fields['0/dir_7']['row_class'] = 'sub_section_valid';
			$fields['0/public_dir_status']['row_class'] = 'sub_valid';
			$fields['0/public_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;public&quot; directory exists and is writable.');
		}
	
	
		if ($fields['0/backup_dir_status']['row_class'] == 'sub_invalid') {
			$showCheckAgainButtonIfDirsAreEditable =
			$fields['0/show_dirs']['pressed'] =
			$fields['0/show_dir_1']['pressed'] = true;
			$fields['0/dirs']['row_class'] = 'section_invalid';
			$fields['0/dir_1']['row_class'] = 'sub_section_invalid';
		}
	
		if ($fields['0/docstore_dir_status']['row_class'] == 'sub_invalid') {
			$showCheckAgainButtonIfDirsAreEditable =
			$fields['0/show_dirs']['pressed'] =
			$fields['0/show_dir_2']['pressed'] = true;
			$fields['0/dirs']['row_class'] = 'section_invalid';
			$fields['0/dir_2']['row_class'] = 'sub_section_invalid';
		}
	
		if ($fields['0/template_dir_status']['row_class'] == 'sub_invalid') {
			$showCheckAgainButton =
			$fields['0/show_dirs']['pressed'] =
			$fields['0/show_dir_3']['pressed'] = true;
			$fields['0/dirs']['row_class'] = 'section_invalid';
			$fields['0/dir_3']['row_class'] = 'sub_section_invalid';
		} else {
			$fields['0/dir_3']['row_class'] = 'sub_section_valid';
		}
	
		if ($fields['0/cache_dir_status']['row_class'] == 'sub_invalid') {
			$showCheckAgainButton =
			$fields['0/show_dirs']['pressed'] =
			$fields['0/show_dir_5']['pressed'] = true;
			$fields['0/dirs']['row_class'] = 'section_invalid';
			$fields['0/dir_5']['row_class'] = 'sub_section_invalid';
		}
	
		if ($fields['0/private_dir_status']['row_class'] == 'sub_invalid') {
			$showCheckAgainButton =
			$fields['0/show_dirs']['pressed'] =
			$fields['0/show_dir_6']['pressed'] = true;
			$fields['0/dirs']['row_class'] = 'section_invalid';
			$fields['0/dir_6']['row_class'] = 'sub_section_invalid';
		}
	
		if ($fields['0/public_dir_status']['row_class'] == 'sub_invalid') {
			$showCheckAgainButton =
			$fields['0/show_dirs']['pressed'] =
			$fields['0/show_dir_7']['pressed'] = true;
			$fields['0/dirs']['row_class'] = 'section_invalid';
			$fields['0/dir_7']['row_class'] = 'sub_section_invalid';
		}
	
		if (!$skinDirsValid) {
			$showCheckAgainButton =
			$fields['0/show_dirs']['pressed'] =
			$fields['0/show_dir_4']['pressed'] = true;
			if ($fields['0/dirs']['row_class'] != 'section_invalid') {
				$fields['0/dirs']['row_class'] = 'section_warning';
			}
			$fields['0/dir_4']['row_class'] = 'sub_section_warning';
		} else {
			$fields['0/dir_4']['row_class'] = 'sub_section_valid';
		}
	
		$fields['0/site']['row_class'] = 'section_valid';
		$fields['0/content']['row_class'] = 'section_valid';
	
		//Don't show the "site" section yet if we've just finished an install
		if ($freshInstall
		 || $task == 'install'
		 || !\ze\row::exists('languages', [])) {
			foreach ($tags['tabs'][0]['fields'] as $fieldName => &$field) {
				if (!empty($field['hide_on_install'])) {
					$field['hidden'] = true;
				}
			}
	
		} else {
			
			//
			// Go through all of the checks in the site configuration section
			//
			
			$show_warning = false;
			$show_error = false;
		
			if (!\ze::setting('site_enabled')) {
				
				$mrg = ['link' => htmlspecialchars('organizer.php#zenario__administration/panels/site_settings//site_disabled~.zenario_enable_site~tsite~k{"id"%3A"site_disabled"}')];
				
				$show_warning = true;
				$fields['0/site_disabled']['row_class'] = 'warning';
				$fields['0/site_disabled']['snippet']['html'] =
					\ze\admin::phrase('Your site is not enabled, so visitors will not be able to see it. Please go to <a href="[[link]]" target="_blank"><em>Site disabled</em> in the site settings</a> to change this.', $mrg);
			} else {
				$fields['0/site_disabled']['row_class'] = 'valid';
				$fields['0/site_disabled']['snippet']['html'] = \ze\admin::phrase('Your site is enabled.');
			}
		
			$sql = "
				SELECT 1
				FROM ". DB_NAME_PREFIX. "special_pages AS sp
				INNER JOIN ". DB_NAME_PREFIX. "content_items AS c
				   ON c.id = sp.equiv_id
				  AND c.type = sp.content_type
				WHERE c.status NOT IN ('published_with_draft','published')";
		
			if (($result = \ze\sql::select($sql)) && (\ze\sql::fetchRow($result))) {
				$show_warning = true;
				$fields['0/site_special_pages_unpublished']['row_class'] = 'warning';
				$fields['0/site_special_pages_unpublished']['snippet']['html'] =
					\ze::setting('site_enabled')?
						\ze\admin::phrase("Zenario identifies some web pages as &quot;special pages&quot; to perform Not Found, Login and other functions. Some of these pages are not published, so visitors may not be able to access some important functions.")
					:	\ze\admin::phrase("Zenario identifies some web pages as &quot;special pages&quot; to perform Not Found, Login and other functions. Some of these pages are not published. Before enabling your site, please remember to publish them.");
		
			} else {
				$fields['0/site_special_pages_unpublished']['row_class'] = 'valid';
				$fields['0/site_special_pages_unpublished']['hidden'] = true;
			}
			
			
			
			$errors = $exampleFile = false;
			\ze\document::checkAllPublicLinks($forceRemake = false, $errors, $exampleFile);
			if ($errors) {
				$show_warning = true;
				$fields['0/public_documents']['row_class'] = 'warning';
				$fields['0/public_documents']['snippet']['html'] =
					\ze\admin::nPhrase('There is a problem with the public link for [[exampleFile]] and 1 other document. Please check your public/downloads directory for possible permission problems.',
						'There is a problem with the public link for [[exampleFile]] and [[count]] other documents. Please check your public/downloads directory for possible permission problems.',
						$errors - 1, ['exampleFile' => $exampleFile],
						'There is a problem with the public link for the document [[exampleFile]]. Please check your public/downloads directory for possible permission problems.'
					);
		
			} else {
				$fields['0/public_documents']['row_class'] = 'valid';
				$fields['0/public_documents']['hidden'] = true;
			}
		
		
			$mrg = [
				'DBNAME' => DBNAME,
				'path' => \ze::setting('automated_backup_log_path'),
				'link' => htmlspecialchars('organizer.php#zenario__administration/panels/site_settings//dirs~.site_settings~tautomated_backups~k{"id"%3A"dirs"}')];
		
			if (!\ze::setting('check_automated_backups')) {
				$fields['0/site_automated_backups']['row_class'] = 'valid';
				$fields['0/site_automated_backups']['hidden'] = true;
		
			} elseif (!\ze::setting('automated_backup_log_path')) {
				$show_warning = true;
				$fields['0/site_automated_backups']['row_class'] = 'warning';
				$fields['0/site_automated_backups']['snippet']['html'] =
					\ze\admin::phrase('Automated backup monitoring is not set up. Please go to <a href="[[link]]" target="_blank"><em>Backups and document storage</em> in the site settings</a> to change this.', $mrg);
		
			} elseif (!is_file(\ze::setting('automated_backup_log_path'))) {
				$show_warning = true;
				$fields['0/site_automated_backups']['row_class'] = 'warning';
				$fields['0/site_automated_backups']['snippet']['html'] =
					\ze\admin::phrase("Automated backup monitoring is not running properly: the log file ([[path]]) could not be found.", $mrg);
		
			} elseif (!is_readable(\ze::setting('automated_backup_log_path'))) {
				$show_warning = true;
				$fields['0/site_automated_backups']['row_class'] = 'warning';
				$fields['0/site_automated_backups']['snippet']['html'] =
					\ze\admin::phrase("Automated backup monitoring is not running properly: the log file ([[path]]) exists but could not be read.", $mrg);
		
			} else {
				//Attempt to get the date of the last backup from
				$timestamp = \ze\welcome::lastAutomatedBackupTimestamp();
			
				if (!$timestamp) {
					$show_warning = true;
					$fields['0/site_automated_backups']['row_class'] = 'warning';
					$fields['0/site_automated_backups']['snippet']['html'] =
						\ze\admin::phrase('This site is not being backed-up using the automated process: database [[DBNAME]] was not listed in [[path]]. <a href="[[link]]" target="_blank">Click for more.</a>', $mrg);
			
				} else {
					$days = (int) floor((time() - (int) $timestamp) / 86400);
				
					if ($days >= (int) \ze::setting('automated_backup_days')) {
						$show_warning = true;
						$fields['0/site_automated_backups']['row_class'] = 'warning';
						$fields['0/site_automated_backups']['snippet']['html'] =
							\ze\admin::nPhrase("Automated backups have not been run in the last day.",
								"Automated backups have not been run in [[days]] days.",
								$days, ['days' => $days]);
		
					} else {
						$fields['0/site_automated_backups']['row_class'] = 'valid';
						$fields['0/site_automated_backups']['snippet']['html'] =
							\ze\admin::phrase("Automated backups are running.");
					}
				}
			}
			
			
			//Check if the scheduled task manager is running
			if (!\ze\module::inc('zenario_scheduled_task_manager')) {
				$fields['0/scheduled_task_manager']['row_class'] = 'valid';
				$fields['0/scheduled_task_manager']['hidden'] = true;
		
			} elseif (!\zenario_scheduled_task_manager::checkScheduledTaskRunning($jobName = false, $checkPulse = false)) {
				$show_warning = true;
				$fields['0/scheduled_task_manager']['row_class'] = 'warning';
				$fields['0/scheduled_task_manager']['snippet']['html'] =
					\ze\admin::phrase('The Scheduled Tasks Manager is installed, but the master switch is not enabled.');
		
			} elseif (!\zenario_scheduled_task_manager::checkScheduledTaskRunning($jobName = false, $checkPulse = true)) {
				$show_warning = true;
				$fields['0/scheduled_task_manager']['row_class'] = 'warning';
				$fields['0/scheduled_task_manager']['snippet']['html'] =
					\ze\admin::phrase('The Scheduled Tasks Manager is installed, but not correctly configured in your crontab.');
			
			} else {
				$fields['0/scheduled_task_manager']['row_class'] = 'valid';
				$fields['0/scheduled_task_manager']['snippet']['html'] =
					\ze\admin::phrase('The Scheduled Tasks Manager is running.');
			}
			
			
			//Check for a missing site description file.
			if (!is_file(CMS_ROOT. 'zenario_custom/site_description.yaml')) {
				$show_error = true;
				$showCheckAgainButton = true;
				$fields['0/site_description_missing']['row_class'] = 'invalid';
			} else {
				//Note: only show this message if it's in the error state; hide it otherwise
				$fields['0/site_description_missing']['hidden'] = true;
			}
		
			//Check to see if there are spare domains without a primary domain
			if (!\ze::setting('primary_domain') && \ze\row::exists('spare_domain_names', [])) {
				$show_warning = true;
				$fields['0/spare_domains_without_primary_domain']['row_class'] = 'warning';
			} else {
				//Note: only show this message if it's in the error state; hide it otherwise
				$fields['0/spare_domains_without_primary_domain']['hidden'] = true;
			}
		
			//Check IP forwarding is either off, or on and working correctly
			if (defined('USE_FORWARDED_IP')
			 && constant('USE_FORWARDED_IP')
			 && empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$show_warning = true;
				$fields['0/forwarded_ip_misconfigured']['row_class'] = 'warning';
			} else {
				//Note: only show this message if it's in the error state; hide it otherwise
				$fields['0/forwarded_ip_misconfigured']['hidden'] = true;
			}
		
			//Check to see if this is a developer installation...
			$fields['0/notices_shown']['hidden'] = true;
			$fields['0/errors_not_shown']['hidden'] = true;
		
			if (\ze::setting('site_mode') == 'development') {
			
				//Check to see if any developers are not showing errors/warnings
				//Note: only show this message if it's in the error state; hide it otherwise
				if (!(ERROR_REPORTING_LEVEL & E_ALL)
				 || !(ERROR_REPORTING_LEVEL & E_NOTICE)
				 || !(ERROR_REPORTING_LEVEL & E_STRICT)) {
					$show_warning = true;
					$fields['0/errors_not_shown']['hidden'] = false;
					$fields['0/errors_not_shown']['row_class'] = 'warning';
				}
			
				////Check to see if any developers are missing the debug_override_enable setting
				//if (!\ze::setting('debug_override_enable')) {
				//	$show_warning = true;
				//	$fields['0/email_addresses_not_overridden']['row_class'] = 'warning';
				//} else {
				//	//Note: only show this message if it's in the error state; hide it otherwise
				//	$fields['0/email_addresses_not_overridden']['hidden'] = true;
				//}
			} else {
				//$fields['0/email_addresses_not_overridden']['hidden'] = true;
			
				//Reverse of the above;
				//Warn production sites that level a high level of error reporting on
				if ((ERROR_REPORTING_LEVEL & E_NOTICE)
				 || (ERROR_REPORTING_LEVEL & E_STRICT)) {
					$show_warning = true;
					$fields['0/notices_shown']['hidden'] = false;
					$fields['0/notices_shown']['row_class'] = 'warning';
				}
			
			}
		
			//Check to see if production sites have the debug_override_enable setting enabled
			if (\ze::setting('debug_override_enable')) {
				$mrg = [
					'email' => htmlspecialchars(\ze::setting('debug_override_email_address')),
					'link' => htmlspecialchars('organizer.php#zenario__administration/panels/site_settings//email~.site_settings~tdebug~k{"id"%3A"email"}')];
				
				$show_warning = true;
				$fields['0/email_addresses_overridden']['row_class'] = 'warning';
				$fields['0/email_addresses_overridden']['snippet']['html'] =
					\ze\admin::phrase('You have &ldquo;Email debug mode&rdquo; enabled in <a href="[[link]]" target="_blank"><em>Email</em> in the site settings</a>. Email sent by this site will be redirected to &quot;[[email]]&quot;.', $mrg);
			} else {
				$fields['0/email_addresses_overridden']['hidden'] = true;
			}
			
			
			//Check for missing modules
			$missingModules = [];
			foreach(\ze\row::getArray('modules', ['class_name', 'display_name'], ['status' => 'module_running'], 'class_name') as $module) {
				if (!\ze::moduleDir($module['class_name'], 'module_code.php', true)) {
					$missingModules[$module['class_name']] = \ze\admin::phrase('[[display_name|escape]] (<code>[[class_name|escape]]</code>)', $module);
				}
			}
			
			if (!$fields['0/missing_modules']['hidden'] = empty($missingModules)) {
				$show_error = true;
				$fields['0/missing_modules']['row_class'] = 'invalid';
				$fields['0/missing_modules']['snippet']['html'] =
					\ze\admin::phrase('Files for the following modules are missing:').
					'<ul><li>'.
						implode('</li><li>', $missingModules).
					'</li></ul>';
			}
		
			$badSymlinks = [];
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
		
			if (!$fields['0/bad_extra_module_symlinks']['hidden'] = empty($badSymlinks)) {
				$fields['0/bad_extra_module_symlinks']['row_class'] = 'warning';
				$show_warning = true;
			
				$mrg = ['module' => array_pop($badSymlinks), 'version' => ZENARIO_MAJOR_VERSION. '.'. ZENARIO_MINOR_VERSION];
				if (empty($badSymlinks)) {
					$fields['0/bad_extra_module_symlinks']['snippet']['html'] =
						\ze\admin::phrase('The <code>[[module]]</code> symlink in the <code>zenario_extra_modules/</code> directory is linked to the wrong version of Zenario. It should be linked to version [[version]].', $mrg);
				} else {
					$mrg['modules'] = implode('</code>, <code>', $badSymlinks);
					$fields['0/bad_extra_module_symlinks']['snippet']['html'] =
						\ze\admin::phrase('The <code>[[modules]]</code> and <code>[[module]]</code> symlinks in the <code>zenario_extra_modules/</code> directory are linked to the wrong version of Zenario. They should be linked to version [[version]].', $mrg);
				}
			}
		
			$moduleErrors = '';
			\ze\dbAdm::checkIfUpdatesAreNeeded($moduleErrors, $andDoUpdates = false);
		
			if (!$fields['0/module_errors']['hidden'] = !$moduleErrors) {
				$show_error = true;
				$fields['0/module_errors']['row_class'] = 'invalid';
				$fields['0/module_errors']['snippet']['html'] = nl2br(htmlspecialchars($moduleErrors));
			}
			
			
			//Check if extranet sites have two-factor authentication enabled
			$warnAboutThis = 
				!\ze\site::description('enable_two_factor_authentication_for_admin_logins')
			 && \ze\row::exists('modules', [
					'status' => ['module_running', 'module_suspended'],
					'class_name' => ['zenario_extranet', 'zenario_users', 'zenario_user_forms']
				]);
			
			if (!$fields['0/two_factor_security']['hidden'] = !$warnAboutThis) {
				$show_warning = true;
				$fields['0/two_factor_security']['row_class'] = 'warning';
			}
			
			
			//Check a site accepts password without SSL
			$warnAboutThis = 
				!\ze\link::isHttps()
			 && \ze\row::exists('modules', [
					'status' => ['module_running', 'module_suspended'],
					'class_name' => 'zenario_extranet'
				]);
			
			if (!$fields['0/no_ssl_for_login']['hidden'] = !$warnAboutThis) {
				$show_warning = true;
				$fields['0/no_ssl_for_login']['row_class'] = 'warning';
			}
			
			
			//Check if extranet sites use SSL
			$warnAboutThis = false;
			if (!\ze\site::description('enable_two_factor_authentication_for_admin_logins')) {
				
				$sql = "
					SELECT 1
					FROM ". DB_NAME_PREFIX. "modules
					WHERE `status` IN ('module_running','module_suspended')
					  AND class_name IN ('zenario_extranet', 'zenario_users', 'zenario_user_forms')
					LIMIT 1";
				
				$warnAboutThis = (bool) \ze\sql::fetchRow($sql);
			}
			if (!$fields['0/two_factor_security']['hidden'] = !$warnAboutThis) {
				$show_warning = true;
				$fields['0/two_factor_security']['row_class'] = 'warning';
			}
			
			
			//Do some basic checks on the robots.txt file
			$robotsDotTextError = false;
			if (!file_exists(CMS_ROOT. 'robots.txt')) {
				$robotsDotTextError =
					\ze\admin::phrase('The <code>robots.txt</code> file for this site is missing.');
			
			} elseif ($robotsDotTextContents = self::getTrimmedFileContents(CMS_ROOT. 'robots.txt')) {
				
				if (strpos($robotsDotTextContents, ' User-agent:* Disallow:/ ') !== false) {
					$robotsDotTextError =
						\ze\admin::phrase('This site has a <code>robots.txt</code> that is blocking search engine indexing.');
				
				} else {
				
					if (($standardRobotsDotTextContents = self::getTrimmedFileContents(CMS_ROOT. 'zenario/includes/test_files/default_robots.txt'))
					 && ($standardRobotsDotTextContents != $robotsDotTextContents)) {
						$robotsDotTextError =
							\ze\admin::phrase('This site has a <code>robots.txt</code> that has non-standard modifications.');
					}
				}
			}
			if (!$fields['0/robots_txt']['hidden'] = !$robotsDotTextError) {
				$show_warning = true;
				$fields['0/robots_txt']['row_class'] = 'warning';
				$fields['0/robots_txt']['snippet']['html'] = $robotsDotTextError;
			}
			
			
			
			if ($show_error) {
				$fields['0/show_site']['pressed'] = true;
				$fields['0/site']['row_class'] = 'section_invalid';
		
			} elseif ($show_warning) {
				$fields['0/show_site']['pressed'] = true;
				$fields['0/site']['row_class'] = 'section_warning';
			}
			
			
			
			//
			// Go through all of the checks in the site content section
			//
			
			$show_warning = false;
			$show_error = false;
			
			
			$fields['0/content_unpublished_1']['row_class'] =
			$fields['0/content_unpublished_2']['row_class'] =
			$fields['0/content_unpublished_3']['row_class'] =
			$fields['0/content_unpublished_4']['row_class'] =
			$fields['0/content_unpublished_5']['row_class'] =
			$fields['0/content_more_unpublished']['row_class'] =
			$fields['0/content_nothing_unpublished']['row_class'] = 'valid';
			$fields['0/content_unpublished_1']['hidden'] =
			$fields['0/content_unpublished_2']['hidden'] =
			$fields['0/content_unpublished_3']['hidden'] =
			$fields['0/content_unpublished_4']['hidden'] =
			$fields['0/content_unpublished_5']['hidden'] =
			$fields['0/content_more_unpublished']['hidden'] = true;
			$fields['0/content_nothing_unpublished']['hidden'] = false;
		
			$sql = "
				SELECT c.id, c.type, c.alias, c.language_id, c.status
				FROM ". DB_NAME_PREFIX. "content_items AS c
				INNER JOIN ". DB_NAME_PREFIX. "content_item_versions AS v
				   ON c.id = v.id
				  AND c.type = v.type
				  AND c.admin_version = v.version
				WHERE c.status IN ('first_draft','published_with_draft','hidden_with_draft','trashed_with_draft')
				ORDER BY last_modified_datetime DESC";
			
			$result = \ze\sql::select($sql);
			
			if ($rowCount = \ze\sql::numRows($result)) {
				$fields['0/content_nothing_unpublished']['hidden'] = true;
				$show_warning = true;
				$i = 0;
				while ($row = \ze\sql::fetchAssoc($result)) {
					
					if (++$i > 5) {
						$row['link'] = 'organizer.php#zenario__content/panels/content/refiners/work_in_progress////';
						
						$fields['0/content_more_unpublished']['hidden'] = false;
						$fields['0/content_more_unpublished']['row_class'] = 'warning';
						$fields['0/content_more_unpublished']['snippet']['html'] =
							\ze\admin::nPhrase('[[count]] other page is in draft mode. <a target="blank" href="[[link]]">View...</a>',
								'[[count]] other pages are in draft mode. <a target="blank" href="[[link]]">View...</a>',
								$rowCount - 5, $row);
					
					} else {
						$row['tag'] = htmlspecialchars(\ze\content::formatTag($row['id'], $row['type'], $row['alias'], $row['language_id']));
						$row['link'] = htmlspecialchars(\ze\link::toItem($row['id'], $row['type'], true));
						$row['class'] = 'organizer_item_image '. \ze\contentAdm::getItemIconClass($row['id'], $row['type'], true, $row['status']);
			
						$fields['0/content_unpublished_'. $i]['hidden'] = false;
						$fields['0/content_unpublished_'. $i]['row_class'] = 'warning';
						$fields['0/content_unpublished_'. $i]['snippet']['html'] =
							\ze\admin::phrase('<a target="blank" href="[[link]]"><span class="[[class]]"></span>[[tag]]</a> is in draft mode.', $row);
					}
				}
			}
			
			
			if ($show_error) {
				$fields['0/show_content']['pressed'] = true;
				$fields['0/content']['row_class'] = 'section_invalid';
		
			} elseif ($show_warning) {
				$fields['0/show_content']['pressed'] = true;
				$fields['0/content']['row_class'] = 'section_warning';
			}
		}
	
	
	
	
	
	
	
		//Strip any trailing slashes off of a directory path
		$values['0/backup_dir'] = preg_replace('/[\\\\\\/]+$/', '', $values['0/backup_dir']);
		$values['0/docstore_dir'] = preg_replace('/[\\\\\\/]+$/', '', $values['0/docstore_dir']);
	
	
		//On multisite sites, don't allow local Admins to change the directory paths
		if (\ze\db::hasGlobal() && !($_SESSION['admin_global_id'] ?? false)) {
			$_SESSION['zenario_installer_disallow_changes_to_dirs'] = true;
	
		//Only allow changes to the directories if they were not correctly set to start with
		} elseif (!isset($_SESSION['zenario_installer_disallow_changes_to_dirs'])) {
			$_SESSION['zenario_installer_disallow_changes_to_dirs'] =
				$fields['0/backup_dir_status']['row_class'] == 'sub_valid'
			 && $fields['0/docstore_dir_status']['row_class'] == 'sub_valid';
		}
	
		if ($_SESSION['zenario_installer_disallow_changes_to_dirs']) {
			$fields['0/backup_dir']['readonly'] = true;
			$fields['0/docstore_dir']['readonly'] = true;
	
		} else {
			if ($fields['0/backup_dir_status']['row_class'] == 'sub_valid') {
				\ze\site::setSetting('backup_dir', $values['0/backup_dir']);
			}
		
			if ($fields['0/docstore_dir_status']['row_class'] == 'sub_valid') {
				\ze\site::setSetting('docstore_dir', $values['0/docstore_dir']);
			}
		}
	
	
		\ze\welcome::systemRequirementsAJAX($source, $tags, $fields, $values, $changes, $isDiagnosticsPage = true);
	
	
		$everythingIsOkay =
			$fields['0/dirs']['row_class'] == 'section_valid'
		 && $fields['0/site']['row_class'] == 'section_valid'
		 && $fields['0/content']['row_class'] == 'section_valid'
		 && $fields['0/system_requirements']['row_class'] == 'section_valid';
	
		if (!$everythingIsOkay) {
			$showCheckAgainButton = true;
		}
	
	
		//If all of the directory info valid (or uneditable due to not being a super-admin),
		//only show one button as there is nothing to save or recheck
		$fields['0/check_again']['hidden'] =
			!$showCheckAgainButton
		 && (!$showCheckAgainButtonIfDirsAreEditable || $_SESSION['zenario_installer_disallow_changes_to_dirs']);
	
	
		$wasFirstViewing = $tags['key']['first_viewing'];
		$tags['key']['first_viewing'] = false;
	
	
	
	
		//If everything is valid, do not show this screen unless it was shown previously,
		//or the admin specificly requested it by clicking on the stethoscope button
		if ($everythingIsOkay && $wasFirstViewing) {
			if ($task != 'diagnostics') {
				unset($_SESSION['zenario_installer_disallow_changes_to_dirs']);
				return true;
			} else {
				//Don't show all of the toggles closed to start with, if everything is green
				//open the "Site configuration" and "Site content" sections by default in this case.
				$fields['0/show_site']['pressed'] = true;
				$fields['0/show_content']['pressed'] = true;
			}
		}
	
		//Continue on from this step if the "Continue" button is pressed
		if (!empty($fields['0/continue']['pressed'])) {
			unset($_SESSION['zenario_installer_disallow_changes_to_dirs']);
			return true;
	
		} else {
			//If the Admin has pressed the "Save and Continue" button...
			if (!empty($fields['0/check_again']['pressed'])) {
				//If the directory section is valid, also don't show this screen unless it was shown previously
				if ($fields['0/dirs']['row_class'] == 'section_valid'
				 && empty($fields['0/check_again']['pressed'])) {
					unset($_SESSION['zenario_installer_disallow_changes_to_dirs']);
					return true;
			
				//Otherwise shake the screen
				} else {
					$tags['shake'] = true;
				}
			}
		
			return false;
		}
	}
	
	public static function getTrimmedFileContents($path) {
		
		if ($contents = @file_get_contents($path)) {
			return preg_replace('@[\n\r]+@', ' ', preg_replace('@[^\S\n\r]@', '', "\n". $contents. "\n"));
		} else {
			return false;
		}
	}

	//Formerly "protectBackupAndDocstoreDirsIfPossible()"
	public static function protectBackupAndDocstoreDirsIfPossible() {
		foreach ([\ze::setting('backup_dir'), \ze::setting('docstore_dir')] as $dir) {
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

	//Formerly "licenseExpiryDate()"
	public static function licenseExpiryDate($file) {
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

	//Formerly "congratulationsAJAX()"
	public static function congratulationsAJAX(&$source, &$tags, &$fields, &$values, $changes) {
		$fields['0/link']['snippet']['html'] =
			'<a href="'. \ze\link::protocol(). $_SERVER['HTTP_HOST']. SUBDIRECTORY. '">'. \ze\link::protocol(). $_SERVER['HTTP_HOST']. SUBDIRECTORY. '</a>';
	}




	//Formerly "redirectAdmin()"
	public static function redirectAdmin($getRequest, $forceAliasInAdminMode = false) {
		
		$cID = $cType = $redirectNeeded = $aliasInURL = false;
		if (!empty($getRequest)) {
			\ze\content::resolveFromRequest($cID, $cType, $redirectNeeded, $aliasInURL, $getRequest, $getRequest, []);
		}
		
		$domain = ($forceAliasInAdminMode || !\ze\priv::check())? \ze\link::primaryDomain() : \ze\link::adminDomain();
	
		if (!empty($getRequest['og']) && \ze\priv::check()) {
			return
				'zenario/admin/organizer.php'.
				(isset($getRequest['fromCID']) && isset($getRequest['fromCType'])? '?fromCID='. $getRequest['fromCID']. '&fromCType='. $getRequest['fromCType'] : '').
				'#'. $getRequest['og'];
		
		} elseif (!empty($getRequest['desturl']) && \ze\priv::check()) {
			return \ze\link::protocol(). $domain. $getRequest['desturl'];
		
		}
		
		if (!$cID && !empty($_SESSION['destCID'])) {
			$cID = $_SESSION['destCID'];
			$cType = $_SESSION['destCType'] ?? 'html';
		}
	
		if ($cID && \ze\content::checkPerm($cID, $cType)) {
		
			unset($getRequest['task'], $getRequest['cID'], $getRequest['cType'], $getRequest['cVersion']);
		
			return \ze\link::toItem($cID, $cType, true, http_build_query($getRequest), false, false, $forceAliasInAdminMode);
		} else {
			return (DIRECTORY_INDEX_FILENAME ?: SUBDIRECTORY);
		}
	}


	//Formerly "lastAutomatedBackupTimestamp()"
	public static function lastAutomatedBackupTimestamp($automated_backup_log_path = false) {
	
		if ($automated_backup_log_path === false) {
			$automated_backup_log_path = \ze::setting('automated_backup_log_path');
		}
	
		//Attempt to get the date of the last backup from
		$timestamp = 0;
		ini_set('auto_detect_line_endings', true);
		if ($f = fopen($automated_backup_log_path, 'r')) {
			while ($line = fgets($f)) {
				if (trim($line) != ''
				 && ($lineValues = str_getcsv($line))
				 && ($lineValues[0])
				 && ($lineValues[2]? DBNAME == $lineValues[2] : DBNAME == $lineValues[1])
				 && ($time = new \DateTime($lineValues[0]))) {
					$timestamp = max($timestamp, $time->getTimestamp());
				}
			}
		}
	
		return $timestamp;
	}





	//Formerly "deleteNamedPluginSetting()"
	public static function deleteNamedPluginSetting($moduleClassName, $settingName) {
	
		$sql = "
			DELETE ps.*
			FROM `". DB_NAME_PREFIX. "modules` AS m
			INNER JOIN `". DB_NAME_PREFIX. "plugin_instances` AS pi
			   ON m.id = pi.module_id
			INNER JOIN `". DB_NAME_PREFIX. "plugin_settings` AS ps
			   ON pi.id = ps.instance_id
			  AND ps.egg_id = 0
			  AND ps.name = '". \ze\escape::sql($settingName). "'
			WHERE m.class_name = '". \ze\escape::sql($moduleClassName). "'";
		\ze\sql::update($sql);

		$sql = "
			DELETE ps.*
			FROM `". DB_NAME_PREFIX. "modules` AS m
			INNER JOIN `". DB_NAME_PREFIX. "nested_plugins` AS np
			   ON m.id = np.module_id
			INNER JOIN `". DB_NAME_PREFIX. "plugin_settings` AS ps
			   ON ps.egg_id = np.id
			  AND ps.name = '". \ze\escape::sql($settingName). "'
			WHERE m.class_name = '". \ze\escape::sql($moduleClassName). "'";
		\ze\sql::update($sql);
	}

}