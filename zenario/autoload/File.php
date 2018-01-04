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

namespace Ze;

class File {

	public static function trackDownload($url) {
		if (!empty($_SESSION['admin_userid'])) {
			return '';
		} else {
			return "if (window.ga) ga('send', 'pageview', {'page' : '".jsEscape($url)."'});";
		}
	}

	public static function addToDocstoreDir($usage, $location, $filename = false, $mustBeAnImage = false, $deleteWhenDone = true) {
		return self::addToDatabase($usage, $location, $filename, $mustBeAnImage, $deleteWhenDone, true);
	}

	public static function addFromString($usage, &$contents, $filename, $mustBeAnImage = false, $addToDocstoreDirIfPossible = false) {
	
		if ($temp_file = tempnam(sys_get_temp_dir(), 'cpy')) {
			if (file_put_contents($temp_file, $contents)) {
				return self::addToDatabase($usage, $temp_file, $filename, $mustBeAnImage, true, $addToDocstoreDirIfPossible);
			}
		}
	
		return false;
	}

	public static function addToDatabase($usage, $location, $filename = false, $mustBeAnImage = false, $deleteWhenDone = false, $addToDocstoreDirIfPossible = false, $imageAltTag = false, $imageTitle = false, $imagePopoutTitle = false) {
		
		//Add some logic to handle any old links to email/inline/menu images (these are now just classed as "image"s).
		if ($usage == 'email'
		 || $usage == 'inline'
		 || $usage == 'menu') {
			$usage = 'image';
		}


		$file = array();

		if (!is_readable($location)
		 || !is_file($location)
		 || !($file['size'] = filesize($location))
		 || !($file['checksum'] = md5_file($location))
		 || !($file['checksum'] = base16To64($file['checksum']))) {
			return false;
		}
		$basename =  basename($location);
		if ($filename === false) {
			$filename = preg_replace('/([^.a-z0-9\s]+)/i', '-', $basename);
		}

		$file['filename'] = $filename;
		$file['mime_type'] = \Ze\File::mimeType($filename);
		$file['usage'] = $usage;

		if ($mustBeAnImage && !\Ze\File::isImageOrSVG($file['mime_type'])) {
			return false;
		}

		//Check if this file exists in the system already
		$key = array('checksum' => $file['checksum'], 'usage' => $file['usage']);
		if ($existingFile = getRow('files', array('id', 'filename', 'location', 'path'), $key)) {
			$key = $existingFile['id'];
	
			//If this file is stored in the database, continue running this function to move it to the docstore dir
			if (!($addToDocstoreDirIfPossible && $existingFile['location'] == 'db')) {
		
				//If this file is already stored, just update the name and remove the 'archived' flag if it was set
				$path = false;
				if ($existingFile['location'] == 'db' || ($path = \Ze\File::docstorePath($existingFile['path']))) {
					//If the name has changed, attempt to rename the file in the filesystem
					/*if ($path && $file['filename'] != $existingFile['filename']) {
						@rename($path, setting('docstore_dir'). '/'. $existingFile['path']. '/'. $file['filename']);
					}
			
					*/
					updateRow('files', array('filename' => $filename, 'archived' => 0), $key);
					if ($deleteWhenDone) {
						unlink($location);
					}
			
					return $existingFile['id'];
				}
			}
		}

		//Otherwise we must insert the new file


		//Check if the file is an image
		if (\Ze\File::isImageOrSVG($file['mime_type'])) {
	
			if (\Ze\File::isImage($file['mime_type'])) {
		
				$image = getimagesize($location);
				if ($image === false) {
					return false;
				}	
				$file['width'] = $image[0];
				$file['height'] = $image[1];
				$file['mime_type'] = $image['mime'];
		
				//Create resizes for the image as needed.
				//Working copies should only be created if they are enabled, and the image is big enough to need them.
				//Organizer thumbnails should always be created, even if the image needs to be scaled up
				foreach (array(
					array('working_copy_data', 'working_copy_width', 'working_copy_height', setting('working_copy_image_size'), setting('working_copy_image_size'), false),
					array('working_copy_2_data', 'working_copy_2_width', 'working_copy_2_height', setting('thumbnail_wc_image_size'), setting('thumbnail_wc_image_size'), false),
					array('thumbnail_180x130_data', 'thumbnail_180x130_width', 'thumbnail_180x130_height', 180, 130, true),
					array('thumbnail_64x64_data', 'thumbnail_64x64_width', 'thumbnail_64x64_height', 64, 64, true),
					array('thumbnail_24x23_data', 'thumbnail_24x23_width', 'thumbnail_24x23_height', 24, 23, true)
				) as $c) {
					if ($c[3] && $c[4] && ($c[5] || ($file['width'] > $c[3] || $file['height'] > $c[4]))) {
						$file[$c[1]] = $image[0];
						$file[$c[2]] = $image[1];
						$file[$c[0]] = file_get_contents($location);
						resizeImageString($file[$c[0]], $file['mime_type'], $file[$c[1]], $file[$c[2]], $c[3], $c[4]);
					}
				}
	
			} else
			if (function_exists('simplexml_load_string')
			 && ($svg = simplexml_load_string(file_get_contents($location)))) {
		
				foreach ($svg->attributes() as $name => $value) {
					switch (strtolower($name)) {
						case 'width':
							$file['width'] = (int) $value;
							break;
				
						case 'height':
							$file['height'] = (int) $value;
							break;
				
						case 'viewbox':
					
							$viewbox = explode(' ', (string) $value);
					
							if (empty($file['width']) && !empty($viewbox[2])) {
								$file['width'] = (int) $viewbox[2];
							}
							if (empty($file['height']) && !empty($viewbox[3])) {
								$file['height'] = (int) $viewbox[3];
							}
					}
				}
			}
	
	
			$filenameArray = explode('.', $filename);
			$altTag = trim(preg_replace('/[^a-z0-9]+/i', ' ', $filenameArray[0]));
			$file['alt_tag'] = $altTag;
		}


		$file['archived'] = 0;
		$file['created_datetime'] = now();

		if ($addToDocstoreDirIfPossible
		 && is_dir($dir = setting('docstore_dir'). '/')
		 && is_writable($dir)
		 && ((is_dir($dir = $dir. ($path = preg_replace('/\W/', '_', $filename). '_'. $file['checksum']). '/'))
		  || (mkdir($dir) && @chmod($dir, 0777)))) {
	
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

		$fileId = setRow('files', $file, $key);
		updateShortChecksums();
		return $fileId;
	}

	protected static $factory;
	protected static $options = [
		//'ignore_errors' => false,
		'jpegoptim_options' => ['--strip-all', '--all-progressive', '--max=88'],
		'optipng_options' => ['-i0', '-o1', '-quiet'],
		'advpng_options' => ['-z', '-2', '-q']
	];

	protected static function optimise($method, $path) {
		if (empty(self::$factory)) {
			$jpeg_quality_limit = (int) setting('jpeg_quality_limit');
			if (80 <= $jpeg_quality_limit && $jpeg_quality_limit <= 100) {
				self::$options['jpegoptim_options'][2] = '--max='. $jpeg_quality_limit;
			}
		
			foreach (['advpng', 'jpegoptim', 'jpegtran', 'optipng'] as $program) {
				self::$options[$program. '_bin'] =
					programPathForExec(setting($program. '_path'), $program, true);
			}
		
			self::$factory = new \ImageOptimizer\OptimizerFactory(self::$options);
		}

		if (!empty(self::$options[$method. '_bin'])) {
			//$time_start = microtime(true);
			//$sizeBefore = filesize($path);
			self::$factory->get($method)->optimize($path);
			//clearstatcache();
			//var_dump($path, $method, microtime(true) - $time_start, $sizeBefore, filesize($path));
			return true;
		}
		return false;
	}

	public static function optimizeImage($path) {
		return self::optimiseImage($path);
	}
	public static function optimiseImage($path) {
	
		if (!in(self::mimeType($path), 'image/png', 'image/jpeg')
		 || !is_file($path)
		 || !is_writable($path)) {
			return false;
		}
	
		switch (self::mimeType($path)) {
			case 'image/png':
				self::optimise('optipng', $path);
				self::optimise('advpng', $path);
				break;
			case 'image/jpeg':
				if (!self::optimise('jpegoptim', $path)) {
					self::optimise('jpegtran', $path);
				}
				break;
		}
	
		return true;
	}


	//Delete a file from the database, and anywhere it was stored on the disk
	public static function delete($fileId) {
	
		if ($file = getRow('files', array('path', 'mime_type', 'short_checksum'), $fileId)) {
	
			//If the file was being stored in the docstore and nothing else uses it...
			if ($file['path']
			 && !checkRowExists('files', array('id' => array('!' => $fileId), 'path' => $file['path']))) {
				//...then delete that directory from the docstore
				deleteCacheDir(setting('docstore_dir'). '/'. $file['path']);
			}
		
			//If the file was an image and there's no other copies with a different usage
			if (self::isImageOrSVG($file['mime_type'])
			 && !checkRowExists('files', array('id' => array('!' => $fileId), 'short_checksum' => $file['short_checksum']))) {
				//...then delete it from the public/images/ directory
				self::deletePublicImage($file);
			}
		
			deleteRow('files', $fileId);
		}
	}

	//Remove an image from the public/images/ directory
	public static function deletePublicImage($image) {
	
		if (!is_array($image)) {
			$image = getRow('files', array('mime_type', 'short_checksum'), $image);
		}
	
		if ($image
		 && $image['short_checksum']
		 && self::isImageOrSVG($image['mime_type'])) {
			deleteCacheDir(CMS_ROOT. 'public/images/'. $image['short_checksum'], 1);
		}
	}

	public static function addImageDataURIsToDatabase(&$content, $prefix = '', $usage = 'image') {
	
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
				
					if ($fileId = self::addFromString($usage, $data, $filename, $mustBeAnImage = true)) {
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

	public static function isAllowed($file) {
		$type = explode('.', $file);
		$type = $type[count($type) - 1];
	
		return !self::isExecutable($type)
			&& checkRowExists('document_types', array('type' => $type));
	}

	public static function isExecutable($extension) {
		switch (strtolower($extension)) {
			case 'asp':
			case 'bin':
			case 'cgi':
			case 'exe':
			case 'js':
			case 'jsp':
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
				return true;
			default:
				return false;
		}
	}

	public static function contentLink(&$url, $cID, $cType, $cVersion) {
		$url = false;
	
		//Check that this file exists
		if (!($version = getRow('content_item_versions', array('filename', 'file_id'), array('id' => $cID, 'type' => $cType, 'version' => $cVersion)))
		 || !($file = getRow('files', array('mime_type', 'checksum', 'filename', 'location', 'path'), $version['file_id']))) {
			return $url = false;
		}
	
	
		if (adminId()) {
			$onlyForCurrentVisitor = setting('restrict_downloads_by_ip');
			$hash = hash64('admin_'. ($_SESSION['admin_userid'] ?? false). '_'. visitorIP(). '_'. $file['checksum']);
	
		} elseif ($_SESSION['extranetUserID'] ?? false) {
			$onlyForCurrentVisitor = setting('restrict_downloads_by_ip');
			$hash = hash64('user_'. ($_SESSION['extranetUserID'] ?? false). '_'. visitorIP(). '_'. $file['checksum']);
	
		} else {
			$onlyForCurrentVisitor = false;
			$hash = $file['checksum'];
		}
	
		//Check to see if the file is missing
		$path = false;
		if ($file['location'] == 'docstore' && !($path = self::docstorePath($file['path']))) {
			return false;
	
		//Attempt to add/symlink the file in the cache directory
		} elseif ($path && (cleanCacheDir()) && ($dir = createCacheDir($hash, 'downloads', $onlyForCurrentVisitor))) {
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

	public static function copyInDatabase($usage, $existingFileId, $filename = false, $mustBeAnImage = false, $addToDocstoreDirIfPossible = false) {
	
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
				return self::addFromString($usage, $file['data'], ifNull($filename, $file['filename']), $mustBeAnImage, $addToDocstoreDirIfPossible);
		
			} elseif ($file['location'] == 'docstore' && ($location = self::docstorePath($file['path']))) {
				return self::addToDatabase($usage, $location, ifNull($filename, $file['filename']), $mustBeAnImage, $deleteWhenDone = false, $addToDocstoreDirIfPossible = true);
			}
		}
	
		return false;
	}

	public static function docstorePath($fileIdOrPath, $useTmpDir = true, $customDocstorePath = false) {
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
	
		$dir = setting('docstore_dir');
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

	public static function mimeType($file) {
		$parts = explode('.', $file);
		$type = $parts[count($parts) - 1];
	
		//Work on files in cache with upload extension added
		if ($type == 'upload') {
			$type = $parts[count($parts) - 2];
		}
	
		return ifNull(getRow('document_types', 'mime_type', array('type' => strtolower($type))), 'application/octet-stream');
	}

	public static function isImage($mimeType) {
		return $mimeType == 'image/gif'
			|| $mimeType == 'image/jpeg'
			|| $mimeType == 'image/png';
	}

	public static function isImageOrSVG($mimeType) {
		return $mimeType == 'image/gif'
			|| $mimeType == 'image/jpeg'
			|| $mimeType == 'image/png'
			|| $mimeType == 'image/svg+xml';
	}

	public static function getDocumentFrontEndLink($documentId, $privateLink = false) {
		$link = false;
		$document = getRow('documents', array('file_id', 'privacy'), $documentId);
		if ($document) {
			// Create private link
			if ($privateLink || ($document['privacy'] == 'private')) {
				$link = self::createPrivateLink($document['file_id']);
			// Create public link
			} elseif ($document['privacy'] == 'public' && !windowsServer()) {
				$link = self::createPublicLink($document['file_id']);
			// Create link based on content item status and privacy
			} elseif ($document['privacy'] == 'auto') {
				if (\cms_core::$status != 'published') {
					$link = self::createPrivateLink($document['file_id']);
				} else {
					$contentItemPrivacy = getRow('translation_chains', 'privacy', array('equiv_id' => \cms_core::$equivId, 'type' => \cms_core::$cType));
					if (($contentItemPrivacy == 'public') && !windowsServer()) {
						$link = self::createPublicLink($document['file_id']);
					} else {
						$link = self::createPrivateLink($document['file_id']);
						$contentItemPrivacy = 'private';
					}
					updateRow('documents', array('privacy' => $contentItemPrivacy), $documentId);
				}
			} 
		}
		return $link;
	}

	public static function createPublicLink($fileId) {
		$path = self::docstorePath($fileId, false);
		$file = getRow('files', array('short_checksum', 'filename'), $fileId);
	
		$dirPath = 'public' . '/downloads/' . $file['short_checksum'];
		$symFolder =  CMS_ROOT . $dirPath;
		$symPath = $symFolder . '/' . $file['filename'];
		$link = $dirPath . '/' . $file['filename'];
	
		if (!file_exists($symPath)) {
			if(!file_exists($symFolder)) {
				mkdir($symFolder);
			}
			symlink($path, $symPath);
		}
		return $link;
	}

	public static function createPrivateLink($fileId) {
		return self::link($fileId, hash64($fileId. '_'. randomString(10)), 'downloads');
	}

	public static function link($fileId, $hash = false, $type = 'files', $customDocstorePath = false) {
		//Check that this file exists
		if (!$fileId
		 || !($file = getRow('files', array('usage', 'short_checksum', 'checksum', 'filename', 'location', 'path'), $fileId))) {
			return false;
		}
	
		//Workout a hash for the file
		if (!$hash) {
			if (chopPrefixOffString('public/', $type)) {
				$hash = $file['short_checksum'];
			} else {
				$hash = $file['checksum'];
			}
		}
	
		//Try to get a directory in the cache dir
		$path = false;
		if (cleanCacheDir()) {
			$path = createCacheDir($hash, $type, false);
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
		
			} elseif ($pathDS = self::docstorePath($file['path'], true, $customDocstorePath)) {
				$file['data'] = file_get_contents($pathDS);
		
			} else {
				return false;
			}
		
			if (file_put_contents(CMS_ROOT. $path. $file['filename'], $file['data'])) {
				@chmod(CMS_ROOT. $path. $file['filename'], 0666);
				return $path. rawurlencode($file['filename']);
			}
		}
	
		//If we could not use the cache directory, we'll have to link to file.php and load the file from the database each time on the fly.
		return 'zenario/file.php?usage='. $file['usage']. '&c='. $file['checksum']. '&filename='. urlencode($file['filename']);
	}

	//Format a file type for display
	//	function formatFileTypeNicely($type) {}

	public static function guessAltTagFromname($filename) {
		$filename = explode('.', $filename);
		unset($filename[count($filename) - 1]);
		return implode('.', $filename);
	}
	public static function imageLink(
		&$width, &$height, &$url, $fileId, $widthLimit = 0, $heightLimit = 0, $mode = 'resize', $offset = 0,
		$retina = false, $privacy = 'auto',
		$useCacheDir = true, $internalFilePath = false, $returnImageStringIfCacheDirNotWorking = false
	) {
		$url =
		$width = $height =
		$widthOut = $heightOut =
		$newWidth = $newHeight =
		$cropWidth = $cropHeight =
		$cropNewWidth = $cropNewHeight = false;
	
		$widthLimit = (int) $widthLimit;
		$heightLimit = (int) $heightLimit;
	
		//Check the $privacy variable is set to a valid option
		if ($privacy != 'auto'
		 && $privacy != 'public'
		 && $privacy != 'private') {
			return false;
		}
	
		//Check that this file exists, and is actually an image
		if (!$fileId
		 || !($image = getRow(
						'files',
						array(
							'privacy', 'mime_type', 'width', 'height',
							'working_copy_width', 'working_copy_height', 'working_copy_2_width', 'working_copy_2_height',
							'thumbnail_180x130_width', 'thumbnail_180x130_height',
							'thumbnail_64x64_width', 'thumbnail_64x64_height',
							'thumbnail_24x23_width', 'thumbnail_24x23_height',
							'checksum', 'short_checksum', 'filename', 'location', 'path'),
						$fileId))
		 || !(self::isImageOrSVG($image['mime_type']))) {
			return false;
		}
	
		//SVG images do not need to use the retina image logic, as they are always crisp
		if ($isSVG = $image['mime_type'] == 'image/svg+xml') {
			$retina = false;
		}
	
		$imageWidth = (int) $image['width'];
		$imageHeight = (int) $image['height'];
	
		//Special case for the "unlimited, but use a retina image" option
		if ($retina && !$widthLimit && !$heightLimit) {
			$newWidth =
			$cropWidth =
			$cropNewWidth = $imageWidth;
			$newHeight =
			$cropHeight =
			$cropNewHeight = $imageHeight;
		
			$widthOut =
			$widthLimit = (int) ($imageWidth / 2);
			$heightOut =
			$heightLimit = (int) ($imageHeight / 2);
	
		} else {
			//If no limits were set, use the image's own width and height
			if (!$widthLimit) {
				$widthLimit = $imageWidth;
			}
			if (!$heightLimit) {
				$heightLimit = $imageHeight;
			}
	
			//Work out what size the resized image should actually be
			resizeImageByMode(
				$mode, $imageWidth, $imageHeight,
				$widthLimit, $heightLimit,
				$newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight,
				$image['mime_type']);
	
			$widthOut = $cropNewWidth;
			$heightOut = $cropNewHeight;
	
			//Try to use a retina image if requested
			if ($retina
			 && (2 * $newWidth <= $imageWidth)
			 && (2 * $newHeight <= $imageHeight)
			 && (2 * $cropNewWidth <= $imageWidth)
			 && (2 * $cropNewHeight <= $imageHeight)) {
				$newWidth *= 2;
				$newHeight *= 2;
				$cropNewWidth *= 2;
				$cropNewHeight *= 2;
			}
		}
	
		$imageNeedsToBeResized = $imageWidth != $cropNewWidth || $imageHeight != $cropNewHeight;
	
	
		//Check the privacy settings for the image
		//If the image is set to auto, check the settings here
		if ($image['privacy'] == 'auto') {
		
			//If the privacy settings here weren't specified, try to work them out form the current content item
			//(Note that this won't we shouldn't try to do this running from a published content item.)
			if ($privacy == 'auto'
			 && \cms_core::$equivId
			 && \cms_core::$cType
			 && \cms_core::$cVersion
			 && \cms_core::$cVersion == \cms_core::$visitorVersion
			 && ($citemPrivacy = getRow('translation_chains', 'privacy', array('equiv_id' => \cms_core::$equivId, 'type' => \cms_core::$cType)))) {
				if ($citemPrivacy == 'public') {
					$privacy = 'public';
				} else {
					$privacy = 'private';
				}
			}
		
			//If the privacy settings were specified, and the image was set to auto, update the image and to use these settings
			if ($privacy != 'auto') {
				$image['privacy'] = $privacy;
				updateRow('files', array('privacy' => $privacy), $fileId);
			}
		}
	
		//If we couldn't work out the privacy settings for an image, assume for now that it is private,
		//but don't update them in the database
		//if ($image['privacy'] == 'auto') {
		//	$image['privacy'] = 'private';
		//}
	
	
		//Combine the resize options into a string
		$settingCode = $mode. '_'. $widthLimit. '_'. $heightLimit. '_'. $offset;
	
		if ($retina) {
			$settingCode .= '_2';
		}
	
		//If the $useCacheDir variable is set and the public/private directories are writable,
		//try to create this image on the disk
		$path = false;
		if ($useCacheDir && cleanCacheDir()) {
			//If this image should be in the public directory, try to create friendly and logical directory structure
			if ($image['privacy'] == 'public') {
				//We'll try to create a subdirectory inside public/images/ using the short checksum as the name
				$path = createCacheDir($image['short_checksum'], 'public/images', false);
			
				//If this is a resize, we'll put the resize in another subdirectory using the code above as the name
				if ($path && $imageNeedsToBeResized) {
					$path = createCacheDir($image['short_checksum']. '/'. $settingCode, 'public/images', false);
				}
		
			//If the image should be in the private directory, don't worry about a friendly URL and
			//just use the full hash.
			} else {
				//Workout a hash for the image at this size
				$hash = hash64($settingCode. '_'. $image['checksum']);
			
				//Try to get a directory in the cache dir
				$path = createCacheDir($hash, 'images', false);
			}
		}
	
		//Look for the image inside the cache directory
		if ($path && file_exists($path. $image['filename'])) {
		
			//If the image is already available, all we need to do is link to it
			if ($internalFilePath) {
				$url = CMS_ROOT. $path. $image['filename'];
		
			} else {
				$url = absURLIfNeeded(). $path. rawurlencode($image['filename']);
			
				$width = $widthOut;
				$height = $heightOut;
				return true;
			}
		}
	
		//Otherwise, create a resized version now
		if ($path || $returnImageStringIfCacheDirNotWorking) {
		
			//Where an image has multiple sizes stored in the database, get the most suitable size
			if (setting('working_copy_image_threshold')) {
				$wcit = ifNull((int) setting('working_copy_image_threshold'), 66) / 100;
			} else {
				$wcit = 0.66;
			}
		
			foreach (array(
				array('thumbnail_24x23_data', 'thumbnail_24x23_width', 'thumbnail_24x23_height'),
				array('thumbnail_64x64_data', 'thumbnail_64x64_width', 'thumbnail_64x64_height'),
				array('thumbnail_180x130_data', 'thumbnail_180x130_width', 'thumbnail_180x130_height'),
				array('working_copy_data', 'working_copy_width', 'working_copy_height'),
				array('working_copy_2_data', 'working_copy_2_width', 'working_copy_2_height')
			) as $c) {
			
				$xOK = $image[$c[1]] && $newWidth == $image[$c[1]] || ($newWidth < $image[$c[1]] * $wcit);
				$yOK = $image[$c[1]] && $newHeight == $image[$c[2]] || ($newHeight < $image[$c[2]] * $wcit);
			
				if ($mode == 'resize_and_crop' && (($yOK && $cropNewWidth >= $image[$c[1]]) || ($xOK && $cropNewHeight >= $image[$c[2]]))) {
					$xOK = $yOK = true;
				}
			
				if ($xOK && $yOK) {
					$imageWidth = $image[$c[1]];
					$imageHeight = $image[$c[2]];
					$image['data'] = getRow('files', $c[0], $fileId);
				
					//Repeat the call to resizeImageByMode() to resize the thumbnail to the correct size again
					resizeImageByMode(
						$mode, $imageWidth, $imageHeight,
						$widthLimit, $heightLimit,
						$newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight,
						$image['mime_type']);
	
					if ($retina
					 && (2 * $newWidth <= $imageWidth)
					 && (2 * $newHeight <= $imageHeight)
					 && (2 * $cropNewWidth <= $imageWidth)
					 && (2 * $cropNewHeight <= $imageHeight)) {
						$newWidth *= 2;
						$newHeight *= 2;
						$cropNewWidth *= 2;
						$cropNewHeight *= 2;
					}
	
					$imageNeedsToBeResized = $imageWidth != $cropNewWidth || $imageHeight != $cropNewHeight;
					break;
				}
			}
		
			if (empty($image['data'])) {
				if ($image['location'] == 'db') {
					$image['data'] = getRow('files', 'data', $fileId);
			
				} elseif ($pathDS = self::docstorePath($image['path'])) {
					$image['data'] = file_get_contents($pathDS);
			
				} else {
					return false;
				}
			}
		
			if ($imageNeedsToBeResized) {
				resizeImageStringToSize($image['data'], $image['mime_type'], $imageWidth, $imageHeight, $newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight, $offset);
			}
		
			//If $useCacheDir is set, attempt to store the image in the cache directory
			if ($useCacheDir && $path
			 && file_put_contents(CMS_ROOT. $path. $image['filename'], $image['data'])) {
				@chmod(CMS_ROOT. $path. $image['filename'], 0666);
			
				//Try to optimise the image, if the libraries are installed
				self::optimiseImage(CMS_ROOT. $path. $image['filename']);
			
				if ($internalFilePath) {
					$url = CMS_ROOT. $path. $image['filename'];
			
				} else {
					$url = absURLIfNeeded(). $path. rawurlencode($image['filename']);
				}
			
				$width = $widthOut;
				$height = $heightOut;
				return true;
		
			//Otherwise just return the data if $returnImageStringIfCacheDirNotWorking is set
			} elseif ($returnImageStringIfCacheDirNotWorking) {
				return $image['data'];
			}
		}
	
		//If $internalFilePath or $returnImageStringIfCacheDirNotWorking were set then we need to give up at this point.
		if ($internalFilePath || $returnImageStringIfCacheDirNotWorking) {
			return false;
	
		//Otherwise, we'll have to link to file.php and do any resizing needed in there.
		} else {
			//Workout a hash for the image at this size
			$hash = hash64($settingCode. '_'. $image['checksum']);
		
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
		
			$url = 'zenario/file.php?usage=resize&c='. $hash. '&filename='. rawurlencode($image['filename']);
		
			$width = $widthOut;
			$height = $heightOut;
			return true;
		}
	}



	public static function imageLinkArray(
		$imageId, $widthLimit = 0, $heightLimit = 0, $mode = 'resize', $offset = 0,
		$retina = false, $privacy = 'auto', $useCacheDir = true
	) {
		$details = array(
			'alt' => '',
			'src' => '',
			'width' => '',
			'height' => '');
	
		if (self::imageLink(
			$details['width'], $details['height'], $details['src'], $imageId, $widthLimit, $heightLimit, $mode, $offset,
			$retina, $privacy, $useCacheDir
		)) {
			$details['alt'] = getRow('files', 'alt_tag', $imageId);
			return $details;
		}
	
		return false;
	}

	public static function itemStickyImageId($cID, $cType, $cVersion = false) {
		if (!$cVersion) {
			if (checkPriv()) {
				$cVersion = getLatestVersion($cID, $cType);
			} else {
				$cVersion = getPublishedVersion($cID, $cType);
			}
		}
	
		return getRow('content_item_versions', 'feature_image_id', array('id' => $cID, 'type' => $cType, 'version' => $cVersion));
	}

	public static function itemStickyImageLink(
		&$width, &$height, &$url, $cID, $cType, $cVersion = false,
		$widthLimit = 0, $heightLimit = 0, $mode = 'resize', $offset = 0,
		$retina = false, $privacy = 'auto', $useCacheDir = true
	) {
		if ($imageId = self::itemStickyImageId($cID, $cType, $cVersion)) {
			return self::imageLink($width, $height, $url, $imageId, $widthLimit, $heightLimit, $mode, $offset, $retina, $privacy, $useCacheDir);
		}
		return false;
	}

	public static function itemStickyImageLinkArray(
		$cID, $cType, $cVersion = false,
		$widthLimit = 0, $heightLimit = 0, $mode = 'resize', $offset = 0,
		$retina = false, $privacy = 'auto', $useCacheDir = true
	) {
		if ($imageId = self::itemStickyImageId($cID, $cType, $cVersion)) {
			return self::imageLinkArray($imageId, $widthLimit, $heightLimit, $mode, $offset, $retina, $privacy, $useCacheDir);
		}
		return false;
	}

	public static function createPpdfFirstPageScreenshotPng($file) {
		if (file_exists($file) && is_readable($file)) {
			if (self::mimeType($file) == 'application/pdf') {
				if ($programPath = programPathForExec(setting('ghostscript_path'), 'gs')) {
					if ($temp_file = tempnam(sys_get_temp_dir(), 'pdf2png')) {
						$escaped_file = escapeshellarg($file);
						//$jpeg_file = basename($file) . '.jpg';
						$cmd = escapeshellarg($programPath).
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

	public static function addContentItemPdfScreenshotImage($cID, $cType, $cVersion, $file_name, $setAsStickImage=false){
		if($img_file = self::createPpdfFirstPageScreenshotPng($file_name)) {
			$img_base_name = basename($file_name) . '.png';
			$fileId = self::addToDatabase('image', $img_file, $img_base_name, true, true);
			if ($fileId) {
				setRow('inline_images', array(), array(
						'image_id' => $fileId,
						'foreign_key_to' => 'content',
						'foreign_key_id' => $cID, 'foreign_key_char' => $cType, 'foreign_key_version' => $cVersion
					));
				if($setAsStickImage) {
					updateVersion($cID, $cType, $cVersion, array('feature_image_id' => $fileId));
					syncInlineFileContentLink($cID, $cType, $cVersion);
				}
				return true;
			}
		}
		return false;
	}

	public static function plainTextExtract($file, &$extract) {
		$extract = '';
	
		if (file_exists($file) && is_readable($file)) {
			switch (self::mimeType($file)) {
				//.doc
				case 'application/msword':
					if ($programPath = programPathForExec(setting('antiword_path'), 'antiword')) {
						$return_var = false;
						exec(
							escapeshellarg($programPath).
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
						$zip = new \ZipArchive;
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
					if ($programPath = programPathForExec(setting('pdftotext_path'), 'pdftotext')) {
						if ($temp_file = tempnam(sys_get_temp_dir(), 'p2t')) {
							$return_var = $output = false;
							exec(
								escapeshellarg($programPath).
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
										escapeshellarg($programPath).
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
							
								$extract = trim(utf8_encode(mb_ereg_replace('\s+', ' ', str_replace("\xc2\xa0", ' ', $extract))));
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

	public static function updatePlainTextExtract($cID, $cType, $cVersion, $fileId = false) {
		if ($fileId === false) {
			$fileId = getRow('content_item_versions', 'file_id', array('id' => $cID, 'type' => $cType, 'version' => $cVersion));
		}
	
		$success = false;
	
		$extract = array('extract' => '', 'extract_wordcount' => 0);
		if ($fileId && $file = self::docstorePath($fileId)) {
			$success = self::plainTextExtract($file, $extract['extract']);
			$extract['extract_wordcount'] = str_word_count($extract['extract']);
			self::addContentItemPdfScreenshotImage($cID, $cType, $cVersion, $file, true);
		}
	
		setRow('content_cache', $extract, array('content_id' => $cID, 'content_type' => $cType, 'content_version' => $cVersion));
	
		return $success;
	}

	public static function updateDocumentPlainTextExtract($fileId, &$extract, &$imgFileId) {
		$errors = array();
		$extract = array('extract' => '', 'extract_wordcount' => 0);
	
		$filePath = self::docstorePath($fileId);
	
		self::plainTextExtract($filePath, $extract['extract']);
		$extract['extract_wordcount'] = str_word_count($extract['extract']);
		
		$mime = self::mimeType($filePath);
		switch ($mime) {
			case 'application/pdf':
				if ($imgFile = self::createPpdfFirstPageScreenshotPng($filePath)) {
					$imgBaseName = basename($filePath) . '.png';
					$imgFileId = self::addToDatabase('documents', $imgFile, $imgBaseName, true, true);
				}
				break;
			case 'image/jpeg':
			case 'image/png':
			case 'image/gif':
				$imageThumbnailWidth = 300;
				$imageThumbnailHeight = 300;
				$size = getimagesize($filePath);
			
				if ($size && $size[0] > $imageThumbnailWidth && $size[1] > $imageThumbnailHeight) {
					$width = $height = $url = false;
					self::imageLink($width, $height, $url, $fileId, $imageThumbnailWidth, $imageThumbnailHeight, 'resize', 0, false, 'auto', true, $internalFilePath = true);
					$imgFileId = self::addToDocstoreDir('document_thumbnail', $url);
				} 
				break;
		}
	}

	public static function safeName($filename, $strict = false) {
		if ($strict) {
			$filename = preg_replace('@[^\w\.-]@', '', $filename);
		} else {
			$filename = str_replace(array('/', '\\'), '', $filename);
		}
		if ($filename === '') {
			$filename = '_';
		}
		if ($filename[0] === '.') {
			$filename[0] = '_';
		}
		return $filename;
	}

	public static function getPathOfUploadedInCacheDir($string) {
		$details = explode('/', decodeItemIdForOrganizer($string), 3);
	
		if (!empty($details[1])
		 && file_exists($filepath = CMS_ROOT. 'private/uploads/'. preg_replace('@[^\w-]@', '', $details[0]). '/'. self::safeName($details[1]))) {
			return $filepath;
		} else {
			return false;
		}
	}

	public static function fileSizeConvert($bytes) {
		$bytes = floatval($bytes);
			$arBytes = array(
				0 => array(
					"UNIT" => "TB",
					"VALUE" => pow(1024, 4)
				),
				1 => array(
					"UNIT" => "GB",
					"VALUE" => pow(1024, 3)
				),
				2 => array(
					"UNIT" => "MB",
					"VALUE" => pow(1024, 2)
				),
				3 => array(
					"UNIT" => "KB",
					"VALUE" => 1024
				),
				4 => array(
					"UNIT" => "bytes",
					"VALUE" => 1
				),
			);
	
		foreach($arBytes as $arItem) {
			if($bytes >= $arItem["VALUE"]) {
				$result = $bytes / $arItem["VALUE"];
				$result = strval(round($result, 2)). " " .$arItem["UNIT"];
				break;
			}
		}
		return $result;
	}
	
}