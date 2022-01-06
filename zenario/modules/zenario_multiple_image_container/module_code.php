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

class zenario_multiple_image_container extends zenario_banner {
	
	protected $zipArchiveName;
	
	public function init() {
		$imageId = false;
		$fancyboxLink = false;
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = true, $clearByModuleData = false);
		
		$allIds = [];
		$width = $height = $url = $widthFullSize = $heightFullSize = $urlFullSize = false;
		foreach (ze\ray::explodeAndTrim($this->setting('image'), true) as $imageId) {
			if (($imageId = (int) trim($imageId))
			 && ($image = ze\row::get('files', ['id', 'alt_tag', 'title', 'floating_box_title', 'size', 'created_datetime'], $imageId))
			 && ((ze\file::imageLink($width, $height, $url, $imageId, $this->setting('width'), $this->setting('height'), $this->setting('canvas'), $this->setting('offset'), $this->setting('retina'))))) {
				
				if (!isset($this->mergeFields['Images'])) {
					$this->mergeFields['Images'] = [];
				}
				
				$imageMF = [
					'Alt' => $this->phrase($image['alt_tag']),
					'Src' => $url,
					'Title' => $this->phrase($image['title']),
					'Width' => $width,
					'Height' => $height,
					'Popout' => false];
				
				if ($this->setting('link_type_'. $imageId) == '_ENLARGE_IMAGE'
				 && (ze\file::imageLink($widthFullSize, $heightFullSize, $urlFullSize, $imageId, $this->setting('enlarge_width'), $this->setting('enlarge_height'), $this->setting('enlarge_canvas')))) {
					
					$imageMF['Floating_Box'] = [
						'Src' => $urlFullSize,
						'Width' => $widthFullSize,
						'Height' => $heightFullSize,
						'Title' => $this->phrase($image['floating_box_title'])];
				} else {
					
					$cID = $cType = false;
					$this->setupLink($imageMF, $cID, $cType, $useTranslation = true, 'link_type_'. $imageId, 'hyperlink_target_'. $imageId, 'target_blank_'. $imageId, 'url_'. $imageId, $imageId);
				}
				
				
				if ($text = $this->setting('image_title_'. $imageId)) {
					$imageMF['Text'] = $text;
				}
				
				if ($this->setting('show_link_to_download_original')) {
					$imageMF['File_Link'] = ze\file::link($image['id']);
				}
				
				if ($this->setting('show_file_size')) {
					$imageMF['File_Size'] = ze\lang::formatFilesizeNicely($image['size']);
				}
				
				if ($this->setting('show_image_uploaded_date')) {
					$imageMF['Uploaded_Date'] = ze\date::format($image['created_datetime'], '_MEDIUM');
				}
				
				$this->mergeFields['Images'][] = $imageMF;

				$allIds[] = $imageId;
			}
		}
		
		$this->mergeFields['Text'] = $this->setting('text');
		$this->mergeFields['Title'] = htmlspecialchars($this->setting('title'));
		$this->mergeFields['Title_Tags'] = $this->setting('title_tags') ? $this->setting('title_tags') : 'h2';
		
		if (!$this->isVersionControlled && $this->setting('translate_text')) {
			if ($this->mergeFields['Text']) {
				$this->replacePhraseCodesInString($this->mergeFields['Text']);
			}
			if ($this->mergeFields['Title']) {
				$this->mergeFields['Title'] = $this->phrase($this->mergeFields['Title']);
			}
		}
		
		$this->mergeFields['Show_caption_on_image'] = $this->setting('show_caption_on_image');
		$this->mergeFields['Show_caption_above_thumbnail'] = $this->setting('show_caption_above_thumbnail');
		$this->mergeFields['Show_caption_on_enlarged_image'] = $this->setting('show_caption_on_enlarged_image');
		$this->mergeFields['Show_link_to_download_original'] = $this->setting('show_link_to_download_original');
		$this->mergeFields['Show_file_size'] = $this->setting('show_file_size');
		$this->mergeFields['Show_image_uploaded_date'] = $this->setting('show_image_uploaded_date');
		
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
		 && empty($this->mergeFields['Text'])
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
			if (!empty($_POST['prepareDownloadData']) && $_POST['slotName'] == $this->slotName) {
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
			foreach ($fileIDs as $fileID){
				$fileDetails = ze\row::get('files', ['path', 'filename'], ['id' => $fileID]);
				if (!empty($fileDetails) && $fileDetails['path'] && $fileDetails['filename']) {
					$filesize += filesize(ze::setting("docstore_dir") . "/" . $fileDetails['path'] . "/" . $fileDetails['filename']);
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
						$randomDir = ze\cache::createRandomDir(15, 'downloads', $onlyForCurrentVisitor = ze::setting('restrict_downloads_by_ip'));
						$contentSubdirectory = $this->getArchiveNameNoExtension($zipArchive);
						if (mkdir($randomDir . '/' . $contentSubdirectory)){
							foreach ($imageIDs as $ID) {
								chdir($randomDir);
								
								$filePathAndFilename = ze\row::get('files', ['path', 'filename'], ['id' => $ID]);
								if (!empty($filePathAndFilename) && $filePathAndFilename['path'] && $filePathAndFilename['filename']) {
									$nextFileName = $this->getNextFileName($contentSubdirectory . '/' . $filePathAndFilename['filename']);
									copy(ze::setting("docstore_dir") . "/" . $filePathAndFilename['path'] . "/" . $filePathAndFilename['filename'], $nextFileName);
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
}