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

cms_core::$whitelist[] = 'canSetCookie';
function canSetCookie() {
	return setting('cookie_require_consent') != 'explicit' || !empty($_COOKIE['cookies_accepted']) || checkPriv();
}

function hideCookieConsent() {
	if (cms_core::$cookieConsent != 'require') {
		cms_core::$cookieConsent = 'hide';
	}
}

cms_core::$whitelist[] = 'inc';
//	function inc($moduleClass) {}

//	function isError($object) {}

//Attempt to get a special page
//We should never show unpublished pages to Visitors, and never return a Special Page in the wrong language if $languageMustMatch was set
//Otherwise return a $cID and $cType as best we can
function langSpecialPage($pageType, &$cID, &$cType, $preferredLanguageId = false, $languageMustMatch = false, $skipPermsCheck = false) {
	//Assume that we'll want the special page in the language that the Visitor is currently viewing, if a language is not specified
	if ($preferredLanguageId === false) {
		$preferredLanguageId = ifNull(session('user_lang'), setting('default_language'));
	}
	
	//Convert the requested language to the format used in the special pages array
	if ($preferredLanguageId == setting('default_language')) {
		$preferredLanguageId = '';
	} else {
		$preferredLanguageId = '`'. $preferredLanguageId;
	}
	
	//Try to get the Special Page in the language that we've requested
	if (isset(cms_core::$specialPages[$pageType. $preferredLanguageId])) {
		if (getCIDAndCTypeFromTagId($cID, $cType, cms_core::$specialPages[$pageType. $preferredLanguageId])) {
			if ($skipPermsCheck || checkPerm($cID, $cType)) {
				return true;
			}
		}
	}
	
	//Otherwise try to fall back to the page for the default language
	if ($preferredLanguageId && !$languageMustMatch && isset(cms_core::$specialPages[$pageType])) {
		if (getCIDAndCTypeFromTagId($cID, $cType, cms_core::$specialPages[$pageType])) {
			if ($skipPermsCheck || checkPerm($cID, $cType)) {
				return true;
			}
		}
	}
	
	$cID = $cType = false;
	return false;
}

cms_core::$whitelist[] = 'now';
function now() {
	$row = sqlFetchRow("SELECT NOW()");
	return $row[0];
}

cms_core::$whitelist[] = 'dateNow';
function dateNow() {
	$row = sqlFetchRow("SELECT DATE(NOW())");
	return $row[0];
}


//Given a MySQL timestamp, a unix timestamp, or a PHP date object, return a PHP date object in the current user's timezone
function convertToUserTimezone($time, $specificTimeZone = false) {
	
	//Accept either dates in UTC/GMT, or UNIX timestamps (in seconds, not in ms)
	//Also accept a PHP date object (i.e. this function won't cause an error if someone accidentally calls it twice!)
	if (is_numeric($time)) {
		$time = new DateTime('@'. (int) $time);
	
	} elseif (is_string($time)) {
		$time = new DateTime($time);
	}
	
	if ($specificTimeZone) {
		$time->setTimeZone(new DateTimeZone($specificTimeZone));
	} else {
		//Get the user's timezone, if not already checked
		if (cms_core::$timezone === null) {
			if (empty($_SESSION['extranetUserID'])
			 || !($timezoneFieldId = setting('zenario_timezones__timezone_dataset_field'))
			 || !($timezoneFieldCol = getRow('custom_dataset_fields', 'db_column', $timezoneFieldId))
			 || !(cms_core::$timezone = getRow('users_custom_data', $timezoneFieldCol, $_SESSION['extranetUserID']))) {
				cms_core::$timezone = false;
			}
		}
		if (cms_core::$timezone) {
			$time->setTimeZone(new DateTimeZone(cms_core::$timezone));
		}
	}
	
	return $time;
}



