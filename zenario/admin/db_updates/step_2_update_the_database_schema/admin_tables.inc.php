<?php
/*
 * Copyright (c) 2025, Tribal Limited
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

/*
	Any admin-related tables should be created in this script.
	Reminder: every table you create here should also be listed in the local-admin-DROP.sql file
*/




//
//	Zenario 9.2
//

//Drop the "All content items in these languages" admin permission option
	ze\dbAdm::revision(54250
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	DROP COLUMN `specific_languages`
_sql


//
//	Zenario 9.3
//

//In 9.3, we're going through and fixing the character-set on several columns that should
//have been using "ascii"
);	ze\dbAdm::revision(55140
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admin_organizer_prefs`
	MODIFY COLUMN `checksum` varchar(22) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '{}'
_sql


//
//	Zenario 9.4
//

//In 9.4, we started counting admin failed logins.
//have been using "ascii"
);	ze\dbAdm::revision(57301
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	ADD COLUMN `failed_login_count_since_last_successful_login` int unsigned NOT NULL DEFAULT 0 AFTER `last_login_ip`
_sql


//
//	Zenario 9.6
//

//Tidy up a now unused admin setting from the database
);	ze\dbAdm::revision(59300
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]admin_settings`
	WHERE `name` = 'drop_downs_open_at'
_sql


//
//	Zenario 9.7
//

//In 9.7, we changed the "Trash admin" behaviour to also blank out
//the admin's email and remove all their permissions.
//Update existing trashed admin accounts to match.
);

if (ze\dbAdm::needRevision(59850)) {
	$result = ze\row::query('admins', 'id', ['status' => 'deleted']);
    $trashedAdmins = ze\sql::fetchValues($result);
    
    if (!empty($trashedAdmins)) {
    	$sql = "
    		UPDATE " . DB_PREFIX . "admins
    		SET email = ''
    		WHERE id IN (" . ze\escape::in($trashedAdmins) . ")";
    	ze\sql::update($sql);
    	
    	$sql = "
    		DELETE FROM " . DB_PREFIX . "action_admin_link
			WHERE admin_id IN (" . ze\escape::in($trashedAdmins) . ")";
		ze\sql::update($sql);
    }
	ze\dbAdm::revision(59850);
}

//We also deleted the _PRIV_EDIT_VACANCIES permission.
//Instead, job vacancies will be managed by the existing permissions systems
//(either _PRIV_EDIT_DRAFT or permissions for a specific content type).
//PLEASE NOTE: The permission is not use anymore, and the removal was backpatched to 9.6.
//However, the DB cleanup logic itself was not backpatched to 9.6.
ze\dbAdm::revision(60112
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]action_admin_link`
	WHERE `action_name` IN ('_PRIV_EDIT_VACANCIES', 'perm_job_vacanies')
_sql



//
//	Zenario 10.0
//

//In Zenario 10, we renamed a lot of cookies. Tidy up and delete anything from the
//admin settings table that was using the old name
);	ze\dbAdm::revision(60810
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]admin_settings`
	WHERE name LIKE 'COOKIE_ADMIN_SECURITY_CODE_%'
_sql

//In Zenario 10, we removed an unused module Content Notifications.
//Remove obsolete site settings and admin perms.
);	ze\dbAdm::revision(60951
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]action_admin_link`
	WHERE `action_name` = '_PRIV_APPEAR_ON_CONTENT_REQUEST_RECIPIENT_LIST'
_sql

, <<<_sql
	DELETE FROM `[[DB_PREFIX]]site_settings`
	WHERE `name` IN ('content_notification_email_subject', 'content_notification_email_body')
_sql

);