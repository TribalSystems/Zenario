<?php
//if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');
if (!defined('NOT_ACCESSED_DIRECTLY')) exit;

ze\dbAdm::revision(1
, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data(
	`id` int(10) NOT NULL AUTO_INCREMENT,
	`crm_url` varchar(255) NOT NULL,
	`name1` varchar(255)  NULL,
	`value1` varchar(255)  NULL,
	`name2` varchar(255)  NULL,
	`value2` varchar(255)  NULL,
	`name3` varchar(255)  NULL,
	`value3` varchar(255)  NULL,
	`name4` varchar(255)  NULL,
	`value4` varchar(255)  NULL,
	`name5` varchar(255)  NULL,
	`value5` varchar(255)  NULL,
	`enable_crm_integration` tinyint DEFAULT 0,
	PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
);

ze\dbAdm::revision(2
,  <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data
	  CHANGE `crm_url` `crm_url` varchar(255) NULL
_sql
);

ze\dbAdm::revision(4
,  <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data
	  ADD COLUMN `user_form_id` int(10)
_sql
);

ze\dbAdm::revision(5
, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data(
	`form_id` int(10),
	`crm_url` varchar(255) NULL,
	`custom_input_name_1` varchar(255)  NULL,
	`custom_input_value_1` varchar(255)  NULL,
	`custom_input_name_2` varchar(255)  NULL,
	`custom_input_value_2` varchar(255)  NULL,
	`custom_input_name_3` varchar(255)  NULL,
	`custom_input_value_3` varchar(255)  NULL,
	`custom_input_name_4` varchar(255)  NULL,
	`custom_input_value_4` varchar(255)  NULL,
	`custom_input_name_5` varchar(255)  NULL,
	`custom_input_value_5` varchar(255)  NULL,
	`enable_crm_integration` tinyint DEFAULT 0,
	PRIMARY KEY (`form_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
);

ze\dbAdm::revision(6
, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_fields
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_fields(
	`form_field_id` int(10),
	`custom_field` varchar(255),
	PRIMARY KEY (`form_field_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
);

ze\dbAdm::revision(7
, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_fields(
	`form_field_value_id` int(10),
	`form_field_id` int(10),
	`ordinal` int(10), 
	`name` varchar(255) NOT NULL,
	`value` varchar(255) NOT NULL,
	PRIMARY KEY (`form_field_value_id`),
	KEY(`form_field_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
);

ze\dbAdm::revision(8
,  <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_fields
	  CHANGE `custom_field` `field_crm_name` varchar(255) NULL
_sql
);

ze\dbAdm::revision(9
, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values(
	`form_field_value_dataset_id` int(10),
	`form_field_value_unlinked_id` int(10),
	`form_field_id` int(10),
	`value` varchar(255),
	PRIMARY KEY (`form_field_id`),
	KEY(`form_field_value_dataset_id`),
	KEY(`form_field_value_unlinked_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
);

ze\dbAdm::revision(10
, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values(
	`id` int(10) NOT NULL AUTO_INCREMENT,
	`form_field_value_dataset_id` int(10),
	`form_field_value_unlinked_id` int(10),
	`form_field_id` int(10),
	`value` varchar(255),
	PRIMARY KEY (`id`),
	KEY(`form_field_value_dataset_id`),
	KEY(`form_field_value_unlinked_id`),
	KEY(`form_field_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
);

ze\dbAdm::revision(15
, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_fields
	ADD COLUMN `ordinal` int(10) unsigned NOT NULL DEFAULT 1
_sql

);

// Update existing ordinals
if (ze\dbAdm::needRevision(16)) {
	// Get forms
	$forms = ze\row::getAssocs(ZENARIO_USER_FORMS_PREFIX . 'user_forms', 'id');
	foreach($forms as $formId) {
		// Get form fields
		$formFields = ze\row::getValues(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', 'id', ['user_form_id' => $formId]);
		// Set correct ordinal for form fields
		$fieldNameCount = [];
		foreach ($formFields as $formFieldId) {
			// Get crm name
			if ($CRMField = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields', ['field_crm_name', 'ordinal'], $formFieldId)) {
				if (!isset($fieldNameCount[$CRMField['field_crm_name']])) {
					$fieldNameCount[$CRMField['field_crm_name']] = 1;
				} else {
					$ord = ++$fieldNameCount[$CRMField['field_crm_name']];
					ze\row::update(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields', ['ordinal' => $ord], ['form_field_id' => $formFieldId]);
				}
			}
		}
	}
	ze\dbAdm::revision(16);
}

ze\dbAdm::revision(18
, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values
	ADD COLUMN form_field_value_centralised_key varchar(255) AFTER form_field_value_unlinked_id
_sql

);

ze\dbAdm::revision(19
, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values
	ADD COLUMN form_field_value_checkbox_state tinyint(1) DEFAULT NULL AFTER form_field_value_centralised_key
_sql

);

// Delete any bad data
if (ze\dbAdm::needRevision(23)) {
	ze\row::delete(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_fields', ['field_crm_name' => '']);
	ze\dbAdm::revision(23);
}

ze\dbAdm::revision(24
, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]last_crm_requests
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]last_crm_requests (
		`form_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
		`url` TEXT,
		`request` MEDIUMTEXT,
		`datetime` DATETIME NOT NULL,
		PRIMARY KEY (`form_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); ze\dbAdm::revision(25
, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_static_inputs
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_static_inputs (
		`form_id` int(10) UNSIGNED NOT NULL,
		`ord` int(10) UNSIGNED NOT NULL DEFAULT 1,
		`name` varchar(255) DEFAULT NULL,
		`value` varchar(255) DEFAULT NULL,
		PRIMARY KEY (`form_id`, `ord`),
		KEY(`form_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

);

if (ze\dbAdm::needRevision(26)) {
	$sql = '
		SELECT form_id, custom_input_name_1, custom_input_value_1, custom_input_name_2, custom_input_value_2, custom_input_name_3, custom_input_value_3, custom_input_name_4, custom_input_value_4, custom_input_name_5, custom_input_value_5
		FROM ' . DB_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_data';
	$result = ze\sql::select($sql);
	while ($row = ze\sql::fetchAssoc($result)) {
		$ord = 0;
		for ($i = 1; $i <= 5; $i++) {
			if ($row['custom_input_name_' . $i]) {
				ze\row::insert(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_static_inputs', ['form_id' => $row['form_id'], 'ord' => ++$ord, 'name' => $row['custom_input_name_' . $i], 'value' => $row['custom_input_value_' . $i]]);
			}
		}
	}
	ze\dbAdm::revision(26);
}

ze\dbAdm::revision(27
, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data
	DROP COLUMN `custom_input_name_1`,
	DROP COLUMN `custom_input_value_1`,
	DROP COLUMN `custom_input_name_2`,
	DROP COLUMN `custom_input_value_2`,
	DROP COLUMN `custom_input_name_3`,
	DROP COLUMN `custom_input_value_3`,
	DROP COLUMN `custom_input_name_4`,
	DROP COLUMN `custom_input_value_4`,
	DROP COLUMN `custom_input_name_5`,
	DROP COLUMN `custom_input_value_5`
_sql

//Big update to make this module, salesforce and mailchimp apis more friendly
); ze\dbAdm::revision(30
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_link`
_sql
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_link` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`form_id` int(10) unsigned NOT NULL,
		`crm_id` varchar(255) NOT NULL DEFAULT 'generic',
		`enable` tinyint(1) NOT NULL DEFAULT 0,
		`url` varchar(255) DEFAULT NULL,
		PRIMARY KEY (`id`),
		KEY (`form_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]static_crm_values`
_sql
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]static_crm_values` (
		`link_id` int(10) unsigned NOT NULL,
		`ord` int(10) unsigned NOT NULL DEFAULT 1,
		`name` varchar(255) NOT NULL,
		`value` varchar(255) NOT NULL,
		PRIMARY KEY (`link_id`, `ord`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]crm_fields`
_sql
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]crm_fields` (
		`form_field_id` int(10) unsigned NOT NULL DEFAULT 0,
		`name` varchar(255) NOT NULL,
		`ord` int(10) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY (`form_field_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]crm_field_values`
_sql
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]crm_field_values` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`form_field_value_dataset_id` int(10) DEFAULT NULL,
		`form_field_value_unlinked_id` int(10) DEFAULT NULL,
		`form_field_value_centralised_key` varchar(255) DEFAULT NULL,
		`form_field_value_checkbox_state` tinyint(1) DEFAULT NULL,
		`form_field_id` int(10) DEFAULT NULL,
		`value` varchar(255) DEFAULT NULL,
		PRIMARY KEY (`id`),
		KEY (`form_field_value_dataset_id`),
		KEY (`form_field_value_unlinked_id`),
		KEY (`form_field_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Migrate data
, <<<_sql
	INSERT INTO `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_link`
	SELECT NULL, form_id, 'generic', enable_crm_integration, crm_url
	FROM `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data`
_sql

, <<<_sql
	INSERT INTO `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]static_crm_values`
	SELECT 
		(
			SELECT l.id
			FROM `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_link` l
			WHERE l.form_id = s.form_id
			AND l.crm_id = 'generic'
		), 
		s.ord, 
		s.name, 
		s.value
	FROM `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_static_inputs` s
_sql

, <<<_sql
	INSERT INTO `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]crm_fields`
	SELECT *
	FROM `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_fields`
_sql

, <<<_sql
	INSERT INTO `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]crm_field_values`
	SELECT *
	FROM `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]last_crm_requests`
	CHANGE `form_id` `link_id` int(10) unsigned NOT NULL DEFAULT 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]last_crm_requests`
	DROP PRIMARY KEY
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]last_crm_requests` r
	INNER JOIN `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_link` l
		ON r.link_id = l.form_id
		AND l.crm_id = 'generic'
	SET r.link_id = l.id
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]last_crm_requests`
	ADD PRIMARY KEY (`link_id`)
_sql

); ze\dbAdm::revision(31

, <<<_sql
	DROP TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data`
_sql
, <<<_sql
	DROP TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values`
_sql
, <<<_sql
	DROP TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_fields`
_sql
, <<<_sql
	DROP TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_static_inputs`
_sql

); ze\dbAdm::revision(32

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]salesforce_data`
_sql
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]salesforce_data` (
		`form_id` int(10) UNSIGNED NOT NULL,
		`s_object` varchar(255) NOT NULL DEFAULT 'Case',
		PRIMARY KEY (`form_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]salesforce_response_log
_sql

, <<<_sql
	CREATE TABLE [[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]salesforce_response_log (
		`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		`datetime` datetime NOT NULL,
		`form_id` int(10) UNSIGNED NOT NULL,
		`response_id` int(10) UNSIGNED NOT NULL,
		`oauth_status` varchar(255) DEFAULT NULL,
		`oauth_response` text,
		`salesforce_status` varchar(255) DEFAULT NULL,
		`salesforce_response` text,
		PRIMARY KEY (`id`),
		KEY (`form_id`),
		KEY (`response_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

);

//Pull across data from salesforce module if it's running
//(N.b. This is a little messy because there was a migration written in the salesforce module before it was decided 
//to merge it into this module which may or may not have already run)
if (ze\dbAdm::needRevision(36) && ze\module::inc('zenario_salesforce_api_form_integration')) {
	if (ze\sql::numRows('SHOW TABLES LIKE "'. DB_PREFIX . ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX . 'form_crm_link"')) {

ze\dbAdm::revision(36
//form_crm_link
, <<<_sql
	INSERT INTO `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_link`
	SELECT NULL, form_id, 'salesforce', enable_crm_integration, ''
	FROM `[[DB_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]form_crm_link`
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]salesforce_data`
	SELECT 
		scf.form_id,
		scf.s_object
	FROM `[[DB_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]form_crm_link` scf
_sql

//form_crm_static_inputs
, <<<_sql
	INSERT INTO `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]static_crm_values`
	SELECT
		(
			SELECT cf.id
			FROM `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_link` cf
			WHERE cf.form_id = s.form_id
			AND cf.crm_id = 'salesforce'
		),
		ord,
		name,
		value
	FROM `[[DB_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]form_crm_static_inputs` s
_sql


//form_crm_fields
, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]crm_fields`
	SELECT *
	FROM `[[DB_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]form_crm_fields`
_sql

//form_crm_field_values
, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]crm_field_values`
	SELECT *
	FROM `[[DB_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]form_crm_field_values`
_sql

);
	} else {

ze\dbAdm::revision(36
, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]salesforce_data`
	SELECT *
	FROM `[[DB_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]salesforce_data`
_sql

);
		
	}
}
if (ze\dbAdm::needRevision(37) && ze\module::inc('zenario_salesforce_api_form_integration')) {
	ze\site::setSetting("zenario_salesforce_api_form_integration__enable", true);
	ze\dbAdm::revision(37

, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]salesforce_response_log`
	SELECT *
	FROM `[[DB_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]salesforce_response_log`
_sql

	);
}

//Resume normal updates...
ze\dbAdm::revision(38
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]mailchimp_data`
_sql
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]mailchimp_data` (
		`form_id` int(10) UNSIGNED NOT NULL,
		`mailchimp_list_id` varchar(255) NOT NULL,
		PRIMARY KEY (`form_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Table to store 360lifecycle form specific data
); ze\dbAdm::revision(40
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]360lifecycle_data`
_sql
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]360lifecycle_data` (
		`form_id` int(10) UNSIGNED NOT NULL,
		`opportunity_advisor` varchar(255) NOT NULL DEFAULT '',
		`opportunity_lead_source` varchar(255) NOT NULL DEFAULT '',
		`opportunity_lead_type` varchar(255) NOT NULL DEFAULT '',
		PRIMARY KEY (`form_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
	
//Added a column for consent dropdown on malchimp	
); ze\dbAdm::revision(41
,  <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]mailchimp_data`
	DROP COLUMN `consent_dropdown`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]mailchimp_data`
	ADD COLUMN `consent_field` int(5) NOT NULL DEFAULT '0'
_sql

);
