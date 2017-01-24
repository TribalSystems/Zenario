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

//Add a new Twig Snippet to the nest, from a module's twig/ directory
$moduleClassName = preg_replace('/[^\w-]/', '', $moduleClassName);
$snippetName = 'twig/'. preg_replace('/[^\w-]/', '', $snippetName). '.twig.html';

if ((canActivateModule($moduleClassName))
 && (canActivateModule('zenario_twig_snippet'))
 && ($moduleId = getModuleIdByClassName('zenario_twig_snippet'))
 && ($instance = getPluginInstanceDetails($instanceId))
 && ($filepath = moduleDir($moduleClassName, false, $checkExists = true))) {
	
	$with = '';
	$matches = array();
	if (($file_get_contents = file_get_contents(CMS_ROOT. $filepath))
	 && (preg_match('@\{\#.*?(\bwith\b\s*\{.*?\})\s*\#\}@', $file_get_contents, $matches))) {
		$with = $matches[1]. ' ';
	}
	
	$nestId = self::addPlugin($moduleId, $instanceId, $tab, $snippetName, $tabIsTabId);
	
	setRow(
		'plugin_settings',
		array(
			'value' => '{% include moduleDir(\''. $moduleClassName. '\', \''. $snippetName. '\') '. $with. '%}',
			'is_content' => $instance['content_id']? 'version_controlled_setting' : 'synchronized_setting'),
		array(
			'instance_id' => $instanceId,
			'nest' => $nestId,
			'name' => 'html'));
	
	return $nestId;
} else {
	return false;
}