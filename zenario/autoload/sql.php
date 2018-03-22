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






class sql {








	//Replacement for mysql_affected_rows()
	//Formerly "sqlAffectedRows()"
	public static function affectedRows() {
		return \ze::$lastDB->affected_rows;
	}

	//Replacement for mysql_error()
	//Formerly "sqlError()"
	public static function error() {
		return \ze::$lastDB->error;
	}

	//Replacement for mysql_errno()
	//Formerly "sqlErrno()"
	public static function errno() {
		return \ze::$lastDB->errno;
	}

	//Replacement for mysql_fetch_array()
	//Formerly "sqlFetchArray()"
	public static function fetchArray($result, $mrg = false) {
		if (is_string($result)) {
			$result = \ze\sql::select($result, $mrg);
		}
		if ($row = $result->q->fetch_array()) {
			if ($result->colDefs) {
				$result->parseAssoc($row);
			}
			return $row;
		} else {
			return false;
		}
	}

	//Replacement for mysql_fetch_assoc()
	//Formerly "sqlFetchAssoc()"
	public static function fetchAssoc($result, $mrg = false) {
		if (is_string($result)) {
			$result = \ze\sql::select($result, $mrg);
		}
		if ($row = $result->q->fetch_assoc()) {
			if ($result->colDefs) {
				$result->parseAssoc($row);
			}
	
			return $row;
		} else {
			return false;
		}
	}

	//Replacement for mysql_fetch_row()
	//Formerly "sqlFetchRow()"
	public static function fetchRow($result, $mrg = false) {
		if (is_string($result)) {
			$result = \ze\sql::select($result, $mrg);
		}
		if ($row = $result->q->fetch_row()) {
			if ($result->colDefs) {
				$result->parseRow($row);
			}
			return $row;
		} else {
			return false;
		}
	}

	//Replacement for mysql_insert_id()
	//Formerly "sqlInsertId()"
	public static function insertId() {
		return \ze::$lastDB->insert_id;
	}

	//Replacement for mysql_num_rows()
	//Formerly "sqlNumRows()"
	public static function numRows($result, $mrg = false) {
		if (is_string($result)) {
			$result = \ze\sql::select($result, $mrg);
		}
		return $result->q->num_rows;
	}

	//Fetch just one value from a SQL query
	//Formerly "sqlFetchValue()"
	public static function fetchValue($result, $mrg = false) {
		if ($row = \ze\sql::fetchRow($result, $mrg)) {
			return $row[0];
		} else {
			return false;
		}
	}

	//Fetch multiple values from a SQL query (one column, multiple rows)
	//Formerly "sqlFetchValues()"
	public static function fetchValues($result, $mrg = false, $numeric = false) {
		if (is_string($result)) {
			$result = \ze\sql::select($result, $mrg);
		}
		$out = [];
		while ($row = \ze\sql::fetchRow($result)) {
			if ($numeric) {
				$out[] = (int) $row[0];
			} else {
				$out[] = $row[0];
			}
		}
		return $out;
	}

	//Fetch multiple values from a SQL query (multiple columns, multiple rows)
	//Formerly "sqlFetchAssocs()"
	public static function fetchAssocs($result, $mrg = false, $indexBy = false) {
		if (is_string($result)) {
			$result = \ze\sql::select($result, $mrg);
		}
		$out = [];
		while ($row = \ze\sql::fetchAssoc($result)) {
			if ($indexBy === false) {
				$out[] = $row;
			} else {
				$out[$row[$indexBy]] = $row;
			}
		}
		return $out;
	}
	//Formerly "sqlFetchRows()"
	public static function fetchRows($result, $mrg = false) {
		if (is_string($result)) {
			$result = \ze\sql::select($result, $mrg);
		}
		$out = [];
		while ($row = \ze\sql::fetchRow($result)) {
			$out[] = $row;
		}
		return $out;
	}



