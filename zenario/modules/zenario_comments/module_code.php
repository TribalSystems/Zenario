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





class zenario_comments extends zenario_anonymous_comments {
	
	
	
	function runCheckPrivs() {
		
		$this->modPrivs =
			checkPriv('_PRIV_MODERATE_USER_COMMENTS') || (
					$this->setting('enable_extranet_moderators')
				 && $this->setting('moderators')
				 && userId()
				 && checkUserInGroup($this->setting('moderators'))
			);
		
		$this->postPrivs =
			userId() && (
					$this->modPrivs
				 || !$this->setting('enable_posting_restrictions')
				 || !$this->setting('restrict_posting_to_group')
				 || checkUserInGroup($this->setting('restrict_posting_to_group'))
			);
		
		if (($this->modPrivs || userId()) && empty($_SESSION['confirm_key'])) {
			$_SESSION['confirm_key'] = randomString();
		}
	}
	
	
	function showSlot() {
		if (!$this->show) {
			return;
		}
		
		if (userId()) {
			$this->updateUserLatestActivity(userId());
		}
		
		$mode = $this->mode;
		$this->$mode();
	}
	
	
	//Show detailed information on one user, to appear next to their post
	function showUserInfo(&$mergeFields, &$sections, $userId, $post = false) {
		if (!$userId || !setting('user_use_screen_name')) {
			$mergeFields['Username'] = $mergeFields['Username_Link'] = htmlspecialchars($this->phrase('_ANONYMOUS'));
		
			if ($this->setting('show_user_avatars')) {
				$sections['No_Avatar'] = $sections['Posting_No_Avatar'] = true;
			}
		
			return;
		}
		
		$sql = "
			SELECT
				latest_activity > NOW() - INTERVAL 10 MINUTE AS `online`,
				post_count
			FROM ". DB_NAME_PREFIX. ZENARIO_COMMENTS_PREFIX. "users
			WHERE user_id = ". (int) $userId;
		$result = sqlQuery($sql);
		
		$forumDetails = sqlFetchAssoc($result);
		$coreDetails = getRow('users', array('screen_name', 'created_date', 'image_id'), $userId);
		
		if ($this->setting('show_user_avatars')) {
			$width = $height = $url = false;
			if ($coreDetails['image_id']
			 && imageLink($width, $height, $url, $coreDetails['image_id'], $this->setting('avatar_width'), $this->setting('avatar_height'))) {
				$sections['Show_Avatar'] = $sections['Posting_Show_Avatar'] = true;
				$mergeFields['Width'] = $width;
				$mergeFields['Height'] = $height;
				$mergeFields['Src'] = htmlspecialchars($url);
			} else {
				$sections['No_Avatar'] = $sections['Posting_No_Avatar'] = true;
			}
		}
		
		if (setting('user_use_screen_name')) {
			$mergeFields['Username'] = htmlspecialchars($coreDetails['screen_name']);
			$mergeFields['Username_Link'] = $this->getUserScreenNameLink($userId, $coreDetails['screen_name']);
		}
		
		if ($this->setting('show_user_job_titles')) {
			$sections['Show_User_Title'] = $sections['Posting_Show_Title'] = $this->getUserTitles($userId);
		}
		
		if ($this->setting('show_user_online_status')) {
			if ($forumDetails['online']) {
				$sections['Show_Online'] = $sections['Posting_Show_Online'] = true;
			} else {
				$sections['Show_Offline'] = $sections['Posting_Show_Offline'] = true;
			}
		}
		
		if ($this->setting('show_user_post_counts')) {
			$sections['Show_Post_Count'] = $sections['Posting_Show_Post_Count'] = true;
			$mrg = array('post_count' => $forumDetails['post_count']);
			$mergeFields['Post_Count'] = $this->phrase('_POST_COUNT', $mrg);
		}
		
		if ($this->setting('show_user_join_dates')) {
			$sections['Show_Join_Date'] = $sections['Posting_Show_Join_Date'] = true;
			$mrg = array('date' => formatDateNicely($coreDetails['created_date'], $this->setting('date_format')));
			$mergeFields['Join_Date'] = $this->phrase('_JOIN_DATE', $mrg);
		}
	}
	
