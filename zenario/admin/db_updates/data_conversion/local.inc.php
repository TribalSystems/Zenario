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



//Code for converting table data, after the more drastic database structure changes




//Populate the menu_hierarchy table
if (needRevision(28550)) {
	$sql = "
		TRUNCATE TABLE ". DB_NAME_PREFIX. "menu_hierarchy";
	sqlQuery($sql);
	
	
	$sql = "
		SELECT id
		FROM ". DB_NAME_PREFIX. "menu_sections";
	
	$result = sqlQuery($sql);
	while ($row = sqlFetchAssoc($result)) {
		recalcMenuHierarchy($row['id']);
	}
	
	revision(28550);
}


//There used to be a bug where new Menu Sections were not being recorded.
//This is now fixed, but I need to add an update that reruns the
//recalcMenuPositionsTopLevel() function to fix any bad data
if (needRevision(28650)) {
	recalcMenuPositionsTopLevel();
	revision(28650);
}


//Look through the banners that have been created, trying to migrate their frameworks as best we can
//This won't be perfect, but should at least reduce the amount of changes that need to be made manually
if (needRevision(28710)) {
	if ($bannerModuleId = getRow('modules', 'id', array('class_name' => 'zenario_banner', 'status' => 'module_running'))) {
		
		//Look for all Banner Plugins that used More Links
		//Also try and look up the visitor phrase that would be used for the "More Link" text
		$sql = "
			SELECT pi.id, pi.framework, pi.content_id, vp.local_text
			FROM ". DB_NAME_PREFIX. "plugin_instances AS pi
			LEFT JOIN ". DB_NAME_PREFIX. "content AS c
			   ON pi.content_id = c.id
			  AND pi.content_type = c.type
			LEFT JOIN ". DB_NAME_PREFIX. "visitor_phrases AS vp
			   ON vp.code = '_FIND_OUT_MORE'
			  AND vp.module_class_name = 'zenario_banner'
			  AND vp.language_id = IFNULL(c.language_id, '". sqlEscape(setting('default_language')). "')
			WHERE pi.framework LIKE '%more_link%' 
			  AND pi.module_id = ". (int) $bannerModuleId;
	
		$result = sqlQuery($sql);
		while ($plugin = sqlFetchAssoc($result)) {
			//A couple of the removed frameworks have direct replacements, so we may as well fix them now
			if ($plugin['framework'] == 'image_then_title_then_text_with_more_link') {
				updateRow('plugin_instances', array('framework' => 'image_then_title_then_text'), $plugin['id']);
			}
			if ($plugin['framework'] == 'title_then_image_then_text_with_more_link') {
				updateRow('plugin_instances', array('framework' => 'title_then_image_then_text'), $plugin['id']);
			}
			
			if (!$plugin['local_text']) {
				$plugin['local_text'] = 'Find out more';
			}
			
			//Insert a new setting for the "find out more" text
			insertRow(
				'plugin_settings',
				array(
					'instance_id' => $plugin['id'],
					'name' => 'more_link_text',
					'nest' => 0,
					'value' => $plugin['local_text'],
					'is_content' => $plugin['content_id']? 'version_controlled_content' : 'synchronized_setting',
					'format' => 'translatable_text'),
				true);
		}
	}
	
	revision(28710);
}


//Similar to above, look for Content Summary Lists that use the "More" link and replace this with a phrase
if (needRevision(29560)) {
	$sql = "
		SELECT pi.id, pi.module_id, pi.content_id, vp.local_text
		FROM ". DB_NAME_PREFIX. "modules AS m
		INNER JOIN ". DB_NAME_PREFIX. "plugin_instances AS pi
		   ON pi.module_id = m.id
		LEFT JOIN ". DB_NAME_PREFIX. "content AS c
		   ON pi.content_id = c.id
		  AND pi.content_type = c.type
		LEFT JOIN ". DB_NAME_PREFIX. "visitor_phrases AS vp
		   ON vp.code = '_MORE'
		  AND vp.module_class_name = 'zenario_content_list'
		  AND vp.language_id = IFNULL(c.language_id, '". sqlEscape(setting('default_language')). "')
		WHERE m.class_name LIKE 'zenario_content_list%'";

	$result = sqlQuery($sql);
	while ($plugin = sqlFetchAssoc($result)) {
		if (!$plugin['local_text']) {
			$plugin['local_text'] = 'More...';
		}
		
		//Insert a new setting for the "more" text
		insertRow(
			'plugin_settings',
			array(
				'instance_id' => $plugin['id'],
				'name' => 'more_link_text',
				'nest' => 0,
				'value' => $plugin['local_text'],
				'is_content' => $plugin['content_id']? 'version_controlled_content' : 'synchronized_setting',
				'format' => 'translatable_text'),
			true);
	}
	
	revision(29560);
}


//Set a value for the organizer_title site setting
if (needRevision(29570)) {
	setSetting('organizer_title', 'Organizer for '. primaryDomain());
	revision(29570);
}


if (needRevision(30130)) {
	foreach (getRowsArray(
		'documents',
		array('id', 'file_id', 'filename', 'file_datetime'),
		array('type' => 'file')
	) as $document) {
		if ($fileDetails = getRow('files', array('filename', 'created_datetime'), array('id' => $document['file_id']))) {
			
			if (!$document['filename']) {
				updateRow('documents', array('filename' => $fileDetails['filename']), array('id' => $document['id']));
			}
			if (!$document['file_datetime']) {
				updateRow('documents', array('file_datetime' => $fileDetails['created_datetime']), array('id' => $document['id']));
			}
		}
	}
	
	revision(30130);
}


