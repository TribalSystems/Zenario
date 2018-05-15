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

class user {


	const ipFromTwig = true;
	//Formerly "visitorIP()"
	public static function ip() {
		if (defined('USE_FORWARDED_IP')
		 && constant('USE_FORWARDED_IP')
		 && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	
		} elseif (!empty($_SERVER['REMOTE_ADDR'])) {
			$ip = $_SERVER['REMOTE_ADDR'];
	
		} else {
			return false;
		}
	
		$ip = explode(',', $ip, 2);
		return $ip[0];
	}

	//Add an extranet user into a group, or out of a group
	//Formerly "addUserToGroup()"
	public static function addToGroup($userId, $groupId, $remove = false) {
		if ($col = \ze\dataset::fieldDBColumn($groupId)) {
			\ze\row::set('users_custom_data', [$col => ($remove ? 0 : 1)], ['user_id' => $userId]);
		}
	}


	
	//Formerly "getUserGroups()", "userGroups()"
	public static function groups($userId = null, $flat = true, $getLabelWhenFlat = false) {
		if ($userId === -1) {
			$userId = $_SESSION['extranetUserID'] ?? false;
		}
		
		$groups = [];
	
		//Look up a list of group names on the system
		if (!is_array(\ze::$groups)) {
			\ze::$groups = \ze\row::getAssocs('custom_dataset_fields', ['id', 'label', 'db_column'], ['type' => 'group', 'is_system_field' => 0], 'db_column', 'db_column');
		}
	
		if (!empty(\ze::$groups)) {
			//Get the row from the users_custom_data table for this user
			//(Note that the group names stored in \ze::$groups are the column names)
			$inGroups = \ze\row::get('users_custom_data', array_keys(\ze::$groups), $userId);
		
			//Come up with a subsection of the groups that this user is in
			foreach (\ze::$groups as $groupCol => $group) {
				if (!empty($inGroups[$groupCol])) {
					if ($flat) {
						if ($getLabelWhenFlat) {
							$groups[$group['id']] = $group['label'];
						} else {
							$groups[$group['id']] = $groupCol;
						}
					} else {
						$groups[$group['id']] = $group;
					}
				}
			}
		}
	
		return $groups;
	}

	const isInGroupFromTwig = true;
	//Formerly "checkUserInGroup()"
	public static function isInGroup($groupId, $userId = 'session') {
	
		if ($userId === 'session') {
			if (empty($_SESSION['extranetUserID'])) {
				return false;
			} else {
				$userId = $_SESSION['extranetUserID'];
			}
		}
	
		if (!$userId || !((int) $groupId)) {
			return false;
		}
	
		$group_name = \ze\dataset::fieldDBColumn($groupId);
	
		if(!$group_name) {
			return false;
		}
	
		return (bool) \ze\row::get('users_custom_data', $group_name, $userId);
	}

	//Formerly "getUserGroupsNames()"
	public static function getUserGroupsNames( $userId ) {
		$groups = \ze\user::groups($userId);
	
		if (empty($groups)) {
			return \ze\admin::phrase('_NO_GROUP_MEMBERSHIPS');
		} else {
			return implode(', ', $groups);
		}
	}

	//Formerly "getGroupLabel()"
	public static function getGroupLabel($group_id) {
		if ($group_id) {
			if(is_numeric($group_id)) {
				return \ze\row::get('custom_dataset_fields', 'label', $group_id);
			} else {
				return \ze\row::get('custom_dataset_fields', 'label', ['db_column' => $group_id]);
			}
		} else {
			return \ze\admin::phrase("_ALL_EXTRANET_USERS");
		}

	}
	
	

