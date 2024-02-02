<?php
/*
 * Copyright (c) 2024, Tribal Limited
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


class zenario_common_features__admin_boxes__admin_copy_perms extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if ($box['key']['id_is_from']) {
			$values['copy/copy_from'] = $box['key']['id'];
		} elseif ($box['key']['id_is_to']) {
			$values['copy/copy_to'] = $box['key']['id'];
		}
		
		$fields['copy/copy_from']['values'] =
		$fields['copy/copy_to']['values'] = [];
		
		foreach (ze\row::getAssocs(
			'admins',
			['first_name', 'last_name', 'username', 'authtype'],
			['status' => 'active', 'authtype' => 'local']
		) as $adminId => $admin) {
			
			$fields['copy/copy_from']['values'][$adminId] = [
				'label' => ze\admin::formatName($admin)];
			
			$fields['copy/copy_to']['values'][$adminId] = [
				'label' => ze\admin::formatName($admin),
				'visible_if' => "id != zenario.adminId && id != zenarioAB.value('copy_from')"];
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$adminsTo = ze\ray::explodeAndTrim($values['copy/copy_to'], true);
		$count = count($adminsTo);
		
		if ($count == 1) {
			$box['confirm']['message'] = ze\admin::phrase("Are you sure you wish to overwrite the permissions of [[name]]?", ['name' => ze\admin::formatName($adminsTo[0])]);
		} else {
			$box['confirm']['message'] = ze\admin::phrase("Are you sure you wish to overwrite the permissions of the [[count]] selected administrators?", ['count' => $count]);
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		ze\priv::exitIfNot('_PRIV_EDIT_ADMIN');
		
		if ($adminFrom = ze\row::get(
			'admins',
			['permissions', 'specific_content_items', 'specific_content_types'],
			['status' => 'active', 'authtype' => 'local', 'id' => $values['copy/copy_from']]
		)) {
			
			$perms = [];
			if ($adminFrom['permissions'] == 'specific_actions') {
				$perms = ze\admin::loadPerms($values['copy/copy_from']);
			}
			
			foreach (ze\ray::explodeAndTrim($values['copy/copy_to'], true) as $adminIdTo) {
				if ($adminIdTo != ze\admin::id()
				 && ze\row::exists('admins',['status' => 'active', 'authtype' => 'local', 'id' => $adminIdTo])) {
					ze\adminAdm::savePerms($adminIdTo, $adminFrom['permissions'], $perms, $adminFrom);
				}
			}
		}
	}
}
