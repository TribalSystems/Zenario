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

//Add an extranet user into a group, or out of a group
function addUserToGroup($userId, $groupId, $remove = false) {
	if ($col = datasetFieldDBColumn($groupId)) {
		$sql = "UPDATE ". DB_NAME_PREFIX. "users_custom_data
					SET `" . $col . "`=" . ($remove ? 0 : 1) 
				. " WHERE user_id = ". (int) $userId;
		sqlQuery($sql);
	}
}

//Check for permission to see a content item
//	function checkPerm($cID, $cType, $cVersion = false) {}

//Check is a user is in a certain extranet group
//	function checkUserInGroup($groupId, $userId = 'session') {}

//Gets an array of ids => names of the groups that an user belongs to. 
//	function getUserGroups( $userId ) {}

//Attempt to automatically log a User in if the cookie is set on the User's Machine
function logUserInAutomatically() {
	if (isset($_SESSION)) {
		if (empty($_SESSION['extranetUserID'])) {
			
			if (isset($_COOKIE['LOG_ME_IN_COOKIE'])
			 && ($idAndMD5 = explode('_', $_COOKIE['LOG_ME_IN_COOKIE'], 2))
			 && (count($idAndMD5) == 2)
			 && ($user = getRow('users', array('id', 'first_name', 'last_name', 'email', 'screen_name', 'password'), array('id' => (int) $idAndMD5[0], 'status' => 'active')))
			 && ($idAndMD5[1] === md5(httpHost(). $user['id']. $user['screen_name']. $user['email']. $user['password']))) {
				logUserIn($user['id']);
				
				if (setting('cookie_consent_for_extranet') == 'granted') {
					setCookieConsent();
				}
			}
		
		//Also automatically log out any Users who have been suspended
		} elseif (!checkRowExists('users', array('id' => (int) $_SESSION['extranetUserID'], 'status' => 'active'))) {
			logUserOut();
		}
	}
	
	//A similar function for Admins
	if (isset($_SESSION['admin_userid']) && checkPriv()) {
		//Check if we can find the current admin
		$admin = false;
		if (empty($_SESSION['admin_global_id'])) {
			$admin = getRow('admins', array('modified_date'), array('authtype' => 'local', 'id' => $_SESSION['admin_userid'], 'status' => 'active'));
		
		} elseif (connectGlobalDB()) {
			$admin = getRow('admins', array('modified_date'), array('authtype' => 'local', 'id' => $_SESSION['admin_global_id'], 'status' => 'active'));
			connectLocalDB();
		}
		
		//If not, log them out
		if (!$admin) {
			unsetAdminSession();
		
		//Also update an Admin's permissions if they've been modified
		} elseif (empty($_SESSION['admin_modified_date']) || $admin['modified_date'] != $_SESSION['admin_modified_date']) {
			if (empty($_SESSION['admin_global_id'])) {
				setAdminSession($_SESSION['admin_userid']);
			} else {
				setAdminSession(syncSuperAdmin($_SESSION['admin_global_id']), $_SESSION['admin_global_id']);
			}
		}
	}

	cms_core::$adminId = adminId();
	cms_core::$userId = userId();
}

function logUserOut() {
	unset($_SESSION['extranetUserID']);
	unset($_SESSION['extranetUserImpersonated']);
	unset($_SESSION['extranetUserID_pending']);
	unset($_SESSION['extranetUser_firstname']);
	unset($_SESSION['extranetUserIsAnEmployee']);
	unset($_SESSION['extranetUserSteps']);
	$_SESSION['FORGET_EXTRANET_LOG_ME_IN_COOKIE'] = true;
}

function userEmail($userId = 'session') {
	if ($userId === 'session') {
		$userId = session('extranetUserID');
	}
	
	if ($userId) {
		return getEmail($userId);
	} else {
		return false;
	}
}

function userId() {
	return session('extranetUserID');
}

function userScreenName($userId = 'session') {
	if ($userId === 'session') {
		$userId = session('extranetUserID');
	}
	
	if ($userId) {
		return getUserScreenName($userId);
	} else {
		return false;
	}
}

function userUsername($userId = 'session') {
	if ($userId === 'session') {
		$userId = session('extranetUserID');
	}
	
	if ($userId) {
		return getUsername($userId);
	} else {
		return false;
	}
}

//	function visitorIP()



//Smart group functionality
//These have been placed here for now, but probably need moving somewhere else if and when the API is changed



