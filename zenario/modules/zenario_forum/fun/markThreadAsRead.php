<?php
/*
 * Copyright (c) 2021, Tribal Limited
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


$threadLastUpdated = zenario_forum::getThreadLastUpdated($this->forumId, $this->threadId);

//Check if the thread has already been read by this user first
if (zenario_forum::markThreadCheckUserHasReadThread($threadLastUpdated, $this->forumId)) {
	return;
}

//We're actively trying to keep the data in the user_unread_threads table as compact as possible.
//The best case would be is if there is a span of just the right size, we can actually delete it
//Next check for an existing span just to the left or to the right, and attempt to contract it.
//Finally if we didn't find our optimal case, then it means that a span is completely covering this position. In that case
//it needs to be split in to two spans to create a break here.
switch(1) {
  default:
	
	$sql = "
		DELETE FROM ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_unread_threads
		WHERE unread_from = ". (int) $threadLastUpdated. "
		  AND unread_to = ". (int) $threadLastUpdated. "
		  AND forum_id = ". (int) $this->forumId. "
		  AND reader_id = ". (int) $readerId;
	
	ze\sql::update($sql, false, false);
	if (ze\sql::affectedRows()) {
		break;
	}
	
	$sql = "
		UPDATE ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_unread_threads SET
			unread_from = ". ((int) $threadLastUpdated + 1). "
		WHERE unread_from = ". (int) $threadLastUpdated. "
		  AND forum_id = ". (int) $this->forumId. "
		  AND reader_id = ". (int) $readerId;
	
	ze\sql::update($sql, false, false);
	if (ze\sql::affectedRows()) {
		break;
	}
	
	$sql = "
		UPDATE ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_unread_threads SET
			unread_to = ". ((int) $threadLastUpdated - 1). "
		WHERE unread_to = ". (int) $threadLastUpdated. "
		  AND forum_id = ". (int) $this->forumId. "
		  AND reader_id = ". (int) $readerId;
	
	ze\sql::update($sql, false, false);
	if (ze\sql::affectedRows()) {
		break;
	}
	
	$sql = "
		SELECT unread_from, unread_to
		FROM ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_unread_threads
		WHERE unread_from < ". (int) $threadLastUpdated. "
		  AND unread_to > ". (int) $threadLastUpdated. "
		  AND forum_id = ". (int) $this->forumId. "
		  AND reader_id = ". (int) $readerId;
	
	$result = ze\sql::select($sql);
	if ($row = ze\sql::fetchAssoc($result)) {
		$sql = "
			UPDATE ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_unread_threads SET
				unread_to = ". ((int) $threadLastUpdated - 1). "
			WHERE unread_from = ". (int) $row['unread_from']. "
			  AND unread_to = ". (int) $row['unread_to']. "
			  AND forum_id = ". (int) $this->forumId. "
			  AND reader_id = ". (int) $readerId;
		ze\sql::update($sql, false, false);
		
		$sql = "
			INSERT INTO ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_unread_threads SET
				unread_from = ". ((int) $threadLastUpdated + 1). ",
				unread_to = ". (int) $row['unread_to']. ",
				forum_id = ". (int) $this->forumId. ",
				reader_id = ". (int) $readerId;
		ze\sql::update($sql, false, false);
	}
	
	break;
}

//Tidy up any zero-sized spans
$sql = "
	DELETE FROM ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_unread_threads
	WHERE unread_from = ". (int) $threadLastUpdated. "
	  AND unread_to = ". (int) $threadLastUpdated. "
	  AND forum_id = ". (int) $this->forumId. "
	  AND reader_id = ". (int) $readerId;
ze\sql::update($sql, false, false);

//Update the thread's view-count
$sql = "
	UPDATE ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "threads SET
		view_count = view_count + 1
	WHERE id = ". (int) $this->threadId;
ze\sql::update($sql, false, false);