	//Formerly "sqlAddMergeFields()"
	public static function addMergeFields(&$sql, &$mrg, &$colDefs, $tables = []) {
	
		$cacheQuery = strlen($sql) < 1024;
	
		//Check to see if we've got this SQL source cached 
		if ($cacheQuery && isset(\ze::$pq[$sql])) {
			//If so, use the previous values
			$details = &\ze::$pq[$sql];
			$parts = &$details[0];
			$count = &$details[1];
			$tables = &$details[2];
	
		//If not, we'll need to parse it using a preg_split()
		} else {
			$parts = preg_split('@\[(\w+)(|\.\w+)\s*(|AS|=|==|\!=)\s*([\w\/]*)\]@is', $sql, -1,  PREG_SPLIT_DELIM_CAPTURE);
			$count = count($parts) - 1;
	
			//Do an intial sweep, looking for table definitions
			for ($j=0; $j < $count; $j += 5) {
		
				$a = &$parts[$j+1];
				$b = &$parts[$j+2];
				$c = &$parts[$j+3];
				$d = &$parts[$j+4];
			
				$c = strtoupper($c);
				$isAs = $c == 'AS';
		
				if ($isAs && ($b === '' || defined($a))) {
					if ($b !== '') {
						$tableName = DB_NAME_PREFIX. constant($a). substr($b, 1);
					} else {
						$tableName = DB_NAME_PREFIX. $a;
					}
					$tables[$d] = $tableName;
			
					if (!isset(\ze::$dbCols[$tableName])) {
						\ze\row::cacheTableDef($tableName);
					}
				}
			}
		
			//Cache the last 5 queries, so we don't repeatedly perform the preg_split
			//above on the same query.
			if ($cacheQuery) {
				if (count(\ze::$pq) >= 5) {
					array_splice(\ze::$pq, 0, 1);
				}
		
				\ze::$pq[$sql] = [$parts, $count, $tables];
			}
		}
		
		//Loop through the parts of the SQL query, rewriting it in a few places where we
		//add merge fields or encrypted columns
		$sql = '';
		for ($j=0; $j < $count; $j += 5) {
		
			$a = &$parts[$j+1];
			$b = &$parts[$j+2];
			$c = &$parts[$j+3];
			$d = &$parts[$j+4];
			$isAs = $c == 'AS';
		
			$sql .= $parts[$j];
		
			//Table definitions
			if ($isAs && isset($tables[$d])) {
				$tableName = $tables[$d];
			
				$sql .= '`'. $tableName. '` AS '. $d;
		
			//Merge fields that are unrelated to table/columns
			} elseif ($b === '') {
				\ze\sql::applyMergeField($sql, $mrg, $a);
		
			} elseif ($b === '.likeEscape' && isset($mrg[$a])) {
				$sql .= \ze\escape::like($mrg[$a]);
		
			//Columns
			} else {
				$tableName = $tables[$a];
				$colName = $as = substr($b, 1);
				$colDef = &\ze::$dbCols[$tableName][$colName];
			
				//Check the operation sign on this column
				switch ($c) {
					//Inserting/updating data into a column
					case '=':
						if ($colDef->encrypted) {
							if ($colDef->hashed) {
								//If a column is both encrypted and hashed, we'll need to insert two values
								$sql .= $a. '.`#'. $colName. '` '. $c. ' ';
								\ze\sql::applyMergeField($sql, $mrg, $d, $colDef, true);
								$sql .= ', ';
							}
						
							$colName = '%'. $colName;
						}
		
						$sql .= $a. '.`'. $colName. '` '. $c. ' ';
						\ze\sql::applyMergeField($sql, $mrg, $d, $colDef);
						break;
				
					//Reading a value from a table
					case 'AS':
						$as = $d;
					case '':
						//If a column is encrypted, we'll need to read data from the encrypted version of the column
						if ($colDef->encrypted) {
							$colName = '%'. $colName;
						}
		
						$sql .= $a. '.`'. $colName. '`';
					
						if ($as != $colName) {
							$sql .= ' AS `'. $as. '`';
						}
				
						$colDefs[$as] = $colDef;
						break;
				
					//Checking the values of  columns
					case '==':
						$c = '=';
					default:
						//Check to see if a column is hashed, and check the hashed value
						//If a column is hashed then only equals or not-equals is possible; e.g. you can't do < or >
						if ($colDef->encrypted) {
							if (!$colDef->hashed) {
								\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The column `[[col]]` in the table `[[table]]` is encrypted and cannot be used in a WHERE-statement.', ['col' => $colName, 'table' => $tableName]));
							}
					
							$colName = '#'. $colName;
						}
					
						$sql .= $a. '.`'. $colName. '` '. $c. ' ';
						\ze\sql::applyMergeField($sql, $mrg, $d, $colDef, true);
						break;
				}
			}	
		}
	
		$sql .= $parts[$count];
	}

