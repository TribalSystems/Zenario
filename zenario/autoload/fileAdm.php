<?php 
/*
 * Copyright (c) 2025, Tribal Limited
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

class fileAdm {


	
	public static function addToDocstoreDir($usage, $location, $filename = false, $mustBeAnImage = false, $deleteWhenDone = true) {
		return \ze\fileAdm::addToDatabase($usage, $location, $filename, $mustBeAnImage, $deleteWhenDone, true);
	}

	public static function addFromString($usage, &$contents, $filename, $mustBeAnImage = false, $addToDocstoreDirIfPossible = false, $imageCredit = '') {
	
		if ($temp_file = tempnam(sys_get_temp_dir(), 'cpy')) {
			if (file_put_contents($temp_file, $contents)) {
				return \ze\fileAdm::addToDatabase($usage, $temp_file, $filename, $mustBeAnImage, true, $addToDocstoreDirIfPossible, false, false, false, false, $imageCredit);
			}
		}
	
		return false;
	}

	public static function addToDatabase(
		$usage, $location, $filename = false,
		$mustBeAnImage = false, $deleteWhenDone = false, $addToDocstoreDirIfPossible = false,
		$imageAltTag = false, $imageTitle = false, $imagePopoutTitle = false, $imageMimeType = false, $imageCredit = '', $setPrivacy = null
	) {
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
			
					//If this file is already stored, just update the name
					$path = false;
					if ($existingFile['location'] == 'db' || ($path = \ze\file::docstorePath($existingFile['path']))) {
						//If the name has changed, attempt to rename the file in the filesystem
						/*if ($path && $file['filename'] != $existingFile['filename']) {
							@rename($path, \ze::setting('docstore_dir'). '/'. $existingFile['path']. '/'. $file['filename']);
						}
				
						*/
						\ze\row::update('files', ['filename' => $filename], $key);
						if ($deleteWhenDone) {
							unlink($location);
						}
				
						return $existingFile['id'];
					}
				}
			}
		
		//Otherwise we must insert the new file
		} else {
			if (!is_null($setPrivacy)) {
				$file['privacy'] = $setPrivacy;
			} else {
				$file['privacy'] = \ze::oneOf(\ze::setting('default_image_privacy'), 'auto', 'public', 'private');
			}
		}
		
		if ($usage == 'site_setting') {
			$file['privacy'] = 'public';
		}



		//Check if the file is an image and get its meta info
		//(Note this logic is the same as in \ze\fileAdm::check(), except it also saves the meta info.)
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
						\ze\image::resize($file[$c[0]], $file['mime_type'], $file[$c[1]], $file[$c[2]], $c[3], $c[4]);
					}
				}
	
			} else {
				if (function_exists('simplexml_load_string')) {
					//Try and get the width and height of the SVG from its metadata.
					$rtn = \ze\fileAdm::getWidthAndHeightOfSVG($file, file_get_contents($location));
				
					//If PHP's simplexml_load_string function isn't callable allow this and continue.
					//However if the funciton was callable and couldn't parse the file, don't allow this
					//and reject the file.
					if (!$rtn) {
						return false;
					}
				}
			}
	
			if ($imageAltTag) {
				$altTag = $imageAltTag;
			} else {
				if ($usage == 'site_setting') {
					$altTag = '';
				} else {
					$filenameArray = explode('.', $filename);
					$altTag = self::generateAltTagFromFilename($filename);
				}
			}
			
			if (strlen($altTag) > 125) {
				$altTag = substr($altTag, 0, 124);
			}
			
			$file['alt_tag'] = $altTag;
		
		//Have some special handling to try and get metadata from other types of file
		} else {
			switch ($file['mime_type']) {
				case 'image/icon':
				case 'image/x-icon':
					//Attempt to read the header of a .ico file and get the image size from it.
					$data = file_get_contents($location);
					
					if (empty($data)) {
						break;
					}
					
					$iconsMetadata = unpack("Sreserved/Stype/Scount", $data);
					
					if (empty($iconsMetadata)) {
						break;
					}
					$data = substr($data, 6);
					
					$largestWidth = $largestHeight = 0;
					for ($i = 0; $i < $iconsMetadata['count']; ++$i) {
						$iconMetadata = unpack("Cwidth/Cheight/Ccolours/Creserved/Splanes/SbitCount/Lsize/LfOffset", $data);
						
						if (empty($iconMetadata)) {
							break;
						}
						$data = substr($data, 16);
						
						$width = (int) ($iconMetadata['width'] ?? 0);
						$height = (int) ($iconMetadata['height'] ?? 0);
						
						if ($width
						 && $largestWidth < $width) {
							$largestWidth = $width;
							$largestHeight = $height;
						}
					}
					
					if ($largestWidth) {
						$file['width'] = $largestWidth;
						$file['height'] = $largestHeight;
					}
				break;
			}
		}


		$file['created_datetime'] = \ze\date::now();
		
		//Assume we're storing this file in the database to start with, but change these settings later if needed.
		$file['location'] = 'db';
		$file['path'] = '';
		$file['data'] = null;
		
		//Save the file in the database so we can generate a short checksum for it
		$fileId = \ze\row::set('files', $file, $key);
		\ze\fileAdm::updateShortChecksums();
		
		$path = $filePath = false;
		if ($addToDocstoreDirIfPossible
		 && ($file['short_checksum'] = \ze\row::get('files', 'short_checksum', $fileId))
		 && (\ze\fileAdm::createDocstoreDir($file, $path, $filePath))) {
	
			if ($deleteWhenDone) {
				rename($location, $filePath);
				\ze\cache::chmod($filePath, 0666);
			} else {
				copy($location, $filePath);
				\ze\cache::chmod($filePath, 0666);
			}
			
			\ze\row::update('files', ['location' => 'docstore', 'path' => $path], $fileId);

		} else {
			\ze\row::update('files', ['data' => file_get_contents($location)], $fileId);

			if ($deleteWhenDone) {
				unlink($location);
			}
		}

		return $fileId;
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
					$sql = "SELECT IFNULL(MAX(id), 0) + 1 FROM ". DB_PREFIX. "files";
					$result = \ze\sql::select($sql);
					$row = \ze\sql::fetchRow($result);
					$filename = 'image_'. $row[0]. '.'. $ext;
				
					$data = base64_decode($data);
				
					if ($fileId = \ze\fileAdm::addFromString($usage, $data, $filename, $mustBeAnImage = true)) {
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
				return \ze\fileAdm::addFromString($usage, $data, ($filename ?: $file['filename']), $mustBeAnImage, $addToDocstoreDirIfPossible, $file['image_credit']);
		
			} elseif ($file['location'] == 'docstore' && ($location = \ze\file::docstorePath($file['path']))) {
				return \ze\fileAdm::addToDatabase($usage, $location, ($filename ?: $file['filename']), $mustBeAnImage, $deleteWhenDone = false, $addToDocstoreDirIfPossible = true, false, false, false, false, $file['image_credit']);
			}
		}
	
		return false;
	}


	//Delete a file from the database, and anywhere it was stored on the disk
	public static function delete($fileId) {
	
		if ($file = \ze\row::get('files', ['path', 'mime_type', 'short_checksum', 'usage'], $fileId)) {
	
			//If the file was being stored in the docstore and nothing else uses it...
			if ($file['path']
			 && !\ze\row::exists('files', ['id' => ['!' => $fileId], 'path' => $file['path']])) {
				//...then delete that directory from the docstore
				\ze\cache::deleteDir(\ze::setting('docstore_dir'). '/'. $file['path']);
			}
		
			//If the file was an image and there's no other copies with a different usage
			if (\ze\file::isImageOrSVG($file['mime_type'])
			 && !\ze\row::exists('files', ['id' => ['!' => $fileId], 'short_checksum' => $file['short_checksum']])) {
				//...then delete it from the public/images/ directory
				\ze\file::deletePublicImage($file);
			}
		
			\ze\row::delete('files', $fileId);
			
			//Delete the linked row from the file_extracts table as well if it exists
			\ze\row::delete('file_extracts', ['file_id' => $fileId]);
		}
	}

	public static function deleteSpecialImage($fileId) {
		\ze\file::deletePublicImage($fileId, $specialImage = true);
		\ze\row::delete('files', $fileId);
	}

	public static function deleteMediaContentItemFileIfUnused($cID, $cType, $fileId) {
		if ($cID && $cType && $fileId) {
			//Check if the file is used by other content items...
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
			
			//Please note: before 10.2, this logic would also check if any hierarchical document uses the same file.
			//As of 10.2, that logic is removed, as docstore is now split into folders named after the `usage` column.
			//Doc content items and hierarchical documents are in separate pools.
			if (!$usage) {
				\ze\fileAdm::delete($fileId);
				return true;
			}
		}

		return false;
	}
	
	
	//Try and get the width and height of a SVG from its metadata.
	public static function getWidthAndHeightOfSVG(&$file, $data) {
			
		//For SVGs, try to read the metadata from the image and get the width and height from it.
		\ze::ignoreErrors();
			$svg = simplexml_load_string($data);
		\ze::noteErrors();
		
		if ($svg) {
			//There are lots of possible formats for this to watch out for.
			//I've tried to be very flexible and code support for as many as I know.
			//This code has support for setting the width and height in the following formats:
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
	

	public static function docstoreDirPath($file) {
		return $file['usage']. '/'. preg_replace('@[^\w\.-]+@', '_', $file['filename']). '-'. $file['short_checksum'];
	}

	public static function createDocstoreDir($file, &$path, &$filePath) {
		
		$docStoreDir = \ze::setting('docstore_dir'). '/';
		
		if (!is_dir($docStoreDir)
		 || !is_writable($docStoreDir)) {
			return false;
		}
		
		$usageDir = $docStoreDir. $file['usage']. '/';
		
		if (!is_dir($usageDir)) {
			if (!(mkdir($usageDir) && \ze\cache::chmod($usageDir, 0777))) {
				return false;
			}
		}
		
		$path = \ze\fileAdm::docstoreDirPath($file);
		$pathInDocstoreDir = $docStoreDir. $path. '/';
		
		if (!is_dir($pathInDocstoreDir)) {
			if (!(mkdir($pathInDocstoreDir) && \ze\cache::chmod($pathInDocstoreDir, 0777))) {
				return false;
			}
		}
		
		$filePath = $pathInDocstoreDir. \ze\file::safeName($file['filename']);
		
		if (file_exists($filePath)) {
			unlink($filePath);
		}
		
		return true;
	}



	//Generic handler for misc. AJAX requests from admin boxes
	public static function handleAdminBoxAJAX() {
	
		if (\ze::request('fileUpload')) {
		
			\ze\fileAdm::exitIfUploadError(true, true, true, 'Filedata');
			\ze\fileAdm::putUploadFileIntoCacheDir($_FILES['Filedata']['name'], $_FILES['Filedata']['tmp_name'], \ze::request('_html5_backwards_compatibility_hack'));
	
		} else {
			exit;
		}
	}

	//See also: function \ze\file::getPathOfUploadInCacheDir()

	public static function putUploadFileIntoCacheDir(
		$filename, $tempnam, $html5_backwards_compatibility_hack = false,
		$cacheFor = false, $isAllowed = null
	) {
		
		//Catch the case where the browser or the server URLencoded the filename
		$filename = rawurldecode($filename);
		
		if (is_null($isAllowed)) {
			$isAllowed = \ze\file::isAllowed($filename);
		}
		
		if (!$isAllowed) {
			echo
				\ze\admin::phrase('You must select a known file format, for example .doc, .docx, .jpg, .pdf, .png or .xls.'), 
				"\n\n",
				\ze\admin::phrase('To add a file format to the known file format list, go to "Configuration -> File/MIME Types" in Organizer.'),
				"\n\n",
				\ze\admin::phrase('Please also check that your filename does not contain any of the following characters: ' . "\n" . '\\ / : ; * ? " < > |');
			exit;
		}
	
		if ($tempnam) {
			$sha = sha1_file($tempnam);
		} else {
			exit;
		}
	
		if (!\ze\cache::cleanDirs()
		 || !($dir = \ze\cache::createDir($sha, 'private/uploads', false))) {
			echo
				\ze\admin::phrase('Zenario cannot currently receive uploaded files, because one of the
cache/, public/ or private/ directories is not writeable.

To correct this, please ask your system administrator to perform a
"chmod 777 cache/ public/ private/" to make them writeable.
(If that does not work, please check that all subdirectories are also writeable.)');
			exit;
		}
	
		$file = [];
		$file['filename'] = \ze\file::safeName($filename);
	
		//Check if the file is already uploaded
		if (!file_exists($path = CMS_ROOT. $dir. $file['filename'])
		 || !filesize($path = CMS_ROOT. $dir. $file['filename'])) {
		
			\ze\fileAdm::moveUploadedFile($tempnam, $path);
		}
		
		$mimeType = \ze\file::mimeType($file['filename']);
		
		if (\ze\file::isImage($mimeType)
		 && ($image = @getimagesize($path))) {
			$file['width'] = $image[0];
			$file['height'] = $image[1];
		
			$file['id'] = \ze\ring::encodeIdForOrganizer($sha. '/'. $file['filename']. '/'. $file['width']. '/'. $file['height']);
		
		} else
		if ($mimeType == 'image/svg+xml'
		 && \ze\fileAdm::getWidthAndHeightOfSVG($file, file_get_contents($path))) {
			$file['id'] = \ze\ring::encodeIdForOrganizer($sha. '/'. $file['filename']. '/'. $file['width']. '/'. $file['height']);
		
		} else {
			$file['id'] = \ze\ring::encodeIdForOrganizer($sha. '/'. $file['filename']);
		}
		
		$file['link'] = 'zenario/preview_uploaded_file.php?uploadCode='. $file['id'];
		if ($cacheFor) {
			$file['link'] .= '&cacheFor=' . (int)$cacheFor;
		}
		
		if ($html5_backwards_compatibility_hack) {
			echo '
				<html>
					<body>
						<script type="text/javascript">
							self.parent.zenarioAB.uploadComplete([', json_encode($file), ']);
						</script>
					</body>
				</html>';
		} else {
			header('Content-Type: text/javascript; charset=UTF-8');
			//\ze\ray::jsonDump($tags);
			echo json_encode($file);
		}
	
		exit;
	}







	public static function updateShortChecksums() {
	
		//Attempt to fill in any missing short checksums
		$sql = "
			UPDATE IGNORE ". DB_PREFIX. "files
			SET short_checksum = SUBSTR(checksum, 1, ". (int) \ze::setting('short_checksum_length'). ")
			WHERE short_checksum IS NULL";
		\ze\sql::update($sql);	
	
		//Check for a unique key error (i.e. one or more short checksums were left as null)
		if (\ze\row::exists('files', ['short_checksum' => null])) {
		
			//Handle the problem by increasing the short checksum length and trying again
			\ze\site::setSetting('short_checksum_length', 1 + (int) \ze::setting('short_checksum_length'));
			\ze\fileAdm::updateShortChecksums();
		}
	}






	//Handle some common errors when uploading a file.
		//$adminFacing should be set to true to use admin phrases or false to use visitor phrases.
		//If $checkIsAllowed is set then only files in the document_types table with the is_allowed flag set will be allowed
		//If $alwaysAllowImages is set then only GIF/JPG/PNG/SVG files will be accepted
		//If both are set then a file will be accepted if it's an image OR allowed in the document_types table.
		//$fileVar is the name of the file upload field, usually 'Filedata'.
	public static function exitIfUploadError($adminFacing, $checkIsAllowed = true, $alwaysAllowImages = false, $fileVar = null, $i = null, $doVirusScan = true) {
		
		//If a variable name isn't specified, loop through checking all of the variables
		if ($fileVar === null) {
			foreach ($_FILES as $fileVar => $file) {
				\ze\fileAdm::exitIfUploadError($adminFacing, $checkIsAllowed, $alwaysAllowImages, $fileVar);
			}
			return;
		}
		
		//Catch the case where index numbers are being used, but an index number isn't specified.
		//Loop through checking all of the index numbers
		if ($i === null && is_array($_FILES[$fileVar]['name'])) {
			foreach ($_FILES[$fileVar]['name'] as $i => $file) {
				\ze\fileAdm::exitIfUploadError($adminFacing, $checkIsAllowed, $alwaysAllowImages, $fileVar, $i);
			}
			return;
		}
			
		//Handle the two possible different formats in $_FILE, one with index numbers and one without
		if ($i === null) {
			$error = $_FILES[$fileVar]['error'] ?? false;
			$name = $_FILES[$fileVar]['name'];
			$path = $_FILES[$fileVar]['tmp_name'];
		} else {
			$error = $_FILES[$fileVar]['error'][$i] ?? false;
			$name = $_FILES[$fileVar]['name'][$i];
			$path = $_FILES[$fileVar]['tmp_name'][$i];
		}
		
		if ($adminFacing) {
			$moduleClass = false;
		} else {
			$moduleClass = 'zenario_common_features';
		}
		
		//There are site settings to limit max user image upload sizes, or location image max upload sizes.
		//They may be applied to front-end interfaces only, or admin interfaces as well.
		//Check if there is an admin interface limit.
		$pathRequest = '';
		$method_call = \ze::request('method_call');
		if ($method_call == 'handleAdminBoxAJAX' || $method_call == 'handlePluginAJAX') {
			$pathRequest = \ze::request('path');
		} elseif ($method_call == 'handleOrganizerPanelAJAX') {
			$pathRequest = \ze::request('__path__');
		}
		
		if ($pathRequest) {
			//Location image max upload size
			if ($pathRequest == 'zenario_location_manager__location') {
				if (\ze::setting('max_location_image_filesize_override') && (\ze::setting('apply_file_size_limit_to') == 'always_apply')) {
					$locationManagerMaxFilesizeValue = \ze::setting('max_location_image_filesize');
					$locationManagerMaxFilesizeUnit = \ze::setting('max_location_image_filesize_unit');
					$locationManagerMaxFilesize = \ze\file::fileSizeBasedOnUnit($locationManagerMaxFilesizeValue, $locationManagerMaxFilesizeUnit);
					
					if ($_FILES[$fileVar]['size'] > $locationManagerMaxFilesize) {
						echo \ze\admin::phrase(
							'This file is too large, and exceeds the file size limit of [[maxUploadSizeFormatted]]. (This limit is determined in site settings for Locations.)',
							['maxUploadSizeFormatted' => $locationManagerMaxFilesizeValue . " " . $locationManagerMaxFilesizeUnit]
						);
						exit;
					}
				}
			//User image max upload size
			} elseif ($pathRequest == 'zenario__users/panels/users' || $pathRequest == 'zenario__users/panels/hierarchy') {
				if (\ze::setting('max_user_image_filesize_override') && (\ze::setting('apply_file_size_limit_to') == 'always_apply')) {
					$userImageMaxFilesizeValue = \ze::setting('max_user_image_filesize');
					$userImageMaxFilesizeUnit = \ze::setting('max_user_image_filesize_unit');
					$userImageMaxFilesize = \ze\file::fileSizeBasedOnUnit($userImageMaxFilesizeValue, $userImageMaxFilesizeUnit);
					
					if ($_FILES[$fileVar]['size'] > $userImageMaxFilesize) {
						echo \ze\admin::phrase(
							'This file is too large, and exceeds the file size limit of [[maxUploadSizeFormatted]]. (This limit is determined in site settings for User and Contact data.)',
							['maxUploadSizeFormatted' => $userImageMaxFilesizeValue . " " . $userImageMaxFilesizeUnit]
						);
						exit;
					}
				}
			} else {
				//This logic will run in a TUIX file picker
				$apacheMaxFilesizeInBytes = \ze\dbAdm::apacheMaxFilesize();
				$apacheMaxFilesizeFormatted = \ze\file::fileSizeConvert($apacheMaxFilesizeInBytes);

				$zenarioMaxFilesizeValue = \ze::setting('content_max_filesize');
				$zenarioMaxFilesizeUnit = \ze::setting('content_max_filesize_unit');
				$zenarioMaxFilesizeInBytes = \ze\file::fileSizeBasedOnUnit($zenarioMaxFilesizeValue, $zenarioMaxFilesizeUnit);
				$zenarioMaxFilesizeFormatted = $zenarioMaxFilesizeValue . ' ' .  $zenarioMaxFilesizeUnit;

				if ($_FILES[$fileVar]['size'] ?? false) {
					if ($_FILES[$fileVar]['size'] > $apacheMaxFilesizeInBytes || $_FILES[$fileVar]['size'] > $zenarioMaxFilesizeInBytes) {
						if ($apacheMaxFilesizeInBytes < $zenarioMaxFilesizeInBytes) {
							$maxUploadableSizeFormatted = $apacheMaxFilesizeFormatted;
						} else {
							$maxUploadableSizeFormatted = $zenarioMaxFilesizeFormatted;
						}
		
						echo \ze\lang::phrase(
							'Your file was too large to be uploaded. The maximum uploadable size is [[max_uploadable_file_size]].',
							['max_uploadable_file_size' => $maxUploadableSizeFormatted],
							$moduleClass
						);
						exit;
					}
				}
			}
		}
		
		switch ($_FILES[$fileVar]['error'] ?? false) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				//This logic runs on a form submission
				$apacheMaxFilesizeInBytes = \ze\dbAdm::apacheMaxFilesize();
				$apacheMaxFilesizeFormatted = \ze\file::fileSizeConvert($apacheMaxFilesizeInBytes);

				$zenarioMaxFilesizeValue = \ze::setting('content_max_filesize');
				$zenarioMaxFilesizeUnit = \ze::setting('content_max_filesize_unit');
				$zenarioMaxFilesizeInBytes = \ze\file::fileSizeBasedOnUnit($zenarioMaxFilesizeValue, $zenarioMaxFilesizeUnit);
				$zenarioMaxFilesizeFormatted = $zenarioMaxFilesizeValue . ' ' .  $zenarioMaxFilesizeUnit;

				if ($apacheMaxFilesizeInBytes < $zenarioMaxFilesizeInBytes) {
					$maxUploadableSizeFormatted = $apacheMaxFilesizeFormatted;
				} else {
					$maxUploadableSizeFormatted = $zenarioMaxFilesizeFormatted;
				}

				echo \ze\lang::phrase(
					'Your file was too large to be uploaded. The maximum uploadable size is [[max_uploadable_file_size]].',
					['max_uploadable_file_size' => $maxUploadableSizeFormatted],
					$moduleClass
				);
				exit;
			case UPLOAD_ERR_PARTIAL:
			case UPLOAD_ERR_NO_FILE:
				echo \ze\lang::phrase('There was a problem whilst uploading your file.', false, $moduleClass);
				exit;
			
			//I've never seen these happen before, if we ever see these messages we can debug them and add a friendlier message at that point
			case UPLOAD_ERR_NO_TMP_DIR:
				echo 'UPLOAD_ERR_NO_TMP_DIR';
				exit;
			case UPLOAD_ERR_CANT_WRITE:
				echo 'UPLOAD_ERR_CANT_WRITE';
				exit;
			case UPLOAD_ERR_EXTENSION:
				echo 'UPLOAD_ERR_EXTENSION';
				exit;
		}
		
		
		if ($checkIsAllowed) {
			if (!\ze\file::isAllowed($name, $alwaysAllowImages)) {
				if ($adminFacing) {
					echo
						\ze\admin::phrase('You must select a known file format, for example .doc, .docx, .jpg, .pdf, .png or .xls.'), 
						"\n\n",
						\ze\admin::phrase('To add a file format to the known file format list, go to "Configuration -> File/MIME Types" in Organizer.'),
						"\n\n",
						\ze\admin::phrase('Please also check that your filename does not contain any of the following characters: ' . "\n" . '\\ / : ; * ? " < > |');
					exit;
				} else {
					echo
						\ze\lang::phrase('The uploaded file is not a supported file format.', false, $moduleClass);
					exit;
				}
			}
		
		} elseif ($alwaysAllowImages) {
			if (!\ze\file::isImageOrSVG(\ze\file::mimeType($name))) {
				echo
					\ze\lang::phrase('The uploaded image is not in a supported format.', false, $moduleClass),
					"\n\n",
					\ze\lang::phrase('Please upload an image in GIF, JPEG, PNG or SVG format. The file extension should be either .gif, .jpg, .jpeg, .png or .svg.', false, $moduleClass);
				exit;
			}
		}
		
		if ($doVirusScan) {
			\ze\fileAdm::exitIfVirusInFile($adminFacing, $path, $name, true);
		}
		
		//Any SVGs that are uploaded should be sanitsed as a precaution against XSS attacks.
		if (\ze\file::mimeType($name) == 'image/svg+xml') {
			if (is_writable($path)) {
				require_once CMS_ROOT. 'zenario/libs/yarn/SVG-Sanitizer/SvgSanitizer.php';
				$SvgSanitizer = new \SvgSanitizer();
				$SvgSanitizer->load($path);
				$SvgSanitizer->sanitize();
				$SvgSanitizer->save($path);
			}
		
		} elseif (\ze\file::isImage(\ze\file::mimeType($name))) {
			\ze::ignoreErrors();
				$size = getimagesize($path);
			\ze::noteErrors();
			
			$size['maxImageWidth'] = \ze::setting('max_image_width') ?: 6000;
			$size['maxImageHeight'] = \ze::setting('max_image_height') ?: 6000;
			
			if ($size && $size[0] > $size['maxImageWidth'] && $size[1] > $size['maxImageHeight']) {
				echo
					\ze\lang::phrase('The uploaded image is [[0]] × [[1]]. The largest allowed size is [[maxImageWidth]] × [[maxImageHeight]].', $size, $moduleClass);
				exit;
			}
		}
	}
	
	public static function exitIfVirusInFile($adminFacing, $path, $name, $autoDelete = false) {
		
		if ($adminFacing) {
			$moduleClass = false;
		} else {
			$moduleClass = 'zenario_common_features';
		}
		
		//If it looks like the file is in the /tmp/ directory, move it into the cache/scans/ directory.
		//This is to avoid issues with AppArmor, and/or multiple different instances of the /tmp/ directory in AWS.
		$inTmpDir = substr($path, 0, 4) == '/tmp';
		if ($inTmpDir) {
			$scanDir = \ze\cache::createRandomDir(10, 'cache/scans', false);
			$scanPath = $scanDir. 'scanme';
			
			if (!\ze\fileAdm::moveUploadedFile($path, $scanPath)) {
				echo \ze\lang::phrase('Invalid uploaded file.', false, $moduleClass);
				exit;
			}
			
			//Try to run an anti-virus scan
			$avScan = \ze\server::antiVirusScan($scanPath, $autoDelete);
			
			//Move the file back to the tmp dir if we moved it earlier.
			if ($autoDelete && $avScan === false) {
				//Though don't do this if the autodelete option would have triggered
			} else {
				@rename($scanPath, $path);
			}
			//Also clear up the directory we made in the cache folder
			@unlink($scanDir. 'accessed');
			@rmdir($scanDir);
		
		} else {
			//Try to run an anti-virus scan with the file where it is
			$avScan = \ze\server::antiVirusScan($path, $autoDelete);
		}
		
		if ($avScan === false) {
			echo \ze\lang::phrase('A virus was detected in [[name]]. It cannot be accepted.', ['name' => $name], $moduleClass);
			exit;
		}
		
		if ($avScan === null && \ze::setting('require_av_scan')) {
			
			if ($autoDelete) {
				@unlink($path);
			}
			
			echo \ze\lang::phrase('This site cannot currently accept file uploads as antivirus scanning is currently unavailable.', false, $moduleClass);
			exit;
		}
		
		//For file-types supported by the ze\fileAdm::check function, run a check to see if the extension looks correct
		//(I.e. try to check that this isn't a different type of file where the extension has been altered.)
		$fileCheck = \ze\fileAdm::check($path, $mimeType = \ze\file::mimeType($name));
		if (\ze::isError($fileCheck)) {
			
			if (isset($fileCheck->errors['PASSWORD_PROTECTED'])) {
				echo \ze\lang::phrase('The file "[[filename]]" is password-protected. Password protection needs to be removed before you can upload it to Zenario.', ['filename' => $name], $moduleClass);
			
			} else {
				$parts = explode('.', $name);
				$extension = $parts[count($parts) - 1];
				$mrg = [
					'name' => $name,
					'mimeType' => $mimeType,
					'extension' => $extension
				];
			
				echo
					\ze\lang::phrase('This file could not be uploaded.', [], $moduleClass),
					"\n\n";
				
				if (isset($fileCheck->errors['MISSNAMED_GIF'])) {
					echo \ze\lang::phrase('According to its name, "[[name]]" should be a [[extension]] file, but from scanning its contents it appears to be a GIF, so its extension must be .gif (upper or lower case).', $mrg, $moduleClass);
				
				} elseif (isset($fileCheck->errors['MISSNAMED_JPG'])) {
					echo \ze\lang::phrase('According to its name, "[[name]]" should be a [[extension]] file, but from scanning its contents it appears to be a JPEG, so its extension must be .jpg or .jpeg (upper or lower case).', $mrg, $moduleClass);
				
				} elseif (isset($fileCheck->errors['MISSNAMED_WEBP'])) {
					echo \ze\lang::phrase('According to its name, "[[name]]" should be a [[extension]] file, but from scanning its contents it appears to be a WebP, so its extension must be .webp (upper or lower case).', $mrg, $moduleClass);
				
				} elseif (isset($fileCheck->errors['MISSNAMED_PNG'])) {
					echo \ze\lang::phrase('According to its name, "[[name]]" should be a [[extension]] file, but from scanning its contents it appears to be a PNG, so its extension must be .png (upper or lower case).', $mrg, $moduleClass);
				
				} elseif (isset($fileCheck->errors['MISSNAMED_SVG'])) {
					echo \ze\lang::phrase('According to its name, "[[name]]" should be a [[extension]] file, but from scanning its contents it appears to be an SVG, so its extension must be .svg (upper or lower case).', $mrg, $moduleClass);
				
				} else {
					echo \ze\lang::phrase('According to its name, "[[name]]" should be a file of type [[extension]], but from scanning its contents it failed to match "[[mimeType]]".', $mrg, $moduleClass);
				}
				
				echo
					"\n\n",
					\ze\lang::phrase('This could be because the file has been corrupted, or you could have renamed the extension by mistake.', [], $moduleClass);
				
				if (\ze::isAdmin()) {
					echo
						"\n\n",
						\ze\admin::phrase('Developers: if this message constantly occurs, even on valid files, then this is probably a misclassification in the UNIX file utility. You can fix this by adding an exception/correction in the function check() in zenario/autoload/file.php.');
				}
			}
			
			exit;
		}
	}
	
	
	public static function scanMimeType($filepath) {
		return exec('file --mime-type --brief '. escapeshellarg($filepath));
	}
	
	
	//Attempt to check if the contents of a file match the file
	public static function check($filepath, $mimeType = null, $allowPasswordProtectedOfficeDocs = false) {
		
		if ($mimeType === null) {
			$mimeType = \ze\file::mimeType($filepath);
		}
		
		//Check to see if we have access to the file utility in UN*X
		if (!\ze\server::isWindows() && \ze\server::execEnabled()) {
			
			//Attempt to call the file program to check what mime-type it thinks this file should be.
			//Note that our check using \ze\file::mimeType() just checks the file extension and nothing else.
			//The file program is a little more sophisticated and does some basic checks on the file's contents as well.
			if (!$scannedMimeType = \ze\fileAdm::scanMimeType($filepath)) {
				return \ze\file::genericCheckError($filepath);
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
					return \ze\file::genericCheckError($filepath);
				}
			}
			
			//For images, enforce that the exact format of the image's contents matches what the file
			//extension says it should be. E.g. don't allow .PNGs renamed to .JPGs
			if ($basicType == 'image'
			 && $scannedBasicType == 'image'
			 && $mimeType !== $scannedMimeType) {
				
				if (\ze::in($mimeType, "image/x-icon", "image/icon") && \ze::in($scannedMimeType, 'image/x-icon', 'image/icon', 'image/ico', 'image/vnd.microsoft.icon')) {
					//Special case for ICO files: allow certain mime types
				} else {
					switch ($scannedMimeType) {
						case 'image/gif':
							return new \ze\error('MISSNAMED_GIF', \ze\admin::phrase('The file "[[filename]]" is a GIF, so its extension must be .gif (upper or lower case).', ['filename' => basename($filepath)]));
							break;
						case 'image/jpeg':
							return new \ze\error('MISSNAMED_JPG', \ze\admin::phrase('The file "[[filename]]" is a JPG, so its extension must be .jpg or .jpeg (upper or lower case).', ['filename' => basename($filepath)]));
							break;
						case 'image/webp':
							return new \ze\error('MISSNAMED_WEBP', \ze\admin::phrase('The file "[[filename]]" is a WebP, so its extension must be .webp (upper or lower case).', ['filename' => basename($filepath)]));
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
					return \ze\file::genericCheckError($filepath);
				}
			}
			
			//...and vice versa, don't let other files mascerade as Office docs
			if ($scanIsOffice && !$exIsOffice) {
				return \ze\file::genericCheckError($filepath);
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
						return \ze\file::genericCheckError($filepath);
					}
					break;
				
				//Always block executable files
				case 'application/dosexec':
				case 'application/executable':
				case 'application/mach-binary':
					return \ze\file::genericCheckError($filepath);
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
			return \ze\file::genericCheckError($filepath);
		}
	}






	//Get the $usage details for an image in the image library.
	public static function getImageUsage($imageId) {
		
		$usage = [];
		
		foreach (\ze\sql::fetchAssocs("
			SELECT foreign_key_to, is_nest, is_slideshow, COUNT(DISTINCT foreign_key_id, foreign_key_char) AS cnt, MIN(foreign_key_id) AS eg
			FROM ". DB_PREFIX. "inline_images
			WHERE image_id = ". (int) $imageId. "
              AND in_use = 1
			  AND archived = 0
			  AND foreign_key_to IN ('content', 'library_plugin', 'menu_node', 'email_template', 'newsletter', 'newsletter_template', 'standard_email_template') 
			  AND foreign_key_id != 0
			GROUP BY foreign_key_to, is_nest, is_slideshow
		") as $ucat) {
			$keyTo = $ucat['foreign_key_to'];
			
			if ($keyTo == 'content') {
				$usage['content_items'] = $ucat['cnt'];
				$usage['content_item'] = \ze\sql::fetchValue("
					SELECT CONCAT(foreign_key_char, '_', foreign_key_id)
					FROM ". DB_PREFIX. "inline_images
					WHERE image_id = ". (int) $imageId. "
					  AND archived = 0
					  AND foreign_key_to = 'content'
					LIMIT 1
				");
			
			} elseif ($keyTo == 'library_plugin') {
				if ($ucat['is_slideshow']) {
					$usage['slideshows'] = $ucat['cnt'];
					$usage['slideshow'] = $ucat['eg'];
					
				} elseif ($ucat['is_nest']) {
					$usage['nests'] = $ucat['cnt'];
					$usage['nest'] = $ucat['eg'];
				
				} else {
					$usage['plugins'] = $ucat['cnt'];
					$usage['plugin'] = $ucat['eg'];
				}
				
			} else {
				$usage[$keyTo. 's'] = $ucat['cnt'];
				$usage[$keyTo] = $ucat['eg'];
			}
		}
		
		$historicContent = \ze\sql::fetchAssoc("
			SELECT foreign_key_id, foreign_key_char, foreign_key_version
			FROM ". DB_PREFIX. "inline_images
			WHERE image_id = ". (int) $imageId. "
			  AND archived = 1
			  AND foreign_key_to = 'content'
			ORDER BY foreign_key_version DESC
			LIMIT 1
		");
		
		if ($historicContent) {
			$usage['historic_content'] = [
				'cID' => $historicContent['foreign_key_id'],
				'cType' => $historicContent['foreign_key_char'],
				'cVersion' => $historicContent['foreign_key_version']
			];
		}
		
		return $usage;
	}
	
	//Get the $usage details for an image in the MIC library.
	public static function getMICImageUsage($imageId) {
		$module = 'zenario_multiple_image_container';
		$moduleId = \ze\row::get('modules', 'id', ['class_name' => $module]);

		$usage = [];
		$instances = \ze\module::getModuleInstancesAndPluginSettings($module);
		
		foreach ($instances as $instance) {
			if (!empty($instance['settings']['image'])) {
				foreach (explode(',', $instance['settings']['image']) as $micImageId) {
					if ($micImageId == $imageId) {
						if ($instance['egg_id']) {
							if (!isset($usage[$micImageId]['nest'])) {
								$usage[$micImageId]['nest'] = $instance['instance_id'];
								$usage[$micImageId]['nests'] = 1;
							} else {
								$usage[$micImageId]['nests']++;
							}
						} else {
							if (!isset($usage[$micImageId]['plugin'])) {
								$usage[$micImageId]['plugin'] = $instance['instance_id'];
								$usage[$micImageId]['plugins'] = 1;
							} else {
								$usage[$micImageId]['plugins']++;
							}
						}
					}
				}
			}
		}

		if (isset($usage[$imageId]) && count($usage[$imageId]) >= 1) {
			return \ze\miscAdm::getUsageText($usage[$imageId], $usageLinks = []);
		} else {
			return null;
		}
	}
	
	
	
	//A wrapper for the move_uploaded_file() function.
	//The only difference in functionality is that this caches the result,
	//which allows one script to move the file and then move it back (i.e. to virus scan it),
	//without distrupting or needing to rewrite another script
	private static $movedFiles = [];
	public static function moveUploadedFile($pathFrom, $pathTo) {
		if (self::$movedFiles !== [] && !empty(self::$movedFiles[$pathFrom])) {
			return rename($pathFrom, $pathTo);
		
		} else {
			return self::$movedFiles[$pathFrom] = move_uploaded_file($pathFrom, $pathTo);
		}
	}
	
	//A wrapper for the is_uploaded_file() function that plays nicely when using the function above
	public static function isUploadedFile($path) {
		if (self::$movedFiles !== [] && !empty(self::$movedFiles[$path])) {
			return true;
		
		} else {
			return is_uploaded_file($path);
		}
	}

	public static function rerenderWorkingCopyImages($recreateCustomThumbnailOnes, $recreateCustomThumbnailTwos, $removeOldCopies) {
		require \ze::funIncPath(__FILE__, __FUNCTION__);
	}

	//Look through all images in the database that are flagged as public, add them if they're not there, and update their names.
	public static function updateAllImagePublicLinks() {
		\ze\fileAdm::checkAllImagePublicLinks(false, true);
	}

	//Look through all images in the database that are flagged as public, check if they're all there, and add them if not
	public static function checkAllImagePublicLinks($check, $updateHTML = false) {
		
		if ($check) {
			$report = ['numMissing' => 0];
		}
		
		$sql = "
			SELECT id, short_checksum, `usage`, filename, mime_type, width, height, location
			FROM ". DB_PREFIX. "files
			WHERE `usage` IN ('image', 'mic')
			  AND mime_type IN ('image/gif', 'image/png', 'image/jpeg', 'image/webp', 'image/svg+xml')
			  AND `privacy` = 'public'";
		
		foreach (\ze\sql::select($sql) as $image) {
			$filepath = CMS_ROOT. \ze\image::publicPath($image);
			
			if (!is_file($filepath)) {
				//In "check" mode, we're just quickly checking if all of the directories look like they are there,
				//and reporting which ones are missing.
				if ($check) {
					++$report['numMissing'];
					$report['exampleFile'] = $image['filename'];
				
				//In "fix" mode, add any missing directories.
				} else {
					//During development we often saw trying to make WebP versions of some images generate a crash on the server.
					//We've added some simplistic logging information just to help see where this script is getting stuck.
					//Note: This log file will be auto-deleted after one week of not being written to by the ze\cache::cleanDirs() function.
					\ze\miscAdm::debugLog('images', 'adding_to_public_dir.log', \ze\admin::phrase('Adding [[filename]] into the public directory. (#[[id]], [[short_checksum]], [[mime_type]], [[usage]])', $image));
					
					\ze\image::addToPublicDir($image['id']);

					\ze\miscAdm::debugLog('images', 'adding_to_public_dir.log', \ze\admin::phrase('Done!', $image));
				}
			}
		}
		
		if ($check) {
			return $report;
		
		//Also in "fix" mode, look for links to resized copies of public images in WYSIWYG Editors.
		} else {
			$publishingAPublicPage = false;
			$fixWhereLinksGo = false;
			$fixPublicDir = true;

			//Get every content area on a WYSIWYG Editor that's on the current version
			//or a draft version of a content item.
			$sql = "
				SELECT ps.instance_id, ps.name, ps.egg_id, ps.value
				FROM ". DB_PREFIX. "content_items AS c
				INNER JOIN ". DB_PREFIX. "plugin_instances AS pi
				   ON pi.content_id = c.id
				  AND pi.content_type = c.type
				  AND pi.content_version IN(c.visitor_version, c.admin_version)
				INNER JOIN ". DB_PREFIX. "plugin_settings AS ps
				   ON ps.instance_id = pi.id
				  AND ps.is_content = 'version_controlled_content'
				WHERE c.status NOT IN ('trashed','deleted')";
			$result = \ze\sql::select($sql);

			while ($row = \ze\sql::fetchAssoc($result)) {
				//Scan the HTML for images, with the "$fixPublicDir" option set.
				$files = [];
				$htmlChanged = false;
				\ze\contentAdm::syncInlineFileLinks($files, $row['value'], $htmlChanged, 'image', $publishingAPublicPage, $fixWhereLinksGo, $fixPublicDir);
				
				//The syncInlineFileLinks() function will suggest changes to the HTML. If we're just
				//checking for missing images that we need to add back to the public directories, we can ignore this.
				//However include the option to automatically apply its suggestions, e.g. during a migration from
				//a previous version of Zenario where the link format may have changed.
				if ($htmlChanged && $updateHTML) {
					\ze\row::update('plugin_settings', ['value' => $row['value']], [
						'instance_id' => $row['instance_id'],
						'egg_id' => $row['egg_id'],
						'name' => $row['name']
					]);
				}
			}

			//Same logic as above, but checking library plugins this time.
			$sql = "
				SELECT ps.instance_id, ps.name, ps.egg_id, ps.value
				FROM ". DB_PREFIX. "plugin_instances AS pi
				INNER JOIN ". DB_PREFIX. "plugin_settings AS ps
				   ON ps.instance_id = pi.id
				  AND ps.format IN ('html', 'translatable_html')
				WHERE pi.content_id = 0";
			$result = \ze\sql::select($sql);

			while ($row = \ze\sql::fetchAssoc($result)) {
				//Scan the HTML for images, with the "$fixPublicDir" option set.
				$files = [];
				$htmlChanged = false;
				\ze\contentAdm::syncInlineFileLinks($files, $row['value'], $htmlChanged, 'image', $publishingAPublicPage, $fixWhereLinksGo, $fixPublicDir);
				
				if ($htmlChanged && $updateHTML) {
					\ze\row::update('plugin_settings', ['value' => $row['value']], [
						'instance_id' => $row['instance_id'],
						'egg_id' => $row['egg_id'],
						'name' => $row['name']
					]);
				}
			}
			
			//Check images used in emails
			\ze\fileAdm::updateAllImagePublicLinksInEmailTemplates();
			
			//Also run a scan on images in Newsletters when trying to fix links.
			//These need to use untranscoded images instead of WebPs, and also
			//might have images used at different resizes.
			if (\ze\module::inc('zenario_newsletter')) {
				\zenario_newsletter::checkAllImagePublicLinksInNewsletters();
				\zenario_newsletter::checkAllImagePublicLinksInNewsletterTemplates();
			}
		}
	}
	
	//Similar logic to the above, but check email templates
	public static function updateAllImagePublicLinksInEmailTemplates() {
		
		$sql = "
			SELECT id, code, body
			FROM ". DB_PREFIX. "email_templates
			WHERE body IS NOT NULL";
		$result = \ze\sql::select($sql);

		while ($row = \ze\sql::fetchAssoc($result)) {
			$files = [];
			$htmlChanged = false;
			\ze\contentAdm::syncInlineFileLinksWithoutTranscoding($files, $row['body'], $htmlChanged, 'image', $publishingAPublicPage = false, $fixWhereLinksGo = true, $fixPublicDir = true);
			
			if ($htmlChanged) {
				\ze\row::update('email_templates', ['body' => $row['body']], $row['id']);
			}
			
			\ze\contentAdm::syncInlineFiles(
				$files,
				['foreign_key_to' => 'email_template', 'foreign_key_id' => $row['id'], 'foreign_key_char' => $row['code']],
				$keepOldImagesThatAreNotInUse = false);
		}
		
		$sql = "
			SELECT name, `value`
			FROM ". DB_PREFIX. "site_settings
			WHERE name IN ('standard_email_template')
			  AND `value` IS NOT NULL";
		$result = \ze\sql::select($sql);

		while ($row = \ze\sql::fetchAssoc($result)) {
			$files = [];
			$htmlChanged = false;
			\ze\contentAdm::syncInlineFileLinksWithoutTranscoding($files, $row['value'], $htmlChanged, 'image', $publishingAPublicPage = false, $fixWhereLinksGo = true, $fixPublicDir = true);
			
			if ($htmlChanged) {
				\ze\row::update('site_settings', ['value' => $row['value']], $row['name']);
			}
			
			$key = ['foreign_key_to' => 'standard_email_template', 'foreign_key_id' => 1, 'foreign_key_char' => ''];
			\ze\contentAdm::syncInlineFiles($files, $key, $keepOldImagesThatAreNotInUse = false);
		}
	}	
	
	public static function generateAltTagFromFilename($filename) {
		// Find the position of the last dot in the filename
		$lastDotPosition = strrpos($filename, '.');
	
		// Extract the filename without the extension
		if ($lastDotPosition !== false) {
			$nameWithoutExtension = substr($filename, 0, $lastDotPosition);
		} else {
			// If no dot is found, use the whole filename
			$nameWithoutExtension = $filename;
		}
	
		// Replace underscores and hyphens with spaces
		$nameWithSpaces = str_replace(['_', '-'], ' ', $nameWithoutExtension);
	
		// Remove any special characters except alphanumeric and spaces
		$nameSanitised = self::sanitiseAltTag($nameWithSpaces);
	
		// Capitalize the first letter of each word
		$altTag = ucwords($nameSanitised);
	
		return $altTag;
	}
	
	public static function sanitiseAltTag($altTag) {
		$nameSanitised = preg_replace('/[^a-zA-Z0-9\s]/', '', $altTag);
		
		return $nameSanitised;
	}
	
	
	
	
	
	
	
	

	public static function plainTextExtract($filePath, &$extract, $mimeType = null) {
		$extract = '';
		
		if (file_exists($filePath) && is_readable($filePath)) {
			
			if (is_null($mimeType)) {
				$mimeType = \ze\file::mimeType($filePath);
			}
			
			switch ($mimeType) {
				//.doc
				case 'application/msword':
					if ($programPath = \ze\server::programPathForExec(\ze::setting('antiword_path'), 'antiword')) {
						$return_var = false;
						exec(
							escapeshellarg($programPath).
							' '.
							escapeshellarg($filePath),
						$extract, $return_var);
					
						if ($return_var == 0) {
							$extract = \ze\ring::encodeToUtf8(implode("\n", $extract));
							
							//PLEASE NOTE: as of 28 Jan 2025, there appears to be a bug when processing Word documents
							//and it could possibly be related to undocumented changes in PHP 8.1 and above.
							//If the text encoding could not be found, treat this as if there was no extract, so the upload does not fail.
							if ($extract) {
								$extract = trim(mb_ereg_replace('\s+', ' ', str_replace("\xc2\xa0", ' ', $extract)));
							}
							return 'antiword';
						}
					}
				
					break;
			
			
				//.docx
				case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
					if (class_exists('ZipArchive')) {
						$zip = new \ZipArchive;
						if ($zip->open($filePath) === true) {
							if ($extract = html_entity_decode(strip_tags($zip->getFromName('word/document.xml')), ENT_QUOTES, 'UTF-8')) {
								$zip->close();
							
								$extract = trim(mb_ereg_replace('\s+', ' ', str_replace("\xc2\xa0", ' ', $extract)));
								return 'ZipArchive';
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
								escapeshellarg($filePath).
								' '.
								escapeshellarg($temp_file),
							$output, $return_var);
						
						
							//pdftotext has a bug where it can't read certain filenames (maybe there's some missed escaping in its code?)
							//If pdftotext couldn't read the file, try copying the file to a sensible name
							if ($return_var == 1) {
								if ($temp_pdf_file = tempnam(sys_get_temp_dir(), 'pdf')) {
									copy($filePath, $temp_pdf_file);
								
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
								$extract = mb_ereg_replace('\xC2\xAD\n', '', $extract);
								
								//Comment from Chris:
								//This line of code is suspicous.
								//It looks like it was intended to remove corrupted characters caused due to
								//an incorrect character set being applied, but I disagree with the logic.
								//It has also started generating PHP errors after a OS upgrade, so we've decided to remove it.
								#// Get rid of other characters which may show as Â or â
								#$extract = mb_ereg_replace('[\xC2\xE2]', '', $extract);
							
								$extract = trim(\ze\ring::encodeToUtf8($extract));

								return 'pdftotext';
							}
						}
					}
				
					break;
			}
		}
	
		$extract = '';
		return false;
	}

	public static function updateDocumentContentItemExtract($cID, $cType, $cVersion, $fileId = false, $forceRescan = false, $createThumbnail = false) {
		
		if ($fileId === false) {
			$fileId = \ze\row::get('content_item_versions', 'file_id', ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
		}
	
		if ($createThumbnail
		 && $fileId
		 && $file = \ze\file::docstorePath($fileId)) {
			\ze\file::addContentItemPdfScreenshotImage($cID, $cType, $cVersion, $file, true);
		}
		
		//Get the file's extract
		$extract = \ze\fileAdm::textExtract($fileId, $allowAsync = true, $forceRescan);
		
		//If this is a draft content item, we just want to call the ze\fileAdm::textExtract() function above
		//to populate the file_extracts table. However for published versions of the content item
		//we'll want to update the content_items_searchable_cache table as well.
		if (\ze\contentAdm::contentItemIsSearchable($cID, $cType, $cVersion)) {
			
			\ze\row::set('content_items_searchable_cache', [
				'content_tag' => $cType . '_' . $cID,
				'content_version' => $cVersion,
				'file_extract' => $extract['extract'],
				'file_extract_wordcount' => $extract['extract_wordcount'],
				'file_extract_pagecount' => $extract['extract_pagecount']
			], [
				'content_id' => $cID,
				'content_type' => $cType
			]);
		
		//Drafts/trashed content items/hidden content items/unlisted content items/non-searchable special pages should not be in the 
		//content_items_searchable_cache table, clear them up if they are there.
		} else {
			\ze\row::delete('content_items_searchable_cache', ['content_id' => $cID, 'content_type' => $cType, 'content_version' => $cType]);
		}
	
		return $extract;
	}

	public static function updateHierarchicalDocumentExtract($fileId, &$extract, &$imgFileId, $forceRescan = false) {
		
		//Get the file's extract
		$extract = \ze\fileAdm::textExtract($fileId, $allowAsync = false, $forceRescan);
		
		//Trim down to just the columns we use in the documents table
		$extract = [
			'file_extract' => $extract['extract'],
			'file_extract_wordcount' => $extract['extract_wordcount'],
			'file_extract_pagecount' => $extract['extract_pagecount']
		];
	
		$filePath = \ze\file::docstorePath($fileId);
		
		$mime = \ze\row::get('files', 'mime_type', $fileId);
		switch ($mime) {
			case 'application/pdf':
				if ($imgFile = \ze\file::createPdfFirstPageScreenshotPng($filePath)) {
					$imgBaseName = basename($filePath) . '.png';
					$imgFileId = \ze\fileAdm::addToDatabase('hierarchical_file_thumbnail', $imgFile, $imgBaseName, true, true);
				}
				break;
			case 'image/jpeg':
			case 'image/webp':
			case 'image/png':
			case 'image/gif':
				$imageThumbnailWidth = 300;
				$imageThumbnailHeight = 300;
				$size = getimagesize($filePath);
			
				if ($size && $size[0] > $imageThumbnailWidth && $size[1] > $imageThumbnailHeight) {
					$width = $height = $url = false;
					\ze\image::link($width, $height, $url, $fileId, $imageThumbnailWidth, $imageThumbnailHeight, 'resize', 0, false, false, 'auto', true, $internalFilePath = true);
					$imgFileId = \ze\fileAdm::addToDatabase('hierarchical_file_thumbnail', $url);
				} 
				break;
		}
	}
	
	public static function textExtract($fileId, $allowAsync, $forceRescan = false) {
		
		$key = ['file_id' => $fileId];
		
		//If we're already scanned a file, we'd not normally rescan it.
		if (!$forceRescan && ($extract = \ze\row::get('file_extracts', true, $key))) {
			return $extract;
		}
		
		$extract = [
			'extract' => null,
			'extract_wordcount' => 0,
			'extract_pagecount' => null,
			'extract_status' => 'failed'
		];
		
		//Currently this will only work for files stored in the docstore, and not files stored in the database.
		//If we wanted to overcome this limitation, we'd need to clopy the file data into a temporary file.
		if ($fileId
		 && ($file = \ze\row::get('files', ['usage', 'short_checksum', 'filename', 'mime_type'], $fileId))
		 && ($filePath = \ze\file::docstorePath($fileId))
		 && (file_exists($filePath))
		 && (is_readable($filePath))) {
			
			$useTextract = false;
			$imageFormatNotSupported = false;
			
			switch ($file['mime_type']) {
				case 'application/pdf':
					$useTextract = \ze::setting('enable_aws_support') && \ze::setting('enable_aws_textract') && \ze::setting('aws_textract_extract_from_pdf');
					break;
				
				case 'image/webp':
					$imageFormatNotSupported = true;
				case 'image/jpeg':
				case 'image/png':
					$useTextract = \ze::setting('enable_aws_support') && \ze::setting('enable_aws_textract') && \ze::setting('aws_textract_extract_from_jpg_and_png');
					break;
			}
			
			if ($useTextract) {
				
				
				//When scanning multiple files in batch, we should wait a little bit
				//between sending each one.
				//(N.b. these are likely done in multiple requests, so I need to have some logic that
				// stores the last time this was done.)
				$time = time();
				$prevTime = (int) \ze::setting('aws_textract_last_called');
				
				if ($prevTime && $prevTime >= ($time - 1)) {
					usleep(max(68000, min(999999, (int) \ze::setting('aws_textract_throttle'))));
				}
				
				\ze\site::setSetting('aws_textract_last_called', $time);
				
				
				
				//Work around image formats not supported by Textract. Convert them to PNGs as a workaround.
				if ($imageFormatNotSupported) {
					$imageDriver = new \Imagine\Gd\Imagine();
					$image = $imageDriver->open($filePath);
					$filePath = tempnam(sys_get_temp_dir(), 'img');
					$image->save($filePath, ['format' => 'png']);
					
					//Make a filename out of the id, usage, checksum and extension
					$remoteFileName = 'textract-target-'. $file['usage']. '-'. $fileId. '-'. $file['short_checksum']. '.png';
				
				} else {
					//Get the file's extension
					$parts = explode('.', $file['filename']);
					$type = $parts[count($parts) - 1];
					
					//Make a filename out of the id, usage, checksum and extension
					$remoteFileName = 'textract-target-'. $file['usage']. '-'. $fileId. '-'. $file['short_checksum']. '.'. $type;
				}
				
				//Upload the file to AWS S3
				$cS3 = $cTextract = $s3BucketName = null;
				try {
					\ze\fileAdm::textractConnection($cS3, $cTextract, $s3BucketName);
					
					$result = $cS3->putObject([
						'Bucket' => $s3BucketName,
						'Key' => $remoteFileName,
						'SourceFile' => $filePath,
					]);

				} catch (AwsException $e) {
					if ($imageFormatNotSupported) {
						unlink($filePath);
					}
					
					echo 'Error sending file to AWS S3: ' . $e->getMessage();
					exit;
				}
				if ($imageFormatNotSupported) {
					unlink($filePath);
				}

				// Start the Textract job
				$result = $cTextract->startDocumentTextDetection([
					'DocumentLocation' => [
						'S3Object' => [
							'Bucket' => $s3BucketName,
							'Name' => $remoteFileName,
						],
					],
				]);
				
				$extract['extract_source'] = 'Textract';
				$extract['extract_status'] = 'processing';
				$extract['requested_on'] = \ze\date::now();
				$extract['extract_job_id'] = $result['JobId'];
				
				\ze\row::set('file_extracts', $extract, $key);
				return $extract;
			
			} elseif ($extract['extract_source'] = \ze\fileAdm::plainTextExtract($filePath, $extract['extract'], $file['mime_type'])) {
				$extract['extract_wordcount'] = \ze\fileAdm::unicodeWordCount($extract['extract']);
				$extract['extract_status'] = 'completed';
				
				\ze\row::set('file_extracts', $extract, $key);
				return $extract;
			}
		}
		
		//If anything failed, remove the row from the extracts table
		\ze\row::delete('file_extracts', $key);
		return $extract;
	}

	public static function unicodeWordCount($text) {
		if (empty($text)) {
			return 0;
		}
		
		// Use Unicode-aware word boundary regex that properly handles Greek and other non-Latin scripts
		// \p{L} matches any Unicode letter (including Greek, Cyrillic, Arabic, etc.)
		// \p{M} matches combining marks (accents, diacritics)
		// \p{Nd} matches decimal digits
		$words = preg_split('/[^\p{L}\p{M}\p{Nd}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
		
		return count($words);
	}

	public static function textractConnection(&$cS3, &$cTextract, &$s3BucketName) {
		$credentials = [
			'key'    => \ze::setting('aws_s3_key_id'),
			'secret' => \ze::setting('aws_s3_secret_key'),
			// 'token' => 'your_session_token', // Uncomment and provide a session token if you're using temporary credentials
		];
		$region = \ze::setting('aws_s3_region');

		// Document details
		$s3BucketName = \ze::setting('aws_textract_temporary_storage_bucket');

		// Create an S3 client
		$cS3 = new \Aws\S3\S3Client([
			'version' => 'latest',
			'region' => $region,
			'credentials' => $credentials,
		]);

		// Create AWS Textract client
		$cTextract = new \Aws\Textract\TextractClient([
			'region' => $region,
			'version' => 'latest',
			'credentials' => $credentials,
		]);
	}
}
