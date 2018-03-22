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



class zenario_abstract_list extends zenario_abstract_manager {
	
	protected static $requestVar = 'id';
	
	protected $merge = [
		'paginationData' => [],
		'paginationHTML' => '',
		'rows' => [],
		'show' => false
	];
	
	protected $usePagination = false;
	protected $page = 0;
	protected $pageSize = 0;
	protected $pageLimit = 0;
	protected $totalPages = 0;
	protected $rows = 0;
	protected $pages = [];
	
	//Set some basic parameters for pagination
	//Intended to be easily overwritten
	protected function setupSettings() {
		$this->usePagination = false;
		$this->pageSize = 10;
		$this->pageLimit = 10;
		//$this->pageSize = $this->setting('page_size');
		//$this->pageLimit = $this->setting('page_limit');
	}

	//Return the date format to use for any dates we see
	//Intended to be easily overwritten
	protected function dateFormat() {
		return false;
		//return $this->setting('date_format')
	}
	
	//Returns a list of fields needed by the Plugin
	//Intended to be easily overwritten
	protected function lookForContentSelect() {
		$sql = "
			SELECT
				cd.*";
		
		return $sql;
	}
	
	
	//The table to select from
	//Intended to be easily overwritten
	protected function lookForContentFrom() {
		$sql = "
			FROM `". ze\escape::sql(DB_NAME_PREFIX. static::table()). "` AS cd";
		
		return $sql;
	}
	
	
	//Adds table joins to the SQL query
	//Intended to be easily overwritten
	protected function lookForContentTableJoins() {
		$sql = "";
		
		return $sql;
	}
	
	
	//Adds to the WHERE clause of the SQL query
	//Intended to be easily overwritten
	protected function lookForContentWhere() {
		$sql = "";
		
		return $sql;
	}
	
	
	//Sort the Content
	//Intended to be easily overwritten
	protected function orderContentBy() {
		$sql = "";
		
		if ($labelDetails = ze\dataset::labelFieldDetails(static::getDatasetId())) {
			$sql = "
				ORDER BY `". ze\escape::sql($labelDetails['db_column']). "`,  `". ze\escape::sql($labelDetails['id_column']). "`";
		}
		
		return $sql;
	}
	
	//Add specific rules to format a row
	//Intended to be easily overwritten
	protected function formatRow($id, &$values, &$ids) {
		
		//...your PHP code...//
	}
	
	
	
	//Runs the SQL statement that will return a list of content items
	protected function lookForContent() {
		
		$limitSql = "";
		
		if ($this->usePagination) {
			$this->registerGetRequest('page', 1);
		
			//Pick a page to display
			$this->page = is_numeric($_REQUEST['page'] ?? false)? (int) ($_REQUEST['page'] ?? false) : 1;
			
			//Get a count of how many items we have to display
			$sql = "
				SELECT COUNT(*)".
				$this->lookForContentFrom().
				$this->lookForContentTableJoins().
				$this->lookForContentWhere();
			$result = ze\sql::select($sql);
			list($this->rows) = ze\sql::fetchRow($result);
			
			$importantGetRequests = ze\link::importantGetRequests();
			
			$this->totalPages = (int) ceil($this->rows / $this->pageSize);
			for ($i = 1; $i <= $this->pageLimit && $i <= $this->totalPages; ++$i) {
				$this->pages[$i] = '&page='. $i;
				foreach ($importantGetRequests as $requestName => $requestValue) {
					if ($requestName != 'page') {
						$this->pages[$i] .= '&' . $requestName . '=' . $requestValue;
					}
				}
			}
			
			$limitSql = ze\sql::limit($this->page, $this->pageSize);
		
			$this->pagination('pagination_style', $this->page, $this->pages, $this->merge['paginationHTML'], $this->merge['paginationData']);
		}
		
		$sql =
			$this->lookForContentSelect().
			$this->lookForContentFrom().
			$this->lookForContentTableJoins().
			$this->lookForContentWhere().
			$this->orderContentBy().
			$limitSql;
		
		return ze\sql::select($sql);
	}
	
	
	
	public function addToPageHead() {
		
		//...your PHP code...//
	}

	public function addToPageFoot() {
		
		//...your PHP code...//
	}

	public function init() {
		$this->setupSettings();
		
		$idColumn = ze\row::idColumnOfTable(static::table(), true);
		
		if ($result = $this->lookForContent()) {
			while($values = ze\sql::fetchAssoc($result)) {
				if (!empty($values[$idColumn])) {
					
					$ids = [];
					static::formatRecord($values[$idColumn], $values, $ids, $this->dateFormat());
					$this->formatRow($values[$idColumn], $values, $ids);
				
					$this->merge['show'] = true;
					$this->merge['rows'][] = ['values' => $values, 'ids' => $ids, 'id' => $values[$idColumn]];
				}
			}
		}
		
		return true;
	}

	public function showSlot() {
		$this->twigFramework($this->merge);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

	
	
	
	
	  /////////////////////////////////////
	 //  Methods called by Admin Boxes  //
	/////////////////////////////////////
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//...your PHP code...//
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		//...your PHP code...//
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
	
	
	
	
	  ///////////////////////////////////
	 //  Methods called by Organizer  //
	///////////////////////////////////
	
	
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		//...your PHP code...//
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		//...your PHP code...//
	}

	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		//...your PHP code...//
	}
}