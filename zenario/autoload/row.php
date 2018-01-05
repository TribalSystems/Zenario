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





class row {




	
	
	

	//Check a table definition and see which columns are numeric
	//Formerly "checkTableDefinition()"
	public static function cacheTableDef($prefixAndTable, $checkExists = false, $useCache = false) {
		$pkCol = false;
		$exists = false;
	
		if (!$useCache || !isset(\ze::$dbCols[$prefixAndTable])) {
			\ze::$dbCols[$prefixAndTable] = array();
			$useCache = false;
		}
	
		if (!\ze::$lastDB) {
			return false;
		}
	
		if (!$useCache) {
			if ($checkExists
			 && !(($result = \ze\sql::select("SHOW TABLES LIKE '". \ze\escape::sql($prefixAndTable). "'"))
			   && (\ze\sql::fetchRow($result))
			)) {
				return false;
			}
	
			if ($result = \ze\sql::select('SHOW COLUMNS FROM `'. \ze\escape::sql($prefixAndTable). '`')) {
				while ($row = \ze\sql::fetchRow($result)) {
					$col = &$row[0];
				
					//Look out for encrypted versions of columns
					if ($col[0] === '%') {
						//If they exist, load the encryption wrapper library
						\ze\zewl::init();
						//Record that this column should be encrypted
						\ze::$dbCols[$prefixAndTable][substr($col, 1)]->encrypted = true;
				
					//Look out for hashed versions of columns
					} elseif ($col[0] === '#') {
						//Record that this column should be hashed
						\ze::$dbCols[$prefixAndTable][substr($col, 1)]->hashed = true;
				
					} else {
						$exists = true;
					
						$colDef = new SQLCol;
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
					
						\ze::$dbCols[$prefixAndTable][$col] = $colDef;
					}
				}
			}
	
			if (!$exists) {
				\ze::$pkCols[$prefixAndTable] = '';
	
			} elseif ($pkCol !== false && $pkCol !== true) {
				\ze::$pkCols[$prefixAndTable] = $pkCol;
	
			} else {
				\ze::$pkCols[$prefixAndTable] = false;
			}
		}
	
		if ($checkExists && is_string($checkExists)) {
			return
				is_array(\ze::$dbCols[$prefixAndTable])
				&& isset(\ze::$dbCols[$prefixAndTable][$checkExists]);
		}
	
		return !empty(\ze::$dbCols[$prefixAndTable]);
	}

