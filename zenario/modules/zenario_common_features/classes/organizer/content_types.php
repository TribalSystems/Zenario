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


class zenario_common_features__organizer__content_types extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__content/panels/content_types') return;
		
		foreach ($panel['items'] as $id => &$item) {
			
			//Apply some formatting from ze\contentAdm::cTypeDetails()
			$item = ze\contentAdm::cTypeDetails($item);
			
			$item['css_class'] = 'content_type_'. $item['content_type_id'];
			
			if ($item['not_enabled']) {
				$item['not_enabled'] = ' '. ze\admin::phrase('(not enabled)');
			} else {
				$item['not_enabled'] = '';
			}
			
			//Hide the folders that click through to content items
			if ($mode != 'select') {
				$item['link'] = false;
			}
			
			//Show a description of the settings
			if (ze::in($mode, 'full', 'quick')) {
				$item['defaults'] = ze\admin::phrase('Version-controlled content items');
				
				$with = [];
				if ($item['description_field'] != 'hidden') {
					$with[] = ze\admin::phrase('meta description');
				}
				if ($item['keywords_field'] != 'hidden') {
					$with[] = ze\admin::phrase('keywords');
				}
				if ($item['release_date_field'] != 'hidden') {
					$with[] = ze\admin::phrase('release date');
				}
				if ($item['writer_field'] != 'hidden') {
					$with[] = ze\admin::phrase('writer field');
				}
				if ($item['summary_field'] != 'hidden') {
					$with[] = ze\admin::phrase('content summary field');
				}
				
				if (!empty($with)) {
					$item['defaults'] .= ze\admin::phrase(', with [[with]]', ['with' => implode(', ', $with)]);
				}
				
				if ($item['content_type_id'] != 'html') {
					$menuNodes = array_values(ze\row::getValues('menu_nodes', 'id', ['restrict_child_content_types' => $item['content_type_id']]));
					$count = count($menuNodes);
				
					if ($item['menu_node_position_edit'] == 'force') {
						if ($count == 0) {
							$item['defaults'] .= ze\admin::phrase('. New items restricted but no menu item nominated');
					
						} else {
							$mrg = ['menu_path' => ze\menuAdm::pathWithSection($menuNodes[0])];
							$item['defaults'] .= ze\admin::phrase('. New items restricted to under [[menu_path]]', $mrg);
						
							--$count;
							$item['defaults'] .= ze\admin::nPhrase(' and 1 other place', ', and [[count]] other places', $count, [], '');
						}
					} else {
						if ($count > 0) {
							$mrg = ['menu_path' => ze\menuAdm::pathWithSection($menuNodes[0])];
							$item['defaults'] .= ze\admin::phrase('. New items attached to menu under [[menu_path]]', $mrg);
						
							--$count;
							$item['defaults'] .= ze\admin::nPhrase(' and 1 other place', ', and [[count]] other places', $count, [], '');
						}
					}
				}
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}