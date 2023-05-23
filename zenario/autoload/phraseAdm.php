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

class phraseAdm {



	//Formerly "secondsToAdminPhrase()"
	public static function seconds($seconds) {
		if (!class_exists('DateTime')) {
			$a = [
				['second', '[[n]] seconds', (int) $seconds]];
		} else {
			$zero = new \DateTime('@0');
			$dt = $zero->diff(new \DateTime('@'. (int) $seconds));
			$a = [
				['second', '[[n]] seconds', (int) $dt->format('%s')],
				['minute', '[[n]] minutes', (int) $dt->format('%i')],
				['hour', '[[n]] hours', (int) $dt->format('%h')], 
				['day', '[[n]] days', (int) $dt->format('%a')]];
		}
	
		$t = '';
		$s = '';
		foreach ($a as $v) {
			if ($v = \ze\admin::nPhrase($v[0], $v[1], $v[2], ['n' => $v[2]], '')) {
				$t = $v. $s. $t;
			
				if ($s === '') {
					$s = \ze\admin::phrase(' and ');
				} else {
					$s = \ze\admin::phrase(', ');
				}
			}
		}
	
		return $t;
	}





	//check if a Visitor Phrase is protected
	//Formerly "checkIfPhraseIsProtected()"
	public static function isProtected($languageId, $moduleClass, $phraseCode, $adding) {

		$sql = "
			SELECT";
	
		//Are we adding a new VLP, or updating an existing one?
		if (!$adding) {
			//If we are editing an existing one, do not overwrite a protected phrase
			$sql .= "
				protect_flag";
	
		} else {
			//If we are adding a new VLP, don't allow anything that already exists (and has a value) to be overwritten
			$sql .= "
				local_text IS NOT NULL AND local_text != ''";
		}
	
		$sql .= "
			FROM " . DB_PREFIX . "visitor_phrases
			WHERE language_id = '". \ze\escape::asciiInSQL($languageId). "'
			  AND module_class_name = '". \ze\escape::asciiInSQL($moduleClass). "'
			  AND code = '". \ze\escape::sql($phraseCode). "'";
	
	
		//Return true for protected, false for exists bug not protected, and 0 for when the phrase does not exist
		if ($row = \ze\sql::fetchRow(\ze\sql::select($sql))) {
			return (bool) $row[0];
		} else {
			return 0;
		}
	}

	//Update a Visitor Phrase from the importer
	//Formerly "importVisitorPhrase()"
	public static function importVisitorPhrase($languageId, $moduleClass, $phraseCode, $localText, $adding, &$numberOf) {
	
		//Don't attempt to add empty phrases
		if (!$phraseCode || $localText === null || $localText === false || $localText === '') {
			return;
		}
	
		//Check if the phrase is protected
		if ($protected = \ze\phraseAdm::isProtected($languageId, $moduleClass, $phraseCode, $adding)) {
			++$numberOf['protected'];
		
		} else {
			//Update or insert the phrase
			\ze\row::set(
				'visitor_phrases',
				[
					'local_text' => trim($localText)],
				[
					'language_id' => $languageId,
					'module_class_name' => $moduleClass,
					'code' => $phraseCode]);
		
			//\ze\phraseAdm::isProtected() returns false for phrases that are unprotected, and 0 for phrases that do not exist
			if ($protected === 0) {
				++$numberOf['added'];
		
			} else {
				++$numberOf['updated'];
			}
		}

	}


	//Given an uploaded XML file, pharse that file looking for visitor language phrases
	//Formerly "importVisitorLanguagePack()"
	public static function importVisitorLanguagePack($file, &$languageIdFound, $adding, $scanning = false, $forceLanguageIdOverride = false, $realFilename = false, $checkPerms = false) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}


	//Formerly "scanModulePhraseDir()"
	public static function scanModulePhraseDir($moduleName, $scanMode) {
		$importFiles = [];
		if ($path = \ze::moduleDir($moduleName, 'phrases/', true)) {
			foreach (scandir($path) as $file) {
				if (is_file($path. $file) && substr($file, 0, 1) != '.') {
				
					$languageIdFound = false;
					$numberOf = \ze\phraseAdm::importVisitorLanguagePack($path. $file, $languageIdFound, $adding = true, $scanMode);
				
					if (!$numberOf['upload_error']) {
						if ($scanMode === 'number and file') {
							$numberOf['file'] = $file;
							$importFiles[$languageIdFound] = $numberOf;
					
						} elseif ($scanMode === 'full scan') {
							$importFiles[$languageIdFound] = $numberOf['added'];
					
						} else {
							$importFiles[$languageIdFound] = $file;
						}
					}
				}
			}
		}
	
		return $importFiles;
	}

}