	//Helper function for selectInternal
	//Formerly "checkRowExistsCol()"
	public static function writeCol(&$tableName, &$sql, &$col, &$val, &$first, $isWhere, $ignoreMissingColumns = false, $sign = '=', $in = 0, $wasNot = false) {
	
		if (!isset(\ze::$dbCols[$tableName][$col])) {
			\ze\row::cacheTableDef($tableName);
		}
	
		if (!isset(\ze::$dbCols[$tableName][$col])) {
			if ($ignoreMissingColumns && !$isWhere) {
				return;
			} else {
				\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The column `[[col]]` does not exist in the table `[[table]]`.', array('col' => $col, 'table' => $tableName)));
			}
		}
	
		$colDef = &\ze::$dbCols[$tableName][$col];
	
	
		if ($colDef->encrypted) {
			if ($isWhere) {
				if (!$colDef->hashed) {
					\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The column `[[col]]` in the table `[[table]]` is encrypted and cannot be used in a WHERE-statement.', array('col' => $col, 'table' => $tableName)));
				}
			} else {
				$sql .= ($first? '' : ','). '`%'. \ze\escape::sql($col). '` = \''. \ze\escape::sql((string) \ze\zewl::encrypt($val, true)). '\'';
			
				if ($colDef->hashed) {
					$sql .= ', `#'. \ze\escape::sql($col). '` = \''. \ze\escape::sql(\ze\db::hashDBColumn($val)). '\'';
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
							\ze\row::writeCol($tableName, $sql, $col, $val2, $first, $isWhere, $ignoreMissingColumns, $wasNot? 'NOT (' : '(', 1);
							$firstIn = false;
						} else {
							\ze\row::writeCol($tableName, $sql, $col, $val2, $first, $isWhere, $ignoreMissingColumns, ' OR ', 2);
						}
					} else {
						if ($firstIn) {
							\ze\row::writeCol($tableName, $sql, $col, $val2, $first, $isWhere, $ignoreMissingColumns, $wasNot? 'NOT IN (' : 'IN (', 1);
							$firstIn = false;
						} else {
							\ze\row::writeCol($tableName, $sql, $col, $val2, $first, $isWhere, $ignoreMissingColumns, ', ', 2);
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
					\ze\row::writeCol($tableName, $sql, $col, $val2, $first, $isWhere, $ignoreMissingColumns, $sign2, 0, $isNot);
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
				$cSql = '`#'. \ze\escape::sql($col). '` ';
			} else {
				$cSql = '`'. \ze\escape::sql($col). '` ';
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
			$sql .= $cSql. $sign. '\''. \ze\escape::sql(\ze\db::hashDBColumn($val)). '\'';
	
		} elseif ($colDef->isFloat) {
			$sql .= $cSql. $sign. ' '. (float) $val;
	
		} elseif ($colDef->isInt) {
			$sql .= $cSql. $sign. ' '. (int) $val;
	
		} elseif ($colDef->isSet && $in) {
			$sql .= $sign. 'FIND_IN_SET(\''. \ze\escape::sql((string) $val). '\', '. $cSql. ')';
	
		} else {
			$sql .= $cSql. $sign. ' \''. \ze\escape::sql((string) $val). '\'';
		}
	}


	const existsFromTwig = true;
	//Declare a function to check if something exists in the database
	//Formerly "checkRowExists()"
	public static function exists($table, $ids, $ignoreMissingColumns = false) {
		return self::selectInternal($table, $ids, $ignoreMissingColumns);
	}
	
	private static function selectInternal(
		$table, $ids,
		$ignoreMissingColumns = false, $cols = false, $multiple = false, $mode = false, $orderBy = array(),
		$distinct = false, $returnArrayIndexedBy = false, $addId = false
	) {
		$tableName = \ze::$lastDBPrefix. $table;
	
		if (!isset(\ze::$dbCols[$tableName])) {
			\ze\row::cacheTableDef($tableName);
		}
	
		if (\ze::$pkCols[$tableName] === '') {
			\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The table `[[table]]` does not exist.', array('table' => $tableName)));
		}
	
		if ($cols === true) {
			$cols = array_keys(\ze::$dbCols[$tableName]);
		}
	
	
		if ($returnArrayIndexedBy !== false) {
			$out = array();
		
			if ($result = self::selectInternal($table, $ids, $ignoreMissingColumns, $cols, true, false, $orderBy, $distinct, false, !$distinct)) {
				while ($row = \ze\sql::fetchAssoc($result)) {
				
					$id = false;
					if (is_string($returnArrayIndexedBy) && isset($row[$returnArrayIndexedBy])) {
						$id = $row[$returnArrayIndexedBy];
				
					} elseif (isset($row['[[ id column ]]'])) {
						$id = $row['[[ id column ]]'];
				
					} elseif (($idCol = \ze::$pkCols[$tableName]) && (isset($row[$idCol]))) {
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
			if (\ze::$pkCols[$tableName]) {
				$ids = array(\ze::$pkCols[$tableName] => $ids);
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
		
			$dbCols = &\ze::$dbCols[$tableName];
		
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
							\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The column `[[col]]` does not exist in the table `[[table]]`.', array('col' => $col, 'table' => $tableName)));
				
						} elseif ($dbCols[$col]->encrypted) {
							if ($pre !== '') {
								\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The column `[[col]]` in the table `[[table]]` is encrypted. You cannot use MIN(), MAX() or other group-statements on it.', array('col' => $col, 'table' => $tableName)));
							}
					
							$sql .= '`%'. \ze\escape::sql($col). '` AS `'. \ze\escape::sql($col). '`';
				
						} else {
							$sql .= $pre. '`'. \ze\escape::sql($col). '`'. $suf;
			
							if ($pre !== '' || $suf !== '') {
								$sql .= ' AS `'. \ze\escape::sql($col). '`';
						
							} elseif ($addId && $col == \ze::$pkCols[$tableName]) {
								$addId = false;
							}
						}
					}
				} else {
					if (!isset($dbCols[$cols])) {
						\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The column `[[col]]` does not exist in the table `[[table]]`.', array('col' => $cols, 'table' => $tableName)));
			
					} elseif ($dbCols[$cols]->encrypted) {
						if ($pre !== '') {
							\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The column `[[col]]` in the table `[[table]]` is encrypted. You cannot use MIN(), MAX() or other group-statements on it.', array('col' => $cols, 'table' => $tableName)));
						}
				
						$sql .= '`%'. \ze\escape::sql($cols). '` AS `'. \ze\escape::sql($cols). '`';
			
					} else {
						$sql .= $pre. '`'. \ze\escape::sql($cols). '`'. $suf;
		
						if ($pre !== '' || $suf !== '') {
							$sql .= ' AS `'. \ze\escape::sql($cols). '`';
					
						} elseif ($addId && $cols == \ze::$pkCols[$tableName]) {
							$addId = false;
						}
					}
				}
	
				if ($addId && \ze::$pkCols[$tableName]) {
					$sql .= ', `'. \ze\escape::sql(\ze::$pkCols[$tableName]). '` as `[[ id column ]]`';
				}
			}
		} while(false);
	
	
	
		$sql .= '
				FROM `'. \ze\escape::sql($tableName). '`';
	
		$first = true;
		foreach($ids as $col => &$val) {
			\ze\row::writeCol($tableName, $sql, $col, $val, $first, true, $ignoreMissingColumns);
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
					ORDER BY `'. \ze\escape::sql($col). '`';
			
				} else {
					$sql .= ',
						`'. \ze\escape::sql($col). '`';
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
			$affectedRows = \ze\db::reviewQueryForChanges($sql, $ids, $values, $table, true);
			return $affectedRows;
	
		} else {
			$result = \ze\sql::select($sql, false, $tableName);
		
			if ($multiple) {
				return $result;
		
			} elseif (!$row = \ze\sql::fetchAssoc($result)) {
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

	//Formerly "setRow()"
	public static function set($table, $values, $ids, $ignore = false, $ignoreMissingColumns = false, $markNewThingsInSession = false) {
		return self::setInternal($table, $values, $ids, $ignore, $ignoreMissingColumns, $markNewThingsInSession);
	}
	
	private static function setInternal(
		$table, $values, $ids = array(),
		$ignore = false, $ignoreMissingColumns = false,
		$markNewThingsInSession = false, $insertIfNotPresent = true, $checkCache = true
	) {
		$sqlW = '';
		$tableName = \ze::$lastDBPrefix. $table;
	
		if (!isset(\ze::$dbCols[$tableName])) {
			\ze\row::cacheTableDef($tableName);
		}
	
		if (\ze::$pkCols[$tableName] === '') {
			\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The table `[[table]]` does not exist.', array('table' => $tableName)));
		}
	
	
		if (!is_array($ids)) {
		
			if (\ze::$pkCols[$tableName]) {
				$ids = array(\ze::$pkCols[$tableName] => $ids);
			} else {
				$ids = array('id' => $ids);
			}
		}
	
		if (!$insertIfNotPresent || (!empty($ids) && self::selectInternal($table, $ids))) {
			$affectedRows = 0;
			
			$updatesNeeded = !empty($values);
			$returnPK = $insertIfNotPresent && \ze::$pkCols[$tableName];
			
			if ($updatesNeeded || $returnPK) {
				$first = true;
				foreach($ids as $col => &$val) {
					\ze\row::writeCol($tableName, $sqlW, $col, $val, $first, true, $ignoreMissingColumns);
				}
			}
			
			if ($updatesNeeded) {
				$sql = '
					UPDATE '. ($ignore? 'IGNORE ' : ''). '`'. \ze\escape::sql($tableName). '` SET ';
			
				$first = true;
				foreach ($values as $col => &$val) {
					\ze\row::writeCol($tableName, $sql, $col, $val, $first, false, $ignoreMissingColumns);
				}
			
				\ze\sql::update($sql. $sqlW, false, false);
				if (($affectedRows = \ze\sql::affectedRows()) > 0
				 && $checkCache) {
				
					if (empty($ids)) {
						$dummy = false;
						\ze\db::reviewQueryForChanges($sql, $values, $dummy, $table);
					} else {
						\ze\db::reviewQueryForChanges($sql, $ids, $values, $table);
					}
				}
			}
		
			if ($returnPK) {
				if (($sql = 'SELECT `'. \ze\escape::sql(\ze::$pkCols[$tableName]). '` FROM `'. \ze\escape::sql($tableName). '` '. $sqlW)
				 && ($result = \ze\sql::select($sql))
				 && ($row = \ze\sql::fetchRow($result))
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
				INSERT '. ($ignore? 'IGNORE ' : ''). 'INTO `'. \ze\escape::sql($tableName). '` SET ';
		
			$first = true;
			$hadColumns = array();
			foreach ($values as $col => &$val) {
				\ze\row::writeCol($tableName, $sql, $col, $val, $first, false, $ignoreMissingColumns);
				$hadColumns[$col] = true;
			}
		
			foreach ($ids as $col => &$val) {
				if (!isset($hadColumns[$col])) {
					\ze\row::writeCol($tableName, $sql, $col, $val, $first, false, $ignoreMissingColumns);
				}
			}
		
			\ze\sql::update($sql, false, false);
			$id = \ze\sql::insertId();
		
			if ($markNewThingsInSession) {
				$_SESSION['new_id_in_'. $table] = $id;
			}
		
			if ($checkCache
			 && \ze\sql::affectedRows() > 0) {
				if (empty($ids)) {
					$dummy = false;
					\ze\db::reviewQueryForChanges($sql, $values, $dummy, $table);
				} else {
					\ze\db::reviewQueryForChanges($sql, $ids, $values, $table);
				}
			}
		
			return $id;
	
		} else {
			return false;
		}
	}




	//Formerly "insertRow()"
	public static function insert($table, $values, $ignore = false, $ignoreMissingColumns = false, $markNewThingsInSession = false) {
		return self::setInternal($table, $values, array(), $ignore, $ignoreMissingColumns, $markNewThingsInSession, true);
	}


	//Formerly "updateRow()"
	public static function update($table, $values, $ids, $ignore = false, $ignoreMissingColumns = false) {
		return self::setInternal($table, $values, $ids, $ignore, $ignoreMissingColumns, false, false);
	}



	//Formerly "deleteRow()"
	public static function delete($table, $ids, $multiple = true) {
		return self::selectInternal($table, $ids, false, false, $multiple, 'delete');
	}

	const getFromTwig = true;
	//Formerly "getRow()"
	public static function get($table, $cols, $ids, $ignoreMissingColumns = false) {
		return self::selectInternal($table, $ids, $ignoreMissingColumns, $cols);
	}

	const queryFromTwig = true;
	//Formerly "getRows()"
	public static function query($table, $cols, $ids, $orderBy = array(), $ignoreMissingColumns = false) {
		return self::selectInternal($table, $ids, $ignoreMissingColumns, $cols, true, false, $orderBy);
	}

	const distinctQueryFromTwig = true;
	//Formerly "getDistinctRows()"
	public static function distinctQuery($table, $cols, $ids, $orderBy = array(), $ignoreMissingColumns = false) {
		return self::selectInternal($table, $ids, $ignoreMissingColumns, $cols, true, false, $orderBy, true);
	}

	const getArrayFromTwig = true;
	//Formerly "getRowsArray()"
	public static function getArray($table, $cols, $ids = array(), $orderBy = array(), $indexBy = false, $ignoreMissingColumns = false) {
		return self::selectInternal($table, $ids, $ignoreMissingColumns, $cols, true, false, $orderBy, false, $indexBy? $indexBy : true);
	}

	const getDistinctArrayFromTwig = true;
	//Formerly "getDistinctRowsArray()"
	public static function getDistinctArray($table, $cols, $ids = array(), $orderBy = array(), $indexBy = false, $ignoreMissingColumns = false) {
		return self::selectInternal($table, $ids, $ignoreMissingColumns, $cols, true, false, $orderBy, true, $indexBy? $indexBy : true);
	}

	const countFromTwig = true;
	//Formerly "selectCount()"
	public static function count($table, $ids = array()) {
		return (int) self::selectInternal($table, $ids, false, false, false, 'count');
	}

	const maxFromTwig = true;
	//Formerly "selectMax()"
	public static function max($table, $cols, $ids = array(), $ignoreMissingColumns = false) {
		return self::selectInternal($table, $ids, $ignoreMissingColumns, $cols, false, 'max');
	}

	const minFromTwig = true;
	//Formerly "selectMin()"
	public static function min($table, $cols, $ids = array(), $ignoreMissingColumns = false) {
		return self::selectInternal($table, $ids, $ignoreMissingColumns, $cols, false, 'min');
	}


	//Look up the name of the primary/foreign key column
	//Formerly "getIdColumnOfTable()"
	public static function idColumnOfTable($table, $guess = false) {
		\ze\row::cacheTableDef(DB_NAME_PREFIX. $table);		
		if (\ze::$pkCols[DB_NAME_PREFIX. $table]) {
			return \ze::$pkCols[DB_NAME_PREFIX. $table];
	
		} elseif ($guess) {
			return 'id';
	
		} else {
			return false;
		}
	}

}