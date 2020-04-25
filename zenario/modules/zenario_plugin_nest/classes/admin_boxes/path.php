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
		
		if (!$instance = ze\plugin::details($box['key']['instanceId'])) {
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
		
		//Get the details of this slide
		$slide = ze\row::get('nested_plugins',
			['id', 'slide_num', 'use_slide_layout', 'request_vars', 'hierarchical_var'],
			['instance_id' => $box['key']['instanceId'], 'states' => [$box['key']['state']]]
		);
		$box['key']['slideNum'] = $slide['slide_num'];
		
		$moduleDescs = [];
		$modulesAndModes = [];
		$moduleIdsToNames = [];
		
		//Look up the module ids and modes of all of the plugins used on this page
		$sql = "
			SELECT np.module_id, np.makes_breadcrumbs, ps.value AS mode
			FROM ". DB_PREFIX. "nested_plugins AS np
			LEFT JOIN ". DB_PREFIX. "plugin_settings AS ps
			   ON ps.instance_id = np.instance_id
			  AND ps.egg_id = np.id
			  AND ps.name = 'mode'
			WHERE np.instance_id = ". (int) $box['key']['instanceId']. "
			  AND np.slide_num = ". (int) $box['key']['slideNum']. "
			  AND is_slide = 0";
		$result = ze\sql::select($sql);
		
		//Note each down
		$somethingMakesBreadcrumbs = false;
		while ($egg = ze\sql::fetchAssoc($result)) {
			$mode = $egg['mode'];
			$moduleId = $egg['module_id'];
			
			if (!isset($moduleIdsToNames[$moduleId])) {
				$moduleIdsToNames[$moduleId] = ze\module::className($moduleId);
			}
			$moduleClassName = $moduleIdsToNames[$moduleId];
			
			if ($egg['makes_breadcrumbs'] > 1) {
				$somethingMakesBreadcrumbs = true;
			}
			
			$moduleDescs[$moduleClassName] = [];
			$modulesAndModes[$moduleClassName. '-'. $mode] = [$moduleClassName, $mode];
		}
		
		//Check which slide layouts are used on this slide, of any
		$slKey = false;
		switch ($slide['use_slide_layout']) {
			case 'asset_schema':
			case 'datapool_schema':
				if (ze\module::inc('assetwolf_2')) {
					$slKey = assetwolf_2::getAllPossibleSlideLayoutsForSlide($slide);
				}
				break;
		}
		
		//If there were some used, check which plugins/modes are there and note those down as well
		if (!empty($slKey)) {
			foreach (ze\row::getValues('slide_layouts', 'data', $slKey) as $data) {
				if ($data = json_decode($data, true)) {
					if (!empty($data) && is_array($data)) {
						foreach ($data as $plugin) {
							if (($moduleClassName = $plugin['class_name'] ?? false)
							 && ($mode = $plugin['settings']['mode'] ?? false)) {
								$moduleDescs[$moduleClassName] = [];
								$modulesAndModes[$moduleClassName. '-'. $mode] = [$moduleClassName, $mode];
							}
						}
					}
				}
			}
		}
		
		//Load the description.yaml file of all of the modules used
		foreach ($moduleDescs as $moduleClassName => &$desc) {
			ze\moduleAdm::loadDescription($moduleClassName, $desc);
		}
		unset($desc);
		
		$ord = 2;
		$commands = [];
		foreach ($modulesAndModes as $moduleAndMode) {
			$moduleClassName = $moduleAndMode[0];
			$mode = $moduleAndMode[1];
			$desc = $moduleDescs[$moduleClassName];
			
			if (!empty($desc['path_commands'])) {
				foreach($desc['path_commands'] as $command => $details) {
					
					if (!empty($details['hidden'])) {
						continue;
					}
					
					if (!isset($details['modes']) || in_array($mode, $details['modes'])) {
						$commands[$command] = $details;
					}
				}
			}
		}
		
		ksort($commands);
		foreach ($commands as $command => $details) {
			$fields['path/command']['values'][$command] = [
				'ord' => ++$ord,
				'label' => empty($details['label'])? $command : $details['label']. ' ('. $command. ')',
				'request_vars' => implode(',', $details['request_vars'] ?? []),
				'hierarchical_var' => $details['hierarchical_var'] ?? ''
			];
		}
		
		if ($details = ze\row::get('nested_paths', true, $this->getKey($box))) {
			$values['path/is_forwards'] = $details['is_forwards'];
			$values['path/custom_vars'] = $details['request_vars'];
			
			if (isset($fields['path/command']['values'][$details['command']])) {
				$values['path/command'] = $details['command'];
			} else {
				$values['path/custom_command'] = $details['command'];
				$values['path/command'] = '#custom#';
			}
			
			if ($box['key']['linkToOtherContentItem']) {
				if ($details['equiv_id']) {
					$values['path/citem'] = $details['content_type']. '_'. $details['equiv_id'];
				}
				$values['path/to_state'] = $details['to_state'];
				
				$box['title'] = ze\admin::phrase('Editing the path from state [[state]]', $box['key']);
			} else {
				$box['title'] = ze\admin::phrase('Editing the path from state [[state]] to state [[to_state]]', $box['key']);
			}
		} else {
			if ($box['key']['linkToOtherContentItem']) {
				$box['title'] = ze\admin::phrase('Creating the path from state [[state]]', $box['key']);
			} else {
				$box['title'] = ze\admin::phrase('Creating the path from state [[state]] to state [[to_state]]', $box['key']);
			}
		}
		
		if ($box['key']['linkToOtherContentItem']) {
			$fields['path/command']['label'] = ze\admin::phrase('Follow this link when a plugin issues the command:', $box['key']);
		} else {
			$fields['path/command']['label'] = ze\admin::phrase('Go from state [[state]] to state [[to_state]] when a plugin issues the command:', $box['key']);
		}
		
	
		if ($instance['content_id']) {
			ze\priv::exitIfNot('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version']);
		} else {
			ze\priv::exitIfNot('_PRIV_VIEW_REUSABLE_PLUGIN');
		}
		
		$fields['path/no_breadcrumb_plugin_set']['hidden'] = $somethingMakesBreadcrumbs;
	}
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($values['path/command'] == '#custom#'
		 && !$values['path/custom_command']) {
			$fields['path/custom_command']['error'] = ze\admin::phrase('Please enter the name of a command');
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if ($values['path/command'] == '#custom#') {
			foreach (ze\ray::explodeAndTrim($values['path/custom_vars']) as $var) {
				if (!\ze\ring::validateScreenName($var)) {
					$fields['path/custom_vars']['error'] =
						$this->phrase("Please don't enter any special characters in the request variables.");
				}
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$instance = ze\plugin::details($box['key']['instanceId']);
		
		//Load details of this Instance, and check for permissions to save
		if ($instance['content_id']) {
			ze\priv::exitIfNot('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version']);
			ze\contentAdm::updateVersion($instance['content_id'], $instance['content_type'], $instance['content_version']);
		} else {
			ze\priv::exitIfNot('_PRIV_MANAGE_REUSABLE_PLUGIN');
		}
		
		if ($values['path/command'] == '#custom#') {
			$custom = 1;
			$command = $values['path/custom_command'];
			$rVar = $values['path/custom_vars'];
			$hVar = '';
		} else {
			$custom = 0;
			$command = $values['path/command'];
			$rVar = $fields['path/command']['values'][$command]['request_vars'] ?? '';
			$hVar = $fields['path/command']['values'][$command]['hierarchical_var'] ?? '';
		}
		
		$equivId = $cType = false;
		ze\content::getCIDAndCTypeFromTagId($equivId, $cType, $values['path/citem']);
		
		if ($box['key']['linkToOtherContentItem']
		 && ($box['key']['to_state'] != strtolower($values['path/to_state'])
		  || $box['key']['equiv_id'] != $equivId
		  || $box['key']['content_type'] != $cType)) {
			
			ze\row::delete('nested_paths', $this->getKey($box));
			
			$box['key']['to_state'] = strtolower($values['path/to_state']);
			$box['key']['equiv_id'] = $equivId;
			$box['key']['content_type'] = $cType;
		}
		
		//If the "is forwards" checkbox is checked, don't let any other path from this state
		//be flagged as forwards
		if ($values['path/is_forwards']) {
			ze\row::update(
				'nested_paths',
				[
					'is_forwards' => 0
				],
				[
					'instance_id' => $box['key']['instanceId'],
					'from_state' => $box['key']['state']
				]
			);
		}
		
		ze\row::set(
			'nested_paths',
			[
				'is_custom' => $custom,
				'slide_num' => $box['key']['slideNum'],
				'command' => preg_replace('/\s/', '', $command),
				'is_forwards' => $values['path/is_forwards'],
				'request_vars' => $rVar,
				'hierarchical_var' => $hVar
			],
			$this->getKey($box)
		);
		
		ze\pluginAdm::calcConductorHierarchy($box['key']['instanceId']);
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}

}