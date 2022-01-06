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




$chain = ze\row::get('translation_chains', ['equiv_id', 'type', 'privacy', 'at_location'], ['equiv_id' => ze::$equivId, 'type' => ze::$cType]);


if (isset($adminToolbar['sections']['icons']['buttons']['item_permissions'])) {
	$adminToolbar['sections']['icons']['buttons']['item_permissions']['css_class'] .=  ' privacy_'. $chain['privacy'];
	$adminToolbar['sections']['icons']['buttons']['item_permissions']['tooltip'] =
		ze\admin::phrase('Permissions: [[privacyDesc]]', ['privacyDesc' => ze\contentAdm::privacyDesc($chain)]);
}


if (isset($adminToolbar['sections']['history']['buttons']['zenario_users__access_log'])) {

	if (ze::setting('period_to_delete_the_user_content_access_log') != 0 && !ze::in($chain['privacy'], 'public', 'logged_out')) {
		$adminToolbar['sections']['history']['buttons']['zenario_users__access_log']['organizer_quick']['path'] =
			'zenario__content/panels/content/user_access_log//'. $cType. '_'. $cID. '//';

	} else {
		unset($adminToolbar['sections']['history']['buttons']['zenario_users__access_log']);
	}
}

// Show the name of the logged in Extranet User
// (N.b. the options to log in/edit/log out are currently commented out.)
if (ze\module::isRunning('zenario_extranet')) {
	if ($userId = ze\user::id()) {
		$userLabel = ze\admin::phrase('Edit [[identifier]]', ['identifier' => ze\user::identifier($userId)]);
		
		$groups = ze\user::groups(ze\user::id());
		foreach ($groups as $groupId => &$groupCodename) {
			$groupCodename = ze\user::getGroupLabel($groupId);
		}
		
		$userLabelNotes = [];
		
		if (!empty($groups)) {
			$userLabelNotes[] = ze\admin::phrase('Groups: [[groups]]', ['groups' => implode(', ', $groups)]);
		} else {
			$userLabelNotes[] = ze\admin::phrase('Groups: None');
		}
		
		if ($ZENARIO_ORGANIZATION_MANAGER_PREFIX = ze\module::prefix('zenario_organization_manager', true)) {
			$sql = "
				SELECT DISTINCT url.name
				FROM ". DB_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link AS urll
				INNER JOIN ". DB_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_location_roles AS url
				ON urll.role_id = url.id
				WHERE user_id = ". (int) $userId. "
				ORDER BY 1";
			$roles = ze\sql::fetchValues($sql);
			
			if (!empty($roles)) {
				$userLabelNotes[] = ze\admin::phrase('Roles: [[roles]]', ['roles' => implode(', ', $roles)]);
			} else {
				$userLabelNotes[] = ze\admin::phrase('Roles: None');
			}
		}
		
		if (!empty($userLabelNotes)) {
			$userLabel .= ' ('. implode('; ', $userLabelNotes). ')';
		}

		if (isset($adminToolbar['sections']['extranet_user']['buttons']['edit_user'])) {
			$adminToolbar['sections']['extranet_user']['buttons']['edit_user']['admin_box']['key']['id'] = $userId;
			$adminToolbar['sections']['extranet_user']['buttons']['edit_user']['label'] = $userLabel;
		}
		
		$adminToolbar['sections']['extranet_user']['buttons']['logged_in']['label'] =
			ze\admin::phrase('User [[identifier]]', ['identifier' => ze\user::identifier($userId)]);

		unset($adminToolbar['sections']['extranet_user']['buttons']['logged_out']);
		unset($adminToolbar['sections']['extranet_user']['buttons']['impersonate_previous']);
		unset($adminToolbar['sections']['extranet_user']['buttons']['log_in']);

		$adminToolbar['sections']['extranet_user']['buttons']['impersonate']['parent'] = 'logged_in';
		$adminToolbar['sections']['extranet_user']['buttons']['impersonate']['label'] = ze\admin::phrase('Other user...');

	} else {
		$lCID = $lCType = false;
		if (ze\content::langSpecialPage('zenario_login', $lCID, $lCType, ze::$langId)
		&& ($lURL = ze\link::toItem($lCID, $lCType))) {
		
			$adminToolbar['sections']['extranet_user']['buttons']['log_in']['onclick'] = 'zenario.goToURL("'. ze\escape::js($lURL). '");';
		} else {
			unset($adminToolbar['sections']['extranet_user']['buttons']['log_in']);
		}
		
		if (isset($adminToolbar['sections']['extranet_user']['buttons']['impersonate_previous'])) {
			if ((!empty($_COOKIE['COOKIE_LAST_EXTRANET_EMAIL'])
			&& ($user = ze\row::get('users', ['id', 'identifier'], ['status' => 'active', 'email' => $_COOKIE['COOKIE_LAST_EXTRANET_EMAIL']])))
			|| (!empty($_COOKIE['COOKIE_LAST_EXTRANET_SCREEN_NAME'])
			&& ($user = ze\row::get('users', ['id', 'identifier'], ['status' => 'active', 'screen_name' => $_COOKIE['COOKIE_LAST_EXTRANET_SCREEN_NAME']])))) {
			
				$adminToolbar['sections']['extranet_user']['buttons']['impersonate_previous']['admin_box']['key']['id'] = $user['id'];
				$adminToolbar['sections']['extranet_user']['buttons']['impersonate_previous']['label'] = ze\admin::phrase('Login as [[identifier]]', $user);
				
				$adminToolbar['sections']['extranet_user']['buttons']['impersonate']['label'] = ze\admin::phrase('Other user...');
			} else {
				unset($adminToolbar['sections']['extranet_user']['buttons']['impersonate_previous']);
			}
		}

		unset($adminToolbar['sections']['extranet_user']['buttons']['logged_in']);
		unset($adminToolbar['sections']['extranet_user']['buttons']['edit_user']);
		unset($adminToolbar['sections']['extranet_user']['buttons']['view_user']);
		unset($adminToolbar['sections']['extranet_user']['buttons']['logout']);
	}
} else {
	unset($adminToolbar['sections']['extranet_user']['buttons']['logged_out']);
	unset($adminToolbar['sections']['extranet_user']['buttons']['impersonate_previous']);
	unset($adminToolbar['sections']['extranet_user']['buttons']['log_in']);
	unset($adminToolbar['sections']['extranet_user']['buttons']['logged_in']);
	unset($adminToolbar['sections']['extranet_user']['buttons']['edit_user']);
	unset($adminToolbar['sections']['extranet_user']['buttons']['view_user']);
	unset($adminToolbar['sections']['extranet_user']['buttons']['logout']);
}