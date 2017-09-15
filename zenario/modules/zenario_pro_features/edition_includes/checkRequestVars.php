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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


cms_core::$vars['userId'] =
cms_core::$vars['companyId'] =
cms_core::$vars['locationId'] = 

//For the Conference FEA
cms_core::$vars['conferenceId'] = 
cms_core::$vars['sessionId'] = 
cms_core::$vars['seminarId'] = 
cms_core::$vars['day'] = 
cms_core::$vars['roomId'] =
cms_core::$vars['streamId'] = 
cms_core::$vars['abstractId'] = 0;

//For the API documenter FEA
cms_core::$vars['classMethod'] = $_REQUEST['classMethod'] ?? '';

$status = require editionInclude('checkRequestVars', $continueFrom = 'zenario_pro_features');
if (!$status) {
	return $status;
}

$zclmPrefix = getModulePrefix('zenario_company_locations_manager');
$zlmPrefix = getModulePrefix('zenario_location_manager');
$zcmPrefix = getModulePrefix('zenario_conference_manager');

if ($zcmPrefix) {
	if (!cms_core::$vars['seminarId'] && !empty($_REQUEST['seminarId'])) {
		cms_core::$vars['seminarId'] = (int) $_REQUEST['seminarId'];
		if ($sessionId = getRow($zcmPrefix . 'seminars', 'session_id', cms_core::$vars['seminarId'])) {
			$_REQUEST['sessionId'] = $sessionId;
		}
	}
	if (!cms_core::$vars['sessionId'] && !empty($_REQUEST['sessionId'])) {
		cms_core::$vars['sessionId'] = (int) $_REQUEST['sessionId'];
		if ($session = getRow($zcmPrefix . 'sessions', array('conference_id', 'day'), cms_core::$vars['sessionId'])) {
			$_REQUEST['conferenceId'] = $session['conference_id'];
			$_REQUEST['day'] = $session['day'];
		}
	}
	if (!cms_core::$vars['conferenceId'] && !empty($_REQUEST['conferenceId'])) {
		cms_core::$vars['conferenceId'] = (int) $_REQUEST['conferenceId'];
	}
	if (!cms_core::$vars['day'] && !empty($_REQUEST['day'])) {
		cms_core::$vars['day'] = (int) $_REQUEST['day'];
	}
	if (!cms_core::$vars['roomId'] && !empty($_REQUEST['roomId'])) {
		cms_core::$vars['roomId'] = (int) $_REQUEST['roomId'];
	}
	if (!cms_core::$vars['streamId'] && !empty($_REQUEST['streamId'])) {
		cms_core::$vars['streamId'] = (int) $_REQUEST['streamId'];
	}
	//Can be multiple
	if (!cms_core::$vars['abstractId'] && !empty($_REQUEST['abstractId'])) {
		cms_core::$vars['abstractId'] = $_REQUEST['abstractId'];
	}
}

if ($zlmPrefix) {
	if (!cms_core::$vars['locationId']) {
		if (!empty($_REQUEST['locationId'])) {
			cms_core::$vars['locationId'] = (int) $_REQUEST['locationId'];
		}
	}
}

if ($zclmPrefix) {
	if (!cms_core::$vars['companyId']) {
		
		if (cms_core::$vars['locationId']
		 && (cms_core::$vars['companyId'] = getRow($zclmPrefix. 'company_location_link', 'company_id', array('location_id' => cms_core::$vars['locationId'])))) {
		
		} elseif (!empty($_REQUEST['companyId'])) {
			cms_core::$vars['companyId'] = (int) $_REQUEST['companyId'];
		}
	}
}

if (!cms_core::$vars['userId']) {
	if (!empty($_REQUEST['userId'])) {
		cms_core::$vars['userId'] = (int) $_REQUEST['userId'];
	}
}

//If a companyId and/or locationId is in the URL, check the current visitor is allowed to see that company and/or location
if ((cms_core::$vars['userId'] && !checkUserCan('view', 'user', cms_core::$vars['userId']))
 || (cms_core::$vars['companyId'] && !checkUserCan('view', 'company', cms_core::$vars['companyId']))
 || (cms_core::$vars['locationId'] && !checkUserCan('view', 'location', cms_core::$vars['locationId']))
 || (cms_core::$vars['conferenceId'] && !checkRowExists($zcmPrefix . 'conferences', ['id' => cms_core::$vars['conferenceId']]))
 || (cms_core::$vars['sessionId'] && !checkRowExists($zcmPrefix . 'sessions', ['id' => cms_core::$vars['sessionId']]))) {
	return ZENARIO_403_NO_PERMISSION;
}

//If the assetwolf module is running and there's no data pool in the URL,
//but there's a location in the URL that the visitor has permissions to see,
//then try to use the data pool for the location.
if (isset($a2Prefix) && $a2Prefix
 && !cms_core::$vars['dataPoolId']
 && !empty(cms_core::$vars['locationId'])) {
	cms_core::$vars['dataPoolId'] =
	cms_core::$vars['dataPoolId1'] = (int) getRow(
		$a2Prefix. 'assets',
		'id',
		['owner_type' => 'location', 'owner_id' => cms_core::$vars['locationId'], 'is_data_pool' => 1, 'parent_id' => 0]
	);
	
	//N.b. if the visitor has permissions to see a location then they should also be able to see the data pool for
	//the location, we don't need another checkUserCan() check here.
}

return true;