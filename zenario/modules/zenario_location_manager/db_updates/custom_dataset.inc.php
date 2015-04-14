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


//Add a table to store custom locations data
revision(124
, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations_custom_data
_sql

, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations_custom_data (
		`location_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`location_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
 
);

if (needRevision(127)) {
	
	//Add or update a record in the custom_datasets table with the correct details
	$datasetId = registerDataset(
		'Locations',
		ZENARIO_LOCATION_MANAGER_PREFIX. 'locations_custom_data',
		ZENARIO_LOCATION_MANAGER_PREFIX. 'locations',
		'zenario_location_manager__location',
		'zenario__locations/panel',
		''
		//, '_PRIV_MANAGE_LOCATION'
	);
	//registerDataset($label, $table, $system_table = '', $extends_admin_box = '', $extends_organizer_panel = '', $view_priv = '', $edit_priv = '')
	
	
	//Register system fields
	//(System fields are registered automatically when an admin views the datasets panel in Organizer, so this step
	// is optional, but when they are registered automatically they default to the "other_system_field" type and are
	// not selectable in things such as User Forms. Specifically registering them like this will ensure they are
	// usable.)
	registerDatasetSystemField($datasetId, 'text', 'details', 'name', 'description');
	registerDatasetSystemField($datasetId, 'text', 'details', 'address_line_1', 'address1');
	registerDatasetSystemField($datasetId, 'text', 'details', 'address_line_2', 'address2');
	registerDatasetSystemField($datasetId, 'text', 'details', 'locality');
	registerDatasetSystemField($datasetId, 'text', 'details', 'city');
	registerDatasetSystemField($datasetId, 'text', 'details', 'state');
	registerDatasetSystemField($datasetId, 'text', 'details', 'postcode');
	registerDatasetSystemField($datasetId, 'text', 'details', 'country', 'country_id', 'none', 'zenario_country_manager::getActiveCountries');
	registerDatasetSystemField($datasetId, 'text', 'details', 'phone');
	registerDatasetSystemField($datasetId, 'text', 'details', 'fax');
	registerDatasetSystemField($datasetId, 'text', 'details', 'email', 'email', 'email');
	registerDatasetSystemField($datasetId, 'url', 'details', 'url');
	registerDatasetSystemField($datasetId, 'textarea', 'summary', 'summary');
	//registerDatasetSystemField($datasetId, $type, $tabName, $fieldName, $dbColumn = false, $validation = 'none', $valuesSource = '')


	revision(127);
}

if (needRevision(134)) {
	$dataset = getDatasetDetails(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations');
	registerDatasetSystemField($dataset['id'], 'date', 'details', 'url last accessed', 'last_accessed');
	
	revision(134);
}

if (needRevision(138)) {
	$dataset = getDatasetDetails(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations');
	deleteRow('custom_dataset_tabs', array('dataset_id' => $dataset['id'], 'name' => 'summary'));
	
	revision(138);
}

if (needRevision(139)) {
	$dataset = getDatasetDetails(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations');
	deleteRow('custom_dataset_tabs', array('dataset_id' => $dataset['id'], 'name' => 'details_plus'));
	
	revision(139);
}