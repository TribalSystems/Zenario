<?php

ze\dbAdm::revision( 2
, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_ERROR_LOG_PREFIX]]error_log (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		logged datetime NOT NULL,
		referrer_url varchar(255) NOT NULL DEFAULT '',
		page_alias varchar(255) NOT NULL,
		PRIMARY KEY (id)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); ze\dbAdm::revision( 7
, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_ERROR_LOG_PREFIX]]error_log
	MODIFY COLUMN referrer_url text
_sql

//Attempt to convert some columns with a utf8-3-byte character set to a 4-byte character set
);	ze\dbAdm::revision( 20
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_ERROR_LOG_PREFIX]]error_log` MODIFY COLUMN `referrer_url` text CHARACTER SET utf8mb4 NULL
_sql
);