	//Attempt to automatically log a User in if the cookie is set on the User's Machine
	//Formerly "logUserInAutomatically()"
	public static function logInAutomatically() {
		if (isset($_SESSION)) {
			if (empty($_SESSION['extranetUserID'])) {
			
				if (isset($_COOKIE['LOG_ME_IN_COOKIE'])
				 && ($idAndMD5 = explode('_', $_COOKIE['LOG_ME_IN_COOKIE'], 2))
				 && (count($idAndMD5) == 2)
				 && ($user = \ze\row::get('users', ['id', 'first_name', 'last_name', 'email', 'screen_name', 'password'], ['id' => (int) $idAndMD5[0], 'status' => 'active']))
				 && ($idAndMD5[1] === md5(\ze\link::host(). $user['id']. $user['screen_name']. $user['email']. $user['password']))) {
					\ze\user::logIn($user['id']);
				
					if (\ze::setting('cookie_consent_for_extranet') == 'granted') {
						\ze\cookie::setConsent();
					}
				}
		
			//Check the session to see if the extranet user is for a different site that this one.
			//Also automatically log out any Users who have been suspended.
			} else
			if (empty($_SESSION['extranetUser_logged_into_site'])
			 || $_SESSION['extranetUser_logged_into_site'] != COOKIE_DOMAIN. SUBDIRECTORY. \ze::setting('site_id')
			 || !\ze\row::exists('users', ['id' => (int) $_SESSION['extranetUserID'], 'status' => 'active'])) {
				\ze\user::logOut();
			}
		}
	
		//A similar function for Admins
		if (isset($_SESSION['admin_userid']) && \ze\priv::check()) {
			//Check if we can find the current admin
			$admin = false;
			if (empty($_SESSION['admin_global_id'])) {
				$admin = \ze\row::get('admins', ['modified_date'], ['authtype' => 'local', 'id' => $_SESSION['admin_userid'], 'status' => 'active']);
		
			} elseif (\ze\db::connectGlobal()) {
				$admin = \ze\rowGlobal::get('admins', ['modified_date'], ['authtype' => 'local', 'id' => $_SESSION['admin_global_id'], 'status' => 'active']);
			}
		
			//If not, log them out
			if (!$admin) {
				\ze\admin::unsetSession();
		
			//Update an Admin's permissions if their admin record has been modified since they were last set.
				//Note that I'm also triggering this logic if a couple of $_SESSION variables are missing;
				//this is to catch the case where someone migrates the site from the old admin permission
				//system to the new admin permissions system, when other admins are still logged in.
			} else
			if (empty($_SESSION['admin_permissions'])
			 || empty($_SESSION['admin_modified_date'])
			 || $_SESSION['admin_modified_date'] != $admin['modified_date']) {
				if (empty($_SESSION['admin_global_id'])) {
					\ze\admin::setSession($_SESSION['admin_userid']);
				} else {
					\ze\admin::setSession(\ze\adminAdm::syncMultisiteAdmins($_SESSION['admin_global_id']), $_SESSION['admin_global_id']);
				}
			}
		}

		\ze::$adminId = \ze\admin::id();
		\ze::$userId = \ze\user::id();
	}

	//Formerly "logUserOut()"
	public static function logOut() {
		unset(
			$_SESSION['extranetUserID'],
			$_SESSION['extranetUserImpersonated'],
			$_SESSION['extranetUserID_pending'],
			$_SESSION['extranetUser_firstname'],
			$_SESSION['extranetUserSteps']
		);
		$_SESSION['FORGET_EXTRANET_LOG_ME_IN_COOKIE'] = true;
	}

	const emailFromTwig = true;
	//Formerly "userEmail()"
	public static function email($userId = null) {
		if ($userId === null) {
			$userId = $_SESSION['extranetUserID'] ?? false;
		}
	
		if ($userId) {
			return \ze\row::get('users', 'email', $userId);
		} else {
			return false;
		}
	}

	//Formerly "userId()"
	public static function id() {
		return ($_SESSION['extranetUserID'] ?? false);
	}

	const screenNameFromTwig = true;
	//Formerly "getUserScreenName()", "userScreenName()"
	public static function screenName($userId = 'session') {
		if ($userId === 'session') {
			$userId = $_SESSION['extranetUserID'] ?? false;
		}
	
		if ($userId) {
			return \ze\row::get('users', 'screen_name', $userId);
		} else {
			return false;
		}
	}

	const usernameFromTwig = true;
	//Formerly "userUsername()"
	public static function username($userId = 'session') {
		if ($userId === 'session') {
			$userId = $_SESSION['extranetUserID'] ?? false;
		}
	
		if ($userId) {
			return getUsername($userId);
		} else {
			return false;
		}
	}

	const fieldDisplayValueFromTwig = true;
	//Formerly "userFieldDisplayValue()"
	public static function fieldDisplayValue($cfield, $userId = -1, $returnCSV = true) {
		if ($userId === -1) {
			$userId = $_SESSION['extranetUserID'] ?? false;
		}
		return \ze\dataset::fieldValue('users', $cfield, $userId, $returnCSV, true);
	}

