<?php


//If updating from an earlier version, migrate and then drop the data in the slides table by
//converting it to nests with bannets in.
if (ze\dbAdm::needRevision(52)) {
	
	if (($s2prefix = ze\module::prefix('zenario_slideshow_2', $mustBeRunning = false))
	 && (ze\sql::numRows("SHOW TABLES LIKE '". DB_PREFIX. $s2prefix. "slides'"))) {
	
		ze\module::inc('zenario_plugin_nest');

		$instances = ze\module::getModuleInstancesAndPluginSettings('zenario_slideshow_simple');
		foreach ($instances as $instance) {
		
			if (ze\row::exists('plugin_settings', ['name' => 'advanced_behaviour', 'instance_id' => $instance['instance_id']])) {
				continue;
			}
		
			//Update new slideshow settings
			$hasRolloverImages = ze\row::exists($s2prefix . 'slides', ['instance_id' => $instance['instance_id'], 'rollover_image_id' => ['!' => null]]);
			$hasMobileImages = ze\row::exists($s2prefix . 'slides', ['instance_id' => $instance['instance_id'], 'mobile_image_id' => ['!' => null]]);
			$advanced = '';
			if ($hasRolloverImages) {
				$advanced = 'use_rollover';
			} elseif ($hasMobileImages) {
				$advanced = 'mobile_change_image';
			}
			ze\row::set('plugin_settings', 
				['value' => $advanced], 
				['name' => 'advanced_behaviour', 'instance_id' => $instance['instance_id']]);
	
			if (!empty($instance['settings']['heading'])) {
				ze\row::set('plugin_settings', 
					['value' => true], 
					['name' => 'show_heading', 'instance_id' => $instance['instance_id']]);
				ze\row::set('plugin_settings', 
					['value' => $instance['settings']['heading']], 
					['name' => 'heading_text', 'instance_id' => $instance['instance_id']]);
				ze\row::set('plugin_settings', 
					['value' => 'h2'], 
					['name' => 'heading_tag', 'instance_id' => $instance['instance_id']]);
			}
	
			ze\row::set('plugin_settings', 
				['value' => $instance['settings']['slide_duration'] ?? 4000], 
				['name' => 'timeout', 'instance_id' => $instance['instance_id']]);
			ze\row::set('plugin_settings', 
				['value' => (($instance['settings']['navigation_style'] ?? false) == 'thumbnail_navigator')], 
				['name' => 'show_tabs', 'instance_id' => $instance['instance_id']]);
			ze\row::set('plugin_settings', 
				['value' => $instance['settings']['arrow_buttons'] ?? 1], 
				['name' => 'show_next_prev_buttons', 'instance_id' => $instance['instance_id']]);
			ze\row::set('plugin_settings', 
				['value' => $instance['settings']['auto_play'] ?? 1], 
				['name' => 'use_timeout', 'instance_id' => $instance['instance_id']]);
			ze\row::set('plugin_settings', 
				['value' => $instance['settings']['hover_to_pause'] ?? 0], 
				['name' => 'pause', 'instance_id' => $instance['instance_id']]);
			ze\row::set('plugin_settings', 
				['value' => 'fade'], 
				['name' => 'fx', 'instance_id' => $instance['instance_id']]);
		
			$sql = '
				SELECT *
				FROM ' . DB_PREFIX . $s2prefix . 'slides
				WHERE instance_id = ' . (int)$instance['instance_id'] . '
				ORDER BY ordinal';
			$result = ze\sql::select($sql);
			while ($slide = ze\sql::fetchAssoc($result)) {
		
				//Create slide / banner
				$nestedSlideId = zenario_plugin_nest::addSlide($slide['instance_id']);
				$nestedBannerId = zenario_plugin_nest::addBanner($slide['image_id'], $slide['instance_id'], $nestedSlideId, $inputIsSlideId = true);
		
		
				//Update slide details
				$slideDetails = [
					'privacy' => 'public',
					'always_visible_to_admins' => 1,
					'smart_group_id' => 0,
					'module_class_name' => '',
					'method_name' => '',
					'param_1' => '',
					'param_2' => ''
				];
	
				if ($slide['slide_visibility'] == 'call_static_method') {
					$slideDetails['privacy'] = 'call_static_method';
					$slideDetails['module_class_name'] = $slide['plugin_class'];
					$slideDetails['method_name'] = $slide['method_name'];
					$slideDetails['param_1'] = $slide['param_1'];
					$slideDetails['param_2'] = $slide['param_2'];
				} elseif ($slide['slide_visibility'] == 'logged_in_with_field' && $slide['field_id']) {
					$datasetFieldType = ze\row::get('custom_dataset_fields', 'type', $slide['field_id']);
					if ($datasetFieldType == 'group') {
						$slideDetails['privacy'] = 'group_members';
						$key = ['link_to' => 'group', 'link_from' => 'slide', 'link_from_id' => $nestedSlideId];
						if ($slideDetails['privacy'] == 'group_members') {
							ze\miscAdm::updateLinkingTable('group_link', $key, 'link_to_id', [$slide['field_id']]);
						}
					}
				}
				if ($slide['hidden']) {
					$slideDetails['privacy'] = 'hidden';
				}
		
				if (($instance['settings']['navigation_style'] ?? false) == 'thumbnail_navigator') {
					$slideDetails['name_or_title'] = $slide['tab_name'] ?? '';
				}
		
				ze\row::update('nested_plugins', $slideDetails, $nestedSlideId);
		
		
				//Update banner details
				ze\row::set('plugin_settings', 
					['value' => $slide['image_id']], 
					['name' => 'image', 'instance_id' => $slide['instance_id'], 'egg_id' => $nestedBannerId]);
				ze\row::set('plugin_settings', 
					['value' => $slide['overwrite_alt_tag']], 
					['name' => 'alt_tag', 'instance_id' => $slide['instance_id'], 'egg_id' => $nestedBannerId]);
				ze\row::set('plugin_settings', 
					['value' => $slide['slide_title']], 
					['name' => 'title', 'instance_id' => $slide['instance_id'], 'egg_id' => $nestedBannerId]);
				ze\row::set('plugin_settings', 
					['value' => $slide['slide_extra_html']], 
					['name' => 'text', 'instance_id' => $slide['instance_id'], 'egg_id' => $nestedBannerId]);
		
				$linkType = '';
				if ($slide['target_loc'] == 'external') {
					$linkType = '_EXTERNAL_URL';
					ze\row::set('plugin_settings', 
						['value' => $slide['dest_url']], 
						['name' => 'url', 'instance_id' => $slide['instance_id'], 'egg_id' => $nestedBannerId]);
				
				} elseif ($slide['target_loc'] == 'internal') {
					$linkType = '_CONTENT_ITEM';
					ze\row::set('plugin_settings', 
						['value' => $slide['dest_url']], 
						['name' => 'hyperlink_target', 'instance_id' => $slide['instance_id'], 'egg_id' => $nestedBannerId]);
				}
				ze\row::set('plugin_settings', 
					['value' => $linkType], 
					['name' => 'link_type', 'instance_id' => $slide['instance_id'], 'egg_id' => $nestedBannerId]);
		
				if ($slide['target_loc'] == 'external' || $slide['target_loc'] == 'internal') {
					ze\row::set('plugin_settings', 
						['value' => $slide['slide_more_link_text']], 
						['name' => 'more_link_text', 'instance_id' => $slide['instance_id'], 'egg_id' => $nestedBannerId]);
					ze\row::set('plugin_settings', 
						['value' => $slide['open_in_new_window']], 
						['name' => 'target_blank', 'instance_id' => $slide['instance_id'], 'egg_id' => $nestedBannerId]);
				}
		
				$advanced = '';
				if ($hasRolloverImages) {
					$advanced = 'use_rollover';
					ze\row::set('plugin_settings', 
						['value' => $slide['rollover_image_id']], 
						['name' => 'rollover_image', 'instance_id' => $slide['instance_id'], 'egg_id' => $nestedBannerId]);
				} elseif ($hasMobileImages) {
					$advanced = 'mobile_change_image';
					ze\row::set('plugin_settings', 
						['value' => $slide['mobile_image_id']], 
						['name' => 'rollover_image', 'instance_id' => $slide['instance_id'], 'egg_id' => $nestedBannerId]);
				}
		
				ze\row::set('plugin_settings', 
					['value' => $advanced], 
					['name' => 'advanced_behaviour', 'instance_id' => $slide['instance_id'], 'egg_id' => $nestedBannerId]);
			}
		}
		
		ze\sql::cacheFriendlyUpdate("DROP TABLE `". DB_PREFIX. $s2prefix. "slides`");
	}
}
