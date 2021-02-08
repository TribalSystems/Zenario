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


class zenario_content_notifications__admin_boxes__admin extends zenario_content_notifications {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($details = self::getAdminNotifications($box['key']['id'])) {
			$values['notifications_tab/draft_notification'] = $details['draft_notification'];
			$values['notifications_tab/published_notification'] = $details['published_notification'];
			$values['notifications_tab/menu_node_notification'] = $details['menu_node_notification'];
			$values['notifications_tab/content_request_notification'] = $details['content_request_notification'];
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($values['permissions/permissions']) {
			case 'all_permissions':
				$notificationPerms = true;
				break;
			
			case 'specific_actions':
				$notificationPerms = 
					!empty($values['permissions/perm_publish_permissions'])
				 && strpos($values['permissions/perm_publish_permissions'], '_PRIV_APPEAR_ON_CONTENT_REQUEST_RECIPIENT_LIST') !== false;
				break;
			
			default:
				$notificationPerms = false;
		}
		
		$fields['notifications_tab/no_notifications']['hidden'] = $notificationPerms;
		
		$fields['notifications_tab/content_request_notification']['hidden'] =
		$fields['notifications_tab/draft_notification']['hidden'] =
		$fields['notifications_tab/published_notification']['hidden'] =
		$fields['notifications_tab/menu_node_notification']['hidden'] = !$notificationPerms;
		
		//Allow an admin to edit their own notifications, otherwise check the _PRIV_EDIT_ADMIN permission
		$box['tabs']['notifications_tab']['edit_mode']['enabled'] =
			$notificationPerms && ($box['key']['id'] == ze\admin::id() || ze\priv::check('_PRIV_EDIT_ADMIN'));
	}

	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Allow an admin to edit their own notifications, otherwise check the _PRIV_EDIT_ADMIN permission
		if (ze\ring::engToBoolean($box['tabs']['notifications_tab']['edit_mode']['on'] ?? false)
		 && ($box['key']['id'] == ze\admin::id() || ze\priv::check('_PRIV_EDIT_ADMIN'))) {
			ze\row::set(
				ZENARIO_CONTENT_NOTIFICATIONS_PREFIX. 'admins_mirror',
				[
					'draft_notification' => $values['notifications_tab/draft_notification'],
					'published_notification' => $values['notifications_tab/published_notification'],
					'menu_node_notification' => $values['notifications_tab/menu_node_notification'],
					'content_request_notification' => $values['notifications_tab/content_request_notification']],
				$box['key']['id']);
		}
	}

}
