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

//Add an extranet user into a group, or out of a group
function addUserToGroup($userId, $groupId, $remove = false) {
	if ($col = datasetFieldDBColumn($groupId)) {
		setRow('users_custom_data', array($col => ($remove ? 0 : 1)), array('user_id' => $userId));
	}
}

//Check for permission to see a content item
cms_core::$whitelist[] = 'checkPerm';
//	function checkPerm($cID, $cType, $cVersion = false) {}

//Check is a user is in a certain extranet group
cms_core::$whitelist[] = 'checkUserInGroup';
//	function checkUserInGroup($groupId, $userId = 'session') {}

//Deprecated, please don't call
//	function getUserGroups( $userId ) {}

//Get details on the groups that a user is in
cms_core::$whitelist[] = 'userGroups';
function userGroups($userId = -1) {
	if ($userId === -1) {
		$userId = session('extranetUserID');
	}
	
	return getUserGroups($userId, false);
}

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
		
		//Check the session to see if the extranet user is for a different site that this one.
		//Also automatically log out any Users who have been suspended.
		} else
		if (empty($_SESSION['extranetUser_logged_into_site'])
		 || $_SESSION['extranetUser_logged_into_site'] != COOKIE_DOMAIN. SUBDIRECTORY. setting('site_id')
		 || !checkRowExists('users', array('id' => (int) $_SESSION['extranetUserID'], 'status' => 'active'))) {
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
		
		//Update an Admin's permissions if their admin record has been modified since they were last set.
			//Note that I'm also triggering this logic if a couple of $_SESSION variables are missing;
			//this is to catch the case where someone migrates the site from the old admin permission
			//system to the new admin permissions system, when other admins are still logged in.
		} else
		if (empty($_SESSION['admin_permissions'])
		 || empty($_SESSION['admin_modified_date'])
		 || $_SESSION['admin_modified_date'] != $admin['modified_date']) {
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
	unset($_SESSION['extranetUserSteps']);
	$_SESSION['FORGET_EXTRANET_LOG_ME_IN_COOKIE'] = true;
}

cms_core::$whitelist[] = 'userEmail';
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

cms_core::$whitelist[] = 'userScreenName';
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

cms_core::$whitelist[] = 'userUsername';
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

cms_core::$whitelist[] = 'visitorIP';
//	function visitorIP() {}




cms_core::$whitelist[] = 'userFieldDisplayValue';
function userFieldDisplayValue($cfield, $userId = -1, $returnCSV = true) {
	if ($userId === -1) {
		$userId = session('extranetUserID');
	}
	return datasetFieldValue('users', $cfield, $userId, $returnCSV, true);
}

cms_core::$whitelist[] = 'userFieldValue';
function userFieldValue($cfield, $userId = -1, $returnCSV = true) {
	if ($userId === -1) {
		$userId = session('extranetUserID');
	}
	return datasetFieldValue('users', $cfield, $userId, $returnCSV, false);
}

cms_core::$whitelist[] = 'datasetFieldDisplayValue';
function datasetFieldDisplayValue($dataset, $cfield, $recordId, $returnCSV = true) {
	return datasetFieldValue($dataset, $cfield, $recordId, $returnCSV, true);
}

cms_core::$whitelist[] = 'datasetFieldValue';
//	function datasetFieldValue($dataset, $cfield, $recordId, $returnCSV = true) {}


//Deprecated old name!
function getDatasetFieldValue($recordId, $cfield, $dataset = false) {
	return datasetFieldValue($dataset, $cfield, $recordId, true);
}






//Smart group functionality
//These have been placed here for now, but probably need moving somewhere else if and when the API is changed



function getSmartGroupDetails($smartGroupId) {
	return getRow('smart_groups',
			array(
					'name',
					'must_match',
					'created_on',
					'created_by',
					'last_modified_on',
					'last_modified_by'
			)
			, $smartGroupId);
}



function smartGroupSQL($smartGroupId, $usersTableAlias = 'u', $customTableAlias = 'ucd') {
	return require funIncPath(__FILE__, __FUNCTION__);
}

function countSmartGroupMembers($smartGroupId) {
	
	$sql = "
		SELECT COUNT(DISTINCT u.id)
		FROM ". DB_NAME_PREFIX. "users AS u
		LEFT JOIN ". DB_NAME_PREFIX. "users_custom_data AS ucd
		   ON ucd.user_id = u.id
		WHERE TRUE
		". smartGroupSQL($smartGroupId);
	
	return sqlFetchValue($sql);
}

cms_core::$whitelist[] = 'checkUserIsInSmartGroup';
function checkUserIsInSmartGroup($smartGroupId, $userId = -1) {
	
	if ($userId === -1) {
		$userId = userId();
	}
	
	if (!$userId) {
		return false;
	}
	
	$sql = "
		SELECT 1
		FROM ". DB_NAME_PREFIX. "users AS u
		LEFT JOIN ". DB_NAME_PREFIX. "users_custom_data AS ucd
		   ON ucd.user_id = u.id
		WHERE u.id = ". (int) $userId. "
		". smartGroupSQL($smartGroupId). "
		LIMIT 1";
	
	$result = sqlSelect($sql);
	return (bool) sqlFetchRow($result);
}



