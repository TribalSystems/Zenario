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
	case 'zenario_plugin_nest__tab':
		$instance = getPluginInstanceDetails($box['key']['instanceId']);
		
		//Load details of this Instance, and check for permissions to save
		if ($instance['content_id']) {
			exitIfNotCheckPriv('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version']);
			updateVersion($instance['content_id'], $instance['content_type'], $instance['content_version']);
		} else {
			exitIfNotCheckPriv('_PRIV_MANAGE_REUSABLE_PLUGIN');
		}
		
		if (!empty($box['key']['id'])) {
			$this->updateTab($values['tab/name_or_title'], $box['key']['id']);
		} else {
			$box['key']['id'] = self::addTab($box['key']['instanceId'], $values['tab/name_or_title']);
		}
		
		break;
	
	
	case 'zenario_plugin_nest__convert_between':
		
		$instance = $nestable = $numPlugins = $moduleId = $onlyOneModule = $onlyBanners = false;
		if (!$this->setupConversionAdminBox($box['key']['id'], $box['tabs']['convert']['fields'], $instance, $nestable, $numPlugins, $moduleId, $onlyOneModule, $onlyBanners)) {
			exit;
		
		} elseif (!(
			$instance['content_id']?
				checkPriv('_PRIV_MANAGE_ITEM_SLOT') && checkInstanceIsWireframeOnItemLayer($box['key']['id'])
			:	checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')
		)) {
			exit;
		}
		
		foreach ($box['tabs']['convert']['fields'] as $key => &$field) {
			if (!empty($values['convert/'. $key])) {
				if (isset($field['convert_to']) && !engToBooleanArray($field, 'disabled')) {
					
					//Convert the Nest
					//There are three different possibilities: Plugin -> Nest, Nest -> Different Nest, and Nest -> Plugin
					//This can also be done on Reusable Plugins, or Wireframe Plugins placed on the Item Layer
					
					if ($field['convert_to'] == 'standalone') {
						//Nest -> Plugin
						$egg = getRow('nested_plugins', array('id', 'module_id', 'framework', 'css_class'), array('instance_id' => $instance['instance_id'], 'is_tab' => 0));
						$newModule = getModuleDetails($egg['module_id']);
						
						//Remove the Nest's Settings
						deleteRow('plugin_settings', array('instance_id' => $instance['instance_id'], 'nest' => 0));
						//Update the Nested Plugin's Settings to be for the Plugin
						updateRow('plugin_settings', array('nest' => 0), array('instance_id' => $instance['instance_id']));
						//Update the framework/css_class
						updateRow('plugin_instances', array('framework' => $egg['framework'], 'css_class' => $egg['css_class']), $instance['instance_id']);
						
						//Remove the Egg, and any Tabs
						$result = getRows('nested_plugins', array('id', 'is_tab'), array('instance_id' => $instance['instance_id']));
						while ($egg = sqlFetchAssoc($result)) {
							if ($egg['is_tab']) {
								self::removeTab(false, $egg['id'], $instance['instance_id']);
							} else {
								self::removePlugin(false, $egg['id'], $instance['instance_id']);
							}
						}
						
						
						
						
						
					} else {
						$newModule = getModuleDetails($field['convert_to'], 'class');
						
						if ($nestable) {
							//Plugin -> Nest
							//Start by adding the Plugin in as an Egg of itself
							$nestedItemId = self::addPluginInstance($instance['instance_id'], $instance['instance_id']);
							
							//Remove the original settings
							deleteRow('plugin_settings', array('instance_id' => $instance['instance_id'], 'nest' => 0));
							
							//Update the framework
							updateRow('plugin_instances', array('framework' => arrayKey($field, 'convert_to_framework'), 'css_class' => ''), $instance['instance_id']);
						
						} else {
							//Nest -> Different Nest
							//Overwrite the framework if specified
							if (isset($field['convert_to_framework'])) {
								updateRow('plugin_instances', array('framework' => $field['convert_to_framework']), $instance['instance_id']);
							}
							
							//No other action needed, we just need to change the Module Ids below
						}
					}
					
					
					//Update to the new Module Id
					updateRow('plugin_instances', array('module_id' => $newModule['module_id']), $instance['instance_id']);
					if ($instance['content_id']) {
						updateRow(
							'plugin_item_link',
							array('module_id' => $newModule['module_id']),
							array('content_id' => $instance['content_id'], 'content_type' => $instance['content_type'], 'content_version' => $instance['content_version'], 'slot_name' => $instance['slot_name']));
						
						updateVersion($instance['content_id'], $instance['content_type'], $instance['content_version']);
					
					} else {
						replacePluginInstance($instance['module_id'], $instance['instance_id'], $newModule['module_id'], $instance['instance_id']);
					}
					
					if ($field['convert_to'] != 'standalone') {
						if (inc($field['convert_to'])) {
							call_user_func(array($field['convert_to'], 'resyncNest'), $instance['instance_id']);
						}
					}
					
					return;
				}
			}
		}
		
		break;
}