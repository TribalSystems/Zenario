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

$adminColumns = array(
	'id', 'authtype', 'global_id', 'username', 'password', 'password_salt', 'password_needs_changing', 
	'status', 'last_login', 'first_name', 'last_name', 'email', 'created_date', 'modified_date', 'image_id');
		//last_login_ip isn't in this list yet as it was newly added in 6.0.4

$adminL = array();
$adminG = array();
$actionsL = array();
$actionsG = array();
$adminIdL = false;
$image = false;
$result = sqlSelect("SHOW COLUMNS IN ". DB_NAME_PREFIX. "admins WHERE Field = 'image_id'");
$dbAtRecentRevision = sqlFetchRow($result);

//Look up the current copy of the Admin's details on the local database
if ($dbAtRecentRevision) {
	if ($row = getRow('admins', $adminColumns, array('authtype' => 'super', 'global_id' => $adminIdG))) {
		$adminL = $row;
		$adminIdL = $adminL['id'];
		unset($adminL['id']);
		
		$result = getRows('action_admin_link', 'action_name', array('admin_id' => $adminIdL), array('action_name'));
		
		while ($row = sqlFetchAssoc($result)) {
			$actionsL[] = $row['action_name'];
		}
	}
}

//Look up the details on the global database
if (connectGlobalDB()) {
		//Get the full admin details and permissions for the current Admin
		if ($adminG = getRow('admins', $adminColumns, array('authtype' => 'local', 'id' => $adminIdG))) {
			unset($adminG['id']);
			
			$result = getRows('action_admin_link', 'action_name', array('admin_id' => $adminIdG), array('action_name'));
			
			while ($row = sqlFetchAssoc($result)) {
				$actionsG[] = $row['action_name'];
			}
		} else {
			return false;
		}
		
		//Get a few details on every Global Admin
		$result = getRows(
			'admins',
			array('id', 'username', 'status', 'last_login', 'first_name', 'last_name', 'email', 'created_date', 'modified_date'),
			array('authtype' => 'local'),
			'id');
		
		$adminStatuses = array();
		while ($row = sqlFetchAssoc($result)) {
			$adminStatuses[] = $row;
		}
		
		//Check to see if the Admin has an image, and get its details if so
		if ($adminG['image_id']) {
			$image = getRow('files', array('data', 'filename', 'checksum'), $adminG['image_id']);
			unset($image['id']);
		}
	connectLocalDB();
	
	
	//Compare the two
	$adminG['authtype'] = 'super';
	$adminG['global_id'] = (int) $adminIdG;
	
	if (!$adminIdL || print_r($adminL, true) != print_r($adminG, true) || print_r($actionsL, true) != print_r($actionsG, true)) {
		//Update the two if there are differences
		if ($dbAtRecentRevision) {
			
			//If the admin had an image, try to find the local version of it
			if ($image) {
				if (!$adminG['image_id'] = getRow('files', 'id', array('checksum'=> $image['checksum'], 'usage' => 'admin'))) {
					//If there was no local version, try to add one
					$adminG['image_id'] = addFileFromString('admin', $image['data'], $image['filename'], true);
				}
			}
			
			$adminIdL = setRow('admins', $adminG, $adminIdL);
			
			deleteRow('action_admin_link', array('admin_id' => $adminIdL));
			foreach ($actionsG as $action) {
				insertRow('action_admin_link', array('admin_id' => $adminIdL, 'action_name' => $action));
			}
			
		} else {
			$sql = "
				INSERT INTO `". DB_NAME_PREFIX. "admins` SET
					username = '". sqlEscape($adminG['username']). "',
					status = 'active',
					first_name = '". sqlEscape($adminG['first_name']). "',
					last_name = '". sqlEscape($adminG['last_name']). "',
					email = '". sqlEscape($adminG['email']). "'";
			sqlUpdate($sql);
			$adminIdL = sqlInsertId();
		}
	}
	
	//Update the details on every Global Admin, if they have previously logged in
	if ($dbAtRecentRevision) {
		foreach ($adminStatuses as &$adminStatus) {
			$id = array('global_id' => $adminStatus['id'], 'authtype' => 'super');
			unset($adminStatus['id']);
			updateRow('admins', $adminStatus, $id);
		}
	}
	
	return $adminIdL;
}

//Return an empty string if the link is not working
	//I want the "global db not enabled" and "password not correct" states to be different,
	//yet still both evaulate to false.
return '';