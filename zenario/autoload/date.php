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


namespace ze;

class date {


	private static $timeNow = null;
	
	const nowFromTwig = true;
	//Formerly "NOW()"
	//Formerly "now()"
	public static function now($cache = false) {
		
		if ($cache && self::$timeNow !== null) {
			return self::$timeNow;
		}
		
		return self::$timeNow = \ze\sql::fetchValue('SELECT NOW()');
	}

	const ymdFromTwig = true;
	//Formerly "dateNow()"
	public static function ymd() {
		$row = \ze\sql::fetchRow('SELECT DATE(NOW())');
		return $row[0];
	}


	//Formerly "formatDateNicely()"
	public static function format($date, $format_type = false, $languageId = false, $time_format = '', $rss = false, $cli = false, $admin = false) {
		
		if (empty($date)) {
			return '';
		}
	
		//Use $languageId === true as a shortcut to the site default language
		//Otherwise if $languageId is not set, try to get language from session, or the site default if that is not set
		if ($languageId === true) {
			$languageId = \ze::$defaultLang;
	
		} elseif (!$languageId) {
			$languageId = \ze::$visLang ?? $_SESSION['user_lang'] ?? \ze::$defaultLang;
		}
	
		if ($time_format === true) {
			$time_format = ' %H:%i';
		}
	
		if ($rss) {
			$format_type = '%a, %d %b %Y';
			$time_format = ' %H:%i:%s ';
	
		} elseif (!$format_type || $format_type == 'vis_date_format_long' || $format_type == '_LONG') {
			$format_type = \ze::setting('vis_date_format_long');
	
		} elseif ($format_type == 'vis_date_format_med' || $format_type == '_MEDIUM') {
			$format_type = \ze::setting('vis_date_format_med');
	
		} elseif ($format_type == 'vis_date_format_short' || $format_type == '_SHORT') {
			$format_type = \ze::setting('vis_date_format_short');
		}
	
		//If this language is not English, do not show "1st/2nd/3rd
		if ($languageId != 'en' && substr($languageId, 0, 3) != 'en-') {
			$format_type = str_replace('%D', '%e', $format_type);
		}
	
		if (is_numeric($date)) {
			$date = \ze\user::convertToUsersTimeZone($date);
		}
		if (is_object($date)) {
			$sql = "SELECT DATE_FORMAT('". \ze\escape::sql($date->format('Y-m-d H:i:s')). "', '". \ze\escape::sql($format_type. $time_format). "')";
		} else {
			$sql = "SELECT DATE_FORMAT('". \ze\escape::sql($date). "', '". \ze\escape::sql($format_type. $time_format). "')";
		}
	
		$formattedDate = \ze\sql::fetchRow($sql);
		$formattedDate = $formattedDate[0];
	
		$returnDate = $formattedDate;
		if ($rss) {
			if ($time_format) {
				$sql = "SELECT TIME_FORMAT(NOW() - UTC_TIMESTAMP(), '%H%i') ";
				$result = \ze\sql::select($sql);
				list($timezone) = \ze\sql::fetchRow($result);
			
				if (substr($timezone, 0, 1) != '-') {
					$timezone = '+'. $timezone;
				}
			
				$returnDate .= $timezone;
			}
		
		} elseif ($admin) {
			\ze\lang::applyMergeFields($returnDate, \ze\admin::$englishDatePhrases);
		} else {
			\ze\lang::replacePhraseCodesInString($returnDate, 'zenario_common_features', $languageId, 2, $cli);
		}
	
		return $returnDate;
	}

	//Formerly "formatDateTimeNicely()"
	public static function formatDateTime($date, $format_type = false, $languageId = false, $rss = false, $cli = false, $admin = false) {
		return \ze\date::format($date, $format_type, $languageId, true, $rss, $cli, $admin);
	}

