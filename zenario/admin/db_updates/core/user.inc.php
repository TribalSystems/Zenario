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


//A couple of functions to assist with migrating user-data from version 6.1.0 to 6.1.1
function get_user_main_table_fields(){
	return array(
			array('email', 'Email:', 1, 'text', 1, 1),
			array('salutation', 'Salutation:', 2, 'text', 1, 1),
			array('first_name', 'First Name:', 3, 'text', 1, 1),
			array('last_name', 'Last Name:', 4, 'text', 1, 1),
			array('status', 'Status:', 5, 'list_single_select', 1, 1),
			array('screen_name', 'Screen Name:', 6, 'text', 1, 1),
			array('password', 'Password:', 7, 'text', 1, 1),
			array('created_date', 'Created Date:', 8, 'date', 1, 1),
			array('modified_date', 'Modified Date:', 9, 'date', 1, 1),
			array('last_login', 'Last Login:', 10, 'date', 1, 1)
	);
}

function get_user_main_table_fields_to_sql_insert(){
	$result = '';
	foreach(get_user_main_table_fields() as $fld){
		if($result) $result .= ',';
		$result .= sprintf("('%s', '%s', %d, '%s', %d, %d)", $fld[0], $fld[1], $fld[2], $fld[3], $fld[4], $fld[5]);
	}
	return $result;
}





revision( 13760
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `last_profile_update_in_frontend` datetime default NULL
	AFTER `last_login`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD INDEX (`last_profile_update_in_frontend`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD INDEX (`last_login`)
_sql
);

// Update empty User's hash values
if (needRevision(17643)) {
	$sql = "UPDATE 
			"  . DB_NAME_PREFIX . "users 
		SET 
			hash = md5(CONCAT(username,'" . primaryDomain() . "'))
		WHERE 
			hash is NULL or hash=''";
	 
	sqlQuery($sql);

	revision(17643);
}


	revision( 21080
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `equiv_id` int(10) unsigned NOT NULL DEFAULT 0
	AFTER `notes`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `content_type` varchar(20) NOT NULL DEFAULT ''
	AFTER `equiv_id`
_sql


//Fix some keys with bad names
);	revision( 21340
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]characteristic_user_link`
	DROP KEY `idx_characteristic_id`
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]characteristic_user_link`
	ADD KEY (`characteristic_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]characteristic_user_link`
	DROP KEY `idx_characteristic_value_id`
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]characteristic_user_link`
	ADD KEY (`characteristic_value_id`)
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristic_values`
	DROP KEY `idx_characteristic_id`
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristic_values`
	ADD KEY (`characteristic_id`)
_sql
);


revision(22770
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users` MODIFY COLUMN `status` enum('pending','active','suspended', 'contact') NOT NULL DEFAULT 'pending';
_sql
);

revision(22780
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` ADD COLUMN `label` varchar(255) NOT NULL DEFAULT 'Label not assigned' AFTER `name`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` MODIFY COLUMN `type` enum('list_single_select','list_multi_select','boolean','date','text','textarea', 'group') NOT NULL;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristic_values` ADD COLUMN `label` varchar(255) NOT NULL DEFAULT 'Label not assigned' AFTER `name`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristic_values` ADD COLUMN `help_text` varchar(255);
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` ADD COLUMN `help_text` varchar(255);
_sql

);

revision(22801
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_admin_box_tabs`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]user_admin_box_tabs` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`ordinal` int(10) unsigned NOT NULL default 0,
		`name` varchar(64) NOT NULL,
		`label` varchar(255) NOT NULL,
		`is_system_field` tinyint(1) NOT NULL DEFAULT 0,
		UNIQUE  KEY (`label`),
		UNIQUE  KEY (`name`),
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]user_admin_box_tabs`(`ordinal`, `label`, `name`, `is_system_field`)
	VALUES('1', 'Details', 'main_info', 1);
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` ADD `admin_box_tab_id` int(10) unsigned;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` ADD `is_system_field` tinyint(1) NOT NULL DEFAULT 0;
_sql

);

revision(22802
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_characteristics` SET
		ordinal = ordinal + 100
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]user_characteristics`(`name`, `label`, `ordinal`, `type`, `is_system_field`, `admin_box_tab_id`)
	VALUES
_sql
. get_user_main_table_fields_to_sql_insert()

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]user_characteristic_values`(`characteristic_id`, `ordinal`, `name`, `label`)
	VALUES
		((SELECT id FROM `[[DB_NAME_PREFIX]]user_characteristics` WHERE name = 'status'), 1, 'pending', 'Pending extranet users'),
		((SELECT id FROM `[[DB_NAME_PREFIX]]user_characteristics` WHERE name = 'status'), 2, 'active', 'Active'),
		((SELECT id FROM `[[DB_NAME_PREFIX]]user_characteristics` WHERE name = 'status'), 3, 'suspended', 'Suspended'),
		((SELECT id FROM `[[DB_NAME_PREFIX]]user_characteristics` WHERE name = 'status'), 4, 'contact', 'Contact');
_sql

);

revision(22803
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_data_dynamic`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]users_custom_data`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]users_custom_data` (
		`user_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`user_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

);

revision(22804

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` MODIFY COLUMN `type` enum('list_single_select','list_multi_select','boolean','date','text','textarea', 'group', 'integer', 'float') NOT NULL;
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]user_characteristics`(`name`, `label`, `ordinal`, `type`, `is_system_field`, `admin_box_tab_id`)
	VALUES
			('image_id', 'Image:', 11, 'integer', true, 1),
			('ip', 'IP:', 12, 'text', true, 1),
			('last_profile_update_in_frontend', 'Last profile update:', 13, 'date', true, 1),
			('suspended_date', 'Suspended date:', 14, 'date', true, 1);
_sql

);


revision(22805

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]user_characteristics`(`name`, `label`, `ordinal`, `type`, `is_system_field`, `admin_box_tab_id`)
	VALUES
			('hash', 'Hashed password:', 15, 'text', true, 1);
_sql

);

revision(22807

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_characteristic_values_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]user_characteristic_values_link` (
		`user_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`user_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

);


