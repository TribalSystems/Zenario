<?php
/*
 * Copyright (c) 2024, Tribal Limited
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

class zenario_multiple_image_container extends ze\moduleBaseClass {
	
	protected $mergeFields = [];
	
	protected $empty = false;
	protected $zipArchiveName;
	
	public function init() {
		$imageId = false;
		$fancyboxLink = false;
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetOrPostVarIsSet = true, $ifSessionVarOrCookieIsSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByFile = true, $clearByModuleData = false);
		
		$allIds = [];
		
		foreach (ze\ray::explodeAndTrim($this->setting('image'), true) as $imageId) {
			if (($imageId = (int) trim($imageId))
			 && ($image = ze\row::get('files', [
			 	'id', 'alt_tag', 'title', 'floating_box_title', 'image_credit',
			 	'size', 'created_datetime'
			 ], $imageId))) {
				
				$imageMF = [
					'Popout' => false
				];
				
				$cssRules = [];
				$imageMF['Image_HTML'] = ze\file::imageHTML(
					$cssRules, $preferInlineStypes = true,
					$imageId, $this->setting('width'), $this->setting('height'), $this->setting('canvas'), $this->setting('retina'), $this->setting('webp'),
					$image['alt_tag'], $htmlID = '', $cssClass = '', $styles = '', 'title="'. htmlspecialchars($this->phrase($image['title'])). '"',
					$showAsBackgroundImage = false, $this->setting('lazy_load')
				);
				
				if ($this->setting('show_image_credit_on_thumbnail') && !empty($image['image_credit'])) {
					$imageMF['Image_Credit'] = true;
					$imageMF['Image_Credit_Text'] = $image['image_credit'];
				}
				
				if ($caption = (string) $this->setting('image_title_'. $imageId)) {
					$imageMF['Caption'] = $caption;
				}
				
				if ($this->setting('show_link_to_download_original')) {
					$imageMF['File_Link'] = ze\file::linkForCurrentVisitor($image['id']);
				}
				
				if ($this->setting('show_file_size')) {
					$imageMF['File_Size'] = ze\lang::formatFilesizeNicely($image['size']);
				}
				
				if ($this->setting('show_image_uploaded_date')) {
					$imageMF['Uploaded_Date'] = ze\date::format($image['created_datetime']);
				}
				
				
				if ($this->setting('link_type_'. $imageId) == '_ENLARGE_IMAGE') {
					
					$width = $height = $url = $webPURL = $isRetina = $mimeType = false;
					if (ze\file::imageAndWebPLink($width, $height, $url, $this->setting('enlarge_webp'), $webPURL, false, $isRetina, $mimeType, $imageId, $this->setting('enlarge_width'), $this->setting('enlarge_height'), $this->setting('enlarge_canvas'))) {
						
						$this->requireJsLib('zenario/libs/manually_maintained/mit/colorbox/jquery.colorbox.min.js');
						
						$imageMF['Enlarge_Image'] = true;
						$imageMF['Image_Link_Href'] = 'rel="colorbox" href="' . htmlspecialchars($url) . '" class="enlarge_in_fancy_box" ';
					
						if ($webPURL) {
							$imageMF['Image_Link_Href'] .= ' data-webp-href="'. htmlspecialchars($webPURL). '"';
						}
						
						if ($this->setting('show_image_credit_on_enlarged_image') && !empty($image['image_credit'])) {
							$icText = $this->phrase('Credit: [[image_credit]]', $image);
							
							if (empty($caption)) {
								$cbCaption = htmlspecialchars($icText);
							} else {
								$cbCaption = htmlspecialchars($caption). ' ('. htmlspecialchars($icText). ')';
							}
						} else {
							$cbCaption = '';
							if (empty($caption)) {
								$cbCaption = '';
							} else {
								$cbCaption = htmlspecialchars($caption);
							}
						}
						
						if (!empty($imageMF['Uploaded_Date'])) {
							$cbCaption .= '<span class="uploaded_date">'. htmlspecialchars($imageMF['Uploaded_Date']). '</span>';
						}
					
						if (!empty($imageMF['File_Link'])) {
							$cbCaption .= '<span class="download_link">';
								$cbCaption .= '<a href="'. htmlspecialchars($imageMF['File_Link']). '" download>';
									$cbCaption .= htmlspecialchars($this->phrase('Download original'));
									
									if (!empty($imageMF['File_Size'])) {
										$cbCaption .= ' ('. htmlspecialchars($imageMF['File_Size']). ')';
									}
								$cbCaption .= '</a>';
							$cbCaption .= '</span>';
						}
						
						if (empty($cbCaption)) {
							$imageMF['Image_Link_Href'] .= ' data-box-className="multiple_image_container caption_hidden"';
						} else {
							$imageMF['Image_Link_Href'] .= ' data-box-className="multiple_image_container"';
							$imageMF['Image_Link_Href'] .= ' data-box-title="'. htmlspecialchars($cbCaption). '"';
						}
					}
				
				} else {
					$cID = $cType = false;
					$this->setupLink($imageMF, $cID, $cType, $useTranslation = true, 'link_type_'. $imageId, 'hyperlink_target_'. $imageId, 'target_blank_'. $imageId, 'url_'. $imageId, $imageId);
				}
				
				
				if (!isset($this->mergeFields['Images'])) {
					$this->mergeFields['Images'] = [];
				}
				$this->mergeFields['Images'][] = $imageMF;

				$allIds[] = $imageId;
			}
		}
		
		$this->mergeFields['Title'] = $this->setting('title');
		$this->mergeFields['Title_Tags'] = $this->setting('title_tags') ? $this->setting('title_tags') : 'h2';
		
		if (!$this->isVersionControlled && $this->setting('translate_text')) {
			if ($this->mergeFields['Title']) {
				$this->mergeFields['Title'] = $this->phrase($this->mergeFields['Title']);
			}
		}
		
		if ($this->setting('zip_archive_enabled')) {
			if (($this->zipArchiveName = $this->setting('zip_archive_name')) == ''){
				$this->zipArchiveName = "images.zip";
			} else {
				$arr = explode(".", $this->zipArchiveName);
				if ((count($arr) < 2) || ($arr[count($arr) - 1] != "zip")) {
					$this->zipArchiveName .= ".zip";
				}
			}

			if (count($allIds) > 0) {
				$this->mergeFields['Image_ids_for_zip_download'] = implode(",", $allIds);
				$this->mergeFields['Slot_name'] = $this->slotName;
				$this->mergeFields['openForm'] = $this->openForm();
				$this->mergeFields['closeForm'] = $this->closeForm();
			}

			$this->mergeFields['No_images_to_download'] = $this->phrase('No images to download.');
		}
		
		//Don't show empty Banners
		//Note: If there is some more link text set, but no Image/Text/Title, then I'll still consider the Banner to be empty
		if (empty($this->mergeFields['Images'])
		 && empty($this->mergeFields['Title'])) {
			$this->empty = true;
			return false;
			
		} else {
			return true;
		}
	}
	
	function showSlot() {
		if (!$this->empty) {
			//Zip download feature
			$downloadPage = false;
			$linkResult = [];
			$fileName = $this->phrase('Prepare zip');
			$Link = '';
			$noContent = false;
			$mainLinkArr = [];
			$allIdsValue = '';
			if (!empty($_POST['prepareDownloadData']) && $_POST['slotName'] == $this->slotName && $this->checkPostIsMine()) {
				$getIds = $_POST['imageIds'];
				$zipFiles = [];
				$zipFile = [];
				$zipFileSize = 0;

				if (($maxUnpackedSize = (int) ze::setting('max_unpacked_size')) <= 0) {
					$maxUnpackedSize = 64;
				}

				$maxUnpackedSize *= 1048576;
				if ($imageIds = explode(",", $getIds)) {
					foreach ($imageIds as $imageID) {
						
						if ($zipFileSize + $this->getUnpackedFilesSize($imageID) > $maxUnpackedSize) {
							$zipFiles[] = $zipFile;
							$zipFile = [];
							$zipFileSize = 0;
						}
						
						$zipFile[] = $imageID;
						$zipFileSize += $this->getUnpackedFilesSize($imageID);
						
					}

					if (!empty($zipFile)) {
						$zipFiles[] = $zipFile;
					}
				}

				$fileCtr = 0;
				$fileDocCtr = 0;
				foreach ($zipFiles as $zipFileids) {
					if ($zipFileids) {
						$zipFileValue = implode(",", $zipFileids);
						$fileNameArr = [];
						$fileCtr++;
						if (sizeof($zipFiles) > 1) {
							$linkResult = $this->build($zipFileValue, $fileCtr);
						} else {
							$linkResult = $this->build($zipFileValue, 0);
						}

						if ($linkResult[0]) {
							if ($linkResult[1]) {
								
								$downloadPage = true;
								
								$fileNameArr['fileName'] = $linkResult[2];
								$fileNameArr['linkName'] = $linkResult[1];
								if (sizeof($zipFiles) > 1) {
									$fileDocCtrValue = '';
									$fileCtrVal = 0;
									foreach ($zipFileids as $filevaluectr) {
										$fileDocCtr++;
										$fileCtrVal++;
										if (sizeof($zipFileids) == $fileCtrVal) {
											$fileDocCtrValue .= $fileDocCtr;
										} else {
											$fileDocCtrValue .= $fileDocCtr . ', ';
										}
									}
									
									//There is no $fileNameArr['labelName'] set here, because the messages were getting too verbose.
									//Just the filename will be shown.
									//$fileNameArr['labelName'] = $this->phrase('Download volume ' . $fileCtr . ' of zip archive (contains images ' . $fileDocCtrValue . '):');
								} else {
									$fileNameArr['labelName'] = $this->phrase('Download zip archive:');
								}

								$fileNameArr['fileSize'] = $linkResult[3];

							} else {
								$noContent = true;
							}
						} else {
							if ((int)($_SESSION['admin_userid'] ?? false)){
								$downloadPage = true;
								$fileDocCtr++;
								$filename = '';
								if($zipFileids[0]) {
									$filename = ze\row::get('files', 'filename', ['id' => $zipFileids[0]]);
								}

								$fileNameArr['errorMsg'] = 'Error: image ' . $fileDocCtr . ' could not be archived. ' . nl2br($linkResult[1]);
							}
						}
						
						$mainLinkArr[] = $fileNameArr;

						$this->mergeFields['FilenameArr'] = ($fileNameArr ?? []);
						$this->mergeFields['Main_Link_Array'] = $mainLinkArr;
						$this->mergeFields['Image_ids_for_zip_download'] = false;
					}
				}
			} else {
				$allIds = explode(',', $this->setting('image'));
				$counter = count($allIds);
				
				ksort($allIds);
				$allIdsValue = implode(",", $allIds);

				if ($counter > 0 && $this->setting('zip_archive_enabled')) {
					$this->mergeFields['Image_ids_for_zip_download'] = $allIdsValue;
				}
			}

			$this->mergeFields['Download_Page'] = $downloadPage;
			$this->mergeFields['Empty_Archive'] = $noContent;
			
			//Display the Plugin
			$this->twigFramework($this->mergeFields);
		}
	}
	
	public function fillAdminSlotControls(&$controls) {
		//Do nothing special here
	}
	
	function getUnpackedFilesSize($ids = '') {
		$filesize = 0;
		if ($fileIDs = explode(",", $ids)) {
			foreach ($fileIDs as $fileID) {
				$filePath = ze\file::docstorePath($fileID);
				if (!empty($filePath)) {
					$filesize += filesize($filePath);
				}
			}
		}

		return $filesize;
	}

	function getArchiveNameNoExtension($archiveName) {
		$arr = explode(".", $archiveName);
		if (count($arr) > 1) {
			unset($arr[count($arr) - 1]);
			return implode(".", $arr);
		} else {
			return $archiveName;
		}
	}
	
	function canZip() {
		if (ze\server::isWindows() || !ze\server::execEnabled() || !$this->getZIPExecutable()) {
			return false;
		}
		exec(escapeshellarg($this->getZIPExecutable()) .' -v',$arr,$rv);
		return ! (bool)$rv;
	}
	function getZIPExecutable() {
		return ze\server::programPathForExec(ze::setting('zip_path'), 'zip');
	}

	function build($cIds, $fileCtr) {
		$archiveEmpty = true;
		$oldDir = getcwd();
		
		if (($maxUnpackedSize = (int)ze::setting('max_unpacked_size')) <= 0) {
			$maxUnpackedSize = 64;
		} 
		$maxUnpackedSize *= 1048576;
		
		if ($this->canZIP()) {
			if ($this->getUnpackedFilesSize($cIds ?? false) <= $maxUnpackedSize) {
				if ($imageIDs = explode(",", ($cIds ?? false))) {
					$zipArchive = $this->getZipArchiveName();
					if ($fileCtr > 0) {
						$explodeZip = explode(".", $zipArchive);
						$zipArchive = $explodeZip[0] . $fileCtr . '.' . $explodeZip[1];
					}
					
					if ($this->getArchiveNameNoExtension($zipArchive)) {
						ze\cache::cleanDirs();
						$randomDir = ze\cache::createRandomDir(15, 'private/downloads', $onlyForCurrentVisitor = ze::setting('restrict_downloads_by_ip'));
						$contentSubdirectory = $this->getArchiveNameNoExtension($zipArchive);
						if (mkdir($randomDir . '/' . $contentSubdirectory)){
							foreach ($imageIDs as $ID) {
								chdir($randomDir);
								
								$filePathAndFilename = ze\row::get('files', ['path', 'filename'], ['id' => $ID]);
								$filePath = (ze\file::docstorePath($ID));
								if (!empty($filePath) && !empty($filePathAndFilename['filename'])) {
									$nextFileName = $this->getNextFileName($contentSubdirectory . '/' . $filePathAndFilename['filename']);
									copy($filePath, $nextFileName);
									if (($err = $this->addToZipArchive($zipArchive, $nextFileName)) == "") {
										$archiveEmpty = false;
									} else {
										$errors[] = $err;
									}

									unlink($nextFileName);
								}

								chdir($oldDir);
							}

							rmdir($randomDir . '/' . $contentSubdirectory);

							if (isset($errors)) {
								return [false, implode('\n', $errors)];
							} elseif ($archiveEmpty) {
								return [true, []];
							} else {
								return [true, $randomDir . $zipArchive, $zipArchive, ze\file::formatSizeUnits(filesize($randomDir . $zipArchive))];
							}
						} else {
							return [false, 'Error. Cannot create the documents subdirectory. This can be caused by either: <br/> 1) Incorrect downloads folder write permissions.<br/> 2) Incorrect archive name.'];
						}
					} else {
						return [false, 'Error. Archive filename was not specified.'];
					}
				} else {
					return [true, []];
				}
			} else {
				return [false, 'The size of the file exceeds the ' . (int)ze::setting('max_unpacked_size') . 'MB per volume limit.'];
			}
		} else {
			return [false, 'Error. Cannot create ZIP archives using ' . $this->getZIPExecutable() . '.'];
		}
	}
	
	function getNextFileName($fileName) {
		return $fileName;
	}

	function addToZipArchive($archiveName,$filenameToAdd) {
		exec(escapeshellarg($this->getZIPExecutable()) . ' -r '. escapeshellarg($archiveName) . ' ' . escapeshellarg($filenameToAdd),$arr,$rv);
		if ($rv) {
			return 'Failure when adding the file ' . basename($filenameToAdd) . ' to the archive ' . basename($archiveName) . '.';
		}
		return "";
	}
	
	function getZipArchiveName() {
		return $this->zipArchiveName;
	}

	protected function setupLink(&$mergeFields, &$cID, &$cType, $useTranslation = true, $link_type = 'link_type', $hyperlink_target = 'hyperlink_target', $target_blank = 'target_blank', $url = false, $imageId='') {
		
		$mergeFields['Target_Blank'] = '';
		$link = $downloadFile = $cID = $cType = false;
		$linkExists = false;
		$linkTo = $this->setting($link_type);
		
		//Check to see if an item is set in the hyperlink_target setting 
		if ($linkTo == '_CONTENT_ITEM'
		 && ($linkExists = $this->getCIDAndCTypeFromSetting($cID, $cType, $hyperlink_target, $useTranslation))) {
			
			$request = '';
			
			$downloadFile = ($cType == 'document' && !$this->setting('use_download_page'));
			
			if ($downloadFile) {
				$request = 'download=1';
			}
			
			if (!$this->isVersionControlled && $useTranslation) {
				$link = ze\link::toItemInVisitorsLanguage($cID, $cType, $fullPath = false, $request);
			} else {
				$link = ze\link::toItem($cID, $cType, $fullPath = false, $request);
			}

			if ($this->setting('link_to_anchor') && ($anchor = $this->setting('hyperlink_anchor'))) {
			    $link .= '#' . rawurlencode($anchor);
			}
			if ($this->setting('link_to_anchor_'. $imageId) && ($anchor = $this->setting('hyperlink_anchor_'. $imageId))) {
			    $link .= '#' . rawurlencode($anchor);
			}
			//Use the Theme Section for a Masthead with a link and set the link
			$mergeFields['Image_Link_Href'] =
				'href="'. htmlspecialchars($link). '"';
			
			if ($downloadFile) {
				$mergeFields['Target_Blank'] = ' onclick="'. htmlspecialchars(ze\file::trackDownload($link)). '"';
			}
			
			
			$this->allowCaching(
				$atAll = true, $ifUserLoggedIn = false, $ifGetOrPostVarIsSet = true, $ifSessionVarOrCookieIsSet = true);
			$this->clearCacheBy(
				$clearByContent = true, $clearByMenu = false, $clearByFile = false, $clearByModuleData = false);
			
			//Check the Privacy settings on this banner
			if (!ze\priv::check()) {
				switch ($this->setting('hide_private_item')) {
					case '_LOGGED_IN':
						if (empty($_SESSION['extranetUserID'])) {
							return false;
						}
						break;
					
					case '_LOGGED_OUT':
						if (!empty($_SESSION['extranetUserID'])) {
							return false;
						}
						break;
					
					case '_PRIVATE':
						if (!ze\content::checkPerm($cID, $cType)) {
							return false;
						}
						break;
					
					default:
						$this->allowCaching(
							$atAll = true, $ifUserLoggedIn = true, $ifGetOrPostVarIsSet = true, $ifSessionVarOrCookieIsSet = true);
				}
			}
		
		} else
		if ($linkTo == '_DOCUMENT'
		 && ($documentId = $this->setting('document_id'))
		 && ($linkExists = (bool) $link = ze\file::getDocumentFrontEndLink($documentId))) {
			
			$document = ze\row::get('documents', ['filename', 'privacy'], ['id' => $documentId]);
			$contentItemPrivacy = ze\row::get('translation_chains', 'privacy', ['equiv_id' => ze::$equivId]);

			//Always show public documents,
			//Don't show private documents on public content items.
			if ($document['privacy'] == 'public' || ($document['privacy'] == 'private' && $contentItemPrivacy != 'public' && $contentItemPrivacy != 'logged_out')) {
				//Use the Theme Section for a Masthead with a link and set the link
				$mergeFields['Image_Link_Href'] =
					'href="'. htmlspecialchars($link). '"';
		
				$mergeFields['Target_Blank'] = ' onclick="'. htmlspecialchars(ze\file::trackDownload($link)). '"';
		
				$downloadFile = true;
				
				//Only allow caching for public documents.
				if ($document['privacy'] == 'public') {
					$this->allowCaching(
						$atAll = true, $ifUserLoggedIn = true, $ifGetOrPostVarIsSet = true, $ifSessionVarOrCookieIsSet = true);
					$this->clearCacheBy(
						$clearByContent = false, $clearByMenu = false, $clearByFile = true, $clearByModuleData = false);
				}
			} else {
				if (ze\admin::id()) {
					$mergeFields['privacy_warning'] = true;
					$mergeFields['filename'] = $document['filename'];
					$mergeFields['privacy'] = $document['privacy'];
				}
			}
			
		} else {
			$this->allowCaching(
				$atAll = true, $ifUserLoggedIn = true, $ifGetOrPostVarIsSet = true, $ifSessionVarOrCookieIsSet = true);
			$this->clearCacheBy(
				$clearByContent = false, $clearByMenu = false, $clearByFile = false, $clearByModuleData = false);
			
			// If the content item this banner was linking to has been removed, update setting to no-link
			if ($linkTo == '_CONTENT_ITEM' && !$linkExists) {
				
				if (!ze\content::getCIDAndCTypeFromTagId($cID, $cType, $this->setting($hyperlink_target))
				 || !(($equivId = ze\content::equivId($cID, $cType))
				   && ze\row::exists('content_items', ['equiv_id' => $equivId, 'type' => $cType, 'status' => ['!1' => 'trashed', '!2' => 'deleted']]))) {
					
					//Don't update the settings if this was just a preview!
					if (empty($_POST['overrideSettings'])) {
						$this->setSetting($link_type, '_NO_LINK', true);
						$this->setSetting($hyperlink_target, '', true);
						$this->setSetting($target_blank, '', true);
					}
				}
			
			//If a document that this banner was linking to has been removed, update the settingas not no-link as well.
			} elseif ($linkTo == '_DOCUMENT' && !$linkExists) {
				
				if (ze::isAdmin()) {
					if ($document = ze\row::get('documents', ['filename', 'privacy'], ['id' => $documentId])) {
						
						if ($document['privacy'] == 'offline') {
							$mergeFields['privacy_warning'] = true;
							$mergeFields['filename'] = $document['filename'];
							$mergeFields['privacy'] = $document['privacy'];
						}
					
					} else {
						//Don't update the settings if this was just a preview!
						if (empty($_POST['overrideSettings'])) {
							$this->setSetting($link_type, '_NO_LINK', true);
							$this->setSetting('document_id', '', true);
						}
					}
				}
			
			} elseif ($linkTo == '_EXTERNAL_URL') {
				if (!$url) {
					$url = 'url';
				}
				
				if ($link = $this->setting($url)) {
					$mergeFields['Image_Link_Href'] =
						'href="'. htmlspecialchars($link). '"';
				}
			
			}  elseif ($linkTo == '_EMAIL') {
				$url = 'email_address';
				if ($link = $this->setting($url)) {
					$mergeFields['Image_Link_Href'] =
						'href="'. htmlspecialchars($link). '"';
				}
			}
		}
		
		if ($link && ($openIn = $this->setting($target_blank))) {
			
			$mergeFields['Target_Blank'] .= ' target="_blank"';
			
			if (!$downloadFile && $openIn == 2) {
				$this->requireJsLib('zenario/libs/manually_maintained/mit/colorbox/jquery.colorbox.min.js');
				
				$mergeFields['Target_Blank'] .= ' onclick="if (window.$) { $.colorbox({href: \''. ze\escape::js($link). '\', iframe: true, width: \'95%\', height: \'95%\'}); return false; }"';
			}
		}
		
		return true;
	}
	
	public static function nestedPluginName($eggId, $instanceId, $moduleClassName) {
		
		$title = ze\plugin::setting('title', $instanceId, $eggId);
		
		if ($title) {
			return ze\admin::phrase('MIC:'). ' '. $title;
		}
			
		return parent::nestedPluginName($eggId, $instanceId, $moduleClassName);
	}
}