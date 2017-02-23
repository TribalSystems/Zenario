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

//Allow any id from the core vars to be displayed
if (isset(cms_core::$vars[$name])) {
	return cms_core::$vars[$name];
}

switch ($name) {
	//Allow company name to be displayed if the Company Locations Manager Module is running
	case 'company_name':
		if ($zclmPrefix = getModulePrefix('zenario_company_locations_manager')) {
			return sqlFetchValue('
				SELECT company_name
				FROM '. DB_NAME_PREFIX. $zclmPrefix. 'companies
				WHERE id = '. (int) cms_core::$vars['companyId']
			);
		}
		break;
	
	//Allow location name to be displayed if the Location Manager Module is running
	case 'location_name':
		if ($zlmPrefix = getModulePrefix('zenario_location_manager')) {
			return sqlFetchValue('
				SELECT description
				FROM '. DB_NAME_PREFIX. $zlmPrefix. 'locations
				WHERE id = '. (int) cms_core::$vars['locationId']
			);
		}
		break;
	
	//Allow a user's first/last name to be displayed
	case 'user_first_and_last_name':
		return userFirstAndLastName(cms_core::$vars['userId']);
	
	//If we didn't match anything, check if other modules have any merge fields
	default:
		return require editionInclude('requestVarMergeField', $continueFrom = 'zenario_pro_features');
}