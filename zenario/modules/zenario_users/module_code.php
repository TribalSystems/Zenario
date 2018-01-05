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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

class zenario_users extends ze\moduleBaseClass {

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
		if ($details = ze\dataset::fieldDetails($field, 'users')) {
		
			//Attempt to convert back to the old format.
			$details['name'] = $details['db_column'];
			$details['ordinal'] = $details['ord'];
			$details['help_text'] = $details['note_below'];
			$details['show_in_organizer_panel'] = $details['organizer_visibility'] != 'none';
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
		if ($details = ze\row::get('custom_dataset_field_values', true, $id)) {
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
		&& ze::setting('log_user_access')) {
			
			self::clearOldData();
			ze::$userAccessLogged = true;
	
			//Log the hit
			$sql = "
				INSERT IGNORE INTO ". DB_NAME_PREFIX. "user_content_accesslog SET
					user_id = ". (int) $_SESSION['extranetUserID']. ",
					hit_datetime = NOW(),
					ip = '". ze\escape::sql(ze\user::ip()). "',
					content_id = ". (int) $cID. ",
					content_type = '". ze\escape::sql($cType). "',
					content_version = ". (int) $cVersion;
			ze\sql::update($sql);
		}
	}
	
	public static function clearOldData(){
		if($days = ze::setting('period_to_delete_the_user_content_access_log')){
			if(is_numeric($days)){
				$today = date('Y-m-d');
				$date = date('Y-m-d', strtotime('-'.$days.' day', strtotime($today)));
				if($date){
					$sql = " 
						DELETE FROM ". DB_NAME_PREFIX. "user_content_accesslog 
						WHERE hit_datetime < '".ze\escape::sql($date)."'";
					ze\sql::update($sql);
				}
			}
		}
	
	}
	
	//Check if a content item logs user access
	public static function doesContentItemLogUserAccess($cID, $cType) {
		$privacy = ze\row::get('translation_chains', 'privacy', array('equiv_id' => ze\content::equivId($cID, $cType), 'type' => $cType));
		return !ze::in($privacy, 'public', 'logged_out');
	}
	
	
	public static function  setUserGroupOrBoolean($userId, $characteristic_id, $bool) {
		$rv = zenario_users::getCharacteristic($characteristic_id);
		if($rv){
			switch($rv['type']) {
				case 'boolean':
				case 'group':
					ze\row::update('users_custom_data', [$rv['name'] => $bool], $userId);
					break;
			}
		}
	}
	
	public function fillAdminToolbar(&$adminToolbar, $cID, $cType, $cVersion) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	
	
	//These are just for testing...
	public static function returnTrue() {
		return true;
	}
	
	public static function returnNull() {
		return null;
	}
	
	public static function returnZero() {
		return 0;
	}
	
	public static function returnEmptyString() {
		return '';
	}
	
	public static function returnFalse() {
		return false;
	}
	
	
	
	
	
	
	
	//Various API and internal functions
	
	protected static function savePrivacySettings($tagIds, $values) {
		$equivId = $cType = false;
		foreach ($tagIds as $tagId) {
			if (ze\content::getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
				
				$key = array('link_to' => 'group', 'link_from' => 'chain', 'link_from_id' => $equivId, 'link_from_char' => $cType);
				$chain = array('privacy' => $values['privacy/privacy']);
				
				if ($chain['privacy'] == 'group_members') {
					ze\miscAdm::updateLinkingTable('group_link', $key, 'link_to_id', $values['privacy/group_ids']);
				} else {
					ze\row::delete('group_link', $key);
				}
				
				$key['link_to'] = 'role';
				if ($chain['privacy'] == 'with_role') {
					ze\miscAdm::updateLinkingTable('group_link', $key, 'link_to_id', $values['privacy/role_ids']);
				} else {
					ze\row::delete('group_link', $key);
				}
				
				$key = array('equiv_id' => $equivId, 'content_type' => $cType);
				if ($chain['privacy'] == 'call_static_method') {
					ze\row::set('translation_chain_privacy', array(
						'module_class_name' => $values['privacy/module_class_name'],
						'method_name' => $values['privacy/method_name'],
						'param_1' => $values['privacy/param_1'],
						'param_2' => $values['privacy/param_2']
					), $key);
				} else {
					ze\row::delete('translation_chain_privacy', $key);
				}
				
				if (ze::in($chain['privacy'], 'in_smart_group', 'logged_in_not_in_smart_group')) {
					$chain['smart_group_id'] = $values['privacy/smart_group_id'];
				} else {
					$chain['smart_group_id'] = 0;
				}
				
				//Save the privacy settings
				ze\row::set(
					'translation_chains',
					$chain,
					array('equiv_id' => $equivId, 'type' => $cType));
			}
		}
	}
	
