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
		zenario_plugin_nest::validateAdminBox('zenario_plugin_nest__tab', $settingGroup, $box, $fields, $values, $changes, $saving);
		
		switch ($values['tab/tab_visibility']) {
			case 'in_smart_group':
				if (!$values['tab/tab__smart_group']) {
					$box['tabs']['tab']['errors'][] = adminPhrase('Please select a smart group.');
				}
				break;
				
			case 'logged_in_with_field':
			case 'logged_in_without_field':
			case 'without_field':
				if (!$values['tab/tab__field_id']) {
					$box['tabs']['tab']['errors'][] = adminPhrase('Please select a field.');
				}
				break;
				
			case 'call_static_method':
				if (!$values['tab/tab__module_class_name']) {
					$box['tabs']['tab']['errors'][] = adminPhrase('Please enter the Class Name of a Plugin.');
				
				} elseif (!inc($values['tab/tab__module_class_name'])) {
					$box['tabs']['tab']['errors'][] = adminPhrase('Please enter the Class Name of a Plugin that you have running on this site.');
				
				} elseif ($values['tab/tab__method_name']
					&& !method_exists(
							$values['tab/tab__module_class_name'],
							$values['tab/tab__method_name'])
				) {
					$box['tabs']['tab']['errors'][] = adminPhrase('Please enter the name of an existing Static Method.');
				}
				
				if (!$values['tab/tab__method_name']) {
					$box['tabs']['tab']['errors'][] = adminPhrase('Please enter the name of a Static Method.');
				}
				break;
		}
		
		break;
}
