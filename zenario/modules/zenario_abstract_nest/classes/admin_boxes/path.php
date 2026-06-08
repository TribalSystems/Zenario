<?php


class zenario_abstract_nest__admin_boxes__path extends zenario_abstract_nest {
	
	
	protected function getKey(&$box) {
		return [
			'instance_id' => $box['key']['instanceId'],
			'from_state' => $box['key']['state'],
			'to_state' => $box['key']['to_state'],
			'equiv_id' => $box['key']['equiv_id'],
			'content_type' => $box['key']['content_type']
		];
	}
	
	protected function slideNumFromState($state, &$instanceId) {
		$sql = "
			SELECT id, slide_num, request_vars, states, slide_label
			FROM ". DB_PREFIX. "nested_plugins AS slide
			WHERE FIND_IN_SET('". ze\escape::sql($state). "', slide.states)
			  AND slide.is_slide = 1
			  AND slide.instance_id = ". (int) $instanceId;
		
		return ze\sql::fetchAssoc($sql);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (!ze::get('refiner__nest')) {
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
		
		$mrg = $box['key'];
		$fromSlide = $this->slideNumFromState($box['key']['state'], $box['key']['instanceId']);
		$mrg['from_slide_num'] = $fromSlide['slide_num'];
		$mrg['from_slide_label'] = $fromSlide['slide_label'];
		
		if (!empty($box['key']['to_state']) && ($toSlide = $this->slideNumFromState($box['key']['to_state'], $box['key']['instanceId']))) {
			$mrg['to_slide_num'] = $toSlide['slide_num'];
			$mrg['to_slide_label'] = $toSlide['slide_label'];
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
		}
		
		//Get the details of this slide
		$slide = ze\row::get('nested_plugins',
			['id', 'slide_num', 'request_vars', 'states'],
			['instance_id' => $box['key']['instanceId'], 'states' => [$box['key']['state']]]
		);
		$box['key']['slideNum'] = $slide['slide_num'];
		
		
		//Check if this slide has multiple states (using the advanced options).
		//We'll change the labels slightly in this situation.
		$states = ze\ray::explodeAndTrim($slide['states']);
		$box['key']['multipleStates'] = count($states) > 1;
		
		
		$moduleDescs = [];
		$modulesAndModes = [];
		$moduleIdsToNames = [];
		
		//Look up the module ids and modes of all of the plugins used on this slide
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
		
		//Load the description.yaml file of all of the modules used
		foreach ($moduleDescs as $moduleClassName => &$desc) {
			ze\moduleAdm::loadDescription($moduleClassName, $desc);
		}
		unset($desc);
		
		$ord = 2;
		$commands = [];
		$modulesAddingCommands = [];
		$commandAndModuleLink = [];
		$parentMovuleVisibleChildren = [];
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
						
						if (!isset($modulesAddingCommands[$command])) {
							$modulesAddingCommands[$command] = [];
						}
						$modulesAddingCommands[$command][] = $desc['display_name'] ?? $moduleClassName;
					}
					
					if (!isset($fields['path/command']['values'][$moduleClassName])) {
						$fields['path/command']['values'][$moduleClassName] = [
							'label' => ze\admin::phrase('Commands from module [[module_display_name]]', ['module_display_name' => $moduleDescs[$moduleClassName]['display_name']]),
							'ord' => ++$ord,
							'disabled' => true,
							'class' => 'zenario_radio_label_only zenario_radio_with_module_icon',
							'hide_when_children_are_not_visible' => true
						];
						
						$parentMovuleVisibleChildren[$moduleClassName] = false;
					}
					
					$commandAndModuleLink[$command] = $moduleClassName;
				}
			}
		}
		
		foreach ($commands as $command => $details) {
			$fields['path/command']['values'][$command] = [
				'ord' => ++$ord,
				'label' => empty($details['label'])? $command : $command. ' ('. $details['label']. ')',
				'request_vars' => implode(',', $details['request_vars'] ?? []),
				'parent' => $commandAndModuleLink[$command],
				'split_values_if_selected' => true
			];
			
			$parentMovuleVisibleChildren[$moduleClassName] = true;
		}
		
		foreach ($parentMovuleVisibleChildren as $moduleClassName => $hasVisibleChildren) {
			if (isset($fields['path/command']['values'][$moduleClassName]) && !$hasVisibleChildren) {
				$fields['path/command']['values'][$moduleClassName]['hidden'] = true;
			}
		}
		
		
		//Check if there are any banner plugins on this slide that add custom commands,
		//and add radio options for them.
		$sql = "
			SELECT ps.value
			FROM ". DB_PREFIX. "nested_plugins AS np
			INNER JOIN ". DB_PREFIX. "plugin_settings AS ps
			   ON ps.instance_id = np.instance_id
			  AND ps.egg_id = np.id
			  AND ps.name = 'custom_command'
			WHERE np.instance_id = ". (int) $box['key']['instanceId']. "
			  AND np.is_slide = 0
			  AND np.slide_num = ". (int) $box['key']['slideNum']. "
			ORDER BY ps.value";
		
		foreach (ze\sql::fetchValues($sql) as $command) {
			if (!isset($fields['path/command']['values'][$command])) {
				$fields['path/command']['values'][$command] = [
					'ord' => ++$ord,
					'label' => $command,
					'split_values_if_selected' => true,
					'tooltip' => ze\admin::phrase('Issued by a Banner plugin on this slide.')
				];
			}
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
				
				$box['title'] = ze\admin::phrase('Editing the path from [[from_slide_num]]. [[from_slide_label]] (state [[state]])', $mrg);
			} else {
				$box['title'] = ze\admin::phrase('Editing the path from [[from_slide_num]]. [[from_slide_label]] (state [[state]]) to [[to_slide_num]]. [[to_slide_label]] (state [[to_state]])', $mrg);
			}
		} else {
			if ($box['key']['linkToOtherContentItem']) {
				$box['title'] = ze\admin::phrase('Creating a path from [[from_slide_num]]. [[from_slide_label]] (state [[state]])', $mrg);
			} else {
				$box['title'] = ze\admin::phrase('Creating a path from [[from_slide_num]]. [[from_slide_label]] (state [[state]]) to [[to_slide_num]]. [[to_slide_label]] (state [[to_state]])', $mrg);
			}
		}
		
		if ($box['key']['linkToOtherContentItem']) {
			$fields['path/command']['label'] = ze\admin::phrase('Follow this link when a plugin issues the command:', $mrg);
		} else {
			$fields['path/command']['label'] = ze\admin::phrase('Command name to navigate user from state [[state]] to state [[to_state]]:', $mrg);
			$fields['path/command']['note_below'] = ze\admin::phrase('Commands are issued by plugins on the slides of a nest. The command that is issued determines navigation from one slide to another.');
		}
		
	
		if ($instance['content_id']) {
			ze\priv::exitIfNot('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version']);
		} else {
			ze\priv::exitIfNot('_PRIV_VIEW_REUSABLE_PLUGIN');
		}
		
		
		//Check if the target slide has a back link set
		if (!$box['key']['linkToOtherContentItem']) {
			$sql = '
				SELECT 1
				FROM '. DB_PREFIX. 'nested_paths AS back
				WHERE back.instance_id = '. (int) $instance['instance_id']. '
				  AND back.from_state = \''. \ze\escape::sql($box['key']['to_state']). '\'
				  AND back.command = \'back\'
				  AND back.equiv_id = 0
				LIMIT 1';
			
			$fields['path/no_path_back_set']['hidden'] = ze\sql::exists($sql);
		}
		
		$fields['path/is_forwards']['notices_below']['no_breadcrumb_plugin_set']['hidden'] = $somethingMakesBreadcrumbs;
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
			
			$command = $values['path/custom_command'];
		} else {
			$command = $values['path/command'];
		}
		
		//Stop the admin from creating two identical commands from the same slide/state going to
		//two different places
		if (!empty($command)) {
			$sql = '
				SELECT 1
				FROM '. DB_PREFIX. 'nested_paths AS path
				WHERE path.instance_id = '. (int) $box['key']['instanceId']. '
				  AND path.from_state = \''. \ze\escape::sql($box['key']['state']). '\'
				  AND path.command = \''. ze\escape::sql($command). '\'
				  AND (path.to_state != \''. \ze\escape::sql($box['key']['to_state']). '\'
					OR path.equiv_id != '. (int) $box['key']['equiv_id']. '
					OR path.content_type != \''. \ze\escape::sql($box['key']['content_type']). '\')
				LIMIT 1';
			
			if (ze\sql::exists($sql)) {
				$mrg = $box['key'];
				$mrg['command'] = $command;
				
				if ($values['path/command'] == '#custom#') {
					$fields['path/custom_command']['error'] = ze\admin::phrase('State [[state]] already has a [[command]] command from it.', $mrg);
				} else {
					$box['tabs']['path']['errors'][] = ze\admin::phrase('State [[state]] already has a [[command]] command from it.', $mrg);
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
		} else {
			$custom = 0;
			$command = $values['path/command'];
			$rVar = $fields['path/command']['values'][$command]['request_vars'] ?? '';
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
				'request_vars' => $rVar
			],
			$this->getKey($box)
		);
		
		ze\pluginAdm::calcConductorHierarchy($box['key']['instanceId']);
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}

}
