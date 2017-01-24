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
			tab,
			ord,
			cols,
			small_screens,
			module_id,
			framework,
			css_class,
			is_slide,
			name_or_title,
			states,
			visibility,
			smart_group_id,
			module_class_name,
			method_name,
			param_1,
			param_2
		) SELECT
			". (int) $instanceId. " AS `instance_id`,
			tab,
			ord,
			cols,
			small_screens,
			module_id,
			framework,
			css_class,
			is_slide,
			name_or_title,
			states,
			visibility,
			smart_group_id,
			module_class_name,
			method_name,
			param_1,
			param_2
		FROM ". DB_NAME_PREFIX. "nested_plugins
		WHERE instance_id = ". (int) $oldInstanceId;
	sqlSelect($sql);  //No need to check the cache as the other statements should clear it correctly
	
	//Copy paths in the conductor
	$sql = "
		INSERT INTO ". DB_NAME_PREFIX. "nested_paths (
			instance_id,
			from_state,
			to_state,
			commands
		) SELECT
			". (int) $instanceId. " AS `instance_id`,
			from_state,
			to_state,
			commands
		FROM ". DB_NAME_PREFIX. "nested_paths
		WHERE instance_id = ". (int) $oldInstanceId;
	sqlSelect($sql);  //No need to check the cache as the other statements should clear it correctly
	
	//Copy settings, as well as settings for any nested Plugins
	$sql = "
		INSERT INTO ". DB_NAME_PREFIX. "plugin_settings (
			instance_id,
			nest,
			`name`,
			`value`,
			is_content,
			format,
			foreign_key_to,
			foreign_key_id,
			foreign_key_char,
			dangling_cross_references
		) SELECT
			". (int) $instanceId. " AS `instance_id`,
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
		  AND np_old.id = ps.nest
		LEFT JOIN ". DB_NAME_PREFIX. "nested_plugins AS np_new
		   ON np_new.instance_id = ". (int) $instanceId. "
		  AND np_old.tab = np_new.tab
		  AND np_old.ord = np_new.ord
		  AND np_old.module_id = np_new.module_id
		WHERE (ps.nest != 0 XOR np_new.id IS NULL)
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
		  AND np_old.tab = np_new.tab
		  AND np_old.ord = np_new.ord
		  AND np_old.module_id = np_new.module_id
		WHERE np_old.instance_id = ". (int) $oldInstanceId;
	
	$result = sqlSelect($sql);
	while ($row = sqlFetchAssoc($result)) {
		//If we were saving from a nested Plugin, convert its id to the new format
		if ($nest
		 && $nest == $row['old_id']) {
			$nest = $row['new_id'];
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