<?php
//if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');
if (!defined('NOT_ACCESSED_DIRECTLY')) exit;

ze\dbAdm::revision(2
, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]form_crm_link
_sql
, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]form_crm_link(
		`form_id` int(10),
		`enable_crm_integration` tinyint DEFAULT 0,
		PRIMARY KEY (`form_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]form_crm_static_inputs
_sql

, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]form_crm_static_inputs (
		`form_id` int(10) UNSIGNED NOT NULL,
		`ord` int(10) UNSIGNED NOT NULL DEFAULT 1,
		`name` varchar(255) DEFAULT NULL,
		`value` varchar(255) DEFAULT NULL,
		PRIMARY KEY (`form_id`, `ord`),
		KEY(`form_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); ze\dbAdm::revision(3
, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]form_crm_fields
_sql

, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]form_crm_fields(
		`form_field_id` int(10) NOT NULL DEFAULT '0',
		`field_crm_name` varchar(255) DEFAULT NULL,
		`ordinal` int(10) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY (`form_field_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]form_crm_field_values
_sql

, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]form_crm_field_values(
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`form_field_value_dataset_id` int(10) DEFAULT NULL,
		`form_field_value_unlinked_id` int(10) DEFAULT NULL,
		`form_field_value_centralised_key` varchar(255) DEFAULT NULL,
		`form_field_value_checkbox_state` tinyint(1) DEFAULT NULL,
		`form_field_id` int(10) DEFAULT NULL,
		`value` varchar(255) DEFAULT NULL,
		PRIMARY KEY (`id`),
		KEY `form_field_value_dataset_id` (`form_field_value_dataset_id`),
		KEY `form_field_value_unlinked_id` (`form_field_value_unlinked_id`),
		KEY `form_field_id` (`form_field_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); ze\dbAdm::revision(5
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]form_crm_link
	ADD COLUMN `s_object` varchar(255) NOT NULL DEFAULT 'Case'
_sql

); ze\dbAdm::revision(6
, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]salesforce_response_log
_sql

, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_SALESFORCE_API_FORM_INTEGRATION_PREFIX]]salesforce_response_log(
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



