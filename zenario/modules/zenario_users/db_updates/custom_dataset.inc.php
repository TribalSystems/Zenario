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


if (ze\dbAdm::needRevision(50)) {
	
	//Add or update a record in the custom_datasets table with the correct details
	//(Note if you upgrade from version 7 or earlier this will have been done manually
	// by the migration script, but it's safe to call again.)
	$datasetId = ze\datasetAdm::register(
		'Users',
		'users_custom_data',
		'users',
		'zenario_user__details',
		'zenario__users/panels/users',
		'_PRIV_VIEW_USER',
		'_PRIV_EDIT_USER');
	//ze\datasetAdm::register($label, $table, $system_table = '', $extends_admin_box = '', $extends_organizer_panel = '', $view_priv = '', $edit_priv = '')
	
	
	//Register system fields
	//(System fields are registered automatically when an admin views the datasets panel in Organizer, so this step
	// is optional, but when they are registered automatically they default to the "other_system_field" type and are
	// not selectable in things such as User Forms. Specifically registering them like this will ensure they are
	// usable.)
	//(Again, if you upgrade from version 7 or earlier these will have also been done manually
	// by the migration script, but they're also safe to call again.)
	ze\datasetAdm::registerSystemField($datasetId, 'text', '', 'identifier', 'identifier', 'none', '', false, true);
	ze\datasetAdm::registerSystemField($datasetId, 'text', 'details', 'email', 'email', 'email', '', false, false, $includeInExport = true);
	ze\datasetAdm::registerSystemField($datasetId, 'checkbox', 'details', 'email_verified');
	ze\datasetAdm::registerSystemField($datasetId, 'text', 'details', 'salutation');
	ze\datasetAdm::registerSystemField($datasetId, 'text', 'details', 'first_name', false, 'none', '', false, false, $includeInExport = true);
	ze\datasetAdm::registerSystemField($datasetId, 'text', 'details', 'last_name', false, 'none', '', false, false, $includeInExport = true);
	ze\datasetAdm::registerSystemField($datasetId, 'text', 'details', 'screen_name', 'screen_name', 'screen_name');
	ze\datasetAdm::registerSystemField($datasetId, 'centralised_radios', 'details', 'status', 'status', 'none', 'zenario_common_features::userStatus', true, false, $includeInExport = true);
	ze\datasetAdm::registerSystemField($datasetId, 'text', 'details', 'password');
	ze\datasetAdm::registerSystemField($datasetId, 'checkbox', 'details', 'password_needs_changing');
	ze\datasetAdm::registerSystemField($datasetId, 'checkbox', 'details', 'terms_and_conditions_accepted');
	ze\datasetAdm::registerSystemField($datasetId, 'checkbox', 'details', 'screen_name_confirmed');
	ze\datasetAdm::registerSystemField($datasetId, 'checkbox', 'details', 'send_activation_email_to_user', '');
	
	ze\datasetAdm::registerSystemField($datasetId, 'date', 'dates', 'created_date');
	ze\datasetAdm::registerSystemField($datasetId, 'date', 'dates', 'modified_date');
	ze\datasetAdm::registerSystemField($datasetId, 'date', 'dates', 'last_login');
	ze\datasetAdm::registerSystemField($datasetId, 'text', 'dates', 'last_login_ip');
	ze\datasetAdm::registerSystemField($datasetId, 'date', 'dates', 'last_profile_update_in_frontend');
	ze\datasetAdm::registerSystemField($datasetId, 'date', 'dates', 'suspended_date');
	//ze\datasetAdm::registerSystemField($datasetId, $type, $tabName, $fieldName, $dbColumn = false, $validation = 'none', $valuesSource = '', $fundamental = false, $isRecordName = false)
	
	ze\dbAdm::revision(50);
}

