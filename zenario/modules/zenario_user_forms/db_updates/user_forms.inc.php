<?php

//Create the user_forms and user_form_fields tables.
//Note that the installer may have already created these, because of a complicated script for migrating old sites,
//but if this is a fresh install of the User Forms module then we still want to drop and recreate fresh empty tables anyway.
revision( 1
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
_sql
, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		`type` enum('standard','profile','registration') NOT NULL DEFAULT 'standard',
		`status` enum('active','archived') DEFAULT 'active',
		`send_email_to_user` tinyint(1) NOT NULL DEFAULT '0',
		`user_email_template` varchar(255) DEFAULT NULL,
		`send_email_to_admin` tinyint(1) NOT NULL DEFAULT '0',
		`admin_email_use_template` tinyint(1) NOT NULL DEFAULT '0',
		`admin_email_addresses` varchar(255) DEFAULT NULL,
		`admin_email_template` varchar(255) DEFAULT NULL,
		`reply_to` tinyint(1) NOT NULL DEFAULT '0',
		`save_data` tinyint(1) NOT NULL DEFAULT '1',
		`save_record` tinyint(1) NOT NULL DEFAULT '0',
		`send_signal` tinyint(1) NOT NULL DEFAULT '0',
		`redirect_after_submission` tinyint(1) NOT NULL DEFAULT '0',
		`redirect_location` varchar(255) DEFAULT NULL,
		`reply_to_email_field` varchar(255) DEFAULT NULL,
		`reply_to_first_name` varchar(255) DEFAULT NULL,
		`reply_to_last_name` varchar(255) DEFAULT NULL,
		`add_user_to_group` int(10) unsigned DEFAULT NULL,
		`user_status` enum('active','contact') DEFAULT 'contact',
		`log_user_in` tinyint(1) NOT NULL DEFAULT '0',
		`show_success_message` tinyint(1) NOT NULL DEFAULT '0',
		`success_message` varchar(255) DEFAULT NULL,
		`user_duplicate_email_action` enum('merge','overwrite','ignore','stop') DEFAULT 'merge',
		`create_another_form_submission_record` tinyint(1) NOT NULL DEFAULT '0',
		`title` varchar(255) DEFAULT '',
		`title_tag` enum('h1','h2','h3','h4','h5','h6','p') DEFAULT 'h2',
		`use_captcha` tinyint(1) NOT NULL DEFAULT '0',
		`captcha_type` enum('word','math','pictures') DEFAULT 'word',
		`extranet_users_use_captcha` tinyint(1) NOT NULL DEFAULT '0',
		`translate_text` tinyint(1) NOT NULL DEFAULT '1',
		`submit_button_text` varchar(255) DEFAULT 'Submit',
		`default_next_button_text` varchar(255) DEFAULT 'Next',
		`default_previous_button_text` varchar(255) DEFAULT 'Back',
		`log_user_in_cookie` tinyint(1) NOT NULL DEFAULT '0',
		`duplicate_email_address_error_message` varchar(255) DEFAULT NULL,
		`profanity_filter_text` tinyint(1) NOT NULL DEFAULT '0',
		`allow_partial_completion` tinyint(1) NOT NULL DEFAULT '0',
		`partial_completion_message` varchar(255) DEFAULT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `name` (`name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
_sql
, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`user_form_id` int(10) unsigned NOT NULL,
		`user_field_id` int(10) unsigned DEFAULT '0',
		`ord` int(10) unsigned NOT NULL DEFAULT '0',
		`field_type` enum('checkbox','checkboxes','date','editor','radios','centralised_radios','select','centralised_select','text','textarea','url','attachment','page_break','section_description','calculated','restatement','repeat_start','repeat_end') DEFAULT NULL,
		`protected` tinyint(1) NOT NULL DEFAULT '0',
		`is_readonly` tinyint(1) NOT NULL DEFAULT '0',
		`is_required` tinyint(1) NOT NULL DEFAULT '0',
		`mandatory_condition_field_id` int(10) unsigned DEFAULT '0',
		`mandatory_condition_field_value` varchar(255) DEFAULT NULL,
		`visibility` enum('visible','hidden','visible_on_condition') DEFAULT 'visible',
		`visible_condition_field_id` int(10) unsigned DEFAULT '0',
		`visible_condition_field_value` varchar(255) DEFAULT NULL,
		`name` varchar(255) NOT NULL,
		`custom_code_name` varchar(255) DEFAULT NULL,
		`label` varchar(255) DEFAULT NULL,
		`size` enum('small','medium','large') DEFAULT 'medium',
		`placeholder` varchar(255) DEFAULT NULL,
		`default_value` varchar(255) DEFAULT NULL,
		`default_value_class_name` varchar(255) DEFAULT NULL,
		`default_value_method_name` varchar(255) DEFAULT NULL,
		`default_value_param_1` varchar(255) DEFAULT NULL,
		`default_value_param_2` varchar(255) DEFAULT NULL,
		`note_to_user` varchar(255) DEFAULT NULL,
		`css_classes` varchar(255) DEFAULT NULL,
		`div_wrap_class` varchar(255) DEFAULT NULL,
		`required_error_message` varchar(255) DEFAULT NULL,
		`validation` enum('email','URL','integer','number','floating_point') DEFAULT NULL,
		`validation_error_message` varchar(255) DEFAULT NULL,
		`next_button_text` varchar(255) DEFAULT NULL,
		`previous_button_text` varchar(255) DEFAULT NULL,
		`description` text,
		`numeric_field_1` int(10) unsigned DEFAULT '0',
		`numeric_field_2` int(10) unsigned DEFAULT '0',
		`calculation_type` enum('+','-') DEFAULT NULL,
		`restatement_field` int(10) unsigned DEFAULT '0',
		`values_source` varchar(255) NOT NULL DEFAULT '',
		`values_source_filter` varchar(255) NOT NULL DEFAULT '',
		`show_repeat_field` tinyint(1) NOT NULL DEFAULT '0',
		`repeat_field_label` varchar(255) DEFAULT NULL,
		`repeat_error_message` varchar(255) DEFAULT NULL,
		`autocomplete_class_name` varchar(255) DEFAULT NULL,
		`autocomplete_method_name` varchar(255) DEFAULT NULL,
		`autocomplete_param_1` varchar(255) DEFAULT NULL,
		`autocomplete_param_2` varchar(255) DEFAULT NULL,
		`autocomplete` tinyint(1) NOT NULL DEFAULT '0',
		`autocomplete_no_filter_placeholder` varchar(255) DEFAULT NULL,
		`value_field_columns` int(10) unsigned NOT NULL DEFAULT '0',
		`min_rows` int(10) unsigned NOT NULL DEFAULT '0',
		`max_rows` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision( 10
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
	if (!checkRowExists(ZENARIO_USER_FORMS_PREFIX. 'user_forms', array('name' => 'Simple contact form'))) {
		// Create form
		$userFormId = insertRow(ZENARIO_USER_FORMS_PREFIX. 'user_forms', array(
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
		insertRow(ZENARIO_USER_FORMS_PREFIX. 'user_form_fields', array(
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
		insertRow(ZENARIO_USER_FORMS_PREFIX. 'user_form_fields', array(
			'user_form_id' => $userFormId,
			'user_field_id' => $firstNameFieldId,
			'ord' => 2,
			'is_required' => 1,
			'label' => 'First name',
			'name' => 'First name',
			'required_error_message' => 'Please enter your first name'
		));
		$lastNameFieldId = getRow('custom_dataset_fields', 'id', array('field_name' => 'last_name', 'is_system_field' => 1));
		insertRow(ZENARIO_USER_FORMS_PREFIX. 'user_form_fields', array(
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
	if (!checkRowExists(ZENARIO_USER_FORMS_PREFIX. 'user_forms', array('name' => 'Extranet registration form'))) {
		
		// Create form
		$formId = insertRow(ZENARIO_USER_FORMS_PREFIX. 'user_forms', array(
			'name' => 'Extranet registration form',
			'title' => 'Create an account',
			'type' => 'registration',
			'submit_button_text' => 'Register'
		));
		
		$dataset = getDatasetDetails('users');
		
		// Create form fields
		$emailField = getDatasetFieldDetails('email', $dataset);
		insertRow(
			ZENARIO_USER_FORMS_PREFIX. 'user_form_fields', 
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
			ZENARIO_USER_FORMS_PREFIX. 'user_form_fields', 
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
			ZENARIO_USER_FORMS_PREFIX. 'user_form_fields', 
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
			ZENARIO_USER_FORMS_PREFIX. 'user_form_fields', 
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

revision(33
// Create tables for partial form responses
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response`
_sql

,<<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response`(
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`user_id` int(10) unsigned NOT NULL,
		`form_id` int(10) unsigned NOT NULL,
		`response_datetime` datetime NOT NULL,
		PRIMARY KEY (`id`),
		KEY (`user_id`),
		KEY (`form_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response_data`
_sql

,<<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response_data`(
		`user_partial_response_id` int(10) unsigned NOT NULL,
		`form_field_id` int(10) unsigned NOT NULL,
		`value` text NOT NULL,
		PRIMARY KEY (`user_partial_response_id`, `form_field_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision(36
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response_data`
	ADD COLUMN `field_row` int(10) unsigned NOT NULL DEFAULT 0 AFTER `form_field_id`,
	DROP PRIMARY KEY,
	ADD PRIMARY KEY (`user_response_id`, `form_field_id`, `field_row`)
_sql

); revision(37
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response_data`
	ADD COLUMN `field_row` int(10) unsigned NOT NULL DEFAULT 0 AFTER `form_field_id`,
	DROP PRIMARY KEY,
	ADD PRIMARY KEY (`user_partial_response_id`, `form_field_id`, `field_row`)
_sql


); revision(48
// Create a table to store a calculated field's parameters
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]calculated_field_parameters`
_sql

); revision(49
// New column for calculated fields
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `calculation_code` text
_sql

); revision(50
// New column for calculated fields prefix and postfix
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `value_prefix` varchar(255) DEFAULT NULL,
	ADD COLUMN `value_postfix` varchar(255) DEFAULT NULL
_sql

);

// Migrate existing calculation fields to new system
if (needRevision(51)) {
	$result = getRows(
		ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', 
		array('id', 'numeric_field_1', 'numeric_field_2', 'calculation_type'),
		array('field_type' => 'calculated')
	);
	while ($row = sqlFetchAssoc($result)) {
		if ($row['numeric_field_1'] && $row['numeric_field_2'] && $row['calculation_type']) {
			$calculationCode = array();
			$calculationCode[] = array('type' => 'field', 'value' => $row['numeric_field_1']);
			$calculationCode[] = array('type' => ($row['calculation_type'] == '+') ? 'operation_addition' : 'operation_subtraction');
			$calculationCode[] = array('type' => 'field', 'value' => $row['numeric_field_2']);
			
			$calculationCode = json_encode($calculationCode);
			
			if ($calculationCode) {
				updateRow(
					ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', 
					array('calculation_code' => $calculationCode),
					array('id' => $row['id'])
				);
			}
			
		}
	}
	
	revision(51);
}

revision(52
// Delete old calculation field columns
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	DROP COLUMN `numeric_field_1`,
	DROP COLUMN `numeric_field_2`,
	DROP COLUMN `calculation_type`
_sql
);

// Migrate dataset field DB columns to form field IDs
if (needRevision(54)) {
	$forms = getRowsArray(ZENARIO_USER_FORMS_PREFIX . 'user_forms', true, array());
	foreach ($forms as $form) {
		$set = false;
		if ($form['reply_to_email_field']) {
			$sql = '
				SELECT f.id
				FROM ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields f
				INNER JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields c
					ON f.user_field_id = c.id
				WHERE c.db_column = "' . sqlEscape($form['reply_to_email_field']) . '"
				AND f.user_form_id = ' . (int)$form['id'];
			$result = sqlSelect($sql);
			$row = sqlFetchAssoc($result);
			if ($row) {
				$set = true;
				updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('reply_to_email_field' => $row['id']), $form['id']);
			}
		}
		if (!$set) {
			updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('reply_to_email_field' => 0), $form['id']);
		}
		
		$set = false;
		if ($form['reply_to_first_name']) {
			$sql = '
				SELECT f.id
				FROM ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields f
				INNER JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields c
					ON f.user_field_id = c.id
				WHERE c.db_column = "' . sqlEscape($form['reply_to_first_name']) . '"
				AND f.user_form_id = ' . (int)$form['id'];
			$result = sqlSelect($sql);
			$row = sqlFetchAssoc($result);
			if ($row) {
				$set = true;
				updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('reply_to_first_name' => $row['id']), $form['id']);
			}
		}
		if (!$set) {
			updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('reply_to_first_name' => 0), $form['id']);
		}
		
		$set = false;
		if ($form['reply_to_last_name']) {
			$sql = '
				SELECT f.id
				FROM ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields f
				INNER JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields c
					ON f.user_field_id = c.id
				WHERE c.db_column = "' . sqlEscape($form['reply_to_last_name']) . '"
				AND f.user_form_id = ' . (int)$form['id'];
			$result = sqlSelect($sql);
			$row = sqlFetchAssoc($result);
			if ($row) {
				$set = true;
				updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('reply_to_last_name' => $row['id']), $form['id']);
			}
		}
		if (!$set) {
			updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('reply_to_last_name' => 0), $form['id']);
		}
	}
	revision(54);
}

