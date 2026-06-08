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

namespace ze;

class file {

	public static function trackDownload($url) {
		return "if (window.gtag) gtag('event', 'pageview', {'page_location' : '".\ze\escape::js($url)."'});";
	}
	
	
	//Return the basic filetype from the mime-type.  E.g. "text/plain" would be just "text".
	//I've had to add a few hard-coded exception for JSON, as it's registered as "application"
	//but is classified as text when scanned.
	public static function basicType($mimeType) {
		switch ($mimeType) {
			case 'application/json':
				return 'text';
			default:
				return explode('/', $mimeType, 2)[0];
		}
	}
	
	public static function genericCheckError($filepath) {
		return new \ze\error('INVALID', \ze\admin::phrase('The contents of the file "[[filename]]" are corrupted and/or invalid.', ['filename' => basename($filepath)]));
	}

	//Remove an image from the public/images/ directory
	public static function deletePublicImage($image, $specialImage = false) {
	
		if (!is_array($image)) {
			$image = \ze\row::get('files', ['mime_type', 'short_checksum', 'usage'], $image);
		}
	
		if ($image
		 && $image['short_checksum']
		 && \ze\file::isImageOrSVG($image['mime_type'])) {
			
			//Starting in version 10.2, we're going to be using slightly different logic for special images.
			//These will always be considered public, and be placed in the public/special_images directory instead
			//of the public/images directory.
			if ($specialImage) {
				$publicDir = 'public/special_images/';
		
			//T13130, MIC images should be stored in their own folder inside public/ directory
			//MiC images use slightly different different logic to regular images.
			//From version 10.3 onwards, we're going to be putting them in a different directory to regular images
			//to prevent bugs and issues caused when the same image is used in both places.
			} elseif ($image['usage'] == 'mic') {
				$publicDir = 'public/mic_images/';
			
			} else {
				$publicDir = 'public/images/';
			}
			
			
			\ze\cache::deleteDir(CMS_ROOT. $publicDir. $image['short_checksum'], 1);
		}
	}

	public static function isAllowed($file, $alwaysAllowImages = true) {
		$type = explode('.', $file);
		$type = $type[count($type) - 1];
		
		if (\ze\file::isExecutable($type)) {
			return false;
		}
		
		$sql = '
			SELECT mime_type, is_allowed
			FROM '. DB_PREFIX. 'document_types
			WHERE `type` = \''. \ze\escape::asciiInSQL($type). '\'';
		
		if (!$dt = \ze\sql::fetchRow($sql)) {
			return false;
		}
		
		return (bool) ($dt[1] || ($alwaysAllowImages && \ze\file::isImageOrSVG($dt[0])));
	}

	public static function isExecutable($extension) {
		switch (strtolower($extension)) {
			case 'asp':
			case 'bin':
			case 'cgi':
			case 'exe':
			case 'js':
			case 'jsp':
			case 'phar':
			case 'php':
			case 'php3':
			case 'ph3':
			case 'php4':
			case 'ph4':
			case 'php5':
			case 'ph5':
			case 'phtm':
			case 'phtml':
			case 'sh':
			//As of 9.0, we now globally block users from uploading HTML files
			case 'htm':
			case 'html':
			case 'htt':
			case 'mhtml':
			case 'stm':
			case 'xhtml':
				return true;
			default:
				return false;
		}
	}
	
	public static function isArchive($extension) {
		switch (strtolower($extension)) {
			case '7z':
			case 'csv':
			case 'gtar':
			case 'gz':
			case 'sql':
			case 'tar':
			case 'tgz':
			case 'zip':
				return true;
			default:
				return false;
		}
	}

