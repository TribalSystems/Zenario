<?php
/*
 * Copyright (c) 2026, Tribal Limited
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


//Always run the ze\moduleAdm::addNew() function when doing any database updates,
//just to check for newly added modules.
ze\moduleAdm::addNew($skipIfFilesystemHasNotChanged = false);


//Code for handling renaming Plugin directories
function renameModuleDirectory($oldName, $newName, $movePlugins, $moveEditableCSS, $movePhrases, $uninstallOldModule = false) {
	
	$oldId = ze\module::id($oldName);
	$newId = ze\module::id($newName);
	
	if ($newName) {
		
		if ($movePlugins && $oldId && $newId) {
			foreach([
				'content_types', 'jobs', 'signals',
				'module_dependencies', 'plugin_setting_defs',
				'nested_plugins', 'plugin_instances',
				'plugin_item_link', 'plugin_layout_link', 'plugin_sitewide_link'
			] as $table) {
				$sql = "
					UPDATE IGNORE ". DB_PREFIX. $table. " SET
						module_id = ". (int) $newId. "
					WHERE module_id = ". (int) $oldId;
				ze\sql::update($sql);
			}
			
			$oldStatus = ze\row::get('modules', 'status', $oldId);
			$newStatus = ze\row::get('modules', 'status', $newId);
			
			if (ze::in($newStatus, 'module_not_initialized', 'module_suspended')) {
				ze\row::set('modules', ['status' => $oldStatus], $newId);
			}
		}
		
		if ($movePhrases) {
			$sql = "
				UPDATE IGNORE ". DB_PREFIX. "visitor_phrases SET
					module_class_name = '". ze\escape::sql($newName). "'
				WHERE module_class_name = '". ze\escape::sql($oldName). "'";
			ze\sql::update($sql);
		}
		
		if ($moveEditableCSS
		 && is_dir($gtDir = CMS_ROOT. 'zenario_custom/skins/')) {
			
			foreach (scandir($gtDir) as $skin) {
				
				if ($skin[0] != '.'
				 && is_dir($cssDir = $gtDir. $skin. '/editable_css/')
				 && is_writable($cssDir = $gtDir. $skin. '/editable_css/')) {
					
					foreach (scandir($cssDir) as $oldFile) {
						if (is_file($cssDir. $oldFile)
						 && ($suffix = ze\ring::chopPrefix('2.'. $oldName, $oldFile))
						 && ($contents = file_get_contents($cssDir. $oldFile))) {
							
							$contents = preg_replace('/\b'. $oldName. '_(\d)/', $newName. '_$1', $contents);
							
							$newFile = '2.'. $newName. $suffix;
							
							if (file_exists($cssDir. $newFile)) {
								if (is_writable($cssDir. $newFile)) {
									file_put_contents(
										$cssDir. $newFile,
										"\n\n\n". $contents,
										FILE_APPEND | LOCK_EX
									);
									unlink($cssDir. $oldFile);
								}
							} else {
								file_put_contents($cssDir. $newFile, $contents);
								unlink($cssDir. $oldFile);
							}
						}
					}
				}
			}
		}
	}
	
	if ($uninstallOldModule && $oldId) {
		ze\row::update('modules', ['status' => 'module_not_initialized'], $oldId);
		ze\row::delete('special_pages', ['module_class_name' => $oldName]);
	}
}

//Code for one Module replacing functionality from another
function replaceModule($oldName, $newName) {
	if (($oldId = ze\module::id($oldName)) && ($newId = ze\module::id($newName))) {
		foreach([
			'content_types',
			'nested_plugins', 'plugin_instances',
			'plugin_item_link', 'plugin_layout_link'
		] as $table) {
			$sql = "
				UPDATE IGNORE ". DB_PREFIX. $table. " SET
					module_id = ". (int) $newId. "
				WHERE module_id = ". (int) $oldId;
			ze\sql::update($sql);
		}
		
		$oldStatus = ze\row::get('modules', 'status', $oldId);
		$newStatus = ze\row::get('modules', 'status', $newId);
		
		if ($oldStatus == 'module_running' || $newStatus == 'module_running') {
			ze\row::set('modules', ['status' => 'module_running'], $newId);
		
		} elseif ($oldStatus == 'module_suspended' || $newStatus == 'module_suspended') {
			ze\row::set('modules', ['status' => 'module_suspended'], $newId);
		}
		
		ze\moduleAdm::uninstall($oldId, $uninstallRunningModules = true);
		
		return true;
	}
	
	return false;
}

//Code for one Module replacing specific plugins from another
//Currently only supports replacing plugins that are in a nest
function replaceModulePlugins($oldName, $newName, $settingName, $settingValue) {
	if (($oldId = ze\module::id($oldName)) && ($newId = ze\module::id($newName))) {
		$sql = "
			UPDATE IGNORE ". DB_PREFIX. "nested_plugins np
			INNER JOIN " . DB_PREFIX . "plugin_settings ps
				ON np.id = ps.egg_id
				AND ps.name = '" . ze\escape::sql($settingName) . "'
			SET np.module_id = ". (int) $newId. "
			WHERE np.module_id = ". (int) $oldId;
		if (is_array($settingValue)) {
			$sql .= "
				AND ps.value IN (" . ze\escape::in($settingValue) . ")";
		} else {
			 $sql .= "
				AND ps.value = '" . ze\escape::sql($settingValue) . "'";
		}
		ze\sql::update($sql);
		
		$oldStatus = ze\row::get('modules', 'status', $oldId);
		$newStatus = ze\row::get('modules', 'status', $newId);
		
		if ($oldStatus == 'module_running' || $newStatus == 'module_running') {
			ze\row::set('modules', ['status' => 'module_running'], $newId);
		
		} elseif ($oldStatus == 'module_suspended' || $newStatus == 'module_suspended') {
			ze\row::set('modules', ['status' => 'module_suspended'], $newId);
		}
		
		return true;
	}
	
	return false;
}

//Code for running a dependency, if a previously existing Module gains a new dependancy
function runNewModuleDependency($moduleName, $dependencyName) {
	if (($moduleId = ze\module::id($moduleName)) && ($dependencyId = ze\module::id($dependencyName))) {
		$moduleStatus = ze\row::get('modules', 'status', $moduleId);
		$dependencyStatus = ze\row::get('modules', 'status', $dependencyId);
		
		if ($moduleStatus == 'module_running' && !ze::in($dependencyStatus, 'module_running', 'module_is_abstract')) {
			ze\row::set('modules', ['status' => 'module_running'], $dependencyId);
		
		} elseif ($moduleStatus == 'module_suspended' && !ze::in($dependencyStatus, 'module_running', 'module_suspended', 'module_is_abstract')) {
			ze\row::set('modules', ['status' => 'module_suspended'], $dependencyId);
		}
		
		return true;
	}
	
	return false;
}


function convertSpecialPageToPluginPage($specialPage, $pluginPageModule = '', $pluginPageMode = '') {
	if ($spDetails = ze\row::get('special_pages', true, $specialPage)) {
		
		ze\row::insert('plugin_pages_by_mode', [
			'equiv_id' => $spDetails['equiv_id'],
			'content_type' => $spDetails['content_type'],
			'module_class_name' => $pluginPageModule ?: $spDetails['module_class_name'],
			'mode' => $pluginPageMode ?: '',
		], $ignore = true);
		
		ze\row::delete('special_pages', $specialPage);
	}
}


function renamePluginSetting(
	$moduleNames, $oldPluginSettingName, $newPluginSettingName,
	$checkNonNestedPlugins = true, $checkNestedPlugins = true,
	$extraSetSQL = '', $extraWhereSQL = ''
) {
	
	if ($checkNonNestedPlugins) {
		$sql = "
			UPDATE IGNORE `". DB_PREFIX. "modules` AS m
			INNER JOIN `". DB_PREFIX. "plugin_instances` AS pi
			   ON pi.module_id = m.id
			INNER JOIN `". DB_PREFIX. "plugin_settings` AS ps
			   ON ps.instance_id = pi.id
			  AND ps.egg_id = 0
			  AND ps.name = '". ze\escape::sql($oldPluginSettingName). "'
			SET ps.name = '". ze\escape::sql($newPluginSettingName). "'
			". $extraSetSQL. "
			WHERE m.class_name IN (". ze\escape::in($moduleNames, 'asciiInSQL'). ")
			". $extraWhereSQL;
		ze\sql::update($sql);
	}

	if ($checkNestedPlugins) {
		$sql = "
			UPDATE IGNORE `". DB_PREFIX. "modules` AS m
			INNER JOIN `". DB_PREFIX. "nested_plugins` AS np
			   ON np.module_id = m.id
			INNER JOIN `". DB_PREFIX. "plugin_settings` AS ps
			   ON ps.instance_id = np.instance_id
			  AND ps.egg_id = np.id
			  AND ps.name = '". ze\escape::sql($oldPluginSettingName). "'
			SET ps.name = '". ze\escape::sql($newPluginSettingName). "'
			". $extraSetSQL. "
			WHERE m.class_name IN (". ze\escape::in($moduleNames, 'asciiInSQL'). ")
			". $extraWhereSQL;
		ze\sql::update($sql);
	}
}


function uninstalledRemovedModule($moduleName) {
	if (ze\module::isRunning($moduleName)) {
		$moduleId = ze\module::id($moduleName);
		ze\moduleAdm::uninstall($moduleId, $uninstallRunningModules = true, $checkForDependenciesBeforeUninstalling = false);
	}
}






//Rename a plugin setting used by slideshows
if (ze\dbAdm::needRevision(53600)) {
	renamePluginSetting(['zenario_slideshow', 'zenario_slideshow_simple'], 'mode', 'animation_library', $checkNonNestedPlugins = true, $checkNestedPlugins = true);
	
	ze\dbAdm::revision(53600);
}


//Rename a *lot* of inconsistent plugin settings
if (ze\dbAdm::needRevision(55900)) {
	renamePluginSetting(['zenario_content_list'], 'author_canvas', 'image_canvas', true, true);
	renamePluginSetting(['zenario_content_list'], 'author_width', 'image_width', true, true);
	renamePluginSetting(['zenario_content_list'], 'author_height', 'image_height', true, true);
	renamePluginSetting(['zenario_content_list'], 'author_retina', 'image_retina', true, true);

	renamePluginSetting(['zenario_user_profile_search'], 'photo_list_canvas', 'image_canvas', true, true);
	renamePluginSetting(['zenario_user_profile_search'], 'photo_list_width', 'image_width', true, true);
	renamePluginSetting(['zenario_user_profile_search'], 'photo_list_height', 'image_height', true, true);
	renamePluginSetting(['zenario_user_profile_search'], 'photo_list_retina', 'image_retina', true, true);

	renamePluginSetting(['zenario_location_map_and_listing', 'zenario_location_map_and_listing_2'], 'list_view_thumbnail_canvas', 'image_canvas', true, true);
	renamePluginSetting(['zenario_location_map_and_listing', 'zenario_location_map_and_listing_2'], 'list_view_thumbnail_width', 'image_width', true, true);
	renamePluginSetting(['zenario_location_map_and_listing', 'zenario_location_map_and_listing_2'], 'list_view_thumbnail_height', 'image_height', true, true);
	renamePluginSetting(['zenario_location_map_and_listing', 'zenario_location_map_and_listing_2'], 'list_view_thumbnail_retina', 'image_retina', true, true);

	renamePluginSetting(['zenario_event_slideshow'], 'slide_canvas', 'image_canvas', true, true);
	renamePluginSetting(['zenario_event_slideshow'], 'slide_width', 'image_width', true, true);
	renamePluginSetting(['zenario_event_slideshow'], 'slide_height', 'image_height', true, true);
	renamePluginSetting(['zenario_event_slideshow'], 'slide_retina', 'image_retina', true, true);

	renamePluginSetting(['zenario_conference_fea'], 'thumbnail_canvas', 'image_canvas', true, true);
	renamePluginSetting(['zenario_conference_fea'], 'thumbnail_width', 'image_width', true, true);
	renamePluginSetting(['zenario_conference_fea'], 'thumbnail_height', 'image_height', true, true);
	renamePluginSetting(['zenario_conference_fea'], 'thumbnail_retina', 'image_retina', true, true);

	renamePluginSetting(['zenario_ctype_document'], 'sticky_image_canvas', 'image_canvas', true, true);
	renamePluginSetting(['zenario_ctype_document'], 'sticky_image_width', 'image_width', true, true);
	renamePluginSetting(['zenario_ctype_document'], 'sticky_image_height', 'image_height', true, true);
	renamePluginSetting(['zenario_ctype_document'], 'sticky_image_retina', 'image_retina', true, true);

	renamePluginSetting(['zenario_meta_data'], 'sticky_image_canvas', 'image_2_canvas', true, true);
	renamePluginSetting(['zenario_meta_data'], 'sticky_image_width', 'image_2_width', true, true);
	renamePluginSetting(['zenario_meta_data'], 'sticky_image_height', 'image_2_height', true, true);
	renamePluginSetting(['zenario_meta_data'], 'sticky_image_retina', 'image_2_retina', true, true);

	renamePluginSetting(['zenario_user_profile_search'], 'photo_popup_canvas', 'image_2_canvas', true, true);
	renamePluginSetting(['zenario_user_profile_search'], 'photo_popup_width', 'image_2_width', true, true);
	renamePluginSetting(['zenario_user_profile_search'], 'photo_popup_height', 'image_2_height', true, true);
	renamePluginSetting(['zenario_user_profile_search'], 'photo_popup_retina', 'image_2_retina', true, true);

	renamePluginSetting(['zenario_location_map_and_listing'], 'map_view_thumbnail_canvas', 'image_2_canvas', true, true);
	renamePluginSetting(['zenario_location_map_and_listing'], 'map_view_thumbnail_width', 'image_2_width', true, true);
	renamePluginSetting(['zenario_location_map_and_listing'], 'map_view_thumbnail_height', 'image_2_height', true, true);
	renamePluginSetting(['zenario_location_map_and_listing'], 'map_view_thumbnail_retina', 'image_2_retina', true, true);
	
	renamePluginSetting(['zenario_content_list', 'zenario_event_listing', 'zenario_location_listing', 'zenario_blog_news_list', 'zenario_event_calendar', 'zenario_forum_list'], 'show_sticky_images', 'show_featured_image', true, true);
	renamePluginSetting(['zenario_meta_data'], 'show_sticky_image', 'show_featured_image', true, true);
	
	renamePluginSetting(['zenario_meta_data'], 'show_feature_image_fallback', 'fall_back_to_default_image', true, true);
	renamePluginSetting(['zenario_meta_data'], 'feature_image_fallback', 'default_image_id', true, true);
	
	
	ze\dbAdm::revision(55900);
}


//Migrate the "nest type" and "slideshow type" plugin settings from being
//stored as indiviudal checkboxes, to being stored as an enum.
if (ze\dbAdm::needRevision(56290)) {
	
	$sql = "
		SELECT
			m.class_name,
			pi.id AS instanceId,
			psTabs.value AS show_tabs,
			psButtons.value AS show_next_prev_buttons,
			psConductor.value AS enable_conductor
		FROM `". DB_PREFIX. "modules` AS m
		INNER JOIN `". DB_PREFIX. "plugin_instances` AS pi
		   ON pi.module_id = m.id
		LEFT JOIN `". DB_PREFIX. "plugin_settings` AS psTabs
		   ON psTabs.instance_id = pi.id
		  AND psTabs.egg_id = 0
		  AND psTabs.name = 'show_tabs'
		LEFT JOIN `". DB_PREFIX. "plugin_settings` AS psButtons
		   ON psButtons.instance_id = pi.id
		  AND psButtons.egg_id = 0
		  AND psButtons.name = 'show_next_prev_buttons'
		LEFT JOIN `". DB_PREFIX. "plugin_settings` AS psConductor
		   ON psConductor.instance_id = pi.id
		  AND psConductor.egg_id = 0
		  AND psConductor.name = 'enable_conductor'
		WHERE m.class_name IN ('zenario_plugin_nest', 'zenario_slideshow', 'zenario_slideshow_simple')";
	
	foreach (ze\sql::fetchAssocs($sql) as $nest) {
		
		$isNest = $nest['class_name'] == 'zenario_plugin_nest';
		
		//Check what was selected for the existing options.
		//Attempt to migrate to the most sensible option in the enum.
		if ($isNest) {
			if (!empty($nest['enable_conductor']) && $isNest) {
				$nestType = 'conductor';
		
			} elseif (!empty($nest['show_tabs'])) {
				if (!empty($nest['show_next_prev_buttons'])) {
					$nestType = 'tabs_and_buttons';
				} else {
					$nestType = 'tabs';
				}
		
			} else {
				if (!empty($nest['show_next_prev_buttons'])) {
					$nestType = 'buttons';
				} else {
					$nestType = 'permission';
				}
			}
		} else {
			if (!empty($nest['show_tabs'])) {
				if (!empty($nest['show_next_prev_buttons'])) {
					$nestType = 'indicator_and_buttons';
				} else {
					$nestType = 'indicator';
				}
		
			} else {
				if (!empty($nest['show_next_prev_buttons'])) {
					$nestType = 'buttons';
				} else {
					$nestType = 'permission';
				}
			}
		}
		
		ze\row::set('plugin_settings', ['value' => $nestType], [
			'instance_id' => $nest['instanceId'],
			'egg_id' => 0,
			'name' => 'nest_type'
		]);
	}
	
	ze\dbAdm::revision(56290);
}


//Change the format of the "enlarge image" plugin settings in nests and slideshow to be a select list rather than a checkbox,
//to be consistent with everything else
if (ze\dbAdm::needRevision(57100)) {
	renamePluginSetting(['zenario_plugin_nest', 'zenario_slideshow', 'zenario_slideshow_simple'], 'enlarge_image', 'link_type', true, false,
		$extraSetSQL = ", ps.value = '_ENLARGE_IMAGE'",
		$extraWhereSQL = "AND ps.value IS NOT NULL AND ps.value"
	);
	
	
	ze\dbAdm::revision(57100);
}


//Remove the old jQuery Cycle plugin.
//Anything that had it selected should now use Cycle 2
if (ze\dbAdm::needRevision(57130)) {
	renamePluginSetting(['zenario_slideshow', 'zenario_slideshow_simple'], 'animation_library', 'animation_library', true, false,
		$extraSetSQL = ", ps.value = 'cycle2'",
		$extraWhereSQL = "AND ps.value = 'cycle'"
	);
	
	
	ze\dbAdm::revision(57130);
}

//Rename Advanced Search settings to have more sensible code names.
if (ze\dbAdm::needRevision(57981)) {
	if (ze\module::inc('zenario_advanced_search')) {
		renamePluginSetting(['zenario_advanced_search'], 'show_private_items', 'search_private_items', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'hide_private_items', 'show_private_content_item_link_control', true, true);
	}
	
	ze\dbAdm::revision(57981);
}

//Tidy up some Meta Data plugin settings:
//The setting to show the language ISO code needs to be renamed.
//Once it's renamed, a new checkbox needs to be checked, depending on
//whether the plugin is set to display the language name and/or ISO code.
if (ze\dbAdm::needRevision(58751)) {
	if (ze\module::inc('zenario_meta_data')) {
		renamePluginSetting(['zenario_meta_data'], 'show_language', 'show_language_iso_code', true, true);
		
		$instances = ze\module::getModuleInstancesAndPluginSettings('zenario_meta_data');
		
		foreach ($instances as $instance) {
			if (!empty($instance['settings']['show_language_name']) || $instance['settings']['show_language_iso_code']) {
				ze\row::set('plugin_settings', ['value' => 1], ['instance_id' => (int)$instance['instance_id'], 'egg_id' => (int)$instance['egg_id'], 'name' => 'show_language']);
				
				//Also make sure the field order setting references the new name
				if (!empty($instance['settings']['reorder_fields'])) {
					$fieldsInOrderBeforeRenaming = explode(',', $instance['settings']['reorder_fields']);
					
					$fieldsInOrder = [];
					foreach ($fieldsInOrderBeforeRenaming as $field) {
						if ($field == 'show_language') {
							$fieldsInOrder[] = 'show_language_iso_code';
						} else {
							$fieldsInOrder[] = $field;
						}
					}
					
					$fieldsInOrder = implode(',', $fieldsInOrder);
					
					ze\row::set('plugin_settings', ['value' => ze\escape::sql($fieldsInOrder)], ['instance_id' => (int)$instance['instance_id'], 'egg_id' => (int)$instance['egg_id'], 'name' => 'reorder_fields']);
				}
			}
		}
	}
	
	ze\dbAdm::revision(58751);
}

//Rename the scope for Users FEA
if (ze\dbAdm::needRevision(59040)) {
	if (ze\module::inc('zenario_users_fea')) {
		$instances = ze\module::getModuleInstancesAndPluginSettings('zenario_users_fea');
		
		foreach ($instances as $instance) {
			if (!empty($instance['settings']['scope']) && $instance['settings']['scope'] == 'user_supervisory_smart_groups') {
				ze\row::set('plugin_settings', ['value' => 'user_supervised_smart_groups'], ['instance_id' => (int)$instance['instance_id'], 'egg_id' => (int)$instance['egg_id'], 'name' => 'scope']);
			}
		}
	}
	
	ze\dbAdm::revision(59040);
}

//In 9.7, the Error Log module was integrated into Common Features.
//Uninstall the leftovers.
if (ze\dbAdm::needRevision(60010)) {
	$errorLogModuleId = ze\module::id('zenario_error_log');
	
	if ($errorLogModuleId) {
		ze\moduleAdm::uninstall($errorLogModuleId, $uninstallRunningModules = true);
	}
	
	ze\dbAdm::revision(60010);
}

//In 9.7, Document Container canvas options were updated
//to match Content Summary List. "Resize and crop" was replaced with "Crop and zoom".
if (ze\dbAdm::needRevision(60113)) {
	$instances = ze\module::getModuleInstancesAndPluginSettings('zenario_document_container');
	
	foreach ($instances as $instance) {
		if (!empty($instance['settings']['canvas']) && $instance['settings']['canvas'] == 'resize_and_crop') {
			ze\row::set('plugin_settings', ['value' => 'crop_and_zoom'], ['instance_id' => (int) $instance['instance_id'], 'egg_id' => (int) $instance['egg_id'], 'name' => 'canvas']);
		}
	}
	
	ze\dbAdm::revision(60113);
}

//In 10.0, Document content items plugins also had their settings updated:
//"Resize and crop" was replaced with "Crop and zoom".
//Also an unnecessary 2nd level checkbox was removed.
if (ze\dbAdm::needRevision(60665)) {
	$instances = ze\module::getModuleInstancesAndPluginSettings('zenario_ctype_document');
	
	foreach ($instances as $instance) {
		if (!empty($instance['settings']['image_canvas']) && $instance['settings']['image_canvas'] == 'resize_and_crop') {
			ze\row::set('plugin_settings', ['value' => 'crop_and_zoom'], ['instance_id' => (int) $instance['instance_id'], 'egg_id' => (int) $instance['egg_id'], 'name' => 'image_canvas']);
		}
		
		if (!empty($instance['settings']['use_sticky_image'])) {
			ze\row::delete('plugin_settings', ['instance_id' => (int) $instance['instance_id'], 'egg_id' => (int) $instance['egg_id'], 'name' => 'use_sticky_image']);
		}
	}
	
	ze\dbAdm::revision(60665);
}

if (ze\dbAdm::needRevision(60666)) {
	if (ze\module::inc('zenario_ctype_document')) {
		renamePluginSetting(['zenario_ctype_document'], 'show_default_stick_image', 'show_featured_image', true, true);
	}
	
	ze\dbAdm::revision(60666);
}

if (ze\dbAdm::needRevision(60670)) {
	if (ze\module::inc('zenario_advanced_search')) {
		renamePluginSetting(['zenario_advanced_search'], 'html_show_feature_image', 'html_show_featured_image', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'html_feature_image_canvas', 'html_canvas', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'html_feature_image_width', 'html_width', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'html_feature_image_height', 'html_height', true, true);
		
		renamePluginSetting(['zenario_advanced_search'], 'document_show_feature_image', 'document_show_featured_image', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'document_feature_image_canvas', 'document_canvas', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'document_feature_image_width', 'document_width', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'document_feature_image_height', 'document_height', true, true);
		
		renamePluginSetting(['zenario_advanced_search'], 'news_show_feature_image', 'news_show_featured_image', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'news_feature_image_canvas', 'news_canvas', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'news_feature_image_width', 'news_width', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'news_feature_image_height', 'news_height', true, true);
		
		renamePluginSetting(['zenario_advanced_search'], 'blog_show_feature_image', 'blog_show_featured_image', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'blog_feature_image_canvas', 'blog_canvas', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'blog_feature_image_width', 'blog_width', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'blog_feature_image_height', 'blog_height', true, true);
		
		renamePluginSetting(['zenario_advanced_search'], 'other_module_image_canvas', 'canvas', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'other_module_retina', 'retina', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'other_module_image_width', 'width', true, true);
		renamePluginSetting(['zenario_advanced_search'], 'other_module_image_height', 'height', true, true);
	}
	
	ze\dbAdm::revision(60670);
}

if (ze\dbAdm::needRevision(60671)) {
	if (ze\module::inc('zenario_ai_qdrant_search')) {
		renamePluginSetting(['zenario_ai_qdrant_search'], 'html_show_feature_image', 'html_show_featured_image', true, true);
		renamePluginSetting(['zenario_ai_qdrant_search'], 'html_feature_image_canvas', 'html_canvas', true, true);
		renamePluginSetting(['zenario_ai_qdrant_search'], 'html_feature_image_width', 'html_width', true, true);
		renamePluginSetting(['zenario_ai_qdrant_search'], 'html_feature_image_height', 'html_height', true, true);
		
		renamePluginSetting(['zenario_ai_qdrant_search'], 'document_show_feature_image', 'document_show_featured_image', true, true);
		renamePluginSetting(['zenario_ai_qdrant_search'], 'document_feature_image_canvas', 'document_canvas', true, true);
		renamePluginSetting(['zenario_ai_qdrant_search'], 'document_feature_image_width', 'document_width', true, true);
		renamePluginSetting(['zenario_ai_qdrant_search'], 'document_feature_image_height', 'document_height', true, true);
		
		renamePluginSetting(['zenario_ai_qdrant_search'], 'news_show_feature_image', 'news_show_featured_image', true, true);
		renamePluginSetting(['zenario_ai_qdrant_search'], 'news_feature_image_canvas', 'news_canvas', true, true);
		renamePluginSetting(['zenario_ai_qdrant_search'], 'news_feature_image_width', 'news_width', true, true);
		renamePluginSetting(['zenario_ai_qdrant_search'], 'news_feature_image_height', 'news_height', true, true);
		
		renamePluginSetting(['zenario_ai_qdrant_search'], 'blog_show_feature_image', 'blog_show_featured_image', true, true);
		renamePluginSetting(['zenario_ai_qdrant_search'], 'blog_feature_image_canvas', 'blog_canvas', true, true);
		renamePluginSetting(['zenario_ai_qdrant_search'], 'blog_feature_image_width', 'blog_width', true, true);
		renamePluginSetting(['zenario_ai_qdrant_search'], 'blog_feature_image_height', 'blog_height', true, true);
	}
	
	ze\dbAdm::revision(60671);
}






//
//	Zenario 10.1
//



//In 10.1, we implemented a new plugin setting for searching in other modules.
//A module may now advertise what data type it might search in.
//As of writing this DB update, there are 5 modules which allow Advanced Search to search in them.
//Each of these modules supports exactly 1 data type.
//Set a value for existing plugins.

if (ze\dbAdm::needRevision(61220)) {
	if (ze\module::inc('zenario_advanced_search')) {
		$instances = ze\module::getModuleInstancesAndPluginSettings('zenario_advanced_search');
		
		foreach ($instances as $instance) {
			if (!empty($instance['settings']['search_in_other_modules']) && !empty($instance['settings']['module_to_search'])) {
				if (empty($instance['settings']['searchable_data_type'])) {
					
					switch ($instance['settings']['module_to_search']) {
						case 'zenario_conference_manager':
							$searchableDataType = 'abstracts';
							break;
						case 'zenario_ecommerce_document':
							$searchableDataType = 'documents';
							break;
						case 'zenario_ecommerce_physical_products':
							$searchableDataType = 'physical_products';
							break;
						case 'zenario_location_manager':
							$searchableDataType = 'locations';
							break;
						case 'zenario_videos_manager':
							$searchableDataType = 'videos';
							break;
					}
					
					ze\row::set('plugin_settings', ['value' => $searchableDataType], ['instance_id' => (int)$instance['instance_id'], 'egg_id' => (int)$instance['egg_id'], 'name' => 'searchable_data_type']);
				}
			}
		}
	}
	
	ze\dbAdm::revision(61220);
}

//In 10.1, the Email Template Manager module was moved into Common Features.
//Uninitialise ETM if it was running before.
if (ze\dbAdm::needRevision(61240)) {
	uninstalledRemovedModule('zenario_email_template_manager');
	
	ze\dbAdm::revision(61240);
}


//Also in 10.1, the ctype picture module has been scrapped and should be uninstalled
if (ze\dbAdm::needRevision(61400)) {
	uninstalledRemovedModule('zenario_ctype_picture');
	
	ze\dbAdm::revision(61400);
}


//In 10.1, we've renamed/restructured the various nest and slideshow plugins.
//We now have:
	//zenario_nest (formerly the advanced slideshow), which is now more focused on outputting the plugins on the page and less on animating them
	//zenario_slideshow (formerly the simple slideshow), no change in functionality, just making it clear that this is the one that's focused on slideshows
	//zenario_ajax_nest (formerly just the plguin nest), no change in functionality, just making it clear that this uses AJAX reloads.
if (ze\dbAdm::needRevision(61450)) {
	
	//Skip this step for a fresh install. Only run this for sites that have had the old modules before.
	if (ze\module::id('zenario_plugin_nest')
	 || ze\module::id('zenario_slideshow_simple')) {
		
		renameModuleDirectory('zenario_slideshow', 'zenario_nest', $movePlugins = true, $moveEditableCSS = false, $movePhrases = true, $uninstallOldModule = false);
		renameModuleDirectory('zenario_slideshow_simple', 'zenario_slideshow', $movePlugins = true, $moveEditableCSS = false, $movePhrases = true, $uninstallOldModule = true);
		renameModuleDirectory('zenario_plugin_nest', 'zenario_ajax_nest', $movePlugins = true, $moveEditableCSS = false, $movePhrases = true, $uninstallOldModule = true);
		
		//What was the old advanced slideshow plugin is now classed as a nest not a slideshow.
		//Update any cached flags for its plugins.
		$nestModuleId = ze\module::id('zenario_nest');
		ze\row::update('plugin_instances',
			['is_nest' => 1, 'is_slideshow' => 0],
			['module_id' => $nestModuleId]
		);
	}
	
	ze\dbAdm::revision(61450);
}

//In 10.1, the document envelope thumbnails were moved to their own pot.
//Migrate the data for existing thumbnails.
if (ze\dbAdm::needRevision(61610)) {
	if (ze\module::inc('zenario_document_envelopes_fea')) {
		$sql = "
			SELECT GROUP_CONCAT(DISTINCT de.thumbnail_id SEPARATOR ',')
			FROM " . DB_PREFIX . ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . "document_envelopes de
			WHERE de.thumbnail_id > 0";
		$result = ze\sql::select($sql);
		$thumbnailIds = ze\sql::fetchValue($result);
		
		if ($thumbnailIds) {
			$sql2 = "
				UPDATE " . DB_PREFIX . "files
				SET `usage` = 'document_envelope_thumbnail'
				WHERE id IN(" . ze\escape::in($thumbnailIds) . ")";
			ze\sql::update($sql2);
		}
	}
	
	ze\dbAdm::revision(61610);
}

//Also tidy up an old pot name.
if (ze\dbAdm::needRevision(61615)) {
	if (ze\module::inc('zenario_document_envelopes_fea')) {
		$sql = "
			UPDATE " . DB_PREFIX . "files
			SET `usage` = 'document_envelope_thumbnail'
			WHERE `usage` = 'document_in_envelope_thumbnail'";
		ze\sql::update($sql);
	}
	
	ze\dbAdm::revision(61615);
}

if (ze\dbAdm::needRevision(61940)) {
	
	if (ze\module::inc('zenario_user_timers_list')) {
		
		renamePluginSetting(['zenario_user_timers_list'], 'allow_renew', 'show_warning_about_expiring_timer');
		renamePluginSetting(['zenario_user_timers_list'], 'allow_renew_when', 'show_expiring_timer_warning_when');
	}
	
	ze\dbAdm::revision(61940);
}





//
//	Zenario 10.3
//



//In 10.3, a few settings in Meta Data were renamed.
if (ze\dbAdm::needRevision(63400)) {
	
	if (ze\module::inc('zenario_meta_data')) {
		
		renamePluginSetting(['zenario_meta_data'], 'show_date', 'show_release_date');
		renamePluginSetting(['zenario_meta_data'], 'date_format', 'release_date_format');
		renamePluginSetting(['zenario_meta_data'], 'date_html_tag', 'release_date_html_tag');
		
		$instances = ze\module::getModuleInstancesAndPluginSettings('zenario_meta_data');
		
		//Also make sure the field order uses the new name for a setting.
		foreach ($instances as $instance) {
			if (!empty($instance['settings']['reorder_fields'])) {
				$currentOrder = explode(',', $instance['settings']['reorder_fields']);
				
				if (in_array('show_date', $currentOrder)) {
					$newOrder = [];
					
					foreach ($currentOrder as $field) {
						if ($field == 'show_date') {
							$newOrder[] = 'show_release_date';
						} else {
							$newOrder[] = $field;
						}
					}
					
					$newOrder = implode(',', $newOrder);
					
					ze\pluginAdm::setSetting('reorder_fields', $newOrder, $instance['instance_id'], $instance['egg_id']);
				}
			}
		}
	}
	
	ze\dbAdm::revision(63400);
}

if (ze\dbAdm::needRevision(63405)) {
	
	if (ze\module::inc('zenario_ctype_document')) {
		
		renamePluginSetting(['zenario_ctype_document'], 'date_format', 'release_date_format');
		renamePluginSetting(['zenario_ctype_document'], 'show_time', 'show_release_time');
	}
	
	ze\dbAdm::revision(63405);
}

if (ze\dbAdm::needRevision(63435)) {
	$listOfModules = [
		'zenario_banner',
		'zenario_storefront_banner',
		'zenario_document_shortlist_banner',
		'zenario_content_list',
		'zenario_blog_news_list',
		'zenario_forum_list',
		'zenario_job_vacancy_summary_list',
		'zenario_comment_forum_subscriptions'
	];
	
	ze\pluginAdm::deleteSettingFromModules($listOfModules, 'translate_text');
	
	ze\dbAdm::revision(63435);
}

if (ze\dbAdm::needRevision(63605)) {
	$listOfModules = [
		'zenario_ctype_document'
	];
	
	$settingsToDelete = [
		'show_view_link',
		'local_file',
		'links_should'
	];
	
	foreach ($settingsToDelete as $settingToDelete) {
		ze\pluginAdm::deleteSettingFromModules($listOfModules, $settingToDelete);
	}
	
	ze\dbAdm::revision(63605);
}

if (ze\dbAdm::needRevision(63750)) {
	$settingsToDelete = [
		'enable_categories',
		'document_use_download_page'
	];
	
	foreach ($settingsToDelete as $settingToDelete) {
		ze\pluginAdm::deleteSettingFromModules('zenario_advanced_search', $settingToDelete);
	}
	
	ze\dbAdm::revision(63750);
}

if (ze\dbAdm::needRevision(63755)) {
	$settingsToDelete = [
		'html_limit_search_scope_by_category',
		'document_limit_search_scope_by_category',
		'news_limit_search_scope_by_category',
		'blog_limit_search_scope_by_category',
		'project_limit_search_scope_by_category',
		'html_limit_search_scope_choose_categories',
		'document_limit_search_scope_choose_categories',
		'news_limit_search_scope_choose_categories',
		'blog_limit_search_scope_choose_categories',
		'project_limit_search_scope_choose_categories'
	];
	
	foreach ($settingsToDelete as $settingToDelete) {
		ze\pluginAdm::deleteSettingFromModules('zenario_advanced_search', $settingToDelete);
	}
	
	ze\dbAdm::revision(63755);
}

if (ze\dbAdm::needRevision(64115)) {
	$settingsToDelete = [
		'show_phone',
		'show_fax',
		'show_website',
		'show_summary',
		'show_email'
	];
	
	foreach ($settingsToDelete as $settingToDelete) {
		ze\pluginAdm::deleteSettingFromModules('zenario_location_viewer', $settingToDelete);
	}
	
	ze\dbAdm::revision(64115);
}

//Previously, an indented textbox was not mandatory if visible.
//It is now as of 10.4. Fix any bad data that might have resulted from lack of validation.
//PLEASE NOTE: This was backpatched from 10.4 to 10.3, but is safe to run more than once.
if (ze\dbAdm::needRevision(64132)) {
	$instances = ze\module::getModuleInstancesAndPluginSettings('zenario_event_listing');
	
	foreach ($instances as $instance) {
		if (
			!empty($instance['settings']['heading'])
			&& $instance['settings']['heading'] == 'show_heading'
			&& empty($instance['settings']['heading_text'])
		) {
			ze\row::set('plugin_settings', ['value' => 'dont_show'], ['instance_id' => (int) $instance['instance_id'], 'egg_id' => (int) $instance['egg_id'], 'name' => 'heading']);
		}
	}
	
	ze\dbAdm::revision(64132);
}