revision(55
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	MODIFY COLUMN `reply_to_email_field` int(10) unsigned NOT NULL DEFAULT 0,
	MODIFY COLUMN `reply_to_first_name` int(10) unsigned NOT NULL DEFAULT 0,
	MODIFY COLUMN `reply_to_last_name` int(10) unsigned NOT NULL DEFAULT 0
_sql

); revision(56
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `user_email_field` int(10) unsigned NOT NULL DEFAULT 0 AFTER `send_email_to_user`
_sql

); revision(58
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
    ADD COLUMN `use_honeypot` tinyint(1) NOT NULL DEFAULT '0',
    ADD COLUMN `honeypot_label` varchar(255) DEFAULT NULL
_sql

); revision(60
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
    ADD COLUMN `show_page_switcher` tinyint(1) NOT NULL DEFAULT '0',
    ADD COLUMN `page_switcher_navigation` enum('none', 'only_visited_pages') DEFAULT 'none',
    ADD COLUMN `hide_final_page_in_page_switcher` tinyint(1) NOT NULL DEFAULT '0'
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `hide_in_page_switcher` tinyint(1) NOT NULL DEFAULT '0'
_sql

); revision(61
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
    ADD COLUMN `partial_completion_mode` enum('auto', 'button') DEFAULT NULL AFTER `allow_partial_completion`
_sql

);

