<?php
/*
 * Copyright (c) 2016, Tribal Limited
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

, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]site_settings`
	WHERE name = 'yaml_files_last_changed'
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

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	ADD COLUMN `hide_if_no_get_requests` tinyint(1) unsigned NOT NULL default 0
	AFTER `add_registered_get_requests`
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

//Remove one of the columns above (n.b. these two statements are completely removed in HEAD)
);	revision( 37090
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	DROP COLUMN `hide_if_no_get_requests`
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

);
