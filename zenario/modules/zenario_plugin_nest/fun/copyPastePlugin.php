<?php
/*
 * Copyright (c) 2024, Tribal Limited
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


//Get details on what we're about to copy
if ($isEgg) {
	$source = ze\pluginAdm::getNestDetails($sourceId);
	
	if (!$source) {
		return false;
	}
	
	if ($source['is_slide']) {
		return false;
	}
	
	$sourceInstanceId = $source['instance_id'];
	$sourceEggId = $sourceId;

} else {
	$source = ze\plugin::details($sourceId);
	
	if (!$source) {
		return false;
	}
	
	$sourceInstanceId = $sourceId;
	$sourceEggId = 0;
}

if ($mustBeBanner && $source['module_id'] != ze\module::id('zenario_banner')) {
	return false;
}


//Get details on where we're aiming it
$dest = ze\pluginAdm::getNestDetails($destId);

if (!$dest) {
	return false;
}

if ($dest['is_slide']) {
	$insert = false;
	$ord = 1 + (int) self::maxOrd($instanceId, $dest['slide_num']);
} else {
	$insert = true;
	$ord = $dest['ord'];
}

if ($newEggId = self::addPlugin($source['module_id'], $instanceId, $dest['slide_num'])) {
	
	//Bump up the ordinals of any other plugins on this slide by one,
	//so we can place this new plugin just after the one we duplicated
	if ($insert) {
		ze\sql::update("
			UPDATE ". DB_PREFIX. "nested_plugins
			  SET ord = ord + 1
			WHERE ord > ". (int) $ord. "
			  AND instance_id = ". (int) $instanceId. "
			  AND slide_num = ". (int) $dest['slide_num']
		);
	}
	
	//Put the copied plugin in at the correct place, and set some vars
	ze\row::update('nested_plugins', [
		'ord' => $ord,
		'framework' => $source['framework'],
		'css_class' => $source['css_class'],
		'cols' => $source['cols'] ?? 0,
		'small_screens' => $source['small_screens'] ?? 'show'
	], $newEggId);
	
	//Copy the plugin settings
	$sql = "
		INSERT INTO ". DB_PREFIX. "plugin_settings (
			instance_id, name, egg_id,
			value, is_content, foreign_key_to, foreign_key_id, foreign_key_char, dangling_cross_references
		) SELECT
			". (int) $instanceId. ", name, ". (int) $newEggId. ",
			value, is_content, foreign_key_to, foreign_key_id, foreign_key_char, dangling_cross_references
		FROM ". DB_PREFIX. "plugin_settings
		WHERE instance_id = ". (int) $sourceInstanceId. "
		  AND egg_id = ". (int) $sourceEggId. "
		ORDER BY instance_id, egg_id, name";
	
	ze\sql::cacheFriendlyUpdate($sql);  //No need to check the cache as the other statements should clear it correctly
	
	//Copy and custom CSS
	ze\pluginAdm::manageCSSFile('copy', $sourceInstanceId, $sourceEggId, $instanceId, $newEggId);
	
	return $newEggId;
}

return false;