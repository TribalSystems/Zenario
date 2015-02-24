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


//
// Functions for connecting to a database, and running basic queries
//


function connectLocalDB() {
	
	if (cms_core::$localDB) {
		cms_core::$lastDB = cms_core::$localDB;
		cms_core::$lastDBHost = DBHOST;
		cms_core::$lastDBName = DBNAME;
		cms_core::$lastDBPrefix = DB_NAME_PREFIX;
		return;
	}
	
	if (!$dbSelected = connectToDatabase(DBHOST, DBNAME, DBUSER, DBPASS)) {
		if (!defined('SHOW_SQL_ERRORS_TO_VISITORS') || SHOW_SQL_ERRORS_TO_VISITORS !== true) {
			echo 'A database error has occured on this section of the site. Please contact a site Administrator.';
			exit;
		
		} else {
			echo "<p>Sorry, there was a database error. Could not connect to the database using:<ul>
				<li>DBHOST = ". DBHOST ."</li>
				<li>DBNAME = ". DBNAME ."</li>
				<li>DBUSER = ". DBUSER ."</li>
			</ul></p>";
			exit;
		}
	}
	
	cms_core::$localDB =
	cms_core::$lastDB = $dbSelected;
	cms_core::$lastDBHost = DBHOST;
	cms_core::$lastDBName = DBNAME;
	cms_core::$lastDBPrefix = DB_NAME_PREFIX;
	
	return true;
}


function globalDBDefined() {
	return defined('DBHOST_GLOBAL') && defined('DBNAME_GLOBAL') && defined('DBUSER_GLOBAL') && defined('DBPASS_GLOBAL') && defined('DB_NAME_PREFIX_GLOBAL')
			&& (DBHOST_GLOBAL != DBHOST || DBNAME_GLOBAL != DBNAME);
}

function connectGlobalDB() {
	
	if (!globalDBDefined()) {
		return false;
	}
	
	if (cms_core::$globalDB) {
		cms_core::$lastDB = cms_core::$globalDB;
		cms_core::$lastDBHost = DBHOST_GLOBAL;
		cms_core::$lastDBName = DBNAME_GLOBAL;
		cms_core::$lastDBPrefix = DB_NAME_PREFIX_GLOBAL;
		return true;
	}
	
	if ((!$dbSelected = connectToDatabase(DBHOST_GLOBAL, DBNAME_GLOBAL, DBUSER_GLOBAL, DBPASS_GLOBAL))) {
		if (!defined('SHOW_SQL_ERRORS_TO_VISITORS') || SHOW_SQL_ERRORS_TO_VISITORS !== true) {
			echo 'A database error has occured on this section of the site. Please contact a site Administrator.';
			exit;
		
		} else {
			echo "Sorry, there was a database error. Could not connect to global database using:<ul>
				<li>DBHOST_GLOBAL = ". DBHOST_GLOBAL ."</li>
				<li>DBNAME_GLOBAL = ". DBNAME_GLOBAL ."</li>
				<li>DBUSER_GLOBAL = ". DBUSER_GLOBAL ."</li>
			</ul>";
			exit;
		}
	}
	
	cms_core::$globalDB =
	cms_core::$lastDB = $dbSelected;
	cms_core::$lastDBHost = DBHOST_GLOBAL;
	cms_core::$lastDBName = DBNAME_GLOBAL;
	cms_core::$lastDBPrefix = DB_NAME_PREFIX_GLOBAL;
	return true;
}


function connectToDatabase($dbhost = 'localhost', $dbname, $dbuser, $dbpass, $reportErrors = true) {
	$errorText = 'Database connection failure';
	
	try {
		if ($dbconnection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname)) {
			if (mysqli_query($dbconnection,'SET NAMES "UTF8"')
			 && mysqli_query($dbconnection,"SET collation_connection='utf8_general_ci'")
			 && mysqli_query($dbconnection,"SET collation_server='utf8_general_ci'")
			 && mysqli_query($dbconnection,"SET character_set_client='utf8'")
			 && mysqli_query($dbconnection,"SET character_set_connection='utf8'")
			 && mysqli_query($dbconnection,"SET character_set_results='utf8'")
			 && mysqli_query($dbconnection,"SET character_set_server='utf8'")) {
				return $dbconnection;
			}
		}
	} catch (Exception $e) {
	}
	
	if ($reportErrors) {
		reportDatabaseError($errorText, mysqli_errno($dbconnection), mysqli_error($dbconnection));
	}
	
	return false;
}

