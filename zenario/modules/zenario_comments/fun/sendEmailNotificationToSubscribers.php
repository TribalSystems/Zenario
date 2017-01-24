<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

$comment = getRow(ZENARIO_ANONYMOUS_COMMENTS_PREFIX . 'user_comments', array(
									'poster_id', 
									'message_text', 
									'content_id', 
									'content_type',
									'poster_name', 
									'poster_email'
									),
								array( 'id' => (int) $commentId));

$poster = getUserDetails($comment['poster_id']);


$formFields = array(
	'cms_url' => absCMSDirURL(),
	'link' => linkToItem($comment['content_id'], $comment['content_type'], true, '', false, false, true),
	'message' => $comment['message_text'],
	'page_title' => getItemTitle($comment['content_id'], $comment['content_type']),
	'poster_screen_name' => '');

if (setting('user_use_screen_name')) {
	$formFields['poster_screen_name'] = userScreenName($poster['id']);
}

$subscriptionsConfig =  getRow(ZENARIO_ANONYMOUS_COMMENTS_PREFIX  . 'comment_content_items', array(
																						'enable_subs',
																						'comment_subs_email_template'
																						),
																					array(	'content_id' => $comment['content_id'],
																							'content_type' => $comment['content_type']
																						 ) );
																						 

if ($newPost
 && $subscriptionsConfig['enable_subs']
 && $subscriptionsConfig['comment_subs_email_template']
 && inc('zenario_email_template_manager')) {
	
	$sql = "
		SELECT u.id, u.salutation, u.first_name, u.last_name, u.screen_name, u.email
		FROM ". DB_NAME_PREFIX. ZENARIO_COMMENTS_PREFIX. "user_subscriptions AS us
		INNER JOIN ". DB_NAME_PREFIX. "users AS u
		   ON u.id = us.user_id
		  AND u.status = 'active'
		INNER JOIN ". DB_NAME_PREFIX. ZENARIO_COMMENTS_PREFIX. "users AS cu
		   ON cu.user_id = u.id
		  AND IFNULL(cu.latest_activity > us.last_notified, true)
		WHERE us.content_id = ". $comment['content_id'] . "
		  AND us.content_type = '". $comment['content_type'] . "'
		  AND us.forum_id = 0
		  AND us.thread_id = 0
		  AND us.user_id != ". (int) $poster['id'];
	
	$result = sqlQuery($sql);
	
	while ($row = sqlFetchAssoc($result)) {
		if ($row['email']) {
			
			foreach ($row as $fieldName => &$field) {
				$formFields['subscriber_'. $fieldName] = $field;
			}
			
			if (!setting('user_use_screen_name')) {
				$formFields['subscriber_screen_name'] = '';
			}
			
			zenario_email_template_manager::sendEmailsUsingTemplate(
				$row['email'],
				$subscriptionsConfig['comment_subs_email_template'],
				$formFields,
				array(),
				array(),
				array('message' => true));
			
			updateRow(
				ZENARIO_COMMENTS_PREFIX. 'user_subscriptions',
				array('last_notified' => now()),
				array(
					'user_id' => $row['id'],
					'content_id' => $comment['content_id'],
					'content_type' => $comment['content_type'],
					'forum_id' => 0,
					'thread_id' => 0));
		}
	}
}
