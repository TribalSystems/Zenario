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



//Code for handling renaming Plugin directories
function renameModuleDirectory($oldName, $newName, $uninstallOldModule = false, $moveEditableCSS = false) {
	ze\moduleAdm::addNew($skipIfFilesystemHasNotChanged = false);
	
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
		 && is_dir($gtDir = CMS_ROOT. ze\content::templatePath('grid_templates'). '/skins/')) {
			
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
	}
}

//Code for one Module replacing functionality from another
function replaceModule($oldName, $newName) {
	ze\moduleAdm::addNew($skipIfFilesystemHasNotChanged = false);
	
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
	ze\moduleAdm::addNew($skipIfFilesystemHasNotChanged = false);
	
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
	ze\moduleAdm::addNew($skipIfFilesystemHasNotChanged = false);
	
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






//Replace the (removed) zenario_company_listing_fea module with the assetwolf_2 module
if (ze\dbAdm::needRevision(38823)) {
	
	renameModuleDirectory('zenario_company_listing_fea', 'assetwolf_2', true);
	
	ze\dbAdm::revision(38823);
}

//Remove the image container and convert any existing image containers to banners
if (ze\dbAdm::needRevision(39790)) {
	
	renameModuleDirectory('zenario_image_container', 'zenario_banner', true, true);
	
	ze\dbAdm::revision(39790);
}

//Assetwolf 2 now needs the locations modules also running
if (ze\dbAdm::needRevision(40100)) {
	runNewModuleDependency('assetwolf_2', 'zenario_location_manager');
	runNewModuleDependency('assetwolf_2', 'zenario_company_locations_manager');
	ze\dbAdm::revision(40100);
}

//Replace the (removed) zenario_menu_responsive_multilevel module with the zenario_menu_responsive_multilevel_2 module
if (ze\dbAdm::needRevision(41550)) {
	
	renameModuleDirectory('zenario_menu_responsive_multilevel', 'zenario_menu_responsive_multilevel_2', true);
	
	ze\dbAdm::revision(41550);
}

//Replace the (removed) zenario_extranet_password_reminder module with the zenario_extranet_password_reset module
if (ze\dbAdm::needRevision(41700)) {
	
	renameModuleDirectory('zenario_extranet_password_reminder', 'zenario_extranet_password_reset', true);
	
	ze\dbAdm::revision(41700);
}

//Replace Assetwolf companies mode plugins with new companies FEA plugins
if (ze\dbAdm::needRevision(41744)) {
	//Replace module plugins with matching mode
	$modes = ['list_companies', 'create_company', 'edit_company', 'delete_company', 'view_company_details'];
	$rv = replaceModulePlugins('assetwolf_2', 'zenario_companies_fea', 'mode', $modes);

	//Change setting names
	foreach ([
		'enable.show_edit_company_button_from_details' => 'enable.edit_company', 
		'enable.show_delete_company_button_from_details' => 'enable.delete_company'
	] as $oldName => $newName) {
		$sql = '
			SELECT instance_id, name, egg_id, value
			FROM ' . DB_PREFIX . 'plugin_settings
			WHERE name = "' . ze\escape::sql($oldName) . '"';
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$value = $row['value'];
			unset($row['value']);
			$check = $row;
			$check['name'] = $newName;
			if (!ze\row::exists('plugin_settings', $check)) {
				ze\row::update('plugin_settings', ['name' => $newName], $row);
			} else {
				ze\row::update('plugin_settings', ['value' => $value], $check);
			}
		}
	}

	//Migrate setting phrases
	foreach ($modes as $mode) {
		$oldPathName = 'assetwolf_' . $mode;
		$sql = '
			SELECT instance_id, name, egg_id
			FROM ' . DB_PREFIX . 'plugin_settings
			WHERE name LIKE "phrase.' . ze\escape::sql($oldPathName) . '%"';
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$newName = str_replace($oldPathName, 'zenario_' . $mode, $row['name']);
			ze\row::update('plugin_settings', ['name' => $newName], $row);
		}
	}
	
	ze\dbAdm::revision(41744);
}

//The zenario_translation_tools module is gone in verion 8 (it's been merged into the core).
if (ze\dbAdm::needRevision(41920)) {
	renameModuleDirectory('zenario_translation_tools', false, true);
	ze\dbAdm::revision(41920);
}

//The zenario_companies_fea modules now needs the zenario_organization_manager running
if (ze\dbAdm::needRevision(43630)) {
	runNewModuleDependency('zenario_companies_fea', 'zenario_organization_manager');
	ze\dbAdm::revision(43630);
}

