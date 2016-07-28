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

require CMS_ROOT. 'zenario/includes/database_connection.inc.php';

cms_core::$whitelist[] = 'checkRowExists';
//	function checkRowExists($table, $ids, $ignoreMissingColumns = false) {}

//	function connectGlobalDB() {}

//	function connectLocalDB() {}

function deleteRow($table, $ids, $multiple = true) {
	return checkRowExists($table, $ids, false, false, $multiple, 'delete');
}

cms_core::$whitelist[] = 'getRow';
function getRow($table, $cols, $ids, $ignoreMissingColumns = false) {
	return checkRowExists($table, $ids, $ignoreMissingColumns, $cols);
}

cms_core::$whitelist[] = 'getRows';
function getRows($table, $cols, $ids, $orderBy = array(), $ignoreMissingColumns = false) {
	return checkRowExists($table, $ids, $ignoreMissingColumns, $cols, true, false, $orderBy);
}

cms_core::$whitelist[] = 'getDistinctRows';
function getDistinctRows($table, $cols, $ids, $orderBy = array(), $ignoreMissingColumns = false) {
	return checkRowExists($table, $ids, $ignoreMissingColumns, $cols, true, false, $orderBy, true);
}

cms_core::$whitelist[] = 'getRowsArray';
function getRowsArray($table, $cols, $ids = array(), $orderBy = array(), $indexBy = false, $ignoreMissingColumns = false) {
	return checkRowExists($table, $ids, $ignoreMissingColumns, $cols, true, false, $orderBy, false, $indexBy? $indexBy : true);
}

cms_core::$whitelist[] = 'getDistinctRowsArray';
function getDistinctRowsArray($table, $cols, $ids = array(), $orderBy = array(), $indexBy = false, $ignoreMissingColumns = false) {
	return checkRowExists($table, $ids, $ignoreMissingColumns, $cols, true, false, $orderBy, true, $indexBy? $indexBy : true);
}

//New in 7.1
cms_core::$whitelist[] = 'selectCount';
function selectCount($table, $ids = array()) {
	return checkRowExists($table, $ids, false, false, false, 'count');
}

cms_core::$whitelist[] = 'selectMax';
function selectMax($table, $cols, $ids = array(), $ignoreMissingColumns = false) {
	return checkRowExists($table, $ids, $ignoreMissingColumns, $cols, false, 'max');
}

cms_core::$whitelist[] = 'selectMin';
function selectMin($table, $cols, $ids = array(), $ignoreMissingColumns = false) {
	return checkRowExists($table, $ids, $ignoreMissingColumns, $cols, false, 'min');
}

function getNextAutoIncrementId($table) {
	if ($row = sqlFetchAssoc(sqlSelect("SHOW TABLE STATUS LIKE '". sqlEscape(cms_core::$lastDBPrefix. $table). "'"))) {
		return $row['Auto_increment'];
	}
	return false;
}

//Look up the name of the primary/foreign key column
function getIdColumnOfTable($table, $guess = false) {
	checkTableDefinition(DB_NAME_PREFIX. $table);		
	if (cms_core::$pkCols[DB_NAME_PREFIX. $table]) {
		return cms_core::$pkCols[DB_NAME_PREFIX. $table];
	
	} elseif ($guess) {
		return 'id';
	
	} else {
		return false;
	}
}

//New in 7.3, this automatically fixes a bug where data from MySQL is loaded as a string,
//and not an int or a float
function correctMySQLDatatypes($table, &$data) {
	if (!isset(cms_core::$numericCols[cms_core::$lastDBPrefix. $table])) {
		checkTableDefinition(cms_core::$lastDBPrefix. $table);
	}
	$numericCols = &cms_core::$numericCols[cms_core::$lastDBPrefix. $table];
	
	foreach ($data as $key => &$value) {
		if (isset($numericCols[$key])) {
			if ($numericCols[$key] === ZENARIO_INT_COL) {
				$value = (int) $value;
			} elseif ($numericCols[$key] === ZENARIO_FLOAT_COL) {
				$value = (float) $value;
			}
		}
	}
}



function inEscape($csv, $escaping = 'sql', $prefix = false) {
	if (!is_array($csv)) {
		$csv = explode(',', $csv);
	}
	$sql = '';
	foreach ($csv as $var) {
		$var = trim($var);
		
		if ($sql !== '') {
			$sql .= ',';
		}
		
		if ($escaping === 'numeric' || $escaping === true) {
			$sql .= (int) $var;
		
		} elseif ($escaping === 'sql') {
			$sql .= "'". sqlEscape($var). "'";
		
		} elseif ($escaping === 'identifier') {
			if ($prefix) {
				$sql .= $prefix. ".";
			}
			
			$sql .= "`". sqlEscape($var). "`";
		
		} else {
			$sql .= str_replace(',', '', $var);
		}
	}
	return $sql;
}
//	function inEscape($csv, $escaping = 'sql') {}


function insertRow($table, $values, $ignore = false, $ignoreMissingColumns = false, $markNewThingsInSession = false) {
	return setRow($table, $values, array(), $ignore, $ignoreMissingColumns, $markNewThingsInSession, true);
}

