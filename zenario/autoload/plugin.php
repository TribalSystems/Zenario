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

class plugin {




	//Get a list of every plugin instance currently running on a page
	//Formerly "getSlotContents()"
	public static function slotContents(
		&$slotContents,
		$cID, $cType, $cVersion,
		$layoutId = false, $templateFamily = false, $templateFileBaseName = false,
		$specificInstanceId = false, $specificSlotName = false, $ajaxReload = false,
		$runPlugins = true, $exactMatch = false, $overrideSettings = false, $overrideFrameworkAndCSS = false
	) {
	
		if ($layoutId === false) {
			$layoutId = \ze\content::layoutId($cID, $cType, $cVersion);
		}
	
		if ($templateFamily === false) {
			$templateFamily = \ze\row::get('layouts', 'family_name', $layoutId);
		}
	
		if ($templateFileBaseName === false) {
			$templateFileBaseName = \ze\row::get('layouts', 'file_base_name', $layoutId);
		}
	
	
		$slots = [];
		$slotContents = [];
		$modules = \ze\module::runningModules();
	
		$whereSlotName = '';
		if ($specificSlotName && !$specificInstanceId) {
			$whereSlotName = "
				  AND slot_name = '". \ze\escape::sql($specificSlotName). "'";
		}
	
		//Look for every plugin instance on the current page, prioritising item level
		//over Layout level, and Layout level over Template Family level.
		$sql = "
			SELECT
				pi.slot_name,
				pi.module_id,
				pi.instance_id,
				vcpi.id AS vcpi_id,
				tsl.slot_name IS NOT NULL as `exists`,
				pi.level
			FROM (
				SELECT slot_name, module_id, instance_id, id, 'template' AS type, 2 AS level
				FROM ". DB_NAME_PREFIX. "plugin_layout_link
				WHERE family_name = '". \ze\escape::sql($templateFamily). "'
				  AND layout_id = ". (int) $layoutId.
				  $whereSlotName;
	
		if ($cID) {
			$sql .= "
			  UNION
				SELECT slot_name, module_id, instance_id, id, 'item' AS type, 1 AS level
				FROM ". DB_NAME_PREFIX. "plugin_item_link
				WHERE content_id = ". (int) $cID. "
				  AND content_type = '". \ze\escape::sql($cType). "'
				  AND content_version = ". (int) $cVersion.
				  $whereSlotName;
		}
	
		$sql .= "
			) AS pi";
	
		//Don't show missing slots, except for Admins with the correct permissions
		if (!(\ze\priv::check('_PRIV_MANAGE_ITEM_SLOT') || \ze\priv::check('_PRIV_MANAGE_ITEM_SLOT')) && !($specificInstanceId || $specificSlotName)) {
			$sql .= "
			INNER JOIN ". DB_NAME_PREFIX. "template_slot_link AS tsl";
		} else {
			$sql .= "
			LEFT JOIN ". DB_NAME_PREFIX. "template_slot_link AS tsl";
		}
	
		$sql .= "
			   ON tsl.family_name = '". \ze\escape::sql($templateFamily). "'
			  AND tsl.file_base_name = '". \ze\escape::sql($templateFileBaseName). "'
			  AND tsl.slot_name = pi.slot_name";
	
		$sql .= "
			LEFT JOIN ". DB_NAME_PREFIX. "plugin_instances AS vcpi
			   ON vcpi.module_id = pi.module_id
			  AND vcpi.content_id = ". (int) $cID. "
			  AND vcpi.content_type = '". \ze\escape::sql($cType). "'
			  AND vcpi.content_version = ". (int) $cVersion. "
			  AND vcpi.slot_name = pi.slot_name
			  AND pi.instance_id = 0
			WHERE TRUE";
	
		if ($exactMatch && $specificInstanceId) {
			$sql .= "
			  AND IFNULL(vcpi.id, pi.instance_id) = ". (int) $specificInstanceId. "";
		}
		if ($exactMatch && $specificSlotName) {
			$sql .= "
			  AND pi.slot_name = '". \ze\escape::sql($specificSlotName). "'";
		}
	
		$sql .= "
			ORDER BY";
		
		if (!$exactMatch && $specificInstanceId) {
			$sql .= "
				IFNULL(vcpi.id, pi.instance_id) = ". (int) $specificInstanceId. " DESC,";
		}
		if (!$exactMatch && $specificSlotName) {
			$sql .= "
				pi.slot_name = '". \ze\escape::sql($specificSlotName). "' DESC,";
		}
	
		$sql .= "
				tsl.slot_name IS NOT NULL DESC,
				tsl.ord,";
	
		if ($specificInstanceId || $specificSlotName) {
			$sql .= "
				pi.level ASC,
				pi.slot_name
			LIMIT 1";
		
			$checkOpaqueRulesAreValid = false;
	
		} else {
			$sql .= "
				pi.level DESC,
				pi.slot_name";
		
			$checkOpaqueRulesAreValid = true;
		}
	
	
		$result = \ze\sql::select($sql);
		while($row = \ze\sql::fetchAssoc($result)) {
		
			//Don't allow Opaque missing slots to count as missing slots
			if (empty($row['module_id']) && !$row['exists']) {
				continue;
			}
		
			//Check if this is a version-controlled Plugin instance
			$isVersionControlled = false;
			if ($row['module_id'] != 0 && $row['instance_id'] == 0) {
				$isVersionControlled = true;
			
				//Check if an instance has been inserted for this Content Item
				if ($row['vcpi_id']) {
					$row['instance_id'] = $row['vcpi_id'];
			
				//Otherwise, create and insert a new version controlled instance
				} elseif ($runPlugins) {
					$row['instance_id'] =
						\ze\plugin::vcId($cID, $cType, $cVersion, $row['slot_name'], $row['module_id']);
				}
			}
		
			//The "Opaque" option is a special case; let it through without an "is running" check
			if ($row['module_id'] == 0) {
				//The "Opaque" option is used to hide plugins on the layout layer on specific pages.
				//It's not valid if it's not actually covering anything up!
				if ($checkOpaqueRulesAreValid && empty($slotContents[$row['slot_name']])) {
					continue;
				}
			
				$slotContents[$row['slot_name']] = ['instance_id' => 0, 'module_id' => 0];
				$slotContents[$row['slot_name']]['error'] = \ze\admin::phrase('[Plugin hidden on this content item]');
				$slotContents[$row['slot_name']]['level'] = $row['level'];
				$slots[$row['slot_name']] = true;
		
			//Otherwise, if the instance is running, allow it to be added to the page
			} elseif (!empty($modules[$row['module_id']])) {
				$slotContents[$row['slot_name']] = $modules[$row['module_id']];
				$slotContents[$row['slot_name']]['level'] = $row['level'];
				$slotContents[$row['slot_name']]['module_id'] = $row['module_id'];
				$slotContents[$row['slot_name']]['instance_id'] = $row['instance_id'];
				$slotContents[$row['slot_name']]['css_class'] = $modules[$row['module_id']]['css_class_name'];
			
				if ($isVersionControlled) {
					$slotContents[$row['slot_name']]['content_id'] = $cID;
					$slotContents[$row['slot_name']]['content_type'] = $cType;
					$slotContents[$row['slot_name']]['content_version'] = $cVersion;
					$slotContents[$row['slot_name']]['slot_name'] = $row['slot_name'];
				}
			
				$slotContents[$row['slot_name']]['cache_if'] = [];
				$slotContents[$row['slot_name']]['clear_cache_by'] = [];
			
				$slots[$row['slot_name']] = true;
			}
		}
	
		$edition = \ze::$edition;
	
		//Attempt to initialise each plugin on the page
		if ($runPlugins) {
			foreach ($slots as $slotName => $dummy) {
				if (!empty($slotContents[$slotName]['class_name']) && !empty($slotContents[$slotName]['instance_id'])) {
					$moduleClassName = $slotContents[$slotName]['class_name'];
		
					if (!isset(\ze::$modulesOnPage[$moduleClassName])) {
						\ze::$modulesOnPage[$moduleClassName] = [];
					}
					\ze::$modulesOnPage[$moduleClassName][] = $slotName;
				}
			}
				
			foreach ($slots as $slotName => $dummy) {
				if (!empty($slotContents[$slotName]['class_name']) && !empty($slotContents[$slotName]['instance_id'])) {
					
					$thisSettings = $thisFrameworkAndCSS = false;
					if ($overrideSettings !== false && $slotName == \ze::request('slotName')) {
						$thisSettings = $overrideSettings;
					}
					if ($overrideFrameworkAndCSS !== false && $slotName == \ze::request('slotName')) {
						$thisFrameworkAndCSS = $overrideFrameworkAndCSS;
					}
					
					$edition::loadPluginInstance(
						$slotContents, $slotName,
						$cID, $cType, $cVersion,
						$layoutId, $templateFamily, $templateFileBaseName,
						$specificInstanceId, $specificSlotName, $ajaxReload,
						$runPlugins, $thisSettings, $thisFrameworkAndCSS);
		
				} elseif (!empty($slotContents[$slotName]['level'])) {
					\ze\plugin::setupNewBaseClass($slotName);
			
					//Treat the case of hidden (item layer) and empty (layout layer) as just empty,
					//but if there is something hidden at the item layer and there is a plugin
					//at the layout layer, show a special message
					if (!$checkOpaqueRulesAreValid
					 && $slotContents[$slotName]['level'] == 1
					 && $layoutId
					 && \ze\row::exists('plugin_layout_link', ['slot_name' => $slotName, 'layout_id' => $layoutId])) {
						$slotContents[$slotName]['error'] = \ze\admin::phrase('[Plugin hidden on this content item]');
					}
				}
			}
		}
	}





