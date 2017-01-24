<?php
/*
 * Copyright (c) 2017, Tribal Limited
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


switch ($path) {
	case 'zenario__modules/panels/plugins':
		$nestablemoduleIds = getNestablemoduleIds();
		
		foreach ($panel['items'] as $id => &$item) {
			if (!empty($nestablemoduleIds[$item['module_id']])) {
				$item['traits']['zenario_plugin_nest__nest'] = true;
			}
		}
	
		break;
	
	
	case 'zenario__modules/hidden_nav/zenario_plugin_nest/panel':
		
		$instance = getPluginInstanceDetails(get('refiner__nest'));
		
		if (isset($panel[$instance['class_name']. '__title_'. ($instance['content_id']? 'wf' : 'rp')])) {
			$panel['title'] = $panel[$instance['class_name']. '__title_'. ($instance['content_id']? 'wf' : 'rp')];
		}
		
		$panel['title'] = adminPhrase($panel['title'], array('nest' => htmlspecialchars($instance['instance_name'])));
		
		if (isset($panel['collection_buttons']['nest_settings'])) {
			$panel['collection_buttons']['nest_settings']['admin_box']['key']['instanceId'] = get('refiner__nest');
		}
		
		foreach ($panel['items'] as $id => &$item) {
			$item['traits'] = array();
			if ($item['is_tab']) {
				$item['traits']['is_tab'] = true;
				$item['css_class'] = 'zenario_nest_tab';
			} else {
				$item['traits']['is_not_tab'] = true;
				
				if ($item['checksum']) {
					$img = '&c='. $item['checksum'];
					$item['traits']['has_image'] = true;
					$item['image'] = 'zenario/file.php?og=1'. $img;
					$item['list_image'] = 'zenario/file.php?ogl=1'. $img;
				} else {
					$item['image'] = getModuleIconURL($item['module_class_name']);
				}
			}
		}
	
		break;
}


?>