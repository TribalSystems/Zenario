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

class zenario_event_listing extends ze\moduleBaseClass {

    protected $data = [];
    
	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = true, $clearByModuleData = true);
		
		$this->page = is_numeric($_GET['page'] ?? false)? (int) ($_GET['page'] ?? false) : 1;
		
		switch ($this->setting('period_mode')){
			case 'date_range':
				$periodName = 'date_range';
				$periodShift = 0;
				break;
			case 'all_time':
				$periodName = 'all_time';
				$periodShift = 0;
				break;
			case 'year_period';
				$periodName = 'year';
				if ($this->isLastDayOfYear()) {
					$periodShift = 1;
				} else {
					$periodShift = 0;
				}
				break;
			case 'month_period':
				if ($this->setting('month_period_operator')=='eq'  || $this->setting('month_period_operator')=='ge') {
					$periodName = 'month_' . $this->setting('month_period_operator');
					$periodShift =	(int) $this->setting('month_period_value');
				} else {
					$periodName = 'month_eq';
					$periodShift =	0;
				}
				break;
			case 'week_period':
				$periodName = 'week';
				if ($this->isLastDayOfWeek()) {
					$periodShift = 1;
				} else {
					$periodShift = 0;
				}
				break;
			case 'today_only':
			default:
				$periodName = 'today';
				$periodShift = 0;
				break;
		}

		$eventRows = [];
		if ($sql = $this->buildQuery($periodName, $periodShift)){
			
			//Get a count of how many items we have to display
			$result = ze\sql::select('SELECT COUNT(*) FROM ('. $sql . ') A');
			list($rows) = ze\sql::fetchRow($result);
			
			$totalPages = (int) ceil($rows / $this->setting('page_size'));
			
			//Loop through each page to display, and add its details to an array of merge fields
			$pages = [];
			for ($i = 1; $i <= $this->setting('page_limit') && $i <= $totalPages; ++$i) {
				$pages[$i] = '&page='. $i;
			}
			
			$defaultImageURL = false;
			if ($this->setting('show_sticky_images') && $this->setting('fall_back_to_default_image')) {
                $width = 0;
                $height = 0;
			    ze\file::imageLink($width, $height, $defaultImageURL, $this->setting('default_image_id'), $this->setting("width"), $this->setting("height"), $this->setting('canvas'), 0, $this->setting('retina'));
			}
			
			if ($showCategory = $this->setting('show_content_items_category') && ze::setting('enable_display_categories_on_content_lists')) {
				$categories = ze\row::getAssocs('categories', ['name', 'id', 'parent_id', 'public'], []);
			}
			
			$result = ze\sql::select($sql . ze\sql::limit($this->page, $this->setting('page_size'), $this->setting('offset')));
			while($row = ze\sql::fetchAssoc($result)){
			    $eventRow = [];
				$eventRow['Link_To_Event'] = $this->linkToItem($row['id'], 'event');
				
				if ($this->setting('show_sticky_images')) {
				    $stickyImageURL = $defaultImageURL;
				    
                    $url = '';
                    $width = 0;
                    $height = 0;
                    ze\file::imageLink($width, $height, $url, $row['feature_image_id'], $this->setting("width"), $this->setting("height"), $this->setting('canvas'), 0, $this->setting('retina'));
                    
                    if ($url) {
                        $stickyImageURL = $url;
                    }
                    if ($stickyImageURL) {
                        $eventRow['Sticky_image_HTML_tag'] =  '<img src="' . $stickyImageURL . '"/>';
                    }
				}
				
				if ($this->setting('show_event_title')){
					$eventRow['Event_Title'] = htmlspecialchars($row['title']);
				}

				if ($this->setting('show_event_summary')){
					$eventRow['Event_Description'] = $row['content_summary'];
					ze\ring::displayHTMLAsPlainText($eventRow['Event_Description'],$this->setting('excerpt_length'));
					$eventRow['Event_Description'] = nl2br($eventRow['Event_Description']);
				}

				if ($this->setting('show_location_name') && ze::setting('zenario_ctype_event__location_field') != 'hidden') {
					if (ze::setting('zenario_ctype_event__location_text') && $row['location']) {
						$eventRow['Event_location_name'] = $row['location'];
					} elseif (($locationId = $row['location_id'] ?? false) 
						&& ze\module::inc('zenario_location_manager')
						&& ($location = zenario_location_manager::getLocationDetails($locationId))
					) {
						$eventRow['Event_location_name'] = $location['description'];
					}
				}
				
				if ($this->setting('show_location_city') && $row['location_id']) {
					$eventRow['Event_location_city'] = ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations', 'city', ['id' => $row['location_id']]);
				}
				
				if ($this->setting('show_location_country') && $row['location_id']) {
					$countryId = ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations', 'country_id', ['id' => $row['location_id']]);
					$eventRow['Event_location_country'] = ze\row::get(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries', 'english_name', ['id' => $countryId]);
				}
				
				$datetime = [];
				$datetime['start_date'] = '';
				$datetime['end_date'] = '';
				$datetime['start_time'] = '';
				$datetime['end_time'] = '';
				
				$datetime['separator'] = ' ';

				switch ($this->setting('date_display')){
					case 'dont_show':
						$datetime['start_date'] = '';
						$datetime['end_date'] = '';
						break;
					case 'show_start_time_only':
						$datetime['start_date'] = ze\date::format($row['start_date'],$this->setting('date_format'));
						$datetime['end_date'] = '';
						break;
					case 'show_start_and_end_date':
						$datetime['start_date'] = ze\date::format($row['start_date'],$this->setting('date_format'));
						if ($row['start_date']!=$row['end_date']) {
							$datetime['end_date'] = ze\date::format($row['end_date'],$this->setting('date_format'));
						} 
						break;
				}

				switch ($this->setting('time_display')){
					case 'dont_show':
						$datetime['start_time'] = '';
						$datetime['end_time'] = '';
						break;
					case 'show_start_time_only':
						if ($row['specify_time'] ?? false){
							$datetime['start_time'] = ze\date::formatTime($row['start_time'], ze::setting('vis_time_format'));
						}
						$datetime['end_time'] = '';
						break;
					case 'show_start_and_end_time':
						if ($row['specify_time'] ?? false){
							$datetime['start_time'] = ze\date::formatTime($row['start_time'], ze::setting('vis_time_format'));
							if ($row['start_time']!=$row['end_time']) {
								$datetime['end_time'] = ze\date::formatTime($row['end_time'], ze::setting('vis_time_format'));
							} 
						}
						break;
				}
				if ($datetime['end_date'] || $datetime['end_time']) {
					$datetime['separator'] = '-';
				}
				

				
				$eventRow['Event_Dates'] = ($datetime['start_date'] ?? false) . ' ' 
											. ($datetime['start_time'] ?? false) . ' ' 
												.  ($datetime['separator'] ?? false) . ' ' 
													. ($datetime['end_date'] ?? false) . ' ' 	
													 	. ($datetime['end_time'] ?? false);



				$eventRow['cID'] = $row['id'];
				$eventRow['equiv_id'] = $row['equiv_id'];
				$eventRow['language_id'] = $row['language_id'];
				$eventRow['type'] = $row['type'];
				
				if ($showCategory) {
					if (ze\module::inc('zenario_content_list')) {
						$categoryId = zenario_content_list::getContentItemLowestPublicCategory($eventRow['equiv_id'], $eventRow['type'], $categories);
						if ($categoryId) {
							$eventRow['Category'] = ze\lang::phrase('_CATEGORY_' . $categoryId);
							$eventRow['Category_Id'] = $categoryId;
							$category = ze\row::get('categories', ['landing_page_equiv_id', 'landing_page_content_type', 'code_name'], $categoryId);
							$eventRow['Category_code_name'] = $category['code_name'];
							if ($category['landing_page_equiv_id'] && $category['landing_page_content_type']) {
								$eventRow['Category_Landing_Page_Link'] = ze\link::toItem($category['landing_page_equiv_id'], $category['landing_page_content_type']);
							}
						}
					}
				}

				if ($groupEventsByYearAndMonth = (bool)$this->setting('group_events_by_year_and_month')) {
					
					$startDateTimestamp = strtotime($row['start_date']);
					
					$startMonth = date('n', $startDateTimestamp);
					//Make sure the month is always a double-digit number.
					$startMonth = str_pad($startMonth, 2, '0', STR_PAD_LEFT);
					
					$startYear = date('Y', $startDateTimestamp);
					
					$eventRow['start_year_label'] = $startYear;
					$eventRow['start_month_label'] = ze\lang::phrase('_MONTH_LONG_' . $startMonth, []);
				}
				
				if ( ($this->cType != 'event' || $eventRow['equiv_id'] != ze\content::equivId($this->cID, $this->cType))  && (!isset($eventRows[$eventRow['equiv_id']]) || ($eventRows[$eventRow['equiv_id']]['language_id'] != ze::$langId)) ){
					$eventRows[$eventRow['equiv_id']] = $eventRow;
				}
			}
		}

		if ($eventRows) {
			switch ($this->setting('heading')) {
				case 'show_heading':
					$heading_text = $this->setting('heading_text');
					if ($this->setting('use_phrases')) {
						$this->replacePhraseCodesInString($heading_text);
					}
					$this->data['Title'] = $heading_text;
					
					break;
				case 'show_period_name':
					$this->data['Title'] = $this->getPeriodName($periodName, $periodShift);
					break;
				case 'dont_show':
				default:
					$this->data['Title'] = false;
					break;
			}

			if ($this->setting('show_pagination') && count($pages) > 1) {
				$this->pagination('pagination_style', $this->page, $pages, $this->data['Pagination']);
			}
			
			if ($this->setting('make_event_elements_equal_height')) {
				$this->data['Event_elements_equal_height'] = true;
			}
			
			$this->data['Events_List'] = true;
			$this->data['Event_Row_On_List'] = $eventRows;
			$this->data['Show_Category'] = (bool)$this->setting('show_content_items_category') && (bool)ze::setting('enable_display_categories_on_content_lists');
		} else {
		    $this->data['No_Events'] = true;
		}
		return true;
	}

	public function showSlot(){
		$this->twigFramework($this->data);
	}

	protected function displayForAsSQLDateString(){
		return "NOW()";
		//return "'2010-01-01'";
	}

	protected function isLastDayOfWeek(){
		$sql = "SELECT DAYOFWEEK( " .   $this->displayForAsSQLDateString() . " ) as dow";
		$result = ze\sql::select($sql);
		if ($row=ze\sql::fetchAssoc($result)){
			return $row['dow']==1;
		} else {
			return false;
		}
	}
	
	protected function isLastDayOfMonth(){
		$sql = "SELECT DAYOFMONTH(DATE_ADD( " .   $this->displayForAsSQLDateString() . " ,INTERVAL 1 DAY)) as dom";
		$result = ze\sql::select($sql);
		if ($row=ze\sql::fetchAssoc($result)){
			return $row['dom']==1;
		}else {
			return false;
		}
	}
	
	protected function isLastDayOfYear(){
		$sql = "SELECT DAYOFYEAR(DATE_ADD( " .   $this->displayForAsSQLDateString() . " ,INTERVAL 1 DAY)) as doy";
		$result = ze\sql::select($sql);
		if ($row=ze\sql::fetchAssoc($result)){
			return $row['doy']==1;
		}else {
			return false;
		}
	}

	protected function getDayNumber($date){
		$sql = "SELECT TO_DAYS('" . ze\escape::sql($date) . "') AS day_no";
		$result = ze\sql::select($sql);
		if ($row=ze\sql::fetchAssoc($result)){
			return $row['day_no'];
		} else {
			return 0;
		}
	}

	protected function expandPeriodAsSQLSafeArray($periodBegin,$periodEnd,$periodName){
		
		$rv=[];
		if (($startDay= $this->getDayNumber($periodBegin)) && ($endDay= $this->getDayNumber($periodEnd)) && ($startDay<=$endDay) ) {
			if (($periodName=='week') || ($periodName=='today')) {
				$sql = " ";
				for ($i=$startDay;$i<=$endDay;$i++){
					$sql .= 'SELECT ' . $i . ' as day_no,FROM_DAYS(' . $i . ') as date_str FROM ' . DB_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . 'content_event WHERE TRUE UNION ';
				}
				$sql .= "SELECT 0 as day_no,1 as date_str FROM " . DB_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event WHERE FALSE";
				$result = ze\sql::select($sql);
				
				while($row = ze\sql::fetchAssoc($result)){
					$rv[$row['day_no']] = $row['date_str'];
				}
				return $rv;
			} else {
				return true;
			}
		} else {
			return $rv;
		}
	}

	protected function splitPeriod($periodDaysSortedArray,$cutDate){
		
		$rv = ['before'=>[],'now_and_after'=>[]];
		if ($cutDay = $this->getDayNumber($cutDate)){
			foreach ($periodDaysSortedArray as $K=>$V){
				if ($K<$cutDay){
					$rv['before'][$K] = $V;
				} else {
					$rv['now_and_after'][$K] = $V;
				}
			}
		}
		return $rv;		
	}


	protected function getPeriodStartAsSQLDateString($periodName, $periodShift=0){
		
		$rv='';
		$sql='';
		
		switch ($periodName){
			case 'today':
				$sql .= 'SELECT DATE(' . $this->displayForAsSQLDateString() . ') as period_start';
				break;	
			case 'week':
				if ((int)$periodShift) {
					$sql = 'SELECT DATE_SUB(DATE_ADD(DATE(' . $this->displayForAsSQLDateString() . '),INTERVAL ' . (int) $periodShift . ' WEEK),INTERVAL ((DAYOFWEEK(DATE(' . $this->displayForAsSQLDateString() . ')) +5 ) % 7) DAY ) as period_start';
				} else {
					$sql = 'SELECT DATE_SUB(DATE(' . $this->displayForAsSQLDateString() . '),INTERVAL ((DAYOFWEEK(DATE(' . $this->displayForAsSQLDateString() . ')) +5 ) % 7) DAY ) as period_start';
				}
				break;
			case 'month_eq':
			case 'month_ge':
				if ((int)$periodShift) {
					$sql = 'SELECT DATE_SUB(DATE_ADD(DATE(' . $this->displayForAsSQLDateString() . '),INTERVAL ' . (int) $periodShift . ' MONTH),INTERVAL (DAYOFMONTH(DATE_ADD(DATE(' . $this->displayForAsSQLDateString() . '),INTERVAL ' . (int) $periodShift . ' MONTH))-1)  DAY ) as period_start';
				} else {
					$sql = 'SELECT DATE_SUB(DATE(' . $this->displayForAsSQLDateString() . '),INTERVAL (DAYOFMONTH(DATE(' . $this->displayForAsSQLDateString() . '))-1)  DAY ) as period_start';
				}
				break;
			case 'year':
				if ((int)$periodShift) {
					$sql = 'SELECT DATE_SUB(DATE_ADD(DATE(' . $this->displayForAsSQLDateString() . '),INTERVAL ' . (int) $periodShift . ' YEAR),INTERVAL (DAYOFYEAR(DATE_ADD(DATE(' . $this->displayForAsSQLDateString() . '),INTERVAL ' . (int) $periodShift . ' YEAR))-1)  DAY ) as period_start';
				} else {
					$sql = 'SELECT DATE_SUB(DATE(' . $this->displayForAsSQLDateString() . '),INTERVAL (DAYOFYEAR(DATE(' . $this->displayForAsSQLDateString() . '))-1)  DAY ) as period_start';
				}
				break;
			case 'all_time':
				$sql .= "SELECT '1000-01-01' as period_start";
				break;
			case 'date_range':
				$sql .= "SELECT '" . ze\escape::sql($this->setting('period_start_date')) . "' as period_start";
				break;
		}
		$result = ze\sql::select($sql);
		if ($row = ze\sql::fetchAssoc($result)){
			$rv=$row['period_start'];
		}
		return $rv;
	}
	
	protected function getPeriodEndAsSQLDateString($periodName, $periodShift=0){

		$rv='';
		$sql='';
		
		switch($periodName){
			case 'today':
				$sql = 'SELECT DATE(' . $this->displayForAsSQLDateString() . ') as period_end';
				break;
			case 'week':
				if ((int)$periodShift) {
					$sql = 'SELECT DATE_ADD(DATE_SUB(DATE_ADD(DATE(' . $this->displayForAsSQLDateString() . '),INTERVAL ' . (int) $periodShift . ' WEEK),INTERVAL ((DAYOFWEEK(DATE(' . $this->displayForAsSQLDateString() . ')) +5 ) % 7) DAY ), INTERVAL 6 DAY) as period_end';
				} else {
					$sql = 'SELECT DATE_ADD(DATE_SUB(DATE(' . $this->displayForAsSQLDateString() . '),INTERVAL ((DAYOFWEEK(DATE(' . $this->displayForAsSQLDateString() . ')) +5 ) % 7) DAY ), INTERVAL 6 DAY) as period_end';
				}
				break;
			case 'month_eq':
				if ((int)$periodShift) {
					$sql = 'SELECT LAST_DAY(DATE_ADD(DATE(' . $this->displayForAsSQLDateString() . '), INTERVAL ' . (int) $periodShift . ' MONTH)) as period_end';
				} else {
					$sql = 'SELECT LAST_DAY(DATE(' . $this->displayForAsSQLDateString() . ')) as period_end';
				}
				break;
			case 'month_ge':
				$sql = "SELECT '9999-12-31' as period_end";
				break;
			case 'year':
				if ((int)$periodShift) {
					$sql = "SELECT CONCAT(YEAR(DATE_ADD(" . $this->displayForAsSQLDateString() . ",INTERVAL " . (int) $periodShift . " YEAR)),'-12-31') as period_end";
				} else {
					$sql = "SELECT CONCAT(YEAR(" . $this->displayForAsSQLDateString() . "),'-12-31') period_end";
				}
				break;				
			case 'all_time':
				$sql ="SELECT '9999-12-31' as period_end";
				break;
			case 'date_range':
				$sql .= "SELECT '" . ze\escape::sql($this->setting('period_end_date')) . "' as period_end";
				break;
		}
		$result = ze\sql::select($sql);
		if ($row = ze\sql::fetchAssoc($result)){
			$rv=$row['period_end'];
		}
		return $rv;
	}
	
	protected function getPeriodName($periodName, $periodShift) {
		$rv = '';
		switch($periodName){
			case 'today':
				$rv = $this->phrase("Today");
				break;
			case 'week':
				if ((int)$periodShift) {
					$rv = $this->phrase("Next week");
				} else {
					$rv = $this->phrase("This week");
				}
				break;
			case 'month_eq':
				$periodStart = $this->getPeriodStartAsSQLDateString($periodName, $periodShift);
				$sql = "SELECT IF(YEAR(" . $this->displayForAsSQLDateString() . ")=YEAR('" . $periodStart ."'),'[[_MONTH_LONG_%m]]','[[_MONTH_LONG_%m]] %Y') AS format";
				$result = ze\sql::select($sql);
				if ($row = ze\sql::fetchAssoc($result)){
					$rv = ze\date::format($periodStart,$row['format']);
				}
				break;
			case 'month_ge':
				$periodStart = $this->getPeriodStartAsSQLDateString($periodName, $periodShift);
				$sql = "SELECT IF(YEAR(" . $this->displayForAsSQLDateString() . ")=YEAR('" . $periodStart ."'),'[[_MONTH_LONG_%m]]','[[_MONTH_LONG_%m]] %Y') AS format";
				$result = ze\sql::select($sql);
				if ($row = ze\sql::fetchAssoc($result)){
					$rv = $this->phrase("[[month_name]] onwards", ['month_name' => ze\date::format($periodStart,$row['format'])] );
				}
				break;
			case 'year':
				if ((int)$periodShift) {
					$rv = $this->phrase("Next year");
				} else {
					$rv = $this->phrase("This year");
				}
				break;
			case 'all_time':
				$rv = $this->phrase("This year");
				break;
		}
		return $rv;
	}
	
	protected function buildQuery($periodName, $periodShift=0){
		
		$sql= '';
		$categoryJoin = '';

		$periodStart = $this->getPeriodStartAsSQLDateString($periodName, $periodShift);
		$periodEnd = $this->getPeriodEndAsSQLDateString($periodName, $periodShift);

		if (($periodStart) && ($periodEnd)){
				
				$period = $this->expandPeriodAsSQLSafeArray($periodStart, $periodEnd, $periodName);
				if ($period){
					if ($this->setting('category_list')){
						foreach (explode(',', $this->setting('category_list')) as $catId) {
							if ($catId && ze\row::exists('categories', ['id' => (int) $catId])) {
								$categoryJoin .=" INNER JOIN ". DB_PREFIX. "category_item_link AS cil_". (int) $catId. "
												   ON cil_". (int) $catId. ".equiv_id = c.equiv_id
												  AND cil_". (int) $catId. ".content_type = c.type
												  AND cil_". (int) $catId. ".category_id = ". (int) $catId;
							}
						}
					}
				
					$sql = " SELECT 
								v.id,
								v.version,
								v.title,
								v.content_summary,
								v.feature_image_id,
								c.equiv_id,
								c.type,
								c.language_id,
								ce.location_id,
								ce.location,
								ce.specify_time,
								ce.start_date,
								ce.end_date,
								ce.start_time,
								ce.end_time,
								( ce.end_date<'" . date('Y-m-d') . "' ) as sort_past,
								( ce.start_date<='" . date('Y-m-d') . "' AND ce.end_date>='" . date('Y-m-d') . "'  ) as sort_ongoing,
								( ce.start_date>'" . date('Y-m-d') . "' ) as sort_future " 
							. ze\content::sqlToSearchContentTable(true,false, "
																	INNER JOIN " 
																		. DB_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event ce
																	ON
																			v.id = ce.id
																		AND v.version = ce.version "
																	. $categoryJoin
																	) . "
								AND (ce.start_date <= '" . $periodEnd . "' 
								AND ce.end_date >= '" . $periodStart . "') 
								AND  c.type='event'";
					if ($this->setting('language_selection') == 'visitor') {
						//Only return content in the current language
						$sql .= "
						  AND c.language_id = '". ze\escape::asciiInSQL(ze::$langId). "'";
					} elseif ($this->setting('language_selection') == 'specific_languages') { 
						//Return content in languages selected by admin
						$arr = [''];
						foreach(explode(",", $this->setting('specific_languages')) as $langCode)  {
							$arr[] = ze\escape::sql($langCode);
						}
						$sql .="
							AND c.language_id IN ('". implode("','", $arr) . "')";
					}
					if ($this->setting('location')=='location_associated_with_content_item'){
						if (($locId = zenario_location_manager::getLocationIdFromContentItem($this->cID,$this->cType))){
							$sql .=" 
									AND location_id = " . (int) $locId;
						}
					}
					switch($periodName){
						case 'week':
							if (ze\module::inc('zenario_event_days_and_dates')) {
								$sql .= ' AND ( FALSE ';
								foreach ($period as $K=>$V){		
									$sql .= " OR IFNULL(ce.stop_dates,'')  not like '%" . $V . "%' " ;
								}								
								$sql .= ' ) ';
							}
							break;
						case 'today':
							if (ze\module::inc('zenario_event_days_and_dates')) {
								foreach ($period as $K=>$V){		
									switch (($K-1) % 7){
										case 0:
											$sql .= " AND IFNULL(ce.stop_dates,'') not like '%" . date('Y-m-d') . "%' AND day_sun_on ";
											break;
										case 1:
											$sql .= " AND IFNULL(ce.stop_dates,'') not like '%" . date('Y-m-d') . "%' AND day_mon_on ";
											break;
										case 2:
											$sql .= " AND IFNULL(ce.stop_dates,'') not like '%" . date('Y-m-d') . "%' AND day_tue_on ";
											break;
										case 3:
											$sql .= " AND IFNULL(ce.stop_dates,'') not like '%" . date('Y-m-d') . "%' AND day_wed_on ";
											break;
										case 4:
											$sql .= " AND IFNULL(ce.stop_dates,'') not like '%" . date('Y-m-d') . "%' AND day_thu_on ";
											break;
										case 5:
											$sql .= " AND IFNULL(ce.stop_dates,'') not like '%" . date('Y-m-d') . "%' AND day_fri_on ";
											break;
										case 6:
											$sql .= " AND IFNULL(ce.stop_dates,'') not like '%" . date('Y-m-d') . "%' AND day_sat_on ";
											break;
									}
								}
							}
							break;
					}
					
					if (!in_array($periodName, ['date_range', 'today'])) {
						$sql .=" AND (false ";
						if ($this->setting('past')){
							$sql .=" OR ( ce.end_date<'" . date('Y-m-d') . "' )";
						}
						if ($this->setting('ongoing')){
							$sql .=" OR ( ce.start_date<='" . date('Y-m-d') . "' AND ce.end_date>='" . date('Y-m-d') . "'  )";
						}
						if ($this->setting('future')){
							$sql .=" OR ( ce.start_date>'" . date('Y-m-d') . "' )";
						}
						$sql .= ")"; 
					}
					
					switch ($this->setting('sort_field')) {
						case 'start_date':
							switch ($this->setting('sort_order')){
								case 'most_recent_first':
									$sql .= " ORDER BY start_date DESC";
									break;
								case 'older_first':
									$sql .= " ORDER BY start_date ASC";
									break;
							}
							break;
						case 'end_date':
							switch ($this->setting('sort_order')){
								case 'most_recent_first':
									$sql .= " ORDER BY end_date DESC";
									break;
								case 'older_first':
									$sql .= " ORDER BY end_date ASC";
									break;
							}
							break;
					}
				}
		} 
		return $sql;
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				
				// Show an initial date range
				if ($values['first_tab/period_mode'] != 'date_range') {
					$values['first_tab/period_start_date'] = gmdate('Y-01-01');
					$values['first_tab/period_end_date'] = gmdate('Y-m-d', strtotime($values['first_tab/period_start_date'] . ' + 2 years UTC'));
				}
				
				$fields['pagination/pagination_style']['values'] = ze\pluginAdm::paginationOptions();
				break;
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch($path){
			case 'plugin_settings':
				ze\categoryAdm::setupFABCheckboxes($fields['first_tab/category_list'], true);
				
				$fields['first_tab/period_start_date']['hidden'] = 
				$fields['first_tab/period_end_date']['hidden'] = 
					$values['first_tab/period_mode'] != 'date_range';
				
				$fields['first_tab/month_period_operator']['hidden'] =
				$fields['first_tab/month_period_value']['hidden'] =
					$values['first_tab/period_mode'] != 'month_period';
				
				$fields['first_tab/specific_languages']['hidden'] = 
					$values['first_tab/language_selection'] != 'specific_languages';
				
				$fields['first_tab/ongoing']['hidden'] = 
				$fields['first_tab/future']['hidden'] = 
				$fields['first_tab/past']['hidden'] = 
					in_array($values['first_tab/period_mode'], ['today_only', 'date_range']);
				
				$fields['pagination/page_limit']['hidden'] = 
				$fields['pagination/pagination_style']['hidden'] = 
					!$values['pagination/show_pagination'];

				$fields['overall_list/heading_text']['hidden'] =
					$values['overall_list/heading'] != 'show_heading';
				
				
				$fields['each_item/retina']['hidden'] = 
                $fields['each_item/fall_back_to_default_image']['hidden'] = 
                    !$values['each_item/show_sticky_images'];
        
                $fields['each_item/default_image_id']['hidden'] = 
                    !($values['each_item/show_sticky_images'] && $values['each_item/fall_back_to_default_image']);
                   
                $fields['each_item/show_location_name']['hidden'] = 
                $fields['each_item/show_location_city']['hidden'] = 
                $fields['each_item/show_location_country']['hidden'] = 
                	!($values['each_item/show_location']);
                	
                $hidden = !$values['each_item/show_sticky_images'];
                $this->showHideImageOptions($fields, $values, 'each_item', $hidden);
                
                $categoriesEnabled = ze::setting('enable_display_categories_on_content_lists');
				if (!$categoriesEnabled) {
					$fields['each_item/show_content_items_category']['disabled'] = true;
					$fields['each_item/show_content_items_category']['side_note'] = ze\admin::phrase('You must enable this option in your site settings under "Categories".');
					$values['each_item/show_content_items_category'] = false;
				}
				
				if (!empty($fields['overall_list/group_events_by_year_and_month']['current_value'])) {
					$fields['overall_list/sort_field']['values']['end_date']['disabled'] = true;
					$fields['overall_list/sort_field']['side_note'] = ze\lang::phrase('Cannot sort by end date if grouping events by year and month is enabled.');
				} else {
					$fields['overall_list/sort_field']['values']['end_date']['disabled'] = false;
					unset($fields['overall_list/sort_field']['side_note']);
				}
				
				if (!empty($fields['overall_list/sort_field']['current_value']) && $fields['overall_list/sort_field']['current_value'] == 'end_date') {
					$fields['overall_list/group_events_by_year_and_month']['disabled'] = true;
					$fields['overall_list/group_events_by_year_and_month']['side_note'] = ze\lang::phrase('Cannot group events by year and month if sorting by end date is enabled.');
				} else {
					$fields['overall_list/group_events_by_year_and_month']['disabled'] = false;
					unset($fields['overall_list/group_events_by_year_and_month']['side_note']);
				}
                
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
    
    	switch ($path) {
			case 'plugin_settings':
				if ($values['each_item/show_location'] == true
					&& $values['each_item/show_location_name'] == false
					&& $values['each_item/show_location_city'] == false
					&& $values['each_item/show_location_country'] == false) {
						$fields['each_item/show_location']['error'] = 'Please choose at least one of the filters below';
				}
				break;
		}
	}
	
}
?>