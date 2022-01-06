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


class zenario_videos_fea__visitor__edit_video extends zenario_videos_fea__visitor_base {
	
	protected $data = [];
	protected $postIsMine = false;
	protected $mode = false;
	
	protected $video = false;
	protected $videoId = false;
	protected $goingBack = false;
	
	protected $noLanguageErrors = true;
	protected $noTitleErrors = true;
	protected $noURLErrors = true;
	protected $documentEnvelopesModuleIsRunning;
	protected $vimeoPrivacySettingsEnabled;
	
	public function init() {
		$this->mode = $this->getMode();
		
		if (!ze\user::can('manage', 'video')) {
			return ZENARIO_403_NO_PERMISSION;;
		}
		
		$this->documentEnvelopesModuleIsRunning = ze\module::inc('zenario_document_envelopes_fea');
		$this->vimeoPrivacySettingsEnabled = ze::setting('enable_vimeo_privacy_settings');
		$this->videoId = ze::request($this->idVarName);
		$this->videoCategories = ze\row::getAssocs(ZENARIO_VIDEOS_MANAGER_PREFIX . 'categories', 'name');
		
		$this->data['show_success_message'] = false;
		$this->postIsMine = false;
		if ($this->postIsMine = $this->checkPostIsMine() && ze::post('submit')) {
			
			$this->noTitleErrors = true;
			$title = ze::post('title');
			if (!$title) {
				$this->noTitleErrors = false;
			}
			
			$this->noURLErrors = true;
			$url = ze::post('url');
			if (!$url) {
				$this->noURLErrors = false;
			}
			
			$this->noLanguageErrors = true;
			$languageId = null;
			if ($this->documentEnvelopesModuleIsRunning) {
				$languageId = ze::post('language_id_value');
				if (ze::setting('video_language_is_mandatory') && !$languageId) {
					$this->noLanguageErrors = false;
				}
			}
			
			if ($this->noTitleErrors && $this->noURLErrors && $this->noLanguageErrors) {
				$details = [
					'url' => mb_substr($url, 0, 255, 'UTF-8'),
					'title' => mb_substr($title, 0, 255, 'UTF-8'),
					'short_description' => mb_substr(ze::post('short_description'), 0, 65535, 'UTF-8'),
				];
				
				if ($languageId) {
					$details['language_id'] = $languageId;
				}

				if ($this->mode == 'edit_video') {
					$parsed = parse_url($url);
					if ($parsed && strpos($parsed['host'], 'vimeo.com') !== false) {
						$vimeoVideoId = (int)str_replace('/', '', $parsed['path']);
					
						$currentVimeoThumnailId = ze\row::get(ZENARIO_VIDEOS_MANAGER_PREFIX . 'videos', 'image_id', $this->videoId);
						if (!$currentVimeoThumnailId) {
							$videoData = zenario_videos_manager::getVimeoVideoData($vimeoVideoId);
							$status = $videoData['status'];
						
							if ($status == 'available') {
								$vimeoThumbnail = zenario_videos_manager::getVimeoVideoThumbnail($videoData['link']);
								$vimeoThumbnailData = file_get_contents($vimeoThumbnail);
						
								$newVimeoThumbnailImageId = ze\file::addFromString('zenario_video_image', $vimeoThumbnailData, 'video_' . $vimeoVideoId . '_thumbnail.jpg');
								$details['image_id'] = $newVimeoThumbnailImageId;
							}
						}
					}
				}

				$date = ze::post('date');
				if (!$date) {
					$date = date('Y-m-d');
				}
				$details['date'] = $date;

				ze\user::setLastUpdated($details, !$this->videoId);
				$videoId = ze\row::set(ZENARIO_VIDEOS_MANAGER_PREFIX . 'videos', $details, $this->videoId ?: false);
			
				//Categories
				if ($this->mode == 'edit_video') {
					ze\row::delete(ZENARIO_VIDEOS_MANAGER_PREFIX . 'video_categories', ['video_id' => $videoId]);
				}
			
				if ($this->videoCategories) {
					if ($currentVideoCategories = ze::post('current_video_category')) {
						foreach ($currentVideoCategories as $categoryId) {
							ze\row::insert(ZENARIO_VIDEOS_MANAGER_PREFIX . 'video_categories', ['video_id' => $videoId, 'category_id' => $categoryId]);
						}
					}
				}
				
				$this->data['uploaded_or_added_by_url'] = ze::post('uploaded_or_added_by_url');
				$this->data['show_success_message'] = true;
				
				//Clear POST variables after processing
				unset($_POST['title']);
				unset($_POST['url']);
				unset($_POST['short_description']);
				unset($_POST['language_id']);
				unset($_POST['date']);
			}
			
			if ($this->mode == 'edit_video' && $this->data['show_success_message']) {
				$this->callScript('zenario_conductor', 'go', $this->slotName, 'back');
				$this->goingBack = true;
				return true;
			}
		}
		
		if ($this->videoId) {
			$this->video = ze\row::get(ZENARIO_VIDEOS_MANAGER_PREFIX . 'videos', true, $this->videoId);
		}
		
		return true;
	}
	
