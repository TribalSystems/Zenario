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

class escape {


	//Remove non-ascii characters
	public static function ascii($text) {
		return preg_replace('@[^(\x20-\x7E)]*@', '', $text);
	}


	//Formerly "eschyp()"
	public static function hyp($text) {
		return str_replace(['`', '-', ':', "\n", "\r"], ['`t', '`h', '`c', '`n', '`r'], $text);
	}

	//Formerly "jsEscape()"
	public static function js($text) {
		return strtr(addcslashes($text, "\\\n\r\"'"), array('&' => '\\x26', '<' => '\\x3c', '>' => '\\x3e'));
	}

	//Formerly "jsOnClickEscape()", "jsOnclickEscape()"
	public static function jsOnClick($text) {
		return htmlspecialchars(addcslashes($text, "\\\n\r\"'"));
	}

	//Formerly "XMLEscape()"
	public static function xml($text) {
		return str_ireplace(array("'", '&nbsp;'), array('&apos;', ' '), htmlspecialchars($text));
	}




	//Formerly "likeEscape()"
	public static function like($text, $allowStarsAsWildcards = false) {
	
		if (!$allowStarsAsWildcards) {
			return str_replace('%', '\\%', str_replace('_', '\\_', \ze\escape::sql($text)));
	
		} elseif ($text == '*') {
			return '_';
	
		} else {
			return str_replace('*', '%', str_replace('%', '\\%', str_replace('_', '\\_', \ze\escape::sql($text))));
		}
	}
	
	
	
	

	//Replacement for mysql_real_escape_string()
	//Formerly "sqlEscape()"
	public static function sql($text) {
		return \ze::$lastDB->escape_string($text);
	}

	//Auto-convert ints and floats that were entered as strings into numbers.
	//There are two modes here:
		//By default, strings are always converted into either an int or a float.
		//If $sqlEscapeStrings is set, strings may be left as strings and will be \ze\escape::sql()'ed if needed
	//Formerly "stringToIntOrFloat()"
	public static function stringToIntOrFloat($text, $sqlEscapeStrings = false) {
		if (is_string($text)) {
			if (is_numeric($text))  {
				if ($text[0] !== '0' && strpbrk($text, '.eE') === false) {
					return (int) $text;
			
				} elseif (!$sqlEscapeStrings) {
					return (float) $text;
				}
			}
			if ($sqlEscapeStrings) {
				return "'". \ze::$lastDB->escape_string($text). "'";
			} else {
				return \ze\ring::engToBoolean($text);
			}
		}
		return $text;
	}



	//Formerly "inEscape()"
	public static function in($csv, $escaping = -1, $prefix = false) {
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
				$sql .= \ze\escape::stringToIntOrFloat($var, true);
		
			} elseif ($escaping === 'numeric' || $escaping === true) {
				$sql .= (int) $var;
		
			} elseif ($escaping === 'sql') {
				$sql .= "'". \ze\escape::sql($var). "'";
		
			} elseif ($escaping === 'identifier') {
				if ($prefix) {
					$sql .= $prefix. ".";
				}
			
				$sql .= "`". \ze\escape::sql($var). "`";
		
			} else {
				$sql .= str_replace(',', '', $var);
			}
		}
		return $sql;
	}


}