<?php


class zenario_abstract_nest__admin_boxes__convert_nest extends zenario_abstract_nest {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//Watch out for someone opening this FAB from the front-end where the request format is different
		if (!empty($box['key']['instanceId'])) {
			//Use "id" for the instance ID in this FAB.
			$box['key']['id'] = $box['key']['instanceId'];
		}
		
		//Try to load details about this plugin instance
		if (!$instance = ze\plugin::details($box['key']['id'])) {
			exit;
		}
		
		//Set the identifier and title, similar to how they appear on the plugin settings FAB
		$box['identifier']['value'] = ze\plugin::codeName($instance['instance_id'], $instance['class_name']);
		$box['title'] = ze\admin::phrase('Converting the nest "[[name]]"', $instance);
		
		//Look up what some of the current plugin settings are
		$instanceId = $box['key']['id'];
		$animation_library = ze\plugin::setting('animation_library', $instanceId);
		$nest_type = ze\plugin::setting('nest_type', $instanceId);
		
		
		//Load the values of the plugin settings into the radiogroups, but only set them
		//if they are initially valid options.
		$this->checkOptionsPermutation(
			$values['details/module_class_name'], $values['details/animation_library'], $values['details/nest_type'],
			$instance['class_name'], $animation_library, $nest_type
		);
		
		//Add a note to some values if they are the current ones
		foreach (['details/module_class_name'] as $fieldName) {		//N.b. I've left this as a forloop as it used to handle multiple fields
			
			$val = $values[$fieldName];
			
			if (!empty($val) && isset($fields[$fieldName]['values'][$val]['label'])) {
				$fields[$fieldName]['values'][$val]['label'] .= ' '. ze\admin::phrase('(current selection)');
			}
		}
		
		
		//Some small QoL for the special case where someone has a nest with one slide and wants to convert
		//between a Nest and an AJAX Nest.
		//Make it so the "one slide" option on the other version of the settings is pre-selected.
		if ($values['details/module_class_name'] == 'zenario_nest'
		 && $values['details/animation_library'] == 'one_slide') {
			$values['details/nest_type'] = 'permission';
		
		} else
		if ($values['details/module_class_name'] == 'zenario_ajax_nest'
		 && $values['details/nest_type'] == 'permission') {
			$values['details/animation_library'] = 'one_slide';
		}
		
	}
	
	
	//This function takes a permutation of the module class name, animation library and nest type settings.
	//It then filters them to only return values for them if they are a meaningful.
	//E.g. the nest type is not used for accordions, and certain values of the nest type are only valid for certain modules.
	protected function checkOptionsPermutation(&$classOut, &$aLibOut, &$nestTypeOut, $classIn, $aLibIn, $nestTypeIn) {
		switch ($classIn) {
			case 'zenario_ajax_nest':
				switch ($nestTypeIn) {
					case 'buttons':
					case 'tabs':
					case 'tabs_and_buttons':
					case 'permission':
					case 'conductor':
						$nestTypeOut = $nestTypeIn;
						break;
				}
				$classOut = $classIn;
				break;
				
			case 'zenario_nest':
				switch ($aLibIn) {
					case 'cycle2':
						switch ($nestTypeIn) {
							case 'buttons':
							case 'tabs':
							case 'tabs_and_buttons':
							case 'permission':
							case 'conductor':
								$nestTypeOut = $nestTypeIn;
								break;
						}
						$aLibOut = $aLibIn;
						break;
					
					case 'accordion':
						$aLibOut = $aLibIn;
						break;
					
					case 'one_slide':
						$aLibOut = $aLibIn;
						break;
				}
				$classOut = $classIn;
				break;
		}
		
	}
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//The "Navigation between slides" field needs different indents depending on which option was chosen first
		switch ($values['details/module_class_name']) {
			case 'zenario_ajax_nest':
				$fields['details/nest_type']['indent'] = 1;
				$fields['details/buttons_desc']['indent'] =
				$fields['details/indicator_desc']['indent'] =
				$fields['details/indicator_and_buttons_desc']['indent'] =
				$fields['details/tabs_desc']['indent'] =
				$fields['details/tabs_and_buttons_desc']['indent'] =
				$fields['details/permission_desc']['indent'] =
				$fields['details/conductor_desc']['indent'] = 2;
				break;
		
			case 'zenario_nest':
				$fields['details/nest_type']['indent'] = 2;
				$fields['details/buttons_desc']['indent'] =
				$fields['details/indicator_desc']['indent'] =
				$fields['details/indicator_and_buttons_desc']['indent'] =
				$fields['details/tabs_desc']['indent'] =
				$fields['details/tabs_and_buttons_desc']['indent'] =
				$fields['details/permission_desc']['indent'] =
				$fields['details/conductor_desc']['indent'] = 3;
				break;
		}
		
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		//Check the admin has actually tried to change something, and don't allow them to open the FAB and hit save
		//without changing anything.
		
		$class1 = $aLib1 = $nestType1 = '';
		$this->checkOptionsPermutation(
			$class1, $aLib1, $nestType1,
			$fields['details/module_class_name']['value'] ?? null, $fields['details/animation_library']['value'] ?? null, $fields['details/nest_type']['value'] ?? null
		);
		
		$class2 = $aLib2 = $nestType2 = '';
		$this->checkOptionsPermutation(
			$class2, $aLib2, $nestType2,
			$values['details/module_class_name'], $values['details/animation_library'], $values['details/nest_type']
		);
		
		if ($class1 == $class2
		 && $aLib1 == $aLib2
		 && $nestType1 == $nestType2) {
			$box['tabs']['details']['errors'][] = ze\admin::phrase('Please change an option.');
		}
		
		
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//Look up what some of the current plugin settings are
		$instanceId = $box['key']['id'];
		
		
		//Run the vaues through the checkOptionsPermutation() function yet again
		$newClassName = $newAnimationLibrary = $newNestType = '';
		$this->checkOptionsPermutation(
			$newClassName, $newAnimationLibrary, $newNestType,
			$values['details/module_class_name'], $values['details/animation_library'], $values['details/nest_type']
		);
		
		//Paranoia check!
		if (empty($newClassName)) {
			exit;
		}
		
		//Switch the plugin to be handled by the new module
		$newId = ze\module::id($newClassName);
		
		$sql = "
			UPDATE IGNORE ". DB_PREFIX. "plugin_instances SET
				module_id = ". (int) $newId. "
			WHERE id = ". (int) $instanceId;
		ze\sql::update($sql);
		
		$sql = "
			UPDATE IGNORE ". DB_PREFIX. "plugin_item_link SET
				module_id = ". (int) $newId. "
			WHERE instance_id = ". (int) $instanceId;
		ze\sql::update($sql);
		
		$sql = "
			UPDATE IGNORE ". DB_PREFIX. "plugin_layout_link SET
				module_id = ". (int) $newId. "
			WHERE instance_id = ". (int) $instanceId;
		ze\sql::update($sql);
		
		$sql = "
			UPDATE IGNORE ". DB_PREFIX. "plugin_sitewide_link SET
				module_id = ". (int) $newId. "
			WHERE instance_id = ". (int) $instanceId;
		ze\sql::update($sql);
		
		//Update the value of the two plugin settings shown in this FAB.
		ze\pluginAdm::setSetting('animation_library', $newAnimationLibrary, $instanceId);
		ze\pluginAdm::setSetting('nest_type', $newNestType, $instanceId);
	
	}
}