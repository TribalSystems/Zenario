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


class zenario_menu_multicolumn extends zenario_menu {	
	
	//Recursive function to draw Menu Nodes from the database
	function getMenuMergeFields(&$menuArray, $depth = 0) {
		$menuMergeFields = [];
		$maxInCol = $this->setting('max_items_per_column');
		
		//Loop through level 1s
		if (is_array($menuArray)) {
			foreach ($menuArray as $val) {
				$node = [];
				$node['item'] = $this->drawMenuItemWithLevel($val, 1);
				$node['max_items_per_column'] = $maxInCol;
				
				if (!empty($val['children']) && is_array($val['children'])) {
					$node['has_children'] = true;
					$node['children'] = [];
					
					$currentInCol = 0;
					//Loop through level 2s
					foreach ($val['children'] as $val2) {
						$level2node = [];
						$level2node['item'] = $this->drawMenuItemWithLevel($val2, 2);
						++$currentInCol;
						$children = 0;
						
						if (isset($val2['children']) && is_array($val2['children'])) {
							$children = count($val2['children']);
							$level2node['children'] = [];
						}
						
						if (($currentInCol > $maxInCol)
						 || (!empty($val2['mID']) && ze\row::get(ZENARIO_MENU_MULTICOLUMN_PREFIX. 'nodes_top_of_column','top_of_column', $val2['mID']))) {
							$level2node['top_of_column'] = true;
							if ($currentInCol <= $maxInCol) {
								$level2node['current_in_col'] = $currentInCol;
							}
							$currentInCol = 1;
						}
						
						if ($children) {
							//Loop through level 3s
							foreach ($val2['children'] as $val3) {
								$level3node = [];
								$level3node['item'] = $this->drawMenuItemWithLevel($val3, 3);
								++$currentInCol;
								
								if ($currentInCol > $maxInCol) {
									$currentInCol = 1;
									$level3node['top_of_column'] = true;
								}
							}
							$level2node['children'][] = $level3node;
						}
						$node['children'][] = $level2node;
					}
				}
				$menuMergeFields[] = $node;
			}
		}
		
		return $menuMergeFields;
	}
	
	function drawMenuItemWithLevel(&$row, $l) {
		$menuItem = '<a';
		$class = '';
		
		if (!empty($row['on'])) {
			$class .= ' level' . $l .'_on';
		}
		
		if (!empty($row['children'])) {
			$class .= ' has_child';
		}
		
		if (!empty($row['css_class'])) {
			$class .= ' '. $row['css_class'];
		}
		
		if($class) {
			$menuItem .= ' class="'. $class .'"';
		}
		
		if (!empty($row['url'])) {
			$menuItem .= ' href="'. htmlspecialchars($row['url']). '"';
		} else {
			$menuItem .= ' class="unlinked_menu_item"';
		}
		if (!empty($row['onclick'])) {
			$menuItem .= ' onclick="'. htmlspecialchars($row['onclick']). '"';
		}
		if (!empty($row['accesskey'])) {
			$menuItem .= ' accesskey="'. htmlspecialchars($row['accesskey']). '"';
		}
		if (!empty($row['rel_tag'])) {
			$menuItem .= ' rel="'. htmlspecialchars($row['rel_tag']). '"';
		}
		if (!empty($row['target'])) {
			$menuItem .= ' target="'. $row['target']. '"';
		}
		if (!empty($row['title'])) {
			$menuItem .= ' title="'. $row['title']. '"';
		}
		
		$menuItem .= '>';
		
		if (!empty($row['name'])) {
			$menuItem .= htmlspecialchars($row['name']);
		
		} elseif (!empty($row['img'])) {
			$menuItem .= $row['img'];
		}
		
		$menuItem .= '</a>';
		
		if (!isset($row['active']) || !$row['active']) {
			$menuItem = '<em class="zenario_inactive">'. $menuItem. '</em>';
		}
		
		if (!empty($row['descriptive_text'])) {
			$menuItem .= $row['descriptive_text'];
		}
		
		return $menuItem;
	}
	
	
	
	
	public function getNodeLevel($nodeId,$i=1){
		if($parentId = ze\row::get('menu_nodes', 'parent_id', array('id'=>$nodeId))){
			self::getNodeLevel($parentId,++$i);
		}
		return $i;
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				$fields['menu_start_from']['values'] = array('_MENU_LEVEL_1' => $fields['menu_start_from']['values']['_MENU_LEVEL_1']);
				$fields['menu_number_of_levels']['values'] = array(1 => 1, 2 => 2, 3 => 3);
				break;
			
			case 'zenario_menu':
				if ($box['key']['parentMenuID'] && !ze\menu::parentId($box['key']['parentMenuID'])) {
					if ($box['key']['id']) {
						$nodeLevel = self::getNodeLevel($box['key']['id']);
						if($nodeLevel == 2){
							$fields['zenario_menu_multicolumn__top_of_column']['hidden'] = false;
							$values['zenario_menu_multicolumn__top_of_column'] =
							ze\row::get(
								ZENARIO_MENU_MULTICOLUMN_PREFIX. 'nodes_top_of_column',
								'top_of_column',
								$box['key']['id']);
						}else{
							$fields['zenario_menu_multicolumn__top_of_column']['hidden'] = true;
							$values['zenario_menu_multicolumn__top_of_column'] = 0;
						}
					}
				}
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_menu':
				if ($box['key']['id']) {
					if(isset($values['zenario_pro_features__invisible']) && $values['zenario_pro_features__invisible']){
							$values['zenario_menu_multicolumn__top_of_column'] = 0;
							$fields['zenario_menu_multicolumn__top_of_column']['hidden'] = true;
					}else if ($box['key']['parentMenuID'] && !ze\menu::parentId($box['key']['parentMenuID'])) {
						$nodeLevel = self::getNodeLevel($box['key']['id']);
					
						if($nodeLevel == 2){
							$fields['zenario_menu_multicolumn__top_of_column']['hidden'] = false;
						}else{
							$fields['zenario_menu_multicolumn__top_of_column']['hidden'] = true;
							$values['zenario_menu_multicolumn__top_of_column'] = 0;
						}
					}	
				}
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_menu':
				if (!empty($box['tabs']['advanced']['edit_mode']['on'])) {
					if ($box['key']['id']) {
						$details = array();
						if (!$details['top_of_column'] = $values['zenario_menu_multicolumn__top_of_column']) {
							$details['top_of_column'] = 0;
						}
						ze\row::set(
							ZENARIO_MENU_MULTICOLUMN_PREFIX. 'nodes_top_of_column',
							$details,
							$box['key']['id']);
					}
				}
				break;
		}
	}
}