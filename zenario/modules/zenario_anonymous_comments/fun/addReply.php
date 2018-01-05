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


//Add the comment
$sql = "
	INSERT INTO ". DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "user_comments SET
		content_id = ". (int) $this->cID. ",
		content_type = '". ze\escape::sql($this->cType). "',
		date_posted = NOW(),
		poster_id = ". (int) $userId. ",
		poster_ip = '". ze\escape::sql(ze\user::ip()). "',
		poster_name = '". ze\escape::sql($name). "',
		poster_email = '". ze\escape::sql($email). "',
		poster_session_id = '". ze\escape::sql(ze\user::hashPassword(ze\link::primaryDomain(), session_id())). "',
		message_text = '". ze\escape::sql(zenario_anonymous_comments::sanitiseHTML($messageText, $this->setting('enable_images'), $this->setting('enable_links'))). "',
		status = '" . $this->defaultReplyStatus() . "'";

ze\sql::update($sql);
$commentId = ze\sql::insertId();

//Update the post count for the thread
$sql = "
	UPDATE ". DB_NAME_PREFIX. ZENARIO_ANONYMOUS_COMMENTS_PREFIX. "comment_content_items SET
		date_updated = NOW(),
		updater_id = ". (int) $userId. ",
		post_count = post_count + 1, 
		send_notification_email = " . (int) $this->setting('send_notification_email') . ",
		notification_email_template = '" . ze\escape::sql($this->setting('notification_email_template')) . "',
		notification_email_address = '" . ze\escape::sql($this->setting('notification_email_address')) . "',
		enable_subs = " .(int) $this->setting('enable_subs') . ",
		comment_subs_email_template = '" . ze\escape::sql($this->setting('comment_subs_email_template')) . "'
	WHERE content_id = ". (int) $this->cID. "
	  AND content_type = '". ze\escape::sql($this->cType). "'";

ze\sql::update($sql);

//Show the last page
$this->page = $this->setting('order') == 'MOST_RECENT_FIRST'? 1 : -1;

if ($this->defaultReplyStatus() == 'pending') {
	$this->sendApproveRequest($userId, $messageText, $name, $email);
} else {
	$this->sendEmailNotification($commentId);
}
