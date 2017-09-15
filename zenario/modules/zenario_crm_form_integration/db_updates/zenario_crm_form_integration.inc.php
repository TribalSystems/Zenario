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

revision(15
, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_fields
	ADD COLUMN `ordinal` int(10) unsigned NOT NULL DEFAULT 1
_sql

);

// Update existing ordinals
if (needRevision(16)) {
	// Get forms
	$forms = getRowsArray(ZENARIO_USER_FORMS_PREFIX . 'user_forms', 'id');
	foreach($forms as $formId) {
		// Get form fields
		$formFields = getRowsArray(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', 'id', array('user_form_id' => $formId));
		// Set correct ordinal for form fields
		$fieldNameCount = array();
		foreach ($formFields as $formFieldId) {
			// Get crm name
			if ($CRMField = getRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields', array('field_crm_name', 'ordinal'), $formFieldId)) {
				if (!isset($fieldNameCount[$CRMField['field_crm_name']])) {
					$fieldNameCount[$CRMField['field_crm_name']] = 1;
				} else {
					$ord = ++$fieldNameCount[$CRMField['field_crm_name']];
					updateRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields', array('ordinal' => $ord), array('form_field_id' => $formFieldId));
				}
			}
		}
	}
	revision(16);
}

revision(18
, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values
	ADD COLUMN form_field_value_centralised_key varchar(255) AFTER form_field_value_unlinked_id
_sql

);

revision(19
, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_field_values
	ADD COLUMN form_field_value_checkbox_state tinyint(1) DEFAULT NULL AFTER form_field_value_centralised_key
_sql

);

// Delete any bad data
if (needRevision(23)) {
	deleteRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_fields', array('field_crm_name' => ''));
	revision(23);
}

revision(24
, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]last_crm_requests
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]last_crm_requests (
		`form_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
		`url` TEXT,
		`request` MEDIUMTEXT,
		`datetime` DATETIME NOT NULL,
		PRIMARY KEY (`form_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision(25
, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_static_inputs
_sql
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_static_inputs (
		`form_id` int(10) UNSIGNED NOT NULL,
		`ord` int(10) UNSIGNED NOT NULL DEFAULT 1,
		`name` varchar(255) DEFAULT NULL,
		`value` varchar(255) DEFAULT NULL,
		PRIMARY KEY (`form_id`, `ord`),
		KEY(`form_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

);

if (needRevision(26)) {
	$sql = '
		SELECT form_id, custom_input_name_1, custom_input_value_1, custom_input_name_2, custom_input_value_2, custom_input_name_3, custom_input_value_3, custom_input_name_4, custom_input_value_4, custom_input_name_5, custom_input_value_5
		FROM ' . DB_NAME_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_data';
	$result = sqlSelect($sql);
	while ($row = sqlFetchAssoc($result)) {
		$ord = 0;
		for ($i = 1; $i <= 5; $i++) {
			if ($row['custom_input_name_' . $i]) {
				insertRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_static_inputs', ['form_id' => $row['form_id'], 'ord' => ++$ord, 'name' => $row['custom_input_name_' . $i], 'value' => $row['custom_input_value_' . $i]]);
			}
		}
	}
	revision(26);
}

revision(27
, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_CRM_FORM_INTEGRATION_PREFIX]]form_crm_data
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

);

