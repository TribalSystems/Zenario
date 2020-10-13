<?php
/*
 * Copyright (c) 2020, Tribal Limited
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




//Some old updates. Not currently needed but I'm just keeping the code
//here so I can easily find it if I ever need to re-issue them again.

/*
//Scan anything related to a Content Item and sync the inline_images table properly
if (ze\dbAdm::needRevision(30731)) {
	
	$result = ze\row::query('content_items', ['id', 'type', 'visitor_version', 'admin_version'], [], ['type', 'id']);
	while ($row = ze\sql::fetchAssoc($result)) {
		
		if ($row['visitor_version']) {
			ze\contentAdm::syncInlineFileContentLink($row['id'], $row['type'], $row['visitor_version']);
		}
		if ($row['admin_version'] && $row['admin_version'] != $row['visitor_version']) {
			ze\contentAdm::syncInlineFileContentLink($row['id'], $row['type'], $row['admin_version']);
		}
	}
	
	ze\dbAdm::revision(30731);
}

//Update Organizer's image thumbnails
if (ze\dbAdm::needRevision(31200)) {
	$docstoreDir = ze::setting('docstore_dir');
	
	foreach ([
		['thumbnail_180x130_data', 'thumbnail_180x130_width', 'thumbnail_180x130_height', 180, 130],
		['thumbnail_64x64_data', 'thumbnail_64x64_width', 'thumbnail_64x64_height', 64, 64],
		['thumbnail_24x23_data', 'thumbnail_24x23_width', 'thumbnail_24x23_height', 24, 23]
	] as $c) {

		$sql = "
			SELECT id, location, path, filename, data, mime_type, width, height
			FROM ". DB_PREFIX. "files
			WHERE mime_type IN ('image/gif', 'image/png', 'image/jpeg')
			  AND width != 0
			  AND height != 0
			  AND (`". ze\escape::sql($c[0]). "` IS NULL
				OR `". ze\escape::sql($c[1]). "` > ". (int) $c[3]. "
				OR `". ze\escape::sql($c[2]). "` > ". (int) $c[4]. "
				OR (	width > ". (int) $c[3]. "
					AND height > ". (int) $c[4]. "
					AND `". ze\escape::sql($c[1]). "` < ". (int) $c[3]. "
					AND `". ze\escape::sql($c[2]). "` < ". (int) $c[4]. "
			))";
		$result = ze\sql::select($sql);

		while($img = ze\sql::fetchAssoc($result)) {
			if ($img['location'] == 'docstore') {
				if ($docstoreDir && is_file($docstoreDir. '/'. $img['path'])) {
					$img['data'] = file_get_contents($docstoreDir. '/'. $img['path']. '/'. $img['filename']);
				} else {
					continue;
				}
			}
	
			ze\file::resizeImageString($img['data'], $img['mime_type'], $img['width'], $img['height'], $c[3], $c[4]);
			$img['data'] = "
				UPDATE ". DB_PREFIX. "files SET
					`". ze\escape::sql($c[0]). "` = '". ze\escape::sql($img['data']). "',
					`". ze\escape::sql($c[1]). "` = ". (int) $img['width']. ",
					`". ze\escape::sql($c[2]). "` = ". (int) $img['height']. "
				WHERE id = ". (int) $img['id'];
			ze\sql::update($img['data']);
			unset($img);
		}
	}
	
	ze\dbAdm::revision(31200);
}

//Attempt to clear the cache/frameworks directory
if (ze\dbAdm::needRevision(36380)) {
	if (is_dir(CMS_ROOT. 'cache/frameworks')
	 && !ze\server::isWindows()
	 && ze\server::execEnabled()) {
		exec('rm -r '. escapeshellarg(CMS_ROOT. 'cache/frameworks'));
		ze\cache::cleanDirs();
	}
	
	ze\dbAdm::revision(36380);
}
*/


//Migrate the "other modes" plugin settings from a multiple-checkboxes field to
//multiple single-checkboxes fields.
if (ze\dbAdm::needRevision(39440)) {
	$sql = "
		SELECT *
		FROM ". DB_PREFIX. "plugin_settings
		WHERE name = 'other_modes'
		  AND `value` != ''
		  AND `value` IS NOT NULL";
	
	$result = ze\sql::select($sql);
	while ($row = ze\sql::fetchAssoc($result)) {
		
		foreach (ze\ray::explodeAndTrim($row['value']) as $modeOrFeature) {
			$row['name'] = 'enable.'. $modeOrFeature;
			$row['value'] = 1;
			ze\row::insert('plugin_settings', $row, true);
		}
	}
	ze\dbAdm::revision(39440);
}

