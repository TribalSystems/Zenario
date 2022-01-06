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

class zenario_advanced_search extends ze\moduleBaseClass {
	
	protected $mergeFields = [];
	protected $styles = [];
	protected $fields;
	protected $cTypeToSearch = '%all%';
	protected $searchString;
	protected $results;
	
	protected $category00_id = 0;
	protected $category01_id = 0;
	protected $category02_id = 0;
	protected $language_id = '';
	protected $keywords = '';
	
	public function init() {
		if ($_REQUEST['clearSearch'] ?? false) {
			$_REQUEST['language_id'] = $_POST['language_id'] = '0';
			$_REQUEST['category00_id'] = $_POST['category00_id'] = '0';
			$_REQUEST['category01_id'] = $_POST['category01_id'] = '0';
			$_REQUEST['category02_id'] = $_POST['category02_id'] = '0';
			$_REQUEST['category02_id'] = $_POST['category02_id'] = '0';
			$_REQUEST['searchString'] = $_POST['searchString'] = '';
			$_REQUEST['ctab'] = $_POST['ctab'] = $defaultTab;
		}
		
		$defaultTab = $this->setting('search_html')? 'html' : ($this->setting('search_document')? 'document' : 'news');
		$this->cTypeToSearch = (($_REQUEST['ctab'] ?? false) ?: $defaultTab);
		//Catch the case where a hacker is trying to break the page
		if (!ze::in($this->cTypeToSearch, 'html', 'document', 'news')) {
			$this->cTypeToSearch = $defaultTab;
		}
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$this->mergeFields = [];
		$this->mergeFields = [];
		
		$this->mergeFields['Search_Results'] = true;
		
		$this->mergeFields['Delay'] = (int) $this->setting('keyboard_delay_before_submit');
		$mode = $this->setting('mode');
		
		$this->category00_id = (int)($_REQUEST['category00_id'] ?? false);
		if ($mode == 'search_page' && $this->category00_id) {
			$this->mergeFields['category00_id'] = $this->category00_id;
			if (count($this->getCategoryOptionsWithParentId($this->category00_id)) > 1) {
				$this->mergeFields['HasCategory01'] = true;
			}
		}
		
		$this->category01_id = (int)($_REQUEST['category01_id'] ?? false);
		if ($mode == 'search_page' && $this->category01_id) {
			$this->mergeFields['category01_id'] = $this->category01_id;
			if (count($this->getCategoryOptionsWithParentId($this->category01_id)) > 1) {
				$this->mergeFields['HasCategory02'] = true;
			}
		}
		
		$this->category02_id = (int)($_REQUEST['category02_id'] ?? false);
		if ($mode == 'search_page' && $this->category02_id) {
			$this->mergeFields['category02_id'] = $this->category02_id;
		}
		$this->language_id = $_REQUEST['language_id'] ?? false;
		$this->keywords = $_REQUEST['keywords'] ?? false;
		
		$this->category = false;
		if ($this->setting('enable_categories') && (int) ($_REQUEST['category'] ?? false)) {
			$this->category = $_REQUEST['category'] ?? false;
		}

		$this->searchString = '';
		$this->page = 0;
		if ($this->setting('mode') == 'search_page' || ($this->setting('mode') == 'search_entry_box' && $this->isAJAXReload())) {
			//Do not pre-populate the search term in "Search entry box" mode on page refresh.
			//However, once the user actually searches for something, carry on as normal.
			$this->searchString = substr($_REQUEST['searchString'] ?? '', 0, 100);
			$this->page = (int) ($_REQUEST['page'] ?? 1) ?: 1;
		}

		if ($this->setting('mode') == 'search_entry_box') {
			$this->styles[] = '#' . $this->containerId . '_search_results { display: flex; }';
			$this->styles[] = 'body.mobile #' . $this->containerId . '_search_results { display: block; }';
			
			foreach (['html', 'document', 'news'] as $contentType) {
				if ($this->setting('search_' . $contentType)) {
					$this->styles[] = '#' . $this->containerId . '_' . $contentType . '_results { width: ' . $this->setting($contentType . '_column_width') . '% }';
					$this->styles[] = 'body.mobile #' . $this->containerId . '_' . $contentType . '_results { width: 100% }';
				}
			}
		}

		if ($this->styles !== [] && $this->methodCallIs('refreshPlugin')) {
			$this->callScriptBeforeAJAXReload('zenario', 'addStyles', $this->containerId, implode("\n", $this->styles));
		}
		
		return true;
	}

