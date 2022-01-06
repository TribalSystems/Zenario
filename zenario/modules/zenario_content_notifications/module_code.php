<?php
/*
 * Copyright (c) 2022, Tribal Limited
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


class zenario_content_notifications extends ze\moduleBaseClass {
	
	public static function getNotes($cID, $cType, $cVersion) {
		return ze\row::getAssocs(
			ZENARIO_CONTENT_NOTIFICATIONS_PREFIX. 'versions_mirror', true, 
			['content_id' => $cID, 'content_type' => $cType, 'content_version' => $cVersion],
			'datetime_created'
		);
	}
	
	public static function getNoteMergeFields($adminId, $datetime, $cID, $cType, $cVersion, $title = false) {
		$mrg = [];
		
		if ($title) {
			switch ($title) {
				case 'request':
					$mrg['title'] = ze\admin::phrase('Request:');
					break;
				case 'note':
					$mrg['title'] = ze\admin::phrase('Note:');
					break;
				default:
					$mrg['title'] = ze\admin::phrase($title);
					break;
			}
		}
		
		$mrg['by'] = ze\admin::formatName($adminId);
		$mrg['on'] = ze\admin::formatDateTime($datetime);
		$mrg['content'] = ze\content::formatTag($cID, $cType);
		$mrg['version'] = $cVersion;
		
		return $mrg;
	}
	
	public static function getNoteHeader($adminId, $datetime, $cID, $cType, $cVersion, $title = false, $link = false) {
		$header = '';
		
		$mrg = self::getNoteMergeFields($adminId, $datetime, $cID, $cType, $cVersion, $title);
		
		if ($title) {
			$header .= ze\admin::phrase('<b><u>[[title]]</u></b>', $mrg) . '<br>';
		}
		
		$header .= ze\admin::phrase('<b>By:</b> [[by]]', $mrg) . '<br>';
		$header .= ze\admin::phrase('<b>On:</b> [[on]]', $mrg) . '<br>';
		$header .= ze\admin::phrase('<b>For:</b> [[content]] (Version [[version]])', $mrg);
		
		if ($link) {
			$header .= '<br>' . ze\link::toItem($cID, $cType, $fullPath = true);;
		}
		
		return $header;
	}
	
	protected static function getListOfAdminsWhoReceiveNotifications($adminId = false) {
		$sql = "
			SELECT DISTINCT a.id, a.email
			FROM ". DB_PREFIX. "admins AS a
			INNER JOIN ". DB_PREFIX. ZENARIO_CONTENT_NOTIFICATIONS_PREFIX. "admins_mirror AS am
			   ON am.admin_id = a.id
			  AND am.content_request_notification = 1
			INNER JOIN " . DB_PREFIX . "action_admin_link as aal 
			   ON aal.admin_id = a.id
			  AND aal.action_name IN ('_ALL', '_PRIV_APPEAR_ON_CONTENT_REQUEST_RECIPIENT_LIST')
			WHERE a.status = 'active'";
		
		if ($adminId) {
			$sql .= "
			  AND a.id = ". (int) $adminId;
		}

		$result = ze\sql::select($sql);
		
		if ($adminId) {
			return (bool) ze\sql::fetchRow($result);
		
		} else {
			$admins_list = [];
			while ($row = ze\sql::fetchAssoc($result)) {
				$admins_list[$row['id']] = $row;
			}
			return $admins_list;
		}
	}

	protected static function getAdminNotifications($adminId) {
		return ze\row::get(
			ZENARIO_CONTENT_NOTIFICATIONS_PREFIX . 'admins_mirror', 
			['content_request_notification', 'draft_notification', 'published_notification', 'menu_node_notification'], 
			$adminId
		);
	}
	

	public function fillAdminToolbar(&$adminToolbar, $cID, $cType, $cVersion) {

		if (ze\content::isDraft(ze::$status)
		 && ($notes = self::getNotes($cID, $cType, $cVersion))
		 && (!empty($notes))) {
			$adminToolbar['sections']['icons']['buttons']['no_request']['hidden'] = true;
		} else {
			$adminToolbar['sections']['icons']['buttons']['request']['hidden'] = true;
		}
		
		//Replace the status button with the Request to Publish button if someone can't publish a draft.
		//Also, if this item could be published, but this admin doesn't have the rights to publish it,
		//show the Request to Publish button where the Publish button usually is.
		if (ze\content::isDraft(ze::$status) && !ze\priv::check('_PRIV_PUBLISH_CONTENT_ITEM', $cID, $cType)) {
			unset($adminToolbar['sections']['status_button']['buttons']['status_button']);
		
		} elseif (!ze\content::isDraft(ze::$status) || ze\priv::check('_PRIV_PUBLISH_CONTENT_ITEM', $cID, $cType)) {
			unset($adminToolbar['sections']['status_button']['buttons']['request_publish']);
			unset($adminToolbar['sections']['edit']['buttons']['request_publish']);
			unset($adminToolbar['sections']['restricted_editing']['buttons']['request_publish']);
		}
		
		//If this item could be trashed, but this admin doesn't have the rights to trashed it,
		//show the Request to Rrash button where the Rrash button usually is.
		if (!ze\contentAdm::allowTrash($cID, $cType, ze::$status) || ze\priv::check('_PRIV_HIDE_CONTENT_ITEM', $cID, $cType)) {
			unset($adminToolbar['sections']['edit']['buttons']['request_trash']);
			unset($adminToolbar['sections']['restricted_editing']['buttons']['request_trash']);
		}
	}
	

	
	
	

	

	
	
	
	
	//Delete requests for any versions that are deleted from the system
	public static function eventContentDeleted($cID, $cType, $cVersion) {
		ze\row::delete(
			ZENARIO_CONTENT_NOTIFICATIONS_PREFIX. 'versions_mirror',
			['content_id' => $cID, 'content_type' => $cType, 'content_version' => $cVersion]
		);
	}
	
	//Notification sending
	
	public static function eventContentPublished($cID, $cType, $cVersion) {
		self::sendEmailNotification('published_notification', 'content_item', 'published', self::getContentFieldsForEmail($cID, $cType, $cVersion));
	}

	public static function eventDraftCreated($cIDTo, $cIDFrom, $cTypeTo, $cVersionTo, $cVersionFrom, $cTypeFrom) {
		self::sendEmailNotification('draft_notification', 'content_item', 'drafted', self::getContentFieldsForEmail($cIDTo, $cTypeTo, $cVersionTo));
	}
	
	public static function eventMenuNodeTextAdded($menuId, $languageId, $newText) {
		self::sendEmailNotification('menu_node_notification', 'menu_node', 'created', self::getMenuFieldsForEmail($menuId, $languageId, $newText));
	}
	
	public static function eventMenuNodeTextUpdated($menuId, $languageId, $newText, $oldText) {
		self::sendEmailNotification('menu_node_notification', 'menu_node', 'updated', self::getMenuFieldsForEmail($menuId, $languageId, $newText, $oldText));
	}
	
	
	protected static function sendEmailNotification($notificationType, $thing, $action, $mergeFields) {
		$mergeFields['action'] = '[[item]] [[action]]';
		$replace = [];
		switch ($thing) {
			case 'content_item':
				$replace['item'] = 'Item';
				switch ($action) {
					case 'published':
						$replace['action'] = 'published';
						break;
					case 'drafted':
						$replace['action'] = 'drafted';
						break;
				}
				break;
			case 'menu_node':
				$replace['item'] = 'Menu node';
				switch ($action) {
					case 'created':
						$replace['action'] = 'created';
						break;
					case 'updated':
						$replace['action'] = 'updated';
						break;
				}
				break;
		}
		ze\lang::applyMergeFields($mergeFields['action'], $replace);
		
		$subject = self::processTemplate('content_notification_email_subject', $mergeFields);
		$body = self::processTemplate('content_notification_email_body', $mergeFields);
		$addressToOverriddenBy = '';
		
		if ($subject && $body) {
			$sql = "
				SELECT DISTINCT a.id, a.email
				FROM ". DB_PREFIX. "admins AS a
				INNER JOIN ". DB_PREFIX. ZENARIO_CONTENT_NOTIFICATIONS_PREFIX. "admins_mirror AS am
				   ON am.admin_id = a.id
				  AND am.`". ze\escape::sql($notificationType). "` = 1
				INNER JOIN " . DB_PREFIX . "action_admin_link as aal 
				   ON aal.admin_id = a.id
				  AND aal.action_name IN ('_ALL', '_PRIV_APPEAR_ON_CONTENT_REQUEST_RECIPIENT_LIST')
				WHERE a.status = 'active'
				  AND id != ". (int) ze\admin::id();
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
				ze\server::sendEmail($subject, $body, $row['email'], $addressToOverriddenBy, $nameTo = false, $addressFrom = false, $nameFrom = false, $attachments = [], $attachmentFilenameMappings = [], $precedence = 'bulk', $isHTML = false);
			}
		}
	}
	
	public static function processTemplate($template_key, &$fields) {
		$template = ze::setting($template_key);
		$re = '/\{\{([^}]+)\}\}/';

		$result = preg_replace_callback($re, function ($matches) use (&$fields) {
			$key = $matches[1];
			if (isset($fields[$key])) {
				return $fields[$key];
			}
			return $matches[0];
		}, $template);
		
		return $result;
	}
	
	
	protected static function insertFieldsForEmail(&$record) {
		$record['url'] = $url = ze\link::adminDomain() . SUBDIRECTORY;
		$record['admin_name'] = ze\admin::formatName();
		$record['admin_profile_url'] = 
			ze\link::absolute(). 'organizer.php?#zenario__users/panels/administrators';
		$record['datetime_when'] = ze\admin::formatDateTime(ze\date::now());
	}
	
	protected static function getContentFieldsForEmail($cID, $cType, $cVersion) {
		$record = ze\row::get('content_item_versions', ['tag_id', 'title'], ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
		self::insertFieldsForEmail($record);
		$url = $record['url'];
		$record['hyperlink'] = ze\link::toItem($cID, $cType, $fullPath = true, $request = '&cVersion=' . $cVersion);
		$record['previous_menu_node'] = 'n/a';
		$record['new_menu_node'] = 'n/a';
		return $record;
	}
	
	protected static function getMenuFieldsForEmail($menuId, $languageId, $newText, $oldText = '') {
		$title = $tagId = $link = '';
		if ($equiv = ze\menu::getContentItem($menuId)) {
			if (ze\content::langEquivalentItem($equiv['content_id'], $equiv['content_type'], $languageId)) {
				$title = ze\content::title($equiv['content_id'], $equiv['content_type']);
				$tagId = ze\content::formatTag($equiv['content_id'], $equiv['content_type']);
				$link = ze\link::toItem($equiv['content_id'], $equiv['content_type'], $fullPath = true);
			}
		}
		
		$record = [];
		self::insertFieldsForEmail($record);
		$record['previous_menu_node'] = $oldText;
		$record['new_menu_node'] = $newText;
		$record['title'] = $title;
		$record['tag_id'] = $tagId;
		$record['hyperlink'] = $link;
		return $record;
	}
	
	

}