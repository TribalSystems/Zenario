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


class zenario_videos_fea__admin_boxes__plugin_settings extends zenario_videos_fea {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		ze\miscAdm::setupSlideDestinations($box, $fields, $values);

		$sql = '
			SELECT cat.id, cat.name, COUNT(cvl.video_id) AS count
			FROM ' . DB_PREFIX . ZENARIO_VIDEOS_MANAGER_PREFIX . 'categories cat
			LEFT JOIN ' . DB_PREFIX . ZENARIO_VIDEOS_MANAGER_PREFIX . 'category_video_link cvl
				ON cvl.category_id = cat.id
			GROUP BY cat.id
			ORDER BY cat.name';
		
		$categories = ze\sql::select($sql);

		$ord = 0;
		while ($row = ze\sql::fetchAssoc($categories)) {
			$category = ['label' => $row['name'] . ' (' . (int) ($row['count'] ?: 0) . ')', 'ord' => ++$ord];
			$fields['first_tab/category_filters']['values'][$row['id']] = $category;
		}
		
		$documentEnvelopesModuleIsRunning = ze\module::inc('zenario_document_envelopes_fea');
		if (!$documentEnvelopesModuleIsRunning) {
			unset($fields['first_tab/show_video_language']['visible_if']);
			$fields['first_tab/show_video_language']['hidden'] = true;
		}
		
		//Build a path to the Videos dataset custom field picker
		$dataset = ze\dataset::details('videos');
		$customDatasetFieldsPath = "zenario__administration/panels/custom_fields_hierarchy/refiners/dataset_text_fields_only//" . $dataset['id'] . "//";
		$fields['first_tab/show_dataset_fields']['pick_items']['path'] = $fields['first_tab/sort_by_custom_dataset_field']['pick_items']['path'] = $customDatasetFieldsPath;
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$this->setupOverridesForPhrases($box, $fields, $values);
        
        //Image options visibility
        $hidden = $values['global_area/mode'] != 'list_videos' || !$values['first_tab/show_images'];
		$this->showHideImageOptions($fields, $values, 'first_tab', $hidden, 'image_');
	}

	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($values['global_area/mode'] == 'list_videos' && (!$values['first_tab/show_images'] && !$values['first_tab/show_video_titles'])) {
			$fields['first_tab/show_images']['error'] =
			$fields['first_tab/show_video_titles']['error'] = ze\admin::phrase('Please select either "Show video titles", "Show images", or both.');
		}
	}
}