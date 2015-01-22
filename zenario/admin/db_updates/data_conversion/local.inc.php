<?php
/*
 * Copyright (c) 2014, Tribal Limited
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

//Attempt to drop all of the views that we used to use in versions before 5.1.2
//I'm doing this in PHP, just in case there are permissions errors
if (needRevision(7630)) {
	
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_admin_reg_template_list`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_diagnostics_report_audio`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_diagnostics_report_document`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_diagnostics_report_event`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_diagnostics_report_extranet`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_diagnostics_report_forum`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_diagnostics_report_html`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_diagnostics_report_news`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_diagnostics_report_nlar`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_diagnostics_report_picture`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_diagnostics_report_video`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_diagnostics_report`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_history_audio`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_history_document`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_history_event`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_history_extranet`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_history_forum`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_history_html`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_history_news`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_history_nlar`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_history_picture`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_history_video`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_history`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_skins`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_template_content_document_status`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_template_content_event_status`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_template_content_extranet_status`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_template_content_forum_status`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_template_content_html_status`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_template_content_news_status`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_template_content_nlar_status`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_template_content_picture_status`");
	@sqlSelect("DROP VIEW IF EXISTS `". DB_NAME_PREFIX. "v_vlp_status`");

	
	revision(7630);
}


//Convert the format of inline image/movie URLs
if (needRevision(14020)) {
	//Get a list of checksums in the old format
	$sql = "
		SELECT checksum, MD5(CONCAT(filename, checksum))
		FROM ". DB_NAME_PREFIX. "files";
	$result = sqlQuery($sql);
	
	$checksums = array();
	while ($row = sqlFetchRow($result)) {
		$checksums[$row[1]] = $row[0];
	}
	
	
	//Get each content area (which will have been converted into HTML snippets)
	$sql = "
		SELECT instance_id, nest, value
		FROM ". DB_NAME_PREFIX. "plugin_settings
		WHERE name = 'html'
		  AND value LIKE '%cmsincludes/%'";
	$result = sqlQuery($sql);
	
	while ($row = sqlFetchAssoc($result)) {
		$html = '';
		$htmlChanged = false;
		
		//Parse the html, looking for links in the old format
		$links = preg_split('@cmsincludes/(\w+)\.php\?([^\'">]+)@s', $row['value'], -1,  PREG_SPLIT_DELIM_CAPTURE);
		$c = count($links) - 1;
		
		//Find the details of each link
		for ($i=0; $i < $c; $i += 3) {
			//Remember the html surrounding each link
			$html .= $links[$i];
			
			$params = array();
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
				//If this file is linked by cID/filename
				if (!empty($params['cID']) && !empty($params['cType'])) {
					//Attempt to look up the checksum of this image, or the checksum of the closest match if some of the paramters are wrong
					$sql = "
						SELECT f.checksum, f.filename
						FROM ". DB_NAME_PREFIX. "inline_file_link AS l
						INNER JOIN ". DB_NAME_PREFIX. "files AS f
						   ON l.file_id = f.id
						WHERE l.foreign_key_to = 'content'
						  AND l.foreign_key_id = ". (int) $params['cID']. "
						  AND l.foreign_key_char = '". sqlEscape($params['cType']). "'
						ORDER BY
							  f.filename = '". sqlEscape(trim(rawurldecode(arrayKey($params, 'filename')))). "' DESC,
							  l.foreign_key_version = ". (int) arrayKey($params, 'cVersion'). " DESC
						LIMIT 1";
					
					if (($result2 = sqlQuery($sql)) && ($row2 = sqlFetchAssoc($result2))) {
						$html .= 'zenario/file.php?c='. $row2['checksum']. $amp. 'filename='. rawurlencode($row2['filename']);
						$htmlChanged = $doneSomething = true;
					}
				
				//If this file is already linked by checksum/filename
				} elseif ($checksum = ifNull(arrayKey($params, 'c'), arrayKey($params, 'checksum'))) {
					//No lookup needed; just need to change the path of the link
					$html .= 'zenario/file.php?c='. ifNull(arrayKey($checksums, $checksum), $checksum);
					
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
			updateRow('plugin_settings', array('value' => $html), array('instance_id' => $row['instance_id'], 'nest' => $row['nest'], 'name' => 'html'));
		}
	}

	revision(14020);
}


//Move everything (except Superadmins) from the global database to the local database
//(Superadmins will be handled with a later update/feature change)
if (needRevision(14870)) {
	
	//Attempt to get the host name
	$host = httpHost();
	$hostWOPort = httpHostWithoutPort();
	
	//Connect to the global database to copy some things out of it
	$siteSettings = array();
	$spareDomainNames = array();
	
	if (connectGlobalDB()) {
		try {
			$result = sqlSelect("SHOW TABLES LIKE '". DB_NAME_PREFIX_GLOBAL. "global_settings'");
			if (sqlFetchRow($result)) {
				//Attempt to work out what the site name of the current site is.
				if (!($sitename = getRow('site_registry', 'sitename', array('goto_admin_url' => $host)))
				 && !($sitename = getRow('site_registry', 'sitename', array('goto_admin_url' => $hostWOPort)))
				 && !($sitename = getRow('public_urls', 'sitename', array('public_url' => $host)))
				 && !($sitename = getRow('public_urls', 'sitename', array('public_url' => $hostWOPort)))
				 && !($sitename = getRow('spare_urls', 'sitename', array('requested_url' => $host)))
				 && !($sitename = getRow('spare_urls', 'sitename', array('requested_url' => $hostWOPort)))) {
					$sitename = 'myzenariosite';
				}
				
				//Look up the directory paths from the global_settings table
				$siteSettings['backup_dir'] = (string) getRow('global_settings', 'value', array('name' => 'backup_dir'));
				$siteSettings['docstore_dir'] = (string) getRow('global_settings', 'value', array('name' => 'docstore_dir'));
				
				if (!is_dir($siteSettings['backup_dir'])) {
					unset($siteSettings['backup_dir']);
				}
				if (!is_dir($siteSettings['docstore_dir'])) {
					unset($siteSettings['docstore_dir']);
				}
				
				//Update the format of the existing BACKUP_DIR, DOCSTORE_DIR and DOCSTORE_DIR values to add
				//the SITENAME into the path so that old paths do not automatically break with the changes
				//to the format of those paths.
				foreach ($siteSettings as $settingName => &$settingValue) {
					$settingValue = str_replace('//', '/', $settingValue. '/'. $sitename);
				}
				
				//Look up the Primary URL and SSL settings from the site registry table
				$siteSettings['primary_domain'] = (string) getRow('site_registry', 'goto_admin_url', array('sitename' => $sitename));
				$siteSettings['admin_use_ssl'] = 'force' == (string) getRow('site_registry', 'admin_use_ssl', array('sitename' => $sitename));
				
				//Look up the URLs from the spare_urls table
				if ($urlContentRedirectResult = getRows(
					'spare_urls',
					array('requested_url', 'content_id', 'content_type'),
					array('sitename' => defined('SITENAME')? SITENAME : 'myzenariosite'),
					array()
				)) {
					while ($spareDomainName = sqlFetchAssoc($urlContentRedirectResult)) {
						$spareDomainNames[] = $spareDomainName;
					}
				}
			}
		} catch (Exception $e) {
		}
		connectLocalDB();
	}
	
	
	if (empty($siteSettings['backup_dir']) && defined('BACKUP_DIR') && is_dir(BACKUP_DIR)) {
		$siteSettings['backup_dir'] = BACKUP_DIR;
	}
	if (empty($siteSettings['docstore_dir']) && defined('DOCSTORE_DIR') && is_dir(DOCSTORE_DIR)) {
		$siteSettings['docstore_dir'] = DOCSTORE_DIR;
	}
	
	if (empty($siteSettings['backup_dir']) && is_dir($dir = suggestDir('backup_dir'))) {
		$siteSettings['backup_dir'] = $dir;
	}
	if (empty($siteSettings['docstore_dir']) && is_dir($dir = suggestDir('docstore_dir'))) {
		$siteSettings['docstore_dir'] = $dir;
	}
	
	if (empty($siteSettings['backup_dir']) && is_dir($dir = suggestDir('backup'))) {
		$siteSettings['backup_dir'] = $dir;
	}
	if (empty($siteSettings['docstore_dir']) && is_dir($dir = suggestDir('docstore'))) {
		$siteSettings['docstore_dir'] = $dir;
	}
	
	foreach ($siteSettings as $settingName => &$settingValue) {
		setSetting($settingName, $settingValue);
	}
	
	foreach ($spareDomainNames as $spareDomainName) {
		insertRow('spare_domain_names', $spareDomainName);
	}
	
	unset($dir);
	unset($host);
	unset($hostWOPort);
	unset($siteSettings);
	unset($settingValue);
	unset($settingName);
	unset($spareDomainName);
	unset($spareDomainNames);
	revision(14870);
}


//Add document files to the files table
if (needRevision(14880)) {
	$result = sqlSelect("SHOW TABLES LIKE '". DB_NAME_PREFIX. "_old_content_document'");
	if (sqlFetchRow($result)) {
		
		$successes = 0;
		$failures = '';
		$result = getRows('_old_content_document', array('id', 'version', 'created_datetime', 'filename', 'size', 'mime_type'), array(), array('id', 'version'));
		
		while ($doc = sqlFetchAssoc($result)) {
			if (($fullPath = docstoreFilePath($path = $doc['id']. '/'. $doc['version']))
			 && ($checksum = md5(file_get_contents($fullPath)))) {
				$fileId = setRow(
					'files',
					array(
						'created_datetime' => $doc['created_datetime'],
						'filename' => basename($fullPath),
						'mime_type' => ifNull($doc['mime_type'], documentMimeType($fullPath)),
						'size' => filesize($fullPath),
						'location' => 'docstore',
						'path' => $path),
					array('usage' => 'content', 'checksum' => $checksum));
				
				updateRow('versions', array('file_id' => $fileId, 'filename' => basename($fullPath)), array('id' => $doc['id'], 'type' => 'document', 'version' => $doc['version']));
				
				++$successes;
			} else {
				$failures .= ($failures? "\n" : ''). setting('docstore_dir'). '/'. $path. '/'. $doc['filename'];
			}
		}
		
		if (!empty($failures) && $successes == 0) {
			echo
				'<!--Message_Type:None-->',
				'<!--Modal-->',
				'<!--Reload_Button:', adminPhrase('Retry and Resume'), '-->',
				'<p>', adminPhrase('The migration script cannot read the documents in your docstore directory:'), '</p>',
				'<p><textarea rows="10" cols="43">', htmlspecialchars($failures), '</textarea></p>',
				'<p>', adminPhrase('Please correct this to continue.'), '</p>';
			exit;
		}
	}
	
	revision(14880);
}


//Convert DEFAULT_LANGUAGE to a Site Setting
if (needRevision(15130)) {
	
	if (defined('DEFAULT_LANGUAGE') && checkRowExists('languages', DEFAULT_LANGUAGE)) {
		setSetting('default_language', DEFAULT_LANGUAGE);
	
	} elseif ($langId = getRow('languages', 'id', array())) {
		setSetting('default_language', $langId);
	
	} else {
		setSetting('default_language', '');
	}
	unset($langId);
	
	revision(15130);
}


//Scan anything related to a Content Item and sync the inline_file_link table properly
if (needRevision(15145)) {
	
	$result = getRows('content', array('id', 'type', 'visitor_version', 'admin_version'), array(), array('type', 'id'));
	while ($row = sqlFetchAssoc($result)) {
		
		if ($row['visitor_version']) {
			syncInlineFileContentLink($row['id'], $row['type'], $row['visitor_version']);
		}
		if ($row['admin_version'] && $row['admin_version'] != $row['visitor_version']) {
			syncInlineFileContentLink($row['id'], $row['type'], $row['admin_version']);
		}
	}
	
	revision(15145);
}


//Convert the format of inline image/movie URLs in email templates
//(Note - this is mostly identical to the code above for Content areas)
if (needRevision(16220)) {
	//Get a list of checksums in the old format
	$sql = "
		SELECT checksum, MD5(CONCAT(filename, checksum))
		FROM ". DB_NAME_PREFIX. "files";
	$result = sqlQuery($sql);
	
	$checksums = array();
	while ($row = sqlFetchRow($result)) {
		$checksums[$row[1]] = $row[0];
	}
	
	
	//Get each content area (which will have been converted into HTML snippets)
	$sql = "
		SELECT id, body
		FROM ". DB_NAME_PREFIX. "email_templates
		WHERE body LIKE '%cmsincludes/%'";
	$result = sqlQuery($sql);
	
	while ($row = sqlFetchAssoc($result)) {
		$html = '';
		$htmlChanged = false;
		
		//Parse the html, looking for links in the old format
		$links = preg_split('@cmsincludes/(\w+)\.php\?([^\'">]+)@s', $row['body'], -1,  PREG_SPLIT_DELIM_CAPTURE);
		$c = count($links) - 1;
		
		//Find the details of each link
		for ($i=0; $i < $c; $i += 3) {
			//Remember the html surrounding each link
			$html .= $links[$i];
			
			$params = array();
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
				if ($checksum = ifNull(arrayKey($params, 'c'), arrayKey($params, 'checksum'))) {
					//Change the path of the link
					$html .= 'zenario/file.php?c='. ifNull(arrayKey($checksums, $checksum), $checksum);
					
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
			updateRow('email_templates', array('body' => $html), array('id' => $row['id']));
		}
	}

	revision(16220);
}


//Continue to populate the format column on the plugin settings table, trying to set it to HTML appropriately
if (needRevision(16910)) {
	$result = getRows('plugin_settings', array('instance_id', 'name', 'nest', 'format', 'value'), array());
	while ($row = sqlFetchAssoc($result)) {
		$row['value'] = trim($row['value']);
		$format = $row['format'];
		
		if (!$row['value']) {
			unset($row['value']);
			unset($row['format']);
			updateRow('plugin_settings', array('format' => 'empty'), $row);
		
		} elseif (html_entity_decode($row['value']) != $row['value'] || strip_tags($row['value']) != $row['value']) {
			unset($row['value']);
			unset($row['format']);
			updateRow('plugin_settings', array('format' => $format == 'translatable_text'? 'translatable_html' : 'html'), $row);
		}
	}
	
	revision(16910);
}


//Attempt to fix any previous bad data, for Menu Nodes without equivalences
if (needRevision(17440)) {
	 $sql = "
		SELECT sec.equiv_id, sec.content_type, MIN(sec.id) AS id
		FROM ". DB_NAME_PREFIX. "menu_nodes AS sec
		LEFT JOIN ". DB_NAME_PREFIX. "menu_nodes as pri
		   ON pri.target_loc = 'int'
		  AND pri.equiv_id = sec.equiv_id
		  AND pri.content_type = sec.content_type
		  AND pri.redundancy = 'primary'
		WHERE sec.target_loc = 'int'
		  AND sec.redundancy = 'secondary'
		  AND pri.redundancy IS NULL
		GROUP BY sec.equiv_id, sec.content_type";
	
	$result = sqlQuery($sql);
	while ($row = sqlFetchAssoc($result)) {
		$sql = "
			UPDATE ". DB_NAME_PREFIX. "menu_nodes
			SET redundancy = 'primary'
			WHERE id = ". (int) $row['id'];
		sqlQuery($sql);
	}
	
	revision(17440);
}


//Set any document extracts
if (needRevision(19650)) {
	
	$result = getRows(
		'content',
		array('id', 'type', 'admin_version', 'visitor_version'),
		array('type' => 'document', 'status' => array('!1' => 'trashed', '!2' => 'deleted')));
	
	while ($row = sqlFetchAssoc($result)) {
		if ($row['admin_version']) {
			updatePlainTextExtract($row['id'], $row['type'], $row['admin_version']);
		}
		if ($row['visitor_version'] && $row['visitor_version'] != $row['admin_version']) {
			updatePlainTextExtract($row['id'], $row['type'], $row['visitor_version']);
		}
	}

	revision(19650);
}


//Add a working copy for large images
if (needRevision(19800)) {
	if (!setting('thumbnail_wc_image_size')) {
		setSetting('thumbnail_wc_image_size', 300);
	}
	
	rerenderWorkingCopyImages();
	revision(19800);
}


//Convert the format of the Cookie Consent Site Setting from a checkbox to a select list
if (needRevision(19890)) {
	if (setting('cookie_require_consent')) {
		setSetting('cookie_require_consent', 'explicit');
	}
	
	revision(19890);
}


//Set a site id key if one is not already set.
if (needRevision(20700)) {
	if (!setting('site_id')) {
		//This used to have a different name, so check the old name first and rename it.
		if (setting('encryption_key')) {
			setSetting('site_id', setting('encryption_key'));
		} else {
			setSetting('site_id', generateRandomSiteIdentifierKey());
		}
		deleteRow('site_settings', array('name' => 'encryption_key'));
		
		//Changing the site key will log the Admin out, so we immediately need to set their session again.
		setAdminSession(session('admin_userid'), session('admin_global_id'));
	}
	
	revision(20700);
}


//IPs used to be stored as an integer. We should now store them as a string so we can handle Proxy lists and IPV6
if (needRevision(20775)) {
	$sql = "
		SELECT DISTINCT ip
		FROM ". DB_NAME_PREFIX. "user_content_accesslog
		WHERE ip NOT LIKE '%.%'
		  AND ip NOT LIKE '%:%'";
	
	$result = sqlQuery($sql);
	while ($row = sqlFetchRow($result)) {
		if ($row[0] && is_numeric($row[0])) {
			updateRow('user_content_accesslog', array('ip' => long2ip($row[0])), array('ip' => $row[0]));
		}
	}
	
	revision(20775);
}


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
}