//Rename some old site settings with "storekeeper" in the name
if (needRevision(30150)) {
	
	if (setting('storekeeper_page_size') && !setting('organizer_page_size')) {
		setSetting('organizer_page_size', setting('storekeeper_page_size'), true, false);
		deleteRow('site_settings', array('name' => 'storekeeper_page_size'));
	}
	if (setting('storekeeper_date_format') && !setting('organizer_date_format')) {
		setSetting('organizer_date_format', setting('storekeeper_date_format'), true, false);
		deleteRow('site_settings', array('name' => 'storekeeper_date_format'));
	}
	
	revision(30150);
}


//Remove the library_images tables if they were ever created for people running early versions of 7.0.5
if (needRevision(30500)) {
	deleteDataset('library_images');
	
	revision(30500);
}


//Change the checksum column in the files table to use base 64 rather than base 16.
if (needRevision(30600)) {
	$sql = "
		SELECT id, checksum
		FROM ". DB_NAME_PREFIX. "files
		WHERE LENGTH(checksum) = 32";

	$result = sqlQuery($sql);
	while ($file = sqlFetchAssoc($result)) {
		updateRow('files', array('checksum' => base16To64($file['checksum'])), $file['id']);
	}
	
	revision(30600);
}

//Populate the short checksum field
if (needRevision(30625)) {
	setSetting('short_checksum_length', 6);
	updateShortChecksums(true);
	
	revision(30625);
}

//Set the "archived" flag in the inline_images table
if (needRevision(30700)) {
	flagImagesInArchivedVersions();
	
	revision(30700);
}


//Scan anything related to a Content Item and sync the inline_images table properly
if (needRevision(30731)) {
	
	$result = getRows('content', array('id', 'type', 'visitor_version', 'admin_version'), array(), array('type', 'id'));
	while ($row = sqlFetchAssoc($result)) {
		
		if ($row['visitor_version']) {
			syncInlineFileContentLink($row['id'], $row['type'], $row['visitor_version']);
		}
		if ($row['admin_version'] && $row['admin_version'] != $row['visitor_version']) {
			syncInlineFileContentLink($row['id'], $row['type'], $row['admin_version']);
		}
	}
	
	revision(30731);
}

//Update the copie
if (needRevision(30840)) {
	$docstoreDir = setting('docstore_dir');

	$skWidth = 180;
	$skHeight = 130;
	$skListWidth = 24;
	$skListHeight = 23;

	$sql = "
		SELECT id, location, path, filename, data, mime_type, width, height
		FROM ". DB_NAME_PREFIX. "files
		WHERE mime_type IN ('image/gif', 'image/png', 'image/jpeg', 'image/pjpeg')
		  AND width != 0
		  AND height != 0
		  AND (organizer_data IS NULL
			OR organizer_width > ". (int) $skWidth. "
			OR organizer_height > ". (int) $skHeight. "
			OR (	width > ". (int) $skWidth. "
				AND height > ". (int) $skHeight. "
				AND organizer_width < ". (int) $skWidth. "
				AND organizer_height < ". (int) $skHeight. "
		))";
	$result = sqlQuery($sql);

	while($img = sqlFetchAssoc($result)) {
		if ($img['location'] == 'docstore') {
			if ($docstoreDir && is_file($docstoreDir. '/'. $img['path'])) {
				$img['data'] = file_get_contents($docstoreDir. '/'. $img['path']. '/'. $img['filename']);
			} else {
				continue;
			}
		}
	
		resizeImageString($img['data'], $img['mime_type'], $img['width'], $img['height'], $skWidth, $skHeight);
		$img['data'] = "
			UPDATE ". DB_NAME_PREFIX. "files SET
				organizer_data = '". sqlEscape($img['data']). "',
				organizer_width = ". (int) $img['width']. ",
				organizer_height = ". (int) $img['height']. "
			WHERE id = ". (int) $img['id'];
		sqlUpdate($img['data']);
		unset($img);
	}

	$sql = "
		SELECT id, location, path, filename, data, mime_type, width, height
		FROM ". DB_NAME_PREFIX. "files
		WHERE mime_type IN ('image/gif', 'image/png', 'image/jpeg', 'image/pjpeg')
		  AND width != 0
		  AND height != 0
		  AND (organizer_list_data IS NULL
			OR organizer_list_width > ". (int) $skListWidth. "
			OR organizer_list_height > ". (int) $skListHeight. "
			OR (	width > ". (int) $skListWidth. "
				AND height > ". (int) $skListHeight. "
				AND organizer_list_width < ". (int) $skListWidth. "
				AND organizer_list_height < ". (int) $skListHeight. "
		))";
	$result = sqlQuery($sql);

	while($img = sqlFetchAssoc($result)) {
		if ($img['location'] == 'docstore') {
			if ($docstoreDir && is_file($docstoreDir. '/'. $img['path'])) {
				$img['data'] = file_get_contents($docstoreDir. '/'. $img['path']. '/'. $img['filename']);
			} else {
				continue;
			}
		}
	
		resizeImageString($img['data'], $img['mime_type'], $img['width'], $img['height'], $skListWidth, $skListHeight);
		$img['data'] = "
			UPDATE ". DB_NAME_PREFIX. "files SET
				organizer_list_data = '". sqlEscape($img['data']). "',
				organizer_list_width = ". (int) $img['width']. ",
				organizer_list_height = ". (int) $img['height']. "
			WHERE id = ". (int) $img['id'];
		sqlUpdate($img['data']);
		unset($img);
	}
	
	revision(30840);
}
