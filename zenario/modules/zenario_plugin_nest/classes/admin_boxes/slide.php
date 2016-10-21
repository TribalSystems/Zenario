<?php


class zenario_plugin_nest__admin_boxes__slide extends zenario_plugin_nest {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (!get('refiner__nest')) {
			exit;
		}
		
		//Catch the case where we open this up with a prefix on the id
		if ($actualId = chopPrefixOffOfString($box['key']['id'], 'slide_')) {
			$box['key']['idInOrganizer'] = $box['key']['id'];
			$box['key']['id'] = $actualId;
		}
		
		
		$details = array();
		if (!empty($box['key']['id'])) {
			$details = getNestDetails($box['key']['id'], false, true);
			
			$box['key']['instanceId'] = $details['instance_id'];
			$instance = getPluginInstanceDetails($box['key']['instanceId']);
			
			if ($instance['content_id']) {
				if (!checkPriv('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version'])) {
					$box['tabs']['tab']['edit_mode']['enabled'] = false;
				}
			} else {
				exitIfNotCheckPriv('_PRIV_VIEW_REUSABLE_PLUGIN');
			}
			
			$box['tabs']['tab']['fields']['invisible_in_nav']['value'] = $details['invisible_in_nav'];
			$box['tabs']['tab']['fields']['tab_visibility']['value'] = $details['visibility'];
			$box['tabs']['tab']['fields']['tab__smart_group']['value'] = $details['smart_group_id'];
			$box['tabs']['tab']['fields']['tab__module_class_name']['value'] = $details['module_class_name'];
			$box['tabs']['tab']['fields']['tab__method_name']['value'] = $details['method_name'];
			$box['tabs']['tab']['fields']['tab__param_1']['value'] = $details['param_1'];
			$box['tabs']['tab']['fields']['tab__param_2']['value'] = $details['param_2'];
			
			$instance['stndrdth'] = stndrdth($details['tab']);
			if (false !== strpos($instance['class_name'], 'slide')) {
				if ($instance['content_id']) {
					$box['title'] = adminPhrase('Editing the [[stndrdth]] slide of the slideshow on [[slot_name]]', $instance);
				} else {
					$box['title'] = adminPhrase('Editing the [[stndrdth]] slide of the slideshow "[[instance_name]]"', $instance);
				}
			} else {
				if ($instance['content_id']) {
					$box['title'] = adminPhrase('Editing the [[stndrdth]] slide of the nest on [[slot_name]]', $instance);
				} else {
					$box['title'] = adminPhrase('Editing the [[stndrdth]] slide of the nest "[[instance_name]]"', $instance);
				}
			}
		
		} else {
			$box['key']['instanceId'] = get('refiner__nest');
			$instance = getPluginInstanceDetails($box['key']['instanceId']);
			
			if ($instance['content_id']) {
				exitIfNotCheckPriv('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version']);
			} else {
				exitIfNotCheckPriv('_PRIV_VIEW_REUSABLE_PLUGIN');
			}
			
			$box['tabs']['tab']['edit_mode']['always_on'] = true;
			$details['tab'] = 1 + (int) self::maxTab($box['key']['instanceId']);
			$details['name_or_title'] = adminPhrase('Slide [[num]]', array('num' => $details['tab']));
			
			if ($details['tab'] == 1) {
				$instance['stndrdth'] = adminPhrase('new');
			} else {
				$instance['stndrdth'] = stndrdth($details['tab']);
			}
			
			if (false !== strpos($instance['class_name'], 'slide')) {
				if ($instance['content_id']) {
					$box['title'] = adminPhrase('Adding a [[stndrdth]] slide to the slideshow on [[slot_name]]', $instance);
				} else {
					$box['title'] = adminPhrase('Adding a [[stndrdth]] slide to the slideshow "[[instance_name]]"', $instance);
				}
			} else {
				if ($instance['content_id']) {
					$box['title'] = adminPhrase('Adding a [[stndrdth]] slide to the nest on [[slot_name]]', $instance);
				} else {
					$box['title'] = adminPhrase('Adding a [[stndrdth]] slide to the nest "[[instance_name]]"', $instance);
				}
			}
		}
		
		$box['identifier']['value'] = $details['tab'];
		$box['tabs']['tab']['fields']['name_or_title']['value'] = $details['name_or_title'];
	}
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		
		$fields['tab/tab__smart_group']['hidden'] = true;

		if (in($values['tab/tab_visibility'], 'in_smart_group', 'logged_in_not_in_smart_group')) {
			$fields['tab/tab__smart_group']['hidden'] = false;
	
			if (!isset($fields['tab/tab__smart_group']['values'])) {
				$fields['tab/tab__smart_group']['values'] = getListOfSmartGroupsWithCounts();
			}
		}

		$fields['tab/tab__module_class_name']['hidden'] =
		$fields['tab/tab__method_name']['hidden'] =
		$fields['tab/tab__param_1']['hidden'] =
		$fields['tab/tab__param_2']['hidden'] =
			$values['tab/tab_visibility'] != 'call_static_method';
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		
		
		switch ($values['tab/tab_visibility']) {
			case 'in_smart_group':
			case 'logged_in_not_in_smart_group':
				if (!$values['tab/tab__smart_group']) {
					$box['tabs']['tab']['errors'][] = adminPhrase('Please select a smart group.');
				}
				break;
		
			case 'call_static_method':
				if (!$values['tab/tab__module_class_name']) {
					$box['tabs']['tab']['errors'][] = adminPhrase('Please enter the Class Name of a Plugin.');
		
				} elseif (!inc($values['tab/tab__module_class_name'])) {
					$box['tabs']['tab']['errors'][] = adminPhrase('Please enter the Class Name of a Plugin that you have running on this site.');
		
				} elseif ($values['tab/tab__method_name']
					&& !method_exists(
							$values['tab/tab__module_class_name'],
							$values['tab/tab__method_name'])
				) {
					$box['tabs']['tab']['errors'][] = adminPhrase('Please enter the name of an existing Static Method.');
				}
		
				if (!$values['tab/tab__method_name']) {
					$box['tabs']['tab']['errors'][] = adminPhrase('Please enter the name of a Static Method.');
				}
				break;
		}
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
		
		
		if (!$box['key']['id']) {
			$box['key']['id'] = self::addTab($box['key']['instanceId'], $values['tab/name_or_title']);
		}
		
		$details = array(
			'name_or_title' => $values['tab/name_or_title'],
			'invisible_in_nav' => $values['tab/invisible_in_nav'],
			'visibility' => $values['tab/tab_visibility'],
			'smart_group_id' => 0,
			'module_class_name' => '',
			'method_name' => '',
			'param_1' => '',
			'param_2' => ''
		);
		
		switch ($values['tab/tab_visibility']) {
			case 'in_smart_group':
			case 'logged_in_not_in_smart_group':
				$details['smart_group_id'] = $values['tab/tab__smart_group'];
				break;
		
			case 'call_static_method':
				$details['module_class_name'] = $values['tab/tab__module_class_name'];
				$details['method_name'] = $values['tab/tab__method_name'];
				$details['param_1'] = $values['tab/tab__param_1'];
				$details['param_2'] = $values['tab/tab__param_2'];
				break;
		}
		
		updateRow('nested_plugins', $details, $box['key']['id']);
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