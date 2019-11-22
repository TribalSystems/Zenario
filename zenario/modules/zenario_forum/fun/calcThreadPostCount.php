<?php
/*
 * Copyright (c) 2019, Tribal Limited
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


//Update the user's post count
if ($quickAdd && $userId) {
	$sql = "
		UPDATE ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "threads SET
			date_updated = NOW(),
			updater_id = ". (int) $userId. ",
			post_count = post_count + 1
		WHERE id = ". (int) $threadId;
} else {
	//Get details of the id of the new latest post
	$sql = "
		SELECT id, poster_id, date_posted
		FROM ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_posts
		WHERE thread_id = ". (int) $threadId. "
		ORDER BY id DESC
		LIMIT 1";
	
	$result = ze\sql::select($sql);
	$newLatestPost = ze\sql::fetchAssoc($result);
	
	$sql = "
		UPDATE ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "threads SET
			date_updated = '". ze\escape::sql($newLatestPost['date_posted']). "',
			updater_id = ". (int) $newLatestPost['poster_id']. ",
			post_count = (
				SELECT COUNT(*)
				FROM ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_posts
				WHERE thread_id = ". (int) $threadId. "
			)
		WHERE id = ". (int) $threadId;
}

$result = ze\sql::update($sql);

