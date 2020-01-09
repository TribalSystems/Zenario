<?php
/*
 * Copyright (c) 2020, Tribal Limited
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


class zenario_common_features__organizer__administrators extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__users/panels/administrators') return;
		
		if (!$refinerName && !ze::in($mode, 'get_item_name', 'get_item_links')) {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_no_refiner'];
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__users/panels/administrators') return;
		
		foreach ($panel['items'] as $id => &$item) {
			
			$item['traits'] = [];
			$item['has_permissions'] = ze\row::exists('action_admin_link', ['admin_id' => $id]);
			
			if ($id == ($_SESSION['admin_userid'] ?? false)) {
				$item['traits']['current_admin'] = true;
			}
			
			if ($item['authtype'] == 'super') {
				$item['traits']['super'] = true;
			} else {
				$item['traits']['local'] = true;
			}
			
			if ($item['status'] == 'active') {
				$item['traits']['active'] = true;
			} else {
				$item['traits']['trashed'] = true;
			}
			
			if (!empty($item['checksum'])) {
				$item['traits']['has_image'] = true;
				$img = '&usage=admin&c='. $item['checksum'];
	
				$item['image'] = 'zenario/file.php?og=1'. $img;
			}
			
			//Show an inline warning button if this admin is inactive.
			if (ze\admin::isInactive($id)) {
				$item['is_inactive'] = true;
				if ($item['last_login']) {
					$item['inactive_tooltip'] = ze\admin::phrase(
						"This administrator hasn't logged in since [[last_login]], [[days]] days ago.", 
						[
							'last_login' => ze\admin::formatDate($item['last_login'], '_MEDIUM'),
							'days' => (string)floor((strtotime('now') - strtotime($item['last_login'])) / 60 / 60 / 24)
						]
					);
				} else {
					$item['inactive_tooltip'] = ze\admin::phrase(
						"This administrator was created on [[created_date]] and has never logged in.", 
						[
							'created_date' => ze\admin::formatDate($item['created_date'], '_MEDIUM')
						]
					);
				}
			}
			
			$item['last_login'] = ze\admin::formatDateTime($item['last_login'], 'vis_date_format_med', $useDefaultLang = true);
				
			//Check if an admin has ever logged in.
			if ($sessionId = $item['session_id']) {
				
				if(file_exists(session_save_path(). "/sess_" . $sessionId)) {
					clearstatcache(true, session_save_path(). "/sess_" . $sessionId);
					$sessionInfo = stat(session_save_path(). "/sess_" . $sessionId);
				
					//Check how long ago the admin was active.
					$lastActivityTimestamp = $sessionInfo['mtime'];
		
					//If the admin was active less than 10 mins ago, show "Logged in now" instead of a date.
					$inactivityDuration = (time() - $lastActivityTimestamp);
				
					if($inactivityDuration < 600) {
						$item['last_login'] = 'Logged in now';
					}
				}
			}
			
			unset($item['session_id']);
		}
		
		if ($refinerName == 'trashed') {
			$panel['trash'] = false;
			$panel['title'] = ze\admin::phrase('Trashed Administrators');
			$panel['no_items_message'] = ze\admin::phrase('No Administrators have been Trashed.');
		
		} else {
			$panel['trash']['empty'] = !ze\row::exists('admins', ['status' => 'deleted']);
		}
		
		if (!zenario_common_features::canCreateAdditionalAdmins()) {
			$tooltip = ze\admin::phrase('The maximum number of client administrators has been reached ([[i]])', ['i' => ze\site::description('max_local_administrators')]);
			$panel['collection_buttons']['create']['disabled'] = 
			$panel['item_buttons']['restore_admin']['disabled'] = true;
			$panel['collection_buttons']['create']['disabled_tooltip'] = 
			$panel['item_buttons']['restore_admin']['disabled_tooltip'] = $tooltip;
			$panel['collection_buttons']['create']['css_class'] = '';
		}
		
		if (ze\sql::numRows(ze\row::query(
			'admins',
			['id'],
			['status' => 'active', 'authtype' => 'local']
		)) < 2) {
			if (isset($panel['collection_buttons']['copy_perms'])) {
				$panel['collection_buttons']['copy_perms']['disabled'] = true;
			}
			if (isset($panel['item_buttons']['copy_perms_to'])) {
				$panel['item_buttons']['copy_perms_to']['disabled'] = true;
			}
			if (isset($panel['item_buttons']['copy_perms_from'])) {
				$panel['item_buttons']['copy_perms_from']['disabled'] = true;
			}
		}
		
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__users/panels/administrators') return;
		
		if (($_POST['trash'] ?? false) && ze\priv::check('_PRIV_DELETE_ADMIN')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				if ($id != $_SESSION['admin_userid'] ?? false) {
					ze\adminAdm::delete($id);
				}
			}
		
		} elseif (($_POST['restore'] ?? false) && ze\priv::check('_PRIV_DELETE_ADMIN') && zenario_common_features::canCreateAdditionalAdmins()) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				ze\adminAdm::delete($id, true);
			}
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}