<?php
/*
 * Copyright (c) 2021, Tribal Limited
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


set_time_limit(60 * 10);

if ($jpegOnly) {
	$mimeType = "('image/jpeg')";
} else {
	$mimeType = "('image/gif', 'image/png', 'image/jpeg')";
}


if ($recreateCustomThumbnailOnes) {
	if ($removeOldCopies) {
		$sql = "
			UPDATE ". DB_PREFIX. "files SET
				custom_thumbnail_1_width = NULL,
				custom_thumbnail_1_height = NULL,
				custom_thumbnail_1_data = NULL
			WHERE mime_type IN ". $mimeType . "
			  AND custom_thumbnail_1_width IS NOT NULL";
		\ze\sql::update($sql);
	}
	
	if (($custom_thumbnail_1_width = (int) \ze::setting('custom_thumbnail_1_width'))
	 && ($custom_thumbnail_1_height = (int) \ze::setting('custom_thumbnail_1_height'))){
		$sql = "
			SELECT id
			FROM ". DB_PREFIX. "files
			WHERE mime_type IN ". $mimeType. "
			  AND (width > ". (int) $custom_thumbnail_1_width. " OR height > ". (int) $custom_thumbnail_1_height. ")
			  AND custom_thumbnail_1_width IS NULL";
		$result = \ze\sql::select($sql);
		
		while ($id = \ze\sql::fetchValue($result)) {
			$imageSql = "
				SELECT id, location, path, filename, data AS custom_thumbnail_1_data, mime_type, width AS custom_thumbnail_1_width, height AS custom_thumbnail_1_height
				FROM ". DB_PREFIX. "files
				WHERE id = " . (int)$id;
			
			$imageResult = \ze\sql::select($imageSql);
			$img = \ze\sql::fetchAssoc($imageResult);
			
			if ($img['location'] == 'docstore') {
				if ($path = ze\file::docstorePath($img['path'])) {
					$img['custom_thumbnail_1_data'] = file_get_contents($path);
				} else {
					continue;
				}
			}
			
			$imageId = $img['id'];
			unset($img['id']);
			unset($img['location']);
			unset($img['path']);
			unset($img['filename']);
			
			\ze\file::resizeImageString($img['custom_thumbnail_1_data'], $img['mime_type'], $img['custom_thumbnail_1_width'], $img['custom_thumbnail_1_height'], $custom_thumbnail_1_width, $custom_thumbnail_1_height);
			
			\ze\row::update('files', $img, $imageId);
		}
	}
}


if ($recreateCustomThumbnailTwos) {
	if ($removeOldCopies) {
		$sql = "
			UPDATE ". DB_PREFIX. "files SET
				custom_thumbnail_2_width = NULL,
				custom_thumbnail_2_height = NULL,
				custom_thumbnail_2_data = NULL
			WHERE mime_type IN ". $mimeType . "
			  AND custom_thumbnail_2_width IS NOT NULL";
		\ze\sql::update($sql);
	}
	
	if (($custom_thumbnail_2_width = (int) \ze::setting('custom_thumbnail_2_width'))
	 && ($custom_thumbnail_2_height = (int) \ze::setting('custom_thumbnail_2_height'))) {
		$sql = "
			SELECT id
			FROM ". DB_PREFIX. "files
			WHERE mime_type IN ". $mimeType. "
			  AND (width > ". (int) $custom_thumbnail_2_width. " OR height > ". (int) $custom_thumbnail_2_height. ")
			  AND custom_thumbnail_2_width IS NULL";
		$result = \ze\sql::select($sql);
		
		while ($id = \ze\sql::fetchValue($result)) {
			$imageSql = "
				SELECT id, location, path, filename, data AS custom_thumbnail_2_data, mime_type, width AS custom_thumbnail_2_width, height AS custom_thumbnail_2_height
				FROM ". DB_PREFIX. "files
				WHERE id = " . (int)$id;
			
			$imageResult = \ze\sql::select($imageSql);
			$img = \ze\sql::fetchAssoc($imageResult);
			
			if ($img['location'] == 'docstore') {
				if ($path = ze\file::docstorePath($img['path'])) {
					$img['custom_thumbnail_2_data'] = file_get_contents($path);
				} else {
					continue;
				}
			}
			
			$imageId = $img['id'];
			unset($img['id']);
			unset($img['location']);
			unset($img['path']);
			unset($img['filename']);
			
			\ze\file::resizeImageString($img['custom_thumbnail_2_data'], $img['mime_type'], $img['custom_thumbnail_2_width'], $img['custom_thumbnail_2_height'], $custom_thumbnail_2_width, $custom_thumbnail_2_height);
			
			\ze\row::update('files', $img, $imageId);
		}
	}
}
