<?php


class zenario_plugin_nest__admin_boxes__path extends zenario_plugin_nest {
	
	
	protected function getKey(&$box) {
		return [
			'instance_id' => $box['key']['instanceId'],
			'from_state' => $box['key']['state'],
			'to_state' => $box['key']['to_state'],
			'equiv_id' => $box['key']['equiv_id'],
			'content_type' => $box['key']['content_type']
		];
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (!($_GET['refiner__nest'] ?? false)) {
			exit;
		}
		
		$box['key']['instanceId'] = $_GET['refiner__nest'] ?? false;
		
		if (!$instance = getPluginInstanceDetails($box['key']['instanceId'])) {
			exit;
		}
		
		$ids = explode('_', $box['key']['id']);
		
		if (empty($box['key']['state']) && !empty($ids[1])) {
			$box['key']['state'] = $ids[1];
		}
		if (!empty($ids[2])) {
			$box['key']['to_state'] = $ids[2];
		}
		
		if ($box['key']['linkToOtherContentItem'] == 'autodetect') {
			$box['key']['linkToOtherContentItem'] = !empty($ids[3]);
		}
		
		if ($box['key']['linkToOtherContentItem']) {
			if (!empty($ids[3])) {
				$box['key']['equiv_id'] = (int) $ids[3];
			}
			if (!empty($ids[4])) {
				$box['key']['content_type'] = $ids[4];
			}
			
			$box['max_height'] += 200;
		}
		
		
		//Look up the module ids of all of the plugins used on this page
		$slideNum = getRow('nested_plugins', 'slide_num', ['instance_id' => $box['key']['instanceId'], 'states' => [$box['key']['state']]]);
		
		$sql = "
			SELECT np.module_id, GROUP_CONCAT(DISTINCT ps.value SEPARATOR ', ') AS modes
			FROM ". DB_NAME_PREFIX. "nested_plugins AS np
			LEFT JOIN ". DB_NAME_PREFIX. "plugin_settings AS ps
			   ON ps.instance_id = np.instance_id
			  AND ps.egg_id = np.id
			  AND ps.name = 'mode'
			WHERE np.instance_id = ". (int) $box['key']['instanceId']. "
			  AND np.slide_num = ". (int) $slideNum. "
			  AND is_slide = 0
			GROUP BY np.module_id";
		$result = sqlSelect($sql);
		
		$ord = 2;
		$commands = array();
		while ($egg = sqlFetchAssoc($result)) {
			$modes = explodeAndTrim($egg['modes']);
			$tags = array();
			if ((loadModuleDescription(getModuleName($egg['module_id']), $tags))
			 && !empty($tags['commands'])) {
				foreach($tags['commands'] as $command => $details) {
					if (!isset($details['modes'])
					 || !empty(array_intersect($details['modes'], $modes))) {
						
						if (empty($details['label'])) {
							$commands[$command] = $command;
						} else {
							$commands[$command] = $details['label']. ' ('. $command. ')';
						}
					}
				}
			}
		}
		asort($commands);
		foreach ($commands as $command => $label) {
			$fields['path/commands']['values'][$command] = ['ord' => ++$ord, 'label' => $label];
		}
		
		if ($details = getRow('nested_paths', true, $this->getKey($box))) {
			
			if (isset($fields['path/commands']['values'][$details['commands']])) {
				$values['path/commands'] = $details['commands'];
			} else {
				$values['path/command_custom'] = $details['commands'];
				$values['path/commands'] = '#custom#';
			}
			
			if ($box['key']['linkToOtherContentItem']) {
				if ($details['equiv_id']) {
					$values['path/citem'] = $details['content_type']. '_'. $details['equiv_id'];
				}
				$values['path/to_state'] = $details['to_state'];
				
				$box['title'] = adminPhrase('Editing the path from state [[state]]', $box['key']);
			} else {
				$box['title'] = adminPhrase('Editing the path from state [[state]] to state [[to_state]]', $box['key']);
			}
		} else {
			if ($box['key']['linkToOtherContentItem']) {
				$box['title'] = adminPhrase('Creating the path from state [[state]]', $box['key']);
			} else {
				$box['title'] = adminPhrase('Creating the path from state [[state]] to state [[to_state]]', $box['key']);
			}
		}
		
		if ($box['key']['linkToOtherContentItem']) {
			$fields['path/commands']['label'] = adminPhrase('Follow this link when a plugin issues the command:', $box['key']);
		} else {
			$fields['path/commands']['label'] = adminPhrase('Go from state [[state]] to state [[to_state]] when a plugin issues the command:', $box['key']);
		}
		
	
		if ($instance['content_id']) {
			exitIfNotCheckPriv('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version']);
		} else {
			exitIfNotCheckPriv('_PRIV_VIEW_REUSABLE_PLUGIN');
		}
	}
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($values['path/commands'] == '#custom#'
		 && !$values['path/command_custom']) {
			$fields['path/command_custom']['error'] = adminPhrase('Please enter the name of a command');
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$instance = getPluginInstanceDetails($box['key']['instanceId']);
		
		//Load details of this Instance, and check for permissions to save
		if ($instance['content_id']) {
			exitIfNotCheckPriv('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version']);
			updateVersion($instance['content_id'], $instance['content_type'], $instance['content_version']);
		} else {
			exitIfNotCheckPriv('_PRIV_MANAGE_REUSABLE_PLUGIN');
		}
		
		if ($values['path/commands'] == '#custom#') {
			$command = $values['path/command_custom'];
		} else {
			$command = $values['path/commands'];
		}
		
		$equivId = $cType = false;
		getCIDAndCTypeFromTagId($equivId, $cType, $values['path/citem']);
		
		if ($box['key']['linkToOtherContentItem']
		 && ($box['key']['to_state'] != strtoupper($values['path/to_state'])
		  || $box['key']['equiv_id'] != $equivId
		  || $box['key']['content_type'] != $cType)) {
			
			deleteRow('nested_paths', $this->getKey($box));
			
			$box['key']['to_state'] = strtoupper($values['path/to_state']);
			$box['key']['equiv_id'] = $equivId;
			$box['key']['content_type'] = $cType;
		}
		
		setRow(
			'nested_paths',
			array('commands' => preg_replace('/\s/', '', $command)),
			$this->getKey($box)
		);
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}

}