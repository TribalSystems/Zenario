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

$id = arrayKey($box, 'key', 'id');
$parent_menu_id = arrayKey($box, 'key', 'parentMenuID');

if ($id) {
	if (getRow('menu_nodes', 'parent_id', $id)) {
		exitIfNotCheckPriv('_PRIV_EDIT_MENU_ITEM');
	} else {
		exitIfNotCheckPriv('_PRIV_EDIT_MENU_ITEM');
	}
} else {
	if ($parent_menu_id) {
		exitIfNotCheckPriv('_PRIV_ADD_MENU_ITEM');
	} else {
		exitIfNotCheckPriv('_PRIV_ADD_MENU_ITEM');
	}
}

$cID = $cType = false;
getCIDAndCTypeFromTagId($cID, $cType, $values['text/hyperlink_target']);

$submission = array();
if (!$id) {
	$submission['section_id'] = menuSectionId($box['key']['sectionId']);
	$submission['parent_id'] = $parent_menu_id;
}

if (engToBooleanArray($box['tabs']['text'], 'edit_mode', 'on')) {
	$submission['target_loc'] = $values['text/target_loc'];
	
	
	if ($submission['target_loc'] == 'exts') {
		$submission['target_loc'] = 'ext';
	}
	
	$submission['content_id'] = $cID;
	$submission['content_type'] = $cType;
	$submission['hide_private_item'] = $values['text/hide_private_item'];
	$submission['use_download_page'] = $values['text/use_download_page'];
	//$submission['ext_url'] = $values['destination/ext_url'];
	$submission['open_in_new_window'] = $values['text/open_in_new_window'];
	$submission['anchor'] = ($values['text/target_loc'] == 'int') ? $values['text/hyperlink_anchor'] : '';
}

if (engToBooleanArray($box['tabs']['advanced'], 'edit_mode', 'on')) {
	$submission['accesskey'] = $values['advanced/accesskey'];
	$submission['rel_tag'] = $values['advanced/rel_tag'];
	$submission['css_class'] = $values['advanced/css_class'];
	
	$hide_by_static_method = $values['advanced/hide_by_static_method'];
	$submission['module_class_name'] = $hide_by_static_method ? $values['advanced/menu__module_class_name'] : '';
	$submission['method_name'] = $hide_by_static_method ? $values['advanced/menu__method_name'] : '';
	$submission['param_1'] = $hide_by_static_method ? $values['advanced/menu__param_1'] : '';
	$submission['param_2'] = $hide_by_static_method ? $values['advanced/menu__param_2'] : '';
	
	if ($imageId = $values['advanced/image_id']) {
		if ($path = getPathOfUploadedFileInCacheDir($imageId)) {
			$imageId = addFileToDatabase('menu', $path);
		}
	}
	$submission['image_id'] = $imageId;
	
	if ($values['advanced/use_rollover_image']) {
		if ($rolloverImageId = $values['advanced/rollover_image_id']) {
			if ($path = getPathOfUploadedFileInCacheDir($rolloverImageId)) {
				$rolloverImageId = addFileToDatabase('menu', $path);
			}
		}
		$submission['rollover_image_id'] = $rolloverImageId;
	} else {
		$submission['rollover_image_id'] = 0;
	}
	
	
}

$box['key']['id'] = saveMenuDetails($submission, $id);

if (engToBooleanArray($box['tabs']['advanced'], 'edit_mode', 'on')) {
	
	$sql = "
		DELETE f.*
		FROM ". DB_NAME_PREFIX. "files AS f
		LEFT JOIN `". DB_NAME_PREFIX. "menu_nodes` AS l
		   ON l.`image_id` = f.id
		   OR l.`rollover_image_id` = f.id
		WHERE l.image_id IS NULL
		  AND l.rollover_image_id IS NULL
		  AND f.location = 'db'
		  AND f.`usage` = 'menu'";
	
	my_mysql_query($sql);
}


$langs = getLanguages();
$numLangs = count($langs);

foreach ($langs as $lang) {
	
	if (engToBooleanArray($box['tabs']['text'], 'edit_mode', 'on')) {
		$submission = array();
		
		//Remove a Menu Node without any text.
		if (!$values['text/menu_title__'. $lang['id']]) {
			removeMenuText($box['key']['id'], $lang['id']);
			continue;
		
		} else {
			$submission['name'] = $values['text/menu_title__'. $lang['id']];
		}
		
		saveMenuText($box['key']['id'], $lang['id'], $submission);
	}
	
	$submission = array();
	
	if (engToBooleanArray($box['tabs']['text'], 'edit_mode', 'on')) {
		if ($values['text/target_loc'] == 'exts') {
			$submission['ext_url'] = $values['text/ext_url__'. $lang['id']];
		} else {
			$submission['ext_url'] = $values['text/ext_url'];
		}
	}
	
	if (!empty($submission)) {
		saveMenuText($box['key']['id'], $lang['id'], $submission, $neverCreate = true);
	}
}

//For Menu Items in the Front End, navigate to that page if it's an internal link.
//Always recalculate the link from the chosen destination, as this may just have been changed
if ($cID) {
	$box['key']['cID'] = $cID;
	$box['key']['cType'] = $cType;
	
	if (!empty($box['key']['languageId'])) {
		langEquivalentItem($box['key']['cID'], $box['key']['cType'], $box['key']['languageId']);
	}
}

return false;