function getSmartGroupDetails($smartGroupId) {
	return getRow('smart_groups',
			array(
					'name',
					'values',
					'created_on',
					'created_by',
					'last_modified_on',
					'last_modified_by'
			)
			, $smartGroupId);
}

function smartGroupInclusionsDescription($values) {
	$pieces = array();
	foreach (explode(',', arrayKey($values, 'first_tab/indexes')) as $index) {
		if ($index && (arrayKey($values, 'first_tab/rule_type_' . $index)=='group')) {
			$groups = array();
			foreach (explode(',', arrayKey($values, 'first_tab/rule_group_picker_' . $index)) as $groupId) {
				if ($groupId) {
					$groups[] = datasetFieldDBColumn($groupId);
				}
			}
			$pieces[] = adminPhrase("in group" . (count($groups)>1?'s (':' ') . "[[groups_list]]" . (count($groups)>1?')':''),
					array	(
							'groups_list' =>  implode(arrayKey($values, 'first_tab/rule_logic_' . $index)=='all'?' AND ': ' OR ', $groups)
					)
			);
		}
	}
	return implode(' AND ', $pieces);
}

function smartGroupExclusionsDescription($values) {
	$rv = '';
	if (arrayKey($values, 'exclude/rule_type')=='group') {
		$groups = array();
		foreach (explode(',', arrayKey($values, 'exclude/rule_group_picker')) as $groupId) {
			if ($groupId) {
				$groups[] = datasetFieldDBColumn($groupId);
			}
		}

		$rv = adminPhrase("in group" . (count($groups)>1?'s (':' ') . "[[groups_list]]" . (count($groups)>1?')':''),
				array	(
						'groups_list' =>  implode(arrayKey($values, 'exlude/rule_logic')=='all'?' AND ': ' OR ', $groups)
				)
		);
	}
	return $rv;
}

function smartGroupSQL($smartGroupId, &$whereStatement, &$joins) {
	if ($json = getRow('smart_groups', 'values', $smartGroupId)) {
		return advancedSearchSQL($whereStatement, $joins, 'zenario__users/panels/users', $json, $smartGroupId);
	}
	return false;
}

function countSmartGroupMembers($smartGroupId) {

	$sql = "
		SELECT COUNT(DISTINCT u.id)
		FROM ". DB_NAME_PREFIX. "users AS u";

	$whereStatement = "
		WHERE TRUE";
	
	$joins = array();

	if (smartGroupSQL($smartGroupId, $whereStatement, $joins)) {
			
		foreach ($joins as $join => $dummy) {
			$sql .= "
				". $join;
		}
			
		$sql .= $whereStatement;
		
		if (($result = sqlQuery($sql))
		 && ($row = sqlFetchRow($result))) {
			return $row[0];
		}
	}

	return 0;
}

function optOutOfSmartGroup($smartGroupId, $userId, $method) {
	setRow(
	'smart_group_opt_outs',
	array('opted_out_on' => now(), 'opt_out_method' => $method),
	array('smart_group_id' => $smartGroupId, 'user_id' => $userId));
}

function cancelOptOutOfSmartGroup($smartGroupId, $userId) {
	deleteRow(
	'smart_group_opt_outs',
	array('smart_group_id' => $smartGroupId, 'user_id' => $userId));
}

function hasOptedOutOfSmartGroup($smartGroupId, $userId) {
	return checkRowExists(
			'smart_group_opt_outs',
			array('smart_group_id' => $smartGroupId, 'user_id' => $userId));
}


//An API function to check if a user is valid.
//To avoid code duplication it's implemented by calling the saveUser() function
//with $doSave set to false.
function isInvalidUser($values, $id = false) {
	return saveUser($values, $id, false);
}


