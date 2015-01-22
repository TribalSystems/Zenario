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

//Get the checksum and intended usage of this file from the request
$usage = isset($_GET['usage'])? $_GET['usage'] : 'inline';
$checksum = isset($_GET['checksum'])? $_GET['checksum'] : (isset($_GET['c'])? $_GET['c'] : false);
$requestedWidth = isset($_GET['width'])? (int) $_GET['width'] : '';
$requestedHeight = isset($_GET['height'])? (int) $_GET['height'] : '';
$key = isset($_GET['k'])? $_GET['k'] : '';
$adminBackend = false;

require 'cacheheader.inc.php';

//If a checksum was given, we can cache this file
if ($checksum) {
	$ETag =
		'zenario-file-'. $_SERVER['HTTP_HOST']. '-'. $usage. '-'. $checksum.
		(isset($_GET['sk'])? '-sk' : '').
		(isset($_GET['skl'])? '-skl' : '').
		(isset($_GET['closeup'])? '-closeup' : '').
		(isset($_GET['popout'])? '-popout' : '').
		'-'. $requestedWidth. '-'. $requestedHeight. '-'. $key;
	useCache($ETag);
}
useGZIP();


//Storekeeper views should have an admin header; 
if (isset($_GET['sk'])
 || isset($_GET['skl'])
 || isset($_GET['closeup'])
 || isset($_GET['popout'])
 || isset($_GET['adminDownload'])
 || isset($_GET['getUploadedFileInCacheDir'])) {
	require CMS_ROOT. 'zenario/adminheader.inc.php';
	$adminBackend = true;

//All other usage should use the visitor header
} else {
	//For files associated with a Content Item, we'll need to check permissions and so we'll need access to the session
	if ($usage == 'content') {
		session_start();
	}
	
	require CMS_ROOT. 'zenario/visitorheader.inc.php';
}



//Look for files either by checksum/usage or file id.
//Looking by id allows a visitor to see any file, so only allow it for certain types or under certain restrictions.
//Usually if a visitor has the correct checksum then that's enough to view any file, except for files associated with content.
//However a visitor will need the a key in the get request or session entry if they want to view a resized copy of an image.
$id = false;
$mode = 'resize';
$width = false;
$height = false;
$offset = 0;
$file = false;
$filePath = false;
$filename = request('filename');
$useCacheDir = true;
$getUploadedFileInCacheDir = request('getUploadedFileInCacheDir') && checkPriv();

//Attempt to get the id from the request
	//(This is only allowed under certain situations, as images may be protected or not public.)
if ($usage == 'user' && request('user_id')) {
	$id = getRow('users', 'image_id', array('id' => request('user_id')));

} elseif ($usage == 'group' && request('group_id')) {
	$id = getRow('groups', 'image_id', array('id' => request('group_id')));

} elseif ($usage == 'template' && request('layout_id')) {
	$id = getRow('layouts', 'image_id', request('layout_id'));

} elseif ($adminBackend && empty($_GET['usage']) && !empty($_GET['id'])) {
	$id = $_GET['id'];
}


//Generate or load a thumbnail for Storekeeper
if (isset($_GET['sk'])) {
	$width = 180;
	$height = 130;

//Generate or load a small thumbnail for Storekeeper
} elseif (isset($_GET['skl'])) {
	$width = 24;
	$height = 23;

//Generate a close-up view for Storekeeper
} elseif (isset($_GET['closeup']) && checkPriv()) {
	$width = 400;
	$height = 400;

//Generate a pop-out view for Storekeeper
} elseif (isset($_GET['popout']) && checkPriv()) {
	$width = 900;
	$height = 900;

//Handle resizes, using an entry in a visitor's session to prevent visitors hacking the URL and asking for whatever size image they want.
} elseif ($checksum && $usage == 'resize') {
	//If this is a resized image, check if this resize is allowed
	//Note that using the session for each image is quite slow, so it's better to make sure that your cache/ directory is writable
	//and not use this fallback logic!
	session_start();
	if (!isset($_SESSION['zenario_allowed_files'][$checksum])) {
		header('HTTP/1.0 404 Not Found');
		exit;
	} else {
		$id = $_SESSION['zenario_allowed_files'][$checksum]['id'];
		$width = $_SESSION['zenario_allowed_files'][$checksum]['width'];
		$height = $_SESSION['zenario_allowed_files'][$checksum]['height'];
		$mode = $_SESSION['zenario_allowed_files'][$checksum]['mode'];
		$offset = $_SESSION['zenario_allowed_files'][$checksum]['offset'];
		$useCacheDir = !empty($_SESSION['zenario_allowed_files'][$checksum]['useCacheDir']);
	}

//Handle resizes from WYSIWYG Editors, using a hash in the get request to make it harder for visitors to hack the URL and ask for whatever size image they want.
} elseif ($requestedWidth && $requestedHeight && $key) {
	$width = $requestedWidth;
	$height = $requestedHeight;
	$mode = 'stretch';

} elseif ($requestedWidth && $requestedHeight && $getUploadedFileInCacheDir) {
	$width = $requestedWidth;
	$height = $requestedHeight;
	$mode = 'resize';
}


