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

				
class zenario_pro_features__admin_boxes__phrase extends zenario_pro_features {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$languageCount = ze\lang::count();
		if ($languageCount > 1) {
			$googleTranslateApiKey = ze::setting('google_translate_api_key');
			
			//These 3 variables will be used in case the API key is not set
			$href = ze\link::absolute() .'organizer.php#zenario__administration/panels/site_settings//api_keys~.site_settings~ttranslation~k{"id"%3A"api_keys"}';
			$linkStart = "<a target='_blank' href='" . $href . "'>";
			$linkEnd = "</a>";
			
			$languages = ze\lang::getLanguages(false, true, true);
			foreach ($languages as $language) {
				if (isset($box['tabs']['phrase']['fields'][$language['id']])) {
					$ord = $box['tabs']['phrase']['fields'][$language['id']]['ord'];
					
					$box['tabs']['phrase']['fields']['translate_with_google_'. $language['id']] =
						[
							'class_name' => 'zenario_pro_features',
							'css_class' => 'zenario_text_button',
							'ord' => ++$ord,
							'value' => ze\admin::phrase('Translate with Google'),
							'type' => 'button',
							'format_onchange' => true,
							'grouping' => 'grouping_' . $language['id']
						];
					
					if (!$googleTranslateApiKey) {
						$box['tabs']['phrase']['fields']['translate_with_google_'. $language['id']]['disabled'] = true;
						
						$box['tabs']['phrase']['fields']['translate_with_google_'. $language['id']]['side_note'] =
							ze\admin::phrase(
								'To use this feature, please add a Google Translation API key. See [[link_start]]site settings[[link_end]].',
								['link_start' => $linkStart, 'link_end' => $linkEnd]
							);
					}
				}
			}
		}
		
		$linkStart = "<a href='organizer.php#zenario__administration/panels/site_settings//api_keys~.site_settings~ttranslation~k{\"id\"%3A\"api_keys\"}' target='_blank'>";
		$linkEnd = "</a>";

		ze\lang::applyMergeFields($box['tabs']['phrase']['notices']['translation_error']['message'], ['link_start' => $linkStart, 'link_end' => $linkEnd]);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
    	$languageCount = ze\lang::count();
    	$box['tabs']['phrase']['notices']['translation_error']['show'] = false;
    	
		if ($languageCount > 1) {
			$defaultSiteLanguage = ze::$defaultLang;
			
			$languages = ze\lang::getLanguages(false, true, true);
			foreach ($languages as $language) {
				if (isset($box['tabs']['phrase']['fields']['translate_with_google_'. $language['id']])) {
					$phraseIsProtected = false;
					
					if (isset($fields['phrase/protect_flag_'. $language['id']])) {
						if ($values['phrase/protect_flag_'. $language['id']]) {
							$phraseIsProtected = true;
						}
					}
					
					$fields['phrase/translate_with_google_'. $language['id']]['hidden'] = ($phraseIsProtected || ze::in($box['key']['code'], '__LANGUAGE_ENGLISH_NAME__', '__LANGUAGE_LOCAL_NAME__'));
					
					if (!empty($fields['phrase/translate_with_google_'. $language['id']]['pressed']) && !$phraseIsProtected) {
						//This admin box works for both standard phrases and code-based phrases.
						//Work out what the value should be.
						if ($box['key']['is_code']) {
							$phraseToTranslate = $values['phrase/' . $defaultSiteLanguage];
						} else {
							$phraseToTranslate = $values['phrase/code'];
						}
						
						if ($phraseToTranslate) {
							$translatedPhrase = zenario_pro_features::translatePhrase($phraseToTranslate, $language['id']);
							
							if ($translatedPhrase) {
								$values['phrase/'. $language['id']] = $translatedPhrase;
							} else {
								$values['phrase/'. $language['id']] = '';
								$box['tabs']['phrase']['notices']['translation_error']['show'] = true;
							}
						}
					}
					unset($fields['phrase/translate_with_google_'. $language['id']]['pressed']);
				}
			}
    	}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//...your PHP code...//
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...your PHP code...//
	}
}