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




if (isset($adminToolbar['sections']['history']['buttons']['zenario_users__access_log'])) {
	$item = getRow('translation_chains', array('privacy'), array('equiv_id' => cms_core::$equivId, 'type' => cms_core::$cType));

	if (setting('log_user_access') && !in($item['privacy'], 'public', 'logged_out')) {
		$adminToolbar['sections']['history']['buttons']['zenario_users__access_log']['organizer_quick']['path'] =
			'zenario__content/panels/content/user_access_log//'. $cType. '_'. $cID. '//';

	} else {
		unset($adminToolbar['sections']['history']['buttons']['zenario_users__access_log']);
	}
}

// Show the name of the logged in Extranet User
// (N.b. the options to log in/edit/log out are currently commented out.)
if ($userId = userId()) {
	$userLabel = adminPhrase('User [[identifier]]', ['identifier' => getUserIdentifier($userId)]);
	
	$groups = getUserGroups(userId());
	foreach ($groups as $groupId => &$groupCodename) {
		$groupCodename = getGroupLabel($groupId);
	}
	
	$userLabelNotes = [];
	
	if (!empty($groups)) {
		$userLabelNotes[] = adminPhrase('Groups: [[groups]]', ['groups' => implode(', ', $groups)]);
	} else {
		$userLabelNotes[] = adminPhrase('Groups: None');
	}
	
	if ($ZENARIO_ORGANIZATION_MANAGER_PREFIX = getModulePrefix('zenario_organization_manager', true)) {
		$sql = "
			SELECT DISTINCT url.name
			FROM ". DB_NAME_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link AS urll
			INNER JOIN ". DB_NAME_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_location_roles AS url
			   ON urll.role_id = url.id
			WHERE user_id = ". (int) $userId. "
			ORDER BY 1";
		$roles = sqlFetchValues($sql);
		
		if (!empty($roles)) {
			$userLabelNotes[] = adminPhrase('Roles: [[roles]]', ['roles' => implode(', ', $roles)]);
		} else {
			$userLabelNotes[] = adminPhrase('Roles: None');
		}
	}
	
	if (!empty($userLabelNotes)) {
		$userLabel .= ' ('. implode('; ', $userLabelNotes). ')';
	}
	
	$adminToolbar['sections']['extranet_user']['buttons']['logged_in']['label'] = $userLabel;

	
	if (isset($adminToolbar['sections']['extranet_user']['buttons']['edit_user'])) {
		$adminToolbar['sections']['extranet_user']['buttons']['edit_user']['admin_box']['key']['id'] = $userId;
		$adminToolbar['sections']['extranet_user']['buttons']['edit_user']['label'] =
			adminPhrase('Edit [[identifier]]', ['identifier' => getUserIdentifier($userId)]);
	}

	unset($adminToolbar['sections']['extranet_user']['buttons']['logged_out']);
	unset($adminToolbar['sections']['extranet_user']['buttons']['impersonate']);
	unset($adminToolbar['sections']['extranet_user']['buttons']['impersonate_previous']);
	unset($adminToolbar['sections']['extranet_user']['buttons']['log_in']);

} else {
	$lCID = $lCType = false;
	if (langSpecialPage('zenario_login', $lCID, $lCType, cms_core::$langId)
	 && ($lURL = linkToItem($lCID, $lCType))) {
	
		$adminToolbar['sections']['extranet_user']['buttons']['log_in']['onclick'] =
			'zenario.goToURL("'. jsEscape($lURL). '");';
	} else {
		unset($adminToolbar['sections']['extranet_user']['buttons']['log_in']);
	}
	
	if (isset($adminToolbar['sections']['extranet_user']['buttons']['impersonate_previous'])) {
		if ((!empty($_COOKIE['COOKIE_LAST_EXTRANET_EMAIL'])
		  && ($user = getRow('users', ['id', 'identifier'], ['status' => 'active', 'email' => $_COOKIE['COOKIE_LAST_EXTRANET_EMAIL']])))
		 || (!empty($_COOKIE['COOKIE_LAST_EXTRANET_SCREEN_NAME'])
		  && ($user = getRow('users', ['id', 'identifier'], ['status' => 'active', 'screen_name' => $_COOKIE['COOKIE_LAST_EXTRANET_SCREEN_NAME']])))) {
		
			$adminToolbar['sections']['extranet_user']['buttons']['impersonate_previous']['admin_box']['key']['id'] = $user['id'];
			$adminToolbar['sections']['extranet_user']['buttons']['impersonate_previous']['label'] =
				adminPhrase('Login as [[identifier]]', $user);
			
			$adminToolbar['sections']['extranet_user']['buttons']['impersonate']['label'] =
				adminPhrase('Other user...');
		} else {
			unset($adminToolbar['sections']['extranet_user']['buttons']['impersonate_previous']);
		}
	}

	unset($adminToolbar['sections']['extranet_user']['buttons']['logged_in']);
	unset($adminToolbar['sections']['extranet_user']['buttons']['edit_user']);
	unset($adminToolbar['sections']['extranet_user']['buttons']['view_user']);
	unset($adminToolbar['sections']['extranet_user']['buttons']['logout']);
}