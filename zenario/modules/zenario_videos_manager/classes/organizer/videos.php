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
			
			if (ze::setting('vimeo_access_token')) {
				$parsed = parse_url($item['url']);
				if ($parsed) {
					$url = false;
					if (isset($parsed['host'])) {
						if (strpos($parsed['host'], 'vimeo.com') !== false) {
							$vimeoVideoId = $parsed['path'];
							if (substr($vimeoVideoId, 0, 1) == '/') {
								$vimeoVideoId = substr($vimeoVideoId, 1);
							}

							//Remember the Zenario video ID for easier processing later.
							$vimeoVideos[$vimeoVideoId][] = $id;
						}
					}
				}
			}
		}

		if (ze::setting('vimeo_access_token')) {
			$videosCount = count($vimeoVideos);
			if ($videosCount > 0) {
				//There appears to be a limit of IDs that can be passed to Vimeo,
				//around 100. If the page size is set to 200, make 2 requests.
				if ($videosCount > 100) {
					$preserveKeys = true;
					$videoIdsArray = [array_slice($vimeoVideos, 0, 100, $preserveKeys), array_slice($vimeoVideos, 100, 100, $preserveKeys)];
				} else {
					$videoIdsArray = [$vimeoVideos];
				}

				$vimeoPrivacySettingsFormattedNicely = zenario_videos_manager::getVimeoPrivacySettingsFormattedNicely();

				foreach ($videoIdsArray as $videos) {
					$videoData = zenario_videos_manager::getVimeoVideoDataForMultiple(array_keys($videos));
					
					if (is_array($videoData) && !empty($videoData['data']) && count($videoData['data']) > 0) {
						foreach ($videoData['data'] as $video) {
							//Match the Vimeo ID to Zenario video ID...
							$videoId = str_replace('/videos/', '', $video['uri']);
							
							$itemIds = isset($vimeoVideos[$videoId]) ? $vimeoVideos[$videoId] : false;
							//Handle unlisted videos, which have an additional string after the URL.
							//Vimeo response only contains the first part.
							if (!$itemIds) {
								foreach ($vimeoVideos as $vimeoId => $zenarioIds) {
									if (stristr($vimeoId, $videoId) === false) {
										continue;
									} else {
										$itemIds = $zenarioIds;
										break;
									}
								}
							}

							$privacy = $video['privacy']['view'] ?? '';
						
							if ($privacy && array_key_exists($privacy, $vimeoPrivacySettingsFormattedNicely)) {
								$privacyString = $vimeoPrivacySettingsFormattedNicely[$privacy]['note'];
							} else {
								$privacyString = $this->phrase('Sorry, cannot show privacy setting');
							}

							//... and populate the value.
							foreach ($itemIds as $itemId) {
								$panel['items'][$itemId]['video_privacy'] = $privacyString;
							}
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
		}
	}
}