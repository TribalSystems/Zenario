<?php
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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


if ($time_format === true) {
	$time_format = ' %H:%i';
}

if ($rss) {
	$format_type = '%a, %d %b %Y';
	$time_format = ' %H:%i:%s ';

} elseif (!$format_type || $format_type == 'vis_date_format_long' || $format_type == '_LONG') {
	$format_type = setting('vis_date_format_long');

} elseif ($format_type == 'vis_date_format_med' || $format_type == '_MEDIUM') {
	$format_type = setting('vis_date_format_med');

} elseif ($format_type == 'vis_date_format_short' || $format_type == '_SHORT') {
	$format_type = setting('vis_date_format_short');
}


$sql = "SELECT DATE_FORMAT('" . sqlEscape($date) . "', '" . sqlEscape($format_type. $time_format) . "')";

$result = sqlQuery($sql);
list($formattedDate) = sqlFetchRow($result);

$returnDate = '';
if ($rss) {
	$returnDate = $formattedDate;
	
	if ($time_format) {
		$sql = "SELECT TIME_FORMAT(NOW() - UTC_TIMESTAMP(), '%H%i') ";
		$result = sqlQuery($sql);
		list($timezone) = sqlFetchRow($result);
		
		if (substr($timezone, 0, 1) != '-') {
			$timezone = '+'. $timezone;
		}
		
		$returnDate .= $timezone;
	}
	
} else {
	foreach (preg_split('/\[\[([^\[\]]+)\]\]/', $formattedDate, -1,  PREG_SPLIT_DELIM_CAPTURE) as $part) {
		$returnDate .= phrase($part, false, '', $lang);
	}
}

return $returnDate;

?>