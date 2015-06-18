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
	$whereStatement = "email = '". sqlEscape($adminUsernameOrEmail). "'";
} else {
	$whereStatement = "username = BINARY '". sqlEscape($adminUsernameOrEmail). "'";
}

if (checkAdminTableColumnsExist() >= 2) {
	$passwordColumns = "authtype,";
} else {
	$passwordColumns = "NULL AS authtype,";
}

if (checkAdminTableColumnsExist() >= 1) {
	$passwordColumns .= "password, password_salt,";
} else {
	$passwordColumns .= "password, NULL AS password_salt,";
}

if (checkAdminTableColumnsExist() >= 3) {
	$passwordColumns .= "
		IF(reset_password_time IS NULL OR DATE_ADD(reset_password_time, INTERVAL 1 DAY) < NOW(),
			NULL,
			reset_password)
		AS reset_password,
		reset_password_salt,";

} else {
	$passwordColumns .= " NULL AS reset_password, NULL AS reset_password_salt,";
}

//Check for super admins first
$details = $sha2 = false;
if (connectGlobalDB()) {
		$sql = "
			SELECT SQL_NO_CACHE
				id,
				email,
				status,
				username,
				". $passwordColumns. "
				password_needs_changing,
				CONCAT(first_name, ' ', last_name) AS full_name
			FROM ". DB_NAME_PREFIX_GLOBAL ."admins
			WHERE ". $whereStatement. "
			AND status = 'active'";
		$result = sqlQuery($sql);
	connectLocalDB();
	
	//Is there a superadmin with that name?
	if ($details = sqlFetchAssoc($result)) {
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
		FROM ". DB_NAME_PREFIX ."admins
		WHERE ". $whereStatement. "
		AND status = 'active'";
	$result = sqlQuery($sql);
	
	//Is there an admin with that name?
	if ($details = sqlFetchAssoc($result)) {
		//If the link to the global database is down, don't allow super admins to log in with the copies of their data
		if (arrayKey($details, 'authtype') == 'super') {
			//Return an empty string in this case.
				//I want the "global db not enabled" and "password not correct" states to be different,
				//yet still both evaulate to false.
			return '';
		
		} else {
			$details['type'] = 'local';
		}
	
	} else {
		//If we found no-one with that name, return 0.
			//I want the "user not found" and "password not correct" states to be different,
			//yet still both evaulate to false
		return 0;
	}
}


//Do their passwords match?
foreach (array('password' => 'password_salt', 'reset_password' => 'reset_password_salt') as $col => $sCol) {
	if ($details[$col] === null) {
		continue;
	}
	
	if ($details[$sCol] === null) {
		//Old, non-hashed passwords
		$details['password_correct'] = $details[$col] === $password;
	
	} elseif (substr($details[$col], 0, 6) != 'sha256') {
		//SHA1
		$details['password_correct'] = $details[$col] == hashPasswordSha1($details[$sCol], $password);
	
	} else {
		//SHA2
		$details['password_correct'] = $details[$col] == hashPasswordSha2($details[$sCol], $password);
		$sha2 = true;
	}
	
	if ($details['password_correct']) {
		$details['logged_in_via_'. $col] = true;
		break;
	}
}

//Return true or false appropriately
if (!$details['password_correct'] || $details['status'] != 'active') {
	return false;
}

if ($details['type'] == 'global') {
	//If the password needs changing flag is set, don't allow a superadmin to log in until they have changed
	//it on the control site.
	if ($details['password_needs_changing']) {
		//Return null in this case.
			//I want the "password needs changing" and "password not correct" states to be different,
			//yet still both evaulate to false.
		return null;
	
	} else {
		return syncSuperAdmin($details['id']);
	}
}

//Update non-sha2 passwords to sha2. 
//Also, if an Admin has a reset password, either clear a reset password or switch to the reset
//password when the admin logs in, depending on which password they used to log in with.
if (checkAdminTableColumnsExist() >= 1) {
	if ($details['reset_password'] || !$sha2) {
		if ($details['reset_password'] && empty($details['logged_in_via_reset_password'])) {
			setPasswordAdmin($details['id'], $password, 0);
		} else {
			setPasswordAdmin($details['id'], $password);
		}
	}
}

return $details['id'];