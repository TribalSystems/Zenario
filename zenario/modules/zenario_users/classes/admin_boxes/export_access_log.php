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

class zenario_users__admin_boxes__export_access_log extends zenario_users {
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$headers = array();
		$rows = array();
		$filename = false;
		$format = $values['details/format'];
		$filename = str_replace('/', ' ', $box['key']['filename']);
		// Export accesses for content item
		if ($tagId = $box['key']['tag_id']) {
			$headers = array(
				'Time accessed',
				'User ID',
				'First name',
				'Last name',
				'Email',
				'IP address'
			);
			$cID = $cType = false;
			getCIDAndCTypeFromTagId($cID, $cType, $tagId);
			$sql = '
				SELECT l.hit_datetime, l.user_id, [u.first_name], [u.last_name], [u.email], l.ip
				FROM [user_content_accesslog AS l]
				INNER JOIN [users AS u]
					ON l.user_id = u.id
				WHERE l.content_id = [0]
				  AND l.content_type = [1]
				ORDER BY l.hit_datetime DESC';
			$result = sqlSelect($sql, [$cID, $cType]);
			while ($row = sqlFetchAssoc($result)) {
				$rows[] = $row;
			}
		// Export accesses for user
		} elseif ($userId = $box['key']['user_id']) {
			$headers = array(
				'Time accessed',
				'Content item',
				'IP address'
			);
			$sql = '
				SELECT l.hit_datetime, l.content_id, l.content_type, l.content_version, l.ip
				FROM ' . DB_NAME_PREFIX . 'user_content_accesslog l
				WHERE l.user_id = ' . (int)$userId . '
				ORDER BY l.hit_datetime DESC';
			$result = sqlSelect($sql);
			while ($row = sqlFetchAssoc($result)) {
				$contentAccess = array(
					$row['hit_datetime'],
					formatTag($row['content_id'], $row['content_type']),
					$row['ip']
				);
				$rows[] = $contentAccess;
			}
		}
		
		if ($format && $filename) {
			exportPanelItems($headers, $rows, $format, $filename);
		}
	}
	
}