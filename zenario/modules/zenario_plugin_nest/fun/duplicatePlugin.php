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


//Duplicate a Plugin
if (($nestedItem = getNestDetails($nestedItemId, $instanceId)) && (!$nestedItem['is_tab'])) {
	if ($newNestedItemId = self::addPlugin($nestedItem['module_id'], $instanceId, $tab = false)) {
		
		updateRow('nested_plugins', array('name_or_title' => $nestedItem['name_or_title'], 'framework' => $nestedItem['framework'], 'css_class' => $nestedItem['css_class']), $newNestedItemId);
		
		$sql = "
			INSERT INTO ". DB_NAME_PREFIX. "plugin_settings (
				instance_id, name, nest,
				value, is_content, foreign_key_to, foreign_key_id, foreign_key_char, dangling_cross_references
			) SELECT
				instance_id, name, ". (int) $newNestedItemId. ",
				value, is_content, foreign_key_to, foreign_key_id, foreign_key_char, dangling_cross_references
			FROM ". DB_NAME_PREFIX. "plugin_settings
			WHERE instance_id = ". (int) $instanceId. "
			  AND nest = ". (int) $nestedItemId;
		
		sqlSelect($sql);  //No need to check the cache as the other statements should clear it correctly
		
		managePluginCSSFile('copy', $instanceId, $nestedItemId, $instanceId, $newNestedItemId);
		
		return $newNestedItemId;
	}
}

return false;