	const fieldValueFromTwig = true;
	//Formerly "userFieldValue()"
	public static function fieldValue($cfield, $userId = -1, $returnCSV = true) {
		if ($userId === -1) {
			$userId = $_SESSION['extranetUserID'] ?? false;
		}
		return \ze\dataset::fieldValue('users', $cfield, $userId, $returnCSV, false);
	}
	


	//Formerly "getUserIdentifier()"
	public static function identifier($userId) {
		return \ze\row::get('users', 'identifier', $userId);
	}

	const nameFromTwig = true;
	//Formerly "userFirstAndLastName()", "getUserFirstNameSpaceLastName()"
	public static function name($userId = null) {
		if ($userId === null) {
			$userId = $_SESSION['extranetUserID'] ?? false;
		}
		if ($row = \ze\row::get('users', ['first_name', 'last_name'], $userId)) {
			return $row['first_name']. ' '. $row['last_name'];
		}
		return null;
	}

	//Formerly "getUserIdFromScreenName()"
	public static function getIdFromScreenName($screenName) {
		return \ze\row::get('users', 'id', ['screen_name' => $screenName]);
	}








	//Formerly "logUserIn()"
	public static function logIn($userId, $impersonate = false) {
	
		//Get details on this user
		$user = \ze\row::get('users', ['id', 'first_name', 'last_name', 'screen_name', 'email', 'password'], $userId);
	
		//Create a login hash (used for the \ze\user::logInAutomatically() function)
		$user['login_hash'] = $user['id']. '_'. md5(\ze\link::host(). $user['id']. $user['screen_name']. $user['email']. $user['password']);
		unset($user['password']);
	
		if (!$impersonate) {
			//Update their last login time
			\ze\row::update('users', ['last_login' => \ze\date::now()], $userId);
		
	
			if(\ze::setting('period_to_delete_sign_in_log') != 'never_save'){
				require_once CMS_ROOT. 'zenario/libs/manually_maintained/mit/browser/lib/browser.php';
				$browser = new \Browser();
		
				if($days = \ze::setting('period_to_delete_sign_in_log')){
					if(is_numeric($days)){
						$today = date('Y-m-d');
						$date = date('Y-m-d', strtotime('-'.$days.' day', strtotime($today)));
						if($date){
							$sql = " 
								DELETE FROM ". DB_PREFIX. "user_signin_log
								WHERE login_datetime < '".\ze\escape::sql($date)."'";
							\ze\sql::update($sql);
						}
					}
				}
		
				$sql = "
					INSERT INTO ". DB_PREFIX. "user_signin_log SET
						user_id = ". (int)  \ze\escape::sql($userId).",
						login_datetime = NOW(),
						browser = '". \ze\escape::sql($browser->getBrowser()). "',
						browser_version = '". \ze\escape::sql($browser->getVersion()). "',
						platform = '". \ze\escape::sql($browser->getPlatform()). "'";
				\ze\sql::update($sql);
			}
			\ze\module::sendSignal('eventUserLoggedIn',['user_id' => $userId]);
		}
	
		$_SESSION['extranetUserID'] = $userId;
		$_SESSION['extranetUser_firstname'] = $user['first_name'];
		$_SESSION['extranetUser_logged_into_site'] = COOKIE_DOMAIN. SUBDIRECTORY. \ze::setting('site_id');
	
		return $user;
	}

	//Formerly "getUserDetails()"
	public static function details($userId) {
	
		if ($user = \ze\row::get('users', true, $userId)) {
			if ($custom_data = \ze\row::get('users_custom_data', true, $userId)) {
				unset($custom_data['user_id']);
				$user = array_merge($custom_data, $user);
			}
		}
		return $user;
	}

	//Formerly "checkUsersPassword()"
	public static function checkPassword($userId, $password) {
		//Look up some of this user's details
		if (!$user = \ze\row::get('users', ['id', 'password', 'password_salt'], (int) $userId)) {
			return false;
		}
	
		//N.b. from version 8 we are forcing everyone to encrypted their passwords,
		//you can no longer opt out of this!
		$shouldBeEncrypted = true;
	
		//The password could have been stored as either plain text, sha1 or sha2.
		if ($user['password_salt'] === null) {
			//Non-hashed passwords
			$wasEncrypted = false;
			$correct = $user['password'] === $password;
	
		} elseif (substr($user['password'], 0, 6) != 'sha256') {
			//SHA1
			$wasEncrypted = true;
			$correct = $user['password'] == \ze\user::hashPasswordSha1($user['password_salt'], $password);
	
		} else {
			//SHA2
			$wasEncrypted = true;
			$correct = $user['password'] == \ze\user::hashPasswordSha2($user['password_salt'], $password);
		}
	
		if ($correct) {
			//If the password was not stored in the form chosen in the site settings, save it in the correct form
			if ($wasEncrypted != $shouldBeEncrypted) {
				\ze\userAdm::setPassword($user['id'], $password);
			}
		
			return true;
		} else {
			return false;
		}
	}
	
