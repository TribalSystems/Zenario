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


class zenario_banner__admin_boxes__plugin_settings extends ze\moduleBaseClass {
	
	

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$fields['image_and_link/image']['side_note'] =
		$fields['image_and_link/mobile_image']['side_note'] =
		$fields['image_and_link/rollover_image']['side_note'] = 
			ze\admin::phrase('If a JPG or PNG image is selected, Zenario will create and display a WebP version of the image. Fallback logic will be used for browsers which do not support WebP.');
		
		//For Wireframe Plugins, pick images from this item's images, rather than 
		if ($box['key']['isVersionControlled']/*
		 && ze\content::isDraft($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'])*/) {
			$box['tabs']['image_and_link']['fields']['image']['pick_items']['path'] =
			$box['tabs']['image_and_link']['fields']['rollover_image']['pick_items']['path'] =
				'zenario__content/panels/content/item_buttons/images//'. $box['key']['cType']. '_'. $box['key']['cID']. '//';
			
			$box['tabs']['image_and_link']['fields']['image']['pick_items']['min_path'] =
			$box['tabs']['image_and_link']['fields']['image']['pick_items']['max_path'] =
			$box['tabs']['image_and_link']['fields']['image']['pick_items']['target_path'] =
			$box['tabs']['image_and_link']['fields']['rollover_image']['pick_items']['min_path'] =
			$box['tabs']['image_and_link']['fields']['rollover_image']['pick_items']['max_path'] =
			$box['tabs']['image_and_link']['fields']['rollover_image']['pick_items']['target_path'] =
				'zenario__content/panels/image_library';
		}
		
		$box['first_display'] = true;
		
		//Banner Plugins should have a note that appears below their settings if they are in a nest,
		//explaining that they may be overwritten by the global settings.
		//However in Modules that extend the banner, these should not be visible.
		if ((!empty($box['key']['eggId']))
		 && ($nestedPlugin = ze\pluginAdm::getNestDetails($box['key']['eggId']))
		 && (ze\module::className($nestedPlugin['module_id']) == 'zenario_banner')) {
			$box['tabs']['image_and_link']['fields']['canvas']['note_below'] =
			$box['tabs']['image_and_link']['fields']['enlarge_canvas']['note_below'] =
				ze\admin::phrase('If placed in a nest or slideshow, the canvas size setting will be overridden by the setting of the nest or slideshow.');
		}

		$box['tabs']['first_tab']['fields']['anchor_name']['validation']['no_special_characters'] =
			$box['tabs']['first_tab']['fields']['anchor_name']['validation']['no_spaces'] =
			$box['tabs']['first_tab']['fields']['anchor_name']['validation']['no_commas'] =
				ze\admin::phrase('Anchor name cannot contain spaces or special characters.');
		
		$box['tabs']['first_tab']['fields']['anchor_name']['oninput'] = '
			var side_note = document.getElementById("row__anchor_name").getElementsByClassName("zenario_note_content")[0];
			var anchor_name = document.getElementById("row__anchor_name").getElementsByClassName("zfab_row_fields")[0].firstElementChild.value;

			if (!anchor_name) {
				anchor_name = "[anchorname]";
			}

			if (side_note) {
				side_note.textContent =
					"You can link to this anchor using #" + anchor_name + ". Please make sure your anchor is unique within the page on which you place this plugin.";
			}';
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (!ze\module::inc('zenario_ctype_picture')) {
			unset($fields['image_and_link/image_source']['values']['_PICTURE']);
		}
		
		$retinaSideNote = "If the source image is large enough,
                            the resized image will be output at twice its displayed width &amp; height
                            to appear crisp on retina screens.
                            This will increase the download size.
                            <br/>
                            If the source image is not large enough this will have no effect.";
	
		
		$fields['image_and_link/use_rollover']['hidden'] = 
		$fields['image_and_link/image']['hidden'] = 
			$values['image_and_link/image_source'] != '_CUSTOM_IMAGE';
		
		$fields['image_and_link/rollover_image']['hidden'] = 
			$values['image_and_link/advanced_behaviour'] != 'use_rollover'
			|| !($values['image_and_link/image_source'] == '_CUSTOM_IMAGE');
			
