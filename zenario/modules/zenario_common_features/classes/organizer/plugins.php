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


class zenario_common_features__organizer__plugins extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__modules/panels/plugins') return;
		
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__modules/panels/plugins') return;
		
		$panel['key']['skinId'] = $_REQUEST['skinId'] ?? false;
		
		
		if (($_GET['refiner__plugin'] ?? false) && !isset($_GET['refiner__all_instances'])) {
			$panel['title'] =
			$panel['select_mode_title'] =
				ze\admin::phrase('"[[name]]" plugins in the library', ['name' => ze\module::displayName($_GET['refiner__plugin'] ?? false)]);
			$panel['no_items_message'] =
				ze\admin::phrase('There are no "[[name]]" plugins in the library. Click the "Create" button to create one.', ['name' => ze\module::displayName($_GET['refiner__plugin'] ?? false)]);
		}
		
		foreach ($panel['items'] as $id => &$item) {
		
			if ($item['checksum']) {
				$img = '&c='. $item['checksum'];
				$item['has_image'] = true;
				$item['image'] = 'zenario/file.php?og=1'. $img;
				$item['list_image'] = 'zenario/file.php?ogl=1'. $img;
			}
			
			//if (strpos($item['module_class_name'], 'nest') !== false
			// && ze\pluginAdm::conductorEnabled($id)) {
			//	$item['usesConductor'] = true;
			//}
			
			$item['usage_item'] = $item['usage_layouts'] = 0;
			$item['usage'] = zenario_common_features::getPluginInstanceUsage($id, $item['usage_item'], $item['usage_layouts']);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__modules/panels/plugins') return;
		
		if (($_POST['delete'] ?? false) && ze\priv::check('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				ze\pluginAdm::delete($id);
			}
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}