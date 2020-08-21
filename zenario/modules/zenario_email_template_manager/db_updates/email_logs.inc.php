<?php
/*
 * Copyright (c) 2020, Tribal Limited
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
ze\dbAdm::revision(1,
<<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log
_sql

, <<<_sql
	CREATE TABLE [[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		plugin_id int(10) ,
		plugin_instance_id int(10) ,
		content_id int(10) ,
		content_type varchar(25) ,
		content_version int(10),
		email_template_id int(10) NOT NULL,
		email_template_name varchar(255) NOT NULL,
		email_subject varchar(255),
		email_address_to varchar(255) NOT NULL,
		email_address_from varchar(255) NOT NULL,
		email_name_from varchar(255),
		email_body TEXT,
		sent_timestamp datetime NOT NULL,			
	PRIMARY KEY (`id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql
);
ze\dbAdm::revision(47,
<<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log 
	CHANGE COLUMN sent_timestamp sent_datetime datetime NOT NULL
_sql
);
ze\dbAdm::revision(50,
<<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log 
	CHANGE COLUMN email_template_id email_template_id int(10) NULL
_sql
);
ze\dbAdm::revision(51,
<<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log 
	CHANGE COLUMN email_template_name email_template_name varchar(255) NULL
_sql
);
ze\dbAdm::revision(54
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log`
	CHANGE COLUMN `plugin_id` `module_id` int(10) unsigned NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log`
	CHANGE COLUMN `plugin_instance_id` `instance_id` int(10) unsigned NULL
_sql
);
ze\dbAdm::revision(57
, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log 
	ADD COLUMN email_address_to_overridden_by varchar(255) after email_address_to
_sql
);
ze\dbAdm::revision(58
, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log 
	ADD COLUMN attachment_present tinyint after `email_body`
_sql
);
ze\dbAdm::revision(95
, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log 
	ADD COLUMN `status` enum('success','failure') DEFAULT 'success' AFTER `sent_datetime` 
_sql
);

ze\dbAdm::revision(98
, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log
	ADD COLUMN email_address_replyto varchar(255) NOT NULL after `email_address_from`
_sql
);

ze\dbAdm::revision(99
, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log
	MODIFY COLUMN email_address_replyto varchar(255)
_sql
);

ze\dbAdm::revision(100
, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log
	ADD COLUMN email_name_replyto varchar(255)
_sql


//Fixed a bug where emails larger than 64K caused a db error when being logged in the email template manager
);	ze\dbAdm::revision( 121
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log`
	MODIFY COLUMN `email_body` MEDIUMTEXT
_sql

//Attempt to convert some columns with a utf8-3-byte character set to a 4-byte character set
);	ze\dbAdm::revision( 130
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` MODIFY COLUMN `content_type` varchar(25) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` SET `email_address_from` = SUBSTR(`email_address_from`, 1, 250) WHERE CHAR_LENGTH(`email_address_from`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` MODIFY COLUMN `email_address_from` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` SET `email_address_replyto` = SUBSTR(`email_address_replyto`, 1, 250) WHERE CHAR_LENGTH(`email_address_replyto`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` MODIFY COLUMN `email_address_replyto` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` SET `email_address_to` = SUBSTR(`email_address_to`, 1, 250) WHERE CHAR_LENGTH(`email_address_to`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` MODIFY COLUMN `email_address_to` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` SET `email_address_to_overridden_by` = SUBSTR(`email_address_to_overridden_by`, 1, 250) WHERE CHAR_LENGTH(`email_address_to_overridden_by`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` MODIFY COLUMN `email_address_to_overridden_by` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` MODIFY COLUMN `email_body` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` SET `email_name_from` = SUBSTR(`email_name_from`, 1, 250) WHERE CHAR_LENGTH(`email_name_from`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` MODIFY COLUMN `email_name_from` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` SET `email_name_replyto` = SUBSTR(`email_name_replyto`, 1, 250) WHERE CHAR_LENGTH(`email_name_replyto`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` MODIFY COLUMN `email_name_replyto` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` SET `email_subject` = SUBSTR(`email_subject`, 1, 250) WHERE CHAR_LENGTH(`email_subject`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` MODIFY COLUMN `email_subject` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` SET `email_template_name` = SUBSTR(`email_template_name`, 1, 250) WHERE CHAR_LENGTH(`email_template_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log` MODIFY COLUMN `email_template_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
);

//Rename a site-setting
if (ze\dbAdm::needRevision(132)) {
	$days = ze::setting('period_to_delete_the_email_template_sending_log');
	if ($days) {
		ze\site::setSetting('period_to_delete_the_email_template_sending_log_headers', $days);
		ze\site::setSetting('period_to_delete_the_email_template_sending_log_content', $days);
	}
	ze\dbAdm::revision(132);
}

ze\dbAdm::revision(135
, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log
	ADD COLUMN email_ccs varchar(255) DEFAULT NULL
_sql

);