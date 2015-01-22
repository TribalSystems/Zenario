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

//Define all possible conversaions to convert alternate formats/out of date formats of permissions
//to the format that the CMS actually uses. This covers:
	//zenario 5 -> zenario 6.0.5
	//zenario 6.0.0 - 6.0.4 -> zenario 6.0.5
	//Simplified community permissions -> detailed permissions

$defaultCommunityPerms = array(
	'perm_author' => true,
	'perm_edit_users' => true,
	'perm_manage' => true,
	'perm_editmenu' => true,
	'perm_publish' => true,
	'perm_designer' => true,
	'perm_restore' => true);

$simplifiedCommunityPerms = $defaultCommunityPerms;
$simplifiedCommunityPerms['perm_dev_tools'] = true;

$oldToNewPermMapping = array(
	'perm_author' => array(
		'_PRIV_VIEW_CONTENT_ITEM_SETTINGS',
			'_PRIV_CREATE_FIRST_DRAFT',
				'_PRIV_CREATE_REVISION_DRAFT',
					'_PRIV_DELETE_DRAFT',
						'_PRIV_EDIT_DRAFT',
							'_PRIV_EDIT_CONTENT_ITEM_CATEGORIES',
								'_PRIV_EDIT_CONTENT_ITEM_PERMISSIONS',
									'_PRIV_EDIT_CONTENT_ITEM_TEMPLATE',
										'_PRIV_MANAGE_MEDIA',
											'_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE'),
											'_PRIV_SET_ITEM_STICKY_IMAGE' => '_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE',
										'_PRIV_ADD_NEW_IMAGE' => '_PRIV_MANAGE_MEDIA',
										'_PRIV_ADD_NEW_MOVIE' => '_PRIV_MANAGE_MEDIA',
										'_PRIV_UPLOAD_ITEM_FILE' => '_PRIV_MANAGE_MEDIA',
										'_PRIV_DELETE_IMAGE' => '_PRIV_MANAGE_MEDIA',
									'_PRIV_EDIT_ITEM_TEMPLATE' => '_PRIV_EDIT_CONTENT_ITEM_TEMPLATE',
								'_PRIV_EDIT_ITEM_PERMISSIONS' => '_PRIV_EDIT_CONTENT_ITEM_PERMISSIONS',
							'_PRIV_EDIT_ITEM_CATEGORIES' => '_PRIV_EDIT_CONTENT_ITEM_CATEGORIES',
						'_PRIV_ADD_IMAGE_TO_ITEM' => '_PRIV_EDIT_DRAFT',
						'_PRIV_ADD_MOVIE_TO_ITEM' => '_PRIV_EDIT_DRAFT',
						'_PRIV_EDIT_ITEM_META_DATA' => '_PRIV_EDIT_DRAFT',
						'_PRIV_EDIT_ITEM_SETTINGS' => '_PRIV_EDIT_DRAFT',
						'_PRIV_LOCK_ITEM' => '_PRIV_EDIT_DRAFT',
				'_PRIV_ROLLBACK_ITEM' => '_PRIV_CREATE_REVISION_DRAFT',
				'_PRIV_UNHIDE_CONTENT' => '_PRIV_CREATE_REVISION_DRAFT',
			'_PRIV_DUPLICATE_ITEM' => '_PRIV_CREATE_FIRST_DRAFT',
		'_PRIV_VIEW_ITEM_SETTINGS' => '_PRIV_VIEW_CONTENT_ITEM_SETTINGS',
	'perm_edit_users' => array(
		'_PRIV_VIEW_USER',
			'_PRIV_CHANGE_USER_PASSWORD',
				'_PRIV_CHANGE_USER_STATUS',
					'_PRIV_CREATE_USER',
						'_PRIV_DELETE_USER',
							'_PRIV_EDIT_USER',
								'_PRIV_MANAGE_GROUP_MEMBERSHIP',
									'_PRIV_CREATE_GROUP',
										'_PRIV_DELETE_GROUP',
											'_PRIV_EDIT_GROUP',
												'_PRIV_MODERATE_USER_COMMENTS'),
		'_PRIV_VIEW_GROUP' => '_PRIV_VIEW_USER',
	'perm_manage' => array(
		'_PRIV_VIEW_ADMIN',
			'_PRIV_CHANGE_ADMIN_PASSWORD',
				'_PRIV_DELETE_ADMIN',
					'_PRIV_EDIT_ADMIN',
						'_PRIV_CREATE_ADMIN'),
	'perm_editmenu' => array(
		'_PRIV_VIEW_MENU_ITEM',
			'_PRIV_ADD_MENU_ITEM',
				'_PRIV_ADD_MENU_SECTION',
					'_PRIV_DELETE_MENU_ITEM',
						'_PRIV_DELETE_MENU_SECTION',
							'_PRIV_EDIT_MENU_ITEM',
								'_PRIV_REORDER_MENU_ITEM'),
								'_PRIV_SHUFFLE_MENU_ITEM' => '_PRIV_REORDER_MENU_ITEM',
							'_PRIV_EDIT_TOP_LEVEL_MENU_ITEM' => '_PRIV_EDIT_MENU_ITEM',
					'_PRIV_DELETE_TOP_LEVEL_MENU_ITEM' => '_PRIV_DELETE_MENU_ITEM',
			'_PRIV_ADD_TOP_LEVEL_MENU_ITEM' => '_PRIV_ADD_MENU_ITEM',
	'perm_publish' => array(
		'_PRIV_HIDE_CONTENT_ITEM',
			'_PRIV_MANAGE_CATEGORY',
				'_PRIV_MANAGE_EMAIL_TEMPLATE',
					'_PRIV_MANAGE_LANGUAGE_PHRASE',
						'_PRIV_PUBLISH_CONTENT_ITEM',
							'_PRIV_CANCEL_CHECKOUT',
								'_PRIV_TRASH_CONTENT_ITEM',
									'_PRIV_VIEW_REUSABLE_PLUGIN',
										'_PRIV_MANAGE_REUSABLE_PLUGIN',
											'_PRIV_VIEW_LANGUAGE',
												'_PRIV_MANAGE_LANGUAGE_CONFIG'),
												'_PRIV_EDIT_LANGUAGE_CONFIG' => '_PRIV_MANAGE_LANGUAGE_CONFIG',
											'_PRIV_VIEW_VLP' => '_PRIV_VIEW_LANGUAGE',
										'_PRIV_MANAGE_PLUGIN_INSTANCE' => '_PRIV_MANAGE_REUSABLE_PLUGIN',
										'_PRIV_MANAGE_NESTED_PLUGIN' => '_PRIV_MANAGE_REUSABLE_PLUGIN',
										'_PRIV_REORDER_PLUGIN_NEST' => '_PRIV_MANAGE_REUSABLE_PLUGIN',
									'_PRIV_VIEW_PLUGIN_INSTANCE' => '_PRIV_VIEW_REUSABLE_PLUGIN',
									'_PRIV_VIEW_NESTED_PLUGIN' => '_PRIV_VIEW_REUSABLE_PLUGIN',
								'_PRIV_TRASH_CONTENT' => '_PRIV_TRASH_CONTENT_ITEM',
							'_PRIV_REMOVE_LOCK_ON_ITEM' => '_PRIV_CANCEL_CHECKOUT',
						'_PRIV_PUBLISH_ITEM' => '_PRIV_PUBLISH_CONTENT_ITEM',
					'_PRIV_DELETE_VLP' => '_PRIV_MANAGE_LANGUAGE_PHRASE',
					'_PRIV_EDIT_VLP' => '_PRIV_MANAGE_LANGUAGE_PHRASE',
					'_PRIV_EXPORT_VLP' => '_PRIV_MANAGE_LANGUAGE_PHRASE',
					'_PRIV_IMPORT_VLP' => '_PRIV_MANAGE_LANGUAGE_PHRASE',
				'_PRIV_EDIT_EMAIL_TEMPLATE' => '_PRIV_MANAGE_EMAIL_TEMPLATE',
			'_PRIV_ADD_CATEGORY' => '_PRIV_MANAGE_CATEGORY',
			'_PRIV_CATEGORY_SETTINGS' => '_PRIV_MANAGE_CATEGORY',
			'_PRIV_DELETE_CATEGORY' => '_PRIV_MANAGE_CATEGORY',
		'_PRIV_HIDE_CONTENT' => '_PRIV_HIDE_CONTENT_ITEM',
	'perm_restore' => array(
		'_PRIV_BACKUP_SITE',
			'_PRIV_RESET_SITE',
				'_PRIV_RESTORE_SITE'),
	'perm_dev_tools' => array(
		'_PRIV_VIEW_DEV_TOOLS',
			'_PRIV_SAVE_DEV_TOOLS'),
	'perm_designer' => array(
			'_PRIV_MANAGE_SPARE_DOMAIN_NAME',
				'_PRIV_VIEW_SLOT',
					'_PRIV_MANAGE_ITEM_SLOT',
						'_PRIV_MANAGE_TEMPLATE_SLOT',
							'_PRIV_VIEW_TEMPLATE',
								'_PRIV_EDIT_TEMPLATE',
									'_PRIV_VIEW_TEMPLATE_FAMILY',
										'_PRIV_EDIT_TEMPLATE_FAMILY',
											'_PRIV_RUN_MODULE',
												'_PRIV_SUSPEND_MODULE',
													'_PRIV_RESET_MODULE',
														'_PRIV_EDIT_CONTENT_TYPE',
															'_PRIV_VIEW_SITE_SETTING',
																'_PRIV_EDIT_SITE_SETTING',
																	'_PRIV_MANAGE_DATASET',
																		'_PRIV_PROTECT_DATASET_FIELD'),
															'_PRIV_UNINSTALL_PLUGIN' => '_PRIV_RESET_MODULE',
														'_PRIV_SUSPEND_PLUGIN' => '_PRIV_SUSPEND_MODULE',
													'_PRIV_RUN_PLUGIN' => '_PRIV_RUN_MODULE',
	
	//A couple of Module permissions that were renamed in 6.0.4
	'_PRIV_EXPORT_ITEM' => '_PRIV_EXPORT_CONTENT_ITEM',
	'_PRIV_IMPORT_ITEM' => '_PRIV_IMPORT_CONTENT_ITEM',
	
	//Convert the old sysadmin perms, no longer used in 6.0.5 but was used previously
	'perm_sysadmin' => array(
		'_PRIV_BACKUP_SITE',
			'_PRIV_RESET_SITE',
				'_PRIV_RESTORE_SITE',
					'_PRIV_MANAGE_SPARE_DOMAIN_NAME',
						'_PRIV_VIEW_SLOT',
							'_PRIV_MANAGE_ITEM_SLOT',
								'_PRIV_MANAGE_TEMPLATE_SLOT',
									'_PRIV_VIEW_TEMPLATE',
										'_PRIV_EDIT_TEMPLATE',
											'_PRIV_VIEW_TEMPLATE_FAMILY',
												'_PRIV_EDIT_TEMPLATE_FAMILY',
													'_PRIV_RUN_MODULE',
														'_PRIV_SUSPEND_MODULE',
															'_PRIV_RESET_MODULE',
																'_PRIV_EDIT_CONTENT_TYPE',
																	'_PRIV_VIEW_SITE_SETTING',
																		'_PRIV_EDIT_SITE_SETTING')
);