		//TODO disable using rollover images when the source isn't "Show an image"
		$fields['image_and_link/advanced_behaviour']['values']['use_rollover']['disabled'] =
			!($values['image_and_link/image_source'] == '_CUSTOM_IMAGE');
		
		$fields['image_and_link/picture']['hidden'] =
			$values['image_and_link/image_source'] != '_PICTURE';
		
		//Check whether an image is picked
		$cID = $cType = $pictureCID = $pictureCType = $imageId = $imagePicked = false;
		
		//A little hack to make extending this module easier
		//If the extending module doesn't use the "image_source" field, just check whether
		//the image field is empty or not
		if (empty($fields['image_and_link/image_source'])
		 || !empty($fields['image_and_link/image_source']['hidden'])) {
			$imagePicked = !empty($values['image_and_link/image']);
		
		//Otherwise run through the full logic for different types of images
		} else {
			$cID = $cType = $pictureCID = $pictureCType = $imageId = false;
			
			(($values['image_and_link/image_source'] == '_CUSTOM_IMAGE'
			  && ($imageId = $values['image_and_link/image']))
		
			 || ($values['image_and_link/image_source'] == '_PICTURE'
			  && (ze\content::getCIDAndCTypeFromTagId($pictureCID, $pictureCType, $values['image_and_link/picture']))
			  && ($imageId = ze\row::get("versions", "file_id", ["id" => $pictureCID, 'type' => $pictureCType, "version" => ze\content::version($pictureCID, $pictureCType)])))
		 
			 || ($values['image_and_link/image_source'] == '_STICKY_IMAGE'
			  && (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $values['image_and_link/hyperlink_target']))
			  && ($imageId = ze\file::itemStickyImageId($cID, $cType))));
			
