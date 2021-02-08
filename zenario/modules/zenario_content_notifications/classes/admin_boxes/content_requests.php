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


class zenario_content_notifications__admin_boxes__content_requests extends zenario_content_notifications {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		//Get cID and cType
		if ($box['key']['cID'] && $box['key']['cType']) {
			$box['key']['id'] = $box['key']['cType']. '_'. $box['key']['cID'];
		} else {
			ze\content::getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $box['key']['id']);
		}
		//Get cVersion
		$latestVersion = ze\content::latestVersion($box['key']['cID'], $box['key']['cType']);
		if (!$box['key']['cVersion']) {
			$box['key']['cVersion'] = $latestVersion;
		//Don't allow new notes on old versions
		} elseif ($box['key']['cVersion'] != $latestVersion) {
			unset($box['tabs']['requests']['edit_mode']);
		}
		
		$box['title'] = ze\admin::phrase('Notes and requests for the content item "[[tag]]"', ['tag' => ze\content::formatTag($box['key']['cID'], $box['key']['cType'])]);
		
		//Pre-open the new note action
		if ($box['key']['action_requested']) {
			$values['requests/action'] = $box['key']['action_requested'];
		}
		
		//Load notes for this content item (all versions)
		$sql = '
			SELECT content_id, content_type, content_version, admin_id, type, datetime_created, note
			FROM ' . DB_PREFIX . ZENARIO_CONTENT_NOTIFICATIONS_PREFIX . 'versions_mirror
			WHERE content_id = ' . (int)$box['key']['cID'] . '
			AND content_type = "' . ze\escape::sql($box['key']['cType']) . '"
			ORDER BY datetime_created DESC';
		$result = ze\sql::select($sql);
		$i = 0;
		while ($note = ze\sql::fetchAssoc($result)) {
			$box['tabs']['requests']['fields']['note_' . ++$i] = [
				'pre_field_html' => static::getNoteHeader(
					$note['admin_id'], 
					$note['datetime_created'], 
					$note['content_id'], 
					$note['content_type'], 
					$note['content_version'], 
					$note['type']
				),
				'type' => 'textarea',
				'value' => $note['note'],
				'ord' => $i + 100,
				'class' => 'ab_note',
				'style' => 'min-height:100px',
				'readonly' => true,
			];
		}
		
		if (ze\sql::numRows($result) == 0) {
			$fields['requests/line']['snippet']['html'] .= '<br><br><p>' . ze\admin::phrase('No notes or requests found.') . '</p>';
		}
		
		//Load recipients for new requests
		$values['requests/recipients'] = ze\escape::in(array_keys(self::getListOfAdminsWhoReceiveNotifications()), 'numeric');
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Add a header when making a new note
		if ($values['requests/action']) {
			$title = '';
			if ($values['requests/action'] == 'send_request') {
				$title = 'New request:';
			} else {
				$title = 'New note:';
			}
			$fields['requests/note']['pre_field_html'] = static::getNoteHeader(
				ze\admin::id(), 
				ze\date::now(), 
				$box['key']['cID'],
				$box['key']['cType'],
				$box['key']['cVersion'],
				$title
			);
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Save a note
		if ($values['requests/action'] && ($note = trim($values['requests/note']))) {
			$type = $values['requests/action'] == 'send_request' ? 'request' : 'note';
			
			ze\row::insert(
				ZENARIO_CONTENT_NOTIFICATIONS_PREFIX. 'versions_mirror',
				[
					'content_id' => $box['key']['cID'], 
					'content_type' => $box['key']['cType'],
					'content_version' => $box['key']['cVersion'],
					'admin_id' => ze\admin::id(),
					'type' => $type,
					'datetime_created' => ze\date::now(),
					'note' => $note
				]	
			);
			
			//Send to admins if this is a request
			if ($type == 'request') {
				$admins = self::getListOfAdminsWhoReceiveNotifications();
				$header = static::getNoteHeader(
					ze\admin::id(), 
					ze\date::now(), 
					$box['key']['cID'],
					$box['key']['cType'],
					$box['key']['cVersion'],
					'New request:',
					$link = true
				);
				foreach($admins as $admin) {
					$addressToOverriddenBy = '';
					$body = $header . '<br><br>' . nl2br(htmlspecialchars($note));
					ze\server::sendEmail('Website content request: Item request', $body, $admin['email'], $addressToOverriddenBy, $nameTo = false, $addressFrom = false, $nameFrom = false, $attachments = [], $attachmentFilenameMappings = [], $precedence = 'bulk', $isHTML = true);
				}
			}
		}
	}

}
