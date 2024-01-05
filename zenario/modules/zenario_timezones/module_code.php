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
		$time = ze\date::convertToUsersTimeZone($microtime);
		echo $time->format('H~i~s~T');
		exit;
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		switch($path) {
			case 'site_settings':
				if ($settingGroup == 'date_and_time') {
					$fields['timezone_settings/timezone_dataset_field']['values'] = ze\datasetAdm::listCustomFields('users', $flat = false, ['centralised_radios', 'centralised_select'], $customOnly = true, $useOptGroups = true, $hideEmptyOptGroupParents = true);
					$fields['timezone_settings/default_timezone']['values'] = ze\dataset::getTimezonesLOV();
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
	
	
	
	
	public static function getTimezonesLOV() {
		return ze\dataset::getTimezonesLOV();
	}
	
	public static function getTimezones($mode, $value = false) {
		return ze\dataset::getTimezones($mode, $value);
	}
}