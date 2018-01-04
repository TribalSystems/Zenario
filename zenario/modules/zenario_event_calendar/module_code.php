<?php
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed'); 
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


class zenario_event_calendar extends module_base_class {

		var $content;
		var $displaySections;
		var $mergeFields;
		var $missingFieldsErrors;
		var $incorrectEmailFormatErrors;
        var $errors;
		

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
	} 


	function showMonthView(){
	
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
		switch ($this->setting('first_day_of_week'))
			{
				case 'Monday':
					$calendarStartDayOfWeek=1;break;
				case 'Tuesday':
					$calendarStartDayOfWeek=2;break;
				case 'Wednesday':
					$calendarStartDayOfWeek=3;break;
				case 'Thursday':
					$calendarStartDayOfWeek=4;break;
				case 'Friday':
					$calendarStartDayOfWeek=5;break;
				case 'Saturday':
					$calendarStartDayOfWeek=6;break;
				case 'Sunday':
					$calendarStartDayOfWeek=7;break;
				default:
					$calendarStartDayOfWeek=7;break;
					
			}
		//$calendarStartDayOfWeek=1;
		$currentMonth=mktime(0,0,0,$month,1,$year);

		//currentMonthStartDayOfWeek - Monday - 1,..., Sunday -7
		$currentMonthStartDayOfWeek=date('N',$currentMonth);		
		
		//in which column will the 1st day of the Month appeaar
		$currentMonthStartPosition = (($currentMonthStartDayOfWeek - $calendarStartDayOfWeek  + 7) % 7)+1; 

		$currentMonthLength = date('t',$currentMonth);

		$langIDs = $this->getAllowedLanguages();
		
		
			$this->frameworkHead('Event_calendar_month_view','Calendar_month_view_content',array('Full_date'=>formatDateNicely(date("Y-m-d",$currentMonth),"[[_MONTH_LONG_%m]] %Y",false,false),
																				    'Year_number'=>(string)(int)$year),
																			 array('Calendar_month_view_title'=>true,'Calendar_month_view_header'=>true));
																			 
																			 
																			 
			$this->frameworkHead('Calendar_month_view_content','Days_row_element',array(
																				  'Cal_day_1'=>mb_substr(phrase('_WEEKDAY_' . (string) ($calendarStartDayOfWeek  ) % 7, array(), false), 0,1,'utf8'),
																				  'Cal_day_2'=>mb_substr(phrase('_WEEKDAY_' . (string) ($calendarStartDayOfWeek+1) % 7, array(), false), 0,1,'utf8'),
																				  'Cal_day_3'=>mb_substr(phrase('_WEEKDAY_' . (string) ($calendarStartDayOfWeek+2) % 7, array(), false), 0,1,'utf8'),
																				  'Cal_day_4'=>mb_substr(phrase('_WEEKDAY_' . (string) ($calendarStartDayOfWeek+3) % 7, array(), false), 0,1,'utf8'),
																				  'Cal_day_5'=>mb_substr(phrase('_WEEKDAY_' . (string) ($calendarStartDayOfWeek+4) % 7, array(), false), 0,1,'utf8'),
																				  'Cal_day_6'=>mb_substr(phrase('_WEEKDAY_' . (string) ($calendarStartDayOfWeek+5) % 7, array(), false), 0,1,'utf8'),
																				  'Cal_day_7'=>mb_substr(phrase('_WEEKDAY_' . (string) ($calendarStartDayOfWeek+6) % 7, array(), false), 0,1,'utf8')																					
																					)
																			,array('Table_header'=>true));
			for ($i=1;$i<$currentMonthStartPosition;$i++){
				$mergeFields[]=array('Day_class_name'=>'empty_day_cell','Day_label'=> ' ','Day_event_span' => "");
			}
			
			
			
			$j=1;
			for ($i;($i<=7*6)&&($j<=$currentMonthLength);$i++){
			
				$numberOfEvents = $this->getEventDay($year,$month,$j,$langIDs);
				//var_dump($numberOfEvents);
			
				if ($this->isEventDay($year,$month,$j,$langIDs)){
					if ( ($j==date('j',time())) && ($month==date('n',time())) && ($year==date('Y',time()))   ){ 
						$day_class_name_var = 'today';
					} else {
						$day_class_name_var = 'day';
					}
					
					
					if ($this->setting('event_count') == "event_count_on"){
						$mergeFields[]=
							array('Anchor'=>
								' rel="colorbox" href="'. htmlspecialchars(
									$this->showFloatingBoxLink("&mode=month_view&day=" . (string)(int)$j . "&month=" . (string)(int)$month . "&year=" . (string)(int)$year,1,true,300,-150,17,false,0)
								). '"',
								'Day_class_name'=>$day_class_name_var,
								'Td_day_class_name'=>'event',
								'Day_label'=> (string)(int)($j++),
								'Day_event_span' => "<span class='event_count has_events'>".$numberOfEvents."</span>");
								
					}elseif($this->setting('event_count') == "event_count_off"){
					
						$mergeFields[]=
							array('Anchor'=>
								' rel="colorbox" href="'. htmlspecialchars(
									$this->showFloatingBoxLink("&mode=month_view&day=" . (string)(int)$j . "&month=" . (string)(int)$month . "&year=" . (string)(int)$year,1,true,300,-150,17,false,0)
								). '"',
								'Day_class_name'=>$day_class_name_var,
								'Td_day_class_name'=>'event',
								'Day_label'=> (string)(int)($j++),
								'Day_event_span' => "");
					}
					
				} else {
					if ( ($j==date('j',time())) && ($month==date('n',time())) && ($year==date('Y',time()))   ){ 
						$mergeFields[]=array('Day_class_name'=>'today','Td_day_class_name'=>'today','Day_label'=> (string)(int)($j++),'Day_event_span' => "");
					} else {
						$mergeFields[]=array('Day_class_name'=>'day','Day_label'=> (string)(int)($j++),'Day_event_span' => "" );
					}
				}
				if (($i%7)==0) {
					$this->frameworkHead('Days_row_element');
					$this->framework('Days_cell_element',$mergeFields);
					$this->frameworkFoot('Days_row_element');
					$mergeFields=array();
				}
			}
			if (count($mergeFields)>0){
					$this->frameworkHead('Days_row_element');
					$this->framework('Days_cell_element',$mergeFields);
					$this->frameworkFoot('Days_row_element');
					$mergeFields=array();
				} 
			$this->frameworkFoot('Calendar_month_view_content','Days_row_element',array(),array());
			$this->frameworkFoot('Event_calendar_month_view','Calendar_month_view_content',array('Previous_month_onclick'=>($previousYear>1969&&$previousYear<2038)?$this->refreshPluginSlotJS('&month=' . (string) (int)$previousMonth . '&year=' . (string)(int) $previousYear):"",
																					'Previous_month_name'=>($previousYear>1969&&$previousYear<2038)?formatDateNicely(date("Y-m-d",mktime(0,0,0,$previousMonth,1,$previousYear)),"[[_MONTH_LONG_%m]] ",false,false):"" ,
																					'Next_month_onclick'=>($nextYear>1969&&$nextYear<2038)?$this->refreshPluginSlotJS('&month=' . (string) (int)$nextMonth . '&year=' . (string)(int)$nextYear):"",
																					'Next_month_name'=>($nextYear>1969&&$nextYear<2038)?formatDateNicely(date("Y-m-d",mktime(0,0,0,$nextMonth,1,$nextYear)),"[[_MONTH_LONG_%m]] ",false,false):"") ,array('Calendar_month_view_footer'=>true));
	}

	public function showYearView(){
	
	
		$monthFormat = $this->setting('months_format');
	
		$langIDs = array();
		if (($_GET['year'] ?? false) && (($_GET['year'] ?? false)>1969) && (($_GET['year'] ?? false)<2038)){
			$year=(int)($_GET['year'] ?? false);
		} else {
			$year=date('Y',time());
		}
		$currentYear=mktime(0,0,0,0,1,$year);
		$previousYear=$year-1;
		$nextYear=$year+1;
		$this->frameworkHead('Event_calendar_year_view','Calendar_year_view_content',array('Full_date'=>formatDateNicely(date("Y-m-d",$currentYear),"[[_MONTH_LONG_%m]] %Y",false,false),
																									   'Year_number'=>(string)(int)$year),
																		 						array('Calendar_year_view_title'=>true,'Calendar_year_view_header'=>true));
		$this->frameworkHead('Calendar_year_view_content');
		for ($i=0;$i<3;$i++){
			$mergeFields = array();
			for ($j=0;$j<4;$j++){
				$month=$i*4+$j + 1;
				
					
					$lang = $this->getAllowedLanguages();
					$monthEvents=$this->getMonthEvent($year,$month,$lang);
					
				
				if ($this->isEventMonth($year,$month,$langIDs)){
					if (($month==date('n',time())) && ($year==date('Y',time()))){ 
						$currentMonthClass = 'current_month';
					} else {
						$currentMonthClass = '';
					}
					

					$monthShort = formatDateNicely(date("Y-m-d",mktime(0,0,0,$month,1,$year)),"[[_MONTH_SHORT_%m]] ",false,false);
					$monthLong = formatDateNicely(date("Y-m-d",mktime(0,0,0,$month,1,$year)),"[[_MONTH_LONG_%m]] ",false,false);
					
					if ($monthFormat == "months_short_name"){
						$monthLabel = $monthShort;
					}elseif($monthFormat == "months_long_name"){
						$monthLabel = $monthLong;
					}
					
					
					if ($this->setting('event_count') == "event_count_on"){
					
					$mergeFields[]= array(	'Anchor' =>' rel="colorbox" href="'. htmlspecialchars(
													$this->showFloatingBoxLink("&mode=year_view&month=" . (string)(int)$month . "&year=" . (string)(int)$year,1,true,300,-150,17,false,0)
												). '"',
											'Current_month'=>$currentMonthClass,
											'Month_with_events'=>'month_with_events',
											'Month_label'=> $monthLabel,
											'Month_event_span' => "<span class='event_count has_events'>".$monthEvents."</span>");
											
											
					}elseif($this->setting('event_count') == "event_count_off"){
					
						$mergeFields[]= array(	'Anchor' =>' rel="colorbox" href="'. htmlspecialchars(
									$this->showFloatingBoxLink("&mode=year_view&month=" . (string)(int)$month . "&year=" . (string)(int)$year,1,true,300,-150,17,false,0)
								). '"',
							'Current_month'=>$currentMonthClass,
							'Month_with_events'=>'month_with_events',
							'Month_label'=> $monthLabel,
							'Month_event_span' => "");
					}
											
											
				} else {
					if ( ($month==date('n',time())) && ($year==date('Y',time()))   ){ 
					
						$monthShort = formatDateNicely(date("Y-m-d",mktime(0,0,0,$month,1,$year)),"[[_MONTH_SHORT_%m]] ",false,false);
						$monthLong = formatDateNicely(date("Y-m-d",mktime(0,0,0,$month,1,$year)),"[[_MONTH_LONG_%m]] ",false,false);
						
						if ($monthFormat == "months_short_name"){
							$monthLabel = $monthShort;
						}elseif($monthFormat == "months_long_name"){
							$monthLabel = $monthLong;
						}
						
						$mergeFields[]=array('Current_month'=>'current_month','Month_label'=> $monthLabel,'Month_event_span' => "");
						
					} else {
						
						$monthShort = formatDateNicely(date("Y-m-d",mktime(0,0,0,$month,1,$year)),"[[_MONTH_SHORT_%m]] ",false,false);
						$monthLong =  formatDateNicely(date("Y-m-d",mktime(0,0,0,$month,1,$year)),"[[_MONTH_LONG_%m]] ",false,false);
						
						
						if ($monthFormat == "months_short_name"){
							$monthLabel = $monthShort;
						}elseif($monthFormat == "months_long_name"){
							$monthLabel = $monthLong;
						}
						//No events
						$mergeFields[]=array('Current_month'=>'','Month_label'=> $monthLabel,'Month_event_span' => "");
					
					}
				}
			}
			$this->frameworkHead('Months_row_element');
			$this->framework('Months_cell_element',$mergeFields);
			$this->frameworkFoot('Months_row_element');
		}
		$this->frameworkFoot('Calendar_year_view_content');
		$this->framework('Calendar_year_view_footer',array(
															'Previous_year_onclick'=>($previousYear>1969&&$previousYear<2038)?$this->refreshPluginSlotJS('&year=' . (string)(int)$previousYear):"",
															'Previous_year_name'=> ($previousYear>1969&&$previousYear<2038)?(string) $previousYear:"",
															'Next_year_onclick'=>($nextYear>1969&&$nextYear<2038)?$this->refreshPluginSlotJS('&year=' . (string)(int)$nextYear):"",
															'Next_year_name'=>($nextYear>1969&&$nextYear<2038)?(string)$nextYear:"")
															);
		$this->frameworkFoot('Event_calendar_year_view','Calendar_year_view_footer',array(),array());
	}
		

	function isEventMonth($year,$month,$langs){
		$year = (int)$year;
		$month = (int)$month;
		$sql = "SELECT DISTINCT 
					c.id
				";

		$sqlJoin = "
				INNER JOIN " . DB_NAME_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event AS ce
					ON v.id = ce.id
					AND v.version = ce.version
					AND v.type = 'event'
				LEFT JOIN "
					. DB_NAME_PREFIX . "category_item_link as cil 
				ON 
						c.equiv_id = cil.equiv_id
					AND c.type = cil.content_type";
				
		$sql .= sqlToSearchContentTable($this->setting('hide_private_items'),false,$sqlJoin);

		if ($this->setting('category')){
			$sql .= " AND  cil.category_id=" .(int) $this->setting('category') ;
		}
		
		$sql .=  ' AND start_date <= LAST_DAY("'. sqlEscape($year . '-' . $month . '-01') . '")';
		$sql .=  ' AND end_date >= "'. sqlEscape($year . '-' . $month . '-01') . '"';
	
		if (count($langs)>0){
				$sql .=" AND (FALSE ";
				foreach ($langs as $lang){
					$sql .= " OR c.language_id='" . sqlEscape($lang) . "'"; 
				}
				$sql .=") ";
		 	}
		$sql .= " LIMIT 1";
 
 		if (sqlNumRows($result=sqlQuery($sql))>0 ){
			return true;
		} else {
			return false;
		}
	}

	//num of events for the month
	function getMonthEvent($year,$month,$langs){
		$year = (int)$year;
		$month = (int)$month;
		$sql = "SELECT DISTINCT 
					c.id
				";

		$sqlJoin = "
				INNER JOIN " . DB_NAME_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event AS ce
					ON v.id = ce.id
					AND v.version = ce.version
					AND v.type = 'event'
				LEFT JOIN "
					. DB_NAME_PREFIX . "category_item_link as cil 
				ON 
						c.equiv_id = cil.equiv_id
					AND c.type = cil.content_type";
				
		$sql .= sqlToSearchContentTable($this->setting('hide_private_items'),false,$sqlJoin);

		if ($this->setting('category')){
			$sql .= " AND  cil.category_id=" .(int) $this->setting('category') ;
		}
		
		$sql .=  ' AND start_date <= LAST_DAY("'. sqlEscape($year . '-' . $month . '-01') . '")';
		$sql .=  ' AND end_date >= "'. sqlEscape($year . '-' . $month . '-01') . '"';
	
		if (count($langs)>0){
				$sql .=" AND (FALSE ";
				foreach ($langs as $lang){
					$sql .= " OR c.language_id='" . sqlEscape($lang) . "'"; 
				}
				$sql .=") ";
		 	}
		//$sql .= " LIMIT 1";
 
 
 		$result = sqlQuery($sql);
		$events = array();
		while($row = sqlFetchAssoc($result)) {
			$events[] = $row;
		}
		
		if ($events){
			$numerOfEvents=count($events);
			return $numerOfEvents;
		}else{
			return 0;
		}
	}





	function isEventDay($year,$month,$day,$langs){
		$sql = "SELECT DISTINCT 
					c.id
				";

		$sqlJoin = "
				INNER JOIN " . DB_NAME_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event AS ce
					ON v.id = ce.id
					AND v.version = ce.version
					AND v.type = 'event'
				LEFT JOIN "
					. DB_NAME_PREFIX . "category_item_link as cil 
				ON 
						c.equiv_id = cil.equiv_id
					AND c.type = cil.content_type";
				
		$sql .= sqlToSearchContentTable($this->setting('hide_private_items'),false,$sqlJoin);
		
		if ($this->setting('category')){
			$sql .= " AND  cil.category_id=" . (int)$this->setting('category') ;
		}
		
		$sql .=  ' AND start_date <= "'. sqlEscape($year . '-' . $month . '-' . $day) .'"';
		$sql .=  ' AND end_date >= "'. sqlEscape($year . '-' . $month . '-' . $day) .'"';
	
		if (inc('event_days_and_dates')){
			switch (date('N',mktime(0,0,0,$month,$day,$year))){
				case '1':
					$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_mon_on ";
					break;
				case '2':
					$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_tue_on ";
					break;
				case '3':
					$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_wed_on ";
					break;
				case '4':
					$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_thu_on ";
					break;
				case '5':
					$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_fri_on ";
					break;
				case '6':
					$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_sat_on ";
					break;
				case '7':
					$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_sun_on ";
					break;
			}
		}
	
		if (count($langs)>0){
				$sql .=" AND (FALSE ";
				foreach ($langs as $lang){
					$sql .= " OR c.language_id='" . sqlEscape($lang) . "'"; 
				}
				$sql .=") ";
		 	}
		$sql .= " LIMIT 1";
 
 		if (sqlNumRows($result=sqlQuery($sql))>0 ){
			return true;
		} else {
			return false;
		}
	}
	
	//num of events for the day
	function getEventDay($year,$month,$day,$langs){
		// Sanitize input
		$year = (int)$year;
		$month = (int)$month;
		$day = (int)$day;
		
		$sql = "SELECT DISTINCT 
					c.id
				";

		$sqlJoin = "
				INNER JOIN " . DB_NAME_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event AS ce
					ON v.id = ce.id
					AND v.version = ce.version
					AND v.type = 'event'
				LEFT JOIN "
					. DB_NAME_PREFIX . "category_item_link as cil 
				ON 
						c.equiv_id = cil.equiv_id
					AND c.type = cil.content_type";
				
		$sql .= sqlToSearchContentTable($this->setting('hide_private_items'),false,$sqlJoin);
		
		if ($this->setting('category')){
			$sql .= " AND  cil.category_id=" . (int)$this->setting('category') ;
		}
		$sql .=  ' AND start_date <= "'. sqlEscape($year . '-' . $month . '-' . $day) .'"';
		$sql .=  ' AND end_date >= "'. sqlEscape($year . '-' . $month . '-' . $day) .'"';
	
		if (inc('event_days_and_dates')){
			switch (date('N',mktime(0,0,0,$month,$day,$year))){
				case '1':
					$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_mon_on ";
					break;
				case '2':
					$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_tue_on ";
					break;
				case '3':
					$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_wed_on ";
					break;
				case '4':
					$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_thu_on ";
					break;
				case '5':
					$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_fri_on ";
					break;
				case '6':
					$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_sat_on ";
					break;
				case '7':
					$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_sun_on ";
					break;
			}
		}
	
		if (count($langs)>0){
				$sql .=" AND (FALSE ";
				foreach ($langs as $lang){
					$sql .= " OR c.language_id='" . sqlEscape($lang) . "'"; 
				}
				$sql .=") ";
		 	}
		//$sql .= " LIMIT 1";
		
		$result = sqlQuery($sql);
		$events = array();
		while($row = sqlFetchAssoc($result)) {
			$events[] = $row;
		}
		
		if ($events){
			$numerOfEvents=count($events);
			return $numerOfEvents;
		}else{
			return 0;
		}
	}


	function getEventsDesc($year,$month,$day,$langs){
		// Sanitize input
		$year = (int)$year;
		$month = (int)$month;
		$day = (int)$day;
		
		$sql = "SELECT DISTINCT 
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
					description
				";
				
		$sqlJoin = "
				INNER JOIN " . DB_NAME_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event AS ce
					ON v.id = ce.id
					AND v.version = ce.version
					AND v.type = 'event'
				LEFT JOIN "
					. DB_NAME_PREFIX . "category_item_link as cil 
				ON 
						c.equiv_id = cil.equiv_id
					AND c.type = cil.content_type
				";

		$sql .= sqlToSearchContentTable($this->setting('hide_private_items'),false,$sqlJoin);

		if ($this->setting('category')){
			$sql .= " AND  cil.category_id=" . (int) $this->setting('category') ;
		}
		
		if ($day){
			//month view
			$sql .=  ' AND start_date <= "'. sqlEscape($year . '-' . $month . '-' . $day) .'"';
			$sql .=  ' AND end_date >= "'. sqlEscape($year . '-' . $month . '-' . $day) .'"';
			if (inc('event_days_and_dates')){
				switch (date('N',mktime(0,0,0,$month,$day,$year))){
					case '1':
						$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_mon_on ";
						break;
					case '2':
						$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_tue_on ";
						break;
					case '3':
						$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_wed_on ";
						break;
					case '4':
						$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_thu_on ";
						break;
					case '5':
						$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_fri_on ";
						break;
					case '6':
						$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_sat_on ";
						break;
					case '7':
						$sql .= " AND IFNULL(stop_dates,'') not like '%" . sqlEscape($year . '-' . $month . '-' . $day) . "%' AND day_sun_on ";
						break;
				}
			}
		} else {
			//year view
			$sql .=  ' AND start_date <= LAST_DAY("'. sqlEscape($year . '-' . $month . '-01') . '")';
			$sql .=  ' AND end_date >= "'. sqlEscape($year . '-' . $month . '-01') . '"';
		}
	
		if (count($langs)>0){
				$sql .=" AND (FALSE ";
				foreach ($langs as $lang){
					$sql .= " OR c.language_id='" . sqlEscape($lang) . "'"; 
				}
				$sql .=") ";
		 	}
		$sql .= " ORDER BY end_date,end_time ";
		 
 		if (sqlNumRows($result=sqlQuery($sql))>0 ){
			while($row=sqlFetchArray($result)){
				if ((!($this->setting('hide_private_items'))) || checkPerm($row['id'],'event',$row['version'])){
					$retVal[]=array('id'=>$row['id'],'version'=>$row['version'],'title'=>$row['title'],'language_id'=>$row['language_id'],
									'specify_time'=>$row['specify_time'],'next_day_finish'=>$row['next_day_finish'],'start_date'=>$row['start_date'],
									'start_time'=>$row['start_time'],'end_date'=>$row['end_date'],'end_time'=>$row['end_time'],'description'=>$row['description'],
									'content_summary'=>$row['content_summary']);
				}
			}
		}
		return $retVal;
	}
	

	function getInstalledLangIDs(){
		$arr=getLanguages();
		foreach ($arr as $a){
			$retVal[]=$a['id'];
		}
		return $retVal;
	}

	function formatTimePeriod($timeFrom='', $timeTo='',$separator=' - ' ){
		$rv = '';
		if ($timeFrom){
			$rv = formatTimeNicely($timeFrom,setting('vis_time_format'),'');
			if ($timeTo){
				$rv .=  $separator . formatTimeNicely($timeTo,setting('vis_time_format'),'');
			} 
		} 
		return $rv;
	}

	function showFloatingBox(){
	
		$langIDs = $this->getAllowedLanguages();
		
		$events = $this->getEventsDesc($_GET['year'] ?? false,($_GET['month'] ?? false),($_GET['day'] ?? false),$langIDs);
		

		if (count($events)>0){
			foreach ($events as $event){
				
				/* Sticky image */
				$stickyImageEnabled=$this->setting('show_sticky_images');
				if ($stickyImageEnabled) {
					$stickyImageUrl = self::getStickyImage($event['id'],'event',$event['version']);
					if ($stickyImageUrl){
						$htmlStickyImage="<div class='sticky_image'><img src=".$stickyImageUrl."></div>";
					}else{
						$htmlStickyImage="";
					}
				}else{
					$htmlStickyImage="";
				}
			
				$arr = array (	'Event_title'=> ($this->setting('show_title')?htmlspecialchars($event['title']):''),
								'Event_summary'=> ($this->setting('show_summary')?$event['content_summary']:''),
								'SUBDIR' => SUBDIRECTORY. DIRECTORY_INDEX_FILENAME, 
								'Event_id'=>(int)$event['id'], 
								'Content_type'=>'event',
								'StickyImage'=>$htmlStickyImage);

				if (($event['start_date'] ?? false)==($event['end_date'] ?? false)){
					$arr['Time_of_event'] = $this->phrase('_SINGLE_DAY_DATE_RANGE',array('date'=>formatDateNicely($event['start_date'] ?? false,$this->setting('date_format'),false,false)));
				} else {
					$arr['Time_of_event'] = $this->phrase('_MULTIPLE_DAYS_DATE_RANGE',array('start_date'=>formatDateNicely($event['start_date'] ?? false,$this->setting('date_format'),false,false)
																								,'end_date'=>formatDateNicely($event['end_date'] ?? false,$this->setting('date_format'),false,false)));
				}
				if ($event['specify_time'] && !empty($event['start_time']) && (($event['start_time'] ?? false)!='00:00:00')){
					if ( $event['end_time'] && ($event['end_time']!='00:00:00' || $event['next_day_finish']) && (($event['start_time'] ?? false)!=($event['end_time'] ?? false))){
						$arr['Time_of_event'] .= " " . $this->phrase('_MULTIPLE_HOURS_EVENT_RANGE',array('start_time'=>formatTimeNicely($event['start_time'],setting('vis_time_format'),''),
																									'end_time'=>formatTimeNicely($event['end_time'],setting('vis_time_format'),'')));
					} else {
						$arr['Time_of_event'] .= " " .  $this->phrase('_SINGLE_HOUR_EVENT_RANGE',array('time'=>formatTimeNicely($event['start_time'],setting('vis_time_format'),'')));
					}
				}
				

				$mergeFields[] = $arr;
			}
		} else {
			$mergeFields[]=array('Event_title'=>htmlspecialchars($this->phrase('_NO_EVENTS_ON', array('date' => formatDateNicely(($_GET['year'] ?? false) . '-' . ($_GET['month'] ?? false) . '-' . ($_GET['day'] ?? false)  ,  $this->setting('date_format'),false,false)))));
		}
		if ($_GET['day'] ?? false){
			$numerOfEvents=$this->getEventDay($_GET['year'] ?? false,($_GET['month'] ?? false),($_GET['day'] ?? false),$langIDs);
			if ($numerOfEvents>1){
				$counter = $numerOfEvents." ".$this->phrase('Events');
			}else{
				$counter = $numerOfEvents." ".$this->phrase('Event');
			}
			
			
			
			
			
			if ($this->setting('event_count') == "event_count_on"){
					$this->frameworkHead('EFrame','Single_event',array('Close_popup_script'=> '$.colorbox.close();', 
																				  'Date_of_event'=>formatDateNicely(($_GET['year'] ?? false) . '-' . ($_GET['month'] ?? false) . '-' . ($_GET['day'] ?? false),  
																															 $this->setting('date_format'),
																															 false,
																															 false), 
																					'Event_counter_class_in_window'=>"<p class='event_count_in_window has_events'>(".$counter.")</p>"));
			}elseif($this->setting('event_count') == "event_count_off"){
					$this->frameworkHead('EFrame','Single_event',array('Close_popup_script'=> '$.colorbox.close();', 
																		  'Date_of_event'=>formatDateNicely(($_GET['year'] ?? false) . '-' . ($_GET['month'] ?? false) . '-' . ($_GET['day'] ?? false),  
																													 $this->setting('date_format'),
																													 false,
																													 false), 
																			'Event_counter_class_in_window'=>""));	
			}
		} else {
			$numerOfEvents=$this->getMonthEvent($_GET['year'] ?? false,($_GET['month'] ?? false),$langIDs);
			if ($numerOfEvents>1){
				$counter = $numerOfEvents." ".$this->phrase('Events');
			}else{
				$counter = $numerOfEvents." ".$this->phrase('Event');
			}
			
			
			if ($this->setting('event_count') == "event_count_on"){
				$this->frameworkHead('EFrame','Single_event',array('Close_popup_script'=> '$.colorbox.close();', 
																			  'Date_of_event'=>formatDateNicely(($_GET['year'] ?? false) . '-' . ($_GET['month'] ?? false) . '-01',  
																														 '[[_MONTH_LONG_%m]] %Y',
																														 false,
																														 false), 
																				'Event_counter_class_in_window'=>"<p class='event_count_in_window has_events'>(".$counter.")</p>"));
		}elseif($this->setting('event_count') == "event_count_off"){
				$this->frameworkHead('EFrame','Single_event',array('Close_popup_script'=> '$.colorbox.close();', 
																			  'Date_of_event'=>formatDateNicely(($_GET['year'] ?? false) . '-' . ($_GET['month'] ?? false) . '-01',  
																														 '[[_MONTH_LONG_%m]] %Y',
																														 false,
																														 false), 
																				'Event_counter_class_in_window'=>""));
		}
		
	}
		$this->framework('Single_event',$mergeFields);
		$this->frameworkFoot('EFrame','Single_event');
		}
		
	function getAllowedLanguages(){
		if ($this->setting('language_selection')=='visitor'){
			return array(cms_core::$langId);
		} else {
			return $this->getInstalledLangIDs();
		}
	}
	

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path){
			case 'plugin_settings':
				$fields['calendar/first_day_of_week']['hidden'] = ($values['calendar/view_mode'] ?? false) != 'month_view';
				$fields['calendar/months_format']['hidden'] = ($values['calendar/view_mode'] ?? false) == 'month_view';
				
				$hidden = !$values['calendar/show_sticky_images'];
				$this->showHideImageOptions($fields, $values, 'calendar', $hidden);
				break;
		}
	}
	
	
	public function getStickyImage($id,$type,$version){
		$width = $height = $url = false;
		Ze\File::itemStickyImageLink($width, $height, $url, $id, $type, $version, $this->setting('width'), $this->setting('height'), $this->setting('canvas'));
		if($url){
			return htmlspecialchars($url);
		}else{
			return false;
		}
	}
}
?>