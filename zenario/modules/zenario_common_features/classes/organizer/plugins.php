<?php
/*
 * Copyright (c) 2019, Tribal Limited
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


class zenario_common_features__organizer__plugins extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__modules/panels/plugins') return;
		
		switch ($refinerName) {
			case 'nests':
			case 'nests_using_form':
			case 'nests_using_image':
				$isNest = true;
				$isSlideshow = false;
				$pluginAdminName = \ze\admin::phrase('nest');
				$ucPluginAdminName = \ze\admin::phrase('Nest');
				$panel['key']['moduleId'] = ze\module::id('zenario_plugin_nest');
                $panel['no_items_in_search_message'] = \ze\admin::phrase('No nests match your search');
				break;
			
			case 'slideshows':
			case 'slideshows_using_form':
			case 'slideshows_using_image':
				$isNest = true;
				$isSlideshow = true;
				$pluginAdminName = \ze\admin::phrase('slideshow');
				$ucPluginAdminName = \ze\admin::phrase('Slideshow');
				$panel['key']['moduleId'] = ze\module::id('zenario_slideshow');
                $panel['no_items_in_search_message'] = \ze\admin::phrase('No slideshows match your search');
				break;
			
			case 'plugin':
				$panel['key']['moduleId'] = $refinerId;
			
			default:
				$isNest = false;
				$isSlideshow = false;
				$pluginAdminName = \ze\admin::phrase('plugin');
				$ucPluginAdminName = \ze\admin::phrase('Plugin');
                $panel['no_items_in_search_message'] = \ze\admin::phrase('No plugins match your search');
		}
		
		//Set specific titles for some refiners
		$mrg = [];
		switch ($refinerName) {
			case 'plugins_using_form':
			case 'nests_using_form':
			case 'slideshows_using_form':
				if (ze\module::inc('zenario_user_forms')) {
					$mrg['name'] = zenario_user_forms::getFormName($refinerId);
				}
				unset($panel['collection_buttons']['create'], $panel['collection_buttons']['create_dropdown']);
				break;
				
			case 'plugins_using_image':
			case 'nests_using_image':
			case 'slideshows_using_image':
				$mrg = ze\row::get('files', ['filename'], $refinerId);
				unset($panel['collection_buttons']['create'], $panel['collection_buttons']['create_dropdown']);
		}
		switch ($refinerName) {
			case 'plugins_using_form':
				$panel['title'] = ze\admin::phrase('Plugins using the form "[[name]]"', $mrg);
				$panel['no_items_message'] = ze\admin::phrase('There are no plugins using the form "[[name]]"', $mrg);
				break;
			case 'nests_using_form':
				$panel['title'] = ze\admin::phrase('Nests using the form "[[name]]"', $mrg);
				$panel['no_items_message'] = ze\admin::phrase('There are no nests using the form "[[name]]"', $mrg);
				break;
			case 'slideshows_using_form':
				$panel['title'] = ze\admin::phrase('Slideshows using the form "[[name]]"', $mrg);
				$panel['no_items_message'] = ze\admin::phrase('There are no slideshows using the form "[[name]]"', $mrg);
				break;
			
			case 'plugins_using_image':
				$panel['title'] = ze\admin::phrase('Plugins using the image "[[filename]]"', $mrg);
				$panel['no_items_message'] = ze\admin::phrase('There are no plugins using the image "[[filename]]"', $mrg);
				break;
			case 'nests_using_image':
				$panel['title'] = ze\admin::phrase('Nests using the image "[[filename]]"', $mrg);
				$panel['no_items_message'] = ze\admin::phrase('There are no nests using the image "[[filename]]"', $mrg);
				break;
			case 'slideshows_using_image':
				$panel['title'] = ze\admin::phrase('Slideshows using the image "[[filename]]"', $mrg);
				$panel['no_items_message'] = ze\admin::phrase('There are no slideshows using the image "[[filename]]"', $mrg);
				break;
			
			case 'nests':
                $panel['no_items_message'] = \ze\admin::phrase('No nests are in the nest library');
                break;
			case 'slideshows':
                $panel['no_items_message'] = \ze\admin::phrase('No slideshows are in the slideshow library');
                break;
			default:
                $panel['no_items_message'] = \ze\admin::phrase('No plugins are in the plugin library');
		}


		
		//Change everywhere we've written ~plugin~ to what this panel is actually for
		$panel = json_decode(str_replace('~plugin~', $pluginAdminName, str_replace('~Plugin~', $ucPluginAdminName, json_encode($panel))), true);
		
		
		if (!$panel['key']['moduleId'] && isset($panel['collection_buttons']['create_dropdown'])) {
			//Get a list of modules that can be pluggable
			$key = ['status' => 'module_running', 'is_pluggable' => 1];
			$modules = ze\row::getValues('modules', 'display_name', ['status' => 'module_running', 'is_pluggable' => 1], 'display_name');
			$ord = 222;
			
			//Automatically create drop-down menus for quickly adding plugins
			foreach ($modules as $moduleId => $name) {
				$panel['collection_buttons']['create_plugin_'. $moduleId] =
					[
						'ord' => ++$ord,
						'parent' => 'create_dropdown',
						'label' => $name,
						'admin_box' => [
							//'class_name' => $c,
							'path' => 'plugin_settings',
							'create_another' => true,
							'key' => [
								'moduleId' => $moduleId
					]]];
			}
		}
		
		//Commented out code for the quick-filter byttons
		#	unset($panel['quick_filter_buttons']['module']);
		#	unset($panel['quick_filter_buttons']['all_modules']);
		#
		#} elseif (!$refinerName || $refinerName == 'album') {
		#	//Check the current module filter, if there is one
		#	$moduleIdFilter = zenario_organizer::filterValue('module_id');
		#	
		#	//If this is the first load, and something was selected,
		#	//work out which module that was and start the filter set to that!
		#	if ($moduleIdFilter === null
		#	 && ($instanceId = (int) ze::request('_item'))
		#	 && ($moduleId = ze\row::get('plugin_instances', 'module_id', $instanceId))) {
		#		zenario_organizer::setFilterValue('module_id', $moduleIdFilter = $moduleId);
		#	}
		#
		#	$sql = "
		#		SELECT m.id, m.display_name, COUNT(pi.id) AS cnt
		#		FROM ". DB_PREFIX. "modules AS m
		#		LEFT JOIN ". DB_PREFIX. "plugin_instances AS pi
		#		   ON pi.module_id = m.id
		#		  AND pi.content_id = 0
		#		WHERE m.status = 'module_running'
		#		  AND m.is_pluggable = 1
		#		  AND m.class_name NOT IN ('zenario_plugin_nest', 'zenario_slideshow')
		#		GROUP BY m.id, m.display_name
		#		ORDER BY m.display_name";
		#	
		#	$ord = 100;
		#	foreach (ze\sql::fetchAssocs($sql) as $module) {
		#		
		#		$label = ze\admin::phrase('[[display_name]] ([[cnt]])', $module);
		#		
		#		$panel['quick_filter_buttons']['module_'. $module['id']] = [
		#			'ord' => ++$ord,
		#			'parent' => 'module',
		#			'column' => 'module_id',
		#			'label' => $label,
		#			'value' => $module['id']
		#		];
		#		
		#		//If the module was chosen, change the text on the parent-button
		#		//and also set the module id in the key so any FABs that open using that module id.
		#		if ($moduleIdFilter == $module['id']) {
		#			$panel['quick_filter_buttons']['module']['label'] = $label;
		#		}
		#	}
		#
		#} else {
		#	unset($panel['quick_filter_buttons']['module']);
		#	unset($panel['quick_filter_buttons']['all_modules']);

		
		//By default, don't show nests and slideshows with other library plugins
		if (!$isNest) {
			$panel['db_items']['where_statement'] .= ' '. $panel['db_items']['custom__exclude_nests_and_slideshows'];
			
			if ($panel['key']['moduleId']) {
				
				$mrg = ['name' => ze\module::displayName($panel['key']['moduleId'])];
				$panel['title'] =
				$panel['select_mode_title'] =
					ze\admin::phrase('"[[name]]" plugins in the library', $mrg);
				$panel['no_items_message'] =
					ze\admin::phrase('There are no "[[name]]" plugins in the library. Click the "Create" button to create one.', $mrg);
			}
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__modules/panels/plugins') return;
		
		$addFullDetails = ze::in($mode, 'full', 'quick', 'select');
		
		$panel['key']['skinId'] = $_REQUEST['skinId'] ?? false;
		
		foreach ($panel['items'] as $id => &$item) {
			
			$item['code'] = ze\plugin::codeName($id, $item['module_class_name']);
			
			
			if ($item['checksum']) {
				$img = '&c='. $item['checksum'];
				$item['has_image'] = true;
				$item['image'] = 'zenario/file.php?og=1'. $img;
			}
			
			if ($item['module_class_name'] != 'zenario_plugin_nest'
			 && $item['module_class_name'] != 'zenario_slideshow') {
				$item['link'] = false;
			}
			
			//if (strpos($item['module_class_name'], 'nest') !== false
			// && ze\pluginAdm::conductorEnabled($id)) {
			//	$item['usesConductor'] = true;
			//}
			
			if ($addFullDetails) {
				//Show the usage of the plugin instance
				$usage = ze\pluginAdm::getUsage($id);
				$item['usage_item'] = $usage['content_items'];
				$item['usage_layouts'] = $usage['layouts'];
			
				$usageLinks = [
					'content_items' => 'zenario__modules/panels/plugins/item_buttons/usage_item//' . (int)$id . '//', 
					'layouts' => 'zenario__modules/panels/plugins/item_buttons/usage_layouts//' . (int)$id . '//'
				];
				$item['where_used'] = implode('; ', ze\miscAdm::getUsageText($usage, $usageLinks));
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__modules/panels/plugins') return;
		
		if (($_POST['delete'] ?? false) && ze\priv::check('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				ze\pluginAdm::delete($id);
			}
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}