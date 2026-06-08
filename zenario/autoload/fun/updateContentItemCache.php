<?php
/*
 * Copyright (c) 2026, Tribal Limited
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

$files = [];
$content = '';

if ($publishing) {
	$citemPrivacy = \ze\row::get('translation_chains', 'privacy', ['equiv_id' => \ze\content::equivId($cID, $cType), 'type' => $cType]);
	$publishingAPublicPage = $citemPrivacy == 'public';
} else {
	$publishingAPublicPage = false;
}



//Add images linked via Version Controlled modules
$fileIds = [];
$sql = "
	SELECT ps.value
	FROM ". DB_PREFIX. "plugin_instances AS pi
	INNER JOIN ". DB_PREFIX. "plugin_settings AS ps
	   ON pi.id = ps.instance_id
	  AND ps.is_content = 'version_controlled_setting'
	  AND ps.foreign_key_to IN('file', 'multiple_files')
	WHERE pi.content_id = ". (int) $cID. "
	  AND pi.content_type = '". \ze\escape::asciiInSQL($cType). "'
	  AND pi.content_version = ". (int) $cVersion;
$result = \ze\sql::select($sql);

while ($fileIdsInPlugin = \ze\sql::fetchRow($result)) {
	foreach (\ze\ray::explodeAndTrim($fileIdsInPlugin[0], true) as $fileId) {
		$fileIds[$fileId] = $fileId;
	}
}

//Note down the feature image for this Content Item, if there is one
$featureImageId = 0;
if ($fileId = \ze\row::get('content_item_versions', 'feature_image_id', ['id' => $cID, 'type' => $cType, 'version' => $cVersion])) {
	$fileIds[$fileId] = $featureImageId = $fileId;
}

//Do a quick check to see if all of those ids exist, only add the ones in the database!
if (!empty($fileIds)) {
	$files = \ze\row::getAssocs('files', ['id', 'usage', 'filename', 'mime_type', 'privacy', 'width', 'height', 'checksum', 'short_checksum'], ['id' => $fileIds]);
}




//Get each content area (which will have been converted into HTML snippets)
$sql = "
	SELECT ps.instance_id, ps.name, ps.egg_id, ps.value
	FROM ". DB_PREFIX. "plugin_instances AS pi
	INNER JOIN ". DB_PREFIX. "plugin_settings AS ps
	   ON pi.id = ps.instance_id
	WHERE pi.content_id = ". (int) $cID. "
	  AND pi.content_type = '". \ze\escape::asciiInSQL($cType). "'
	  AND pi.content_version = ". (int) $cVersion. "
	  AND ps.is_content = 'version_controlled_content'";
$result = \ze\sql::select($sql);

while ($row = \ze\sql::fetchAssoc($result)) {
	$htmlChanged = false;
	\ze\contentAdm::syncInlineFileLinks($files, $row['value'], $htmlChanged, 'image', $publishingAPublicPage);
	
	//Keep a block of all of the content to put into the cache table
	$content .= $row['value']. "\n";
	
	if ($htmlChanged) {
		\ze\row::update(
			'plugin_settings',
			['value' => $row['value']],
			['instance_id' => $row['instance_id'], 'name' => $row['name'], 'egg_id' => $row['egg_id']]);
	}
}


//Update the link table
\ze\contentAdm::syncInlineFiles(
	$files,
	[
		'foreign_key_to' => 'content',
		'foreign_key_id' => $cID,
		'foreign_key_char' => $cType,
		'foreign_key_version' => $cVersion],
	$keepOldImagesThatAreNotInUse = true);

//If there is no feature image, and the "Flag the first-uploaded image as the featured image" setting
//is enabled for this content type, and the first image has just been uploaded, try to flag it now.
if (!$featureImageId) {
	if (ze\row::get('content_types', 'auto_flag_feature_image', ['content_type_id' => $cType]) && count($files) > 0) {
		$inlineImagesCount = ze\row::count('inline_images', ['foreign_key_to' => 'content', 'foreign_key_id' => $cID, 'foreign_key_char' => $cType, 'foreign_key_version' => $cVersion]);

		if ($inlineImagesCount == 1) {
			$featureImageId = array_key_first($files);
		}

		//If unflagging a feature image, don't flag anything else as feature image.
		if ($featureImageId && empty($_REQUEST['unflag_as_feature'])) {
			ze\contentAdm::updateVersion($cID, $cType, $cVersion, ['feature_image_id' => $featureImageId]);
		}
	}
}

//We'll want to go through the plain text extract, trying to balance the text on each line so that
//it would work well in a semantic search.
$chunks = ze\ring::parseExtract($content, true);
$text = implode("\n\n", $chunks);

//Update the Content in the content_items_searchable_cache table
if (\ze\contentAdm::contentItemIsSearchable($cID, $cType, $cVersion)) {
	
	$versionDetails = \ze\row::get('content_item_versions', ['file_id', 'filename', 'title', 'description', 'keywords', 'content_summary'], ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
	if (!$versionDetails['content_summary']) {
		$versionDetails['content_summary'] = '';
	}
	
	\ze\row::set(
		'content_items_searchable_cache',
		[
			'content_tag' => $cType . '_' . $cID,
			'content_version' => $cVersion,
			'alias' => \ze\row::get('content_items', 'alias', ['id' => $cID, 'type' => $cType]),
			'title' => $versionDetails['title'],
			'description' => $versionDetails['description'],
			'keywords' => $versionDetails['keywords'],
			'content_summary' => implode("\n\n", \ze\ring::parseExtract($versionDetails['content_summary'], $isHTML = true)),
			'filename' => $versionDetails['filename'],
			'content_item_text' => $text,
			'content_item_text_wordcount' => str_word_count($text)
		],
		[
			'content_id' => $cID,
			'content_type' => $cType
		]
	);

//Drafts/trashed content items/hidden content items/unlisted content items/non-searchable special pages should not be in the 
//content_items_searchable_cache table. Clear them up if they are there.
} else {
	\ze\row::delete('content_items_searchable_cache', ['content_id' => $cID, 'content_type' => $cType, 'content_version' => $cType]);
}


//Fix for T10031, Images in WYSIWYG Editors staying on "will auto detect" in the image library
//Look through any of the images used on this content item that are still set to auto-detect,
//and set them to either public or private (depending on this item's privacy setting) when
//the content item is published.
if ($publishing && !empty($files)) {
	
	if ($citemPrivacy == 'public') {
		$privacy = 'public';
	} else {
		$privacy = 'private';
	}
	
	foreach ($files as $fileId => $file) {
		if ($file['usage'] == 'image'
		 && $file['privacy'] == 'auto') {
			\ze\row::update('files', ['privacy' => $privacy], $fileId);
			
			if ($citemPrivacy == 'public') {
				\ze\image::addToPublicDir($fileId);
			}
		}
	}
}