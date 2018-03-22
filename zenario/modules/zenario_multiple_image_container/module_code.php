<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

class zenario_multiple_image_container extends zenario_banner {
	
	public function init() {
		$imageId = false;
		$fancyboxLink = false;
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = true, $clearByModuleData = false);
		
		$width = $height = $url = $widthFullSize = $heightFullSize = $urlFullSize = false;
		foreach (ze\ray::explodeAndTrim($this->setting('image'), true) as $imageId) {
			if (($imageId = (int) trim($imageId))
			 && ($image = ze\row::get('files', ['alt_tag', 'title', 'floating_box_title'], $imageId))
			 && ((ze\file::imageLink($width, $height, $url, $imageId, $this->setting('width'), $this->setting('height'), $this->setting('canvas'), $this->setting('offset'), $this->setting('retina'))))) {
				
				if (!isset($this->mergeFields['Images'])) {
					$this->mergeFields['Images'] = [];
				}
				
				$imageMF = [
					'Alt' => $this->phrase($image['alt_tag']),
					'Src' => $url,
					'Title' => $this->phrase($image['title']),
					'Width' => $width,
					'Height' => $height,
					'Popout' => false];
				
				if ($this->setting('link_type_'. $imageId) == '_ENLARGE_IMAGE'
				 && (ze\file::imageLink($widthFullSize, $heightFullSize, $urlFullSize, $imageId, $this->setting('enlarge_width'), $this->setting('enlarge_height'), $this->setting('enlarge_canvas')))) {
					
					$imageMF['Floating_Box'] = [
						'Src' => $urlFullSize,
						'Width' => $widthFullSize,
						'Height' => $heightFullSize,
						'Title' => $this->phrase($image['floating_box_title'])];
				} else {
					$cID = $cType = false;
					$this->setupLink($imageMF, $cID, $cType, $useTranslation = true, 'link_type_'. $imageId, 'hyperlink_target_'. $imageId, 'target_blank_'. $imageId, 'url_'. $imageId);
				}
				
				if ($text = $this->setting('image_title_'. $imageId)) {
					$imageMF['Text'] = $text;
				}
				
				$this->mergeFields['Images'][] = $imageMF;
			}
		}
		
		$this->mergeFields['Text'] = $this->setting('text');
		$this->mergeFields['Title'] = htmlspecialchars($this->setting('title'));
		$this->mergeFields['Title_Tags'] = $this->setting('title_tags') ? $this->setting('title_tags') : 'h2';
		
		if (!$this->isVersionControlled && $this->setting('translate_text')) {
			if ($this->mergeFields['Text']) {
				$this->replacePhraseCodesInString($this->mergeFields['Text']);
			}
			if ($this->mergeFields['Title']) {
				$this->mergeFields['Title'] = $this->phrase($this->mergeFields['Title']);
			}
		}
		
