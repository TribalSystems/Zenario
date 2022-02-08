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

class zenario_videos_manager__admin_boxes__videos_manager__video extends zenario_videos_manager {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$videoCategories = ze\row::getAssocs(ZENARIO_VIDEOS_MANAGER_PREFIX . 'categories', 'name');

		if (!empty($videoCategories)) {
			$fields['details/categories']['values'] = $videoCategories;
		} else {
			$fields['details/categories']['hidden'] = true;
			$fields['details/categories_not_found']['hidden'] = false;
		}
		
		//Load languages - only when zenario_document_envelopes_fea module is running
		//(soft depengency)
		$documentEnvelopesModuleIsRunning = ze\module::inc('zenario_document_envelopes_fea');
		if ($documentEnvelopesModuleIsRunning) {
			$fields['details/language_id']['values'] = ze\dataset::centralisedListValues('zenario_document_envelopes_fea::getEnvelopeLanguages');
			$fields['details/language_id']['hidden'] = false;
			
			$href = ze\link::absolute() . 'organizer.php#zenario__document_envelopes/panels/envelope_languages';
			$fields['details/language_id']['note_below'] = ze\admin::phrase('<a target="_blank" href=[[href]]>Click here to manage languages</a>', ['href' => $href]);
			
			if (!ze::setting('video_language_is_mandatory')) {
				unset($fields['details/language_id']['validation']);
			}
		}
		
