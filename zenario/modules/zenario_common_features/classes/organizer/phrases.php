<?php
/*
 * Copyright (c) 2016, Tribal Limited
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


class zenario_common_features__organizer__phrases extends module_base_class {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__languages/panels/phrases') return;
		
		if (!$refinerName || $refinerName == 'language' || $refinerName == 'language_and_plugin') {
			$mrg = array('lang_name' => htmlspecialchars(getLanguageName(FOCUSED_LANGUAGE_ID__NO_QUOTES)));
	
			if ($refinerName == 'language_and_plugin') {
				if ($module = getModuleDetails(get('refiner__language_and_plugin'))) {
					$mrg['display_name'] = $module['display_name'];
					$panel['key']['moduleClass'] = $module['class_name'];
			
					$panel['title'] = adminPhrase('Phrases in the Language "[[lang_name]]" (source: "[[display_name]]" Module)', $mrg);
					$panel['no_items_message'] = adminPhrase('There are no "[[lang_name]]" Phrases for the "[[display_name]]" Module', $mrg);
				}
		
				unset($panel['columns']['module_class_name']);
	
			} elseif ($refinerName == 'language') {
				$panel['title'] = adminPhrase('Phrases in the Language "[[lang_name]]"', $mrg);
				$panel['no_items_message'] = adminPhrase('There are no Phrases in the Language "[[lang_name]]"', $mrg);
			}
	
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_no_refiner'];
			$panel['key']['language_id'] = FOCUSED_LANGUAGE_ID__NO_QUOTES;
	
			if (isset($panel['item_buttons']['delete'])) {
		
				if (FOCUSED_LANGUAGE_ID__NO_QUOTES == setting('default_language')) {
					$panel['item_buttons']['delete']['ajax']['request']['delete_translated_phrases'] = 1;
				}
			}

		} elseif ($refinerName == 'translations') {
			$mrg = getRow('visitor_phrases', array('code', 'module_class_name'), $refinerId);
	
			if ($mrg['display_name'] = getModuleDisplayNameByClassName($mrg['module_class_name'])) {
				$panel['title'] = adminPhrase('Translations of the Phrase "[[code]]" (source: "[[display_name]]" Module)', $mrg);
				$panel['no_items_message'] = adminPhrase('There are no translations of the Phrase "[[code]]" for the "[[display_name]]" Module', $mrg);
			} else {
				$panel['title'] = adminPhrase('Translations of the Phrase "[[code]]"', $mrg);
				$panel['no_items_message'] = adminPhrase('There are no translations of the Phrase "[[code]]"', $mrg);
			}
	
			unset($panel['columns']['localized_phrase']);
			unset($panel['columns']['localized_phrases']);
		}

		if ($path == 'zenario__languages/panels/phrases') {
	
			$languages = getLanguages(false, true, true);
			$ord = 2;
			foreach ($languages as $language) {
		
				if (!empty($panel['key']['language_id'])
				 && $panel['key']['language_id'] == $language['id']) {
					$dbColumnText = "vp.local_text";
					$dbColumnFlag = "vp.protect_flag";
					$tableJoin = "";
				} else {
					$alias = '`'. sqlEscape('vp_'. $language['id']). '`';
					$dbColumnText = $alias. ".local_text";
					$dbColumnFlag = $alias. ".protect_flag";
					$tableJoin = "
						LEFT JOIN ". DB_NAME_PREFIX. "visitor_phrases AS ". $alias. "
						   ON ". $alias. ".code = vp.code
						  AND ". $alias. ".module_class_name = vp.module_class_name
						  AND ". $alias. ".language_id = '". sqlEscape($language['id']). "'";
				}
		
				$panel['columns'][$language['id']] =
					array(
						'class_name' => 'zenario_common_features',
						'title' => 'Text in '.$language['english_name'],
						'show_by_default' => true,
						'searchable' => true,
						'ord' => $ord,
						'db_column' => $dbColumnText,
						'table_join' => $tableJoin
					);
				$panel['columns']['protect_'. $language['id']] =
					array(
						'class_name' => 'zenario_common_features',
						'title' => 'Protect '.$language['english_name'],
						'show_by_default' => true,
						'format' => 'yes_or_no',
						'ord' => $ord + 0.01,
						'width' => 'xxsmall',
						'db_column' => $dbColumnFlag,
						'table_join' => $tableJoin
					);
		
				$ord += 0.02;
			}
		}

		// Hide import button if not showing module phrases OR no phrases directory found
		if (($refinerName == 'language_and_plugin') && file_exists(CMS_ROOT . moduleDir(getModuleClassName($refinerId)) . 'phrases/')) {
			$moduleDetails = getRow('modules', array('class_name', 'display_name'), $refinerId);
			$importFiles = scanModulePhraseDir($moduleDetails['class_name'], 'number and file');
			$list = array();
			$languages = getLanguages(false, true, true);
	
			foreach ($languages as $langId => $language) {
				if (isset($importFiles[$langId])
					|| (
						($pos = strpos($langId, '-')) !== false
						&& ($langId = substr($langId, 0, $pos))
						&& isset($importFiles[$langId])
					)
				) {
					$count = $importFiles[$langId]['added'] + $importFiles[$langId]['updated'];
					$list[] = nAdminPhrase(
						'[[name]] ([[count]] phrase)',
						'[[name]] ([[count]] phrases)',
						$count,
						array(
							'name' => $language['english_name'], 
							'count' => $count));
				}
			}
			$list = implode(', ', $list);
			$panel['collection_buttons']['reimport_phrases']['ajax']['confirm']['message'] = adminPhrase('
				Are you sure you wish to re-import phrases for the module [[display_name]] ([[class_name]])?
		
				This will re-import phrases from the config files in the module\'s "phrases" directory: [[list_of_languages_and_phrase_count]].
		
				Existing phrases that are marked as "protected" will not be overwritten, but all non-protected phrases will be overwritten.
			', array(
				'display_name' => $moduleDetails['display_name'], 
				'class_name' => $moduleDetails['class_name'],
				'list_of_languages_and_phrase_count' => $list));
	
			unset($panel['collection_buttons']['import_phrases']);
		} else {
			unset($panel['collection_buttons']['reimport_phrases']);
	
			//For each language, add an export phrases button
			$languages = getLanguages(false, true, true);
			$ord = 100;
			foreach ($languages as $langId => $language) {
				if ($language['translate_phrases']
				 && checkPrivForLanguage('_PRIV_MANAGE_LANGUAGE_PHRASE', $langId)) {
					$panel['collection_buttons'][] = array(
						'ord' => ++$ord,
						'class_name' => 'zenario_common_features',
						'parent' => 'export_phrases_dropdown',
						'label' => adminPhrase('Export phrases for translation into [[english_name]]', $language),
						'admin_box' => array(
							'path' => 'zenario_export_vlp',
							'key' => array(
								'id' => $langId
					)));
				}
			}
		}
		
		
		//For each language, add an edit phrase button
		$languages = getLanguages(false, true, true);
		$ord = 200;
		foreach ($languages as $langId => $language) {
			if (checkPrivForLanguage('_PRIV_MANAGE_LANGUAGE_PHRASE', $langId)) {
				
				$button = array(
					'ord' => ++$ord,
					'class_name' => 'zenario_common_features',
					'parent' => 'edit_dropdown',
					'admin_box' => array(
						'path' => 'zenario_translate_phrase',
						'key' => array(
							'language_id' => $langId
				)));
				
				if ($language['translate_phrases']) {
					$button['label'] = adminPhrase('Edit phrase in [[english_name]]', $language);
				} else {
					$button['label'] = adminPhrase('Edit phrase in [[english_name]]', $language);
					$button['visible_if'] = 'item.code && item.code.substr(0, 1) == "_"';
				}
				
				$panel['item_buttons'][] = $button;
			}
		}
		if (getNumLanguages() < 2) {
			$panel['item_buttons']['edit']['hidden'] = true;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__languages/panels/phrases') return;
		
		$languages = getLanguages(false, true, true);
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
			}
			
			//Task #9611 Change the icon in the phrases panel to help when creating a module's phrase
			if ($additionalLanguages) {
				if ($missingTranslations) {
					if ($translations) {
						$item['css_class'] = 'phrase_partially_translated';
						$item['tooltip'] = adminPhrase('This phrase has been translated into some site languages, click "Edit phrase" to add missing translations.');
		
					} else {
						$item['css_class'] = 'phrase_not_translated';
						$item['tooltip'] = adminPhrase('This phrase has not been translated into all site languages, click "Edit phrase" to add translations.');
					}
				} else {
					$item['css_class'] = 'phrase_translated';
					$item['tooltip'] = adminPhrase('This phrase has been translated into all site languages.');
				}
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__languages/panels/phrases') return;
		
		if (request('delete_phrase') && checkPriv('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
			//Handle translated and/or customised phrases that are linked to the current phrase
			if (request('delete_translated_phrases')) {
				$sql = "
					FROM ". DB_NAME_PREFIX. "visitor_phrases AS t
					INNER JOIN ". DB_NAME_PREFIX. "visitor_phrases AS l
					   ON l.module_class_name = t.module_class_name
					  AND l.code = t.code
					WHERE t.id IN (". inEscape($ids, 'numeric'). ")
					  AND t.language_id != l.language_id";
	
				if (get('delete_phrase')) {
					$result = sqlSelect("SELECT COUNT(DISTINCT l.id) AS cnt". $sql);
					$mrg = sqlFetchAssoc($result);
			
					if (is_numeric($ids)) {
						$mrg['code'] = getRow('visitor_phrases', 'code', array('id' => $ids));
						echo '<p>', adminPhrase('Are you sure you wish to delete the Phrase &quot;[[code]]&quot;?', $mrg), '</p>';
					} else {
						echo '<p>', adminPhrase('Are you sure you wish to delete the selected Phrases?'), '</p>';
					}
			
					if ($mrg['cnt']) {
						if ($mrg['cnt'] == 1) {
							echo '<p>', adminPhrase('1 translated Phrase will also be deleted.', $mrg), '</p>';
				
						} else {
							echo '<p>', adminPhrase('[[cnt]] translated Phrases will also be deleted.', $mrg), '</p>';
						}
					}
		
				} elseif (post('delete_phrase')) {
					$result = sqlSelect("SELECT l.id". $sql);
					while ($row = sqlFetchAssoc($result)) {
						deleteRow('visitor_phrases', array('id' => $row['id']));
					}
				}
			}
	
			if (post('delete_phrase') && checkPriv('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
				foreach (explodeAndTrim($ids) as $id) {
					deleteRow('visitor_phrases', array('id' => $id));
				}
			}

		} elseif (request('merge_phrases') && checkPriv('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
			//Merge phrases together
			$className = false;
			$newCode = false;
			$codes = array();
			$idsToKeep = array();
			$returnId = false;
	
			//Look through the phrases that have been collected and:
				//Check if none are phrase codes
				//Check that they are all from the same module
				//Find the newest code (which will probably be the correct one)
				//Get a list of codes to merge
			$sql = "
				SELECT id, code, module_class_name, SUBSTR(code, 1, 1) = '_' AS is_code
				FROM ". DB_NAME_PREFIX. "visitor_phrases
				WHERE id IN (". inEscape($ids, 'numeric'). ")
				ORDER BY id DESC";
			$result = sqlQuery($sql);
			while ($row = sqlFetchAssoc($result)) {
				if ($row['is_code']) {
					echo adminPhrase('You can only merge phrases that are not phrase codes');
					exit;
		
				} elseif ($newCode === false) {
					$newCode = $row['code'];
					$className = $row['module_class_name'];
		
				} else {
					if ($className != $row['module_class_name']) {
						echo adminPhrase('You can only merge phrases if they are all for the same Module');
						exit;
					}
				}
				$codes[] = $row['code'];
			}
	
			if ($newCode === false) {
				echo adminPhrase('Could not merge these phrases');
				exit;
			}
	
			//Get a list of the newest ids in each language (which will probably have the most up to date translations)
			$sql = "
				SELECT MAX(id), language_id
				FROM ". DB_NAME_PREFIX. "visitor_phrases
				WHERE module_class_name = '". sqlEscape($className). "'
				  AND code IN (". inEscape($codes). ")
				GROUP BY language_id";
			$result = sqlQuery($sql);
			while ($row = sqlFetchRow($result)) {
				$idsToKeep[] = $row[0];
		
				if ($row[1] == setting('default_language')) {
					$returnId = $row[0];
				}
			}
	
			//Delete the oldest phrases that would clash with the primary key after a merge
			$sql = "
				DELETE FROM ". DB_NAME_PREFIX. "visitor_phrases
				WHERE module_class_name = '". sqlEscape($className). "'
				  AND code IN (". inEscape($codes). ")
				  AND id NOT IN (". inEscape($idsToKeep, 'numeric'). ")";
			sqlQuery($sql);
	
			//Update the remaining phrases to use the correct code
			$sql = "
				UPDATE ". DB_NAME_PREFIX. "visitor_phrases
				SET code = '". sqlEscape($newCode). "'
				WHERE module_class_name = '". sqlEscape($className). "'
				  AND id IN (". inEscape($idsToKeep, 'numeric'). ")";
			sqlQuery($sql);
	
			return $returnId;

		} elseif (post('import_phrases') && checkPriv('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
			
			if (documentMimeType($_FILES['Filedata']['name']) == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
			 && !extension_loaded('zip')) {
				echo adminPhrase('Importing or exporting .xlsx files requires the php_zip extension. Please ask your server administrator to enable it.');
				exit;
	
			} else {
				$languageIdFound = false;
				$numberOf = importVisitorLanguagePack($_FILES['Filedata']['tmp_name'], $languageIdFound, false, false, false, $_FILES['Filedata']['name'], $checkPerms = true);
				$this->languageImportResults($numberOf);
			}

		} elseif (request('reimport_phrases')) {
			if ($refinerId && ($moduleDetails = getRow('modules', array('class_name', 'display_name'), $refinerId))) {
				$importFiles = scanModulePhraseDir($moduleDetails['class_name'], 'number and file');
				$list = array();
				$languages = getLanguages(false, true, true);
		
				foreach ($languages as $langId => $language) {
					importPhrasesForModule($moduleDetails['class_name'], $langId);
				}
			} else {
				echo adminPhrase('Could not find target module');
			}
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
	
	

	
	protected function languageImportResults($numberOf, $error = false, $changeButtonHTML = false) {
		if ($error) {
			echo $error;

		} elseif ($numberOf['wrong_language']) {
			echo adminPhrase("_VLP_IMPORT_FOR_WRONG_LANGUAGE");

		} elseif ($numberOf['upload_error']) {
			echo adminPhrase("There was an error with your file upload. Please make sure you have provided a valid file, in the format required by this tool.").
					adminPhrase("Language Pack imported. [[added]] phrase(s) were added and [[updated]] phrase(s) have been updated. [[protected]] phrase(s) were protected and not overwritten.", $numberOf);
	
		} elseif ($numberOf['added'] || $numberOf['updated']) {
			echo '<!--Message_Type:Success-->';
			if ($changeButtonHTML) {
				echo '<!--Button_HTML:<input type="button" class="submit" value="', adminPhrase('OK'), '" onclick="zenarioO.reloadPage(\'zenario__languages/panels/languages\');"/>-->';
			}
			echo adminPhrase("Language pack imported. [[added]] phrase(s) were added and [[updated]] phrase(s) have been updated. [[protected]] phrase(s) were protected and not overwritten.", $numberOf);
	
		} else {
			echo '<!--Message_Type:Warning-->';
			echo adminPhrase("No phrases were imported.");
	
			if ($numberOf['protected'] > 0) {
				echo adminPhrase(" [[protected]] phrase(s) were protected and not overwritten.", $numberOf);
			}
		}
	}
}