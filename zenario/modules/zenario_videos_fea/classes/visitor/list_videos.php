<?php
/*
 * Copyright (c) 2024, Tribal Limited
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


class zenario_videos_fea__visitor__list_videos extends zenario_videos_fea__visitor_base {
	
	protected $scope = false;
	protected $videoId = false;
	
	public function init() {
		$this->scope = $this->setting('scope');
		if ($this->scope == 'all' || $this->scope == 'similar_videos' || $this->scope == 'specific_categories') {
			$this->videoId = ze::request($this->idVarName);
			return parent::init();
		} else {
			return ZENARIO_403_NO_PERMISSION;
		}
	}
	
	protected function populateItemsIdCol($path, &$tags, &$fields, &$values) {
		return 'id';
	}
	protected function populateItemsIdColDB($path, &$tags, &$fields, &$values) {
		return 'v.id';
	}
	protected function populateItemsSelectCount($path, &$tags, &$fields, &$values) {
		return '
			SELECT COUNT(DISTINCT v.id)';
	}
	protected function populateItemsSelect($path, &$tags, &$fields, &$values) {
		$sql = '
			SELECT
				v.id,
				v.image_id,
				v.title,
				v.short_description AS description,
				v.date,
				v.url';
		
		if ($datasetFieldIds = $this->setting('show_dataset_fields')) {
			$datasetFieldIds = explode(',', $datasetFieldIds);
			foreach ($datasetFieldIds as $datasetFieldId) {
				$datasetField = ze\row::get('custom_dataset_fields', ['db_column', 'is_system_field', 'type'], $datasetFieldId);
				if ($datasetField && $datasetField['db_column']) {
					if ($datasetField['is_system_field']) {
						$sql .= ', v.' . ze\escape::sql($datasetField['db_column']) . ' ';
					} else {
						$sql .= ', vcd.' . ze\escape::sql($datasetField['db_column']) . ' ';
					}
				}
			}
		}
		
		return $sql;
	}
	protected function populateItemsFrom($path, &$tags, &$fields, &$values) {
		$sql = '
			FROM ' . DB_PREFIX . ZENARIO_VIDEOS_MANAGER_PREFIX . 'videos v
			LEFT JOIN ' . DB_PREFIX . ZENARIO_VIDEOS_MANAGER_PREFIX . 'category_video_link cvl
				ON v.id = cvl.video_id
			LEFT JOIN '. DB_PREFIX. ZENARIO_VIDEOS_MANAGER_PREFIX . 'videos_custom_data AS vcd
				ON v.id = vcd.video_id';
		return $sql;
	}
	protected function populateItemsWhere($path, &$tags, &$fields, &$values) {
		$sql = '
			WHERE TRUE';
		
		if ($this->scope == 'similar_videos') {
			$categoryFilters = [];
			$result = ze\row::query(ZENARIO_VIDEOS_MANAGER_PREFIX . 'category_video_link', ['category_id'], ['video_id' => $this->videoId]);
			while ($row = ze\sql::fetchAssoc($result)) {
				$categoryFilters[] = $row['category_id'];
			}
			if ($categoryFilters) {
				$sql .= '
					AND cvl.category_id IN (' . ze\escape::in($categoryFilters, 'numeric') . ')';
			} else {
				$sql .= '
					AND FALSE';
			}
		} else {
			//Filter videos by category
			if ($categories = $this->setting('category_filters')) {
				$categoryFilters = [];
				foreach (explode(',', $categories) as $categoryId) {
					$categoryFilters[] = $categoryId;
				}

				if (ze::in($this->setting('in_any_or_all_categories'), 'any', 'all')) {
					$sql .= '
						AND cvl.category_id IN (' . ze\escape::in($categoryFilters, 'numeric') . ')';
					
				}
			}
		}

		return $sql;
	}
	protected function populateItemsGroupBy($path, &$tags, &$fields, &$values) {
		$sql = '
			GROUP BY v.id';
		
		if ($this->scope == 'specific_categories' && ($categories = $this->setting('category_filters')) && $this->setting('in_any_or_all_categories') == 'all') {
			$categoryFilters = [];
			foreach (explode(',', $categories) as $categoryId) {
				$categoryFilters[] = $categoryId;
			}

			$categoriesCount = count($categoryFilters);
			if ($categoriesCount > 0) {
				$sql .= '
					HAVING COUNT(DISTINCT cvl.category_id) = ' . (int) $categoriesCount;
			}
				
		}

		return $sql;
	}
	protected function populateItemsOrderBy($path, &$tags, &$fields, &$values) {
		//Order by options
		$sql = '';
		$orderBy = $this->setting('order_by');
		if ($orderBy == 'alphabetic') {
			$sql = '
				ORDER BY v.title';
		} elseif ($orderBy == 'date') {
			$sql = '
				ORDER BY v.date DESC, v.title';
		} elseif ($orderBy == 'custom_dataset_field' && ($datasetFieldId = $this->setting('sort_by_custom_dataset_field'))) {
			$datasetField = ze\row::get('custom_dataset_fields', ['db_column', 'is_system_field', 'type'], $datasetFieldId);
			if ($datasetField && $datasetField['db_column']) {
				if ($datasetField['is_system_field']) {
					$sql = '
						ORDER BY v.' . ze\escape::sql($datasetField['db_column']) . ' ASC, v.title ASC';
				} else {
					$sql = '
						ORDER BY  vcd.' . ze\escape::sql($datasetField['db_column']) . ' ASC, v.title ASC';
				}
			}
		}
		return $sql;
	}
	
	protected function populateItemsPageSize($path, &$tags, &$fields, &$values) {
		return false;
	}
	
	protected function formatItemRow(&$item, $path, &$tags, &$fields, &$values) {
		$item['date'] = ze\date::format($item['date']);
		$item['description'] = nl2br(htmlspecialchars($item['description']));
		$item['title'] = $this->rapInViewVideoAnchor($item['title'], $item['id']);
		
		$parsed = parse_url($item['url']);
		
		if ($this->setting('show_images')) {
			//Show a product image
			$width = $height = $url = $imageId = false;
			
			//If set, use the image assigned in the database...
			if (!empty($item['image_id'])) {
				$imageId = $item['image_id'];
			} elseif ($this->setting('fall_back_to_default_image') && $this->setting('default_image_id')) {
				//...or use the fallback image...
				$imageId = $this->setting('default_image_id');
			}
		
			if ($imageId) {
				ze\file::imageLink($width, $height, $url, $imageId, $this->setting('image_width'), $this->setting('image_height'), $this->setting('image_canvas'));
				$item['image'] = '<img src="' . htmlspecialchars($url) . '" width="' . $width . '" height="' . $height . '">';
				$item['image'] = $this->rapInViewVideoAnchor($item['image'], $item['id']);
			} else {
				//...or display nothing.
				$item['image'] = "";
			}
		}
		
		if ($this->setting('highlight_currently_playing_video')) {
			if ($this->videoId && $item['id'] == $this->videoId) {
				if (empty($item['row_class'])) {
					$item['row_class'] = '';
				}
				$item['row_class'] .= ' current';
			}
		}
	}
	
	public function fillVisitorTUIX($path, &$tags, &$fields, &$values) {
		//If displaying custom fields, set up the tuix columns
		if ($datasetFieldIds = $this->setting('show_dataset_fields')) {
			$datasetFieldIds = explode(',', $datasetFieldIds);
			foreach ($datasetFieldIds as $datasetFieldId) {
				$datasetField = ze\row::get('custom_dataset_fields', ['db_column', 'label'], $datasetFieldId);
				if ($datasetField && $datasetField['db_column']) {
					$tags['columns'][$datasetField['db_column']] = [
						'title' => $datasetField['label']
					];
				}
			}
		}
		
		parent::fillVisitorTUIX($path, $tags, $fields, $values);
		$this->translatePhrasesInTUIX($tags, $path);
		$this->populateItems($path, $tags, $fields, $values);
		
		if ($this->setting('view_video_new_window')) {
			$tags['item_buttons']['view']['new_window'] = true;
		}

		if (!$this->setting('list_videos__column__short_description')) {
			$tags['columns']['description']['hidden'] = true;
		}

		if (!$this->setting('show_video_titles')) {
			$tags['columns']['title']['hidden'] = true;
		}

		if (!$this->setting('list_videos__column__date')) {
			$tags['columns']['date']['hidden'] = true;
		}
		
		if ($this->scope == 'all') {
			$tags['header_html'] = 
				'<div id="video_view_toggle_wrap" class="view_toggle_wrap">
					<div onclick="zenario_videos_fea.changeView(this, \'' . $this->containerId . '\', \'grid\')" class="on">Grid</div>
					<div onclick="zenario_videos_fea.changeView(this, \'' . $this->containerId . '\', \'list\')">List</div>
				</div>';
		}
		
		$tags['perms'] = [
			'manage' => ze\user::can('manage', 'video')
		];
	}
	
	public function handlePluginAJAX() {
		$videoId = $_POST['id'] ?? false;
		switch (ze::post('action')) {
			default:
				echo 'Error, unrecognised command';
		}
	}
	
	public function rapInViewVideoAnchor($innerHTML, $itemId) {
		
		if ($this->conductorEnabled()) {
			$url = $this->conductorLink('view_video', [$this->idVarName => $itemId]);
			if ($this->setting('enable.view_video')) {
				$anchor = '<a href="' . htmlspecialchars($url) . '"';
				if ($this->setting('view_video_new_window')) {
					$anchor .= ' target="_blank"';
				} else {
					$anchor .= " onclick='zenario_conductor.go(\"" . $this->slotName . "\", \"view_video\", " . json_encode([$this->idVarName => $itemId]) . "); return false;'";
				}
				$anchor .= '>';
				$anchor .= $innerHTML . '</a>';
			} else {
				$anchor = $innerHTML;
			}
		} else {
			$anchor = $innerHTML;
		}
		return $anchor;
	}
}