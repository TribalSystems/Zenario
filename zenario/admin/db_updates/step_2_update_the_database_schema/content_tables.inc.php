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






//Add a plugin setting for plugin nests to turn on the conductor,
//and make sure it's turned on for any existing conductors.
ze\dbAdm::revision( 38660
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

);	ze\dbAdm::revision(38669
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `show_auto_refresh` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_refresh`,
	ADD COLUMN `auto_refresh_interval` int(10) unsigned NOT NULL DEFAULT 60 AFTER `show_auto_refresh`
_sql

//Rename the "tab" column in the nested_plugins table to "slide number",
//and the "nest" column in the plugin_settings table to "egg id"
);	ze\dbAdm::revision( 38820
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	CHANGE COLUMN `tab` `slide_num` smallint(4) unsigned NOT NULL default 1
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	CHANGE COLUMN `nest` `egg_id` int(10) unsigned NOT NULL default 0
_sql


//Convert the format of the site settings for external programs
);	ze\dbAdm::revision( 39300
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
);	ze\dbAdm::revision( 39400
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings`
	ADD COLUMN `encrypted` tinyint(1) NOT NULL default 0
_sql



//Add a couple of columns to the nested plugins table
//(N.b. this was added in an after-branch patch in 7.5 revision 38669, so we need to check if it's not already there.)
);	if (ze\dbAdm::needRevision(39401) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_NAME_PREFIX. 'nested_plugins LIKE "show_auto_refresh"'))	ze\dbAdm::revision(39401
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `show_auto_refresh` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_refresh`,
	ADD COLUMN `auto_refresh_interval` int(10) unsigned NOT NULL DEFAULT 60 AFTER `show_auto_refresh`
_sql


//Update the names of some Assetwolf plugin settings to the new format
);	ze\dbAdm::revision( 39500
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings`
	SET name = 'enable.edit_asset'
	WHERE name = 'assetwolf__view_asset_name_and_links__show_edit_link'
_sql


//Replace the "close" command with the "back" command
);	ze\dbAdm::revision( 39550
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
); ze\dbAdm::revision( 39560

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
);	ze\dbAdm::revision( 39737
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
);	ze\dbAdm::revision( 39790
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
);	ze\dbAdm::revision( 39800
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings`
	SET `value` = ''
	WHERE `name` = 'working_copy_image_threshold'
	  AND `value` = '66'
_sql


);	ze\dbAdm::revision( 39830
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	ADD COLUMN `from_details` enum('site_settings', 'custom_details') NOT NULL DEFAULT 'custom_details'
	AFTER `subject`
_sql


);	ze\dbAdm::revision( 39840
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents`
	ADD COLUMN `short_checksum_list` MEDIUMTEXT
_sql


//Rename "sticky images" to "feature images"
//Also add a new feature to automatically flag the first-uploaded image as a feature image
);	ze\dbAdm::revision( 40000
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
);	ze\dbAdm::revision( 40020
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	MODIFY COLUMN `auto_flag_feature_image` tinyint(1) NOT NULL default 1
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_types`
	SET `auto_flag_feature_image` = 1
_sql


//Attempt to convert some columns with a utf8-3-byte character set to a 4-byte character set
);	ze\dbAdm::revision( 40150
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
);	ze\dbAdm::revision( 40170
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
);	ze\dbAdm::revision( 40190
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
);	ze\dbAdm::revision( 40300
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
);	if (ze\dbAdm::needRevision(40400) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_NAME_PREFIX. 'skins LIKE "import"'))	ze\dbAdm::revision( 40400
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD COLUMN `import` TEXT
	AFTER `extension_of_skin`
_sql



//Rename "asset types" and "data pool types" back to "schemas" again
);	ze\dbAdm::revision( 40600
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
);	ze\dbAdm::revision( 40785
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]nested_plugins`
	SET `privacy` = 'public'
	WHERE `privacy` = 'public'
	  AND `method_name` IS NOT NULL
	  AND `method_name` != ''
_sql


//Add an option to control whether a language shows placeholder content items from the default language
);	ze\dbAdm::revision( 41200
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `show_untranslated_content_items` tinyint(1) NOT NULL default 0
	AFTER `translate_phrases`
_sql


//Add a "show embed" option for slides
);	ze\dbAdm::revision( 41250
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `show_embed` tinyint(1) NOT NULL default 0
	AFTER `show_back`
_sql




//	Zenario 8 changes


//Move all of the settings for user permissions into a separate table to the site settings table
);	ze\dbAdm::revision( 41730
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_perm_settings`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]user_perm_settings` (
		`name` varchar(255) CHARACTER SET ascii NOT NULL,
		`value` varchar(255) CHARACTER SET ascii,
		PRIMARY KEY (`name`),
		KEY (`value`)
	)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings`
	MODIFY COLUMN `name` varchar(255) CHARACTER SET ascii NOT NULL
_sql

);	ze\dbAdm::revision( 41740
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]user_perm_settings`
	SELECT SUBSTR(name, 6), value
	FROM `[[DB_NAME_PREFIX]]site_settings`
	WHERE name LIKE 'perm.%'
_sql

//Rename a plugin setting in some Assetwolf plugins
);	ze\dbAdm::revision( 42190
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings` as ps1
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` as ps2
	   ON ps2.instance_id = ps1.instance_id
	  AND ps2.egg_id = ps1.egg_id
	  AND ps2.name IN ('enable.view_schema', 'enable.edit_schema')
	  AND ps2.value = 1
	SET ps2.name = 'enable.go_to_schema'
	WHERE ps1.name = 'mode'
	  AND ps1.value IN ('view_data_pool_name', 'view_data_pool_details', 'view_asset_details')
_sql

//Updated any "include" statements in Twig snippets created in Zenario 7 to the correct syntax in Zenario 8
);	ze\dbAdm::revision( 42280
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings`
	SET `value` = REPLACE(`value`, '{% include moduleDir(', '{% include ze(\'\', \'moduleDir\', ')
	WHERE name = 'html'
	  AND `value` LIKE '%{\% include moduleDir(%'
_sql

//Fix a bug where the user permissions were accidentally inserted back into the site settings
//table by an earlier db update
);	ze\dbAdm::revision( 42430
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]site_settings`
	WHERE name LIKE 'perm.%'
_sql


//Add a flag for which plugin on a slide can generate breadcrumbs
);	ze\dbAdm::revision( 42500
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `makes_breadcrumbs` tinyint(1) NOT NULL default 0
	AFTER `css_class`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD KEY `slide_num` (`instance_id`, `slide_num`, `ord`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD KEY `makes_breadcrumbs` (`instance_id`, `makes_breadcrumbs`)
_sql


//Add a flag for where the breadcrumbs should go next to the nested_paths table
);	ze\dbAdm::revision( 42550
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_paths`
	ADD COLUMN `is_forwards` tinyint(1) NOT NULL default 0
	AFTER `commands`
_sql


//Rename the "plugin_instance_cache" table to "plugin_instance_store",
//and add a is_cache flag, to reflect the fact that some things in there
//should be kept and not auto-deleted
);	ze\dbAdm::revision(42580
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_instance_store`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instance_cache`
	RENAME TO `[[DB_NAME_PREFIX]]plugin_instance_store`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instance_store`
	CHANGE COLUMN `cache` `store` mediumtext CHARACTER SET utf8mb4 NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instance_store`
	ADD COLUMN `is_cache` tinyint(1) NOT NULL default 1
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_instance_store`
	SET is_cache = 0
	WHERE method_name = '#conductor_positions#'
_sql


//Add an index to the name column in the plugin settings table,
//to make certain updates more efficient.
);	ze\dbAdm::revision( 42760
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD KEY (`name`, `egg_id`, `instance_id`)
_sql


//Companies, data-repeaters, schedules, schemas, triggers and procedures should be "global", not "unassigned".
);	ze\dbAdm::revision( 42770
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	LEFT JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON ps.instance_id = pi.id
	  AND ps.egg_id = 0
	  AND pi.module_id IN (SELECT id FROM `[[DB_NAME_PREFIX]]modules` WHERE class_name IN('zenario_banner'))
	LEFT JOIN `[[DB_NAME_PREFIX]]nested_plugins` AS np
	   ON ps.instance_id = np.instance_id
	  AND ps.egg_id = np.id
	  AND np.module_id IN (SELECT id FROM `[[DB_NAME_PREFIX]]modules` WHERE class_name IN('zenario_banner'))
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps2
	   ON ps.instance_id = ps2.instance_id
	  AND ps.egg_id = ps2.egg_id
	  
	  AND ps2.name = 'scope_for_creation_and_lists'
	  AND ps2.value = 'unassigned'
	SET ps2.value = 'global'

	WHERE (pi.id IS NOT NULL XOR np.id IS NOT NULL)
	  
	  AND ps.name = 'mode'
	  AND ps.value IN (
		'create_schema', 'list_schemas',
		'create_schedule', 'list_schedules',
		'create_procedure', 'list_procedures',
		'create_trigger', 'list_triggers', 'edit_trigger')
_sql


//Rename states to be lower case
);	ze\dbAdm::revision( 42780
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	CHANGE COLUMN `states` `old_states`
		set('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL')
		NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `states`
		set('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','aa','ab','ac','ad','ae','af','ag','ah','ai','aj','ak','al','am','an','ao','ap','aq','ar','as','at','au','av','aw','ax','ay','az','ba','bb','bc','bd','be','bf','bg','bh','bi','bj','bk','bl')
		NOT NULL default ''
		AFTER `global_command`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]nested_plugins`
	SET states = LOWER(old_states)
	WHERE old_states != ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	DROP COLUMN `old_states`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]nested_paths`
	SET from_state = LOWER(from_state),
		to_state = LOWER(to_state)
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_instance_store`
	SET store = LOWER(store)
	WHERE is_cache = 0
	  AND method_name = '#conductor_positions#'
_sql


//Rename another mode.
);	ze\dbAdm::revision( 42970
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings`
	SET value = 'list_node_hierarchy'
	WHERE value = 'list_da_hierarchy'
	  AND name = 'mode'
_sql


//Create a LOV for salutations
);	ze\dbAdm::revision( 43020
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]lov_salutations`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]lov_salutations` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(20) CHARACTER SET utf8mb4 NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`name`)
	) 