			$imagePicked = (bool) $imageId;
		}
		
		
		if (!empty($fields['image_and_link/link_type']['values'])) {
			
			$onlyShowLinkToContent = false;
			if ($values['image_and_link/image_source'] == '_STICKY_IMAGE') {
				$values['image_and_link/link_type'] = '_CONTENT_ITEM';
				$onlyShowLinkToContent = true;
			}
			
			$fields['image_and_link/link_type']['values']['_NO_LINK']['disabled'] =
			$fields['image_and_link/link_type']['values']['_EXTERNAL_URL']['disabled'] =
			$fields['image_and_link/link_type']['values']['_EMAIL']['disabled'] = $onlyShowLinkToContent;
			
		}
		
		$fields['image_and_link/alt_tag']['hidden'] = 
		$fields['image_and_link/retina']['hidden'] = 
		$hidden = 
			!$imagePicked;
		
		$this->showHideImageOptions($fields, $values, 'image_and_link', $hidden);
		if ($values['image_and_link/canvas'] != "unlimited") {
			$fields['image_and_link/canvas']['side_note'] = $retinaSideNote;
		} else {
			$fields['image_and_link/canvas']['side_note'] = "";
		}
		
		$fields['image_and_link/floating_box_title']['hidden'] = 
			!$imagePicked
		 || $values['image_and_link/image_source'] == '_STICKY_IMAGE'
		 || $values['image_and_link/link_type'] != '_ENLARGE_IMAGE';
		
		$fields['image_and_link/stretched_image_warning']['hidden'] = true;

		if ($imagePicked
		 && $imageId
		 && ($image = ze\row::get('files', ['width', 'height', 'alt_tag', 'title', 'floating_box_title', 'mime_type'], $imageId))) {
			$editModeOn = ze\ring::engToBoolean($box['tabs']['image_and_link']['edit_mode']['on'] ?? false);
			
			if ($box['first_display'] && !$values['image_and_link/alt_tag']) {
				$fields['image_and_link/alt_tag']['value'] = $image['alt_tag'];
			}
			if ($editModeOn && !$values['image_and_link/alt_tag']) {
				$fields['image_and_link/alt_tag']['current_value'] = $image['alt_tag'];
			}
			
			if ($image['floating_box_title']) {
				$merge = '"' . $image['floating_box_title'] . '"';
			} else {
				$merge = 'No caption set';
			}
			$fields['image_and_link/floating_box_title_mode']['values']['use_default']['label'] = 'Use the image\'s default floating box caption (' . htmlspecialchars($merge) .')';
				
			if ($box['first_display']) {
				if (!$values['image_and_link/floating_box_title']) {
					$fields['image_and_link/floating_box_title']['value'] = $image['floating_box_title'];
				}
			}
			
			if ($values['image_and_link/canvas'] == "resize_and_crop") {
				$mimeType = $image['mime_type'];
				if ($mimeType != 'image/svg+xml'
					&& ($values['image_and_link/width'] > $image['width'] || $values['image_and_link/height'] > $image['height']
					)
				) {
					$fields['image_and_link/stretched_image_warning']['hidden'] = false;
				}
			}
		}
		
		$box['first_display'] = false;
		
		$fields['image_and_link/hyperlink_target']['hidden'] = 
		$fields['image_and_link/hide_private_item']['hidden'] = 
		$fields['image_and_link/use_download_page']['hidden'] = 
			$values['image_and_link/link_type'] != '_CONTENT_ITEM';

		$fields['image_and_link/get_translation']['hidden'] = 
			$values['image_and_link/link_type'] != '_CONTENT_ITEM'
		 || $box['key']['isVersionControlled']
		 || ze\lang::count() < 2;

		$fields['first_tab/more_link_text']['hidden'] = 
		$fields['image_and_link/target_blank']['hidden'] = 
			$values['image_and_link/link_type'] != '_CONTENT_ITEM'
		 && $values['image_and_link/link_type'] != '_DOCUMENT'
		 && $values['image_and_link/link_type'] != '_EXTERNAL_URL'
		 && $values['image_and_link/link_type'] != '_EMAIL';

		$fields['image_and_link/use_translation']['hidden'] = 
			$values['image_and_link/link_type'] != '_CONTENT_ITEM'
		 || $box['key']['isVersionControlled']
		 || ze\lang::count() < 2;

		$fields['image_and_link/url']['hidden'] = 
			$values['image_and_link/link_type'] != '_EXTERNAL_URL';
		
		if ($values['image_and_link/link_type'] == '_EMAIL') {
			$fields['image_and_link/email_address']['hidden'] = false;
			if (!empty($values['image_and_link/email_address']) && !strstr($values['image_and_link/email_address'], 'mailto:')) {
				$values['image_and_link/email_address'] = 'mailto:' . $values['image_and_link/email_address'];
			}
		} else {
			$fields['image_and_link/email_address']['hidden'] = true;
		}
		
		$hidden = $values['image_and_link/link_type'] != '_ENLARGE_IMAGE';
		$this->showHideImageOptions($fields, $values, 'image_and_link', $hidden, 'enlarge_');
		if ($values['image_and_link/enlarge_canvas'] != "unlimited") {
			$fields['image_and_link/enlarge_canvas']['side_note'] = $retinaSideNote;
		} else {
			$fields['image_and_link/enlarge_canvas']['side_note'] = "";
		}
		
		$cID = $cType = false;
		if ($values['image_and_link/link_type'] == '_CONTENT_ITEM'
		 && (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $values['image_and_link/hyperlink_target']))
		 && ($cType == 'document')) {
			$fields['image_and_link/use_download_page']['hidden'] = false;
		} else {
			$fields['image_and_link/use_download_page']['current_value'] = false;
			$fields['image_and_link/use_download_page']['hidden'] = true;
		}
		
		//Don't show the translations checkbox if this can never be translated
		$fields['first_tab/translate_text']['hidden'] =
			$box['key']['isVersionControlled']
		 || !ze\row::exists('languages', ['translate_phrases' => 1]);
		
		//Don't show notes about translations if this won't be translated
		if ($fields['first_tab/translate_text']['hidden'] || !$values['first_tab/translate_text']) {
			$fields['first_tab/text']['show_phrase_icon'] =
			$fields['first_tab/title']['show_phrase_icon'] =
			$fields['first_tab/more_link_text']['show_phrase_icon'] = false;
			
			$fields['first_tab/text']['note_below'] =
			$fields['first_tab/title']['note_below'] =
			$fields['first_tab/more_link_text']['note_below'] = '';
		
		} else {
			
			$mrg = [
				'def_lang_name' => htmlspecialchars(ze\lang::name(ze::$defaultLang)),
				'phrases_panel' => htmlspecialchars(ze\link::absolute(). 'organizer.php#zenario__languages/panels/phrases')
			];
			
			$fields['first_tab/text']['show_phrase_icon'] =
			$fields['first_tab/title']['show_phrase_icon'] =
			$fields['first_tab/more_link_text']['show_phrase_icon'] = true;
			
			$fields['first_tab/title']['side_note'] = 
			$fields['first_tab/more_link_text']['side_note'] = 
                                ze\admin::phrase('
                                        To make a phrase that gets managed by the translation system, check the checkbox to make this plugin multilingual, and enter text in [[def_lang_name]] between double square brackets, [[Like this]], wherever you see the speech bubbles icon.<br />
                                        <a href="[[phrases_panel]]" target="_blank">View phrases</a>.',
                                                ['def_lang_name' => $mrg['def_lang_name'], 'Like this' => '[[Like this]]', 'phrases_panel' => $mrg['phrases_panel']]
                                );
		}
		

		//Only show the image picker if "Mobile behaviour" is set to "Different image".
		$fields['image_and_link/mobile_image']['hidden'] = $values['image_and_link/mobile_behaviour'] != 'mobile_change_image';
		
		//Only show mobile options if "Mobile behaviour" is set to either "Different image", or "Same image, different size".
		$hideMobileOptions = (
			($values['image_and_link/mobile_behaviour'] != 'mobile_change_image')
			&& ($values['image_and_link/mobile_behaviour'] != 'mobile_same_image_different_size')
			);
			
		$this->showHideImageOptions($fields, $values, 'image_and_link', $hideMobileOptions, 'mobile_');
		if ($values['image_and_link/mobile_canvas'] != "unlimited") {
			$fields['image_and_link/mobile_canvas']['side_note'] = $retinaSideNote;
		} else {
			$fields['image_and_link/mobile_canvas']['side_note'] = "";
		}
		
		//Lazy load - only works if Mobile Behaviour is set to "Same image".
		if ($values['image_and_link/mobile_behaviour'] != 'mobile_same_image') {
			$fields['image_and_link/advanced_behaviour']['values']['lazy_load']['disabled'] = true;
			$fields['image_and_link/advanced_behaviour']['side_note'] = ze\admin::phrase('Lazy load may not be used if Mobile Behaviour is not set to "Same image". There is no WebP support for lazy loading.');
		} else {
			$fields['image_and_link/advanced_behaviour']['values']['lazy_load']['disabled'] = false;
			unset($fields['image_and_link/advanced_behaviour']['side_note']);
		}
		
		if ($values['image_and_link/advanced_behaviour'] == 'lazy_load') {
			$fields['image_and_link/mobile_behaviour']['values']['mobile_same_image_different_size']['disabled'] = 
			$fields['image_and_link/mobile_behaviour']['values']['mobile_change_image']['disabled'] = 
			$fields['image_and_link/mobile_behaviour']['values']['mobile_hide_image']['disabled'] = true;
			$fields['image_and_link/mobile_behaviour']['note_below'] = ze\admin::phrase('If lazy load is enabled, only "Same image" setting may be used.');

			$fields['image_and_link/advanced_behaviour']['note_below'] = ze\admin::phrase('There is no WebP support for lazy loading.');
		} else {
			$fields['image_and_link/mobile_behaviour']['values']['mobile_same_image_different_size']['disabled'] = 
			$fields['image_and_link/mobile_behaviour']['values']['mobile_change_image']['disabled'] = 
			$fields['image_and_link/mobile_behaviour']['values']['mobile_hide_image']['disabled'] = false;
			unset($fields['image_and_link/mobile_behaviour']['note_below']);

			if ($values['image_and_link/advanced_behaviour'] == 'use_rollover') {
				$fields['image_and_link/advanced_behaviour']['note_below'] = ze\admin::phrase("WebP images will be generated, with fallback for browsers not supporting WebP.");
			} else {
				$fields['image_and_link/advanced_behaviour']['note_below'] = ze\admin::phrase("WebP image will be generated, with fallback for browsers not supporting WebP.");
			}
		}
		
		if ($values['first_tab/set_an_anchor']) {
			$anchorName = $values['first_tab/anchor_name'] ?: '[anchorname]';
			$box['tabs']['first_tab']['fields']['anchor_name']['side_note'] =
				ze\admin::phrase(
					'You can link to this anchor using #[[anchor_name]]. Please make sure your anchor is unique within the page on which you place this plugin.',
					['anchor_name' => htmlspecialchars($anchorName)]
				);
		}
		
		/////////////////////////
		//	Privacy warning:  //
		///////////////////////
		
		//1) Link to a document:
		
		//Get selected document...
		if (isset($values['image_and_link/document_id'])) {
			$documentId = $values['image_and_link/document_id'];
		}

		//...get privacy settings of the document and content item...
		$document = ze\row::get('documents', ['filename', 'privacy'], ['id' => $documentId]);
		$contentItemPrivacy = ze\row::get('translation_chains', 'privacy', ['equiv_id' => $box['key']['cID']]);

		//...and display or hide a privacy warning note if necessary.
		
		if ($document && $document['privacy'] == 'private' && ($contentItemPrivacy == 'public' || $contentItemPrivacy == 'logged_out')) {
			$box['tabs']['image_and_link']['fields']['privacy_warning']['note_below'] = '<p>Warning: this content item is public, the selected document is private, so it will not appear to visitors.</p>';
		} elseif ($document && $document['privacy'] == 'offline') {
			$box['tabs']['image_and_link']['fields']['privacy_warning']['note_below'] = '<p>Warning: the selected document is offline, so it will not appear to visitors. Change the privacy of the document to make it available.</p>';
		} else {
			$box['tabs']['image_and_link']['fields']['privacy_warning']['note_below'] = '';
		}
		
		//2) Link to a content item:
		
		//Get the privacy of the target content item...
		if ($values['image_and_link/link_type'] == '_CONTENT_ITEM') {
			if (!empty($values['image_and_link/hyperlink_target'])) {
				$targetContentItem = $values['image_and_link/hyperlink_target'];
				$cID = $cType = false;
				ze\content::getCIDAndCTypeFromTagId($cID, $cType, $targetContentItem);
			
				$contentItemPrivacy = ze\row::get('translation_chains', 'privacy', ['equiv_id' => $cID, 'type' => $cType]);
				
				//...and display it to the admin...
				$fields['image_and_link/hide_private_item']['note_below'] = '<p>Selected content item privacy setting is:</p><p>"' . ze\contentAdm::privacyDesc($contentItemPrivacy) . '"</p>';
				$fields['image_and_link/hide_private_item']['indent'] = 2;
				$fields['image_and_link/target_blank']['indent'] = 2;
			} else {
				//...or don't show the note at all if no content item is selected.
				$fields['image_and_link/hide_private_item']['indent'] = 1;
				$fields['image_and_link/target_blank']['indent'] = 1;
				unset($fields['image_and_link/hide_private_item']['note_below']);
			}
		}
	}

	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (!empty($fields['image_and_link/alt_tag'])
		 && empty($fields['image_and_link/alt_tag']['hidden'])
		 && $changes['image_and_link/alt_tag']
		 && !$values['image_and_link/alt_tag']) {
			$box['tabs']['image_and_link']['errors'][] = ze\admin::phrase('Please enter an alt-tag.');
		}
		
		if (!empty($fields['image_and_link/floating_box_title'])
		 && empty($fields['image_and_link/floating_box_title']['hidden'])
		 && $changes['image_and_link/floating_box_title']
		 && !$values['image_and_link/floating_box_title']) {
			$box['tabs']['image_and_link']['errors'][] = ze\admin::phrase('Please enter a floating box title attribute.');
		}
		
		//Convert all absolute URLs in the HTML Text to relative URLs when saving
		foreach (['value', 'current_value'] as $value) {
			if (isset($box['tabs']['first_tab']['fields']['text'][$value])) {
				foreach (['"', "'"] as $quote) {
					$box['tabs']['first_tab']['fields']['text'][$value] = 
						str_replace(
							$quote. htmlspecialchars(ze\link::absolute()),
							$quote,
							$box['tabs']['first_tab']['fields']['text'][$value]);
				}
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
	
	
	
	
}
