<?php
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

revision(19
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_CURRENCY_MANAGER_PREFIX]]currencies`
_sql

, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_CURRENCY_MANAGER_PREFIX]]currencies (
		`id` int(10) AUTO_INCREMENT,
		`english_name` varchar(32) NOT NULL, 
		`code` varchar(3) NOT NULL,
		`symbol_left` varchar(12) default NULL,
		`symbol_right` varchar(12) default NULL,
		`decimal_separator` char(1) default '.',
		`thousands_separator` char(1)  default ',',
		`decimal_places` TINYINT default 2,
		`rate` DECIMAL(10,4) NULL,
		`base_currency` tinyint DEFAULT 0,
		`rate_timestamp` datetime,
		PRIMARY KEY (`id`),
		UNIQUE (`code`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	INSERT IGNORE INTO [[DB_NAME_PREFIX]][[ZENARIO_CURRENCY_MANAGER_PREFIX]]currencies
		(`code`,`english_name`,`symbol_left`,`rate`,`base_currency`)
	VALUES
		('GBP','British Pound','£',1,1),
		('EUR','Euro','€',1,0),
		('USD','American Dollar','$',1,0)
_sql
);


revision(20
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_CURRENCY_MANAGER_PREFIX]]currencies
	  CHANGE `rate_timestamp` `last_updated_timestamp` datetime
_sql
);

revision(23
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_CURRENCY_MANAGER_PREFIX]]currencies
	  ADD COLUMN `date_rate_last_fetched` datetime
_sql
);