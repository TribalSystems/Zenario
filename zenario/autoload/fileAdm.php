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

class fileAdm {



	//Generic handler for misc. AJAX requests from admin boxes
	//Formerly "handleAdminBoxAJAX()"
	public static function handleAdminBoxAJAX() {
	
		if ($_REQUEST['fileUpload'] ?? false) {
			\ze\fileAdm::exitIfUploadError();
		
			//If this is the plugin settings FAB, and this is an image, try to add the image
			//to the image library straight away, and return the id.
			if (!empty($_REQUEST['path'])
			 && $_REQUEST['path'] == 'plugin_settings'
			 && (\ze\priv::check('_PRIV_MANAGE_MEDIA') || \ze\priv::check('_PRIV_EDIT_DRAFT') || \ze\priv::check('_PRIV_CREATE_REVISION_DRAFT') || \ze\priv::check('_PRIV_MANAGE_REUSABLE_PLUGIN'))
			 && \ze\file::isImageOrSVG(\ze\file::mimeType($_FILES['Filedata']['name']))) {
			
				$imageId = \ze\file::addToDatabase('image', $_FILES['Filedata']['tmp_name'], rawurldecode($_FILES['Filedata']['name']), $mustBeAnImage = true);
				echo json_encode(\ze\file::labelDetails($imageId));
		
			//Otherwise upload
			} else {
				\ze\fileAdm::putUploadFileIntoCacheDir($_FILES['Filedata']['name'], $_FILES['Filedata']['tmp_name'], ($_REQUEST['_html5_backwards_compatibility_hack'] ?? false));
			}
	
		} else if ($_REQUEST['fetchFromDropbox'] ?? false) {
			\ze\fileAdm::putDropboxFileIntoCacheDir($_POST['name'] ?? false, ($_POST['link'] ?? false));
	
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
				\ze\admin::phrase('To add a file format to the known file format list, go to "Configuration -> Uploadable file types" in Organizer.'),
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
		
				//Attempt to use wget to fetch the file
				if (!\ze\server::isWindows() && \ze\server::execEnabled()) {
					try {
						//Don't fetch via ssh, as this doesn't work when calling wget from php
						$httpDropboxLink = str_replace('https://', 'http://', $dropboxLink);
					
						//$output = $return_var = false;
						//$return = exec('wget '. escapeshellarg($httpDropboxLink). ' -O '. escapeshellarg($path), $output, $return_var);
						//var_dump($output);
						//var_dump($return_var);
						//var_dump($return);
						//var_dump($path);
						//var_dump(filesize($path));
					
						exec('wget -q '. escapeshellarg($httpDropboxLink). ' -O '. escapeshellarg($path));
					
						$failed = false;
		
					} catch (\Exception $e) {
						//echo 'Caught exception: ',  $e->getMessage(), "\n";
					}
				}
		
				//If that didn't work, try using php
				if ($failed || !filesize($path)) {
					$in = fopen($dropboxLink, 'r');
					$out = fopen($path, 'w');
					while (!feof($in)) {
						fwrite($out, fread($in, 65536));
					}
					fclose($out);
					fclose($in);
				}
		
				if ($failed || !filesize($path)) {
					echo \ze\admin::phrase('Could not get the file from Dropbox!');
					exit;
				}
			} else {
				move_uploaded_file($tempnam, $path);
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
	public static function exitIfUploadError($moduleClass = false) {
		$error = $_FILES['Filedata']['error'] ?? false;
		switch ($error) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				echo \ze\admin::phrase('Your file was too large to be uploaded.', false, $moduleClass);
				exit;
			case UPLOAD_ERR_PARTIAL:
			case UPLOAD_ERR_NO_FILE:
				echo \ze\admin::phrase('There was a problem whilst uploading your file.', false, $moduleClass);
				exit;
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
}