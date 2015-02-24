<?php
/*
 * Copyright (c) 2015, Tribal Limited
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
	if ($request != '' && substr($request, 0, 1) != '&') {
		return '&'. $request;
	} else {
		return $request;
	}
}

function chopPrefixOffOfString($string, $prefix) {
	$len = strlen($prefix);
	
	if (substr($string, 0, $len) == $prefix) {
		return substr($string, $len);
	} else {
		return false;
	}
}

//Reverses encodeItemIdForStorekeeper()
function decodeItemIdForStorekeeper($id, $prefix = '~') {
	$len = strlen($prefix);
	if (substr($id, 0, $len) == $prefix) {
		return rawurldecode(str_replace('~', '%', substr($id, $len)));
	} else {
		return $id;
	}
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
function encodeItemIdForStorekeeper($id, $prefix = '~') {
	if (is_numeric($id)) {
		return $id;
	} else {
		return $prefix. str_replace('%', '~', str_replace('~', '%7E', rawurlencode($id)));
	}
}

//	function engToBoolean($text) {}

//Format a date for display
//	function formatDateNicely($date, $format_type = false, $lang = false, $time_format = '', $rss = false) {}

//Format a datetime for display
//	function formatDateTimeNicely($date, $format_type = false, $lang = false, $rss = false) {}

//Format a filesize for display
//	function formatFilesizeNicely($size, $precision = 0) {}

//	function hash64($text, $len = 28) {}

//Encode a random name to something suitable for a HTML ID by replacing anything
//non-alphanumeric with an underscore
function HTMLId($text) {
	return preg_replace('/[^a-zA-Z0-9]/', '_', $text);
}

//A short function that makes code a little bit nicer to read if used
function ifNull($a, $b, $c = null) {
	return $a? $a : ($b? $b : $c);
}

function isInfoTag($tagName) {
	return $tagName === 'back_link'
		|| $tagName === 'class_name'
		|| $tagName === 'count'
		|| $tagName === 'ord';
}

function jsEscape($text) {
	return strtr(addcslashes($text, "\\\n\r\"'"), array('&' => '\\x26', '<' => '\\x3c', '>' => '\\x3e'));
}

function jsOnClickEscape($text) {
	return htmlspecialchars(addcslashes($text, "\\\n\r\"'"));
}

function urlRequest($arr) {
	if (!$arr) {
		return '';
	} elseif (!is_array($arr)) {
		return addAmp($arr);
	}
	
	$request = '';
	foreach ($arr as $name => &$value) {
		$request .= '&'. rawurlencode($name). '=';
		
		if ($value !== false && $value !== null) {
			$request .= rawurlencode($value);
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
function validateScreenName($screenName) {
	//Attempt to validate allowing UTF-8 characters through
	$invalid = @preg_match('/[^\p{L}\p{M} \d\-\_]/u', $screenName);
	
	//Fall back to traditional pattern matching if that fails
	if ($invalid !== 0 && $invalid !== 1) {
		$invalid = preg_match('/[^\w \d\-\_]/u', $screenName);
	}
	
	return !$invalid;
}


function XMLEscape($text) {
	return str_ireplace(array("'", '&nbsp;'), array('&apos;', ' '), htmlspecialchars($text));
}
