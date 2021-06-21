


DROP TABLE IF EXISTS `[[DB_PREFIX]]admin_setting_defaults`;
CREATE TABLE `[[DB_PREFIX]]admin_setting_defaults` (
  `name` varchar(255) NOT NULL DEFAULT '',
  `default_value` mediumtext,
  PRIMARY KEY (`name`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]categories`;
CREATE TABLE `[[DB_PREFIX]]categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) CHARACTER SET utf8mb4 NOT NULL,
  `code_name` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `public` tinyint(1) NOT NULL DEFAULT '0',
  `landing_page_equiv_id` int(10) unsigned NOT NULL DEFAULT '0',
  `landing_page_content_type` varchar(20) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_name` (`code_name`),
  KEY `name` (`name`,`parent_id`),
  KEY `public` (`public`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]category_item_link`;
CREATE TABLE `[[DB_PREFIX]]category_item_link` (
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `equiv_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` char(20) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`category_id`,`content_type`,`equiv_id`),
  KEY `equiv_id` (`equiv_id`,`content_type`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]centralised_lists`;
CREATE TABLE `[[DB_PREFIX]]centralised_lists` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_class_name` varchar(255) NOT NULL,
  `method_name` varchar(255) NOT NULL,
  `label` varchar(250) CHARACTER SET utf8mb4 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]characteristic_user_link`;
CREATE TABLE `[[DB_PREFIX]]characteristic_user_link` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `characteristic_id` int(10) NOT NULL,
  `characteristic_value_id` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_characteristic_value` (`user_id`,`characteristic_id`,`characteristic_value_id`),
  KEY `characteristic_id` (`characteristic_id`),
  KEY `characteristic_value_id` (`characteristic_value_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]consents`;
CREATE TABLE `[[DB_PREFIX]]consents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_name` varchar(255) NOT NULL DEFAULT '',
  `source_id` varchar(255) NOT NULL DEFAULT '',
  `datetime` datetime DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `ip_address` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `email` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `first_name` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `last_name` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `label` varchar(250) CHARACTER SET utf8mb4 DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]content_cache`;
CREATE TABLE `[[DB_PREFIX]]content_cache` (
  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` varchar(20) CHARACTER SET ascii NOT NULL,
  `content_version` int(10) unsigned NOT NULL DEFAULT '0',
  `text` mediumtext CHARACTER SET utf8mb4,
  `text_wordcount` int(10) unsigned NOT NULL DEFAULT '0',
  `extract` mediumtext CHARACTER SET utf8mb4,
  `extract_wordcount` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`content_id`,`content_type`,`content_version`),
  KEY `extract_wordcount` (`extract_wordcount`),
  FULLTEXT KEY `text` (`text`),
  FULLTEXT KEY `extract` (`extract`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]content_item_versions`;
CREATE TABLE `[[DB_PREFIX]]content_item_versions` (
  `id` int(10) unsigned NOT NULL,
  `type` varchar(20) CHARACTER SET ascii NOT NULL,
  `tag_id` varchar(32) NOT NULL,
  `version` int(10) unsigned NOT NULL,
  `title` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `description` mediumtext CHARACTER SET utf8mb4,
  `keywords` text CHARACTER SET utf8mb4,
  `content_summary` mediumtext CHARACTER SET utf8mb4,
  `lock_summary` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `file_id` int(10) unsigned NOT NULL DEFAULT '0',
  `s3_file_id` int(10) unsigned NOT NULL DEFAULT '0',
  `filename` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `s3_filename` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `feature_image_id` int(10) unsigned NOT NULL DEFAULT '0',
  `layout_id` int(10) unsigned NOT NULL DEFAULT '0',
  `skin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `css_class` varchar(100) NOT NULL DEFAULT '',
  `bg_image_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bg_color` varchar(64) CHARACTER SET ascii NOT NULL DEFAULT '',
  `bg_position` enum('left top','center top','right top','left center','center center','right center','left bottom','center bottom','right bottom') DEFAULT NULL,
  `bg_repeat` enum('repeat','repeat-x','repeat-y','no-repeat') DEFAULT NULL,
  `head_html` mediumtext CHARACTER SET utf8mb4,
  `head_cc` enum('not_needed','needed','required') NOT NULL DEFAULT 'not_needed',
  `head_visitor_only` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `head_overwrite` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `foot_html` mediumtext CHARACTER SET utf8mb4,
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
  `release_date` datetime DEFAULT NULL,
  `rss_slot_name` varchar(100) CHARACTER SET ascii NOT NULL DEFAULT '',
  `rss_nest` int(10) unsigned NOT NULL DEFAULT '0',
  `writer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `writer_name` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `scheduled_publish_datetime` datetime DEFAULT NULL,
  `in_sitemap` tinyint(1) NOT NULL DEFAULT '1',
  `sensitive_content_message` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`type`,`version`),
  UNIQUE KEY `tag_id` (`tag_id`,`version`),
  KEY `type` (`type`),
  KEY `version` (`version`),
  KEY `created_datetime` (`created_datetime`),
  KEY `published_datetime` (`published_datetime`),
  KEY `publication_date` (`release_date`),
  KEY `file_id` (`file_id`),
  KEY `filename` (`filename`),
  KEY `last_modified_datetime` (`last_modified_datetime`),
  KEY `title_2` (`title`),
  KEY `layout_id` (`layout_id`),
  KEY `feature_image_id` (`feature_image_id`),
  KEY `in_sitemap` (`in_sitemap`),
  FULLTEXT KEY `title` (`title`),
  FULLTEXT KEY `description` (`description`),
  FULLTEXT KEY `keywords` (`keywords`),
  FULLTEXT KEY `content_summary` (`content_summary`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]content_items`;
CREATE TABLE `[[DB_PREFIX]]content_items` (
  `id` int(10) unsigned NOT NULL,
  `type` varchar(20) CHARACTER SET ascii NOT NULL,
  `tag_id` varchar(32) CHARACTER SET ascii NOT NULL,
  `equiv_id` int(10) unsigned NOT NULL,
  `language_id` varchar(15) NOT NULL,
  `alias` varchar(75) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
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
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]content_types`;
CREATE TABLE `[[DB_PREFIX]]content_types` (
  `content_type_id` varchar(20) CHARACTER SET ascii NOT NULL,
  `content_type_name_en` varchar(255) NOT NULL DEFAULT '',
  `content_type_plural_en` varchar(255) NOT NULL DEFAULT '',
  `writer_field` enum('optional','mandatory','hidden') NOT NULL DEFAULT 'optional',
  `description_field` enum('optional','mandatory','hidden') NOT NULL DEFAULT 'optional',
  `tooltip_text` varchar(255) NOT NULL DEFAULT '',
  `keywords_field` enum('optional','mandatory','hidden') NOT NULL DEFAULT 'optional',
  `summary_field` enum('optional','mandatory','hidden') NOT NULL DEFAULT 'optional',
  `release_date_field` enum('optional','mandatory','hidden') NOT NULL DEFAULT 'optional',
  `auto_flag_feature_image` tinyint(1) NOT NULL DEFAULT '1',
  `enable_summary_auto_update` tinyint(1) NOT NULL DEFAULT '0',
  `enable_categories` tinyint(1) NOT NULL DEFAULT '0',
  `is_creatable` tinyint(1) NOT NULL DEFAULT '1',
  `default_layout_id` int(10) unsigned NOT NULL DEFAULT '0',
  `module_id` int(10) unsigned NOT NULL,
  `prompt_to_create_a_menu_node` tinyint(1) NOT NULL DEFAULT '1',
  `menu_node_position_edit` enum('force','suggest') NOT NULL,
  `hide_menu_node` tinyint(1) NOT NULL DEFAULT '0',
  `default_permissions` enum('public','logged_in') NOT NULL DEFAULT 'public',
  `hide_private_item` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`content_type_id`),
  KEY `content_type_id` (`content_type_id`),
  KEY `plugin_id` (`module_id`),
  KEY `default_layout_id` (`default_layout_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]custom_dataset_field_values`;
CREATE TABLE `[[DB_PREFIX]]custom_dataset_field_values` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `field_id` int(10) NOT NULL,
  `ord` int(10) NOT NULL DEFAULT '0',
  `label` varchar(250) CHARACTER SET utf8mb4 NOT NULL,
  `note_below` text CHARACTER SET utf8mb4,
  PRIMARY KEY (`id`),
  UNIQUE KEY `field_id` (`field_id`,`id`),
  KEY `field_id_2` (`field_id`,`ord`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]custom_dataset_fields`;
