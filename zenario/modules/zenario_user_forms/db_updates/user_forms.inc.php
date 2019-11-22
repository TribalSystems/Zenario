<?php

//Create the user_forms and user_form_fields tables.
//Note that the installer may have already created these, because of a complicated script for migrating old sites,
//but if this is a fresh install of the User Forms module then we still want to drop and recreate fresh empty tables anyway.
ze\dbAdm::revision( 1
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
_sql
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` (
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
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
_sql
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` (
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
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

); ze\dbAdm::revision( 10
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response`
_sql

,<<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response`(
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`user_id` int(10) unsigned NOT NULL,
		`form_id` int(10) unsigned NOT NULL,
		`response_datetime` datetime NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response_data`
_sql

,<<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response_data`(
		`user_response_id` int(10) unsigned NOT NULL,
		`form_field_id` int(10) unsigned NOT NULL,
		`value` text NOT NULL,
		PRIMARY KEY (`user_response_id`, `form_field_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

); ze\dbAdm::revision (11
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response`
	ADD COLUMN `internal_value` varchar(255) DEFAULT NULL 
_sql

); ze\dbAdm::revision (12
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response`
	DROP COLUMN `internal_value`
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response_data`
	ADD COLUMN `internal_value` varchar(255) DEFAULT NULL 
_sql

); ze\dbAdm::revision (17
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_values`
_sql

,<<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_values`(
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`form_field_id` int(10) unsigned NOT NULL,
		`ord` int(10) unsigned NOT NULL,
		`label` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

);

// Make initial sample forms:
if (ze\dbAdm::needRevision(19)) {
	//Simple Contact us
	if (!ze\row::exists(ZENARIO_USER_FORMS_PREFIX. 'user_forms', ['name' => 'Simple Contact us'])) {
		// Create form
		$userFormId = ze\row::insert(ZENARIO_USER_FORMS_PREFIX. 'user_forms', [
			'name' => 'Simple Contact us',
			'title' => 'Send us a message',
			'send_email_to_admin' => 1,
			'admin_email_addresses' => ze::setting('email_address_admin'),
			'save_data' => 0,
			'save_record' => 1,
			'user_status' => 'contact',
			'show_success_message' => 1,
			'success_message' => '<h2>Message sent</h2>
<p>Thank you for your message! We will get back to you shortly.</p>',
			'user_duplicate_email_action' => 'merge',
			'submit_button_text' => 'Send',
			'admin_email_use_template' => 1,
			'admin_email_template' => 'zenario_common_features__to_admin_contact_form_submission'
		]);
		
		// Create form fields
		$emailFieldId = ze\row::get('custom_dataset_fields', 'id', ['field_name' => 'email', 'is_system_field' => 1]);
		ze\row::insert(ZENARIO_USER_FORMS_PREFIX. 'user_form_fields', [
			'user_form_id' => $userFormId,
			'user_field_id' => $emailFieldId,
			'ord' => 1,
			'is_required' => 1,
			'label' => 'Email',
			'name' => 'Email',
			'required_error_message' => 'This field is required.',
			'validation' => 'email',
			'validation_error_message' => 'Please enter a valid email address.',
			'placeholder' => 'Your email'
		]);
		$firstNameFieldId = ze\row::get('custom_dataset_fields', 'id', ['field_name' => 'first_name', 'is_system_field' => 1]);
		ze\row::insert(ZENARIO_USER_FORMS_PREFIX. 'user_form_fields', [
			'user_form_id' => $userFormId,
			'user_field_id' => $firstNameFieldId,
			'ord' => 2,
			'is_required' => 1,
			'label' => 'First Name',
			'name' => 'First Name',
			'required_error_message' => 'This field is required.',
			'placeholder' => 'Your first name'
		]);
		$lastNameFieldId = ze\row::get('custom_dataset_fields', 'id', ['field_name' => 'last_name', 'is_system_field' => 1]);
		ze\row::insert(ZENARIO_USER_FORMS_PREFIX. 'user_form_fields', [
			'user_form_id' => $userFormId,
			'user_field_id' => $lastNameFieldId,
			'ord' => 3,
			'is_required' => 1,
			'label' => 'Last Name',
			'name' => 'Last Name',
			'required_error_message' => 'This field is required.',
			'placeholder' => 'Your last name'
		]);
		ze\row::insert(ZENARIO_USER_FORMS_PREFIX. 'user_form_fields', [
			'user_form_id' => $userFormId,
			'ord' => 4,
			'is_required' => 1,
			'label' => 'Message',
			'name' => 'Message',
			'field_type' => 'textarea',
			'required_error_message' => 'This field is required.',
			'placeholder' => 'Your message'
		]);
		$consentFieldId = ze\row::get('custom_dataset_fields', 'id', ['field_name' => 'terms_and_conditions_accepted', 'is_system_field' => 1]);
		ze\row::insert(ZENARIO_USER_FORMS_PREFIX. 'user_form_fields', [
			'user_form_id' => $userFormId,
			'user_field_id' => $consentFieldId,
			'ord' => 5,
			'is_required' => 1,
			'label' => 'I consent to my data being stored so that I may be contacted in connection with your products and services.',
			'name' => 'Consent',
			'required_error_message' => 'This field is required.',
			'note_to_user' => 'Please read our <a href="privacy-policy" target="_blank">privacy policy</a>.'
		]);
	}
	ze\dbAdm::revision(19);
}

ze\dbAdm::revision (22
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response`
	ADD COLUMN `crm_response` text DEFAULT NULL
_sql


); ze\dbAdm::revision (25
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_update_link`
_sql

,<<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_update_link`(
		`source_field_id` int(10) unsigned NOT NULL,
		`target_field_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`source_field_id`, `target_field_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

); ze\dbAdm::revision( 26
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response`
	ADD COLUMN blocked_by_profanity_filter BOOLEAN NOT NULL DEFAULT 0
_sql

); ze\dbAdm::revision( 27
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response`
	ADD COLUMN profanity_filter_score INT NOT NULL DEFAULT 0,
	ADD COLUMN profanity_tolerance_limit INT NOT NULL DEFAULT 0
_sql

);

ze\dbAdm::revision(33
// Create tables for partial form responses
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response`
_sql

,<<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response`(
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`user_id` int(10) unsigned NOT NULL,
		`form_id` int(10) unsigned NOT NULL,
		`response_datetime` datetime NOT NULL,
		PRIMARY KEY (`id`),
		KEY (`user_id`),
		KEY (`form_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response_data`
_sql

,<<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response_data`(
		`user_partial_response_id` int(10) unsigned NOT NULL,
		`form_field_id` int(10) unsigned NOT NULL,
		`value` text NOT NULL,
		PRIMARY KEY (`user_partial_response_id`, `form_field_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

); ze\dbAdm::revision(36
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response_data`
	ADD COLUMN `field_row` int(10) unsigned NOT NULL DEFAULT 0 AFTER `form_field_id`,
	DROP PRIMARY KEY,
	ADD PRIMARY KEY (`user_response_id`, `form_field_id`, `field_row`)
_sql

); ze\dbAdm::revision(37
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response_data`
	ADD COLUMN `field_row` int(10) unsigned NOT NULL DEFAULT 0 AFTER `form_field_id`,
	DROP PRIMARY KEY,
	ADD PRIMARY KEY (`user_partial_response_id`, `form_field_id`, `field_row`)
_sql


); ze\dbAdm::revision(48
// Create a table to store a calculated field's parameters
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]calculated_field_parameters`
_sql

); ze\dbAdm::revision(49
// New column for calculated fields
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `calculation_code` text
_sql

); ze\dbAdm::revision(50
// New column for calculated fields prefix and postfix
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `value_prefix` varchar(255) DEFAULT NULL,
	ADD COLUMN `value_postfix` varchar(255) DEFAULT NULL
_sql

);

// Migrate existing calculation fields to new system
if (ze\dbAdm::needRevision(51)) {
	$sql = '
		SELECT id, numeric_field_1, numeric_field_2, calculation_type
		FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields
		WHERE field_type = "calculated"';
	$result = ze\sql::select($sql);
	while ($row = ze\sql::fetchAssoc($result)) {
		if ($row['numeric_field_1'] && $row['numeric_field_2'] && $row['calculation_type']) {
			$calculationCode = [];
			$calculationCode[] = ['type' => 'field', 'value' => $row['numeric_field_1']];
			$calculationCode[] = ['type' => ($row['calculation_type'] == '+') ? 'operation_addition' : 'operation_subtraction'];
			$calculationCode[] = ['type' => 'field', 'value' => $row['numeric_field_2']];
			
			$calculationCode = json_encode($calculationCode);
			
			if ($calculationCode) {
				ze\row::update(
					ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', 
					['calculation_code' => $calculationCode],
					['id' => $row['id']]
				);
			}
			
		}
	}
	
	ze\dbAdm::revision(51);
}

ze\dbAdm::revision(52
// Delete old calculation field columns
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	DROP COLUMN `numeric_field_1`,
	DROP COLUMN `numeric_field_2`,
	DROP COLUMN `calculation_type`
_sql
);

// Migrate dataset field DB columns to form field IDs
if (ze\dbAdm::needRevision(54)) {
	$sql = '
		SELECT id, reply_to_email_field, reply_to_first_name, reply_to_last_name
		FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms';
	$result = ze\sql::select($sql);
	while ($form = ze\sql::fetchAssoc($result)) {
		$set = false;
		if (!empty($form['reply_to_email_field'])) {
			$sql = '
				SELECT f.id
				FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields f
				INNER JOIN ' . DB_PREFIX . 'custom_dataset_fields c
					ON f.user_field_id = c.id
				WHERE c.db_column = "' . ze\escape::sql($form['reply_to_email_field']) . '"
				AND f.user_form_id = ' . (int)$form['id'];
			$result = ze\sql::select($sql);
			$row = ze\sql::fetchAssoc($result);
			if ($row) {
				$set = true;
				ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['reply_to_email_field' => $row['id']], $form['id']);
			}
		}
		if (!$set) {
			ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['reply_to_email_field' => 0], $form['id']);
		}
		
		$set = false;
		if (!empty($form['reply_to_first_name'])) {
			$sql = '
				SELECT f.id
				FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields f
				INNER JOIN ' . DB_PREFIX . 'custom_dataset_fields c
					ON f.user_field_id = c.id
				WHERE c.db_column = "' . ze\escape::sql($form['reply_to_first_name']) . '"
				AND f.user_form_id = ' . (int)$form['id'];
			$result = ze\sql::select($sql);
			$row = ze\sql::fetchAssoc($result);
			if ($row) {
				$set = true;
				ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['reply_to_first_name' => $row['id']], $form['id']);
			}
		}
		if (!$set) {
			ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['reply_to_first_name' => 0], $form['id']);
		}
		
		$set = false;
		if (!empty($form['reply_to_last_name'])) {
			$sql = '
				SELECT f.id
				FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields f
				INNER JOIN ' . DB_PREFIX . 'custom_dataset_fields c
					ON f.user_field_id = c.id
				WHERE c.db_column = "' . ze\escape::sql($form['reply_to_last_name']) . '"
				AND f.user_form_id = ' . (int)$form['id'];
			$result = ze\sql::select($sql);
			$row = ze\sql::fetchAssoc($result);
			if ($row) {
				$set = true;
				ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['reply_to_last_name' => $row['id']], $form['id']);
			}
		}
		if (!$set) {
			ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['reply_to_last_name' => 0], $form['id']);
		}
	}
	ze\dbAdm::revision(54);
}

ze\dbAdm::revision(55
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	MODIFY COLUMN `reply_to_email_field` int(10) unsigned NOT NULL DEFAULT 0,
	MODIFY COLUMN `reply_to_first_name` int(10) unsigned NOT NULL DEFAULT 0,
	MODIFY COLUMN `reply_to_last_name` int(10) unsigned NOT NULL DEFAULT 0
_sql

); ze\dbAdm::revision(56
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `user_email_field` int(10) unsigned NOT NULL DEFAULT 0 AFTER `send_email_to_user`
_sql

); ze\dbAdm::revision(58
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
    ADD COLUMN `use_honeypot` tinyint(1) NOT NULL DEFAULT '0',
    ADD COLUMN `honeypot_label` varchar(255) DEFAULT NULL
_sql

); ze\dbAdm::revision(60
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
    ADD COLUMN `show_page_switcher` tinyint(1) NOT NULL DEFAULT '0',
    ADD COLUMN `page_switcher_navigation` enum('none', 'only_visited_pages') DEFAULT 'none',
    ADD COLUMN `hide_final_page_in_page_switcher` tinyint(1) NOT NULL DEFAULT '0'
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `hide_in_page_switcher` tinyint(1) NOT NULL DEFAULT '0'
_sql

); ze\dbAdm::revision(61
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
    ADD COLUMN `partial_completion_mode` enum('auto', 'button') DEFAULT NULL AFTER `allow_partial_completion`
_sql

);

if (ze\dbAdm::needRevision(62)) {
    ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['partial_completion_mode' => 'button'], ['allow_partial_completion' => 1]);
    ze\dbAdm::revision(62);
}