	function updateUserLatestActivity($userId) {
		$sql = "
			UPDATE ". DB_NAME_PREFIX. ZENARIO_COMMENTS_PREFIX. "users SET
				latest_activity = NOW()
			WHERE user_id = ". (int) $userId;
		sqlUpdate($sql, false);
	}
	
	function canDeletePost(&$post) {
		if (isset($post['first_post']) && $post['first_post']) {
			return false;
		} elseif ($this->modPrivs) {
			return true;
		} else {
			return userId()
				&& $this->setting('allow_user_delete_own_post')
				&& $post['poster_id'] == userId()
				&& !$this->locked();
		}
	}
	
	
	function canEditPost(&$post) {
		if (isset($post['first_post']) && $post['first_post']) {
			return false;
		} elseif ($this->modPrivs) {
			return true;
		} else {
			return userId()
				&& $this->setting('allow_user_edit_own_post')
				&& $post['poster_id'] == userId()
				&& !$this->locked()
				&& !($this->setting('comments_require_approval') && $post['status'] == 'published');
		}
	}
	
	
	function canReportPost() {
		return $this->setting('enable_report_a_post') && $this->setting('email_template_for_reports') && 
					($this->setting('enable_anonymous_report_a_post') || userId());
	}
	
	
	function canSubsThread() {
		return userId() && $this->setting('enable_subs');
	}
	
	function couldSubsThread() {
		return !userId() && $this->setting('enable_subs') && !$this->locked();
	}
	
	function hasSubsThread() {
		return checkRowExists(
			ZENARIO_COMMENTS_PREFIX. 'user_subscriptions',
			array(
				'user_id' => userId(),
				'content_id' => $this->cID,
				'content_type' => $this->cType,
				'forum_id' => 0,
				'thread_id' => 0));
	}
	
	protected function subs($subs, $thread = true) {
		$key = array(
			'user_id' => userId(),
			'content_id' => $this->cID,
			'content_type' => $this->cType,
			'forum_id' => 0,
			'thread_id' => 0);
		
		if ($subs) {
			setRow(
				ZENARIO_COMMENTS_PREFIX. 'user_subscriptions',
				array('date_subscribed' => now()),
				$key);
		
		} else {
			deleteRow(
				ZENARIO_COMMENTS_PREFIX. 'user_subscriptions',
				$key);
		}
	}
	
	
	//Check the mirror table to the users table for a given user, and add them in if needed
	function checkUserIsInForumsUserTable($userId) {
		if (!checkRowExists(ZENARIO_COMMENTS_PREFIX. 'users', array('user_id' => $userId))) {
			$sql = "
				INSERT INTO ". DB_NAME_PREFIX. ZENARIO_COMMENTS_PREFIX. "users
					(user_id, latest_activity)
				VALUES
					(". (int) $userId. ", NOW())";
			sqlUpdate($sql, false);
		}
	}
	
	//Add a comment onto the thread
	function addReply($userId, &$messageText, $firstPost = 0, $name = '', $email = '') {
		
		//Make sure that thid user has an entry in the forum user details table
		$this->checkUserIsInForumsUserTable($userId);
		
		zenario_anonymous_comments::addReply($userId, $messageText, $firstPost);
		
		//Update the user's post count
		$sql = "
			UPDATE ". DB_NAME_PREFIX. ZENARIO_COMMENTS_PREFIX. "users SET
				post_count = post_count + 1
			WHERE user_id = ". (int) $userId;
		
		$result = sqlQuery($sql);
	}
	
	
	function showPostScreenTopFields($titleText) {
		
	}
	
	protected static function sendEmailNotificationToSubscribers($commentId, $newPost = true) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	
	
	//Remove a comment from the thread
	function deletePost() {
		zenario_anonymous_comments::deletePost();
		
		//Update the user's post count
		$sql = "
			UPDATE ". DB_NAME_PREFIX. ZENARIO_COMMENTS_PREFIX. "users SET
				post_count = post_count - 1
			WHERE post_count > 0
			  AND user_id = ". (int) $this->post['poster_id'];
		
		$result = sqlQuery($sql);
	}

