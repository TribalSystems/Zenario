<?php


class zenario_plugin_nest__admin_boxes__slide extends zenario_plugin_nest {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//Populate the list of core vars
		if (empty(ze::$vars)) {
			require ze::editionInclude('checkRequestVars');
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
			
			if (!$details) {
				echo 'Missing ID';
				exit;
			}
			
			//Allow the slide FAB to be opened from an eggId, rather than a slideId.
			//If this happens, just look up what the correct ID should be.
			if (!$details['is_slide']) {
				if (empty($box['key']['idInOrganizer'])) {
					$box['key']['idInOrganizer'] = $box['key']['id'];
				}
				
				$details = ze\row::get('nested_plugins', true, [
					'instance_id' => $details['instance_id'],
					'slide_num' => $details['slide_num'],
					'is_slide' => 1
				]);
				
				$box['key']['id'] = $details['id'];
			
				if (!$details) {
					echo 'Could not find slide';
					exit;
				}
			}
			
			$box['key']['instanceId'] = $details['instance_id'];
			$instance = ze\plugin::details($box['key']['instanceId']);
			
			if ($instance['content_id']) {
				if (!ze\priv::check('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version'])) {
					$box['tabs']['details']['edit_mode']['enabled'] = false;
				}
			} else {
				ze\priv::exitIfNot('_PRIV_VIEW_REUSABLE_PLUGIN');
			}
			
			$values['details/show_back'] = $details['show_back'];
			$values['details/no_choice_no_going_back'] = $details['no_choice_no_going_back'];
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
				$values['details/at_location'] = $details['at_location'];
				$values['details/smart_group_id'] = $details['smart_group_id'];
				$values['details/module_class_name'] = $details['module_class_name'];
				$values['details/method_name'] = $details['method_name'];
				$values['details/param_1'] = $details['param_1'];
				$values['details/param_2'] = $details['param_2'];
				$values['details/always_visible_to_admins'] = $details['always_visible_to_admins'];
		
				$values['details/group_ids'] =
					ze\escape::in(ze\row::getValues('group_link', 'link_to_id', ['link_to' => 'group', 'link_from' => 'slide', 'link_from_id' => $box['key']['id']]), true);
				$values['details/role_ids'] =
					ze\escape::in(ze\row::getValues('group_link', 'link_to_id', ['link_to' => 'role', 'link_from' => 'slide', 'link_from_id' => $box['key']['id']]), true);
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
			if (!($box['key']['instanceId'] = ze::get('instanceId'))
			 && !($box['key']['instanceId'] = ze::get('refiner__nest'))) {
				exit;
			}
			$instance = ze\plugin::details($box['key']['instanceId']);
			