//Attempt to output an image in the cache/uploads/ directory
if ($getUploadedFileInCacheDir) {
	
	$imageMimeTypes = array('image/gif' => true, 'image/jpeg' => true, 'image/jpg' => true, 'image/pjpeg' => true, 'image/png' => true);
	
	$file = array();
	
	if (($filepath = getPathOfUploadedFileInCacheDir(request('getUploadedFileInCacheDir')))
	 && ($file['mime_type'] = documentMimeType($filepath))) {
		
		$file['data'] = file_get_contents($filepath);
		$file['filename'] = $filename = basename($filepath);
		
		if (!empty($imageMimeTypes[$file['mime_type']])
		 && ($image = getimagesize($filepath))) {
			$file['width'] = $image[0];
			$file['height'] = $image[1];
			$file['mime_type'] = $image['mime'];
		
			if ($width && $height) {
				resizeImageString(
					$file['data'], $file['mime_type'],
					$file['width'], $file['height'],
					$width, $height,
					$mode);
			}
		}
	}

} else {

	//If this is a file for a content item, check that the visitor can see the current content item
	//Again, this is a little slower than it colud be, as you need to exchange session information. It's only intended
	//as a fallback if the cache/ directory isn't writable.
	if ($usage == 'content') {
		$hasPerms = false;

		$sql = "
			SELECT v.id, v.type, v.version, v.file_id, v.filename
			FROM ". DB_NAME_PREFIX. "files AS f
			INNER JOIN ". DB_NAME_PREFIX. "versions AS v
			   ON v.file_id = f.id";

		if (request('cID') && request('cType')) {
			$sql .= "
			WHERE f.`usage` = 'content'
			  AND v.id = ". (int) request('cID'). "
			  AND v.type = '". sqlEscape(request('cType')). "'";
	
			if (checkPriv() && request('cVersion')) {
				$sql .= "
				  AND v.version = ". (int) request('cVersion');
	
			} elseif (checkPriv()) {
				$sql .= "
				  AND v.version = ". (int) getLatestVersion(request('cID'), request('cType'));
	
			} else {
				$sql .= "
				  AND v.version = ". (int) getPublishedVersion(request('cID'), request('cType'));
			}

		} elseif ($checksum) {
			$sql .= "
			INNER JOIN ". DB_NAME_PREFIX. "content AS c
			   ON v.id = c.id
			  AND v.type = c.type
			  AND v.version = ". (checkPriv()? "c.admin_version" : "c.visitor_version"). "
			WHERE f.checksum = '". sqlEscape($checksum). "'
			  AND f.`usage` = 'content'";

		} else {
			header('HTTP/1.0 404 Not Found');
			exit;
		}

		if ($result = sqlQuery($sql)) {
			while ($row = sqlFetchAssoc($result)) {
				if (checkPerm($row['id'], $row['type'], $row['version'])) {
					$hasPerms = true;
					$id = $row['file_id'];
			
					if (!$filename) {
						$filename = $row['filename'];
					}
					break;
				}
			}
		}

		if (!$hasPerms) {
			header('HTTP/1.0 404 Not Found');
			exit;
		}
	
	//Requests for favicons
	} elseif ($usage == 'favicon') {
	
	//Requests for home screen icons
	} elseif ($usage == 'mobile_icon') {

	//If this wasn't a request for a Content Item file/Favicon/Home screen icon,
	//and if no id or checksum was requested, exit
	} elseif (!$checksum && !$id) {
		header('HTTP/1.0 404 Not Found');
		exit;
	}



	//Get the details of the file from the database
	$sql = "
		SELECT
			id,
			filename,
			";

	//If this is a Storekeeper thumbnail then we'll grab the image data up straight away.
	if (isset($_GET['sk'])) {
		$sql .= "storekeeper_data AS data";

	} elseif (isset($_GET['skl'])) {
		$sql .= "storekeeper_list_data AS data";

	//If this is content, then we'll also grab the data straight away as there should be no need to manipulate it.
	} elseif ($usage == 'content') {
		$sql .= "data";

	//Otherwise we won't load it now, and we'll use the imageLink() function to get it below.
	} else {
		$sql .= "NULL AS data";
	}

	$sql .= ",
			location,
			path,
			mime_type,
			width,
			height,
			storekeeper_list_data, storekeeper_list_width, storekeeper_list_height,
			storekeeper_data, storekeeper_width, storekeeper_height,
			working_copy_data, working_copy_width, working_copy_height,
			working_copy_2_data, working_copy_2_width, working_copy_2_height,
			size
		FROM ". DB_NAME_PREFIX . "files";

	if ($id) {
		$sql .= "
		WHERE id = ". (int) $id;

	} else {
		$sql .= "
		WHERE `usage` = '". sqlEscape($usage). "'";

		if ($checksum) {
			$sql .= "
		  AND checksum = '". sqlEscape($checksum). "'";
		}
	}

	$sql .= "
		LIMIT 1";


	if (($result = sqlQuery($sql)) && ($file = sqlFetchAssoc($result))) {

		//If the file is supposed to be in the docstore, check if it is actually there
		if ($file['location'] == 'docstore' && empty($file['data'])) {
			if (!$filePath = docstoreFilePath($file['path'])) {
				echo adminPhrase('File missing!');
				exit;
			
			//Check to see if this is an image
			} elseif (substr($file['mime_type'], 0, 6) == 'image/') {
			
			//Check to see if this is a pdf downloading from Organizer
			} else
			if ($file['mime_type'] == 'application/pdf'
			 && !empty($_SERVER['HTTP_REFERER'])
			 && strpos($_SERVER['HTTP_REFERER'], '/zenario/admin/') !== false) {
			
			//If this is not an image, and is not a PDF that is being downloaded from Organizer,
			//attempt to symlink the file to the private directory rather than load it all into memory in php
			} else
			if (($fileLink = fileLink($file['id'], randomString(24)))
			 && (!chopPrefixOffOfString('zenario/file.php', $fileLink))) {
				header('location: '. absCMSDirURL(). $fileLink);
				exit;
			}
		}

		//When Handling resizes from WYSIWYG Editors, use a hash in the get request to make it harder for visitors to hack the URL and ask for whatever size image they want.
		if ($requestedWidth && $requestedHeight && $key) {
			if ($key != hash64($file['id']. '_'. $requestedWidth. '_'. $requestedHeight. '_'. $checksum, 10)) {
				$width = $file['width'];
				$height = $file['height'];
				$mode = 'resize';
			}
		}

		//If this is an image, check to see if it is in the cache directory.
		//If it's not yet there, resize it if needed and then attempt to put it in there
		if (empty($file['data'])) {
			if (substr($file['mime_type'], 0, 6) == 'image/') {
				$result =
					imageLink(
						$width, $height, $filePath, $file['id'], $width, $height, $mode, $offset,
						$useCacheDir, $internalFilePath = true, $returnImageStringIfCacheDirNotWorking = true);
		
				//The image link function will return false if a file is not an image, or if it was not found...
				if ($result === false) {
		
				//...true if an image was found and it could get a path
				} elseif ($result === true) {
					$file['data'] = null;
		
				//...otherwise it will return the image data, if it could get the image but couldn't write to the cache directory
				} else {
					$file['data'] = $result;
				}
	
			} else {
				$file['data'] = getRow('files', 'data', $file['id']);
			}
	
			unset($result);
		}
	}
}



//If we didn't find the file, show a 404
if (!$file
 || (empty($file['data']) && (!$filePath || !is_file($filePath)))) {
	
	header('HTTP/1.0 404 Not Found');
	exit;
}



//Output the file
header('Content-type: '. ifNull($file['mime_type'], 'application/octet-stream'));

if (!$filename && !empty($file['filename'])) {
	$filename = $file['filename'];
}
if ($filename) {
	if (request('download') || $usage == 'content') {
		header('Content-Disposition: attachment; filename="'. urlencode($filename). '"');
	} else {
		header('Content-Disposition: filename="'. urlencode($filename). '"');
	}
}

if ($filePath) {
	readfile($filePath);
} else {
	echo $file['data'];
}