function loadSiteConfig() {

	//Connect to the database
	connectLocalDB();
	if (defined('DEBUG_USE_STRICT_MODE') && DEBUG_USE_STRICT_MODE) {
		sqlSelect("SET @@SESSION.sql_mode = 'STRICT_ALL_TABLES'");
	}
	
	//Don't directly show a Content Item if a major Database update needs to be applied
	if (defined('CHECK_IF_MAJOR_REVISION_IS_NEEDED')) {
		$sql = "
			SELECT 1
			FROM ". DB_NAME_PREFIX. "local_revision_numbers
			WHERE path = 'admin/db_updates/core'
			  AND revision_no >= ". (int) LATEST_BIG_CHANGE_REVISION_NO. "
			LIMIT 1";
		
		if (!($result = sqlQuery($sql)) || !(sqlFetchRow($result))) {
			require_once CMS_ROOT. 'zenario/includes/cms.inc.php';
			showStartSitePageIfNeeded(true);
			exit;
		}
		unset($result);
	}
	
	//Load the full site settings.
	$sql = "
		SELECT name, IFNULL(value, default_value)
		FROM ". DB_NAME_PREFIX. "site_settings
		WHERE name NOT IN ('site_disabled_title', 'site_disabled_message')";
	$result = sqlQuery($sql);
	while ($row = sqlFetchRow($result)) {
		cms_core::$siteConfig[$row[0]] = $row[1];
	}
	
	//Load information on the special pages and their language equivalences
	$sql = "
		SELECT sp.page_type, c.equiv_id, c.language_id, c.id, c.type
		FROM ". DB_NAME_PREFIX. "special_pages AS sp
		INNER JOIN ". DB_NAME_PREFIX. "content AS c
		   ON c.equiv_id = sp.equiv_id
		  AND c.type = sp.content_type";
	
	$result = sqlQuery($sql);
	while ($row = sqlFetchAssoc($result)) {
		if ($row['id'] ==  $row['equiv_id']) {
			
			if ($row['page_type'] == 'zenario_home') {
				cms_core::$homeCID = $row['id'];
				cms_core::$homeCType = $row['type'];
			}
			
			cms_core::$specialPages[$row['page_type']] = $row['type']. '_'. $row['id'];
		} else {
			cms_core::$specialPages[$row['page_type']. '`'. $row['language_id']] = $row['type']. '_'. $row['id'];
		}
	}
	
	//Load a list of languages whose phrases need translating
	cms_core::$translateLanguages = getRowsArray('languages', 'translate_phrases', array('translate_phrases' => 1));
	
	//Check whether we should show error messages or not
	if (!defined('SHOW_SQL_ERRORS_TO_VISITORS')) {
		if (!empty($_SESSION['admin_logged_in']) || setting('show_sql_errors_to_visitors')) {
			define('SHOW_SQL_ERRORS_TO_VISITORS', true);
		} else {
			define('SHOW_SQL_ERRORS_TO_VISITORS', false);
		}
	}
}

//Old database functionality, please don't use any more
function my_mysql_query($sql, $updateDataRevisionNumber = -1, $checkCache = true, $return = 'sqlSelect') {
	
	if ($return === true || $return === 'mysql_affected_rows' || $return === 'mysql_affected_rows()' || $return === 'sqlAffectedRows' || $return === 'sqlAffectedRows()') {
		if (sqlUpdate($sql, $checkCache)) {
			return sqlAffectedRows();
		}
	
	} elseif ($return === 'mysql_insert_id' || $return === 'mysql_insert_id()' || $return === 'sqlInsertId' || $return === 'sqlInsertId()') {
		if (sqlUpdate($sql, $checkCache)) {
			return sqlInsertId();
		}
	
	} else {
		return sqlQuery($sql, $checkCache);
	}
	
	return false;
}

function handleDatabaseError($dbconnection, $sql) {
	$sqlErrno = mysqli_errno($dbconnection);
	$sqlError = mysqli_error($dbconnection);
	
	if (defined('RUNNING_FROM_COMMAND_LINE')) {
		echo "Database query error\n\n". $sqlErrno. "\n\n". $sqlError. "\n\n". $sql. "\n\n";
		exit;
	}
	
	if (defined('DEBUG_SEND_EMAIL') && DEBUG_SEND_EMAIL === true) {
		reportDatabaseError("Database query error", $sqlErrno, $sqlError, $sql, print_r(debug_backtrace(), true));
	}
	
	if (!defined('SHOW_SQL_ERRORS_TO_VISITORS') || SHOW_SQL_ERRORS_TO_VISITORS !== true) {
		echo 'A database error has occured on this section of the site. Please contact a site Administrator.';
		exit;
	}
	
	echo "<div id=\"error_information\">
Database query error: ".$sqlErrno.", ".$sqlError.",\n$sql\n\n
Trace-back information:\n";
	
	print_r(debug_backtrace());
	
	echo '
		<!-- Dont show this bit in the source -->
		<br /><br />
		<a href="#" onClick="
			this.innerHTML = \'\';
			document.body.innerHTML = 
				\'<textarea style=&quot;height: 95%; min-height: 600px; width:95%; min-width: 800px;&quot;>\' +
					document.getElementById(\'error_information\').innerHTML.substring(0,
						document.getElementById(\'error_information\').innerHTML.indexOf(
							\'<!-- Dont show this bit in the source -->\'
						)
					) + 
				\'<\' + \'/\' + \'textarea>\';
			return false;
		">
			<b>Click here to see this error message in a textarea</b>
		</a>
	</div>';
	
	exit;
}

