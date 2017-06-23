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

); revision(68
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response`
	ADD COLUMN `max_page_reached` int(10) unsigned NOT NULL DEFAULT 1
_sql

); revision(69
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	MODIFY COLUMN `partial_completion_mode` enum('auto','button', 'auto_and_button') DEFAULT NULL
_sql

); revision(70
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	DROP COLUMN `create_another_form_submission_record`,
	ADD COLUMN `update_linked_fields` tinyint(1) NOT NULL DEFAULT 0 AFTER `user_duplicate_email_action`,
	ADD COLUMN `no_duplicate_submissions` tinyint(1) NOT NULL DEFAULT 0 AFTER `update_linked_fields`,
	ADD COLUMN `add_logged_in_user_to_group` int(10) unsigned DEFAULT NULL AFTER `no_duplicate_submissions`,
	ADD COLUMN `duplicate_submission_message` varchar(255) DEFAULT NULL AFTER `no_duplicate_submissions`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `preload_dataset_field_user_data` tinyint(1) NOT NULL DEFAULT 1 AFTER `placeholder`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	SET `add_logged_in_user_to_group` = `add_user_to_group`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	SET `update_linked_fields` = 1
	WHERE `save_data` = 1 AND `user_duplicate_email_action` = 'overwrite'
_sql

); revision(80
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_values`
	ADD COLUMN `is_invalid` tinyint(1) NOT NULL DEFAULT 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `invalid_field_value_error_message` varchar(255) DEFAULT NULL
_sql

); revision(81
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `visible_condition_invert` tinyint(1) NOT NULL DEFAULT 0 AFTER `visible_condition_field_id`,
	ADD COLUMN `mandatory_condition_invert` tinyint(1) NOT NULL DEFAULT 0 AFTER `mandatory_condition_field_id`
_sql

); revision(82
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `allow_clear_partial_data` tinyint(1) NOT NULL DEFAULT 0 AFTER `partial_completion_message`,
	ADD COLUMN `clear_partial_data_message` varchar(255) DEFAULT NULL AFTER `allow_clear_partial_data`
_sql

); revision(83
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `enable_summary_page` tinyint(1) NOT NULL DEFAULT 0
_sql

); revision(84
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `visible_condition_checkboxes_operator` enum('AND', 'OR') NOT NULL DEFAULT 'AND' AFTER `visible_condition_invert`,
	ADD COLUMN `mandatory_condition_checkboxes_operator` enum('AND', 'OR') NOT NULL DEFAULT 'AND' AFTER `mandatory_condition_invert`
_sql

); revision(85
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `word_limit` int(10) unsigned DEFAULT NULL
_sql

); revision(100
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	MODIFY COLUMN `field_type` enum('checkbox','checkboxes','date','editor','radios','centralised_radios','select','centralised_select','text','textarea','url','attachment','page_break','section_description','calculated','restatement','repeat_start','repeat_end','pdf_upload') DEFAULT NULL
_sql

); revision(101

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_values` SET `label` = SUBSTR(`label`, 1, 250) WHERE CHAR_LENGTH(`label`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_values` MODIFY COLUMN `label` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `admin_email_addresses` = SUBSTR(`admin_email_addresses`, 1, 250) WHERE CHAR_LENGTH(`admin_email_addresses`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `admin_email_addresses` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `admin_email_template` = SUBSTR(`admin_email_template`, 1, 250) WHERE CHAR_LENGTH(`admin_email_template`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `admin_email_template` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `clear_partial_data_message` = SUBSTR(`clear_partial_data_message`, 1, 250) WHERE CHAR_LENGTH(`clear_partial_data_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `clear_partial_data_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `default_next_button_text` = SUBSTR(`default_next_button_text`, 1, 250) WHERE CHAR_LENGTH(`default_next_button_text`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `default_next_button_text` varchar(250) CHARACTER SET utf8mb4 NULL default 'Next'
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `default_previous_button_text` = SUBSTR(`default_previous_button_text`, 1, 250) WHERE CHAR_LENGTH(`default_previous_button_text`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `default_previous_button_text` varchar(250) CHARACTER SET utf8mb4 NULL default 'Back'
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `duplicate_email_address_error_message` = SUBSTR(`duplicate_email_address_error_message`, 1, 250) WHERE CHAR_LENGTH(`duplicate_email_address_error_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `duplicate_email_address_error_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `duplicate_submission_message` = SUBSTR(`duplicate_submission_message`, 1, 250) WHERE CHAR_LENGTH(`duplicate_submission_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `duplicate_submission_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `honeypot_label` = SUBSTR(`honeypot_label`, 1, 250) WHERE CHAR_LENGTH(`honeypot_label`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `honeypot_label` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `name` = SUBSTR(`name`, 1, 250) WHERE CHAR_LENGTH(`name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `page_end_name` = SUBSTR(`page_end_name`, 1, 250) WHERE CHAR_LENGTH(`page_end_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `page_end_name` varchar(250) CHARACTER SET utf8mb4 NULL default 'Page end'
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `partial_completion_message` = SUBSTR(`partial_completion_message`, 1, 250) WHERE CHAR_LENGTH(`partial_completion_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `partial_completion_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `redirect_location` = SUBSTR(`redirect_location`, 1, 250) WHERE CHAR_LENGTH(`redirect_location`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `redirect_location` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `submit_button_text` = SUBSTR(`submit_button_text`, 1, 250) WHERE CHAR_LENGTH(`submit_button_text`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `submit_button_text` varchar(250) CHARACTER SET utf8mb4 NULL default 'Submit'
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `success_message` = SUBSTR(`success_message`, 1, 250) WHERE CHAR_LENGTH(`success_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `success_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `title` = SUBSTR(`title`, 1, 250) WHERE CHAR_LENGTH(`title`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `title` varchar(250) CHARACTER SET utf8mb4 NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `user_email_template_from_field` = SUBSTR(`user_email_template_from_field`, 1, 250) WHERE CHAR_LENGTH(`user_email_template_from_field`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `user_email_template_from_field` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `user_email_template_logged_in_user` = SUBSTR(`user_email_template_logged_in_user`, 1, 250) WHERE CHAR_LENGTH(`user_email_template_logged_in_user`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `user_email_template_logged_in_user` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `autocomplete_class_name` = SUBSTR(`autocomplete_class_name`, 1, 250) WHERE CHAR_LENGTH(`autocomplete_class_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `autocomplete_class_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `autocomplete_method_name` = SUBSTR(`autocomplete_method_name`, 1, 250) WHERE CHAR_LENGTH(`autocomplete_method_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `autocomplete_method_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `autocomplete_no_filter_placeholder` = SUBSTR(`autocomplete_no_filter_placeholder`, 1, 250) WHERE CHAR_LENGTH(`autocomplete_no_filter_placeholder`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `autocomplete_no_filter_placeholder` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `autocomplete_param_1` = SUBSTR(`autocomplete_param_1`, 1, 250) WHERE CHAR_LENGTH(`autocomplete_param_1`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `autocomplete_param_1` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `autocomplete_param_2` = SUBSTR(`autocomplete_param_2`, 1, 250) WHERE CHAR_LENGTH(`autocomplete_param_2`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `autocomplete_param_2` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `calculation_code` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `css_classes` = SUBSTR(`css_classes`, 1, 250) WHERE CHAR_LENGTH(`css_classes`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `css_classes` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `custom_code_name` = SUBSTR(`custom_code_name`, 1, 250) WHERE CHAR_LENGTH(`custom_code_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `custom_code_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `default_value` = SUBSTR(`default_value`, 1, 250) WHERE CHAR_LENGTH(`default_value`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `default_value` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `default_value_class_name` = SUBSTR(`default_value_class_name`, 1, 250) WHERE CHAR_LENGTH(`default_value_class_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `default_value_class_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `default_value_method_name` = SUBSTR(`default_value_method_name`, 1, 250) WHERE CHAR_LENGTH(`default_value_method_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `default_value_method_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `default_value_param_1` = SUBSTR(`default_value_param_1`, 1, 250) WHERE CHAR_LENGTH(`default_value_param_1`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `default_value_param_1` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `default_value_param_2` = SUBSTR(`default_value_param_2`, 1, 250) WHERE CHAR_LENGTH(`default_value_param_2`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `default_value_param_2` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `description` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `div_wrap_class` = SUBSTR(`div_wrap_class`, 1, 250) WHERE CHAR_LENGTH(`div_wrap_class`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `div_wrap_class` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `invalid_field_value_error_message` = SUBSTR(`invalid_field_value_error_message`, 1, 250) WHERE CHAR_LENGTH(`invalid_field_value_error_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `invalid_field_value_error_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `label` = SUBSTR(`label`, 1, 250) WHERE CHAR_LENGTH(`label`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `label` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `mandatory_condition_field_value` = SUBSTR(`mandatory_condition_field_value`, 1, 250) WHERE CHAR_LENGTH(`mandatory_condition_field_value`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `mandatory_condition_field_value` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `name` = SUBSTR(`name`, 1, 250) WHERE CHAR_LENGTH(`name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `next_button_text` = SUBSTR(`next_button_text`, 1, 250) WHERE CHAR_LENGTH(`next_button_text`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `next_button_text` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `note_to_user` = SUBSTR(`note_to_user`, 1, 250) WHERE CHAR_LENGTH(`note_to_user`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `note_to_user` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `placeholder` = SUBSTR(`placeholder`, 1, 250) WHERE CHAR_LENGTH(`placeholder`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `placeholder` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `previous_button_text` = SUBSTR(`previous_button_text`, 1, 250) WHERE CHAR_LENGTH(`previous_button_text`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `previous_button_text` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `repeat_error_message` = SUBSTR(`repeat_error_message`, 1, 250) WHERE CHAR_LENGTH(`repeat_error_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `repeat_error_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `repeat_field_label` = SUBSTR(`repeat_field_label`, 1, 250) WHERE CHAR_LENGTH(`repeat_field_label`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `repeat_field_label` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `required_error_message` = SUBSTR(`required_error_message`, 1, 250) WHERE CHAR_LENGTH(`required_error_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `required_error_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `validation_error_message` = SUBSTR(`validation_error_message`, 1, 250) WHERE CHAR_LENGTH(`validation_error_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `validation_error_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `values_source` = SUBSTR(`values_source`, 1, 250) WHERE CHAR_LENGTH(`values_source`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `values_source` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `values_source_filter` = SUBSTR(`values_source_filter`, 1, 250) WHERE CHAR_LENGTH(`values_source_filter`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `values_source_filter` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `value_postfix` = SUBSTR(`value_postfix`, 1, 250) WHERE CHAR_LENGTH(`value_postfix`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `value_postfix` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `value_prefix` = SUBSTR(`value_prefix`, 1, 250) WHERE CHAR_LENGTH(`value_prefix`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `value_prefix` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `visible_condition_field_value` = SUBSTR(`visible_condition_field_value`, 1, 250) WHERE CHAR_LENGTH(`visible_condition_field_value`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `visible_condition_field_value` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response_data` MODIFY COLUMN `value` text CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response` MODIFY COLUMN `crm_response` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response_data` SET `internal_value` = SUBSTR(`internal_value`, 1, 250) WHERE CHAR_LENGTH(`internal_value`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response_data` MODIFY COLUMN `internal_value` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response_data` MODIFY COLUMN `value` text CHARACTER SET utf8mb4 NOT NULL
_sql

);


