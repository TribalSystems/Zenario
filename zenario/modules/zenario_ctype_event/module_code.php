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




class zenario_ctype_event extends ze\moduleBaseClass {
	
	public $targetID = false;
	public $targetVersion = false;
	public $targetType = false;
	
	protected $data = [];
	
	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = false, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		return true;
	}
	
	function showSlot() {
		if ($this->setting('show_details_and_link')=='another_content_item') {
			$item = $this->setting('another_event');
			if (count($arr = explode("_", $item)) == 2) {
				$this->targetID = $arr[1];
				$this->targetType = $arr[0];
				if (!$this->targetVersion = ze\content::showableVersion($this->targetID, $this->targetType)){
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
			if ((int)($_SESSION['admin_userid'] ?? false)){
				echo "This plugin will only work when placed on an Event content item, or when configured to point to another Event content item. Please check your plugin settings.";
			}
			return;
		}
		
		$weekdays = [];
		if ($event=$this->getEventDetails($this->targetID,$this->targetVersion)) {

			if ($this->setting('show_title')) {
				$this->data['show_title'] = true;
				$this->data['title'] = htmlspecialchars(ze\content::title($this->targetID, $this->targetType, $this->targetVersion));
				$this->data['title_tags'] = $this->setting('title_tags');
			}
			

			if (($event['start_date'] ?? false) == ($event['end_date'] ?? false)) {
				$this->data['dates_range'] = $this->phrase('[[date]]', ['date'=> ze\date::format($event['start_date'] ?? false, $this->setting('date_format'), false, false)]);
			} else {
				$this->data['dates_range'] = $this->phrase(
					'[[start_date]] to [[end_date]]',
					[
						'start_date'=> ze\date::format($event['start_date'] ?? false, $this->setting('date_format'), false, false),
						'end_date'=> ze\date::format($event['end_date'] ?? false, $this->setting('date_format'), false, false)
					]
				);
			}
			
			if (ze\module::inc('zenario_event_days_and_dates')) {
				foreach (['sun','mon','tue','wed','thu','fri','sat'] as $K => $day) {
					if (($event['day_' . $day . '_on']) && ($event['day_' . $day . '_start_time']) && ($event['day_' . $day . '_start_time']!='00:00:00'))   {
						$weekdays[] = [
							'weekday' => ze\lang::phrase('_WEEKDAY_' . $K ),
							'time' => (
								($event['day_' . $day . '_start_time'] != $event['day_' . $day . '_end_time'] && $event['day_' . $day . '_end_time'] && ($event['day_' . $day . '_end_time']!='00:00:00')) ? 
								$this->phrase(
									'[[start_time]] to [[end_time]]',
									[
										'start_time'=> ze\date::formatTime($event['day_' . $day . '_start_time'],ze::setting('vis_time_format'),''),
										'end_time'=> ze\date::formatTime($event['day_' . $day . '_end_time'],ze::setting('vis_time_format'),'')]
								) : 
								$this->phrase(
									'[[time]]',
									[
										'time'=> ze\date::formatTime($event['day_' . $day . '_start_time'],ze::setting('vis_time_format'),'')
									]
								)
							)
						];
					}
				}
			}

			if ($weekdays){
				$data['Event_On_Weekday_Details'] = $weekdays;
			} else {
				if (!empty($event['start_time']) && (($event['start_time'] ?? false) != '00:00:00')) {
					if (!empty($event['end_time']) && (($event['start_time'] ?? false) != ($event['end_time'] ?? false))){
						$this->data['dates_range'] .= " " . $this->phrase('[[start_time]] to [[end_time]]', ['start_time' => ze\date::formatTime($event['start_time'], ze::setting('vis_time_format'),''),
																									'end_time'=> ze\date::formatTime($event['end_time'], ze::setting('vis_time_format'),'')]);
					} else {
						$this->data['dates_range'] .= " " .  $this->phrase('[[time]]', ['time'=> ze\date::formatTime($event['start_time'], ze::setting('vis_time_format'),'')]);
					}
				}
			}
			
			$stopDates = explode(',',$event['stop_dates']);
			if ($event['stop_dates'] && count($stopDates)) {
				foreach ($stopDates as $K=>$stopDate){
					$stopDates[$K] = ze\date::format($stopDate, $this->setting('date_format'), false, false);
				}
				$this->data['stop_dates'] = implode(', ',$stopDates);
			}
			
			if ($event['url']) {
				$this->data['More_Info_Url'] = true;
				$this->data['event_url'] = htmlspecialchars($event['url']);
			}

			if (
				ze::setting('zenario_ctype_event__location_field') != 'hidden'
				&& $this->setting('show_online_when_event_is_online')
				&& $event['online']
				&& ($onlineText = $this->setting('online_text'))
			) {
				$this->data['Online_Text'] = $onlineText;
			}

			if (
				ze::setting('zenario_ctype_event__location_field') != 'hidden'
				&& ze::setting('zenario_ctype_event__location_text')
				&& $this->setting('show_address')
				&& $event['location']
			) {
				$this->data['Address_Text'] = $event['location'];
			}
			
			$this->data['Event_Details'] = true;
			$this->twigFramework($this->data);
		}
	}	

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'zenario_content':
				if ($box['key']['cType'] == 'event' && ($box['key']['source_cID'] ?? false) && ($box['key']['source_cVersion'] ?? false) ) {
					$eventDetails = $this->getEventDetails($box['key']['source_cID'], $box['key']['source_cVersion']);
					
					$values['zenario_ctype_event__when_and_where/start_date'] = $eventDetails['start_date'];
					
					if ($values['zenario_ctype_event__when_and_where/start_date']) {
						$fields['tabs']['zenario_ctype_event__when_and_where/start_date']['last_value'] = $values['zenario_ctype_event__when_and_where/start_date'];
					}
					
					$values['zenario_ctype_event__when_and_where/end_date'] = $eventDetails['end_date'];
					
					$values['zenario_ctype_event__when_and_where/specify_time'] = $eventDetails['specify_time'];
					$values['zenario_ctype_event__when_and_where/late_evening_event'] = $eventDetails['next_day_finish'];
					
					$startTime = explode(":", $eventDetails['start_time']);
					$endTime = explode(":", $eventDetails['end_time']);
					
					$values['zenario_ctype_event__when_and_where/start_time_hours'] = $startTime[0] ?? false;
					$values['zenario_ctype_event__when_and_where/start_time_minutes'] = $startTime[1] ?? false;

					$values['zenario_ctype_event__when_and_where/end_time_hours'] = $endTime[0] ?? false;
					$values['zenario_ctype_event__when_and_where/end_time_minutes'] = $endTime[1] ?? false;

					$values['zenario_ctype_event__when_and_where/url'] = $eventDetails['url'];
					
					$values['zenario_ctype_event__when_and_where/online'] = $eventDetails['online'];
					$values['zenario_ctype_event__when_and_where/location'] = $eventDetails['location'];
					$values['zenario_ctype_event__when_and_where/location_id'] = $eventDetails['location_id'];

					if ($eventDetails['location'] || $eventDetails['location_id']) {
						$values['zenario_ctype_event__when_and_where/at_location_checkbox'] = true;

						if ($eventDetails['location_id']) {
							$values['zenario_ctype_event__when_and_where/at_physical_location'] = 'location_picker';
						} elseif ($eventDetails['location']) {
							$values['zenario_ctype_event__when_and_where/at_physical_location'] = 'address_text';
						}
					}
				}

				if (!ze\module::isRunning('zenario_location_manager')) {
					$fields['zenario_ctype_event__when_and_where/location_id']['disabled'] = true;
					$fields['zenario_ctype_event__when_and_where/at_physical_location']['values']['location_picker']['disabled'] = true;
				}
				
				if (ze::setting('zenario_ctype_event__location_field') == 'hidden') {
					$fields['zenario_ctype_event__when_and_where/location']['hidden'] =
					$fields['zenario_ctype_event__when_and_where/location_id']['hidden'] =
					$fields['zenario_ctype_event__when_and_where/locations_snippet']['hidden'] =
					$fields['zenario_ctype_event__when_and_where/online']['hidden'] =
					$fields['zenario_ctype_event__when_and_where/at_location_checkbox']['hidden'] =
					$fields['zenario_ctype_event__when_and_where/at_physical_location']['hidden'] = true;
				} else {
					if (!ze::setting('zenario_ctype_event__location_text')) {
						$fields['zenario_ctype_event__when_and_where/location']['disabled'] = true;
						$fields['zenario_ctype_event__when_and_where/at_physical_location']['values']['address_text']['disabled'] = true;

						$siteSettingLink = "<a href='organizer.php#zenario__content/panels/content_types//event~.zenario_content_type_details~tdetails~k{\"id\":\"event\"}' target='_blank'>Content type site settings</a>";
						
						$fields['zenario_ctype_event__when_and_where/at_physical_location']['values']['address_text']['side_note'] = ze\admin::phrase(
							'You can enable this setting in the [[site_setting_link]].',
							['site_setting_link' => $siteSettingLink]
						);
					}
				}
				
				break;
			case 'zenario_content_type_details':
				if ($box['key']['id'] == 'event') {
					
					if (ze\module::isRunning('zenario_location_manager')) {
						$runningOrNotRunning = 'running';
						$values['zenario_ctype_event__location_picker'] = true;
					} else {
						$runningOrNotRunning = 'not running';
						$values['zenario_ctype_event__location_picker'] = false;
					}

					ze\lang::applyMergeFields($fields['zenario_ctype_event__location_picker']['label'], ['running_or_not_running' => $runningOrNotRunning]);

					$moduleLink = "<a href='organizer.php#zenario__modules/panels/modules~-Location%20Manager' target='_blank'>Modules panel</a>";
					
					$fields['zenario_ctype_event__location_picker']['side_note'] = ze\admin::phrase(
						'You can manage the Location Manager module on the [[modules_panel_link]].',
						['modules_panel_link' => $moduleLink]
					);

					$values['zenario_ctype_event__location_field'] = ze::setting('zenario_ctype_event__location_field') != 'hidden';
					$values['zenario_ctype_event__location_text'] = ze::setting('zenario_ctype_event__location_text');
				}
				break;
			case 'plugin_settings':
				$siteSettingLink = "<a href='organizer.php#zenario__content/panels/content_types//event~.zenario_content_type_details~tdetails~k{\"id\":\"event\"}' target='_blank'>Content type site settings</a>";
				$disabledPhrase = ze\admin::phrase(
					'You can enable this setting in the [[site_setting_link]].',
					['site_setting_link' => $siteSettingLink]
				);

				if (ze::setting('zenario_ctype_event__location_field') == 'hidden') {
					$fields['first_tab/show_online_when_event_is_online']['disabled'] =
					$fields['first_tab/online_text']['disabled'] =
					$fields['first_tab/show_address']['disabled'] = true;

					$values['first_tab/show_online_when_event_is_online'] =
					$values['first_tab/show_address'] = false;

					$fields['first_tab/show_online_when_event_is_online']['side_note'] =
					$fields['first_tab/online_text']['side_note'] =
					$fields['first_tab/show_address']['side_note'] = $disabledPhrase;
				} elseif (!ze::setting('zenario_ctype_event__location_text')) {
					$fields['first_tab/show_address']['disabled'] = true;
					$values['first_tab/show_address'] = false;
					$fields['first_tab/show_address']['side_note'] = $disabledPhrase;
				}
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_content':
				if ($box['key']['cType'] == 'event') {				
					$fields['meta_data/release_date']['hidden'] = true;
					
					if ($values['zenario_ctype_event__when_and_where/specify_time']) {
						$hideTimes = false;
					} else {
						$hideTimes = true;
					}

					$fields['zenario_ctype_event__when_and_where/start_time_hours']['hidden'] = $hideTimes;
					$fields['zenario_ctype_event__when_and_where/start_time_minutes']['hidden'] = $hideTimes;
					$fields['zenario_ctype_event__when_and_where/end_time_hours']['hidden'] = $hideTimes;
					$fields['zenario_ctype_event__when_and_where/end_time_minutes']['hidden'] = $hideTimes;
					$fields['zenario_ctype_event__when_and_where/late_evening_event']['hidden'] = $hideTimes;
				}

				if (!$box['key']['id'] && $values['zenario_ctype_event__when_and_where/start_date'] && ($values['zenario_ctype_event__when_and_where/start_date'] != $fields['zenario_ctype_event__when_and_where/start_date']['last_value'])) {
					$fields['zenario_ctype_event__when_and_where/end_date']['current_value'] = $values['zenario_ctype_event__when_and_where/start_date'];				
				}

				if ($values['zenario_ctype_event__when_and_where/start_date'] != $fields['zenario_ctype_event__when_and_where/start_date']['last_value']) {
					$fields['zenario_ctype_event__when_and_where/start_date']['last_value'] = $values['zenario_ctype_event__when_and_where/start_date'];
				}
				break;
			case 'plugin_settings':
		        $fields['first_tab/another_event']['hidden'] = !(($values['first_tab/show_details_and_link'] ?? false) =='another_content_item');
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'zenario_content':
				if ($box['key']['cType'] == 'event') {
					if ($saving) {
						if (!$values['zenario_ctype_event__when_and_where/start_date']) {
							$box['tabs']['zenario_ctype_event__when_and_where']['errors']['incomplete_dates'] = ze\admin::phrase("Please enter start and end dates for the event. They can be the same for a single-day event.");
						}

						if ($values['zenario_ctype_event__when_and_where/start_date']) {
							$start_time_hours = $values['zenario_ctype_event__when_and_where/start_time_hours'] ?: '00';
							$start_time_minutes = $values['zenario_ctype_event__when_and_where/start_time_minutes'] ?: '00';
							$end_time_hours = $values['zenario_ctype_event__when_and_where/end_time_hours'] ?: '00';
							$end_time_minutes = $values['zenario_ctype_event__when_and_where/end_time_minutes'] ?: '00';

							if ($values['zenario_ctype_event__when_and_where/start_date'] && !$values['zenario_ctype_event__when_and_where/end_date']) {
								$box['tabs']['zenario_ctype_event__when_and_where']['errors']['incomplete_dates'] = ze\admin::phrase("Please enter start and end dates for the event. They can be the same for a single-day event.");
							} elseif ($values['zenario_ctype_event__when_and_where/end_date'] && $values['zenario_ctype_event__when_and_where/end_date'] < $values['zenario_ctype_event__when_and_where/start_date']) {
								$fields['zenario_ctype_event__when_and_where/end_date']['error'] = ze\admin::phrase('The end date cannot be earlier than the start date.');
							}
						} else {
							$start_time_hours =
							$start_time_minutes =
							$end_time_hours =
							$end_time_minutes = '00';
						}
	
						if ((($start_time_hours * 100 + $start_time_minutes) 
									> ($end_time_hours * 100 + $end_time_minutes)
							) && (!$values['zenario_ctype_event__when_and_where/late_evening_event'])) {
	
							$box['tabs']['zenario_ctype_event__when_and_where']['errors']['incorrect_time'] = ze\admin::phrase('The event cannot finish earlier than it starts. Please set the "Late evening Event" flag the Event runs past midnight.');
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
					$url = $values['zenario_ctype_event__when_and_where/url'];
					if ($url == 'http://') {
						$url = null;
					}
					
					if ($values['zenario_ctype_event__when_and_where/start_date']) {
						$start_time_hours = $values['zenario_ctype_event__when_and_where/start_time_hours'] ?: '00';
						$start_time_minutes = $values['zenario_ctype_event__when_and_where/start_time_minutes'] ?: '00';
						$end_time_hours = $values['zenario_ctype_event__when_and_where/end_time_hours'] ?: '00';
						$end_time_minutes = $values['zenario_ctype_event__when_and_where/end_time_minutes'] ?: '00';
					} else {
						$start_time_hours =
						$start_time_minutes =
						$end_time_hours =
						$end_time_minutes = '00';
					}
					
					$details = [
						"id" => $box['key']['cID'],
						"version" => $box['key']['cVersion'],
						"version" => $box['key']['cVersion'],
						"start_date" => $values['zenario_ctype_event__when_and_where/start_date'],
						"start_time" => (
										(($start_time_hours ?? false) 
											&& ($start_time_minutes ?? false)
												&& ($values['zenario_ctype_event__when_and_where/specify_time'] ?? false))?
										 ($start_time_hours . ":" . $start_time_minutes): 
										 null
										 ),
						"end_date" => $values['zenario_ctype_event__when_and_where/end_date'],
						"end_time" => (
										(($end_time_hours ?? false) 
											&& ($end_time_minutes ?? false) 
												&& ($values['zenario_ctype_event__when_and_where/specify_time'] ?? false))?
										 ($end_time_hours . ":" . $end_time_minutes): 
										 null
									   ),
						"specify_time" => ze\ring::engToBoolean($values['zenario_ctype_event__when_and_where/specify_time']),
						"next_day_finish" => ze\ring::engToBoolean($values['zenario_ctype_event__when_and_where/late_evening_event']) && ($values['zenario_ctype_event__when_and_where/specify_time'] ?? false),
						"url" => $url
					];

					$details['location'] = $details['location_id'] = '';

					if (ze::setting('zenario_ctype_event__location_field') != 'hidden' && $values['zenario_ctype_event__when_and_where/at_location_checkbox']) {
						if (ze::setting('zenario_ctype_event__location_text') && $values['zenario_ctype_event__when_and_where/at_physical_location'] == 'address_text') {
							$details['location'] = $values['zenario_ctype_event__when_and_where/location'];
						} elseif ($values['zenario_ctype_event__when_and_where/at_physical_location'] == 'location_picker') {
							$details['location_id'] = $values['zenario_ctype_event__when_and_where/location_id'];
						}

						$details['online'] = $values['zenario_ctype_event__when_and_where/online'];
					}
					
					ze\row::set(
						ZENARIO_CTYPE_EVENT_PREFIX . "content_event", $details, ["id" => $box['key']['cID'], "version" => $box['key']['cVersion']]);
				}
				break;
			case 'zenario_content_type_details':
				if ($box['key']['id'] == 'event') {
					ze\site::setSetting('zenario_ctype_event__location_field', $values['zenario_ctype_event__location_field']? 'optional' : 'hidden');
					ze\site::setSetting('zenario_ctype_event__location_text', $values['zenario_ctype_event__location_text']);
				}
				break;
		}
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__content/panels/content':
			case 'zenario__content/panels/chained':
				//...
				
				break;
		}
	}

	public static function getEventDetails ($id,$version) {
		return ze\row::get(
			ZENARIO_CTYPE_EVENT_PREFIX . "content_event",
			[
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
				"location",
				"url",
				"stop_dates",
				'online'
			],
			[
				"id" => $id,
				"version" => $version
			]
		);
	}	

	public static function eventDraftCreated($cIDTo, $cIDFrom, $cTypeTo, $cVersionTo, $cVersionFrom, $cTypeFrom) {
		if ($cTypeFrom == 'event' && $cTypeTo == 'event') {
			$sql = "
				INSERT INTO " . DB_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event
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
					location,
					url,
					stop_dates,
					online
				FROM " . DB_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event
				WHERE id = " . (int) $cIDFrom . "
					AND version = " . (int) $cVersionFrom;
			$result = ze\sql::update($sql);
		}
	}
	
	public static function eventContentDeleted($cID,$cType,$cVersion) {
		$sql = "
			DELETE FROM " . DB_PREFIX . ZENARIO_CTYPE_EVENT_PREFIX . "content_event
			WHERE id = " . (int) $cID . "
				AND version = " . (int) $cVersion;
				
		$result = ze\sql::update($sql);
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if (($_GET['refiner__content_type'] ?? false)!='event') {
			if ($panel['columns']['zenario_ctype_event__start_date'] ?? false) {
				unset($panel['columns']['zenario_ctype_event__start_date']);
			}

			if ($panel['columns']['zenario_ctype_event__start_time'] ?? false) {
				unset($panel['columns']['zenario_ctype_event__start_time']);
			}

			if ($panel['columns']['zenario_ctype_event__end_date'] ?? false) {
				unset($panel['columns']['zenario_ctype_event__end_date']);
			}

			if ($panel['columns']['zenario_ctype_event__end_time'] ?? false) {
				unset($panel['columns']['zenario_ctype_event__end_time']);
			}
		}
	}
}