<?php
/*
 * Copyright (c) 2015, Tribal Limited
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
		
		return $sql;
	}
	
	
	//Adds table joins to the SQL query
	//Intended to be easily overwritten
	protected function lookForContentTableJoins() {
		$sql = "";
		
		//Filter by a category if requested
		if ($this->setting('category') && checkRowExists('categories', array('id' => $this->setting('category')))) {
			$sql .= "
			INNER JOIN ". DB_NAME_PREFIX. "category_item_link AS cil
			   ON cil.equiv_id = c.equiv_id
			  AND cil.content_type = c.type
			  AND cil.category_id = ". (int) $this->setting('category');
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
		
		$sql .= "
		  AND v.type = '". sqlEscape($this->setting('content_type')). "'";
		
		//Only return content in the current language
		$sql .= "
		  AND c.language_id = '". sqlEscape(session('user_lang')). "'";
		
		return $sql;
	}
	
	
	//Sort the Content
	//Intended to be easily overwritten
	protected function orderContentBy() {
		if ($this->setting('order') == 'Alphabetically') {
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
		$sql =
			sqlToSearchContentTable($this->setting('hide_private_items'), $this->setting('only_show'), $this->lookForContentTableJoins()).
			$this->lookForContentWhere();
		
		return $sql;
	}
	
	
	protected function addExtraMergeFields(&$row, &$item) {
	
	}
	
	protected function escapeAppropriately($text) {
		if (arrayKey($_REQUEST, 'method_call') == 'showRSS') {
			return XMLEscape($text);
		} else {
			return $this->escapeIfOldFramework($text);
		}
	}
	
	
	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = $this->setting('hide_private_items'), $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = $this->setting('show_sticky_images'), $clearByModuleData = false);
		
		if ($this->setting('data_field') == 'description') {
			$this->dataField = 'v.description';
		} elseif ($this->setting('data_field') == 'content_summary') {
			$this->dataField = 'v.content_summary';
		} else {
			$this->dataField = false;
		}
		
		$this->registerGetRequest('page', 1);
		
		//Pick a page to display
		$this->page = is_numeric(get('page'))? (int) get('page') : 1;
		
		
		//Loop through each item to display, and add its details to an array of merge fields
		$this->items = array();
		$oddOrEven = 'odd';
		
		if ($result = $this->lookForContent()) {
			while($row = sqlFetchAssoc($result)) {
				
				$item = array();
				
				if ($this->dataField == 'v.description') {
					$item['Excerpt_Text'] = $this->escapeAppropriately($row['content_table_data']);
				
				} elseif ($this->dataField == 'v.content_summary') {
					if (arrayKey($_REQUEST, 'method_call') == 'showRSS') {
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
					imageLink($width, $height, $url, $row['writer_image_id'], $this->setting('author_width'), $this->setting('author_height'), $this->setting('author_canvas'), (int)$this->setting('author_offset'));
					$item['Author_Image_Src'] = $url;
					$item['Author_Image_Alt'] = $row['alt_tag'];
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
						arrayKey($_REQUEST, 'method_call') == 'showRSS');
				}
				
				
				$width = $height = $url = false;
				if ($this->setting('show_sticky_images')) {
					if ($row['type'] == 'picture') {
						//Legacy code for Pictures
						if ($imageId = getRow("versions", "file_id", array("id" => $row['id'], 'type' => $row['type'], "version" => $row['version']))) {
							imageLink($width, $height, $url, $imageId, $this->setting('width'), $this->setting('height'), $this->setting('canvas'));
						}
					} else {
						itemStickyImageLink($width, $height, $url, $row['id'], $row['type'], $row['version'], $this->setting('width'), $this->setting('height'), $this->setting('canvas'));
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
				}
				
				$this->getStyledExtensionIcon(pathinfo($row['filename'], PATHINFO_EXTENSION), $item);
					
				
				if ($row['type'] == 'document') {
					$link = $this->linkToItem($row['id'], $row['type'], false, 'download=1', $row['alias']);
					$item['Download_Page_Link'] = $item['Link'];
					$item['Download_Page_Full_Link'] = $item['Full_Link'];
					$item['Download_Now_Link'] = $this->linkToItemAnchor($row['id'], $row['type'], false, 'download=1', $row['alias']);
					$item['Download_Now_Full_Link'] = $this->escapeAppropriately(absCMSDirURL() . $link);
					$item['Download_Now_Link'] .= ' onclick="'. htmlspecialchars(trackFileDownload($link)). '"';
					
					if (!$this->setting('use_download_page')) {
						$item['Link'] = $item['Download_Now_Link'];
						$item['Full_Link'] = $item['Download_Now_Full_Link'];
					}
				}
				
				
				$this->addExtraMergeFields($row, $item);
				
				$this->items[$item['Id']] = $item;
			}
		}
		
		if (!$this->isVersionControlled && !$this->eggId && checkPriv()) {
			$this->showInEditMode();
		}
		
		return !empty($this->items) || ((bool)$this->setting('show_headings_if_no_items'));
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
			paginationLimit($this->page, $this->setting('page_size'));
		
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
				
				if (!is_array(arrayKey($box['tabs']['first_tab']['fields']['content_type'], 'values'))) {
					$box['tabs']['first_tab']['fields']['content_type']['values'] = array();
				}
				foreach (getContentTypes() as $cType) {
					switch (arrayKey($cType,'content_type_id')){
						case 'recurringevent':
						case 'event':
							break;
						default:
							$box['tabs']['first_tab']['fields']['content_type']['values'][$cType['content_type_id']] =
								htmlspecialchars($cType['content_type_name_en']);
							break;
					}
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
			'page_modes' => array('edit' => true, 'layout' => true));
		
		$this->fillAdminSlotControlsShowFilterSettings($controls, $key);
		
		$controls['actions']['create_a_content_item'] = array(
			'ord' => 50,
			'label' => adminPhrase('Create a Content Item'),
			'page_modes' => array('edit' => true),
			'onclick' => "
				zenarioAB.open('zenario_content', ". json_encode($key). ");
				return false;");
		
	}
	
	protected function fillAdminSlotControlsShowFilterSettings(&$controls, &$key) {
		$key = array();
		
		if ($this->setting('content_type') == 'all') {
			$controls['notes']['filter_settings']['label'] .=
				adminPhrase('Source Content Type: All Content Types');
		
		} else {
			$key['cType'] = $this->setting('content_type');
			$controls['notes']['filter_settings']['label'] .=
				adminPhrase('Source Content Type: [[ctype]]',
					array('ctype' => htmlspecialchars(getContentTypeName($this->setting('content_type')))));
		}
		
		$this->fillAdminSlotControlsShowFilterSettingsCategories($key,$controls);
		
		$key['target_language_id'] = cms_core::$langId;
	}
	
	public function fillAdminSlotControlsShowFilterSettingsCategories(&$key,&$controls) {
		if ($this->setting('category')) {
			$key['target_categories'] = $this->setting('category');
			
			$first = true;
			foreach(explode(',', $this->setting('category')) as $catId) {
				if ($name = getCategoryName($catId)) {
					
					if ($first) {
						$first = false;
						$controls['notes']['filter_settings']['label'] .=
							'<br/>'.
							adminPhrase('Category:').
							' ';
					} else {
						$controls['notes']['filter_settings']['label'] .= ', ';
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