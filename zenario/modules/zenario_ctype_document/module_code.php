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
			$atAll = true, $ifUserLoggedIn = true, $ifGetOrPostVarIsSet = false, $ifSessionVarOrCookieIsSet = true);
			
		
		
		if ($this->setting('show_details_and_link') == 'another_content_item') {
			$this->clearCacheBy(
				$clearByContent = true, $clearByMenu = false, $clearByFile = true, $clearByModuleData = false);
		} else {
			$this->clearCacheBy(
				$clearByContent = false, $clearByMenu = false, $clearByFile = true, $clearByModuleData = false);
		}
		
		if ($this->cType == 'document' && ze\priv::check()) {
			$this->callScript('zenario_wysiwyg_editor', 'hideAnimationsInEditors');
		}
		
		if ($this->setting('show_details_and_link')=='another_content_item'){
			$item = $this->setting('another_document');
			if (count($arr = explode("_",$item)) == 2) {
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
			['id'=> $this->targetID, 'type'=> $this->targetType, 'version'=> $this->targetVersion]
		)) {
			return false;
		}
		
		
		if ($this->setting('show_title')) {
			$this->mergeFields['Show_Title'] = true;
			$this->mergeFields['Title'] = htmlspecialchars($version['title']);
			$this->mergeFields['Title_Tags'] = $this->setting('title_tags');
		}
		
		if ($this->setting('show_language_name')) {
			$this->mergeFields['Show_Language_Name'] = true;
			
			$contentItemLanguageId = ze\row::get('content_items', 'language_id', ['id'=> $this->targetID, 'type'=> $this->targetType]);
			$showInWhichLanguage = $this->setting('show_language_name_in_which_language');
			if ($showInWhichLanguage == 'in_local_language') {
				$localName = true;
			} else {
				$localName = false;
			}
			
			$languageName = ze\lang::name($contentItemLanguageId, true, true, $localName);
			
			$this->mergeFields['Language_Name'] = htmlspecialchars($languageName);
		}
		
		if (!is_null($version['description'])) {
			if ($this->mergeFields['Description'] = htmlspecialchars($version['description'])) {
				$this->allowedChildSections['Summary_Section'] = true;
				$this->allowedChildSections['Description_Section'] = true;
			}
		}
			
		$type = explode('.', $version['filename']);
		$type = $type[count($type) - 1];
		$this->mergeFields['Type'] = htmlspecialchars($type);
		
		//When S3 is enabled in site setting
		if ($version['s3_filename']) {
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
				$width = (int)$this->setting('image_width');
				$height = (int)$this->setting('image_height');
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
		$s3FileDownloadPhrase = '';
		$s3Link = '';
		$s3Filesize = '';
		
		if (ze::get('download') && (!$this->eggId || $this->eggId == ze::get('eggId'))) {
			if (!ze\file::contentLink($url, $this->targetID, $this->targetType, $this->targetVersion)) {
				
				$body = ze\admin::phrase('An attempt to download the document [[url]] was unsuccessful.', ['url' => $this->linkToItem($this->targetID,$this->targetType,true)]);
				ze\db::reportError('Failed document download at', $body);
				
				$this->allowedChildSections['No_Document_Link_Section'] = true;
				$this->allowedChildSections['No_Document_Image_Link_Section'] = true;
			} else {
				$this->headerRedirect($url);
			}
		} else {
			
			if ($s3SupportEnabledOnSite = (ze::setting('enable_aws_support') && ze::setting('allow_document_content_items_to_be_stored_on_aws_s3'))) {
				$localFileDownload = ze::setting('local_file_link_text');
				$s3FileDownloadPhrase = ze::setting('s3_file_link_text');
			} else {
				//This will be translated in the framework.
				$localFileDownload = 'Download now';
			}
			
			$localFileDetails = ze\row::get('files', ['filename','path','size'], $version['file_id']);
			if ($this->setting('local_file') && $localFileDetails && $localFileDetails['filename']) {
				
				$this->mergeFields['Links_should'] = $this->setting('links_should');
				
				$request = 'download=1';
				
				if ($this->eggId) {
					$request .= '&eggId='. $this->eggId;
				}
				
				$link = $this->linkToItem($this->targetID, $this->targetType, false, $request);
				$link = htmlspecialchars($link);

				if ($this->setting('show_view_link')) {
					$linkForViewing = ze\file::linkForCurrentVisitor($version['file_id'], false, $type = 'private/downloads');
					$this->mergeFields['Link_For_Viewing'] = htmlspecialchars($linkForViewing);
				}

				$this->mergeFields['Show_Local_File_Type_And_Size'] = $this->setting('show_local_file_type_and_size');
				
				if ($this->setting('show_filename_local_file')) {
					$this->mergeFields['Local_File_Name'] = $localFileDetails['filename'];
				}
			} else {
				$link = '';
			}
			
			if ($s3SupportEnabledOnSite && $version['s3_file_id']) {
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
					$s3FileDownloadPhrase = ze::setting('s3_file_play_video_text');
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
			
			$this->mergeFields['File_Size'] = $localFileSize;

			$this->mergeFields['Aws_Support_Enabled_On_Site'] = $s3SupportEnabledOnSite;
			$this->mergeFields['Show_S3_File_Type_And_Size'] = $this->mergeFields['Aws_Support_Enabled_On_Site'] && $this->setting('show_s3_file_type_and_size');
			$this->mergeFields['S3_Size'] = $s3Filesize;
			$this->mergeFields['S3_File_Id'] = $version['s3_file_id'];
			$this->mergeFields['S3_Link'] = $s3Link;
			$this->mergeFields['S3_File_Not_Found_Error_Phrase'] = $this->phrase($this->setting('s3_file_not_found_error_phrase'));
			$this->mergeFields['Google_Analytics_Link'] = htmlspecialchars(ze\file::trackDownload($link));
			$this->mergeFields['Download_Local_File'] = $localFileDownload;
			$this->mergeFields['S3_File_Download_Phrase'] = $s3FileDownloadPhrase;
			$this->mergeFields['module_loc'] = ze::moduleDir('zenario_ctype_document');

			if ($this->cType == 'document') {
				if ($this->setting('show_categories')) {
					$this->mergeFields['Show_categories'] = true;

					$sql = "
						SELECT GROUP_CONCAT(cat.name SEPARATOR ', ') AS categories
						FROM " . DB_PREFIX . "category_item_link cil
						INNER JOIN " . DB_PREFIX . "categories cat
							ON cat.id = cil.category_id
						INNER JOIN " . DB_PREFIX . "content_items c
							ON cil.equiv_id = c.equiv_id
						INNER JOIN " . DB_PREFIX . "content_item_versions v
							ON v.id = c.id
							AND v.type = c.type
							AND v.tag_id = c.tag_id
						WHERE cil.content_type = 'document'
						AND v.id = " . (int) $this->cID . "
						AND v.type = 'document'
						AND v.version = " . (int) $this->cVersion . "
						AND cat.parent_id = 0
						AND cat.public = 1
						GROUP BY cil.equiv_id";
					$result = ze\sql::select($sql);
					$this->mergeFields['Categories'] = ze\sql::fetchValue($result, 'equiv_id');
				}

				if ($this->setting('show_document_extra_data') && ze\module::inc('zenario_ctype_document_extra_data')) {
					$this->mergeFields['Show_document_extra_data'] = true;

					$documentExtraData = ze\module::sendSignal('signalGetContentItemExtraData', [$this->cID, $this->cType, $this->cVersion]);
					if (
						!empty($documentExtraData)
						&& is_array($documentExtraData)
						&& !empty($documentExtraData['zenario_ctype_document_extra_data'])
						&& is_array($documentExtraData['zenario_ctype_document_extra_data'])
					) {
						$this->mergeFields['Extra_data'] = [];
						foreach ($documentExtraData['zenario_ctype_document_extra_data'] as $extraDataKey => $extraDataValue) {
							$this->mergeFields['Extra_data'][$extraDataKey] = $extraDataValue;
						}
					}
				}
			}
		}
		
		if ($this->setting('show_permalink')) {
			$this->requireJsPhrases('zenario/js/visitor.phrases.js.php');

			if (!ze::isAdmin()) {
				$this->requireJsLib('zenario/libs/yarn/toastr/toastr.min.js', 'zenario/libs/yarn/toastr/build/toastr.min.css');
			}
		}
		
		return true;
	}

	function showSlot() {
		if ($this->targetType != 'document') {
			if ((int)($_SESSION['admin_userid'] ?? false)){
				echo "This plugin must be placed on a Document-type content item, or configured to point to another Document-type content item. Please check your plugin settings.";
			}
			return;
		}
		
		$this->framework( 'Outer', $this->mergeFields, $this->allowedChildSections );
	}
	

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if ($path == 'site_settings' && $settingGroup == 'aws_s3_and_vector_data_processing') {
			
			if (!class_exists('Aws\\S3\\S3Client')) {
				$values['awss3_file_downloads/allow_document_content_items_to_be_stored_on_aws_s3'] = '';
				$fields['awss3_file_downloads/allow_document_content_items_to_be_stored_on_aws_s3']['disabled'] = true;
			}
		
		} elseif ($path == 'plugin_settings') {
			if (ze::setting('enable_aws_support') && ze::setting('allow_document_content_items_to_be_stored_on_aws_s3')) {
				$fields['first_tab/download_source']['hidden'] = false;
				$fields['first_tab/s3_file']['hidden'] = false;
				$fields['first_tab/show_filename_s3_file']['hidden'] = false;

				$fields['first_tab/show_view_button']['label'] = ze\admin::phrase('Show link to document download page');
				$fields['first_tab/local_file']['label'] = ze\admin::phrase('Show local file Download button for immediate download (if local file exists)');
				$fields['first_tab/s3_file']['label'] = ze\admin::phrase('Show S3 file Download button for immediate download');
			}
			
			$defaultLanguage = ze\lang::name(ze::$defaultLang);
			ze\lang::applyMergeFields($fields['first_tab/show_language_name_in_which_language']['values']['in_site_default_language'], ['default_site_language' => $defaultLanguage]);

			//Extra option available if the "Document content type: extra data"
			//module is running.
			if (!ze\module::isRunning('zenario_ctype_document_extra_data')) {
				unset($box['tabs']['first_tab']['fields']['show_document_extra_data']);
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
		        $this->showHideImageOptions($fields, $values, 'first_tab', $hidden, 'image_');
				
				if (ze::setting('enable_aws_support') && ze::setting('allow_document_content_items_to_be_stored_on_aws_s3')) {
					$fields['first_tab/download_source']['hidden'] = false;
					$fields['first_tab/local_file']['hidden'] = false;
					$fields['first_tab/s3_file']['hidden'] = false;
				}
				
		        break;
			
			
			case 'zenario_content':
				$fields['file/text_extract_processing']['hidden'] =
				$fields['file/text_extract']['hidden'] =
				$fields['file/text_extract_word_count']['hidden'] = true;
				
				if ($box['key']['cType'] == 'document') {
					$box['tabs']['file']['hidden'] = false;
					unset($box['tabs']['file']['fields']['file']['upload']['accept']);
					unset($box['tabs']['file']['fields']['file']['upload']['extensions']);
					
					if ($values['file/file']
					 && is_numeric($values['file/file'])
					 && ($extract = ze\row::get('file_extracts', ['extract', 'extract_wordcount', 'extract_source', 'extract_status', 'requested_on'], $values['file/file']))) {
				
						switch ($extract['extract_status']) {
							case 'completed':
								$fields['file/text_extract']['hidden'] =
								$fields['file/text_extract_word_count']['hidden'] = false;
								$fields['file/text_extract']['value'] = $extract['extract'];
								$fields['file/text_extract_word_count']['value'] = $extract['extract_wordcount'];
								
								switch ($extract['extract_source']) {
									case 'antiword':
										$fields['file/text_extract']['label'] = ze\admin::phrase('Text extract by Antiword:');
										break;
									case 'pdftotext':
										$fields['file/text_extract']['label'] = ze\admin::phrase('Text extract by pdftotext:');
										break;
									case 'Textract':
										$fields['file/text_extract']['label'] = ze\admin::phrase('Text extract by Textract:');
										break;
									case 'ZipArchive':
										$fields['file/text_extract']['label'] = ze\admin::phrase('Text extract:');
										break;
								}
								
								if (ze::setting('aws_textract_extract_from_jpg_and_png')) {
									$fields['file/text_extract']['note_below'] = ze\admin::phrase('Cannot be edited. With PDFs, PNG and JPEG images, and Word files, Zenario makes a plain-text extract of the file\'s contents. To re-run an extract, close this box and click "Rescan".');
								} else {
									$fields['file/text_extract']['note_below'] = ze\admin::phrase('Cannot be edited. With PDFs and Word files, Zenario makes a plain-text extract of the file\'s contents. To re-run an extract, close this box and click "Rescan".');
								}
								
								break;
							
							
							case 'processing':
								$mrg = ['ago' => \ze\admin::formatRelativeDateTime($extract['requested_on'], 'day', false)];
								
								$fields['file/text_extract_processing']['hidden'] = false;
								$fields['file/text_extract_processing']['notices_below']['text_extract_processing']['message'] =
									ze\admin::phrase('The text extract for this file is still processing. Scan requested from Textract [[ago]], the extract will appear here in a few minutes.', $mrg);
								
								break;
						}
					}
				}
				
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'zenario_content':
				if ($box['key']['cType'] == 'document') {
					if (ze\ring::engToBoolean($box['tabs']['file']['edit_mode']['on'] ?? false)) {
						if (ze::setting('enable_aws_support') && ze::setting('allow_document_content_items_to_be_stored_on_aws_s3')) {
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
				if ($settingGroup == 'aws_s3_and_vector_data_processing') {
					
					if ($values['awss3_file_downloads/enable_aws_support'] && $values['awss3_file_downloads/allow_document_content_items_to_be_stored_on_aws_s3'] && class_exists('Aws\\S3\\S3Client')) {
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
								$box['tabs']['awss3_file_downloads']['errors']['aws'] = ze\admin::phrase('Connection failed! Please check your credentials.');
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
				if ($panel['key']['cType'] != 'document') {
					unset($panel['collection_buttons']['zenario_ctype_document__create_multiple']);
					unset($panel['collection_buttons']['aws_s3_and_vector_data_processing']);
				}
				
				if (!ze::in($mode, 'full', 'quick', 'select')) {
					unset(
						$panel['item_buttons']['no_extract'],
						$panel['item_buttons']['extract_processing'],
						$panel['item_buttons']['extract_textract'],
						$panel['item_buttons']['extract_antiword'],
						$panel['item_buttons']['extract_pdftotext'],
						$panel['item_buttons']['extract_zip']
					);
				}
				
				break;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__content/panels/content':
			case 'zenario__content/panels/chained':
				
				if (isset($_GET['refiner__trash']) && !ze::get('refiner__template')) {
					unset($panel['item_buttons']['zenario_ctype_document__rescan_extract']);
				}
				
				break;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__content/panels/content':
				//Handle creating multiple Documents immediately in Organizer
				if (ze::post('create_multiple') && ze\priv::check('_PRIV_EDIT_DRAFT', false, 'document')) {
					$newIds = [];
					
					//This sholud only be allowed if we know what the language will be
					if (($languageId = (ze::post('language') ?: ze::$defaultLang))) {
						
						if (ze::request('refiner__template')) {
							$cType = ze\row::get('layouts', 'content_type', ze::request('refiner__template'));
						} else {
							$cType = $_POST['cType'] ?? false;
						}
						
						if ($cType == 'document') {
							
							if (ze::request('refiner__template')) {
								$layoutId = $_REQUEST['refiner__template'] ?? false;
							} else {
								$layoutId = ze\row::get('content_types', 'default_layout_id', ['content_type_id' => $cType]);
							}
							
							
							ze\fileAdm::exitIfUploadError(true, true, false, 'Filedata');
							
							if (ze\file::fileSizeBasedOnUnit(ze::setting('content_max_filesize'),ze::setting('content_max_filesize_unit')) < filesize($_FILES['Filedata']['tmp_name'])) {
								
								echo
									ze\admin::phrase(
										'The file "[[file]]" is [[size]], which is more than the maximum content file size ([[maxContentSize]]) as set in site settings, and so cannot be uploaded.',
										['file' => htmlspecialchars($_FILES['Filedata']['name']), 'size' => ze\file::fileSizeConvert(filesize($_FILES['Filedata']['tmp_name'])), 'maxContentSize' => ze\file::fileSizeConvert(ze\file::fileSizeBasedOnUnit(ze::setting('content_max_filesize'),ze::setting('content_max_filesize_unit')))]);
							
							} else {
								$filename = preg_replace('/([^.a-z0-9_\(\)\[\]]+)/i', '-', $_FILES['Filedata']['name']);

								$fileData = pathinfo($_FILES['Filedata']['name']);
								$filenameForTitle = preg_replace('/([^.a-z0-9\-_\(\)\[\]\'\"]+)/i', ' ', $fileData['filename']);
								
								if ($fileId = ze\file::addToDocstoreDir('content', $_FILES['Filedata']['tmp_name'], $filename)) {
									$cID = $cVersion = false;
									ze\contentAdm::createDraft($cID, false, $cType, $cVersion, false, $languageId);
									ze\row::set(
										'content_item_versions',
										['layout_id' => $layoutId, 'title' => $filenameForTitle, 'filename' => $filename, 'file_id' => $fileId],
										['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
									$newIds[] = $cType. '_'. $cID;
									
									ze\file::updateDocumentContentItemExtract($cID, $cType, $cVersion, $fileId);
								}
							}
						}
					}
					
					return $newIds;
				
				
				} elseif (ze::post('rescan_extract') && ze\priv::check('_PRIV_EDIT_DRAFT')) {
					self::rescanExtract($ids, $showResultMessage = true);
				}
				
				break;
		}
	}

	public static function getS3FilePresignedUrl($keyName) {
		
		$presignedUrl = '';
		if (ze::setting('enable_aws_support') && ze::setting('allow_document_content_items_to_be_stored_on_aws_s3')) {
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
		if (ze::setting('enable_aws_support') && ze::setting('allow_document_content_items_to_be_stored_on_aws_s3') && $s3FileId) {
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
		if (ze::setting('enable_aws_support') && ze::setting('allow_document_content_items_to_be_stored_on_aws_s3')) {
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
					
					$file['privacy'] = ze::oneOf(ze::setting('default_image_privacy'), 'auto', 'public', 'private');
					
					$filenameArray = explode('.', $s3Filename);
					$altTag = trim(preg_replace('/[^a-z0-9]+/i', ' ', $filenameArray[0]));
					$file['alt_tag'] = $altTag;
					$file['archived'] = 0;
					
					//If the file already exists in the DB, keep some of its files table values without overwriting.
					//Otherwise, set them on the new file.
					if (!ze\row::exists('files', $filekey)) {
						$file['location'] = 's3';
						$file['created_datetime'] = \ze\date::now();
						$file['path'] = $folderName;
						$file['data'] = null;
					}
					
					$fileId = ze\row::set('files', $file, $filekey);
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
		$failed = $processing = $completed = 0;
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
							$forceRescan = true;
							$doneSomething = false;
							$success = true;
							$extractOneStatus = '';
							$extractTwoStatus = '';
						
							//Plain text extracts for the latest published version and any drafts are stored separately,
							//because a draft may use a different file. Update the extract for both if applicable.
							if ($row['admin_version']) {
								$extract = ze\file::updateDocumentContentItemExtract($row['id'], $row['type'], $row['admin_version'], false, $forceRescan);
								$extractOneStatus = $extract['extract_status'] ?? '';
								$doneSomething = true;
								$forceRescan = false;
							}
						
							if ($row['visitor_version'] && $row['visitor_version'] != $row['admin_version']) {
								$extract = ze\file::updateDocumentContentItemExtract($row['id'], $row['type'], $row['visitor_version'], false, $forceRescan);
								$extractTwoStatus = $extract['extract_status'] ?? '';
								$doneSomething = true;
								$forceRescan = false;
							}
							
							if ($extractOneStatus == 'failed'
							 || $extractTwoStatus == 'failed') {
								++$failed;
							
							} elseif ($extractOneStatus == 'processing'
							 || $extractTwoStatus == 'processing') {
								++$processing;
							
							} elseif ($extractOneStatus == 'completed'
							 || $extractTwoStatus == 'completed') {
								++$completed;
							}
						}
					}
				}
			}
		}
		
		if ($showResultMessage) {
			if ($failed) {
				ze\escape::bFlag('MESSAGE_TYPE', 'error');
			
			} elseif ($completed || $processing) {
				ze\escape::bFlag('MESSAGE_TYPE', 'success');
			} else {
				ze\escape::bFlag('MESSAGE_TYPE', 'warning');
				echo ze\admin::phrase('No extracts were updated.');
			}
			
			if ($completed) {
				echo ze\admin::nPhrase('1 extract was updated.', '[[count]] extracts were updated.', $completed), ' ';
			}
			if ($processing) {
				echo ze\admin::nPhrase('1 extract is in process and will be updated soon.', '[[count]] extracts are in process and will be updated soon.', $processing), ' ';
			}
			if ($failed) {
				echo ze\admin::nPhrase('1 extract could not be updated.', '[[count]] extracts could not be updated.', $failed), ' ';
			}
		}
	}
}