//The zenario_storefront_physical_products_fea and zenario_storefront_documents_fea modules have been
//merged into a single module: zenario_storefront_products_fea
//so migrate their plugin settings then replace the modules.
if (ze\dbAdm::needRevision(45191)) {
	
	//Update plugin settings for zenario_storefront_physical_products_fea
	$sql = '
		SELECT id AS egg_id, instance_id
		FROM ' . DB_PREFIX . 'nested_plugins
		WHERE module_id = (
			SELECT id
			FROM ' . DB_PREFIX . 'modules
			WHERE class_name = "zenario_storefront_physical_products_fea"
		)';
	$result = ze\sql::select($sql);
	while ($plugin = ze\sql::fetchAssoc($result)) {
		$settings = [];
		$sql = '
			SELECT name, value
			FROM ' . DB_PREFIX . 'plugin_settings
			WHERE instance_id = ' . (int)$plugin['instance_id'] . '
			AND egg_id = ' . (int)$plugin['egg_id'];
		$result2 = ze\sql::select($sql);
		while ($setting = ze\sql::fetchAssoc($result2)) {
			$settings[$setting['name']] = $setting['value'];
		}
	
		$vals = [];
		$keys = ['instance_id' => $plugin['instance_id'], 'egg_id' => $plugin['egg_id']];
	
		$keys['name'] = 'product_type';
		$vals = ['value' => 'physical_product'];
		ze\row::set('plugin_settings', $vals, $keys);
	
		$keys['name'] = 'enable.column__thumbnail_image';
		$vals = ['name' => 'list_products__column__thumbnail_image'];
		ze\row::update('plugin_settings', $vals, $keys);
	
		$keys['name'] = 'enable.add_product_to_basket';
		$vals = ['name' => 'enable.buy_product'];
		ze\row::update('plugin_settings', $vals, $keys);
	
		$keys['name'] = 'price_sales_tax';
		$vals = ['value' => 'inc_tax'];
		ze\row::set('plugin_settings', $vals, $keys);
	
		if ($settings['mode'] == 'list_products') {
			$keys['name'] = 'enable.buy_product';
			$vals = ['value' => '1'];
			ze\row::set('plugin_settings', $vals, $keys);
		}
	}
	renameModuleDirectory('zenario_storefront_physical_products_fea', 'zenario_storefront_products_fea', true);
	
	
	//Update plugin settings for zenario_storefront_documents_fea
	$sql = '
		SELECT id AS egg_id, instance_id
		FROM ' . DB_PREFIX . 'nested_plugins
		WHERE module_id = (
			SELECT id
			FROM ' . DB_PREFIX . 'modules
			WHERE class_name = "zenario_storefront_documents_fea"
		)';
	$result = ze\sql::select($sql);
	while ($plugin = ze\sql::fetchAssoc($result)) {
		$settings = [];
		$sql = '
			SELECT name, value
			FROM ' . DB_PREFIX . 'plugin_settings
			WHERE instance_id = ' . (int)$plugin['instance_id'] . '
			AND egg_id = ' . (int)$plugin['egg_id'];
		$result2 = ze\sql::select($sql);
		while ($setting = ze\sql::fetchAssoc($result2)) {
			$settings[$setting['name']] = $setting['value'];
		}
	
		$vals = [];
		$keys = ['instance_id' => $plugin['instance_id'], 'egg_id' => $plugin['egg_id']];
	
		$keys['name'] = 'product_type';
		$vals = ['value' => 'document'];
		ze\row::set('plugin_settings', $vals, $keys);
	
		if ($settings['mode'] == 'list_storefront_documents') {
			$keys['name'] = 'mode';
			$vals = ['value' => 'list_products'];
			ze\row::set('plugin_settings', $vals, $keys);
		} elseif ($settings['mode'] == 'view_storefront_document') {
			$keys['name'] = 'mode';
			$vals = ['value' => 'view_product'];
			ze\row::set('plugin_settings', $vals, $keys);
		
			$keys['name'] = 'show_title';
			$vals = ['value' => '1'];
			ze\row::set('plugin_settings', $vals, $keys);
		
			$keys['name'] = 'title_tags';
			$vals = ['value' => 'h2'];
			ze\row::set('plugin_settings', $vals, $keys);
		}
	
		$keys['name'] = 'enable.view_storefront_document';
		$vals = ['value' => 'enable.view_document'];
		ze\row::update('plugin_settings', $vals, $keys);
	
		$keys['name'] = 'enable.buy_storefront_document';
		$vals = ['value' => 'enable.buy_product'];
		ze\row::update('plugin_settings', $vals, $keys);
	
		$keys['name'] = 'list_products__column__thumbnail_image';
		$vals = ['value' => '0'];
		ze\row::set('plugin_settings', $vals, $keys);
	
	}
	renameModuleDirectory('zenario_storefront_documents_fea', 'zenario_storefront_products_fea', true);
	
	//Update nest paths
	$count = ze\sql::numRows(ze\sql::select('SHOW COLUMNS FROM ' . DB_PREFIX . 'nested_paths WHERE Field = "command"'));
	if ($count == 1) {
		$col = 'command';
	} else {
		$col = 'commands';
	}
	$sql = '
		UPDATE ' . DB_PREFIX . 'nested_paths
		SET `' . ze\escape::sql($col) . '` = "view_product"
		WHERE `' . ze\escape::sql($col) . '` = "view_storefront_document"';
	ze\sql::update($sql);
	
	ze\dbAdm::revision(45191);
}

//The zenario_salesforce_api_form_integration modules now needs the zenario_crm_form_integration running
if (ze\dbAdm::needRevision(45351)) {
	runNewModuleDependency('zenario_salesforce_api_form_integration', 'zenario_crm_form_integration');
	ze\dbAdm::revision(45351);
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