	function deletePostById($id) {
		$posterId = getRow(ZENARIO_ANONYMOUS_COMMENTS_PREFIX . 'user_comments', 'poster_id', array('id' =>  $id ));
		
		zenario_anonymous_comments::deletePostById($id);
		
		//Update the user's post count
		$sql = "
			UPDATE "
					. DB_NAME_PREFIX. ZENARIO_COMMENTS_PREFIX. "users 
			SET
				post_count = post_count - 1
			WHERE 
					post_count > 0
			  	AND user_id = ". (int) $posterId;
		
		$result = sqlQuery($sql);
	}
	
	public static function eventUserDeleted($userId) {
		$sql = "
			DELETE FROM ". DB_NAME_PREFIX. ZENARIO_COMMENTS_PREFIX. "users
			WHERE user_id = ". (int) $userId;
		sqlQuery($sql);
		
		$sql = "
			UPDATE ". DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "user_comments SET
				poster_id = 0,
				employee_post = 0
			WHERE poster_id = ". (int) $userId;
		sqlQuery($sql);
		
		$sql = "
			UPDATE ". DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "user_comments SET
				updater_id = 0
			WHERE updater_id = ". (int) $userId;
		sqlQuery($sql);
	}
	
	public static function userLastPost($userId = false) {
		if ($userId === false && userId()) {
			$userId = userId();
		}
		
		if ($userId) {
			$sql = "
				SELECT content_id, content_type, date_posted, message_text
				FROM ". ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "user_comments
				WHERE poster_id = ". (int) $userId. "
				ORDER BY date_posted DESC
				LIMIT 1";
			
			$result = sqlQuery($sql);
			if ($row = sqlFetchAssoc($result)) {
				return $row;
			}
		}
		
		return false;
	}
	
	public static function userLatestActivity($userId = false) {
		if ($userId === false && userId()) {
			$userId = userId();
		}
		
		if ($userId) {
			return getRow(
				ZENARIO_COMMENTS_PREFIX. 'users',
				'latest_activity',
				array('user_id' => (int) $userId));
		} else {
			return false;
		}
	}
	
	public static function userLatestActivityDays($userId = false) {
		$rv = false;
		if ($latestActivityOn = self::userLatestActivity($userId)) {
			$sql = "SELECT DATEDIFF(NOW(), '" . $latestActivityOn  . "') as last_activity_days ";
			if (($result = sqlQuery($sql)) && ($row = sqlFetchAssoc($result))) {
				$rv = $row['last_activity_days'];
			}
		}
		return $rv;
	}

	public static function userPostCount($userId = false) {
		if ($userId === false && userId()) {
			$userId = userId();
		}
		
		if ($userId) {
			return (int) getRow(
				ZENARIO_COMMENTS_PREFIX. 'users',
				'post_count',
				array('user_id' => (int) $userId));
		} else {
			return false;
		}
	}
	
	public static function userPostCount_framework(&$mergeFields) {
		$userId = pullFromArray($mergeFields, 'userid', 'user', 'poster');
		
		return zenario_anonymous_comments::userPostCount($userId);
	}
	
	protected static function getUserScreenNameConfirmed($userId) {
		if ($userId === false && userId()) {
			$userId = userId();
		}
		if ($userId) {
			$confirmed = getRow('users', 'screen_name_confirmed', array('id' => $userId));
			return (bool)$confirmed;
		}
		return false;
	}
	
	public function showThreadActions() {
		if (setting('user_use_screen_name') && !self::getUserScreenNameConfirmed(userId())) {
			$cID = $cType = false;
			langSpecialPage('zenario_profile', $cID, $cType);
			$profileAnchor = $this->linkToItemAnchor($cID, $cType);
			$this->sections['Comments_Profile_Link'] = true;
			$profileLink =  '<a '.$profileAnchor.'>'. $this->phrase('your profile').'</a>';
			$this->mergeFields['Comments_Profile_Link'] = $this->phrase('You must confirm your screen name on [[profile_link]] in order to comment.', array('profile_link' => $profileLink));
		}
		parent::showThreadActions();
	}
	
	function canMakePost() {
		if (setting('user_use_screen_name')) {
			$confirmed = self::getUserScreenNameConfirmed(userId());
		} else {
			$confirmed = true;
		}
		return $confirmed && !$this->locked() && $this->postPrivs;
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		zenario_anonymous_comments::formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		require funIncPath(__FILE__, __FUNCTION__);
	}
}