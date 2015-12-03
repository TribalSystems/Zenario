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


$instance = getPluginInstanceDetails($instanceId);
$nestable = getRow('modules', 'nestable', $instance['module_id']);

$sql = "
	SELECT COUNT(*)
	FROM ". DB_NAME_PREFIX. "nested_plugins
	WHERE is_tab = 0
	  AND instance_id = ". (int) $instanceId;

$result = sqlQuery($sql);
$row = sqlFetchRow($result);
$numPlugins = $row[0];

if ($nestable && $numPlugins == 0) {
	$moduleId = $instance['module_id'];
	
	$numPlugins = 1;
	$onlyOneModule = true;
	$currentClassName = 'standalone';

} elseif (!$nestable && $numPlugins > 0) {
	$currentClassName = $instance['class_name'];
	
	$sql = "
		SELECT DISTINCT module_id
		FROM ". DB_NAME_PREFIX. "nested_plugins
		WHERE is_tab = 0
		  AND instance_id = ". (int) $instanceId;
	
	$result = sqlQuery($sql);
	if ($row = sqlFetchAssoc($result)) {
		$moduleId = $row['module_id'];
		$onlyOneModule = !sqlFetchAssoc($result);
	}

} else {
	return false;
}

//Don't allow non-draft or locked Wireframes to be changed
if ($instance['content_id'] && !checkPriv(false, $instance['content_id'], $instance['content_type'], $instance['content_version'])) {
	return false;
}



$onlyBanners = $onlyOneModule && $moduleId == getRow('modules', 'id', array('class_name' => 'zenario_banner'));

foreach ($fields as $key => &$field) {
	if (is_array($field)) {
		if (isset($field['current_module'])) {
			if ($field['current_module'] != $currentClassName) {
				$field['hidden'] = true;
			
			} else {
				$field['label'] = adminPhrase('Current usage:');
			}
		
		} elseif (isset($field['convert_to'])) {
			if ($field['convert_to'] == $currentClassName) {
				$field['hidden'] = true;
			}
			
			if ($field['convert_to'] == $currentClassName
			 || (engToBooleanArray($field, 'req_only_1_plugin') && $numPlugins > 1)
			 || (engToBooleanArray($field, 'req_only_banner_plugins') && !$onlyBanners)
			 || (engToBooleanArray($field, 'req_only_1_type_of_plugin') && !$onlyOneModule)) {
				$field['disabled'] = true;
				if (isset($field['post_field_html'])) {
					$field['post_field_html'] = adminPhrase('[[name]] (unavailable)', array('name' => strip_tags($field['post_field_html'])));
				}
			}
		}
	}
}
