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

class zenario_promo_menu extends zenario_menu_multicolumn {
	
	var $menuArray = [];
	
	function drawMenu(&$menuArray, $recurseCount = 0, $headerObjects = [], $subSections = []) {
		return $menuArray;
	}
	
	public function init() {
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetOrPostVarIsSet = true, $ifSessionVarOrCookieIsSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = true, $clearByFile = false, $clearByModuleData = false);
		$this->showInMenuMode();
		if ($this->sectionId === false) {
			$this->sectionId = $this->setting('menu_section');
		}
		$this->sectionId = ze\menu::sectionId($this->sectionId);
		
		// Get the Menu Node for this content item
		$this->currentMenuId = ze\menu::getIdFromContentItem(ze::$equivId, ze::$cType, $this->sectionId);
		$this->numLevels = 3;
		$this->maxLevel1MenuItems = 999;
		$this->language = false;
		$this->onlyFollowOnLinks = false;
		$this->onlyIncludeOnLinks = false;
		$this->showInvisibleMenuItems = false;
		$this->showMissingMenuNodes = $this->setting('show_missing_menu_nodes');
		$cachingRestrictions = 0;
		
		$parentMenuId = $this->getStartNode();
		
		$this->menuArray['nodes'] =
			ze\menu::getStructure($cachingRestrictions,
				$this->sectionId, $this->currentMenuId, $parentMenuId,
				$this->numLevels, $this->maxLevel1MenuItems, $this->language,
				$this->onlyFollowOnLinks, $this->onlyIncludeOnLinks, 
				$this->showInvisibleMenuItems,
				$this->showMissingMenuNodes,
				false,
				ze\content::showUntranslatedContentItems()
			);
		
		switch ($cachingRestrictions) {
			case ze\menu::privateItemsExist:
				$this->allowCaching(
					$atAll = true, $ifUserLoggedIn = false, $ifGetOrPostVarIsSet = true, $ifSessionVarOrCookieIsSet = true);
				break;
			case ze\menu::staticFunctionCalled:
				$this->allowCaching(false);
				break;
		}
		
		$canvasSetting = $this->setting('canvas');
		$retinaSetting = $this->setting('retina');
		$widthSetting = $this->setting('width');
		$heightSetting = $this->setting('height');
		
		$this->menuArray['maxInCol'] = $this->setting('max_items_per_column');
		$sql = '
			SELECT 
				n.id,
				n.image_id,
				n.rollover_image_id,
				m.use_feature_image,
				m.image_id AS feature_image_id,
				m.use_rollover_image ,
				m.rollover_image_id AS feature_rollover_image_id,
				m.title,
				m.text,
				m.link_type,
				m.link_visibility,
				m.dest_url,
				m.open_in_new_window,
				m.overwrite_alt_tag,
				t.top_of_column,
				f.alt_tag,
				n.target_loc,
				mt.ext_url
			FROM '.DB_PREFIX.'menu_nodes n
			LEFT JOIN '.DB_PREFIX. 'menu_node_feature_image m
				ON n.id = m.node_id
			LEFT JOIN '. DB_PREFIX. 'menu_text AS mt
				ON mt.menu_id = n.id
			LEFT JOIN '.DB_PREFIX. ZENARIO_MENU_MULTICOLUMN_PREFIX. 'nodes_top_of_column t
				ON n.id = t.node_id
			LEFT JOIN '.DB_PREFIX. 'files f
				ON m.image_id = f.id
			WHERE section_id = '.(int)$this->sectionId;
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			if ($row['target_loc'] == 'ext' && $row['ext_url']) {
				$row['link_external'] = true;
			}
			
			// Set image links:
			
			//Featured image...
			$url = $width = $height = false;
			ze\file::imageLink($width, $height, $url, $row['feature_image_id'], $widthSetting, $heightSetting, $canvasSetting, $offset = 0, $retinaSetting);
			if ($retinaSetting) {
				$row['Image_Srcset'] = $url. ' 2x';
			}
			$row['feature_image_width'] = $width;
			$row['feature_image_height'] = $height;
			$row['feature_image_link'] = $url;
			
