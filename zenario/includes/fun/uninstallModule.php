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

$module = getModuleDetails($moduleId);

if (!$uninstallRunningModules){
	if ($module['status'] == 'module_running') {
		echo adminPhrase('Running modules cannot be Uninstalled');
		exit;
	}
}

uninstallModuleCheckForDependencies($module);


//Remove all data about the module
$result = getRows('plugin_instances', 'id', array('module_id' => $moduleId));
while ($row = sqlFetchAssoc($result)) {
	deletePluginInstance($row['id']);
}

foreach(array('job_logs') as $table) {
	$sql = "
		DELETE FROM ". DB_NAME_PREFIX. $table. "
		WHERE job_id IN (
			SELECT id
			FROM ". DB_NAME_PREFIX. "jobs
			WHERE job_id = ". (int) $moduleId. "
		)";
	sqlQuery($sql);
}

foreach(array(
	'jobs', 'nested_plugins',
	'module_dependencies', 'signals',
	'plugin_item_link', 'plugin_layout_link',
	'plugin_setting_defs', 'plugin_instances'
) as $table) {
	deleteRow($table, array('module_id' => $moduleId));
}
deleteRow('special_pages', array('module_class_name' => $module['class_name']));

//Attempt to delete any module tables or views
$prefix2 = "mod". (int) $moduleId. "_";
$prefix = DB_NAME_PREFIX. $prefix2;
$prefixLen = strlen($prefix);

$sql = "
	SHOW TABLES LIKE '". $prefix. "%'";
$result = sqlQuery($sql);

while($row = sqlFetchRow($result)) {
	if (substr($row[0], 0, $prefixLen) == $prefix) {
		sqlUpdate("DROP TABLE IF EXISTS `". sqlEscape($row[0]). "`", false);
		@sqlUpdate("DROP VIEW IF EXISTS `". sqlEscape($row[0]). "`", false);
	}
}

//Delete any datasets that used any of these tables
$sql = "
	DELETE cd.*, cdt.*, cdf.*, fv.*, vl.*
	FROM ". DB_NAME_PREFIX. "custom_datasets AS cd
	LEFT JOIN ". DB_NAME_PREFIX. "custom_dataset_tabs AS cdt
	   ON cdt.dataset_id = cd.id
	LEFT JOIN ". DB_NAME_PREFIX. "custom_dataset_fields AS cdf
	   ON cdf.dataset_id = cd.id
	LEFT JOIN ". DB_NAME_PREFIX. "custom_dataset_field_values AS fv
	   ON fv.field_id = cdf.id
	LEFT JOIN ". DB_NAME_PREFIX. "custom_dataset_values_link AS vl
	   ON vl.dataset_id = cd.id
	WHERE cd.table LIKE '". $prefix2. "%'";
sqlQuery($sql);

//Completely delete any Content Types it has
$result = getRows('content_types', 'content_type_id', array('module_id' => $moduleId), 'content_type_name_en');
while ($contentType = sqlFetchAssoc($result)) {
	//Completely delete any Content Item for that Content Type
	$result2 = getRows('content', array('id', 'type'), array('type' => $contentType['content_type_id']));
	while ($content = sqlFetchAssoc($result2)) {
		deleteContentItem($content['id'], $content['type']);
	}
	
	deleteRow('content_types', $contentType);
}

//Completely delete any Special Pages it has
$result = getRows('special_pages', array('equiv_id', 'content_type'), array('module_class_name' => $module['class_name']));
while ($specialPage = sqlFetchAssoc($result)) {
	//Completely delete the Special Page in every language
	$result2 = getRows('content', array('id', 'type'), array('equiv_id' => $specialPage['equiv_id'], 'type' => $specialPage['content_type']));
	while ($content = sqlFetchAssoc($result2)) {
		deleteContentItem($content['id'], $content['type']);
	}
	
	deleteRow('special_pages', $specialPage);
}

//Unlink this module from any special pages
updateRow('special_pages', array('module_class_name' => ''), array('module_class_name' => $module['class_name']));

//Delete any records of the module having been installed
$sql = "
	DELETE FROM ". DB_NAME_PREFIX. "local_revision_numbers
	WHERE path LIKE '%modules/". likeEscape($module['class_name']). "'
	   OR path LIKE '%modules/". likeEscape($module['class_name']). "/db_updates'
	   OR path LIKE '%plugins/". likeEscape($module['class_name']). "'
	   OR path LIKE '%plugins/". likeEscape($module['class_name']). "/db_updates'";
sqlQuery($sql);

//Remove any Visitor Phrases
deleteRow('visitor_phrases', array('module_class_name' => $module['class_name']));

//If we are uninstalling a suspended Module, or removing a Module that is no longer in the filesystem,
//remove it from the module table.
if (!$uninstallRunningModules || !moduleDir($module['class_name'], '', true)) {
	deleteRow('modules', $moduleId);
	
	//Force a re-scan of the Module directory, so that the Module's name will be re-read in next time if it is still in the file system
	setSetting('module_description_hash', '');

 //But if this was an installation attempt, just mark the Module as not running to keep its Module Id
} else {
	setRow('modules', array('status' => 'module_not_initialized'), $moduleId);
}

$modules = array();
getModuleCodeHash($modules, true);