<?php
/*
 * Copyright (c) 2017, Tribal Limited
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




	//Wrapper functions for the two different PHP MongoDB libraries
	//
	//Warning! The inputs to these functions are in the following order:
	//collection, columns, ids, sort
	//This is to be consistent with the rest of the Zenario database API functions,
	//but is not consistent with the normal order in MongoDB's functions

cms_core::$whitelist[] = 'mongoEscapeKey';
function mongoEscapeKey($key) {
	return str_replace('.', '~', $key);
}

cms_core::$whitelist[] = 'mongoUnescapeKey';
function mongoUnescapeKey($key) {
	return str_replace('~', '.', $key);
}

//Connect to MongoDB and return a pointer to a collection
function mongoCollection($collection, $returnFalseOnError = false) {
	
	//Connect to MongoDB if we haven't already
	if (!isset(cms_core::$mongoDB)) {
		
		//If the connection details were not defined, default to localhost/the default port/no username or password
		if (!defined('MONGODB_CONNECTION_URI')) {
			define('MONGODB_CONNECTION_URI', 'mongodb://localhost:27017');
		}
		
		if (!defined('MONGODB_DBNAME')) {
			if ($returnFalseOnError) {
				return false;
			} else {
				reportDatabaseErrorFromHelperFunction('The MONGODB_DBNAME constant was not defined in the zenario_siteconfig.php file.');
				exit;
			}
	
		} elseif (class_exists('MongoDB\Driver\Manager')) {
			//new logic for PHP 7
			$mongoClient = new MongoDB\Client(MONGODB_CONNECTION_URI);
			cms_core::$mongoDB = $mongoClient->{MONGODB_DBNAME};
		
		} else {
			if ($returnFalseOnError) {
				return false;
			} else {
				reportDatabaseErrorFromHelperFunction('The MongoDB PHP extension is not installed.');
				exit;
			}
		}
	}
	
	return cms_core::$mongoDB->{$collection};
}

function zenarioMongoParseInputs(&$collection, &$ids) {
	if (is_string($collection)) {
		$collection = mongoCollection($collection);
	}
	if (!is_array($ids) && !empty($ids)) {
		$ids = ['_id' => $ids];
	}
}

//Get a COUNT(*) of rows
cms_core::$whitelist[] = 'mongoCount';
function mongoCount($collection, $ids = array()) {
	zenarioMongoParseInputs($collection, $ids);
	return $collection->count($ids);
}

//Run a query on a collection
function mongoFind($collection, $cols = array(), $ids = array(), $sort = null, $limit = 0, $queryOptions = array()) {
	
	zenarioMongoParseInputs($collection, $ids);
	if (!empty($sort) && is_string($sort)) {
		if ($sort[0] == '-') {
			$sort = [substr($sort, 1) => -1];
		} else {
			$sort = [$sort => 1];
		}
	}
	
	if (is_array($cols) && $cols !== []) {
		$queryOptions['projection'] = $cols;
	}
	if (isset($limit)) {
		$queryOptions['limit'] = $limit;
	}
	if (isset($sort)) {
		$queryOptions['sort'] = $sort;
	}
	
	$cursor = $collection->find($ids, $queryOptions);
	$cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
	
	try {
		$IteratorIterator = new IteratorIterator($cursor);
		$IteratorIterator->rewind();
		return $IteratorIterator;
	
	} catch (Exception $e) {
		$obj = new ArrayObject([]);
		return $obj->getIterator();
	}
}

//Run a query on a collection, returning just one row or one property value
cms_core::$whitelist[] = 'mongoFindOne';
function mongoFindOne($collection, $cols = array(), $ids = array(), $sort = null, $queryOptions = array()) {
	
	$col = false;
	if (is_array($cols) || $cols === true) {
		$col = false;
	} else {
		$col = $cols;
		$cols = [$col => 1];
	}
	
	$row = mongoFetchRow(mongoFind($collection, $cols, $ids, $sort, 1, $queryOptions));
	
	if ($col === false) {
		return $row;
	} else {
		return ($row[$col] ?? false);
	}
}

function mongoUpdateOne($collection, $update, $ids, $queryOptions = array()) {
	zenarioMongoParseInputs($collection, $ids);
	$collection->updateOne($ids, $update, $queryOptions);
}
	
function mongoUpdateMany($collection, $update, $ids = array(), $queryOptions = array()) {
	zenarioMongoParseInputs($collection, $ids);
	$collection->updateMany($ids, $update, $queryOptions);
}

function mongoDeleteOne($collection, $ids, $queryOptions = array()) {
	zenarioMongoParseInputs($collection, $ids);
	$collection->deleteOne($ids, $queryOptions);
}
	
function mongoDeleteMany($collection, $ids = array(), $queryOptions = array()) {
	zenarioMongoParseInputs($collection, $ids);
	$collection->deleteMany($ids, $queryOptions);
}

//Fetch a row from a cursor returned by mongoFind()
function mongoFetchRow($cursor) {
	if ($row = $cursor->current()) {
		$cursor->next();
		return $row;
	}
	
	return false;
}


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
	return (int) checkRowExists($table, $ids, false, false, false, 'count');
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
	if ($row = sqlFetchAssoc("SHOW TABLE STATUS LIKE '". sqlEscape(cms_core::$lastDBPrefix. $table). "'")) {
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



function inEscape($csv, $escaping = -1, $prefix = false) {
	if (!is_array($csv)) {
		$csv = explode(',', $csv);
	}
	$sql = '';
	foreach ($csv as $var) {
		$var = trim($var);
		
		if ($sql !== '') {
			$sql .= ',';
		}
		
		if ($escaping === -1) {
			$sql .= stringToIntOrFloat($var, true);
		
		} elseif ($escaping === 'numeric' || $escaping === true) {
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

function likeEscape($text, $allowStarsAsWildcards = false) {
	
	if (!$allowStarsAsWildcards) {
		return str_replace('%', '\\%', str_replace('_', '\\_', sqlEscape($text)));
	
	} elseif ($text == '*') {
		return '_';
	
	} else {
		return str_replace('*', '%', str_replace('%', '\\%', str_replace('_', '\\_', sqlEscape($text))));
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
function sqlFetchArray($result, $mrg = false) {
	if (is_string($result)) {
		$result = sqlSelect($result, $mrg);
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
function sqlFetchAssoc($result, $mrg = false) {
	if (is_string($result)) {
		$result = sqlSelect($result, $mrg);
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
function sqlFetchRow($result, $mrg = false) {
	if (is_string($result)) {
		$result = sqlSelect($result, $mrg);
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
function sqlInsertId() {
	return cms_core::$lastDB->insert_id;
}

//Replacement for mysql_num_rows()
function sqlNumRows($result, $mrg = false) {
	if (is_string($result)) {
		$result = sqlSelect($result, $mrg);
	}
	return $result->q->num_rows;
}

//Fetch just one value from a SQL query
function sqlFetchValue($result, $mrg = false) {
	if ($row = sqlFetchRow($result, $mrg)) {
		return $row[0];
	} else {
		return false;
	}
}

//Fetch multiple values from a SQL query (one column, multiple rows)
function sqlFetchValues($result, $mrg = false, $numeric = false) {
	if (is_string($result)) {
		$result = sqlSelect($result, $mrg);
	}
	$out = array();
	while ($row = sqlFetchRow($result)) {
		if ($numeric) {
			$out[] = (int) $row[0];
		} else {
			$out[] = $row[0];
		}
	}
	return $out;
}

//Fetch multiple values from a SQL query (multiple columns, multiple rows)
function sqlFetchAssocs($result, $mrg = false, $indexBy = false) {
	if (is_string($result)) {
		$result = sqlSelect($result, $mrg);
	}
	$out = array();
	while ($row = sqlFetchAssoc($result)) {
		if ($indexBy === false) {
			$out[] = $row;
		} else {
			$out[$row[$indexBy]] = $row;
		}
	}
	return $out;
}
function sqlFetchRows($result, $mrg = false) {
	if (is_string($result)) {
		$result = sqlSelect($result, $mrg);
	}
	$out = array();
	while ($row = sqlFetchRow($result)) {
		$out[] = $row;
	}
	return $out;
}



function sqlAddMergeFields(&$sql, &$mrg, &$colDefs, $tables = array()) {
	
	$cacheQuery = strlen($sql) < 1024;
	
	//Check to see if we've got this SQL source cached 
	if ($cacheQuery && isset(cms_core::$pq[$sql])) {
		//If so, use the previous values
		$details = &cms_core::$pq[$sql];
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
			
				if (!isset(cms_core::$dbCols[$tableName])) {
					checkTableDefinition($tableName);
				}
			}
		}
		
		//Cache the last 5 queries, so we don't repeatedly perform the preg_split
		//above on the same query.
		if ($cacheQuery) {
			if (count(cms_core::$pq) >= 5) {
				array_splice(cms_core::$pq, 0, 1);
			}
		
			cms_core::$pq[$sql] = [$parts, $count, $tables];
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
			sqlApplyMergeField($sql, $mrg, $a);
		
		} elseif ($b === '.likeEscape' && isset($mrg[$a])) {
			$sql .= likeEscape($mrg[$a]);
		
		//Columns
		} else {
			$tableName = $tables[$a];
			$colName = $as = substr($b, 1);
			$colDef = &cms_core::$dbCols[$tableName][$colName];
			
			//Check the operation sign on this column
			switch ($c) {
				//Inserting/updating data into a column
				case '=':
					if ($colDef->encrypted) {
						if ($colDef->hashed) {
							//If a column is both encrypted and hashed, we'll need to insert two values
							$sql .= $a. '.`#'. $colName. '` '. $c. ' ';
							sqlApplyMergeField($sql, $mrg, $d, $colDef, true);
							$sql .= ', ';
						}
						
						$colName = '%'. $colName;
					}
		
					$sql .= $a. '.`'. $colName. '` '. $c. ' ';
					sqlApplyMergeField($sql, $mrg, $d, $colDef);
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
							require_once CMS_ROOT. 'zenario/includes/cms.inc.php';
							reportDatabaseErrorFromHelperFunction(adminPhrase('The column `[[col]]` in the table `[[table]]` is encrypted and cannot be used in a WHERE-statement.', array('col' => $colName, 'table' => $tableName)));
						}
					
						$colName = '#'. $colName;
					}
					
					$sql .= $a. '.`'. $colName. '` '. $c. ' ';
					sqlApplyMergeField($sql, $mrg, $d, $colDef, true);
					break;
			}
		}	
	}
	
	$sql .= $parts[$count];
}

function sqlApplyMergeField(&$sql, &$mrg, $key, $colDef = false, $useHash = false) {
	
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
				$cipher = hashDBColumn($val);
			} else {
				$cipher = zewl::encrypt($val, true);
			}
			$sql .= "'". sqlEscape($cipher). "'";
		
		} elseif ($colDef->isFloat) {
			$sql .= (float) $val;
		
		} elseif ($colDef->isInt) {
			$sql .= (int) $val;
		
		} else {
			$sql .= "'". sqlEscape($val). "'";
		}
	
	//Call inEscape() for arrays
	} elseif (is_array($val)) {
		$sql .= inEscape($val);
	
	//Try and auto-detect the value of anything else
	} else {
		$sql .= stringToIntOrFloat($val, true);
	}
}

//Replacement for mysql_query()
//Runs a SQL query without updating the revision number or clearing the cache
function sqlSelect($sql, $mrg = false, $tableName = false) {
	
	if (!cms_core::$lastDB) {
		return false;
	}
	
	//Attempt to get a list of column definitions for the columns we are about to select from
	$colDefs = array();
	$colDefsAreSpecfic = false;
	if ($mrg) {
		sqlAddMergeFields($sql, $mrg, $colDefs);
		$colDefsAreSpecfic = true;
	
	} elseif ($tableName) {
		if (!isset(cms_core::$dbCols[$tableName])) {
			checkTableDefinition($tableName);
		}
		$colDefs = &cms_core::$dbCols[$tableName];
	}
	
	if ($result = cms_core::$lastDB->query($sql)) {
		return new zenario_sql_query_wrapper($result, $colDefs, $colDefsAreSpecfic);
	
	} else {
		handleDatabaseError(cms_core::$lastDB, $sql);
	}
}

//Runs a SQL query and always updates the revision number and clears the cache if needed
function sqlUpdate($sql, $mrg = false, $checkCache = true) {
	
	if (!cms_core::$lastDB) {
		return false;
	}
	
	if ($mrg) {
		$colDefs = array();
		sqlAddMergeFields($sql, $mrg, $colDefs);
	}
	
	if ($result = cms_core::$lastDB->query($sql)) {
		
		if (cms_core::$lastDB->affected_rows) {
			updateDataRevisionNumber();
		
			if ($checkCache) {
				$ids = $values = false;
				reviewDatabaseQueryForChanges($sql, $ids, $values);
			}
		}
		return new zenario_sql_query_wrapper($result);
	
	} else {
		handleDatabaseError(cms_core::$lastDB, $sql);
	}
}

//Deprecated function, please call either sqlSelect() or sqlUpdate() instead!
function sqlQuery($sql, $checkCache = true) {
	$test = strtoupper(substr(trim($sql), 0, 3));
	if ($test != 'DES' && $test != 'SEL' && $test != 'SET' && $test != 'SHO') {
		return sqlUpdate($sql, false, $checkCache);
	} else {
		return sqlSelect($sql);
	}
}

//Replacement for mysql_real_escape_string()
function sqlEscape($text) {
	return cms_core::$lastDB->escape_string($text);
}

//Auto-convert ints and floats that were entered as strings into numbers.
//There are two modes here:
	//By default, strings are always converted into either an int or a float.
	//If $sqlEscapeStrings is set, strings may be left as strings and will be sqlEscape()'ed if needed
function stringToIntOrFloat($text, $sqlEscapeStrings = false) {
	if (is_string($text)) {
		if (is_numeric($text))  {
			if ($text[0] !== '0' && strpbrk($text, '.eE') === false) {
				return (int) $text;
			
			} elseif (!$sqlEscapeStrings) {
				return (float) $text;
			}
		}
		if ($sqlEscapeStrings) {
			return "'". cms_core::$lastDB->escape_string($text). "'";
		} else {
			return engToBoolean($text);
		}
	}
	return $text;
}

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

//Update the values stored for something (e.g. a content item) in a linking table
function updateLinkingTable($table, $key, $idCol, $ids = array()) {
	
	if (!is_array($ids)) {
		$ids = explodeAndTrim($ids);
	}
	
	//Delete anything that wasn't picked from the linking table
	//E.g. deleteRow('group_content_link', array('equiv_id' => 42, 'content_type' => 'test', 'group_id' => array('!' => array(1, 2, 3, 4, 5, 6, 7, 8))));
	$key[$idCol] = array('!' => $ids);
	deleteRow($table, $key);
	
	//Make sure each new row exists
	foreach ($ids as $id) {
		$key[$idCol] = $id;
		setRow($table, array(), $key);
	}
}


function columnIsEncrypted($table, $column) {
	
	$tableName = DB_NAME_PREFIX. $table;
	
	if (!isset(cms_core::$dbCols[$tableName])) {
		checkTableDefinition($tableName);
	}

	return isset(cms_core::$dbCols[$tableName][$column])? cms_core::$dbCols[$tableName][$column]->encrypted : null;
}


function columnIsHashed($table, $column) {
	
	$tableName = DB_NAME_PREFIX. $table;
	
	if (!isset(cms_core::$dbCols[$tableName])) {
		checkTableDefinition($tableName);
	}

	return isset(cms_core::$dbCols[$tableName][$column])? cms_core::$dbCols[$tableName][$column]->hashed : null;
}