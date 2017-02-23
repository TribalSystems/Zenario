<?php


class zenario_plugin_nest__admin_boxes__slide extends zenario_plugin_nest {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (!get('refiner__nest')) {
			exit;
		}
		
		//Populate the list of core vars
		if (empty(cms_core::$vars)) {
			require editionInclude('checkRequestVars');
		}
		$box['lovs']['coreVars'] = array();
		foreach (cms_core::$vars as $key => $val) {
			$box['lovs']['coreVars'][$key] = $key;
		}
		
		//Catch the case where we open this up with a prefix on the id
		if ($actualId = chopPrefixOffString('slide_', $box['key']['id'])) {
			$box['key']['idInOrganizer'] = $box['key']['id'];
			$box['key']['id'] = $actualId;
		}
		
		
		$details = array();
		if (!empty($box['key']['id'])) {
			$details = getRow('nested_plugins', true, $box['key']['id']);
			
			$box['key']['instanceId'] = $details['instance_id'];
			$instance = getPluginInstanceDetails($box['key']['instanceId']);
			
			if ($instance['content_id']) {
				if (!checkPriv('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version'])) {
					$box['tabs']['tab']['edit_mode']['enabled'] = false;
				}
			} else {
				exitIfNotCheckPriv('_PRIV_VIEW_REUSABLE_PLUGIN');
			}
			
			$values['tab/invisible_in_nav'] = $details['invisible_in_nav'];
			$values['tab/privacy'] = $details['privacy'];
			$values['tab/smart_group_id'] = $details['smart_group_id'];
			$values['tab/module_class_name'] = $details['module_class_name'];
			$values['tab/method_name'] = $details['method_name'];
			$values['tab/param_1'] = $details['param_1'];
			$values['tab/param_2'] = $details['param_2'];
			$values['tab/always_visible_to_admins'] = $details['always_visible_to_admins'];
			$values['tab/show_back'] = $details['show_back'];
			$values['tab/show_refresh'] = $details['show_refresh'];
			$values['tab/show_auto_refresh'] = $details['show_auto_refresh'];
			$values['tab/auto_refresh_interval'] = $details['auto_refresh_interval'];
			$values['tab/global_command'] = $details['global_command'];
			$values['tab/group_ids'] =
				inEscape(getRowsArray('group_slide_link', 'group_id', array('slide_id' => $box['key']['id'])), true);
			
			//Split the request variables up by commas, and populate up to three values in the select lists
			$requestVars = explodeAndTrim($details['request_vars']);
			for ($i = 0; $i < 5; ++$i) {
				if (!empty($requestVars[$i])) {
					$values['tab/request_vars'. $i] = $requestVars[$i];
				}
			}
			
			
			$instance['slideNum'] = $details['tab'];
			if (false !== strpos($instance['class_name'], 'slide')) {
				if ($instance['content_id']) {
					$box['title'] = adminPhrase('Editing slide [[slideNum]] of the slideshow on [[slot_name]]', $instance);
				} else {
					$box['title'] = adminPhrase('Editing slide [[slideNum]] of the slideshow "[[instance_name]]"', $instance);
				}
			} else {
				if ($instance['content_id']) {
					$box['title'] = adminPhrase('Editing slide [[slideNum]] of the nest on [[slot_name]]', $instance);
				} else {
					$box['title'] = adminPhrase('Editing slide [[slideNum]] of the nest "[[instance_name]]"', $instance);
				}
			}
			
			$box['identifier']['value'] = $details['tab'];
		
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
			
			$instance['slideNum'] = $details['tab'];
			if (false !== strpos($instance['class_name'], 'slide')) {
				if ($instance['content_id']) {
					$box['title'] = adminPhrase('Adding slide [[slideNum]] to the slideshow on [[slot_name]]', $instance);
				} else {
					$box['title'] = adminPhrase('Adding slide [[slideNum]] to the slideshow "[[instance_name]]"', $instance);
				}
			} else {
				if ($instance['content_id']) {
					$box['title'] = adminPhrase('Adding slide [[slideNum]] to the nest on [[slot_name]]', $instance);
				} else {
					$box['title'] = adminPhrase('Adding slide [[slideNum]] to the nest "[[instance_name]]"', $instance);
				}
			}
			
