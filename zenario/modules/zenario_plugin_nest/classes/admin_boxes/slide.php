<?php


class zenario_plugin_nest__admin_boxes__slide extends zenario_plugin_nest {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//Populate the list of core vars
		if (empty(ze::$vars)) {
			require ze::editionInclude('checkRequestVars');
		}
		$box['lovs']['coreVars'] = !empty($box['lovs']['coreVars']) ? $box['lovs']['coreVars'] : [];
		foreach (ze::$vars as $key => $val) {
			$box['lovs']['coreVars'][$key] = $key;
		}
		
		//Catch the case where we open this up from the conductor
		if ($box['key']['slideId']) {
			$box['key']['idInOrganizer'] = $box['key']['id'];
			$box['key']['id'] = $box['key']['slideId'];
		}
		
		//Catch the case where we know the slide number and instance id, but not the slide's actual id
		if (!$box['key']['id']
		 && $box['key']['slideNum']
		 && $box['key']['instanceId']) {
			$box['key']['id'] = ze\row::get('nested_plugins', 'id', [
				'instance_id' => $box['key']['instanceId'],
				'slide_num' => $box['key']['slideNum'],
				'is_slide' => 1
			]);
		}
		
		$details = [];
		if (!empty($box['key']['id'])) {
			$details = ze\row::get('nested_plugins', true, $box['key']['id']);
			
			$box['key']['instanceId'] = $details['instance_id'];
			$instance = ze\plugin::details($box['key']['instanceId']);
			
			if ($instance['content_id']) {
				if (!ze\priv::check('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version'])) {
					$box['tabs']['details']['edit_mode']['enabled'] = false;
				}
			} else {
				ze\priv::exitIfNot('_PRIV_VIEW_REUSABLE_PLUGIN');
			}
			
			$values['details/invisible_in_nav'] = $details['invisible_in_nav'];
			$values['details/show_back'] = $details['show_back'];
			$values['details/show_embed'] = $details['show_embed'];
			$values['details/show_refresh'] = $details['show_refresh'];
			$values['details/show_auto_refresh'] = $details['show_auto_refresh'];
			$values['details/auto_refresh_interval'] = $details['auto_refresh_interval'];
			$values['details/global_command'] = $details['global_command'];
			
			if (!$details['privacy'] || $details['privacy'] == 'public') {
				$values['details/privacy'] = '';
			
			} else {
				$values['details/apply_slide_specific_permissions'] = 1;
				$values['details/privacy'] = $details['privacy'];
				$values['details/smart_group_id'] = $details['smart_group_id'];
				$values['details/module_class_name'] = $details['module_class_name'];
				$values['details/method_name'] = $details['method_name'];
				$values['details/param_1'] = $details['param_1'];
				$values['details/param_2'] = $details['param_2'];
				$values['details/always_visible_to_admins'] = $details['always_visible_to_admins'];
		
				$values['details/group_ids'] =
					ze\escape::in(ze\row::getArray('group_link', 'link_to_id', ['link_to' => 'group', 'link_from' => 'slide', 'link_from_id' => $box['key']['id']]), true);
				$values['details/role_ids'] =
					ze\escape::in(ze\row::getArray('group_link', 'link_to_id', ['link_to' => 'role', 'link_from' => 'slide', 'link_from_id' => $box['key']['id']]), true);
			}
			
			//Split the request variables up by commas, and populate up to three values in the select lists
			$requestVars = ze\ray::explodeAndTrim($details['request_vars']);
			for ($i = 0; $i < 5; ++$i) {
				if (!empty($requestVars[$i])) {
					$values['details/request_vars'. $i] = $requestVars[$i];
				}
			}
			
			
			$instance['slideNum'] = $details['slide_num'];
			if (false !== strpos($instance['class_name'], 'slide')) {
				if ($instance['content_id']) {
					$box['title'] = ze\admin::phrase('Editing slide [[slideNum]] of the slideshow on [[slot_name]]', $instance);
				} else {
					$box['title'] = ze\admin::phrase('Editing slide [[slideNum]] of the slideshow "[[instance_name]]"', $instance);
				}
			} else {
				if ($instance['content_id']) {
					$box['title'] = ze\admin::phrase('Editing slide [[slideNum]] of the nest on [[slot_name]]', $instance);
				} else {
					$box['title'] = ze\admin::phrase('Editing slide [[slideNum]] of the nest "[[instance_name]]"', $instance);
				}
			}
			
