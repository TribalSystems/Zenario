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


//Create a table for storing smart group rules in the new format
);	revision( 31740
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]smart_group_rules`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]smart_group_rules` (
		`smart_group_id` int(10) unsigned NOT NULL,
		`ord` int(10) unsigned NOT NULL,
		`field_id` int(10) unsigned NOT NULL,
		`field2_id` int(10) unsigned NOT NULL default 0,
		`field3_id` int(10) unsigned NOT NULL default 0,
		`not` tinyint(1) NOT NULL default 0,
		`value` text,
		PRIMARY KEY (`smart_group_id`, `ord`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
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



//Remake the tuix_file_contents table to start tracking which panel types are used in which files
//(Technically I could have added the column and truncated the table, but I may as well
// drop and recreate it.)
);	revision( 32260
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]tuix_file_contents`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]tuix_file_contents` (
		`type` enum('admin_boxes','admin_toolbar','help','organizer','slot_controls','wizards') NOT NULL,
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

);