CREATE TABLE `[[DB_PREFIX]]custom_dataset_fields` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `dataset_id` int(10) unsigned NOT NULL,
  `tab_name` varchar(64) CHARACTER SET ascii NOT NULL,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `is_system_field` tinyint(1) NOT NULL DEFAULT '0',
  `protected` tinyint(1) NOT NULL DEFAULT '0',
  `fundamental` tinyint(1) NOT NULL DEFAULT '0',
  `field_name` varchar(64) CHARACTER SET ascii NOT NULL DEFAULT '',
  `ord` int(10) unsigned NOT NULL DEFAULT '0',
  `label` varchar(64) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `default_label` varchar(64) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `type` enum('group','checkbox','consent','checkboxes','date','editor','radios','centralised_radios','select','centralised_select','text','textarea','url','other_system_field','dataset_select','dataset_picker','file_picker','repeat_start','repeat_end') NOT NULL DEFAULT 'other_system_field',
  `width` smallint(5) unsigned NOT NULL DEFAULT '0',
  `height` smallint(5) unsigned NOT NULL DEFAULT '0',
  `values_source` varchar(255) NOT NULL DEFAULT '',
  `values_source_filter` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `dataset_foreign_key_id` int(10) unsigned NOT NULL DEFAULT '0',
  `multiple_select` tinyint(1) NOT NULL DEFAULT '0',
  `store_file` enum('in_docstore','in_database') DEFAULT NULL,
  `extensions` varchar(255) NOT NULL DEFAULT '',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `required_message` text CHARACTER SET utf8mb4,
  `validation` enum('none','email','emails','no_spaces','numeric','screen_name') NOT NULL DEFAULT 'none',
  `validation_message` text CHARACTER SET utf8mb4,
  `note_below` text CHARACTER SET utf8mb4,
  `side_note` text CHARACTER SET utf8mb4,
  `db_column` varchar(255) NOT NULL DEFAULT '',
  `db_update_running` tinyint(1) NOT NULL DEFAULT '0',
  `admin_box_visibility` enum('show','show_on_condition','hide') NOT NULL DEFAULT 'show',
  `organizer_visibility` enum('none','hide','show_by_default','always_show') NOT NULL DEFAULT 'none',
  `create_index` tinyint(1) NOT NULL DEFAULT '0',
  `searchable` tinyint(1) NOT NULL DEFAULT '0',
  `filterable` tinyint(1) NOT NULL DEFAULT '0',
  `sortable` tinyint(1) NOT NULL DEFAULT '0',
  `readonly` tinyint(1) NOT NULL DEFAULT '0',
  `include_in_export` tinyint(1) NOT NULL DEFAULT '0',
  `autocomplete` tinyint(1) NOT NULL DEFAULT '0',
  `indent` int(10) unsigned NOT NULL DEFAULT '0',
  `allow_admin_to_change_visibility` tinyint(1) NOT NULL DEFAULT '0',
  `allow_admin_to_change_export` tinyint(1) NOT NULL DEFAULT '0',
  `min_rows` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `max_rows` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `repeat_start_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `dataset_id` (`dataset_id`,`tab_name`,`id`),
  KEY `dataset_id_2` (`dataset_id`,`tab_name`,`ord`),
  KEY `is_system_field` (`is_system_field`),
  KEY `parent_id` (`parent_id`),
  KEY `field_name` (`field_name`),
  KEY `protected` (`protected`),
  KEY `organizer_visibility` (`organizer_visibility`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]custom_dataset_files_link`;