	public static function contentLink(&$url, $cID, $cType, $cVersion) {
		$url = false;
	
		//Check that this file exists
		if (!($version = \ze\row::get('content_item_versions', ['filename', 'file_id'], ['id' => $cID, 'type' => $cType, 'version' => $cVersion]))
		 || !($file = \ze\row::get('files', ['mime_type', 'checksum', 'filename', 'location', 'path'], $version['file_id']))) {
			return $url = false;
		}
	
	
		if (\ze\admin::id()) {
			$onlyForCurrentVisitor = \ze::setting('restrict_downloads_by_ip');
			$hash = \ze::hash64('admin_'. ($_SESSION['admin_userid'] ?? false). '_'. \ze\user::ip(). '_'. $file['checksum']);
	
		} elseif ($_SESSION['extranetUserID'] ?? false) {
			$onlyForCurrentVisitor = \ze::setting('restrict_downloads_by_ip');
			$hash = \ze::hash64('user_'. ($_SESSION['extranetUserID'] ?? false). '_'. \ze\user::ip(). '_'. $file['checksum']);
	
		} else {
			$onlyForCurrentVisitor = false;
			$hash = $file['checksum'];
		}
	
		//Check to see if the file is missing
		$path = false;
		if ($file['location'] == 'docstore' && !($path = \ze\file::docstorePath($file['path']))) {
			return false;
	
		//Attempt to add/symlink the file in the cache directory
		} elseif ($path && (\ze\cache::cleanDirs()) && ($dir = \ze\cache::createDir($hash, 'private/downloads', $onlyForCurrentVisitor))) {
			$url = $dir. ($version['filename'] ?: $file['filename']);
			
			if (!file_exists(CMS_ROOT. $url)) {
				\ze\server::symlinkOrCopy($path, CMS_ROOT. $url, 0666);
			}
	
		//Otherwise, we'll need to link to file.php for the file
		} else {
			$url = 'zenario/file.php?usage=content&c='. $file['checksum'];
		
			if ($cID && $cType) {
				$url .= '&cID='. $cID. '&cType='. $cType;
			
				if (\ze\priv::check() && $cVersion) {
					$url .='&cVersion='. $cVersion;
				}
			}
		}
		
		if (\ze::$mustUseFullPath) {
			$url = \ze\link::absolute(). $url;
		}
	
		return true;
	}

	public static function publicLink($fileId, $filename = false) {
		$url = \ze\file::link($fileId, false, 'public/documents', false, $filename);
		
		if (!$url) {
			return false;
		}
		
		if (\ze::$mustUseFullPath) {
			$url = \ze\link::absolute(). $url;
		}
	
		return $url;
	}

