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


class zenario_common_features__organizer__menu_position extends ze\moduleBaseClass {
	
	protected $seperator = ' › ';
	protected $pathsByParents = [];
	
	protected function getParentPath($parentId, $item) {
		
		if (!isset($pathsByParents[$parentId])) {
			if ($parentId) {
				$pathsByParents[$parentId] = ze\menuAdm::pathWithSection($parentId, false, $this->seperator) . $this->seperator;
			} else {
				$pathsByParents[$parentId] = ze\menu::sectionName($item['section_id']). $this->seperator;
			}
		}
		
		return $pathsByParents[$parentId];
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__menu/panels/menu_position') return;
		
		//We'll get the menu nodes in no particular order (the sorting happens on the client).
		//But I need a flag for which menu node at each place in the tree has the smallest ordinal,
		//so we'll need to calculate this
		$firstMenuNodes = [];
		foreach ($panel['items'] as $id => &$item) {
			if (!$item['is_dummy_child']) {
				$ordinal = (int) $item['ordinal'];
				
				if ($ordinal) {
					if (!isset($firstMenuNodes[$item['parent_id']])
					 || ($ordinal < $firstMenuNodes[$item['parent_id']])) {
						$firstMenuNodes[$item['parent_id']] = $ordinal;
					}
				}
			}
		}	
		
		foreach ($panel['items'] as $id => &$item) {
			$parentTagParts = explode('_', $item['parent_id']);
			$parentId = (int) ($parentTagParts[1] ?? 0);
			$ordinal = (int) $item['ordinal'];
			
			if ($item['is_dummy_child']) {
				
				$item['parent_menu_id'] = $parentId;
				$item['css_class'] = 'zenario_menunode_unlinked ghost';
				$item['target'] =
				$item['target_loc'] =
				$item['internal_target'] =
				$item['redundancy'] = '';
				
				
				switch ($refinerName) {
					case 'create':
						$item['menu_path'] =
							$this->getParentPath($parentId, $item).
							($item['name'] = ze\admin::phrase('[ Create here ]'));
						break;
					
					case 'move':
						$item['menu_path'] =
							$this->getParentPath($parentId, $item).
							($item['name'] = ze\admin::phrase('[ Move to the end ]'));
						break;
				}
				
			} elseif ($item['menu_id']) {
				
				$item['parent_menu_id'] = $parentId;
				
				if ($item['target_loc'] == 'int' && $item['internal_target']) {
					
					if (ze\menu::isUnique($item['redundancy'], $item['equiv_id'], $item['content_type'])) {
						$item['redundancy'] = 'unique';
					}
					
					if ($item['redundancy'] == 'unique') {
						$item['css_class'] = 'zenario_menunode_internal_unique';
					} elseif ($item['redundancy'] == 'primary') {
						$item['css_class'] = 'zenario_menunode_internal';
					} else {
						$item['css_class'] = 'zenario_menunode_internal_secondary';
					}

				} elseif ($item['target_loc'] == 'ext' && $item['target']) {
					$item['css_class'] = 'zenario_menunode_external';

				} else {
					$item['css_class'] = 'zenario_menunode_unlinked';
				}

				if (empty($item['parent_id'])) {
					$item['css_class'] .= ' zenario_menunode_toplevel';
				}
				
				if ($isFirst = $firstMenuNodes[$item['parent_id']] == $ordinal) {
					$item['is_first'] = true;
				}
				
				
				switch ($refinerName) {
					case 'create':
						$item['menu_path'] =
							$this->getParentPath($parentId, $item).
							($item['name'] = ze\admin::phrase('[ Create before "[[name]]" ]', $item));
						break;
					
					case 'move':
						$item['menu_path'] =
							$this->getParentPath($parentId, $item).
							($item['name'] = ze\admin::phrase('[ Move before "[[name]]" ]', $item));
						break;
					
					case 'existing':
						$item['menu_path'] = ze\menuAdm::pathWithSection($item['menu_id'], false, $this->seperator);
						break;
				}
				 
				
			} else {
				$item['css_class'] = 'menu_section';
				$item['menu_path'] = $item['name'];
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}