	protected function impersonateUser($userId, $logAdminOut = false, $rememberMe = false, $logMeIn = false) {
		
		//Log the admin out of admin mode
		if ($logAdminOut) {
			ze\admin::unsetSession(false);
		}
		
		//Log the admin in as the target user
		$user = ze\user::logIn($userId, true);
		$_SESSION['extranetUserImpersonated'] = true;
		
		if ($rememberMe) {
			ze\cookie::set('COOKIE_LAST_EXTRANET_EMAIL', $user['email']);
			ze\cookie::set('COOKIE_LAST_EXTRANET_SCREEN_NAME', $user['screen_name']);
		}
		if ($logMeIn) {
			ze\cookie::set('LOG_ME_IN_COOKIE', $user['login_hash']);
		}
	}
		
	protected function loggedInAsParentFor($userId){
		$sql='SELECT id FROM ' . DB_NAME_PREFIX . 'users WHERE id=' . (int)$userId  . ' AND parent_id=' . (int) ($_SESSION['extranetUserID']);
		return (ze\sql::numRows(ze\sql::select($sql))==1);
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
		
		if (ze::isError($result)) {
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
			
			if ($dbSelected = ze\db::connect($hub['DBHOST'], $hub['DBNAME'], $hub['DBUSER'], $hub['DBPASS'], ($hub['DBPORT'] ?? false))) {
				ze::$lastDB = $dbSelected;
				ze::$lastDBHost = $hub['DBHOST'];
				ze::$lastDBName = $hub['DBNAME'];
				ze::$lastDBPrefix = $hub['DB_NAME_PREFIX'];
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
		$now = ze\date::now();
		
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
			ze\site::setSetting('user_last_sync_time', $now, $updateDB = true, $encrypt = false, $clearCache = false);
		}
		
		if ($success) {
			return $syncs > 0;
		} else {
			return new ze\error('Database connection could not be established');
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
				last_login_ip,
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
		
		if (ze::setting('user_last_sync_time')) {
			$sql .= "
			  AND last_updated_timestamp >= '". ze\escape::sql(ze::setting('user_last_sync_time')). "'";
		}
		
		$result = ze\sql::select($sql);
		
		//Connect to the other site
		if ($dbSelected = ze\db::connect($site['DBHOST'], $site['DBNAME'], $site['DBUSER'], $site['DBPASS'], ($site['DBPORT'] ?? false))) {
			ze::$lastDB = $dbSelected;
			ze::$lastDBHost = $site['DBHOST'];
			ze::$lastDBName = $site['DBNAME'];
			ze::$lastDBPrefix = $site['DB_NAME_PREFIX'];
		
		
			while ($user = ze\sql::fetchAssoc($result)) {
				//Remember the User's id and global id, then remove them from the array of data
				$userId = $user['id'];
				$globalId = $user['global_id'];
				unset($user['id']);
				unset($user['global_id']);
			
			
				//Check if we can find a synced User on the remote site, and whether their information is out of date
				$sql = "
					SELECT id, global_id, last_updated_timestamp <= '". ze\escape::sql($user['last_updated_timestamp']). "' AS outdated
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
				$result2 = ze\sql::select($sql);
				if ($linkedUser = ze\sql::fetchAssoc($result2)) {
					//Update the linked User's details.
					//We only need to update the data on the site we are connecting to if it was not more recent than
					//the copy on this site
					if ($linkedUser['outdated']) {
						ze\row::update('users', $user, $linkedUser['id']);
						++$syncs;
					}
				
				//If this site is a hub, and no User record was found on the satellite, add the User record to the satellite
				} elseif ($thisIsHub) {
					$user['global_id'] = $userId;
					ze\row::insert('users', $user);
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
				$result = ze\sql::select($sql);
				while ($linkedUser = ze\sql::fetchAssoc($result)) {
					$deletedIds[] = $linkedUser['user_id'];
				}
			}
			ze\db::connectLocal();
			
			if (!$thisIsHub) {
				if (!empty($deletedIds)) {
					$sql = "
						SELECT id
						FROM ". DB_NAME_PREFIX. "users
						WHERE global_id IN (". ze\escape::in($deletedIds, 'numeric'). ")";
					$result = ze\sql::select($sql);
					while ($linkedUser = ze\sql::fetchAssoc($result)) {
						ze\userAdm::delete($linkedUser['id']);
						++$syncs;
					}
				}
			
			//If this is the hub, we should make a log of all of the user accounts that we have synced to other sites
			} else {
				foreach ($syncedIds as $userId) {
					ze\row::set('user_sync_log', array('last_synced_timestamp' => $now), $userId);
				}
			}
			
			return true;
		} else {
			return false;
		}
	}
	
	public static function jobRemoveInactivePendingUsers() {
		if (ze::setting('remove_inactive_users')) {
			$interval = 28;
			$intervalSetting = ze::setting('max_days_user_inactive');
			if ($intervalSetting && is_numeric($intervalSetting)) {
				$interval = $intervalSetting;
			}
			$sql = '
				SELECT u.id, [u.screen_name], [u.first_name], [u.last_name], [u.email], [u.created_date]
				FROM [users AS u]
				WHERE status = "pending"
				AND email_verified = 0
				AND created_date < DATE_SUB(NOW(), INTERVAL [0] DAY)';
			$result = ze\sql::select($sql, [$interval]);
			$count = 0;
			$message = '';
			while ($user = ze\sql::fetchAssoc($result)) {
				ze\userAdm::delete($user['id']);
				$count++;
				$message .= "\n\n--------------------";
				$message .= "\nScreen name: ".$user['screen_name'];
				$message .= "\nFirst name: ".$user['first_name'];
				$message .= "\nLast name: ".$user['last_name'];
				$message .= "\nEmail: ".$user['email'];
				$message .= "\nCreated date: ".ze\date::format($user['created_date'], '_MEDIUM');
			}
			echo 'Users deleted: '.$count . $message;
			return $count;
		}
		echo 'Remove inactive users not enabled in site settings';
		return false;
	}
	
	
	
	
	
	public static function jobSendInactiveUserEmail(){
			$k=0;
			$emailTemplate1 = ze::setting('inactive_user_email_template_1');
			$emailTemplate2 = ze::setting('inactive_user_email_template_2');
			
			$timeUserInactive1 = ze::setting('time_user_inactive_1');
			$timeUserInactive2 = ze::setting('time_user_inactive_2');
			
			$emailSettings =array();
			if($emailTemplate1 && $timeUserInactive1){
				$emailSettings[]=array('emailTemplate'=>$emailTemplate1,'period'=>$timeUserInactive1);
			}
			
			if($emailTemplate2 && $timeUserInactive2){
				$emailSettings[]=array('emailTemplate'=>$emailTemplate2,'period'=>$timeUserInactive2);
			}
			
			if($emailSettings){
				foreach($emailSettings as $setting){
					$userDetails=self::getInactiveUserDetails($setting['period']);
					
					if(is_array($userDetails) && $userDetails){
						foreach($userDetails as $user){
							$emailMergeFields = array();
							$emailMergeFields['salutation'] = $user['salutation'];
							$emailMergeFields['first_name'] = $user['first_name'];
							$emailMergeFields['last_name'] = $user['last_name'];
							$emailMergeFields['cms_url'] = ze\link::absolute();
							$k++;
							zenario_email_template_manager::sendEmailsUsingTemplate(
																			$user['email'],
																			$setting['emailTemplate'],
																			$emailMergeFields,
																			array(),
																			false,
																			true
																			);
						}
					}
				}
			}else{
				echo "The email template and the user inactivity period are unset in the site settings. <br>";
			}
		
		if ($k > 1 || $k == 0) {
			echo "Sent " . $k . " inactive user emails.";
		} else {
			echo "Sent " . $k . " inactive user emails.";
		}
		
		if($k) {
			return true;
		} else {
			return false;
		}
	}
	
	public static function getInactiveUserDetails($period){
		$date = self::getInactiveDate($period);

		if(!$date){
			return false;
		}
			
		//Live users
		$datasetId= ze::setting('user_dataset_field_to_receive_emails');
		$datasetColumnNameLiveUser = false;
		if($datasetId){
			$datasetDetails=ze\dataset::fieldDetails($datasetId);
			if(is_array($datasetDetails)){
				$datasetColumnNameLiveUser = $datasetDetails['db_column'];
			}else{
				$datasetColumnNameLiveUser = false;
			}
		}

		$sql = "
			SELECT [u.id], [u.salutation], [u.first_name], [u.last_name], [u.email]
			FROM [users as u]";
			
		if ($datasetColumnNameLiveUser){
			$sql .= "
				INNER JOIN [users_custom_data AS ucd]
				   ON ucd.user_id = u.id";
		}
		$sql .= "
			WHERE u.status = 'active'
			  AND u.last_login BETWEEN [date] AND DATE_ADD([date], INTERVAL 1 DAY)";
			
		if ($datasetColumnNameLiveUser){
			$sql .= "
			  AND ucd.`". ze\escape::sql($datasetColumnNameLiveUser). "` = 1";
		}

		$result = ze\sql::select($sql, ['date' => $date]);
		$users = array();
		while ($row = ze\sql::fetchAssoc($result)) {
			$users[] = $row;
		}
		
		if($users){
			return $users;
		}
		return false;
	}
	
	
	public static function getInactiveDate($period){
		switch($period){
			case '2_weeks':
				return date("Y-m-d",strtotime("-2 weeks"));
				break;

			case '3_weeks':
				return date("Y-m-d",strtotime("-3 weeks"));
				break;
		
			case '4_weeks':
				return date("Y-m-d",strtotime("-4 weeks"));
				break;
			case '6_weeks':
				return date("Y-m-d",strtotime("-6 weeks"));
				break;
			
			case '2_months':
				return date("Y-m-d",strtotime("-2 months"));
				break;
			case '3_months':
				return date("Y-m-d",strtotime("-3 months"));
				break;
			case '6_months':
				return date("Y-m-d",strtotime("-6 months"));
				break;
			case '9_months':
				return date("Y-m-d",strtotime("-9 months"));
				break;
			case '1_year':
				return date("Y-m-d",strtotime("-1 year"));
				break;
			
		}
			
		return false;
	}
	
	public static function uploadUserImage($userIds) {
		$imageId = ze\file::addToDatabase('user', $_FILES['Filedata']['tmp_name'], rawurldecode($_FILES['Filedata']['name']), true);
		if ($imageId) {
			foreach (explode(',', $userIds) as $userId) {
				ze\row::update('users', array('image_id' => $imageId), $userId);
			}
			ze\contentAdm::deleteUnusedImagesByUsage('user');
		}
	}
	
	public static function deleteUserImage($userIds) {
		foreach (explode(',', $userIds) as $userId) {
			ze\row::update('users', array('image_id' => 0), $userId);
		}
		ze\contentAdm::deleteUnusedImagesByUsage('user');
	}
	
	public function suspendUser($userId) {
		$sql ="
			UPDATE " . DB_NAME_PREFIX . "users
			SET 
				status='suspended',
				suspended_date=NOW()
			WHERE
				id = " . (int)$userId;
		ze\sql::update($sql);
		ze\module::sendSignal("eventUserStatusChange",array("userId" => $userId, "status" => "suspended"));
	}
	
}