		//Don't show empty Banners
		//Note: If there is some more link text set, but no Image/Text/Title, then I'll still consider the Banner to be empty
		if (empty($this->mergeFields['Images'])
		 && empty($this->mergeFields['Text'])
		 && empty($this->mergeFields['Title'])) {
			$this->empty = true;
			return false;
			
		} else {
			return true;
		}
	}
	
	function showSlot() {
		if (!$this->empty) {
			//Display the Plugin
			$this->twigFramework($this->mergeFields);
		}
	}
	
	public function fillAdminSlotControls(&$controls) {
		//Do nothing special here
	}
	
	//For the most part we can use the Banner Module's admin box methods for our plugin settings,
	//however we will need a few tweaks as our plugin settings differ slightly
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		parent::fillAdminBox($path, $settingGroup, $box, $fields, $values);
		$box['css_class'] .= ' zenario_fab_multiple_image_container';
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
		
				//For every picked image on the Images tab, we need to make a copy of the template fields.
				$ord = 0;
				$valuesInDB = false;
				$enlargeImageOptionPicked = false;
				$imageIds = ze\ray::explodeAndTrim($values['first_tab/image']);
				foreach ($imageIds as $imageId) {
					if (!isset($box['tabs']['links']['fields']['image_'. $imageId])) {
						//Copy the template files, replacing znz with the id of the image
						$templateFields = json_decode(str_replace('znz', $imageId, json_encode($box['tabs']['links']['custom_template_fields'])), true);
				
						//Load the plugin setting values, if we've not yet done so
						if (!$valuesInDB) {
							ze\tuix::loadAllPluginSettings($box, $valuesInDB);
						}
				
						//For each template field, note down which image it was for, it's value if it had one saved,
						//then and add it to the links tab.
						foreach ($templateFields as $settingName => &$field) {
							$field['for_image'] = $imageId;
							if (isset($valuesInDB[$settingName])) {
								$field['value'] = $valuesInDB[$settingName];
								
								//Remember if anything was set to "Enlarge image in floating box"
								if ($field['value'] == '_ENLARGE_IMAGE') {
									$enlargeImageOptionPicked = true;
								}
							}
							$box['tabs']['links']['fields'][$settingName] = $field;
						}
						unset($field);
					}
			
					//Set the order of the fields.
					//We need to do this each time, as if someone rearranges the images, the fields will need to be rearranged as well.
					foreach ($box['tabs']['links']['custom_template_fields'] as $fieldName => &$field) {
						$settingName = str_replace('znz', $imageId, $fieldName);
						$box['tabs']['links']['fields'][$settingName]['ord'] = ++$ord;
					}
					
					//Remember if anything was set to "Enlarge image in floating box"
					if (isset($values['links/link_type_'. $imageId])
					 && $values['links/link_type_'. $imageId] == '_ENLARGE_IMAGE') {
						$enlargeImageOptionPicked = true;
					}
				}
		
				//Handle removed images by hiding all of their fields
				foreach ($box['tabs']['links']['fields'] as $settingName => &$field) {
					if (isset($field['for_image'])) {
						$field['hidden'] = !in_array($field['for_image'], $imageIds);
					}
				}
				
				//Try to use the Banner plugin's formatAdminBox() method for some things
				$fields['first_tab/image_source']['hidden'] = true;
				$values['first_tab/link_type'] = $enlargeImageOptionPicked? '_ENLARGE_IMAGE' : '';
				parent::formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
				
				//Tweak/remove some of the banner plugin's options
				//(Note that these aren't done in our TUIX file because they would be
				// overridden in zenario_banner::formatAdminBox(),
				// so we need to override them here!)
				foreach ($box['tabs']['first_tab']['fields'] as $fieldName => &$field) {
					
					//Ignore core fields here
					if (isset($field['class_name']) && $field['class_name'] == 'zenario_common_features') {
						continue;
					}
					
					switch ($fieldName) {
						//On the first tab, always show the image and canvas options...
						case 'image':
						case 'canvas':
						case 'lazy_load':
							$field['hidden'] = false;
							break;
						
						//...use the logic from the Banner plugin for some fields...
						case 'width':
						case 'height':
						case 'offset':
						case 'retina':
						case 'enlarge_canvas':
						case 'enlarge_width':
						case 'enlarge_height':
							break;
						
						//...and always hide anything else
						default:
							$field['hidden'] = true;
							unset($field['plugin_setting']);
							continue 2;
					}
					
					//The multiple image container plugin uses one level less of indents than the banner plugin
					//When we first open the FAB, reduce any indent by 1
					if (isset($field['indent']) && ($_REQUEST['_fill'] ?? false)) {
						$field['indent'] -= 1;
					}
				}
				
				$fields['text/text']['hidden'] =
				$fields['text/more_link_text']['hidden'] = true;
				unset($fields['text/text']['plugin_setting']);
				unset($fields['text/more_link_text']['plugin_setting']);
				
				
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$imageIds = ze\ray::explodeAndTrim($values['first_tab/image']);
		foreach ($imageIds as $imageId) {
			if (!empty($values['links/link_type_'. $imageId])) {
				switch ($values['links/link_type_'. $imageId]) {
					case '_CONTENT_ITEM':
						if (!$values['links/hyperlink_target_'. $imageId]) {
							$box['tabs']['links']['errors']['no_content_item'] = ze\admin::phrase('Please select a content item');
						}
						break;
					
					case '_EXTERNAL_URL':
						if (!$values['links/url_'. $imageId]) {
							$box['tabs']['links']['errors']['no_url'] = ze\admin::phrase('Please enter a URL');
						}
						break;
				}
			}
		}
	}
}