	public function addToPageHead() {
		if ($this->styles !== []) {
			echo "\n", '<style type="text/css" id="', $this->containerId, '-styles">', "\n", implode("\n", $this->styles), "\n", '</style>';
		}
	}
	
	public function getCategoryOptionsWithParentId($parentId = 0){
		
		$sql = "SELECT c.id, IFNULL(vp.local_text, c.name) as name
				FROM " . DB_PREFIX . "categories
					AS c LEFT JOIN " . DB_PREFIX . "visitor_phrases 
					AS vp ON CONCAT('_CATEGORY_', c.id) = vp.code
				WHERE parent_id=" . (int)$parentId . " AND c.public = 1 ORDER BY 2";
		$result = ze\sql::select($sql);
			
		$options = [];
		$options[0] =  $this->phrase('-- All categories --');
			
		while($row = ze\sql::fetchAssoc($result)){
			$options[$row['id']] = $row['name'];
		}
		return $options;
	}
	
	public function getCategory00Options(){
		return $this->getCategoryOptionsWithParentId(0);
	}
	
	public function getCategory01Options(){
		if(!$this->category00_id) return [];
		return $this->getCategoryOptionsWithParentId($this->category00_id);
	}
	
	public function getCategory02Options(){
		if(!$this->category01_id) return [];
		return $this->getCategoryOptionsWithParentId($this->category01_id);
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
		$fields[] =	['name' => 'c.alias',				'weighting' => $weights[$this->setting('alias_weighting')]];
		$fields[] =	['name' => 'c.language_id',		'weighting' => 0];
		$fields[] =	['name' => 'v.title',				'weighting' => $weights[$this->setting('title_weighting')]];
		$fields[] =	['name' => 'v.keywords',			'weighting' => $weights[$this->setting('keywords_weighting')]];
		$fields[] =	['name' => 'v.description',		'weighting' => $weights[$this->setting('description_weighting')]];
		$fields[] =	['name' => 'v.filename',			'weighting' => $weights[$this->setting('filename_weighting')]];
		$fields[] =	['name' => 'v.content_summary',	'weighting' => $weights[$this->setting('content_summary_weighting')]];
		$fields[] =	['name' => 'v.feature_image_id',	'weighting' => 0];
		$fields[] =	['name' => 'cc.text',				'weighting' => $weights[$this->setting('content_weighting')]];
		$fields[] =	['name' => 'cc.extract',			'weighting' => $weights[$this->setting('extract_weighting')]];

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

		if ($this->mergeFields['Mode'] == 'search_entry_box') {
			if ($this->setting('search_html')) {
				$this->mergeFields['Html_Column_Width'] = $this->setting('html_column_width');
			}

			if ($this->setting('search_document')) {
				$this->mergeFields['Document_Column_Width'] = $this->setting('document_column_width');
			}

			if ($this->setting('search_news')) {
				$this->mergeFields['News_Column_Width'] = $this->setting('news_column_width');
			}
		}
		
		$this->drawSearchBox();
		
		if ($this->searchString) {
			$this->doSearch($this->mergeFields['Mode']);
			
			//If this is the "Search entry box" mode, display nothing when there are no results.
			//But in "Search page" mode, still display the results div with tabs showing 0 results.
			
			if ($this->mergeFields['Mode'] == 'search_entry_box') {
				$this->mergeFields['Search_Result_Rows'] = false;
			} else {
				$this->mergeFields['Search_Result_Rows'] = true;
			}
			foreach ($this->results as $type => &$result) {
				if ($this->mergeFields['Mode'] == 'search_page' && $type != $this->cTypeToSearch) {
					continue;
				}

				switch ($type) {
					case 'document':
						$this->mergeFields['Document_Column_Heading_Text'] = $this->phrase($this->setting('document_column_heading_text'));

						if ($result['Record_Count']) {
							$this->mergeFields['Search_Result_Rows'] = true;
							$this->mergeFields['Document_Search_Results'] = $result['search_results'];
						} else {
							$this->mergeFields['Document_Search_No_Results'] = true;

							if ($this->setting('document_show_message_if_no_results') && $this->setting('document_no_results_text')) {
								$this->mergeFields['Document_No_Results_Text'] = $this->phrase($this->setting('document_no_results_text'));
							}
						}
						
						break;
					case 'news':
						$this->mergeFields['News_Column_Heading_Text'] = $this->phrase($this->setting('news_column_heading_text'));

						if ($result['Record_Count']) {
							$this->mergeFields['Search_Result_Rows'] = true;
							$this->mergeFields['News_Search_Results'] = $result['search_results'];
						} else {
							$this->mergeFields['News_Search_No_Results'] = true;

							if ($this->setting('news_show_message_if_no_results') && $this->setting('news_no_results_text')) {
								$this->mergeFields['News_No_Results_Text'] = $this->phrase($this->setting('news_no_results_text'));
							}
						}
						break;
					default:
						$this->mergeFields['Html_Page_Column_Heading_Text'] = $this->phrase($this->setting('html_column_heading_text'));

						if ($result['Record_Count']) {
							$this->mergeFields['Search_Result_Rows'] = true;
							$this->mergeFields['Html_Page_Search_Results'] = $result['search_results'];
						} else {
							$this->mergeFields['Html_Page_Search_No_Results'] = true;

							if ($this->setting('html_show_message_if_no_results') && $this->setting('html_no_results_text')) {
								$this->mergeFields['Html_No_Results_Text'] = $this->phrase($this->setting('html_no_results_text'));
							}
						}
				}

				if ($result['Record_Count']) {
					
					if ($result['pagination']) {
						$this->mergeFields['Search_Pagination'] = '';

						if ($this->mergeFields['Mode'] == 'search_page') {
							$this->pagination(
								'pagination_style',
								$this->page, $result['pagination'],
								$this->mergeFields['Search_Pagination']);
						}
					}
				}
			}
		}

		if ($this->mergeFields['Mode'] == 'search_entry_box') {
			$columnsCount = 0;
			foreach (['html', 'document', 'news'] as $contentType) {
				if ($this->setting('search_' . $contentType)) {
					$columnsCount++;
				}
			}

			$this->mergeFields['Column_Count'] = (int) $columnsCount;
		}
		
		if ($this->mergeFields['Mode'] == 'search_entry_box' && $this->setting('search_label')) {
			$this->mergeFields['Search_Label'] = true;
		}
		
		if ($this->setting('search_placeholder')) {
			$this->mergeFields['Placeholder'] = true;
			$this->mergeFields['Placeholder_Phrase'] = $this->setting('search_placeholder_phrase');
		}

		$this->mergeFields['Show_Clear_Search_Button'] = $this->mergeFields['Mode'] == 'search_page' && $this->setting('show_clear_search_button');

		$this->mergeFields['Show_scores'] = ($this->setting('show_scores') && ze\admin::id());
		
		if ($this->mergeFields['Mode'] == 'search_page' && $this->setting('enable_categories')) {
			$this->mergeFields['HasCategory00'] = true;
		}
			
		$this->twigFramework($this->mergeFields);
	}
	

