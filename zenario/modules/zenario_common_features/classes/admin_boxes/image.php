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


class zenario_common_features__admin_boxes__image extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (!$details = ze\row::get(
			'files',
			['id', 'usage', 'path', 'filename', 'mime_type', 'width', 'height', 'size', 'alt_tag', 'floating_box_title', 'short_checksum', 'privacy', 'image_credit'],
			$box['key']['id'])
		) {
			exit;
		}
		
		if ($details['usage'] == 'mic') {
			$box['key']['mic_image'] = true;
		}
		
		$box['title'] = ze\admin::phrase('Editing properties of image "[[filename]]"', $details);
		
		$box['identifier']['value'] = ze\admin::phrase('Image ID [[id]], checksum "[[short_checksum]]"', $details);
		
		

		
		
		$details['filesize'] = ze\lang::formatFilesizeNicely($details['size'], 1, true);
		
		$mimeType = $details['mime_type'];
		$dimensionsString = '{{filesize}}';
		if ($details['width'] && $details['height']) {
			$dimensionsString .= ' [{{width}} × {{height}}px]';
		} else {
			$dimensionsString .= ', dimensions not set';
		}

		if ($isSVG = $mimeType == 'image/svg+xml') {
			$dimensionsString .= '; scalable';
		}

		$box['tabs']['details']['fields']['size']['snippet']['html'] = 
			ze\admin::phrase($dimensionsString, $details, false, '{{', '}}');
		
		$box['tabs']['details']['fields']['filename']['value'] = $details['filename'];
		$box['tabs']['details']['fields']['alt_tag']['value'] = $details['alt_tag'];
		
		
		
		//Show a resized version of the image front-and-center in the properties tab
		$width = $height = $url = false;
		\ze\file::retinaImageLink($width, $height, $url, $box['key']['id'], $widthLimit = 700, $heightLimit = 200);
		$fields['details/image']['image'] = [
			'width' => $width,
			'height' => $height,
			'url' => $url
		];
		
		
		//SVGs shouldn't see the crop and zoom options
		if ($isSVG) {
			unset($box['tabs']['crop']);
			unset($box['tabs']['crops']);
		
		} else {
			//We'll want a slightly bigger version of the image for use when defining crops and zooms
			$width = $height = $url = false;
			\ze\file::retinaImageLink($width, $height, $url, $box['key']['id'], $widthLimit = 900, $heightLimit = 400);
			$cropImageBG = [
				'width' => $width,
				'height' => $height,
				'url' => $url
			];
		
		
			//Show options for crop and zoom.
			//There are three different modes of operation here:
				//We've opened the FAB from the backend. Show all of the existing options that have been saved.
				//We've opened the FAB from a plugin. Show all of the options used in that plugin.
				//This image is an SVG, which shouldn't display options for crop and zoom.
		
		
			//Get a list of every aspect image ratio that we're going to display
			$aspectRatios = [];
		
			//If opened from a plugin, look through the plugin settings, looking for settings named width/height/canvas.
			//Load all of the values of these settings.
			if ($box['key']['instanceId']) {
				$sql = "
					SELECT psW.value AS width, psH.value AS height, psC.name AS name
					FROM ". DB_PREFIX. "plugin_settings AS psC
					INNER JOIN ". DB_PREFIX. "plugin_settings AS psW
					   ON psW.instance_id = psC.instance_id
					  AND psW.egg_id = psC.egg_id
					  AND psW.name IN ('width', 'banner_width', 'mobile_width', 'image_width', 'image_2_width')
					  AND (psC.name, psW.name) IN (('canvas', 'width'), ('banner_canvas', 'banner_width'), ('mobile_canvas', 'mobile_width'), ('image_canvas', 'image_width'), ('image_2_canvas', 'image_2_width'))
					INNER JOIN ". DB_PREFIX. "plugin_settings AS psH
					   ON psH.instance_id = psC.instance_id
					  AND psH.egg_id = psC.egg_id
					  AND psH.name IN ('height', 'banner_height', 'mobile_height', 'image_height', 'image_2_height')
					  AND (psC.name, psH.name) IN (('canvas', 'height'), ('banner_canvas', 'banner_height'), ('mobile_canvas', 'mobile_height'), ('image_canvas', 'image_height'), ('image_2_canvas', 'image_2_height'))
					WHERE psC.name IN ('canvas', 'banner_canvas', 'mobile_canvas', 'image_canvas', 'image_2_canvas')
					  AND psC.value = 'crop_and_zoom'
					  AND psC.instance_id = ". (int) $box['key']['instanceId']. "
					  AND psC.egg_id IN (0, ". (int) $box['key']['eggId']. ")
					ORDER BY psC.egg_id DESC, psC.name ASC";
				
				$nestedBannerOverwritesSize = false;
				foreach (ze\sql::select($sql) as $setting) {
					
					//Catch the case where we have banners in a nest.
					//Don't show the width/height from the nest if it's been overwritten by the
					//width/height from the banner.
					switch ($setting['name']) {
						case 'canvas':
							$nestedBannerOverwritesSize = true;
							break;
						case 'banner_canvas':
							if ($nestedBannerOverwritesSize) {
								continue 2;
							}
							break;
					}
					
					//Convert to an aspect ratio
					$width = $height = 0;
					list($width, $height) = ze\file::aspectRatioRemoveFactors($setting['width'], $setting['height'], true);
			
					//I want to combine aspect ratios in the list, so if the same ratio is used twice it only
					//appears in the list once.
					//I also want to show the list in some kind of logical order.
					//Come up with a key that will achieve this
					$widthStr = (string) $width;
					$heightStr = (string) $height;
					$maxLen = max(strlen($widthStr), strlen($heightStr));
	
					$key = '1'.
						str_pad($widthStr, $maxLen, '0', STR_PAD_LEFT).
						str_pad($heightStr, $maxLen, '0', STR_PAD_LEFT);
			
					//Note down any new aspect ratios we find
					if (!isset($aspectRatios[$key])) {
						$aspectRatios[$key] = ['width' => $width, 'height' => $height];
					}
				}
			}
		
			//Load any previously saved crop values for this image.
			$sql = "
				SELECT *
				FROM ". DB_PREFIX. "cropped_images
				WHERE image_id = ". (int) $box['key']['id'];
		
			foreach (ze\sql::select($sql) as $crop) {
			
				//Use the same key rules as above to make sure we combine things properly
				$widthStr = (string) $crop['aspect_ratio_width'];
				$heightStr = (string) $crop['aspect_ratio_height'];
				$maxLen = max(strlen($widthStr), strlen($heightStr));

				$key = '1'.
					str_pad($widthStr, $maxLen, '0', STR_PAD_LEFT).
					str_pad($heightStr, $maxLen, '0', STR_PAD_LEFT);
			
				//Slightly different logic, depending on whether we've opened the FAB from a specific plugin.
				if (!isset($aspectRatios[$key])) {
				
					//For specific plugins, only load the values used on this plugin
					if ($box['key']['instanceId']) {
						continue;
				
					//In the backend, show every saved value
					} else {
						$aspectRatios[$key] = ['width' => $crop['aspect_ratio_width'], 'height' => $crop['aspect_ratio_height']];
					}
				}
		
				$aspectRatios[$key]['value'] = implode(',', [$crop['ui_crop_x'], $crop['ui_crop_y'], $crop['ui_crop_width'], $crop['ui_crop_height'], $crop['ui_image_width'], $crop['ui_image_height']]);
			}
		
			//Try to show the options in some sort of logical order
			ksort($aspectRatios);
		
			//Get rid of the defined "crop" tab, but turn it into a template we can use to make duplicates
			$templateCropTab = json_encode($box['tabs']['crop']);
			unset($box['tabs']['crop']);
		
			//Don't show the crop and zoom options if nothing was found above
			if (!empty($aspectRatios)) {
			
				//Collect some stats on every aspect ratio used on this site
				$arUsage = [];
				$sql = "
					SELECT psW.value AS width, psH.value AS height, psC.instance_id, m.class_name as module_class_name
					FROM ". DB_PREFIX. "plugin_settings AS psC
					INNER JOIN ". DB_PREFIX. "plugin_settings AS psW
					   ON psW.instance_id = psC.instance_id
					  AND psW.egg_id = psC.egg_id
					  AND psW.name IN ('width', 'banner_width', 'mobile_width', 'image_width', 'image_2_width')
					  AND (psC.name, psW.name) IN (('canvas', 'width'), ('banner_canvas', 'banner_width'), ('mobile_canvas', 'mobile_width'), ('image_canvas', 'image_width'), ('image_2_canvas', 'image_2_width'))
					INNER JOIN ". DB_PREFIX. "plugin_settings AS psH
					   ON psH.instance_id = psC.instance_id
					  AND psH.egg_id = psC.egg_id
					  AND psH.name IN ('height', 'banner_height', 'mobile_height', 'image_height', 'image_2_height')
					  AND (psC.name, psH.name) IN (('canvas', 'height'), ('banner_canvas', 'banner_height'), ('mobile_canvas', 'mobile_height'), ('image_canvas', 'image_height'), ('image_2_canvas', 'image_2_height'))
					INNER JOIN ". DB_PREFIX. "plugin_instances AS pi
					   ON pi.id = psC.instance_id
					INNER JOIN ". DB_PREFIX. "modules AS m
					   ON m.id = pi.module_id
					WHERE psC.name IN ('canvas', 'banner_canvas', 'mobile_canvas', 'image_canvas', 'image_2_canvas')
					  AND psC.value = 'crop_and_zoom'";

				foreach (ze\sql::select($sql) as $setting) {
					//Use the same logic as above to create a key
					$width = $height = 0;
					list($width, $height) = ze\file::aspectRatioRemoveFactors($setting['width'], $setting['height'], true);
					$widthStr = (string) $width;
					$heightStr = (string) $height;
					$maxLen = max(strlen($widthStr), strlen($heightStr));
	
					$key = '1'.
						str_pad($widthStr, $maxLen, '0', STR_PAD_LEFT).
						str_pad($heightStr, $maxLen, '0', STR_PAD_LEFT);
			
					//Note down any new aspect ratios we find
					if (!isset($arUsage[$key])) {
						$arUsage[$key] = ['width' => $width, 'height' => $height, 'banners' => 0, 'csls' => 0, 'nests' => 0, 'slideshows' => 0, 'plugins' => 0];
					}
				
					//Keep track of the counts
					switch ($setting['module_class_name']) {
						case 'zenario_banner':
							++$arUsage[$key]['banners'];
						
							if (!isset($arUsage[$key]['banner'])) {
								$arUsage[$key]['banner'] = $setting['instance_id'];
							}
							break;
					
						case 'zenario_content_list':
							++$arUsage[$key]['csls'];
						
							if (!isset($arUsage[$key]['csl'])) {
								$arUsage[$key]['csl'] = $setting['instance_id'];
							}
							break;
						
						case 'zenario_plugin_nest':
							++$arUsage[$key]['nests'];
						
							if (!isset($arUsage[$key]['nest'])) {
								$arUsage[$key]['nest'] = $setting['instance_id'];
							}
							break;
						
						case 'zenario_slideshow':
						case 'zenario_slideshow_simple':
							++$arUsage[$key]['slideshows'];
						
							if (!isset($arUsage[$key]['slideshow'])) {
								$arUsage[$key]['slideshow'] = $setting['instance_id'];
							}
							break;
					
						default:
							++$arUsage[$key]['plugins'];
						
							if (!isset($arUsage[$key]['plugin'])) {
								$arUsage[$key]['plugin'] = $setting['instance_id'];
							}
							break;
					}
				}
			
			
				//Loop through each aspect ratio, setting up the fields on that tab
				$ord = 0;
				$car = count($aspectRatios);
				foreach ($aspectRatios as $key => $ratio) {
				
					$tab = json_decode($templateCropTab, true);
					$tab['ord'] = 1000 + ++$ord;
					$tab['fields']['aspect_ratio_width']['value'] = $ratio['width'];
					$tab['fields']['aspect_ratio_height']['value'] = $ratio['height'];
			
					//Create a resize tool.
					//This should use the image we generated *way* above as the background,
					//only be visible when its option from the list is selected, and be locked
					//into the aspect ratio that it's for.
					$tab['fields']['crop_tool']['image'] = $cropImageBG;
					$tab['fields']['crop_tool']['image_crop_tool']['minSize'] = [$ratio['width'], $ratio['height']];
			
					//If we didn't load any values earlier, attempt to set default values for the aspect ratio.
					if (!isset($ratio['value'])) {
						//I'd like it as large as possible, and centred in the middle.
						//(I.e. the displayed defaults should be the same logic as the ze\file::scaleImageDimensionsByMode()
						// function uses when there are no presets defined.)
						if (($cropImageBG['width'] / $ratio['width']) > ($cropImageBG['height'] / $ratio['height'])) {
							$cropWidth = (int) ($ratio['width'] * $cropImageBG['height'] / $ratio['height']);
							$cropHeight = $cropImageBG['height'];
							$cropX = (int) (($cropImageBG['width'] - $cropWidth) / 2);
							$cropY = 0;
						} else {
							$cropWidth = $cropImageBG['width'];
							$cropHeight = (int) ($ratio['height'] * $cropImageBG['width'] / $ratio['width']);
							$cropX = 0;
							$cropY = (int) (($cropImageBG['height'] - $cropHeight) / 2);
						}
			
						$tab['fields']['crop_tool']['value'] = implode(',', [$cropX, $cropY, $cropWidth, $cropHeight, $cropImageBG['width'], $cropImageBG['height']]);
					} else {
						$tab['fields']['crop_tool']['value'] = $ratio['value'];
					}
				
				
				
					//Have slightly different logic, depending on how many crop options we had to show.
					//If there's just one, we can get away with having just one crop and zoom tab
					if ($car > 1) {
						//More than one, we need to turn the existing single tab into a drop-down list of
						//multiple tabs
						$tab['parent'] = 'crops';
						$tab['label'] = ze\admin::phrase('[[width]]:[[height]]', $ratio);
				
						if ($box['key']['instanceId']) {
							//$box['tabs']['crops']['label'] = ze\admin::phrase('Crops and zooms used in [[plugin]]', ['plugin' => ze\plugin::codeName($box['key']['instanceId'])]);
							$box['tabs']['crops']['label'] = ze\admin::phrase('Crops and zooms');
						} else {
							$box['tabs']['crops']['label'] = ze\admin::phrase('Created crops and zooms');
						}
				
					} else {
						if ($box['key']['instanceId']) {
							//$tab['label'] = ze\admin::phrase('Crop and zoom used in [[plugin]]', ['plugin' => ze\plugin::codeName($box['key']['instanceId'])]);
							$tab['label'] = ze\admin::phrase('Crop and zoom');
						} else {
							$box['tabs']['crops']['label'] = ze\admin::phrase('Created crop and zoom');
						}
					}
				
					$tab['fields']['usage']['snippet']['html'] = ze\admin::phrase('<strong style="font-weight: bold;">Aspect ratio:</strong> <code>[[width]]:[[height]]</code>', $ratio);
					
					//Display some usage stats
					if (isset($arUsage[$key])) {
						$tab['fields']['usage']['snippet']['html'] .=
							'<br/><em style="font-style: italic;">'.
							ze\admin::phrase('Used on').
							' '.
							implode('; ', ze\miscAdm::getUsageText($arUsage[$key], [], true)).
							'</em>';
					}
				
					$box['tabs']['crop_'. $ord] = $tab;
				}
			}
		}
		

		//If this is a MIC image, do not allow changing the image name.
		if ($box['key']['mic_image']) {
			$fields['details/filename']['read_only'] = true;
			$fields['details/filename']['show_as_a_span'] = true;

			unset($box['tabs']['details']['fields']['tags']);
			unset($box['tabs']['details']['fields']['floating_box_title']);
			
			unset($box['tabs']['details']['fields']['filename']['side_note']);
			$box['tabs']['details']['fields']['filename']['note_below'] = ze\admin::phrase(
				'Stored in the docstore, folder name [[folder_name]]. Actual filename in the docstore may differ.',
				['folder_name' => $details['path']]
			);
		} else {
			//Load details on the image tags in use in the system, and which have been chosen here
			$sql = "
				SELECT it.name, itl.tag_id
				FROM ". DB_PREFIX. "image_tags AS it
				LEFT JOIN ". DB_PREFIX. "image_tag_link AS itl
				ON itl.image_id = ". (int) $box['key']['id']. "
				AND itl.tag_id = it.id
				ORDER BY it.name";
			$result = ze\sql::select($sql);
			
			$pickedTagNames = [];
			while ($tag = ze\sql::fetchAssoc($result)) {
				if ($tag['tag_id']) {
					$pickedTagNames[] = $tag['name'];
				}
			}

			
			$box['tabs']['details']['fields']['tags']['value'] = implode(',', $pickedTagNames);
			$box['tabs']['details']['fields']['tags']['tag_colors'] = ze\contentAdm::getImageTagColours($byId = false, $byName = true);

			$box['tabs']['details']['fields']['floating_box_title']['value'] = $details['floating_box_title'];
		}

		$box['tabs']['details']['fields']['image_credit']['value'] = $details['image_credit'];
		
		
		switch ($details['privacy']) {
			case 'auto':
				$fields['details/privacy_auto']['hidden'] = false;
				break;
			
			case 'private':
				$fields['details/privacy_private']['hidden'] = false;
				break;
			
			case 'public':
				$fields['details/privacy_public']['hidden'] = false;
				
				$mrg = [];
				$mrg['path'] = 'public/images/'. $details['short_checksum']. '/'. ze\file::safeName($details['filename']);
				$mrg['link'] = ze\link::absolute(). $mrg['path'];
				
				$fields['details/privacy_public']['note_below'] =
					ze\admin::phrase('Public: this image can be accessed publicly via the URL [[link]], internal references to the image should be via [[path]]', $mrg);
				
				if (!file_exists(CMS_ROOT. $mrg['path'])) {
					$fields['details/missing_public_image']['hidden'] = false;
				}
				
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		$mimeType = ze\file::mimeType($values['details/filename']);
		$mimeTypeFromDb = ze\row::get('files', 'mime_type', $box['key']['id']);

		if (!$values['details/filename'] || !ze\file::guessAltTagFromname($values['details/filename'])) {
			//First check if just the extension is missing.
			if ($values['details/filename']) {
				if (!ze\file::isImageOrSVG($mimeType)) {
					$box['tabs']['details']['errors'][] = ze\admin::phrase('Please enter a filename with a valid extension.');
				}
			} else {
				$box['tabs']['details']['errors'][] = ze\admin::phrase('Please enter a filename.');
			}
		
		} elseif ($mimeType != $mimeTypeFromDb) {
			switch ($mimeTypeFromDb) {
				case 'image/gif':
					$errorMessage = "This file is a GIF, so its extension must be .gif (upper or lower case).";
					break;
				case 'image/jpeg':
					$errorMessage = "This file is a JPG, so its extension must be .jpg or .jpeg (upper or lower case).";
					break;
				case 'image/png':
					$errorMessage = "This file is a PNG, so its extension must be .png (upper or lower case).";
					break;
				case 'image/svg+xml':
					$errorMessage = "This file is an SVG, so its extension must be .svg (upper or lower case).";
					break;
				default:
					$errorMessage = "You must not change the file's extension.";
					break;
			}
			$box['tabs']['details']['errors'][] = ze\admin::phrase($errorMessage);
		
		} elseif ($values['details/filename'] !== ze\file::safeName($values['details/filename'])) {
			$box['tabs']['details']['errors'][] = ze\admin::phrase('The filename must not contain any of the following characters: \\ / : ; * ? " < > |');
		}
		
		if (!$box['key']['mic_image']) {
			//Ensure image tags are all lower-case
			$values['details/tags'] = mb_strtolower($values['details/tags']);
			
			$tags = ze\ray::explodeAndTrim($values['details/tags']);
			
			//Validate the image tags
			foreach ($tags as $tagName) {
				$tagName = trim($tagName);
			
				if (!ze\ring::validateScreenName(trim($tagName))) {
					$box['tabs']['details']['errors']['alphanumeric'] = ze\admin::phrase("Tag names can contain only alphanumeric characters, underscores or hyphens.");
				}
			}
		}
		
		
		$box['confirm']['show'] = false;
		
		if (empty($box['tabs']['details']['errors'])) {
			
			if (!$box['key']['mic_image'] && !empty($tags)) {
				$existingTags = ze\sql::fetchValues("
					SELECT name
					FROM ". DB_PREFIX. "image_tags
					WHERE name IN (". ze\escape::in($tags, 'sql'). ")
				");
			
			
				$newTags = [];
				foreach ($tags as $tagName) {
					if (!in_array($tagName, $existingTags)) {
						$newTags[] = $tagName;
					}
				}
			
				if (!empty($newTags)) {
					$count = count($newTags);
					$lastNewTag = array_pop($newTags);
					$mrg = ['newTags' => implode(', ', $newTags), 'lastNewTag' => $lastNewTag];
					$box['confirm']['show'] = true;
					$box['confirm']['message'] = ze\admin::nPhrase('The tag [[lastNewTag]] does not exist. Are you sure you wish to create it?',
						'The tags [[newTags]] and [[lastNewTag]] do not exist. Are you sure you wish to create them?', $count, $mrg);
					$box['confirm']['button_message'] = ze\admin::nPhrase('Create tag', 'Create tags', $count);
				}
			}
		}

	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_MANAGE_MEDIA');
		
		$details = [
			'filename' => $values['details/filename'],
			'alt_tag' => $values['details/alt_tag'],
			'image_credit' => $values['details/image_credit']
		];

		if (!$box['key']['mic_image']) {
			$details['floating_box_title'] = $values['details/floating_box_title'];
		}
		//Update the image's details
		ze\row::update('files', $details, $box['key']['id']);
		
		
		//Check whether any tags were picked
		if (!$box['key']['mic_image'] && $values['details/tags']
		 && ($tagNames = ze\escape::in($values['details/tags'], 'sql'))) {
			//If so, remove any tags that weren't picked
			$sql = "
				DELETE itl.*
				FROM ". DB_PREFIX. "image_tag_link AS itl
				LEFT JOIN ". DB_PREFIX. "image_tags AS it
				   ON it.name IN (". $tagNames. ")
				  AND it.id = itl.tag_id
				WHERE it.id IS NULL
				  AND itl.image_id = ". (int) $box['key']['id'];
			ze\sql::update($sql);
			
			//Check all added tags are in the database
			//Note: this logic is only safe because validateAdminBox() and the ze\escape::in() function above
			//will insure that there are no commas in the tag names.
			$sql = "
				INSERT IGNORE INTO ". DB_PREFIX. "image_tags (name)
				VALUES (". str_replace(',', '),(', $tagNames). ")";
			ze\sql::update($sql);
			
			//Add the tags that were picked
			$sql = "
				INSERT IGNORE INTO ". DB_PREFIX. "image_tag_link (image_id, tag_id)
				SELECT ". (int) $box['key']['id']. ", id
				FROM ". DB_PREFIX. "image_tags
				WHERE name IN (". $tagNames. ")
				ORDER BY id";
			ze\sql::update($sql);
		
		} else {
			//If no tags were picked, just remove any unused tags.
			ze\row::delete('image_tag_link', ['image_id' => $box['key']['id']]);
		}
		
		
		//Get the actual dimensions of the image
		$image = ze\row::get('files', ['width', 'height'], $box['key']['id']);
		
		//Check all of the crop sizes that shown
		$ord = 0;
		while (!empty($box['tabs'][$key = 'crop_'. ++$ord])) {
			
			if (($width = $values[$key. '/aspect_ratio_width'] ?? null)
			 && ($height = $values[$key. '/aspect_ratio_height'] ?? null)) {
				$crop = explode(',', $values[$key. '/crop_tool']);
				
				//Check we had meaningful numbers entered for each option
				if (!empty($crop[2])
				 && !empty($crop[3])
				 && !empty($crop[4])
				 && !empty($crop[5])) {
				 	
					//If all looks good, save into the database
				 	$row = [
						'ui_crop_x' => $crop[0],
						'ui_crop_y' => $crop[1],
						'ui_crop_width' => $crop[2],
						'ui_crop_height' => $crop[3],
						'ui_image_width' => $crop[4],
						'ui_image_height' => $crop[5],
						'crop_x' => (int) ($crop[0] * $image['width'] / $crop[4]),
						'crop_y' => (int) ($crop[1] * $image['height'] / $crop[5]),
						'crop_width' => (int) ($crop[2] * $image['width'] / $crop[4]),
						'crop_height' => (int) ($crop[3] * $image['height'] / $crop[5]),
						'aspect_ratio_angle' => \ze\file::aspectRatioToDegrees($crop[2], $crop[3])
					];
					$id = [
						'aspect_ratio_width' => $width,
						'aspect_ratio_height' => $height,
						'image_id' => $box['key']['id']
					];
					
					ze\row::set('cropped_images', $row, $id);
				}
			}
		}
	}
}