			//Featured image rollover...
			if ($row['use_rollover_image'] && $row['feature_rollover_image_id']) {
				$url = $width = $height = false;
				ze\file::imageLink($width, $height, $url, $row['feature_rollover_image_id'], $widthSetting, $heightSetting, $canvasSetting, $offset = 0, $retinaSetting);
				if ($retinaSetting) {
					$row['Rollover_Image_Srcset'] = $url. ' 2x';
				}
				$row['feature_rollover_image_link'] = $url;
			}
			
			//Node image...
			if ($this->setting('show_thumbnail_menu_node_icons')) {
				$url = $width = $height = false;
				$thumbnailCanvasSetting = $this->setting('thumbnail_menu_node_icon_canvas');
				$thumbnailRetinaSetting = $this->setting('thumbnail_menu_node_icon_retina');
				$thumbnailWidthSetting = $this->setting('thumbnail_menu_node_icon_width');
				$thumbnailHeightSetting = $this->setting('thumbnail_menu_node_icon_height');
				
				ze\file::imageLink($width, $height, $url, $row['image_id'], $thumbnailWidthSetting, $thumbnailHeightSetting, $thumbnailCanvasSetting, $offset = 0, $thumbnailRetinaSetting);
				$row['node_image_width'] = $width;
				$row['node_image_height'] = $height;
				$row['image_link'] = $url;
			
				if ($row['overwrite_alt_tag']) {
					$row['alt_tag'] = $row['overwrite_alt_tag'];
				}
				
				//and Node image rollover.
				if ($row['rollover_image_id']) {
					$url = $width = $height = false;
					ze\file::imageLink($width, $height, $url, $row['rollover_image_id']);
					$row['rollover_image_link'] = $url;
				}
			}
			
			// Set link if to content item
			$cID = $cType = false;
			if (($row['link_type'] == 'content_item') && ze\content::getCIDAndCTypeFromTagId($cID, $cType, $row['dest_url'])) {
				ze\content::langEquivalentItem($cID, $cType);
				$row['dest_url'] = ze\link::toItem($cID, $cType);
			}
			
			$row['show_banner'] = false;
			switch($row['link_visibility']) {
				case 'private':
					if (ze\content::checkPerm($cID, $cType)) {
						$row['show_banner'] = true;
					}
					break;
				case 'logged_out':
					if (!($_SESSION['extranetUserID'] ?? false)) {
						$row['show_banner'] = true;
					}
					break;
				case 'logged_in':
					if ($_SESSION['extranetUserID'] ?? false) {
						$row['show_banner'] = true;
					}
					break;
				case 'always_show':
					$row['show_banner'] = true;
					break;
			}
			$this->menuArray['nodeExtraProperties'][$row['id']] = $row;
		}
		
		return true;
	}
	
	public function showSlot() {
		$this->twigFramework($this->menuArray);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				$hidden = false;
				$this->showHideImageOptions($fields, $values, 'promo_images', $hidden);
				
				//Retina support
				$retinaSideNote = "If the source image is large enough,
							the resized image will be output at twice its displayed width &amp; height
							to appear crisp on retina screens.
							This will increase the download size.
							<br/>
							If the source image is not large enough this will have no effect.";
		
				if ($values['promo_images/canvas'] != "unlimited") {
					$fields['promo_images/canvas']['side_note'] = $retinaSideNote;
				} else {
					$fields['promo_images/canvas']['side_note'] = "";
				}
				
				$hidden = !$values['promo_images/show_thumbnail_menu_node_icons'];
				$this->showHideImageOptions($fields, $values, 'promo_images', $hidden, 'thumbnail_menu_node_icon_');
				$fields['promo_images/thumbnail_menu_node_icon_canvas']['side_note'] = $retinaSideNote;
				
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'plugin_settings':
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				break;
		}
	}
}