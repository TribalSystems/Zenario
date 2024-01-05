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

class ring {
	//N.b. I wanted to name this class "ze\ring", but using the word "string" is not allowed,
	//so "ring" will have to do!


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


	public static function base64Decode($text) {
		return base64_decode(strtr($text, '-_', '+/'));
	}

	public static function base64To16($text) {
		$data = unpack('H*', \ze\ring::base64Decode($text));
		if (!empty($data[1])) {
			return $data[1];
		}
	}
	
	public static function encodeToUtf8($text) {
		//This function was taken from one of the comments in the PHP documentation:
		//https://www.php.net/manual/en/function.mb-convert-encoding.php
		//This is in the public domain.
		return mb_convert_encoding($text, "UTF-8", mb_detect_encoding($text, "UTF-8, ISO-8859-1, ISO-8859-15", true));
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

	public static function displayHTMLAsPlainText(&$text, $excerptLength = 200, $stripTitles = false, $highlightTerm = false, $excerptCutOffPhrase = '...') {
		if (!$text) {
			$text = '';
		}
		
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
	public static function HTMLId($text) {
		return preg_replace('/[^a-zA-Z0-9]/', '_', $text);
	}

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
	public static function validateScreenName($screenName) {
		$invalid = @preg_match('/[^a-zA-Z0-9\-\_\.]/', $screenName) ?: 0;
	
		return !$invalid;
	}
	
	public static function checkStringHasSpecialCharacters($string, $allowMultilingualChars = true) {
		//Attempt to validate allowing UTF-8 characters through
		$invalid = $allowMultilingualChars? @preg_match('/[^\p{L}\p{M} \d\-\_]/u', $string) : 0;
	
		//Fall back to traditional pattern matching if that fails
		if ($invalid !== 0 && $invalid !== 1) {
			$invalid = preg_match('/[^\w \d\-\_]/u', $string);
		}
	
		return !$invalid;
	}
	
	public static function convertAccentsInStringToAscii($string) {
		$a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
		$b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
		return str_replace($a, $b, $string);
	}
	
	public static function validateAnchorTag($string) {
		//Do not allow spaces or any of the following: #%^[]{}\"<>`\'
		$invalid = @preg_match('/[\#\%\^\[\]\{\}\\\"\<\>\`\'\s]/', $string) ?: 0;
	
		return !$invalid;
	}
	
	//A very restrictive HTML sanitiser.
	//Aimed at sanitising HTML provided by users, e.g. in comments and forum posts.
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

	//A less restrictive HTML sanitiser.
	//Aimed at sanitising HTML provided by admins, e.g. in WYSIWYG Editors
	public static function sanitiseWYSIWYGEditorHTML($html, $preserveMergeFields = false) {
		
		//By default HTMLPurifier will mangle any spaces and merge fields in attributes, and doesn't have any config option
		//to disable this other than to switch off some of its security, which we don't want to do.
		//Instead, we're using a hack to prevent this happening.
		//For the hack to work, we'll need to use a ~ as a special character so before doing anything else, escape any existing ~s in the string.
		if ($preserveMergeFields) {
			$html = str_replace('~', '~t', $html);
		}
		
		//Run HTMLPurifier
		$config = \HTMLPurifier_Config::createDefault();
		$config->set('Attr.EnableID', true);
		$config->set('Attr.AllowedFrameTargets', ['_blank' => true]);
		$config->set('Attr.AllowedRel', [
			'alternate', 'author', 'bookmark', 'external', 'help', 'license', 'next', 'nofollow',
			'noopener', 'noreferrer', 'prev', 'search', 'tag',
			'colorbox'
		]);
		
		$config->set('Cache.DefinitionImpl', null);
		$purifier = new \HTMLPurifier($config);
		$html = $purifier->purify($html);
		
		
		//If we want to use preserve mergefields, we'll need to scan through the tags HTMLPurifier just generated.
		if ($preserveMergeFields) {
			//Convert the HTML to an object
			$DOMDocument = new \DOMDocument('1.0', 'UTF-8');
			libxml_use_internal_errors(true);
				$DOMDocument->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' . $html. '</body></html>');
			libxml_use_internal_errors(false);

			$elements = $DOMDocument->getElementsByTagName('*');
			
			//Scan through all elements/attributes, looking for mangled spaces, square brackets and curley brackets.
			//Replace them with more ~-escaped characters.
			foreach ($elements as $item) {
				if ($item->attributes
				 && $item->attributes->length) {
					foreach ($item->attributes as $a => $b) {
						$item->setAttribute($a, str_replace('%7B', '~c', str_replace('%7D', '~v', str_replace('%5B', '~l', str_replace('%5D', '~r', str_replace('%20', '~s', $item->getAttribute($a)))))));
					}
				}
			}
			
			//Convert back to a string
			$html = $DOMDocument->saveHTML();
			$start = strpos($html, '<body>') + 6;
			$stop = strrpos($html, '</body>') - $start;
			$html = substr($html, $start, $stop);
			
			//Convert the escaped characters back to the characters they should be
			$html = str_replace('~t', '~', str_replace('~c', '{', str_replace('~v', '}', str_replace('~l', '[', str_replace('~r', ']', str_replace('~s', ' ', $html))))));
		}
		
		return $html;
	}
	
	
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
	
	public static function stringContainsTooManyProfanities($text = '', $toleranceLevel = "none") {
		if (is_numeric($toleranceLevel)) {
			$toleranceLevelNumeric = (int) $toleranceLevel;
		} else {
			switch ($toleranceLevel) {
				case 'high':
					$toleranceLevelNumeric = 15;
					break;
				case 'medium':
					$toleranceLevelNumeric = 10;
					break;
				case 'low':
					$toleranceLevelNumeric = 5;
					break;
				case 'none':
				default:
					$toleranceLevelNumeric = 0;
					break;
			}
		}
		
		$path = CMS_ROOT . 'zenario/libs/not_to_redistribute/profanity-filter/profanities.csv';
		$file = fopen($path, "r");
		$rating = 0;
		
		if (is_string($text)) {
			while(!feof($file)) {
				$line = fgetcsv($file);
				$word = str_replace('-', '\\W*', $line[0]);
				$level = $line[1];
			
				preg_match_all("#\b". $word ."(?:es|s)?\b#si", $text, $matches, PREG_SET_ORDER);
				$rating += count($matches) * $level;
			}
		
			fclose($file);
		}
		
		return $rating > $toleranceLevelNumeric;
	}
	
	public static function randomFromSetNoProfanities($requiredLength = 5, $set = 'ABCDEFGHIJKLMNPQRSTUVWXYZ', $toleranceLevel = 'none') {
	
		//Make sure the generated code does not end up being a swear word.
		$code = '';
		do {
			//The letter O is omitted to avoid confusion with the number 0.
			$code = \ze\ring::randomFromSet($requiredLength, $set);
			$codeContainsProfanities = \ze\ring::stringContainsTooManyProfanities($code, $toleranceLevel);
		} while ($codeContainsProfanities);
		
		return $code;
	}
}