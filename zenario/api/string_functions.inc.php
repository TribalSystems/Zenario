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

function addAmp($request) {
	if (is_array($request)) {
		$request = http_build_query($request);
	}
	
	if ($request != '' && substr($request, 0, 1) != '&') {
		return '&'. $request;
	} else {
		return $request;
	}
}

function addQu($request) {
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

function base64($text) {
	return rtrim(strtr(base64_encode($text), '+/', '-_'), '=');
}

function base64Decode($text) {
	return base64_decode(strtr($text, '-_', '+/'));
}

function base16To64($text) {
	return base64(pack('H*', $text));
}

function base64To16($text) {
	$data = unpack('H*', base64Decode($text));
	if (!empty($data[1])) {
		return $data[1];
	}
}

//Find the lowest common denominator of two numbers
function rationalNumber(&$a, &$b) {
  for ($i = min($a, $b); $i > 1; --$i) {
	  if (($a % $i == 0)
	   && ($b % $i == 0)) {
		  $a = (int) ($a / $i);
		  $b = (int) ($b / $i);
	  }
  }
}

//Give a grid's cell a class-name based on how many columns it takes up, and the ratio out of the total width that it takes up
function rationalNumberGridClass($a, $b) {
	$w = $a;
	rationalNumber($a, $b);
	return 'span span'. $w. ' span'. $a. '_'. $b;
}

function chopPrefixOffString($prefix, $string, $returnStringOnFailure = false) {
	if ($string === $prefix) {
		return '';
	}
	
	$len = strlen($prefix);
	
	if (substr($string, 0, $len) == $prefix) {
		return substr($string, $len);
	} elseif ($returnStringOnFailure) {
		return $string;
	} else {
		return false;
	}
}

//Deprecated old version of the above function, with the inputs the other way around
function chopPrefixOffOfString($string, $prefix, $returnStringOnFailure = false) {
	chopPrefixOffString($prefix, $string, $returnStringOnFailure);
}

//Reverses encodeItemIdForOrganizer()
function decodeItemIdForOrganizer($id, $prefix = '~') {
	$len = strlen($prefix);
	if (substr($id, 0, $len) == $prefix) {
		return rawurldecode(str_replace('~', '%', substr($id, $len)));
	} else {
		return $id;
	}
}
//	function decodeItemIdForOrganizer($id) {}

//Old, deprecated alias of the above
function decodeItemIdForStorekeeper($id, $prefix = '~') {
	return decodeItemIdForOrganizer($id, $prefix);
}

function displayHTMLAsPlainText(&$text, $excerptLength = 200, $stripTitles = false, $highlightTerm = false, $excerptCutOffPhrase = '...') {
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
function encodeItemIdForOrganizer($id, $prefix = '~') {
	if (is_numeric($id)) {
		return $id;
	} else {
		return $prefix. str_replace('%', '~', str_replace('~', '%7E', rawurlencode($id)));
	}
}
//	function encodeItemIdForOrganizer($id) {}

//Old, deprecated alias of the above
function encodeItemIdForStorekeeper($id, $prefix = '~') {
	return encodeItemIdForOrganizer($id, $prefix);
}

cms_core::$whitelist[] = 'engToBoolean';
//	function engToBoolean($text) {}

//Format a date for display
//	function formatDateNicely($date, $format_type = false, $lang = false, $time_format = '', $rss = false) {}

//Format a datetime for display
//	function formatDateTimeNicely($date, $format_type = false, $lang = false, $rss = false) {}

//Format a filesize for display
//	function formatFilesizeNicely($size, $precision = 0) {}

cms_core::$whitelist[] = 'hash64';
//	function hash64($text, $len = 28) {}

//Encode a random name to something suitable for a HTML ID by replacing anything
//non-alphanumeric with an underscore
function HTMLId($text) {
	return preg_replace('/[^a-zA-Z0-9]/', '_', $text);
}

//A short function that makes code a little bit nicer to read if used
cms_core::$whitelist[] = 'ifNull';
function ifNull($a, $b, $c = null) {
	return $a? $a : ($b? $b : $c);
}

//No longer used as of 7.0.6!
function isInfoTag($tagName) {
	return false;
	//return $tagName === 'back_link'
	//	|| $tagName === 'class_name'
	//	|| $tagName === 'count'
	//	|| $tagName === 'ord';
}

function jsEscape($text) {
	return strtr(addcslashes($text, "\\\n\r\"'"), array('&' => '\\x26', '<' => '\\x3c', '>' => '\\x3e'));
}

function jsOnClickEscape($text) {
	return htmlspecialchars(addcslashes($text, "\\\n\r\"'"));
}

function urlRequest($arr, $tidyClutter = false) {
	if (!$arr) {
		return '';
	} elseif (!is_array($arr)) {
		return addAmp($arr);
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
function validateEmailAddress($email, $multiple = false) {
	$valid = false;
	
	if ($multiple) {
		$addresses = preg_split("/[\,\;]+/", $email);	
	} else {
		$addresses = array($email);
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
function validateScreenName($screenName, $allowMultilingualChars = true) {
	
	//Attempt to validate allowing UTF-8 characters through
	$invalid = $allowMultilingualChars? @preg_match('/[^\p{L}\p{M} \d\-\_]/u', $screenName) : 0;
	
	//Fall back to traditional pattern matching if that fails
	if ($invalid !== 0 && $invalid !== 1) {
		$invalid = preg_match('/[^\w \d\-\_]/u', $screenName);
	}
	
	return !$invalid;
}

function sanitiseHTML($html, $allowable_tags = '', $allowedStyles = array(), $allowedAttributes = array()) {

	$DOMDocument = new DOMDocument('1.0', 'UTF-8');
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
				
				//Always allow hrefs in links, and srcs in images
				if ($a == 'href' && $item->tagName == 'a') {
				} elseif ($a == 'src' && $item->tagName == 'img') {
				
				} elseif ($a == 'style') {
					$styles = array();
					
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

function trimNonWordCharactersUnicode($string) {
	$out = @preg_replace('/[^\p{L}\p{M}\d\-]/u', '', $string);
	
	//Fall back to traditional pattern matching if that fails
	if ($out === null) {
		$out = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
	}
	
	return $out;
}


function XMLEscape($text) {
	return str_ireplace(array("'", '&nbsp;'), array('&apos;', ' '), htmlspecialchars($text));
}
