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


class zenario_advanced_search__admin_boxes__plugin_settings extends zenario_advanced_search {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				$fields['first_tab/pagination_style']['values'] = ze\pluginAdm::paginationOptions();

				if (!$values['first_tab/specific_search_results_page']) {
					$cID = $cType = $state = false;
					ze\content::pluginPage($cID, $cType, $state, 'zenario_advanced_search');

					if ($cID && $cType) {
						$tagId = $cType. '_' . $cID;
						$values['first_tab/specific_search_results_page'] = $tagId;
					}
				}

				if (!$values['content_types/html_column_width']) {
					$values['content_types/html_column_width'] = 25;
				}

				if (!$values['content_types/document_column_width']) {
					$values['content_types/document_column_width'] = 25;
				}

				if (!$values['content_types/news_column_width']) {
					$values['content_types/news_column_width'] = 25;
				}

				if (!$values['content_types/blog_column_width']) {
					$values['content_types/blog_column_width'] = 25;
				}

				if (!$values['first_tab/title_char_limit_value']) {
					$values['first_tab/title_char_limit_value'] = 50;
				}

				if (!$values['first_tab/summary_char_limit_value']) {
					$values['first_tab/summary_char_limit_value'] = 50;
				}

				$languages = ze\lang::getLanguages();
				if (count($languages) < 2) {
					$fields['first_tab/let_user_select_language']['hidden'] = true;
				}

				foreach (['document', 'news', 'blog'] as $cType) {
					if (ze\module::isRunning('zenario_ctype_' . $cType)) {
						unset($box['tabs']['content_types']['fields'][$cType . '_ctype_not_running_warning']);
					}
				}

				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				$fields['first_tab/hide_private_items']['hidden'] = !$values['first_tab/show_private_items'];
				
				foreach (['html', 'document', 'news', 'blog'] as $contentType) {
					$hidden = !$values['content_types/search_' . $contentType] || !$values['content_types/' . $contentType . '_show_feature_image'];
					$this->showHideImageOptions($fields, $values, 'content_types', $hidden, $contentType . '_feature_image_');

					//Default column heading text
					if (
						$values['content_types/search_' . $contentType]
						&& !$values['content_types/' . $contentType . '_column_heading_text']
					) {
						$columnHeadingText = '';
						switch ($contentType) {
							case 'html':
								$columnHeadingText = 'HTML pages';
								break;
							case 'document':
								$columnHeadingText = 'Documents';
								break;
							case 'news':
								$columnHeadingText = 'News articles';
								break;
							case 'blog':
								$columnHeadingText = 'Blog posts';
								break;
						}

						$values['content_types/' . $contentType . '_column_heading_text'] = $columnHeadingText;
					}
					
					//Default "No results" text
					if (
						$values['content_types/search_' . $contentType]
						&& $values['content_types/' . $contentType . '_show_message_if_no_results']
						&& !$values['content_types/' . $contentType . '_no_results_text']
					) {
						$noResultsText = '';
						switch ($contentType) {
							case 'html':
								$noResultsText = 'No results found';
								break;
							case 'document':
								$noResultsText = 'No documents found';
								break;
							case 'news':
								$noResultsText = 'No news articles found';
								break;
							case 'blog':
								$noResultsText = 'No blog posts found';
								break;
						}

						$values['content_types/' . $contentType . '_no_results_text'] = $noResultsText;
					}
				}
				
				if ($box['tabs']['first_tab']['fields']['search_placeholder'] == true
					&& empty($box['tabs']['first_tab']['fields']['search_placeholder_phrase']['value'])) {
					$box['tabs']['first_tab']['fields']['search_placeholder_phrase']['value'] = 'Search the site';
				}

				if (!$values['first_tab/page_size'] || $values['first_tab/page_size'] != 'maximum_of') {
					$values['first_tab/page_size'] = 'maximum_of';
					if (!$values['first_tab/maximum_results_number']) {
						$values['first_tab/maximum_results_number'] = 20;
					}
				}

				if (!$values['first_tab/keyboard_delay_before_submit']) {
					$values['first_tab/keyboard_delay_before_submit'] = 500;
				}
				
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'plugin_settings':
				$weightingSet = false;
				if (is_array($box['tabs']['weightings'])) {
					foreach ($box['tabs']['weightings'] as $weighting) {
						if ($weighting && $weighting != '_NONE') {
							$weightingSet = true;
							break;
						}
					}
				}
				
				if (!$weightingSet) {
					$box['tabs']['weightings']['errors'][] = ze\admin::phrase('Please choose at least one weighting.');
				}
				
				if ($values['first_tab/page_size'] == 'maximum_of' && $values['first_tab/maximum_results_number'] < 0) {
					$box['tabs']['first_tab']['fields']['maximum_results_number']['error'] = ze\admin::phrase('The page size cannot be a negative number.');
				} elseif ($values['first_tab/page_size'] == 'maximum_of' && $values['first_tab/maximum_results_number'] > 999) {
					$box['tabs']['first_tab']['fields']['maximum_results_number']['error'] = ze\admin::phrase('The page size cannot exceed 999.');
				}

				$enabledColumns = [];
				if ($values['content_types/search_html']) {
					$enabledColumns[] = 'html';
				}

				if ($values['content_types/search_document']) {
					$enabledColumns[] = 'document';
				}

				if ($values['content_types/search_news']) {
					$enabledColumns[] = 'news';
				}

				if ($values['content_types/search_blog']) {
					$enabledColumns[] = 'blog';
				}

				if (empty($enabledColumns)) {
					$fields['content_types/search_html']['error'] =
					$fields['content_types/search_document']['error'] =
					$fields['content_types/search_news']['error'] =
					$fields['content_types/search_blog']['error'] = ze\admin::phrase('Please select at least 1 content type.');
				} else {
					$columnWidthSum = 0;
					foreach ($enabledColumns as $enabledColumn) {
						$columnWidthSum += (float) $values['content_types/' . $enabledColumn . '_column_width'];

						if ((float) $values['content_types/' . $enabledColumn . '_column_width'] == 0) {
							$fields['content_types/' . $enabledColumn . '_column_width']['error'] = ze\admin::phrase('The column width must be greater than 0%.');
						}
					}

					if ($columnWidthSum > 100) {
						foreach ($enabledColumns as $enabledColumn) {
							if (empty($fields['content_types/' . $enabledColumn . '_column_width']['error'])) {
								$fields['content_types/' . $enabledColumn . '_column_width']['error'] = ze\admin::phrase('Total column width must not exceed 100%.');
							}
						}
					}
				}

				if ($values['first_tab/limit_num_of_chars_in_title'] && ($values['first_tab/title_char_limit_value'] < 1 || $values['first_tab/title_char_limit_value'] > 125)) {
					$fields['first_tab/title_char_limit_value']['error'] = ze\admin::phrase('Please enter a number between 1 and 125.');
				}

				break;
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//..
	}
}