function reportDatabaseError($errtext="", $errno="", $error="", $sql="", $backtrace = "") {
	
	$subject = "Error at ".$_SERVER['HTTP_HOST'];
	$body = visitorIP(). " accessing ". $_SERVER['REQUEST_URI']. "\n\n". $errtext. "\n\n". $errno. "\n\n". $error. "\n\n". $sql. "\n\n". $backtrace. "\n\n";
	
	// Mail it
	require_once CMS_ROOT. 'zenario/api/system_functions.inc.php';
	$addressToOverriddenBy = false;
	
	//A little hack - don't allow sendEmail() to connect to the database
	$lastDB = cms_core::$lastDB;
	cms_core::$lastDB = false;
	
	sendEmail(
		$subject, $body,
		EMAIL_ADDRESS_GLOBAL_SUPPORT,
		$addressToOverriddenBy,
		$nameTo = false,
		$addressFrom = false,
		$nameFrom = false,
		false, false, false,
		$isHTML = false);
	
	cms_core::$lastDB = $lastDB;
}


//Update the data revision number
//It's designed to update the local revision number if we're connected to the local database,
//otherwise update the global revision number if we're connected to the global database
function updateDataRevisionNumber() {
	
	//We only need to do this at most once per request/load
	if (!defined('ZENARIO_INCREASING_DATA_REVISION_NUMBER')) {
		define('ZENARIO_INCREASING_DATA_REVISION_NUMBER', true);
		register_shutdown_function('updateDataRevisionNumber2');
	}
}

function updateDataRevisionNumber2() {
	connectLocalDB();
	
	if (($result = mysqli_query(cms_core::$localDB, "SHOW TABLES LIKE '". DB_NAME_PREFIX. "local_revision_numbers'"))
	 && (mysqli_fetch_row($result))) {
		$sql = "
			INSERT INTO ". DB_NAME_PREFIX. "local_revision_numbers SET
				path = 'data_rev',
				patchfile = 'data_rev',
				revision_no = 1
			ON DUPLICATE KEY UPDATE
				revision_no = MOD(revision_no + 1, 4294960000)";
		mysqli_query(cms_core::$localDB, $sql);
	}
}



//
// Functions for Super Admins
//


//Look for and update the copy of the Global Admins in the local table
function syncSuperAdmin($adminIdG) {
	return require funIncPath(__FILE__, __FUNCTION__);
}




//
// Basic functions for initialising Modules
//


function checkForChangesInPhpFiles() {
	
	//Safety catch - do not try to do anything if there is no database connection!
	if (!cms_core::$lastDB) {
		return;
	}
	
	require_once CMS_ROOT. 'zenario/api/system_functions.inc.php';
	
	//Make sure we are in the CMS root directory.
	//This should already be done, but I'm being paranoid...
	chdir(CMS_ROOT);
	
	$time = time();
	
	//Get the date of the last time we ran this check and there was a change.
	if (!($lastChanged = (int) setting('php_files_last_changed'))) {
		//If this has never been run before then it must be run now!
		$changed = true;
	
	//Otherwise, work out the time difference between that time and now
	} else {
		
		try {
			//Check to see if there are any .xml or .yaml files that have changed on the system
			$find =
				' -name "*.php"'.
				' -not -path "./cache/*"'.
				' -not -path "./public/*"'.
				' -not -path "./private/*"'.
				' -not -path "*/.*"'.
				' -print'.
				' | sed 1q';
			
			//If possble, try to use the UNIX shell
			switch (PHP_OS) {
				case 'Linux':
					$changed = exec('find -L . -newermt @'. (int) $lastChanged. $find);
					$useFallback = false;
					break;
				
				case 'Darwin':
					$ago = $time - $lastChanged;
					$changed = exec('find -L . -mtime -'. (int) $ago. 's'. $find);
					$useFallback = false;
					break;
				
				default:
					$useFallback = true;
			}
	
		} catch (Exception $e) {
			$useFallback = true;
		}
		
		//If we couldn't use the command line, we'll need to do roughly the same logic using PHP functions
		if ($useFallback) {
			$dirs = array('zenario', 'zenario/admin', 'zenario/api', 'zenario/includes');
			foreach (array('', 'fun/', 'classes/admin_boxes/', 'classes/organizer/') as $dir) {
				foreach (moduleDirs($dir) as $dir2) {
					$dirs[] = $dir2;
				}
			}
			
			$changed = false;
			foreach ($dirs as $dir) {
				chdir(CMS_ROOT. $dir);
					foreach (array_map('filemtime', scandir('.')) as $mtime) {
						if ($mtime > $lastChanged) {
							$changed = true;
							break 2;
						}
					}
				chdir(CMS_ROOT);
			}
		}
	}
	chdir(CMS_ROOT);
	
	
	if ($changed) {
		setSetting('php_files_last_changed', $time);
		setSetting('php_version', hash64(setting('site_id'). $time));
	}
}


