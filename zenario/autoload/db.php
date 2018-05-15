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



//Some quality of life constant definitions for Mongo Queries!
	/*
		$eq		Matches values that are equal to a specified value.
		$gt		Matches values that are greater than a specified value.
		$gte	Matches values that are greater than or equal to a specified value.
		$lt		Matches values that are less than a specified value.
		$lte	Matches values that are less than or equal to a specified value.
		$ne		Matches all values that are not equal to a specified value.
		$in		Matches any of the values specified in an array.
		$nin	Matches none of the values specified in an array.
		$set	Sets the value of a field in a document.
		$unset	Removes the specified field from a document.
		$min	Only updates the field if the specified value is less than the existing field value.
		$max	Only updates the field if the specified value is greater than the existing field value.
		$exists	Matches documents that have the specified field.
		$type	Selects documents if a field is of the specified type.
	*/
define('§eq', '$eq');
define('§gt', '$gt');
define('§gte', '$gte');
define('§lt', '$lt');
define('§lte', '$lte');
define('§ne', '$ne');
define('§in', '$in');
define('§nin', '$nin');
define('§set', '$set');
define('§unset', '$unset');
define('§min', '$min');
define('§max', '$max');
define('§exists', '$exists');
define('§type', '$type');



class SQLCol {
	public $col = '';
	public $encrypted = false;
	public $hashed = false;
	public $isFloat = false;
	public $isInt = false;
	public $isTime = false;
	public $isSet = false;
}


class SQLQueryWrapper {
	public $q;
	public $colDefs = [];
	public $colDefsAreSpecfic = false;

	public function __construct($q, $colDefs = [], $colDefsAreSpecfic = false) {
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
					$val = \ze\zewl::decrypt($val);
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
						$val = \ze\zewl::decrypt($val);
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
	
	public function free() {
		if (isset($this->q)) {
			$this->q->free();
		}
	}
	
	public function close() {
		if (isset($this->q)) {
			$this->q->close();
		}
	}
}





class db {



	
	//Formerly "ZENARIO_INT_COL"
	const INT_COL = true;
	
	//Formerly "ZENARIO_FLOAT_COL"
	const FLOAT_COL = 1;
	
	//Formerly "ZENARIO_SET_COL"
	const SET_COL = '';
	
	//Formerly "ZENARIO_STRING_COL"
	const STRING_COL = false;
	
	//Formerly "ZENARIO_TIME_COL"
	const TIME_COL = 0;
	
	//Note that int and float evaluate to true, and time and string evalulate to false
	


	//
	// Functions for connecting to a MySQL database
	//


