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



switch ($path) {
	case 'plugin_settings':
		//For Wireframe Plugins, pick images from this item's images, rather thamn 
		if ($box['key']['isVersionControlled']/*
		 && isDraft($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'])*/) {
			$box['tabs']['first_tab']['fields']['image']['pick_items']['path'] =
			$box['tabs']['first_tab']['fields']['rollover_image']['pick_items']['path'] =
				'zenario__content/panels/content/item_buttons/images//'. $box['key']['cType']. '_'. $box['key']['cID']. '//';
			
			$box['tabs']['first_tab']['fields']['image']['pick_items']['min_path'] =
			$box['tabs']['first_tab']['fields']['image']['pick_items']['max_path'] =
			$box['tabs']['first_tab']['fields']['image']['pick_items']['target_path'] =
			$box['tabs']['first_tab']['fields']['rollover_image']['pick_items']['min_path'] =
			$box['tabs']['first_tab']['fields']['rollover_image']['pick_items']['max_path'] =
			$box['tabs']['first_tab']['fields']['rollover_image']['pick_items']['target_path'] =
				'zenario__content/panels/inline_images_for_content';
		}
		
		$box['first_display'] = true;
		
		if (!empty($box['tabs']['first_tab']['fields']['alt_tag']['value'])) {
			$box['tabs']['first_tab']['fields']['alt_tag']['multiple_edit']['changed'] = true;
		}
		
		if (!empty($box['tabs']['first_tab']['fields']['floating_box_title']['value'])) {
			$box['tabs']['first_tab']['fields']['floating_box_title']['multiple_edit']['changed'] = true;
		}
		
		
		//Banner Plugins should have a note that appears below their settings if they are in a nest,
		//explaining that they may be overwritten by the global settings.
		//However in Modules that extend the banner, these should not be visible.
		if ((!empty($box['key']['nest']))
		 && ($nestedPlugin = getNestDetails($box['key']['nest']))
		 && (getModuleClassName($nestedPlugin['module_id']) == 'zenario_banner')) {
			$box['tabs']['first_tab']['fields']['canvas']['note_below'] =
			$box['tabs']['first_tab']['fields']['enlarge_canvas']['note_below'] =
				adminPhrase('This setting may be overwritten by the settings of the Nest.');
		}
		
		break;
}