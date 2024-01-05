<?php
/*
 * Copyright (c) 2024, Tribal Limited
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

$newPostId = 0;

//Make sure that this user has an entry in the forum user details table
$this->checkUserIsInForumsUserTable($userId);

//Add the post
$sql = "
	INSERT INTO ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_posts SET
		forum_id = ". (int) $this->forumId. ",
		thread_id = ". (int) $this->threadId. ",
		first_post = ". (int) $firstPost. ",
		date_posted = NOW(),
		poster_id = ". (int) $userId. ",
		message_text = '". ze\escape::sql(zenario_anonymous_comments::sanitiseHTML($messageText, $this->setting('enable_images'), $this->setting('enable_links'))). "'";

ze\sql::update($sql);
$newPostId = ze\sql::insertId();

//Update the user's post count
$this->calcUserPostCount($userId, true);

//Update the post count for the thread
$this->calcThreadPostCount($this->threadId, true, $userId);

//Update the post count for the forum
$this->calcForumPostCount($this->forumId, true, $userId, $firstPost);


if (!$firstPost) {
	//Move the last updated orders around if we are bumping an old thread
	$latestUpdated = $this->getLatestUpdated();
	$threadLastUpdated = zenario_forum::getThreadLastUpdated($this->forumId, $this->threadId);
	
	$this->markOtherThreadsAsLessRecent($threadLastUpdated, $this->forumId);
	$this->markThreadAsMostRecent($latestUpdated);
	$this->markThreadAsUnRead($latestUpdated);
	$this->markThreadAsRead($userId);
}

//Show the last page
$this->page = -1;

if (!$firstPost) {
	$this->sendEmailNotification($newPostId, true);
}

$this->manageUploads($newPostId);

return $newPostId;