cms_core::$whitelist[] = 'getRelativeDate';
function getRelativeDate($timestamp, $maxPeriod = "day", $addFullTime = false, $format_type = false, $languageId = false, $time_format = true) {
	
	$time = convertToUserTimezone($timestamp);
	if (!is_numeric($timestamp)) {
		$timestamp = $time->getTimestamp();
	}
	
	$etime = time() - (int) $timestamp;
	if ($etime < 1) {
		return phrase('[[time_elapsed]] secs ago', array('time_elapsed' => 0), 'zenario_common_features');
	}
	
	$units = array('sec', 'min', 'hour', 'day', 'month', 'year');
	$uPlurals = array('secs', 'mins', 'hours', 'days', 'months', 'years');
	$uValues = array(1, 60, 3600, 86400);
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
					$relativeDate = phrase('[[time_elapsed]] ' . $uPlurals[$i] . ' ago', array('time_elapsed' => $r), 'zenario_common_features');
				} else {
					$relativeDate = phrase('[[time_elapsed]] ' . $units[$i] . ' ago', array('time_elapsed' => $r), 'zenario_common_features');
				}
			
				if ($addFullTime) {
					if (is_string($addFullTime)) {
						return $relativeDate. ' ('. formatDateNicely($time, $addFullTime, $languageId, $time_format). ')';
					} else {
						return $relativeDate. ' ('. formatDateNicely($time, $format_type, $languageId, $time_format). ')';
					}
					
				} else {
					return $relativeDate;
				}
			}
		}
	}
	
	return formatDateNicely($time, $format_type, $languageId);
}



function addSqlDateTimeByPeriodAndReturnStartEnd($sql_start_date, $by_period) {
	if(strpos($sql_start_date, '23:59:59')) {
		$sql_start_date = strtotime('+1 second', strtotime($sql_start_date));
	} else {
		$sql_start_date = strtotime($sql_start_date);
	}
	$sql_end_date = strtotime($by_period . ' -1 second', $sql_start_date);

	$sql_start_date = date('Y-m-d H:i:s', $sql_start_date);
	$sql_end_date = date('Y-m-d H:i:s', $sql_end_date);

	//echo $sql_start_date, " ", $sql_end_date, "\n";

	return array($sql_start_date, $sql_end_date);
}

function requireCookieConsent() {
	cms_core::$cookieConsent = 'require';
}