function checkForChangesInYamlFiles() {
	
	//Safety catch - do not try to do anything if there is no database connection!
	if (!cms_core::$lastDB) {
		return;
	}
	
	//Make sure we are in the CMS root directory.
	//This should already be done, but I'm being paranoid...
	chdir(CMS_ROOT);
	
	$time = time();
	
	//Get the date of the last time we ran this check and there was a change.
	if (!($lastChanged = (int) setting('yaml_files_last_changed'))) {
		//If this has never been run before then it must be run now!
		$changed = true;
	
	//Otherwise, work out the time difference between that time and now
	} else {
		
		try {
			//Check to see if there are any .xml or .yaml files that have changed on the system
			$find =
				' -path "*modules/*/tuix/*.*ml"'.
				' -not -path "./cache/*"'.
				' -not -path "./public/*"'.
				' -not -path "./private/*"'.
				' -not -path "*/.*"'.
				' -print'.
				' | sed 1q';
			
			//If possble, try to use the UNIX shell
			switch (PHP_OS) {
				case 'Linux':
					$changed = exec('find -L . -newermt @'. (int) $lastChanged. $find);
					$useFallback = false;
					break;
				
				case 'Darwin':
					$ago = $time - $lastChanged;
					$changed = exec('find -L . -mtime -'. (int) $ago. 's'. $find);
					$useFallback = false;
					break;
				
				default:
					$useFallback = true;
			}
	
		} catch (Exception $e) {
			$useFallback = true;
		}
		
		//If we couldn't use the command line, we'll need to do roughly the same logic using PHP functions
		if ($useFallback) {
			$changed = false;
			foreach (array('admin_boxes', 'admin_toolbar', 'slot_controls', 'organizer') as $type) {
				foreach (moduleDirs('tuix/'. $type. '/') as $tuixDir) {
					chdir(CMS_ROOT. $tuixDir);
						foreach (array_map('filemtime', scandir('.')) as $mtime) {
							if ($mtime > $lastChanged) {
								$changed = true;
								break 3;
							}
						}
					chdir(CMS_ROOT);
				}
			}
		}
	}
	chdir(CMS_ROOT);
	
	
	if ($changed) {
		//We'll need to be reading TUIX files, the functions needed for this are stored in admin.inc.php
		require_once CMS_ROOT. 'zenario/adminheader.inc.php';
		
		//Scan the TUIX files, and come up with a list of what paths are in what files
		$tuixFiles = array();
		$result = getRows('tuix_file_contents', true, array());
		while ($tf = sqlFetchAssoc($result)) {
			$key = $tf['module_class_name']. '/'. $tf['type']. '/'. $tf['filename'];
			$key2 = $tf['path']. '//'. $tf['setting_group'];
		
			if (empty($tuixFiles[$key])) {
				$tuixFiles[$key] = array();
			}
			$tuixFiles[$key][$key2] = $tf;
		}
	
		$contents = array();
		foreach (array('admin_boxes', 'admin_toolbar', 'slot_controls', 'organizer') as $type) {
			foreach (moduleDirs('tuix/'. $type. '/') as $moduleClassName => $dir) {
			
				if ($type == 'organizer') {
					$type = 'storekeeper';
				}
			
				foreach (scandir($dir) as $file) {
					if (substr($file, 0, 1) != '.') {
						$key = $moduleClassName. '/'. $type. '/'. $file;
						$filemtime = null;
						$md5_file = null;
						$changes = true;
						$first = true;
					
						//Check the modification time and the checksum of the file. If either are the same as before,
						//there's no need to update this row.
						if (!empty($tuixFiles[$key])) {
							foreach ($tuixFiles[$key] as $key2 => &$tf) {
							
								//Note that this is an array of arrays, but I only need to check the first one
								if ($first) {
									$filemtime = filemtime($dir. $file);
								
									if ($tf['last_modified'] == $filemtime) {
										$changes = false;
								
									} else {
										$md5_file = md5_file($dir. $file);
									
										if ($tf['checksum'] == $md5_file) {
											$changes = false;
										}
									}
								}
							
								//Note that this is an array of arrays, but I only need to check the first one
								if (!$changes) {
									$tf['status'] = 'unchanged';
								}
							}
							unset($tf);
						
							if (!$changes) {
								continue;
							}
						} else {
							$tuixFiles[$key] = array();
						}
					
						//If there have been changes, or if this is the first time we've seen this file,
						//read it, then loop through it looking for all of the TUIX paths it contains
							//Note that as we know there are changes, I'm overriding the normal timestamp logic in zenarioReadTUIXFile()
						if (($tags = zenarioReadTUIXFile($dir. $file, false))
						 && (!empty($tags))
						 && (is_array($tags))) {
						
							if ($filemtime === null) {
								$filemtime = filemtime($dir. $file);
							}
							if ($md5_file === null) {
								$md5_file = md5_file($dir. $file);
							}
						
							$pathsFound = false;
							if ($type == 'storekeeper' || $type == 'organizer') {
								//For Storekeeper, run zenarioAJAXShortenPath() to get their short paths
								$paths = array();
								logTUIXFileContentsR($paths, $tags, $type);
							
								foreach ($paths as $path => $dummy) {
									$pathsFound = true;
									$settingGroup = '';
								
									$key2 = $path. '//'. $settingGroup;
									$tuixFiles[$key][$key2] = array(
										'type' => $type,
										'path' => $path,
										'setting_group' => $settingGroup,
										'module_class_name' => $moduleClassName,
										'filename' => $file,
										'last_modified' => $filemtime,
										'checksum' => $md5_file,
										'status' => empty($tuixFiles[$key][$key2])? 'new' : 'updated'
									);
								}
							}
						
							if (!$pathsFound) {
								//For anything else, just read the top-level path
								//Note - also do this for Storekeeper if no paths were found above,
								//as logTUIXFileContentsR() will miss files that have navigation definitions but no panel definitions
								foreach ($tags as $path => &$tag) {
								
									$settingGroup = '';
									if ($type == 'admin_boxes') {
										if ($path == 'plugin_settings' && !empty($tag['module_class_name'])) {
											$settingGroup = $tag['module_class_name'];
									
										} elseif ($path == 'advanced_search' && !empty($tag['storekeeper_path'])) {
											$settingGroup = $tag['storekeeper_path'];
									
										} elseif ($path == 'site_settings' && !empty($tag['setting_group'])) {
											$settingGroup = $tag['setting_group'];
										}
								
									} elseif ($type == 'slot_controls') {
										if (!empty($tag['module_class_name'])) {
											$settingGroup = $tag['module_class_name'];
										}
									}
								
									$key2 = $path. '//'. $settingGroup;
									$tuixFiles[$key][$key2] = array(
										'type' => $type,
										'path' => $path,
										'setting_group' => $settingGroup,
										'module_class_name' => $moduleClassName,
										'filename' => $file,
										'last_modified' => $filemtime,
										'checksum' => $md5_file,
										'status' => empty($tuixFiles[$key][$key2])? 'new' : 'updated'
									);
								}
							}
						}
						unset($tags);
					}
				}
			}
		}
	
	
	
		//Loop through the array we've generated, and take actions as appropriate
		foreach ($tuixFiles as $key => &$tuixFile) {
			foreach ($tuixFile as $key2 => $tf) {
			
				//Where we could no longer find files, delete them
				if (empty($tf['status'])) {
					$sql = "
						DELETE FROM ". DB_NAME_PREFIX. "tuix_file_contents
						WHERE type = '". sqlEscape($tf['type']). "'
						  AND path = '". sqlEscape($tf['path']). "'
						  AND setting_group = '". sqlEscape($tf['setting_group']). "'
						  AND module_class_name = '". sqlEscape($tf['module_class_name']). "'
						  AND filename = '". sqlEscape($tf['filename']). "'";
					sqlSelect($sql);
			
				//Add/update newly added/edited files
				} else if ($tf['status'] != 'unchanged') {
					$sql = "
						INSERT INTO ". DB_NAME_PREFIX. "tuix_file_contents
						SET type = '". sqlEscape($tf['type']). "',
							path = '". sqlEscape($tf['path']). "',
							setting_group = '". sqlEscape($tf['setting_group']). "',
							module_class_name = '". sqlEscape($tf['module_class_name']). "',
							filename = '". sqlEscape($tf['filename']). "',
							last_modified = ". (int) $tf['last_modified']. ",
							checksum = '". sqlEscape($tf['checksum']). "'
						ON DUPLICATE KEY UPDATE
							last_modified = VALUES(last_modified),
							checksum = VALUES(checksum)";
					sqlSelect($sql);
				}
			
			}
		}
		
		setSetting('yaml_files_last_changed', $time);
		setSetting('yaml_version', hash64(setting('site_id'). $time));
	}
}

