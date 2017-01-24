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

$desc = false;
if (!loadModuleDescription($moduleClassName, $desc)
 || !$moduleId = getModuleId($moduleClassName)) {
	exit;
	
	return false;
}
//Update the modules table with the details
$category = (isset($desc['category']) && !empty($desc['category'])) ? ("'".sqlEscape($desc['category'])."'") : "NULL";
$sql = "
	UPDATE ". DB_NAME_PREFIX. "modules SET
		vlp_class = '". sqlEscape($desc['vlp_class_name']). "',
		display_name = '". sqlEscape($desc['display_name']). "',
		default_framework = '". sqlEscape($desc['default_framework']). "',
		css_class_name = '". sqlEscape($desc['css_class_name']). "',
		is_pluggable = ". engToBoolean($desc['is_pluggable']). ",
		fill_organizer_nav = ". engToBoolean($desc['fill_organizer_nav']). ",
		can_be_version_controlled = ". engToBoolean(engToBoolean($desc['is_pluggable'])? $desc['can_be_version_controlled'] : 0). ",
		for_use_in_twig = ". engToBoolean($desc['for_use_in_twig']). ",
		nestable = ". engToBoolean($desc['nestable']). ",
		category = ". $category . "
	WHERE id = '". (int) $moduleId. "'";
sqlQuery($sql);


//Remove any existing dependencies
$sql = "
	DELETE FROM ". DB_NAME_PREFIX. "module_dependencies
	WHERE module_id = '". (int) $moduleId. "'";
sqlQuery($sql);

//Look to see which dependencies this Module has
$dependencies = array(
	'dependency' => array(),
	'inherit_frameworks' => array(),
	'include_javascript' => array(),
	'inherit_settings' => array()
);

foreach (readModuleDependencies($moduleClassName, $desc) as $module) {
	$dependencies['dependency'][$module] = $module;
}



if (!empty($desc['inheritance']['inherit_frameworks_from_module'])) {
	$dep = $desc['inheritance']['inherit_frameworks_from_module'];
	$dependencies['inherit_frameworks'] = array($dep);
}
if (!empty($desc['inheritance']['include_javascript_from_module'])) {
	$dep = $desc['inheritance']['include_javascript_from_module'];
	$dependencies['include_javascript'] = array($dep);
}
if (!empty($desc['inheritance']['inherit_settings_from_module'])) {
	$dep = $desc['inheritance']['inherit_settings_from_module'];
	$dependencies['inherit_settings'] = array($dep);
}

//Record any dependencies found
foreach ($dependencies as $type => $modules) {
	foreach ($modules as $module) {
		if ($module && $module != $moduleClassName && $module != $moduleClassName) {
			$sql = "
				INSERT INTO ". DB_NAME_PREFIX. "module_dependencies SET
					module_id = '". (int) $moduleId. "',
					module_class_name = '". sqlEscape($moduleClassName). "',
					dependency_class_name = '". sqlEscape(trim($module)). "',
					`type` = '". sqlEscape(trim($type)). "'";
			sqlQuery($sql);
		}
	}
}


//Add any special pages that this module uses
$specialPageChanges = false;
if (!empty($desc['special_pages']) && is_array($desc['special_pages'])) {
	foreach($desc['special_pages'] as $page) {
		if (!empty($page['page_type'])) {
			
			//Choose one of the three rules
			$defaultLogic = 'create_and_maintain_in_default_language';
			$otherRules = array(
				'create_and_maintain_in_all_languages' => true,
				'create_in_default_language_on_install' => true);
			
			if (!empty($page['logic']) && !empty($otherRules[$page['logic']])) {
				$logic = $page['logic'];
			} else {
				$logic = $defaultLogic;
			}
			
			
			//Check if this special page already exists
			if (!$specialPage = getRow('special_pages', true, array('page_type' => $page['page_type']))) {
				$specialPageChanges = true;
				insertRow(
					'special_pages',
					array(
						'module_class_name' => $moduleClassName,
						'logic' => $logic,
						'publish' => engToBooleanArray($page, 'publish'),
						'page_type' => $page['page_type']));
			
			} elseif (!$specialPage['module_class_name'] || $specialPage['module_class_name'] == $moduleClassName) {
				$specialPageChanges = true;
				updateRow(
					'special_pages',
					array(
						'module_class_name' => $moduleClassName,
						'logic' => $logic,
						'publish' => engToBooleanArray($page, 'publish')),
					array(
						'page_type' => $page['page_type']));
			
			} elseif (!$specialPage['equiv_id']) {
				$specialPageChanges = true;
			}
		}
	}
}


//Remove any existing signals
$sql = "
	DELETE FROM ". DB_NAME_PREFIX. "signals
	WHERE module_id = '". (int) $moduleId. "'";
sqlQuery($sql);

//Record any signals listened for
if (!empty($desc['signals']) && is_array($desc['signals'])) {
	foreach($desc['signals'] as $signal) {
		if (!empty($signal['name'])) {
			$sql = "
				INSERT INTO ". DB_NAME_PREFIX. "signals SET
					signal_name = '". sqlEscape($signal['name']). "',
					module_id = '". (int) $moduleId. "',
					module_class_name = '". sqlEscape($moduleClassName). "',
					static_method = ". engToBooleanArray($signal, 'static'). ",
					suppresses_module_class_name = '". sqlEscape(arrayKey($signal, 'suppresses_module_class_name')). "'";
			sqlQuery($sql);
		}
	}
}


