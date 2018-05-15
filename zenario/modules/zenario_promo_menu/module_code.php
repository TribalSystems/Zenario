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

class zenario_promo_menu extends zenario_menu_multicolumn {
	
	var $menuArray = false;
	
	function drawMenu(&$menuArray, $recurseCount = 0, $headerObjects = [], $subSections = []) {
		return $menuArray;
	}
	
	public function init() {
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = true, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
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
		$cachingRestrictions = false;
		
		$parentMenuId = $this->getStartNode();
		
		$this->menuArray['nodes'] =
			ze\menu::getStructure($cachingRestrictions,
							 $this->sectionId, $this->currentMenuId, $parentMenuId,
							 $this->numLevels, $this->maxLevel1MenuItems, $this->language,
							 $this->onlyFollowOnLinks, $this->onlyIncludeOnLinks, 
							 $this->showInvisibleMenuItems,
							 $this->showMissingMenuNodes,
							 false,
							 ze\content::showUntranslatedContentItems());
							 
		switch ($cachingRestrictions) {
			case 'privateItemsExist':
				$this->allowCaching(
					$atAll = true, $ifUserLoggedIn = false, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
				break;
			case 'staticFunctionCalled':
				$this->allowCaching(false);
				break;
		}
		
		$this->menuArray['maxInCol'] = $this->setting('max_items_per_column');
		$sql = '
			SELECT 
				n.id,
				n.image_id,
				n.rollover_image_id,
				m.use_feature_image, 
				m.image_id AS feature_image_id, 
				m.canvas, 
				m.width, 
				m.height,
				m.offset,
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
				f.alt_tag
			FROM '.DB_PREFIX.'menu_nodes n
			LEFT JOIN '.DB_PREFIX. ZENARIO_PROMO_MENU_PREFIX. 'menu_node_feature_image m
				ON n.id = m.node_id
			LEFT JOIN '.DB_PREFIX. ZENARIO_MENU_MULTICOLUMN_PREFIX. 'nodes_top_of_column t
				ON n.id = t.node_id
			LEFT JOIN '.DB_PREFIX. 'files f
				ON m.image_id = f.id
			WHERE section_id = '.(int)$this->sectionId;
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			// Set image links
			$url = $width = $height = false;
			ze\file::imageLink($width, $height, $url, $row['feature_image_id'], $row['width'], $row['height'], $row['canvas'], $row['offset']);
			$row['feature_image_link'] = $url;
			
			$url = $width = $height = false;
			ze\file::imageLink($width, $height, $url, $row['image_id']);
			$row['image_link'] = $url;
			
			if ($row['overwrite_alt_tag']) {
				$row['alt_tag'] = $row['overwrite_alt_tag'];
			}
			
			if ($row['use_rollover_image'] && $row['feature_rollover_image_id']) {
				$url = $width = $height = false;
				ze\file::imageLink($width, $height, $url, $row['feature_rollover_image_id'], $row['width'], $row['height'], $row['canvas'], $row['offset']);
				$row['feature_rollover_image_link'] = $url;
			}
			
			if ($row['rollover_image_id']) {
				$url = $width = $height = false;
				ze\file::imageLink($width, $height, $url, $row['rollover_image_id']);
				$row['rollover_image_link'] = $url;
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
	
	public static function getFeatureImageId($nodeId) {
		return ze\row::get(ZENARIO_PROMO_MENU_PREFIX. 'menu_node_feature_image', 'image_id', ['node_id' => $nodeId, 'use_feature_image' => 1]);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch($path) {
			case 'zenario_menu':
				$nodeId = $box['key']['id'];
				$row = ze\row::get(ZENARIO_PROMO_MENU_PREFIX. 'menu_node_feature_image',
					['use_feature_image', 'image_id', 'canvas', 'width', 'height', 'offset', 'use_rollover_image', 'rollover_image_id', 'title', 'text', 'link_type', 'link_visibility', 'dest_url', 'open_in_new_window', 'overwrite_alt_tag'],
					['node_id' => $nodeId]);
				$box['key']['feature_image_id'] = $row['image_id'] ? $row['image_id'] : '';
				$file = ze\row::get('files', ['alt_tag'], $row['image_id']);
				$fields['feature_image/zenario_promo_menu__overwrite_alt_tag']['multiple_edit']['original_value'] = 
					$file['alt_tag'];
				
				if ($row['overwrite_alt_tag']) {
					$values['feature_image/zenario_promo_menu__overwrite_alt_tag'] = $row['overwrite_alt_tag'];
					$fields['feature_image/zenario_promo_menu__overwrite_alt_tag']['multiple_edit']['changed'] = true;
				} else {
					$values['feature_image/zenario_promo_menu__overwrite_alt_tag'] = $file['alt_tag'];
				}

				if ($box['key']['id'] && ($menu = ze\menu::details($box['key']['id']))) {
					
					if($menu['image_id']){
						$values['feature_image/show_image'] = true;
					}else{
						$menu['image_id']=false;
					}
					$values['feature_image/image_id'] = $menu['image_id'];
					$values['feature_image/use_rollover_image'] = (bool) $menu['rollover_image_id'];
					$values['feature_image/rollover_image_id'] = $menu['rollover_image_id'];
				}
				
				$values['feature_image/zenario_promo_menu__feature_image_checkbox'] = $row['use_feature_image'];
				$values['feature_image/zenario_promo_menu__feature_image'] = $row['image_id'];
				$values['feature_image/zenario_promo_menu__canvas'] = empty($row['canvas']) ? 'unlimited' : $row['canvas'];
				$values['feature_image/zenario_promo_menu__width'] = $row['width'];
				$values['feature_image/zenario_promo_menu__height'] = $row['height'];
				$values['feature_image/zenario_promo_menu__offset'] = $row['offset'];
				$values['feature_image/zenario_promo_menu__use_rollover'] = $row['use_rollover_image'];
				$values['feature_image/zenario_promo_menu__rollover_image'] = $row['rollover_image_id'];
				$values['feature_image/zenario_promo_menu__title'] = $row['title'];
				$values['feature_image/zenario_promo_menu__text'] = $row['text'];
				$values['feature_image/zenario_promo_menu__link_type'] = empty($row['link_type']) ? 'no_link' : $row['link_type'];
				switch($row['link_type']) {
					case 'content_item':
						$values['feature_image/zenario_promo_menu__hyperlink_target'] = $row['dest_url'];
						break;
					case 'external_url':
						$values['feature_image/zenario_promo_menu__url'] = $row['dest_url'];
						break;
				}
				$values['feature_image/zenario_promo_menu__hide_private_item'] = empty($row['link_visibility']) ? 'always_show' : $row['link_visibility'];
				$values['feature_image/zenario_promo_menu__target_blank'] = $row['open_in_new_window'];
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch($path) {
			case 'zenario_menu':
				
				$imageId = $values['feature_image/zenario_promo_menu__feature_image'];
				
				if ($imageId != $box['key']['feature_image_id']) {
					$alt_tag = '';
					if ($imageDetails = ze\row::get('files', ['alt_tag'], $imageId)) {
						$alt_tag = $imageDetails['alt_tag'];
					}
					$fields['feature_image/zenario_promo_menu__overwrite_alt_tag']['changed'] = false;
					$fields['feature_image/zenario_promo_menu__overwrite_alt_tag']['multiple_edit']['original_value'] = 
					$values['feature_image/zenario_promo_menu__overwrite_alt_tag'] = 
						$alt_tag;
				}
				
				$fields['feature_image/zenario_promo_menu__feature_image']['hidden'] =
				$fields['feature_image/zenario_promo_menu__use_rollover']['hidden'] =
				$fields['feature_image/zenario_promo_menu__title']['hidden'] =
				$fields['feature_image/zenario_promo_menu__text']['hidden'] =
				$fields['feature_image/zenario_promo_menu__link_type']['hidden'] =
					!$values['feature_image/zenario_promo_menu__feature_image_checkbox'];
				
				$fields['feature_image/zenario_promo_menu__overwrite_alt_tag']['hidden'] =
				$hidden = 
					!($values['feature_image/zenario_promo_menu__feature_image_checkbox'] && $imageId);
				
				$this->showHideImageOptions($fields, $values, 'feature_image', $hidden, 'zenario_promo_menu__');
				
				$fields['feature_image/zenario_promo_menu__rollover_image']['hidden'] =
					!empty($fields['feature_image/zenario_promo_menu__use_rollover']['hidden']) || 
					!$values['feature_image/zenario_promo_menu__use_rollover'];
				
				$fields['feature_image/zenario_promo_menu__hyperlink_target']['hidden'] =
				$fields['feature_image/zenario_promo_menu__hide_private_item']['hidden'] =
					!empty($fields['feature_image/zenario_promo_menu__link_type']['hidden']) ||
					!($values['feature_image/zenario_promo_menu__link_type'] == 'content_item');
				
				$fields['feature_image/zenario_promo_menu__url']['hidden'] =
					!empty($fields['feature_image/zenario_promo_menu__link_type']['hidden']) ||
					!($values['feature_image/zenario_promo_menu__link_type'] == 'external_url');
				
				$fields['feature_image/zenario_promo_menu__target_blank']['hidden'] =
					!empty($fields['feature_image/zenario_promo_menu__link_type']['hidden']) ||
					!($values['feature_image/zenario_promo_menu__link_type'] == 'external_url' ||
					$values['feature_image/zenario_promo_menu__link_type'] == 'content_item');
					
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch($path) {
			case 'zenario_menu':
				//new features
				$id = $box['key']['id'];

				
				if ($imageId = $values['feature_image/image_id']) {
					if ($path = ze\file::getPathOfUploadInCacheDir($imageId)) {
						$imageId = ze\file::addToDatabase('image', $path);
					}
				}
				
				if(!$values['feature_image/show_image']){
					$imageId=0;
				} 
				
				
				if ($imageId) {
					ze\row::set('inline_images', ['image_id' => $imageId, 'in_use' => 1], ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $id, 'foreign_key_char' => 'image']);
				} else {
					ze\row::delete('inline_images', ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $id, 'foreign_key_char' => 'image']);
				}
				$submission['image_id'] = $imageId;
	
				if ($rolloverImageId = $values['feature_image/rollover_image_id']) {
					if ($path = ze\file::getPathOfUploadInCacheDir($rolloverImageId)) {
						$rolloverImageId = ze\file::addToDatabase('image', $path);
			
					}
					$submission['rollover_image_id'] = $rolloverImageId;
				} else {
					$submission['rollover_image_id'] = 0;
				}
				if ($values['use_rollover_image'] && $rolloverImageId) {
					ze\row::set('inline_images', ['image_id' => $rolloverImageId, 'in_use' => 1], ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $id, 'foreign_key_char' => 'rollover_image']);
				} else {
					ze\row::delete('inline_images', ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $id, 'foreign_key_char' => 'rollover_image']);
				}
				ze\menuAdm::save($submission, $id);
				
				
				//end new features
				
				
				
				
				$row = [];
				$nodeId = $box['key']['id'];
				
				$featureImage = [
					'use_feature_image' => 0,
					'image_id' => 0,
					'canvas' => 'unlimited',
					'width' => 0,
					'height' => 0,
					'offset' => 0,
					'use_rollover_image' => 0,
					'rollover_image_id' => 0,
					'title' => '',
					'text' => '',
					'link_type' => 'no_link',
					'link_visibility' => 'always_show',
					'dest_url' => '',
					'open_in_new_window' => 0
				];
				
				if ($values['feature_image/zenario_promo_menu__feature_image_checkbox'] ) {
					
					$featureImage['use_feature_image'] = 1;
					$featureImage['image_id'] = $values['feature_image/zenario_promo_menu__feature_image'];
					if ($location = ze\file::getPathOfUploadInCacheDir($values['feature_image/zenario_promo_menu__feature_image'])) {
						$featureImage['image_id'] = ze\file::addToDatabase('image', $location);
					}
					
					$featureImage['canvas'] = $values['feature_image/zenario_promo_menu__canvas'];
					$featureImage['width'] = ($fields['feature_image/zenario_promo_menu__width']['hidden']) ? 0 : $values['feature_image/zenario_promo_menu__width'];
					$featureImage['height'] = ($fields['feature_image/zenario_promo_menu__height']['hidden']) ? 0 : $values['feature_image/zenario_promo_menu__height'];
					$featureImage['offset'] = ($fields['feature_image/zenario_promo_menu__offset']['hidden']) ? 0 : $values['feature_image/zenario_promo_menu__offset'];
					
					if ($values['feature_image/zenario_promo_menu__use_rollover']) {
						$featureImage['use_rollover_image'] = 1;
						$featureImage['rollover_image_id'] = $values['feature_image/zenario_promo_menu__rollover_image'];
						if ($location = ze\file::getPathOfUploadInCacheDir($values['feature_image/zenario_promo_menu__rollover_image'])) {
							$featureImage['rollover_image_id'] = ze\file::addToDatabase('image', $location);
						}
					}
					
					
					$featureImage['title'] = $values['feature_image/zenario_promo_menu__title'];
					$featureImage['text'] = $values['feature_image/zenario_promo_menu__text'];
					$featureImage['link_type'] = $values['feature_image/zenario_promo_menu__link_type'];
					switch($featureImage['link_type']) {
						case 'content_item':
							$featureImage['dest_url'] = $values['feature_image/zenario_promo_menu__hyperlink_target'];
							break;
						case 'external_url':
							$featureImage['dest_url'] = $values['feature_image/zenario_promo_menu__url'];
							break;
					}
					$featureImage['link_visibility'] = $values['feature_image/zenario_promo_menu__hide_private_item'];
					$featureImage['open_in_new_window'] = $values['feature_image/zenario_promo_menu__target_blank'];
				}
				
				if ($featureImage['image_id']) {
					ze\row::set('inline_images', ['image_id' => $featureImage['image_id'], 'in_use' => 1], ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $nodeId, 'foreign_key_char' => 'feature_image']);
				} else {
					ze\row::delete('inline_images', ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $nodeId, 'foreign_key_char' => 'feature_image']);
				}
				
				if ($featureImage['rollover_image_id']) {
					ze\row::set('inline_images', ['image_id' => $featureImage['rollover_image_id'], 'in_use' => 1], ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $nodeId, 'foreign_key_char' => 'rollover_feature_image']);
				} else {
					ze\row::delete('inline_images', ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $nodeId, 'foreign_key_char' => 'rollover_feature_image']);
				}
				

				$featureImage['overwrite_alt_tag'] = $values['feature_image/zenario_promo_menu__overwrite_alt_tag'];

				ze\row::set(ZENARIO_PROMO_MENU_PREFIX. 'menu_node_feature_image', $featureImage, ['node_id' => $nodeId]);
				break;
		}
	}
	
	public static function eventMenuNodeDeleted($menuId) {
		ze\row::delete('inline_images', ['foreign_key_to' => 'menu_node', 'foreign_key_id' => $menuId]);
		ze\row::delete(ZENARIO_PROMO_MENU_PREFIX. 'menu_node_feature_image', ['node_id' => $menuId]);
	}
}