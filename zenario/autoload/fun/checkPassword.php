<?php
/*
 * Copyright (c) 2020, Tribal Limited
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

//Otherwise, check the user's password.
//We need to check if we are using SHA one-way encryption on a password. Any users who
//have not changed their password or logged in since we implemented this will have their
//passwords stored as a plain-text.
//For plain texts, use (BINARY) string comparision for now, until we can fix them below.
//For encrypted passwords, we need to encrypt the attempted password and compare it against
//the encrypted password in the database.
//Also deal with the fact that we switched from using SHA1 to SHA2 in version 6.0.5.



//Have an optional parameter that lets us check by email and not username
if ($checkViaEmail) {
	$whereStatement = "email = '". \ze\escape::sql($adminUsernameOrEmail). "'";
} else {
	$whereStatement = "username = BINARY '". \ze\escape::sql($adminUsernameOrEmail). "'";
}

$passwordColumns = "
	authtype,
	password, password_salt,
	IF (reset_password_time IS NULL OR DATE_ADD(reset_password_time, INTERVAL 1 DAY) < NOW(),
		NULL,
		reset_password
	) AS reset_password,
	reset_password_salt,
	permissions,";

//Check for super admins first
$details = $sha2 = false;
if (\ze\db::connectGlobal()) {
		$sql = "
			SELECT SQL_NO_CACHE
				id,
				email,
				status,
				username,
				". $passwordColumns. "
				password_needs_changing,
				CONCAT(first_name, ' ', last_name) AS full_name
			FROM ". DB_PREFIX_GLOBAL ."admins
			WHERE ". $whereStatement. "
			  AND status = 'active'";
		$result = \ze\sql\g::select($sql);
	
	//Is there a superadmin with that name?
	if ($details = \ze\sql::fetchAssoc($result)) {
		$details['type'] = 'global';
	}
}

if (!$details) {
	$sql = "
		SELECT SQL_NO_CACHE
			id,
			email,
			status,
			username,
			". $passwordColumns. "
			CONCAT(first_name, ' ', last_name) AS full_name
		FROM ". DB_PREFIX ."admins
		WHERE ". $whereStatement. "
		  AND status = 'active'";
	$result = \ze\sql::select($sql);
	
	//Is there an admin with that name?
	if ($details = \ze\sql::fetchAssoc($result)) {
		//If the link to the global database is down, don't allow super admins to log in with the copies of their data
		if (($details['authtype'] ?? false) == 'super') {
			return new \ze\error('_ERROR_ADMIN_LOGIN_MULTISITE_CONNECTION');
		
		} else {
			$details['type'] = 'local';
		}
	
	} else {
		//If we found no-one with that name, return an error
		return new \ze\error('_ERROR_ADMIN_LOGIN_USERNAME');
	}
}


//Do their passwords match?
foreach (['password' => 'password_salt', 'reset_password' => 'reset_password_salt'] as $col => $sCol) {
	if ($details[$col] === null) {
		continue;
	}
	
	if ($details[$sCol] === null) {
		//Old, non-hashed passwords
		$details['password_correct'] = $details[$col] === $password;
	
	} elseif (substr($details[$col], 0, 6) != 'sha256') {
		//SHA1
		$details['password_correct'] = $details[$col] == \ze\user::hashPasswordSha1($details[$sCol], $password);
	
	} else {
		//SHA2
		$details['password_correct'] = $details[$col] == \ze\user::hashPasswordSha2($details[$sCol], $password);
		$sha2 = true;
	}
	
	if ($details['password_correct']) {
		$details['logged_in_via_'. $col] = true;
		break;
	}
}

//Return an error if the password was wrong
if (!$details['password_correct']) {
	if (empty($details['reset_password'])
	 && (empty($details['password']) || empty($details['password_salt']))) {
		return new \ze\error('_ERROR_ADMIN_LOGIN_PASSWORD_RESET');
	} else {
		return new \ze\error('_ERROR_ADMIN_LOGIN_PASSWORD');
	}
}

if ($details['type'] == 'global') {
	//If the password needs changing flag is set, don't allow a superadmin to log in until they have changed
	//it on the control site.
	if ($details['permissions'] != 'all_permissions'
	 && $details['permissions'] != 'specific_actions') {
		return new \ze\error('_ERROR_ADMIN_LOGIN_MULTISITE_PERMISSIONS');
	
	} elseif ($details['password_needs_changing']) {
		return new \ze\error('_ERROR_ADMIN_LOGIN_MULTISITE_PASSWORD_CHANGE_NEEDED');
	
	} else {
		return \ze\adminAdm::syncMultisiteAdmins($details['id']);
	}
}

//Update non-sha2 passwords to sha2. 
//Also, if an Admin has a reset password, either clear a reset password or switch to the reset
//password when the admin logs in, depending on which password they used to log in with.
if ($details['reset_password'] || !$sha2) {
	if ($details['reset_password'] && empty($details['logged_in_via_reset_password'])) {
		\ze\adminAdm::setPassword($details['id'], $password, 0);
	} else {
		\ze\adminAdm::setPassword($details['id'], $password);
	}
}

return $details['id'];