function sendEmail(
	$subject, $body, $addressTo, &$addressToOverriddenBy,
	$nameTo = false, $addressFrom = false, $nameFrom = false, 
	$attachments = array(), $attachmentFilenameMappings = array(),
	$precedence = 'bulk', $isHTML = true, $exceptions = false,
	$addressReplyTo = false, $nameReplyTo = false, $warningEmailCode = false,
	$ccs = '', $bccs = ''
) {
	
	// If this is a warning email only send it as oftern as the site setting "warning_email_frequency" allows
	if (setting('warning_email_frequency') && (setting('warning_email_frequency') != 'no_limit') && $warningEmailCode) {
		// If no record is set create one
		if (!checkRowExists('last_sent_warning_emails', array('warning_code' => $warningEmailCode))) {
			insertRow('last_sent_warning_emails', array('timestamp' => now(), 'warning_code' => $warningEmailCode));
		// If a record is found check when it was last sent
		} else {
			$lastSent = getRow('last_sent_warning_emails', 'timestamp', array('warning_code' => $warningEmailCode));
			$lastSent = strtotime($lastSent);
			// If email was sent within the frequency time, return false
			if (strtotime('+ '.setting('warning_email_frequency'), $lastSent) > time()) {
				return false;
			}
			// Otherwise send email and update last sent time
			updateRow('last_sent_warning_emails', array('timestamp' => now()), array('warning_code' => $warningEmailCode));
		}
	}
	
	
	require_once CMS_ROOT. 'zenario/libraries/lgpl/PHPMailer_5_2_17/PHPMailerAutoload.php';
	
	if ($addressFrom === false) {
		$addressFrom = setting('email_address_from');
	}
	if ($nameFrom === false) {
		$nameFrom = setting('email_name_from');
	}
	
	if ($body === '' || $body === null || $body === false) {
		$body = ' ';
	}
	
	if (!$precedence) {
		$precedence = 'bulk';
	}
	
	
	$mail = new PHPMailer($exceptions);
	$mail->Subject = $subject;
	$mail->Body = $body;
	$mail->CharSet = 'UTF-8';
	$mail->Encoding = 'base64';
	
	if ($addressFrom) {
		$mail->Sender = $addressFrom;
		$mail->From = $addressFrom;
	}
	if ($nameFrom) {
		$mail->FromName = $nameFrom;
	}
	if($addressReplyTo && $nameReplyTo) {
		$mail->AddReplyTo($addressReplyTo, $nameReplyTo);
	} else {
		$mail->AddReplyTo($mail->From, $mail->FromName);
	}
	
	if ($ccs) {
		foreach (array_unique(explodeAndTrim(str_replace(';', ',', $ccs))) as $emailAddress) {
			$mail->AddCC($emailAddress);
		}
	}
	if ($bccs) {
		foreach (array_unique(explodeAndTrim(str_replace(';', ',', $bccs))) as $emailAddress) {
			$mail->AddBCC($emailAddress);
		}
	}
	
	if (setting('smtp_specify_server')) {
		$mail->Mailer = 'smtp';
		$mail->isSMTP();
		$mail->Host = setting('smtp_host');
		$mail->Port = setting('smtp_port');
		$mail->SMTPSecure = setting('smtp_security');
		$mail->SMTPAutoTLS = false;
		
		if ($mail->SMTPAuth = (bool) setting('smtp_use_auth')) {
			$mail->Username = setting('smtp_username');
			$mail->Password = setting('smtp_password');
		}		
		$mail->SMTPDebug = false;
	}

	if (setting('debug_override_enable') && setting('debug_override_email_address')){
		$mail->AddAddress(setting('debug_override_email_address'));
		$addressToOverriddenBy = setting('debug_override_email_address');
	
	} elseif ($nameTo === false) {
		$mail->AddAddress($addressTo);
		$addressToOverriddenBy = '';
	
	} else {
		$mail->AddAddress($addressTo, $nameTo);
		$addressToOverriddenBy = '';
	}
	
	$mail->AddCustomHeader('Precedence: '. $precedence);
	
	if ($isHTML) {
		$mail->IsHTML(true);
		$mail->AltBody = html_entity_decode(strip_tags($body), ENT_COMPAT, 'UTF-8');
	} else {
		$mail->IsHTML(false);
	}
	
	if (!empty($attachments)) {
		foreach ($attachments as $fieldName => $fileToAttach) {
			if (file_exists($fileToAttach)){
				if (!empty($attachmentFilenameMappings[$fieldName])) {
					$mail->AddAttachment($fileToAttach, $attachmentFilenameMappings[$fieldName]);	
				} else {
					$mail->AddAttachment($fileToAttach);	
				}
			}
		}
	}
	
	return $mail->Send();
}

//	function sendSignal($signalName, $signalParams) {}

//	function setCookieConsent() {}

function setSetting($settingName, $value, $updateDB = true, $clearCache = true) {
	cms_core::$siteConfig[$settingName] = $value;
	
	if ($updateDB && cms_core::$lastDB) {
		$sql = "
			INSERT INTO ". DB_NAME_PREFIX. "site_settings SET
				`name` = '". sqlEscape($settingName). "',
				`value` = '". sqlEscape($value). "'
			ON DUPLICATE KEY UPDATE
				`value` = VALUES(`value`)";
		sqlUpdate($sql, $clearCache);
	}
}

cms_core::$whitelist[] = 'setting';
//	function setting($settingName) {}

function windowsServer() {
	return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}