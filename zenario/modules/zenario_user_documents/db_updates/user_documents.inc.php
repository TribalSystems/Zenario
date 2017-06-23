<?php
if (!defined('NOT_ACCESSED_DIRECTLY')) exit;

revision( 3
,<<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_USER_DOCUMENTS_PREFIX]]user_documents
_sql
, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_USER_DOCUMENTS_PREFIX]]user_documents(
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`ordinal` int(10) NOT NULL,
		`type` enum('file','folder') NOT NULL DEFAULT 'file',
		`file_id` int(10) DEFAULT NULL,
		`folder_id` int(10) NOT NULL DEFAULT '0',
		`folder_name` varchar(255) DEFAULT NULL,
		`document_datetime` datetime DEFAULT NULL,
		`user_id` int(10) NOT NULL,
		`thumbnail_id` int(10) DEFAULT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `file_id` (`file_id`),
		KEY `ordinal` (`ordinal`),
		KEY `user_id` (`user_id`),
		KEY `type` (`type`),
		KEY `folder_id` (`folder_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
_sql
);
revision( 4
,<<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_USER_DOCUMENTS_PREFIX]]user_document_tag_link
_sql
, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_USER_DOCUMENTS_PREFIX]]user_document_tag_link (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`user_document_id` int(10) NOT NULL,
		`tag_id` int(10) NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `document_tag_link` (`user_document_id`,`tag_id`),
		KEY `user_document_id` (`user_document_id`),
		KEY `tag_id` (`tag_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
_sql
);
revision( 5
,<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_DOCUMENTS_PREFIX]]user_documents`
		DROP KEY `file_id`
_sql
,<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_DOCUMENTS_PREFIX]]user_documents`
		ADD UNIQUE KEY (`file_id`, `user_id`)
_sql
);
revision( 6
,<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_DOCUMENTS_PREFIX]]user_documents`
	ADD COLUMN `title` varchar(255) DEFAULT NULL
_sql

//Attempt to convert some columns with a utf8-3-byte character set to a 4-byte character set
);	revision( 20
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_USER_DOCUMENTS_PREFIX]]user_documents` SET `folder_name` = SUBSTR(`folder_name`, 1, 250) WHERE CHAR_LENGTH(`folder_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_USER_DOCUMENTS_PREFIX]]user_documents` MODIFY COLUMN `folder_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql

); 