	public static function isPasswordExpired($userId) {
		$sql = "
			SELECT
				(
					password_needs_changing
					AND reset_password_time <= DATE_SUB(NOW(), INTERVAL ". ((int) \ze::setting('temp_password_timeout') ?: 14). " DAY)
				) AS password_expired
			FROM " . DB_PREFIX . "users as u
			WHERE id = " . (int)$userId;
		$user = \ze\sql::fetchAssoc($sql);
		return $user && $user['password_expired'];
	}


	//Formerly "checkNamedUserPermExists()"
	public static function checkNamedPermExists($perm, &$directlyAssignedToUser, &$hasRoleAtCompany, &$hasRoleAtLocation, &$hasRoleAtLocationAtCompany, &$onlyIfHasRolesAtAllAssignedLocations) {
	
		switch ($perm) {
			//Custom permissions (TODO: some way to add these in from custom modules)
			case 'view.pending_membership_application':
			case 'upload_extra_documents.pending_membership_application':
			case 'accept_reject.pending_membership_application':
				return true;
			
			case 'manage.conference':
			//Permissions for changing site settings
			case 'manage.options-assetwolf':
			//Possible permissions for companies, locations and users
			case 'create-company.unassigned':
			case 'delete.company':
			case 'create-location.unassigned':
			case 'create-user.unassigned':
			//Export all of the asset data on a site
			case 'export.allData':
			//Recalculate all of the asset data on a site
			case 'recalculate.allData':
			//View invoices
			case 'view.invoice':
				//Superusers only
				return true;
			case 'view.company':
			case 'edit.company':
			case 'create-location.company':
				return
					$hasRoleAtCompany = true;
			case 'edit.location':
			case 'delete.location':
				return
					$hasRoleAtLocation = true;
			case 'view.location':
			case 'create-user.location':
			case 'assign-user.location':
			case 'deassign-user.location':
			case 'view.user':
			case 'edit.user':
				return
					$hasRoleAtLocation =
					$hasRoleAtLocationAtCompany = true;
			case 'delete.user':
				return
					$hasRoleAtLocation =
					$hasRoleAtLocationAtCompany =
					$onlyIfHasRolesAtAllAssignedLocations = true;
		
			//Possible permissions for assets
			case 'create-asset.unassigned':
			case 'create-asset.oneself':
			case 'assign.asset':
				//Superusers only
				return true;
			case 'create-asset.company':
				return
					$hasRoleAtCompany = true;
			case 'create-asset.location':
				return
					$hasRoleAtLocation = true;
			case 'view.asset':
			case 'edit.asset':
			case 'acknowledge.asset':
			case 'enterData.asset':
			case 'enterAnyData.asset':
			case 'delete.asset':
			case 'sendCommandTo.asset':
			case 'sendSimpleCommandTo.asset':
				return
					$hasRoleAtLocation =
					$hasRoleAtCompany =
					$hasRoleAtLocationAtCompany =
					$directlyAssignedToUser = true;
		
			//Possible permissions for other Assetwolf things
			case 'create-schema.unassigned':
			case 'create-command.unassigned':
			case 'create-dataRepeater.unassigned':
			case 'create-trigger.unassigned':
			case 'create-procedure.unassigned':
			case 'create-schedule.unassigned':
			case 'create-scheduledReport.unassigned':
			case 'create-schema.oneself':
			case 'create-command.oneself':
			case 'create-trigger.oneself':
			case 'create-procedure.oneself':
			case 'create-schedule.oneself':
			case 'manage.command':
				//Superusers only
				return true;
			case 'create-schema.company':
			case 'create-command.company':
			case 'create-trigger.company':
			case 'create-procedure.company':
			case 'create-schedule.company':
			case 'edit.scheduledReport':
			case 'delete.scheduledReport':
				return
					$hasRoleAtCompany = true;
			case 'view.schema':
			case 'view.command':
			case 'view.dataRepeater':
			case 'view.trigger':
			case 'view.procedure':
			case 'view.schedule':
			case 'edit.schema':
			case 'edit.command':
			case 'edit.trigger':
			case 'edit.procedure':
			case 'edit.schedule':
			case 'delete.schema':
			case 'delete.command':
			case 'delete.dataRepeater':
			case 'delete.trigger':
			case 'delete.procedure':
			case 'delete.schedule':
				return
					$hasRoleAtCompany =
					$directlyAssignedToUser = true;
			case 'view.scheduledReport':
				return 
					$hasRoleAtLocationAtCompany = true;
			//Reject any unrecognised permission
			default:
				return false;
		}
	}
	
	
	public static function permSetting($name) {
		
		return \ze\sql::fetchValue(
			"SELECT value FROM ". DB_PREFIX. "user_perm_settings WHERE name = '". \ze\escape::sql($name). "'"
		);
	}

