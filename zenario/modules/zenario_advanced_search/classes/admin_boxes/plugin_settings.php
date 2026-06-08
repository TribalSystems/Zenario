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
					$values['content_types/html_column_width'] = 20;
				}

				if (!$values['content_types/document_column_width']) {
					$values['content_types/document_column_width'] = 20;
				}

				if (!$values['content_types/news_column_width']) {
					$values['content_types/news_column_width'] = 20;
				}

				if (!$values['content_types/blog_column_width']) {
					$values['content_types/blog_column_width'] = 20;
				}

				if (!$values['content_types/project_column_width']) {
					$values['content_types/project_column_width'] = 20;
				}

				if (!$values['content_types/other_module_column_width']) {
					$values['content_types/other_module_column_width'] = 20;
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

				foreach (['html', 'document', 'news', 'blog', 'project'] as $cType) {
					//Content type HTML page is always enabled.
					if ($cType != 'html') {
						$moduleToCheck = ($cType == 'project') ? 'zenario_project_locations' : 'zenario_ctype_' . $cType;
						if (ze\module::isRunning($moduleToCheck)) {
							unset($box['tabs']['content_types']['fields'][$cType . '_ctype_not_running_warning']);
						}
					}
				}

				$searchInOtherModules = ze\module::sendSignal("signalAdvancedSearchGetSearchableModules", []);
				if (!empty($searchInOtherModules) && is_array($searchInOtherModules)) {
					$ord = 0;
					$modulesWhichDontSupportThumbnails = [];
					
					foreach ($searchInOtherModules as $module) {
						$moduleClassName = $module['module_class_name'];
						$fields['content_types/module_to_search']['values'][$moduleClassName] = ['ord' => ++$ord, 'label' => ze\module::getModuleDisplayNameByClassName($moduleClassName) . ' (' . $moduleClassName . ')'];
						
						foreach ($module['searchable_data_types'] as $searchableDataType => $searchableDataTypeLabel) {
							$fields['content_types/searchable_data_type']['values'][$searchableDataType] = [
								'label' => ze\admin::phrase($searchableDataTypeLabel),
								'visible_if' => "lib.value('search_in_other_modules') && lib.value('module_to_search') == '" . htmlspecialchars($moduleClassName) . "'"
							];
						}
						
						if (!$module['images_are_supported']) {
							$modulesWhichDontSupportThumbnails[] = $moduleClassName;
						}
					}
					
					if (count($modulesWhichDontSupportThumbnails) > 0) {
						$fields['content_types/other_module_show_image']['disabled_if'] = "lib.valueIn('module_to_search', '" . implode("', '", $modulesWhichDontSupportThumbnails) . "')";
						$values['content_types/modules_which_dont_support_images'] = implode(', ', $modulesWhichDontSupportThumbnails);
					}
				}

				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				$fields['first_tab/show_private_content_item_link_control']['hidden'] = !$values['first_tab/search_private_items'];
				
				$retinaSideNote = "If the source image is large enough,
                            the resized image will be output at twice its displayed width &amp; height
                            to appear crisp on retina screens.
                            This will increase the download size.
                            <br/>
                            If the source image is not large enough this will have no effect.";
				
				foreach (['html', 'document', 'news', 'blog', 'project'] as $contentType) {
					$hidden = !$values['content_types/search_' . $contentType] || !$values['content_types/' . $contentType . '_show_featured_image'];
					$this->showHideImageOptions($fields, $values, 'content_types', $hidden, $contentType . '_');

					//Default column heading text
					if (empty($fields['content_types/' . $contentType . '_column_heading_text']['value'])) {
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
							case 'project':
								$columnHeadingText = 'Projects';
								break;
						}
	
						$fields['content_types/' . $contentType . '_column_heading_text']['value'] = $columnHeadingText;
					}
					
					//Default "No results" text
					if (empty($fields['content_types/' . $contentType . '_no_results_text']['value'])) {
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
							case 'project':
								$noResultsText = 'No projects found';
								break;
						}
	
						$fields['content_types/' . $contentType . '_no_results_text']['value'] = $noResultsText;
					}
					
					//Side note for retina feature for content types
					if ($values['content_types/' . $contentType . '_canvas'] != "unlimited") {
						$fields['content_types/' . $contentType . '_canvas']['side_note'] = $retinaSideNote;
					} else {
						unset($fields['content_types/' . $contentType . '_canvas']['side_note']);
					}
				}

				if (empty($fields['content_types/other_module_column_heading_text']['value'])) {
					$fields['content_types/other_module_column_heading_text']['value'] = 'Other results';
				}

				if ($values['content_types/search_in_other_modules']) {
					unset($fields['content_types/other_module_show_image']['note_below']);
					
					if (!empty($values['content_types/modules_which_dont_support_images']) && in_array($values['content_types/module_to_search'], explode(',', $values['content_types/modules_which_dont_support_images']))) {
						$values['content_types/other_module_show_image'] = false;
						$fields['content_types/other_module_show_image']['note_below'] = ze\admin::phrase('The selected module does not support images.');
					}
				}
				
				$hidden = !$values['content_types/search_in_other_modules'] || !$values['content_types/other_module_show_image'];
				$this->showHideImageOptions($fields, $values, 'content_types', $hidden, '');

				$fields['weightings/other_module_title_weighting']['hidden'] =
				$fields['weightings/other_module_description_weighting']['hidden'] =
					!($values['content_types/search_in_other_modules'] && $values['content_types/module_to_search']);
				
				if ($box['tabs']['first_tab']['fields']['search_placeholder'] == true
					&& empty($box['tabs']['first_tab']['fields']['search_placeholder_phrase']['value'])) {
					$box['tabs']['first_tab']['fields']['search_placeholder_phrase']['value'] = 'Search the site';
				}

				if (!$values['first_tab/maximum_results_number']) {
					$values['first_tab/maximum_results_number'] = 20;
				}

				if (!$values['first_tab/keyboard_delay_before_submit']) {
					$values['first_tab/keyboard_delay_before_submit'] = 500;
				}
				
				//If searching in other modules, let the user know what module
				//is expected to be on the results page.
				if ($values['content_types/search_in_other_modules']) {
					$suggestedModule = null;
					
					if ($values['content_types/module_to_search']) {
						switch ($values['content_types/module_to_search']) {
							case 'zenario_ecommerce_document':
							case 'zenario_ecommerce_physical_products':
								$suggestedModule = 'zenario_storefront_products_fea';
								break;
							case 'zenario_location_manager':
								$suggestedModule = 'zenario_locations_fea';
								break;
							case 'zenario_videos_manager':
								$suggestedModule = 'zenario_videos_fea';
								break;
						}
					}
					
					if ($suggestedModule !==  null) {
						$fields['content_types/other_module_view_item_content_item']['notices_below']['expected_module_on_results_page']['hidden'] = false;
						$fields['content_types/other_module_view_item_content_item']['notices_below']['expected_module_on_results_page']['message'] =
							ze\admin::phrase('For best results, use a plugin nest containing the [[suggested_plugin]] plugin on the results page.', ['suggested_plugin' => ze\module::getModuleDisplayNameByClassName($suggestedModule)]);
					} else {
						$fields['content_types/other_module_view_item_content_item']['notices_below']['expected_module_on_results_page']['hidden'] = true;
					}
				}
				
				//Side note for retina feature for searching in other modules
				if ($values['content_types/canvas'] != "unlimited") {
					$fields['content_types/canvas']['side_note'] = $retinaSideNote;
				} else {
					unset($fields['content_types/canvas']['side_note']);
				}

				/**********************
                ** Order of elements **
                *********************/

				//All available fields in Details tab
				$availableFields = [
					'html',
					'document',
					'news',
					'blog',
					'project',
					'other_modules'
				];
													
				$fieldsWithNiceNames = [];
				
				//Give these fields nice names
				foreach ($availableFields as $field) {

					if ((ze::in($field, 'html', 'document', 'news', 'blog', 'project') && $values['content_types/search_' . $field]) || ($field == 'other_modules' && $values['content_types/search_in_other_modules'])) {
						switch ($field) {
							case 'html':
								$niceName = ze\admin::phrase('HTML pages');
								break;
							case 'document':
								$niceName = ze\admin::phrase('Documents');
								break;
							case 'news':
								$niceName = ze\admin::phrase('News');
								break;
							case 'blog':
								$niceName = ze\admin::phrase('Blog');
								break;
							case 'project':
								$niceName = ze\admin::phrase('Projects');
								break;
							case 'other_modules':
								$niceName = ze\admin::phrase('Results from other modules');
								break;
							default:
								$niceName = '';
								break;
						}
							
						$fieldsWithNiceNames[$field] = $niceName;
					}
				}
				
				//Check if this is the first time the admin box has been run...
				if (isset($fields['content_types/search_result_types_order']['current_value'])) {
					//... if not (e.g. switching a tab), use current order instead of getting it from the database...
					$searchResultTypesFields = explode(',', $fields['content_types/search_result_types_order']['current_value']);
				} elseif (!empty($fields['content_types/search_result_types_order']['value'])) {
					//... if yes (opening the admin box), get the order from the database...
					$searchResultTypesFields = explode(',', $fields['content_types/search_result_types_order']['value']);
				} else {
					//... or if the plugin has never been used before, use the default order.
					$searchResultTypesFields = $availableFields;
				}
				
				$fieldsInOrder = [];
				
				//Only process fields selected on Details page
				foreach ($searchResultTypesFields as $field) {
					if ($field) {
						if ((ze::in($field, 'html', 'document', 'news', 'blog', 'project') && $values['content_types/search_' . $field]) || ($field == 'other_modules' && $values['content_types/search_in_other_modules'])) {
							$fieldsInOrder[$field] = $fieldsWithNiceNames[$field];
						}
					}
						
				}
				
				//If a previously unselected field has been selected now, add it
				foreach ($fieldsWithNiceNames as $field => $value) {
					if (
						!isset($searchResultTypesFields[$field])
						&& (ze::in($field, 'html', 'document', 'news', 'blog', 'project') && $values['content_types/search_' . $field]) || ($field == 'other_modules' && $values['content_types/search_in_other_modules'])
					) {
						$fieldsInOrder[$field] = $value;
					}
				}
				
				$fields['content_types/search_result_types_order']['values'] = [];
				$fields['content_types/search_result_types_order']['values'] = $fieldsInOrder;
				
				$fields['content_types/search_result_types_order']['current_value'] 
					= $fields['content_types/search_result_types_order']['value'] = implode(",", array_keys($fields['content_types/search_result_types_order']['values']));
				
				if ($values['content_types/search_in_other_modules']) {
					unset($fields['content_types/other_module_show_image']['note_below']);
					
					if (!empty($values['content_types/modules_which_dont_support_images']) && in_array($values['content_types/module_to_search'], explode(',', $values['content_types/modules_which_dont_support_images']))) {
						$values['content_types/other_module_show_image'] = false;
						$fields['content_types/other_module_show_image']['note_below'] = ze\admin::phrase('The selected module does not support images.');
					}
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
				
				if ($values['first_tab/maximum_results_number'] < 0) {
					$box['tabs']['first_tab']['fields']['maximum_results_number']['error'] = ze\admin::phrase('The page size cannot be a negative number.');
				} elseif ($values['first_tab/maximum_results_number'] > 999) {
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

				if ($values['content_types/search_project']) {
					$enabledColumns[] = 'project';
				}

				if ($values['content_types/search_in_other_modules']) {
					$enabledColumns[] = 'other_module';
				}

				if (empty($enabledColumns)) {
					$fields['content_types/search_html']['error'] =
					$fields['content_types/search_document']['error'] =
					$fields['content_types/search_news']['error'] =
					$fields['content_types/search_blog']['error'] =
					$fields['content_types/search_project']['error'] =
					$fields['content_types/search_in_other_modules']['error'] = ze\admin::phrase('Please select at least 1 content type or search in other module.');
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
				
				//Weightings
				$loopThrough = [
					'content_published_in_the_last_30_days_weighting',
					'content_published_in_the_last_90_days_weighting',
					'content_published_in_the_last_365_days_weighting',
					'content_published_over_365_days_ago_weighting',
					'pinned_content_item_weighting'
				];
				
				foreach ($loopThrough as $settingName) {
					if (!($values['weightings/' . $settingName] >= 0 && $values['weightings/' . $settingName] <= 12)) {
						$fields['weightings/' . $settingName]['error'] = ze\admin::phrase('Please enter a number between 0 and 12.');
					}
				}

				break;
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//..
	}
}