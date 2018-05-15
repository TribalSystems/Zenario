<?php
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


//Fix plugins with broken settings.
ze\dbAdm::revision(6
,<<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings`
	SET value = "documents"
	WHERE name = "container_mode" AND value = ""
_sql

);