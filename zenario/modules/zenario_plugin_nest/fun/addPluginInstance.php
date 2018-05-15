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


//Add a new Plugin to the nest, placing it in the right-most slide
if (($instance = ze\plugin::details($addPluginInstance))
 && (ze\row::get('modules', 'nestable', $instance['module_id']))) {
	
	if ($slideNum && $inputIsSlideId) {
		$slideNum = ze\row::get('nested_plugins', 'slide_num', ['instance_id' => $instanceId, 'id' => $slideNum]);
	}
	
	if (!$slideNum) {
		$slideNum = self::maxTab($instanceId) ?: 1;
	}
	
	$ord = 1 + (int) self::maxOrd($instanceId, $slideNum);
	
	$eggId = ze\row::insert(
		'nested_plugins',
		[
			'instance_id' => $instanceId,
			'slide_num' => $slideNum,
			'ord' => $ord,
			'module_id' => $instance['module_id'],
			'framework' => $instance['framework'],
			'css_class' => $instance['css_class'],
			'name_or_title' => ze\module::displayName($instance['module_id'])]);
	
	$sql = "
		INSERT INTO ". DB_PREFIX. "plugin_settings (
			instance_id,
			name,
			egg_id,
			value,
			is_content,
			foreign_key_to,
			foreign_key_id,
			foreign_key_char,
			dangling_cross_references
		) SELECT
			". (int) $instanceId. ",
			name,
			". (int) $eggId. ",
			value,";
	
	//Convert the is_content column differently depending on whether this is a Reusable or Wireframe Nest
	if (ze\row::get('plugin_instances', 'content_id', $instanceId)) {
		$sql .= "
			IF (is_content = 'version_controlled_content', 'version_controlled_content', 'version_controlled_setting'),";
	} else {
		$sql .= "
			'synchronized_setting',";
	}
	
	$sql .= "
			foreign_key_to,
			foreign_key_id,
			foreign_key_char,
			dangling_cross_references
		FROM ". DB_PREFIX. "plugin_settings
		WHERE instance_id = ". (int) $addPluginInstance;
	ze\sql::cacheFriendlyUpdate($sql);  //No need to check the cache as the other statements should clear it correctly
	
	ze\contentAdm::resyncLibraryPluginFiles($instanceId, $instance);
	
	//Update the request vars for this slide
	ze\pluginAdm::setSlideRequestVars($instanceId, $slideNum);
	
	
	return $eggId;
} else {
	return false;
}