			$box['identifier']['value'] = $details['slide_num'];
		
		} else {
			if (!($box['key']['instanceId'] = ($_GET['instanceId'] ?? false))
			 && !($box['key']['instanceId'] = ($_GET['refiner__nest'] ?? false))) {
				exit;
			}
			$instance = ze\plugin::details($box['key']['instanceId']);
			
			if ($instance['content_id']) {
				ze\priv::exitIfNot('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version']);
			} else {
				ze\priv::exitIfNot('_PRIV_VIEW_REUSABLE_PLUGIN');
			}
			
			$box['tabs']['details']['edit_mode']['always_on'] = true;
			$details['slide_num'] = 1 + (int) self::maxTab($box['key']['instanceId']);
			$details['name_or_title'] = ze\admin::phrase('Slide [[num]]', ['num' => $details['slide_num']]);
			
			$instance['slideNum'] = $details['slide_num'];
			if (false !== strpos($instance['class_name'], 'slide')) {
				if ($instance['content_id']) {
					$box['title'] = ze\admin::phrase('Adding slide [[slideNum]] to the slideshow on [[slot_name]]', $instance);
				} else {
					$box['title'] = ze\admin::phrase('Adding slide [[slideNum]] to the slideshow "[[instance_name]]"', $instance);
				}
			} else {
				if ($instance['content_id']) {
					$box['title'] = ze\admin::phrase('Adding slide [[slideNum]] to the nest on [[slot_name]]', $instance);
				} else {
					$box['title'] = ze\admin::phrase('Adding slide [[slideNum]] to the nest "[[instance_name]]"', $instance);
				}
			}
			
			unset($box['identifier']);
		}
		
		//If the conductor is enabled, and some states have been created for this nest,
		//display the options for the back/refresh buttons
		$box['key']['usesConductor'] = ze\pluginAdm::conductorEnabled($box['key']['instanceId']);
		
		$values['details/name_or_title'] = $details['name_or_title'];
		
		
		$fields['details/smart_group_id']['values'] = ze\contentAdm::getListOfSmartGroupsWithCounts();
		$fields['details/group_ids']['values'] = ze\datasetAdm::getGroupPickerCheckboxesForFAB();
		
		if ($ZENARIO_ORGANIZATION_MANAGER_PREFIX = ze\module::prefix('zenario_organization_manager')) {
			$fields['details/role_ids']['values'] = ze\row::getArray($ZENARIO_ORGANIZATION_MANAGER_PREFIX. 'user_location_roles', 'name', [], 'name');
		} else {
			$fields['details/role_ids']['hidden'] =
			$fields['details/privacy']['values']['with_role']['hidden'] = true;
		}
		
		
		if ($box['key']['usesConductor']) {
			$fields['details/name_or_title']['note_below'] =
				ze\admin::phrase('This title will appear on links (e.g. breadcrumb-links) to this slide.');
		
		} else {
			$fields['details/name_or_title']['note_below'] =
				ze\admin::phrase('This title will appear on links (e.g. tab-links) to this slide.');
		}
		
		
		if (!$instance['content_id'] && (ze\lang::count() > 1)) {
			$mrg = [
				'def_lang_name' => htmlspecialchars(ze\lang::name(ze::$defaultLang)),
				'phrases_panel' => htmlspecialchars(ze\link::absolute(). 'zenario/admin/organizer.php#zenario__languages/panels/phrases')
			];
			
			$fields['details/name_or_title']['show_phrase_icon'] = true;
			$fields['details/name_or_title']['note_below'] .=
				"\n".
				ze\admin::phrase('Enter text in [[def_lang_name]], this site\'s default language. <a href="[[phrases_panel]]" target="_blank">Click here to manage translations in Organizer</a>.', $mrg);
		}
	}
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($values['details/privacy']) {
			case 'call_static_method':
				if (!$values['details/module_class_name']) {
					$box['tabs']['details']['errors'][] = ze\admin::phrase('Please enter the class name of a module.');
		
				} elseif (!ze\module::inc($values['details/module_class_name'])) {
					$box['tabs']['details']['errors'][] = ze\admin::phrase('Please enter the class name of a module that you have running on this site.');
		
				} elseif ($values['details/method_name']
					&& !method_exists(
							$values['details/module_class_name'],
							$values['details/method_name'])
				) {
					$box['tabs']['details']['errors'][] = ze\admin::phrase('Please enter the name of an existing public static method.');
				}
		
				if (!$values['details/method_name']) {
					$box['tabs']['details']['errors'][] = ze\admin::phrase('Please enter the name of a public static method.');
				}
				break;
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
		
		
		if (!$box['key']['id']) {
			$box['key']['id'] = self::addSlide($box['key']['instanceId'], $values['details/name_or_title']);
		}
		
		$details = [
			'name_or_title' => $values['details/name_or_title'],
			'invisible_in_nav' => $values['details/invisible_in_nav'],
			'privacy' => 'public',
			'smart_group_id' => 0,
			'module_class_name' => '',
			'method_name' => '',
			'param_1' => '',
			'param_2' => '',
			'always_visible_to_admins' => 1,
			'show_back' => 0,
			'show_embed' => $values['details/show_embed'],
			'show_refresh' => 0,
			'show_auto_refresh' => 0,
			'auto_refresh_interval' => 60,
			'request_vars' => '',
			'global_command' => ''
		];
		
		if ($values['details/apply_slide_specific_permissions']) {
			$details['privacy'] = $values['details/privacy'];
			$details['always_visible_to_admins'] = $values['details/always_visible_to_admins'];
		}
		
		if ($box['key']['usesConductor']) {
			$details['show_back'] = $values['details/show_back'];
			if ($details['show_refresh'] = $values['details/show_refresh']) {
				if ($details['show_auto_refresh'] = $values['details/show_auto_refresh']) {
					if ($values['details/auto_refresh_interval'] > 0) {
						$details['auto_refresh_interval'] = $values['details/auto_refresh_interval'];
					}
				}
			}
			$details['global_command'] = $values['details/global_command'];
			$details['request_vars'] = '';
			
			for ($i = 0; $i < 5; ++$i) {
				if ($values['details/request_vars'. $i]) {
					$details['request_vars'] .= ($i? ',' : ''). $values['details/request_vars'. $i];
				} else {
					break;
				}
			}
		}
		
		switch ($details['privacy']) {
			case 'in_smart_group':
			case 'logged_in_not_in_smart_group':
				$details['smart_group_id'] = $values['details/smart_group_id'];
				break;
		
			case 'call_static_method':
				$details['module_class_name'] = $values['details/module_class_name'];
				$details['method_name'] = $values['details/method_name'];
				$details['param_1'] = $values['details/param_1'];
				$details['param_2'] = $values['details/param_2'];
				break;
		}
		
		ze\row::update('nested_plugins', $details, $box['key']['id']);
		
		$key = ['link_to' => 'group', 'link_from' => 'slide', 'link_from_id' => $box['key']['id']];
		if ($details['privacy'] == 'group_members') {
			ze\miscAdm::updateLinkingTable('group_link', $key, 'link_to_id', $values['details/group_ids']);
		} else {
			ze\row::delete('group_link', $key);
		}
		
		$key['link_to'] = 'role';
		if ($details['privacy'] == 'with_role') {
			ze\miscAdm::updateLinkingTable('group_link', $key, 'link_to_id', $values['details/role_ids']);
		} else {
			ze\row::delete('group_link', $key);
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