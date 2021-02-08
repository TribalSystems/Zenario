<?php
/*
 * Copyright (c) 2021, Tribal Limited
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

class zenario_slideshow_simple extends zenario_slideshow {
	
	public function fillAdminSlotControls(&$controls) {
		if (ze\priv::check('_PRIV_MANAGE_REUSABLE_PLUGIN') && isset($controls['actions']['settings'])) {
			$controls['actions']['settings']['label'] = ze\admin::phrase('Slideshow properties');
			
			$controls['actions']['slideshow_settings'] = [
				'ord' => 1.1,
				'page_modes' => $controls['actions']['settings']['page_modes'],
				'onclick' => 'zenario_slideshow_simple.openSlideManager(
					this, 
					slotName, 
					zenario_slideshow_simple.AJAXLink({id: ' . (int)$this->instanceId . '})
				);'
			];
			
			$existingPlugins = ze\row::exists('nested_plugins', ['instance_id' => $this->instanceId, 'is_slide' => 0]);
			if ($existingPlugins) {
				$controls['actions']['slideshow_settings']['label'] = ze\admin::phrase('Add/edit slides in slideshow');
			} else {
				$controls['actions']['slideshow_settings']['label'] = ze\admin::phrase('Add slides to this slideshow');
			}
		}
	}
	
	public function handleAJAX() {
		switch($_REQUEST['mode'] ?? false) {
			case 'get_data':
				ze\priv::exitIfNot('_PRIV_MANAGE_REUSABLE_PLUGIN');
				
				$instanceId = $_REQUEST['id'];
				
				$groups = ze\datasetAdm::getGroupPickerCheckboxesForFAB();
				$s2Groups = [];
				foreach ($groups as $id => $group) {
					$s2Groups[$id] = ['label' => $group['label'], 'ord' => $group['ord']];
				}
				
				$smartGroups = ze\contentAdm::getListOfSmartGroupsWithCounts();
				$s2SmartGroups = [];
				$ord = 0;
				foreach ($smartGroups as $id => $label) {
					$s2SmartGroups[$id] = ['label' => $label, 'ord' => ++$ord];
				}
				
				$desktopCanvasSetting = ze\row::get('plugin_settings', 'value', ['name' => 'banner_canvas', 'instance_id' => $instanceId, 'egg_id' => 0]);
				$desktopCanvasSettingNiceName = '';
				switch ($desktopCanvasSetting) {
					case 'fixed_width':
						$desktopCanvasSettingNiceName = 'Constrain by width';
						break;
					case 'fixed_height':
						$desktopCanvasSettingNiceName = 'Constrain by height';
						break;
					case 'fixed_width_and_height':
						$desktopCanvasSettingNiceName = 'Constrain by width and height';
						break;
					case 'resize_and_crop':
						$desktopCanvasSettingNiceName = 'Resize and crop';
						break;
				}
				
				$mobileCanvasSetting = ze\row::get('plugin_settings', 'value', ['name' => 'mobile_canvas', 'instance_id' => $instanceId, 'egg_id' => 0]);
				$mobileCanvasSettingNiceName = '';
				switch ($mobileCanvasSetting) {
					case 'fixed_width':
						$mobileCanvasSettingNiceName = 'Constrain by width';
						break;
					case 'fixed_height':
						$mobileCanvasSettingNiceName = 'Constrain by height';
						break;
					case 'fixed_width_and_height':
						$mobileCanvasSettingNiceName = 'Constrain by width and height';
						break;
					case 'resize_and_crop':
						$mobileCanvasSettingNiceName = 'Resize and crop';
						break;
				}
				
				$details = [
					'slides' => static::getSlideshowForEditor($instanceId),
					'groups' => $s2Groups,
					'smartGroups' => $s2SmartGroups,
					'showTabs' => (bool)ze\row::get('plugin_settings', 'value', ['name' => 'show_tabs', 'instance_id' => $instanceId, 'egg_id' => 0]),
					'desktopCanvasSetting' => $desktopCanvasSetting,
					'desktopCanvasSettingNiceName' => $desktopCanvasSettingNiceName,
					'width' => (int)ze\row::get('plugin_settings', 'value', ['name' => 'banner_width', 'instance_id' => $instanceId, 'egg_id' => 0]),
					'height' => (int)ze\row::get('plugin_settings', 'value', ['name' => 'banner_height', 'instance_id' => $instanceId, 'egg_id' => 0]),
					'mobileCanvasSetting' => $mobileCanvasSetting,
					'mobileCanvasSettingNiceName' => $mobileCanvasSettingNiceName,
					'mobileWidth' => (int)ze\row::get('plugin_settings', 'value', ['name' => 'mobile_width', 'instance_id' => $instanceId, 'egg_id' => 0]),
					'mobileHeight' => (int)ze\row::get('plugin_settings', 'value', ['name' => 'mobile_height', 'instance_id' => $instanceId, 'egg_id' => 0])
				];
				
				echo json_encode($details);
				break;
				
			case 'file_upload':
				ze\priv::exitIfNot('_PRIV_MANAGE_REUSABLE_PLUGIN');
				
				ze\fileAdm::exitIfUploadError(true, false, true, 'Filedata');
				
				//Outputs file URL
				ze\fileAdm::putUploadFileIntoCacheDir($_FILES['Filedata']['name'], $_FILES['Filedata']['tmp_name'], ($_REQUEST['_html5_backwards_compatibility_hack'] ?? false), $dropboxLink = false, $cacheFor = 3600);
				break;
			
			case 'add_slides_from_organizer':
				ze\priv::exitIfNot('_PRIV_MANAGE_REUSABLE_PLUGIN');
				
				$instanceId = $_REQUEST['id'];
				
				$fileIds = explode(',', ($_GET['ids'] ?? ''));
				$slides = [];
				if ($fileIds) {
					foreach($fileIds as $fileId) {
						$slides[] = static::createSlideFromImage($fileId);
					}
				}
				echo json_encode($slides);
				break;
			
			case 'save_slides':
				ze\priv::exitIfNot('_PRIV_MANAGE_REUSABLE_PLUGIN');
				
				$instanceId = $_REQUEST['id'];
				
				$slides = json_decode($_POST['slides'], true);
				$deletedSlideIds = json_decode($_POST['deletedSlideIds'], true);
				
				$errors = static::validateSlides($slides);
				
				if (!$errors) {
					static::saveSlides($instanceId, $slides, $deletedSlideIds);
				}
				
				echo json_encode($errors);
				break;
		}
	}
	
	public static function getSlideshowForEditor($instanceId) {
		$slides = [];
		$ord = 0;
		$nestedSlidesResult = ze\row::query('nested_plugins', true, ['instance_id' => $instanceId, 'is_slide' => 1], 'slide_num');
		while ($nestedSlide = ze\sql::fetchAssoc($nestedSlidesResult)) {
			//Get banner egg_id
			$nestedBanner = ze\row::get('nested_plugins', ['id'], ['instance_id' => $instanceId, 'slide_num' => $nestedSlide['slide_num'], 'is_slide' => 0]);
			
			//Only load valid slides (ones with a banner on)
			if (!$nestedBanner) {
				continue;
			}
			
			//Get settings for banner on slide
			$settings = [];
			$settingsResult = ze\row::query('plugin_settings', ['name', 'value'], ['instance_id' => $instanceId, 'egg_id' => $nestedBanner['id']]);
			while ($row = ze\sql::fetchAssoc($settingsResult)) {
				$settings[$row['name']] = $row['value'];
			}
			
			$slideId = $nestedSlide['id'];
			$slide = [
				'id' => $slideId,
				'ord' => ++$ord,
				'image_id' => $settings['image'] ?? false,
				'overwrite_alt_tag' => $settings['alt_tag'] ?? '',
				'tab_name' => $nestedSlide['name_or_title'],
				'slide_title' => $settings['title'] ?? '',
				'slide_extra_html' => $settings['text'] ?? '',
				'slide_more_link_text' => $settings['more_link_text'] ?? '',
				'rollover_image_id' => $settings['rollover_image'] ?? false,
				'mobile_image_id' => $settings['mobile_image'] ?? false,
				'link_type' => $settings['link_type'] ?? false,
				'url' => $settings['url'] ?? '',
				'hyperlink_target' => $settings['hyperlink_target'] ?? '',
				'open_in_new_window' => ($settings['target_blank'] ?? false) == 1,
				'privacy' => $nestedSlide['privacy']
			];
			
			if ($slide['image_id']) {
				$file = ze\row::get('files', ['width', 'height', 'filename'], (int)$slide['image_id']);
				$slide['width'] = $file['width'];
				$slide['height'] = $file['height'];
				$slide['filename'] = $file['filename'];
				
				$width = $height = false;
				ze\file::imageLink($width, $height, $slide['image_details_thumbnail_url'], $slide['image_id'], 300, 150);
			
				$width = $height = false;
				ze\file::imageLink($width, $height, $slide['image_list_thumbnail_url'], $slide['image_id'], 150, 150);
			}
			
			if ($slide['rollover_image_id']) {
				$file = ze\row::get('files', ['width', 'height', 'filename'], (int)$slide['rollover_image_id']);
				$slide['r_width'] = $file['width'];
				$slide['r_height'] = $file['height'];
				$slide['r_filename'] = $file['filename'];
				
				$width = $height = false;
				ze\file::imageLink($width, $height, $slide['rollover_image_details_thumbnail_url'], $slide['rollover_image_id'], 300, 150);
			}
			
			if ($slide['mobile_image_id']) {
				if ($slide['image_id'] == $slide['mobile_image_id']) {
					$slide['mobile_behaviour'] = 'same_image_different_size';
					$slide['mobile_image_id'] = false;
				} else {
					$slide['mobile_behaviour'] = 'different_image';
					$file = ze\row::get('files', ['width', 'height', 'filename'], (int)$slide['mobile_image_id']);
					$slide['m_width'] = $file['width'];
					$slide['m_height'] = $file['height'];
					$slide['m_filename'] = $file['filename'];
				
					$width = $height = false;
					ze\file::imageLink($width, $height, $slide['mobile_image_details_thumbnail_url'], $slide['mobile_image_id'], 300, 150);
				}
			}
			
			if ($slide['link_type'] == '_CONTENT_ITEM' && $slide['hyperlink_target']) {
				$slide['hyperlink_target_display'] = ze\content::formatTagFromTagId($slide['hyperlink_target']);
			}
			
			if ($slide['privacy'] == 'group_members') {
				$groupIds = ze\row::getValues('group_link', 'link_to_id', ['link_to' => 'group', 'link_from' => 'slide', 'link_from_id' => $nestedSlide['id']]);
				$slide['group_ids'] = [];
				foreach ($groupIds as $groupId) {
					$slide['group_ids'][$groupId] = true;
				}
			} elseif ($slide['privacy'] == 'in_smart_group' || $slide['privacy'] == 'logged_in_not_in_smart_group') {
				$slide['smart_group_id'] = $nestedSlide['smart_group_id'];
			} elseif ($slide['privacy'] == 'call_static_method') {
				$slide['module_class_name'] = $nestedSlide['module_class_name'];
				$slide['method_name'] = $nestedSlide['method_name'];
				$slide['param_1'] = $nestedSlide['param_1'];
				$slide['param_2'] = $nestedSlide['param_2'];
			}
			
			
			$slides[$slideId] = $slide;
		}
		
		return $slides;
	}
	
	public static function validateSlides($slides) {
		$errors = [];
		
		foreach ($slides as $slide) {
			if (($slide['mobile_behaviour'] ?? false) == 'different_image' && !$slide['mobile_image_id']) {
				$errors[$slide['id']]['mobile'][] = ze\admin::phrase('Select a mobile image.');
			}
		}
		
		return $errors;
	}
	
	public static function saveSlides($instanceId, $slides, $deletedSlideIds) {
		//Delete slides 
		foreach ($deletedSlideIds as $slideId) {
			if (is_numeric($slideId)) {
				static::removeSlide($slideId, $instanceId);
			}
		}
	
		usort($slides, function($a, $b) { return $a['ord'] - $b['ord']; });
	
		$ids = [];
		$ordinals = [];
		$parentIds = [];
	
		$ord = 0;
		foreach ($slides as $slide) {
			$nestedSlideId = $slide['id'];
			
			if (!is_numeric($slide['image_id'])) {
				$path = ze\file::getPathOfUploadInCacheDir($slide['image_id']);
				$slide['image_id'] = ze\file::addToDatabase('image', $path);
			}
			
			//Create slides
			if (!is_numeric($nestedSlideId)) {
				$nestedSlideId = zenario_plugin_nest::addSlide($instanceId);
				$nestedBannerId = zenario_plugin_nest::addBanner($slide['image_id'], $instanceId, $nestedSlideId, $inputIsSlideId = true);
			} else {
				$nestedSlide = ze\row::get('nested_plugins', ['slide_num'], $nestedSlideId);
				$nestedBannerId = ze\row::get('nested_plugins', 'id', ['instance_id' => $instanceId, 'slide_num' => $nestedSlide['slide_num'], 'is_slide' => 0]);
			}
		
			//Update details
			$slideDetails = [
				'privacy' => $slide['privacy'] ?? 'public',
				'always_visible_to_admins' => 1,
				'smart_group_id' => 0,
				'module_class_name' => '',
				'method_name' => '',
				'param_1' => '',
				'param_2' => ''
			];
		
			if (ze\row::get('plugin_settings', 'value', ['name' => 'show_tabs', 'instance_id' => $instanceId, 'egg_id' => 0])) {
				$slideDetails['name_or_title'] = $slide['tab_name'] ?? '';
			}
			
			
			//Keep slide banner settings in sync with slideshow mobile canvas settings
			ze\row::set('plugin_settings', 
				['value' => ze\row::get('plugin_settings', 'value', ['name' => 'mobile_canvas', 'instance_id' => $instanceId, 'egg_id' => 0])], 
				['name' => 'mobile_canvas', 'instance_id' => $instanceId, 'egg_id' => $nestedBannerId]);
			ze\row::set('plugin_settings', 
				['value' => ze\row::get('plugin_settings', 'value', ['name' => 'mobile_width', 'instance_id' => $instanceId, 'egg_id' => 0])], 
				['name' => 'mobile_width', 'instance_id' => $instanceId, 'egg_id' => $nestedBannerId]);
			ze\row::set('plugin_settings', 
				['value' => ze\row::get('plugin_settings', 'value', ['name' => 'mobile_height', 'instance_id' => $instanceId, 'egg_id' => 0])], 
				['name' => 'mobile_height', 'instance_id' => $instanceId, 'egg_id' => $nestedBannerId]);
			
			
			
			switch ($slideDetails['privacy']) {
				case 'in_smart_group':
				case 'logged_in_not_in_smart_group':
					$slideDetails['smart_group_id'] = $slide['smart_group_id'];
					break;

				case 'call_static_method':
					$slideDetails['module_class_name'] = $slide['module_class_name'];
					$slideDetails['method_name'] = $slide['method_name'];
					$slideDetails['param_1'] = $slide['param_1'];
					$slideDetails['param_2'] = $slide['param_2'];
					break;
			}
		
			ze\row::update('nested_plugins', $slideDetails, $nestedSlideId);
		
			$key = ['link_to' => 'group', 'link_from' => 'slide', 'link_from_id' => $nestedSlideId];
			if ($slideDetails['privacy'] == 'group_members') {
				ze\miscAdm::updateLinkingTable('group_link', $key, 'link_to_id', array_keys($slide['group_ids']));
			} else {
				ze\row::delete('group_link', $key);
			}
		
			ze\row::set('plugin_settings', 
				['value' => $slide['image_id']], 
				['name' => 'image', 'instance_id' => $instanceId, 'egg_id' => $nestedBannerId]);
			ze\row::set('plugin_settings', 
				['value' => $slide['overwrite_alt_tag'] ?? ''], 
				['name' => 'alt_tag', 'instance_id' => $instanceId, 'egg_id' => $nestedBannerId]);
			ze\row::set('plugin_settings', 
				['value' => $slide['slide_title'] ?? ''], 
				['name' => 'title', 'instance_id' => $instanceId, 'egg_id' => $nestedBannerId]);
			ze\row::set('plugin_settings', 
				['value' => $slide['slide_extra_html'] ?? ''], 
				['name' => 'text', 'instance_id' => $instanceId, 'egg_id' => $nestedBannerId]);
		
		
			$linkType = $slide['link_type'] ?? false;
			$moreLinkText = '';
			$externalURL = '';
			$internalTarget = '';
			if ($linkType == '_EXTERNAL_URL') {
				$externalURL = $slide['url'] ?? '';
			} elseif ($linkType == '_CONTENT_ITEM') {
				$internalTarget = $slide['hyperlink_target'] ?? '';
			}
			if ($linkType == '_EXTERNAL_URL' || $linkType == '_CONTENT_ITEM') {
				$moreLinkText = $slide['slide_more_link_text'];
			}
			ze\row::set('plugin_settings', 
				['value' => $linkType], 
				['name' => 'link_type', 'instance_id' => $instanceId, 'egg_id' => $nestedBannerId]);
			ze\row::set('plugin_settings', 
				['value' => $externalURL], 
				['name' => 'url', 'instance_id' => $instanceId, 'egg_id' => $nestedBannerId]);
			ze\row::set('plugin_settings', 
				['value' => $internalTarget], 
				['name' => 'hyperlink_target', 'instance_id' => $instanceId, 'egg_id' => $nestedBannerId]);
			ze\row::set('plugin_settings', 
				['value' => $moreLinkText], 
				['name' => 'more_link_text', 'instance_id' => $instanceId, 'egg_id' => $nestedBannerId]);
			ze\row::set('plugin_settings', 
				['value' => ($slide['open_in_new_window'] ?? false) ? 1 : ''], 
				['name' => 'target_blank', 'instance_id' => $instanceId, 'egg_id' => $nestedBannerId]);
			
			
			$advancedBehaviour = 'none';
			$rolloverImageId = false;
			$mobileImageId = false;
			if (!empty($slide['mobile_behaviour']) && ($slide['mobile_behaviour'] == 'same_image_different_size' || $slide['mobile_behaviour'] == 'different_image')) {
			
				if ($slide['mobile_behaviour'] == 'same_image_different_size') {
					$advancedBehaviour = 'mobile_same_image_different_size';
					$mobileImageId = $slide['image_id'];
				} elseif ($slide['mobile_behaviour'] == 'different_image') {
					$advancedBehaviour = 'mobile_change_image';
					if (!is_numeric($slide['mobile_image_id'])) {
						$path = ze\file::getPathOfUploadInCacheDir($slide['mobile_image_id']);
						$mobileImageId = ze\file::addToDatabase('image', $path);
					} else {
						$mobileImageId = $slide['mobile_image_id'];
					}
				}
				
			} elseif (!empty($slide['rollover_image_id'])) {
				$advancedBehaviour = 'use_rollover';
				
				if (!is_numeric($slide['rollover_image_id'])) {
					$path = ze\file::getPathOfUploadInCacheDir($slide['rollover_image_id']);
					$rolloverImageId = ze\file::addToDatabase('image', $path);
				} else {
					$rolloverImageId = $slide['rollover_image_id'];
				}
			}
			
			ze\row::set('plugin_settings', 
				['value' => $advancedBehaviour], 
				['name' => 'advanced_behaviour', 'instance_id' => $instanceId, 'egg_id' => $nestedBannerId]);
			ze\row::set('plugin_settings', 
				['value' => $rolloverImageId], 
				['name' => 'rollover_image', 'instance_id' => $instanceId, 'egg_id' => $nestedBannerId]);
			ze\row::set('plugin_settings', 
				['value' => $mobileImageId], 
				['name' => 'mobile_image', 'instance_id' => $instanceId, 'egg_id' => $nestedBannerId]);			
			
			//Paremeters for reordering
			$ids[] = $nestedSlideId;
			$ids[] = $nestedBannerId;
			$ordinals[$nestedSlideId] = ++$ord;
			$ordinals[$nestedBannerId] = 1;
			$parentIds[$nestedSlideId] = 0;
			$parentIds[$nestedBannerId] = $nestedSlideId;
		}
	
		//Remove nest plugins not found in request
		$sql = '
			SELECT id
			FROM ' . DB_PREFIX . 'nested_plugins
			WHERE instance_id = ' . (int)$instanceId;
		if ($ids) {
			$sql .= '
				AND id NOT IN (' . ze\escape::in($ids) . ')';
		}
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			static::removeSlide($row['id'], $instanceId);
		}
	
		//Set slides order
		static::reorderNest($instanceId, $ids, $ordinals, $parentIds);
	}
	
	public static function createSlideFromImage($fileId) {
		$sql = "
			SELECT id AS image_id, filename, alt_tag,  width, height
			FROM ". DB_PREFIX. "files
			WHERE id = ". (int)$fileId;
		$result = ze\sql::select($sql);
		$slide = ze\sql::fetchAssoc($result);
		
		$width = $height = false;
		ze\file::imageLink($width, $height, $slide['image_details_thumbnail_url'], $slide['image_id'], 300, 150);
		
		$width = $height = false;
		ze\file::imageLink($width, $height, $slide['image_list_thumbnail_url'], $slide['image_id'], 150, 150);
		
		return $slide;
	}
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				if (isset($box['tabs']['size']['fields']['mobile_canvas'])) {
					$this->showHideImageOptions($fields, $values, 'size', $hidden = false, $fieldPrefix = 'mobile_', $hasCanvas = true);
				}
				break;
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				
				//When changing the mobile canvas option for the whole slideshow, update the local setting on all banners.
				$instanceId = $box['key']['instanceId'];
				$result = ze\row::query('nested_plugins', ['id'], ['instance_id' => $instanceId, 'is_slide' => 0]);
				while ($row = ze\sql::fetchAssoc($result)) {
					ze\row::set('plugin_settings', 
						['value' => $values['size/mobile_canvas']], 
						['name' => 'mobile_canvas', 'instance_id' => $instanceId, 'egg_id' => $row['id']]);
					ze\row::set('plugin_settings', 
						['value' => $values['size/mobile_width']], 
						['name' => 'mobile_width', 'instance_id' => $instanceId, 'egg_id' => $row['id']]);
					ze\row::set('plugin_settings', 
						['value' => $values['size/mobile_height']], 
						['name' => 'mobile_height', 'instance_id' => $instanceId, 'egg_id' => $row['id']]);
				}
				
				break;
		}
	}
}