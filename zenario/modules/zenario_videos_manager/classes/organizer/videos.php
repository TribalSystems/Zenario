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

class zenario_videos_manager__organizer__videos extends zenario_videos_manager {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$documentEnvelopesModuleIsRunning = ze\module::inc('zenario_document_envelopes_fea');
		$languages = ze\dataset::centralisedListValues('zenario_document_envelopes_fea::getEnvelopeLanguages');
		
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
					if (strpos($parsed['host'], 'vimeo.com') !== false) {
						$vimeoVideoId = (int)str_replace('/', '', $parsed['path']);
						$videoData = zenario_videos_manager::getVimeoVideoData($vimeoVideoId);
						$privacy = $videoData['privacy']['view'] ?? '';
						$vimeoPrivacySettingsFormattedNicely = zenario_videos_manager::getVimeoPrivacySettingsFormattedNicely();
					
						if ($privacy && array_key_exists($privacy, $vimeoPrivacySettingsFormattedNicely)) {
							$privacyString = $vimeoPrivacySettingsFormattedNicely[$privacy]['note'];
						} else {
							$privacyString = $this->phrase('Sorry, cannot show privacy setting');
						}
						$item['video_privacy'] = $privacyString;
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