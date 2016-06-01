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
		zenario_plugin_nest::formatAdminBox('zenario_plugin_nest__tab', $settingGroup, $box, $fields, $values, $changes);
		
		$box['tabs']['tab']['fields']['tab__field_id']['hidden'] = true;
		$box['tabs']['tab']['fields']['tab__field_value']['hidden'] = true;
		$box['tabs']['tab']['fields']['tab__field_value']['values'] = array();
		
		if (in($values['tab/tab_visibility'], 'logged_in_with_field', 'logged_in_without_field', 'without_field')) {
			$box['tabs']['tab']['fields']['tab__field_id']['hidden'] = false;
			$box['tabs']['tab']['fields']['tab__field_value']['hidden'] = false;
			
			if ($field = getDatasetFieldDetails($values['tab/tab__field_id'])) {
				switch ($field['type']) {
					case 'group':
					case 'checkbox':
						$box['tabs']['tab']['fields']['tab__field_value']['empty_value'] = adminPhrase('checked');
						break;
					
					case 'checkboxes':
						$box['tabs']['tab']['fields']['tab__field_value']['empty_value'] = adminPhrase('checked');
						break;
						
					case 'radios':
					case 'select':
					case 'centralised_radios':
					case 'centralised_select':
						$box['tabs']['tab']['fields']['tab__field_value']['empty_value'] = adminPhrase('set to any value');
						
						foreach (getDatasetFieldLOV($field) as $value => $displayValue) {
							$box['tabs']['tab']['fields']['tab__field_value']['values'][$value] =
								adminPhrase('set to "[[displayValue]]"', array('displayValue' => $displayValue));
							
						}
						break;
				}
			}
		}
		
		$box['tabs']['tab']['fields']['tab__module_class_name']['hidden'] =
		$box['tabs']['tab']['fields']['tab__method_name']['hidden'] =
		$box['tabs']['tab']['fields']['tab__param_1']['hidden'] =
		$box['tabs']['tab']['fields']['tab__param_2']['hidden'] =
			$values['tab/tab_visibility'] != 'call_static_method';
		
		break;
	
	
	case 'plugin_settings':
		zenario_plugin_nest::formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);

		break;
}
