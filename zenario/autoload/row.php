<?php
/*
 * Copyright (c) 2022, Tribal Limited
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


	public static function whereCol($tableName, $alias, $col, $sign, $val, $first = false) {
		$sql = '';
		static::writeCol($sql, DB_PREFIX. $tableName, $alias === ''? '' : $alias. '.', $col, $val, $first, true, false, false, $sign);
		return $sql;
	}
	public static function setCol($tableName, $alias, $col, $sign, $val, $first = false) {
		$sql = '';
		static::writeCol($sql, DB_PREFIX. $tableName, $alias === ''? '' : $alias. '.', $col, $val, $first, false, false, false, $sign);
		return $sql;
	}



	//Helper function for selectInternal
	//Formerly "checkRowExistsCol()"
	public static function writeCol(&$sql, $tableName, $alias, $col, $val, &$first, $isWhere, $ignoreMissingColumns = false, $path = false, $sign = '=', $in = 0, $wasNot = false) {
		
		if (!isset(static::$db->cols[$tableName][$col])) {
			static::$db->checkTableDef($tableName);
		}
	
		if (!isset(static::$db->cols[$tableName][$col])) {
			if ($ignoreMissingColumns && !$isWhere) {
				return;
			
			} else {
				//If not, check if that's because we're looking up a path in a JSON column.
				//We'll use the format of the name of the column, then either a "." or a "[" to mark the start of the JSON selector,
				//e.g. "data.pressure" or "data[0]"
				$pos = strpos($col, '.');
				$posB = strpos($col, '[');
			
				if ($posB !== false) {
					if ($pos !== false) {
						$pos = min($posB, $pos);
					} else {
						$pos = $posB;
					}
				}
			
				if ($pos
				 && ($doc = substr($col, 0, $pos))
				 && ($path = substr($col, $pos))
				 && (isset(static::$db->cols[$tableName][$doc]))
				 && (static::$db->cols[$tableName][$doc]->isJSON)) {
					$col = $doc;
					//$path = $path;
				
				} else {
					\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The column `[[col]]` does not exist in the table `[[table]]`.', ['col' => $col, 'table' => $tableName]));
				}
			}
		}
	
		$d = &static::$db->cols[$tableName][$col];
	
	
		if ($d->encrypted) {
			if ($isWhere) {
				if (!$d->hashed) {
					\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The column `[[col]]` in the table `[[table]]` is encrypted and cannot be used in a WHERE-statement.', ['col' => $col, 'table' => $tableName]));
				}
			} else {
				$sql .= ($first? '' : ','). $alias. '`%'. \ze\escape::sql($col). '` = \''. \ze\escape::sql((string) \ze\zewl::encrypt($val, true)). '\'';
			
				if ($d->hashed) {
					$sql .= ', '. $alias. '`#'. \ze\escape::sql($col). '` = \''. \ze\escape::sql(\ze\db::hashDBColumn($val)). '\'';
				}
			
				$first = false;
				return;
			}
		}
	
	
		if ($isWhere && is_array($val)) {
			
			//Catch the case where someone tries to do an IN() on an empty list
			if ($val === []) {
				if (substr($sign, 0, 1) == '!' || $sign == 'NOT LIKE') {
					//"Not in empty list" is always true.
					//(Though don't bother writing "AND TRUE" as that's redundant.)
				} else {
					//"In empty list" is always false.
					if ($first) {
						$first = false;
						$sql .= '
							WHERE FALSE';
					} else {
						$sql .= '
							  AND FALSE ';
					}
				}
				
				return;
			}
			
			$firstIn = true;
			foreach ($val as $sign2 => &$val2) {
				if (is_numeric($sign2) || substr($sign2, 0, 1) == '=') {
					if ($d->isSet) {
						if ($firstIn) {
							static::writeCol($sql, $tableName, $alias, $col, $val2, $first, $isWhere, $ignoreMissingColumns, $path, $wasNot? 'NOT (' : '(', 1);
							$firstIn = false;
						} else {
							static::writeCol($sql, $tableName, $alias, $col, $val2, $first, $isWhere, $ignoreMissingColumns, $path, ' OR ', 2);
						}
					} else {
						if ($firstIn) {
							static::writeCol($sql, $tableName, $alias, $col, $val2, $first, $isWhere, $ignoreMissingColumns, $path, $wasNot? 'NOT IN (' : 'IN (', 1);
							$firstIn = false;
						} else {
							static::writeCol($sql, $tableName, $alias, $col, $val2, $first, $isWhere, $ignoreMissingColumns, $path, ', ', 2);
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
					static::writeCol($sql, $tableName, $alias, $col, $val2, $first, $isWhere, $ignoreMissingColumns, $path, $sign2, 0, $isNot);
				}
			}
		
			return;
		}
		
		//Catch the case where the caller is trying to update part of a JSON doc.
		//We can't add this here, as there might be multiple paths to update, and if so they all need to be grouped together.
		//So instead we'll note down some info and then return that to the calling function.
		if ($path !== false && !$isWhere) {
			$aor = is_null($val)? '-' : '+';
			return [$col => [$aor => ['$'. $path => $val]]];
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
		
			if ($d->hashed) {
				$cSql = $alias. '`#'. \ze\escape::sql($col). '` ';
			
			} elseif ($path !== false) {
				$cSql = $alias. '`'. \ze\escape::sql($col). '`->"$'. \ze\escape::sql($path). '" ';
			
			} else {
				$cSql = $alias. '`'. \ze\escape::sql($col). '` ';
			}
		}
	
		if ($val === null || (!$val && $d->isTime)) {
			if ($in) {
				$sql .= $cSql. $sign. 'NULL';
		
			} elseif (!$isWhere) {
				$sql .= $cSql. '= NULL';
		
			} elseif ($sign == '=') {
				$sql .= $cSql. 'IS NULL';
		
			} else {
				$sql .= $cSql. 'IS NOT NULL';
			}
	
		} elseif ($d->hashed) {
			$sql .= $cSql. $sign. '\''. \ze\escape::sql(\ze\db::hashDBColumn($val)). '\'';
	
		} elseif ($d->isInt) {
			$sql .= $cSql. $sign. ' '. (int) $val;
	
		} elseif ($d->isFloat) {
			$sql .= $cSql. $sign. ' '. (float) $val;
	
		} elseif ($d->isJSON) {
			if ($path !== false) {
				$sql .= $cSql. $sign. \ze\escape::stringToIntOrFloat($val, true, true);
			} else {
				$sql .= $cSql. $sign. \ze\escape::json($val);
			}
	
		} elseif ($d->isSet && $in) {
			$sql .= $sign. 'FIND_IN_SET(\''. \ze\escape::sql((string) $val). '\', '. $cSql. ')';
	
		} elseif ($d->isASCII) {
			$sql .= $cSql. $sign. ' \''. \ze\escape::asciiInSQL((string) $val). '\'';
	
		} else {
			$sql .= $cSql. $sign. ' \''. \ze\escape::sql((string) $val). '\'';
		}
		
		return;
	}


	//Declare a function to check if something exists in the database
	//Formerly "checkRowExists()"
	public static function exists($table, $ids, $ignoreMissingColumns = false) {
		return static::selectInternal($table, $ids, $ignoreMissingColumns);
	}
	
	private static function selectInternal(
		$table, $ids,
		$ignoreMissingColumns = false, $cols = false, $multiple = false, $mode = false, $orderBy = [],
		$distinct = false, $returnArrayIndexedBy = false, $addId = false, $storeResult = true
	) {
		
		$tableName = static::$db->prefix. $table;
	
		if (!isset(static::$db->cols[$tableName])) {
			static::$db->checkTableDef($tableName);
		}
		$dbCols = &static::$db->cols[$tableName];
		$pkCol = &static::$db->pks[$tableName];
	
		if ($pkCol === '') {
			\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The table `[[table]]` does not exist.', ['table' => $tableName]));
		}
	
		if ($cols === true) {
			$cols = array_keys($dbCols);
		}
	
	
		if ($returnArrayIndexedBy !== false) {
			$out = [];
		
			if ($result = static::selectInternal($table, $ids, $ignoreMissingColumns, $cols, $multiple, false, $orderBy, $distinct, false, !$distinct)) {
				while ($row = $result->fAssoc()) {
				
					$id = false;
					if (is_string($returnArrayIndexedBy) && isset($row[$returnArrayIndexedBy])) {
						$id = $row[$returnArrayIndexedBy];
				
					} elseif (isset($row['[[ id ]]'])) {
						$id = $row['[[ id ]]'];
				
					} elseif (($idCol = $pkCol) && (isset($row[$idCol]))) {
						$id = $row[$idCol];
					}
					unset($row['[[ id ]]']);
				
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
			if ($pkCol) {
				$ids = [$pkCol => $ids];
			} else {
				$ids = ['id' => $ids];
			}
		}
		$colDefs = [];
	
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
			
				case 'sum':
					$pre = 'SUM(';
					$suf = ')';
					break;
			
				case 'avg':
					$pre = 'AVG(';
					$suf = ')';
					break;
			
				default:
					$pre = '';
					$suf = '';
			}
			
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
						
						static::selCol($colDefs, $addId, $sql, $pre, $suf, $tableName, $pkCol, $col, $dbCols, $ignoreMissingColumns);
					}
				} else {
					static::selCol($colDefs, $addId, $sql, $pre, $suf, $tableName, $pkCol, $cols, $dbCols, $ignoreMissingColumns = false);
				}
	
				if ($addId && $pkCol) {
					$sql .= ', `'. \ze\escape::sql($pkCol). '` as `[[ id ]]`';
					$colDefs[] = $dbCols[$pkCol];
				}
			}
		} while(false);
	
	
	
		$sql .= '
				FROM `'. \ze\escape::sql($tableName). '`';
	
		$first = true;
		foreach($ids as $col => &$val) {
			static::writeCol($sql, $tableName, '', $col, $val, $first, true, $ignoreMissingColumns);
		}
		
	
		if (!empty($orderBy)) {
			if (!is_array($orderBy)) {
				$orderBy = [$orderBy];
			}
			$first = true;
			foreach ($orderBy as $col) {
			
				if ($col == 'DESC'
				 || $col == 'ASC'
				 || $col == 'Desc'
				 || $col == 'Asc') {
					$sql .= ' '. $col;
			
				} else {
					if ($first) {
						$sql .= '
						ORDER BY ';
			
					} else {
						$sql .= ',
							';
					}
					
					if (strpbrk($col, '.[') === false) {
						$sql .= '`'. \ze\escape::sql($col). '`';
					
					} else {
						static::selCol($colDefs, $addId, $sql, '', '', $tableName, $pkCol, $col, $dbCols, false, true);
					}
				}
				$first = false;
			}
		}
	
		if (!$multiple) {
			$sql .= '
				LIMIT 1';
		
		} elseif (!is_bool($multiple)) {
			$sql .= '
				LIMIT '. (int) $multiple;
		}
	
	
		if ($mode == 'delete') {
			$values = false;
			if ($affectedRows = static::$db->reviewQueryForChanges($sql, $ids, $values, $table, true)) {
				\ze\db::updateDataRevisionNumber();
			}
			return $affectedRows;
	
		} else {
			$result = static::doSelect($sql, $storeResult, $colDefs);
		
			if ($multiple) {
				return $result;
		
			} elseif (!$row = $result->fAssoc()) {
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
	
	



	protected static function selCol(&$colDefs, &$addId, &$sql, $pre, $suf, $tableName, $pkCol, $col, $dbCols, $ignoreMissingColumns, $inOrderBy = false) {
		
		if ($inOrderBy) {
			$as = '';
		} else {
			$as = ' AS `'. \ze\escape::sql($col). '`';
		}
		
		//Check that the column we're looking for exists
		if (!isset($dbCols[$col])) {
			
			//If not, check if that's because we're looking up a path in a JSON column.
			//We'll use the format of the name of the column, then either a "." or a "[" to mark the start of the JSON selector,
			//e.g. "data.pressure" or "data[0]"
			$pos = strpos($col, '.');
			$posB = strpos($col, '[');
			
			if ($posB !== false) {
				if ($pos !== false) {
					$pos = min($posB, $pos);
				} else {
					$pos = $posB;
				}
			}
			
			if ($pos
			 && ($doc = substr($col, 0, $pos))
			 && ($path = substr($col, $pos))
			 && (isset($dbCols[$doc]))
			 && ($dbCols[$doc]->isJSON)) {
				
				$sql .= $pre. '`'. \ze\escape::sql($doc). '`->"$'. \ze\escape::sql($path). '"'. $suf. $as;
				
				if (!$inOrderBy) {
					$colDefs[] = $dbCols[$doc];
				}
			
			} elseif ($ignoreMissingColumns && $pre === '' && $suf === '') {
				$sql .= ' NULL'. $as;
			
			} else {
				\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The column `[[col]]` does not exist in the table `[[table]]`.', ['col' => $col, 'table' => $tableName]));
			}

		} elseif ($dbCols[$col]->encrypted) {
			if ($inOrderBy) {
				\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The column `[[col]]` in the table `[[table]]` is encrypted. You cannot use ORDER BY on it.', ['col' => $col, 'table' => $tableName]));
			}
			if ($pre !== '') {
				\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The column `[[col]]` in the table `[[table]]` is encrypted. You cannot use MIN(), MAX() or other group-statements on it.', ['col' => $col, 'table' => $tableName]));
			}

			$sql .= '`%'. \ze\escape::sql($col). '`'. $as;
			$colDefs[] = $dbCols[$col];

		} else {
			$sql .= $pre. '`'. \ze\escape::sql($col). '`'. $suf;

			if ($pre !== '' || $suf !== '') {
				$sql .= $as;

			} elseif ($addId && $col == $pkCol) {
				$addId = false;
			}
			
			if (!$inOrderBy) {
				$colDefs[] = $dbCols[$col];
			}
		}
	}
	
	

	//Formerly "setRow()"
	public static function set($table, $values, $ids, $ignore = false, $ignoreMissingColumns = false, $markNewThingsInSession = false) {
		return static::setInternal($table, $values, $ids, $ignore, $ignoreMissingColumns, $markNewThingsInSession);
	}
	
	public static function setAndMarkNew($table, $values, $ids, $ignore = false, $ignoreMissingColumns = false) {
		return static::setInternal($table, $values, $ids, $ignore, $ignoreMissingColumns, true);
	}
	
	private static function setInternal(
		$table, $values, $ids = [],
		$ignore = false, $ignoreMissingColumns = false,
		$markNewThingsInSession = false, $insertIfNotPresent = true, $checkCache = true
	) {
		$sqlW = '';
		$tableName = static::$db->prefix. $table;
	
		if (!isset(static::$db->cols[$tableName])) {
			static::$db->checkTableDef($tableName);
		}
	
		if (static::$db->pks[$tableName] === '') {
			\ze\db::reportDatabaseErrorFromHelperFunction(\ze\admin::phrase('The table `[[table]]` does not exist.', ['table' => $tableName]));
		}
	
	
		if (!is_array($ids)) {
		
			if (static::$db->pks[$tableName]) {
				$ids = [static::$db->pks[$tableName] => $ids];
			} else {
				$ids = ['id' => $ids];
			}
		}
	
		if (!$insertIfNotPresent || (!empty($ids) && static::selectInternal($table, $ids))) {
			$affectedRows = 0;
			
			$updatesNeeded = !empty($values);
			$returnPK = $insertIfNotPresent && static::$db->pks[$tableName];
			
			if ($updatesNeeded || $returnPK) {
				$first = true;
				foreach($ids as $col => &$val) {
					static::writeCol($sqlW, $tableName, '', $col, $val, $first, true, $ignoreMissingColumns);
				}
			}
			
			if ($updatesNeeded) {
				$sql = '
					UPDATE '. ($ignore? 'IGNORE ' : ''). '`'. \ze\escape::sql($tableName). '` SET ';
				
				$first = true;
				$jsonUpdates = [];
				foreach ($values as $col => &$val) {
					$thisUpdates = static::writeCol($sql, $tableName, '', $col, $val, $first, false, $ignoreMissingColumns);
					
					if (!is_null($thisUpdates)) {
						$jsonUpdates = array_merge_recursive($jsonUpdates, $thisUpdates);
					}
				}
				
				//If there are individual updates to JSON documents, these need to be handled separately
				if ($jsonUpdates !== []) {
					foreach ($jsonUpdates as $col => $jus) {
						if ($first) {
							$first = false;
						} else {
							$sql .= ', ';
						}
						
						$sql .= '`'. \ze\escape::sql($col). '` = ';
						
						//For unsets, we need to use JSON_REMOVE(), e.g. JSON_REMOVE(data, '$.path', ...)
						//For other updates we need to use JSON_SET(), e.g. JSON_SET(data, '$.path', 789, ...)
						//These functions can both take multiple inputs if there are multiple paths to remove/update.
						if (isset($jus['-'])) {
							if (isset($jus['+'])) {
								$sql .= 'JSON_SET(JSON_REMOVE(`'. \ze\escape::sql($col). '`';
							} else {
								$sql .= 'JSON_REMOVE(`'. \ze\escape::sql($col). '`';
							}
							
							foreach ($jus['-'] as $path => $val) {
								$sql .= ', \''. \ze\escape::sql($path). '\'';
							}
							$sql .= ')';
						}
						
						if (isset($jus['+'])) {
							if (!isset($jus['-'])) {
								$sql .= 'JSON_SET(`'. \ze\escape::sql($col). '`';
							}
							
							foreach ($jus['+'] as $path => $val) {
								$sql .= ', \''. \ze\escape::sql($path). '\', ';
								
								if (is_array($val)) {
									$sql .= \ze\escape::json($val);
								} else {
									$sql .= \ze\escape::stringToIntOrFloat($val, true, true);
								}
							}
							$sql .= ')';
						}
					}
				}
			
				static::doUpdate($sql. $sqlW);
				if (($affectedRows = static::$db->con->affected_rows) > 0
				 && $checkCache) {
				
					if (empty($ids)) {
						$dummy = false;
						static::$db->reviewQueryForChanges($sql, $values, $dummy, $table);
					} else {
						static::$db->reviewQueryForChanges($sql, $ids, $values, $table);
					}
				}
			}
		
			if ($returnPK) {
				$result = static::doSelect('SELECT `'. \ze\escape::sql(static::$db->pks[$tableName]). '` FROM `'. \ze\escape::sql($tableName). '` '. $sqlW);
				$row = $result->fRow();
				return $row[0] ?? false;
			} else {
				return $affectedRows;
			}
	
		} elseif ($insertIfNotPresent) {
			$sql = '
				INSERT '. ($ignore? 'IGNORE ' : ''). 'INTO `'. \ze\escape::sql($tableName). '` SET ';
		
			$first = true;
			$hadColumns = [];
			foreach ($values as $col => &$val) {
				static::writeCol($sql, $tableName, '', $col, $val, $first, false, $ignoreMissingColumns);
				$hadColumns[$col] = true;
			}
		
			foreach ($ids as $col => &$val) {
				if (!isset($hadColumns[$col])) {
					static::writeCol($sql, $tableName, '', $col, $val, $first, false, $ignoreMissingColumns);
				}
			}
		
			static::doUpdate($sql);
			$id = static::$db->con->insert_id;
		
			if ($markNewThingsInSession) {
				$_SESSION['new_id_in_'. $table] = $id;
			}
		
			if ($checkCache
			 && static::$db->con->affected_rows > 0) {
				if (empty($ids)) {
					$dummy = false;
					static::$db->reviewQueryForChanges($sql, $values, $dummy, $table);
				} else {
					static::$db->reviewQueryForChanges($sql, $ids, $values, $table);
				}
			}
		
			return $id;
	
		} else {
			return false;
		}
	}




	//Formerly "insertRow()"
	public static function insert($table, $values, $ignore = false, $ignoreMissingColumns = false, $markNewThingsInSession = false) {
		return static::setInternal($table, $values, [], $ignore, $ignoreMissingColumns, $markNewThingsInSession, true);
	}
	
	public static function insertAndMarkNew($table, $values, $ignore = false, $ignoreMissingColumns = false) {
		return static::setInternal($table, $values, [], $ignore, $ignoreMissingColumns, true, true);
	}


	//Formerly "updateRow()"
	public static function update($table, $values, $ids, $ignore = false, $ignoreMissingColumns = false) {
		return static::setInternal($table, $values, $ids, $ignore, $ignoreMissingColumns, false, false);
	}



	//Formerly "deleteRow()"
	public static function delete($table, $ids, $multiple = true) {
		return static::selectInternal($table, $ids, false, false, $multiple, 'delete');
	}

	//Formerly "getRow()"
	public static function get($table, $cols, $ids, $orderBy = [], $ignoreMissingColumns = false) {
		return static::selectInternal($table, $ids, $ignoreMissingColumns, $cols, false, false, $orderBy);
	}

	//Formerly "getRows()"
	public static function query($table, $cols, $ids, $orderBy = [], $indexBy = false, $ignoreMissingColumns = false, $limit = false, $storeResult = true) {
		return static::selectInternal($table, $ids, $ignoreMissingColumns, $cols, $limit ?: true, false, $orderBy, false, $indexBy, false, $storeResult);
	}

	//Formerly "getDistinctRows()"
	public static function distinctQuery($table, $cols, $ids, $orderBy = [], $indexBy = false, $ignoreMissingColumns = false, $limit = false, $storeResult = true) {
		return static::selectInternal($table, $ids, $ignoreMissingColumns, $cols, $limit ?: true, false, $orderBy, true, $indexBy, false, $storeResult);
	}

	//Formerly "getRowsArray()"
	public static function getAssocs($table, $cols, $ids = [], $orderBy = [], $indexBy = false, $ignoreMissingColumns = false, $limit = false) {
		return static::selectInternal($table, $ids, $ignoreMissingColumns, $cols, $limit ?: true, false, $orderBy, false, $indexBy? $indexBy : true);
	}
	
	//Alternate name for getAssocs() - included for consistency with the ze\sql library
	public static function getValues($table, $cols, $ids = [], $orderBy = [], $indexBy = false, $ignoreMissingColumns = false, $limit = false) {
		return static::selectInternal($table, $ids, $ignoreMissingColumns, $cols, $limit ?: true, false, $orderBy, false, $indexBy? $indexBy : true);
	}
	
	public static function getDistinctValues($table, $cols, $ids = [], $orderBy = [], $indexBy = false, $ignoreMissingColumns = false, $limit = false) {
		return static::selectInternal($table, $ids, $ignoreMissingColumns, $cols, $limit ?: true, false, $orderBy, true, $indexBy? $indexBy : true);
	}
	
	//Deprecated old name for getAssocs()
	public static function getArray($table, $cols, $ids = [], $orderBy = [], $indexBy = false, $ignoreMissingColumns = false, $limit = false) {
		return static::selectInternal($table, $ids, $ignoreMissingColumns, $cols, $limit ?: true, false, $orderBy, false, $indexBy? $indexBy : true);
	}

	//Formerly "getDistinctRowsArray()"
	public static function getDistinctAssocs($table, $cols, $ids = [], $orderBy = [], $indexBy = false, $ignoreMissingColumns = false) {
		return static::selectInternal($table, $ids, $ignoreMissingColumns, $cols, true, false, $orderBy, true, $indexBy? $indexBy : true);
	}

	//Formerly "selectCount()"
	public static function count($table, $ids = []) {
		return (int) static::selectInternal($table, $ids, false, false, false, 'count');
	}

	//Formerly "selectMax()"
	public static function max($table, $cols, $ids = [], $ignoreMissingColumns = false) {
		return static::selectInternal($table, $ids, $ignoreMissingColumns, $cols, false, 'max');
	}

	//Formerly "selectMin()"
	public static function min($table, $cols, $ids = [], $ignoreMissingColumns = false) {
		return static::selectInternal($table, $ids, $ignoreMissingColumns, $cols, false, 'min');
	}

	public static function sum($table, $cols, $ids = [], $ignoreMissingColumns = false) {
		return static::selectInternal($table, $ids, $ignoreMissingColumns, $cols, false, 'sum');
	}

	public static function avg($table, $cols, $ids = [], $ignoreMissingColumns = false) {
		return static::selectInternal($table, $ids, $ignoreMissingColumns, $cols, false, 'avg');
	}


	//Look up the name of the primary/foreign key column
	//Formerly "getIdColumnOfTable()"
	public static function idColumnOfTable($table, $guess = false) {
		static::$db->checkTableDef(DB_PREFIX. $table);		
		if (static::$db->pks[DB_PREFIX. $table]) {
			return static::$db->pks[DB_PREFIX. $table];
	
		} elseif ($guess) {
			return 'id';
	
		} else {
			return false;
		}
	}
	
	
	
	
	protected static function doSelect($sql, $storeResult = true, $colDefs = []) {
	
		if ($result = static::$db->con->query($sql, $storeResult? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT)) {
			return new queryCursor(static::$db, $result, $colDefs);
	
		} else {
			\ze\db::handleError(static::$db->con, $sql);
		}
	}
	
	protected static function doUpdate($sql) {
	
		if ($result = static::$db->con->query($sql)) {
		
			if (static::$db->con->affected_rows) {
				\ze\db::updateDataRevisionNumber();
			}
			return $result;
	
		} else {
			\ze\db::handleError(static::$db->con, $sql);
		}
	}



	
	protected static $db;
	public static function init(&$db) {
		static::$db = &$db;
	}
}
\ze\row::init(\ze::$dbL);