<?php

revision( 2
, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_ERROR_LOG_PREFIX]]error_log (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		logged datetime NOT NULL,
		referrer_url varchar(255) NOT NULL DEFAULT '',
		page_alias varchar(255) NOT NULL,
		PRIMARY KEY (id)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
);