revision(23220
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	MODIFY COLUMN `status` enum('pending','active','suspended', 'contact') NOT NULL DEFAULT 'pending'
_sql
);



revision(23230
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `username`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `spare_blob1`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `spare_time1`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `spare_time2`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `spare_enum1`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `spare_enum2`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `spare_int1`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `spare_int2`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `spare_float1`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `spare_float2`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `consent1`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `consent2`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `consent3`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `spare_date1`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `spare_date2`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `spare_datetime1`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `spare_datetime2`;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users` CHANGE `title` `salutation` varchar(25);
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]user_admin_box_tabs`(`ordinal`, `label`, `name`, `is_system_field`)
	VALUES('2', 'Upgraded Characteristics', 'migrated_characteristics', 0);
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]user_admin_box_tabs`(`ordinal`, `label`, `name`, `is_system_field`)
	VALUES('3', 'Upgraded Groups', 'migrated_groups', 0);
_sql

);



revision(23240
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups` ADD `user_characteristic_id` int(10)
_sql
);





//Convert the format of users from pre 6.1.1
if (needRevision(23250)) {
	
	//Already existing user characteristics
	$sql = "SELECT * FROM `". DB_NAME_PREFIX. "user_characteristics`";
	$result = sqlQuery($sql);	
	while($row = sqlFetchAssoc($result)){
		$machine_name = sqlEscape($row['name']);
		$machine_name = mb_strtolower($machine_name);
		$machine_name = mb_ereg_replace('[\s-]+', '_', $machine_name);
		$machine_name = mb_ereg_replace('[^a-z_0-9]+', '', $machine_name);
		
		if($machine_name != $row['name']){
			$sql = "UPDATE `". DB_NAME_PREFIX. "user_characteristics` SET label = name, name='" . $machine_name . "'
					WHERE id=" . $row['id'];
			sqlQuery($sql);
		}
		//Tab 3 set above
		$sql = "UPDATE `". DB_NAME_PREFIX. "user_characteristics` SET admin_box_tab_id = 2	WHERE admin_box_tab_id IS NULL AND id=" . $row['id'];
		sqlQuery($sql);
		
		if($row['type'] == 'list_single_select' || $row['type'] == 'list_multi_select'){
			$sql_values = "SELECT * FROM `". DB_NAME_PREFIX. "user_characteristic_values` WHERE characteristic_id=" . $row['id'];
			$result_values = sqlQuery($sql_values);
			while($row_values = sqlFetchAssoc($result_values)){
				$machine_name = sqlEscape($row_values['name']);
				$machine_name = mb_strtolower($machine_name);
				$machine_name = mb_ereg_replace('[\s-]+', '_', $machine_name);
				$machine_name = mb_ereg_replace('[^a-z_0-9]+', '', $machine_name);
				
				if($machine_name != $row_values['name']){
					$sql_update = "UPDATE `". DB_NAME_PREFIX. "user_characteristic_values` SET label = name, name='" . $machine_name . "'
					WHERE id=" . $row_values['id'];
					sqlQuery($sql_update);
				}
			}
		}
	}
	
	/*
	 * 
	 * Rename title to salutation ?????????????
	 * Rename equiv_id to profile_equiv_id ???????
	 * Rename content_type to profile_content_type ???????
	 * 
	 * */
	
	$rec_fields_to_cvt = array(
			//'id' => 'int',
			//'parent_id' => 'int',
			//'ip' => 'text',
			//'session_id'=> 'text',
			//'username' => 'text',
			//'screen_name' varchar(50) DEFAULT '',
			//'password' varchar(50) NOT NULL DEFAULT '',
			//'status' int(10) unsigned NOT NULL DEFAULT '0',
			//'image_id' => 'int',
			//'last_login' datetime DEFAULT NULL,
			//'last_profile_update_in_frontend' datetime DEFAULT NULL,
			//'title' varchar(25) DEFAULT '',
			//'first_name' varchar(100) NOT NULL DEFAULT '',
			'middle_name' => 'text',
			//'last_name' varchar(100) NOT NULL DEFAULT '',
			'maiden_name' => 'text',
			'birth_date' => 'date',
			//'email' varchar(100) NOT NULL DEFAULT '',
			//'email_verified' tinyint(1) NOT NULL DEFAULT '0',
			'alt_email' => 'text',
			'website' => 'text',
			'job_title' => 'text',
			'company_name' => 'text',
			'bus_address1' => 'text',
			'bus_address2' => 'text',
			'bus_address3' => 'text',
			'bus_town' => 'text',
			'bus_state' => 'text',
			'bus_postcode' => 'text',
			'bus_country_id' => 'text',
			'res_address1' => 'text',
			'res_address2' => 'text',
			'res_address3' => 'text',
			'res_town' => 'text',
			'res_state' => 'text',
			'res_postcode' => 'text',
			'res_country_id' => 'text',
			
			'address_pref' => 'enum', //('residential','business','no_pref') NOT NULL DEFAULT 'no_pref',
			
			'mobile' => 'text',
			'bus_phone' => 'text',
			'res_phone' => 'text',
			'fax' => 'text',
			//'created_date' datetime DEFAULT NULL,
			//'modified_date' datetime DEFAULT NULL,
			//'suspended_date' datetime DEFAULT NULL,
			'first_subscrip_id' => 'int',
			'first_subscrip_start' => 'datetime',
			'first_subscrip_end' => 'datetime',
			'notes' => 'text',
			//'equiv_id' int(10) unsigned NOT NULL DEFAULT '0',
			//'content_type' varchar(20) NOT NULL DEFAULT '',
			'spare_varchar1' => 'text',
			'spare_varchar2' => 'text',
			'spare_varchar3' => 'text',
			'spare_varchar4' => 'text',
			'spare_varchar5' => 'text',
			'spare_text1' => 'longtext',
			//'spare_blob1' longblob,
			//'spare_date1' date DEFAULT NULL,
			//'spare_date2' date DEFAULT NULL,
			//'spare_datetime1' datetime DEFAULT NULL,
			//'spare_datetime2' datetime DEFAULT NULL,
			//'spare_time1' time DEFAULT NULL,
			//'spare_time2' time DEFAULT NULL,
			//'spare_enum1' enum('A','B','C','D','E') DEFAULT NULL,
			//'spare_enum2' enum('A','B','C','D','E') DEFAULT NULL,
			//'spare_int1' int(11) DEFAULT NULL,
			//'spare_int2' int(11) DEFAULT NULL,
			//'spare_float1' float(8,2) DEFAULT NULL,
			//'spare_float2' float(8,2) DEFAULT NULL,
			//'consent1' enum('Yes','No') DEFAULT NULL,
			//'consent2' enum('Yes','No') DEFAULT NULL,
			//'consent3' enum('Yes','No') DEFAULT NULL,
			//'hash' varchar(255) DEFAULT NULL,
	);
	
	$sql_insert_user_id_in_custom_data = "INSERT IGNORE INTO `". DB_NAME_PREFIX. "users_custom_data`(user_id)
			SELECT id FROM `". DB_NAME_PREFIX. "users`";
	sqlQuery($sql_insert_user_id_in_custom_data);
	
	$sql = "SELECT MAX(ordinal) FROM `". DB_NAME_PREFIX. "user_characteristics`";
	$result = sqlQuery($sql);
	$row = sqlFetchRow($result);
	$last_ordinal = $row[0];
	
	foreach($rec_fields_to_cvt as $column => $type){
		
		if (($sql = "SHOW COLUMNS IN `". DB_NAME_PREFIX. "users` LIKE '". sqlEscape($column). "'")
		 && ($result = sqlQuery($sql))
		 && (sqlFetchRow($result))) {
			
			$field_type = 'text';
			$db_field_type = 'text';
			
		 	if($column == 'address_pref') {
				$field_type = 'list_single_select';
				$db_field_type = "enum('residential','business','no_pref') NOT NULL DEFAULT 'no_pref'";
			} elseif($type == 'longtext'){
				$field_type = 'text';
				$db_field_type = 'text';
			} elseif($type == 'enum') {
				$field_type = 'text';
				$db_field_type = 'varchar(255)';
			} elseif($type == 'int') {
				$field_type = 'integer';
				$db_field_type = 'int(10)';
			} elseif($type == 'date') {
				$field_type = 'date';
				$db_field_type = 'datetime';
			} elseif($type == 'datetime') {
				$field_type = 'date';
				$db_field_type = 'datetime';
			} elseif($type == 'boolean') {
				$field_type = 'boolean';
				$db_field_type = 'tinyint(1) NOT NULL DEFAULT 0';
			}
			
			
			//$sql = "SELECT EXISTS(SELECT id FROM `". DB_NAME_PREFIX. "users` WHERE `$column` <> '') as one";
			$sql = "SELECT COUNT(*) AS one FROM `". DB_NAME_PREFIX. "users` WHERE `$column` <> ''";
			//echo "$sql\n";
			$result = sqlQuery($sql);
			if($result && ($row = sqlFetchRow($result))){
				//echo "$sql {$row[0]}\n";
				if($row[0]){
					$sql_add_column = "ALTER TABLE `". DB_NAME_PREFIX. "users_custom_data` ADD COLUMN `$column` $db_field_type";
					//echo "$sql_add_column\n";
					sqlQuery($sql_add_column);
					
					$last_ordinal++;
					
					$sql_add_user_charactheristic = "
						INSERT IGNORE INTO `". DB_NAME_PREFIX. "user_characteristics`(`name`, `label`, `ordinal`, `type`, `is_system_field`, `admin_box_tab_id`)
						VALUES
						('$column', '$column:', $last_ordinal, '$field_type', 0, 2)"; //2 for the tab 'Previous Characteristics' created above
					
					sqlQuery($sql_add_user_charactheristic);
	
					$sql_transfer_data = '';
					if($field_type == 'datetime' || $field_type == 'date') {
						$sql_transfer_data = "UPDATE `". DB_NAME_PREFIX. "users_custom_data`
							SET `$column` = (SELECT CASE WHEN (`$column` = '0000-00-00 00:00:00' OR `$column` = '0000-00-00') THEN NULL ELSE `$column` END
							FROM `". DB_NAME_PREFIX. "users`
							WHERE user_id = id)";
					} else {
						$sql_transfer_data = "UPDATE `". DB_NAME_PREFIX. "users_custom_data`
							SET `$column` = (SELECT `$column`
							FROM `". DB_NAME_PREFIX. "users`
							WHERE user_id = id)";	
					}
	
					sqlQuery($sql_transfer_data);
					
				}
			}
		
			//drop all converted columns
			$sql = "ALTER TABLE `". DB_NAME_PREFIX. "users` DROP `". sqlEscape($column). "`";
			sqlQuery($sql);
		}
	}
		
	
	//Groups
	$sql = "SELECT * FROM `". DB_NAME_PREFIX. "groups` ";
	$old_groups_result = sqlQuery($sql);
	
	while($row_group = sqlFetchAssoc($old_groups_result)){
		$last_ordinal++;
		$group_label = sqlEscape($row_group['name']);
		$group_field_name = mb_strtolower($row_group['name']);
		$group_field_name = mb_ereg_replace('[\s-]+', '_', $group_field_name);
		$group_field_name = mb_ereg_replace('[^a-z_0-9]+', '', $group_field_name);
		
		$sql_add_user_charactheristic = "
			INSERT IGNORE INTO `". DB_NAME_PREFIX. "user_characteristics`(`name`, `label`, `ordinal`, `type`, `is_system_field`, `admin_box_tab_id`)
			VALUES
			('$group_field_name', '$group_label:', $last_ordinal, 'group', 0, 3)"; //3 for the tab 'Groups as Characteristics' created above
			
		sqlQuery($sql_add_user_charactheristic);

		$user_characteristic_id = sqlInsertId();
		
		$sql_update_group_with_user_characteristic_id = "UPDATE `". DB_NAME_PREFIX. "groups` SET user_characteristic_id=$user_characteristic_id
			WHERE id={$row_group['id']}";
		sqlQuery($sql_update_group_with_user_characteristic_id);
		
		$sql_add_column = "ALTER TABLE `". DB_NAME_PREFIX. "users_custom_data` ADD COLUMN `$group_field_name` tinyint(1) NOT NULL DEFAULT 0";
		sqlQuery($sql_add_column);
				
		$sql_transfer_data = "UPDATE `". DB_NAME_PREFIX. "users_custom_data` as ucd
			SET `$group_field_name` = 1
			WHERE ucd.user_id IN(SELECT user_id
				FROM `". DB_NAME_PREFIX. "group_user_link`
				WHERE group_id={$row_group['id']})";
		sqlQuery($sql_transfer_data);
	}
	
	
	revision(23250);
}

