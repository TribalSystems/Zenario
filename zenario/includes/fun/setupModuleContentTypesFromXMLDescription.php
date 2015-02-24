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


$desc = false;
if (!loadModuleDescription($moduleName, $desc)
 || !$moduleId = getModuleId($moduleName)) {
	return false;
}

//Add/update any content types
if (!empty($desc['content_types']) && is_array($desc['content_types'])) {
	foreach($desc['content_types'] as $type) {
		if (!empty($type['content_type_id'])
		 && !empty($type['content_type_name_en'])) {
			$sql = "
				INSERT INTO ". DB_NAME_PREFIX. "content_types SET
					content_type_id = '". sqlEscape($type['content_type_id']). "',
					content_type_name_en = '". sqlEscape($type['content_type_name_en']). "',
					writer_field = '". ifNull(sqlEscape(arrayKey($type, 'writer_field')), 'hidden'). "',
					description_field = '". ifNull(sqlEscape(arrayKey($type, 'description_field')), 'optional'). "',
					keywords_field = '". ifNull(sqlEscape(arrayKey($type, 'keywords_field')), 'optional'). "',
					summary_field = '". ifNull(sqlEscape(arrayKey($type, 'summary_field')), 'optional'). "',
					release_date_field = '". ifNull(sqlEscape(arrayKey($type, 'release_date_field')), 'optional'). "',
					enable_summary_auto_update = ". engToBooleanArray($type, 'enable_summary_auto_update'). ",
					module_id = ". (int) $moduleId. "
				ON DUPLICATE KEY UPDATE
					module_id = ". (int) $moduleId;
			sqlQuery($sql);
			
			//Make sure a template exists for this Content Type, creating it if it doesn't
			if (!$layoutId = getRow('layouts', 'layout_id', array('content_type' => $type['content_type_id']))) {
				//Find an HTML Layout to copy; try to pick the most popular one, otherwise just pick the first one
				$sql = "
					SELECT t.*
					FROM ". DB_NAME_PREFIX. "content AS c
					INNER JOIN ". DB_NAME_PREFIX. "versions AS v
					   ON v.id = c.id
					  AND v.type = c.type
					  AND v.version = c.admin_version
					INNER JOIN ". DB_NAME_PREFIX. "layouts AS t
					   ON t.layout_id = v.layout_id
					  AND t.content_type = v.type
					WHERE c.status NOT IN ('hidden','trashed','deleted')
					  AND c.type = 'html'
					GROUP BY t.layout_id
					ORDER BY COUNT(c.tag_id) DESC, t.layout_id
					LIMIT 1";
				
				if (!($result = sqlQuery($sql)) || !($layout = sqlFetchAssoc($result))) {
					$layout = getRow('layouts', true, array('content_type' => 'html'));
				}
				
				if ($layout) {
					//Work out a slot to put this Plugin into, favouring empty "Main" slots.
					$slotName = getTemplateMainSlot($layout['family_name'], $layout['file_base_name']);
					
					//Make a copy of the template files if we can
					$layout['file_base_name'] = $layout['file_base_name'];
					
					//Make a copy of that Layout for the new Content Type
					$layout['templateFamily'] = $layout['family_name'];
					$layout['content_type'] = (string) $type['content_type_id'];
					$layout['name'] = ifNull((string) arrayKey($type, 'default_template_name'), (string) $type['content_type_name_en']);
					
					saveTemplate($layout, $layoutId, $layout['layout_id']);
					
					//Put an instance of this Plugin on that template, if this module uses instances
					//Otherwise put an instance of the WYSIWYG Plugin on that template, if it's running
					$addingEditor = false;
					if ((engToBoolean($desc['is_pluggable']) && ($addmoduleId = $moduleId))
					 || (($addmoduleId = (getModuleIdByClassName('zenario_wysiwyg_editor'))) && ($addingEditor = true))) {
						
						//Insert this Plugin onto the page
						if ($addingEditor || engToBoolean($desc['can_be_version_controlled'])) {
							//Prefer a Wireframe Plugin if the Plugin allows it
							updatePluginInstanceInTemplateSlot(0, $slotName, $layout['family_name'], $layoutId, $addmoduleId);
						
						} else {
							//Otherwise set a Reusable Instance there
							if (!$instanceId = getRow('plugin_instances', 'id', array('module_id' => $addmoduleId, 'content_id' => 0))) {
								//Create a new reusable instance if one does not already exist
								$errors = array();
								createNewInstance(
									$addmoduleId,
									$desc['default_instance_name'],
									$instanceId,
									$errors, $onlyValidate = false, $forceName = true);
							}
							
							updatePluginInstanceInTemplateSlot($instanceId, $slotName, $layout['family_name'], $layoutId, $addmoduleId);
						}
					}
				}
			}
			
			//Ensure a default template is set
			updateRow(
				'content_types',
				array('default_layout_id' => $layoutId),
				array('content_type_id' => $type['content_type_id'], 'default_layout_id' => 0));
		}
	}
}