	//Formerly "getVersionControlledPluginInstanceId()"
	public static function vcId($cID, $cType, $cVersion, $slotName, $moduleId) {
	
	
		if ($cID == 0 || $cID == -1) {
			return $cID;
		}
	
		$ids = ['module_id' => $moduleId, 'content_id' => $cID, 'content_type' => $cType, 'content_version' => $cVersion, 'slot_name' => $slotName];

		if (!$instanceId = \ze\row::get('plugin_instances', 'id', $ids)) {
			$instanceId = \ze\row::insert('plugin_instances', $ids);
		}
	
		return $instanceId;
	}



	//Activate and setup a plugin
	//Note that the function canActivateModule() or equivalent should be called on the plugin's name before calling \ze\plugin::setInstance(), loadPluginInstance() or \ze\plugin::initInstance()
	//Formerly "setInstance()"
	public static function setInstance(&$instance, $cID, $cType, $cVersion, $slotName, $checkForErrorPages = false, $overrideSettings = false, $eggId = 0, $slideId = 0, $beingDisplayed = true) {
	
		$missingPlugin = false;
		if (!\ze\module::incWithDependencies($instance['class_name'], $missingPlugin)) {
			$instance['class'] = false;
			return false;
		}
	
		$instance['class'] = new $instance['class_name'];
		
		$instance['class']->setInstance(
			[
				$cID, $cType, $cVersion, $slotName,
				($instance['instance_name'] ?? false), $instance['instance_id'],
				$instance['class_name'], $instance['vlp_class'],
				$instance['module_id'],
				$instance['default_framework'], $instance['framework'],
				$instance['css_class'],
				($instance['level'] ?? false), !empty($instance['content_id'])
			], $overrideSettings, $eggId, $slideId, $beingDisplayed
		);
	}