		if ($box['key']['id']) {
			$video = ze\row::get(ZENARIO_VIDEOS_MANAGER_PREFIX . 'videos', true, $box['key']['id']);
			
			$box['title'] = ze\admin::phrase('Editing the video "[[title]]"', $video);
			$box['identifier']['value'] = $box['key']['id'];
			$values['details/url'] = $video['url'];
			$values['details/image'] = $video['image_id'];
			$values['details/title'] = $video['title'];
			$values['details/short_description'] = $video['short_description'];
			$values['details/description'] = $video['description'];
			$values['details/date'] = $video['date'];
			$values['details/language_id'] = $video['language_id'];
			
			$categories = [];
			$result = ze\row::query(ZENARIO_VIDEOS_MANAGER_PREFIX . 'category_video_link', ['category_id'], ['video_id' => $box['key']['id']]);
			while ($row = ze\sql::fetchAssoc($result)) {
				$categories[] = $row['category_id'];
			}
			$values['details/categories'] = implode(',', $categories);
			
			$box['last_updated'] = ze\admin::formatLastUpdated($video);
			
			if (ze::setting('vimeo_access_token')) {
				$parsed = parse_url($video['url']);
				if ($parsed) {
					$url = false;
					if (strpos($parsed['host'], 'vimeo.com') !== false) {
						$vimeoVideoId = (int)str_replace('/', '', $parsed['path']);
						$videoData = zenario_videos_manager::getVimeoVideoData($vimeoVideoId);
						$privacy = $videoData['privacy']['view'] ?? '';
						$vimeoPrivacySettingsFormattedNicely = zenario_videos_manager::getVimeoPrivacySettingsFormattedNicely();
					
						if ($privacy && array_key_exists($privacy, $vimeoPrivacySettingsFormattedNicely)) {
							$privacyString = $vimeoPrivacySettingsFormattedNicely[$privacy]['note'];
						} else {
							$privacyString = $this->phrase('Sorry, cannot fetch privacy setting');
						}
						$fields['details/video_privacy']['snippet']['html'] = $privacyString;
					}
				}
			}
		} else {
			$values['details/date'] = date('Y-m-d');
			
			if ($box['key']['from_video_upload']) {
				$box['title'] = ze\admin::phrase('Upload successful');
				$fields['details/url']['readonly'] = true;
			}
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//"Fetch thumbnail and title" feature: for Youtube only
		if (!empty($fields['details/fetch_youtube_settings']['pressed'])) {
			if ($values['details/url']) {
				$parsed = parse_url($values['details/url']);
				if ($parsed) {
					if (strpos($parsed['host'], 'youtube.com') !== false || strpos($parsed['host'], 'youtu.be') !== false) {
						$videoId = false;
						if (strpos($parsed['host'], 'youtube.com') !== false) {
							$videoId = substr($parsed['query'], 2);
						} elseif (strpos($parsed['host'], 'youtu.be') !== false) {
							$videoId = substr($parsed['path'], 1);
						}

						$thumbnailUrl = false;
						$apiUrl = 'https://www.youtube.com/oembed?format=json&url=http%3A//youtube.com/watch%3Fv%3D' . htmlspecialchars($videoId);
						$data = file_get_contents($apiUrl);
						if (!empty($data)) {
							$json = json_decode($data, true);
						}
						
						if ($videoId && !empty($json)) {
							if (!$values['details/title'] && !empty($json['title'])) {
								$values['details/title'] = $json['title'];
							}

							if (!empty($json['thumbnail_url'])) {
								//Check if there is a thumbnail available.
								//There may not be a max resolution thumbnail, so try smaller ones if needed.
								$thumbnailUrl = $json['thumbnail_url'];

								$sha = sha1($thumbnailUrl);
								if (!\ze\cache::cleanDirs() || !($dir = \ze\cache::createDir($sha, 'uploads', false))) {
									echo ze\admin::phrase('Zenario cannot currently receive uploaded files, because the private/ folder is not writeable.');
								} else {
									$filename = 'video_' . ze\escape::sql($videoId) . '_thumbnail.jpg';
									$safeFileName = ze\file::safeName($filename);

									$failed = false;
									if (!file_exists($path = CMS_ROOT. $dir. $safeFileName) || !filesize($path = CMS_ROOT. $dir. $safeFileName)) {
										touch($path);
										ze\cache::chmod($path, 0666);

										if ($in = fopen($thumbnailUrl, 'r')) {
											$out = fopen($path, 'w');
											while (!feof($in)) {
												fwrite($out, fread($in, 65536));
											}
											fclose($out);
											fclose($in);
											
											clearstatcache();
											$failed = !filesize($path);
										}
									}

									if (!$failed && ($mimeType = ze\file::mimeType($safeFileName)) && (ze\file::isImage($mimeType)) && ($image = @getimagesize($path))) {
										$file = [
											'filename' => $safeFileName,
											'width' => $image[0],
											'height' => $image[1],
										];
										$file['id'] = ze\ring::encodeIdForOrganizer($sha. '/'. $safeFileName. '/'. $file['width']. '/'. $file['height']);
										$file['link'] = 'zenario/file.php?getUploadedFileInCacheDir='. $file['id'];
										$file['label'] = $safeFileName . ' [' . $file['width'] . ' × ' . $file['height'] . ']';

										$fields['details/image']['values'][$file['id']] = $file;
										$values['details/image'] = $file['id'];
									}
								}
							}
						} else {
							$fields['details/fetch_youtube_settings']['error'] = ze\admin::phrase("Thumbnail not found. Please make sure the link is valid.");
						}
					}
				}
			} else {
				$fields['details/url']['error'] = ze\admin::phrase("Please enter a valid URL beginning with https://");
			}
		}

		unset($fields['details/fetch_youtube_settings']['pressed']);
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($values['details/url'] && !filter_var($values['details/url'], FILTER_VALIDATE_URL)) {
			$fields['details/url']['error'] = ze\admin::phrase("Please enter a valid URL beginning with https://");
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$imageId = $values['details/image'];
		if ($filepath = ze\file::getPathOfUploadInCacheDir($imageId)) {
			$imageId = ze\file::addToDatabase('zenario_video_image', $filepath, false, $mustBeAnImage = true);
		}
		
		if (!$values['details/date']) {
			$values['details/date'] = date('Y-m-d');
		}

		$url = mb_substr($values['details/url'], 0, 255, 'UTF-8');
		
		$videoDetails = [
			'url' => $url,
			'image_id' => (int)$imageId,
			'title' => mb_substr($values['details/title'], 0, 255, 'UTF-8'),
			'short_description' => mb_substr($values['details/short_description'], 0, 65535, 'UTF-8'),
			'description' => mb_substr($values['details/description'], 0, 65535, 'UTF-8'),
			'date' => $values['details/date']
		];
		
		$documentEnvelopesModuleIsRunning = ze\module::inc('zenario_document_envelopes_fea');
		if ($documentEnvelopesModuleIsRunning && $values['details/language_id']) {
			$videoDetails['language_id'] = $values['details/language_id'];
		} else {
			$videoDetails['language_id'] = false;
		}
		
		ze\admin::setLastUpdated($videoDetails, !$box['key']['id']);
		
		$box['key']['id'] = ze\row::set(ZENARIO_VIDEOS_MANAGER_PREFIX . 'videos', $videoDetails, $box['key']['id']);
		
		ze\row::delete(ZENARIO_VIDEOS_MANAGER_PREFIX . 'category_video_link', ['video_id' => $box['key']['id']]);
		if ($categories = $values['details/categories']) {
			foreach (ze\ray::explodeAndTrim($categories) as $categoryId) {
				ze\row::insert(ZENARIO_VIDEOS_MANAGER_PREFIX . 'category_video_link', ['video_id' => $box['key']['id'], 'category_id' => $categoryId]);
			}
		}
	}
}