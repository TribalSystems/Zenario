<?php
/*
 * Copyright (c) 2023, Tribal Limited
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






class sql {








	//Replacement for mysql_affected_rows()
	//Formerly "sqlAffectedRows()"
	public static function affectedRows() {
		return static::$db->con->affected_rows;
	}

	//Replacement for mysql_error()
	//Formerly "sqlError()"
	public static function error() {
		return static::$db->con->error;
	}

	//Replacement for mysql_errno()
	//Formerly "sqlErrno()"
	public static function errno() {
		return static::$db->con->errno;
	}

	//Replacement for mysql_fetch_assoc()
	//Formerly "sqlFetchAssoc()"
	public static function fetchAssoc($result) {
		if (is_string($result)) {
			$result = static::select($result);
		}
		return $result->fAssoc();
	}

	//Replacement for mysql_fetch_row()
	//Formerly "sqlFetchRow()"
	public static function fetchRow($result) {
		if (is_string($result)) {
			$result = static::select($result);
		}
		return $result->fRow();
	}

	//Replacement for mysql_insert_id()
	//Formerly "sqlInsertId()"
	public static function insertId() {
		return static::$db->con->insert_id;
	}

	//Replacement for mysql_num_rows()
	//Formerly "sqlNumRows()"
	public static function numRows($result) {
		if (is_string($result)) {
			$result = static::select($result);
		}
		return $result->q->num_rows;
	}

	//Fetch just one value from a SQL query
	//Formerly "sqlFetchValue()"
	public static function fetchValue($result) {
		if ($row = static::fetchRow($result)) {
			return $row[0];
		} else {
			return false;
		}
	}

	//Fetch multiple values from a SQL query (one column, multiple rows)
	//Formerly "sqlFetchValues()"
	public static function fetchValues($result, $indexBySecondColumn = false) {
		if (is_string($result)) {
			$result = static::select($result);
		}
		$out = [];
		while ($row = static::fetchRow($result)) {
			if ($indexBySecondColumn) {
				$out[$row[1]] = $row[0];
			} else {
				$out[] = $row[0];
			}
		}
		return $out;
	}

	//Fetch multiple values from a SQL query (multiple columns, multiple rows)
	//Formerly "sqlFetchAssocs()"
	public static function fetchAssocs($result, $indexBy = false) {
		if (is_string($result)) {
			$result = static::select($result);
		}
		$out = [];
		while ($row = static::fetchAssoc($result)) {
			if ($indexBy === false) {
				$out[] = $row;
			} else {
				$out[$row[$indexBy]] = $row;
			}
		}
		return $out;
	}
	//Formerly "sqlFetchRows()"
	public static function fetchRows($result) {
		if (is_string($result)) {
			$result = static::select($result);
		}
		$out = [];
		while ($row = static::fetchRow($result)) {
			$out[] = $row;
		}
		return $out;
	}




	//Runs a SQL query without updating the revision number or clearing the cache
	//Formerly "sqlSelect()"
	public static function select($sql, $storeResult = true) {
	
		//if (!static::$db->con) {
		//	return false;
		//}
		
		if ($result = static::$db->con->query($sql, $storeResult? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT)) {
			return new queryCursor(static::$db, $result);
	
		} else {
			\ze\db::handleError(static::$db->con, $sql);
		}
	}

	//Runs a SQL query and always updates the revision number and clears the cache if needed
	//Formerly "sqlUpdate()"
	public static function update($sql, $checkCache = true, $checkRevNo = true) {
	
		//if (!static::$db->con) {
		//	return false;
		//}
		
		if ($result = static::$db->con->query($sql)) {
		
			if ($checkRevNo && static::$db->con->affected_rows) {
				\ze\db::updateDataRevisionNumber();
		
				if ($checkCache) {
					$ids = $values = false;
					static::$db->reviewQueryForChanges($sql, $ids, $values);
				}
			}
			return $result;
	
		} else {
			\ze\db::handleError(static::$db->con, $sql);
		}
	}
	
	//Run an update that doesn't need to clear the cache or data revision numbers
	public static function cacheFriendlyUpdate($sql) {
		return static::update($sql, false, false);
	}


	//Formerly "getNextAutoIncrementId()"
	public static function getNextAutoIncrementId($table) {
		if ($row = static::fetchAssoc("SHOW TABLE STATUS LIKE '". \ze\escape::sql(static::$db->prefix. $table). "'")) {
			return $row['Auto_increment'];
		}
		return false;
	}
	
	
	
	
	
	//Formerly "paginationLimit()"
	public static function limit($page, $pageSize, $offset = 0) {
		return "
			LIMIT ". static::pageStart($page, $pageSize, $offset). ", ". (int) $pageSize;
	}
	
	public static function pageStart($page, $pageSize, $offset = 0) {
		$start = max((( (int) $page - 1) * (int) $pageSize) + $offset, 0);
		
		if (is_int($start)) {
			return $start;
		} else {
			return 0;
		}
	}


	
	protected static $db;
	public static function init(&$db) {
		static::$db = &$db;
	}
}
\ze\sql::init(\ze::$dbL);