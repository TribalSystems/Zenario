<?php

revision( 10

, <<<_sql
	TRUNCATE `[[DB_NAME_PREFIX]]user_forms`;
_sql
, <<<_sql
	TRUNCATE `[[DB_NAME_PREFIX]]user_form_fields`;
_sql

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

// Make an initial sample form
if (needRevision(19)) {
	if (!checkRowExists('user_forms', array('name' => 'Simple contact form'))) {
		// Create form
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
			'ord' => 1,
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
			'ord' => 2,
			'is_required' => 1,
			'label' => 'First name',
			'name' => 'First name',
			'required_error_message' => 'Please enter your first name'
		));
		$lastNameFieldId = getRow('custom_dataset_fields', 'id', array('field_name' => 'last_name', 'is_system_field' => 1));
		insertRow('user_form_fields', array(
			'user_form_id' => $userFormId,
			'user_field_id' => $lastNameFieldId,
			'ord' => 3,
			'is_required' => 1,
			'label' => 'Last name',
			'name' => 'Last name',
			'required_error_message' => 'Please enter your last name'
		));
	}
	revision(19);
}

revision (22

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response`
	ADD COLUMN `crm_response` text DEFAULT NULL
_sql


); revision (25

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_update_link`
_sql

,<<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_update_link`(
		`source_field_id` int(10) unsigned NOT NULL,
		`target_field_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`source_field_id`, `target_field_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision( 26

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response`
	ADD COLUMN blocked_by_profanity_filter BOOLEAN NOT NULL DEFAULT 0
_sql

); revision( 27

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response`
	ADD COLUMN profanity_filter_score INT NOT NULL DEFAULT 0,
	ADD COLUMN profanity_tolerance_limit INT NOT NULL DEFAULT 0
_sql

);

// Create a registration user form on install
if (needRevision(29)) {
	if (!checkRowExists('user_forms', array('name' => 'Extranet registration form'))) {
		
		// Create form
		$formId = insertRow('user_forms', array(
			'name' => 'Extranet registration form',
			'title' => 'Create an account',
			'type' => 'registration',
			'submit_button_text' => 'Register'
		));
		
		$dataset = getDatasetDetails('users');
		
		// Create form fields
		$emailField = getDatasetFieldDetails('email', $dataset);
		insertRow(
			'user_form_fields', 
			array(
				'user_form_id' => $formId,
				'user_field_id' => $emailField['id'],
				'ord' => 1,
				'is_required' => 1,
				'label' => 'Email:',
				'name' => 'Email',
				'required_error_message' => 'Please enter your email address',
				'validation' => 'email',
				'validation_error_message' => 'Please enter a valid email address'
			)
		);
		
		$salutationField = getDatasetFieldDetails('salutation', $dataset);
		insertRow(
			'user_form_fields', 
			array(
				'user_form_id' => $formId,
				'user_field_id' => $salutationField['id'],
				'ord' => 2,
				'label' => 'Salutation:',
				'name' => 'Salutation'
			)
		);
		
		$firstNameField = getDatasetFieldDetails('first_name', $dataset);
		insertRow(
			'user_form_fields', 
			array(
				'user_form_id' => $formId,
				'user_field_id' => $firstNameField['id'],
				'ord' => 3,
				'is_required' => 1,
				'label' => 'First name:',
				'name' => 'First name',
				'required_error_message' => 'Please enter your first name'
			)
		);
		
		$lastNameField = getDatasetFieldDetails('last_name', $dataset);
		insertRow(
			'user_form_fields', 
			array(
				'user_form_id' => $formId,
				'user_field_id' => $lastNameField['id'],
				'ord' => 3,
				'is_required' => 1,
				'label' => 'Last name:',
				'name' => 'Last name',
				'required_error_message' => 'Please enter your last name'
			)
		);
	}
	revision(29);
}

revision(30
,<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_form_fields`
	MODIFY COLUMN description text
_sql

);