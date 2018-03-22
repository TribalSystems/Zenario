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


//Send notification emails to Users

$comment = ze\row::get(ZENARIO_ANONYMOUS_COMMENTS_PREFIX . 'user_comments', [
									'poster_id', 
									'message_text', 
									'content_id', 
									'content_type',
									'poster_name', 
									'poster_email'
									],
								[ 'id' => (int) $commentId]);

$poster = ze\user::details($comment['poster_id']);


$formFields = [
	'cms_url' => ze\link::absolute(),
	'link' => ze\link::toItem($comment['content_id'], $comment['content_type'], true, '', false, false, true),
	'message' => $comment['message_text'],
	'page_title' => ze\content::title($comment['content_id'], $comment['content_type']),
	'poster_screen_name' => ''];

if (ze::setting('user_use_screen_name')) {
	$formFields['poster_screen_name'] = ze\user::screenName($poster['id']);
}

$subscriptionsConfig =  ze\row::get(ZENARIO_ANONYMOUS_COMMENTS_PREFIX  . 'comment_content_items', [
																						'enable_subs',
																						'comment_subs_email_template'
																						],
																					[	'content_id' => $comment['content_id'],
																							'content_type' => $comment['content_type']
																						 ] );
																						 

if ($newPost
 && $subscriptionsConfig['enable_subs']
 && $subscriptionsConfig['comment_subs_email_template']
 && ze\module::inc('zenario_email_template_manager')) {
	
	$sql = "
		SELECT u.id, [u.salutation], [u.first_name], [u.last_name], [u.screen_name], [u.email]
		FROM [ZENARIO_COMMENTS_PREFIX.user_subscriptions AS us]
		INNER JOIN [users AS u]
		   ON u.id = us.user_id
		  AND u.status = 'active'
		INNER JOIN [ZENARIO_COMMENTS_PREFIX.users AS cu]
		   ON cu.user_id = u.id
		  AND IFNULL(cu.latest_activity > us.last_notified, true)
		WHERE us.content_id = [0]
		  AND us.content_type = [1]
		  AND us.forum_id = 0
		  AND us.thread_id = 0
		  AND us.user_id != [2]";
	
	$result = ze\sql::select($sql, [$comment['content_id'], $comment['content_type'], $poster['id']]);
	
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
				$subscriptionsConfig['comment_subs_email_template'],
				$formFields,
				[],
				[],
				['message' => true]);
			
			ze\row::update(
				ZENARIO_COMMENTS_PREFIX. 'user_subscriptions',
				['last_notified' => ze\date::now()],
				[
					'user_id' => $row['id'],
					'content_id' => $comment['content_id'],
					'content_type' => $comment['content_type'],
					'forum_id' => 0,
					'thread_id' => 0]);
		}
	}
}
