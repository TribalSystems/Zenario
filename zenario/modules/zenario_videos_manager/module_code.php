<?php
if (!defined('NOT_ACCESSED_DIRECTLY')) exit;
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


class zenario_videos_manager extends ze\moduleBaseClass {
	
	public static $categories = [
		"c1" => "Category 1",
		"c2" => "Category 2",
		"c3" => "Category 3"
	];
	
	public static function deleteVideo($videoId) {
		$imageId = ze\row::get(ZENARIO_VIDEOS_MANAGER_PREFIX . 'videos', 'image_id', $videoId);
		
		//Check if other videos use the same image.
		//Delete it if not.
		$result = ze\row::query(ZENARIO_VIDEOS_MANAGER_PREFIX . 'videos', 'id', ['image_id' => $imageId]);
		if (ze\sql::numRows($result) == 1 && ($id = ze\sql::fetchValue($result)) && $id == $videoId) {
			ze\row::delete('files', ['id' => $imageId]);
		}
		
		ze\row::delete(ZENARIO_VIDEOS_MANAGER_PREFIX . 'videos', $videoId);
		ze\row::delete(ZENARIO_VIDEOS_MANAGER_PREFIX . 'videos_custom_data', $videoId);
		ze\row::delete(ZENARIO_VIDEOS_MANAGER_PREFIX . 'video_categories', ['video_id' => $videoId]);
	}
	
	public static function deleteCategory($categoryId) {
		ze\row::delete(ZENARIO_VIDEOS_MANAGER_PREFIX . 'categories', $categoryId);
		ze\row::delete(ZENARIO_VIDEOS_MANAGER_PREFIX . 'video_categories', ['category_id' => $categoryId]);
	}
	
	public static function getVimeoVideoData($vimeoVideoId) {
		$vimeoAccessToken = ze::setting('vimeo_access_token');
		$link = "https://api.vimeo.com/videos/" . (int)$vimeoVideoId;
		$params = [
			"Content-Type: application/json",
			"Authorization: Bearer " . $vimeoAccessToken
		];
		$options = [
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => $params,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_SSL_VERIFYHOST => 0,
		];
		$result = ze\curl::fetch($link, false, $options);

		$result = json_decode($result, true);
		return $result;
	}
	
	public static function getVimeoVideoThumbnail($vimeoVideoUrl) {
		$vimeoAccessToken = ze::setting('vimeo_access_token');
		$link = "https://vimeo.com/api/oembed.json?url=" . urlencode($vimeoVideoUrl);
		$params = [
			"Content-Type: application/json",
			"Authorization: Bearer " . $vimeoAccessToken
		];
		$options = [
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => $params,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_SSL_VERIFYHOST => 0,
		];
		$result = ze\curl::fetch($link, false, $options);

		$result = json_decode($result, true);
		return $result['thumbnail_url'];
	}
	
	public static function getVimeoPrivacySettingsFormattedNicely() {
		$vimeoPrivacySettingsFormattedNicely = [
			'anybody' => [
				'label' => "Anybody",
				'note' => "Anyone can view the video",
				'visitor_note' => "Anyone can view this video"
			],
			'disable' => [
				'label' => "Disable",
				'note' => "Hide video from <a href='vimeo.com' target='_blank'>vimeo.com</a>, but the video can still be embedded on external sites. Non-shareable.",
				'visitor_note' => "You may not share the link to this video"
			],
			'nobody' => [
				'label' => "Nobody",
				'note' => "Nobody but the owner can view the video",
				'visitor_note' => "Nobody but the owner can view this video"
			],
			'unlisted' => [
				'label' => "Unlisted",
				'note' => "People with private link can view video, and it can be embedded and viewed on external sites. Shareable. (Requires Vimeo Plus/Pro/Business/Premium account.)",
				'visitor_note' => "You may share this video with others"
			]
		];
		
		return $vimeoPrivacySettingsFormattedNicely;
	}
}