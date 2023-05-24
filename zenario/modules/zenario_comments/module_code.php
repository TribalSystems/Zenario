<?php
/*
 * Copyright (c) 2023, Tribal Limited
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
			ze\priv::check('_PRIV_MODERATE_USER_COMMENTS') || (
					$this->setting('enable_extranet_moderators')
				 && $this->setting('moderators')
				 && ze\user::id()
				 && ze\user::isInGroup($this->setting('moderators'))
			);
		
		$this->postPrivs =
			ze\user::id() && (
					$this->modPrivs
				 || !$this->setting('enable_posting_restrictions')
				 || !$this->setting('restrict_posting_to_group')
				 || ze\user::isInGroup($this->setting('restrict_posting_to_group'))
			);
		
		if (($this->modPrivs || ze\user::id()) && empty($_SESSION['confirm_key'])) {
			$_SESSION['confirm_key'] = ze\ring::random();
		}
	}
	
	
	function showSlot() {
		if (!$this->show) {
			return;
		}
		
		if (ze\user::id()) {
			$this->updateUserLatestActivity(ze\user::id());
		}
		
		$mode = $this->mode;
		$this->$mode();
	}
	
	
	//Show detailed information on one user, to appear next to their post
	function showUserInfo(&$mergeFields, &$sections, $userId, $post = false) {
		if (!$userId || !ze::setting('user_use_screen_name')) {
			if ($adminId = ze\admin::id()) {
				$adminDetails = ze\admin::details($adminId);
				$mergeFields['Username'] = $mergeFields['Username_Link'] = htmlspecialchars($adminDetails['first_name'] . ' ' . $adminDetails['last_name']);
			} else {
				$mergeFields['Username'] = $mergeFields['Username_Link'] = htmlspecialchars($this->phrase('Anonymous'));
			}
		
			if ($this->setting('show_user_avatars')) {
				$sections['No_Avatar'] = $sections['Posting_No_Avatar'] = true;
			}
		
			return;
		}
		
		$sql = "
			SELECT
				latest_activity > NOW() - INTERVAL 10 MINUTE AS `online`,
				post_count
			FROM ". DB_PREFIX. ZENARIO_COMMENTS_PREFIX. "users
			WHERE user_id = ". (int) $userId;
		$result = ze\sql::select($sql);
		
		$forumDetails = ze\sql::fetchAssoc($result);
		$coreDetails = ze\row::get('users', ['screen_name', 'created_date', 'image_id'], $userId);
		
		if ($this->setting('show_user_avatars')) {
			$width = $height = $url = false;
			if ($coreDetails['image_id']
			 && ze\file::imageLink($width, $height, $url, $coreDetails['image_id'], $this->setting('avatar_width'), $this->setting('avatar_height'))) {
				$sections['Show_Avatar'] = $sections['Posting_Show_Avatar'] = true;
				$mergeFields['Width'] = $width;
				$mergeFields['Height'] = $height;
				$mergeFields['Src'] = htmlspecialchars($url);
			} else {
				$sections['No_Avatar'] = $sections['Posting_No_Avatar'] = true;
			}
		}
		
		if (ze::setting('user_use_screen_name')) {
			$mergeFields['Username'] = htmlspecialchars($coreDetails['screen_name']);
			$mergeFields['Username_Link'] = $this->getUserScreenNameLink($userId, $coreDetails['screen_name']);
		}
		
		if ($this->setting('show_user_online_status')) {
			//If this is a user who has never posted before, their SQL lookup will be a boolean false.
			//Show them as online right now.
			if (!$forumDetails || (is_array($forumDetails) && !empty($forumDetails['online']))) {
				$sections['Show_Online'] = $sections['Posting_Show_Online'] = true;
			} else {
				$sections['Show_Offline'] = $sections['Posting_Show_Offline'] = true;
			}
		}
		
		if ($this->setting('show_user_post_counts')) {
			$sections['Show_Post_Count'] = $sections['Posting_Show_Post_Count'] = true;
			
			//If this is a user who has never posted before, their SQL lookup will be a boolean false.
			//Set the post count to 0 in that case.
			if (is_array($forumDetails) && isset($forumDetails['post_count'])) {
				$postCount = $forumDetails['post_count'];
			} else {
				$postCount = 0;
			}
			$mrg = ['post_count' => $postCount];
			$mergeFields['Post_Count'] = $this->phrase('Post Count: [[post_count]]', $mrg);
		}
		
		if ($this->setting('show_user_join_dates')) {
			$sections['Show_Join_Date'] = $sections['Posting_Show_Join_Date'] = true;
			$mrg = ['date' => ze\date::format($coreDetails['created_date'], $this->setting('date_format'))];
			$mergeFields['Join_Date'] = $this->phrase('Join Date: [[date]]', $mrg);
		}
	}
	
	function updateUserLatestActivity($userId) {
		$sql = "
			UPDATE ". DB_PREFIX. ZENARIO_COMMENTS_PREFIX. "users SET
				latest_activity = NOW()
			WHERE user_id = ". (int) $userId;
		ze\sql::update($sql, false, false);
	}
	
	function canDeletePost(&$post) {
		if (isset($post['first_post']) && $post['first_post']) {
			return false;
		} elseif ($this->modPrivs) {
			return true;
		} else {
			return ze\user::id()
				&& $this->setting('allow_user_delete_own_post')
				&& $post['poster_id'] == ze\user::id()
				&& !$this->locked();
		}
	}
	
	
	function canEditPost(&$post) {
		if (isset($post['first_post']) && $post['first_post']) {
			return false;
		} elseif ($this->modPrivs) {
			return true;
		} else {
			return ze\user::id()
				&& $this->setting('allow_user_edit_own_post')
				&& $post['poster_id'] == ze\user::id()
				&& !$this->locked()
				&& !($this->setting('comments_require_approval') && $post['status'] == 'published');
		}
	}
	
	
	function canReportPost() {
		return $this->setting('enable_report_a_post') && $this->setting('email_template_for_reports') && 
					($this->setting('enable_anonymous_report_a_post') || ze\user::id());
	}
	
	
	function canSubsThread() {
		return ze\user::id() && $this->setting('enable_subs');
	}
	
	function couldSubsThread() {
		return !ze\user::id() && $this->setting('enable_subs') && !$this->locked();
	}
	
	function hasSubsThread() {
		return ze\row::exists(
			ZENARIO_COMMENTS_PREFIX. 'user_subscriptions',
			[
				'user_id' => ze\user::id(),
				'content_id' => $this->cID,
				'content_type' => $this->cType,
				'forum_id' => 0,
				'thread_id' => 0]);
	}
	
	protected function subs($subs, $thread = true) {
		$key = [
			'user_id' => ze\user::id(),
			'content_id' => $this->cID,
			'content_type' => $this->cType,
			'forum_id' => 0,
			'thread_id' => 0];
		
		if ($subs) {
			ze\row::set(
				ZENARIO_COMMENTS_PREFIX. 'user_subscriptions',
				['date_subscribed' => ze\date::now()],
				$key);
		
		} else {
			ze\row::delete(
				ZENARIO_COMMENTS_PREFIX. 'user_subscriptions',
				$key);
		}
	}
	
	
	//Check the mirror table to the users table for a given user, and add them in if needed
	function checkUserIsInForumsUserTable($userId) {
		if (!ze\row::exists(ZENARIO_COMMENTS_PREFIX. 'users', ['user_id' => $userId])) {
			$sql = "
				INSERT INTO ". DB_PREFIX. ZENARIO_COMMENTS_PREFIX. "users
					(user_id, latest_activity)
				VALUES
					(". (int) $userId. ", NOW())";
			ze\sql::update($sql, false, false);
		}
	}
	
	//Add a comment onto the thread
	function addReply($userId, &$messageText, $firstPost = 0, $name = '', $email = '') {
		
		//Make sure that thid user has an entry in the forum user details table
		$this->checkUserIsInForumsUserTable($userId);
		
		zenario_anonymous_comments::addReply($userId, $messageText, $firstPost);
		
		//Update the user's post count
		$sql = "
			UPDATE ". DB_PREFIX. ZENARIO_COMMENTS_PREFIX. "users SET
				post_count = post_count + 1
			WHERE user_id = ". (int) $userId;
		
		$result = ze\sql::update($sql);
	}
	
	
	function showPostScreenTopFields($titleText) {
		
	}
	
	protected static function sendEmailNotificationToSubscribers($commentId, $newPost = true) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	
	
	//Remove a comment from the thread
	function deletePost() {
		zenario_anonymous_comments::deletePost();
		
		//Update the user's post count
		$sql = "
			UPDATE ". DB_PREFIX. ZENARIO_COMMENTS_PREFIX. "users SET
				post_count = post_count - 1
			WHERE post_count > 0
			  AND user_id = ". (int) $this->post['poster_id'];
		
		$result = ze\sql::update($sql);
	}

	function deletePostById($id) {
		$posterId = ze\row::get(ZENARIO_ANONYMOUS_COMMENTS_PREFIX . 'user_comments', 'poster_id', ['id' =>  $id ]);
		
		zenario_anonymous_comments::deletePostById($id);
		
		//Update the user's post count
		$sql = "
			UPDATE "
					. DB_PREFIX. ZENARIO_COMMENTS_PREFIX. "users 
			SET
				post_count = post_count - 1
			WHERE 
					post_count > 0
			  	AND user_id = ". (int) $posterId;
		
		$result = ze\sql::update($sql);
	}
	
	public static function deleteUserDataGetInfo($userIds) {
		$sql = "
			SELECT COUNT(id)
			FROM " . DB_PREFIX . ZENARIO_ANONYMOUS_COMMENTS_PREFIX . "user_comments
			WHERE poster_id IN (" . ze\escape::in($userIds) . ")";
		$result = ze\sql::select($sql);
		$count = ze\sql::fetchValue($result);
		
		$userCommentsResults = ze\admin::phrase('Comments posted by this user will have the creator ID removed ([[count]] found)', ['count' => $count]);
		
		return $userCommentsResults;
	}
	
	public static function eventUserDeleted($userId) {
		$sql = "
			DELETE FROM ". DB_PREFIX. ZENARIO_COMMENTS_PREFIX. "users
			WHERE user_id = ". (int) $userId;
		ze\sql::update($sql);
		
		$sql = "
			UPDATE ". DB_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "user_comments SET
				poster_id = 0
			WHERE poster_id = ". (int) $userId;
		ze\sql::update($sql);
		
		$sql = "
			UPDATE ". DB_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "user_comments SET
				updater_id = 0
			WHERE updater_id = ". (int) $userId;
		ze\sql::update($sql);
	}
	
	public static function userLastPost($userId = false) {
		if ($userId === false && ze\user::id()) {
			$userId = ze\user::id();
		}
		
		if ($userId) {
			$sql = "
				SELECT content_id, content_type, date_posted, message_text
				FROM ". ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "user_comments
				WHERE poster_id = ". (int) $userId. "
				ORDER BY date_posted DESC
				LIMIT 1";
			
			$result = ze\sql::select($sql);
			if ($row = ze\sql::fetchAssoc($result)) {
				return $row;
			}
		}
		
		return false;
	}
	
	public static function userLatestActivity($userId = false) {
		if ($userId === false && ze\user::id()) {
			$userId = ze\user::id();
		}
		
		if ($userId) {
			return ze\row::get(
				ZENARIO_COMMENTS_PREFIX. 'users',
				'latest_activity',
				['user_id' => (int) $userId]);
		} else {
			return false;
		}
	}
	
	public static function userLatestActivityDays($userId = false) {
		$rv = false;
		if ($latestActivityOn = self::userLatestActivity($userId)) {
			$sql = "SELECT DATEDIFF(NOW(), '" . $latestActivityOn  . "') as last_activity_days ";
			if (($result = ze\sql::select($sql)) && ($row = ze\sql::fetchAssoc($result))) {
				$rv = $row['last_activity_days'];
			}
		}
		return $rv;
	}

	public static function userPostCount($userId = false) {
		if ($userId === false && ze\user::id()) {
			$userId = ze\user::id();
		}
		
		if ($userId) {
			return (int) ze\row::get(
				ZENARIO_COMMENTS_PREFIX. 'users',
				'post_count',
				['user_id' => (int) $userId]);
		} else {
			return false;
		}
	}
	
	public static function userPostCount_framework(&$mergeFields) {
		$userId = ze\ray::grabValue($mergeFields, 'userid', 'user', 'poster');
		
		return zenario_anonymous_comments::userPostCount($userId);
	}
	
	protected static function getUserScreenNameConfirmed($userId) {
		if ($userId === false && ze\user::id()) {
			$userId = ze\user::id();
		}
		if ($userId) {
			$confirmed = ze\row::get('users', 'screen_name_confirmed', ['id' => $userId]);
			return (bool)$confirmed;
		}
		return false;
	}
	
	public function showThreadActions() {
		if (ze::setting('user_use_screen_name') && !self::getUserScreenNameConfirmed(ze\user::id())) {
			$this->sections['Comments_Profile_Link'] = true;
			
			$profileLink = '<a';
			if ($link = ze\link::toPluginPage('zenario_extranet_profile_edit')) {
				$profileLink .= ' href="'. htmlspecialchars($link). '"';
			}
			$profileLink .= '>'. $this->phrase('your profile'). '</a>';
			
			$this->mergeFields['Comments_Profile_Link'] = $this->phrase('You must confirm your screen name on [[profile_link]] in order to comment.', ['profile_link' => $profileLink]);
		}
		parent::showThreadActions();
	}
	
	function canMakePost() {
		if (ze::setting('user_use_screen_name')) {
			$confirmed = self::getUserScreenNameConfirmed(ze\user::id());
		} else {
			$confirmed = true;
		}
		return $confirmed && !$this->locked() && $this->postPrivs;
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				$hidden = !$values['first_tab/show_user_avatars'];
				$this->showHideImageOptions($fields, $values, 'first_tab', $hidden, 'avatar_', false);
				
				$fields['posting/restrict_posting_to_group']['hidden'] = !$values['posting/enable_posting_restrictions'];
				$fields['moderation/moderators']['hidden'] = !$values['moderation/enable_extranet_moderators'];
				$fields['moderation/enable_anonymous_report_a_post']['hidden'] = !$values['moderation/enable_report_a_post'];
				if (isset($values['notification/comment_subs_email_template'])) {
					$fields['notification/comment_subs_email_template']['hidden'] = !$values['notification/enable_subs'];
				}
				
				break;
		}
	}
}