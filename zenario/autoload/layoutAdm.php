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


namespace ze;

class layoutAdm {
	
	public static function codeName($layoutId) {
		return 'L'. str_pad((string) $layoutId, 2, '0', STR_PAD_LEFT);
	}

	//Formerly "getDefaultTemplateId()"
	public static function defaultId($cType) {
		if (!$layoutId = \ze\row::get('content_types', 'default_layout_id', ['content_type_id' => $cType])) {
			$layoutId = \ze\row::get('layouts', 'layout_id', ['content_type' => $cType]);
		}
	
		return $layoutId;
	}


	//Formerly "getTemplateFamilyDetails()"
	public static function familyDetails($familyName) {
		return NULL;
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
	public static function save($submission, &$layoutId, $sourceLayoutId = false) {
	
		$values = [];
		$duplicating = (bool) $sourceLayoutId;
		
		if ($duplicating) {
			$sourceDetails = \ze\row::get('layouts', [
				'skin_id', 'css_class', 'bg_image_id', 'bg_color', 'bg_position', 'bg_repeat',
				'json_data', 'json_data_hash',
				'cols', 'min_width', 'max_width', 'fluid', 'responsive',
				'head_html', 'head_cc', 'head_visitor_only', 'foot_html', 'foot_cc', 'foot_visitor_only'
			], $sourceLayoutId);
			
			foreach ($sourceDetails as $col => $value) {
				if (!isset($submission[$col])) {
					$values[$col] = $value;
				}
			}
		}
		
		foreach ([
			'skin_id', 'css_class', 'bg_image_id', 'bg_color', 'bg_repeat', 'bg_position',
			'name', 'cols', 'min_width', 'max_width', 'fluid', 'responsive'
		] as $var) {
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
			 || (!\ze\row::exists('content_item_versions', ['layout_id' => $layoutId]) && !\ze\row::exists('content_types', ['default_layout_id' => $layoutId]))) {
				$values['content_type'] = $submission['content_type'];
			}
		}
	
		if (isset($submission['json_data'])) {
			$values['json_data'] = $submission['json_data'];
			if (is_string($submission['json_data'])) {
				$values['json_data_hash'] = \ze::hash64($submission['json_data'], 8);
			} else {
				$values['json_data_hash'] = \ze::hash64(json_encode($submission['json_data']), 8);
			}
		}
	
		if ($layoutId && !$duplicating) {
			\ze\row::update('layouts', $values, $layoutId);
	
		} else {
			$layoutId = \ze\row::insert('layouts', $values);
		}
	
		if ($duplicating) {
		
			// Copy slots to duplicated layout
			$slots = \ze\row::getAssocs('layout_slot_link', 
				['slot_name'], 
				['layout_id' => $sourceLayoutId]);
			if ($slots) {
				$sql = '
					INSERT IGNORE INTO '.DB_PREFIX.'layout_slot_link (
						layout_id,
						slot_name
					) VALUES ';
				foreach ($slots as $slot) {
					$sql .= '("'.(int) $layoutId. '","'. \ze\escape::sql($slot['slot_name']). '"),';
				}
				$sql = trim($sql, ',');
				\ze\sql::update($sql);
			}
		
			$sql = "
				REPLACE INTO ". DB_PREFIX. "plugin_layout_link (
					module_id,
					instance_id,
					layout_id,
					slot_name
				) SELECT 
					module_id,
					instance_id,
					". (int) $layoutId.  ",
					slot_name
				FROM ". DB_PREFIX. "plugin_layout_link
				WHERE layout_id = ". (int) $sourceLayoutId;
			\ze\sql::update($sql);
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
		$toPlace = ['old' => [], 'new' => []];
	
		$layoutId['old'] = $oldLayoutId;
		$layoutId['new'] = $newLayoutId;
	
		//Get information on the templates we're using
		foreach (['old', 'new'] as $oon) {
			//Loop through the slots on the templates, seeing what Modules are placed where
			$layout[$oon] = \ze\content::layoutDetails($layoutId[$oon]);
			\ze\plugin::slotContents(
				$slotContents[$oon],
				$cID, $cType, $cVersion,
				$layoutId[$oon],
				$specificInstanceId = false, $specificSlotName = false, $ajaxReload = false,
				$runPlugins = false);
		
			foreach ($slotContents[$oon] as $slotName => $slot) {
				if (!empty($slot['content_id']) && !empty($slot['module_id'])) {
				
					//For the old Template, only count a slot if there is Content in there
					if ($oon == 'new'
					 || ((
						$sql = "
							SELECT 1
							FROM ". DB_PREFIX. "plugin_settings AS ps
							INNER JOIN ". DB_PREFIX. "plugin_setting_defs AS psd
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
							FROM ". DB_PREFIX. "plugin_settings AS ps
							INNER JOIN ". DB_PREFIX. "nested_plugins AS np
							   ON np.instance_id = ps.instance_id
							  AND np.id = ps.egg_id
							  AND np.is_slide = 1
							INNER JOIN ". DB_PREFIX. "plugin_setting_defs AS psd
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
			$version = ['layout_id' => $newLayoutId];
			\ze\contentAdm::updateVersion($cID, $cType, $cVersion, $version);
		
			//Loop through all of the matched slots
			foreach ($matches['old'] as $oSlotName => $nSlotName) {
				//Move Plugins/data between slots as needed
				if ($oSlotName != $nSlotName) {
					$oldSlot =
						[
						'module_id' => $slotContents['old'][$oSlotName]['module_id'],
						'content_id' => $cID,
						'content_type' => $cType,
						'content_version' => $cVersion,
						'slot_name' => $oSlotName];
				
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

	//Formerly "checkSkinInUse()"
	public static function skinInUse($skinId) {
		return
			\ze\row::exists('layouts', ['skin_id' => $skinId]);
	}

	//Delete a Layout from the system
	//Formerly "deleteLayout()"
	public static function delete($layoutId) {
	
		//Delete the layout from the database
		\ze\module::sendSignal('eventTemplateDeleted', ['layoutId' => $layoutId]);

		\ze\row::delete('layouts', ['layout_id' => $layoutId]);
		\ze\row::delete('plugin_layout_link', ['layout_id' => $layoutId]);
	}

	

	//Check how many items use a Layout or a Template Family
	//Formerly "checkTemplateUsage()"
	public static function usage($layoutId, $publishedOnly = false, $countItems = true, $checkWhereItemLayerIsUsed = false, $slotName = false) {
		
		if ($countItems) {
			$sql = "
				SELECT COUNT(DISTINCT c.tag_id)";
		} else {
			$sql = "
				SELECT DISTINCT c.tag_id";
		}
		
		$sql .= "
			FROM ". DB_PREFIX. "content_items AS c
			INNER JOIN ". DB_PREFIX. "content_item_versions as v
			   ON c.id = v.id
			  AND c.type = v.type";
	
		if ($publishedOnly) {
			$sql .= "
			  AND v.version = c.visitor_version
			INNER JOIN ". DB_PREFIX. "layouts AS t
			   ON t.layout_id = v.layout_id";
	
		} else {
			$sql .= "
			  AND v.version IN (c.admin_version, c.visitor_version)
			INNER JOIN ". DB_PREFIX. "layouts AS t
			   ON t.layout_id = v.layout_id";
		}
	
		if ($checkWhereItemLayerIsUsed) {
			$sql .= "
			INNER JOIN ". DB_PREFIX. "plugin_item_link AS pil
			   ON pil.content_id = v.id
			  AND pil.content_type = v.type
			  AND pil.content_version = v.version
			  AND pil.slot_name = '". \ze\escape::asciiInSQL($slotName). "'";
		}
	
		if ($publishedOnly) {
			$sql .= "
			WHERE c.status IN ('published_with_draft', 'published')";
	
		} else {
			$sql .= "
			WHERE c.status IN ('first_draft', 'published_with_draft', 'hidden_with_draft', 'trashed_with_draft', 'published')";
		}
	
		
		$sql .= "
			AND v.layout_id = ". (int) $layoutId;
	
		if ($countItems) {
			return \ze\sql::fetchValue($sql);
		} else {
			return \ze\sql::fetchValues($sql);
		}
	}


	//Work out a slot to put this Plugin into, favouring empty "Main" slots. Default to Main_3.
	//Formerly "getTemplateMainSlot()"
	public static function mainSlotByName($layoutId, $guess1 = 'Main_3', $guess2 = 'Main') {
		$sql = "
			SELECT lsl.slot_name
			FROM ". DB_PREFIX. "layout_slot_link AS lsl
			LEFT JOIN ". DB_PREFIX. "layouts AS t
			   ON lsl.layout_id = t.layout_id
			LEFT JOIN ". DB_PREFIX. "plugin_layout_link AS pitl
			   ON t.layout_id = pitl.layout_id
			  AND lsl.slot_name = pitl.slot_name
			WHERE lsl.layout_id = '". \ze\escape::sql($layoutId). "'
			GROUP BY lsl.slot_name
			ORDER BY
				pitl.slot_name IS NULL DESC,
				lsl.slot_name LIKE '". \ze\escape::like(\ze\escape::ascii($guess1)). "%' DESC,
				lsl.slot_name LIKE '". \ze\escape::like(\ze\escape::ascii($guess2)). "%' DESC,
				lsl.slot_name
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
			SELECT lsl.slot_name
			FROM ". DB_PREFIX. "layouts AS t
			INNER JOIN ". DB_PREFIX. "layout_slot_link AS lsl
			   ON lsl.layout_id = t.layout_id
			INNER JOIN ". DB_PREFIX. "plugin_layout_link AS pitl
			   ON pitl.layout_id = t.layout_id
			  AND pitl.slot_name = lsl.slot_name
			WHERE pitl.layout_id = ". (int) $layoutId. "
			  AND pitl.module_id = ". (int) $moduleId. "
			  AND pitl.instance_id = 0
			GROUP BY lsl.slot_name
			ORDER BY
				pitl.slot_name IS NOT NULL DESC,
				lsl.slot_name IS NOT NULL DESC,
				lsl.slot_name LIKE '%Main%' DESC,
				lsl.ord,
				lsl.slot_name";
	
		if ($limitToOne) {
			$sql .= "
				LIMIT 1";
		}
	
		$slots = [];
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
		return \ze\link::absolute(). 'organizer.php#'.
				'zenario__layouts/panels/layouts/view_content//'. (int) $layoutId.  '//';
	}
	
	//Function to display content item attached with particular slotname
	public static function slotUsage($layoutId, $slotName) {
		
		$sql = "
			SELECT DISTINCT c.tag_id
			FROM ". DB_PREFIX. "content_items AS c
			INNER JOIN ". DB_PREFIX. "content_item_versions AS v
			   ON v.id = c.id
			  AND v.type = c.type
			  AND v.version IN (c.visitor_version, admin_version)
			  AND v.layout_id = ". (int) $layoutId. "
			INNER JOIN ". DB_PREFIX. "plugin_instances AS pi
			   ON pi.content_id = v.id
			  AND pi.content_type = v.type
			  AND pi.content_version = v.version
			  AND pi.slot_name = '". \ze\escape::asciiInSQL($slotName).  "'
			INNER JOIN ". DB_PREFIX. "plugin_settings AS ps
			   ON ps.instance_id = pi.id
			  AND ps.is_content = 'version_controlled_content'
			  AND ps.name = 'html'
			  AND ps.value != ''
			  AND ps.value IS NOT NULL
			WHERE c.status NOT IN ('hidden','trashed','deleted')
			  AND (c.id, c.type) IN (
				SELECT DISTINCT vs.id, vs.type
				FROM ". DB_PREFIX. "content_item_versions AS vs
				WHERE vs.layout_id = ". (int) $layoutId. "
			  )";
						
		return \ze\sql::fetchValues($sql);
	}

}