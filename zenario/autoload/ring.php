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

class ring {
	//N.b. I wanted to name this class "ze\ring", but using the word "string" is not allowed,
	//so "ring" will have to do!


	//Formerly "addAmp()"
	public static function addAmp($request) {
		if (is_array($request)) {
			$request = http_build_query($request);
		}
	
		if ($request != '' && substr($request, 0, 1) != '&') {
			return '&'. $request;
		} else {
			return $request;
		}
	}

	//Formerly "addQu()"
	public static function addQu($request) {
		if (is_array($request)) {
			$request = http_build_query($request);
		}
	
		if ($request != '') {
			switch (substr($request, 0, 1)) {
				case '?':
					return $request;
				case '&':
					return '?'. substr($request, 1);
				default:
					return '?'. $request;
			}
		} else {
			return $request;
		}
	}


	//Formerly "base64Decode()"
	public static function base64Decode($text) {
		return base64_decode(strtr($text, '-_', '+/'));
	}

	//Formerly "base64To16()"
	public static function base64To16($text) {
		$data = unpack('H*', \ze\ring::base64Decode($text));
		if (!empty($data[1])) {
			return $data[1];
		}
	}

	//Formerly "chopPrefixOffString()", "chopPrefixOffOfString()"
	public static function chopPrefix($prefix, $string, $returnStringOnFailure = false, $caseInsensitive = false) {
		$compareString = $caseInsensitive ? strtolower($string) : $string;
		$comparePrefix = $caseInsensitive ? strtolower($prefix) : $prefix;
		
		if ($compareString === $comparePrefix) {
			return '';
		}
	
		$len = strlen($comparePrefix);
	
		if (substr($compareString, 0, $len) == $comparePrefix) {
			return substr($string, $len);
		} elseif ($returnStringOnFailure) {
			return $string;
		} else {
			return false;
		}
	}
	
	public static function chopSuffix($string, $suffix, $returnStringOnFailure = false) {
		if ($string === $suffix) {
			return '';
		}
		
		$len = strlen($suffix);
	
		if (substr($string, -$len) == $suffix) {
			return substr($string, 0, -$len);
		} elseif ($returnStringOnFailure) {
			return $string;
		} else {
			return false;
		}
	}

	//Formerly "explodeAndSet()"
	public static function explodeAndSet($delimiter, $string, &...$args) {
		$vals = explode($delimiter, $string, count($args));
	
		foreach ($args as $i => &$arg) {
			$arg = $vals[$i] ?? null;
		}
	}

	//Reverses \ze\ring::encodeIdForOrganizer()
	//Formerly "decodeItemIdForOrganizer()", "decodeItemIdForStorekeeper()"
	public static function decodeIdForOrganizer($id, $prefix = '~') {
		$len = strlen($prefix);
		if (substr($id, 0, $len) == $prefix) {
			return rawurldecode(str_replace('~', '%', substr($id, $len)));
		} else {
			return $id;
		}
	}

	//Formerly "displayHTMLAsPlainText()"
	public static function displayHTMLAsPlainText(&$text, $excerptLength = 200, $stripTitles = false, $highlightTerm = false, $excerptCutOffPhrase = '...') {
		if ($stripTitles) {
			$text = preg_replace('@<h\d>.*?</h\d>@is', '', $text);
		}
	
		$text = strip_tags($text);
	
		if (strlen($text) > (int) $excerptLength) {
			$text = mb_substr($text, 0, (int) $excerptLength,'UTF-8');
		
			$text = preg_replace("/[\S]+$/","",$text);
			$text = preg_replace("/[\s\t\n\r,]+$/","",$text);
			
			if (!preg_match("/[\.]$/",$text)) {
				$text .= $excerptCutOffPhrase;
			}
		} else {
			$text = preg_replace("/[\s\t\n\r,]+$/","",$text);
		}
	
		if ($highlightTerm !== false) {
			$text = str_ireplace($highlightTerm, '<strong>'. $highlightTerm. '</strong>', $text);
		}
	}

	//Given a string, this function makes it safe to use in the URL after a hash (i.e. a safe id for Storekeeper)
	//Formerly "encodeItemIdForOrganizer()", "encodeItemIdForStorekeeper()"
	public static function encodeIdForOrganizer($id, $prefix = '~') {
		if (is_numeric($id)) {
			return $id;
		} else {
			return $prefix. str_replace('%', '~', str_replace('.', '%2E', str_replace('~', '%7E', rawurlencode($id))));
		}
	}