	const canFromTwig = true;
	//Formerly "checkUserCan()"
	public static function can($action, $target = 'unassigned', $targetId = false, $multiple = false, $authenticatingUserId = -1) {
	
		//If the multiple flag is set, we'll want an array of inputs.
		//If the multiple flag isn't set, then we'll want a singular input.
		//For security reasons I expect the developer to specifically say which of these they are expecting,
		//just to avoid someone passing in an array that the caller then evalulates to true.
		if ($multiple XOR is_array($targetId)) {
			return false;
		}
		
		//"global" is an alias for "unassigned", for the purposes of this function
		if ($target === 'global') {
			$target = 'unassigned';
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
		$perm = $action;
		if ($target !== false) {
			$perm .= '.'. $target;
		}
	
	
		//Run some global checks that apply regardless of the $targetId
		//If the action is always allowed (or always disallowed) everywhere, then we can skip some specific logic
		do {
			$isGlobal = true;
			//Only certain combination of settings are valid, if the name of a permissions check is requested that does not exist
			//then return false.
			if (!\ze\user::checkNamedPermExists($perm, $directlyAssignedToUser, $hasRoleAtCompany, $hasRoleAtLocation, $hasRoleAtLocationAtCompany, $onlyIfHasRolesAtAllAssignedLocations)) {
				$hasGlobal = false;
				break;
			}
		
			//View permissions have the option to show to everyone, or to always show to any extranet user,
			//except for "view user's details" which always needs at least an extranet login
			if ($action == 'view') {
				switch (\ze\user::permSetting($perm)) {
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
			if (\ze\user::permSetting($perm. '.by.group')
			 && ($groupIds = \ze\user::permSetting($perm. '.groups'))) {
			
				//Get a row from the users_custom_data table containing the groups indexed by group id
				$userGroups = \ze\user::groups($authenticatingUserId, true);
			
				foreach (\ze\ray::explodeAndTrim($groupIds, true) as $groupId) {
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
			$hasRoleAtLocation = $hasRoleAtLocation && \ze\user::permSetting($perm. '.atLocation');
			$hasRoleAtCompany = $hasRoleAtCompany && \ze\user::permSetting($perm. '.atCompany');
			$hasRoleAtLocationAtCompany = $hasRoleAtLocationAtCompany && \ze\user::permSetting($perm. '.atLocationAtCompany');
		
			//Look up table-prefixes if needed
			if ($isAW) {
				$ASSETWOLF_2_PREFIX = \ze\module::prefix('assetwolf_2', true);
			}
	
			if ($hasRoleAtCompany || $hasRoleAtLocationAtCompany || $hasRoleAtLocation) {
				$ZENARIO_ORGANIZATION_MANAGER_PREFIX = \ze\module::prefix('zenario_organization_manager', true);
			}
			if ($hasRoleAtCompany || $hasRoleAtLocationAtCompany) {
				$ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX = \ze\module::prefix('zenario_company_locations_manager', true);
			}
	
		} while (false);
	
		//Allow multiple ids to be checked at once in an array
		if ($multiple) {
			//We need an associative array with targetIds as keys.
		
			//This code would check if we have a numeric array and convert it to an associative array.
			//foreach ($targetId as $key => $dummy) {
			//	if ($key === 0) {
			//		$targetId = \ze\ray::valuesToKeys($targetId);
			//	}
			//	break;
			//}
		
			//Loop through the array, applying the permissions check to each entry
			foreach ($targetId as $key => &$value) {
				$value = self::canInternal(
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
			return self::canInternal(
				$target, $targetId, $authenticatingUserId,
				$perm, $isAW, $awTable, $awIdCol, $isGlobal, $hasGlobal,
				$directlyAssignedToUser, $hasRoleAtCompany, $hasRoleAtLocation, $hasRoleAtLocationAtCompany,
				$onlyIfHasRolesAtAllAssignedLocations, $ASSETWOLF_2_PREFIX,
				$ZENARIO_ORGANIZATION_MANAGER_PREFIX, $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX
			);
		}
	}

	private static function canInternal(
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
				if ($targetId) {
					switch ($perm) {
						case 'view.user':
							//Users can always view their own data, this is hard-coded
							if ($targetId == $authenticatingUserId) {
								return true;
							}
							break;
		
						case 'edit.user':
							//Users can always edit their own data if the site setting to do so is enabled
							if ($targetId == $authenticatingUserId && \ze\user::permSetting('edit.oneself')) {
								return true;
							}
							break;
		
						case 'delete.user':
							//Stop users from deleting themslves
							if ($targetId == $authenticatingUserId) {
								return false;
							}
							break;
					}
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
		if ($isAW && ($ASSETWOLF_2_PREFIX = \ze\module::prefix('assetwolf_2', true))) {
			if ($row = \ze\sql::fetchRow("
				SELECT owner_type, owner_id
				FROM ". DB_PREFIX. $ASSETWOLF_2_PREFIX. $awTable. "
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
						return $directlyAssignedToUser && ($authenticatingUserId == $row[1]) && \ze\user::permSetting($perm. '.directlyAssigned');
				}
			} else {
				return false;
			}
		}
	
		//The *thing* is assigned to a company, and they have [ANY/the specified] role at ANY location in that company
		if ($hasRoleAtCompany && $companyId && $ZENARIO_ORGANIZATION_MANAGER_PREFIX && $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX) {
			$sql = "
				SELECT 1
				FROM ". DB_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link AS urll
				". ($onlyIfHasRolesAtAllAssignedLocations? "LEFT" : "INNER"). " JOIN ". DB_PREFIX. $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX. "company_location_link AS cll
				   ON cll.company_id = ". (int) $companyId. "
				  AND urll.location_id = cll.location_id
				WHERE urll.user_id = ". (int) $authenticatingUserId;
		
			if ($roleId = \ze\user::permSetting($perm. '.atCompany.role')) {
				$sql .= "
				  AND role_id = ". (int) $roleId;
			}
			$sql .= "
				LIMIT 1";
		
			if (\ze\sql::fetchRow($sql)) {
				return true;
			}
		}
	
		//The *thing* is assigned to a location at which they have [ANY/the specified] role
		if ($hasRoleAtLocation && ($locationId || $isUserCheck) && $ZENARIO_ORGANIZATION_MANAGER_PREFIX) {
		
			$roleId = \ze\user::permSetting($perm. '.atLocation.role');
		
			//Check the case where something is assigned to just one location
			if ($locationId && !$isUserCheck) {
				$sql = "
					SELECT 1
					FROM ". DB_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link
					WHERE location_id = ". (int) $locationId. "
					  AND user_id = ". (int) $authenticatingUserId;
		
				if ($roleId) {
					$sql .= "
					  AND role_id = ". (int) $roleId;
				}
				$sql .= "
					LIMIT 1";
			
				if (\ze\sql::fetchRow($sql)) {
					return true;
				}
		
			//Handle checks on users, who can be assigned to multiple locations
			} elseif ($isUserCheck) {
				//Check if the target user is at any location that the current user is at
				//Also, if we're following ONLY logic, check that they are not at any other company
				$sql = "
					SELECT ". ($onlyIfHasRolesAtAllAssignedLocations? "cur.user_id IS NOT NULL" : "1"). "
					FROM ". DB_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link AS tar
					". ($onlyIfHasRolesAtAllAssignedLocations? "LEFT" : "INNER"). "
					JOIN ". DB_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link AS cur
					   ON cur.user_id = ". (int) $authenticatingUserId;
		
				if ($roleId) {
					$sql .= "
					  AND cur.role_id = ". (int) $roleId;
				}
			
				$sql .= "
					WHERE tar.user_id = ". (int) $targetId. "
					". ($onlyIfHasRolesAtAllAssignedLocations? "ORDER BY 1" : ""). "
					LIMIT 1";
			
				if (($row = \ze\sql::fetchRow($sql)) && ($row[0])) {
					return true;
				}
			}
		}
	
		//The *thing* is assigned to a location at a company, and they have [ANY/the specified] role at ANY location in that company
		if ($hasRoleAtLocationAtCompany && ($locationId || $isUserCheck) && $ZENARIO_ORGANIZATION_MANAGER_PREFIX && $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX) {
		
			$roleId = \ze\user::permSetting($perm. '.atLocationAtCompany.role');
		
			//Check the case where something is assigned to just one location
			if ($locationId && !$isUserCheck) {
				//Look up the company id up from the location
				$sql = "
					SELECT company_id
					FROM ". DB_PREFIX. $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX. "company_location_link
					WHERE location_id = ". (int) $locationId;
				if ($companyId = \ze\sql::fetchValue($sql)) {
				
					//Check if the current user is at any location in this company
					$sql = "
						SELECT 1
						FROM ". DB_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link AS urll
						INNER JOIN ". DB_PREFIX. $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX. "company_location_link AS cll
						   ON cll.company_id = ". (int) $companyId. "
						  AND urll.location_id = cll.location_id
						WHERE urll.user_id = ". (int) $authenticatingUserId;
		
					if ($roleId) {
						$sql .= "
						  AND role_id = ". (int) $roleId;
					}
					$sql .= "
						LIMIT 1";
		
					if (\ze\sql::fetchRow($sql)) {
						return true;
					}
				}
		
			//Handle users, who can be assigned to multiple locations
			} elseif ($isUserCheck) {
				//Look up every company that the current user is assigned to
				$sql = "
					SELECT DISTINCT company_id
					FROM ". DB_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link AS urll
					INNER JOIN ". DB_PREFIX. $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX. "company_location_link AS cll
					   ON urll.location_id = cll.location_id
					WHERE urll.user_id = ". (int) $authenticatingUserId;
		
				if ($roleId) {
					$sql .= "
					  AND urll.role_id = ". (int) $roleId;
				}
			
				if (($companyIds = \ze\sql::fetchValues($sql)) && (!empty($companyIds))) {
					//Check if the target user is at any of these companies
					//Also, if we're following ONLY logic, check that they are not at any other company
					$sql = "
						SELECT ". ($onlyIfHasRolesAtAllAssignedLocations? "cll.company_id IS NOT NULL" : "1"). "
						FROM ". DB_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link AS urll
						". ($onlyIfHasRolesAtAllAssignedLocations? "LEFT" : "INNER"). "
						JOIN ". DB_PREFIX. $ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX. "company_location_link AS cll
						   ON cll.company_id IN (". \ze\escape::in($companyIds, true). ")
						  AND urll.location_id = cll.location_id
						WHERE urll.user_id = ". (int) $targetId. "
						". ($onlyIfHasRolesAtAllAssignedLocations? "ORDER BY 1" : ""). "
						LIMIT 1";
			
					if (($row = \ze\sql::fetchRow($sql)) && ($row[0])) {
						return true;
					}
				}
			}
		}
	
		//If no rule matches, deny access
		return false;
	}

	const canCreateFromTwig = true;
	//Shortcut function for creating things, which has a slightly less confusing syntax
	//Formerly "checkUserCanCreate()"
	public static function canCreate($thingToCreate, $assignedTo = 'unassigned', $assignedToId = false, $multiple = false, $authenticatingUserId = -1) {
	
		//Convenience feature: accept either the thing's type, or the thing's variable's name.
		//If the name ends with "Id" we'll just strip it off.
		if (substr($thingToCreate, -2) == 'Id') {
			$thingToCreate = substr($thingToCreate, 0, -2);
		}
	
		return \ze\user::can('create-'. $thingToCreate, $assignedTo, $assignedToId, $multiple, $authenticatingUserId);
	}








	//Some password functions for users/admins

	//Formerly "hashPassword()"
	public static function hashPassword($salt, $password) {
		if ($hash = \ze\user::hashPasswordSha2($salt, $password)) {
			return $hash;
		} else {
			return \ze\user::hashPasswordSha1($salt, $password);
		}
	}

	//Formerly "hashPasswordSha2()"
	public static function hashPasswordSha2($salt, $password) {
		if ($hash = @hash('sha256', $salt. $password, true)) {
			return 'sha256'. base64_encode($hash);
		} else {
			return false;
		}
	}

	//Old sha1 function for passwords created before version 6.0.5. Or if sha2 is not enabled on a server.
	//Formerly "hashPasswordSha1()"
	public static function hashPasswordSha1($salt, $password) {
		$result = \ze\sql::select(
			"SELECT SQL_NO_CACHE SHA('". \ze\escape::sql($salt. $password). "')");
		$row = \ze\sql::fetchRow($result);
		return $row[0];
	}








	//Returns an array of password strength names to scores
	//Defined as a function so that it is only hardcoded here, rather
	//than everywhere it is used.
	//Formerly "passwordStrengthsToValues()"
	public static function passwordStrengthsToValues($strength = false) {
		$a = ['_WEAK' => 0, '_MEDIUM' => 35, '_STRONG' => 70, '_VERY_STRONG' => 100];
		//Either return an array, or if $strength is set, return the score for that strength
		if ($strength) {
			return $a[$strength];
		} else {
			return $a;
		}
	}

	//Formerly "passwordValuesToStrengths()"
	public static function passwordValuesToStrengths($value) {
		if ($value < 35) {
			return '_WEAK';
		} elseif ($value < 70) {
			return '_MEDIUM';
		} elseif ($value < 100) {
			return '_STRONG';
		} else {
			return '_VERY_STRONG';
		}
	}


	//Calculate the bonus for using different sets of characters
		//e.g. lower case, upper case, numeric, non-alphanumeric...
	//Formerly "calculateBonusFromUsage()"
	public static function calculateBonusFromUsage($n) {
		return min(2, $n);
	}

	//Check if a given password meets the strength requirements.
	//Formerly "checkPasswordStrength()"
	public static function checkPasswordStrength($pass, $passwordStrengthRequired = false) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}






	//Given a MySQL timestamp, a unix timestamp, or a PHP date object, return a PHP date object in the current user's timezone
	//Formerly "convertToUserTimezone()"
	public static function convertToUsersTimeZone($time, $specificTimeZone = false) {
	
		//Accept either dates in UTC/GMT, or UNIX timestamps (in seconds, not in ms)
		//Also accept a PHP date object (i.e. this function won't cause an error if someone accidentally calls it twice!)
		if (is_numeric($time)) {
			$time = new \DateTime('@'. (int) $time);
	
		} elseif (is_string($time)) {
			$time = new \DateTime($time);
		}
	
		if ($specificTimeZone) {
			$time->setTimeZone(new \DateTimeZone($specificTimeZone));
		} else {
			//Get the user's timezone, if not already checked
			if (\ze::$timezone === null) {
				\ze::$timezone = \ze\user::timeZone();
			}
			if (\ze::$timezone) {
				$time->setTimeZone(new \DateTimeZone(\ze::$timezone));
			}
		}
	
		return $time;
	}

	//Formerly "getUserTimezone()"
	public static function timeZone($userId = false) {
		$timezone = false;
		if (!$userId) {
			$userId = ($_SESSION['extranetUserID'] ?? false);
		}
	
		if ($userId
		 && ($timezoneFieldId = \ze::setting('zenario_timezones__timezone_dataset_field'))
		 && ($timezoneFieldCol = \ze\row::get('custom_dataset_fields', 'db_column', $timezoneFieldId))
		 && ($timezone = \ze\row::get('users_custom_data', $timezoneFieldCol, $userId))) {
			//Use the timezone from the user's preferences, if set
	
		} elseif ($timezone = \ze::setting('zenario_timezones__default_timezone')) {
			//Use the timezone from the site settings, if set
	
		} else {
			//Otherwise use the server default if neither is set
			$timezone = false;
		}
		return $timezone;
	}
	
	public static function recordConsent($userId, $email, $firstName, $lastName, $label = '') {
		\ze\row::insert(
			'consents', 
			[
				'datetime' => date('Y-m-d H:i:s'), 
				'user_id' => $userId, 
				'ip_address' => \ze\user::ip(),
				'email' => mb_substr($email, 0, 255, 'UTF-8'),
				'first_name' => mb_substr($firstName, 0, 255, 'UTF-8'),
				'last_name' => mb_substr($lastName, 0, 255, 'UTF-8'),
				'label' => mb_substr($label, 0, 250, 'UTF-8')
			]
		);
	}

}