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

class zenario_videos_manager__organizer__videos extends zenario_videos_manager {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($categoryId = ze::get('refiner__category')) {
			$categoryName = ze\row::get(ZENARIO_VIDEOS_MANAGER_PREFIX . 'categories', 'name', ['id' => $categoryId]);
			$panel['title'] = ze\admin::phrase('Videos in the category "[[category_name]]"', ['category_name' => $categoryName]);
			$panel['no_items_message'] = ze\admin::phrase('There are no videos in the category "[[category_name]]".', ['category_name' => $categoryName]);
			$panel['collection_buttons']['add_embed_code']['hidden'] = true;
		}
		
		$documentEnvelopesModuleIsRunning = ze\module::inc('zenario_document_envelopes_fea');
		$languages = ze\dataset::centralisedListValues('zenario_document_envelopes_fea::getEnvelopeLanguages');
		
		//If there are Vimeo videos, Zenario will load their privacy settings.
		//This will be done using 1 query with multiple IDs.
		$vimeoVideos = [];
		
		$vimeoAccessToken = ze::setting('vimeo_access_token');
		$vimeoPrivacySettingsFormattedNicely = zenario_videos_manager::getVimeoPrivacySettingsFormattedNicely();

		foreach ($panel['items'] as $id => &$item) {
			if (!empty($item['thumbnail_id'])) {
				$item['traits']['has_image'] = true;
			
				$img = '&usage=zenario_video_image&c='. $item['thumbnail_checksum'];

				$item['image'] = 'zenario/file.php?og=1'. $img;
			}
			
			if ($documentEnvelopesModuleIsRunning) {
				if ($item['language']) {
					$item['language'] = zenario_document_envelopes_fea::getEnvelopeLanguages(ze\dataset::LIST_MODE_VALUE, $item['language']);
				} else {
					$item['language'] = '';
				}
			}
			
			if ($vimeoAccessToken) {
				$parsed = parse_url($item['url']);
				if ($parsed) {
					$url = false;
					if (isset($parsed['host'])) {
						if (strpos($parsed['host'], 'vimeo.com') !== false) {
							$vimeoVideoId = $parsed['path'];
							if (substr($vimeoVideoId, 0, 1) == '/') {
								$vimeoVideoId = substr($vimeoVideoId, 1);
								
								if ($item['vimeo_privacy_setting'] && array_key_exists($item['vimeo_privacy_setting'], $vimeoPrivacySettingsFormattedNicely)) {
									$privacyString = $vimeoPrivacySettingsFormattedNicely[$item['vimeo_privacy_setting']]['note'];
									ze\lang::applyMergeFields($privacyString, ['code' => $item['vimeo_privacy_setting'], 'date_time' => ze\date::formatRelativeDateTime(strtotime($item['vimeo_privacy_last_cached']))]);
								} else {
									$privacyString = $this->phrase('Sorry, cannot show privacy setting');
								}

								//... and populate the value.
								$item['video_privacy'] = $privacyString;
							}

							//Remember the Zenario video ID for easier processing later.
							$vimeoVideos[$vimeoVideoId][] = $id;
						}
					}
				}
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if (isset($_POST['delete_video']) && $ids) {
			foreach (explode(',', $ids) as $videoId) {
				static::deleteVideo($videoId);
			}
		} elseif (isset($_POST['update_vimeo_privacy'])) {
			//Every video stored on Zenario has a URL which contains a Vimeo ID.
			$zenarioVideosAndUrls = [];
			$vimeoVideos = [];
			
			//This function will send about 30 videos at a time in 1 request with multiple IDs.
			$videoCount = 0;
			$requestNumber = 1;
			$videosCount = 0;
			$dateNow = ze\date::now();
			
			$sql = "
				SELECT id, url
				FROM " . DB_PREFIX . ZENARIO_VIDEOS_MANAGER_PREFIX . "videos
				WHERE url LIKE '%vimeo.com%'";
			$result = ze\sql::select($sql);
			
			while ($row = ze\sql::fetchAssoc($result)) {
				$videoCount++;
				$parsed = parse_url($row['url']);
				if ($parsed) {
					$url = false;
					if (isset($parsed['host'])) {
						if (strpos($parsed['host'], 'vimeo.com') !== false) {
							
							//Every video should have a unique URL, but the site may not have many videos,
							//or there may be videos that had been created before the validation logic for URL uniqueness was implemented.
							//If that is the case, it is possible that the videos count is over 30,
							//but there are fewer unique URLs that will be sent to Vimeo for processing.
							$videosCount++;
							if ($videosCount > 30) {
								$requestNumber++;
								$videosCount = 1;
							}
							
							$vimeoVideoId = $parsed['path'];
							if (substr($vimeoVideoId, 0, 1) == '/') {
								$vimeoVideoId = substr($vimeoVideoId, 1);
							}
							
							if (($forwardSlashPos = strpos($vimeoVideoId, '/')) !== false) {
								$vimeoVideoId = substr($vimeoVideoId, 0, $forwardSlashPos);
							}
							
							//Remember which Zenario IDs are associated with each Vimeo ID.
							//If a Vimeo URL is used in multiple videos, because had been created
							//before the validation logic was implemented, the entry will store multiple Zenario IDs.
							$zenarioVideosAndUrls[$vimeoVideoId][] = $row['id'];
							$vimeoVideos[$requestNumber][$vimeoVideoId] = true;
						}
					}
				}
			}
			
			if (!empty($vimeoVideos)) {
				foreach ($vimeoVideos as $requestNumber => $videosRequest) {
					$videoData = zenario_videos_manager::getVimeoVideoDataForMultiple(array_keys($videosRequest));
					
					if (is_array($videoData) && !empty($videoData['data']) && count($videoData['data']) > 0) {
						foreach ($videoData['data'] as $video) {
							// //Match the Vimeo ID to Zenario video IDs.
							$videoId = str_replace('/videos/', '', $video['uri']);
							
							$zenarioIds = isset($zenarioVideosAndUrls[$videoId]) ? $zenarioVideosAndUrls[$videoId] : false;
							
							$privacy = $video['privacy']['view'] ?? '';
						
							$sql = "
								UPDATE " . DB_PREFIX . ZENARIO_VIDEOS_MANAGER_PREFIX . "videos
								SET
									vimeo_privacy_setting = '" . ze\escape::sql($privacy) . "',
									vimeo_privacy_last_cached = '" . ze\escape::sql($dateNow) . "'
								WHERE id IN (" . ze\escape::in($zenarioIds) . ")";
							ze\sql::update($sql);
						}
					}
				}
			}
		}
	}
}