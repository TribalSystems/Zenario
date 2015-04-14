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







//General Patch File

//Any updates that are not associated with a module
//	(i.e. updates that should apply to every version of the CMS)
//should be placed in here.

//Updates are applied using the revision function, which takes inputs in the following format:
	//The first input is the revision number
		//Revision numbers are used by the program as a way of tracking whether the revision
		//has already been applied.
		//For example, if the database has recorded in it that it's current revision number
		//for zenario/admin/db_updates/local/local.inc.php is revision #2390, then only revisions
		//numbered 2391 or higher will be run.
		//The following rules apply for picking revision numbers
			//Revision numbers need to appear in order
			//When adding a new revision number, it should be greater than the current revision number
				//stored in the zenario/admin/latest_revision_no.inc.php file
			//After adding a new revision number, the number stored in the
				//zenario/admin/latest_revision_no.inc.php file should be updated to reflect this
		//If you use the SVN version number of your latest SVN commit as the revision number all this will
		//work itself out.
	//Inputs after the first input should be SQL statements to run as part of that revision.
		//They will be run as is, however the subsitution string [[DB_NAME_PREFIX]] will be replaced by
		//the correct table name prefix.
		//For the first few SQL statements I have added below, I've gone out of my way to ensure
		//that they will not break if run twice. The reason for this is that they have already appeared
		//in the database change log, and I don't want to harm any database with the change log already applied.
		//From now on we won't have to worry about that, however.






//Give the xml_file_tuix_contents table a more meaningful name to avoid confusion in the future
	revision( 26950
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]tuix_file_contents`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
	RENAME TO `[[DB_NAME_PREFIX]]tuix_file_contents`
_sql


//Add a translate_phrases column to the languages table
);	revision( 27090
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `translate_phrases` tinyint(1) NOT NULL default 1
	AFTER `detect_lang_codes`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD KEY (`translate_phrases`)
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]languages`
	SET translate_phrases = 0
	WHERE id LIKE 'en-%'
	   OR id = 'en'
_sql


//Delete any animations that are stored as the animations module and all handling for them has been removed
);	revision( 27220
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]files`
	WHERE `usage` = 'inline'
	  AND mime_type = 'application/x-shockwave-flash'
_sql

); revision( 27230

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `scheduled_publish_datetime` datetime DEFAULT NULL
_sql

); revision( 27232

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	ADD COLUMN `anchor` varchar(255) DEFAULT NULL
_sql

);  revision( 27233

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	ADD COLUMN `module_class_name` varchar(255) DEFAULT NULL,
	ADD COLUMN `method_name` varchar(255) DEFAULT NULL,
	ADD COLUMN `param_1` varchar(255) DEFAULT NULL,
	ADD COLUMN `param_2` varchar(255) DEFAULT NULL
_sql

); revision( 27234

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	ADD COLUMN `rollover_image_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `image_id`
_sql

);	revision(27320
, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]]documents_custom_data
_sql

, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]]documents_custom_data (
		`document_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`document_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


); revision( 27341

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents`
	ADD COLUMN `extract` mediumtext DEFAULT NULL,
	ADD COLUMN `extract_wordcount` int(10) unsigned NOT NULL DEFAULT 0
_sql


);	revision( 27931
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	MODIFY COLUMN `path` varchar(255) NOT NULL default ''
_sql


//Rename a couple of module/plugin related columns
);	revision( 28000
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	CHANGE COLUMN `uses_instances` `is_pluggable` tinyint(1) NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	CHANGE COLUMN `uses_wireframes` `can_be_version_controlled` tinyint(1) NOT NULL default 0
_sql

//Add a column to the modules table to mark which ones are missing
);	revision( 28050
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `missing` tinyint(1) unsigned NOT NULL default 0
	AFTER `status`
_sql

//Create the document rules table
); revision( 28230
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents`
	ADD COLUMN `do_not_auto_recode` tinyint(1) unsigned NOT NULL default 0
	AFTER `extract_wordcount`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]document_rules`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]document_rules` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `ordinal` int(10) unsigned NOT NULL default 0,
	  `pattern` mediumtext,
	  `action` enum('move_to_folder', 'set_field') NOT NULL,
	  `folder_id` int(10) unsigned NOT NULL default 0,
	  `field_id` int(10) unsigned NOT NULL default 0,
	  `replacement` mediumtext,
	  `replacement_is_regexp` tinyint(1) unsigned NOT NULL,
	  `stop_processing_rules` tinyint(1) unsigned NOT NULL default 1,
	   PRIMARY KEY (`id`),
	   KEY (`ordinal`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision( 28240
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_rules`
	ADD COLUMN `use` enum('filename_without_extension', 'filename_and_extension', 'extension') NOT NULL default 'filename_without_extension'
	AFTER `ordinal`
_sql

); revision( 28260
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents`
	CHANGE COLUMN `do_not_auto_recode` `dont_autoset_metadata` tinyint(1) unsigned NOT NULL default 0
_sql

); revision( 28460
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_rules`
	ADD COLUMN `apply_second_pass` tinyint(1) unsigned NOT NULL default 0
	AFTER `replacement_is_regexp`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_rules`
	ADD COLUMN `second_pattern` mediumtext
	AFTER `apply_second_pass`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_rules`
	ADD COLUMN `second_replacement` mediumtext
	AFTER `second_pattern`
_sql


//Create a table which will be used for the hierarchial panel for selecting
//new menu node positions.
//It has an entry for every menu section, every menu node, and dupicate entries for
//everything which work as "child" selectors.
//Having to make a table for this is a big hack; at some point we should improve or rewrite
//the selection interface and remove the need for this table.
); revision( 28550
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]menu_positions`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]menu_positions` (
		`tag` char(18) NOT NULL,
		`section_id` smallint(10) unsigned NOT NULL,
		`menu_id` int(10) unsigned NOT NULL default 0,
		`is_dummy_child` tinyint(1) unsigned NOT NULL default 0,
		`parent_tag` char(18) NOT NULL,
		PRIMARY KEY (`tag`),
		KEY (`parent_tag`),
		UNIQUE KEY (`section_id`, `menu_id`, `is_dummy_child`)
	) ENGINE=MyISAM DEFAULT CHARSET=ascii
_sql

//Add some more keys to the files table
); revision( 28460
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`width`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`height`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`storekeeper_width`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`storekeeper_height`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`storekeeper_list_width`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`storekeeper_list_height`)
_sql


//Enable background images for Content Items/Layouts
); 	revision( 29000
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `background_image_id` int(10) unsigned NOT NULL default 0
	AFTER `css_class`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	ADD COLUMN `background_image_id` int(10) unsigned NOT NULL default 0
	AFTER `css_class`
_sql

//Remove the create_lang_equiv_content column from the special_pages table and replace it with an enum which has a new third option
); 	revision( 29030
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]special_pages`
	ADD COLUMN `logic`
		ENUM('create_and_maintain_in_default_language', 'create_and_maintain_in_all_languages', 'create_in_default_language_on_install')
		NOT NULL default 'create_and_maintain_in_default_language'
	AFTER `create_lang_equiv_content`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]special_pages` SET
		`logic` = 'create_and_maintain_in_all_languages'
	WHERE create_lang_equiv_content = 1
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]special_pages`
	DROP COLUMN `create_lang_equiv_content`
_sql

//Automatically strip off all of the [[ double square brackets ]] from the title fields of banners
//as these are now not needed
);	revision( 29180
,<<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.id = ps.instance_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	SET ps.value = REPLACE(REPLACE(ps.value, '[[', ''), ']]', '')
	WHERE ps.nest = 0
	  AND m.class_name = 'zenario_banner'
	  AND ps.name = 'title'
	  AND ps.value LIKE '[[%]]'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]nested_plugins` AS np
	   ON np.id = ps.nest
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = np.module_id
	SET ps.value = REPLACE(REPLACE(ps.value, '[[', ''), ']]', '')
	WHERE ps.nest != 0
	  AND m.class_name = 'zenario_banner'
	  AND ps.name = 'title'
	  AND ps.value LIKE '[[%]]'
_sql


//Enable more background options for Content Items/Layouts
); 	revision( 29230
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	CHANGE COLUMN `background_image_id` `bg_image_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `bg_color` varchar(64) NOT NULL default ''
	AFTER `bg_image_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `bg_position` enum('left top', 'center top', 'right top', 'left center', 'center center', 'right center', 'left bottom', 'center bottom', 'right bottom')
	NULL default NULL
	AFTER `bg_color`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `bg_repeat` enum('repeat', 'repeat-x', 'repeat-y', 'no-repeat')
	NULL default NULL
	AFTER `bg_position`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	CHANGE COLUMN `background_image_id` `bg_image_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	ADD COLUMN `bg_color` varchar(64) NOT NULL default ''
	AFTER `bg_image_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	ADD COLUMN `bg_position` enum('left top', 'center top', 'right top', 'left center', 'center center', 'right center', 'left bottom', 'center bottom', 'right bottom')
	NULL default NULL
	AFTER `bg_color`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	ADD COLUMN `bg_repeat` enum('repeat', 'repeat-x', 'repeat-y', 'no-repeat')
	NULL default NULL
	AFTER `bg_position`
_sql
); revision ( 29238

,<<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings`
	SET name = 'user_form'
	WHERE name = 'user_profile_form'
