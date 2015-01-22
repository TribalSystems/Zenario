<?php
/*
 * Copyright (c) 2014, Tribal Limited
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
		setRow('modules', array('status' => $oldStatus), $newId);
		
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


if (needRevision(8432)) {
	
	renameModuleDirectory('zenario_contact_form_flexible_pro', 'zenario_flexible_form');
	
	revision(8432);
}


if (needRevision(8466)) {
	
	renameModuleDirectory('zenario_show_static_content', 'zenario_html_snippet');
	
	revision(8466);
}

//Fix a bug where a setting name was renamed
revision( 8467
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings` SET
		name = 'html'
	WHERE name = 'static_content'
_sql
);


if (needRevision(10724)) {
	
	renameModuleDirectory('zenario_content_list_pro', 'zenario_content_list');
	
	revision(10724);
}


if (needRevision(10725)) {
	
	renameModuleDirectory('zenario_extranet_pro', 'zenario_extranet');
	
	revision(10725);
}


if (needRevision(10726)) {
	
	renameModuleDirectory('zenario_extranet_probusiness', 'zenario_extranet');
	
	revision(10726);
}


if (needRevision(10727)) {
	
	renameModuleDirectory('zenario_contact_form_pro', 'zenario_contact_form');
	
	revision(10727);
}


if (needRevision(10728)) {
	
	renameModuleDirectory('zenario_publication_date', 'zenario_meta_data');
	
	revision(10728);
}


//Try to in screen names for anyone with an empty screen-name
if (needRevision(10729)) {
	@sqlSelect("
		UPDATE `". DB_NAME_PREFIX. "users` SET
			screen_name = CONCAT(first_name, ' ', last_name)
		WHERE (screen_name = '' OR screen_name IS NULL)
	");
	
	@sqlSelect("
		UPDATE `". DB_NAME_PREFIX. "users` SET
			screen_name = SUBSTR(email, 1, INSTR(email, '@')-1)
		WHERE (screen_name = '' OR screen_name IS NULL)
		  AND INSTR(email, '@')
	");
	
	revision(10729);
}


if (needRevision(11540)) {
	
	renameModuleDirectory('zenario_language_picker_avls', 'zenario_language_picker');
	
	revision(11540);
}


if (needRevision(13430)) {
	
	renameModuleDirectory('my_full_moon_calculator_v1', 'my_hello_world_two_v1');
	
	revision(13430);
}

if (needRevision(15142)) {
	
	renameModuleDirectory('zenario_font_size', 'zenario_font_size');
	renameModuleDirectory('zenario_google_analytics_tracker', 'zenario_google_analytics_tracker');
	
	revision(15142);
}

//Remove the old Content Plugin if it still exists
if (needRevision(15300)) {
	
	if ($moduleId = getRow('modules', 'id', array('class_name' => 'zenario_content'))) {
		uninstallModule($moduleId, true);
	}
	revision(15300);
}

if (needRevision(15353)) {
	
	renameModuleDirectory('zenario_google_ad_slot', 'zenario_google_ad_slot');
	
	revision(15353);
}

if (needRevision(15795)) {
	
	renameModuleDirectory('zenario_plugin_nest', 'zenario_plugin_nest_probusiness');
	renameModuleDirectory('zenario_slideshow', 'zenario_slideshow_probusiness');
	
	revision(15795);
}

if (needRevision(15880)) {
	if (renameModuleDirectory('zenario_search_results', 'zenario_search_results_pro')) {
		//Handle the changed VLP class name by copying every phrase from the old to the new name
		$sql = "
			INSERT IGNORE INTO ". DB_NAME_PREFIX. "visitor_phrases (
				code,
				language_id,
				module_class_name,
				local_text,
				protect_flag
			) SELECT
				code,
				language_id,
				'zenario_search_results_pro',
				local_text,
				protect_flag
			FROM ". DB_NAME_PREFIX. "visitor_phrases
			WHERE module_class_name = 'zenario_search_results'";
		sqlQuery($sql);
	}
	
	revision(15880);
}

if (needRevision(20450)) {
	
	replaceModule('zenario_menu_pro', 'zenario_menu');
	replaceModule('zenario_menu_vertical_pro', 'zenario_menu_vertical');
	replaceModule('zenario_footer_pro', 'zenario_footer');
	
	revision(20450);
}

if (needRevision(26515)) {
	
	replaceModule('zenario_social_tools', 'zenario_email_a_friend');
	
	revision(26515);
}



//Remove the old Animations Module if it is still in the system
if (needRevision(27220)) {
	
	if ($moduleId = getRow('modules', 'id', array('class_name' => 'zenario_animation'))) {
		uninstallModule($moduleId, true);
	}
	revision(27220);
}