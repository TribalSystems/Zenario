<?php
/*
 * Copyright (c) 2018, Tribal Limited
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


class zenario_common_features__admin_boxes__change_tags extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($ids = $box['key']['id']) {
			$idsArray = explodeAndTrim($ids);
			$idsCount = count($idsArray);
			$tagUsage = array();
			$sql = '
				SELECT tag_id, COUNT(image_id) AS count
				FROM ' . DB_NAME_PREFIX . 'image_tag_link
				WHERE image_id IN (' . sqlEscape($ids) . ')
				GROUP BY tag_id';
			$result = sqlSelect($sql);
			while ($row = sqlFetchAssoc($result)) {
				$tagUsage[$row['tag_id']] = $row['count'];
			}
			$tags = array();
			$counter = 10;
			$sql = '
				SELECT id, name AS label, color AS tag_color
				FROM ' . DB_NAME_PREFIX . 'image_tags
				ORDER BY name';
			$result = sqlSelect($sql);
			if (sqlNumRows($result) <= 0) {
				$fields['desc']['hidden'] = true;
				$link = 'zenario/admin/organizer.php?#zenario__content/panels/image_tags';
				$fields['no_tags_warning']['hidden'] = false;
				$fields['no_tags_warning']['snippet']['html'] = 
					'No tags have been created. To create or edit image tags click <a href="' . $link . '">this link</a>.';
				return false;
			}
			while ($row = sqlFetchAssoc($result)) {
				++$counter;
				$tags[$row['id']] = $row;
				$label = '';
				$fieldValues = array();
				if (!empty($tagUsage[$row['id']])) {
					$label = nAdminPhrase(
						' ([[count]] image)',
						' ([[count]] images)',
						(int)$tagUsage[$row['id']],
						array('count' => $tagUsage[$row['id']]));
					
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
				
				$box['tabs']['details']['fields']['tag_label_' . $row['id']] = array(
					'full_width' => true,
					'ord' => $counter,
					'snippet' => 
						array('html' => $html));
				$box['tabs']['details']['fields']['tag_' . $row['id']] = array(
					'same_row' => true,
					'ord' => $counter + 0.1,
					'type' => 'select',
					'values' => $fieldValues,
					'empty_value' => 'Do nothing');
				$box['tabs']['details']['fields']['image_count_' . $row['id']] = array(
					'same_row' => true,
					'full_width' => true,
					'ord' => $counter + 0.2,
					'snippet' => 
						array('html' => $label));
				
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		exitIfNotCheckPriv('_PRIV_MANAGE_MEDIA');
		$ids = explodeAndTrim($box['key']['id']);
		$tags = array();
		$sql = '
			SELECT id, name AS label, color AS tag_color
			FROM ' . DB_NAME_PREFIX . 'image_tags';
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			if (isset($values['tag_' . $row['id']])) {
				self::changeImageTags($ids, $row['id'], $values['tag_' . $row['id']]);
			}
		}
	}
	
	private static function changeImageTags($ids, $tagId, $action = false) {
		if ($action) {
			foreach ($ids as $imageId) {
				if ($action == 'add_tag') {
					setRow('image_tag_link', array(), array('image_id' => $imageId, 'tag_id' => $tagId));
				} elseif ($action == 'remove_tag') {
					deleteRow('image_tag_link', array('image_id' => $imageId, 'tag_id' => $tagId));
				}
			}
		}
	}
}
