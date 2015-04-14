<?php
/*
 * Copyright (c) 2015, Tribal Limited
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


$sql = "
	SELECT forum_id, last_updated_order
	FROM ". DB_NAME_PREFIX. ZENARIO_FORUM_PREFIX. "threads
	WHERE id = ". (int) $threadId. "
	  AND forum_id != ". (int) $this->forumId;

if (($result = sqlQuery($sql)) && ($thread = sqlFetchAssoc($result))) {
	$oldForumId = $thread['forum_id'];

	//Move the last updated orders around to make up for the fact that this thread is disappearing
	$this->markOtherThreadsAsLessRecent($thread['last_updated_order'], $oldForumId);
	
	//Move the thread into this forum
	$latestUpdated = $this->getLatestUpdated() + 1;
	$sql = "
		UPDATE ". DB_NAME_PREFIX. ZENARIO_FORUM_PREFIX. "threads SET
			forum_id = ". (int) $this->forumId. ",
			last_updated_order = ". (int) $latestUpdated. "
		WHERE id = ". (int) $threadId;
	sqlQuery($sql);
	
	$sql = "
		UPDATE ". DB_NAME_PREFIX. ZENARIO_FORUM_PREFIX. "user_posts SET
			forum_id = ". (int) $this->forumId. "
		WHERE thread_id = ". (int) $threadId;
	sqlQuery($sql);
	
	//Mark the thread as unread for everyone
	$this->markThreadAsUnRead($latestUpdated);
	
	//Update the post count for the forums
	$this->calcForumPostCount($oldForumId);
	$this->calcForumPostCount($this->forumId);
}

?>