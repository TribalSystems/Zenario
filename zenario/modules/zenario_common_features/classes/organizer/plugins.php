<?php
/*
 * Copyright (c) 2021, Tribal Limited
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
		
		$nestModuleId = ze\module::id('zenario_plugin_nest');
		$slideshowModuleId = ze\module::id('zenario_slideshow');
		$slideshow2ModuleId = ze\module::id('zenario_slideshow_simple');
		
		switch ($refinerName) {
			case 'nests':
			case 'nests_using_form':
			case 'nests_using_image':
				$isNest = true;
				$isSlideshow = false;
				$pluginAdminName = \ze\admin::phrase('nest');
				$ucPluginAdminName = \ze\admin::phrase('Nest');
				$panel['key']['moduleId'] = $nestModuleId;
                $panel['no_items_in_search_message'] = \ze\admin::phrase('No nests match your search');
				break;
			
			case 'slideshows':
			case 'slideshows_using_form':
			case 'slideshows_using_image':
				$isNest = true;
				$isSlideshow = true;
				$pluginAdminName = \ze\admin::phrase('slideshow');
				$ucPluginAdminName = \ze\admin::phrase('Slideshow');
				
				$moduleIds = [];
				if ($moduleId = $slideshowModuleId) {
					$moduleIds[] = $moduleId;
				}
				if ($moduleId = $slideshow2ModuleId) {
					$moduleIds[] = $moduleId;
				}
				
				if (count($moduleIds) > 1) {
					$panel['key']['moduleIds'] = implode(',', $moduleIds);
				} elseif (count($moduleIds) == 1) {
					$panel['key']['moduleId'] = $moduleIds[0];
				}
				
                $panel['no_items_in_search_message'] = \ze\admin::phrase('No slideshows match your search');
				break;
			
			case 'plugin':
				$panel['key']['moduleId'] = $refinerId;
			
			case 'view_nests_containing':
				$isNest = false;
				$isSlideshow = true;
				$pluginAdminName = \ze\admin::phrase('plugin');
				$ucPluginAdminName = \ze\admin::phrase('Plugin');
                $panel['no_items_in_search_message'] = \ze\admin::phrase('No nests or slideshows match your search');
				$panel['key']['containingModuleId'] = (int) ze::get('refiner__plugin');
				break;
			
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
				break;
			
			case 'view_nests_containing':
				$mrg = ze\row::get('modules', ['display_name'], ['id' => (int) $refinerId]);
				unset($panel['collection_buttons'], $panel['item_buttons']);
				break;
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

		if (ze::in($refinerName, 'nests_containing_plugins_of_specific_module', 'slideshows_containing_plugins_of_specific_module')) {
			$panel['db_items']['table'] .= '
				INNER JOIN [[DB_PREFIX]]nested_plugins np
					ON pi.id = np.instance_id';
		}

		//Catch the case where the user is viewing nest/slideshow plugin instances
		//(meaning that $refinerName is still 'plugin')
		if ($panel['key']['moduleId'] == $nestModuleId) {
			$pluginAdminName = \ze\admin::phrase('nest');
			$ucPluginAdminName = \ze\admin::phrase('Nest');
		} elseif ($panel['key']['moduleId'] == $slideshowModuleId || $panel['key']['moduleId'] == $slideshow2ModuleId) {
			$pluginAdminName = \ze\admin::phrase('slideshow');
			$ucPluginAdminName = \ze\admin::phrase('Slideshow');
		}
		
		//Change everywhere we've written ~plugin~ to what this panel is actually for
		$panel = json_decode(str_replace('~plugin~', $pluginAdminName, str_replace('~Plugin~', $ucPluginAdminName, json_encode($panel))), true);
		
		
		if (!$panel['key']['moduleId'] && isset($panel['collection_buttons']['create_dropdown'])) {
			//Get a list of modules that can be pluggable
			$key = ['status' => 'module_running', 'is_pluggable' => 1];
			if ($panel['key']['moduleIds']) {
				$key['id'] = explode(',', $panel['key']['moduleIds']);
			}
			$modules = ze\row::getValues('modules', 'display_name', $key, 'display_name');
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
		
		//Commented out code for the quick-filter buttons
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

		
		if ($panel['key']['moduleId']) {
			
			//If we're show plugins from a specific module, check the module is there and running.
			$module = ze\module::details($panel['key']['moduleId']);
			
			if (!$module) {
				echo \ze\admin::phrase('This module could not be found.');
				exit;
			}
			
			$moduleNotRunning =	$module['status'] == 'module_suspended'
							 || $module['status'] == 'module_not_initialized';
			
			$moduleMissing = !ze::moduleDir($module['class_name'], 'module_code.php', true);
			
			
			if ($moduleNotRunning || $moduleMissing) {
				
				if ($moduleNotRunning) {
					$panel['notice'] = [
						'show' => true,
						'message' => ze\admin::phrase('Warning: the module [[display_name]] ([[class_name]]) is not running.', $module),
						'type' => 'warning'
					];
				
				} else {
					$panel['notice'] = [
						'show' => true,
						'message' => ze\admin::phrase('Warning: the module [[display_name]] ([[class_name]]) is missing from the file system, so it cannot run.', $module),
						'type' => 'error'
					];
				}
				
				foreach (['collection_buttons', 'item_buttons', 'inline_buttons'] as $bType) {
					if (!empty($panel[$bType])
					 && is_array($panel[$bType])) {
						foreach ($panel[$bType] as &$button) {
							$button['disabled'] = true;
						}
					}
				}
			}
			
			$mrg = ['name' => $module['display_name']];
			
			switch ($panel['key']['moduleId']) {
				case $nestModuleId:
					$panel['select_mode_title'] = ze\admin::phrase('Nests', $mrg);

					//Do not override the title when viewing nests using a form or image.
					if (!ze::in($refinerName, 'nests_using_form', 'nests_using_image')) {
						$panel['title'] = $panel['select_mode_title'];
					}

					$panel['no_items_message'] = ze\admin::phrase('There are no nests. Click the "Create" button to create one.', $mrg);
					break;
				
				case $slideshowModuleId:
					$panel['select_mode_title'] = ze\admin::phrase('Slideshows (advanced)', $mrg);

					//Do not override the title when viewing slideshows using a form or image.
					if (!ze::in($refinerName, 'slideshows_using_form', 'slideshows_using_image')) {
						$panel['title'] = $panel['select_mode_title'];
					}

					$panel['no_items_message'] = ze\admin::phrase('There are no advanced slideshows. Click the "Create" button to create one.', $mrg);
					break;
				
				case $slideshow2ModuleId:
					$panel['select_mode_title'] = ze\admin::phrase('Slideshows (simple)', $mrg);

					//Do not override the title when viewing slideshows using a form or image.
					if (!ze::in($refinerName, 'slideshows_using_form', 'slideshows_using_image')) {
						$panel['title'] = $panel['select_mode_title'];
					}

					$panel['no_items_message'] = ze\admin::phrase('There are no slideshows. Click the "Create" button to create one.', $mrg);
					break;
				
				default:
					$panel['title'] =
					$panel['select_mode_title'] =
						ze\admin::phrase('"[[name]]" plugins in the library', $mrg);
					$panel['no_items_message'] = ze\admin::phrase('There are no "[[name]]" plugins in the library. Click the "Create" button to create one.', $mrg);
			}
		
		} elseif ($refinerName == 'view_nests_containing' && !empty($panel['key']['containingModuleId'])) {
			//Table join required to work...
			$panel['db_items']['table'] .= '
				INNER JOIN [[DB_PREFIX]]nested_plugins np
					ON np.instance_id = pi.id';
			
			//... and WHERE statement.
			$panel['db_items']['where_statement'] .= '
				AND pi.module_id IN (
					SELECT id FROM [[DB_PREFIX]]modules
					WHERE class_name IN ("zenario_plugin_nest", "zenario_slideshow", "zenario_slideshow_simple")
				)
				AND np.module_id = ' . (int) $panel['key']['containingModuleId'];

			$module = ze\module::details($panel['key']['containingModuleId']);
			$mrg = ['name' => $module['display_name']];

			$panel['title'] =
			$panel['select_mode_title'] =
				ze\admin::phrase('Nests or slideshows containing plugins of module "[[name]]"', $mrg);
			$panel['no_items_message'] = \ze\admin::phrase('There are no nests or slideshows containing plugins of module "[[name]]"', $mrg);
		
		} elseif (!$isNest) {
			//By default, don't show nests and slideshows with other library plugins
			$panel['db_items']['where_statement'] .= ' '. $panel['db_items']['custom__exclude_nests_and_slideshows'];
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

		if ($refinerName == 'plugin' && $refinerId) {
			$usageInNestsAndSlideshows = ze\moduleAdm::usageInNestsAndSlideshows($refinerId);
			$usageInNestsAndSlideshowsTotal = $usageInNestsAndSlideshows['nestCount'] + $usageInNestsAndSlideshows['slideshowCount'];
			if ($usageInNestsAndSlideshowsTotal > 0) {
				$panel['notice']['show'] = true;
				$panel['collection_buttons']['view_nests_containing']['hidden'] = false;

				$panel['notice']['message'] = ze\admin::nPhrase(
					"There is 1 plugin nest or slideshow which uses this module, [[link_start]]click to view[[link_end]].",
					"There are [[count]] nests or slideshows which use this module, [[link_start]]click to view[[link_end]].",
					$usageInNestsAndSlideshowsTotal,
					[
						'link_start' =>
							'<a href="' . ze\link::absolute(). 'organizer.php#zenario__modules/panels/modules/item//' . (int) $refinerId . '//collection_buttons/view_nests_containing////">',
						'link_end' => '</a>'
					]
				);
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__modules/panels/plugins') return;
		
		if (($_POST['delete'] ?? false) && ze\priv::check('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			foreach (ze\ray::explodeAndTrim($ids, true) as $id) {
				ze\pluginAdm::delete($id);
			}
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}