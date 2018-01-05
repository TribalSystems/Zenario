<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

//This file contains php scripts code for converting user data after some database structure changes

//_PRIV_EDIT_TEMPLATE_FAMILY has been merged with _PRIV_EDIT_TEMPLATE.
if (ze\dbAdm::needRevision(41745)) {
	
	$result = ze\row::query('action_admin_link', true, ['action_name' => '_PRIV_EDIT_TEMPLATE_FAMILY']);
	while ($row = ze\sql::fetchAssoc($result)) {
		ze\row::set('action_admin_link', [], ['action_name' => '_PRIV_EDIT_TEMPLATE', 'admin_id' => $row['admin_id']]);
	}
	ze\row::delete('action_admin_link', ['action_name' => '_PRIV_EDIT_TEMPLATE_FAMILY']);
	ze\row::delete('action_admin_link', ['action_name' => '_PRIV_VIEW_TEMPLATE_FAMILY']);
	
	ze\dbAdm::revision(41745);
}

//New permission _PRIV_VIEW_FORMS should be given to anyone with _PRIV_MANAGE_FORMS
if (ze\dbAdm::needRevision(41746)) {
	
	$result = ze\row::query('action_admin_link', true, ['action_name' => '_PRIV_MANAGE_FORMS']);
	while ($row = ze\sql::fetchAssoc($result)) {
		ze\row::insert('action_admin_link', ['action_name' => '_PRIV_VIEW_FORMS', 'admin_id' => $row['admin_id']]);
	}
	
	ze\dbAdm::revision(41746);
}

//Bunch of user permissions are merged into one
if (ze\dbAdm::needRevision(41748)) {
	
	$sql = '
		SELECT DISTINCT admin_id
		FROM ' . DB_NAME_PREFIX . 'action_admin_link
		WHERE action_name IN ("_PRIV_CREATE_USER", "_PRIV_DELETE_USER", "_PRIV_CHANGE_USER_STATUS", "_PRIV_CHANGE_USER_PASSWORD")';
	$result = ze\sql::select($sql);
	while ($row = ze\sql::fetchAssoc($result)) {
		ze\row::set('action_admin_link', [], ['action_name' => '_PRIV_EDIT_USER', 'admin_id' => $row['admin_id']]);
	}
	
	ze\row::delete('action_admin_link', ['action_name' => '_PRIV_CREATE_USER']);
	ze\row::delete('action_admin_link', ['action_name' => '_PRIV_DELETE_USER']);
	ze\row::delete('action_admin_link', ['action_name' => '_PRIV_CHANGE_USER_STATUS']);
	ze\row::delete('action_admin_link', ['action_name' => '_PRIV_CHANGE_USER_PASSWORD']);
	
	ze\dbAdm::revision(41748);
}

//Company permission merged into locations permission
if (ze\dbAdm::needRevision(41749)) {
	
	$result = ze\row::query('action_admin_link', true, ['action_name' => '_PRIV_MANAGE_COMPANIES']);
	while ($row = ze\sql::fetchAssoc($result)) {
		ze\row::set('action_admin_link', [], ['action_name' => '_PRIV_MANAGE_LOCATIONS', 'admin_id' => $row['admin_id']]);
	}
	ze\row::delete('action_admin_link', ['action_name' => '_PRIV_MANAGE_COMPANIES']);
	
	ze\dbAdm::revision(41749);
}

if (ze\dbAdm::needRevision(41750)) {
	//_PRIV_SUSPEND_MODULE merged into _PRIV_RUN_MODULE
	$result = ze\row::query('action_admin_link', true, ['action_name' => '_PRIV_SUSPEND_MODULE']);
	while ($row = ze\sql::fetchAssoc($result)) {
		ze\row::set('action_admin_link', [], ['action_name' => '_PRIV_RUN_MODULE', 'admin_id' => $row['admin_id']]);
	}
	ze\row::delete('action_admin_link', ['action_name' => '_PRIV_SUSPEND_MODULE']);
	
	//_PRIV_TRASH_CONTENT_ITEM merged into _PRIV_HIDE_CONTENT_ITEM
	$result = ze\row::query('action_admin_link', true, ['action_name' => '_PRIV_TRASH_CONTENT_ITEM']);
	while ($row = ze\sql::fetchAssoc($result)) {
		ze\row::set('action_admin_link', [], ['action_name' => '_PRIV_HIDE_CONTENT_ITEM', 'admin_id' => $row['admin_id']]);
	}
	ze\row::delete('action_admin_link', ['action_name' => '_PRIV_TRASH_CONTENT_ITEM']);
	
	//_PRIV_VIEW_CONFIDENTIAL_USER_DOCUMENTS merged into _PRIV_MANAGE_CONFIDENTIAL_USER_DOCUMENTS
	$result = ze\row::query('action_admin_link', true, ['action_name' => '_PRIV_VIEW_CONFIDENTIAL_USER_DOCUMENTS']);
	while ($row = ze\sql::fetchAssoc($result)) {
		ze\row::set('action_admin_link', [], ['action_name' => '_PRIV_MANAGE_CONFIDENTIAL_USER_DOCUMENTS', 'admin_id' => $row['admin_id']]);
	}
	ze\row::delete('action_admin_link', ['action_name' => '_PRIV_VIEW_CONFIDENTIAL_USER_DOCUMENTS']);
	
	ze\dbAdm::revision(41750);
}