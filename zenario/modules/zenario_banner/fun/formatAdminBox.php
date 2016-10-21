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


switch ($path) {
	case 'plugin_settings':
		if (!inc('zenario_ctype_picture')) {
			unset($fields['first_tab/image_source']['values']['_PICTURE']);
		}
	
		
		$fields['first_tab/use_rollover']['hidden'] = 
		$fields['first_tab/image']['hidden'] = 
			$values['first_tab/image_source'] != '_CUSTOM_IMAGE';
		
		$fields['first_tab/rollover_image']['hidden'] = 
			$fields['first_tab/use_rollover']['hidden']
		 || !$values['first_tab/use_rollover'];
		
		$fields['first_tab/picture']['hidden'] =
			$values['first_tab/image_source'] != '_PICTURE';
		
		//Check whether an image is picked
		$cID = $cType = $pictureCID = $pictureCType = $imageId = $imagePicked = false;
		
		//A little hack to make extended this module easier
		//If the extending module doesn't use the "image_source" field, just check whether
		//the image field is empty or not
		if (empty($fields['first_tab/image_source'])
		 || !empty($fields['first_tab/image_source']['hidden'])) {
			$imagePicked = !empty($values['first_tab/image']);
		
		//Otherwise run through the full logic for different types of images
		} else {
			$cID = $cType = $pictureCID = $pictureCType = $imageId = false;
			
			(($values['first_tab/image_source'] == '_CUSTOM_IMAGE'
			  && ($imageId = $values['first_tab/image']))
		
			 || ($values['first_tab/image_source'] == '_PICTURE'
			  && (getCIDAndCTypeFromTagId($pictureCID, $pictureCType, $values['first_tab/picture']))
			  && ($imageId = getRow("versions", "file_id", array("id" => $pictureCID, 'type' => $pictureCType, "version" => contentVersion($pictureCID, $pictureCType)))))
		 
			 || ($values['first_tab/image_source'] == '_STICKY_IMAGE'
			  && (getCIDAndCTypeFromTagId($cID, $cType, $values['first_tab/hyperlink_target']))
			  && ($imageId = itemStickyImageId($cID, $cType))));
			
			$imagePicked = (bool) $imageId;
		}
		
		
		if (!empty($fields['first_tab/link_type']['values'])) {
			
			$onlyShowLinkToContent = false;
			if ($values['first_tab/image_source'] == '_STICKY_IMAGE'){
				$values['first_tab/link_type'] = '_CONTENT_ITEM';
				$onlyShowLinkToContent = true;
			}
			
			$fields['first_tab/link_type']['values']['_NO_LINK']['disabled'] =
			$fields['first_tab/link_type']['values']['_EXTERNAL_URL']['disabled'] = $onlyShowLinkToContent;
			
		}
		
		$fields['first_tab/alt_tag']['hidden'] = 
		$fields['first_tab/retina']['hidden'] = 
		$hidden = 
			!$imagePicked;
		
		$this->showHideImageOptions($fields, $values, 'first_tab', $hidden);
		
		$fields['first_tab/floating_box_title']['hidden'] = 
			!$imagePicked
		 || $values['first_tab/image_source'] == '_STICKY_IMAGE'
		 || $values['first_tab/link_type'] != '_ENLARGE_IMAGE';
		
		if ($imagePicked
		 && $imageId
		 && ($image = getRow('files', array('width', 'height', 'alt_tag', 'title', 'floating_box_title'), $imageId))) {
			$editModeOn = engToBooleanArray($box['tabs']['first_tab'], 'edit_mode', 'on');
			
			$fields['first_tab/alt_tag']['multiple_edit']['original_value'] = $image['alt_tag'];
			if ($box['first_display'] && !$values['first_tab/alt_tag']) {
				$fields['first_tab/alt_tag']['value'] = $image['alt_tag'];
			}
			if ($editModeOn && !$changes['first_tab/alt_tag']) {
				$fields['first_tab/alt_tag']['current_value'] = $image['alt_tag'];
			}

			$fields['first_tab/floating_box_title']['multiple_edit']['original_value'] = $image['floating_box_title'];
			if ($box['first_display'] && !$values['first_tab/floating_box_title']) {
				$fields['first_tab/floating_box_title']['value'] = $image['floating_box_title'];
			}
			if ($editModeOn && !$changes['first_tab/floating_box_title']) {
				$fields['first_tab/floating_box_title']['current_value'] = $image['floating_box_title'];
			}
		
			if	(($values['first_tab/image_source']  == '_CUSTOM_IMAGE' && !($values['first_tab/use_rollover']))
				|| $values['first_tab/image_source']  == '_PICTURE' ) {
			} else {
				if ($values['first_tab/link_type']=='_ENLARGE_IMAGE') {
					$fields['first_tab/link_type']['current_value'] = '_NO_LINK';
					$fields['first_tab/link_type']['value'] = '_NO_LINK';
				}
			}
				
		} else {
			$fields['first_tab/alt_tag']['multiple_edit']['original_value'] = '';
			$fields['first_tab/floating_box_title']['multiple_edit']['original_value'] = '';
		}
		
		$box['first_display'] = false;
		
		
		//if (isset($fields['text/use_phrases'])) {
		//	$fields['text/use_phrases']['hidden'] =
		//		getNumLanguages() <= 1
		//	 && strpos($values['text/text'], '[[') === false
		//	 && strpos($values['text/text'], ']]') === false
		//	 && strpos($values['text/title'], '[[') === false
		//	 && strpos($values['text/title'], ']]') === false;
		//}
		
		
		$fields['first_tab/hyperlink_target']['hidden'] = 
		$fields['first_tab/hide_private_item']['hidden'] = 
		$fields['first_tab/use_download_page']['hidden'] = 
			$values['first_tab/link_type'] != '_CONTENT_ITEM';

		$fields['first_tab/get_translation']['hidden'] = 
			$values['first_tab/link_type'] != '_CONTENT_ITEM'
		 || $box['key']['isVersionControlled']
		 || getNumLanguages() < 2;

		$fields['text/more_link_text']['hidden'] = 
		$fields['first_tab/target_blank']['hidden'] = 
			$values['first_tab/link_type'] != '_CONTENT_ITEM'
		 && $values['first_tab/link_type'] != '_EXTERNAL_URL';

		$fields['first_tab/use_translation']['hidden'] = 
			$values['first_tab/link_type'] != '_CONTENT_ITEM'
		 || $box['key']['isVersionControlled']
		 || getNumLanguages() < 2;

		$fields['first_tab/url']['hidden'] = 
			$values['first_tab/link_type'] != '_EXTERNAL_URL';
		
		$hidden = $values['first_tab/link_type'] != '_ENLARGE_IMAGE';
		$this->showHideImageOptions($fields, $values, 'first_tab', $hidden, 'enlarge_');
		
		$cID = $cType = false;
		if ($values['first_tab/link_type'] == '_CONTENT_ITEM'
		 && (getCIDAndCTypeFromTagId($cID, $cType, $values['first_tab/hyperlink_target']))
		 && ($cType == 'document')) {
			$fields['first_tab/use_download_page']['hidden'] = false;
		} else {
			$fields['first_tab/use_download_page']['current_value'] = false;
			$fields['first_tab/use_download_page']['hidden'] = true;
		}
		
		//Don't show the translations checkbox if this can never be translated
		$fields['text/translate_text']['hidden'] =
			$box['key']['isVersionControlled']
		 || !checkRowExists('languages', array('translate_phrases' => 1));
		
		//Don't show notes about translations if this won't be translated
		if ($fields['text/translate_text']['hidden'] || !$values['text/translate_text']) {
			$fields['text/text']['show_phrase_icon'] =
			$fields['text/title']['show_phrase_icon'] =
			$fields['text/more_link_text']['show_phrase_icon'] = false;
			
			$fields['text/text']['note_below'] =
			$fields['text/title']['side_note'] =
			$fields['text/more_link_text']['side_note'] = '';
		
		} else {
			
			$mrg = array(
				'def_lang_name' => htmlspecialchars(getLanguageName(setting('default_language'))),
				'phrases_panel' => htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__languages/panels/phrases')
			);
			
			$fields['text/text']['show_phrase_icon'] =
			$fields['text/title']['show_phrase_icon'] =
			$fields['text/more_link_text']['show_phrase_icon'] = true;
			
			$fields['text/text']['note_below'] = 
				adminPhrase('To use the phrases system, place your text in double square brackes [[like this]]. This must be in [[def_lang_name]], this site\'s default language.', $mrg);
			
			$fields['text/title']['side_note'] =
			$fields['text/more_link_text']['side_note'] =
				adminPhrase('Enter text in [[def_lang_name]], this site\'s default language. <a href="[[phrases_panel]]" target="_blank">Click here to manage translations in Organizer</a>.', $mrg);
		}
		

		break;
}
