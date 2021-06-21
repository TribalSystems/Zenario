<?php
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed'); 
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


class zenario_event_calendar extends ze\moduleBaseClass {
	
	public $data = [];
	public $content;
	public $displaySections;
	public $mergeFields;
	public $missingFieldsErrors;
	public $incorrectEmailFormatErrors;
	public $errors;
	
	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = true);
		
		$this->registerGetRequest('day');
		$this->registerGetRequest('month');
		$this->registerGetRequest('year');
		return true;
	}

	function showSlot() {
		if ($this->setting('view_mode')=='year_view'){
			$this->showYearView();
		} else {
			$this->showMonthView();
		}

		$this->data['Enable_popup'] = $this->setting('enable_popup');
		$this->data['Show_event_count'] = $this->setting('event_count');
		$this->twigFramework($this->data);
	}

	function showMonthView() {
		if ($_GET['month'] ?? false){
			$month=(int)($_GET['month'] ?? false);
		} else {
			$month=date('n',time());
		}
		if (($_GET['year'] ?? false) && (($_GET['year'] ?? false)>1969) && (($_GET['year'] ?? false)<2038)){
			$year=(int)($_GET['year'] ?? false);
		} else {
			$year=date('Y',time());
		}
		
		if ($month==1){
			$previousMonth = 12;
			$previousYear=$year-1;
		} else {
			$previousMonth=$month-1;
			$previousYear=$year;	//previous means year on previous calendar page (year in previous month!!!!)
		}
		if ($month==12){
			$nextMonth = 1;
			$nextYear=$year+1;
		} else {
			$nextMonth=$month+1;
			$nextYear=$year;
		}

		//calendarStartDayOfWeek - Monday - 1,..., Sunday -7     <---determines first column of calendar 
		switch ($this->setting('first_day_of_week')) {
			case 'Monday':
				$calendarStartDayOfWeek=1;
				break;
			case 'Tuesday':
				$calendarStartDayOfWeek=2;
				break;
			case 'Wednesday':
				$calendarStartDayOfWeek=3;
				break;
			case 'Thursday':
				$calendarStartDayOfWeek=4;
				break;
			case 'Friday':
				$calendarStartDayOfWeek=5;
				break;
			case 'Saturday':
				$calendarStartDayOfWeek=6;
				break;
			default:
			case 'Sunday':
				$calendarStartDayOfWeek=7;
				break;
		}
		
		$currentMonth=mktime(0,0,0,$month,1,$year);
		//currentMonthStartDayOfWeek - Monday - 1,..., Sunday -7
		$currentMonthStartDayOfWeek=date('N',$currentMonth);		
		//in which column will the 1st day of the Month appeaar
		$currentMonthStartPosition = (($currentMonthStartDayOfWeek - $calendarStartDayOfWeek  + 7) % 7)+1; 
		$currentMonthLength = date('t',$currentMonth);
		$langIDs = $this->getAllowedLanguages();
		
		$this->data['Event_calendar_month_view'] = true;
		$this->data['Calendar_month_view_title'] = true;
		$this->data['Calendar_month_view_header'] = true;
		$this->data['Full_date'] = ze\date::format(date("Y-m-d",$currentMonth),"[[_MONTH_LONG_%m]] %Y",false,false);
		$this->data['Year_number'] = (string)(int)$year;
		
		$this->data['Calendar_month_view_content'] = true;
		$this->data['Table_header'] = true;
		$this->data['Cal_day_1'] = mb_substr(ze\lang::phrase('_WEEKDAY_' . (string) ($calendarStartDayOfWeek  ) % 7, []), 0,1,'utf8');
		$this->data['Cal_day_2'] = mb_substr(ze\lang::phrase('_WEEKDAY_' . (string) ($calendarStartDayOfWeek+1) % 7, []), 0,1,'utf8');
		$this->data['Cal_day_3'] = mb_substr(ze\lang::phrase('_WEEKDAY_' . (string) ($calendarStartDayOfWeek+2) % 7, []), 0,1,'utf8');
		$this->data['Cal_day_4'] = mb_substr(ze\lang::phrase('_WEEKDAY_' . (string) ($calendarStartDayOfWeek+3) % 7, []), 0,1,'utf8');
		$this->data['Cal_day_5'] = mb_substr(ze\lang::phrase('_WEEKDAY_' . (string) ($calendarStartDayOfWeek+4) % 7, []), 0,1,'utf8');
		$this->data['Cal_day_6'] = mb_substr(ze\lang::phrase('_WEEKDAY_' . (string) ($calendarStartDayOfWeek+5) % 7, []), 0,1,'utf8');
		$this->data['Cal_day_7'] = mb_substr(ze\lang::phrase('_WEEKDAY_' . (string) ($calendarStartDayOfWeek+6) % 7, []), 0,1,'utf8');
		
		$this->data['Days_row_element'] = [];
		
		for ($i=1;$i<$currentMonthStartPosition;$i++){
			$mergeFields[]=['Day_class_name'=>'empty_day_cell','Day_label'=> ' ','Day_event_span' => ""];
		}
		$j=1;
		for ($i; ($i<=7*6) && ($j<=$currentMonthLength); $i++){
			
			$numberOfEvents = $this->getEventDay($year, $month, $j, $langIDs);
			
			if ($this->isEventDay($year, $month, $j, $langIDs)) {
				$events = $this->getEventsDesc($year, $month, $j, $langIDs);

				if (($j == date('j', time())) && ($month == date('n', time())) && ($year == date('Y', time()))){ 
					$day_class_name_var = 'today';
				} else {
					$day_class_name_var = 'day';
				}
				
				if (ze::in($this->setting('show_event_titles'), "first_event", "all_events") || $this->setting('event_count')) {
					$dayEvents = [];
					foreach ($events as $event) {
						if ($this->setting('show_event_titles') != "nothing") {
							$dayEvents[] = [
								'Title' => $event['title'],
								'SUBDIR' => SUBDIRECTORY . DIRECTORY_INDEX_FILENAME, 
								'Event_id' => (int) $event['id'], 
								'Content_type' => 'event',
							];
						}
						
						if ($this->setting('show_event_titles') == "first_event") {
							break;
						}
					}
					
					$mergeFields[] = [
						'Anchor' => ' rel="colorbox" href="'. htmlspecialchars($this->showFloatingBoxLink("&mode=month_view&day=" . (string)(int)$j . "&month=" . (string)(int)$month . "&year=" . (string)(int)$year,1,true,300,-150,17,false,0)). '"',
						'Day_class_name' => $day_class_name_var,
						'Td_day_class_name' => 'event',
						'Day_label' => (string)(int)($j++),
						'Num_events' => $numberOfEvents,
						'Day_events' => $dayEvents
					];
				} elseif ($this->setting('show_event_titles') == "nothing" || !$this->setting('event_count')) {
					$mergeFields[] = [
						'Anchor' => ' rel="colorbox" href="'. htmlspecialchars($this->showFloatingBoxLink("&mode=month_view&day=" . (string)(int)$j . "&month=" . (string)(int)$month . "&year=" . (string)(int)$year,1,true,300,-150,17,false,0)). '"',
						'Day_class_name' => $day_class_name_var,
						'Td_day_class_name' => 'event',
						'Day_label' => (string)(int)($j++),
						'Day_event_span' => "",
						'Num_events' => $numberOfEvents
					];
				}
			} else {
				if (($j==date('j',time())) && ($month==date('n',time())) && ($year==date('Y',time()))){ 
					$mergeFields[]=['Day_class_name'=>'today','Td_day_class_name'=>'today','Day_label'=> (string)(int)($j++),'Day_event_span' => ""];
				} else {
					$mergeFields[]=['Day_class_name'=>'day','Day_label'=> (string)(int)($j++),'Day_event_span' => "" ];
				}
			}
			if (($i%7)==0) {
				$this->data['Days_row_element'][]['Days_cell_element'] = $mergeFields;
				$mergeFields=[];
			}
		}
		if (count($mergeFields)>0){
			$this->data['Days_row_element'][]['Days_cell_element'] = $mergeFields;
			$mergeFields=[];
		} 
		
		$this->data['Calendar_month_view_footer'] = true;
		$this->data['Previous_month_onclick'] = ($previousYear > 1969 && $previousYear < 2038) ? $this->refreshPluginSlotJS('&month=' . (string) (int)$previousMonth . '&year=' . (string)(int) $previousYear) : "";
		$this->data['Previous_month_name'] = ($previousYear > 1969 && $previousYear < 2038) ? ze\date::format(date("Y-m-d", mktime(0, 0, 0, $previousMonth, 1, $previousYear)), "[[_MONTH_LONG_%m]] ", false, false) : "";
		$this->data['Next_month_onclick'] = ($nextYear > 1969 && $nextYear < 2038) ? $this->refreshPluginSlotJS('&month=' . (string) (int)$nextMonth . '&year=' . (string)(int)$nextYear) : "";
		$this->data['Next_month_name'] = ($nextYear > 1969 && $nextYear < 2038) ? ze\date::format(date("Y-m-d", mktime(0, 0, 0, $nextMonth, 1, $nextYear)), "[[_MONTH_LONG_%m]] ", false, false) : "";
	}

	public function showYearView(){
		$monthFormat = $this->setting('months_format');
	
		$langIDs = [];
		if (($_GET['year'] ?? false) && (($_GET['year'] ?? false)>1969) && (($_GET['year'] ?? false)<2038)){
			$year=(int)($_GET['year'] ?? false);
		} else {
			$year=date('Y',time());
		}
		$currentYear=mktime(0,0,0,0,1,$year);
		$previousYear=$year-1;
		$nextYear=$year+1;
		
		$this->data['Event_calendar_year_view'] = true;
		$this->data['Calendar_year_view_title'] = true;
		$this->data['Calendar_year_view_header'] = true;
		$this->data['Full_date'] = ze\date::format(date("Y-m-d",$currentYear),"[[_MONTH_LONG_%m]] %Y",false,false);
		$this->data['Year_number'] = (string)(int)$year;
		
		$this->data['Calendar_year_view_content'] = true;
		$this->data['Months_row_element'] = [];
		
		for ($i = 0; $i < 3; $i++){
			$mergeFields = [];
			for ($j = 0; $j < 4; $j++){
				$month = $i * 4 + $j + 1;
				$lang = $this->getAllowedLanguages();
				$numberOfEvents = $this->getMonthEvent($year, $month, $lang);
				if ($this->isEventMonth($year, $month, $langIDs)) {
					$events = $this->getEventsDesc($year, $month, false, $langIDs);
					if (($month == date('n', time())) && ($year == date('Y', time()))) { 
						$currentMonthClass = 'current_month';
					} else {
						$currentMonthClass = '';
					}
					
					$monthShort = ze\date::format(date("Y-m-d",mktime(0,0,0,$month,1,$year)),"[[_MONTH_SHORT_%m]] ",false,false);
					$monthLong = ze\date::format(date("Y-m-d",mktime(0,0,0,$month,1,$year)),"[[_MONTH_LONG_%m]] ",false,false);
					
					if ($monthFormat == "months_short_name"){
						$monthLabel = $monthShort;
					} elseif ($monthFormat == "months_long_name") {
						$monthLabel = $monthLong;
					}
					
							
					if (ze::in($this->setting('show_event_titles'), "first_event", "all_events") || $this->setting('event_count')) {
						$monthEvents = [];
						foreach ($events as $event) {
							if ($this->setting('show_event_titles') != "nothing") {
								$monthEvents[] = [
									'Title' => $event['title'],
									'SUBDIR' => SUBDIRECTORY . DIRECTORY_INDEX_FILENAME, 
									'Event_id' => (int) $event['id'], 
									'Content_type' => 'event',
								];
							}

							if ($this->setting('show_event_titles') == "first_event") {
								break;
							}
						}
					
						$mergeFields[] = [	
							'Anchor' =>' rel="colorbox" href="'. htmlspecialchars($this->showFloatingBoxLink("&mode=year_view&month=" . (string)(int)$month . "&year=" . (string)(int)$year,1,true,300,-150,17,false,0)). '"',
							'Current_month'=>$currentMonthClass,
							'Month_with_events'=>'month_with_events',
							'Month_label'=> $monthLabel,
							'Num_events' => $numberOfEvents,
							'Month_events' => $monthEvents
						];		
					} elseif ($this->setting('show_event_titles') == "nothing" || !$this->setting('event_count')) {
						$mergeFields[] = [	
							'Anchor' => ' rel="colorbox" href="'. htmlspecialchars($this->showFloatingBoxLink("&mode=year_view&month=" . (string)(int)$month . "&year=" . (string)(int)$year,1,true,300,-150,17,false,0)). '"',
							'Current_month'=>$currentMonthClass,
							'Month_with_events'=>'month_with_events',
							'Month_label'=> $monthLabel,
							'Num_events' => $numberOfEvents
						];
					}
				} else {
					if (($month==date('n',time())) && ($year==date('Y',time()))) { 
						$monthShort = ze\date::format(date("Y-m-d",mktime(0,0,0,$month,1,$year)),"[[_MONTH_SHORT_%m]] ",false,false);
						$monthLong = ze\date::format(date("Y-m-d",mktime(0,0,0,$month,1,$year)),"[[_MONTH_LONG_%m]] ",false,false);
						
						if ($monthFormat == "months_short_name") {
							$monthLabel = $monthShort;
						}elseif($monthFormat == "months_long_name") {
							$monthLabel = $monthLong;
						}
						
						$mergeFields[]=['Current_month'=>'current_month','Month_label'=> $monthLabel,'Month_event_span' => ""];
					} else {
						$monthShort = ze\date::format(date("Y-m-d",mktime(0,0,0,$month,1,$year)),"[[_MONTH_SHORT_%m]] ",false,false);
						$monthLong =  ze\date::format(date("Y-m-d",mktime(0,0,0,$month,1,$year)),"[[_MONTH_LONG_%m]] ",false,false);
						
						if ($monthFormat == "months_short_name") {
							$monthLabel = $monthShort;
						} elseif ($monthFormat == "months_long_name") {
							$monthLabel = $monthLong;
						}
						//No events
						$mergeFields[] = ['Current_month'=>'', 'Month_label' => $monthLabel, 'Month_event_span' => ""];
					}
				}
			}
			$this->data['Months_row_element'][]['Months_cell_element'] = $mergeFields;
		}
		
		$this->data['Calendar_year_view_footer'] = true;

		if ($this->setting('show_other_periods') == 'current_future_and_previous') {
			$minAndMaxYear = $this->getMinAndMaxYear();

			if ($minAndMaxYear['min_year'] && $minAndMaxYear['max_year']) {
				$years = [];
				for ($i = $minAndMaxYear['max_year']; $i >= $minAndMaxYear['min_year']; $i--) {
					$years[$i] = ['Label' => (int) $i, 'Onclick' => ($i > 1969 && $i < 2038) ? $this->refreshPluginSlotJS('&year=' . (string)(int)$i) : ""];
				}

				$this->data['Min_year'] = $minAndMaxYear['min_year'];
				$this->data['Max_year'] = $minAndMaxYear['max_year'];
				$this->data['Year_range'] = $years;
				$this->data['Show_year_range'] = true;
			}
		} else {
			$this->data['Previous_year_onclick'] = ($previousYear>1969&&$previousYear<2038)?$this->refreshPluginSlotJS('&year=' . (string)(int)$previousYear):"";
			$this->data['Previous_year_name'] = ($previousYear>1969&&$previousYear<2038)?(string) $previousYear:"";
			$this->data['Next_year_onclick'] = ($nextYear>1969&&$nextYear<2038)?$this->refreshPluginSlotJS('&year=' . (string)(int)$nextYear):"";
			$this->data['Next_year_name'] = ($nextYear>1969&&$nextYear<2038)?(string)$nextYear:"";
			$this->data['Show_next_and_previous_only'] = true;
		}
	}
		

	function isEventMonth($year,$month,$langs) {
		$year = (int)$year;
		$month = (int)$month;
		$sql = "
			SELECT DISTINCT c.id";
		$sqlJoin = "
			INNER JOIN " . DB_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event AS ce
				ON v.id = ce.id
				AND v.version = ce.version
				AND v.type = 'event'
			LEFT JOIN " . DB_PREFIX . "category_item_link as cil 
				ON c.equiv_id = cil.equiv_id
				AND c.type = cil.content_type";
				
		$sql .= ze\content::sqlToSearchContentTable($this->setting('hide_private_items'),false,$sqlJoin);

		if ($this->setting('category')){
			$sql .= "
				AND  cil.category_id = " . (int) $this->setting('category') ;
		}
		
		$sql .= '
			AND start_date <= LAST_DAY("'. ze\escape::sql($year . '-' . $month . '-01') . '")
			AND end_date >= "'. ze\escape::sql($year . '-' . $month . '-01') . '"';
	
		if (count($langs) > 0) {
				$sql .="
					AND (FALSE ";
				foreach ($langs as $lang){
					$sql .= " OR c.language_id='" . ze\escape::sql($lang) . "'"; 
				}
				$sql .= ") ";
		 	}
		$sql .= "
			LIMIT 1";
 
 		if (ze\sql::numRows($result=ze\sql::select($sql))>0 ){
			return true;
		} else {
			return false;
		}
	}

	//num of events for the month
	function getMonthEvent($year,$month,$langs) {
		$year = (int)$year;
		$month = (int)$month;
		$sql = "
			SELECT DISTINCT c.id";
		$sqlJoin = "
			INNER JOIN " . DB_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event AS ce
				ON v.id = ce.id
				AND v.version = ce.version
				AND v.type = 'event'
			LEFT JOIN " . DB_PREFIX . "category_item_link as cil 
				ON c.equiv_id = cil.equiv_id
				AND c.type = cil.content_type";
				
		$sql .= ze\content::sqlToSearchContentTable($this->setting('hide_private_items'),false,$sqlJoin);

		if ($this->setting('category')){
			$sql .= "
				AND  cil.category_id = " . (int) $this->setting('category') ;
		}
		
		$sql .= '
			AND start_date <= LAST_DAY("'. ze\escape::sql($year . '-' . $month . '-01') . '")
			AND end_date >= "'. ze\escape::sql($year . '-' . $month . '-01') . '"';
	
		if (count($langs)>0){
			$sql .= "
				AND (FALSE ";
			foreach ($langs as $lang){
				$sql .= " OR c.language_id='" . ze\escape::sql($lang) . "'"; 
			}
			$sql .= ") ";
		}
 
 		$result = ze\sql::select($sql);
		$events = [];
		while ($row = ze\sql::fetchAssoc($result)) {
			$events[] = $row;
		}
		
		if ($events) {
			$numerOfEvents = count($events);
			return $numerOfEvents;
		} else {
			return 0;
		}
	}
	
	//num of events for the month
	function getMonthEventDesc($year,$month,$langs) {
		$year = (int)$year;
		$month = (int)$month;
		$sql = "
			SELECT DISTINCT c.id, v.title";
		$sqlJoin = "
			INNER JOIN " . DB_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event AS ce
				ON v.id = ce.id
				AND v.version = ce.version
				AND v.type = 'event'
			LEFT JOIN " . DB_PREFIX . "category_item_link as cil 
				ON c.equiv_id = cil.equiv_id
				AND c.type = cil.content_type";
				
		$sql .= ze\content::sqlToSearchContentTable($this->setting('hide_private_items'),false,$sqlJoin);

		if ($this->setting('category')){
			$sql .= "
				AND  cil.category_id=" .(int) $this->setting('category') ;
		}
		
		$sql .= '
			AND start_date <= LAST_DAY("'. ze\escape::sql($year . '-' . $month . '-01') . '")
			AND end_date >= "'. ze\escape::sql($year . '-' . $month . '-01') . '"';
	
		if (count($langs)>0){
			$sql .= "
				AND (FALSE ";
			foreach ($langs as $lang){
				$sql .= " OR c.language_id='" . ze\escape::sql($lang) . "'"; 
			}
			$sql .= ") ";
		}
		$sql .= "
			LIMIT 1";
 
 		$result = ze\sql::select($sql);
		$events = [];
		while($row = ze\sql::fetchAssoc($result)) {
			$events[] = $row;
		}
		
		return $events;
	}

	function isEventDay($year,$month,$day,$langs) {
		$sql = "
			SELECT DISTINCT c.id";
		$sqlJoin = "
			INNER JOIN " . DB_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event AS ce
				ON v.id = ce.id
				AND v.version = ce.version
				AND v.type = 'event'
			LEFT JOIN " . DB_PREFIX . "category_item_link as cil 
				ON c.equiv_id = cil.equiv_id
				AND c.type = cil.content_type";
				
		$sql .= ze\content::sqlToSearchContentTable($this->setting('hide_private_items'),false,$sqlJoin);
		
		if ($this->setting('category')){
			$sql .= "
				AND  cil.category_id=" . (int)$this->setting('category') ;
		}
		
		$sql .=  '
			AND start_date <= "'. ze\escape::sql($year . '-' . $month . '-' . $day) .'"
			AND end_date >= "'. ze\escape::sql($year . '-' . $month . '-' . $day) .'"';
	
		if (ze\module::inc('event_days_and_dates')){
			switch (date('N',mktime(0,0,0,$month,$day,$year))){
				case '1':
					$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_mon_on ";
					break;
				case '2':
					$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_tue_on ";
					break;
				case '3':
					$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_wed_on ";
					break;
				case '4':
					$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_thu_on ";
					break;
				case '5':
					$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_fri_on ";
					break;
				case '6':
					$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_sat_on ";
					break;
				case '7':
					$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_sun_on ";
					break;
			}
		}
	
		if (count($langs) > 0) {
			$sql .= "
				AND (FALSE ";
			foreach ($langs as $lang){
				$sql .= " OR c.language_id='" . ze\escape::sql($lang) . "'"; 
			}
			$sql .= ") ";
		}
		$sql .= "
			LIMIT 1";
 
 		if (ze\sql::numRows($result = ze\sql::select($sql)) >0 ) {
			return true;
		} else {
			return false;
		}
	}
	
	//num of events for the day
	function getEventDay($year, $month, $day, $langs) {
		// Sanitize input
		$year = (int)$year;
		$month = (int)$month;
		$day = (int)$day;
		
		$sql = "
			SELECT DISTINCT c.id";
		$sqlJoin = "
			INNER JOIN " . DB_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event AS ce
				ON v.id = ce.id
				AND v.version = ce.version
				AND v.type = 'event'
			LEFT JOIN " . DB_PREFIX . "category_item_link as cil 
				ON c.equiv_id = cil.equiv_id
				AND c.type = cil.content_type";
				
		$sql .= ze\content::sqlToSearchContentTable($this->setting('hide_private_items'),false,$sqlJoin);
		
		if ($this->setting('category')){
			$sql .= "
				AND  cil.category_id=" . (int)$this->setting('category') ;
		}
		$sql .=  '
			AND start_date <= "'. ze\escape::sql($year . '-' . $month . '-' . $day) .'"
			AND end_date >= "'. ze\escape::sql($year . '-' . $month . '-' . $day) .'"';
	
		if (ze\module::inc('event_days_and_dates')){
			switch (date('N',mktime(0,0,0,$month,$day,$year))){
				case '1':
					$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_mon_on ";
					break;
				case '2':
					$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_tue_on ";
					break;
				case '3':
					$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_wed_on ";
					break;
				case '4':
					$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_thu_on ";
					break;
				case '5':
					$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_fri_on ";
					break;
				case '6':
					$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_sat_on ";
					break;
				case '7':
					$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_sun_on ";
					break;
			}
		}
	
		if (count($langs) > 0) {
			$sql .= "
				AND (FALSE ";
			foreach ($langs as $lang) {
				$sql .= " OR c.language_id='" . ze\escape::sql($lang) . "'"; 
			}
			$sql .= ") ";
		}
		
		$result = ze\sql::select($sql);
		$events = [];
		while($row = ze\sql::fetchAssoc($result)) {
			$events[] = $row;
		}
		if ($events) {
			$numerOfEvents = count($events);
			return $numerOfEvents;
		} else {
			return 0;
		}
	}


	function getEventsDesc($year, $month, $day, $langs) {
		// Sanitize input
		$year = (int)$year;
		$month = (int)$month;
		$day = (int)$day;
		
		$sql = "
			SELECT DISTINCT 
				ce.id,
				ce.version,
				v.title,
				v.content_summary,
				c.status,
				c.language_id,
				ce.start_date,
				ce.start_time,
				ce.specify_time,
				ce.next_day_finish,
				ce.end_date,
				ce.end_time,
				cil.content_type,
				description";
		$sqlJoin = "
			INNER JOIN " . DB_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event AS ce
				ON v.id = ce.id
				AND v.version = ce.version
				AND v.type = 'event'
			LEFT JOIN " . DB_PREFIX . "category_item_link as cil 
				ON  c.equiv_id = cil.equiv_id
				AND c.type = cil.content_type";

		$sql .= ze\content::sqlToSearchContentTable($this->setting('hide_private_items'), false, $sqlJoin);

		if ($this->setting('category')){
			$sql .= "
				AND  cil.category_id=" . (int) $this->setting('category') ;
		}
		
		if ($day){
			//month view
			$sql .=  '
				AND start_date <= "'. ze\escape::sql($year . '-' . $month . '-' . $day) .'"
				AND end_date >= "'. ze\escape::sql($year . '-' . $month . '-' . $day) .'"';
			if (ze\module::inc('event_days_and_dates')) {
				switch (date('N',mktime(0,0,0,$month,$day,$year))) {
					case '1':
						$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_mon_on ";
						break;
					case '2':
						$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_tue_on ";
						break;
					case '3':
						$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_wed_on ";
						break;
					case '4':
						$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_thu_on ";
						break;
					case '5':
						$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_fri_on ";
						break;
					case '6':
						$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_sat_on ";
						break;
					case '7':
						$sql .= " AND IFNULL(stop_dates, '') not like '%" . ze\escape::sql($year . '-' . $month . '-' . $day) . "%' AND day_sun_on ";
						break;
				}
			}
		} else {
			//year view
			$sql .=  '
				AND start_date <= LAST_DAY("'. ze\escape::sql($year . '-' . $month . '-01') . '")
				AND end_date >= "'. ze\escape::sql($year . '-' . $month . '-01') . '"';
		}
	
		if (count($langs) > 0) {
			$sql .= "
				AND (FALSE ";
			foreach ($langs as $lang){
				$sql .= " OR c.language_id='" . ze\escape::sql($lang) . "'"; 
			}
			$sql .= ") ";
		}
		$sql .= "
			ORDER BY end_date,end_time ";
		 
 		$retVal = [];
 		if (ze\sql::numRows($result=ze\sql::select($sql)) > 0 ) {
			while($row=ze\sql::fetchAssoc($result)){
				if ((!($this->setting('hide_private_items'))) || ze\content::checkPerm($row['id'], 'event', $row['version'])) {
					$retVal[] = [
						'id' => $row['id'],
						'version' => $row['version'],
						'title' => $row['title'],
						'language_id' => $row['language_id'],
						'specify_time' => $row['specify_time'],
						'next_day_finish' => $row['next_day_finish'],
						'start_date' => $row['start_date'],
						'start_time' => $row['start_time'],
						'end_date' => $row['end_date'],
						'end_time' => $row['end_time'],
						'description' => $row['description'],
						'content_summary' => $row['content_summary']
					];
				}
			}
		}
		return $retVal;
	}
	
	function getInstalledLangIDs() {
		$arr = ze\lang::getLanguages();
		foreach ($arr as $a) {
			$retVal[] = $a['id'];
		}
		return $retVal;
	}

	function formatTimePeriod($timeFrom='', $timeTo='', $separator = ' - ' ) {
		$rv = '';
		if ($timeFrom) {
			$rv = ze\date::formatTime($timeFrom, ze::setting('vis_time_format'), '');
			if ($timeTo) {
				$rv .=  $separator . ze\date::formatTime($timeTo, ze::setting('vis_time_format'), '');
			} 
		} 
		return $rv;
	}

	function getMinAndMaxYear() {
		$sql = "
			SELECT 
				MIN(YEAR (ce.start_date)) AS min_year,
				MAX(YEAR (ce.end_date)) AS max_year";
		$sqlJoin = "
			INNER JOIN " . DB_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event AS ce
				ON v.id = ce.id
				AND v.version = ce.version
				AND v.type = 'event'
			LEFT JOIN " . DB_PREFIX . "category_item_link as cil 
				ON  c.equiv_id = cil.equiv_id
				AND c.type = cil.content_type";

		$sql .= ze\content::sqlToSearchContentTable($this->setting('hide_private_items'), false, $sqlJoin);

		if ($this->setting('category')){
			$sql .= "
				AND  cil.category_id=" . (int) $this->setting('category') ;
		}

		//Limit previous periods
		$limit = $this->setting('past_periods_limit') ?: 0;

		$sql .= "
			AND (
				YEAR(ce.start_date) >= YEAR(DATE_ADD(NOW(), INTERVAL -" . (int) $limit . " YEAR))
				AND YEAR(ce.end_date) >= YEAR(DATE_ADD(NOW(), INTERVAL -" . (int) $limit . " YEAR))
			)";

		$result = ze\sql::select($sql);
		return ze\sql::fetchAssoc($result);
	}

	function showFloatingBox() {
		$langIDs = $this->getAllowedLanguages();
		$events = $this->getEventsDesc($_GET['year'] ?? false, ($_GET['month'] ?? false), ($_GET['day'] ?? false), $langIDs);
		
		$this->data['EFrame'] = true;
		$this->data['Single_event'] = [];
		
		if (count($events) > 0) {
			foreach ($events as $event) {
				/* Sticky image */
				$htmlStickyImage = "";
				$stickyImageEnabled = $this->setting('show_sticky_images');
				if ($stickyImageEnabled) {
					$stickyImageUrl = self::getStickyImage($event['id'], 'event', $event['version']);
					if ($stickyImageUrl) {
						$htmlStickyImage = "<div class='sticky_image'><img src=".$stickyImageUrl."></div>";
					}
				}
			
				$arr = [	
					'Event_title' => (htmlspecialchars($event['title']) ?: ''),
					'Event_summary'=> ($this->setting('show_summary') ? $event['content_summary'] :''),
					'SUBDIR' => SUBDIRECTORY . DIRECTORY_INDEX_FILENAME, 
					'Event_id' => (int) $event['id'], 
					'Content_type' => 'event',
					'StickyImage' => $htmlStickyImage
				];

				if (($event['start_date'] ?? false)==($event['end_date'] ?? false)){
					$arr['Time_of_event'] = $this->phrase('[[date]]', ['date'=>ze\date::format($event['start_date'] ?? false, $this->setting('date_format'), false, false)]);
				} else {
					$arr['Time_of_event'] = $this->phrase('[[start_date]] to [[end_date]]',['start_date'=>ze\date::format($event['start_date'] ?? false,$this->setting('date_format'),false,false)
																								,'end_date'=>ze\date::format($event['end_date'] ?? false,$this->setting('date_format'),false,false)]);
				}
				if ($event['specify_time'] && !empty($event['start_time']) && (($event['start_time'] ?? false)!='00:00:00')) {
					if ( $event['end_time'] && ($event['end_time']!='00:00:00' || $event['next_day_finish']) && (($event['start_time'] ?? false)!=($event['end_time'] ?? false))){
						$arr['Time_of_event'] .= " " . $this->phrase('[[start_time]] to [[end_time]]',['start_time'=>ze\date::formatTime($event['start_time'],ze::setting('vis_time_format'),''),
																									'end_time'=>ze\date::formatTime($event['end_time'],ze::setting('vis_time_format'),'')]);
					} else {
						$arr['Time_of_event'] .= " " .  $this->phrase('[[time]]',['time'=>ze\date::formatTime($event['start_time'],ze::setting('vis_time_format'),'')]);
					}
				}
				
				$this->data['Single_event'][] = $arr;
			}
		} else {
			$this->data['Single_event'][] = ['Event_title'=>htmlspecialchars($this->phrase('No Events on [[date]]', ['date' => ze\date::format(($_GET['year'] ?? false) . '-' . ($_GET['month'] ?? false) . '-' . ($_GET['day'] ?? false)  ,  $this->setting('date_format'),false,false)]))];
		}
		
		if ($_GET['day'] ?? false) {
			$numerOfEvents = $this->getEventDay($_GET['year'] ?? false, ($_GET['month'] ?? false), ($_GET['day'] ?? false), $langIDs);
			if ($numerOfEvents > 1) {
				$counter = $numerOfEvents." ".$this->phrase('events');
			} else {
				$counter = $numerOfEvents." ".$this->phrase('event');
			}
			
			
			if ($this->setting('event_count')) {
				$this->data['Close_popup_script'] = '$.colorbox.close();';
				$this->data['Date_of_event'] = ze\date::format(($_GET['year'] ?? false) . '-' . ($_GET['month'] ?? false) . '-' . ($_GET['day'] ?? false),  $this->setting('date_format'), false, false);
				$this->data['Event_counter_class_in_window'] = "<p class='event_count_in_window has_events'>".$counter."</p>";
			} else {
				$this->data['Close_popup_script'] = '$.colorbox.close();';
				$this->data['Date_of_event'] = ze\date::format(($_GET['year'] ?? false) . '-' . ($_GET['month'] ?? false) . '-' . ($_GET['day'] ?? false), $this->setting('date_format'), false, false);
				$this->data['Event_counter_class_in_window'] = "";
			}
		} else {
			$numerOfEvents = $this->getMonthEvent($_GET['year'] ?? false, ($_GET['month'] ?? false), $langIDs);
			if ($numerOfEvents > 1) {
				$counter = $numerOfEvents . " " . $this->phrase('events');
			} else {
				$counter = $numerOfEvents . " " . $this->phrase('event');
			}
			
			if ($this->setting('event_count')) {
				$this->data['Close_popup_script'] = '$.colorbox.close();';
				$this->data['Date_of_event'] = ze\date::format(($_GET['year'] ?? false) . '-' . ($_GET['month'] ?? false) . '-01', '[[_MONTH_LONG_%m]] %Y', false, false);
				$this->data['Event_counter_class_in_window'] = "<p class='event_count_in_window has_events'>".$counter."</p>";
			} else {
				$this->data['Close_popup_script'] = '$.colorbox.close();';
				$this->data['Date_of_event'] = ze\date::format(($_GET['year'] ?? false) . '-' . ($_GET['month'] ?? false) . '-01', '[[_MONTH_LONG_%m]] %Y', false, false);
				$this->data['Event_counter_class_in_window'] = "";
			}
		}
		
		$this->twigFramework($this->data);
	}
		
	function getAllowedLanguages() {
		if ($this->setting('language_selection') == 'visitor') {
			return [ze::$langId];
		} else {
			return $this->getInstalledLangIDs();
		}
	}
	
	public function getStickyImage($id, $type, $version) {
		$width = $height = $url = false;
		ze\file::itemStickyImageLink($width, $height, $url, $id, $type, $version, $this->setting('width'), $this->setting('height'), $this->setting('canvas'));
		if ($url) {
			return htmlspecialchars($url);
		} else {
			return false;
		}
	}
	

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				$fields['first_tab/first_day_of_week']['hidden'] = ($values['first_tab/view_mode'] ?? false) != 'month_view';
				$fields['first_tab/months_format']['hidden'] = ($values['first_tab/view_mode'] ?? false) == 'month_view';
				
				if ($values['first_tab/view_mode'] == 'year_view' && empty($values['first_tab/months_format'])) {
					$values['first_tab/months_format'] = 'months_short_name';
				}
				
				$hidden = !($values['popout/enable_popup'] && $values['popout/show_sticky_images']);
				$this->showHideImageOptions($fields, $values, 'popout', $hidden);
				break;
		}
	}
}