_sql

//Insert some initial values
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]lov_salutations` (name)
	VALUES ('Dr'), ('Miss'), ('Mr'), ('Mrs'), ('Ms'), ('Mx')
_sql


//Update the format of the selected menu node in the breadcrumbs plugin to be
//the format used in the position selector
);	ze\dbAdm::revision( 43090
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]menu_nodes` AS mn
	   ON ps.value = mn.id
	SET value = CONCAT(mn.section_id, '_', mn.id, '_0')
	WHERE ps.name IN ('breadcrumb_prefix_menu', 'bc_breadcrumb_prefix_menu')
_sql


//The background colour column should be ascii, not multilingual
);	ze\dbAdm::revision( 43100
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	MODIFY COLUMN `bg_color` varchar(64) CHARACTER SET ascii NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions`
	MODIFY COLUMN `bg_color` varchar(64) CHARACTER SET ascii NOT NULL default ''
_sql


//Add an option to include/exclude something from the site map
);	ze\dbAdm::revision( 43440
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions`
	ADD COLUMN `in_sitemap` tinyint(1) NOT NULL default 1
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_item_versions`
	ADD KEY (`in_sitemap`)
_sql


//A bug with the backups could cause the user_perm_settings table to be skipped.
//Recreate it if this is happened, so those backups are not invalidated.
//N.b. I don't want to recrate the table if it's correctly there, so this is a rare
//situation where I want to use "CREATE TABLE IF NOT EXISTS" in a db-update.
//Normally you must use "DROP TABLE IF EXISTS".
);	ze\dbAdm::revision( 43725
, <<<_sql
	CREATE TABLE IF NOT EXISTS `[[DB_NAME_PREFIX]]user_perm_settings` (
		`name` varchar(255) CHARACTER SET ascii NOT NULL,
		`value` varchar(255) CHARACTER SET ascii,
		PRIMARY KEY (`name`),
		KEY (`value`)
	)
_sql

);