//Have a quick shortcut for setting every single core permission (this is used in the installer).
if ($perms === 'all') {
	$perms = $defaultCommunityPerms;
}


//Look at the permissions we have, and see if any are in the conversaion array
$permsCopy = $perms;
foreach ($permsCopy as $perm => $set) {
	if (!empty($oldToNewPermMapping[$perm])) {
	
		//Add in the new permissions that each old/simple permission maps to. (There may be more than one, so loop through.)
		//But for simple perms, don't change it if it already set!
		if (!is_array($oldToNewPermMapping[$perm])) {
			$oldToNewPermMapping[$perm] = array($oldToNewPermMapping[$perm]);
		}
		foreach ($oldToNewPermMapping[$perm] as $newPerm) {
			if (!isset($perms[$newPerm])) {
				$perms[$newPerm] = $set;
			}
		}
	
		//Remove the old permission if it's not used anymore
		if (empty($simplifiedCommunityPerms[$perm])) {
			$perms[$perm] = false;
		}
	}
}
unset($permsCopy);

//Add/remove each permission from the database for this Admin.
foreach ($perms as $perm => $set) {
	if ($set) {
		setRow('action_admin_link', array(), array('action_name' => $perm, 'admin_id' => $adminId));
	} else {
		deleteRow('action_admin_link', array('action_name' => $perm, 'admin_id' => $adminId));
	}
}


//Set the modification date
$sql = "
	UPDATE ". DB_NAME_PREFIX. "admins SET
		modified_date = NOW()
	WHERE id = ". (int) $adminId;
sqlQuery($sql);