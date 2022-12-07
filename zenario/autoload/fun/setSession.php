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



//Load this admin's details
$adminCols = [
	'id', 'username', 'last_login', 'first_name', 'last_name', 'modified_date',
	'permissions', 'specific_content_items', 'specific_content_types'
];

//During a migration from an earlier version, some of these columns won't be created until
//after the migration script has run, so this script should be a little sensitive to columns that don't exist yet!
if ($admin = \ze\row::get('admins', $adminCols, $adminIdL, [], $ignoreMissingColumns = true)) {
	
	$privs = [];
	$contentItems = [];
	$contentTypes = [];
	
	switch ($admin['permissions']) {
		
		case 'all_permissions':
			//For backwards compatability with a few old bits of the system,
			//add an action called "_ALL" if someone's permission option is set to "all_permissions"
			$privs = ['_ALL' => true];
			break;
		
		case 'specific_actions':
			//Look up each specific action that this admin is allowed to do from the links table
			$privs = \ze\admin::loadPerms($adminIdL);
			break;
		
		case 'specific_areas':
			//Admins who can only edit specific pages/areas of the site.
			//These are checked using PHP logic, but for backwards compatability with anything else
			//we'll also insert them into the database.
			$privs = \ze\admin::privsForTranslators();
			
			//Note down the specific content items this admin can edit
			if (!empty($admin['specific_content_items'])) {
				foreach (\ze\ray::explodeAndTrim($admin['specific_content_items']) as $id) {
					$contentItems[$id] = $id;
				}
			}
			
			//Note down the specific content types this admin can edit
			if (!empty($admin['specific_content_types'])) {
				foreach (\ze\ray::explodeAndTrim($admin['specific_content_types']) as $id) {
					$contentTypes[$id] = $id;
				}
			}
			
			break;
		
		default:
			return false;
	}
	
	\ze\cookie::antiSessionFixationScript();
	
	//Set the admin's details in their session
	//Some are different depending on whether this was a local or a super admin
	$_SESSION['admin_userid'] = $adminIdL;
	$_SESSION['admin_global_id'] = $adminIdG;
	$_SESSION['admin_first_name'] = $admin['first_name'];
	$_SESSION['admin_last_name'] = $admin['last_name'];
	$_SESSION['admin_modified_date'] = $admin['modified_date'];
	$_SESSION['admin_username'] = $admin['username'];
	$_SESSION['admin_server_host'] = \ze\link::host();
	
	$_SESSION['admin_permissions'] = $admin['permissions'];
	$_SESSION['admin_specific_content_items'] = $contentItems;
	$_SESSION['admin_specific_content_types'] = $contentTypes;
	$_SESSION['privs'] = $privs;
	
	//Mark the site that they've logged into
	$_SESSION['admin_logged_into_site'] = COOKIE_DOMAIN. SUBDIRECTORY. \ze::setting('site_id');
	
	
	return true;
} else {
	return false;
}