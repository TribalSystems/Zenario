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

if (is_numeric($sl)) {
	$sl = \ze\row::get('slide_layouts', ['id', 'privacy', 'at_location'], $sl);
}

$slId = $sl['id'] ?? $sl['slide_layout_id'];


switch ($sl['privacy']) {
	case 'public':
		return $this->phrase('Catch-all, show to anyone if no other layout is shown');
	
	case 'logged_in':
		return $this->phrase('Catch-all, show to any extranet user if no other layout is shown');
	
	case 'group_members':
		$groupIds = ze\row::getValues('group_link', 'link_to_id', ['link_from' => 'slide_layout', 'link_from_id' => $slId, 'link_to' => 'group']);
		$groups = [];
		
		foreach ($groupIds as $groupId) {
			$groups[] = ze\user::getGroupLabel($groupId);
		}
		
		return $this->phrase('Private, restrict by group:'). ' '. implode(', ', $groups);
	
	case 'with_role':
		$roleIds = ze\row::getValues('group_link', 'link_to_id', ['link_from' => 'slide_layout', 'link_from_id' => $slId, 'link_to' => 'role']);
		
		if ($ZENARIO_ORGANIZATION_MANAGER_PREFIX = ze\module::prefix('zenario_organization_manager')) {
			$roles = ze\row::getValues($ZENARIO_ORGANIZATION_MANAGER_PREFIX. 'user_location_roles', 'name', ['id' => $roleIds]);
		} else {
			$roles = [];
		}
		
		return $this->phrase('Private, restrict by role:'). ' '. implode(', ', $roles);
}