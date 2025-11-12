<?php
/*
 * Copyright (c) 2025, Tribal Limited
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
	
	
	public static function replacePhraseCodesInString(&$string, $moduleClass = 'zenario_common_features', $languageId = false) {
		
		if (!is_string($string)) {
			return;
		}
		
		$content = preg_split('/\[\[([^\[\]]+)\]\]/', $string, -1,  PREG_SPLIT_DELIM_CAPTURE);
		$string = '';
		
		foreach ($content as $i => $part) {
			if ($i % 2 === 1) {
				$string .= \ze\lang::phrase($part, false, $moduleClass, $languageId);
			} else {
				$string .= $part;
			}
		}
	}
	
	
	//Shortcut function to calling the phrase() function with the $isHTML option set
	public static function htmlPhrase($html, $replace = false, $moduleClass = 'zenario_common_features', $languageId = false) {
		return \ze\lang::phrase($html, $replace, $moduleClass, $languageId, true);
	}


	const phraseFromTwig = true;
	//Replacement function for gettext()/ngettext() in our Twig frameworks
	public static function phrase($code, $replace = false, $moduleClass = 'zenario_common_features', $languageId = false, $isHTML = false) {
	
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
		$phrase = $code;
		
		$needsInsert = false;
		$needsUpdate = false;
		$neverSeenByVisitorBefore = false;
		$neverSeenOnContentItemBefore = false;
		$seenAtCID = null;
		$seenAtCType = null;
		
		
		
		// Functionality for a "phase debug mode" prototype.
		// Currently not in use/implemented.
		#if (...) {
		#	$phrase = \ze\phraseAdm::debugText($phrase, $isHTML);
		#} else
	
		//Phrase codes (which start with an underscore) always need to be looked up
		//Otherwise we only need to look up phrases on multi-lingual sites
		if (\ze::$trackPhrases || $needsTranslating) {
			
			global $argc;
			$isFromCommandLine = !empty($argc);
			
			//Attempt to find a record of the phrase in the database
			$sql = "
				SELECT local_text, seen_in_visitor_mode, seen_at_content_id IS NULL, is_html, `archived`
				FROM ". DB_PREFIX. "visitor_phrases
				WHERE language_id = ?
				  AND module_class_name = ?
				  AND code = ?
				LIMIT 1";
			
			$statement = \ze\sql::prepare($sql, 'aas');
			$row = $statement->fetchRow([$languageId, $moduleClass, $code]);
			
			if ($row) {
				//If we found a translation, replace the code/default text with the translation
					//Note that phrases in the default language are never actually translated,
					//we're just checking if they are there!
				if ($needsTranslating) {
					if (is_null($row[0])) {
						$phrase = $code;
						
						if (!$isFromCommandLine && \ze::isAdmin()) {
							$phrase .= ' (untranslated)';
						}
					} else {
						$phrase = $row[0];
					}
				}
			
				//If this is the first time we've seen this phrase in visitor mode, note it down
				if (!$row[1] && ($isFromCommandLine || !\ze::isAdmin())) {
					$needsUpdate = true;
					$neverSeenByVisitorBefore = true;
				}
			
				//If we've never recorded a URL for this phrase before, we need to note it down
				if ($row[2] && !$isFromCommandLine) {
					if (\ze\lang::getCurrentContentItem($seenAtCID, $seenAtCType)) {
						$needsUpdate = true;
						$neverSeenOnContentItemBefore = true;
					}
				}
				
				//Catch the case where the isHTML flag has been changed by a dev, we need to update this
				if ($isHTML != ((bool) $row[3])) {
					$needsUpdate = true;
				}
				
				//Catch the case where we've found a phrase that we thought was archived
				if ((bool) $row[4]) {
					$needsUpdate = true;
					
					//Restoring a phrase from archive should also update the first seen date.
					$neverSeenByVisitorBefore = true;
				}
		
			} else {
				//If we didn't find a translation that we needed, complain about it
				if ($needsTranslating) {
					$phrase = $code;
					if (!$isFromCommandLine && \ze::isAdmin()) {
						$phrase .= ' (untranslated)';
					}
				}
			
				//For multilingal sites, check if the phrase is at least recorded as existing in the
				//default language.
				if (\ze::$trackPhrases) {
					//Catch the case where this is the default language and we already looked for it above.
					if (\ze::$defaultLang == $languageId) {
						//In this case we already know it's missing without needing another check in the DB.
						$needsInsert = true;
					
					//Otherwise we'll need to do another query to check to see if the row is
					//already recorded in the default language already.
					} else {
						$row = \ze\row::get('visitor_phrases', ['archived'], [
							'language_id' => \ze::$defaultLang,
							'module_class_name' => $moduleClass,
							'code' => $code
						]);
						
						//If not, it needs adding
						if (!$row) {
							$needsInsert = true;
						
						//Catch the case where it's there, but archived.
						} elseif ($row['archived']) {
							//We'll need to update it to remove the archive flag in this situation.
							$needsUpdate = true;
							
							//Restoring a phrase from archive should also update the first seen date.
							$neverSeenByVisitorBefore = true;
						}
					}
				}
			}
		
			//Make sure that this phrase is registered in the database
			if (($needsInsert || $needsUpdate) && \ze::$trackPhrases
				//Never register a phrase if this is a plugin preview!
			 && empty($_REQUEST['fakeLayout'])
			 && empty($_REQUEST['grid_columns'])
			 && empty($_REQUEST['grid_container'])
			 && empty($_REQUEST['grid_pxWidth'])) {
			
			
				//Unless we're running from the command line, attempt to get a URL for this page
				$url = null;
				if (!$isFromCommandLine) {
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
				
				
				//Collect the details that this phrase should have
				$details = [];
				$details['is_html'] = $isHTML;
				$details['archived'] = 0;
				
				if ($neverSeenByVisitorBefore) {
					$details['seen_in_visitor_mode'] = 1;
					$details['first_seen_by_visitor'] = \ze\date::now(true);
				}
				
				if ($neverSeenOnContentItemBefore) {
					$details['seen_at_content_id'] = $seenAtCID;
					$details['seen_at_content_type'] = $seenAtCType;
				}
				
				$key = [
					'module_class_name' => $moduleClass,
					'code' => $code
				];
				
				
				//If we want to update the flags for an existing phrase, we should
				//update them on every translation of that phrase to make sure they are kept in sync.
				if ($needsUpdate) {	
					//Don't clear the cache for this update
					\ze\row::cacheFriendlyUpdate(
						'visitor_phrases',
						$details,
						$key
					);
				}
				
				
				//If we've found a phrase we've never seen before, note down that it exists in the
				//default language.
				if ($needsInsert) {
					$key['language_id'] = \ze::$defaultLang;
					
					//Don't clear the cache for this update
					\ze\row::cacheFriendlySet(
						'visitor_phrases',
						$details,
						$key
					);
				}
			}
		}
	
		//Replace merge fields in the phrase
		if (!empty($replace) && is_array($replace)) {
			\ze\lang::applyMergeFields($phrase, $replace);
		}
	
		return $phrase;
	}

	public static function applyMergeFields(&$string, $replace, $open = '[[', $close = ']]', $autoHTMLEscape = false, $showTheWordEmptyInsteadOfBlanks = false) {
		
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
					if (isset($replace[$mf])) {
						$replaceWith = $replace[$mf];
					} else {
						if ($showTheWordEmptyInsteadOfBlanks) {
							$replaceWith = \ze\lang::phrase('(empty)');
						} else {
							$replaceWith = '';
						}
					}
					
					$newString .= htmlspecialchars($replaceWith);
				
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
					
						if (isset($replace[$mf])) {
							$replaceWith = $replace[$mf];
						} else {
							if ($showTheWordEmptyInsteadOfBlanks) {
								$replaceWith = \ze\lang::phrase('(empty)');
							} else {
								$replaceWith = '';
							}
						}
						
						$newString .= $replaceWith;
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
	public static function nPhrase($text, $pluralText = false, $n = 1, $replace = [], $moduleClass = 'zenario_common_features', $languageId = false, $zeroText = null) {
	
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
			return \ze\lang::phrase($zeroText, $replace, $moduleClass, $languageId);
		} else if ($pluralText !== false && $n !== 1 && $n !== '1') {
			return \ze\lang::phrase($pluralText, $replace, $moduleClass, $languageId);
		} else {
			return \ze\lang::phrase($text, $replace, $moduleClass, $languageId);
		}
	}
	
	const nzphraseFromTwig = true;
	public static function nzPhrase($zeroText, $text, $pluralText = false, $n = 1, $replace = [], $moduleClass = 'zenario_common_features', $languageId = false) {
		return \ze\lang::nPhrase($text, $pluralText, $n, $replace, $moduleClass, $languageId, $zeroText);
	}
	
	const phraseInHTMLFromTwig = true;
	public static function phraseInHTML($code, $replace = false, $moduleClass = 'zenario_common_features', $languageId = false) {
		$text = \ze\lang::phrase($code, $replace, $moduleClass, $languageId);
		if (is_string($text)) {
			$text = htmlspecialchars($text);
		}
		return $text;
	}
	
	const nPhraseInHTMLFromTwig = true;
	public static function nPhraseInHTML($text, $pluralText = false, $n = 1, $replace = [], $moduleClass = 'zenario_common_features', $languageId = false, $zeroText = null) {
		$text = \ze\lang::nPhraseInHTML($text, $pluralText, $n, $replace, $moduleClass, $languageId, $zeroText);
		if (is_string($text)) {
			$text = htmlspecialchars($text);
		}
		return $text;
	}
	
	const nzphraseInHTMLFromTwig = true;
	public static function nzPhraseInHTML($zeroText, $text, $pluralText = false, $n = 1, $replace = [], $moduleClass = 'zenario_common_features', $languageId = false) {
		return \ze\lang::nPhraseInHTML($text, $pluralText, $n, $replace, $moduleClass, $languageId, $zeroText);
	}
	
	const monthPhraseFromTwig = true;
	public static function monthPhrase($code, $i, $languageId = false) {
		return \ze\lang::phrase($code. str_pad($i, 2, '0', STR_PAD_LEFT), false, 'zenario_common_features', $languageId);
	}

	const dayPhraseFromTwig = true;
	public static function dayPhrase($code, $i, $languageId = false) {
		return \ze\lang::phrase($code. $i, false, 'zenario_common_features', $languageId);
	}
	
	
	//Try and check if we know what content item we're translating phrases for
	public static function getCurrentContentItem(&$seenAtCID, &$seenAtCType) {
		
		//If the phrase is actually on a page, we can just record the cID and cType
		if (!empty(\ze::$cID)) {
			$seenAtCID = \ze::$cID;
			$seenAtCType = \ze::$cType;
			return true;
		}
		
		//For AJAX requests, try and check the referer
		if (!isset($_SERVER['HTTP_REFERER'])) {
			return false;
		}
		$parsedURL = parse_url($_SERVER['HTTP_REFERER']);
		
		//This logic checks to see if this was a URL in the form "index.php?cID=123", and tries to parse it to get the content item.
		if (!empty($parsedURL['query'])) {
			$parsedQuery = [];
			parse_str($parsedURL['query'] ?? '', $parsedQuery);
			
			if (!empty($parsedQuery)) {
				$cID = $cType = $redirectNeeded = $aliasInURL = $langIdInURL = false;
				\ze\content::resolveFromRequest($cID, $cType, $redirectNeeded, $aliasInURL, $langIdInURL, $parsedQuery, $parsedQuery, []);
				
				if ($cID) {
					$seenAtCID = $cID;
					$seenAtCType = $cType;
					return true;
				}
			}
		}
			
		//This logic checks to see if this was a friendly URL in the form "/alias" or "/alias.html", and tries to parse it to get the content item.
		if (!empty($parsedURL['path'])) {
			
			$pathParts = \ze\ray::explodeAndTrim($parsedURL['path'], false, '/');
			
			if (!empty($pathParts)) {
				$alias = array_pop($pathParts);
				$aliasParts = \ze\ray::explodeAndTrim($alias, false, '.');
				
				if (!empty($aliasParts)) {
					$alias = array_shift($aliasParts);
					$parsedQuery = ['cID' => $alias];
					
					$cID = $cType = $redirectNeeded = $aliasInURL = $langIdInURL = false;
					\ze\content::resolveFromRequest($cID, $cType, $redirectNeeded, $aliasInURL, $langIdInURL, $parsedQuery, $parsedQuery, []);
				
					if ($cID) {
						$seenAtCID = $cID;
						$seenAtCType = $cType;
						return true;
					}
				}
			}
		}
		
		return false;
	}


	public static function formatFileTypeNicely($type, $vlpClass = 'zenario_common_features') {
		switch($type) {
			case 'image/webp': 
				//Note by Chris:
				//This seems to be a rare function where we still use phrase codes, not English base-phrases.
				//No plans to change any existing phrases/break any existing translations, but I don't want to
				//create any new phrase codes, so the new addition I've added here uses an English base-phrase.
				$new_type = \ze\lang::phrase('WebP file', false, $vlpClass);
				break;
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







	public static function getLanguages($includeAllLanguages = false, $orderByEnglishName = false, $defaultLangFirst = false) {
		
		$sql = "
			SELECT";
	
		if ($includeAllLanguages) {
			$sql .= "
				vp.language_id AS id";
	
		} else {
			$sql .= "
				l.id";
		}
	
		$sql .= ",
				IFNULL(en.local_text, lo.local_text) AS english_name,
				IFNULL(lo.local_text, en.local_text) AS language_local_name,
				IFNULL(f.local_text, 'white') as flag,
				detect,
				translate_phrases,
				search_type,
				language_picker_logic";
	
		if ($includeAllLanguages) {
			$sql .= "
				FROM (
					SELECT DISTINCT language_id
					FROM ". DB_PREFIX. "visitor_phrases
				) AS vp
				LEFT JOIN ". DB_PREFIX. "languages AS l
				   ON l.id = vp.language_id";
	
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
			$sql .= "l.id = '". \ze\escape::asciiInSQL(\ze::$defaultLang). "' DESC, ";
		}
	
		if ($orderByEnglishName) {
			$sql .= "IFNULL(en.local_text, lo.local_text)";
		} else {
			$sql .= "l.id";
		}
		
		$langs = [];
		foreach (\ze\sql::select($sql) as $row) {
			$langs[$row['id']] = $row;
		}
	
		return $langs;
	}

	public static function name($languageId = false, $addIdInBracketsToEnd = true, $returnIdOnFailure = true, $localName = false) {
	
		if ($languageId === false) {
			$languageId = (\ze::$visLang ?: \ze::$defaultLang);
		}
	
		if ($localName) {
			$code = '__LANGUAGE_LOCAL_NAME__';
		} else {
			$code = '__LANGUAGE_ENGLISH_NAME__';
		}
		
		$name = \ze\row::get('visitor_phrases', 'local_text', ['code' => $code, 'language_id' => $languageId, 'module_class_name' => 'zenario_common_features']);
	
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

	public static function localName($languageId = false) {
		return \ze\lang::name($languageId, false, false, true);
	}
	
	//If the current visitor speaks English, return a language name in English.
	//Otherwise return a language name in its local tongue.
	const appropriateNameFromTwig = true;
	public static function appropriateName($languageId) {
		if (\ze::$visLang
		 && \ze::$visLang != 'en'
		 && substr(\ze::$visLang, 0, 3) != 'en-') {
			return \ze\lang::name($languageId, false, true, true);
		} else {
			return \ze\lang::name($languageId, false, true, false);
		}
	}

	public static function sanitiseLanguageId($languageId) {
		return preg_replace('/[^a-zA-Z0-9\-]/', '', $languageId);
	}
}