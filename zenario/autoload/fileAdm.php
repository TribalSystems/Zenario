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

class fileAdm {



	//Generic handler for misc. AJAX requests from admin boxes
	//Formerly "handleAdminBoxAJAX()"
	public static function handleAdminBoxAJAX() {
	
		if (\ze::request('fileUpload')) {
		
			\ze\fileAdm::exitIfUploadError(true, true, true, 'Filedata');
			\ze\fileAdm::putUploadFileIntoCacheDir($_FILES['Filedata']['name'], $_FILES['Filedata']['tmp_name'], \ze::request('_html5_backwards_compatibility_hack'));
	
		} else if (\ze::request('fetchFromDropbox')) {
			\ze\fileAdm::putDropboxFileIntoCacheDir($_POST['name'] ?? false, $_POST['link'] ?? false);
	
		} else {
			exit;
		}
	}

	//See also: function \ze\file::getPathOfUploadInCacheDir()

	//Formerly "putDropboxFileIntoCacheDir()"
	public static function putDropboxFileIntoCacheDir($filename, $dropboxLink) {
		\ze\fileAdm::putUploadFileIntoCacheDir($filename, false, false, $dropboxLink);
	}

	//Formerly "putUploadFileIntoCacheDir()"
	public static function putUploadFileIntoCacheDir(
		$filename, $tempnam, $html5_backwards_compatibility_hack = false, $dropboxLink = false,
		$cacheFor = false, $isAllowed = null, $baseLink = 'zenario/file.php'
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
		} elseif ($dropboxLink) {
			$sha = sha1($dropboxLink);
		} else {
			exit;
		}
	
		if (!\ze\cache::cleanDirs()
		 || !($dir = \ze\cache::createDir($sha, 'uploads', false))) {
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
		
			if ($dropboxLink) {
				$failed = true;
		
				touch($path);
				\ze\cache::chmod($path, 0666);
				
				//Attempt to use PHP to load the file
				if ($in = fopen($dropboxLink, 'r')) {
					$out = fopen($path, 'w');
					while (!feof($in)) {
						fwrite($out, fread($in, 65536));
					}
					fclose($out);
					fclose($in);
					
					clearstatcache();
					$failed = !filesize($path);
				}
				
				//Attempt to use wget to fetch the file
				if ($failed && !\ze\server::isWindows() && \ze\server::execEnabled()) {
					try {
						//Don't fetch via ssh, as this doesn't work when calling wget from php
						$httpDropboxLink = str_replace('https://', 'http://', $dropboxLink);
						
						exec('wget -q '. escapeshellarg($httpDropboxLink). ' -O '. escapeshellarg($path));
						
						clearstatcache();
						$failed = !filesize($path);
						
					} catch (\Exception $e) {
						//echo 'Caught exception: ',  $e->getMessage(), "\n";
					}
				}
				
				if ($failed) {
					echo \ze\admin::phrase('Could not get the file from Dropbox!');
					exit;
				}
				
				\ze\fileAdm::exitIfVirusInFile(true, $path, $filename, true);
				
			} else {
				\ze\fileAdm::moveUploadedFile($tempnam, $path);
			}
		}
		
		if (($mimeType = \ze\file::mimeType($file['filename']))
		 && (\ze\file::isImage($mimeType))
		 && ($image = @getimagesize($path))) {
			$file['width'] = $image[0];
			$file['height'] = $image[1];
		
			$file['id'] = \ze\ring::encodeIdForOrganizer($sha. '/'. $file['filename']. '/'. $file['width']. '/'. $file['height']);
		} else {
			$file['id'] = \ze\ring::encodeIdForOrganizer($sha. '/'. $file['filename']);
		}
		
		$file['link'] = $baseLink. '?getUploadedFileInCacheDir='. $file['id'];
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







	//Formerly "updateShortChecksums()"
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






	//Formerly "exitIfUploadError()"
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

		
		switch ($_FILES[$fileVar]['error'] ?? false) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
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
		
