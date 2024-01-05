<?php
/*
 * Copyright (c) 2024, Tribal Limited
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




//
//	Zenario 8.9
//


//Add a "Only show the Back button when the previous slide has more than one item to choose from" flag for slides
	ze\dbAdm::revision( 52150
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	ADD COLUMN `no_choice_no_going_back` tinyint(1) NOT NULL default 0
	AFTER `show_back`
_sql

//As of 21 Aug 2020 (HEAD, backpatched to 8.7 and 8.8), deleting a content item will wipe its alias.
//This logic wipes the aliases of already deleted content items.
//These are safe to re-apply more than once.
);	ze\dbAdm::revision( 52201
, <<<_sql
	UPDATE `[[DB_PREFIX]]content_items`
	SET alias = ''
	WHERE status = "deleted" AND alias <> ''
_sql


//Update the files table to fix some slow queries.
//In HEAD/8/9, I'm adding keys to the thumbnail width/height columns.
//In all versions, I'm also fixing an inconsistency where we sometimes store missing dimensions as a 0,
//and sometimes as a null, by now always using null.
);	ze\dbAdm::revision( 52500
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	ADD KEY (`custom_thumbnail_1_width`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	ADD KEY (`custom_thumbnail_1_height`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	ADD KEY (`custom_thumbnail_2_width`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	ADD KEY (`custom_thumbnail_2_height`)
_sql


, <<<_sql
	UPDATE `[[DB_PREFIX]]files`
	SET custom_thumbnail_1_width = NULL
	WHERE custom_thumbnail_1_width = 0
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]files`
	SET custom_thumbnail_1_height = NULL
	WHERE custom_thumbnail_1_height = 0
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]files`
	SET custom_thumbnail_2_width = NULL
	WHERE custom_thumbnail_2_width = 0
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]files`
	SET custom_thumbnail_2_height = NULL
	WHERE custom_thumbnail_2_height = 0
_sql


//
//	Zenario 9.0
//


//Changes for layouts in 9.0
); 	ze\dbAdm::revision( 52526
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]layouts` 
	WHERE family_name != "grid_templates"
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layouts` 
	DROP COLUMN `family_name`
_sql

, <<<_sql
	DELETE FROM `[[DB_PREFIX]]skins` 
	WHERE family_name != "grid_templates"
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]skins` 
	DROP COLUMN `family_name`
_sql

, <<<_sql
	DELETE FROM `[[DB_PREFIX]]plugin_layout_link` 
	WHERE family_name != "grid_templates"
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_layout_link` 
	DROP COLUMN `family_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layouts` 
	ADD COLUMN `json_data` JSON
	AFTER `bg_repeat` 
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]template_slot_link`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]template_files`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]template_families`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]layout_slot_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]]layout_slot_link` (
	`layout_id` int(10) unsigned NOT NULL,
	`slot_name` varchar(100) NOT NULL,
	`ord` smallint(4) unsigned NOT NULL default 0,
	`cols` tinyint(2) unsigned NOT NULL default 0,
	`small_screens` enum('show','hide','only','first') DEFAULT 'show',
	PRIMARY KEY (`layout_id`,`slot_name`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql



//Add a column to save a layout's hash, so we don't have to keep generating it each time we want to check it
);	ze\dbAdm::revision( 53100
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layouts` 
	ADD COLUMN `json_data_hash`  varchar(8) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL default 'xxxxxxxx'
	AFTER `json_data` 
_sql


//Add an index to the nested_plugins table
);	ze\dbAdm::revision( 53600
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	ADD KEY (`module_id`)
_sql


//
//	Zenario 9.1
//


//Add support for menu nodes to allo linking to documents.
//Please note: the revision number was changed while 9.1 was HEAD,
//so this update will not run if the column already exists.
);	if (ze\dbAdm::needRevision(53700) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'menu_nodes LIKE "document_id"')) ze\dbAdm::revision(53700
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]menu_nodes`
	CHANGE COLUMN `target_loc` `target_loc` enum('int', 'doc', 'ext', 'none') NOT NULL DEFAULT 'none',
	ADD COLUMN `document_id` int(10) unsigned NOT NULL default 0 AFTER `use_download_page`,
	ADD KEY `document_id` (`document_id`)
_sql


//Correct any bad data left over from a bug where it was possible to make an image tag
//name with upper case characters.
//Note that this was also added in a post-branch patch, but is safe to repeat.
);	ze\dbAdm::revision( 53730
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]image_tags`
	SET `name` = LOWER(`name`)
_sql

//Content can be pinned now.
//Please note: as this feature was backpatched to 9.0, there is a check
//to see if the update was already applied.
);	if (ze\dbAdm::needRevision(53731)
		&& !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'content_types LIKE "allow_pinned_content"')
		&& !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'content_item_versions LIKE "pinned"'))	ze\dbAdm::revision(53731
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_types`
	ADD COLUMN `allow_pinned_content` tinyint(1) NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	ADD COLUMN `pinned` tinyint(1) NOT NULL default 0
_sql


//Add a new column to the content_item_versions table, which allows us to cache the result
//of the ze\contentAdm::checkIfVersionChanged() function.
);	ze\dbAdm::revision( 53800
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	ADD COLUMN `version_changed` enum('not_checked', 'no_changes_made', 'changes_made') DEFAULT 'not_checked'
	AFTER `last_modified_datetime`
_sql


//As of Zenario 9.1, the page preview sizes feature has been completely removed.
//Please note: this was backpatched to 9.0. However, this code should be safe to use more than once.
);	ze\dbAdm::revision( 53901
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]page_preview_sizes`
_sql

); ze\dbAdm::revision( 53902,
<<<_sql
	ALTER TABLE [[DB_PREFIX]]content_item_versions
	ADD COLUMN `apply_noindex_meta_tag` tinyint(1) NOT NULL default 0 AFTER `in_sitemap`
_sql



//Remove the old "slide designer" feature.
//This was never fully finished, has now been replaced by something much more simple, and we no longer want to maintain it
);  ze\dbAdm::revision(54400
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]slide_layouts`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	DROP COLUMN `use_slide_layout`
_sql

, <<<_sql
	DELETE FROM `[[DB_PREFIX]]group_link`
	WHERE `link_from` = 'slide_layout'
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]group_link`
	MODIFY COLUMN `link_from` enum('chain', 'slide') NOT NULL
_sql

);  ze\dbAdm::revision(54401
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_types`
	ADD COLUMN `when_creating_put_title_in_body` tinyint(1) NOT NULL default 0
_sql

);  ze\dbAdm::revision(54402
, <<<_sql
	UPDATE `[[DB_PREFIX]]content_types`
	SET `when_creating_put_title_in_body` = 1
	WHERE content_type_id = 'html'
_sql

);  ze\dbAdm::revision(54403
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]writer_profiles`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]]writer_profiles` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`admin_id` int(10) unsigned default NULL,
		`first_name` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL,
		`last_name` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL,
		`type` enum('administrator', 'external_writer') default NULL,
		`email` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL,
		`photo` int(10) unsigned DEFAULT NULL,
		`profile` blob,
		`created` datetime DEFAULT NULL,
		`created_admin_id` int(10) unsigned DEFAULT NULL,
		`last_edited` datetime DEFAULT NULL,
		`last_edited_admin_id` int(10) unsigned DEFAULT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`admin_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]] 
_sql

//Feature implemented in 9.2 and backpatched to 9.1. Check before attempting to add the column twice.
);	if (ze\dbAdm::needRevision(54601) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'special_pages LIKE "allow_search"')) ze\dbAdm::revision(54601

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]special_pages`
	ADD COLUMN `allow_search` tinyint(1) NOT NULL default 0
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]special_pages`
	SET allow_search = 1
	WHERE page_type = "zenario_privacy_policy"
_sql

);  ze\dbAdm::revision(54650
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_types`
	ADD COLUMN `auto_set_release_date` tinyint(1) NOT NULL default 0
_sql

, <<<_sql
	DELETE FROM `[[DB_PREFIX]]site_settings`
	WHERE name IN("create_draft_warning", "lock_item_upon_draft_creation", "auto_set_release_date")
_sql

);  ze\dbAdm::revision(54710
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	CHANGE COLUMN `width` `width` smallint(5) unsigned DEFAULT NULL,
	CHANGE COLUMN `height` `height` smallint(5) unsigned DEFAULT NULL
_sql


//Add a table to store crop-settings for images
);  ze\dbAdm::revision(54790
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]cropped_images`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]]cropped_images` (
		`image_id` int(10) unsigned NOT NULL,
		`aspect_ratio_width` mediumint(6) NOT NULL,
		`aspect_ratio_height` mediumint(6) NOT NULL,
		`aspect_ratio_angle` float NOT NULL,
		`ui_crop_x` mediumint(6) NOT NULL,
		`ui_crop_y` mediumint(6) NOT NULL,
		`ui_crop_width` mediumint(6) NOT NULL,
		`ui_crop_height` mediumint(6) NOT NULL,
		`ui_image_width` mediumint(6) NOT NULL,
		`ui_image_height` mediumint(6) NOT NULL,
		`crop_x` mediumint(6) NOT NULL,
		`crop_y` mediumint(6) NOT NULL,
		`crop_width` mediumint(6) NOT NULL,
		`crop_height` mediumint(6) NOT NULL,
		PRIMARY KEY (`image_id`, `aspect_ratio_width`, `aspect_ratio_height`),
		KEY (`image_id`, `aspect_ratio_angle`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]] 
_sql


//Remove any "HTML" files from the allowed file types table
);	ze\dbAdm::revision( 55000
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]document_types`
	WHERE `type` IN ('htm', 'html', 'htt', 'mhtml', 'stm', 'xhtml')
_sql


//Remove any excessively large crop ratio numbers from the cropped_images table.
//There will also be a UI change to prevent these from being used.
);  ze\dbAdm::revision(55050
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]cropped_images`
	WHERE aspect_ratio_width > 100
	   OR aspect_ratio_height > 100
_sql




//
//	Zenario 9.3
//

//In 9.3, we're going through and fixing the character-set on several columns that should
//have been using "ascii"
);	ze\dbAdm::revision(55140
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]centralised_lists`
	MODIFY COLUMN `module_class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	MODIFY COLUMN `tag_id` varchar(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_items`
	MODIFY COLUMN `language_id` varchar(15) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates`
	MODIFY COLUMN `module_class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]jobs`
	MODIFY COLUMN `manager_class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]jobs`
	MODIFY COLUMN `module_class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]languages`
	MODIFY COLUMN `detect_lang_codes` varchar(100) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]languages`
	MODIFY COLUMN `id` varchar(15) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layout_slot_link`
	MODIFY COLUMN `slot_name` varchar(100) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]menu_nodes`
	MODIFY COLUMN `module_class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]menu_text`
	MODIFY COLUMN `language_id` varchar(15) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'en'
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]module_dependencies`
	MODIFY COLUMN `module_class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]module_dependencies`
	MODIFY COLUMN `dependency_class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]modules`
	MODIFY COLUMN `class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]modules`
	MODIFY COLUMN `vlp_class` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	MODIFY COLUMN `module_class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_item_link`
	MODIFY COLUMN `slot_name` varchar(100) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_layout_link`
	MODIFY COLUMN `slot_name` varchar(100) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_pages_by_mode`
	MODIFY COLUMN `module_class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_setting_defs`
	MODIFY COLUMN `module_class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]signals`
	MODIFY COLUMN `module_class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]signals`
	MODIFY COLUMN `suppresses_module_class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]special_pages`
	MODIFY COLUMN `module_class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]translation_chain_privacy`
	MODIFY COLUMN `module_class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]visitor_phrases`
	MODIFY COLUMN `language_id` varchar(15) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]visitor_phrases`
	MODIFY COLUMN `module_class_name` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

);	ze\dbAdm::revision(55150
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates`
	MODIFY COLUMN `code` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

);  ze\dbAdm::revision(55154
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_types`
	ADD COLUMN `enable_css_tab` tinyint(1) NOT NULL default 0
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]content_types`
	SET enable_css_tab = 1
	WHERE content_type_id = 'html'
_sql

//Remove obsolete site settings
);  ze\dbAdm::revision(55155
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]site_settings`
	WHERE name IN ('cookie_consent_type__explicit', 'individual_cookie_consent', 'manage_cookie_consent_content_item')
_sql


//Update the "alias" column to use utf8mb4.
//However also cut the length down to 250 characters, just in case anyone is limited to 1,000 bytes in their key lengths.
//Please note: this was backpatched to 9.1 and 9.2. However, this code should be safe to use more than once.
);	ze\dbAdm::revision( 55600
, <<<_sql
	UPDATE `[[DB_PREFIX]]spare_aliases`
	SET `alias` = SUBSTRING(`alias`, 1, 250)
	WHERE LENGTH(`alias`) > 250
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]spare_aliases` MODIFY COLUMN `alias` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL
_sql

//Remove unused cookie phrases
);	ze\dbAdm::revision( 55602
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]visitor_phrases`
	WHERE code IN ("_COOKIE_CONSENT_IMPLIED_MESSAGE", "_COOKIE_CONSENT_CONTINUE", "_COOKIE_CONSENT_MESSAGE", "_COOKIE_CONSENT_MANAGE", "_COOKIE_CONSENT_ACCEPT", "_COOKIE_CONSENT_SAVE_PREFERENCES", "_COOKIE_CONSENT_REJECT")
_sql

//Bring cookie consent for layouts and content item versions
//in line with the HTML/Twig snippets.
);	ze\dbAdm::revision( 55603
//Migrate content item versions settings:
//Head cc...
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	MODIFY COLUMN `head_cc` enum('not_needed', 'needed', 'specific_types', 'required') NOT NULL DEFAULT 'not_needed'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]content_item_versions`
	SET head_cc = "needed"
	WHERE head_cc = "required"
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	MODIFY COLUMN `head_cc` enum('not_needed', 'needed', 'specific_types') NOT NULL DEFAULT 'not_needed'
_sql

//... and foot cc.
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	MODIFY COLUMN `foot_cc` enum('not_needed', 'needed', 'specific_types', 'required') NOT NULL DEFAULT 'not_needed'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]content_item_versions`
	SET foot_cc = "needed"
	WHERE foot_cc = "required"
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	MODIFY COLUMN `foot_cc` enum('not_needed', 'needed', 'specific_types') NOT NULL DEFAULT 'not_needed'
_sql

//Migrate layouts settings:
//Head cc...
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layouts`
	MODIFY COLUMN `head_cc` enum('not_needed', 'needed', 'specific_types', 'required') NOT NULL DEFAULT 'not_needed'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]layouts`
	SET head_cc = "needed"
	WHERE head_cc = "required"
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layouts`
	MODIFY COLUMN `head_cc` enum('not_needed', 'needed', 'specific_types') NOT NULL DEFAULT 'not_needed'
_sql

//... and foot cc.
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layouts`
	MODIFY COLUMN `foot_cc` enum('not_needed', 'needed', 'specific_types', 'required') NOT NULL DEFAULT 'not_needed'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]layouts`
	SET foot_cc = "needed"
	WHERE foot_cc = "required"
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layouts`
	MODIFY COLUMN `foot_cc` enum('not_needed', 'needed', 'specific_types') NOT NULL DEFAULT 'not_needed'
_sql

//Follow-up to revision 55603 above: add the specific_cookies column
);	ze\dbAdm::revision( 55604
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	ADD COLUMN `head_cc_specific_cookie_types` enum('functionality', 'analytics', 'social_media') DEFAULT NULL AFTER `head_cc`,
	ADD COLUMN `foot_cc_specific_cookie_types` enum('functionality', 'analytics', 'social_media') DEFAULT NULL AFTER `foot_cc`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layouts`
	ADD COLUMN `head_cc_specific_cookie_types` enum('functionality', 'analytics', 'social_media') DEFAULT NULL AFTER `head_cc`,
	ADD COLUMN `foot_cc_specific_cookie_types` enum('functionality', 'analytics', 'social_media') DEFAULT NULL AFTER `foot_cc`
_sql

//Remove an obsolete site setting
);	ze\dbAdm::revision( 55605
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]site_settings`
	WHERE name = "cookie_consent_for_extranet"
_sql

//Remove minified javascript from HTML and Twig snippets
);	ze\dbAdm::revision( 55606
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]plugin_settings`
	WHERE name = "minified_javascript"
_sql

//Add more control over how long a content item may be pinned
);	ze\dbAdm::revision( 55607
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	ADD COLUMN `pinned_duration` enum('fixed_date', 'fixed_duration', 'until_unpinned') DEFAULT NULL,
	ADD COLUMN `pinned_fixed_duration_value` tinyint(2) NOT NULL default 0,
	ADD COLUMN `pinned_fixed_duration_unit` enum('days', 'weeks') DEFAULT NULL,
	ADD COLUMN `unpin_date` datetime DEFAULT NULL
_sql

);	ze\dbAdm::revision( 55608
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]writer_profiles`
	DROP KEY `admin_id`,
	ADD KEY (`admin_id`)
_sql

//Rename one of the values in an enum in the meta data plugin
);	ze\dbAdm::revision( 55900
, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings`
	SET `value` = REPLACE(`value`, 'show_sticky_image', 'show_featured_image')
	WHERE `name` = 'reorder_fields'
_sql

);	ze\dbAdm::revision( 55901
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	ADD COLUMN `image_credit` varchar(255) NOT NULL DEFAULT ''
_sql

);	ze\dbAdm::revision( 55902
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]spare_aliases`
	MODIFY COLUMN `alias` varchar(255) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL
_sql

//Fix a bug where simple slideshow instances were not flagged as slideshows
);	ze\dbAdm::revision( 56250
, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_instances`
	SET is_slideshow = 1
	WHERE module_id IN (
		SELECT id
		FROM `[[DB_PREFIX]]modules`
		WHERE class_name = 'zenario_slideshow_simple'
	)
_sql

//Remove the Google Maps Geocode site setting
);	ze\dbAdm::revision( 56252
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]site_settings`
	WHERE name = "google_maps_geocode_api_key"
_sql

);	ze\dbAdm::revision( 56291
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]categories`
	ADD COLUMN `created` datetime DEFAULT NULL,
	ADD COLUMN `created_admin_id` int(10) unsigned DEFAULT NULL,
	ADD COLUMN `last_edited` datetime DEFAULT NULL,
	ADD COLUMN `last_edited_admin_id` int(10) unsigned DEFAULT NULL
_sql


//We're now refering to the labels for slides as "slide labels", and not "titles".
//I'm renaming a column to try and make that a little more clear
);	ze\dbAdm::revision( 56300
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	CHANGE COLUMN `name_or_title` `name_or_slide_label` varchar(250) COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL default ''
_sql


//Switch some plugins that previously used "resize and crop" mode to using
//"crop and zoom" mode.
//Note: Not all of the plugins have use "resize and crop" mode been converted and added to this list yet.
//More plugins may come soon!
);	ze\dbAdm::revision( 56350
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_PREFIX]]plugin_instances` AS pi
	   ON pi.id = ps.instance_id
	INNER JOIN `[[DB_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	SET ps.value = 'crop_and_zoom'
	WHERE ps.egg_id = 0
	  AND m.class_name IN (
	  	'zenario_banner',
	  	'zenario_content_list',
	  	'zenario_location_listing',
	  	'zenario_multiple_image_container',
	  	'zenario_plugin_nest',
	  	'zenario_slideshow',
	  	'zenario_slideshow_simple'
	  )
	  AND (m.class_name, ps.name) IN (
		('zenario_banner', 'canvas'),
		('zenario_banner', 'mobile_canvas'),
		('zenario_content_list', 'canvas'),
		('zenario_location_listing', 'canvas'),
		('zenario_multiple_image_container', 'canvas'),
		('zenario_plugin_nest', 'banner_canvas'),
		('zenario_plugin_nest', 'mobile_canvas'),
		('zenario_slideshow', 'banner_canvas'),
		('zenario_slideshow', 'mobile_canvas'),
		('zenario_slideshow_simple', 'banner_canvas'),
		('zenario_slideshow_simple', 'mobile_canvas')
	  )
	  AND ps.value = 'resize_and_crop'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_PREFIX]]nested_plugins` AS np
	   ON np.id = ps.egg_id
	INNER JOIN `[[DB_PREFIX]]modules` AS m
	   ON m.id = np.module_id
	SET ps.value = 'crop_and_zoom'
	WHERE ps.egg_id != 0
	  AND m.class_name IN (
	  	'zenario_banner',
	  	'zenario_content_list',
	  	'zenario_location_listing',
	  	'zenario_multiple_image_container'
	  )
	  AND (m.class_name, ps.name) IN (
		('zenario_banner', 'canvas'),
		('zenario_banner', 'mobile_canvas'),
		('zenario_content_list', 'canvas'),
		('zenario_location_listing', 'canvas'),
		('zenario_multiple_image_container', 'canvas')
	  )
	  AND ps.value = 'resize_and_crop'
_sql


//Remove the "Cache floating admin boxes" site setting
);	ze\dbAdm::revision( 56351
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]site_settings`
	WHERE name = "fab_use_cache_dir"
_sql


//
//	Zenario 9.4
//




//Admin-facing names for nested plugins are no longer stored in the database.
//The name_or_slide_label ne name_or_title column is now only used for storing slide labels.
//Rename it again to make this clear.
);	ze\dbAdm::revision( 56500
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	CHANGE COLUMN `name_or_slide_label` `slide_label` varchar(250) COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL default ''
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]nested_plugins`
	SET slide_label = ''
	WHERE is_slide = 0
_sql


//Add a table to store details on the standard head and foot for for layouts
);  ze\dbAdm::revision(56659
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]layout_head_and_foot`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]]layout_head_and_foot` (
		`for` enum('sitewide') NOT NULL,
		`cols` tinyint(2) unsigned NOT NULL DEFAULT 0,
		`min_width` smallint(4) unsigned NOT NULL default 0,
		`max_width` smallint(4) unsigned NOT NULL default 0,
		`fluid` tinyint(1) unsigned NOT NULL default 0,
		`responsive` tinyint(1) unsigned NOT NULL default 0,
		`head_json_data` json DEFAULT NULL,
		`foot_json_data` json DEFAULT NULL,
		PRIMARY KEY (`for`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]] 
_sql

//Also try to find the combination of settings that is most commonly used,
//and pre-populate it.
//Note: this will only work when migrating an existing site, not when doing a fresh install
//or site reset. There's another revision in step 4 that will handle this case.
, <<<_sql
	INSERT INTO [[DB_PREFIX]]layout_head_and_foot
	SELECT
		'sitewide',
		l.cols, l.min_width, l.max_width, l.fluid, l.responsive,
		null, null
	FROM (
		SELECT
			COUNT(DISTINCT c.tag_id) AS citems, 
			l.cols, l.min_width, l.max_width, l.fluid, l.responsive
		FROM [[DB_PREFIX]]content_items AS c
		INNER JOIN [[DB_PREFIX]]content_item_versions AS v
		   ON v.id = c.id
		  AND v.type = c.type
		  AND v.version = c.admin_version
		INNER JOIN [[DB_PREFIX]]layouts AS l
		   ON l.layout_id = v.layout_id
		GROUP BY l.cols, l.min_width, l.max_width, l.fluid, l.responsive
		ORDER BY 1 DESC
		LIMIT 1
	) AS l
_sql


//Add some more metadata to the layouts table, so we can easily see which layouts
//use the standard header and footer
);	ze\dbAdm::revision( 56660
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layouts`
	ADD COLUMN `header_and_footer` tinyint(1) unsigned NOT NULL default 0
	AFTER `responsive`
_sql


//Add some more metadata to the layout_slot_link table, so we can easily see which slots
//use the standard header and footer
);	ze\dbAdm::revision( 56750
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layout_slot_link`
	ADD COLUMN `is_header` tinyint(1) unsigned NOT NULL default 0
	AFTER `small_screens`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layout_slot_link`
	ADD COLUMN `is_footer` tinyint(1) unsigned NOT NULL default 0
	AFTER `is_header`
_sql


//Revert the above update, as the spec has now changed a bit!
	//(DB updates #56750 and #56779 could probably be removed completely in a future,
	// when our dev sites have had enough time to all get both updates.)
);	ze\dbAdm::revision( 56779
, <<<_sql
	DELETE pll.*
	FROM `[[DB_PREFIX]]layout_slot_link` AS lsl
	INNER JOIN `[[DB_PREFIX]]plugin_layout_link` AS pll
	   ON pll.layout_id = lsl.layout_id
	  AND pll.slot_name = lsl.slot_name
	WHERE (lsl.is_header = 1 OR lsl.is_footer = 1)
_sql

, <<<_sql
	DELETE pil.*
	FROM `[[DB_PREFIX]]layout_slot_link` AS lsl
	INNER JOIN `[[DB_PREFIX]]plugin_item_link` AS pil
	   ON pil.slot_name = lsl.slot_name
	WHERE (lsl.is_header = 1 OR lsl.is_footer = 1)
_sql


//Create a linking table to store which plugin instances have been placed in the header and footer
);	ze\dbAdm::revision( 56785
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]plugin_sitewide_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]]plugin_sitewide_link` (
		`module_id` int(10) unsigned NOT NULL,
		`instance_id` int(10) unsigned NOT NULL,
		`slot_name` varchar(100) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
		PRIMARY KEY (`slot_name`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]] 
_sql


//Fix some bad data where some nests/slideshows were not flagged as nests/slideshows in the database.
//(N.b. this was added in an after-branch patch in 9.2 revision 55053, and 9.3 revision 56353, but is safe to repeat.)
);	ze\dbAdm::revision( 57000
, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_instances` AS pi SET
		pi.is_nest = 0,
		pi.is_slideshow = 0
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_instances` AS pi SET
		pi.is_nest = 1,
		pi.is_slideshow = 0
	WHERE pi.module_id IN (
		SELECT m.id
		FROM `[[DB_PREFIX]]modules` AS m
		WHERE m.class_name in ('zenario_plugin_nest')
	)
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_instances` AS pi SET
		pi.is_nest = 0,
		pi.is_slideshow = 1
	WHERE pi.module_id IN (
		SELECT m.id
		FROM `[[DB_PREFIX]]modules` AS m
		WHERE m.class_name in ('zenario_slideshow', 'zenario_slideshow_simple')
	)
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]inline_images` AS ii
	INNER JOIN `[[DB_PREFIX]]plugin_instances` AS pi
	   ON pi.id = ii.foreign_key_id
	SET
		ii.is_nest = pi.is_nest,
		ii.is_slideshow = pi.is_slideshow
	WHERE ii.foreign_key_to = 'library_plugin'
_sql

//Remove the "invisible in nav" option for slides
);	ze\dbAdm::revision( 46050
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	DROP COLUMN `invisible_in_nav`
_sql

//Remove support for module-powered TUIX installation wizards.
);	ze\dbAdm::revision( 57210
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]tuix_file_contents`
	WHERE `type` = 'wizards'
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]tuix_file_contents`
	MODIFY COLUMN `type` enum('admin_boxes','admin_toolbar','help','organizer','slot_controls','visitor') NOT NULL
_sql

//Remove unused settings
);	ze\dbAdm::revision( 57212
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]site_settings`
	WHERE name IN ("max_content_image_filesize", "max_content_image_filesize_unit")
_sql

//Bugfixes: moved logic to add columns from:
//Email Template Manager, Common Features and Users.
//Also added checks to make sure the columns exist before attempting to add them again.
//Please note: this set of updates was backpatched from HEAD to 9.3.
//It was added to 9.3 as a post-branch fix.
);	if (ze\dbAdm::needRevision(57302) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'email_templates LIKE "include_a_fixed_attachment"')) ze\dbAdm::revision(57302
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]email_templates`
	ADD COLUMN `include_a_fixed_attachment` tinyint(1) NOT NULL default 0,
	ADD COLUMN `selected_attachment` int(10) unsigned default NULL,
	ADD COLUMN `allow_visitor_uploaded_attachments` tinyint(1) NOT NULL default 0,
	ADD COLUMN `when_sending_attachments` enum('send_organizer_link', 'send_actual_file') DEFAULT 'send_organizer_link'
_sql

);	if (ze\dbAdm::needRevision(57303) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'content_item_versions LIKE "sensitive_content_message"')) ze\dbAdm::revision(57303
, <<<_sql
	ALTER TABLE [[DB_PREFIX]]content_item_versions
	ADD COLUMN `sensitive_content_message` tinyint(1) NOT NULL default 0
_sql

);	if (ze\dbAdm::needRevision(57304) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'layouts LIKE "sensitive_content_message"')) ze\dbAdm::revision(57304
, <<<_sql
	ALTER TABLE [[DB_PREFIX]]layouts
	ADD COLUMN `sensitive_content_message` tinyint(1) NOT NULL default 0
_sql

);	if (ze\dbAdm::needRevision(57305) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'users LIKE "consent_hash"')) ze\dbAdm::revision(57305
, <<<_sql
	ALTER TABLE [[DB_PREFIX]]users 
	ADD COLUMN `consent_hash` varchar(28) NULL
_sql

//In addition to the previous comment, this update was in Zenario User Consent Forms.
//A core table column should not have different sizes depending on what module is or isn't running,
//so this will be standardised.
);	ze\dbAdm::revision( 57306
, <<<_sql
	ALTER TABLE [[DB_PREFIX]]users 
	MODIFY COLUMN `consent_hash` varchar(35) NULL
_sql

//Add a setting to control whether conductor should change the page title
);	ze\dbAdm::revision(57550
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	ADD COLUMN `set_page_title_with_conductor` enum('dont_set', 'append', 'overwrite') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'append'
	AFTER `slide_label`
_sql




//
//	Zenario 9.5
//



//Add a new option to the privacy settings for staging mode.
);	ze\dbAdm::revision(57900
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]translation_chains`
	MODIFY COLUMN `privacy`
	enum('public', 'logged_out', 'logged_in', 'group_members', 'in_smart_group', 'logged_in_not_in_smart_group', 'call_static_method', 'send_signal', 'with_role', 'with_access_code')
	CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL default 'public'
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]translation_chains`
	ADD COLUMN `access_code` char(20) CHARACTER SET ascii COLLATE ascii_general_ci NULL default NULL
	AFTER `smart_group_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]translation_chains`
	ADD KEY (`access_code`)
_sql


//Staging mocde was redesigned part-way through development as we realised the initial implementation
//would not actually be very useful.
//Remove the columns added before and add them to a different table.
);	ze\dbAdm::revision(57980
, <<<_sql
	UPDATE `[[DB_PREFIX]]translation_chains`
	SET `privacy` = 'send_signal'
	WHERE `privacy` = 'with_access_code'
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]translation_chains`
	MODIFY COLUMN `privacy`
	enum('public', 'logged_out', 'logged_in', 'group_members', 'in_smart_group', 'logged_in_not_in_smart_group', 'call_static_method', 'send_signal', 'with_role')
	CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL default 'public'
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]translation_chains`
	DROP COLUMN `access_code`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	ADD COLUMN `access_code` varchar(5) CHARACTER SET ascii COLLATE ascii_general_ci NULL default NULL
	AFTER `unpin_date`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	ADD KEY (`access_code`)
_sql

);




//Very carefully handle adding the menu_node_feature_image to the core.
if (ze\dbAdm::needRevision(57982)) {
	
	//If someone was using the zenario_promo_menu at some point between Zenario 8.8 and 9.4 (inclusive),
	//it will have created a module-version of this table. We can simple rename the table.
	if (ze\module::prefix('zenario_menu', true, true)
	 && ze\module::prefix('zenario_menu_multicolumn', true, true)
	 && ze\module::prefix('zenario_promo_menu', true, true)
	 && (ze::$dbL->checkTableDef(DB_PREFIX. ZENARIO_PROMO_MENU_PREFIX. 'menu_node_feature_image', 'feature_image_is_retina', false))) {
		
		ze\dbAdm::revision(57982
			, <<<_sql
				DROP TABLE IF EXISTS `[[DB_PREFIX]]menu_node_feature_image`
			_sql
			
			, <<<_sql
				ALTER TABLE `[[DB_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image`
				RENAME TO `[[DB_PREFIX]]menu_node_feature_image`
			_sql
		);
	
	//Otherwise create the table from scratch.
	} else {
		ze\dbAdm::revision(57982
			, <<<_sql
				DROP TABLE IF EXISTS `[[DB_PREFIX]]menu_node_feature_image`
			_sql
			
			, <<<_sql
				CREATE TABLE `[[DB_PREFIX]]menu_node_feature_image` (
					`node_id` int(10) unsigned NOT NULL,
					`use_feature_image` tinyint(1) NOT NULL default 0,
					`image_id` int(10) unsigned NOT NULL,
					`feature_image_is_retina` tinyint(1) NOT NULL default 0,
					`canvas` enum('unlimited', 'fixed_width','fixed_height', 'fixed_width_and_height', 'resize_and_crop') NOT NULL default 'unlimited',
					`width` int(10) unsigned NOT NULL,
					`height` int(10) unsigned NOT NULL,
					`offset` int(10) NOT NULL,
					`use_rollover_image` tinyint(1) NOT NULL default 0,
					`rollover_image_id` int(10) unsigned NOT NULL,
					`title` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL default '',
					`text` text CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]],
					`link_type` enum('no_link','content_item','external_url') NOT NULL default 'no_link',
					`link_visibility` enum('always_show','private','logged_out','logged_in') NOT NULL default 'always_show',
					`dest_url` varchar(255) NOT NULL default '',
					`open_in_new_window` tinyint(1) NOT NULL default '0',
					`overwrite_alt_tag` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] default NULL,
					PRIMARY KEY (`node_id`),
					KEY `image_id` (`image_id`)
				) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]] 
			_sql
		);
	}
}



//The "Keep GET requests from plugins when linking to the current content item" for menu nodes sholud now default to "off", not "on"
//(No changes to existing settings.)
	ze\dbAdm::revision(58200
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]menu_nodes`
	MODIFY COLUMN `add_registered_get_requests` tinyint(1) unsigned NOT NULL default 0
_sql



//Rename some site settings that had confusing names!
);	ze\dbAdm::revision(58300
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]site_settings`
	SET `name` = 'assetwolf_mqtt_down_topic'
	WHERE `name` = 'assetwolf_mqtt_publish_topic'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]site_settings`
	SET `name` = 'assetwolf_mqtt_up_topic'
	WHERE `name` = 'assetwolf_mqtt_subscribe_topic'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]site_settings`
	SET `name` = 'assetwolf_mqtt_alt_lora_topic'
	WHERE `name` = 'assetwolf_mqtt_subscribe_topic_2'
_sql



//This update is just to fix a typo in a CSS class name for auto-created layouts for content types
//Please note: this update was also backpatched to 9.4. However, this code should be safe to use more than once.
);	ze\dbAdm::revision(58400
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]layouts`
	SET json_data = REPLACE(json_data, 'Gribreak_Body', 'Gridbreak_Body')
	WHERE json_data like '%Gribreak_Body%'
_sql

);










//Very carefully handle adding/moving the email_template_sending_log table to the core.
if (ze\dbAdm::needRevision(58499)) {
	
	//If someone was using the zenario_promo_menu at some point between Zenario 8.8 and 9.4 (inclusive),
	//it will have created a module-version of this table. We can simple rename the table.
	if (ze\module::prefix('zenario_email_template_manager', true, true)
	 && (ze::$dbL->checkTableDef(DB_PREFIX. ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX. 'email_template_sending_log', 'debug_mode', false))) {
		
		ze\dbAdm::revision(58499
			, <<<_sql
				DROP TABLE IF EXISTS `[[DB_PREFIX]]email_template_sending_log`
			_sql
			
			, <<<_sql
				ALTER TABLE `[[DB_PREFIX]][[ZENARIO_EMAIL_TEMPLATE_MANAGER_PREFIX]]email_template_sending_log`
				RENAME TO `[[DB_PREFIX]]email_template_sending_log`
			_sql
			
			//In 9.3, went through and fixed the character-set on several columns that should
			//have been using "ascii".
			//This is safe to re-apply, so re-apply here just in case this site is coming from pre-9.3
			//and skipped this update earlier.
			, <<<_sql
				ALTER TABLE `[[DB_PREFIX]]email_template_sending_log`
				MODIFY COLUMN `content_type` varchar(20) CHARACTER SET ascii COLLATE ascii_general_ci default NULL
			_sql

		);
	
	//Otherwise create the table from scratch.
	} else {
		ze\dbAdm::revision(58499
			, <<<_sql
				DROP TABLE IF EXISTS `[[DB_PREFIX]]email_template_sending_log`
			_sql
			
			, <<<_sql
				CREATE TABLE `[[DB_PREFIX]]email_template_sending_log` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`module_id` int(10) unsigned default NULL,
					`instance_id` int(10) unsigned default NULL,
					`content_id` int(10) default NULL,
					`content_type` varchar(20) CHARACTER SET ascii COLLATE ascii_general_ci default NULL,
					`content_version` int(10) default NULL,
					`email_template_id` int(10) default NULL,
					`email_template_name` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] default NULL,
					`email_subject` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] default NULL,
					`email_address_to` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL,
					`email_address_to_overridden_by` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] default NULL,
					`email_address_from` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL,
					`email_address_replyto` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] default NULL,
					`email_name_from` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] default NULL,
					`email_body` mediumtext CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]],
					`attachment_present` tinyint(4) default NULL,
					`sent_datetime` datetime NOT NULL,
					`status` enum('success','failure') CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] default 'success',
					`debug_mode` tinyint(4) default '0',
					`email_name_replyto` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] default NULL,
					`email_ccs` varchar(255) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] default NULL,
					PRIMARY KEY (`id`)
				) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]] 
			_sql
		);
	}
}

//Very carefully handle adding/moving the error_404_log table to the core.
if (ze\dbAdm::needRevision(58500)) {
	
	//If someone was using the zenario_error_log at some point between Zenario 8.8 and 9.4 (inclusive),
	//it will have created a module-version of this table. We can simple rename the table.
	if (ze\module::prefix('zenario_error_log', true, true)
	 && (ze::$dbL->checkTableDef(DB_PREFIX. ZENARIO_ERROR_LOG_PREFIX. 'error_log', true, false))) {
		
		ze\dbAdm::revision(58500
			, <<<_sql
				DROP TABLE IF EXISTS `[[DB_PREFIX]]error_404_log`
			_sql
			
			, <<<_sql
				ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ERROR_LOG_PREFIX]]error_log`
				RENAME TO `[[DB_PREFIX]]error_404_log`
			_sql
		);
	
	//Otherwise create the table from scratch.
	} else {
		ze\dbAdm::revision(58500
			, <<<_sql
				DROP TABLE IF EXISTS `[[DB_PREFIX]]error_404_log`
			_sql
			
			, <<<_sql
				CREATE TABLE [[DB_PREFIX]]error_404_log (
					id int(10) unsigned NOT NULL AUTO_INCREMENT,
					logged datetime NOT NULL,
					referrer_url text CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL,
					page_alias varchar(255) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL,
					PRIMARY KEY (id)
				) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
			_sql
		);
	}
}



//Update the home page layout for everyone who has the wrong path to the wow.js library.
//Please note: this patch was made in 9.5 and backpatched to 9.3 and 9.4.
//However this code is safe to run more than once without a specific check needed.
ze\dbAdm::revision( 58550
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]layouts`
	SET foot_html = REPLACE(foot_html, 'zenario/libs/yarn/wowjs', 'zenario/libs/yarn/wow.js')
	WHERE foot_html like '%zenario/libs/yarn/wowjs%'
_sql
);

//In 9.5, we removed the domain redirects (or spare domains, as previously called).
//This logic will remove an obsolete site setting and drop the spare domain names table.
ze\dbAdm::revision( 58551
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]site_settings`
	WHERE name = "admin_use_ssl"
_sql
);

ze\dbAdm::revision( 58552
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]spare_domain_names`;
_sql
);

//In 9.5, we removed two scheduled tasks which were not in use for a long time:
//importCustomerData, importCustomerLocationData. Clean up the site settings table.
ze\dbAdm::revision( 58559
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]site_settings`
	WHERE name IN (
		"zenario_company_locations_manager__file_path",
		"zenario_company_locations_manager__in_filename",
		"zenario_company_locations_manager__in_locations_filename"
		"zenario_company_locations_manager__warning_email",
		"zenario_company_locations_manager__error_email"
	)
_sql
);

//Also in 9.5, the per-image settings for canvas/width/height/retina were removed from promo menu images.
//Instead, the Menu with Promo Images plugin has its own settings. Drop the obsolete columns.
ze\dbAdm::revision( 58560
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]menu_node_feature_image`
	DROP COLUMN `canvas`,
	DROP COLUMN `feature_image_is_retina`,
	DROP COLUMN `width`,
	DROP COLUMN `height`,
	DROP COLUMN `offset`
_sql


//Fix a bug where a column could be created with the wrong collation.
//Note that this is being added in 9.5 as a post-branch patch, but will be safe to reapply again in 9.6.
);	ze\dbAdm::revision( 58561
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]menu_node_feature_image`
	MODIFY COLUMN `overwrite_alt_tag` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] default NULL
_sql


//Another update to fix the wrong path to the wow.js library, this time in the versions table.
//Please note: this patch was made in 9.6 and backpatched to 9.5 and 9.4.
//However this code is safe to run more than once without a specific check needed.
);	ze\dbAdm::revision( 58562
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]content_item_versions`
	SET foot_html = REPLACE(foot_html, 'zenario/libs/yarn/wowjs', 'zenario/libs/yarn/wow.js')
	WHERE foot_html like '%zenario/libs/yarn/wowjs%'
_sql


//Fix for some bad data that could stop you from editing the plugin settings of a slideshow
//if it was migrated from an earlier version of Zenario.
//Please note: this patch was made in 9.6 and backpatched to 9.5.
//However this code is safe to run more than once without a specific check needed.
);	ze\dbAdm::revision( 58563
, <<<_sql
	 DELETE FROM `[[DB_PREFIX]]plugin_settings`
	 WHERE egg_id = 0
	   AND `name` = 'roundabout_speed'
	   AND `value` = ''
_sql

, <<<_sql
	 DELETE FROM `[[DB_PREFIX]]plugin_settings`
	 WHERE egg_id = 0
	   AND `name` = 'roundabout_speed'
	   AND `value` = '0'
_sql

, <<<_sql
	 DELETE FROM `[[DB_PREFIX]]plugin_settings`
	 WHERE egg_id = 0
	   AND `name` = 'roundabout_speed'
	   AND `value` IS NULL
_sql
);