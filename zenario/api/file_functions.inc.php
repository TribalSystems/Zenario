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

function trackFileDownload($url) {
	if (!empty($_SESSION['admin_userid'])) {
		return '';
	} else {
		return "if (window.ga) ga('send', 'pageview', {'page' : '".jsEscape($url)."'});";
	}
}

function addFileToDocstoreDir($usage, $location, $filename = false, $mustBeAnImage = false, $deleteWhenDone = true) {
	return addFileToDatabase($usage, $location, $filename, $mustBeAnImage, $deleteWhenDone, true);
}

function addFileFromString($usage, &$contents, $filename, $mustBeAnImage = false, $addToDocstoreDirIfPossible = false) {
	
	if ($temp_file = tempnam(sys_get_temp_dir(), 'cpy')) {
		if (file_put_contents($temp_file, $contents)) {
			return addFileToDatabase($usage, $temp_file, $filename, $mustBeAnImage, true, $addToDocstoreDirIfPossible);
		}
	}
	
	return false;
}

function addFileToDatabase($usage, $location, $filename = false, $mustBeAnImage = false, $deleteWhenDone = false, $addToDocstoreDirIfPossible = false, $imageAltTag = false, $imageTitle = false, $imagePopoutTitle = false) {
	return require funIncPath(__FILE__, __FUNCTION__);
}

function deleteFile($fileId) {
	$path = getRow("files", 'path', $fileId);
	$pathForDelete = docstoreFilePath($fileId);
	$result = deleteRow("files",$fileId);
	if ($result) {
		if ($path) {
			if(checkRowExists("files", array('path' => $path) )) {
				//file still being used
				return false;
			} else {
				if(is_dir($dir = setting('docstore_dir'). '/')
					 && is_writable($dir)) {
					unlink($pathForDelete);
					rmdir($dir . $path);
					return true;
				} else {
					return false;
				}
			}
		} else {
			//file in database not Docstore
			return true;
		}
	} else {
		//file not found
		return false;
	}
	
}

function addImageDataURIsToDatabase(&$content, $prefix = '', $usage = 'image') {
	
	//Add some logic to handle any old links to email/inline/menu images (these are now just classed as "image"s).
	if ($usage == 'email'
	 || $usage == 'inline'
	 || $usage == 'menu') {
		$usage = 'image';
	}
	
	foreach (preg_split('@(["\'])data:image/(\w*);base64,([^"\']*)(["\'])@s', $content, -1,  PREG_SPLIT_DELIM_CAPTURE) as $i => $data) {
		
		if ($i == 0) {
			$content = '';
		}
		
		switch ($i % 5) {
			case 2:
				$ext = $data;
				break;
			
			case 3:
				$sql = "SELECT IFNULL(MAX(id), 0) + 1 FROM ". DB_NAME_PREFIX. "files";
				$result = sqlQuery($sql);
				$row = sqlFetchRow($result);
				$filename = 'image_'. $row[0]. '.'. $ext;
				
				$data = base64_decode($data);
				
				if ($fileId = addFileFromString($usage, $data, $filename, $mustBeAnImage = true)) {
					if ($checksum = getRow('files', 'checksum', $fileId)) {
						$content .= htmlspecialchars($prefix. 'zenario/file.php?c='. $checksum);
						
						if ($usage != 'image') {
							$content .= htmlspecialchars('&usage='. rawurlencode($usage));
						}
						
						$content .= htmlspecialchars('&filename='. rawurlencode($filename));
					}
				}
				break;
				
			default:
				$content .= $data;
				break;
		}
	}
}

function checkDocumentTypeIsAllowed($file) {
	$type = explode('.', $file);
	$type = $type[count($type) - 1];
	
	return checkRowExists('document_types', array('type' => $type));
}

