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


if (!\ze\contentAdm::isCTypeRunning($cType)) {
	echo
		\ze\admin::phrase(
			'Content items of type "[[cType]]" cannot be created as their handler module is missing or not running.',
			['cType' => $cType]);
	exit;
}

//Have the ability to move from one Content Type to another (not recommended for Content Typed that use mirror tables)
$cIDTo = (int) $cIDTo;
$cTypeTo = $cType;
$contentTypeDetails = \ze\contentAdm::cTypeDetails($cTypeTo);
if (!$cTypeFrom) {
	$cTypeFrom = $cType;
}

if (!$adminId) {
	$adminId = $_SESSION['admin_userid'] ?? false;
}

//Check to see if a target Content Item has been set, and actually exists
$newDraftCreated = true;
if (!$cIDTo || !($content = \ze\row::get('content_items', true, ['id' => $cIDTo, 'type' => $cTypeTo]))) {
	
	//If there was no target, create the details for a new Content Item
	if (!$cIDTo || !$useCIDIfItDoesntExist) {
		$cIDTo = \ze\content::latestId($cTypeTo) + 1;
	}
	
	$content = [
		'equiv_id' => $cIDTo,
		'id' => $cIDTo,
		'type' => $cTypeTo,
		'tag_id' => $cTypeTo. '_'. $cIDTo,
		'language_id' => ($languageId ?: \ze::$defaultLang),
		'first_created_datetime' => \ze\date::now(),
		'visitor_version' => 0,
		'admin_version' => 1,
		'status' => 'first_draft'];
	
	//Create an entry in the translation_chains table for this translation chain if one is not already there
	$key = ['equiv_id' => $content['equiv_id'], 'type' => $content['type']];
	if (!\ze\row::exists('translation_chains', $key)) {
		$chain = [];
		
		//T10672, Creating a content item: Automatically set permissions
		if (\ze\module::inc('zenario_users') && $contentTypeDetails) {
			$chain['privacy'] = $contentTypeDetails['default_permissions'] ?? 'public';
		}
		
		\ze\row::set('translation_chains', $chain, $key);
	}

//If there was a target, create a new draft version
} else {
	if ($content['status'] == 'published') {
		$content['status'] = 'published_with_draft';
		++$content['admin_version'];
	
	} elseif ($content['status'] == 'unlisted') {
		$content['status'] = 'unlisted_with_draft';
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

//Update/insert into the content table
\ze\row::set('content_items', $content, ['id' => $content['id'], 'type' => $content['type']]);


//Check to see if the source content item version has been set, and actually exists
if (!$cIDFrom
 || !$cTypeFrom
 || !($cVersionFrom = $cVersionFrom ?: \ze\content::latestVersion($cIDFrom, $cTypeFrom))
 || !($version = \ze\row::get('content_item_versions', true, ['id' => $cIDFrom, 'type' => $cTypeFrom, 'version' => $cVersionFrom]))) {
	$cIDFrom = false;
	$cVersionFrom = false;
	$version = [];
	
	
	//T10208, Creating content items: auto-populate release date and author where used
	
	if (!empty($contentTypeDetails['writer_field'])
	 && $contentTypeDetails['writer_field'] != 'hidden'
	 && ($adminDetails = \ze\admin::details($adminId))) {
		$currentAdminId = ze\admin::id();

		//Check if this admin has a writer profile.
		$writerProfile = ze\row::get('writer_profiles', ['id'], ['admin_id' => (int) $currentAdminId]);
		if ($writerProfile) {
			$version['writer_id'] = $writerProfile['id'];
		}
	}
	
	if (!empty($contentTypeDetails['release_date_field'])
	 && $contentTypeDetails['release_date_field'] == 'mandatory') {
		$version['release_date'] = \ze\date::ymd();
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
	$version['created_datetime'] = \ze\date::now(true);
	$version['creating_author_id'] = $adminId;
} else {
	unset($version['created_datetime']);
	unset($version['creating_author_id']);
}


//Try to ensure a template is set, as a Content Item must have a template
if (empty($version['layout_id'])) {
	$version['layout_id'] = \ze\layoutAdm::defaultId($content['type']);
}

//Insert into the versions table
\ze\row::set('content_item_versions', $version, ['id' => $version['id'], 'type' => $version['type'], 'version' => $version['version']]);



if ($newDraftCreated) {
	//Copy everything from the Source Content Item, if one was set
	if ($cIDFrom) {
		//Copy the record of which inline files are used here
		//Note that while the "in_use" column is set here, it will be recalculated by the \ze\contentAdm::syncInlineFileContentLink() function below
		$sql = "
			REPLACE INTO ". DB_PREFIX. "inline_images (
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
				'". \ze\escape::sql($cTypeTo). "',
				". (int) $cVersionTo. ",
				in_use
			FROM ". DB_PREFIX. "inline_images
			WHERE foreign_key_to = 'content'
			  AND foreign_key_id = ". (int) $cIDFrom. "
			  AND foreign_key_char = '". \ze\escape::sql($cTypeFrom). "'
			  AND foreign_key_version = ". (int) $cVersionFrom;
		
		//When duplicating a content item, try to only copy links to images that were flagged as in use.
		if ($cIDTo != $cIDFrom) {
			$sql .= "
			  AND in_use = 1";
		}
		
		\ze\sql::cacheFriendlyUpdate($sql);  //No need to check the cache as the other statements should clear it correctly
		
		//Copy Slot Contents
		$sql = "
			REPLACE INTO ". DB_PREFIX. "plugin_item_link (
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
				'". \ze\escape::sql($cTypeTo). "',
				". (int) $cVersionTo. ",
				slot_name
			FROM ". DB_PREFIX. "plugin_item_link
			WHERE content_id = ". (int) $cIDFrom. "
			  AND content_type = '". \ze\escape::asciiInSQL($cTypeFrom). "'
			  AND content_version = ". (int) $cVersionFrom;
		\ze\sql::cacheFriendlyUpdate($sql);  //No need to check the cache as the other statements should clear it correctly
		
		
		$sql = "
			REPLACE INTO ". DB_PREFIX. "content_cache (
				content_id,
				content_type,
				content_version,
				text,
				extract,
				extract_wordcount
			) SELECT
				". (int) $cIDTo. ",
				'". \ze\escape::sql($cTypeTo). "',
				". (int) $cVersionTo. ",
				text,
				extract,
				extract_wordcount
			FROM ". DB_PREFIX. "content_cache
			WHERE content_id = ". (int) $cIDFrom. "
			  AND content_type = '". \ze\escape::asciiInSQL($cTypeFrom). "'
			  AND content_version = ". (int) $cVersionFrom;
		\ze\sql::cacheFriendlyUpdate($sql);  //No need to check the cache as the other statements should clear it correctly
		
		
		\ze\pluginAdm::duplicateVC($cIDFrom, $cTypeFrom, $cVersionFrom, $cIDTo, $cTypeTo, $cVersionTo);
		\ze\pluginAdm::removeUnusedVCs($cIDTo, $cTypeTo, $cVersionTo);
		\ze\contentAdm::flagImagesInArchivedVersions($cIDTo, $cTypeTo);
		\ze\contentAdm::syncInlineFileContentLink($cIDTo, $cTypeTo, $cVersionTo);
	}

	\ze\module::sendSignal("eventDraftCreated", ["cIDTo" => $cIDTo, "cIDFrom" => $cIDFrom, "cTypeTo" => $cTypeTo, "cVersionTo" => $cVersionTo, "cVersionFrom" => $cVersionFrom, "cTypeFrom" => $cTypeFrom]);
}

return $newDraftCreated;