function generateUserIdentifier($userId, $details = array()) {
	
	$baseScreenName = $firstName = $lastName = $email = '';
	if (!empty($details['screen_name'])) {
		$baseScreenName = $details['screen_name'];
	} elseif ($userId) {
		$userDetails = getRow('users', array('identifier', 'screen_name', 'first_name', 'last_name', 'email'), $userId);
		foreach ($userDetails as $col => $value) {
			if (!isset($detais[$col])) {
				$details[$col] = $value;
			}
		}
		if ($details['screen_name']) {
			$baseScreenName = $userDetails['screen_name'];
		}
	}
	
	$firstName = $details['first_name'];
	$lastName = $details['last_name'];
	$email = $details['email'];
	// Remove special characters and get the base screen name
	if (!$baseScreenName) {
		$firstName = preg_replace('/[^A-Za-z0-9\-]/', '', $firstName);
		$lastName = preg_replace('/[^A-Za-z0-9\-]/', '', $lastName);
		if ($firstName || $lastName) {
			$baseScreenName = $firstName. $lastName;
		} elseif (($emailArray = explode('@', $email)) && ($email = preg_replace('/[^A-Za-z0-9\-]/', '', $emailArray[0]))) {
			$baseScreenName = $email;
		} else {
			$baseScreenName = 'User';
		}
		if (strlen($baseScreenName) > 50) {
			$baseScreenName = substr($baseScreenName, 0, 50);
		}
	}
	
	// Get all current similar screen names from all linked sites
	$screenNames = array();
	if (file_exists(CMS_ROOT. 'zenario_usersync_config.php')) {
		require CMS_ROOT. 'zenario_usersync_config.php';
		$thisIsHub =
			$hub['DBHOST'] == DBHOST
		 && $hub['DBNAME'] == DBNAME;
		
		$screenNames = getSimilarScreenNames($baseScreenName, $thisIsHub, $hub, $satellites);
		connectLocalDB();
	}
	
	// Get similar identifiers from this site
	$sql = '
		SELECT id, identifier 
		FROM '.DB_NAME_PREFIX.'users
		WHERE identifier LIKE "'.sqlEscape($baseScreenName).'%"
		AND id != '.(int)$userId;
	$result = sqlSelect($sql);
	while ($user = sqlFetchAssoc($result)) {
		$screenNames[strtoupper($user['identifier'])] = $user['id'];
	}
	
	// Find a unique screen name
	$uniqueScreenName = $baseScreenName;
	if (!isset($screenNames[strtoupper($uniqueScreenName)])) {
		return $uniqueScreenName;
	} else {
		$userId = (string)$userId;
		for ($i = 1; $i <= strlen($userId); $i++) {
			$userNumber = substr($userId, -($i));
			$baseScreenName = substr($baseScreenName, 0, (50 - ($i + 1)));
			$uniqueScreenName = $baseScreenName . '-' . $userNumber;
			if (!isset($screenNames[strtoupper($uniqueScreenName)])) {
				return $uniqueScreenName;
			}
		}
		$uniqueScreenName .= rand(0, 99);
		return $uniqueScreenName;
	}
}

function getSimilarScreenNames($screenName, $thisIsHub, $hub, $satellites) {
	$screenNames = array();
	$DBHost = DBHOST;
	$DBName = DBNAME;
	// if not thisIsHub, return hubs screen names
	if (!$thisIsHub) {
		if ($dbSelected = connectToDatabase($hub['DBHOST'], $hub['DBNAME'], $hub['DBUSER'], $hub['DBPASS'])) {
			cms_core::$lastDB = $dbSelected;
			cms_core::$lastDBHost = $hub['DBHOST'];
			cms_core::$lastDBName = $hub['DBNAME'];
			cms_core::$lastDBPrefix = $hub['DB_NAME_PREFIX'];
			
			$sql = '
				SELECT id, identifier
				FROM '. $hub['DB_NAME_PREFIX']. 'users
				WHERE identifier LIKE "'.sqlEscape($screenName).'%"';
			
			$result = sqlSelect($sql);
			while ($user = sqlFetchAssoc($result)) {
				$screenNames[strtoupper($user['identifier'])] = $user['id'];
			}
		} else {
			return false;
		}
	// If thisIsHub, return all satellite screen names
	} else {
		foreach($satellites as $satellite) {
			if ($satellite['DBHOST'] == $DBHost
				&& $satellite['DBNAME'] == $DBName) {
				continue;
			} else {
				if ($dbSelected = connectToDatabase($satellite['DBHOST'], $satellite['DBNAME'], $satellite['DBUSER'], $satellite['DBPASS'])) {
					cms_core::$lastDB = $dbSelected;
					cms_core::$lastDBHost = $satellite['DBHOST'];
					cms_core::$lastDBName = $satellite['DBNAME'];
					cms_core::$lastDBPrefix = $satellite['DB_NAME_PREFIX'];
					
					$sql = '
						SELECT id, identifier
						FROM '. $satellite['DB_NAME_PREFIX']. 'users
						WHERE identifier LIKE "'.sqlEscape($screenName).'%"';
					
					$result = sqlSelect($sql);
					while ($user = sqlFetchAssoc($result)) {
						$screenNames[strtoupper($user['identifier'])] = $user['id'];
					}
				}
			}
		}
	}
	return $screenNames;
}

