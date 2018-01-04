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


if (!checkContentTypeRunning($cType)) {
	echo
		adminPhrase(
			'Drafts of "[[cType]]" type Content Items cannot be created as their handler Module is missing or not running.',
			array('cType' => $cType));
	exit;
}

//Have the ability to move from one Content Type to another (not recommended for Content Typed that use mirror tables)
$cIDTo = (int) $cIDTo;
$cTypeTo = $cType;
$contentTypeDetails = getContentTypeDetails($cTypeTo);
if (!$cTypeFrom) {
	$cTypeFrom = $cType;
}

if (!$adminId) {
	$adminId = $_SESSION['admin_userid'] ?? false;
}

//Check to see if a target Content Item has been set, and actually exists
$newDraftCreated = true;
if (!$cIDTo || !($content = getRow('content_items', true, array('id' => $cIDTo, 'type' => $cTypeTo)))) {
	
	//If there was no target, create the details for a new Content Item
	if (!$cIDTo || !$useCIDIfItDoesntExist) {
		$cIDTo = getLatestContentID($cTypeTo) + 1;
	}
	
	$content = array(
		'equiv_id' => $cIDTo,
		'id' => $cIDTo,
		'type' => $cTypeTo,
		'tag_id' => $cTypeTo. '_'. $cIDTo,
		'language_id' => ifNull($languageId, cms_core::$defaultLang),
		'first_created_datetime' => now(),
		'visitor_version' => 0,
		'admin_version' => 1,
		'status' => 'first_draft');
	
	//Create an entry in the translation_chains table for this translation chain if one is not already there
	$key = array('equiv_id' => $content['equiv_id'], 'type' => $content['type']);
	if (!checkRowExists('translation_chains', $key)) {
		$chain = array();
		
		//T10672, Creating a content item: Automatically set permissions
		if (inc('zenario_users') && $contentTypeDetails) {
			$chain['privacy'] = $contentTypeDetails['default_permissions'] ?? 'public';
		}
		
		setRow('translation_chains', $chain, $key);
	}

//If there was a target, create a new draft version
} else {
	if ($content['status'] == 'published') {
		$content['status'] = 'published_with_draft';
		++$content['admin_version'];
	
	} elseif ($content['status'] == 'hidden') {
		$content['status'] = 'hidden_with_draft';
		++$content['admin_version'];
	
	} elseif ($content['status'] == 'trashed') {
		$content['status'] = 'trashed_with_draft';
		++$content['admin_version'];
	
	} elseif ($content['status'] == 'deleted') {
		$content['status'] = 'first_draft';
		$content['admin_version'] = 1;
		$content['visitor_version'] = 0;
	
	} else {
		$newDraftCreated = false;
	}
}

if ($newDraftCreated) {
	if (setting('lock_item_upon_draft_creation')) {
		$content['lock_owner_id'] = $_SESSION['admin_userid'] ?? false;
		$content['locked_datetime'] = now();
	}
}

//Update/insert into the content table
setRow('content_items', $content, array('id' => $content['id'], 'type' => $content['type']));


//Check to see if the Source Content Item version has been set, and actually exists
if (!$cIDFrom
 || !$cTypeFrom
 || !($cVersionFrom = ifNull($cVersionFrom, getLatestVersion($cIDFrom, $cTypeFrom)))
 || !($version = getRow('content_item_versions', true, array('id' => $cIDFrom, 'type' => $cTypeFrom, 'version' => $cVersionFrom)))) {
	$cIDFrom = false;
	$cVersionFrom = false;
	$version = array();
	
	
	//T10208, Creating content items: auto-populate release date and author where used
	
	if (!empty($contentTypeDetails['writer_field'])
	 && $contentTypeDetails['writer_field'] != 'hidden'
	 && ($adminDetails = getAdminDetails($adminId))) {
		$version['writer_id'] = $adminId;
		$version['writer_name'] = $adminDetails['first_name']. ' '. $adminDetails['last_name'];
	}
	
	if (!empty($contentTypeDetails['release_date_field'])
	 && $contentTypeDetails['release_date_field'] != 'hidden') {
		$version['publication_date'] = dateNow();
	}
	
} else {
	$cVersionFrom = $version['version'];
}


