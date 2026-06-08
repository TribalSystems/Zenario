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


class zenario_common_features__admin_boxes__translate_phrase extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//Don't use this box for editing phrases in the default language
		if (!$box['key']['language_id']
		 || !($phraseKey = ze\row::get('visitor_phrases', ['code', 'module_class_name', 'is_html', 'seen_at_content_id', 'seen_at_content_type', 'first_seen_by_visitor'], $box['key']['id']))) {
			exit;
		}
		
		$box['key']['code'] = $phraseKey['code'];
		$box['key']['is_html'] = $phraseKey['is_html'];
		$box['key']['module_class_name'] = $phraseKey['module_class_name'];
		$box['key']['is_code'] = substr($box['key']['code'], 0, 1) == '_';
		unset($phraseKey['is_html']);

		$languages = ze\lang::getLanguages(false, true, true);
		$translateDefaultLang = $languages[ze::$defaultLang]['translate_phrases'];
		$translateThisPhraseInDefaultLang = $box['key']['is_code'] || $translateDefaultLang;
		
		if ($box['key']['is_code']) {
			$fields['phrase/local_text']['rows'] = $fields['phrase/phrase']['rows'] = 1;
		} else {
			unset($box['tabs']['phrase']['fields']['phrase_codes_info']);
		}
		
		$mrg = [
			'default_lang' => ze\lang::name(ze::$defaultLang, $addIdInBracketsToEnd = false),
			'this_lang' => ze\lang::name($box['key']['language_id'], $addIdInBracketsToEnd = false)
		];
		
		$box['title'] = ze\admin::phrase('Editing a phrase in [[this_lang]]', $mrg);
		$fields['phrase/phrase']['label'] = ze\admin::phrase('Phrase in [[default_lang]]:', $mrg);
		$fields['phrase/local_text']['label'] = ze\admin::phrase('Phrase in [[this_lang]]:', $mrg);
		
		if ($box['key']['is_html']) {
			$fields['phrase/phrase']['type'] =
			$fields['phrase/local_text']['type'] = 'editor';
			$fields['phrase/phrase']['editor_type'] =
			$fields['phrase/local_text']['editor_type'] = 'phrase_editor';
			$fields['phrase/phrase']['notices_below'] = [
				'html_detected' => [
					'type' => 'information',
					'message' => ze\admin::phrase('This phrase uses HTML')
				]
			];
		}
		
		$lookupWhereStatement = $phraseKey;
		unset($lookupWhereStatement['seen_at_content_id'], $lookupWhereStatement['seen_at_content_type']);
		$lookupWhereStatement['language_id'] = ze::$defaultLang;
		if ($phrase = ze\row::get('visitor_phrases', ['local_text', 'protect_flag'], $phraseKey)) {
			
			if ($translateThisPhraseInDefaultLang) {
				$values['phrase/phrase'] = $phrase['local_text'];
			} else {
				$values['phrase/phrase'] = $box['key']['code'];
			}
		}
		
		$lookupWhereStatement['language_id'] = $box['key']['language_id'];
		if ($phrase = ze\row::get('visitor_phrases', ['local_text', 'protect_flag', 'modified_date'], $lookupWhereStatement)) {
			$values['phrase/local_text'] = $phrase['local_text'];
			$values['phrase/protect_flag'] = $phrase['protect_flag'];
			
			if ($phrase['modified_date']) {
				$box['last_updated'] = ze\admin::phrase('Last edited [[date]]', ['date' => \ze\date::formatDateTime($phrase['modified_date'], 'vis_date_format_short')]);
			}
		}
		
		//If this is the default language, don't show two columns
		if ($box['key']['language_id'] == ze::$defaultLang) {
			$fields['phrase/left_column']['hidden'] = true;
			$fields['phrase/right_column']['hidden'] = true;
			$fields['phrase/phrase']['hidden'] = true;
			$fields['phrase/phrase']['grouping'] = false;
			$fields['phrase/local_text']['grouping'] = false;
			$fields['phrase/protect_flag']['grouping'] = false;
		}
		
		if ($translateThisPhraseInDefaultLang) {
			$fields['phrase/code']['hidden'] = false;
			$values['phrase/code'] = $box['key']['code'];
		}
		
		$values['phrase/module'] = $box['key']['module_class_name'] . ' (' . ze\module::getModuleDisplayNameByClassName($box['key']['module_class_name']) . ')';
		
		if ($phraseKey['seen_at_content_id'] && $phraseKey['seen_at_content_type']) {
			$contentItemTag = $phraseKey['seen_at_content_type'] . '_' . $phraseKey['seen_at_content_id'];
			$contentMissing = false;
			
			$usage = [];
					
			if (ze\lang::count() > 1) {
				$usage['content_translation_chains'] = 1;
				$usage['content_translation_chain'] = $contentItemTag;
			} else {
				if (ze\row::exists('content_items', ['id' => $phraseKey['seen_at_content_id'], 'type' => $phraseKey['seen_at_content_type'], 'status' => ['!' => 'deleted']])) {
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
			
			$fields['phrase/first_seen_in_visitor_mode']['snippet']['html'] = ze\date::formatDateTime($phraseKey['first_seen_by_visitor']);
		} else {
			$fields['phrase/seen_at']['snippet']['html'] = ze\admin::phrase('Not yet seen');
			$fields['phrase/first_seen_in_visitor_mode']['hidden'] = true;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (ze\priv::onLanguage('_PRIV_MANAGE_LANGUAGE_PHRASE', $box['key']['language_id'])) {
			ze\row::set('visitor_phrases', 
				['local_text' => $values['phrase/local_text'], 'protect_flag' => $values['phrase/protect_flag'], 'modified_date' => ze\date::now()], 
				['code' => $box['key']['code'], 'module_class_name' => $box['key']['module_class_name'], 'language_id' => $box['key']['language_id']]);
			
			ze\phraseAdm::flagAsUpdated();
		}
	}
}