//Migrate data from documents short_checksum_list column to new table.
if (ze\dbAdm::needRevision(40190)) {
	$result = ze\row::query('documents', ['id', 'short_checksum_list'], []);
	while ($document = ze\sql::fetchAssoc($result)) {
		if ($document['short_checksum_list']) {
			$redirects = explode(',', $document['short_checksum_list']);
			foreach ($redirects as $path) {
				ze\sql::update('
					INSERT IGNORE INTO ' . DB_PREFIX . 'document_public_redirects (`document_id`, `path`) 
					VALUES (' . (int)$document['id']. ', "' . ze\escape::sql($path) . '")'
				);
			}
		}
	}
	
	ze\sql::update('
		ALTER TABLE `'. DB_PREFIX. 'documents`
		DROP COLUMN `short_checksum_list`'
	);

	ze\dbAdm::revision(40190);
}

//Correct a bug where uploading an image into a nest did not flag the image as being used.
if (ze\dbAdm::needRevision(40670)) {
	$sql = "
		SELECT id, content_id, content_type, content_version, is_nest, is_slideshow
		FROM ". DB_PREFIX. "plugin_instances
		WHERE content_id = 0
		  AND module_id IN (
			SELECT module_id
			FROM ". DB_PREFIX. "modules
			WHERE class_name IN ('zenario_plugin_nest', 'zenario_slideshow')
		)";
	
	$result = ze\sql::select($sql);
	while ($row = ze\sql::fetchAssoc($result)) {
		ze\contentAdm::resyncLibraryPluginFiles($row['id'], $row);
	}
	ze\dbAdm::revision(40670);
}


//Look for conductors with breadcrumbs set on each tab, and move the breadcrumbs
//to the nest level
if (ze\dbAdm::needRevision(42450)) {
	
	if (ze\module::inc('zenario_plugin_nest')) {
	
		//For each nest with breadcrumbs in it, get all of the ids of the breadcrumb plugins
		//inside the nest, and what their settings are set to
		$sql = "
			SELECT
				instance_id,
				settings,
				MAX(cols) as cols,
				GROUP_CONCAT(egg_id) as eggs,
				COUNT(DISTINCT settings) AS cnt
			FROM (
				SELECT
					pi.id AS instance_id,
					np.id AS egg_id,
					np.slide_num,
					np.cols,
					GROUP_CONCAT(ps.value ORDER BY ps.name SEPARATOR '`') AS settings
				FROM ". DB_PREFIX. "plugin_instances AS pi
				INNER JOIN ". DB_PREFIX. "nested_plugins AS np
				   ON np.instance_id = pi.id
				  AND np.module_id  = (SELECT id FROM ". DB_PREFIX. "modules WHERE class_name = 'zenario_breadcrumbs')
				INNER JOIN ". DB_PREFIX. "plugin_settings AS ps
				   ON ps.instance_id = pi.id
				  AND ps.egg_id = np.id
				  AND ps.name IN ('add_conductor_slides', 'breadcrumb_trail', 'breadcrumb_trail_separator', 'menu_section')
				WHERE pi.content_id = 0
				  AND pi.module_id = (SELECT id FROM ". DB_PREFIX. "modules WHERE class_name = 'zenario_plugin_nest')
				GROUP BY
					pi.id,
					np.id,
					np.slide_num,
					np.cols
			) AS x
			GROUP BY instance_id";
	
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			
			//Only handle the cases where the settings were the same across the whole nest
			if ($row['cnt'] == 1) {
				$settings = explode('`', $row['settings']);
				
				//Check the settings match the pattern we're looking for - will be something like "1`site_home_page``6"
				if ($settings[0] && count($settings) == 4) {
					
					//Add the settings from the breadcrumb plugin to the top level
					$key = ['instance_id' => $row['instance_id'], 'egg_id' => 0];
					
					$key['name'] = 'bc_add';
					ze\row::set('plugin_settings', ['value' => 1], $key);
					
					$key['name'] = 'bc_cols';
					ze\row::set('plugin_settings', ['value' => $row['cols']], $key);
					
					$key['name'] = 'bc_breadcrumb_trail';
					ze\row::set('plugin_settings', ['value' => $settings[1]], $key);
					
					$key['name'] = 'bc_breadcrumb_trail_separator';
					ze\row::set('plugin_settings', ['value' => $settings[2]], $key);
					
					$key['name'] = 'bc_menu_section';
					ze\row::set('plugin_settings', ['value' => $settings[3]], $key);
					
					
					//Remove the old breadcrumb plugins
					foreach (ze\ray::explodeAndTrim($row['eggs'], true) as $eggId) {
						zenario_plugin_nest::removePlugin($eggId, $row['instance_id']);
					}
				}
			}
		}
	}
	
	ze\dbAdm::revision(42450);
}


