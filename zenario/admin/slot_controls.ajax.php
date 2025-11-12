<?php
/*
 * Copyright (c) 2025, Tribal Limited
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
 
/*
	The slot controls don't normally use AJAX.
	However this script just exists to load them in debug mode when using the TUIX inspector.
*/


$mode = false;
$tagPath = '';
$tags = [];
$modules = [];
$moduleFilesLoaded = [];
$debugMode = true;
$settingGroup = '';
$compatibilityClassNames = [];
ze::$tuixType = $type = 'slot_controls';

//See if there is a requested path.
$path = preg_replace('/[^\w\/]/', '', $_REQUEST['path'] ?? '');
ze::$tuixPath = $path;


\ze\tuix::load($moduleFilesLoaded, $tags, $type, $path);
$removedColumns = false;
\ze\tuix::parse2($tags, $removedColumns, $type, $path);
$tags = $tags[$path];




#//$moduleId = (int) ($_REQUEST['moduleId'] ?? 0);
#$moduleClassName = preg_replace('/[^\w\/]/', '', $_REQUEST['moduleClassName'] ?? '');
#
#
#$ob = ['class_name' => 'zenario_common_features'];
#ze\tuix::includeModule($modules, $ob, $type, $path, $settingGroup);
#
#
#switch ($path) {
#	case 'full_slot':
#	case 'full_sitewide_slot':
#		foreach (\ze\module::inheritances($moduleClassName, 'inherit_settings') as $className) {
#			$ob = ['class_name' => $className];
#			ze\tuix::includeModule($modules, $ob, $type, $path, $settingGroup);
#		}
#}





ze\tuix::displayDebugMode($tags, $moduleFilesLoaded, $moduleFilesLoaded, $tagPath);
exit;