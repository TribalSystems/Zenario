<?php
/*
 * Copyright (c) 2017, Tribal Limited
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


class zenario_common_features__organizer__administrators extends module_base_class {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__users/panels/administrators') return;
		
		if (!$refinerName && !in($mode, 'get_item_name', 'get_item_links')) {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_no_refiner'];
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__users/panels/administrators') return;
		
		foreach ($panel['items'] as $id => &$item) {
			
			$item['traits'] = array();
			$item['has_permissions'] = checkRowExists('action_admin_link', array('admin_id' => $id));
			
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
				$item['list_image'] = 'zenario/file.php?ogl=1'. $img;
			}
			
		}
		
		if ($refinerName == 'trashed') {
			$panel['trash'] = false;
			$panel['title'] = adminPhrase('Trashed Administrators');
			$panel['no_items_message'] = adminPhrase('No Administrators have been Trashed.');
		
		} else {
			$panel['trash']['empty'] = !checkRowExists('admins', array('status' => 'deleted'));
		}
		
		if (!zenario_common_features::canCreateAdditionalAdmins()) {
			$tooltip = adminPhrase('The maximum number of client administrators has been reached ([[i]])', array('i' => siteDescription('max_local_administrators')));
			$panel['collection_buttons']['create']['disabled'] = 
			$panel['item_buttons']['restore_admin']['disabled'] = true;
			$panel['collection_buttons']['create']['disabled_tooltip'] = 
			$panel['item_buttons']['restore_admin']['disabled_tooltip'] = $tooltip;
			$panel['collection_buttons']['create']['css_class'] = '';
		}
		
		if (sqlNumRows(getRows(
			'admins',
			array('id'),
			array('status' => 'active', 'authtype' => 'local')
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
		
		if (($_POST['trash'] ?? false) && checkPriv('_PRIV_DELETE_ADMIN')) {
			foreach (explodeAndTrim($ids) as $id) {
				if ($id != $_SESSION['admin_userid'] ?? false) {
					deleteAdmin($id);
				}
			}
		
		} elseif (($_POST['restore'] ?? false) && checkPriv('_PRIV_DELETE_ADMIN') && zenario_common_features::canCreateAdditionalAdmins()) {
			foreach (explodeAndTrim($ids) as $id) {
				deleteAdmin($id, true);
			}
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}