//Migate the old "custom columns/custom buttons" to the new format
if (ze\dbAdm::needRevision(42460)) {
	
	//Look for custom columns and buttons
	$sql = "
		SELECT
			cc.instance_id,
			cc.egg_id,
			cc.name,
			cc.value AS codename,
			ct.value AS thing
		FROM ". DB_PREFIX. "plugin_settings AS cc
		INNER JOIN ". DB_PREFIX. "plugin_settings AS ct
		   ON ct.instance_id = cc.instance_id
		  AND ct.egg_id = cc.egg_id
		  AND SUBSTR(ct.name, 1, 6) = SUBSTR(cc.name, 1, 6)
		  AND ct.name like 'cus\\_%\\_thing'
		WHERE cc.name like 'cus\\_%\\_codename'
		ORDER BY cc.instance_id, cc.egg_id";
	
	$tuix = [];
	$lastId = 0;
	$result = ze\sql::select($sql);
	while ($row = ze\sql::fetchAssoc($result)) {
		
		if ($lastId != $row['egg_id']) {
			$lastId = $row['egg_id'];
			$tuix = [];
		}
		
		if ($num = (int) preg_replace('@\D@', '', $row['name'])) {
			$prefix = 'cus_'. $num. '_prop_';
			
			//Look for all of the properties saved for this column or button
			$sql = "
				SELECT name, `value`
				FROM ". DB_PREFIX. "plugin_settings
				WHERE instance_id = ". (int) $row['instance_id']. "
				  AND egg_id = ". (int) $row['egg_id']. "
				  AND name LIKE '". ze\escape::like($prefix). "%'";
	
			$result2 = ze\sql::select($sql);
			while ($row2 = ze\sql::fetchAssoc($result2)) {
				
				//Read the old format, and turn it into an array
				$prop = explode('.', ze\ring::chopPrefix($prefix, $row2['name']));
				
				if (!isset($tuix[$row['thing']])) {
					$tuix[$row['thing']] = [];
				}
				
				if (!isset($tuix[$row['thing']][$row['codename']])) {
					$tuix[$row['thing']][$row['codename']] = [];
				}
				
				if (isset($prop[1])) {
					if (!isset($tuix[$row['thing']][$row['codename']][$prop[0]])) {
						$tuix[$row['thing']][$row['codename']][$prop[0]] = [];
					}
					$tuix[$row['thing']][$row['codename']][$prop[0]][$prop[1]] = $row2['value'];
				} else {
					$tuix[$row['thing']][$row['codename']][$prop[0]] = $row2['value'];
				}
			}
			
			if ($lastId && $tuix !== []) {
				//Save the TUIX into the plugin settings using the new format
				$key = ['instance_id' => $row['instance_id'], 'egg_id' => $row['egg_id']];
				
				$key['name'] = '~custom_json~';
				ze\row::set('plugin_settings', ['value' => json_encode($tuix, JSON_FORCE_OBJECT)], $key);
				
				$key['name'] = '~custom_yaml~';
				ze\row::set('plugin_settings', ['value' => Spyc::YAMLDump($tuix, 4, false, true)], $key);
			}
			
			//N.b. I'm not going to worry about deleting the old plugin settings.
		}
	}
	
	ze\dbAdm::revision(42460);
}


//Attempt to re-generate any grid-templates where the function names/variables might have changed
//in Zenario 8.
if (ze\dbAdm::needRevision(43250)) {
	
	//Look for all grid layouts
	foreach (ze\row::getAssocs(
		'layouts',
		['layout_id', 'family_name', 'file_base_name'],
		['family_name' => 'grid_templates']
	) as $layout) {
		
		//Attempt to read the grid data from the template file
		if (($data = ze\gridAdm::readLayoutCode($layout['layout_id']))
		 && (!empty($data['cells']))
		 && (ze\gridAdm::validateData($data))) {
			
			//Attempt to regenerate the .tpl and .css files.
			//This may fail (e.g. if the files were not writable), but even if it fails it may still return
			//the slot information.
			$slots = [];
			$output = ze\gridAdm::generateDirectory($data, $slots, $writeToFS = true, $preview = false, $layout['file_base_name']);
			
			if (ze::isError($output)) {
				$path = \ze\content::templatePath($layout['family_name'], $layout['file_base_name']);
				
				if (($contents = @file_get_contents(CMS_ROOT. $path))
				 && (strpos($contents, 'cms_core') !== false
				  || strpos($contents, 'zenarioSGS') !== false)) {
					
					if (!is_writable(dirname(CMS_ROOT. $path))) {
						echo ze\admin::phrase('Your layouts need migrating to Zenario 8, but the zenario_custom/templates/grid_templates directory is not writable. Please make this writable, or else update the files there manually.');
					} else {
						echo ze\admin::phrase('Your layouts need migrating to Zenario 8, but the files in the zenario_custom/templates/grid_templates directory are not writable. Please make them writable, e.g. "chmod 666 *.css *.tpl.php", or else update the files there manually.', ['path' => $path]);
					}
					exit;
				}
			
			} else {
				//Update the slot information in the database
				if (!empty($slots)) {
					ze\gridAdm::updateMetaInfoInDB($data, $slots, $layout);
				}
			}
		}
		
		unset($contents, $data, $output, $slots);
	}

	
	ze\dbAdm::revision(43250);
}





