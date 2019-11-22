<?php
/*
 * Copyright (c) 2019, Tribal Limited
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
		//They will be run as is, however the subsitution string [[DB_PREFIX]] will be replaced by
		//the correct table name prefix.
		//For the first few SQL statements I have added below, I've gone out of my way to ensure
		//that they will not break if run twice. The reason for this is that they have already appeared
		//in the database change log, and I don't want to harm any database with the change log already applied.
		//From now on we won't have to worry about that, however.






//Add a plugin setting for plugin nests to turn on the conductor,
//and make sure it's turned on for any existing conductors.
ze\dbAdm::revision( 38660
, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]plugin_settings`
	(instance_id, name, nest, value, is_content)
	SELECT np.instance_id, 'enable_conductor', 0, 1, ps.is_content
	FROM `[[DB_PREFIX]]nested_plugins` AS np
	INNER JOIN `[[DB_PREFIX]]plugin_settings` AS ps
	   ON ps.instance_id = np.instance_id
	  AND ps.nest = 0
	WHERE np.is_slide = 1
	  AND np.states != ''
	GROUP BY np.instance_id, ps.is_content
	ORDER BY np.instance_id, ps.is_content
_sql

);	ze\dbAdm::revision(38669
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	ADD COLUMN `show_auto_refresh` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_refresh`,
	ADD COLUMN `auto_refresh_interval` int(10) unsigned NOT NULL DEFAULT 60 AFTER `show_auto_refresh`
_sql

//Rename the "tab" column in the nested_plugins table to "slide number",
//and the "nest" column in the plugin_settings table to "egg id"
);	ze\dbAdm::revision( 38820
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	CHANGE COLUMN `tab` `slide_num` smallint(4) unsigned NOT NULL default 1
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_settings`
	CHANGE COLUMN `nest` `egg_id` int(10) unsigned NOT NULL default 0
_sql


//Convert the format of the site settings for external programs
);	ze\dbAdm::revision( 39300
//Where the program is run from a directory in the environment PATH, just store the word "PATH".
, <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings` SET
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
	UPDATE `[[DB_PREFIX]]site_settings` SET
		value = SUBSTR(value, 1, LENGTH(value) - 8)
	WHERE name = 'antiword_path'
	  AND value LIKE '%/antiword'
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings` SET
		value = SUBSTR(value, 1, LENGTH(value) - 8)
	WHERE name = 'clamscan_tool_path'
	  AND value LIKE '%/clamscan'
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings` SET
		value = SUBSTR(value, 1, LENGTH(value) - 2)
	WHERE name = 'ghostscript_path'
	  AND value LIKE '%/gs'
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings` SET
		value = SUBSTR(value, 1, LENGTH(value) - 9)
	WHERE name = 'pdftotext_path'
	  AND value LIKE '%/pdftotext'
_sql


//Add an "encrypted" column to the site settings table
);	ze\dbAdm::revision( 39400
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]site_settings`
	ADD COLUMN `encrypted` tinyint(1) NOT NULL default 0
_sql



//Add a couple of columns to the nested plugins table
//(N.b. this was added in an after-branch patch in 7.5 revision 38669, so we need to check if it's not already there.)
);	if (ze\dbAdm::needRevision(39401) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'nested_plugins LIKE "show_auto_refresh"'))	ze\dbAdm::revision(39401
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	ADD COLUMN `show_auto_refresh` tinyint(1) NOT NULL DEFAULT '0' AFTER `show_refresh`,
	ADD COLUMN `auto_refresh_interval` int(10) unsigned NOT NULL DEFAULT 60 AFTER `show_auto_refresh`
_sql


//Update the names of some Assetwolf plugin settings to the new format
);	ze\dbAdm::revision( 39500
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings`
	SET name = 'enable.edit_asset'
	WHERE name = 'assetwolf__view_asset_name_and_links__show_edit_link'
_sql


//Replace the "close" command with the "back" command
);	ze\dbAdm::revision( 39550
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]nested_paths`
	SET `commands` = 'back'
	WHERE `commands` = 'close'
_sql

, <<<_sql
	DELETE FROM `[[DB_PREFIX]]nested_paths`
	WHERE `commands` = 'close'
_sql




//Add the "with_role" option for content item/slide visibility
//Also do some tidying up and merge a few tables together
); ze\dbAdm::revision( 39560

//Create a new table to store links between groups/roles and content items/slides
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]group_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]]group_link` (
		`link_from` enum('chain', 'slide') NOT NULL,
		`link_from_id` int(10) unsigned NOT NULL,
		`link_from_char` char(20) CHARACTER SET ascii default '',
		`link_to` enum('group', 'role') NOT NULL,
		`link_to_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`link_from`, `link_from_id`, `link_from_char`, `link_to`, `link_to_id`),
		KEY (`link_to`, `link_to_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8 
_sql


//Copy the existing data from the tables we are replacing
, <<<_sql
	INSERT INTO `[[DB_PREFIX]]group_link`
	SELECT 'chain', equiv_id, content_type, 'group', group_id
	FROM `[[DB_PREFIX]]group_content_link`
	ORDER BY equiv_id, content_type, group_id
_sql

, <<<_sql
	INSERT INTO `[[DB_PREFIX]]group_link`
	SELECT 'slide', slide_id, '', 'group', group_id
	FROM `[[DB_PREFIX]]group_slide_link`
	ORDER BY slide_id, group_id
_sql


//Drop the old tables that we don't use any more
, <<<_sql
	DROP TABLE `[[DB_PREFIX]]group_content_link`
_sql

, <<<_sql
	DROP TABLE `[[DB_PREFIX]]group_slide_link`
_sql

, <<<_sql
	DROP TABLE `[[DB_PREFIX]]group_user_link`
_sql


//Update the privacy column in the translation_chains/nested_plugins table with a new option
//to link to roles
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]translation_chains`
	MODIFY COLUMN `privacy` enum(
		'public','logged_out','logged_in','group_members','in_smart_group','logged_in_not_in_smart_group','call_static_method',
		'send_signal',
		'with_role'
	) NOT NULL default 'public'
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	MODIFY COLUMN `privacy` enum(
		'public','logged_out','logged_in','group_members','in_smart_group','logged_in_not_in_smart_group','call_static_method',
		'with_role'
	) NOT NULL default 'public'
_sql


//Add the ability for conductor paths to link to a slide on another content item
);	ze\dbAdm::revision( 39737
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_paths`
	ADD COLUMN `equiv_id` int(10) unsigned NOT NULL default 0
	AFTER `to_state`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_paths`
	ADD COLUMN `content_type` varchar(20) CHARACTER SET ascii NOT NULL default ''
	AFTER `equiv_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_paths`
	DROP KEY `instance_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_paths`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_paths`
	ADD PRIMARY KEY (`instance_id`, `from_state`, `equiv_id`, `content_type`, `to_state`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_paths`
	ADD KEY (`instance_id`, `to_state`, `from_state`)
_sql


//Add zenario_image_container as a CSS class to any image containers before they are migrated to banners,
//to hopefully keep any CSS styles that might have been applied to them.
);	ze\dbAdm::revision( 39790
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_instances`
	SET css_class =
			IF (css_class = '',
				'zenario_image_container zenario_image_container__default_style',
				CONCAT(css_class, ' zenario_image_container')
			),
		framework =
			IF (framework = 'standard', 'image_then_title_then_text', framework)
	WHERE module_id IN (
		SELECT id
		FROM `[[DB_PREFIX]]modules`
		WHERE `class_name` = 'zenario_image_container'
	)
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]nested_plugins`
	SET css_class =
			IF (css_class = '',
				'zenario_image_container zenario_image_container__default_style',
				CONCAT(css_class, ' zenario_image_container')
			)
	WHERE is_slide = 0
	  AND module_id IN (
		SELECT id
		FROM `[[DB_PREFIX]]modules`
		WHERE `class_name` = 'zenario_image_container'
	)
_sql

//Add the image_source setting to any image containers, so they will work properly as banners
, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]plugin_settings` (
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
	FROM `[[DB_PREFIX]]plugin_settings` AS ps
	WHERE ps.name = 'mobile_behavior'
	ORDER BY ps.instance_id, ps.egg_id
_sql


//Delete the working_copy_image_threshold site setting if it was set to the default value
);	ze\dbAdm::revision( 39800
, <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET `value` = ''
	WHERE `name` = 'working_copy_image_threshold'
	  AND `value` = '66'
_sql


);	ze\dbAdm::revision( 39830
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates`
	ADD COLUMN `from_details` enum('site_settings', 'custom_details') NOT NULL DEFAULT 'custom_details'
	AFTER `subject`
_sql


);	ze\dbAdm::revision( 39840
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]documents`
	ADD COLUMN `short_checksum_list` MEDIUMTEXT
_sql


//Rename "sticky images" to "feature images"
//Also add a new feature to automatically flag the first-uploaded image as a feature image
);	ze\dbAdm::revision( 40000
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_types`
	ADD COLUMN `auto_flag_feature_image` tinyint(1) NOT NULL default 0
	AFTER `release_date_field`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	DROP KEY `sticky_image_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	CHANGE COLUMN `sticky_image_id` `feature_image_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	ADD KEY (`feature_image_id`)
_sql

//Auto-flag feature images by default, unless someone goes into the content-type settings and turns it off
);	ze\dbAdm::revision( 40020
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_types`
	MODIFY COLUMN `auto_flag_feature_image` tinyint(1) NOT NULL default 1
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]content_types`
	SET `auto_flag_feature_image` = 1
_sql


//Attempt to convert some columns with a utf8-3-byte character set to a 4-byte character set
);	ze\dbAdm::revision( 40150
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]categories` MODIFY COLUMN `name` varchar(50) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]centralised_lists` SET `label` = SUBSTR(`label`, 1, 250) WHERE CHAR_LENGTH(`label`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]centralised_lists` MODIFY COLUMN `label` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_cache` MODIFY COLUMN `extract` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_cache` MODIFY COLUMN `text` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_items` MODIFY COLUMN `alias` varchar(75) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_items` MODIFY COLUMN `tag_id` varchar(32) CHARACTER SET ascii NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions` MODIFY COLUMN `content_summary` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions` MODIFY COLUMN `description` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]content_item_versions` SET `filename` = SUBSTR(`filename`, 6, 255) WHERE CHAR_LENGTH(`filename`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions` MODIFY COLUMN `filename` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions` MODIFY COLUMN `foot_html` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions` MODIFY COLUMN `head_html` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions` MODIFY COLUMN `keywords` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions` MODIFY COLUMN `rss_slot_name` varchar(100) CHARACTER SET ascii NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions` MODIFY COLUMN `title` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]content_item_versions` SET `writer_name` = SUBSTR(`writer_name`, 1, 250) WHERE CHAR_LENGTH(`writer_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions` MODIFY COLUMN `writer_name` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_datasets` MODIFY COLUMN `label` varchar(64) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]custom_dataset_field_values` SET `label` = SUBSTR(`label`, 1, 250) WHERE CHAR_LENGTH(`label`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_field_values` MODIFY COLUMN `label` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_field_values` MODIFY COLUMN `note_below` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_tabs` MODIFY COLUMN `label` varchar(32) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]documents` MODIFY COLUMN `extract` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]documents` SET `filename` = SUBSTR(`filename`, 6, 255) WHERE CHAR_LENGTH(`filename`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]documents` MODIFY COLUMN `filename` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]documents` SET `folder_name` = SUBSTR(`folder_name`, 1, 250) WHERE CHAR_LENGTH(`folder_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]documents` MODIFY COLUMN `folder_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]documents` SET `title` = SUBSTR(`title`, 1, 250) WHERE CHAR_LENGTH(`title`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]documents` MODIFY COLUMN `title` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]document_rules` MODIFY COLUMN `pattern` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]document_rules` MODIFY COLUMN `replacement` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]document_rules` MODIFY COLUMN `second_pattern` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]document_rules` MODIFY COLUMN `second_replacement` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]document_tags` SET `tag_name` = SUBSTR(`tag_name`, 1, 250) WHERE CHAR_LENGTH(`tag_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]document_tags` MODIFY COLUMN `tag_name` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]document_types` MODIFY COLUMN `mime_type` varchar(128) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]document_types` MODIFY COLUMN `type` varchar(10) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates` MODIFY COLUMN `bcc_email_address` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates` MODIFY COLUMN `body` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates` MODIFY COLUMN `cc_email_address` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates` MODIFY COLUMN `debug_email_address` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates` MODIFY COLUMN `email_address_from` varchar(100) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]email_templates` SET `email_name_from` = SUBSTR(`email_name_from`, 1, 250) WHERE CHAR_LENGTH(`email_name_from`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates` MODIFY COLUMN `email_name_from` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates` MODIFY COLUMN `head` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]email_templates` SET `module_class_name` = SUBSTR(`module_class_name`, 1, 250) WHERE CHAR_LENGTH(`module_class_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates` MODIFY COLUMN `module_class_name` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]email_templates` SET `subject` = SUBSTR(`subject`, 1, 250) WHERE CHAR_LENGTH(`subject`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates` MODIFY COLUMN `subject` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files` MODIFY COLUMN `alt_tag` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]files` SET `filename` = SUBSTR(`filename`, 6, 255) WHERE CHAR_LENGTH(`filename`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files` MODIFY COLUMN `filename` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files` MODIFY COLUMN `floating_box_title` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files` MODIFY COLUMN `mime_type` varchar(128) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]files` SET `path` = SUBSTR(`path`, 1, 250) WHERE CHAR_LENGTH(`path`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files` MODIFY COLUMN `path` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files` MODIFY COLUMN `title` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]image_tags` SET `name` = SUBSTR(`name`, 1, 250) WHERE CHAR_LENGTH(`name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]image_tags` MODIFY COLUMN `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]jobs` MODIFY COLUMN `email_address_on_action` varchar(200) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]jobs` MODIFY COLUMN `email_address_on_error` varchar(200) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]jobs` MODIFY COLUMN `email_address_on_no_action` varchar(200) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]job_logs` MODIFY COLUMN `note` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]languages` MODIFY COLUMN `detect_lang_codes` varchar(100) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]languages` SET `domain` = SUBSTR(`domain`, 1, 250) WHERE CHAR_LENGTH(`domain`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]languages` MODIFY COLUMN `domain` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layouts` MODIFY COLUMN `family_name` varchar(50) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layouts` MODIFY COLUMN `foot_html` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layouts` MODIFY COLUMN `head_html` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]layouts` SET `name` = SUBSTR(`name`, 1, 250) WHERE CHAR_LENGTH(`name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layouts` MODIFY COLUMN `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]menu_nodes` MODIFY COLUMN `rel_tag` varchar(100) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]menu_sections` MODIFY COLUMN `section_name` varchar(20) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]menu_text` MODIFY COLUMN `descriptive_text` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]menu_text` SET `name` = SUBSTR(`name`, 1, 250) WHERE CHAR_LENGTH(`name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]menu_text` MODIFY COLUMN `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]modules` SET `display_name` = SUBSTR(`display_name`, 1, 250) WHERE CHAR_LENGTH(`display_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]modules` MODIFY COLUMN `display_name` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins` MODIFY COLUMN `name_or_title` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins` MODIFY COLUMN `request_vars` varchar(250) CHARACTER SET ascii NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]page_preview_sizes` SET `description` = SUBSTR(`description`, 1, 250) WHERE CHAR_LENGTH(`description`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]page_preview_sizes` MODIFY COLUMN `description` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_instances` MODIFY COLUMN `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_instances` MODIFY COLUMN `slot_name` varchar(100) CHARACTER SET ascii NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_instance_cache` MODIFY COLUMN `cache` mediumtext CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_settings` MODIFY COLUMN `foreign_key_char` varchar(250) CHARACTER SET ascii NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_settings` MODIFY COLUMN `foreign_key_to` varchar(64) CHARACTER SET ascii NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_settings` MODIFY COLUMN `value` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]site_settings` MODIFY COLUMN `value` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]skins` SET `display_name` = SUBSTR(`display_name`, 1, 250) WHERE CHAR_LENGTH(`display_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]skins` MODIFY COLUMN `display_name` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]smart_groups` MODIFY COLUMN `name` varchar(50) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]spare_aliases` MODIFY COLUMN `alias` varchar(75) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]visitor_phrases` DROP KEY `module_class_name`
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]visitor_phrases` ADD KEY (`module_class_name`(100),`language_id`,`code`(150))
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]visitor_phrases` MODIFY COLUMN `code` text CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]visitor_phrases` MODIFY COLUMN `local_text` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]visitor_phrases` MODIFY COLUMN `seen_at_url` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]visitor_phrases` SET `seen_in_file` = SUBSTR(`seen_in_file`, 1, 250) WHERE CHAR_LENGTH(`seen_in_file`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]visitor_phrases` MODIFY COLUMN `seen_in_file` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql

//More tweaks for zenario_image_container migration.
//Try to automatically set the "background image" plugin setting so they work more-or-less as they did before
);	ze\dbAdm::revision( 40170
, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]plugin_settings` (
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
	FROM `[[DB_PREFIX]]plugin_settings` AS ps
	WHERE ps.name = 'show_custom_css_code'
	ORDER BY ps.instance_id, ps.egg_id
_sql

//Create a table to store redirects from replaced documents to replace the old short_checksum_list column
);	ze\dbAdm::revision( 40190
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]document_public_redirects`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]]document_public_redirects` (
		`document_id` int(10) unsigned NOT NULL,
		`file_id` int(10) unsigned NOT NULL,
		`path` varchar(255) NOT NULL,
		PRIMARY KEY (`document_id`, `path`(10))
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8mb4
_sql

//Rename "possible events" to "triggers"
);	ze\dbAdm::revision( 40300
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]site_settings`
	  SET name = REPLACE(name, 'possibleEvent', 'trigger')
	WHERE name LIKE '%possibleEvent%'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings`
	  SET name = REPLACE(name, 'possible_event', 'trigger')
	WHERE name IN ('enable.create_possible_event', 'enable.delete_possible_event', 'enable.edit_possible_event', 'enable.list_possible_events')
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings`
	  SET `value` = REPLACE(`value`, 'possible_event', 'trigger')
	WHERE name = 'mode'
	  AND `value` IN ('create_possible_event', 'list_possible_events', 'edit_possible_event')
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]nested_plugins`
	  SET request_vars = REPLACE(request_vars, 'possibleEventId', 'triggerId')
	WHERE request_vars LIKE '%possibleEventId%'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]nested_paths`
	  SET commands = REPLACE(commands, 'possible_event', 'trigger')
	WHERE commands LIKE '%possible_event%'
_sql


//Add the ability to import files in a skin
//(N.b. this was added in an after-branch patch in 7.6 revision 40191, so we need to check if it's not already there.)
);	if (ze\dbAdm::needRevision(40400) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'skins LIKE "import"'))	ze\dbAdm::revision( 40400
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]skins`
	ADD COLUMN `import` TEXT
	AFTER `extension_of_skin`
_sql



//Rename "asset types" and "data pool types" back to "schemas" again
);	ze\dbAdm::revision( 40600
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]site_settings`
	  SET name = REPLACE(name, 'assetType', 'schema')
	WHERE name LIKE '%assetType%'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings`
	  SET name = REPLACE(name, 'asset_type', 'schema')
	WHERE name LIKE 'enable.%asset_type%'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings`
	  SET name = REPLACE(name, 'data_pool_type', 'schema')
	WHERE name LIKE 'enable.%data_pool_type%'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings`
	  SET `value` = REPLACE(REPLACE(`value`, 'asset_type', 'schema'), 'data_pool_type', 'schema')
	WHERE name = 'mode'
	  AND (`value` LIKE '%asset_type' OR `value` LIKE '%asset_types' OR `value` LIKE '%data_pool_type' OR `value` LIKE '%data_pool_types')
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]nested_plugins`
	  SET request_vars = REPLACE(request_vars, 'assetTypeId', 'schemaId')
	WHERE request_vars LIKE '%assetTypeId%'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]nested_paths`
	  SET commands = REPLACE(REPLACE(commands, 'asset_type', 'schema'), 'data_pool_type', 'schema')
	WHERE commands LIKE '%asset_type%' OR commands LIKE '%data_pool_type%'
_sql


//Fix a bug with the migration for plugin nests from back in version 7.5,
//where the "Apply slide-specific permissions" checkbox does not seem to be automatically checked
//where you had a slide with the "Call a module's static method to decide" option.
);	ze\dbAdm::revision( 40785
, <<<_sql
	UPDATE `[[DB_PREFIX]]nested_plugins`
	SET `privacy` = 'public'
	WHERE `privacy` = 'public'
	  AND `method_name` IS NOT NULL
	  AND `method_name` != ''
_sql


//Add an option to control whether a language shows placeholder content items from the default language
);	ze\dbAdm::revision( 41200
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]languages`
	ADD COLUMN `show_untranslated_content_items` tinyint(1) NOT NULL default 0
	AFTER `translate_phrases`
_sql


//Add a "show embed" option for slides
);	ze\dbAdm::revision( 41250
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	ADD COLUMN `show_embed` tinyint(1) NOT NULL default 0
	AFTER `show_back`
_sql




//	Zenario 8 changes


//Move all of the settings for user permissions into a separate table to the site settings table
);	ze\dbAdm::revision( 41730
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]user_perm_settings`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]]user_perm_settings` (
		`name` varchar(255) CHARACTER SET ascii NOT NULL,
		`value` varchar(255) CHARACTER SET ascii,
		PRIMARY KEY (`name`),
		KEY (`value`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8 
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]site_settings`
	MODIFY COLUMN `name` varchar(255) CHARACTER SET ascii NOT NULL
_sql

);	ze\dbAdm::revision( 41740
, <<<_sql
	INSERT INTO `[[DB_PREFIX]]user_perm_settings`
	SELECT SUBSTR(name, 6), value
	FROM `[[DB_PREFIX]]site_settings`
	WHERE name LIKE 'perm.%'
	ORDER BY name
_sql

//Rename a plugin setting in some Assetwolf plugins
);	ze\dbAdm::revision( 42190
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings` as ps1
	INNER JOIN `[[DB_PREFIX]]plugin_settings` as ps2
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
	UPDATE `[[DB_PREFIX]]plugin_settings`
	SET `value` = REPLACE(`value`, '{% include moduleDir(', '{% include ze(\'\', \'moduleDir\', ')
	WHERE name = 'html'
	  AND `value` LIKE '%{\% include moduleDir(%'
_sql

//Fix a bug where the user permissions were accidentally inserted back into the site settings
//table by an earlier db update
);	ze\dbAdm::revision( 42430
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]site_settings`
	WHERE name LIKE 'perm.%'
_sql


//Add a flag for which plugin on a slide can generate breadcrumbs
);	ze\dbAdm::revision( 42500
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	ADD COLUMN `makes_breadcrumbs` tinyint(1) NOT NULL default 0
	AFTER `css_class`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	ADD KEY `slide_num` (`instance_id`, `slide_num`, `ord`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	ADD KEY `makes_breadcrumbs` (`instance_id`, `makes_breadcrumbs`)
_sql


//Add a flag for where the breadcrumbs should go next to the nested_paths table
);	ze\dbAdm::revision( 42550
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_paths`
	ADD COLUMN `is_forwards` tinyint(1) NOT NULL default 0
	AFTER `commands`
_sql


//Rename the "plugin_instance_cache" table to "plugin_instance_store",
//and add a is_cache flag, to reflect the fact that some things in there
//should be kept and not auto-deleted
);	ze\dbAdm::revision(42580
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]plugin_instance_store`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_instance_cache`
	RENAME TO `[[DB_PREFIX]]plugin_instance_store`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_instance_store`
	CHANGE COLUMN `cache` `store` mediumtext CHARACTER SET utf8mb4 NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_instance_store`
	ADD COLUMN `is_cache` tinyint(1) NOT NULL default 1
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_instance_store`
	SET is_cache = 0
	WHERE method_name = '#conductor_positions#'
_sql


//Add an index to the name column in the plugin settings table,
//to make certain updates more efficient.
);	ze\dbAdm::revision( 42760
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_settings`
	ADD KEY (`name`, `egg_id`, `instance_id`)
_sql


//Companies, data-repeaters, schedules, schemas, triggers and procedures should be "global", not "unassigned".
);	ze\dbAdm::revision( 42770
, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings` AS ps
	LEFT JOIN `[[DB_PREFIX]]plugin_instances` AS pi
	   ON ps.instance_id = pi.id
	  AND ps.egg_id = 0
	  AND pi.module_id IN (SELECT id FROM `[[DB_PREFIX]]modules` WHERE class_name IN('zenario_banner'))
	LEFT JOIN `[[DB_PREFIX]]nested_plugins` AS np
	   ON ps.instance_id = np.instance_id
	  AND ps.egg_id = np.id
	  AND np.module_id IN (SELECT id FROM `[[DB_PREFIX]]modules` WHERE class_name IN('zenario_banner'))
	INNER JOIN `[[DB_PREFIX]]plugin_settings` AS ps2
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
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	CHANGE COLUMN `states` `old_states`
		set('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL')
		NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	ADD COLUMN `states`
		set('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','aa','ab','ac','ad','ae','af','ag','ah','ai','aj','ak','al','am','an','ao','ap','aq','ar','as','at','au','av','aw','ax','ay','az','ba','bb','bc','bd','be','bf','bg','bh','bi','bj','bk','bl')
		NOT NULL default ''
		AFTER `global_command`
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]nested_plugins`
	SET states = LOWER(old_states)
	WHERE old_states != ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	DROP COLUMN `old_states`
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]nested_paths`
	SET from_state = LOWER(from_state),
		to_state = LOWER(to_state)
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_instance_store`
	SET store = LOWER(store)
	WHERE is_cache = 0
	  AND method_name = '#conductor_positions#'
_sql


//Rename another mode.
);	ze\dbAdm::revision( 42970
, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings`
	SET value = 'list_node_hierarchy'
	WHERE value = 'list_da_hierarchy'
	  AND name = 'mode'
_sql


//Create a LOV for salutations
);	ze\dbAdm::revision( 43020
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]lov_salutations`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]]lov_salutations` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(20) CHARACTER SET utf8mb4 NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`name`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8 
_sql

//Insert some initial values
, <<<_sql
	INSERT INTO `[[DB_PREFIX]]lov_salutations` (name)
	VALUES ('Dr'), ('Miss'), ('Mr'), ('Mrs'), ('Ms'), ('Mx')
_sql


//Update the format of the selected menu node in the breadcrumbs plugin to be
//the format used in the position selector
);	ze\dbAdm::revision( 43090
, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_PREFIX]]menu_nodes` AS mn
	   ON ps.value = mn.id
	SET value = CONCAT(mn.section_id, '_', mn.id, '_0')
	WHERE ps.name IN ('breadcrumb_prefix_menu', 'bc_breadcrumb_prefix_menu')
_sql


//The background colour column should be ascii, not multilingual
);	ze\dbAdm::revision( 43100
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layouts`
	MODIFY COLUMN `bg_color` varchar(64) CHARACTER SET ascii NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	MODIFY COLUMN `bg_color` varchar(64) CHARACTER SET ascii NOT NULL default ''
_sql


//Add an option to include/exclude something from the site map
);	ze\dbAdm::revision( 43440
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	ADD COLUMN `in_sitemap` tinyint(1) NOT NULL default 1
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	ADD KEY (`in_sitemap`)
_sql

//Rename the publication_date column to release_date
);	ze\dbAdm::revision( 43770
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	CHANGE COLUMN `publication_date` `release_date` datetime NULL default NULL
_sql


//Rename yet another mode.
);	ze\dbAdm::revision( 44000
, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings`
	SET value = 'edit_global_setup_options'
	WHERE value = 'edit_options'
	  AND name = 'mode'
_sql


//A bug with the backups could cause the user_perm_settings table to be skipped.
//Recreate it if this is happened, so those backups are not invalidated.
//N.b. I don't want to recrate the table if it's correctly there, so this is a rare
//situation where I want to use "CREATE TABLE IF NOT EXISTS" in a db-update.
//Normally you must use "DROP TABLE IF EXISTS".
);	ze\dbAdm::revision( 44265
, <<<_sql
	CREATE TABLE IF NOT EXISTS `[[DB_PREFIX]]user_perm_settings` (
		`name` varchar(255) CHARACTER SET ascii NOT NULL,
		`value` varchar(255) CHARACTER SET ascii,
		PRIMARY KEY (`name`),
		KEY (`value`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8 
_sql


//Rename scheduled calculations to metrics - which means renaming yet more modes, settings and paths!
);	ze\dbAdm::revision( 44500
, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings`
	SET value = 'create_metric'
	WHERE value = 'create_scheduled_calc'
	  AND name = 'mode'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings`
	SET value = 'edit_metric'
	WHERE value = 'edit_scheduled_calc'
	  AND name = 'mode'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings`
	SET value = 'list_metrics'
	WHERE value = 'list_scheduled_calcs'
	  AND name = 'mode'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings`
	SET name = 'enable.create_metric'
	WHERE name = 'enable.create_scheduled_calc'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings`
	SET name = 'enable.edit_metric'
	WHERE name = 'enable.edit_scheduled_calc'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings`
	SET name = 'enable.delete_scheduled_calc'
	WHERE name = 'enable.delete_scheduled_calc'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings`
	SET value = REPLACE(value, 'scheduled_calc', 'metric')
	WHERE name = 'source_and_storage'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]nested_plugins`
	SET request_vars = REPLACE(request_vars, 'scheduledCalcId', 'metricId')
	WHERE request_vars != ''
	  AND is_slide = 1
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]nested_paths`
	SET commands = 'create_metric'
	WHERE commands = 'create_scheduled_calc'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]nested_paths`
	SET commands = 'edit_metric'
	WHERE commands = 'edit_scheduled_calc'
_sql

//Add a column to store whether an email template uses the standard email template or not
);	ze\dbAdm::revision( 44502
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates`
	ADD COLUMN `use_standard_email_template` tinyint(1) NOT NULL default 0
_sql

//Add options to delete email logs on a per-template basis
//(N.b. this was added in an after-branch patch in 8.1 revision 44268, so we need to check if it's not already there.)
);	if (ze\dbAdm::needRevision(44503) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'email_templates LIKE "period_to_delete_log_headers"'))	ze\dbAdm::revision( 44503
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates`
	ADD COLUMN `period_to_delete_log_headers` varchar(255) NOT NULL DEFAULT '',
	ADD COLUMN `period_to_delete_log_content` varchar(255) NOT NULL DEFAULT ''
_sql

//Add a column to the nested_paths table to track the descendants of each state
//N.b. they're only actually used on back-links, as right now they're only needed when someone presses a "back" button.
);	ze\dbAdm::revision( 44700
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_paths`
	ADD COLUMN `descendants`
		set('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','aa','ab','ac','ad','ae','af','ag','ah','ai','aj','ak','al','am','an','ao','ap','aq','ar','as','at','au','av','aw','ax','ay','az','ba','bb','bc','bd','be','bf','bg','bh','bi','bj','bk','bl')
		NOT NULL default ''
		AFTER `commands`
_sql


//Add a hierarchical_var variable to the nested paths table
);	ze\dbAdm::revision( 44785
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_paths`
	ADD COLUMN `hierarchical_var` varchar(32) CHARACTER SET ascii NOT NULL default ''
	AFTER `commands`
_sql


//Add an is_custom flag to the nested_paths table
);	ze\dbAdm::revision( 44786
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_paths`
	ADD COLUMN `is_custom` tinyint(1) NOT NULL default 0
	AFTER `commands`
_sql


//Add an is_custom flag to the nested_paths table
);	ze\dbAdm::revision( 44788
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_paths`
	ADD COLUMN `request_vars` varchar(250) CHARACTER SET ascii NOT NULL default ''
	AFTER `is_custom`
_sql

//Reset all of the slide request_vars and descendants
, <<<_sql
	UPDATE `[[DB_PREFIX]]nested_plugins`
	SET request_vars = ''
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]nested_paths`
	SET descendants = ''
_sql


//Rename the "commands" column to "command", as it only ever stores one command.
//Also convert from a TEXT to a varchar for better efficiency.
);	ze\dbAdm::revision( 44799
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]nested_paths`
	WHERE `commands` IS NULL
	   OR `commands` = ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_paths`
	CHANGE COLUMN `commands` `command` varchar(255) CHARACTER SET ascii NOT NULL
_sql


//Add a slide number column to the nested_paths table
);	ze\dbAdm::revision( 44800
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_paths`
	ADD COLUMN `slide_num` smallint(4) unsigned NOT NULL default 0
	AFTER `instance_id`
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]nested_paths` AS path
	INNER JOIN `[[DB_PREFIX]]nested_plugins` AS slide
	   ON slide.instance_id = path.instance_id
	  AND slide.is_slide = 1
	  AND FIND_IN_SET(path.from_state, slide.states)
	SET path.slide_num = slide.slide_num
_sql


//Update the format of the "makes breadcrumbs" column
//The old format was:
	//0 - Doesn't make breadcrumbs
	//1 - Makes breadcrumbs
	//2 - Makes breadcrumbs and is hidden
//The new format is:
	//0 - Can't make breadcrumbs
	//1 - Can but doesn't make breadcrumbs
	//2 - Makes breadcrumbs
	//3 - Makes breadcrumbs and is hidden
);	ze\dbAdm::revision( 44890
, <<<_sql
	UPDATE `[[DB_PREFIX]]nested_plugins`
	SET makes_breadcrumbs = makes_breadcrumbs + 1
	WHERE makes_breadcrumbs > 0
_sql


//Combine two frameworks for the meta-data plugin nest into one, and replace
//the choice with a plugin setting
);	ze\dbAdm::revision( 44910
, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `egg_id`,
	  `name`,
	  `value`,
	  `is_content`
	)
	SELECT 
	  pi.id,
	  0,
	  'show_labels',
	  1,
	  IF (pi.content_id, 'version_controlled_setting', 'synchronized_setting')
	FROM `[[DB_PREFIX]]plugin_instances` AS pi
	WHERE pi.framework = 'show_label'
	  AND pi.module_id IN (
			SELECT m.id
			FROM `[[DB_PREFIX]]modules` AS m
			WHERE m.class_name = 'zenario_meta_data'
		)
	ORDER BY pi.id
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_instances` AS pi
	SET pi.framework = 'standard'
	WHERE pi.framework = 'show_label'
	  AND pi.module_id IN (
			SELECT m.id
			FROM `[[DB_PREFIX]]modules` AS m
			WHERE m.class_name = 'zenario_meta_data'
		)
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `egg_id`,
	  `name`,
	  `value`,
	  `is_content`
	)
	SELECT 
	  pi.id,
	  np.id,
	  'show_labels',
	  1,
	  IF (pi.content_id, 'version_controlled_setting', 'synchronized_setting')
	FROM `[[DB_PREFIX]]nested_plugins` AS np
	INNER JOIN `[[DB_PREFIX]]plugin_instances` AS pi
	   ON pi.id = np.instance_id
	WHERE np.is_slide = 0
	  AND np.framework = 'show_label'
	  AND np.module_id IN (
			SELECT m.id
			FROM `[[DB_PREFIX]]modules` AS m
			WHERE m.class_name = 'zenario_meta_data'
		)
	ORDER BY pi.id, np.id
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]nested_plugins` AS np
	SET np.framework = 'standard'
	WHERE np.framework = 'show_label'
	  AND np.module_id IN (
			SELECT m.id
			FROM `[[DB_PREFIX]]modules` AS m
			WHERE m.class_name = 'zenario_meta_data'
		)
_sql


//Split the edit_data_pool command into edit_data_pool (for list plugins) and edit_this_data_pool (for details plugins)
//so the conductor can tell the difference between the hierarchy levels of the two.
);	ze\dbAdm::revision( 44930
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings` AS ee
	INNER JOIN `[[DB_PREFIX]]plugin_settings` AS mode
	   ON mode.instance_id = ee.instance_id
	  AND mode.egg_id = ee.egg_id
	  AND mode.name = 'mode'
	  AND mode.value IN ('view_data_pool_details', 'view_data_pool_name', 'view_data_pool_block')
	SET ee.name = 'enable.edit_this_data_pool'
	WHERE ee.name = 'enable.edit_data_pool'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]nested_paths` AS path
	INNER JOIN `[[DB_PREFIX]]nested_plugins` AS slide
	   ON slide.instance_id  = path.instance_id
	  AND slide.is_slide = 1
	  AND FIND_IN_SET(path.from_state, slide.states)
	INNER JOIN `[[DB_PREFIX]]nested_plugins` AS plugin
	   ON plugin.instance_id  = slide.instance_id
	  AND plugin.is_slide = 0
	  AND plugin.slide_num = slide.slide_num
	INNER JOIN `[[DB_PREFIX]]plugin_settings` AS mode
	   ON mode.instance_id = plugin.instance_id
	  AND mode.egg_id = plugin.id
	  AND mode.name = 'mode'
	  AND mode.value IN ('view_data_pool_details', 'view_data_pool_name', 'view_data_pool_block')
	SET path.command = 'edit_this_data_pool'
	WHERE path.command = 'edit_data_pool'
_sql


//Combine two frameworks for the zenario_search_entry_box plugin nest into one, and replace
//the choice with a plugin setting
);	ze\dbAdm::revision( 45020
, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `egg_id`,
	  `name`,
	  `value`,
	  `is_content`
	)
	SELECT 
	  pi.id,
	  0,
	  'search_label',
	  1,
	  IF (pi.content_id, 'version_controlled_setting', 'synchronized_setting')
	FROM `[[DB_PREFIX]]plugin_instances` AS pi
	WHERE pi.framework = 'search_label'
	  AND pi.module_id IN (
			SELECT m.id
			FROM `[[DB_PREFIX]]modules` AS m
			WHERE m.class_name = 'zenario_search_entry_box'
		)
	ORDER BY pi.id
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_instances` AS pi
	SET pi.framework = 'standard'
	WHERE pi.framework = 'search_label'
	  AND pi.module_id IN (
			SELECT m.id
			FROM `[[DB_PREFIX]]modules` AS m
			WHERE m.class_name = 'zenario_search_entry_box'
		)
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `egg_id`,
	  `name`,
	  `value`,
	  `is_content`
	)
	SELECT 
	  pi.id,
	  np.id,
	  'search_label',
	  1,
	  IF (pi.content_id, 'version_controlled_setting', 'synchronized_setting')
	FROM `[[DB_PREFIX]]nested_plugins` AS np
	INNER JOIN `[[DB_PREFIX]]plugin_instances` AS pi
	   ON pi.id = np.instance_id
	WHERE np.is_slide = 0
	  AND np.framework = 'search_label'
	  AND np.module_id IN (
			SELECT m.id
			FROM `[[DB_PREFIX]]modules` AS m
			WHERE m.class_name = 'zenario_search_entry_box'
		)
	ORDER BY pi.id, np.id
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]nested_plugins` AS np
	SET np.framework = 'standard'
	WHERE np.framework = 'search_label'
	  AND np.module_id IN (
			SELECT m.id
			FROM `[[DB_PREFIX]]modules` AS m
			WHERE m.class_name = 'zenario_search_entry_box'
		)
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `egg_id`,
	  `name`,
	  `value`,
	  `is_content`
	)
	SELECT 
	  pi.id,
	  0,
	  'search_label',
	  1,
	  IF (pi.content_id, 'version_controlled_setting', 'synchronized_setting')
	FROM `[[DB_PREFIX]]plugin_instances` AS pi
	WHERE pi.framework = 'search_label'
	  AND pi.module_id IN (
			SELECT m.id
			FROM `[[DB_PREFIX]]modules` AS m
			WHERE m.class_name = 'zenario_search_entry_box_predictive_probusiness'
		)
	ORDER BY pi.id
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_instances` AS pi
	SET pi.framework = 'standard'
	WHERE pi.framework = 'search_label'
	  AND pi.module_id IN (
			SELECT m.id
			FROM `[[DB_PREFIX]]modules` AS m
			WHERE m.class_name = 'zenario_search_entry_box_predictive_probusiness'
		)
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `egg_id`,
	  `name`,
	  `value`,
	  `is_content`
	)
	SELECT 
	  pi.id,
	  np.id,
	  'search_label',
	  1,
	  IF (pi.content_id, 'version_controlled_setting', 'synchronized_setting')
	FROM `[[DB_PREFIX]]nested_plugins` AS np
	INNER JOIN `[[DB_PREFIX]]plugin_instances` AS pi
	   ON pi.id = np.instance_id
	WHERE np.is_slide = 0
	  AND np.framework = 'search_label'
	  AND np.module_id IN (
			SELECT m.id
			FROM `[[DB_PREFIX]]modules` AS m
			WHERE m.class_name = 'zenario_search_entry_box_predictive_probusiness'
		)
	ORDER BY pi.id, np.id
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]nested_plugins` AS np
	SET np.framework = 'standard'
	WHERE np.framework = 'search_label'
	  AND np.module_id IN (
			SELECT m.id
			FROM `[[DB_PREFIX]]modules` AS m
			WHERE m.class_name = 'zenario_search_entry_box_predictive_probusiness'
		)
_sql


//Create a table to record consents from users/visitors to process their data.
);	ze\dbAdm::revision( 45061
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]consents`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]]consents` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`datetime` datetime,
		`user_id` int(10) unsigned NOT NULL DEFAULT 0,
		`ip_address` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
		`email` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
		`first_name` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
		`last_name` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
		PRIMARY KEY (`id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8 
_sql

//Change default email template "from" address and name to use site_settings rather than custom
);	ze\dbAdm::revision( 45064
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates`
	MODIFY COLUMN `from_details` enum('site_settings','custom_details') NOT NULL DEFAULT 'site_settings'
_sql

//Enable "show venue name" (show_location_name) setting in Zenario Event Listing if "show_location" was enabled
);	ze\dbAdm::revision( 45190
, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `egg_id`,
	  `name`,
	  `value`,
	  `is_content`
	)
	SELECT
	  `instance_id`,
	  `egg_id`,
	  'show_location_name',
	  `value`,
	  `is_content`
	FROM `[[DB_PREFIX]]plugin_settings` AS ps
	WHERE ps.name = "show_location"
	ORDER BY instance_id, egg_id
_sql

);	ze\dbAdm::revision( 45193
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]documents`
	MODIFY COLUMN `privacy` enum('auto','public','private', 'offline') NOT NULL DEFAULT 'offline'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]documents`
	SET privacy = 'offline'
	WHERE privacy = 'auto'
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]documents`
	MODIFY COLUMN `privacy` enum('public','private', 'offline') NOT NULL DEFAULT 'offline'
_sql




//Migrate all mergefields in slide titles to the new format in 8.2
);	ze\dbAdm::revision( 45300
, <<<_sql
	UPDATE `[[DB_PREFIX]]nested_plugins`
	SET name_or_title = 
		REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
		REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
		REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
		REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
			name_or_title,
			'[[asset_id]]', '[[assetId]]'),
			'[[asset_id1]]', '[[assetId1]]'),
			'[[asset_id2]]', '[[assetId2]]'),
			'[[name]]', '[[assetwolf_2:name]]'),
			'[[asset_name]]', '[[assetwolf_2:name]]'),
			'[[asset_type_name]]', '[[assetwolf_2:asset_schema_name]]'),
			'[[asset_schema_name]]', '[[assetwolf_2:asset_schema_name]]'),
			'[[data_pool_id]]', '[[dataPoolId]]'),
			'[[data_pool_id1]]', '[[dataPoolId1]]'),
			'[[data_pool_id2]]', '[[dataPoolId2]]'),
			'[[data_pool_name]]', '[[assetwolf_2:data_pool_name]]'),
			'[[data_pool_name1]]', '[[assetwolf_2:data_pool_name1]]'),
			'[[data_pool_name2]]', '[[assetwolf_2:data_pool_name2]]'),
			'[[data_pool_name3]]', '[[assetwolf_2:data_pool_name3]]'),
			'[[data_pool_name4]]', '[[assetwolf_2:data_pool_name4]]'),
			'[[data_pool_name5]]', '[[assetwolf_2:data_pool_name5]]'),
			'[[data_pool_schema_name]]', '[[assetwolf_2:data_pool_schema_name]]'),
			'[[data_repeater_source_name]]', '[[assetwolf_2:data_repeater_source_name]]'),
			'[[data_repeater_target_name]]', '[[assetwolf_2:data_repeater_target_name]]'),
			'[[procedure_name]]', '[[assetwolf_2:procedure_name]]'),
			'[[schedule_name]]', '[[assetwolf_2:schedule_name]]'),
			
			'[[abstract_name]]', '[[zenario_conference_manager:abstract_name]]'),
			'[[conference_name]]', '[[zenario_conference_manager:name]]'),
			'[[day_name]]', '[[zenario_conference_manager:day_name]]'),
			'[[room_name]]', '[[zenario_conference_manager:room_name]]'),
			'[[seminar_name]]', '[[zenario_conference_manager:seminar_name]]'),
			'[[session_name]]', '[[zenario_conference_manager:session_name]]'),
			
			'[[company_name]]', '[[zenario_company_locations_manager:name]]'),
			'[[location_name]]', '[[zenario_location_manager:name]]'),
			'[[user_first_and_last_name]]', '[[zenario_users:name]]'),

			'[[class]]', '[[zenario_api_documenter_fea:class]]'),
			'[[method]]', '[[zenario_api_documenter_fea:method]]')
	WHERE is_slide = 1
	  AND name_or_title LIKE '%[[%]]%'
_sql

);	ze\dbAdm::revision( 45302
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]consents`
	ADD COLUMN `label` varchar(250) CHARACTER SET utf8mb4 DEFAULT ''
_sql

//Fix a bug when picking a User Form in the plugin settings, where the form was not
//correctly linked via a foreign key
);	ze\dbAdm::revision( 45380
, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings` SET
		foreign_key_to = 'user_form',
		foreign_key_id = `value`
	WHERE name = 'user_form'
	  AND `value` = 1 * `value`
_sql

//Add columns to help us more easily tell the difference between plugins/nests/slideshows when
//counting/tracking things in Organizer.
);	ze\dbAdm::revision( 45400
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_instances`
	ADD COLUMN `is_nest` tinyint(1) NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_instances`
	ADD COLUMN `is_slideshow` tinyint(1) NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]inline_images`
	ADD COLUMN `is_nest` tinyint(1) NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]inline_images`
	ADD COLUMN `is_slideshow` tinyint(1) NOT NULL default 0
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]modules` AS m
	INNER JOIN `[[DB_PREFIX]]plugin_instances` AS pi
	   ON m.id = pi.module_id
	SET is_nest = 1
	WHERE m.class_name = 'zenario_plugin_nest'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]modules` AS m
	INNER JOIN `[[DB_PREFIX]]plugin_instances` AS pi
	   ON m.id = pi.module_id
	SET pi.is_nest = 1,
		pi.is_slideshow = 1
	WHERE m.class_name = 'zenario_slideshow'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_instances` AS pi
	INNER JOIN `[[DB_PREFIX]]inline_images` AS ii
	   ON ii.foreign_key_to = 'library_plugin'
	  AND ii.foreign_key_id = pi.id
	SET ii.is_nest = 1
	WHERE pi.is_nest = 1
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_instances` AS pi
	INNER JOIN `[[DB_PREFIX]]inline_images` AS ii
	   ON ii.foreign_key_to = 'library_plugin'
	  AND ii.foreign_key_id = pi.id
	SET ii.is_slideshow = 1
	WHERE pi.is_slideshow = 1
_sql

);	ze\dbAdm::revision( 45401
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_instances`
	ADD KEY (`is_nest`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_instances`
	ADD KEY (`is_slideshow`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]inline_images`
	ADD KEY (`is_nest`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]inline_images`
	ADD KEY (`is_slideshow`)
_sql

//Drop some of the thumbnail sizes from the files table
);	ze\dbAdm::revision( 46050
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	DROP COLUMN `thumbnail_64x64_width`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	DROP COLUMN `thumbnail_64x64_height`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	DROP COLUMN `thumbnail_64x64_data`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	DROP COLUMN `thumbnail_24x23_width`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	DROP COLUMN `thumbnail_24x23_height`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	DROP COLUMN `thumbnail_24x23_data`
_sql

//Add some columns to the consents table to record the source of the consent (e.g. form or extranet registration plugin)
);	ze\dbAdm::revision( 46051
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]consents`
	ADD COLUMN `source_name` varchar(255) NOT NULL DEFAULT '' AFTER `id`,
	ADD COLUMN `source_id` varchar(255) NOT NULL DEFAULT '' AFTER `source_name`
_sql


//Fix a mistake where a couple of tables had the wrong character-set chosen by mistake.
);	ze\dbAdm::revision( 46200
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]lov_salutations`
	CONVERT TO CHARACTER SET utf8
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]user_perm_settings`
	CONVERT TO CHARACTER SET utf8
_sql

//Add the hierarchical_var column to the nested_plugins table,
//as an efficiency improvement to save more queries to look this up.
);	ze\dbAdm::revision(46250
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	ADD COLUMN `hierarchical_var` varchar(32) CHARACTER SET ascii NOT NULL DEFAULT ''
	AFTER `request_vars`
_sql

//Rename and merge another case where there were two different plugin settings for the same thing
);  ze\dbAdm::revision(46300
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings`
	SET name = 'enable.metadata'
	WHERE name = 'assetwolf__view_data_pool_details__show_meta_data'
_sql


//Add a new option for special pages, that allows them to be hidden
);  ze\dbAdm::revision(46501
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]special_pages`
	ADD COLUMN `allow_hide` tinyint(1) NOT NULL default 0
	AFTER `logic`
_sql


//Rename "scheduled calculations" to "metrics" in the scheduled tasks table
);  ze\dbAdm::revision(46615
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]jobs`
	SET job_name = 'jobCalculateMetrics'
	WHERE job_name = 'jobRunScheduledCalculator'
_sql

//Enable "Show a heading" (show_a_heading) setting in Zenario Document Container if "Show selected folder name as title" (show_folder_name_as_title) was enabled
);	ze\dbAdm::revision( 46616
, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `egg_id`,
	  `name`,
	  `value`,
	  `is_content`
	)
	SELECT
	  `instance_id`,
	  `egg_id`,
	  'show_a_heading',
	  `value`,
	  `is_content`
	FROM `[[DB_PREFIX]]plugin_settings` AS ps
	WHERE ps.name = "show_folder_name_as_title"
	ORDER BY instance_id, egg_id
_sql

//Some Banner module settings were changed. Adjust these:
);	ze\dbAdm::revision( 46618
//Set the Advanced Behaviour select list to "Show as a background image" if it was selected before (used to be a checkbox).
, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `egg_id`,
	  `name`,
	  `value`,
	  `is_content`
	)
	SELECT
	  `instance_id`,
	  `egg_id`,
	  'advanced_behaviour',
	  'background_image',
	  `is_content`
	FROM `[[DB_PREFIX]]plugin_settings` AS ps
	WHERE ps.name = 'background_image'
		AND value = 1
	ORDER BY instance_id, egg_id
_sql

//Set the Advanced Behaviour select list to "Use a rollover image" if it was selected before (used to be a checkbox).
, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `egg_id`,
	  `name`,
	  `value`,
	  `is_content`
	)
	SELECT
	  `instance_id`,
	  `egg_id`,
	  'advanced_behaviour',
	  'use_rollover',
	  `is_content`
	FROM `[[DB_PREFIX]]plugin_settings` AS ps
	WHERE ps.name = 'use_rollover'
		AND value = 1
	ORDER BY instance_id, egg_id
_sql

//Set the Advanced Behaviour select list to "Hide image" if it was selected before (used to be a checkbox on a separate tab, "Mobile image").
, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `egg_id`,
	  `name`,
	  `value`,
	  `is_content`
	)
	SELECT
	  `instance_id`,
	  `egg_id`,
	  'advanced_behaviour',
	  'mobile_hide_image',
	  `is_content`
	FROM `[[DB_PREFIX]]plugin_settings` AS ps
	WHERE ps.name = 'mobile_behavior'
		AND value = "hide_image"
	ORDER BY instance_id, egg_id
_sql

//Set the Advanced Behaviour select list to "Change image" if it was selected before (used to be a checkbox on a separate tab, "Mobile image").
, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]plugin_settings` (
	  `instance_id`,
	  `egg_id`,
	  `name`,
	  `value`,
	  `is_content`
	)
	SELECT
	  `instance_id`,
	  `egg_id`,
	  'advanced_behaviour',
	  'mobile_change_image',
	  `is_content`
	FROM `[[DB_PREFIX]]plugin_settings` AS ps
	WHERE ps.name = "mobile_behavior"
		AND value = "change_image"
	ORDER BY instance_id, egg_id
_sql


//Add a table for slide layouts
);  ze\dbAdm::revision(46700
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]slide_layouts`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]]slide_layouts` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`layout_for` enum('schema') NOT NULL,
		`layout_for_id` int(10) unsigned NOT NULL,
		`ord` smallint(4) unsigned NOT NULL default 1,
		`name` varchar(250) CHARACTER SET utf8mb4 NOT NULL,
		`privacy` enum('public', 'logged_out', 'logged_in', 'group_members', 'in_smart_group', 'logged_in_not_in_smart_group', 'with_role') NOT NULL default 'public',
		`smart_group_id` int(10) unsigned NOT NULL default 0,
		`data` mediumtext CHARACTER SET utf8mb4,
		`created` datetime NOT NULL,
		`last_edited` datetime default NULL,
		`last_edited_admin_id` int(10) unsigned NOT NULL default 0,
		`last_edited_user_id` int(10) unsigned NOT NULL default 0,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`layout_for`, `layout_for_id`, `ord`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] default CHARSET=utf8 
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]group_link`
	MODIFY COLUMN `link_from` enum('chain', 'slide', 'slide_layout') NOT NULL
_sql


//Remove the smart group options from slide layouts
);  ze\dbAdm::revision(46740
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]slide_layouts`
	DROP COLUMN `smart_group_id`
_sql

//Add a column to the nested plugins table for selecting a slide layout for a slide.
//Currently there are only two options: asset schema and datapool schema
);	ze\dbAdm::revision(46800
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	ADD COLUMN `use_slide_layout` enum('', 'asset_schema', 'datapool_schema') NOT NULL default ''
	AFTER `is_slide`
_sql

//Add a "hidden" privacy option for slides
); ze\dbAdm::revision(46802
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	MODIFY COLUMN `privacy` enum('public','logged_out','logged_in','group_members','in_smart_group','logged_in_not_in_smart_group','call_static_method','with_role','hidden') NOT NULL DEFAULT 'public'
_sql


//(N.b. this was added in an after-branch patch in 8.3 revision 46306, so we need to check if it's not already there.)
);	if (ze\dbAdm::needRevision(46803) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'menu_nodes LIKE "custom_get_requests"')) ze\dbAdm::revision(46803
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]menu_nodes`
	ADD COLUMN `custom_get_requests` varchar(255) DEFAULT NULL
_sql

//(N.b. this was added in an after-branch patch in 8.3 revision 46307, so we need to check if it's not already there.)
);	if (ze\dbAdm::needRevision(47035) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'categories LIKE "code_name"')) ze\dbAdm::revision(47035
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]categories`
	ADD COLUMN `code_name` varchar(255) CHARACTER SET ascii DEFAULT NULL AFTER `name`,
	ADD UNIQUE KEY(`code_name`)
_sql

//Enable categories for Event content types
); ze\dbAdm::revision(47251
,<<<_sql
	UPDATE `[[DB_PREFIX]]content_types`
	SET enable_categories = 1
	WHERE content_type_name_en = "Event"
_sql

//Rename a plugin setting
);  ze\dbAdm::revision(46300
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings`
	SET name = 'show_keys'
	WHERE name = 'data_pool_custom_column_keys'
_sql

//Add a "must be public/must be private" option for plugins
);	ze\dbAdm::revision(47800
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]modules`
	ADD COLUMN `must_be_on` enum('', 'public_page', 'private_page') NOT NULL default ''
	AFTER `is_pluggable`
_sql


//Fix a bug where it was possible to put invalid characters in a filename when renaming it.
//Adding a DB query to sanitise anywhere it's previously happened.
);	ze\dbAdm::revision(47803
, <<<_sql
	UPDATE `[[DB_PREFIX]]files`
	SET filename =
		REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
		REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
			filename
		, '\\\\', ''), '/', ''), ':', ''), ';', ''), '*', ''),
		'?', ''), '"', ''), '<', ''), '>', ''), '|', '')
_sql


//For anyone using the Black Dog skin, attempt to update the logic for the CSS animations to use the new library.
//Note: This patch was from 8.6, but it's safe to re-run so there's no harm in back-patching it.
);	ze\dbAdm::revision(47804
, <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET value = REPLACE(value, "<script type='text/javascript' src='zenario_custom/templates/grid_templates/skins/blackdog/js/animation_load.js'></script>", '')
	WHERE name = 'sitewide_foot'
	  AND value IS NOT NULL
	  AND value != ''
_sql
	
, <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET value = REPLACE(value, "<script type='text/javascript' src='zenario_custom/templates/grid_templates/skins/blackdog/js/css3-animate-it.js'></script>", '')
	WHERE name = 'sitewide_foot'
	  AND value IS NOT NULL
	  AND value != ''
_sql
	
, <<<_sql
	UPDATE `[[DB_PREFIX]]layouts`
	SET head_html = CONCAT(IFNULL(head_html, ''), '\n<link rel="stylesheet" href="zenario/libs/yarn/animate.css/animate.min.css"/>')
	WHERE family_name = 'grid_templates'
	  AND file_base_name = 'L02'
	  AND (head_html IS NULL OR head_html NOT LIKE '%animate.min.css%')
_sql
	
, <<<_sql
	UPDATE `[[DB_PREFIX]]layouts`
	SET foot_html = CONCAT(IFNULL(foot_html, ''), '\n<script type="text/javascript" src="zenario/libs/yarn/wowjs/dist/wow.min.js"></script>\n<script type="text/javascript" src="zenario_custom/templates/grid_templates/skins/blackdog/js/animation_load.js"></script>')
	WHERE family_name = 'grid_templates'
	  AND file_base_name = 'L02'
	  AND (foot_html IS NULL OR foot_html NOT LIKE '%wow.min.js%')
_sql

);
