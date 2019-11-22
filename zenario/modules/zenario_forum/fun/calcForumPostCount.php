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


//Update the forums's post count
if ($quickAdd && $userId) {
	$sql = "
		UPDATE ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "forums SET
			date_updated = NOW(),
			updater_id = ". (int) $userId. ",
			post_count = post_count + 1";
	
	if ($firstPost) {
		$sql .= ",
			thread_count = thread_count + 1";
	}
	
	$sql .= "
		WHERE id = ". (int) $forumId;
} else {
	$sql = "
		SELECT poster_id, date_posted
		FROM ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_posts
		WHERE forum_id = ". (int) $forumId. "
		ORDER BY id DESC
		LIMIT 1";
	
	$result = ze\sql::select($sql);
	if ($row = ze\sql::fetchAssoc($result)) {
		$dateUpdated = "'". ze\escape::sql($row['date_posted']). "'";
		$updaterId = $row['poster_id'];
	} else {
		$dateUpdated = "NOW()";
		$updaterId = 0;
	}
	
	$sql = "
		UPDATE ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "forums SET
			date_updated = ". $dateUpdated. ",
			updater_id = ". (int) $updaterId. ",
			thread_count = (
				SELECT COUNT(*)
				FROM ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "threads
				WHERE forum_id = ". (int) $forumId. "
			),
			post_count = (
				SELECT COUNT(*)
				FROM ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "user_posts
				WHERE forum_id = ". (int) $forumId. "
			)
		WHERE id = ". (int) $forumId;
}

$result = ze\sql::update($sql);

?>