_sql

); revision( 29239

,<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	DROP COLUMN `image_id`
_sql

//Automatically strip off all of the [[ double square brackets ]] from the heading fields of content lists
//as these are now not needed
);	revision( 29560
,<<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.id = ps.instance_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	SET ps.value = REPLACE(REPLACE(ps.value, '[[', ''), ']]', '')
	WHERE ps.nest = 0
	  AND m.class_name LIKE 'zenario_content_list%'
	  AND ps.name IN ('heading_if_items', 'heading_if_no_items')
	  AND ps.value LIKE '[[%]]'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]nested_plugins` AS np
	   ON np.id = ps.nest
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = np.module_id
	SET ps.value = REPLACE(REPLACE(ps.value, '[[', ''), ']]', '')
	WHERE ps.nest != 0
	  AND m.class_name LIKE 'zenario_content_list%'
	  AND ps.name IN ('heading_if_items', 'heading_if_no_items')
	  AND ps.value LIKE '[[%]]'
_sql

); revision( 29572

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_cache`
	ADD COLUMN `text_wordcount` int(10) unsigned NOT NULL DEFAULT 0 AFTER `text`
_sql

); revision( 29576

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `category` enum('custom', 'core', 'content_type', 'management', 'pluggable') NULL DEFAULT NULL AFTER `display_name`
_sql

); revision( 29578

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]page_preview_sizes`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]page_preview_sizes` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `width` int(10) unsigned NOT NULL,
	  `height` int(10) unsigned NOT NULL,
	  `description` varchar(255) NOT NULL DEFAULT '',
	  `is_default` tinyint(1) unsigned NOT NULL DEFAULT 0,
	  `ordinal` int(10) unsigned NOT NULL,
	   PRIMARY KEY (`id`),
	   KEY (`ordinal`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision( 29579

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]page_preview_sizes` 
	(width, height, description, is_default, ordinal)
	VALUES 
	(1680, 1050, 'Current computers', 0, 1),
	(1280, 1024, 'Not that old computers', 0, 2),
	(1024, 768, 'Old computers', 0, 3),
	(1440, 900, 'Other laptops', 0, 4),
	(1366, 769, 'Laptop 15.7"', 0, 5),
	(1280, 800, 'Laptop 15.4"', 0, 6),
	(1024, 600, 'Netbook', 1, 7),
	(768, 1024, 'iPad portrait', 0, 8),
	(320, 480, 'HVGA - iPhone, Android, Palm Pre', 0, 9)
_sql

);

revision(29580
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]]documents
	  ADD COLUMN `file_name` varchar(255)
_sql
);

revision(29581
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]]documents
	  CHANGE `file_id` `file_id` int(10) unsigned NULL
_sql
);

revision(29582
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]]documents
	  DROP INDEX `file_id`
_sql
);

revision(29583
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]]documents
	  CHANGE `file_name` `filename` varchar(255)
_sql
);

revision(29584
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]]documents
	  ADD COLUMN `file_datetime` datetime default NULL
_sql
); revision( 30151

,  <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]page_preview_sizes`
	ADD COLUMN `type` enum('desktop','laptop','tablet', 'tablet_landscape', 'smartphone') NOT NULL default 'desktop'
_sql
,  <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]page_preview_sizes` SET type = 'laptop' WHERE description = 'Other laptops'
_sql
,  <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]page_preview_sizes` SET type = 'laptop' WHERE description = 'Laptop 15.7"'
_sql
,  <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]page_preview_sizes` SET type = 'laptop' WHERE description = 'Laptop 15.4"'
_sql
,  <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]page_preview_sizes` SET type = 'laptop' WHERE description = 'Netbook'
_sql
,  <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]page_preview_sizes` SET type = 'tablet' WHERE description = 'iPad portrait'
_sql
,  <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]page_preview_sizes` SET type = 'smartphone' WHERE description = 'HVGA - iPhone, Android, Palm Pre'
_sql

);