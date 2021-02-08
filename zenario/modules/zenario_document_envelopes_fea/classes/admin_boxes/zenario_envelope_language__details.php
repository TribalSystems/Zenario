<?php
/*
 * Copyright (c) 2021, Tribal Limited
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


class zenario_document_envelopes_fea__admin_boxes__envelope_language__details extends zenario_document_envelopes_fea {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$languageId = $box['key']['id'];
		$languageFlaggedAsMultipleLanguages = ze\row::get(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelope_languages', ['language_id', 'label'], ['multiple_languages_flag' => true]);
		
		if ($languageId) {
			$envelopeLanguageData = ze\row::get(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelope_languages', true, $languageId);
			ze\lang::applyMergeFields($box['title'], $envelopeLanguageData);
			$values['details/language_id'] = $envelopeLanguageData['language_id'];
			$values['details/label'] = $envelopeLanguageData['label'];
			$values['details/multiple_languages_flag'] = $envelopeLanguageData['multiple_languages_flag'];
			
			$fields['details/language_id']['read_only'] = true;
			if (!$languageFlaggedAsMultipleLanguages || $languageFlaggedAsMultipleLanguages['language_id'] == $languageId) {
				//Only one language may be marked as "multiple languages".
				//If we're editing a language marked as multi, or there are no languages marked as multi,
				//let admins edit the checkbox.
				$fields['details/multiple_languages_flag']['read_only'] = false;
			}
			
			//$box['last_updated'] = ze\admin::formatLastUpdated($envelopeData);
		} else {
			if (!$languageFlaggedAsMultipleLanguages) {
				//Only one language may be marked as "multiple languages".
				//If there already is a language marked as multi in the database,
				//let admins edit the checkbox.
				$fields['details/multiple_languages_flag']['read_only'] = false;
			}
			$box['title'] = ze\admin::phrase('Creating an envelope language');
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...your PHP code...//
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if (!$box['key']['id'] && ze\row::exists(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelope_languages', $values['details/language_id'])) {
			$fields['details/language_id']['error'] = ze\admin::phrase("Language code must be unique.");
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$languageId = $box['key']['id'];
		$details = [
			'label' => $values['details/label'],
			'multiple_languages_flag' => $values['details/multiple_languages_flag']
		];
		
		if (!$languageId) {
			$details['language_id'] = $values['details/language_id'];
		}
		
		//ze\admin::setLastUpdated($details, !$envelopeId);
		
		$box['key']['id'] = ze\row::set(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelope_languages', $details, ($languageId ?? false));
	}
	
}