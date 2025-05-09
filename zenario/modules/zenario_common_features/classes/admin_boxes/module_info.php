<?php
/*
 * Copyright (c) 2025, Tribal Limited
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


class zenario_common_features__admin_boxes__module_info extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if ((!$module = ze\module::details($box['key']['id']))) {
			exit;
		}
		
		$box['title'] = ze\admin::phrase(
			'Module details for "[[module_display_name]]" ([[module_class_name]])',
			['module_display_name' => $module['display_name'], 'module_class_name' => $module['class_name']]
		);

		$moduleStatusFormattedNicely = '';
		switch ($module['status']) {
			case 'module_suspended':
				$moduleStatusFormattedNicely = 'Suspended';
				break;
			case 'module_running':
				$moduleStatusFormattedNicely = 'Running';
				break;
			case 'module_not_initialized':
				$moduleStatusFormattedNicely = 'Uninitialised';
				break;
		}
		
		$desc = false;
		if (ze\moduleAdm::loadDescription($module['class_name'], $desc)) {
			$box['tabs']['module_info']['fields']['module_description_or_help']['snippet']['html'] = '<div class="zenario_fbInfo">';
			$box['tabs']['module_info']['fields']['module_description_or_help']['snippet']['html'] .= $desc['description'];
			$box['tabs']['module_info']['fields']['module_description_or_help']['snippet']['html'] .= '</div>';
			$box['tabs']['module_info']['fields']['module_description_or_help']['hidden'] = false;
			
			$values['module_info/name'] = $module['display_name'];
			$values['module_info/class_name'] = $module['class_name'];
			
			$path = ze::moduleDir($module['class_name'], 'module_code.php', true);
			$coreExtraOrCustom = '';
			$pathParts = explode('/', $path);
			if ($pathParts[0] == 'zenario' && $pathParts[1] == 'modules') {
				$coreExtraOrCustom = 'Core';
			} elseif ($pathParts[0] == 'zenario_extra_modules') {
				$coreExtraOrCustom = 'Extra';
			} elseif ($pathParts[0] == 'zenario_custom') {
				$coreExtraOrCustom = 'Custom';
			} else {
				$coreExtraOrCustom = "Unknown";
			}
			$coreExtraOrCustom .= ' module';
			$fields['module_info/class_name']['notices_below']['core_extra_or_custom_module']['message'] = ze\admin::phrase($coreExtraOrCustom);
			
			//Module full path
			$values['module_info/path'] = substr(CMS_ROOT. $path, 0, -15);
			$values['module_info/status'] = ze\admin::phrase($moduleStatusFormattedNicely);
			
			if (!empty($desc['editions'])) {
				$fields['module_info/included_in_editions']['hidden'] = false;
				$values['module_info/included_in_editions'] = $desc['editions'];
			}
			
			//If this module is dependent on other modules, list them all
			if (!empty($desc['dependencies'])) {
				$fields['module_info/dependencies']['hidden'] = false;
				
				$dependenciesFormattedNicely = [];
				foreach ($desc['dependencies'] as $dependency => $true) {
					$dependenciesFormattedNicely[] = $dependency . ' (' . ze\module::getModuleDisplayNameByClassName($dependency) . ')';
				}
				$fields['module_info/dependencies']['snippet']['html'] = implode('<br />', $dependenciesFormattedNicely);
			}
			
			//If any running modules depend on this module, list them all
			$pluginDependencies = [];
			$result = ze\row::query('module_dependencies', ['module_class_name', 'dependency_class_name'], ['type' => 'dependency', 'dependency_class_name' => $module['moduleClassName']]);
			while ($row = ze\sql::fetchAssoc($result)) {
				$pluginDependencies[] = $row['module_class_name'] . ' (' . ze\module::getModuleDisplayNameByClassName($row['module_class_name']) . ')';
			}
			
			if (count($pluginDependencies) > 0) {
				$fields['module_info/running_dependents']['hidden'] = false;
				$fields['module_info/running_dependents']['snippet']['html'] = implode('<br />', $pluginDependencies);
			}
			
			$revision = ze\admin::phrase('#1 (unspecified, assuming 1)');
			if (($path = ze::moduleDir($module['class_name'], 'latest_revision_no.inc.php', true))
			 && ($config = file_get_contents($path))) {
		
				foreach(preg_split('/define\s*\(.*?_LATEST_REVISION_NO\D*?(\d+)\D*?\)\s*\;/is', $config, -1, PREG_SPLIT_DELIM_CAPTURE) as $i => $value) {
					if ($i % 2) {
						$revision = '#'. (int) $value;
						break;
					}
				}
			}
			$values['module_info/revision'] = $revision;
			
			$values['module_info/licence'] = $desc['license_info'];
			$values['module_info/copyright_info'] = $desc['copyright_info'];
			$values['module_info/author_name'] = $desc['author_name'];
			$values['module_info/keywords'] = $desc['keywords'];
			
			if (!empty($desc['category'])) {
				$categoryDisplayName = '';
				switch ($desc['category']) {
					case 'custom':
						$categoryDisplayName = 'Custom module';
						break;
					case 'core':
						$categoryDisplayName = 'Core module';
						break;
					case 'content_type':
						$categoryDisplayName = 'Content type defining module';
						break;
					case 'management':
						$categoryDisplayName = 'Management module';
						break;
					case 'pluggable':
						$categoryDisplayName = 'Pluggable module';
						break;
				}
				$values['module_info/category'] = ze\admin::phrase($categoryDisplayName);
			}
			
			$moduleSpecialPages = [];
			$result = ze\row::query('special_pages', ['equiv_id', 'content_type', 'module_class_name'], []);
			while ($sp = ze\sql::fetchAssoc($result)) {
				if (!isset($moduleSpecialPages[$sp['module_class_name']])) {
					$moduleSpecialPages[$sp['module_class_name']] = $sp['content_type']. '_'. $sp['equiv_id'];
				}
			}
			if (count($moduleSpecialPages) > 0 && isset($moduleSpecialPages[$module['class_name']])) {
				$fields['module_info/special_page']['hidden'] = false;
				$values['module_info/special_page'] = $moduleSpecialPages[$module['class_name']];
			}
			
			$hasPlugins = '';
			$nestable = false;
			if ($desc['is_pluggable'] || $desc['can_be_version_controlled']) {
				$hasPlugins = 'Yes';
				$nestable = $desc['nestable'] ? 'Yes' : 'No';
				$fields['module_info/nestable']['hidden'] = false;
				
				if ($desc['is_pluggable'] && $desc['can_be_version_controlled']) {
					$hasPlugins .= ' (Library plugin and version controlled plugin)';
				} else if ($desc['is_pluggable']) {
					$hasPlugins .= ' (library plugin)';
				} else if ($desc['can_be_version_controlled']) {
					$hasPlugins .= ' (version controlled plugin)';
				}
			} else {
				$hasPlugins = 'No';
			}
			$values['module_info/has_plugins'] = ze\admin::phrase($hasPlugins);
			$values['module_info/nestable'] = ze\admin::phrase($nestable);
			
			if ($module['is_pluggable'] && is_dir(CMS_ROOT. ze::moduleDir($module['class_name'], 'tuix/visitor'))) {
					$fields['module_info/fea_info']['hidden'] = false;
			}
			
			$signals = [];
			if (!empty($desc['signals']) && is_array($desc['signals'])) {
				foreach ($desc['signals'] as $signal) {
					if (!empty($signal['name'])) {
						$signals[$signal['name']] = $signal['name'];
					}
				}
			}
			if (!empty($signals)) {
				$fields['module_info/listens_for']['hidden'] = false;
				$values['module_info/listens_for'] = implode(', ', $signals);
			}
			
			$values['module_info/table_prefix'] = DB_PREFIX. ze\module::prefix($module);
			
			$moduleTables = [];
			foreach (ze\dbAdm::lookupExistingCMSTables() as $table) {
				if ($table['module_id'] && $table['module_id'] == $module['module_id']) {
			
					$moduleTables[] = $table['actual_name'];
				}
			}
			
			if (count($moduleTables) > 0) {
				$fields['module_info/db_tables_created_by_this_module']['hidden'] = false;
				$fields['module_info/db_tables_created_by_this_module']['snippet']['html'] = implode('<br />', $moduleTables);
			}
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}
}