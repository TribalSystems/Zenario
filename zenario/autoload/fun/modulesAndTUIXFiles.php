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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


$sql = "
	SELECT DISTINCT m.id, m.class_name, m.class_name AS name";

if ($getIndividualFiles) {
	$sql .= ", x.filename";
}

$sql .= "
	FROM ". DB_NAME_PREFIX. "tuix_file_contents AS x
	INNER JOIN ". DB_NAME_PREFIX. "modules AS m
	   ON x.module_class_name = m.class_name";

if ($runningModulesOnly) {
	$sql .= "
	  AND m.status IN ('module_running', 'module_is_abstract')";
}

$sql .= "
	WHERE x.type = '". \ze\escape::sql($type). "'";

if ($requestedPath) {
	$sql .= "
	  AND x.path = '". \ze\escape::sql($requestedPath). "'";
}

$settingGroups = [];
//For module Settings, use the "module_class_name" attribute to only show the related settings
//However compatilibity now includes inheriting module Settings, so include module Settings from
//compatible modules as well
if (($type == 'admin_boxes' || $type == 'slot_controls') && !empty($compatibilityClassNames)) {
	$settingGroups = $compatibilityClassNames;

//For Site Settings, only show settings from the current settings group
//For Advanced Searches, only show fields for the current Storekeepr Path
} elseif ($type == 'admin_boxes' && $requestedPath == 'site_settings') {
	$settingGroups[] = $settingGroup;

//Visitor TUIX has the option to be customised.
//(However this is optional; you can also show the base logic without any customisation.)
} elseif ($type == 'visitor' && $settingGroup) {
	$settingGroups[] = $settingGroup;
}

if ($type == 'visitor' || !empty($settingGroups)) {
	if ($includeBaseFunctionalityWithSettingGroups) {
		$settingGroups[] = '';
	}
	
	$sql .= "
		AND x.setting_group IN(". \ze\escape::in($settingGroups). ")";
}

$files = [];
$result = \ze\sql::select($sql);
while ($file = \ze\sql::fetchAssoc($result)) {
	$files[] = $file;
}

return $files;