	//Formerly "formatTimeNicely()"
	public static function formatTime($time, $format_type) {
	
		if (is_numeric($time)) {
			$time = \ze\user::convertToUsersTimeZone($time);
		}
		if (is_object($time)) {
			$sql = "SELECT TIME_FORMAT('". \ze\escape::sql($time->format('Y-m-d H:i:s')). "', '". \ze\escape::sql($format_type). "')";
		} else {
			$sql = "SELECT TIME_FORMAT('". \ze\escape::sql($time). "', '". \ze\escape::sql($format_type). "')";
		}
	
		$row = \ze\sql::fetchRow($sql);
		return $row[0];
	}
	
	//Attempt to get the name of the timezone that a date is in.
	const formatTimeZoneFromTwig = true;
	public static function formatTimeZone($date = null) {
		
		if (is_null($date)) {
			$date = \ze\user::convertToUsersTimeZone(new \DateTime());
		}
		
		$text = $date->format('T');
		
		//If we couldn't a named timezone, call formatTimeZoneOffset() instead as a fallback.
		if ('' == str_replace(['+', '-', ':', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], '', $text)) {
			return \ze\date::formatTimeZoneOffset($date);
		} else {
			return $text;
		}
	}
	
	//A deprecated aliases
	public static function formattedTimeZone($date = null) {
		return \ze\date::formatTimeZone($date);
	}

	//Get the timezone offset from UTC that a date is in.
	const formatTimeZoneOffsetFromTwig = true;
	public static function formatTimeZoneOffset($date = null) {
		
		if (is_null($date)) {
			$date = \ze\user::convertToUsersTimeZone(new \DateTime());
		}
		
		return 'UTC '. $date->format('P');
	}

	const formattedServerTimeZoneFromTwig = true;
	public static function formattedServerTimeZone() {
		return \ze\date::formatTimeZone(new \DateTime());
	}
	
	
	//This function gets the timezone as set on the database server
	private static $sqlTZ = null;
	public static function sqlTimezone() {
		
		if (is_null(self::$sqlTZ)) {
			if ($tdiff = \ze\sql::fetchValue('SELECT TIMEDIFF(NOW(), UTC_TIMESTAMP)')) {
				$tdiff = substr($tdiff, 0, -3);
				
				if ($tdiff[0] != '-'
				 && $tdiff[0] != '+') {
					$tdiff = '+'. $tdiff;
				}
			
			} else {
				$tdiff = '+00:00';
			}
			
			self::$sqlTZ = $tdiff;
		}
		
		return self::$sqlTZ;
	}
	
	//Create a new date object from a integer or a string
	public static function new($time) {
		
		//UNIX timestamps (in seconds, not in ms)
		if (is_numeric($time)) {
			return new \DateTime('@'. (int) $time);
		
		} elseif (is_string($time)) {
			//Dates from MySQL, in server time
			if (($time[4] ?? '') === '-'
			 && ($time[7] ?? '') === '-'
			 && strrpos($time, '-', 8) === false
			 && strrpos($time, '+', 8) === false) {
				return new \DateTime($time. self::sqlTimezone());
			
			//Generic fallback to catch anything else!
			} else {
				$time = new \DateTime($time);
			}
		}
		
		//Catch the case where this is already a date object (i.e. this function won't cause an error if someone accidentally calls it twice!)
		return $time;
	}



