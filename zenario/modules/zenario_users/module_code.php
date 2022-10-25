<?php
/*
 * Copyright (c) 2022, Tribal Limited
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

	public function handleAJAX() {
		if ($c = $this->runSubClass(__FILE__, 'organizer', 'zenario__users/panels/smart_groups')) {
			return $c->handleAJAX();
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
			$details = ['label' => (string) $id];
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
		&& ze::setting('period_to_delete_the_user_content_access_log') != 0) {
			
			self::clearOldData();
			ze::$userAccessLogged = true;
	
			//Log the hit
			$sql = "
				INSERT IGNORE INTO ". DB_PREFIX. "user_content_accesslog SET
					user_id = ". (int) $_SESSION['extranetUserID']. ",
					hit_datetime = NOW(),
					content_id = ". (int) $cID. ",
					content_type = '". ze\escape::sql($cType). "',
					content_version = ". (int) $cVersion;
			ze\sql::update($sql);
		}
	}
	
	public static function clearOldData() {
		$cleared = 0;
		//Clear user content access log
		$days = ze::setting('period_to_delete_the_user_content_access_log');
		if (is_numeric($days)) {
			$sql = " 
				DELETE FROM ". DB_PREFIX. "user_content_accesslog";
			if ($days && ($date = date('Y-m-d', strtotime('-'.$days.' day', strtotime(date('Y-m-d')))))) {
				$sql .= "
					WHERE hit_datetime < '".ze\escape::sql($date)."'";
			}
			ze\sql::update($sql);
			$cleared += ze\sql::affectedRows();
		}
		
		//Clear user signin log
		$days = ze::setting('period_to_delete_sign_in_log');
		if (is_numeric($days)) {
			$sql = " 
				DELETE FROM ". DB_PREFIX. "user_signin_log";
			if ($days && ($date = date('Y-m-d', strtotime('-'.$days.' day', strtotime(date('Y-m-d')))))) {
				$sql .= "
					WHERE login_datetime < '".ze\escape::sql($date)."'";
			}
			ze\sql::update($sql);
			$cleared += ze\sql::affectedRows();
		}
		return $cleared;
	}
	
	//Check if a content item logs user access
	public static function doesContentItemLogUserAccess($cID, $cType) {
		$privacy = ze\row::get('translation_chains', 'privacy', ['equiv_id' => ze\content::equivId($cID, $cType), 'type' => $cType]);
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
	
	//Various API and internal functions
	
	protected static function savePrivacySettings($tagIds, $values) {
		$equivId = $cType = false;
		foreach ($tagIds as $tagId) {
			if (ze\content::getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
				
				$key = ['link_to' => 'group', 'link_from' => 'chain', 'link_from_id' => $equivId, 'link_from_char' => $cType];
				$chain = ['privacy' => $values['privacy/privacy']];
				
				if ($chain['privacy'] == 'group_members') {
					ze\miscAdm::updateLinkingTable('group_link', $key, 'link_to_id', $values['privacy/group_ids']);
				} else {
					ze\row::delete('group_link', $key);
				}
				
				$key['link_to'] = 'role';
				if ($chain['privacy'] == 'with_role') {
					ze\miscAdm::updateLinkingTable('group_link', $key, 'link_to_id', $values['privacy/role_ids']);
					$chain['at_location'] = $values['privacy/at_location'];
				} else {
					ze\row::delete('group_link', $key);
					$chain['at_location'] = 'any';
				}
				
				$key = ['equiv_id' => $equivId, 'content_type' => $cType];
				if ($chain['privacy'] == 'call_static_method') {
					ze\row::set('translation_chain_privacy', [
						'module_class_name' => $values['privacy/module_class_name'],
						'method_name' => $values['privacy/method_name'],
						'param_1' => $values['privacy/param_1'],
						'param_2' => $values['privacy/param_2']
					], $key);
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
					['equiv_id' => $equivId, 'type' => $cType]);
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
		
		if (ze\cookie::canSet('functionality')) {
			if ($rememberMe) {
				ze\cookie::set('COOKIE_LAST_EXTRANET_EMAIL', $user['email']);
				ze\cookie::set('COOKIE_LAST_EXTRANET_SCREEN_NAME', $user['screen_name']);
			}
			if ($logMeIn) {
				ze\cookie::set('LOG_ME_IN_COOKIE', $user['login_hash']);
			}
		}
	}
		
	protected function loggedInAsParentFor($userId){
		$sql='SELECT id FROM ' . DB_PREFIX . 'users WHERE id=' . (int)$userId  . ' AND parent_id=' . (int) ($_SESSION['extranetUserID']);
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
	
	
	public static function jobRemoveInactivePendingUsers() {
		if (ze::setting('remove_inactive_users')) {
			$interval = 28;
			$intervalSetting = ze::setting('max_days_user_inactive');
			if ($intervalSetting && is_numeric($intervalSetting)) {
				$interval = $intervalSetting;
			}
			$sql = '
				SELECT u.id, u.screen_name, u.first_name, u.last_name, u.email, u.created_date
				FROM '. DB_PREFIX. 'users AS u
				WHERE status = "pending"
				AND email_verified = 0
				AND created_date < DATE_SUB(NOW(), INTERVAL '. (int) $interval. ' DAY)';
			$result = ze\sql::select($sql);
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
				$message .= "\nCreated date: ".ze\admin::formatDate($user['created_date'], '_MEDIUM');
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
			
			$emailSettings =[];
			if($emailTemplate1 && $timeUserInactive1){
				$emailSettings[]=['emailTemplate'=>$emailTemplate1,'period'=>$timeUserInactive1];
			}
			
			if($emailTemplate2 && $timeUserInactive2){
				$emailSettings[]=['emailTemplate'=>$emailTemplate2,'period'=>$timeUserInactive2];
			}
			
			if($emailSettings){
				foreach($emailSettings as $setting){
					$userDetails=self::getInactiveUserDetails($setting['period']);
					
					if(is_array($userDetails) && $userDetails){
						foreach($userDetails as $user){
							$emailMergeFields = [];
							$emailMergeFields['salutation'] = $user['salutation'];
							$emailMergeFields['first_name'] = $user['first_name'];
							$emailMergeFields['last_name'] = $user['last_name'];
							$emailMergeFields['cms_url'] = ze\link::absolute();
							$k++;
							zenario_email_template_manager::sendEmailsUsingTemplate(
																			$user['email'],
																			$setting['emailTemplate'],
																			$emailMergeFields,
																			[],
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
			SELECT u.id, u.salutation, u.first_name, u.last_name, u.email
			FROM ". DB_PREFIX. "users as u";
			
		if ($datasetColumnNameLiveUser){
			$sql .= "
				INNER JOIN ". DB_PREFIX. "users_custom_data AS ucd
				   ON ucd.user_id = u.id";
		}
		$sql .= "
			WHERE u.status = 'active'
			  AND u.last_login BETWEEN '". ze\escape::sql($datasetColumnNameLiveUser). "' AND DATE_ADD('". ze\escape::sql($datasetColumnNameLiveUser). "', INTERVAL 1 DAY)";
			
		if ($datasetColumnNameLiveUser){
			$sql .= "
			  AND ucd.`". ze\escape::sql($datasetColumnNameLiveUser). "` = 1";
		}

		$result = ze\sql::select($sql);
		$users = [];
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
		
		ze\fileAdm::exitIfUploadError(true, false, true, 'Filedata');
		
		$imageId = ze\file::addToDatabase('user', $_FILES['Filedata']['tmp_name'], rawurldecode($_FILES['Filedata']['name']), true);
		if ($imageId) {
			foreach (explode(',', $userIds) as $userId) {
				ze\row::update('users', ['image_id' => $imageId], $userId);
			}
			ze\contentAdm::deleteUnusedImagesByUsage('user');
		}
	}
	
	public static function deleteUserImage($userIds) {
		foreach (explode(',', $userIds) as $userId) {
			ze\row::update('users', ['image_id' => 0], $userId);
		}
		ze\contentAdm::deleteUnusedImagesByUsage('user');
	}
	
	public function suspendUser($userId) {
		$cols = [];
		ze\admin::setLastUpdated($cols, $creating = false);
		$cols['modified_date'] = $cols['last_edited'];
		$cols['suspended_date'] = $cols['last_edited'];
		unset($cols['last_edited']);
		$cols['status'] = 'suspended';
		
		ze\row::update('users', $cols, $userId);
		
		ze\module::sendSignal("eventUserStatusChange", ["userId" => $userId, "status" => "suspended"]);
	}
	
	public static function requestVarMergeField($name) {
		switch ($name) {
			//Allow a user's first/last name to be displayed
			case 'name':
				return ze\user::name(ze::$vars['userId']);
		}
	}
	public static function requestVarDisplayName($name) {
		switch ($name) {
			case 'name':
				return 'User first and last name';
		}
	}
	
	public static function formatConsentUser($consentId) {
		$consent = ze\row::get('consents', ['first_name', 'last_name', 'email'], $consentId);
		$user = trim($consent['first_name'] . ' ' . $consent['last_name']);
		if ($user && $consent['email']) {
			$user .= ' (' . $consent['email'] . ')';
		} elseif ($consent['email']) {
			$user = $consent['email'];
		}
		return $user;
	}
	//To show roles and sub-roles
	public static function getRoleTypesIndexedByIdOrderedByName(){
		$ZENARIO_ORGANIZATION_MANAGER_PREFIX = ze\module::prefix('zenario_organization_manager'); 
		$rv = [];
		$ord = 0;
		$sql = "SELECT 
					id,
					parent_id,
					name
				FROM " . 
					DB_PREFIX . $ZENARIO_ORGANIZATION_MANAGER_PREFIX . "user_location_roles
				ORDER BY name";
		$result = ze\sql::select($sql);
		while($row = ze\sql::fetchAssoc($result)){
			$rv[$row['id']] = ['label' => $row['name'], 'parent' => $row['parent_id'], 'ord' => ++$ord];
		}
		return $rv;
		
	
	}
	
}