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

require '../adminheader.inc.php';
ze\cache::start();

/*
	This file is used to handle AJAX requests for the admin toolbar.
	It reads all relevant yaml files, then merge them together into a PHP array, calls module methods to process
	that array, and then finally sends them via JSON to the client.
*/


$mode = false;
$tagPath = '';
$modules = array();
$debugMode = (bool) ($_GET['_debug'] ?? false);
$loadDefinition = true;
$settingGroup = '';
$compatibilityClassNames = array();
ze::$skType = $type = 'admin_toolbar';
ze::$skPath = $requestedPath = false;




if ($loadDefinition) {
	//Scan the Module directory for Modules with the relevant TUIX files, read them, and get a php array
	$moduleFilesLoaded = array();
	$tags = array();
	$originalTags = array();
	ze\tuix::load($moduleFilesLoaded, $tags, $type, $requestedPath, $settingGroup, $compatibilityClassNames);
}

if ($debugMode) {
	$staticTags = $tags;
}



//See if other modules have added toolbars/sections/buttons. If so, they'll need their own placeholder methods executing as well.
if (isset($tags['toolbars']) && is_array($tags['toolbars'])) {
	foreach ($tags['toolbars'] as &$toolbar) {
		if (!empty($toolbar['class_name'])) {
			ze\tuix::includeModule($modules, $toolbar, $type, $requestedPath, $settingGroup);
		}
	}
}
if (isset($tags['sections']) && is_array($tags['sections'])) {
	foreach ($tags['sections'] as &$section) {
		if (!empty($section['class_name'])) {
			ze\tuix::includeModule($modules, $section, $type, $requestedPath, $settingGroup);
		}
		
		if (isset($section['buttons']) && is_array($section['buttons'])) {
			foreach ($section['buttons'] as &$button) {
				if (!empty($button['class_name'])) {
					ze\tuix::includeModule($modules, $button, $type, $requestedPath, $settingGroup);
				}
			}
		}
	}
}

$removedColumns = false;
if ($loadDefinition) {
	ze\tuix::parse2($tags, $removedColumns, $type, '', $mode);
}

//Debug mode - show the TUIX before it's been modified
if ($debugMode) {
	ze\tuix::displayDebugMode($staticTags, $modules, $moduleFilesLoaded, $tagPath);
	exit;
}

//Apply the modules' specific logic
foreach ($modules as $className => &$module) {
	$module->fillAdminToolbar($tags, (int) ($_REQUEST['cID'] ?? false), ($_REQUEST['cType'] ?? false), (int) ($_REQUEST['cVersion'] ?? false));
}



//Display the output as JSON
header('Content-Type: text/javascript; charset=UTF-8');
	
if ($_REQUEST['_script'] ?? false) {
	echo 'zenarioAT.init2(';
}

ze\ray::jsonDump($tags);

if ($_REQUEST['_script'] ?? false) {
	echo ');';
}