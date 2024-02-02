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

class zenario_blog_news_list extends zenario_content_list {
	
	protected $categoryCount = [];
	protected $totalItemCount = 0;
	
	protected $catId = false;
	
	public function init() {
		$display = parent::init();
		
		if ($this->setting('enable_user_category_filter')) {
			if ($catCodeName = ze::request('category')) {
				if (ze\row::exists('categories', ['code_name' => $catCodeName])) {
					$this->catId = ze\row::get('categories', 'id', ['code_name' => $catCodeName]);
					$this->registerGetRequest('category');
					$this->setPageTitle(\ze::$pageTitle . ": " . ze\lang::phrase('_CATEGORY_' . $this->catId));
				}
			}
		}
		
		if ($this->setting('show_count_on_user_category_filter')) {
			//get category count
			$hidePrivateItems = false;
			if ($this->setting('hide_private_items') == 1) {
				$hidePrivateItems = true;
			}
		
			$tableJoins = parent::lookForContentTableJoins() . " 
					LEFT JOIN ". DB_PREFIX. "category_item_link AS cil_count 
					ON cil_count.equiv_id = c.equiv_id
					AND cil_count.content_type = c.type";
		
			$sql =
				ze\content::sqlToSearchContentTable(
					$hidePrivateItems, $this->setting('only_show'), 
					$tableJoins
				).
				$this->lookForContentWhere();
		
			$result = ze\sql::fetchAssocs('SELECT cil_count.*, c.tag_id '. $sql);
			
			$tagIds = [];
			foreach ($result as $contentCat) {
				if (isset($this->categoryCount[$contentCat['category_id']])) {
					$this->categoryCount[$contentCat['category_id']]++;
				} else {
					$this->categoryCount[$contentCat['category_id']] = 1;
				}
				$tagIds[$contentCat['tag_id']] = 1;
			}
			
			//Keep track of the total number of content items
			$this->totalItemCount = count($tagIds);
		}
		
		return true;
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				$box['tabs']['pagination']['fields']['pagination_style']['values'] = 
					ze\pluginAdm::paginationOptions();
				
				unset($box['tabs']['first_tab']['fields']['content_type']['value']);
				foreach ($box['tabs']['first_tab']['fields']['content_type']['values'] as $key => $cType) {
					if ($key != "blog" && $key != "news") {
						unset($box['tabs']['first_tab']['fields']['content_type']['values'][$key]);
					}
				}
				
				//Catch the case where this module is running, but neither Blog nor News content items are enabled
				if (!ze\module::isRunning('zenario_ctype_blog') && !ze\module::isRunning('zenario_ctype_news')) {
					$fields['first_tab/content_type']['disabled'] = true;
				}
				
				$categoriesEnabled = ze::setting('enable_display_categories_on_content_lists');
				if (!$categoriesEnabled) {
					$fields['each_item/show_content_items_lowest_category']['disabled'] = true;
					$fields['each_item/show_content_items_lowest_category']['side_note'] = ze\admin::phrase('You must enable this option in your site settings under "Categories".');
					$values['each_item/show_content_items_lowest_category'] = false;
				}
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				//Catch the case where this module is running, but neither Blog nor News content items are enabled
				if (!ze\module::isRunning('zenario_ctype_blog') && !ze\module::isRunning('zenario_ctype_news')) {
					$fields['first_tab/content_type']['error'] = $this->phrase('Please enable content type "Blog" and/or "News" module to use this plugin.');
				}
				break;
		}
	}
	
	protected function lookForContentTableJoins() {
		$sql = parent::lookForContentTableJoins();
		
		//Filter by a categories if requested
		if ($this->setting('enable_user_category_filter') && $catCodeName = ze::request('category')) {
			if (ze\row::exists('categories', ['code_name' => $catCodeName])) {
				$catId = ze\row::get('categories', 'id', ['code_name' => $catCodeName]);
				$sql .= "
				INNER JOIN ". DB_PREFIX. "category_item_link AS cil_user_". (int) $catId. "
				   ON cil_user_". (int) $catId. ".equiv_id = c.equiv_id
				  AND cil_user_". (int) $catId. ".content_type = c.type
				  AND cil_user_". (int) $catId. ".category_id = ". (int) $catId;
			}
		}
		return $sql;
	}
	
	protected function buildTree(array &$categories, $parentId = 0) {
		$branch = array();
		foreach ($categories as $cat) {
			if ($cat['parent_id'] == $parentId) {
				$children = $this->buildTree($categories, $cat['id']);
				if ($children) {
					$cat['children'] = $children;
				}
				$branch[$cat['id']] = $cat;
				unset($categories[$cat['id']]);
			}
		}
		return $branch;
	}
	
	public function showSlot() {
		$categoriesTree = [];
		$selectedCategoryName = "All Categories";
		$topLevelParent = false;
		
		if ($this->setting('enable_user_category_filter')) {
			$categories = ze\row::getAssocs('categories', ['name', 'id', 'parent_id', 'code_name'], ["public" => 1]);
			foreach ($categories as &$cat) {
				$cat['link'] = $this->refreshPluginSlotJS('category=' . $cat['code_name']);
				$cat['visitorName'] = ze\lang::phrase('_CATEGORY_' . $cat['id']);
				$cat['count'] = $this->categoryCount[$cat['id']] ?? 0;
			}
		
			if($this->catId && isset($categories[$this->catId])) {
				$selectedCategoryName = $categories[$this->catId]['visitorName'];
				
				//If the selected category is not a level 1 category, find the top-level parent.
				$selectedCategory = $this->catId;
				$iteration = 1;
				while ($currentParent = $categories[$selectedCategory]['parent_id']) {
					$selectedCategory = $currentParent;
					$iteration++;
					if ($iteration > 500) {
						break;
					}
				}
				
				$topLevelParent = $selectedCategory;
			}
		
			//Get the visitor name column...
			$catVisitorName = array_column($categories, 'visitorName');
			//... and sort the categories array on it.
			array_multisort($catVisitorName, SORT_ASC, $categories);
			
			$categoriesTree = $this->buildTree($categories);
		}
		
		$allBlogPostsLink = $this->refreshPluginSlotJS();
		
		if (!(!empty($this->items) || ((bool)$this->setting('show_headings_if_no_items'))) && !(bool)$this->setting('enable_user_category_filter')) {
			if (ze\priv::check()) {
				echo ze\admin::phrase('This plugin will not be shown to visitors because there are no results.');
			}
			return;
		}
		
		$moreLink = false;
		if ($this->setting('show_more_link') && $this->getCIDAndCTypeFromSetting($cID, $cType, 'more_hyperlink_target')) {
			ze\content::langEquivalentItem($cID, $cType);
			$moreLink = $this->linkToItemAnchor($cID, $cType);
		}
		
		//Remember the category filter in pagination links
		if ($catCodeName = ze::request('category')) {
			foreach ($this->pages as $i => $page) {
				$this->pages[$i] .= '&category=' . urlencode($catCodeName);
			}
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
				$titleWithContent = $this->phrase($titleWithContent, ['category' => $selectedCategoryName]);
			}
		}
		$titleWithNoContent = '';
		$titleWithNoContent = htmlspecialchars($this->setting('heading_if_no_items'));
			
		if (!$this->isVersionControlled && $this->setting('translate_text')) {
			$titleWithNoContent = $this->phrase($titleWithNoContent);
		}
		
		$moreLinkText = '';
		if ($moreLink) {
			$moreLinkText = htmlspecialchars($this->setting('more_link_text'));
			
			if (!$this->isVersionControlled && $this->setting('translate_text')) {
				$moreLinkText = $this->phrase($moreLinkText);
			}
		}
		
		foreach($this->items as &$item) {
			if (isset($item['Category_Id'])) {
				$item['Category_Link'] = $this->refreshPluginSlotJS('category=' . $item['Category_code_name']);
			}
		}
		
		$this->framework(
			'Outer', 
			[
				'More_Link' => $moreLink,
				'More_Link_Title' => $moreLinkText,
				'More_Phrase' => $this->phrase('More...'),
				'Pagination' => $pagination,
				'Pagination_Data' => $paginationLinks,
				'Results' => $this->totalItemCount,
				'RSS_Link' => $this->setting('enable_rss')? $this->escapeIfRSS($this->showRSSLink(true)) : null,
				'Title_With_Content' => $titleWithContent,
				'Title_With_No_Content' => $titleWithNoContent,
				'Title_Tags' => $this->setting('heading_tags') ? $this->setting('heading_tags') : 'h1'
			],
			[
				'Slot' => true,
				'More' => (bool) $moreLink,
				'No_Rows' => empty($this->items),
				'Rows' => !empty($this->items),
				'Row' => $this->items,
				'Show_Date' => $this->setting('show_dates'),
				'Show_Author' => $this->setting('show_author'),
				
				//As of 06 Sept 2021, the "Show writer's photo" setting is disabled.
				//Commenting out the code on 08 Nov 2022 in Blog And News List module to match CSL.
				//'Show_Author_Image' => $this->setting('show_author_image'),
				
				'Show_Excerpt' => (bool) $this->dataField,
				'Show_Item_Title' => (bool)$this->setting('show_titles'),
				'Item_Title_Tags' => $this->setting('titles_tags') ? $this->setting('titles_tags') : 'h2',
				'Show_Featured_Image' => (bool) $this->setting('show_featured_image'),
				'Show_RSS_Link' => (bool) $this->setting('enable_rss'),
				'Show_Title' => (bool)$this->setting('show_headings'),
				'Show_No_Title' => (bool)$this->setting('show_headings_if_no_items'),
				'Show_Category' => (bool)$this->setting('show_content_items_lowest_category') && (bool)ze::setting('enable_display_categories_on_content_lists'),
				'UserCanFilterByCategory' => (bool)$this->setting('enable_user_category_filter'),
				'ShowCountOnFilter' => (bool)$this->setting('show_count_on_user_category_filter'),
				'OnlyShowCategoryWithItems' => (bool)$this->setting('only_show_category_with_items'),
				'Categories' => $categoriesTree,
				'SelectedCategory' => $this->catId,
				'SelectedCategoryName' => $selectedCategoryName,
				'TopLevelParent' => $topLevelParent,
				'AllBlogPostsLinks' => $allBlogPostsLink,
				'Content_Items_Equal_Height' => (bool)$this->setting('make_content_items_equal_height')
			]
		);
		
	}
	
}
