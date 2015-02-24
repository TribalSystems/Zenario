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

class zenario_promo_menu extends zenario_menu_multicolumn {
	
	var $menuArray = false;
	
	function drawMenu(&$menuArray, $recurseCount = 0, $headerObjects = array(), $subSections = array()) {
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
		$this->sectionId = menuSectionId($this->sectionId);
		
		// Get the Menu Node for this content item
		$this->currentMenuId = getSectionMenuItemFromContent(cms_core::$equivId, cms_core::$cType, $this->sectionId);
		$this->numLevels = 3;
		$this->maxLevel1MenuItems = 999;
		$this->language = false;
		$this->onlyFollowOnLinks = !$this->setting('menu_show_all_branches');
		$this->onlyIncludeOnLinks = false;
		$this->showAdminAddMenuItem = $this->setting('menu_show_admin_add_button');
		$this->showInvisibleMenuItems = false;
		$this->showMissingMenuNodes = $this->setting('show_missing_menu_nodes');
		$cachingRestrictions = false;
		
		$this->menuArray['nodes'] =
			getMenuStructure($cachingRestrictions,
							 $this->sectionId, $this->currentMenuId, 0,
							 $this->numLevels, $this->maxLevel1MenuItems, $this->language,
							 $this->onlyFollowOnLinks, $this->onlyIncludeOnLinks, 
							 $this->showAdminAddMenuItem, $this->showInvisibleMenuItems,
							 $this->showMissingMenuNodes);
							 
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
				t.top_of_column
			FROM '.DB_NAME_PREFIX.'menu_nodes n
			LEFT JOIN '.DB_NAME_PREFIX. ZENARIO_PROMO_MENU_PREFIX. 'menu_node_feature_image m
				ON n.id = m.node_id
			LEFT JOIN '.DB_NAME_PREFIX. ZENARIO_MENU_MULTICOLUMN_PREFIX. 'nodes_top_of_column t
				ON n.id = t.node_id
			WHERE section_id = '.(int)$this->sectionId;
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)) {
			// Set image links
			$url = $width = $height = false;
			imageLink($width, $height, $url, $row['feature_image_id'], $row['width'], $row['height'], $row['canvas'], $row['offset']);
			$row['feature_image_link'] = $url;
			
			$url = $width = $height = false;
			imageLink($width, $height, $url, $row['image_id']);
			$row['image_link'] = $url;
			
			if ($row['use_rollover_image'] && $row['feature_rollover_image_id']) {
				$url = $width = $height = false;
				imageLink($width, $height, $url, $row['feature_rollover_image_id'], $row['width'], $row['height'], $row['canvas'], $row['offset']);
				$row['feature_rollover_image_link'] = $url;
			}
			
			if ($row['rollover_image_id']) {
				$url = $width = $height = false;
				imageLink($width, $height, $url, $row['rollover_image_id']);
				$row['rollover_image_link'] = $url;
			}
			
			// Set link if to content item
			$cID = $cType = false;
			if (($row['link_type'] == 'content_item') && getCIDAndCTypeFromTagId($cID, $cType, $row['dest_url'])) {
				langEquivalentItem($cID, $cType);
				$row['dest_url'] = linkToItem($cID, $cType);
			}
			
			$row['show_banner'] = false;
			switch($row['link_visibility']) {
				case 'private':
					if (checkPerm($cID, $cType)) {
						$row['show_banner'] = true;
					}
					break;
				case 'logged_out':
					if (!session('extranetUserID')) {
						$row['show_banner'] = true;
					}
					break;
				case 'logged_in':
					if (session('extranetUserID')) {
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
		switch($path) {
			case 'zenario_menu':
			$nodeId = $box['key']['id'];
			$row = getRow(ZENARIO_PROMO_MENU_PREFIX. 'menu_node_feature_image',
				array('use_feature_image', 'image_id', 'canvas', 'width', 'height', 'offset', 'use_rollover_image', 'rollover_image_id', 'title', 'text', 'link_type', 'link_visibility', 'dest_url', 'open_in_new_window'),
				array('node_id' => $nodeId));
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
			$this->getImageHtmlSnippet($row['image_id'], $fields['zenario_promo_menu__feature_image_thumbnail']['snippet']['html']);
			$this->getImageHtmlSnippet($row['rollover_image_id'], $fields['zenario_promo_menu__rollover_image_thumbnail']['snippet']['html']);
			break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch($path) {
			case 'zenario_menu':
				$fields['feature_image/zenario_promo_menu__feature_image']['hidden'] =
				$fields['feature_image/zenario_promo_menu__feature_image_thumbnail']['hidden'] =
				$fields['feature_image/zenario_promo_menu__canvas']['hidden'] =
				$fields['feature_image/zenario_promo_menu__use_rollover']['hidden'] =
				$fields['feature_image/zenario_promo_menu__title']['hidden'] =
				$fields['feature_image/zenario_promo_menu__text']['hidden'] =
				$fields['feature_image/zenario_promo_menu__link_type']['hidden'] =
					!$values['feature_image/zenario_promo_menu__feature_image_checkbox'];
				
				$fields['feature_image/zenario_promo_menu__width']['hidden'] =
					!empty($fields['feature_image/zenario_promo_menu__canvas']['hidden']) ||
					!in($values['feature_image/zenario_promo_menu__canvas'], 'fixed_width', 'fixed_width_and_height', 'resize_and_crop');
				
				$fields['feature_image/zenario_promo_menu__height']['hidden'] =
					!empty($fields['feature_image/zenario_promo_menu__canvas']['hidden']) ||
					!in($values['feature_image/zenario_promo_menu__canvas'], 'fixed_height', 'fixed_width_and_height', 'resize_and_crop');
				
				$fields['feature_image/zenario_promo_menu__offset']['hidden'] =
					!empty($fields['feature_image/zenario_promo_menu__canvas']['hidden']) ||
					!($values['feature_image/zenario_promo_menu__canvas'] == 'resize_and_crop');
				
				$fields['feature_image/zenario_promo_menu__rollover_image_thumbnail']['hidden'] =
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
					
				
				$this->getImageHtmlSnippet($values['feature_image/zenario_promo_menu__feature_image'], $fields['zenario_promo_menu__feature_image_thumbnail']['snippet']['html']);
				$this->getImageHtmlSnippet($values['feature_image/zenario_promo_menu__rollover_image'], $fields['zenario_promo_menu__rollover_image_thumbnail']['snippet']['html']);
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch($path) {
			case 'zenario_menu':
				$nodeId = $box['key']['id'];
				$row = array();
				$row['node_id'] = $nodeId;
				$row['use_feature_image'] = $values['feature_image/zenario_promo_menu__feature_image_checkbox'];
				$row['image_id'] = $values['feature_image/zenario_promo_menu__feature_image'];
				$row['canvas'] = $values['feature_image/zenario_promo_menu__canvas'];
				$row['width'] = ($fields['feature_image/zenario_promo_menu__width']['hidden']) ? 0 : $values['feature_image/zenario_promo_menu__width'];
				$row['height'] = ($fields['feature_image/zenario_promo_menu__height']['hidden']) ? 0 : $values['feature_image/zenario_promo_menu__height'];
				$row['offset'] = ($fields['feature_image/zenario_promo_menu__offset']['hidden']) ? 0 : $values['feature_image/zenario_promo_menu__offset'];
				$row['use_rollover_image'] = $values['feature_image/zenario_promo_menu__use_rollover'];
				$row['rollover_image_id'] = $values['feature_image/zenario_promo_menu__rollover_image'];
				$row['title'] = $values['feature_image/zenario_promo_menu__title'];
				$row['text'] = $values['feature_image/zenario_promo_menu__text'];
				$row['link_type'] = $values['feature_image/zenario_promo_menu__link_type'];
				switch($row['link_type']) {
					case 'content_item':
						$row['dest_url'] = $values['feature_image/zenario_promo_menu__hyperlink_target'];
						break;
					case 'external_url':
						$row['dest_url'] = $values['feature_image/zenario_promo_menu__url'];
						break;
				}
				$row['link_visibility'] = $values['feature_image/zenario_promo_menu__hide_private_item'];
				$row['open_in_new_window'] = $values['feature_image/zenario_promo_menu__target_blank'];
				
				setRow(ZENARIO_PROMO_MENU_PREFIX. 'menu_node_feature_image', $row, array('node_id' => $row['node_id']));
				break;
		}
	}
}