revision(23261

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_forms`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]user_forms` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`name` varchar(255) NOT NULL,
		UNIQUE  KEY (`name`),
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_form_fields`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]user_form_fields` (
		`user_form_id` int(10) unsigned NOT NULL,
		`user_field_id` int(10) unsigned NOT NULL,
		`ordinal` int(10) unsigned NOT NULL default 0,
		PRIMARY KEY (`user_form_id`,`user_field_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

);

revision(23262

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` MODIFY COLUMN `group_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` MODIFY COLUMN `admin_box_tab_id` int(10) unsigned NOT NULL default 0
_sql

);

revision(23263

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_characteristic_values_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]user_characteristic_values_link` (
		`user_id` int(10) unsigned NOT NULL,
		`user_characteristic_value_id` int(10) NOT NULL,
		PRIMARY KEY (`user_id`, `user_characteristic_value_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
			
);

if (needRevision(23264)) {

	$addr_pref_characteristic = 0;
	$sql = "SELECT id FROM " . DB_NAME_PREFIX . "user_characteristics WHERE name='address_pref'";
	$result = sqlQuery($sql);
	if($result && ($row=sqlFetchRow($result))) {
		$addr_pref_characteristic_id = $row[0];
		
		$sql = "SELECT MAX(ordinal) FROM `". DB_NAME_PREFIX. "user_characteristics`";
		$result = sqlQuery($sql);
		$row = sqlFetchRow($result);
		$last_ordinal = (int)$row[0];
		
		$characteristic_value = array(
				'characteristic_id' => $addr_pref_characteristic_id,
				'ordinal' => ++$last_ordinal,
				'name' => 'residential',
				'label' => 'Residential addr.:'
		);
		$residential = setRow('user_characteristic_values', $characteristic_value);
		
		$characteristic_value['name'] = 'business';
		$characteristic_value['label'] = 'Business addr.:';
		$characteristic_value['ordinal'] = ++$last_ordinal;
		$business = setRow('user_characteristic_values', $characteristic_value);

		$characteristic_value['name'] = 'no_pref';
		$characteristic_value['label'] = 'No addr. pref.:';
		$characteristic_value['ordinal'] = ++$last_ordinal;
		$no_pref = setRow('user_characteristic_values', $characteristic_value);

		$tmp_table = 'zenario_old_user_addr_pref';
		$sql = "CREATE TEMPORARY TABLE $tmp_table(`id` int(10) unsigned PRIMARY KEY NOT NULL, `value` int(10) unsigned NOT NULL)";
		sqlQuery($sql);
		
		$sql = "INSERT INTO $tmp_table(id, value) SELECT user_id, 
			CASE address_pref 
				WHEN 'business' THEN $business
				WHEN 'residential' THEN $residential
				ELSE $no_pref
			END
			FROM " . DB_NAME_PREFIX . "users_custom_data";
		sqlQuery($sql);
		
		$sql = "ALTER TABLE " . DB_NAME_PREFIX . "users_custom_data MODIFY COLUMN `address_pref` int(10) unsigned NOT NULL default 0";
		sqlQuery($sql);
		
		$sql = "UPDATE " . DB_NAME_PREFIX . "users_custom_data SET address_pref=(SELECT value FROM $tmp_table WHERE id=user_id)";
		sqlQuery($sql);

		$sql = "DELETE FROM " . DB_NAME_PREFIX . "user_characteristic_values WHERE id IN(
					SELECT characteristic_value_id FROM " . DB_NAME_PREFIX . "characteristic_user_link
					WHERE characteristic_id IN($residential,$business, $no_pref)
				)";
		sqlQuery($sql);

		$sql = "DELETE FROM " . DB_NAME_PREFIX . "characteristic_user_link 
				WHERE characteristic_id IN($residential,$business, $no_pref)";
		sqlQuery($sql);

	
		//delete possible duplicates
		$sql = "DELETE FROM " . DB_NAME_PREFIX . "characteristic_user_link WHERE id NOT IN (
					SELECT id
					FROM (					
						SELECT MIN( id ) AS id
						FROM " . DB_NAME_PREFIX . "characteristic_user_link AS cul
						GROUP BY cul.user_id, cul.characteristic_id, characteristic_value_id
					) AS tu
				)";
		sqlQuery($sql);
	}
	
	revision(23264);
}
	
