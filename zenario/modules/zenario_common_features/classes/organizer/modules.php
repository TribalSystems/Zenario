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


class zenario_common_features__organizer__modules extends module_base_class {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__modules/panels/modules') return;
		
		addNewModules();

		switch ($refinerName) {
			case 'nestable_only':
			case 'nestable_wireframes_only':
				//Don't show the "all_instances" options for Plugin Nests
				$panel['collection_buttons']['all_instances']['hidden'] =
				$panel['collection_buttons']['all_instances']['hidden'] = true;

			case 'phrases_only':
			case 'slotable_only':
			case 'wireframe_only':
				//Don't show the filters if picking a plugin
				$panel['quick_filter_buttons']['all']['hidden'] =
				$panel['quick_filter_buttons']['module_not_initialized']['hidden'] =
				$panel['quick_filter_buttons']['module_running']['hidden'] =
				$panel['quick_filter_buttons']['module_suspended']['hidden'] = true;
		}
		
		//Add a column to say which modules in the current edition
		switch (siteEdition()) {
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
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__modules/panels/modules') return;
		
		//Remove close-up view form select mode, and make it so that double-clicking goes straight into Plugin Instances
		if ($mode == 'select' && $panel['item_buttons']['view_instances']['link']) {
			$panel['item']['link'] = $panel['item_buttons']['view_instances']['link'];
		}

		switch ($refinerName) {
			case 'nestable_only':
				$panel['title'] = adminPhrase('Modules that can be nested');
				break;
			case 'nestable_wireframes_only':
				$panel['title'] = adminPhrase('Plugins that can be nested');
				break;
			case 'phrases_only':
				$panel['title'] = adminPhrase('Modules with phrases');
				break;
			case 'slotable_only':
				$panel['title'] = adminPhrase('Running modules with library plugins');
				break;
			case 'wireframe_only':
				$panel['title'] = adminPhrase('Plugins');
				break;
			case 'custom_modules':
				$panel['title'] = adminPhrase('Custom modules');
				break;
			case 'core_modules':
				$panel['title'] = adminPhrase('Core modules');
				break;
			case 'content_type_modules':
				$panel['title'] = adminPhrase('Content type defining modules');
				break;
			case 'management_modues':
				$panel['title'] = adminPhrase('Managing modules');
				break;
			case 'pluggable_modules':
				$panel['title'] = adminPhrase('Pluggable modules');
				break;
		}

		$languagesExist = false;
		$emptyListView = array();
		$inheritedListView = array();
		$pluginDependencies = array();
		$moduleTables = array();
		if ($mode != 'select') {
			//Prep a list of Languages installed on the site
			foreach (getLanguages() as $lang) {
				$panel['columns'][$lang['id']] = array('title' => adminPhrase('Phrases in [[english_name]]', $lang));
				$emptyListView[$lang['id']] = '-';
				$inheritedListView[$lang['id']] = '*';
				$languagesExist = true;
			}
	
			//Look for (installed) module dependencies
			$result = getRows('module_dependencies', array('module_class_name', 'dependency_class_name'), array('type' => 'dependency'), 'dependency_class_name');
			while($row = sqlFetchAssoc($result)) {
				if (!isset($pluginDependencies[$row['dependency_class_name']])) {
					$pluginDependencies[$row['dependency_class_name']] = array();
				}
		
				$pluginDependencies[$row['dependency_class_name']][$row['module_class_name']] = true;
			}
	
			//Look for tables used by the CMS
			foreach (lookupExistingCMSTables() as $table) {
				if (substr($table['name'], 0, 3) == 'mod') {
					$moduleId = explode('_', substr($table['name'], 3), 2);
					$moduleId = (int) $moduleId[0];
			
					if (!isset($moduleTables[$moduleId])) {
						$moduleTables[$moduleId] = array();
					}
			
					$moduleTables[$moduleId][] = $table['actual_name'];
				}
			}

		} else {
			unset($panel['columns']['prefix']['title']);
		}

		//Look for special pages
		$moduleSpecialPages = array();
		$result = getRows('special_pages', array('equiv_id', 'content_type', 'module_class_name'), array());
		while ($sp = sqlFetchAssoc($result)) {
			if (!isset($moduleSpecialPages[$sp['module_class_name']])) {
				$moduleSpecialPages[$sp['module_class_name']] = $sp['content_type']. '_'. $sp['equiv_id'];
			}
		}

		/*
		$sql = "
			SELECT
				id,
				class_name,
				class_name AS name,
				display_name,
				vlp_class,
				is_pluggable,
				can_be_version_controlled,
				nestable,
				status
			FROM " . DB_NAME_PREFIX . "modules";
		$result = sqlQuery($sql);

		$missingModules = array();
		$panel['items'] = array();
		while ($module = sqlFetchAssoc($result)) {
	
			if ($refinerName == 'slotable_only' && !($module['is_pluggable'] && $module['status'] == 'module_running')) {
				continue;
	
			} elseif (($refinerName == 'wireframe_only' || $refinerName == 'nestable_wireframes_only') && !($module['is_pluggable'] && $module['can_be_version_controlled'] && $module['status'] == 'module_running')) {
				continue;
	
			} elseif (($refinerName == 'nestable_only' || $refinerName == 'nestable_wireframes_only') && !($module['is_pluggable'] && $module['nestable'] && $module['status'] == 'module_running')) {
				continue;
	
			} elseif ($refinerName == 'phrases_only' && !($module['is_pluggable'] && $module['vlp_class'] && $module['vlp_class'] == $module['name'])) {
				continue;
			}
			*/

		foreach ($panel['items'] as $id => &$module) {
			$module['traits'] = array();
			$module['traits'][$module['status']] = true;
			$module['cell_css_classes'] = array();
	
			if ($path = moduleDir($module['name'], 'module_code.php', true)) {
				$module['path'] = substr(CMS_ROOT. $path, 0, -15);
				$module['traits']['code_present'] = true;
		
				if ($module['status'] == 'module_not_initialized') {
					$module['comment'] = adminPhrase('(uninitialised)');
					$module['cell_css_classes']['status'] = "orange";
				} elseif ($module['status'] == 'module_suspended') {
					$module['comment'] = adminPhrase('(suspended)');
					$module['cell_css_classes']['status'] = "brown";
				} else {
					$module['comment'] = '';
					$module['cell_css_classes']['status'] = "green";
				}
		
				$module['special_page'] = $moduleSpecialPages[$module['name']] ?? false;
		
				//Don't allow people to click the folder to see a module's plugins if it doesn't use plugins, or is not running
				if (!$module['is_pluggable']
				 || $module['status'] != 'module_running') {
					$module['link'] = false;
				}
		
				if ($mode != 'select') {
					if (!($module['is_pluggable'] && $module['vlp_class'])) {
						$module = array_merge($module, $emptyListView);
					} elseif ($module['vlp_class'] != $module['name']) {
						$module = array_merge($module, $inheritedListView);
						$lv = $inheritedListView;
					} else {
						$module = array_merge($module, $emptyListView);
				
						$sql2 = "
							SELECT language_id, COUNT(*) AS c
							FROM ". DB_NAME_PREFIX. "visitor_phrases
							WHERE module_class_name = '". sqlEscape($module['vlp_class']). "'
							GROUP BY language_id";
				
						$result2 = sqlQuery($sql2);
						while($row2 = sqlFetchAssoc($result2)) {
							if (isset($module[$row2['language_id']])) {
								$module[$row2['language_id']] = $row2['c'];
							}
						}
					}
				}
		
		
				if ($module['status'] != 'module_not_initialized') {
			
					if ($module['is_pluggable']) {
						$module['traits']['is_pluggable'] = true;
					}
			
					if ($module['vlp_class'] && $module['vlp_class'] == $module['name']) {
						$module['traits']['uses_vlps'] = true;
				
						if ($languagesExist && moduleDir($module['name'], 'phrases/', true)) {
							$module['traits']['has_phrase_packs'] = true;
						}
					}
				}
		
		
				//Load information from the module's description.xml
				$desc = false;
				if (loadModuleDescription($module['name'], $desc)) {
			
					//Read the module's minor revision number from the latest_revision_no.inc.php file
					$module['revision'] = adminPhrase('#1 (unspecified, assuming 1)');
					if (($path = moduleDir($module['name'], 'latest_revision_no.inc.php', true))
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
						$module['editions'] = $desc['editions'];
					}
					
					$signals = array();
					if (!empty($desc['signals']) && is_array($desc['signals'])) {
						foreach($desc['signals'] as $signal) {
							if (!empty($signal['name'])) {
								$signals[$signal['name']] = $signal['name'];
							}
						}
					}
					if (!empty($signals)) {
						$module['listens_for'] = implode(', ', $signals);
					} else {
						$module['listens_for'] = adminPhrase('none');
					}
			
			
			
			
					$module['close_up_view'] = '';
					$module['close_up_view_bottom'] = '';
					$module['dependencies'] = '';
					$module['dependents'] = '';
					$dependencyContent = '';
			
					$module['close_up_view'] = $desc['description'];
			
					//if (!empty($desc['webpage'])) {
						//$module['frontend_link'] = $desc['webpage'];
						//$module['traits']['has_webpage'] = true;
					//}
			
					if ($mode != 'select') {
						//Read the dependancies of a Module from an XML description
						$dependencies = readModuleDependencies($module['name'], $desc);
				
						//Display inheritances as dependencies if they are not already
						if (!empty($desc['inheritance']['inherit_description_from_module'])) {
							$dep = $desc['inheritance']['inherit_description_from_module'];
							$dependencies[$dep] = $dep;
						}
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
						
						
						if (!empty($dependencies) || !empty($pluginDependencies[$module['name']])) {
					
							$module['dependencies'] .= '';
							$module['dependents'] .= '';
					
							if (!empty($dependencies)) {
								$i = 0;
								foreach ($dependencies as $dependency) {
									if ($i++) {
										$module['dependencies'] .= ', ';
								
									}
									$module['dependencies'] .= htmlspecialchars(getModuleDisplayNameByClassName($dependency));
								}
							}
					
							if (!empty($pluginDependencies[$module['name']])) {
								$i = 0;
								foreach ($pluginDependencies[$module['name']] as $dependent => $dummy) {
									if ($i++) {
										$module['dependents'] .= ', ';
									}
									$module['dependents'] .= htmlspecialchars(getModuleDisplayNameByClassName($dependent));
								}
							}
						}
				
				
						if (!empty($moduleTables[$id])) {
					
							$module['close_up_view_bottom'] .= '<tr><th>'. adminPhrase('DB tables created by this module:'). '&nbsp;</th><td valign="bottom">';
							$i = 0;
							foreach ($moduleTables[$id] as $table) {
								if ($i++) {
									$module['close_up_view_bottom'] .= '<br/>';
								} else {
									if (($prefix = explode('_', $table, 3)) && (!empty($prefix[2]))) {
										$module['prefix'] = $prefix[0]. '_'. $prefix[1];
									}
								}
								$module['close_up_view_bottom'] .= htmlspecialchars($table);
							}
							$module['close_up_view_bottom'] .= '</td></tr>';
						}
					}
				}
		
				$module['status'] = adminPhrase($module['status']);
		
			} else {
				if ($module['status'] == 'module_running'){
					$missingModules[$module['name']] = $module['display_name'] . ' (' . $module['name'] . ')' ;
				}
		
				//To do: hide or auto-delete missing modules that aren't running!
				$module['display_name'] = $module['name'] . ' (Module code is missing)';
				$module['comment'] ='';
				$module['status'] = adminPhrase('Module code is missing');
				$module['cell_css_classes']['status'] = "warning";
			}
		}

		if (!empty($missingModules) && EMAIL_ADDRESS_GLOBAL_SUPPORT) {
			asort($missingModules);
			$subject = adminPhrase('Missing module files on [[HTTP_HOST]]', $_SERVER);
			$body = adminPhrase('Files for the following modules are missing:'). "\n\n". implode("\r\n", $missingModules);
			$addressToOverriddenBy = false;
			sendEmail(
				$subject, $body,
				EMAIL_ADDRESS_GLOBAL_SUPPORT,
				$addressToOverriddenBy,
				$nameTo = false,
				$addressFrom = false,
				$nameFrom = false,
				false, false, false,
				$isHTML = false,
				false, false, false,
				'module_missing');
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__modules/panels/modules') return;
		
		if (!$module = getModuleDetails($ids)) {
			echo adminPhrase('Module not found!');
			exit;
		}

		$reloadIfNeeded = false;
		$return = null;
		
		if (($_POST['suspend'] ?? false) && checkPriv('_PRIV_SUSPEND_MODULE') && $module['status'] == 'module_running') {
			suspendModule($ids);
			zenarioClearCache();
			$reloadIfNeeded = true;

		} elseif (($_GET['remove'] ?? false) || ($_GET['uninstall'] ?? false)) {
			$module = getModuleDetails($ids);
	
			if ($_GET['remove'] ?? false) {
				echo adminPhrase('Are you sure that you wish to remove the Module "[[display_name]]"?', $module);
			} else {
				echo adminPhrase('Are you sure that you wish to uninitialise the Module "[[display_name]]"?', $module);
			}
	
			echo "\n\n";
	
			echo adminPhrase('All of its data, ');
	
			if ($module['vlp_class'] && $module['vlp_class'] == $module['class_name']) {
				echo adminPhrase('all of its Phrases, ');
			}
	
			if ($module['is_pluggable']) {
				echo adminPhrase('all Plugins derived from this Module, ');
			}
	
			if (checkRowExists('special_pages', array('module_class_name' => $module['class_name']))) {
				echo adminPhrase('all Special Pages for this Module, ');
			}
	
			if (checkRowExists('centralised_lists', array('module_class_name' => $module['class_name']))) {
				echo adminPhrase('all centralised lists for this Module, ');
			}
	
			$result = getRows('content_types', 'content_type_name_en', array('module_id' => $ids), 'content_type_name_en');
			while ($contentType = sqlFetchAssoc($result)) {
				echo adminPhrase('all Content Items of the "[[content_type_name_en]]" Content Type, ', $contentType);
			}
	
			echo adminPhrase('WILL BE DELETED.');
	
			echo "\n\n";
	
			echo adminPhrase('This cannot be undone.');
	

		} elseif (($_POST['remove'] ?? false) && checkPriv("_PRIV_RESET_MODULE") && (!file_exists(CMS_ROOT . 'modules/'. $module['class_name']. '/module_code.php'))) {
			uninstallModule($ids, true);
			zenarioClearCache();

		} elseif (($_POST['uninstall'] ?? false) && checkPriv("_PRIV_RESET_MODULE") && $module['status'] == 'module_suspended') {
			
			//Remember the module's class name
			$moduleClassName = getModuleClassName($ids);
			
			//Uninstall the module. This will also remove it's old id.
			uninstallModule($ids);
			zenarioClearCache();
			
			//Try and look for the new id of the module
			addNewModules();
			$return = getModuleId($moduleClassName);
			$reloadIfNeeded = false;
		}

		//Send a command to reload Organizer if a module adds to Organizer, or has a content type
		if ($reloadIfNeeded && needToReloadOrganizerWhenModuleIsInstalled($module['class_name'])) {
			echo '<!--Reload_Organizer-->';
		}
		
		return $return;
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}