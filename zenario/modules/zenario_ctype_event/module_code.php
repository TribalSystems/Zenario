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




class zenario_ctype_event extends module_base_class {
	
	var	$targetID = false;
	var	$targetVersion = false;
	var	$targetType = false;

	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = false, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		return true;
	}
	
	function showSlot() {
		$weekdays = array();
		$this->subSections = array();

		if ($this->setting('show_details_and_link')=='another_content_item'){
			$item = $this->setting('another_event');
			if (count($arr = explode("_",$item))==2){
				$this->targetID = $arr[1];
				$this->targetType = $arr[0];
				if (!$this->targetVersion = getShowableVersion($this->targetID,$this->targetType)){
					return;
				}
			}
		}
		if (!($this->targetID && $this->targetVersion && $this->targetType)) {
			$this->targetID = $this->cID;
			$this->targetVersion = $this->cVersion;
			$this->targetType = $this->cType;
		}
		
		if ($this->targetType!='event') {
			if ((int)arrayKey($_SESSION,'admin_userid')){
				echo "This Plugin needs to be placed on an Event-type Content Item or configured to point to another Event-type Content Item. Please check your Plugin Settings.";
			}
			return;
		}

		if ($event=$this->getEventDetails($this->targetID,$this->targetVersion)){

			$this->mergeFields['title']=htmlspecialchars(getItemTitle($this->targetID, $this->targetType, $this->targetVersion));

			if (arrayKey($event,'start_date')==arrayKey($event,'end_date')){
				$this->mergeFields['dates_range']=$this->phrase('_SINGLE_DAY_DATE_RANGE',array('date'=>formatDateNicely(arrayKey($event,'start_date'),$this->setting('date_format'),false,false)));
			} else {
				$this->mergeFields['dates_range']=$this->phrase('_MULTIPLE_DAYS_DATE_RANGE',array('start_date'=>formatDateNicely(arrayKey($event,'start_date'),$this->setting('date_format'),false,false)
																								,'end_date'=>formatDateNicely(arrayKey($event,'end_date'),$this->setting('date_format'),false,false)));
			}
			
			if (inc('zenario_event_days_and_dates')){
				foreach (array('sun','mon','tue','wed','thu','fri','sat') as $K=>$day){
					if (($event['day_' . $day . '_on']) && ($event['day_' . $day . '_start_time']) && ($event['day_' . $day . '_start_time']!='00:00:00'))   {
						$weekdays[] = array('weekday' => phrase('_WEEKDAY_' . $K ),
											'time'=>(($event['day_' . $day . '_start_time']!=$event['day_' . $day . '_end_time'] && $event['day_' . $day . '_end_time'] && ($event['day_' . $day . '_end_time']!='00:00:00')) 
													 ? $this->phrase('_MULTIPLE_HOURS_EVENT_RANGE',array('start_time'=>formatTimeNicely($event['day_' . $day . '_start_time'],setting('vis_time_format'),''),
																										'end_time'=>formatTimeNicely($event['day_' . $day . '_end_time'],setting('vis_time_format'),'')))
													 : $this->phrase('_SINGLE_HOUR_EVENT_RANGE',array('time'=>formatTimeNicely($event['day_' . $day . '_start_time'],setting('vis_time_format'),'')))
													 )
											);
					}
				}
			}
			if ($weekdays){
				$this->subSections['Event_On_Weekday_Details']=$weekdays;
			} else {
				if (!empty($event['start_time']) && (arrayKey($event,'start_time')!='00:00:00')){
					if (!empty($event['end_time']) && (arrayKey($event,'start_time')!=arrayKey($event,'end_time'))){
						$this->mergeFields['dates_range'] .= " " . $this->phrase('_MULTIPLE_HOURS_EVENT_RANGE',array('start_time'=>formatTimeNicely($event['start_time'],setting('vis_time_format'),''),
																									'end_time'=>formatTimeNicely($event['end_time'],setting('vis_time_format'),'')));
					} else {
						$this->mergeFields['dates_range'] .= " " .  $this->phrase('_SINGLE_HOUR_EVENT_RANGE',array('time'=>formatTimeNicely($event['start_time'],setting('vis_time_format'),'')));
					}
				}
			}
		

			$stopDates = explode(',',$event['stop_dates']);
			if ($event['stop_dates'] && count($stopDates)){
				foreach ($stopDates as $K=>$stopDate){
					$stopDates[$K] = formatDateNicely($stopDate,$this->setting('date_format'),false,false);
				}
				$this->subSections['Stop_Dates'] =  array('1'=>array('stop_dates' => implode(', ',$stopDates)));
			}
			
			if ($event['url']){
				$this->subSections['More_Info_Url'] = array('1'=>array('event_url'=>htmlspecialchars($event['url'])));
			}

			$this->framework('Event_Details',$this->mergeFields,$this->subSections);
		}
	}	

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'zenario_content':
				if ($box['key']['cType'] == 'event' && arrayKey($box,'key','source_cID') && arrayKey($box,'key','source_cVersion') ) {
					$eventDetails = $this->getEventDetails($box['key']['source_cID'],$box['key']['source_cVersion']);
					
					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['start_date']['value'] = $eventDetails['start_date'];
					
					if ($box['tabs']['zenario_ctype_event__when_and_where']['fields']['start_date']['value']) {
						$box['tabs']['zenario_ctype_event__when_and_where']['fields']['start_date']['last_value'] = $box['tabs']['zenario_ctype_event__when_and_where']['fields']['start_date']['value'];
					}
					
					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['end_date']['value'] = $eventDetails['end_date'];
					
					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['specify_time']['value'] = $eventDetails['specify_time'];
					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['late_evening_event']['value'] = $eventDetails['next_day_finish'];
					
					$startTime = explode(":",$eventDetails['start_time']);
					$endTime = explode(":",$eventDetails['end_time']);
					
					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['start_time_hours']['value'] = arrayKey($startTime,0);
					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['start_time_minutes']['value'] = arrayKey($startTime,1);

					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['end_time_hours']['value'] = arrayKey($endTime,0);
					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['end_time_minutes']['value'] = arrayKey($endTime,1);

					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['url']['value'] = $eventDetails['url'];
					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['location']['value'] = $eventDetails['location_id'];
				}
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_content':
				if ($box['key']['cType'] == 'event') {				
					$box['tabs']['meta_data']['fields']['publication_date']['hidden'] = true;
					
					if ($values['zenario_ctype_event__when_and_where/specify_time']) {
						$hideTimes = false;
					} else {
						$hideTimes = true;
					}

					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['start_time_hours']['hidden'] = $hideTimes;
					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['start_time_minutes']['hidden'] = $hideTimes;
					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['end_time_hours']['hidden'] = $hideTimes;
					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['end_time_minutes']['hidden'] = $hideTimes;
					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['late_evening_event']['hidden'] = $hideTimes;
				}

				if ($values['zenario_ctype_event__when_and_where/start_date'] && ($values['zenario_ctype_event__when_and_where/start_date'] != $box['tabs']['zenario_ctype_event__when_and_where']['fields']['start_date']['last_value'])) {
					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['end_date']['current_value'] = $values['zenario_ctype_event__when_and_where/start_date'];				
				}

				if ($values['zenario_ctype_event__when_and_where/start_date'] != $box['tabs']['zenario_ctype_event__when_and_where']['fields']['start_date']['last_value']) {
					$box['tabs']['zenario_ctype_event__when_and_where']['fields']['start_date']['last_value'] = $values['zenario_ctype_event__when_and_where/start_date'];
				}
				break;
			case 'plugin_settings':
		        $box['tabs']['first_tab']['fields']['another_event']['hidden'] = !(arrayKey($values,'first_tab/show_details_and_link')=='another_content_item');
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'zenario_content':
				if ($box['key']['cType'] == 'event') {
					if ($saving) {
						if (!$values['zenario_ctype_event__when_and_where/start_date']) {
							$box['tabs']['zenario_ctype_event__when_and_where']['errors']['incomplete_dates'] = adminPhrase("Your Event's Start and End Dates must be defined.");
						}
	
						if ((($values['zenario_ctype_event__when_and_where/start_time_hours'] * 100 + $values['zenario_ctype_event__when_and_where/start_time_minutes']) 
									> ($values['zenario_ctype_event__when_and_where/end_time_hours'] * 100 + $values['zenario_ctype_event__when_and_where/end_time_minutes'])
							) && (!$values['zenario_ctype_event__when_and_where/late_evening_event'])) {
	
							$box['tabs']['zenario_ctype_event__when_and_where']['errors']['incorrect_time'] = adminPhrase('The Event cannot finish earlier than it starts. Please set the "Late evening Event" flag the Event runs past midnight.');
						}							
					}
				}
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_content':
				if ($box['key']['cType'] == 'event') {
					setRow(
							ZENARIO_CTYPE_EVENT_PREFIX . "content_event",
							array(
								"id" => $box['key']['cID'],
								"version" => $box['key']['cVersion'],
								"version" => $box['key']['cVersion'],
								"start_date" => $values['zenario_ctype_event__when_and_where/start_date'],
								"start_time" => (
												(arrayKey($values,'zenario_ctype_event__when_and_where/start_time_hours') 
													&& arrayKey($values,'zenario_ctype_event__when_and_where/start_time_minutes')
														&& arrayKey($values,'zenario_ctype_event__when_and_where/specify_time'))?
												 ($values['zenario_ctype_event__when_and_where/start_time_hours'] . ":" . $values['zenario_ctype_event__when_and_where/start_time_minutes']): 
												 null
												 ),
								"end_date" => $values['zenario_ctype_event__when_and_where/end_date'],
								"end_time" => (
												(arrayKey($values,'zenario_ctype_event__when_and_where/end_time_hours') 
													&& arrayKey($values,'zenario_ctype_event__when_and_where/end_time_minutes') 
														&& arrayKey($values,'zenario_ctype_event__when_and_where/specify_time'))?
												 ($values['zenario_ctype_event__when_and_where/end_time_hours'] . ":" . $values['zenario_ctype_event__when_and_where/end_time_minutes']): 
												 null
											   ),
								"specify_time" => engToBoolean($values['zenario_ctype_event__when_and_where/specify_time']),
								"next_day_finish" => engToBoolean($values['zenario_ctype_event__when_and_where/late_evening_event']) && arrayKey($values,'zenario_ctype_event__when_and_where/specify_time'),
								"location_id" => $values['zenario_ctype_event__when_and_where/location'],
								"url" => $values['zenario_ctype_event__when_and_where/url']
							),
							array(
								"id" => $box['key']['cID'],
								"version" => $box['key']['cVersion']
							)
					);
				}
				break;
		}
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__content/panels/content':
			case 'zenario__content/panels/chained':
			case 'zenario__content/panels/language_equivs':
				//...
				
				break;
		}
	}

	public static function getEventDetails ($id,$version) {
		return getRow(
					ZENARIO_CTYPE_EVENT_PREFIX . "content_event",
					array(
						"id",
						"version",
						"start_date",
						"start_time",
						"end_date",
						"end_time",
						"specify_time",
						"next_day_finish",
						"day_sun_on",
						"day_sun_start_time",
						"day_sun_end_time",
						"day_mon_on",
						"day_mon_start_time",
						"day_mon_end_time",
						"day_tue_on",
						"day_tue_start_time",
						"day_tue_end_time",
						"day_wed_on",
						"day_wed_start_time",
						"day_wed_end_time",
						"day_thu_on",
						"day_thu_start_time",
						"day_thu_end_time",
						"day_fri_on",
						"day_fri_start_time",
						"day_fri_end_time",
						"day_sat_on",
						"day_sat_start_time",
						"day_sat_end_time",
						"location_id",
						"url",
						"stop_dates"
					),
					array(
						"id" => $id,
						"version" => $version
					)
				);
	}	

	public static function eventDraftCreated ($cIDTo, $cIDFrom, $cType, $cVersionTo, $cVersionFrom) {
		if ($cType == 'event' ) {
			$sql = "INSERT INTO " . DB_NAME_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event
					SELECT " . (int) $cIDTo . " AS id,
						" . (int) $cVersionTo . " AS version,
						start_date,
						start_time,
						end_date,
						end_time,
						specify_time,
						next_day_finish,
						day_sun_on,
						day_sun_start_time,
						day_sun_end_time,
						day_mon_on,
						day_mon_start_time,
						day_mon_end_time,
						day_tue_on,
						day_tue_start_time,
						day_tue_end_time,
						day_wed_on,
						day_wed_start_time,
						day_wed_end_time,
						day_thu_on,
						day_thu_start_time,
						day_thu_end_time,
						day_fri_on,
						day_fri_start_time,
						day_fri_end_time,
						day_sat_on,
						day_sat_start_time,
						day_sat_end_time,
						location_id,
						url,
						stop_dates
					FROM " . DB_NAME_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event
					WHERE id = " . (int) $cIDFrom . "
						AND version = " . (int) $cVersionFrom;
			$result = sqlQuery($sql);
		}
	}
	
	public static function eventContentDeleted($cID,$cType,$cVersion) {
		$sql = "DELETE
				FROM " . DB_NAME_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event
				WHERE id = " . (int) $cID . "
					AND version = " . (int) $cVersion;
				
		$result = sqlQuery($sql);
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if (get('refiner__content_type')!='event') {
			if (arrayKey($panel,'columns','zenario_ctype_event__start_date')) {
				unset($panel['columns']['zenario_ctype_event__start_date']);
			}

			if (arrayKey($panel,'columns','zenario_ctype_event__start_time')) {
				unset($panel['columns']['zenario_ctype_event__start_time']);
			}

			if (arrayKey($panel,'columns','zenario_ctype_event__end_date')) {
				unset($panel['columns']['zenario_ctype_event__end_date']);
			}

			if (arrayKey($panel,'columns','zenario_ctype_event__end_time')) {
				unset($panel['columns']['zenario_ctype_event__end_time']);
			}
		}
	}

}
