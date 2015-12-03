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


set_time_limit(60 * 10);

if ($jpegOnly) {
	$mimeType = "('image/jpeg')";
} else {
	$mimeType = "('image/gif', 'image/png', 'image/jpeg')";
}


if ($workingCopyImages) {
	if ($removeOldWorkingCopies) {
		$sql = "
			UPDATE ". DB_NAME_PREFIX. "files SET
				working_copy_width = 0,
				working_copy_height = 0,
				working_copy_data = NULL
			WHERE mime_type IN ". $mimeType;
		sqlQuery($sql);
	}
	
	if ($working_copy_image_size = (int) setting('working_copy_image_size')) {
		$sql = "
			SELECT id, location, path, filename, data AS working_copy_data, mime_type, width AS working_copy_width, height AS working_copy_height
			FROM ". DB_NAME_PREFIX. "files
			WHERE mime_type IN ". $mimeType. "
			  AND (width > ". (int) $working_copy_image_size. " OR height > ". (int) $working_copy_image_size. ")
			  AND working_copy_data IS NULL";
		$result = sqlQuery($sql);
		
		while($img = sqlFetchAssoc($result)) {
			if ($img['location'] == 'docstore') {
				if ($path = docstoreFilePath($img['path'])) {
					$img['working_copy_data'] = file_get_contents($path);
				} else {
					continue;
				}
			}
			
			$imageId = $img['id'];
			unset($img['id']);
			unset($img['location']);
			unset($img['path']);
			unset($img['filename']);
			
			resizeImageString($img['working_copy_data'], $img['mime_type'], $img['working_copy_width'], $img['working_copy_height'], $working_copy_image_size, $working_copy_image_size);
			
			updateRow('files', $img, $imageId);
		}
	}
}


if ($thumbnailWorkingCopyImages) {
	if ($removeOldWorkingCopies) {
		$sql = "
			UPDATE ". DB_NAME_PREFIX. "files SET
				working_copy_2_width = 0,
				working_copy_2_height = 0,
				working_copy_2_data = NULL
			WHERE mime_type IN ". $mimeType;
		sqlQuery($sql);
	}
	
	if ($thumbnail_wc_image_size = (int) setting('thumbnail_wc_image_size')) {
		$sql = "
			SELECT id, location, path, filename, data AS working_copy_2_data, mime_type, width AS working_copy_2_width, height AS working_copy_2_height
			FROM ". DB_NAME_PREFIX. "files
			WHERE mime_type IN ". $mimeType. "
			  AND (width > ". (int) $thumbnail_wc_image_size. " OR height > ". (int) $thumbnail_wc_image_size. ")
			  AND working_copy_2_data IS NULL";
		$result = sqlQuery($sql);
		
		while($img = sqlFetchAssoc($result)) {
			if ($img['location'] == 'docstore') {
				if ($path = docstoreFilePath($img['path'])) {
					$img['working_copy_2_data'] = file_get_contents($path);
				} else {
					continue;
				}
			}
			
			$imageId = $img['id'];
			unset($img['id']);
			unset($img['location']);
			unset($img['path']);
			unset($img['filename']);
			
			resizeImageString($img['working_copy_2_data'], $img['mime_type'], $img['working_copy_2_width'], $img['working_copy_2_height'], $thumbnail_wc_image_size, $thumbnail_wc_image_size);
			
			updateRow('files', $img, $imageId);
		}
	}
}
