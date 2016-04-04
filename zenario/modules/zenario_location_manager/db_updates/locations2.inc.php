<?php
/*
 * Copyright (c) 2016, Tribal Limited
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

	revision( 65
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD KEY (`description`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD KEY (`city`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD KEY (`state`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD KEY (`postcode`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD KEY (`status`)
_sql
);

if (needRevision(135)) {
	$dataset = getDatasetDetails(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations');
	
	$columnsToMigrate = array();
	
	$key = array(
		'db_column' => 'phone',
		'dataset_id' => $dataset['id']);
	$cols = array('label' => 'Phone:', 'is_system_field' => 0);
	
	if (checkRowExists('custom_dataset_fields', $key)) {
		$sql = "SELECT COUNT('phone') as count FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations 
			WHERE phone IS NOT NULL AND phone <> ''";
		$result = sqlQuery($sql);
		$count = sqlFetchArray($result);
		if ($count['count'] > 0) {
			//change to custom field
			$fieldId = setRow('custom_dataset_fields', $cols, $key);
			createDatasetFieldInDB($fieldId);
			$columnsToMigrate[] = 'phone';
		} else {
			//otherwise remove from custom dataset fields
			deleteRow('custom_dataset_fields', $key);
		}
	}
	
	
	$key = array(
		'db_column' => 'fax',
		'dataset_id' => $dataset['id']);
	$cols = array('label' => 'Fax:', 'is_system_field' => 0);
	
	if (checkRowExists('custom_dataset_fields', $key)) {
		$sql = "SELECT COUNT('fax') as count FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations 
			WHERE fax IS NOT NULL AND fax <> ''";
		$result = sqlQuery($sql);
		$count = sqlFetchArray($result);
		if ($count['count'] > 0) {
			//change to custom field
			$fieldId = setRow('custom_dataset_fields', $cols, $key);
			createDatasetFieldInDB($fieldId);
			$columnsToMigrate[] = 'fax';
		} else {
			//otherwise remove from custom dataset fields
			deleteRow('custom_dataset_fields', $key);
		}
	}
	
	$key = array(
		'db_column' => 'url',
		'dataset_id' => $dataset['id']);
	$cols = array('label' => 'url:', 'is_system_field' => 0);
	
	if (checkRowExists('custom_dataset_fields', $key)) {
		$sql = "SELECT COUNT('url') as count FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations 
			WHERE url IS NOT NULL AND url <> ''";
		$result = sqlQuery($sql);
		$count = sqlFetchArray($result);
		if ($count['count'] > 0) {
			//change to custom field
			$fieldId = setRow('custom_dataset_fields', $cols, $key);
			createDatasetFieldInDB($fieldId);
			$columnsToMigrate[] = 'url';
		} else {
			//otherwise remove from custom dataset fields
			deleteRow('custom_dataset_fields', $key);
		}
	}
	
	
	$key = array(
		'db_column' => 'last_accessed',
		'dataset_id' => $dataset['id']);
	$cols = array('label' => 'url last accessed:', 'is_system_field' => 0);
	
	if (checkRowExists('custom_dataset_fields', $key)) {
		$sql = "SELECT COUNT('last_accessed') as count FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations 
			WHERE last_accessed IS NOT NULL AND last_accessed <> ''";
		$result = sqlQuery($sql);
		$count = sqlFetchArray($result);
		if ($count['count'] > 0) {
			//change to custom field
			$fieldId = setRow('custom_dataset_fields', $cols, $key);
			createDatasetFieldInDB($fieldId);
			$columnsToMigrate[] = 'last_accessed';
		} else {
			//otherwise remove from custom dataset fields
			deleteRow('custom_dataset_fields', $key);
		}
	}
	
	
	$key = array(
		'db_column' => 'summary',
		'dataset_id' => $dataset['id']);
	$cols = array('label' => 'Summary:','is_system_field' => 0, 'tab_name' => 'details');
	
	if (checkRowExists('custom_dataset_fields', $key)) {
		$sql = "SELECT COUNT('summary') as count FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations 
			WHERE summary IS NOT NULL AND summary <> ''";
		$result = sqlQuery($sql);
		$count = sqlFetchArray($result);
		if ($count['count'] > 0) {
			//change to custom field
			$fieldId = setRow('custom_dataset_fields', $cols, $key);
			createDatasetFieldInDB($fieldId);
			$columnsToMigrate[] = 'summary';
		} else {
			//otherwise remove from custom dataset fields
			deleteRow('custom_dataset_fields', $key);
		}
	}
	
	
	if ($columnsToMigrate) {
		$columnsToMigrateCommaList = implode(',', $columnsToMigrate);
		
		$sql = "
			INSERT INTO " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations_custom_data 
			(" . $columnsToMigrateCommaList . ",location_id) 
			SELECT ";
		
		foreach ($columnsToMigrate as $column) {
			$sql .= "IFNULL(l." . $column . ", ''), ";
		}
		
		$sql .= "
			id
			FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations as l 
			ON DUPLICATE KEY UPDATE ";
		
		foreach ($columnsToMigrate as $column) {
			$sql .= $column . " = IFNULL(l." . $column . ", ''),";
		}
		
		$sql = rtrim($sql, ",");
		
		sqlQuery($sql);
	}
	revision(135);
}

revision(136, 
<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	DROP COLUMN `phone`,
	DROP COLUMN `fax`,
	DROP COLUMN `url`,
	DROP COLUMN `last_accessed`,
	DROP COLUMN `summary`
_sql
); 

if (needRevision(140)) {
	$dataset = getDatasetDetails(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations');
	
	$columnsToMigrate = array();

	$key = array(
		'db_column' => 'email',
		'dataset_id' => $dataset['id']);
	$cols = array('label' => 'Email:', 'is_system_field' => 0);
	
	if (checkRowExists('custom_dataset_fields', $key)) {
		$sql = "SELECT COUNT('email') as count FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations 
			WHERE email IS NOT NULL AND email <> ''";
		$result = sqlQuery($sql);
		$count = sqlFetchArray($result);
		if ($count['count'] > 0) {
			//change to custom field
			$fieldId = setRow('custom_dataset_fields', $cols, $key);
			createDatasetFieldInDB($fieldId);
			$columnsToMigrate[] = 'email';
		}
	}
	
	if ($columnsToMigrate) {
		$columnsToMigrateCommaList = implode(',', $columnsToMigrate);
		
		$sql = "
			INSERT INTO " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations_custom_data 
			(" . $columnsToMigrateCommaList . ",location_id) 
			SELECT ";
		
		foreach ($columnsToMigrate as $column) {
			$sql .= "IFNULL(l." . $column . ", ''), ";
		}
		
		$sql .= "
			id
			FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations as l 
			ON DUPLICATE KEY UPDATE ";
		
		foreach ($columnsToMigrate as $column) {
			$sql .= $column . " = IFNULL(l." . $column . ", ''),";
		}
		
		$sql = rtrim($sql, ",");
		
		sqlQuery($sql);
	}
	revision(140);

}

revision(141, 
<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	DROP COLUMN `email`
_sql
); 
