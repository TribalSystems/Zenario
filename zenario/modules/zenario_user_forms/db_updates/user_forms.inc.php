<?php

revision( 10
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response`
_sql

,<<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response`(
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`user_id` int(10) unsigned NOT NULL,
		`form_id` int(10) unsigned NOT NULL,
		`response_datetime` datetime NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response_data`
_sql

,<<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response_data`(
		`user_response_id` int(10) unsigned NOT NULL,
		`form_field_id` int(10) unsigned NOT NULL,
		`value` text NOT NULL,
		PRIMARY KEY (`user_response_id`, `form_field_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision (11

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response`
	ADD COLUMN `internal_value` varchar(255) DEFAULT NULL 
_sql

); revision (12

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response`
	DROP COLUMN `internal_value`
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response_data`
	ADD COLUMN `internal_value` varchar(255) DEFAULT NULL 
_sql

); revision (17

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_values`
_sql

,<<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_values`(
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`form_field_id` int(10) unsigned NOT NULL,
		`ord` int(10) unsigned NOT NULL,
		`label` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

);

if (needRevision(19)) {
	// Create form
	if (!checkRowExists('user_forms', array('name' => 'Simple contact form'))) {
		$userFormId = insertRow('user_forms', array(
			'name' => 'Simple contact form',
			'send_email_to_admin' => 1,
			'admin_email_addresses' => setting('email_address_admin'),
			'save_data' => 1,
			'save_record' => 1,
			'user_status' => 'contact',
			'show_success_message' => 1,
			'success_message' => 'Thank you!',
			'user_duplicate_email_action' => 'merge',
			'title' => 'Please enter your details'
		));
		
		// Create form fields
		$emailFieldId = getRow('custom_dataset_fields', 'id', array('field_name' => 'email', 'is_system_field' => 1));
		insertRow('user_form_fields', array(
			'user_form_id' => $userFormId,
			'user_field_id' => $emailFieldId,
			'ordinal' => 1,
			'is_required' => 1,
			'label' => 'Email',
			'name' => 'Email',
			'required_error_message' => 'Please enter your email address',
			'validation' => 'email',
			'validation_error_message' => 'Please enter a valid email address'
		));
		$firstNameFieldId = getRow('custom_dataset_fields', 'id', array('field_name' => 'first_name', 'is_system_field' => 1));
		insertRow('user_form_fields', array(
			'user_form_id' => $userFormId,
			'user_field_id' => $firstNameFieldId,
			'ordinal' => 2,
			'is_required' => 1,
			'label' => 'First name',
			'name' => 'First name',
			'required_error_message' => 'Please enter your first name'
		));
		$lastNameFieldId = getRow('custom_dataset_fields', 'id', array('field_name' => 'last_name', 'is_system_field' => 1));
		insertRow('user_form_fields', array(
			'user_form_id' => $userFormId,
			'user_field_id' => $lastNameFieldId,
			'ordinal' => 3,
			'is_required' => 1,
			'label' => 'Last name',
			'name' => 'Last name',
			'required_error_message' => 'Please enter your last name'
		));
	}
	revision(19);
}