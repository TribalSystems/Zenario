<?php
/*
 * Copyright (c) 2019, Tribal Limited
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




//Class to store meta-information on a column from the database
class col {
	public $col = '';
	public $tbl = '';
	public $encrypted = false;
	public $hashed = false;
	public $isFloat = false;
	public $isInt = false;
	public $isTime = false;
	public $isSet = false;
	public $isJSON = false;
	public $isPK = false;
	
	public function __construct($f) {
		
		$this->col = $f->orgname;
		$this->tbl = $f->orgtable;
		
		switch ($f->type) {
			case 1:
			case 2:
			case 3:
			case 8:
			case 9:
				$this->isInt = true;
				break;

			case 0:
			case 4:
			case 5:
			case 246:
				$this->isFloat = true;
				break;

			case 7:
			case 10:
			case 11:
			case 12:
			case 13:
				$this->isTime = true;
				break;

			case 245:
				$this->isJSON = true;
				break;
		}
		
		$this->isSet = (bool) ($f->flags & 2048);
		$this->isPK = (bool) ($f->flags & 2);
	}
}


//Wrapper class for a SQL query
class queryCursor implements \Iterator {
	public $q;
	protected $d = [];
	protected $dc = 0;
	protected $enc = false;
	
	public function __construct($db, $q, $colDefs = []) {
		$this->q = $q;
		
		//Where possible, grab the definitions of the columns used in this query so we can automatically fix & convert the types later
		if ($colDefs !== []) {
			foreach ($colDefs as $d) {
				if ($d->encrypted) {
					$this->enc = true;
				}
			}
			$this->d = $colDefs;
			$this->dc = count($colDefs);
		
		} else {
			$fs = $q->fetch_fields();
			foreach ($fs as $f) {
				if ($f && $f->db === $db->name && $f->orgtable && $f->orgname) {
					if (!isset($db->cols[$f->orgtable])) {
						$db->checkTableDef($f->orgtable);
					}
					if (isset($db->cols[$f->orgtable][$f->orgname])) {
						$d = $db->cols[$f->orgtable][$f->orgname];
					} else {
						$d = new col($f);
					}
				} else {
					$d = new col($f);
				}
				
				if ($d->encrypted) {
					$this->enc = true;
				}
				
				$this->d[] = $d;
				++$this->dc;
			}
		}
	}
	
	
	protected function fixTypes(&$row) {
		
		//The MySQL API always converts ints and floats to strings.
		//Using the definitions we got above, try to convert them back.
		if ($this->dc !== 0
		 && $this->dc == count($row)) {
			
			if ($this->enc) {
				$pks = [];
				$decrypts = [];
			
				$di = 0;
				foreach ($row as $ri => &$val) {
					$d = &$this->d[$di];
				
					if ($d->isPK) {
						$pks[$d->tbl] = $val;
					}
				
					//Decrypt columns that were flagged as being encrypted
					if ($d->encrypted) {
						if ($val) {
							$val = \ze\zewl::decrypt($val);
						} else {
							if (!isset($decrypts[$d->tbl])) {
								$decrypts[$d->tbl] = [];
							}
							$decrypts[$d->tbl][$ri] = $d->col;
						}
					}
				
					++$di;
				}
				
				if ($decrypts !== []) {
					foreach ($decrypts as $tbl => $decrypt) {
						if (isset($pks[$tbl])) {
							$pt = \ze\row::get(\ze\ring::chopPrefix(DB_PREFIX, $tbl), $decrypt, $pks[$tbl]);
							//N.b. we're using the local database's API functions here as we only support encryption on tables in the local database.
				
							foreach ($decrypt as $ri => $col) {
								$row[$ri] = $pt[$col];
							}
						}
					}
				}
			}
			
			$di = 0;
			foreach ($row as $ri => &$val) {
				if ($val !== null) {
					$d = &$this->d[$di];
			
					//Ensure that int and float columns are returned as ints and floats, and not strings
					if ($d->isInt) {
						$val = (int) $val;
			
					} elseif ($d->isFloat) {
						$val = (float) $val;
			
					} elseif ($d->isJSON) {
						$val = json_decode($val, true);
					}
				}
				++$di;
			}
		}
	}
	
	
	public function fRow() {
		if ($row = $this->q->fetch_row()) {
			
			$this->fixTypes($row);
			return $row;
		} else {
			return false;
		}
	}
	
	public function fAssoc() {
		if ($row = $this->q->fetch_assoc()) {
			
			$this->fixTypes($row);
			return $row;
		} else {
			return false;
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
	
	
	//
	//	Iterator version of this, for FOR loops
	//
	
	protected $i = 0;
	protected $nr;
	
	public function rewind() {
	}
	
	public function next() {
	}
	
	public function valid() {
		++$this->i;
		return false !== ($this->nr = $this->fAssoc());
	}
	
	public function key() {
		return $this->i;
	}
	
	public function current() {
		return $this->nr;
	}
}





class db {



	//
	// Functions for connecting to a MySQL database
	//
	
	//Formerly "connectLocalDB()"
	public static function connectLocal() {
		if (!\ze::$dbL) {
			\ze::$dbL = new \ze\db(DB_PREFIX, DBHOST, DBNAME, DBUSER, DBPASS, DBPORT);
		} else {
			\ze::$dbL->reconnect(DBHOST, DBNAME, DBUSER, DBPASS, DBPORT);
		}
		
		return !empty(\ze::$dbL->con);
	}


	//Formerly "globalDBDefined()"
	public static function hasGlobal() {
		return defined('DBHOST_GLOBAL')
			&& defined('DBNAME_GLOBAL')
			&& defined('DBUSER_GLOBAL')
			&& defined('DBPASS_GLOBAL')
			&& defined('DB_PREFIX_GLOBAL')
			&& !(DBHOST_GLOBAL == DBHOST && DBNAME_GLOBAL == DBNAME);
	}

	//Formerly "connectGlobalDB()"
	public static function connectGlobal() {
	
		if (!\ze\db::hasGlobal()) {
			return false;
		}
		
		if (!\ze::$dbG) {
			\ze::$dbG = new \ze\db(DB_PREFIX_GLOBAL, DBHOST_GLOBAL, DBNAME_GLOBAL, DBUSER_GLOBAL, DBPASS_GLOBAL, DBPORT_GLOBAL);
		} else {
			\ze::$dbG->reconnect(DBHOST_GLOBAL, DBNAME_GLOBAL, DBUSER_GLOBAL, DBPASS_GLOBAL, DBPORT_GLOBAL);
		}
		
		return !empty(\ze::$dbG->con);
	}


	public static function hasDataArchive() {
		return defined('DBHOST_DA')
			&& defined('DBNAME_DA')
			&& defined('DBUSER_DA')
			&& defined('DBPASS_DA')
			&& defined('DB_PREFIX_DA')
			&& !(DBHOST_DA == DBHOST && DBNAME_DA == DBNAME);
	}

	public static function connectDataArchive() {
	
		if (!\ze\db::hasDataArchive()) {
			return false;
		}
		
		if (!\ze::$dbD) {
			\ze::$dbD = new \ze\db(DB_PREFIX_DA, DBHOST_DA, DBNAME_DA, DBUSER_DA, DBPASS_DA, DBPORT_DA);
		} else {
			\ze::$dbD->reconnect(DBHOST_DA, DBNAME_DA, DBUSER_DA, DBPASS_DA, DBPORT_DA);
		}
		
		return !empty(\ze::$dbD->con);
	}
	
	
	
	
	//
	// Non-static functions, for handling a specific database connection
	//
	
	public $con;
	public $host = '';
	public $name = '';
	public $prefix = '';

	public function __construct($prefix, $host, $name, $user, $pass, $port = '', $reportErrors = true) {
		$this->host = $host;
		$this->name = $name;
		$this->prefix = $prefix;
		
		$this->reconnect($host, $name, $user, $pass, $port, $reportErrors);
	}

	public function disconnect() {
		if ($this->con) {
		
			$rv = $this->con->close();
			$this->con = null;
		
			return $rv;
		}
	}
	
	//Attempt to set the timezone used by the current database connection.
	//Note that this may fail if the 
	public function setTimezone($tz) {
		if ($this->con && $tz) {
			return $this->con->query("SET time_zone = '". \ze\escape::sql($tz). "'");
		}
		return false;
	}

	public function reconnect($host, $name, $user, $pass, $port, $reportErrors = true) {
		if (!$this->con) {
			if (!$this->con = \ze\db::connect($host, $name, $user, $pass, $port, $reportErrors)) {
				if ($reportErrors) {
					if (!defined('SHOW_SQL_ERRORS_TO_VISITORS') || SHOW_SQL_ERRORS_TO_VISITORS !== true) {
						echo 'A database error has occured on this section of the site. Please contact a site Administrator.';
						exit;
		
					} else {
						echo "<p>Sorry, there was a database error. Could not connect to the database using:<ul>
							<li>DBHOST = ". htmlspecialchars($host) ."</li>
							<li>DBNAME = ". htmlspecialchars($name) ."</li>
							<li>DBUSER = ". htmlspecialchars($user) ."</li>
						</ul></p>";
						exit;
					}
				} else {
					return false;
				}
			}
		}
		
		return true;
	}
	


	//Formerly "reviewDatabaseQueryForChanges()"
	public function reviewQueryForChanges(&$sql, &$ids, &$values, $table = false, $runSql = false) {
	
		//Only do the review when Modules are running normally and we're connected to the local db
		if ($this->con) {
			if ($this === \ze::$dbL) {
				return \ze\pageCache::reviewQuery($sql, $ids, $values, $table, $runSql);
			
			} elseif ($runSql) {
				$this->con->query($sql);
				return $this->con->affected_rows;
			} 
		}
	}
	
	
	
	public $cols = [];	//formerly \ze::$dbCols
	public $pks = [];	//formerly \ze::$pkCols

	//Check a table definition and see which columns are numeric
	//Formerly "checkTableDefinition()"
	public function checkTableDef($prefixAndTable, $checkExists = false, $useCache = false) {
		$pkCol = false;
		$exists = false;
	
		if (!$useCache || !isset($this->cols[$prefixAndTable])) {
			$this->cols[$prefixAndTable] = [];
			$useCache = false;
		}
		
		if (!$useCache) {
			if ($checkExists) {
				if (!($q = $this->con->query('SHOW TABLES LIKE \''. \ze\escape::sql($prefixAndTable). '\''))
				 || !($q->fetch_row())) {
					return false;
				}
			}
			
			$q = $this->con->query('SELECT * FROM `'. \ze\escape::sql($prefixAndTable). '` LIMIT 0');
			
			if (!$q) {
				static::handleError($this->con, 'SELECT * FROM `'. \ze\escape::sql($prefixAndTable). '` LIMIT 0');
			}
			
			$fs = $q->fetch_fields();
			foreach ($fs as $f) {
				
				$col = $f->orgname;

			
				//Look out for encrypted versions of columns
				if ($col[0] === '%') {
					//If they exist, load the encryption wrapper library
					\ze\zewl::init();
					//Record that this column should be encrypted
					$this->cols[$prefixAndTable][substr($col, 1)]->encrypted = true;
			
				//Look out for hashed versions of columns
				} elseif ($col[0] === '#') {
					//Record that this column should be hashed
					$this->cols[$prefixAndTable][substr($col, 1)]->hashed = true;
			
				} else {
					$exists = true;
				
					$d = new col($f);
		
					//Also check to see if there is a single primary key column
					//N.b. we check the flag using a bitmask of 2 to see if a column is flagged as primary
					if ($d->isPK) {
						if ($pkCol === false) {
							$pkCol = $col;
						} else {
							$pkCol = true;
						}
					}
				
					$this->cols[$prefixAndTable][$col] = $d;
				}
			}
	
			if (!$exists) {
				$this->pks[$prefixAndTable] = '';
	
			} elseif ($pkCol !== false && $pkCol !== true) {
				$this->pks[$prefixAndTable] = $pkCol;
	
			} else {
				$this->pks[$prefixAndTable] = false;
			}
		}
	
		if ($checkExists && is_string($checkExists)) {
			return
				is_array($this->cols[$prefixAndTable])
				&& isset($this->cols[$prefixAndTable][$checkExists]);
		}
	
		return !empty($this->cols[$prefixAndTable]);
	}
	


	//Formerly "columnIsEncrypted()"
	public function columnIsEncrypted($table, $column) {
	
		$tableName = $this->prefix. $table;
	
		if (!isset($this->cols[$tableName])) {
			$this->checkTableDef($tableName);
		}

		return isset($this->cols[$tableName][$column])? $this->cols[$tableName][$column]->encrypted : null;
	}


	//Formerly "columnIsHashed()"
	public function columnIsHashed($table, $column) {
	
		$tableName = $this->prefix. $table;
	
		if (!isset($this->cols[$tableName])) {
			$this->checkTableDef($tableName);
		}

		return isset($this->cols[$tableName][$column])? $this->cols[$tableName][$column]->hashed : null;
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
	
		if (($q = \ze::$dbL->con->query("SHOW TABLES LIKE '". DB_PREFIX. "local_revision_numbers'"))
		 && ($q->fetch_row())) {
			$sql = "
				INSERT INTO ". DB_PREFIX. "local_revision_numbers SET
					path = 'data_rev',
					patchfile = 'data_rev',
					revision_no = 1
				ON DUPLICATE KEY UPDATE
					revision_no = MOD(revision_no + 1, 4294960000)";
			mysqli_query(\ze::$dbL->con, $sql);
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	


	//Formerly "connectToDatabase()"
	public static function connect($dbhost = 'localhost', $dbname, $dbuser, $dbpass, $dbport = '', $reportErrors = true) {
		$errorText = 'Database connection failure';
	
		try {
		
			if ($dbport) {
				$con = @mysqli_connect($dbhost, $dbuser, $dbpass, $dbname, $dbport);
			} else {
				$con = @mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
			}
		
			if ($con) {
				if (mysqli_query($con,'SET NAMES "UTF8"')
				 && mysqli_query($con,"SET collation_connection='utf8mb4_general_ci'")
				 && mysqli_query($con,"SET collation_server='utf8mb4_general_ci'")
				 && mysqli_query($con,"SET character_set_client='utf8mb4'")
				 && mysqli_query($con,"SET character_set_connection='utf8mb4'")
				 && mysqli_query($con,"SET character_set_results='utf8mb4'")
				 && mysqli_query($con,"SET character_set_server='utf8mb4'")) {
					
					if (defined('DEBUG_USE_STRICT_MODE') && DEBUG_USE_STRICT_MODE) {
						mysqli_query($con,"SET @@SESSION.sql_mode = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE'");
					} else {
						mysqli_query($con,"SET @@SESSION.sql_mode = ''");
					}
					//N.b. we don't support the new ONLY_FULL_GROUP_BY option in 5.7, as some of our queries rely on this being disabled.
					
					return $con;
				}
			}
		} catch (\Exception $e) {
		}
	
		if ($reportErrors) {
			\ze\db::reportError($errorText, @mysqli_errno($con), @mysqli_error($con));
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
				FROM ". DB_PREFIX. "local_revision_numbers
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
		if (!\ze::$dbL->checkTableDef(DB_PREFIX. 'site_settings', true)) {
			return;
		}
	
		$sql = "
			SELECT name, IFNULL(value, default_value), ". (\ze::$dbL->checkTableDef(DB_PREFIX. 'site_settings', 'encrypted', true)? 'encrypted' : '0'). "
			FROM ". DB_PREFIX. "site_settings
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
			
			//Also try to set the timezone for the local database connection to the same value
			\ze::$dbL->setTimezone(\ze::$siteConfig['zenario_timezones__default_timezone']);
		} else {
			\ze::$dbL->setTimezone(date_default_timezone_get());
		}
	
	
		//Load information on the special pages and their language equivalences
		if (!\ze::$dbL->checkTableDef(DB_PREFIX. 'content_items', true)
		 || !\ze::$dbL->checkTableDef(DB_PREFIX. 'special_pages', true)) {
			return;
		}
	
		$sql = "
			SELECT sp.page_type, c.equiv_id, c.language_id, c.id, c.type
			FROM ". DB_PREFIX. "special_pages AS sp
			INNER JOIN ". DB_PREFIX. "content_items AS c
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
		if (!\ze::$dbL->checkTableDef(DB_PREFIX. 'languages', 'show_untranslated_content_items')) {
			return;
		}
	
		\ze::$langs = \ze\sql::fetchAssocs('SELECT id, translate_phrases, domain, show_untranslated_content_items FROM '. DB_PREFIX. 'languages', 'id');
	
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
	public static function handleError($con, $sql) {
		$sqlErrno = mysqli_errno($con);
		$sqlError = mysqli_error($con);
	
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
		
		self::showTraceback("Database query error: ". $sqlErrno. ", ". $sqlError. ",\n". $sql, $debugBacktrace);
	}
	
	protected static function showTraceback($errorText, $debugBacktrace) {
	
		if ($addDiv = !empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'ajax.php') === false) {
			echo '<div id="error_information">';
		}
	
		echo "\n", $errorText, "\n\nTrace-back information:\n";
	
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
		$lastDB = \ze::$dbL;
		\ze::$dbL = null;
	
		\ze\server::sendEmail(
			$subject, $body,
			EMAIL_ADDRESS_GLOBAL_SUPPORT,
			$addressToOverriddenBy,
			$nameTo = false,
			$addressFrom = \ze::setting('email_address_from') ?: EMAIL_ADDRESS_GLOBAL_SUPPORT,
			$nameFrom = false,
			false, false, false,
			$isHTML = false);
	
		\ze::$dbL = $lastDB;
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
	
		$debugBacktrace = debug_backtrace();
		$debugBacktrace = \ze\db::trimDebugBacktrace($debugBacktrace);
	
		if (defined('DEBUG_SEND_EMAIL') && DEBUG_SEND_EMAIL === true) {
			\ze\db::reportError('Database query error', '', $error, '', $debugBacktrace);
		}
		
		if (defined('RUNNING_FROM_COMMAND_LINE')) {
			echo "Database query error\n\n". $error. "\n\n";
			exit;
		}
	
		if (!defined('SHOW_SQL_ERRORS_TO_VISITORS') || SHOW_SQL_ERRORS_TO_VISITORS !== true) {
			echo 'A database error has occured on this section of the site. Please contact a site Administrator.';
			exit;
		}
		
		self::showTraceback($error, $debugBacktrace);
	}





	//Formerly "hashDBColumn()"
	public static function hashDBColumn($val) {
		return hash('sha256', \ze::$siteConfig['site_id']. strtolower($val), true);
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
}