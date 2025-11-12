<?php
/*
 * Copyright (c) 2025, Tribal Limited
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
		if ($path != 'zenario__administration/panels/administrators') return;
		
		if (!$refinerName && !ze::in($mode, 'get_item_name', 'get_item_links')) {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_no_refiner'];
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__administration/panels/administrators') return;
		
		$currentlyLoggedInAdmin = ze\admin::id();
		
		
		//Get a list of which admin permissions are core/advanced/module permissions.
		//Note: I don't want to hard-code this so I'll load and read them from the YAML
		//file.
		$corePrivs = [];
		$advancedPrivs = [];
		$tags = ze\tuix::readFile(CMS_ROOT. 'zenario/modules/zenario_common_features/tuix/admin_boxes/admin_perms.yaml');
		foreach ($tags['zenario_admin']['tabs']['permissions']['fields'] as $fieldCodeName => $field) {
			
			$fieldType = $field['type'] ?? null;
			
			if ($fieldType == 'checkboxes' && !empty($field['values'])) {
				if ($fieldCodeName == 'perm_advanced_permissions') {
					$advancedPrivs = array_keys($field['values']);
				} else {
					$corePrivs = array_merge($corePrivs, array_keys($field['values']));
				}
			}
		}
		
		//Use these lists to prepare some SQL statements that will give us a count of
		//an admin's permissions
		$sql = "
			SELECT COUNT(action_name)
			FROM ". DB_PREFIX. "action_admin_link
			WHERE admin_id = ?
			  AND action_name IN (". ze\escape::in($corePrivs, 'sql'). ")";
		$corePrivsStatement = \ze\sql::prepare($sql, 'i');
		
		$sql = "
			SELECT COUNT(action_name)
			FROM ". DB_PREFIX. "action_admin_link
			WHERE admin_id = ?
			  AND action_name IN (". ze\escape::in($advancedPrivs, 'sql'). ")";
		$advancedPrivsStatement = \ze\sql::prepare($sql, 'i');
		
		$sql = "
			SELECT COUNT(action_name)
			FROM ". DB_PREFIX. "action_admin_link
			WHERE admin_id = ?
			  AND action_name NOT IN (". ze\escape::in(array_merge($corePrivs, $advancedPrivs), 'sql'). ")
			  AND action_name NOT LIKE 'perm_%'";
		$modulePrivsStatement = \ze\sql::prepare($sql, 'i');
		
		foreach ($panel['items'] as $id => &$item) {
			
			$item['has_permissions'] = ze\row::exists('action_admin_link', ['admin_id' => $id]);
			
			if ($id == ($_SESSION['admin_userid'] ?? false)) {
				$item['isCurrentAdmin'] = true;
			}
			
			if ($item['authtype'] == 'super') {
				$item['isMultisite'] = true;
			} else {
				$item['isLocal'] = true;
			}
			
			if ($item['status'] == 'active') {
				$item['isActive'] = true;
			} else {
				$item['isTrashed'] = true;
			}
			
			if ($item['is_client_account']) {
				$item['is_client_account'] = ze\admin::phrase('Client');
			} else {
				unset($item['is_client_account']);
			}
			
			if (!empty($item['checksum'])) {
				$item['hasImage'] = true;
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
			
			if (!empty($item['failed_login_count_since_last_successful_login']) && $item['failed_login_count_since_last_successful_login'] >= 1) {
				$item['admin_had_3_or_more_failed_login_attempts_since_last_successful_login'] = true;
				
				$item['3_or_more_failed_login_attempts_since_last_successful_login_tooltip'] = ze\admin::nPhrase(
					"This administrator has had [[failed_login_count]] failed login attempt since the last successful login.",
					"This administrator has had [[failed_login_count]] failed login attempts since the last successful login.",
					$item['failed_login_count_since_last_successful_login'],
					['failed_login_count' => $item['failed_login_count_since_last_successful_login']]
				);
			}
			
			$originalLastLogin = $item['last_login'];
			$loginStatus = ze\admin::getFormattedLoginStatus($id, $item);
			$item['last_activity_time'] = $loginStatus['last_activity_time'];
			if ($loginStatus['pending_2fa']) {
				$item['pending_2fa'] = ze\admin::phrase('Pending 2FA');
			}
			
			//For Organizer, use relative time formatting for non-current sessions
			if ($loginStatus['last_login'] === ze\admin::phrase('Never logged in') || 
			    $loginStatus['last_login'] === ze\admin::phrase('Logged in now') || 
			    $loginStatus['last_login'] === ze\admin::phrase('Logged in now (pending 2FA)')) {
				$item['last_login'] = $loginStatus['last_login'];
			} else {
				$item['last_login'] = ze\admin::formatRelativeDateTime($originalLastLogin, "day", true, 'vis_date_format_med', $useDefaultLang = true);
			}
			
			unset($item['session_id']);
			
			//Permissions count
			$adminPermissions = ze\row::get('admins', ['permissions', 'specific_content_items', 'specific_content_types'], ['id' => $item['id']]);
			switch ($adminPermissions['permissions']) {
				case 'all_permissions':
					$item['permissions'] = ze\admin::phrase('All permissions');
					break;
				case 'specific_actions':
					
					$corePrivCount = $corePrivsStatement->fetchValue([$id]);
					$modulePrivCount = $modulePrivsStatement->fetchValue([$id]);
					$advancedPrivCount = $advancedPrivsStatement->fetchValue([$id]);
					
					if (!$corePrivCount
					 && !$modulePrivCount
					 && !$advancedPrivCount) {
						$item['permissions'] = ze\admin::phrase('No permissions');
					} else {
						$item['permissions'] = ze\admin::phrase('Specific actions ([[corePrivCount]] core, [[modulePrivCount]] module-based, [[advancedPrivCount]] advanced)', [
							'corePrivCount' => $corePrivCount,
							'modulePrivCount' => $modulePrivCount,
							'advancedPrivCount' => $advancedPrivCount
						]);
					}
					break;
				case 'specific_areas':
					$contentItemsCount = $contentTypesCount = 0;
					$contentTypesFormattedNicely = [];
					//Check specific content items first, then specific content types.
					if (!empty($adminPermissions['specific_content_items'])) {
						$contentItems = explode(',', $adminPermissions['specific_content_items']);
						$contentItemsCount = count($contentItems );
					}
					
					if (!empty($adminPermissions['specific_content_types'])) {
						$contentTypes = explode(',', $adminPermissions['specific_content_types']);
						foreach ($contentTypes as $contentType) {
							$contentTypesFormattedNicely[] = ze\content::getContentTypeName($contentType, $plural = true);
							$contentTypesCount++;
						}
					}
					
					if ($contentItemsCount > 0 && $contentTypesCount > 0) {
						$item['permissions'] = ze\admin::phrase(
							'Specific content items ([[content_item_count]]) and content types ([[content_types]])',
							['content_item_count' => $contentItemsCount, 'content_types' => implode(', ', $contentTypesFormattedNicely)]
						);
					} else {
						if ($contentItemsCount > 0) {
							$item['permissions'] = ze\admin::phrase(
								'Specific content items ([[content_item_count]])',
								['content_item_count' => $contentItemsCount]
							);
						} elseif ($contentTypesCount > 0) {
							$item['permissions'] = ze\admin::phrase(
								'Specific content types ([[content_types]])',
								['content_types' => implode(', ', $contentTypesFormattedNicely)]
							);
						}
					}
					break;
			}
			
			if ($currentlyLoggedInAdmin == $id) {
				$item['currently_logged_in_admin'] = true;
			}
			
			$item['full_name_for_pickers'] = self::formatNameIncludeEmailAddress($item['id']);
		}
		
		if ($refinerName == 'trashed') {
			$panel['trash'] = false;
			$panel['title'] = ze\admin::phrase('Trashed administrator accounts');
			$panel['no_items_message'] = ze\admin::phrase('There are no trashed administrator accounts.');
		
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
		
		$localAdminCountQuery = ze\row::query('admins', ['id'], ['status' => 'active', 'authtype' => 'local']);
		$localAdminCount = ze\sql::numRows($localAdminCountQuery);
		if ($localAdminCount < 2) {
			if (isset($panel['item_buttons']['copy_perms'])) {
				$panel['item_buttons']['copy_perms']['disabled'] = true;
			}
			
			//These two buttons are not used anywhere in Zenario core.
			//Are these for a client custom module?...
			if (isset($panel['item_buttons']['copy_perms_to'])) {
				$panel['item_buttons']['copy_perms_to']['disabled'] = true;
			}
			if (isset($panel['item_buttons']['copy_perms_from'])) {
				$panel['item_buttons']['copy_perms_from']['disabled'] = true;
			}
		} else {
			if (isset($panel['item_buttons']['copy_perms'])) {
				$panel['item_buttons']['copy_perms']['disabled_tooltip'] = ze\admin::phrase("You cannot copy another admin's permissions to yourself.");
			}
		}
		
		//Display information about the maximum local admins number. 0 means unlimited.
		$maxLocalAdmins = ze\site::description('max_local_administrators');
		if ($maxLocalAdmins) {
			$panel['notice']['show'] = true;
			
			$panel['notice']['message'] = ze\admin::nPhrase(
				'This site has 1 client administrator out of a maximum of [[max_local_administrators]] allowed. Please contact support if you need more accounts.',
				'This site has [[current_local_admin_count]] client administrators out of a maximum of [[max_local_administrators]] allowed. Please contact support if you need more accounts.',
				$localAdminCount,
				['current_local_admin_count' => $localAdminCount, 'max_local_administrators' => $maxLocalAdmins]
			);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__administration/panels/administrators') return;
		
		if (!empty($_POST['restore']) && ze\priv::check('_PRIV_DELETE_ADMIN') && zenario_common_features::canCreateAdditionalAdmins()) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				ze\adminAdm::delete($id, true);
			}
		
		} else
		if (!empty($_POST['delete']) && ze\priv::check('_PRIV_DELETE_ADMIN')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				ze\adminAdm::reallyDelete($id, $onlyDeleteAdminsThatHaveNeverLoggedIn = true, $deleteAllButThisAdmin = false);
			}
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
	
	private function formatNameIncludeEmailAddress($adminDetails = false) {
	
		if (!$adminDetails) {
			$adminDetails = $_SESSION['admin_userid'] ?? false;
		}
	
		if (!is_array($adminDetails)) {
			$adminDetails = \ze\row::get('admins', ['first_name', 'last_name', 'username', 'email', 'authtype'], $adminDetails);
		}
	
		if (!empty($adminDetails)) {
			if ($adminDetails['authtype'] == 'super') {
				return $adminDetails['first_name']. ' '. $adminDetails['last_name']. ' ('. $adminDetails['username']. ', multi-site admin, ' . $adminDetails['email'] . ')';
			} else {
				return $adminDetails['first_name']. ' '. $adminDetails['last_name']. ' ('. $adminDetails['username']. ', local admin, ' . $adminDetails['email'] . ')';
			}
		} else {
			return '';
		}
	}
}