//Record any jobs the module has
$jobs = '';
if (!empty($desc['jobs']) && is_array($desc['jobs'])) {
	foreach($desc['jobs'] as $job) {
		if (!empty($job['name'])) {
			$jobs .= ($jobs? ',' : ''). "'". sqlEscape($job['name']). "'";
			$sql = "
				INSERT IGNORE INTO ". DB_NAME_PREFIX. "jobs SET
					manager_class_name = '". sqlEscape(ifNull(arrayKey($job, 'manager_class_name'), 'zenario_scheduled_task_manager')). "',
					job_name = '". sqlEscape($job['name']). "',
					module_id = '". (int) $moduleId. "',
					module_class_name = '". sqlEscape($moduleClassName). "',
					static_method = ". engToBooleanArray($job, 'static'). ",
					months = '". sqlEscape(arrayKey($job, 'months')). "',
					days = '". sqlEscape(arrayKey($job, 'days')). "',
					hours = '". sqlEscape(arrayKey($job, 'hours')). "',
					minutes = '". sqlEscape(arrayKey($job, 'minutes')). "',
					first_n_days_of_month = ". (int) arrayKey($job, 'first_n_days_of_month'). ",
					log_on_action = ". engToBooleanArray($job, 'log_on_action'). ",
					log_on_no_action = ". engToBooleanArray($job, 'log_on_no_action'). ",
					email_on_action = ". engToBooleanArray($job, 'email_on_action'). ",
					email_on_no_action = ". engToBooleanArray($job, 'email_on_no_action'). ",
					email_address_on_action = '". sqlEscape(EMAIL_ADDRESS_GLOBAL_SUPPORT). "',
					email_address_on_no_action = '". sqlEscape(EMAIL_ADDRESS_GLOBAL_SUPPORT). "',
					email_address_on_error = '". sqlEscape(EMAIL_ADDRESS_GLOBAL_SUPPORT). "'";
			sqlQuery($sql);
		}
	}
}

//Remove any unused jobs
$innerSql = "
	FROM ". DB_NAME_PREFIX. "jobs
	WHERE module_id = ". (int) $moduleId;

if ($jobs) {
	$innerSql .= "
	  AND job_name NOT IN (". $jobs. ")";
}

$sql = "
	DELETE FROM ". DB_NAME_PREFIX. "job_logs
	WHERE job_id IN (
		SELECT id
		". $innerSql. "
	)";

sqlQuery($sql);

$sql = "
	DELETE ". $innerSql;

sqlQuery($sql);


//Remove any existing centralised lists
deleteRow('centralised_lists', array('module_class_name' => $moduleClassName));

//Record any centralised lists the module has
if (!empty($desc['centralised_lists']) && is_array($desc['centralised_lists'])) {
	foreach($desc['centralised_lists'] as $centralised_list) {
		if (!empty($centralised_list['method_name']) && !empty($centralised_list['label'])) {
			setRow('centralised_lists', 
				array('label' => $centralised_list['label']), 
				array(
					'module_class_name' => $moduleClassName, 
					'method_name' => $centralised_list['method_name']
				)
			);
		}
	}
}


//Remove any existing settings
deleteRow('plugin_setting_defs', array('module_id' => $moduleId));
deleteRow('plugin_setting_defs', array('module_class_name' => $moduleClassName));

//Loop through every module Setting that a module has in its Admin Box XML file(s)
if ($dir = moduleDir($moduleClassName, 'tuix/admin_boxes/', true)) {
	foreach (array(
		'zenario_admin' => 'admin_setting',
		'plugin_settings' => 'plugin_setting',
		'site_settings' => 'site_setting'
	) as $path => $settingDef) {
		$tags = array();
		foreach (scandir(CMS_ROOT. $dir) as $file) {
			if (is_file(CMS_ROOT. $dir. $file) && substr($file, 0, 1) != '.') {
				//Attempt to open and read the XML for an Admin Boxes
				$compatibilityClassNames = array($moduleClassName => true);

				$tagsToParse = zenarioReadTUIXFile(CMS_ROOT. $dir. $file);
				zenarioParseTUIX($tags, $tagsToParse, 'admin_boxes', $moduleClassName, $settingGroup = true, $compatibilityClassNames, $path);
				unset($tagsToParse);
			}
		}
		
		if (!empty($tags)) {
			if (isset($tags['admin_boxes'][$path]['tabs']) && is_array($tags['admin_boxes'][$path]['tabs'])) {
				foreach($tags['admin_boxes'][$path]['tabs'] as &$tab) {
					if (isset($tab['fields']) && is_array($tab['fields'])) {
						foreach($tab['fields'] as &$field) {
							if (!empty($field[$settingDef]['name'])) {
								
								$value = '';
								if (isset($field[$settingDef]['value'])) {
									$value = $field[$settingDef]['value'];
								
								} elseif (isset($field['value'])) {
									$value = $field['value'];
								}
								
								switch ($settingDef) {
								
									case 'admin_setting':
										setRow('admin_setting_defaults',
											array('default_value' => $value),
											$field[$settingDef]['name']);
										break;
									
									case 'plugin_setting':
										insertRow('plugin_setting_defs',
											array('module_id' => $moduleId,
												  'module_class_name' => $moduleClassName,
												  'name' => $field[$settingDef]['name'],
												  'default_value' => $value));
										break;
								
									case 'site_setting':
										$sql = "
											INSERT INTO ". DB_NAME_PREFIX. "site_settings SET
												name = '". sqlEscape($field[$settingDef]['name']). "',
												value = NULL,
												default_value = '". sqlEscape((string) $value). "'
											ON DUPLICATE KEY UPDATE
												default_value = '". sqlEscape((string) $value). "'";
										sqlQuery($sql);
										break;
								}
							}
						}
					}
				}
			}
		}
	}
}

importPhrasesForModule($moduleClassName);


if ($specialPageChanges) {
	addNeededSpecialPages();
}


return true;