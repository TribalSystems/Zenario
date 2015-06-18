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

require CMS_ROOT. 'zenario/includes/database_connection.inc.php';


//	function checkRowExists($table, $ids) {}

//	function connectGlobalDB() {}

//	function connectLocalDB() {}

function deleteRow($table, $ids, $multiple = true) {
	return checkRowExists($table, $ids, false, false, $multiple, true);
}

function getRow($table, $cols, $ids, $notZero = false) {
	return checkRowExists($table, $ids, $cols, $notZero);
}

function getRows($table, $cols, $ids, $orderBy = array(), $notZero = false) {
	return checkRowExists($table, $ids, $cols, $notZero, true, false, $orderBy);
}

function getDistinctRows($table, $cols, $ids, $orderBy = array(), $notZero = false) {
	return checkRowExists($table, $ids, $cols, $notZero, true, false, $orderBy, true);
}

function getRowsArray($table, $cols, $ids = array(), $orderBy = array(), $notZero = false) {
	return checkRowExists($table, $ids, $cols, $notZero, true, false, $orderBy, false, true);
}

function getDistinctRowsArray($table, $cols, $ids = array(), $orderBy = array(), $notZero = false) {
	return checkRowExists($table, $ids, $cols, $notZero, true, false, $orderBy, true, true);
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

function insertRow($table, $values, $insertIgnore = false) {
	return setRow($table, $values, array(), true, $insertIgnore);
}

function likeEscape($sql, $allowStarsAsWildcards = false, $asciiCharactersOnly = false) {
	
	if ($asciiCharactersOnly) {
		//http://stackoverflow.com/questions/8781911/remove-non-ascii-characters-from-string-in-php
		$sql = preg_replace('/[^\x20-\x7E]/', '', $sql);
	}
	
	if (!$allowStarsAsWildcards) {
		return str_replace('%', '\\%', str_replace('_', '\\_', sqlEscape($sql)));
	
	} elseif ($sql == '*') {
		return '_';
	
	} else {
		return str_replace('*', '%', str_replace('%', '\\%', str_replace('_', '\\_', sqlEscape($sql))));
	}
}

//	function my_mysql_query($sql, $updateDataRevisionNumber = -1, $checkCache = true, $return = 'sqlSelect') {}

function paginationLimit($page, $pageSize) {
	return "
		LIMIT ". (max(((int) $page - 1) * (int) $pageSize, 0)). ", ". (int) $pageSize;
}

//	function setRow($table, $values, $ids) {}

function updateRow($table, $values, $ids) {
	return setRow($table, $values, $ids, false);
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
	return $result->fetch_array();
}

//Replacement for mysql_fetch_assoc()
function sqlFetchAssoc($result) {
	return $result->fetch_assoc();
}

//Replacement for mysql_fetch_row()
function sqlFetchRow($result) {
	return $result->fetch_row();
}

//Replacement for mysql_insert_id()
function sqlInsertId() {
	return cms_core::$lastDB->insert_id;
}

//Replacement for mysql_num_rows()
function sqlNumRows($result) {
	return $result->num_rows;
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
				cms_core::reviewDatabaseQueryForChanges($sql, $ids, $values);
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

