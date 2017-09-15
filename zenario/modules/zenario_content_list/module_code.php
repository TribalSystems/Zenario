<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

class zenario_content_list extends module_base_class {
	
	protected $dataField;
	protected $pages;
	protected $totalPages;
	protected $rows = false;
	protected $items = false;
	protected $sql;
	
	protected $show_language = false;
	protected $target_blank = false;
	
	
	//Returns a list of fields needed by the Plugin
	//Intended to be easily overwritten
	protected function lookForContentSelect() {
		$sql = "
			SELECT
				v.id,
				v.type,
				v.version,
				v.title,
				v.filename,
				v.keywords,
				v.description,
				c.alias,
				c.equiv_id,
				c.language_id,
				v.publisher_id,
				v.writer_id,
				v.writer_name,
				IFNULL(v.publication_date, c.first_created_datetime) AS `content_table_date`,
				publication_date as release_date,
				". ifNull($this->dataField, "''"). " AS `content_table_data`";
		if ($this->setting('show_author_image')) {
			$sql .= ', 
				ad.image_id AS writer_image_id,
				fi.alt_tag';
		}
		
		if ($this->setting('only_show_child_items')) {
			$sql .= ",
				mi2.id AS menu_id,
				mi2.parent_id AS menu_parent_id,
				mh.separation AS menu_separation";
		}
		
		return $sql;
	}
	
	
	//Adds table joins to the SQL query
	//Intended to be easily overwritten
	protected function lookForContentTableJoins() {
		$sql = "";
		
		//Filter by a categories if requested
		if ($categories = $this->setting('category')) {
			foreach (explodeAndTrim($categories, true) as $catId) {
				if (checkRowExists('categories', array('id' => (int) $catId))) {
					if ($this->setting('refine_type') != 'any_categories') {
						$sql .= "
					INNER";
					} else {
						$sql .= "
					LEFT";
					}
		
					$sql .= " JOIN ". DB_NAME_PREFIX. "category_item_link AS cil_". (int) $catId. "
					   ON cil_". (int) $catId. ".equiv_id = c.equiv_id
					  AND cil_". (int) $catId. ".content_type = c.type
					  AND cil_". (int) $catId. ".category_id = ". (int) $catId;
				}
			}
		}
		if ($this->setting('enable_omit_category') && ($categories = $this->setting('omit_category'))) {
			foreach (explodeAndTrim($categories, true) as $catId) {
				if (checkRowExists('categories', array('id' => (int) $catId))) {
					$sql .= "
					LEFT JOIN ". DB_NAME_PREFIX. "category_item_link AS cil_". (int) $catId. "
					   ON cil_". (int) $catId. ".equiv_id = c.equiv_id
					  AND cil_". (int) $catId. ".content_type = c.type
					  AND cil_". (int) $catId. ".category_id = ". (int) $catId;
				}
			}
		}
		
		//Only show child-nodes of the current Menu Node
		if ($this->setting('only_show_child_items')) {
			$sql .= "
			INNER JOIN ". DB_NAME_PREFIX. "menu_nodes AS mi1
			   ON mi1.equiv_id = ". (int) cms_core::$equivId. "
			  AND mi1.content_type = '". sqlEscape($this->cType). "'
			  AND mi1.target_loc = 'int'";
			
			if (!$this->setting('show_secondaries')) {
				$sql .= "
			  AND mi1.redundancy = 'primary'";
			}
			
			$sql .= "
			INNER JOIN ". DB_NAME_PREFIX. "menu_nodes AS mi2
			   ON mi2.equiv_id = c.equiv_id
			  AND mi2.content_type = c.type
			  AND mi2.target_loc = 'int'";
			
			if (!$this->setting('show_secondaries')) {
				$sql .= "
			  AND mi2.redundancy = 'primary'";
			}
			
			$sql .= "
			INNER JOIN ". DB_NAME_PREFIX. "menu_hierarchy AS mh
			   ON mi1.id = mh.ancestor_id
			  AND mi2.id = mh.child_id
			  AND mh.separation <= ". (int) $this->setting('child_item_levels');
			
			$this->showInMenuMode();
		}
		
		if ($this->setting('show_author_image')) {
			$sql .= '
				LEFT JOIN '.DB_NAME_PREFIX.'admins AS ad
					ON v.writer_id = ad.id
				LEFT JOIN '.DB_NAME_PREFIX.'files AS fi
					ON ad.image_id = fi.id';
		}
		
		return $sql;
	}
	
	
	//Adds to the WHERE clause of the SQL query
	//Intended to be easily overwritten
	protected function lookForContentWhere() {
		$sql = "";
		
		if ($this->setting('content_type') != 'all') {
			$sql .= "
			  AND v.type = '". sqlEscape($this->setting('content_type')). "'";
		} else {
			$cTypes = array();
			foreach (getContentTypes() as $cType) {
				switch ($cType['content_type_id'] ?? false){
					case 'recurringevent':
					case 'event':
						break;
					default:
						$cTypes[] = $cType['content_type_id'];
						break;
				}
			}
			if ($cTypes) {
				$sql .= "
				  AND v.type IN ('" . implode("','", $cTypes) . "')";
			}
		}
		
		$first = true;
		if ($this->setting('refine_type') == 'any_categories' && ($categories = $this->setting('category'))) {
			foreach (explodeAndTrim($categories, true) as $catId) {
				if (checkRowExists('categories', array('id' => (int) $catId))) {
					if ($first) {
						$sql .= "
							AND (";
					} else {
						$sql .= "
							OR";
					}
					
					$sql .= " cil_". (int) $catId. ".category_id IS NOT NULL";
					$first = false;
				}
			}
		}
		if (!$first) {
			$sql .= ")";
		}
		
		if ($this->setting('enable_omit_category') && ($categories = $this->setting('omit_category'))) {
			foreach (explodeAndTrim($categories, true) as $catId) {
				if (checkRowExists('categories', array('id' => (int) $catId))) {
					$sql .= "
						AND cil_". (int) $catId. ".category_id IS NULL";
				}
			}
		}
		
		if ($this->setting('language_selection') == 'visitor') {
			//Only return content in the current language
			$sql .= "
			  AND c.language_id = '". sqlEscape(cms_core::$langId). "'";
		
		} elseif ($this->setting('language_selection') == 'specific_languages') { 
			//Return content in languages selected by admin
			$arr = array('');
			foreach(explode(",", $this->setting('specific_languages')) as $langCode)  {
				$arr[] = sqlEscape($langCode);
			}
			$sql .="
				AND c.language_id IN ('". implode("','", $arr) . "')";
		}
		
		//Exclude this page itself
		$sql .= "
		  AND v.tag_id != '". sqlEscape($this->cType. '_'. $this->cID). "'";
		
		
		//Release date section
		
		//Date range
		$startDate = $this->setting('start_date');
		$endDate = $this->setting('end_date');
		
		if ($this->setting('release_date')=='date_range'){
			$sql .= "
				AND DATE(v.publication_date) >= '" . sqlEscape($startDate) . "'
				";
			
			$sql .= "
				AND DATE(v.publication_date) <=  '" . sqlEscape($endDate) . "'
				";
		}

		//Relative date range
		if ($this->setting('release_date')=='relative_date_range' && $this->setting('relative_operator')
					&& ((int)$this->setting('relative_value'))>0 && $this->setting('relative_units')) {
			if ($this->setting('relative_operator')=='older'){
				$sqlOperator = " < ";
			} else {
				$sqlOperator = " >= ";
			}
			
			switch ($this->setting('relative_units')){
				case 'days':
					$sql .= " AND publication_date " . $sqlOperator . " DATE_SUB(DATE(NOW()), INTERVAL " . (int)$this->setting('relative_value') . " DAY)  ";
					break;
				case 'months':
					$sql .= " AND publication_date " . $sqlOperator . " DATE_SUB(DATE(NOW()), INTERVAL " . (int)$this->setting('relative_value') . " MONTH)  ";
					break;
				case 'years':
					$sql .= " AND publication_date " . $sqlOperator . " DATE_SUB(DATE(NOW()), INTERVAL " . (int)$this->setting('relative_value') . " YEAR)  ";
					break;
			}
		}
		
		//prior_to_date
		if ($this->setting('release_date')=='prior_to_date'){
			$priorToDate = $this->setting('prior_to_date');
			
			$sql .= "
				AND DATE(v.publication_date) <  '".sqlEscape($priorToDate)."'
				";
		}
		//on_date
		if ($this->setting('release_date')=='on_date'){
			$onDate = $this->setting('on_date');

			
			$sql .= "
				AND DATE(v.publication_date) =  '" . sqlEscape($onDate) . "'
				";
		}
		//after_date
		if ($this->setting('release_date')=='after_date'){
			$afterDate = $this->setting('after_date');
			
			$sql .= "
				AND DATE(v.publication_date) >  '" . sqlEscape($afterDate) . "'
				";
		}
		
		return $sql;
	}
	
	
	//Sort the Content
	//Intended to be easily overwritten
	protected function orderContentBy() {
		if ($this->setting('only_show_child_items') && $this->setting('order') == 'Menu') {
			if ($this->setting('child_item_levels') == 1) {
				return "
				ORDER BY mi2.ordinal";
			} else {
				return "
				ORDER BY mh.separation, mi2.ordinal";
			}
		
		} elseif ($this->setting('order') == 'Alphabetically') {
			return "
			ORDER BY v.title";
		
		} elseif ($this->setting('order') == 'Most_Recent_First') {
			return "
			ORDER BY `content_table_date` DESC, c.id DESC";
		
		} else {
			return "
			ORDER BY `content_table_date`, c.id";
		}
	}
	
	
	//Returns a SQL statement that should identify each content item in the list
	//Intended to be easily overwritten
	protected function lookForContentSQL() {
		$hidePrivateItems = false;
		if ($this->setting('hide_private_items') == 1) {
			$hidePrivateItems = true;
		}
		$sql =
			sqlToSearchContentTable(
				$hidePrivateItems, $this->setting('only_show'), 
				$this->lookForContentTableJoins()
			).
			$this->lookForContentWhere();
		return $sql;
	}
	
	
	protected function addExtraMergeFields(&$row, &$item) {
	
	}
	
	protected function escapeAppropriately($text) {
		if (($_REQUEST['method_call'] ?? false) == 'showRSS') {
			return XMLEscape($text);
		} else {
			return $this->escapeIfOldFramework($text);
		}
	}
	
	
	public function init() {
		$this->show_language = $this->setting('show_language');
		$this->target_blank = $this->setting('target_blank');
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = !$this->setting('hide_private_items'), $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = $this->setting('only_show_child_items'), $clearByUser = (bool) $this->setting('hide_private_items'), $clearByFile = $this->setting('show_sticky_images'), $clearByModuleData = false);
		
		
		if ($this->setting('data_field') == 'description') {
			$this->dataField = 'v.description';
		} elseif ($this->setting('data_field') == 'content_summary') {
			$this->dataField = 'v.content_summary';
		} else {
			$this->dataField = false;
		}
		
		$this->registerGetRequest('page', 1);
		
		//Pick a page to display
		$this->page = is_numeric($_GET['page'] ?? false)? (int) ($_GET['page'] ?? false) : 1;
		
		
		//Loop through each item to display, and add its details to an array of merge fields
		$this->items = array();
		$oddOrEven = 'odd';
		
		if ($showCategory = $this->setting('show_content_items_lowest_category') && setting('enable_display_categories_on_content_lists')) {
			$categories = getRowsArray('categories', array('name', 'id', 'parent_id', 'public'), array());
		}
		
		
		if ($result = $this->lookForContent()) {
			while($row = sqlFetchAssoc($result)) {
				
				$item = array();
				
				if ($this->dataField == 'v.description') {
					$item['Excerpt_Text'] = $this->escapeAppropriately($row['content_table_data']);
				
				} elseif ($this->dataField == 'v.content_summary') {
					if (($_REQUEST['method_call'] ?? false) == 'showRSS') {
						$item['Excerpt_Text'] = XMLEscape(html_entity_decode(strip_tags($row['content_table_data']), ENT_QUOTES, 'UTF-8'));
					} else {
						$item['Excerpt_Text'] = $row['content_table_data'];
					}
				}
				if ($row['writer_id']) {
					$item['Author'] = $row['writer_name'];
				}
				if (isset($row['writer_image_id']) && !empty($row['writer_image_id'])) {
					$width = $height = $url = false;
					Ze\File::imageLink($width, $height, $url, $row['writer_image_id'], $this->setting('author_width'), $this->setting('author_height'), $this->setting('author_canvas'), (int)$this->setting('author_offset'), $this->setting('author_retina'));
					$item['Author_Image_Src'] = $url;
					$item['Author_Image_Alt'] = $row['alt_tag'];
					$item['Author_Image_Width'] = $width;
					$item['Author_Image_Height'] = $height;
				}
				
				$item['language_id'] = $row['language_id'];
				$item['equiv_id'] = $row['equiv_id'];
				
				$item['cID'] = $row['id'];
				$item['cType'] = $row['type'];
				$item['cVersion'] = $row['version'];
				$item['Id'] = $row['type']. '_'. $row['id'];
				$item['Target_Blank'] = '';
				$item['ODD_OR_EVEN'] = $oddOrEven = $oddOrEven == 'odd'? 'even' : 'odd';
				$item['Link'] = $this->linkToItemAnchor($row['id'], $row['type'], false, '', $row['alias']);
				$item['Full_Link'] = $this->escapeAppropriately($this->linkToItem($row['id'], $row['type'], true, '', $row['alias']));
				$item['Content_Type'] = $row['type'];
				$item['Title'] = $this->escapeAppropriately($row['title']);
				$item['Keywords'] = $this->escapeAppropriately($row['keywords']);
				$item['Description'] = $this->escapeAppropriately($row['description']);
				
				if ($this->setting('show_dates') && $row['release_date']) {
					$item['Date'] = formatDateNicely(
						$row['release_date'],
						$this->setting('date_format'),
						false,
						(bool) $this->setting('show_times'),
						($_REQUEST['method_call'] ?? false) == 'showRSS');
				}
				
				
				$width = $height = $url = false;
				if ($this->setting('show_sticky_images')) {
					if ($row['type'] == 'picture') {
						//Legacy code for Pictures
						if ($imageId = getRow("content_item_versions", "file_id", array("id" => $row['id'], 'type' => $row['type'], "version" => $row['version']))) {
							Ze\File::imageLink($width, $height, $url, $imageId, $this->setting('width'), $this->setting('height'), $this->setting('canvas'), 0, $this->setting('retina'));
						}
					} else {
						$foundStickyImage = Ze\File::itemStickyImageLink($width, $height, $url, $row['id'], $row['type'], $row['version'], $this->setting('width'), $this->setting('height'), $this->setting('canvas'), 0, $this->setting('retina'));
						
						if (!$foundStickyImage && $this->setting('fall_back_to_default_image') && $this->setting('default_image_id')) {
							$width = $height = $url = false;
							Ze\File::imageLink($width, $height, $url, $this->setting('default_image_id'), $this->setting('width'), $this->setting('height'), $this->setting('canvas'), 0, $this->setting('retina'));
						}
					} 
				}
				
				if ($url) {
					$item['Sticky_Image'] =
						'<img class="sticky_image" alt="'. $item['Title']. '"'.
						' src="'. htmlspecialchars($url). '"'.
						' style="width: '. $width. 'px; height: '. $height. 'px;"/>';
					
					$item['Sticky_Image_Alt'] = $item['Title'];
					$item['Sticky_Image_Src'] = $url;
					$item['Sticky_Image_Width'] = $width;
					$item['Sticky_Image_Height'] = $height;
					$item['Sticky_image_class_name']="sticky_image";
				}else{
					$item['Sticky_image_class_name']="sticky_image_placeholder";
				}
				
				$this->getStyledExtensionIcon(pathinfo($row['filename'], PATHINFO_EXTENSION), $item);
					
				
				if ($row['type'] == 'document') {
					$link = $this->linkToItem($row['id'], $row['type'], false, 'download=1', $row['alias']);
					$item['Download_Page_Link'] = $item['Link'];
					$item['Download_Page_Full_Link'] = $item['Full_Link'];
					$item['Download_Now_Link'] = $this->linkToItemAnchor($row['id'], $row['type'], false, 'download=1', $row['alias']);
					$item['Download_Now_Full_Link'] = $this->escapeAppropriately(absCMSDirURL() . $link);
					$item['Download_Now_Link'] .= ' onclick="'. htmlspecialchars(Ze\File::trackDownload($link)). '"';
					
					if (!$this->setting('use_download_page')) {
						$item['Link'] = $item['Download_Now_Link'];
						$item['Full_Link'] = $item['Download_Now_Full_Link'];
					}
				}
				
				if ($this->setting('only_show_child_items')) {
					$item['Menu_Id'] = $row['menu_id'];
					$item['Menu_Parent_Id'] = $row['menu_parent_id'];
					$item['Menu_Separation'] = $row['menu_separation'];
				}
		
				if ($this->show_language) {
					$item['Language'] = htmlspecialchars(getLanguageName($row['language_id'], false));
				}
		
				if ($this->target_blank) {
					$item['Target_Blank'] = ' target="_blank"';
				}
				
				if ($showCategory) {
					$categoryId = static::getContentItemLowestPublicCategory($row['equiv_id'], $row['type'], $categories);
					if ($categoryId) {
						$item['Category'] = phrase('_CATEGORY_' . $categoryId);
						$item['Category_Id'] = $categoryId;
						$category = getRow('categories', array('landing_page_equiv_id', 'landing_page_content_type'), $categoryId);
						if ($category['landing_page_equiv_id'] && $category['landing_page_content_type']) {
							$item['Category_Landing_Page_Link'] = linkToItem($category['landing_page_equiv_id'], $category['landing_page_content_type']);
						}
					}
				}
				
				$dontAddItem = false;
				if ($this->setting('hide_private_items') == 2) {
					//show content item but with disabled link / class
					//check if use can see target
					if(!checkPerm($item['cID'], $item['cType'])) {
						$item['Link'] = false;
						$item['Full_Link'] = false;
						$item['Disabled'] = true;
					}
					
				} elseif ($this->setting('hide_private_items') == 3) {
					//call a static method to decide 
					
					//first check to see if they have access
					if(!checkPerm($item['cID'], $item['cType'])) {
						$staticMethodClassName = $this->setting('hide_private_items_class_name');
						$staticMethodName = $this->setting('hide_private_items_method_name');
						$userId = userId();
						
						inc($staticMethodClassName);
						$value = call_user_func(
							array(
								$staticMethodClassName, 
								$staticMethodName
							),
							$item['cID'], 
							$item['cType'],
							$userId
						);
						
						if ($value) {
							$item['Link'] = false;
							$item['Full_Link'] = false;
							$item['Disabled'] = true;
						} else {
							$dontAddItem = true;
						}
						
					} else {
						//if they have access then show the content item link
					}
					
				}
				if (!$dontAddItem) {
					$this->addExtraMergeFields($row, $item);
					$this->items[$item['Id']] = $item;
				} 
				$dontAddItem = false;
			}
		}
		
		return !empty($this->items) || ((bool)$this->setting('show_headings_if_no_items'));
	}
	
