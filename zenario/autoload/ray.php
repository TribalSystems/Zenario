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

class ray {
	//N.b. I wanted to name this class "ze\array", but using the word "array" is not allowed,
	//so "ray" will have to do!



	//Formerly "sortByOrd()"
	public static function sortByOrd($a, $b) {
		if ($a['ord'] == $b['ord']) {
			return 0;
		}
		return ($a['ord'] < $b['ord']) ? -1 : 1;
	}

	//Formerly "arrayValuesToKeys()"
	public static function valuesToKeys($a) {
		$o = [];
		if (is_array($a)) {
			foreach ($a as $k => &$v) {
				//if (!is_array($v) && !is_object($v))
				$o[$v] = true;
			}
		}
		return $o;
	}

	public static function pullOutKey($a, $key) {
		$o = [];
		if (is_array($a)) {
			foreach ($a as $v) {
				if (is_array($a) && isset($v[$key])) {
					$o[] = $v[$key];
				}
			}
		}
		return $o;
	}

	//Formerly "engToBooleanArray()"
	public static function engToBooleanArray(&$array, $key, $key2 = false, $key3 = false, $key4 = false, $key5 = false, $key6 = false, $key7 = false, $key8 = false, $key9 = false) {
	
		if (is_array($array) && isset($array[$key])) {
			if ($key2 === false) {
				return \ze\ring::engToBoolean($array[$key]);
			} else {
				return \ze\ray::engToBooleanArray($array[$key], $key2, $key3, $key4, $key5, $key6, $key7, $key8, $key9);
			}
		} else {
			return 0;
		}
	}

	const explodeAndTrimFromTwig = true;
	//Explode a string, and return any values that aren't empty and/or whitespace
	//Formerly "explodeAndTrim()"
	public static function explodeAndTrim($string, $mustBeNumeric = false, $separator = ',') {
		$a = [];
		foreach (explode($separator, $string) as $id) {
			if (($id = trim($id))
			 && (!$mustBeNumeric || ($id = (int) $id))) {
				$a[] = $id;
			}
		}
		return $a;
	}

	//Formerly "explodeDecodeAndTrim()"
	public static function explodeDecodeAndTrim($string, $separator = ',') {
		$a = [];
		foreach (explode($separator, $string) as $id) {
			if ($id = \ze\ring::decodeIdForOrganizer(trim($id))) {
				$a[] = $id;
			}
		}
		return $a;
	}


	//Formerly "issetArrayKey()"
	public static function issetArrayKey($array, $key, $key2 = false, $key3 = false, $key4 = false, $key5 = false, $key6 = false, $key7 = false, $key8 = false, $key9 = false) {
	
		if (is_array($array) && isset($array[$key])) {
			if ($key2 === false) {
				return (bool) $array[$key];
			} else {
				return \ze\ray::issetArrayKey($array[$key], $key2, $key3, $key4, $key5, $key6, $key7, $key8, $key9);
			}
		} else {
			return false;
		}
	}

	//Formerly "jsonEncodeForceObject()"
	//Dump an array as JSON to send to the client.
	//Also force any empty arrays to be objects, not arrays, but without using the JSON_FORCE_OBJECT logic
	//which corrupts actual arrays.
	public static function jsonDump(&$tags) {
		echo str_replace('":[]', '":{}', json_encode($tags, JSON_INVALID_UTF8_SUBSTITUTE));
	}

	//Convert a value to a 1D array, or merge a 2D array into a 1D array.
	//Formerly "oneDimensionalArray()"
	public static function oneD(&$a) {
		if (!is_array($a)) {
			$a = [$a];
			return;
		}

		foreach ($a as &$b) {
			if (!is_array($b)) {
				return;
			} else {
				break;
			}
		}
	
		$a = call_user_func_array('array_merge', $a);
	}

	//Get a value from an array of merge fields, where you're not sure of the index name but have a few ideas about what it might be called
	//Formerly "pullFromArray()"
	public static function grabValue(&$array/*, $key1, $key2 [, $key3 [, $key4 [, ... ]]]*/) {
	
		if (!is_array($array)) {
			return false;
		}
	
		$keys = func_get_args();
		array_splice($keys, 0, 1);
	
		foreach ($keys as &$key) {
			if ($key = preg_replace('/[^a-zA-Z]/', '', strtolower($key))) {
				foreach ($array as $akey => &$value) {
					$akey = preg_replace('/[^a-zA-Z]/', '', strtolower($akey));
					if ($akey == $key) {
						return $value;
					}
				}
			}
		}
	
		return false;
	}


	//Formerly "sqlArraySort()"
	public static function sqlSort(&$phpArray) {
	
		if (is_array($phpArray) && !empty($phpArray)) {
			$sql = "show collation like 'utf8_unicode_ci'";
			$result = \ze\sql::select($sql);
			$collate = \ze\sql::fetchRow($result);
		
			$first = true;
			$arraysOfArrays = [];
			$sql = "
				SELECT *
				FROM (";
		
			foreach ($phpArray as $key => $value) {
				$sql .= "\n". ($first? "" : " UNION "). " SELECT ";
			
				if ($arraysOfArrays[$key] = is_array($value)) {
					$value = json_encode($value);
				}
			
				if (is_numeric($key)) {
					$sql .= (int) $key;
				} else {
					$sql .= "'". \ze\escape::sql($key). "'";
				}
			
				$sql .= ", _utf8'". \ze\escape::sql($value). "'";
			
				if ($collate) {
					$sql .= "COLLATE utf8_unicode_ci";
				}
			
				$first = false;
			}
		
			$sql .= ") x
				ORDER BY 2";
		
			$phpArray = [];
			$result = \ze\sql::select($sql);
			while ($row = \ze\sql::fetchRow($result)) {
				if ($arraysOfArrays[$row[0]]) {
					$phpArray[$row[0]] = json_decode($row[1], true);
				} else {
					$phpArray[$row[0]] = $row[1];
				}
			}
		}
	}

	//This function is deprecated since php 7.0; please use the null coalescing operator (??) instead!
	//Formerly "arrayKey()"
	public static function value(&$a, $k) {
		if (is_array($a) && isset($a[$k])) {
			$result = &$a[$k];
			$count = func_num_args();
			for($i = 2; $i < $count; ++$i){
				if(!is_array($result)) return false;
				$arg = func_get_arg($i);
				if(!isset($result[$arg])) return false;
				$result = &$result[$arg];
			}
			return $result;
		}
		return false;
	}

}