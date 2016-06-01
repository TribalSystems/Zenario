<?php
/*
 * Copyright (c) 2016, Tribal Limited
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
$fileIds = array();
$sql = "
	SELECT ps.value
	FROM ". DB_NAME_PREFIX. "plugin_instances AS pi
	INNER JOIN ". DB_NAME_PREFIX. "plugin_settings AS ps
	   ON pi.id = ps.instance_id
	  AND ps.is_content = 'version_controlled_setting'
	  AND ps.foreign_key_to IN('file', 'multiple_files')
	WHERE pi.content_id = ". (int) $cID. "
	  AND pi.content_type = '". sqlEscape($cType). "'
	  AND pi.content_version = ". (int) $cVersion;
$result = sqlQuery($sql);

while ($fileIdsInPlugin = sqlFetchRow($result)) {
	if ($fileIdsInPlugin = explode(',', $fileIdsInPlugin[0])) {
		foreach ($fileIdsInPlugin as $fileId) {
			if ($fileId = (int) trim($fileId)) {
				$fileIds[$fileId] = $fileId;
			}
		}
	}
}

//Note down the sticky image for this Content Item, if there is one
if ($fileId = getRow('content_item_versions', 'sticky_image_id', array('id' => $cID, 'type' => $cType, 'version' => $cVersion))) {
	$fileIds[$fileId] = $fileId;
}

//Do a quick check to see if all of those ids exist, only add the ones in the database!
if (!empty($fileIds)) {
	$files = getRowsArray('files', array('id', 'usage', 'privacy'), array('id' => $fileIds));
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
	syncInlineFileLinks($files, $row['value'], $htmlChanged);
	
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
		'foreign_key_version' => $cVersion),
	$keepOldImagesThatAreNotInUse = true);

//Update the Content in the cache table
$text = trim(strip_tags($content));
setRow('content_cache', array('text' => $text, 'text_wordcount' => str_word_count($text)), array('content_id' => $cID, 'content_type' => $cType, 'content_version' => $cVersion));


//Fix for T10031, Images in WYSIWYG Editors staying on "will auto detect" in the image library
//Look through any of the images used on this content item that are still set to auto-detect,
//and set them to either public or private (depending on this item's privacy setting) when
//the content item is published.
if ($publishing && !empty($files)) {
	$citemPrivacy = getRow('translation_chains', 'privacy', array('equiv_id' => equivId($cID, $cType), 'type' => $cType));
	
	if ($citemPrivacy == 'public') {
		$privacy = 'public';
	} else {
		$privacy = 'private';
	}
	
	foreach ($files as $fileId => $file) {
		if ($file['usage'] == 'image'
		 && $file['privacy'] == 'auto') {
			updateRow('files', array('privacy' => $privacy), $fileId);
		}
	}
}