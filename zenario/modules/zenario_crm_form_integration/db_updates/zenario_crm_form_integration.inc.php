<?php
//if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');
if (!defined('NOT_ACCESSED_DIRECTLY')) exit;

revision(1
, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data(
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


revision(2
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data
	  CHANGE `crm_url` `crm_url` varchar(255) NULL
_sql
);

revision(4
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data
	  ADD COLUMN `user_form_id` int(10)
_sql
);



revision(5
, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data(
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

revision(6
, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_fields
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_fields(
	`form_field_id` int(10),
	`custom_field` varchar(255),
	PRIMARY KEY (`form_field_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
);

revision(7
, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_fields(
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

revision(8
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_fields
	  CHANGE `custom_field` `field_crm_name` varchar(255) NULL
_sql
);


revision(9
, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values(
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

revision(10
, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values(
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