if (needRevision(23265)) {
	//text, date, booleans user_characteristics
	$sql = "SELECT id, name, type FROM " . DB_NAME_PREFIX . "user_characteristics WHERE NOT is_system_field";
	$result = sqlQuery($sql);
	while($row = sqlFetchRow($result)) {
		$characteristic_id = $row[0];
		$characteristic_name = $row[1];
		$characteristic_type = $row[2];
						
		if($characteristic_name == 'address_pref') {
			//we already dealt with it above
			continue;
		}
		
		$column_type = 'text';
		
		if($characteristic_type == 'list_single_select' 
				|| $characteristic_type == 'list_multi_select' 
				|| $characteristic_type == 'group'){
			//php switch statement is considered a loop and continue inside it wil not produce the expected result here.
			continue;
		}

		switch($characteristic_type) {
			case 'text':
			case 'textarea':
				$column_type = "text NOT NULL DEFAULT ''";
				break;

			case 'date':
				$column_type = 'datetime';
				break;

			case 'boolean':
				$column_type = 'tinyint(1) NOT NULL DEFAULT 0';
				break;
		}
		
		if (($sql = "SHOW COLUMNS IN `". DB_NAME_PREFIX. "users_custom_data` LIKE '". sqlEscape($characteristic_name). "'")
		&& ($result2 = sqlQuery($sql))
		&& (sqlFetchRow($result2))) continue;
			
		$sql = "ALTER TABLE " . DB_NAME_PREFIX . "users_custom_data ADD `$characteristic_name` $column_type";
		$results = sqlSelect($sql);
			
		if($characteristic_type == 'boolean') {
			//boolean values are true if they exist in characteristic_user_link
			$sql = "UPDATE " . DB_NAME_PREFIX . "users_custom_data As a 
				SET a.`$characteristic_name`=IFNULL(
					(SELECT 1 FROM " . DB_NAME_PREFIX . "characteristic_user_link as cul
					WHERE cul.user_id=a.user_id AND cul.characteristic_id=$characteristic_id
					), 0)";
		
		} else {
			//text and date values
			$sql = "UPDATE " . DB_NAME_PREFIX . "users_custom_data As a SET a.`$characteristic_name`=(
				SELECT ucv.name FROM " . DB_NAME_PREFIX . "user_characteristic_values AS ucv,"
				. DB_NAME_PREFIX . "characteristic_user_link as cul
				WHERE cul.user_id=a.user_id AND cul.characteristic_id=$characteristic_id
				AND ucv.id = cul.characteristic_value_id)";				
		}
		sqlQuery($sql);

		//remove it now
		if($characteristic_type != 'boolean') {
			$sql = "DELETE FROM " . DB_NAME_PREFIX . "user_characteristic_values
			WHERE id IN(
				SELECT cul.characteristic_value_id FROM " . DB_NAME_PREFIX
				. "characteristic_user_link as cul
				WHERE cul.characteristic_id=$characteristic_id)";
			sqlQuery($sql);
		}
	}
	
	
	//other one to one characteristics "list_single_select"
	$sql = "SELECT id, name FROM " . DB_NAME_PREFIX . "user_characteristics
		WHERE type = 'list_single_select' AND NOT is_system_field";
	$result = sqlQuery($sql);
	while($row = sqlFetchRow($result)) {
		$characteristic_id = $row[0];
		$characteristic_name = $row[1];
		
		if($characteristic_name == 'address_pref') {
			//we already dealt with it above
			continue;
		}
		
		$sql = "ALTER TABLE " . DB_NAME_PREFIX . "users_custom_data ADD `$characteristic_name` int(10) NOT NULL DEFAULT 0";
		sqlQuery($sql);
		
		$sql = "UPDATE " . DB_NAME_PREFIX . "users_custom_data As a SET a.`$characteristic_name`=(
			SELECT cul.characteristic_value_id FROM " . DB_NAME_PREFIX
			. "characteristic_user_link as cul
			WHERE cul.user_id=a.user_id AND cul.characteristic_id=$characteristic_id)";
		sqlQuery($sql);			
	}

	
	//one to many characteristics
	$sql = "INSERT INTO " . DB_NAME_PREFIX . "user_characteristic_values_link(user_id, user_characteristic_value_id)
			SELECT cul.user_id, cul.characteristic_value_id 
			FROM " . DB_NAME_PREFIX . "characteristic_user_link AS cul
				INNER JOIN " . DB_NAME_PREFIX . "user_characteristics uc
						ON cul.characteristic_id = uc.id AND uc.type = 'list_multi_select'";
	sqlQuery($sql);

	revision(23265);
}