//Get all existing modules
function getModules($onlyGetRunningPlugins = false, $ignoreUninstalledPlugins = false, $dbUpdateSafemode = false, $orderBy = false) {
	return require funIncPath(__FILE__, __FUNCTION__);
}

//Get all of the existing modules that are running
function getRunningModules($dbUpdateSafemode = false, $orderBy = false) {
	return getModules($onlyGetRunningPlugins = true, false, $dbUpdateSafemode, $orderBy);
}


//Define a plugin's database table prefix
function setModulePrefix(&$module, $define = true, $oldFormat = false) {
	
	if (empty($module['class_name'])) {
		return false;
	}
	
	$module['prefix'] = strtoupper($module['class_name']). '_PREFIX';
	
	if ($define && defined($module['prefix'])) {
		return true;
	}
	
	if (!empty($module['module_id'])) {
		$id = $module['module_id'];
	
	} elseif (!empty($module['id'])) {
		$id = $module['id'];
	
	} else {
		return false;
	}
	
	if ($oldFormat === 1) {
		$prefix = 'plg'. $id. '_';
	
	} else {
		$className = $module['class_name'];
		
		if ($oldFormat === 2) {
			$oldFormat = false;
			$className = str_replace('zenario_', 'tribiq_', $className);
		}
		
		$prefix = 'mod'. $id. '_';
		foreach (explode('_', $className) as $frag) {
			$prefix .= $frag[0];
		}
		$prefix .= '_';
	}
	
	if ($define) {
		define($module['prefix'], $prefix);
		return true;
	} else {
		return $prefix;
	}
}