//In previous versions there was a bug where the installer did not correctly create
//translations of the menu nodes for special pages - it created two completely separate
//menu nodes instead!
//Try to correct this here, if we see two menu nodes, with the same parent,
//pointing to the same chain, each in different languages.
if (ze\dbAdm::needRevision(43720)) {
	
	$sql = "
		SELECT
			mn_ol.id,
			mn_dl.id
		FROM ". DB_PREFIX. "menu_nodes AS mn_dl
		INNER JOIN ". DB_PREFIX. "menu_text AS mt_dl
		   ON mt_dl.menu_id = mn_dl.id
		  AND mt_dl.language_id = '". ze\escape::sql(ze::$defaultLang). "'
		LEFT JOIN ". DB_PREFIX. "menu_text AS mt_dl_null
		   ON mt_dl_null.menu_id = mn_dl.id
		  AND mt_dl_null.language_id != '". ze\escape::sql(ze::$defaultLang). "'

		INNER JOIN ". DB_PREFIX. "menu_nodes AS mn_ol
		   ON mn_ol.equiv_id = mn_dl.equiv_id
		  AND mn_ol.content_type = mn_dl.content_type
		  AND mn_ol.section_id = mn_dl.section_id
		  AND mn_ol.parent_id = mn_dl.parent_id
		INNER JOIN ". DB_PREFIX. "menu_text AS mt_ol
		   ON mt_ol.menu_id = mn_ol.id
		  AND mt_ol.language_id != '". ze\escape::sql(ze::$defaultLang). "'
		LEFT JOIN ". DB_PREFIX. "menu_text AS mt_ol_null
		   ON mt_ol_null.menu_id = mn_ol.id
		  AND mt_ol_null.language_id != mt_ol.language_id


		WHERE mt_dl_null.menu_id IS NULL
		  AND mt_ol_null.menu_id IS NULL";
	
	$result = ze\sql::select($sql);
	while ($row = ze\sql::fetchRow($result)) {
		
		ze\row::update('menu_text', ['menu_id' => $row[1]], ['menu_id' => $row[0]], $ignore = true);
		ze\row::update('menu_nodes', ['parent_id' => $row[1]], ['parent_id' => $row[0]], $ignore = true);
		ze\row::delete('menu_nodes', ['id' => $row[0]]);
	
	}
	
	ze\menuAdm::recalcAllHierarchy();
	ze\dbAdm::revision(43720);
}



//Fix any bad data left over from a bug where the images used by library plugins were not correctly tracked in the linking tables
if (ze\dbAdm::needRevision(44260)) {
	
	$sql = "
		SELECT id, content_id, content_type, content_version, is_nest, is_slideshow
		FROM ". DB_PREFIX. "plugin_instances
		WHERE content_id = 0";
	
	$result = ze\sql::select($sql);
	while ($instance = ze\sql::fetchAssoc($result)) {
		ze\contentAdm::resyncLibraryPluginFiles($instance['id'], $instance);
	}
	
	ze\dbAdm::revision(44260);
}


//Migrate data after removing a setting.
//(N.b. this was added in an after-branch patch in 8.1 revision 44269, but is safe to re-run.)
if (ze\dbAdm::needRevision(44601)) {
	
	if (!ze::setting('sign_in_access_log')) { 
		ze\site::setSetting('period_to_delete_sign_in_log', 'never_save');
	} elseif (!ze::setting('period_to_delete_sign_in_log')) {
		ze\site::setSetting('period_to_delete_sign_in_log', 'never_delete');
	}
	
	if (!ze::setting('log_user_access')) {
		ze\site::setSetting('period_to_delete_the_user_content_access_log', 'never_save');
	} elseif (!ze::setting('period_to_delete_the_user_content_access_log')) {
		ze\site::setSetting('period_to_delete_the_user_content_access_log', 'never_delete');
	}
	
	ze\dbAdm::revision(44601);
}



