<?php
/*
 * Copyright (c) 2015, Tribal Limited
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






//This file handles updates which are key to how the updater works.
//They are the first to run

//This file also handles some important updates to the Modules table:
	//It renames it from "plugins" to "modules" if it is still called "pluigns" (from zenario 5)
	//It adds the class_name and display_name columns if they have not yet been created
//Note that all other column changes for the Modules tables are done as regular updates, as they don't affect the updater


//Create the table to store the local revision numbers.

revision( 2885
, <<<_sql
	CREATE TABLE IF NOT EXISTS `[[DB_NAME_PREFIX]]local_revision_numbers` (
		`module` varchar(20) NOT NULL,
		`revision_no` int(10) unsigned NOT NULL,
		PRIMARY KEY (`module`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//In zenario 5.0.12 we changed how the updater works to include modules.
//The path column needs to be added, and the module column should
//be renamed to patchfile
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]local_revision_numbers`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]local_revision_numbers`
	ADD COLUMN `path` varchar(255) NOT NULL FIRST
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]local_revision_numbers`
	CHANGE COLUMN `module` `patchfile` varchar(64) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]local_revision_numbers`
	ADD PRIMARY KEY (`path`, `patchfile`)
_sql

//Convert the format of existing entries
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]local_revision_numbers` SET
		`path` = 'admin/db_updates/core',
		`patchfile` = 'local.inc.php'
	WHERE `patchfile` = 'general.inc.php'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]local_revision_numbers` SET
		`path` = 'admin/db_updates/modules/microsite'
	WHERE `patchfile` = 'microsite.inc.php'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]local_revision_numbers` SET
		`path` = 'admin/db_updates/modules/workflow'
	WHERE `patchfile` = 'workflow.inc.php'
_sql


//Moved modules to their own revision numbers
);	revision( 2899
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]local_revision_numbers` SET
		`revision_no` = 0
	WHERE `path` = 'modules/calendar/db_updates'
	  AND `patchfile` = 'calendar.inc.php'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]local_revision_numbers` SET
		`revision_no` = 0
	WHERE `path = 'modules/picture_gallery/db_updates'
	  AND `patchfile` = 'picture_gallery.inc.php'
_sql


//Remove some junk revision numbers that may have entered the table
//during testing/changing the updater
);	revision( 2945
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]local_revision_numbers`
	WHERE (`path`, `patchfile`) IN (
		('', 'admin_tabs.inc.php'),
		('', 'pro.inc.php'),
		('admin/db_updates/admin', 'perms.inc.php'),
		('admin/db_updates/admin', 'tabs.inc.php'),
		('modules/howarewe_outlets/db_updates', 'admin_menu.inc.php')
	)
_sql


//Fix a problem where some revision numbers were incorrectly entered into 5.0.13a and 5.0.13b
);	revision( 3670
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]local_revision_numbers`
	SET `revision_no` = 3669
	WHERE `revision_no` = 3843
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]local_revision_numbers`
	SET `revision_no` = 3670
	WHERE `revision_no` = 4201
_sql


//Add display name column to the plugins table, defaulting it to the filename
//Also add version column
); revision( 4240
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugins`
	ADD COLUMN `display_name` varchar(255) NULL AFTER `name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugins`
	ADD COLUMN `version` varchar(32) NULL AFTER `display_name`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugins` SET
		display_name = name,
		version = '0.1'
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugins`
	MODIFY COLUMN `display_name` varchar(255) NOT NULL
_sql


//Add a class name column to the plugins table
);	revision( 5165
//Add the new column
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugins`
	ADD COLUMN `class_name` varchar(255) NULL AFTER `name`
_sql

//For developers with test versions, clear out any plugins in that look like junk
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]plugins`
	WHERE `name` NOT LIKE '%_v1'
_sql

//For developers with test versions, populate the class_name column from the name column
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugins` SET
		`class_name` = REPLACE(`name`, '_v1', '')
_sql

//Now it has been populated, make the column NOT NULL
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugins`
	MODIFY COLUMN `class_name` varchar(255) NOT NULL
_sql


//Add a class name column to a plugin
);	revision( 5165
//Add the new column
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugins`
	ADD COLUMN `class_name` varchar(255) NULL AFTER `name`
_sql

//For developers with test versions, clear out any plugins in that look like junk
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]plugins`
	WHERE `name` NOT LIKE '%_v1'
_sql

//For developers with test versions, populate the class_name column from the name column
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugins` SET
		`class_name` = REPLACE(`name`, '_v1', '')
_sql

//Now it has been populated, make the column NOT NULL
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugins`
	MODIFY COLUMN `class_name` varchar(255) NOT NULL
_sql


//Add a unique key for class name
);	revision( 5170
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugins`
	ADD UNIQUE (`class_name`, `version`)
_sql


//Drop the allowed size of the plugin class names down from 250 to 200
);	revision( 6540
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugins`
	MODIFY COLUMN `class_name` varchar(200) NOT NULL
_sql



//Handle some core table renames in zenario 6
//The plugins table has been retro-actively renamed to the modules table. This first time the migration scripts runs
//it will need to be immediately renamed, reguardless of what revision number the rest of the site is on
	//(Note that this is an exception for us; usually renames follow a nice chain if they can be
	//done without breaking the migration tools.)
);	revision( 15400
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]modules`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugins`
	RENAME TO `[[DB_NAME_PREFIX]]modules`
_sql


//Update the paths in the local_revision_numbers table to refer to Modules and not Plugins
	//(Note that this may be too late for the first sweep of the table;
	//there's also code in getAllCurrentRevisionNumbers() to handle that this might not have been done yet
);	revision( 15500
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]local_revision_numbers`
	SET `path` = CONCAT('modules/', substr(`path`, 9))
	WHERE `path` LIKE 'plugins/%'
_sql



//Add an index on the revision_no column
);	revision( 15800
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]local_revision_numbers`
	ADD INDEX (`revision_no`)
_sql


//Add a default value to the site settings table
	//Note there are also more column changes done to the site settings table as regular updates
);	revision( 20700
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings`
	ADD COLUMN `default_value` varchar(255) NOT NULL default ''
	AFTER `value`
_sql


//Forcebly run the new Anonymous Comments Module if the Comments Module is running
);	revision( 20785
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]modules` (
		`name`,
		`class_name`,
		`display_name`,
		`status`
	) SELECT
		'zenario_anonymous_comments_v1',
		'zenario_anonymous_comments',
		'Anonymous Comments',
		`status`
	FROM `[[DB_NAME_PREFIX]]modules` AS c
	WHERE `name` = 'zenario_comments_v1'
	ON DUPLICATE KEY UPDATE
		`status` = c.`status`
_sql


//Copy the database update record of the Comments Module to the Anonymous Comments Module as well
);	revision( 20790
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]local_revision_numbers` (
		path, patchfile, revision_no
	) SELECT
		'modules/zenario_anonymous_comments_v1/db_updates',
		patchfile,
		revision_no
	FROM `[[DB_NAME_PREFIX]]local_revision_numbers`
	WHERE path = 'modules/zenario_comments_v1/db_updates'
_sql
);























		//					 //
		//  Changes for 6.1  //
		//					 //




//Moving Transaction Manager tables to E-Commerce Manager
		revision( 20959
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]local_revision_numbers` (
		path, patchfile, revision_no
	) SELECT
		'modules/zenario_ecommerce_manager_v1/db_updates',
		patchfile,
		revision_no
	FROM `[[DB_NAME_PREFIX]]local_revision_numbers`
	WHERE path = 'modules/zenario_transaction_manager_v1/db_updates'
	  AND patchfile = 'transaction_manager.inc.php'
_sql
);


//Rename all Module directories to remove the "_v1" prefix, so that the class name is now the same as the directory name.
//And remove the Module's "name" column, as this is now the same as the class name column
	revision( 21035
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	DROP KEY `name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	DROP KEY `class_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD UNIQUE KEY (`class_name`)
_sql

//Note that we'll actually drop the column in zenario/admin/db_updates/core/local.inc.php and not here,
//as some old statements in zenario/admin/db_updates/core/local.inc.php may rely on the column being there


//Update the directory names from the local revision numbers table
);	revision( 21040
	//Delete some bad data from an old Module that may cause errors
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]local_revision_numbers`
	WHERE path LIKE '%zenario_ecommerce_bundle_manager%'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]local_revision_numbers`
	SET path = SUBSTR(path, 1, LENGTH(path)-3)
	WHERE path LIKE '%_v1'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]local_revision_numbers`
	SET path = REPLACE(path, '_v1/', '/')
	WHERE path LIKE '%_v1/%'
_sql

//Clean up all old records from local_revision_numbers where the description.xml files were in the wrong location
//(Note that there's no need to migrate this data as description.xml doesn't have individual revisions where we need
//to remember which has been applied.)
);	revision( 21070
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]local_revision_numbers`
	WHERE patchfile = 'description.xml'
_sql


//Forcebly run the new Users Module if the Pro Features Module was running
);	revision( 22610
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]modules` (
		`class_name`,
		`display_name`,
		`status`
	) SELECT
		'zenario_users',
		'Users',
		`status`
	FROM `[[DB_NAME_PREFIX]]modules` AS c
	WHERE `class_name` = 'zenario_users'
	ON DUPLICATE KEY UPDATE
		`status` = c.`status`
_sql


//Copy the database update record of the two Smart Group tables in Pro Module to the core
);	revision( 22620
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]local_revision_numbers` (
		path, patchfile, revision_no
	) SELECT
		'admin/db_updates/core',
		'smart_groups.inc.php',
		revision_no
	FROM `[[DB_NAME_PREFIX]]local_revision_numbers`
	WHERE path = 'modules/zenario_pro_features/db_updates'
	  AND patchfile = 'smart_groups.inc.php'
_sql
);

//Move the two Smart Group tables from Pro into the Core if they have been created
if (needRevision(22630)) {
	
	if (($module = getRow('modules', array('class_name', 'id'), array('class_name' => 'zenario_pro_features')))
	 && ($oldPrefix = setModulePrefix($module, false, false))) {
		$newPrefix = '';
		$len = strlen(DB_NAME_PREFIX. $oldPrefix);

		//Check to see if any tables have been created with this prefix
		$result = sqlSelect("SHOW TABLES LIKE '". DB_NAME_PREFIX. likeEscape($oldPrefix). "%'");
		while ($table = sqlFetchRow($result)) {
			//Get the table name from the prefix
			$table[0] = substr($table[0], $len);
				
			//Get current table name (should be the same as the value we've just had).
			$oldTable = DB_NAME_PREFIX. $oldPrefix. sqlEscape($table[0]);
			$newTable = DB_NAME_PREFIX. $newPrefix. sqlEscape($table[0]);
			
			if ($newTable != $oldTable) {
				//Rename the table (dropping anything with the new name if it exists, just in case we're restoring an old backup).
				$sql = "
					DROP TABLE IF EXISTS `". $newTable. "`";
				sqlSelect($sql);
				
				$sql = "
					ALTER TABLE `". $oldTable. "`
					RENAME TO `". $newTable. "`";
				sqlSelect($sql);
			}
		}
	}

	revision(22630);
}























		//					 //
		//  Changes for 7.0  //
		//					 //




//Rename any modules called "tribiq_" to "zenario_"
if (needRevision(25490)) {
	$sql = "
		UPDATE `". DB_NAME_PREFIX. "local_revision_numbers`
		SET `path` = REPLACE(`path`, 'tribiq_', 'zenario_')";
	sqlSelect($sql);
	
	$sql = "
		UPDATE `". DB_NAME_PREFIX. "modules`
		SET `class_name` = REPLACE(`class_name`, 'tribiq_', 'zenario_')";
	sqlSelect($sql);
	
	//Also update the "name" column if it still exists
	if (($sql = "SHOW COLUMNS IN `". DB_NAME_PREFIX. "modules` LIKE 'name'")
	 && ($result = sqlQuery($sql))
	 && (sqlFetchRow($result))) {
		$sql = "
			UPDATE `". DB_NAME_PREFIX. "modules`
			SET `name` = REPLACE(`name`, 'tribiq_', 'zenario_')";
		sqlSelect($sql);
	}
	
	revision(25490);
}


//Handle the table prefix for Modules being changed.
if (needRevision(25500)) {
	
	//Loop through every running or suspending Module
	foreach (getModules(null, null, $dbUpdateSafemode = true) as $module) {
		foreach (array(2, 1) as $oldFormat) {
			//Get what the old prefix and the new prefix should be
			$oldPrefix = setModulePrefix($module, false, $oldFormat);
			$newPrefix = setModulePrefix($module, false, false);
			$len = strlen(DB_NAME_PREFIX. $oldPrefix);
		
			//Check to see if any tables have been created with this prefix
			$result = sqlSelect("SHOW TABLES LIKE '". DB_NAME_PREFIX. likeEscape($oldPrefix). "%'");
			while ($table = sqlFetchRow($result)) {
				//Get the table name from the prefix
				$table[0] = substr($table[0], $len);
			
				//Get current table name (should be the same as the value we've just had).
				$oldTable = DB_NAME_PREFIX. $oldPrefix. sqlEscape($table[0]);
			
				//Get the new table name with the new prefix
					//Special case: Move the user_comments and comment_content_items tables from being handled by the
					//Comments Module to being handled by the Anonymous Comments Module.
				if ($module['class_name'] == 'zenario_comments'
				 && ($table[0] == 'comment_content_items' || $table[0] == 'user_comments')
				 && ($anonCommentsModule = getModuleIdByClassName('zenario_anonymous_comments'))
				 && ($anonCommentsModule = array('id' => $anonCommentsModule, 'class_name' => 'zenario_anonymous_comments'))
				 && ($anonCommentsModule = setModulePrefix($anonCommentsModule, false, false))) {
					//The comment_content_items and user_comments tables for the Comments Module should be
					//converted to the Anonymous Comments Module's prefix.
					$newTable = DB_NAME_PREFIX. $anonCommentsModule. sqlEscape($table[0]);
			
				} else
				if ($module['class_name'] == 'zenario_transaction_manager'
				 && ($table[0] == 'order_line_items'
				  || $table[0] == 'order_line_statuses'
				  || $table[0] == 'order_status_log'
				  || $table[0] == 'orders'
				  || $table[0] == 'payments'
				  || $table[0] == 'transactions')
				 && ($ecommerceManagerModule = getModuleIdByClassName('zenario_ecommerce_manager'))
				 && ($ecommerceManagerModule = array('id' => $ecommerceManagerModule, 'class_name' => 'zenario_ecommerce_manager'))
				 && ($ecommerceManagerModule = setModulePrefix($ecommerceManagerModule, false, false))) {
					//Moving Transaction Manager tables to E-Commerce Manager
					$newTable = DB_NAME_PREFIX. $ecommerceManagerModule. sqlEscape($table[0]);
				} else {
					$newTable = DB_NAME_PREFIX. $newPrefix. sqlEscape($table[0]);
				}
				
				if ($newTable != $oldTable) {
					//Rename the table (dropping anything with the new name if it exists, just in case we're restoring an old backup).
					$sql = "
						DROP TABLE IF EXISTS `". $newTable. "`";
					sqlSelect($sql);
			
					$sql = "
						ALTER TABLE `". $oldTable. "`
						RENAME TO `". $newTable. "`";
					sqlSelect($sql);
				}
			}
		}
	}
	
	revision(20960);
}