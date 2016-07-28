<?php
/*
 * Copyright (c) 2016, Tribal Limited
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
	private $num_in_col = 0;
	private $class = '';	
	//Your fillAdminBox() method is called by the CMS whenever an Admin opens one of
	//your Admin Boxes.
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				$fields['menu_start_from']['values'] = array('_MENU_LEVEL_1' => $fields['menu_start_from']['values']['_MENU_LEVEL_1']);
				
				$fields['menu_number_of_levels']['values'] = array(1 => 1, 2 => 2, 3 => 3);
				
				break;
				
			case 'zenario_menu':
				if ($box['key']['parentMenuID'] && !getMenuParent($box['key']['parentMenuID'])) {
					$fields['zenario_menu_multicolumn__top_of_column']['hidden'] = false;
					
					if ($box['key']['id']) {
					$values['zenario_menu_multicolumn__top_of_column'] =
						getRow(
							ZENARIO_MENU_MULTICOLUMN_PREFIX. 'nodes_top_of_column',
							'top_of_column',
							$box['key']['id']);
					}								
				}				
				
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//You should always use an if () or a switch () statement on the path,
		//to ensure that you are running code for the correct Admin Box.
		switch ($path) {
			case 'zenario_menu':
				if (!empty($box['tabs']['advanced']['edit_mode']['on'])) {
					
					if ($box['key']['id']) {
						
						$details = array();
						if (!$details['top_of_column'] = $values['zenario_menu_multicolumn__top_of_column']) {
							$details['top_of_column'] = 0;
						}
						
						setRow(
							ZENARIO_MENU_MULTICOLUMN_PREFIX. 'nodes_top_of_column',
							$details,
							$box['key']['id']);
					}
				}
				
				break;
		}
	}	
	
	function drawMenu(&$menuArray, $recurseCount = 0, $headerObjects = array(), $subSections = array()) {
		$maxincol = $this->setting('max_items_per_column');
		
		
		
		//Loop through level 1s
		if (is_array($menuArray)) {
			foreach ($menuArray as $val) {
				echo '
<ul>
	<li>'. $this->drawMenuItemWithLevel($val, 1);
				if (isset($val['children']) && is_array($val['children']) && $val['children']) {
					echo '<div class="columns_wrap"><div class="column">';
					$currentInCol = 0;
					
					//Loop through level 2s
					foreach ($val['children'] as $val2) {
						++$currentInCol;
						
						$children = 0;
						
						if (isset($val2['children']) && is_array($val2['children'])) {
							$children = count($val2['children']);
						}
											
						if ($currentInCol >= $maxincol
						 || (!empty($val2['mID']) && getRow(ZENARIO_MENU_MULTICOLUMN_PREFIX. 'nodes_top_of_column','top_of_column', $val2['mID']))) {
							
							for (; $currentInCol <= $maxincol; ++$currentInCol) {
								echo '
									<div class="node node-level-3"></div>';
							}
							$currentInCol = 1;
							echo '
								</div><div class="column">
								<div class="node node-level-2">'.
									$this->drawMenuItemWithLevel($val2, 2).
								'</div>';
						} else {
							echo '
								<div class="node node-level-2">'. 
									$this->drawMenuItemWithLevel($val2, 2).
								'</div>';
						}
						
						//Loop through level 3s
						if ($children) {
							foreach ($val2['children'] as $val3) {
								++$currentInCol;
								
								//redundant?
								if ($currentInCol > $maxincol) {
									$currentInCol = 1;
									echo '
										</div><div class="column"> 
										<div class="node node-level-3 first-node">'.
											$this->drawMenuItemWithLevel($val3, 3).
										'</div>';
								
								} else {
									echo '
										<div class="node node-level-3">'. 
											$this->drawMenuItemWithLevel($val3, 3).
										'</div>';
								}
							
							}
						}
					}
					
					echo '
			</div>			 
			
		</div>';
				}
				echo '
	</li>
</ul>';
			}
		}

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
	
	
	//Main Display function for the slot
	function showSlot() {
		
		//my code here
		
		$rv = zenario_menu::showSlot();
		
		//more code goes here
		
		return $rv;
		
	}
}