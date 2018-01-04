<?php
/*
 * Copyright (c) 2018, Tribal Limited
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
		$userId = $_SESSION['extranetUserID'] ?? false;
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
		$userId = $_SESSION['extranetUserID'] ?? false;
	}
	
	if ($userId) {
		return getEmail($userId);
	} else {
		return false;
	}
}

function userId() {
	return ($_SESSION['extranetUserID'] ?? false);
}

cms_core::$whitelist[] = 'userScreenName';
function userScreenName($userId = 'session') {
	if ($userId === 'session') {
		$userId = $_SESSION['extranetUserID'] ?? false;
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
		$userId = $_SESSION['extranetUserID'] ?? false;
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
		$userId = $_SESSION['extranetUserID'] ?? false;
	}
	return datasetFieldValue('users', $cfield, $userId, $returnCSV, true);
}

cms_core::$whitelist[] = 'userFieldValue';
function userFieldValue($cfield, $userId = -1, $returnCSV = true) {
	if ($userId === -1) {
		$userId = $_SESSION['extranetUserID'] ?? false;
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
					'intended_usage',
					'must_match',
					'created_on',
					'created_by',
					'last_modified_on',
					'last_modified_by'
			)
			, $smartGroupId);
}


define('ZENARIO_NOT_IN_SMART_GROUP', "AND FALSE");
function smartGroupSQL(&$and, &$tableJoins, $smartGroupId, $list = true, $usersTableAlias = 'u', $customTableAlias = 'ucd') {
	return require funIncPath(__FILE__, __FUNCTION__);
}

function countSmartGroupMembers($smartGroupId) {
	
	$and = $tableJoins = '';
	if (smartGroupSQL($and, $tableJoins, $smartGroupId)) {
		return sqlFetchValue("
			SELECT COUNT(DISTINCT u.id)
			FROM ". DB_NAME_PREFIX. "users AS u
			LEFT JOIN ". DB_NAME_PREFIX. "users_custom_data AS ucd
			   ON ucd.user_id = u.id
			". $tableJoins. "
			WHERE TRUE
			". $and);
	}
	
	return false;
}

cms_core::$whitelist[] = 'checkUserIsInSmartGroup';
function checkUserIsInSmartGroup($smartGroupId, $userId = -1) {
	
	$and = $tableJoins = '';
	if ($userId === -1) {
		$userId = userId();
	}
	
	if ($userId && smartGroupSQL($and, $tableJoins, $smartGroupId, false)) {
		return (bool) sqlFetchRow("
			SELECT 1
			FROM ". DB_NAME_PREFIX. "users AS u
			LEFT JOIN ". DB_NAME_PREFIX. "users_custom_data AS ucd
			   ON ucd.user_id = u.id
			". $tableJoins. "
			WHERE u.id = ". (int) $userId. "
			". $and. "
			LIMIT 1"
		);
	}
	
	return false;
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
	
	$baseIdentifier = '';
	$firstName = $details['first_name'];
	$lastName = $details['last_name'];
	$email = $details['email'];
	
	//Create a "Base identifier" for this user based on their details
	if (!setting('user_use_screen_name')
	 || !($baseIdentifier = $details['screen_name'])) {
		$firstName = trimNonWordCharactersUnicode($firstName);
		$lastName = trimNonWordCharactersUnicode($lastName);
		if ($firstName || $lastName) {
			$baseIdentifier = $firstName. $lastName;
		} elseif (($emailArray = explode('@', $email)) && ($email = trimNonWordCharactersUnicode($emailArray[0]))) {
			$baseIdentifier = $email;
		} else {
			$baseIdentifier = 'User';
		}
		if (strlen($baseIdentifier) > 50) {
			$baseIdentifier = substr($baseIdentifier, 0, 50);
		}
	}
	
	//Then create a unqiue identifier by appending some numbers to the end of the "Base identifier"
	
	//Check if the identifier column is encrypted
	checkTableDefinition(DB_NAME_PREFIX. 'users');
	if (!cms_core::$dbCols[DB_NAME_PREFIX. 'users']['identifier']->encrypted) {
		//Attempt to generate a unique indentifier
	
		// Get all current identifiers from all linked sites
		$identifiers = array();
		if (file_exists(CMS_ROOT. 'zenario_usersync_config.php')) {
			require CMS_ROOT. 'zenario_usersync_config.php';
			$thisIsHub =
				$hub['DBHOST'] == DBHOST
			 && $hub['DBNAME'] == DBNAME;
		
			$identifiers = getSimilarIdentifiers($baseIdentifier, $thisIsHub, $hub, $satellites);
			connectLocalDB();
		}
	
		// Get similar identifiers from this site
		$sql = '
			SELECT id, identifier 
			FROM '.DB_NAME_PREFIX.'users
			WHERE identifier LIKE "'.sqlEscape($baseIdentifier).'%"
			AND id != '.(int)$userId;
		$result = sqlSelect($sql);
		while ($user = sqlFetchAssoc($result)) {
			$identifiers[strtoupper($user['identifier'])] = $user['id'];
		}
	
		// Find a unique indentifier
		$uniqueIdentifier = $baseIdentifier;
		if (!isset($identifiers[strtoupper($uniqueIdentifier)])) {
			return $uniqueIdentifier;
		} else {
			$userId = (string)$userId;
			for ($i = 1; $i <= strlen($userId); $i++) {
				$userNumber = substr($userId, -($i));
				$baseIdentifier = substr($baseIdentifier, 0, (50 - ($i + 1)));
				$uniqueIdentifier = $baseIdentifier . '-' . $userNumber;
				if (!isset($identifiers[strtoupper($uniqueIdentifier)])) {
					return $uniqueIdentifier;
				}
			}
			$uniqueIdentifier .= rand(0, 99);
			return $uniqueIdentifier;
		}
	
	} else {
		//Attempt to generate a unique indentifier... without using a LIKE
		$uniqueIdentifier = $baseIdentifier;
		if (!checkRowExists('users', ['identifier' => $uniqueIdentifier, 'id' => ['!' => $userId]])) {
			return $uniqueIdentifier;
		} else {
			$userId = (string)$userId;
			for ($i = 1; $i <= strlen($userId); $i++) {
				$userNumber = substr($userId, -($i));
				$baseIdentifier = substr($baseIdentifier, 0, (50 - ($i + 1)));
				$uniqueIdentifier = $baseIdentifier . '-' . $userNumber;
				if (!checkRowExists('users', ['identifier' => $uniqueIdentifier, 'id' => ['!' => $userId]])) {
					return $uniqueIdentifier;
				}
			}
			$uniqueIdentifier .= rand(0, 99);
			return $uniqueIdentifier;
		}
	}
}

//N.b. this function won't work if identifier is encrypted on a site!
function getSimilarIdentifiers($screenName, $thisIsHub, $hub, $satellites) {
	$identifiers = array();
	$DBHost = DBHOST;
	$DBName = DBNAME;
	// if not thisIsHub, return hubs identifiers
	if (!$thisIsHub) {
		if ($dbSelected = connectToDatabase($hub['DBHOST'], $hub['DBNAME'], $hub['DBUSER'], $hub['DBPASS'], ($hub['DBPORT'] ?? false))) {
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
				$identifiers[strtoupper($user['identifier'])] = $user['id'];
			}
		} else {
			return false;
		}
	// If thisIsHub, return all satellite identifiers
	} else {
		foreach($satellites as $satellite) {
			if ($satellite['DBHOST'] == $DBHost
				&& $satellite['DBNAME'] == $DBName) {
				continue;
			} else {
				if ($dbSelected = connectToDatabase($satellite['DBHOST'], $satellite['DBNAME'], $satellite['DBUSER'], $satellite['DBPASS'], ($satellite['DBPORT'] ?? false))) {
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
						$identifiers[strtoupper($user['identifier'])] = $user['id'];
					}
				}
			}
		}
	}
	return $identifiers;
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
		//...and is not too long.
		} elseif (strlen($values['screen_name']) > 50) {
			$e->add('screen_name', 'Your Screen Name cannot be more than 50 characters long.');
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
			UPDATE [users AS u] SET
				[u.last_login_ip = 0],
				last_login = NOW()
			WHERE id = [1]";
		sqlUpdate($sql, [visitorIP(), $userId]);
		
	
		if(setting('sign_in_access_log')){
			require_once CMS_ROOT. 'zenario/libraries/mit/browser/lib/browser.php';
			$browser = new Browser();
		
			if($days = setting('period_to_delete_sign_in_log')){
				if(is_numeric($days)){
					$today = date('Y-m-d');
					$date = date('Y-m-d', strtotime('-'.$days.' day', strtotime($today)));
					if($date){
						$sql = " 
							DELETE FROM ". DB_NAME_PREFIX. "user_signin_log
							WHERE login_datetime < '".sqlEscape($date)."'";
						sqlUpdate($sql);
					}
				}
			}
		
			$sql = "
				INSERT INTO ". DB_NAME_PREFIX. "user_signin_log SET
					user_id = ". (int)  sqlEscape($userId).",
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

function getUserDetails($userId) {
	
	if ($user = getRow('users', true, $userId)) {
		if ($custom_data = getRow('users_custom_data', true, $userId)) {
			unset($custom_data['user_id']);
			$user = array_merge($custom_data, $user);
		}
	}
	return $user;
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
		$userId = $_SESSION['extranetUserID'] ?? false;
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
	
	$numbers = "0,1,2,3,4,5,6,7,8,9";
	$letters = "a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z";
	$symbols = "!,#,$,%,<,>,(,),*,+,-,@,?,{,},_";
	
	$lowercase = explode(',',$letters);
	$uppercase = explode(',',strtoupper($letters));
	$symbolsArray = explode(',',$symbols);
	$numbersArray = explode(',',$numbers);
	
	$password = "";
	$passwordLength = max(5, (int) setting('min_extranet_user_password_length'));
	
	$passwordCharacters = array();
	
	if($passwordLength){
	
		if(setting('a_z_uppercase_characters')){
			$passwordCharacters = array_merge($passwordCharacters,$uppercase);
		}
		
		if(setting('a_z_lowercase_characters')){
			$passwordCharacters = array_merge($passwordCharacters,$lowercase);
		}
		
		if(setting('0_9_numbers_in_user_password')){
			$passwordCharacters = array_merge($passwordCharacters,$numbersArray);
		}
		
		if(setting('symbols_in_user_password')){
			$passwordCharacters = array_merge($passwordCharacters,$symbolsArray);
		}
		
		if($passwordCharacters){
			$lenght = count($passwordCharacters) - 1;
			for($i=1; $i<=$passwordLength; $i++){
				$randomNumber = mt_rand(0, $lenght);
				$password .= $passwordCharacters[$randomNumber];
			}
		}
	
	}
	
	if ($password) {
		return $password;
	} else {
		return randomString($passwordLength);
	}	
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
	
	$emailAddress = getRow('users', 'email', $userId);
	
	$sql = "
		UPDATE ". DB_NAME_PREFIX. "users 
		SET hash = '". sqlEscape(hash64($userId. '-'. date('Yz'). '-'. primaryDomain(). '-'. $emailAddress)). "'
		WHERE id = ". (int) $userId;
	sqlUpdate($sql, false, false);
}


function checkNamedUserPermExists($perm, &$directlyAssignedToUser, &$hasRoleAtCompany, &$hasRoleAtLocation, &$hasRoleAtLocationAtCompany, &$onlyIfHasRolesAtAllAssignedLocations) {
	
	switch ($perm) {
		//Permissions for changing site settings
		case 'perm.manage.options-assetwolf':
		//Possible permissions for companies, locations and users
		case 'perm.create-company.unassigned':
		case 'perm.delete.company':
		case 'perm.create-location.unassigned':
		case 'perm.create-user.unassigned':
		//Export all of the asset data on a site
		case 'perm.export.allData':
		//Recalculate all of the asset data on a site
		case 'perm.recalculate.allData':
			//Superusers only
			return true;
		case 'perm.view.company':
		case 'perm.edit.company':
		case 'perm.create-location.company':
			return
				$hasRoleAtCompany = true;
		case 'perm.edit.location':
		case 'perm.delete.location':
			return
				$hasRoleAtLocation = true;
		case 'perm.view.location':
		case 'perm.create-user.location':
		case 'perm.assign-user.location':
		case 'perm.deassign-user.location':
		case 'perm.view.user':
		case 'perm.edit.user':
			return
				$hasRoleAtLocation =
				$hasRoleAtLocationAtCompany = true;
		case 'perm.delete.user':
			return
				$hasRoleAtLocation =
				$hasRoleAtLocationAtCompany =
				$onlyIfHasRolesAtAllAssignedLocations = true;
		
		//Possible permissions for assets
		case 'perm.create-asset.unassigned':
		case 'perm.create-asset.oneself':
		case 'perm.assign.asset':
			//Superusers only
			return true;
		case 'perm.create-asset.company':
			return
				$hasRoleAtCompany = true;
		case 'perm.create-asset.location':
			return
				$hasRoleAtLocation = true;
		case 'perm.view.asset':
		case 'perm.edit.asset':
		case 'perm.acknowledge.asset':
		case 'perm.delete.asset':
			return
				$hasRoleAtLocation =
				$hasRoleAtCompany =
				$hasRoleAtLocationAtCompany =
				$directlyAssignedToUser = true;
		
		//Possible permissions for other Assetwolf things
		case 'perm.create-schema.unassigned':
		case 'perm.create-command.unassigned':
		case 'perm.create-dataRepeater.unassigned':
		case 'perm.create-trigger.unassigned':
		case 'perm.create-procedure.unassigned':
		case 'perm.create-schedule.unassigned':
		case 'perm.create-scheduledReport.unassigned':
		case 'perm.create-schema.oneself':
		case 'perm.create-command.oneself':
		case 'perm.create-trigger.oneself':
		case 'perm.create-procedure.oneself':
		case 'perm.create-schedule.oneself':
		case 'perm.manage.command':
			//Superusers only
			return true;
		case 'perm.create-schema.company':
		case 'perm.create-command.company':
		case 'perm.create-trigger.company':
		case 'perm.create-procedure.company':
		case 'perm.create-schedule.company':
		case 'perm.edit.scheduledReport':
		case 'perm.delete.scheduledReport':
			return
				$hasRoleAtCompany = true;
		case 'perm.view.schema':
		case 'perm.view.command':
		case 'perm.view.dataRepeater':
		case 'perm.view.trigger':
		case 'perm.view.procedure':
		case 'perm.view.schedule':
		case 'perm.edit.schema':
		case 'perm.edit.command':
		case 'perm.edit.trigger':
		case 'perm.edit.procedure':
		case 'perm.edit.schedule':
		case 'perm.delete.schema':
		case 'perm.delete.command':
		case 'perm.delete.dataRepeater':
		case 'perm.delete.trigger':
		case 'perm.delete.procedure':
		case 'perm.delete.schedule':
			return
				$hasRoleAtCompany =
				$directlyAssignedToUser = true;
		case 'perm.view.scheduledReport':
		    return 
		        $hasRoleAtLocationAtCompany = true;
	    case 'perm.sendCommandTo.asset':
			return
				$hasRoleAtLocation =
				$hasRoleAtCompany =
				$hasRoleAtLocationAtCompany =
				$directlyAssignedToUser = true;
		//Reject any unrecognised permission
		default:
			return false;
	}
}


cms_core::$whitelist[] = 'checkUserCan';
function checkUserCan($action, $target = 'unassigned', $targetId = false, $multiple = false, $authenticatingUserId = -1) {
	
	//If the multiple flag is set, we'll want an array of inputs.
	//If the multiple flag isn't set, then we'll want a singular input.
	//For security reasons I expect the developer to specifically say which of these they are expecting,
	//just to avoid someone passing in an array that the caller then evalulates to true.
	if ($multiple XOR is_array($targetId)) {
		return false;
	}
	
	
	$awIdCol = 'id';
	$isAW =
	$awTable =
	$hasGlobal =
	$directlyAssignedToUser =
	$hasRoleAtCompany =
	$hasRoleAtLocation =
	$hasRoleAtLocationAtCompany =
	$onlyIfHasRolesAtAllAssignedLocations =
	$ASSETWOLF_2_PREFIX =
	$ZENARIO_ORGANIZATION_MANAGER_PREFIX =
	$ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX = false;
	
	
	//If the $authenticatingUserId is not set, default to the current user
	if ($authenticatingUserId === -1) {
		if (isset($_SESSION['extranetUserID'])) {
			$authenticatingUserId = $_SESSION['extranetUserID'];
		} else {
			$authenticatingUserId = false;
		}
	}
	
	//Convenience feature: accept either the target's type, or the target's variable's name.
	//If the name ends with "Id" we'll just strip it off.
	if (substr($target, -2) == 'Id') {
		$target = substr($target, 0, -2);
	}
	
	//The site settings use certain set patterns, e.g. perm.view.company
	$perm = 'perm.'. $action;
	if ($target !== false) {
		$perm .= '.'. $target;
	}
	
	
	//Run some global checks that apply regardless of the $targetId
	//If the action is always allowed (or always disallowed) everywhere, then we can skip some specific logic
	do {
		$isGlobal = true;
		//Only certain combination of settings are valid, if the name of a permissions check is requested that does not exist
		//then return false.
		if (!checkNamedUserPermExists($perm, $directlyAssignedToUser, $hasRoleAtCompany, $hasRoleAtLocation, $hasRoleAtLocationAtCompany, $onlyIfHasRolesAtAllAssignedLocations)) {
			$hasGlobal = false;
			break;
		}
		
		//View permissions have the option to show to everyone, or to always show to any extranet user,
		//except for "view user's details" which always needs at least an extranet login
		if ($action == 'view') {
			switch (setting($perm)) {
				case 'logged_out':
					$hasGlobal = $target != 'user';
					break 2;
				case 'logged_in':
					$hasGlobal = (bool) $authenticatingUserId;
					break 2;
			}
		}
		
		//All other options require a user to be logged in
		if (!$authenticatingUserId) {
			$hasGlobal = false;
			break;
		}
		
		//Check to see if the "by groups" option is checked.
		//(Note that every type of permission has a "by groups" option.)
		//If so, if the extranet user is in any of the listed groups then allow the action.
		if (setting($perm. '.by.group')
		 && ($groupIds = setting($perm. '.groups'))) {
		    
			//Get a row from the users_custom_data table containing the groups indexed by group id
			$userGroups = getUserGroups($authenticatingUserId, true);
		    
			foreach (explodeAndTrim($groupIds, true) as $groupId) {
				if (!empty($userGroups[$groupId])) {
					$hasGlobal = true;
					break 2;
				}
			}
		}
		
		//If we reach this point then we can't do a global check,
		//we must run specific logic on the target id.
		$isGlobal = false;
		
		
		//If this is a check on something for assetwolf, note down which table this is for
		switch ($target) {
			case 'asset':
				$awTable = 'assets';
				$isAW = true;
				break;
			case 'schema':
				$awTable = 'schemas';
				$isAW = true;
				break;
			case 'command':
				$awTable = 'commands';
				$isAW = true;
				break;
			case 'dataRepeater':
				$awTable = 'data_repeaters';
				$awIdCol = 'source_id';
				$isAW = true;
				break;
			case 'trigger':
				$awTable = 'triggers';
				$isAW = true;
				break;
			case 'procedure':
				$awTable = 'procedures';
				$isAW = true;
				break;
			case 'schedule':
				$awTable = 'schedules';
				$isAW = true;
				break;
		}
	
		//Only actually do each check if it's enabled in the site settings
		$hasRoleAtLocation = $hasRoleAtLocation && setting($perm. '.atLocation');
		$hasRoleAtCompany = $hasRoleAtCompany && setting($perm. '.atCompany');
		$hasRoleAtLocationAtCompany = $hasRoleAtLocationAtCompany && setting($perm. '.atLocationAtCompany');
		
		//Look up table-prefixes if needed
		if ($isAW) {
			$ASSETWOLF_2_PREFIX = getModulePrefix('assetwolf_2', true);
		}
	
		if ($hasRoleAtCompany || $hasRoleAtLocationAtCompany || $hasRoleAtLocation) {
			$ZENARIO_ORGANIZATION_MANAGER_PREFIX = getModulePrefix('zenario_organization_manager', true);
		}
		if ($hasRoleAtCompany || $hasRoleAtLocationAtCompany) {
			$ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX = getModulePrefix('zenario_company_locations_manager', true);
		}
	
	} while (false);
	
	//Allow multiple ids to be checked at once in an array
	if ($multiple) {
		//We need an associative array with targetIds as keys.
		
		//This code would check if we have a numeric array and convert it to an associative array.
		//foreach ($targetId as $key => $dummy) {
		//	if ($key === 0) {
		//		$targetId = arrayValuesToKeys($targetId);
		//	}
		//	break;
		//}
		
		//Loop through the array, applying the permissions check to each entry
		foreach ($targetId as $key => &$value) {
			$value = checkUserCanInternal(
				$target, $key, $authenticatingUserId,
				$perm, $isAW, $awTable, $awIdCol, $isGlobal, $hasGlobal,
				$directlyAssignedToUser, $hasRoleAtCompany, $hasRoleAtLocation, $hasRoleAtLocationAtCompany,
				$onlyIfHasRolesAtAllAssignedLocations, $ASSETWOLF_2_PREFIX,
				$ZENARIO_ORGANIZATION_MANAGER_PREFIX, $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX
			);
		}
		
		//Return the resulting array
		return $targetId;
	
	//Otherwise just return true or false
	} else {
		return checkUserCanInternal(
			$target, $targetId, $authenticatingUserId,
			$perm, $isAW, $awTable, $awIdCol, $isGlobal, $hasGlobal,
			$directlyAssignedToUser, $hasRoleAtCompany, $hasRoleAtLocation, $hasRoleAtLocationAtCompany,
			$onlyIfHasRolesAtAllAssignedLocations, $ASSETWOLF_2_PREFIX,
			$ZENARIO_ORGANIZATION_MANAGER_PREFIX, $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX
		);
	}
}

function checkUserCanInternal(
	$target, $targetId, $authenticatingUserId,
	$perm, $isAW, $awTable, $awIdCol, $isGlobal, $hasGlobal,
	$directlyAssignedToUser, $hasRoleAtCompany, $hasRoleAtLocation, $hasRoleAtLocationAtCompany,
	$onlyIfHasRolesAtAllAssignedLocations, $ASSETWOLF_2_PREFIX,
	$ZENARIO_ORGANIZATION_MANAGER_PREFIX, $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX
) {
	
	$companyId =
	$locationId =
	$isUserCheck = false;
	
	//Look to see what type of object this is a check for
	switch ($target) {
		//If this was a check on a company or location, then we can use the provided company or location id
		case 'company':
			$companyId = $targetId;
			break;
		
		case 'location':
			$locationId = $targetId;
			break;
		
		//Some special cases for dealing with user records
		case 'user':
			switch ($perm) {
				case 'perm.view.user':
					//Users can always view their own data, this is hard-coded
					if ($targetId == $authenticatingUserId) {
						return true;
					}
					break;
		
				case 'perm.edit.user':
					//Users can always edit their own data if the site setting to do so is enabled
					if ($targetId == $authenticatingUserId && setting('perm.edit.oneself')) {
						return true;
					}
					break;
		
				case 'perm.delete.user':
					//Stop users from deleting themslves
					if ($targetId == $authenticatingUserId) {
						return false;
					}
					break;
			}
			$isUserCheck = true;
			break;
	}
	
	//Apart from the above special cases, some rules can be checked globally.
	//E.g. someone might be in the super-users group and can view or edit anything.
	//If so then there's no need to check anything for this specific id.
	if ($isGlobal) {
		return $hasGlobal;
	
	//The rest of the checks require the specific id of something to check,
	//return false if one wasn't provided.
	} elseif (!$targetId) {
		return false;
	}
    
	//If this is a check for something in Assetwolf, try to load the company id or location id
	//that it is assigned to.
	if ($isAW && ($ASSETWOLF_2_PREFIX = getModulePrefix('assetwolf_2', true))) {
		if ($row = sqlFetchRow("
			SELECT owner_type, owner_id
			FROM ". DB_NAME_PREFIX. $ASSETWOLF_2_PREFIX. $awTable. "
			WHERE ". $awIdCol. " = ". (int) $targetId
		)) {
			switch ($row[0]) {
				//Note down the company or location id
				case 'company':
					$companyId = $row[1];
					break;
				case 'location':
					$locationId = $row[1];
					break;
				
				//If the asset (or whatever) was directly assigned to a user, then we can run
				//that check now
				case 'user':
					return $directlyAssignedToUser && ($authenticatingUserId == $row[1]) && setting($perm. '.directlyAssigned');
			}
		} else {
			return false;
		}
	}
	
	//The *thing* is assigned to a company, and they have [ANY/the specified] role at ANY location in that company
	if ($hasRoleAtCompany && $companyId && $ZENARIO_ORGANIZATION_MANAGER_PREFIX && $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX) {
		$sql = "
			SELECT 1
			FROM ". DB_NAME_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link AS urll
			". ($onlyIfHasRolesAtAllAssignedLocations? "LEFT" : "INNER"). " JOIN ". DB_NAME_PREFIX. $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX. "company_location_link AS cll
			   ON cll.company_id = ". (int) $companyId. "
			  AND urll.location_id = cll.location_id
			WHERE urll.user_id = ". (int) $authenticatingUserId;
		
		if ($roleId = setting($perm. '.atCompany.role')) {
			$sql .= "
			  AND role_id = ". (int) $roleId;
		}
		$sql .= "
			LIMIT 1";
		
		if (sqlFetchRow($sql)) {
			return true;
		}
	}
	
	//The *thing* is assigned to a location at which they have [ANY/the specified] role
	if ($hasRoleAtLocation && ($locationId || $isUserCheck) && $ZENARIO_ORGANIZATION_MANAGER_PREFIX) {
		
		$roleId = setting($perm. '.atLocation.role');
		
		//Check the case where something is assigned to just one location
		if ($locationId && !$isUserCheck) {
			$sql = "
				SELECT 1
				FROM ". DB_NAME_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link
				WHERE location_id = ". (int) $locationId. "
				  AND user_id = ". (int) $authenticatingUserId;
		
			if ($roleId) {
				$sql .= "
				  AND role_id = ". (int) $roleId;
			}
			$sql .= "
				LIMIT 1";
			
			if (sqlFetchRow($sql)) {
				return true;
			}
		
		//Handle checks on users, who can be assigned to multiple locations
		} elseif ($isUserCheck) {
			//Check if the target user is at any location that the current user is at
			//Also, if we're following ONLY logic, check that they are not at any other company
			$sql = "
				SELECT ". ($onlyIfHasRolesAtAllAssignedLocations? "cur.user_id IS NOT NULL" : "1"). "
				FROM ". DB_NAME_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link AS tar
				". ($onlyIfHasRolesAtAllAssignedLocations? "LEFT" : "INNER"). "
				JOIN ". DB_NAME_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link AS cur
				   ON cur.user_id = ". (int) $authenticatingUserId;
		
			if ($roleId) {
				$sql .= "
				  AND cur.role_id = ". (int) $roleId;
			}
			
			$sql .= "
				WHERE tar.user_id = ". (int) $targetId. "
				". ($onlyIfHasRolesAtAllAssignedLocations? "ORDER BY 1" : ""). "
				LIMIT 1";
			
			if (($row = sqlFetchRow($sql)) && ($row[0])) {
				return true;
			}
		}
	}
	
	//The *thing* is assigned to a location at a company, and they have [ANY/the specified] role at ANY location in that company
	if ($hasRoleAtLocationAtCompany && ($locationId || $isUserCheck) && $ZENARIO_ORGANIZATION_MANAGER_PREFIX && $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX) {
		
		$roleId = setting($perm. '.atLocationAtCompany.role');
		
		//Check the case where something is assigned to just one location
		if ($locationId && !$isUserCheck) {
			//Look up the company id up from the location
			$sql = "
				SELECT company_id
				FROM ". DB_NAME_PREFIX. $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX. "company_location_link
				WHERE location_id = ". (int) $locationId;
			if ($companyId = sqlFetchValue($sql)) {
				
				//Check if the current user is at any location in this company
				$sql = "
					SELECT 1
					FROM ". DB_NAME_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link AS urll
					INNER JOIN ". DB_NAME_PREFIX. $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX. "company_location_link AS cll
					   ON cll.company_id = ". (int) $companyId. "
					  AND urll.location_id = cll.location_id
					WHERE urll.user_id = ". (int) $authenticatingUserId;
		
				if ($roleId) {
					$sql .= "
					  AND role_id = ". (int) $roleId;
				}
				$sql .= "
					LIMIT 1";
		
				if (sqlFetchRow($sql)) {
					return true;
				}
			}
		
		//Handle users, who can be assigned to multiple locations
		} elseif ($isUserCheck) {
			//Look up every company that the current user is assigned to
			$sql = "
				SELECT DISTINCT company_id
				FROM ". DB_NAME_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link AS urll
				INNER JOIN ". DB_NAME_PREFIX. $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX. "company_location_link AS cll
				   ON urll.location_id = cll.location_id
				WHERE urll.user_id = ". (int) $authenticatingUserId;
		
			if ($roleId) {
				$sql .= "
				  AND urll.role_id = ". (int) $roleId;
			}
			
			if (($companyIds = sqlFetchValues($sql)) && (!empty($companyIds))) {
				//Check if the target user is at any of these companies
				//Also, if we're following ONLY logic, check that they are not at any other company
				$sql = "
					SELECT ". ($onlyIfHasRolesAtAllAssignedLocations? "cll.company_id IS NOT NULL" : "1"). "
					FROM ". DB_NAME_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link AS urll
					". ($onlyIfHasRolesAtAllAssignedLocations? "LEFT" : "INNER"). "
					JOIN ". DB_NAME_PREFIX. $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX. "company_location_link AS cll
					   ON cll.company_id IN (". inEscape($companyIds, true). ")
					  AND urll.location_id = cll.location_id
					WHERE urll.user_id = ". (int) $targetId. "
					". ($onlyIfHasRolesAtAllAssignedLocations? "ORDER BY 1" : ""). "
					LIMIT 1";
			
				if (($row = sqlFetchRow($sql)) && ($row[0])) {
					return true;
				}
			}
		}
	}
	
	//If no rule matches, deny access
	return false;
}

//Shortcut function for creating things, which has a slightly less confusing syntax
cms_core::$whitelist[] = 'checkUserCanCreate';
function checkUserCanCreate($thingToCreate, $assignedTo = 'unassigned', $assignedToId = false, $multiple = false, $authenticatingUserId = -1) {
	
	//Convenience feature: accept either the thing's type, or the thing's variable's name.
	//If the name ends with "Id" we'll just strip it off.
	if (substr($thingToCreate, -2) == 'Id') {
		$thingToCreate = substr($thingToCreate, 0, -2);
	}
	
	return checkUserCan('create-'. $thingToCreate, $assignedTo, $assignedToId, $multiple, $authenticatingUserId);
}
