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


$forumMessage = ze\row::get(ZENARIO_FORUM_PREFIX. "user_posts", ['message_text', 'poster_id'], ['id' => $postId]);

$userId = $forumMessage['poster_id'];


$formFields = [
	'cms_url' => ze\link::absolute(),
	'forum_link' =>
		ze\link::toItem(
			$this->forum['forum_content_id'], $this->forum['forum_content_type'], true,
			'', false, false, true),
	'forum_title' => ze\content::title($this->forum['forum_content_id'], $this->forum['forum_content_type']),
	'message' => $forumMessage['message_text'],
	'thread_link' =>
		ze\link::toItem(
			$this->forum['thread_content_id'], $this->forum['thread_content_type'], true,
			'&forum_thread='. $this->threadId. '&comm_page='. $this->page, false, false, true),
	'thread_title' => $newThreadTitle? $newThreadTitle : ze\row::get(ZENARIO_FORUM_PREFIX. 'threads', 'title', $this->threadId),
	'poster_screen_name' => ''];

if (ze::setting('user_use_screen_name')) {
	$formFields['poster_screen_name'] = ze\user::screenName($userId);
}

if ($this->setting('send_notification_email')
 && $this->setting('notification_email_address')
 && (($newThreadTitle === false && $this->setting('post_notification_email_template'))
  || ($newThreadTitle !== false && $this->setting('new_thread_notification_email_template')))
 && ze\module::inc('zenario_email_template_manager')) {
	
	zenario_email_template_manager::sendEmailsUsingTemplate(
		$this->setting('notification_email_address'),
		$newThreadTitle === false?
			$this->setting('post_notification_email_template')
		 :	$this->setting('new_thread_notification_email_template'),
		$formFields,
		[],
		[],
		['message' => true]);
}





if ($newPost
 && (($newThreadTitle === false && $this->setting('enable_thread_subs') && $this->setting('post_subs_email_template'))
  || ($newThreadTitle !== false && $this->setting('enable_forum_subs') && $this->setting('new_thread_subs_email_template')))
 && ze\module::inc('zenario_email_template_manager')) {
	
	$sql = "
		SELECT u.id, u.salutation, u.first_name, u.last_name, u.screen_name, u.email
		FROM ". DB_PREFIX. ZENARIO_COMMENTS_PREFIX. "user_subscriptions AS us
		INNER JOIN ". DB_PREFIX. "users AS u
		   ON u.id = us.user_id
		  AND u.status = 'active'
		INNER JOIN ". DB_PREFIX. ZENARIO_COMMENTS_PREFIX. "users AS cu
		   ON cu.user_id = u.id
		  AND IFNULL(cu.latest_activity > us.last_notified, true)
		WHERE us.forum_id = ". (int) $this->forumId. "
		  AND us.thread_id = ". (int) ($newThreadTitle === false? $this->threadId : 0). "
		  AND us.user_id != ". (int) $userId;
	
	$result = ze\sql::select($sql);
	while ($row = ze\sql::fetchAssoc($result)) {
		if ($row['email']) {
			
			foreach ($row as $fieldName => &$field) {
				$formFields['subscriber_'. $fieldName] = $field;
			}
			
			if (!ze::setting('user_use_screen_name')) {
				$formFields['subscriber_screen_name'] = '';
			}
			
			zenario_email_template_manager::sendEmailsUsingTemplate(
				$row['email'],
				$newThreadTitle === false?
					$this->setting('post_subs_email_template')
				 :	$this->setting('new_thread_subs_email_template'),
				$formFields,
				[],
				[],
				['message' => true]);
			
			ze\row::update(
				ZENARIO_COMMENTS_PREFIX. 'user_subscriptions',
				['last_notified' => ze\date::now()],
				[
					'user_id' => $row['id'],
					'forum_id' => $this->forumId,
					'thread_id' => $newThreadTitle === false? $this->threadId : 0]);
		}
	}
}

?>