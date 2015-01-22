<?php
/*
 * Copyright (c) 2014, Tribal Limited
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





class zenario_anonymous_comments extends module_base_class {
	
	protected $show = true;
	protected $mode = 'showPosts';
	protected $form_encode_type = null;
	var $reloadPage = false;
	var $modPrivs = false;
	var $postPrivs = false;
	var $newThreadPrivs = false;
	
	var $forum = array();
	var $thread = array();
	var $posts = false;
	var $post = array();
	var $page = 1;
	
	var $mergeFields = array();
	var $sections = array();
	var $postingErrors = array();
	
	
 /**
  * The clearRequest() method removes an entry from the $_POST and the $_GET
  */
	protected final function clearRequest($name) {
		if (isset($_GET[$name])) {
			unset($_GET[$name]);
		}
		if (isset($_POST[$name])) {
			unset($_POST[$name]);
		}
		if (isset($_REQUEST[$name])) {
			unset($_REQUEST[$name]);
		}
	}
	
	
	function init() {
		if (!$this->setting('show_user_online_status')) {
			$this->allowCaching(
				$atAll = true, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
			$this->clearCacheBy(
				$clearByContent = false, $clearByMenu = false, $clearByUser = true, $clearByFile = true, $clearByModuleData = true);
		}
		
		if (in(isSpecialPage($this->cID, $this->cType), 'zenario_no_access', 'zenario_not_found', 'zenario_logout')) {
			return $this->show = false;
		}
		
		$this->registerGetRequest('comm_page', 1);
		$this->page = ifNull((int) request('comm_page'), 1);
		
		$this->runCheckPrivs();
		
		$this->loadThreadInfo();
		$this->loadPagination();
		$this->loadPosts();
		
		$this->threadActionHandler();
		
		$this->threadSelectMode();
		
		return $this->show = true;
	}


	
	function showSlot() {
		if (!$this->show) {
			return;
		}
		
		$mode = $this->mode;
		$this->$mode();
	}
	
	
	
	
	//Workaround for a bug with PunyMCE and leaving unclosed tags at the end of messages
	function adjustMessageText($text) {
		return preg_replace('@\[\w\]$@', '', $text);
	}
	
	
	//Add a comment onto the thread
	function addReply($userId, &$messageText, $firstPost = 0, $name = '', $email = '') {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	function defaultReplyStatus() {
		return $this->setting('comments_require_approval') 
					? ( $this->canApprovePost() ? 'published' : 'pending' ) 
						: 'published';
	}
	
	function canDeleteThread() {
		return false;
	}
	
	function canApprovePost() {
		return $this->modPrivs;
	}
	
	function canDeletePost(&$post) {
		if (isset($post['first_post']) && $post['first_post']) {
			return false;
		} else {
			return $this->modPrivs;
		}
	}
	
	
	function canEditFirstPost(&$post) {
		return false;
	}
	
	
	function canEditPost(&$post) {
		if (isset($post['first_post']) && $post['first_post']) {
			return false;
		} else {
			return $this->modPrivs;
		}
	}
	
	
	function canLockThread() {
		return $this->modPrivs && !$this->locked();
	}
	
	
	function canMakeThread() {
		return false;
	}
	
	
	function canMoveThread() {
		return false;
	}
	
	
	function canSubsThread() {
		return false;
	}
	
	function couldSubsThread() {
		return false;
	}
	
	function hasSubsThread() {
		return false;
	}
	
	
	function canMakePost() {
		return !$this->locked() && $this->postPrivs;
	}
	
	
	function canQuotePost() {
		return $this->canMakePost() && $this->setting('enable_reply_with_quote');
	}
	
	
	function canReportPost() {
		return $this->setting('enable_report_a_post') && $this->setting('email_template_for_reports');
	}
	
	
	function canUnlockThread() {
		return $this->modPrivs && $this->locked();
	}
	
	
	
	//Add action buttons to a post, as appropriate
	function checkPostActions(&$post, &$mergeFields, &$sections) {
		$controls = false;
		
		if ($this->canQuotePost() && ($post['status']=='published') ) {
			$controls = true;
			$sections['Quote_Post'] = true;
			$mergeFields['Quote_Post_Link'] = $this->refreshPluginSlotAnchor('&comm_page='. $this->page. '&comm_request=post_reply&comm_enter_text=1&comm_post='. $post['id']. '&forum_thread='. $this->thread['id']);
		}

		if ($this->canEditFirstPost($post)) {
			$controls = true;
			$sections['Edit_Post'] = true;
			$mergeFields['Edit_Post_Link'] = $this->refreshPluginSlotAnchor('&comm_page='. $this->page. '&comm_request=edit_first_post&comm_enter_text=1&comm_post='. $post['id']. '&forum_thread='. $this->thread['id']);
		
		} elseif ($this->canEditPost($post)) {
			$controls = true;
			$sections['Edit_Post'] = true;
			$mergeFields['Edit_Post_Link'] = $this->refreshPluginSlotAnchor('&comm_page='. $this->page. '&comm_request=edit_post&comm_enter_text=1&comm_post='. $post['id']. '&forum_thread='. $this->thread['id']);
		}

		if ($this->canDeletePost($post)) {
			$controls = true;
			$sections['Delete_Post'] = true;
			$mergeFields['Delete_Post_Link'] = $this->refreshPluginSlotAnchor('&comm_page='. $this->page. '&comm_request=delete_post&comm_confirm=1&comm_post='. $post['id']. '&forum_thread='. $this->thread['id']);
		}

		if (arrayKey($post,'status')=='pending' && $this->canApprovePost()) {
			$controls = true;
			$sections['Approve_Post'] = true;
			$mergeFields['Approve_Post_Link'] = $this->refreshPluginSlotAnchor('&comm_page='. $this->page. '&comm_request=approve_post&comm_confirm=1&comm_post='. $post['id']. '&forum_thread='. $this->thread['id'] . '&checksum=' . md5($post['message_text']));
		}

		if ($this->canReportPost() && ($post['status']=='published') ) {
			$controls = true;
			$sections['Report_Post'] = true;
			$mergeFields['Report_Post_Link'] =
				' onclick="'.
					$this->refreshPluginSlotJS('&comm_page='. $this->page. '&comm_request=report_post&comm_enter_text=1&comm_post='. $post['id']. '&forum_thread='. $this->thread['id']).
					' return false;"';

			
		}
		
		return $controls;
	}
	
	
	function runCheckPrivs() {
		
		$this->modPrivs = checkPriv('_PRIV_MODERATE_USER_COMMENTS');
		
		$this->postPrivs = true;
		
		if (empty($_SESSION['confirm_key'])) {
			$_SESSION['confirm_key'] = randomString();
		}
	}
	
	
	//Remove a comment from the thread
	function deletePost() {
		
		//Remove the comment
		$sql = "
			DELETE FROM ". DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "user_comments
			WHERE content_id = ". (int) $this->cID. "
			  AND content_type = '". sqlEscape($this->cType). "'
			  AND id = ". (int) $this->post['id'];
		
		$result = sqlQuery($sql);
		
		//Update the post count for the thread
		$sql = "
			UPDATE ". DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "comment_content_items SET
				post_count = post_count - 1
			WHERE post_count > 0
			  AND content_id = ". (int) $this->cID. "
			  AND content_type = '". sqlEscape($this->cType). "'";
		
		$result = sqlQuery($sql);
	}

	function deletePostById($id) {
		$contentId = getRow(ZENARIO_ANONYMOUS_COMMENTS_PREFIX . 'user_comments', 'content_id', array('id' =>  $id ));
		$contentType = getRow(ZENARIO_ANONYMOUS_COMMENTS_PREFIX . 'user_comments', 'content_type', array('id' =>  $id ));

		deleteRow(ZENARIO_ANONYMOUS_COMMENTS_PREFIX . 'user_comments', array('id' =>  $id ));
		
		//Update the post count for the thread
		$sql = "
			UPDATE "
				. DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "comment_content_items 
			SET	
				post_count = post_count - 1
			WHERE 
					post_count > 0
				AND content_id = ". (int) $contentId . "
				AND content_type = '".$contentType . "'";
		
		$result = sqlQuery($sql);
	}

	function approvePost() {
		$sql = "
			UPDATE ". DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "user_comments
				SET status='published'
			WHERE content_id = ". (int) $this->cID. "
			  AND content_type = '". sqlEscape($this->cType). "'
			  AND id = ". (int) $this->post['id'];
		
		$result = sqlQuery($sql);

		$this->sendEmailNotification((int) $this->post['id']);
		
		
	}
	
	function approvePostById($id) {

		$sql = "
			UPDATE "
				. DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "user_comments
			SET 
				status='published'
			WHERE 
				id = ". (int) $id;
		
		$result = sqlQuery($sql);

		$this->sendEmailNotification((int) $id);
		
	}

	//Edit a comment
	function editPost($userId, $messageText) {
		
		//Add the comment
		$sql = "
			UPDATE ". DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "user_comments SET
				message_text = '". sqlEscape($this->adjustMessageText($messageText)). "',
				date_updated = NOW(),
				updater_id = ". (int) $userId. "
			WHERE content_id = ". (int) $this->cID. "
			  AND content_type = '". sqlEscape($this->cType). "'
			  AND id = ". (int) $this->post['id'];
		
		$result = sqlQuery($sql);
		
		$this->sendEmailNotification((int) $this->post['id'], false);
	}
	
	//Get a User's titles
	function getUserTitles($userId) {
		
		$titles = array();
		
		$groups = getUserGroups((int) $userId);
		
		foreach ($groups as $group) {
			$groupId = datasetFieldId($group);
			$isVisibleGroupId = datasetFieldId($group . '_visible');
			if (substr($group, -strlen('_hidden')) === '_hidden') {
				
			} else {
				$titles[] = array('Title' => getGroupLabel($groupId));
			}
		}
		
		if (count($titles)) {
			return $titles;
		} else {
			return false;
		}
	}
	
	//Get a user's screen_name, if we're showing screennames
	function getUserScreenName($userId) {
		if (!$userId) {
			return $this->phrase('_ANONYMOUS');
		} elseif (setting('user_use_screen_name')) {
			return getUserScreenName($userId);
		} else {
			return '';
		}
	}
	
	//Get a user's screen name, and add a Storekeeper Link if in Admin mode with the correct Perms
	function getUserScreenNameLink($userId, $screenName = false, $alwaysShowLink = false) {
		if ($screenName === false) {
			$screenName = $this->getUserScreenName($userId);
		}
		
		if (!$screenName) {
			return '';
		} elseif ($userId && ($alwaysShowLink || checkPriv('_PRIV_VIEW_USER'))) {
			return '<a href="'. absCMSDirURL(). 'admin/organizer.php#zenario__users/nav/users/panel//'. $userId. '/" target="_blank">'. htmlspecialchars($screenName). '</a>';
		} else {
			return htmlspecialchars($screenName);
		}
	}
	
	
	function loadThreadInfo() {
		
		//Get information on comments for this content item from the mirror table
		$sql = "
			SELECT
				0 AS id,
				date_updated,
				updater_id,
				post_count,
				locked
			FROM ". DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "comment_content_items
			WHERE content_id = ". (int) $this->cID. "
			  AND content_type = '". sqlEscape($this->cType). "'";
		
		$result = sqlQuery($sql);
		
		if ($this->thread = sqlFetchAssoc($result)) {
			return;
		}
		
		//If we didn't find a row, then add one in
		$sql = "
			INSERT INTO ". DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "comment_content_items SET
				content_id = ". (int) $this->cID. ",
				content_type = '". sqlEscape($this->cType). "'";
		sqlQuery($sql);
		
		$this->thread = array(
			'id' => 0,
			'date_updated' => null,
			'updater_id' => 0,
			'post_count' => 0,
			'locked' => 0);
	}
	
	
	//Setup and generate Pagination
	function loadPagination() {
		
		if ($this->mode == 'showPosts') {
			$this->pageSize = ifNull((int) $this->setting('page_size_posts'), 12);
			
			//Don't show pagination if a specific post id is beind displayed
			if (request('comm_post')) {
				return;
			}
			
			$pageCount = (int) ceil($this->thread['post_count'] / $this->pageSize);
			
			//Show the last page in the thread when adding a new reply
			if (request('comm_enter_text') || $this->page == -1) {
				$this->page = $pageCount;
			}
			
			//Don't show pagination when the enter-reply box is displayed
			if (request('comm_enter_text')) {
				return;
			}
			
			$paginationStyleSettingName = 'pagination_style_posts';
		
		} elseif ($this->mode == 'showThreads') {
			$this->pageSize = ifNull((int) $this->setting('page_size_threads'), 12);
			$pageCount = (int) ceil($this->forum['thread_count'] / $this->pageSize);
			
			$paginationStyleSettingName = 'pagination_style_threads';
		
		} elseif ($this->mode == 'showSearch' && $this->results) {
			$this->pageSize = ifNull((int) $this->setting('page_size_search'), 12);
			$pageCount = (int) ceil($this->results / $this->pageSize);
			
			$paginationStyleSettingName = 'pagination_style_search';
		
		} else {
			return;
		}
		
		
		$pages = array();
		for ($i=1; $i <= $pageCount; ++$i) {
			$pages[$i] = '&comm_page='. $i;
			
			if ($this->mode == 'showPosts' && request('forum_thread')) {
				$pages[$i] .= '&forum_thread='. (int) request('forum_thread');
			}
			
			if ($this->mode == 'showSearch') {
				$pages[$i] .= '&searchString='. rawurlencode(request('searchString'));
			}
		}
		
		$this->mergeFields['Pagination'] = '';
		$this->pagination($paginationStyleSettingName, $this->page, $pages, $this->mergeFields['Pagination']);
	}
	
	
	//Load all of the comments/posts within our current view range
	function loadPosts() {
		
		$this->posts = array();
	
		$sql = "
			SELECT
				id,
				date_posted,
				date_updated,
				status,
				employee_post,
				poster_id,
				poster_name,
				poster_email,
				updater_id,
				message_text,
				rating
			FROM ". DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "user_comments
			WHERE content_id = ". (int) $this->cID. "
			  AND content_type = '". sqlEscape($this->cType). "'";

		if ($this->setting('comments_require_approval') && !$this->modPrivs) {
			$sql .= "
				AND (
						status='published'
					OR	(
							status='pending'";
			
			if (userId()) {
				$sql .= "
						AND poster_id = ". (int) userId();
			} else {
				$sql .= "
						AND poster_session_id = '". sqlEscape(session_id()). "'";
			}
			
			$sql .= "
					)
				)";
		}
		
		//Have the option to just display a specific post
		if (request('comm_post')) {
			$sql .= "
			  AND id = ". (int) request('comm_post');
		} else {
			$sql .= "
			ORDER BY id";
			
			//Normally, display posts in order, unless the MOST_RECENT_FIRST option is checked and we're not making a reply.
			if ($this->setting('order') == 'MOST_RECENT_FIRST' && !request('comm_enter_text')) {
				$sql .= " DESC";
			}
			
			if ($this->setting('order') == 'MOST_RECENT_FIRST' && request('comm_enter_text')) {
				$sql .= "
				LIMIT ". max(0, ($this->thread['post_count'] - $this->pageSize)). ", ". (int) $this->pageSize;
			} else {
				$sql .= paginationLimit($this->page, $this->pageSize);
			}
		}
		
		$result = sqlQuery($sql);

		if (request('comm_post')) {
			//Attempt to get information on a specific post. If it doesn't exist, clear the request and reload the page
			if (!$this->posts[] = $this->post = sqlFetchAssoc($result)) {
				$this->clearRequest('comm_post');
				$this->clearRequest('comm_request');
				$this->clearRequest('comm_confirm');
				$this->clearRequest('comm_enter_text');
				
				$this->loadPagination();
				$this->loadPosts();
			}
		
		} else {
			while($row = sqlFetchAssoc($result)) {
				$this->posts[] = $row;
			}
		}
	}
	
	
	function locked() {
		return $this->thread['locked'];
	}
	
	
	function lockThread($lock) {
		
		$sql = "
			UPDATE ". DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "comment_content_items SET
				locked = ". (int) $lock. "
			WHERE content_id = ". (int) $this->cID. "
			  AND content_type = '". sqlEscape($this->cType). "'";
		
		$result = sqlQuery($sql);
	}
	
	
	//Send an email notification for a reported post
	function reportPost() {
		require funIncPath(__FILE__, __FUNCTION__);
	}
		
	function sendApproveRequest($userId, $messageText, $name = '', $email = '') {
		require funIncPath(__FILE__, __FUNCTION__);
	}

	protected function sendEmailNotification($commentId, $newPost = true) {
		require funIncPath(__FILE__, __FUNCTION__);
		if (inc('zenario_comments')) {
			zenario_comments::sendEmailNotificationToSubscribers($commentId, $newPost);
		}
	}
	
	protected function subs($subs, $thread = true) {
	}
	
	
	//Show an "are you sure" box
	function showConfirmBox($message, $submitButtonText) {

		$this->sections['Confirmation_Box'] = array();
		$this->sections['Confirmation_Box']['Submit_Button_Text'] = $submitButtonText;
		$this->sections['Confirmation_Box']['Confirmation_Message'] = $message;
		$this->sections['Confirmation_Box']['Cancel_Link'] = $this->refreshPluginSlotAnchor('&comm_page='. $this->page. '&forum_thread='. arrayKey($this->thread, 'id'), false);
		
		$this->sections['Confirmation_Box']['Open_Form'] = $this->openForm('', 'class="'. htmlspecialchars(request('comm_request')). '"'). 
			$this->remember('comm_request').
			$this->remember('comm_page').
			$this->remember('comm_confirm').
			$this->remember('comm_post').
			$this->remember('forum_thread') .
			$this->remember('checksum', md5($this->post['message_text']));
		$this->sections['Confirmation_Box']['Close_Form'] = $this->closeForm();
	}
	
	
	function showPosts() {
		
		$this->mergeFields['Post_Class'] = 'list_of_comments';
		$this->frameworkHead('Posts', 'Post', $this->mergeFields, $this->sections);
		
		if ($this->posts) {
			require_once CMS_ROOT. 'zenario/libraries/mit/markitup/bbcode2html.inc.php';
			
			$first = true;
			foreach($this->posts as &$post) {
				
				$sections = array();
				$mergeFields = array();
				
				if ($first && !empty($this->thread['title'])) {
					$first = false;
					$sections['Show_Thread_Title'] = true;
					$mergeFields['Thread_Title'] = htmlspecialchars($this->thread['title']);
				}
				
				$mergeFields['Date_Posted'] = formatDateTimeNicely($post['date_posted'], $this->setting('date_format'));
				$mergeFields['Post_Text'] = $post['message_text'];
				$mergeFields['Employee'] = $post['employee_post'] && $this->setting('mark_employee_posts')? 'employee' : '';
				
				if ($post['status'] == 'pending') {
					$sections['Pending_Post'] = true;
				}
				
				BBCode2Html($mergeFields['Post_Text'],
					$this->setting('enable_colours'), $this->setting('enable_images'),
					$this->setting('enable_links'), $this->setting('enable_emoticons'));
				$this->showUserInfo($mergeFields, $sections, $post['poster_id'], $post);
				
				$this->getExtraPostInfo($post, $mergeFields, $sections /*, arrayKey($_REQUEST,'comm_request') == 'edit_post'*/);
				
				if (request('comm_confirm') || request('comm_enter_text') || !($this->checkPostActions($post, $mergeFields, $sections))) {
					$this->framework('Post', $mergeFields, $sections);
				} else {
					$sections['Post_Controls'] = true;
					$this->framework('Post', $mergeFields, $sections);
				}
			}
		}
		
		$this->frameworkFoot('Posts', 'Post', $this->mergeFields, $this->sections);
	}
	
	protected function getExtraPostInfo(&$post, &$mergeFields, &$sections, $to_edit=false){
	}
	
	
	//Show a post entry screen, with a TinyMCE box for inputting a message
	//Called with different options, it can also be used for quoting a post, editing a post or making a new thread
	function showPostScreen($labelText, $submitButtonText, $quoteMode, $titleText = false, $onSubmit = '') {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	
	function showPostScreenTopFields($titleText) {
		if ($this->setting('show_name')) {
			$this->sections['Show_Post_Name'] = true;
			
			if (isset($_POST['comm_name'])) {
				$this->sections['Post_Message']['Post_Name'] = htmlspecialchars($_POST['comm_name']);
			
			} elseif (userScreenName()) {
				$this->sections['Post_Message']['Post_Name'] =  htmlspecialchars(userScreenName());
			
			} else {
				$sql = "
					SELECT poster_name
					FROM ". DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "user_comments
					WHERE poster_session_id = '". sqlEscape(session_id()). "'
					ORDER BY date_posted DESC
					LIMIT 1";
				
				if (($result = sqlQuery($sql))
				 && ($row = sqlFetchRow($result))) {
					$this->sections['Post_Message']['Post_Name'] =  htmlspecialchars($row[0]);
				}
			}
			
			$this->sections['Post_Message']['Post_Name'] = '<input type="text" id="comm_name" name="comm_name" maxlength="50" value="'. $this->sections['Post_Message']['Post_Name']. '"/>';
		}
		
		if ($this->setting('show_email')) {
			$this->sections['Show_Post_Email'] = true;
			
			if (isset($_POST['comm_email'])) {
				$this->sections['Post_Message']['Post_Email'] = htmlspecialchars($_POST['comm_email']);
			
			} elseif (userEmail()) {
				$this->sections['Post_Message']['Post_Email'] =  htmlspecialchars(userEmail());
			
			} else {
				$sql = "
					SELECT poster_email
					FROM ". DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "user_comments
					WHERE poster_session_id = '". sqlEscape(session_id()). "'
					ORDER BY date_posted DESC
					LIMIT 1";
				
				if (($result = sqlQuery($sql))
				 && ($row = sqlFetchRow($result))) {
					$this->sections['Post_Message']['Post_Email'] =  htmlspecialchars($row[0]);
				}
			}
			
			$this->sections['Post_Message']['Post_Email'] = '<input type="email" id="comm_email" name="comm_email" maxlength="100" value="'. $this->sections['Post_Message']['Post_Email']. '"/>';
		}
	}
	
	
	//Show/hide the action buttons for the current thread
	function showThreadActions() {
		$loginCID = $loginCType = false;
		
		if ($this->canMakePost()) {
			$this->sections['Add_Reply'] = true;
			$this->mergeFields['Add_Reply_Link'] = $this->refreshPluginSlotAnchor('&comm_page='. $this->page. '&comm_request=post_reply&comm_enter_text=1&forum_thread='. $this->thread['id'], false);
			
		} elseif ($this->locked()) {
			if (!$this->canUnlockThread()) {
				$this->sections['Thread_Locked'] = true;
			}
			
		} elseif (empty($_SESSION['extranetUserID']) && langSpecialPage('zenario_login', $loginCID, $loginCType)) {
			$this->sections['Login_To_Post'] = true;
			$this->mergeFields['Login_To_Post_Link'] = $this->linkToItemAnchor($loginCID, $loginCType);
		}
		
		if ($this->canSubsThread()) {
			if ($this->hasSubsThread()) {
				$this->sections['Unsubs_To_Thread'] = true;
				$this->sections['Subscribed_To_Thread'] = true;
				$this->mergeFields['Unsubs_To_Thread_Link'] = $this->refreshPluginSlotAnchor('&comm_page='. $this->page. '&comm_request=unsubs_thread&comm_confirm=1&forum_thread='. $this->thread['id'], false);
			} else {
				$this->sections['Subs_To_Thread'] = true;
				$this->mergeFields['Subs_To_Thread_Link'] = $this->refreshPluginSlotAnchor('&comm_page='. $this->page. '&comm_request=subs_thread&comm_confirm=1&forum_thread='. $this->thread['id'], false);
			}
		
		} elseif ($this->couldSubsThread()) {
			$this->sections['Login_To_Subs_To_Thread'] = true;
			
			if (empty($this->mergeFields['Login_To_Post_Link'])) {
				$this->mergeFields['Login_To_Post_Link'] = $this->linkToItemAnchor($loginCID, $loginCType);
			}
		}
		
		if ($this->canLockThread()) {
			$this->sections['Lock_Thread'] = true;
			$this->mergeFields['Lock_Thread_Link'] = $this->refreshPluginSlotAnchor('&comm_page='. $this->page. '&comm_request=lock_thread&comm_confirm=1&forum_thread='. $this->thread['id'], false);
			
		} elseif ($this->canUnlockThread()) {
			$this->sections['Unlock_Thread'] = true;
			$this->mergeFields['Unlock_Thread_Link'] = $this->refreshPluginSlotAnchor('&comm_page='. $this->page. '&comm_request=unlock_thread&comm_confirm=1&forum_thread='. $this->thread['id'], false);
		}
		
		if ($this->canMoveThread()) {
			$this->sections['Move_Thread'] = true;
			$this->mergeFields['Move_Thread_Link'] = 'href="#" onclick="'. $this->moduleClassName. ".moveThread('". $this->slotName. "', '". adminPhrase('Move Thread to a different Forum'). "', ". $this->forumId. ", ". $this->thread['id']. ", '". session('confirm_key'). "'); return false;". '"';
		}
		
		if ($this->canDeleteThread()) {
			$this->sections['Delete_Thread'] = true;
			$this->mergeFields['Delete_Thread_Link'] = $this->refreshPluginSlotAnchor('&comm_request=delete_thread&comm_confirm=1&forum_thread='. $this->thread['id'], false);
		}
	}
	
	
	//Show detailed information on one user, to appear next to their post
	function showUserInfo(&$mergeFields, &$sections, $userId, $post = false) {
		
		$postScreen = empty($post);
		if (!$postScreen) {
			if ($this->setting('show_name')) {
				$mergeFields['Username_Link'] = htmlspecialchars(ifNull($post['poster_name'], $this->phrase('_ANONYMOUS')));
			}
			if ($this->setting('show_email')) {
				$mergeFields['Email'] = str_replace('@', '<span class="zenario_dn">ie</span>@<span class="zenario_dn">the</span>', htmlspecialchars($post['poster_email']));
			}
		}
	
		return;
	}
	
	//Handle any requests the users ask for
	function threadActionHandler() {
		if (post('comm_request')) {
			require funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	
	function threadSelectMode() {
		
		if (request('comm_confirm')) {
			if (request('comm_request') == 'delete_post' && $this->canDeletePost($this->post)) {
				$this->showConfirmBox($this->phrase('_CONFIRM_DELETE_POST'), $this->phrase('_SUBMIT_DELETE_POST'));
				
			} elseif (request('comm_request') == 'approve_post' && $this->canApprovePost()) {
				if (request('checksum') == md5($this->post['message_text'])) {
					$this->showConfirmBox($this->phrase('_CONFIRM_APPROVE_POST'), $this->phrase('_SUBMIT_APPROVE_POST'));
				} else {
					$this->showConfirmBox($this->phrase('_CONFIRM_APPROVE_MODIFIED_POST'), $this->phrase('_SUBMIT_APPROVE_MODIFIED_POST'));
				}
				
			} elseif (request('comm_request') == 'delete_thread' && $this->canDeleteThread()) {
				$this->showConfirmBox($this->phrase('_CONFIRM_DELETE_THREAD'), $this->phrase('_SUBMIT_DELETE_THREAD'));
				
			} elseif (request('comm_request') == 'lock_thread' && $this->canLockThread()) {
				$this->showConfirmBox($this->phrase('_CONFIRM_LOCK_THREAD'), $this->phrase('_SUBMIT_LOCK_THREAD'));
				
			} elseif (request('comm_request') == 'unlock_thread' && $this->canUnlockThread()) {
				$this->showConfirmBox($this->phrase('_CONFIRM_UNLOCK_THREAD'), $this->phrase('_SUBMIT_UNLOCK_THREAD'));
				
			} elseif (request('comm_request') == 'subs_thread' && $this->canSubsThread()) {
				$this->showConfirmBox($this->phrase('_CONFIRM_SUBS_THREAD', array('email' => htmlspecialchars(userEmail()))), $this->phrase('_SUBMIT_SUBS_THREAD'));
				
			} elseif (request('comm_request') == 'unsubs_thread' && $this->canSubsThread()) {
				$this->showConfirmBox($this->phrase('_CONFIRM_UNSUBS_THREAD'), $this->phrase('_SUBMIT_UNSUBS_THREAD'));
			}
			
		} elseif (request('comm_enter_text')) {
			if (request('comm_request') == 'edit_first_post' && $this->canEditFirstPost($this->post)) {
				$this->showPostScreen($this->phrase('_EDIT_MESSAGE:'), $this->phrase('_SAVE_POST'), 'edit', $this->phrase('_EDIT_TITLE:'));
			
			} elseif (request('comm_request') == 'edit_post' && $this->canEditPost($this->post)) {
				$this->showPostScreen($this->phrase('_EDIT_MESSAGE:'), $this->phrase('_SAVE_POST'), 'edit');
			
			} elseif (request('comm_request') == 'post_reply' && $this->canMakePost()) {
				$this->showPostScreen($this->phrase('_ADD_REPLY:'), $this->phrase('_ADD_REPLY'), 'quote');
			
			} elseif (request('comm_request') == 'report_post' && $this->canReportPost()) {
				$this->showPostScreen($this->phrase('_REPORT_MESSAGE:'), $this->phrase('_REPORT_POST'), 'none');
			}
			
		} else {
			$this->showThreadActions();
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	public static function commentsOnPage($cID, $cType = 'html') {
		return (int) getRow(
			ZENARIO_ANONYMOUS_COMMENTS_PREFIX. 'comment_content_items',
			'post_count',
			array('content_id' => (int) $cID, 'content_type' => $cType));
	}
	
	public static function commentsOnPage_framework(&$mergeFields) {
		$cID = pullFromArray($mergeFields, 'cid', 'contentid', 'id');
		$cType = pullFromArray($mergeFields, 'ctype', 'contenttype', 'type');
		
		return zenario_anonymous_comments::commentsOnPage($cID, $cType);
	}
	
	public static function commentsOnThisPage_framework() {
		return zenario_anonymous_comments::commentsOnPage(cms_core::$cID, cms_core::$cType);
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		require funIncPath(__FILE__, __FUNCTION__);
	}

	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {	
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
}