			unset($box['identifier']);
		}
		
		//If the conductor is enabled, and some states have been created for this nest,
		//display the options for the back/refresh buttons
		$box['key']['usesConductor'] = conductorEnabled($box['key']['instanceId']);
		
		$values['tab/name_or_title'] = $details['name_or_title'];
		
		
		$fields['tab/smart_group_id']['values'] = getListOfSmartGroupsWithCounts();
		$fields['tab/group_ids']['values'] = getGroupPickerCheckboxesForFAB();
		
		
		if ($box['key']['usesConductor']) {
			$fields['tab/name_or_title']['note_below'] =
				adminPhrase('This title will appear on links (e.g. breadcrumb-links) to this slide.');
		
		} else {
			$fields['tab/name_or_title']['note_below'] =
				adminPhrase('This title will appear on links (e.g. tab-links) to this slide.');
		}
		
		
		if (getNumLanguages() > 1) {
			$mrg = array(
				'def_lang_name' => htmlspecialchars(getLanguageName(setting('default_language'))),
				'phrases_panel' => htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__languages/panels/phrases')
			);
			
			$fields['tab/name_or_title']['show_phrase_icon'] = true;
			$fields['tab/name_or_title']['note_below'] .=
				"\n".
				adminPhrase('Enter text in [[def_lang_name]], this site\'s default language. <a href="[[phrases_panel]]" target="_blank">Click here to manage translations in Organizer</a>.', $mrg);
		}
	}
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($values['tab/privacy']) {
			case 'call_static_method':
				if (!$values['tab/module_class_name']) {
					$box['tabs']['tab']['errors'][] = adminPhrase('Please enter the class name of a module.');
		
				} elseif (!inc($values['tab/module_class_name'])) {
					$box['tabs']['tab']['errors'][] = adminPhrase('Please enter the class name of a module that you have running on this site.');
		
				} elseif ($values['tab/method_name']
					&& !method_exists(
							$values['tab/module_class_name'],
							$values['tab/method_name'])
				) {
					$box['tabs']['tab']['errors'][] = adminPhrase('Please enter the name of an existing public static method.');
				}
		
				if (!$values['tab/method_name']) {
					$box['tabs']['tab']['errors'][] = adminPhrase('Please enter the name of a public static method.');
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
			'privacy' => $values['tab/privacy'],
			'smart_group_id' => 0,
			'module_class_name' => '',
			'method_name' => '',
			'param_1' => '',
			'param_2' => '',
			'always_visible_to_admins' => $values['tab/always_visible_to_admins'],
			'show_back' => 0,
			'show_refresh' => 0,
			'show_auto_refresh' => 0,
			'auto_refresh_interval' => 60,
			'request_vars' => '',
			'global_command' => ''
		);
		
		if ($box['key']['usesConductor']) {
			$details['show_back'] = $values['tab/show_back'];
			if ($details['show_refresh'] = $values['tab/show_refresh']) {
				if ($details['show_auto_refresh'] = $values['tab/show_auto_refresh']) {
					if ($values['tab/auto_refresh_interval'] > 0) {
						$details['auto_refresh_interval'] = $values['tab/auto_refresh_interval'];
					}
				}
			}
			$details['global_command'] = $values['tab/global_command'];
			$details['request_vars'] = '';
			
			for ($i = 0; $i < 5; ++$i) {
				if ($values['tab/request_vars'. $i]) {
					$details['request_vars'] .= ($i? ',' : ''). $values['tab/request_vars'. $i];
				} else {
					break;
				}
			}
		}
		
		switch ($values['tab/privacy']) {
			case 'public':
				$details['always_visible_to_admins'] = 1;
				break;
		
			case 'in_smart_group':
			case 'logged_in_not_in_smart_group':
				$details['smart_group_id'] = $values['tab/smart_group_id'];
				break;
		
			case 'call_static_method':
				$details['module_class_name'] = $values['tab/module_class_name'];
				$details['method_name'] = $values['tab/method_name'];
				$details['param_1'] = $values['tab/param_1'];
				$details['param_2'] = $values['tab/param_2'];
				break;
		}
		
		updateRow('nested_plugins', $details, $box['key']['id']);
		
		$key = array('instance_id' => $box['key']['instanceId'], 'slide_id' => $box['key']['id']);
		if ($values['tab/privacy'] == 'group_members') {
			updateLinkingTable('group_slide_link', $key, 'group_id', $values['tab/group_ids']);
		} else {
			deleteRow('group_slide_link', $key);
		}
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