<?php
/*
 * Copyright (c) 2023, Tribal Limited
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
		return "if (window.gtag) gtag('event', 'pageview', {'page_path' : '".\ze\escape::js($url)."'});";
	}

	//Formerly "addFileToDocstoreDir()"
	public static function addToDocstoreDir($usage, $location, $filename = false, $mustBeAnImage = false, $deleteWhenDone = true) {
		return self::addToDatabase($usage, $location, $filename, $mustBeAnImage, $deleteWhenDone, true);
	}

	//Formerly "addFileFromString()"
	public static function addFromString($usage, &$contents, $filename, $mustBeAnImage = false, $addToDocstoreDirIfPossible = false, $imageCredit = '') {
	
		if ($temp_file = tempnam(sys_get_temp_dir(), 'cpy')) {
			if (file_put_contents($temp_file, $contents)) {
				return self::addToDatabase($usage, $temp_file, $filename, $mustBeAnImage, true, $addToDocstoreDirIfPossible, false, false, false, false, $imageCredit);
			}
		}
	
		return false;
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
	
	private static function genericCheckError($filepath) {
		return new \ze\error('INVALID', \ze\admin::phrase('The contents of the file "[[filename]]" are corrupted and/or invalid.', ['filename' => basename($filepath)]));
	}
	
	
	//Attempt to check if the contents of a file match the file
	public static function check($filepath, $mimeType = null, $allowPasswordProtectedOfficeDocs = false) {
		
		if ($mimeType === null) {
			$mimeType = \ze\file::mimeType($filepath);
		}
		
		//Check to see if we have access to the file utility in UN*X
		if (!\ze\server::isWindows() && \ze\server::execEnabled()) {
			
			//Attempt to call the file program to check what mime-type it thinks this file should be.
			//Note that our check using ze\file::mimeType() just checks the file extension and nothing else.
			//The file program is a little more sophisticated and does some basic checks on the file's contents as well.
			if (!$scannedMimeType = exec('file --mime-type --brief '. escapeshellarg($filepath))) {
				return self::genericCheckError($filepath);
			}
			
			//Ignore the "x-" prefix as this is inconsistently applied in different versions of file.
			$mimeType = str_replace('/x-', '/', $mimeType);
			$scannedMimeType = str_replace('/x-', '/', $scannedMimeType);
			
			//Sometimes the fine details might differ between the registered mime-type and the scanned mime-type.
			//Try to work out the basic type and compare off of that to prevent lots of false positives
			//from slightly different classifications
			$basicType = \ze\file::basicType($mimeType);
			$scannedBasicType = \ze\file::basicType($scannedMimeType);
			
			//Check the basic types match, and reject the file if not.
			if ($basicType !== $scannedBasicType) {
				if (substr($mimeType, 0, 22) == 'application/postscript' && $scannedMimeType == 'image/eps') {
					//Special case for EPS files: do nothing, allow these files
				} elseif ($mimeType == "text/csv" && \ze::in($scannedMimeType, 'text/csv', 'application/csv')) {
					//Special case for CSV files: allow certain mime types
				} else {
					return self::genericCheckError($filepath);
				}
			}
			
			//For images, enforce that the exact format of the image's contents matches what the file
			//extension says it should be. E.g. don't allow .PNGs renamed to .JPGs
			if ($basicType == 'image'
			 && $scannedBasicType == 'image'
			 && $mimeType !== $scannedMimeType) {
				
				switch ($scannedMimeType) {
					case 'image/gif':
						return new \ze\error('MISSNAMED_GIF', \ze\admin::phrase('The file "[[filename]]" is a GIF, so its extension must be .gif (upper or lower case).', ['filename' => basename($filepath)]));
						break;
					case 'image/jpeg':
						return new \ze\error('MISSNAMED_JPG', \ze\admin::phrase('The file "[[filename]]" is a JPG, so its extension must be .jpg or .jpeg (upper or lower case).', ['filename' => basename($filepath)]));
						break;
					case 'image/png':
						return new \ze\error('MISSNAMED_PNG', \ze\admin::phrase('The file "[[filename]]" is a PNG, so its extension must be .png (upper or lower case).', ['filename' => basename($filepath)]));
						break;
					case 'image/svg+xml':
						return new \ze\error('MISSNAMED_SVG', \ze\admin::phrase('The file "[[filename]]" is a SVG, so its extension must be .svg (upper or lower case).', ['filename' => basename($filepath)]));
						break;
					default:
						return new \ze\error('INVALID', \ze\admin::phrase('The file "[[filename]]" has been saved with the wrong extension and cannot be accepted.', ['filename' => basename($filepath)]));
				}
			}
			
			//If this is an Office document, check both checks agree that it's an Office document
			$exIsOffice = substr($mimeType, 0, 15) == 'application/vnd';
			$scanIsOffice = substr($scannedMimeType, 0, 15) == 'application/vnd';
			
			if ($exIsOffice
			 && !$scanIsOffice
			 && substr($scannedMimeType, 0, 15) != 'application/vnd') {
				
				if ($scannedMimeType == 'application/encrypted') {
					if ($allowPasswordProtectedOfficeDocs) {
						//Do nothing, allow these files
					} else {
						$filename = basename($filepath);
						return new \ze\error('PASSWORD_PROTECTED', \ze\admin::phrase('The file "[[filename]]" is password-protected. Password protection needs to be removed before you can upload it to Zenario.', ['filename' => $filename]));
					}
				} else {
					return self::genericCheckError($filepath);
				}
			}
			
			//...and vice versa, don't let other files mascerade as Office docs
			if ($scanIsOffice && !$exIsOffice) {
				return self::genericCheckError($filepath);
			}
			
			switch ($mimeType) {
				//For a short list of files, check we have an exact match of mime-types
				//(Everywhere else I'm being a little more flexiable as there is often disagreement about exactly what
				// the mime-type for a file should be.)
				case 'application/msword':
				case 'application/pdf':
				case 'application/zip':
				case 'application/gzip':
				case 'application/7z-compressed':
					if ($mimeType !== $scannedMimeType) {
						return self::genericCheckError($filepath);
					}
					break;
				
				//Always block executable files
				case 'application/dosexec':
				case 'application/executable':
				case 'application/mach-binary':
					return self::genericCheckError($filepath);
			}
		}
		
		//Note none of the code below is affected by the "x-" prefix, so I don't need to 
		//worry about whether I've changed that or not above.
		
		
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
		
		if ($check) {
			return true;
		} else {
			return self::genericCheckError($filepath);
		}
	}

	//Formerly "addFileToDatabase()"
	public static function addToDatabase($usage, $location, $filename = false, $mustBeAnImage = false, $deleteWhenDone = false, $addToDocstoreDirIfPossible = false, $imageAltTag = false, $imageTitle = false, $imagePopoutTitle = false, $imageMimeType = false, $imageCredit = '') {
		//$overrideMimeType should only be specified when running the installer, because we don't yet have the proper handling for mime types
		
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
		
		if ($filename === false) {
			$filename = \ze\file::safeName(basename($location));
		} else {
			$filename = \ze\file::safeName($filename);
		}

		$file['filename'] = $filename;
		
		if ($imageMimeType) {
			$file['mime_type'] = $imageMimeType;
		} else {
			$file['mime_type'] = \ze\file::mimeType($filename);
		}

		$file['usage'] = $usage;

		//The image credit column was added in Zenario 9.3, rev 55901.
		//The code below will ensure that the Installer isn't trying to use it
		//before the relevant DB update adds the new column.
		if (!is_null(\ze::$dbL) && \ze::$dbL->checkTableDef(DB_PREFIX. 'files', 'image_credit')) {
			$file['image_credit'] = $imageCredit;
		}

		if ($mustBeAnImage && !\ze\file::isImageOrSVG($file['mime_type'])) {
			return false;
		}

		//Check if this file exists in the system already
		$key = ['checksum' => $file['checksum'], 'usage' => $file['usage']];
		if ($existingFile = \ze\row::get('files', ['id', 'filename', 'location', 'path'], $key)) {
			if($existingFile['location'] != 's3'){
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
			}
		
		//Otherwise we must insert the new file
		} else {
			$file['privacy'] = \ze::oneOf(\ze::setting('default_image_privacy'), 'auto', 'public', 'private');
		}



		//Check if the file is an image and get its meta info
		//(Note this logic is the same as in \ze\file::check(), except it also saves the meta info.)
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
					['custom_thumbnail_1_data', 'custom_thumbnail_1_width', 'custom_thumbnail_1_height', \ze::setting('custom_thumbnail_1_width'), \ze::setting('custom_thumbnail_1_height'), false],
					['custom_thumbnail_2_data', 'custom_thumbnail_2_width', 'custom_thumbnail_2_height', \ze::setting('custom_thumbnail_2_width'), \ze::setting('custom_thumbnail_2_height'), false],
					['thumbnail_180x130_data', 'thumbnail_180x130_width', 'thumbnail_180x130_height', 180, 130, true]
				] as $c) {
					if ($c[3] && $c[4] && ($c[5] || ($file['width'] > $c[3] || $file['height'] > $c[4]))) {
						$file[$c[1]] = $image[0];
						$file[$c[2]] = $image[1];
						$file[$c[0]] = file_get_contents($location);
						\ze\file::resizeImageString($file[$c[0]], $file['mime_type'], $file[$c[1]], $file[$c[2]], $c[3], $c[4]);
					}
				}
	
			} else {
				if (function_exists('simplexml_load_string')) {
					//Try and get the width and height of the SVG from its metadata.
					$rtn = \ze\file::getWidthAndHeightOfSVG($file, file_get_contents($location));
				
					//If PHP's simplexml_load_string function isn't callable allow this and continue.
					//However if the funciton was callable and couldn't parse the file, don't allow this
					//and reject the file.
					if (!$rtn) {
						return false;
					}
				}
			}
	
			if ($imageAltTag) {
				$file['alt_tag'] = $imageAltTag;
			} else {
				$filenameArray = explode('.', $filename);
				$altTag = trim(preg_replace('/[^a-z0-9]+/i', ' ', $filenameArray[0]));
				$file['alt_tag'] = $altTag;
			}
		}


		$file['archived'] = 0;
		$file['created_datetime'] = \ze\date::now();
		
		$dir = $path = false;
		if ($addToDocstoreDirIfPossible
		 && self::createDocstoreDir($file['filename'], $file['checksum'], $dir, $path)) {
	
			if ($deleteWhenDone) {
				rename($location, $dir. $file['filename']);
				\ze\cache::chmod($dir. $file['filename'], 0666);
			} else {
				copy($location, $dir. $file['filename']);
				\ze\cache::chmod($dir. $file['filename'], 0666);
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
	
	//Try and get the width and height of a SVG from its metadata.
	public static function getWidthAndHeightOfSVG(&$file, $data) {
			
		//For SVGs, try to read the metadata from the image and get the width and height from it.
		\ze::ignoreErrors();
			$svg = simplexml_load_string($data);
		\ze::noteErrors();
		
		if ($svg) {
			//There are lots of possible formats for this to watch out for.
			//I've tried to be very flexible and code support for as many as I
			//know. This code has suppose for setting the width and height in the
			//following formats:
				// width="123" height="456"
				// width="123px" height="456px"
				// width="100%" height="100%" viewbox="0 0 123 456"
			//...and also any variation of those should work!
			$vars = [];
			foreach ($svg->attributes() as $name => $value) {
				switch (strtolower($name)) {
					case 'width':
					case 'height':
						$value = (string) $value;
						if (is_numeric($value)) {
							$vars[$name] = (int) $value;
						} else {
							$value2 = str_replace('px', '', $value);
							if (is_numeric($value2)) {
								$vars[$name] = (int) $value2;
							} else {
								$value2 = str_replace('%', '', $value);
								if (is_numeric($value2)) {
									$vars[$name. '%'] = (int) $value2;
								}
							}
						}
						break;
					case 'viewbox':
						$vb = explode(' ', (string) $value);
						for ($i = 0; $i < 4; ++$i) {
							if (isset($vb[$i]) && is_numeric($vb[$i])) {
								$vars['vb'. $i] = (int) $vb[$i];
							}
							
						}
						break;
				}
			}
			
			//Set the width and height from the variables we just extracted.
			//If we failed to do this, default to 100x100.
			if (isset($vars['width'])) {
				$file['width'] = $vars['width'];
			
			} elseif (isset($vars['vb0'], $vars['vb2'])) {
				$file['width'] = (int) (($vars['vb2'] - $vars['vb0']) * ($vars['width%'] ?? 100) / 100);
			
			} else {
				$file['width'] = 100;
			}
			
			if (isset($vars['height'])) {
				$file['height'] = $vars['height'];
			
			} elseif (isset($vars['vb1'], $vars['vb3'])) {
				$file['height'] = (int) (($vars['vb3'] - $vars['vb1']) * ($vars['height%'] ?? 100) / 100);
			
			} else {
				$file['height'] = 100;
			}
			
			return true;
			
		} else {
			return false;
		}
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
			
			//advpng_path, jpegoptim_path, jpegtran_path, optipng_path
			foreach (['advpng', 'jpegoptim', 'jpegtran', 'optipng'] as $program) {
				if ($programPath = \ze\server::programPathForExec(\ze::setting($program. '_path'), $program, true)) {
					self::$options[$program. '_bin'] = $programPath;
				} else {
					unset(self::$options[$program. '_options']);
				}	
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
	
		$mimeType = self::mimeType($path);

		if (!\ze::in($mimeType, 'image/png', 'image/jpeg')
		 || !is_file($path)
		 || !is_writable($path)) {
			return false;
		}
	
		switch ($mimeType) {
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

	public static function deleteMediaContentItemFileIfUnused($cID, $cType, $fileId) {
		if ($cID && $cType && $fileId) {
			$tagId = $cType . '_' . $cID;
			$sql = '
				SELECT GROUP_CONCAT(ci.id) AS content_items
				FROM ' . DB_PREFIX . 'content_items ci
				LEFT JOIN ' . DB_PREFIX . 'content_item_versions civ
					ON civ.tag_id = ci.tag_id
				WHERE civ.file_id = ' . (int) $fileId . '
				AND ci.tag_id <> "' . \ze\escape::asciiInSQL($tagId) . '"
				AND ci.status NOT IN ("deleted", "trashed", "hidden")';
			$result = \ze\sql::select($sql);
			$usage = \ze\sql::fetchValue($result);
			
			if (!$usage) {
				\ze\file::delete($fileId);
				return true;
			}
		}

		return false;
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

	//If an image is set as public, add it to the public/images/ directory
	public static function addPublicImage($imageId, $makeWebP = true) {
		
		//Note: this actually just works by calling the function to get a link to the image.
		$retina = false;
		$width = $height = $url = $webPURL = $isRetina = $mimeType = null;
		return \ze\file::imageAndWebPLink($width, $height, $url, $makeWebP, $webPURL, $retina, $isRetina, $mimeType, $imageId);
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
	public static function isAllowed($file, $alwaysAllowImages = true) {
		$type = explode('.', $file);
		$type = $type[count($type) - 1];
		
		if (self::isExecutable($type)) {
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

	//Formerly "checkDocumentTypeIsExecutable()"
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
		
		if (\ze::$mustUseFullPath) {
			$url = \ze\link::absolute(). $url;
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
	
		if ($file = \ze\row::get('files', ['usage', 'filename', 'location', 'path', 'image_credit'], ['id' => $existingFileId])) {
			if ($file['usage'] == $usage) {
				return $existingFileId;
		
			} elseif ($file['location'] == 'db') {
				$data = \ze\row::get('files', 'data', ['id' => $existingFileId]);
				return self::addFromString($usage, $data, ($filename ?: $file['filename']), $mustBeAnImage, $addToDocstoreDirIfPossible, $file['image_credit']);
		
			} elseif ($file['location'] == 'docstore' && ($location = self::docstorePath($file['path']))) {
				return self::addToDatabase($usage, $location, ($filename ?: $file['filename']), $mustBeAnImage, $deleteWhenDone = false, $addToDocstoreDirIfPossible = true, false, false, false, false, $file['image_credit']);
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
			header('Content-Disposition: attachment; filename="'. \ze\file::safeName($filename). '"');
			
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
		
		//Look up this mime type.
		//But if we don't have database access (e.g. we're not installed), call commonMimeType() as a fallback
		if (is_null(\ze::$dbL)) {
			return \ze\welcome::commonMimeType($type);
		} else {
			return \ze\row::get('document_types', 'mime_type', ['type' => strtolower($type)]) ?: 'application/octet-stream';
		}
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
	public static function labelDetails($fileId, $filename = null) {
		
		$sql = '
			SELECT id, filename, size, path, location, width, height, checksum, short_checksum, `usage`
			FROM '. DB_PREFIX. 'files
			WHERE id = '. (int) $fileId;
		
		if ($file = \ze\sql::fetchAssoc($sql)) {
			
			$file['label'] = $filename ?? $file['filename'];

			$file['size'] = self::formatSizeUnits($file['size']);

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
	



	
	//Try to set the feature image (aka sticky image) for a link to a content item in the framework
	public static function featureImageHTML(
		$cID, $cType, $cVersion,
		$useFallbackImage, $fallbackImageId,
		$maxWidth, $maxHeight, $canvas, $retina, $makeWebP,
		$altTag, $htmlID = '', $cssClass = '', $styles = '', $attributes = ''
	) {

		if ($cType == 'picture') {
			//Legacy code for Pictures
			$imageId = \ze\row::get("content_item_versions", "file_id", ["id" => $cID, 'type' => $cType, "version" => $cVersion]);
		} else {
			$imageId = \ze\file::itemStickyImageId($cID, $cType, $cVersion);
		} 
		
		if (!$imageId && $useFallbackImage) {
			$imageId = $fallbackImageId;
		}
		
		if ($imageId) {
			$cssRules = [];
			return \ze\file::imageHTML(
				$cssRules, true,
				$imageId, $maxWidth, $maxHeight, $canvas, $retina, $makeWebP,
				$altTag, $htmlID, $cssClass, $styles, $attributes
			);
		}
		
		return '';
	}
	
	//Generate the HTML and CSS tags needed for an image on the page.
	//This supports numerous different modes and features.
	//N.b. some modes/features are not compatible with each other, and are mutualy exclusive:
		//You may only use one of the two following options: Show as background image; Lazy Load
		//Different options for mobile images cannot be used if the Lazy Load option is being used
	public static function imageHTML(
		&$cssRules, $preferInlineStypes,
		$imageId, $maxWidth, $maxHeight, $canvas, $retina, $makeWebP,
		$altTag = '', $htmlID = '', $cssClass = '', $styles = '', $attributes = '',
		$showAsBackgroundImage = false, $lazyLoad = false, $hideOnMob = false, $changeOnMob = false,
		$mobImageId = false, $mobMaxWidth = false, $mobMaxHeight = false, $mobCanvas = false, $mobRetina = false, $mobWebP = true,
		$sourceIDPrefix = '',
		$showImageLinkInAdminMode = true, $imageLinkNum = 1, $alsoShowMobileLink = true, $mobImageLinkNum = 2
	) {
		//Get a link to the image. Also get retina/WebP links as well if requested.
		$retinaSrcset = $webPSrcset = 
		$baseURL = $retinaURL = $webPURL = $retinaWebPURL =
		$width = $height = $mimeType = false;
		if (\ze\file::imageHTMLInternal(
			$imageId, $maxWidth, $maxHeight, $canvas, $retina, $makeWebP,
			$baseURL, $retinaURL, $webPURL, $retinaWebPURL,
			$retinaSrcset, $webPSrcset,
			$width, $height, $mimeType
		)) {
			$setMobDimensions =
			$mobRetinaSrcset = $mobWebPSrcset =
			$mobBaseURL = $mobRetinaURL = $mobWebPURL = $mobRetinaWebPURL =
			$mobWidth = $mobHeight = $mobMimeType = false;
			
			//If in admin mode, add a specific CSS class to images.
			if ($showImageLinkInAdminMode && \ze::isAdmin()) {
				$cssClass .= ' zenario_image_properties zenario_image_id__'. $imageId. '__ zenario_image_num__'. $imageLinkNum. '__';
				
				if ($alsoShowMobileLink && $changeOnMob && $mobImageId != $imageId) {
					$cssClass .= ' zenario_mob_image_id__'. $mobImageId. '__ zenario_mob_image_num__'. $mobImageLinkNum. '__';
				}
			}
			
			
			//Show an image in the background.
			//This mode:
				//Requires you to provide a $htmlID for the image.
				//Does not return any HTML.
				//Outputs CSS rules for the image into the $cssRules array.
			if ($showAsBackgroundImage) {
				
				//Add CSS rules for the images width/height/URL.
				//The rules differ slightly if we are using WebP, as we need both the WebP
				//image and support for a fallback.
				if ($webPURL) {
					$cssRules[] = '#'. $htmlID. ' {
'.					'	display: block;
'.					'	width: '. $width. 'px;
'.					'	height: '. $height. 'px;
'.					'	background-size: '. $width. 'px '. $height. 'px;
'.					'	background-image: url(\''. htmlspecialchars($webPURL).  '\');
'.					'	background-repeat: no-repeat;
'.					'}';
					$cssRules[] = 'body.no_webp #'. $htmlID. ' {
'.					'	background-image: url(\''. htmlspecialchars($baseURL).  '\');
'.					'}';
				} else {
					$cssRules[] = '#'. $htmlID. ' {
'.					'	display: block;
'.					'	width: '. $width. 'px;
'.					'	height: '. $height. 'px;
'.					'	background-size: '. $width. 'px '. $height. 'px;
'.					'	background-image: url(\''. htmlspecialchars($baseURL).  '\');
'.					'	background-repeat: no-repeat;
'.					'}';
				}
				
				//If we have a retina version of the image, add some extra rules to show this
				//on retina screens.
				if ($retinaURL) {
					if ($retinaWebPURL) {
						$cssRules[] = 'body.retina #'. $htmlID. ' {
'.						'	background-image: url(\''. htmlspecialchars($retinaWebPURL).  '\');
'.						'}';
						$cssRules[] = 'body.retina.no_webp #'. $htmlID. ' {
'.						'	background-image: url(\''. htmlspecialchars($retinaURL).  '\');
'.						'}';
					} else {
						$cssRules[] = 'body.retina #'. $htmlID. ' {
'.						'	background-image: url(\''. htmlspecialchars($retinaURL).  '\');
'.						'}';
					}
				}
				
				//If we should hide the image on mobile, add one extra rule for this.
				if ($hideOnMob) {
					$cssRules[] = 'body.mobile #'. $htmlID. ' { display: none; }';
				
				//If we should show a different image on mobile, repeat some of the above logic
				//and add some extra CSS rules for a mobile version.
				} elseif ($changeOnMob) {
					if (\ze\file::imageHTMLInternal(
						$mobImageId, $mobMaxWidth, $mobMaxHeight, $mobCanvas, $mobRetina, $mobWebP,
						$mobBaseURL, $mobRetinaURL, $mobWebPURL, $mobRetinaWebPURL,
						$mobRetinaSrcset, $mobWebPSrcset,
						$mobWidth, $mobHeight, $mobMimeType
					)) {
						if ($mobWebPURL) {
							$cssRules[] = 'body.mobile #'. $htmlID. ' {
'.							'	width: '. $width. 'px;
'.							'	height: '. $height. 'px;
'.							'	background-size: '. $mobWidth. 'px '. $mobHeight. 'px;
'.							'	background-image: url(\''. htmlspecialchars($mobWebPURL).  '\');
'.							'}';
							$cssRules[] = 'body.mobile.no_webp #'. $htmlID. ' {
'.							'	background-image: url(\''. htmlspecialchars($mobBaseURL).  '\');
'.							'}';
						} else {
							$cssRules[] = 'body.mobile #'. $htmlID. ' {
'.							'	width: '. $width. 'px;
'.							'	height: '. $height. 'px;
'.							'	background-size: '. $mobWidth. 'px '. $mobHeight. 'px;
'.							'	background-image: url(\''. htmlspecialchars($mobBaseURL).  '\');
'.							'}';
						}
				
						if ($mobRetinaURL) {
							if ($mobRetinaWebPURL) {
								$cssRules[] = 'body.mobile.retina #'. $htmlID. ' {
'.								'	background-image: url(\''. htmlspecialchars($mobRetinaWebPURL).  '\');
'.								'}';
								$cssRules[] = 'body.mobile.retina.no_webp #'. $htmlID. ' {
'.								'	background-image: url(\''. htmlspecialchars($mobRetinaURL).  '\');
'.								'}';
							} else {
								$cssRules[] = 'body.mobile.retina #'. $htmlID. ' {
'.								'	background-image: url(\''. htmlspecialchars($mobRetinaURL).  '\');
'.								'}';
							}
						}
						
						//If the mobile version is displayed at a different width and height, we'll need to add a
						//CSS rule to change that info too.
						if ($mobWidth != $width
						 || $mobHeight != $height) {
							$cssRules[] = 'body.mobile #'. $htmlID. ' { width: '. $mobWidth. 'px; height: '. $mobHeight. 'px; }';
						}
					}
				}
				
				
				$html = '';
				
				$html .= "\n\t". 'id="'. htmlspecialchars($htmlID). '"';
				
				if ($cssClass !== '') {
					$html .= "\n\t". 'class="'. htmlspecialchars($cssClass). '"';
				}
				
				return $html;
			
			
			//Lazy load an image
			//This mode:
				//Writes the URLs for the images needed onto the page, but not in a way that the browser will start loading them.
				//Relies on the jQuery Lazy library to load them later.
				//Has some supporting code in the zenario.addJQueryElements() function (in visitor.js) to trigger the load.
				//Also uses some supporting code to add WebP support with fallbacks for non-WebP browsers.
				//Does not currently support different images for mobile devices.
			} elseif ($lazyLoad) {
				
				//This code mostly works by writing a normal image tag, but with a few odd exceptions
				$html = '<img';
				
				//The "lazy" class is used to bind the Lazy Load logic to the image
				$cssClass .= ' lazy';
				
				//Require the lazy-load library
				//Note: due to some bugs when using the "Auto" option for this library, we've removed
				//the option to select "Auto" for this library, so this line is currently not needed.
				//\ze::requireJsLib('zenario/libs/yarn/jquery-lazy/jquery.lazy.min.js');
				
				if ($webPURL) {
					//The "lazyWebP" class is used to bind the WebP fallback logic to the image
					$cssClass .= ' lazyWebP';
					
					//Any image URL we will need when the image should be displayed is written to the image,
					//but using data attributes instead of the attributes used in the HTML spec. This stops the
					//browser loading them initially.
					//When we do need to load them, they'll be swapped in by the jQuery Lazy library.
					$html .= "\n\t". 'type="image/webp"';
					$html .= "\n\t". 'data-no-webp-type="'. htmlspecialchars($mimeType). '"';
					
					$html .= "\n\t". 'data-src="'. htmlspecialchars($webPURL). '"';
					$html .= "\n\t". 'data-no-webp-src="'. htmlspecialchars($baseURL). '"';
					
					if ($retinaSrcset) {
						$html .= "\n\t". 'data-srcset="'. htmlspecialchars($webPSrcset). '"';
						$html .= "\n\t". 'data-no-webp-srcset="'. htmlspecialchars($retinaSrcset). '"';
					}
				
				} else {
					$html .= "\n\t". 'type="'. htmlspecialchars($mimeType). '"';
					$html .= "\n\t". 'data-src="'. htmlspecialchars($baseURL). '"';
					
					if ($retinaSrcset) {
						$html .= "\n\t". 'data-srcset="'. htmlspecialchars($retinaSrcset). '"';
					}
				}
				
				if ($htmlID !== '') {
					$html .= "\n\t". 'id="'. htmlspecialchars($htmlID). '"';
				}
				
				if ($cssClass !== '') {
					$html .= "\n\t". 'class="'. htmlspecialchars($cssClass). '"';
				}
		
				$html .= "\n\t". 'style="';
			
				$html .= htmlspecialchars('width: '. $width. 'px; height: '. $height. 'px;');
				$html .= ' ';
				$html .= htmlspecialchars($styles);
		
				$html .= '"';
				
				if ($altTag !== '') {
					$html .= "\n\t". 'alt="'. htmlspecialchars($altTag). '"';
				}
		
				if ($attributes !== '') {
					$html .= ' '. $attributes;
				}
				$html .= "\n". '/>';
				
				return $html;
			
			
			//"No additional behaviour" mode - aka normal mode.
			//This mode:
				//Uses a <picture> tag with <source> tags inside to specify all of the possible links for an image.
				//Tries to use inline styles to set information if requested and if possible, however some features do need to use CSS rules.
			} else {
				$html = '<picture>';
					
					//Count how many <source> tags we've used.
					//If we've been asked to give the <source> tags IDs, we'll use this variable to give them each a different ID.
					$sIdNum = 0;
					
					//Try to hide the image on mobile devices
					if ($hideOnMob) {
						if ($preferInlineStypes) {
							//This is a bit annoying to do if we can't use CSS rules.
							//As a hack to try and implement it, we'll add a blank source image.
							$trans = \ze\link::absoluteIfNeeded(). 'zenario/admin/images/trans.png';
					
							$html .= "\n\t". '<source';
								if ($sourceIDPrefix !== '') {
									$html .= ' id="'. htmlspecialchars($sourceIDPrefix. ++$sIdNum). '"';
								}
							$html .= ' srcset="'. htmlspecialchars($trans. ' 1x, '. $trans. ' 2x'). '" media="(max-width: '. (\ze::$minWidth - 1). 'px)" type="image/png">';
						
						} else {
							//So much easier to do if CSS is available!
							$cssRules[] = 'body.mobile #'. $htmlID. ' { display: none; }';
						}
					
					//Show a different image on a mobile device.
					} elseif ($changeOnMob) {
						if (\ze\file::imageHTMLInternal(
							$mobImageId, $mobMaxWidth, $mobMaxHeight, $mobCanvas, $mobRetina, $mobWebP,
							$mobBaseURL, $mobRetinaURL, $mobWebPURL, $mobRetinaWebPURL,
							$mobRetinaSrcset, $mobWebPSrcset,
							$mobWidth, $mobHeight, $mobMimeType
						)) {
							if ($mobRetinaSrcset) {
								$mobSrcset = $mobBaseURL. ' 1x, '. $mobRetinaSrcset;
							} else {
								$mobSrcset = $mobBaseURL;
							}
							
							//We'll set <source> tags with a media/max-width rule to supply a different image on a mobile device
							if ($webPSrcset) {
								$html .= "\n\t". '<source';
									if ($sourceIDPrefix !== '') {
										$html .= ' id="'. htmlspecialchars($sourceIDPrefix. ++$sIdNum). '"';
									}
								$html .= ' srcset="'. htmlspecialchars($mobWebPSrcset). '" media="(max-width: '. (\ze::$minWidth - 1). 'px)" type="image/webp">';
							}
						
							$html .= "\n\t". '<source';
								if ($sourceIDPrefix !== '') {
									$html .= ' id="'. htmlspecialchars($sourceIDPrefix. ++$sIdNum). '"';
								}
							$html .= ' srcset="'. htmlspecialchars($mobSrcset). '" media="(max-width: '. (\ze::$minWidth - 1). 'px)" type="'. htmlspecialchars($mobMimeType). '">';
							
							//If we need to change the image's dimensions on mobile, we can't use inline styles, as they
							//would overwrite the mobile options.
							if ($mobWidth != $width
							 || $mobHeight != $height) {
								$preferInlineStypes = false;
								$setMobDimensions = true;
							}
						}
					}
				
				
		
					//If we have a WebP-version of the image, offer it as an alternate by setting a <source> tag
					if ($webPSrcset) {
						$html .= "\n\t". '<source';
							if ($sourceIDPrefix !== '') {
								$html .= ' id="'. htmlspecialchars($sourceIDPrefix. ++$sIdNum). '"';
							}
						$html .= ' srcset="'. htmlspecialchars($webPSrcset). '" type="image/webp">';
					}
		
					//If we have a retina-version of the image, offer it as an alternate by setting a <source> tag
					if ($retinaSrcset) {
						$html .= "\n\t". '<source';
							if ($sourceIDPrefix !== '') {
								$html .= ' id="'. htmlspecialchars($sourceIDPrefix. ++$sIdNum). '"';
							}
						$html .= ' srcset="'. htmlspecialchars($retinaSrcset). '" type="'. htmlspecialchars($mimeType). '">';
					}
					
					//Write out an actual image tag, with the basic version of the image in the src.
					$html .= "\n\t". '<img';
				
						if ($htmlID !== '') {
							$html .= "\n\t\t". 'id="'. htmlspecialchars($htmlID). '"';
						}
					
						$html .= "\n\t\t". 'src="'. htmlspecialchars($baseURL). '"';
				
						if ($cssClass !== '') {
							$html .= "\n\t\t". 'class="'. htmlspecialchars($cssClass). '"';
						}
						
						if ($preferInlineStypes || $styles !== '') {
							$html .= "\n\t\t". 'style="';
						}
						
						//We need to set the width and height of the image. This can either be done using inline styles,
						//or as a CSS rule.
						if ($preferInlineStypes) {
							$html .= htmlspecialchars('width: '. $width. 'px; height: '. $height. 'px;');
						} else {
							$cssRules[] = '#'. $htmlID. ' { width: '. $width. 'px; height: '. $height. 'px; }';
						}
						
						//Set different width/height rules for mobile if needed.
						if ($setMobDimensions) {
							$cssRules[] = 'body.mobile #'. $htmlID. ' { width: '. $mobWidth. 'px; height: '. $mobHeight. 'px; }';
						}
				
						if ($styles !== '') {
							if ($preferInlineStypes) {
								$html .= ' ';
							}
							$html .= htmlspecialchars($styles);
						}
						
						if ($preferInlineStypes || $styles !== '') {
							$html .= '"';
						}
				
						$html .= "\n\t\t". 'type="'. htmlspecialchars($mimeType). '"';
				
						if ($altTag !== '') {
							$html .= "\n\t\t". 'alt="'. htmlspecialchars($altTag). '"';
						}
				
						if ($attributes !== '') {
							$html .= ' '. $attributes;
						}
					$html .= "\n\t". '/>';
				$html .= "\n". '</picture>';
			
				return $html;
			}
		} else {
			return '';
		}
	}
	
	private static function imageHTMLInternal(
		$imageId, $maxWidth, $maxHeight, $canvas, $retina, $makeWebP,
		&$baseURL, &$retinaURL, &$webPURL, &$retinaWebPURL,
		&$retinaSrcset, &$webPSrcset,
		&$width, &$height, &$mimeType
	) {
		//The "retina" option only applies to images using the "unlimited" canvas option. 
		$retina = $retina || $canvas != 'unlimited';
		
		$baseURL = $retinaURL = $webPURL = $retinaWebPURL =
		$retinaSrcset = $webPSrcset =
		$width = $height = $baseURL = $webPURL = $isRetina = $mimeType = false;
		
		//Try and get a link to the image, and also a WebP version if requested.
		if (\ze\file::imageAndWebPLink(
			$width, $height, $baseURL, $makeWebP, $webPURL, $retina, $isRetina, $mimeType,
			$imageId, $maxWidth, $maxHeight, $canvas
		)) {
			$webPSrcset = $webPURL;

			//If this was a retina image, get a normal version of the image as well for standard displays
			if ($isRetina) {
				$sWidth = $sHeight = $sURL = $sWebPURL = false;
				if (\ze\file::imageAndWebPLink(
					$sWidth, $sHeight, $sURL, $makeWebP, $sWebPURL, false, $isRetina, $mimeType,
					$imageId, $width, $height, $canvas == 'crop_and_zoom'? 'crop_and_zoom' : 'adjust'
						//We already know the width and height of the image from the call above,
						//so unless we're using the "crop_and_zoom" option, we don't need any
						//special logic and can switch the canvas to "adjust".
				)) {
					$retinaURL = $baseURL;
					$baseURL = $sURL;
					$retinaSrcset = $retinaURL. ' 2x';
					
					if ($sWebPURL) {
						$retinaWebPURL = $webPURL;
						$webPURL = $sWebPURL;
						$webPSrcset = $webPURL. ' 1x, '. $retinaWebPURL. ' 2x';
					}
				}
			}
			
			return true;
		}
		
		return false;
	}

	//Formerly "imageLink()"
	public static function imageLink(
		&$width, &$height, &$url, $fileId, $maxWidth = 0, $maxHeight = 0, $canvas = 'resize', $offset = 0,
		$retina = false, $fullPath = false, $privacy = 'auto',
		$useCacheDir = true, $internalFilePath = false, $returnImageStringIfCacheDirNotWorking = false
	) {
		$webPURL = $mimeType = $isRetina = null;

		return self::imageAndWebPLink(
			$width, $height, $url, false, $webPURL, $retina, $isRetina, $mimeType,
			$fileId, $maxWidth, $maxHeight, $canvas, $offset,
			$fullPath, $privacy,
			$useCacheDir, $internalFilePath, $returnImageStringIfCacheDirNotWorking
		);
	}
	
	public static function imageAndWebPLink(
		&$width, &$height, &$url, $makeWebP, &$webPURL, $retina, &$isRetina, &$mimeType,
		$fileId, $maxWidth = 0, $maxHeight = 0, $canvas = 'resize', $offset = 0,
		$fullPath = false, $privacy = 'auto',
		$useCacheDir = true, $internalFilePath = false, $returnImageStringIfCacheDirNotWorking = false
	) {
		$madeWebP =
		$url = $webPURL =
		$width = $height = $isRetina = $mimeType = false;
		
		$maxWidth = (int) $maxWidth;
		$maxHeight = (int) $maxHeight;
	
		//Check the $privacy variable is set to a valid option
		if ($privacy != 'auto'
		 && $privacy != 'public'
		 && $privacy != 'private') {
			return false;
		}
	
		//Check that this file exists, and is actually an image
		if (!$fileId
		 || !($image = \ze\row::get('files', [
				'privacy', 'mime_type', 'width', 'height',
				'custom_thumbnail_1_width', 'custom_thumbnail_1_height', 'custom_thumbnail_2_width', 'custom_thumbnail_2_height',
				'thumbnail_180x130_width', 'thumbnail_180x130_height',
				'checksum', 'short_checksum', 'filename', 'location', 'path'
			], $fileId, $orderBy = [], $ignoreMissingColumns = true))
		 || !(self::isImageOrSVG($image['mime_type']))) {
			return false;
		}
		
		$mimeType = $image['mime_type'];
	
		//SVG images do not need to use the retina or webp logic, as they are always crisp
		if ($isSVG = $mimeType == 'image/svg+xml') {
			$retina = false;
			$makeWebP = false;
		}
	
		$imageWidth = (int) $image['width'];
		$imageHeight = (int) $image['height'];
		
		$cropX = $cropY = $cropWidth = $cropHeight = $finalImageWidth = $finalImageHeight = false;
	
		//Special case for the "unlimited, but use a retina image" option
		if ($retina && $canvas === 'unlimited') {
			$cropX = $cropY = 0;
			$maxWidth =
			$cropWidth =
			$finalImageWidth = $imageWidth;
			$maxHeight =
			$cropHeight =
			$finalImageHeight = $imageHeight;
			$isRetina = true;
	
		} else {
			//If no limits were set, use the image's own width and height
			if (!$maxWidth) {
				$maxWidth = $imageWidth;
			}
			if (!$maxHeight) {
				$maxHeight = $imageHeight;
			}
			
			\ze\file::scaleImageDimensionsByMode(
				$mimeType, $imageWidth, $imageHeight,
				$maxWidth, $maxHeight, $canvas, $offset,
				$cropX, $cropY, $cropWidth, $cropHeight, $finalImageWidth, $finalImageHeight,
				$fileId
			);
			
			//Try to use a retina image if requested
			if ($retina
			 && (2 * $finalImageWidth <= $imageWidth)
			 && (2 * $finalImageHeight <= $imageHeight)) {
				$finalImageWidth *= 2;
				$finalImageHeight *= 2;
				$isRetina = true;
			
			} else {
				$retina = false;
			}
		}
		
		$imageNeedsToBeCropped =
			$cropX != 0
		 || $cropY != 0
		 || $cropWidth != $imageWidth
		 || $cropHeight != $imageHeight;
	
		$imageNeedsToBeResized = $imageNeedsToBeCropped || $imageWidth != $finalImageWidth || $imageHeight != $finalImageHeight;
		$pregeneratedThumbnailUsed = false;
		
		//SVGs are vector images and don't need resizing.
		if ($isSVG) {
			$imageNeedsToBeResized = false;
		}
		
	
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
				
				//Catch the following very specific case:
					//An image has just been uploaded and is still set to "auto".
					//It's used in a plugin (e.g. a slideshow) that's on a public page.
					//A resize is used, rather than a full sized image.
				//In this case, as well as flipping the image to public, we need to add it to the public directory.
				if ($privacy == 'public' && $imageNeedsToBeResized) {
					
					//N.b. if the " && $imageNeedsToBeResized" check wasn't in the if-statement above,
					//and the state change in the database didn't happen,
					//it would be possible to send the script into an infinite recursion loop, because
					//the addPublicImage() function actually calls this function again (without a resize)
					//to do its work!
					
					\ze\file::addPublicImage($fileId);
				}
			}
		}
	
		//If we couldn't work out the privacy settings for an image, assume for now that it is private,
		//but don't update them in the database
		//if ($image['privacy'] == 'auto') {
		//	$image['privacy'] = 'private';
		//}
	
	
		//Combine the resize options into a string
		switch ($canvas) {
			case 'unlimited':
			case 'stretch':
			case 'adjust':
			case 'fixed_width':
			case 'fixed_height':
			case 'resize':
				//For any mode that shows the whole image without cropping it, there's no need to record the mode's name,
				//as any two images at the same dimensions will be the same
				$settingCode = $finalImageWidth. '_'. $finalImageHeight;
				break;
			case 'crop_and_zoom':
				//For crop and zoom mode, we need the crop-settings in the URL, so users don't see cached copies of old crop-settings
				$settingCode = $canvas. '_'. $finalImageWidth. 'x'. $finalImageHeight. '_'. $cropX. 'x'. $cropY. '_'. $cropWidth. 'x'. $cropHeight;
				break;
			case 'resize_and_crop':
				$settingCode = $canvas. '_'. $finalImageWidth. 'x'. $finalImageHeight. '_'. $offset;
				break;
			default:
				$settingCode = $canvas. '_'. $finalImageWidth. 'x'. $finalImageHeight;
		}
		
	
		//If the $useCacheDir variable is set and the public/private directories are writable,
		//try to create this image on the disk
		$path = false;
		$publicImagePath = false;
		if ($useCacheDir && \ze\cache::cleanDirs()) {
			//If this image should be in the public directory, try to create friendly and logical directory structure
			if ($image['privacy'] == 'public') {
				//We'll try to create a subdirectory inside public/images/ using the short checksum as the name
				$path = $publicImagePath = \ze\cache::createDir($image['short_checksum'], 'public/images', false);
			
				//If this is a resize, we'll put the resize in another subdirectory using the code above as the name.
				if ($path && $imageNeedsToBeResized) {
					$path = \ze\cache::createDir($image['short_checksum']. '/'. $settingCode, 'public/images', false);
				}
		
			//If the image should be in the private directory, don't worry about a friendly URL and
			//just use the full hash.
			} else {
				//Workout a hash for the image at this size.
				//Except for SVGs, the hash should also include the resize parameters
				if ($isSVG) {
					$hash = $image['checksum'];
				} else {
					$hash = \ze::hash64($settingCode. '_'. $image['checksum']);
				}			
				
				//Try to get a directory in the cache dir
				$path = \ze\cache::createDir($hash, 'images', false);
			}
		}
		
		$safeName = \ze\file::safeName($image['filename']);
		$filepath = CMS_ROOT. $path. $safeName;
		
		if ($makeWebP) {
			$webpName = implode('.', explode('.', $safeName, -1)). '.webp';
			$webpPath = CMS_ROOT. $path. $webpName;
		}
		
		//Look for the image inside the cache directory
		if ($path && file_exists($filepath)) {
			
			//Catch the case where the regular image already exists, but we also need
			//a WebP image, and that's not already there.
			if ($makeWebP) {
				if (file_exists($webpPath)) {
					$madeWebP = true;
				} else {
					$madeWebP = \ze\file::convertToWebP($filepath, $webpPath, $mimeType);
				}
			}
		
			//If the image is already available, all we need to do is link to it
			if ($internalFilePath) {
				$url = $filepath;
				
				if ($madeWebP) {
					$webPURL = $webpPath;
				}
		
			} else {
				if ($fullPath) {
					$abs = \ze\link::absolute();
				} else {
					$abs = \ze\link::absoluteIfNeeded();
				}
				
				$url = $abs. $path. rawurlencode($safeName);

				if ($madeWebP) {
					$webPURL = $abs. $path. rawurlencode($webpName);
				}
				
				if ($retina) {
					$width = (int) ($finalImageWidth / 2);
					$height = (int) ($finalImageHeight / 2);
				} else {
					$width = $finalImageWidth;
					$height = $finalImageHeight;
				}
				
				return true;
			}
		}
		
		
		//Catch another obscure issue:
			//A public image is missing from the pubic images directory.
			//We're about to display a resized copy of the image.
		//In this case, as well as creating the resized copy as normal,
		//we should check if the full sized version of the image is in the public directory.
		if ($imageNeedsToBeResized && $publicImagePath !== false) {
			if (!file_exists(CMS_ROOT. $publicImagePath. $safeName)) {
				
				//N.b. if the " && $imageNeedsToBeResized" check wasn't in the if-statement above,
				//and the file_exists() check wasn't made,
				//it would be possible to send the script into an infinite recursion loop, because
				//the addPublicImage() function actually calls this function again (without a resize)
				//to do its work!
				
				\ze\file::addPublicImage($fileId);
			}
		}
		
		
		//If there wasn't already an image we could use in the cache directory,
		//create a resized version now.
		if ($path || $returnImageStringIfCacheDirNotWorking) {
		
			//Where an image has multiple sizes stored in the database, get the most suitable size
			if (\ze::setting('thumbnail_threshold')) {
				$wcit = ((int) \ze::setting('thumbnail_threshold') ?: 66) / 100;
			} else {
				$wcit = 0.66;
			}
			
			//Work out what size the image will need to be before being cropped.
			//(If we're not cropping, this is the same as the final size.)
			if ($imageNeedsToBeCropped) {
				$widthPreCrop = $finalImageWidth * $imageWidth / $cropWidth;
				$heightPreCrop = $finalImageHeight * $imageHeight / $cropHeight;
			} else {
				$widthPreCrop = $finalImageWidth;
				$heightPreCrop = $finalImageHeight;
			}
			
			//If resizing, check to see if we can use a pregenerated thumbnail.
			if ($imageNeedsToBeResized) {
				foreach ([
					['thumbnail_180x130_data', 'thumbnail_180x130_width', 'thumbnail_180x130_height'],
					['custom_thumbnail_1_data', 'custom_thumbnail_1_width', 'custom_thumbnail_1_height'],
					['custom_thumbnail_2_data', 'custom_thumbnail_2_width', 'custom_thumbnail_2_height']
				] as $c) {
			
					//Ideally there'll be a thumbnail with exactly the right size already.
					//Alternately, grab a thumbnail that's smaller than the full image, but big enough to not cause artifacts when
					//resized down.
					$xOK = !empty($image[$c[1]]) && $widthPreCrop == $image[$c[1]] || ($widthPreCrop < $image[$c[1]] * $wcit);
					$yOK = !empty($image[$c[1]]) && $heightPreCrop == $image[$c[2]] || ($heightPreCrop < $image[$c[2]] * $wcit);
			
					if ($xOK && $yOK) {
						$thumbWidth = $image[$c[1]];
						$thumbHeight = $image[$c[2]];
						$image['data'] = \ze\row::get('files', $c[0], $fileId);
						$pregeneratedThumbnailUsed = true;
						
						//Adjust the crop settings down to the same scale factor as the thumbnail
						if ($cropX != 0) {
							$cropX = $cropX * $thumbWidth / $imageWidth;
						}
						if ($cropY != 0) {
							$cropY = $cropY * $thumbHeight / $imageHeight;
						}
						if ($cropWidth != 0) {
							$cropWidth = $cropWidth * $thumbWidth / $imageWidth;
						}
						if ($cropHeight != 0) {
							$cropHeight = $cropHeight * $thumbHeight / $imageHeight;
						}
					
						$imageNeedsToBeResized = $imageNeedsToBeCropped || $thumbWidth != $finalImageWidth || $thumbHeight != $finalImageHeight;
						break;
					}
				}
			}
			
			if ($image['location'] == 'docstore') {
				$pathDS = self::docstorePath($image['path']);
			}
			
			if (empty($image['data'])) {
				if ($image['location'] == 'db') {
					$image['data'] = \ze\row::get('files', 'data', $fileId);
			
				} elseif ($image['location'] == 'docstore' && $pathDS) {
					$image['data'] = file_get_contents($pathDS);
			
				} else {
					return false;
				}
			}
		
			if ($imageNeedsToBeResized) {
				\ze\file::scaleImageToSize(
					$image['data'], $mimeType,
					$cropX, $cropY, $cropWidth, $cropHeight, $finalImageWidth, $finalImageHeight
				);
			}
			
			//If $useCacheDir is set, attempt to store the image in the cache directory
			if ($useCacheDir && $path) {
				if ($imageNeedsToBeResized || $pregeneratedThumbnailUsed || $image['location'] == 'db') {
					file_put_contents($filepath, $image['data']);
					\ze\cache::chmod($filepath, 0666);
				
					//Try to optimise the image, if the libraries are installed.
					//Please note: private images will not be optimised, as they need to be re-generated periodically.
					if ($privacy == 'public') {
						self::optimiseImage($filepath);
					}
				} elseif ($image['location'] == 'docstore') {
					if (!file_exists($filepath)) {
						\ze\server::symlinkOrCopy($pathDS, $filepath);
					}
				}
				
				//Create a WebP image as well if asked for
				if ($makeWebP) {
					if (file_exists($webpPath)) {
						$madeWebP = true;
					} else {
						$madeWebP = \ze\file::convertToWebP($filepath, $webpPath, $mimeType);
					}
				}
				
				if ($internalFilePath) {
					$url = $filepath;
				
					if ($madeWebP) {
						$webPURL = $webpPath;
					}
		
				} else {
					if ($fullPath) {
						$abs = \ze\link::absolute();
					} else {
						$abs = \ze\link::absoluteIfNeeded();
					}
				
					$url = $abs. $path. rawurlencode($safeName);

					if ($madeWebP) {
						$webPURL = $abs. $path. rawurlencode($webpName);
					}
				}
			
				if ($retina) {
					$width = (int) $finalImageWidth / 2;
					$height = (int) $finalImageHeight / 2;
				} else {
					$width = $finalImageWidth;
					$height = $finalImageHeight;
				}
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
					'width' => $maxWidth, 'height' => $maxHeight,
					'mode' => $canvas, 'offset' => $offset,
					'id' => $fileId, 'useCacheDir' => $useCacheDir];
		
			$url = 'zenario/file.php?usage=resize&c='. $hash. ($retina? '&retina=1' : ''). '&filename='. rawurlencode($safeName);
		
			if ($retina) {
				$width = (int) $finalImageWidth / 2;
				$height = (int) $finalImageHeight / 2;
			} else {
				$width = $finalImageWidth;
				$height = $finalImageHeight;
			}
			return true;
		}
	}



	//Formerly "imageLinkArray()"
	public static function imageLinkArray(
		$imageId, $maxWidth = 0, $maxHeight = 0, $canvas = 'resize', $offset = 0,
		$retina = false, $fullPath = false, $privacy = 'auto', $useCacheDir = true
	) {
		$details = [
			'alt' => '',
			'src' => '',
			'width' => '',
			'height' => ''];
	
		if (self::imageLink(
			$details['width'], $details['height'], $details['src'], $imageId, $maxWidth, $maxHeight, $canvas, $offset,
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
	
	public static function retinaImageLink(
		&$width, &$height, &$url, $imageId,
		$maxWidth = 0, $maxHeight = 0, $canvas = 'resize', $offset = 0,
		$retina = false, $fullPath = false, $privacy = 'auto', $useCacheDir = true
	) {
		return self::imageLink($width, $height, $url, $imageId, $maxWidth, $maxHeight, $canvas, $offset, true, $fullPath, $privacy, $useCacheDir);
	}

	//Formerly "itemStickyImageLink()"
	public static function itemStickyImageLink(
		&$width, &$height, &$url, $cID, $cType, $cVersion = false,
		$maxWidth = 0, $maxHeight = 0, $canvas = 'resize', $offset = 0,
		$retina = false, $fullPath = false, $privacy = 'auto', $useCacheDir = true
	) {
		if ($imageId = self::itemStickyImageId($cID, $cType, $cVersion)) {
			return self::imageLink($width, $height, $url, $imageId, $maxWidth, $maxHeight, $canvas, $offset, $retina, $fullPath, $privacy, $useCacheDir);
		}
		return false;
	}

	//Formerly "itemStickyImageLinkArray()"
	public static function itemStickyImageLinkArray(
		$cID, $cType, $cVersion = false,
		$maxWidth = 0, $maxHeight = 0, $canvas = 'resize', $offset = 0,
		$retina = false, $fullPath = false, $privacy = 'auto', $useCacheDir = true
	) {
		if ($imageId = self::itemStickyImageId($cID, $cType, $cVersion)) {
			return self::imageLinkArray($imageId, $maxWidth, $maxHeight, $canvas, $offset, $retina, $fullPath, $privacy, $useCacheDir);
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
							$extract = \ze\ring::encodeToUtf8(implode("\n", $extract));
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
								' -enc UTF-8 -raw -eol unix '.
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
										' -enc UTF-8 -raw -eol unix '.
										escapeshellarg($temp_pdf_file).
										' '.
										escapeshellarg($temp_file),
									$output, $return_var);
								}
							}
						
						
							if ($return_var == 0) {
								$extract = file_get_contents($temp_file);
								unlink($temp_file);
								
								// Get rid of hyphens where words break across a newline
								$extract = preg_replace ('/\xC2\xAD\n/', '', $extract);
							
								// Get rid of other characters which may show as Â or â
								$extract = preg_replace ('/[\xC2\xE2]/', '', $extract);
							
								$extract = trim(\ze\ring::encodeToUtf8($extract));

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
	public static function safeName($filename, $strict = false, $replaceSpaces = false) {
		
		if ($strict || $replaceSpaces) {
			$filename = str_replace(' ', '-', $filename);
		}
		
		if ($strict) {
			$filename = preg_replace('@[^\w\.-]@', '', $filename);
		} else {
			$filename = str_replace(['/', '\\', ':', ';', '*', '?', '"', '<', '>', '|'], '', $filename);
		}
		
		if ($filename === '') {
			$filename = 'noname';
		}
		if ($filename[0] === '.') {
			$filename[0] = 'noname';
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
	
	const ASPECT_RATIO_LIMIT_DEG = 10.0;
	public static function aspectRatioToDegrees($width, $height) {
		return atan2($width, $height) * 180 / M_PI;
	}
	
	//Quick and dirty little function to remove the the common factors from two numbers.
	//The numbers won't be very large so it needn't be super efficient
	public static function aspectRatioRemoveFactors($a, $b, $sensibleLimit) {
		
		$step = 1;
		$limit = min((int) floor(sqrt($a)), (int) floor(sqrt($b)));
		
		for ($i = 2; $i <= $limit; $i += $step) {
			while (($a % $i === 0) && ($b % $i === 0)) {
				$a = (int) ($a / $i);
				$b = (int) ($b / $i);
			}
			if ($i === 3) {
				$step = 2;
			}
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

		//Attempt to prevent "division by zero" error
		if (empty($imageWidth)) {
			$imageWidth = 1;
		}
		
		if (empty($imageHeight)) {
			$imageHeight = 1;
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
	public static function scaleImageDimensionsByMode(
		$mimeType, $imageWidth, $imageHeight,
		$maxWidth, $maxHeight, &$canvas, $offset,
		&$cropX, &$cropY, &$cropWidth, &$cropHeight, &$finalImageWidth, &$finalImageHeight,
		$fileId = 0
	) {
	
		$maxWidth = (int) $maxWidth;
		$maxHeight = (int) $maxHeight;
		
		//By default, only allow upscaling for SVGs.
		$allowUpscale = $isSVG = $mimeType == 'image/svg+xml';
		
		//Don't allow the "crop" modes for SVGs
		if ($isSVG) {
			switch ($canvas) {
				case 'crop_and_zoom':
				case 'resize_and_crop':
					$canvas = 'resize';
			}
		}
		
		//Most modes don't make use of the crop settings.
		$cropX = $cropY = 0;
		$cropWidth = $imageWidth;
		$cropHeight = $imageHeight;
		
		switch ($canvas) {
			//No limits, just leave the image size as it is
			case 'unlimited':
				$finalImageWidth = $imageWidth;
				$finalImageHeight = $imageHeight;
				break;
			
			//"adjust" is an alternate name for stretch
			case 'adjust':
			
			//Stretch the image to meet the requested width and height, without worrying
			//about maintaining aspect ratio, or DPI/resolution.
			//You might also use this mode if you've previously called the
			//scaleImageDimensionsByMode() or resizeImageString() function, and already
			//know the correct numbers, thus don't need to check them again.
			case 'stretch':
				$allowUpscale = true;
				$finalImageWidth = $maxWidth;
				$finalImageHeight = $maxHeight;
				break;
			
			//Crop and zoom mode. WiP.
			case 'crop_and_zoom':
				$allowUpscale = true;
				$finalImageWidth = $maxWidth;
				$finalImageHeight = $maxHeight;
				
				//
				//To do - if this image has some pre-determined crops,
				//pick the best fit here.
				//
				$bestCropX = 0;
				$bestCropY = 0;
				$bestCropWidth = $imageWidth;
				$bestCropHeight = $imageHeight;
				
				//Attempt to prevent "division by zero" error
				if (empty($maxWidth)) {
					$maxWidth = 1;
				}
				if (empty($maxHeight)) {
					$maxHeight = 1;
				}
				if (empty($bestCropWidth)) {
					$bestCropWidth = 1;
				}
				if (empty($bestCropHeight)) {
					$bestCropHeight = 1;
				}
				
				if (!empty($fileId)) {
					$desiredAspectRatioAngle = \ze\file::aspectRatioToDegrees($maxWidth, $maxHeight);
					$bestAspectRatioAngle = \ze\file::aspectRatioToDegrees($bestCropWidth, $bestCropHeight);
					
					$sql = "
						SELECT crop_x, crop_y, crop_width, crop_height, aspect_ratio_angle
						FROM ". DB_PREFIX. "cropped_images
						WHERE aspect_ratio_angle
							BETWEEN ". (float) ($desiredAspectRatioAngle - \ze\file::ASPECT_RATIO_LIMIT_DEG). "
								AND ". (float) ($desiredAspectRatioAngle + \ze\file::ASPECT_RATIO_LIMIT_DEG). "
						  AND image_id = ". (int) $fileId. "
						  AND ABS (aspect_ratio_angle - ". (float) $desiredAspectRatioAngle. ") <= ". (float) $bestAspectRatioAngle. "
						ORDER BY ABS (aspect_ratio_angle - ". (float) $desiredAspectRatioAngle. ") ASC
						LIMIT 1";
					
					if ($row = \ze\sql::fetchAssoc($sql)) {
						$bestCropX = $row['crop_x'];
						$bestCropY = $row['crop_y'];
						$bestCropWidth = $row['crop_width'];
						$bestCropHeight = $row['crop_height'];
						$bestAspectRatioAngle = $row['aspect_ratio_angle'];
					}
				}
				
				//Slightly reduce either the width or the height of the cropped section
				//to make sure that the resulting aspect ratio matches the aspect ratio requested.
				if (($maxWidth / $bestCropWidth) < ($maxHeight / $bestCropHeight)) {
					$desiredCropWidth = (int) ($bestCropHeight * $maxWidth / $maxHeight);
					$chipOffLeft = (int) (($bestCropWidth - $desiredCropWidth) / 2);
					$chipOffRight = $bestCropWidth - $desiredCropWidth - $chipOffLeft;
					
					$cropX = $bestCropX + $chipOffLeft;
					$cropY = $bestCropY;
					$cropWidth = $bestCropWidth - $chipOffLeft - $chipOffRight;
					$cropHeight = $bestCropHeight;
					
				} else {
					$desiredCropHeight = (int) ($bestCropWidth * $maxHeight / $maxWidth);
					$chipOffTop = (int) (($bestCropHeight - $desiredCropHeight) / 2);
					$chipOffBottom = $bestCropHeight - $desiredCropHeight - $chipOffTop;
					
					$cropX = $bestCropX;
					$cropY = $bestCropY + $chipOffBottom;
					$cropWidth = $bestCropWidth;
					$cropHeight = $bestCropHeight - $chipOffTop - $chipOffBottom;
				}
				break;
			
			//The resize and crop mode is what we used before we implemented
			//the crop and zoom mode.
			//It's basically a (mostly) unguided crop that tried to trim off the edge of the image
			//to fit the requested aspect ratio.
			//Also, another difference here is we care about DPI/resolution, whereas
			//crop and zoom mode doesn't.
			case 'resize_and_crop':
				//Attempt to prevent "division by zero" error
				if (empty($imageWidth)) {
					$imageWidth = 1;
				}
			
				if (empty($imageHeight)) {
					$imageHeight = 1;
				}
			
				if (($maxWidth / $imageWidth) < ($maxHeight / $imageHeight)) {
					$widthPreCrop = (int) ($imageWidth * $maxHeight / $imageHeight);
					$heightPreCrop = $maxHeight;
					$cropWidth = (int) ($maxWidth * $imageHeight / $maxHeight);
					$cropHeight = $imageHeight;
					$finalImageWidth = $maxWidth;
					$finalImageHeight = $maxHeight;
		
				} else {
					$widthPreCrop = $maxWidth;
					$heightPreCrop = (int) ($imageHeight * $maxWidth / $imageWidth);
					$cropWidth = $imageWidth;
					$cropHeight = (int) ($maxHeight * $imageWidth / $maxWidth);
					$finalImageWidth = $maxWidth;
					$finalImageHeight = $maxHeight;
				}
				
				if ($widthPreCrop != $finalImageWidth) {
					$cropX = (int) (((10 - $offset) / 20) * ($imageWidth - $cropWidth));

				} elseif ($heightPreCrop != $finalImageHeight) {
					$cropY = (int) ((($offset + 10) / 20) * ($imageHeight - $cropHeight));
				}
				
				break;
			
			//Max width/height mode are actually implemented by changing the settings,
			//then using resize mode.
			case 'fixed_width':
				$maxHeight = $allowUpscale? 999999 : $imageHeight;
				$canvas = 'resize';
				break;
			
			case 'fixed_height':
				$maxWidth = $allowUpscale? 999999 : $imageWidth;
				$canvas = 'resize';
			
			default:
				$canvas = 'resize';
		}
		
		//For "resize" mode, scale the image whilst maintaining aspect ratio
		if ($canvas == 'resize') {
			$finalImageWidth = false;
			$finalImageHeight = false;
			\ze\file::resizeImage($imageWidth, $imageHeight, $maxWidth, $maxHeight, $finalImageWidth, $finalImageHeight, $allowUpscale);
		}
	
		if ($cropWidth < 1) {
			$cropWidth = 1;
		}
		if ($finalImageWidth < 1) {
			$finalImageWidth = 1;
		}
	
		if ($cropHeight < 1) {
			$cropHeight = 1;
		}
		if ($finalImageHeight < 1) {
			$finalImageHeight = 1;
		}
	}

	//Formerly "resizeImageString()"
	public static function resizeImageString(
		&$image, $mimeType, &$imageWidth, &$imageHeight,
		$maxWidth, $maxHeight, $canvas = 'resize', $offset = 0
	) {
		//Work out the new width/height of the image
		$cropX = $cropY = $cropWidth = $cropHeight = $finalImageWidth = $finalImageHeight = false;
		\ze\file::scaleImageDimensionsByMode(
			$mimeType, $imageWidth, $imageHeight,
			$maxWidth, $maxHeight, $canvas, $offset,
			$cropX, $cropY, $cropWidth, $cropHeight, $finalImageWidth, $finalImageHeight
		);
	
		\ze\file::scaleImageToSize(
			$image, $mimeType,
			$cropX, $cropY, $cropWidth, $cropHeight, $finalImageWidth, $finalImageHeight
		);
	
		if (!is_null($image)) {
			$imageWidth = $finalImageWidth;
			$imageHeight = $finalImageHeight;
		}
	}

	public static function scaleImageToSize(
		&$image, $mimeType,
		$cropX, $cropY, $cropWidth, $cropHeight, $finalImageWidth, $finalImageHeight
	) {
		//Check if the image needs to be resized
		if ($cropX != 0
		 || $cropY != 0
		 || $cropWidth != $finalImageWidth
		 || $cropHeight != $finalImageHeight) {
			
			if (\ze\file::isImage($mimeType)) {
				
				\ze::ignoreErrors();
					
					//Load the original image into a canvas
					if ($image = @imagecreatefromstring($image)) {
						//Make a new blank canvas
						$trans = -1;
						$resized_image = imagecreatetruecolor($finalImageWidth, $finalImageHeight);
		
						//Transparent gifs need a few fixes. Firstly, we need to fill the new image with the transparent colour.
						if ($mimeType == 'image/gif') {
							$trans = imagecolortransparent($image);
							
							//N.b. check the transparrent colour ID is actually valid and in-range of the colour index.
							//Some GIF images can be corrupt come with bogus colour IDs. This will cause a fatal error in PHP 8.1 if we don't catch this.
							if ($trans >= 0
							 && $trans < imagecolorstotal($image)) {
								$colour = imagecolorsforindex($image, $trans);
								$trans = imagecolorallocate($resized_image, $colour['red'], $colour['green'], $colour['blue']);				
			
								imagefill($resized_image, 0, 0, $trans);				
								imagecolortransparent($resized_image, $trans);
							} else {
								$trans = -1;
							}
		
						//Transparent pngs should also be filled with the transparent colour initially.
						} elseif ($mimeType == 'image/png') {
							imagealphablending($resized_image, false); // setting alpha blending on
							imagesavealpha($resized_image, true); // save alphablending \ze::setting (important)
							$trans = imagecolorallocatealpha($resized_image, 255, 255, 255, 127);
							imagefilledrectangle($resized_image, 0, 0, $finalImageWidth, $finalImageHeight, $trans);
						}
		
						//Place a resized copy of the original image on the canvas of the new image
						imagecopyresampled($resized_image, $image, 0, 0, $cropX, $cropY, $finalImageWidth, $finalImageHeight, $cropWidth, $cropHeight);
		
						//The resize algorithm doesn't always respect the transparent colour nicely for gifs.
						//Solve this by resizing using a different algorithm which doesn't do any anti-aliasing, then using
						//this to create a transparent mask. Then use the mask to update the new image, ensuring that any pixels
						//that should be transparent actually are.
						if ($mimeType == 'image/gif') {
							if ($trans >= 0) {
								$mask = imagecreatetruecolor($finalImageWidth, $finalImageHeight);
								imagepalettecopy($image, $mask);
				
								imagefill($mask, 0, 0, $trans);				
								imagecolortransparent($mask, $trans);
				
								imagetruecolortopalette($mask, true, 256); 
								imagecopyresampled($mask, $image, 0, 0, $cropX, $cropY, $finalImageWidth, $finalImageHeight, $cropWidth, $cropHeight);
				
								$maskTrans = imagecolortransparent($mask);
								for ($y = 0; $y < $finalImageHeight; ++$y) {
									for ($x = 0; $x < $finalImageWidth; ++$x) {
										if (imagecolorat($mask, $x, $y) === $maskTrans) {
											imagesetpixel($resized_image, $x, $y, $trans);
										}
									}
								}
							}
						}
		
		
						$temp_file = tempnam(sys_get_temp_dir(), 'Img');
							if ($mimeType == 'image/gif') imagegif($resized_image, $temp_file);
							if ($mimeType == 'image/png') imagepng($resized_image, $temp_file);
							if ($mimeType == 'image/jpeg') imagejpeg($resized_image, $temp_file, $jpeg_quality = 100);
			
							imagedestroy($resized_image);
							unset($resized_image);
							$image = file_get_contents($temp_file);
						unlink($temp_file);
					} else {
						$image = null;
					}
					
				\ze::noteErrors();
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

	public static function moveFileFromDBToDocstore(&$path, $fileId, $filename, $checksum) {
		$location = self::docstorePath($fileId);

		if (
			is_dir($dir = \ze::setting('docstore_dir'). '/')
			&& is_writable($dir)
			&& (
				(is_dir($dir = $dir. ($path = preg_replace('/\W/', '_', $filename). '_'. $checksum). '/'))
				|| (mkdir($dir) && \ze\cache::chmod($dir, 0777))
			)
		) {
	
			if (file_exists($dir. $filename)) {
				unlink($dir. $filename);
			}
	
			copy($location, $dir. $filename);
			\ze\cache::chmod($dir. $filename, 0666);

			return true;
		}

		return false;
	}

	public static function formatSizeUnits($bytes) {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' ' . \ze\lang::phrase('_FILE_SIZE_UNIT_GB');
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' ' . \ze\lang::phrase('_FILE_SIZE_UNIT_MB');
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' ' . \ze\lang::phrase('_FILE_SIZE_UNIT_KB');
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' ' . \ze\lang::phrase('_FILE_SIZE_UNIT_BYTES');
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' ' . \ze\lang::phrase('_FILE_SIZE_UNIT_BYTE');
        } else {
            $bytes = '0 ' . \ze\lang::phrase('_FILE_SIZE_UNIT_BYTES');
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

	public static function convertToWebP($pathIn, $pathOut, $mimeType) {
		
		if (!is_callable('imagewebp')) {
			return false;
		}
		
		switch ($mimeType) {
			case 'image/jpeg':
				$img = imagecreatefromjpeg($pathIn);
				break;
			
			case 'image/png':
				$img = imagecreatefrompng($pathIn);

				imagepalettetotruecolor($img);
				imagealphablending($img, true);
				imagesavealpha($img, true);
				break;
			
			default:
				return false;
		}
		
		//In 9.2, a WebP quality slider was introduced.
		//Set a fallback value before the DB update is applied
		//to avoid a bug with very poor quality images.
		$quality = (int) \ze::setting('webp_quality');
		if (!$quality) {
			$quality = 90;
		}
		
		if (imagewebp($img, $pathOut, $quality)) {
			\ze\cache::chmod($pathOut, 0666);

			if (filesize($pathOut) % 2 == 1) {
				file_put_contents($pathOut, "\0", FILE_APPEND);
			}

			imagedestroy($img);
		
			return true;
		} else {
			return false;
		}
	}
}
