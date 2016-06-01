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

class zenario_content_notifications__admin_boxes__content_requests extends zenario_content_notifications {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if ($box['key']['cID'] && $box['key']['cType']) {
			$box['key']['id'] = $box['key']['cType']. '_'. $box['key']['cID'];
		} else {
			getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $box['key']['id']);
		}
		
		$latestVersion = getLatestVersion($box['key']['cID'], $box['key']['cType']);
		if (!$box['key']['cVersion']) {
			$box['key']['cVersion'] = $latestVersion;
		
		} elseif ($box['key']['cVersion'] != $latestVersion) {
			unset($box['tabs']['requests']['edit_mode']);
		}
		
		if (!isDraft($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'])) {
			unset($fields['action_requested']['values']['publish']);
		}
		
		if ($box['key']['action_requested']) {
			$values['action_requested'] = $box['key']['action_requested'];
		}
		
		$box['title'] = adminPhrase('Requests for the content item "[[tag]]"', array('tag' => formatTag($box['key']['cID'], $box['key']['cType'])));
		
		
		$values['existing_requests'] = '';
		foreach (self::getNotes($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion']) as $note) {
			
			if ($values['existing_requests'] !== '') {
				$values['existing_requests'] .= "\n\n\n";
			}
			
			$values['existing_requests'] .= self::noteHeader($note). "\n\n". $note['note'];
		}
		
		$values['recipients'] = inEscape(array_keys(self::getListOfAdminsWhoReceiveNotifications()), 'numeric');
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		if (!empty($box['tabs']['requests']['edit_mode']['enabled'])
		 && $values['action_requested']) {
			$newNote = $this->newNote($box, $values);
			$fields['request_details']['snippet']['html'] = nl2br(htmlspecialchars(self::noteHeader($newNote)));
		}
	}

	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($values['action_requested']  == 'publish'
		 && !isDraft($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'])) {
			$box['tabs']['requests']['errors'][] = adminPhrase('This content item does not have a draft to publish.');
		}
	}

	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$note = $this->newNote($box, $values);
		
		insertRow(ZENARIO_CONTENT_NOTIFICATIONS_PREFIX. 'versions_mirror', $note);
		
		$messageText = self::noteHeader($note). "\n\n". $note['note'];
		
		foreach (self::getListOfAdminsWhoReceiveNotifications() as $admin) {
			$addressToOverriddenBy = '';
			sendEmail('Website content request: Item request', $messageText, $admin['email'], $addressToOverriddenBy, $nameTo = false, $addressFrom = false, $nameFrom = false, $attachments = array(), $attachmentFilenameMappings = array(), $precedence = 'bulk', $isHTML = false);
		}
	}

}
