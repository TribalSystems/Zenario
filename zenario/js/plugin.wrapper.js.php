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

header('Content-Type: text/javascript; charset=UTF-8');
require '../basicheader.inc.php';

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

if (!empty($_GET['admin_frontend'])) {
	$ETag .= '--admin_frontend';

} elseif (!empty($_GET['organizer'])) {
	$ETag .= '--organizer';

} elseif (!empty($_GET['wizard'])) {
	$ETag .= '--wizard';
}

//Cache this combination of running Plugin JavaScript
useCache($ETag);


//Run pre-load actions
require editionInclude('wrapper.pre_load');


useGZIP(isset($_GET['gz']) && $_GET['gz']);
require CMS_ROOT. 'zenario/includes/cms.inc.php';
loadSiteConfig();


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

$includeMicrotemplates = array();
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
		echo ');';
	}
	echo "\n", '})(zenario.enc);';

	foreach ($moduleDetails as $module) {
		if ($jsDir = moduleDir($module['class_name'], 'js/')) {
			if (!empty($_GET['admin_frontend'])) {
				//Add the Plugins's Admin Frontend library
				incJS($jsDir. 'admin_frontend', true);
	
			} elseif (!empty($_GET['organizer'])) {
				//Add the any JavaScript needed for Organizer
				incJS($jsDir. 'organizer', true);
				incJS($jsDir. 'storekeeper', true);
	
			} elseif (!empty($_GET['wizard'])) {
				incJS($jsDir. 'wizard', true);
	
			} else {
				//Add the Plugin's library include if it exists
				incJS($jsDir. 'plugin', true);
		
				//For Admins, add the Plugin's admin js if it exists
				if (!empty($_GET['admin'])) {
					incJS($jsDir. 'admin', true);
				}
				
				if ($jsDir = moduleDir($module['class_name'], 'microtemplates/', true)) {
					$includeMicrotemplates[] = $jsDir;
				}
			}
		}
	}
	
	if (!empty($includeMicrotemplates)) {
		
		function esctick($text) {
			$searches = array('`', '~');
			$replaces = array('`t', '`s');
			return str_replace($searches, $replaces, $text);
		}
		
		
		$output = '';
		foreach ($includeMicrotemplates as $jsDir) {
			foreach (scandir($dir = CMS_ROOT. $jsDir) as $file) {
				if (substr($file, 0, 1) != '.' && substr($file, -5) == '.html' && is_file($dir. $file)) {
					$name = substr($file, 0, -5);
					$output .= esctick($name). '~'. esctick(preg_replace('@\s+@', ' ', file_get_contents($dir. $file))). '~';
				}
			}
		}
		echo "\n". 'zenario._uAM(zenario.microTemplates,', json_encode($output), ');';
		unset($output);
	}
}

if (!empty($_GET['organizer'])) {
	//Get a list of Module names for use in the formatting options
	$moduleInfo =
		sqlFetchAssocs("
			SELECT id, class_name, display_name, status IN ('module_running', 'module_is_abstract') AS running
			FROM ". DB_NAME_PREFIX. "modules");
	
	foreach ($moduleInfo as &$info) {
		$info['id'] = (int) $info['id'];
		$info['running'] = (bool) $info['running'];
	}
	
	echo '
		zenarioA.setModuleInfo(', json_encode($moduleInfo), ');';
}


//Run post-display actions
require editionInclude('wrapper.post_display');