//An API function to check if a user is valid.
//To avoid code duplication it's implemented by calling the saveUser() function
//with $doSave set to false.
function isInvalidUser($values, $id = false) {
	return saveUser($values, $id, false);
}


function generateUserIdentifier($userId, $details = array()) {
	
	//Look up details on this user if not provided
	if (empty($details)
	 || !isset($details['email'])
	 || !isset($details['last_name'])
	 || !isset($details['first_name'])
	 || !isset($details['screen_name'])) {
		$details = getRow('users', array('screen_name', 'first_name', 'last_name', 'email'), $userId);
	}
	
	$baseScreenName = '';
	$firstName = $details['first_name'];
	$lastName = $details['last_name'];
	$email = $details['email'];
	
	//If this site uses screen names, use the screen name as the identifier...
	if (!setting('user_use_screen_name')
	 || !($baseScreenName = $details['screen_name'])) {
		//...otherwise calcuate an identifier from the first name, last name and/or email address
		
		// Remove special characters and get the base screen name
		$firstName = trimNonWordCharactersUnicode($firstName);
		$lastName = trimNonWordCharactersUnicode($lastName);
		if ($firstName || $lastName) {
			$baseScreenName = $firstName. $lastName;
		} elseif (($emailArray = explode('@', $email)) && ($email = trimNonWordCharactersUnicode($emailArray[0]))) {
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
		if ($dbSelected = connectToDatabase($hub['DBHOST'], $hub['DBNAME'], $hub['DBUSER'], $hub['DBPASS'], arrayKey($hub, 'DBPORT'))) {
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
				if ($dbSelected = connectToDatabase($satellite['DBHOST'], $satellite['DBNAME'], $satellite['DBUSER'], $satellite['DBPASS'], arrayKey($satellite, 'DBPORT'))) {
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
	
	
	//Ensure salutation first_name, last_name are not too long
	if (!empty($values['salutation'])) {
		if (strlen($values['salutation']) > 25) {
			$e->add('salutation', 'Your Salutation cannot be more than 25 characters long.');
		}
	}
	if (!empty($values['first_name'])) {
		if (strlen($values['first_name']) > 100) {
			$e->add('first_name', 'Your First Name cannot be more than 100 characters long.');
		}
	}
	if (!empty($values['last_name'])) {
		if (strlen($values['last_name']) > 100) {
			$e->add('last_name', 'Your Last Name cannot be more than 100 characters long.');
		}
	}
	
	//Backwards compatability for a couple of renamed columns
	if (!isset($values['last_login_ip']) && isset($values['ip'])) {
		$values['last_login_ip'] = $values['ip'];
		unset($values['ip']);
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
		
		if ($id && !empty($values['status']) && $values['status'] == 'contact') {
			$values['parent_id'] = 0;
			$sql = '
				UPDATE ' . DB_NAME_PREFIX . 'users u
				INNER JOIN ' . DB_NAME_PREFIX . 'users u2
					ON u.parent_id = u2.id
				SET u.parent_id = 0
				WHERE u2.id = ' . (int)$id;
			sqlUpdate($sql);
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




function logUserIn($userId, $impersonate = false) {
	
	//Get details on this user
	$user = getRow('users', array('id', 'first_name', 'last_name', 'screen_name', 'email', 'password'), $userId);
	
	//Create a login hash (used for the logUserInAutomatically() function)
	$user['login_hash'] = $user['id']. '_'. md5(httpHost(). $user['id']. $user['screen_name']. $user['email']. $user['password']);
	unset($user['password']);
	
	if (!$impersonate) {
		//Update their last login time
		$sql = "
			UPDATE " . DB_NAME_PREFIX . "users SET
				last_login_ip = '". sqlEscape(visitorIP()). "',
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
		sendSignal('eventUserLoggedIn',array('user_id' => $userId));
	}
	
	$_SESSION['extranetUserID'] = $userId;
	$_SESSION['extranetUser_firstname'] = $user['first_name'];
	$_SESSION['extranetUser_logged_into_site'] = COOKIE_DOMAIN. SUBDIRECTORY. setting('site_id');
	
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

cms_core::$whitelist[] = 'userFirstAndLastName';
function userFirstAndLastName($userId = -1) {
	if ($userId === -1) {
		$userId = session('extranetUserID');
	}
	if ($row = getRow('users', array('first_name', 'last_name'), $userId)) {
		return $row['first_name']. ' '. $row['last_name'];
	}
	return null;
}
function getUserFirstNameSpaceLastName($userId) {
	return userFirstAndLastName($userId);
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
	$details['reset_password_time'] = now();
	
	updateRow('users', $details, $userId);
	//Adding hash
	updateUserHash($userId);
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
		UPDATE ". DB_NAME_PREFIX. "users 
		SET hash = md5(CONCAT(id, '-". date('Yz'). '-'. primaryDomain(). "-', email))
		WHERE id = ". (int) $userId;
	sqlUpdate($sql, false);
}