function reportDatabaseErrorFromHelperFunction($error) {
	echo adminPhrase('A database error has occured on this section of the site.'), "\n\n";
	
	if (defined('RUNNING_FROM_COMMAND_LINE') || (defined('SHOW_SQL_ERRORS_TO_VISITORS') && SHOW_SQL_ERRORS_TO_VISITORS === true)) {
		echo $error;
	} else {
		echo adminPhrase('Please contact a site Administrator.');
	}
		
	if (defined('DEBUG_SEND_EMAIL') && DEBUG_SEND_EMAIL === true) {
		reportDatabaseError(adminPhrase('Database query error'), '', $error, '', print_r(debug_backtrace(), true));
	}
	
	exit;
}

//Check a table definition and see which columns are numeric
function checkTableDefinition($prefixAndTable) {
	$pkCol = false;
	$exists = false;
	
	if (!isset(cms_core::$numericCols[$prefixAndTable])) {
		cms_core::$numericCols[$prefixAndTable] = array();
	}
	
	if ($result = sqlSelect('SHOW COLUMNS FROM `'. sqlEscape($prefixAndTable). '`')) {
		while ($row = sqlFetchRow($result)) { 
			$exists = true;
			$def = preg_split('/\W/', $row[1], 2);
			switch ($def[0]) {
				case 'tinyint':
				case 'smallint':
				case 'mediumint':
				case 'int':
				case 'integer':
				case 'bigint':
					cms_core::$numericCols[$prefixAndTable][$row[0]] = true;
					break;
				
				case 'float':
				case 'double':
				case 'decimal':
					cms_core::$numericCols[$prefixAndTable][$row[0]] = 1;
					break;
				
				case 'datetime':
				case 'date':
				case 'timestamp':
				case 'time':
				case 'year':
					cms_core::$numericCols[$prefixAndTable][$row[0]] = 0;
					break;
				
				default:
					cms_core::$numericCols[$prefixAndTable][$row[0]] = false;
			}
			
			//Also check to see if there is a single primary key column
			if ($row[3] == 'PRI') {
				if ($pkCol === false) {
					$pkCol = $row[0];
				} else {
					$pkCol = true;
				}
			}
		}
	}
	
	if (!$exists) {
		cms_core::$pkCols[$prefixAndTable] = '';
	
	} elseif ($pkCol !== false && $pkCol !== true) {
		cms_core::$pkCols[$prefixAndTable] = $pkCol;
	
	} else {
		cms_core::$pkCols[$prefixAndTable] = false;
	}
	
	return !empty(cms_core::$numericCols[$prefixAndTable]);
}

//Helper function for checkRowExists
function checkRowExistsCol(&$table, &$sql, &$col, &$val, &$first, $isWhere, $sign = '=', $in = 0) {
	
	if (!isset(cms_core::$numericCols[cms_core::$lastDBPrefix. $table][$col])) {
		checkTableDefinition(cms_core::$lastDBPrefix. $table);
	}
	
	if (!isset(cms_core::$numericCols[cms_core::$lastDBPrefix. $table][$col])) {
		reportDatabaseErrorFromHelperFunction(adminPhrase('The column `[[col]]` does not exist in the table `[[table]]`.', array('col' => $col, 'table' => cms_core::$lastDBPrefix. $table)));
	}
	
	
	if ($isWhere && is_array($val)) {
		$firstIn = true;
		foreach ($val as $sign2 => &$val2) {
			if (is_numeric($sign2) || substr($sign2, 0, 1) == '=') {
				if ($firstIn) {
					checkRowExistsCol($table, $sql, $col, $val2, $first, $isWhere, 'IN (', 1);
					$firstIn = false;
				} else {
					checkRowExistsCol($table, $sql, $col, $val2, $first, $isWhere, ', ', 2);
				}
			}
		}
		if (!$firstIn) {
			$sql .= ')';
		}
		
		foreach ($val as $sign2 => &$val2) {
			if (substr($sign2, 0, 1) == '!') {
				$sign2 = '!=';
			}
			if ($sign2 === '!=' || $sign2 === '<>' || $sign2 === '<=' || $sign2 === '<' || $sign2 === '>' || $sign2 === '>=') {
				checkRowExistsCol($table, $sql, $col, $val2, $first, $isWhere, $sign2);
			}
		}
		
		return;
	}
	
	
	if ($in <= 1) {
		if (!$isWhere) {
			$sql .= ($first? '' : ','). '
				';
		} elseif ($first) {
			$sql .= '
			WHERE ';
		} else {
			$sql .= '
			  AND ';
		}
		$first = false;
		
		$sql .= '`'. sqlEscape($col). '` ';
	}
	
	if ($val === null || (!$val && cms_core::$numericCols[cms_core::$lastDBPrefix. $table][$col] === 0)) {
		if ($in) {
			$sql .= $sign. 'NULL';
		
		} elseif (!$isWhere) {
			$sql .= '= NULL';
		
		} elseif ($sign == '=') {
			$sql .= 'IS NULL';
		
		} else {
			$sql .= 'IS NOT NULL';
		}
	
	} elseif (!cms_core::$numericCols[cms_core::$lastDBPrefix. $table][$col]) {
		$sql .= $sign. ' \''. sqlEscape((string) $val). '\'';
	
	} elseif (cms_core::$numericCols[cms_core::$lastDBPrefix. $table][$col] === 1) {
		$sql .= $sign. ' '. (float) $val;
	
	} else {
		$sql .= $sign. ' '. (int) $val;
	}
	
}


