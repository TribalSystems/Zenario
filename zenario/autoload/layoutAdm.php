<?php 
/*
 * Copyright (c) 2018, Tribal Limited
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


namespace ze;

class layoutAdm {


	//Formerly "getSlotsOnTemplate()"
	public static function slots($templateFamily, $templateFileBaseName) {
		return \ze\row::getArray(
			'template_slot_link',
			'slot_name',
			array('family_name' => $templateFamily, 'file_base_name' => $templateFileBaseName),
			array('slot_name'));
	}

	//Formerly "getDefaultTemplateId()"
	public static function defaultId($cType) {
		if (!$layoutId = \ze\row::get('content_types', 'default_layout_id', array('content_type_id' => $cType))) {
			$layoutId = \ze\row::get('layouts', 'layout_id', array('content_type' => $cType));
		}
	
		return $layoutId;
	}


	//Formerly "getTemplateFamilyDetails()"
	public static function familyDetails($familyName) {
		return \ze\row::get('template_families', array('family_name', 'skin_id', 'missing'), $familyName);
	}

	//Formerly "validateChangeSingleLayout()"
	public static function validateChangeSingleLayout(&$box, $cID, $cType, $cVersion, $newLayoutId, $saving) {
		$box['confirm']['show'] = false;
		$box['confirm']['message'] = '';

		$status = \ze\content::status($cID, $cType);

		switch ($status) {
			case 'published':
				$box['confirm']['message'] .=
					\ze\admin::phrase('You are editing a content item that\'s already published.');
				break;
	
			case 'hidden':
				$box['confirm']['message'] .=
					\ze\admin::phrase('You are editing a content item that\'s hidden.');
				break;
	
			case 'trashed':
				$box['confirm']['message'] .=
					\ze\admin::phrase('You are editing a content item that\'s trashed.');
				break;
		}

		if (!\ze\content::isDraft($status)) {
			$box['confirm']['show'] = true;
			$box['confirm']['message'] .= $box['confirm']['message']? '<br/><br/>' : '';
			$box['confirm']['message'] .=
				\ze\admin::phrase('When you want to edit a content item, the CMS makes a draft version. This won\'t be seen by site visitors until it is published.');
		}

		if ($saving && ($warnings = \ze\layoutAdm::changeContentItemLayout(
			$cID, $cType, $cVersion, $newLayoutId,
			$check = true, $warnOnChanges = true
		))) {
			$box['confirm']['show'] = true;
			$box['confirm']['message'] .= $box['confirm']['message']? '<br/><br/>' : '';
			$box['confirm']['message'] .=
				\ze\admin::phrase('You are about to change the layout of this content item. The content and settings will moved as follows:').
				'<br/><br/>'.
				$warnings;
			$box['confirm']['button_message'] = \ze\admin::phrase('Change Layout');
		}



		if (!\ze\content::isDraft($status)) {
			$box['confirm']['show'] = true;
			$box['confirm']['message'] .= $box['confirm']['message']? '<br/><br/>' : '';
			$box['confirm']['message'] .= \ze\admin::phrase('Make a draft?');
			$box['confirm']['button_message'] = \ze\admin::phrase('Make a draft');
		}
	}

	//Formerly "saveTemplate()"
	public static function save($submission, &$layoutId, $sourceTemplateId = false) {
	
		$duplicating = (bool) $sourceTemplateId;
	
		$values = array();
		foreach (array(
			'skin_id', 'css_class', 'bg_image_id', 'bg_color', 'bg_repeat', 'bg_position',
			'name', 'cols', 'min_width', 'max_width', 'fluid', 'responsive'
		) as $var) {
			if (isset($submission[$var])) {
				$values[$var] = $submission[$var];
			}
		}
		if (isset($submission['minWidth'])) {
			$values['min_width'] = $submission['minWidth'];
		}
		if (isset($submission['maxWidth'])) {
			$values['max_width'] = $submission['maxWidth'];
		}
	
		if (isset($submission['content_type'])) {
			if (!$layoutId
			 || $duplicating
			 || (!\ze\row::exists('content_item_versions', array('layout_id' => $layoutId)) && !\ze\row::exists('content_types', array('default_layout_id' => $layoutId)))) {
				$values['content_type'] = $submission['content_type'];
			}
		}
	
		if (isset($submission['file_base_name'])) {
			$values['file_base_name'] = $submission['file_base_name'];
		}
	
		if ($layoutId && !$duplicating) {
			\ze\row::update('layouts', $values, $layoutId);
	
		} else {
			$values['family_name'] = \ze\ray::grabValue($submission, 'templateFamily', 'family_name');
		
			$layoutId = \ze\row::insert('layouts', $values);
		}
	
		if ($duplicating) {
		
			$details = \ze\row::get(
				'layouts',
				array('css_class', 'head_html', 'head_visitor_only', 'foot_html', 'foot_visitor_only', 'file_base_name'),
				$sourceTemplateId);
		
			$sourceFileBaseName = $details['file_base_name'];
			unset($details['file_base_name']);
		
			\ze\row::update('layouts', $details, $layoutId);
		
			// Copy slots to duplicated layout
			if (isset($values['file_base_name'])) {
				$slots = \ze\row::getArray('template_slot_link', 
					array('family_name', 'slot_name'), 
					array('family_name' => $values['family_name'], 'file_base_name' => $sourceFileBaseName));
				if ($slots) {
					$sql = '
						INSERT IGNORE INTO '.DB_NAME_PREFIX.'template_slot_link (
							family_name,
							file_base_name,
							slot_name
						) VALUES ';
					foreach ($slots as $slot) {
						$sql .= '("'. \ze\escape::sql($slot['family_name']). '","'. \ze\escape::sql($values['file_base_name']). '","'. \ze\escape::sql($slot['slot_name']). '"),';
					}
					$sql = trim($sql, ',');
					\ze\sql::update($sql);
				}
			}
		
			$sql = "
				REPLACE INTO ". DB_NAME_PREFIX. "plugin_layout_link (
					module_id,
					instance_id,
					family_name,
					layout_id,
					slot_name
				) SELECT 
					module_id,
					instance_id,
					'". \ze\escape::sql($values['family_name']). "',
					". (int) $layoutId.  ",
					slot_name
				FROM ". DB_NAME_PREFIX. "plugin_layout_link
				WHERE layout_id = ". (int) $sourceTemplateId;
			\ze\sql::update($sql);
		}
	
		if (($family = \ze\ray::grabValue($submission, 'templateFamily', 'family_name'))
		 && (!\ze\layoutAdm::familyDetails($family))) {
			\ze\row::insert('template_families', array('family_name' => $family));
		}
	}


	//Formerly "changeContentItemLayout()"
	public static function changeContentItemLayout($cID, $cType, $cVersion, $newLayoutId, $check = false, $warnOnChanges = false) {
	
		$oldLayoutId = \ze\content::layoutId($cID, $cType, $cVersion);
	
		if (!$oldLayoutId || $oldLayoutId == $newLayoutId) {
			return false;
		}
	
		$slotChanges =
		$slotsLost = false;
	
		$layoutId =
		$layout =
		$slotContents =
		$pluginsOnLayout =
		$nonMatches =
		$matches =
		$toPlace = array('old' => array(), 'new' => array());
	
		$layoutId['old'] = $oldLayoutId;
		$layoutId['new'] = $newLayoutId;
	
		//Get information on the templates we're using
		foreach (array('old', 'new') as $oon) {
			//Loop through the slots on the templates, seeing what Modules are placed where
			$layout[$oon] = \ze\content::layoutDetails($layoutId[$oon]);
			\ze\plugin::slotContents($slotContents[$oon], $cID, $cType, $cVersion, $layoutId[$oon], $layout[$oon]['family_name'], $layout[$oon]['filename'], false, false, false, false, $runPlugins = false);
		
			foreach ($slotContents[$oon] as $slotName => $slot) {
				if (!empty($slot['content_id']) && !empty($slot['module_id'])) {
				
					//For the old Template, only count a slot if there is Content in there
					if ($oon == 'new'
					 || ((
						$sql = "
							SELECT 1
							FROM ". DB_NAME_PREFIX. "plugin_settings AS ps
							INNER JOIN ". DB_NAME_PREFIX. "plugin_setting_defs AS psd
							   ON psd.module_id = ". (int) $slot['module_id']. "
							  AND psd.name = ps.name
							  AND psd.default_value != ps.value
							WHERE ps.egg_id = 0
							  AND ps.value IS NOT NULL
							  AND ps.is_content IN('version_controlled_setting', 'version_controlled_content')
							  AND ps.instance_id = ". (int) $slot['instance_id']. "
							LIMIT 1")
					  && ($result = \ze\sql::select($sql))
					  && (\ze\sql::fetchRow($result)))
					 || ((
						$sql = "
							SELECT 1
							FROM ". DB_NAME_PREFIX. "plugin_settings AS ps
							INNER JOIN ". DB_NAME_PREFIX. "nested_plugins AS np
							   ON np.instance_id = ps.instance_id
							  AND np.id = ps.egg_id
							  AND np.is_slide = 1
							INNER JOIN ". DB_NAME_PREFIX. "plugin_setting_defs AS psd
							   ON psd.module_id = np.module_id
							  AND psd.name = ps.name
							  AND psd.default_value != ps.value
							WHERE ps.egg_id != 0
							  AND ps.value IS NOT NULL
							  AND ps.is_content IN('version_controlled_setting', 'version_controlled_content')
							  AND ps.instance_id = ". (int) $slot['instance_id']. "
							LIMIT 1")
					  && ($result = \ze\sql::select($sql))
					  && (\ze\sql::fetchRow($result)))) {
						$pluginsOnLayout[$oon][$slotName] =
						$nonMatches[$oon][$slotName] = $slot['class_name'];
					}
				}
			}
		}
	
	
		//Loop through the Modules placed in the previous Template
		foreach (array_keys($nonMatches['old']) as $slotname) {
		
			//Does this slot match with what's in the new Template?
			if (isset($nonMatches['new'][$slotname]) && $nonMatches['new'][$slotname] == $nonMatches['old'][$slotname]) {
			
				//If so, note down this match, and remove all other mention of it so we don't touch anything
				$matches['old'][$slotname] = $slotname;
				$matches['new'][$slotname] = $slotname;
				unset($nonMatches['old'][$slotname], $nonMatches['new'][$slotname]);
			}
		}
	
		$slotChanges = !empty($nonMatches['old']);
		if ($slotChanges) {
			//Try to handle the case where the same number of Plugins exist, but they are
			//just in different places, by adding them into slots as we see them
			$changes = true;
			while ($changes) {
				$changes = false;
				foreach ($nonMatches['old'] as $oSlotName => $oClassName) {
					foreach ($nonMatches['new'] as $nSlotName => $nClassName) {
						if ($oClassName == $nClassName) {
							$matches['old'][$oSlotName] = $nSlotName;
							$matches['new'][$nSlotName] = $oSlotName;
					
							unset($nonMatches['old'][$oSlotName], $nonMatches['new'][$nSlotName]);
					
							$changes = true;
							continue 3;
						}
					}
				}
			}
		}
		$slotsLost = !empty($nonMatches['old']);
	
		if ($check) {
			if ($slotsLost || ($slotChanges && $warnOnChanges)) {
				$html =
					'<table class="zenario_changeLayoutDestinations"><tr>
						<th>'. \ze\admin::phrase('Old layout'). '</th>
						<th>'. \ze\admin::phrase('Contents'). '</th>
						<th>'. \ze\admin::phrase('New layout'). '</th>
					</tr>';
			
				foreach ($pluginsOnLayout['old'] as $oSlotName => $className) {
					$html .=
						'<tr>
							<td>'. htmlspecialchars($oSlotName). '</td>
							<td>'. htmlspecialchars(\ze\module::getModuleDisplayNameByClassName($className)). '</td>';
				
					if (empty($matches['old'][$oSlotName])) {
						$html .= '<td class="zenario_changeLayoutDestinationMissing">'. \ze\admin::phrase('data will NOT be copied'). '</td>';
					} else {
						$html .= '<td>'. htmlspecialchars($matches['old'][$oSlotName]). '</td>';
					}
				
					$html .= '</tr>';
				}
			
				$html .= '<table>';
				return $html;
		
			} else {
				return false;
			}
		
		} else {
		
			//Change the Layout
			$version = array('layout_id' => $newLayoutId);
			\ze\contentAdm::updateVersion($cID, $cType, $cVersion, $version, true);
		
			//Loop through all of the matched slots
			foreach ($matches['old'] as $oSlotName => $nSlotName) {
				//Move Plugins/data between slots as needed
				if ($oSlotName != $nSlotName) {
					$oldSlot =
						array(
						'module_id' => $slotContents['old'][$oSlotName]['module_id'],
						'content_id' => $cID,
						'content_type' => $cType,
						'content_version' => $cVersion,
						'slot_name' => $oSlotName);
				
					$newSlot = $oldSlot;
					$newSlot['slot_name'] = $nSlotName;
				
					//Delete any existing data that would cause a primary key collision
					//(this shouldn't happen, but you might get bad/left over data).
					if ($badData = \ze\row::get('plugin_instances', 'id', $newSlot)) {
						\ze\pluginAdm::delete($badData);
					}
				
					//Update the Instance from the old slot to the new slot
					\ze\row::update('plugin_instances', $newSlot, $oldSlot);
				}
			}
		}
	}

	//Formerly "checkSiteHasMultipleTemplateFamilies()"
	public static function siteHasMultipleFamilies() {
		return ($result = \ze\row::query('template_families', array('family_name'), array()))
			&& (\ze\sql::fetchRow($result))
			&& (\ze\sql::fetchRow($result));
	}

	//Formerly "checkTemplateFileInFS()"
	public static function isInFS($template_family, $fileBaseName) {
		return $template_family && $fileBaseName && is_file(CMS_ROOT. \ze\content::templatePath($template_family, $fileBaseName));
	}

	//Formerly "checkTemplateFamilyInFS()"
	public static function isFamilyInFS($template_family) {
		return $template_family && is_dir(CMS_ROOT. \ze\content::templatePath($template_family));
	}

	//Quick version of the above that just checks for missing template files
	//Formerly "checkForMissingTemplateFiles()"
	public static function checkForMissingFiles() {
		foreach(\ze\row::getArray('template_files', array('family_name', 'file_base_name', 'missing')) as $tf) {
			$missing = (int) !file_exists(CMS_ROOT. \ze\content::templatePath($tf['family_name'], $tf['file_base_name']));
			if ($missing != $tf['missing']) {
				\ze\row::update('template_files', array('missing' => $missing), $tf);
			}
		}
	}


	//Formerly "checkTemplateFamilyInUse()"
	public static function familyInUse($template_family) {
		return
			\ze\row::exists('layouts', array('family_name' => $template_family))
		 || \ze\row::exists('skins', array('family_name' => $template_family))
		 || \ze\row::exists('template_files', array('family_name' => $template_family));
	}

	//Formerly "checkSkinInUse()"
	public static function skinInUse($skinId) {
		return
			\ze\row::exists('layouts', array('skin_id' => $skinId))
		 || \ze\row::exists('template_families', array('skin_id' => $skinId));
	}

	//Formerly "checkLayoutInUse()"
	public static function inUse($layoutId) {
		return
			\ze\row::exists('content_item_versions', array('layout_id' => $layoutId));
	}

	//Formerly "generateLayoutFileBaseName()"
	public static function generateFileBaseName($layoutName, $layoutId = false) {
	
		if (!$layoutId) {
			$layoutId = \ze\db::getNextAutoIncrementId('layouts');
		}
	
		//New logic, return the id
		return 'L'. str_pad($layoutId, 2, '0', STR_PAD_LEFT);
	
		//Old logic, return the name escaped
		//return substr(str_replace('~20', ' ', \ze\ring::encodeIdForOrganizer($layoutName, '')), 0, 255);
	}

	//Delete a Layout from the system
	//Formerly "deleteLayout()"
	public static function delete($layout, $deleteFromDB) {
	
		if (is_numeric($layout)) {
			$layout = \ze\row::get('layouts', array('layout_id', 'family_name', 'file_base_name'), $layout);
		} elseif (!is_array($layout)) {
			$parts = explode('/', \ze\ring::decodeIdForOrganizer($layout));
			$layout = array();
			$layout['family_name'] = $parts[0];
			$layout['file_base_name'] = $parts[1];
		}
	
		if ($deleteFromDB && $layout && !empty($layout['layout_id'])) {
			//Delete the layout from the database
			\ze\module::sendSignal('eventTemplateDeleted',array('layoutId' => $layout['layout_id']));
	
			\ze\row::delete('layouts', array('layout_id' => $layout['layout_id']));
			\ze\row::delete('plugin_layout_link', array('layout_id' => $layout['layout_id']));
		}
	
		//Check whether anything else uses this Template File
		if (!\ze\row::exists(
			'layouts',
			array(
				'family_name' => $layout['family_name'],
				'file_base_name' => $layout['file_base_name'])
		)) {
			//If not, attempt to delete the Layout's files
			foreach (array(
				CMS_ROOT. \ze\content::templatePath($layout['family_name'], $layout['file_base_name']),
				CMS_ROOT. \ze\content::templatePath($layout['family_name'], $layout['file_base_name'], true)
			) as $filePath) {
				if (file_exists($filePath)
				 && is_writable($filePath)) {
					@unlink($filePath);
				}
			}
		}
	}

	//Given a Layout that has just been renamed or duplicated from another Layout, try to copy its Template Files
	//Formerly "copyLayoutFiles()"
	public static function copyFiles($layout, $newName = false, $newFamilyName = false) {
	
		if (!is_array($layout)) {
			$layout = \ze\row::get('layouts', array('family_name', 'file_base_name'), $layout);
		}
	
		if (!$newName) {
			$newName = \ze\layoutAdm::generateFileBaseName($layout['name']);
		}
		if (!$newFamilyName) {
			$newFamilyName = $layout['family_name'];
		}
	
		$copies = array(
			CMS_ROOT. \ze\content::templatePath($layout['family_name'], $layout['file_base_name'])
		  => 
			CMS_ROOT. \ze\content::templatePath($newFamilyName, $newName),
		
			CMS_ROOT. \ze\content::templatePath($layout['family_name'], $layout['file_base_name'], true)
		  => 
			CMS_ROOT. \ze\content::templatePath($newFamilyName, $newName, true)
		);
	
		foreach ($copies as $filePath => $newFilePath) {
			if (file_exists($filePath)) {
				if (!is_readable($filePath)
				 || file_exists($newFilePath)
				 || !is_writable(dirname($newFilePath))) {
					return false;
				}
			}
		}
	
		foreach ($copies as $filePath => $newFilePath) {
			if (file_exists($filePath)) {
				copy($filePath, $newFilePath);
			}
		}
	
		return true;
	}







	//Check how many items use a Layout or a Template Family
	//Formerly "checkTemplateUsage()"
	public static function usage($layoutId, $templateFamily = false, $publishedOnly = false, $skinId = false) {
		$sql = "
			SELECT COUNT(DISTINCT c.tag_id) AS ctu_". (int) $layoutId. "_". \ze\ring::engToBoolean($templateFamily). "_". \ze\ring::engToBoolean($publishedOnly). "_". (int) $skinId. "
			FROM ". DB_NAME_PREFIX. "content_items AS c
			INNER JOIN ". DB_NAME_PREFIX. "content_item_versions as v
			   ON c.id = v.id
			  AND c.type = v.type";
	
		if ($publishedOnly) {
			$sql .= "
			  AND v.version = c.visitor_version
			INNER JOIN ". DB_NAME_PREFIX. "layouts AS t
			   ON t.layout_id = v.layout_id
			INNER JOIN ". DB_NAME_PREFIX. "template_families AS f
			   ON f.family_name = t.family_name
			WHERE c.status IN ('published_with_draft', 'published')";
	
		} else {
			$sql .= "
			  AND v.version IN (c.admin_version, c.visitor_version)
			INNER JOIN ". DB_NAME_PREFIX. "layouts AS t
			   ON t.layout_id = v.layout_id
			INNER JOIN ". DB_NAME_PREFIX. "template_families AS f
			   ON f.family_name = t.family_name
			WHERE c.status IN ('first_draft', 'published_with_draft', 'hidden_with_draft', 'trashed_with_draft', 'published')";
		}
	
		if ($templateFamily) {
			$sql .= "
			  AND t.family_name = '". \ze\escape::sql($templateFamily). "'";
		} else {
			$sql .= "
			  AND v.layout_id = ". (int) $layoutId;
		}
	
		if ($skinId) {
			$sql .= "
			  AND IF(t.skin_id != 0, t.skin_id, f.skin_id) = ". (int) $skinId;
		}
	
		$result = \ze\sql::select($sql);
		$row = \ze\sql::fetchRow($result);
		return $row[0];
	}


	//Work out a slot to put this Plugin into, favouring empty "Main" slots. Default to Main_3.
	//Formerly "getTemplateMainSlot()"
	public static function mainSlotByName($templateFamilyName, $templateFileBaseName, $guess1 = 'Main_3', $guess2 = 'Main') {
		$sql = "
			SELECT tsl.slot_name
			FROM ". DB_NAME_PREFIX. "template_slot_link AS tsl
			LEFT JOIN ". DB_NAME_PREFIX. "layouts AS t
			   ON tsl.family_name = t.family_name
			  AND tsl.file_base_name = t.file_base_name
			LEFT JOIN ". DB_NAME_PREFIX. "plugin_layout_link AS pitl
			   ON tsl.family_name = pitl.family_name
			  AND t.layout_id = pitl.layout_id
			  AND tsl.slot_name = pitl.slot_name
			WHERE tsl.family_name = '". \ze\escape::sql($templateFamilyName). "'
			  AND tsl.file_base_name = '". \ze\escape::sql($templateFileBaseName). "'
			GROUP BY tsl.slot_name
			ORDER BY
				pitl.slot_name IS NULL DESC,
				tsl.slot_name LIKE '". \ze\escape::like($guess1). "%' DESC,
				tsl.slot_name LIKE '". \ze\escape::like($guess2). "%' DESC,
				tsl.slot_name
			LIMIT 1";
	
		if (($result = \ze\sql::select($sql)) && ($row = \ze\sql::fetchAssoc($result)) && ($row['slot_name'])) {
			return $row['slot_name'];
		}
	
		return $guess1;
	}

	//Similar to \ze\contentAdm::mainSlot(), but just use the Layout in the calculation
	//Formerly "pluginMainSlotOnLayout()"
	public static function mainSlot($layoutId, $moduleId = false, $limitToOne = true) {
	
		if (!$moduleId) {
			$moduleId = \ze\module::id('zenario_wysiwyg_editor');
		}
	
		$sql = "
			SELECT tsl.slot_name
			FROM ". DB_NAME_PREFIX. "layouts AS t
			INNER JOIN ". DB_NAME_PREFIX. "template_slot_link AS tsl
			   ON tsl.family_name = t.family_name
			  AND tsl.file_base_name = t.file_base_name
			INNER JOIN ". DB_NAME_PREFIX. "plugin_layout_link AS pitl
			   ON pitl.layout_id = t.layout_id
			  AND pitl.slot_name = tsl.slot_name
			WHERE pitl.layout_id = ". (int) $layoutId. "
			  AND pitl.module_id = ". (int) $moduleId. "
			  AND pitl.instance_id = 0
			GROUP BY tsl.slot_name
			ORDER BY
				pitl.slot_name IS NOT NULL DESC,
				tsl.slot_name IS NOT NULL DESC,
				tsl.slot_name LIKE '%Main%' DESC,
				tsl.ord,
				tsl.slot_name";
	
		if ($limitToOne) {
			$sql .= "
				LIMIT 1";
		}
	
		$slots = array();
		$result = \ze\sql::select($sql);
		while ($row = \ze\sql::fetchAssoc($result)) {
			if ($row['slot_name']) {
				if ($limitToOne) {
					return $row['slot_name'];
				} else {
					$slots[] = $row['slot_name'];
				}
			}
		}
	
		if ($limitToOne) {
			return false;
		} else {
			return $slots;
		}
	}

	//Formerly "getTemplateUsageStorekeeperDeepLink()"
	public static function usageOrganizerLink($layoutId) {
		return \ze\link::absolute(). 'zenario/admin/organizer.php#'.
				'zenario__layouts/panels/layouts/view_content//'. (int) $layoutId.  '//';
	}

	//Formerly "getTemplateFamilyUsageStorekeeperDeepLink()"
	public static function familyUsageOrganizerLink($templateFamily) {
		return \ze\link::absolute(). 'zenario/admin/organizer.php#'.
				'zenario__layouts/panels/template_families/view_content//'. \ze\ring::encodeIdForOrganizer($templateFamily).  '//';
	}
	
	
	
	
	
	
	

}