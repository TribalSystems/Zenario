<?php
/*
 * Copyright (c) 2021, Tribal Limited
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


//Create the tables needed for newsletters
ze\dbAdm::revision( 8

//Create a table to store newsletters
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`(
		`id` int(10) unsigned NOT NULL auto_increment,
		`newsletter_name` varchar(255) NOT NULL default '',
		`subject` tinytext NULL,
		`email_address_from` varchar(100) NOT NULL default '',
		`email_name_from` varchar(255) NOT NULL default '',
		`body` text NULL,
		`status` enum('_DRAFT', '_IN_PROGRESS', '_ARCHIVED') NOT NULL default '_DRAFT',
		`date_created` datetime NOT NULL,
		`created_by_id` int(10) unsigned NOT NULL,
		`created_by_authtype` enum('local','super') NOT NULL,
		`date_modified` datetime NULL,
		`modified_by_id` int(10) unsigned NULL,
		`modified_by_authtype` enum('local','super') NULL,
		`date_sent` datetime NULL,
		`sent_by_id` int(10) unsigned NULL,
		`sent_by_authtype` enum('local','super') NULL,
		PRIMARY KEY  (`id`),
		UNIQUE INDEX (`newsletter_name`),
		INDEX (`status`),
		INDEX (`date_modified`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql


//Create a table to record which newsletters have gone to which people
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`(
		`newsletter_id` int(10) unsigned NOT NULL,
		`user_id` int(10) unsigned NOT NULL,
		`tracker_hash` varchar(40) NOT NULL,
		`username` varchar(50) NOT NULL,
		`email` varchar(100) NOT NULL default '',
		`email_sent` tinyint(1) NOT NULL default 0,
		`time_sent` datetime NULL,
		`time_received` datetime NULL,
		`time_clicked_through` datetime NULL,
		INDEX `user_id` (`newsletter_id`, `email_sent`, `user_id`),
		INDEX `time_sent` (`newsletter_id`, `time_sent`),
		UNIQUE (`tracker_hash`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

//Fix some errors in MySQL strict mode
);	ze\dbAdm::revision( 15
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	MODIFY COLUMN `body` text NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	MODIFY COLUMN `subject` tinytext NULL
_sql

//Remove an unused table if it exists
);	ze\dbAdm::revision( 19
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_designs`
_sql


//Add another key to the newsletter_user_link table
);	ze\dbAdm::revision( 22
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD INDEX (`newsletter_id`, `user_id`)
_sql


//Add more hashes to the user link table
);	ze\dbAdm::revision( 24
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD COLUMN `remove_hash` varchar(40) NOT NULL DEFAULT ''
	AFTER `tracker_hash`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD COLUMN `delete_account_hash` varchar(40) NOT NULL DEFAULT ''
	AFTER `remove_hash`
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link` SET
		`remove_hash` = SHA(CONCAT('remove_', `tracker_hash`)),
		`delete_account_hash` = SHA(CONCAT('delete_', `tracker_hash`))
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD UNIQUE KEY (`remove_hash`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD UNIQUE KEY (`delete_account_hash`)
_sql


//Add a column to record the URL
);	ze\dbAdm::revision( 29
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	ADD COLUMN `url` varchar(255) NOT NULL default ''
	AFTER `email_name_from`
_sql


//Create a table to record who has been sent a Newsletter
);	ze\dbAdm::revision( 30
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_sent_newsletter_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_sent_newsletter_link`(
		`newsletter_id` int(10) unsigned NOT NULL,
		`include` tinyint(1) NOT NULL default 1,
		`sent_newsletter_id` int(10) unsigned NOT NULL,
		PRIMARY KEY  (`newsletter_id`,`include`,`sent_newsletter_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql


//Updates for version 6
);	ze\dbAdm::revision( 41
, <<<_sql
	UPDATE `[[DB_PREFIX]]admins` AS a
	INNER JOIN `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` AS n
	   ON n.created_by_id = a.global_id
	  AND n.created_by_authtype = 'super'
	SET n.created_by_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]admins` AS a
	INNER JOIN `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` AS n
	   ON n.modified_by_id = a.global_id
	  AND n.modified_by_authtype = 'super'
	SET n.modified_by_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]admins` AS a
	INNER JOIN `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` AS n
	   ON n.sent_by_id = a.global_id
	  AND n.sent_by_authtype = 'super'
	SET n.sent_by_id = a.id
_sql

//Updates for zenario 6
);	ze\dbAdm::revision( 42
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	DROP COLUMN `created_by_authtype`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	DROP COLUMN `modified_by_authtype`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	DROP COLUMN `sent_by_authtype`
_sql

);


//Convert the format of inline image/movie URLs in Newsletters
//(Note - this is mostly identical to the code above for Content areas and Email Templates in zenario/admin/db_updates/step_4_migrate_the_data/content_tables.inc.php)
if (ze\dbAdm::needRevision(46)) {
	//Get a list of checksums in the old format
	$sql = "
		SELECT checksum, MD5(CONCAT(filename, checksum))
		FROM ". DB_PREFIX. "files";
	$result = ze\sql::select($sql);
	
	$checksums = [];
	while ($row = ze\sql::fetchRow($result)) {
		$checksums[$row[1]] = $row[0];
	}
	
	
	//Get the body text from the newsletters
	$sql = "
		SELECT id, body
		FROM ". DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletters
		WHERE body LIKE '%cmsincludes/%'";
	$result = ze\sql::select($sql);
	
	while ($row = ze\sql::fetchAssoc($result)) {
		$html = '';
		$htmlChanged = false;
		
		//Parse the html, looking for links in the old format
		$links = preg_split('@cmsincludes/(\w+)\.php\?([^\'">]+)@s', $row['body'], -1,  PREG_SPLIT_DELIM_CAPTURE);
		$c = count($links) - 1;
		
		//Find the details of each link
		for ($i=0; $i < $c; $i += 3) {
			//Remember the html surrounding each link
			$html .= $links[$i];
			
			$params = [];
			$amp = '&amp;';
			
			//Attempt to loop through each request
			$request = preg_split('@(\w+)=([^\&]+)@s', $links[$i+2], -1,  PREG_SPLIT_DELIM_CAPTURE);
			$d = count($request) - 1;
			
			for ($j=0; $j < $d; $j += 3) {
				//Note down the seperator being used. Will usually be &amp;
				if (trim($request[$j])) {
					$amp = trim($request[$j]);
				}
				
				//Note down each request
				$params[$request[$j+1]] = $request[$j+2];
			}
			
			//Check the format of the links to see if it needs to be converted, and if it can be converted
			$doneSomething = false;
			if ($links[$i+1] == 'image' || $links[$i+1] == 'movie') {
				//If this file is already linked by checksum/filename
				if ($checksum = ze::ifNull($params['c'] ?? false, $params['checksum'] ?? false)) {
					//Change the path of the link
					$html .= 'zenario/file.php?c='. (($checksums[$checksum] ?? false) ?: $checksum);
					
					if (!empty($params['filename'])) {
						$html .= $amp. 'filename='. $params['filename'];
					}
					$htmlChanged = $doneSomething = true;
				}
			}
			
			//If we couldn't convert the format, just leave the link as it was
			if (!$doneSomething) {
				$html .= 'cmsincludes/'. $links[$i+1]. '.php?'. $links[$i+2];
			}
		}
		$html .= $links[$c];
		
		if ($htmlChanged) {
			ze\row::update(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', ['body' => $html], ['id' => $row['id']]);
		}
	}

	ze\dbAdm::revision(46);
}

	ze\dbAdm::revision(49
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD COLUMN email_overridden_by varchar(255) DEFAULT '' AFTER email_sent 
_sql


//Drop a table created in the beta but no longer used, if it exists
);	ze\dbAdm::revision( 65
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_image_link`
_sql


//Add a foreign key from the Newsletters table to Smart Groups
);	ze\dbAdm::revision( 75
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	ADD COLUMN `smart_group_id` int(10) unsigned NOT NULL default 0
	AFTER `newsletter_name`
_sql


//Turn a one-to-many relationship between the newsletters and groups (using the newsletter_group_link table) to just a one-to-one
);	ze\dbAdm::revision( 78
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	ADD COLUMN `group_id` int(10) unsigned NOT NULL default 0
	AFTER `newsletter_name`
_sql
);

//Attempt to migrate the data. This might fail, as the newsletter_group_link might never have been created, or (for some
//versions of the 6.0.6 beta) have already been dropped.
if (ze\dbAdm::needRevision(79)) {
	
	$sql = "
		SHOW TABLES LIKE '". ze\escape::sql(DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_group_link"). "'";
	
	if (($result = ze\sql::select($sql))
	 && (ze\sql::fetchRow($result))) {
		$sql = "
			UPDATE IGNORE ". DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletters AS n
			INNER JOIN ". DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_group_link AS ngl
			   ON ngl.newsletter_id = n.id
			  AND ngl.include = 1
			SET n.group_id = ngl.group_id";
	
		ze\sql::select($sql);
	}
	
	unset($sql);
	unset($result);
}

//Add some keys
	ze\dbAdm::revision( 80
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	ADD KEY (`group_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	ADD KEY (`smart_group_id`)
_sql

//Drop some tables that we're not using anymore.
);	ze\dbAdm::revision( 81
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_group_link`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_characteristic_link`
_sql

//Recipients are now selected using multiple Smart groups (OR logic)
//Unsubscribe / Delete account links ar enow pecified in the newsletetrs
);  ze\dbAdm::revision( 95
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_smart_group_link`
	(
		`newsletter_id` int(10) unsigned NOT NULL,
		`smart_group_id` int(10) unsigned NOT NULL
	)  ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

, <<<_sql
	INSERT INTO
		`[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_smart_group_link`
	SELECT
		id, smart_group_id
	FROM 
		`[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	WHERE 
		IFNULL(smart_group_id, 0) <> 0
	ORDER BY id
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	DROP COLUMN `smart_group_id`,
	CHANGE COLUMN `group_id` `unsubscribe_group_id` int(10) unsigned AFTER 	`status`, 
	ADD COLUMN unsubscribe_text text AFTER `unsubscribe_group_id`, 
	ADD COLUMN delete_account_text text AFTER `unsubscribe_text`
_sql


);  ze\dbAdm::revision( 98, 
 <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	ADD COLUMN `smart_group_descriptions_when_sent_out` TEXT AFTER `delete_account_text`
_sql



);	ze\dbAdm::revision( 108

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`(
		`id` int(10) unsigned NOT NULL auto_increment,
		`newsletter_id` int(10) NOT NULL,
		`link_ordinal` int(10) NOT NULL,
		`text_or_image` enum('text','image') NOT NULL,
		`link_text` varchar(100) NULL,
		`hyperlink` varchar(100) NOT NULL,
		`clickthrough_count` int(10) NOT NULL default 0,
		`last_clicked_date` datetime NULL,
		PRIMARY KEY  (`id`),
		INDEX (`newsletter_id`),
		INDEX (`link_ordinal`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql


);	ze\dbAdm::revision( 109
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`
		ADD COLUMN `hyperlink_hash` varchar(40) NOT NULL DEFAULT ''
	AFTER `id`
_sql

);  ze\dbAdm::revision( 110, 
  <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD COLUMN `clicked_hyperlink_id` int(10)
_sql

);	ze\dbAdm::revision( 111
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`
		CHANGE COLUMN `hyperlink` `hyperlink` varchar(255) NOT NULL
_sql

);	ze\dbAdm::revision( 112

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`
		CHANGE COLUMN `link_text` `link_text` varchar(255) NOT NULL
_sql


);	ze\dbAdm::revision( 117
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	DROP COLUMN `unsubscribe_group_id`
_sql




); ze\dbAdm::revision( 120
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	SET body = REPLACE(body, 'tribiq/file.php', 'zenario/file.php')
	WHERE body LIKE '%tribiq/file.php%'
_sql

); ze\dbAdm::revision( 132

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_templates`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_templates` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`name` varchar(255) NOT NULL,
		`body` text,
		`date_created` datetime NOT NULL,
		`created_by_id` int(10) unsigned NOT NULL DEFAULT '0',
		`date_modified` datetime DEFAULT NULL,
		`modified_by_id` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`id`)
	)  ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

//Add some sample designs
);	ze\dbAdm::revision(25, "
	INSERT INTO `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_templates` (
		`name`,
		`body`,
		`date_created`,
		`created_by_id`
	) VALUES (
		 'Blank Template',
		 '',
		 NOW(),
		 ". (int) $_SESSION['admin_userid']. "
		), (
		 'Sample Newsletter Design One',
		 '".
<<<_html
	<table width="600" align="center" border="0" cellspacing="0" cellpadding="0" style="font-family: Arial; color: #666666; margin-top:50px; font-size: 11px;">
		<tr>
		   <td colspan="2" align="left"  style="border:1px solid #DCDCDC;">
			  
			  <table border="0" cellspacing="0" cellpadding="0" style="margin:20px; font-size:14px; color:#444444;">
				<tr>
				 <td align="left" style="padding-bottom:10px; border-bottom:1px solid #DCDCDC; padding-bottom:15px;">
					<h1 style="color:#03779C; font-size:24px; padding:5px 0px 0px 0px; margin:0px;">Introductory Title</h1>
					<h2 style="color:#76AFC2; font-size:18px; padding:8px 0px 0px 0px; margin:0px;">Hello [[SALUTATION]] [[FIRST_NAME]] [[LAST_NAME]]</h2>
				 </td>
				</tr>
				<tr>
				 <td align="left" style="border-bottom:1px solid #DCDCDC; padding-bottom:8px; padding-top:15px;">
					<h1 style="font-size:18px; padding:0px 0px 5px 0px; color:#03779C; margin:0px;">Company News</h1>
					<p style="padding:5px 0px 5px 0px; margin:0px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi ut purus ipsum, quis fringilla nisi. Etiam euismod mattis nisi et convallis. Vivamus eleifend, sem vitae volutpat dignissim, lectus augue pellentesque ligula, sed rutrum libero nulla at metus. Proin varius porttitor erat et feugiat.</p>
					<p style="padding:5px 0px 5px 0px; margin:0px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi ut purus ipsum, quis fringilla nisi. Etiam euismod mattis nisi et convallis. Vivamus eleifend, sem vitae volutpat dignissim, lectus augue pellentesque ligula, sed rutrum libero nulla at metus. Proin varius porttitor erat et feugiat.</p>
					<p style="padding:5px 0px 5px 0px; margin:0px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi ut purus ipsum, quis fringilla nisi. Etiam euismod mattis nisi et convallis. Vivamus eleifend, sem vitae volutpat dignissim, lectus augue pellentesque ligula, sed rutrum libero nulla at metus. Proin varius porttitor erat et feugiat.</p>
					<p style="padding:5px 0px 5px 0px; margin:0px;"><a href="#" style="font-size:12px; display:block; text-align:right; color:#B80602;">find out more</a></p>
				 </td>
				</tr>
				<tr>
				 <td align="left" style="border-bottom:1px solid #DCDCDC; padding-bottom:8px; padding-top:15px;">
					<h1 style="font-size:18px; padding:0px 0px 5px 0px; color:#03779C; margin:0px;">Event News</h1>
					<h2 style="color:#76AFC2; font-size:14px; padding:8px 0px 0px 0px; margin:0px;">H2 Subheading</h2>
					<p style="padding:5px 0px 5px 0px; margin:0px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi ut purus ipsum, quis fringilla nisi. Etiam euismod mattis nisi et convallis. Vivamus eleifend, sem vitae volutpat dignissim, lectus augue pellentesque ligula, sed rutrum libero nulla at metus. Proin varius porttitor erat et feugiat.</p>
					<p style="padding:5px 0px 5px 0px; margin:0px;"><a href="#" style="font-size:12px; display:block; text-align:right; color:#B80602;">find out more</a></p>
				 </td>
				</tr>
				<tr>
				 <td align="left" style="border-bottom:1px solid #DCDCDC; padding-bottom:8px;">
					<h2 style="color:#76AFC2; font-size:14px; padding:8px 0px 0px 0px; margin:0px;">H2 Subheading</h2>
					<p style="padding:5px 0px 5px 0px; margin:0px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi ut purus ipsum, quis fringilla nisi. Etiam euismod mattis nisi et convallis. Vivamus eleifend, sem vitae volutpat dignissim, lectus augue pellentesque ligula, sed rutrum libero nulla at metus. Proin varius porttitor erat et feugiat.</p>
					<p style="padding:5px 0px 5px 0px; margin:0px;"><a href="#" style="font-size:12px; display:block; text-align:right; color:#B80602;">find out more</a></p>
				 </td>
				</tr>
				<tr>
				 <td align="left" style="border-bottom:1px solid #DCDCDC; padding-bottom:8px;">
					<h2 style="color:#76AFC2; font-size:14px; padding:8px 0px 0px 0px; margin:0px;">H2 Subheading</h2>
					<p style="padding:5px 0px 5px 0px; margin:0px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi ut purus ipsum, quis fringilla nisi. Etiam euismod mattis nisi et convallis. Vivamus eleifend, sem vitae volutpat dignissim, lectus augue pellentesque ligula, sed rutrum libero nulla at metus. Proin varius porttitor erat et feugiat.</p>
					<p style="padding:5px 0px 5px 0px; margin:0px;"><a href="#" style="font-size:12px; display:block; text-align:right; color:#B80602;">find out more</a></p>
				 </td>
				</tr>
			   </table>
		   </td>
		</tr>
	</table>
_html
		."',
		 NOW(),
		 0
		), (
		 'Sample Newsletter Design Two',
		'".
<<<_html
	<table width="600" align="center" border="0" cellspacing="0" cellpadding="0" style="font-family: Arial; color: #666666; background-color: #ffffff; margin-top:10px;   font-size: 11px;">
		<tr>
		   <td align="left" valign="top" style="vertical-align:top; padding-bottom:17px;">
				[Logo Image]
		   </td>
	
		   <td align="right" valign="top" style="vertical-align:top; padding-bottom:17px;">
			   <p>Donec iaculis Nunc a purus eu sapien--lacinia fermentum.</p>
		   </td>
		</tr>
		<tr>
		   <td height="102" colspan="2" align="left" valign="middle" style="vertical-align:top; background:#298AAA; vertical-align:middle; color:#ffffff; padding:0px 0px 0px 20px;">
			  <h1 style="font-size:24px; color:#ffffff;">Introductory Title</h1>
		   </td>
		</tr>
		<tr>
		   <td colspan="2" align="center" style="vertical-align:top;">
		   
				<table width="100%" border="0" style="border: 1px solid #DCDCDC; border-top:none; padding-top:7px;">
					<tr>
						<td style="vertical-align:top; font-size:14px; padding:15px 15px 0px 15px; color:#444444;">
							
							<h1 style="font-size:18px; color:#72AFC4;">Hello [[SALUTATION]] [[FIRST_NAME]] [[LAST_NAME]]</h1>
							<h2 style="font-size:18px; color:#03779E;">Company News</h2>
							<p style="padding-right:20px;">Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermen-tum quam. Donec imperdiet, nibh sit amet pharetra placerat, tortor purus condimentum lectus.</p>
							<p style="padding-right:20px;">Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis <a href="#" style="color:#72AFC1;">Nunc a purus</a> eu sapien--lacinia fermen-tum quam. Donec imperdiet, nibh sit amet pharetra placerat, tortor purus condimentum lectus.</p>
							<p style="padding-right:20px;"><a href="#" style="color:#B70505; display:block; text-align:right; font-size:12px;">find out more</a></p>
							<h2 style="font-size:18px; color:#03779E;">Event News</h2>
							<table width="100%" cellspacing="0" cellpadding="4" style="font-size:14px; background:#EEF8FA; border:1px solid #74AEC2; margin-top:10px; color:#444444;">
								<tr>
									<td style="padding:10px 10px 10px 10px;">
										<h2 style="color:#72AFC4; font-size:14px; padding:0px; margin:0px;">H2 Subheading</h2>
										<p style="padding:0px; margin:0px;">Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermentum.</p>
										<a href="#" style="color:#B70505; display:block; text-align:right; font-size:12px; padding-top:4px;">find out more</a>
									</td>
								</tr>
								<tr>
									<td style="padding:10px 10px 10px 10px;">
										<h2 style="color:#72AFC4; font-size:14px; padding:0px; margin:0px;">H2 Subheading</h2>
										<p style="padding:0px; margin:0px;">Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermentum.</p>
										<ul>
											<li style="color:#03779E; line-height:1.4em;"><span style="color: #666666;">Donec iaculis Nunc a purus eu sapien</span></li>
											<li style="color:#03779E; line-height:1.4em;"><span style="color: #666666;">Donec iaculis Nunc a purus eu sapien</span></li>
											<li style="color:#03779E; line-height:1.4em;"><span style="color: #666666;">Donec iaculis Nunc a purus eu sapien</span></li>
											<li style="color:#03779E; line-height:1.4em;"><span style="color: #666666;">Donec iaculis Nunc a purus eu sapien</span></li>
										</ul>
										<a href="#" style="color:#B70505; display:block; text-align:right; font-size:12px; padding-top:4px;">find out more</a>
									</td>
								</tr>
								<tr>
									<td style="padding:10px 10px 10px 10px;">
										<h2 style="color:#72AFC4; font-size:14px; padding:0px; margin:0px;">H2 Subheading</h2>
										<p style="padding:0px; margin:0px;">Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermentum.</p>
										<a href="#" style="color:#B70505; display:block; text-align:right; font-size:12px; padding-top:4px;">find out more</a>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
		  </td>
		</tr>
	</table>
_html
		."',
		 NOW(),
		 0
		), (
		 'Sample Newsletter Design Three',
		'".
<<<_html
	<table width="600" align="center" border="0" cellspacing="0" cellpadding="0" style="font-family: Arial; color: #666666; font-size:12px;">
		<tr height="70">
		   <td align="left" valign="top" style="vertical-align:top;">
				[Logo Image]
		   </td>
	
		   <td align="right" valign="top" style="vertical-align:top; font-size:10px;">
			   <p>Donec iaculis Nunc a purus eu sapien--lacinia fermentum.</p>
		   </td>
		</tr>
		<tr>
		   <td height="102" colspan="2" align="left" valign="middle" style="vertical-align:middle; background:#107EA1; color:#ffffff; padding:0px 0px 0px 20px;">
			  <h1 style="font-size:22px;">Introductory Title</h1>
		   </td>
		</tr>
		<tr>
		   <td colspan="2" align="center" style="vertical-align:top;">
		   
				<table width="100%" border="0" style="border: 1px solid #DCDCDC; border-top:none; padding-top:7px; color: #666666;">
					<tr>
						<td style="vertical-align:top; font-size:14px; padding:15px 15px 0px 15px;">
							
							<h1 style="font-size:18px; color:#72AFC4;">Hello [[SALUTATION]] [[FIRST_NAME]] [[LAST_NAME]]</h1>
							[Image here]
							<h2 style="font-size:18px; color:#03779E;">Company News</h2>
							<p style="padding-right:20px;">Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermen-tum quam. Donec imperdiet, nibh sit amet pharetra placerat, tortor purus condimentum lectus.</p>
							<p style="padding-right:20px;">Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis <a href="#" style="color:#95C3D2;">Nunc a purus</a> eu sapien--lacinia fermen-tum quam. Donec imperdiet, nibh sit amet pharetra placerat, tortor purus condimentum lectus.</p>
							<p style="padding-right:20px;"><a href="#" style="color:#B70505; display:block; text-align:right; font-size:12px; padding-top:4px;">find out more</a></p>
					  <table width="100%" cellspacing="0" cellpadding="4" style="font-size:12px; background:#EEF8FA; border:1px solid #74AEC2; margin-top:10px; color: #666666;">
								<tr>
									<td valign="top" style="vertical-align:top; padding:10px 5px 10px 8px;">
										[Image_here]
									</td>
									<td style="vertical-align:top; padding:10px 5px 10px 8px;">
										<h3 style="font-size:16px; color:#0377A0; padding:0px 0px 4px 0px; margin:0px;">Promo title</h3>
										<p style="padding:0px; margin:0px;">Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermentum.</p>
										<a href="#" style="color:#B70505; display:block; text-align:right; font-size:12px; padding-top:4px;">find out more</a>
									</td>
								</tr>
							</table>
							<table width="100%" cellspacing="0" cellpadding="4" style="font-size:12px; background:#EEF8FA; border:1px solid #74AEC2; margin-top:10px; color: #666666;">
								<tr>
									<td valign="top" style="vertical-align:top; padding:10px 5px 10px 8px;">
										[Image_here]
									</td>
									<td style="vertical-align:top; padding:10px 5px 10px 8px;">
										<h3 style="font-size:16px; color:#0377A0; padding:0px 0px 4px 0px; margin:0px;">Promo title</h3>
										<p style="padding:0px; margin:0px;">Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermentum.</p>
										<a href="#" style="color:#B70505; display:block; text-align:right; font-size:12px; padding-top:4px;">find out more</a>
									</td>
								</tr>
							</table>
							<table width="100%" cellspacing="0" cellpadding="4" style="font-size:12px; background:#EEF8FA; border:1px solid #74AEC2; margin-top:10px; color: #666666;">
								<tr>
									<td valign="top" style="vertical-align:top; padding:10px 5px 10px 8px;">
										[Image_here]
									</td>
									<td style="vertical-align:top; padding:10px 5px 10px 8px;">
										<h3 style="font-size:16px; color:#0377A0; padding:0px 0px 4px 0px; margin:0px;">Promo title</h3>
										<p style="padding:0px; margin:0px;">Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermentum.</p>
										<a href="#" style="color:#B70505; display:block; text-align:right; font-size:12px; padding-top:4px;">find out more</a>
									</td>
								</tr>
							</table>
							
						</td>
						<td style="background:#F5F5F5; vertical-align:top; width:160px;">
						
							<table width="100%" cellspacing="0" cellpadding="4" style="width:160px; border-bottom:1px solid #ffffff; color: #666666;">
								<tr>
									<td style="vertical-align:top; padding-bottom:25px;">
										<h1 style="background:#00749B; color:#ffffff; font-size:13px; padding:7px 10px;">News &amp; Views</h1>
										<h2 style="padding:0px 5px; font-size:12px; line-height:1em; margin-bottom:0px; padding-bottom:0px;"><a href="#" style="color:#05769E; text-decoration:none;">Tortor purus condimentum lectus</a></h2>
										<p style="padding:0px 5px; font-size:12px; margin-top:0px; padding-top:0px;">Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermentum.</p>
										<h2 style="padding:0px 5px; font-size:12px; line-height:1em; margin-bottom:0px; padding-bottom:0px;"><a href="#" style="color:#05769E; text-decoration:none;">Tortor purus condimentum lectus</a></h2>
										<p style="padding:0px 5px; font-size:12px; margin-top:0px; padding-top:0px;">Donec iaculis Nunc a purus eu sapien--lacinia fermentum. Donec iaculis Nunc a purus eu sapien--lacinia fermentum.</p>
									</td>
								</tr>
							</table>
							
							<table width="100%" cellspacing="0" cellpadding="4" style="width:160px; font-size:11px;	border-bottom:1px solid #ffffff; border-bottom:0px;">
								<tr>
									<td style="vertical-align:top; padding-bottom:25px;">
										<h1 style="background:#00749B; color:#ffffff; font-size:13px; padding:7px 10px;">Browse the Site</h1>
										<ul style="padding:0px 0px 0px 15px; margin:0px; color: #666666;">
											<li><a href="#" style="color:#4095B2;">3D Shapes</a></li>
											<li><a href="#" style="color:#4095B2;">Animals</a></li>
											<li><a href="#" style="color:#4095B2;">Arts and crafts</a></li>
											<li><a href="#" style="color:#4095B2;">3D Shapes</a></li>
											<li><a href="#" style="color:#4095B2;">Animals</a></li>
											<li><a href="#" style="color:#4095B2;">Arts and crafts</a></li>
											<li><a href="#" style="color:#4095B2;">3D Shapes</a></li>
											<li><a href="#" style="color:#4095B2;">Animals</a></li>
											<li><a href="#" style="color:#4095B2;">Arts and crafts</a></li>
											<li><a href="#" style="color:#4095B2;">3D Shapes</a></li>
											<li><a href="#" style="color:#4095B2;">Animals</a></li>
											<li><a href="#" style="color:#4095B2;">Arts and crafts</a></li>
										</ul>
									</td>
								</tr>
							</table>
						
						</td>
					</tr>
				</table>
		  </td>
		</tr>
	</table>
_html
		."',
		 NOW(),
		 0
	)
	ON DUPLICATE KEY UPDATE
		`name` = VALUES(`name`),
		`body` = VALUES(`body`),
		`date_created` = VALUES(`date_created`),
		`created_by_id` = VALUES(`created_by_id`)
",

<<<_sql
	DELETE FROM `[[DB_PREFIX]]email_templates`
	WHERE `code` = 'zenario_newsletter__blank'
	OR `code` = 'zenario_newsletter__sample_design_1'
	OR `code` = 'zenario_newsletter__sample_design_2'
	OR `code` = 'zenario_newsletter__sample_design_3'
_sql



//Remove the "time_received" column
);	ze\dbAdm::revision( 117
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	DROP COLUMN `time_received`
_sql

//Add some missing keys
);	ze\dbAdm::revision( 158
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	DROP INDEX `username`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	DROP INDEX `email`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	DROP INDEX `email_sent`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	DROP INDEX `time_clicked_through`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD INDEX `username` (`newsletter_id`, `username`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD INDEX `email` (`newsletter_id`, `email`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD INDEX `email_sent` (`newsletter_id`, `email_sent`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD INDEX `time_clicked_through` (`newsletter_id`, `time_clicked_through`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD INDEX `email_overridden_by` (`newsletter_id`, `email_overridden_by`)
_sql
);





//Convert the format of inline image URLs in Newsletters again.
//This time we're moving them from the old inline image pool to the new email pool.
//Also resync all of the images used in them.
if (ze\dbAdm::needRevision(159)) {
	//Get the body text from the newsletters
	$sql = "
		SELECT id, body
		FROM ". DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletters
		WHERE body LIKE '%file.php%'";
	$result = ze\sql::select($sql);
	
	while ($row = ze\sql::fetchAssoc($result)) {
		$files = [];
		$htmlChanged = false;
		ze\contentAdm::syncInlineFileLinks($files, $row['body'], $htmlChanged);
		
		if ($htmlChanged) {
			ze\row::update(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', ['body' => $row['body']], ['id' => $row['id']]);
		}
		
		ze\contentAdm::syncInlineFiles(
			$files,
			['foreign_key_to' => 'newsletter', 'foreign_key_id' => $row['id']],
			$keepOldImagesThatAreNotInUse = true);
	}

	ze\dbAdm::revision(159);
}

//Update and resync images in newsletter templates too
if (ze\dbAdm::needRevision(160)) {
	//Get the body text from the newsletters
	$sql = "
		SELECT id, body
		FROM ". DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_templates
		WHERE body LIKE '%file.php%'";
	$result = ze\sql::select($sql);
	
	while ($row = ze\sql::fetchAssoc($result)) {
		$files = [];
		$htmlChanged = false;
		ze\contentAdm::syncInlineFileLinks($files, $row['body'], $htmlChanged);
		
		if ($htmlChanged) {
			ze\row::update(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_templates', ['body' => $row['body']], ['id' => $row['id']]);
		}
		
		ze\contentAdm::syncInlineFiles(
			$files,
			['foreign_key_to' => 'newsletter_template', 'foreign_key_id' => $row['id']],
			$keepOldImagesThatAreNotInUse = false);
	}

	ze\dbAdm::revision(160);
}

//Flag the links to images in archived newsletters as archived.
if (ze\dbAdm::needRevision(162)) {

	foreach (ze\row::getValues(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', 'id', ['status' => '_ARCHIVED']) as $id) {
		ze\row::update('inline_images', ['archived' => 1], ['foreign_key_to' => 'newsletter', 'foreign_key_id' => $id]);
	}

	ze\dbAdm::revision(162);
}




//Re-add the time_received column
ze\dbAdm::revision( 164
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD COLUMN `time_received` datetime NULL 
_sql

);	ze\dbAdm::revision( 165
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	DROP INDEX `time_received`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD INDEX `time_received` (`newsletter_id`, `time_received`)
_sql

);	ze\dbAdm::revision( 166
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	CHANGE COLUMN `username` `identifier` varchar(50) NOT NULL
_sql

);	ze\dbAdm::revision( 167
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	DROP INDEX `username`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD INDEX `identifier` (`newsletter_id`, `identifier`)
_sql

);	ze\dbAdm::revision( 170
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	MODIFY COLUMN `body` mediumtext
_sql

);	ze\dbAdm::revision( 171
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	ADD COLUMN `head` mediumtext
	AFTER `url`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_templates`
	ADD COLUMN `head` mediumtext
	AFTER `name`
_sql


);	ze\dbAdm::revision( 173
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`
		CHANGE COLUMN `link_text` `link_text` text
_sql



); ze\dbAdm::revision( 175
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	ADD COLUMN `scheduled_send_datetime` datetime DEFAULT NULL
_sql


//Attempt to convert some columns with a utf8-3-byte character set to a 4-byte character set
); ze\dbAdm::revision( 180
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` MODIFY COLUMN `body` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` MODIFY COLUMN `delete_account_text` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` MODIFY COLUMN `email_address_from` varchar(100) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` SET `email_name_from` = SUBSTR(`email_name_from`, 1, 250) WHERE CHAR_LENGTH(`email_name_from`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` MODIFY COLUMN `email_name_from` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` MODIFY COLUMN `head` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` SET `newsletter_name` = SUBSTR(`newsletter_name`, 1, 250) WHERE CHAR_LENGTH(`newsletter_name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` MODIFY COLUMN `newsletter_name` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` MODIFY COLUMN `smart_group_descriptions_when_sent_out` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` SET `subject` = SUBSTR(`subject`, 1, 250) WHERE CHAR_LENGTH(`subject`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` MODIFY COLUMN `subject` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` MODIFY COLUMN `unsubscribe_text` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` SET `url` = SUBSTR(`url`, 1, 250) WHERE CHAR_LENGTH(`url`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters` MODIFY COLUMN `url` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks` SET `hyperlink` = SUBSTR(`hyperlink`, 1, 250) WHERE CHAR_LENGTH(`hyperlink`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks` MODIFY COLUMN `hyperlink` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks` MODIFY COLUMN `hyperlink_hash` varchar(40) CHARACTER SET ascii NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks` MODIFY COLUMN `link_text` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_templates` MODIFY COLUMN `body` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_templates` MODIFY COLUMN `head` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_templates` SET `name` = SUBSTR(`name`, 1, 250) WHERE CHAR_LENGTH(`name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_templates` MODIFY COLUMN `name` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link` MODIFY COLUMN `delete_account_hash` varchar(40) CHARACTER SET ascii NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link` MODIFY COLUMN `email` varchar(100) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link` SET `email_overridden_by` = SUBSTR(`email_overridden_by`, 1, 245) WHERE CHAR_LENGTH(`email_overridden_by`) > 245
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link` MODIFY COLUMN `email_overridden_by` varchar(245) CHARACTER SET utf8mb4 NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link` MODIFY COLUMN `identifier` varchar(50) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link` MODIFY COLUMN `remove_hash` varchar(40) CHARACTER SET ascii NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link` MODIFY COLUMN `tracker_hash` varchar(40) CHARACTER SET ascii NOT NULL
_sql


);	ze\dbAdm::revision( 181
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`
		CHANGE COLUMN `hyperlink` `hyperlink` text
_sql


);	ze\dbAdm::revision( 182
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`
	DROP COLUMN `identifier`
_sql

);	ze\dbAdm::revision( 184
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters`
	MODIFY COLUMN `date_created` datetime NULL DEFAULT NULL
_sql

);	ze\dbAdm::revision( 185
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`
	MODIFY COLUMN `hyperlink` text
_sql

);