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
	case 'zenario_plugin_nest_probusiness__tab':
		zenario_plugin_nest::fillAdminBox('zenario_plugin_nest__tab', $settingGroup, $box, $fields, $values);
		
		if (!empty($box['key']['id'])
		 && $details = getRow(
							ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX. 'tabs',
							true,
							array('tab_id' => $box['key']['id']))
		) {
			$box['tabs']['tab']['fields']['tab_visibility']['value'] = $details['visibility'];
			$box['tabs']['tab']['fields']['tab__smart_group']['value'] = $details['smart_group_id'];
			$box['tabs']['tab']['fields']['tab__field_id']['value'] = $details['field_id'];
			$box['tabs']['tab']['fields']['tab__field_value']['value'] = $details['field_value'];
			$box['tabs']['tab']['fields']['tab__module_class_name']['value'] = $details['module_class_name'];
			$box['tabs']['tab']['fields']['tab__method_name']['value'] = $details['method_name'];
			$box['tabs']['tab']['fields']['tab__param_1']['value'] = $details['param_1'];
			$box['tabs']['tab']['fields']['tab__param_2']['value'] = $details['param_2'];
		
		} else {
			$box['tabs']['tab']['fields']['tab_visibility']['value'] = 'everyone';
		}
		
		$box['tabs']['tab']['fields']['tab__field_id']['values'] =
			listCustomFields('users', false, 'group_boolean_and_list_only', false, true);
		//listCustomFields($dataset, $flat = true, $filter = false, $customOnly = true, $useOptGroups = false)
		
		break;
}
