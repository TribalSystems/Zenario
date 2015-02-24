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

header('Content-Type: text/javascript; charset=UTF-8');
require '../cacheheader.inc.php';

//Get a list of module ids from the url
$modules = array_unique(explode(',', $_GET['ids']));
$moduleDetails = array();

//Ensure that the site name and subdirectory are part of the ETag, as modules can have different ids on different servers
$ETag = 'zenario-plugin-js-'. LATEST_REVISION_NO. '--'. $_SERVER["HTTP_HOST"]. '-';

//Add the id of each running Plugin to the ETag
foreach ($modules as $moduleId) {
	if ($moduleId) {
		$ETag .= '-'. (int) $moduleId;
	}
}

if (isset($_GET['admin']) && $_GET['admin']) {
	$ETag .= '--admin';
}

//Cache this combination of running Plugin JavaScript
useCache($ETag);


//Run pre-load actions
foreach (cms_core::$editions as $className => $dirName) {
	if ($action = moduleDir($dirName, 'actions/wrapper.pre_load.php', true)) {
		require $action;
	}
}


useGZIP(isset($_GET['gz']) && $_GET['gz']);
require CMS_ROOT. 'zenario/liteheader.inc.php';
require CMS_ROOT. 'zenario/includes/cms.inc.php';
loadSiteConfig();


function incJS($path, $file) {
	$file = $path. $file;
	
	if (file_exists($file. '.js.php')) {
		chdir($path);
		require $file. '.js.php';
		chdir(CMS_ROOT);
	
	} elseif (file_exists($file. '.pack.js')) {
		require $file. '.pack.js';
	} elseif (file_exists($file. '.min.js')) {
		require $file. '.min.js';
	} elseif (file_exists($file. '.js')) {
		require $file. '.js';
	}
	
	echo "\n/**/\n";
}


//Get a list of each Plugin
foreach ($modules as $moduleId) {
	if ($moduleId) {
		$module = getModuleDetails($moduleId);
		$moduleDetails[$module['class_name']] = $module;
	}
}

//Add Plugin inheritances as well
foreach (array_keys($moduleDetails) as $moduleClassName) {
	foreach (getModuleInheritances($moduleDetails[$moduleClassName]['class_name'], 'include_javascript', false) as $inheritanceClassName) {
		if (empty($moduleDetails[$inheritanceClassName])) {
			$moduleDetails[$inheritanceClassName] = getModuleDetails($inheritanceClassName, $fetchBy = 'class');
		}
	}
}

//Try to put the modules in dependency order (technically this isn't needed, but it looks clearer to read)
$moduleDetails = array_reverse($moduleDetails, true);


//Add JavaScript support elements for each Plugin on the page
foreach ($moduleDetails as $module) {
	//Create a namespace for each Plugin used on this page
	echo '
		zenario.enc(', $module['module_id'], ', "', $module['class_name'], '", "', $module['vlp_class'], '");';
	
	if (!empty($_GET['admin_frontend'])) {
		//Add the Plugins's Admin Frontent library
		incJS(CMS_ROOT. moduleDir($module['class_name'], 'js/'), 'admin_frontend');
	
	} elseif (!empty($_GET['storekeeper'])) {
		//Add the Plugins's Storekeeper Admin library
		incJS(CMS_ROOT. moduleDir($module['class_name'], 'js/'), 'organizer');
		incJS(CMS_ROOT. moduleDir($module['class_name'], 'js/'), 'storekeeper');
	
	} else {
		//Add the Plugin's library include if it exists
		incJS(CMS_ROOT. moduleDir($module['class_name'], 'js/'), 'plugin');
		
		//For Admins, add the Plugin's admin js if it exists
		if (!empty($_GET['admin'])) {
			incJS(CMS_ROOT. moduleDir($module['class_name'], 'js/'), 'admin');
		}
	}
}

if (!empty($_GET['storekeeper'])) {
	//Get a list of Module names for use in the formatting options
	$pluginNames = array();
	foreach (getModules() as $module) {
		$pluginNames[$module['id']] = $pluginNames[$module['class_name']] = $pluginNames[$module['class_name']] = $module['display_name'];
	}
	
	echo '
		zenarioA.pluginNames = ', json_encode($pluginNames), ';';
}


//Run post-display actions
foreach (cms_core::$editions as $className => $dirName) {
	if ($action = moduleDir($dirName, 'actions/wrapper.post_display.php', true)) {
		require $action;
	}
}