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

class zenario_users extends module_base_class {

	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->preFillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->fillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		} else {
			switch ($path) {
				case 'zenario__users/panels/all_fields':
					$panel['items'] = listCustomFields('users', false, false, false);
					break;
				
				case 'zenario__users/panels/boolean_and_list_only':
					$panel['items'] = listCustomFields('users', false, 'boolean_and_list_only');
					break;
				
				case 'zenario__users/panels/text_only':
					$panel['items'] = listCustomFields('users', false, 'text_only');
					break;
				
				case 'zenario__users/panels/country_only':
					$panel['items'] = listCustomFields('users', false, 'country_only');
					break;
				
				case 'zenario__users/panels/boolean_and_groups_only':
					$panel['items'] = listCustomFields('users', false, 'boolean_and_groups_only');
					break;
				
				case 'zenario__users/panels/groups_only':
					$panel['items'] = listCustomFields('users', false, 'groups_only');
					break;
			}
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
		&& self::doesContentItemLogUserAccess($cID, $cType, $cVersion)) {
	
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
		return getRow('translation_chains', 'log_user_access', array('equiv_id' => equivId($cID, $cType), 'type' => $cType));
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
			$item = getRow('translation_chains', array('privacy', 'log_user_access'), array('equiv_id' => cms_core::$equivId, 'type' => cms_core::$cType));
		
			if (!empty($item['log_user_access']) && in($item['privacy'], 'all_extranet_users', 'group_members', 'specific_users')) {
				$adminToolbar['sections']['history']['buttons']['zenario_users__access_log']['organizer_quick']['path'] =
					'zenario__content/panels/content/user_access_log//'. $cType. '_'. $cID. '//';
		
			} else {
				unset($adminToolbar['sections']['history']['buttons']['zenario_users__access_log']);
			}
		}
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	public static function advancedSearchTableJoins($path, $values, $tablePrefix) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	public static function advancedSearchWhereStatement($path, $values, $tablePrefix) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	
	
	//Various API and internal functions
	

	

	
	public function createSmartGroupFieldSet(&$box, $index) {
		$myfields = &$box['tabs']['first_tab']['fields'];
		
		$rule_key = 'rule_type_' . $index;
		$myfields[$rule_key] = $myfields['rule_type'];
		$myfields[$rule_key]['ord'] = 101 + $index * 10;
		unset($myfields[$rule_key]['value']);
		unset($myfields[$rule_key]['current_value']);
		
		$rule_key = 'rule_delete_' . $index;
		$myfields[$rule_key] = $myfields['rule_delete'];
		$myfields[$rule_key]['ord'] = 102 + $index * 10;
		$myfields[$rule_key]['value'] = adminPhrase("Delete rule");
		
		$rule_key = 'rule_group_picker_' . $index;
		$myfields[$rule_key] = $myfields['rule_group_picker'];
		$myfields[$rule_key]['ord'] = 103 + $index * 10;
		unset($myfields[$rule_key]['value']);
		unset($myfields[$rule_key]['current_value']);
		
		$rule_key = 'rule_characteristic_picker_' . $index;
		$myfields[$rule_key] = $myfields['rule_characteristic_picker'];
		$myfields[$rule_key]['ord'] = 104 + $index * 10;
		unset($myfields[$rule_key]['value']);
		unset($myfields[$rule_key]['current_value']);
		
		$rule_key = 'rule_characteristic_values_picker_' . $index;
		$myfields[$rule_key] = $myfields['rule_characteristic_values_picker'];
		$myfields[$rule_key]['ord'] = 105 + $index * 10;
		unset($myfields[$rule_key]['value']);
		unset($myfields[$rule_key]['current_value']);
		
		$rule_key = 'rule_logic_' . $index;
		$myfields[$rule_key] = $myfields['rule_logic'];
		$myfields[$rule_key]['ord'] = 106 + $index * 10;
		unset($myfields[$rule_key]['value']);
		unset($myfields[$rule_key]['current_value']);
		
		$rule_key = 'rule_separator_' . $index;
		$myfields[$rule_key] = $myfields['rule_separator'];
		$myfields[$rule_key]['ord'] = 107 + $index * 10;
		unset($myfields[$rule_key]['value']);
		unset($myfields[$rule_key]['current_value']);
		
		$myfields['rule_create']['ord'] = 108 + $index * 10;
		$myfields['rule_create']['value'] = "Create rule";
		
	}
	
	public function deleteSmartGroupFieldSet(&$box, $index, $prevIndex) {
		$myfields = &$box['tabs']['first_tab']['fields'];
		unset($myfields['rule_type_' . $index]);
		unset($myfields['rule_delete_' . $index]);
		unset($myfields['rule_group_picker_' . $index]);
		unset($myfields['rule_logic_' . $index]);
		unset($myfields['rule_separator_' . $index]);
		
		$rule_key = 'rule_separator_' . $prevIndex;
		if (isset($myfields[$rule_key])) {
			$myfields[$rule_key]['hidden'] = 'true';
		}
		
		unset($myfields['rule_characteristic_picker_' . $index]);
		unset($myfields['rule_characteristic_values_picker_' . $index]);
		
	}

	public static function smartGroupInclusionsDescription($values) {
		$pieces = array();
		$indexes = explode(',', arrayKey($values, 'first_tab', 'indexes'));
		foreach ($indexes as $index) {
			if ($index && (arrayKey($values, 'first_tab', 'rule_type_'. $index)=='characteristic')) {
				$pieces[] = self::smartGroupDescriptionCharacteristic($values, 'first_tab', $index);
			}
			if ($index && (arrayKey($values, 'first_tab', 'rule_type_' . $index)=='group')) {
				$groups = array();
				foreach (explode(',', arrayKey($values, 'first_tab', 'rule_group_picker_' . $index)) as $groupId) {
					if ($groupId) {
						$groups[] = '"' . getGroupLabel($groupId) . '"';		
					}
				}
				$pieces[] = adminPhrase("in group" . (count($groups)>1?'s (':' ') . "[[groups_list]]" . (count($groups)>1?')':''),
												array	(
														'groups_list' =>  implode(arrayKey($values, 'first_tab', 'rule_logic_' . $index)=='all'?' AND ': ' OR ', $groups)
														)
												);
			}
		}
		return implode(' AND ', $pieces);
	}
	
	public static function smartGroupExclusionsDescription($values) {
		$rv = '';
		if (arrayKey($values, 'exclude', 'rule_type')=='characteristic') {
			$rv = self::smartGroupDescriptionCharacteristic($values, 'exclude', 0);
		}
		
		if (arrayKey($values, 'exclude', 'rule_type')=='group') {
			$groups = array();
			foreach (explode(',', arrayKey($values, 'exclude', 'rule_group_picker')) as $groupId) {
				if ($groupId) {
					$groups[] = '"' . getGroupLabel($groupId) .'"';
				}
			}
		
			$rv = adminPhrase("in group" . (count($groups)>1?'s (':' ') . "[[groups_list]]" . (count($groups)>1?')':''),
					array	(
							'groups_list' =>  implode(arrayKey($values, 'exlude', 'rule_logic')=='all'?' AND ': ' OR ', $groups)
					)
			);
		}
		
		return $rv;
	}
		
	public static function smartGroupDescriptionCharacteristic($values, $tab, $index) {
		$index_idx = ($index?('_' . $index):'');
		$C = zenario_users::getCharacteristic(arrayKey($values, $tab , 'rule_characteristic_picker' .  $index_idx));
		switch ($C['type']) {
			case 'list_single_select':
			case 'list_multi_select':
				$VIds = explode(',', arrayKey($values, $tab , 'rule_characteristic_values_picker' .  $index_idx));
				foreach ($VIds as $VId) {
					if ($VId) {
						$V = zenario_users::getCharacteristicValue($VId);
						$VNames[] = '"' . $V['label'] . '"';
					}
				}
				return adminPhrase("with characteristic [[characteristic_name]] set to " . (count($VNames)>1?'(':'') ."[[characterisic_values]]" . (count($VNames)>1?')':''),
						array(
								'characteristic_name' => '"' . $C['label'] . '"',
								'characterisic_values' => implode(arrayKey($values, $tab , 'rule_logic' .  $index_idx)=='all'?' AND ': ' OR ', $VNames)
						)
				);
				break;
			case 'boolean':
				return adminPhrase("with characteristic [[characteristic_name]]", array('characteristic_name' => '"' . $C['label'] . '"'));
				break;
		}
	}


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
						'privacy' => $values['privacy/privacy'],
						'log_user_access' => in($values['privacy/privacy'], 'all_extranet_users', 'group_members', 'specific_users') && $values['privacy/log_user_access']),
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
	
	protected function impersonateUser($userId, $logAdminOut = false) {
		//Log the admin out of admin mode
		if ($logAdminOut) {
			require_once CMS_ROOT.  'zenario/includes/admin.inc.php';
			unsetAdminSession(false);
		}
		
		//Log the admin in as the target user
		$details = getUserDetails($userId);
	
		$_SESSION["extranetUserImpersonated"] = true;
		$_SESSION["extranetUserID"] = $userId;
		$_SESSION["extranetUser_firstname"] = $details['first_name'];
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
				screen_name,
				password,
				password_needs_changing,
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
}