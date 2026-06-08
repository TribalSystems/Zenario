<?php
/*
 * Copyright (c) 2026, Tribal Limited
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


class zenario_videos_manager__admin_boxes__video_categories extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (!ze\priv::check('_PRIV_MANAGE_VIDEOS')) {
			$box['tabs']['categories']['edit_mode']['enabled'] = false;
			$box['tabs']['categories']['edit_mode']['on'] = false;
		}
		
		$videoTitle = ze\row::get(ZENARIO_VIDEOS_MANAGER_PREFIX . 'videos', 'title', $box['key']['id']);
		$box['title'] = ze\admin::phrase('Changing categories for the video "[[title]]"', ['title' => $videoTitle]);
		
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
			$videoCategories[$row['id']] = ['name' => $row['name'], 'video_count' => $row['video_count']];
		}
		
		$categoriesPanelHref = ze\link::absolute() . 'organizer.php#zenario_videos_manager/panels/categories';
		$linkStart = '<a href="' . htmlspecialchars($categoriesPanelHref) . '" target="_blank">';
		$linkEnd = "</a>";
		
		if (count($videoCategories) > 0) {
			$fields['categories/no_categories']['hidden'] = true;
			$box['tabs']['categories']['fields']['desc']['snippet']['html'] = 
				ze\admin::phrase('You can put videos into one or more categories. ([[Link_start]]Define categories[[Link_end]].)',
					['Link_start' => $linkStart, 'Link_end' => $linkEnd]);
			
			$ord = 0;
			foreach ($videoCategories as $catId => $cat) {
				if ($cat['video_count'] == 1) {
					$label = '[[cat_label]] ([[video_count]] video)';
				} else {
					$label = '[[cat_label]] ([[video_count]] videos)';
				}
				$replace = ['cat_label' => $cat['name'], 'video_count' => $cat['video_count']];
				$fields['categories/categories']['values'][$catId] = ['ord' => $ord++, 'label' => ze\admin::phrase($label, $replace)];
			}
			
			$sql = "
				SELECT GROUP_CONCAT(category_id)
				FROM " . DB_PREFIX . ZENARIO_VIDEOS_MANAGER_PREFIX . "category_video_link
				WHERE video_id = " . (int) $box['key']['id'];
			$result = ze\sql::select($sql);
			$thisVideoCategories = ze\sql::fetchValue($result);
			
			$values['categories/categories'] = $thisVideoCategories;
		} else {
			unset($box['tabs']['categories']['edit_mode']);
			$fields['categories/categories']['hidden'] = true;
			
			$fields['categories/no_categories']['snippet']['html'] = ze\admin::phrase(
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
		
		$sql = "
			DELETE FROM " . DB_PREFIX . ZENARIO_VIDEOS_MANAGER_PREFIX . "category_video_link
			WHERE video_id = " . (int) $box['key']['id'];
		ze\sql::update($sql);
		
		if ($values['categories/categories']) {
			foreach (ze\ray::explodeAndTrim($values['categories/categories']) as $categoryId) {
				ze\row::insert(ZENARIO_VIDEOS_MANAGER_PREFIX . "category_video_link", ['video_id' => (int) $box['key']['id'], 'category_id' => (int) $categoryId]);
			}
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
}