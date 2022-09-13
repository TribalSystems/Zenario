<?php
/*
 * Copyright (c) 2022, Tribal Limited
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


class zenario_common_features__organizer__modules extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__modules/panels/modules') return;
		
		ze\moduleAdm::addNew();

		switch ($refinerName) {
			case 'nestable_only':
			case 'nestable_wireframes_only':
				//Don't show the "all_instances" options for Plugin Nests
				$panel['collection_buttons']['all_instances']['hidden'] =
				$panel['collection_buttons']['all_instances']['hidden'] = true;

			case 'phrases_only':
			case 'slotable_only':
				//Don't show the filters if picking a plugin
				$panel['quick_filter_buttons']['all']['hidden'] =
				$panel['quick_filter_buttons']['module_not_initialized']['hidden'] =
				$panel['quick_filter_buttons']['module_running']['hidden'] =
				$panel['quick_filter_buttons']['module_suspended']['hidden'] = true;
		}
		
		//Add a column to say which modules in the current edition
		switch (ze\moduleAdm::siteEdition()) {
			case 'Community':
				$editionLevel = 2;
				break;
			case 'Pro':
				$editionLevel = 3;
				break;
			case 'ProBusiness':
				$editionLevel = 4;
				break;
			case 'Enterprise':
				$editionLevel = 5;
				break;
			case 'Other':
			default:
				$editionLevel = 1;
		}
		$panel['columns']['in_edition']['db_column'] = "1*m.edition <= ". (int) $editionLevel;
		
		
		//Abstract and missing modules. Include slightly different logic depending on the mode.
		switch ($mode) {
			case 'typeahead_search':
				//Only show modules that are neither missing or abstract in type-ahead searches
				$panel['db_items']['where_statement'] = "
					WHERE m.missing = 0
					  AND m.status != 'module_is_abstract'";
				break;
			
			case 'full':
			case 'quick':
			case 'select':
				//Don't show abstract modules unless they are missing.
				//Show modules that aren't running, unless they are missing.
				$panel['db_items']['where_statement'] = "
					WHERE (m.missing, m.status) NOT IN ((0, 'module_is_abstract'), (1, 'module_not_initialized'))";
				break;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__modules/panels/modules') return;
		
		//Remove close-up view form select mode, and make it so that double-clicking goes straight into Plugin Instances
		if ($mode == 'select' && $panel['item_buttons']['view_instances']['link']) {
			$panel['item']['link'] = $panel['item_buttons']['view_instances']['link'];
		}

		switch ($refinerName) {
			case 'nestable_only':
				$panel['title'] = ze\admin::phrase('Modules that can be nested');
				break;
			case 'nestable_wireframes_only':
				$panel['title'] = ze\admin::phrase('Plugins that can be nested');
				break;
			case 'phrases_only':
				$panel['title'] = ze\admin::phrase('Modules with phrases');
				break;
			case 'slotable_only':
				$panel['title'] = ze\admin::phrase('Running modules with library plugins');
				break;
		}

		$languagesExist = false;
		$emptyListView = [];
		$inheritedListView = [];
		$pluginDependencies = [];
		$moduleTables = [];
		if ($mode != 'select') {
			//Prep a list of Languages installed on the site
			foreach (ze\lang::getLanguages() as $lang) {
				$panel['columns'][$lang['id']] = ['title' => ze\admin::phrase('Phrases in [[english_name]]', $lang)];
				$emptyListView[$lang['id']] = '-';
				$inheritedListView[$lang['id']] = '*';
				$languagesExist = true;
			}
	
			//Look for (installed) module dependencies
			$result = ze\row::query('module_dependencies', ['module_class_name', 'dependency_class_name'], ['type' => 'dependency'], 'dependency_class_name');
			while($row = ze\sql::fetchAssoc($result)) {
				if (!isset($pluginDependencies[$row['dependency_class_name']])) {
					$pluginDependencies[$row['dependency_class_name']] = [];
				}
		
				$pluginDependencies[$row['dependency_class_name']][$row['module_class_name']] = true;
			}
	
			//Look for tables used by modules
			foreach (ze\dbAdm::lookupExistingCMSTables() as $table) {
				if ($table['module_id']) {
			
					if (!isset($moduleTables[$table['module_id']])) {
						$moduleTables[$table['module_id']] = [];
					}
			
					$moduleTables[$table['module_id']][] = $table['actual_name'];
				}
			}

		} else {
			unset($panel['columns']['prefix']['title']);
		}

		//Look for special pages
		$moduleSpecialPages = [];
		$result = ze\row::query('special_pages', ['equiv_id', 'content_type', 'module_class_name'], []);
		while ($sp = ze\sql::fetchAssoc($result)) {
			if (!isset($moduleSpecialPages[$sp['module_class_name']])) {
				$moduleSpecialPages[$sp['module_class_name']] = $sp['content_type']. '_'. $sp['equiv_id'];
			}
		}

		foreach ($panel['items'] as $id => &$module) {
			$module['cell_css_classes'] = [];
	
			if ($path = ze::moduleDir($module['class_name'], 'module_code.php', true)) {
				//Check whether this is a core/extra/custom module
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
				$module['core_extra_or_custom'] = ze\admin::phrase($coreExtraOrCustom);
				
				//Module full path
				$module['path'] = substr(CMS_ROOT. $path, 0, -15);
				$module['code_present'] = true;
		
				if ($module['status'] == 'module_not_initialized') {
					$module['comment'] = ze\admin::phrase('(uninitialised)');
					$module['cell_css_classes']['status'] = "orange";
				} elseif ($module['status'] == 'module_suspended') {
					$module['comment'] = ze\admin::phrase('(suspended)');
					$module['cell_css_classes']['status'] = "brown";
				} else {
					$module['comment'] = '';
					$module['cell_css_classes']['status'] = "green";
				}
		
				$module['special_page'] = $moduleSpecialPages[$module['class_name']] ?? false;
		
				//Don't allow people to click the folder to see a module's plugins if it doesn't use plugins, or is not running
				if (!$module['is_pluggable']
				 || $module['status'] != 'module_running') {
					$module['link'] = false;
				}
		
				if ($mode != 'select') {
					if (!($module['is_pluggable'] && $module['vlp_class'])) {
						$module = array_merge($module, $emptyListView);
					} elseif ($module['vlp_class'] != $module['class_name']) {
						$module = array_merge($module, $inheritedListView);
						$lv = $inheritedListView;
					} else {
						$module = array_merge($module, $emptyListView);
				
						$sql2 = "
							SELECT language_id, COUNT(*) AS c
							FROM ". DB_PREFIX. "visitor_phrases
							WHERE module_class_name = '". ze\escape::asciiInSQL($module['vlp_class']). "'
							GROUP BY language_id";
				
						$result2 = ze\sql::select($sql2);
						while($row2 = ze\sql::fetchAssoc($result2)) {
							if (isset($module[$row2['language_id']])) {
								$module[$row2['language_id']] = $row2['c'];
							}
						}
					}
				}
		
		
				if ($module['status'] != 'module_not_initialized') {
			
					if ($module['vlp_class'] && $module['vlp_class'] == $module['class_name']) {
						$module['uses_vlps'] = true;
				
						//if ($languagesExist && ze::moduleDir($module['class_name'], 'phrases/', true)) {
						//	$module['has_phrase_packs'] = true;
						//}
					}
				}
		
		
				//Load information from the module's description.xml
				$desc = false;
				if (ze\moduleAdm::loadDescription($module['class_name'], $desc)) {
			
					//Read the module's minor revision number from the latest_revision_no.inc.php file
					$module['revision'] = ze\admin::phrase('#1 (unspecified, assuming 1)');
					if (($path = ze::moduleDir($module['class_name'], 'latest_revision_no.inc.php', true))
					 && ($config = file_get_contents($path))) {
				
						foreach(preg_split('/define\s*\(.*?_LATEST_REVISION_NO\D*?(\d+)\D*?\)\s*\;/is', $config, -1, PREG_SPLIT_DELIM_CAPTURE) as $i => $value) {
							if ($i % 2) {
								$module['revision'] = '#'. (int) $value;
								break;
							}
						}
					}
					
			
					$module['author_name'] = $desc['author_name'];
					$module['copyright_info'] = $desc['copyright_info'];
					$module['license_info'] = $desc['license_info'];
					$module['keywords'] = $desc['keywords'];
					
					if (!empty($desc['editions'])) {
						$module['editions'] = $module['editions_column'] = $desc['editions'];
					}
					
					$signals = [];
					if (!empty($desc['signals']) && is_array($desc['signals'])) {
						foreach($desc['signals'] as $signal) {
							if (!empty($signal['name'])) {
								$signals[$signal['name']] = $signal['name'];
							}
						}
					}
					if (!empty($signals)) {
						$module['listens_for'] = implode(', ', $signals);
					}
			
			
			
			
					$module['close_up_view'] = '';
					$module['close_up_view_bottom'] = '';
					$module['dependencies'] = '';
					$module['dependents'] = '';
					$dependencyContent = '';
			
					$module['close_up_view'] = $desc['description'];
			
					//if (!empty($desc['webpage'])) {
						//$module['frontend_link'] = $desc['webpage'];
						//$module['has_webpage'] = true;
					//}
			
					if ($mode != 'select') {
						//Read the dependancies of a Module from an XML description
						$dependencies = ze\moduleAdm::readDependencies($module['class_name'], $desc);
				
						//Display inheritances as dependencies if they are not already
						if (!empty($desc['inheritance']['inherit_frameworks_from_module'])) {
							$dep = $desc['inheritance']['inherit_frameworks_from_module'];
							$dependencies[$dep] = $dep;
						}
						if (!empty($desc['inheritance']['include_javascript_from_module'])) {
							$dep = $desc['inheritance']['include_javascript_from_module'];
							$dependencies[$dep] = $dep;
						}
						if (!empty($desc['inheritance']['inherit_settings_from_module'])) {
							$dep = $desc['inheritance']['inherit_settings_from_module'];
							$dependencies[$dep] = $dep;
						}
						
						
						if (!empty($dependencies) || !empty($pluginDependencies[$module['class_name']])) {
					
							$module['dependencies'] .= '';
							$module['dependents'] .= '';
					
							if (!empty($dependencies)) {
								$i = 0;
								foreach ($dependencies as $dependency) {
									if ($i++) {
										$module['dependencies'] .= ', ';
								
									}
									$module['dependencies'] .= htmlspecialchars($dependency. ' ('. ze\module::getModuleDisplayNameByClassName($dependency). ')');
								}
							}
					
							if (!empty($pluginDependencies[$module['class_name']])) {
								$i = 0;
								foreach ($pluginDependencies[$module['class_name']] as $dependent => $dummy) {
									if ($i++) {
										$module['dependents'] .= ', ';
									}
									$module['dependents'] .= htmlspecialchars($dependent. ' ('. ze\module::getModuleDisplayNameByClassName($dependent). ')');
								}
							}
						}
				
				
						if (!empty($moduleTables[$id])) {
							
							$module['prefix'] = $prefix = DB_PREFIX. ze\module::prefix($module);
					
							$module['close_up_view_bottom'] .= '<tr><th>'. ze\admin::phrase('DB tables created by this module:'). '&nbsp;</th><td valign="bottom">';
							$i = 0;
							foreach ($moduleTables[$id] as $table) {
								if (ze\ring::chopPrefix($prefix, $table)) {
									if ($i++) {
										$module['close_up_view_bottom'] .= '<br/>';
									}
									$module['close_up_view_bottom'] .= htmlspecialchars($table);
								}
							}
							$module['close_up_view_bottom'] .= '</td></tr>';
						}
					}
				}
				
				if ($module['is_pluggable']
				 && is_dir(CMS_ROOT. ze::moduleDir($module['class_name'], 'tuix/visitor'))) {
					$module['isFEA'] = true;
				}
		
				$module['status'] = ze\admin::phrase($module['status']);
		
			} else {
		
				$module['display_name'] = $module['class_name'] . ' (Module code is missing)';
				$module['comment'] ='';
				$module['status'] = ze\admin::phrase('Module code is missing');
				$module['cell_css_classes']['status'] = "warning";
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__modules/panels/modules') return;
		
		if (!$module = ze\module::details($ids)) {
			echo ze\admin::phrase('Module not found!');
			exit;
		}

		$reloadIfNeeded = false;
		$return = null;
		
		if (($_POST['suspend'] ?? false) && ze\priv::check('_PRIV_RUN_MODULE') && $module['status'] == 'module_running') {
			ze\moduleAdm::suspend($ids);
			ze\skinAdm::clearCache();
			$reloadIfNeeded = true;

		} elseif (($_GET['remove'] ?? false) || ($_GET['uninstall'] ?? false)) {
			$module = ze\module::details($ids);
	
			if ($_GET['remove'] ?? false) {
				echo ze\admin::phrase('Are you sure that you wish to remove the module "[[class_name]]" ([[display_name]])?', $module);
			} else {
				echo ze\admin::phrase('Are you sure that you wish to uninitialise the module "[[class_name]]" ([[display_name]])?', $module);
			}
	
			echo "\n\n";
	
			echo ze\admin::phrase('All of its data, ');
	
			if ($module['vlp_class'] && $module['vlp_class'] == $module['class_name']) {
				echo ze\admin::phrase('all of its phrases, ');
			}
	
			if ($module['is_pluggable']) {
				echo ze\admin::phrase('all plugins derived from this module, ');
			}
	
			if (ze\row::exists('special_pages', ['module_class_name' => $module['class_name']])) {
				echo ze\admin::phrase('all special pages for this module, ');
			}
	
			if (ze\row::exists('centralised_lists', ['module_class_name' => $module['class_name']])) {
				echo ze\admin::phrase('all centralised lists for this module, ');
			}
	
			$result = ze\row::query('content_types', 'content_type_name_en', ['module_id' => $ids], 'content_type_name_en');
			while ($contentType = ze\sql::fetchAssoc($result)) {
				echo ze\admin::phrase('all content items of the "[[content_type_name_en]]" content type, ', $contentType);
			}
	
			echo ze\admin::phrase('WILL BE DELETED.');
	
			echo "\n\n";
	
			echo ze\admin::phrase('This cannot be undone.');
	

		} elseif (($_POST['remove'] ?? false) && ze\priv::check("_PRIV_RESET_MODULE") && (!file_exists(CMS_ROOT . 'modules/'. $module['class_name']. '/module_code.php'))) {
			ze\moduleAdm::uninstall($ids, true);
			ze\skinAdm::clearCache();

		} elseif (($_POST['uninstall'] ?? false) && ze\priv::check("_PRIV_RESET_MODULE") && $module['status'] == 'module_suspended') {
			
			//Remember the module's class name
			$moduleClassName = ze\module::className($ids);
			
			//Uninstall the module. This will also remove it's old id.
			ze\moduleAdm::uninstall($ids);
			ze\skinAdm::clearCache();
			
			//Try and look for the new id of the module
			ze\moduleAdm::addNew();
			$return = ze\module::id($moduleClassName);
			$reloadIfNeeded = false;
		}

		//Send a command to reload Organizer if a module adds to Organizer, or has a content type
		if ($reloadIfNeeded && ze\dbAdm::needToReloadOrganizerWhenModuleIsInstalled($module['class_name'])) {
			echo '<!--Reload_Organizer-->';
		}
		
		return $return;
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}
