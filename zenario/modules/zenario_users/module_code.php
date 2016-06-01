<?php
/*
 * Copyright (c) 2016, Tribal Limited
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

class zenario_users extends module_base_class {

	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->preFillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->fillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function lineStorekeeperCSV($path, &$columns, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->lineStorekeeperCSV($path, $columns, $refinerName, $refinerId);
		}
	}	
	public function formatStorekeeperCSV($path, &$item, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->formatStorekeeperCSV($path, $item, $refinerName, $refinerId);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId);
		}
	}	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->organizerPanelDownload($path, $ids, $refinerName, $refinerId);
		}
	}	
	public function handleAJAX() {
		if ($c = $this->runSubClass(__FILE__, 'organizer', 'zenario__users/panels/smart_groups')) {
			return $c->handleAJAX();
		}
	}
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->fillAdminBox($path, $settingGroup, $box, $fields, $values);
		}
	}	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->validateAdminBox($path, $settingGroup, $box, $fields, $values, $changes, $saving);
		}
	}	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->saveAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->adminBoxSaveCompleted($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->adminBoxDownload($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	//Deprecated characteristics functions
	public static function getCharacteristic($field) {
		//Load details on a custom field
		if ($details = getDatasetFieldDetails($field, 'users')) {
		
			//Attempt to convert back to the old format.
			$details['name'] = $details['db_column'];
			$details['ordinal'] = $details['ord'];
			$details['help_text'] = $details['note_below'];
			$details['show_in_organizer_panel'] = $details['show_in_organizer'];
			$details['organizer_allow_sort'] = $details['sortable'];
			$details['admin_box_text_field_width'] = $details['width'];
			$details['admin_box_text_field_rows'] = $details['height'];
			
			//Convert the type field as best we can!
			switch ($details['type']) {
			
				case 'centralised_select':
					if ($details['values_source'] == 'zenario_country_manager::getActiveCountries') {
						$details['type'] = 'country';
					}
					
				case 'radios':
				case 'centralised_radios':
					$details['type'] = 'list_single_select';
					break;
			
				case 'checkboxes':
					$details['type'] = 'list_multi_select';
					break;
			
				case 'checkbox':
					$details['type'] = 'boolean';
					break;
			}
		}
		
		//Hope whatever code called this still works!
		return $details;
	}
	
	public static function getGroupDetails($id) {
		return self::getCharacteristic($id);
	}
	
	public static function getCharacteristicValue($id) {
		
		//Load details on a custom field value
		if ($details = getRow('custom_dataset_field_values', true, $id)) {
			//Attempt to convert back to the old format.
			$details['name'] = '';
			$details['ordinal'] = $details['ord'];
			$details['help_text'] = $details['note_below'];
			$details['characteristic_id'] = $details['field_id'];
		} else {
			$details = array('label' => (string) $id);
		}
		
		//Hope whatever code called this still works!
		return $details;
	}
	
	

	
	
	//User Access Logging
	
	//Check if we should log that a user has accessed an item
	public static function logUserAccess($extranetUserID, $cID, $cType, $cVersion) {
	
		//Check whether this content item logs user access
		if (!empty($_SESSION['extranetUserID'])
		&& !isset($_SESSION['extranetUserImpersonated'])
		&& self::doesContentItemLogUserAccess($cID, $cType, $cVersion)
		&& setting('log_user_access')) {
	
			cms_core::$userAccessLogged = true;
	
			//Log the hit
			$sql = "
				INSERT IGNORE INTO ". DB_NAME_PREFIX. "user_content_accesslog SET
					user_id = ". (int) $_SESSION['extranetUserID']. ",
					hit_datetime = NOW(),
					ip = '". sqlEscape(visitorIP()). "',
					content_id = ". (int) $cID. ",
					content_type = '". sqlEscape($cType). "',
					content_version = ". (int) $cVersion;
			sqlQuery($sql);
		}
	}
	
	//Check if a content item logs user access
	public static function doesContentItemLogUserAccess($cID, $cType) {
		$privacy = getRow('translation_chains', 'privacy', array('equiv_id' => equivId($cID, $cType), 'type' => $cType));
		return in_array($privacy, array('all_extranet_users', 'group_members', 'specific_users'));
	}
	
	
	public static function  setUserGroupOrBoolean($userId, $characteristic_id, $bool) {
		$rv = zenario_users::getCharacteristic($characteristic_id);
		if($rv){
			switch($rv['type']) {
				case 'boolean':
				case 'group':
					$sql = "UPDATE ". DB_NAME_PREFIX. "users_custom_data
							SET `" . $rv['name'] . "`=" . ($bool ? 1 : 0)
								. " WHERE user_id = ". (int) $userId;
					sqlQuery($sql);
					break;
			}
		}
	}
	
	public function fillAdminToolbar(&$adminToolbar, $cID, $cType, $cVersion) {
		
		if (isset($adminToolbar['sections']['history']['buttons']['zenario_users__access_log'])) {
			$item = getRow('translation_chains', array('privacy'), array('equiv_id' => cms_core::$equivId, 'type' => cms_core::$cType));
		
			if (setting('log_user_access') && in($item['privacy'], 'all_extranet_users', 'group_members', 'specific_users')) {
				$adminToolbar['sections']['history']['buttons']['zenario_users__access_log']['organizer_quick']['path'] =
					'zenario__content/panels/content/user_access_log//'. $cType. '_'. $cID. '//';
		
			} else {
				unset($adminToolbar['sections']['history']['buttons']['zenario_users__access_log']);
			}
		}
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	//Various API and internal functions
	
	protected static function setupGroupOrUserCheckboxes($table, $idCol, $equivId, $cType) {
		return inEscape(getRowsArray($table, $idCol, array('equiv_id' => $equivId, 'content_type' => $cType)), true);
	}
	
	
	
	protected static function setContentItemGroupsOrUsers($table, $idCol, $equivId, $cType, $groupsOrUsers) {
		deleteRow($table, array('equiv_id' => $equivId, 'content_type' => $cType));
		
		if (is_array($groupsOrUsers)) {
			foreach ($groupsOrUsers as $value) {
				setRow($table, array(), array($idCol => $value, 'equiv_id' => $equivId, 'content_type' => $cType));
			}
		}
	}
	
	protected static function savePrivacySettings($tagIds, $values) {
		$equivId = $cType = false;
		foreach ($tagIds as $tagId) {
			if (getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
				
				//Save the privacy settings
				setRow(
					'translation_chains',
					array(
						'privacy' => $values['privacy/privacy']),
					array('equiv_id' => $equivId, 'type' => $cType));
				
				//Update the list of Users
				$users = array();
				if ($values['privacy/privacy'] == 'specific_users' && !empty($values['privacy/specific_users'])) {
					$users = explode(',', $values['privacy/specific_users']);
				}
				self::setContentItemGroupsOrUsers('user_content_link', 'user_id', $equivId, $cType, $users);
				
				//Update the list of Groups
				$groups = array();
				if ($values['privacy/privacy'] == 'group_members' && !empty($values['privacy/group_members'])) {
					$groups = explode(',', $values['privacy/group_members']);
				}
				self::setContentItemGroupsOrUsers('group_content_link', 'group_id', $equivId, $cType, $groups);
			}
		}
	}
	
	protected function impersonateUser($userId, $logAdminOut = false, $setCookie = false) {
		//Log the admin out of admin mode
		if ($logAdminOut) {
			require_once CMS_ROOT.  'zenario/includes/admin.inc.php';
			unsetAdminSession(false);
		}
		
		//Log the admin in as the target user
		$user = logUserIn($userId, true);
		$_SESSION["extranetUserImpersonated"] = true;
		if ($setCookie) {
			setCookieOnCookieDomain('LOG_ME_IN_COOKIE', $user['login_hash']);
		}
	}
		
	protected function loggedInAsParentFor($userId){
		$sql='SELECT id FROM ' . DB_NAME_PREFIX . 'users WHERE id=' . (int)$userId  . ' AND parent_id=' . (int) ($_SESSION['extranetUserID']);
		return (sqlNumRows(sqlQuery($sql))==1);
	}
	
	public function eventUserDeleted($userId) {
	//added to stop warning that zenario_users did not have this method
	
	}
	
	
	
	
	
	
	
	
	
	
	
	//
	//	User sync functions.
	//
	
	//If a User is synced, any updates on any site should be synced out to the hub and all satellites
	//All new Users on a hub must be synced out to every satellite. (New Users on a satellite are not synced out to the hub)
	//Any deleted Hub Users should be deleted from the satellites
	
	
	protected static $userDB = false;
	public static function validateUserSyncSiteConfig() {
		return zenario_pro_features::validateUserSyncSiteConfig();
	}
	
	public static function jobSyncUsers() {
		$result = zenario_users::syncUsers();
		
		if (isError($result)) {
			echo $result;
			exit;
		} else {
			return $result;
		}
	}
	
	//Check whether we are on the hub site or a satellite site.
		//Note that the logic above in validateUserSyncSiteConfig() ensures that a site can't be listed as a hub
		//and as a satellite.
	public static function thisIsHub() {
		if (!zenario_pro_features::validateUserSyncSiteConfig()) {
			return false;
		}
		require CMS_ROOT. 'zenario_usersync_config.php';
		
		$thisIsHub =
			$hub['DBHOST'] == DBHOST
		 && $hub['DBNAME'] == DBNAME;
		
		return $thisIsHub;
	}
	
	public static function connectHubDB() {
		if (zenario_pro_features::validateUserSyncSiteConfig()
		 && !zenario_pro_features::thisIsHub()) {
			
			require CMS_ROOT. 'zenario_usersync_config.php';
			
			if ($dbSelected = connectToDatabase($hub['DBHOST'], $hub['DBNAME'], $hub['DBUSER'], $hub['DBPASS'])) {
				cms_core::$lastDB = $dbSelected;
				cms_core::$lastDBHost = $hub['DBHOST'];
				cms_core::$lastDBName = $hub['DBNAME'];
				cms_core::$lastDBPrefix = $hub['DB_NAME_PREFIX'];
				return true;
			}
		}
		
		return false;
	}
	
	public static function syncUsers() {
		if (!zenario_pro_features::validateUserSyncSiteConfig()) {
			return false;
		}
		require CMS_ROOT. 'zenario_usersync_config.php';
		
		$thisIsHub =
			$hub['DBHOST'] == DBHOST
		 && $hub['DBNAME'] == DBNAME;
		
		
		//Get the current time
		$now = now();
		
		//Run the sync logic
		$syncs = 0;
		$success = zenario_users::syncUsersToSite($syncs, $now, $thisIsHub, $hub, true);
		foreach ($satellites as $satellite) {
			$success &= zenario_users::syncUsersToSite($syncs, $now, $thisIsHub, $satellite, false);
		}
		
		//Mark when this function was last run
		//(If we couldn't connect to a site for whatever reason, the last successful
		// run date of the sync function should not be updated)
		if ($success) {
			setSetting('user_last_sync_time', $now, $updateDB = true, $clearCache = false);
		}
		
		if ($success) {
			return $syncs > 0;
		} else {
			return new zenario_error('Database connection could not be established');
		}
	}
	
	//This function syncs users with a different database
	//Currently only the basic details (from the users table) are synced
	public static function syncUsersToSite(&$syncs, $now, $thisIsHub, $site, $isHub) {
		$syncedIds = array();
		
		//Don't sync this site to itself!
		if ($site['DBHOST'] == DBHOST
		 && $site['DBNAME'] == DBNAME) {
			return true;
		}
		
		//Get the basic details of all Users (on the hub site)
		//or all Users that have a global id (on the satellite sites)
		//and have been updated since the last time this function was has run
		$sql = "
			SELECT
				id,
				global_id,
				/*parent_id,*/
				ip,
				session_id,
				identifier,
				screen_name,
				screen_name_confirmed,
				password,
				password_salt,
				password_needs_changing,
				/*reset_password_time,*/
				status,
				/*image_id,*/
				last_login,
				last_profile_update_in_frontend,
				salutation,
				first_name,
				last_name,
				email,
				email_verified,
				created_date,
				modified_date,
				suspended_date,
				last_updated_timestamp,
				terms_and_conditions_accepted,
				/*equiv_id,
				content_type,*/
				hash,
				creation_method,
				ordinal
			FROM ". DB_NAME_PREFIX. "users
			WHERE TRUE";
		//Note that I'm delibrately leaving last_updated_timestamp in as I want to update this!
		
		if (!$thisIsHub) {
			$sql .= "
			  AND global_id != 0";
		}
		
		if (setting('user_last_sync_time')) {
			$sql .= "
			  AND last_updated_timestamp >= '". sqlEscape(setting('user_last_sync_time')). "'";
		}
		
		$result = sqlSelect($sql);
		
		//Connect to the other site
		if ($dbSelected = connectToDatabase($site['DBHOST'], $site['DBNAME'], $site['DBUSER'], $site['DBPASS'])) {
			cms_core::$lastDB = $dbSelected;
			cms_core::$lastDBHost = $site['DBHOST'];
			cms_core::$lastDBName = $site['DBNAME'];
			cms_core::$lastDBPrefix = $site['DB_NAME_PREFIX'];
		
		
			while ($user = sqlFetchAssoc($result)) {
				//Remember the User's id and global id, then remove them from the array of data
				$userId = $user['id'];
				$globalId = $user['global_id'];
				unset($user['id']);
				unset($user['global_id']);
			
			
				//Check if we can find a synced User on the remote site, and whether their information is out of date
				$sql = "
					SELECT id, global_id, last_updated_timestamp <= '". sqlEscape($user['last_updated_timestamp']). "' AS outdated
					FROM ". $site['DB_NAME_PREFIX']. "users";
				
				//The rule for ids is:
					//The local id on the hub should always match the global id on the satellites
				//Make sure we match on the right ids, depending on what sites we are syncing:
				if ($thisIsHub) {
					//Hub -> satellite
					$sql .= "
						WHERE global_id = ". (int) $userId;
					$syncedIds[] = $userId;
				
				} elseif ($isHub) {
					//satellite -> hub
					$sql .= "
						WHERE id = ". (int) $globalId;
				
				} else {
					//satellite -> satellite
					$sql .= "
						WHERE global_id = ". (int) $globalId;
				}
				
				$sql .= "
					LIMIT 1";
				
				//Check to see if a linked User exists
				$result2 = sqlSelect($sql);
				if ($linkedUser = sqlFetchAssoc($result2)) {
					//Update the linked User's details.
					//We only need to update the data on the site we are connecting to if it was not more recent than
					//the copy on this site
					if ($linkedUser['outdated']) {
						updateRow('users', $user, $linkedUser['id']);
						++$syncs;
					}
				
				//If this site is a hub, and no User record was found on the satellite, add the User record to the satellite
				} elseif ($thisIsHub) {
					$user['global_id'] = $userId;
					insertRow('users', $user);
					++$syncs;
				}
			}
			
			//For satellite sites, we should look out for any users that have been recently deleted
			//(To stop this lost getting too big we'll limit it to deletions in the last 30 days)
			$deletedIds = array();
			if (!$thisIsHub) {
				$sql = "
					SELECT usl.user_id
					FROM ". $site['DB_NAME_PREFIX']. "user_sync_log AS usl
					LEFT JOIN ". $site['DB_NAME_PREFIX']. "users AS u
					   ON u.id = usl.user_id
					WHERE u.id IS NULL
					  AND usl.last_synced_timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)";
				$result = sqlSelect($sql);
				while ($linkedUser = sqlFetchAssoc($result)) {
					$deletedIds[] = $linkedUser['user_id'];
				}
			}
			connectLocalDB();
			
			if (!$thisIsHub) {
				if (!empty($deletedIds)) {
					$sql = "
						SELECT id
						FROM ". DB_NAME_PREFIX. "users
						WHERE global_id IN (". inEscape($deletedIds, 'numeric'). ")";
					$result = sqlSelect($sql);
					while ($linkedUser = sqlFetchAssoc($result)) {
						deleteUser($linkedUser['id']);
						++$syncs;
					}
				}
			
			//If this is the hub, we should make a log of all of the user accounts that we have synced to other sites
			} else {
				foreach ($syncedIds as $userId) {
					setRow('user_sync_log', array('last_synced_timestamp' => $now), $userId);
				}
			}
			
			return true;
		} else {
			return false;
		}
	}
	
	public static function jobRemoveInactivePendingUsers() {
		if (setting('remove_inactive_users')) {
			$interval = 28;
			$intervalSetting = setting('max_days_user_inactive');
			if ($intervalSetting && is_numeric($intervalSetting)) {
				$interval = $intervalSetting;
			}
			$sql = '
				SELECT id, screen_name, first_name, last_name, email, created_date
				FROM '.DB_NAME_PREFIX.'users
				WHERE status = "pending"
				AND email_verified = 0
				AND created_date < DATE_SUB(NOW(), INTERVAL '.(int)$interval.' DAY)';
			$result = sqlSelect($sql);
			$count = 0;
			$message = '';
			while ($user = sqlFetchAssoc($result)) {
				deleteUser($user['id']);
				$count++;
				$message .= "\n\n--------------------";
				$message .= "\nScreen name: ".$user['screen_name'];
				$message .= "\nFirst name: ".$user['first_name'];
				$message .= "\nLast name: ".$user['last_name'];
				$message .= "\nEmail: ".$user['email'];
				$message .= "\nCreated date: ".formatDateNicely($user['created_date'], '_MEDIUM');
			}
			echo 'Users deleted: '.$count . $message;
			return $count;
		}
		echo 'Remove inactive users not enabled in site settings';
		return false;
	}
}