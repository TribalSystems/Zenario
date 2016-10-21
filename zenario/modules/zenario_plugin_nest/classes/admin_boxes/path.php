<?php


class zenario_plugin_nest__admin_boxes__path extends zenario_plugin_nest {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (!get('refiner__nest')) {
			exit;
		}
		
		$box['key']['instanceId'] = get('refiner__nest');
		
		if (!$instance = getPluginInstanceDetails($box['key']['instanceId'])) {
			exit;
		}
		
		$ids = explode('_', $box['key']['id']);
		
		if (!empty($ids[1])) {
			$box['key']['from_state'] = $ids[1];
		}
		if (!empty($ids[2])) {
			$box['key']['to_state'] = $ids[2];
		}
		
		if ($details = getRow(
		 	'nested_paths',
		 	true,
		 	array('instance_id' => $box['key']['instanceId'], 'from_state' => $box['key']['from_state'], 'to_state' => $box['key']['to_state'])
		)) {
			$values['path/commands'] = $details['commands'];
			$box['title'] = adminPhrase('Editing the path from state [[from_state]] to state [[to_state]]', $box['key']);
		} else {
			$box['title'] = adminPhrase('Creating the path from state [[from_state]] to state [[to_state]]', $box['key']);
		}
	
		if ($instance['content_id']) {
			exitIfNotCheckPriv('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version']);
		} else {
			exitIfNotCheckPriv('_PRIV_VIEW_REUSABLE_PLUGIN');
		}
		
	}
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
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
		
		setRow(
			'nested_paths',
			array('commands' => preg_replace('/\s/', '', $values['path/commands'])),
			array('instance_id' => $box['key']['instanceId'], 'from_state' => $box['key']['from_state'], 'to_state' => $box['key']['to_state'])
		);
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//Restore the prefix on the id, if it was there
		if ($box['key']['idInOrganizer']) {
			$box['key']['id'] = $box['key']['idInOrganizer'];
		}
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}

}