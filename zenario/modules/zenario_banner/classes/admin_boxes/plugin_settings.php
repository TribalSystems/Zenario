<?php
/*
 * Copyright (c) 2024, Tribal Limited
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
	
	public static function getInheritInfo($canvas, $width = 0, $height = 0, $isSVG = false) {
		
		$mrg = ['width' => $width, 'height' => $height];
	
		switch ($canvas) {
			case 'background_image':
				return ze\admin::phrase('Inherit (show as a background image)');
			case 'lazy_load':
				return ze\admin::phrase('Inherit (lazy load image)');
			
			case 'fixed_width':
				if ($isSVG) {
					return ze\admin::phrase('Inherit (set width to [[width]])', $mrg);
				} else {
					return ze\admin::phrase('Inherit (constrain width by [[width]])', $mrg);
				}
			case 'fixed_height':
				if ($isSVG) {
					return ze\admin::phrase('Inherit (set height to [[height]])', $mrg);
				} else {
					return ze\admin::phrase('Inherit (constrain height by [[height]])', $mrg);
				}
			case 'crop_and_zoom':
				if (!$isSVG) {
					return ze\admin::phrase('Inherit (crop and zoom to [[width]]w × [[height]]h)', $mrg);
				}
			case 'fixed_width_and_height':
				return ze\admin::phrase('Inherit (constrain width and height by [[width]]w × [[height]]h)', $mrg);
			
			case 'mobile_same_image_different_size':
			case 'mobile_same_image_different_size.':
				return ze\admin::phrase('Inherit (same image, different size)');
			case 'mobile_same_image_different_size.fixed_width':
				if ($isSVG) {
					return ze\admin::phrase('Inherit (same image, set width to [[width]])', $mrg);
				} else {
					return ze\admin::phrase('Inherit (same image, constrain width by [[width]])', $mrg);
				}
			case 'mobile_same_image_different_size.fixed_height':
				if ($isSVG) {
					return ze\admin::phrase('Inherit (same image, set height to [[height]])', $mrg);
				} else {
					return ze\admin::phrase('Inherit (same image, constrain height by [[height]])', $mrg);
				}
			case 'mobile_same_image_different_size.crop_and_zoom':
				if (!$isSVG) {
					return ze\admin::phrase('Inherit (same image, crop and zoom to [[width]]w × [[height]]h)', $mrg);
				}
			case 'mobile_same_image_different_size.fixed_width_and_height':
				return ze\admin::phrase('Inherit (same image, constrain width and height by [[width]]w × [[height]]h)', $mrg);
			
			case 'mobile_hide_image':
			case 'mobile_hide_image.':
			case 'mobile_hide_image.crop_and_zoom':
			case 'mobile_hide_image.fixed_width':
			case 'mobile_hide_image.fixed_height':
			case 'mobile_hide_image.fixed_width_and_height':
				return ze\admin::phrase('Inherit (hide image on mobile browsers)');
			
			case '_ENLARGE_IMAGE':
			case '_ENLARGE_IMAGE.':
			case '_ENLARGE_IMAGE.unlimited':
				return ze\admin::phrase('Inherit (enlarge image in floating box)');
			case '_ENLARGE_IMAGE.fixed_width':
				return ze\admin::phrase('Inherit (enlarge image in floating box, constrain width by [[width]])', $mrg);
			case '_ENLARGE_IMAGE.fixed_height':
				return ze\admin::phrase('Inherit (enlarge image in floating box, constrain height by [[height]])', $mrg);
			case '_ENLARGE_IMAGE.crop_and_zoom':
				return ze\admin::phrase('Inherit (enlarge image in floating box, crop and zoom to [[width]]w × [[height]]h)', $mrg);
			case '_ENLARGE_IMAGE.fixed_width_and_height':
				return ze\admin::phrase('Inherit (enlarge image in floating box, constrain width and height by [[width]]w × [[height]]h)', $mrg);
		}
		
		return ze\admin::phrase('Inherit');
	}
	

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//The banner plugin has some fiddly logic for the default value of the canvas.
		//Outside of a nest, the default value should be "Crop and Zoom"
		if ($values['first_tab/canvas'] == 'DEFAULT') {
			if (empty($box['key']['eggId'])) {
				//Outside of a nest, the default value should be "Crop and Zoom"
				$values['first_tab/canvas'] = 'crop_and_zoom';
			} else {
				//In a nest, the default value should be "Unlimited/Inherit"
				$values['first_tab/canvas'] = 'unlimited';
			}
		}
		
		$fields['first_tab/image']['side_note'] =
		$fields['first_tab/mobile_image']['side_note'] =
		$fields['first_tab/rollover_image']['side_note'] = 
			ze\admin::phrase('If a JPG or PNG image is selected, Zenario will create and display a WebP version of the image. Fallback logic will be used for browsers which do not support WebP.');
		
		//For Wireframe Plugins, pick images from this item's images, rather than 
		if ($box['key']['isVersionControlled']/*
		 && ze\content::isDraft($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'])*/) {
			$box['tabs']['first_tab']['fields']['image']['pick_items']['path'] =
			$box['tabs']['first_tab']['fields']['rollover_image']['pick_items']['path'] =
				'zenario__content/panels/content/item_buttons/images//'. $box['key']['cType']. '_'. $box['key']['cID']. '//';
			
			$box['tabs']['first_tab']['fields']['image']['pick_items']['min_path'] =
			$box['tabs']['first_tab']['fields']['image']['pick_items']['max_path'] =
			$box['tabs']['first_tab']['fields']['image']['pick_items']['target_path'] =
			$box['tabs']['first_tab']['fields']['rollover_image']['pick_items']['min_path'] =
			$box['tabs']['first_tab']['fields']['rollover_image']['pick_items']['max_path'] =
			$box['tabs']['first_tab']['fields']['rollover_image']['pick_items']['target_path'] =
				'zenario__library/panels/image_library';
		}
		
		$box['first_display'] = true;
		
		//Banner Plugins should have a note that appears below their settings if they are in a nest,
		//explaining that they may be overwritten by the global settings.
		//However in Modules that extend the banner, these should not be visible.
		if ((!empty($box['key']['eggId']))
		 && ($nestedPlugin = ze\pluginAdm::getNestDetails($box['key']['eggId']))
		 && (ze\module::className($nestedPlugin['module_id']) == 'zenario_banner')) {
			
			//If this banner is in a nest, check if the nest has a banner image size constraint set.
			$setCanvas = ze\plugin::setting('banner_canvas', $nestedPlugin['instance_id']);
			$setWidth = ze\plugin::setting('banner_width', $nestedPlugin['instance_id']);
			$setHeight = ze\plugin::setting('banner_height', $nestedPlugin['instance_id']);
			$setBehaviour = ze\plugin::setting('advanced_behaviour', $nestedPlugin['instance_id']);
			$setMobBehaviour = ze\plugin::setting('mobile_behaviour', $nestedPlugin['instance_id']);
			$setMobCanvas = ze\plugin::setting('mobile_canvas', $nestedPlugin['instance_id']);
			$setMobWidth = ze\plugin::setting('mobile_width', $nestedPlugin['instance_id']);
			$setMobHeight = ze\plugin::setting('mobile_height', $nestedPlugin['instance_id']);
			$setLinkType = ze\plugin::setting('link_type', $nestedPlugin['instance_id']);
			$setLargeCanvas = ze\plugin::setting('enlarge_canvas', $nestedPlugin['instance_id']);
			$setLargeWidth = ze\plugin::setting('enlarge_width', $nestedPlugin['instance_id']);
			$setLargeHeight = ze\plugin::setting('enlarge_height', $nestedPlugin['instance_id']);
			
			if ($setCanvas && $setCanvas != 'unlimited') {
				$fields['first_tab/canvas']['values']['unlimited']['ord'] = -1;
				$fields['first_tab/canvas']['values']['unlimited']['custom__is_inherit'] = true;
				$fields['first_tab/canvas']['values']['unlimited']['custom__label_raster'] = self::getInheritInfo($setCanvas, $setWidth, $setHeight);
				$fields['first_tab/canvas']['values']['unlimited']['custom__label_svg'] = self::getInheritInfo($setCanvas, $setWidth, $setHeight, true);
				
				//The "is retina" checkbox shouldn't appear if the "unlimited" option has been renamed to "inherit".
				//However there's a function call later that will overwrite whatever we set here.
				$fields['first_tab/retina']['hidden'] = true;
				//Slightly hacky work-around: use the visible_if property to hide the field instead!
				$fields['first_tab/retina']['visible_if'] = 'false';
			}
			
			if ($setBehaviour && $setBehaviour != 'none') {
				$fields['first_tab/advanced_behaviour']['values']['none']['label'] = self::getInheritInfo($setBehaviour);
			}
			
			if ($setMobBehaviour && $setMobBehaviour != 'mobile_same_image') {
				$fields['first_tab/mobile_behaviour']['values']['mobile_same_image']['custom__label_raster'] = self::getInheritInfo($setMobBehaviour. '.'. $setMobCanvas, $setMobWidth, $setMobHeight);
				$fields['first_tab/mobile_behaviour']['values']['mobile_same_image']['custom__label_svg'] = self::getInheritInfo($setMobBehaviour. '.'. $setMobCanvas, $setMobWidth, $setMobHeight, true);
			}
			
			if ($setLinkType && $setLinkType != '_NO_LINK') {
				$fields['first_tab/link_type']['values']['_NO_LINK']['label'] = self::getInheritInfo($setLinkType. '.'. $setLargeCanvas, $setLargeWidth, $setLargeHeight);
			}
		}

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
		
		
		//Don't show the option to pick a translation chain when not linking to a content item, on single-language sites,
		//or on version controlled plugins.
		$fields['first_tab/use_translation']['hidden'] = 
			$values['first_tab/link_type'] != '_CONTENT_ITEM'
		 || $box['key']['isVersionControlled']
		 || ze\lang::count() < 2;
		
		//On multilingual sites, default the set the value of the use_translation option to enabled by default.
		//We'll achieve this by changing the value on opening the FAB, if we see it hidden.
		if (!empty($fields['first_tab/use_translation']['hidden'])
		 && !$box['key']['isVersionControlled']
		 && ze\lang::count() >= 2) {
			$values['first_tab/use_translation'] = 1;
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//The tag IDs in translation chain pickers have a slightly different format.
		//This is needed for a technical reason, as meta-info about the selected items are stored by ID.
		//When displaying, change between formats depending on whether we are showing a specific content item or a translation chain.
		$values['first_tab/hyperlink_target'] =
			ze\contentAdm::convertBetweenTagIdAndTranslationChainId($values['first_tab/hyperlink_target'], $values['first_tab/use_translation']);
		
		
		//If an SVG image is selected, tweak the canvas options slightly.
		//Give some slightly different labels to reflect the fact that the options for SVGs work slightly differently,
		//and hide the "crop and zoom" option.
		if (isset($fields['first_tab/canvas']['values']['fixed_width']['custom__label_raster'])) {
			$canvasLOV = &$fields['first_tab/canvas']['values'];
			
			$svgSelected = false;
			if ($values['first_tab/image_source'] == '_CUSTOM_IMAGE') {
				if ($image = $values['first_tab/image']) {
					if (is_numeric($image)) {
						$svgSelected = ze\row::get('files', 'mime_type', $image) == 'image/svg+xml';
					} else {
						if ($tmpFilePath = ze\file::getPathOfUploadInCacheDir($image)) {
							$svgSelected = ze\file::mimeType($tmpFilePath) == 'image/svg+xml';
						}
					}
				}
			}
			
			if ($svgSelected) {
				//Don't allow SVGs to select the "Crop and zoom" option
				if ($values['first_tab/canvas'] == 'crop_and_zoom') {
					$values['first_tab/canvas'] = 'fixed_width_and_height';
				}
				
				$canvasLOV['crop_and_zoom']['hidden'] = true;
				$canvasLOV['fixed_width']['label'] = $canvasLOV['fixed_width']['custom__label_svg'];
				$canvasLOV['fixed_height']['label'] = $canvasLOV['fixed_height']['custom__label_svg'];
				$canvasLOV['unlimited']['label'] = $canvasLOV['unlimited']['custom__label_svg'];
			} else {
				$canvasLOV['crop_and_zoom']['hidden'] = false;
				$canvasLOV['fixed_width']['label'] = $canvasLOV['fixed_width']['custom__label_raster'];
				$canvasLOV['fixed_height']['label'] = $canvasLOV['fixed_height']['custom__label_raster'];
				$canvasLOV['unlimited']['label'] = $canvasLOV['unlimited']['custom__label_raster'];
			}
			
			//The "unlimited" option should be shown first if it has been renamed (e.g. to "Inherit" or "Use base width and height").
			if ($svgSelected || !empty($canvasLOV['unlimited']['custom__is_inherit'])) {
				$canvasLOV['unlimited']['ord'] = -1;
			} else {
				$canvasLOV['unlimited']['ord'] = 99;
			}
			unset($canvasLOV);
			
			
			if (isset($fields['first_tab/mobile_behaviour']['values']['mobile_same_image']['custom__label_raster'])) {
				$canvasLOV = &$fields['first_tab/mobile_behaviour']['values'];
			
				if ($svgSelected) {
					$canvasLOV['mobile_same_image']['label'] = $canvasLOV['mobile_same_image']['custom__label_svg'];
				} else {
					$canvasLOV['mobile_same_image']['label'] = $canvasLOV['mobile_same_image']['custom__label_raster'];
				}
				unset($canvasLOV);
			}
			
			
			if (isset($fields['first_tab/mobile_canvas']['values']['fixed_width']['custom__label_raster'])) {
				$canvasLOV = &$fields['first_tab/mobile_canvas']['values'];
				
				if ($values['first_tab/mobile_behaviour'] == 'mobile_change_image') {
					if ($image = $values['first_tab/mobile_image']) {
						if (is_numeric($image)) {
							$svgSelected = ze\row::get('files', 'mime_type', $image) == 'image/svg+xml';
						} else {
							if ($tmpFilePath = ze\file::getPathOfUploadInCacheDir($image)) {
								$svgSelected = ze\file::mimeType($tmpFilePath) == 'image/svg+xml';
							}
						}
					}
				}
			
				if ($svgSelected) {
					//Don't allow SVGs to select the "Crop and zoom" option
					if ($values['first_tab/mobile_canvas'] == 'crop_and_zoom') {
						$values['first_tab/mobile_canvas'] = 'fixed_width_and_height';
					}
				
					$canvasLOV['crop_and_zoom']['hidden'] = true;
					$canvasLOV['fixed_width']['label'] = $canvasLOV['fixed_width']['custom__label_svg'];
					$canvasLOV['fixed_height']['label'] = $canvasLOV['fixed_height']['custom__label_svg'];
					$canvasLOV['unlimited']['label'] = $canvasLOV['unlimited']['custom__label_svg'];
				} else {
					$canvasLOV['crop_and_zoom']['hidden'] = false;
					$canvasLOV['fixed_width']['label'] = $canvasLOV['fixed_width']['custom__label_raster'];
					$canvasLOV['fixed_height']['label'] = $canvasLOV['fixed_height']['custom__label_raster'];
					$canvasLOV['unlimited']['label'] = $canvasLOV['unlimited']['custom__label_raster'];
				}
				
				//The "unlimited" option should be shown first if it has been renamed (e.g. to "Inherit" or "Use base width and height").
				if ($svgSelected) {
					$canvasLOV['unlimited']['ord'] = -1;
				} else {
					$canvasLOV['unlimited']['ord'] = 99;
				}
				unset($canvasLOV);
			}
		}
		
		
		
		
		
		if (!ze\module::inc('zenario_ctype_picture')) {
			unset($fields['first_tab/image_source']['values']['_PICTURE']);
		}
		
		$retinaSideNote = "If the source image is large enough,
                            the resized image will be output at twice its displayed width &amp; height
                            to appear crisp on retina screens.
                            This will increase the download size.
                            <br/>
                            If the source image is not large enough this will have no effect.";
	
		
		$fields['first_tab/image']['hidden'] = 
			$values['first_tab/image_source'] != '_CUSTOM_IMAGE';
		
		$fields['first_tab/use_rollover']['hidden'] = ($values['first_tab/image_source'] != '_CUSTOM_IMAGE' || $values['first_tab/image_source'] != '_PRODUCT_IMAGE');
		
		$fields['first_tab/rollover_image']['hidden'] = 
		$fields['first_tab/rollover_tech']['hidden'] = 
			$values['first_tab/advanced_behaviour'] != 'use_rollover'
			|| !($values['first_tab/image_source'] == '_CUSTOM_IMAGE' || $values['first_tab/image_source'] == '_PRODUCT_IMAGE');
			
		//TODO disable using rollover images when the source isn't "Show an image"
		$fields['first_tab/advanced_behaviour']['values']['use_rollover']['disabled'] =
			!($values['first_tab/image_source'] == '_CUSTOM_IMAGE' || $values['first_tab/image_source'] == '_PRODUCT_IMAGE');
		
		$fields['first_tab/picture']['hidden'] =
			$values['first_tab/image_source'] != '_PICTURE';
		
		//Check whether an image is picked
		$cID = $cType = $pictureCID = $pictureCType = $imageId = $imagePicked = false;
		
		//A little hack to make extending this module easier
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
			  && (ze\content::getCIDAndCTypeFromTagId($pictureCID, $pictureCType, $values['first_tab/picture']))
			  && ($imageId = ze\row::get("versions", "file_id", ["id" => $pictureCID, 'type' => $pictureCType, "version" => ze\content::version($pictureCID, $pictureCType)])))
		 
			 || ($values['first_tab/image_source'] == '_STICKY_IMAGE'
			  && (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $values['first_tab/hyperlink_target']))
			  && ($imageId = ze\file::itemStickyImageId($cID, $cType)))
			  
			  || (isset($fields['first_tab/product_source'])
			  && (
			  		($values['first_tab/product_source'] == 'auto' && $product = zenario_ecommerce_manager::getProductFromDescriptiveContentItem($this->cID, $this->cType))
			  		|| ($values['first_tab/product_source'] == 'select_product' && $values['first_tab/product_for_sale'] && ($product = zenario_ecommerce_manager::getProduct($values['first_tab/product_for_sale'])) && $product)
			  	)
			  	&& (($values['first_tab/image_source'] == '_PRODUCT_IMAGE' && ($imageId = $product['image_id'])) || ($values['first_tab/image_source'] == '_CUSTOM_IMAGE' && ($imageId = $values['first_tab/image'])))
			  ));
			
			$imagePicked = (bool) $imageId;
		}
		
		
		if (!empty($fields['first_tab/link_type']['values'])) {
			
			$onlyShowLinkToContent = false;
			if ($values['first_tab/image_source'] == '_STICKY_IMAGE') {
				$values['first_tab/link_type'] = '_CONTENT_ITEM';
				$onlyShowLinkToContent = true;
			}
			
			$fields['first_tab/link_type']['values']['_NO_LINK']['disabled'] =
			$fields['first_tab/link_type']['values']['_EXTERNAL_URL']['disabled'] =
			$fields['first_tab/link_type']['values']['_EMAIL']['disabled'] = $onlyShowLinkToContent;
			
		}
		
		$fields['first_tab/alt_tag']['hidden'] = 
		$fields['first_tab/retina']['hidden'] = 
		$hidden = 
			!$imagePicked;
		
		$this->showHideImageOptions($fields, $values, 'first_tab', $hidden);
		if ($values['first_tab/canvas'] != "unlimited") {
			$fields['first_tab/canvas']['side_note'] = $retinaSideNote;
		} else {
			$fields['first_tab/canvas']['side_note'] = "";
		}
		
		$fields['first_tab/floating_box_title']['hidden'] = 
			!$imagePicked
		 || $values['first_tab/image_source'] == '_STICKY_IMAGE'
		 || $values['first_tab/link_type'] != '_ENLARGE_IMAGE';
		
		$fields['first_tab/stretched_image_warning']['hidden'] = true;

		if ($imagePicked
		 && $imageId
		 && ($image = ze\row::get('files', ['width', 'height', 'alt_tag', 'title', 'floating_box_title', 'mime_type'], $imageId))) {
			$editModeOn = ze\ring::engToBoolean($box['tabs']['first_tab']['edit_mode']['on'] ?? false);
			
			$fields['first_tab/alt_tag']['placeholder'] = $image['alt_tag'];
			
			if ($image['floating_box_title']) {
				$merge = '"' . $image['floating_box_title'] . '"';
			} else {
				$merge = 'No caption set';
			}
			$fields['first_tab/floating_box_title_mode']['values']['use_default']['label'] = 'Use the image\'s default floating box caption (' . htmlspecialchars($merge) .')';
				
			if ($box['first_display']) {
				if (!$values['first_tab/floating_box_title']) {
					$fields['first_tab/floating_box_title']['value'] = $image['floating_box_title'];
				}
			}
			
			if ($values['first_tab/canvas'] == "resize_and_crop") {
				$mimeType = $image['mime_type'];
				if ($mimeType != 'image/svg+xml'
					&& ($values['first_tab/width'] > $image['width'] || $values['first_tab/height'] > $image['height']
					)
				) {
					$fields['first_tab/stretched_image_warning']['hidden'] = false;
				}
			}
		}
		
		$fields['first_tab/mobile_behaviour']['hidden'] = !ze::in($values['first_tab/image_source'], '_CUSTOM_IMAGE', '_STICKY_IMAGE', '_PICTURE', '_PRODUCT_IMAGE');
		
		$fields['first_tab/hyperlink_target']['notices_below']['featured_image_filename']['hidden'] = true;
		$fields['first_tab/hyperlink_target']['notices_below']['featured_image_filename']['type'] = '';
		$fields['first_tab/hyperlink_target']['notices_below']['featured_image_filename']['message'] = '';
		
		if ($values['first_tab/image_source'] == '_STICKY_IMAGE') {
			$fields['first_tab/mobile_behaviour']['values']['mobile_change_image']['hidden'] =
			$fields['first_tab/mobile_behaviour']['values']['mobile_change_image']['hidden'] = true;
			
			if (!empty($cID) && !empty($cType)) {
				if ($imagePicked && ($filename = ze\row::get('files', 'filename', $imageId))) {
					$fields['first_tab/hyperlink_target']['notices_below']['featured_image_filename']['hidden'] = false;
					$fields['first_tab/hyperlink_target']['notices_below']['featured_image_filename']['type'] = 'information';
					$fields['first_tab/hyperlink_target']['notices_below']['featured_image_filename']['message'] = ze\admin::phrase('Featured image filename: [[filename]]', ['filename' => $filename]);
				} else {
					$fields['first_tab/hyperlink_target']['notices_below']['featured_image_filename']['hidden'] = false;
					$fields['first_tab/hyperlink_target']['notices_below']['featured_image_filename']['type'] = 'warning';
					$fields['first_tab/hyperlink_target']['notices_below']['featured_image_filename']['message'] = ze\admin::phrase('Warning: the selected content item has no featured image.');
				}
			}
		} else {
			$fields['first_tab/mobile_behaviour']['values']['mobile_change_image']['hidden'] =
			$fields['first_tab/mobile_behaviour']['values']['mobile_change_image']['hidden'] = false;
		}
		
		$box['first_display'] = false;
		
		$fields['first_tab/hyperlink_target']['hidden'] = 
		$fields['first_tab/hide_private_item']['hidden'] = 
		$fields['first_tab/use_download_page']['hidden'] = 
		$fields['first_tab/add_referrer']['hidden'] = 
			$values['first_tab/link_type'] != '_CONTENT_ITEM';

		$fields['first_tab/get_translation']['hidden'] = 
			$values['first_tab/link_type'] != '_CONTENT_ITEM'
		 || $box['key']['isVersionControlled']
		 || ze\lang::count() < 2;

		$fields['first_tab/more_link_text']['hidden'] = 
		$fields['first_tab/target_blank']['hidden'] = 
			$values['first_tab/link_type'] != '_CONTENT_ITEM'
		 && $values['first_tab/link_type'] != '_DOCUMENT'
		 && $values['first_tab/link_type'] != '_EXTERNAL_URL'
		 && $values['first_tab/link_type'] != '_EMAIL'
		 && $values['first_tab/link_type'] != '_PRODUCT_DESCRIPTION_PAGE'; //This value is for Storefront Banner
		
		//Don't show the option to pick a translation chain when not linking to a content item, on single-language sites,
		//or on version controlled plugins.
		$fields['first_tab/use_translation']['hidden'] = 
			$values['first_tab/link_type'] != '_CONTENT_ITEM'
		 || $box['key']['isVersionControlled']
		 || ze\lang::count() < 2;
		
		//Format the picker slightly differently when selecting a translation chain v.s selecting a content item.
		//Note: these are cosmetic changes only, for backwards compatibility reasons the values in the database and logic in the
		//PHP code is still exactly the same as it was in Zenario 9.4.
		if ($values['first_tab/use_translation'] && empty($fields['first_tab/use_translation']['hidden'])) {
			$fields['first_tab/hyperlink_target']['pick_items'] = $fields['first_tab/hyperlink_target__translation']['pick_items'];
			$fields['first_tab/hyperlink_target']['validation'] = $fields['first_tab/hyperlink_target__translation']['validation'];
		
		} else {
			$fields['first_tab/hyperlink_target']['pick_items'] = $fields['first_tab/hyperlink_target__specific']['pick_items'];
			$fields['first_tab/hyperlink_target']['validation'] = $fields['first_tab/hyperlink_target__specific']['validation'];
			
			if (!empty($fields['first_tab/use_translation']['hidden'])) {
				$fields['first_tab/hyperlink_target']['label'] = ze\admin::phrase('Content item:');
			}
		}
		
		//On a multilingual site, if the “specific” option is selected, there should be a box below saying “this will link to the content item in [[language name]]”
		$fields['first_tab/hyperlink_target']['notices_below']['in_language']['hidden'] = true;
		$fields['first_tab/hyperlink_target']['notices_below']['in_language']['message'] = '';
		$cID = $cType = false;
		if (empty($fields['first_tab/use_translation']['hidden'])
		 && !$values['first_tab/use_translation']
		 && (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $values['first_tab/hyperlink_target']))
		 && ($langId = ze\content::langId($cID, $cType))) {
			
			$mrg = ['language_name' => ze\lang::name($langId)];
			
			$fields['first_tab/hyperlink_target']['notices_below']['in_language']['hidden'] = false;
			$fields['first_tab/hyperlink_target']['notices_below']['in_language']['message'] =
				ze\admin::phrase('This will link to the content item in [[language_name]]', $mrg);
		}
		

		$fields['first_tab/url']['hidden'] = 
			$values['first_tab/link_type'] != '_EXTERNAL_URL';
		
		if ($values['first_tab/link_type'] == '_EMAIL') {
			$fields['first_tab/email_address']['hidden'] = false;
			if (!empty($values['first_tab/email_address']) && !strstr($values['first_tab/email_address'], 'mailto:')) {
				$values['first_tab/email_address'] = 'mailto:' . $values['first_tab/email_address'];
			}
		} else {
			$fields['first_tab/email_address']['hidden'] = true;
		}
		
		$hidden = $values['first_tab/link_type'] != '_ENLARGE_IMAGE';
		$this->showHideImageOptions($fields, $values, 'first_tab', $hidden, 'enlarge_');
		if ($values['first_tab/enlarge_canvas'] != "unlimited") {
			$fields['first_tab/enlarge_canvas']['side_note'] = $retinaSideNote;
		} else {
			$fields['first_tab/enlarge_canvas']['side_note'] = "";
		}
		
		$cID = $cType = false;
		if ($values['first_tab/link_type'] == '_CONTENT_ITEM'
		 && (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $values['first_tab/hyperlink_target']))
		 && ($cType == 'document')) {
			$fields['first_tab/use_download_page']['hidden'] = false;
		} else {
			$fields['first_tab/use_download_page']['current_value'] = false;
			$fields['first_tab/use_download_page']['hidden'] = true;
		}
		
		//Don't show the translations checkbox if this can never be translated
		$fields['first_tab/translate_text']['hidden'] =
			$box['key']['isVersionControlled']
		 || !ze\row::exists('languages', ['translate_phrases' => 1]);
		
		//Don't show notes about translations if this won't be translated
		if ($fields['first_tab/translate_text']['hidden'] || !$values['first_tab/translate_text']) {
			$fields['title_and_description/text']['show_phrase_icon'] =
			$fields['title_and_description/title']['show_phrase_icon'] =
			$fields['first_tab/more_link_text']['show_phrase_icon'] = false;
			
			$fields['title_and_description/text']['note_below'] =
			$fields['title_and_description/title']['note_below'] =
			$fields['first_tab/more_link_text']['note_below'] = '';
		
		} else {
			
			$mrg = [
				'def_lang_name' => htmlspecialchars(ze\lang::name(ze::$defaultLang)),
				'phrases_panel' => htmlspecialchars(ze\link::absolute(). 'organizer.php#zenario__languages/panels/phrases')
			];
			
			$fields['title_and_description/text']['show_phrase_icon'] =
			$fields['title_and_description/title']['show_phrase_icon'] =
			$fields['first_tab/more_link_text']['show_phrase_icon'] = true;
			
			$fields['title_and_description/title']['side_note'] = 
			$fields['first_tab/more_link_text']['side_note'] = 
				ze\admin::phrase('Enter text in [[def_lang_name]], this site\'s default language. <a href="[[phrases_panel]]" target="_blank">Click here to manage translations in Organizer.</a>.', $mrg);
		}
		

		//Only show the mobile image picker if an image is picked and "Mobile behaviour" is set to "Different image".
		$fields['first_tab/mobile_image']['hidden'] = $fields['first_tab/mobile_behaviour']['hidden'] || $values['first_tab/mobile_behaviour'] != 'mobile_change_image';
		
		//Only show mobile options if "Mobile behaviour" is set to either "Different image", or "Same image, different size".
		$hideMobileOptions = !empty($fields['first_tab/mobile_behaviour']['hidden']) || !ze::in($values['first_tab/mobile_behaviour'], 'mobile_change_image', 'mobile_same_image_different_size');
		$this->showHideImageOptions($fields, $values, 'first_tab', $hideMobileOptions, 'mobile_');
		
		if ($values['first_tab/mobile_canvas'] != "unlimited") {
			$fields['first_tab/mobile_canvas']['side_note'] = $retinaSideNote;
		} else {
			$fields['first_tab/mobile_canvas']['side_note'] = "";
		}
		
		//Lazy load and rollover only work if Mobile Behaviour is set to "Same image".
		if ($values['first_tab/mobile_behaviour'] != 'mobile_same_image') {
			$fields['first_tab/advanced_behaviour']['values']['lazy_load']['disabled'] =
			$fields['first_tab/advanced_behaviour']['values']['use_rollover']['disabled'] = true;
			$fields['first_tab/advanced_behaviour']['side_note'] = ze\admin::phrase('The lazy load and rollover options are only available when using the "Same image" option for mobile browsers.');
		} else {
			$fields['first_tab/advanced_behaviour']['values']['lazy_load']['disabled'] = false;
			unset($fields['first_tab/advanced_behaviour']['side_note']);
		}
		
		if ($values['first_tab/advanced_behaviour'] == 'lazy_load') {
			$fields['first_tab/mobile_behaviour']['values']['mobile_same_image_different_size']['disabled'] = 
			$fields['first_tab/mobile_behaviour']['values']['mobile_change_image']['disabled'] = 
			$fields['first_tab/mobile_behaviour']['values']['mobile_hide_image']['disabled'] = true;
			$fields['first_tab/mobile_behaviour']['side_note'] = ze\admin::phrase('When lazy loading images, only the "Same image" option for mobile browsers is supported.');
		
		} elseif ($values['first_tab/advanced_behaviour'] == 'use_rollover') {
			$fields['first_tab/mobile_behaviour']['values']['mobile_same_image_different_size']['disabled'] = 
			$fields['first_tab/mobile_behaviour']['values']['mobile_change_image']['disabled'] = 
			$fields['first_tab/mobile_behaviour']['values']['mobile_hide_image']['disabled'] = true;
			$fields['first_tab/mobile_behaviour']['side_note'] = ze\admin::phrase('When using rollover images, only the "Same image" option for mobile browsers is supported.');
		
		} else {
			$fields['first_tab/mobile_behaviour']['values']['mobile_same_image_different_size']['disabled'] = 
			$fields['first_tab/mobile_behaviour']['values']['mobile_change_image']['disabled'] = 
			$fields['first_tab/mobile_behaviour']['values']['mobile_hide_image']['disabled'] = false;
			unset($fields['first_tab/mobile_behaviour']['side_note']);
		}
		
		if ($values['title_and_description/set_an_anchor']) {
			$anchorName = $values['title_and_description/anchor_name'] ?: '[anchorname]';
			$box['tabs']['title_and_description']['fields']['anchor_name']['side_note'] =
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
		if (isset($values['first_tab/document_id'])) {
			$documentId = $values['first_tab/document_id'];
		}

		//...get privacy settings of the document and content item...
		$document = ze\row::get('documents', ['filename', 'privacy'], ['id' => $documentId]);
		$contentItemPrivacy = ze\row::get('translation_chains', 'privacy', ['equiv_id' => $box['key']['cID']]);

		//...and display or hide a privacy warning note if necessary.
		
		if ($document && $document['privacy'] == 'private' && ($contentItemPrivacy == 'public' || $contentItemPrivacy == 'logged_out')) {
			$box['tabs']['first_tab']['fields']['privacy_warning']['note_below'] = '<p>Warning: this content item is public, the selected document is private, so it will not appear to visitors.</p>';
		} elseif ($document && $document['privacy'] == 'offline') {
			$box['tabs']['first_tab']['fields']['privacy_warning']['note_below'] = '<p>Warning: the selected document is offline, so it will not appear to visitors. Change the privacy of the document to make it available.</p>';
		} else {
			$box['tabs']['first_tab']['fields']['privacy_warning']['note_below'] = '';
		}
		
		//2) Link to a content item:
		
		//Get the privacy of the target content item...
		if ($values['first_tab/link_type'] == '_CONTENT_ITEM') {
			$cID = $cType = false;
			if (!empty($values['first_tab/hyperlink_target'])
			 && ze\content::getEquivIdAndCTypeFromTagId($cID, $cType, $values['first_tab/hyperlink_target'])) {
			
				$contentItemPrivacy = ze\row::get('translation_chains', 'privacy', ['equiv_id' => $cID, 'type' => $cType]);
				
				//...and display it to the admin...
				$fields['first_tab/hide_private_item']['note_below'] = '<p>Selected content item privacy setting is:</p><p>"' . ze\contentAdm::privacyDesc($contentItemPrivacy) . '"</p>';
				$fields['first_tab/hide_private_item']['indent'] = 2;
			} else {
				//...or don't show the note at all if no content item is selected.
				$fields['first_tab/hide_private_item']['indent'] = 1;
				unset($fields['first_tab/hide_private_item']['note_below']);
			}
		}
	}

	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		//The tag IDs in translation chain pickers have a slightly different format.
		//This is needed for a technical reason, as meta-info about the selected items are stored by ID.
		//For backwards compatibility reasons, always save the value in the old format
		$values['first_tab/hyperlink_target'] =
			ze\contentAdm::convertBetweenTagIdAndTranslationChainId($values['first_tab/hyperlink_target'], false);

		if (!empty($fields['first_tab/alt_tag'])
		 && empty($fields['first_tab/alt_tag']['hidden'])
		 && $changes['first_tab/alt_tag']
		 && !$values['first_tab/alt_tag']) {
			$box['tabs']['first_tab']['errors'][] = ze\admin::phrase('Please enter an alt-tag.');
		}
		
		if (!empty($fields['first_tab/floating_box_title'])
		 && empty($fields['first_tab/floating_box_title']['hidden'])
		 && $changes['first_tab/floating_box_title']
		 && !$values['first_tab/floating_box_title']) {
			$box['tabs']['first_tab']['errors'][] = ze\admin::phrase('Please enter a floating box title attribute.');
		}
		
		//Convert all absolute URLs in the HTML Text to relative URLs when saving
		foreach (['value', 'current_value'] as $value) {
			if (isset($box['tabs']['title_and_description']['fields']['text'][$value])) {
				foreach (['"', "'"] as $quote) {
					$box['tabs']['title_and_description']['fields']['text'][$value] = 
						str_replace(
							$quote. htmlspecialchars(ze\link::absolute()),
							$quote,
							$box['tabs']['title_and_description']['fields']['text'][$value]);
				}
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
	
	
	
	
}
