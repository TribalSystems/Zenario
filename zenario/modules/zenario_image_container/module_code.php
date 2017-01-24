<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

class zenario_image_container extends zenario_banner {
	
	static protected $page_plugin_index_start = 1;
	static protected $page_plugin_index_end = 0;
	static protected $all_page_plugin_images = array();
	protected $my_page_plugin_index = 0;
	
	protected $images = array();
	
	public function init(){
		$result = parent::init();
		if (!$result) return $result;
		
		//We need to index all of the zenario_image_containers current on the page
		if (request('method_call') == 'refreshPlugin') {
			//If we're reloading via AJAX, generate an index that won't clash with anything already on the page
			$this->my_page_plugin_index = self::$page_plugin_index_start = self::$page_plugin_index_end =
				(time() % 1000) * 1000 + rand(0, 9999);
		} else {
			//If we're not reloading via AJAX, start counting from 1
			$this->my_page_plugin_index = ++self::$page_plugin_index_end;
		}
		
		
		if (isset($this->mergeFields['Image_Src'])) {
			$this->images['image_main'] = array(
				'src_url' => $this->mergeFields['Image_Src'],
				'img_height' => $this->mergeFields['Image_Height'],
				'img_width' => $this->mergeFields['Image_Width'],
				'custom_css_code' => $this->setting('custom_css_code')
				);
			if (isset($this->mergeFields['Image_Rollover'])) {
				$rollover_image = $this->mergeFields['Image_Rollover'];
				$this->images['image_hover'] = array(
						'src_url' => $rollover_image['Image_Src'],
						'img_height' => $rollover_image['Image_Height'],
						'img_width' => $rollover_image['Image_Width'],
				);
			}
			
			if ($this->setting('mobile_behavior') == 'change_image') {
				$imageId = $this->setting('mobile_image');
				$width = $this->setting('mobile_width');
				$height = $this->setting('mobile_height');
				$url = null;
				if (imageLink($width, $height, $url, $imageId, $width, $height, $this->setting('mobile_canvas'), $this->setting('mobile_offset'))) {
					$this->images['image_mobile'] = array(
							'src_url' => htmlspecialchars($url),
							'img_height' => $height,
							'img_width' => $width,
							'custom_css_code' => $this->setting('mobile_custom_css_code')
					);
				}
			}
		} 
		
		//If we're reloading via AJAX, we need to call a JavaScript function to add the style to the head.
		//Otherwise we can use addToPageHead() below.
		if (request('method_call') == 'refreshPlugin') {
			$this->callScriptBeforeAJAXReload('zenario_image_container', 'addToPageHead', $this->generateStyles());
		}
		
		return $result;
	}
	
	public function addToPageHead() {
		//Note: Old versions of IE need media-queries in stylesheets, they won't work inline on the page.
		echo '
<!--[if gt IE 8]><!-->
	<style type="text/css">', $this->generateStyles(), '
	</style>
<!--<![endif]-->
<!--[if lte IE 8]>
	<link rel="stylesheet" type="text/css" media="screen" href="', htmlspecialchars($this->pluginAJAXLink('generateStyles=1')), '"/>
<![endif]-->';
	}
	
	public function handlePluginAJAX() {
		if (get('generateStyles')) {
			header('Content-Type: text/css; charset=UTF-8');
			echo $this->generateStyles($fullPath = true);
		}
	}
	
	public function generateStyles($fullPath = false) {
		$html = '';
		
		if (!empty($this->images)) {
			$min_width_px = setting('image_mobile_resize_point');
			$min_width_px_plus_one = $min_width_px+1;

			$mobile_behavior = $this->setting('mobile_behavior');
			//we will not load the main image on media queries less than $min_width_px+1
			$hide_image = ($mobile_behavior == 'hide_image' || isset($this->images['image_mobile'])) ? 
				"@media (min-width: {$min_width_px_plus_one}px) {" : false;

			if (isset($this->images['image_main'])) {
				$image = $this->images['image_main'];
				$imageId = 'zic_'. $this->containerId;
				
				if ($fullPath && !chopPrefixOffOfString($image['src_url'], 'http')) {
					$image['src_url'] = absCMSDirURL(). $image['src_url'];
				}
				
				//Bugfix - URLs should not be html escaped in CSS code.
				$image['src_url'] = html_entity_decode($image['src_url']);
				
				if ($hide_image) {
					$html .= "\n\t\t". $hide_image;
				}
				$html .= "
		#{$imageId} {
			display:block;
			height: {$image['img_height']}px;
			width: {$image['img_width']}px;
			background-image: url('{$image['src_url']}');
			background-repeat: no-repeat;
			{$image['custom_css_code']}
		}";
			
				if (isset($this->images['image_hover'])) {
				
					if ($fullPath && !chopPrefixOffOfString($image['src_url'], 'http')) {
						$image['src_url'] = absCMSDirURL(). $image['src_url'];
					}
					
					//Bugfix - URLs should not be html escaped in CSS code.
					$image['src_url'] = html_entity_decode($image['src_url']);
				
					$image = $this->images['image_hover'];
					$html .= "
		#{$imageId}:hover {
			display:block;
			height: {$image['img_height']}px;
			width: {$image['img_width']}px;
			background-image: url('{$image['src_url']}');
			background-repeat: no-repeat;
		}";
				}
				
				if ($hide_image) {
					$html .= "\n\t\t}\n";
				}
			}
			