	//Formerly "engToBoolean()"
	public static function engToBoolean($text) {
		if (is_object($text) && get_class($text) == 'ze\error') {
			return 0;
	
		} elseif (is_bool($text) || is_numeric($text)) {
			return (int) ((bool) $text);
	
		} else {
			return (int) (false !== filter_var($text, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
		}
	}

	//Encode a random name to something suitable for a HTML ID by replacing anything
	//non-alphanumeric with an underscore
	//Formerly "HTMLId()"
	public static function HTMLId($text) {
		return preg_replace('/[^a-zA-Z0-9]/', '_', $text);
	}

	//Formerly "urlRequest()"
	public static function urlRequest($arr, $tidyClutter = false) {
		if (!$arr) {
			return '';
		} elseif (!is_array($arr)) {
			return \ze\ring::addAmp($arr);
		}
	
		$request = '';
		foreach ($arr as $name => &$value) {
		
			if (!$tidyClutter || $value) {
				$request .= '&'. rawurlencode($name). '=';
		
				if ($value !== false && $value !== null) {
					$request .= rawurlencode($value);
				}
			}
		}
	
		return $request;
	}

	//Attempt to check if an email address looks valid
	//Formerly "validateEmailAddress()"
	public static function validateEmailAddress($email, $multiple = false) {
		$valid = false;
	
		if ($multiple) {
			$addresses = preg_split("/[\,\;]+/", $email);	
		} else {
			$addresses = [$email];
		}
	
		foreach ($addresses as &$addr) {
			if ($multiple) {
				if (!($addr = trim($addr))) {
					continue;
				}
			}
		
			if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) {
				return false;
			}
		}
	
		return true;
	}

	//Validate a screen name
	//Formerly "validateScreenName()"
	public static function validateScreenName($screenName, $allowMultilingualChars = true) {
	
		//Attempt to validate allowing UTF-8 characters through
		$invalid = $allowMultilingualChars? @preg_match('/[^\p{L}\p{M} \d\-\_]/u', $screenName) : 0;
	
		//Fall back to traditional pattern matching if that fails
		if ($invalid !== 0 && $invalid !== 1) {
			$invalid = preg_match('/[^\w \d\-\_]/u', $screenName);
		}
	
		return !$invalid;
	}

	//Formerly "sanitiseHTML()"
	public static function sanitiseHTML($html, $allowable_tags = '', $allowedStyles = [], $allowedAttributes = []) {

		$DOMDocument = new \DOMDocument('1.0', 'UTF-8');
		libxml_use_internal_errors(true);
		
			$DOMDocument->loadHTML('<?xml encoding="UTF-8">' . $html);
		
			//Or possibly:
			//$DOMDocument->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' . $html. '</body></html>');
		libxml_use_internal_errors(false);

		$elements = $DOMDocument->getElementsByTagName('*');
	
		$allowedAttributes['src'] = true;

		foreach ($elements as $item) {
			if ($item->attributes
			 && $item->attributes->length) {
				foreach ($item->attributes as $a => $b) {
				
					//Always allow:
					//- href and rel in links,
					//- src in images.
					if ($item->tagName == 'a' && ($a == 'href' || $a == 'rel')) {
					} elseif ($item->tagName == 'img' && $a == 'src') {
				
					} elseif ($a == 'style') {
						$styles = [];
					
						if (!empty($allowedStyles)) {
						
							foreach (explode(';', $item->getAttribute('style')) as $style) {
								$keyValue = explode(':', $style);
								if ($keyValue[0] = trim($keyValue[0])) {
									if (!empty($allowedStyles[$keyValue[0]])) {
										$styles[] = $keyValue[0]. ':'. $keyValue[1];
									}
								}
							}
						}
					
						if (!empty($styles)) {
							$item->setAttribute('style', implode(';', $styles));
						} else {
							$item->removeAttribute($a);
						}
				
					} elseif (empty($allowedAttributes[$a])) {
						$item->removeAttribute($a);
					}
				}
			}
		}
	
		return strip_tags($DOMDocument->saveHTML(), $allowable_tags);
	}

	//Formerly "trimNonWordCharactersUnicode()"
	public static function trimNonWordCharactersUnicode($string) {
		$out = @preg_replace('/[^\p{L}\p{M}\d\-]/u', '', $string);
	
		//Fall back to traditional pattern matching if that fails
		if ($out === null) {
			$out = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
		}
	
		return $out;
	}


	private static $rngIsSeeded = false;

	//Check if the random number generator has been seeded yet, and seed it if not
	//Formerly "seedRandomNumberGeneratorIfNeeded()"
	public static function seedRandomNumberGeneratorIfNeeded() {
		if (!self::$rngIsSeeded) {
			//If we need to seed the random number generator, get the number of microseconds
			//past the current second, and use that to seed
			$x = explode(' ', microtime());
			mt_srand((int) (1000000 * $x[0]));
		
			self::$rngIsSeeded = true;
		}
	}
	


	//Generate a random string of numbers and letters
	//Formerly "randomString()"
	public static function random($requiredLength = 12) {
	
		\ze\ring::seedRandomNumberGeneratorIfNeeded();
	
		$stringOut = '';
		//Loop while our output string is still too short
		while (strlen($stringOut) < $requiredLength) {
			$stringOut .= \ze::base64(pack('I', mt_rand()));
		}
		return substr($stringOut, 0, $requiredLength);
	}

	//Generate a string from a specific set of characters
		//By default I've stripped out vowels, just in case a swearword is randomly generated.
		//Also "1"s look too much like "l"s and "0"s look too much like "o"s so I've removed those too for clarity.
	//Formerly "randomStringFromSet()"
	public static function randomFromSet($requiredLength = 12, $set = 'BCDFGHJKMNPQRSTVWXYZbcdfghjkmnpqrstvwxyz23456789') {
		$lettersToUse = str_split($set);
		$max = count($lettersToUse) - 1;
	
		$stringOut = '';
		for ($i = 0; $i < $requiredLength; ++$i) {
			$stringOut .= $lettersToUse[mt_rand(0, $max)];
		}
	
		return $stringOut;
	}

}