	//Work out whether we are displaying this Plugin.
	//Run the plugin's own initalisation routine. If it returns true, then display the plugin.
	//(But note that modules are always displayed in admin mode.)
	//Formerly "initPluginInstance()"
	public static function initInstance(&$instance) {
		if (!($instance['init'] = $instance['class']->init()) && !(\ze\priv::check())) {
			$instance['class'] = false;
			return false;
		} else {
			return true;
		}
	}
	
	
	

	//Display a Plugin in a slot
	//Formerly "slot()"
	public static function slot($slotName, $mode = false) {
		//Replacing anything non-alphanumeric with an underscore
		$slotName = \ze\ring::HTMLId($slotName);
	
		//Start the plugin if it is there, then return it to the Layout
		if (!empty(\ze::$slotContents[$slotName])
		 && !empty(\ze::$slotContents[$slotName]['class'])
		 && empty(\ze::$slotContents[$slotName]['error'])) {
			++\ze::$pluginsOnPage;
			\ze::$slotContents[$slotName]['used'] = true;
			\ze::$slotContents[$slotName]['found'] = true;
		
			\ze::$slotContents[$slotName]['class']->start();
		
			$slot = \ze::$slotContents[$slotName]['class'];
	
		//If we didn't find a plugin, but we're in admin mode, 
		//return an "empty" plugin derrived from the base class so that the controls are still displayed to the admin
		} elseif (\ze\priv::check()) {
			//Mark that we've found this slot
			\ze\plugin::setupNewBaseClass($slotName);
			\ze::$slotContents[$slotName]['found'] = true;
		
			\ze::$slotContents[$slotName]['class']->start();
		
			$slot = \ze::$slotContents[$slotName]['class'];
	
		} else {
			$slot = false;
		}
	
		if ($mode == 'grid' || $mode == 'outside_of_grid') {
			//New functionality for grids - output the whole slot, don't use a return value
			if ($slot) {
				$slot->show();
				$slot->end();
			}
			//Add some padding for empty grid slots so they don't disappear and break the grid
			if ($mode == 'grid' && (!$slot || \ze\priv::check())) {
				echo '<span class="pad_slot pad_tribiq_slot">&nbsp;</span>';
				//Note: "pad_tribiq_slot" was the old class name.
				//I'm leaving it in for a while as any old Grid Layouts might still be using that name
				//and they won't be updated until the next time someone edits them.
			}
		
		} else {
			//Old functionality - return the class object
			return $slot;
		}
	}

