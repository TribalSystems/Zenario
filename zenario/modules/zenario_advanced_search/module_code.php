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

class zenario_advanced_search extends ze\moduleBaseClass {
	
	protected $mergeFields = [];
	protected $styles = [];
	protected $fields;
	protected $cTypeToSearch = '%all%';
	protected $searchString;
	protected $results;
	protected $searchResultTypesOrder;
	protected $releaseDateSetting;
	
	protected $langCount = 0;
	protected $language_id = '';
	protected $keywords = '';
	
	protected $page = 0;
	
	public function init() {
	
		$this->searchResultTypesOrder = $this->setting('search_result_types_order');

		if (!$this->searchResultTypesOrder) {
			//Fallback code for plugins created before the order setting was introduced
			$order = [];
			if ($this->setting('search_html')) {
				$order[] = 'html';
			}

			if ($this->setting('search_document')) {
				$order[] = 'document';
			}

			if ($this->setting('search_news')) {
				$order[] = 'news';
			}

			if ($this->setting('search_blog')) {
				$order[] = 'blog';
			}

			if ($this->setting('search_project')) {
				$order[] = 'project';
			}

			if ($this->setting('search_in_other_modules')) {
				$order[] = 'other_modules';
			}

			$this->searchResultTypesOrder = implode(',', $order);
		}

		$searchResultTypesOrderFirstElement = explode(',', $this->searchResultTypesOrder)[0];
		if (ze::in($searchResultTypesOrderFirstElement, 'html', 'document', 'news', 'blog', 'project')) {
			$defaultTab = $searchResultTypesOrderFirstElement;
		} elseif ($searchResultTypesOrderFirstElement == 'other_modules') {
			$defaultTab = 'results_from_module';
		}

		if (ze::request('clearSearch')) {
			$_REQUEST['language_id'] = $_POST['language_id'] = '0';
			$_REQUEST['searchString'] = $_POST['searchString'] = '';
			$_REQUEST['ctab'] = $_POST['ctab'] = $defaultTab;
		}
		
		$this->langCount = ze\lang::count();
		
		$this->cTypeToSearch = (ze::request('ctab') ?: $defaultTab);
		//Catch the case where a hacker is trying to break the page
		if (!ze::in($this->cTypeToSearch, 'html', 'document', 'news', 'blog', 'project')) {
			if ($this->cTypeToSearch == 'results_from_module') {
				$this->cTypeToSearch = 'results_from_module';
			} else {
				$this->cTypeToSearch = $defaultTab;
			}
		}
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetOrPostVarIsSet = false, $ifSessionVarOrCookieIsSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByFile = false, $clearByModuleData = false);
		
		$this->mergeFields = [];
		
		$this->mergeFields['Search_Results'] = true;
		
		$this->mergeFields['Delay'] = (int) $this->setting('keyboard_delay_before_submit');
		$mode = $this->setting('mode');
		
		$this->searchString = '';
		$this->page = 0;
		if ($this->setting('mode') == 'search_page' || (ze::in($this->setting('mode'), 'search_entry_box', 'search_entry_box_show_always') && $this->isAJAXReload())) {
			//Do not pre-populate the search term in "Search entry box" mode on page refresh.
			//However, once the user actually searches for something, carry on as normal.
			$this->searchString = substr($_REQUEST['searchString'] ?? '', 0, 100);
			$this->page = (int) ($_REQUEST['page'] ?? 1) ?: 1;
			
			//Remember the search parameters
			$params = [];
			foreach (['page', 'ctab', 'language_id', 'searchString'] as $param) {
				if (!empty($_POST[$param])) {
					$params[$param] = $_POST[$param];
				} elseif (!empty($_REQUEST[$param])) {
					$params[$param] = $_REQUEST[$param];
				}
			}
			
			$this->callScript(
				'zenario', 
				'recordRequestsInURL',
				$this->containerId,
				$params
			);
		}

		if (ze::in($this->setting('mode'), 'search_entry_box', 'search_entry_box_show_always')) {
			$this->styles[] = 'body.mobile #' . $this->containerId . '_search_results { display: block; }';
			
			foreach (['html', 'document', 'news', 'blog', 'project'] as $contentType) {
				if ($this->setting('search_' . $contentType)) {
					$this->styles[] = '#' . $this->containerId . '_' . $contentType . '_results { width: ' . $this->setting($contentType . '_column_width') . '% }';
					$this->styles[] = 'body.mobile #' . $this->containerId . '_' . $contentType . '_results { width: 100% }';
				}
			}

			if ($this->setting('search_in_other_modules')) {
				$this->styles[] = '#' . $this->containerId . '_results_from_module { width: ' . $this->setting('other_module_column_width') . '% }';
				$this->styles[] = 'body.mobile #' . $this->containerId . '_results_from_module { width: 100% }';
			}
		}

		$this->mergeFields['Default_Tab'] = $defaultTab;
		$this->mergeFields['Uses_Separate_Results_Page'] = (int) ($this->setting('mode') == 'search_entry_box' && $this->setting('use_specific_search_results_page'));
		
		
		//If we're reloading via AJAX, our addToPageHead() method won't be called, and the addStylesOnAJAXReload() function will add the styles.
		//Otherwise the styles will be added using addToPageHead() and addStylesOnAJAXReload() as as normal.
		$this->addStylesOnAJAXReload($this->styles);
		
