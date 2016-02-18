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

switch ($path) {
	case 'plugin_settings':
	case 'plugin_css_and_framework':
		return require funIncPath(__FILE__, 'plugin_settings.formatAdminBox');
	
	
	case 'zenario_content':
		require funIncPath(__FILE__, 'content.formatAdminBox');

	case 'zenario_quick_create':
		//Note that this code should run for both zenario_content and zenario_quick_create
		$box['tabs']['meta_data']['fields']['menu_title']['hidden'] =
		$box['tabs']['meta_data']['fields']['menu_path']['hidden'] =
		$box['tabs']['meta_data']['fields']['menu_parent_path']['hidden'] =
			!empty($box['tabs']['meta_data']['fields']['create_menu']['hidden']) || !$values['meta_data/create_menu'];
		
		break;
	
	
	case 'zenario_document_move':
		$fields['details/move_to']['hidden'] = $values['details/move_to_root'];
		break;
	
	case 'zenario_admin':
		return require funIncPath(__FILE__, 'admin.formatAdminBox');
	
	case 'zenario_document_properties':
		if (!empty($fields['upload_image/delete_thumbnail_image']['pressed'])) {
			$fields['upload_image/thumbnail_image']['snippet']['html'] = adminPhrase('No thumbnail available');
			$box['key']['delete_thumbnail'] = true;
		}
		break;

}

return false;