function contentFileLink(&$url, $cID, $cType, $cVersion) {
	$url = false;
	
	//Check that this file exists
	if (!($version = getRow('versions', array('filename', 'file_id'), array('id' => $cID, 'type' => $cType, 'version' => $cVersion)))
	 || !($file = getRow('files', array('mime_type', 'checksum', 'filename', 'location', 'path'), $version['file_id']))) {
		return $url = false;
	}
	
	
	if (adminId()) {
		$onlyForCurrentVisitor = setting('restrict_downloads_by_ip');
		$hash = hash64('admin_'. session('admin_userid'). '_'. visitorIP(). '_'. $file['checksum']);
	
	} elseif (session('extranetUserID')) {
		$onlyForCurrentVisitor = setting('restrict_downloads_by_ip');
		$hash = hash64('user_'. session('extranetUserID'). '_'. visitorIP(). '_'. $file['checksum']);
	
	} else {
		$onlyForCurrentVisitor = false;
		$hash = $file['checksum'];
	}
	
	//Check to see if the file is missing
	$path = false;
	if ($file['location'] == 'docstore' && !($path = docstoreFilePath($file['path']))) {
		return false;
	
	//Attempt to add/symlink the file in the cache directory
	} elseif ($path && (cleanDownloads()) && ($dir = createCacheDir($hash, 'downloads', $onlyForCurrentVisitor))) {
		$url = $dir. ifNull($version['filename'], $file['filename']);
		
		if (!file_exists(CMS_ROOT. $url)) {
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				copy($path, CMS_ROOT. $url);
			} else {
				symlink($path, CMS_ROOT. $url);
			}
		}
	
	//Otherwise, we'll need to link to file.php for the file
	} else {
		$url = 'zenario/file.php?usage=content&c='. $file['checksum'];
		
		if ($cID && $cType) {
			$url .= '&cID='. $cID. '&cType='. $cType;
			
			if (checkPriv() && $cVersion) {
				$url .='&cVersion='. $cVersion;
			}
		}
	}
	
	return true;
}

function copyFileInDatabase($usage, $existingFileId, $filename = false, $mustBeAnImage = false, $addToDocstoreDirIfPossible = false) {
	
	//Add some logic to handle any old links to email/inline/menu images (these are now just classed as "image"s).
	if ($usage == 'email'
	 || $usage == 'inline'
	 || $usage == 'menu') {
		$usage = 'image';
	}
	
	if ($file = getRow('files', array('usage', 'filename', 'location', 'data', 'path'), array('id' => $existingFileId))) {
		if ($file['usage'] == $usage) {
			return $existingFileId;
		
		} elseif ($file['location'] == 'db') {
			return addFileFromString($usage, $file['data'], ifNull($filename, $file['filename']), $mustBeAnImage, $addToDocstoreDirIfPossible);
		
		} elseif ($file['location'] == 'docstore' && ($location = docstoreFilePath($file['path']))) {
			return addFileToDatabase($usage, $location, ifNull($filename, $file['filename']), $mustBeAnImage, $deleteWhenDone = false, $addToDocstoreDirIfPossible = true);
		}
	}
	
	return false;
}

function docstoreFilePath($fileIdOrPath, $useTmpDir = true) {
	if (is_numeric($fileIdOrPath)) {
		if (!$fileIdOrPath = getRow('files', array('location', 'data', 'path'), array('id'=> $fileIdOrPath))) {
			return false;
		}
		
		if ($fileIdOrPath['location'] == 'docstore') {
			$fileIdOrPath = $fileIdOrPath['path'];
		
		} elseif ($useTmpDir && ($temp_file = tempnam(sys_get_temp_dir(), 'doc')) && (file_put_contents($temp_file, $fileIdOrPath['data']))) {
			return $temp_file;
		
		} else {
			return false;
		}
	}
	
	if ($fileIdOrPath && ($dir = setting('docstore_dir')) && (is_dir($dir = $dir. '/'. $fileIdOrPath. '/'))) {
		foreach (scandir($dir) as $file) {
			if (substr($file, 0, 1) != '.') {
				return $dir. $file;
			}
		}
	}
	
	return false;
}