	public static function docstorePath($fileIdOrPath, $useTmpDir = true, $customDocstorePath = false) {
		if (is_numeric($fileIdOrPath)) {
			if (!$fileIdOrPath = \ze\row::get('files', ['location', 'data', 'path'], ['id'=> $fileIdOrPath])) {
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
	
		$dir = \ze::setting('docstore_dir');
		if ($customDocstorePath) {
			$dir = $customDocstorePath;
		}
	
		if ($fileIdOrPath && $dir && (is_dir($dir = $dir. '/'. $fileIdOrPath. '/'))) {
			foreach (scandir($dir) as $file) {
				if (substr($file, 0, 1) != '.') {
					return $dir. $file;
				}
			}
		}
	
		return false;
	}
	
	public static function stream($fileId, $filename = false) {
		if ($file = \ze\row::get('files', ['location', 'data', 'path', 'mime_type', 'filename'], ['id'=> $fileId])) {
			
			if ($filename === false) {
				$filename = $file['filename'];
			}
			
			header('Content-type: '. ($file['mime_type'] ?: 'application/octet-stream'));
			header('Content-Disposition: attachment; filename="'. \ze\file::safeName($filename). '"');
			
			\ze\cache::end();
			if ($file['location'] == 'docstore') {
				readfile(\ze\file::docstorePath($file['path']));
			} else {
				echo $file['data'];
			}
		}
	}

	public static function mimeType($file) {
		$parts = explode('.', $file);
		$type = $parts[count($parts) - 1];
	
		//Work on files in cache with upload extension added
		if ($type == 'upload') {
			$type = $parts[count($parts) - 2];
		}
		$type = strtolower($type);
		
		//Look up this mime type if we have database access
		$mimeType = false;
		if (!is_null(\ze::$dbL)) {
			$mimeType = \ze\row::get('document_types', 'mime_type', ['type' => $type]);
		}
		
		if ($mimeType !== false) {
			return $mimeType;
		}
		
		//Some fallbacks.
		switch ($type) {
			case 'gif':
				return 'image/gif';
			case 'ico':
				return 'image/x-icon';
			case 'jpe':
			case 'jpeg':
			case 'jpg':
				return 'image/jpeg';
			case 'webp':
				return 'image/webp';
			case 'png':
				return 'image/png';
			case 'svg':
				return 'image/svg+xml';
			case 'sql':
				return 'text/plain';
			default:
				return 'application/octet-stream';
		}
	}

	public static function isImage($mimeType) {
		return $mimeType == 'image/gif'
			|| $mimeType == 'image/jpeg'
			|| $mimeType == 'image/webp'
			|| $mimeType == 'image/png';
	}

	public static function isImageOrSVG($mimeType) {
		return $mimeType == 'image/gif'
			|| $mimeType == 'image/jpeg'
			|| $mimeType == 'image/webp'
			|| $mimeType == 'image/png'
			|| $mimeType == 'image/svg+xml';
	}

	public static function webpName($filename) {
		return implode('.', explode('.', $filename, -1)). '.webp';
	}

	public static function getDocumentFrontEndLink($documentId, $privateLink = false) {
		$link = false;
		$document = \ze\row::get('documents', ['file_id', 'filename', 'privacy'], $documentId);
		if ($document) {
			// Create private link
			if ($privateLink || ($document['privacy'] == 'private')) {
				$link = \ze\file::createPrivateLink($document['file_id'], $document['filename']);
			// Create public link
			} elseif ($document['privacy'] == 'public' && !\ze\server::isWindows()) {
				$link = \ze\file::createPublicLink($document['file_id'], $document['filename']);
			// Create link based on content item status and privacy
			}
		}
		return $link;
	}

	public static function createPublicLink($fileId, $filename = false) {
		$path = \ze\file::docstorePath($fileId, false);
		$file = \ze\row::get('files', ['short_checksum', 'filename'], $fileId);
		
		if ($filename === false) {
			$filename = $file['filename'];
		}
	
		$relDir = 'public'. '/downloads/'. $file['short_checksum'];
		$absDir =  CMS_ROOT. $relDir;
		$relPath = $relDir. '/'. rawurlencode($filename);
		$absPath = $absDir. '/'. $filename;
	
		if (!file_exists($absPath)) {
			if (!file_exists($absDir)) {
				mkdir($absDir);
			}
			\ze\server::symlinkOrCopy($path, $absPath, 0666);
		}
		return $relPath;
	}

	public static function createPrivateLink($fileId, $filename = false) {
		return \ze\file::linkForCurrentVisitor($fileId, \ze::hash64($fileId. '_'. \ze\ring::random(10)), 'private/downloads', false, $filename);
	}
	
	//Generate an access code for an image.
	//This is a code, unique to the current visitor, that cannot be generated without knowing
	//the site's secret key that's server-side only.
	//By passing a visitor these codes for specific images, that will allow us to grant access to them
	//on a one-by-one basis.
	public static function accessCode($checksum) {
		return \ze::hash64($checksum. '~'. session_id(). '~'. \ze::setting('site_id'));
	}
	
	
	//Produce a label for a file in the standard format
	public static function labelDetails($fileId, $filename = null) {
		
		$sql = '
			SELECT id, filename, size, path, location, width, height, checksum, short_checksum, `usage`
			FROM '. DB_PREFIX. 'files
			WHERE id = '. (int) $fileId;
		
		if ($file = \ze\sql::fetchAssoc($sql)) {
			
			$file['label'] = $filename ?? $file['filename'];

			$file['size'] = \ze\file::formatSizeUnits($file['size']);

			if (\ze::isAdmin()) {
				$sql = '
					SELECT 1
					FROM '. DB_PREFIX. 'files
					WHERE `usage` = \''. \ze\escape::asciiInSQL($file['usage']). '\'
					  AND filename = \''. \ze\escape::sql($file['filename']). '\'
					  AND short_checksum != \''. \ze\escape::asciiInSQL($file['short_checksum']). '\'
					LIMIT 1';
			
				if ($file['ssc'] = (bool) \ze\sql::fetchRow($sql)) {
					$file['label'] .= ' '. \ze\admin::phrase('[checksum [[short_checksum]]]', $file);
				}
			
			//If the current visitor is not an admin, create an access code for the image and put
			//it into the metadata so the visitor will be able to view its thumbnail using file.php.
			} else {
				$file['access_code'] = \ze\file::accessCode($file['checksum']);
			}
		
			if ($file['width'] && $file['height']) {
				$file['label'] .= ' ['. $file['width']. ' × '. $file['height']. 'px]';
			}
			if ($file['location'] == 's3') {
				if ($file['path']) {
					$s3fileName = $file['path'].'/'.$file['filename'];
				} else {
					$s3fileName = $file['filename'];
				}
				if ($s3fileName) {
					if (\ze\module::inc('zenario_ctype_document')) {
						$presignedUrl = \zenario_ctype_document::getS3FilePresignedUrl($s3fileName);
					}
					 $file['s3Link'] = $presignedUrl;
				}
			}
		}
		
		return $file;
	}

	public static function linkForCurrentVisitor($fileId, $hash = false, $type = 'private/files', $customDocstorePath = false, $filename = false) {
		return \ze\file::link($fileId, $hash, $type, $customDocstorePath, $filename, true);
	}
	
	public static function link($fileId, $hash = false, $type = 'private/files', $customDocstorePath = false, $filename = false, $forCurrentVisitor = false) {
		//Check that this file exists
		if (!$fileId
		 || !($file = \ze\row::get('files', ['usage', 'short_checksum', 'checksum', 'filename', 'location', 'path'], $fileId))) {
			return false;
		}
		
		if ($filename === false) {
			$filename = $file['filename'];
		}
	
		if (\ze\ring::chopPrefix('public/', $type)) {
			if (!$hash) {
				//Workout a hash for the file
				$hash = $file['short_checksum'];
			}
			$onlyForCurrentVisitor = false;
		} else {
			if (!$hash) {
				//Workout a hash for the file
				$hash = $file['checksum'];
			}
			$onlyForCurrentVisitor = $forCurrentVisitor && \ze::setting('restrict_downloads_by_ip');
		}
	
		//Try to get a directory in the cache dir
		$path = false;
		if (\ze\cache::cleanDirs()) {
			$path = \ze\cache::createDir($hash, $type, $onlyForCurrentVisitor);
		}
	
		if ($path) {
	
			//If the file is already there, just return the link
			if (file_exists(CMS_ROOT. $path. $filename)) {
				return $path. rawurlencode($filename);
			}
		
			//Otherwise we need to add it
			if ($file['location'] == 'db') {
				$data = \ze\row::get('files', 'data', $fileId);
				file_put_contents(CMS_ROOT. $path. $filename, $data);
				unset($data);
				\ze\cache::chmod(CMS_ROOT. $path. $filename, 0666);
		
			} elseif ($pathDS = \ze\file::docstorePath($file['path'], true, $customDocstorePath)) {
				
				\ze\server::symlinkOrCopy($pathDS, CMS_ROOT. $path. $filename, 0666);
		
			} else {
				return false;
			}
		
			return $path. rawurlencode($filename);
		}
	
		//If we could not use the cache directory, we'll have to link to file.php and load the file from the database each time on the fly.
		return 'zenario/file.php?usage='. $file['usage']. '&c='. $file['checksum']. '&filename='. urlencode($filename);
	}

	public static function specialImageLink($fileId) {
		return \ze\file::link($fileId, false, 'public/special_images');
	}

	public static function guessAltTagFromname($filename) {
		$filename = explode('.', $filename);
		unset($filename[count($filename) - 1]);
		return implode('.', $filename);
	}
	



	public static function createPdfFirstPageScreenshotPng($file) {
		if (file_exists($file) && is_readable($file)) {
			if (\ze\file::mimeType($file) == 'application/pdf') {
				if ($programPath = \ze\server::programPathForExec(\ze::setting('ghostscript_path'), 'gs')) {
					if ($temp_file = tempnam(sys_get_temp_dir(), 'pdf2png')) {
						$escaped_file = escapeshellarg($file);
						//$jpeg_file = basename($file) . '.jpg';
						$cmd = escapeshellarg($programPath).
							' -dNOPAUSE -q -dBATCH -sDEVICE=png16m -r'. ((int) \ze::setting('ghostscript_dpi') ?: '72').
							' -sOutputFile="' . $temp_file . '" -dLastPage=1 ' . $escaped_file;
						exec($cmd, $output, $return_var);
						
						return $return_var == 0 ? $temp_file : false;
					}
				}
			}
		}
		return false;
	}

	public static function addContentItemPdfScreenshotImage($cID, $cType, $cVersion, $file_name, $setAsStickImage = false) {
		if ($img_file = \ze\file::createPdfFirstPageScreenshotPng($file_name)) {
			$img_base_name = basename($file_name) . '.png';
			$fileId = \ze\fileAdm::addToDatabase('image', $img_file, $img_base_name, true, true);
			if ($fileId) {
				\ze\row::set('inline_images', [], [
						'image_id' => $fileId,
						'foreign_key_to' => 'content',
						'foreign_key_id' => $cID, 'foreign_key_char' => $cType, 'foreign_key_version' => $cVersion
					]);
				if ($setAsStickImage) {
					\ze\contentAdm::updateVersion($cID, $cType, $cVersion, ['feature_image_id' => $fileId]);
					\ze\contentAdm::updateContentItemCache($cID, $cType, $cVersion);
				}
				return true;
			}
		}
		
		return false;
	}

	public static function safeName($filename, $strict = false, $replaceSpaces = false) {
		
		if ($strict || $replaceSpaces) {
			$filename = str_replace(' ', '-', $filename);
		}
		
		if ($strict) {
			$filename = preg_replace('@[^\w\.-]@', '', $filename);
		} else {
			$filename = str_replace(['%', '/', '\\', ':', ';', '*', '?', '"', '<', '>', '|'], '', $filename);
		}
		
		if ($filename === '') {
			$filename = 'noname';
		}
		if ($filename[0] === '.') {
			$filename = 'noname'. $filename;
		}
		return $filename;
	}

	public static function getPathOfUploadInCacheDir($uploadCode) {
		
		if (is_numeric($uploadCode)) {
			return false;
		}
		
		$details = explode('/', \ze\ring::decodeIdForOrganizer($uploadCode), 3);
	
		if (!empty($details[1])
		 && file_exists($filepath = CMS_ROOT. 'private/uploads/'. preg_replace('@[^\w-]@', '', $details[0]). '/'. \ze\file::safeName($details[1]))) {
			return $filepath;
		} else {
			return false;
		}
	}

	public static function fileSizeConvert($bytes) {
		$bytes = floatval($bytes);
			$arBytes = [
				0 => [
					"UNIT" => "TB",
					"VALUE" => pow(1024, 4)
				],
				1 => [
					"UNIT" => "GB",
					"VALUE" => pow(1024, 3)
				],
				2 => [
					"UNIT" => "MB",
					"VALUE" => pow(1024, 2)
				],
				3 => [
					"UNIT" => "KB",
					"VALUE" => 1024
				],
				4 => [
					"UNIT" => "bytes",
					"VALUE" => 1
				],
			];
	
		foreach($arBytes as $arItem) {
			if($bytes >= $arItem["VALUE"]) {
				$result = $bytes / $arItem["VALUE"];
				$result = strval(round($result, 2)). " " .$arItem["UNIT"];
				break;
			}
		}
		return $result;
	}
	
	const ASPECT_RATIO_LIMIT_DEG = 10.0;
	public static function aspectRatioToDegrees($width, $height) {
		return atan2($width, $height) * 180 / M_PI;
	}
	
	//Quick and dirty little function to remove the common factors from two numbers.
	//The numbers won't be very large so it needn't be super efficient
	public static function aspectRatioRemoveFactors($a, $b, $sensibleLimit) {
		
		//Handle the simple case where the two numbers are equal.
		if ($a == $b) {
			return [1, 1];
		}
		
		//Loop through all possible factors for the numbers.
		//Note that we only need to check up to the smallest square root of the number
		$step = 1;
		$limit = min((int) floor(sqrt($a)), (int) floor(sqrt($b)));
		
		for ($i = 2; $i <= $limit; $i += $step) {
			while (($a % $i === 0) && ($b % $i === 0)) {
				$a = (int) ($a / $i);
				$b = (int) ($b / $i);
			}
			
			//Start skipping odd numbers after 2.
			//Note: really I could skip all non-prime numebrs, but this is just a quick-and-dirty
			//implementation.
			if ($i === 3) {
				$step = 2;
			}
		}
		
		//Catch the case where one number is a factor of the other
		$i = min($a, $b);
		if (($a % $i === 0) && ($b % $i === 0)) {
			$a = (int) ($a / $i);
			$b = (int) ($b / $i);
		}
		
		//Have an option not to have crazy mis-matched aspect ratios.
		//(This can cause a problem in the admin UI.)
		if ($sensibleLimit) {
			$max = max($a, $b);
			
			//If we see any of the numbers in the aspect ratio go above 100,
			//adjust it slightly (rounding as needed) so that they are no more than 100.
			if ($max > 100) {
				$scale = $max / 100;
				$a = max(1, (int) round($a / $scale));
				$b = max(1, (int) round($b / $scale));
				
				return \ze\file::aspectRatioRemoveFactors($a, $b, false);
			}
		}
		
		
		return [$a, $b];
	}

	public static function moveFileFromDBToDocstore($fileId) {
		
		$path = $filePath = false;
		if (($file = \ze\row::get('files', ['usage', 'filename', 'short_checksum', 'data'], ['id' => $fileId, 'location' => 'db', 'data' => ['!' => null]]))
		 && (\ze\fileAdm::createDocstoreDir($file, $path, $filePath))) {
			
			file_put_contents($filePath, $file['data']);
			\ze\cache::chmod($filePath, 0666);
			
			\ze\row::update('files', ['location' => 'docstore', 'path' => $path, 'data' => null], $fileId);

			return true;
		}

		return false;
	}

	public static function formatSizeUnits($bytes, $adminMode = false) {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' ';
            if ($adminMode) {
            	$bytes .= \ze\admin::phrase('_FILE_SIZE_UNIT_GB');
            } else {
            	$bytes .= \ze\lang::phrase('_FILE_SIZE_UNIT_GB');
            }
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' ';
            if ($adminMode) {
            	$bytes .= \ze\admin::phrase('_FILE_SIZE_UNIT_MB');
            } else {
            	$bytes .= \ze\lang::phrase('_FILE_SIZE_UNIT_MB');
            }
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 0) . ' ';
            if ($adminMode) {
            	$bytes .= \ze\admin::phrase('_FILE_SIZE_UNIT_KB');
            } else {
            	$bytes .= \ze\lang::phrase('_FILE_SIZE_UNIT_KB');
            }
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' ';
            if ($adminMode) {
            	$bytes .= \ze\admin::phrase('_FILE_SIZE_UNIT_BYTES');
            } else {
            	$bytes .= \ze\lang::phrase('_FILE_SIZE_UNIT_BYTES');
            }
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' ';
            if ($adminMode) {
            	$bytes .= \ze\admin::phrase('_FILE_SIZE_UNIT_BYTE');
            } else {
            	$bytes .= \ze\lang::phrase('_FILE_SIZE_UNIT_BYTE');
            }
        } else {
            $bytes = '0 ';
            if ($adminMode) {
            	$bytes .= \ze\admin::phrase('_FILE_SIZE_UNIT_BYTES');
            } else {
            	$bytes .= \ze\lang::phrase('_FILE_SIZE_UNIT_BYTES');
            }
        }

        return $bytes;
	}

	public static function fileSizeBasedOnUnit($filevalue, $units) {
		$calculatedFilesize = $filevalue;
		if ($units == 'GB') {
			$calculatedFilesize = $filevalue * 1073741824;
		} elseif ($units == 'MB') {
			$calculatedFilesize = $filevalue * 1048576;
		} elseif ($units == 'KB') {
			$calculatedFilesize = $filevalue * 1024;
		}
		return $calculatedFilesize;
	}
}