	//Formerly "sqlApplyMergeField()"
	public static function applyMergeField(&$sql, &$mrg, $key, $colDef = false, $useHash = false) {
	
		if (is_array($mrg) && isset($mrg[$key])) {
			$val = &$mrg[$key];
	
		} else {
			$sql .= 'NULL';
			return;
		}
	
		//Inserting/updating data into a column
		if ($colDef) {
			if ($colDef->encrypted) {
				if ($useHash) {
					$cipher = \ze\db::hashDBColumn($val);
				} else {
					$cipher = \ze\zewl::encrypt($val, true);
				}
				$sql .= "'". \ze\escape::sql($cipher). "'";
		
			} elseif ($colDef->isFloat) {
				$sql .= (float) $val;
		
			} elseif ($colDef->isInt) {
				$sql .= (int) $val;
		
			} else {
				$sql .= "'". \ze\escape::sql($val). "'";
			}
	
		//Call \ze\escape::in() for arrays
		} elseif (is_array($val)) {
			$sql .= \ze\escape::in($val);
	
		//Try and auto-detect the value of anything else
		} else {
			$sql .= \ze\escape::stringToIntOrFloat($val, true);
		}
	}

	//Replacement for mysql_query()
	//Runs a SQL query without updating the revision number or clearing the cache
	//Formerly "sqlSelect()"
	public static function select($sql, $mrg = false, $tableName = false, $storeResult = true) {
	
		if (!\ze::$lastDB) {
			return false;
		}
	
		//Attempt to get a list of column definitions for the columns we are about to select from
		$colDefs = [];
		$colDefsAreSpecfic = false;
		if ($mrg) {
			\ze\sql::addMergeFields($sql, $mrg, $colDefs);
			$colDefsAreSpecfic = true;
	
		} elseif ($tableName) {
			if (!isset(\ze::$dbCols[$tableName])) {
				\ze\row::cacheTableDef($tableName);
			}
			$colDefs = &\ze::$dbCols[$tableName];
		}
		
		if ($result = \ze::$lastDB->query($sql, $storeResult? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT)) {
			return new SQLQueryWrapper($result, $colDefs, $colDefsAreSpecfic);
	
		} else {
			\ze\db::handleError(\ze::$lastDB, $sql);
		}
	}

	//Runs a SQL query and always updates the revision number and clears the cache if needed
	//Formerly "sqlUpdate()"
	public static function update($sql, $mrg = false, $checkCache = true) {
	
		if (!\ze::$lastDB) {
			return false;
		}
	
		if ($mrg) {
			$colDefs = [];
			\ze\sql::addMergeFields($sql, $mrg, $colDefs);
		}
	
		if ($result = \ze::$lastDB->query($sql)) {
		
			if (\ze::$lastDB->affected_rows) {
				\ze\db::updateDataRevisionNumber();
		
				if ($checkCache) {
					$ids = $values = false;
					\ze\db::reviewQueryForChanges($sql, $ids, $values);
				}
			}
			return new SQLQueryWrapper($result);
	
		} else {
			\ze\db::handleError(\ze::$lastDB, $sql);
		}
	}
	
	//Formerly "paginationLimit()"
	public static function limit($page, $pageSize, $offset = 0) {
		return "
			LIMIT ". self::pageStart($page, $pageSize, $offset). ", ". (int) $pageSize;
	}
	
	public static function pageStart($page, $pageSize, $offset = 0) {
		return max((( (int) $page - 1) * (int) $pageSize) + $offset, 0);
	}




}