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


class zenario_common_features__admin_boxes__content_type_details extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//Allow this FAB to be opened from the modules panel, by module id
		if (is_numeric($box['key']['id'])) {
			$box['key']['idFromOrganizer'] = $box['key']['id'];
			
			if (!$box['key']['id'] = ze\row::get('content_types', 'content_type_id', ['module_id' => $box['key']['id']])) {
				echo ze\admin::phrase('This module does not seem to have a content type associated with it');
				exit;
			}
		}
		
		
		if (!$details = ze\contentAdm::cTypeDetails($box['key']['id'])) {
			exit;
		}
		
		$box['identifier']['css_class'] = 'content_type_'. $details['content_type_id'];
		$box['title'] = ze\admin::phrase('Settings for the content type "[[content_type_name_en]]"', $details);
		
		$values['details/content_type_name_en'] = $details['content_type_name_en'];
		$values['details/content_type_plural_en'] = $details['content_type_plural_en'];
		$values['details/description_field'] = $details['description_field'];
		$values['details/keywords_field'] = $details['keywords_field'];
		$values['details/writer_field'] = $details['writer_field'];
		$values['details/summary_field'] = $details['summary_field'];
		$values['details/release_date_field'] = $details['release_date_field'];
		$values['details/auto_flag_feature_image'] = $details['auto_flag_feature_image'];
		$values['details/enable_summary_auto_update'] = $details['enable_summary_auto_update'];
		$values['details/enable_categories'] = $details['enable_categories'] ? 'enabled' : 'disabled';
		$values['details/default_layout_id'] = $details['default_layout_id'];
		$values['details/default_permissions'] = $details['default_permissions'];
		$values['details/hide_private_item'] = $details['hide_private_item'];
		$values['details/hide_menu_node'] = $details['hide_menu_node'];
		
		$values['details/prompt_to_create_a_menu_node'] = $details['prompt_to_create_a_menu_node']? 'prompt' : 'dont_prompt';
		$values['details/menu_node_position_edit'] = $details['menu_node_position_edit'];
		
		
		if (!$details['module_id']
		 || !($status = ze\module::status($details['module_id']))
		 || ($status == 'module_is_abstract')) {
			$fields['details/module_id']['hidden'] = true;
		} else {
			$values['details/module_id'] = $details['module_id'];
		}
		
		$fields['details/menu_node_position_edit']['values']['force']['label'] = 
			ze\admin::phrase($fields['details/menu_node_position_edit']['values']['force']['label'], $details);
		
		
		$box['tabs']['details']['fields']['default_layout_id']['pick_items']['path'] =
			'zenario__layouts/panels/layouts/refiners/content_type//'. $box['key']['id']. '//';
		
		switch ($box['key']['id']) {
			case 'html':
			case 'document':
			case 'picture':
			case 'video':
			case 'audio':
				//HTML, Document, Picture, Video and Audio fields cannot currently be mandatory
				foreach (['description_field', 'keywords_field', 'summary_field', 'release_date_field'] as $field) {
					$box['tabs']['details']['fields'][$field]['values']['mandatory']['hidden'] = true;
				}
				
				break;
				
			
			case 'event':
				//Event release dates must be hidden as it is overridden by another field
				$box['tabs']['details']['fields']['release_date_field']['hidden'] = true;
		}
		
		
		$suggestedPositions = [];
		if ($box['key']['id'] != 'html') {
			foreach (ze\row::getAssocs('menu_nodes', ['id', 'section_id'], ['restrict_child_content_types' => $box['key']['id']]) as $menuNode) {
				
				//Menu positions are in the format CONCAT(section_id, '_', menu_id, '_', child_options)
				//Possible options for "child_options" are:
				$beforeNode = 0;
				$underNode = 1;
				$underNodeAtStart = 2;	//N.b. this option is not supported by position pickers using Organizer Select, but supported by ze\menuAdm::addContentItems() when saving
				$defaultPos = '';
				
				$mPath = ze\menuAdm::pathWithSection($menuNode['id'], true). ' › '. ze\admin::phrase('[ Create at the start ]');
				$mVal = $menuNode['section_id']. '_'. $menuNode['id']. '_'. $underNodeAtStart;
				
				$suggestedPositions[$mVal] = htmlspecialchars($mPath);
			}
			
			if (empty($suggestedPositions)) {
				$fields['details/menu_node_position_edit']['note_below'] =
					ze\admin::phrase('No positions in the menu have been set for [[content_type_plural_en]]. You can set a position by editing a menu node and going to the <em style="font-style: italic;">Advanced</em> tab.', $details);
			} else {
				$fields['details/menu_node_position_edit']['note_below'] =
					ze\admin::nphrase('The following position in the menu has been set for [[content_type_plural_en]]:',
						'The following positions in the menu have been set for [[content_type_plural_en]]:',
						count($suggestedPositions), $details).
					'<ul><li>'. implode('</li><li>', $suggestedPositions). '</li></ul>'.
					ze\admin::phrase('You can set a position by editing a menu node and going to the <em style="font-style: italic;">Advanced</em> tab.', $details);
			}
		}

	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (!$values['details/default_layout_id'] || !($template = ze\content::layoutDetails($values['details/default_layout_id']))) {
			$box['tabs']['details']['errors'][] = ze\admin::phrase('Please select a default layout.');
		
		} elseif ($template['status'] != 'active') {
			$box['tabs']['details']['errors'][] = ze\admin::phrase('The default layout must be an active layout.');
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		if (ze\priv::check('_PRIV_EDIT_CONTENT_TYPE')) {
			
			$vals = [
				'content_type_name_en' => $values['details/content_type_name_en'],
				'content_type_plural_en' => $values['details/content_type_plural_en'],
				'description_field' => $values['details/description_field'],
				'keywords_field' => $values['details/keywords_field'],
				'writer_field' => $values['details/writer_field'],
				'summary_field' => $values['details/summary_field'],
				'release_date_field' => $values['details/release_date_field'],
				'auto_flag_feature_image' => $values['details/auto_flag_feature_image'],
				'enable_summary_auto_update' => 0,
				'enable_categories' => ($values['details/enable_categories'] == 'enabled') ? 1 : 0,
				'default_layout_id' => $values['details/default_layout_id'],
				'prompt_to_create_a_menu_node' => (int) ($values['details/prompt_to_create_a_menu_node'] == 'prompt'),
				'menu_node_position_edit' => $values['details/menu_node_position_edit'] ?: 'suggest',
				'default_permissions' => $values['details/default_permissions'],
				'hide_private_item' => $values['details/hide_private_item'],
				'hide_menu_node' => $values['details/hide_menu_node']
			];
			
			if ($values['details/summary_field'] != 'hidden') {
				$vals['enable_summary_auto_update'] = $values['details/enable_summary_auto_update'];
			}
			
			switch ($box['key']['id']) {
				case 'document':
				case 'picture':
				case 'html':
					//HTML/Document/Picture fields cannot currently be mandatory
					foreach (['description_field', 'keywords_field', 'summary_field', 'release_date_field'] as $field) {
						if ($vals[$field] == 'mandatory') {
							$vals[$field] = 'optional';
						}
					}
					
					break;
					
				
				case 'event':
					//Event release dates must be hidden as it is overridden by another field
					$vals['release_date_field'] = 'hidden';
			}
			
			ze\row::update('content_types', $vals, $box['key']['id']);
		}
		if ($box['key']['idFromOrganizer']) {
			$box['key']['id'] = $box['key']['idFromOrganizer'];
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Put the key back to what it originally was, to prevent highlighting bugs in Organizer
		if ($box['key']['idFromOrganizer']) {
			$box['key']['id'] = $box['key']['idFromOrganizer'];
		}
	}
}
