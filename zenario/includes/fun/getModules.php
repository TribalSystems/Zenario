<?php
/*
 * Copyright (c) 2015, Tribal Limited
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

$result = false;

//For certain parts of the CMS (e.g. the database updater), this query may cause errors, yet we'd still like to continue smoothly
//(albeit without a list of modules)
//I also have to be a bit careful due to the status enum being changed in 6.0.4; I need to check both formats of values!
if ($dbUpdateSafemode) {
	$sql = "
		SELECT id, class_name, class_name AS name
		FROM " . DB_NAME_PREFIX . "modules";
	
	//Add in certain conditions, depending on where this is being called
	if ($onlyGetRunningPlugins) {
		$sql .= "
			WHERE status IN ('_ENUM_RUNNING', 'module_running')";
	} elseif ($ignoreUninstalledPlugins) {
		$sql .= "
			WHERE status NOT IN ('_ENUM_INSTALLED', 'module_not_initialized')";
	}

	$sql .= "
			ORDER BY status = 'module_running' DESC, status, class_name, id";
	
	$result = @sqlSelect($sql);

} else {
	$sql = "
		SELECT
			id,
			id AS module_id,
			class_name,
			class_name AS name,
			display_name,
			vlp_class,
			status,
			default_framework,
			css_class_name,
			is_pluggable,
			can_be_version_controlled
		FROM ". DB_NAME_PREFIX. "modules";
	
	//Add in certain conditions, depending on where this is being called
	if ($onlyGetRunningPlugins) {
		$sql .= "
			WHERE status = 'module_running'";
	} elseif ($ignoreUninstalledPlugins) {
		$sql .= "
			WHERE status != 'module_not_initialized'";
	}
	
	if ($orderBy) {
		$sql .= "
				ORDER BY ". $orderBy;
	
	} else {
		$sql .= "
				ORDER BY status = 'module_running' DESC, status, class_name, id";
	}
	
	$result = sqlQuery($sql);
}


//Fetch details on each plugin
$modules = array();
if ($result) {
	while ($module = sqlFetchAssoc($result)) {
		//Is the plugin's prefix defined? Define it now if not.
		setModulePrefix($module);
		
		$modules[$module['id']] = $module;
	}
}

return $modules;