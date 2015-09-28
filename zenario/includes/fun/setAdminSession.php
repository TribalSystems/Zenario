<?php
/*
 * Copyright (c) 2015, Tribal Limited
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


$allowedActions = array();

//Look up the id of each action that the admin is allowed to do from the links table
// 	(For backwards compatability purposes this statement should not give an error message if it failes)
$sql = "
	SELECT action_name
	FROM ". DB_NAME_PREFIX. "action_admin_link
	WHERE admin_id = ". (int) $adminIdL;

if ($result = @sqlSelect($sql)) {
	while($row = sqlFetchAssoc($result)) {
		$allowedActions[$row['action_name']] = true;
	}
}


//Look up the admin's name, and other details, from the admin table
$sql = "
	SELECT
		id,
		username,
		last_login,
		first_name,
		last_name,
		modified_date
	FROM ". DB_NAME_PREFIX. "admins
	WHERE id = ". (int) $adminIdL;
$result = sqlQuery($sql);

if (sqlNumRows($result)>0) {
	$row = sqlFetchArray($result);
	
	//Set the admin's details in their session
	//Some are different depending on whether this was a local or a super admin
	$_SESSION['admin_userid'] = $adminIdL;
	$_SESSION['admin_global_id'] = $adminIdG;
	$_SESSION['admin_first_name'] = $row['first_name'];
	$_SESSION['admin_last_name'] = $row['last_name'];
	$_SESSION['admin_modified_date'] = $row['modified_date'];
	$_SESSION['admin_username'] = $row['username'];
	$_SESSION['admin_server_host'] = httpHost();
	
	$_SESSION['privs'] = $allowedActions;
	
	//Mark the site that they've logged into
	$_SESSION['admin_logged_into_site'] = COOKIE_DOMAIN. SUBDIRECTORY. setting('site_id');
	
	return true;
} else {
	return false;
}