//In version 8.1 and earlier there was some "black magic" in the conductor, that tried to find
//the path that the smart breadcrumbs should take, if it wasn't specified.
//We've removed this in 8.2 and it's now either specifically set, or not at all.
//However to prevent problems with sites breaking in the migration, I'm going to apply the
//same settings as the "black magic" did in a migration script, just so no functionality changes.
if (ze\dbAdm::needRevision(44800)) {
	
	$sql = "
		SELECT path.instance_id, path.from_state, path.equiv_id, path.content_type, path.to_state
		
		/* For each slide */
		FROM ". DB_PREFIX. "nested_plugins AS from_slide
		
		/* Look for each path leading from said slide (except for back/submit/create/delete links) */
		INNER JOIN ". DB_PREFIX. "nested_paths AS path
		   ON path.instance_id = from_slide.instance_id
		  AND FIND_IN_SET(path.from_state, from_slide.states)
		  AND path.command NOT IN ('back', 'submit')
		  AND path.command NOT LIKE 'crea%'
		  AND path.command NOT LIKE 'dele%'
		  AND path.equiv_id = 0
		
		/* Look for the slide it goes to */
		INNER JOIN ". DB_PREFIX. "nested_plugins AS to_slide
		   ON to_slide.instance_id = from_slide.instance_id
		  AND to_slide.is_slide = 1
		  AND FIND_IN_SET(path.to_state, to_slide.states)
		
		/* Check if something on the slide has breadcrumbs */
		LEFT JOIN ". DB_PREFIX. "nested_plugins AS mbc
		   ON mbc.instance_id = from_slide.instance_id
		  AND mbc.slide_num = to_slide.slide_num
		  AND mbc.is_slide = 0
		  AND mbc.makes_breadcrumbs > 1
		
		/* Completely exclude anything that already has breadcrumbs set */
		LEFT JOIN ". DB_PREFIX. "nested_paths AS bc
		   ON bc.instance_id = from_slide.instance_id
		  AND FIND_IN_SET(bc.from_state, from_slide.states)
		  AND bc.is_forwards = 1


		WHERE from_slide.is_slide = 1
		  AND from_slide.states != ''
		  AND bc.instance_id IS NULL

		ORDER BY
			from_slide.instance_id, path.from_state,
	
			/* Prefer a link to a slide that has a plugin with the makes_breadcrumbs flag set */
			mbc.instance_id IS NOT NULL DESC,
	
			/* Look out for a view command, and favour that one */
			path.command LIKE 'view%' DESC,
	
			/* Otherwise look out for an edit command */
			path.command LIKE 'edit%' DESC,
	
			/* Prefer a slide that's earlier in the order as this is more likely to be higher up */
			to_slide.slide_num";
	
	$last = '';
	$result = ze\sql::select($sql);
	while ($path = ze\sql::fetchAssoc($result)) {
		$ths = $path['instance_id']. '.'. $path['from_state'];
		
		if ($last != $ths) {
			$last = $ths;
			ze\row::update('nested_paths', ['is_forwards' => 1], $path);
		}
	}
	
	ze\dbAdm::revision(44800);
}




//Various bugs in the system at various points were causing duplicate module tables to be created.
//Look at all of the tables created in the CMS, and look for module tables that are for
//modules that don't exist, or don't actually match the module they were created for.
if (ze\dbAdm::needRevision(45060)) {
	
	$modules = ze\module::modules($onlyGetRunningPlugins = false, $ignoreUninstalledPlugins = true, $dbUpdateSafemode = true);
	foreach (ze\dbAdm::lookupExistingCMSTables() as $tbl) {
		//Only check module tables
		if ($tbl['module_id']) {
			
			//Look for the module with the id from the table prefix
			if (isset($modules[$tbl['module_id']])) {
				
				//Work out what the prefix should be for this module
				$prefixConstant = $modules[$tbl['module_id']]['prefix'];
				$prefix = DB_PREFIX. constant($prefixConstant);
				
				//Check the prefix matches
				if (ze\ring::chopPrefix($prefix, $tbl['actual_name'])) {
					
					//If it matches, this table is good, don't delete it!
					continue;
				}
			}
			
			ze\sql::update('DROP TABLE `'. ze\escape::sql($tbl['actual_name']). '`');
		}
	}
	
	ze\dbAdm::revision(45060);
}

