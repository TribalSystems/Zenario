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



if (needRevision(131)) {
	
	if (!($dataset = getDatasetDetails('users'))) {
		echo adminPhrase('The Newsletter Module could not install correctly as the users data-view is not correctly set up.');
		exit;
	}
	
	$key = array(
		'db_column' => 'all_newsletters_opt_out',
		'dataset_id' => $dataset['id']);
	$cols = array(
			'tab_name' => 'details',
			'ord' => 999,
			'label' => 'Opt out of newsletters',
			'type' => 'checkbox',
			'show_in_organizer' => 1,
			'sortable' => 1);
	
	if (!checkRowExists('custom_dataset_fields', $key)) {
		$optOutFieldId = setRow('custom_dataset_fields', $cols, $key);
		createDatasetFieldInDB($optOutFieldId);
	}
	
	if (($statusField = getDatasetFieldDetails('status', 'users'))
	 && ($optOutField = getDatasetFieldDetails('all_newsletters_opt_out', 'users'))) {
		setSetting('zenario_newsletter__all_newsletters_opt_out', $optOutField['id']);
		
		$allActiveUsers =
			'{"first_tab":{"name":"All active users","indexes":"1","rule_type":"","rule_group_picker":"","rule_characteristic_picker":"","rule_characteristic_values_picker":"","rule_logic":"","rule_type_1":"characteristic","rule_group_picker_1":"","rule_characteristic_picker_1":"'.
			$statusField['id'].
			'","rule_characteristic_values_picker_1":"active","rule_logic_1":""},"exclude":{"enable":true,"rule_type":"characteristic","rule_group_picker":"","rule_characteristic_picker":"'.
			$optOutField['id'].
			'","rule_characteristic_values_picker":"","rule_logic":""}}';
		
		$allContacts =
			'{"first_tab":{"name":"All contacts","indexes":"1","rule_type":"","rule_group_picker":"","rule_characteristic_picker":"","rule_characteristic_values_picker":"","rule_logic":"","rule_type_1":"characteristic","rule_group_picker_1":"","rule_characteristic_picker_1":"'.
			$statusField['id'].
			'","rule_characteristic_values_picker_1":"contact","rule_logic_1":""},"exclude":{"enable":true,"rule_type":"characteristic","rule_group_picker":"","rule_characteristic_picker":"'.
			$optOutField['id'].
			'","rule_characteristic_values_picker":"","rule_logic":""}}';
		
		setRow(
			'smart_groups',
			array('created_on' => now(), 'last_modified_on' => now(), 'values' => $allActiveUsers),
			array('name' => 'All active users'));
		setRow(
			'smart_groups',
			array('created_on' => now(), 'last_modified_on' => now(), 'values' => $allContacts),
			array('name' => 'All contacts'));
	}
	
	revision(131);
}