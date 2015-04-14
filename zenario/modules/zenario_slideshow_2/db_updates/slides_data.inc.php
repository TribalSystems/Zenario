<?php

revision( 13
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

); revision( 14
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
); revision( 15

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	CHANGE `slide_visibility` `slide_visibility` enum('call_static_method', 'logged_in', 'logged_out', 'logged_in_with_field', 'logged_in_without_field', 'without_field', 'everyone') DEFAULT 'everyone'
_sql

); revision( 16

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	DROP COLUMN `characteristic_id`,
	ADD COLUMN field_id int(10) unsigned,
	ADD COLUMN field_value varchar(255)
_sql

); revision( 17

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	CHANGE `plugin_class` `plugin_class` varchar(255) default '',
	CHANGE `method_name` `method_name` varchar(255) default '',
	CHANGE `param_1` `param_1` varchar(255) default '',
	CHANGE `param_2` `param_2` varchar(255) default ''
_sql

); revision( 18

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	ADD COLUMN `use_caption_transitions` tinyint(1) NOT NULL DEFAULT 0
_sql

); revision( 19

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	CHANGE `use_caption_transitions` `use_title_transition` tinyint(1) NOT NULL DEFAULT 0,
	ADD COLUMN `use_extra_html_transition` tinyint(1) NOT NULL DEFAULT 0,
	ADD COLUMN `title_transition` text DEFAULT NULL,
	ADD COLUMN `extra_html_transition` text DEFAULT NULL
_sql

); revision( 22

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	ADD COLUMN `hidden` tinyint(1) NOT NULL DEFAULT 0
_sql
);


//If this is a fresh install and the installer has created a library plugin and some slide images,
//automatically add the 
if (needRevision(23)) {
	if (($moduleId = getModuleId('zenario_slideshow_2'))
	 && ($instanceId = getRow('plugin_instances', 'id', array('module_id' => $moduleId)))
	 && (!checkRowExists(ZENARIO_SLIDESHOW_2_PREFIX. 'slides', array()))) {
		$ordinal = 0;
		foreach (getRowsArray('files', array('id', 'filename'), array('usage' => 'slideshow')) as $image) {
			$slide = array('ordinal' => ++$ordinal, 'instance_id' => $instanceId, 'image_id' => $image['id']);
			
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
			}
			
			insertRow(ZENARIO_SLIDESHOW_2_PREFIX. 'slides', $slide);
		}
	}
	
	revision(23);
}

revision( 26

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	DROP `link_visibility`
_sql

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	MODIFY COLUMN `slide_visibility` enum('call_static_method','logged_in','logged_out','logged_in_with_field','logged_in_without_field','everyone') DEFAULT 'everyone'
_sql

); revision( 27

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_SLIDESHOW_2_PREFIX]]slides
	DROP COLUMN `use_title_transition`,
	DROP COLUMN `use_extra_html_transition`,
	DROP COLUMN `title_transition`,
	DROP COLUMN `extra_html_transition`,
	ADD COLUMN `transition_code` text DEFAULT NULL,
	ADD COLUMN `use_transition_code` tinyint(1) NOT NULL DEFAULT 0
_sql

);