//Migrate data for zenario_extranet after renaming a plugin setting
//(N.b. this was added in an after-branch patch in 8.1 revision 44276, but is safe to re-run.)
if (ze\dbAdm::needRevision(45192)) {
	
	$sql = '
		UPDATE ' . DB_PREFIX . 'plugin_settings
		SET name = "show_link_to_registration_page", value = !value
		WHERE name = "hide_registration_link"';
	ze\sql::update($sql);
	
	ze\dbAdm::revision(45192);
}

//Delete any cached frameworks, as the format has changed slightly in this version
if (ze\dbAdm::needRevision(45600)) {
	
	\ze\skinAdm::clearCacheDir();
	
	ze\dbAdm::revision(45600);
}

//(N.b. this was added in an after-branch patch in 8.3 revision 46308, but is safe to re-run.)
ze\dbAdm::revision(47037
,  <<<_sql
	UPDATE `[[DB_PREFIX]]email_templates`
	SET period_to_delete_log_headers = 0
	WHERE period_to_delete_log_headers = 'never_save'
_sql
,  <<<_sql
	UPDATE `[[DB_PREFIX]]email_templates`
	SET period_to_delete_log_content = 0
	WHERE period_to_delete_log_content = 'never_save'
_sql
,  <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET value = 0
	WHERE name = 'period_to_delete_the_email_template_sending_log_headers'
	AND value = 'never_save'
_sql
,  <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET value = 0
	WHERE name = 'period_to_delete_the_email_template_sending_log_content'
	AND value = 'never_save'
_sql
,  <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET value = 0
	WHERE name = 'period_to_delete_sign_in_log'
	AND value = 'never_save'
_sql
,  <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET value = 0
	WHERE name = 'period_to_delete_the_user_content_access_log'
	AND value = 'never_save'
_sql

);

//A bug caused uploaded documents to be added to the database instead of the docstore.
//Now the bug is fixed we need to sort out the bugged documents by re-uploading them into
//the docstore.
//(N.b. this was added in an after-branch patch in 8.3 revision 46310, but is safe to re-run.)
if (ze\dbAdm::needRevision(47601)) {
	$sql = "
		SELECT d.id, d.file_id, f.filename, f.location, f.path, f.short_checksum, f.usage
		FROM " . DB_PREFIX . "documents d
		INNER JOIN " . DB_PREFIX . "files f
			ON d.file_id = f.id
		WHERE d.type = 'file' 
		AND d.privacy = 'public'";
	$result = ze\sql::select($sql);
	while ($doc = ze\sql::fetchAssoc($result)) {
	
		if (!file_exists(CMS_ROOT. 'public/downloads/'. $doc['short_checksum']. '/'. ze\file::safeName($doc['filename']))) {
			$publicLink = ze\document::generatePublicLink($doc['id']);
		
			if (ze::isError($publicLink)) {
				$location = ze\file::link($doc['file_id']);
				$newFileId = ze\file::addToDocstoreDir($doc['usage'], rawurldecode(CMS_ROOT . $location), $doc['filename']);
			
				if ($newFileId) {
					ze\row::update('documents', ['file_id' => $newFileId], $doc['id']);
				}
			} 
		}
	}
	
	ze\dbAdm::revision(47601);
}

//This code would migrate email templates to using curly brackets, if we choose to go and do that

////Update email templates to use {{ and }} for merge fields rather than [[ and ]].
//if (ze\dbAdm::needRevision(48400)) {
//	$sql = "
//		SELECT id, subject, email_address_from, email_name_from, body
//		FROM " . DB_PREFIX . "email_templates";
//	$result = ze\sql::select($sql);
//	while ($et = ze\sql::fetchAssoc($result)) {
//		$etId = $et['id'];
//		unset($et['id']);
//		
//		foreach ($et as &$val) {
//			$val = preg_replace('@\{\{(\s*\w+?\s*)\}\}@', '[[$1]]', $val);
//		}
//		unset($val);
//		
//		ze\row::update('email_templates', $et, $etId);
//	}
//	
//	ze\dbAdm::revision(48400);
//}