	public function showSlot() {
		if ($this->goingBack) {
			return;
		}
		
		$this->data['mode'] = $this->mode;
		$extraAttributes = 'id="add_video" enctype="multipart/form-data"';
		if ($this->mode == 'new_video') {
			$extraAttributes .= ' style="display: none;"';
		}
		$this->data['openForm'] = $this->openForm('', $extraAttributes);
		$this->data['closeForm'] = $this->closeForm();
		
		$this->data['http_host'] = $_SERVER['HTTP_HOST'];
		
		if (!$this->noURLErrors) {
			$this->data['no_url_error'] = true;
		}
		
		if (!$this->noTitleErrors) {
			$this->data['no_title_error'] = true;
		}
		
		//Load languages - only when zenario_document_envelopes_fea module is running
		//(soft depengency)
		if ($this->documentEnvelopesModuleIsRunning) {
			$this->data['languages_are_available'] = $this->documentEnvelopesModuleIsRunning;
			$this->data['language_id_values'] = ze\dataset::centralisedListValues('zenario_document_envelopes_fea::getEnvelopeLanguages');
			
			//Show empty value on the list of languages
			array_unshift($this->data['language_id_values'], ['label' => '-- Select --', 'ord' => 0]);
			
			//Make language mandatory if required
			if (ze::setting('video_language_is_mandatory')) {
				$this->data['language_is_mandatory'] = true;
				
				if (!$this->noLanguageErrors) {
					$this->data['no_language_selected'] = true;
				}
			}
		}
		
		$this->data['video_categories'] = $this->videoCategories;
		
		if (!$this->videoId) {
			$this->data['title'] = $this->phrase('Adding new video');
		}
		
		if ($this->postIsMine) {
			$this->data['url'] = mb_substr(ze::post('url'), 0, 255, 'UTF-8');
			$this->data['video_title'] = mb_substr(ze::post('title'), 0, 255, 'UTF-8');
			$this->data['short_description'] = mb_substr(ze::post('short_description'), 0, 65535, 'UTF-8');
			$this->data['date'] = ze::post('date') ?: date('Y-m-d');
			$this->data['language_id'] = ze::post('language_id_value') ?: "";
			$this->data['current_video_categories'] = ze::post('current_video_category') ?: "";
		} elseif ($this->video) {
			$this->data['url'] = $this->video['url'];
			$this->data['video_title'] = $this->video['title'];
			$this->data['short_description'] = $this->video['short_description'];
			$this->data['date'] = $this->video['date'];
			$this->data['language_id'] = $this->video['language_id'];
			$this->data['current_video_categories'] = ze\row::getValues(ZENARIO_VIDEOS_MANAGER_PREFIX . 'video_categories', 'category_id', ['video_id' => $this->videoId]);
		} else {
			$this->data['date'] = ze\date::ymd();
		}
		
		if ($this->video) {
			$this->data['title'] = $this->phrase('Editing the video "[[video_title]]"', ['video_title' => $this->video['title']]);
			$this->data['last_updated'] = ze\user::formatLastUpdated($this->video);
			$this->data['videoId'] = $this->videoId;
			
			
			$parsed = parse_url($this->video['url']);
			if ($parsed) {
				$url = false;
				if (strpos($parsed['host'], 'vimeo.com') !== false) {
					$vimeoVideoId = (int)str_replace('/', '', $parsed['path']);
					$videoData = zenario_videos_manager::getVimeoVideoData($vimeoVideoId);
					$privacy = $videoData['privacy']['view'] ?? '';
					$vimeoPrivacySettingsFormattedNicely = zenario_videos_manager::getVimeoPrivacySettingsFormattedNicely();
					
					//Video thumbnail
					$currentVimeoThumnailId = $this->video['image_id'];
					if ($currentVimeoThumnailId) {
						$width = $height = $imageUrl = false;
						ze\file::imageLink($width, $height, $imageUrl, $currentVimeoThumnailId);
						$this->data['videoThumbnailHref'] = $imageUrl;
					} else {
						$status = $videoData['status'];
						
						if ($status == 'available') {
							$vimeoThumbnail = zenario_videos_manager::getVimeoVideoThumbnail($videoData['link']);
							$this->data['videoThumbnailHref'] = $vimeoThumbnail;
							$this->data['thumbnailNotSavedYet'] = true;
						}
					}
					
					if ($privacy && array_key_exists($privacy, $vimeoPrivacySettingsFormattedNicely)) {
						$privacyString = $vimeoPrivacySettingsFormattedNicely[$privacy]['visitor_note'];
					} else {
						$privacyString = $this->phrase('Sorry, cannot show privacy setting');
					}
					$this->data['video_privacy'] = $privacyString;
				}
			}
		}
		
		//Privacy settings
		if ($this->vimeoPrivacySettingsEnabled) {
			$vimeoPrivacySettingsFormattedNicely = zenario_videos_manager::getVimeoPrivacySettingsFormattedNicely();
	
			$vimeoPrivacySettings = ze::setting('vimeo_privacy_settings');
			$vimeoPrivacySettings = explode(',', $vimeoPrivacySettings);
			$numberOfOptions = count($vimeoPrivacySettings);
			foreach ($vimeoPrivacySettings as $vimeoPrivacySetting) {
				$this->data['vimeo_privacy_settings'][$vimeoPrivacySetting] = $vimeoPrivacySettingsFormattedNicely[$vimeoPrivacySetting];
			}
			$this->data['number_of_vimeo_privacy_settings'] = $numberOfOptions;
		}
		
		$this->data['module_loc'] = ze::moduleDir('zenario_videos_fea');
		
		$this->twigFramework($this->data);
	}
}