		return true;
	}

	public function addToPageHead() {
		$this->addStylesToPageHead($this->styles);
	}
	
	public function sqlToSearchContentTable(
		$hidePrivateItems = true,
		$onlyShow = false,
		$extraJoinSQL = ''
	) {
		$sql = "
			FROM ". DB_PREFIX. "content_items_searchable_cache AS cc
			INNER JOIN ". DB_PREFIX. "translation_chains AS tc
				ON cc.content_type = tc.type
			INNER JOIN ". DB_PREFIX. "content_items AS c
				ON tc.equiv_id = c.equiv_id
				AND tc.type = c.type
				AND cc.content_id = c.id
			INNER JOIN ". DB_PREFIX. "content_item_versions AS v
				ON c.id = v.id 
				AND c.type = v.type 
				AND cc.content_version = v.version";
	
		$sql .= "
			". $extraJoinSQL;
	
	
		$userId = \ze\user::id();
	
		//Filter by whether the current viewer can see each item
		if (!$hidePrivateItems) {
			//If show_private_items is enabled, show all items
			$sql .= "
			WHERE TRUE";
		
		} elseif (!$userId && $onlyShow == 'private') {
			//Private items can only be seen by logged in users...
			$sql .= "
			WHERE FALSE";
		  
		} elseif (!$userId || $onlyShow == 'public') {
			//If the visitor is not logged in, only show public items
			$sql .= "
			WHERE tc.privacy = 'public'";
	
		} else {
			//If the visitor is logged in, check which items they can see
		
			$groupsList = "FALSE";
			foreach (\ze\user::groups($userId) as $groupId => $groupName) {
				$sql .= "
					LEFT JOIN ". DB_PREFIX. "group_link AS gcl". $groupId. "
					   ON gcl". $groupId. ".link_from = 'chain'
					  AND gcl". $groupId. ".link_from_id = tc.equiv_id
					  AND gcl". $groupId. ".link_from_char = tc.type
					  AND gcl". $groupId. ".link_to = 'group'
					  AND gcl". $groupId. ".link_to_id = ". $groupId;
			
				if ($groupsList == "FALSE") {
					$groupsList = "";
				} else {
					$groupsList .= " OR ";
				}
			
				$groupsList .= "gcl". $groupId. ".link_to_id IS NOT NULL";
			}
		
			$sql .= "
			WHERE IF (tc.privacy = 'group_members',
				". $groupsList. ",
				tc.privacy IN ('public', 'logged_in')
			)";
			
			//Content items that are private and only available to users in or not in a smart group
			//are always excluded by this function. There is no need to process smart groups.
		}
	
		if ($onlyShow == 'private') {
			$sql .= "
			  AND tc.privacy IN ('logged_in', 'group_members', 'with_role', 'in_smart_group', 'logged_in_not_in_smart_group')";
		}

		return $sql;
	}
		
	public function getLanguagesOptions(){
		$options = [];
		$options[0] =  $this->phrase('-- All languages --');
		foreach (ze\lang::getLanguages() as $lang) {
			$options[$lang['id']] = $lang['language_local_name'];
		}
		return $options;
	}
	
	protected function setSearchFields() {
		$weights = 
			[
				'_NONE'		=> 0,
				'_LOW'		=> 1,
				'_MEDIUM'	=> 3,
				'_HIGH'		=> 12];
		
		$fields = [];
		$fields[] =	['name' => 'cc.alias',				'weighting' => $weights[$this->setting('alias_weighting')]];
		$fields[] =	['name' => 'c.language_id',			'weighting' => 0];
		$fields[] =	['name' => 'cc.title',				'weighting' => $weights[$this->setting('title_weighting')]];
		$fields[] =	['name' => 'cc.keywords',			'weighting' => $weights[$this->setting('keywords_weighting')]];
		$fields[] =	['name' => 'cc.description',		'weighting' => $weights[$this->setting('description_weighting')]];
		$fields[] =	['name' => 'cc.filename',			'weighting' => $weights[$this->setting('filename_weighting')]];
		$fields[] =	['name' => 'cc.content_summary',	'weighting' => $weights[$this->setting('content_summary_weighting')]];
		$fields[] =	['name' => 'v.feature_image_id',	'weighting' => 0];
		$fields[] =	['name' => 'cc.content_item_text',	'weighting' => $weights[$this->setting('content_weighting')]];
		$fields[] =	['name' => 'cc.file_extract',		'weighting' => $weights[$this->setting('extract_weighting')]];

		$this->fields = [];
		
		if ($this->setting('search_html')) {
			$this->fields['html'] = $fields;
		}
		
		if ($this->setting('search_document')) {
			$this->fields['document'] = $fields;
		}
		
		if ($this->setting('search_news')) {
			$this->fields['news'] = $fields;
		}

		if ($this->setting('search_blog')) {
			$this->fields['blog'] = $fields;
		}

		if ($this->setting('search_project')) {
			$this->fields['project'] = $fields;
		}
	}
	
	public function showSlot() {
		$this->mergeFields['Container_Id'] = $this->containerId;
		$this->mergeFields['Mode'] = $this->setting('mode');

		//Only show tabs in "Search page" mode.
		if ($this->mergeFields['Mode'] == 'search_page' && $this->searchString) {
			$this->mergeFields['Search_Result_Tabs'] = true;
		}
		
		if ($this->mergeFields['Mode'] == 'search_page' && $this->setting('let_user_select_language')) {
			$languages = ze\lang::getLanguages();
			if (count($languages) > 1) {
				$this->mergeFields['HasLanguageSelection'] = true;
			}
		}
		
		if ($this->mergeFields['Mode'] == 'search_page' && $this->searchString) {
			$this->mergeFields['Search_Result_Heading'] = true;
			$this->mergeFields['Search_Results_For'] = $this->phrase('Search results for [[term]]:', ['term' => htmlspecialchars('"'. $this->searchString. '"')]);
		}

		if (ze::in($this->mergeFields['Mode'], 'search_entry_box', 'search_entry_box_show_always')) {
			if ($this->setting('search_html')) {
				$this->mergeFields['Html_Column_Width'] = $this->setting('html_column_width');
			}

			if ($this->setting('search_document')) {
				$this->mergeFields['Document_Column_Width'] = $this->setting('document_column_width');
			}

			if ($this->setting('search_news')) {
				$this->mergeFields['News_Column_Width'] = $this->setting('news_column_width');
			}

			if ($this->setting('search_blog')) {
				$this->mergeFields['Blog_Column_Width'] = $this->setting('blog_column_width');
			}

			if ($this->setting('search_project')) {
				$this->mergeFields['Project_Column_Width'] = $this->setting('project_column_width');
			}
		}
		
		$this->drawSearchBox();
		
		if ($this->searchString) {
			$this->doSearch($this->mergeFields['Mode']);

			//Order the result types using the plugin setting.
			//Also pass the order to the framework so that the correct snipipets can be called in the right order.
			$this->mergeFields['Search_Result_Types_Order'] = explode(',', $this->searchResultTypesOrder);

			if ($this->mergeFields['Search_Result_Types_Order']) {
				$searchResultTabs = [];
				foreach ($this->mergeFields['Search_Result_Types_Order'] as $searchResultType) {
					if (ze::in($searchResultType, 'html', 'document', 'news', 'blog', 'project')) {
						$searchResultTabs[$searchResultType] = $this->mergeFields['Search_Result_Tab'][$searchResultType];
					} elseif ($searchResultType == 'other_modules') {
						$searchResultTabs['Results_From_Module'] = $this->mergeFields['Search_Result_Tab']['Results_From_Module'];
					}
				}

				$this->mergeFields['Search_Result_Tab'] = $searchResultTabs;
				unset($searchResultTabs);
			}
			
			//If this is the "Search entry box" mode, display nothing when there are no results.
			//But in "Search page" mode, still display the results div with tabs showing 0 results.
			
			if (ze::in($this->mergeFields['Mode'], 'search_entry_box', 'search_entry_box_show_always')) {
				$this->mergeFields['Search_Result_Rows'] = false;
			} else {
				$this->mergeFields['Search_Result_Rows'] = true;
			}
			
			$this->mergeFields['Record_count_total'] = 0;
			$showPressEnter = false;

			foreach ($this->results as $type => &$result) {
				if ($this->mergeFields['Mode'] == 'search_page' && $type != $this->cTypeToSearch) {
					continue;
				}
				
				$maxResultsShowable = $this->setting('maximum_results_number');

				switch ($type) {
					case 'html':
						$this->mergeFields['Html_Page_Column_Heading_Text'] = $this->phrase($this->setting('html_column_heading_text'));

						if ($result['Record_count_this_page']) {
							$this->mergeFields['Search_Result_Rows'] = true;
							$this->mergeFields['Html_Page_Search_Results'] = $result['search_results'];
							
							if ($result['Record_count_total'] > $maxResultsShowable) {
								$additionalResultsCount = $result['Record_count_total'] - $maxResultsShowable;
								$this->mergeFields['Html_Page_And_X_More_Results_Phrase'] = $this->nPhrase('and 1 more', 'and [[count]] more', $additionalResultsCount);
								$showPressEnter = true;
							}
						} else {
							$this->mergeFields['Html_Page_Search_No_Results'] = true;

							if ($this->setting('html_show_message_if_no_results') && $this->setting('html_no_results_text')) {
								$this->mergeFields['Html_No_Results_Text'] = $this->phrase($this->setting('html_no_results_text'));
							}
						}

						break;
					case 'document':
						$this->mergeFields['Document_Column_Heading_Text'] = $this->phrase($this->setting('document_column_heading_text'));

						if ($result['Record_count_this_page']) {
							$this->mergeFields['Search_Result_Rows'] = true;
							$this->mergeFields['Document_Search_Results'] = $result['search_results'];
							
							if ($result['Record_count_total'] > $maxResultsShowable) {
								$additionalResultsCount = $result['Record_count_total'] - $maxResultsShowable;
								$this->mergeFields['Document_And_X_More_Results_Phrase'] = $this->nPhrase('and 1 more document', 'and [[count]] more documents', $additionalResultsCount);
								$showPressEnter = true;
							}
						} else {
							$this->mergeFields['Document_Search_No_Results'] = true;

							if ($this->setting('document_show_message_if_no_results') && $this->setting('document_no_results_text')) {
								$this->mergeFields['Document_No_Results_Text'] = $this->phrase($this->setting('document_no_results_text'));
							}
						}
						
						break;
					case 'news':
						$this->mergeFields['News_Column_Heading_Text'] = $this->phrase($this->setting('news_column_heading_text'));

						if ($result['Record_count_this_page']) {
							$this->mergeFields['Search_Result_Rows'] = true;
							$this->mergeFields['News_Search_Results'] = $result['search_results'];
							
							if ($result['Record_count_total'] > $maxResultsShowable) {
								$additionalResultsCount = $result['Record_count_total'] - $maxResultsShowable;
								$this->mergeFields['News_And_X_More_Results_Phrase'] = $this->nPhrase('and 1 more news item', 'and [[count]] more news items', $additionalResultsCount);
								$showPressEnter = true;
							}
						} else {
							$this->mergeFields['News_Search_No_Results'] = true;

							if ($this->setting('news_show_message_if_no_results') && $this->setting('news_no_results_text')) {
								$this->mergeFields['News_No_Results_Text'] = $this->phrase($this->setting('news_no_results_text'));
							}
						}

						break;
					case 'blog':
						$this->mergeFields['Blog_Column_Heading_Text'] = $this->phrase($this->setting('blog_column_heading_text'));

						if ($result['Record_count_this_page']) {
							$this->mergeFields['Search_Result_Rows'] = true;
							$this->mergeFields['Blog_Search_Results'] = $result['search_results'];
							
							if ($result['Record_count_total'] > $maxResultsShowable) {
								$additionalResultsCount = $result['Record_count_total'] - $maxResultsShowable;
								$this->mergeFields['Blog_And_X_More_Results_Phrase'] = $this->nPhrase('and 1 more blog post', 'and [[count]] more blog posts', $additionalResultsCount);
								$showPressEnter = true;
							}
						} else {
							$this->mergeFields['Blog_Search_No_Results'] = true;

							if ($this->setting('blog_show_message_if_no_results') && $this->setting('blog_no_results_text')) {
								$this->mergeFields['Blog_No_Results_Text'] = $this->phrase($this->setting('blog_no_results_text'));
							}
						}

						break;
					case 'project':
						$this->mergeFields['Project_Column_Heading_Text'] = $this->phrase($this->setting('project_column_heading_text'));

						if ($result['Record_count_this_page']) {
							$this->mergeFields['Search_Result_Rows'] = true;
							$this->mergeFields['Project_Search_Results'] = $result['search_results'];
							
							if ($result['Record_count_total'] > $maxResultsShowable) {
								$additionalResultsCount = $result['Record_count_total'] - $maxResultsShowable;
								$this->mergeFields['Project_And_X_More_Results_Phrase'] = $this->nPhrase('and 1 more project', 'and [[count]] more projects', $additionalResultsCount);
								$showPressEnter = true;
							}
						} else {
							$this->mergeFields['Project_Search_No_Results'] = true;

							if ($this->setting('project_show_message_if_no_results') && $this->setting('project_no_results_text')) {
								$this->mergeFields['Project_No_Results_Text'] = $this->phrase($this->setting('project_no_results_text'));
							}
						}

						break;
				}

				if (ze::in($type, 'document', 'news', 'html', 'blog', 'project')) {
					$this->mergeFields['Record_count_total'] += $result['Record_count_total'];
				}

				if ($result['Record_count_this_page']) {
					
					if ($result['pagination']) {
						$this->mergeFields['Search_Pagination'] = '';

						if ($this->mergeFields['Mode'] == 'search_page') {
							$this->pagination(
								$this->page, $result['pagination'],
								$this->mergeFields['Search_Pagination']);
						}
					}
				}
			}

			if ($showPressEnter) $this->mergeFields['Press_enter_to_see_all_results_phrase'] = $this->phrase('Press Enter to see all results');

			if ($this->setting('search_in_other_modules') && !empty($this->mergeFields['Results_From_Module'])) {
				$this->mergeFields['Search_Result_Rows'] = true;
				$this->mergeFields['Record_count_total'] += $this->mergeFields['Search_Result_Tab']['Results_From_Module']['Record_count_this_page'];
			}
		}

		if (ze::in($this->mergeFields['Mode'], 'search_entry_box', 'search_entry_box_show_always')) {
			$columnsCount = 0;
			foreach (['html', 'document', 'news', 'blog', 'project'] as $contentType) {
				if ($this->setting('search_' . $contentType)) {
					$columnsCount++;
				}
			}

			$this->mergeFields['Column_Count'] = (int) $columnsCount;
		}
		
		if (ze::in($this->mergeFields['Mode'], 'search_entry_box', 'search_entry_box_show_always') && $this->setting('search_label')) {
			$this->mergeFields['Search_Label'] = true;
		}
		
		if ($this->setting('search_placeholder')) {
			$this->mergeFields['Placeholder'] = true;
			$this->mergeFields['Placeholder_Phrase'] = $this->setting('search_placeholder_phrase');
		}

		$this->mergeFields['Show_Clear_Search_Button'] = $this->mergeFields['Mode'] == 'search_page' && $this->setting('show_clear_search_button');

		$this->mergeFields['Show_scores'] = ($this->setting('show_scores') && ze\admin::id());
		$this->mergeFields['Open_links_to_results_in_a_new_window'] = $this->setting('open_links_to_results_in_a_new_window');
		
		$this->twigFramework($this->mergeFields);
	}
	

	private function getSearchRequestParameters(){
		$request = '';
		if ($this->language_id ) $request .= '&language_id=' . $this->language_id;
		return $request;
	}

	function doSearch($mode) {
// 		$debugFilePath = ze\cache::createDir('advanced_search', 'private/debug');
// 		$debugFile = fopen($debugFilePath . '/queries.txt', 'ab');
// 		$text = "\n\nStarting search, " . ze\date::formatDateTime(ze\date::now()) . " with mode $mode\n";
// 		fwrite($debugFile, $text);
		
		$this->setSearchFields();
		$this->results['Search_String'] = htmlspecialchars($this->searchString);
		
		//Launch a search on each Content Type in turn
		$this->results = [];

		$this->releaseDateSetting = ze\row::getAssocs('content_types', 'release_date_field', ['content_type_id' => ['html', 'document', 'news', 'blog', 'project']]);

		$contentPluginSettings = [];
		foreach($this->fields as $cType => $fields) {
// 			$this->results[$cType] = $this->searchContent($cType, $fields, $mode, $debugFile);
			$this->results[$cType] = $this->searchContent($cType, $fields, $mode);

			$contentPluginSettings[$cType] = [
				'show_menu_path_if_available' => $this->setting($cType . '_show_menu_path_if_available'),
				'show_language' => $this->setting($cType . '_show_language'),
				'show_featured_image' => $this->setting($cType . '_show_featured_image'),
				'canvas' => $this->setting($cType . '_canvas'),
				'width' => $this->setting($cType . '_width'),
				'height' => $this->setting($cType . '_height'),
				'show_summary' => $this->setting($cType . '_show_summary')
			];
			
			$contentPluginSettings[$cType]['retina'] = $contentPluginSettings[$cType]['show_featured_image']
				&& (($contentPluginSettings[$cType]['canvas'] == 'unlimited' && $this->setting($cType . '_retina'))
					|| $contentPluginSettings[$cType]['canvas'] != 'unlimited');
		}
		
		//In "Full page search and results" mode, display tabs with searches from one content type for each
		if ($mode == 'search_page') {
			$contentTypes = [$this->cTypeToSearch];
		//In "Inline search" mode, there may be more than one content type at a time.
		} elseif (ze::in($mode, 'search_entry_box', 'search_entry_box_show_always')) {
			$contentTypes = [];
			if ($this->setting('search_html')) $contentTypes[] = 'html';
			if ($this->setting('search_document')) $contentTypes[] = 'document';
			if ($this->setting('search_news')) $contentTypes[] = 'news';
			if ($this->setting('search_blog')) $contentTypes[] = 'blog';
			if ($this->setting('search_project')) $contentTypes[] = 'project';
		}

		foreach ($contentTypes as $contentType) {

			if (ze::in($contentType, 'html', 'document', 'news', 'blog', 'project')) {
				$results = &$this->results[$contentType];
				if ($results['Record_count_this_page'] && $results['search_results']) {
					foreach($results['search_results'] as $i => &$result) {
					
						if ($this->setting('limit_num_of_chars_in_title') && ($charLimit = $this->setting('title_char_limit_value'))) {
							self::applyCharacterLimit($charLimit, $result['title']);
						}
						
						$result['Result_No'] 	= $results['offset'] + $i;
						$result['title']		= htmlspecialchars($result['title']);
						$result['language_id']	= htmlspecialchars($result['language_id']);

						//Language name
						$result['language_name'] = false;
						if ($contentPluginSettings[$result['type']]['show_language']) {
							$result['language_name'] = '<span>('.htmlspecialchars(ze\lang::name($result['language_id'], false)).')</span>';
						}

						$result['score']	= htmlspecialchars($result['score']);

						if ($contentPluginSettings[$result['type']]['show_summary']) {
							$result['content_bodymain'] = strip_tags($result['content_summary_short']);
							
							if ($this->setting('limit_num_of_chars_in_summary') && ($charLimit = $this->setting('summary_char_limit_value'))) {
								self::applyCharacterLimit($charLimit, $result['content_bodymain']);
							}
						}
						
						$requests = '';
						$result['url'] = htmlspecialchars($this->linkToItem($result['id'], $result['type'], false, $requests, $result['alias']));
						
						//Menu path
						if ($contentPluginSettings[$result['type']]['show_menu_path_if_available']) {
							$menu_item = ze\menu::getFromContentItem($result['id'], $result['type']);
							if ($menu_item) {
								$homePagecID = $homePagecType = false;
								ze\content::langSpecialPage('zenario_home', $homePagecID, $homePagecType, ze\content::visitorLangId(), true);

								$breadcrumbs = [];
								$breadcrumbs[] = $menu_item['name'];
								while ($menu_item && $menu_item['parent_id']) {
									$menu_item = ze\menu::details($menu_item['parent_id'], ze::$visLang);
									if ($menu_item ) {
										$breadcrumbs[] = $menu_item['name'];
									}
								}

								if ($homePagecID && $homePagecType) {
									$breadcrumbs[] = $this->phrase('Home');
								}
								krsort($breadcrumbs);
								$result['Breadcrumb'] = implode(' &raquo; ', $breadcrumbs);
							}
						}
						
						$img_tag = '';
						if ($contentPluginSettings[$result['type']]['show_featured_image']) {
							$url_img = '';
							$width = (int)$contentPluginSettings[$result['type']]['width'];
							$height = (int)$contentPluginSettings[$result['type']]['height'];
								
							ze\image::link(
								$width, $height, $url_img, $result['feature_image_id'],
								$width, $height, $contentPluginSettings[$result['type']]['canvas'], $offset = 0, $contentPluginSettings[$result['type']]['retina']
							);
							
							if ($url_img) {
								$img_tag = '<img src="' . $url_img . '" style="width: '. $width. 'px; height: '. $height. 'px;"';
								
								if ($contentPluginSettings[$result['type']]['retina']) {
									$srcset = $url_img . ' 2x';
								} else {
									$srcset = $url_img;
								}
								
								$img_tag .= ' srcset="' . $srcset . '"';
								
								if (ze::isAdmin()) {
									$img_tag .= ' class="zenario_image_properties zenario_image_id__'. $result['feature_image_id']. '__ zenario_image_num__'. ($imageLinkNum = 1). '__';
									
									if ($contentPluginSettings[$result['type']]['canvas'] == 'crop_and_zoom') {
										$img_tag .= ' zenario_crop_properties';
									}
									
									$img_tag .= '"';
								}
								
								$img_tag .= ' />';
							}
						}
						
						if ($img_tag) {
							$result['Featured_image_HTML_tag'] = $img_tag;
						} else {
							$this->getStyledExtensionIcon(pathinfo($result['filename'], PATHINFO_EXTENSION), $result);
						}
					}
				}
			}
			
			$this->mergeFields['Search_Result_Tab'] = [];
			foreach($this->fields as $cType => $fields) {
				$results = &$this->results[$cType];
				$this->mergeFields['Search_Result_Tab'][$cType] = [
					'Tab_On' => $results['Tab_On'],
					'Tab_Onclick' => $results['Tab_Onclick'],
					'Type' => $this->phrase($this->setting($cType . '_column_heading_text')),
					'Record_count_total' => $results['Record_count_total']
				];
			}
		}

		if ($this->setting('search_in_other_modules')) {
			$moduleToSearch = $this->setting('module_to_search');
			$this->mergeFields['Search_Result_Tab']['Results_From_Module'] = [];
			if (ze\module::inc($moduleToSearch)) {
				$usePagination = $this->setting('use_pagination');
				$pageSize = (int) $this->setting('maximum_results_number') ?: 999999;
				if ($this->page == 1) {
					$record_number = 1;
				} else {
					$record_number = (($this->page - 1) * $pageSize) + 1;
				}

				$showImage = $this->setting('other_module_show_image');
				$otherModuleCanvas = $this->setting('canvas');
				$otherModuleWidth = (int) $this->setting('width');
				$otherModuleHeight = (int) $this->setting('height');
				$contentItem = $this->setting('other_module_view_item_content_item');
				$conductorState = $this->setting('other_module_view_item_conductor_state');
				$pagination = [];

				/* Spec for searching other modules:
					1) There are 6 function parameters that the function definition needs to use: ($searchString, $searchableDataType, $weightings, $usePagination = false, $page = 0, $pageSize = 999999)
					2) The target module will need its own logic for returning results, including a DB key if appropriate
					3) Expecting the module's function to return an array:
						[
							'Record_count_total' => $recordCount,
							'Results' => $resultsFromModule,
							'Variable_name' => '(the module's variable id, e.g. id, locationId, etc)',
							'No_results_text' => '(the module's text when nothing was found. It needs to be plain text and will be passed through $this->phrase())',
							(optional) 'Additional_variables' => [
								'(additionalPropertyName)' => '(the name of the column where the value will be for each item)'
							]
						]
					4) Additional_variables is a sub-array, and may contain multiple variables. Each additional variable should have a name as it appears in the URL, and the item column name where the value will be.
						Example:
						[
							'Record_count_total' => 1,
							'Results' => $resultsFromModule,
							'Variable_name' => 'abstractId',
							'No_results_text' => 'No abstracts found',
							'Additional_variables' => [
								'conferenceId' => 'conference_id'
							]
						]
						
						For the above, this module will add '&conferenceId=', followed by the value of the conference_id property of each item.
						If an item has no value for that variable, then the whole string for this variable will be omitted entirely.
						At the moment, only Conference Manager uses this to search in abstracts.
					5) Expecting the following properties for each result: item_id, title, thumbnail_Id, filename, score
					6) There may be additional properties:
						short_description, date - only Videos Manager returns these
					7) To view results from another module, Advanced Search uses a content item picker and a conductor state to generate "View" links.
				*/
				
				/* Current list of searchable modules:
					Location Manager
					Videos Manager
					Ecommerce Physical Products
					Ecommerce Document Products
					Conference Manager
				*/
				
				$weights = [
					'_NONE'		=> 0,
					'_LOW'		=> 1,
					'_MEDIUM'	=> 3,
					'_HIGH'		=> 12
				];

				//Get the weights values
				$weightingsForModule = ['title' =>  $weights[$this->setting('other_module_title_weighting')], 'description' =>  $weights[$this->setting('other_module_description_weighting')]];

				$resultsFromModule = $moduleToSearch::searchFromModule($this->searchString, $searchableDataType = $this->setting('searchable_data_type'), $weightingsForModule, $usePagination, $this->page, $pageSize);
				$countResultsFromModule = $resultsFromModule['Record_count_total'];
				$resultPerPageCount = 0;
				if ($countResultsFromModule > 0) {
					$this->mergeFields['Search_Result_Rows'] = true;
					foreach ($resultsFromModule['Results'] as &$resultFromModule) {
						$resultPerPageCount++;
						
						$resultFromModule['Result_No'] = $record_number;
						$record_number++;

						$img_tag = '';
						if ($showImage) {
							$url_img = '';
							$width = (int) $otherModuleWidth;
							$height = (int) $otherModuleHeight;
								
							$otherModuleRetina = 
								($otherModuleCanvas == 'unlimited' && $this->setting('retina'))
								|| $otherModuleCanvas != 'unlimited';
							
							if (!empty($resultFromModule['thumbnail_Id'])) {
								ze\image::link($width, $height, $url_img, $resultFromModule['thumbnail_Id'], $otherModuleWidth, $otherModuleHeight, $otherModuleCanvas, $offset = 0, $otherModuleRetina);
							}
							
							if ($url_img) {
								$img_tag = '<img src="' . $url_img . '" style="width: '. $width. 'px; height: '. $height. 'px;"';
								
								if ($otherModuleRetina) {
									$srcset = $url_img . ' 2x';
								} else {
									$srcset = $url_img;
								}
								
								$img_tag .= ' srcset="' . $srcset . '"';
								
								if (ze::isAdmin()) {
									$img_tag .= ' class="zenario_image_properties zenario_image_id__'. $resultFromModule['thumbnail_Id']. '__ zenario_image_num__'. ($imageLinkNum = 1). '__';
									
									if ($otherModuleCanvas == 'crop_and_zoom') {
										$img_tag .= ' zenario_crop_properties';
									}
									
									$img_tag .= '"';
								}
								
								$img_tag .= ' />';
							}
						}
						
						if ($img_tag) {
							$resultFromModule['Featured_image_HTML_tag'] = $img_tag;
						} else {
							if (empty($resultFromModule['filename'])) {
								$resultFromModule['filename'] = '';
							}
							$this->getStyledExtensionIcon(pathinfo(($resultFromModule['filename'] ?: ''), PATHINFO_EXTENSION), $resultFromModule);
						}

						if ($contentItem && $conductorState) {
							$cID = $cType = false;
							ze\content::getCIDAndCTypeFromTagId($cID, $cType, $contentItem);
							
							//Build a list of variables for the item URL
							$variables = '';
							$variables .= htmlspecialchars($resultsFromModule['Variable_name'])  . '=' . (int)$resultFromModule['item_id'];
							
							if (!empty($resultsFromModule['Additional_variables'])) {
								foreach ($resultsFromModule['Additional_variables'] as $variableName => $variableValue) {
									if (!empty($resultFromModule[$variableValue])) {
										$variables .= '&' . htmlspecialchars($variableName) . '=' . htmlspecialchars($resultFromModule[$variableValue]);
									}
								}
							}
							
							$variables .= '&state=' . htmlspecialchars($conductorState);
							
							$resultFromModule['url'] = ze\link::toItem($cID, $cType, true, $variables);
						}
						
						if ($this->setting('limit_num_of_chars_in_summary') && ($charLimit = $this->setting('summary_char_limit_value')) && !empty($resultFromModule['short_description'])) {
							self::applyCharacterLimit($charLimit, $resultFromModule['short_description']);
						}
					}

					if ('results_from_module' == $this->cTypeToSearch) {
						if ($usePagination) {
							$numberOfPages = ceil($countResultsFromModule/$pageSize);
							
							for ($i=1;$i<=$numberOfPages;$i++) {
								$pagination[$i] = '&page='. $i. '&ctab=results_from_module' . $this->getSearchRequestParameters() . '&searchString=' . rawurlencode($this->searchString);
							}
						
						} else {
							$pagination = [];
						}

						if ($this->mergeFields['Mode'] == 'search_page') {
							$this->mergeFields['Search_Pagination'] = '';
							$this->pagination(
								$this->page, $pagination,
								$this->mergeFields['Search_Pagination']);
						}
					}
					
					if ('results_from_module' == $this->cTypeToSearch || ze::in($mode, 'search_entry_box', 'search_entry_box_show_always')) {
						$this->mergeFields['Results_From_Module'] = $resultsFromModule['Results'];
					}
				}

				if ($countResultsFromModule > 0 || $this->mergeFields['Mode'] == 'search_page') {
					$resultsFromModulePhrase = $this->phrase($this->setting('other_module_column_heading_text') ?: 'Other results');
					$this->mergeFields['Search_Result_Tab']['Results_From_Module'] = [
						'Type' => $resultsFromModulePhrase,
						'Record_count_total' => $countResultsFromModule,
						'Record_count_this_page' => $resultPerPageCount,
						"pagination" => $pagination,
						"Tab_On" => 'results_from_module' == $this->cTypeToSearch ? '_on' : null,
						"Tab_Onclick" => $this->refreshPluginSlotAnchor('&ctab='. rawurlencode('results_from_module'). $this->getSearchRequestParameters() . '&searchString='. rawurlencode($this->searchString))
					];
					
					$maxResultsShowable = $this->setting('maximum_results_number');
					$this->mergeFields['Module_Search_Results_Count'] = $countResultsFromModule;
					
					if ($countResultsFromModule > $maxResultsShowable) {
						$additionalResultsCount = $countResultsFromModule - $maxResultsShowable;
						$this->mergeFields['Module_And_X_More_Results_Phrase'] = $this->nPhrase('and 1 more result', 'and [[count]] more results', $additionalResultsCount, ['count' => $additionalResultsCount]);
					}

					$this->mergeFields['Results_From_Module_Heading_Text'] = $resultsFromModulePhrase;
					
					if ($this->mergeFields['Mode'] == 'search_page' && 'results_from_module' == $this->cTypeToSearch && !$countResultsFromModule) {
						$this->mergeFields['Results_From_Module_No_Results'] = true;
						$this->mergeFields['Results_From_Module_No_Results_Text'] = $this->phrase($resultsFromModule['No_results_text']);
					}
				}
			}
		}
		
// 		$text = "\nDone with doSearch().\n";
// 		fwrite($debugFile, $text);
// 		fclose($debugFile);
	}
	
	protected function drawSearchBox($cID = false, $cType = false) {
		
		$this->mergeFields['Search_Box'] = true;

		$this->mergeFields['Use_specific_search_results_page'] = $this->setting('use_specific_search_results_page');

		if (ze::in($this->setting('mode'), 'search_entry_box', 'search_entry_box_show_always') && $this->mergeFields['Use_specific_search_results_page']) {
			$cID = $cType = $state = false;
			$this->getCIDAndCTypeFromSetting($cID, $cType, 'specific_search_results_page');

			$this->mergeFields['Open_Form'] = '<form type="post" action="' . ze\link::toItem($cID, $cType) . '">';
		} else {
			$this->mergeFields['Open_Form'] = $this->openForm('return false;', '', false, false, true, $usePost = true). $this->remember('ctab');
		}

		if (ze::in($this->setting('mode'), 'search_entry_box', 'search_entry_box_show_always') && $this->setting('show_further_search_page_link')) {
			$cID = $cType = $state = false;
			$this->getCIDAndCTypeFromSetting($cID, $cType, 'further_search_page_target');
			$this->mergeFields['Further_Search_Link'] = ze\link::toItem($cID, $cType);
			$this->mergeFields['Further_Search_Phrase'] = $this->phrase($this->setting('additional_search_page_text'));
		}

		$this->mergeFields['Close_Form'] = $this->closeForm();
		$this->mergeFields['Search_Field_ID'] = $this->containerId . '-search_input_box';
		$this->mergeFields['Search_String'] = htmlspecialchars($this->searchString);
		
		if ($this->mergeFields['Mode'] == 'search_page') {
			if ($this->setting('let_user_select_language') && $this->langCount > 1 && $this->language_id) {
				$this->mergeFields['Language_Value'] = htmlspecialchars($this->language_id);
			}
		}
	}
		
	protected function applyCharacterLimit($charLimit, &$string) {
		if (strlen($string) > $charLimit) {
			$wordsArray = preg_split('/\s+/', $string);
			if (is_array($wordsArray) && count($wordsArray) > 0) {
				$currentLength = 0;
				$words = [];
				foreach ($wordsArray as $word) {
					$currentLength += strlen($word);
					if ($currentLength <= $charLimit) {
						$words[] = $word;
					} else {
						$words[] = '...';
						break;
					}
				}

				$string = implode(' ', $words);
			}
		}
	}

	protected function searchContent($cType, $fields, $mode) {
	
		if (!is_array($fields)) {
			return false;
		}
		
		$isSearchPage = ($mode == 'search_page');
		$isActiveTab = ($cType == $this->cTypeToSearch);
		$showTabCountOnly = ($isSearchPage && !$isActiveTab);

// 		if ($debugFile) {
// 			$text = "\nshowTabCountOnly: " . $showTabCountOnly . "...\n";
// 			fwrite($debugFile, $text);
// 		}

		
		//Create a first temporary table for the search results.
		//This is for matching any of the search terms.
		//It will be dropped automatically by MySQL at the end of the search.
		$sessionId = session_id();
		
		$searchPrivateItems = $this->setting('search_private_items');
		if ($this->setting('show_private_content_item_link_control') == 1) {
			//Only show links to private content items to authorised visitors
			$hidePrivateItems = true;
		} else {
			//Show links to private content items to all visitors
			$hidePrivateItems = false;
		}
		
// 		if ($debugFile) {
// 			$text = "\nProcessing content type: " . $cType . "...\n";
// 			fwrite($debugFile, $text);
// 		}		
				
		//Step 1: Calculate the SQL needed for matching rows against the search terms.
		//Use the "flat table".
		$sqlScore = "";
		$sqlWhere = "";
		$scoreStatementFirstLine = $whereStatementFirstLine = true;
		
		if ($this->searchString) {
		
			$tempTableName1 = 'results_initial_' . $cType . "_" . $sessionId;
			$tempTableName1WithPrefix = DB_PREFIX . $tempTableName1;
				
			$tempTableS1ql = "
				CREATE TEMPORARY TABLE " . ze\escape::sql($tempTableName1WithPrefix) . " (
					`id` int unsigned NOT NULL,
					`type` varchar(20) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
					`title` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
					`content_summary_short` varchar(250) COLLATE utf8mb4_unicode_ci,
					`feature_image_id` int unsigned NOT NULL DEFAULT '0',
					`privacy` enum('public','logged_out','logged_in','group_members','in_smart_group','logged_in_not_in_smart_group','call_static_method','send_signal','with_role') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'public',
					`filename` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
					`file_id` int unsigned NOT NULL DEFAULT '0',
					`s3_file_id` int unsigned NOT NULL DEFAULT '0',
					`pinned` tinyint(1) NOT NULL DEFAULT '0',
					`alias` varchar(75) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
					`language_id` varchar(15) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
					`published_datetime` DATETIME,
					`release_date` DATETIME,
					`score` DECIMAL(8,2)
				)";
			
// 			if ($debugFile) {
// 				$text = "\nQuery 1: creating temp table 1\n";
// 				fwrite($debugFile, $text);
// 			}
			
			ze\sql::cacheFriendlyUpdate($tempTableS1ql);
	
			//Create a second temporary table for the aggregated search results.
			$tempTableName2 = 'results_aggregated_' . $cType . "_" . $sessionId;
			$tempTableName2WithPrefix = DB_PREFIX . $tempTableName2;
	
			$tempTable2Sql = "
				CREATE TEMPORARY TABLE " . ze\escape::sql($tempTableName2WithPrefix) . " LIKE " . ze\escape::sql($tempTableName1WithPrefix); 
			
// 			if ($debugFile) {
// 				$text = "\nQuery 2: creating temp table 2\n";
// 				fwrite($debugFile, $text);
// 			}
			
			ze\sql::cacheFriendlyUpdate($tempTable2Sql);

			//Get the search terms
			$searchTerms = ze\content::searchtermParts($this->searchString);
			
			$stopWords = array("a", "about", "all", "and", "are", "as", "at", "be", "but", "by", "can", "do", "each", "for", "from", "had", "have", "he", "his", "how", "i", "if", "in", "it", "many", "of", "on", "one", "or", "other", "out", "said", "she", "some", "that", "the", "their", "then", "there", "they", "this", "to", "up", "was", "we", "were", "what", "when", "which", "will", "with", "word", "you", "your");

			//Stop words will be removed from the text used for searching
			//Currently we only support English stopwords
			$searchTerms = ze\content::searchtermParts($this->searchString);
			$searchTermsWithoutStopWords = array_diff_key($searchTerms, array_flip($stopWords));

			$searchTermsAreAllStopWords = true;
			if (!empty($searchTermsWithoutStopWords) && count($searchTermsWithoutStopWords) > 0) {
				$searchTermsAreAllStopWords = false;
			}
			unset($searchTermsWithoutStopWords);

			if ($searchTerms && !$searchTermsAreAllStopWords && count($searchTerms) > 0) {
				$sqlScore = "(";

				foreach ($searchTerms as $searchTerm => $searchTermType) {
					
					$searchTermIsAStopWord = in_array($searchTerm, $stopWords);

					if (!$searchTermsAreAllStopWords && !$searchTermIsAStopWord) {
						if ($whereStatementFirstLine) {
							$sqlWhere .= "
								( ";
						} else {
							$sqlWhere .= "
								AND (";
						}
					} else {
						continue;
					}

					$whereStatementFirstLine = true;
					
					foreach ($fields as $field) {
						if ($field['weighting']) {
							if (!$scoreStatementFirstLine) {
								$sqlScore .= " + ";
							}
							$scoreStatementFirstLine = false;

							if ($sqlWhere && !$whereStatementFirstLine && !$searchTermIsAStopWord) {
								$sqlWhere .= " OR ";
							}

							$whereStatementFirstLine = false;
								
							if ($field['name'] == 'cc.filename') {
								if (!$searchTermsAreAllStopWords && !$searchTermIsAStopWord) {
									$sqlWhere .= "
										(". $field['name']. " LIKE '". ze\escape::sql($searchTerm). "%')";
								}

								$sqlScore .= "
									((". $field['name']. " LIKE '". ze\escape::sql($searchTerm). "%') OR  (" . $field['name']." RLIKE '[\-_ ]+" . ze\escape::sql($searchTerm) . "')) * ". $field['weighting'];
							} elseif ($field['name'] == 'cc.alias') {
								if (!$searchTermsAreAllStopWords && !$searchTermIsAStopWord) {
									$sqlWhere .= "
										(". $field['name']. " LIKE '". ze\escape::sql($searchTerm). "%' OR " . $field['name']." RLIKE '[\-_]+" . ze\escape::sql($searchTerm) . "')";
								}

								$sqlScore .= "
									((". $field['name']. " LIKE '". ze\escape::sql($searchTerm). "%') OR (" . $field['name']." RLIKE '[\-_]+" . ze\escape::sql($searchTerm) . "')) * ". $field['weighting'];
							} else {
								if (!$searchTermsAreAllStopWords && !$searchTermIsAStopWord) {
									$sqlWhere .= "
										MATCH (". $field['name']. ") AGAINST ('". ze\escape::sql($searchTerm) . "*' IN BOOLEAN MODE)";
								}

								$sqlScore .= "
									MATCH (". $field['name']. ") AGAINST ('". ze\escape::sql($searchTerm) . "*' IN BOOLEAN MODE) * ". $field['weighting'];
							}
						}
					}
					
					if (!$searchTermsAreAllStopWords && !$searchTermIsAStopWord) {
						$sqlWhere .= ")";
					}
				}
				
				$sqlScore .= "
					) AS score";
			} else {
				$sqlScore = "0";
			}
		} else {
			$sqlScore = "0";
		}
		
		$joinSQL = "";
		
		if ($this->langCount > 1) {
			$joinSQL .= "
				INNER JOIN " . DB_PREFIX . "languages l
					ON c.language_id = l.id";
		}

		//Here is the big WHERE clause, beginning here	
		if ($searchPrivateItems) {
			$sqlFrom = $this->sqlToSearchContentTable($hidePrivateItems, '', $joinSQL);
		} else {
			$sqlFrom = $this->sqlToSearchContentTable(true, 'public', $joinSQL);
		}
		
		$sql = $sqlFrom;
		
		if ($cType != '%all%') {
			$sql .= "
			  	AND cc.content_type = '". ze\escape::asciiInSQL($cType). "'";
		}
	
		//We may only have to select rows in the visitor's language
		if ($this->setting('let_user_select_language') && $this->langCount > 1 && $this->language_id) {
			$sql .= "
				AND c.language_id = '". ze\escape::asciiInSQL($this->language_id). "' ";
		}

		if ($sqlWhere) {
			$sql .= "
				AND (". $sqlWhere. ")";
			
		} else {
			$sql .= "
				AND false";
		}
			  
		$record_number = 1;
		$searchresults = false;
		$pagination = [];
		
		$result = ze\sql::select("SELECT COUNT(DISTINCT cc.content_id) " . $sql);
		
// 		if ($debugFile) {
// 			$text = "\n\nQuery 3: count records " . "SELECT COUNT(DISTINCT cc.content_id) SQL not shown\n";
// 			fwrite($debugFile, $text);
// 		}
		
		$row = ze\sql::fetchRow($result);
		$recordCount = $row[0];
		
		if ($recordCount > 0 && $cType == $this->cTypeToSearch) {
			
			if ($this->setting('use_pagination')) {
				$pageSize = (int) $this->setting('maximum_results_number') ?: 999;
				$numberOfPages = ceil($recordCount/$pageSize);
				
				for ($i=1;$i<=$numberOfPages;$i++) {
					$pagination[$i] = '&page='. $i. '&ctab='. rawurlencode($cType). $this->getSearchRequestParameters() . '&searchString='. rawurlencode($this->searchString);
				}
				
				if ($this->page == 1) {
					$record_number = 1;
				} else {
					$record_number = (($this->page - 1) * $pageSize) + 1;
				}
			
			} else {
				$pagination = false;
			}
		}
		
		if ($showTabCountOnly) {
			//If we are only showing a tab for counting, don't fill tables with data
			$tempTableInsertSql = "
				INSERT INTO " . ze\escape::sql($tempTableName1WithPrefix) . "
				(id, type, score)
				SELECT DISTINCT v.id, v.type, 
				" . $sqlScore. $sql;
		} else {
			//Otherwise do the proper query because we need the results
			$tempTableInsertSql = "
				INSERT INTO " . ze\escape::sql($tempTableName1WithPrefix) . "
				(id, type, title, content_summary_short, feature_image_id, privacy, filename, file_id, s3_file_id, pinned, alias, language_id, published_datetime, release_date, score)
				SELECT DISTINCT v.id, v.type, IFNULL(cc.title, '') AS title,
					LEFT(IFNULL(cc.content_summary, ''), 250),
					v.feature_image_id,
					tc.privacy, cc.filename, v.file_id, v.s3_file_id, v.pinned, cc.alias,
					IFNULL(c.language_id, '') AS language_id, v.published_datetime, v.release_date,
				" . $sqlScore. $sql;
		}
		
// 		if ($debugFile) {
// 			$text = "\nQuery 4: insert into temp table 1\n$tempTableInsertSql\n";
// 			fwrite($debugFile, $text);
// 		}
		
		ze\sql::cacheFriendlyUpdate($tempTableInsertSql);

		//Step 2: Check if there are exact matches for multi-word searches.
		//Still use the temporary flat table 1.
		$numResults = ze\row::count($tempTableName1);
		if ($numResults > 0) {
			if ($this->searchString && count($searchTerms) > 1) {
				if (!function_exists('mb_ereg_replace')
				|| !$fullSearchTerm = mb_ereg_replace('[^\w\s_\'"]', ' ', $this->searchString)) {
					//Fall back to traditional pattern matching if that fails
					$fullSearchTerm = preg_replace('/[^\w\s_\'"]/', ' ', $this->searchString);
				}
			
				//Limit the length of the search term to 250 chars and 20 words maximum
				if (strlen($fullSearchTerm) > 250) {
					$fullSearchTerm = substr($fullSearchTerm, 0, 250);
					$lastSpace = strrpos($fullSearchTerm, ' ');
					if ($lastSpace !== false) {
						$fullSearchTerm = substr($fullSearchTerm, 0, $lastSpace);
					}
				}
				$words = explode(' ', $fullSearchTerm);
				$fullSearchTerm = implode(' ', array_slice($words, 0, 20));

				$sqlScore = "(";
				$sqlWhere = "";

				$scoreStatementFirstLine = $whereStatementFirstLine = true;
				$sqlWhere .= "
					AND (";
				
				foreach ($fields as $field) {
					if ($field['name'] == 'cc.alias') {
						//Alias can never be a multi word phrase. Skip that field.
						continue;
					}
					
					if ($field['weighting']) {
						if (!$scoreStatementFirstLine) {
							$sqlScore .= " + ";
						}
						$scoreStatementFirstLine = false;

						if ($sqlWhere && !$whereStatementFirstLine) {
							$sqlWhere .= " OR ";
						}

						$whereStatementFirstLine = false;
							
						if ($field['name'] == 'cc.filename') {
							$sqlWhere .= "
								((". $field['name']. " LIKE '". ze\escape::sql($fullSearchTerm). "%') OR  (" . $field['name']." RLIKE '[\-_ ]+" . ze\escape::sql($fullSearchTerm) . "'))";
							$sqlScore .= "
								((". $field['name']. " LIKE '". ze\escape::sql($fullSearchTerm). "%') OR  (" . $field['name']." RLIKE '[\-_ ]+" . ze\escape::sql($fullSearchTerm) . "')) * ". $field['weighting'];
						} else {
							$sqlWhere .= "
								MATCH (". $field['name']. ") AGAINST ('\"" . ze\escape::sql($fullSearchTerm) . "\"' IN BOOLEAN MODE)";
							$sqlScore .= "
								MATCH (". $field['name']. ") AGAINST ('\"" . ze\escape::sql($fullSearchTerm) . "\"' IN BOOLEAN MODE) * ". $field['weighting'];
						}
					}
				}
				
				$sqlScore .= "
					) AS score";

				$sqlWhere .= ")";

				if ($cType != '%all%') {
					$sqlWhere .= "
					  AND cc.content_type = '". ze\escape::asciiInSQL($cType). "'";
				}

				if ($showTabCountOnly) {
					$exactPhraseMatchSql = "
						INSERT INTO " . ze\escape::sql($tempTableName1WithPrefix) . "
						(id, type, score)
						SELECT DISTINCT v.id, v.type,
						" . $sqlScore . $sqlFrom . $sqlWhere;
				} else {
					$exactPhraseMatchSql = "
						INSERT INTO " . ze\escape::sql($tempTableName1WithPrefix) . "
						(id, type, title, content_summary_short, feature_image_id, privacy, filename, file_id, s3_file_id, pinned, alias, language_id, published_datetime, release_date, score)
						SELECT DISTINCT v.id, v.type, IFNULL(cc.title, '') AS title,
							LEFT(IFNULL(cc.content_summary, ''), 250) AS content_summary_short,
							v.feature_image_id,
							tc.privacy, cc.filename, v.file_id, v.s3_file_id, v.pinned, cc.alias,
							IFNULL(c.language_id, '') AS language_id, v.published_datetime, v.release_date,
						" . $sqlScore . $sqlFrom . $sqlWhere;
				}
								
// 				if ($debugFile) {
// 					$text = "\nQuery 5: exact match multi-word search, insert into temp table 1\n" . $exactPhraseMatchSql . "\n";
// 					fwrite($debugFile, $text);
// 				}
				
				ze\sql::cacheFriendlyUpdate($exactPhraseMatchSql);
				
				//Now aggregate the scores by copying to temp table 2 and summing the scores
				$tempResult2 = "
					INSERT INTO " . ze\escape::sql($tempTableName2WithPrefix) . "
					SELECT id, type, title, content_summary_short, feature_image_id, privacy, filename, file_id, s3_file_id, pinned, alias, language_id, published_datetime, release_date, SUM(score)
					FROM " . ze\escape::sql($tempTableName1WithPrefix) . "
					GROUP BY id, type";

// 				if ($debugFile) {
// 					$text = "\n\nQuery 6: multi-word search, insert into temp table 2\n" . $tempResult2 . "\n";
// 					fwrite($debugFile, $text);
// 				}
				
				ze\sql::cacheFriendlyUpdate($tempResult2);
			} else {
				//If that's a single-word search, just copy the results into the aggregated table.
				$tempResult2 = "
					INSERT INTO " . ze\escape::sql($tempTableName2WithPrefix) . "
					SELECT *
					FROM " . ze\escape::sql($tempTableName1WithPrefix);

// 				if ($debugFile) {
// 					$text = "\nQuery 7: single-word search, copy temp table 1 to temp table 2 (SQL not shown)\n";
// 					fwrite($debugFile, $text);
// 				}
				
				ze\sql::cacheFriendlyUpdate($tempResult2);
			}
		}

		if (!$showTabCountOnly) {
			//Update scores based on other factors
			//But skip this if we are only running this to get the count of items for the tab
			if (isset($this->releaseDateSetting[$cType]) && ze::in($this->releaseDateSetting[$cType], 'mandatory', 'optional')) {
				$releaseDateSql = "
					UPDATE " . ze\escape::sql($tempTableName2WithPrefix) . "
					SET score = 
						CASE
							WHEN (COALESCE(release_date, published_datetime) IS NOT NULL AND DATEDIFF(NOW(), COALESCE(release_date, published_datetime)) < 30) THEN score * " . (float) $this->setting('content_published_in_the_last_30_days_weighting') . "
							WHEN (COALESCE(release_date, published_datetime) IS NOT NULL AND DATEDIFF(NOW(), COALESCE(release_date, published_datetime)) < 90) THEN score * " . (float) $this->setting('content_published_in_the_last_90_days_weighting') . "
							WHEN (COALESCE(release_date, published_datetime) IS NOT NULL AND DATEDIFF(NOW(), COALESCE(release_date, published_datetime)) < 365) THEN score * " . (float) $this->setting('content_published_in_the_last_365_days_weighting') . "
							ELSE score * " . (float) $this->setting('content_published_over_365_days_ago_weighting') . "
						END";
				
// 				if ($debugFile) {
// 					$text = "\nQuery 8: extra points for date, when release dates are in use (SQL not shown)\n";
// 					fwrite($debugFile, $text);
// 				}
				
				ze\sql::cacheFriendlyUpdate($releaseDateSql);
			} else {
				$releaseDateSql = "
					UPDATE " . ze\escape::sql($tempTableName2WithPrefix) . "
					SET score = 
						CASE
							WHEN DATEDIFF(NOW(), published_datetime) < 30 THEN score * " . (float) $this->setting('content_published_in_the_last_30_days_weighting') . "
							WHEN DATEDIFF(NOW(), published_datetime) < 90 THEN score * " . (float) $this->setting('content_published_in_the_last_90_days_weighting') . "
							WHEN DATEDIFF(NOW(), published_datetime) < 365 THEN score * " . (float) $this->setting('content_published_in_the_last_365_days_weighting') . "
							ELSE score * " . (float) $this->setting('content_published_over_365_days_ago_weighting') . "
						END";
				
// 				if ($debugFile) {
// 					$text = "\nQuery 9: extra points for date, when release dates are NOT in use (SQL not shown)\n";
// 					fwrite($debugFile, $text);
// 				}
				
				ze\sql::cacheFriendlyUpdate($releaseDateSql);
			}

			//Step 4: Add extra points to pinned content items. Use the weighting.
			$pinnedSql = "
				UPDATE " . ze\escape::sql($tempTableName2WithPrefix) . "
				SET score = 
					CASE
						WHEN (pinned = 1) THEN score * " . (float) $this->setting('pinned_content_item_weighting') . "
						ELSE score
					END";
			
// 			if ($debugFile) {
// 				$text = "\nQuery 10: Add extra points for pinned items (SQL not shown)\n";
// 				fwrite($debugFile, $text);
// 			}
			
			ze\sql::cacheFriendlyUpdate($pinnedSql);
		}

		//Step 5: Load the results and pass them to the framework.
		//Add fields to the query:

		//Count (used later)...
		$resultsCountSql = "
			SELECT COUNT(DISTINCT id, type)
			FROM " . ze\escape::sql($tempTableName2WithPrefix) . "";

		//... and actual columns
		$resultsSql = "
			SELECT DISTINCT id, type, title, content_summary_short, feature_image_id, privacy,
				filename, file_id, s3_file_id, pinned, alias, language_id,
				published_datetime, release_date, score
			FROM " . ze\escape::sql($tempTableName2WithPrefix) . "
			ORDER BY score DESC";

				
// 		foreach ($fields as $field) {
// 			if (!ze::in($field['name'], 'cc.content_item_text', 'cc.file_extract')) {
// 				$columnName = substr($field['name'], (strpos($field['name'], '.') + 1));
// 				
// 				if (ze::in($columnName, 'language_id', 'title', 'keywords', 'description', 'content_summary')) {
// 					//These columns use NULL as default. Make sure they are blank strings if needed
// 					//to better support the changes in PHP 8.1.
// 					$resultsSql .= ", IFNULL(" . $field['name'] . ", '') AS " . $columnName;
// 				} else {
// 					$resultsSql .= ", ". $field['name'];
// 				}
// 			}
// 		}

// 		//The $joinSQL variable was created earlier. Add the join to the temporary results table now.
// 		$joinSQL .= "
// 			INNER JOIN " . ze\escape::sql($tempTableName2WithPrefix) . " results
// 				ON results.id = v.id
// 				AND results.type = v.type";
		
// 		if ($searchPrivateItems) {
// 			$sqlFrom = $this->sqlToSearchContentTable($hidePrivateItems, '', $joinSQL);
// 		} else {
// 			$sqlFrom = $this->sqlToSearchContentTable(true, 'public', $joinSQL);
// 		}
// 		
// 		$sqlFrom .= "
// 			AND score > 0";

// 		$resultsCountSql .= $sqlFrom;
// 		$resultsSql .= $sqlFrom;

// 		$resultsSql .= "
// 			ORDER BY score DESC, ";
// 		}

		$pageSize = $this->setting('maximum_results_number') ?: 999;
		$resultsSql .= ze\sql::limit($this->page, $pageSize);

		//Get the count...
		$result = ze\sql::select($resultsCountSql);
		
// 		if ($debugFile) {
// 			$text = "\nQuery 11: select total count without pagination (SQL not shown)\n";
// 			fwrite($debugFile, $text);
// 		}
		
		$resultsCountWithoutLimit = ze\sql::fetchValue($result);

		//... and the rows.
		
// 		if ($debugFile) {
// 			$text = "\nQuery 12: select items, paying attention to pagination (SQL not shown)\n";
// 			fwrite($debugFile, $text);
// 		}
		
		$result = ze\sql::select($resultsSql);
		
		$recordCount = 0;
		while ($row = ze\sql::fetchAssoc($result)) {
			if (!$searchresults) {
				$searchresults = [];
			}
			
			$searchresults[] = $row;
			
			++$recordCount;
		}
		
		//Drop the temporary tables after use... PHP used to do it here explicitly,
		//but MySQL does this automatically.
		
		//Prepare the return data, and try to avoid sending back result data when the only
		//reason for calling the function is to get the result record count.
		$returnData = [
			"Record_count_total" => $resultsCountWithoutLimit,
			"Record_count_this_page" => $recordCount,
			"pagination" => $pagination,
			"offset" => $record_number,
			"Tab_Onclick" => $this->refreshPluginSlotAnchor('&ctab='. rawurlencode($cType). $this->getSearchRequestParameters() . '&searchString='. rawurlencode($this->searchString))
		];
		$returnData["search_results"] = ($isSearchPage && !$isActiveTab) ? null : $searchresults;
		$returnData["Tab_On"] = $isActiveTab ? '_on' : null;
		
		return $returnData;
	}
	
	public static function nestedPluginName($eggId, $instanceId, $moduleClassName) {
		
		switch (ze\plugin::setting('mode', $instanceId, $eggId)) {
			case 'search_entry_box':
			default:
				return ze\admin::phrase('Advanced search (click to show)');
			case 'search_entry_box_show_always':
				return ze\admin::phrase('Advanced search (always show)');
			case 'search_page':
				return ze\admin::phrase('Advanced search (full page search)');
		}
			
		return parent::nestedPluginName($eggId, $instanceId, $moduleClassName);
	}
}