


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]categories`;
CREATE TABLE `[[DB_NAME_PREFIX]]categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL,
  `public` tinyint(1) NOT NULL DEFAULT '0',
  `landing_page_equiv_id` int(10) unsigned NOT NULL DEFAULT '0',
  `landing_page_content_type` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `name` (`name`,`parent_id`),
  KEY `public` (`public`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]category_item_link`;
CREATE TABLE `[[DB_NAME_PREFIX]]category_item_link` (
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `equiv_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` char(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`category_id`,`content_type`,`equiv_id`),
  KEY `equiv_id` (`equiv_id`,`content_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]characteristic_user_link`;
CREATE TABLE `[[DB_NAME_PREFIX]]characteristic_user_link` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `characteristic_id` int(10) NOT NULL,
  `characteristic_value_id` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_characteristic_value` (`user_id`,`characteristic_id`,`characteristic_value_id`),
  KEY `characteristic_id` (`characteristic_id`),
  KEY `characteristic_value_id` (`characteristic_value_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content`;
CREATE TABLE `[[DB_NAME_PREFIX]]content` (
  `id` int(10) unsigned NOT NULL,
  `type` varchar(20) NOT NULL,
  `tag_id` varchar(32) NOT NULL,
  `equiv_id` int(10) unsigned NOT NULL,
  `language_id` varchar(15) NOT NULL,
  `alias` varchar(75) NOT NULL DEFAULT '',
  `lang_code_in_url` enum('show','hide','default') NOT NULL DEFAULT 'default',
  `first_created_datetime` datetime DEFAULT NULL,
  `visitor_version` int(10) unsigned NOT NULL DEFAULT '0',
  `admin_version` int(10) unsigned NOT NULL DEFAULT '1',
  `status` enum('first_draft','published_with_draft','hidden_with_draft','trashed_with_draft','published','hidden','trashed','deleted') NOT NULL DEFAULT 'first_draft',
  `lock_owner_id` int(10) unsigned NOT NULL DEFAULT '0',
  `locked_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`,`type`),
  UNIQUE KEY `tag_id` (`tag_id`),
  UNIQUE KEY `equiv_id` (`equiv_id`,`type`,`language_id`),
  KEY `type` (`type`),
  KEY `language_id` (`language_id`),
  KEY `alias` (`alias`),
  KEY `first_created_datetime` (`first_created_datetime`),
  KEY `visitor_version` (`visitor_version`),
  KEY `admin_version` (`admin_version`),
  KEY `status` (`status`),
  KEY `lock_owner_id` (`lock_owner_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_cache`;
CREATE TABLE `[[DB_NAME_PREFIX]]content_cache` (
  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` varchar(20) NOT NULL DEFAULT '',
  `content_version` int(10) unsigned NOT NULL DEFAULT '0',
  `text` mediumtext,
  `extract` mediumtext,
  `extract_wordcount` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`content_id`,`content_type`,`content_version`),
  KEY `extract_wordcount` (`extract_wordcount`),
  FULLTEXT KEY `text` (`text`),
  FULLTEXT KEY `extract` (`extract`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_types`;
CREATE TABLE `[[DB_NAME_PREFIX]]content_types` (
  `content_type_id` varchar(20) NOT NULL DEFAULT '',
  `content_type_name_en` varchar(255) NOT NULL DEFAULT '',
  `writer_field` enum('optional','mandatory','hidden') NOT NULL DEFAULT 'optional',
  `description_field` enum('optional','mandatory','hidden') NOT NULL DEFAULT 'optional',
  `keywords_field` enum('optional','mandatory','hidden') NOT NULL DEFAULT 'optional',
  `summary_field` enum('optional','mandatory','hidden') NOT NULL DEFAULT 'optional',
  `release_date_field` enum('optional','mandatory','hidden') NOT NULL DEFAULT 'optional',
  `enable_summary_auto_update` tinyint(1) NOT NULL DEFAULT '0',
  `default_layout_id` int(10) unsigned NOT NULL DEFAULT '0',
  `module_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`content_type_id`),
  KEY `content_type_id` (`content_type_id`),
  KEY `plugin_id` (`module_id`),
  KEY `default_layout_id` (`default_layout_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]custom_dataset_field_values`;
CREATE TABLE `[[DB_NAME_PREFIX]]custom_dataset_field_values` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `field_id` int(10) NOT NULL,
  `ord` int(10) NOT NULL DEFAULT '0',
  `label` varchar(255) NOT NULL,
  `note_below` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `field_id` (`field_id`,`id`),
  KEY `field_id_2` (`field_id`,`ord`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]custom_dataset_fields`;
CREATE TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `dataset_id` int(10) unsigned NOT NULL,
  `tab_name` varchar(16) CHARACTER SET ascii NOT NULL,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `is_system_field` tinyint(1) NOT NULL DEFAULT '0',
  `field_name` varchar(64) CHARACTER SET ascii NOT NULL DEFAULT '',
  `ord` int(10) unsigned NOT NULL DEFAULT '0',
  `label` varchar(64) NOT NULL DEFAULT '',
  `type` enum('group','checkbox','checkboxes','date','editor','radios','centralised_radios','select','centralised_select','text','textarea','url','other_system_field') NOT NULL DEFAULT 'other_system_field',
  `width` smallint(5) unsigned NOT NULL DEFAULT '0',
  `height` smallint(5) unsigned NOT NULL DEFAULT '0',
  `values_source` varchar(255) NOT NULL DEFAULT '',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `required_message` text,
  `validation` enum('none','email','emails','no_spaces','numeric','screen_name') NOT NULL DEFAULT 'none',
  `validation_message` text,
  `note_below` text,
  `db_column` varchar(255) NOT NULL DEFAULT '',
  `show_in_organizer` tinyint(1) NOT NULL DEFAULT '0',
  `searchable` tinyint(1) NOT NULL DEFAULT '0',
  `sortable` tinyint(1) NOT NULL DEFAULT '0',
  `show_by_default` tinyint(1) NOT NULL DEFAULT '0',
  `always_show` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `dataset_id` (`dataset_id`,`tab_name`,`id`),
  KEY `dataset_id_2` (`dataset_id`,`tab_name`,`ord`),
  KEY `show_in_organizer` (`show_in_organizer`),
  KEY `is_system_field` (`is_system_field`),
  KEY `parent_id` (`parent_id`),
  KEY `field_name` (`field_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]custom_dataset_tabs`;
CREATE TABLE `[[DB_NAME_PREFIX]]custom_dataset_tabs` (
  `dataset_id` int(10) unsigned NOT NULL,
  `name` varchar(16) CHARACTER SET ascii NOT NULL,
  `ord` int(10) unsigned NOT NULL DEFAULT '0',
  `label` varchar(32) NOT NULL DEFAULT '',
  `parent_field_id` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`dataset_id`,`name`),
  KEY `ord` (`ord`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]custom_dataset_values_link`;
CREATE TABLE `[[DB_NAME_PREFIX]]custom_dataset_values_link` (
  `dataset_id` int(10) NOT NULL,
  `value_id` int(10) NOT NULL,
  `linking_id` int(10) NOT NULL,
  PRIMARY KEY (`value_id`,`linking_id`),
  UNIQUE KEY `linking_id` (`linking_id`,`value_id`),
  KEY `dataset_id` (`dataset_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]custom_datasets`;
CREATE TABLE `[[DB_NAME_PREFIX]]custom_datasets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(64) NOT NULL,
  `system_table` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `table` varchar(255) CHARACTER SET ascii NOT NULL,
  `extends_admin_box` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `extends_organizer_panel` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `table` (`table`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]document_tag_link`;
CREATE TABLE `[[DB_NAME_PREFIX]]document_tag_link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` int(10) NOT NULL,
  `tag_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_tag_link` (`document_id`,`tag_id`),
  KEY `type` (`document_id`),
  KEY `folder_id` (`tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]document_tags`;
CREATE TABLE `[[DB_NAME_PREFIX]]document_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_name` (`tag_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]document_types`;
CREATE TABLE `[[DB_NAME_PREFIX]]document_types` (
  `type` varchar(10) NOT NULL DEFAULT '',
  `mime_type` varchar(128) NOT NULL DEFAULT '',
  `custom` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`type`),
  KEY `custom` (`custom`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]documents`;
CREATE TABLE `[[DB_NAME_PREFIX]]documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ordinal` int(10) NOT NULL,
  `type` enum('file','folder') NOT NULL DEFAULT 'file',
  `file_id` int(10) DEFAULT NULL,
  `folder_id` int(10) NOT NULL DEFAULT '0',
  `folder_name` varchar(255) DEFAULT NULL,
  `document_datetime` datetime DEFAULT NULL,
  `thumbnail_id` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_id` (`file_id`),
  KEY `ordinal` (`ordinal`),
  KEY `type` (`type`),
  KEY `folder_id` (`folder_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]email_templates`;
CREATE TABLE `[[DB_NAME_PREFIX]]email_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `template_name` varchar(255) NOT NULL DEFAULT '',
  `subject` tinytext NOT NULL,
  `email_address_from` varchar(100) NOT NULL DEFAULT '',
  `email_name_from` varchar(255) NOT NULL DEFAULT '',
  `body` text,
  `date_created` datetime NOT NULL,
  `created_by_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date_modified` datetime DEFAULT NULL,
  `modified_by_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_sent` datetime DEFAULT NULL,
  `allow_attachments` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_name` (`template_name`),
  UNIQUE KEY `code` (`code`),
  KEY `date_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]files`;
CREATE TABLE `[[DB_NAME_PREFIX]]files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `checksum` varchar(32) NOT NULL,
  `usage` varchar(64) NOT NULL,
  `shared` tinyint(1) NOT NULL DEFAULT '0',
  `created_datetime` datetime DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `mime_type` varchar(128) NOT NULL,
  `width` smallint(5) unsigned NOT NULL DEFAULT '0',
  `height` smallint(5) unsigned NOT NULL DEFAULT '0',
  `alt_tag` text,
  `title` text,
  `floating_box_title` text,
  `size` int(10) unsigned NOT NULL,
  `location` enum('db','docstore') NOT NULL,
  `data` longblob,
  `path` varchar(128) NOT NULL DEFAULT '',
  `storekeeper_width` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `storekeeper_height` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `storekeeper_data` blob,
  `storekeeper_list_width` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `storekeeper_list_height` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `storekeeper_list_data` blob,
  `working_copy_width` smallint(5) unsigned DEFAULT NULL,
  `working_copy_height` smallint(5) unsigned DEFAULT NULL,
  `working_copy_data` mediumblob,
  `working_copy_2_width` smallint(5) unsigned DEFAULT NULL,
  `working_copy_2_height` smallint(5) unsigned DEFAULT NULL,
  `working_copy_2_data` mediumblob,
  PRIMARY KEY (`id`),
  UNIQUE KEY `checksum` (`checksum`,`usage`),
  KEY `usage` (`usage`),
  KEY `created_datetime` (`created_datetime`),
  KEY `filename` (`filename`),
  KEY `mime_type` (`mime_type`),
  KEY `location` (`location`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]group_content_link`;
CREATE TABLE `[[DB_NAME_PREFIX]]group_content_link` (
  `group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `equiv_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` char(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`group_id`,`content_type`,`equiv_id`),
  KEY `equiv_id` (`equiv_id`,`content_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]group_user_link`;
CREATE TABLE `[[DB_NAME_PREFIX]]group_user_link` (
  `group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `joined_group_date` datetime DEFAULT NULL,
  `remove_date` datetime DEFAULT NULL,
  PRIMARY KEY (`group_id`,`user_id`),
  KEY `user_id` (`user_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]groups`;
CREATE TABLE `[[DB_NAME_PREFIX]]groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `public` tinyint(1) NOT NULL DEFAULT '0',
  `allow_opt_in_opt_out` tinyint(1) NOT NULL DEFAULT '0',
  `description` text,
  `members_are_employees` tinyint(1) NOT NULL DEFAULT '0',
  `active` int(1) NOT NULL DEFAULT '1',
  `image_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_characteristic_id` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `public` (`public`),
  KEY `image_id` (`image_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]inline_file_link`;
CREATE TABLE `[[DB_NAME_PREFIX]]inline_file_link` (
  `file_id` int(10) unsigned NOT NULL,
  `foreign_key_to` varchar(64) NOT NULL,
  `foreign_key_id` int(10) unsigned NOT NULL DEFAULT '0',
  `foreign_key_char` varchar(255) NOT NULL DEFAULT '',
  `foreign_key_version` int(10) unsigned NOT NULL DEFAULT '0',
  `in_use` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`foreign_key_to`,`foreign_key_id`,`foreign_key_char`,`foreign_key_version`,`file_id`),
  KEY `file_id` (`file_id`),
  KEY `in_use` (`in_use`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]job_logs`;
CREATE TABLE `[[DB_NAME_PREFIX]]job_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `status` enum('action_taken','no_action_taken','error') NOT NULL,
  `started` datetime DEFAULT NULL,
  `finished` datetime DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `status` (`status`),
  KEY `started` (`started`),
  KEY `finished` (`finished`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]jobs`;
CREATE TABLE `[[DB_NAME_PREFIX]]jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `manager_class_name` varchar(200) NOT NULL,
  `job_name` varchar(127) NOT NULL,
  `module_id` int(10) unsigned NOT NULL,
  `module_class_name` varchar(200) NOT NULL,
  `static_method` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `months` set('jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec') NOT NULL DEFAULT 'jan,feb,mar,apr,may,jun,jul,aug,sep,oct,nov,dec',
  `days` set('mon','tue','wed','thr','fri','sat','sun') NOT NULL DEFAULT 'mon,tue,wed,thr,fri,sat,sun',
  `hours` set('0h','1h','2h','3h','4h','5h','6h','7h','8h','9h','10h','11h','12h','13h','14h','15h','16h','17h','18h','19h','20h','21h','22h','23h') NOT NULL DEFAULT '0h',
  `minutes` set('0m','5m','10m','15m','20m','25m','30m','35m','40m','45m','50m','55m','59m') NOT NULL DEFAULT '0m',
  `first_n_days_of_month` tinyint(1) NOT NULL DEFAULT '0',
  `log_on_action` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `log_on_no_action` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `email_on_action` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `email_on_no_action` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `email_address_on_action` varchar(200) NOT NULL DEFAULT '',
  `email_address_on_no_action` varchar(200) NOT NULL DEFAULT '',
  `email_address_on_error` varchar(200) NOT NULL DEFAULT '',
  `last_run_started` datetime DEFAULT NULL,
  `last_run_finished` datetime DEFAULT NULL,
  `status` enum('never_run','rerun_scheduled','in_progress','action_taken','no_action_taken','error') NOT NULL DEFAULT 'never_run',
  `last_successful_run` datetime DEFAULT NULL,
  `last_action` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_name` (`job_name`,`module_class_name`),
  KEY `plugin_id` (`module_id`),
  KEY `first_n_days_of_month` (`first_n_days_of_month`),
  KEY `manager_class_name` (`manager_class_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]languages`;
CREATE TABLE `[[DB_NAME_PREFIX]]languages` (
  `id` varchar(15) NOT NULL DEFAULT '',
  `detect` tinyint(1) NOT NULL DEFAULT '0',
  `detect_lang_codes` varchar(100) NOT NULL DEFAULT '',
  `sync_assist` tinyint(1) NOT NULL DEFAULT '0',
  `search_type` enum('full_text','simple') NOT NULL DEFAULT 'full_text',
  PRIMARY KEY (`id`),
  KEY `detect` (`detect`),
  KEY `sync_assist` (`sync_assist`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]layouts`;
CREATE TABLE `[[DB_NAME_PREFIX]]layouts` (
  `layout_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `family_name` varchar(50) NOT NULL DEFAULT '',
  `file_base_name` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `content_type` varchar(20) NOT NULL DEFAULT '',
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `skin_id` int(10) unsigned DEFAULT NULL,
  `css_class` varchar(100) NOT NULL DEFAULT '',
  `head_html` mediumtext,
  `head_cc` enum('not_needed','needed','required') NOT NULL DEFAULT 'not_needed',
  `head_visitor_only` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `foot_html` mediumtext,
  `foot_cc` enum('not_needed','needed','required') NOT NULL DEFAULT 'not_needed',
  `foot_visitor_only` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`layout_id`),
  KEY `family_name` (`family_name`,`file_base_name`),
  KEY `name` (`name`),
  KEY `content_type` (`content_type`),
  KEY `status` (`status`),
  KEY `skin_id` (`skin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]local_revision_numbers`;
CREATE TABLE `[[DB_NAME_PREFIX]]local_revision_numbers` (
  `path` varchar(255) NOT NULL,
  `patchfile` varchar(64) NOT NULL,
  `revision_no` int(10) unsigned NOT NULL,
  PRIMARY KEY (`path`,`patchfile`),
  KEY `revision_no` (`revision_no`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]menu_hierarchy`;
CREATE TABLE `[[DB_NAME_PREFIX]]menu_hierarchy` (
  `section_id` smallint(10) unsigned NOT NULL,
  `ancestor_id` int(10) unsigned NOT NULL,
  `child_id` int(10) unsigned NOT NULL,
  `separation` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`ancestor_id`,`child_id`),
  KEY `child_id` (`child_id`),
  KEY `separation` (`separation`),
  KEY `section_id` (`section_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]menu_nodes`;
CREATE TABLE `[[DB_NAME_PREFIX]]menu_nodes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` smallint(10) unsigned NOT NULL,
  `redundancy` enum('primary','secondary') NOT NULL DEFAULT 'primary',
  `accesskey` char(1) NOT NULL DEFAULT '',
  `target_loc` enum('int','ext','none') NOT NULL DEFAULT 'none',
  `open_in_new_window` tinyint(1) NOT NULL DEFAULT '0',
  `equiv_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` varchar(20) NOT NULL DEFAULT '',
  `use_download_page` tinyint(1) NOT NULL DEFAULT '0',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `ordinal` int(10) unsigned NOT NULL DEFAULT '0',
  `invisible` tinyint(1) NOT NULL DEFAULT '0',
  `hide_private_item` tinyint(1) NOT NULL DEFAULT '0',
  `rel_tag` varchar(100) DEFAULT NULL,
  `css_class` varchar(100) NOT NULL DEFAULT '',
  `image_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ordinal` (`ordinal`),
  KEY `content_type` (`content_type`),
  KEY `parent_id` (`parent_id`),
  KEY `content_id` (`equiv_id`),
  KEY `target_loc` (`target_loc`),
  KEY `invisible` (`invisible`),
  KEY `section_id` (`section_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]menu_sections`;
CREATE TABLE `[[DB_NAME_PREFIX]]menu_sections` (
  `id` smallint(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_name` (`section_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]menu_text`;
CREATE TABLE `[[DB_NAME_PREFIX]]menu_text` (
  `menu_id` int(10) unsigned NOT NULL,
  `language_id` varchar(10) NOT NULL DEFAULT 'en',
  `name` varchar(255) NOT NULL DEFAULT '',
  `ext_url` varchar(255) NOT NULL DEFAULT '',
  `descriptive_text` mediumtext,
  PRIMARY KEY (`menu_id`,`language_id`),
  KEY `language_id` (`language_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]module_dependencies`;
CREATE TABLE `[[DB_NAME_PREFIX]]module_dependencies` (
  `module_id` int(10) unsigned NOT NULL,
  `module_class_name` varchar(200) NOT NULL,
  `dependency_class_name` varchar(200) NOT NULL,
  `type` enum('dependency','inherit_frameworks','include_javascript','inherit_settings','allow_upgrades') NOT NULL,
  UNIQUE KEY `module_id` (`module_id`,`type`,`dependency_class_name`),
  KEY `type` (`type`),
  KEY `module_class_name` (`module_class_name`,`type`),
  KEY `dependency_class_name` (`dependency_class_name`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]modules`;
CREATE TABLE `[[DB_NAME_PREFIX]]modules` (
  `id` int(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_name` varchar(200) NOT NULL,
  `vlp_class` varchar(200) NOT NULL DEFAULT '',
  `display_name` varchar(255) NOT NULL,
  `default_framework` varchar(50) NOT NULL DEFAULT '',
  `css_class_name` varchar(200) NOT NULL DEFAULT '',
  `uses_instances` tinyint(1) NOT NULL DEFAULT '0',
  `uses_wireframes` tinyint(1) NOT NULL DEFAULT '0',
  `nestable` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('module_not_initialized','module_running','module_suspended') NOT NULL DEFAULT 'module_not_initialized',
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_name` (`class_name`),
  KEY `uses_wireframes` (`uses_wireframes`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]nested_plugins`;
CREATE TABLE `[[DB_NAME_PREFIX]]nested_plugins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `instance_id` int(10) unsigned NOT NULL,
  `tab` smallint(4) unsigned NOT NULL DEFAULT '1',
  `ord` smallint(4) unsigned NOT NULL DEFAULT '1',
  `module_id` int(10) unsigned NOT NULL,
  `framework` varchar(50) NOT NULL DEFAULT '',
  `css_class` varchar(100) NOT NULL DEFAULT '',
  `is_tab` tinyint(1) NOT NULL DEFAULT '0',
  `name_or_title` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `instance_id` (`instance_id`,`is_tab`,`tab`,`ord`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_instance_cache`;
CREATE TABLE `[[DB_NAME_PREFIX]]plugin_instance_cache` (
  `instance_id` int(10) unsigned NOT NULL,
  `method_name` varchar(64) NOT NULL,
  `request` varchar(255) NOT NULL DEFAULT '',
  `last_updated` datetime NOT NULL,
  `cache` mediumtext NOT NULL,
  PRIMARY KEY (`instance_id`,`method_name`,`request`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_instances`;
CREATE TABLE `[[DB_NAME_PREFIX]]plugin_instances` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL DEFAULT '',
  `module_id` int(10) unsigned NOT NULL,
  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` varchar(20) NOT NULL DEFAULT '',
  `content_version` int(10) unsigned NOT NULL DEFAULT '0',
  `slot_name` varchar(100) NOT NULL DEFAULT '',
  `framework` varchar(50) NOT NULL DEFAULT '',
  `css_class` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `wireframe_instance` (`content_id`,`content_type`,`content_version`,`slot_name`,`module_id`),
  KEY `module_id` (`module_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_item_link`;
CREATE TABLE `[[DB_NAME_PREFIX]]plugin_item_link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(10) unsigned NOT NULL,
  `instance_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_id` int(10) unsigned NOT NULL,
  `content_type` varchar(20) NOT NULL,
  `content_version` int(10) unsigned NOT NULL,
  `slot_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_id` (`content_id`,`content_type`,`content_version`,`slot_name`),
  KEY `instance_id` (`instance_id`,`content_type`),
  KEY `slot_name` (`instance_id`,`slot_name`),
  KEY `reusable_plugin_item_link` (`instance_id`,`content_id`,`content_type`,`content_version`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_layout_link`;
CREATE TABLE `[[DB_NAME_PREFIX]]plugin_layout_link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(10) unsigned NOT NULL,
  `instance_id` int(10) unsigned NOT NULL DEFAULT '0',
  `family_name` varchar(50) NOT NULL,
  `layout_id` int(10) unsigned NOT NULL,
  `slot_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `family_name` (`family_name`,`layout_id`,`slot_name`),
  KEY `slot_name` (`instance_id`,`slot_name`),
  KEY `layout_id` (`layout_id`,`slot_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_setting_defs`;
CREATE TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs` (
  `module_id` int(10) unsigned NOT NULL,
  `module_class_name` varchar(200) NOT NULL,
  `name` varchar(50) NOT NULL,
  `default_value` mediumtext,
  PRIMARY KEY (`module_id`,`name`),
  UNIQUE KEY `module_class_name` (`module_class_name`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_settings`;
CREATE TABLE `[[DB_NAME_PREFIX]]plugin_settings` (
  `instance_id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `nest` int(10) unsigned NOT NULL DEFAULT '0',
  `value` mediumtext,
  `is_content` enum('synchronized_setting','version_controlled_setting','version_controlled_content') NOT NULL DEFAULT 'synchronized_setting',
  `format` enum('empty','text','html','translatable_text','translatable_html') NOT NULL DEFAULT 'text',
  `foreign_key_to` varchar(64) DEFAULT NULL,
  `foreign_key_id` int(10) unsigned NOT NULL DEFAULT '0',
  `foreign_key_char` varchar(255) NOT NULL DEFAULT '',
  `dangling_cross_references` enum('keep','remove','delete_instance') NOT NULL DEFAULT 'remove',
  PRIMARY KEY (`instance_id`,`name`,`nest`),
  KEY `is_content` (`is_content`),
  KEY `foreign_key_to` (`foreign_key_to`,`foreign_key_id`,`foreign_key_char`),
  KEY `dangling_cross_references` (`dangling_cross_references`),
  KEY `value` (`value`(64)),
  KEY `foreign_key_char` (`foreign_key_to`,`foreign_key_char`),
  KEY `format` (`format`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]signals`;
CREATE TABLE `[[DB_NAME_PREFIX]]signals` (
  `signal_name` varchar(127) NOT NULL,
  `module_id` int(10) unsigned NOT NULL,
  `module_class_name` varchar(200) NOT NULL,
  `static_method` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `suppresses_module_class_name` varchar(200) NOT NULL DEFAULT '',
  UNIQUE KEY `event_name` (`signal_name`,`module_class_name`),
  KEY `suppresses_plugin_class` (`suppresses_module_class_name`),
  KEY `plugin_id` (`module_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]site_settings`;
CREATE TABLE `[[DB_NAME_PREFIX]]site_settings` (
  `name` varchar(255) NOT NULL DEFAULT '',
  `value` mediumtext,
  `default_value` mediumtext,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]skins`;
CREATE TABLE `[[DB_NAME_PREFIX]]skins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `family_name` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL DEFAULT '',
  `type` enum('component','usable') NOT NULL DEFAULT 'usable',
  `extension_of_skin` varchar(255) NOT NULL DEFAULT '',
  `css_class` varchar(100) NOT NULL DEFAULT '',
  `missing` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `family_name` (`family_name`,`name`),
  KEY `name` (`name`),
  KEY `display_name` (`display_name`),
  KEY `type` (`type`),
  KEY `extension_of_skin` (`extension_of_skin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]smart_group_opt_outs`;
CREATE TABLE `[[DB_NAME_PREFIX]]smart_group_opt_outs` (
  `smart_group_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `opted_out_on` datetime NOT NULL,
  `opt_out_method` varchar(20) NOT NULL,
  PRIMARY KEY (`smart_group_id`,`user_id`),
  KEY `opted_out_on` (`opted_out_on`),
  KEY `opt_out_method` (`opt_out_method`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]smart_groups`;
CREATE TABLE `[[DB_NAME_PREFIX]]smart_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `values` mediumtext,
  `created_on` datetime DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `last_modified_on` datetime DEFAULT NULL,
  `last_modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]spare_aliases`;
CREATE TABLE `[[DB_NAME_PREFIX]]spare_aliases` (
  `alias` varchar(75) NOT NULL,
  `target_loc` enum('int','ext') NOT NULL DEFAULT 'int',
  `content_id` int(10) unsigned NOT NULL,
  `content_type` varchar(20) NOT NULL,
  `ext_url` varchar(255) NOT NULL DEFAULT '',
  `created_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`alias`),
  KEY `content_type` (`content_type`,`content_id`),
  KEY `target_loc` (`target_loc`),
  KEY `ext_url` (`ext_url`),
  KEY `created_datetime` (`created_datetime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]spare_domain_names`;
CREATE TABLE `[[DB_NAME_PREFIX]]spare_domain_names` (
  `requested_url` varchar(255) NOT NULL,
  `content_id` int(10) unsigned NOT NULL,
  `content_type` varchar(20) NOT NULL,
  PRIMARY KEY (`requested_url`),
  KEY `content_type` (`content_type`,`content_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]special_pages`;
CREATE TABLE `[[DB_NAME_PREFIX]]special_pages` (
  `equiv_id` int(10) unsigned DEFAULT NULL,
  `content_type` varchar(20) DEFAULT NULL,
  `page_type` varchar(64) NOT NULL,
  `create_lang_equiv_content` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `publish` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `module_class_name` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`page_type`),
  UNIQUE KEY `content_type` (`content_type`,`equiv_id`),
  KEY `module_class_name` (`module_class_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]template_families`;
CREATE TABLE `[[DB_NAME_PREFIX]]template_families` (
  `family_name` varchar(50) NOT NULL,
  `skin_id` int(10) unsigned DEFAULT NULL,
  `missing` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`family_name`),
  KEY `skin_id` (`skin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]template_files`;
CREATE TABLE `[[DB_NAME_PREFIX]]template_files` (
  `family_name` varchar(50) NOT NULL,
  `file_base_name` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `missing` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`family_name`,`file_base_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]template_slot_link`;
CREATE TABLE `[[DB_NAME_PREFIX]]template_slot_link` (
  `family_name` varchar(50) NOT NULL,
  `file_base_name` varchar(255) CHARACTER SET ascii NOT NULL,
  `slot_name` varchar(100) NOT NULL,
  PRIMARY KEY (`family_name`,`file_base_name`,`slot_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]translation_chains`;
CREATE TABLE `[[DB_NAME_PREFIX]]translation_chains` (
  `equiv_id` int(10) unsigned NOT NULL,
  `type` varchar(20) NOT NULL,
  `privacy` enum('public','all_extranet_users','group_members','specific_users','no_access') NOT NULL DEFAULT 'public',
  `log_user_access` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`equiv_id`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]tuix_file_contents`;
CREATE TABLE `[[DB_NAME_PREFIX]]tuix_file_contents` (
  `type` enum('admin_boxes','admin_toolbar','help','storekeeper','slot_controls') NOT NULL,
  `path` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `setting_group` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `module_class_name` varchar(200) CHARACTER SET ascii NOT NULL,
  `filename` varchar(255) CHARACTER SET ascii NOT NULL,
  `last_modified` int(10) unsigned NOT NULL DEFAULT '0',
  `checksum` varchar(32) CHARACTER SET ascii NOT NULL DEFAULT '',
  PRIMARY KEY (`type`,`path`,`setting_group`,`module_class_name`,`filename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_admin_box_tabs`;
CREATE TABLE `[[DB_NAME_PREFIX]]user_admin_box_tabs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ordinal` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(64) NOT NULL,
  `label` varchar(255) NOT NULL,
  `is_system_field` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `label` (`label`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_characteristic_values`;
CREATE TABLE `[[DB_NAME_PREFIX]]user_characteristic_values` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `characteristic_id` int(10) NOT NULL,
  `ordinal` int(10) NOT NULL,
  `name` text NOT NULL,
  `label` varchar(255) NOT NULL DEFAULT 'Label not assigned',
  `help_text` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `characteristic_id` (`characteristic_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_characteristic_values_link`;
CREATE TABLE `[[DB_NAME_PREFIX]]user_characteristic_values_link` (
  `user_id` int(10) unsigned NOT NULL,
  `user_characteristic_value_id` int(10) NOT NULL,
  PRIMARY KEY (`user_id`,`user_characteristic_value_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_characteristics`;
CREATE TABLE `[[DB_NAME_PREFIX]]user_characteristics` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL DEFAULT 'Label not assigned',
  `ordinal` int(10) NOT NULL,
  `type` enum('list_single_select','list_multi_select','boolean','date','text','textarea','group','integer','float','country','url') NOT NULL,
  `protected` tinyint(1) NOT NULL DEFAULT '0',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `help_text` varchar(255) DEFAULT NULL,
  `admin_box_tab_id` int(10) unsigned NOT NULL DEFAULT '0',
  `is_system_field` tinyint(1) NOT NULL DEFAULT '0',
  `show_in_organizer_panel` tinyint(1) NOT NULL DEFAULT '1',
  `organizer_allow_sort` tinyint(1) NOT NULL DEFAULT '0',
  `admin_box_text_field_width` int(11) NOT NULL DEFAULT '0',
  `admin_box_text_field_rows` int(11) NOT NULL DEFAULT '0',
  `admin_box_display_columns` int(11) NOT NULL DEFAULT '0',
  `user_access` enum('user_readable','user_writeable') DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_content_accesslog`;
CREATE TABLE `[[DB_NAME_PREFIX]]user_content_accesslog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hit_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` varchar(20) NOT NULL DEFAULT '',
  `content_version` int(10) unsigned NOT NULL DEFAULT '0',
  `ip` varchar(80) CHARACTER SET ascii NOT NULL DEFAULT '',
  PRIMARY KEY (`hit_datetime`,`user_id`,`content_id`,`content_type`),
  UNIQUE KEY `id` (`id`),
  KEY `user_id` (`user_id`),
  KEY `content_type` (`content_type`),
  KEY `content_id` (`content_id`),
  KEY `user_id_2` (`user_id`),
  KEY `content_id_2` (`content_id`),
  KEY `content_type_2` (`content_type`),
  KEY `new_ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_content_link`;
CREATE TABLE `[[DB_NAME_PREFIX]]user_content_link` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `equiv_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` char(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`,`content_type`,`equiv_id`),
  KEY `equiv_id` (`equiv_id`,`content_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_form_fields`;
CREATE TABLE `[[DB_NAME_PREFIX]]user_form_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_form_id` int(10) unsigned NOT NULL,
  `user_field_id` int(10) unsigned NOT NULL,
  `ordinal` int(10) unsigned NOT NULL DEFAULT '0',
  `is_readonly` tinyint(1) NOT NULL DEFAULT '0',
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_form_id` (`user_form_id`,`user_field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_forms`;
CREATE TABLE `[[DB_NAME_PREFIX]]user_forms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_signin_log`;
CREATE TABLE `[[DB_NAME_PREFIX]]user_signin_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `screen_name` varchar(50) DEFAULT '',
  `first_name` varchar(100) NOT NULL DEFAULT '',
  `last_name` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `login_datetime` datetime DEFAULT NULL,
  `ip` varchar(80) CHARACTER SET ascii NOT NULL DEFAULT '',
  `browser` varchar(255) NOT NULL DEFAULT '',
  `browser_version` varchar(255) NOT NULL DEFAULT '',
  `platform` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `screen_name` (`screen_name`),
  KEY `email` (`email`),
  KEY `login_datetime` (`login_datetime`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]users`;
CREATE TABLE `[[DB_NAME_PREFIX]]users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `ip` varchar(255) NOT NULL DEFAULT '',
  `session_id` varchar(100) NOT NULL DEFAULT '',
  `screen_name` varchar(50) DEFAULT '',
  `password` varchar(50) NOT NULL DEFAULT '',
  `password_needs_changing` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('pending','active','suspended','contact') NOT NULL DEFAULT 'pending',
  `image_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `last_profile_update_in_frontend` datetime DEFAULT NULL,
  `salutation` varchar(25) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL DEFAULT '',
  `last_name` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `email_verified` tinyint(1) NOT NULL DEFAULT '0',
  `created_date` datetime DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `suspended_date` datetime DEFAULT NULL,
  `equiv_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` varchar(20) NOT NULL DEFAULT '',
  `hash` varchar(255) DEFAULT NULL,
  `creation_method` enum('visitor','admin') DEFAULT NULL,
  `ordinal` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `parent_id` (`parent_id`),
  KEY `screen_name` (`screen_name`),
  KEY `image_id` (`image_id`),
  KEY `last_profile_update_in_frontend` (`last_profile_update_in_frontend`),
  KEY `last_login` (`last_login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]users_custom_data`;
CREATE TABLE `[[DB_NAME_PREFIX]]users_custom_data` (
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]versions`;
CREATE TABLE `[[DB_NAME_PREFIX]]versions` (
  `id` int(10) unsigned NOT NULL,
  `type` varchar(20) NOT NULL,
  `tag_id` varchar(32) NOT NULL,
  `version` int(10) unsigned NOT NULL,
  `title` varchar(250) NOT NULL DEFAULT '',
  `description` mediumtext,
  `keywords` text,
  `content_summary` mediumtext,
  `lock_summary` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `file_id` int(10) unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `sticky_image_id` int(10) unsigned NOT NULL DEFAULT '0',
  `layout_id` int(10) unsigned NOT NULL DEFAULT '0',
  `skin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `css_class` varchar(100) NOT NULL DEFAULT '',
  `head_html` mediumtext,
  `head_cc` enum('not_needed','needed','required') NOT NULL DEFAULT 'not_needed',
  `head_visitor_only` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `head_overwrite` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `foot_html` mediumtext,
  `foot_cc` enum('not_needed','needed','required') NOT NULL DEFAULT 'not_needed',
  `foot_visitor_only` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `foot_overwrite` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created_datetime` datetime DEFAULT NULL,
  `creating_author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_modified_datetime` datetime DEFAULT NULL,
  `publisher_id` int(10) unsigned NOT NULL DEFAULT '0',
  `published_datetime` datetime DEFAULT NULL,
  `concealer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `concealed_datetime` datetime DEFAULT NULL,
  `publication_date` datetime DEFAULT NULL,
  `rss_slot_name` varchar(100) NOT NULL DEFAULT '',
  `rss_nest` int(10) unsigned NOT NULL DEFAULT '0',
  `writer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `writer_name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`,`type`,`version`),
  UNIQUE KEY `tag_id` (`tag_id`,`version`),
  KEY `type` (`type`),
  KEY `version` (`version`),
  KEY `created_datetime` (`created_datetime`),
  KEY `published_datetime` (`published_datetime`),
  KEY `publication_date` (`publication_date`),
  KEY `file_id` (`file_id`),
  KEY `sticky_image_id` (`sticky_image_id`),
  KEY `filename` (`filename`),
  KEY `last_modified_datetime` (`last_modified_datetime`),
  KEY `title_2` (`title`),
  KEY `layout_id` (`layout_id`),
  FULLTEXT KEY `title` (`title`),
  FULLTEXT KEY `description` (`description`),
  FULLTEXT KEY `keywords` (`keywords`),
  FULLTEXT KEY `content_summary` (`content_summary`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]visitor_phrases`;
CREATE TABLE `[[DB_NAME_PREFIX]]visitor_phrases` (
  `id` int(1) NOT NULL AUTO_INCREMENT,
  `code` text NOT NULL,
  `language_id` varchar(15) NOT NULL DEFAULT '',
  `module_class_name` varchar(200) NOT NULL,
  `local_text` text,
  `protect_flag` tinyint(1) NOT NULL DEFAULT '0',
  `seen_in_visitor_mode` tinyint(1) NOT NULL DEFAULT '0',
  `seen_in_file` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `code` (`code`(250)),
  KEY `language_id` (`language_id`),
  KEY `module_class_name` (`module_class_name`,`language_id`,`code`(100)),
  KEY `seen_in_visitor_mode` (`seen_in_visitor_mode`),
  KEY `seen_in_file` (`seen_in_file`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

REPLACE INTO `[[DB_NAME_PREFIX]]local_revision_numbers` VALUES
 ('admin/db_updates/core','local.inc.php',[[INSTALLER_REVISION_NO]]),
 ('admin/db_updates/core','user.inc.php',[[INSTALLER_REVISION_NO]]),
 ('admin/db_updates/data_conversion','local.inc.php',[[INSTALLER_REVISION_NO]]),
 ('admin/db_updates/data_conversion','plugins.inc.php',[[INSTALLER_REVISION_NO]]),
 ('admin/db_updates/data_conversion','user.inc.php',[[INSTALLER_REVISION_NO]]),
 ('admin/db_updates/updater','local.inc.php',[[INSTALLER_REVISION_NO]]);
