<?php
/*
 * Copyright (c) 2016, Tribal Limited
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
function renameModuleDirectory($oldName, $newName) {
	addNewModules($skipIfFilesystemHasNotChanged = false);
	
	if (($oldId = getModuleId($oldName)) && ($newId = getModuleId($newName))) {
		foreach(array(
			'content_types', 'jobs', 'signals',
			'module_dependencies', 'plugin_setting_defs',
			'nested_plugins', 'plugin_instances',
			'plugin_item_link', 'plugin_layout_link'
		) as $table) {
			$sql = "
				UPDATE IGNORE ". DB_NAME_PREFIX. $table. " SET
					module_id = ". (int) $newId. "
				WHERE module_id = ". (int) $oldId;
			sqlQuery($sql);
		}
		
		$oldStatus = getRow('modules', 'status', $oldId);
		$newStatus = getRow('modules', 'status', $newId);
		
		if (in($newStatus, 'module_not_initialized', 'module_suspended')) {
			setRow('modules', array('status' => $oldStatus), $newId);
		}
		
		return true;
	}
	
	return false;
}

//Code for one Module replacing functionality from another
function replaceModule($oldName, $newName) {
	addNewModules($skipIfFilesystemHasNotChanged = false);
	
	if (($oldId = getModuleId($oldName)) && ($newId = getModuleId($newName))) {
		foreach(array(
			'content_types',
			'nested_plugins', 'plugin_instances',
			'plugin_item_link', 'plugin_layout_link'
		) as $table) {
			$sql = "
				UPDATE IGNORE ". DB_NAME_PREFIX. $table. " SET
					module_id = ". (int) $newId. "
				WHERE module_id = ". (int) $oldId;
			sqlQuery($sql);
		}
		
		$oldStatus = getRow('modules', 'status', $oldId);
		$newStatus = getRow('modules', 'status', $newId);
		
		if ($oldStatus == 'module_running' || $newStatus == 'module_running') {
			setRow('modules', array('status' => 'module_running'), $newId);
		
		} elseif ($oldStatus == 'module_suspended' || $newStatus == 'module_suspended') {
			setRow('modules', array('status' => 'module_suspended'), $newId);
		}
		
		uninstallModule($oldId, $uninstallRunningModules = true);
		
		return true;
	}
	
	return false;
}

//Code for running a dependency, if a previously existing Module gains a new dependancy
function runNewModuleDependency($moduleName, $dependencyName) {
	addNewModules($skipIfFilesystemHasNotChanged = false);
	
	if (($moduleId = getModuleId($moduleName)) && ($dependencyId = getModuleId($dependencyName))) {
		$moduleStatus = getRow('modules', 'status', $moduleId);
		$dependencyStatus = getRow('modules', 'status', $dependencyId);
		
		if ($moduleStatus == 'module_running' && !in($dependencyStatus, 'module_running', 'module_is_abstract')) {
			setRow('modules', array('status' => 'module_running'), $dependencyId);
		
		} elseif ($moduleStatus == 'module_suspended' && !in($dependencyStatus, 'module_running', 'module_suspended', 'module_is_abstract')) {
			setRow('modules', array('status' => 'module_suspended'), $dependencyId);
		}
		
		return true;
	}
	
	return false;
}




//Remove the old Animations Module if it is still in the system
if (needRevision(27220)) {
	
	if ($moduleId = getRow('modules', 'id', array('class_name' => 'zenario_animation'))) {
		uninstallModule($moduleId, true);
	}
	revision(27220);
}

//If the Location Viewer Module is running, the Google Map Module now also needs to be running.
if (needRevision(33940)) {
	runNewModuleDependency('zenario_location_viewer', 'zenario_google_map');
	revision(33940);
}


//Force module descriptions to be rescanned by addNewModules(), the next time it is run
if (needRevision(36485)) {
	setSetting('module_description_hash', '');
	
	revision(36485);
}


//Remove the content list probusiness and merge all of it's former features into the regular content list
if (needRevision(36500)) {
	
	renameModuleDirectory('zenario_content_list_probusiness', 'zenario_content_list');
	
	revision(36500);
}

//Mark the old content list probusiness module as not running to avoid warning emails
if (needRevision(36660)) {
	
	updateRow('modules', array('status' => 'module_not_initialized'), array('class_name' => 'zenario_content_list_probusiness'));
	
	revision(36660);
}


//In version 7.4, we're merging all of the nest-based slideshow-type modules
if (needRevision(36900)) {
	
	renameModuleDirectory('zenario_slideshow_probusiness', 'zenario_slideshow');
	renameModuleDirectory('zenario_roundabout', 'zenario_slideshow');
	renameModuleDirectory('zenario_revealable_panel', 'zenario_slideshow');
	updateRow('modules', array('status' => 'module_not_initialized'), array('class_name' =>
		array('zenario_slideshow_probusiness', 'zenario_roundabout', 'zenario_revealable_panel')));
	
	revision(36900);
}

//Merge all of the nest modules
if (needRevision(36930)) {
	
	renameModuleDirectory('zenario_plugin_tabbed_nest', 'zenario_plugin_nest');
	renameModuleDirectory('zenario_plugin_nest_probusiness', 'zenario_plugin_nest');
	
	//Check if the ProBusiness module was installed, and the tabs table exists
	if (($module = getModuleDetails('zenario_plugin_nest_probusiness', 'class'))
	 && (setModulePrefix($module))
	 && (checkTableDefinition(DB_NAME_PREFIX. ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX. 'tabs', true))) {
		
		//Attempt to migrate all of the data from that table into the core table
		$result = getRows(ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX. 'tabs', true, array());
		while ($row = sqlFetchAssoc($result)) {
			$tabId = $row['tab_id'];
			unset($row['tab_id']);
			updateRow('nested_plugins', $row, array('id' => $tabId, 'is_slide' => 1), $ignore = true, $ignoreMissingColumns = true);
		}
		
		//Drop the table
		$sql = "
			DROP TABLE `". DB_NAME_PREFIX. ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX. "tabs`";
		sqlQuery($sql);
	}
	
	
	updateRow('modules', array('status' => 'module_not_initialized'), array('class_name' =>
		array('zenario_plugin_nest_probusiness', 'zenario_plugin_tabbed_nest')));
	
	revision(36930);
}



//Post-branch patch:
//Replace the (removed) zenario_feed_reader_pro module with the zenario_feed_reader module,
//which now has all the features that the "pro" module used to have.
if (needRevision(37237)) {
	
	renameModuleDirectory('zenario_feed_reader_pro', 'zenario_feed_reader');
	
	revision(37237);
}