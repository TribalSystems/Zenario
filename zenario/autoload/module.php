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

class module {


	public static function details($idOrName, $fetchBy = 'id') {
		$sql = "
			SELECT
				id,
				id AS module_id,
				class_name,
				class_name AS name,
				class_name AS moduleClassName,
				display_name,
				vlp_class,
				status,
				default_framework,
				css_class_name,
				is_pluggable,
				nestable,
				can_be_version_controlled
			FROM " . DB_PREFIX . "modules";
	
		if ($fetchBy == 'class' || $fetchBy == 'name') {
			$sql .= "
				WHERE class_name = '" . \ze\escape::asciiInSQL($idOrName) . "'";
	
		} else {
			$sql .= "
				WHERE id = " . (int) $idOrName;
		}
	
		if (!$module = \ze\sql::fetchAssoc($sql)) {
			return false;
		} else {
			
			$module['nestable_only'] = $module['nestable'] == 2;
			
			return $module;
		}
	}

	public static function className($id) {
		return \ze\row::get('modules', 'class_name', ['id' => $id]);
	}

	public static function displayName($id) {
		return \ze\row::get('modules', 'display_name', ['id' => $id]);
	}

	public static function getModuleDisplayNameByClassName($name) {
		return \ze\row::get('modules', 'display_name', ['class_name' => $name]);
	}

	public static function id($name) {
		return \ze\row::get('modules', 'id', ['class_name' => $name]);
	}

	public static function status($id) {
		return \ze\row::get('modules', 'status', ['id' => $id]);
	}

	public static function statusByName($name) {
		return \ze\row::get('modules', 'status', ['class_name' => $name]);
	}

	public static function isRunning($className) {
	
		return (bool) \ze\sql::fetchRow("
			SELECT 1
			FROM ". DB_PREFIX. "modules
			WHERE class_name = '". \ze\escape::asciiInSQL($className). "'
			  AND status IN ('module_running', 'module_is_abstract')"
		);
	}


	public static function canActivate($name, $fetchBy = 'name', $activate = false) {
	
		$error = [];
		$missingPlugin = false;
	
		if ($module = \ze\module::details($name, $fetchBy)) {
			if ($module['status'] != 'module_running'
			 && $module['status'] != 'module_is_abstract') {
				return false;
		
			} elseif ($activate && !\ze\module::incWithDependencies($module['class_name'], $missingPlugin)) {
				return false;
			}
	
		} else {
			return false;
		}
	
	
		if ($activate) {
			\ze\module::setPrefix($module);
			$useThisClassInstance = false;
		
			$class = new $module['class_name'];
		
			$class->setInstance([false, false, false, false, false, $module['class_name'], $module['vlp_class'], $module['id'], false, false, false, false]);

			return $class;
		} else {
			return true;
		}
	}

	public static function activate($name) {
		return \ze\module::canActivate($name, 'class', true);
	}