function getNextScreenName() {
	$sql = "
		SELECT IFNULL(MAX(id), 0) + 1
		FROM ". DB_NAME_PREFIX. "users";
	$result = sqlQuery($sql);
	$row = sqlFetchRow($result);
	
	$prefix = 'User_';
	
	//Default hub users to a different name pattern to try and make collisions less likely
	if (file_exists(CMS_ROOT. 'zenario_usersync_config.php') && inc('zenario_users')) {
		if (zenario_users::thisIsHub()) {
			$prefix = 'Hub_User_';
		}
	}
	
	return $prefix. $row[0];
}

//An API function to save a user to the database.
//It will only save it if it passes a validation check; if it is not valid then this
//function will return an error object.
function saveUser($values, $id = false, $doSave = true) {
	//First, validate the submission.
	$e = new zenario_error();
	
	//Validate the screen_name field if it is set.
	//(Always validate it when creating a new user.)
	if (!empty($values['screen_name'])) {
		//...has no special characters...
		if (!validateScreenName($values['screen_name'])) {
			$e->add('screen_name', '_ERROR_SCREEN_NAME_INVALID');
		
		//...and is not already taken by a different row.
		} elseif (checkRowExists('users', array('screen_name' => $values['screen_name'], 'id' => array('!' => $id)))) {
			$e->add('screen_name', '_ERROR_SCREEN_NAME_IN_USE');
		}
	}
	
	if (!$id) {
		$values['created_date'] = now();
	}
	$values['modified_date'] = now();
	
	//Validate the email field if it is not empty.
	if (!empty($values['email'])) {
		if (!validateEmailAddress($values['email'])) {
			$e->add('email', '_ERROR_EMAIL_INVALID');
		
		//...and is not already taken by a different row.
		} elseif (checkRowExists('users', array('email' => $values['email'], 'id' => array('!' => $id)))) {
			$e->add('email', '_ERROR_EMAIL_NAME_IN_USE');
		}
	}
	
	//If there were errors, return the errors
	if (!empty($e->errors)) {
		return $e;
	
	//If we were just validating, stop at this point
	} elseif (!$doSave) {
		return false;
	
	} else {
		
		$password = false;
		if (isset($values['password'])) {
			$password = $values['password'];
			unset($values['password']);
		}
		
		//Save the details to the database
		$newId = setRow('users', $values, $id);
		
		$identifier = generateUserIdentifier($newId);
		updateRow('users', array('identifier' => $identifier), $newId);
		
		if ($password !== false) {
			setUsersPassword($newId, $password);
		}
		
		//Send a signal to let other Modules know this event has happened
		if ($id) {
			sendSignal(
				'eventUserModified',
				array('id' => $id));
		
		} else {
			sendSignal(
				'eventUserCreated',
				array('id' => $newId));
		}
		
		//Return the primary id from the database to the caller
		return $newId;
	}
}



//These functions were copied from the old extranet-cms.inc.php file:




function logUserIn($userId) {
	
	//Get details on this user
	$user = getRow('users', array('id', 'first_name', 'last_name', 'screen_name', 'email', 'password'), $userId);
	
	//Create a login hash (used for the logUserInAutomatically() function)
	$user['login_hash'] = $user['id']. '_'. md5(httpHost(). $user['id']. $user['screen_name']. $user['email']. $user['password']);
	unset($user['password']);
	
	//Update their last login time
	$sql = "
		UPDATE " . DB_NAME_PREFIX . "users SET
			session_id = '" . session_id() . "',
			ip = '" . visitorIP() . "',
			last_login = NOW()
		WHERE id = ". (int) $userId;
	sqlUpdate($sql);
	

	if(setting('sign_in_access_log'))
	{
	require_once CMS_ROOT. 'zenario/libraries/mit/browser/lib/browser.php';
	$browser = new Browser();
	
	$sql = "
		INSERT INTO ". DB_NAME_PREFIX. "user_signin_log SET
		    user_id = ". (int)  sqlEscape($userId).",
			screen_name = '". sqlEscape($user['screen_name']). "',
			first_name = '". sqlEscape($user['first_name']). "',
			last_name = '". sqlEscape($user['last_name']). "',
			email = '". sqlEscape($user['email']). "',
			login_datetime = NOW(),
			ip = '". sqlEscape(visitorIP()). "',
			browser = '". sqlEscape($browser->getBrowser()). "',
			browser_version = '". sqlEscape($browser->getVersion()). "',
			platform = '". sqlEscape($browser->getPlatform()). "'";
	sqlQuery($sql);
	}
	
	
	$_SESSION["extranetUserID"] = $userId;
	$_SESSION["extranetUser_firstname"] = $user['first_name'];
	
	
	sendSignal("eventUserLoggedIn",array("user_id" => $userId));
	
	return $user;
}