revision(23266

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` MODIFY COLUMN `type` enum('list_single_select','list_multi_select','boolean','date','text','textarea', 'group', 'integer', 'float', 'country') NOT NULL;
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_characteristics` SET `type` = 'country' 
	WHERE name IN('bus_country_id', 'res_country_id');
_sql

);

revision(23331

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` ADD COLUMN `show_in_organizer_panel` tinyint(1) NOT NULL DEFAULT 1
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` ADD COLUMN `organizer_allow_sort` tinyint(1) NOT NULL DEFAULT 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` ADD COLUMN `admin_box_text_field_width` integer NOT NULL DEFAULT 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` ADD COLUMN `admin_box_text_field_rows` integer NOT NULL DEFAULT 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` ADD COLUMN `admin_box_display_columns` integer NOT NULL DEFAULT 0
_sql

);

revision(23332

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]group_content_link` gcl 
		SET gcl.group_id= (SELECT user_characteristic_id FROM `[[DB_NAME_PREFIX]]groups` g WHERE g.id=gcl.group_id)
_sql

);

revision(23333

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields` 
		DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields` 
		ADD COLUMN `id` int(10) unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL FIRST
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
		ADD UNIQUE KEY(user_form_id, user_field_id)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields` ADD COLUMN `is_readonly` tinyint(1) NOT NULL DEFAULT 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields` ADD COLUMN `is_required` tinyint(1) NOT NULL DEFAULT 0
_sql

);

revision(23334

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_characteristics` AS gc SET gc.admin_box_tab_id = 0, gc.`group_id`= (
		SELECT g.user_characteristic_id FROM `[[DB_NAME_PREFIX]]groups` g
		WHERE g.id=gc.group_id)
	WHERE gc.group_id > 0
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_characteristics` SET protected = 0
_sql

);

//convert the groups on membership plans
if (needRevision(23335)) {
	$sql = "SHOW TABLES LIKE '" . DB_NAME_PREFIX . "%temporary_membership_plans'";
	$result = sqlQuery($sql);
	while($row = sqlFetchRow($result)) {
		//table exists
		$sql = "UPDATE {$row[0]} as tmp SET tmp.group_id=(
				SELECT g.user_characteristic_id FROM " . DB_NAME_PREFIX . "groups g
				WHERE g.id = tmp.group_id
			)";
		sqlQuery($sql);
	};

	revision(23335);
}

revision(23336

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]user_characteristics`(`name`, `label`, `ordinal`, `type`, `is_system_field`, `admin_box_tab_id`)
	VALUES
			('email_verified', 'Email verified:', 16, 'boolean', true, 1);
_sql

);


revision(23641

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` ADD COLUMN `user_access` enum('user_readable','user_writeable')
_sql

);


