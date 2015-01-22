<?php
/*
 * Copyright (c) 2014, Tribal Limited
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

$box['popout_message'] = '';

if ($module['status'] == 'module_not_initialized') {
	//Check to see if the Module just made any special pages
	$sp = getRow('special_pages', array('equiv_id', 'content_type'), array('module_class_name' => $module['class_name']));
	
	//If the Module just created some special pages, the prompt should like them
	if (!empty($sp['equiv_id'])) {
		$box['popout_message'] .= '<!--Message_Type:Warning-->';
	
		$equivs = equivalences(false, $sp['content_type'], true, $sp['equiv_id']);
		
		if (empty($equivs) || count($equivs) < 2) {
			$box['popout_message'] .=
				'<p>'. 
					adminPhrase(
						'The Module &quot;[[name]]&quot; has created &quot;[[tag]]&quot; as a Special Page. You should review and Publish this Content Item.',
						array('name' => htmlspecialchars($module['display_name']), 'tag' => htmlspecialchars(formatTag($sp['equiv_id'], $sp['content_type'])))).
				'</p>';
		
		} else {
			$box['popout_message'] .=
				'<p>'.
					adminPhrase(
						'The Module &quot;[[name]]&quot; has created the following Content Items:',
						array('name' => htmlspecialchars($module['display_name']))).
				'</p><ul>';
			
			foreach ($equivs as $equiv) {
				$box['popout_message'] .= '<li>'. htmlspecialchars(formatTag($equiv['id'], $equiv['type'])). '</li>';
			}
			
			$box['popout_message'] .=
				'</ul><p>'.
					adminPhrase('You should review and Publish these Content Items.').
				'</p>';
		}
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
	$box['popout_message'] = '<!--Reload_Storekeeper-->'. html_entity_decode(strip_tags($box['popout_message']));
}

return false;