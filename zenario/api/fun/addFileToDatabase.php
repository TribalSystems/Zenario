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


$file = array();
$imageMimeTypes = array('image/gif' => true, 'image/jpeg' => true, 'image/jpg' => true, 'image/pjpeg' => true, 'image/png' => true);

if (!is_readable($location) || !is_file($location) || !($file['size'] = filesize($location)) || !($file['checksum'] = md5_file($location))) {
	return false;
}

if ($filename === false) {
	$filename = preg_replace('/([^.a-z0-9]+)/i', '_', basename($location));
}

$file['filename'] = $filename;
$file['mime_type'] = documentMimeType($filename);
$file['usage'] = $usage;

if ($mustBeAnImage && empty($imageMimeTypes[$file['mime_type']])) {
	return false;
}

//Check if this file exists in the system already
$key = array('checksum' => $file['checksum'], 'usage' => $file['usage']);
if ($existingFile = getRow('files', array('id', 'filename', 'location', 'path'), $key)) {
	$key = $existingFile['id'];
	
	//If this file is stored in the database, continue running this function to move it to the docstore dir
	if (!($addToDocstoreDirIfPossible && $existingFile['location'] == 'db')) {
		
		//If this file is already stored, just update the name
		$path = false;
		if ($existingFile['location'] == 'db' || ($path = docstoreFilePath($existingFile['path']))) {
			//If the name has changed, attempt to rename the file in the filesystem
			if ($path && $file['filename'] != $existingFile['filename']) {
				@rename($path, setting('docstore_dir'). '/'. $existingFile['path']. '/'. $file['filename']);
			}
			
			setRow('files', array('filename' => $filename), $key);
			
			if ($deleteWhenDone) {
				unlink($location);
			}
			
			return $existingFile['id'];
		}
	}
}

//Otherwise we must insert the new file


//Check if the file is an image
if ($file['mime_type'] == 'image/gif' || $file['mime_type'] == 'image/png' || $file['mime_type'] == 'image/jpeg') {
	
	$image = getimagesize($location);
	if (!empty($imageMimeTypes[$image['mime']])) {
		
		$file['width'] = $image[0];
		$file['height'] = $image[1];
		$file['mime_type'] = $image['mime'];
		
		//Create resizes for the image as needed.
		//Working copies should only be created if they are enabled, and the image is big enough to need them.
		//Storekeeper thumbnails should always be created, even if the image needs to be scaled up
		foreach (array(
			array('working_copy_data', 'working_copy_width', 'working_copy_height', setting('working_copy_image_size'), setting('working_copy_image_size'), false),
			array('working_copy_2_data', 'working_copy_2_width', 'working_copy_2_height', setting('thumbnail_wc_image_size'), setting('thumbnail_wc_image_size'), false),
			array('storekeeper_data', 'storekeeper_width', 'storekeeper_height', 180, 130, true),
			array('storekeeper_list_data', 'storekeeper_list_width', 'storekeeper_list_height', 24, 23, true)
		) as $c) {
			if ($c[3] && $c[4] && ($c[5] || ($file['width'] > $c[3] || $file['height'] > $c[4]))) {
				$file[$c[1]] = $image[0];
				$file[$c[2]] = $image[1];
				$file[$c[0]] = file_get_contents($location);
				resizeImageString($file[$c[0]], $file['mime_type'], $file[$c[1]], $file[$c[2]], $c[3], $c[4]);
			}
		}
	}
	
	$file['alt_tag'] = $file['filename'];
}

if ($mustBeAnImage && !(!empty($file['width']) && !empty($file['height']))) {
	return false;
}


$file['created_datetime'] = now();

if ($addToDocstoreDirIfPossible
 && is_dir($dir = setting('docstore_dir'). '/')
 && is_writable($dir)
 && ((is_dir($dir = $dir. ($path = preg_replace('/\W/', '_', $filename). '_'. base_convert($file['checksum'], 16, 36)). '/'))
  || (mkdir($dir) && chmod($dir, 0777)))) {
	
	if (file_exists($dir. $file['filename'])) {
		unlink($dir. $file['filename']);
	}
	
	if ($deleteWhenDone) {
		rename($location, $dir. $file['filename']);
	} else {
		copy($location, $dir. $file['filename']);
	}
	
	$file['location'] = 'docstore';
	$file['path'] = $path;
	$file['data'] = null;

} else {
	$file['location'] = 'db';
	$file['path'] = '';
	$file['data'] = file_get_contents($location);

	if ($deleteWhenDone) {
		unlink($location);
	}
}

return setRow('files', $file, $key);