//Content Summary List (and Blog News List), and search modules had an update of settings.
//The max number of elements is now a text field rather than a dropdown of values.
if (ze\dbAdm::needRevision(48641)) {
	$sql = '
		SELECT ps.instance_id, ps.egg_id, ps.name, ps.value, ps.is_content, m.class_name
		FROM ' . DB_PREFIX . 'plugin_settings ps
		LEFT JOIN ' . DB_PREFIX . 'plugin_instances pi
			ON ps.instance_id = pi.id
		LEFT JOIN ' . DB_PREFIX . 'modules m
			ON pi.module_id = m.id
		WHERE ps.name = "page_size";';
			
	$result = ze\sql::select($sql);
	
	$modules = [
		'zenario_search_entry_box',
		'zenario_search_entry_box_predictive_probusiness',
		'zenario_search_results',
		'zenario_search_results_pro',
		'zenario_advanced_search',
		'zenario_content_list',
		'zenario_blog_news_list'
	];
	
	$values = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 15, 20, 50];
	
	while ($row = ze\sql::fetchAssoc($result)) {
		if (in_array($row['class_name'], $modules) && in_array($row['value'], $values)) {
			ze\row::insert('plugin_settings', ['instance_id' => $row['instance_id'], 'egg_id' => $row['egg_id'], 'name' => 'maximum_results_number', 'value' => $row['value'], 'is_content' => $row['is_content']], $ignore = true);
			ze\row::update('plugin_settings', ['value' => 'maximum_of'], ['instance_id' => $row['instance_id'], 'egg_id' => $row['egg_id'], 'name' => 'page_size']);
		}
	}
	
	ze\dbAdm::revision(48641);
}


//Migrate any custom TUIX to the new system
ze\dbAdm::revision( 50330

//Look for any existing custom TUIX defined in the plugin settings, and attempt to copy it into the new table
, <<<_sql
	INSERT INTO `[[DB_PREFIX]]tuix_snippets` (name, custom_yaml, custom_json)
	SELECT CONCAT('Migrated TUIX Snippet ', SUBSTR(SHA1(cj.value), 1, 10)), MIN(cy.value), cj.value
	FROM [[DB_PREFIX]]plugin_settings AS cj
	INNER JOIN [[DB_PREFIX]]plugin_settings AS cy
	   ON cy.instance_id = cj.instance_id
	  AND cy.egg_id = cj.egg_id
	  AND cy.name = '~custom_yaml~'
	WHERE cj.name = '~custom_json~'
	  AND cj.value IS NOT NULL
	  AND cj.value != ''
	  AND TRIM(cj.value) != ''
	GROUP BY cj.value
_sql

//Update the plugin settings to point back to the table
, <<<_sql
	UPDATE [[DB_PREFIX]]plugin_settings AS cj
	INNER JOIN [[DB_PREFIX]]tuix_snippets AS tc
	   ON tc.custom_json =  cj.value
	SET cj.name = '~tuix_snippet~',
		cj.value = tc.id
	WHERE cj.name = '~custom_json~'
_sql

, <<<_sql
	DELETE FROM [[DB_PREFIX]]plugin_settings
	WHERE name IN ('~custom_yaml~', '~custom_json~')
_sql
);


//Try to give any new TUIX snippets a more helpful name
if (ze\dbAdm::needRevision(50340)) {
	foreach (ze\row::getAssocs('tuix_snippets', ['name', 'custom_json']) as $tuixSnippetId => $tuix) {
		if (($custom = json_decode($tuix['custom_json'], true))
		 && (is_array($custom))
		 && (!empty($custom))) {
			
			foreach (['columns', 'item_buttons'] as $tag) {
				if (!empty($custom[$tag]) && is_array($custom[$tag])) {
					$tuix['name'] .= ' ('. $tag. ': '. implode(', ', array_keys($custom[$tag])). ')';
				}
			}
			ze\row::set('tuix_snippets', $tuix, $tuixSnippetId);
		}
	}
	ze\dbAdm::revision(50340);
}

//In 8.6 we've added the cache/scans/ and cache/stop_flags/ directories,
//call the cleanDirs() function to try and ensure that they're created properly
if (ze\dbAdm::needRevision(50420)) {
	
	if (\ze::$dbL
	 && \ze::$dbL->con
	 && is_dir(CMS_ROOT. 'cache/tuix')
	 && !is_dir(CMS_ROOT. 'cache/stop_flags')) {
		ze\cache::cleanDirs(true);
	}
	
	ze\dbAdm::revision(50420);
}

