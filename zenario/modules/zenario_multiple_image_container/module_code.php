<?php
/*
 * Copyright (c) 2015, Tribal Limited
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
		$imageIds = explode(',', $this->setting('image'));
		foreach ($imageIds as $imageId) {
			if (($imageId = (int) trim($imageId))
			 && ($image = getRow('files', array('alt_tag', 'title', 'floating_box_title'), $imageId))
			 && ((imageLink($width, $height, $url, $imageId, $this->setting('width'), $this->setting('height'), $this->setting('canvas'), $this->setting('offset'))))) {
				
				if (!isset($this->mergeFields['Images'])) {
					$this->mergeFields['Images'] = array();
				}
				
				$imageMF = array(
					'Alt' => $this->phrase($image['alt_tag']),
					'Src' => $url,
					'Title' => $this->phrase($image['title']),
					'Width' => $width,
					'Height' => $height,
					'Popout' => false);
				
				if ($this->setting('link_type') == '_ENLARGE_IMAGE'
				 && (imageLink($widthFullSize, $heightFullSize, $urlFullSize, $imageId, $this->setting('enlarge_width'), $this->setting('enlarge_height'), $this->setting('enlarge_canvas')))) {
					$imageMF['Floating_Box'] = array(
						'Src' => $urlFullSize,
						'Width' => $widthFullSize,
						'Height' => $heightFullSize,
						'Title' => $this->phrase($image['floating_box_title']));
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
	//public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
	//}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				parent::formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
				
				
				//Tweak/remove some of the banner plugin's options
				//(Note that these aren't done in our TUIX file because they would be
				// overridden in zenario_banner::formatAdminBox(),
				// so we need to override them here!)
				$fields['first_tab/image']['hidden'] =
				$fields['first_tab/canvas']['hidden'] = false;
				
				$fields['first_tab/use_rollover']['hidden'] =
				$fields['first_tab/rollover_image']['hidden'] =
				$fields['first_tab/alt_tag']['hidden'] =
				$fields['first_tab/floating_box_title']['hidden'] = true;
				
				$fields['text/more_link_text']['hidden'] = true;
				
				
				break;
		}
	}
	
	//public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
	//}
}
