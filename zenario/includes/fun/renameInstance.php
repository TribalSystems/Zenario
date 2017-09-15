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


$instance = getPluginInstanceDetails($instanceId);

if ($newName === false) {
	$newName = '';
}

if ($createNewInstance) {
	//Copy an instance
	$values = array();
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
	$instanceId = insertRow('plugin_instances', $values);
	
	
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
	sqlSelect($sql);  //No need to check the cache as the other statements should clear it correctly
	
	
	//Copy paths in the conductor
	$sql = "
		INSERT INTO ". DB_NAME_PREFIX. "nested_paths (
			instance_id,
			from_state,
			to_state,
			equiv_id,
			content_type,
			commands
		) SELECT
			". (int) $instanceId. ",
			from_state,
			to_state,
			equiv_id,
			content_type,
			commands
		FROM ". DB_NAME_PREFIX. "nested_paths
		WHERE instance_id = ". (int) $oldInstanceId;
	sqlSelect($sql);  //No need to check the cache as the other statements should clear it correctly
	
	
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
	
	sqlSelect($sql);  //No need to check the cache as the other statements should clear it correctly
	
	
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
	
	sqlSelect($sql);  //No need to check the cache as the other statements should clear it correctly
	
	
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
	
	$result = sqlSelect($sql);
	while ($row = sqlFetchAssoc($result)) {
		//If we were saving from a nested Plugin, convert its id to the new format
		if ($eggId
		 && $eggId == $row['old_id']) {
			$eggId = $row['new_id'];
		}
		
		//Copy any plugin CSS files
		managePluginCSSFile('copy', $oldInstanceId, $row['old_id'], $instanceId, $row['new_id']);
	}
	
	
	managePluginCSSFile('copy', $oldInstanceId, false, $instanceId);
	
	sendSignal('eventPluginInstanceDuplicated', array('oldInstanceId' => $oldInstanceId, 'newInstanceId' => $instanceId));
	
} else {
	updateRow('plugin_instances', array('name' => $newName), $instanceId);
}

return true;