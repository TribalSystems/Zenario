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


//Make sure that this user has an entry in the forum user details table
$this->checkUserIsInForumsUserTable($userId);

//Add the thread
$latestUpdated = $this->getLatestUpdated() + 1;
$sql = "
	INSERT INTO ". DB_NAME_PREFIX. ZENARIO_FORUM_PREFIX. "threads SET
		forum_id = ". (int) $this->forumId. ",
		date_posted = NOW(),
		date_updated = NOW(),
		last_updated_order = ". (int) $latestUpdated. ",
		employee_post = ". (int) !empty($_SESSION['extranetUserIsAnEmployee']). ",
		poster_id = ". (int) $userId. ",
		post_count = 0,
		title = '". sqlEscape($threadTitle). "'";

sqlUpdate($sql);
$this->threadId = sqlInsertId();

$postId = $this->addReply($userId, $messageText, $firstPost = 1);
$this->markThreadAsUnRead($latestUpdated);

//Show the first page
$this->page = 1;

$this->sendEmailNotification($postId, true, $threadTitle);
