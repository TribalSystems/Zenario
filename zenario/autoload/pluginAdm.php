<?php 
/*
 * Copyright (c) 2018, Tribal Limited
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

class pluginAdm {

	//Attempt to add a new instance
	//Formerly "createNewInstance()"
	public static function create($moduleId, $instanceName, &$instanceId, &$errors, $onlyValidate = false, $forceName = false) {
	
		if (!$moduleId) {
			return false;
		}

		//Exit if called somewhere it shouldn't be
		if (!\ze\moduleAdm::isPluggable($moduleId)) {
			exit;
		}
	
		if (!$instanceName) {
			$errors[] = \ze\admin::phrase('_ERROR_INSTANCE_NAME');
			return false;
		}
	
		//Check to see if an instance of that name already exists
		if (\ze\row::exists('plugin_instances', ['name' => $instanceName])) {
			if (!$forceName) {
				$errors[] = \ze\admin::phrase("There's already a plugin with that name (plugin names must be unique).");
				return false;
		
			//Have the option to attempt to force a unique name
			} else {
				$sql = "
					SELECT COUNT(*)
					FROM ". DB_NAME_PREFIX. "plugin_instances
					WHERE name LIKE '". \ze\escape::sql($instanceName). " (%)'";
				$result = \ze\sql::select($sql);
				$row = \ze\sql::fetchRow($result);
			
				$instanceName .= ' ('. ($row[0] + 2). ')';
			}
		}
	
		if ($onlyValidate) {
			return true;
		}
	
		//Insert a new record into the instances table
		$sql = "
			INSERT INTO ". DB_NAME_PREFIX. "plugin_instances (module_id, name)
			SELECT id, '". \ze\escape::sql($instanceName). "'
			FROM ". DB_NAME_PREFIX. "modules
			WHERE id = ". (int) $moduleId;
	
		\ze\sql::update($sql, false, false);  //No need to check the cache as this new instance is not used anywhere yet
		$instanceId = \ze\sql::insertId();
	
		return true;
	}


	//Formerly "fillAdminSlotControlPluginInfo()"
	public static function fillSlotControlPluginInfo($moduleId, $instanceId, $isVersionControlled, $cID, $cType, $level, $isNest, &$info, &$actions) {
	
		$module = \ze\module::details($moduleId);
	
		$skLink = 'zenario/admin/organizer.php?fromCID='. (int) $cID. '&fromCType='. urlencode($cType);
		
		//$modulesLink = '#zenario__modules/panels/modules//' . $moduleId;
		//
		//$mrg = [
		//	'link' => htmlspecialchars($skLink . $modulesLink),
		//	'class_name' => htmlspecialchars($module['class_name']),
		//	'display_name' => htmlspecialchars($module['display_name'])
		//];
		//$info['module_name']['label'] =
		//	\ze\admin::phrase('<a target="_blank" href="[[link]]">[[display_name]]</a>', $mrg);
		
		$info['module_name']['label'] = htmlspecialchars($module['display_name']);
	
		if ($isVersionControlled) {
			$pluginAdminName =
			$ucPluginAdminName = htmlspecialchars($module['display_name']);
		
			//$info['module_name']['css_class'] = 'zenario_slotControl_wireframe';
		
			unset($info['reusable_plugin_name'], $info['reusable_plugin_usage']);
	
		} else {
			unset($info['vc']);
			unset($info['vc_warning']);
			
			if ($isNest) {
				$pluginAdminName = \ze\admin::phrase('nest');
				$ucPluginAdminName = \ze\admin::phrase('Nest');
			} else {
				$pluginAdminName = \ze\admin::phrase('plugin');
				$ucPluginAdminName = \ze\admin::phrase('Plugin');
			}
		
			$pluginsLink = '#zenario__modules/panels/modules/item//' . $moduleId. '//'. $instanceId;
		
			//$info['module_name']['css_class'] = 'zenario_slotControl_reusable';
		
			$mrg = \ze\plugin::details($instanceId);
			$mrg['instance_name'] = htmlspecialchars($mrg['instance_name']);
			$mrg['content_items'] = \ze\pluginAdm::usage($instanceId, $publishedOnly = false, $itemLayerOnly = true);
		
			$getPluginsUsageOnLayouts = \ze\pluginAdm::usageOnLayouts($instanceId);
			$mrg['layouts_active'] = $getPluginsUsageOnLayouts['active'];
			$mrg['layouts_archived'] = $getPluginsUsageOnLayouts['archived'];
		
			$mrg['plugins_link'] = htmlspecialchars($skLink. $pluginsLink);
			$mrg['content_items_link'] = htmlspecialchars($skLink. '#zenario__modules/panels/plugins/item_buttons/usage_item//'. (int) $instanceId. '//');
			$mrg['layouts_link'] = $skLink. htmlspecialchars('#zenario__modules/panels/plugins/item_buttons/usage_layouts//'. (int) $instanceId. '//');
		
			//Not used on any layouts
			if (!$mrg['layouts_active'] && !$mrg['layouts_archived']) {
				//Not used on any content items
				if (!$mrg['content_items']) {
					$info['reusable_plugin_details']['label'] = \ze\admin::phrase(' <a target="_blank" href="[[plugins_link]]">[[instance_name]]</a>', $mrg);
			
				//Used on this content item only
				} elseif ($mrg['content_items'] == 1 && $level == 1) {
					$info['reusable_plugin_details']['label'] = \ze\admin::phrase(' <a target="_blank" href="[[plugins_link]]">[[instance_name]]</a>, used on this content item only', $mrg);
			
				} elseif ($mrg['content_items'] == 1) {
					$info['reusable_plugin_details']['label'] = \ze\admin::phrase(' <a target="_blank" href="[[plugins_link]]">[[instance_name]]</a>, used on <a target="_blank" href="[[content_items_link]]">1 content item</a>', $mrg);
			
				} else {
					$info['reusable_plugin_details']['label'] = \ze\admin::phrase(' <a target="_blank" href="[[plugins_link]]">[[instance_name]]</a>, used on <a target="_blank" href="[[content_items_link]]">[[content_items]] content items</a>', $mrg);
				}
		
			//Just used on this layout
			} elseif (($mrg['layouts_active'] + $mrg['layouts_archived']) == 1 && $level == 2) {
				//Not used on any content items
				if (!$mrg['content_items']) {
					$info['reusable_plugin_details']['label'] = \ze\admin::phrase(' <a target="_blank" href="[[plugins_link]]">[[instance_name]]</a>, used on this layout only', $mrg);
			
				//Used on this content item only
				} elseif ($mrg['content_items'] == 1 && $level == 1) {
					$info['reusable_plugin_details']['label'] = \ze\admin::phrase(' <a target="_blank" href="[[plugins_link]]">[[instance_name]]</a>, used on this layout and on this content item (are you sure you want that setup?)', $mrg);
			
				} elseif ($mrg['content_items'] == 1) {
					$info['reusable_plugin_details']['label'] = \ze\admin::phrase(' <a target="_blank" href="[[plugins_link]]">[[instance_name]]</a>, used on this layout and on <a target="_blank" href="[[content_items_link]]">1 content item</a>', $mrg);
			
				} else {
					$info['reusable_plugin_details']['label'] = \ze\admin::phrase(' <a target="_blank" href="[[plugins_link]]">[[instance_name]]</a>, used on this layout and on <a target="_blank" href="[[content_items_link]]">[[content_items]] content items</a>', $mrg);
				}
		
			} else {
			
				if ($mrg['layouts_archived']) {
					if ($mrg['layouts_active'] == 1) {
						$mrg['layouts'] = \ze\admin::phrase('1 layout (and [[layouts_archived]] archived)', $mrg);
					} else {
						$mrg['layouts'] = \ze\admin::phrase('[[layouts_active]] layouts (and [[layouts_archived]] archived)', $mrg);
					}
				} else {
					if ($mrg['layouts_active'] == 1) {
						$mrg['layouts'] = \ze\admin::phrase('1 layout', $mrg);
					} else {
						$mrg['layouts'] = \ze\admin::phrase('[[layouts_active]] layouts', $mrg);
					}
				}
				
			
				//Not used on any content items
				if (!$mrg['content_items']) {
					$info['reusable_plugin_details']['label'] = \ze\admin::phrase(' <a target="_blank" href="[[plugins_link]]">[[instance_name]]</a>, used on <a target="_blank" href="[[layouts_link]]">[[layouts]]</a>', $mrg);
			
				//Used on this content item only
				} elseif ($mrg['content_items'] == 1 && $level == 1) {
					$info['reusable_plugin_details']['label'] = \ze\admin::phrase(' <a target="_blank" href="[[plugins_link]]">[[instance_name]]</a>, used on <a target="_blank" href="[[layouts_link]]">[[layouts]]</a> and on this content item', $mrg);
			
				} elseif ($mrg['content_items'] == 1) {
					$info['reusable_plugin_details']['label'] = \ze\admin::phrase(' <a target="_blank" href="[[plugins_link]]">[[instance_name]]</a>, used on <a target="_blank" href="[[layouts_link]]">[[layouts]]</a> and on <a target="_blank" href="[[content_items_link]]">1 content item</a>', $mrg);
			
				} else {
					$info['reusable_plugin_details']['label'] = \ze\admin::phrase(' <a target="_blank" href="[[plugins_link]]">[[instance_name]]</a>, used on <a target="_blank" href="[[layouts_link]]">[[layouts]]</a> and on <a target="_blank" href="[[content_items_link]]">[[content_items]] content items</a>', $mrg);
				}
			}
		}
	
		if (isset($actions) && is_array($actions)) {
			foreach ($actions as &$action) {
				if (is_array($action)) {
					if (!empty($action['label'])) {
						$action['label'] = str_replace('~plugin~', $pluginAdminName, str_replace('~Plugin~', $ucPluginAdminName, $action['label']));
					}
					if (!empty($action['onclick'])) {
						$action['onclick'] = str_replace('~plugin~', \ze\escape::js($pluginAdminName), str_replace('~Plugin~', \ze\escape::js($ucPluginAdminName), $action['onclick']));
					}
				}
			}
		}
	}

	//Formerly "setupAdminSlotControls()"
	public static function setupSlotControls(&$slotContents, $ajaxReload) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	


	//List all of the Frameworks available to a Module
	//Formerly "listModuleFrameworks()"
	public static function listFrameworks($className, $limit = 10, $recursive = false) {
		if (!--$limit) {
			return false;
		}
	
		//Search this module's inheritances for frameworks first
		$sql = "
			SELECT dependency_class_name
			FROM ". DB_NAME_PREFIX. "module_dependencies
			WHERE type = 'inherit_frameworks'
			  AND module_class_name = '". \ze\escape::sql($className). "'
			LIMIT 1";
	
		$frameworks = [];
		if (($result = \ze\sql::select($sql))
		 && ($row = \ze\sql::fetchRow($result))) {
			$frameworks = \ze\pluginAdm::listFrameworks($row[0], $limit, true);
		}
	
		//Look through this module's framework directories
		foreach ([
			'zenario/modules/',
			'zenario_extra_modules/',
			'zenario_custom/modules/',
			'zenario_custom/frameworks/'
		] as $moduleDir) {
			if (is_dir($path = CMS_ROOT. $moduleDir. $className. '/frameworks/')) {
				foreach(scandir($path) as $themeName) {
					if (substr($themeName, 0, 1) != '.') {
						//Only list a framework if the .html file is present
						if (is_file($path. $themeName. '/framework.html')) {
							$frameworks[$themeName] = [
								'name' => $themeName,
								'label' => $themeName,
								'path' => $path. $themeName. '/framework.html',
								'filename' => 'framework.html',
								'module_class_name' => $className];
						} elseif (is_file($path. $themeName. '/framework.twig.html')) {
							$frameworks[$themeName] = [
								'name' => $themeName,
								'label' => $themeName,
								'path' => $path. $themeName. '/framework.twig.html',
								'filename' => 'framework.twig.html',
								'module_class_name' => $className];
						}
					}
				}
			}
		}
	
		if (!$recursive) {
			ksort($frameworks);
		
			\ze\tuix::addOrdinalsToTUIX($frameworks);
		}
	
		return $frameworks;
	}

	//Gets a list of pagination options for modules
	//Formerly "paginationOptions()"
	public static function paginationOptions() {
		$options = [];
	
		foreach (\ze\module::runningModules() as $module) {
			if (\ze\moduleAdm::getPaginationTypesFromDescription($module['class_name'], $paginationTypes)) {
				foreach ($paginationTypes as $type => $label) {
					$options[$type] = (string) $label;
				}
			}
		}
	
		asort($options, SORT_STRING);
		return $options;
	}

	//Remove any Version Controlled plugin settings, that are not actually being used for a Content Item
	//Formerly "removeUnusedVersionControlledPluginSettings()"
	public static function removeUnusedVCs($cID, $cType, $cVersion) {
		$slotContents = [];
		\ze\plugin::slotContents($slotContents, $cID, $cType, $cVersion, false, false, false, false, false, false, $runPlugins = false);
	
		$result = \ze\row::query('plugin_instances', ['id', 'slot_name'], ['content_id' => $cID, 'content_type' => $cType, 'content_version' => $cVersion]);
		while ($instance = \ze\sql::fetchAssoc($result)) {
			if ($instance['id'] != ($slotContents[$instance['slot_name']]['instance_id'] ?? false)) {
				\ze\pluginAdm::delete($instance['id']);
			}
		}
	}

	//Copy Wireframe modules from one Content Item to another, as part of creating a new draft.
	//Logic similar to \ze\pluginAdm::removeUnusedVCs() above needs to be used to check that only Settings that are actually being used are copied
	//Formerly "duplicateVersionControlledPluginSettings()"
	public static function duplicateVC($cIDTo, $cIDFrom, $cType, $cVersionTo, $cVersionFrom, $cTypeFrom = false, $slotName = false) {
		$cTypeTo = $cType;
		if (!$cTypeFrom) {
			$cTypeFrom = $cType;
		}
		$slotContents = [];
		\ze\plugin::slotContents($slotContents, $cIDFrom, $cTypeFrom, $cVersionFrom, false, false, false, false, false, false, $runPlugins = false);
	
		$result = \ze\row::query('plugin_instances', ['id', 'slot_name'], ['content_id' => $cIDFrom, 'content_type' => $cTypeFrom, 'content_version' => $cVersionFrom]);
		while ($instance = \ze\sql::fetchAssoc($result)) {
			if (!$slotName || $slotName == $instance['slot_name']) {
				if ($instance['id'] == ($slotContents[$instance['slot_name']]['instance_id'] ?? false)) {
					$eggId = 0;
					\ze\pluginAdm::rename($instance['id'], $eggId, false, true, $cIDTo, $cTypeTo, $cVersionTo, $instance['slot_name']);
				}
			}
		}
	}

	//Duplicate or rename an instance if possible
	//Also has the functionality to convert a Plugin between a Wireframe and a Reusable or vice versa when duplicating
	//Formerly "renameInstance()"
	public static function rename(&$instanceId, &$eggId, $newName, $createNewInstance, $cID = false, $cType = false, $cVersion = false, $slotName = false) {
		$instance = \ze\plugin::details($instanceId);
		
		if ($newName === false) {
			$newName = '';
		}

		if ($createNewInstance) {
			//Copy an instance
			$values = [];
			$values['name'] = $newName;
			$values['framework'] = $instance['framework'];
			$values['css_class'] = $instance['css_class'];
			$values['module_id'] = $instance['module_id'];
	
			if ($cID) {
				$values['content_id'] = $cID;
				$values['content_type'] = $cType;
				$values['content_version'] = $cVersion;
				$values['slot_name'] = $slotName;
			}
	
			$oldInstanceId = $instanceId;
			$instanceId = \ze\row::insert('plugin_instances', $values);
			
			
			//Copy any nested Plugins
			$sql = "
				INSERT INTO ". DB_NAME_PREFIX. "nested_plugins (
					instance_id,
					slide_num,
					ord,
					cols,
					small_screens,
					module_id,
					framework,
					css_class,
					is_slide,
					invisible_in_nav,
					show_back,
					show_embed,
					show_refresh,
					show_auto_refresh,
					auto_refresh_interval,
					request_vars,
					global_command,
					states,
					name_or_title,
					privacy,
					smart_group_id,
					module_class_name,
					method_name,
					param_1,
					param_2,
					always_visible_to_admins
				) SELECT
					". (int) $instanceId. ",
					slide_num,
					ord,
					cols,
					small_screens,
					module_id,
					framework,
					css_class,
					is_slide,
					invisible_in_nav,
					show_back,
					show_embed,
					show_refresh,
					show_auto_refresh,
					auto_refresh_interval,
					request_vars,
					global_command,
					states,
					name_or_title,
					privacy,
					smart_group_id,
					module_class_name,
					method_name,
					param_1,
					param_2,
					always_visible_to_admins
				FROM ". DB_NAME_PREFIX. "nested_plugins
				WHERE instance_id = ". (int) $oldInstanceId;
			\ze\sql::select($sql);  //No need to check the cache as the other statements should clear it correctly
	
	
			//Copy paths in the conductor
			$sql = "
				INSERT INTO ". DB_NAME_PREFIX. "nested_paths (
					instance_id,
					from_state,
					to_state,
					equiv_id,
					content_type,
					commands,
					is_forwards
				) SELECT
					". (int) $instanceId. ",
					from_state,
					to_state,
					equiv_id,
					content_type,
					commands,
					is_forwards
				FROM ". DB_NAME_PREFIX. "nested_paths
				WHERE instance_id = ". (int) $oldInstanceId;
			\ze\sql::select($sql);  //No need to check the cache as the other statements should clear it correctly
	
	
			//Copy any meta info that isn't cached data
			$sql = "
				INSERT INTO ". DB_NAME_PREFIX. "plugin_instance_store (
					instance_id,
					method_name,
					request,
					last_updated,
					store,
					is_cache
				) SELECT
					". (int) $instanceId. ",
					method_name,
					request,
					last_updated,
					store,
					0
				FROM ". DB_NAME_PREFIX. "plugin_instance_store
				WHERE instance_id = ". (int) $oldInstanceId. "
				  AND is_cache = 0";
			\ze\sql::select($sql);  //No need to check the cache as the other statements should clear it correctly
	
	
			//Copy any groups chosen for slides
			$sql = "
				INSERT INTO ". DB_NAME_PREFIX. "group_link
					(`link_from`, `link_from_id`, `link_from_char`, `link_to`, `link_to_id`)
				SELECT
					gsl.link_from,
					np_new.id,
					gsl.link_from_char, gsl.link_to, gsl.link_to_id
				FROM ". DB_NAME_PREFIX. "nested_plugins AS np_old
				INNER JOIN ". DB_NAME_PREFIX. "group_link AS gsl
				   ON gsl.link_from = 'slide'
				  AND gsl.link_from_id = np_old.id
				INNER JOIN ". DB_NAME_PREFIX. "nested_plugins AS np_new
				   ON np_new.instance_id = ". (int) $instanceId. "
				  AND np_old.slide_num = np_new.slide_num
				  AND np_old.ord = np_new.ord
				WHERE np_old.is_slide = 1
				  AND np_old.instance_id = ". (int) $oldInstanceId;
	
			\ze\sql::select($sql);  //No need to check the cache as the other statements should clear it correctly
	
	
			//Copy settings, as well as settings for any nested Plugins
			$sql = "
				INSERT INTO ". DB_NAME_PREFIX. "plugin_settings (
					instance_id,
					egg_id,
					`name`,
					`value`,
					is_content,
					format,
					foreign_key_to,
					foreign_key_id,
					foreign_key_char,
					dangling_cross_references
				) SELECT
					". (int) $instanceId. ",
					IFNULL(np_new.id, 0),
					ps.`name`,
					ps.`value`,";
	
			if ($cID) {
				$sql .= "
					IF (ps.is_content = 'version_controlled_content', 'version_controlled_content', 'version_controlled_setting'),";
			} else {
				$sql .= "
					'synchronized_setting',";
			}
	
			$sql .= "
					ps.format,
					ps.foreign_key_to,
					ps.foreign_key_id,
					ps.foreign_key_char,
					ps.dangling_cross_references
				FROM ". DB_NAME_PREFIX. "plugin_settings AS ps
				LEFT JOIN ". DB_NAME_PREFIX. "nested_plugins AS np_old
				   ON np_old.instance_id = ". (int) $oldInstanceId. "
				  AND np_old.id = ps.egg_id
				LEFT JOIN ". DB_NAME_PREFIX. "nested_plugins AS np_new
				   ON np_new.instance_id = ". (int) $instanceId. "
				  AND np_old.slide_num = np_new.slide_num
				  AND np_old.ord = np_new.ord
				  AND np_old.module_id = np_new.module_id
				WHERE (ps.egg_id != 0 XOR np_new.id IS NULL)
				  AND ps.instance_id = ". (int) $oldInstanceId;
	
			if (!$cID) {
				$sql .= "
				  AND ps.name NOT LIKE '\%%'";
			}
	
			\ze\sql::select($sql);  //No need to check the cache as the other statements should clear it correctly
	
	
			//Copy any CSS for nested plugins
			$sql = "
				SELECT np_old.id AS old_id, np_new.id AS new_id
				FROM ". DB_NAME_PREFIX. "nested_plugins AS np_old
				INNER JOIN ". DB_NAME_PREFIX. "nested_plugins AS np_new
				   ON np_new.instance_id = ". (int) $instanceId. "
				  AND np_old.slide_num = np_new.slide_num
				  AND np_old.ord = np_new.ord
				  AND np_old.module_id = np_new.module_id
				WHERE np_old.instance_id = ". (int) $oldInstanceId;
	
			$result = \ze\sql::select($sql);
			while ($row = \ze\sql::fetchAssoc($result)) {
				//If we were saving from a nested Plugin, convert its id to the new format
				if ($eggId
				 && $eggId == $row['old_id']) {
					$eggId = $row['new_id'];
				}
		
				//Copy any plugin CSS files
				\ze\pluginAdm::manageCSSFile('copy', $oldInstanceId, $row['old_id'], $instanceId, $row['new_id']);
			}
	
	
			\ze\pluginAdm::manageCSSFile('copy', $oldInstanceId, false, $instanceId);
	
			\ze\module::sendSignal('eventPluginInstanceDuplicated', ['oldInstanceId' => $oldInstanceId, 'newInstanceId' => $instanceId]);
	
		} else {
			\ze\row::update('plugin_instances', ['name' => $newName], $instanceId);
		}

		return true;
	}



	//Check how many content items use a Library plugin
	//Formerly "getPluginsUsageOnLayouts()"
	public static function usageOnLayouts($instanceIds) {
	
		$usage = ['active' => 0, 'archived' => 0];
	
		$sql = "
			SELECT l.status, COUNT(DISTINCT l.layout_id)
			FROM ". DB_NAME_PREFIX. "plugin_layout_link AS pll
			INNER JOIN ". DB_NAME_PREFIX. "layouts AS l
			   ON l.layout_id = pll.layout_id
			INNER JOIN ". DB_NAME_PREFIX. "template_slot_link AS s
			   ON s.family_name = l.family_name
			  AND s.file_base_name = l.file_base_name
			  AND s.slot_name = pll.slot_name
			WHERE pll.instance_id IN (". \ze\escape::in($instanceIds, 'numeric'). ")
			GROUP BY l.status";
		
		$result = \ze\sql::select($sql);
		while ($row = \ze\sql::fetchRow($result)) {
			if ($row[0] == 'active') {
				$usage['active'] = $row[1];
			} else {
				$usage['archived'] = $row[1];
			}
		}
	
		return $usage;
	}

	//Check how many content items use a Library plugin
	//Formerly "checkInstancesUsage()"
	public static function usage($instanceIds, $publishedOnly = false, $itemLayerOnly = false, $reportContentItems = false) {
	
		if (!$instanceIds) {
			return 0;
		}
	
		$layoutIds = [];
		if (!$itemLayerOnly) {
			$sql2 = "
				SELECT l.layout_id
				FROM ". DB_NAME_PREFIX. "plugin_layout_link AS pll
				INNER JOIN ". DB_NAME_PREFIX. "layouts AS l
				   ON l.layout_id = pll.layout_id
				INNER JOIN ". DB_NAME_PREFIX. "template_slot_link AS s
				   ON s.family_name = l.family_name
				  AND s.file_base_name = l.file_base_name
				  AND s.slot_name = pll.slot_name
				WHERE pll.instance_id IN (". \ze\escape::in($instanceIds, 'numeric'). ")";
		
			$layoutIds = \ze\sql::fetchValues($sql2);
		
			if (empty($layoutIds)) {
				$itemLayerOnly = true;
			}
		}
	
		if ($reportContentItems) {
			$sql = "
				SELECT c.id, c.type";
		} else {
			$sql = "
				SELECT COUNT(DISTINCT c.tag_id) AS ciu_". (int) $instanceIds. "_". \ze\ring::engToBoolean($publishedOnly). "_". \ze\ring::engToBoolean($itemLayerOnly);
		}
	
		$sql .= "
			FROM ". DB_NAME_PREFIX. "content_items AS c
			INNER JOIN ". DB_NAME_PREFIX. "content_item_versions as v
			   ON c.id = v.id
			  AND c.type = v.type";
	
		if ($publishedOnly) {
			$sql .= "
			  AND v.version = c.visitor_version";
		} else {
			$sql .= "
			  AND v.version IN (c.admin_version, c.visitor_version)";
		}
	
		$sql .= "
			INNER JOIN ". DB_NAME_PREFIX. "layouts AS l
			   ON l.layout_id = v.layout_id";
	
		if ($itemLayerOnly) {
			$sql .= "
				INNER JOIN ". DB_NAME_PREFIX. "plugin_item_link as pil";
		} else {
			$sql .= "
				LEFT JOIN ". DB_NAME_PREFIX. "plugin_item_link as pil";
		}
	
		$sql .= "
		   ON pil.instance_id IN (". \ze\escape::in($instanceIds, 'numeric'). ")
		  AND pil.content_id = c.id
		  AND pil.content_type = c.type
		  AND pil.content_version = v.version";
	
		if ($itemLayerOnly) {
			$sql .= "
				INNER JOIN ". DB_NAME_PREFIX. "template_slot_link as t";
		} else {
			$sql .= "
				LEFT JOIN ". DB_NAME_PREFIX. "template_slot_link as t";
		}
	
		$sql .= "
			   ON t.family_name = l.family_name
			  AND t.file_base_name = l.file_base_name
			  AND t.slot_name = pil.slot_name";
	
		if ($publishedOnly) {
			$sql .= "
			WHERE c.status IN ('published_with_draft', 'published')";
		} else {
			$sql .= "
			WHERE c.status IN ('first_draft', 'published_with_draft', 'hidden', 'hidden_with_draft', 'trashed_with_draft', 'published')";
		}
	
		if (!$itemLayerOnly) {
			$sql .= "
			  AND (
				t.slot_name IS NOT NULL
			   OR
				v.layout_id IN (". \ze\escape::in($layoutIds, 'numeric'). ")
			  )";
		}
	
		if ($reportContentItems) {
			return \ze\sql::fetchAssocs($sql);
	
		} else {
			return \ze\sql::fetchValue($sql);
		}
	}

	//Replace one instance with another
	//Formerly "replacePluginInstance()"
	public static function replace($oldmoduleId = false, $oldInstanceId, $newmoduleId = false, $newInstanceId, $cID = false, $cType = false, $cVersion = false, $slotName = false) {
	
		if ((!$oldmoduleId && !($oldmoduleId = \ze\row::get('plugin_instances', 'module_id', $oldInstanceId)))
		 || (!$newmoduleId && !($newmoduleId = \ze\row::get('plugin_instances', 'module_id', $newInstanceId)))) {
			return;
		}
	
		//Replace the slot
		foreach (['plugin_item_link', 'plugin_layout_link'] as $table) {
			\ze\row::update(
				$table,
				['module_id' => $newmoduleId, 'instance_id' => $newInstanceId],
				['module_id' => $oldmoduleId, 'instance_id' => $oldInstanceId]);
		}
	
		//Remove the item level placement if needed
		if ($cID && $cType && $cVersion && $slotName) {
			$layoutId = \ze\content::layoutId($cID, $cType, $cVersion);
			$templateFamily = \ze\row::get('layouts', 'family_name', $layoutId);
		
			$templateLevelInstanceId = \ze\plugin::idInLayoutSlot($slotName, $templateFamily, $layoutId);
			$templateLevelmoduleId = \ze\module::idInLayoutSlot($slotName, $templateFamily, $layoutId);
		
			if ($templateLevelmoduleId == $newmoduleId && $templateLevelInstanceId == $newInstanceId) {
				\ze\pluginAdm::updateItemSlot('', $slotName, $cID, $cType, $cVersion);
			}
		}
	}


	//Formerly "managePluginCSSFile()"
	public static function manageCSSFile($action, $oldInstanceId, $oldEggId = false, $newInstanceId = false, $newEggId = false) {
	
		$instance = \ze\row::get('plugin_instances', ['module_id', 'content_id'], $oldInstanceId);
	
		//Don't do anything for version controlled plugins
		if (!$instance || $instance['content_id']) {
			return;
		}
	
		//Work out the module's CSS class name - note that if this an egg, we need the egg's class name not the nest's
		if ($oldEggId) {
			$moduleId = \ze\row::get('nested_plugins', 'module_id', $oldEggId);
		} else {
			$moduleId = $instance['module_id'];
		}
		$baseCSSName = \ze\row::get('modules', 'css_class_name', $moduleId);
	
		//Work out file names to delete/add
		$oldFilename = $s1 = $baseCSSName. '_'. $oldInstanceId;
		$newFilename = $r1 = $baseCSSName. '_'. $newInstanceId;
	
		if ($oldEggId) {
			$oldFilename = $s2 = $oldFilename. '_'. $oldEggId;
			$newFilename = $r2 = $newFilename. '_'. $newEggId;
		}
		$oldFilename = '2.'. $oldFilename. '.css';
		$newFilename = '2.'. $newFilename. '.css';
	
		$skins = \ze\row::getArray('skins', ['id', 'family_name', 'name'], ['missing' => 0]);
	
		foreach ($skins as $skin) {
			$skinWritableDir = CMS_ROOT. \ze\content::skinPath($skin['family_name'], $skin['name']). 'editable_css/';
		
			if (file_exists($skinWritableDir. $oldFilename)) {
				switch ($action) {
					case 'delete':
						if (is_writable($skinWritableDir. $oldFilename)) {
							unlink($skinWritableDir. $oldFilename);
						}
						break;
				
					case 'copy':
						if (is_writable($skinWritableDir)
						 && is_readable($skinWritableDir. $oldFilename)
						 && !file_exists($skinWritableDir. $newFilename)) {
						
							$css = file_get_contents($skinWritableDir. $oldFilename);
							$css = preg_replace('/\b'. preg_quote($s1). '\b/', $r1, $css);
						
							if ($oldEggId) {
								$css = preg_replace('/\b'. preg_quote($s2). '\b/', $r2, $css);
							}
						
							file_put_contents($skinWritableDir. $newFilename, $css);
						}
						break;
				}
			}
		}
	}


	//Formerly "deletePluginInstance()"
	public static function delete($instanceId) {
	
		foreach (\ze\row::getArray('nested_plugins', 'id', ['is_slide' => 0, 'instance_id' => $instanceId]) as $eggId) {
			\ze\pluginAdm::manageCSSFile('delete', $instanceId, $eggId);
		}
		\ze\pluginAdm::manageCSSFile('delete', $instanceId);
	
		\ze\row::delete('plugin_instances', $instanceId);
	
		\ze\sql::update("
			DELETE np.*, gsl.*
			FROM ". DB_NAME_PREFIX. "nested_plugins AS np
			LEFT JOIN ". DB_NAME_PREFIX. "group_link AS gsl
			   ON gsl.link_from = 'slide'
			  AND gsl.link_from_id = np.id
			  AND np.is_slide = 1
			WHERE np.instance_id = ". $instanceId);
	
		foreach ([
			'nested_paths', 'plugin_instance_store',
			'plugin_settings', 'plugin_item_link', 'plugin_layout_link'
		] as $table) {
			\ze\row::delete($table, ['instance_id' => $instanceId]);
		}
		\ze\row::delete('inline_images', ['foreign_key_to' => 'library_plugin', 'foreign_key_id' => $instanceId]);
	
		\ze\module::sendSignal('eventPluginInstanceDeleted', ['instanceId' => $instanceId]);
	}

	//Formerly "deleteVersionControlledPluginSettings()"
	public static function deleteVC($cID, $cType, $cVersion) {
		$result = \ze\row::query('plugin_instances', ['id'], ['content_id' => $cID, 'content_type' => $cType, 'content_version' => $cVersion]);
		while ($row = \ze\sql::fetchAssoc($result)) {
			\ze\pluginAdm::delete($row['id']);
		}
	}



	//Update or remove a modules in slots
	//Formerly "updatePluginInstanceInItemSlot()"
	public static function updateItemSlot($instanceId, $slotName, $cID, $cType = false, $cVersion = false, $moduleId = false, $copySwatchUp = false) {
	
		if (!$cVersion) {
			$cVersion = \ze\content::latestVersion($cID, $cType);
		}
	
		if (!$moduleId && $instanceId) {
			$details = \ze\plugin::details($instanceId);
			$moduleId = $details['module_id'];
		}
	
		if ($moduleId || $instanceId !== '') {
			$placementId = \ze\row::set(
				'plugin_item_link',
				[
					'module_id' => $moduleId,
					'instance_id' => $instanceId],
				[
					'slot_name' => $slotName,
					'content_id' => $cID,
					'content_type' => $cType,
					'content_version' => $cVersion]);
		
		} else {
			\ze\row::delete(
				'plugin_item_link',
				[
					'slot_name' => $slotName,
					'content_id' => $cID,
					'content_type' => $cType,
					'content_version' => $cVersion]);
		}
	}
	

	//Formerly "updatePluginInstanceInTemplateSlot()"
	public static function updateLayoutSlot($instanceId, $slotName, $templateFamily = false, $layoutId, $moduleId = false, $cID = false, $cType = false, $cVersion = false, $copySwatchUp = false, $copySwatchDown = false) {
	
		if ($cID && $cType && !$cVersion) {
			$cVersion = \ze\content::latestVersion($cID, $cType);
		}
	
		if (!$moduleId && $instanceId) {
			$details = \ze\plugin::details($instanceId);
			$moduleId = $details['module_id'];
		}
		
		if (!$templateFamily) {
			$templateFamily = \ze\row::get('layouts', 'family_name', $layoutId);
		}
	
		if ($moduleId) {
			$placementId = \ze\row::set(
				'plugin_layout_link',
				[
					'module_id' => $moduleId,
					'instance_id' => $instanceId],
				[
					'slot_name' => $slotName,
					'family_name' => $templateFamily,
					'layout_id' => $layoutId]);
		
		} else {
			\ze\row::delete(
				'plugin_layout_link',
				[
					'slot_name' => $slotName,
					'family_name' => $templateFamily,
					'layout_id' => $layoutId]);
		}
	}

	//Remove the "hide plugin on this content item" option if it has been set
	//Formerly "unhidePlugin()"
	public static function unhide($cID, $cType, $cVersion, $slotName) {
	
		if ($cID && $cType && $cVersion) {
			\ze\row::delete(
				'plugin_item_link',
				[
					'module_id' => 0,
					'instance_id' => 0,
					'content_id' => $cID,
					'content_type' => $cType,
					'content_version' => $cVersion,
					'slot_name' => $slotName]);
		}
	}


	//Formerly "getPluginInstanceUsageStorekeeperDeepLink()"
	public static function usageOrganizerLink($instanceId, $moduleId = false) {
	
		if (!$moduleId) {
			$instance = \ze\plugin::details($instanceId);
			$moduleId = $instance['module_id'];
		}
	
		return \ze\link::absolute(). 'zenario/admin/organizer.php#'.
				'zenario__modules/panels/modules/item//'. (int) $moduleId. '//item_buttons/view_content_items//'. (int) $instanceId. '//';
	}
	
	
	

	//Formerly "getNestDetails()"
	public static function getNestDetails($eggId, $instanceId = false) {

		$sql = "
			SELECT
				slide_num,
				ord,
				instance_id,
				module_id,
				framework,
				css_class,
				is_slide,
				states,
				show_back,
				show_refresh,
				name_or_title,
				cols, small_screens
			FROM ". DB_NAME_PREFIX. "nested_plugins
			WHERE id = ". (int) $eggId;
	
		if ($instanceId !== false) {
			$sql .= "
			  AND instance_id = ". (int) $instanceId;
		}
	
		$result = \ze\sql::select($sql);
		return \ze\sql::fetchAssoc($result);
	}

	//Formerly "getNestedPluginName()"
	public static function nestedPluginName($id) {
		return \ze\row::get('nested_plugins', 'name_or_title', $id);
	}

	//Formerly "conductorEnabled()"
	public static function conductorEnabled($instanceId) {
		return (bool) \ze\row::get('plugin_settings', 'value', ['instance_id' => $instanceId, 'name' => 'enable_conductor', 'egg_id' => 0]);
	}


}