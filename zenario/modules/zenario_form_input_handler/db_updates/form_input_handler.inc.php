<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

revision(10
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_FORM_INPUT_HANDLER_PREFIX]]form_submissions (
		`id` int(10) AUTO_INCREMENT NOT NULL,
		`plugin_instance_name` VARCHAR(255) NOT NULL,
		`plugin_instance_id` int(10) NOT NULL,
		`submission_datetime` datetime NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
,
<<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_FORM_INPUT_HANDLER_PREFIX]]form_submission_data (
		`submission_id` int(10) NOT NULL,
		`ordinal` SMALLINT NOT NULL,
		`label` TEXT,
		`value` TEXT,
		PRIMARY KEY (`submission_id`,`ordinal`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
);
revision(342
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_FORM_INPUT_HANDLER_PREFIX]]routing_rules (
		`id` int(10) AUTO_INCREMENT NOT NULL,
		`set_id` int(10) NOT NULL,
		`rule_name` VARCHAR(255),
		`rule_type_name` VARCHAR(255) NOT NULL,
		`rule_config` TEXT,
		`object_name` VARCHAR(255) NOT NULL,
		`matching_value` VARCHAR(255) NOT NULL,
		`email_template_number` smallint(10),
		`destination_address` VARCHAR(255),
		`return_address` VARCHAR(255),
		`email_from_name_1` VARCHAR(255),
		`subject_1` VARCHAR(255),
		`template_1` TEXT,
		`destination_address_2` VARCHAR(255),
		`return_address_2` VARCHAR(255),
		`email_from_name_2` VARCHAR(255),
		`subject_2` VARCHAR(255),
		`template_2` TEXT,
		`destination_address_3` VARCHAR(255),
		`return_address_3` VARCHAR(255),
		`email_from_name_3` VARCHAR(255),
		`subject_3` VARCHAR(255),
		`template_3` TEXT,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
);
revision(373
, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_FORM_INPUT_HANDLER_PREFIX]]routing_rules
	CHANGE column `set_id` `rule_set_id` int(10) NOT NULL,
	DROP COLUMN `rule_name`,
	DROP COLUMN `rule_config`,
	DROP COLUMN	`return_address`,
	DROP COLUMN	`email_from_name_1`,
	DROP COLUMN	`subject_1`,
	DROP COLUMN	`template_1`,
	DROP COLUMN	`destination_address_2`,
	DROP COLUMN	`return_address_2`,
	DROP COLUMN	`email_from_name_2`,
	DROP COLUMN	`subject_2`,
	DROP COLUMN	`template_2`,
	DROP COLUMN	`destination_address_3`,
	DROP COLUMN	`return_address_3`,
	DROP COLUMN	`email_from_name_3`,
	DROP COLUMN	`subject_3`,
	DROP COLUMN	`template_3`
_sql
,
<<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_FORM_INPUT_HANDLER_PREFIX]]rule_sets (
		`id` INT(10) AUTO_INCREMENT,
		`name` VARCHAR (255), 
		`creation_datetime` DATETIME,
		`modification_datetime` DATETIME,
		`last_rule_matching_datetime` DATETIME,
		PRIMARY KEY(`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
);
revision(400
,<<<_sql
ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_FORM_INPUT_HANDLER_PREFIX]]form_submissions
ADD COLUMN `user_id` int(10) unsigned,
ADD COLUMN `content_id` int(10) unsigned,
ADD COLUMN `content_type` varchar(20),
ADD COLUMN `content_version` int(10) unsigned,
ADD COLUMN `ip_address` varchar(15)
_sql
,
<<<_sql
ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_FORM_INPUT_HANDLER_PREFIX]]form_submission_data
ADD COLUMN `attachment` MEDIUMBLOB
_sql
);

revision(431
,<<<_sql
ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_FORM_INPUT_HANDLER_PREFIX]]form_submissions
ADD COLUMN `user_id` int(10) unsigned,
ADD COLUMN `content_id` int(10) unsigned,
ADD COLUMN `content_type` varchar(20),
ADD COLUMN `content_version` int(10) unsigned,
ADD COLUMN `ip_address` varchar(15)
_sql
,
<<<_sql
ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_FORM_INPUT_HANDLER_PREFIX]]form_submissions
MODIFY COLUMN `ip_address` varchar(50)
_sql
);