//Declare a function to check if something exists in the database
function checkRowExists(
	$table, $ids,
	$cols = false, $notZero = false, $multiple = false, $delete = false, $orderBy = array(),
	$distinct = false, $array = false, $addId = false
) {
	
	if (!isset(cms_core::$numericCols[cms_core::$lastDBPrefix. $table])) {
		checkTableDefinition(cms_core::$lastDBPrefix. $table);
	}
	
	if (cms_core::$pkCols[cms_core::$lastDBPrefix. $table] === '') {
		reportDatabaseErrorFromHelperFunction(adminPhrase('The table `[[table]]` does not exist.', array('table' => cms_core::$lastDBPrefix. $table)));
	}
	
	
	if ($array) {
		$out = array();
		
		if ($result = checkRowExists($table, $ids, $cols, $notZero, true, false, $orderBy, $distinct, false, !$distinct)) {
			while ($row = sqlFetchAssoc($result)) {
				
				$id = false;
				if ((($idCol = '[[ id column ]]') && (isset($row[$idCol])))
				 || (($idCol = cms_core::$pkCols[cms_core::$lastDBPrefix. $table]) && (isset($row[$idCol])))) {
					$id = $row[$idCol];
					unset($row['[[ id column ]]']);
				}
				
				if (is_string($cols)) {
					if ($id) {
						$out[$id] = $row[$cols];
					} else {
						$out[] = $row[$cols];
					}
				} else {
					if ($id) {
						$out[$id] = $row;
					} else {
						$out[] = $row;
					}
				}
			}
		}
		
		return $out;
	}
	
	
	if (!is_array($ids)) {
		if (cms_core::$pkCols[cms_core::$lastDBPrefix. $table]) {
			$ids = array(cms_core::$pkCols[cms_core::$lastDBPrefix. $table] => $ids);
		} else {
			$ids = array('id' => $ids);
		}
	}
	
	if ($delete) {
		$sql = '
			DELETE';
	
	} elseif (is_array($cols) && !empty($cols)) {
		$sql = '';
		foreach ($cols as $col) {
			$sql .= ($sql? ',' : 'SELECT '). '`'. sqlEscape($col). '` AS `'. sqlEscape($col). '`';
		}
		
		if ($addId && cms_core::$pkCols[cms_core::$lastDBPrefix. $table] && !in_array(cms_core::$pkCols[cms_core::$lastDBPrefix. $table], $cols)) {
			$sql .= ', `'. sqlEscape(cms_core::$pkCols[cms_core::$lastDBPrefix. $table]). '` as `[[ id column ]]`';
		}
		
	} elseif ($cols === true) {
		$cols = array();
		$sql = '
			SELECT '. ($distinct? 'DISTINCT ' : ''). '*';
		
	} elseif ($cols !== false) {
		$sql = '
			SELECT '. ($distinct? 'DISTINCT ' : ''). '`'. sqlEscape($cols). '`';
		
		if ($addId && cms_core::$pkCols[cms_core::$lastDBPrefix. $table] && $cols != cms_core::$pkCols[cms_core::$lastDBPrefix. $table]) {
			$sql .= ', `'. sqlEscape(cms_core::$pkCols[cms_core::$lastDBPrefix. $table]). '` as `[[ id column ]]`';
		}
	
	} else {
		$sql = '
			SELECT 1';
	}
	
	
	
	
	$sql .= '
			FROM `'. sqlEscape(cms_core::$lastDBPrefix. $table). '`';
	
	$first = true;
	foreach($ids as $col => &$val) {
		checkRowExistsCol($table, $sql, $col, $val, $first, true);
	}
	
	if ($notZero && !is_array($cols) && $cols !== false) {
		if ($first) {
			$sql .= '
			WHERE ';
		} else {
			$sql .= '
			  AND ';
		}
		$sql .= '`'. sqlEscape($cols). '` != 0';
	}
	
	if (!empty($orderBy)) {
		if (!is_array($orderBy)) {
			$orderBy = array($orderBy);
		}
		$first = true;
		foreach ($orderBy as $col) {
			if ($first) {
				$sql .= '
				ORDER BY `'. sqlEscape($col). '`';
			} else {
				$sql .= ',
					`'. sqlEscape($col). '`';
			}
			$first = false;
		}
	}
	
	if (!$multiple) {
		$sql .= '
			LIMIT 1';
	}
	
	
	if ($delete) {
		$values = false;
		$affectedRows = cms_core::reviewDatabaseQueryForChanges($sql, $ids, $values, $table, true);
		return $affectedRows;
	
	} else {
		$result = sqlSelect($sql);
		
		if ($multiple) {
			return $result;
		
		} elseif (!$row = sqlFetchAssoc($result)) {
			return false;
		
		} elseif (is_array($cols)) {
			return $row;
		
		} elseif ($cols !== false) {
			return $row[$cols];
		
		} else {
			return true;
		}
	}
}

