<?php 
/*
 * Copyright (c) 2024, Tribal Limited
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
					FROM ". DB_PREFIX. "plugin_instances
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
		$instance = [
			'module_id' => $moduleId,
			'name' => $instanceName,
			'is_nest' => $moduleId == \ze\module::id('zenario_plugin_nest'),
			'is_slideshow' => $moduleId == \ze\module::id('zenario_slideshow') || $moduleId == \ze\module::id('zenario_slideshow_simple')
		];
		$instanceId = \ze\row::insert('plugin_instances', $instance);
	
		return true;
	}


	//Formerly "fillAdminSlotControlPluginInfo()"
	public static function fillSlotControlPluginInfo($moduleId, $instanceId, $isVersionControlled, $cID, $cType, $level, $isNest, $isSlideshow, &$info, &$actions, &$re_move_place) {


		$pluginType = $isVersionControlled? 99 : ($isSlideshow? 2 : ($isNest? 1 : 0));
		
		foreach ([
			'replace_reusable_on_item_layer' => [1, 0], 'replace_nest_on_item_layer' => [1, 1], 'replace_slideshow_on_item_layer' => [1, 2],
			'replace_reusable_on_layout_layer' => [2, 0], 'replace_nest_on_layout_layer' => [2, 1], 'replace_slideshow_on_layout_layer' => [2, 2]
		] as $buttonName => $details) {
			$buttonLevel = $details[0];
			$buttonPluginType = $details[1];
			
			if (isset($actions[$buttonName])) {
				$button = &$actions[$buttonName];
			
			} elseif (isset($re_move_place[$buttonName])) {
				$button = &$re_move_place[$buttonName];
			
			} else {
				continue;
			}
			
			$isLikeForLikeSwitch = $pluginType === $buttonPluginType;
			$isOverride = $buttonLevel == 1 && $level == 2;
			$isReplace = !$isOverride;
			
			//If this has a different label for replacing like with like, switch to that if we are replacing like with like.
			//Also some options have a different option for replacing (on the same level) vs overriding (on the level below)
			if ($isReplace && $isLikeForLikeSwitch && isset($button['label_replace_like4like'])) {
				$button['label'] = $button['label_replace_like4like'];
			
			} elseif ($isReplace && isset($button['label_replace'])) {
				$button['label'] = $button['label_replace'];
			
			} elseif ($isLikeForLikeSwitch && isset($button['label_like4like'])) {
				$button['label'] = $button['label_like4like'];
			}
			
			unset(
				$button['label_like4like'],
				$button['label_replace'],
				$button['label_replace_like4like']
			);
	
			if ($pluginType === $buttonPluginType) {
				$preselectCurrentChoice = 'true';
			} else {
				$preselectCurrentChoice = 'false';
			}
			
			if (isset($button['onclick'])) {
				$button['onclick'] = str_replace('[[preselectCurrentChoice]]', $preselectCurrentChoice, $button['onclick']);
			}
		}
		
		
		$module = \ze\module::details($moduleId);
	
		$skLink = 'organizer.php?fromCID='. (int) $cID. '&fromCType='. urlencode($cType);
		
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
			
			if ($isSlideshow) {
				$pluginAdminName = \ze\admin::phrase('slideshow');
				$ucPluginAdminName = \ze\admin::phrase('Slideshow');
				$pluginsLink = '#zenario__modules/panels/plugins/refiners/slideshows////'. $instanceId;
			
			} elseif ($isNest) {
				$pluginAdminName = \ze\admin::phrase('nest');
				$ucPluginAdminName = \ze\admin::phrase('Nest');
				$pluginsLink = '#zenario__modules/panels/plugins/refiners/nests////'. $instanceId;
			
			} else {
				$pluginAdminName = \ze\admin::phrase('plugin');
				$ucPluginAdminName = \ze\admin::phrase('Plugin');
				$pluginsLink = '#zenario__modules/panels/modules/item//' . $moduleId. '//'. $instanceId;
			}
		
			//$info['module_name']['css_class'] = 'zenario_slotControl_reusable';
			
			
			$mrg = \ze\plugin::details($instanceId);
			$mrg['instance_name'] = htmlspecialchars($mrg['instance_name']);
			$mrg['plugins_link'] = htmlspecialchars($skLink. $pluginsLink);
			
			$tagId = $cType. '_'. $cID;
			$usage = \ze\pluginAdm::getUsage($instanceId, \ze::$layoutId, $tagId);
			
			if (empty($usage)) {
				$info['reusable_plugin_details']['label'] = \ze\admin::phrase(' <a target="_blank" href="[[plugins_link]]">[[instance_name]]</a>', $mrg);
			} else {
				$usageLinks = [
					'content_items' => 'zenario__modules/panels/plugins/item_buttons/usage_item//'. (int) $instanceId. '//', 
					'layouts' => 'zenario__modules/panels/plugins/item_buttons/usage_layouts//'. (int) $instanceId. '//'
				];
				$mrg['usage_text'] = implode(', ', \ze\miscAdm::getUsageText($usage, $usageLinks, true));
				$info['reusable_plugin_details']['label'] = \ze\admin::phrase(' <a target="_blank" href="[[plugins_link]]">[[instance_name]]</a>; used on [[usage_text]]', $mrg);
			}
		}
		
		if (isset($actions) && is_array($actions)) {
			foreach ($actions as &$action) {
				if (is_array($action)) {
					if (isset($action['label'])) {
						$action['label'] = str_replace('~plugin~', $pluginAdminName, str_replace('~Plugin~', $ucPluginAdminName, $action['label']));
					}
					if (isset($action['onclick'])) {
						$action['onclick'] = str_replace('~plugin~', \ze\escape::js($pluginAdminName), str_replace('~Plugin~', \ze\escape::js($ucPluginAdminName), $action['onclick']));
					}
				}
			}
		}
		if (isset($re_move_place) && is_array($re_move_place)) {
			foreach ($re_move_place as &$action) {
				if (is_array($action)) {
					if (isset($action['label'])) {
						$action['label'] = str_replace('~plugin~', $pluginAdminName, str_replace('~Plugin~', $ucPluginAdminName, $action['label']));
					}
					if (isset($action['onclick'])) {
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
			FROM ". DB_PREFIX. "module_dependencies
			WHERE type = 'inherit_frameworks'
			  AND module_class_name = '". \ze\escape::asciiInSQL($className). "'
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
		\ze\plugin::slotContents(
			$slotContents,
			$cID, $cType, $cVersion,
			$layoutId = false,
			$specificInstanceId = false, $specificSlotName = false, $ajaxReload = false,
			$runPlugins = false);
	
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
		\ze\plugin::slotContents(
			$slotContents,
			$cIDFrom, $cTypeFrom, $cVersionFrom,
			$layoutId = false,
			$specificInstanceId = false, $specificSlotName = false, $ajaxReload = false,
			$runPlugins = false);
	
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
			$values['is_nest'] = $instance['module_id'] == \ze\module::id('zenario_plugin_nest');
			$values['is_slideshow'] = $instance['module_id'] == \ze\module::id('zenario_slideshow') || $instance['module_id'] == \ze\module::id('zenario_slideshow_simple');
	
			$oldInstanceId = $instanceId;
			$instanceId = \ze\row::insert('plugin_instances', $values);
			
			
			//Copy any nested Plugins
			$sql = "
				INSERT INTO ". DB_PREFIX. "nested_plugins (
					instance_id,
					slide_num,
					ord,
					cols,
					small_screens,
					module_id,
					framework,
					css_class,
					makes_breadcrumbs,
					is_slide,
					show_back,
					no_choice_no_going_back,
					show_embed,
					show_refresh,
					show_auto_refresh,
					auto_refresh_interval,
					request_vars,
					hierarchical_var,
					global_command,
					states,
					slide_label,
					set_page_title_with_conductor,
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
					makes_breadcrumbs,
					is_slide,
					show_back,
					no_choice_no_going_back,
					show_embed,
					show_refresh,
					show_auto_refresh,
					auto_refresh_interval,
					request_vars,
					hierarchical_var,
					global_command,
					states,
					slide_label,
					set_page_title_with_conductor,
					privacy,
					smart_group_id,
					module_class_name,
					method_name,
					param_1,
					param_2,
					always_visible_to_admins
				FROM ". DB_PREFIX. "nested_plugins
				WHERE instance_id = ". (int) $oldInstanceId. "
				ORDER BY slide_num, ord";
			\ze\sql::cacheFriendlyUpdate($sql);  //No need to check the cache as the other statements should clear it correctly
	
	
			//Copy paths in the conductor
			$sql = "
				INSERT INTO ". DB_PREFIX. "nested_paths (
					instance_id,
					slide_num,
					from_state,
					to_state,
					equiv_id,
					content_type,
					command,
					is_custom,
					request_vars,
					hierarchical_var,
					descendants,
					is_forwards
				) SELECT
					". (int) $instanceId. ",
					slide_num,
					from_state,
					to_state,
					equiv_id,
					content_type,
					command,
					is_custom,
					request_vars,
					hierarchical_var,
					descendants,
					is_forwards
				FROM ". DB_PREFIX. "nested_paths
				WHERE instance_id = ". (int) $oldInstanceId. "
				ORDER BY from_state, to_state";
			\ze\sql::cacheFriendlyUpdate($sql);  //No need to check the cache as the other statements should clear it correctly
	
	
			//Copy any meta info that isn't cached data
			$sql = "
				INSERT INTO ". DB_PREFIX. "plugin_instance_store (
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
				FROM ". DB_PREFIX. "plugin_instance_store
				WHERE instance_id = ". (int) $oldInstanceId. "
				  AND is_cache = 0
				ORDER BY method_name";
			\ze\sql::cacheFriendlyUpdate($sql);  //No need to check the cache as the other statements should clear it correctly
	
	
			//Copy any groups chosen for slides
			$sql = "
				INSERT INTO ". DB_PREFIX. "group_link
					(`link_from`, `link_from_id`, `link_from_char`, `link_to`, `link_to_id`)
				SELECT
					gsl.link_from,
					np_new.id,
					gsl.link_from_char, gsl.link_to, gsl.link_to_id
				FROM ". DB_PREFIX. "nested_plugins AS np_old
				INNER JOIN ". DB_PREFIX. "group_link AS gsl
				   ON gsl.link_from = 'slide'
				  AND gsl.link_from_id = np_old.id
				INNER JOIN ". DB_PREFIX. "nested_plugins AS np_new
				   ON np_new.instance_id = ". (int) $instanceId. "
				  AND np_old.slide_num = np_new.slide_num
				  AND np_old.ord = np_new.ord
				WHERE np_old.is_slide = 1
				  AND np_old.instance_id = ". (int) $oldInstanceId. "
				ORDER BY 
					gsl.link_from,
					np_new.id,
					gsl.link_from_char, gsl.link_to, gsl.link_to_id";
	
			\ze\sql::cacheFriendlyUpdate($sql);  //No need to check the cache as the other statements should clear it correctly
	
	
			//Copy settings, as well as settings for any nested Plugins
			$sql = "
				INSERT INTO ". DB_PREFIX. "plugin_settings (
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
				FROM ". DB_PREFIX. "plugin_settings AS ps
				LEFT JOIN ". DB_PREFIX. "nested_plugins AS np_old
				   ON np_old.instance_id = ". (int) $oldInstanceId. "
				  AND np_old.id = ps.egg_id
				LEFT JOIN ". DB_PREFIX. "nested_plugins AS np_new
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
			
			$sql .= "
				ORDER BY np_new.id, ps.`name`";
	
			\ze\sql::cacheFriendlyUpdate($sql);  //No need to check the cache as the other statements should clear it correctly
	
	
			//Copy any CSS for nested plugins
			$sql = "
				SELECT np_old.id AS old_id, np_new.id AS new_id
				FROM ". DB_PREFIX. "nested_plugins AS np_old
				INNER JOIN ". DB_PREFIX. "nested_plugins AS np_new
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
	//Formerly "checkInstancesUsage()"
	public static function usage($instanceIds, $publishedOnly = false, $itemLayerOnly = false, $reportContentItems = false, $publicPagesOnly = false) {
	
		if (!$instanceIds) {
			return 0;
		}
	
		$layoutIds = [];
		if (!$itemLayerOnly) {
			$sql2 = "
				SELECT l.layout_id
				FROM ". DB_PREFIX. "plugin_layout_link AS pll
				INNER JOIN ". DB_PREFIX. "layouts AS l
				   ON l.layout_id = pll.layout_id
				INNER JOIN ". DB_PREFIX. "layout_slot_link AS s
				   ON s.layout_id = l.layout_id
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
			FROM ". DB_PREFIX. "content_items AS c
			INNER JOIN ". DB_PREFIX. "content_item_versions as v
			   ON c.id = v.id
			  AND c.type = v.type";
	
		if ($publishedOnly) {
			$sql .= "
			  AND v.version = c.visitor_version";
		} else {
			$sql .= "
			  AND v.version IN (c.admin_version, c.visitor_version)";
		}
		
		if ($publicPagesOnly) {
			$sql .= "
				INNER JOIN ". DB_PREFIX . "translation_chains AS tc
				   ON c.equiv_id = tc.equiv_id
				  AND c.type = tc.type
				  AND tc.privacy = 'public'";
		}
	
		$sql .= "
			INNER JOIN ". DB_PREFIX. "layouts AS l
			   ON l.layout_id = v.layout_id";
	
		if ($itemLayerOnly) {
			$sql .= "
				INNER JOIN ". DB_PREFIX. "plugin_item_link as pil";
		} else {
			$sql .= "
				LEFT JOIN ". DB_PREFIX. "plugin_item_link as pil";
		}
	
		$sql .= "
		   ON pil.instance_id IN (". \ze\escape::in($instanceIds, 'numeric'). ")
		  AND pil.content_id = c.id
		  AND pil.content_type = c.type
		  AND pil.content_version = v.version";
	
		if ($itemLayerOnly) {
			$sql .= "
				INNER JOIN ". DB_PREFIX. "layout_slot_link as t";
		} else {
			$sql .= "
				LEFT JOIN ". DB_PREFIX. "layout_slot_link as t";
		}
	
		$sql .= "
			   ON t.layout_id = l.layout_id
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
	
	
	public static function getUsage($instanceId, $thisLayoutId = null, $thisTagId = null) {
		if (is_array($instanceId)) {
			$instanceIdSQL = ' IN (' . \ze\escape::in($instanceId) . ')';
		} else {
			$instanceIdSQL = ' = ' . (int)$instanceId;
		}

		$layoutCount = $itemCount = 0;
		$usage = [];

		if ($instanceId) {
			//Check if this is used in the sitewide header
			$sql = "
				SELECT 1
				FROM " . DB_PREFIX . "plugin_sitewide_link AS psl
				INNER JOIN " . DB_PREFIX . "layout_slot_link AS lsl
				   ON lsl.slot_name = psl.slot_name
				  AND lsl.is_header = 1
				WHERE psl.instance_id ". $instanceIdSQL. "
				LIMIT 1";
			$usage['swHeader'] = (bool) \ze\sql::numRows($sql);
			
			
			//Check if this is used in the sitewide footer
			$sql = "
				SELECT 1
				FROM " . DB_PREFIX . "plugin_sitewide_link AS psl
				INNER JOIN " . DB_PREFIX . "layout_slot_link AS lsl
				   ON lsl.slot_name = psl.slot_name
				  AND lsl.is_footer = 1
				WHERE psl.instance_id ". $instanceIdSQL. "
				LIMIT 1";
			$usage['swFooter'] = (bool) \ze\sql::numRows($sql);
			
			
			//Count how many layouts use this plugin, and get one example
			$sql = "
				SELECT DISTINCT l.layout_id
				FROM " . DB_PREFIX . "plugin_layout_link AS pll
				INNER JOIN " . DB_PREFIX . "layouts AS l
				   ON l.layout_id = pll.layout_id
				  AND l.status = 'active'
				WHERE pll.instance_id ". $instanceIdSQL;
			
			if ($thisLayoutId !== null) {
				$sql .= "
				ORDER BY l.layout_id = ". (int) $thisLayoutId. " DESC";
			}
			
			$result = \ze\sql::select($sql);
			
			if ($usage['layouts'] = \ze\sql::numRows($result)) {
				$usage['layout'] = \ze\sql::fetchValue($result);
				
				if ($thisLayoutId !== null
				 && $thisLayoutId == $usage['layout']) {
					$usage['layout'] = 'THIS';
				}
			}
			
			
			//Count how many content items use this plugin, and get one example
			$usage['content_items'] = [];
			$sql = "
				SELECT DISTINCT ci.tag_id
				FROM " . DB_PREFIX . "plugin_item_link AS pil
				INNER JOIN " . DB_PREFIX . "content_items AS ci
					ON ci.id = pil.content_id
					AND ci.type = pil.content_type
					AND pil.content_version IN (ci.visitor_version, ci.admin_version)
					AND ci.status IN ('first_draft', 'published_with_draft', 'hidden_with_draft', 'trashed_with_draft', 'published', 'hidden')
					AND (pil.content_version, ci.status) IN (
						(ci.admin_version, 'first_draft'),
						(ci.admin_version, 'hidden_with_draft'),
						(ci.admin_version, 'trashed_with_draft'),
						(ci.admin_version, 'published_with_draft'),
						(ci.visitor_version, 'published_with_draft'),
						(ci.visitor_version, 'published'),
						(ci.admin_version - 1, 'hidden_with_draft'),
						(ci.admin_version, 'hidden')
					)
				INNER JOIN " . DB_PREFIX . "content_item_versions AS viil
					ON viil.id = pil.content_id
					AND viil.type = pil.content_type
					AND viil.version = pil.content_version
				INNER JOIN " . DB_PREFIX . "layouts AS liil
					ON liil.layout_id = viil.layout_id
				INNER JOIN " . DB_PREFIX . "layout_slot_link AS tiil
					ON tiil.layout_id = liil.layout_id
					AND tiil.slot_name = pil.slot_name
				WHERE pil.instance_id " . $instanceIdSQL;
			
			if ($thisTagId !== null) {
				$sql .= "
				ORDER BY ci.tag_id = '". \ze\escape::sql($thisTagId). "' DESC";
			}
			
			$result = \ze\sql::select($sql);
			
			if ($usage['content_items'] = \ze\sql::numRows($result)) {
				$usage['content_item'] = \ze\sql::fetchValue($result);
				
				if ($thisTagId !== null
				 && $thisTagId == $usage['content_item']) {
					$usage['content_item'] = 'THIS';
				}
			}
		}
		return $usage;
	}
	
	
	
	
	

	//Replace one instance with another
	//Formerly "replacePluginInstance()"
	public static function replace($oldmoduleId, $oldInstanceId, $newmoduleId, $newInstanceId, $cID = false, $cType = false, $cVersion = false, $slotName = false) {
	
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
		
			$templateLevelInstanceId = \ze\plugin::idInLayoutSlot($slotName, $layoutId, false);
			$templateLevelmoduleId = \ze\module::idInLayoutSlot($slotName, $layoutId);
		
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
	
		$skins = \ze\row::getAssocs('skins', ['id', 'name'], ['missing' => 0]);
	
		foreach ($skins as $skin) {
			$skinWritableDir = CMS_ROOT. 'zenario_custom/skins/'. $skin['name']. '/editable_css/';
		
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
	
		foreach (\ze\row::getValues('nested_plugins', 'id', ['is_slide' => 0, 'instance_id' => $instanceId]) as $eggId) {
			\ze\pluginAdm::manageCSSFile('delete', $instanceId, $eggId);
		}
		\ze\pluginAdm::manageCSSFile('delete', $instanceId);
	
		\ze\row::delete('plugin_instances', $instanceId);
	
		\ze\sql::update("
			DELETE np.*, gsl.*
			FROM ". DB_PREFIX. "nested_plugins AS np
			LEFT JOIN ". DB_PREFIX. "group_link AS gsl
			   ON gsl.link_from = 'slide'
			  AND gsl.link_from_id = np.id
			  AND np.is_slide = 1
			WHERE np.instance_id = ". (int) $instanceId);
	
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
			
			//If trying to add a new plugin, check this slot exists
			$key = [
				'layout_id' => ($layoutId = \ze\content::layoutId($cID, $cType, $cVersion)),
				'slot_name' => $slotName
			];
		
			//Allow site-wide slots to be hidden and unhidden on a per-content item basis,
			//but don't allow site-wide slots to be replace by a plugin on a content item.
			if ($moduleId || $instanceId) {
				$key['is_header'] = 0;
				$key['is_footer'] = 0;
			}
		
			if (!\ze\row::exists('layout_slot_link', $key)) {
				return false;
			}
			
			
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
		
		return true;
	}
	

	//Formerly "updatePluginInstanceInTemplateSlot()"
	public static function updateLayoutSlot($instanceId, $slotName, $layoutId, $moduleId = false) {
	
		if (!$moduleId && $instanceId) {
			$details = \ze\plugin::details($instanceId);
			$moduleId = $details['module_id'];
		}
		
		if ($moduleId) {
			//When trying to add a plugin, check this slot exists, and isn't a site-wide slot
			if (!\ze\row::exists('layout_slot_link', [
				'layout_id' => $layoutId,
				'slot_name' => $slotName,
				'is_header' => 0,
				'is_footer' => 0
			])) {
				return false;
			}
			
			$placementId = \ze\row::set(
				'plugin_layout_link',
				[
					'module_id' => $moduleId,
					'instance_id' => $instanceId],
				[
					'slot_name' => $slotName,
					'layout_id' => $layoutId]);
		
		} else {
			\ze\row::delete(
				'plugin_layout_link',
				[
					'slot_name' => $slotName,
					'layout_id' => $layoutId]);
		}
		
		return true;
	}
	

	//Formerly "updatePluginInstanceInTemplateSlot()"
	public static function updateSitewideSlot($slotName, $instanceId, $moduleId = false) {
	
		if (!$moduleId && $instanceId) {
			$details = \ze\plugin::details($instanceId);
			$moduleId = $details['module_id'];
		}
		
		if ($moduleId) {
			//When trying to add a plugin, check this slot exists, and is a site-wide slot
			$lsl = \ze\row::get('layout_slot_link', ['is_header', 'is_footer'], [
				'slot_name' => $slotName
			]);
			if (!$lsl || (!$lsl['is_header'] && !$lsl['is_footer'])) {
				return false;
			}
			
			$placementId = \ze\row::set(
				'plugin_sitewide_link',
				[
					'module_id' => $moduleId,
					'instance_id' => $instanceId],
				[
					'slot_name' => $slotName]);
		
		} else {
			\ze\row::delete(
				'plugin_sitewide_link',
				[
					'slot_name' => $slotName]);
		}
		
		return true;
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
	
		return \ze\link::absolute(). 'organizer.php#'.
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
				slide_label,
				cols, small_screens
			FROM ". DB_PREFIX. "nested_plugins
			WHERE id = ". (int) $eggId;
	
		if ($instanceId !== false) {
			$sql .= "
			  AND instance_id = ". (int) $instanceId;
		}
	
		$result = \ze\sql::select($sql);
		return \ze\sql::fetchAssoc($result);
	}

	//Formerly "getNestedPluginName()"
	public static function nestedPluginName($eggId, $instanceId = null, $moduleId = null, $moduleClassName = null) {
		if ($instanceId === null
		 || ($moduleId === null && $moduleClassName === null)) {
			$egg = \ze\row::get('nested_plugins', ['instance_id', 'module_id'], $eggId);
			$instanceId = $egg['instance_id'];
			$moduleId = $egg['module_id'];
		}
		if ($moduleClassName === null) {
			$moduleClassName = \ze\module::className($moduleId);
		}
		if (\ze\module::inc($moduleClassName)) {
			return call_user_func([$moduleClassName, 'nestedPluginName'], $eggId, $instanceId, $moduleClassName);
		}
	}

	//Formerly "conductorEnabled()"
	public static function conductorEnabled($instanceId) {
		return \ze\plugin::setting('nest_type', $instanceId) == 'conductor';
	}
	
	
	
	
	
	
	
	
	//Update the request vars that are stored against each slide
	//You can call this for a specific slide, all slides in a specific nest, every nest & slide on a site that uses a specific plugin,
	//or for every nest & slide on a site.
	public static function setSlideRequestVars($instanceId = false, $moduleId = false) {
		
		$prevInstance = -1;
		$moduleCommands = [];
		$slideRequestVars = [];
		
		$knownCommands = [
			'back' => ['hVar' => '', 'rVars' => ''],
			'submit' => ['hVar' => '', 'rVars' => '']
		];
		
		$sql = '
			SELECT
				slide.instance_id, slide.id AS slide_id,
				egg.id AS egg_id, egg.module_id, egg.makes_breadcrumbs,
				ps.value AS mode
			FROM '. DB_PREFIX. 'nested_plugins AS slide
			INNER JOIN '. DB_PREFIX. 'nested_plugins AS egg
			   ON egg.instance_id = slide.instance_id
			  AND egg.slide_num = slide.slide_num
			  AND egg.is_slide = 0
			LEFT JOIN '. DB_PREFIX. 'plugin_settings AS ps
			   ON ps.instance_id = egg.instance_id
			  AND ps.egg_id = egg.id
			  AND ps.name = \'mode\'
			  AND ps.value IS NOT NULL
			  AND ps.value != \'\'
			WHERE slide.is_slide = 1
			  AND slide.`states` != \'\'';
		
		if ($instanceId) {
			$sql .= '
			  AND slide.instance_id = '. (int) $instanceId;
		
		} elseif ($moduleId) {
			$sql .= '
			  AND slide.instance_id IN (
				SELECT DISTINCT module.instance_id
				FROM '. DB_PREFIX. 'nested_plugins AS module
				WHERE module.module_id = '. (int) $moduleId. '
			  )';
		}
		
		$sql .= '
			ORDER BY slide.instance_id, slide.id';
		
		
		foreach (\ze\sql::select($sql) as $egg) {
			
			$instanceId = $egg['instance_id'];
			$slideId = $egg['slide_id'];
			$moduleId = $egg['module_id'];
			
			if ($prevInstance !== -1
			 && $prevInstance != $instanceId) {
				\ze\pluginAdm::calcConductorHierarchy($prevInstance, $knownCommands);
			}
			$prevInstance = $instanceId;
			
			
			
			//Get the details for this module, if not already loaded
			if (!isset($moduleCommands[$moduleId])) {
				$tags = [];
				if ((\ze\moduleAdm::loadDescription(\ze\module::className($moduleId), $tags))
				 && !empty($tags['path_commands'])) {
					
					//Note down the commands and their variables for each module
					foreach ($tags['path_commands'] as $command => $commandDetails) {
						
						$knownCommands[$command] = [
							'hVar' => $commandDetails['hierarchical_var'] ?? '',
							'rVars' => implode(',', $commandDetails['request_vars'] ?? [])
						];
					}
					
					$moduleCommands[$moduleId] = $tags['path_commands'];
				} else {
					$moduleCommands[$moduleId] = [];
				}
			}
			
			//Check if this module/mode can generate smart breadcrumbs
			$canMakeBreadcrumbs = $egg['mode'] && !empty($moduleCommands[$moduleId][$egg['mode']]['able_to_generate_smart_breadcrumbs']);
			
			//Update the row if it was wrong in the database
			if ($canMakeBreadcrumbs XOR ((bool) $egg['makes_breadcrumbs'])) {
				\ze\row::update('nested_plugins', ['makes_breadcrumbs' => (int) $canMakeBreadcrumbs], $egg['egg_id']);
			}
		}
		
		if ($prevInstance !== -1) {
			\ze\pluginAdm::calcConductorHierarchy($prevInstance, $knownCommands);
		}
	}

	//Remove all of the variables such as dataPoolId1 and dataPoolId2, if they've been previously added
	private static function cchTrimReqVars($slide) {
		$out = [];
		
		foreach (\ze\ray::explodeAndTrim($slide['request_vars']) as $var) {
			if (!preg_match('@\d@', substr($var, -1, 1))) {
				$out[] = $var;
			}
		}
		
		return $out;
	}

	//Add some hierarchy information to conductor
	public static function calcConductorHierarchy($instanceId, $knownCommands = null) {
		
		//Update the hierarchical_vars in the nested paths, if this function was chain-called from
		//the setSlideRequestVars() above. (Otherwise assume this is already correct and doesn't need updating.)
		if (!is_null($knownCommands)) {
			\ze\sql::update('
				UPDATE '. DB_PREFIX. 'nested_paths
				SET is_custom = 1,
					hierarchical_var = \'\'
				WHERE instance_id = '. (int) $instanceId. '
			');
			
			foreach ($knownCommands as $command => $details) {
				\ze\sql::update('
					UPDATE '. DB_PREFIX. 'nested_paths
					SET is_custom = 0,
						request_vars = \''. \ze\escape::sql($details['rVars']). '\',
						hierarchical_var = \''. \ze\escape::sql($details['hVar']). '\'
					WHERE command = \''. \ze\escape::sql($command). '\'
					  AND instance_id = '. (int) $instanceId. '
				');
			}
		}
		
		
		$level = 1;
		$map = [];
		$states = [];
		$slides = [];


		//Look for slides with no back links going from them. These are top-level slides
		$sql = '
			SELECT slide.id, slide.slide_num, slide.slide_label, slide.states
			FROM '. DB_PREFIX. 'nested_plugins AS slide
			LEFT JOIN '. DB_PREFIX. 'nested_paths AS path
			   ON path.instance_id = slide.instance_id
			  AND path.command IN (\'back\', \'submit\')
			  AND FIND_IN_SET(path.from_state, slide.states)
			WHERE slide.is_slide = 1
			  AND slide.instance_id = '. (int) $instanceId. '
			  AND path.instance_id IS NULL';
		
		//Note down some info on each
		foreach(\ze\sql::fetchAssocs($sql) as $slide) {
			
			$slide['request_vars'] =
			$slide['untouched_request_vars'] = [];
			
			$slide['depth'] = 0;
			$slide['descendants'] = [];
			$slide['level'] = $level;
			$slide['parents'] = '';
			$slide['hierarchical_var'] = '';
			
			foreach (\ze\ray::explodeAndTrim($slide['states']) as $state) {
				$state = $slide['states'];
				$states[$state] = $slide;
			}
			
			$slides[$slide['id']] = $slide;
		}
		
		
		//Do one sweep looking for slides with back links correctly set,
		//then a second sweep looking for slides with broken back links
		foreach ([false, true] as $brokenBackLinks) {
		
			//Keep looking for states that lead from the states we've already found
			$progress = true;
			while ($progress) {
				$progress = false;
				++$level;
	
				foreach ($states as $fromState => $fromSlide) {
				
					$sql = '
						SELECT slide.id, slide.slide_num, slide.slide_label, slide.states, path.request_vars, path.hierarchical_var, path.to_state
						FROM '. DB_PREFIX. 'nested_paths AS path
						INNER JOIN '. DB_PREFIX. 'nested_plugins AS slide
						   ON path.instance_id = slide.instance_id
						  AND FIND_IN_SET(path.to_state, slide.states)
						'. ($brokenBackLinks? 'LEFT' : 'INNER'). ' JOIN '. DB_PREFIX. 'nested_paths AS back
						   ON path.instance_id = back.instance_id
						  AND path.to_state = back.from_state
						  AND path.from_state = back.to_state
						  AND back.equiv_id = 0
						  AND back.command = \'back\'
						WHERE path.instance_id = '. (int) $instanceId. '
						  AND path.from_state = \''. \ze\escape::sql($fromState). '\'
						  AND path.command NOT IN (\'back\', \'submit\')
						  AND path.to_state NOT IN ('. \ze\escape::in(array_keys($states), 'sql'). ')
						  AND path.equiv_id = 0';
					
					if ($brokenBackLinks) {
						$sql .= '
						  AND back.instance_id IS NULL';
					}
		
					foreach (\ze\sql::fetchAssocs($sql) as $slide) {
						$toState = $slide['to_state'];
					
						$slide['request_vars'] =
						$slide['untouched_request_vars'] = self::cchTrimReqVars($slide);
					
						//Build up information about the descendants and parents we've seen so far
						$slide['depth'] = 0;
						$slide['descendants'] = [];
						$slide['level'] = $level;
			
						if ($level == 2) {
							$slide['parents'] = $fromState;
						} else {
							$slide['parents'] = $states[$fromState]['parents']. ','. $fromState;
						}
					
						//Get info on the slides above this one in the hierarchy
						$hVarCounts = [];
					
						if ($hVar = $slide['hierarchical_var']) {
							$hVarCounts[$hVar] = 1;
						}
					
						foreach (\ze\ray::explodeAndTrim($slide['parents']) as $parent) {
							$states[$parent]['descendants'][] = $toState;
							$states[$parent]['depth'] = $level;
						
							//Work out information on hierarchical variables, e.g. dataPoolId1 and so on
							if (!empty($states[$parent]['hierarchical_var'])) {
								$hVar = $states[$parent]['hierarchical_var'];
							
								if (isset($hVarCounts[$hVar])) {
									++$hVarCounts[$hVar];
								} else {
									$hVarCounts[$hVar] = 1;
								}
							}
						
							//Add any variable defined on the parents to the children as well,
							//just in case those plugins missed defining them.
							$slide['request_vars'] = array_merge($slide['request_vars'], $states[$parent]['untouched_request_vars']);
						}
						$slide['request_vars'] = array_unique($slide['request_vars']);
					
						//Add the correct hierarchical variable to each slide's request variables
						foreach ($hVarCounts as $hVar => $count) {
							$slide['request_vars'][] = $hVar. $count;
						
							//Also remove the base variable
							$slide['request_vars'] = array_diff($slide['request_vars'], [$hVar]);
						}
						
						$slide['hierarchical_var'] = $hVar;
					
					
					
						$states[$toState] = $slide;
						$slides[$slide['id']] = $slide;
					
					
						$progress = true;
					}
				}
			}
		}
		
	
		//For every back link, note down the states that are below that link.
		//This is so when the back link is followed by the conductor, any variables from the states below can be cleared.
		\ze\sql::update('
			UPDATE '. DB_PREFIX. 'nested_paths AS back
			SET back.descendants = \'\'
			WHERE back.instance_id = '. (int) $instanceId
		);
		
		foreach ($states as $state => $slide) {
			\ze\sql::update('
				UPDATE '. DB_PREFIX. 'nested_paths AS back
				SET back.descendants = \''. \ze\escape::sql(implode(',', $slide['descendants'])). '\'
				WHERE back.from_state = \''. \ze\escape::sql($state). '\'
				  AND back.command = \'back\'
				  AND back.instance_id = '. (int) $instanceId
			);
		}
		
		//Update the request_vars on each slide, with the hierarchical variables added
		foreach ($slides as $slideId => $slide) {
			\ze\sql::update('
				UPDATE '. DB_PREFIX. 'nested_plugins
				SET request_vars = \''. \ze\escape::sql(implode(',', $slide['request_vars'])). '\',
					hierarchical_var = \''. \ze\escape::sql($slide['hierarchical_var']). '\'
				WHERE id = '. (int) $slideId
			);
		}
		
		
		//N.b. the parents, depth and level variables calculated are currently not used anywhere
	}

	//In admin mode, show an error if the plugin could not run due to user permissions
	public static function showInitialisationError($slot, $status) {
		
		if ($status === ZENARIO_401_NOT_LOGGED_IN || $status === ZENARIO_403_NO_PERMISSION) {
		
			//N.b. as a convience feature, I'll allow for plugin devs to send either a 401 or a 403 error,
			//and pick the correct message here
			if (\ze\user::id()) {
				echo '<em>'. \ze\admin::phrase('You do not have permission to view this plugin, or there is a problem with its settings.'). '</em>';
			} else {
				echo '<em>'. \ze\admin::phrase('You need to be logged in as an extranet user to view this plugin.'). '</em>';
			}
	
		} elseif (!empty($slot['error'])) {
			echo '<em>'. htmlspecialchars($slot['error']). '</em>';
	
		} elseif (empty($slot['module_id'])) {
			echo \ze\admin::phrase('[Empty Slot]');
		}
	}

}