if (needRevision(62)) {
    updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('partial_completion_mode' => 'button'), array('allow_partial_completion' => 1));
    revision(62);
}

revision(63
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `page_end_name` varchar(255) DEFAULT 'Page end'
_sql

); revision(64
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `show_month_year_selectors` tinyint(1) NOT NULL DEFAULT '0'
_sql

//Allow a form to send multiple user emails
); revision(65
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `send_email_to_logged_in_user` tinyint(1) NOT NULL DEFAULT '0' AFTER `user_email_template`,
	ADD COLUMN `user_email_use_template_for_logged_in_user` tinyint(1) NOT NULL DEFAULT '0' AFTER `send_email_to_logged_in_user`,
	ADD COLUMN `user_email_template_logged_in_user` varchar(255) DEFAULT NULL AFTER `user_email_use_template_for_logged_in_user`,
	
	ADD COLUMN `send_email_to_email_from_field` tinyint(1) NOT NULL DEFAULT '0' AFTER `user_email_template`,
	ADD COLUMN `user_email_use_template_for_email_from_field` tinyint(1) NOT NULL DEFAULT '0' AFTER `send_email_to_logged_in_user`,
	ADD COLUMN `user_email_template_from_field` varchar(255) DEFAULT NULL AFTER `user_email_use_template_for_logged_in_user`,
	
	MODIFY COLUMN `user_email_field` int(10) unsigned NOT NULL DEFAULT '0' AFTER `send_email_to_email_from_field`
_sql

);
//Migrate form user email settings to new format
if (needRevision(66)) {
	
	$formsResult = getRows(ZENARIO_USER_FORMS_PREFIX . 'user_forms', true, array());
	while ($form = sqlFetchAssoc($formsResult)) {
		$details = array();
		if ($form['send_email_to_user'] && $form['user_email_field']) {
			$details['send_email_to_email_from_field'] = true;
			$details['user_email_use_template_for_email_from_field'] = true;
			$details['user_email_template_from_field'] = $form['user_email_template'];
		} elseif ($form['send_email_to_user'] && !$form['user_email_field']) {
			$details['send_email_to_logged_in_user'] = true;
			$details['user_email_use_template_for_logged_in_user'] = true;
			$details['user_email_template_logged_in_user'] = $form['user_email_template'];
		}
		if ($details) {
			updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $details, $form['id']);
		}
	}
	revision(66);
}
//Drop old columns
revision(67
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	DROP COLUMN `send_email_to_user`,
	DROP COLUMN `user_email_template`
_sql

);