	private function getSearchRequestParameters(){
		$request = '';
		if($this->category00_id ) $request .= '&category00_id=' . $this->category00_id;
		if($this->category01_id ) $request .= '&category01_id=' . $this->category01_id;
		if($this->category02_id ) $request .= '&category02_id=' . $this->category02_id;
		if($this->language_id ) $request .= '&language_id=' . $this->language_id;
		return $request;
	}

	function doSearch($mode) {
		$this->setSearchFields();
		$this->results['Search_String'] = htmlspecialchars($this->searchString);
		
		//Launch a search on each Content Type in turn
		$this->results = [];

		$this->releaseDateSetting = ze\row::getAssocs('content_types', 'release_date_field', ['content_type_id' => ['html', 'document', 'news']]);

		$contentPluginSettings = [];
		foreach($this->fields as $cType => $fields) {
			$this->results[$cType] = $this->searchContent($cType, $fields);

			$contentPluginSettings[$cType] = [
				'show_menu_path_if_available' => $this->setting($cType . '_show_menu_path_if_available'),
				'show_language' => $this->setting($cType . '_show_language'),
				'show_feature_image' => $this->setting($cType . '_show_feature_image'),
				'feature_image_canvas' => $this->setting($cType . '_feature_image_canvas'),
				'feature_image_width' => $this->setting($cType . '_feature_image_width'),
				'feature_image_height' => $this->setting($cType . '_feature_image_height'),
				'show_summary' => $this->setting($cType . '_show_summary')
			];
		}
		
		//In "Search page" mode, we'll only be displaying the details on one content type at a time.
		if ($mode == 'search_page') {
			$contentTypes = [$this->cTypeToSearch];
		//In "Search entry box" mode though, it might be more than one content type at a time.
		} elseif ($mode == 'search_entry_box') {
			$contentTypes = [];
			if ($this->setting('search_html')) {
				$contentTypes[] = 'html';
			}

			if ($this->setting('search_document')) {
				$contentTypes[] = 'document';
			}

			if ($this->setting('search_news')) {
				$contentTypes[] = 'news';
			}
		}

		foreach ($contentTypes as $contentType) {
			$results = &$this->results[$contentType];
			if ($results['Record_Count'] && $results['search_results']) {
				foreach($results['search_results'] as $i => &$result) {

					if ($this->setting('limit_num_of_chars_in_title') && ($charLimit = $this->setting('title_char_limit_value'))) {
						self::applyCharacterLimit($charLimit, $result['title']);
					}
					
					$result['Result_No'] = $results['offset'] + $i;
					
					$result['title']		= htmlspecialchars($result['title']);
					$result['keywords']		= htmlspecialchars($result['keywords']);
					$result['description']	= htmlspecialchars($result['description']);
					$result['language_id']	= htmlspecialchars($result['language_id']);

					//Language name
					$result['language_name'] = false;
					if ($contentPluginSettings[$result['type']]['show_language']) {
						$result['language_name'] = '<span>('.htmlspecialchars(ze\lang::name($result['language_id'], false)).')</span>';
					}

					$result['score']	= htmlspecialchars($result['score']);

					if ($contentPluginSettings[$result['type']]['show_summary']) {
						$result['content_bodymain'] = strip_tags($result['content_summary']);
						
						if ($this->setting('limit_num_of_chars_in_summary') && ($charLimit = $this->setting('summary_char_limit_value'))) {
							self::applyCharacterLimit($charLimit, $result['content_bodymain']);
						}
					}
					
					$requests = '';
					if($result['type'] == 'document') {
						$requests = 'download=1';
					}
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
					if ($contentPluginSettings[$result['type']]['show_feature_image']) {
						$url_img = '';
						$width = (int)$contentPluginSettings[$result['type']]['feature_image_width'];
						$height = (int)$contentPluginSettings[$result['type']]['feature_image_height'];
							
						ze\file::imageLink($width, $height, $url_img, $result['feature_image_id'], $width, $height, $contentPluginSettings[$result['type']]['feature_image_canvas']);
						if ($url_img) {
							$img_tag =  '<img src="' . $url_img . '" style="width: '. $width. 'px; height: '. $height. 'px;" />';
						}
					}
					
					if ($img_tag) {
						$result['Feature_image_HTML_tag'] = $img_tag;
					} else {
						$this->getStyledExtensionIcon(pathinfo($result['filename'], PATHINFO_EXTENSION), $result);
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
						'Record_Count' => $results['Record_Count']
				];
			}
		}
	}
	
	protected function drawSearchBox($cID = false, $cType = false) {
		
		$this->mergeFields['Search_Box'] = true;

		if ($this->setting('mode') == 'search_entry_box' && $this->setting('use_specific_search_results_page')) {
			$cID = $cType = $state = false;
			$this->getCIDAndCTypeFromSetting($cID, $cType, 'specific_search_results_page');

			$this->mergeFields['Open_Form'] = '<form type="post" action="' . ze\link::toItem($cID, $cType) . '">';
		} else {
			$this->mergeFields['Open_Form'] = $this->openForm('', '', false, false, true, $usePost = true). $this->remember('ctab');
		}

		if ($this->setting('mode') == 'search_entry_box' && $this->setting('show_further_search_page_link')) {
			$cID = $cType = $state = false;
			$this->getCIDAndCTypeFromSetting($cID, $cType, 'further_search_page_target');
			$this->mergeFields['Further_Search_Link'] = ze\link::toItem($cID, $cType);
			$this->mergeFields['Further_Search_Phrase'] = $this->phrase($this->setting('additional_search_page_text'));
		}

		$this->mergeFields['Close_Form'] = $this->closeForm();
		$this->mergeFields['Search_Field_ID'] = $this->containerId . '_search_input_box';
		$this->mergeFields['Search_String'] = htmlspecialchars($this->searchString);
	}
	
	
	protected function getCategoriesSQLFilter() {
		$category = $this->category02_id ? $this->category02_id : (
				$this->category01_id ? $this->category01_id : $this->category00_id);
		if($category) {
			return " INNER JOIN " . DB_PREFIX . "category_item_link cil 
				   ON cil.equiv_id = c.equiv_id
				  AND cil.content_type = c.type
				  AND cil.category_id = ". $category;
		}
		return "";
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

	protected function searchContent($cType, $fields, $onlyShowFirstPage = false) {
	
		if (!is_array($fields)) {
			return false;
		}
		
		//Create a first temporary table for the search results. It will be dropped at the end of the search.
		$sessionId = session_id();
		$randomNumber = rand(1, 9999);
		
		$tempTableName1 = 'search_flat_table_' . $sessionId . "_" . $randomNumber;
		$tempTableName1WithPrefix = DB_PREFIX . $tempTableName1;

		$tempTableS1ql = "
			CREATE TEMPORARY TABLE " . ze\escape::sql($tempTableName1WithPrefix) . " (
				`id` INT(10) UNSIGNED,
				`type` VARCHAR(20),
				`published_datetime` DATETIME,
				`release_date` DATETIME,
				`pinned` TINYINT(1),
				`score` DECIMAL(8,2)
			)";
		
		ze\sql::update($tempTableS1ql);

		//Create a second temporary table for the search results. It will be dropped at the end of the search.
		$tempTableName2 = 'search_aggr_table_' . $sessionId . "_" . $randomNumber;
		$tempTableName2WithPrefix = DB_PREFIX . $tempTableName2;

		$tempTable2Sql = "
			CREATE TEMPORARY TABLE " . ze\escape::sql($tempTableName2WithPrefix) . " (
				`id` INT(10) UNSIGNED,
				`type` VARCHAR(20),
				`published_datetime` DATETIME,
				`release_date` DATETIME,
				`pinned` TINYINT(1),
				`score` DECIMAL(8,2)
			)";
		
		ze\sql::update($tempTable2Sql);
		
		//Add fields to the query
		$sqlFields = "
			SELECT v.id, v.type, v.published_datetime, v.release_date";
		
		$allowPinnedContent = ze\row::get('content_types', 'allow_pinned_content', ['content_type_id' => $cType]);
		if ($allowPinnedContent) {
			$sqlFields .= ', v.pinned';
		} else {
			$sqlFields .= ', 0 AS pinned';
		}
		
		//Step 1: Calculate the SQL needed for matching rows against the search terms.
		//Use the "flat table".
		$useCC = false;
		$sqlScore = "";
		$sqlWhere = "";
		$scoreStatementFirstLine = $whereStatementFirstLine = true;
		
		if ($this->searchString) {
			//Calculate the search terms
			$searchTerms = ze\content::searchtermParts($this->searchString);

			if ($searchTerms && count($searchTerms) > 0) {
				$sqlScore = ", (";

				foreach ($searchTerms as $searchTerm => $searchTermType) {
					
					$wildcard = "*";

					if ($whereStatementFirstLine) {
						$sqlWhere .= "
							( ";
					} else {
						$sqlWhere .= "
							AND (";
					}

					$whereStatementFirstLine = true;
					
					foreach ($fields as $field) {
						if ($field['weighting']) {
							if (substr($field['name'], 0, 3) == 'cc.') {
								$useCC = true;
							}
							
							if ($sqlWhere) {
								if (!$scoreStatementFirstLine) {
									$sqlScore .= " + ";
								}
								$scoreStatementFirstLine = false;

								if (!$whereStatementFirstLine) {
									$sqlWhere .= " OR ";
								}
							}

							$whereStatementFirstLine = false;
								
							if ($field['name'] == 'v.filename') {
								$sqlWhere .= "
									(". $field['name']. " LIKE '". ze\escape::sql($searchTerm). "%')";
								$sqlScore .= "
									((". $field['name']. " LIKE '". ze\escape::sql($searchTerm). "%') OR  (" . $field['name']." RLIKE '[\-_ ]+" . ze\escape::sql($searchTerm) . "')) * ". $field['weighting'];
							} elseif ($field['name'] == 'c.alias') {
								$sqlWhere .= "
									(". $field['name']. " LIKE '". ze\escape::sql($searchTerm). "%' OR " . $field['name']." RLIKE '[\-_]+" . ze\escape::sql($searchTerm) . "')";
								$sqlScore .= "
									((". $field['name']. " LIKE '". ze\escape::sql($searchTerm). "%') OR (" . $field['name']." RLIKE '[\-_]+" . ze\escape::sql($searchTerm) . "')) * ". $field['weighting'];
							} else {
								$sqlWhere .= "
									MATCH (". $field['name']. ") AGAINST ('". ze\escape::sql($searchTerm) . $wildcard. "' IN BOOLEAN MODE)";
								$sqlScore .= "
									MATCH (". $field['name']. ") AGAINST ('". ze\escape::sql($searchTerm) . $wildcard . "' IN BOOLEAN MODE) * ". $field['weighting'];
							}
						}
					}
					
					$sqlWhere .= ")";
				}
				
				$sqlScore .= "
					) AS score";
			} else {
				$sqlScore = ", 0";
			}
		} else {
			$sqlScore = ", 0";
		}

		//Check whether this content type allows pinned items.
		
		$joinSQL = "
			INNER JOIN " . DB_PREFIX . "languages l
				ON c.language_id = l.id ";
		
		if ($useCC) {
			$joinSQL .= "
				INNER JOIN ". DB_PREFIX. "content_cache AS cc
					ON cc.content_id = v.id
					AND cc.content_type = v.type
					AND cc.content_version = v.version";
		}
		
		$joinSQL .= $this->getCategoriesSQLFilter();
		
		
		if ($this->setting('show_private_items')) {
			$sqlFrom = ze\content::sqlToSearchContentTable($this->setting('hide_private_items'), '', $joinSQL);
		} else {
			$sqlFrom = ze\content::sqlToSearchContentTable(true, 'public', $joinSQL);
		}
		
		$sql = $sqlFrom;
		
		//Only select rows in the Visitor's language, and that match the search terms
		if ($this->setting('let_user_select_language') && $this->language_id) {
			$sql .= "
				AND c.language_id = '". ze\escape::asciiInSQL($this->language_id). "' ";
		}

		if ($sqlWhere) {
			$sql .= "
				AND (". $sqlWhere. ")";
		}
			  
		if ($cType != '%all%') {
			$sql .= "
			  AND v.type = '". ze\escape::asciiInSQL($cType). "'";
		}
	
		$record_number = 1;
		$searchresults = false;
		$pagination = [];
		
		
		if (!$onlyShowFirstPage) {
			$result = ze\sql::select("SELECT COUNT(*) ". $sql);
			$row = ze\sql::fetchRow($result);
			$recordCount = $row[0];
			
			if ($recordCount > 0 && $cType == $this->cTypeToSearch) {
				
				if ($this->setting('use_pagination')) {
					$pageSize = (int) $this->setting('maximum_results_number') ?:999999;
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
		
		} else {
			$recordCount = 0;
			$pagination = false;
			
			if ($cType == $this->cTypeToSearch) {

				$result = ze\sql::select($sqlFields. $sqlScore. $sql);
			}
		}

		$tempTableInsertSql = "
			INSERT INTO " . ze\escape::sql($tempTableName1WithPrefix) . "
			(id, type, published_datetime, release_date, pinned, score)
			" . $sqlFields. $sqlScore. $sql;
		ze\sql::update($tempTableInsertSql);

		$tempResult1 = "
			SELECT id, type, published_datetime, release_date, pinned, score
			FROM " . ze\escape::sql($tempTableName1WithPrefix);
		$result1 = ze\sql::select($tempResult1);
		$result1 = ze\sql::fetchAssocs($result1);
		
		//Debug code for testing
		// if (!empty($result1)) {
		// 	echo '<table>';
		// 		echo '<tr>
		// 			<th>Id</th>
		// 			<th>Type</th>
		// 			<th>Published date</th>
		// 			<th>Release date</th>
		// 			<th>Pinned</th>
		// 			<th>Score</th>
		// 			</tr>';
		// 		foreach ($result1 as $row) {
		// 			echo '<tr>';
		// 			foreach ($row as $rowKey => $rowValue) {
		// 				echo '<td>' . htmlspecialchars($rowValue) . '</td>';
		// 			}
		// 			echo '</tr>';
		// 		}
		// 	echo '</table>';
		// }

		//Step 2: Check if there are exact matches for multi-word searches.
		//Still use the "flat table".
		$numResults = ze\row::count($tempTableName1);
		if ($numResults > 0) {
			if ($this->searchString && count($searchTerms) > 1) {
				if (!function_exists('mb_ereg_replace')
				|| !$fullSearchTerm = mb_ereg_replace('[^\w\s_\'"]', ' ', $this->searchString)) {
					//Fall back to traditional pattern matching if that fails
					$fullSearchTerm = preg_replace('/[^\w\s_\'"]/', ' ', $this->searchString);
				}
			
				//Limit the search results to 100 chars
				$fullSearchTerm = substr($fullSearchTerm, 0, 100);

				$sqlScore = ", (";
				$sqlWhere = "";

				$scoreStatementFirstLine = $whereStatementFirstLine = true;
				$sqlWhere .= "
					AND (";
				
				foreach ($fields as $field) {
					if ($field['name'] == 'c.alias') {
						//Alias can never be a multi word phrase. Skip that field.
						continue;
					}
					
					if ($field['weighting']) {
						if (substr($field['name'], 0, 3) == 'cc.') {
							$useCC = true;
						}
						
						if ($sqlWhere) {
							if (!$scoreStatementFirstLine) {
								$sqlScore .= " + ";
							}
							$scoreStatementFirstLine = false;

							if (!$whereStatementFirstLine) {
								$sqlWhere .= " OR ";
							}
						}

						$whereStatementFirstLine = false;
							
						if ($field['name'] == 'v.filename') {
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
					  AND v.type = '". ze\escape::asciiInSQL($cType). "'";
				}

				$exactPhraseMatchSql = "
					INSERT INTO " . ze\escape::sql($tempTableName1WithPrefix) . "
					(id, type, published_datetime, release_date, pinned, score)
					" . $sqlFields . $sqlScore . $sqlFrom . $sqlWhere;
				
				ze\sql::update($exactPhraseMatchSql);

				//Debug code for testing
				// $tempResult2 = "
				// 	SELECT id, type, published_datetime, release_date, pinned, score
				// 	FROM " . ze\escape::sql($tempTableName1WithPrefix);

				// $result2 = ze\sql::select($tempResult2);
				// $result2 = ze\sql::fetchAssocs($result2);

				// if (!empty($result2)) {
				// 	echo '<table>';
				// 		echo '<tr>
				// 			<th>Id</th>
				// 			<th>Type</th>
				// 			<th>Published date</th>
				// 			<th>Release date</th>
				// 			<th>Pinned</th>
				// 			<th>Score</th>
				// 			</tr>';
				// 		foreach ($result2 as $row) {
				// 			echo '<tr>';
				// 			foreach ($row as $rowKey => $rowValue) {
				// 				echo '<td>' . htmlspecialchars($rowValue) . '</td>';
				// 			}
				// 			echo '</tr>';
				// 		}
				// 	echo '</table>';
				// }
				
				$tempResult2 = "
					INSERT INTO " . ze\escape::sql($tempTableName2WithPrefix) . "
					(id, type, published_datetime, release_date, pinned, score)
					SELECT id, type, published_datetime, release_date, pinned, SUM(score)
					FROM " . ze\escape::sql($tempTableName1WithPrefix) . "
					GROUP BY id, type";

				ze\sql::update($tempResult2);
			} else {
				//If that's a single-word search, just copy the results into the aggregated table.
				$tempResult2 = "
					INSERT INTO " . ze\escape::sql($tempTableName2WithPrefix) . "
					(id, type, published_datetime, release_date, pinned, score)
					SELECT id, type, published_datetime, release_date, pinned, score
					FROM " . ze\escape::sql($tempTableName1WithPrefix);

				ze\sql::update($tempResult2);
			}
		}

		//Step 3: Add extra points to more recent content items.
		if (isset($this->releaseDateSetting[$cType]) && ze::in($this->releaseDateSetting[$cType], 'mandatory', 'optional')) {
			$releaseDateSql = "
				UPDATE " . ze\escape::sql($tempTableName2WithPrefix) . "
				SET score = 
					CASE
						WHEN (release_date IS NOT NULL AND DATEDIFF(NOW(), release_date) < 30) THEN score * 10
						WHEN (release_date IS NOT NULL AND DATEDIFF(NOW(), release_date) < 90) THEN score * 6
						WHEN (release_date IS NOT NULL AND DATEDIFF(NOW(), release_date) < 365) THEN score * 3
						ELSE score
					END";
			ze\sql::update($releaseDateSql);
		} else {
			$releaseDateSql = "
				UPDATE " . ze\escape::sql($tempTableName2WithPrefix) . "
				SET score = 
					CASE
						WHEN DATEDIFF(NOW(), published_datetime) < 30 THEN score * 10
						WHEN DATEDIFF(NOW(), published_datetime) < 90 THEN score * 6
						WHEN DATEDIFF(NOW(), published_datetime) < 365 THEN score * 3
						ELSE score
					END";
			ze\sql::update($releaseDateSql);
		}

		$tempResult3 = "
			SELECT id, type, published_datetime, release_date, pinned, score
			FROM " . ze\escape::sql($tempTableName2WithPrefix);

		$result3 = ze\sql::select($tempResult3);
		$result3 = ze\sql::fetchAssocs($result3);

		//Debug code for testing
		// if (!empty($result3)) {
		// 	echo '<table>';
		// 		echo '<tr>
		// 			<th>Id</th>
		// 			<th>Type</th>
		// 			<th>Published date</th>
		// 			<th>Release date</th>
		// 			<th>Pinned</th>
		// 			<th>Score</th>
		// 			</tr>';
		// 		foreach ($result3 as $row) {
		// 			echo '<tr>';
		// 			foreach ($row as $rowKey => $rowValue) {
		// 				echo '<td>' . htmlspecialchars($rowValue) . '</td>';
		// 			}
		// 			echo '</tr>';
		// 		}
		// 	echo '</table>';
		// }

		//Step 4: Add extra points to pinned content items.
		$pinnedSql = "
			UPDATE " . ze\escape::sql($tempTableName2WithPrefix) . "
			SET score = 
				CASE
					WHEN (pinned = 1) THEN score * 10
					ELSE score
				END";
		ze\sql::update($pinnedSql);

		$tempResult4 = "
			SELECT id, type, published_datetime, release_date, pinned, score
			FROM " . ze\escape::sql($tempTableName2WithPrefix);

		$result4 = ze\sql::select($tempResult4);
		$result4 = ze\sql::fetchAssocs($result4);

		//Debug code for testing
		// if (!empty($result4)) {
		// 	echo '<table>';
		// 		echo '<tr>
		// 			<th>Id</th>
		// 			<th>Type</th>
		// 			<th>Published date</th>
		// 			<th>Release date</th>
		// 			<th>Pinned</th>
		// 			<th>Score</th>
		// 			</tr>';
		// 		foreach ($result4 as $row) {
		// 			echo '<tr>';
		// 			foreach ($row as $rowKey => $rowValue) {
		// 				echo '<td>' . htmlspecialchars($rowValue) . '</td>';
		// 			}
		// 			echo '</tr>';
		// 		}
		// 	echo '</table>';
		// }

		//Step 5: Load the results and pass them to the framework.
		//Add fields to the query
		$resultsSql = "
			SELECT v.id, v.type, score, tc.privacy";
		foreach ($fields as $field) {
			if (substr($field['name'], 0, 3) != 'cc.') {
				$resultsSql .= ", ". $field['name'];
			}
		}

		//The $joinSQL variable was created earlier. Add the join to the temporary results table now.
		$joinSQL .= "
			INNER JOIN " . ze\escape::sql($tempTableName2WithPrefix) . " results
				ON results.id = v.id
				AND results.type = v.type";
		
		if ($this->setting('show_private_items')) {
			$sqlFrom = ze\content::sqlToSearchContentTable($this->setting('hide_private_items'), '', $joinSQL);
		} else {
			$sqlFrom = ze\content::sqlToSearchContentTable(true, 'public', $joinSQL);
		}

		$resultsSql .= $sqlFrom;

		$resultsSql .= "
			ORDER BY ";

		if ($this->searchString) {
			$resultsSql .= "score DESC, ";
		}

		$resultsSql .= "c.id, c.type";

		$pageSize = $this->setting('maximum_results_number') ?:999999;
		if (!$onlyShowFirstPage) {
			$resultsSql .= ze\sql::limit($this->page, $pageSize);
		} else {
			$resultsSql .= "
				LIMIT ". (int)$pageSize;
		}

		$result = ze\sql::select($resultsSql);
		
		while ($row = ze\sql::fetchAssoc($result)) {
			if (!$searchresults) {
				$searchresults = [];
			}
			
			$searchresults[] = $row;
			
			if ($onlyShowFirstPage) {
				++$recordCount;
			}
		}
		
		//Drop the temporary tables after use.
		$dropTempTable1Sql = "DROP TEMPORARY TABLE " . ze\escape::sql($tempTableName1WithPrefix);
		ze\sql::update($dropTempTable1Sql);

		$dropTempTable2Sql = "DROP TEMPORARY TABLE " . ze\escape::sql($tempTableName2WithPrefix);
		ze\sql::update($dropTempTable2Sql);
		
		return [
			"Record_Count" => $recordCount,
			"search_results" => $searchresults,
			"pagination" => $pagination,
			"offset" => $record_number,
			"Tab_On" => $cType == $this->cTypeToSearch? '_on' :null,
			"Tab_Onclick" => $this->refreshPluginSlotAnchor('&ctab='. rawurlencode($cType). $this->getSearchRequestParameters() . '&searchString='. rawurlencode($this->searchString))
		];
	}
}
