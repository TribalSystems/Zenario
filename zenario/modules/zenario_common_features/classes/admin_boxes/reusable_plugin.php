<?php
/*
 * Copyright (c) 2017, Tribal Limited
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


class zenario_common_features__admin_boxes__reusable_plugin extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if (!$instance = getPluginInstanceDetails($box['key']['id'])) {
			exit;
		}
		
		if ($box['key']['duplicate']) {
			$box['tabs']['instance']['edit_mode']['always_on'] = true;
			$box['title'] = adminPhrase('Duplicating the plugin "[[instance_name]]".', $instance);
		
		} else {
			$box['title'] = adminPhrase('Renaming the plugin "[[instance_name]]".', $instance);
		}
		
		$box['tabs']['instance']['fields']['name']['value'] = $instance['instance_name'];
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (engToBoolean($box['tabs']['instance']['edit_mode']['on'] ?? false) && checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			if ($values['instance/name']) {
				//Check to see if an instance of that name already exists
				$sql = "
					SELECT 1
					FROM ". DB_NAME_PREFIX. "plugin_instances
					WHERE name =  '". sqlEscape($values['instance/name']). "'";
				
				if (!$box['key']['duplicate']) {
					$sql .= "
					  AND id != ". (int) $box['key']['id'];
				}
				
				$result = sqlQuery($sql);
				if (sqlNumRows($result)) {
					$box['tabs']['instance']['errors'][] = adminPhrase('A plugin with the name "[[name]]" already exists. Please choose a different name.', array('name' => $values['instance/name']));
				}
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		if (engToBoolean($box['tabs']['instance']['edit_mode']['on'] ?? false) && checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			$eggId = false;
			if ($box['key']['duplicate']) {
				renameInstance($box['key']['id'], $eggId, $values['instance/name'], $createNewInstance = true);
			
			} else {
				renameInstance($box['key']['id'], $eggId, $values['instance/name'], $createNewInstance = false);
			}
		}
	}
}
