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

//Send notification emails to Admins

$comment = getRow(ZENARIO_ANONYMOUS_COMMENTS_PREFIX . 'user_comments', array(
									'poster_id', 
									'message_text', 
									'content_id', 
									'content_type',
									'poster_name', 
									'poster_email'
									),
								array( 'id' => (int) $commentId)
				);

$poster = array();
if ($comment['poster_id'] && inc('zenario_users')) {
	$poster = getUserDetails($comment['poster_id']);
}

$formFields = array(
	'cms_url' => absCMSDirURL(),
	'link' => linkToItem($comment['content_id'], $comment['content_type'], true, '', false, false, true),
	'message' => $comment['message_text'],
	'page_title' => getItemTitle($comment['content_id'], $comment['content_type']),
	'poster_screen_name' => '');

if (!empty($poster['id'])) {
	$formFields['poster_screen_name'] = $this->getUserScreenName($poster['id']);

} elseif ($comment['poster_name'] && $comment['poster_email']) {
	$formFields['poster_username'] =
	$formFields['poster_screen_name'] = $comment['poster_name']. ' ('. $comment['poster_email']. ')';

} elseif ($comment['poster_name']) {
	$formFields['poster_username'] =
	$formFields['poster_screen_name'] = $comment['poster_name'];

} elseif ($comment['poster_email']) {
	$formFields['poster_username'] =
	$formFields['poster_screen_name'] = $comment['poster_email'];
}

$notification = getRow(ZENARIO_ANONYMOUS_COMMENTS_PREFIX  . 'comment_content_items', array(
																						'send_notification_email',
																						'notification_email_address',
																						'notification_email_template'
																						),
																					array(	'content_id' => $comment['content_id'],
																							'content_type' => $comment['content_type']
																						 ) );

if ($notification['send_notification_email'] && $notification['notification_email_address'] && 
		$notification['notification_email_template'] && inc('zenario_email_template_manager')) {
	
	//Hack for backwards compatability with layouts using the old merge fields
	$formFields['title'] = $formFields['page_title'];
	$formFields['comment'] = $formFields['message'];
	$formFields['username'] = $formFields['poster_username'];
	
	zenario_email_template_manager::sendEmailsUsingTemplate(
		$notification['notification_email_address'],
		$notification['notification_email_template'],
		$formFields,
		array(),
		array(),
		array('message' => true));
}