<?php
/*
 * Copyright (c) 2014, Tribal Limited
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

$files = array();
$content = '';


//Add images linked via Version Controlled modules
$sql = "
	SELECT f.id
	FROM ". DB_NAME_PREFIX. "plugin_instances AS pi
	INNER JOIN ". DB_NAME_PREFIX. "plugin_settings AS ps
	   ON pi.id = ps.instance_id
	  AND ps.is_content = 'version_controlled_setting'
	INNER JOIN ". DB_NAME_PREFIX. "files AS f
	   ON ps.foreign_key_to = 'file'
	  AND ps.foreign_key_id = f.id
	WHERE pi.content_id = ". (int) $cID. "
	  AND pi.content_type = '". sqlEscape($cType). "'
	  AND pi.content_version = ". (int) $cVersion;
$result = sqlQuery($sql);

while ($file = sqlFetchAssoc($result)) {
	$fileId = $file['id'];
	$files[$fileId] = array('id' => $fileId);
}


//Note down the sticky image for this Content Item, if there is one
if ($fileId = getRow('versions', 'sticky_image_id', array('id' => $cID, 'type' => $cType, 'version' => $cVersion))) {
	$files[$fileId] = array('id' => $fileId);
}


//Get each content area (which will have been converted into HTML snippets)
$sql = "
	SELECT ps.instance_id, ps.name, ps.nest, ps.value
	FROM ". DB_NAME_PREFIX. "plugin_instances AS pi
	INNER JOIN ". DB_NAME_PREFIX. "plugin_settings AS ps
	   ON pi.id = ps.instance_id
	WHERE pi.content_id = ". (int) $cID. "
	  AND pi.content_type = '". sqlEscape($cType). "'
	  AND pi.content_version = ". (int) $cVersion. "
	  AND ps.is_content = 'version_controlled_content'";
$result = sqlQuery($sql);

while ($row = sqlFetchAssoc($result)) {
	$htmlChanged = false;
	syncInlineFileLinks('inline', $files, $row['value'], $htmlChanged);
	
	//Keep a block of all of the content to put into the cache table
	$content .= $row['value']. "\n";
	
	if ($htmlChanged) {
		updateRow(
			'plugin_settings',
			array('value' => $row['value']),
			array('instance_id' => $row['instance_id'], 'name' => $row['name'], 'nest' => $row['nest']));
	}
}


//Update the link table
syncInlineFiles(
	$files,
	array(
		'foreign_key_to' => 'content',
		'foreign_key_id' => $cID,
		'foreign_key_char' => $cType,
		'foreign_key_version' => $cVersion));

//Update the Content in the cache table
setRow('content_cache', array('text' => trim(strip_tags($content))), array('content_id' => $cID, 'content_type' => $cType, 'content_version' => $cVersion));
