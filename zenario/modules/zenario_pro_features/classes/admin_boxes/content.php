<?php
/*
 * Copyright (c) 2020, Tribal Limited
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

				
class zenario_pro_features__admin_boxes__content extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if (isset($box['tabs']['meta_data']['fields']['menu_invisible'])) {
			if ($box['key']['source_cID']) {
				if ($menu = ze\menu::getFromContentItem($box['key']['source_cID'], $box['key']['cType'])) {
					$box['tabs']['meta_data']['fields']['menu_invisible']['value'] = $menu['invisible'];
				}
			
			} elseif (!$box['key']['cID']) {
				$contentType = ze\row::get('content_types', true, $box['key']['cType']);
				
				if (!empty($contentType['hide_menu_node'])) {
					$values['meta_data/menu_invisible'] = 1;
				}
			}
		}	
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (ze\ring::engToBoolean($box['tabs']['meta_data']['edit_mode']['on'] ?? false)
		&& isset($box['tabs']['meta_data']['fields']['menu_invisible'])
		&& $values['meta_data/create_menu_node']
		&& ($menu = ze\menu::getFromContentItem($box['key']['cID'], $box['key']['cType']))) {
			ze\row::update(
			'menu_nodes',
			['invisible' => ze\ring::engToBoolean($values['meta_data/menu_invisible'])],
			$menu['id']);
		}
	}
}