ze\dbAdm::revision(63
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `page_end_name` varchar(255) DEFAULT 'Page 1'
_sql

); ze\dbAdm::revision(64
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `show_month_year_selectors` tinyint(1) NOT NULL DEFAULT '0'
_sql

//Allow a form to send multiple user emails
); ze\dbAdm::revision(65
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
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
if (ze\dbAdm::needRevision(66)) {
	$sql = '
		SELECT id, send_email_to_user, user_email_field, user_email_template
		FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms';
	$result = ze\sql::select($sql);
	while ($form = ze\sql::fetchAssoc($result)) {
		if (!empty($form['send_email_to_user'])) {
			$details = [];
			if (!empty($form['user_email_field'])) {
				$details['send_email_to_email_from_field'] = true;
				$details['user_email_use_template_for_email_from_field'] = true;
				$details['user_email_template_from_field'] = $form['user_email_template'];
			} else {
				$details['send_email_to_logged_in_user'] = true;
				$details['user_email_use_template_for_logged_in_user'] = true;
				$details['user_email_template_logged_in_user'] = $form['user_email_template'];
			}
			ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $details, $form['id']);
		}
	}
	ze\dbAdm::revision(66);
}
//Drop old columns
ze\dbAdm::revision(67
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	DROP COLUMN `send_email_to_user`,
	DROP COLUMN `user_email_template`
_sql

); ze\dbAdm::revision(68
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response`
	ADD COLUMN `max_page_reached` int(10) unsigned NOT NULL DEFAULT 1
_sql

); ze\dbAdm::revision(69
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	MODIFY COLUMN `partial_completion_mode` enum('auto','button', 'auto_and_button') DEFAULT NULL
_sql

); ze\dbAdm::revision(70
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	DROP COLUMN `create_another_form_submission_record`,
	ADD COLUMN `update_linked_fields` tinyint(1) NOT NULL DEFAULT 0 AFTER `user_duplicate_email_action`,
	ADD COLUMN `no_duplicate_submissions` tinyint(1) NOT NULL DEFAULT 0 AFTER `update_linked_fields`,
	ADD COLUMN `add_logged_in_user_to_group` int(10) unsigned DEFAULT NULL AFTER `no_duplicate_submissions`,
	ADD COLUMN `duplicate_submission_message` varchar(255) DEFAULT NULL AFTER `no_duplicate_submissions`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `preload_dataset_field_user_data` tinyint(1) NOT NULL DEFAULT 1 AFTER `placeholder`
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	SET `add_logged_in_user_to_group` = `add_user_to_group`
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	SET `update_linked_fields` = 1
	WHERE `save_data` = 1 AND `user_duplicate_email_action` = 'overwrite'
_sql

); ze\dbAdm::revision(80
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_values`
	ADD COLUMN `is_invalid` tinyint(1) NOT NULL DEFAULT 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `invalid_field_value_error_message` varchar(255) DEFAULT NULL
_sql

); ze\dbAdm::revision(81
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `visible_condition_invert` tinyint(1) NOT NULL DEFAULT 0 AFTER `visible_condition_field_id`,
	ADD COLUMN `mandatory_condition_invert` tinyint(1) NOT NULL DEFAULT 0 AFTER `mandatory_condition_field_id`
_sql

); ze\dbAdm::revision(82
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `allow_clear_partial_data` tinyint(1) NOT NULL DEFAULT 0 AFTER `partial_completion_message`,
	ADD COLUMN `clear_partial_data_message` varchar(255) DEFAULT NULL AFTER `allow_clear_partial_data`
_sql

); ze\dbAdm::revision(83
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `enable_summary_page` tinyint(1) NOT NULL DEFAULT 0
_sql

); ze\dbAdm::revision(84
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `visible_condition_checkboxes_operator` enum('AND', 'OR') NOT NULL DEFAULT 'AND' AFTER `visible_condition_invert`,
	ADD COLUMN `mandatory_condition_checkboxes_operator` enum('AND', 'OR') NOT NULL DEFAULT 'AND' AFTER `mandatory_condition_invert`
_sql

); ze\dbAdm::revision(85
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `word_limit` int(10) unsigned DEFAULT NULL
_sql

); ze\dbAdm::revision(100
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	MODIFY COLUMN `field_type` enum('checkbox','checkboxes','date','editor','radios','centralised_radios','select','centralised_select','text','textarea','url','attachment','page_break','section_description','calculated','restatement','repeat_start','repeat_end','pdf_upload') DEFAULT NULL
_sql

); ze\dbAdm::revision(101

, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_values` SET `label` = SUBSTR(`label`, 1, 250) WHERE CHAR_LENGTH(`label`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_values` MODIFY COLUMN `label` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `admin_email_addresses` = SUBSTR(`admin_email_addresses`, 1, 250) WHERE CHAR_LENGTH(`admin_email_addresses`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `admin_email_addresses` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `admin_email_template` = SUBSTR(`admin_email_template`, 1, 250) WHERE CHAR_LENGTH(`admin_email_template`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `admin_email_template` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `clear_partial_data_message` = SUBSTR(`clear_partial_data_message`, 1, 250) WHERE CHAR_LENGTH(`clear_partial_data_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `clear_partial_data_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `default_next_button_text` = SUBSTR(`default_next_button_text`, 1, 250) WHERE CHAR_LENGTH(`default_next_button_text`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `default_next_button_text` varchar(250) CHARACTER SET utf8mb4 NULL default 'Next'
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `default_previous_button_text` = SUBSTR(`default_previous_button_text`, 1, 250) WHERE CHAR_LENGTH(`default_previous_button_text`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `default_previous_button_text` varchar(250) CHARACTER SET utf8mb4 NULL default 'Back'
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `duplicate_email_address_error_message` = SUBSTR(`duplicate_email_address_error_message`, 1, 250) WHERE CHAR_LENGTH(`duplicate_email_address_error_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `duplicate_email_address_error_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `duplicate_submission_message` = SUBSTR(`duplicate_submission_message`, 1, 250) WHERE CHAR_LENGTH(`duplicate_submission_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `duplicate_submission_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `honeypot_label` = SUBSTR(`honeypot_label`, 1, 250) WHERE CHAR_LENGTH(`honeypot_label`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `honeypot_label` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `name` = SUBSTR(`name`, 1, 250) WHERE CHAR_LENGTH(`name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `page_end_name` = SUBSTR(`page_end_name`, 1, 250) WHERE CHAR_LENGTH(`page_end_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `page_end_name` varchar(250) CHARACTER SET utf8mb4 NULL default 'Page 1'
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `partial_completion_message` = SUBSTR(`partial_completion_message`, 1, 250) WHERE CHAR_LENGTH(`partial_completion_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `partial_completion_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `redirect_location` = SUBSTR(`redirect_location`, 1, 250) WHERE CHAR_LENGTH(`redirect_location`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `redirect_location` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `submit_button_text` = SUBSTR(`submit_button_text`, 1, 250) WHERE CHAR_LENGTH(`submit_button_text`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `submit_button_text` varchar(250) CHARACTER SET utf8mb4 NULL default 'Submit'
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `success_message` = SUBSTR(`success_message`, 1, 250) WHERE CHAR_LENGTH(`success_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `success_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `title` = SUBSTR(`title`, 1, 250) WHERE CHAR_LENGTH(`title`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `title` varchar(250) CHARACTER SET utf8mb4 NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `user_email_template_from_field` = SUBSTR(`user_email_template_from_field`, 1, 250) WHERE CHAR_LENGTH(`user_email_template_from_field`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `user_email_template_from_field` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` SET `user_email_template_logged_in_user` = SUBSTR(`user_email_template_logged_in_user`, 1, 250) WHERE CHAR_LENGTH(`user_email_template_logged_in_user`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms` MODIFY COLUMN `user_email_template_logged_in_user` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `autocomplete_class_name` = SUBSTR(`autocomplete_class_name`, 1, 250) WHERE CHAR_LENGTH(`autocomplete_class_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `autocomplete_class_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `autocomplete_method_name` = SUBSTR(`autocomplete_method_name`, 1, 250) WHERE CHAR_LENGTH(`autocomplete_method_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `autocomplete_method_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `autocomplete_no_filter_placeholder` = SUBSTR(`autocomplete_no_filter_placeholder`, 1, 250) WHERE CHAR_LENGTH(`autocomplete_no_filter_placeholder`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `autocomplete_no_filter_placeholder` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `autocomplete_param_1` = SUBSTR(`autocomplete_param_1`, 1, 250) WHERE CHAR_LENGTH(`autocomplete_param_1`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `autocomplete_param_1` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `autocomplete_param_2` = SUBSTR(`autocomplete_param_2`, 1, 250) WHERE CHAR_LENGTH(`autocomplete_param_2`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `autocomplete_param_2` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `calculation_code` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `css_classes` = SUBSTR(`css_classes`, 1, 250) WHERE CHAR_LENGTH(`css_classes`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `css_classes` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `custom_code_name` = SUBSTR(`custom_code_name`, 1, 250) WHERE CHAR_LENGTH(`custom_code_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `custom_code_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `default_value` = SUBSTR(`default_value`, 1, 250) WHERE CHAR_LENGTH(`default_value`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `default_value` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `default_value_class_name` = SUBSTR(`default_value_class_name`, 1, 250) WHERE CHAR_LENGTH(`default_value_class_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `default_value_class_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `default_value_method_name` = SUBSTR(`default_value_method_name`, 1, 250) WHERE CHAR_LENGTH(`default_value_method_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `default_value_method_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `default_value_param_1` = SUBSTR(`default_value_param_1`, 1, 250) WHERE CHAR_LENGTH(`default_value_param_1`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `default_value_param_1` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `default_value_param_2` = SUBSTR(`default_value_param_2`, 1, 250) WHERE CHAR_LENGTH(`default_value_param_2`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `default_value_param_2` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `description` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `div_wrap_class` = SUBSTR(`div_wrap_class`, 1, 250) WHERE CHAR_LENGTH(`div_wrap_class`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `div_wrap_class` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `invalid_field_value_error_message` = SUBSTR(`invalid_field_value_error_message`, 1, 250) WHERE CHAR_LENGTH(`invalid_field_value_error_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `invalid_field_value_error_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `label` = SUBSTR(`label`, 1, 250) WHERE CHAR_LENGTH(`label`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `label` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `mandatory_condition_field_value` = SUBSTR(`mandatory_condition_field_value`, 1, 250) WHERE CHAR_LENGTH(`mandatory_condition_field_value`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `mandatory_condition_field_value` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `name` = SUBSTR(`name`, 1, 250) WHERE CHAR_LENGTH(`name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `next_button_text` = SUBSTR(`next_button_text`, 1, 250) WHERE CHAR_LENGTH(`next_button_text`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `next_button_text` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `note_to_user` = SUBSTR(`note_to_user`, 1, 250) WHERE CHAR_LENGTH(`note_to_user`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `note_to_user` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `placeholder` = SUBSTR(`placeholder`, 1, 250) WHERE CHAR_LENGTH(`placeholder`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `placeholder` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `previous_button_text` = SUBSTR(`previous_button_text`, 1, 250) WHERE CHAR_LENGTH(`previous_button_text`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `previous_button_text` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `repeat_error_message` = SUBSTR(`repeat_error_message`, 1, 250) WHERE CHAR_LENGTH(`repeat_error_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `repeat_error_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `repeat_field_label` = SUBSTR(`repeat_field_label`, 1, 250) WHERE CHAR_LENGTH(`repeat_field_label`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `repeat_field_label` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `required_error_message` = SUBSTR(`required_error_message`, 1, 250) WHERE CHAR_LENGTH(`required_error_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `required_error_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `validation_error_message` = SUBSTR(`validation_error_message`, 1, 250) WHERE CHAR_LENGTH(`validation_error_message`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `validation_error_message` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `values_source` = SUBSTR(`values_source`, 1, 250) WHERE CHAR_LENGTH(`values_source`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `values_source` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `values_source_filter` = SUBSTR(`values_source_filter`, 1, 250) WHERE CHAR_LENGTH(`values_source_filter`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `values_source_filter` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `value_postfix` = SUBSTR(`value_postfix`, 1, 250) WHERE CHAR_LENGTH(`value_postfix`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `value_postfix` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `value_prefix` = SUBSTR(`value_prefix`, 1, 250) WHERE CHAR_LENGTH(`value_prefix`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `value_prefix` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` SET `visible_condition_field_value` = SUBSTR(`visible_condition_field_value`, 1, 250) WHERE CHAR_LENGTH(`visible_condition_field_value`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` MODIFY COLUMN `visible_condition_field_value` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response_data` MODIFY COLUMN `value` text CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response` MODIFY COLUMN `crm_response` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response_data` SET `internal_value` = SUBSTR(`internal_value`, 1, 250) WHERE CHAR_LENGTH(`internal_value`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response_data` MODIFY COLUMN `internal_value` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response_data` MODIFY COLUMN `value` text CHARACTER SET utf8mb4 NOT NULL
_sql

//Rename pdf_upload to document_upload
); ze\dbAdm::revision(102
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	MODIFY COLUMN `field_type` enum('checkbox','checkboxes','date','editor','radios','centralised_radios','select','centralised_select','text','textarea','url','attachment','page_break','section_description','calculated','restatement','repeat_start','repeat_end', 'pdf_upload', 'document_upload') DEFAULT NULL
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	SET `field_type` = 'document_upload' WHERE `field_type` = 'pdf_upload'
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	MODIFY COLUMN `field_type` enum('checkbox','checkboxes','date','editor','radios','centralised_radios','select','centralised_select','text','textarea','url','attachment','page_break','section_description','calculated','restatement','repeat_start','repeat_end', 'document_upload') DEFAULT NULL
_sql

//Add column for fields being mandatory if visible
); ze\dbAdm::revision(103
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `mandatory_if_visible` tinyint(1) NOT NULL DEFAULT '0' AFTER `is_required`
_sql

//Add column for document upload default combined images filename
); ze\dbAdm::revision(104
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `combined_filename` varchar(250) CHARACTER SET utf8mb4 DEFAULT NULL
_sql

//New table to store pages seperatly rather than having page break fields
); ze\dbAdm::revision(106
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]pages`
_sql

,<<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]pages`(
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`form_id` int(10) unsigned NOT NULL,
		`ord` int(10) unsigned NOT NULL DEFAULT '1',
		`name` varchar(250) CHARACTER SET utf8mb4 DEFAULT NULL,
		`label` varchar(250) CHARACTER SET utf8mb4 DEFAULT NULL,
		`visibility` enum('visible','hidden','visible_on_condition') DEFAULT 'visible',
		`visible_condition_field_id` int(10) unsigned DEFAULT '0',
		`visible_condition_invert` tinyint(1) NOT NULL DEFAULT '0',
		`visible_condition_checkboxes_operator` enum('AND','OR') NOT NULL DEFAULT 'AND',
		`visible_condition_field_value` varchar(250) CHARACTER SET utf8mb4 DEFAULT NULL,
		`next_button_text` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT 'Next',
		`previous_button_text` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT 'Previous',
		`hide_in_page_switcher` tinyint(1) NOT NULL DEFAULT '0',
		PRIMARY KEY (`id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `page_id` int(10) unsigned NOT NULL AFTER `user_form_id`
_sql

);

//Migrate page breaks to pages..
if (ze\dbAdm::needRevision(107)) {
	$sql = '
		SELECT id, page_end_name, default_previous_button_text, hide_final_page_in_page_switcher
		FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms';
	$result = ze\sql::select($sql);
	while ($form = ze\sql::fetchAssoc($result)) {
		$ord = 0;
		$pageFields = [];
		
		$sql = '
			SELECT * 
			FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields
			WHERE user_form_id = ' . (int)$form['id'] . '
			ORDER BY ord';
		$fieldsResult = ze\sql::select($sql);
		while ($field = ze\sql::fetchAssoc($fieldsResult)) {
			if ($field['field_type'] == 'page_break') {
				$page = [
					'form_id' => $form['id'],
					'ord' => ++$ord,
					'name' => $field['name'],
					'label' => $field['name'],
					'visibility' => $field['visibility'],
					'visible_condition_field_id' => $field['visible_condition_field_id'],
					'visible_condition_invert' => $field['visible_condition_invert'],
					'visible_condition_checkboxes_operator' => $field['visible_condition_checkboxes_operator'],
					'visible_condition_field_value' => $field['visible_condition_field_value'],
					'next_button_text' => $field['next_button_text'] ? $field['next_button_text'] : 'Next',
					'previous_button_text' => $field['previous_button_text'],
					'hide_in_page_switcher' => $field['hide_in_page_switcher']
				];
				$pageId = ze\row::insert(ZENARIO_USER_FORMS_PREFIX . 'pages', $page);
				if ($pageFields) {
					$sql = '
						UPDATE ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields
						SET page_id = ' . (int)$pageId . ' WHERE id IN (' . implode(',', array_keys($pageFields)) . ')';
					ze\sql::update($sql);
				}
				$pageFields = [];
			} else {
				$pageFields[$field['id']] = $field;
			}
		}
		
		$page = [
			'form_id' => $form['id'],
			'ord' => ++$ord,
			'name' => $form['page_end_name'],
			'label' => $form['page_end_name'],
			'previous_button_text' => $form['default_previous_button_text'],
			'hide_in_page_switcher' => $form['hide_final_page_in_page_switcher']
		];
		$pageId = ze\row::insert(ZENARIO_USER_FORMS_PREFIX . 'pages', $page);
		if ($pageFields) {
			$sql = '
				UPDATE ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields
				SET page_id = ' . (int)$pageId . ' WHERE id IN (' . implode(',', array_keys($pageFields)) . ')';
			ze\sql::update($sql);
		}
	}
	
	ze\row::delete(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', ['field_type' => 'page_break']);
	
	
	
	ze\dbAdm::revision(107);
}

ze\dbAdm::revision(108
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	DROP COLUMN `default_next_button_text`,
	DROP COLUMN `default_previous_button_text`,
	DROP COLUMN `hide_final_page_in_page_switcher`,
	DROP COLUMN `page_end_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	MODIFY COLUMN `field_type` enum('checkbox','checkboxes','date','editor','radios','centralised_radios','select','centralised_select','text','textarea','url','attachment','section_description','calculated','restatement','repeat_start','repeat_end','document_upload') DEFAULT NULL,
	DROP COLUMN `next_button_text`,
	DROP COLUMN `previous_button_text`,
	DROP COLUMN `hide_in_page_switcher`
_sql

//Replacing a table with a column...
); ze\dbAdm::revision(109
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `filter_on_field` int(10) unsigned NOT NULL DEFAULT 0
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` uf
	INNER JOIN `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_update_link` ufl 
		ON uf.id = ufl.target_field_id
	SET  uf.filter_on_field = ufl.source_field_id
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_update_link`
_sql

); ze\dbAdm::revision(110
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]form_field_values`
	MODIFY `ord` int(10) unsigned NOT NULL DEFAULT 0,
	MODIFY `label` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	MODIFY COLUMN `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT ''
_sql

//This was never implemented or used
); ze\dbAdm::revision(111
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	DROP COLUMN `autocomplete_class_name`,
	DROP COLUMN `autocomplete_method_name`,
	DROP COLUMN `autocomplete_param_1`,
	DROP COLUMN `autocomplete_param_2`
_sql

); ze\dbAdm::revision(112
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `repeat_start_id` int(10) unsigned NOT NULL DEFAULT '0',
	DROP COLUMN `show_repeat_field`,
	DROP COLUMN `repeat_field_label`,
	DROP COLUMN `repeat_error_message`
_sql

);

//Migration for new repeat_start_id column
if (ze\dbAdm::needRevision(114)) {
	$sql = '
		SELECT id
		FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms';
	$result = ze\sql::select($sql);
	while ($form = ze\sql::fetchAssoc($result)) {
		$repeatStartId = false;
		$sql = '
			SELECT uff.id, uff.field_type
			FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
			INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'pages p
				ON uff.page_id = p.id
			WHERE uff.user_form_id = ' . (int)$form['id'] . '
			ORDER BY p.ord, uff.ord';
		$result = ze\sql::select($sql);
		while ($field = ze\sql::fetchAssoc($result)) {
			ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', ['repeat_start_id' => $repeatStartId], $field['id']);
			
			if ($field['field_type'] == 'repeat_start') {
				$repeatStartId = $field['id'];
			} elseif ($field['field_type'] == 'repeat_end') {
				$repeatStartId = false;
			}
		}
	}
	ze\dbAdm::revision(114);
}

//Migration for max_page_reached stored in form partial responses
ze\dbAdm::revision(115
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response` r
	INNER JOIN `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]pages` p
		ON r.form_id = p.form_id
		AND r.max_page_reached = p.ord
	SET r.max_page_reached = p.id
_sql

); ze\dbAdm::revision(117
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `verification_email_template` varchar(255) DEFAULT NULL,
	ADD COLUMN `welcome_email_template` varchar(255) DEFAULT NULL
_sql

); ze\dbAdm::revision(118
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	MODIFY COLUMN `verification_email_template` varchar(250) CHARACTER SET utf8mb4 DEFAULT NULL,
	MODIFY COLUMN `welcome_email_template` varchar(250) CHARACTER SET utf8mb4 DEFAULT NULL,
	ADD COLUMN `welcome_message` varchar(250) CHARACTER SET utf8mb4 DEFAULT NULL,
	ADD COLUMN `welcome_redirect_location` varchar(250) CHARACTER SET utf8mb4 DEFAULT NULL
_sql

); ze\dbAdm::revision(120
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD KEY (`user_form_id`),
	ADD KEY (`page_id`),
	ADD KEY (`user_field_id`),
	ADD KEY (`custom_code_name`)
_sql

); ze\dbAdm::revision(121
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `no_past_dates` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_month_year_selectors`
_sql

); ze\dbAdm::revision(122
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	CHANGE `word_limit` `word_count_max` int(10) unsigned DEFAULT NULL,
	ADD COLUMN `word_count_min` int(10) unsigned DEFAULT NULL AFTER `word_count_max`
_sql

); ze\dbAdm::revision(124
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `disable_manual_input` tinyint(1) NOT NULL DEFAULT '0'
_sql

); ze\dbAdm::revision(126
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	SET welcome_email_template = "zenario_users__to_user_account_activated"
	WHERE welcome_email_template = "zenario_extranet_registration__to_user_account_activation_en"
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	MODIFY COLUMN `success_message` text CHARACTER SET utf8mb4 DEFAULT NULL
_sql

); ze\dbAdm::revision(151
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `enable_suggested_values` tinyint(1) NOT NULL DEFAULT '0'
_sql

); ze\dbAdm::revision(152
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `add_row_label` varchar(250) CHARACTER SET utf8mb4 DEFAULT NULL AFTER `max_rows`
_sql

); ze\dbAdm::revision(153
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `no_future_dates` tinyint(1) NOT NULL DEFAULT '0' AFTER `no_past_dates`
_sql

); ze\dbAdm::revision(154
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `stop_user_editing_filename` tinyint(1) NOT NULL DEFAULT '0' AFTER `combined_filename`
_sql

);

if (ze\dbAdm::needRevision(156)) {
	$sql = '
		UPDATE ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields
		SET stop_user_editing_filename = 1
		WHERE field_type = "document_upload"
		AND (combined_filename IS NOT NULL OR combined_filename != "")';
	ze\sql::update($sql);
	
	ze\dbAdm::revision(156);
}


ze\dbAdm::revision(157
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `summary_page_lower_text` text CHARACTER SET utf8mb4 AFTER `enable_summary_page`
_sql

); ze\dbAdm::revision(158
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `enable_summary_page_required_checkbox` tinyint(1) NOT NULL DEFAULT '0' AFTER `enable_summary_page`,
	ADD COLUMN `summary_page_required_checkbox_label` varchar(250) CHARACTER SET utf8mb4 NULL AFTER `enable_summary_page_required_checkbox`,
	ADD COLUMN `summary_page_required_checkbox_error_message` varchar(250) CHARACTER SET utf8mb4 NULL AFTER `summary_page_required_checkbox_label`
_sql

); ze\dbAdm::revision(159
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `set_simple_access_cookie` tinyint(1) NOT NULL DEFAULT '0'
_sql

); ze\dbAdm::revision(160
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `simple_access_cookie_override_redirect` tinyint(1) NOT NULL DEFAULT '0'
_sql

); ze\dbAdm::revision(161
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `invert_dataset_result` tinyint(1) NOT NULL DEFAULT '0'
_sql

); ze\dbAdm::revision(162
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `show_in_summary` tinyint(1) NOT NULL DEFAULT '0'
_sql

); ze\dbAdm::revision(163
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]pages`
	ADD COLUMN `show_in_summary` tinyint(1) NOT NULL DEFAULT '0'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]pages`
	SET `show_in_summary` = 1
	WHERE `hide_in_page_switcher` = 0
_sql

); ze\dbAdm::revision(180
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `partial_completion_get_request` varchar(250) CHARACTER SET utf8mb4 NULL AFTER `partial_completion_message`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_partial_response`
	ADD COLUMN `get_request_value` int(10) unsigned DEFAULT NULL AFTER `form_id`
_sql

); ze\dbAdm::revision(181
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]predefined_text_targets`
_sql
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]predefined_text_targets` (
		`form_field_id` int(10) unsigned NOT NULL,
		`button_label` varchar(250) CHARACTER SET utf8mb4 NOT NULL,
		PRIMARY KEY (`form_field_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]predefined_text_triggers`
_sql
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]predefined_text_triggers` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`target_form_field_id` int(10) unsigned NOT NULL,
		`form_field_id` int(10) unsigned NOT NULL DEFAULT 0,
		`form_field_value_id` int(10) unsigned NOT NULL DEFAULT 0,
		`ord` int(10) unsigned NOT NULL DEFAULT 0,
		`text` text,
		PRIMARY KEY (`id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

//Add individual options to forms on the period to delete the response
); ze\dbAdm::revision(182
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `period_to_delete_response_headers` varchar(255) NOT NULL DEFAULT '',
	ADD COLUMN `period_to_delete_response_content` varchar(255) NOT NULL DEFAULT ''
_sql

//Add a column to user responces to note whether data has been deleted from it
); ze\dbAdm::revision(184
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_response`
	ADD COLUMN `user_deleted` tinyint(1) NOT NULL DEFAULT '0',
	ADD COLUMN `data_deleted` tinyint(1) NOT NULL DEFAULT '0'
_sql

//Remove word type captchas (Google recaptcha 1.0) because it has been discontinued
); ze\dbAdm::revision(201
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	MODIFY COLUMN `captcha_type` enum('word', 'math','pictures') DEFAULT NULL
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	SET `captcha_type` = NULL,
	`use_captcha` = 0
	WHERE `captcha_type` = 'word'
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	MODIFY COLUMN `captcha_type` enum('math','pictures') DEFAULT NULL
_sql

//Add a setting to control the position of error messages
); ze\dbAdm::revision(202
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `show_errors_below_fields` tinyint(1) NOT NULL DEFAULT '0'
_sql

//Add a setting for first_name or last_name dataset fields to record both names
); ze\dbAdm::revision(210
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN `split_first_name_last_name` tinyint(1) NOT NULL DEFAULT '0'
_sql

); ze\dbAdm::revision(211
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	MODIFY COLUMN `welcome_message` text CHARACTER SET utf8mb4 DEFAULT NULL
_sql

//Merge "autocomplete" and "enable_suggested_values" options into somthing that makes more sense
); ze\dbAdm::revision(215
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	CHANGE `autocomplete_no_filter_placeholder` `filter_placeholder` varchar(250) CHARACTER SET utf8mb4 DEFAULT NULL,
	ADD COLUMN `suggested_values` enum('custom', 'pre_defined') DEFAULT NULL AFTER `filter_placeholder`,
	ADD COLUMN `force_suggested_values` tinyint(1) NOT NULL DEFAULT '0' AFTER `suggested_values`
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` 
	SET `force_suggested_values` = 1, `suggested_values` = 'pre_defined'
	WHERE `autocomplete` = 1
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields` 
	SET `suggested_values` = 'custom'
	WHERE `enable_suggested_values` = 1 AND `autocomplete` = 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	DROP COLUMN `enable_suggested_values`,
	DROP COLUMN `autocomplete`
_sql

//Added a column for third radio button option
); ze\dbAdm::revision(217
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `consent_dropdown` int(5) NOT NULL DEFAULT '0'
_sql

//Added a column for third radio button option
); ze\dbAdm::revision(218
,  <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	DROP COLUMN `consent_dropdown`
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `consent_field` int(5) NOT NULL DEFAULT '0'
_sql

//Fix legacy form pages with "Page end" as the first pages name
); ze\dbAdm::revision(250
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]pages`
	SET `name` = 'Page 1', `label` = 'Page 1'
	WHERE `name` = 'Page end'
	AND `ord` = 1
_sql

//(N.b. this was added in an after-branch patch in 8.3 revision 220, but is safe to re-run.)
); ze\dbAdm::revision(251
,  <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	SET period_to_delete_response_headers = 0
	WHERE period_to_delete_response_headers = 'never_save'
_sql

,  <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	SET period_to_delete_response_content = 0
	WHERE period_to_delete_response_content = 'never_save'
_sql

,  <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET value = 0
	WHERE name = 'period_to_delete_the_form_response_log_headers'
	AND value = 'never_save'
_sql

,  <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET value = 0
	WHERE name = 'period_to_delete_the_form_response_log_content'
	AND value = 'never_save'
_sql

); ze\dbAdm::revision(253
,  <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	ADD COLUMN rows int(10) unsigned default NULL
_sql
); ze\dbAdm::revision(254
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	MODIFY COLUMN `visible_condition_field_value` longtext CHARACTER SET utf8mb4 DEFAULT NULL
_sql

); ze\dbAdm::revision(255
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_form_fields`
	MODIFY COLUMN `mandatory_condition_field_value` longtext CHARACTER SET utf8mb4 DEFAULT NULL
_sql

); ze\dbAdm::revision(256
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_USER_FORMS_PREFIX]]user_forms`
	ADD COLUMN `send_email_to_admin_condition` enum('always_send','send_on_condition') NOT NULL DEFAULT 'always_send' AFTER `send_email_to_admin`,
	ADD COLUMN `send_email_to_admin_condition_field` int(5) unsigned NULL AFTER `send_email_to_admin_condition`
_sql

);
