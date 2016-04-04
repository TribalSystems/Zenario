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

//	function arrayKey(&$array, $key, $key2 = false, $key3 = false, $key4 = false, $key5 = false, $key6 = false, $key7 = false, $key8 = false, $key9 = false) {}

function arrayValuesToKeys($a) {
	$o = array();
	if (is_array($a)) {
		foreach ($a as $k => &$v) {
			//if (!is_array($v) && !is_object($v))
			$o[$v] = true;
		}
	}
	return $o;
}

function checkCURLEnabled() {
	return function_exists('curl_version');
}

function curl($URL, $post = false, $options = array(), $saveToFile = false) {
	if (!function_exists('curl_version')
	 || !($curl = @curl_init())) {
		return false;
	}

	$sReturn = '';
	$sReferer = httpHost();
	
	curl_setopt($curl, CURLOPT_FAILONERROR, true); 
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_TIMEOUT, 15);
	curl_setopt($curl, CURLOPT_VERBOSE, false);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
	curl_setopt($curl, CURLOPT_URL, $URL);
	curl_setopt($curl, CURLOPT_REFERER, httpHost());
	
	if ($saveToFile) {
		$fp = fopen($saveToFile, 'w');
		curl_setopt($curl, CURLOPT_FILE, $fp);
	} else {
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	}
	
	if (!empty($post)) {
		curl_setopt($curl, CURLOPT_POST, true);
		
		if ($post !== true) {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		}
	} else {
		curl_setopt($curl, CURLOPT_POST, false);
	}
	
	if (!empty($options)) {
		foreach ($options as $opt => $optVal) {
			curl_setopt($curl, $opt, $optVal);
		}
	}
	
	$result = curl_exec($curl);
	curl_close($curl);
	
	if ($saveToFile) {
		fclose($fp);
	}
	
	return $result;
}

//Ensure that empty arrays are objects
	//Note that if we ever raise our requirements from php 5.2 to php 5.3,
	//this could be replaced with a call to json_encode(..., JSON_FORCE_OBJECT);
function emptyArrayToObject(&$el) {
	if (is_array($el)) {
		if (empty($el)) {
			$el = (object) $el;
		} else {
			foreach ($el as &$child) {
				emptyArrayToObject($child);
			}
		}
	}
}

function engToBooleanArray(&$array, $key, $key2 = false, $key3 = false, $key4 = false, $key5 = false, $key6 = false, $key7 = false, $key8 = false, $key9 = false) {
	
	if (is_array($array) && isset($array[$key])) {
		if ($key2 === false) {
			return engToBoolean($array[$key]);
		} else {
			return engToBooleanArray($array[$key], $key2, $key3, $key4, $key5, $key6, $key7, $key8, $key9);
		}
	} else {
		return 0;
	}
}

//Explode a string, and return any values that aren't empty and/or whitespace
function explodeAndTrim($string, $mustBeNumeric = false, $separator = ',') {
	$a = array();
	foreach (explode($separator, $string) as $id) {
		if (($id = trim($id))
		 && (!$mustBeNumeric || ($id = (int) $id))) {
			$a[] = $id;
		}
	}
	return $a;
}

function explodeDecodeAndTrim($string, $separator = ',') {
	$a = array();
	foreach (explode($separator, $string) as $id) {
		if ($id = decodeItemIdForOrganizer(trim($id))) {
			$a[] = $id;
		}
	}
	return $a;
}

//	function get($name) {}

//A shortcut function to in_array, that looks similar to the MySQL in
//	function in($needle, $value1, $value2 [, $value3 [, $value4 [, ... ]]]) {}

function issetArrayKey($array, $key, $key2 = false, $key3 = false, $key4 = false, $key5 = false, $key6 = false, $key7 = false, $key8 = false, $key9 = false) {
	
	if (is_array($array) && isset($array[$key])) {
		if ($key2 === false) {
			return (bool) $array[$key];
		} else {
			return issetArrayKey($array[$key], $key2, $key3, $key4, $key5, $key6, $key7, $key8, $key9);
		}
	} else {
		return false;
	}
}

function jsonEncodeForceObject(&$tags) {
	if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
		echo json_encode($tags, JSON_FORCE_OBJECT);
	} else {
		emptyArrayToObject($tags);
		echo json_encode($tags);
	}
}

//Convert a value to a 1D array, or merge a 2D array into a 1D array.
function oneDimensionalArray(&$a) {
	if (!is_array($a)) {
		$a = array($a);
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


function putErrorsOnAdminBoxTabs(&$box, $e, $defaultTab = false, $specifics = array()) {
	if (isError($e)) {
		$errors = $e->errors;
	} elseif (is_array($e)) {
		$errors = $e;
	} else {
		return;
	}
		
	foreach ($errors as $fieldName => &$error) {
		$error = array('c' => $error, 't' => arrayKey($specifics, $fieldName));
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
				$box['tabs'][$error['t']]['errors'] = array();
			}
			$box['tabs'][$error['t']]['errors'][] = adminPhrase($error['c']);
		}
	}
}

//Get a value from an array of merge fields, where you're not sure of the index name but have a few ideas about what it might be called
function pullFromArray(&$array/*, $key1, $key2 [, $key3 [, $key4 [, ... ]]]*/) {
	
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

//	function post($name) {}

//	function request($name) {}

//	function session($name) {}

function SimpleXMLString(&$string) {
	//Check if the input is false before processing it, so this function can safely be chained
	if (!$string) {
		return false;
	}
	
	try {
		$xml = @new SimpleXMLElement($string);
		return $xml;
	} catch (Exception $e) {
		return false;
	}
}

function SimpleXMLCURL($url, &$xml, &$error) {
	$error = false;
	
	if (!function_exists('curl_init') || !($curl = @curl_init())) {
		return false;
	}
	
	curl_setopt($curl, CURLOPT_FAILONERROR, true); 
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, 15);
	curl_setopt($curl, CURLOPT_VERBOSE, false);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_REFERER, httpHost());
	curl_setopt($curl, CURLOPT_POST, false);
	
	$xml = curl_exec($curl);
	curl_close($curl);
	
	if ($xml === false) {
		return false;
		
	} elseif (!$xml) {
		return false;
		
	} else {
		try {
			$xml = @new SimpleXMLElement($xml);
		} catch (Exception $e) {
			$xml = false;
		}
		
		if (!$xml) {
			return false;
		}
	}
	
	return true;
}

function sqlArraySort(&$phpArray) {
	
	if (is_array($phpArray) && !empty($phpArray)) {
		$sql = "show collation like 'utf8_unicode_ci'";
		$result = sqlSelect($sql);
		$collate = sqlFetchRow($result);
		
		$first = true;
		$arraysOfArrays = array();
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
				$sql .= "'". sqlEscape($key). "'";
			}
			
			$sql .= ", _utf8'". sqlEscape($value). "'";
			
			if ($collate) {
				$sql .= "COLLATE utf8_unicode_ci";
			}
			
			$first = false;
		}
		
		$sql .= ") x
			ORDER BY 2";
		
		$phpArray = array();
		$result = sqlSelect($sql);
		while ($row = sqlFetchRow($result)) {
			if ($arraysOfArrays[$row[0]]) {
				$phpArray[$row[0]] = json_decode($row[1], true);
			} else {
				$phpArray[$row[0]] = $row[1];
			}
		}
	}
}