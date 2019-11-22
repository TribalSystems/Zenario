<?php
/*
 * Copyright (c) 2019, Tribal Limited
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

	//Formerly "trackFileDownload()"
	public static function trackDownload($url) {
		return "if (window.ga) ga('send', 'pageview', {'page' : '".\ze\escape::js($url)."'});";
	}

	//Formerly "addFileToDocstoreDir()"
	public static function addToDocstoreDir($usage, $location, $filename = false, $mustBeAnImage = false, $deleteWhenDone = true) {
		return self::addToDatabase($usage, $location, $filename, $mustBeAnImage, $deleteWhenDone, true);
	}

	//Formerly "addFileFromString()"
	public static function addFromString($usage, &$contents, $filename, $mustBeAnImage = false, $addToDocstoreDirIfPossible = false) {
	
		if ($temp_file = tempnam(sys_get_temp_dir(), 'cpy')) {
			if (file_put_contents($temp_file, $contents)) {
				return self::addToDatabase($usage, $temp_file, $filename, $mustBeAnImage, true, $addToDocstoreDirIfPossible);
			}
		}
	
		return false;
	}
	
	
	//Attempt to check if the contents of a file match the file
	public static function check($filepath, $mimeType = null) {
		
		if ($mimeType === null) {
			$mimeType = \ze\file::mimeType($filepath);
		}
		
		$check = true;
		\ze::ignoreErrors();
		
			if (\ze\file::isImageOrSVG($mimeType)) {
				if (\ze\file::isImage($mimeType)) {
					$check = (bool) getimagesize($filepath);
			
				} else {
					if (function_exists('simplexml_load_string')) {
						$check = (bool) simplexml_load_string(file_get_contents($filepath));
					}
				}
			
			} elseif ($mimeType == 'application/zip'  || substr($mimeType, 0, 45) == 'application/vnd.openxmlformats-officedocument') {
				if (class_exists('ZipArchive')) {
					$zip = new \ZipArchive;
					$check = ($zip->open($filepath)) && ($zip->numFiles);
				}
			}
		\ze::noteErrors();
		
		return $check;
	}

	//Formerly "addFileToDatabase()"
	public static function addToDatabase($usage, $location, $filename = false, $mustBeAnImage = false, $deleteWhenDone = false, $addToDocstoreDirIfPossible = false, $imageAltTag = false, $imageTitle = false, $imagePopoutTitle = false) {
		
		//Add some logic to handle any old links to email/inline/menu images (these are now just classed as "image"s).
		if ($usage == 'email'
		 || $usage == 'inline'
		 || $usage == 'menu') {
			$usage = 'image';
		}


		$file = [];

		if (!is_readable($location)
		 || !is_file($location)
		 || !($file['size'] = filesize($location))
		 || !($file['checksum'] = md5_file($location))
		 || !($file['checksum'] = \ze::base16To64($file['checksum']))) {
			return false;
		}
		$basename =  basename($location);
		if ($filename === false) {
			$filename = preg_replace('/([^.a-z0-9\s]+)/i', '-', $basename);
		}

		$file['filename'] = $filename;
		$file['mime_type'] = \ze\file::mimeType($filename);
		$file['usage'] = $usage;

		if ($mustBeAnImage && !\ze\file::isImageOrSVG($file['mime_type'])) {
			return false;
		}

		//Check if this file exists in the system already
		$key = ['checksum' => $file['checksum'], 'usage' => $file['usage']];
		if ($existingFile = \ze\row::get('files', ['id', 'filename', 'location', 'path'], $key)) {
			$key = $existingFile['id'];
	
			//If this file is stored in the database, continue running this function to move it to the docstore dir
			if (!($addToDocstoreDirIfPossible && $existingFile['location'] == 'db')) {
		
				//If this file is already stored, just update the name and remove the 'archived' flag if it was set
				$path = false;
				if ($existingFile['location'] == 'db' || ($path = \ze\file::docstorePath($existingFile['path']))) {
					//If the name has changed, attempt to rename the file in the filesystem
					/*if ($path && $file['filename'] != $existingFile['filename']) {
						@rename($path, \ze::setting('docstore_dir'). '/'. $existingFile['path']. '/'. $file['filename']);
					}
			
					*/
					\ze\row::update('files', ['filename' => $filename, 'archived' => 0], $key);
					if ($deleteWhenDone) {
						unlink($location);
					}
			
					return $existingFile['id'];
				}
			}
		
		//Otherwise we must insert the new file
		} else {
			$file['privacy'] = \ze::oneOf(\ze::setting('default_image_privacy'), 'auto', 'public', 'private');
		}



		//Check if the file is an image and get its meta info
		//(Note this logic is the same as in ze\file::check(), except it also saves the meta info.)
		if (\ze\file::isImageOrSVG($file['mime_type'])) {
	
			if (\ze\file::isImage($file['mime_type'])) {
				
				\ze::ignoreErrors();
					$image = getimagesize($location);
				\ze::noteErrors();
				
				if ($image === false) {
					return false;
				}	
				$file['width'] = $image[0];
				$file['height'] = $image[1];
				$file['mime_type'] = $image['mime'];
		
				//Create resizes for the image as needed.
				//Working copies should only be created if they are enabled, and the image is big enough to need them.
				//Organizer thumbnails should always be created, even if the image needs to be scaled up
				foreach ([
					['working_copy_data', 'working_copy_width', 'working_copy_height', \ze::setting('working_copy_image_size'), \ze::setting('working_copy_image_size'), false],
					['working_copy_2_data', 'working_copy_2_width', 'working_copy_2_height', \ze::setting('thumbnail_wc_image_size'), \ze::setting('thumbnail_wc_image_size'), false],
					['thumbnail_180x130_data', 'thumbnail_180x130_width', 'thumbnail_180x130_height', 180, 130, true]
				] as $c) {
					if ($c[3] && $c[4] && ($c[5] || ($file['width'] > $c[3] || $file['height'] > $c[4]))) {
						$file[$c[1]] = $image[0];
						$file[$c[2]] = $image[1];
						$file[$c[0]] = file_get_contents($location);
						\ze\file::resizeImageString($file[$c[0]], $file['mime_type'], $file[$c[1]], $file[$c[2]], $c[3], $c[4]);
					}
				}
	
			} else
			if (function_exists('simplexml_load_string')) {
				\ze::ignoreErrors();
					$svg = simplexml_load_string(file_get_contents($location));
				\ze::noteErrors();
				
				if ($svg) {
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
				} else {
					return false;
				}
			}
	
	
			$filenameArray = explode('.', $filename);
			$altTag = trim(preg_replace('/[^a-z0-9]+/i', ' ', $filenameArray[0]));
			$file['alt_tag'] = $altTag;
		
		//For non images, just run ze\file::check() without any specific tweaks.
		} else {
			if (!\ze\file::check($location, $file['mime_type'])) {
				return false;
			}
		}


		$file['archived'] = 0;
		$file['created_datetime'] = \ze\date::now();
		
		$dir = $path = false;
		if ($addToDocstoreDirIfPossible
		 && self::createDocstoreDir($file['filename'], $file['checksum'], $dir, $path)) {
	
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

		$fileId = \ze\row::set('files', $file, $key);
		\ze\fileAdm::updateShortChecksums();
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
			$jpeg_quality_limit = (int) \ze::setting('jpeg_quality_limit');
			if (80 <= $jpeg_quality_limit && $jpeg_quality_limit <= 100) {
				self::$options['jpegoptim_options'][2] = '--max='. $jpeg_quality_limit;
			}
		
			foreach (['advpng', 'jpegoptim', 'jpegtran', 'optipng'] as $program) {
				self::$options[$program. '_bin'] =
					\ze\server::programPathForExec(\ze::setting($program. '_path'), $program, true);
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

	//Formerly "optimizeImage()"
	public static function optimizeImage($path) {
		return self::optimiseImage($path);
	}
	//Formerly "optimiseImage()"
	public static function optimiseImage($path) {
	
		if (!\ze::in(self::mimeType($path), 'image/png', 'image/jpeg')
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
	//Formerly "deleteFile()"
	public static function delete($fileId) {
	
		if ($file = \ze\row::get('files', ['path', 'mime_type', 'short_checksum'], $fileId)) {
	
			//If the file was being stored in the docstore and nothing else uses it...
			if ($file['path']
			 && !\ze\row::exists('files', ['id' => ['!' => $fileId], 'path' => $file['path']])) {
				//...then delete that directory from the docstore
				\ze\cache::deleteDir(\ze::setting('docstore_dir'). '/'. $file['path']);
			}
		
			//If the file was an image and there's no other copies with a different usage
			if (self::isImageOrSVG($file['mime_type'])
			 && !\ze\row::exists('files', ['id' => ['!' => $fileId], 'short_checksum' => $file['short_checksum']])) {
				//...then delete it from the public/images/ directory
				self::deletePublicImage($file);
			}
		
			\ze\row::delete('files', $fileId);
		}
	}

	//Remove an image from the public/images/ directory
	//Formerly "deletePublicImage()"
	public static function deletePublicImage($image) {
	
		if (!is_array($image)) {
			$image = \ze\row::get('files', ['mime_type', 'short_checksum'], $image);
		}
	
		if ($image
		 && $image['short_checksum']
		 && self::isImageOrSVG($image['mime_type'])) {
			\ze\cache::deleteDir(CMS_ROOT. 'public/images/'. $image['short_checksum'], 1);
		}
	}

	//Formerly "addImageDataURIsToDatabase()"
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
					$sql = "SELECT IFNULL(MAX(id), 0) + 1 FROM ". DB_PREFIX. "files";
					$result = \ze\sql::select($sql);
					$row = \ze\sql::fetchRow($result);
					$filename = 'image_'. $row[0]. '.'. $ext;
				
					$data = base64_decode($data);
				
					if ($fileId = self::addFromString($usage, $data, $filename, $mustBeAnImage = true)) {
						if ($checksum = \ze\row::get('files', 'checksum', $fileId)) {
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

	//Formerly "checkDocumentTypeIsAllowed()"
	public static function isAllowed($file) {
		$type = explode('.', $file);
		$type = $type[count($type) - 1];
	
		return !self::isExecutable($type)
			&& \ze\row::exists('document_types', ['type' => $type]);
	}

	//Formerly "checkDocumentTypeIsExecutable()"
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

	//Formerly "contentFileLink()"
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
		if ($file['location'] == 'docstore' && !($path = self::docstorePath($file['path']))) {
			return false;
	
		//Attempt to add/symlink the file in the cache directory
		} elseif ($path && (\ze\cache::cleanDirs()) && ($dir = \ze\cache::createDir($hash, 'downloads', $onlyForCurrentVisitor))) {
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
	
		return true;
	}

	//Formerly "copyFileInDatabase()"
	public static function copyInDatabase($usage, $existingFileId, $filename = false, $mustBeAnImage = false, $addToDocstoreDirIfPossible = false) {
	
		//Add some logic to handle any old links to email/inline/menu images (these are now just classed as "image"s).
		if ($usage == 'email'
		 || $usage == 'inline'
		 || $usage == 'menu') {
			$usage = 'image';
		}
	
		if ($file = \ze\row::get('files', ['usage', 'filename', 'location', 'data', 'path'], ['id' => $existingFileId])) {
			if ($file['usage'] == $usage) {
				return $existingFileId;
		
			} elseif ($file['location'] == 'db') {
				return self::addFromString($usage, $file['data'], ($filename ?: $file['filename']), $mustBeAnImage, $addToDocstoreDirIfPossible);
		
			} elseif ($file['location'] == 'docstore' && ($location = self::docstorePath($file['path']))) {
				return self::addToDatabase($usage, $location, ($filename ?: $file['filename']), $mustBeAnImage, $deleteWhenDone = false, $addToDocstoreDirIfPossible = true);
			}
		}
	
		return false;
	}

	//Formerly "docstoreFilePath()"
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
			header('Content-Disposition: attachment; filename="'. urlencode($filename). '"');
			
			\ze\cache::end();
			if ($file['location'] == 'docstore') {
				readfile(self::docstorePath($file['path']));
			} else {
				echo $file['data'];
			}
		}
	}

	//Formerly "documentMimeType()"
	public static function mimeType($file) {
		$parts = explode('.', $file);
		$type = $parts[count($parts) - 1];
	
		//Work on files in cache with upload extension added
		if ($type == 'upload') {
			$type = $parts[count($parts) - 2];
		}
	
		return \ze\row::get('document_types', 'mime_type', ['type' => strtolower($type)]) ?: 'application/octet-stream';
	}

	//Formerly "isImage()"
	public static function isImage($mimeType) {
		return $mimeType == 'image/gif'
			|| $mimeType == 'image/jpeg'
			|| $mimeType == 'image/png';
	}

	//Formerly "isImageOrSVG()"
	public static function isImageOrSVG($mimeType) {
		return $mimeType == 'image/gif'
			|| $mimeType == 'image/jpeg'
			|| $mimeType == 'image/png'
			|| $mimeType == 'image/svg+xml';
	}

	//Formerly "getDocumentFrontEndLink()"
	public static function getDocumentFrontEndLink($documentId, $privateLink = false) {
		$link = false;
		$document = \ze\row::get('documents', ['file_id', 'filename', 'privacy'], $documentId);
		if ($document) {
			// Create private link
			if ($privateLink || ($document['privacy'] == 'private')) {
				$link = self::createPrivateLink($document['file_id'], $document['filename']);
			// Create public link
			} elseif ($document['privacy'] == 'public' && !\ze\server::isWindows()) {
				$link = self::createPublicLink($document['file_id'], $document['filename']);
			// Create link based on content item status and privacy
			}
		}
		return $link;
	}

	//Formerly "createFilePublicLink()"
	public static function createPublicLink($fileId, $filename = false) {
		$path = self::docstorePath($fileId, false);
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

	//Formerly "createPrivateLink()"
	public static function createPrivateLink($fileId, $filename = false) {
		return self::link($fileId, \ze::hash64($fileId. '_'. \ze\ring::random(10)), 'downloads', false, $filename);
	}
	
	//Produce a label for a file in the standard format
	public static function labelDetails($fileId) {
		
		$sql = '
			SELECT id, filename, width, height, checksum, short_checksum, `usage`
			FROM '. DB_PREFIX. 'files
			WHERE id = '. (int) $fileId;
		
		if ($file = \ze\sql::fetchAssoc($sql)) {
			
			$file['label'] = $file['filename'];
			
			if (\ze::isAdmin()) {
				$sql = '
					SELECT 1
					FROM '. DB_PREFIX. 'files
					WHERE `usage` = \''. \ze\escape::sql($file['usage']). '\'
					  AND filename = \''. \ze\escape::sql($file['filename']). '\'
					  AND short_checksum != \''. \ze\escape::sql($file['short_checksum']). '\'
					LIMIT 1';
			
				if ($file['ssc'] = (bool) \ze\sql::fetchRow($sql)) {
					$file['label'] .= ' '. \ze\admin::phrase('[checksum [[short_checksum]]]', $file);
				}
			}
		
			if ($file['width'] && $file['height']) {
				$file['label'] .= ' ['. $file['width']. ' × '. $file['height']. 'px]';
			}
		}
		
		return $file;
	}

	//Formerly "fileLink()"
	public static function link($fileId, $hash = false, $type = 'files', $customDocstorePath = false, $filename = false) {
		//Check that this file exists
		if (!$fileId
		 || !($file = \ze\row::get('files', ['usage', 'short_checksum', 'checksum', 'filename', 'location', 'path'], $fileId))) {
			return false;
		}
		
		if ($filename === false) {
			$filename = $file['filename'];
		}
	
		//Workout a hash for the file
		if (!$hash) {
			if (\ze\ring::chopPrefix('public/', $type)) {
				$hash = $file['short_checksum'];
			} else {
				$hash = $file['checksum'];
			}
		}
	
		//Try to get a directory in the cache dir
		$path = false;
		if (\ze\cache::cleanDirs()) {
			$path = \ze\cache::createDir($hash, $type, false);
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
		
			} elseif ($pathDS = self::docstorePath($file['path'], true, $customDocstorePath)) {
				
				\ze\server::symlinkOrCopy($pathDS, CMS_ROOT. $path. $filename, 0666);
		
			} else {
				return false;
			}
		
			return $path. rawurlencode($filename);
		}
	
		//If we could not use the cache directory, we'll have to link to file.php and load the file from the database each time on the fly.
		return 'zenario/file.php?usage='. $file['usage']. '&c='. $file['checksum']. '&filename='. urlencode($filename);
	}

	//Formerly "guessAltTagFromFilename()"
	public static function guessAltTagFromname($filename) {
		$filename = explode('.', $filename);
		unset($filename[count($filename) - 1]);
		return implode('.', $filename);
	}
	//Formerly "imageLink()"
	public static function imageLink(
		&$width, &$height, &$url, $fileId, $widthLimit = 0, $heightLimit = 0, $mode = 'resize', $offset = 0,
		$retina = false, $fullPath = false, $privacy = 'auto',
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
		 || !($image = \ze\row::get(
						'files',
						[
							'privacy', 'mime_type', 'width', 'height',
							'working_copy_width', 'working_copy_height', 'working_copy_2_width', 'working_copy_2_height',
							'thumbnail_180x130_width', 'thumbnail_180x130_height',
							'checksum', 'short_checksum', 'filename', 'location', 'path'],
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
			\ze\file::resizeImageByMode(
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
			 && \ze::$equivId
			 && \ze::$cType
			 && \ze::$cVersion
			 && \ze::$cVersion == \ze::$visitorVersion
			 && ($citemPrivacy = \ze\row::get('translation_chains', 'privacy', ['equiv_id' => \ze::$equivId, 'type' => \ze::$cType]))) {
				if ($citemPrivacy == 'public') {
					$privacy = 'public';
				} else {
					$privacy = 'private';
				}
			}
		
			//If the privacy settings were specified, and the image was set to auto, update the image and to use these settings
			if ($privacy != 'auto') {
				$image['privacy'] = $privacy;
				\ze\row::update('files', ['privacy' => $privacy], $fileId);
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
		if ($useCacheDir && \ze\cache::cleanDirs()) {
			//If this image should be in the public directory, try to create friendly and logical directory structure
			if ($image['privacy'] == 'public') {
				//We'll try to create a subdirectory inside public/images/ using the short checksum as the name
				$path = \ze\cache::createDir($image['short_checksum'], 'public/images', false);
			
				//If this is a resize, we'll put the resize in another subdirectory using the code above as the name
				if ($path && $imageNeedsToBeResized) {
					$path = \ze\cache::createDir($image['short_checksum']. '/'. $settingCode, 'public/images', false);
				}
		
			//If the image should be in the private directory, don't worry about a friendly URL and
			//just use the full hash.
			} else {
				//Workout a hash for the image at this size
				$hash = \ze::hash64($settingCode. '_'. $image['checksum']);
			
				//Try to get a directory in the cache dir
				$path = \ze\cache::createDir($hash, 'images', false);
			}
		}
		
		$safeName = \ze\file::safeName($image['filename']);
		$filepath = CMS_ROOT. $path. $safeName;
		
		//Look for the image inside the cache directory
		if ($path && file_exists($filepath)) {
		
			//If the image is already available, all we need to do is link to it
			if ($internalFilePath) {
				$url = $filepath;
		
			} else {
				if ($fullPath) {
					$url = \ze\link::absolute();
				} else {
					$url = \ze\link::absoluteIfNeeded();
				}
				$url .= $path. rawurlencode($safeName);
			
				$width = $widthOut;
				$height = $heightOut;
				return true;
			}
		}
	
		//Otherwise, create a resized version now
		if ($path || $returnImageStringIfCacheDirNotWorking) {
		
			//Where an image has multiple sizes stored in the database, get the most suitable size
			if (\ze::setting('working_copy_image_threshold')) {
				$wcit = ((int) \ze::setting('working_copy_image_threshold') ?: 66) / 100;
			} else {
				$wcit = 0.66;
			}
		
			foreach ([
				['thumbnail_180x130_data', 'thumbnail_180x130_width', 'thumbnail_180x130_height'],
				['working_copy_data', 'working_copy_width', 'working_copy_height'],
				['working_copy_2_data', 'working_copy_2_width', 'working_copy_2_height']
			] as $c) {
			
				$xOK = $image[$c[1]] && $newWidth == $image[$c[1]] || ($newWidth < $image[$c[1]] * $wcit);
				$yOK = $image[$c[1]] && $newHeight == $image[$c[2]] || ($newHeight < $image[$c[2]] * $wcit);
			
				if ($mode == 'resize_and_crop' && (($yOK && $cropNewWidth >= $image[$c[1]]) || ($xOK && $cropNewHeight >= $image[$c[2]]))) {
					$xOK = $yOK = true;
				}
			
				if ($xOK && $yOK) {
					$imageWidth = $image[$c[1]];
					$imageHeight = $image[$c[2]];
					$image['data'] = \ze\row::get('files', $c[0], $fileId);
				
					//Repeat the call to \ze\file::resizeImageByMode() to resize the thumbnail to the correct size again
					\ze\file::resizeImageByMode(
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
					$image['data'] = \ze\row::get('files', 'data', $fileId);
			
				} elseif ($pathDS = self::docstorePath($image['path'])) {
					$image['data'] = file_get_contents($pathDS);
			
				} else {
					return false;
				}
			}
		
			if ($imageNeedsToBeResized) {
				\ze\file::resizeImageStringToSize($image['data'], $image['mime_type'], $imageWidth, $imageHeight, $newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight, $offset);
			}
			
			//If $useCacheDir is set, attempt to store the image in the cache directory
			if ($useCacheDir && $path
			 && file_put_contents($filepath, $image['data'])) {
				\ze\cache::chmod($filepath, 0666);
			
				//Try to optimise the image, if the libraries are installed
				self::optimiseImage($filepath);
			
				if ($internalFilePath) {
					$url = $filepath;
			
				} else {
					if ($fullPath) {
						$url = \ze\link::absolute();
					} else {
						$url = \ze\link::absoluteIfNeeded();
					}
					$url .= $path. rawurlencode($safeName);
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
			$hash = \ze::hash64($settingCode. '_'. $image['checksum']);
		
			//Note that using the session for each image is quite slow, so it's better to make sure that your cache/ directory is writable
			//and not use this fallback logic!
			if (!isset($_SESSION['zenario_allowed_files'])) {
				$_SESSION['zenario_allowed_files'] = [];
			}
		
			$_SESSION['zenario_allowed_files'][$hash] =
				[
					'width' => $widthLimit, 'height' => $heightLimit,
					'mode' => $mode, 'offset' => $offset,
					'id' => $fileId, 'useCacheDir' => $useCacheDir];
		
			$url = 'zenario/file.php?usage=resize&c='. $hash. ($retina? '&retina=1' : ''). '&filename='. rawurlencode($safeName);
		
			$width = $widthOut;
			$height = $heightOut;
			return true;
		}
	}



	const imageLinkArrayFromTwig = true;
	//Formerly "imageLinkArray()"
	public static function imageLinkArray(
		$imageId, $widthLimit = 0, $heightLimit = 0, $mode = 'resize', $offset = 0,
		$retina = false, $fullPath = false, $privacy = 'auto', $useCacheDir = true
	) {
		$details = [
			'alt' => '',
			'src' => '',
			'width' => '',
			'height' => ''];
	
		if (self::imageLink(
			$details['width'], $details['height'], $details['src'], $imageId, $widthLimit, $heightLimit, $mode, $offset,
			$retina, $fullPath, $privacy, $useCacheDir
		)) {
			$details['alt'] = \ze\row::get('files', 'alt_tag', $imageId);
			return $details;
		}
	
		return false;
	}

	//Formerly "itemStickyImageId()"
	public static function itemStickyImageId($cID, $cType, $cVersion = false) {
		if (!$cVersion) {
			if (\ze\priv::check()) {
				$cVersion = \ze\content::latestVersion($cID, $cType);
			} else {
				$cVersion = \ze\content::publishedVersion($cID, $cType);
			}
		}
	
		return \ze\row::get('content_item_versions', 'feature_image_id', ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
	}

	//Formerly "itemStickyImageLink()"
	public static function itemStickyImageLink(
		&$width, &$height, &$url, $cID, $cType, $cVersion = false,
		$widthLimit = 0, $heightLimit = 0, $mode = 'resize', $offset = 0,
		$retina = false, $fullPath = false, $privacy = 'auto', $useCacheDir = true
	) {
		if ($imageId = self::itemStickyImageId($cID, $cType, $cVersion)) {
			return self::imageLink($width, $height, $url, $imageId, $widthLimit, $heightLimit, $mode, $offset, $retina, $fullPath, $privacy, $useCacheDir);
		}
		return false;
	}

	//Formerly "itemStickyImageLinkArray()"
	public static function itemStickyImageLinkArray(
		$cID, $cType, $cVersion = false,
		$widthLimit = 0, $heightLimit = 0, $mode = 'resize', $offset = 0,
		$retina = false, $fullPath = false, $privacy = 'auto', $useCacheDir = true
	) {
		if ($imageId = self::itemStickyImageId($cID, $cType, $cVersion)) {
			return self::imageLinkArray($imageId, $widthLimit, $heightLimit, $mode, $offset, $retina, $fullPath, $privacy, $useCacheDir);
		}
		return false;
	}

	//Formerly "createPpdfFirstPageScreenshotPng()"
	public static function createPpdfFirstPageScreenshotPng($file) {
		if (file_exists($file) && is_readable($file)) {
			if (self::mimeType($file) == 'application/pdf') {
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

	//Formerly "addContentItemPdfScreenshotImage()"
	public static function addContentItemPdfScreenshotImage($cID, $cType, $cVersion, $file_name, $setAsStickImage=false){
		if($img_file = self::createPpdfFirstPageScreenshotPng($file_name)) {
			$img_base_name = basename($file_name) . '.png';
			$fileId = self::addToDatabase('image', $img_file, $img_base_name, true, true);
			if ($fileId) {
				\ze\row::set('inline_images', [], [
						'image_id' => $fileId,
						'foreign_key_to' => 'content',
						'foreign_key_id' => $cID, 'foreign_key_char' => $cType, 'foreign_key_version' => $cVersion
					]);
				if($setAsStickImage) {
					\ze\contentAdm::updateVersion($cID, $cType, $cVersion, ['feature_image_id' => $fileId]);
					\ze\contentAdm::syncInlineFileContentLink($cID, $cType, $cVersion);
				}
				return true;
			}
		}
		return false;
	}

	//Formerly "plainTextExtract()"
	public static function plainTextExtract($file, &$extract) {
		$extract = '';
	
		if (file_exists($file) && is_readable($file)) {
			switch (self::mimeType($file)) {
				//.doc
				case 'application/msword':
					if ($programPath = \ze\server::programPathForExec(\ze::setting('antiword_path'), 'antiword')) {
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
					if ($programPath = \ze\server::programPathForExec(\ze::setting('pdftotext_path'), 'pdftotext')) {
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

	//Formerly "updatePlainTextExtract()"
	public static function updatePlainTextExtract($cID, $cType, $cVersion, $fileId = false) {
		if ($fileId === false) {
			$fileId = \ze\row::get('content_item_versions', 'file_id', ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
		}
	
		$success = false;
	
		$extract = ['extract' => '', 'extract_wordcount' => 0];
		if ($fileId && $file = self::docstorePath($fileId)) {
			$success = self::plainTextExtract($file, $extract['extract']);
			$extract['extract_wordcount'] = str_word_count($extract['extract']);
			self::addContentItemPdfScreenshotImage($cID, $cType, $cVersion, $file, true);
		}
	
		\ze\row::set('content_cache', $extract, ['content_id' => $cID, 'content_type' => $cType, 'content_version' => $cVersion]);
	
		return $success;
	}

	//Formerly "updateDocumentPlainTextExtract()"
	public static function updateDocumentPlainTextExtract($fileId, &$extract, &$imgFileId) {
		$errors = [];
		$extract = ['extract' => '', 'extract_wordcount' => 0];
	
		$filePath = self::docstorePath($fileId);
	
		self::plainTextExtract($filePath, $extract['extract']);
		$extract['extract_wordcount'] = str_word_count($extract['extract']);
		
		$mime = \ze\row::get('files', 'mime_type', $fileId);
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
					self::imageLink($width, $height, $url, $fileId, $imageThumbnailWidth, $imageThumbnailHeight, 'resize', 0, false, false, 'auto', true, $internalFilePath = true);
					$imgFileId = self::addToDocstoreDir('document_thumbnail', $url);
				} 
				break;
		}
	}

	//Formerly "safeFileName()"
	public static function safeName($filename, $strict = false) {
		if ($strict) {
			$filename = preg_replace('@[^\w\.-]@', '', $filename);
		} else {
			$filename = str_replace(['/', '\\', ':', ';', '*', '?', '"', '<', '>', '|'], '', $filename);
		}
		if ($filename === '') {
			$filename = '_';
		}
		if ($filename[0] === '.') {
			$filename[0] = '_';
		}
		return $filename;
	}

	//Formerly "getPathOfUploadedFileInCacheDir()"
	public static function getPathOfUploadInCacheDir($string) {
		$details = explode('/', \ze\ring::decodeIdForOrganizer($string), 3);
	
		if (!empty($details[1])
		 && file_exists($filepath = CMS_ROOT. 'private/uploads/'. preg_replace('@[^\w-]@', '', $details[0]). '/'. self::safeName($details[1]))) {
			return $filepath;
		} else {
			return false;
		}
	}

	//Formerly "fileSizeConvert()"
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



	//Given an image size and a target size, resize the image (maintaining aspect ratio).
	//Formerly "resizeImage()"
	public static function resizeImage($imageWidth, $imageHeight, $constraint_width, $constraint_height, &$width_out, &$height_out, $allowUpscale = false) {
		$width_out = $imageWidth;
		$height_out = $imageHeight;
	
		if ($imageWidth == $constraint_width && $imageHeight == $constraint_height) {
			return;
		}
	
		if (!$allowUpscale && ($imageWidth <= $constraint_width) && ($imageHeight <= $constraint_height)) {
			return;
		}

		if (($constraint_width / $imageWidth) < ($constraint_height / $imageHeight)) {
			$width_out = $constraint_width;
			$height_out = (int) ($imageHeight * $constraint_width / $imageWidth);
		} else {
			$height_out = $constraint_height;
			$width_out = (int) ($imageWidth * $constraint_height / $imageHeight);
		}

		return;
	}

	//Given an image size and a target size, resize the image by different conditions and return the values used in the calculations
	//Formerly "resizeImageByMode()"
	public static function resizeImageByMode(
		&$mode, $imageWidth, $imageHeight, $maxWidth, $maxHeight,
		&$newWidth, &$newHeight, &$cropWidth, &$cropHeight, &$cropNewWidth, &$cropNewHeight,
		$mimeType = ''
	) {
	
		$maxWidth = (int) $maxWidth;
		$maxHeight = (int) $maxHeight;
		$allowUpscale = $mimeType == 'image/svg+xml';
	
		if ($mode == 'unlimited') {
			$cropNewWidth = $cropWidth = $newWidth = $imageWidth;
			$cropNewHeight = $cropHeight = $newHeight = $imageHeight;
	
		} elseif ($mode == 'stretch') {
			$allowUpscale = true;
			$cropWidth = $imageWidth;
			$cropHeight = $imageHeight;
			$cropNewWidth = $newWidth = $maxWidth;
			$cropNewHeight = $newHeight = $maxHeight;
	
		} elseif ($mode == 'resize_and_crop') {
		
			if (($maxWidth / $imageWidth) < ($maxHeight / $imageHeight)) {
				$newWidth = (int) ($imageWidth * $maxHeight / $imageHeight);
				$newHeight = $maxHeight;
				$cropWidth = (int) ($maxWidth * $imageHeight / $maxHeight);
				$cropHeight = $imageHeight;
				$cropNewWidth = $maxWidth;
				$cropNewHeight = $maxHeight;
		
			} else {
				$newWidth = $maxWidth;
				$newHeight = (int) ($imageHeight * $maxWidth / $imageWidth);
				$cropWidth = $imageWidth;
				$cropHeight = (int) ($maxHeight * $imageWidth / $maxWidth);
				$cropNewWidth = $maxWidth;
				$cropNewHeight = $maxHeight;
			}
	
		} elseif ($mode == 'fixed_width') {
			$maxHeight = $allowUpscale? 999999 : $imageHeight;
			$mode = 'resize';
	
		} elseif ($mode == 'fixed_height') {
			$maxWidth = $allowUpscale? 999999 : $imageWidth;
			$mode = 'resize';
	
		} else {
			$mode = 'resize';
		}
	
		if ($mode == 'resize') {
			$newWidth = false;
			$newHeight = false;
			\ze\file::resizeImage($imageWidth, $imageHeight, $maxWidth, $maxHeight, $newWidth, $newHeight, $allowUpscale);
			$cropWidth = $imageWidth;
			$cropHeight = $imageHeight;
			$cropNewWidth = $newWidth;
			$cropNewHeight = $newHeight;
		}
	
		if ($newWidth < 1) {
			$newWidth = 1;
		}
		if ($cropWidth < 1) {
			$cropWidth = 1;
		}
		if ($cropNewWidth < 1) {
			$cropNewWidth = 1;
		}
	
		if ($newHeight < 1) {
			$newHeight = 1;
		}
		if ($cropHeight < 1) {
			$cropHeight = 1;
		}
		if ($cropNewHeight < 1) {
			$cropNewHeight = 1;
		}
	}

	//Formerly "resizeImageString()"
	public static function resizeImageString(&$image, $mime_type, &$imageWidth, &$imageHeight, $maxWidth, $maxHeight, $mode = 'resize', $offset = 0) {
		//Work out the new width/height of the image
		$newWidth = $newHeight = $cropWidth = $cropHeight = $cropNewWidth = $cropNewHeight = false;
		\ze\file::resizeImageByMode($mode, $imageWidth, $imageHeight, $maxWidth, $maxHeight, $newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight, $mime_type);
	
		\ze\file::resizeImageStringToSize($image, $mime_type, $imageWidth, $imageHeight, $newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight, $offset);
	
		if (!is_null($image)) {
			$imageWidth = $cropNewWidth;
			$imageHeight = $cropNewHeight;
		}
	}

	//Formerly "resizeImageStringToSize()"
	public static function resizeImageStringToSize(&$image, $mime_type, $imageWidth, $imageHeight, $newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight, $offset = 0) {
		//Check if the image needs to be resized
		if ($imageWidth != $cropNewWidth || $imageHeight != $cropNewHeight) {
			if (\ze\file::isImage($mime_type)) {
				
				\ze::ignoreErrors();
					
					//Load the original image into a canvas
					if ($image = @imagecreatefromstring($image)) {
						//Make a new blank canvas
						$trans = -1;
						$resized_image = imagecreatetruecolor($cropNewWidth, $cropNewHeight);
		
						//Transparent gifs need a few fixes. Firstly, we need to fill the new image with the transparent colour.
						if ($mime_type == 'image/gif' && ($trans = imagecolortransparent($image)) >= 0) {
							$colour = imagecolorsforindex($image, $trans);
							$trans = imagecolorallocate($resized_image, $colour['red'], $colour['green'], $colour['blue']);				
			
							imagefill($resized_image, 0, 0, $trans);				
							imagecolortransparent($resized_image, $trans);
		
						//Transparent pngs should also be filled with the transparent colour initially.
						} elseif ($mime_type == 'image/png') {
							imagealphablending($resized_image, false); // setting alpha blending on
							imagesavealpha($resized_image, true); // save alphablending \ze::setting (important)
							$trans = imagecolorallocatealpha($resized_image, 255, 255, 255, 127);
							imagefilledrectangle($resized_image, 0, 0, $cropNewWidth, $cropNewHeight, $trans);
						}
		
						$xOffset = 0;
						$yOffset = 0;
						if ($newWidth != $cropNewWidth) {
							$xOffset = (int) (((10 - $offset) / 20) * ($imageWidth - $cropWidth));
		
						} elseif ($newHeight != $cropNewHeight) {
							$yOffset = (int) ((($offset + 10) / 20) * ($imageHeight - $cropHeight));
						}
		
						//Place a resized copy of the original image on the canvas of the new image
						imagecopyresampled($resized_image, $image, 0, 0, $xOffset, $yOffset, $cropNewWidth, $cropNewHeight, $cropWidth, $cropHeight);
		
						//The resize algorithm doesn't always respect the transparent colour nicely for gifs.
						//Solve this by resizing using a different algorithm which doesn't do any anti-aliasing, then using
						//this to create a transparent mask. Then use the mask to update the new image, ensuring that any pixels
						//that should be transparent actually are.
						if ($mime_type == 'image/gif') {
							if ($trans >= 0) {
								$mask = imagecreatetruecolor($cropNewWidth, $cropNewHeight);
								imagepalettecopy($image, $mask);
				
								imagefill($mask, 0, 0, $trans);				
								imagecolortransparent($mask, $trans);
				
								imagetruecolortopalette($mask, true, 256); 
								imagecopyresampled($mask, $image, 0, 0, $xOffset, $yOffset, $cropNewWidth, $cropNewHeight, $cropWidth, $cropHeight);
				
								$maskTrans = imagecolortransparent($mask);
								for ($y = 0; $y < $cropNewHeight; ++$y) {
									for ($x = 0; $x < $cropNewWidth; ++$x) {
										if (imagecolorat($mask, $x, $y) === $maskTrans) {
											imagesetpixel($resized_image, $x, $y, $trans);
										}
									}
								}
							}
						}
		
		
						$temp_file = tempnam(sys_get_temp_dir(), 'Img');
							if ($mime_type == 'image/gif') imagegif($resized_image, $temp_file);
							if ($mime_type == 'image/png') imagepng($resized_image, $temp_file);
							if ($mime_type == 'image/jpeg') imagejpeg($resized_image, $temp_file, $jpeg_quality = 100);
			
							imagedestroy($resized_image);
							unset($resized_image);
							$image = file_get_contents($temp_file);
						unlink($temp_file);

						//$imageWidth = $cropNewWidth;
						//$imageHeight = $cropNewHeight;
					} else {
						$image = null;
					}
					
				\ze::noteErrors();
				
			} else {
				//$imageWidth = $cropNewWidth;
				//$imageHeight = $cropNewHeight;
			}
		}
	}

	private static function createDocstoreDir($filename, $checksum, &$dir, &$path) {
		if (is_dir($dir = \ze::setting('docstore_dir'). '/')
		 && is_writable($dir)
		 && ((is_dir($dir = $dir. ($path = preg_replace('/\W/', '_', $filename). '_'. $checksum). '/'))
		  || (mkdir($dir) && \ze\cache::chmod($dir, 0777)))) {
	
			if (file_exists($dir. $filename)) {
				unlink($dir. $filename);
			}
			
			return true;
		}
		return false;
	}
}