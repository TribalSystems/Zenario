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

$adminColumns = [
	'username', 'password', 'password_salt', 'password_needs_changing', 
	'status', 'first_name', 'last_name', 'email', 'created_date', 'modified_date', 'image_id',
	'permissions', 'specific_languages', 'specific_content_types'
];

//Attempt to connect to the global database
if (\ze\db::connectGlobal()) {
		//Look up the details on the global database
		$globalAdmins = \ze\row\g::getArray('admins', $adminColumns, ['authtype' => 'local']);
	
		//For all global admins...
		foreach ($globalAdmins as $globalId => &$admin) {
		
			//...check if they have an image and get the checksum...
			if ($admin['image_id']) {
				$admin['image_checksum'] = \ze\row\g::get('files', 'checksum', ['id' => $admin['image_id']]);
			} else {
				$admin['image_checksum'] = false;
			}
		
			//...and get an array of their actions
			$admin['_actions_'] = \ze\row\g::getArray('action_admin_link', 'action_name', ['admin_id' => $globalId], 'action_name');
		}
} else {
	//Return an empty string if the link is not working
		//I want the "global db not enabled" and "password not correct" states to be different,
		//yet still both evaulate to false.
	return '';
}


//Loop through all of the global admins we found
foreach ($globalAdmins as $globalId => &$admin) {
	
	$admin['global_id'] = $globalId;
	
	if (\ze::$dbL->checkTableDef(DB_PREFIX. 'admins', 'is_client_account')) {
		$admin['is_client_account'] = 0;
	}
	
	$key = ['global_id' => $admin['global_id']];
	
	//Skip trashed globsl admins that were never on this site in the first place
	if ($admin['status'] == 'deleted'
	 && !\ze\row::exists('admins', $key)) {
		continue;
	}
	
	//Did this admin have an image set?
	if ($admin['image_checksum'] !== false) {
		//If so, try to use the same image here, if we can find the image on this site as well
		if (!$admin['image_id'] = \ze\row::get('files', 'id', ['checksum' => $admin['image_checksum'], 'usage' => 'admin'])) {
			
			//If we can't find it, get the image from the global database
			$image = \ze\row\g::get('files', ['data', 'filename', 'checksum'], $admin['image_id']);
			
			//Copy it to the local database and then use the copy
			if ($image !== false) {
				$adminG['image_id'] = ze\file::addFromString('admin', $image['data'], $image['filename'], true);
			}
		}
	} else {
		$admin['image_id'] = 0;
	}
	
	$actions = $admin['_actions_'];
	unset($admin['image_checksum']);
	unset($admin['_actions_']);
	$admin['authtype'] = 'super';
	
	$admin['local_id'] = $localId = \ze\row::set('admins', $admin, $key);
	
	//Check to see if the specific permissions have changed
	$actionsHere = \ze\row::getValues('action_admin_link', 'action_name', ['admin_id' => $localId], 'action_name');
	if (print_r($actions, true) != print_r($actionsHere, true)) {
		
		//If so, delete the old ones and re-insert all of the new ones
		\ze\row::delete('action_admin_link', ['admin_id' => $localId]);
		foreach ($actions as $action) {
			\ze\row::insert('action_admin_link', ['admin_id' => $localId, 'action_name' => $action], true);
		}
	}
}

if (!empty($globalAdmins[$adminIdG]['local_id'])) {
	return $globalAdmins[$adminIdG]['local_id'];
} else {
	return false;
}