revision(23642

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]users`
	SET screen_name = CONCAT('user_', id)
	WHERE screen_name = ''
_sql

);

revision(23645
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]user_characteristics` WHERE name = 'image_id'
_sql

);

revision(24340
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` MODIFY COLUMN `type` enum('list_single_select','list_multi_select','boolean','date','text','textarea', 'group', 'integer', 'float', 'country', 'url') NOT NULL
_sql

);

revision(24341
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users` ADD COLUMN `creation_method` enum('visitor','admin') NULL
_sql

);

revision(24684
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_characteristic_values` SET `label`='Contact only', ordinal=1,
			help_text='A user who cannot log in (for data capture or mailing purpose only)' 
	WHERE characteristic_id = (SELECT id FROM `[[DB_NAME_PREFIX]]user_characteristics` WHERE name = 'status') AND `name`='contact'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_characteristic_values` SET `label`='Pending extranet user', ordinal=2,
			help_text='A user who needs activation by an administrator before logging in' 
	WHERE characteristic_id = (SELECT id FROM `[[DB_NAME_PREFIX]]user_characteristics` WHERE name = 'status') AND `name`='pending'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_characteristic_values` SET `label`='Active extranet user', ordinal=3, 
			help_text='A user who can immediately log in to the site\'s extranet area' 
	WHERE characteristic_id = (SELECT id FROM `[[DB_NAME_PREFIX]]user_characteristics` WHERE name = 'status') AND `name`='active'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_characteristic_values` SET `label`='Suspended extranet user', ordinal=4, 
			help_text='A user who has previously been active and whose login ability is disabled' 
	WHERE characteristic_id = (SELECT id FROM `[[DB_NAME_PREFIX]]user_characteristics` WHERE name = 'status') AND `name`='suspended'
_sql


);

revision(24687
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics` CHANGE `group_id` `parent_id` int(10) unsigned NOT NULL default 0
_sql
);

revision(25632
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users` ADD COLUMN `ordinal` int(10) NOT NULL default 0
_sql

);

