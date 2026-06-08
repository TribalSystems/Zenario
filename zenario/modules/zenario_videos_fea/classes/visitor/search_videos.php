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


class zenario_videos_fea__visitor__search_videos extends zenario_videos_fea__visitor_base {
	
	protected $searchTerms = [];
	
	public function init() {
		$searchString = substr($_REQUEST['search'] ?? '', 0, 100);
		
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
				$this->searchTerms = $searchTerms;
			}
		}
		
		return parent::init();
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
		
		if ($this->searchTerms) {
			$scoreStatementFirstLine = true;
			$sqlFields = ',
				(';
			$wildcard = "*";
			
			$weights = [
				'_NONE'		=> 0,
				'_LOW'		=> 1,
				'_MEDIUM'	=> 3,
				'_HIGH'		=> 12
			];
			
			$weightings = ['title' => $weights[$this->setting('title_weighting')], 'description' => $weights[$this->setting('description_weighting')]];
			
			foreach ($this->searchTerms as $searchTerm => $searchTermType) {

				foreach (['v.title', 'v.short_description', 'v.description'] as $column) {
					if (!$scoreStatementFirstLine) {
						$sqlFields .= " + ";
					}

					$scoreStatementFirstLine = false;
					
					if ($column == 'v.title') {
						$weighting = $weightings['title'];
					} elseif (ze::in($column, 'v.short_description', 'v.description')) {
						$weighting = $weightings['description'];
					}

					$sqlFields .= "
						(MATCH (". $column. ") AGAINST (\"" . ze\escape::sql($searchTerm) . $wildcard . "\" IN BOOLEAN MODE) * " . $weighting . ")";
				}
			}

			$sqlFields .= "
				) AS score";
			
			$sql .= $sqlFields;
		} else {
			$sql .= ',
				0 AS score';
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
		
		$whereStatementFirstLine = true;
		
		if ($this->searchTerms) {
			$firstRow = true;

			$sqlMatch = '
				AND (';

			foreach ($this->searchTerms as $searchTerm => $searchTermType) {
				$wildcard = "*";
				
				if ($whereStatementFirstLine) {
					$sqlMatch .= "
						(";
					$whereStatementFirstLine = false;
				} else {
					$sqlMatch .= "
						AND (";
				}
				
				$firstRow = true;

				foreach (['v.title', 'v.short_description', 'v.description'] as $column) {
					if ($firstRow) {
						$or = '';
						$firstRow = false;
					} else {
						$or = " OR";
					}
					
					$sqlMatch .= $or . "
						(MATCH (". $column. ") AGAINST (\"" . ze\escape::sql($searchTerm) . $wildcard . "\" IN BOOLEAN MODE))";
				}
				
				$sqlMatch .= "
					)";
			}
			
			$sqlMatch .= ")";
			
			$sql .= $sqlMatch;
		} else {
			$sql .= " AND FALSE";
		}

		return $sql;
	}
	
	protected function populateItemsGroupBy($path, &$tags, &$fields, &$values) {
		$sql = '
			GROUP BY v.id';

		return $sql;
	}
	
	protected function populateItemsOrderBy($path, &$tags, &$fields, &$values) {
		$sql = '
			ORDER BY score DESC, v.title';
		
		return $sql;
	}
	
	protected function populateItemsPageSize($path, &$tags, &$fields, &$values) {
		return (int)$this->setting('page_size');
	}
	
	protected function formatItemRow(&$item, $path, &$tags, &$fields, &$values) {
		$item['date'] = ze\date::format($item['date']);
		$item['description'] = nl2br(htmlspecialchars($item['description']));
		
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
				ze\image::link($width, $height, $url, $imageId, $this->setting('image_width'), $this->setting('image_height'), $this->setting('image_canvas'));
				$item['image'] = '<img src="' . htmlspecialchars($url) . '" width="' . $width . '" height="' . $height . '">';
			} else {
				//...or display nothing.
				$item['image'] = "";
			}
		}
	}
	
	public function fillVisitorTUIX($path, &$tags, &$fields, &$values) {
		if ($this->searchTerms) {
			$tags['no_items_message'] = $this->phrase('No videos found');
		} else {
			$tags['no_items_message'] = '';
		}
		
		$viewResultsAs = $this->setting('view_results_as');
		if ($viewResultsAs == 'list') {
			$tags['key']['view'] = 'list';
			$tags['collection_buttons']['block_view']['hidden'] =
			$tags['collection_buttons']['list_view']['hidden'] = true;
		} elseif ($viewResultsAs == 'blocks') {
			$tags['key']['view'] = 'blocks';
			$tags['collection_buttons']['block_view']['hidden'] =
			$tags['collection_buttons']['list_view']['hidden'] = true;
		}
		
		if ($tags['key']['view'] == 'list') {
			$tags['microtemplate'] = 'fea_list';
			$tags['css_class'] = '';
			$tags['collection_buttons']['list_view']['css_class'] .= ' selected';
		} else {
			$tags['microtemplate'] = 'fea_list_blocks';
			$tags['css_class'] = 'zfea_block_like_block';
			$tags['collection_buttons']['block_view']['css_class'] .= ' selected';
		}
		
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

		if (!$this->setting('search_videos__column__short_description')) {
			$tags['columns']['description']['hidden'] = true;
		}

		if (!$this->setting('show_video_titles')) {
			$tags['columns']['title']['hidden'] = true;
		}

		if (!$this->setting('search_videos__column__date')) {
			$tags['columns']['date']['hidden'] = true;
		}
	}
}