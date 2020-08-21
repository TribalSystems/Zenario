<?php
/*
 * Copyright (c) 2020, Tribal Limited
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

class lang {
	
	private static $numLangs = null;

	//Formerly "getNumLanguages()"
	public static function count() {
	
		if (self::$numLangs === null) {
			if (!empty(\ze::$langs)) {
				self::$numLangs = count(\ze::$langs);
			} else {
				self::$numLangs = \ze\row::count('languages');
			}
		}
		return self::$numLangs;
	}
	
	
	public static function replacePhraseCodesInString(&$string, $moduleClass = 'zenario_common_features', $languageId = false, $backtraceOffset = 2, $cli = false) {
		
		if (!is_string($string)) {
			return;
		}
		
		$content = preg_split('/\[\[([^\[\]]+)\]\]/', $string, -1,  PREG_SPLIT_DELIM_CAPTURE);
		$string = '';
		
		foreach ($content as $i => $part) {
			if ($i % 2 === 1) {
				$string .= \ze\lang::phrase($part, false, $moduleClass, $languageId, $backtraceOffset, $cli);
			} else {
				$string .= $part;
			}
		}
	}


	const phraseFromTwig = true;
	//Replacement function for gettext()/ngettext() in our Twig frameworks
	//Formerly "phrase()"
	public static function phrase($code, $replace = false, $moduleClass = 'zenario_common_features', $languageId = false, $backtraceOffset = 1, $cli = false) {
	
		if (false === $code
		 || $code === null
		 || '' === ($code = trim($code))) {
			return '';
		}
		
		if ($moduleClass === false) {
			return \ze\admin::phrase($code, $replace);
		}
	
	
		//Use $languageId === true as a shortcut to the site default language
		//Otherwise if $languageId is not set, try to get language from session, or the site default if that is not set
		if ($languageId === true) {
			$languageId = \ze::$defaultLang;
	
		} elseif (!$languageId) {
			$languageId = \ze::$visLang ?? $_SESSION['user_lang'] ?? \ze::$defaultLang;
				//N.b. The \ze\content::visitorLangId() function is inlined here in order to not create a dependancy on another library
		}
	
		$isCode = substr($code, 0, 1) == '_';
		$needsTranslating = $isCode || !empty(\ze::$langs[$languageId]['translate_phrases']);
		$needsUpdate = false;
		$phrase = $code;
	
		//Phrase codes (which start with an underscore) always need to be looked up
		//Otherwise we only need to look up phrases on multi-lingual sites
		if (\ze::$trackPhrases || $needsTranslating) {
		
			//Attempt to find a record of the phrase in the database
			$sql = "
				SELECT local_text, seen_in_visitor_mode, seen_at_url IS NULL
				FROM ". DB_PREFIX. "visitor_phrases
				WHERE language_id = '". \ze\escape::sql($languageId). "'
				  AND module_class_name = '". \ze\escape::sql($moduleClass). "'
				  AND code = '". \ze\escape::sql($code). "'
				LIMIT 1";
	
			$result = \ze\sql::select($sql);
			if ($row = \ze\sql::fetchRow($result)) {
				//If we found a translation, replace the code/default text with the translation
					//Note that phrases in the default language are never actually translated,
					//we're just checking if they are there!
				if ($needsTranslating) {
					if (is_null($row[0])) {
						$phrase = $code;
						if (!$cli && \ze\priv::check()) {
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
				} elseif (!$row[1] && ($cli || !\ze\priv::check())) {
					$needsUpdate = true;
				}
		
			} else {
				//If we didn't find a translation that we needed, complain about it
				if ($needsTranslating) {
					$phrase = $code;
					if (!$cli && \ze\priv::check()) {
						$phrase .= ' (untranslated)';
					}
				}
			
				//For multilingal sites, any phrases that are not in the database need to be noted down
				if (\ze::$trackPhrases
				 && (\ze::$defaultLang == $languageId
				  || !\ze\row::exists(
						'visitor_phrases',
						[
							'language_id' => \ze::$defaultLang,
							'module_class_name' => $moduleClass,
							'code' => $code]
				))) {
					$needsUpdate = true;
				}
			}
		
			//Make sure that this phrase is registered in the database
			if ($needsUpdate
			 && \ze::$trackPhrases
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
			
					$back = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
			
					if (!empty($back[$backtraceOffset]['file'])) {
						//Strip off the CMS root
						$filename = str_replace('$'. CMS_ROOT, '', '$'. $back[$backtraceOffset]['file']);
						
						//If this looks like it was in a framework, try to overwrite this with the path to the
						//source of the framework file
						if (\ze::$frameworkFile
						 && ($filename == 'zenario/autoload/moduleBaseClass.php'
						  || \ze\ring::chopPrefix('cache/frameworks/', $filename)
						  || \ze\ring::chopPrefix('zenario/libs/composer_dist/twig/', $filename)
						  || \ze\ring::chopPrefix('zenario/libs/composer_no_dist/twig/', $filename)
						)) {
							$filename = \ze::$frameworkFile;
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
				
						$url = \ze\link::toItem($_REQUEST['cID'], $_REQUEST['cType'], 'never', $requests);
			
					//Otherwise report the URL as it is
					} elseif (!empty($_SERVER['REQUEST_URI'])) {
						$url = $_SERVER['REQUEST_URI'];
				
						//Try to remove the SUBDIRECTORY from the start of the URL
						if ($url != SUBDIRECTORY) {
							$url = \ze\ring::chopPrefix(SUBDIRECTORY, $url, true);
						}
					}
			
					if ($url !== null) {
						$url = substr($url, 0, 0xffff);
					}
				}
			
				\ze\row::set(
					'visitor_phrases',
					[
						'seen_in_visitor_mode' => (!$cli && \ze\priv::check())? 0 : 1,
						'seen_in_file' => substr($filename, 0, 0xff),
						'seen_at_url' => $url],
					[
						'language_id' => \ze::$defaultLang,
						'module_class_name' => $moduleClass,
						'code' => $code],
				
					//Don't clear the cache for this update
					false, false, false, true, $checkCache = false);
			
				//For multilingual sites, we need to note down this information against
				//the current language as well, to
				//fix a bug where missing phrases would continously clear the cache.
				if (\ze::$defaultLang != $languageId) {
					\ze\row::set(
						'visitor_phrases',
						[
							'seen_in_visitor_mode' => (!$cli && \ze\priv::check())? 0 : 1,
							'seen_in_file' => '-',
							'seen_at_url' => '-'],
						[
							'language_id' => $languageId,
							'module_class_name' => $moduleClass,
							'code' => $code],
					
						//Don't clear the cache for this update
						false, false, false, true, $checkCache = false);
				}
			}
		}
	
	
		//Replace merge fields in the phrase
		if (!empty($replace) && is_array($replace)) {
			\ze\lang::applyMergeFields($phrase, $replace);
		}
	
		return $phrase;
	}

	//Formerly "applyMergeFields()"
	public static function applyMergeFields(&$string, $replace, $open = '[[', $close = ']]', $autoHTMLEscape = false) {
		
		//If dataset fields are processed, an array with the label and an ordinal might be passed instead of a label string.
		//The code below will account for that.
		if (is_array($string)) {
			$content = explode($open, $string['label']);
		} else {
			$content = explode($open, $string);
		}
		
		$newString = '';
		$first = true;
		
		foreach ($content as &$str) {
			if ($first) {
				$first = false;
			
			} elseif (false !== ($sbe = strpos($str, $close))) {
				$mf = substr($str, 0, $sbe);
				$str = substr($str, $sbe + 2);
				
				if ($autoHTMLEscape) {
					$newString .= htmlspecialchars($replace[$mf] ?? '');
				
				} else {
					do {
						//Look out for Twig-style flags at the end of the phrase code's name
						if (false !== ($pos = strpos($mf, '|'))) {
							$filter = trim(substr($mf, $pos + 1));
							$mf = trim(substr($mf, 0, $pos));
					
							switch ($filter) {
								case 'e':
								case 'escape':
									//html escaping anything using the "escape" flag
									$newString .= htmlspecialchars($replace[$mf] ?? '');
									break 2;
							}
						}
					
						$newString .= $replace[$mf] ?? '';
					} while (false);
				}
			}
			
			$newString .= $str;
			
			if (is_array($string)) {
				$string['label'] = $newString;
			} else {
				$string = $newString;
			}
		}
	}

	const nphraseFromTwig = true;
	//Formerly "nphrase()"
	public static function nphrase($text, $pluralText = false, $n = 1, $replace = [], $moduleClass = 'zenario_common_features', $languageId = false, $backtraceOffset = 1, $cli = false, $zeroText = null) {
	
		//Allow the caller to enter the name of a merge field that contains $n
		if (is_string($n) && !is_numeric($n) && isset($replace[$n])) {
			$n = $replace[$n];
		} else {
			if (!is_array($replace)) {
				$replace = [];
			}
			if (!isset($replace['count'])) {
				$replace['count'] = $n;
			}
		}
		
		if ($zeroText !== null && empty($n)) {
			return \ze\lang::phrase($zeroText, $replace, $moduleClass, $languageId, $backtraceOffset + 1, $cli);
		} else if ($pluralText !== false && $n !== 1 && $n !== '1') {
			return \ze\lang::phrase($pluralText, $replace, $moduleClass, $languageId, $backtraceOffset + 1, $cli);
		} else {
			return \ze\lang::phrase($text, $replace, $moduleClass, $languageId, $backtraceOffset + 1, $cli);
		}
	}
	
	const nzphraseFromTwig = true;
	public static function nzphrase($zeroText, $text, $pluralText = false, $n = 1, $replace = [], $moduleClass = 'zenario_common_features', $languageId = false, $backtraceOffset = 1, $cli = false) {
		return \ze\lang::nphrase($text, $pluralText, $n, $replace, $moduleClass, $languageId, $backtraceOffset + 1, $cli, $zeroText);
	}


	//Formerly "formatFilesizeNicely()"
	public static function formatFilesizeNicely($size, $precision = 0, $adminMode = false, $vlpClass = '') {
	
		if (is_array($size)) {
			$size = $size['size'];
		}
	
		//Return 0 without formating if the size is 0.
		if ($size <= 0) {
			return '0';
		}
	
		//Define labels to use
		$labels = ['[[size]] Bytes', '[[size]] KB', '[[size]] MB', '[[size]] GB', '[[size]] TB'];
	
		//Work out which of the labels to use, based on how many powers of 1024 go into the size, and
		//how many labels we have
		$order = min(
					floor(
						log($size) / log(1024)
					),
				  count($labels)-1);
	
		$mrg = 
			['size' => 
				round($size / pow(1024, $order), $precision)
			];
	
		if ($adminMode) {
			return \ze\admin::phrase($labels[$order], $mrg);
		} else {
			return \ze\lang::phrase($labels[$order], $mrg, $vlpClass);
		}
	}

	//Formerly "formatFileTypeNicely()"
	public static function formatFileTypeNicely($type, $vlpClass = '') {
		switch($type) {
			case 'image/jpeg': 
				$new_type = \ze\lang::phrase('_JPEG_file', false, $vlpClass);
				break;
			case 'image/pjpeg': 
				$new_type = \ze\lang::phrase('_JPEG_file', false, $vlpClass);
				break;
			case 'image/jpg': 
				$new_type = \ze\lang::phrase('_JPG_file', false, $vlpClass);
				break;
			case 'image/gif': 
				$new_type = \ze\lang::phrase('_GIF_file', false, $vlpClass);
				break;
			case 'image/png': 
				$new_type = \ze\lang::phrase('_PNG_file', false, $vlpClass);
				break;
			default:
				$new_type = \ze\lang::phrase('_UNDEFINED', false, $vlpClass);
		}
		return $new_type;
	}







	//Formerly "getLanguages()"
	public static function getLanguages($includeAllLanguages = false, $orderByEnglishName = false, $defaultLangFirst = false) {
		
		$sql = "
			SELECT
				l.id,
				IFNULL(en.local_text, lo.local_text) AS english_name,
				IFNULL(lo.local_text, en.local_text) AS language_local_name,
				IFNULL(f.local_text, 'white') as flag,
				detect,
				translate_phrases,
				sync_assist,
				search_type,
				language_picker_logic";
	
		if ($includeAllLanguages) {
			$sql .= "
				FROM (
					SELECT DISTINCT language_id AS id
					FROM ". DB_PREFIX. "visitor_phrases
				) AS l
				LEFT JOIN ". DB_PREFIX. "languages el
				   ON l.id = el.id";
	
		} else {
			$sql .= "
				FROM ". DB_PREFIX. "languages AS l";
		}
	
		$sql .= "
			LEFT JOIN ". DB_PREFIX. "visitor_phrases AS en
			   ON en.module_class_name = 'zenario_common_features'
			  AND en.language_id = l.id
			  AND en.code = '__LANGUAGE_ENGLISH_NAME__'
			LEFT JOIN ". DB_PREFIX. "visitor_phrases AS lo
			   ON lo.module_class_name = 'zenario_common_features'
			  AND lo.language_id = l.id
			  AND lo.code = '__LANGUAGE_LOCAL_NAME__'
			LEFT JOIN ". DB_PREFIX. "visitor_phrases AS f
			   ON f.module_class_name = 'zenario_common_features'
			  AND f.language_id = l.id
			  AND f.code = '__LANGUAGE_FLAG_FILENAME__'
			ORDER BY ";
	
		if ($defaultLangFirst) {
			$sql .= "l.id = '". \ze\escape::sql(\ze::$defaultLang). "' DESC, ";
		}
	
		if ($orderByEnglishName) {
			$sql .= "IFNULL(en.local_text, lo.local_text)";
		} else {
			$sql .= "l.id";
		}
	
		$result = \ze\sql::select($sql);
		$langs = [];
		while ($row = \ze\sql::fetchAssoc($result)) {
			$langs[$row['id']] = $row;
		}
	
		return $langs;
	}

	//Formerly "getLanguageName()"
	public static function name($languageId = false, $addIdInBracketsToEnd = true, $returnIdOnFailure = true, $localName = false) {
	
		if ($languageId === false) {
			$languageId = (\ze::$visLang ?: \ze::$defaultLang);
		}
	
		$name = \ze\row::get('visitor_phrases', 'local_text', ['code' => '__LANGUAGE_ENGLISH_NAME__', 'language_id' => $languageId, 'module_class_name' => 'zenario_common_features']);
	
		if ($name !== false) {
			if ($addIdInBracketsToEnd) {
				return $name. ' ('. $languageId. ')';
			} else {
				return $name;
			}
		} elseif ($returnIdOnFailure) {
			return $languageId;
		} else {
			return false;
		}

	}

	//Formerly "getLanguageLocalName()"
	public static function localName($languageId = false) {
		return \ze\lang::name($languageId, false, false, true);
	}


}