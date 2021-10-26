<?php
/*
 * Copyright (c) 2021, Tribal Limited
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
require '../basicheader.inc.php';

//Get a list of module ids from the url
$modules = array_unique(explode(',', $_GET['ids'] ?? false));
$moduleIds = [];
$moduleDetails = [];

//Ensure that the site name and subdirectory are part of the ETag, as modules can have different ids on different servers
$ETag = 'zenario-plugin-js-'. LATEST_REVISION_NO. '--'. $_SERVER["HTTP_HOST"]. '-';

//Add the id of each running Plugin to the ETag
foreach ($modules as $moduleId) {
	if ($moduleId = (int) $moduleId) {
		$moduleIds[] = $moduleId; 
		$ETag .= '-'. $moduleId;
	}
}

$mode = 'visitor';
if (!empty($_GET['admin_frontend'])) {
	$mode = 'admin_frontend';
	$ETag .= '--admin_frontend';

} elseif (!empty($_GET['organizer'])) {
	$mode = 'organizer';
	$ETag .= '--organizer';

} elseif (!empty($_GET['wizard'])) {
	$mode = 'wizard';
	$ETag .= '--wizard';
}

$flagJsAsNotLoaded = $mode !== 'visitor';

//Cache this combination of running Plugin JavaScript
ze\cache::useBrowserCache($ETag);

//Catch a bug where someone could cause a database error by not passing a list of ids
if ($moduleIds === []) {
	exit;
}


//Run pre-load actions

if (ze::$canCache) require CMS_ROOT. 'zenario/includes/wrapper.pre_load.inc.php';


ze\cache::start();
ze\db::loadSiteConfig();


//Get a list of each Plugin
foreach ($moduleIds as $moduleId) {
	if ($module = ze\module::details($moduleId)) {
		$moduleDetails[$module['class_name']] = $module;
	}
}

//Catch a bug where someone could cause a database error by not passing a list of ids
if ($moduleDetails === []) {
	exit;
}

//Add Plugin inheritances as well
foreach (array_keys($moduleDetails) as $moduleClassName) {
	foreach (ze\module::inheritances($moduleDetails[$moduleClassName]['class_name'], 'include_javascript', false) as $inheritanceClassName) {
		
		//Bugfix - if we see one of the modules that we were already going to load is a dependant, move it up in the order.
		if (isset($moduleDetails[$inheritanceClassName])) {
			$mDetails = $moduleDetails[$inheritanceClassName];
			unset($moduleDetails[$inheritanceClassName]);
			$moduleDetails[$inheritanceClassName] = $mDetails;
		
		} else {
			$moduleDetails[$inheritanceClassName] = ze\module::details($inheritanceClassName, $fetchBy = 'class');
		}
	}
}

//Try to put the modules in dependency order
$moduleDetails = array_reverse($moduleDetails, true);


//For FEA plugins, we need to load a list of their paths & which type of FEA logic is used for each path.
//(This used to be hand-written in each plugin's JS file, but now this list is calculated automatically.)
$sql = '
	SELECT module_class_name, path, panel_type
	FROM '. DB_PREFIX. 'tuix_file_contents
	WHERE `type` = \'visitor\'
	  AND module_class_name IN ('. ze\escape::in(array_keys($moduleDetails), 'asciiInSQL'). ')';

$result = ze\sql::select($sql);
while ($fea = ze\sql::fetchRow($result)) {
	$className = $fea[0];
	$path = $fea[1];
	$feaType = $fea[2];
	
	if (!isset($moduleDetails[$className]['feaPaths'])) {
		$moduleDetails[$className]['feaPaths'] = [];
	}
	$moduleDetails[$className]['feaPaths'][$path] = $feaType;
}


//Add JavaScript support elements for each Plugin on the page

$includeMicrotemplates = [];
if (!empty($moduleDetails)) {
	
	//Create a namespace for each Plugin used on this page
	echo '(function(c){';
	
	foreach ($moduleDetails as $module) {
		echo "\n", 'c(', (int) $module['module_id'], ', ', json_encode($module['class_name']), ', ';
	
		if ($module['class_name'] == $module['vlp_class']) {
			echo '1';
		} else {
			echo json_encode($module['vlp_class']);
		}
		
		if (isset($module['feaPaths'])) {
			echo ', ', json_encode($module['feaPaths']);
		}
		
		if ($flagJsAsNotLoaded) {
			if (!isset($module['feaPaths'])) {
				echo ', undefined';
			}
			echo ', 1';
		}
		
		echo ');';
	}
	echo "\n", '})(zenario.enc);';

	foreach ($moduleDetails as $module) {
		if ($jsDir = ze::moduleDir($module['class_name'], 'js/')) {
			if (!empty($_GET['admin_frontend'])) {
				//Add the Plugins's Admin Frontend library
				ze\cache::incJS($jsDir. 'admin_frontend', true);
	
			} elseif (!empty($_GET['organizer'])) {
				//Add the any JavaScript needed for Organizer
				ze\cache::incJS($jsDir. 'organizer', true);
				ze\cache::incJS($jsDir. 'storekeeper', true);
	
			} elseif (!empty($_GET['wizard'])) {
				ze\cache::incJS($jsDir. 'wizard', true);
	
			} else {
				//Add the Plugin's library include if it exists
				ze\cache::incJS($jsDir. 'plugin', true);
		
				//For Admins, add the Plugin's admin js if it exists
				if (!empty($_GET['admin'])) {
					ze\cache::incJS($jsDir. 'admin', true);
				}
				
				if ($jsDir = ze::moduleDir($module['class_name'], 'microtemplates/', true)) {
					$includeMicrotemplates[] = $jsDir;
				}
			}
		}
	}
	
	if (!empty($includeMicrotemplates)) {
		ze\cache::outputMicrotemplates($includeMicrotemplates, 'zenario.microTemplates');
	}
}

if (!empty($_GET['organizer'])) {
	//Get a list of Module names for use in the formatting options
	$moduleInfo =
		ze\sql::fetchAssocs("
			SELECT id, class_name, display_name, status IN ('module_running', 'module_is_abstract') AS running
			FROM ". DB_PREFIX. "modules");
	
	foreach ($moduleInfo as &$info) {
		$info['id'] = (int) $info['id'];
		$info['running'] = (bool) $info['running'];
	}
	
	echo '
		zenarioA.setModuleInfo(', json_encode($moduleInfo), ');';
}


//Run post-display actions
if (ze::$canCache) require CMS_ROOT. 'zenario/includes/wrapper.post_display.inc.php';
