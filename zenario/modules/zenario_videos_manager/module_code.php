<?php
if (!defined('NOT_ACCESSED_DIRECTLY')) exit;
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
		ze\row::delete(ZENARIO_VIDEOS_MANAGER_PREFIX . 'category_video_link', ['video_id' => $videoId]);
	}
	
	public static function deleteCategory($categoryId) {
		ze\row::delete(ZENARIO_VIDEOS_MANAGER_PREFIX . 'categories', $categoryId);
		ze\row::delete(ZENARIO_VIDEOS_MANAGER_PREFIX . 'category_video_link', ['category_id' => $categoryId]);
	}
	
	public static function getVimeoVideoData($vimeoVideoId) {
		$vimeoAccessToken = ze::setting('vimeo_access_token');
		$link = "https://api.vimeo.com/videos/" . (int) $vimeoVideoId;
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

	public static function getVimeoVideoDataForMultiple($vimeoVideoIds) {
		$vimeoAccessToken = ze::setting('vimeo_access_token');

		if (is_array($vimeoVideoIds) && count($vimeoVideoIds) > 0) {
			//Example of the format:
			//https://api.vimeo.com/videos?uris=/videos/111,/videos/222,/videos/333
			$link = 'https://api.vimeo.com/videos?uris=/videos/' . implode(',/videos/', $vimeoVideoIds);
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

		return [];
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
	
	public static function getVimeoPrivacySettingsFormattedNicely($creatingNewVideo = false) {
		$vimeoPrivacySettingsFormattedNicely = [
			'anybody' => [
				'label' => "Public/anybody",
				'note' => "Vimeo 'Public', code 'anybody'. Anyone can view the video.",
				'visitor_note' => "Anyone can view this video"
			],
			'disable' => [
				'label' => "Hide from Vimeo/disable",
				'note' => "Vimeo 'Hide from Vimeo' with code 'disable'. Hide video from Vimeo.com, but the video can still be embedded on external sites. Effectively non-shareable.",
				'visitor_note' => "You may not share the link to this video"
			],
			'nobody' => [
				'label' => "Only me/nobody",
				'note' => "Vimeo 'Only me' with code 'nobody'. Nobody but the owner can view the video.",
				'visitor_note' => "Nobody but the owner can view this video"
			],
			'unlisted' => [
				'label' => "Private/unlisted",
				'note' => "Vimeo 'Private' with code 'unlisted'. People with private link can view video, and it can be embedded and viewed on external sites. Effectively shareable.",
				'visitor_note' => "You may share this video with others"
			]
		];
		
		if (!$creatingNewVideo) {
			foreach (['anybody', 'disable', 'nobody', 'unlisted'] as $privacy) {
				$vimeoPrivacySettingsFormattedNicely[$privacy]['note'] .= " (Vimeo privacy: \"[[code]]\". Last cached: [[date_time]]).";
			}
		}
		
		return $vimeoPrivacySettingsFormattedNicely;
	}
	
	public static function signalAdvancedSearchPopulateValuesSearchInOtherModules() {
		return 'zenario_videos_manager';
	}
	
	public static function searchFromModule($searchString, $weightings, $usePagination = false, $page = 0, $pageSize = 999999) {
		$recordCount = 0;
		$resultsFromModule = [];

		if ($searchString) {
			//Calculate the search terms
			$searchTerms = ze\content::searchtermParts($searchString);

			//Get a list of MySQL stop-words to exclude.
			$stopWordsSql = "
				SELECT value FROM INFORMATION_SCHEMA.INNODB_FT_DEFAULT_STOPWORD";
			$stopWords = ze\sql::fetchValues($stopWordsSql);

			//Remove the stop words from search.
			$searchTermsWithoutStopWords = $searchTerms;
			foreach ($searchTerms as $searchTerm => $searchTermType) {
				if (in_array($searchTerm, $stopWords)) {
					unset($searchTermsWithoutStopWords[$searchTerm]);
				}
			}

			$searchTermsAreAllStopWords = true;
			if (!empty($searchTermsWithoutStopWords) && count($searchTermsWithoutStopWords) > 0) {
				$searchTermsAreAllStopWords = false;
			}
			unset($searchTermsWithoutStopWords);

			if ($searchTerms && !$searchTermsAreAllStopWords && count($searchTerms) > 0) {
				$firstRow = true;

				$sqlFields = "
					SELECT v.id AS item_id, v.title, v.image_id, f.filename, v.short_description, v.date";
				
				$sqlFrom = "
					FROM " . DB_PREFIX . ZENARIO_VIDEOS_MANAGER_PREFIX . "videos v";
				
				$sqlJoin = "
					LEFT JOIN " . DB_PREFIX . "files f
						ON f.id = v.image_id";
				
				$sqlWhere = "
					WHERE (";
				
				$sqlMatch = '';
				$sqlCount = '';

				$sqlFields .= ", (";

				$scoreStatementFirstLine = true;
				foreach ($searchTerms as $searchTerm => $searchTermType) {
					$wildcard = "*";

					//The location name column is called description.
					//Treat it as a title.
					foreach (['v.title', 'v.short_description', 'v.description'] as $column) {
						if ($firstRow) {
							$or = '';
							$firstRow = false;
						} else {
							$or = " OR";
						}

						if (!$scoreStatementFirstLine) {
							$sqlFields .= " + ";
						}
	
						$scoreStatementFirstLine = false;

						if ($column == 'v.title') {
							$weighting = $weightings['title'];
						} elseif (ze::in($column, 'v.short_description', 'v.description')) {
							$weighting = $weightings['description'];
						}
						
						$sqlFields .= "(MATCH (". $column. ") AGAINST (\"" . ze\escape::sql($searchTerm) . $wildcard . "\" IN BOOLEAN MODE))";
						
						$sqlMatch .= $or . "
							(MATCH (". $column. ") AGAINST (\"" . ze\escape::sql($searchTerm) . $wildcard . "\" IN BOOLEAN MODE) * " . $weighting . ")";
						
						$sqlCount .= $or . "
							(MATCH (". $column. ") AGAINST (\"" . ze\escape::sql($searchTerm) . $wildcard . "\" IN BOOLEAN MODE))";
					}
				}

				$sqlFields .= "
					) AS score";
				
				$sqlMatch .= ")";
				$sqlCount .= ")";

				//Get the record count now...
				$result = ze\sql::select("SELECT DISTINCT COUNT(*) " . $sqlFrom . $sqlWhere . $sqlCount);
				$row = ze\sql::fetchRow($result);
				$recordCount = $row[0];

				//... and then the results.
				$sqlMatch .= "
					ORDER BY score DESC, v.title ASC";

				$sqlMatch .= ze\sql::limit($page, $pageSize);

				$result = ze\sql::select($sqlFields . $sqlFrom . $sqlJoin . $sqlWhere . $sqlMatch);

				while ($row = ze\sql::fetchAssoc($result)) {
					$item = [
						'item_id' => $row['item_id'],
						'title' => $row['title'],
						'short_description' => $row['short_description'],
						'date' => ze\date::format($row['date']),
						'filename' => $row['filename'],
						'score' => $row['score']
					];

					if ($row['image_id']) {
						$item['thumbnail_Id'] = $row['image_id'];
					} else {
						$item['thumbnail_Id'] = '';
					}
					$resultsFromModule[$row['item_id']] = $item;
				}
			}
		}

		return ['Record_Count' => $recordCount, 'Results' => $resultsFromModule, 'Variable_name' => 'videoId'];
	}
}