	public static function inc($module) {
		
		
		
		if (!is_array($module)) {
			$module = \ze\sql::fetchAssoc("
				SELECT id, class_name, status
				FROM ". DB_PREFIX. "modules
				WHERE class_name = '". \ze\escape::asciiInSQL($module). "'
				LIMIT 1");
		}
	
		$missingPlugin = [];
	
		if ($module
		 && ($module['status'] == 'module_running' || $module['status'] == 'module_is_abstract')
		 && (\ze\module::incWithDependencies($module['class_name'], $missingPlugin))) {
			\ze\module::setPrefix($module);
			return true;
		} else {
			return false;
		}
	}

	public static function incSubclass($filePathOrModuleClassName, $type = false, $path = false) {
	
		if ($type === false) {
			$type = \ze::$tuixType;
		}
		if ($path === false) {
			$path = \ze::$tuixPath;
		}
	
		//Catch a renamed variable
		if ($type == 'storekeeper') {
			$type = 'organizer';
		}
	
		if (strpos($filePathOrModuleClassName, '/') === false
		 && strpos($filePathOrModuleClassName, '\\') === false) {
			$basePath = CMS_ROOT. \ze::moduleDir($filePathOrModuleClassName);
			$moduleClassName = $filePathOrModuleClassName;
		} else {
			$basePath = dirname($filePathOrModuleClassName);
			$moduleClassName = basename($basePath);
		}
		
		
		//Check if this module actually uses the classes directory
		if (!is_dir($basePath. '/classes/')) {
			//Don't try to use subclasses if not.
			return false;
		}
		
	
		//Modules use the owner/author name at the start of their name. Get this prefix.
		$prefix = explode('_', $moduleClassName, 2);
		if (!empty($prefix[1])) {
			$prefix = $prefix[0];
		} else {
			$prefix = '';
		}
	
		//Take the path, and try to get the name of the last tag in the tag path.
		//(But if the last tag is "panel", remove that as the second-last tag will be more helpful.)
		//Also try to remove the prefix from above.
		$matches = [];
		preg_match('@.*/_*(\w+)@', str_replace('/'. $prefix. '_', '/', str_replace('/panel', '', '/'. $path)), $matches);
	
		if (empty($matches[1])) {
			exit('Bad path: '. $path);
		}
	
		//From the logic above, create a standard filepath and class name
		$phpPath = $basePath. '/classes/'. $type. '/'. $matches[1]. '.php';
		$className = $moduleClassName. '__'. $type. '__'. $matches[1];
	
		//Also check for a filepath by stripping off the classname from the path
		//e.g. (zenario_example_manager__test could be called test.php)
		if (!is_file($phpPath)) {
			$matches = [];
			preg_match('@.*/_*(\w+)@', str_replace('/'. $moduleClassName. '_', '/', str_replace('/panel', '', '/'. $path)), $matches);
			$phpPathAlt = $basePath. '/classes/'. $type. '/'. $matches[1]. '.php';
			if (is_file($phpPathAlt)) {
				$phpPath = $phpPathAlt;
			}
		}
	
		//Check if the file is there
		if (is_file($phpPath)) {
			require_once $phpPath;
	
			if (class_exists($className)) {
				if (\ze::$recordFiles) {
					\ze::$tuixFiles[$phpPath] = true;
				}
				return $className;
			} else {
				$msg = 'The module [[moduleClassName]] is trying to load the [[className]] PHP class, which it expects to find in [[phpPath]]. The file exists but the class was not defined!';
			}
	
		} else {
			$msg = 'The module [[moduleClassName]] is trying to load the [[className]] PHP class, which it expects to find in [[phpPath]]. This file is missing!';
		}
		
		exit(\ze\admin::phrase($msg, ['moduleClassName' => $moduleClassName, 'className' => $className, 'phpPath' => str_replace('//', '/', $phpPath)]));
	}

	public static function dependencies($moduleName) {
		$sql = "
			SELECT
				d.dependency_class_name,
				d.`type`,
				m.class_name,
				m.id AS module_id,
				m.vlp_class
			FROM ". DB_PREFIX. "module_dependencies AS d
			LEFT OUTER JOIN ". DB_PREFIX. "modules AS m
			   ON d.dependency_class_name = m.class_name
			  AND m.status IN ('module_running', 'module_is_abstract')
			WHERE d.module_class_name = '". \ze\escape::asciiInSQL($moduleName). "'
			  AND `type` = 'dependency'";
	
		return \ze\sql::fetchAssocs($sql);
	}


	//Include all of a Module's Dependency files, then include the Module
	//Note: you need to check to see if a Module is running first, before calling this
	public static function incWithDependencies($moduleName, &$missingPlugin, $recurseCount = 9) {
	
		if (!$recurseCount) {
			return false;
		}
	
		//Check that this has not been included already - if so, our job has already been done
		if (isset(\ze::$modulesLoaded[$moduleName])) {
			return \ze::$modulesLoaded[$moduleName];
		}

		//Check for dependencies
		foreach (\ze\module::dependencies($moduleName) as $row) {
			//For each dependency, check if it is running and try to include it
			if ($row['module_id'] && \ze\module::incWithDependencies($row['class_name'], $missingPlugin, $recurseCount-1)) {
				\ze\module::setPrefix($row);
			} else {
				//Otherwise report a dependancy as missing, then stop
				$missingPlugin = ['module' => $row['dependency_class_name']];
				return false;
			}
		}
	
		$missingPlugin = false;
	
		$file = \ze::moduleDir($moduleName, 'module_code.php');
		if (\ze::$modulesLoaded[$moduleName] = file_exists($file = \ze::moduleDir($moduleName, 'module_code.php'))) {
			require_once $file;
			return true;
		} else {
			return false;
		}
	}



	public static function inheritance($moduleClassName, $type) {
		$sql = "
			SELECT dependency_class_name
			FROM ".  DB_PREFIX. "module_dependencies
			WHERE module_class_name = '". \ze\escape::asciiInSQL($moduleClassName). "'
			  AND `type` = '". \ze\escape::asciiInSQL($type). "'
			LIMIT 1";
	
		$result = \ze\sql::select($sql);
		if ($row = \ze\sql::fetchAssoc($result)) {
			return $row['dependency_class_name'];
		} else {
			return false;
		}
	}

	public static function inheritances($moduleClassName, $type, $includeCurrent = true, $recurseLimit = 9) {
		$inheritances = [];
	
		if ($includeCurrent) {
			$inheritances[] = $moduleClassName;
		}
	
		while (--$recurseLimit && ($moduleClassName = \ze\module::inheritance($moduleClassName, $type))) {
			$inheritances[] = $moduleClassName;
		}
	
		return $inheritances;
	}


	public static function idInItemSlot($slotName, $cID, $cType = 'html', $cVersion = false) {
		return \ze\plugin::idInItemSlot($slotName, $cID, $cType, $cVersion, true);
	}

	public static function idInLayoutSlot($slotName, $layoutId) {
		return \ze\plugin::idInLayoutSlot($slotName, $layoutId, true);
	}




	public static function sendSignal($signalName, $signalParams) {
		//Don't try to send a signal if we are in the Admin Login Screen applying Database Updates
		if (!class_exists('\ze\moduleBaseClass')) {
			return false;
		}
	
		if (!empty(\ze::$signalsCurrentlyTriggered[$signalName])) {
			return false;
		}
	
		\ze::$signalsCurrentlyTriggered[$signalName] = true;
	
			$sql = "
				SELECT module_id, module_class_name, module_class_name AS class_name, static_method
				FROM ". DB_PREFIX. "signals
				WHERE signal_name = '". \ze\escape::sql($signalName). "'
				  AND module_class_name NOT IN (
					SELECT suppresses_module_class_name
					FROM ". DB_PREFIX. "signals AS e
					INNER JOIN ". DB_PREFIX. "modules AS m
					   ON e.module_id = m.id
					WHERE e.signal_name = '". \ze\escape::sql($signalName). "'
					  AND e.suppresses_module_class_name != ''
					  AND m.status IN ('module_running', 'module_is_abstract')
				  )
				ORDER BY signal_name, module_class_name";
	
			$returns = [];
			$result = \ze\sql::select($sql);
			while($row = \ze\sql::fetchAssoc($result)) {
				if (\ze\module::inc($row['class_name'])) {
					if ($row['static_method']) {
						$returns[$row['class_name']] = call_user_func_array([$row['class_name'], $signalName], $signalParams);
					} else {
						$module = new $row['class_name'];
						$returns[$row['class_name']] = call_user_func_array([$module, $signalName], $signalParams);
					}
				}
			}
	
		unset(\ze::$signalsCurrentlyTriggered[$signalName]);
		return $returns;
	}
	
	
	
	
	


	//Get all existing modules
	public static function modules($onlyGetRunningPlugins = false, $ignoreUninstalledPlugins = false, $dbUpdateSafemode = false, $orderBy = false) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}