function setRow($table, $values, $ids = array(), $insertIfNotPresent = true, $insertIgnore = false) {
	$sqlW = '';
	
	if (!isset(cms_core::$numericCols[cms_core::$lastDBPrefix. $table])) {
		checkTableDefinition(cms_core::$lastDBPrefix. $table);
	}
	
	if (cms_core::$pkCols[cms_core::$lastDBPrefix. $table] === '') {
		reportDatabaseErrorFromHelperFunction(adminPhrase('The table `[[table]]` does not exist.', array('table' => cms_core::$lastDBPrefix. $table)));
	}
	
	
	if (!is_array($ids)) {
		
		if (cms_core::$pkCols[cms_core::$lastDBPrefix. $table]) {
			$ids = array(cms_core::$pkCols[cms_core::$lastDBPrefix. $table] => $ids);
		} else {
			$ids = array('id' => $ids);
		}
	}
	
	if (!$insertIfNotPresent || (!empty($ids) && checkRowExists($table, $ids))) {
		$affectedRows = 0;
		
		if (!empty($values)) {
			$sql = '
				UPDATE `'. sqlEscape(cms_core::$lastDBPrefix. $table). '` SET ';
			
			$first = true;
			foreach ($values as $col => &$val) {
				checkRowExistsCol($table, $sql, $col, $val, $first, false);
			}
			
			$first = true;
			foreach($ids as $col => &$val) {
				checkRowExistsCol($table, $sqlW, $col, $val, $first, true);
			}
			
			sqlUpdate($sql. $sqlW, false);
			if (($affectedRows = sqlAffectedRows()) > 0) {
				
				if (empty($ids)) {
					$dummy = false;
					cms_core::reviewDatabaseQueryForChanges($sql, $values, $dummy, $table);
				} else {
					cms_core::reviewDatabaseQueryForChanges($sql, $ids, $values, $table);
				}
			}
		}
		
		if ($insertIfNotPresent && cms_core::$pkCols[cms_core::$lastDBPrefix. $table]) {
			if (($sql = 'SELECT `'. sqlEscape(cms_core::$pkCols[cms_core::$lastDBPrefix. $table]). '` FROM `'. sqlEscape(cms_core::$lastDBPrefix. $table). '` '. $sqlW)
			 && ($result = sqlSelect($sql))
			 && ($row = sqlFetchRow($result))
			) {
				return $row[0];
			} else {
				return false;
			}
		} else {
			return $affectedRows;
		}
	
	} elseif ($insertIfNotPresent) {
		$sql = '
			INSERT '. ($insertIgnore? 'IGNORE ' : ''). 'INTO `'. sqlEscape(cms_core::$lastDBPrefix. $table). '` SET ';
		
		$first = true;
		$hadColumns = array();
		foreach ($values as $col => &$val) {
			checkRowExistsCol($table, $sql, $col, $val, $first, false);
			$hadColumns[$col] = true;
		}
		
		foreach ($ids as $col => &$val) {
			if (!isset($hadColumns[$col])) {
				checkRowExistsCol($table, $sql, $col, $val, $first, false);
			}
		}
		
		sqlUpdate($sql, false);
		$id = sqlInsertId();
		
		if (sqlAffectedRows() > 0) {
			if (empty($ids)) {
				$dummy = false;
				cms_core::reviewDatabaseQueryForChanges($sql, $values, $dummy, $table);
			} else {
				cms_core::reviewDatabaseQueryForChanges($sql, $ids, $values, $table);
			}
		}
		
		return $id;
	
	} else {
		return false;
	}
}