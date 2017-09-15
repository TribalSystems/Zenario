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

class zenario_event_listing extends module_base_class {

    protected $data = array();
    
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

		$eventRows = array();
		if ($sql = $this->buildQuery($periodName, $periodShift)){
			
			//Get a count of how many items we have to display
			$result = sqlSelect('SELECT COUNT(*) FROM ('. $sql . ') A');
			list($rows) = sqlFetchRow($result);
			
			$totalPages = (int) ceil($rows / $this->setting('page_size'));
			
			//Loop through each page to display, and add its details to an array of merge fields
			$pages = array();
			for ($i = 1; $i <= $this->setting('page_limit') && $i <= $totalPages; ++$i) {
				$pages[$i] = '&page='. $i;
			}
			
			$defaultImageURL = false;
			if ($this->setting('show_sticky_images') && $this->setting('fall_back_to_default_image')) {
                $width = 0;
                $height = 0;
			    Ze\File::imageLink($width, $height, $defaultImageURL, $this->setting('default_image_id'), $this->setting("width"), $this->setting("height"), $this->setting('canvas'), 0, $this->setting('retina'));
			}
				
			$result = sqlSelect($sql . paginationLimit($this->page, $this->setting('page_size'), $this->setting('offset')));
			while($row = sqlFetchAssoc($result)){
			    $eventRow = array();
				$eventRow['Link_To_Event'] = $this->linkToItem($row['id'], 'event');
				
				if ($this->setting('show_sticky_images')) {
				    $stickyImageURL = $defaultImageURL;
				    
                    $url = '';
                    $width = 0;
                    $height = 0;
                    Ze\File::imageLink($width, $height, $url, $row['feature_image_id'], $this->setting("width"), $this->setting("height"), $this->setting('canvas'), 0, $this->setting('retina'));
                    
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
					displayHTMLAsPlainText($eventRow['Event_Description'],$this->setting('excerpt_length'));
					$eventRow['Event_Description'] = nl2br($eventRow['Event_Description']);
				}

				if ($this->setting('show_location')
					&& ($locationId = $row['location_id'] ?? false)
					&& inc('zenario_location_manager') 
					&& ($location = zenario_location_manager::getLocationDetails($locationId))
				) {
				    $eventRow['Event_location'] = $location['description'];
				}
				
				
				$datetime = array();
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
						$datetime['start_date'] = formatDateNicely($row['start_date'],$this->setting('date_format'));
						$datetime['end_date'] = '';
						break;
					case 'show_start_and_end_date':
						$datetime['start_date'] = formatDateNicely($row['start_date'],$this->setting('date_format'));
						if ($row['start_date']!=$row['end_date']) {
							$datetime['end_date'] = formatDateNicely($row['end_date'],$this->setting('date_format'));
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
							$datetime['start_time'] = formatTimeNicely($row['start_time'], setting('vis_time_format'));
						}
						$datetime['end_time'] = '';
						break;
					case 'show_start_and_end_time':
						if ($row['specify_time'] ?? false){
							$datetime['start_time'] = formatTimeNicely($row['start_time'], setting('vis_time_format'));
							if ($row['start_time']!=$row['end_time']) {
								$datetime['end_time'] = formatTimeNicely($row['end_time'], setting('vis_time_format'));
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

				if ( ($this->cType != 'event' || $eventRow['equiv_id'] != equivId($this->cID, $this->cType))  && (!isset($eventRows[$eventRow['equiv_id']]) || ($eventRows[$eventRow['equiv_id']]['language_id'] != cms_core::$langId)) ){
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
			
			$this->data['Events_List'] = true;
			$this->data['Event_Row_On_List'] = $eventRows;
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
		$result = sqlQuery($sql);
		if ($row=sqlFetchAssoc($result)){
			return $row['dow']==1;
		} else {
			return false;
		}
	}
	
	protected function isLastDayOfMonth(){
		$sql = "SELECT DAYOFMONTH(DATE_ADD( " .   $this->displayForAsSQLDateString() . " ,INTERVAL 1 DAY)) as dom";
		$result = sqlQuery($sql);
		if ($row=sqlFetchAssoc($result)){
			return $row['dom']==1;
		}else {
			return false;
		}
	}
	
	protected function isLastDayOfYear(){
		$sql = "SELECT DAYOFYEAR(DATE_ADD( " .   $this->displayForAsSQLDateString() . " ,INTERVAL 1 DAY)) as doy";
		$result = sqlQuery($sql);
		if ($row=sqlFetchAssoc($result)){
			return $row['doy']==1;
		}else {
			return false;
		}
	}

	protected function getDayNumber($date){
		$sql = "SELECT TO_DAYS('" . sqlEscape($date) . "') AS day_no";
		$result = sqlQuery($sql);
		if ($row=sqlFetchAssoc($result)){
			return $row['day_no'];
		} else {
			return 0;
		}
	}

	protected function expandPeriodAsSQLSafeArray($periodBegin,$periodEnd,$periodName){
		
		$rv=array();
		if (($startDay= $this->getDayNumber($periodBegin)) && ($endDay= $this->getDayNumber($periodEnd)) && ($startDay<=$endDay) ) {
			if (($periodName=='week') || ($periodName=='today')) {
				$sql = " ";
				for ($i=$startDay;$i<=$endDay;$i++){
					$sql .= 'SELECT ' . $i . ' as day_no,FROM_DAYS(' . $i . ') as date_str FROM ' . DB_NAME_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . 'content_event WHERE TRUE UNION ';
				}
				$sql .= "SELECT 0 as day_no,1 as date_str FROM " . DB_NAME_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event WHERE FALSE";
				$result = sqlQuery($sql);
				
				while($row = sqlFetchAssoc($result)){
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
		
		$rv = array('before'=>array(),'now_and_after'=>array());
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
				$sql .= "SELECT '" . sqlEscape($this->setting('period_start_date')) . "' as period_start";
				break;
		}
		$result = sqlQuery($sql);
		if ($row = sqlFetchAssoc($result)){
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
				$sql .= "SELECT '" . sqlEscape($this->setting('period_end_date')) . "' as period_end";
				break;
		}
		$result = sqlQuery($sql);
		if ($row = sqlFetchAssoc($result)){
			$rv=$row['period_end'];
		}
		return $rv;
	}
	
	protected function getPeriodName($periodName, $periodShift) {
		$rv = '';
		switch($periodName){
			case 'today':
				$rv = $this->phrase("_TODAY");
				break;
			case 'week':
				if ((int)$periodShift) {
					$rv = $this->phrase("_NEXT_WEEK");
				} else {
					$rv = $this->phrase("_THIS_WEEK");
				}
				break;
			case 'month_eq':
				$periodStart = $this->getPeriodStartAsSQLDateString($periodName, $periodShift);
				$sql = "SELECT IF(YEAR(" . $this->displayForAsSQLDateString() . ")=YEAR('" . $periodStart ."'),'[[_MONTH_LONG_%m]]','[[_MONTH_LONG_%m]] %Y') AS format";
				$result = sqlQuery($sql);
				if ($row = sqlFetchAssoc($result)){
					$rv = formatDateNicely($periodStart,$row['format']);
				}
				break;
			case 'month_ge':
				$periodStart = $this->getPeriodStartAsSQLDateString($periodName, $periodShift);
				$sql = "SELECT IF(YEAR(" . $this->displayForAsSQLDateString() . ")=YEAR('" . $periodStart ."'),'[[_MONTH_LONG_%m]]','[[_MONTH_LONG_%m]] %Y') AS format";
				$result = sqlQuery($sql);
				if ($row = sqlFetchAssoc($result)){
					$rv = $this->phrase("_MONTH_ONWARDS", array('month_name' => formatDateNicely($periodStart,$row['format'])) );
				}
				break;
			case 'year':
				if ((int)$periodShift) {
					$rv = $this->phrase("_NEXT_YEAR");
				} else {
					$rv = $this->phrase("_THIS_YEAR");
				}
				break;
			case 'all_time':
				$rv = $this->phrase("_THIS_YEAR");
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
							if ($catId && checkRowExists('categories', array('id' => (int) $catId))) {
								$categoryJoin .=" INNER JOIN ". DB_NAME_PREFIX. "category_item_link AS cil_". (int) $catId. "
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
								c.language_id,
								ce.location_id,
								ce.specify_time,
								ce.start_date,
								ce.end_date,
								ce.start_time,
								ce.end_time,
								( ce.end_date<'" . date('Y-m-d') . "' ) as sort_past,
								( ce.start_date<='" . date('Y-m-d') . "' AND ce.end_date>='" . date('Y-m-d') . "'  ) as sort_ongoing,
								( ce.start_date>'" . date('Y-m-d') . "' ) as sort_future " 
							. sqlToSearchContentTable(true,false, "
																	INNER JOIN " 
																		. DB_NAME_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event ce
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
					if ($this->setting('location')=='location_associated_with_content_item'){
						if (($locId = zenario_location_manager::getLocationIdFromContentItem($this->cID,$this->cType))){
							$sql .=" 
									AND location_id = " . (int) $locId;
						}
					}
					switch($periodName){
						case 'week':
							if (inc('zenario_event_days_and_dates')) {
								$sql .= ' AND ( FALSE ';
								foreach ($period as $K=>$V){		
									$sql .= " OR IFNULL(ce.stop_dates,'')  not like '%" . $V . "%' " ;
								}								
								$sql .= ' ) ';
							}
							break;
						case 'today':
							if (inc('zenario_event_days_and_dates')) {
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
					
					if (!in_array($periodName, array('date_range', 'today'))) {
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
				
				$fields['pagination/pagination_style']['values'] = paginationOptions();
				break;
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch($path){
			case 'plugin_settings':
				setupCategoryCheckboxes($fields['first_tab/category_list'], true);
				
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
					in_array($values['first_tab/period_mode'], array('today_only', 'date_range'));
				
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
        
                $hidden = !$values['each_item/show_sticky_images'];
                $this->showHideImageOptions($fields, $values, 'each_item', $hidden);
				break;
		}
	}
	
}
?>