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

class zenario_common_features__organizer__custom_datasets extends ze\moduleBaseClass {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		$panel['items'] = ze\row::getAssocs('custom_datasets', true, []);
			
		foreach ($panel['items'] as $id => &$item) {
			if ($item['extends_admin_box']) {
				if (!ze\row::exists('tuix_file_contents', ['type' => 'admin_boxes', 'path' => $item['extends_admin_box']])) {
					$item['link'] = false;
					$item['type'] = ze\admin::phrase('Extends system dataset [[extends_admin_box]] (module not enabled!)', $item);
				} else {
					$item['type'] = ze\admin::phrase('Extends system dataset [[extends_admin_box]]', $item);
				}
			} else {
				$item['type'] = ze\admin::phrase('Standalone dataset', $item);
			}
			
			if ($item['table']) {
				$item['table'] = DB_PREFIX. $item['table'];
			}
			if ($item['system_table']) {
				$item['system_table'] = DB_PREFIX. $item['system_table'];
			}
			
			// Temporarily disable the GUI button for assets dataset
			$item['gui_blacklist'] = false;
			if (ze\module::inc('assetwolf_asset_manager') && ($item['system_table'] == (DB_PREFIX . ASSETWOLF_ASSET_MANAGER_PREFIX . 'assets'))) {
				$item['gui_blacklist'] = true;
			}
		}
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
	}
	
}