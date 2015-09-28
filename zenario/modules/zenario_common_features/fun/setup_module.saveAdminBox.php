<?php
/*
 * Copyright (c) 2015, Tribal Limited
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


exitIfNotCheckPriv('_PRIV_RUN_MODULE');

if (!$module = getModuleDetails($box['key']['id'])) {
	exit;
}


if ($module['status'] != 'module_running') {
	if ($error = runModule($box['key']['id'], $test = false)) {
		echo $error;
		exit;
	}
}

//Update the special pages, creating new ones if needed
addNeededSpecialPages();

if ($module['status'] == 'module_not_initialized') {
	//Check to see if the Module just made any special pages
	$sql = "
		SELECT c.id, c.type, c.alias, c.language_id
		FROM ". DB_NAME_PREFIX. "special_pages AS sp
		INNER JOIN ". DB_NAME_PREFIX. "content_items AS c
		   ON c.equiv_id = sp.equiv_id
		  AND c.type = sp.content_type
		WHERE sp.module_class_name = '". sqlEscape($module['class_name']). "'
		ORDER BY c.type, c.equiv_id, c.id";
	
	$contentItems = array();
	$result = sqlSelect($sql);
	while ($row = sqlFetchAssoc($result)) {
		$contentItems[] = $row;
	}
	
	if (!empty($contentItems)) {
		if (count($contentItems) < 2) {
			$toastMessage =
				adminPhrase('&quot;[[tag]]&quot; was created by the [[name]] Module. You should review and publish this content item.',
					array(
						'name' => htmlspecialchars($module['display_name']),
						'tag' => htmlspecialchars(formatTag($contentItems[0]['id'], $contentItems[0]['type'], $contentItems[0]['alias'], $contentItems[0]['language_id']))));
		
		} else {
			$toastMessage =
				adminPhrase('The following content items were created by the [[name]] Module, you should review and publish them:',
					array('name' => htmlspecialchars($module['display_name']))).
				'<ul>';
			
			foreach ($contentItems as $contentItem) {
				$toastMessage .= '<li>'. htmlspecialchars(formatTag($contentItem['id'], $contentItem['type'], $contentItem['alias'], $contentItem['language_id'])). '</li>';
			}
			
			$toastMessage .= '</ul>';
		}
		
		$box['toast'] = array(
			'message' => $toastMessage,
			'options' => array('timeOut' => 0, 'extendedTimeOut' => 0));
	}
	
	
	
	if (!engToBoolean($box['tabs']['confirm']['fields']['grant_perms']['hidden'])
	 && (($values['confirm/grant_perms'] == 'myself' && !session('admin_global_id')) || $values['confirm/grant_perms'] == 'site_admins')
	 && ($perms = scanModulePermissionsInTUIXDescription($module['class_name']))) {
		
		if ($values['confirm/grant_perms'] == 'myself') {
			saveAdminPerms($perms, adminId());
		} else {
			$result = getRows('admins', 'id', array('authtype' => 'local', 'status' => 'active'));
			while ($admin = sqlFetchAssoc($result)) {
				saveAdminPerms($perms, $admin['id']);
			}
		}
		
		if (!session('admin_global_id')) {
			setAdminSession(adminId());
		}
	}
}

//Modules that change Storekeeper will require a Storekeeper reload.
if (moduleDir($module['class_name'], 'tuix/organizer', true)) {
	$box['popout_message'] = '<!--Reload_Storekeeper-->';
}

return false;