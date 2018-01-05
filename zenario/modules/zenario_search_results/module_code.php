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

class zenario_search_results extends ze\moduleBaseClass {
	
	protected $mergeFields = [];
	protected $sections = [];
	protected $fields;
	protected $cTypeToSearch = '%all%';
	protected $searchString;
	protected $results;
	
	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$this->mergeFields = [];
		$this->sections = [];
		
		$this->category = false;
		if ($this->setting('enable_categories') && (int) ($_GET['category'] ?? false)) {
			$this->category = $_GET['category'] ?? false;
		}
		
		
		//$this->registerGetRequest('page', 1);
		//$this->registerGetRequest('category');
		//$this->registerGetRequest('searchString');
		
		$this->searchString = substr((string) ($_GET['searchString'] ?? false), 0, 100);
		$this->page = (int)(($_GET['page'] ?? false) ?: 1);
		
		return true;
	}
	
	protected function setSearchFields() {
		$weights = 
			[
				'_NONE'		=> 0,
				'_LOW'		=> 1,
				'_MEDIUM'	=> 3,
				'_HIGH'		=> 12];
		
		$fields = [];
		$fields[] =	array('name' => 'c.alias',				'weighting' => $weights[$this->setting('alias_weighting')]);
		$fields[] =	array('name' => 'v.title',				'weighting' => $weights[$this->setting('title_weighting')]);
		$fields[] =	array('name' => 'v.keywords',			'weighting' => $weights[$this->setting('keywords_weighting')]);
		$fields[] =	array('name' => 'v.description',		'weighting' => $weights[$this->setting('description_weighting')]);
		$fields[] =	array('name' => 'v.filename',			'weighting' => $weights[$this->setting('filename_weighting')]);
		$fields[] =	array('name' => 'v.content_summary',	'weighting' => $weights[$this->setting('content_summary_weighting')]);
		$fields[] =	array('name' => 'cc.text',				'weighting' => $weights[$this->setting('content_weighting')]);
		$fields[] =	array('name' => 'cc.extract',			'weighting' => $weights[$this->setting('extract_weighting')]);

		$this->fields = [];
		$this->fields['%all%'] = $fields;
	}
	
	/* $parentId:
		false - do not check parentage 
		0 - select top level categories
		n - select children of n
	*/
	protected function getUsedCategories($parentId = false) {
		$sql = "
			SELECT DISTINCT
				c.id,
				c.parent_id,
				c.name,
				c.public
			FROM " . DB_NAME_PREFIX . "categories AS c
			INNER JOIN " . DB_NAME_PREFIX . "category_item_link AS cil
				ON c.id = cil.category_id";

		if ($parentId!==false) {
			$sql .="
					WHERE 
						IFNULL(c.parent_id, 0)=" . (int) $parentId;
		}
		
		return ze\category::contentItemCategories($cID = false, $cType = false, $publicOnly = true, $langId = false, $sql);
	}
	
	protected function drawSubcategories($category, $fuse = 10) {
		$rv = "";
		if ($fuse) {
			$rv .= '<ul><li>
						<a class="search_category' . ($this->category  == $category['id']?'_on' :null) . ' ' .  
							$this->refreshPluginSlotAnchor('&category='. $category['id']. 
															'&ctab='. rawurlencode($this->cTypeToSearch). 
															'&searchString='. rawurlencode($this->searchString)) . 
							'> ' . htmlspecialchars($category['public_name']) . 
						'</a>';
			if ($childCategories = $this->getUsedCategories($category['id'])) {
				foreach ($childCategories as $childCategory) {
					$rv .=  $this->drawSubcategories($childCategory, $fuse - 1);
				}
			}
			$rv .= '</li></ul>';
		} 
		return $rv;
	}
	
	protected function drawCategories() {
		$cateoriesTree = "";
		if ($this->setting('enable_categories') && 
				($this->results) &&
					($this->sections['Search_Category'] = $this->getUsedCategories(0))) {
			foreach ($this->getUsedCategories(0) as $category) {
				$cateoriesTree .=  $this->drawSubcategories($category);
			}

			$this->sections['Search_Categories'] = true;
			$this->mergeFields['Search_Categories_Tree'] = $cateoriesTree;
			$this->mergeFields['All_Categories_Onclick'] = 
						$this->refreshPluginSlotAnchor(
							'&ctab='. rawurlencode($this->cTypeToSearch). '&searchString='. rawurlencode($this->searchString));
		}
	}
	
	
	public function showSlot() {
		
		if ($this->searchString) {
			$this->sections['Search_Result_Heading'] = true;
			$this->mergeFields['Search_Results_For'] = $this->phrase( '_SEARCH_RESULTS_FOR', array('term' => htmlspecialchars('"'. $this->searchString. '"')));
		}
		
		$this->drawSearchBox();
		
		if ($this->searchString) {
			$this->doSearch($this->searchString);
			
			switch ($this->cTypeToSearch) {
				case 'document':
					$Search_Result_Row = 'Search_Result_document_Row';
					$Search_No_Results = 'Search_No_Results_document';
					break;
				case 'news':
					$Search_Result_Row = 'Search_Result_news_Row';
					$Search_No_Results = 'Search_No_Results_news';
					break;
				default:
					$Search_Result_Row = 'Search_Result_Row';
					$Search_No_Results = 'Search_No_Results';
			}
			
			if ($this->results[$this->cTypeToSearch]['Record_Count']) {
				$this->sections['Search_Result_Rows'] = true;
				
				$this->sections[$Search_Result_Row] = $this->results[$this->cTypeToSearch]['search_results'];
				
				if ($this->results[$this->cTypeToSearch]['pagination']) {
					$this->pagination(
						'pagination_style',
						$this->page, $this->results[$this->cTypeToSearch]['pagination'],
						$this->mergeFields['Search_Pagination']);
				}
			} else {
				$this->sections[$Search_No_Results] = true;
			}
		}
		
		$this->drawCategories();
			
		$this->framework('Outer', $this->mergeFields, $this->sections);
	}
	



	function doSearch($searchTerm) {
		$this->setSearchFields();
		$this->results['Search_String'] = htmlspecialchars($this->searchString);
		
		//Launch a search on each Content Type in turn
		$this->results = [];
		foreach($this->fields as $cType => $fields) {
			$this->results[$cType] = $this->searchContent($cType, $fields);
		}
		
		//We'll only be displaying the details on one content type at a time
		//Sanitise the result set that we will be displaying, and prepare the url and other details
		if ($this->results[$this->cTypeToSearch]['Record_Count']) {
			foreach($this->results[$this->cTypeToSearch]['search_results'] as $i => &$result) {
				
				$result['Result_No'] = $this->results[$this->cTypeToSearch]['offset'] + $i;
				
				$result['title']		= htmlspecialchars($result['title']);
				$result['keywords']		= htmlspecialchars($result['keywords']);
				$result['description']	= htmlspecialchars($result['description']);
				
				if ($this->setting('data_field') == 'description') {
					$result['content_bodymain'] = $result['description'];
				} elseif ($this->setting('data_field') == 'content_summary') {
					$result['content_bodymain'] = $result['content_summary'];
				}
				
				$result['url'] = htmlspecialchars($this->linkToItem($result['id'], $result['type'], false, '', $result['alias']));
			}
		}
	}
	
	
	protected function drawSearchBox($cID = false, $cType = false) {
		
		$this->sections['Search_Box'] = true;
		$this->mergeFields['Search_Field_ID'] = 'search_field_'. $this->containerId;
		$this->mergeFields['Search_String'] = htmlspecialchars($this->searchString);
		
		
		if ($cID && $cType) {
			$this->mergeFields['Search_Submit'] = '';
		} else {
			$cID = $this->cID;
			$cType = $this->cType;
			$this->launchAJAXSearch();
		}
		
		$this->mergeFields['Search_Target'] = SUBDIRECTORY. DIRECTORY_INDEX_FILENAME;
		$this->mergeFields['Search_Page_Alias'] = $cID;
		$this->mergeFields['Search_Page_cType'] = $cType;
	}
	
	protected function launchAJAXSearch() {
		$this->mergeFields['Search_Submit'] =
			$this->moduleClassName. ".refreshPluginSlot('". $this->slotName. "', '&searchString=' + encodeURIComponent(ze::get('". $this->mergeFields['Search_Field_ID']. "').value)); return false;";
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	protected function getLanguagesSQLFilter(){
		return "
			AND c.language_id = '". ze\escape::sql(ze\content::currentLangId()). "' ";
	}
	
	
	protected function searchContent($cType, $fields, $onlyShowFirstPage = false) {
	
		if (!is_array($fields)) {
			return false;
		}
		
		
		//Add fields to the query
		$sqlF = "SELECT v.id, v.type";
		foreach ($fields as $field) {
			if (substr($field['name'], 0, 3) != 'cc.') {
				$sqlF .= ", ". $field['name'];
			}
		}
		
		//Calculate the search terms
		$searchTerms = ze\content::searchtermParts($this->searchString);
		
		
		//Calculate the SQL needed for matching rows against the search terms
		$useCC = false;
		$sqlR = ", (";
		$sqlW = "";
		foreach ($searchTerms as $searchTerm => $searchTermType) {
			
			$wildcard = "";
			$booleanSearch = "";
			if ($searchTermType == 'word') {
				$wildcard = "*";
				$searchTermTypeWeighting = 1;
			} else {
				$booleanSearch = " IN BOOLEAN MODE";
				$searchTermTypeWeighting = 2;
			}
			
			foreach ($fields as $field) {
				if ($field['weighting']) {
					if (substr($field['name'], 0, 3) == 'cc.') {
						$useCC = true;
					}
					
					if ($sqlW) {
						$sqlR .= " + ";
						$sqlW .= " OR ";
					}
						
					if ($field['name'] == 'c.alias' || $field['name'] == 'v.filename') {
						$sqlW .= "\n (". $field['name']. " LIKE '%". ze\escape::sql($searchTerm). "%')";
						$sqlR .= "\n (". $field['name']. " LIKE '%". ze\escape::sql($searchTerm). "%') * ". $searchTermTypeWeighting. " * ". $field['weighting'];
					} else {
						$sqlW .= "\n IF (l.search_type = 'simple', ". $field['name']. " LIKE '%". ze\escape::sql($this->searchString). "%', MATCH (". $field['name']. ") AGAINST ('". ze\escape::sql($searchTerm) .      $wildcard. "' IN BOOLEAN MODE) )";
						$sqlR .= "\n IF (l.search_type = 'simple', ". $field['name']. " LIKE '%". ze\escape::sql($this->searchString). "%', MATCH (". $field['name']. ") AGAINST ('". ze\escape::sql($searchTerm) . "'". $booleanSearch . ") ) * ". $searchTermTypeWeighting. " * ". $field['weighting'];
					}
				}
			}
		}
		
		$sqlR .= "
			) AS relevance";
		
		
		$joinSQL = "INNER JOIN 
						" . DB_NAME_PREFIX . "languages l
					ON
						c.language_id = l.id ";
		
		if ($useCC) {
			$joinSQL .= "
				INNER JOIN ". DB_NAME_PREFIX. "content_cache AS cc
				   ON cc.content_id = v.id
				  AND cc.content_type = v.type
				  AND cc.content_version = v.version";
		}
		
		if ($this->category) {
			$joinSQL .= "
			INNER JOIN " . DB_NAME_PREFIX . "category_item_link AS cil
			   ON cil.equiv_id = c.equiv_id
			  AND cil.content_type = c.type
			  AND cil.category_id = ". (int) $this->category;
		}
		
		
		if ($this->setting('show_private_items')) {
			$sql = ze\content::sqlToSearchContentTable($this->setting('hide_private_items'), '', $joinSQL);
		} else {
			$sql = ze\content::sqlToSearchContentTable(true, 'public', $joinSQL);
		}
		
		
		//Only select rows in the Visitor's language, and that match the search terms
		$sql .= $this->getLanguagesSQLFilter() . "
			  AND (". $sqlW. ")";
		
		if ($cType != '%all%') {
			$sql .= "
			  AND v.type = '". ze\escape::sql($cType). "'";
		}
	
		$record_number = 1;
		$searchresults = false;
		$pagination = [];
		
		
		if (!$onlyShowFirstPage) {
			$result = ze\sql::select("SELECT COUNT(*)". $sql);
			$row = ze\sql::fetchRow($result);
			$recordCount = $row[0];
			
			if ($recordCount > 0 && $cType == $this->cTypeToSearch) {
				
				$sql .= "
					ORDER BY relevance DESC, c.id, c.type";
				
				if ($this->setting('use_pagination')) {
					$pageSize = (int) $this->setting('page_size');
					$numberOfPages = ceil($recordCount/$pageSize);
					
					for ($i=1;$i<=$numberOfPages;$i++) {
						$pagination[$i] = '&page='. $i. '&ctab='. rawurlencode($cType). '&category='. (int) $this->category. '&searchString='. rawurlencode($this->searchString);
					}
					
					if ($this->page == 1) {
						$record_number = 1;
					} else {
						$record_number = (($this->page - 1) * $pageSize) + 1;
					}
					
					$sql .= ze\sql::limit($this->page, $pageSize);
				
				} else {
					$pagination = false;
				}
				
				$result = ze\sql::select($sqlF. $sqlR. $sql);
			
				while ($row = ze\sql::fetchAssoc($result)) {
					if (!$searchresults) {
						$searchresults = [];
					}
					
					$searchresults[] = $row;
				}
			}
		
		} else {
			$recordCount = 0;
			$pagination = false;
			
			if ($cType == $this->cTypeToSearch) {
				$sql .= "
					ORDER BY relevance DESC, c.id, c.type";
				
				if ($this->setting('page_size')) {
					$sql .= "
						LIMIT ". (int) $this->setting('page_size');
				}
				
				$result = ze\sql::select($sqlF. $sqlR. $sql);
			
				while ($row = ze\sql::fetchAssoc($result)) {
					if (!$searchresults) {
						$searchresults = [];
					}
					
					$searchresults[] = $row;
					++$recordCount;
				}
			}
		}
		
		return array(
			"Record_Count" => $recordCount,
			"search_results" => $searchresults,
			"pagination" => $pagination,
			"offset" => $record_number,
			"Tab_On" => $cType == $this->cTypeToSearch? '_on' :null,
			"Tab_Onclick" => $this->refreshPluginSlotAnchor('&ctab='. rawurlencode($cType). '&category='. (int) $this->category. '&searchString='. rawurlencode($this->searchString))
		);
		
		
	}
	
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				$box['tabs']['first_tab']['fields']['pagination_style']['values'] = 
					ze\pluginAdm::paginationOptions();
				
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				$box['tabs']['first_tab']['fields']['hide_private_items']['hidden'] = 
					!$values['first_tab/show_private_items'];
				
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

				break;
		}
	}
	
}
