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

//This file should contain php scripts code for converting administrator data after some database structure changes


//
//	Zenario 9.4
//


//In 9.4 we're adding a new higher-level permission for managing site-wide things on a layout.
//Initially, give this new permission to anyone who already had permissions to use Gridmaker.

ze\dbAdm::revision(56880
, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]action_admin_link`
	SELECT '_PRIV_EDIT_SITEWIDE', admin_id
	FROM `[[DB_PREFIX]]action_admin_link`
	WHERE action_name = '_PRIV_EDIT_TEMPLATE'
_sql
);

ze\dbAdm::revision(57141
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]action_admin_link`
	WHERE action_name IN ('_PRIV_HIDE_CONTENT_ITEM', '_PRIV_MANAGE_SPARE_ALIAS')
_sql
);


//
//	Zenario 9.5
//

//In 9.5, we removed the domain redirects (or spare domains, as previously called).
//Clean up an obsolete permission...
ze\dbAdm::revision( 58553
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]action_admin_link`
	WHERE action_name = "_PRIV_MANAGE_SPARE_DOMAIN_NAME"
_sql
);

//... and also remove the perm_system permission if _PRIV_MANAGE_SPARE_DOMAIN_NAME
//was the only admin perm an admin had.
if (ze\dbAdm::needRevision(58554)) {
	$adminIds = [];
	$sql = "
		SELECT admin_id
		FROM " . DB_PREFIX . "action_admin_link
		WHERE action_name = 'perm_system'";
	$result = ze\sql::select($sql);
	
	while ($adminId = ze\sql::fetchValue($result)) {
		$adminIds[] = $adminId;
	}
	
	if (!empty($adminIds)) {
		foreach ($adminIds as $adminId) {
			$sql = "
				SELECT COUNT(*)
				FROM " . DB_PREFIX . "action_admin_link
				WHERE admin_id = " . (int) $adminId . "
				AND action_name IN (
					'_PRIV_VIEW_DIAGNOSTICS', '_PRIV_APPLY_DATABASE_UPDATES', '_PRIV_VIEW_SITE_SETTING', '_PRIV_EDIT_SITE_SETTING',
					'_PRIV_EDIT_CONTENT_TYPE', '_PRIV_MANAGE_DATASET', '_PRIV_PROTECT_UNPROTECT_DATASET_FIELD', '_PRIV_REGENERATE_DOCUMENT_PUBLIC_LINKS'
				)";
			$result = ze\sql::select($sql);
			$count = ze\sql::fetchValue($result);
			
			if (!$count) {
				ze\row::delete('action_admin_link', ['admin_id' => $adminId, 'action_name' => 'perm_system']);
			}
		}
	}

	ze\dbAdm::revision(58554);
}

//Likewise, remove the _PRIV_VIEW_MENU_ITEM, _PRIV_VIEW_TEMPLATE and do similar cleanups.
ze\dbAdm::revision( 58557
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]action_admin_link`
	WHERE action_name IN ("_PRIV_VIEW_MENU_ITEM", "_PRIV_VIEW_TEMPLATE")
_sql
);

if (ze\dbAdm::needRevision(58558)) {
	$adminIds = [];
	$sql = "
		SELECT admin_id
		FROM " . DB_PREFIX . "action_admin_link
		WHERE action_name = 'perm_editmenu'";
	$result = ze\sql::select($sql);
	
	while ($adminId = ze\sql::fetchValue($result)) {
		$adminIds[] = $adminId;
	}
	
	if (!empty($adminIds)) {
		foreach ($adminIds as $adminId) {
			$sql = "
				SELECT COUNT(*)
				FROM " . DB_PREFIX . "action_admin_link
				WHERE admin_id = " . (int) $adminId . "
				AND action_name IN (
					'_PRIV_EDIT_MENU_TEXT', '_PRIV_EDIT_MENU_ITEM', '_PRIV_ADD_MENU_ITEM', '_PRIV_DELETE_MENU_ITEM',
					'_PRIV_REORDER_MENU_ITEM', '_PRIV_ADD_MENU_SECTION', '_PRIV_DELETE_MENU_SECTION'
				)";
			$result = ze\sql::select($sql);
			$count = ze\sql::fetchValue($result);
			
			if (!$count) {
				ze\row::delete('action_admin_link', ['admin_id' => $adminId, 'action_name' => 'perm_editmenu']);
			}
		}
	}
	
	$adminIds = [];
	$sql = "
		SELECT admin_id
		FROM " . DB_PREFIX . "action_admin_link
		WHERE action_name = 'perm_designer'";
	$result = ze\sql::select($sql);
	
	while ($adminId = ze\sql::fetchValue($result)) {
		$adminIds[] = $adminId;
	}
	
	if (!empty($adminIds)) {
		foreach ($adminIds as $adminId) {
			$sql = "
				SELECT COUNT(*)
				FROM " . DB_PREFIX . "action_admin_link
				WHERE admin_id = " . (int) $adminId . "
				AND action_name IN (
					'_PRIV_EDIT_TEMPLATE', '_PRIV_EDIT_SITEWIDE', '_PRIV_EDIT_CSS', '_PRIV_VIEW_SLOT',
					'_PRIV_MANAGE_ITEM_SLOT', '_PRIV_MANAGE_TEMPLATE_SLOT', '_PRIV_RUN_MODULE', '_PRIV_RESET_MODULE'
				)";
			$result = ze\sql::select($sql);
			$count = ze\sql::fetchValue($result);
			
			if (!$count) {
				ze\row::delete('action_admin_link', ['admin_id' => $adminId, 'action_name' => 'perm_designer']);
			}
		}
	}

	ze\dbAdm::revision(58558);
}