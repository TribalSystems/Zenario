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


ze::$vars['userId'] =
ze::$vars['companyId'] =
ze::$vars['locationId'] = 0;

//For the Conference FEA
ze::$vars['conferenceId'] = 
ze::$vars['sessionId'] = 
ze::$vars['seminarId'] = 
ze::$vars['day'] = 
ze::$vars['roomId'] =
ze::$vars['streamId'] = 
ze::$vars['abstractId'] = 0;

$status = require ze::editionInclude('checkRequestVars', $continueFrom = 'zenario_pro_features');
if (!$status) {
	return $status;
}

$zclmPrefix = ze\module::prefix('zenario_company_locations_manager');
$zlmPrefix = ze\module::prefix('zenario_location_manager');
$zcmPrefix = ze\module::prefix('zenario_conference_manager');

if ($zcmPrefix) {
	//Can be multiple
	if (!ze::$vars['abstractId'] && !empty($_REQUEST['abstractId'])) {
		ze::$vars['abstractId'] = $_REQUEST['abstractId'];
		if (is_numeric(ze::$vars['abstractId']) 
			&& ($seminarId = ze\row::get($zcmPrefix . 'papers', 'seminar_id', ['abstract_id' => ze::$vars['abstractId']]))) {
			$_REQUEST['seminarId'] = $seminarId;
		}
	}
	
	if (!ze::$vars['seminarId'] && !empty($_REQUEST['seminarId'])) {
		ze::$vars['seminarId'] = (int) $_REQUEST['seminarId'];
		if ($sessionId = ze\row::get($zcmPrefix . 'seminars', 'session_id', ze::$vars['seminarId'])) {
			$_REQUEST['sessionId'] = $sessionId;
		}
	}
	if (!ze::$vars['sessionId'] && !empty($_REQUEST['sessionId'])) {
		ze::$vars['sessionId'] = (int) $_REQUEST['sessionId'];
		if ($session = ze\row::get($zcmPrefix . 'sessions', ['conference_id', 'day'], ze::$vars['sessionId'])) {
			$_REQUEST['conferenceId'] = $session['conference_id'];
			$_REQUEST['day'] = $session['day'];
		}
	}
	if (!ze::$vars['conferenceId'] && !empty($_REQUEST['conferenceId'])) {
		ze::$vars['conferenceId'] = (int) $_REQUEST['conferenceId'];
	}
	if (!ze::$vars['day'] && !empty($_REQUEST['day'])) {
		ze::$vars['day'] = (int) $_REQUEST['day'];
	}
	if (!ze::$vars['roomId'] && !empty($_REQUEST['roomId'])) {
		ze::$vars['roomId'] = (int) $_REQUEST['roomId'];
	}
	if (!ze::$vars['streamId'] && !empty($_REQUEST['streamId'])) {
		ze::$vars['streamId'] = (int) $_REQUEST['streamId'];
	}
}

if ($zlmPrefix) {
	if (!ze::$vars['locationId']) {
		if (!empty($_REQUEST['locationId'])) {
			ze::$vars['locationId'] = (int) $_REQUEST['locationId'];
		}
	}
}

if ($zclmPrefix) {
	if (!ze::$vars['companyId']) {
		
		if (ze::$vars['locationId']
		 && (ze::$vars['companyId'] = ze\row::get($zclmPrefix. 'company_location_link', 'company_id', ['location_id' => ze::$vars['locationId']]))) {
		
		} elseif (!empty($_REQUEST['companyId'])) {
			ze::$vars['companyId'] = (int) $_REQUEST['companyId'];
		}
	}
}

if (!ze::$vars['userId']) {
	if (!empty($_REQUEST['userId'])) {
		ze::$vars['userId'] = (int) $_REQUEST['userId'];
	}
}

//If a companyId and/or locationId is in the URL, check the current visitor is allowed to see that company and/or location
if ((ze::$vars['userId'] && !ze\user::can('view', 'user', ze::$vars['userId']))
 || (ze::$vars['companyId'] && !ze\user::can('view', 'company', ze::$vars['companyId']))
 || (ze::$vars['locationId'] && !ze\user::can('view', 'location', ze::$vars['locationId']))
 || (ze::$vars['conferenceId'] && !ze\row::exists($zcmPrefix . 'conferences', ['id' => ze::$vars['conferenceId']]))
 || (ze::$vars['sessionId'] && !ze\row::exists($zcmPrefix . 'sessions', ['id' => ze::$vars['sessionId']]))) {
	return ZENARIO_403_NO_PERMISSION;
}

//If the assetwolf module is running and there's no data pool in the URL,
//but there's a location in the URL that the visitor has permissions to see,
//then try to use the data pool for the location.
if (isset($a2Prefix) && $a2Prefix
 && !ze::$vars['dataPoolId']
 && !empty(ze::$vars['locationId'])) {
	ze::$vars['dataPoolId'] =
	ze::$vars['dataPoolId1'] = (int) ze\row::get(
		$a2Prefix. 'assets',
		'id',
		['owner_type' => 'location', 'owner_id' => ze::$vars['locationId'], 'is_data_pool' => 1, 'parent_id' => 0]
	);
	
	//N.b. if the visitor has permissions to see a location then they should also be able to see the data pool for
	//the location, we don't need another ze\user::can() check here.
}

return true;