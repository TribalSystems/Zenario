<?php
/*
 * Copyright (c) 2022, Tribal Limited
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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

use Aws\S3\S3Client;
class zenario_ctype_document extends ze\moduleBaseClass {
	
	protected $mergeFields = [];
	protected $allowedChildSections = [];
	
	var	$targetID = false;
	var	$targetVersion = false;
	var	$targetType = false;

	
	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = false, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
			
		
		
		if ($this->setting('show_details_and_link') == 'another_content_item') {
			$this->clearCacheBy(
				$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = true, $clearByModuleData = false);
		} else {
			$this->clearCacheBy(
				$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = true, $clearByModuleData = false);
		}
		
		if ($this->cType == 'document' && ze\priv::check()) {
			$this->callScript('zenario_wysiwyg_editor', 'hideAnimationsInEditors');
		}
		
		if ($this->setting('show_details_and_link')=='another_content_item'){
			$item = $this->setting('another_document');
			if (count($arr = explode("_",$item))==2){
				$this->targetID = $arr[1];
				$this->targetType = $arr[0];
				if (!$this->targetVersion = ze\content::showableVersion($this->targetID,$this->targetType)){
					return false;
				}
			}
		}
		if (!($this->targetID && $this->targetVersion && $this->targetType)) {
			$this->targetID = $this->cID;
			$this->targetVersion = $this->cVersion;
			$this->targetType = $this->cType;
		}
		
		if ($this->targetType != 'document') {
			return false;
		}
		
		if (!$version = ze\row::get(
			'content_item_versions',
			['title', 'description', 'published_datetime', 'release_date', 'file_id', 'filename', 's3_filename','s3_file_id'],
			['id'=> $this->targetID, 'version'=> $this->targetVersion, 'type'=> $this->targetType]
		)) {
			return false;
		}
		
		
		if ($this->setting('show_title')) {
			$this->mergeFields['Show_Title'] = true;
			$this->mergeFields['Title'] = htmlspecialchars($version['title']);
			$this->mergeFields['Title_Tags'] = $this->setting('title_tags');
		}
		
		if ($this->mergeFields['Description'] = htmlspecialchars($version['description'])) {
			$this->allowedChildSections['Summary_Section'] = true;
			$this->allowedChildSections['Description_Section'] = true;
		}
			
		$type = explode('.', $version['filename']);
		$type = $type[count($type) - 1];
		$this->mergeFields['Type'] = htmlspecialchars($type);
		
		//When S3 is enabled in site setting
		if($version['s3_filename']){
			$s3type = explode('.', $version['s3_filename']);
			$s3type = $s3type[count($s3type) - 1];
			$this->mergeFields['S3Type'] = htmlspecialchars($s3type);

			if ($this->setting('show_filename_s3_file')) {
				$this->mergeFields['S3_File_Name'] = $version['s3_filename'];
			}
		}
	
		if($this->setting('show_default_stick_image')) {
			$has_img = false;
			if($this->setting('use_sticky_image')) {
				$width = (int)$this->setting('sticky_image_width');
				$height = (int)$this->setting('sticky_image_height');
				$url = false;
				
				if ($this->setting('use_sticky_image') && (ze\file::itemStickyImageLink($width, $height, $url, $this->targetID, $this->targetType, $this->targetVersion, $width, $height))) {
					$this->mergeFields['Sticky_image'] = "background: url('" .  htmlspecialchars($url) . "') no-repeat scroll 0 0;";
					$this->mergeFields['Sticky_image_url'] = htmlspecialchars($url) ;
					$this->allowedChildSections['Sticky_image'] = $has_img = true;
				}
			}
			if(!$has_img) {
				$this->getStyledExtensionIcon($type, $this->mergeFields);
			}
		}
			
		$localFileSize = ze\lang::formatFilesizeNicely(ze\row::get('files', 'size', $version['file_id']), 0, false, 'zenario_ctype_document');

		if ($this->setting('show_release_datetime')) {
			if ($this->mergeFields['Published'] = ze\date::format(($version['release_date'] ?: $version['published_datetime']), $this->setting('date_format'))) {
				if ($this->setting('show_time')) {
					$this->mergeFields['Published'] .= ' ' . ze\date::formatTime(($version['release_date'] ?: $version['published_datetime']),ze::setting('vis_time_format'));
				} 
				$this->allowedChildSections['Published_Section'] = true;
			}
		}
		
		$url = false;
		$localFileDownload = '';
		$s3FileDownload = '';
		$s3Link = '';
		$s3Filesize = '';
		
		if (($_GET['download'] ?? false) && (!$this->eggId || $this->eggId == ($_GET['eggId'] ?? false))) {
			if (!ze\file::contentLink($url, $this->targetID, $this->targetType, $this->targetVersion)) {
				
				$body = ze\admin::phrase('An attempt to download the document [[url]] was unsuccessful.', ['url' => $this->linkToItem($this->targetID,$this->targetType,true)]);
				ze\db::reportError('Failed document download at', $body);
				
				$this->allowedChildSections['No_Document_Link_Section'] = true;
				$this->allowedChildSections['No_Document_Image_Link_Section'] = true;
			} else {
				$this->headerRedirect($url);
			}
		} else {
			
			if (ze::setting('aws_s3_support')) {
				$localFileDownload = ze::setting('local_file_link_text');
				$s3FileDownload = ze::setting('s3_file_link_text');
			} else {
				//This will be translated in the framework.
				$localFileDownload = 'Download Now';
			}
			
			$localFileDetails = ze\row::get('files', ['filename','path','size'], $version['file_id']);
			if ($this->setting('local_file') && $localFileDetails && $localFileDetails['filename']) {
				$link = $this->linkToItem($this->targetID, $this->targetType, false, 'download=1'. ($this->eggId? '&eggId='. $this->eggId : ''));
				$link = htmlspecialchars($link);

				if ($this->setting('show_view_link')) {
					$linkForViewing = ze\file::link($version['file_id']);
					$this->mergeFields['Link_For_Viewing'] = htmlspecialchars($linkForViewing);
				}

				$this->mergeFields['Show_Local_File_Type_And_Size'] = $this->setting('show_local_file_type_and_size');
				
				if ($this->setting('show_filename_local_file')) {
					$this->mergeFields['Local_File_Name'] = $localFileDetails['filename'];
				}
			} else {
				$link = '';
			}
			
			if (ze::setting('aws_s3_support') && $version['s3_file_id']) {
				$fileDetails = ze\row::get('files', ['filename','path','size'], $version['s3_file_id']);
				if ($fileDetails && $fileDetails['size']) {
					$s3Filesize = ze\file::formatSizeUnits($fileDetails['size']);
				}

				if ($fileDetails && $fileDetails['path']) {
					$fileName = $fileDetails['path'].'/'.$fileDetails['filename'];
				} else {
					$fileName = $fileDetails['filename'];
				}

				if ($fileName) {
					$s3Link = self::getS3FilePresignedUrl($fileName);
					$s3Link = htmlspecialchars($s3Link);
				}

				$s3FileDetails = self::getS3FileDetails($version['s3_file_id']);
				if (!empty($s3FileDetails) && isset($s3FileDetails['ContentType']) && $s3FileDetails['ContentType'] == 'video/mp4') {
					$s3FileDownload = ze::setting('s3_file_play_video_text');
				}
			}

			$this->allowedChildSections['Document_Image_Link_Section'] = true;
			$this->mergeFields['Link'] = $link;

			if ($this->setting('show_document_download_page_link')) {
				$this->mergeFields['Download_Page'] = $this->linkToItem($this->targetID, $this->targetType, true, '');
			}
			
			$request = '';
			$this->mergeFields['Show_Permalink'] = $this->setting('show_permalink');
			if ($this->mergeFields['Show_Permalink'] && $this->setting('permalink_target') == 'to_file_itself') {
				if (ze::setting('mod_rewrite_enabled') && ze::setting('mod_rewrite_admin_mode')) {
					$request = '?download=1';
				} else {
					$request = 'download=1';
				}
			}
			$this->mergeFields['CopyLink'] = $this->linkToItem($this->targetID, $this->targetType, $fullpath = true, $request);

			$this->mergeFields['Aws_Link'] = ze::setting('aws_s3_support');
			$this->mergeFields['Show_S3_File_Type_And_Size'] = $this->mergeFields['Aws_Link'] && $this->setting('show_s3_file_type_and_size');
			$this->mergeFields['S3_Size'] = $s3Filesize;
			$this->mergeFields['File_Size'] = $localFileSize;
			$this->mergeFields['S3_Link'] = $s3Link;
			$this->mergeFields['Google_Analytics_Link'] = htmlspecialchars(ze\file::trackDownload($link));
			$this->mergeFields['Download_Local_File'] = $localFileDownload;
			$this->mergeFields['Download_S3_File'] = $s3FileDownload;
			$this->mergeFields['module_loc'] = ze::moduleDir('zenario_ctype_document');
		}
		
		if (!ze::isAdmin()) {
			if($this->setting('show_permalink')) {
				ze::requireJsLib('zenario/libs/yarn/toastr/toastr.min.js', 'zenario/libs/yarn/toastr/build/toastr.min.css');
			}
		}
		
		return true;
	}

	function showSlot() {
		if ($this->targetType != 'document') {
			if ((int)($_SESSION['admin_userid'] ?? false)){
				echo "This Plugin must be placed on a Document-type Content Item, or configured to point to another Document-type Content Item. Please check your Plugin Settings.";
			}
			return;
		}
		
		$this->framework( 'Outer', $this->mergeFields, $this->allowedChildSections );
	}
	

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if ($path == 'site_settings' && $settingGroup == 'files_and_images') {
		
			if (isset($fields['awss3_file_downloads/local_file_link_text']) && !$fields['awss3_file_downloads/local_file_link_text']['value']) {
				$fields['awss3_file_downloads/local_file_link_text']['value'] = ze\admin::phrase('Download');
			}

			if (isset($fields['awss3_file_downloads/s3_file_link_text']) && !$fields['awss3_file_downloads/s3_file_link_text']['value']) {
				$fields['awss3_file_downloads/s3_file_link_text']['value'] = ze\admin::phrase('Download from S3');
			}

			if (isset($fields['awss3_file_downloads/s3_file_play_video_text']) && !$fields['awss3_file_downloads/s3_file_play_video_text']['value']) {
				$fields['awss3_file_downloads/s3_file_play_video_text']['value'] = ze\admin::phrase('Play video');
			}
		} elseif ($path == 'plugin_settings') {
			if (ze::setting('aws_s3_support')) {
				$fields['first_tab/download_source']['hidden'] = false;
				$fields['first_tab/s3_file']['hidden'] = false;
				$fields['first_tab/show_filename_s3_file']['hidden'] = false;

				$fields['first_tab/show_view_button']['label'] = ze\admin::phrase('Show link to document download page');
				$fields['first_tab/local_file']['label'] = ze\admin::phrase('Show local file Download button for immediate download (if local file exists)');
				$fields['first_tab/s3_file']['label'] = ze\admin::phrase('Show S3 file Download button for immediate download');
			}
		}
	}
	

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes){
		switch ($path) {
		    case 'plugin_settings':
		        $fields['first_tab/another_document']['hidden'] = 
		        	!(($values['first_tab/show_details_and_link'] ?? false)=='another_content_item');
		        $fields['first_tab/date_format']['hidden'] = 
		        $fields['first_tab/show_time']['hidden'] = 
		        	!(($values['first_tab/show_release_datetime'] ?? false));
		        
		        $fields['first_tab/use_sticky_image']['hidden'] = !$values['first_tab/show_default_stick_image'];
		        $hidden = !($values['first_tab/show_default_stick_image'] && $values['first_tab/use_sticky_image']);
		        $this->showHideImageOptions($fields, $values, 'first_tab', $hidden, 'sticky_image_');
				
				if (ze::setting('aws_s3_support')) {
					$fields['first_tab/download_source']['hidden'] = false;
					$fields['first_tab/local_file']['hidden'] = false;
					$fields['first_tab/s3_file']['hidden'] = false;
				}
				
		        break;
			
			
			case 'zenario_content':
				if ($box['key']['cType'] == 'document') {
					$box['tabs']['file']['hidden'] = false;
					unset($box['tabs']['file']['fields']['file']['upload']['accept']);
					unset($box['tabs']['file']['fields']['file']['upload']['extensions']);
				}
				
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'zenario_content':
				if ($box['key']['cType'] == 'document') {
					if (ze\ring::engToBoolean($box['tabs']['file']['edit_mode']['on'] ?? false)) {
						if (ze::setting('aws_s3_support')) {
							if (empty($values['file/file']) && empty($values['file/s3_file_id']) && $saving) {
								$box['tabs']['file']['errors'][] = ze\admin::phrase('Please upload a local file or an S3 file.');
							}
						
						} elseif (empty($values['file/file']) && $saving) {
							$box['tabs']['file']['errors'][] = ze\admin::phrase('Please select a file.');
						
						} elseif ($path = ze\file::getPathOfUploadInCacheDir($values['file/file'])) {
							if (!ze\file::isAllowed($path)) {
								$box['tabs']['file']['errors'][] = ze\admin::phrase('Please select a valid file format.');
						
							} elseif (ze\file::fileSizeBasedOnUnit(ze::setting('content_max_filesize'), ze::setting('content_max_filesize_unit')) < filesize($path)) {
								$href = 'organizer.php#zenario__administration/panels/site_settings//files_and_images~.site_settings~tfilesizes~k{"id"%3A"files_and_images"}';
								$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
								$linkEnd = '</a>';

								$box['tabs']['file']['errors'][] =
									ze\admin::phrase(
										'This file is larger than the Maximum Content File Size. Please go to the [[link_start]]site settings[[link_end]] to check this setting.',
										[
											'link_start' => $linkStart,
											'link_end' => $linkEnd
										]
									);
							}
						}
					}
				}
				
				break;
				
				
			case 'site_settings':
				if ($settingGroup == 'files_and_images') {
					
					if ($values['awss3_file_downloads/aws_s3_support']) {
						$awsS3Region = $values['awss3_file_downloads/aws_s3_region'];
						$awsS3KeyId = $values['awss3_file_downloads/aws_s3_key_id'];
						$awsS3SecretKey = $values['awss3_file_downloads/aws_s3_secret_key'];
						$awsS3Bucket = $values['awss3_file_downloads/aws_s3_bucket'];
				
						if ($awsS3Region && $awsS3KeyId && $awsS3SecretKey && $awsS3Bucket) {
							$bucket = ltrim($awsS3Bucket, 'arn:aws:s3:::');
							$s3 = new Aws\S3\S3Client([
								'region'  => $awsS3Region,
								'version' => 'latest',
								'credentials' => [
									'key'    => $awsS3KeyId,
									'secret' => $awsS3SecretKey,
								]
							]);

							// Send a PutObject request and get the result object.
							$bucketResponse = $s3->doesBucketExist($bucket);
							if (!$bucketResponse) {
								$box['tabs']['awss3_file_downloads']['errors'][] = ze\admin::phrase('Connection failed! Please check your credentials.');
							}
						}
					}
				}
				
				break;
		}
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__content/panels/content':
				if (isset($panel['collection_buttons']['zenario_ctype_document__create_multiple'])) {
					if ($panel['key']['cType'] != 'document') {
						unset($panel['collection_buttons']['zenario_ctype_document__create_multiple']);
					} else {
						$panel['collection_buttons']['zenario_ctype_document__create_multiple']['tooltip'] = 
							ze\admin::phrase('Create multiple Documents in the Language "[[lang]]"',
								['lang' => ze\lang::name((($panel['key']['language'] ?? false) ?: ze::$defaultLang))]);
					}
				}
				
				break;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__content/panels/content':
			case 'zenario__content/panels/chained':
				
				if (isset($_GET['refiner__trash']) && !($_GET['refiner__template'] ?? false)) {
					unset($panel['item_buttons']['zenario_ctype_document__rescan_extract']);
				}
				
				foreach ($panel['items'] as &$item) {
					if ($item['type'] == 'document') {
						$item['traits']['is_document'] = true;
						$item['traits']['no_animations'] = true;
					}
				}
				
				break;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__content/panels/content':
				//Handle creating multiple Documents at once in Storekeeper
				if (($_POST['create_multiple'] ?? false) && ze\priv::check('_PRIV_EDIT_DRAFT', false, 'document')) {
					$newIds = [];
					
					//This sholud only be allowed if we know what the language will be
					if (($languageId = (($_POST['language'] ?? false) ?: ze::$defaultLang))) {
						
						if ($_REQUEST['refiner__template'] ?? false) {
							$cType = ze\row::get('layouts', 'content_type', ($_REQUEST['refiner__template'] ?? false));
						} else {
							$cType = $_POST['cType'] ?? false;
						}
						
						if ($cType == 'document') {
							
							if ($_REQUEST['refiner__template'] ?? false) {
								$layoutId = $_REQUEST['refiner__template'] ?? false;
							} else {
								$layoutId = ze\row::get('content_types', 'default_layout_id', ['content_type_id' => $cType]);
							}
							
							
							ze\fileAdm::exitIfUploadError(true, true, false, 'Filedata');
							
							if (ze\file::fileSizeBasedOnUnit(ze::setting('content_max_filesize'),ze::setting('content_max_filesize_unit')) < filesize($_FILES['Filedata']['tmp_name'])) {
								
								echo
									ze\admin::phrase(
										'The file "[[file]]" is [[size]], which is more than the Maximum Content File Size ([[maxContentSize]]) as set in site settings, and so cannot be uploaded.',
										['file' => htmlspecialchars($_FILES['Filedata']['name']), 'size' => ze\file::fileSizeConvert(filesize($_FILES['Filedata']['tmp_name'])), 'maxContentSize' => ze\file::fileSizeConvert(ze\file::fileSizeBasedOnUnit(ze::setting('content_max_filesize'),ze::setting('content_max_filesize_unit')))]);
							
							} else {
								$filename = preg_replace('/([^.a-z0-9]+)/i', '_', $_FILES['Filedata']['name']);
								
								if ($fileId = ze\file::addToDocstoreDir('content', $_FILES['Filedata']['tmp_name'], $filename)) {
									$cID = $cVersion = false;
									ze\contentAdm::createDraft($cID, false, $cType, $cVersion, false, $languageId);
									ze\row::set(
										'content_item_versions',
										['layout_id' => $layoutId, 'title' => $filename, 'filename' => $filename, 'file_id' => $fileId],
										['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
									$newIds[] = $cType. '_'. $cID;
									
									ze\file::updatePlainTextExtract($cID, $cType, $cVersion, $fileId);
								}
							}
						}
					}
					
					return $newIds;
				
				
				} elseif (($_POST['rescan_extract'] ?? false) && ze\priv::check('_PRIV_EDIT_DRAFT')) {
					self::rescanExtract($ids, $showResultMessage = true);
				}
				
				break;
		}
	}

	public static function getS3FilePresignedUrl($keyName) {
		
		$presignedUrl = '';
		if (ze::setting('aws_s3_support')) {
		$bucketArn = ze::setting('aws_s3_bucket');
		$bucket = ltrim($bucketArn, 'arn:aws:s3:::');
		$s3 = Aws\S3\S3Client::factory([
			'credentials' => [
				'key' => ze::setting('aws_s3_key_id'),
				'secret' => ze::setting('aws_s3_secret_key')
			],
			'version' => 'latest',
			'region' => ze::setting('aws_s3_region')
		]);
		$bucketResponse = $s3->doesBucketExist($bucket);
			if ($bucketResponse) {
				$response = $s3->doesObjectExist($bucket, $keyName);
				if ($response) {
					$details = [
						'Bucket' => $bucket,
						'Key' => $keyName
					];

					$cmd = $s3->getCommand('GetObject', $details);

					$fileDetails = $s3->getObject($details);

					$expireIn = '+1 hour';
					if ($fileDetails) {
						if ($fileDetails['ContentType'] == 'video/mp4') {
							$expireIn = '+3 hours';
						}
					}

					$request = $s3->createPresignedRequest($cmd, $expireIn);
					
					// Get the actual presigned-url
					$presignedUrl = (string)$request->getUri();
				}
			}
		}
		if ($presignedUrl) {
			return $presignedUrl;
		} else {
			return '';
		}

	}

	public static function getS3FileDetails($s3FileId) {
		if (ze::setting('aws_s3_support') && $s3FileId) {
			$fileDetails = ze\row::get('files', ['filename', 'checksum'], ['id' => (int) $s3FileId]);

			$bucketArn = ze::setting('aws_s3_bucket');
			$bucket = ltrim($bucketArn, 'arn:aws:s3:::');
			$folderName = preg_replace('/\W/', '_', $fileDetails['filename']). '_'. $fileDetails['checksum'];
			$s3 = new Aws\S3\S3Client([
				'region'  => ze::setting('aws_s3_region'),
				'version' => 'latest',
				'credentials' => [
					'key'    => ze::setting('aws_s3_key_id'),
					'secret' => ze::setting('aws_s3_secret_key'),
				]
			]);

			// Send a PutObject request and get the result object.
			$bucketResponse = $s3->doesBucketExist($bucket);
			if ($bucketResponse) {
				$key = $folderName . '/' . $fileDetails['filename'];

				$details = [
					'Bucket' => $bucket,
					'Key'    => $key
				];

				$objectExists = $s3->doesObjectExist($bucket, $key);

				if ($objectExists) {
					$result = $s3->getObject($details);
					return $result;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	public static function uploadS3File($usage, $s3CachePath, $s3Filename, $s3MimeType, $fileInsert = true) {
		if (ze::setting('aws_s3_support')) {
			$s3Filename = ze\file::safeName($s3Filename);
			$checksum = ze::base16To64(md5_file($s3CachePath));
			$presignedUrl = '';
			$bucketArn = ze::setting('aws_s3_bucket');
			$bucket = ltrim($bucketArn, 'arn:aws:s3:::');
			$folderName = preg_replace('/\W/', '_', $s3Filename). '_'. $checksum;
			$s3 = new Aws\S3\S3Client([
				'region'  => ze::setting('aws_s3_region'),
				'version' => 'latest',
				'credentials' => [
					'key'    => ze::setting('aws_s3_key_id'),
					'secret' => ze::setting('aws_s3_secret_key'),
				]
			]);

			// Send a PutObject request and get the result object.
			$bucketResponse = $s3->doesBucketExist($bucket);
			if ($bucketResponse) {
				$key = $folderName.'/'.$s3Filename;

				$details = [
					'Bucket' => $bucket,
					'Key'    => $key,
					'SourceFile' => $s3CachePath,
					'ContentType' => $s3MimeType
				];

				$result = $s3->putObject($details);
				
				if($fileInsert) {
					$file = [];
					$filekey = ['checksum' => $checksum, 'usage' => $usage];
					$file['size'] = filesize($s3CachePath);
					$file['checksum'] = $checksum;
					$file['filename'] = $s3Filename;
					$file['usage'] = $usage;
					$file['mime_type'] = ze\file::mimeType($s3Filename);
					if (ze\file::isImage($file['mime_type'])) {
						$image = getimagesize($s3CachePath);
						$file['width'] = $image[0];
						$file['height'] = $image[1];
						$file['mime_type'] = $image['mime'];
					}
					
					$file['privacy'] = ze::oneOf(\ze::setting('default_image_privacy'), 'auto', 'public', 'private');
					
					$filenameArray = explode('.', $s3Filename);
					$altTag = trim(preg_replace('/[^a-z0-9]+/i', ' ', $filenameArray[0]));
					$file['alt_tag'] = $altTag;
					$file['archived'] = 0;
					$file['created_datetime'] = \ze\date::now();
					$file['location'] = 's3';
					$file['path'] = $folderName;
					$file['data'] = null;
					
					$fileId = \ze\row::set('files', $file, $filekey);
					$file['fid'] = $fileId;
					
					return $file;
					
				} else {
					return $result;
				}
			
			} else {
				return;
			}
		} else {
			return;
		}
	}

	public static function rescanExtract($ids, $showResultMessage = false) {
		$stats = ['successes' => 0, 'fails' => 0];
		$cID = $cType = false;
		foreach (explode(',', $ids) as $id) {
			if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $id)) {
				if (ze\priv::check('_PRIV_EDIT_DRAFT', $cID, $cType)) {
					if ($cType == 'document') {
						if ($row = ze\row::get(
							'content_items',
							['id', 'type', 'admin_version', 'visitor_version'],
							['id' => $cID, 'type' => $cType, 'status' => ['!1' => 'trashed', '!2' => 'deleted']]
						)) {
							$doneSomething = false;
							$success = true;
						
							if ($row['admin_version']) {
								$doneSomething = true;
								$success &= ze\file::updatePlainTextExtract($row['id'], $row['type'], $row['admin_version']);
							}
						
							if ($row['visitor_version'] && $row['visitor_version'] != $row['admin_version']) {
								$doneSomething = true;
								$success &= ze\file::updatePlainTextExtract($row['id'], $row['type'], $row['visitor_version']);
							}
						
							if ($doneSomething) {
								if ($success) {
									++$stats['successes'];
								} else {
									++$stats['fails'];
								}
							}
						}
					}
				}
			}
		}
		
		if ($showResultMessage) {
			if ($stats['fails']) {
				echo '<!--Message_Type:Error-->';
				
				if ($stats['successes']) {
					echo ze\admin::phrase('[[successes]] extract(s) were updated. [[fails]] extract(s) could not be updated.', $stats);
				} else {
					echo ze\admin::phrase('[[fails]] extract(s) could not be updated.', $stats);
				}
			} else if ($stats['successes']) {
				echo '<!--Message_Type:Success-->';
				echo ze\admin::phrase('[[successes]] extract(s) were updated.', $stats);
			} else {
				echo '<!--Message_Type:Warning-->';
				echo ze\admin::phrase('No extracts were updated.');
			}
		}
	}
}