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

class zenario_image_container extends zenario_banner {
	
	static protected $page_plugin_index_start = 1;
	static protected $page_plugin_index_end = 0;
	static protected $all_page_plugin_images = array();
	protected $my_page_plugin_index = 0;
	
	public function init(){
		
		$result = parent::init();
		if(!$result) return $result;
		
		//We need to index all of the zenario_image_containers current on the page
		if (request('method_call') == 'refreshPlugin') {
			//If we're reloading via AJAX, generate an index that won't clash with anything already on the page
			$this->my_page_plugin_index = self::$page_plugin_index_start = self::$page_plugin_index_end =
				(time() % 1000) * 1000 + rand(0, 9999);
		} else {
			//If we're not reloading via AJAX, start counting from 1
			$this->my_page_plugin_index = ++self::$page_plugin_index_end;
		}
		
		
		$my_images = null;
		if (count($this->mergeFields) && isset($this->mergeFields['Image_Src'])) {
			$my_images = array();
			$my_images['image_main'] = array(
				'src_url' => $this->mergeFields['Image_Src'],
				'img_height' => $this->mergeFields['Image_Height'],
				'img_width' => $this->mergeFields['Image_Width'],
				'custom_css_code' => $this->setting('custom_css_code')
				);
			if(isset($this->mergeFields['Image_Rollover'])) {
				$rollover_image = $this->mergeFields['Image_Rollover'];
				$my_images['image_hover'] = array(
						'src_url' => $rollover_image['Image_Src'],
						'img_height' => $rollover_image['Image_Height'],
						'img_width' => $rollover_image['Image_Width'],
				);
			}
			
			if($this->setting('mobile_behavior') == 'change_image') {
				$imageId = $this->setting('mobile_image');
				$width = $this->setting('mobile_width');
				$height = $this->setting('mobile_height');
				$url = null;
				if (imageLink($width, $height, $url, $imageId, $width, $height, $this->setting('mobile_canvas'), $this->setting('mobile_offset'))) {
					$my_images['image_mobile'] = array(
							'src_url' => htmlspecialchars($url),
							'img_height' => $height,
							'img_width' => $width,
							'custom_css_code' => $this->setting('mobile_custom_css_code')
					);
				}
			}
		} 
		self::$all_page_plugin_images['img' . $this->my_page_plugin_index] = $my_images;
		
		//If we're reloading via AJAX, we need to call a JavaScript function to add the style to the head.
		//Otherwise we can use addToPageHead() below.
		if (request('method_call') == 'refreshPlugin') {
			$this->callScriptBeforeAJAXReload('zenario_image_container', 'addToPageHead', $this->generateStyles());
		}
		
		return $result;
	}
	
	public function addToPageHead() {
		echo "\n<style type=\"text/css\">\n", $this->generateStyles(), "\n</style>\n";
	}
	
	public function generateStyles() {
		$html = '';
		
		//Only the first one will write for all of us
		if ($this->my_page_plugin_index == self::$page_plugin_index_start) {
			$min_width_px = setting('image_mobile_resize_point');
			$min_width_px_plus_one = $min_width_px+1;
			
			for ($i = self::$page_plugin_index_start; $i <= self::$page_plugin_index_end; $i++) {
				$plugin_images = self::$all_page_plugin_images['img' . $i];
				if($plugin_images) {

					$mobile_behavior = $this->setting('mobile_behavior');
					//we will not load the main image on media queries less than $min_width_px+1
					$hide_image = ($mobile_behavior == 'hide_image' || isset($plugin_images['image_mobile'])) ? 
						"@media (min-width: {$min_width_px_plus_one}px) {" : false;

					if(isset($plugin_images['image_main'])) {
						
						$image = $plugin_images['image_main'];
						if($hide_image) {
							$html .= $hide_image . "\n";
						}
						$html .= "
#zenario_image_container_{$i} {
 display:block;
 height: {$image['img_height']}px;
 width: {$image['img_width']}px;
 background-image: url({$image['src_url']});
 background-repeat: no-repeat;
 {$image['custom_css_code']}
}";
					
 						if(isset($plugin_images['image_hover'])) {
							$image = $plugin_images['image_hover'];
							$html .= "
#zenario_image_container_{$i}:hover {
 display:block;
 height: {$image['img_height']}px;
 width: {$image['img_width']}px;
 background-image: url({$image['src_url']});
 background-repeat: no-repeat;
}";
 						}
 						
						if($hide_image) {
							$html .= "\n}\n";
						}
					}
					
					if(isset($plugin_images['image_mobile'])) {
						$image = $plugin_images['image_mobile'];
						$html .= "
@media (max-width: {$min_width_px}px) {
 #zenario_image_container_{$i} {
  display:block;
  height: {$image['img_height']}px;
  width: {$image['img_width']}px;
  background-image: url({$image['src_url']});
  background-repeat: no-repeat;
  {$image['custom_css_code']}
 }
}";
					}
				}
			}
		}
		
		return $html;
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		parent::fillAdminBox($path, $settingGroup, $box, $fields, $values);
		switch ($path) {
			case 'plugin_settings':
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
				$fields['mobile_canvas']['hidden'] = $mobile_canvas_hidden;
	
				$fields['mobile_width']['hidden'] = $mobile_canvas_hidden
					|| !in($values['mobile_canvas'], 'fixed_width', 'fixed_width_and_height', 'resize_and_crop');
				
				$fields['mobile_height']['hidden'] = $mobile_canvas_hidden
					|| !in($values['mobile_canvas'], 'fixed_height', 'fixed_width_and_height', 'resize_and_crop');
				
				$fields['mobile_offset']['hidden'] = $mobile_canvas_hidden
					|| $values['mobile_canvas'] != 'resize_and_crop';
				
				$fields['mobile_show_custom_css_code']['hidden'] = $mobile_canvas_hidden;
				$fields['mobile_custom_css_code']['hidden'] = !$values['mobile_show_custom_css_code'];
				
				if($use_mobile_image) {
					$this->getImageHtmlSnippet($values['mobile_image'], $fields['mobile_image_thumbnail']['snippet']['html']);
				} else {
					$fields['mobile_image_thumbnail']['snippet']['html'] = '';
				}
				
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
			$this->mergeFields['Image_css_id'] = 'zenario_image_container_' . $this->my_page_plugin_index;
			//Display the Plugin
			$this->framework('Outer', $this->mergeFields, $this->subSections);
		}
	}	
}