CREATE TABLE `[[DB_PREFIX]]custom_dataset_files_link` (
  `dataset_id` int(10) NOT NULL,
  `field_id` int(10) NOT NULL,
  `linking_id` int(10) NOT NULL,
  `file_id` int(10) NOT NULL,
  PRIMARY KEY (`field_id`,`linking_id`,`file_id`),
  KEY `dataset_id` (`dataset_id`),
  KEY `linking_id` (`linking_id`),
  KEY `file_id` (`file_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]custom_dataset_tabs`;
CREATE TABLE `[[DB_PREFIX]]custom_dataset_tabs` (
  `dataset_id` int(10) unsigned NOT NULL,
  `name` varchar(64) CHARACTER SET ascii NOT NULL,
  `ord` int(10) unsigned NOT NULL DEFAULT '0',
  `label` varchar(32) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `default_label` varchar(255) NOT NULL DEFAULT '',
  `parent_field_id` int(10) NOT NULL DEFAULT '0',
  `is_system_field` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`dataset_id`,`name`),
  KEY `ord` (`ord`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]custom_dataset_values_link`;
CREATE TABLE `[[DB_PREFIX]]custom_dataset_values_link` (
  `dataset_id` int(10) NOT NULL,
  `value_id` int(10) NOT NULL,
  `linking_id` int(10) NOT NULL,
  PRIMARY KEY (`value_id`,`linking_id`),
  UNIQUE KEY `linking_id` (`linking_id`,`value_id`),
  KEY `dataset_id` (`dataset_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]custom_datasets`;
CREATE TABLE `[[DB_PREFIX]]custom_datasets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(64) CHARACTER SET utf8mb4 NOT NULL,
  `system_table` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `table` varchar(255) CHARACTER SET ascii NOT NULL,
  `extends_admin_box` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `extends_organizer_panel` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `view_priv` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `edit_priv` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `label_field_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `table` (`table`),
  KEY `label` (`label`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]document_public_redirects`;
CREATE TABLE `[[DB_PREFIX]]document_public_redirects` (
  `document_id` int(10) unsigned NOT NULL,
  `file_id` int(10) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  PRIMARY KEY (`document_id`,`path`(10))
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `[[DB_PREFIX]]document_rules`;
CREATE TABLE `[[DB_PREFIX]]document_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ordinal` int(10) unsigned NOT NULL DEFAULT '0',
  `use` enum('filename_without_extension','filename_and_extension','extension') NOT NULL DEFAULT 'filename_without_extension',
  `pattern` mediumtext CHARACTER SET utf8mb4,
  `action` enum('move_to_folder','set_field') NOT NULL,
  `folder_id` int(10) unsigned NOT NULL DEFAULT '0',
  `field_id` int(10) unsigned NOT NULL DEFAULT '0',
  `replacement` mediumtext CHARACTER SET utf8mb4,
  `replacement_is_regexp` tinyint(1) unsigned NOT NULL,
  `apply_second_pass` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `second_pattern` mediumtext CHARACTER SET utf8mb4,
  `second_replacement` mediumtext CHARACTER SET utf8mb4,
  `stop_processing_rules` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `ordinal` (`ordinal`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]document_tag_link`;
CREATE TABLE `[[DB_PREFIX]]document_tag_link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` int(10) NOT NULL,
  `tag_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_tag_link` (`document_id`,`tag_id`),
  KEY `type` (`document_id`),
  KEY `folder_id` (`tag_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]document_tags`;
CREATE TABLE `[[DB_PREFIX]]document_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(250) CHARACTER SET utf8mb4 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_name` (`tag_name`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]document_types`;
CREATE TABLE `[[DB_PREFIX]]document_types` (
  `type` varchar(10) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `mime_type` varchar(128) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `custom` tinyint(1) NOT NULL DEFAULT '1',
  `is_allowed` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`type`),
  KEY `custom` (`custom`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]documents`;
CREATE TABLE `[[DB_PREFIX]]documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ordinal` int(10) NOT NULL,
  `type` enum('file','folder') NOT NULL DEFAULT 'file',
  `file_id` int(10) unsigned DEFAULT NULL,
  `folder_id` int(10) NOT NULL DEFAULT '0',
  `folder_name` varchar(250) CHARACTER SET utf8mb4 DEFAULT NULL,
  `privacy` enum('public','private','offline') NOT NULL DEFAULT 'offline',
  `document_datetime` datetime DEFAULT NULL,
  `thumbnail_id` int(10) DEFAULT NULL,
  `extract` mediumtext CHARACTER SET utf8mb4,
  `extract_wordcount` int(10) unsigned NOT NULL DEFAULT '0',
  `dont_autoset_metadata` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `filename` varchar(250) CHARACTER SET utf8mb4 DEFAULT NULL,
  `file_datetime` datetime DEFAULT NULL,
  `title` varchar(250) CHARACTER SET utf8mb4 DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ordinal` (`ordinal`),
  KEY `type` (`type`),
  KEY `folder_id` (`folder_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]documents_custom_data`;
CREATE TABLE `[[DB_PREFIX]]documents_custom_data` (
  `document_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`document_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]email_templates`;
CREATE TABLE `[[DB_PREFIX]]email_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_class_name` varchar(250) CHARACTER SET utf8mb4 DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `template_name` varchar(255) NOT NULL DEFAULT '',
  `subject` varchar(250) CHARACTER SET utf8mb4 NOT NULL,
  `from_details` enum('site_settings','custom_details') NOT NULL DEFAULT 'site_settings',
  `email_address_from` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `email_name_from` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `head` mediumtext CHARACTER SET utf8mb4,
  `body` text CHARACTER SET utf8mb4,
  `send_cc` tinyint(1) NOT NULL DEFAULT '0',
  `cc_email_address` text CHARACTER SET utf8mb4,
  `send_bcc` tinyint(1) NOT NULL DEFAULT '0',
  `bcc_email_address` text CHARACTER SET utf8mb4,
  `debug_override` tinyint(1) NOT NULL DEFAULT '0',
  `debug_email_address` text CHARACTER SET utf8mb4,
  `date_created` datetime NOT NULL,
  `created_by_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date_modified` datetime DEFAULT NULL,
  `modified_by_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_sent` datetime DEFAULT NULL,
  `allow_attachments` tinyint(1) DEFAULT '0',
  `use_standard_email_template` tinyint(1) NOT NULL DEFAULT '0',
  `period_to_delete_log_headers` varchar(255) NOT NULL DEFAULT '',
  `period_to_delete_log_content` varchar(255) NOT NULL DEFAULT '',
  `include_a_fixed_attachment` tinyint(1) NOT NULL DEFAULT '0',
  `selected_attachment` int(10) unsigned DEFAULT NULL,
  `allow_visitor_uploaded_attachments` tinyint(1) NOT NULL DEFAULT '0',
  `when_sending_attachments` enum('send_organizer_link','send_actual_file') DEFAULT 'send_organizer_link',
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_name` (`template_name`),
  UNIQUE KEY `code` (`code`),
  KEY `date_modified` (`date_modified`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]files`;
CREATE TABLE `[[DB_PREFIX]]files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `checksum` varchar(32) CHARACTER SET ascii NOT NULL,
  `short_checksum` varchar(24) CHARACTER SET ascii DEFAULT NULL,
  `usage` varchar(64) CHARACTER SET ascii NOT NULL,
  `privacy` enum('auto','public','private') NOT NULL DEFAULT 'auto',
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  `created_datetime` datetime DEFAULT NULL,
  `filename` varchar(250) CHARACTER SET utf8mb4 NOT NULL,
  `mime_type` varchar(128) CHARACTER SET utf8mb4 NOT NULL,
  `width` smallint(5) unsigned NOT NULL DEFAULT '0',
  `height` smallint(5) unsigned NOT NULL DEFAULT '0',
  `alt_tag` text CHARACTER SET utf8mb4,
  `title` text CHARACTER SET utf8mb4,
  `floating_box_title` text CHARACTER SET utf8mb4,
  `size` int(10) unsigned NOT NULL,
  `location` enum('db','docstore','s3') NOT NULL,
  `data` longblob,
  `path` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `thumbnail_180x130_width` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `thumbnail_180x130_height` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `thumbnail_180x130_data` mediumblob,
  `custom_thumbnail_1_width` smallint(5) unsigned DEFAULT NULL,
  `custom_thumbnail_1_height` smallint(5) unsigned DEFAULT NULL,
  `custom_thumbnail_1_data` mediumblob,
  `custom_thumbnail_2_width` smallint(5) unsigned DEFAULT NULL,
  `custom_thumbnail_2_height` smallint(5) unsigned DEFAULT NULL,
  `custom_thumbnail_2_data` mediumblob,
  PRIMARY KEY (`id`),
  UNIQUE KEY `checksum` (`checksum`,`usage`),
  UNIQUE KEY `short_checksum` (`short_checksum`,`usage`),
  KEY `usage` (`usage`),
  KEY `created_datetime` (`created_datetime`),
  KEY `filename` (`filename`),
  KEY `mime_type` (`mime_type`),
  KEY `location` (`location`),
  KEY `width` (`width`),
  KEY `height` (`height`),
  KEY `archived` (`archived`),
  KEY `thumbnail_180x130_width` (`thumbnail_180x130_width`),
  KEY `thumbnail_180x130_height` (`thumbnail_180x130_height`),
  KEY `custom_thumbnail_1_width` (`custom_thumbnail_1_width`),
  KEY `custom_thumbnail_1_height` (`custom_thumbnail_1_height`),
  KEY `custom_thumbnail_2_width` (`custom_thumbnail_2_width`),
  KEY `custom_thumbnail_2_height` (`custom_thumbnail_2_height`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]group_link`;
CREATE TABLE `[[DB_PREFIX]]group_link` (
  `link_from` enum('chain','slide','slide_layout') NOT NULL,
  `link_from_id` int(10) unsigned NOT NULL,
  `link_from_char` char(20) CHARACTER SET ascii NOT NULL DEFAULT '',
  `link_to` enum('group','role') NOT NULL,
  `link_to_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`link_from`,`link_from_id`,`link_from_char`,`link_to`,`link_to_id`),
  KEY `link_to` (`link_to`,`link_to_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]image_tag_link`;
CREATE TABLE `[[DB_PREFIX]]image_tag_link` (
  `image_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`image_id`,`tag_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]image_tags`;
CREATE TABLE `[[DB_PREFIX]]image_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL,
  `color` enum('blue','red','green','orange','yellow','violet','grey') NOT NULL DEFAULT 'blue',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `color` (`color`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]inline_images`;
CREATE TABLE `[[DB_PREFIX]]inline_images` (
  `image_id` int(10) unsigned NOT NULL,
  `foreign_key_to` varchar(64) NOT NULL,
  `foreign_key_id` int(10) unsigned NOT NULL DEFAULT '0',
  `foreign_key_char` varchar(255) NOT NULL DEFAULT '',
  `foreign_key_version` int(10) unsigned NOT NULL DEFAULT '0',
  `in_use` tinyint(1) NOT NULL DEFAULT '0',
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  `is_nest` tinyint(1) NOT NULL DEFAULT '0',
  `is_slideshow` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`foreign_key_to`,`foreign_key_id`,`foreign_key_char`,`foreign_key_version`,`image_id`),
  KEY `file_id` (`image_id`),
  KEY `in_use` (`in_use`),
  KEY `archived` (`archived`),
  KEY `is_nest` (`is_nest`),
  KEY `is_slideshow` (`is_slideshow`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]job_logs`;
CREATE TABLE `[[DB_PREFIX]]job_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `status` enum('action_taken','no_action_taken','error') NOT NULL,
  `started` datetime DEFAULT NULL,
  `finished` datetime DEFAULT NULL,
  `note` mediumtext CHARACTER SET utf8mb4,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `status` (`status`),
  KEY `started` (`started`),
  KEY `finished` (`finished`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]jobs`;
CREATE TABLE `[[DB_PREFIX]]jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_type` enum('scheduled','background') NOT NULL DEFAULT 'scheduled',
  `manager_class_name` varchar(200) NOT NULL,
  `job_name` varchar(127) NOT NULL,
  `module_id` int(10) unsigned NOT NULL,
  `module_class_name` varchar(200) NOT NULL,
  `static_method` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `script_path` varchar(255) NOT NULL DEFAULT '',
  `script_restart_time` bigint(14) unsigned NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `paused` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `months` set('jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec') NOT NULL DEFAULT 'jan,feb,mar,apr,may,jun,jul,aug,sep,oct,nov,dec',
  `days` set('mon','tue','wed','thr','fri','sat','sun') NOT NULL DEFAULT 'mon,tue,wed,thr,fri,sat,sun',
  `hours` set('0h','1h','2h','3h','4h','5h','6h','7h','8h','9h','10h','11h','12h','13h','14h','15h','16h','17h','18h','19h','20h','21h','22h','23h') NOT NULL DEFAULT '0h',
  `minutes` set('0m','5m','10m','15m','20m','25m','30m','35m','40m','45m','50m','55m','59m') NOT NULL DEFAULT '0m',
  `run_every_minute` tinyint(1) NOT NULL DEFAULT '0',
  `first_n_days_of_month` tinyint(1) NOT NULL DEFAULT '0',
  `log_on_action` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `log_on_no_action` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `email_on_action` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `email_on_no_action` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `email_address_on_action` varchar(200) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `email_address_on_no_action` varchar(200) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `email_address_on_error` varchar(200) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `last_run_started` datetime DEFAULT NULL,
  `last_run_finished` datetime DEFAULT NULL,
  `status` enum('never_run','rerun_scheduled','in_progress','action_taken','no_action_taken','error') NOT NULL DEFAULT 'never_run',
  `last_successful_run` datetime DEFAULT NULL,
  `last_action` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_name` (`job_name`,`module_class_name`),
  KEY `plugin_id` (`module_id`),
  KEY `first_n_days_of_month` (`first_n_days_of_month`),
  KEY `manager_class_name` (`manager_class_name`),
  KEY `job_type` (`job_type`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]languages`;
CREATE TABLE `[[DB_PREFIX]]languages` (
  `id` varchar(15) NOT NULL DEFAULT '',
  `detect` tinyint(1) NOT NULL DEFAULT '0',
  `detect_lang_codes` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `translate_phrases` tinyint(1) NOT NULL DEFAULT '1',
  `show_untranslated_content_items` tinyint(1) NOT NULL DEFAULT '0',
  `sync_assist` tinyint(1) NOT NULL DEFAULT '0',
  `search_type` enum('full_text','simple') NOT NULL DEFAULT 'full_text',
  `language_picker_logic` enum('visible_or_disabled','visible_or_hidden','always_hidden') NOT NULL DEFAULT 'visible_or_disabled',
  `thousands_sep` varchar(1) CHARACTER SET utf8mb4 NOT NULL DEFAULT ',',
  `dec_point` varchar(1) CHARACTER SET utf8mb4 NOT NULL DEFAULT '.',
  `domain` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `detect` (`detect`),
  KEY `sync_assist` (`sync_assist`),
  KEY `translate_phrases` (`translate_phrases`),
  KEY `domain` (`domain`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]last_sent_warning_emails`;
CREATE TABLE `[[DB_PREFIX]]last_sent_warning_emails` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `warning_code` enum('document_container__private_file_in_public_folder','module_missing') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]layout_slot_link`;
CREATE TABLE `[[DB_PREFIX]]layout_slot_link` (
  `layout_id` int(10) unsigned NOT NULL,
  `slot_name` varchar(100) NOT NULL,
  `ord` smallint(4) unsigned NOT NULL DEFAULT '0',
  `cols` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `small_screens` enum('show','hide','only','first') DEFAULT 'show',
  PRIMARY KEY (`layout_id`,`slot_name`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]layouts`;
CREATE TABLE `[[DB_PREFIX]]layouts` (
  `layout_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `content_type` varchar(20) CHARACTER SET ascii NOT NULL,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `cols` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `min_width` smallint(4) unsigned NOT NULL DEFAULT '0',
  `max_width` smallint(4) unsigned NOT NULL DEFAULT '0',
  `fluid` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `responsive` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `skin_id` int(10) unsigned DEFAULT NULL,
  `css_class` varchar(100) NOT NULL DEFAULT '',
  `bg_image_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bg_color` varchar(64) CHARACTER SET ascii NOT NULL DEFAULT '',
  `bg_position` enum('left top','center top','right top','left center','center center','right center','left bottom','center bottom','right bottom') DEFAULT NULL,
  `bg_repeat` enum('repeat','repeat-x','repeat-y','no-repeat') DEFAULT NULL,
  `json_data` json DEFAULT NULL,
  `json_data_hash` varchar(8) CHARACTER SET ascii NOT NULL DEFAULT 'xxxxxxxx',
  `head_html` mediumtext CHARACTER SET utf8mb4,
  `head_cc` enum('not_needed','needed','required') NOT NULL DEFAULT 'not_needed',
  `head_visitor_only` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `foot_html` mediumtext CHARACTER SET utf8mb4,
  `foot_cc` enum('not_needed','needed','required') NOT NULL DEFAULT 'not_needed',
  `foot_visitor_only` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `sensitive_content_message` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`layout_id`),
  KEY `name` (`name`),
  KEY `content_type` (`content_type`),
  KEY `status` (`status`),
  KEY `skin_id` (`skin_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]local_revision_numbers`;
CREATE TABLE `[[DB_PREFIX]]local_revision_numbers` (
  `path` varchar(255) NOT NULL,
  `patchfile` varchar(64) NOT NULL,
  `revision_no` int(10) unsigned NOT NULL,
  PRIMARY KEY (`path`,`patchfile`),
  KEY `revision_no` (`revision_no`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]lock__clean_dirs`;
CREATE TABLE `[[DB_PREFIX]]lock__clean_dirs` (
  `dummy` tinyint(1) unsigned NOT NULL
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=ascii;


DROP TABLE IF EXISTS `[[DB_PREFIX]]lov_salutations`;
CREATE TABLE `[[DB_PREFIX]]lov_salutations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]menu_hierarchy`;
CREATE TABLE `[[DB_PREFIX]]menu_hierarchy` (
  `section_id` smallint(10) unsigned NOT NULL,
  `ancestor_id` int(10) unsigned NOT NULL,
  `child_id` int(10) unsigned NOT NULL,
  `separation` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`ancestor_id`,`child_id`),
  KEY `child_id` (`child_id`),
  KEY `separation` (`separation`),
  KEY `section_id` (`section_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]menu_nodes`;
CREATE TABLE `[[DB_PREFIX]]menu_nodes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` smallint(10) unsigned NOT NULL,
  `redundancy` enum('primary','secondary') NOT NULL DEFAULT 'primary',
  `accesskey` char(1) NOT NULL DEFAULT '',
  `target_loc` enum('int','ext','none') NOT NULL DEFAULT 'none',
  `open_in_new_window` tinyint(1) NOT NULL DEFAULT '0',
  `equiv_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` varchar(20) CHARACTER SET ascii NOT NULL DEFAULT '',
  `use_download_page` tinyint(1) NOT NULL DEFAULT '0',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `ordinal` int(10) unsigned NOT NULL DEFAULT '0',
  `invisible` tinyint(1) NOT NULL DEFAULT '0',
  `hide_private_item` tinyint(1) NOT NULL DEFAULT '0',
  `restrict_child_content_types` varchar(20) CHARACTER SET ascii DEFAULT NULL,
  `add_registered_get_requests` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `rel_tag` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
  `css_class` varchar(100) NOT NULL DEFAULT '',
  `image_id` int(10) unsigned NOT NULL DEFAULT '0',
  `rollover_image_id` int(10) unsigned NOT NULL DEFAULT '0',
  `anchor` varchar(255) DEFAULT NULL,
  `module_class_name` varchar(255) DEFAULT NULL,
  `method_name` varchar(255) DEFAULT NULL,
  `param_1` varchar(255) DEFAULT NULL,
  `param_2` varchar(255) DEFAULT NULL,
  `custom_get_requests` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ordinal` (`ordinal`),
  KEY `content_type` (`content_type`),
  KEY `parent_id` (`parent_id`),
  KEY `content_id` (`equiv_id`),
  KEY `target_loc` (`target_loc`),
  KEY `invisible` (`invisible`),
  KEY `section_id` (`section_id`),
  KEY `restrict_child_content_types` (`restrict_child_content_types`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]menu_positions`;
CREATE TABLE `[[DB_PREFIX]]menu_positions` (
  `tag` char(18) NOT NULL,
  `section_id` smallint(10) unsigned NOT NULL,
  `menu_id` int(10) unsigned NOT NULL DEFAULT '0',
  `is_dummy_child` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `parent_tag` char(18) NOT NULL,
  PRIMARY KEY (`tag`),
  UNIQUE KEY `section_id` (`section_id`,`menu_id`,`is_dummy_child`),
  KEY `parent_tag` (`parent_tag`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=ascii;


DROP TABLE IF EXISTS `[[DB_PREFIX]]menu_sections`;
CREATE TABLE `[[DB_PREFIX]]menu_sections` (
  `id` smallint(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_name` varchar(20) CHARACTER SET utf8mb4 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_name` (`section_name`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]menu_text`;
CREATE TABLE `[[DB_PREFIX]]menu_text` (
  `menu_id` int(10) unsigned NOT NULL,
  `language_id` varchar(10) NOT NULL DEFAULT 'en',
  `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `ext_url` varchar(255) NOT NULL DEFAULT '',
  `descriptive_text` mediumtext CHARACTER SET utf8mb4,
  PRIMARY KEY (`menu_id`,`language_id`),
  KEY `language_id` (`language_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]module_dependencies`;
CREATE TABLE `[[DB_PREFIX]]module_dependencies` (
  `module_id` int(10) unsigned NOT NULL,
  `module_class_name` varchar(200) NOT NULL,
  `dependency_class_name` varchar(200) NOT NULL,
  `type` enum('dependency','inherit_frameworks','include_javascript','inherit_settings') NOT NULL,
  UNIQUE KEY `module_id` (`module_id`,`type`,`dependency_class_name`),
  KEY `type` (`type`),
  KEY `module_class_name` (`module_class_name`,`type`),
  KEY `dependency_class_name` (`dependency_class_name`,`type`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]modules`;
CREATE TABLE `[[DB_PREFIX]]modules` (
  `id` int(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_name` varchar(200) NOT NULL,
  `vlp_class` varchar(200) NOT NULL DEFAULT '',
  `display_name` varchar(250) CHARACTER SET utf8mb4 NOT NULL,
  `edition` enum('Other','Community','Pro','ProBusiness','Enterprise') DEFAULT 'Other',
  `category` enum('custom','core','content_type','management','pluggable') DEFAULT NULL,
  `default_framework` varchar(50) NOT NULL DEFAULT '',
  `css_class_name` varchar(200) NOT NULL DEFAULT '',
  `is_pluggable` tinyint(1) NOT NULL DEFAULT '0',
  `must_be_on` enum('','public_page','private_page') NOT NULL DEFAULT '',
  `fill_organizer_nav` tinyint(1) NOT NULL DEFAULT '0',
  `can_be_version_controlled` tinyint(1) NOT NULL DEFAULT '0',
  `for_use_in_twig` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `nestable` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('module_not_initialized','module_running','module_suspended','module_is_abstract') NOT NULL DEFAULT 'module_not_initialized',
  `missing` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_name` (`class_name`),
  KEY `uses_wireframes` (`can_be_version_controlled`),
  KEY `fill_organizer_nav` (`fill_organizer_nav`),
  KEY `edition` (`edition`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]nested_paths`;
CREATE TABLE `[[DB_PREFIX]]nested_paths` (
  `instance_id` int(10) unsigned NOT NULL,
  `slide_num` smallint(4) unsigned NOT NULL DEFAULT '0',
  `from_state` char(2) CHARACTER SET ascii NOT NULL,
  `to_state` char(2) CHARACTER SET ascii NOT NULL,
  `equiv_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` varchar(20) CHARACTER SET ascii NOT NULL DEFAULT '',
  `command` varchar(255) CHARACTER SET ascii NOT NULL,
  `is_custom` tinyint(1) NOT NULL DEFAULT '0',
  `request_vars` varchar(250) CHARACTER SET ascii NOT NULL DEFAULT '',
  `hierarchical_var` varchar(32) CHARACTER SET ascii NOT NULL DEFAULT '',
  `descendants` set('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','aa','ab','ac','ad','ae','af','ag','ah','ai','aj','ak','al','am','an','ao','ap','aq','ar','as','at','au','av','aw','ax','ay','az','ba','bb','bc','bd','be','bf','bg','bh','bi','bj','bk','bl') NOT NULL DEFAULT '',
  `is_forwards` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`instance_id`,`from_state`,`equiv_id`,`content_type`,`to_state`),
  KEY `instance_id` (`instance_id`,`to_state`,`from_state`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]nested_plugins`;
CREATE TABLE `[[DB_PREFIX]]nested_plugins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `instance_id` int(10) unsigned NOT NULL,
  `slide_num` smallint(4) unsigned NOT NULL DEFAULT '1',
  `ord` smallint(4) unsigned NOT NULL DEFAULT '1',
  `cols` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0 means full-width, -1 means grouped with the previous plugin',
  `small_screens` enum('show','hide','only') DEFAULT 'show',
  `module_id` int(10) unsigned NOT NULL,
  `framework` varchar(50) NOT NULL DEFAULT '',
  `css_class` varchar(100) NOT NULL DEFAULT '',
  `makes_breadcrumbs` tinyint(1) NOT NULL DEFAULT '0',
  `is_slide` tinyint(1) NOT NULL DEFAULT '0',
  `use_slide_layout` enum('','asset_schema','datapool_schema') NOT NULL DEFAULT '',
  `invisible_in_nav` tinyint(1) NOT NULL DEFAULT '0',
  `show_back` tinyint(1) NOT NULL DEFAULT '0',
  `no_choice_no_going_back` tinyint(1) NOT NULL DEFAULT '0',
  `show_embed` tinyint(1) NOT NULL DEFAULT '0',
  `show_refresh` tinyint(1) NOT NULL DEFAULT '0',
  `show_auto_refresh` tinyint(1) NOT NULL DEFAULT '0',
  `auto_refresh_interval` int(10) unsigned NOT NULL DEFAULT '60',
  `request_vars` varchar(250) CHARACTER SET ascii NOT NULL DEFAULT '',
  `hierarchical_var` varchar(32) CHARACTER SET ascii NOT NULL DEFAULT '',
  `global_command` varchar(100) NOT NULL DEFAULT '',
  `states` set('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','aa','ab','ac','ad','ae','af','ag','ah','ai','aj','ak','al','am','an','ao','ap','aq','ar','as','at','au','av','aw','ax','ay','az','ba','bb','bc','bd','be','bf','bg','bh','bi','bj','bk','bl') NOT NULL DEFAULT '',
  `name_or_title` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `privacy` enum('public','logged_out','logged_in','group_members','in_smart_group','logged_in_not_in_smart_group','call_static_method','with_role','hidden') NOT NULL DEFAULT 'public',
  `at_location` enum('any','in_url','detect') NOT NULL DEFAULT 'any',
  `smart_group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `module_class_name` varchar(200) NOT NULL DEFAULT '',
  `method_name` varchar(127) NOT NULL DEFAULT '',
  `param_1` varchar(200) NOT NULL DEFAULT '',
  `param_2` varchar(200) NOT NULL DEFAULT '',
  `always_visible_to_admins` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `instance_id` (`instance_id`,`is_slide`,`slide_num`,`ord`),
  KEY `slide_num` (`instance_id`,`slide_num`,`ord`),
  KEY `makes_breadcrumbs` (`instance_id`,`makes_breadcrumbs`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]page_preview_sizes`;
CREATE TABLE `[[DB_PREFIX]]page_preview_sizes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `width` int(10) unsigned NOT NULL,
  `height` int(10) unsigned NOT NULL,
  `description` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `is_default` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ordinal` int(10) unsigned NOT NULL,
  `type` enum('desktop','laptop','tablet','tablet_landscape','smartphone') NOT NULL DEFAULT 'desktop',
  PRIMARY KEY (`id`),
  KEY `ordinal` (`ordinal`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]plugin_instance_store`;
CREATE TABLE `[[DB_PREFIX]]plugin_instance_store` (
  `instance_id` int(10) unsigned NOT NULL,
  `method_name` varchar(64) NOT NULL,
  `request` varchar(255) NOT NULL DEFAULT '',
  `last_updated` datetime NOT NULL,
  `store` mediumtext CHARACTER SET utf8mb4 NOT NULL,
  `is_cache` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`instance_id`,`method_name`,`request`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]plugin_instances`;
CREATE TABLE `[[DB_PREFIX]]plugin_instances` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `module_id` int(10) unsigned NOT NULL,
  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` varchar(20) CHARACTER SET ascii NOT NULL DEFAULT '',
  `content_version` int(10) unsigned NOT NULL DEFAULT '0',
  `slot_name` varchar(100) CHARACTER SET ascii NOT NULL DEFAULT '',
  `framework` varchar(50) NOT NULL DEFAULT '',
  `css_class` varchar(100) NOT NULL DEFAULT '',
  `is_nest` tinyint(1) NOT NULL DEFAULT '0',
  `is_slideshow` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `wireframe_instance` (`content_id`,`content_type`,`content_version`,`slot_name`,`module_id`),
  KEY `module_id` (`module_id`),
  KEY `is_nest` (`is_nest`),
  KEY `is_slideshow` (`is_slideshow`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]plugin_item_link`;
CREATE TABLE `[[DB_PREFIX]]plugin_item_link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(10) unsigned NOT NULL,
  `instance_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_id` int(10) unsigned NOT NULL,
  `content_type` varchar(20) CHARACTER SET ascii NOT NULL,
  `content_version` int(10) unsigned NOT NULL,
  `slot_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_id` (`content_id`,`content_type`,`content_version`,`slot_name`),
  KEY `instance_id` (`instance_id`,`content_type`),
  KEY `slot_name` (`instance_id`,`slot_name`),
  KEY `reusable_plugin_item_link` (`instance_id`,`content_id`,`content_type`,`content_version`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]plugin_layout_link`;
CREATE TABLE `[[DB_PREFIX]]plugin_layout_link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(10) unsigned NOT NULL,
  `instance_id` int(10) unsigned NOT NULL DEFAULT '0',
  `layout_id` int(10) unsigned NOT NULL,
  `slot_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `family_name` (`layout_id`,`slot_name`),
  KEY `slot_name` (`instance_id`,`slot_name`),
  KEY `layout_id` (`layout_id`,`slot_name`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]plugin_pages_by_mode`;
CREATE TABLE `[[DB_PREFIX]]plugin_pages_by_mode` (
  `equiv_id` int(10) unsigned NOT NULL,
  `content_type` varchar(20) CHARACTER SET ascii NOT NULL,
  `module_class_name` varchar(200) NOT NULL,
  `mode` varchar(200) CHARACTER SET ascii NOT NULL DEFAULT '',
  `state` char(2) CHARACTER SET ascii NOT NULL DEFAULT '',
  PRIMARY KEY (`module_class_name`,`mode`),
  KEY `content_type` (`content_type`,`equiv_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]plugin_setting_defs`;
CREATE TABLE `[[DB_PREFIX]]plugin_setting_defs` (
  `module_id` int(10) unsigned NOT NULL,
  `module_class_name` varchar(200) NOT NULL,
  `name` varchar(255) NOT NULL,
  `default_value` mediumtext,
  PRIMARY KEY (`module_id`,`name`),
  KEY `module_class_name` (`module_class_name`(75),`name`(175))
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]plugin_settings`;
CREATE TABLE `[[DB_PREFIX]]plugin_settings` (
  `instance_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `egg_id` int(10) unsigned NOT NULL DEFAULT '0',
  `value` mediumtext CHARACTER SET utf8mb4,
  `is_content` enum('synchronized_setting','version_controlled_setting','version_controlled_content') NOT NULL DEFAULT 'synchronized_setting',
  `format` enum('empty','text','html','translatable_text','translatable_html') NOT NULL DEFAULT 'text',
  `foreign_key_to` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `foreign_key_id` int(10) unsigned NOT NULL DEFAULT '0',
  `foreign_key_char` varchar(250) CHARACTER SET ascii NOT NULL DEFAULT '',
  `dangling_cross_references` enum('keep','remove','delete_instance') NOT NULL DEFAULT 'remove',
  `is_email_address` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`instance_id`,`name`,`egg_id`),
  KEY `is_content` (`is_content`),
  KEY `foreign_key_to` (`foreign_key_to`,`foreign_key_id`,`foreign_key_char`),
  KEY `dangling_cross_references` (`dangling_cross_references`),
  KEY `value` (`value`(64)),
  KEY `foreign_key_char` (`foreign_key_to`,`foreign_key_char`),
  KEY `format` (`format`),
  KEY `name` (`name`,`egg_id`,`instance_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]signals`;
CREATE TABLE `[[DB_PREFIX]]signals` (
  `signal_name` varchar(127) NOT NULL,
  `module_id` int(10) unsigned NOT NULL,
  `module_class_name` varchar(200) NOT NULL,
  `static_method` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `suppresses_module_class_name` varchar(200) NOT NULL DEFAULT '',
  UNIQUE KEY `event_name` (`signal_name`,`module_class_name`),
  KEY `suppresses_plugin_class` (`suppresses_module_class_name`),
  KEY `plugin_id` (`module_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]site_settings`;
CREATE TABLE `[[DB_PREFIX]]site_settings` (
  `name` varchar(255) CHARACTER SET ascii NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4,
  `default_value` mediumtext,
  `encrypted` tinyint(1) NOT NULL DEFAULT '0',
  `secret` tinyint(1) NOT NULL DEFAULT '0',
  `protect_from_database_restore` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`name`),
  KEY `value` (`value`(64))
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]skins`;
CREATE TABLE `[[DB_PREFIX]]skins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `extension_of_skin` varchar(255) NOT NULL DEFAULT '',
  `import` text,
  `css_class` varchar(100) NOT NULL DEFAULT '',
  `background_selector` varchar(64) DEFAULT 'body',
  `enable_editable_css` tinyint(1) NOT NULL DEFAULT '1',
  `missing` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `family_name` (`name`),
  KEY `name` (`name`),
  KEY `display_name` (`display_name`),
  KEY `extension_of_skin` (`extension_of_skin`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]slide_layouts`;
CREATE TABLE `[[DB_PREFIX]]slide_layouts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `layout_for` enum('schema') NOT NULL,
  `layout_for_id` int(10) unsigned NOT NULL,
  `ord` smallint(4) unsigned NOT NULL DEFAULT '1',
  `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL,
  `privacy` enum('public','logged_out','logged_in','group_members','in_smart_group','logged_in_not_in_smart_group','with_role') NOT NULL DEFAULT 'public',
  `at_location` enum('any','in_url','detect') NOT NULL DEFAULT 'in_url',
  `data` mediumtext CHARACTER SET utf8mb4,
  `created` datetime NOT NULL,
  `last_edited` datetime DEFAULT NULL,
  `last_edited_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_edited_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `layout_for` (`layout_for`,`layout_for_id`,`ord`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]smart_group_opt_outs`;
CREATE TABLE `[[DB_PREFIX]]smart_group_opt_outs` (
  `smart_group_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `opted_out_on` datetime NOT NULL,
  `opt_out_method` varchar(20) NOT NULL,
  PRIMARY KEY (`smart_group_id`,`user_id`),
  KEY `opted_out_on` (`opted_out_on`),
  KEY `opt_out_method` (`opt_out_method`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]smart_group_rules`;
CREATE TABLE `[[DB_PREFIX]]smart_group_rules` (
  `smart_group_id` int(10) unsigned NOT NULL,
  `ord` int(10) unsigned NOT NULL,
  `type_of_check` enum('user_field','role','activity_band','in_a_group','not_in_a_group') NOT NULL DEFAULT 'user_field',
  `field_id` int(10) unsigned NOT NULL DEFAULT '0',
  `field2_id` int(10) unsigned NOT NULL DEFAULT '0',
  `field3_id` int(10) unsigned NOT NULL DEFAULT '0',
  `field4_id` int(10) unsigned NOT NULL DEFAULT '0',
  `field5_id` int(10) unsigned NOT NULL DEFAULT '0',
  `role_id` int(10) unsigned NOT NULL DEFAULT '0',
  `activity_band_id` int(10) unsigned NOT NULL DEFAULT '0',
  `not` tinyint(1) NOT NULL DEFAULT '0',
  `value` text,
  PRIMARY KEY (`smart_group_id`,`ord`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]smart_groups`;
CREATE TABLE `[[DB_PREFIX]]smart_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 NOT NULL,
  `intended_usage` enum('smart_newsletter_group','smart_permissions_group') NOT NULL DEFAULT 'smart_newsletter_group',
  `must_match` enum('all','any') NOT NULL DEFAULT 'all',
  `created_on` datetime DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `last_modified_on` datetime DEFAULT NULL,
  `last_modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `intended_usage` (`intended_usage`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]spare_aliases`;
CREATE TABLE `[[DB_PREFIX]]spare_aliases` (
  `alias` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
  `target_loc` enum('int','ext') NOT NULL DEFAULT 'int',
  `content_id` int(10) unsigned NOT NULL,
  `content_type` varchar(20) CHARACTER SET ascii NOT NULL DEFAULT '',
  `ext_url` varchar(255) NOT NULL DEFAULT '',
  `created_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`alias`),
  KEY `content_type` (`content_type`,`content_id`),
  KEY `target_loc` (`target_loc`),
  KEY `ext_url` (`ext_url`),
  KEY `created_datetime` (`created_datetime`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]spare_domain_names`;
CREATE TABLE `[[DB_PREFIX]]spare_domain_names` (
  `requested_url` varchar(255) NOT NULL,
  `content_id` int(10) unsigned NOT NULL,
  `content_type` varchar(20) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`requested_url`),
  KEY `content_type` (`content_type`,`content_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]special_pages`;
CREATE TABLE `[[DB_PREFIX]]special_pages` (
  `equiv_id` int(10) unsigned DEFAULT NULL,
  `content_type` varchar(20) CHARACTER SET ascii DEFAULT NULL,
  `page_type` varchar(64) NOT NULL,
  `logic` enum('create_and_maintain_in_default_language','create_in_default_language_on_install') NOT NULL DEFAULT 'create_and_maintain_in_default_language',
  `allow_hide` tinyint(1) NOT NULL DEFAULT '0',
  `publish` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `module_class_name` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`page_type`),
  UNIQUE KEY `content_type` (`content_type`,`equiv_id`),
  KEY `module_class_name` (`module_class_name`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]translation_chain_privacy`;
CREATE TABLE `[[DB_PREFIX]]translation_chain_privacy` (
  `equiv_id` int(10) unsigned NOT NULL,
  `content_type` varchar(20) CHARACTER SET ascii NOT NULL,
  `module_class_name` varchar(200) NOT NULL DEFAULT '',
  `method_name` varchar(127) NOT NULL DEFAULT '',
  `param_1` varchar(200) NOT NULL DEFAULT '',
  `param_2` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`equiv_id`,`content_type`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]translation_chains`;
CREATE TABLE `[[DB_PREFIX]]translation_chains` (
  `equiv_id` int(10) unsigned NOT NULL,
  `type` char(20) CHARACTER SET ascii NOT NULL,
  `privacy` enum('public','logged_out','logged_in','group_members','in_smart_group','logged_in_not_in_smart_group','call_static_method','send_signal','with_role') NOT NULL DEFAULT 'public',
  `at_location` enum('any','in_url','detect') NOT NULL DEFAULT 'any',
  `smart_group_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`equiv_id`,`type`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]tuix_file_contents`;
CREATE TABLE `[[DB_PREFIX]]tuix_file_contents` (
  `type` enum('admin_boxes','admin_toolbar','help','organizer','slot_controls','visitor','wizards') NOT NULL,
  `path` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `panel_type` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `setting_group` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `module_class_name` varchar(200) CHARACTER SET ascii NOT NULL,
  `filename` varchar(255) CHARACTER SET ascii NOT NULL,
  `last_modified` int(10) unsigned NOT NULL DEFAULT '0',
  `checksum` varchar(32) CHARACTER SET ascii NOT NULL DEFAULT '',
  PRIMARY KEY (`type`,`path`,`setting_group`,`module_class_name`,`filename`),
  KEY `panel_type` (`panel_type`),
  KEY `module_panel_types` (`type`,`module_class_name`,`panel_type`,`path`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]tuix_snippets`;
CREATE TABLE `[[DB_PREFIX]]tuix_snippets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL,
  `custom_yaml` mediumtext CHARACTER SET utf8mb4,
  `custom_json` mediumtext CHARACTER SET utf8mb4,
  `created` datetime DEFAULT NULL,
  `created_admin_id` int(10) unsigned DEFAULT NULL,
  `created_user_id` int(10) unsigned DEFAULT NULL,
  `created_username` varchar(255) DEFAULT NULL,
  `last_edited` datetime DEFAULT NULL,
  `last_edited_admin_id` int(10) unsigned DEFAULT NULL,
  `last_edited_user_id` int(10) unsigned DEFAULT NULL,
  `last_edited_username` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]user_characteristic_values`;
CREATE TABLE `[[DB_PREFIX]]user_characteristic_values` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `characteristic_id` int(10) NOT NULL,
  `ordinal` int(10) NOT NULL,
  `name` text NOT NULL,
  `label` varchar(255) NOT NULL DEFAULT 'Label not assigned',
  `help_text` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `characteristic_id` (`characteristic_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]user_characteristic_values_link`;
CREATE TABLE `[[DB_PREFIX]]user_characteristic_values_link` (
  `user_id` int(10) unsigned NOT NULL,
  `user_characteristic_value_id` int(10) NOT NULL,
  PRIMARY KEY (`user_id`,`user_characteristic_value_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]user_content_accesslog`;
CREATE TABLE `[[DB_PREFIX]]user_content_accesslog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hit_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` varchar(20) CHARACTER SET ascii NOT NULL,
  `content_version` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`hit_datetime`,`user_id`,`content_id`,`content_type`),
  UNIQUE KEY `id` (`id`),
  KEY `user_id` (`user_id`),
  KEY `content_type` (`content_type`),
  KEY `content_id` (`content_id`),
  KEY `user_id_2` (`user_id`),
  KEY `content_id_2` (`content_id`),
  KEY `content_type_2` (`content_type`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]user_country_link`;
CREATE TABLE `[[DB_PREFIX]]user_country_link` (
  `user_id` int(10) unsigned NOT NULL,
  `country_id` varchar(5) NOT NULL,
  PRIMARY KEY (`user_id`,`country_id`),
  UNIQUE KEY `country_id` (`country_id`,`user_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]user_perm_settings`;
CREATE TABLE `[[DB_PREFIX]]user_perm_settings` (
  `name` varchar(255) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`name`),
  KEY `value` (`value`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]user_signin_log`;
CREATE TABLE `[[DB_PREFIX]]user_signin_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `login_datetime` datetime DEFAULT NULL,
  `browser` varchar(255) NOT NULL DEFAULT '',
  `browser_version` varchar(255) NOT NULL DEFAULT '',
  `platform` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `login_datetime` (`login_datetime`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]user_sync_log`;
CREATE TABLE `[[DB_PREFIX]]user_sync_log` (
  `user_id` int(10) unsigned NOT NULL,
  `last_synced_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  KEY `last_synced_timestamp` (`last_synced_timestamp`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]users`;
CREATE TABLE `[[DB_PREFIX]]users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `identifier` varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL,
  `screen_name` varchar(50) CHARACTER SET utf8mb4 DEFAULT '',
  `screen_name_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `password` varchar(50) NOT NULL DEFAULT '',
  `password_salt` varchar(8) CHARACTER SET ascii DEFAULT NULL,
  `password_needs_changing` tinyint(1) NOT NULL DEFAULT '0',
  `reset_password_time` datetime DEFAULT NULL,
  `status` enum('pending','active','suspended','contact') NOT NULL DEFAULT 'pending',
  `image_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_profile_update_in_frontend` datetime DEFAULT NULL,
  `salutation` varchar(25) CHARACTER SET utf8mb4 DEFAULT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `last_name` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `email` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `email_verified` tinyint(1) NOT NULL DEFAULT '0',
  `created_date` datetime DEFAULT NULL,
  `created_admin_id` int(10) unsigned DEFAULT NULL,
  `created_user_id` int(10) unsigned DEFAULT NULL,
  `created_username` varchar(255) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `last_edited_admin_id` int(10) unsigned DEFAULT NULL,
  `last_edited_user_id` int(10) unsigned DEFAULT NULL,
  `last_edited_username` varchar(255) DEFAULT NULL,
  `suspended_date` datetime DEFAULT NULL,
  `last_updated_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `send_delayed_registration_email` tinyint(1) NOT NULL DEFAULT '0',
  `terms_and_conditions_accepted` tinyint(1) NOT NULL DEFAULT '0',
  `equiv_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` varchar(20) CHARACTER SET ascii NOT NULL DEFAULT '',
  `hash` varchar(28) CHARACTER SET ascii NOT NULL DEFAULT '',
  `creation_method` enum('visitor','admin') NOT NULL DEFAULT 'visitor',
  `creation_method_note` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `ordinal` int(10) NOT NULL DEFAULT '0',
  `consent_hash` varchar(28) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `parent_id` (`parent_id`),
  KEY `screen_name` (`screen_name`),
  KEY `image_id` (`image_id`),
  KEY `last_profile_update_in_frontend` (`last_profile_update_in_frontend`),
  KEY `last_login` (`last_login`),
  KEY `identifier` (`identifier`),
  KEY `last_updated_timestamp` (`last_updated_timestamp`),
  KEY `status` (`status`),
  KEY `send_delayed_registration_email` (`send_delayed_registration_email`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]users_custom_data`;
CREATE TABLE `[[DB_PREFIX]]users_custom_data` (
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_PREFIX]]visitor_phrases`;
CREATE TABLE `[[DB_PREFIX]]visitor_phrases` (
  `id` int(1) NOT NULL AUTO_INCREMENT,
  `code` text CHARACTER SET utf8mb4 NOT NULL,
  `language_id` varchar(15) NOT NULL DEFAULT '',
  `module_class_name` varchar(200) NOT NULL,
  `local_text` text CHARACTER SET utf8mb4,
  `protect_flag` tinyint(1) NOT NULL DEFAULT '0',
  `seen_in_visitor_mode` tinyint(1) NOT NULL DEFAULT '0',
  `seen_in_file` varchar(250) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `seen_at_url` text CHARACTER SET utf8mb4,
  PRIMARY KEY (`id`),
  KEY `code` (`code`(250)),
  KEY `language_id` (`language_id`),
  KEY `seen_in_visitor_mode` (`seen_in_visitor_mode`),
  KEY `seen_in_file` (`seen_in_file`),
  KEY `module_class_name` (`module_class_name`(100),`language_id`,`code`(150))
) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8;

REPLACE INTO `[[DB_PREFIX]]local_revision_numbers` VALUES
 ('admin/db_updates/step_2_update_the_database_schema','content_tables.inc.php',[[INSTALLER_REVISION_NO]]),
 ('admin/db_updates/step_2_update_the_database_schema','user_tables.inc.php',[[INSTALLER_REVISION_NO]]),
 ('admin/db_updates/step_4_migrate_the_data','content_tables.inc.php',[[INSTALLER_REVISION_NO]]),
 ('admin/db_updates/step_4_migrate_the_data','plugins.inc.php',[[INSTALLER_REVISION_NO]]),
 ('admin/db_updates/step_4_migrate_the_data','user_tables.inc.php',[[INSTALLER_REVISION_NO]]),
 ('admin/db_updates/step_1_update_the_updater_itself','updater_tables.inc.php',[[INSTALLER_REVISION_NO]]);
 