	//Did we use all of our slots..?
	//Formerly "checkSlotsWereUsed()"
	public static function checkSlotsWereUsed() {
		//Only run this in admin mode
		if (\ze\priv::check()) {
			require \ze::funIncPath(__FILE__, __FUNCTION__);
		}
	}

	
	
	

	//Formerly "getPluginInstanceDetails()"
	public static function details($instanceIdOrName, $fetchBy = 'id') {
	
		if (!$instanceIdOrName) {
			return false;
		}
	
		$sql = "
			SELECT
				i.id AS instance_id,
				i.name,
				i.content_id,
				i.content_type,
				i.content_version,
				i.slot_name,
				IF(i.framework = '', m.default_framework, i.framework) AS framework,
				m.default_framework,
				m.css_class_name,
				i.css_class,
				i.module_id,
				m.class_name,
				m.display_name,
				m.vlp_class,
				m.status
			FROM ". DB_NAME_PREFIX. "plugin_instances AS i
			INNER JOIN ". DB_NAME_PREFIX. "modules AS m
			   ON m.id = i.module_id";
	
		if ($fetchBy == 'id') {
			$sql .= "
			WHERE i.id = ". (int) $instanceIdOrName;
	
		} elseif ($fetchBy == 'name') {
			$sql .= "
			WHERE i.name = '". \ze\escape::sql($instanceIdOrName). "'";
	
		} else {
			return false;
		}
	
		$result = \ze\sql::select($sql);
		$instance = \ze\sql::fetchAssoc($result);
	
		if ($instance['content_id'] && \ze\priv::check()) {
			$instance['instance_name'] = $instance['display_name'];
		} else {
			$instance['instance_name'] = 'P'. $instance['instance_id']. ' '. $instance['name'];
		}
	
		unset($instance['display_name']);
		return $instance;
	}

