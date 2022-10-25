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

class zenario_timezones extends ze\moduleBaseClass {
	
	//N.b. this plugin works via an AJAX request to not interfere with page caching
	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		return true;
	}
	
	function showSlot() {
		$this->twigFramework();
		
		$this->callScript('zenario_timezones', 'init', $this->containerId, ze::$vars['locationId'] ?? 0, ze::$vars['dataPoolId1'] ?? 0);
	}
	
	function handleAJAX() {
		$microtime = (int) microtime(true);
		$time = ze\user::convertToUsersTimeZone($microtime);
		echo $time->format('H~i~s~T');
		exit;
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		switch($path) {
			case 'site_settings':
				if ($settingGroup == 'date_and_time') {
					$fields['timezone_settings/timezone_dataset_field']['values'] = ze\datasetAdm::listCustomFields('users', $flat = false, ['centralised_radios', 'centralised_select'], $customOnly = true, $useOptGroups = true, $hideEmptyOptGroupParents = true);
					$fields['timezone_settings/default_timezone']['values'] = static::getTimezonesLOV();
				}
				break;
		}
	}

	
	public static function getTime($timezone) {
		$date = new DateTime('now', new DateTimeZone($timezone));
		return $date->format('Y-m-d H:i:s');
	}
	
	// Converts a UTC date/timestamp into the users timezone
	//function removed here
	
	public static function getUserTimezone($user_id){
		$field = ze\row::get('custom_dataset_fields',['db_column'], ["values_source"=>"zenario_timezones::getTimezones"]);
		if($field && $field["db_column"]){
			$supervisor_custom_data = ze\row::get('users_custom_data', [$field["db_column"]], ['user_id' => $user_id]);
			$current_timezone = $supervisor_custom_data[$field["db_column"]];
			return $current_timezone ? $current_timezone : false;
		}else{
			return false;
		}
	}
	
	public static function getUserTimezoneOffsetFromGMTInSeconds($userId) {
		$offset = 0;
		$dbColumn = false;
		if (!ze::setting('zenario_timezones__timezone_dataset_field')
			|| !($dbColumn = ze\dataset::fieldDBColumn(ze::setting('zenario_timezones__timezone_dataset_field')))
			|| !($timezone = ze\row::get('users_custom_data', $dbColumn, ['user_id' => $userId]))
		) {
			$timezone = ze::setting('zenario_timezones__default_timezone');
		}
		if ($timezone) {
			$dateTimezone = new DateTimeZone($timezone);
			$dateTime = new DateTime('now', $dateTimezone);
			$offset = $dateTimezone->getOffset($dateTime);
		}
		return $offset;
	}
	
	public static $timezoneOffsets = [];
	
	
	public static function getTimezonesLOV() {
		$list = [];
		$timezones = static::getTimezones(ze\dataset::LIST_MODE_LIST);
		
		$ord = 0;
		foreach ($timezones as $key => $label) {
			$list[$key] = [
				'ord' => ++$ord,
				'label' => $label
			];
		}
		
		return $list;
	}
	
	
	public static function getTimezones($mode, $value = false) {
		switch ($mode) {
			case ze\dataset::LIST_MODE_INFO:
				return ['can_filter' => false];
			case ze\dataset::LIST_MODE_VALUE:
				return ze\ray::value(self::$timezones, $value);
			case ze\dataset::LIST_MODE_LIST:
				// Get timezone offset from 0 and save against timezone code in array
				$timezones = self::$timezones;
				foreach ($timezones as $timezone => &$city) {
					$dateTimeZone = new DateTimeZone($timezone);
					$dateTime = new DateTime('now', $dateTimeZone);
					$offset = $dateTimeZone->getOffset($dateTime) / 3600;
					self::$timezoneOffsets[$timezone] = $offset;
					$offset = number_format($offset, 2);
					$offset = ($offset > 0 ? '+' . $offset : $offset);
					$city = '(UTC '. $offset.') '.$city;
				}
				// Sort original array by offsets in offset array
				uksort($timezones, "self::sortTimezones");
				// Return sorted array
				return $timezones;
		}
	}
	
	public static function sortTimezones($a, $b) {
		
		if (self::$timezoneOffsets[$a] == self::$timezoneOffsets[$b]) {
			return self::$timezones[$a] <=> self::$timezones[$b];
		} else {
			return self::$timezoneOffsets[$a] <=> self::$timezoneOffsets[$b];
		}
	}
	
	public static $timezones = 
		[
			'Pacific/Midway'       => "Midway Island",
			'US/Hawaii'            => "Hawaii",
			'US/Alaska'            => "Alaska",
			'US/Pacific'           => "Pacific Time (US & Canada)",
			'America/Tijuana'      => "Tijuana",
			'US/Mountain'          => "Mountain Time (US & Canada)",
			'America/Chihuahua'    => "Chihuahua",
			'America/Mazatlan'     => "Mazatlan",
			'America/Mexico_City'  => "Mexico City",
			'America/Monterrey'    => "Monterrey",
			'Canada/Saskatchewan'  => "Saskatchewan",
			'US/Central'           => "Central Time (US & Canada)",
			'US/Eastern'           => "Eastern Time (US & Canada)",
			'America/Bogota'       => "Bogota",
			'America/Lima'         => "Lima",
			'America/Caracas'      => "Caracas",
			'Canada/Atlantic'      => "Atlantic Time (Canada)",
			'America/La_Paz'       => "La Paz",
			'America/Santiago'     => "Santiago",
			'Canada/Newfoundland'  => "Newfoundland",
			'America/Buenos_Aires' => "Buenos Aires",
			'Atlantic/Stanley'     => "Stanley",
			'Atlantic/Azores'      => "Azores",
			'Atlantic/Cape_Verde'  => "Cape Verde Is.",
			'Africa/Casablanca'    => "Casablanca",
			'Europe/Dublin'        => "Dublin",
			'Europe/Lisbon'        => "Lisbon",
			'Europe/London'        => "London",
			'Africa/Monrovia'      => "Monrovia",
			'Europe/Amsterdam'     => "Amsterdam",
			'Europe/Belgrade'      => "Belgrade",
			'Europe/Berlin'        => "Berlin",
			'Europe/Bratislava'    => "Bratislava",
			'Europe/Brussels'      => "Brussels",
			'Europe/Budapest'      => "Budapest",
			'Europe/Copenhagen'    => "Copenhagen",
			'Europe/Ljubljana'     => "Ljubljana",
			'Europe/Madrid'        => "Madrid",
			'Europe/Paris'         => "Paris",
			'Europe/Prague'        => "Prague",
			'Europe/Rome'          => "Rome",
			'Europe/Sarajevo'      => "Sarajevo",
			'Europe/Skopje'        => "Skopje",
			'Europe/Stockholm'     => "Stockholm",
			'Europe/Vienna'        => "Vienna",
			'Europe/Warsaw'        => "Warsaw",
			'Europe/Zagreb'        => "Zagreb",
			'Europe/Athens'        => "Athens",
			'Europe/Bucharest'     => "Bucharest",
			'Africa/Cairo'         => "Cairo",
			'Africa/Harare'        => "Harare",
			'Europe/Helsinki'      => "Helsinki",
			'Europe/Istanbul'      => "Istanbul",
			'Asia/Jerusalem'       => "Jerusalem",
			'Europe/Kiev'          => "Kyiv",
			'Europe/Minsk'         => "Minsk",
			'Europe/Riga'          => "Riga",
			'Europe/Sofia'         => "Sofia",
			'Europe/Tallinn'       => "Tallinn",
			'Europe/Vilnius'       => "Vilnius",
			'Asia/Baghdad'         => "Baghdad",
			'Asia/Kuwait'          => "Kuwait",
			'Africa/Nairobi'       => "Nairobi",
			'Asia/Riyadh'          => "Riyadh",
			'Asia/Tehran'          => "Tehran",
			'Europe/Moscow'        => "Moscow",
			'Asia/Baku'            => "Baku",
			'Europe/Volgograd'     => "Volgograd",
			'Asia/Muscat'          => "Muscat",
			'Asia/Tbilisi'         => "Tbilisi",
			'Asia/Yerevan'         => "Yerevan",
			'Asia/Kabul'           => "Kabul",
			'Asia/Karachi'         => "Karachi",
			'Asia/Tashkent'        => "Tashkent",
			'Asia/Kolkata'         => "Kolkata",
			'Asia/Kathmandu'       => "Kathmandu",
			'Asia/Yekaterinburg'   => "Ekaterinburg",
			'Asia/Almaty'          => "Almaty",
			'Asia/Dhaka'           => "Dhaka",
			'Asia/Novosibirsk'     => "Novosibirsk",
			'Asia/Bangkok'         => "Bangkok",
			'Asia/Jakarta'         => "Jakarta",
			'Asia/Krasnoyarsk'     => "Krasnoyarsk",
			'Asia/Chongqing'       => "Beijing",
			'Asia/Hong_Kong'       => "Hong Kong",
			'Asia/Kuala_Lumpur'    => "Kuala Lumpur",
			'Australia/Perth'      => "Perth",
			'Asia/Singapore'       => "Singapore",
			'Asia/Taipei'          => "Taipei",
			'Asia/Ulaanbaatar'     => "Ulaan Bataar",
			'Asia/Urumqi'          => "Urumqi",
			'Asia/Irkutsk'         => "Irkutsk",
			'Asia/Seoul'           => "Seoul",
			'Asia/Tokyo'           => "Tokyo",
			'Australia/Adelaide'   => "Adelaide",
			'Australia/Darwin'     => "Darwin",
			'Asia/Yakutsk'         => "Yakutsk",
			'Australia/Brisbane'   => "Brisbane",
			'Australia/Canberra'   => "Canberra",
			'Pacific/Guam'         => "Guam",
			'Australia/Hobart'     => "Hobart",
			'Australia/Melbourne'  => "Melbourne",
			'Pacific/Port_Moresby' => "Port Moresby",
			'Australia/Sydney'     => "Sydney",
			'Asia/Vladivostok'     => "Vladivostok",
			'Asia/Magadan'         => "Magadan",
			'Pacific/Auckland'     => "Auckland",
			'Pacific/Fiji'         => "Fiji"
		];
}