function getUserDetails($user_id) {
	if($user_id) {
		$sql = "SELECT u.*, ucd.* FROM ". DB_NAME_PREFIX. "users u 
				LEFT JOIN ". DB_NAME_PREFIX. "users_custom_data ucd
						ON u.id = ucd.user_id WHERE u.id=" . (int) $user_id;
		$result = sqlQuery($sql);
		if($result && ($row = sqlFetchAssoc($result))) {
			unset($row['user_id']);
			return $row;
		}
	}
	return false;
}

function getEmail($userId) {
	return getRow('users', 'email', $userId);
}
function getUsername($userId) {
	return getUserScreenName($userId);
}

function getUserScreenName($userId) {
	return getRow('users', 'screen_name', $userId);
}

function getUserIdentifier($userId) {
	return getRow('users', 'identifier', $userId);
}

function getUserFirstNameSpaceLastName($userId) {
	if ($row = getRow('users', array('first_name', 'last_name'), $userId)) {
		return $row['first_name']. ' '. $row['last_name'];
	}
	return null;
}

function getUserIdFromScreenName($screenName) {
	return getRow('users', 'id', array('screen_name' => $screenName));
}

function createPassword() {
	return randomString(8);
}

function setUsersPassword($userId, $password, $needsChanging = -1, $plaintext = -1) {
	
	if ($plaintext === -1) {
		$plaintext = setting('plaintext_extranet_user_passwords');
	}
	
	if ($plaintext) {
		$salt = null;
	
	} else {
		//Generate a random salt for this password. If someone gets hold of the encrypted value of
		//the password in the database, having a salt on it helps to stop dictonary attacks.
		$salt = randomString(8);
		$password = hashPassword($salt, $password);
	}
	
	
	$details = array('password' => $password, 'password_salt' => $salt);
	
	if ($needsChanging !== -1) {
		$details['password_needs_changing'] = $needsChanging;
	}
	
	updateRow('users', $details, $userId);
}

function checkUsersPassword($userId, $password) {
	//Look up some of this user's details
	if (!$user = getRow('users', array('id', 'password', 'password_salt'), (int) $userId)) {
		return false;
	}
	
	//Should the password be stored encrypted?
	$shouldBeEncrypted = !setting('plaintext_extranet_user_passwords');
	
	//The password could have been stored as either plain text, sha1 or sha2.
	if ($user['password_salt'] === null) {
		//Non-hashed passwords
		$wasEncrypted = false;
		$correct = $user['password'] === $password;
	
	} elseif (substr($user['password'], 0, 6) != 'sha256') {
		//SHA1
		$wasEncrypted = true;
		$correct = $user['password'] == hashPasswordSha1($user['password_salt'], $password);
	
	} else {
		//SHA2
		$wasEncrypted = true;
		$correct = $user['password'] == hashPasswordSha2($user['password_salt'], $password);
	}
	
	if ($correct) {
		//If the password was not stored in the form chosen in the site settings, save it in the correct form
		if ($wasEncrypted != $shouldBeEncrypted) {
			setUsersPassword($user['id'], $password);
		}
		
		return true;
	} else {
		return false;
	}
}

function deleteUser($userId) {
	deleteRow('user_content_link', array('user_id' => $userId));
	
	sendSignal('eventUserDeleted', array('userId' => $userId));
	
	deleteRow('users', $userId);
	deleteRow('users_custom_data', array('user_id' => $userId));
	
	if ($dataset = getDatasetDetails('users')) {
		deleteRow('custom_dataset_values_link', array('dataset_id' => $dataset['id'], 'linking_id' => $userId));
	}
	
	require_once CMS_ROOT. 'zenario/includes/admin.inc.php';
	deleteUnusedImagesByUsage('user');
}

function updateUserHash($userId) {
	$sql = "
		UPDATE 
			"  . DB_NAME_PREFIX . "users 
		SET 
			hash = md5('".sqlEscape(randomString())."')
		WHERE 
			id = " . (int) $userId;
	sqlQuery($sql);
}
