<?php
/*
 * Copyright (c) 2014, Tribal Limited
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

//We've changed how the admin permissions were stored
//Fire the save procedure for every admin, in order to convert their permissions to the new format
if (needRevision(11251)) {
	//Convert permissions from version 5 to version 6, if you do a straight upgrade from 5 to 6.0.5
	$result = getRows('admins',
		array(
			'id',
			'authtype',
			'perm_manage',
			'perm_sysadmin',
			'perm_publish',
			'perm_author',
			'perm_editmenu',
			'perm_manageforum',
			'perm_edit_users',
			'perm_edit_groups'),
		array());
	
	while($perms = sqlFetchAssoc($result)) {
		$adminId = $perms['id'];
		unset($perms['id']);
		saveAdminPerms($perms, $adminId);
	}
	
	if (adminId()) {
		setAdminSession(adminId(), session('admin_global_id'));
	}
	
} elseif (needRevision(19470)) {
	//Convert earlier zenario 6 permissions to 6.0.5
	$result = getRows('admins', 'id', array());
	while($admin = sqlFetchAssoc($result)) {
		$perms = array();
		loadAdminPerms($perms, $admin['id']);
		saveAdminPerms($perms, $admin['id']);
	}
	
	if (adminId()) {
		setAdminSession(adminId(), session('admin_global_id'));
	}
}
revision(19470);

//After we've done the migration above, drop the old permissions columns on the admins table
revision( 19490
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `perm_manage`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `perm_sysadmin`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `perm_publish`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `perm_author`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `perm_editmenu`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `perm_manageforum`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `perm_override_author_lock`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `perm_view_users`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `perm_edit_users`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `perm_view_groups`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `perm_edit_groups`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `perm_translate`;
_sql
);


//IPs used to be stored as an integer. We should now store them as a string so we can handle Proxy lists and IPV6
if (needRevision(20775)) {
	$sql = "
		SELECT DISTINCT last_login_ip
		FROM ". DB_NAME_PREFIX. "admins
		WHERE last_login_ip NOT LIKE '%.%'
		  AND last_login_ip NOT LIKE '%:%'";
	
	$result = sqlQuery($sql);
	while ($row = sqlFetchRow($result)) {
		if ($row[0] && is_numeric($row[0])) {
			updateRow('admins', array('last_login_ip' => long2ip($row[0])), array('last_login_ip' => $row[0]));
		}
	}
	
	revision(20775);
}