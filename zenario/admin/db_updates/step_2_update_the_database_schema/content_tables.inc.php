<?php
/*
 * Copyright (c) 2022, Tribal Limited
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





//Add columns to help us more easily tell the difference between plugins/nests/slideshows when
//counting/tracking things in Organizer.
ze\dbAdm::revision( 45400
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
	ADD COLUMN `hierarchical_var` varchar(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''
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
		`name` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL,
		`privacy` enum('public', 'logged_out', 'logged_in', 'group_members', 'in_smart_group', 'logged_in_not_in_smart_group', 'with_role') NOT NULL default 'public',
		`smart_group_id` int(10) unsigned NOT NULL default 0,
		`data` mediumtext CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]],
		`created` datetime NOT NULL,
		`last_edited` datetime default NULL,
		`last_edited_admin_id` int(10) unsigned NOT NULL default 0,
		`last_edited_user_id` int(10) unsigned NOT NULL default 0,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`layout_for`, `layout_for_id`, `ord`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]] 
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
	ADD COLUMN `code_name` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL AFTER `name`,
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



//
//	Zenario 8.5
//


//Move 5 field labels of Extranet module from "Login" tab to "Phrases" tab
);	ze\dbAdm::revision(48002
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings`
		SET name = "phrase.framework.Sign in"
	WHERE name = "main_login_heading"
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings`
		SET name = "phrase.framework.Your Email:"
	WHERE name = "email_field_label"
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings`
		SET name = "phrase.framework.Your Screen Name:"
	WHERE name = "screen_name_field_label"
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings`
		SET name = "phrase.framework.Your password:"
	WHERE name = "password_field_label"
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings`
		SET name = "phrase.framework.Login"
	WHERE name = "login_button_text"
_sql

);	ze\dbAdm::revision(48003
//Reverting some of the Banner module changes. Mobile settings are once again separate settings.
//These settings are moved from Advanced Behaviour to Mobile Behaviour.
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
	  'mobile_behaviour',
	  `value`,
	  `is_content`
	FROM `[[DB_PREFIX]]plugin_settings` AS ps
	WHERE ps.name = 'advanced_behavior'
		AND value IN ("mobile_same_image", "mobile_same_image_different_size", "mobile_change_image", "mobile_hide_image")
	ORDER BY instance_id, egg_id
_sql

//Set the Advanced Behaviour select list to "None" if one of the old mobile settings was selected before (they are now a separate "Mobile behaviour" select list).
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
	  'none',
	  `is_content`
	FROM `[[DB_PREFIX]]plugin_settings` AS ps
	WHERE ps.name = "advanced_behaviour"
		AND value IN ("mobile_same_image", "mobile_same_image_different_size", "mobile_change_image", "mobile_hide_image")
	ORDER BY instance_id, egg_id
_sql


);	ze\dbAdm::revision(48004
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
	  'show_text_preview',
	  0,
	  `is_content`
	FROM `[[DB_PREFIX]]plugin_settings` AS ps
	WHERE ps.name = 'data_field'
		AND value = 'none'
	ORDER BY instance_id, egg_id
_sql



//Rework the site settings for image thumbnails to make it clearer how it works.
//Also try to cope with the fact that they may be in reverse order
);	ze\dbAdm::revision(48300
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]site_settings`
	SET `name` = 'thumbnail_threshold'
	WHERE `name` = 'working_copy_image_threshold'
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]site_settings` (
	  `name`,
	  `value`,
	  `default_value`,
	  `encrypted`
	)
	SELECT
	  'custom_thumbnail_1_width',
	  `value`,
	  `default_value`,
	  `encrypted`
	FROM `[[DB_PREFIX]]site_settings`
	WHERE `name` IN ('thumbnail_wc_image_size', 'working_copy_image_size')
	  AND `value` != ''
	  AND `value` IS NOT NULL
	ORDER BY CAST(`value` AS UNSIGNED)
	LIMIT 1
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]site_settings` (
	  `name`,
	  `value`,
	  `default_value`,
	  `encrypted`
	)
	SELECT
	  'custom_thumbnail_1_height',
	  `value`,
	  `default_value`,
	  `encrypted`
	FROM `[[DB_PREFIX]]site_settings`
	WHERE `name` IN ('thumbnail_wc_image_size', 'working_copy_image_size')
	  AND `value` != ''
	  AND `value` IS NOT NULL
	ORDER BY CAST(`value` AS UNSIGNED)
	LIMIT 1
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]site_settings` (
	  `name`,
	  `value`,
	  `default_value`,
	  `encrypted`
	)
	SELECT
	  'custom_thumbnail_2_width',
	  `value`,
	  `default_value`,
	  `encrypted`
	FROM `[[DB_PREFIX]]site_settings`
	WHERE `name` IN ('thumbnail_wc_image_size', 'working_copy_image_size')
	  AND `value` != ''
	  AND `value` IS NOT NULL
	ORDER BY CAST(`value` AS UNSIGNED)
	LIMIT 1, 1
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_PREFIX]]site_settings` (
	  `name`,
	  `value`,
	  `default_value`,
	  `encrypted`
	)
	SELECT
	  'custom_thumbnail_2_height',
	  `value`,
	  `default_value`,
	  `encrypted`
	FROM `[[DB_PREFIX]]site_settings`
	WHERE `name` IN ('thumbnail_wc_image_size', 'working_copy_image_size')
	  AND `value` != ''
	  AND `value` IS NOT NULL
	ORDER BY CAST(`value` AS UNSIGNED)
	LIMIT 1, 1
_sql

, <<<_sql
	DELETE FROM `[[DB_PREFIX]]site_settings`
	WHERE name IN ('thumbnail_wc_image_size', 'working_copy_image_size')
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	CHANGE COLUMN `working_copy_width` `custom_thumbnail_1_width` smallint(5) unsigned DEFAULT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	CHANGE COLUMN `working_copy_height` `custom_thumbnail_1_height` smallint(5) unsigned DEFAULT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	CHANGE COLUMN `working_copy_data` `custom_thumbnail_1_data` mediumblob
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	CHANGE COLUMN `working_copy_2_width` `custom_thumbnail_2_width` smallint(5) unsigned DEFAULT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	CHANGE COLUMN `working_copy_2_height` `custom_thumbnail_2_height` smallint(5) unsigned DEFAULT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	CHANGE COLUMN `working_copy_2_data` `custom_thumbnail_2_data` mediumblob
_sql

//Fix the case where the thumbnails were defined the wrong way around.
, <<<_sql
	UPDATE `[[DB_PREFIX]]files` AS f
	INNER JOIN `[[DB_PREFIX]]files` AS g
	   ON f.id = g.id
	SET f.custom_thumbnail_1_width = g.custom_thumbnail_2_width,
		f.custom_thumbnail_1_height = g.custom_thumbnail_2_height,
		f.custom_thumbnail_1_data = g.custom_thumbnail_2_data,
		f.custom_thumbnail_2_width = g.custom_thumbnail_1_width,
		f.custom_thumbnail_2_height = g.custom_thumbnail_1_height,
		f.custom_thumbnail_2_data = g.custom_thumbnail_1_data
	WHERE f.custom_thumbnail_2_width != 0
	  AND f.custom_thumbnail_2_width IS NOT NULL
	  AND (
			f.custom_thumbnail_1_width = 0
		 OR f.custom_thumbnail_1_width IS NULL
		 OR (
				f.custom_thumbnail_1_width > f.custom_thumbnail_2_width
			AND f.custom_thumbnail_1_height > f.custom_thumbnail_2_height
		)
	)
_sql


//Add a column to restrict what content type can be created under a menu node
);	ze\dbAdm::revision(48630
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]menu_nodes`
	ADD COLUMN `restrict_child_content_types` varchar(20) CHARACTER SET ascii COLLATE ascii_general_ci NULL default NULL
	AFTER `hide_private_item`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]menu_nodes`
	ADD KEY (`restrict_child_content_types`)
_sql


//Remove the columns from the content type settings that used to let you pick just one menu node
);	ze\dbAdm::revision(48640
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_types`
	DROP COLUMN `default_parent_menu_node`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_types`
	DROP COLUMN `menu_node_position`
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]content_types`
	SET menu_node_position_edit = 'suggest'
	WHERE menu_node_position_edit IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_types`
	MODIFY COLUMN `menu_node_position_edit` enum('force','suggest') NOT NULL
_sql



//
//	Zenario 8.6
//

//Add some features to the scheduled task manager for background tasks
//(This will replace the old background task manager module, the checkBackground scripts, and a few other things.)
);	ze\dbAdm::revision(49800
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]jobs`
	ADD COLUMN `job_type` enum('scheduled', 'background') NOT NULL default 'scheduled'
	AFTER `id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]jobs`
	ADD COLUMN `script_path` varchar(255) NOT NULL default ''
	AFTER `static_method`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]jobs`
	ADD COLUMN `script_restart_time` bigint(14) unsigned NOT NULL default 0
	AFTER `script_path`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]jobs`
	ADD KEY (`job_type`)
_sql


//Add a "secret" column to the site settings table. This makes settings unavailable from Twig.
);	ze\dbAdm::revision(49860
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]site_settings`
	ADD COLUMN `secret` tinyint(1) NOT NULL default 0
	AFTER `encrypted`
_sql

//Most secret settings should be set using the YAML definitions, but there are a few Assetwolf ones
//that are manually inserted into the DB and have no YAML definitions. As a quick hack to update those,
//just do them manually in a query here
, <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET `secret` = 1
	WHERE name LIKE 'assetwolf_%_password'
_sql


//Fix a bug where it was possible to put invalid characters in a filename when renaming it.
//Adding a DB query to sanitise anywhere it's previously happened.
);	ze\dbAdm::revision(49910
, <<<_sql
	UPDATE `[[DB_PREFIX]]files`
	SET filename =
		REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
		REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
			filename
		, '\\\\', ''), '/', ''), ':', ''), ';', ''), '*', ''),
		'?', ''), '"', ''), '<', ''), '>', ''), '|', '')
_sql


//Add an "is allowed" column to the document_types table to control what type of files can be uploaded
);	ze\dbAdm::revision(49980
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]document_types`
	ADD COLUMN `is_allowed` tinyint(1) NOT NULL default 1
	AFTER `custom`
_sql


//Add a "paused" column to the jobs table. This allows us to temporarily pause/resume background tasks,
//without needing to change any of the status columns. (E.g. if we paused a task by setting it's "enabled"
//column to 0, when we came to resume it if it was running before, we'd not know be sure the value was before.)
);	ze\dbAdm::revision(50000
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]jobs`
	ADD COLUMN `paused` tinyint(1) unsigned NOT NULL default 0
	AFTER `enabled`
_sql


//A few years ago we had a bug where backups taken corrupted multi-lingual characters.
//Most sites don't use multi-lingual definitions, except in the language definitions,
//so now we've got the issue where several sites were affected by the bug and have a few bad
//language definitions, but we've never noticed.
//This update checks if it looks like we've got some corrupted data in the language definitions, and deletes it if so.
//Note there's a script in step 3 that auto-repopulates these values if they are deleted, so to fix
//the issue we only need to delete them here and then wait for them to be auto-repopulated.
);	ze\dbAdm::revision(50100
, <<<_sql
	DELETE c.*, t.*
	FROM `[[DB_PREFIX]]visitor_phrases` AS c
	INNER JOIN `[[DB_PREFIX]]visitor_phrases` AS t
	   ON t.code IN ('__LANGUAGE_ENGLISH_NAME__', '__LANGUAGE_FLAG_FILENAME__', '__LANGUAGE_LOCAL_NAME__')
	WHERE c.code = '__LANGUAGE_LOCAL_NAME__'
	  AND c.local_text LIKE 'à%'
_sql


//Add columns to the language table for decimal points/thousands separator.
);	ze\dbAdm::revision(50150
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]languages`
	ADD COLUMN `thousands_sep` varchar(1) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL DEFAULT ','
	AFTER `search_type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]languages`
	ADD COLUMN `dec_point` varchar(1) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL DEFAULT '.'
	AFTER `thousands_sep`
_sql

//Albanian, Bulgarian, Czech, French, Estonian, Finnish, Hungarian, Latvian,
//Lithuanian, Polish, Russian, Slovak, Swedish, Ukrainian and Vietnamese
//all use spaces and commas instead of commas and periods
, <<<_sql
	UPDATE `[[DB_PREFIX]]languages`
	SET thousands_sep = ' ',
		dec_point = ','
	WHERE id LIKE 'sq%'
	   OR id LIKE 'bg%'
	   OR id LIKE 'cs%'
	   OR id LIKE 'fr%'
	   OR id LIKE 'et%'
	   OR id LIKE 'fi%'
	   OR id LIKE 'hu%'
	   OR id LIKE 'lv%'
	   OR id LIKE 'lt%'
	   OR id LIKE 'pl%'
	   OR id LIKE 'ru%'
	   OR id LIKE 'sk%'
	   OR id LIKE 'sv%'
	   OR id LIKE 'uk%'
	   OR id LIKE 'vi%'
_sql

//...and Italian, Norwegian and Spanish use periods and commas
, <<<_sql
	UPDATE `[[DB_PREFIX]]languages`
	SET thousands_sep = '.',
		dec_point = ','
	WHERE id LIKE 'it%'
	   OR id LIKE 'no%'
	   OR id LIKE 'es%'
_sql


//Some code tidying - I'm mass-renaming a plugin setting.
//The "scope_for_creation_and_lists" should just be called "scope" as that's more generic and therefore more useful.
//(I'm doing this as I want to start making core functions that interact with this, and a more standard name is desirable.)
);	ze\dbAdm::revision(50190
, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings`
	SET name = 'scope'
	WHERE name = 'scope_for_creation_and_lists'
_sql

);	ze\dbAdm::revision(50192
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]job_logs` MODIFY COLUMN `note` mediumtext CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL
_sql


//Add a locking table for the cleanDirs function. This is to try and prevent the race conditions we sometimes get, where
//I think two scripts are running at the same time
);	ze\dbAdm::revision(50300
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]lock__clean_dirs`
_sql
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]]lock__clean_dirs` (
		`dummy` tinyint(1) unsigned NOT NULL
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=ascii
_sql




//Define a new table to store custom tuix
); ze\dbAdm::revision( 50330
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]tuix_customisations`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]]tuix_customisations` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL,
		`custom_yaml` mediumtext CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL,
		`custom_json` mediumtext CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL,
		`created` datetime DEFAULT NULL,
		`created_admin_id` int(10) unsigned DEFAULT NULL,
		`created_user_id` int(10) unsigned DEFAULT NULL,
		`created_username` varchar(255) DEFAULT NULL,
		`last_edited` datetime DEFAULT NULL,
		`last_edited_admin_id` int(10) unsigned DEFAULT NULL,
		`last_edited_user_id` int(10) unsigned DEFAULT NULL,
		`last_edited_username` varchar(255) DEFAULT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`name`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]] 
_sql


//Changed the name of this table to a better name
);	ze\dbAdm::revision(50440
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]tuix_customisations`
	RENAME TO `[[DB_PREFIX]]tuix_snippets`
_sql

//Also update the name in the plugin settings, if anyone already had it saved
, <<<_sql
	UPDATE `[[DB_PREFIX]]plugin_settings`
	SET name = '~tuix_snippet~'
	WHERE name = '~customisation~'
_sql


//For anyone using the Black Dog skin, attempt to update the logic for the CSS animations to use the new library (as the previous one is now broken).
);	ze\dbAdm::revision(50530
, <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET value = REPLACE(value, "<script type='text/javascript' src='zenario_custom/templates/grid_templates/skins/zebra_designs/js/animation_load.js'></script>", '')
	WHERE name = 'sitewide_foot'
	  AND value IS NOT NULL
	  AND value != ''
_sql
	
, <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET value = REPLACE(value, "<script type='text/javascript' src='zenario_custom/templates/grid_templates/skins/zebra_designs/js/css3-animate-it.js'></script>", '')
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
	SET foot_html = CONCAT(IFNULL(foot_html, ''), '\n<script type="text/javascript" src="zenario/libs/yarn/wowjs/dist/wow.min.js"></script>\n<script type="text/javascript" src="zenario_custom/templates/grid_templates/skins/zebra_designs/js/animation_load.js"></script>')
	WHERE family_name = 'grid_templates'
	  AND file_base_name = 'L02'
	  AND (foot_html IS NULL OR foot_html NOT LIKE '%wow.min.js%')
_sql



//Create a new table to remember which plugin/mode is on which content item.
//This will work a bit like the special pages system, but with less bureaucracy. 
); ze\dbAdm::revision(50600
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]plugin_pages_by_mode`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]]plugin_pages_by_mode` (
		`equiv_id` int(10) unsigned NOT NULL,
		`content_type` varchar(20) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
		`module_class_name` varchar(200) NOT NULL,
		`mode` varchar(200) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT '',
		PRIMARY KEY (`module_class_name`, `mode`),
		UNIQUE KEY `content_type` (`content_type`,`equiv_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]] 
_sql

//Add a "state" column to cover plugins in conductors
); ze\dbAdm::revision(50605
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_pages_by_mode`
	ADD COLUMN `state` char(2) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL default ''
_sql




//
//	Zenario 8.7
//


//Add a "protect_from_database_restore" column to the site settings table
); ze\dbAdm::revision(50700
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]site_settings`
	ADD COLUMN `protect_from_database_restore` tinyint(1) NOT NULL default 0
_sql


//Add a column to control creating menu nodes to the content type settings
//(N.b. this was added in an after-branch patch in 8.3 revision 50606, so we need to check if it's not already there.)
);	if (ze\dbAdm::needRevision(50760) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'content_types LIKE "prompt_to_create_a_menu_node"')) ze\dbAdm::revision(50760
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_types`
	ADD COLUMN `prompt_to_create_a_menu_node` tinyint(1) NOT NULL default 1
	AFTER `module_id`
_sql


//Remove the create_and_maintain_in_all_languages option for special pages
//(N.b. this was added in an after-branch patch in 8.3 revision 50607, but is safe to repeat.)
); ze\dbAdm::revision(50780
, <<<_sql
	UPDATE `[[DB_PREFIX]]special_pages`
	SET `logic` = 'create_and_maintain_in_default_language'
	WHERE `logic` = 'create_and_maintain_in_all_languages'
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]special_pages`
	MODIFY COLUMN `logic` enum('create_and_maintain_in_default_language','create_in_default_language_on_install') NOT NULL DEFAULT 'create_and_maintain_in_default_language'
_sql


//Add a column to the languages table to control how each language behaves in the language picker
//(N.b. this was added in an after-branch patch in 8.3 revision 50608, so we need to check if it's not already there.)
);	if (ze\dbAdm::needRevision(50785) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'languages LIKE "language_picker_logic"')) ze\dbAdm::revision(50785
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]languages`
	ADD COLUMN `language_picker_logic` enum('visible_or_disabled', 'visible_or_hidden', 'always_hidden') NOT NULL DEFAULT 'visible_or_disabled'
	AFTER `search_type`
_sql


//Add a new index to the tuix_file_contents table to help speed up a new query I need from that table
); ze\dbAdm::revision(50800
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]tuix_file_contents`
	ADD KEY `module_panel_types` (`type`, `module_class_name`, `panel_type`, `path`)
_sql


//Add a new privacy sub-option that controls where a user needs to have a role set
); ze\dbAdm::revision(51000
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]nested_plugins`
	ADD COLUMN `at_location` enum('any', 'in_url', 'detect') NOT NULL DEFAULT 'any'
	AFTER `privacy`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]slide_layouts`
	ADD COLUMN `at_location` enum('any', 'in_url', 'detect') NOT NULL DEFAULT 'in_url'
	AFTER `privacy`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]translation_chains`
	ADD COLUMN `at_location` enum('any', 'in_url', 'detect') NOT NULL DEFAULT 'any'
	AFTER `privacy`
_sql


//Fix a bug where the key is longer than 1000 bytes - the column was previously a varchar(255).
//Note that this was added in a post-branch patch in 8.6 revision 50610, and also retroactively 
//changed in the CREATE TABLE statement, however it's safe to re-apply so we're also changing
//it here so any existing sites are consistent.
);	ze\dbAdm::revision(51300
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]tuix_snippets`
	MODIFY COLUMN `name` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL
_sql








//
//	Zenario 8.8
//

//Custom dataset labels no longer need to be unique
//The content item index on plugin_pages_by_mode also shouldn't be unique
//Note that these fixes are being added in a post-branch patch in 8.6 revision 50611,
//and in a post-branch patch in 8.7 revision 51301, and in 8.8 in revision 51700.
//However they're safe to re-apply more than once so we don't need the check to see if
//they've already been applied.
);	ze\dbAdm::revision(51700
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_datasets`
	DROP KEY `label`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_datasets`
	ADD KEY `label` (`label`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_pages_by_mode`
	DROP KEY `content_type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]plugin_pages_by_mode`
	ADD KEY `content_type` (`content_type`,`equiv_id`)
_sql


//Add a new column to the content_types table
//(Note - this is written carefully, as the original version of this update caused a SVN clash,
// so I've given it a new number and am very carefully checking if it's been applied first before running it.)
);	if (ze\dbAdm::needRevision(51710) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'content_types LIKE "tooltip_text"'))	ze\dbAdm::revision(51710
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_types`
	ADD COLUMN `tooltip_text` varchar(255) NOT NULL default ''
	AFTER `description_field`
_sql


); ze\dbAdm::revision( 51711
, <<<_sql
	UPDATE `[[DB_PREFIX]]content_types`
	SET `tooltip_text` = 'Flat view of all HTML page content items'
	WHERE `content_type_id` = 'html'
_sql


); 	ze\dbAdm::revision( 51904
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	ADD COLUMN `s3_file_id` int(10) unsigned NOT NULL default 0 AFTER `file_id`
_sql

); ze\dbAdm::revision( 51905
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]files`
	MODIFY COLUMN `location` enum('db','docstore','s3') NOT NULL 
_sql
); ze\dbAdm::revision( 51906
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]content_item_versions`
	ADD COLUMN `s3_filename` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL default '' AFTER `filename`
_sql
);	ze\dbAdm::revision(51907
, <<<_sql
	UPDATE IGNORE `[[DB_PREFIX]]plugin_settings`
	SET name = 'show_title'
	WHERE name = 'show_envelope_name'
_sql


//
//	Zenario 8.9
//


//Add a "Only show the Back button when the previous slide has more than one item to choose from" flag for slides
);	ze\dbAdm::revision( 52150
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
	`ord` smallint(4) unsigned NOT NULL DEFAULT '0',
	`cols` tinyint(2) unsigned NOT NULL DEFAULT '0',
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
	ADD COLUMN `document_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `use_download_page`,
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


//Post branch fix.
//Fix some bad data where some nests/slideshows were not flagged as nests/slideshows in the database.
//(N.b. this was added in an after-branch patch in 9.2 revision 55053, but is safe to repeat.)
);	ze\dbAdm::revision( 56353
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

);