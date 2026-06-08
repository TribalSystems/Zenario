<?php
/*
 * Copyright (c) 2026, Tribal Limited
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

use ZxcvbnPhp\Zxcvbn;

class userAdm {



	//An API function to check if a user is valid.
	//To avoid code duplication it's implemented by calling the \ze\userAdm::save() function
	//with $doSave set to false.
	public static function isInvalid($values, $id = false) {
		return \ze\userAdm::save($values, $id, false);
	}
	
	public static function generateScreenName($details) {
		// This function will only use the first and/or last name to generate a screen name.
		// If neither is provided, it will return a blank string.
		// Please note: it is possible that the suggestion may not be a unique screen name.
		// It is up to the admin to make it unique.
		$baseIdentifier = '';
		
		if (\ze::setting('user_use_screen_name') && !empty($details)) {
			$firstName = (!empty($details['first_name']) ? \ze\ring::trimNonWordCharactersUnicode($details['first_name']) : '');
			$lastName = (!empty($details['last_name']) ? \ze\ring::trimNonWordCharactersUnicode($details['last_name']) : '');
			
			if ($firstName) {
				if ($lastName) {
					$baseIdentifier =
						mb_substr($firstName, 0, (int) \ze::setting('user_chars_from_first_name') ?: 99).
						mb_substr($lastName, 0, (int) \ze::setting('user_chars_from_last_name') ?: 99);
				} else {
					$baseIdentifier =
						mb_substr($firstName, 0, (int) \ze::setting('user_chars_from_name') ?: 99);
				}
			} elseif ($lastName) {
				$baseIdentifier =
					mb_substr($lastName, 0, (int) \ze::setting('user_chars_from_name') ?: 99);
			}
		}
		
		return $baseIdentifier;
	}

	public static function generateIdentifier($userId) {
		//Look up details on this user if not provided
		$details = \ze\row::get('users', ['screen_name', 'first_name', 'last_name', 'email', 'status'], $userId);
	
		$baseIdentifier = '';
		$firstName = (!empty($details['first_name']) ? \ze\ring::trimNonWordCharactersUnicode($details['first_name']) : '');
		$lastName = (!empty($details['last_name']) ? \ze\ring::trimNonWordCharactersUnicode($details['last_name']) : '');
		$email = $details['email'];
	
		//Create a "Base identifier" for this user based on their details
		//If first name and/or last name provided, use these
		//Else use screen name (if the feature is enabled and a screen name is entered)
		//Otherwise, use "User", to which a number will be added
		
		if (\ze::setting('user_use_screen_name') && $details['screen_name']) {
			$baseIdentifier = $details['screen_name'];
		
		} elseif ($firstName) {
			if ($lastName) {
				$baseIdentifier =
					mb_substr($firstName, 0, (int) \ze::setting('user_chars_from_first_name') ?: 99).
					mb_substr($lastName, 0, (int) \ze::setting('user_chars_from_last_name') ?: 99);
			} else {
				$baseIdentifier =
					mb_substr($firstName, 0, (int) \ze::setting('user_chars_from_name') ?: 99);
			}
		} elseif ($lastName) {
			$baseIdentifier =
				mb_substr($lastName, 0, (int) \ze::setting('user_chars_from_name') ?: 99);
		} else {
			// Check if this is a user or contact.
			// Add their record ID to the base identifier.
			if ($details['status'] == 'contact') {
				$baseIdentifier = 'Contact' . (int) $userId;
			} else {
				$baseIdentifier = 'User' . (int) $userId;
			}
		}
		
		if (strlen($baseIdentifier) > 50) {
			//Please note: while this logic limits the identifier to 50 characters,
			//the actual column size is 55. That accounts for up to a 3 digit number
			//being added if the identifier is not unique.
			$baseIdentifier = mb_strcut($baseIdentifier, 0, 50, 'UTF-8');
		}
	
		//Attempt to generate a unique indentifier.
		// In case of collissions, add a random number and check for uniqueness.
		$uniqueIdentifier = $baseIdentifier;
		if (!\ze\row::exists('users', ['identifier' => $uniqueIdentifier, 'id' => ['!' => $userId]])) {
			return $uniqueIdentifier;
		} else {
			$identifierCollision = true;
			do {
				$number = rand(0, 999);
				if (!\ze\row::exists('users', ['identifier' => $uniqueIdentifier . $number, 'id' => ['!' => $userId]])) {
					$identifierCollision = false;
					$uniqueIdentifier = $uniqueIdentifier . $number;
				}
			} while ($identifierCollision);
			
			return $uniqueIdentifier;
		}
	}

	public static function nextScreenName() {
		$sql = "
			SELECT IFNULL(MAX(id), 0) + 1
			FROM ". DB_PREFIX. "users";
		$result = \ze\sql::select($sql);
		$row = \ze\sql::fetchRow($result);
	
		$prefix = 'User_';
	
		return $prefix. $row[0];
	}

	//An API function to save a user to the database.
	//It will only save it if it passes a validation check; if it is not valid then this
	//function will return an error object.
	public static function save($values, $id = false, $doSave = true, $convertContactToExtranetUser = false, $markNewThingsInSession = false) {
		//First, validate the submission.
		$e = new \ze\error();
	
		//Validate the screen_name field if it is set.
		//(Always validate it when creating a new user.)
		if (!empty($values['screen_name'])) {
			//...has no special characters...
			if (!\ze\ring::validateScreenName($values['screen_name'])) {
				$e->add('screen_name', '_ERROR_SCREEN_NAME_USES_INVALID_CHARACTERS');
			//...and is not already taken by a different row...
			} elseif (\ze\row::exists('users', ['screen_name' => $values['screen_name'], 'id' => ['!' => $id]])) {
				$e->add('screen_name', '_ERROR_SCREEN_NAME_IN_USE');
			//...and is not too long.
			} elseif (strlen($values['screen_name']) > 50) {
				$e->add('screen_name', '_ERROR_SCREEN_NAME_TOO_LONG');
			}
		}
	
	
		//Ensure salutation, first_name and last_name are not too long
		if (!empty($values['salutation'])) {
			if (strlen($values['salutation']) > 25) {
				$e->add('salutation', '_ERROR_SALUTATION_TOO_LONG');
			}
		}
		if (!empty($values['first_name'])) {
			if (strlen($values['first_name']) > 100) {
				$e->add('first_name', '_ERROR_FIRST_NAME_TOO_LONG');
			}
		}
		if (!empty($values['last_name'])) {
			if (strlen($values['last_name']) > 100) {
				$e->add('last_name', '_ERROR_LAST_NAME_TOO_LONG');
			}
		}
	
	
		$firstNameLastNameOrScreenNameHasChanged = false;
		if ($id) {
			//Check whether the first name, last name and screen name have changed.
			//If they have not, remember this for later and preserve the existing identifier.
			if ($doSave) {
				$existingUserDetails = \ze\row::get('users', ['first_name', 'last_name', 'screen_name'], $id);
				if (
					!empty($existingUserDetails)
					&& isset($values['first_name']) && isset($values['last_name'])
					&& (
						$existingUserDetails['first_name'] != $values['first_name']
						|| $existingUserDetails['last_name'] != $values['last_name']
						|| (\ze::setting('user_use_screen_name') && isset($values['screen_name']) && $existingUserDetails['screen_name'] != $values['screen_name'])
					)
				) {
					$firstNameLastNameOrScreenNameHasChanged = true;
				}
			}
		} else {
			$values['created_date'] = \ze\date::now();
			if (empty($values['creation_method_note'])) {
				if (\ze\admin::id()) {
					$values['creation_method_note'] = 'Created by admin ' . \ze\row::get('admins', 'username', \ze\admin::id());
				} elseif (\ze\user::id()) {
					$values['creation_method_note'] = 'Created by user ' . \ze\row::get('users', 'identifier', \ze\user::id());
				}
			}
		}
	
		//Validate the email field if it is not empty.
		if (!empty($values['email'])) {
			if (strlen($values['email']) > 100 || !\ze\ring::validateEmailAddress($values['email'])) {
				$e->add('email', '_ERROR_EMAIL_ADDRESS_INVALID');
		
			//...and is not already taken by a different row.
			} else {
				if ($convertContactToExtranetUser) {
					if ($exsitingUser = \ze\row::get('users', ['id','status'], ['email' => $values['email']])) {
						if ($exsitingUser['status'] == "contact") {
							$id = $exsitingUser['id'];
						} else {
							$e->add('email', '_ERROR_EMAIL_ADDRESS_IN_USE');
						}
					}
				} elseif (\ze\row::exists('users', ['email' => $values['email'], 'id' => ['!' => $id]])) {
					$e->add('email', '_ERROR_EMAIL_ADDRESS_IN_USE');
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
			if (isset($values['email'])) {
				$values['email_domain'] = '';
				if (!empty($values['email'])) {
					$values['email_domain'] = self::extractEmailDomainFromEmailAddress($values['email']);
				}
			}
			$password = false;
			if (isset($values['password'])) {
				$password = $values['password'];
				unset($values['password']);
			}
		
			if ($id && !empty($values['status']) && $values['status'] == 'contact') {
				$values['parent_id'] = 0;
				$sql = '
					UPDATE ' . DB_PREFIX . 'users u
					INNER JOIN ' . DB_PREFIX . 'users u2
						ON u.parent_id = u2.id
					SET u.parent_id = 0
					WHERE u2.id = ' . (int)$id;
				\ze\sql::update($sql);
			}
		
			//Save the details to the database
			$newId = \ze\row::set('users', $values, $id, false, false, $markNewThingsInSession);
		
			//Generate an identifier for new records and/or records where the first name, last name and screen name have changed.
			//If editing a user/contact, and the first name, last name and screen name have not changed,
			//there is no need to regenerate the identifier.
			if (!$id || $firstNameLastNameOrScreenNameHasChanged) {
				$identifier = \ze\userAdm::generateIdentifier($newId);
				\ze\row::update('users', ['identifier' => $identifier], $newId);
			}
		
			if ($password !== false) {
				\ze\userAdm::setPassword($newId, $password);
			}
		
			//Send a signal to let other Modules know this event has happened
			if ($id) {
				\ze\module::sendSignal(
					'eventUserModified',
					['id' => $id]);
		
			} else {
				\ze\module::sendSignal(
					'eventUserCreated',
					['id' => $newId]);
			}
		
			//Return the primary id from the database to the caller
			return $newId;
		}
	}

	public static function convertToContact($userId) {
		$values = [
			'screen_name' => '',
			'password' => false,
			'password_salt' => null,
			'password_needs_changing' => 0,
			'reset_password_time' => null,
			'status' => 'contact'
		];
		
		\ze\row::update('users', $values, $userId);
	}

	public static function createPassword() {
		$numbers = "0,1,2,3,4,5,6,7,8,9";
		$letters = "a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z";
		$symbols = "!,#,$,%,<,>,(,),*,+,-,@,?,{,},_";
	
		$lowercase = explode(',', $letters);
		$uppercase = explode(',', strtoupper($letters));
		$symbolsArray = explode(',', $symbols);
		$numbersArray = explode(',', $numbers);
	
		$password = "";

		$passwordMinScore = (int) \ze::setting('min_extranet_user_password_score');
		$passwordMinLength = max(6, (int) \ze::setting('min_extranet_user_password_length'));
		
		/*
			Currently the password length can be between 6 and 32 characters.
			Min char length to match a score:
				- score 4: min length 12
				- score 3: min length 10
				- score 2: min length 8
					(but 8 is just the bare minimum pass for score 2 - the password notifier message would be displayed in amber
					and say e.g. "Password is too easy to guess")
				- score 1: min length 6
			
			For scores 4 and 3, if the min length site setting is less than the min above (e.g. old data),
			or Zenario is not installed yet, then fall back on the values above to generate	a password that matches the score.
			
			For score 2, while an 8 char password would work, use 9 or above to generate a password that exceeds score 2.
			This is to avoid a situation where a generated password shows "Password is too easy to guess" in amber.
		*/
		
		if ($passwordMinScore == 4 && $passwordMinLength < 12) {
			$passwordMinLength = 12;
		} elseif ($passwordMinScore == 3 && $passwordMinLength < 10) {
			$passwordMinLength = 10;
		} elseif ($passwordMinScore == 2 && $passwordMinLength < 9) {
			$passwordMinLength = 9;
		} elseif ($passwordMinScore == 1 && $passwordMinLength < 6) {
			$passwordMinLength = 6;
		}
		
		$passwordLength = $passwordMinLength;
	
		$passwordCharacters = [];
		$numIterations = 0;
		$passwordEasilyGuessable = true;
		
		do {
			$numIterations++;
			$password = "";
			$passwordLength = $passwordMinLength;

			if ($passwordLength) {
				//Build an array of all required characters. It will be used later on to generate a password.
				$passwordCharacters = array_merge($passwordCharacters, $uppercase);
				$passwordCharacters = array_merge($passwordCharacters, $lowercase);
				$passwordCharacters = array_merge($passwordCharacters, $numbersArray);
				
				if (\ze::setting('autogenerated_passwords_should_contain_symbols')) {
					$passwordCharacters = array_merge($passwordCharacters, $symbolsArray);
				}
			
				if ($passwordCharacters) {
					for ($i = 1; $i <= $passwordLength; $i++) {
						$password .=\ze\userAdm::addCharacterToPassword($passwordCharacters);
					}
					
					$password = str_shuffle($password);
				}
			}

			if (!$password) {
				$password = \ze\ring::random($passwordLength);
			}

			//Check if the password is easily guessable or not.
			$zxcvbn = new \ZxcvbnPhp\Zxcvbn();
			$result = $zxcvbn->passwordStrength($password);

			if ($result && !empty($result['score'])) {
				if (\ze::in($result['score'], 3, 4)) {
					if ($passwordMinScore == 3) {
						$passwordEasilyGuessable = false;
					}
				}
			}

		} while ($numIterations <= 10 && $passwordEasilyGuessable);

		return $password;
	}
	
	public static function addCharacterToPassword($charactersArray) {
		$length = count($charactersArray) - 1;
		$randomNumber = mt_rand(0, $length);
		return $charactersArray[$randomNumber];
	}

	public static function setPassword($userId, $password, $needsChanging = -1) {
	
		//Generate a random salt for this password. If someone gets hold of the encrypted value of
		//the password in the database, having a salt on it helps to stop dictonary attacks.
		$salt = \ze\ring::random(8);
		$password = \ze\user::hashPassword($salt, $password);
	
	
		$details = ['password' => $password, 'password_salt' => $salt];
	
		if ($needsChanging !== -1) {
			$details['password_needs_changing'] = $needsChanging;
		}
		$details['reset_password_time'] = \ze\date::now();
	
		\ze\row::update('users', $details, $userId);
		//Adding hash
		\ze\userAdm::updateHash($userId);
	}
	
	public static function suspend($userId, $adminFacing = false) {
		$cols = [];
		
		if ($adminFacing) {
			\ze\admin::setLastUpdated($cols, $creating = false);
		} else {
			\ze\user::setLastUpdated($cols, $creating = false);
		}
		
		$cols['modified_date'] = $cols['last_edited'];
		$cols['suspended_date'] = $cols['last_edited'];
		unset($cols['last_edited']);
		$cols['status'] = 'suspended';
		
		\ze\row::update('users', $cols, $userId);
		
		\ze\module::sendSignal("eventUserStatusChange", ["userId" => $userId, "status" => "suspended"]);
	}

	public static function delete($userId, $deleteAllData = false) {
		\ze\module::sendSignal('eventUserDeleted', [$userId, $deleteAllData]);
	
		$deleteUserAccount = true;
		if (\ze\module::inc('zenario_user_hierarchy')) {
			$userHasChildren = \zenario_user_hierarchy::getUserChildrenIds($userId);
			if ($userHasChildren) {
				$deleteUserAccount = false;
			}
		}
		
		if ($deleteUserAccount) {
			\ze\row::delete('users', $userId);
		} else {
			\ze\row::set('users', ['first_name' => 'Deleted', 'last_name' => 'User', 'email' => ''], ['id' => $userId]);
		}
		
		\ze\row::delete('users_custom_data', ['user_id' => $userId]);
		\ze\row::delete('user_country_link', ['user_id' => $userId]);
	
		if ($dataset = \ze\dataset::details('users')) {
			\ze\row::delete('custom_dataset_values_link', ['dataset_id' => $dataset['id'], 'linking_id' => $userId]);
		}
	
		\ze\contentAdm::deleteUnusedImagesByUsage('user');
		
		if ($deleteAllData) {
			//Delete user signin log
			$sql = ' 
				DELETE FROM '. DB_PREFIX. 'user_signin_log
				WHERE user_id = ' . (int)$userId;
			\ze\sql::update($sql);
			
			//Delete user content access log
			$sql = ' 
				DELETE FROM '. DB_PREFIX. 'user_content_accesslog 
				WHERE user_id = ' . (int)$userId;
			\ze\sql::update($sql);
		}
	}

	public static function updateHash($userId) {
		$emailAddress = \ze\row::get('users', 'email', $userId);
		$sql = "
			UPDATE ". DB_PREFIX. "users 
			SET hash_verify_email = '". \ze\escape::asciiInSQL(\ze\userAdm::createHash()). "'
			WHERE id = ". (int) $userId;
		\ze\sql::update($sql, false, false);
	}
	
	public static function createHash() {
		return \ze\ring::randomMultiDigitCode() . time();
	}
	
	public static function setVerificationExpiryDate($userId) {
		$verificationEmailExpiryPeriod = (int) \ze::setting('verification_email_expiry_period');
		$dateTimeNow = \ze\date::new(\ze\date::now());
		$expiryDate = $dateTimeNow->modify('+' . $verificationEmailExpiryPeriod . ' hours')->format('Y-m-d H::i::s');
		\ze\row::update('users', ['hash_verify_email_expiry' => \ze\escape::sql($expiryDate)], ['id' => $userId]);
	}
	
	public static function hasVerificationExpired($userId) {
		$user = \ze\row::get('users', ['hash_verify_email_expiry'], ['id' => $userId]);
		
		if ($user) {
			$timestampNow = \ze\date::new(\ze\date::now())->getTimestamp();
			$expiryTimestamp = \ze\date::new($user['hash_verify_email_expiry'])->getTimestamp();
			
			if ($timestampNow <= $expiryTimestamp) {
				return false;
			}
		}
		
		return true;
	}
	
	public static function extractEmailDomainFromEmailAddress($emailAddress) {
		$emailAddressParts = explode('@', $emailAddress, 2);
		if (!empty($emailAddressParts) && !empty($emailAddressParts[1])) {
			return '@' . $emailAddressParts[1];
		}
		
		return '';
	}
}