//deprecated
function documentTypeIsAllowed($file) {
	return checkDocumentTypeIsAllowed($file);
}

function documentMimeType($file) {
	$type = explode('.', $file);
	$type = $type[count($type) - 1];
	
	return ifNull(getRow('document_types', 'mime_type', array('type' => strtolower($type))), 'application/octet-stream');
}

function fileLink($fileId, $hash = false) {
	//Check that this file exists
	if (!$fileId
	 || !($file = getRow('files', array('usage', 'checksum', 'filename', 'location', 'path'), $fileId))) {
		return false;
	}
	
	//Workout a hash for the file
	if (!$hash) {
		$hash = $file['checksum'];
	}
	
	//Try to get a directory in the cache dir
	$path = false;
	if (cleanDownloads()) {
		$path = createCacheDir($hash, 'files', false);
	}
	
	//Otherwise attempt to create the resized version in the cache directory
	if ($path) {
	
		//If the image is already available, all we need to do is link to it
		if (file_exists($path. $file['filename'])) {
			return $path. rawurlencode($file['filename']);
		}
		
		//Attempt to add the image inside the cache directory
		if ($file['location'] == 'db') {
			$file['data'] = getRow('files', 'data', $fileId);
		
		} elseif ($pathDS = docstoreFilePath($file['path'])) {
			$file['data'] = file_get_contents($pathDS);
		
		} else {
			return false;
		}
		
		if (file_put_contents(CMS_ROOT. $path. $file['filename'], $file['data'])) {
			chmod(CMS_ROOT. $path. $file['filename'], 0666);
			return $path. rawurlencode($file['filename']);
		}
	}
	
	//If we could not use the cache directory, we'll have to link to file.php and load the file from the database each time on the fly.
	return 'zenario/file.php?usage='. $file['usage']. '&c='. $file['checksum']. '&filename='. urlencode($file['filename']);
}

//Format a file type for display
//	function formatFileTypeNicely($type) {}

function guessAltTagFromFilename($filename) {
	$filename = explode('.', $filename);
	unset($filename[count($filename) - 1]);
	return implode('.', $filename);
}

