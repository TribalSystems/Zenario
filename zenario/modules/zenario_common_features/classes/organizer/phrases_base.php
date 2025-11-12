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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


class zenario_common_features__organizer__phrases_base extends ze\moduleBaseClass {
	
	//The standard phrases and code-based phrases have lots of commonality.
	//We'll handle them using the same class that gets extended, and use some
	//protected variables as flags to decide which logic to use.
	
	protected $standard;
	protected $codeBased;
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		//For standard phrases, the code and the English translation are the same.
		//Change the column title to make this more clear.
		if ($this->standard) {
			$mrg = ['default_language_name' => ze\lang::name(ze::$defaultLang, $addIdInBracketsToEnd = false)];
			$panel['columns']['code']['title'] = ze\admin::phrase('Base phrase in [[default_language_name]]', $mrg);
		}
		
		//Look through the languages being used on this site
		$ord = 2;
		$translationInUse = false;
		$languages = ze\lang::getLanguages(false, true, true);
		foreach ($languages as $language) {
			
			//Remember if we see at least one language that's being translated
			$translationInUse = $translationInUse || $language['translate_phrases'];
	
			//For each language, add a table join to look up the local text and protect flag for this phrase in that language.
			$alias = '`'. ze\escape::sql('vp_'. $language['id']). '`';
			$dbColumnText = $alias. ".local_text";
			$dbColumnFlag = $alias. ".protect_flag";
			$tableJoin = "
				LEFT JOIN ". DB_PREFIX. "visitor_phrases AS ". $alias. "
				   ON ". $alias. ".code = vp.code
				  AND ". $alias. ".module_class_name = vp.module_class_name
				  AND ". $alias. ".language_id = '". ze\escape::asciiInSQL($language['id']). "'";
			
			//Add Organizer columns to display this info.
			//Note: For standard phrases, the code is the same as the English translation, so we don't display that twice.
			if ($this->codeBased || $language['id'] != ze::$defaultLang) {
				$panel['columns'][$language['id']] =
					[
						'class_name' => 'zenario_common_features',
						'title' => 'Text in '.$language['english_name'],
						'show_by_default' => true,
						'searchable' => true,
						'ord' => $ord,
						'db_column' => $dbColumnText,
						'table_join' => $tableJoin
					];
			}
			$panel['columns']['protect_'. $language['id']] =
				[
					'class_name' => 'zenario_common_features',
					'hidden' => true,
					'ord' => $ord + 0.01,
					'db_column' => $dbColumnFlag,
					'table_join' => $tableJoin
				];
	
			$ord += 0.02;
		}
		
		//Don't show the archive button if translations aren't being used.
		if ($this->standard && !$translationInUse) {
			$panel['item_buttons']['archive']['hidden'] = true;
		}

		$ord = 100;
		foreach ($languages as $langId => $language) {
			
			//For each language, add an export phrases button
			if ($language['translate_phrases']
			 && ze\priv::onLanguage('_PRIV_MANAGE_LANGUAGE_PHRASE', $langId)) {
				$panel['collection_buttons'][] = [
					'ord' => ++$ord,
					'class_name' => 'zenario_common_features',
					'parent' => 'export_phrases_dropdown',
					'label' => ze\admin::phrase('Export phrases for translation into [[english_name]]', $language),
					'admin_box' => [
						'path' => 'zenario_export_vlp',
						'key' => [
							'id' => $langId,
							$this->codeBased? 'export_code_based_phrases_only' : 'export_standard_phrases_only' => true
				]]];
			}
			
			//For each language, add an edit phrase button.
			//(Except for standard phrases in untranslated languages.)
			if (($this->codeBased || $language['translate_phrases'])
			 && ze\priv::onLanguage('_PRIV_MANAGE_LANGUAGE_PHRASE', $langId)) {
				
				$button = [
					'ord' => ++$ord,
					'class_name' => 'zenario_common_features',
					'parent' => 'edit_dropdown',
					'label' => ze\admin::phrase('Edit phrase in [[english_name]]', $language),
					'admin_box' => [
						'path' => 'zenario_translate_phrase',
						'key' => [
							'language_id' => $langId
				]]];
				
				$panel['item_buttons'][] = $button;
			}
		}
		
