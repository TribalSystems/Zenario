<?php

ze\dbAdm::revision( 13
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides`
_sql

,<<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides`(
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`ordinal` int(10) unsigned NOT NULL,
		`instance_id` int(10) unsigned NOT NULL,
		`image_id` int(10) unsigned NOT NULL,
		`overwrite_alt_tag` text,
		`tab_name` text,
		`slide_title` text,
		`slide_extra_html` text,
		`rollover_image_id` int(10) unsigned NULL,
		`rollover_overwrite_alt_tag` text,
		`mobile_image_id` int(10) unsigned NULL,
		`mobile_overwrite_alt_tag` text,
		`mobile_tab_name` text,
		`mobile_slide_title` text,
		`mobile_slide_extra_html` text,
		`target_loc` enum('internal','external','none') DEFAULT 'none',
		`dest_url` varchar(255) NULL,
		`open_in_new_window` tinyint(1) NOT NULL DEFAULT 0,
		`slide_visibility` enum('call_static_method', 'logged_in', 'logged_out', 'in_group', 'without_group', 'has_characteristic', 'without_characteristic', 'everyone') DEFAULT 'everyone',
		`link_visibility` enum('always_show_even_private', 'always_show_logged_in', 'only_show_logged_out', 'only_show_see_target') DEFAULT 'always_show_even_private',
		`link_to_translation_chain` tinyint(1) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	ADD KEY (`ordinal`)
_sql

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	ADD KEY (`instance_id`)
_sql

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	ADD KEY (`image_id`)
_sql

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	ADD KEY (`rollover_image_id`)
_sql

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	ADD KEY (`mobile_image_id`)
_sql

); ze\dbAdm::revision( 14
, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	ADD COLUMN `characteristic_id` int(10) unsigned NOT NULL default 0
_sql
, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	ADD COLUMN `plugin_class` varchar(255) NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	ADD COLUMN `method_name` varchar(255) NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	ADD COLUMN `param_1` varchar(255) NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	ADD COLUMN `param_2` varchar(255) NOT NULL default ''
_sql
); ze\dbAdm::revision( 15

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	CHANGE `slide_visibility` `slide_visibility` enum('call_static_method', 'logged_in', 'logged_out', 'logged_in_with_field', 'logged_in_without_field', 'without_field', 'everyone') DEFAULT 'everyone'
_sql

); ze\dbAdm::revision( 16

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	DROP COLUMN `characteristic_id`,
	ADD COLUMN field_id int(10) unsigned,
	ADD COLUMN field_value varchar(255)
_sql

); ze\dbAdm::revision( 17

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	CHANGE `plugin_class` `plugin_class` varchar(255) default '',
	CHANGE `method_name` `method_name` varchar(255) default '',
	CHANGE `param_1` `param_1` varchar(255) default '',
	CHANGE `param_2` `param_2` varchar(255) default ''
_sql

); ze\dbAdm::revision( 18

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	ADD COLUMN `use_caption_transitions` tinyint(1) NOT NULL DEFAULT 0
_sql

); ze\dbAdm::revision( 19

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	CHANGE `use_caption_transitions` `use_title_transition` tinyint(1) NOT NULL DEFAULT 0,
	ADD COLUMN `use_extra_html_transition` tinyint(1) NOT NULL DEFAULT 0,
	ADD COLUMN `title_transition` text DEFAULT NULL,
	ADD COLUMN `extra_html_transition` text DEFAULT NULL
_sql

); ze\dbAdm::revision( 22

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	ADD COLUMN `hidden` tinyint(1) NOT NULL DEFAULT 0
_sql
);


//If this is a fresh install and the installer has created a library plugin and some slide images,
//automatically add 3 images (that are hard-coded by filename) to the first slideshow.
if (ze\dbAdm::needRevision(23)) {
	if (($moduleId = ze\module::id('zenario_slideshow_2'))
	 && ($instanceId = ze\row::get('plugin_instances', 'id', ['module_id' => $moduleId]))
	 && (!ze\row::exists(ZENARIO_SLIDESHOW_2_PREFIX. 'slides', []))) {
		$ordinal = 0;
		foreach (ze\row::getArray('files', ['id', 'filename'], ['usage' => 'image']) as $image) {
			$slide = [];
			
			switch ($image['filename']) {
				case 'cityscape.jpg':
					$slide['slide_title'] = 'Welcome to Zenario';
					$slide['slide_extra_html'] = 'Start your journey...';
					break;
				case 'forest.jpg':
					$slide['slide_title'] = 'Begin editing';
					$slide['slide_extra_html'] = 'The text below tells you how to start growing your site.';
					break;
				case 'man-looking-out-at-mountains.jpg':
					$slide['slide_title'] = 'Keep clarity';
					$slide['slide_extra_html'] = 'Use the Organizer button to manage all of your website features.';
					break;
				
				default:
					continue 2;
			}
			
			$slide['ordinal'] = ++$ordinal;
			$slide['image_id'] = $image['id'];
			$slide['instance_id'] = $instanceId;
			
			ze\row::insert(ZENARIO_SLIDESHOW_2_PREFIX. 'slides', $slide);
		}
	}
	
	ze\dbAdm::revision(23);
}

ze\dbAdm::revision( 26

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	DROP `link_visibility`
_sql

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	MODIFY COLUMN `slide_visibility` enum('call_static_method','logged_in','logged_out','logged_in_with_field','logged_in_without_field','everyone') DEFAULT 'everyone'
_sql

); ze\dbAdm::revision( 27

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	DROP COLUMN `use_title_transition`,
	DROP COLUMN `use_extra_html_transition`,
	DROP COLUMN `title_transition`,
	DROP COLUMN `extra_html_transition`,
	ADD COLUMN `transition_code` text DEFAULT NULL,
	ADD COLUMN `use_transition_code` tinyint(1) NOT NULL DEFAULT 0
_sql

); ze\dbAdm::revision( 33

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	ADD COLUMN `slide_more_link_text` varchar(255) DEFAULT '' AFTER `slide_extra_html`
_sql

); ze\dbAdm::revision( 40

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` SET `dest_url` = SUBSTR(`dest_url`, 1, 250) WHERE CHAR_LENGTH(`dest_url`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `dest_url` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` SET `field_value` = SUBSTR(`field_value`, 1, 250) WHERE CHAR_LENGTH(`field_value`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `field_value` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` SET `method_name` = SUBSTR(`method_name`, 1, 250) WHERE CHAR_LENGTH(`method_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `method_name` varchar(250) CHARACTER SET utf8mb4 NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `mobile_overwrite_alt_tag` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `mobile_slide_extra_html` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `mobile_slide_title` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `mobile_tab_name` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `overwrite_alt_tag` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` SET `param_1` = SUBSTR(`param_1`, 1, 250) WHERE CHAR_LENGTH(`param_1`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `param_1` varchar(250) CHARACTER SET utf8mb4 NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` SET `param_2` = SUBSTR(`param_2`, 1, 250) WHERE CHAR_LENGTH(`param_2`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `param_2` varchar(250) CHARACTER SET utf8mb4 NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` SET `plugin_class` = SUBSTR(`plugin_class`, 1, 250) WHERE CHAR_LENGTH(`plugin_class`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `plugin_class` varchar(250) CHARACTER SET utf8mb4 NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `rollover_overwrite_alt_tag` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `slide_extra_html` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` SET `slide_more_link_text` = SUBSTR(`slide_more_link_text`, 1, 250) WHERE CHAR_LENGTH(`slide_more_link_text`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `slide_more_link_text` varchar(250) CHARACTER SET utf8mb4 NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `slide_title` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `tab_name` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides` MODIFY COLUMN `transition_code` text CHARACTER SET utf8mb4 NULL
_sql

);