if (ze\dbAdm::needRevision(52)) {
	
	if ($statusField = ze\dataset::fieldDetails('status', 'users', ['id'])) {
		
		$stats = ['created_on' => ze\date::now(), 'created_by' => ze\admin::id(), 'last_modified_on' => ze\date::now(), 'last_modified_by' => ze\admin::id()];
		
		$key = ['name' => 'All active users'];
		if (!ze\row::exists('smart_groups', $key)) {
			$smartGroupId = ze\row::set('smart_groups', $stats, $key);
			ze\row::set('smart_group_rules', ['field_id' => $statusField['id'], 'value' => 'active'], ['ord' => 1, 'smart_group_id' => $smartGroupId]);
		}
		
		$key = ['name' => 'All contacts'];
		if (!ze\row::exists('smart_groups', $key)) {
			$smartGroupId = ze\row::set('smart_groups', $stats, $key);
			ze\row::set('smart_group_rules', ['field_id' => $statusField['id'], 'value' => 'contact'], ['ord' => 1, 'smart_group_id' => $smartGroupId]);
		}
	}
	
	ze\dbAdm::revision(52);
}

//IP addresses have been deleted according to GDPR (General Data Protection Regulation)
//Delete the dataset field
if (ze\dbAdm::needRevision(71)) {
	$dataset = ze\dataset::details('users');
	ze\row::delete('custom_dataset_fields', ['dataset_id' => $dataset['id'], 'db_column' => 'last_login_ip']);
}

if (ze\dbAdm::needRevision(73)) {
	//Rename dataset
	$datasetId = ze\datasetAdm::register(
		'Users/Contacts',
		'users_custom_data',
		'users',
		'zenario_user__details',
		'zenario__users/panels/users',
		'_PRIV_VIEW_USER',
		'_PRIV_EDIT_USER');
	
	//Change terms_and_conditions_accepted to a consent type field 
	ze\datasetAdm::registerSystemField($datasetId, 'consent', 'details', 'terms_and_conditions_accepted');
	
	ze\dbAdm::revision(73);
}

if (ze\dbAdm::needRevision(74)) {
	//Get dataset ID
	$datasetId = ze\datasetAdm::register(
		'Users/Contacts',
		'users_custom_data',
		'users',
		'zenario_user__details',
		'zenario__users/panels/users',
		'_PRIV_VIEW_USER',
		'_PRIV_EDIT_USER');
	
	ze\datasetAdm::registerSystemField($datasetId, 'radios', 'profile_page', 'profile_page_link_type');
	ze\datasetAdm::registerSystemField($datasetId, 'other_system_field', 'profile_page', 'profile_page_internal_page');
	ze\datasetAdm::registerSystemField($datasetId, 'url', 'profile_page', 'profile_page_external_url');
	ze\datasetAdm::registerSystemField($datasetId, 'checkbox', 'profile_page', 'profile_page_target_blank');
	
	ze\dbAdm::revision(74);
}

if (ze\dbAdm::needRevision(80)) {
	//Remove the profile page fields introduced in the previous revision
	//Get dataset ID
	$datasetId = ze\datasetAdm::register(
		'Users/Contacts',
		'users_custom_data',
		'users',
		'zenario_user__details',
		'zenario__users/panels/users',
		'_PRIV_VIEW_USER',
		'_PRIV_EDIT_USER');
	
	ze\row::delete('custom_dataset_fields', ['dataset_id' => (int)$datasetId, 'tab_name' => 'profile_page'], $multiple = true);
	
	ze\dbAdm::revision(80);
}

if (ze\dbAdm::needRevision(81)) {
	//Remove the profile page tab, so it doesn't appear in admin boxes
	//Get dataset ID
	$datasetId = ze\datasetAdm::register(
		'Users/Contacts',
		'users_custom_data',
		'users',
		'zenario_user__details',
		'zenario__users/panels/users',
		'_PRIV_VIEW_USER',
		'_PRIV_EDIT_USER');
	
	ze\row::delete('custom_dataset_tabs', ['dataset_id' => (int)$datasetId, 'name' => 'profile_page'], $multiple = true);
	
	ze\dbAdm::revision(81);
}

if (ze\dbAdm::needRevision(93)) {
	//Rename the user status labels:
	//Pending --> Pending extranet user
	//Active --> Active extranet user
	//Suspended --> Suspended extranet user

	//Get dataset ID
	$datasetId = ze\datasetAdm::register(
		'Users/Contacts',
		'users_custom_data',
		'users',
		'zenario_user__details',
		'zenario__users/panels/users',
		'_PRIV_VIEW_USER',
		'_PRIV_EDIT_USER');
	
	ze\datasetAdm::registerSystemField($datasetId, 'centralised_radios', 'details', 'status', 'status', 'none', 'zenario_common_features::userStatus', true, false, $includeInExport = true);
	
	ze\dbAdm::revision(93);
}