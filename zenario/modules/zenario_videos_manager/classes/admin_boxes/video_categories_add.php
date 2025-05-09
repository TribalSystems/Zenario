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


class zenario_videos_manager__admin_boxes__video_categories_add extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$videoIds = ze\ray::explodeAndTrim($box['key']['id']);
		$total = count($videoIds);
		
		if ($total <= 1) {
			exit;
		}
		
		if (!ze\priv::check('_PRIV_MANAGE_VIDEOS')) {
			$box['tabs']['categories_add']['edit_mode']['enabled'] = false;
			$box['tabs']['categories_add']['edit_mode']['on'] = false;
		}
		
		$box['title'] = ze\admin::phrase('Changing categories for [[count]] videos', ['count' => $total]);
		
		$videoCategories = [];
		
		$sql = "
			SELECT c.id, c.name, COUNT(cvl.video_id) AS video_count
			FROM " . DB_PREFIX . ZENARIO_VIDEOS_MANAGER_PREFIX . "categories AS c
			LEFT JOIN " . DB_PREFIX . ZENARIO_VIDEOS_MANAGER_PREFIX . "category_video_link AS cvl
				ON c.id = cvl.category_id
			GROUP BY c.id
			ORDER BY c.name";
		$result = ze\sql::select($sql);
		
		while ($row = ze\sql::fetchAssoc($result)) {
			//Show how many of the selected videos are in this category
			$sql2 = "
				SELECT COUNT(video_id)
				FROM " . DB_PREFIX . ZENARIO_VIDEOS_MANAGER_PREFIX . "category_video_link
				WHERE category_id = " . (int) $row['id'] . "
				AND video_id IN (" . ze\escape::in($videoIds) . ")";
			$result2 = ze\sql::select($sql2);
			$selectedVideosInThisCategory = ze\sql::fetchValue($result2);
			
			$videoCategories[$row['id']] = ['name' => $row['name'], 'video_count' => $row['video_count'], 'selected_videos_in_this_category' => (int) $selectedVideosInThisCategory];
		}
		
		$categoriesPanelHref = ze\link::absolute() . 'organizer.php#zenario_videos_manager/panels/categories';
		$linkStart = '<a href="' . htmlspecialchars($categoriesPanelHref) . '" target="_blank">';
		$linkEnd = "</a>";
		
		if (count($videoCategories) > 0) {
			$fields['categories_add/no_categories']['hidden'] = true;
			$box['tabs']['categories_add']['fields']['desc']['snippet']['html'] = 
				ze\admin::phrase('You can put videos into one or more categories. ([[Link_start]]Define categories[[Link_end]].)',
					['Link_start' => $linkStart, 'Link_end' => $linkEnd]);
			
			$ord = 0;
			foreach ($videoCategories as $catId => $cat) {
				if ($cat['video_count'] == 1) {
					$label = '[[category_name]] ([[video_count]] video)';
				} else {
					$label = '[[category_name]] ([[video_count]] videos)';
				}
				
				$replace = ['category_name' => $cat['name'], 'video_count' => $cat['video_count'], 'total' => $total, 'selected_videos_in_this_category' => $cat['selected_videos_in_this_category']];
				
				if ($cat['selected_videos_in_this_category'] > 0) {
					if ($cat['selected_videos_in_this_category'] == $total) {
						$label .= ' (all [[total]] selected videos are in this category)';
					} else {
						$label .= ' ([[selected_videos_in_this_category]] of [[total]] selected videos are in this category)';
						
					}
				}
				
				$fields['categories_add/categories_add']['values'][$catId] = ['ord' => $ord++, 'label' => ze\admin::phrase($label, $replace)];
			}
		} else {
			unset($box['tabs']['categories_add']['edit_mode']);
			$fields['categories_add/categories_add']['hidden'] = true;
			
			$fields['categories_add/no_categories']['snippet']['html'] = ze\admin::phrase(
				'No content item categories have been created. [[Link_start]]Create categories...[[Link_end]]',
				['Link_start' => $linkStart, 'Link_end' => $linkEnd]
			);
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//...
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_MANAGE_VIDEOS');
		
		$videoIds = ze\ray::explodeAndTrim($box['key']['id']);
		$total = count($videoIds);
		
		if ($total > 1 && $values['categories_add/categories_add']) {
			$categoriesArray = ze\ray::explodeAndTrim($values['categories_add/categories_add']);
			
			foreach ($videoIds as $videoId) {
				foreach ($categoriesArray as $categoryId) {
					if (!ze\row::exists(ZENARIO_VIDEOS_MANAGER_PREFIX . "category_video_link", ['video_id' => (int) $videoId, 'category_id' => (int) $categoryId])) {
						ze\row::insert(ZENARIO_VIDEOS_MANAGER_PREFIX . "category_video_link", ['video_id' => (int) $videoId, 'category_id' => (int) $categoryId]);
					}
				}
			}
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
}