	//Formerly "connectLocalDB()"
	public static function connectLocal() {
	
		if (\ze::$localDB) {
			\ze::$lastDB = \ze::$localDB;
			\ze::$lastDBHost = DBHOST;
			\ze::$lastDBName = DBNAME;
			\ze::$lastDBPrefix = DB_NAME_PREFIX;
			return;
		}
	
		if (!$dbSelected = \ze\db::connect(DBHOST, DBNAME, DBUSER, DBPASS, DBPORT)) {
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
	
		\ze::$localDB =
		\ze::$lastDB = $dbSelected;
		\ze::$lastDBHost = DBHOST;
		\ze::$lastDBName = DBNAME;
		\ze::$lastDBPrefix = DB_NAME_PREFIX;
	
		return true;
	}

	//Formerly "disconnectLastDB()"
	public static function disconnect() {
		if (\ze::$lastDB) {
		
			if (\ze::$localDB === \ze::$lastDB) {
				\ze::$localDB = false;
			}
			if (\ze::$globalDB === \ze::$lastDB) {
				\ze::$globalDB = false;
			}
		
			$rv = \ze::$lastDB->close();
		
			\ze::$lastDB = false;
			\ze::$lastDBHost = false;
			\ze::$lastDBName = false;
			\ze::$lastDBPrefix = false;
		
			return $rv;
		}
	}


	//Formerly "globalDBDefined()"
	public static function hasGlobal() {
		return defined('DBHOST_GLOBAL') && defined('DBNAME_GLOBAL') && defined('DBUSER_GLOBAL') && defined('DBPASS_GLOBAL') && defined('DB_NAME_PREFIX_GLOBAL')
				&& (DBHOST_GLOBAL != DBHOST || DBNAME_GLOBAL != DBNAME);
	}

	//Formerly "connectGlobalDB()"
	public static function connectGlobal() {
	
		if (!\ze\db::hasGlobal()) {
			return false;
		}
	
		if (\ze::$globalDB) {
			\ze::$lastDB = \ze::$globalDB;
			\ze::$lastDBHost = DBHOST_GLOBAL;
			\ze::$lastDBName = DBNAME_GLOBAL;
			\ze::$lastDBPrefix = DB_NAME_PREFIX_GLOBAL;
			return true;
		}
	
		if ((!$dbSelected = \ze\db::connect(DBHOST_GLOBAL, DBNAME_GLOBAL, DBUSER_GLOBAL, DBPASS_GLOBAL, DBPORT_GLOBAL))) {
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
	
		\ze::$globalDB =
		\ze::$lastDB = $dbSelected;
		\ze::$lastDBHost = DBHOST_GLOBAL;
		\ze::$lastDBName = DBNAME_GLOBAL;
		\ze::$lastDBPrefix = DB_NAME_PREFIX_GLOBAL;
		return true;
	}


	//Formerly "connectToDatabase()"
	public static function connect($dbhost = 'localhost', $dbname, $dbuser, $dbpass, $dbport = '', $reportErrors = true) {
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
		} catch (\Exception $e) {
		}
	
		if ($reportErrors) {
			\ze\db::reportError($errorText, @mysqli_errno($dbconnection), @mysqli_error($dbconnection));
		}
	
		return false;
	}

	//Formerly "loadSiteConfig()"
	public static function loadSiteConfig() {

		//Connect to the database
		\ze\db::connectLocal();
	
		//Don't directly show a Content Item if a major Database update needs to be applied
		if (defined('CHECK_IF_MAJOR_REVISION_IS_NEEDED')) {
			$sql = "
				SELECT 1
				FROM ". DB_NAME_PREFIX. "local_revision_numbers
				WHERE path = 'admin/db_updates/step_2_update_the_database_schema'
				  AND revision_no >= ". (int) LATEST_BIG_CHANGE_REVISION_NO. "
				LIMIT 1";
		
			if (!($result = \ze\sql::select($sql)) || !(\ze\sql::fetchRow($result))) {
				\ze\content::showStartSitePageIfNeeded(true);
				exit;
			}
			unset($result);
		}
	
	
		//Load the full site settings.
		if (!\ze\row::cacheTableDef(DB_NAME_PREFIX. 'site_settings', true)) {
			return;
		}
	
		$sql = "
			SELECT name, IFNULL(value, default_value), ". (isset(\ze::$dbCols[DB_NAME_PREFIX. 'site_settings']['encrypted'])? 'encrypted' : '0'). "
			FROM ". DB_NAME_PREFIX. "site_settings
			WHERE name NOT IN ('site_disabled_title', 'site_disabled_message', 'sitewide_head', 'sitewide_body', 'sitewide_foot')";
		$result = \ze\sql::select($sql);
		while ($row = \ze\sql::fetchRow($result)) {
			if ($row[2]) {
				\ze\zewl::init();
				\ze::$siteConfig[$row[0]] = \ze\zewl::decrypt($row[1]);
			} else {
				\ze::$siteConfig[$row[0]] = $row[1];
			}
		}
	
		\ze::$defaultLang = \ze::$siteConfig['default_language'] ?? null;
	
		//Check whether we should show error messages or not
		if (!defined('SHOW_SQL_ERRORS_TO_VISITORS')) {
			if (!empty($_SESSION['admin_logged_in'])
			  || \ze::setting('show_sql_errors_to_visitors')
			  || (defined('RUNNING_FROM_COMMAND_LINE') && RUNNING_FROM_COMMAND_LINE)) {
				define('SHOW_SQL_ERRORS_TO_VISITORS', true);
			} else {
				define('SHOW_SQL_ERRORS_TO_VISITORS', false);
			}
		}
	
		\ze::$cacheWrappers = \ze::setting('caching_enabled') && \ze::setting('cache_css_js_wrappers');
	
		//When we set the timezone in basicheader.inc.php, we were using whatever the server settings were.
		//Now we have access to the database, check if it's been set in the site-settings, and set it to that if so.
		if (!empty(\ze::$siteConfig['zenario_timezones__default_timezone'])) {
			date_default_timezone_set(\ze::$siteConfig['zenario_timezones__default_timezone']);
		}
	
	
		//Load information on the special pages and their language equivalences
		if (!\ze\row::cacheTableDef(DB_NAME_PREFIX. 'content_items', true)
		 || !\ze\row::cacheTableDef(DB_NAME_PREFIX. 'special_pages', true)) {
			return;
		}
	
		$sql = "
			SELECT sp.page_type, c.equiv_id, c.language_id, c.id, c.type
			FROM ". DB_NAME_PREFIX. "special_pages AS sp
			INNER JOIN ". DB_NAME_PREFIX. "content_items AS c
			   ON c.equiv_id = sp.equiv_id
			  AND c.type = sp.content_type";
	
		$result = \ze\sql::select($sql);
		while ($row = \ze\sql::fetchAssoc($result)) {
			if ($row['id'] ==  $row['equiv_id']) {
			
				if ($row['page_type'] == 'zenario_home') {
					\ze::$homeCID = (int) $row['id'];
					\ze::$homeEquivId = (int) $row['equiv_id'];
					\ze::$homeCType = $row['type'];
				}
			
				\ze::$specialPages[$row['page_type']] = $row['type']. '_'. $row['id'];
			} else {
				\ze::$specialPages[$row['page_type']. '`'. $row['language_id']] = $row['type']. '_'. $row['id'];
			}
		}
	
	
		//Load a list of languages whose phrases need translating
		if (!\ze\row::cacheTableDef(DB_NAME_PREFIX. 'languages', true)
		 || !isset(\ze::$dbCols[DB_NAME_PREFIX. 'languages']['show_untranslated_content_items'])) {
			return;
		}
	
		\ze::$langs = \ze\sql::fetchAssocs('SELECT id, translate_phrases, domain, show_untranslated_content_items FROM '. DB_NAME_PREFIX. 'languages', false, 'id');
	
		foreach (\ze::$langs as &$lang) {
			$lang['translate_phrases'] = (bool) $lang['translate_phrases'];
			$lang['show_untranslated_content_items'] = (bool) $lang['show_untranslated_content_items'];
		
			//Don't allow language specific domains if no primary domain has been set
			if (empty(\ze::$siteConfig['primary_domain'])) {
				$lang['domain'] = '';
			}
		}
	
		//If the "Show menu structure in friendly URLs" site setting is enabled,
		//always use the full URL when generating links in an AJAX request, just in case the results
		//are being displayed with a different relative path
		if (\ze::setting('mod_rewrite_slashes')
		 && !empty($_SERVER['SCRIPT_FILENAME'])
		 && substr(basename($_SERVER['SCRIPT_FILENAME']), -8) == 'ajax.php') {
			\ze::$mustUseFullPath = true;
		}
	}

	//Formerly "handleDatabaseError()"
	public static function handleError($dbconnection, $sql) {
		$sqlErrno = mysqli_errno($dbconnection);
		$sqlError = mysqli_error($dbconnection);
	
		if (defined('RUNNING_FROM_COMMAND_LINE')) {
			echo "Database query error\n\n". $sqlErrno. "\n\n". $sqlError. "\n\n". $sql. "\n\n";
			exit;
		}
	
		$debugBacktrace = debug_backtrace();
		$debugBacktrace = \ze\db::trimDebugBacktrace($debugBacktrace);
	
		if (defined('DEBUG_SEND_EMAIL') && DEBUG_SEND_EMAIL === true) {
			\ze\db::reportError("Database query error", $sqlErrno, $sqlError, $sql, $debugBacktrace);
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
	
		echo $debugBacktrace;
	
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

	//Formerly "reportDatabaseError()"
	public static function reportError($errtext = '', $errno = '', $error = '', $sql = '', $backtrace = '', $subjectPrefix = 'Error at ') {
		
		if (!empty($_SERVER['HTTP_HOST'])) {
			$subject = $subjectPrefix. $_SERVER['HTTP_HOST'];
	
		} elseif (!empty(\ze::$siteConfig['primary_domain'])) {
			$subject = $subjectPrefix. \ze::$siteConfig['primary_domain'];
	
		} elseif (!empty(\ze::$siteConfig['last_primary_domain'])) {
			$subject = $subjectPrefix. \ze::$siteConfig['last_primary_domain'];
	
		} else {
			$subject = $subjectPrefix. gethostname();
		}
	
	
		$body = \ze\user::ip();
	
		if (!empty($_SERVER['REQUEST_URI'])) {
			$body .= ' accessing '. $_SERVER['REQUEST_URI'];
		}
	
		$body .= "\n\n". $errtext. "\n\n". $errno. "\n\n". $error. "\n\n". $sql. "\n\n". $backtrace. "\n\n";
	
		// Mail it
		
		$addressToOverriddenBy = false;
	
		//A little hack - don't allow \ze\server::sendEmail() to connect to the database
		$lastDB = \ze::$lastDB;
		\ze::$lastDB = false;
	
		\ze\server::sendEmail(
			$subject, $body,
			EMAIL_ADDRESS_GLOBAL_SUPPORT,
			$addressToOverriddenBy,
			$nameTo = false,
			$addressFrom = \ze::setting('email_address_from') ?: EMAIL_ADDRESS_GLOBAL_SUPPORT,
			$nameFrom = false,
			false, false, false,
			$isHTML = false);
	
		\ze::$lastDB = $lastDB;
	}

	
	private static $increasingRevNum = false;
	
	//Update the data revision number
	//It's designed to update the local revision number if we're connected to the local database,
	//otherwise update the global revision number if we're connected to the global database
	//Formerly "updateDataRevisionNumber()"
	public static function updateDataRevisionNumber() {
	
		//We only need to do this at most once per request/load
		if (!self::$increasingRevNum) {
			register_shutdown_function('ze\db::updateDataRevisionNumber2');
			self::$increasingRevNum = true;
		}
	}

	//Formerly "updateDataRevisionNumber2()"
	public static function updateDataRevisionNumber2() {
		\ze\db::connectLocal();
	
		if (($result = mysqli_query(\ze::$localDB, "SHOW TABLES LIKE '". DB_NAME_PREFIX. "local_revision_numbers'"))
		 && (mysqli_fetch_row($result))) {
			$sql = "
				INSERT INTO ". DB_NAME_PREFIX. "local_revision_numbers SET
					path = 'data_rev',
					patchfile = 'data_rev',
					revision_no = 1
				ON DUPLICATE KEY UPDATE
					revision_no = MOD(revision_no + 1, 4294960000)";
			mysqli_query(\ze::$localDB, $sql);
		}
	}






	//Return a cache-killer variable based on the date of the last svn up or svn change
	//of the core software.
	//We'll check the CMS_ROOT and the zenario_custom directory for a modification time and
	//use whatever is the latest.
	//If there isn't a .svn directory then fall-back to using the latest db_update revision number.
	//Formerly "zenarioCodeLastUpdated()"
	public static function codeLastUpdated($getChecksum = true) {
		$v = 0;
	
		$realDir = dirname(realpath(CMS_ROOT. 'zenario'));
		$customDir = CMS_ROOT. 'zenario_custom';
	
		foreach ([
			$realDir. '/.svn/.',
			$customDir. '/.svn/.',
			$realDir. '/.svn/wc.db',
			$customDir. '/.svn/wc.db',
			$customDir. '/site_description.yaml',
			$realDir. '/zenario/admin/db_updates/latest_revision_no.inc.php'
		] as $check) {
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
	//This won't be completely foolproof though, as \ze\db::codeLastUpdated() relies on
	//svn to give an accurate result, and \ze::setting('css_js_version') is only accurate
	//if the site is set to Development mode.
	//Formerly "zenarioCodeVersion()"
	public static function codeVersion() {
		return \ze\site::versionNumber(false, false). '.'. trim(max(\ze\db::codeLastUpdated(), \ze::setting('css_js_version')));
	}


	//Formerly "trimDebugBacktrace()"
	public static function trimDebugBacktrace(&$debugBacktrace) {
		array_shift($debugBacktrace);
		self::tbtR($debugBacktrace);
		
		return mb_substr(print_r($debugBacktrace, true), 0, 50000);
	}


	private static function tbtR(&$debugBacktrace) {
		foreach ($debugBacktrace as &$entry) {
			if (is_object($entry)) {
				$entry = '<<'. get_class($entry). '>>';
		
			} elseif (is_array($entry) && !empty($entry)) {
				if ($entry === \ze::$slotContents) {
					$entry = '<<\ze::$slotContents>>';
				} else {
					\ze\db::tbtR($entry, false);
				}
			}
		}
	}

	//Formerly "reportDatabaseErrorFromHelperFunction()"
	public static function reportDatabaseErrorFromHelperFunction($error) {
		echo 'A database error has occured on this section of the site.', "\n\n";
	
		if (defined('RUNNING_FROM_COMMAND_LINE') || (defined('SHOW_SQL_ERRORS_TO_VISITORS') && SHOW_SQL_ERRORS_TO_VISITORS === true)) {
			echo $error;
		} else {
			echo 'Please contact a site Administrator.';
		}
	
		echo "\n\n";
		
		if (defined('DEBUG_SEND_EMAIL') && DEBUG_SEND_EMAIL === true) {
			$debugBacktrace = debug_backtrace();
			\ze\db::reportError('Database query error', '', $error, '', \ze\db::trimDebugBacktrace($debugBacktrace));
		}
	
		exit;
	}
	
	
	
	


	//Formerly "reviewDatabaseQueryForChanges()"
	public static function reviewQueryForChanges(&$sql, &$ids, &$values, $table = false, $runSql = false) {
	
		//Only do the review when Modules are running normally and we're connected to the local db
		if (\ze::$lastDBHost
		 && \ze::$lastDBHost == DBHOST
		 && \ze::$lastDBName == DBNAME
		 && \ze::$lastDBPrefix == DB_NAME_PREFIX
		 && ($edition = \ze::$edition)) {
			return $edition::reviewDatabaseQueryForChanges($sql, $ids, $values, $table, $runSql);
	
		} elseif ($runSql) {
			\ze\sql::update($sql, false, false);
			return \ze\sql::affectedRows();
		}
	}





	//Formerly "hashDBColumn()"
	public static function hashDBColumn($val) {
		return hash('sha256', \ze::$siteConfig['site_id']. strtolower($val), true);
	}











	//	function connectGlobalDB() {}

	//	function connectLocalDB() {}

	//Formerly "getNextAutoIncrementId()"
	public static function getNextAutoIncrementId($table) {
		if ($row = \ze\sql::fetchAssoc("SHOW TABLE STATUS LIKE '". \ze\escape::sql(\ze::$lastDBPrefix. $table). "'")) {
			return $row['Auto_increment'];
		}
		return false;
	}

	//Formerly "getNewThingFromSession()"
	public static function getNewThingFromSession($table, $clear = true) {
		$return = false;
		if (isset($_SESSION['new_id_in_'. $table])) {
			$return = $_SESSION['new_id_in_'. $table];
		
			if ($clear) {
				unset($_SESSION['new_id_in_'. $table]);
			}
		}
		return $return;
	}


	//Formerly "columnIsEncrypted()"
	public static function columnIsEncrypted($table, $column) {
	
		$tableName = DB_NAME_PREFIX. $table;
	
		if (!isset(\ze::$dbCols[$tableName])) {
			\ze\row::cacheTableDef($tableName);
		}

		return isset(\ze::$dbCols[$tableName][$column])? \ze::$dbCols[$tableName][$column]->encrypted : null;
	}


	//Formerly "columnIsHashed()"
	public static function columnIsHashed($table, $column) {
	
		$tableName = DB_NAME_PREFIX. $table;
	
		if (!isset(\ze::$dbCols[$tableName])) {
			\ze\row::cacheTableDef($tableName);
		}

		return isset(\ze::$dbCols[$tableName][$column])? \ze::$dbCols[$tableName][$column]->hashed : null;
	}
}