//Copy the version table from the source, but update some columns to refer to the target
$version['id'] = $content['id'];
$version['type'] = $content['type'];
$version['tag_id'] = $content['tag_id'];
$version['version'] = $cVersionTo = $content['admin_version'];

//Remove publication columns
unset($version['last_author_id']);
unset($version['last_modified_datetime']);
unset($version['publisher_id']);
unset($version['published_datetime']);
unset($version['concealer_id']);
unset($version['concealed_datetime']);
unset($version['admin_notes']);

if ($newDraftCreated) {
	$version['created_datetime'] = now();
	$version['creating_author_id'] = $adminId;
} else {
	unset($version['created_datetime']);
	unset($version['creating_author_id']);
}


//Try to ensure a template is set, as a Content Item must have a template
if (empty($version['layout_id'])) {
	$version['layout_id'] = getDefaultTemplateId($content['type']);
}

//Insert into the versions table
setRow('content_item_versions', $version, array('id' => $version['id'], 'type' => $version['type'], 'version' => $version['version']));



if ($newDraftCreated) {
	//Copy everything from the Source Content Item, if one was set
	if ($cIDFrom) {
		//Copy the record of which inline files are used here
		//Note that while the "in_use" column is set here, it will be recalculated by the syncInlineFileContentLink() function below
		$sql = "
			REPLACE INTO ". DB_NAME_PREFIX. "inline_images (
				image_id,
				foreign_key_to,
				foreign_key_id,
				foreign_key_char,
				foreign_key_version,
				in_use
			) SELECT
				image_id,
				foreign_key_to,
				". (int) $cIDTo. ",
				'". sqlEscape($cTypeTo). "',
				". (int) $cVersionTo. ",
				in_use
			FROM ". DB_NAME_PREFIX. "inline_images
			WHERE foreign_key_to = 'content'
			  AND foreign_key_id = ". (int) $cIDFrom. "
			  AND foreign_key_char = '". sqlEscape($cTypeFrom). "'
			  AND foreign_key_version = ". (int) $cVersionFrom;
		sqlSelect($sql);  //No need to check the cache as the other statements should clear it correctly
		
		//Copy Slot Contents
		$sql = "
			REPLACE INTO ". DB_NAME_PREFIX. "plugin_item_link (
				module_id,
				instance_id,
				content_id,
				content_type,
				content_version,
				slot_name
			) SELECT
				module_id,
				instance_id,
				". (int) $cIDTo. ",
				'". sqlEscape($cTypeTo). "',
				". (int) $cVersionTo. ",
				slot_name
			FROM ". DB_NAME_PREFIX. "plugin_item_link
			WHERE content_id = ". (int) $cIDFrom. "
			  AND content_type = '". sqlEscape($cTypeFrom). "'
			  AND content_version = ". (int) $cVersionFrom;
		sqlSelect($sql);  //No need to check the cache as the other statements should clear it correctly
		
		
		$sql = "
			REPLACE INTO ". DB_NAME_PREFIX. "content_cache (
				content_id,
				content_type,
				content_version,
				text,
				extract,
				extract_wordcount
			) SELECT
				". (int) $cIDTo. ",
				'". sqlEscape($cTypeTo). "',
				". (int) $cVersionTo. ",
				text,
				extract,
				extract_wordcount
			FROM ". DB_NAME_PREFIX. "content_cache
			WHERE content_id = ". (int) $cIDFrom. "
			  AND content_type = '". sqlEscape($cTypeFrom). "'
			  AND content_version = ". (int) $cVersionFrom;
		sqlSelect($sql);  //No need to check the cache as the other statements should clear it correctly
		
		
		duplicateVersionControlledPluginSettings($cIDTo, $cIDFrom, $cType, $cVersionTo, $cVersionFrom, $cTypeFrom);
		removeUnusedVersionControlledPluginSettings($cIDTo, $cTypeTo, $cVersionTo);
		flagImagesInArchivedVersions($cIDTo, $cTypeTo);
		syncInlineFileContentLink($cIDTo, $cTypeTo, $cVersionTo);
	}

	sendSignal("eventDraftCreated", array("cIDTo" => $cIDTo, "cIDFrom" => $cIDFrom, "cTypeTo" => $cTypeTo, "cVersionTo" => $cVersionTo, "cVersionFrom" => $cVersionFrom, "cTypeFrom" => $cTypeFrom));
}

return $newDraftCreated;