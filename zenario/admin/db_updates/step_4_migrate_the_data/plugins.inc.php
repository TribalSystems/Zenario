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


//Always run the ze\moduleAdm::addNew() function when doing any database updates,
//just to check for newly added modules.
ze\moduleAdm::addNew($skipIfFilesystemHasNotChanged = false);


//Code for handling renaming Plugin directories
function renameModuleDirectory($oldName, $newName, $uninstallOldModule = false, $moveEditableCSS = false) {
	$oldId = ze\module::id($oldName);
	
	if ($newName && $oldId && ($newId = ze\module::id($newName))) {
		foreach([
			'content_types', 'jobs', 'signals',
			'module_dependencies', 'plugin_setting_defs',
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
		
		if (ze::in($newStatus, 'module_not_initialized', 'module_suspended')) {
			ze\row::set('modules', ['status' => $oldStatus], $newId);
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






//Rename the Slideshow 2 module to the Slideshow (simple) module
if (ze\dbAdm::needRevision(47160)) {
	renameModuleDirectory('zenario_slideshow_2', 'zenario_slideshow_simple', true);
	
	//Also, the Slideshow (simple) module now has a dependancy on the base slideshow (which in turn has a dependancy on the nest module)
	runNewModuleDependency('zenario_slideshow_simple', 'zenario_plugin_nest');
	runNewModuleDependency('zenario_slideshow_simple', 'zenario_slideshow');
	ze\dbAdm::revision(47160);
}

//The location manager module now needs the timezones module to run
if (ze\dbAdm::needRevision(47200)) {
	runNewModuleDependency('zenario_location_manager', 'zenario_timezones');
	ze\dbAdm::revision(47200);
}

//The Assetwolf module now needs the Advanced interface tools FEA module to run
if (ze\dbAdm::needRevision(50500)) {
	runNewModuleDependency('assetwolf_2', 'zenario_advanced_interface_tools_fea');
	ze\dbAdm::revision(50500);
}


//Fix a bug where the "password reminder" page was not unflagged as a special page when then zenario_extranet_password_reminder module was replaced
if (ze\dbAdm::needRevision(50535)) {
	ze\row::delete('special_pages', ['module_class_name' => 'zenario_extranet_password_reminder']);
	ze\dbAdm::revision(50535);
}


//Migrate most of the extranet's special pages to plugin pages
if (ze\dbAdm::needRevision(50790)) {
	convertSpecialPageToPluginPage('zenario_change_email', 'zenario_extranet_change_email');
	convertSpecialPageToPluginPage('zenario_change_password', 'zenario_extranet_change_password');
	convertSpecialPageToPluginPage('zenario_logout', 'zenario_extranet_logout');
	convertSpecialPageToPluginPage('zenario_password_reset', 'zenario_extranet_password_reset');
	convertSpecialPageToPluginPage('zenario_profile', 'zenario_extranet_profile_edit');
	convertSpecialPageToPluginPage('zenario_registration', 'zenario_extranet_registration');
	
	ze\dbAdm::revision(50790);
}


//Migrate the search results special page to a plugin page
if (ze\dbAdm::needRevision(50795)) {
	convertSpecialPageToPluginPage('zenario_search', 'zenario_search_results');
	
	ze\dbAdm::revision(50795);
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

