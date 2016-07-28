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


class zenario_common_features__organizer__menu_position extends module_base_class {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		/*
		if ($refinerName == 'only_existing') {
			$panel['db_items']['where_statement'] = 'WHERE mp.is_dummy_child = 0';
		}*/
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__menu/panels/menu_position') return;
		
		$seperator = ' -> ';
		
		foreach ($panel['items'] as $id => &$item) {
			
			if ($item['is_dummy_child']) {
				$parentTagParts = explode('_', $item['parent_id']);
				$parentId = $parentTagParts[1];
				
				$item['css_class'] = 'zenario_menunode_unlinked ghost';
				$item['name'] = adminPhrase('[ Put menu node here ]');
				$item['target'] =
				$item['target_loc'] =
				$item['internal_target'] =
				$item['redundancy'] = '';
				
				
				if ($parentId) {
					$item['menu_path'] =
						getMenuPath($parentId, false, $seperator) . $seperator.
						adminPhrase('[ new child node ]');
				} else {
					$item['menu_path'] =
						adminPhrase('[ new top-level node ]');
				}
				//$item['menu_path'] .= $item['name'];
				
			} elseif ($item['menu_id']) {
				$parentTagParts = explode('_', $item['parent_id']);
				$parentId = $parentTagParts[1];
				
				if ($item['target_loc'] == 'int' && $item['internal_target']) {
					
					if (isMenuNodeUnique($item['redundancy'], $item['equiv_id'], $item['content_type'])) {
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
				
				//$item['menu_path'] = getMenuPath($item['menu_id'], false, $seperator);
				if ($parentId) {
					$item['menu_path'] =
						getMenuPath($parentId, false, $seperator) . $seperator.
						adminPhrase('[ new node before "[[name]]" ]', $item);
				} else {
					$item['menu_path'] =
						adminPhrase('[ new top-level node before "[[name]]" ]', $item);
				}
				
			} else {
				$item['css_class'] = 'menu_section';
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}