function likeEscape($text, $allowStarsAsWildcards = false, $asciiCharactersOnly = false, $sqlEscape = true) {
	
	if ($asciiCharactersOnly) {
		//http://stackoverflow.com/questions/8781911/remove-non-ascii-characters-from-string-in-php
		$text = preg_replace('/[^\x20-\x7E]/', '', $text);
	}
	
	if ($sqlEscape) {
		$text = sqlEscape($text);
	}
	
	if (!$allowStarsAsWildcards) {
		return str_replace('%', '\\%', str_replace('_', '\\_', $text));
	
	} elseif ($text == '*') {
		return '_';
	
	} else {
		return str_replace('*', '%', str_replace('%', '\\%', str_replace('_', '\\_', $text)));
	}
}

//	function my_mysql_query($sql, $updateDataRevisionNumber = -1, $checkCache = true, $return = 'sqlSelect') {}

function paginationLimit($page, $pageSize, $offset = 0) {
	return "
		LIMIT ". (max((( (int) $page - 1) * (int) $pageSize) + $offset, 0)). ", ". (int) $pageSize;
}

//	function setRow($table, $values, $ids, $ignore = false, $ignoreMissingColumns = false, $markNewThingsInSession = false) {}

function updateRow($table, $values, $ids, $ignore = false, $ignoreMissingColumns = false) {
	return setRow($table, $values, $ids, $ignore, $ignoreMissingColumns, false, false);
}



//
// Easy backwards compatability for code that used to use the old mysql module
//

//Replacement for mysql_affected_rows()
function sqlAffectedRows() {
	return cms_core::$lastDB->affected_rows;
}

//Replacement for mysql_error()
function sqlError() {
	return cms_core::$lastDB->error;
}

//Replacement for mysql_errno()
function sqlErrno() {
	return cms_core::$lastDB->errno;
}

//Replacement for mysql_fetch_array()
function sqlFetchArray($result) {
	if (is_string($result)) {
		$result = sqlSelect($result);
	}
	return $result->fetch_array();
}

//Replacement for mysql_fetch_assoc()
function sqlFetchAssoc($result) {
	if (is_string($result)) {
		$result = sqlSelect($result);
	}
	return $result->fetch_assoc();
}

//Replacement for mysql_fetch_row()
function sqlFetchRow($result) {
	if (is_string($result)) {
		$result = sqlSelect($result);
	}
	return $result->fetch_row();
}

//Replacement for mysql_insert_id()
function sqlInsertId() {
	return cms_core::$lastDB->insert_id;
}

//Replacement for mysql_num_rows()
function sqlNumRows($result) {
	if (is_string($result)) {
		$result = sqlSelect($result);
	}
	return $result->num_rows;
}

//New in 7.3, this quickly gets an array from a sql query
function sqlSelectArray($result, $oneCol = false) {
	if (is_string($result)) {
		$result = sqlSelect($result);
	}
	$out = array();
	if ($oneCol) {
		while ($row = sqlFetchRow($result)) {
			$out[] = $row[0];
		}
	} else {
		while ($row = sqlFetchAssoc($result)) {
			$out[] = $row;
		}
	}
	return $out;
}

//Replacement for mysql_query()
//Runs a SQL query without updating the revision number or clearing the cache
function sqlSelect($sql) {
	
	if (!cms_core::$lastDB) {
		return false;
	
	} elseif ($result = cms_core::$lastDB->query($sql)) {
		return $result;
	
	} else {
		handleDatabaseError(cms_core::$lastDB, $sql);
	}
}

//Runs a SQL query and always updates the revision number and clears the cache if needed
function sqlUpdate($sql, $checkCache = true) {
	
	if (!cms_core::$lastDB) {
		return false;
	
	} elseif ($result = cms_core::$lastDB->query($sql)) {
		
		if (cms_core::$lastDB->affected_rows) {
			updateDataRevisionNumber();
		
			if ($checkCache) {
				$ids = $values = false;
				reviewDatabaseQueryForChanges($sql, $ids, $values);
			}
		}
		return $result;
	
	} else {
		handleDatabaseError(cms_core::$lastDB, $sql);
	}
}

//Smartly calls either sqlSelect() or sqlUpdate()
function sqlQuery($sql, $checkCache = true) {
	$test = strtoupper(substr(trim($sql), 0, 3));
	if ($test != 'DES' && $test != 'SEL' && $test != 'SET' && $test != 'SHO') {
		return sqlUpdate($sql);
	} else {
		return sqlSelect($sql);
	}
}

//Replacement for mysql_real_escape_string()
function sqlEscape($text) {
	return cms_core::$lastDB->escape_string($text);
}

//New in 7.3
function getNewThingFromSession($table, $clear = true) {
	$return = false;
	if (isset($_SESSION['new_id_in_'. $table])) {
		$return = $_SESSION['new_id_in_'. $table];
		
		if ($clear) {
			unset($_SESSION['new_id_in_'. $table]);
		}
	}
	return $return;
}