revision(25926
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_signin_log`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]user_signin_log` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`user_id` int(10) unsigned NOT NULL,
		`screen_name` varchar(50) DEFAULT '',
		`first_name` varchar(100) NOT NULL DEFAULT '',
		`last_name` varchar(100) NOT NULL DEFAULT '',
		`email` varchar(100) NOT NULL DEFAULT '',
		`login_datetime` datetime DEFAULT NULL,
		`ip` varchar(80) CHARACTER SET ascii NOT NULL DEFAULT '',
		`browser` varchar(255) NOT NULL DEFAULT '',
		`browser_version` varchar(255) NOT NULL DEFAULT '',
		`platform` varchar(255) NOT NULL DEFAULT '',
		KEY (`user_id`),
		KEY (`screen_name`),
		KEY (`email`),
		KEY (`login_datetime`),
		KEY (`ip`),
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Create tables for custom datasets
); revision( 26740
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]custom_datasets`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]custom_datasets` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`label` varchar(64) NOT NULL,
		`system_table` varchar(255) CHARACTER SET ascii NOT NULL default '',
		`table` varchar(255) CHARACTER SET ascii NOT NULL,
		`extends_admin_box` varchar(255) CHARACTER SET ascii NOT NULL default '',
		`extends_organizer_panel` varchar(255) CHARACTER SET ascii NOT NULL default '',
		PRIMARY KEY (`id`),
		UNIQUE KEY (`table`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]custom_dataset_tabs`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]custom_dataset_tabs` (
		`dataset_id` int(10) unsigned NOT NULL,
		`name` varchar(16) CHARACTER SET ascii NOT NULL,
		`ord` int(10) unsigned NOT NULL default 0,
		`label` varchar(32) NOT NULL default '',
		PRIMARY KEY (`dataset_id`, `name`),
		KEY (`ord`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]custom_dataset_fields`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`dataset_id` int(10) unsigned NOT NULL,
		`tab_name` varchar(16) CHARACTER SET ascii NOT NULL,
		`parent_id` int(10) unsigned NOT NULL default 0,
		
		`is_system_field` tinyint(1) NOT NULL default 0,
		`field_name` varchar(64) CHARACTER SET ascii NOT NULL default '',
		
		`ord` int(10) unsigned NOT NULL default 0,
		`label` varchar(64) NOT NULL default '',
		
		`type` enum('group', 'checkbox', 'checkboxes', 'date', 'editor', 'radios', 'centralised_radios', 'select', 'centralised_select', 'text', 'textarea', 'url', 'other_system_field') NOT NULL default 'other_system_field',
		`width` smallint(5) unsigned NOT NULL default 0,
		`height` smallint(5) unsigned NOT NULL default 0,
		`values_source` varchar(255) NOT NULL default '',
		
		`required` tinyint(1) NOT NULL default 0,
		`required_message` TEXT,
		`validation` enum('none', 'email', 'emails', 'no_spaces', 'numeric', 'screen_name') NOT NULL default 'none',
		`validation_message` TEXT,
		`note_below` TEXT,
		
		`db_column` varchar(255) NOT NULL default '',
		`show_in_organizer` tinyint(1) NOT NULL default 0,
		`searchable` tinyint(1) NOT NULL default 0,
		`sortable` tinyint(1) NOT NULL default 0,
		`show_by_default` tinyint(1) NOT NULL default 0,
		`always_show` tinyint(1) NOT NULL default 0,
		
		PRIMARY KEY (`id`),
		UNIQUE KEY (`dataset_id`, `tab_name`, `id`),
		KEY (`dataset_id`, `tab_name`, `ord`),
		KEY (`show_in_organizer`),
		KEY (`is_system_field`),
		KEY (`parent_id`),
		KEY (`field_name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]custom_dataset_system_fields`
_sql

,/* <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]custom_dataset_system_fields` (
		`dataset_id` int(10) unsigned NOT NULL,
		`tab_name` varchar(16) CHARACTER SET ascii NOT NULL,
		`field_name` varchar(64) CHARACTER SET ascii NOT NULL,
		
		`ord` int(10) unsigned NOT NULL default 0,
		`label` varchar(64) NOT NULL default '',
		`note_below` TEXT,
		
		PRIMARY KEY (`dataset_id`, `tab_name`, `field_name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

,*/ <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]custom_dataset_field_values`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]custom_dataset_field_values` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`field_id` int(10) NOT NULL,
		`ord` int(10) NOT NULL default 0,
		`label` varchar(255) NOT NULL,
		`note_below` TEXT,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`field_id`, `id`),
		KEY (`field_id`, `ord`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]custom_dataset_values_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]custom_dataset_values_link` (
		`dataset_id` int(10) NOT NULL,
		`value_id` int(10) NOT NULL,
		`linking_id` int(10) NOT NULL,
		PRIMARY KEY (`value_id`, `linking_id`),
		UNIQUE KEY (`linking_id`, `value_id`),
		KEY (`dataset_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Migrate user data to the new custom format

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_datasets` DISABLE KEYS
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_tabs` DISABLE KEYS
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields` DISABLE KEYS
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_field_values` DISABLE KEYS
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_values_link` DISABLE KEYS
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]custom_datasets` VALUES(
		1,
		'Users',
		'users',
		'users_custom_data',
		'zenario_user__details',
		'zenario__users/nav/users/panel'
	)
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]custom_dataset_tabs`
	SELECT
		1,
		IF (id = 1, 'details', CONCAT('__custom_tab_', id)),
		ordinal,
		label
	FROM `[[DB_NAME_PREFIX]]user_admin_box_tabs`
	WHERE is_system_field = 0
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]custom_dataset_fields`
	SELECT
		id,
		1,
		IF (admin_box_tab_id = 1, 'details', CONCAT('__custom_tab_', admin_box_tab_id)),
		parent_id,
		
		0,
		'',
		
		ordinal,
		label,
		
		IF (type = 'list_single_select', 'radios',
		IF (type = 'list_multi_select', 'checkboxes',
		IF (type = 'boolean', 'checkbox',
		IF (type = 'date', 'date',
		IF (type = 'text', 'text',
		IF (type = 'textarea', 'textarea',
		IF (type = 'group', 'group',
		IF (type = 'integer', 'text',
		IF (type = 'float', 'text',
		IF (type = 'country', 'centralised_select',
		IF (type = 'url', 'url', 'text'
		))))))))))),
		
		admin_box_text_field_width,
		admin_box_text_field_rows,
		
		IF (type = 'country', 'zenario_country_manager::getActiveCountries', ''),
		
		0,
		'',
		
		IF (type = 'integer', 'numeric',
		IF (type = 'float', 'numeric', 'none'
		)),
		IF (type = 'integer', CONCAT(REPLACE(label, ':', ''), ' must be a number'),
		IF (type = 'float', CONCAT(REPLACE(label, ':', ''), ' must be a number'), NULL
		)),
		
		help_text,
		name,
		show_in_organizer_panel,
		organizer_allow_sort,
		organizer_allow_sort,
		1,
		0
	FROM `[[DB_NAME_PREFIX]]user_characteristics`
	WHERE is_system_field = 0
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]custom_dataset_fields` (
		`id`,
		`dataset_id`,
		`tab_name`,
		
		`is_system_field`,
		`field_name`,
		
		`ord`,
		`label`,
		
		`type`,
		`db_column`,
		`values_source`
	) SELECT
		id,
		1,
		'details',
		
		1,
		name,
		
		ordinal,
		label,
		
		IF (name = 'status', 'centralised_radios', IF (name = 'email_verified', 'checkbox', 'text')),
		name,
		IF (name = 'status', 'zenario_common_features::userStatus', '')
		
		
	FROM `[[DB_NAME_PREFIX]]user_characteristics`
	WHERE is_system_field = 1
	  AND name IN ('email', 'salutation', 'first_name', 'last_name', 'screen_name', 'email_verified', 'status')
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]custom_dataset_field_values`
	SELECT
		ucv.id,
		uc.id,
		ucv.ordinal,
		ucv.label,
		ucv.help_text
	FROM `[[DB_NAME_PREFIX]]user_characteristic_values` AS ucv
	INNER JOIN `[[DB_NAME_PREFIX]]user_characteristics` AS uc
	ON ucv.characteristic_id = uc.id
	WHERE is_system_field = 0
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]custom_dataset_values_link`
	SELECT
		1,
		user_characteristic_value_id,
		user_id
	FROM `[[DB_NAME_PREFIX]]user_characteristic_values_link` AS ucv
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_datasets` ENABLE KEYS
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_tabs` ENABLE KEYS
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields` ENABLE KEYS
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_field_values` ENABLE KEYS
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_values_link` ENABLE KEYS
_sql

//Automatically delete any empty tabs
, <<<_sql
	DELETE cdt.*
	FROM `[[DB_NAME_PREFIX]]custom_dataset_tabs` AS cdt
	LEFT JOIN `[[DB_NAME_PREFIX]]custom_dataset_fields` AS cdf
	   ON cdf.dataset_id = cdt.dataset_id
	  AND cdf.tab_name = cdt.name
	WHERE cdf.tab_name IS NULL
_sql

//Increase the length of the ip column on the users table
);	revision(26790
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	MODIFY COLUMN `ip` varchar(255) NOT NULL default ''
_sql


);	revision(26820
//Automatically create any missing tabs
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]custom_dataset_tabs`
	SELECT dataset_id, tab_name, 101, REPLACE(label, ':', '')
	FROM `[[DB_NAME_PREFIX]]custom_dataset_fields`
	WHERE tab_name LIKE '__custom_tab_%'
	ORDER BY ord
_sql

//Add a parent_id column to the tabs table, so tabs can be hidden or shown
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_tabs`
	ADD COLUMN `parent_field_id` int(10) NOT NULL default 0
	AFTER `label`
_sql


);	revision(26850
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_datasets`
	ADD COLUMN `status` enum('active', 'disabled') NOT NULL default 'active'
	AFTER `label`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_datasets`
	ADD KEY (`status`)
_sql


);	revision(26910
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `password_needs_changing` tinyint(1) NOT NULL default 0
	AFTER `password`
_sql


//Drop the status column as it wasn't wanted
);	revision(26940
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_datasets`
	DROP COLUMN `status`
