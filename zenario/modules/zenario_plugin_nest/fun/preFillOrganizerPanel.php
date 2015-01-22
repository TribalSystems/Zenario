<?php
/*
 * Copyright (c) 2014, Tribal Limited
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
	case 'zenario__modules/hidden_nav/zenario_plugin_nest/panel':
	
		$instance = getPluginInstanceDetails(get('refiner__nest'));
		$c = $instance['class_name'];
		
		
		//Each type of nest has different controls. Show the relevant controls for this type of nest
		if (isset($panel[$c. '__collection_buttons'])) {
			$panel['collection_buttons'] = $panel[$c. '__collection_buttons'];
		}
		if (isset($panel[$c. '__item_buttons'])) {
			$panel['item_buttons'] = $panel[$c. '__item_buttons'];
		}
		
		if (isset($panel[$c. '__item']) && is_array($panel[$c. '__item'])) {
			if (!isset($panel['item']) || !is_array($panel['item'])) {
				$panel['item'] = array();
			}
			
			foreach ($panel[$c. '__item'] as $key => $tags) {
				$panel['item'][$key] = $tags;
			}
		}
		
		//Plugin pickers need different paths for Wireframe modules
		if ($instance['content_id']) {
			foreach (array('collection_buttons', 'item_buttons') as $buttonType) {
				if (!empty($panel[$buttonType])) {
					foreach ($panel[$buttonType] as &$button) {
						if (isset($button['pick_items']['path_if_wireframe'])) {
							$button['pick_items']['path'] = $button['pick_items']['path_if_wireframe'];
						}
					}
				}
			}
		}
			
		
		//If they are both there, choose between the add_another_wireframe_and_tab and add_wireframe_and_tab buttons as appropriate
		if (isset($panel['collection_buttons']['add_wireframe_and_tab']) && isset($panel['collection_buttons']['add_another_wireframe_and_tab'])) {
			if ($moduleId = getRow('nested_plugins', 'module_id', array('instance_id' => get('refiner__nest'), 'is_tab' => 0))) {
				$panel['collection_buttons']['add_another_wireframe_and_tab']['label'] =
					adminPhrase('Add another "[[name]]" plugin into the nest', array('name' => getModuleDisplayName($moduleId)));
				unset($panel['collection_buttons']['add_wireframe_and_tab']);
			} else {
				unset($panel['collection_buttons']['add_another_wireframe_and_tab']);
			}
		}
		
		//Hide the add_tab_if_there_isnt_one button if there is an existing tab
		if (isset($panel['collection_buttons']['add_tab_if_there_isnt_one'])
		 && checkRowExists('nested_plugins', array('instance_id' => get('refiner__nest'), 'is_tab' => 1))) {
			unset($panel['collection_buttons']['add_tab_if_there_isnt_one']);
		}
		
		if (isset($panel['collection_buttons']['add_existing_banner_and_tab']['pick_items'])) {
			$panel['collection_buttons']['add_existing_banner_and_tab']['pick_items']['path'] = 'zenario__modules/nav/modules/panel/item_buttons/view_instances//'. getModuleIdByClassName('zenario_banner'). '//';
		}
		
		//The remove_all_but_last_tab button should not allow the Admin to remove a Tab if only one is left
		if (isset($panel['item_buttons']['remove_all_but_last_tab'])
		 && !checkRowExists('nested_plugins', array('instance_id' => get('refiner__nest'), 'is_tab' => 1, 'tab' => 2))) {
			unset($panel['item_buttons']['remove_all_but_last_tab']);
		}
		
		
		
		//Check permissions for Wireframe modules
		if ($instance['content_id'] && !isDraft($instance['content_id'], $instance['content_type'], $instance['content_version'])) {
			$panel['collection_buttons'] = array();
			$panel['collection_buttons']['help'] = array(
				'css_class' => 'help',
				'help' => array(
					'message' =>
						adminPhrase('This nest is on a published, hidden or archived content item and cannot be edited.<br /><br />Create a Draft to make changes.')));
			
			$panel['item_buttons'] = array(
				'view' => $panel['item_buttons']['view'],
				'plugin_settings' => $panel['item_buttons']['plugin_settings']);
			
			unset($panel['reorder']);
		
		} elseif ($instance['content_id'] && !checkPriv('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version'])) {
			$panel['collection_buttons'] = array();
			$panel['collection_buttons']['help'] = array(
				'css_class' => 'help',
				'help' => array(
					'message' =>
						adminPhrase('This Content Item is checked out by another Administrator and cannot be edited.')));
			
			$panel['item_buttons'] = array(
				'view' => $panel['item_buttons']['view'],
				'plugin_settings' => $panel['item_buttons']['plugin_settings']);
			
			unset($panel['reorder']);
		
		} elseif (!$instance['content_id'] && !checkPriv('_PRIV_VIEW_REUSABLE_PLUGIN')) {
			exit;
		}
		
		
		//Check permissions for Reusable modules
		if (!$instance['content_id'] && !checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			$panel['collection_buttons'] = array();
			$panel['item_buttons'] = array(
				'view' => $panel['collection_buttons']['view'],
				'plugin_settings' => $panel['collection_buttons']['plugin_settings']);
		
		}
		
		if (!$instance['content_id'] && !checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			unset($panel['reorder']);
		}
		
		break;
}