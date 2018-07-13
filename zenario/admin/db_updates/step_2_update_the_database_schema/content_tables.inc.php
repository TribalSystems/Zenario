<?php
/*
 * Copyright (c) 2018, Tribal Limited
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








//Add a translate_phrases column to the languages table
	revision( 27090
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

);	revision( 27230

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `scheduled_publish_datetime` datetime DEFAULT NULL
_sql

);	revision( 27232

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

);	revision( 27234

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


);	revision( 27341

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
);	revision( 28230
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

);	revision( 28240
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_rules`
	ADD COLUMN `use` enum('filename_without_extension', 'filename_and_extension', 'extension') NOT NULL default 'filename_without_extension'
	AFTER `ordinal`
_sql

);	revision( 28260
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents`
	CHANGE COLUMN `do_not_auto_recode` `dont_autoset_metadata` tinyint(1) unsigned NOT NULL default 0
_sql

);	revision( 28460
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
);	revision( 28550
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
);	revision( 28460
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
, <<<_sql
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
);	revision ( 29238

,<<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings`
	SET name = 'user_form'
	WHERE name = 'user_profile_form'
_sql

);	revision( 29239
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	DROP COLUMN `image_id`
_sql

//Automatically strip off all of the [[ double square brackets ]] from the heading fields of content lists
//as these are now not needed
);	revision( 29560
, <<<_sql
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

);	revision( 29572

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_cache`
	ADD COLUMN `text_wordcount` int(10) unsigned NOT NULL DEFAULT 0 AFTER `text`
_sql

);	revision( 29576

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `category` enum('custom', 'core', 'content_type', 'management', 'pluggable') NULL DEFAULT NULL AFTER `display_name`
_sql

);	revision( 29580
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]]documents
	  ADD COLUMN `file_name` varchar(255)
_sql
);	revision( 29581
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]]documents
	  CHANGE `file_id` `file_id` int(10) unsigned NULL
_sql
);	revision( 29582
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]]documents
	  DROP INDEX `file_id`
_sql
);	revision( 29583
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]]documents
	  CHANGE `file_name` `filename` varchar(255)
_sql
);	revision( 29584
,  <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]]documents
	  ADD COLUMN `file_datetime` datetime default NULL
_sql



//Rename the inline_file_link table to inline_images
);	revision( 30250
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]inline_images`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]inline_file_link`
	RENAME TO `[[DB_NAME_PREFIX]]inline_images`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]inline_images`
	CHANGE COLUMN `file_id` `image_id` int(10) unsigned NOT NULL
_sql


//Change the "usage" column of some images
//Note that this may fail if there is a checksum clash. I'll deal with this by making sure
//that the inline images will always win the clash, then dealing with email/menu images later
);	revision( 30260
, <<<_sql
	UPDATE IGNORE [[DB_NAME_PREFIX]]files
	SET `usage` = 'image'
	WHERE `usage` = 'inline'
_sql

, <<<_sql
	UPDATE IGNORE [[DB_NAME_PREFIX]]files
	SET `usage` = 'image'
	WHERE `usage` IN ('email', 'inline', 'menu')
_sql

//Handle the case where there was a clash of images for Menu Nodes
, <<<_sql
	UPDATE [[DB_NAME_PREFIX]]menu_nodes AS mn
	INNER JOIN [[DB_NAME_PREFIX]]files AS of
	   ON of.id = mn.image_id
	  AND of.`usage` != 'image'
	INNER JOIN [[DB_NAME_PREFIX]]files AS nf
	   ON nf.checksum = of.checksum
	  AND nf.`usage` = 'image'
	SET mn.image_id = nf.id
	WHERE mn.image_id != 0
_sql

//Handle the case where there was a clash of images for Menu Node roleover images
, <<<_sql
	UPDATE [[DB_NAME_PREFIX]]menu_nodes AS mn
	INNER JOIN [[DB_NAME_PREFIX]]files AS of
	   ON of.id = mn.rollover_image_id
	  AND of.`usage` != 'image'
	INNER JOIN [[DB_NAME_PREFIX]]files AS nf
	   ON nf.checksum = of.checksum
	  AND nf.`usage` = 'image'
	SET mn.rollover_image_id = nf.id
	WHERE mn.image_id != 0
_sql

//Handle the case where there was a clash of images for inline images
, <<<_sql
	UPDATE [[DB_NAME_PREFIX]]inline_images AS ii
	INNER JOIN [[DB_NAME_PREFIX]]files AS of
	   ON of.id = ii.image_id
	  AND of.`usage` != 'image'
	INNER JOIN [[DB_NAME_PREFIX]]files AS nf
	   ON nf.checksum = of.checksum
	  AND nf.`usage` = 'image'
	SET ii.image_id = nf.id
	WHERE ii.image_id != 0
_sql

//We should have handled all of the clashes now, so we can delete the duplicate that are no longer linked to
, <<<_sql
	DELETE FROM [[DB_NAME_PREFIX]]files
	WHERE `usage` IN ('email', 'inline', 'menu')
_sql

);	revision( 30275

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]last_sent_warning_emails`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]last_sent_warning_emails` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`timestamp` datetime NOT NULL,
		`warning_code` enum('document_container__private_file_in_public_folder', 'module_missing') NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql





//Remove the library_images tables if they were ever created for people running early versions of 7.0.5
);	revision( 30500
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]library_images`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]library_images_custom_data`
_sql


//Rename reusable_plugin to library_plugin in the inline_images table
);	revision( 30510
, <<<_sql
	UPDATE [[DB_NAME_PREFIX]]inline_images
	SET foreign_key_to = 'library_plugin'
	WHERE foreign_key_to = 'reusable_plugin'
_sql


//Set the checksum column in the files table to ascii only
);	revision( 30600
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	MODIFY COLUMN `checksum` varchar(32) CHARACTER SET ascii NOT NULL
_sql

//Set the usage column in the files table to ascii only
);	revision( 30610
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	MODIFY COLUMN `usage` varchar(64) CHARACTER SET ascii NOT NULL
_sql

//Add a new short-checksum column to the files table
);	revision( 30620
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `short_checksum` varchar(24) CHARACTER SET ascii NULL default NULL
	AFTER `checksum`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD UNIQUE KEY `short_checksum` (`short_checksum`,`usage`)
_sql


//Change/simplify how the image pots work for site settings. Rather that lots of different pots,
//we'll now just use one pot.
);	revision( 30630
//Add an index on value to the site settings table
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings`
	ADD KEY `value` (`value`(64))
_sql

//Add/migrate the four existing site settings to the new format
, <<<_sql
	REPLACE INTO [[DB_NAME_PREFIX]]site_settings (name, value)
	SELECT
		IF (`usage` = 'brand_logo', 'custom_logo',
		IF (`usage` = 'organizer_favicon', 'custom_organizer_favicon',
			`usage`)),
		id
	FROM [[DB_NAME_PREFIX]]files
	WHERE `usage` IN ('brand_logo', 'favicon', 'mobile_icon', 'organizer_favicon')
_sql

//Remove the four old image pots for site settings and migrate them to one new one
, <<<_sql
	UPDATE IGNORE [[DB_NAME_PREFIX]]files
	SET `usage` = 'site_setting'
	WHERE `usage` IN ('brand_logo', 'favicon', 'mobile_icon', 'organizer_favicon')
_sql

//Handle the case where there were duplicates.
//The last statement would leave the duplicates alone so we just need to delete them
, <<<_sql
	DELETE FROM [[DB_NAME_PREFIX]]files
	WHERE `usage` IN ('brand_logo', 'favicon', 'mobile_icon', 'organizer_favicon')
_sql

//Change how the image library works so that images in Content Items/Newsletters
//are *always* in the library, unless they are deleted.
//Add an `archived` column.
);	revision( 30700
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `archived` tinyint(1) NOT NULL default 0
	AFTER `usage`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]inline_images`
	ADD COLUMN `archived` tinyint(1) NOT NULL default 0
	AFTER `in_use`
_sql

);	revision( 30720
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`archived`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]inline_images`
	ADD KEY (`archived`)
_sql


//Temporaily restore the `shared` column in the files table.
	//If it was previously dropped because this site was updated to the 7.0.5 beta, this line will re-add it
	//If it still exists, the db_updater will skip this line
);	revision( 30765
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `shared` tinyint(1) NOT NULL default 0
_sql



//Create tables to store tags for images
);	revision( 30770
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]image_tags`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]image_tags` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]image_tag_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]image_tag_link` (
		`image_id` int(10) unsigned NOT NULL,
		`tag_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`image_id`, `tag_id`),
		KEY (`tag_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Create a new tag to remember which images were shared (unless the shared column was already previously dropped)
);	revision( 30775
, <<<_sql
	INSERT INTO [[DB_NAME_PREFIX]]image_tags
	SELECT 1, 'shared'
	FROM [[DB_NAME_PREFIX]]files
	WHERE `usage` = 'image'
	  AND shared = 1
	LIMIT 1
_sql

, <<<_sql
	INSERT IGNORE INTO [[DB_NAME_PREFIX]]image_tag_link
	SELECT id, 1
	FROM [[DB_NAME_PREFIX]]files
	WHERE `usage` = 'image'
	  AND shared = 1
_sql



);	revision( 30780
//Drop the `in_library` and `shared` columns if they have been created
//If they've not been created the db updater will skip that line
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	DROP COLUMN `in_library`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	DROP COLUMN `shared`
_sql


//Rename some columns from "storekeeper" to "organizer"
);	revision( 30830
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	CHANGE COLUMN `storekeeper_width` `organizer_width` tinyint(3) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	CHANGE COLUMN `storekeeper_height` `organizer_height` tinyint(3) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	CHANGE COLUMN `storekeeper_data` `organizer_data` blob
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	CHANGE COLUMN `storekeeper_list_width` `organizer_list_width` tinyint(3) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	CHANGE COLUMN `storekeeper_list_height` `organizer_list_height` tinyint(3) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	CHANGE COLUMN `storekeeper_list_data` `organizer_list_data` blob
_sql




//Create the page_preview_sizes table
//(Bumped down from above to fix a bug where it wasn't included in CMS backups.)
);	revision( 30845

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
	  `type` enum('desktop','laptop','tablet', 'tablet_landscape', 'smartphone') NOT NULL default 'desktop',
	   PRIMARY KEY (`id`),
	   KEY (`ordinal`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]page_preview_sizes` 
	(width, height, description, is_default, ordinal, type)
	VALUES 
	(1680, 1050, 'Current computers', 0, 1, 'desktop'),
	(1280, 1024, 'Not that old computers', 0, 2, 'desktop'),
	(1024, 768, 'Old computers', 0, 3, 'desktop'),
	(1440, 900, 'Other laptops', 0, 4, 'laptop'),
	(1366, 769, 'Laptop 15.7"', 0, 5, 'laptop'),
	(1280, 800, 'Laptop 15.4"', 0, 6, 'laptop'),
	(1024, 600, 'Netbook', 1, 7, 'laptop'),
	(768, 1024, 'iPad portrait', 0, 8, 'tablet'),
	(320, 480, 'HVGA - iPhone, Android, Palm Pre', 0, 9, 'smartphone')
_sql



//Create tables to store tags for images
);	revision( 31100
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]image_tags`
	ADD COLUMN `color` enum('blue', 'red', 'green', 'orange', 'yellow', 'violet', 'grey') NOT NULL default 'blue'
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]image_tags`
	ADD KEY (`color`)
_sql


//Add a new size of thumbnail for images to the files table
//Also tidy up the column names that we use to make things more specific
);	revision( 31200
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	DROP KEY `storekeeper_width`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	DROP KEY `storekeeper_height`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	DROP KEY `storekeeper_list_width`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	DROP KEY `storekeeper_list_height`
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	CHANGE COLUMN `organizer_width` `thumbnail_180x130_width` tinyint(3) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	CHANGE COLUMN `organizer_height` `thumbnail_180x130_height` tinyint(3) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	CHANGE COLUMN `organizer_data` `thumbnail_180x130_data` blob
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `thumbnail_64x64_width` tinyint(3) unsigned NOT NULL default 0
	AFTER `thumbnail_180x130_data`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `thumbnail_64x64_height` tinyint(3) unsigned NOT NULL default 0
	AFTER `thumbnail_64x64_width`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `thumbnail_64x64_data` blob
	AFTER `thumbnail_64x64_height`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	CHANGE COLUMN `organizer_list_width` `thumbnail_24x23_width` tinyint(3) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	CHANGE COLUMN `organizer_list_height` `thumbnail_24x23_height` tinyint(3) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	CHANGE COLUMN `organizer_list_data` `thumbnail_24x23_data` blob
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`thumbnail_180x130_width`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`thumbnail_180x130_height`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`thumbnail_64x64_width`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`thumbnail_64x64_height`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`thumbnail_24x23_width`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`thumbnail_24x23_height`)
_sql


);	revision( 31210
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD COLUMN is_email_address tinyint(1) DEFAULT NULL
_sql


//Task #9706: Remove Storekeeper on email templates AW
);	revision( 31250
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]email_templates`
	SET body = REPLACE(body, ' in Storekeeper ', ' in Organizer ')
	WHERE body LIKE '% in Storekeeper %'
_sql


);	revision( 31260
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `privacy` enum('auto','public','private') NOT NULL DEFAULT 'auto'
	AFTER `usage`
_sql


//Add some basic information about the grid into the layouts table
); 	revision( 31450
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	ADD COLUMN `cols` tinyint(2) unsigned NOT NULL default 0
	AFTER `status`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	ADD COLUMN `min_width` smallint(4) unsigned NOT NULL default 0
	AFTER `cols`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	ADD COLUMN `max_width` smallint(4) unsigned NOT NULL default 0
	AFTER `min_width`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	ADD COLUMN `fluid` tinyint(1) unsigned NOT NULL default 0
	AFTER `max_width`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	ADD COLUMN `responsive` tinyint(1) unsigned NOT NULL default 0
	AFTER `fluid`
_sql

//Populate some initial values for the default layouts
);	revision( 31460
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]layouts`
	SET cols = 12,
		min_width = 769,
		max_width = 1140,
		fluid = 1,
		responsive = 1
	WHERE family_name = 'grid_templates'
	  AND file_base_name = 'Home page'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]layouts`
	SET cols = 12,
		min_width = 769,
		max_width = 1140,
		fluid = 1,
		responsive = 1
	WHERE family_name = 'grid_templates'
	  AND file_base_name = 'Text-heavy'
_sql


//Add a column into the languages table for a language-specific domain or sub-domain
); 	revision( 31500
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `domain` varchar(255) NOT NULL default ''
	AFTER `search_type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD KEY (`domain`)
_sql

// Add a column to content types to show categorys or not
); revision( 31510

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	ADD COLUMN `enable_categories` tinyint(1) NOT NULL default 0
	AFTER `enable_summary_auto_update`
_sql


); revision( 31550

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	ADD COLUMN `menu_text_module_class_name` varchar(255) DEFAULT NULL,
	ADD COLUMN `menu_text_method_name` varchar(255) DEFAULT NULL,
	ADD COLUMN `menu_text_param_1` varchar(255) DEFAULT NULL,
	ADD COLUMN `menu_text_param_2` varchar(255) DEFAULT NULL
_sql


//Add a column to the datasets table to flag important system fields
); revision( 31720
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	ADD COLUMN `fundamental` tinyint(1) NOT NULL default 0
	AFTER `is_system_field`
_sql


//Correct a bug where TinyMCE added "zenario/admin" into the login link a second time
);	revision( 31850
, <<<_sql
	UPDATE [[DB_NAME_PREFIX]]site_settings
	SET value = REPLACE(value, 'zenario/admin/[', '[')
	WHERE name = 'site_disabled_message'
_sql




//
//Version 7.0.7
//

//T8893, Better naming of content item related tables
);	revision( 32000
, <<<_sql
	RENAME TABLE
		`[[DB_NAME_PREFIX]]content` TO `[[DB_NAME_PREFIX]]content_items`,
		`[[DB_NAME_PREFIX]]versions` TO `[[DB_NAME_PREFIX]]content_item_versions`
_sql

);	revision( 32010
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents`
	ADD COLUMN `privacy` enum('auto','public','private') NOT NULL DEFAULT 'auto' AFTER `folder_name`
_sql

);	revision( 32020

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	ADD COLUMN `module_class_name` varchar(255) DEFAULT NULL AFTER `id`
_sql



//Create a table to store the default values for admin settings
);	revision( 32310
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admin_setting_defaults`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]admin_setting_defaults` (
		`name` varchar(255) NOT NULL DEFAULT '',
		`default_value` mediumtext,
		PRIMARY KEY (`name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision( 32320

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]translation_chains`
	DROP COLUMN `log_user_access`
_sql

//Add a column to the skins table to store the background selector
);	revision( 32460
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD COLUMN `background_selector` varchar(64) DEFAULT 'body'
	AFTER `css_class`
_sql

); revision( 32461
,<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents`
	ADD COLUMN `title` varchar(255) DEFAULT NULL
_sql

); revision( 32465

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]centralised_lists`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]centralised_lists` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`module_class_name` varchar(255) NOT NULL,
		`method_name` varchar(255) NOT NULL,
		`label` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision( 32469

,<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]jobs`
	ADD COLUMN `run_every_minute` tinyint(1) NOT NULL DEFAULT 0 AFTER `minutes` 
_sql




//
//Version 7.1
//



//T10134 Add a "URL" field to the phrases table, that shows a URL that the phrase was found on
); revision( 33580
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD COLUMN `seen_at_url` text
_sql




//
//Version 7.2
//

//Add the "abstract" status for modules
);	revision( 33920
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	MODIFY COLUMN `status` enum('module_not_initialized', 'module_running', 'module_suspended', 'module_is_abstract') NOT NULL default 'module_not_initialized'
_sql


); revision( 34190
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `fill_organizer_nav` tinyint(1) NOT NULL default 0
	AFTER `is_pluggable`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD KEY (`fill_organizer_nav`)
_sql


); revision( 34200
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	ADD COLUMN `content_type_plural_en` varchar(255) NOT NULL default ''
	AFTER `content_type_name_en`
_sql


//Remove landing pages from categories
); revision( 34260
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	DROP COLUMN `landing_page_equiv_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	DROP COLUMN `landing_page_content_type`
_sql


//Remove any excecutables from the document_types table if anyone has previously added them
); revision( 34500
,<<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]document_types`
	WHERE `type` IN ('asp', 'bin', 'cgi', 'exe', 'js', 'jsp', 'php', 'php3', 'ph3', 'php4', 'ph4', 'php5', 'ph5', 'phtm', 'phtml', 'sh')
_sql


//Add CC, BCC and debug options to email templates
);	revision( 34510
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	ADD COLUMN `send_cc` tinyint(1) NOT NULL default 0
	AFTER `body`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	ADD COLUMN `cc_email_address` TEXT
	AFTER `send_cc`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	ADD COLUMN `send_bcc` tinyint(1) NOT NULL default 0
	AFTER `cc_email_address`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	ADD COLUMN `bcc_email_address` TEXT
	AFTER `send_bcc`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	ADD COLUMN `debug_override` tinyint(1) NOT NULL default 0
	AFTER `bcc_email_address`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	ADD COLUMN `debug_email_address` TEXT
	AFTER `debug_override`
_sql


//Make the thumbnail columns on the files table a little larger,
//as it's actually possible to run out of room with just normal blobs
);	revision( 34550
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	MODIFY COLUMN `thumbnail_24x23_data` mediumblob
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	MODIFY COLUMN `thumbnail_64x64_data` mediumblob
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	MODIFY COLUMN `thumbnail_180x130_data` mediumblob
_sql

//Add is_creatable to content types in case you cannot manually create an item of this type
); revision( 34662

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	ADD COLUMN `is_creatable` tinyint(1) NOT NULL DEFAULT 1 AFTER `enable_categories`
_sql





//
//Version 7.3
//


//Remake the tuix_file_contents table to start tracking which panel types are used in which files
//(Technically I could have added the column and truncated the table, but I may as well
// drop and recreate it.)
);	revision( 34750
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]tuix_file_contents`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]tuix_file_contents` (
		`type` enum('admin_boxes','admin_toolbar','help','organizer','slot_controls','visitor','wizards') NOT NULL,
		`path` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
		`panel_type` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
		`setting_group` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
		`module_class_name` varchar(200) CHARACTER SET ascii NOT NULL,
		`filename` varchar(255) CHARACTER SET ascii NOT NULL,
		`last_modified` int(10) unsigned NOT NULL DEFAULT '0',
		`checksum` varchar(32) CHARACTER SET ascii NOT NULL DEFAULT '',
		PRIMARY KEY (`type`,`path`,`setting_group`,`module_class_name`,`filename`),
		KEY (`panel_type`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Add columns to content types table for new options for a default place in the menu
); revision (34752
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	ADD COLUMN `default_parent_menu_node` int(10) NOT NULL DEFAULT 0,
	ADD COLUMN `menu_node_position` enum('start','end') DEFAULT NULL,
	ADD COLUMN `menu_node_position_edit` enum('force','suggest') DEFAULT NULL,
	ADD COLUMN `hide_menu_node` tinyint(1) NOT NULL DEFAULT 0
_sql


//In the site settings table, the name column should not have a default value
);	revision( 35070
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings`
	MODIFY COLUMN `name` varchar(255) NOT NULL
_sql


//Increase the width of the name column in the plugin settings table
);	revision( 35500
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	MODIFY COLUMN `name` varchar(255) NOT NULL
_sql

);	revision( 35550
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	DROP KEY `module_class_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	MODIFY COLUMN `name` varchar(255) NOT NULL
_sql

);	revision( 35600
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	ADD KEY `module_class_name` (`module_class_name` (75), `name` (175))
_sql


//Remove the old unused columns to do with customising the menu node text from the menu nodes table,
//merging their into the columns that are still used if possible
);  revision( 35990
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]menu_nodes` SET
		module_class_name = menu_text_module_class_name,
		method_name = menu_text_method_name,
		param_1 = menu_text_param_1,
		param_2 = menu_text_param_2
	WHERE menu_text_module_class_name IS NOT NULL
	  AND menu_text_module_class_name != ''
	  AND (module_class_name IS NULL
	    OR module_class_name = '')
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	DROP COLUMN `menu_text_module_class_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	DROP COLUMN `menu_text_method_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	DROP COLUMN `menu_text_param_1`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	DROP COLUMN `menu_text_param_2`
_sql


);	revision( 36070
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]module_dependencies`
	WHERE `type` = 'allow_upgrades'
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]module_dependencies`
	MODIFY COLUMN `type` enum('dependency', 'inherit_frameworks', 'include_javascript', 'inherit_settings') NOT NULL
_sql


//Add a column to track which modules provide functions for Twig frameworks
);	revision( 36400
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `for_use_in_twig` tinyint(1) unsigned NOT NULL default 0
	AFTER `can_be_version_controlled`
_sql


//Add a column to control enabling the skin editor.
//Note a little bit of fiddly logic here for backwards compatability reasons:
	//By default, old skins (e.g. skins created before 7.3) should default to not enabled.
		//This is implemented here, by making the column "default 0"
	//Skins created after 7.3 should default to enabled
		//This is implemented in the checkForChangesInCssJsAndHtmlFiles() function
);	revision( 36450
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD COLUMN `enable_editable_css` tinyint(1) NOT NULL default 0
	AFTER `background_selector`
_sql


//Add a column to track which modules provide functions for Twig frameworks
);	revision( 36485
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `edition` enum('Other', 'Community', 'Pro', 'ProBusiness', 'Enterprise') default 'Other'
	AFTER `display_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD KEY (`edition`)
_sql


//Remove component skins as a feature; all skins are now usable skins
);	revision( 36486
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	DROP COLUMN `type`
_sql

//The enable_editable_css column should default to 1
);	revision( 36490
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	MODIFY COLUMN `enable_editable_css` tinyint(1) NOT NULL default 1
_sql


//Convert the how categories are saved in the plugin settings of the 
//content list probusiness, so it can be safely merged in with the
//normal content list plugin.
);	revision( 36500
//Firstly, delete some possible junk data that could cause a clash
, <<<_sql
	DELETE ps2.*
	FROM `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.id = ps.instance_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps2
	   ON ps2.instance_id = ps.instance_id
	  AND ps2.nest = ps.nest
	  AND ps2.name = 'omit_category'
	WHERE ps.nest = 0
	  AND m.class_name = 'zenario_content_list_probusiness'
	  AND ps.name = 'refine_type'
	  AND ps.value = 'not_in_any_category'
_sql

, <<<_sql
	DELETE ps2.*
	FROM `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]nested_plugins` AS np
	   ON np.id = ps.nest
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = np.module_id
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps2
	   ON ps2.instance_id = ps.instance_id
	  AND ps2.nest = ps.nest
	  AND ps2.name = 'omit_category'
	WHERE ps.nest != 0
	  AND m.class_name = 'zenario_content_list_probusiness'
	  AND ps.name = 'refine_type'
	  AND ps.value = 'not_in_any_category'
_sql

//Make sure the new enable_omit_category checkbox is checked when it should be
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `name`,
	  `nest`,
	  `value`,
	  `is_content`
	)
	SELECT 
	  ps.`instance_id`,
	  'enable_omit_category',
	  ps.`nest`,
	  1,
	  ps.`is_content`
	FROM `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	WHERE ps.name = 'omit_category'
	  AND ps.value IS NOT NULL
	  AND ps.value
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `name`,
	  `nest`,
	  `value`,
	  `is_content`
	)
	SELECT 
	  ps.`instance_id`,
	  'enable_omit_category',
	  ps.`nest`,
	  1,
	  ps.`is_content`
	FROM `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps2
	   ON ps2.instance_id = ps.instance_id
	  AND ps2.nest = ps.nest
	  AND ps2.name = 'category'
	  AND ps2.value IS NOT NULL
	  AND ps2.value
	WHERE ps.name = 'refine_type'
	  AND ps.value = 'not_in_any_category'
_sql

//Next, convert the names and values of the plugin settings that need to change
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.id = ps.instance_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps2
	   ON ps2.instance_id = ps.instance_id
	  AND ps2.nest = ps.nest
	  AND ps2.name = 'category'
	SET ps.value = 'any_categories',
		ps2.name = 'omit_category'
	WHERE ps.nest = 0
	  AND m.class_name = 'zenario_content_list_probusiness'
	  AND ps.name = 'refine_type'
	  AND ps.value = 'not_in_any_category'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]nested_plugins` AS np
	   ON np.id = ps.nest
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = np.module_id
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps2
	   ON ps2.instance_id = ps.instance_id
	  AND ps2.nest = ps.nest
	  AND ps2.name = 'category'
	SET ps.value = 'any_categories',
		ps2.name = 'omit_category'
	WHERE ps.nest != 0
	  AND m.class_name = 'zenario_content_list_probusiness'
	  AND ps.name = 'refine_type'
	  AND ps.value = 'not_in_any_category'
_sql




//In version 7.4, we're removing the revealable panel and roundabout modules,
//and merging any existing plugins into the slideshow modules.
//Set the mode plugin setting for any revealable panel or roundabout plugins.
	//The default value for slideshows is "cycle"
	//Roundabouts sholud use "roundaabout"
	//Revealable panels should use "none", and will need their CSS rewritten
);	revision( 36900
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `name`,
	  `nest`,
	  `value`,
	  `is_content`
	)
	SELECT 
	  pi.id,
	  'mode',
	  0,
	  'roundabout',
	  ps.`is_content`
	FROM `[[DB_NAME_PREFIX]]modules` AS m
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.module_id = m.id
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	   ON ps.instance_id = pi.id
	  AND ps.nest = 0
	  AND ps.name = 'speed'
	WHERE m.class_name = 'zenario_roundabout'
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `name`,
	  `nest`,
	  `value`,
	  `is_content`
	)
	SELECT 
	  pi.id,
	  'mode',
	  0,
	  '',
	  ps.`is_content`
	FROM `[[DB_NAME_PREFIX]]modules` AS m
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.module_id = m.id
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	   ON ps.instance_id = pi.id
	  AND ps.nest = 0
	  AND ps.name = 'speed'
	WHERE m.class_name = 'zenario_revealable_panel'
_sql

//Automatically strip off all of the [[ double square brackets ]] from the title fields of
//nests, and labels of tabs, as they are now not needed
);	revision( 36910
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.id = ps.instance_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	SET ps.value = REPLACE(REPLACE(ps.value, '[[', ''), ']]', '')
	WHERE ps.nest = 0
	  AND m.class_name IN (
		'zenario_plugin_nest',
		'zenario_plugin_tabbed_nest',
		'zenario_plugin_nest_probusiness',
		'zenario_slideshow',
		'zenario_slideshow_probusiness',
		'zenario_roundabout',
		'zenario_revealable_panel')
	  AND ps.name = 'heading_text'
	  AND ps.value LIKE '[[%]]'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]nested_plugins`
	SET name_or_title = REPLACE(REPLACE(name_or_title, '[[', ''), ']]', '')
	WHERE is_tab = 1
_sql


//Add the tab options from ProBusiness into community
);	revision( 36920
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `visibility`
		enum('everyone','logged_out','logged_in','logged_in_with_field','logged_in_without_field','without_field','call_static_method','in_smart_group')
		NOT NULL default 'everyone'
	AFTER `name_or_title`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `smart_group_id` int(10) unsigned NOT NULL default 0
	AFTER `visibility`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `field_id` int(10) unsigned NOT NULL default 0
	AFTER `smart_group_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `field_value` varchar(255) NOT NULL default ''
	AFTER `field_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `module_class_name` varchar(200) NOT NULL default ''
	AFTER `field_value`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `method_name` varchar(127) NOT NULL default ''
	AFTER `module_class_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `param_1` varchar(200) NOT NULL default ''
	AFTER `method_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `param_2` varchar(200) NOT NULL default ''
	AFTER `param_1`
_sql


//Rename is_tab to is_slide, we'll be trying to consistantly refer to them as "Slides" now as "Tabs" was confusing
);	revision( 36940
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	CHANGE COLUMN `is_tab` `is_slide` tinyint(1) NOT NULL default 0
_sql





//Combine four frameworks for the plugin nest into one, and replace
//the choices with two plugin settings
);	revision( 36980
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `name`,
	  `nest`,
	  `value`,
	  `is_content`
	)
	SELECT 
	  pi.id,
	  'show_tabs',
	  0,
	  IF (pi.framework LIKE '%tab%', '1', ''),
	  IF (pi.content_id, 'version_controlled_setting', 'synchronized_setting')
	FROM `[[DB_NAME_PREFIX]]modules` AS m
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.module_id = m.id
	WHERE m.class_name IN (
		'zenario_slideshow', 'zenario_slideshow_probusiness',
		'zenario_plugin_nest', 'zenario_plugin_tabbed_nest', 'zenario_plugin_nest_probusiness',
		'zenario_roundabout', 'zenario_revealable_panel')
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `name`,
	  `nest`,
	  `value`,
	  `is_content`
	)
	SELECT 
	  pi.id,
	  'show_next_prev_buttons',
	  0,
	  IF (pi.framework LIKE '%button%', '1', ''),
	  IF (pi.content_id, 'version_controlled_setting', 'synchronized_setting')
	FROM `[[DB_NAME_PREFIX]]modules` AS m
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.module_id = m.id
	WHERE m.class_name IN (
		'zenario_slideshow', 'zenario_slideshow_probusiness',
		'zenario_plugin_nest', 'zenario_plugin_tabbed_nest', 'zenario_plugin_nest_probusiness',
		'zenario_roundabout', 'zenario_revealable_panel')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` AS m
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.module_id = m.id
	SET pi.framework = 'standard'
	WHERE m.class_name IN (
		'zenario_slideshow', 'zenario_slideshow_probusiness',
		'zenario_plugin_nest', 'zenario_plugin_tabbed_nest', 'zenario_plugin_nest_probusiness',
		'zenario_roundabout', 'zenario_revealable_panel')
_sql


//Add a column for setting how many columns a plugin in a nest should take up.
);	revision( 36985
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `cols` tinyint(2) signed NOT NULL default 0
	COMMENT '0 means full-width, -1 means grouped with the previous plugin'
	AFTER `ord`
_sql

//Add a column for the responsive mode of a slot
);	revision( 36990
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `small_screens` enum('show', 'hide', 'only') default 'show'
	AFTER `cols`
_sql

//Also add columns like this to the table that records normal slots, so we can start tracking these
);	revision( 36995
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_slot_link`
	ADD COLUMN `ord` smallint(4) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_slot_link`
	ADD COLUMN `cols` tinyint(2) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_slot_link`
	ADD COLUMN `small_screens` enum('show', 'hide', 'only') default 'show'
_sql

//Add a list of states to each slide in a nest
);	revision( 37000
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `states` SET (
		'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
		'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
		'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL'
	) NOT NULL default ''
	AFTER `is_slide`
_sql

//Add a new option to the Menu Nodes table for adding GET requests
);	revision( 37070
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	ADD COLUMN `add_registered_get_requests` tinyint(1) unsigned NOT NULL default 1
	AFTER `hide_private_item`
_sql

//Create a table for the conductor to save its paths
);	revision( 37080
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]nested_paths`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]nested_paths` (
		`instance_id` int(10) unsigned NOT NULL,
		`from_state` char(2) CHARACTER SET ascii NOT NULL,
		`to_state` char(2) CHARACTER SET ascii NOT NULL,
		`commands` TEXT,
		PRIMARY KEY (`instance_id`,`to_state`,`from_state`),
		UNIQUE KEY (`instance_id`,`from_state`,`to_state`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Add a new column for tabs in nest, that works just like "Invisible in menu navigation" for menu nodes
);	revision( 37100
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `invisible_in_nav` tinyint(1) NOT NULL default 0
	AFTER `is_slide`
_sql


//Fix a bug where an enum value was missing in the template_slot_link table
);	revision( 37110
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_slot_link`
	MODIFY COLUMN `small_screens` enum('show', 'hide', 'only', 'first') default 'show'
_sql

//Add a new column for HTML in the head of email templates like newletters and newsletter templates have
); revision( 37112
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	ADD COLUMN `head` mediumtext AFTER `email_name_from`
_sql


//All nested banners should have the text "Banner:" in front of them so we can see that they're banners
); revision( 37200
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` AS m
	INNER JOIN `[[DB_NAME_PREFIX]]nested_plugins` AS np
	   ON m.id = np.module_id
	  AND np.is_slide = 0
	  AND np.name_or_title != m.display_name
	SET np.name_or_title = CONCAT(m.display_name, ': ', np.name_or_title)
	WHERE m.class_name IN ('zenario_banner', 'zenario_image_container')
_sql


//Add "logged_in_not_in_smart_group" as an option for slide visibility
//Remove the logged_in_with_field/logged_in_without_field/without_field options for slide visibility,
//but keep the data of anything that picked it so we can migrate it to smart groups later.
//Note that "without_field" is being removed as an option, so we are silently switching that
//to "logged_in_without_field".
); revision( 37230
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]tmp_migrate_slide_visibility`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]tmp_migrate_slide_visibility` AS
	SELECT GROUP_CONCAT(id) AS `slide_ids`, field_id, field_value
	FROM `[[DB_NAME_PREFIX]]nested_plugins`
	WHERE is_slide = 1
	  AND visibility IN ('logged_in_with_field','logged_in_without_field','without_field')
	GROUP BY field_id, field_value
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	MODIFY COLUMN `visibility` enum('everyone','logged_out','logged_in','logged_in_with_field','logged_in_without_field','without_field','call_static_method','in_smart_group','logged_in_not_in_smart_group') NOT NULL DEFAULT 'everyone'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]nested_plugins`
	SET visibility = IF (visibility = 'logged_in_with_field', 'in_smart_group','logged_in_not_in_smart_group')
	WHERE is_slide = 1
	  AND visibility IN ('logged_in_with_field','logged_in_without_field','without_field')
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	DROP COLUMN field_id
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	DROP COLUMN field_value
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	MODIFY COLUMN `visibility` enum('everyone','logged_out','logged_in','call_static_method','in_smart_group','logged_in_not_in_smart_group') NOT NULL DEFAULT 'everyone'
_sql


//Fix some content type columns with the wrong definition
	//They should always be ascii
	//If there's another varchar/TEXT/BLOB field on the table, they should be varchar,
		//otherwise they should be char for faster indexing
	//Most should not have a default value
); revision( 37269
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories`
	MODIFY COLUMN `landing_page_content_type` varchar(20) CHARACTER SET ascii NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_cache`
	MODIFY COLUMN `content_type` varchar(20) CHARACTER SET ascii NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions`
	MODIFY COLUMN `type` varchar(20) CHARACTER SET ascii NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_items`
	MODIFY COLUMN `type` varchar(20) CHARACTER SET ascii NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	MODIFY COLUMN `content_type_id` varchar(20) CHARACTER SET ascii NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	MODIFY COLUMN `content_type` varchar(20) CHARACTER SET ascii NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_item_link`
	MODIFY COLUMN `content_type` varchar(20) CHARACTER SET ascii NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]spare_domain_names`
	MODIFY COLUMN `content_type` varchar(20) CHARACTER SET ascii NOT NULL
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	MODIFY COLUMN `content_type` varchar(20) CHARACTER SET ascii NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	MODIFY COLUMN `content_type` varchar(20) CHARACTER SET ascii NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]spare_aliases`
	MODIFY COLUMN `content_type` varchar(20) CHARACTER SET ascii NOT NULL default ''
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]category_item_link`
	MODIFY COLUMN `content_type` char(20) CHARACTER SET ascii NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]group_content_link`
	MODIFY COLUMN `content_type` char(20) CHARACTER SET ascii NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]translation_chains`
	MODIFY COLUMN `type` char(20) CHARACTER SET ascii NOT NULL
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]special_pages`
	MODIFY COLUMN `content_type` varchar(20) CHARACTER SET ascii
_sql




//More changes for slide visibility
//This time I'm trying to merge the "privacy" column for content items and the "visbility" column
//for slides, so everything has the same options and the same syntax.
//I'm also removing the "Private, can be viewed by the selected Extranet User(s)" option for content items,
//and setting anything that used it to "Send signal"/"No access".
); revision( 37270

//Update the privacy column in the translation_chains table with the new values
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]translation_chains`
	ADD COLUMN `new_privacy` enum(
		'public', 'logged_out', 'logged_in',
		'group_members', 'in_smart_group', 'logged_in_not_in_smart_group',
		'call_static_method', 'send_signal'
	) NOT NULL default 'public'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]translation_chains`
	SET `new_privacy` = 'send_signal'
	WHERE `privacy` IN ('specific_users', 'no_access')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]translation_chains`
	SET `new_privacy` = 'logged_in'
	WHERE `privacy` = 'all_extranet_users'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]translation_chains`
	SET `new_privacy` = `privacy`
	WHERE `privacy` NOT IN ('specific_users', 'no_access', 'all_extranet_users')
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]translation_chains`
	DROP COLUMN `privacy`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]translation_chains`
	CHANGE COLUMN `new_privacy` `privacy` enum(
		'public', 'logged_out', 'logged_in',
		'group_members', 'in_smart_group', 'logged_in_not_in_smart_group',
		'call_static_method', 'send_signal'
	) NOT NULL default 'public'
_sql


//Update the privacy column in the plugin nest table with the new values
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `privacy` enum(
		'public', 'logged_out', 'logged_in',
		'group_members', 'in_smart_group', 'logged_in_not_in_smart_group',
		'call_static_method'
	) NOT NULL default 'public'
	AFTER `visibility`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]nested_plugins`
	SET `privacy` = 'public'
	WHERE `visibility` = 'everyone'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]nested_plugins`
	SET `privacy` = `visibility`
	WHERE `visibility` != 'everyone'
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	DROP COLUMN `visibility`
_sql


//Add a column for smart groups to the translation_chains table
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]translation_chains`
	ADD COLUMN `smart_group_id` int(10) unsigned NOT NULL default 0
_sql


//Drop the user_content_link table as we're not using it any more without the "specific_users" option
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_content_link`
_sql

//Translation chains already have the group_content_link table, but slides don't
//have a table like this so we'll need to create a table to hold the groups that can see each slide.
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]group_slide_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]group_slide_link` (
		`instance_id` int(10) unsigned NOT NULL,
		`slide_id` int(10) unsigned NOT NULL,
		`group_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`instance_id`, `slide_id`, `group_id`),
		UNIQUE KEY (`slide_id`, `group_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 
_sql


//Create a table to hold the (rarely used) advanced privacy options for translation_chains
//I don't want to add these to the translation_chains table as currently all of the columns
//in the translation_chains table are fixed width, which makes the translation_chains table
//very quick to run queries on!
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]translation_chain_privacy`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]translation_chain_privacy` (
		`equiv_id` int(10) unsigned NOT NULL,
		`content_type` varchar(20) CHARACTER SET ascii NOT NULL,
		`module_class_name` varchar(200) NOT NULL default '',
		`method_name` varchar(127) NOT NULL default '',
		`param_1` varchar(200) NOT NULL default '',
		`param_2` varchar(200) NOT NULL default '',
		PRIMARY KEY (`equiv_id`, `content_type`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 
_sql

//Add a column for default content type permissions
); revision(37303
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	ADD COLUMN `default_permissions` enum('public', 'logged_in') NOT NULL DEFAULT 'public',
	ADD COLUMN `hide_private_item` tinyint(1) NOT NULL default 0
_sql


//Rename "data schema" to "asset type" anywhere it was picked in plugin settings
);	revision( 37460
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings`
	SET value = REPLACE(value, 'data_schema', 'schema')
	WHERE name IN ('mode', 'other_modes')
	  AND value LIKE '%data_schema%'
_sql


//Rename "list_asset_keys_and_data" to "view_asset_keys_and_data"
);	revision( 37600
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings`
	SET value = REPLACE(value, 'list_asset_keys_and_data', 'view_asset_keys_and_data')
	WHERE name IN ('mode', 'other_modes')
	  AND value LIKE '%list_asset_keys_and_data%'
_sql


//Add checkboxes for showing the back/refresh buttons
);	revision( 37890
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `show_back` tinyint(1) NOT NULL default 0
	AFTER `invisible_in_nav`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `show_refresh` tinyint(1) NOT NULL default 0
	AFTER `show_back`
_sql


//Add a checkbox that allows you to toggle a slide's visibility in admin mode
//(Note that the default is not visible, which is a change from the previous
// behaviour where slides were always visible.)
);	revision( 38400
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `always_visible_to_admins` tinyint(1) NOT NULL default 1
_sql


//Add the option to set a global command name on a slide
);	revision( 38500
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `global_command` varchar(100) NOT NULL default ''
	AFTER `show_refresh`
_sql


//Add the option to set the request variables that a slide uses
);	revision( 38600
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `request_vars` varchar(255) NOT NULL default ''
	AFTER `show_refresh`
_sql

//Attempt to auto-populate this column where possible based on the modes of the plugins picked
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` AS m
	INNER JOIN `[[DB_NAME_PREFIX]]nested_plugins` AS egg
	   ON egg.module_id = m.id
	  AND egg.is_slide = 0
	INNER JOIN `[[DB_NAME_PREFIX]]nested_plugins` AS slide
	   ON slide.instance_id = egg.instance_id
	  AND slide.tab = egg.tab
	  AND slide.is_slide = 1
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	   ON ps.instance_id = egg.instance_id
	  AND ps.nest = egg.id
	  AND ps.name = 'mode'
	LEFT JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps2
	   ON ps2.instance_id = egg.instance_id
	  AND ps2.nest = egg.id
	  AND ps2.name = 'scope_for_creation_and_lists'
	
	SET slide.request_vars = TRIM(BOTH ',' FROM
		CONCAT (
			IF (ps.value = 'edit_asset', 'assetId',
			IF (ps.value = 'view_asset_details', 'assetId',
	
			IF (ps.value = 'edit_data_pool', 'dataPoolId',
			IF (ps.value = 'view_data_pool_details', 'dataPoolId',
	
			IF (ps.value = 'list_locations', 'locationId',
			IF (ps.value = 'view_location_details', 'locationId',
	
			IF (ps.value = 'edit_schema', 'schemaId',
			IF (ps.value = 'delete_schema', 'schemaId',
			IF (ps.value = 'edit_schema', 'schemaId',
			IF (ps.value = 'delete_schema', 'schemaId',

			IF (ps.value = 'edit_schedule', 'scheduleId',
			IF (ps.value = 'edit_trigger', 'triggerId',
			IF (ps.value = 'edit_procedure', 'procedureId',
			
			''
			))))))))))))),
		',', 
			
			IF (ps2.value = 'company_locations', 'companyId',
			IF (ps2.value = 'specified_company_direct', 'companyId',
			IF (ps2.value = 'specified_company_indirect', 'companyId',
			
			IF (ps2.value = 'specified_location_direct', 'locationId',
			IF (ps2.value = 'asset_type_commands', 'schemaId',
			
			IF (ps2.value = 'asset_active_events', 'assetId',
			IF (ps2.value = 'assigned_to_parent_asset', 'assetId1',
			
			IF (ps2.value = 'system_assets', 'dataPoolId',
			IF (ps2.value = 'assigned_to_parent_data_pool', 'dataPoolId1',
			
			''
			)))))))))
		)
	)
	WHERE m.class_name = 'assetwolf_2'
_sql


//Add a plugin setting for plugin nests to turn on the conductor,
//and make sure it's turned on for any nest that already 
);	revision( 38660
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]plugin_settings`
	(instance_id, name, nest, value, is_content)
	SELECT np.instance_id, 'enable_conductor', 0, 1, ps.is_content
	FROM `[[DB_NAME_PREFIX]]nested_plugins` AS np
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	   ON ps.instance_id = np.instance_id
	  AND ps.nest = 0
	WHERE np.is_slide = 1
	  AND np.states != ''
	GROUP BY np.instance_id, ps.is_content
_sql

); revision(38669
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `show_auto_refresh` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_refresh`,
	ADD COLUMN `auto_refresh_interval` int(10) unsigned NOT NULL DEFAULT 60 AFTER `show_auto_refresh`
_sql

//Rename the "tab" column in the nested_plugins table to "slide number",
//and the "nest" column in the plugin_settings table to "egg id"
);	revision( 38820
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	CHANGE COLUMN `tab` `slide_num` smallint(4) unsigned NOT NULL default 1
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	CHANGE COLUMN `nest` `egg_id` int(10) unsigned NOT NULL default 0
_sql


//Convert the format of the site settings for external programs
);	revision( 39300
//Where the program is run from a directory in the environment PATH, just store the word "PATH".
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET
		value = 'PATH'
	WHERE (name, value) IN (
		('antiword_path', ''),
		('antiword_path', 'antiword'),
		('clamscan_tool_path', ''),
		('clamscan_tool_path', 'clamscan'),
		('ghostscript_path', ''),
		('ghostscript_path', 'gs'),
		('pdftotext_path', ''),
		('pdftotext_path', 'pdftotext')
	)
_sql

//Otherwise just store the path without the program's name
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET
		value = SUBSTR(value, 1, LENGTH(value) - 8)
	WHERE name = 'antiword_path'
	  AND value LIKE '%/antiword'
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET
		value = SUBSTR(value, 1, LENGTH(value) - 8)
	WHERE name = 'clamscan_tool_path'
	  AND value LIKE '%/clamscan'
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET
		value = SUBSTR(value, 1, LENGTH(value) - 2)
	WHERE name = 'ghostscript_path'
	  AND value LIKE '%/gs'
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET
		value = SUBSTR(value, 1, LENGTH(value) - 9)
	WHERE name = 'pdftotext_path'
	  AND value LIKE '%/pdftotext'
_sql


//Add an "encrypted" column to the site settings table
);	revision( 39400
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings`
	ADD COLUMN `encrypted` tinyint(1) NOT NULL default 0
_sql



//Add a couple of columns to the nested plugins table
//(N.b. this was added in an after-branch patch in 7.5 revision 38669, so we need to check if it's not already there.)
);	if (needRevision(39401) && !sqlNumRows('SHOW COLUMNS FROM '. DB_NAME_PREFIX. 'nested_plugins LIKE "show_auto_refresh"'))	revision(39401
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `show_auto_refresh` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_refresh`,
	ADD COLUMN `auto_refresh_interval` int(10) unsigned NOT NULL DEFAULT 60 AFTER `show_auto_refresh`
_sql


//Update the names of some Assetwolf plugin settings to the new format
);	revision( 39500
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings`
	SET name = 'enable.edit_asset'
	WHERE name = 'assetwolf__view_asset_name_and_links__show_edit_link'
_sql


//Replace the "close" command with the "back" command
);	revision( 39550
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]nested_paths`
	SET `commands` = 'back'
	WHERE `commands` = 'close'
_sql

, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]nested_paths`
	WHERE `commands` = 'close'
_sql




//Add the "with_role" option for content item/slide visibility
//Also do some tidying up and merge a few tables together
); revision( 39560

//Create a new table to store links between groups/roles and content items/slides
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]group_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]group_link` (
		`link_from` enum('chain', 'slide') NOT NULL,
		`link_from_id` int(10) unsigned NOT NULL,
		`link_from_char` char(20) CHARACTER SET ascii default '',
		`link_to` enum('group', 'role') NOT NULL,
		`link_to_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`link_from`, `link_from_id`, `link_from_char`, `link_to`, `link_to_id`),
		KEY (`link_to`, `link_to_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 
_sql


//Copy the existing data from the tables we are replacing
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]group_link`
	SELECT 'chain', equiv_id, content_type, 'group', group_id
	FROM `[[DB_NAME_PREFIX]]group_content_link`
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]group_link`
	SELECT 'slide', slide_id, '', 'group', group_id
	FROM `[[DB_NAME_PREFIX]]group_slide_link`
_sql


//Drop the old tables that we don't use any more
, <<<_sql
	DROP TABLE `[[DB_NAME_PREFIX]]group_content_link`
_sql

, <<<_sql
	DROP TABLE `[[DB_NAME_PREFIX]]group_slide_link`
_sql

, <<<_sql
	DROP TABLE `[[DB_NAME_PREFIX]]group_user_link`
_sql


//Update the privacy column in the translation_chains/nested_plugins table with a new option
//to link to roles
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]translation_chains`
	MODIFY COLUMN `privacy` enum(
		'public','logged_out','logged_in','group_members','in_smart_group','logged_in_not_in_smart_group','call_static_method',
		'send_signal',
		'with_role'
	) NOT NULL default 'public'
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	MODIFY COLUMN `privacy` enum(
		'public','logged_out','logged_in','group_members','in_smart_group','logged_in_not_in_smart_group','call_static_method',
		'with_role'
	) NOT NULL default 'public'
_sql


//Add the ability for conductor paths to link to a slide on another content item
);	revision( 39737
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_paths`
	ADD COLUMN `equiv_id` int(10) unsigned NOT NULL default 0
	AFTER `to_state`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_paths`
	ADD COLUMN `content_type` varchar(20) CHARACTER SET ascii NOT NULL default ''
	AFTER `equiv_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_paths`
	DROP KEY `instance_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_paths`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_paths`
	ADD PRIMARY KEY (`instance_id`, `from_state`, `equiv_id`, `content_type`, `to_state`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_paths`
	ADD KEY (`instance_id`, `to_state`, `from_state`)
_sql


//Add zenario_image_container as a CSS class to any image containers before they are migrated to banners,
//to hopefully keep any CSS styles that might have been applied to them.
);	revision( 39790
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_instances`
	SET css_class =
			IF (css_class = '',
				'zenario_image_container zenario_image_container__default_style',
				CONCAT(css_class, ' zenario_image_container')
			),
		framework =
			IF (framework = 'standard', 'image_then_title_then_text', framework)
	WHERE module_id IN (
		SELECT id
		FROM `[[DB_NAME_PREFIX]]modules`
		WHERE `class_name` = 'zenario_image_container'
	)
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]nested_plugins`
	SET css_class =
			IF (css_class = '',
				'zenario_image_container zenario_image_container__default_style',
				CONCAT(css_class, ' zenario_image_container')
			)
	WHERE is_slide = 0
	  AND module_id IN (
		SELECT id
		FROM `[[DB_NAME_PREFIX]]modules`
		WHERE `class_name` = 'zenario_image_container'
	)
_sql

//Add the image_source setting to any image containers, so they will work properly as banners
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `name`,
	  `egg_id`,
	  `value`,
	  `is_content`
	)
	SELECT 
	  ps.instance_id,
	  'image_source',
	  ps.egg_id,
	  '_CUSTOM_IMAGE',
	  ps.`is_content`
	FROM `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	WHERE ps.name = 'mobile_behavior'
_sql


//Delete the working_copy_image_threshold site setting if it was set to the default value
);	revision( 39800
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings`
	SET `value` = ''
	WHERE `name` = 'working_copy_image_threshold'
	  AND `value` = '66'
_sql


);	revision( 39830
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	ADD COLUMN `from_details` enum('site_settings', 'custom_details') NOT NULL DEFAULT 'custom_details'
	AFTER `subject`
_sql


);	revision( 39840
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents`
	ADD COLUMN `short_checksum_list` MEDIUMTEXT
_sql


//Rename "sticky images" to "feature images"
//Also add a new feature to automatically flag the first-uploaded image as a feature image
);	revision( 40000
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	ADD COLUMN `auto_flag_feature_image` tinyint(1) NOT NULL default 0
	AFTER `release_date_field`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions`
	DROP KEY `sticky_image_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions`
	CHANGE COLUMN `sticky_image_id` `feature_image_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions`
	ADD KEY (`feature_image_id`)
_sql

//Auto-flag feature images by default, unless someone goes into the content-type settings and turns it off
);	revision( 40020
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	MODIFY COLUMN `auto_flag_feature_image` tinyint(1) NOT NULL default 1
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_types`
	SET `auto_flag_feature_image` = 1
_sql


//Attempt to convert some columns with a utf8-3-byte character set to a 4-byte character set
);	revision( 40150
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories` MODIFY COLUMN `name` varchar(50) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]centralised_lists` SET `label` = SUBSTR(`label`, 1, 250) WHERE CHAR_LENGTH(`label`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]centralised_lists` MODIFY COLUMN `label` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_cache` MODIFY COLUMN `extract` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_cache` MODIFY COLUMN `text` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_items` MODIFY COLUMN `alias` varchar(75) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_items` MODIFY COLUMN `tag_id` varchar(32) CHARACTER SET ascii NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions` MODIFY COLUMN `content_summary` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions` MODIFY COLUMN `description` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_item_versions` SET `filename` = SUBSTR(`filename`, 6, 255) WHERE CHAR_LENGTH(`filename`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions` MODIFY COLUMN `filename` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions` MODIFY COLUMN `foot_html` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions` MODIFY COLUMN `head_html` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions` MODIFY COLUMN `keywords` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions` MODIFY COLUMN `rss_slot_name` varchar(100) CHARACTER SET ascii NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions` MODIFY COLUMN `title` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_item_versions` SET `writer_name` = SUBSTR(`writer_name`, 1, 250) WHERE CHAR_LENGTH(`writer_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions` MODIFY COLUMN `writer_name` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_datasets` MODIFY COLUMN `label` varchar(64) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]custom_dataset_field_values` SET `label` = SUBSTR(`label`, 1, 250) WHERE CHAR_LENGTH(`label`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_field_values` MODIFY COLUMN `label` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_field_values` MODIFY COLUMN `note_below` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_tabs` MODIFY COLUMN `label` varchar(32) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents` MODIFY COLUMN `extract` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]documents` SET `filename` = SUBSTR(`filename`, 6, 255) WHERE CHAR_LENGTH(`filename`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents` MODIFY COLUMN `filename` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]documents` SET `folder_name` = SUBSTR(`folder_name`, 1, 250) WHERE CHAR_LENGTH(`folder_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents` MODIFY COLUMN `folder_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]documents` SET `title` = SUBSTR(`title`, 1, 250) WHERE CHAR_LENGTH(`title`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents` MODIFY COLUMN `title` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_rules` MODIFY COLUMN `pattern` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_rules` MODIFY COLUMN `replacement` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_rules` MODIFY COLUMN `second_pattern` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_rules` MODIFY COLUMN `second_replacement` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]document_tags` SET `tag_name` = SUBSTR(`tag_name`, 1, 250) WHERE CHAR_LENGTH(`tag_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_tags` MODIFY COLUMN `tag_name` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_types` MODIFY COLUMN `mime_type` varchar(128) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_types` MODIFY COLUMN `type` varchar(10) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates` MODIFY COLUMN `bcc_email_address` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates` MODIFY COLUMN `body` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates` MODIFY COLUMN `cc_email_address` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates` MODIFY COLUMN `debug_email_address` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates` MODIFY COLUMN `email_address_from` varchar(100) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]email_templates` SET `email_name_from` = SUBSTR(`email_name_from`, 1, 250) WHERE CHAR_LENGTH(`email_name_from`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates` MODIFY COLUMN `email_name_from` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates` MODIFY COLUMN `head` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]email_templates` SET `module_class_name` = SUBSTR(`module_class_name`, 1, 250) WHERE CHAR_LENGTH(`module_class_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates` MODIFY COLUMN `module_class_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]email_templates` SET `subject` = SUBSTR(`subject`, 1, 250) WHERE CHAR_LENGTH(`subject`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates` MODIFY COLUMN `subject` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files` MODIFY COLUMN `alt_tag` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]files` SET `filename` = SUBSTR(`filename`, 6, 255) WHERE CHAR_LENGTH(`filename`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files` MODIFY COLUMN `filename` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files` MODIFY COLUMN `floating_box_title` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files` MODIFY COLUMN `mime_type` varchar(128) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]files` SET `path` = SUBSTR(`path`, 1, 250) WHERE CHAR_LENGTH(`path`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files` MODIFY COLUMN `path` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files` MODIFY COLUMN `title` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]image_tags` SET `name` = SUBSTR(`name`, 1, 250) WHERE CHAR_LENGTH(`name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]image_tags` MODIFY COLUMN `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]jobs` MODIFY COLUMN `email_address_on_action` varchar(200) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]jobs` MODIFY COLUMN `email_address_on_error` varchar(200) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]jobs` MODIFY COLUMN `email_address_on_no_action` varchar(200) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]job_logs` MODIFY COLUMN `note` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages` MODIFY COLUMN `detect_lang_codes` varchar(100) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]languages` SET `domain` = SUBSTR(`domain`, 1, 250) WHERE CHAR_LENGTH(`domain`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages` MODIFY COLUMN `domain` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts` MODIFY COLUMN `family_name` varchar(50) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts` MODIFY COLUMN `foot_html` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts` MODIFY COLUMN `head_html` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]layouts` SET `name` = SUBSTR(`name`, 1, 250) WHERE CHAR_LENGTH(`name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts` MODIFY COLUMN `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes` MODIFY COLUMN `rel_tag` varchar(100) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_sections` MODIFY COLUMN `section_name` varchar(20) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_text` MODIFY COLUMN `descriptive_text` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]menu_text` SET `name` = SUBSTR(`name`, 1, 250) WHERE CHAR_LENGTH(`name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_text` MODIFY COLUMN `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` SET `display_name` = SUBSTR(`display_name`, 1, 250) WHERE CHAR_LENGTH(`display_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules` MODIFY COLUMN `display_name` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins` MODIFY COLUMN `name_or_title` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins` MODIFY COLUMN `request_vars` varchar(250) CHARACTER SET ascii NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]page_preview_sizes` SET `description` = SUBSTR(`description`, 1, 250) WHERE CHAR_LENGTH(`description`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]page_preview_sizes` MODIFY COLUMN `description` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances` MODIFY COLUMN `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances` MODIFY COLUMN `slot_name` varchar(100) CHARACTER SET ascii NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instance_cache` MODIFY COLUMN `cache` mediumtext CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings` MODIFY COLUMN `foreign_key_char` varchar(250) CHARACTER SET ascii NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings` MODIFY COLUMN `foreign_key_to` varchar(64) CHARACTER SET ascii NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings` MODIFY COLUMN `value` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings` MODIFY COLUMN `value` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]skins` SET `display_name` = SUBSTR(`display_name`, 1, 250) WHERE CHAR_LENGTH(`display_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins` MODIFY COLUMN `display_name` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]smart_groups` MODIFY COLUMN `name` varchar(50) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]spare_aliases` MODIFY COLUMN `alias` varchar(75) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases` DROP KEY `module_class_name`
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases` ADD KEY (`module_class_name`(100),`language_id`,`code`(150))
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases` MODIFY COLUMN `code` text CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases` MODIFY COLUMN `local_text` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases` MODIFY COLUMN `seen_at_url` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]visitor_phrases` SET `seen_in_file` = SUBSTR(`seen_in_file`, 1, 250) WHERE CHAR_LENGTH(`seen_in_file`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases` MODIFY COLUMN `seen_in_file` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql

//More tweaks for zenario_image_container migration.
//Try to automatically set the "background image" plugin setting so they work more-or-less as they did before
);	revision( 40170
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `name`,
	  `egg_id`,
	  `value`,
	  `is_content`
	)
	SELECT 
	  ps.instance_id,
	  'background_image',
	  ps.egg_id,
	  '1',
	  ps.`is_content`
	FROM `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	WHERE ps.name = 'show_custom_css_code'
_sql

//Create a table to store redirects from replaced documents to replace the old short_checksum_list column
);	revision( 40190
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]document_public_redirects`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]document_public_redirects` (
		`document_id` int(10) unsigned NOT NULL,
		`file_id` int(10) unsigned NOT NULL,
		`path` varchar(255) NOT NULL,
		PRIMARY KEY (`document_id`, `path`(10))
	) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4
_sql

//Rename "possible events" to "triggers"
);	revision( 40300
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]site_settings`
	  SET name = REPLACE(name, 'possibleEvent', 'trigger')
	WHERE name LIKE '%possibleEvent%'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings`
	  SET name = REPLACE(name, 'possible_event', 'trigger')
	WHERE name IN ('enable.create_possible_event', 'enable.delete_possible_event', 'enable.edit_possible_event', 'enable.list_possible_events')
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings`
	  SET `value` = REPLACE(`value`, 'possible_event', 'trigger')
	WHERE name = 'mode'
	  AND `value` IN ('create_possible_event', 'list_possible_events', 'edit_possible_event')
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]nested_plugins`
	  SET request_vars = REPLACE(request_vars, 'possibleEventId', 'triggerId')
	WHERE request_vars LIKE '%possibleEventId%'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]nested_paths`
	  SET commands = REPLACE(commands, 'possible_event', 'trigger')
	WHERE commands LIKE '%possible_event%'
_sql


//Add the ability to import files in a skin
//(N.b. this was added in an after-branch patch in 7.6 revision 40191, so we need to check if it's not already there.)
);	if (needRevision(40400) && !sqlNumRows('SHOW COLUMNS FROM '. DB_NAME_PREFIX. 'skins LIKE "import"'))	revision( 40400
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD COLUMN `import` TEXT
	AFTER `extension_of_skin`
_sql



//Rename "asset types" and "data pool types" back to "schemas" again
);	revision( 40600
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]site_settings`
	  SET name = REPLACE(name, 'assetType', 'schema')
	WHERE name LIKE '%assetType%'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings`
	  SET name = REPLACE(name, 'asset_type', 'schema')
	WHERE name LIKE 'enable.%asset_type%'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings`
	  SET name = REPLACE(name, 'data_pool_type', 'schema')
	WHERE name LIKE 'enable.%data_pool_type%'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings`
	  SET `value` = REPLACE(REPLACE(`value`, 'asset_type', 'schema'), 'data_pool_type', 'schema')
	WHERE name = 'mode'
	  AND (`value` LIKE '%asset_type' OR `value` LIKE '%asset_types' OR `value` LIKE '%data_pool_type' OR `value` LIKE '%data_pool_types')
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]nested_plugins`
	  SET request_vars = REPLACE(request_vars, 'assetTypeId', 'schemaId')
	WHERE request_vars LIKE '%assetTypeId%'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]nested_paths`
	  SET commands = REPLACE(REPLACE(commands, 'asset_type', 'schema'), 'data_pool_type', 'schema')
	WHERE commands LIKE '%asset_type%' OR commands LIKE '%data_pool_type%'
_sql


//Fix a bug with the migration for plugin nests from back in version 7.5,
//where the "Apply slide-specific permissions" checkbox does not seem to be automatically checked
//where you had a slide with the "Call a module's static method to decide" option.
);	revision( 40785
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]nested_plugins`
	SET `privacy` = 'public'
	WHERE `privacy` = 'public'
	  AND `method_name` IS NOT NULL
	  AND `method_name` != ''
_sql


//Add an option to control whether a language shows placeholder content items from the default language
);	revision( 41200
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `show_untranslated_content_items` tinyint(1) NOT NULL default 0
	AFTER `translate_phrases`
_sql


//Add a "show embed" option for slides
);	revision( 41250
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `show_embed` tinyint(1) NOT NULL default 0
	AFTER `show_back`
_sql

);
