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

class zenario_forum_search extends zenario_forum {
	
	protected $results = false;
	
	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$this->page = (int) ($_REQUEST['comm_page'] ?? 1) ?: 1;
		
		$this->mode = 'showSearch';
		$this->posts = $this->runSearch($_REQUEST['searchString'] ?? false, $this->setting('show_private_items'), $this->setting('hide_private_items'));
		
		return true;
	}
	
	
	
	//Run a search for the User's search string on the forums
	function runSearch($searchString, $showPrivateItems, $hidePrivateItems) {
		$posts = [];

		if (!$searchString) {
			return false;
		}
		
		$sqlW = "";
		$sqlF = "
			SELECT
				up.id,
				up.thread_id,
				up.forum_id,
				f.forum_content_id,
				f.forum_content_type,
				f.thread_content_id,
				f.thread_content_type,";
		
		$first = true;
		foreach (ze\content::searchtermParts($searchString) as $searchTerm => $searchTermType) {
			$wildcard = "";
			$booleanSearch = "";
			if ($searchTermType == 'word') {
				$wildcard = "*";
				$searchTermTypeWeighting = 1;
			} else {
				$booleanSearch = " IN BOOLEAN MODE";
				$searchTermTypeWeighting = 2;
			}
			
			foreach (['t.title' => 2, 'up.message_text' => 1] as $field => $weighting) {
				if ($first) {
					$first = false;
				} else {
					$sqlF .= "
						+";
					$sqlW .= "
						  OR";
				}
				
				$sqlW .= " MATCH (". $field. ") AGAINST ('". ze\escape::sql($searchTerm).      $wildcard. "' IN BOOLEAN MODE)";
				$sqlF .= " MATCH (". $field. ") AGAINST ('". ze\escape::sql($searchTerm). "'". $booleanSearch. ") * ". $searchTermTypeWeighting. " * ". $weighting;
			}
		}
		
		$sqlF .= "  AS relevance";
		
		$joinSQL = "
			INNER JOIN ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "forums AS f
			   ON c.id = f.forum_content_id
			  AND c.type = f.forum_content_type
			INNER JOIN ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "threads AS t
			   ON f.id = t.forum_id
			INNER JOIN ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_posts AS up
			   ON t.id = up.thread_id";
		
		if ($showPrivateItems) {
			$sql = ze\content::sqlToSearchContentTable($hidePrivateItems, '', $joinSQL, $includeSpecialPages = true);
		} else {
			$sql = ze\content::sqlToSearchContentTable(true, 'public', $joinSQL, $includeSpecialPages = true);
		}
		
		
		//Only select rows in the Visitor's language, and that match the search terms
		$sql .= "
			  AND c.language_id = '". ze\escape::sql(ze::$langId). "'
			  AND (". $sqlW. ")
			ORDER BY relevance DESC, up.id";
		
		$result = ze\sql::select($sqlF. $sql);
		
		//Collect the post_id of the results before we apply pagination
		$threadsFound = [];
		while($post = ze\sql::fetchAssoc($result)) {
			
			if (!$post['thread_content_id']) {
				$post['thread_content_id'] = $post['forum_content_id'];
				$post['thread_content_type'] = $post['forum_content_type'];
			}
			
			//Check to see if the current user has access to the forum...
			//if (!isset($forumsFound[$post['forum_id']])) {
				//$forumsFound[$post['forum_id']] = ze\content::checkPerm($post['thread_content_id'], $post['thread_content_type']);
			//}
			
			//...don't let them see the result if so
			//if (!$forumsFound[$post['forum_id']]) {
				//continue;
			//}
			
			//Check to see if we've already found a better match inside this thread, and don't show it if so
			if (isset($threadsFound[$post['thread_id']])) {
				continue;
			}
			$threadsFound[$post['thread_id']] = true;
			
			$posts[] = $post['id'];
		}
		
		//Apply pagination if there were results
		if ($this->results = count($posts)) {
			$this->loadPagination();
		} else {
			$posts = false;
		}
		
		return $posts;
	}
	
	
	//Show the search results
	function showSearch() {
		//Note: this form should be changed to work via GET when that option is added to the API
		$this->sections['Search'] = ['Open_Form' => $this->openForm(), 'Close_Form' => $this->closeForm()];
		
		if (($_REQUEST['searchString'] ?? false) && !$this->posts) {
			$this->sections['Search_No_Results'] = true;
		}
		
		$this->mergeFields['Post_Class'] = 'list_of_search_results';
		$this->sections['Post'] = [];
		
		if ($this->posts) {
			for ($i = ($this->page - 1) * $this->pageSize, $j = 0; $i < $this->results && $j < $this->pageSize; ++$i, ++$j) {
				
				//For each post within our current pagination range, look up the details of that post/thread and display that row
				$sql = "
					SELECT
						up.thread_id,
						f.forum_content_id,
						f.forum_content_type,
						f.thread_content_id,
						f.thread_content_type,
						up.date_posted,
						up.message_text,
						t.title
					FROM ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_posts AS up
					INNER JOIN ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "threads AS t
					   ON t.id = up.thread_id
					INNER JOIN ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "forums AS f
					   ON f.id = up.forum_id
					WHERE up.id = ". (int) $this->posts[$i];
				
				$result = ze\sql::select($sql);
				if ($post = ze\sql::fetchAssoc($result)) {
					$mergeFields = [];
					
					if (!$post['thread_content_id']) {
						$post['thread_content_id'] = $post['forum_content_id'];
						$post['thread_content_type'] = $post['forum_content_type'];
					}
					
					$mergeFields['Show_Forum_Title_With_Link'] = true;
					$mergeFields['Show_Thread_Title_With_Link'] = true;
					$mergeFields['Forum_Title'] = htmlspecialchars(ze\content::title($post['forum_content_id'], $post['forum_content_type']));
					$mergeFields['Forum_Link'] =
						htmlspecialchars($this->linkToItem($post['forum_content_id'], $post['forum_content_type']));
					
					$mergeFields['Thread_Title'] = htmlspecialchars($post['title']);
					$mergeFields['Thread_Link'] =
						htmlspecialchars($this->linkToItem($post['thread_content_id'], $post['thread_content_type'], false, '&forum_thread='. $post['thread_id']));
					
					$mergeFields['Date_Posted'] = ze\date::formatDateTime($post['date_posted'], $this->setting('date_format'));
					$mergeFields['Post_Text'] = $post['message_text'];
					
					//Limit the size of the extract
					$mergeFields['Post_Text'] = str_replace("\n", '`n', str_replace('`', '`t', $mergeFields['Post_Text']));
					$mergeFields['Post_Text'] = wordwrap($mergeFields['Post_Text'], $this->setting('excerpt_length'));
					$mergeFields['Post_Text'] = explode("\n", $mergeFields['Post_Text'], 2);
					$mergeFields['Post_Text'] = $mergeFields['Post_Text'][0];
					$mergeFields['Post_Text'] = str_replace('`t', '`', str_replace('`n', "\n", $mergeFields['Post_Text']));
					
					//Check the HTML is still valid
					$mergeFields['Post_Text'] = zenario_anonymous_comments::sanitiseHTML($mergeFields['Post_Text'], true, true);
					
					$this->sections['Post'][] = $mergeFields;
				}
			}
		}
		
		$this->framework('Posts', $this->mergeFields, $this->sections);
	}



	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				$box['tabs']['pagination']['fields']['pagination_style_search']['values'] = 
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

	
	public function fillAdminSlotControls(&$controls) {
	}

}