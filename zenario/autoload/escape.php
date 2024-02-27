<?php
/*
 * Copyright (c) 2024, Tribal Limited
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
	
	//Remove non-utf8 characters
	public static function utf8($text) {
		return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
	}
	
	public static function asciiInSQL($text) {
		return \ze::$dbL->con->escape_string(preg_replace('@[^(\x20-\x7E)]*@', '', $text));
	}
	
	

	//This function is used in AJAX requests to send additional metadata and flags to the JavaScript running on the client.
	//It uses HTML Headers if possible, but if this is not possible due to a large payload or it being too late to send a header,
	//it will write the flag using a custom HTML element instead.
	public static function flag($name, $val = null, $useHeader = true) {
		$flag = $name;
		
		if ($val !== null) {
			$flag .= ':'. \ze\escape::hyp($val);
		
		} elseif ($useHeader) {
			$flag .= ':`1';
		}
		
		if ($useHeader) {
			header('Zenario-Flag-'. $flag);
		} else {
			//Old HTML comment format
			//echo '<!--', $flag, '-->';
			
			//New custom element format
			echo '<x-zenario-flag value="', $flag, '"/>';
		}
	}


	public static function hyp($text) {
		return str_replace(
			['`',	'-',	':',	"\n",	"\r",	'&',	'"',	'<',	'>'],
			['`t',	'`h',	'`c',	'`n',	'`r',	'`a',	'`q',	'`l',	'`g'],
			$text
		);
	}

	const jsFromTwig = true;
	public static function js($text) {
		return strtr(addcslashes((string) $text, "\\\n\r\"'"), ['&' => '\\x26', '<' => '\\x3c', '>' => '\\x3e', '{' => '\\x7b', '}' => '\\x7d']);
	}

	const jsOnClickFromTwig = true;
	public static function jsOnClick($text) {
		return htmlspecialchars(addcslashes((string) $text, "\\\n\r\"'"));
	}

	public static function utf($string) {
		return mb_detect_encoding($string, 'UTF-8', true)? $string : '<<invalid UTF-8 string>>';
	}

	public static function xml($text) {
		return str_ireplace(["'", '&nbsp;'], ['&apos;', ' '], htmlspecialchars($text));
	}




	public static function like($text, $allowStarsAsWildcards = false) {
	
		if (!$allowStarsAsWildcards) {
			return str_replace('%', '\\%', str_replace('_', '\\_', \ze\escape::sql($text)));
	
		} elseif ($text == '*') {
			return '_';
	
		} else {
			return str_replace('*', '%', str_replace('%', '\\%', str_replace('_', '\\_', \ze\escape::sql($text))));
		}
	}
	
	public static function microtemplate($text) {
		return strtr(htmlspecialchars($text), ['{' => '&#123;', '}' => '&#125;']);
	}
	
	
	
	

	//Replacement for mysql_real_escape_string()
	public static function sql($text) {
		return \ze::$dbL->con->escape_string($text);
	}
	
	public static function json($val) {
		if (is_null($val) || false === ($enc = json_encode($val))) {
			return 'NULL';
		} else {
			return '\''. \ze::$dbL->con->escape_string($enc). '\'';
		}
	}
	
	public static function intOrNull($int) {
		if (is_null($int)) {
			return 'NULL';
		} else {
			return (int) $int;
		}
	}

	//Auto-convert ints and floats that were entered as strings into numbers.
	//There are two modes here:
		//By default, strings are always converted into either an int or a float.
		//If $sqlEscapeStrings is set, strings may be left as strings and will be \ze\escape::sql()'ed if needed
	public static function stringToIntOrFloat($text, $sqlEscapeStrings = false, $isJSON = false) {
		
		if ($isJSON) {
			if ($text === true) {
				return 'TRUE';
			
			} elseif ($text === false) {
				return 'FALSE';
			
			} elseif ($text === null) {
				return 'NULL';
			}
		}
		
		if (is_string($text)) {
			if (is_numeric($text))  {
				if ($text[0] !== '0' && strpbrk($text, '.eE') === false) {
					return (int) $text;
			
				} elseif (!$sqlEscapeStrings) {
					return (float) $text;
				}
			}
			if ($sqlEscapeStrings) {
				return "'". \ze::$dbL->con->escape_string($text). "'";
			} else {
				return \ze\ring::engToBoolean($text);
			}
		}
		return $text;
	}



	public static function in($csv, $escaping = -1, $prefix = false) {
		if (!is_array($csv)) {
			$csv = explode(',', $csv);
		}
		$sql = '';
		foreach ($csv as $var) {
			if (is_string($var)) {
				$var = trim($var);
			}
		
			if ($sql !== '') {
				$sql .= ',';
			}
		
			if (is_null($var)) {
				//Handle the case where someone passes a NULL value into an array.
				//This won't find any matches because IN() statements in SQL don't find NULLs.
				//However this line is here to stop a PHP or SQL error being triggered.
				$sql .= 'NULL';
		
			} elseif ($escaping === -1) {
				$sql .= \ze\escape::stringToIntOrFloat($var, true);
		
			} elseif ($escaping === 'numeric' || $escaping === true) {
				$sql .= (int) $var;
		
			} elseif ($escaping === 'sql') {
				$sql .= "'". \ze\escape::sql($var). "'";
		
			} elseif ($escaping === 'asciiInSQL') {
				$sql .= "'". \ze\escape::asciiInSQL($var). "'";
		
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

	public static function makeURLsNotClickable($text, $ignoreEmailAddresses = true) {
		$pattern = '/[a-zA-Z0-9\-\.\@]+\.[a-zA-Z]{2,4}(\:[0-9]+)?(\/\S*)?/';
		
		if (preg_match_all($pattern, $text, $out)) {
			foreach ($out[0] as $url) {
				if (!$ignoreEmailAddresses || strpos($url, '@') === false) {
					$nonClickableUrl = str_replace('.', '[.]', $url);
					$text = str_replace($url, $nonClickableUrl, $text);
				}
			}
		}

		return $text;
	}
}