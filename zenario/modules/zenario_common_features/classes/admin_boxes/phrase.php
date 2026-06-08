<?php
/*
 * Copyright (c) 2026, Tribal Limited
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


class zenario_common_features__admin_boxes__phrase extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		
		//Show phrases for the country manager
		if ($box['key']['loadCountryName'] && ze\module::inc('zenario_country_manager')) {
			$details = [
				'code' => '_COUNTRY_NAME_'. $box['key']['id'],
				'module_class_name' => 'zenario_country_manager'];
			
		} elseif ($box['key']['loadRegionName'] && ze\module::inc('zenario_country_manager')) {
			$details = [
				'code' => zenario_country_manager::getEnglishRegionName($box['key']['id']),
				'module_class_name' => 'zenario_country_manager'];
		
		} else {
		 	$details = ze\row::get('visitor_phrases', ['code', 'module_class_name', 'is_html'], $box['key']['id']);
		}
		
		//From 7.0.3 we not longer offer the ability to create a new phrase using this box,
		//so exit if this isn't an existing phrase
		if (empty($details)) {
			exit;
		}
		
		$mostRecentDate = null;
		$existingPhrases = [];
		$result = ze\row::query(
			'visitor_phrases',
			['local_text', 'language_id', 'protect_flag', 'modified_date', 'seen_at_content_id', 'seen_at_content_type', 'first_seen_by_visitor'],
			['code' => $details['code'], 'module_class_name' => $details['module_class_name']]
		);
		
		$contentFound = false;
		$contentMissing = false;
		
		while ($row = ze\sql::fetchAssoc($result)) {
			$existingPhrases[$row['language_id']] = $row;
			
			if ($row['modified_date']) {
				if (is_null($mostRecentDate)
				 || $mostRecentDate < $row['modified_date']) {
					$mostRecentDate = $row['modified_date'];
				}
			}
			
			if (!$contentFound) {
				if ($row['seen_at_content_id'] && $row['seen_at_content_type']) {
					$contentFound = true;
					
					$contentItemTag = $row['seen_at_content_type'] . '_' . $row['seen_at_content_id'];
					
					$usage = [];
					
					if (ze\lang::count() > 1) {
						$usage['content_translation_chains'] = 1;
						$usage['content_translation_chain'] = $contentItemTag;
					} else {
						if (ze\row::exists('content_items', ['id' => $row['seen_at_content_id'], 'type' => $row['seen_at_content_type'], 'status' => ['!' => 'deleted']])) {
							$usage['content_items'] = 1;
							$usage['content_item'] = $contentItemTag;
						} else {
							$contentMissing = true;
							$fields['phrase/seen_at']['snippet']['html'] = ze\admin::phrase('Missing content item [[tag]]', ['tag' => $contentItemTag]);
						}
					}
					
					if (!$contentMissing) {
						$usageLinks = [];
						$whereUsed = implode('; ', ze\miscAdm::getUsageText($usage, $usageLinks));
						
						$fields['phrase/seen_at']['snippet']['html'] = $whereUsed;
					}
					
					$fields['phrase/first_seen_in_visitor_mode']['snippet']['html'] = ze\date::formatDateTime($row['first_seen_by_visitor']);
				} else {
					$fields['phrase/seen_at']['snippet']['html'] = ze\admin::phrase('Not yet seen');
					$fields['phrase/first_seen_in_visitor_mode']['hidden'] = true;
				}
			}
		}
		
		if ($mostRecentDate) {
			$box['last_updated'] = ze\admin::phrase('Last edited [[date]]', ['date' => \ze\date::formatDateTime($mostRecentDate, 'vis_date_format_short')]);
		}
		
		$languages = ze\lang::getLanguages(false, true, true);
		
		$box['key']['code'] = $details['code'];
		$box['key']['is_html'] = $details['is_html'] ?? false;
		$box['key']['module_class_name'] = $details['module_class_name'];
		$box['key']['is_code'] = substr($box['key']['code'], 0, 1) == '_';
		
		if ($box['key']['is_code']) {
			$fields['phrase/code']['label'] = ze\admin::phrase('Phrase code:');
		
		} elseif ($languages[ze::$defaultLang]['translate_phrases']) {
			$fields['phrase/code']['label'] = ze\admin::phrase('Phrase:');
		} else {
			$mrg = [
				'language_english_name' => $languages[ze::$defaultLang]['english_name'],
				'module_display_name' => ze\module::getModuleDisplayNameByClassName($details['module_class_name'])];
				
			if (!$mrg['module_display_name']) {
				$mrg['module_display_name'] = $box['key']['module_class_name'];
			}
			
			$fields['phrase/code']['label'] = ze\admin::phrase('Phrase / [[language_english_name]]:', $mrg);
			
			$fields['phrase/code']['note_below'] = 
				ze\admin::phrase('This phrase comes from a module or one of its plugins. In order to edit the text in [[language_english_name]]
							please go to the [[module_display_name]] and inspect its plugins\' settings, their frameworks, 
							and possibly the module\'s program code.', $mrg);
		}
		
		if (!$box['key']['is_code']) {
			unset($box['tabs']['phrase']['fields']['phrase_codes_info']);
		}
		
		if ($box['key']['is_html']) {
			$fields['phrase/code']['type'] = 'editor';
			$fields['phrase/code']['editor_type'] = 'phrase_editor';
			$fields['phrase/code']['editor_options'] = [
				'height' => 150
			];
			
			if (!$languages[ze::$defaultLang]['translate_phrases']) {
				$box['tabs']['phrase']['fields']['code']['notices_below'] = [
					'html_detected' => [
						'type' => 'information',
						'message' => ze\admin::phrase('This phrase uses HTML')
					]
				];
			}
		}
		
		$ord = 4;
		$hasSomePerms = false;
		$proFeaturesModuleIsRunning = ze\module::isRunning('zenario_pro_features');
		
		foreach ($languages as $language) {
			if ($box['key']['is_code'] || $language['translate_phrases']) {
		
				if (isset($existingPhrases[$language['id']])) {
					$phraseValue = $existingPhrases[$language['id']]['local_text'];
					$protectValue = $existingPhrases[$language['id']]['protect_flag'];
				} else {
					$phraseValue = '';
					$protectValue = '';
				}
				
				$hasSomePerms =
					($hasPerms = ze\priv::onLanguage('_PRIV_MANAGE_LANGUAGE_PHRASE', $language['id']))
				 || $hasSomePerms;
				
				$box['tabs']['phrase']['fields']['grouping_' . $language['id']] = [
					'type' => 'grouping',
					'ord' => ++$ord,
					'grouping_wrapper_is_main_scroll' => true
				];
				
				$box['tabs']['phrase']['fields'][$language['id']] =
					[
						'class_name' => 'zenario_common_features',
						'ord' => ++$ord,
						'label' => $language['english_name']. ':',
						'type' => 'textarea',
						'readonly' => !$hasPerms,
						'rows' => 4,
						'value' => $phraseValue,
						'css_class' => 'textarea_with_slider_on_the_side',
						'grouping' => 'grouping_' . $language['id']
					];
				
				if ($box['key']['is_code']) {
					$box['tabs']['phrase']['fields'][$language['id']]['rows'] = 1;
				}
		
				//If the Pro Features module is running, it will add a "Translate with Google" button.
				//Leave an ordinal gap here.
				if ($proFeaturesModuleIsRunning) {
					$ord++;
				}
				
				$box['tabs']['phrase']['fields']['protect_flag_'. $language['id']] =
					[
						'class_name' => 'zenario_common_features',
						'ord' => ++$ord,
						'label' => 'Protect',
						'type' => 'checkbox',
						'readonly' => !$hasPerms,
						'visible_if' => 'zenarioAB.editModeOn()',
						'value' => $protectValue,
						'format_onchange' => true,
						'onoff' => true,
						'side_note' => ze\admin::phrase("If importing a CSV/Excel translation file, prevent this phrase from being overwritten."),
						'grouping' => 'grouping_' . $language['id']
					];
				
				if ($box['key']['is_html']) {
					$box['tabs']['phrase']['fields'][$language['id']]['type'] = 'editor';
					$box['tabs']['phrase']['fields'][$language['id']]['editor_type'] = 'phrase_editor';
					$box['tabs']['phrase']['fields'][$language['id']]['editor_options'] = [
						'height' => 200
					];
				}
			}
		}
		
		if (!$hasSomePerms) {
			unset($box['tabs']['phrase']['edit_mode']);
		}
		
		//Try to set the Module's name
		if ($box['key']['module_class_name']) {
			if ($box['tabs']['phrase']['fields']['module']['value'] = $box['key']['module_class_name'] . ' (' . ze\module::getModuleDisplayNameByClassName($box['key']['module_class_name']) . ')') {
			} else {
				//If this is a phrase for a Module that doesn't exist any more, don't let it be edited
				$box['tabs']['phrase']['fields']['module']['value'] = $box['key']['module_class_name'];
				unset($box['tabs']['phrase']['edit_mode']);
			}
		
		//Any unclaimed phrases should be marked against the Common Features Module
		} else {
			$box['tabs']['phrase']['fields']['module']['value'] = ze\module::getModuleDisplayNameByClassName('zenario_common_features');
		}
		
		$box['tabs']['phrase']['fields']['code']['value'] = $box['key']['code'];
		
		$phrase = 'Editing a phrase';
		
		if (ze\lang::count() > 1) {
			$phrase .= ' (all languages)';
		}
		
		$box['title'] = ze\admin::phrase($phrase);
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_MANAGE_LANGUAGE_PHRASE');
		
		$languages = ze\lang::getLanguages(false, true, true);
		foreach ($languages as $language) {
			if (ze\priv::onLanguage('_PRIV_MANAGE_LANGUAGE_PHRASE', $language['id'])
			 && ($box['key']['is_code'] || $language['translate_phrases'])) {
				ze\row::set('visitor_phrases', 
					['local_text' => $values['phrase/'. $language['id']], 'protect_flag' => $values['phrase/protect_flag_'. $language['id']], 'modified_date' => ze\date::now(true)], 
					['code' => $box['key']['code'], 'module_class_name' => $box['key']['module_class_name'], 'language_id' => $language['id']]);
			}
		}
		
		ze\phraseAdm::flagAsUpdated();
	}
}