function imageLink(
	&$width, &$height, &$url, $fileId, $widthLimit = 0, $heightLimit = 0, $mode = 'resize', $offset = 0,
	$useCacheDir = true, $internalFilePath = false, $returnImageStringIfCacheDirNotWorking = false
) {
	$url = $width = $height = $newWidth = $newHeight = $cropWidth = $cropHeight = $cropNewWidth = $cropNewHeight = false;
	$widthLimit = (int) $widthLimit;
	$heightLimit = (int) $heightLimit;
	
	//Check that this file exists, and is actually an image
	if (!$fileId
	 || !($file = getRow('files', array('mime_type', 'width', 'height', 'working_copy_width', 'working_copy_height', 'working_copy_2_width', 'working_copy_2_height', 'organizer_width', 'organizer_height', 'organizer_list_width', 'organizer_list_height', 'checksum', 'filename', 'location', 'path'), $fileId))
	 || !(substr($file['mime_type'], 0, 6) == 'image/')) {
		return false;
	}
	
	//If no limits were set, use the image's own width and height
	if (!$widthLimit) {
		$widthLimit = $file['width'];
	}
	if (!$heightLimit) {
		$heightLimit = $file['height'];
	}
	
	//Workout a hash for the image
	$hash = hash64($widthLimit. '_'. $heightLimit. '_'. $mode. '_'. $offset. '_'. $file['checksum']);
	
	//Work out what size the resized image should actually be
	resizeImageByMode(
		$mode, $file['width'], $file['height'],
		$widthLimit, $heightLimit,
		$newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight);
	
	//Try to get a directory in the cache dir
	$path = false;
	if ($useCacheDir && cleanDownloads()) {
		$path = createCacheDir($hash, 'images', false);
	}
	
	//Look for the image inside the cache directory
	if ($path && file_exists($path. $file['filename'])) {
		
		//If the image is already available, all we need to do is link to it
		if ($internalFilePath) {
			$url = CMS_ROOT. $path. $file['filename'];
		
		} else {
			$url = $path. rawurlencode($file['filename']);
			
			if ($cookieFreeDomain = cookieFreeDomain()) {
				$url = $cookieFreeDomain. $url;
			}
			
			$width = $cropNewWidth;
			$height = $cropNewHeight;
			return true;
		}
	}
	
	//Otherwise, create a resized version now
	if ($path || $returnImageStringIfCacheDirNotWorking) {
		
		//Where an image has multiple sizes stored in the database, get the most suitable size
		$wcit = ifNull((int) setting('working_copy_image_threshold'), 66) / 100;
		
		foreach (array(
			array('organizer_list_data', 'organizer_list_width', 'organizer_list_height'),
			array('organizer_data', 'organizer_width', 'organizer_height'),
			array('working_copy_data', 'working_copy_width', 'working_copy_height'),
			array('working_copy_2_data', 'working_copy_2_width', 'working_copy_2_height')
		) as $c) {
			
			$xOK = $file[$c[1]] && $newWidth == $file[$c[1]] || ($newWidth < $file[$c[1]] * $wcit);
			$yOK = $file[$c[1]] && $newHeight == $file[$c[2]] || ($newHeight < $file[$c[2]] * $wcit);
			
			if ($mode == 'resize_and_crop' && (($yOK && $cropNewWidth >= $file[$c[1]]) || ($xOK && $cropNewHeight >= $file[$c[2]]))) {
				$xOK = $yOK = true;
			}
			
			if ($xOK && $yOK) {
				$file['width'] = $file[$c[1]];
				$file['height'] = $file[$c[2]];
				$file['data'] = getRow('files', $c[0], $fileId);
				break;
			}
		}
		
		if (empty($file['data'])) {
			if ($file['location'] == 'db') {
				$file['data'] = getRow('files', 'data', $fileId);
			
			} elseif ($pathDS = docstoreFilePath($file['path'])) {
				$file['data'] = file_get_contents($pathDS);
			
			} else {
				return false;
			}
		}
		
		if ($file['width'] != $cropNewWidth || $file['height'] != $cropNewHeight) {
			resizeImageString(
				$file['data'], $file['mime_type'],
				$file['width'], $file['height'],
				ifNull((int) $widthLimit, $file['width']), ifNull((int) $heightLimit, $file['height']),
				$mode, $offset);
		}
		
		//If $useCacheDir is set, attempt to store the image in the cache directory
		if ($useCacheDir && $path
		 && file_put_contents(CMS_ROOT. $path. $file['filename'], $file['data'])) {
			chmod(CMS_ROOT. $path. $file['filename'], 0666);
			
			if ($internalFilePath) {
				$url = CMS_ROOT. $path. $file['filename'];
			
			} else {
				$url = $path. rawurlencode($file['filename']);
				
				if ($cookieFreeDomain = cookieFreeDomain()) {
					$url = $cookieFreeDomain. $url;
				}
			}
			
			$width = $cropNewWidth;
			$height = $cropNewHeight;
			return true;
		
		//Otherwise just return the data if $returnImageStringIfCacheDirNotWorking is set
		} elseif ($returnImageStringIfCacheDirNotWorking) {
			return $file['data'];
		}
	}
	
	//If $internalFilePath or $returnImageStringIfCacheDirNotWorking were set then we need to give up at this point.
	if ($internalFilePath || $returnImageStringIfCacheDirNotWorking) {
		return false;
	
	//Otherwise, we'll have to link to file.php and do any resizing needed in there.
	} else {
		//Note that using the session for each image is quite slow, so it's better to make sure that your cache/ directory is writable
		//and not use this fallback logic!
		if (!isset($_SESSION['zenario_allowed_files'])) {
			$_SESSION['zenario_allowed_files'] = array();
		}
		
		$_SESSION['zenario_allowed_files'][$hash] =
			array(
				'width' => $widthLimit, 'height' => $heightLimit,
				'mode' => $mode, 'offset' => $offset,
				'id' => $fileId, 'useCacheDir' => $useCacheDir);
		
		$url = 'zenario/file.php?usage=resize&c='. $hash. '&filename='. rawurlencode($file['filename']);
		
		$width = $cropNewWidth;
		$height = $cropNewHeight;
		return true;
	}
}