	// Gets a content items lowest level public category (return false if there are multiple)
	public static function getContentItemLowestPublicCategory($equivId, $cType, $allCategories) {
		$publicCategories = array();
		$sql = '
			SELECT c.name, c.id, c.parent_id, c.public
			FROM ' . DB_NAME_PREFIX . 'category_item_link l
			INNER JOIN ' . DB_NAME_PREFIX . 'categories c
				ON l.category_id = c.id
			WHERE l.equiv_id = ' . (int)$equivId . '
			AND l.content_type = "' . sqlEscape($cType) . '"';
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			if ($row['public']) {
				$publicCategories[$row['id']] = $row;
			}
		}
		
		$highestLevel = 0;
		$publicCategoryLevels = array();
		foreach ($publicCategories as $categoryId => $category) {
			$level = static::getCategoryLevel($categoryId, $allCategories);
			if ($level !== false) {
				$publicCategoryLevels[$level][] = $categoryId;
				if ($level > $highestLevel) {
					$highestLevel = $level;
				}
			}
		}
		
		if (!isset($publicCategoryLevels[$highestLevel]) || (count($publicCategoryLevels[$highestLevel]) !== 1)) {
			return false;
		}
		
		return $publicCategoryLevels[$highestLevel][0];
	}
	
	public static function getCategoryLevel($categoryId, $categories) {
		$category = $categories[$categoryId];
		$level = 1;
		while ($category['parent_id'] != 0) {
			if (isset($categories[$category['parent_id']])) {
				$category = $categories[$category['parent_id']];
				++$level;
			} else {
				return false;
			}
		}
		return $level;
	}
	
	
	
	//Runs the SQL statement that will return a list of content items
	protected function lookForContent() {
		
		$sql = $this->lookForContentSQL();
		
		if ($sql === false) {
			return false;
		}
		
		//Get a count of how many items we have to display
		$result = sqlSelect('SELECT COUNT(*) '. $sql);
		list($this->rows) = sqlFetchRow($result);
		
		$this->totalPages = (int) ceil($this->rows / $this->setting('page_size'));
		
		//Loop through each page to display, and add its details to an array of merge fields
		$this->pages = array();
		for ($i = 1; $i <= $this->setting('page_limit') && $i <= $this->totalPages; ++$i) {
			$this->pages[$i] = '&page='. $i;
		}
		
		$sql =
			$this->lookForContentSelect().
			$sql.
			$this->orderContentBy().
			paginationLimit($this->page, $this->setting('page_size'), $this->setting('offset'));
		return sqlQuery($sql);
	}
	
	//Get a list of document ids matched by this list
	public function getFileIDs() {
		
		$sql = $this->lookForContentSQL();
		
		if ($sql === false) {
			return false;
		}
		
		//Get a count of how many items we have to display
		$sql =
			"SELECT v.file_id AS id, v.filename
			". $sql;
		
		$getFileIDs = array();
		if ($result = sqlQuery($sql)) {
			while ($row = sqlFetchAssoc($result)) {
				if ($row['id']) {
					$getFileIDs[$row['id']] = $row;
				}
			}
		}
		
		return $getFileIDs;
	}

	public function addToPageHead() {
		if ($this->setting('enable_rss')) {
			echo '
				<link
					rel="alternate"
					type="application/rss+xml"
					href="'. htmlspecialchars($this->showRSSLink(true)). '"
					title="'. htmlspecialchars(getItemTitle($this->cID, $this->cType, $this->cVersion)). '" />';

		}
	}
	
	
	public function showSlot() {
		
		if (!(!empty($this->items) || ((bool)$this->setting('show_headings_if_no_items')))) {
			if (checkPriv()) {
				echo adminPhrase('This Plugin will not be shown to visitors because there are no results.');
			}
			return;
		}
		
		$moreLink = false;
		if ($this->setting('show_more_link') && $this->getCIDAndCTypeFromSetting($cID, $cType, 'more_hyperlink_target')) {
			langEquivalentItem($cID, $cType);
			$moreLink = $this->linkToItemAnchor($cID, $cType);
		}
		
		$paginationLinks = array();
		
		if ($this->setting('show_pagination') && count($this->pages) > 1) {
			$this->pagination('pagination_style', $this->page, $this->pages, $pagination, $paginationLinks);
		} else {
			$pagination = false;
		}
		
		
		//Check whether any phrases have been entered into the plugin settings, and translate them
		//if needed
		$titleWithContent = '';
		if ($this->setting('show_headings')) {
			$titleWithContent = htmlspecialchars($this->setting('heading_if_items'));
			
			if (!$this->isVersionControlled && $this->setting('translate_text')) {
				$titleWithContent = $this->phrase($titleWithContent);
			}
		}
		$titleWithNoContent = '';
		if ($this->setting('show_headings_if_no_items')) {
			$titleWithNoContent = htmlspecialchars($this->setting('heading_if_no_items'));
			
			if (!$this->isVersionControlled && $this->setting('translate_text')) {
				$titleWithNoContent = $this->phrase($titleWithNoContent);
			}
		}
		$moreLinkText = '';
		if ($moreLink) {
			$moreLinkText = htmlspecialchars($this->setting('more_link_text'));
			
			if (!$this->isVersionControlled && $this->setting('translate_text')) {
				$moreLinkText = $this->phrase($moreLinkText);
			}
		}
		
		
		$this->framework(
			'Outer', 
			array(
				'More_Link' => $moreLink,
				'More_Link_Title' => $moreLinkText,
				'Pagination' => $pagination,
				'Pagination_Data' => $paginationLinks,
				'Results' => $this->rows,
				'RSS_Link' => $this->setting('enable_rss')? $this->escapeAppropriately($this->showRSSLink(true)) : null,
				'Title_With_Content' => $titleWithContent,
				'Title_With_No_Content' => ((bool)$this->setting('show_headings_if_no_items')) ? $titleWithNoContent: null,
				'Title_Tags' => $this->setting('heading_tags') ? $this->setting('heading_tags') : 'h1'
			),
			array(
				'Slot' => true,
				'More' => (bool) $moreLink,
				'No_Rows' => empty($this->items),
				'Rows' => !empty($this->items),
				'Row' => $this->items,
				'Show_Date' => $this->setting('show_dates'),
				'Show_Author' => $this->setting('show_author'),
				'Show_Author_Image' => $this->setting('show_author_image'),
				'Show_Excerpt' => (bool) $this->dataField,
				'Show_Item_Title' => (bool)$this->setting('show_titles'),
				'Show_Sticky_Image' => (bool) $this->setting('show_sticky_images'),
				'Show_RSS_Link' => (bool) $this->setting('enable_rss'),
				'Show_Title' => (bool)$this->setting('show_headings'),
				'Show_No_Title' => (bool)$this->setting('show_headings_if_no_items'),
				'Show_Category' => (bool)$this->setting('show_content_items_lowest_category') && (bool)setting('enable_display_categories_on_content_lists')
			)
		);
		
	}
	
	
	public function showRSS() {
		
		$this->framework(
			'Outer', 
			array(
				'Description' => XMLEscape(getItemDescription($this->cID, $this->cType, $this->cVersion)),
				'Link' => XMLEscape($this->linkToItem($this->cID, $this->cType, true)),
				'RSS_Link' => XMLEscape($this->showRSSLink(true, false)),
				'Title' => XMLEscape(getItemTitle($this->cID, $this->cType, $this->cVersion)),
				'Results' => $this->rows,
			),
			array(
				'RSS' => true,
				'Rows' => true,
				'RSS_Item' => $this->items
			)
		);
	
	}
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				$box['tabs']['pagination']['fields']['pagination_style']['values'] = 
					paginationOptions();
				
				foreach (getContentTypes() as $cType) {
					switch ($cType['content_type_id'] ?? false){
						case 'recurringevent':
						case 'event':
							break;
						default:
							$box['tabs']['first_tab']['fields']['content_type']['values'][$cType['content_type_id']] =
								$cType['content_type_name_en'];
							break;
					}
				}
				
				$categoriesEnabled = setting('enable_display_categories_on_content_lists');
				if (!$categoriesEnabled) {
					$fields['each_item/show_content_items_lowest_category']['disabled'] = true;
					$fields['each_item/show_content_items_lowest_category']['side_note'] = adminPhrase('You must enable this option in your site settings under "Categories".');
					$values['each_item/show_content_items_lowest_category'] = false;
				}
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillAdminSlotControls(&$controls) {
		$controls['notes']['filter_settings'] = array(
			'ord' => 0,
			'label' => '',
			'css_class' => 'zenario_slotControl_filterSettings',
			'page_modes' => array('edit' => true, 'item' => true, 'layout' => true));
		
		$this->fillAdminSlotControlsShowFilterSettings($controls);
	}
	
	protected function fillAdminSlotControlsShowFilterSettings(&$controls) {
		
		if ($this->setting('content_type') == 'all') {
			$controls['notes']['filter_settings']['label'] .=
				adminPhrase('Source Content Type: All Content Types');
		
		} else {
			$controls['notes']['filter_settings']['label'] .=
				adminPhrase('Source Content Type: [[ctype]]',
					array('ctype' => htmlspecialchars(getContentTypeName($this->setting('content_type')))));
		}
		
		switch ($this->setting('only_show')) {
			case 'public':
				$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase('Show: Public Content Items only');
				break;
			
			case 'all':
				$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase('Show: Public and Private Content Items');
				break;
			
			case 'private':
				$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase('Show: Private Content Items only');
				break;
		}
		
		if ($this->setting('language_selection') == 'all') {
			$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase('Show Items in: All enabled Languages');
		} elseif ($this->setting('language_selection') == 'visitor') {
			$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase("Show Items in: Visitor's Language");
		} elseif ($this->setting('language_selection') == 'specific_languages') {
			$langs = getLanguages();
			$arr = array();
			foreach(explode(",", $this->setting('specific_languages')) as $langCode )  {
				$arr[] = $langs[$langCode]['english_name'] ?? false;
			}
			sort($arr);
			$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase("Show Items in: " . htmlspecialchars(implode(", ", $arr)));
		}
		
		if ($this->setting('only_show_child_items')) {
			if ((int) $this->setting('child_item_levels') == 1) {
				$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase('Menu Levels: Content in the menu one level below current Item');
			
			} elseif ((int) $this->setting('child_item_levels') >= 99) {
				$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase('Menu Levels: All Content in the menu below current Item');
			
			} else {
				$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase('Menu Levels: Content in the menu up to [[child_item_levels]] levels below',
														array('child_item_levels' => (int) $this->setting('child_item_levels')));
			}
		}
		
		if ($this->setting('author_advice')) {
			$controls['notes']['author_advice']['label'] = nl2br(htmlspecialchars($this->setting('author_advice')));
		}
		
		$this->fillAdminSlotControlsShowFilterSettingsCategories($controls);
	}
	
	public function fillAdminSlotControlsShowFilterSettingsCategories(&$controls) {
		if ($this->setting('category')) {
			$first = true;
			foreach(explodeAndTrim($this->setting('category'), true) as $catId) {
				if ($name = getCategoryName($catId)) {
					$separator = ",";
					$labelPrefix = "";
				
					if ($this->setting('refine_type') != 'any_categories') {
						$separator = " AND ";
						$labelPrefix = "In ";
					} else {
						$separator = " OR ";
						$labelPrefix = "In ";
					}

				
					if ($first) {
						$first = false;
						$controls['notes']['filter_settings']['label'] .=
							'<br/>'.
							adminPhrase($labelPrefix . 'Category:').
							' ';
					} else {
						$controls['notes']['filter_settings']['label'] .= $separator;
					}
				
					$controls['notes']['filter_settings']['label'] .= htmlspecialchars($name);
				}
			}
		}
	}

	public function getDocumentIDs() {
		
		if ($this->setting('content_type') != 'document') {
			return false;
		}
		
		$sql = $this->lookForContentSQL();
		
		if ($sql === false) {
			return false;
		}
		
		//Get a count of how many items we have to display
		$sql =
			"SELECT c.id
			". $sql;
		
		$documentIDs = array();
		if ($result = sqlQuery($sql)) {
			while ($row = sqlFetchAssoc($result)) {
				$documentIDs[] = $row['id'];
			}
		}
		
		return $documentIDs;
	}

	
}