	//Formerly "getPluginInstanceName()"
	public static function name($instanceId) {
		$instanceDetails = \ze\plugin::details($instanceId);
		return $instanceDetails['instance_name'];
	}

	//Formerly "getPluginInstanceInItemSlot()"
	public static function idInItemSlot($slotName, $cID, $cType = 'html', $cVersion = false, $getModuleId = false) {
	
		if (!$cVersion) {
			$cVersion = \ze\content::latestVersion($cID, $cType);
		}
	
		$sql = "
			SELECT ". ($getModuleId? 'module_id' : 'instance_id'). "
			FROM ". DB_NAME_PREFIX. "plugin_item_link
			WHERE slot_name = '". \ze\escape::sql($slotName). "'
			  AND content_id = ". (int) $cID. "
			  AND content_type = '". \ze\escape::sql($cType). "'
			  AND content_version = ". (int) $cVersion;
	
		$result = \ze\sql::select($sql);
		if ($row = \ze\sql::fetchRow($result)) {
			return $row[0];
		} else {
			return false;
		}
	}

	//Formerly "checkInstanceIsWireframeOnItemLayer()"
	public static function isVCOnItem($instanceId) {
		return
			($plugin = \ze\row::get('plugin_instances', ['content_id', 'content_type', 'content_version', 'slot_name', 'module_id'], $instanceId))
		 && (!($plugin['instance_id'] = 0))
		 && (\ze\row::exists('plugin_item_link', $plugin));
	}

	//Formerly "getPluginInstanceInTemplateSlot()"
	public static function idInLayoutSlot($slotName, $templateFamily, $layoutId, $getModuleId = false) {
	
		$sql = "
			SELECT ". ($getModuleId? 'module_id' : 'instance_id'). "
			FROM ". DB_NAME_PREFIX. "plugin_layout_link
			WHERE slot_name = '". \ze\escape::sql($slotName). "'
			  AND family_name = '". \ze\escape::sql($templateFamily). "'
			  AND layout_id = ". (int) $layoutId;
	
		$result = \ze\sql::select($sql);
		if ($row = \ze\sql::fetchRow($result)) {
			return $row[0];
		} else {
			return false;
		}
	}



	//Formerly "setupNewBaseClassPlugin()"
	public static function setupNewBaseClass($slotName) {
		if (!isset(\ze::$slotContents[$slotName])) {
			\ze::$slotContents[$slotName] = [];
		}
	
		if (!isset(\ze::$slotContents[$slotName]['class']) || empty(\ze::$slotContents[$slotName]['class'])) {
			\ze::$slotContents[$slotName]['class'] = new \ze\moduleBaseClass;
			\ze::$slotContents[$slotName]['class']->setInstance(
				[\ze::$cID, \ze::$cType, \ze::$cVersion, $slotName, false, false, false, false, false, false, false, false, false, false]);
		}
	}

	//Formerly "showPluginError()"
	public static function showError($slotName) {
		echo \ze\ray::value(\ze::$slotContents, $slotName, 'error') ?: \ze\admin::phrase('[Empty Slot]');
	}


	//Attempt to find the path to a Framework
	//Formerly "frameworkPath()"
	public static function frameworkPath($framework, $className, $limit = 10) {
		if (!--$limit) {
			return false;
		}
	
		if ($path = \ze::moduleDir($className, 'frameworks/'. $framework. '/framework.twig.html', true, true)) {
			return $path;
		}
	
		$sql = "
			SELECT dependency_class_name
			FROM ". DB_NAME_PREFIX. "module_dependencies
			WHERE type = 'inherit_frameworks'
			  AND module_class_name = '". \ze\escape::sql($className). "'
			LIMIT 1";
	
		if (($result = \ze\sql::select($sql))
		 && ($row = \ze\sql::fetchRow($result))) {
			return \ze\plugin::frameworkPath($framework, $row[0], $limit);
		} else {
			return false;
		}
	}
}