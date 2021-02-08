<?php
/*
 * Copyright (c) 2021, Tribal Limited
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



//This file contains php scripts code for converting user data after some database structure changes



//If anyone has been using the extranet registration module in version 7.6 or 7.7 before the patch,
//the send_delayed_registration_email column will have been created on the wrong table.
//Move the data and delete the old column.
if (ze\dbAdm::needRevision(40780)) {
	
	if (ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'users_custom_data LIKE "send_delayed_registration_email"')) {
		ze\sql::update('
			UPDATE '. DB_PREFIX . 'users AS u
			INNER JOIN '. DB_PREFIX. 'users_custom_data AS ucd
			   ON ucd.user_id = u.id
			  AND ucd.send_delayed_registration_email = 1
			SET u.send_delayed_registration_email = 1
		');
	}
	
	if ($details = ze\dataset::fieldDetails('users', 'send_delayed_registration_email')) {
		ze\datasetAdm::deleteField($details['id']);
	}
	
	ze\dbAdm::revision(40780);
}

//If people migrating from version 7 *still* haven't switched to encrypted passwords,
//force them to switch and encrypt them during the migration to version 8.
if (ze\dbAdm::needRevision(41700)) {
	
	foreach (ze\row::getAssocs('users', ['id', 'password'], ['password_salt' => null]) as $user) {
		ze\userAdm::setPassword($user['id'], $user['password']);
	}
	
	ze\dbAdm::revision(41700);
}

//Delete old template from DB
if (ze\dbAdm::needRevision(41741)) {
	$code = 'zenario_users__to_user_account_suspended';
	$sql = "
		DELETE FROM " . DB_PREFIX . "email_templates
		WHERE code = '". ze\escape::sql($code) . "'";
	ze\sql::update($sql);
	ze\contentAdm::removeItemFromPluginSettings('email_template', 0, $code);
	
	ze\dbAdm::revision(41741);
}

//Replace template and delete old one
//Rename new template
if (ze\dbAdm::needRevision(41742)) {
	$oldTemplate = 'zenario_extranet_registration__to_user_account_activation_en';
	$newTemplate = 'zenario_users__to_user_account_activated';
	
	$sql = '
		UPDATE ' . DB_PREFIX . 'site_settings
		SET value = "' . ze\escape::sql($newTemplate) . '"
		WHERE value = "' . ze\escape::sql($oldTemplate) . '"';
	$sql = '
		UPDATE ' . DB_PREFIX . 'plugin_settings
		SET value = "' . ze\escape::sql($newTemplate) . '"
		WHERE value = "' . ze\escape::sql($oldTemplate) . '"';
	
	$sql = "
		DELETE FROM " . DB_PREFIX . "email_templates
		WHERE code = '". ze\escape::sql($oldTemplate) . "'";
	ze\sql::update($sql);
	
	$sql = "
		UPDATE " . DB_PREFIX . "email_templates
		SET template_name = 'To User: Account activated'
		WHERE code = 'zenario_users__to_user_account_activated'";
	ze\sql::update($sql);
	
	ze\dbAdm::revision(41741);
}