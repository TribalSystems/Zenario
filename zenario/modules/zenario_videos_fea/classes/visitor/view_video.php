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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


class zenario_videos_fea__visitor__view_video extends zenario_videos_fea__visitor_base {
	
	protected $videoId = false;
	protected $video = false;
	protected $userCanManageVideo = false;
	
	public function init() {
		$this->registerGetRequest($this->idVarName);
		$this->videoId = ze::request($this->idVarName);
		$this->video = ze\row::get(ZENARIO_VIDEOS_MANAGER_PREFIX . 'videos', true, $this->videoId);
		if ($this->video) {
			$this->userCanManageVideo = ze\user::can('manage', 'video', $this->videoId);
		}
		
		return true;
	}
	
	public function showSlot() {
		$this->data['mode'] = $this->getMode();
		$this->data['module_loc'] = ze::moduleDir('zenario_videos_fea');
		if ($this->video) {
			$this->data['video'] = $this->video;
			$this->data['video']['date'] = ze\date::format($this->video['date']);
			
			$this->data['video']['last_updated'] = ze\user::formatLastUpdated($this->video);
			
			$editConductorLink = $this->conductorLink('edit_video', ['videoId' => $this->videoId]);
			$this->data['edit_video_button'] = $this->setting('enable.edit_video') && $this->userCanManageVideo && $editConductorLink;
			if ($this->data['edit_video_button']) {
				$this->data['edit_video_button_link'] = $editConductorLink;
			}
			
			$this->data['delete_video_button'] = $this->setting('enable.delete_video') && $this->userCanManageVideo;
			$this->data['delete_video_button_link'] = $this->conductorLink('delete_video', ['videoId' => $this->videoId]);
		
		
			//Check if this is youtube or vimeo
			$parsed = parse_url($this->video['url']);
			if ($parsed) {
				$url = false;
				$fromYoutube = false;
				if (strpos($parsed['host'], 'youtube.com') !== false || strpos($parsed['host'], 'youtu.be') !== false) {
					$params = ['url' => $this->video['url'], 'autoplay' => 1, 'format' => 'json'];
					$url = "https://www.youtube.com/oembed?" . http_build_query($params);
					$fromYoutube = true;
				} elseif (strpos($parsed['host'], 'vimeo.com') !== false) {
					//If a Vimeo video is unlisted, there is an additional string after the URL.
					//Work out the full URL.
					$vimeoVideoId = $parsed['path'];
					if (substr($vimeoVideoId, 0, 1) == '/') {
						$vimeoVideoId = substr($vimeoVideoId, 1);
					}
					
					//Get Vimeo data
					$videoData = zenario_videos_manager::getVimeoVideoData($vimeoVideoId);

					$params = ['url' => $videoData['link'], 'autoplay' => true];
					$url = "https://vimeo.com/api/oembed.json?" . http_build_query($params);
				}
			
				if ($url) {
					$result = ze\curl::fetch($url);
					if ($result && ($json = json_decode($result, true))) {
						if ($fromYoutube) {
							$json['html'] = str_replace('feature=oembed', 'feature=oembed&autoplay=1&rel=0', $json['html']);
						} else {
							//Privacy
							if ($this->setting('show_privacy_info')) {
								$this->data['show_privacy_info'] = true;

								$privacy = $videoData['privacy']['view'] ?? '';
								$vimeoPrivacySettingsFormattedNicely = zenario_videos_manager::getVimeoPrivacySettingsFormattedNicely();
								
								if ($privacy && array_key_exists($privacy, $vimeoPrivacySettingsFormattedNicely)) {
									$privacyString = $this->phrase($vimeoPrivacySettingsFormattedNicely[$privacy]['visitor_note']);
								} else {
									$privacyString = $this->phrase('Sorry, cannot show privacy setting');
								}
								$this->data['video']['privacy'] = $privacyString;
								
								if ($privacy == 'unlisted' || $privacy == 'anybody') {
									$this->data['video']['url'] = $this->video['url'];
									$this->data['video']['shareable_link'] = true;
								}
							}

							//Thumbnail
							if (!$this->video['image_id'] && $this->userCanManageVideo) {
								$this->data['videoHasNoThumbnail'] = true;
							}
						}
						
						$this->data['video']['embed'] = $json['html'];
					}
				}
			}
			
			$this->data['Video_id'] = $this->videoId;
			$this->data['Ajax_link'] = $this->pluginAJAXLink();
			
			if ((bool)ze\admin::id()) {
				$this->data['Logged_in_user_is_admin'] = true;
				$this->data['Video_organizer_href_start'] = htmlspecialchars(ze\link::absolute() . 'organizer.php#zenario_videos_manager/panels/videos//');
				$this->data['Video_organizer_href_middle'] = htmlspecialchars('~.zenario_videos_manager__video~tdetails~k{"id":"');
				$this->data['Video_organizer_href_end'] = htmlspecialchars('"}');
			}
			
			if ($this->setting('show_video_title')) {
				$this->data['show_video_title'] = true;
				$this->data['video_title_tags'] = $this->setting('video_title_tags');
			}
			
			if (($documentEnvelopesModuleIsRunning = ze\module::inc('zenario_document_envelopes_fea')) && $this->setting('show_video_language')) {
				$this->data['video']['language'] = zenario_document_envelopes_fea::getEnvelopeLanguages(ze\dataset::LIST_MODE_VALUE, $this->video['language_id']);
			}
		} else {
			if (!$this->videoId) {
				$this->data['no_video_id'] = true;
			} else {
				$this->data['not_found'] = true;
			}
		}
				
		$this->twigFramework($this->data);
	}
	
	public function handlePluginAJAX() {
		if ($this->userCanManageVideo) {
			zenario_videos_manager::deleteVideo($this->videoId);
		}
	}
}