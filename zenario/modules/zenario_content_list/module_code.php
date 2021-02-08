<?php
/*
 * Copyright (c) 2021, Tribal Limited
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
use Aws\S3\S3Client;
class zenario_content_list extends ze\moduleBaseClass {
	
	protected $dataField;
	protected $pages;
	protected $totalPages;
	protected $rows = false;
	protected $items = false;
	protected $sql;
	protected $isRSS = false;
	
	protected $show_language = false;
	protected $target_blank = false;
	var $zipArchiveName;
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
				v.file_id,
				v.s3_file_id,
				c.alias,
				c.equiv_id,
				c.language_id,
				v.publisher_id,
				v.writer_id,
				v.writer_name,
				IFNULL(v.release_date, c.first_created_datetime) AS `content_table_date`,
				release_date,
				". ($this->dataField ?: "''"). " AS `content_table_data`";
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
		if ($this->setting('category_filters_dropdown') == 'choose_categories_to_display_or_omit' && $categories = $this->setting('category')) {
			foreach (ze\ray::explodeAndTrim($categories, true) as $catId) {
				if (ze\row::exists('categories', ['id' => (int) $catId])) {
					if ($this->setting('refine_type') != 'any_categories') {
						$sql .= "
					INNER";
					} else {
						$sql .= "
					LEFT";
					}
		
					$sql .= " JOIN ". DB_PREFIX. "category_item_link AS cil_". (int) $catId. "
					   ON cil_". (int) $catId. ".equiv_id = c.equiv_id
					  AND cil_". (int) $catId. ".content_type = c.type
					  AND cil_". (int) $catId. ".category_id = ". (int) $catId;
				}
			}
		}
		
		if ($this->setting('enable_omit_category') && ($categories = $this->setting('omit_category'))) {
			foreach (ze\ray::explodeAndTrim($categories, true) as $catId) {
				if (ze\row::exists('categories', ['id' => (int) $catId])) {
					$sql .= "
					LEFT JOIN ". DB_PREFIX. "category_item_link AS ocil_". (int) $catId. "
					   ON ocil_". (int) $catId. ".equiv_id = c.equiv_id
					  AND ocil_". (int) $catId. ".content_type = c.type
					  AND ocil_". (int) $catId. ".category_id = ". (int) $catId;
				}
			}
		}
		
		if ($this->setting('category_filters_dropdown') == 'show_content_with_matching_categories') {
			$contentItemInfo = ze\row::get('content_items', ['id', 'type'], ['equiv_id' => ze::$equivId]);
			$contentItemCategories = ze\category::contentItemCategories($contentItemInfo['id'], $contentItemInfo['type'], $publicOnly = true);
			if ($contentItemCategories) {
				foreach ($contentItemCategories as $contentItemCategory) {
					if (ze\row::exists('categories', ['id' => (int) $contentItemCategory['id']])) {
						if ($this->setting('refine_type') != 'any_categories') {
							$sql .= "
						INNER";
						} else {
							$sql .= "
						LEFT";
						}
					
						$sql .= " JOIN ". DB_PREFIX. "category_item_link AS cil_". (int) $contentItemCategory['id']. "
						   ON cil_". (int) $contentItemCategory['id']. ".equiv_id = c.equiv_id
						  AND cil_". (int) $contentItemCategory['id']. ".content_type = c.type
						  AND cil_". (int) $contentItemCategory['id']. ".category_id = ". (int) $contentItemCategory['id'];
					}
				}
			}
		}
		
		//Only show child-nodes of the current Menu Node
		if ($this->setting('only_show_child_items')) {
			$sql .= "
			INNER JOIN ". DB_PREFIX. "menu_nodes AS mi1
			   ON mi1.equiv_id = ". (int) ze::$equivId. "
			  AND mi1.content_type = '". ze\escape::sql($this->cType). "'
			  AND mi1.target_loc = 'int'";
			
			if (!$this->setting('show_secondaries')) {
				$sql .= "
			  AND mi1.redundancy = 'primary'";
			}
			
			$sql .= "
			INNER JOIN ". DB_PREFIX. "menu_nodes AS mi2
			   ON mi2.equiv_id = c.equiv_id
			  AND mi2.content_type = c.type
			  AND mi2.target_loc = 'int'";
			
			if (!$this->setting('show_secondaries')) {
				$sql .= "
			  AND mi2.redundancy = 'primary'";
			}
			
			$sql .= "
			INNER JOIN ". DB_PREFIX. "menu_hierarchy AS mh
			   ON mi1.id = mh.ancestor_id
			  AND mi2.id = mh.child_id
			  AND mh.separation <= ". (int) $this->setting('child_item_levels');
			
			//$this->showInMenuMode();
		}
		
		if ($this->setting('show_author_image')) {
			$sql .= '
				LEFT JOIN '.DB_PREFIX.'admins AS ad
					ON v.writer_id = ad.id
				LEFT JOIN '.DB_PREFIX.'files AS fi
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
			  AND v.type = '". ze\escape::sql($this->setting('content_type')). "'";
		} else {
			$cTypes = [];
			foreach (ze\content::getContentTypes() as $cType) {
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
		if ($this->setting('category_filters_dropdown') == 'choose_categories_to_display_or_omit') {
			$categories = $this->setting('category');
			if ($this->setting('refine_type') == 'any_categories' && $categories) {
				foreach (ze\ray::explodeAndTrim($categories, true) as $catId) {
					if (ze\row::exists('categories', ['id' => (int) $catId])) {
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
			} elseif (($this->setting('refine_type') == 'any_categories' || $this->setting('refine_type') == 'all_categories') && !$categories) {
				$sql .= '
					AND FALSE';
			}
		}
		
		if ($this->setting('category_filters_dropdown') == 'show_content_with_matching_categories' && $this->setting('refine_type') == 'any_categories') {
			$contentItemInfo = ze\row::get('content_items', ['id', 'type'], ['equiv_id' => ze::$equivId]);
			$contentItemCategories = ze\category::contentItemCategories($contentItemInfo['id'], $contentItemInfo['type'], $publicOnly = true);
			if ($contentItemCategories) {
				foreach ($contentItemCategories as $contentItemCategory) {
					if (ze\row::exists('categories', ['id' => (int) $contentItemCategory['id']])) {
						if ($first) {
							$sql .= "
								AND (";
						} else {
							$sql .= "
								OR";
						}
					
						$sql .= " cil_". (int) $contentItemCategory['id']. ".category_id IS NOT NULL";
						$first = false;
					}
				}
			}
		}
				
		if (!$first) {
			$sql .= ")";
		}
		
		if ($this->setting('enable_omit_category') && ($categories = $this->setting('omit_category'))) {
			foreach (ze\ray::explodeAndTrim($categories, true) as $catId) {
				if (ze\row::exists('categories', ['id' => (int) $catId])) {
					$sql .= "
						AND ocil_". (int) $catId. ".category_id IS NULL";
				}
			}
		}
		
		if ($this->setting('language_selection') == 'visitor') {
			//Only return content in the current language
			$sql .= "
			  AND c.language_id = '". ze\escape::sql(ze::$langId). "'";
		
		} elseif ($this->setting('language_selection') == 'specific_languages') { 
			//Return content in languages selected by admin
			$arr = [''];
			foreach(explode(",", $this->setting('specific_languages')) as $langCode)  {
				$arr[] = ze\escape::sql($langCode);
			}
			$sql .="
				AND c.language_id IN ('". implode("','", $arr) . "')";
		}
		
		//Exclude this page itself
		$sql .= "
		  AND v.tag_id != '". ze\escape::sql($this->cType. '_'. $this->cID). "'";
		
		
		//Release date section
		
		//Date range
		$startDate = $this->setting('start_date');
		$endDate = $this->setting('end_date');
		
		if ($this->setting('release_date')=='date_range'){
			$sql .= "
				AND DATE(v.release_date) >= '" . ze\escape::sql($startDate) . "'
				";
			
			$sql .= "
				AND DATE(v.release_date) <=  '" . ze\escape::sql($endDate) . "'
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
					$sql .= " AND release_date " . $sqlOperator . " DATE_SUB(DATE(NOW()), INTERVAL " . (int)$this->setting('relative_value') . " DAY)  ";
					break;
				case 'months':
					$sql .= " AND release_date " . $sqlOperator . " DATE_SUB(DATE(NOW()), INTERVAL " . (int)$this->setting('relative_value') . " MONTH)  ";
					break;
				case 'years':
					$sql .= " AND release_date " . $sqlOperator . " DATE_SUB(DATE(NOW()), INTERVAL " . (int)$this->setting('relative_value') . " YEAR)  ";
					break;
			}
		}
		
		//prior_to_date
		if ($this->setting('release_date')=='prior_to_date'){
			$priorToDate = $this->setting('prior_to_date');
			
			$sql .= "
				AND DATE(v.release_date) <  '".ze\escape::sql($priorToDate)."'
				";
		}
		//on_date
		if ($this->setting('release_date')=='on_date'){
			$onDate = $this->setting('on_date');

			
			$sql .= "
				AND DATE(v.release_date) =  '" . ze\escape::sql($onDate) . "'
				";
		}
		//after_date
		if ($this->setting('release_date')=='after_date'){
			$afterDate = $this->setting('after_date');
			
			$sql .= "
				AND DATE(v.release_date) >  '" . ze\escape::sql($afterDate) . "'
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
		
		$tableJoins = $this->lookForContentTableJoins();
		if ($this->setting('category_filters_dropdown') == 'show_content_with_matching_categories' && !$tableJoins) {
			$sql = false;
		} else {
			$sql =
				ze\content::sqlToSearchContentTable(
					$hidePrivateItems, $this->setting('only_show'), 
					$tableJoins
				).
				$this->lookForContentWhere();
		}
		return $sql;
	}
	
	
	protected function addExtraMergeFields(&$row, &$item) {
	
	}
	
	
	protected function escapeIfRSS($text) {
		if ($this->isRSS) {
			return ze\escape::xml($text);
		} else {
			return $text;
		}
	}
	
	
	public function init() {
		
		//To add Zip settings
		if (($this->zipArchiveName = $this->setting('zip_archive_name'))==''){
			$this->zipArchiveName = "documents.zip";
		} else {
			$arr = explode(".",$this->zipArchiveName);
			if ((count($arr)<2) || ($arr[count($arr)-1]!="zip") ){
				$this->zipArchiveName .= ".zip";
			}
		}
		$this->show_language = $this->setting('show_language');
		$this->target_blank = $this->setting('target_blank');
		
		$this->isRSS = $this->methodCallIs('showRSS');
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = !$this->setting('hide_private_items'), $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = $this->setting('only_show_child_items'), $clearByUser = (bool) $this->setting('hide_private_items'), $clearByFile = $this->setting('show_sticky_images'), $clearByModuleData = false);
		
		
		if ($this->setting('data_field') == 'description') {
			$this->dataField = 'v.description';
		} elseif ($this->setting('data_field') == 'content_summary'
			&& ($this->setting('content_type') == 'all'
				|| !ze\row::exists('content_types', ['content_type_id' => $this->setting('content_type'), 'summary_field' => 'hidden'])
			)
		) {
			$this->dataField = 'v.content_summary';
		} else {
			$this->dataField = false;
		}
		
		$this->registerGetRequest('page', 1);
		
		//Pick a page to display
		$this->page = is_numeric($_GET['page'] ?? false)? (int) ($_GET['page'] ?? false) : 1;
		
		
		//Loop through each item to display, and add its details to an array of merge fields
		$this->items = [];
		
		if ($showCategory = $this->setting('show_content_items_lowest_category') && ze::setting('enable_display_categories_on_content_lists')) {
			$categories = ze\row::getAssocs('categories', ['name', 'id', 'parent_id', 'public'], []);
		}
		
		
		if ($result = $this->lookForContent()) {
			while($row = ze\sql::fetchAssoc($result)) {
				$item = [];
				if ($this->setting('show_text_preview') && $this->dataField == 'v.description') {
					if ($this->isRSS) {
						$item['Excerpt_Text'] = $this->escapeIfRSS($row['content_table_data']);
					} else {
						$item['Excerpt_Text'] = htmlspecialchars($row['content_table_data']);
					}
				
				} elseif ($this->setting('show_text_preview') && $this->dataField == 'v.content_summary') {
					if ($this->setting('content_type') != 'all' || !ze\row::exists('content_types', ['content_type_id' => $row['type'], 'summary_field' => 'hidden'])) {
						if ($this->isRSS) {
							$item['Excerpt_Text'] = ze\escape::xml(html_entity_decode(strip_tags($row['content_table_data']), ENT_QUOTES, 'UTF-8'));
						} else {
							$item['Excerpt_Text'] = $row['content_table_data'];
						}
					}
				}
				
				if ($row['writer_id']) {
					$item['Author'] = $row['writer_name'];
				}
				if (isset($row['writer_image_id']) && !empty($row['writer_image_id'])) {
					$width = $height = $url = false;
					ze\file::imageLink($width, $height, $url, $row['writer_image_id'], $this->setting('author_width'), $this->setting('author_height'), $this->setting('author_canvas'), (int)$this->setting('author_offset'), $this->setting('author_retina'));
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
				$item['Local_File_Id'] = $row['file_id'];
				$item['s3_File_Id'] = $row['s3_file_id'];
				$s3FileDetails = ze\row::get('files', ['size','filename','path'], $row['s3_file_id']);
				$localFileDetails = ze\row::get('files', ['size'], $row['file_id']);
				if ($s3FileDetails && $s3FileDetails['size']) {
					$item['S3_File_Size'] = ze\file::formatSizeUnits($s3FileDetails['size']);
				}
				if ($localFileDetails && $localFileDetails['size'] && $item['cType'] == 'document') {
					$item['Local_File_Size'] = ze\file::formatSizeUnits($localFileDetails['size']);
				}
				if ($s3FileDetails) {
					$fileName = '';
					$presignedUrl = '';	
					if ($s3FileDetails['path']) {
						$fileName = $s3FileDetails['path'].'/'.$s3FileDetails['filename'];
					} else {
						$fileName = $s3FileDetails['filename'];
					}
					if ($fileName && ze::setting('aws_s3_support')) {
						if (ze\module::inc('zenario_ctype_document')) {
							$presignedUrl = zenario_ctype_document::getS3FilePresignedUrl($fileName);
						}
						if ($presignedUrl) {
							$item['S3_Anchor_Link'] =  $presignedUrl;
						}
					}
				}
				//$item['S3_Anchor_Link'] =  $this->linkToItemAnchor($this->cID,$this->cType,true,'&DownloadS3=1&sids=' . $row['s3_file_id']);
				$item['Link'] = $this->linkToItemAnchor($row['id'], $row['type'], false, '', $row['alias'], false, false, $stayInCurrentLanguage = true);
				$item['Full_Link'] = $this->escapeIfRSS($this->linkToItem($row['id'], $row['type'], true, '', $row['alias']));
				$item['Content_Type'] = $row['type'];
				$item['Title'] = $this->escapeIfRSS($row['title']);
				$item['Keywords'] = $this->escapeIfRSS($row['keywords']);
				$item['Description'] = $this->escapeIfRSS($row['description']);
				
				if ($this->setting('show_dates') && $row['release_date']) {
					if ($this->setting('date_format') == '_RELATIVE') {
						$item['Date'] = ze\date::simpleFormatRelativeDate($row['release_date']);
					} else {
						$item['Date'] = ze\date::format(
							$row['release_date'],
							$this->setting('date_format'),
							false,
							(bool) $this->setting('show_times'),
							$this->isRSS
						);
					}
				}
				
				
				$width = $height = $url = false;
				if ($this->setting('show_sticky_images')) {
					if ($row['type'] == 'picture') {
						//Legacy code for Pictures
						if ($imageId = ze\row::get("content_item_versions", "file_id", ["id" => $row['id'], 'type' => $row['type'], "version" => $row['version']])) {
							ze\file::imageLink($width, $height, $url, $imageId, $this->setting('width'), $this->setting('height'), $this->setting('canvas'), 0, $this->setting('retina'));
						}
					} else {
						$foundStickyImage = ze\file::itemStickyImageLink($width, $height, $url, $row['id'], $row['type'], $row['version'], $this->setting('width'), $this->setting('height'), $this->setting('canvas'), 0, $this->setting('retina'));
						
						if (!$foundStickyImage && $this->setting('fall_back_to_default_image') && $this->setting('default_image_id')) {
							$width = $height = $url = false;
							ze\file::imageLink($width, $height, $url, $this->setting('default_image_id'), $this->setting('width'), $this->setting('height'), $this->setting('canvas'), 0, $this->setting('retina'));
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
					if(ze::setting('mod_rewrite_enabled') && ze::setting('mod_rewrite_admin_mode')){
						$fullpath = true;
						$request = '?download=1';
						$item['Download_Now_Link'] = $this->linkToItemAnchor($row['id'], $row['type'], $fullpath, $request , $row['alias'], false, false, $stayInCurrentLanguage = true);
					}
					else {
						$fullpath = false;
						$request = 'download=1';
						$item['Download_Now_Link'] = $this->linkToItemAnchor($row['id'], $row['type'], $fullpath, $request, $row['alias'], false, false, $stayInCurrentLanguage = true);
					}
					
					$item['Download_Now_Full_Link'] = $this->escapeIfRSS(ze\link::absolute() . $link);
					$item['Download_Now_Link'] .= ' onclick="'. htmlspecialchars(ze\file::trackDownload($link)). '"';
					
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
					$item['Language'] = htmlspecialchars(ze\lang::name($row['language_id'], false));
				}
		
				if ($this->target_blank) {
					$item['Target_Blank'] = ' target="_blank"';
				}
				
				if ($showCategory) {
					$categoryId = static::getContentItemLowestPublicCategory($row['equiv_id'], $row['type'], $categories);
					if ($categoryId) {
						$item['Category'] = ze\lang::phrase('_CATEGORY_' . $categoryId);
						$item['Category_Id'] = $categoryId;
						$category = ze\row::get('categories', ['landing_page_equiv_id', 'landing_page_content_type', 'code_name'], $categoryId);
						$item['Category_code_name'] = $category['code_name'];
						if ($category['landing_page_equiv_id'] && $category['landing_page_content_type']) {
							$item['Category_Landing_Page_Link'] = ze\link::toItem($category['landing_page_equiv_id'], $category['landing_page_content_type']);
						}
					}
					
				}
				//Added info icon when viewed in admin mode
				if ((bool)ze\admin::id()) {
					$item['Logged_in_user_is_admin'] = true;
					$item['Content_panel_organizer_href_start'] = htmlspecialchars(ze\link::absolute() . 'zenario/admin/organizer.php#zenario__content/panels/content/refiners/content_type//'.$row['type'].'//'.$item['Id']);
					
				}
				
				$dontAddItem = false;
				if ($this->setting('hide_private_items') == 2) {
					//show content item but with disabled link / class
					//check if use can see target
					if(!ze\content::checkPerm($item['cID'], $item['cType'])) {
						$item['Link'] = false;
						$item['Full_Link'] = false;
						$item['Disabled'] = true;
					}
					
				} elseif ($this->setting('hide_private_items') == 3) {
					//call a static method to decide 
					
					//first check to see if they have access
					if(!ze\content::checkPerm($item['cID'], $item['cType'])) {
						$staticMethodClassName = $this->setting('hide_private_items_class_name');
						$staticMethodName = $this->setting('hide_private_items_method_name');
						$userId = ze\user::id();
						
						ze\module::inc($staticMethodClassName);
						$value = call_user_func(
							[
								$staticMethodClassName, 
								$staticMethodName
							],
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
				
				if ($this->setting('simple_access_cookie_required') && $this->setting('simple_access_cookie_alternate_page')) {
					if (empty($_COOKIE['SIMPLE_ACCESS'])) {
						$this->registerGetRequest('rci');
						
						$this->getCIDAndCTypeFromSetting($cID, $cType, 'simple_access_cookie_alternate_page');
						ze\content::langEquivalentItem($cID, $cType);
						$item['Link'] = $this->linkToItemAnchor($cID, $cType, false, "rci=".$item['Id'], false, false, false, $stayInCurrentLanguage = true);
						$item['Full_Link'] = $this->escapeIfRSS($this->linkToItem($cID, $cType, true, "rci=".$item['Id']));
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
	
	// Gets a content item's lowest level public category (return false if there are multiple)
	public static function getContentItemLowestPublicCategory($equivId, $cType, $allCategories) {
		$publicCategories = [];
		$sql = '
			SELECT c.name, c.id, c.parent_id, c.public
			FROM ' . DB_PREFIX . 'category_item_link l
			INNER JOIN ' . DB_PREFIX . 'categories c
				ON l.category_id = c.id
			WHERE l.equiv_id = ' . (int)$equivId . '
			AND l.content_type = "' . ze\escape::sql($cType) . '"';
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			if ($row['public']) {
				$publicCategories[$row['id']] = $row;
			}
		}
		
		$highestLevel = 0;
		$publicCategoryLevels = [];
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
	protected function lookForContent($paginationRequired = true) {
		
		$sql = $this->lookForContentSQL();
		
		if ($sql === false) {
			return false;
		}
		
		//Get a count of how many items we have to display
		$this->rows = ze\sql::fetchValue('SELECT COUNT(*) '. $sql);
		
		$page_size = $this->setting('maximum_results_number') ?: 999999;
		$offset = $this->setting('offset') ?: 0;
		
		$this->totalPages = (int) ceil($this->rows / $page_size);
		
		if ($this->page > $this->totalPages) {
			$this->page = $this->totalPages;
		}
		
		//Loop through each page to display, and add its details to an array of merge fields
		$this->pages = [];
		for ($i = 1; $i <= $this->setting('page_limit') && $i <= $this->totalPages; ++$i) {
			$this->pages[$i] = '&page='. $i;
		}
		if (!$paginationRequired) {
			$sql =
				$this->lookForContentSelect().
				$sql.
				$this->orderContentBy();
		} else {
			$sql =
				$this->lookForContentSelect().
				$sql.
				$this->orderContentBy().
				ze\sql::limit($this->page, $page_size, $this->setting('offset'));
		}
		return ze\sql::select($sql);
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
		
		$getFileIDs = [];
		if ($result = ze\sql::select($sql)) {
			while ($row = ze\sql::fetchAssoc($result)) {
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
					title="'. htmlspecialchars(ze\content::title($this->cID, $this->cType, $this->cVersion)). '" />';

		}
	}
	
	
	public function showSlot() {
		
		if (!(!empty($this->items) || ((bool)$this->setting('show_headings_if_no_items')))) {
			if (ze\priv::check()) {
				echo ze\admin::phrase('This Plugin will not be shown to visitors because there are no results.');
			}
			return;
		}
		
		$moreLink = false;
		if ($this->setting('show_more_link') && $this->getCIDAndCTypeFromSetting($cID, $cType, 'more_hyperlink_target')) {
			ze\content::langEquivalentItem($cID, $cType);
			$moreLink = $this->linkToItemAnchor($cID, $cType, false, '', false, false, false, $stayInCurrentLanguage = true);
		}
		
		$paginationLinks = [];
		
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
		//Download S3 
		/*if (isset($_GET['DownloadS3']) && $_GET['DownloadS3'] == 1) {
			$getS3Ids = $_GET['sids'];
			$fileName = '';
			$presignedUrl = '';	
			if ($getS3Ids) {
				$fileDetails = ze\row::get('files', ['filename','path'], $getS3Ids);
				if ($fileDetails['path']) {
					$fileName = $fileDetails['path'].'/'.$fileDetails['filename'];
				} else {
					$fileName = $fileDetails['filename'];
				}
				if ($fileName) {
					if (ze\module::inc('zenario_ctype_document')) {
						$presignedUrl = zenario_ctype_document::getS3FilePresignedUrl($fileName);
					}
					if ($presignedUrl) {
						echo '<script type="text/javascript">
								window.open("'. $presignedUrl .'", "_blank")

							 </script>';
					}
				}
			}
			
		}*/
		//To add Zip download link
		
		$downLoadpage = false;
		$linkResult = [];
		$fileName = $this->phrase('Prepare zip');
		$Link = '';
		$noContent = false;
		$mainLinkArr = [];
		$Link_To_Download_Page = false;
		$allIdsValue = '';
		if (isset($_GET['build']) && $_GET['build'] == 1 && $_GET['slotName'] == $this->slotName){
			$getIds = $_GET['ids'];
			$zipFiles = [];
			$zipFile = [];
			$zipFileSize = 0;
			if (($maxUnpackedSize = (int)ze::setting('max_unpacked_size'))<=0){
				$maxUnpackedSize = 64;
			} 
			$maxUnpackedSize*=1048576;
			if ($documentIDs = explode(",",$getIds)){
				foreach ($documentIDs as $dID){
					
					if ($zipFileSize + $this->getUnpackedFilesSize($dID) > $maxUnpackedSize) {
						$zipFiles[] = $zipFile;
						$zipFile = [];
						$zipFileSize = 0;
					}
					
					$zipFile[] = $dID;
					$zipFileSize += $this->getUnpackedFilesSize($dID);
					
				}
					if (!empty($zipFile)) {
						$zipFiles[] = $zipFile;
					}
			}
			$fileCtr = 0;
			$fileDocCtr = 0;
			foreach ($zipFiles as $zipFileids){
				if($zipFileids){
				$zipFileValue = implode(",",$zipFileids);
				$fileNameArr = [];
				$fileCtr++;
				if (sizeof($zipFiles) > 1) {
					$linkResult = $this->build($zipFileValue,$fileCtr);
				} else {
					$linkResult = $this->build($zipFileValue,0);
				}
				if ($linkResult[0]){
					if ($linkResult[1]){
						
						$downLoadpage = true;
						
						$fileNameArr['fileName'] = $linkResult[2];
						$fileNameArr['linkName'] = $linkResult[1];
						if (sizeof($zipFiles) > 1){
							$fileDocCtrValue = '';
							$fileCtrVal = 0;
							foreach($zipFileids as $filevaluectr)
							{
								$fileDocCtr++;
								$fileCtrVal++;
								if(sizeof($zipFileids) == $fileCtrVal)
									$fileDocCtrValue .= $fileDocCtr;
								else
									$fileDocCtrValue .= $fileDocCtr.', ';
								
							}
							$fileNameArr['labelName'] = $this->phrase('Download volume '.$fileCtr.' of zip archive (contains docs '.$fileDocCtrValue.'):');
						}
						else{
							$fileNameArr['labelName'] = $this->phrase('Download zip archive:');
						}
						
						
					} else {
						$noContent = true;
						
					}
				} else {
					if ((int)($_SESSION['admin_userid'] ?? false)){
						$downLoadpage = true;
						$fileDocCtr++;
						$filename='';
						if($zipFileids[0])
						{
							$fileID = (int)ze\row::get('content_item_versions','file_id',['id'=>$zipFileids[0],'type'=>'document']);
							$filename = ze\row::get('files','filename',['id'=>$fileID]);
						}

						$fileNameArr['errorMsg'] = 'For document '.$fileDocCtr.' ('.$filename.'), '.nl2br($linkResult[1]);
					}
				}
				
					$mainLinkArr[] = $fileNameArr;
				}
			}
		} else {
			$allIds = [];
			$ctr = 0;
			$Link_To_Download_Page = false;
			if ($result = $this->lookForContent(false)) {
				while($row = ze\sql::fetchAssoc($result)) {
					$allIds[]= $row['id'];
					$ctr++;
				}
			}
			if($ctr > 0 && $this->setting('zip_archive_enabled'))
			{
				$Link_To_Download_Page = true;
			}
			ksort($allIds);
			$allIdsValue = implode(",",$allIds);
			
		}
		
		$outer = [
			'More_Link' => $moreLink,
			'More_Link_Title' => $moreLinkText,
			'Pagination' => $pagination,
			'Pagination_Data' => $paginationLinks,
			'Results' => $this->rows,
			'RSS_Link' => $this->setting('enable_rss')? $this->escapeIfRSS($this->showRSSLink(true)) : null,
			'Title_With_Content' => $titleWithContent,
			'Title_With_No_Content' => ((bool)$this->setting('show_headings_if_no_items')) ? $titleWithNoContent: null,
			'Title_Tags' => $this->setting('heading_tags') ? $this->setting('heading_tags') : 'h1'
		];
		
		$inner = [
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
			'Item_Title_Tags' => $this->setting('titles_tags') ? $this->setting('titles_tags') : 'h2',
			'Show_Text_Preview' => (bool)$this->setting('show_text_preview'),
			'Show_Sticky_Image' => (bool) $this->setting('show_sticky_images'),
			'Show_RSS_Link' => (bool) $this->setting('enable_rss'),
			'Show_Title' => (bool)$this->setting('show_headings'),
			'Show_No_Title' => (bool)$this->setting('show_headings_if_no_items'),
			'Show_Category' => (bool)$this->setting('show_content_items_lowest_category') && (bool)ze::setting('enable_display_categories_on_content_lists'),
			'Content_Items_Equal_Height' => (bool)$this->setting('make_content_items_equal_height'),
			'Show_Category_Public' => (bool)$this->setting('show_category_name') && (bool)ze::setting('enable_display_categories_on_content_lists'),
			'Local_File_Link_Text' => ze::setting('local_file_link_text') ? ze::setting('local_file_link_text') : 'Download',
			'Local_File_Size' => ze::setting('local_file_size'),
			'Show_File_Size' => $this->setting('show_file_size'),
			'S3_File_Link_Text' => ze::setting('s3_file_link_text') ? ze::setting('s3_file_link_text') : 'Download original/large version',
			'Aws_Link' => ze::setting('aws_s3_support'),
			'Show_Format_And_Size' => ze::setting('show_format_and_size'),
			'Link_To_Download_Page' => $Link_To_Download_Page,
			'Anchor_Link' => $this->linkToItemAnchor($this->cID,$this->cType,true,'&build=1&slotName='.$this->slotName.'&ids=' . $allIdsValue),
			'Filename' => $fileName,
			'Link' => $Link,
			'FilenameArr' => ($fileNameArr ?? []),
			'Empty_Archive' => $noContent,
			'ARCHIVE_READY_FOR_DOWNLOAD' => $this->phrase('Download zip:'),
			'NO_CONTENT_ITEMS' => $this->phrase('No documents to download.'),
			'PREPARING_DOCUMENTS' => $this->phrase('Preparing your zip file...'),
			'DOWNLOAD_PREPARE_LABEL' => $this->phrase('Download all documents as a zip file:'),
			'Download_Page' => $downLoadpage,
			'Main_Link_Array' => $mainLinkArr,
			'Main_Link_Slot' => $this->slotName,
			'Request' => '&build=1&slotName='.$this->slotName.'&ids=' . $allIdsValue
		];
		
		//If the plugin is set to only show content in specific categories,
		//get a list of them.
		
		if (
			ze\admin::id()
			&& (
				($this->setting('category_filters_dropdown') == 'choose_categories_to_display_or_omit' && $this->setting('category'))
				|| $this->setting('category_filters_dropdown') == 'show_content_with_matching_categories'
			)
		) {
			//print("<pre>".print_r($this->setting('category'),true)."</pre>");
			if ($this->setting('category_filters_dropdown') == 'choose_categories_to_display_or_omit') {
				$categoryIds = $this->setting('category');
				$notEmpty = true;
			} elseif ($this->setting('category_filters_dropdown') == 'show_content_with_matching_categories') {
				$contentItemCategories = ze\category::contentItemCategories(ze::$cID, ze::$cType);
				
				if (is_array($contentItemCategories) && count($contentItemCategories) > 0) {
					$categoryIds = [];
					foreach ($contentItemCategories as $contentItemCategory) {
						$categoryIds[] = $contentItemCategory['id'];
					}
					$categoryIds = implode(" , ", $categoryIds);
					$notEmpty = true;
				}else {
					$notEmpty = false;
				}
			}
				
			if ($notEmpty) {
			    //Count the number of selected categories
			    if (count(explode(",", $categoryIds)) > 1) {
			    	$inner['More_Than_One'] = true;
			    	
			    	if ($this->setting('refine_type') == 'all_categories') {
			    		$inner['All_Or_Any'] = 'ALL of';
			    	} elseif ($this->setting('refine_type') == 'any_categories') {
			    		$inner['All_Or_Any'] = 'ANY of';
			    	}
			    }
				$allCurrentlySelectedCategories = [];
				If($this->setting('show_category_name')){
					$sql = '
						SELECT id,name,parent_id
						FROM ' . DB_PREFIX . 'categories
						WHERE public = 1 And id IN (' . ze\escape::in($categoryIds, true) . ')';
				}
				else{
								
					$sql = '
						SELECT id,name,parent_id
						FROM ' . DB_PREFIX . 'categories
						WHERE id IN (' . ze\escape::in($categoryIds, true) . ')';
				}
				$result = ze\sql::select($sql);
			
				while ($row = ze\sql::fetchAssoc($result)) {
					$currentParentId = false;
					$categoryName = [];
					$categoryName[] = $row['name'];
				
					if ($row['parent_id']) {
						$currentParentId = $row['parent_id'];
						while ($currentParentId) {
							$parent = ze\row::get('categories',['id','name','parent_id'],['id' => $currentParentId]);
							if ($parent) {
								$categoryName[] = $parent['name'];
								$currentParentId = $parent['parent_id'];
							} else {
								$currentParentId = false;
							}
						}
					}
				
					krsort($categoryName);
					$categoryName = implode (" / ", $categoryName);
					$allCurrentlySelectedCategories[] = $categoryName;
				}
			    If($this->setting('show_category_name') && ze::setting('enable_display_categories_on_content_lists')){
				 		$inner['All_Currently_Selected_Categories'] = $allCurrentlySelectedCategories;	
				}
				else{
						$inner['All_Currently_Selected_Categories'] = "";

				}
			}
		}
		
		$this->framework('Outer', $outer, $inner);
	}
	
	
	public function showRSS() {
		
		$this->framework(
			'Outer', 
			[
				'Description' => ze\escape::xml(ze\content::description($this->cID, $this->cType, $this->cVersion)),
				'Link' => ze\escape::xml($this->linkToItem($this->cID, $this->cType, true)),
				'RSS_Link' => ze\escape::xml($this->showRSSLink(true, false)),
				'Title' => ze\escape::xml(ze\content::title($this->cID, $this->cType, $this->cVersion)),
				'Results' => $this->rows,
			],
			[
				'RSS' => true,
				'Rows' => true,
				'RSS_Item' => $this->items
			]
		);
	
	}
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				$box['tabs']['pagination']['fields']['pagination_style']['values'] = 
					ze\pluginAdm::paginationOptions();
				
				foreach (ze\content::getContentTypes() as $cType) {
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
				$siteSettingsLink = "<a href='zenario/admin/organizer.php?fromCID=190&fromCType=html#zenario__administration/panels/site_settings//external_programs~.site_settings~tzip~k{%22id%22%3A%22external_programs%22}' target='_blank'>site settings</a>";
				$fields['zip_archive_name']['note_below'] = ze\admin::phrase(
						"Files up to a limit of 64 MB will be made available to visitor, value set in [[site_settings_link]]. Where total of file sizes exceeds this, multiple volumes will be offered.",
						['site_settings_link' => $siteSettingsLink]
				);
				
				$categoriesEnabled = ze::setting('enable_display_categories_on_content_lists');
				if (!$categoriesEnabled) {
					$fields['each_item/show_content_items_lowest_category']['disabled'] = true;
					$fields['each_item/show_content_items_lowest_category']['side_note'] = ze\admin::phrase('You must enable this option in your site settings under "Categories".');
					$values['each_item/show_content_items_lowest_category'] = false;
					$fields['first_tab/show_category_name']['disabled'] = true;
					$fields['first_tab/show_category_name']['side_note'] = ze\admin::phrase('You must enable this option in your site settings under "Categories".');
					$values['first_tab/show_category_name'] = false;
				}
				
				//Show a warning if "Choose categories to filter by" is selected but no categories have been picked
				if (
					!empty($values['first_tab/category_filters_dropdown'])
					&& $values['first_tab/category_filters_dropdown'] == 'choose_categories_to_display_or_omit'
					&& !$values['first_tab/category']
				) {
					$fields['first_tab/category']['error'] = $this->phrase("Please select at least one category.");
				}
				
				break;
		}
	}
	
	function getArchiveNameNoExtension($archiveName){
		$arr = explode(".",$archiveName);
		if (count($arr)>1){
			unset($arr[count($arr)-1]);
			return implode(".",$arr);
		} else {
			return $archiveName;
		}
	}
	
	function canZip(){
		if (ze\server::isWindows() || !ze\server::execEnabled() || !$this->getZIPExecutable()) {
			return false;
		}
		exec(escapeshellarg($this->getZIPExecutable()) .' -v',$arr,$rv);
		return ! (bool)$rv;
	}
	function getZIPExecutable(){
		return ze\server::programPathForExec(ze::setting('zip_path'), 'zip');
	}

	function build($cIds,$fileCtr){
		$archiveEmpty = true;
		$oldDir = getcwd();
		
		if (($maxUnpackedSize = (int)ze::setting('max_unpacked_size'))<=0){
			$maxUnpackedSize = 64;
		} 
		$maxUnpackedSize*=1048576;
		
		if ($this->canZIP()){
			if ($this->getUnpackedFilesSize($cIds ?? false)<=$maxUnpackedSize){
				if ($documentIDs = explode(",",($cIds ?? false))){
					$zipArchive = $this->getZipArchiveName();
					if($fileCtr>0)
					{
						$explodeZip = explode(".",$zipArchive);
						$zipArchive = $explodeZip[0].$fileCtr.'.'.$explodeZip[1];
					}
					
					if ($this->getArchiveNameNoExtension($zipArchive)){
						ze\cache::cleanDirs();
						$randomDir = ze\cache::createRandomDir(15, 'downloads', $onlyForCurrentVisitor = ze::setting('restrict_downloads_by_ip'));
						$contentSubdirectory = $this->getArchiveNameNoExtension($zipArchive);
						if (mkdir($randomDir . '/' . $contentSubdirectory)){
							foreach ($documentIDs as $ID){
								$version = ze\content::showableVersion($ID,'document',false,($_SESSION['admin_username'] ?? false),($_SESSION['extranetUserID'] ?? false));
								
								
								
								if ($filename=$this->getItemFilename($ID)){
									chdir($randomDir);
									
									$nextFileName = $this->getNextFileName($contentSubdirectory . '/' . $filename);
									if ($fileID = (int)ze\row::get('content_item_versions','file_id',['id'=>$ID,'type'=>'document'])){
										if (($path = ze\row::get('files','path',['id'=>$fileID]))
											&& ($filename = ze\row::get('files','filename',['id'=>$fileID]))){
												copy(ze::setting("docstore_dir") . "/" . $path . "/" . $filename,$nextFileName);
											if (($err=$this->addToZipArchive($zipArchive,$nextFileName))=="") {
												$archiveEmpty = false;
												if ((int)($_SESSION['extranetUserID'] ?? false)) {
													if (ze\module::inc('zenario_probusiness_features')) {
														zenario_probusiness_features::logUserAccess($_SESSION['extranetUserID'] ?? false,$ID,'document',$version );
													}
												}
											} else {
												$errors[] = $err;
											}
											unlink($nextFileName);
										}
									}
									chdir($oldDir);
								}
							}
							rmdir($randomDir . '/' . $contentSubdirectory);
							if (isset($errors)){
								return [false,implode('\n',$errors)];
							}elseif($archiveEmpty){
								return [true,[]];
							} else {
								return [true, $randomDir . $zipArchive,$zipArchive];
							}
						} else {
							return [false,'Error. Cannot create the documents subdirectory. This can be caused by either: <br/> 1) Incorrect downloads folder write permissions.<br/> 2) Incorrect archive name.'];
						}
					} else {
						return [false,'Error. Archive filename was not specified.'];
					}
				} else {
					return [true,[]];
				}
			} else {
				return [false,'the size of the file exceeds the '.(int)ze::setting('max_unpacked_size'). 'MB per volume limit.'];
			}
		} else {
			return [false,'Error. Cannot create ZIP archives using ' . $this->getZIPExecutable() . '.'];
		}
	}
	function getNextFileName($fileName){
		$i=1;

		if ($fileName){
			$arr=explode(".",$fileName);
			if (is_array($arr) && count($arr)>1){
				$extension =  $arr[count($arr)-1];
				unset($arr[count($arr)-1]);
			} else {
				$extension =  "";
			}
			$file = implode(".",$arr);
			for ($i=2;$i<1000;$i++){
				$nextName =  $file . ($i?"-".$i:"") . "." . $extension;
				if (!file_exists($nextName)){
					return $nextName;
				}
			}
		}

		return "";
	}
	function addToZipArchive($archiveName,$filenameToAdd){
		exec(escapeshellarg($this->getZIPExecutable()) . ' -r '. escapeshellarg($archiveName) . ' ' . escapeshellarg($filenameToAdd),$arr,$rv);
		if ($rv) {
			return 'Error. Adding the file ' . basename($filenameToAdd) . ' to the archive ' . basename($archiveName) . ' failed.';
		}
		return "";
	}


	function getUnpackedFilesSize($ids=''){
		$filesize = 0;
		if ($documentIDs = explode(",",$ids)){
			foreach ($documentIDs as $ID){
				$version = ze\content::showableVersion($ID,'document',false,($_SESSION['admin_username'] ?? false),($_SESSION['extranetUserID'] ?? false));
				if ($filename=$this->getItemFilename($ID)){
					if ($fileID = (int)ze\row::get('content_item_versions','file_id',['id'=>$ID,'type'=>'document'])){
						if (($path = ze\row::get('files','path',['id'=>$fileID]))
							&& ($filename = ze\row::get('files','filename',['id'=>$fileID]))){
							$filesize+=filesize(ze::setting("docstore_dir") . "/" . $path . "/" . $filename);
						}
					}
				}
			}
		}
		return $filesize;
	}
	function getItemFilename($cID) {
		return ze\row::get('content_item_versions', 'filename', ['id' => $cID]);
	}
	function getZipArchiveName(){
		return $this->zipArchiveName;
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
		
		switch ($path) {
			case 'site_settings':		
				
				if ($settingGroup == 'external_programs') {
					$box['tabs']['zip']['notices']['error']['show'] =
					$box['tabs']['zip']['notices']['success']['show'] = false;
					if (!empty($fields['zip/test']['pressed'])) {
						ze\site::setSetting('zip_path', $values['zip/zip_path'], $updateDB = false);
						
						if ($this->canZip()) {
							$box['tabs']['zip']['notices']['success']['show'] = true;
						} else {
							$box['tabs']['zip']['notices']['error']['show'] = true;
						}
					}
				}
				
				break;
		}
	}
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'site_settings':
			//If a user has entered the value, validate it.
			//If the user has not enterd anything then default value is 64, set in saveAdminBox.
			if ($box['setting_group'] == 'external_programs'){
				if(strlen($values['zip/max_unpacked_size']) > 0 && $values['zip/max_unpacked_size'] < 1 )
				{
					$fields['zip/max_unpacked_size']['error'] = ze\admin::phrase('Please enter an integer number, and a minimum of 1.');
				}
			}
				break;
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
		
		$documentIDs = [];
		if ($result = ze\sql::select($sql)) {
			while ($row = ze\sql::fetchAssoc($result)) {
				$documentIDs[] = $row['id'];
			}
		}
		
		return $documentIDs;
	}
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'site_settings':
			if ($box['setting_group'] == 'external_programs'){
				if (!$values['zip/max_unpacked_size']) {
					$values['zip/max_unpacked_size'] = 64;
				}
			}
				break;
			
		}
		
	}
}
