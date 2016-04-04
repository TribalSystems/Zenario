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
	case 'zenario__modules/hidden_nav/zenario_plugin_nest/panel':
		
		//Check to see if the current Admin has the rights to change this nest
		$instance = getPluginInstanceDetails(request('refiner__nest'));
		
		if (!inc($instance['class_name'])) {
			exit;
		
		} elseif ($instance['content_id'] && !isDraft($instance['content_id'], $instance['content_type'], $instance['content_version'])) {
			exit;
		
		} elseif ($instance['content_id'] && !checkPriv('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version'])) {
			exit;
		
		} elseif (!$instance['content_id'] && !post('reorder') && !checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			exit;
		
		} elseif (!$instance['content_id'] && post('reorder') && !checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			exit;
		}
		
		//If this is a Wireframe Plugin, and a submit is being made, update the latest modification date
		if ($instance['content_id'] && !empty($_POST)) {
			updateVersion($instance['content_id'], $instance['content_type'], $instance['content_version']);
		}
		
		
		if ((get('add_plugin') || get('add_plugin_and_tab') || get('add_plugin_and_at_most_one_tab'))) {
			echo $this->addPluginConfirm($ids, get('refiner__nest'), true);
			
		} elseif (post('add_plugin')) {
			return self::addPluginInstance($ids, post('refiner__nest'));
		
		} elseif (post('add_plugin_and_tab')) {
			self::addTab(post('refiner__nest'));
			return self::addPluginInstance($ids, post('refiner__nest'));
		
		} elseif (post('add_plugin_and_at_most_one_tab')) {
			if (!checkRowExists('nested_plugins', array('instance_id' => post('refiner__nest'), 'is_tab' => 1))) {
				self::addTab(post('refiner__nest'));
			}
			return self::addPluginInstance($ids, post('refiner__nest'));
		
		} elseif ((get('add_wireframe') || get('add_wireframe_and_tab') || get('add_wireframe_and_at_most_one_tab') || get('add_another_wireframe_and_tab'))) {
			echo $this->addPluginConfirm($ids, get('refiner__nest'), false);
			
		} elseif (post('add_wireframe')) {
			return self::addPlugin($ids, post('refiner__nest'));
		
		} elseif (post('add_wireframe_and_tab')) {
			self::addTab(post('refiner__nest'));
			
			if ($newId = self::addPlugin($ids, post('refiner__nest'))) {
				$mrg = array('module' => htmlspecialchars(getModuleDisplayName($ids)));
				echo
					'<!--Message_Type:Success-->',
					'<p>', adminPhrase('You have added a [[module]] Plugin.', $mrg), '</p>',
					'<p>', adminPhrase('Any further Plugins that are added to this Nest will also be [[module]] Plugins.', $mrg), '</p>',
					'<p>', adminPhrase('A tab has been created automatically on which this Plugin sits. You may edit the tab\'s title.', $mrg), '</p>';
				
				return $newId;
			}
		
		} elseif (post('add_wireframe_and_at_most_one_tab')) {
			if (!checkRowExists('nested_plugins', array('instance_id' => post('refiner__nest'), 'is_tab' => 1))) {
				self::addTab(post('refiner__nest'));
			}
			return self::addPlugin($ids, post('refiner__nest'));
		
		} elseif (post('add_another_wireframe_and_tab')) {
			if ($moduleId = getRow('nested_plugins', 'module_id', array('instance_id' => post('refiner__nest'), 'is_tab' => 0))) {
				self::addTab(post('refiner__nest'));
				return self::addPlugin($moduleId, post('refiner__nest'));
			}
		
		} elseif (post('add_banner') || post('add_banner_and_tab')) {
			return self::addBanner($ids, post('refiner__nest'), post('add_banner_and_tab'));
		
		} elseif (post('upload_banner') || post('upload_banner_and_tab')) {
			if ($imageId = addFileToDatabase('image', $_FILES['Filedata']['tmp_name'], rawurldecode($_FILES['Filedata']['name']), true)) {
				return self::addBanner($imageId, post('refiner__nest'), post('upload_banner_and_tab'));
			} else {
				return false;
			}
		
		} elseif ((get('duplicate_plugin') || get('duplicate_plugin_and_add_tab'))) {
			echo $this->duplicatePluginConfirm($ids);
			
		} elseif (post('duplicate_plugin')) {
			return self::duplicatePlugin($ids, post('refiner__nest'));
		
		} elseif (post('duplicate_plugin_and_add_tab')) {
			self::addTab(post('refiner__nest'));
			return self::duplicatePlugin($ids, post('refiner__nest'));
		
		} elseif (get('remove_plugin')) {
			echo $this->removePluginConfirm($ids, post('refiner__nest'));
			
		} elseif (post('remove_plugin')) {
			foreach (explode(',', $ids) as $id) {
				self::removePlugin($instance['class_name'], $id, post('refiner__nest'));
			}
		
		} elseif ((get('remove_tab') || get('remove_all_but_last_tab'))) {
			echo $this->removeTabConfirm($ids, post('refiner__nest'));
			
		} elseif (post('remove_tab')) {
			foreach (explode(',', $ids) as $id) {
				$this->removeTab($instance['class_name'], $id, post('refiner__nest'));
			}
			
		} elseif (post('remove_all_but_last_tab')) {
			if (checkRowExists('nested_plugins', array('instance_id' => post('refiner__nest'), 'is_tab' => 1, 'tab' => 2))) {
				$this->removeTab($instance['class_name'], $ids, post('refiner__nest'));
			}
			
		} elseif (post('reorder')) {
			//Each specific Nest may have it's own rules for ordering, so be sure to call the correct reorder method for this Nest
			call_user_func(array($instance['class_name'], 'reorderNest'), $ids);
			call_user_func(array($instance['class_name'], 'resyncNest'), post('refiner__nest'));
		}
	
		break;
}

return false;