_sql


);	revision(26980
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `protected` tinyint(1) NOT NULL default 0
	AFTER `is_system_field`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD KEY (`protected`)
_sql


);	revision(26985
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_datasets`
	ADD COLUMN `view_priv` varchar(255) CHARACTER SET ascii NOT NULL default ''
	AFTER `extends_organizer_panel`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_datasets`
	ADD COLUMN `edit_priv` varchar(255) CHARACTER SET ascii NOT NULL default ''
	AFTER `view_priv`
_sql


);	revision(27340
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `global_id` int(10) unsigned NOT NULL default 0
	AFTER `id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD KEY (`global_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `last_updated_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
    ON UPDATE CURRENT_TIMESTAMP
	AFTER `suspended_date`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD KEY (`last_updated_timestamp`)
_sql

); revision (27342

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `send_email_to_user` tinyint(1) NOT NULL default 0,
	ADD COLUMN `user_email_field` varchar(255) DEFAULT NULL,
	ADD COLUMN `user_email_template` int(10) unsigned DEFAULT NULL,
	ADD COLUMN `send_email_to_admin` tinyint(1) NOT NULL default 0,
	ADD COLUMN `admin_email_addresses` text DEFAULT NULL,
	ADD COLUMN `admin_email_template` int(10) unsigned DEFAULT NULL,
	ADD COLUMN `admin_reply_to` tinyint(1) NOT NULL default 0
_sql

); revision (27343

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `save_data` tinyint(1) NOT NULL default 0,
	ADD COLUMN `send_signal` tinyint(1) NOT NULL default 0,
	ADD COLUMN `signal_name` varchar(255) DEFAULT NULL,
	ADD COLUMN `redirect_after_submission` tinyint(1) NOT NULL default 0,
	ADD COLUMN `redirect_location` varchar(255) DEFAULT NULL
_sql

); revision (27344

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	CHANGE `user_email_template` `user_email_template` varchar(255) DEFAULT NULL,
	CHANGE `admin_email_template` `admin_email_template` varchar(255) DEFAULT NULL
_sql

); revision (27345

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `reply_to_email_field` varchar(255) DEFAULT NULL,
	ADD COLUMN `reply_to_first_name` varchar(255) DEFAULT NULL,
	ADD COLUMN `reply_to_last_name` varchar(255) DEFAULT NULL,
	CHANGE `admin_reply_to` `reply_to` tinyint(1) NOT NULL default 0
_sql

); revision (27346

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	ADD COLUMN `label` varchar(255) DEFAULT NULL,
	ADD COLUMN `size` enum('small', 'medium', 'large'),
	ADD COLUMN `default_value` varchar(255) DEFAULT NULL,
	ADD COLUMN `note_to_user` varchar(255) DEFAULT NULL,
	ADD COLUMN `css_classes` varchar(255) DEFAULT NULL,
	ADD COLUMN `required_error_message` varchar(255) DEFAULT NULL,
	ADD COLUMN `validation` enum('email', 'URL', 'integer', 'number', 'floating_point')
_sql

); revision (27347

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	CHANGE `size` `size` enum('small', 'medium', 'large') DEFAULT 'medium'
_sql

); revision (27348

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	ADD COLUMN `validation_error_message` varchar(255) DEFAULT NULL
_sql

); revision (27349

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `save_record` tinyint(1) NOT NULL default 0 AFTER `save_data`
_sql

); revision (27350

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	CHANGE `save_data` `save_data` tinyint(1) NOT NULL default 1
_sql

); revision (27351

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `create_new_user` tinyint(1) NOT NULL default 0,
	ADD COLUMN `add_user_to_group` int(10) unsigned DEFAULT NULL,
	ADD COLUMN `user_status` enum('active', 'contact') DEFAULT NULL,
	ADD COLUMN `on_duplicate_send_email` tinyint(1) NOT NULL default 0,
	ADD COLUMN `on_duplicate_update_user` tinyint(1) NOT NULL default 0,
	ADD COLUMN `on_duplicate_update_response` tinyint(1) NOT NULL default 0
_sql

); revision (27352

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `show_success_messsage` tinyint(1) NOT NULL default 0,
	ADD COLUMN `success_message` varchar(255) DEFAULT NULL,
	DROP COLUMN `signal_name`
_sql

); revision (27353

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	CHANGE `show_success_messsage` `show_success_message` tinyint(1) NOT NULL default 0
_sql

); revision (27354

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	CHANGE `user_status` `user_status` enum('active', 'contact') DEFAULT 'contact'
_sql

); revision (27355

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	CHANGE `admin_email_addresses` `admin_email_addresses` varchar(255) DEFAULT NULL
_sql

//Create a table to store the ids of users who have been synced to "spoke" sites
);	revision(27490
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_sync_log`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]user_sync_log` (
		`user_id` int(10) unsigned NOT NULL,
		`last_synced_timestamp` timestamp NOT NULL,
		PRIMARY KEY (`user_id`),
		KEY (`last_synced_timestamp`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision(27491

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN duplicate_action enum('merge', 'overwrite', 'ignore') DEFAULT 'merge'
_sql

); revision(27492

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	CHANGE duplicate_action user_duplicate_email_action enum('merge', 'overwrite', 'ignore') DEFAULT 'merge'
_sql

); revision(27493

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN duplicate_user_email tinyint(1) NOT NULL default 0 AFTER success_message
_sql

); revision(27494

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	DROP `on_duplicate_send_email`,
	DROP `on_duplicate_update_user`,
	DROP `on_duplicate_update_response`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms`
	ADD COLUMN `create_another_form_submission_record` tinyint(1) NOT NULL default 0,
	ADD COLUMN `send_another_email` tinyint(1) NOT NULL default 0
_sql

); revision(28052

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_forms` 
	ADD COLUMN `admin_email_use_template` tinyint(1) NOT NULL default 0 AFTER send_email_to_admin
_sql

); revision(28651

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `terms_and_conditions_accepted` tinyint(1) NOT NULL DEFAULT '0'
	AFTER `last_updated_timestamp`
_sql

);