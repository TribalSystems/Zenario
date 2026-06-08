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

require 'basicheader.inc.php';


//This script handles the situation where someone is using a TUIX form and uploads a file.
//The file will be stored in the private/uploads directory, but we need to provide 
//a script to show them a thumbnail of the file they just uploaded.

//This could be done by just sending them the full-sized image and letting the browser scale it,
//or we could resize it to a specific size that we need (e.g. 180x130 for Organizer/the admin backend).
if (isset($_GET['og'])) {
	$width = 180;
	$height = 130;
} else {
	$width = false;
	$height = false;
}


if (($uploadCode = $_GET['uploadCode'] ?? false)
 && ($filePath = ze\file::getPathOfUploadInCacheDir($uploadCode))
 && ($mimeType = ze\file::mimeType($filePath))) {
	
	
	$filename = basename($filePath);


	if ($width
	 && $height
	 && (ze\file::isImage($mimeType))
	 && ($image = getimagesize($filePath))) {
		
		$data = file_get_contents($filePath);
		$filePath = false;
		
		$imageWidth = $image[0];
		$imageHeight = $image[1];
		$mimeType = $image['mime'];
		
		ze\image::resize(
			$data, $mimeType,
			$imageWidth, $imageHeight,
			$width, $height,
			'resize'
		);
	}
	
	
	//Output the file
	header('Content-type: '. ($mimeType ?: 'application/octet-stream'));
	
	if (isset($_GET['cacheFor'])) {
		header('Pragma: cache');
		header('Cache-Control: max-age=' . (int)$_GET['cacheFor']);
	}
	
	if ($filename) {
		if (ze::request('download')) {
			header('Content-Disposition: attachment; filename="'. urlencode($filename). '"');
		} else {
			header('Content-Disposition: filename="'. urlencode($filename). '"');
		}
	}
	
	ze\cache::end();
	if ($filePath) {
		readfile($filePath);
	} else {
		echo $data;
	}
	exit;




} else {
	header('HTTP/1.0 404 Not Found');
	exit;
}