		//For file-types supported by the ze\file::check function, run a check to see if the extension looks correct
		//(I.e. try to check that this isn't a different type of file where the extension has been altered.)
		$fileCheck = \ze\file::check($path, $mimeType = \ze\file::mimeType($name));
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
					echo \ze\lang::phrase('According to its name, we expect "[[name]]" to be an [[extension]] file - but on scanning its contents are a GIF, so its extension must be .gif (upper or lower case).', $mrg, $moduleClass);
				
				} elseif (isset($fileCheck->errors['MISSNAMED_JPG'])) {
					echo \ze\lang::phrase('According to its name, we expect "[[name]]" to be an [[extension]] file - but on scanning its contents are a JPEG, so its extension must be .jpg or .jpeg (upper or lower case).', $mrg, $moduleClass);
				
				} elseif (isset($fileCheck->errors['MISSNAMED_PNG'])) {
					echo \ze\lang::phrase('According to its name, we expect "[[name]]" to be an [[extension]] file - but on scanning its contents are a PNG, so its extension must be .png (upper or lower case).', $mrg, $moduleClass);
				
				} elseif (isset($fileCheck->errors['MISSNAMED_SVG'])) {
					echo \ze\lang::phrase('According to its name, we expect "[[name]]" to be an [[extension]] file - but on scanning its contents are a SVG, so its extension must be .svg (upper or lower case).', $mrg, $moduleClass);
				
				} else {
					echo \ze\lang::phrase('According to its name, "[[name]]" should be an [[extension]] file, but on scanning its contents it failed to match "[[mimeType]]".', $mrg, $moduleClass);
				}
				
				echo
					"\n\n",
					\ze\lang::phrase('This could be because the file has been corrupted, or you could have renamed the extension by mistake.', [], $moduleClass),
					"\n\n",
					\ze\lang::phrase('Developers: if this message constantly occurs, even on valid files, then this is probably a misclassification in the UNIX file utility. You can fix this by adding an exception/correction in the function check() in zenario/autoload/file.php.', [], $moduleClass);
			}
			
			exit;
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
			  AND foreign_key_to IN ('content', 'library_plugin', 'menu_node', 'email_template', 'newsletter', 'newsletter_template') 
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

	//Look through all images in the database that are flagged as public, check if they're all there, and add them if not
	public static function checkAllImagePublicLinks($check) {
		
		if ($check) {
			$report = ['numMissing' => 0];
		}
		
		$sql = "
			SELECT id, short_checksum, filename
			FROM ". DB_PREFIX. "files
			WHERE `usage` = 'image'
			  AND mime_type IN ('image/gif', 'image/png', 'image/jpeg', 'image/svg+xml')
			  AND `privacy` = 'public'";
		
		foreach (\ze\sql::select($sql) as $image) {
			$filepath = CMS_ROOT. 'public/images/'. $image['short_checksum']. '/'. \ze\file::safeName($image['filename']);
			
			if (!is_file($filepath)) {
				//In "check" mode, we're just quickly checking if all of the directories look like they are there,
				//and reporting which ones are missing.
				if ($check) {
					++$report['numMissing'];
					$report['exampleFile'] = $image['filename'];
				
				//In "fix" mode, add any missing directories.
				} else {
					\ze\file::addPublicImage($image['id']);
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
				SELECT ps.value
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
			}
		}
	}
	
	
	//A drop-in replacement for PHP's readfile() function,
	//without some of the bugs!
	
	//Inspired by
	//https://serverfault.com/questions/115906/is-there-some-limit-on-a-size-of-a-file-when-force-downloading-it-with-php-on-ap
	
	//Note: was added for testing purposes, but didn't fix the problems I was trying to solve,
	//so I've commented it back out!
	#public static function readFile($path, $chunkSize = 0x1000000) {
	#	if ($fh = fopen($path, 'rb')) {
	#		while (!feof($fh)) { 
	#			echo fread($fh, $chunkSize); 
	#			flush();
	#		}
	#		$fh = fclose($fh);
	#	}
	#	return $fh;
	#}
}