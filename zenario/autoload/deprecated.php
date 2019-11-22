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

class deprecated {


	//Formerly "putErrorsOnAdminBoxTabs()"
	public static function putErrorsOnAdminBoxTabs(&$box, $e, $defaultTab = false, $specifics = []) {
		if (\ze::isError($e)) {
			$errors = $e->errors;
		} elseif (is_array($e)) {
			$errors = $e;
		} else {
			return;
		}
		
		foreach ($errors as $fieldName => &$error) {
			$error = ['c' => $error, 't' => ($specifics[$fieldName] ?? false)];
		}
	
		if (!empty($box['tabs'])) {
			foreach ($box['tabs'] as $tabName => &$tab) {
				if (is_array($tab) && !empty($tab['fields'])) {
				
					if (!$defaultTab || !isset($box['tabs'][$defaultTab])) {
						$defaultTab = $tabName;
					}
				
					foreach ($tab['fields'] as $fieldName => &$field) {
						if (is_array($field)) {
							if (isset($errors[$fieldName]) && empty($errors[$fieldName]['t'])) {
								$errors[$fieldName]['t'] = $tabName;
							}
						}
					}
				}
			}
		
			foreach ($errors as $fieldName => &$error) {
				if (!$error['t'] || !isset($box['tabs'][$error['t']])) {
					$error['t'] = $defaultTab;
				}
				if (!isset($box['tabs'][$error['t']]['errors']) || !is_array($box['tabs'][$error['t']]['errors'])) {
					$box['tabs'][$error['t']]['errors'] = [];
				}
				$box['tabs'][$error['t']]['errors'][] = \ze\admin::phrase($error['c']);
			}
		}
	}
	
	
	


	//Deprecated, please use \ze\lang::phrase() instead
	//Formerly "getVLPPhrase()"
	public static function getVLPPhrase($code, $replace = false, $languageId = false, $returnFalseOnFailure = false, $moduleClass = '', $phrase = false, $altCode = false) {
		return \ze\lang::phrase($code, $replace, $moduleClass, $languageId, 2);
	}
	
	
	


	//Warning: this is deprecated, please use the SUBDIRECTORY constant instead!
	//Formerly "CMSDir()"
	public static function CMSDir() {
		return SUBDIRECTORY;
	}

	//Warning: this is deprecated, please use the CMS_ROOT constant instead!
	//Formerly "absCMSDir()"
	public static function absCMSDir() {
		return CMS_ROOT;
	}



	//Formerly "addSqlDateTimeByPeriodAndReturnStartEnd()"
	public static function addSqlDateTimeByPeriodAndReturnStartEnd($sql_start_date, $by_period) {
		if(strpos($sql_start_date, '23:59:59')) {
			$sql_start_date = strtotime('+1 second', strtotime($sql_start_date));
		} else {
			$sql_start_date = strtotime($sql_start_date);
		}
		$sql_end_date = strtotime($by_period . ' -1 second', $sql_start_date);

		$sql_start_date = date('Y-m-d H:i:s', $sql_start_date);
		$sql_end_date = date('Y-m-d H:i:s', $sql_end_date);

		//echo $sql_start_date, " ", $sql_end_date, "\n";

		return [$sql_start_date, $sql_end_date];
	}

	//Deprecated function, please call either \ze\sql::select() or \ze\sql::update() instead!
	//Formerly "my_mysql_query()"
	public static function my_mysql_query($sql, $updateDataRevisionNumber = -1, $checkCache = true, $return = 'sqlSelect') {
	
		if ($return === true || $return === 'mysql_affected_rows' || $return === 'mysql_affected_rows()' || $return === 'sqlAffectedRows' || $return === '\ze\sql::affectedRows()') {
			if (\ze\sql::update($sql, false, $checkCache)) {
				return \ze\sql::affectedRows();
			}
	
		} elseif ($return === 'mysql_insert_id' || $return === 'mysql_insert_id()' || $return === 'sqlInsertId' || $return === '\ze\sql::insertId()') {
			if (\ze\sql::update($sql, false, $checkCache)) {
				return \ze\sql::insertId();
			}
	
		} else {
			return \ze\deprecated::sqlQuery($sql, $checkCache);
		}
	
		return false;
	}

	//Deprecated function, please call either \ze\sql::select() or \ze\sql::update() instead!
	//Formerly "sqlQuery()"
	public static function sqlQuery($sql, $checkCache = true) {
		$test = strtoupper(substr(trim($sql), 0, 3));
		if ($test != 'DES' && $test != 'SEL' && $test != 'SET' && $test != 'SHO') {
			return \ze\sql::update($sql, false, $checkCache);
		} else {
			return \ze\sql::select($sql);
		}
	}
	
	
	
	
	

	//Formerly "SimpleXMLString()"
	public static function SimpleXMLString(&$string) {
		//Check if the input is false before processing it, so this function can safely be chained
		if (!$string) {
			return false;
		}
	
		try {
			$xml = @new \SimpleXMLElement($string);
			return $xml;
		} catch (\Exception $e) {
			return false;
		}
	}
	
	
	
	

	
	
	


	/*
		Functions to help make dynamic forms
	*/


	//Get a description of a table
	//Formerly "getFields()"
	public static function getFields($prefix, $tableName, $addPasswordConfirm = false) {
		$fields = [];
		$sql = "DESC ". \ze\escape::sql($prefix. $tableName);
		$result = \ze\sql::select($sql);

		while($field = \ze\sql::fetchAssoc($result)) {
			$field['Table'] = $tableName;
		
			$field['Date'] = strpos($field['Type'], 'date') !== false;
		
			$field['Numeric'] = strpos($field['Type'], 'enum') === false
							&& (strpos($field['Type'], 'int') !== false
							 || strpos($field['Type'], 'double') !== false
							 || strpos($field['Type'], 'float') !== false);
		
			$fields[$field['Field']] = $field;
		
			if ($field['Field'] == 'password' && $addPasswordConfirm) {
				$fields[$field['Field']]['Type'] = 'password';
				$fields['password_reconfirm'] = $fields[$field['Field']];
				$fields['password_reconfirm']['Table'] = '';
				$fields['password_reconfirm']['Type'] = 'password_reconfirm';
			}
		}
	
		return $fields;
	}

	
	//A function to help with saving the returns from these fields
	//Formerly "addFieldToSQL()"
	public static function addFieldToSQL(&$sql, $table, $field, $values, $editing, $details = []) {
	
		if ($sql) {
			$sql .= ",";
		} elseif ($editing) {
			$sql = "
			UPDATE ". $table. " SET";
		} else {
			$sql = "
			REPLACE INTO ". $table. " SET";
		}
	
		$sql .= "
				". $field. " = ";
	
		//Attempt to save empty dates correctly in strict mode
		if ($details['Date'] && strlen((string) $values[$field] < 8)) {
			if ($details['Null'] == 'Yes') {
				$values[$field] = '';
			} else {
				$values[$field] = '0000-00-00';
			}
		}
	
		//Convert empty strings to NULLs if possible
		if ($values[$field] === '' && $details['Null'] == 'Yes') {
			$sql .= "NULL";
	
		//Otherwise convert empty strings to 0s for non-string fields
		} elseif (!$values[$field] && $details['Numeric']) {
			$sql .= "0";
	
		//Make sure Numeric values are actually numeric
		} elseif ($details['Numeric']) {
			$sql .= (int) $values[$field];
	
		//Otherwise use sqlEscape
		} else {
			$sql .= "'". \ze\escape::sql($values[$field]). "'";
		}
	}

}