	//Get all of the existing modules that are running
	public static function runningModules($dbUpdateSafemode = false, $orderBy = false) {
		return \ze\module::modules($onlyGetRunningPlugins = true, false, $dbUpdateSafemode, $orderBy);
	}


	public static function prefix($module, $mustBeRunning = true, $define = false) {
	
		if (!is_array($module)) {
			$module = \ze\sql::fetchAssoc("
				SELECT id, class_name, status
				FROM ". DB_PREFIX. "modules
				WHERE class_name = '". \ze\escape::asciiInSQL($module). "'
				  ". ($mustBeRunning? "AND status IN ('module_running', 'module_is_abstract')" : ""). "
				LIMIT 1");
		}
	
		if (!$module) {
			return false;
		} else {
			return \ze\module::setPrefix($module, $define);
		}
	}


	//Define a plugin's database table prefix
	public static function setPrefix(&$module, $define = true) {
	
		if (empty($module['class_name'])) {
			return false;
		}
	
		$module['prefix'] = strtoupper($module['class_name']). '_PREFIX';
	
		if ($define && defined($module['prefix'])) {
			return true;
		}
	
		if (!empty($module['module_id'])) {
			$id = $module['module_id'];
	
		} elseif (!empty($module['id'])) {
			$id = $module['id'];
	
		} else {
			return false;
		}
	
		$className = $module['class_name'];
	
		$prefix = 'mod'. $id. '_';
		foreach (explode('_', $className) as $frag) {
			if ($frag !== '') {
				$prefix .= $frag[0];
			}
		}
		$prefix .= '_';
	
		if ($define) {
			define($module['prefix'], $prefix);
			return true;
		} else {
			return $prefix;
		}
	}
	
	//Get an array of all a modules plugins (including nested), and also an array of their settings.
	//Useful for updating plugin settings via a db update.
	public static function getModuleInstancesAndPluginSettings($className) {
		
		$moduleId = \ze\module::id($className);
		
		$instances = \ze\sql::fetchAssocs('
			SELECT id AS instance_id, 0 AS egg_id
			FROM '. DB_PREFIX. 'plugin_instances
			WHERE module_id = '. (int) $moduleId. '
			UNION
			SELECT instance_id, id AS egg_id
			FROM ' . DB_PREFIX . 'nested_plugins
			WHERE module_id = '. (int) $moduleId
		);
		
		//Load default plugin settings
		$defaultSettings = [];
		$sql = '
			SELECT name, default_value
			FROM ' . DB_PREFIX . 'plugin_setting_defs
			WHERE module_id = ' . (int)$moduleId;
		$result = \ze\sql::select($sql);
		while ($row = \ze\sql::fetchAssoc($result)) {
			$defaultSettings[$row['name']] = $row['default_value'];
		}
		
		foreach ($instances as $i => $instance) {
			$settings = $defaultSettings;
			//Load individual plugin settings
			$sql = '
				SELECT name, value
				FROM ' . DB_PREFIX . 'plugin_settings
				WHERE instance_id = ' . (int)$instance['instance_id'] . '
				AND egg_id = ' . (int)$instance['egg_id'];
			$result = \ze\sql::select($sql);
			while ($row = \ze\sql::fetchAssoc($result)) {
				$settings[$row['name']] = $row['value'];
			}
			$instances[$i]['settings'] = $settings;
		}
		return $instances;
	}
	
}