		//Don't show the "edit in all languages" button if translations aren't being used.
		if ($this->standard && !$translationInUse) {
			$panel['item_buttons']['edit']['hidden'] = true;
		}
		
		if (count($languages) > 1) {
			$panel['columns']['seen_at']['item_link'] = 'content_item_translation_chain';
			$panel['columns']['seen_at']['db_column'] = "IF (tc.equiv_id IS NULL, NULL, CONCAT(tc.type, '_', tc.equiv_id))";
		} else {
			$panel['columns']['seen_at']['item_link'] = 'content_item';
			$panel['columns']['seen_at']['db_column'] = "IF (vp.seen_at_content_id IS NULL, NULL, CONCAT(vp.seen_at_content_type, '_', vp.seen_at_content_id))";
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		//Check to see which modules have created phrases
		$sql = "
			SELECT DISTINCT p.module_class_name
			FROM ". DB_PREFIX. "visitor_phrases AS p";
		
		if ($this->standard) {
			$sql .= "
			WHERE substr(p.code, 1, 1) != '_'";
		
		} elseif ($this->codeBased) {
			$sql .= "
			WHERE substr(p.code, 1, 1) = '_'";
		}
		$vlpClasses = ze\sql::fetchValues($sql);
		
		//Also look up their display names so we can write labels for them
		$modules = ze\row::getAssocs('modules', ['class_name', 'display_name'], ['class_name' => $vlpClasses], $orderBy = [], $indexBy = 'class_name');
		
		//Create quick-filter options for each module
		$ord = 10;
		foreach ($vlpClasses as $className) {
			
			if (isset($modules[$className])) {
				$label = ze\admin::phrase('[[class_name]] ([[display_name]])', $modules[$className]);
			} else {
				$label = $className;
			}
			
			$panel['quick_filter_buttons'][$className] = [
				'ord' => ++$ord,
				'parent' => 'module',
				'column' => 'module_name',
				'label' => $label,
				'value' => $className
			];
		}
		
		//If a filter is set, make sure to change the label of the parent to what was chosen
		if (($filterValue = zenario_organizer::filterValue('module_name'))
		  && (!empty($panel['quick_filter_buttons'][$filterValue]['label']))) {
			$panel['quick_filter_buttons']['module']['label'] =
				$panel['quick_filter_buttons'][$filterValue]['label'];
		}
		
		//Example URL to this:
		//http://localhost:8888/eucojud/organizer.php#zenario__languages/panels/phrases~_{%22module_name%22%3A{%22v%22%3A%22zenario_banner%22}}
		
		
		
		$languages = ze\lang::getLanguages(false, true, true);
		$additionalLanguages = count($languages) - 1;
		
		foreach ($panel['items'] as $id => &$item) {
			
			//For each item, check to see if there is a translation for each language
			$translations = false;
			$missingTranslations = false;
			foreach ($languages as $langId => $lang) {
				if (isset($item[$langId]) && $item[$langId] != '') {
					$translations = true;
				
				//If a language does not have the translate_phrases flag set, then as long as this isn't
				//a phrase code, all it to just use the phrase untranslated.
				} elseif (empty($lang['translate_phrases']) && substr($item['code'], 0, 1) != '_') {
					$item[$langId] = $item['code'];
				
				} else {
					$missingTranslations = true;
				}
				
				if (!empty($item['protect_' . $langId])) {
					$item[$langId] .= ' ' . ze\admin::phrase('(protected)');
					
					//Add a CSS class to any translated phrase that is protected, to make the cell colour pale blue instead of white
					if (!isset($item['cell_css_classes'])) {
						$item['cell_css_classes'] = [];
					}
					$item['cell_css_classes'][$langId] = 'organizer_cell_protected_phrase';
				}
			}
			
			//Task #9611 Change the icon in the phrases panel to help when creating a module's phrase
			if ($additionalLanguages) {
				if ($missingTranslations) {
					if ($translations) {
						$item['css_class'] = 'phrase_partially_translated';
						$item['tooltip'] = ze\admin::phrase('This phrase has been translated into some site languages, click "Edit phrase" to add missing translations.');
		
					} else {
						$item['css_class'] = 'phrase_not_translated';
						$item['tooltip'] = ze\admin::phrase('This phrase has not been translated into any site languages, click "Edit phrase" to add translations.');
						$item['phrase_is_not_translated'] = true;
					}
				} else {
					$item['css_class'] = 'phrase_translated';
					$item['tooltip'] = ze\admin::phrase('This phrase has been translated into all site languages.');
				}
			}
			
			if ($item['seen_in_visitor_mode'] && is_null($item['first_seen_by_visitor'])) {
				$item['first_seen_by_visitor'] = 'unknown';
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		if ($this->standard && ze::post('delete_phrase') && ze\priv::check('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
			//Handle translated and/or customised phrases that are linked to the current phrase
			$sql = "
				SELECT l.id
				FROM ". DB_PREFIX. "visitor_phrases AS t
				INNER JOIN ". DB_PREFIX. "visitor_phrases AS l
				   ON l.module_class_name = t.module_class_name
				  AND l.code = t.code
				WHERE t.id IN (". ze\escape::in($ids, 'numeric'). ")
				  AND substr(t.code, 1, 1) != '_'";
						//N.b. this last line is a safety-catch to stop someone trying to delete a code-based phrase
			
			foreach (ze\sql::fetchValues($sql) as $id) {
				ze\row::delete('visitor_phrases', ['id' => $id]);
			}

		} elseif ($this->standard && ze::post('archive_phrase') && ze\priv::check('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
			//Handle translated and/or customised phrases that are linked to the current phrase
			$sql = "
				UPDATE ". DB_PREFIX. "visitor_phrases AS l
				INNER JOIN ". DB_PREFIX. "visitor_phrases AS t
				   ON t.module_class_name = l.module_class_name
				  AND t.code = l.code
				SET t.archived = 1,
					t.protect_flag = 0
				WHERE l.id IN (". ze\escape::in($ids, 'numeric'). ")
				  AND substr(l.code, 1, 1) != '_'";
			ze\sql::cacheFriendlyUpdate($sql);
			
			//Notes:
				//You can't archive a code-based phrase.
				//Archiving a phrase also removes its "protect" flag for simplicity.

		} elseif (ze::post('mark_as_unseen') && ze\priv::check('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
			//Clear all of the "seen in/seen at" flags for a phrase
			$sql = "
				UPDATE ". DB_PREFIX. "visitor_phrases AS t
				INNER JOIN ". DB_PREFIX. "visitor_phrases AS l
				   ON l.module_class_name = t.module_class_name
				  AND l.code = t.code
				SET l.seen_in_visitor_mode = 0,
					l.first_seen_by_visitor =  NULL,
					l.seen_at_content_id =  NULL,
					l.seen_at_content_type =  NULL
				WHERE t.id IN (". ze\escape::in($ids, 'numeric'). ")";
			ze\sql::update($sql);
		
		
		//The ability to fix renamed phrases by merging the old ones into the new ones has been removed,
		//as it was both a bit niche/specific, and a bit hard to explain to someone.
		//I'm leaving the old code here though, so as to not lose/forget it if we do ever need it again.
		#} elseif (ze::request('merge_phrases') && ze\priv::check('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
		#	//Merge phrases together
		#	$className = false;
		#	$newCode = false;
		#	$codes = [];
		#	$idsToKeep = [];
		#	$returnId = false;
		#
		#	//Look through the phrases that have been collected and:
		#		//Check if none are phrase codes
		#		//Check that they are all from the same module
		#		//Find the newest code (which will probably be the correct one)
		#		//Get a list of codes to merge
		#	$sql = "
		#		SELECT id, code, module_class_name, SUBSTR(code, 1, 1) = '_' AS is_code
		#		FROM ". DB_PREFIX. "visitor_phrases
		#		WHERE id IN (". ze\escape::in($ids, 'numeric'). ")
		#		ORDER BY id DESC";
		#	$result = ze\sql::select($sql);
		#	while ($row = ze\sql::fetchAssoc($result)) {
		#		if ($row['is_code']) {
		#			echo ze\admin::phrase('You can only merge phrases that are not phrase codes');
		#			exit;
		#
		#		} elseif ($newCode === false) {
		#			$newCode = $row['code'];
		#			$className = $row['module_class_name'];
		#
		#		} else {
		#			if ($className != $row['module_class_name']) {
		#				echo ze\admin::phrase('You can only merge phrases if they are all for the same module');
		#				exit;
		#			}
		#		}
		#		$codes[] = $row['code'];
		#	}
		#
		#	if ($newCode === false) {
		#		echo ze\admin::phrase('Could not merge these phrases');
		#		exit;
		#	}
		#
		#	//Get a list of the newest ids in each language (which will probably have the most up to date translations)
		#	$sql = "
		#		SELECT MAX(id), language_id
		#		FROM ". DB_PREFIX. "visitor_phrases
		#		WHERE module_class_name = '". ze\escape::asciiInSQL($className). "'
		#		  AND code IN (". ze\escape::in($codes). ")
		#		GROUP BY language_id";
		#	$result = ze\sql::select($sql);
		#	while ($row = ze\sql::fetchRow($result)) {
		#		$idsToKeep[] = $row[0];
		#
		#		if ($row[1] == ze::$defaultLang) {
		#			$returnId = $row[0];
		#		}
		#	}
		#
		#	//Delete the oldest phrases that would clash with the primary key after a merge
		#	$sql = "
		#		DELETE FROM ". DB_PREFIX. "visitor_phrases
		#		WHERE module_class_name = '". ze\escape::asciiInSQL($className). "'
		#		  AND code IN (". ze\escape::in($codes). ")
		#		  AND id NOT IN (". ze\escape::in($idsToKeep, 'numeric'). ")";
		#	ze\sql::update($sql);
		#
		#	//Update the remaining phrases to use the correct code
		#	$sql = "
		#		UPDATE ". DB_PREFIX. "visitor_phrases
		#		SET code = '". ze\escape::sql($newCode). "'
		#		WHERE module_class_name = '". ze\escape::asciiInSQL($className). "'
		#		  AND id IN (". ze\escape::in($idsToKeep, 'numeric'). ")";
		#	ze\sql::update($sql);
		#
		#	return $returnId;

		} elseif (ze::post('import_phrases') && ze\priv::check('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
			
			ze\fileAdm::exitIfUploadError(true, false, false, 'Filedata');
			
			if (ze\file::mimeType($_FILES['Filedata']['name']) == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
			 && !extension_loaded('zip')) {
				echo ze\admin::phrase('Importing or exporting .xlsx files requires the php_zip extension. Please ask your server administrator to enable it.');
				exit;
	
			} else {
				$languageIdFound = false;
				$numberOf = ze\phraseAdm::importVisitorLanguagePack($_FILES['Filedata']['tmp_name'], $languageIdFound, false, false, false, $_FILES['Filedata']['name'], $checkPerms = true);
				zenario_common_features::languageImportResults($languageIdFound, $numberOf);
			}

		} elseif ($this->codeBased && ze::request('reimport_phrases')) {
			ze\contentAdm::importPhrasesForModules($langId = false, $keepExistingTranslations = false);
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}