			if (isset($this->images['image_mobile'])) {
				$image = $this->images['image_mobile'];
				
				if ($fullPath && !chopPrefixOffOfString($image['src_url'], 'http')) {
					$image['src_url'] = absCMSDirURL(). $image['src_url'];
				}
				
				//Bugfix - URLs should not be html escaped in CSS code.
				$image['src_url'] = html_entity_decode($image['src_url']);
				
				$html .= "
		@media (max-width: {$min_width_px}px) {
		#{$imageId} {
			display:block;
			height: {$image['img_height']}px;
			width: {$image['img_width']}px;
			background-image: url('{$image['src_url']}');
			background-repeat: no-repeat;
			{$image['custom_css_code']}
		}
		}";
			}
		}
		
		return $html;
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		parent::fillAdminBox($path, $settingGroup, $box, $fields, $values);
		switch ($path) {
			case 'plugin_settings':
				// Image container has no title
				unset($box['tabs']['text']['fields']['title_tags']);
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		parent::formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		
		switch ($path) {
			case 'plugin_settings':
				$fields['alt_tag']['hidden'] = true;
				$fields['text/title']['hidden'] = true;
				$fields['destination/use_translation']['hidden'] = true;
				$fields['destination/hide_private_item']['hidden'] = true;
				
				$mobile_behavior = $values['mobile_behavior'];
				
				$use_mobile_image = $mobile_behavior == 'change_image';

				$fields['mobile_image']['hidden'] = !$use_mobile_image;
				
				$mobile_canvas_hidden = !$use_mobile_image;
				
				$this->showHideImageOptions($fields, $values, 'mobile_tab', $mobile_canvas_hidden, 'mobile_');
				$fields['mobile_show_custom_css_code']['hidden'] = $mobile_canvas_hidden;
				$fields['mobile_custom_css_code']['hidden'] = !$values['mobile_show_custom_css_code'];
				
				break;
			}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		parent::validateAdminBox($path, $settingGroup, $box, $fields, $values, $changes, $saving);
	}
	
	public function fillAdminSlotControls(&$controls) {
		if ($this->isVersionControlled && checkPriv('_PRIV_EDIT_DRAFT')) {
			
			//Check whether something compatible was previously copied
			$copied =
				!empty($_SESSION['admin_copied_contents']['class_name'])
			 && $_SESSION['admin_copied_contents']['class_name'] == 'zenario_banner';
			
			//If something has been entered, show the copy button
			if (!$this->empty) {
				$controls['actions']['copy_contents']['hidden'] = false;
				$controls['actions']['copy_contents']['onclick'] =
					str_replace('list,of,allowed,modules', 'zenario_banner',
						$controls['actions']['copy_contents']['onclick']);
			}
			
			//Check to see if this is the most recent version and the current administrator can make changes
			if (cms_core::$cVersion == cms_core::$adminVersion
			 && checkPriv('_PRIV_EDIT_DRAFT', cms_core::$cID, cms_core::$cType)) {
				
				if (!$this->empty) {
					$controls['actions']['cut_contents']['hidden'] = false;
					$controls['actions']['cut_contents']['onclick'] =
						str_replace('list,of,allowed,modules', 'zenario_banner',
							$controls['actions']['cut_contents']['onclick']);
				}
			
				//If there is no contents here and something was copied, show the paste option
				if ($this->empty && $copied) {
					$controls['actions']['paste_contents']['hidden'] = false;
				}
			
				//If there is contents here and something was copied, show the swap and overwrite options
				if (!$this->empty && $copied) {
					$controls['actions']['overwrite_contents']['hidden'] = false;
					$controls['actions']['swap_contents']['hidden'] = false;
				}
			}
		}
	}
	
	//The showSlot method is called by the CMS, and displays the Plugin on the page
	function showSlot() {
		if (count($this->mergeFields)) {
			$this->subSections['Image_Container'] = true;
			$this->mergeFields['Image_css_id'] = 'zic_'. $this->containerId;
			//Display the Plugin
			$this->framework('Outer', $this->mergeFields, $this->subSections);
		}
	}	
}
