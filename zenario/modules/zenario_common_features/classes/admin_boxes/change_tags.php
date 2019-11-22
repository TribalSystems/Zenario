<?php
/*
 * Copyright (c) 2019, Tribal Limited
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


class zenario_common_features__admin_boxes__change_tags extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($ids = $box['key']['id']) {
			$idsArray = ze\ray::explodeAndTrim($ids);
			$idsCount = count($idsArray);
			$tagUsage = [];
			$sql = '
				SELECT tag_id, COUNT(image_id) AS count
				FROM ' . DB_PREFIX . 'image_tag_link
				WHERE image_id IN (' . ze\escape::sql($ids) . ')
				GROUP BY tag_id';
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
				$tagUsage[$row['tag_id']] = $row['count'];
			}
			$tags = [];
			$counter = 10;
			$sql = '
				SELECT id, name AS label, color AS tag_color
				FROM ' . DB_PREFIX . 'image_tags
				ORDER BY name';
			$result = ze\sql::select($sql);
			if (ze\sql::numRows($result) <= 0) {
				$fields['desc']['hidden'] = true;
				$link = 'zenario/admin/organizer.php?#zenario__content/panels/image_tags';
				$fields['no_tags_warning']['hidden'] = false;
				$fields['no_tags_warning']['snippet']['html'] = 
					'No tags have been created. To create or edit image tags click <a href="' . $link . '">this link</a>.';
				return false;
			}
			while ($row = ze\sql::fetchAssoc($result)) {
				++$counter;
				$tags[$row['id']] = $row;
				$label = '';
				$fieldValues = [];
				if (!empty($tagUsage[$row['id']])) {
					$label = ze\admin::nPhrase(
						' ([[count]] image)',
						' ([[count]] images)',
						(int)$tagUsage[$row['id']],
						['count' => $tagUsage[$row['id']]]);
					
					$fieldValues['remove_tag'] = 'Remove tag';
					if ($tagUsage[$row['id']] != $idsCount) {
						$fieldValues['add_tag'] = 'Add tag';
					}
				} else {
					$fieldValues['add_tag'] = 'Add tag';
				}
				$html = '
					<span class="organizer_image_tags">
						<label class="zenario_tag zenario_tag_' . $row['tag_color'] . '">' . $row['label'] . '</label>
					</span>';
				
				$box['tabs']['details']['fields']['tag_label_' . $row['id']] = [
					'full_width' => true,
					'ord' => $counter,
					'snippet' => 
						['html' => $html]];
				$box['tabs']['details']['fields']['tag_' . $row['id']] = [
					'same_row' => true,
					'ord' => $counter + 0.1,
					'type' => 'select',
					'values' => $fieldValues,
					'empty_value' => 'Do nothing'];
				$box['tabs']['details']['fields']['image_count_' . $row['id']] = [
					'same_row' => true,
					'full_width' => true,
					'ord' => $counter + 0.2,
					'snippet' => 
						['html' => $label]];
				
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_MANAGE_MEDIA');
		$ids = ze\ray::explodeAndTrim($box['key']['id']);
		$tags = [];
		$sql = '
			SELECT id, name AS label, color AS tag_color
			FROM ' . DB_PREFIX . 'image_tags';
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			if (isset($values['tag_' . $row['id']])) {
				self::changeImageTags($ids, $row['id'], $values['tag_' . $row['id']]);
			}
		}
	}
	
	private static function changeImageTags($ids, $tagId, $action = false) {
		if ($action) {
			foreach ($ids as $imageId) {
				if ($action == 'add_tag') {
					ze\row::set('image_tag_link', [], ['image_id' => $imageId, 'tag_id' => $tagId]);
				} elseif ($action == 'remove_tag') {
					ze\row::delete('image_tag_link', ['image_id' => $imageId, 'tag_id' => $tagId]);
				}
			}
		}
	}
}