//Plugin settings cleanup for Content Summary List and Blog/News List
if (ze\dbAdm::needRevision(51052)) {
	foreach (['zenario_content_list', 'zenario_blog_news_list'] as $module) {
		if (ze\module::isRunning($module)) {
			$instances = ze\module::getModuleInstancesAndPluginSettings($module);
			foreach ($instances as $instance) {
				//Plugin settings cleanup #1: there are 2 identical settings for showing content items in any/all selected categories.
				//Remove one and set its value to the remaining setting.
				
				//Check if the old setting is in use.
				if (isset($instance['settings']['refine_type_content_with_matching_categories'])) {
			
					//Setting is in use, and the filtering option is "Show content with categories matching the current content item"
					if (isset($instance['settings']['category_filters_dropdown']) && ($instance['settings']['category_filters_dropdown'] == 'show_content_with_matching_categories')) {
						//Check if the target value already exists in the DB: set the value if so...
						if (isset($instance['settings']['refine_type'])) {
							ze\row::update(
								'plugin_settings',
								['value' => ze\escape::sql($instance['settings']['refine_type_content_with_matching_categories'])],
								['name' => 'refine_type', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
							);
						} else {
							//... otherwise create it.
							ze\row::insert(
								'plugin_settings',
								['name' => 'refine_type', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id'], 'value' => ze\escape::sql($instance['settings']['refine_type_content_with_matching_categories'])]
							);
						}
					}
			
					//Delete the old setting entry.
					ze\row::delete(
						'plugin_settings',
						['name' => 'refine_type_content_with_matching_categories', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
					);
				}
		
				//Plugin settings cleanup #2: fix a bug where there are categories selected, but the Categories filter dropdown has no value.
				if (!empty($instance['settings']['category'])) {
					if (empty($instance['settings']['category_filters_dropdown'])) {
						ze\row::set(
							'plugin_settings',
							['value' => 'choose_categories_to_display_or_omit'],
							['name' => 'category_filters_dropdown', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
						);
					}
				}
			}
		}
	}
	ze\dbAdm::revision(51052);
}

//Plugin settings update for User Redirector:
//replaced the old redirection rules with the ones from Extranet Login
if (ze\dbAdm::needRevision(51053)) {
	if (ze\module::isRunning('zenario_user_redirector')) {
		$instances = ze\module::getModuleInstancesAndPluginSettings('zenario_user_redirector');
		foreach ($instances as $instance) {
			//Previously the plugin supported up to 4 redirection rules. Migrate these rules.
			$counter = 0;
			foreach (range(1, 4) as $i) {
				$condition = false;
				if ($i == 1) {
					$condition = (!empty($instance['settings']['group_' . $i]) && !empty($instance['settings']['redirect_' . $i]));
				} elseif ($i >= 2 && $i <= 4) {
					$condition = (!empty($instance['settings']['show_' . $i]) && !empty($instance['settings']['group_' . $i]) && !empty($instance['settings']['redirect_' . $i]));
				}

				if ($condition) {
					//The old logic only allowed redirection rules based on group membership.
					//The new logic requires to specify the rule type.
					ze\row::insert(
						'plugin_settings',
						['name' => 'redirect_rule_type__' . (int)$i, 'value' => 'group', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
					);
					
					//Rename the old settings
					ze\row::update(
						'plugin_settings',
						['name' => 'redirect_rule_group__' . (int)$i],
						['name' => 'group_' . (int)$i, 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
					);

					ze\row::update(
						'plugin_settings',
						['name' => 'redirect_rule_content_item__' . (int)$i],
						['name' => 'redirect_' . (int)$i, 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
					);
			
					//Delete the old settings entries if necessary
					if ($i >= 2 && $i <= 4) {
						ze\row::delete(
							'plugin_settings',
							['name' => 'show_' . (int)$i, 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
						);
					}

					$counter++;
				}
			}

			//Insert new value for the new radios
			ze\row::insert(
				'plugin_settings',
				['name' => 'show_welcome_page', 'value' => '_ALWAYS', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
			);

			//Save the total count of redirection rules
			ze\row::insert(
				'plugin_settings',
				['name' => 'number_of_redirect_rules', 'value' => (int)$counter, 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
			);
			
			//Handle the default redirect
			if (!empty($instance['settings']['show_default']) && !empty($instance['settings']['redirect_default'])) {
				//Rename the old setting
				ze\row::update(
					'plugin_settings',
					['name' => 'welcome_page'],
					['name' => 'redirect_default', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
				);

				//Delete the old setting entry
				ze\row::delete(
					'plugin_settings',
					['name' => 'show_default', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
				);
			}
		}
	}
	ze\dbAdm::revision(51053);
}


//Work around a bug where the fea_type property in TUIX files is only read when the files change,
//however in some weird cases it was possible for the checksum of the file to be recorded before
//the code that read the value was implemented.
//(Note: this was back-patched to 8.7, but is safe to re-run.)
if (ze\dbAdm::needRevision(51303)) {
	
	if (is_dir(CMS_ROOT. 'cache/tuix')) {
		ze\row::update('tuix_file_contents', ['last_modified' => 0, 'checksum' => ''], []);
		ze\cache::deleteDir(CMS_ROOT. 'cache/tuix', 1);
		ze\cache::cleanDirs(true);
	}
	
	ze\dbAdm::revision(51303);
}
