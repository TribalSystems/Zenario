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

				
class zenario_pro_features__admin_boxes__content extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (isset($box['tabs']['meta_data']['fields']['menu_invisible'])) {
			if ($box['key']['source_cID']) {
				if ($menu = ze\menu::getFromContentItem($box['key']['source_cID'], $box['key']['cType'])) {
					$box['tabs']['meta_data']['fields']['menu_invisible']['value'] = $menu['invisible'];
				}
			} elseif (!$box['key']['cID']) {
				$contentType = ze\row::get('content_types', true, $box['key']['cType']);
				
				if (!empty($contentType['hide_menu_node'])) {
					$values['meta_data/menu_invisible'] = 1;
				}
			}
		}
		
		if ($box['key']['translate']) {
			$targetLanguage = $box['key']['target_language_id'];
			$googleTranslateApiKey = ze::setting('google_translate_api_key');
			
			//These 3 variables will be used in case the API key is not set
			$href = ze\link::absolute() .'organizer.php#zenario__administration/panels/site_settings//api_keys~.site_settings~ttranslation~k{"id"%3A"api_keys"}';
			$linkStart = "<a target='_blank' href='" . $href . "'>";
			$linkEnd = "</a>";
			
			$loopThrough = [
				'title' => 'grouping_title_right'
			];
			
			//Obey the content-specific field visibility settings
			if ($box['key']['cType'] && $details = ze\contentAdm::cTypeDetails($box['key']['cType'])) {
				if ($details['summary_field'] != 'hidden') {
					$loopThrough['content_summary'] = 'grouping_summary_right';
				}
				
				if ($details['description_field'] != 'hidden') {
					$loopThrough['description'] = 'grouping_description_right';
				}
				
				if ($details['keywords_field'] != 'hidden') {
					$loopThrough['keywords'] = 'grouping_keywords_right';
				}
			}
			
			if ($menu = ze\menu::getFromContentItem($box['key']['source_cID'], $box['key']['cType'])) {
				$loopThrough['menu_text'] = 'grouping_menu_right';
			}
			
			foreach ($loopThrough as $fieldName => $fieldGrouping) {
				$ord = $box['tabs']['meta_data']['fields'][$fieldName]['ord'];
				$box['tabs']['meta_data']['fields']['translate_with_google_' . $fieldName] =
					[
						'class_name' => 'zenario_pro_features',
						'css_class' => 'zenario_text_button',
						'ord' => ++$ord,
						'value' => ze\admin::phrase('Translate with Google'),
						'type' => 'button',
						'format_onchange' => true,
						'grouping' => $fieldGrouping
					];
				
				if (!$googleTranslateApiKey) {
					$box['tabs']['meta_data']['fields']['translate_with_google_' . $fieldName]['disabled'] = true;
					
					$box['tabs']['meta_data']['fields']['translate_with_google_' . $fieldName]['side_note'] =
						ze\admin::phrase(
							'To use this feature, please add a Google Translation API key. See [[link_start]]site settings[[link_end]].',
							['link_start' => $linkStart, 'link_end' => $linkEnd]
						);
				}
			}
			
			$linkStart = "<a href='organizer.php#zenario__administration/panels/site_settings//api_keys~.site_settings~ttranslation~k{\"id\"%3A\"api_keys\"}' target='_blank'>";
			$linkEnd = "</a>";
	
			ze\lang::applyMergeFields($box['tabs']['meta_data']['notices']['translation_error']['message'], ['link_start' => $linkStart, 'link_end' => $linkEnd]);
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
    	if ($box['key']['translate']) {
    		$targetLanguage = $box['key']['target_language_id'];
			$box['tabs']['meta_data']['notices']['translation_error']['show'] = false;
			
			$loopThrough = [
				'title' => 'title_original'
			];
			
			if ($box['key']['cType'] && $details = ze\contentAdm::cTypeDetails($box['key']['cType'])) {
				if ($details['summary_field'] != 'hidden') {
					$loopThrough['content_summary'] = 'content_summary_original';
				}
				
				if ($details['description_field'] != 'hidden') {
					$loopThrough['description'] = 'description_original';
				}
				
				if ($details['keywords_field'] != 'hidden') {
					$loopThrough['keywords'] = 'keywords_original';
				}
			}
			
			if ($menu = ze\menu::getFromContentItem($box['key']['source_cID'], $box['key']['cType'])) {
				$loopThrough['menu_text'] = 'menu_original';
			}
			
			foreach ($loopThrough as $fieldName => $originalField) {
				if (isset($box['tabs']['meta_data']['fields']['translate_with_google_' . $fieldName])) {
					if (!empty($fields['translate_with_google_' . $fieldName]['pressed'])) {
						//This admin box works for both standard phrases and code-based phrases.
						//Work out what the value should be.
						$phraseToTranslate = $values['meta_data/' . $originalField];
						if ($phraseToTranslate) {
							$translatedPhrase = zenario_pro_features::translatePhrase($phraseToTranslate, $targetLanguage);
							
							if ($translatedPhrase) {
								$values['meta_data/'. $fieldName] = $translatedPhrase;
							} else {
								$values['meta_data/'. $fieldName] = '';
								$box['tabs']['meta_data']['notices']['translation_error']['show'] = true;
							}
						}
						
						if ($fieldName == 'menu_text') {
							$this->updateMenuPathPreview($box, $fields, $values);
						}
					}
					unset($fields['meta_data/translate_with_google_' . $fieldName]['pressed']);
				}
			}
    	}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//...your PHP code...//
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (ze\ring::engToBoolean($box['tabs']['meta_data']['edit_mode']['on'] ?? false)
		&& isset($box['tabs']['meta_data']['fields']['menu_invisible'])
		&& $values['meta_data/create_menu_node']
		&& ($menu = ze\menu::getFromContentItem($box['key']['cID'], $box['key']['cType']))) {
			ze\row::update(
			'menu_nodes',
			['invisible' => ze\ring::engToBoolean($values['meta_data/menu_invisible'])],
			$menu['id']);
		}
	}
	
	private function updateMenuPathPreview(&$box, &$fields, &$values) {
		$menuPos = false;
		$fields['meta_data/menu_path_preview']['hidden'] = true;
		
		if ($box['key']['from_cID'] && ($menu = ze\menu::getFromContentItem($box['key']['from_cID'], $box['key']['from_cType']))) {
			$menuPos = $menu['section_id']. '_'. $menu['id']. '_3';
		}
		
		if ($menuPos) {
			ze\menuAdm::setupPathPreview($menuPos, $fields['meta_data/menu_path_preview'], $values['meta_data/language_id']);
			$fields['meta_data/menu_path_preview']['hidden'] = false;
			
			$fields['meta_data/menu_path_preview']['value'] =
			$fields['meta_data/menu_path_preview']['current_value'] = $values['meta_data/menu_text'];
			
			if (!$values['meta_data/create_menu_node']) {
				$fields['meta_data/menu_path_preview']['hidden'] = true;
			}
		}
	}
}