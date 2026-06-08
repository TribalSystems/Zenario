<?php
/*
 * Copyright (c) 2026, Tribal Limited
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

header('Content-Type: text/javascript; charset=UTF-8');
require '../basicheader.inc.php';

if (empty($_GET['langId'])) {
	exit;
}
$v = preg_replace('@[^\w\.-]@', '', $_GET['v'] ?? '1');
$langId = preg_replace('@[^\w\.-]@', '', $_GET['langId']);


//N.b. I'm not sure if I want to cache this, as then phrases would not be updated as people translate them.
#$ETag = 'zenario-datepicker-phrases-'. $langId. '-'. $v. '-';
#ze\cache::useBrowserCache($ETag);
#
#if (ze::$canCache) require CMS_ROOT. 'zenario/includes/bundle.pre_load.inc.php';


ze\db::loadSiteConfig();

//Enable the phrases translation system for this script
\ze::$trackPhrases = true;

$prevText = ze\lang::phrase('_PREV', false, 'zenario_common_features', $langId);
$nextText = ze\lang::phrase('_NEXT', false, 'zenario_common_features', $langId);

$monthNames = [];
$monthNamesShort = [];
for ($i = 1; $i <= 12; ++$i) {
	$monthNames[] = ze\lang::monthPhrase('_MONTH_LONG_', $i, $langId);
	$monthNamesShort[] = ze\lang::monthPhrase('_MONTH_SHORT_', $i, $langId);
}

$dayNames = [];
$dayNamesShort = [];
for ($i = 0; $i <= 6; ++$i) {
	$dayNames[] = ze\lang::dayPhrase('_WEEKDAY_', $i, $langId);
	$dayNamesShort[] = ze\lang::dayPhrase('_WEEKDAY_SHORT_', $i, $langId);
}

switch (ze::setting('first_day_of_the_week_for_calendars')) {
	case 'monday':
	default:
		$firstDayNumber = 1;
		break;
	case 'tuesday':
		$firstDayNumber = 2;
		break;
	case 'wednesday':
		$firstDayNumber = 3;
		break;
	case 'thursday':
		$firstDayNumber = 4;
		break;
	case 'friday':
		$firstDayNumber = 5;
		break;
	case 'saturday':
		$firstDayNumber = 6;
		break;
	case 'sunday':
		$firstDayNumber = 0;
		break;
}

echo '
$.datepicker.setDefaults({
	prevText: ', json_encode($prevText), ',
	nextText: ', json_encode($nextText), ',
	monthNames: ', json_encode($monthNames), ',
	monthNamesShort: ', json_encode($monthNamesShort), ',
	dayNames: ', json_encode($dayNames), ',
	dayNamesShort: ', json_encode($dayNamesShort), ',
	dayNamesMin: ', json_encode($dayNamesShort), ',
	firstDay: ', json_encode($firstDayNumber), '
});
';



//N.b. I'm not sure if I want to cache this, as then phrases would not be updated as people translate them.
#if (ze::$canCache) require CMS_ROOT. 'zenario/includes/bundle.post_display.inc.php';