			if ($instance['content_id']) {
				ze\priv::exitIfNot('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version']);
			} else {
				ze\priv::exitIfNot('_PRIV_VIEW_REUSABLE_PLUGIN');
			}
			
			$details['slide_num'] = 1 + (int) self::maxTab($box['key']['instanceId']);
			$details['slide_label'] = ze\admin::phrase('Slide [[num]]', ['num' => $details['slide_num']]);
			
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
		$nestType = ze\plugin::setting('nest_type', $box['key']['instanceId']);
		$box['key']['usesConductor'] = $nestType == 'conductor';
		
		//If using conductor, check whether any smart breadcrumbs will be overriding the title
		if ($box['key']['usesConductor']) {
			$sql = "
				SELECT 1
				FROM ". DB_PREFIX. "nested_plugins AS ts
				INNER JOIN ". DB_PREFIX. "nested_paths AS p
				   ON p.instance_id = ts.instance_id
				  AND FIND_IN_SET(p.to_state, ts.states)
				  AND p.is_forwards = 1
				INNER JOIN ". DB_PREFIX. "nested_plugins AS b
				   ON b.instance_id = p.instance_id
				  AND b.slide_num = p.slide_num
				  AND b.is_slide = 0
				  AND b.makes_breadcrumbs != 0
				WHERE ts.id = ". (int) $box['key']['id'];
			
			if (ze\sql::fetchRow($sql)) {
				$box['key']['breadcrumbsOverridden'] = true;
			}
		}
		
		
		
		$values['details/css_class'] = $details['css_class'] ?? '';
		$values['details/slide_label'] = $details['slide_label'];
		$values['details/set_page_title_with_conductor'] = $details['set_page_title_with_conductor'] ?? 'append';
		
		
		$fields['details/smart_group_id']['values'] = ze\contentAdm::getListOfSmartGroupsWithCounts();
		$fields['details/group_ids']['values'] = ze\datasetAdm::getGroupPickerCheckboxesForFAB();
		
		if ($ZENARIO_ORGANIZATION_MANAGER_PREFIX = ze\module::prefix('zenario_organization_manager')) {
			$fields['details/role_ids']['values'] = self::getRoleTypesIndexedByIdOrderedByName();
		} else {
			$fields['details/role_ids']['hidden'] =
			$fields['details/privacy']['values']['with_role']['hidden'] = true;
		}
		
		
		if ($box['key']['usesConductor']) {
			if ($box['key']['breadcrumbsOverridden']) {
				$fields['details/slide_label']['label'] = ze\admin::phrase('Slide label (for internal use only):');
				$fields['details/slide_label_notices']['notices_below']['appearance'] = [
					'type' => 'warning',
					'message' => ze\admin::phrase('This label will not appear on breadcrumb-links, as it is overridden by a plugin generating smart breadcrumbs on the slide above this one in the navigation.')
				];
			} else {
				$fields['details/slide_label']['label'] = ze\admin::phrase('Slide label:');
				$fields['details/slide_label_notices']['notices_below']['appearance'] = [
					'type' => 'information',
					'message' => ze\admin::phrase('This label will appear on links (e.g. breadcrumb-links) to this slide.')
				];
			}
		
		} else {
			switch ($nestType) {
				case 'tabs':
				case 'tabs_and_buttons':
					$fields['details/slide_label']['label'] = ze\admin::phrase('Slide label:');
					$fields['details/slide_label_notices']['notices_below']['appearance'] = [
						'type' => 'information',
						'message' => ze\admin::phrase('This label will appear on links (e.g. tab-links) to this slide.')
					];
					break;
				
				default:
					if (false !== strpos($instance['class_name'], 'slide')) {
						$fields['details/slide_label']['label'] = ze\admin::phrase('Slide label (for internal use only):');
						$fields['details/slide_label_notices']['notices_below']['appearance'] = [
							'type' => 'warning',
							'message' => ze\admin::phrase('Label text will not be viewable by visitors for this type of slideshow. (The type of slideshow can be changed in the slideshow settings.)')
						];
					} else {
						$fields['details/slide_label']['label'] = ze\admin::phrase('Slide label (for internal use only):');
						$fields['details/slide_label_notices']['notices_below']['appearance'] = [
							'type' => 'warning',
							'message' => ze\admin::phrase('Label text will not be viewable by visitors for this type of nest. (The type of nest can be changed in the nest settings.)')
						];
					}
			}
		}
		
		
		if (!$instance['content_id'] && !$box['key']['breadcrumbsOverridden'] && (ze\lang::count() > 1)) {
			$mrg = [
				'def_lang_name' => htmlspecialchars(ze\lang::name(ze::$defaultLang)),
				'phrases_panel' => htmlspecialchars(ze\link::absolute(). 'organizer.php#zenario__languages/panels/phrases')
			];
			
			$fields['details/slide_label']['show_phrase_icon'] = true;
			$fields['details/slide_label']['note_below'] =
				"\n".
				ze\admin::phrase('Enter text in [[def_lang_name]], this site\'s default language. <a href="[[phrases_panel]]" target="_blank">Click here to manage translations in Organizer</a>.', $mrg);
		}
		
		if ($box['key']['usesConductor'] && !$box['key']['breadcrumbsOverridden']) {
			$fields['details/slide_label']['onchange'] = "
				zenario.ajax(zenario_plugin_nest.AJAXLink({formatTitleTextAdmin: this.value, htmlescape: true})).after(function(html) {
					$('#zenario__title_merge_fields__note').html(html);
				});
			";
		}
		
		if ($slideNum = $instance['slideNum'] ?? $box['key']['slideNum'] ?? false) {
			$fields['details/css_class']['pre_field_html'] =
				'<span class="zenario_css_class_label">'.
					'nest_plugins slide_'. $slideNum.
				'</span> ';
		}
	}
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($box['key']['usesConductor'] && !$box['key']['breadcrumbsOverridden']) {
			$fields['details/slide_label_notices']['pre_field_html'] =
				'<span id="zenario__title_merge_fields__note">'.
					zenario_plugin_nest::formatTitleTextAdmin($values['details/slide_label'], true).
				'</span><br/>';
		}
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
			$box['key']['id'] = self::addSlide($box['key']['instanceId'], $values['details/slide_label']);
		}
		
		$details = [
			'css_class' => $values['details/css_class'],
			'slide_label' => $values['details/slide_label'],
			'set_page_title_with_conductor' => $values['details/set_page_title_with_conductor'],
			'privacy' => 'public',
			'at_location' => 'any',
			'smart_group_id' => 0,
			'module_class_name' => '',
			'method_name' => '',
			'param_1' => '',
			'param_2' => '',
			'always_visible_to_admins' => 1,
			'show_back' => 0,
			'no_choice_no_going_back' => 0,
			'show_embed' => $values['details/show_embed'],
			'show_refresh' => 0,
			'show_auto_refresh' => 0,
			'auto_refresh_interval' => 60,
			'global_command' => ''
		];
		
		if ($values['details/apply_slide_specific_permissions']) {
			$details['privacy'] = $values['details/privacy'];
			$details['always_visible_to_admins'] = $values['details/always_visible_to_admins'];
		}
		
		if ($box['key']['usesConductor']) {
			$details['global_command'] = $values['details/global_command'];
			
			if ($details['show_back'] = $values['details/show_back']) {
				$details['no_choice_no_going_back'] = $values['details/no_choice_no_going_back'];
			}
			
			if ($details['show_refresh'] = $values['details/show_refresh']) {
				if ($details['show_auto_refresh'] = $values['details/show_auto_refresh']) {
					if ($values['details/auto_refresh_interval'] > 0) {
						$details['auto_refresh_interval'] = $values['details/auto_refresh_interval'];
					}
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
			$details['at_location'] = $values['details/at_location'];
			ze\miscAdm::updateLinkingTable('group_link', $key, 'link_to_id', $values['details/role_ids']);
		} else {
			ze\row::delete('group_link', $key);
		}
		
		ze\pluginAdm::calcConductorHierarchy($box['key']['instanceId']);
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
