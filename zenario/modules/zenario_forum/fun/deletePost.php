<?php
/*
 * Copyright (c) 2020, Tribal Limited
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


//Work out who posted this post
$sql = "
	SELECT poster_id
	FROM ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_posts
	WHERE thread_id = ". (int) $this->threadId. "
	  AND id = ". (int) $this->post['id'];

$result = ze\sql::select($sql);
$row = ze\sql::fetchAssoc($result);
$userId = $row['poster_id'];


//Check to see if this is the last post in the thread
$sql = "
	SELECT MAX(id)
	FROM ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_posts
	WHERE thread_id = ". (int) $this->threadId;

$result = ze\sql::select($sql);
list($maxId) = ze\sql::fetchRow($result);


//Remove the post
$sql = "
	DELETE FROM ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_posts
	WHERE thread_id = ". (int) $this->threadId. "
	  AND id = ". (int) $this->post['id'];
ze\sql::update($sql);


//Update the user's post count
$this->calcUserPostCount($userId);

//Update the post count for the thread
$this->calcThreadPostCount($this->threadId);

//Update the post count for the forum
$this->calcForumPostCount($this->forumId);