function itemStickyImageId($cID, $cType, $cVersion = false) {
	if (!$cVersion) {
		if (checkPriv()) {
			$cVersion = getLatestVersion($cID, $cType);
		} else {
			$cVersion = getPublishedVersion($cID, $cType);
		}
	}
	
	return getRow('versions', 'sticky_image_id', array('id' => $cID, 'type' => $cType, 'version' => $cVersion));
}

function itemStickyImageLink(&$width, &$height, &$url, $cID, $cType, $cVersion = false, $widthLimit = 0, $heightLimit = 0, $mode = 'resize', $offset = 0, $useCacheDir = true) {
	if ($imageId = itemStickyImageId($cID, $cType, $cVersion)) {
		return imageLink($width, $height, $url, $imageId, $widthLimit, $heightLimit, $mode, $offset, $useCacheDir);
	} else {
		return false;
	}
}

function createPpdfFirstPageScreenshotPng($file) {
	if (file_exists($file) && is_readable($file)) {
		if (documentMimeType($file) == 'application/pdf') {
			if (!windowsServer() && execEnabled()) {
				if ($temp_file = tempnam(sys_get_temp_dir(), 'pdf2png')) {
					$escaped_file = escapeshellarg($file);
					//$jpeg_file = basename($file) . '.jpg';
					$cmd = escapeshellarg(ifNull(setting('ghostscript_path'), 'gs')).
						' -dNOPAUSE -q -dBATCH -sDEVICE=png16m -r' . ifNull((int)setting('ghostscript_dpi'), '72') . 
						' -sOutputFile="' . $temp_file . '" -dLastPage=1 ' . $escaped_file;
					exec($cmd, $output, $return_var);
						
					return $return_var == 0 ? $temp_file : false;
				}
			}
		}
	}
	return false;
}

function addContentItemPdfScreenshotImage($cID, $cType, $cVersion, $file_name, $setAsStickImage=false){
	if($img_file = createPpdfFirstPageScreenshotPng($file_name)) {
		$img_base_name = basename($file_name) . '.png';
		$fileId = addFileToDatabase('image', $img_file, $img_base_name, true, true);
		if ($fileId) {
			setRow('inline_images', array(), array(
					'image_id' => $fileId,
					'foreign_key_to' => 'content',
					'foreign_key_id' => $cID, 'foreign_key_char' => $cType, 'foreign_key_version' => $cVersion
				));
			if($setAsStickImage) {
				updateVersion($cID, $cType, $cVersion, array('sticky_image_id' => $fileId));
				syncInlineFileContentLink($cID, $cType, $cVersion);
			}
			return true;
		}
	}
	return false;
}

