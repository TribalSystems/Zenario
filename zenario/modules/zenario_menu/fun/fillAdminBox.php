<?php
/*
 * Copyright (c) 2023, Tribal Limited
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
		
		if (ze\lang::count() > 1) {
			$box['tabs']['first_tab']['fields']['show_missing_menu_nodes']['side_note'] =
				ze\admin::phrase(
					'Show missing menu node text in the default language ([[english_name]]) where translated menu text is missing. (A CSS classname of "<code>missing</code>" will be added to these nodes.)',
					['english_name' => ze\lang::name(ze::$defaultLang)]);
		
		} else {
			$box['tabs']['first_tab']['fields']['show_missing_menu_nodes']['hidden'] = true;
		}
		
		if(ze::setting('zenario_menu__allow_overriding_of_invisible_flag_on_menu_nodes')){
			$box['tabs']['first_tab']['fields']['show_invisible_menu_nodes']['hidden'] = false;
		}else{
			$box['tabs']['first_tab']['fields']['show_invisible_menu_nodes']['hidden'] = true;
		}
		
		if (isset($box['tabs']['first_tab']['fields']['show_group_name_when_user_is_in_groups']) && isset($box['tabs']['first_tab']['fields']['user_groups'])) {
			$userGroups = ze\row::getAssocs('custom_dataset_fields', ['id', 'label'], ['type' => 'group', 'is_system_field' => 0], 'db_column', 'db_column');
			if ($userGroups) {
				$ord = 1;
				foreach ($userGroups as $groupId => $group) {
					$fields['first_tab/user_groups']['values'][$groupId] = ['ord' => $ord, 'label' => $group['label']];
					$ord++;
				}
			}
		}

		//The features below are currently only used in Menu (Vertical) module,
		//which extends this one.
		if ($box['module_class_name'] != 'zenario_menu_vertical') {
			//Custom title feature...
			unset($box['tabs']['first_tab']['fields']['show_custom_title']);
			unset($box['tabs']['first_tab']['fields']['title_tags']);
			unset($box['tabs']['first_tab']['fields']['custom_title']);

			//... open/close menu...
			unset($box['tabs']['first_tab']['fields']['enable_open_close']);
			unset($box['tabs']['first_tab']['fields']['open_close_initial_state']);

			//... and full width view.
			unset($box['tabs']['first_tab']['fields']['menu_number_of_levels']['values']['1_full_width']);
			unset($box['tabs']['first_tab']['fields']['number_of_columns_full_width']);
		}

		if ($box['module_class_name'] != 'zenario_menu') {
			unset($box['tabs']['first_tab']['fields']['limit_initial_level_1_menu_nodes_checkbox']);
			unset($box['tabs']['first_tab']['fields']['limit_initial_level_1_menu_nodes']);
			unset($box['tabs']['first_tab']['fields']['text_for_more_button']);
			unset($box['tabs']['first_tab']['fields']['reverse_order']);
		}

		if (!ze::in($box['module_class_name'], 'zenario_menu_vertical', 'zenario_menu_responsive_multilevel_2')) {
			unset($box['tabs']['first_tab']['fields']['show_parent_menu_node_text']);
		}
		
		break;
}
