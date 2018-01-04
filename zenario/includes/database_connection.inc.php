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
	
	if (!$dbSelected = connectToDatabase(DBHOST, DBNAME, DBUSER, DBPASS, DBPORT)) {
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

function disconnectLastDB() {
	if (cms_core::$lastDB) {
		
		if (cms_core::$localDB === cms_core::$lastDB) {
			cms_core::$localDB = false;
		}
		if (cms_core::$globalDB === cms_core::$lastDB) {
			cms_core::$globalDB = false;
		}
		
		$rv = cms_core::$lastDB->close();
		
		cms_core::$lastDB = false;
		cms_core::$lastDBHost = false;
		cms_core::$lastDBName = false;
		cms_core::$lastDBPrefix = false;
		
		return $rv;
	}
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
	
	if ((!$dbSelected = connectToDatabase(DBHOST_GLOBAL, DBNAME_GLOBAL, DBUSER_GLOBAL, DBPASS_GLOBAL, DBPORT_GLOBAL))) {
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


function connectToDatabase($dbhost = 'localhost', $dbname, $dbuser, $dbpass, $dbport = '', $reportErrors = true) {
	$errorText = 'Database connection failure';
	
	try {
		
		if ($dbport) {
			$dbconnection = @mysqli_connect($dbhost, $dbuser, $dbpass, $dbname, $dbport);
		} else {
			$dbconnection = @mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
		}
		
		if ($dbconnection) {
			if (mysqli_query($dbconnection,'SET NAMES "UTF8"')
			 && mysqli_query($dbconnection,"SET collation_connection='utf8mb4_general_ci'")
			 && mysqli_query($dbconnection,"SET collation_server='utf8mb4_general_ci'")
			 && mysqli_query($dbconnection,"SET character_set_client='utf8mb4'")
			 && mysqli_query($dbconnection,"SET character_set_connection='utf8mb4'")
			 && mysqli_query($dbconnection,"SET character_set_results='utf8mb4'")
			 && mysqli_query($dbconnection,"SET character_set_server='utf8mb4'")) {
				
				if (defined('DEBUG_USE_STRICT_MODE') && DEBUG_USE_STRICT_MODE) {
					mysqli_query($dbconnection,"SET @@SESSION.sql_mode = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE'");
				} else {
					mysqli_query($dbconnection,"SET @@SESSION.sql_mode = ''");
				}
				//N.b. we don't support the new ONLY_FULL_GROUP_BY option in 5.7, as some of our queries rely on this being disabled.
				
				return $dbconnection;
			}
		}
	} catch (Exception $e) {
	}
	
	if ($reportErrors) {
		reportDatabaseError($errorText, @mysqli_errno($dbconnection), @mysqli_error($dbconnection));
	}
	
	return false;
}

function loadSiteConfig() {

	//Connect to the database
	connectLocalDB();
	
	//Don't directly show a Content Item if a major Database update needs to be applied
	if (defined('CHECK_IF_MAJOR_REVISION_IS_NEEDED')) {
		$sql = "
			SELECT 1
			FROM ". DB_NAME_PREFIX. "local_revision_numbers
			WHERE path = 'admin/db_updates/step_2_update_the_database_schema'
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
	if (!checkTableDefinition(DB_NAME_PREFIX. 'site_settings', true)) {
		return;
	}
	
	$sql = "
		SELECT name, IFNULL(value, default_value), ". (isset(cms_core::$dbCols[DB_NAME_PREFIX. 'site_settings']['encrypted'])? 'encrypted' : '0'). "
		FROM ". DB_NAME_PREFIX. "site_settings
		WHERE name NOT IN ('site_disabled_title', 'site_disabled_message', 'sitewide_head', 'sitewide_body', 'sitewide_foot')
		  AND name NOT LIKE 'perm.%'";
	$result = sqlQuery($sql);
	while ($row = sqlFetchRow($result)) {
		if ($row[2]) {
			loadZewl();
			cms_core::$siteConfig[$row[0]] = zewl::decrypt($row[1]);
		} else {
			cms_core::$siteConfig[$row[0]] = $row[1];
		}
	}
	
	cms_core::$defaultLang = cms_core::$siteConfig['default_language'] ?? null;
	
	//Check whether we should show error messages or not
	if (!defined('SHOW_SQL_ERRORS_TO_VISITORS')) {
		if (!empty($_SESSION['admin_logged_in'])
		  || setting('show_sql_errors_to_visitors')
		  || (defined('RUNNING_FROM_COMMAND_LINE') && RUNNING_FROM_COMMAND_LINE)) {
			define('SHOW_SQL_ERRORS_TO_VISITORS', true);
		} else {
			define('SHOW_SQL_ERRORS_TO_VISITORS', false);
		}
	}
	
	cms_core::$cacheWrappers = setting('caching_enabled') && setting('cache_css_js_wrappers');
	
	//When we set the timezone in basicheader.inc.php, we were using whatever the server settings were.
	//Now we have access to the database, check if it's been set in the site-settings, and set it to that if so.
	if (!empty(cms_core::$siteConfig['zenario_timezones__default_timezone'])) {
		date_default_timezone_set(cms_core::$siteConfig['zenario_timezones__default_timezone']);
	}
	
	
	//Load information on the special pages and their language equivalences
	if (!checkTableDefinition(DB_NAME_PREFIX. 'content_items', true)
	 || !checkTableDefinition(DB_NAME_PREFIX. 'special_pages', true)) {
		return;
	}
	
	$sql = "
		SELECT sp.page_type, c.equiv_id, c.language_id, c.id, c.type
		FROM ". DB_NAME_PREFIX. "special_pages AS sp
		INNER JOIN ". DB_NAME_PREFIX. "content_items AS c
		   ON c.equiv_id = sp.equiv_id
		  AND c.type = sp.content_type";
	
	$result = sqlQuery($sql);
	while ($row = sqlFetchAssoc($result)) {
		if ($row['id'] ==  $row['equiv_id']) {
			
			if ($row['page_type'] == 'zenario_home') {
				cms_core::$homeCID = (int) $row['id'];
				cms_core::$homeEquivId = (int) $row['equiv_id'];
				cms_core::$homeCType = $row['type'];
			}
			
			cms_core::$specialPages[$row['page_type']] = $row['type']. '_'. $row['id'];
		} else {
			cms_core::$specialPages[$row['page_type']. '`'. $row['language_id']] = $row['type']. '_'. $row['id'];
		}
	}
	
	
	//Load a list of languages whose phrases need translating
	if (!checkTableDefinition(DB_NAME_PREFIX. 'languages', true)
	 || !isset(cms_core::$dbCols[DB_NAME_PREFIX. 'languages']['show_untranslated_content_items'])) {
		return;
	}
	
	cms_core::$langs = sqlFetchAssocs('SELECT id, translate_phrases, domain, show_untranslated_content_items FROM '. DB_NAME_PREFIX. 'languages', false, 'id');
	
	foreach (cms_core::$langs as &$lang) {
		$lang['translate_phrases'] = (bool) $lang['translate_phrases'];
		$lang['show_untranslated_content_items'] = (bool) $lang['show_untranslated_content_items'];
		
		//Don't allow language specific domains if no primary domain has been set
		if (empty(cms_core::$siteConfig['primary_domain'])) {
			$lang['domain'] = '';
		}
	}
	
	//If the "Show menu structure in friendly URLs" site setting is enabled,
	//always use the full URL when generating links in an AJAX request, just in case the results
	//are being displayed with a different relative path
	if (setting('mod_rewrite_slashes')
	 && !empty($_SERVER['SCRIPT_FILENAME'])
	 && substr(basename($_SERVER['SCRIPT_FILENAME']), -8) == 'ajax.php') {
		cms_core::$mustUseFullPath = true;
	}
}

//Deprecated function, please call either sqlSelect() or sqlUpdate() instead!
function my_mysql_query($sql, $updateDataRevisionNumber = -1, $checkCache = true, $return = 'sqlSelect') {
	
	if ($return === true || $return === 'mysql_affected_rows' || $return === 'mysql_affected_rows()' || $return === 'sqlAffectedRows' || $return === 'sqlAffectedRows()') {
		if (sqlUpdate($sql, false, $checkCache)) {
			return sqlAffectedRows();
		}
	
	} elseif ($return === 'mysql_insert_id' || $return === 'mysql_insert_id()' || $return === 'sqlInsertId' || $return === 'sqlInsertId()') {
		if (sqlUpdate($sql, false, $checkCache)) {
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
	
	$debugBacktrace = debug_backtrace();
	trimDebugBacktrace($debugBacktrace);
	
	if (defined('DEBUG_SEND_EMAIL') && DEBUG_SEND_EMAIL === true) {
		reportDatabaseError("Database query error", $sqlErrno, $sqlError, $sql, print_r($debugBacktrace, true));
	}
	
	if (!defined('SHOW_SQL_ERRORS_TO_VISITORS') || SHOW_SQL_ERRORS_TO_VISITORS !== true) {
		echo 'A database error has occured on this section of the site. Please contact a site Administrator.';
		exit;
	}
	
	if ($addDiv = !empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'ajax.php') === false) {
		echo '<div id="error_information">';
	}
	
	echo "
Database query error: ".$sqlErrno.", ".$sqlError.",\n$sql\n\n
Trace-back information:\n";
	
	print_r($debugBacktrace);
	
	if ($addDiv) {
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
	}
	
	exit;
}

function reportDatabaseError($errtext = '', $errno = '', $error = '', $sql = '', $backtrace = '', $additionalSubject = '') {
	
	if (!empty($_SERVER['HTTP_HOST'])) {
		$subject = 'Error at '. $_SERVER['HTTP_HOST'];
	
	} elseif (!empty(cms_core::$siteConfig['primary_domain'])) {
		$subject = 'Error at '. cms_core::$siteConfig['primary_domain'];
	
	} elseif (!empty(cms_core::$siteConfig['last_primary_domain'])) {
		$subject = 'Error at '. cms_core::$siteConfig['last_primary_domain'];
	
	} else {
		$subject = 'Error at '. gethostname();
	}
	
	$subject .= $additionalSubject;
	
	
	$body = visitorIP();
	
	if (!empty($_SERVER['REQUEST_URI'])) {
		$body .= ' accessing '. $_SERVER['REQUEST_URI'];
	}
	
	$body .= "\n\n". $errtext. "\n\n". $errno. "\n\n". $error. "\n\n". $sql. "\n\n". $backtrace. "\n\n";
	
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
		$addressFrom = setting('email_address_from') ?: EMAIL_ADDRESS_GLOBAL_SUPPORT,
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



//Look for and update the copy of the Global Admins in the local table
function syncSuperAdmin($adminIdG) {
	return require funIncPath(__FILE__, __FUNCTION__);
}





//Return a cache-killer variable based on the date of the last svn up or svn change
//of the core software.
//We'll check the CMS_ROOT and the zenario_custom directory for a modification time and
//use whatever is the latest.
//If there isn't a .svn directory then fall-back to using the latest db_update revision number.
function zenarioCodeLastUpdated($getChecksum = true) {
	$v = 0;
	
	$realDir = dirname(realpath(CMS_ROOT. 'zenario'));
	$customDir = CMS_ROOT. 'zenario_custom';
	
	foreach (array(
		$realDir. '/.svn/.',
		$customDir. '/.svn/.',
		$realDir. '/.svn/wc.db',
		$customDir. '/.svn/wc.db',
		$customDir. '/site_description.yaml',
		$realDir. '/zenario/admin/db_updates/latest_revision_no.inc.php'
	) as $check) {
		if (file_exists($check)
		 && ($mtime = (int) filemtime($check))
		 && ($v < $mtime)) {
			$v = $mtime;
		}
	}
	
	if (!$v) {
		$v = LATEST_REVISION_NO;
	}
	
	if ($getChecksum) {
		return base_convert($v, 10, 36);
	} else {
		return $v;
	}
}

//Returns a cache killer for URLs. Ideally it should give a different URL
//if any PHP code changes or if any CSS or JavaScript code changes.
//This won't be completely foolproof though, as zenarioCodeLastUpdated() relies on
//svn to give an accurate result, and setting('css_js_version') is only accurate
//if the site is set to Development mode.
function zenarioCodeVersion() {
	return getCMSVersionNumber(false, false). '.'. trim(max(zenarioCodeLastUpdated(), setting('css_js_version')));
}

function checkForChangesInYamlFiles($forceScan = false) {
	
	//Safety catch - do not try to do anything if there is no database connection!
	if (!cms_core::$lastDB) {
		return;
	}
	
	//Make sure we are in the CMS root directory.
	//This should already be done, but I'm being paranoid...
	chdir(CMS_ROOT);
	
	$time = time();
	$zenario_version = getCMSVersionNumber();
	
	//Catch the case where someone just updated to a different version of the CMS
	if ($zenario_version != setting('zenario_version')) {
		//Clear everything that was cached if this has happened
		setSetting('css_js_html_files_last_changed', '');
		setSetting('css_js_version', '');
		$changed = true;
	
	//Get the date of the last time we ran this check and there was a change.
	} elseif (!($lastChanged = (int) setting('yaml_files_last_changed'))) {
		//If this has never been run before then it must be run now!
		$changed = true;
	
	} elseif ($forceScan) {
		$changed = true;
	
	//In production mode, only run this check if it looks like there's
	//been a core software update since the last time we ran
	} elseif (setting('site_mode') == 'production' && zenarioCodeLastUpdated(false) < $lastChanged) {
		$changed = false;
	
	//Otherwise, work out the time difference between that time and now
	} else {
		$changed = false;
		foreach (moduleDirs('tuix/') as $tuixDir) {
			
			$RecursiveDirectoryIterator = new RecursiveDirectoryIterator(CMS_ROOT. $tuixDir);
			$RecursiveIteratorIterator = new RecursiveIteratorIterator($RecursiveDirectoryIterator);
			
			foreach ($RecursiveIteratorIterator as $file) {
				if ($file->isFile()
				 && $file->getMTime() > $lastChanged) {
					$changed = true;
					break 2;
				}
			}
		}
		chdir(CMS_ROOT);
	}
	
	
	if ($changed) {
		//We'll need to be reading TUIX files, the functions needed for this are stored in tuix.inc.php
		require_once CMS_ROOT. 'zenario/visitorheader.inc.php';
		require_once CMS_ROOT. 'zenario/includes/tuix.inc.php';
		
		
		//Look to see what datasets are on the system, and which datasets extend which FABs
		$datasets = array();
		$datasetFABs = array();
		foreach (getRowsArray('custom_datasets', 'extends_admin_box') as $datasetId => $extends_admin_box) {
			$datasetFABs[$extends_admin_box] = $datasetId;
		}
		
		
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
		foreach (array('admin_boxes', 'admin_toolbar', 'slot_controls', 'organizer', 'visitor', 'wizards') as $type) {
			foreach (moduleDirs('tuix/'. $type. '/') as $moduleClassName => $dir) {
			
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
							if ($type == 'organizer') {
								$paths = array();
								logTUIXFileContentsR($paths, $tags, $type);
							
								foreach ($paths as $path => $panelType) {
									$pathsFound = true;
									$settingGroup = '';
								
									$key2 = $path. '//'. $settingGroup;
									$tuixFiles[$key][$key2] = array(
										'type' => $type,
										'path' => $path,
										'panel_type' => $panelType,
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
								//Note - also do this for Organizer if no paths were found above,
								//as logTUIXFileContentsR() will miss files that have navigation definitions but no panel definitions
								foreach ($tags as $path => &$tag) {
								
									$settingGroup = '';
									if ($type == 'admin_boxes') {
										if ($path == 'plugin_settings' && !empty($tag['module_class_name'])) {
											$settingGroup = $tag['module_class_name'];
									
										} elseif ($path == 'site_settings' && !empty($tag['setting_group'])) {
											$settingGroup = $tag['setting_group'];
										
										//Note down if we see any changes in a file for a FAB
										//that is used for a dataset.
										} elseif (!empty($datasetFABs[$path])) {
											$datasets[$datasetFABs[$path]] = $datasetFABs[$path];
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
										'panel_type' => '',
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
							panel_type = '". sqlEscape($tf['panel_type']). "',
							setting_group = '". sqlEscape($tf['setting_group']). "',
							module_class_name = '". sqlEscape($tf['module_class_name']). "',
							filename = '". sqlEscape($tf['filename']). "',
							last_modified = ". (int) $tf['last_modified']. ",
							checksum = '". sqlEscape($tf['checksum']). "'
						ON DUPLICATE KEY UPDATE
							panel_type = VALUES(panel_type),
							last_modified = VALUES(last_modified),
							checksum = VALUES(checksum)";
					sqlSelect($sql);
				}
			}
		}
		
		//Rescan the TUIX files for any datasets that have changed
		foreach ($datasets as $datasetId) {
			saveSystemFieldsFromTUIX($datasetId);
		}
		
		
		setSetting('yaml_files_last_changed', $time);
		setSetting('yaml_version', base_convert($time, 10, 36));
		setSetting('zenario_version', $zenario_version);
	}
}


function saveSystemFieldsFromTUIX($datasetId) {
	$dataset = getDatasetDetails($datasetId);
	//If this extends a system admin box, load the system tabs and fields
	if ($dataset['extends_admin_box']
	 && checkRowExists('tuix_file_contents', array('type' => 'admin_boxes', 'path' => $dataset['extends_admin_box']))) {
		$moduleFilesLoaded = array();
		$tags = array();
		
		loadTUIX(
			$moduleFilesLoaded, $tags, $type = 'admin_boxes', $dataset['extends_admin_box'],
			$settingGroup = '', $compatibilityClassNames = false, $runningModulesOnly = false, $exitIfError = true
		);
		
		if (!empty($tags[$dataset['extends_admin_box']]['tabs'])
			 && is_array($tags[$dataset['extends_admin_box']]['tabs'])) {
			$tabCount = 0;
			foreach ($tags[$dataset['extends_admin_box']]['tabs'] as $tabName => $tab) {
				if (is_array($tab) && (!empty($tab['label']) || !empty($tab['dataset_label']))) {
					++$tabCount;
					$tabDetails = getRow('custom_dataset_tabs', true, array('dataset_id' => $datasetId, 'name' => $tabName));
					$values = array(
						'is_system_field' => 1,
						'default_label' => ifNull($tab['dataset_label'] ?? false, ($tab['label'] ?? false), '')
					);
					if (!$tabDetails || !$tabDetails['ord']) {
						$values['ord'] = (float)ifNull($tab['ord'] ?? false, $tabCount);
					}
					setRow('custom_dataset_tabs', 
						$values,
						array(
							'dataset_id' => $datasetId, 
							'name' => $tabName));
					if (!empty($tab['fields'])
						 && is_array($tab['fields'])) {
						$fieldCount = 0;
						foreach ($tab['fields'] as $fieldName => $field) {
							if (is_array($field)) {
								++$fieldCount;
								
								$fieldDetails = getRow('custom_dataset_fields', true, array('dataset_id' => $datasetId, 'tab_name' => $tabName, 'is_system_field' => 1, 'field_name' => $fieldName));
								$values = array(
									'default_label' => ifNull($field['dataset_label'] ?? false, ($field['label'] ?? false), ''),
									'is_system_field' => 1,
									'allow_admin_to_change_visibility' => !empty($field['allow_admin_to_change_visibility']),
									'allow_admin_to_change_export' => !empty($field['allow_admin_to_change_export'])
								);
								if (!$fieldDetails || !$fieldDetails['ord']) {
									$values['ord'] = (float) ifNull($field['ord'] ?? false, $fieldCount);
								}
								setRow('custom_dataset_fields',
									$values,
									array(
										'dataset_id' => $datasetId, 
										'tab_name' => $tabName, 
										'field_name' => $fieldName));
							}
						}
					}
				}
			}
		}
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


function getModulePrefix($module, $mustBeRunning = true, $oldFormat = false) {
	
	if (!is_array($module)) {
		$module = sqlFetchAssoc("
			SELECT id, class_name, status
			FROM ". DB_NAME_PREFIX. "modules
			WHERE class_name = '". sqlEscape($module). "'
			  ". ($mustBeRunning? "AND status IN ('module_running', 'module_is_abstract')" : ""). "
			LIMIT 1");
	}
	
	if (!$module) {
		return false;
	} else {
		return setModulePrefix($module, false, $oldFormat);
	}
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
			if ($frag !== '') {
				$prefix .= $frag[0];
			}
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


function trimDebugBacktrace(&$debugBacktrace, $firstCall = true) {
	
	if ($firstCall) {
		array_shift($debugBacktrace);
	}
	
	foreach ($debugBacktrace as &$entry) {
		if (is_object($entry)) {
			$entry = '<<'. get_class($entry). '>>';
		
		} elseif (is_array($entry) && !empty($entry)) {
			if ($entry === cms_core::$slotContents) {
				$entry = '<<cms_core::$slotContents>>';
			} else {
				trimDebugBacktrace($entry, false);
			}
		}
	}
}

function reportDatabaseErrorFromHelperFunction($error) {
	echo 'A database error has occured on this section of the site.', "\n\n";
	
	if (defined('RUNNING_FROM_COMMAND_LINE') || (defined('SHOW_SQL_ERRORS_TO_VISITORS') && SHOW_SQL_ERRORS_TO_VISITORS === true)) {
		echo $error;
	} else {
		echo 'Please contact a site Administrator.';
	}
	
	echo "\n\n";
		
	if (defined('DEBUG_SEND_EMAIL') && DEBUG_SEND_EMAIL === true) {
		$debugBacktrace = debug_backtrace();
		trimDebugBacktrace($debugBacktrace);
		reportDatabaseError('Database query error', '', $error, '', print_r($debugBacktrace, true));
	}
	
	exit;
}


class zenario_sql_col {
	public $col = '';
	public $encrypted = false;
	public $hashed = false;
	public $isFloat = false;
	public $isInt = false;
	public $isTime = false;
	public $isSet = false;
}


class zenario_sql_query_wrapper {
	public $q;
	public $colDefs = array();
	public $colDefsAreSpecfic = false;
	
	public function __construct($q, $colDefs = array(), $colDefsAreSpecfic = false) {
		$this->q = $q;
		$this->colDefs = $colDefs;
		$this->colDefsAreSpecfic = $colDefsAreSpecfic;
	}
	
	//Given an row fetched from the database using $this->q->fetch_assoc(), attempt to check it against the column definitions
	public function parseAssoc(&$row) {
		if (empty($this->colDefs)) {
			return;
		}
		
		foreach ($row as $col => &$val) {
			if (isset($this->colDefs[$col])) {
				$colDef = &$this->colDefs[$col];
				
				//Decrypt columns that were flagged as being encrypted
				if ($colDef->encrypted) {
					$val = zewl::decrypt($val);
				}
				
				//Ensure that int and float columns are returned as ints and floats, and not strings
				if ($colDef->isInt) {
					$val = (int) $val;
			
				} elseif ($colDef->isFloat) {
					$val = (float) $val;
				}
			}
		}
	}
	
	//Version of the above for $this->q->fetch_row()
	//This will only work if each column is specifically defined
	public function parseRow(&$row) {
		
		if ($this->colDefsAreSpecfic
		 && !empty($this->colDefs)
		 && (count($this->colDefs) == count($row))) {
		
			$i = 0;
			foreach ($this->colDefs as &$colDef) {
				if (isset($row[$i])) {
					$val = &$row[$i];
				
					//Decrypt columns that were flagged as being encrypted
					if ($colDef->encrypted) {
						$val = zewl::decrypt($val);
					}
				
					//Ensure that int and float columns are returned as ints and floats, and not strings
					if ($colDef->isInt) {
						$val = (int) $val;
			
					} elseif ($colDef->isFloat) {
						$val = (float) $val;
					}
				}
				++$i;
			}
		}
	}
}

function loadZewl() {
	if (!defined('ZEWL_LOADED')) {
		if (file_exists($path = CMS_ROOT. 'zenario/libraries/not_to_redistribute/zewl/zewl.inc.php')) {
			require_once $path;
			if (zewl::loadClientKey()) {
				define('ZEWL_LOADED', true);
				return true;
			}
		}
		
		define('ZEWL_LOADED', false);
	}
	return ZEWL_LOADED;
}

//Check a table definition and see which columns are numeric
//Note that int and float evaluate to true, and time and string evalulate to false
define('ZENARIO_INT_COL', true);
define('ZENARIO_FLOAT_COL', 1);
define('ZENARIO_SET_COL', '');
define('ZENARIO_STRING_COL', false);
define('ZENARIO_TIME_COL', 0);
function checkTableDefinition($prefixAndTable, $checkExists = false, $useCache = false) {
	$pkCol = false;
	$exists = false;
	
	if (!$useCache || !isset(cms_core::$dbCols[$prefixAndTable])) {
		cms_core::$dbCols[$prefixAndTable] = array();
		$useCache = false;
	}
	
	if (!cms_core::$lastDB) {
		return false;
	}
	
	if (!$useCache) {
		if ($checkExists
		 && !(($result = sqlSelect("SHOW TABLES LIKE '". sqlEscape($prefixAndTable). "'"))
		   && (sqlFetchRow($result))
		)) {
			return false;
		}
	
		if ($result = sqlSelect('SHOW COLUMNS FROM `'. sqlEscape($prefixAndTable). '`')) {
			while ($row = sqlFetchRow($result)) {
				$col = &$row[0];
				
				//Look out for encrypted versions of columns
				if ($col[0] === '%') {
					//If they exist, load the encryption wrapper library
					loadZewl();
					//Record that this column should be encrypted
					cms_core::$dbCols[$prefixAndTable][substr($col, 1)]->encrypted = true;
				
				//Look out for hashed versions of columns
				} elseif ($col[0] === '#') {
					//Record that this column should be hashed
					cms_core::$dbCols[$prefixAndTable][substr($col, 1)]->hashed = true;
				
				} else {
					$exists = true;
					
					$colDef = new zenario_sql_col;
					$colDef->col = $col;
					
					switch (substr($row[1], 0, strcspn($row[1], ' ('))) {
						case 'tinyint':
						case 'smallint':
						case 'mediumint':
						case 'int':
						case 'integer':
						case 'bigint':
							$colDef->isInt = true;
							break;
				
						case 'float':
						case 'double':
						case 'decimal':
							$colDef->isFloat = true;
							break;
				
						case 'datetime':
						case 'date':
						case 'timestamp':
						case 'time':
						case 'year':
							$colDef->isTime = true;
							break;
				
						case 'set':
							$colDef->isSet = true;
							break;
					}
			
					//Also check to see if there is a single primary key column
					if ($row[3] == 'PRI') {
						if ($pkCol === false) {
							$pkCol = $col;
						} else {
							$pkCol = true;
						}
					}
					
					cms_core::$dbCols[$prefixAndTable][$col] = $colDef;
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
	}
	
	if ($checkExists && is_string($checkExists)) {
		return
			is_array(cms_core::$dbCols[$prefixAndTable])
			&& isset(cms_core::$dbCols[$prefixAndTable][$checkExists]);
	}
	
	return !empty(cms_core::$dbCols[$prefixAndTable]);
}

//Helper function for checkRowExists
function checkRowExistsCol(&$tableName, &$sql, &$col, &$val, &$first, $isWhere, $ignoreMissingColumns = false, $sign = '=', $in = 0, $wasNot = false) {
	
	if (!isset(cms_core::$dbCols[$tableName][$col])) {
		checkTableDefinition($tableName);
	}
	
	if (!isset(cms_core::$dbCols[$tableName][$col])) {
		if ($ignoreMissingColumns && !$isWhere) {
			return;
		} else {
			require_once CMS_ROOT. 'zenario/includes/cms.inc.php';
			reportDatabaseErrorFromHelperFunction(adminPhrase('The column `[[col]]` does not exist in the table `[[table]]`.', array('col' => $col, 'table' => $tableName)));
		}
	}
	
	$colDef = &cms_core::$dbCols[$tableName][$col];
	
	
	if ($colDef->encrypted) {
		if ($isWhere) {
			if (!$colDef->hashed) {
				require_once CMS_ROOT. 'zenario/includes/cms.inc.php';
				reportDatabaseErrorFromHelperFunction(adminPhrase('The column `[[col]]` in the table `[[table]]` is encrypted and cannot be used in a WHERE-statement.', array('col' => $col, 'table' => $tableName)));
			}
		} else {
			$sql .= ($first? '' : ','). '`%'. sqlEscape($col). '` = \''. sqlEscape((string) zewl::encrypt($val, true)). '\'';
			
			if ($colDef->hashed) {
				$sql .= ', `#'. sqlEscape($col). '` = \''. sqlEscape(hashDBColumn($val)). '\'';
			}
			
			$first = false;
			return;
		}
	}
	
	
	if ($isWhere && is_array($val)) {
		$firstIn = true;
		foreach ($val as $sign2 => &$val2) {
			if (is_numeric($sign2) || substr($sign2, 0, 1) == '=') {
				if ($colDef->isSet) {
					if ($firstIn) {
						checkRowExistsCol($tableName, $sql, $col, $val2, $first, $isWhere, $ignoreMissingColumns, $wasNot? 'NOT (' : '(', 1);
						$firstIn = false;
					} else {
						checkRowExistsCol($tableName, $sql, $col, $val2, $first, $isWhere, $ignoreMissingColumns, ' OR ', 2);
					}
				} else {
					if ($firstIn) {
						checkRowExistsCol($tableName, $sql, $col, $val2, $first, $isWhere, $ignoreMissingColumns, $wasNot? 'NOT IN (' : 'IN (', 1);
						$firstIn = false;
					} else {
						checkRowExistsCol($tableName, $sql, $col, $val2, $first, $isWhere, $ignoreMissingColumns, ', ', 2);
					}
				}
			}
		}
		if (!$firstIn) {
			$sql .= ')';
		}
		
		foreach ($val as $sign2 => &$val2) {
			$isNot = false;
			if (substr($sign2, 0, 1) == '!') {
				$isNot = true;
				$sign2 = '!=';
			}
			if ($sign2 === '!='
			 || $sign2 === '<>'
			 || $sign2 === '<='
			 || $sign2 === '<'
			 || $sign2 === '>'
			 || $sign2 === '>='
			 || $sign2 === 'LIKE'
			 || $sign2 === 'NOT LIKE') {
				checkRowExistsCol($tableName, $sql, $col, $val2, $first, $isWhere, $ignoreMissingColumns, $sign2, 0, $isNot);
			}
		}
		
		return;
	}
	
	$cSql = '';
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
		
		if ($colDef->hashed) {
			$cSql = '`#'. sqlEscape($col). '` ';
		} else {
			$cSql = '`'. sqlEscape($col). '` ';
		}
	}
	
	if ($val === null || (!$val && $colDef->isTime)) {
		if ($in) {
			$sql .= $cSql. $sign. 'NULL';
		
		} elseif (!$isWhere) {
			$sql .= $cSql. '= NULL';
		
		} elseif ($sign == '=') {
			$sql .= $cSql. 'IS NULL';
		
		} else {
			$sql .= $cSql. 'IS NOT NULL';
		}
	
	} elseif ($colDef->hashed) {
		$sql .= $cSql. $sign. '\''. sqlEscape(hashDBColumn($val)). '\'';
	
	} elseif ($colDef->isFloat) {
		$sql .= $cSql. $sign. ' '. (float) $val;
	
	} elseif ($colDef->isInt) {
		$sql .= $cSql. $sign. ' '. (int) $val;
	
	} elseif ($colDef->isSet && $in) {
		$sql .= $sign. 'FIND_IN_SET(\''. sqlEscape((string) $val). '\', '. $cSql. ')';
	
	} else {
		$sql .= $cSql. $sign. ' \''. sqlEscape((string) $val). '\'';
	}
}


//Declare a function to check if something exists in the database
function checkRowExists(
	$table, $ids,
	$ignoreMissingColumns = false, $cols = false, $multiple = false, $mode = false, $orderBy = array(),
	$distinct = false, $returnArrayIndexedBy = false, $addId = false
) {
	$tableName = cms_core::$lastDBPrefix. $table;
	
	if (!isset(cms_core::$dbCols[$tableName])) {
		checkTableDefinition($tableName);
	}
	
	if (cms_core::$pkCols[$tableName] === '') {
		require_once CMS_ROOT. 'zenario/includes/cms.inc.php';
		reportDatabaseErrorFromHelperFunction(adminPhrase('The table `[[table]]` does not exist.', array('table' => $tableName)));
	}
	
	if ($cols === true) {
		$cols = array_keys(cms_core::$dbCols[$tableName]);
	}
	
	
	if ($returnArrayIndexedBy !== false) {
		$out = array();
		
		if ($result = checkRowExists($table, $ids, $ignoreMissingColumns, $cols, true, false, $orderBy, $distinct, false, !$distinct)) {
			while ($row = sqlFetchAssoc($result)) {
				
				$id = false;
				if (is_string($returnArrayIndexedBy) && isset($row[$returnArrayIndexedBy])) {
					$id = $row[$returnArrayIndexedBy];
				
				} elseif (isset($row['[[ id column ]]'])) {
					$id = $row['[[ id column ]]'];
				
				} elseif (($idCol = cms_core::$pkCols[$tableName]) && (isset($row[$idCol]))) {
					$id = $row[$idCol];
				}
				unset($row['[[ id column ]]']);
				
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
		if (cms_core::$pkCols[$tableName]) {
			$ids = array(cms_core::$pkCols[$tableName] => $ids);
		} else {
			$ids = array('id' => $ids);
		}
	}
	
	do {
		switch ($mode) {
			case 'delete':
				$sql = '
					DELETE';
				break 2;
		
			case 'count':
				$sql = '
					SELECT COUNT(*) AS c';
				$cols = 'c';
				break 2;
		
			case 'max':
				$pre = 'MAX(';
				$suf = ')';
				break;
			
			case 'min':
				$pre = 'MIN(';
				$suf = ')';
				break;
			
			default:
				$pre = '';
				$suf = '';
		}
		
		$dbCols = &cms_core::$dbCols[$tableName];
		
		if (empty($cols)) {
			$sql = '
				SELECT 1';
		
		} else {
			if ($distinct) {
				$sql = 'SELECT DISTINCT ';
			} else {
				$sql = 'SELECT ';
			}
			
			if (is_array($cols)) {
				$first = true;
				foreach ($cols as $col) {
				
					if ($first) {
						$first = false;
					} else {
						$sql .= ',';
					}	
					
					if (!isset($dbCols[$col])) {
						require_once CMS_ROOT. 'zenario/includes/cms.inc.php';
						reportDatabaseErrorFromHelperFunction(adminPhrase('The column `[[col]]` does not exist in the table `[[table]]`.', array('col' => $col, 'table' => $tableName)));
				
					} elseif ($dbCols[$col]->encrypted) {
						if ($pre !== '') {
							require_once CMS_ROOT. 'zenario/includes/cms.inc.php';
							reportDatabaseErrorFromHelperFunction(adminPhrase('The column `[[col]]` in the table `[[table]]` is encrypted. You cannot use MIN(), MAX() or other group-statements on it.', array('col' => $col, 'table' => $tableName)));
						}
					
						$sql .= '`%'. sqlEscape($col). '` AS `'. sqlEscape($col). '`';
				
					} else {
						$sql .= $pre. '`'. sqlEscape($col). '`'. $suf;
			
						if ($pre !== '' || $suf !== '') {
							$sql .= ' AS `'. sqlEscape($col). '`';
						
						} elseif ($addId && $col == cms_core::$pkCols[$tableName]) {
							$addId = false;
						}
					}
				}
			} else {
				if (!isset($dbCols[$cols])) {
					require_once CMS_ROOT. 'zenario/includes/cms.inc.php';
					reportDatabaseErrorFromHelperFunction(adminPhrase('The column `[[col]]` does not exist in the table `[[table]]`.', array('col' => $cols, 'table' => $tableName)));
			
				} elseif ($dbCols[$cols]->encrypted) {
					if ($pre !== '') {
						require_once CMS_ROOT. 'zenario/includes/cms.inc.php';
						reportDatabaseErrorFromHelperFunction(adminPhrase('The column `[[col]]` in the table `[[table]]` is encrypted. You cannot use MIN(), MAX() or other group-statements on it.', array('col' => $cols, 'table' => $tableName)));
					}
				
					$sql .= '`%'. sqlEscape($cols). '` AS `'. sqlEscape($cols). '`';
			
				} else {
					$sql .= $pre. '`'. sqlEscape($cols). '`'. $suf;
		
					if ($pre !== '' || $suf !== '') {
						$sql .= ' AS `'. sqlEscape($cols). '`';
					
					} elseif ($addId && $cols == cms_core::$pkCols[$tableName]) {
						$addId = false;
					}
				}
			}
	
			if ($addId && cms_core::$pkCols[$tableName]) {
				$sql .= ', `'. sqlEscape(cms_core::$pkCols[$tableName]). '` as `[[ id column ]]`';
			}
		}
	} while(false);
	
	
	
	$sql .= '
			FROM `'. sqlEscape($tableName). '`';
	
	$first = true;
	foreach($ids as $col => &$val) {
		checkRowExistsCol($tableName, $sql, $col, $val, $first, true, $ignoreMissingColumns);
	}
	
	if (!empty($orderBy)) {
		if (!is_array($orderBy)) {
			$orderBy = array($orderBy);
		}
		$first = true;
		foreach ($orderBy as $col) {
			
			if ($col == 'DESC'
			 || $col == 'ASC'
			 || $col == 'Desc'
			 || $col == 'Asc') {
				$sql .= ' '. $col;
			
			} elseif ($first) {
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
	
	
	if ($mode == 'delete') {
		$values = false;
		$affectedRows = reviewDatabaseQueryForChanges($sql, $ids, $values, $table, true);
		return $affectedRows;
	
	} else {
		$result = sqlSelect($sql, false, $tableName);
		
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

function setRow(
	$table, $values, $ids = array(),
	$ignore = false, $ignoreMissingColumns = false,
	$markNewThingsInSession = false, $insertIfNotPresent = true, $checkCache = true
) {
	$sqlW = '';
	$tableName = cms_core::$lastDBPrefix. $table;
	
	if (!isset(cms_core::$dbCols[$tableName])) {
		checkTableDefinition($tableName);
	}
	
	if (cms_core::$pkCols[$tableName] === '') {
		require_once CMS_ROOT. 'zenario/includes/cms.inc.php';
		reportDatabaseErrorFromHelperFunction(adminPhrase('The table `[[table]]` does not exist.', array('table' => $tableName)));
	}
	
	
	if (!is_array($ids)) {
		
		if (cms_core::$pkCols[$tableName]) {
			$ids = array(cms_core::$pkCols[$tableName] => $ids);
		} else {
			$ids = array('id' => $ids);
		}
	}
	
	if (!$insertIfNotPresent || (!empty($ids) && checkRowExists($table, $ids))) {
		$affectedRows = 0;
		
		if (!empty($values)) {
			$sql = '
				UPDATE '. ($ignore? 'IGNORE ' : ''). '`'. sqlEscape($tableName). '` SET ';
			
			$first = true;
			foreach ($values as $col => &$val) {
				checkRowExistsCol($tableName, $sql, $col, $val, $first, false, $ignoreMissingColumns);
			}
			
			$first = true;
			foreach($ids as $col => &$val) {
				checkRowExistsCol($tableName, $sqlW, $col, $val, $first, true, $ignoreMissingColumns);
			}
			
			sqlUpdate($sql. $sqlW, false, false);
			if (($affectedRows = sqlAffectedRows()) > 0
			 && $checkCache) {
				
				if (empty($ids)) {
					$dummy = false;
					reviewDatabaseQueryForChanges($sql, $values, $dummy, $table);
				} else {
					reviewDatabaseQueryForChanges($sql, $ids, $values, $table);
				}
			}
		}
		
		if ($insertIfNotPresent && cms_core::$pkCols[$tableName]) {
			if (($sql = 'SELECT `'. sqlEscape(cms_core::$pkCols[$tableName]). '` FROM `'. sqlEscape($tableName). '` '. $sqlW)
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
			INSERT '. ($ignore? 'IGNORE ' : ''). 'INTO `'. sqlEscape($tableName). '` SET ';
		
		$first = true;
		$hadColumns = array();
		foreach ($values as $col => &$val) {
			checkRowExistsCol($tableName, $sql, $col, $val, $first, false, $ignoreMissingColumns);
			$hadColumns[$col] = true;
		}
		
		foreach ($ids as $col => &$val) {
			if (!isset($hadColumns[$col])) {
				checkRowExistsCol($tableName, $sql, $col, $val, $first, false, $ignoreMissingColumns);
			}
		}
		
		sqlUpdate($sql, false, false);
		$id = sqlInsertId();
		
		if ($markNewThingsInSession) {
			$_SESSION['new_id_in_'. $table] = $id;
		}
		
		if ($checkCache
		 && sqlAffectedRows() > 0) {
			if (empty($ids)) {
				$dummy = false;
				reviewDatabaseQueryForChanges($sql, $values, $dummy, $table);
			} else {
				reviewDatabaseQueryForChanges($sql, $ids, $values, $table);
			}
		}
		
		return $id;
	
	} else {
		return false;
	}
}


function reviewDatabaseQueryForChanges(&$sql, &$ids, &$values, $table = false, $runSql = false) {
	
	//Only do the review when Modules are running normally and we're connected to the local db
	if (cms_core::$lastDBHost
	 && cms_core::$lastDBHost == DBHOST
	 && cms_core::$lastDBName == DBNAME
	 && cms_core::$lastDBPrefix == DB_NAME_PREFIX
	 && ($edition = cms_core::$edition)) {
		return $edition::reviewDatabaseQueryForChanges($sql, $ids, $values, $table, $runSql);
	
	} elseif ($runSql) {
		sqlUpdate($sql, false, false);
		return sqlAffectedRows();
	}
}





function hashDBColumn($val) {
	return hash('sha256', cms_core::$siteConfig['site_id']. strtolower($val), true);
}