function plainTextExtract($file, &$extract) {
	$extract = '';
	
	if (file_exists($file) && is_readable($file)) {
		switch (documentMimeType($file)) {
			//.doc
			case 'application/msword':
				if (!windowsServer() && execEnabled()) {
					$return_var = false;
					exec(
						escapeshellarg(ifNull(setting('antiword_path'), 'antiword')).
						' '.
						escapeshellarg($file),
					$extract, $return_var);
					
					if ($return_var == 0) {
						$extract = utf8_encode(implode("\n", $extract));
						$extract = trim(mb_ereg_replace('\s+', ' ', str_replace("\xc2\xa0", ' ', $extract)));
						return true;
					}
				}
				
				break;
			
			
			//.docx
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
				if (class_exists('ZipArchive')) {
					$zip = new ZipArchive;
					if ($zip->open($file) === true) {
						if ($extract = html_entity_decode(strip_tags($zip->getFromName('word/document.xml')), ENT_QUOTES, 'UTF-8')) {
							$zip->close();
							
							$extract = trim(mb_ereg_replace('\s+', ' ', str_replace("\xc2\xa0", ' ', $extract)));
							return true;
						}
						$zip->close();
					}
				}
				
				break;
			
			
			//.pdf
			case 'application/pdf':
				if (!windowsServer() && execEnabled()) {
					if ($temp_file = tempnam(sys_get_temp_dir(), 'p2t')) {
						$return_var = $output = false;
						exec(
							escapeshellarg(ifNull(setting('pdftotext_path'), 'pdftotext')).
							' '.
							escapeshellarg($file).
							' '.
							escapeshellarg($temp_file),
						$output, $return_var);
						
						
						//pdftotext has a bug where it can't read certain filenames (maybe there's some missed escaping in its code?)
						//If pdftotext couldn't read the file, try copying the file to a sensible name
						if ($return_var == 1) {
							if ($temp_pdf_file = tempnam(sys_get_temp_dir(), 'pdf')) {
								copy($file, $temp_pdf_file);
								
								$return_var = $output = false;
								exec(
									escapeshellarg(ifNull(setting('pdftotext_path'), 'pdftotext')).
									' '.
									escapeshellarg($temp_pdf_file).
									' '.
									escapeshellarg($temp_file),
								$output, $return_var);
							}
						}
						
						
						if ($return_var == 0) {
							$extract = file_get_contents($temp_file);
							unlink($temp_file);
							
							$extract = trim(mb_ereg_replace('\s+', ' ', str_replace("\xc2\xa0", ' ', $extract)));
							return true;
						}
					}
				}
				
				break;
		}
	}
	
	$extract = '';
	return false;
}

function updatePlainTextExtract($cID, $cType, $cVersion, $fileId = false) {
	if ($fileId === false) {
		$fileId = getRow('versions', 'file_id', array('id' => $cID, 'type' => $cType, 'version' => $cVersion));
	}
	
	$success = false;
	
	$extract = array('extract' => '', 'extract_wordcount' => 0);
	if ($fileId && $file = docstoreFilePath($fileId)) {
		$success = plainTextExtract($file, $extract['extract']);
		$extract['extract_wordcount'] = str_word_count($extract['extract']);
		addContentItemPdfScreenshotImage($cID, $cType, $cVersion, $file, true);
	}
	
	setRow('content_cache', $extract, array('content_id' => $cID, 'content_type' => $cType, 'content_version' => $cVersion));
	
	return $success;
}

function updateDocumentPlainTextExtract($fileId, &$extract, &$img_file_id) {
	$errors = array();
	$extract = array('extract' => '', 'extract_wordcount' => 0);
	
	$filePath = docstoreFilePath($fileId);
	
	plainTextExtract($filePath, $extract['extract']);
	$extract['extract_wordcount'] = str_word_count($extract['extract']);
	
	if ($img_file = createPpdfFirstPageScreenshotPng($filePath)) {
		$img_base_name = basename($filePath) . '.png';
		$img_file_id = addFileToDatabase('documents', $img_file, $img_base_name, true, true);
	}
}

function getPathOfUploadedFileInCacheDir($string) {
	$details = explode('/', decodeItemIdForStorekeeper($string), 3);
	
	if (!empty($details[1])
	 && file_exists($filepath = CMS_ROOT. 'cache/uploads/'. preg_replace('@\W@', '', $details[0]). '/'. $details[1])) {
		return $filepath;
	} else {
		return false;
	}
}