	const relativeFromTwig = true;
	//Formerly "getRelativeDate()"
	public static function relative($timestamp, $maxPeriod = "day", $addFullTime = true, $format_type = 'vis_date_format_med', $languageId = false, $time_format = true, $cli = false, $showDateTime = false, $displayAdminPhrase = false) {
		if (is_object($timestamp)) {
			$time = $timestamp;
			$timestamp = (int) $time->format('U');
		
		} else {
			$time = \ze\user::convertToUsersTimeZone($timestamp);
			if (!is_numeric($timestamp)) {
				$timestamp = (int) $time->format('U');
			}
		}
	
		$etime = time() - (int) $timestamp;
		if ($etime < 1) {
			if ($displayAdminPhrase) {
				return \ze\admin::phrase('[[time_elapsed]] secs ago', ['time_elapsed' => 0]);
			} else {
				return \ze\lang::phrase('[[time_elapsed]] secs ago', ['time_elapsed' => 0], 'zenario_common_features', false, 1, $cli);
			}
		}
	
		$units = ['sec', 'min', 'hour', 'day', 'month', 'year'];
		$uPlurals = ['secs', 'mins', 'hours', 'days', 'months', 'years'];
		$uValues = [1, 60, 3600, 86400];
		$maxI = array_search($maxPeriod, $units);
	
		if ($maxI) {
			if ($maxI > 3) {
				$uValues[] = 86400 * (int) date('t');
				if ($maxI > 4) {
					$uValues[] = 86400 * (365 + (int) date('L'));
				}
			}
		
			for ($i = 1; $i <= $maxI; ++$i) {
				if ($etime < $uValues[$i]) {
					$r = round($etime / $uValues[--$i]);
				
					if ($r > 1) {
						if ($displayAdminPhrase) {
							$relativeDate = \ze\admin::phrase('[[time_elapsed]] ' . $uPlurals[$i] . ' ago', ['time_elapsed' => $r]);
						} else {
							$relativeDate = \ze\lang::phrase('[[time_elapsed]] ' . $uPlurals[$i] . ' ago', ['time_elapsed' => $r], 'zenario_common_features', false, 1, $cli);
						}
					} else {
						
						if ($displayAdminPhrase) {
							$relativeDate = \ze\admin::phrase('[[time_elapsed]] ' . $units[$i] . ' ago', ['time_elapsed' => $r]);
						} else {
							$relativeDate = \ze\lang::phrase('[[time_elapsed]] ' . $units[$i] . ' ago', ['time_elapsed' => $r], 'zenario_common_features', false, 1, $cli);
						}
					}
			
					if ($addFullTime) {
						if (is_string($addFullTime)) {
							return $relativeDate. ' ('. \ze\date::format($time, $addFullTime, $languageId, $time_format, false, $cli, $displayAdminPhrase). ')';
						} else {
							return $relativeDate. ' ('. \ze\date::format($time, $format_type, $languageId, $time_format, false, $cli, $displayAdminPhrase). ')';
						}
					
					} else {
						return $relativeDate;
					}
				}
			}
		}
	
		if(!$showDateTime){
			$time_format = '';
		}
	
		return \ze\date::format($time, $format_type, $languageId, $time_format, false, $cli, $displayAdminPhrase);

	}
	//Added new relative date function for CSLs
	public static function simpleFormatRelativeDate($timestamp) {
		$originalTime = $timestamp;
		
		if (is_object($timestamp)) {
			$time = $timestamp;
			$timestamp = (int) $time->format('U');
		
		} else {
			$time = \ze\user::convertToUsersTimeZone($timestamp);
			if (!is_numeric($timestamp)) {
				$timestamp = (int) $time->format('U');
			}
		}
		
	
		$etime = time() - (int) $timestamp;
		$dateDifference = floor($etime/(60*60*24));
		if($dateDifference==0){
			
			$relativeDate = 'Today';
			
		} else if ($dateDifference > 1 && $dateDifference < 29){
			
			$relativeDate = $dateDifference.' days ago';
			
		} else if ($dateDifference > 28){
			
			$relativeDate = \ze\date::format($originalTime,'_MEDIUM');
			
		} else if($dateDifference > 0){
			 
			$relativeDate = 'Yesterday';
			
		} else if($dateDifference < -1){
			
			$relativeDate = \ze\date::format($originalTime,'_MEDIUM');
			
		} else {
			
			$relativeDate = \ze\date::format($originalTime,'_MEDIUM');
		}  
	
		return $relativeDate;

	}
}