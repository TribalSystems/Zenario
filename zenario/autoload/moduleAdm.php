<?php 
/*
 * Copyright (c) 2020, Tribal Limited
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

namespace ze;

class moduleAdm {





	//Formerly "siteEdition()"
	public static function siteEdition() {
		if ($edition = \ze\site::description('edition')) {
			$edition = explode(' ', $edition);
			return trim($edition[0]);
		}
		return 'Community';
	}

	//Formerly "moduleDescriptionFilePath()"
	public static function descriptionFilePath($moduleName) {
		if (($path = \ze::moduleDir($moduleName, 'description.yaml', true))
		 || ($path = \ze::moduleDir($moduleName, 'description.yml', true))
		 || ($path = \ze::moduleDir($moduleName, 'description.xml', true))) {
			return $path;
		} else {
			return false;
		}
	}


	//Formerly "loadModuleDescription()"
	public static function loadDescription($moduleName, &$tags) {
	
		if (!\ze::moduleDir($moduleName, '', true)) {
			return false;
		}
		$tags = [];
		$limit = 20;
		$modulesWeHaveRead = [];
		$baseModuleName = $inherit_description_from_module = $moduleName;
		$settingGroup = 'own';
	
		while (--$limit && $inherit_description_from_module && empty($modulesWeHaveRead[$inherit_description_from_module])) {
			$modulesWeHaveRead[$inherit_description_from_module] = true;
			$baseModuleName = $inherit_description_from_module;
			$inherit_description_from_module = false;
		
			//Attempt to open and read the description file
			if ($path = \ze\moduleAdm::descriptionFilePath($baseModuleName)) {
			
				if (!$tagsToParse = \ze\tuix::readFile(CMS_ROOT. $path)) {
					echo \ze\admin::phrase('[[path]] appears to be in the wrong format or invalid.', ['path' => CMS_ROOT. $path]);
					return false;
			
				} else {
					if (!empty($tagsToParse['inheritance']['inherit_description_from_module'])) {
						$inherit_description_from_module = trim((string) $tagsToParse['inheritance']['inherit_description_from_module']);
					}
				
					\ze\tuix::parse($tags, $tagsToParse, 'module_description', '', $settingGroup);
					unset($tagsToParse);
				}
			}
		
			$settingGroup = 'inherited';
		}
	
		$replaces = [];
		$replaces['[[MODULE_DIRECTORY_NAME]]'] = $baseModuleName;
		$replaces['[[ZENARIO_MAJOR_VERSION]]'] = ZENARIO_MAJOR_VERSION;
		$replaces['[[ZENARIO_MINOR_VERSION]]'] = ZENARIO_MINOR_VERSION;
		$replaces['[[ZENARIO_VERSION]]'] = ZENARIO_VERSION;
	
		$contents = false;
	
		//If the fill_organizer_nav property is missing...
		if (!isset($tags['module']['fill_organizer_nav'])) {
			//Attempt to intelligently guess whether this module populates the Organizer navigation
			//by looking for the fillOrganizerNav() function in its module code.
			if (($contents || (($path = \ze::moduleDir($baseModuleName, 'module_code.php', true)) && ($contents = file_get_contents($path))))
			 && (preg_match('/function\s+fillOrganizerNav/', $contents))) {
				$replaces['[[HAS_FILLORGANIZERNAV_FUNCTION]]'] = true;
			} else {
				$replaces['[[HAS_FILLORGANIZERNAV_FUNCTION]]'] = false;
			}
		}
		unset($contents);
	
	
		$tagsToParse = \ze\tuix::readFile(CMS_ROOT. 'zenario/api/module_base_class/description.yaml');
		\ze\tuix::parse($tags, $tagsToParse, 'module_description', '', 'inherited');
		unset($tagsToParse);
	
		$tags = $tags['module_description'];
	
		foreach ($tags as &$tag) {
			if (is_string($tag) && isset($replaces[$tag])) {
				$tag = $replaces[$tag];
			}
		}
		return true;
	}

	//Formerly "readModuleDependencies()"
	public static function readDependencies($targetModuleClassName, &$desc) {
		$modules = [];
	
		if (!empty($desc['inheritance']['inherit_description_from_module'])) {
			$dep = $desc['inheritance']['inherit_description_from_module'];
			$modules[$dep] = $dep;
		}
		if (!empty($desc['inheritance']['inherit_frameworks_from_module'])) {
			$dep = $desc['inheritance']['inherit_frameworks_from_module'];
			$modules[$dep] = $dep;
		}
		if (!empty($desc['inheritance']['include_javascript_from_module'])) {
			$dep = $desc['inheritance']['include_javascript_from_module'];
			$modules[$dep] = $dep;
		}
		if (!empty($desc['inheritance']['inherit_settings_from_module'])) {
			$dep = $desc['inheritance']['inherit_settings_from_module'];
			$modules[$dep] = $dep;
		}
	
		if (!empty($desc['dependencies']) && is_array($desc['dependencies'])) {
			foreach($desc['dependencies'] as $moduleClassName => $bool) {
				//An attempt at Backwards compatability for Modules before 6.0
				if ($moduleClassName == 'module') {
					$moduleClassName = $bool;
					$bool = true;
				}
			
				if (\ze\ring::engToBoolean($bool)) {
					$modules[$moduleClassName] = $moduleClassName;
				}
			}
		}
	
		//Make sure this Module isn't listed as a dependancy of itself
		unset($modules[$targetModuleClassName]);
	
		return $modules;
	}


	//Formerly "runModule()"
	public static function run($id, $test) {
		$desc = false;
		$missingModules = [];
		$module = \ze\module::details($id);
		$moduleErrors = '';
	
		if (\ze\dbAdm::checkIfUpdatesAreNeeded($moduleErrors)) {
			return \ze\admin::phrase("Core databases need to be applied. Please log out and then log back in again before attempting to install any modules.");
	
		} elseif ($moduleErrors) {
			return $moduleErrors;
	
		//Check to see if there are any other versions of the same module running.
		//If we find another version running, don't let this version be activated!
		} elseif (\ze\row::exists('modules', ['id' => ['!' => $id], 'class_name' => $module['class_name'], 'status' => ['module_running', 'module_is_abstract']])) {
			return \ze\admin::phrase('_ANOTHER_VERSION_OF_PLUGIN_IS_INSTALLED');
	
		} elseif (!\ze\moduleAdm::loadDescription($module['class_name'], $desc)) {
			return \ze\admin::phrase("This module's description file is missing or not valid.");
	
		} elseif (empty($desc['required_cms_version'])) {
			return \ze\admin::phrase("This module's description file does not state its required version number.");
	
		} elseif (version_compare($desc['required_cms_version'], ZENARIO_MAJOR_VERSION. '.'. ZENARIO_MINOR_VERSION, '>')) {
			return \ze\admin::phrase('Sorry, this Module requires Zenario [[version]] or later to run. Please update your copy of the CMS.', ['version' => $desc['required_cms_version']]);
	
		} else {
		
			//If both the module and the site have edition(s) set, check that they match
			if (!empty($desc['editions'])
			 && ($edition = \ze\moduleAdm::siteEdition())) {
			
				$editions = \ze\ray::explodeAndTrim($desc['editions']);
				$inEditions = \ze\ray::valuesToKeys($editions);
			
				if (empty($inEditions[$edition])) {
					return \ze\admin::phrase('In order to start this module, your site needs to be upgraded to Zenario [[0]]. This is determined in the zenario_custom/site_description.yaml file. Please contact your system adminstrator or hosting provider to request this change.', $editions);
				}
			}
		
			if ($installation_check = \ze::moduleDir($module['class_name'], 'installation_check.php', true)) {
				require CMS_ROOT. $installation_check;
				$installation_status = checkInstallationCanProceed();
		
				if ($installation_status !== true) {
					if (is_array($installation_status)) {
						$error_string = '';
						foreach ($installation_status as $error) {
							$error_string .= ($error_string? '<br/>' : ''). $error;
						}
						return $error_string;
			
					} else {
						$installation_status = (string) $installation_status;
						if (!empty($installation_status)) {
							return $installation_status;
						} else {
							return \ze\admin::phrase("This module cannot be run because the checkInstallationCanProceed() function in its installation_check.php file returned false.");
						}
					}
				}
			}
	
			foreach (\ze\moduleAdm::readDependencies($module['class_name'], $desc) as $moduleClassName) {
				if (!\ze\module::inc($moduleClassName)) {
					$missingModules[$moduleClassName] = $moduleClassName;
				}
			}
	
			if (!empty($missingModules)) {
				$module['missing_modules'] = '';
				foreach ($missingModules as $moduleClassName) {
					$module['missing_modules'] .=
						($module['missing_modules']? ', ' : '').
						\ze::ifNull(\ze\module::getModuleDisplayNameByClassName($moduleClassName), $moduleClassName);
				}
		
				if (count($missingModules) > 1) {
					return \ze\admin::phrase(
						'Cannot run the module "[[class_name]]" ([[display_name]]) as it depends on the following modules, which are not present or not running: [[missing_modules]]',
						$module);
		
				} else {
					return \ze\admin::phrase(
						'Cannot run the module "[[class_name]]" ([[display_name]]) as it depends on the "[[missing_modules]]" module, which is not present or not running.',
						$module);
				}
	
			} else {
				require CMS_ROOT. \ze::moduleDir($module['class_name'], 'module_code.php');
		
				if (!class_exists($module['class_name'])) {
					return \ze\admin::phrase(
						'Cannot run the module "[[class_name]]" ([[display_name]]) as its class "[[class_name]]" is not defined in its module_code.php file.',
						$module);
		
				} elseif ($test) {
					return false;
		
				} else {
					\ze\row::update('modules', ['status' => 'module_running'], ['id' => $id, 'status' => ['module_suspended', 'module_not_initialized']]);
			
					//Have a safety feature whereby if the installation fails, the module will be immediately uninstalled
					//However this shouldn't be used for upgrading a module to a different version
					\ze\dbAdm::checkIfUpdatesAreNeeded($moduleErrors, $andDoUpdates = true, $uninstallPluginOnFail = $id);
			
					//Add any content types this module has
					\ze\moduleAdm::setupContentTypesFromDescription($module['class_name']);
				}
			}
		}
	}

	//Formerly "suspendModule()"
	public static function suspend($id) {
		\ze\moduleAdm::checkForDependenciesBeforeSuspending(\ze\module::details($id));
		\ze\row::update('modules', ['status' => 'module_suspended'], ['id' => $id, 'status' => 'module_running']);
	}


	//Formerly "addNewModules()"
	public static function addNew($skipIfFilesystemHasNotChanged = true, $runModulesOnInstall = false, $dbUpdateSafeMode = false) {
		$moduleDirs = \ze::moduleDirs([
			'module_code.php',
			'description.yaml', 'description.yml',
			'description.xml', 'db_updates/description.xml']);
	
		chdir(CMS_ROOT);
		$module_description_hash = \ze::hash64(print_r(array_map('filemtime', $moduleDirs), true), 15);
	
		if ($skipIfFilesystemHasNotChanged) {
			if ($module_description_hash == \ze::setting('module_description_hash')) {
				return;
			}
		}
	
		$foundModules = [];
	
		foreach ($moduleDirs as $moduleName => $moduleDir) {
			$desc = false;
			if (\ze\moduleAdm::loadDescription($moduleName, $desc)) {
			
				$edition = 'Other';
				if (!empty($desc['editions'])) {
					$editions = \ze\ray::valuesToKeys(\ze\ray::explodeAndTrim($desc['editions']));
					foreach (['Community', 'Pro', 'ProBusiness', 'Enterprise'] as $etest) {
						if (isset($editions[$etest])) {
							$edition = $etest;
							break;
						}
					}
				}
			
				$foundModules[$moduleName] = true;
				$sql = "
					INSERT INTO ". DB_PREFIX. "modules SET
						class_name = '". \ze\escape::sql($moduleName). "',
						vlp_class = '". \ze\escape::sql($desc['vlp_class_name']). "',
						display_name = '". \ze\escape::sql($desc['display_name']). "',
						default_framework = '". \ze\escape::sql($desc['default_framework']). "',
						css_class_name = '". \ze\escape::sql($desc['css_class_name']). "',
						nestable = ". \ze\ring::engToBoolean($desc['nestable']);
					
				if (!$dbUpdateSafeMode && \ze\ring::engToBoolean($desc['is_abstract'] ?? false)) {
					$sql .= ",
						status = 'module_is_abstract'";
			
				} elseif ($runModulesOnInstall && \ze\ring::engToBoolean($desc['start_running_on_install'])) {
					$sql .= ",
						status = 'module_running'";
				}
			
				if (!$dbUpdateSafeMode) {
					$category = (!empty($desc['category'])) ? ("'".\ze\escape::sql($desc['category'])."'") : "NULL";
					$sql .= ",
						edition = '". \ze\escape::sql($edition). "',
						is_pluggable = ". \ze\ring::engToBoolean($desc['is_pluggable']). ",
						fill_organizer_nav = ". \ze\ring::engToBoolean($desc['fill_organizer_nav']). ",
						can_be_version_controlled = ". \ze\ring::engToBoolean(\ze\ring::engToBoolean($desc['is_pluggable'])? $desc['can_be_version_controlled'] : 0). ",
						missing = 0,
						category = ". $category;
				}
			
				$sql .= "
					ON DUPLICATE KEY UPDATE
						vlp_class = VALUES(vlp_class),
						display_name = VALUES(display_name),
						default_framework = VALUES(default_framework),
						css_class_name = VALUES(css_class_name),
						nestable = VALUES(nestable)";
					
			
				if (!$dbUpdateSafeMode && \ze\ring::engToBoolean($desc['is_abstract'] ?? false)) {
					$sql .= ",
						status = 'module_is_abstract'";
			
				} elseif ($runModulesOnInstall && \ze\ring::engToBoolean($desc['start_running_on_install'])) {
					$sql .= ",
						status = 'module_running'";
				}
			
				if (!$dbUpdateSafeMode) {
					$sql .= ",
						edition = VALUES(edition),
						is_pluggable = VALUES(is_pluggable),
						fill_organizer_nav = VALUES(fill_organizer_nav),
						can_be_version_controlled = VALUES(can_be_version_controlled),
						missing = 0,
						category = VALUES(category)";
				}
			
				\ze\sql::update($sql);
			}
		}
	
		//Mark modules that are not in the system
		if (!$dbUpdateSafeMode) {
			foreach (\ze\row::getValues('modules', 'class_name', []) as $id => $moduleName) {
				if (!isset($foundModules[$moduleName])) {
					\ze\row::set('modules', ['missing' => 1], $id);
				}
			}
		}
	
		\ze\site::setSetting('module_description_hash', $module_description_hash);
	}





	//Formerly "suspendModuleCheckForDependencies()"
	public static function checkForDependenciesBeforeSuspending($module) {
	
		//Check that the core does not depend on the module
		$sql = "
			SELECT 1
			FROM ". DB_PREFIX. "module_dependencies
			WHERE dependency_class_name = '". \ze\escape::sql($module['class_name']). "'
			  AND module_id = 0";
		$result = \ze\sql::select($sql);
	
		if (\ze\sql::fetchRow($result)) {
			echo \ze\admin::phrase(
				'Cannot Suspend the module &quot;[[module]]&quot; as the Core CMS depends on it.',
				['module' => htmlspecialchars(\ze\module::getModuleDisplayNameByClassName($module['class_name']))]);
			exit;
		}
	
		//Check that the module has no other modules that are inheriting from this one
		$sql = "
			SELECT module_id, module_class_name
			FROM ". DB_PREFIX. "module_dependencies
			WHERE dependency_class_name = '". \ze\escape::sql($module['class_name']). "'
			  AND `type` = 'dependency'";
		$result = \ze\sql::select($sql);
	
		while ($row = \ze\sql::fetchAssoc($result)) {
			if (\ze\module::status($row['module_id']) == 'module_running') {
				echo \ze\admin::phrase(
					'Cannot Suspend the module &quot;[[moduleName]]&quot; as the &quot;[[dependencyName]]&quot; module depends on it.',
					[
						'moduleName' => htmlspecialchars(\ze\module::getModuleDisplayNameByClassName($module['class_name'])),
						'dependencyName' => htmlspecialchars(\ze\module::getModuleDisplayNameByClassName($row['module_class_name']))]);
				exit;
			}
		}
	}


	//Formerly "uninstallModuleCheckForDependencies()"
	public static function checkForDependenciesBeforeUninstalling($module) {
		//Check that the module has no dependencies
		$sql = "
			SELECT module_class_name
			FROM ". DB_PREFIX. "module_dependencies
			WHERE dependency_class_name = '". \ze\escape::sql($module['class_name']). "'
			  AND `type` = 'dependency'";
		$result = \ze\sql::select($sql);
	
		if ($row = \ze\sql::fetchAssoc($result)) {
			echo \ze\admin::phrase(
				'Cannot uninitialise the module &quot;[[moduleName]]&quot; as the &quot;[[dependencyName]]&quot; module depends on it.',
				[
					'moduleName' => htmlspecialchars(\ze\module::getModuleDisplayNameByClassName($module['class_name'])),
					'dependencyName' => htmlspecialchars(\ze\module::getModuleDisplayNameByClassName($row['module_class_name']))]
			);
			exit;
		}
	}

	//Completely removes all traces of a module from a site.
	//Formerly "uninstallModule()"
	public static function uninstall($moduleId, $uninstallRunningModules = false) {

		$module = \ze\module::details($moduleId);

		if (!$uninstallRunningModules){
			if ($module['status'] == 'module_running'
			 || $module['status'] == 'module_is_abstract') {
				echo \ze\admin::phrase('Running modules cannot be Uninstalled');
				exit;
			}
		}

		\ze\moduleAdm::checkForDependenciesBeforeUninstalling($module);


		//Remove all data about the module
		$result = \ze\row::query('plugin_instances', 'id', ['module_id' => $moduleId]);
		while ($row = \ze\sql::fetchAssoc($result)) {
			\ze\row::delete('nested_paths', ['instance_id' => $row['id']]);
			\ze\pluginAdm::delete($row['id']);
		}

		foreach(['job_logs'] as $table) {
			$sql = "
				DELETE FROM ". DB_PREFIX. $table. "
				WHERE job_id IN (
					SELECT id
					FROM ". DB_PREFIX. "jobs
					WHERE job_id = ". (int) $moduleId. "
				)";
			\ze\sql::update($sql);
		}

		foreach([
			'jobs', 'nested_plugins',
			'module_dependencies', 'signals',
			'plugin_item_link', 'plugin_layout_link',
			'plugin_setting_defs', 'plugin_instances'
		] as $table) {
			\ze\row::delete($table, ['module_id' => $moduleId]);
		}
		\ze\row::delete('special_pages', ['module_class_name' => $module['class_name']]);

		//Attempt to delete any module tables or views
		$prefix2 = "mod". (int) $moduleId. "_";
		$prefix = DB_PREFIX. $prefix2;
		$prefixLen = strlen($prefix);

		$sql = "
			SHOW TABLES LIKE '". $prefix. "%'";
		$result = \ze\sql::select($sql);

		while($row = \ze\sql::fetchRow($result)) {
			if (substr($row[0], 0, $prefixLen) == $prefix) {
				\ze\sql::update("DROP TABLE IF EXISTS `". \ze\escape::sql($row[0]). "`", false, false);
				@\ze\sql::update("DROP VIEW IF EXISTS `". \ze\escape::sql($row[0]). "`", false, false);
			}
		}

		//Delete any datasets that used any of these tables
		$sql = "
			DELETE cd.*, cdt.*, cdf.*, fv.*, vl.*
			FROM ". DB_PREFIX. "custom_datasets AS cd
			LEFT JOIN ". DB_PREFIX. "custom_dataset_tabs AS cdt
			   ON cdt.dataset_id = cd.id
			LEFT JOIN ". DB_PREFIX. "custom_dataset_fields AS cdf
			   ON cdf.dataset_id = cd.id
			LEFT JOIN ". DB_PREFIX. "custom_dataset_field_values AS fv
			   ON fv.field_id = cdf.id
			LEFT JOIN ". DB_PREFIX. "custom_dataset_values_link AS vl
			   ON vl.dataset_id = cd.id
			WHERE cd.table LIKE '". $prefix2. "%'";
		\ze\sql::update($sql);

		//Completely delete any Content Types it has
		$result = \ze\row::query('content_types', 'content_type_id', ['module_id' => $moduleId], 'content_type_name_en');
		while ($contentType = \ze\sql::fetchAssoc($result)) {
			//Completely delete any Content Item for that Content Type
			$result2 = \ze\row::query('content_items', ['id', 'type'], ['type' => $contentType['content_type_id']]);
			while ($content = \ze\sql::fetchAssoc($result2)) {
				\ze\contentAdm::deleteContentItem($content['id'], $content['type']);
			}
	
			\ze\row::delete('content_types', $contentType);
		}

		//Completely delete any Special Pages it has
		$result = \ze\row::query('special_pages', ['equiv_id', 'content_type'], ['module_class_name' => $module['class_name']]);
		while ($specialPage = \ze\sql::fetchAssoc($result)) {
			//Completely delete the Special Page in every language
			$result2 = \ze\row::query('content_items', ['id', 'type'], ['equiv_id' => $specialPage['equiv_id'], 'type' => $specialPage['content_type']]);
			while ($content = \ze\sql::fetchAssoc($result2)) {
				\ze\contentAdm::deleteContentItem($content['id'], $content['type']);
			}
	
			\ze\row::delete('special_pages', $specialPage);
		}

		//Unlink this module from any special pages
		\ze\row::update('special_pages', ['module_class_name' => ''], ['module_class_name' => $module['class_name']]);


		//Delete any centralised lists
		\ze\row::delete('centralised_lists', ['module_class_name' => $module['class_name']]);

		//Delete any records of the module having been installed
		$sql = "
			DELETE FROM ". DB_PREFIX. "local_revision_numbers
			WHERE path LIKE '%modules/". \ze\escape::like($module['class_name']). "'
			   OR path LIKE '%modules/". \ze\escape::like($module['class_name']). "/db_updates'
			   OR path LIKE '%plugins/". \ze\escape::like($module['class_name']). "'
			   OR path LIKE '%plugins/". \ze\escape::like($module['class_name']). "/db_updates'";
		\ze\sql::update($sql);

		//Remove any Visitor Phrases
		\ze\row::delete('visitor_phrases', ['module_class_name' => $module['class_name']]);

		//If we are uninstalling a suspended Module, or removing a Module that is no longer in the filesystem,
		//remove it from the module table.
		if (!$uninstallRunningModules || !\ze::moduleDir($module['class_name'], '', true)) {
			\ze\row::delete('modules', $moduleId);
	
			//Force a re-scan of the Module directory, so that the Module's name will be re-read in next time if it is still in the file system
			\ze\site::setSetting('module_description_hash', '');

		 //But if this was an installation attempt, just mark the Module as not running to keep its Module Id
		} else {
			\ze\row::set('modules', ['status' => 'module_not_initialized'], $moduleId);
		}

	}




	//Attempt to check if this module can work with instances
	//Formerly "checkIfModuleUsesPluginInstances()"
	public static function isPluggable($moduleId) {
		return \ze\row::get('modules', 'is_pluggable', $moduleId);
	}




	//Get the XML description of a module, and apply it
	//Formerly "setupModuleFromDescription()"
	public static function setupFromDescription($moduleClassName) {
		$desc = false;
		if (!\ze\moduleAdm::loadDescription($moduleClassName, $desc)
		 || !$moduleId = \ze\module::id($moduleClassName)) {
			exit;
	
			return false;
		}
		//Update the modules table with the details
		if (!empty($desc['category'])) {
			$category = $desc['category'];

		} elseif (is_dir(CMS_ROOT. 'zenario_custom/modules/'. $moduleClassName)) {
			$category = 'custom';

		} else {
			$category = \ze\ring::engToBoolean($desc['is_pluggable'])? 'pluggable' : 'management';
		}
		
		$mustBeOn = '';
		if (\ze\ring::engToBoolean($desc['is_pluggable'])) {
			if (empty($desc['plugin_must_be_on_public_page'])) {
				if (empty($desc['plugin_must_be_on_private_page'])) {
				} else {
					$mustBeOn = 'private_page';
				}
			} else {
				if (empty($desc['plugin_must_be_on_private_page'])) {
					$mustBeOn = 'public_page';
				} else {
				}
			}
		}

		$sql = "
			UPDATE ". DB_PREFIX. "modules SET
				vlp_class = '". \ze\escape::sql($desc['vlp_class_name']). "',
				display_name = '". \ze\escape::sql($desc['display_name']). "',
				default_framework = '". \ze\escape::sql($desc['default_framework']). "',
				css_class_name = '". \ze\escape::sql($desc['css_class_name']). "',
				is_pluggable = ". \ze\ring::engToBoolean($desc['is_pluggable']). ",
				must_be_on = '". \ze\escape::sql($mustBeOn). "',
				fill_organizer_nav = ". \ze\ring::engToBoolean($desc['fill_organizer_nav']). ",
				can_be_version_controlled = ". \ze\ring::engToBoolean(\ze\ring::engToBoolean($desc['is_pluggable'])? $desc['can_be_version_controlled'] : 0). ",
				for_use_in_twig = ". \ze\ring::engToBoolean($desc['for_use_in_twig']). ",
				nestable = ". \ze\ring::engToBoolean($desc['nestable']). ",
				category = '". \ze\escape::sql($category). "'
			WHERE id = '". (int) $moduleId. "'";
		\ze\sql::update($sql);


		//Remove any existing dependencies
		$sql = "
			DELETE FROM ". DB_PREFIX. "module_dependencies
			WHERE module_id = '". (int) $moduleId. "'";
		\ze\sql::update($sql);

		//Look to see which dependencies this Module has
		$dependencies = [
			'dependency' => [],
			'inherit_frameworks' => [],
			'include_javascript' => [],
			'inherit_settings' => []
		];

		foreach (\ze\moduleAdm::readDependencies($moduleClassName, $desc) as $module) {
			$dependencies['dependency'][$module] = $module;
		}



		if (!empty($desc['inheritance']['inherit_frameworks_from_module'])) {
			$dep = $desc['inheritance']['inherit_frameworks_from_module'];
			$dependencies['inherit_frameworks'] = [$dep];
		}
		if (!empty($desc['inheritance']['include_javascript_from_module'])) {
			$dep = $desc['inheritance']['include_javascript_from_module'];
			$dependencies['include_javascript'] = [$dep];
		}
		if (!empty($desc['inheritance']['inherit_settings_from_module'])) {
			$dep = $desc['inheritance']['inherit_settings_from_module'];
			$dependencies['inherit_settings'] = [$dep];
		}

		//Record any dependencies found
		foreach ($dependencies as $type => $modules) {
			foreach ($modules as $module) {
				if ($module && $module != $moduleClassName && $module != $moduleClassName) {
					$sql = "
						INSERT INTO ". DB_PREFIX. "module_dependencies SET
							module_id = '". (int) $moduleId. "',
							module_class_name = '". \ze\escape::sql($moduleClassName). "',
							dependency_class_name = '". \ze\escape::sql(trim($module)). "',
							`type` = '". \ze\escape::sql(trim($type)). "'";
					\ze\sql::update($sql);
				}
			}
		}


		//Add any special pages that this module uses
		$specialPageChanges = false;
		if (!empty($desc['special_pages']) && is_array($desc['special_pages'])) {
			foreach($desc['special_pages'] as $page) {
				if (!empty($page['page_type'])) {
			
					//Choose one of the rules
					$defaultLogic = 'create_and_maintain_in_default_language';
					$otherRules = [
						'create_in_default_language_on_install' => true];
			
					if (!empty($page['logic']) && !empty($otherRules[$page['logic']])) {
						$logic = $page['logic'];
					} else {
						$logic = $defaultLogic;
					}
			
			
					//Check if this special page already exists
					if (!$specialPage = \ze\row::get('special_pages', true, ['page_type' => $page['page_type']])) {
						$specialPageChanges = true;
						\ze\row::insert(
							'special_pages',
							[
								'module_class_name' => $moduleClassName,
								'logic' => $logic,
								'allow_hide' => \ze\ring::engToBoolean($page['allow_hide'] ?? 0),
								'publish' => \ze\ring::engToBoolean($page['publish'] ?? 0),
								'page_type' => $page['page_type']
							]
						);
			
					} elseif (!$specialPage['module_class_name'] || $specialPage['module_class_name'] == $moduleClassName) {
						$specialPageChanges = true;
						\ze\row::update(
							'special_pages',
							[
								'module_class_name' => $moduleClassName,
								'logic' => $logic,
								'allow_hide' => \ze\ring::engToBoolean($page['allow_hide'] ?? 0),
								'publish' => \ze\ring::engToBoolean($page['publish'] ?? 0)
							],
							[
								'page_type' => $page['page_type']
							]
						);
			
					} elseif (!$specialPage['equiv_id']) {
						$specialPageChanges = true;
					}
				}
			}
		}


		//Remove any existing signals
		$sql = "
			DELETE FROM ". DB_PREFIX. "signals
			WHERE module_id = '". (int) $moduleId. "'";
		\ze\sql::update($sql);

		//Record any signals listened for
		if (!empty($desc['signals']) && is_array($desc['signals'])) {
			foreach($desc['signals'] as $signal) {
				if (!empty($signal['name'])) {
					$sql = "
						INSERT INTO ". DB_PREFIX. "signals SET
							signal_name = '". \ze\escape::sql($signal['name']). "',
							module_id = '". (int) $moduleId. "',
							module_class_name = '". \ze\escape::sql($moduleClassName). "',
							static_method = ". \ze\ring::engToBoolean($signal['static'] ?? false). ",
							suppresses_module_class_name = '". \ze\escape::sql($signal['suppresses_module_class_name'] ?? false). "'";
					\ze\sql::update($sql);
				}
			}
		}


		//Record any jobs the module has
		$jobs = '';
		if (!empty($desc['jobs']) && is_array($desc['jobs'])) {
			foreach($desc['jobs'] as $job) {
				if (!empty($job['name'])) {
					$jobs .= ($jobs? ',' : ''). "'". \ze\escape::sql($job['name']). "'";
					$sql = "
						INSERT IGNORE INTO ". DB_PREFIX. "jobs SET
							job_type = 'scheduled',
							manager_class_name = '". \ze\escape::sql((($job['manager_class_name'] ?? false) ?: 'zenario_scheduled_task_manager')). "',
							job_name = '". \ze\escape::sql($job['name']). "',
							module_id = '". (int) $moduleId. "',
							module_class_name = '". \ze\escape::sql($moduleClassName). "',
							static_method = ". \ze\ring::engToBoolean($job['static'] ?? false). ",
							enabled = ". \ze\ring::engToBoolean($job['enabled_by_default'] ?? false). ",
							months = '". \ze\escape::sql($job['months'] ?? false). "',
							days = '". \ze\escape::sql($job['days'] ?? false). "',
							hours = '". \ze\escape::sql($job['hours'] ?? false). "',
							minutes = '". \ze\escape::sql($job['minutes'] ?? false). "',
							run_every_minute = " . \ze\ring::engToBoolean($job['run_every_minute'] ?? false) . ",
							first_n_days_of_month = ". (int) ($job['first_n_days_of_month'] ?? false). ",
							log_on_action = ". \ze\ring::engToBoolean($job['log_on_action'] ?? false). ",
							log_on_no_action = ". \ze\ring::engToBoolean($job['log_on_no_action'] ?? false). ",
							email_on_action = ". \ze\ring::engToBoolean($job['email_on_action'] ?? false). ",
							email_on_no_action = ". \ze\ring::engToBoolean($job['email_on_no_action'] ?? false). ",
							email_address_on_action = '". \ze\escape::sql(EMAIL_ADDRESS_GLOBAL_SUPPORT). "',
							email_address_on_no_action = '". \ze\escape::sql(EMAIL_ADDRESS_GLOBAL_SUPPORT). "',
							email_address_on_error = '". \ze\escape::sql(EMAIL_ADDRESS_GLOBAL_SUPPORT). "'";
					\ze\sql::update($sql);
				}
			}
		}
		
		//Record any background tasks the module has
		if (!empty($desc['background_tasks']) && is_array($desc['background_tasks'])) {
			foreach($desc['background_tasks'] as $job) {
				if (!empty($job['name'])) {
					$jobs .= ($jobs? ',' : ''). "'". \ze\escape::sql($job['name']). "'";
					$sql = "
						INSERT IGNORE INTO ". DB_PREFIX. "jobs SET
							job_type = 'background',
							manager_class_name = 'zenario_scheduled_task_manager',
							job_name = '". \ze\escape::sql($job['name']). "',
							module_id = '". (int) $moduleId. "',
							module_class_name = '". \ze\escape::sql($moduleClassName). "',
							script_path = '". \ze\escape::sql($job['script_path']). "'";
					\ze\sql::update($sql);
				}
			}
		}

		//Remove any unused jobs or background tasks
		$innerSql = "
			FROM ". DB_PREFIX. "jobs
			WHERE module_id = ". (int) $moduleId;

		if ($jobs) {
			$innerSql .= "
			  AND job_name NOT IN (". $jobs. ")";
		}

		$sql = "
			DELETE FROM ". DB_PREFIX. "job_logs
			WHERE job_id IN (
				SELECT id
				". $innerSql. "
			)";

		\ze\sql::update($sql);

		$sql = "
			DELETE ". $innerSql;

		\ze\sql::update($sql);


		//Remove any existing centralised lists
		\ze\row::delete('centralised_lists', ['module_class_name' => $moduleClassName]);

		//Record any centralised lists the module has
		if (!empty($desc['centralised_lists']) && is_array($desc['centralised_lists'])) {
			foreach($desc['centralised_lists'] as $centralised_list) {
				if (!empty($centralised_list['method_name']) && !empty($centralised_list['label'])) {
					\ze\row::set('centralised_lists', 
						['label' => $centralised_list['label']], 
						[
							'module_class_name' => $moduleClassName, 
							'method_name' => $centralised_list['method_name']
						]
					);
				}
			}
		}


		//Remove any existing settings
		\ze\row::delete('plugin_setting_defs', ['module_id' => $moduleId]);
		\ze\row::delete('plugin_setting_defs', ['module_class_name' => $moduleClassName]);
		
		$secretColExists = \ze::$dbL->checkTableDef(DB_PREFIX. 'site_settings', 'secret', $useCache = false);

		//Loop through every module Setting that a module has in its Admin Box XML file(s)
		if ($dir = \ze::moduleDir($moduleClassName, 'tuix/admin_boxes/', true)) {
	
			foreach ([
				'zenario_admin' => 'admin_setting',
				'plugin_settings' => 'plugin_setting',
				'site_settings' => 'site_setting'
			] as $path => $settingDef) {
				$tags = [];
				foreach (scandir(CMS_ROOT. $dir) as $file) {
					if (is_file(CMS_ROOT. $dir. $file) && substr($file, 0, 1) != '.') {
						//Attempt to open and read the XML for an Admin Boxes
						$compatibilityClassNames = [$moduleClassName => true];

						$tagsToParse = \ze\tuix::readFile(CMS_ROOT. $dir. $file);
						\ze\tuix::parse($tags, $tagsToParse, 'admin_boxes', $moduleClassName, $settingGroup = true, $compatibilityClassNames, $path);
						unset($tagsToParse);
					}
				}
		
				if (!empty($tags)) {
					if (isset($tags['admin_boxes'][$path]['tabs']) && is_array($tags['admin_boxes'][$path]['tabs'])) {
						foreach($tags['admin_boxes'][$path]['tabs'] as &$tab) {
							if (isset($tab['fields']) && is_array($tab['fields'])) {
								foreach($tab['fields'] as &$field) {
									if (!empty($field[$settingDef]['name'])) {
										
										$name = $field[$settingDef]['name'];
										$value = '';
										
										if (isset($field[$settingDef]['value'])) {
											$value = $field[$settingDef]['value'];
								
										} elseif (isset($field['value'])) {
											$value = $field['value'];
										}
								
										switch ($settingDef) {
								
											case 'admin_setting':
												\ze\row::set('admin_setting_defaults',
													['default_value' => $value],
													$name);
												break;
									
											case 'plugin_setting':
												\ze\row::insert('plugin_setting_defs',
													['module_id' => $moduleId,
														  'module_class_name' => $moduleClassName,
														  'name' => $name,
														  'default_value' => $value]);
												break;
								
											case 'site_setting':
												
												//User permissions should be handled separately to site settings
												if ($perm = \ze\ring::chopPrefix('perm.', $name)) {
													//For user permissions, set the value to the default value,
													//if the default value is non-empty, and the value has never been saved before.
													if ($value) {
														$sql = "
															INSERT IGNORE INTO ". DB_PREFIX. "user_perm_settings SET
																name = '". \ze\escape::sql($perm). "',
																value = '". \ze\escape::sql((string) $value). "'";
														\ze\sql::update($sql);
													}
												
												} else {
													//For site settings, update the default values.
													$sql = "
														INSERT INTO ". DB_PREFIX. "site_settings SET
															name = '". \ze\escape::sql($name). "',
															value = NULL,
															default_value = '". \ze\escape::sql((string) $value). "'";
													
													if ($secretColExists) {
														$sql .= ",
															`secret` = ". (int) \ze\ring::engToBoolean($field[$settingDef]['secret'] ?? 0);
													}
													
													$sql .= "
														ON DUPLICATE KEY UPDATE
															default_value = '". \ze\escape::sql((string) $value). "'";
													
													if ($secretColExists) {
														$sql .= ",
															`secret` = ". (int) \ze\ring::engToBoolean($field[$settingDef]['secret'] ?? 0);
													}
													
													\ze\sql::update($sql);
												}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		\ze\contentAdm::importPhrasesForModule($moduleClassName);


		if ($specialPageChanges) {
			\ze\contentAdm::addNeededSpecialPages();
		}
		
		
		//Recalculate any request vars set by this module's plugins
		if (!empty($desc['path_commands'])) {
			\ze\pluginAdm::setSlideRequestVars(false, false, $moduleId);
		}

		return true;
	}

	//Formerly "scanModulePermissionsInTUIXDescription()"
	public static function scanPermissionsFromDescription($moduleClassName) {
		$perms = [];

		//Loop through every module Setting that a module has in its Admin Box XML file(s)
		if ($dir = \ze::moduleDir($moduleClassName, 'tuix/admin_boxes/', true)) {
	
			$tags = [];
			foreach (scandir(CMS_ROOT. $dir) as $file) {
				if (is_file(CMS_ROOT. $dir. $file) && substr($file, 0, 1) != '.') {
					//Attempt to open and read the XML for an Admin Boxes
					$tagsToParse = \ze\tuix::readFile(CMS_ROOT. $dir. $file);
					\ze\tuix::parse($tags, $tagsToParse, 'admin_boxes', $moduleClassName, $settingGroup = '', $compatibilityClassNames = [], 'zenario_admin');
					unset($tagsToParse);	
				}
			}
	
			if (!empty($tags)) {
				if (isset($tags['admin_boxes']['zenario_admin']['tabs']['permissions']['fields'])
				 && is_array($tags['admin_boxes']['zenario_admin']['tabs']['permissions']['fields'])) {
					foreach ($tags['admin_boxes']['zenario_admin']['tabs']['permissions']['fields'] as $fieldName => &$field) {
						if (is_array($field)) {
				
							if (!empty($field['type']) && $field['type'] == 'checkbox') {
								$perms[$fieldName] = true;
					
							} elseif ((empty($field['type']) || $field['type'] == 'checkboxes') && !empty($field['values'])) {
								foreach ($field['values'] as $perm => &$dummy) {
									$perms[$perm] = true;
								}
							}
						}
					}
				}
			}
		}

		if (empty($perms)) {
			return false;
		} else {
			return $perms;
		}
	}


	//Set up the Content Types of a module
	//Formerly "setupModuleContentTypesFromXMLDescription()"
	public static function setupContentTypesFromDescription($moduleName) {
		$desc = false;
		if (!\ze\moduleAdm::loadDescription($moduleName, $desc)
		 || !$moduleId = \ze\module::id($moduleName)) {
			return false;
		}

		//Add/update any content types
		if (!empty($desc['content_types']) && is_array($desc['content_types'])) {
			foreach($desc['content_types'] as $type) {
				if (!empty($type['content_type_id'])
				 && !empty($type['content_type_name_en'])) {
			
					$sql = "
						INSERT INTO ". DB_PREFIX. "content_types SET
							content_type_id = '". \ze\escape::sql($type['content_type_id']). "',
							content_type_name_en = '". \ze\escape::sql($type['content_type_name_en']). "',
							content_type_plural_en = '". \ze\escape::sql($type['content_type_plural_en'] ?? ''). "',
							writer_field = '". \ze\escape::sql($type['writer_field'] ?? 'hidden'). "',
							description_field = '". \ze\escape::sql($type['description_field'] ?? 'optional'). "',
							keywords_field = '". \ze\escape::sql($type['keywords_field'] ?? 'optional'). "',
							summary_field = '". \ze\escape::sql($type['summary_field'] ?? 'optional'). "',
							release_date_field = '". \ze\escape::sql($type['release_date_field'] ?? 'optional'). "',
							enable_summary_auto_update = ". \ze\ring::engToBoolean($type['enable_summary_auto_update'] ?? 0). ",
							enable_categories = ". \ze\ring::engToBoolean($type['enable_categories'] ?? 0). ",
							is_creatable = ". (isset($type['is_creatable']) ? \ze\ring::engToBoolean($type['is_creatable'] ?? 0) : '1') . ",
							hide_private_item = 1,
							module_id = ". (int) $moduleId. "
						ON DUPLICATE KEY UPDATE
							content_type_name_en = IF (content_type_name_en = '', '". \ze\escape::sql($type['content_type_name_en']). "', content_type_name_en),
							content_type_plural_en = IF (content_type_plural_en = '', '". \ze\escape::sql($type['content_type_plural_en']). "', content_type_plural_en),
							module_id = ". (int) $moduleId;
					\ze\sql::update($sql);
			
					//Make sure a template exists for this Content Type, creating it if it doesn't
					if (!$layoutId = \ze\row::get('layouts', 'layout_id', ['content_type' => $type['content_type_id']])) {
						//Find an HTML Layout to copy; try to pick the most popular one, otherwise just pick the first one
						$sql = "
							SELECT t.*
							FROM ". DB_PREFIX. "content_items AS c
							INNER JOIN ". DB_PREFIX. "content_item_versions AS v
							   ON v.id = c.id
							  AND v.type = c.type
							  AND v.version = c.admin_version
							INNER JOIN ". DB_PREFIX. "layouts AS t
							   ON t.layout_id = v.layout_id
							  AND t.content_type = v.type
							WHERE c.status NOT IN ('hidden','trashed','deleted')
							  AND c.type = 'html'
							GROUP BY t.layout_id
							ORDER BY COUNT(c.tag_id) DESC, t.layout_id
							LIMIT 1";
				
						if (!($result = \ze\sql::select($sql)) || !($layout = \ze\sql::fetchAssoc($result))) {
							$layout = \ze\row::get('layouts', true, ['content_type' => 'html']);
						}
				
						if ($layout) {
							//Work out a slot to put this Plugin into, favouring empty "Main" slots.
							$slotName = \ze\layoutAdm::mainSlotByName($layout['family_name'], $layout['file_base_name']);
					
							//Make a copy of that Layout for the new Content Type
							$layout['templateFamily'] = $layout['family_name'];
							$layout['content_type'] = (string) $type['content_type_id'];
							$layout['name'] = \ze::ifNull((string) ($type['default_template_name'] ?? false), (string) $type['content_type_name_en']);
					
							//T9858, When initialising a new content type, ensure it creates a layout and template file
							$newname = \ze\layoutAdm::generateFileBaseName($layout['name']);
							if (\ze\layoutAdm::copyFiles($layout, $newname)) {
								$layout['file_base_name'] = $newname;
							}
							\ze\layoutAdm::save($layout, $layoutId, $layout['layout_id']);
					
							//Put an instance of this Plugin on that template, if this module uses instances
							//Otherwise put an instance of the WYSIWYG Plugin on that template, if it's running
							$addingEditor = false;
							if ((\ze\ring::engToBoolean($desc['is_pluggable']) && ($addmoduleId = $moduleId))
							 || (($addmoduleId = (\ze\module::id('zenario_wysiwyg_editor'))) && ($addingEditor = true))) {
						
								//Insert this Plugin onto the page
								if ($addingEditor || \ze\ring::engToBoolean($desc['can_be_version_controlled'])) {
									//Prefer a Wireframe Plugin if the Plugin allows it
									\ze\pluginAdm::updateLayoutSlot(0, $slotName, $layout['family_name'], $layoutId, $addmoduleId);
						
								} else {
									//Otherwise set a Reusable Instance there
									if (!$instanceId = \ze\row::get('plugin_instances', 'id', ['module_id' => $addmoduleId, 'content_id' => 0])) {
										//Create a new reusable instance if one does not already exist
										$errors = [];
										\ze\pluginAdm::create(
											$addmoduleId,
											$desc['default_instance_name'],
											$instanceId,
											$errors, $onlyValidate = false, $forceName = true);
									}
							
									\ze\pluginAdm::updateLayoutSlot($instanceId, $slotName, $layout['family_name'], $layoutId, $addmoduleId);
								}
							}
						}
					}
			
					//Ensure a default template is set
					\ze\row::update(
						'content_types',
						['default_layout_id' => $layoutId],
						['content_type_id' => $type['content_type_id'], 'default_layout_id' => 0]);
				}
			}
		}
	}


	//Read the pagination-types of a module from an XML description
	//Formerly "getPluginPaginationTypesFromDescription()"
	public static function getPaginationTypesFromDescription($moduleName, &$paginationTypes) {
	
		$paginationTypes = [];
		$desc = false;
		if (!\ze\moduleAdm::loadDescription($moduleName, $desc)) {
			return false;
		}
	
		//Record any pagination types
		if (!empty($desc['pagination_types']) && is_array($desc['pagination_types'])) {
			foreach ($desc['pagination_types'] as $pagination_type) {
				if (!empty($pagination_type['function_name']) && !empty($pagination_type['label'])) {
					$paginationTypes[$moduleName. '::'. $pagination_type['function_name']] = \ze\admin::phrase($pagination_type['label']);
				}
			}
		}
	
		return true;
	}

}
