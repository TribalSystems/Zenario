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


/*  
 *  Functions for visitor phrases
 */
 
 
 


function getNumLanguages() {
	
	if (!defined('ZENARIO_NUM_LANGUAGES')) {
		if (!empty(cms_core::$langs)) {
			define('ZENARIO_NUM_LANGUAGES', count(cms_core::$langs));
		} else {
			define('ZENARIO_NUM_LANGUAGES', selectCount('languages'));
		}
	}
	return ZENARIO_NUM_LANGUAGES;
}


//Deprecated, please use phrase() instead
function getVLPPhrase($code, $replace = false, $languageId = false, $returnFalseOnFailure = false, $moduleClass = '', $phrase = false, $altCode = false) {
	return phrase($code, $replace, $moduleClass, $languageId, 2);
}

//Replacement function for gettext()/ngettext() in our Twig frameworks
function phrase($code, $replace = false, $moduleClass = 'lookup', $languageId = false, $backtraceOffset = 1, $cli = false) {
	
	if (false === $code
	 || is_null($code)
	 || '' === ($code = trim($code))) {
		return '';
	}
	
	//The twig frameworks don't pass in the phrase class name, so we need to rememeber it using a static class variable
	if ($moduleClass === 'lookup') {
		$moduleClass = cms_core::$moduleClassNameForPhrases;
	}
	if (!$moduleClass) {
		$moduleClass = 'zenario_common_features';
	}
	
	
	//Use $languageId === true as a shortcut to the site default language
	//Otherwise if $languageId is not set, try to get language from session, or the site default if that is not set
	if ($languageId === true) {
		$languageId = cms_core::$defaultLang;
	
	} elseif (!$languageId) {
		$languageId = cms_core::$visLang ?? $_SESSION['user_lang'] ?? cms_core::$defaultLang;
			//N.b. The visitorLangId() function is inlined here in order to not create a dependancy on zenario/api/system_functions.inc.php
	}
	
	$multiLingal = getNumLanguages() > 1;
	$isCode = substr($code, 0, 1) == '_';
	$needsTranslating = $isCode || !empty(cms_core::$langs[$languageId]['translate_phrases']);
	$needsUpdate = false;
	$phrase = $code;
	
	//Phrase codes (which start with an underscore) always need to be looked up
	//Otherwise we only need to look up phrases on multi-lingual sites
	if ($multiLingal || $needsTranslating) {
		
		//Attempt to find a record of the phrase in the database
		$sql = "
			SELECT local_text, seen_in_visitor_mode, seen_at_url IS NULL
			FROM ". DB_NAME_PREFIX. "visitor_phrases
			WHERE language_id = '". sqlEscape($languageId). "'
			  AND module_class_name = '". sqlEscape($moduleClass). "'
			  AND code = '". sqlEscape($code). "'
			LIMIT 1";
	
		$result = sqlQuery($sql);
		if ($row = sqlFetchRow($result)) {
			//If we found a translation, replace the code/default text with the translation
				//Note that phrases in the default language are never actually translated,
				//we're just checking if they are there!
			if ($needsTranslating) {
				if (is_null($row[0])) {
					$phrase = $code;
					if (!$cli && checkPriv()) {
						$phrase .= ' (untranslated)';
					}
				} else {
					$phrase = $row[0];
				}
			}
			
			//If we've never recorded a URL for this phrase before, we need to note it down
			if ($row[2]) {
				$needsUpdate = true;
			
			//If this is the first time we've seen this phrase in visitor mode, note it down
			} elseif (!$row[1] && ($cli || !checkPriv())) {
				$needsUpdate = true;
			}
		
		} else {
			//If we didn't find a translation that we needed, complain about it
			if ($needsTranslating) {
				$phrase = $code;
				if (!$cli && checkPriv()) {
					$phrase .= ' (untranslated)';
				}
			}
			
			//For multilingal sites, any phrases that are not in the database need to be noted down
			if ($multiLingal
			 && (cms_core::$defaultLang == $languageId
			  || !checkRowExists(
					'visitor_phrases',
					array(
						'language_id' => cms_core::$defaultLang,
						'module_class_name' => $moduleClass,
						'code' => $code)
			))) {
				$needsUpdate = true;
			}
		}
		
		//Make sure that this phrase is registered in the database
		if ($needsUpdate
			//Never register a phrase if this a plugin preview!
		 && empty($_REQUEST['fakeLayout'])
		 && empty($_REQUEST['grid_columns'])
		 && empty($_REQUEST['grid_container'])
		 && empty($_REQUEST['grid_pxWidth'])) {
			
			//Attempt to log the filename that this phrase appeared in by checking debug backtrace
			if (is_string($backtraceOffset)) {
				$filename = $backtraceOffset;
			
			} else {
				$filename = '';
			
				if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
					$back = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
				} elseif (version_compare(PHP_VERSION, '5.3.6', '>=')) {
					$back = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				} else {
					$back = debug_backtrace(false);
				}
			
				if (!empty($back[$backtraceOffset]['file'])) {
					//Strip off the CMS root
					$filename = str_replace('$'. CMS_ROOT, '', '$'. $back[$backtraceOffset]['file']);
				
					//If this looks like it was in a framework, try to overwrite this with the path to the
					//source of the framework file
					if (cms_core::$frameworkFile
					 && ($filename == 'zenario/api/module_api.inc.php'
					  || substr($filename, 0, 17) == 'cache/frameworks/'
					)) {
						$filename = cms_core::$frameworkFile;
					}
				}
			}
			
			
			//Unless we're running from the command line, attempt to get a URL for this page
			$url = null;
			if (!$cli) {
				//If it looks like this is an AJAX request or something like that,
				//then rather than report an actual URL we'll try and generate a link with the same GET requests
				if (!empty($_REQUEST['method_call'])
				 && !empty($_REQUEST['cType'])
				 && !empty($_REQUEST['cID'])) {
					$requests = $_GET;
					unset($requests['cID']);
					unset($requests['cType']);
					unset($requests['method_call']);
					unset($requests['instanceId']);
					unset($requests['slotName']);
				
					$url = linkToItem($_REQUEST['cID'], $_REQUEST['cType'], 'never', $requests);
			
				//Otherwise report the URL as it is
				} elseif (!empty($_SERVER['REQUEST_URI'])) {
					$url = $_SERVER['REQUEST_URI'];
				
					//Try to remove the SUBDIRECTORY from the start of the URL
					if ($url != SUBDIRECTORY) {
						$url = chopPrefixOffString(SUBDIRECTORY, $url, true);
					}
				}
			
				if (!is_null($url)) {
					$url = substr($url, 0, 0xffff);
				}
			}
			
			setRow(
				'visitor_phrases',
				array(
					'seen_in_visitor_mode' => (!$cli && checkPriv())? 0 : 1,
					'seen_in_file' => substr($filename, 0, 0xff),
					'seen_at_url' => $url),
				array(
					'language_id' => cms_core::$defaultLang,
					'module_class_name' => $moduleClass,
					'code' => $code),
				
				//Don't clear the cache for this update
				false, false, false, true, $checkCache = false);
			
			//For multilingual sites, we need to note down this information against
			//the current language as well, to
			//fix a bug where missing phrases would continously clear the cache.
			if (cms_core::$defaultLang != $languageId) {
				setRow(
					'visitor_phrases',
					array(
						'seen_in_visitor_mode' => (!$cli && checkPriv())? 0 : 1,
						'seen_in_file' => '-',
						'seen_at_url' => '-'),
					array(
						'language_id' => $languageId,
						'module_class_name' => $moduleClass,
						'code' => $code),
					
					//Don't clear the cache for this update
					false, false, false, true, $checkCache = false);
			}
		}
	}
	
	
	//Replace merge fields in the phrase
	if (!empty($replace) && is_array($replace)) {
	    applyMergeFields($phrase, $replace);
	}
	
	return $phrase;
}

function applyMergeFields(&$phrase, $mrg) {
	if (is_array($mrg)) {
		foreach ($mrg as $key => $value) {
			$phrase = str_replace(array('[['. $key. ']]', '{{'. $key. '}}'), $value, $phrase);
		}
	}
}

function nphrase($text, $pluralText = false, $n = 1, $replace = array(), $moduleClass = 'lookup', $languageId = false, $backtraceOffset = 1, $cli = false) {
	
	//Allow the caller to enter the name of a merge field that contains $n
	if (is_string($n) && !is_numeric($n) && isset($replace[$n])) {
		$n = $replace[$n];
	} else {
		if (!is_array($replace)) {
			$replace = array();
		}
		if (!isset($replace['count'])) {
			$replace['count'] = $n;
		}
	}
	
	if ($pluralText !== false && $n !== 1 && $n !== '1') {
		return phrase($pluralText, $replace, $moduleClass, $languageId, $backtraceOffset + 1, $cli);
	} else {
		return phrase($text, $replace, $moduleClass, $languageId, $backtraceOffset + 1, $cli);
	}
}