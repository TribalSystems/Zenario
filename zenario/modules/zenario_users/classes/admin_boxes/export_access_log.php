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

class zenario_users__admin_boxes__export_access_log extends zenario_users {
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$headers = [];
		$rows = [];
		$filename = false;
		$format = $values['details/format'];
		$filename = str_replace('/', ' ', $box['key']['filename']);
		// Export accesses for content item
		if ($tagId = $box['key']['tag_id']) {
			$headers = [
				'Time accessed',
				'User ID',
				'First name',
				'Last name',
				'Email'
			];
			$cID = $cType = false;
			ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
			$sql = '
				SELECT l.hit_datetime, u.id AS user_id, u.first_name, u.last_name, u.email
				FROM '. DB_PREFIX. 'user_content_accesslog AS l
				INNER JOIN '. DB_PREFIX. 'users AS u
					ON l.user_id = u.id
				WHERE l.content_id = '. (int) $cID. '
				  AND l.content_type = \''. \ze\escape::sql($cType). '\'
				ORDER BY l.hit_datetime DESC';
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
				$rows[] = $row;
			}
		// Export accesses for user
		} elseif ($userId = $box['key']['user_id']) {
			$headers = [
				'Time accessed',
				'Content item'
			];
			$sql = '
				SELECT l.hit_datetime, l.content_id, l.content_type, l.content_version
				FROM ' . DB_PREFIX . 'user_content_accesslog l
				WHERE l.user_id = ' . (int)$userId . '
				ORDER BY l.hit_datetime DESC';
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
				$contentAccess = [
					$row['hit_datetime'],
					ze\content::formatTag($row['content_id'], $row['content_type'])
				];
				$rows[] = $contentAccess;
			}
		}
		
		if ($format && $filename) {
			ze\miscAdm::exportPanelItems($headers, $rows, $format, $filename);
		}
	}
	
}