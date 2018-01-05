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


namespace ze;

class userAdm {



	//An API function to check if a user is valid.
	//To avoid code duplication it's implemented by calling the \ze\userAdm::save() function
	//with $doSave set to false.
	//Formerly "isInvalidUser()"
	public static function isInvalid($values, $id = false) {
		return \ze\userAdm::save($values, $id, false);
	}


	//Formerly "generateUserIdentifier()"
	public static function generateIdentifier($userId, $details = array()) {
		//Look up details on this user if not provided
		if (empty($details)
		 || !isset($details['email'])
		 || !isset($details['last_name'])
		 || !isset($details['first_name'])
		 || !isset($details['screen_name'])) {
			$details = \ze\row::get('users', array('screen_name', 'first_name', 'last_name', 'email'), $userId);
		}
	
		$baseIdentifier = '';
		$firstName = $details['first_name'];
		$lastName = $details['last_name'];
		$email = $details['email'];
	
		//Create a "Base identifier" for this user based on their details
		if (!\ze::setting('user_use_screen_name')
		 || !($baseIdentifier = $details['screen_name'])) {
			$firstName = \ze\ring::trimNonWordCharactersUnicode($firstName);
			$lastName = \ze\ring::trimNonWordCharactersUnicode($lastName);
			if ($firstName || $lastName) {
				$baseIdentifier = $firstName. $lastName;
			} elseif (($emailArray = explode('@', $email)) && ($email = \ze\ring::trimNonWordCharactersUnicode($emailArray[0]))) {
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
		\ze\row::cacheTableDef(DB_NAME_PREFIX. 'users');
		if (!\ze::$dbCols[DB_NAME_PREFIX. 'users']['identifier']->encrypted) {
			//Attempt to generate a unique indentifier
	
			// Get all current identifiers from all linked sites
			$identifiers = array();
			if (file_exists(CMS_ROOT. 'zenario_usersync_config.php')) {
				require CMS_ROOT. 'zenario_usersync_config.php';
				$thisIsHub =
					$hub['DBHOST'] == DBHOST
				 && $hub['DBNAME'] == DBNAME;
		
				$identifiers = \ze\userAdm::similarIdentifiers($baseIdentifier, $thisIsHub, $hub, $satellites);
				\ze\db::connectLocal();
			}
	
			// Get similar identifiers from this site
			$sql = '
				SELECT id, identifier 
				FROM '.DB_NAME_PREFIX.'users
				WHERE identifier LIKE "'.\ze\escape::sql($baseIdentifier).'%"
				AND id != '.(int)$userId;
			$result = \ze\sql::select($sql);
			while ($user = \ze\sql::fetchAssoc($result)) {
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
			if (!\ze\row::exists('users', ['identifier' => $uniqueIdentifier, 'id' => ['!' => $userId]])) {
				return $uniqueIdentifier;
			} else {
				$userId = (string)$userId;
				for ($i = 1; $i <= strlen($userId); $i++) {
					$userNumber = substr($userId, -($i));
					$baseIdentifier = substr($baseIdentifier, 0, (50 - ($i + 1)));
					$uniqueIdentifier = $baseIdentifier . '-' . $userNumber;
					if (!\ze\row::exists('users', ['identifier' => $uniqueIdentifier, 'id' => ['!' => $userId]])) {
						return $uniqueIdentifier;
					}
				}
				$uniqueIdentifier .= rand(0, 99);
				return $uniqueIdentifier;
			}
		}
	}

	//N.b. this function won't work if identifier is encrypted on a site!
	//Formerly "getSimilarIdentifiers()"
	public static function similarIdentifiers($screenName, $thisIsHub, $hub, $satellites) {
		$identifiers = array();
		$DBHost = DBHOST;
		$DBName = DBNAME;
		// if not thisIsHub, return hubs identifiers
		if (!$thisIsHub) {
			if ($dbSelected = \ze\db::connect($hub['DBHOST'], $hub['DBNAME'], $hub['DBUSER'], $hub['DBPASS'], ($hub['DBPORT'] ?? false))) {
				\ze::$lastDB = $dbSelected;
				\ze::$lastDBHost = $hub['DBHOST'];
				\ze::$lastDBName = $hub['DBNAME'];
				\ze::$lastDBPrefix = $hub['DB_NAME_PREFIX'];
			
				$sql = '
					SELECT id, identifier
					FROM '. $hub['DB_NAME_PREFIX']. 'users
					WHERE identifier LIKE "'.\ze\escape::sql($screenName).'%"';
			
				$result = \ze\sql::select($sql);
				while ($user = \ze\sql::fetchAssoc($result)) {
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
					if ($dbSelected = \ze\db::connect($satellite['DBHOST'], $satellite['DBNAME'], $satellite['DBUSER'], $satellite['DBPASS'], ($satellite['DBPORT'] ?? false))) {
						\ze::$lastDB = $dbSelected;
						\ze::$lastDBHost = $satellite['DBHOST'];
						\ze::$lastDBName = $satellite['DBNAME'];
						\ze::$lastDBPrefix = $satellite['DB_NAME_PREFIX'];
					
						$sql = '
							SELECT id, identifier
							FROM '. $satellite['DB_NAME_PREFIX']. 'users
							WHERE identifier LIKE "'.\ze\escape::sql($screenName).'%"';
					
						$result = \ze\sql::select($sql);
						while ($user = \ze\sql::fetchAssoc($result)) {
							$identifiers[strtoupper($user['identifier'])] = $user['id'];
						}
					}
				}
			}
		}
		return $identifiers;
	}

	//Formerly "getNextScreenName()"
	public static function nextScreenName() {
		$sql = "
			SELECT IFNULL(MAX(id), 0) + 1
			FROM ". DB_NAME_PREFIX. "users";
		$result = \ze\sql::select($sql);
		$row = \ze\sql::fetchRow($result);
	
		$prefix = 'User_';
	
		//Default hub users to a different name pattern to try and make collisions less likely
		if (file_exists(CMS_ROOT. 'zenario_usersync_config.php') && \ze\module::inc('zenario_users')) {
			if (zenario_users::thisIsHub()) {
				$prefix = 'Hub_User_';
			}
		}
	
		return $prefix. $row[0];
	}

	//An API function to save a user to the database.
	//It will only save it if it passes a validation check; if it is not valid then this
	//function will return an error object.
	//Formerly "saveUser()"
	public static function save($values, $id = false, $doSave = true, $convertContactToExtranetUser = false) {
		//First, validate the submission.
		$e = new \ze\error();
	
		//Validate the screen_name field if it is set.
		//(Always validate it when creating a new user.)
		if (!empty($values['screen_name'])) {
			//...has no special characters...
			if (!\ze\ring::validateScreenName($values['screen_name'])) {
				$e->add('screen_name', '_ERROR_SCREEN_NAME_INVALID');
			//...and is not already taken by a different row.
			} elseif (\ze\row::exists('users', array('screen_name' => $values['screen_name'], 'id' => array('!' => $id)))) {
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
			$values['created_date'] = \ze\date::now();
		}
		$values['modified_date'] = \ze\date::now();
	
		//Validate the email field if it is not empty.
		if (!empty($values['email'])) {
			if (!\ze\ring::validateEmailAddress($values['email'])) {
				$e->add('email', '_ERROR_EMAIL_INVALID');
		
			//...and is not already taken by a different row.
			} else {
				if ($convertContactToExtranetUser) {
					if ($exsitingUser = \ze\row::get('users', array('id','status'), array('email' => $values['email']))) {
						if ($exsitingUser['status'] == "contact") {
							$id = $exsitingUser['id'];
						} else {
							$e->add('email', '_ERROR_EMAIL_NAME_IN_USE');
						}
					}
				} elseif (\ze\row::exists('users', array('email' => $values['email'], 'id' => array('!' => $id)))) {
					$e->add('email', '_ERROR_EMAIL_NAME_IN_USE');
				}
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
				\ze\sql::update($sql);
			}
		
			//Save the details to the database
			$newId = \ze\row::set('users', $values, $id);
		
			$identifier = \ze\userAdm::generateIdentifier($newId);
			\ze\row::update('users', array('identifier' => $identifier), $newId);
		
			if ($password !== false) {
				\ze\userAdm::setPassword($newId, $password);
			}
		
			//Send a signal to let other Modules know this event has happened
			if ($id) {
				\ze\module::sendSignal(
					'eventUserModified',
					array('id' => $id));
		
			} else {
				\ze\module::sendSignal(
					'eventUserCreated',
					array('id' => $newId));
			}
		
			//Return the primary id from the database to the caller
			return $newId;
		}
	}



	//Formerly "createPassword()"
	public static function createPassword() {
	
		$numbers = "0,1,2,3,4,5,6,7,8,9";
		$letters = "a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z";
		$symbols = "!,#,$,%,<,>,(,),*,+,-,@,?,{,},_";
	
		$lowercase = explode(',',$letters);
		$uppercase = explode(',',strtoupper($letters));
		$symbolsArray = explode(',',$symbols);
		$numbersArray = explode(',',$numbers);
	
		$password = "";
		$passwordLength = max(5, (int) \ze::setting('min_extranet_user_password_length'));
	
		$passwordCharacters = array();
	
		if($passwordLength){
	
			if(\ze::setting('a_z_uppercase_characters')){
				$passwordCharacters = array_merge($passwordCharacters,$uppercase);
			}
		
			if(\ze::setting('a_z_lowercase_characters')){
				$passwordCharacters = array_merge($passwordCharacters,$lowercase);
			}
		
			if(\ze::setting('0_9_numbers_in_user_password')){
				$passwordCharacters = array_merge($passwordCharacters,$numbersArray);
			}
		
			if(\ze::setting('symbols_in_user_password')){
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
			return \ze\ring::random($passwordLength);
		}	
	}

	//Formerly "setUsersPassword()"
	public static function setPassword($userId, $password, $needsChanging = -1) {
	
		//Generate a random salt for this password. If someone gets hold of the encrypted value of
		//the password in the database, having a salt on it helps to stop dictonary attacks.
		$salt = \ze\ring::random(8);
		$password = \ze\user::hashPassword($salt, $password);
	
	
		$details = array('password' => $password, 'password_salt' => $salt);
	
		if ($needsChanging !== -1) {
			$details['password_needs_changing'] = $needsChanging;
		}
		$details['reset_password_time'] = \ze\date::now();
	
		\ze\row::update('users', $details, $userId);
		//Adding hash
		\ze\userAdm::updateHash($userId);
	}

	//Formerly "deleteUser()"
	public static function delete($userId) {
		\ze\module::sendSignal('eventUserDeleted', array('userId' => $userId));
	
		\ze\row::delete('users', $userId);
		\ze\row::delete('users_custom_data', array('user_id' => $userId));
	
		if ($dataset = \ze\dataset::details('users')) {
			\ze\row::delete('custom_dataset_values_link', array('dataset_id' => $dataset['id'], 'linking_id' => $userId));
		}
	
		\ze\contentAdm::deleteUnusedImagesByUsage('user');
	}

	//Formerly "updateUserHash()"
	public static function updateHash($userId) {
	
		$emailAddress = \ze\row::get('users', 'email', $userId);
	
		$sql = "
			UPDATE ". DB_NAME_PREFIX. "users 
			SET hash = '". \ze\escape::sql(\ze::hash64($userId. '-'. date('Yz'). '-'. \ze\link::primaryDomain(). '-'. $emailAddress)). "'
			WHERE id = ". (int) $userId;
		\ze\sql::update($sql, false, false);
	}
}