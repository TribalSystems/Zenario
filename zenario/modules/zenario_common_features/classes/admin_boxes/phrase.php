<?php
/*
 * Copyright (c) 2022, Tribal Limited
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
		 	$details = ze\row::get('visitor_phrases', ['code', 'module_class_name'], $box['key']['id']);
		}
		
		//From 7.0.3 we not longer offer the ability to create a new phrase using this box,
		//so exit if this isn't an existing phrase
		if (empty($details)) {
			exit;
		}
		
		
		$existingPhrases = [];
		$result = ze\row::query('visitor_phrases', ['local_text', 'language_id', 'protect_flag'], ['code'=>$details['code'], 'module_class_name'=>$details['module_class_name']]);
		while ($row = ze\sql::fetchAssoc($result)) {
			$existingPhrases[$row['language_id']] = $row;
		}
		$languages = ze\lang::getLanguages(false, true, true);
		
		$box['key']['code'] = $details['code'];
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
			
			$fields['phrase/code']['side_note'] = 
				ze\admin::phrase('This code comes from a module or one of its plugins. In order to edit the text in [[language_english_name]]
							please go to the [[module_display_name]] and inspect its plugins\' settings, their frameworks, 
							and possibly the module\'s program code.', $mrg);
		}
		
		$ord = 4;
		$hasSomePerms = false;
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
				
				$box['tabs']['phrase']['fields'][$language['id']] =
					[
						'class_name' => 'zenario_common_features',
						'ord' => $ord,
						'label' => $language['english_name']. ':',
						'type' => 'textarea',
						'readonly' => !$hasPerms,
						'rows' => '4',
						'side_note' => 
							"This is HTML text.
							Any special characters such as <code>&amp;</code> <code>&quot;</code> <code>&lt;</code> or <code>&gt;</code>
							should be escaped (i.e. by replacing them with <code>&amp;amp;</code> <code>&amp;quot;</code> <code>&amp;lt;</code>
							and <code>&amp;gt;</code> respectively).",
						'value' => $phraseValue
						];
		
				$box['tabs']['phrase']['fields']['protect_flag_'. $language['id']] =
					[
						'class_name' => 'zenario_common_features',
						'ord' => $ord + 1,
						'label' => 'Protect',
						'type' => 'checkbox',
						'readonly' => !$hasPerms,
						'visible_if' => 'zenarioAB.editModeOn()',
						'value' => $protectValue,
						'side_note' =>
						"Protecting a Phrase will stop it from being overwritten when
						importing Phrases from a CSV file."
					];
				$ord += 2;
			}
		}
		
		if (!$hasSomePerms) {
			unset($box['tabs']['phrase']['edit_mode']);
		}
		
		//Try to set the Module's name
		if ($box['key']['module_class_name']) {
			if ($box['tabs']['phrase']['fields']['module']['value'] = ze\module::id($box['key']['module_class_name'])) {
				$box['tabs']['phrase']['fields']['module']['readonly'] = true;
			} else {
				//If this is a phrase for a Module that doesn't exist any more, don't let it be edited
				unset($box['tabs']['phrase']['fields']['module']['pick_items']);
				$box['tabs']['phrase']['fields']['module']['type'] = 'text';
				$box['tabs']['phrase']['fields']['module']['value'] = $box['key']['module_class_name'];
				unset($box['tabs']['phrase']['edit_mode']);
			}
		
		//Any unclaimed phrases should be marked against the Common Features Module
		} else {
			$box['tabs']['phrase']['fields']['module']['value'] = ze\module::id('zenario_common_features');
		}
		
		$box['tabs']['phrase']['fields']['code']['value'] = $box['key']['code'];
		
		// If this a phrase code (e.g. _HELLO_WORLD) or a ze\lang::phrase (e.g. Hello World)
		if ($box['key']['is_code']) {
			$box['title'] = ze\admin::phrase('Editing the Phrase "[[code]]".', $box['key']);			
		
		// If this is a ze\lang::phrase (not a code)
		} else {
			$box['title'] = ze\admin::phrase('Editing a Phrase');
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_MANAGE_LANGUAGE_PHRASE');
		
		$languages = ze\lang::getLanguages(false, true, true);
		foreach ($languages as $language) {
			if (ze\priv::onLanguage('_PRIV_MANAGE_LANGUAGE_PHRASE', $language['id'])
			 && ($box['key']['is_code'] || $language['translate_phrases'])) {
				ze\row::set('visitor_phrases', 
					['local_text' => $values['phrase/'. $language['id']], 'protect_flag' => $values['phrase/protect_flag_'. $language['id']]], 
					['code' => $box['key']['code'], 'module_class_name' => $box['key']['module_class_name'], 'language_id' => $language['id']]);
			}
		}
	}
}
