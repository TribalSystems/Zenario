<?php


class zenario_abstract_nest__admin_boxes__plugin_settings extends ze\moduleBaseClass {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//Hide the breadcrumbs option if the module isn't running
		if (!ze\module::inc('zenario_breadcrumbs')) {
			$box['tabs']['breadcrumbs']['hidden'] = true;
		}
		
		//Find out the largest number of columns used on a layout, or just guess at 12 if there are no layouts yet
		$maxCols = (int) ze\row::max('layouts', 'cols') ?: 12;
		for ($i = 2; $i < $maxCols; ++$i) {
			$label = ze\admin::phrase('[[cols]] cols', ['cols' => $i]);
		
			$box['lovs']['grid_cols'][$i] = [
				'ord' => $i,
				'label' => $label
			];
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		if (isset($box['tabs']['size']['fields']['max_height'])) {
			$box['tabs']['size']['fields']['max_height']['hidden'] = 
				!$values['size/set_max_height'];
		}
		
		//Lazy load and rollover only work if Mobile Behaviour is set to "Same image".
		if ($values['size/mobile_behaviour'] != 'mobile_same_image') {
			$fields['size/advanced_behaviour']['values']['lazy_load']['disabled'] = true;
			$fields['size/advanced_behaviour']['side_note'] = ze\admin::phrase('The lazy load option is only available when using the "Same image" option for mobile browsers.');
		} else {
			$fields['size/advanced_behaviour']['values']['lazy_load']['disabled'] = false;
			unset($fields['size/advanced_behaviour']['side_note']);
		}
		
		if ($values['size/advanced_behaviour'] == 'lazy_load') {
			$fields['size/mobile_behaviour']['values']['mobile_same_image_different_size']['disabled'] = 
			$fields['size/mobile_behaviour']['values']['mobile_hide_image']['disabled'] = true;
			$fields['size/mobile_behaviour']['side_note'] = ze\admin::phrase('When lazy loading images, only the "Same image" option for mobile browsers is supported.');
		
		} else {
			$fields['size/mobile_behaviour']['values']['mobile_same_image_different_size']['disabled'] = 
			$fields['size/mobile_behaviour']['values']['mobile_hide_image']['disabled'] = false;
			unset($fields['size/mobile_behaviour']['side_note']);
		}
		
		if (isset($box['tabs']['size']['fields']['banner_canvas'])) {
			$this->showHideImageOptions($fields, $values, 'size', $hidden = false, $fieldPrefix = 'banner_', $hasCanvas = true);
			
			if (isset($box['tabs']['size']['fields']['enlarge_canvas'])) {
				$this->showHideImageOptions($fields, $values, 'size', $hidden = $values['size/link_type'] != '_ENLARGE_IMAGE', $fieldPrefix = 'enlarge_', $hasCanvas = true);
			}
			
			if (isset($box['tabs']['size']['fields']['mobile_canvas'])) {
				$this->showHideImageOptions($fields, $values, 'size', $hidden = $values['size/mobile_behaviour'] != 'mobile_same_image_different_size', $fieldPrefix = 'mobile_', $hasCanvas = true);
			}
		}
		
		
		//Check whether this type of nest should show the option to have images on the tab links to slides.
		if (isset($box['tabs']['first_tab']['fields']['show_images_on_slide_links'])) {
			
			//For nests, this option should be visible if the tab links are visible.
			//For accordion nests, the tab links are always visble so this option should always be visible
			//For conductors, this option should be visible if the "Show tabs with labels" is enabled.
			$imageSettingIndents = [1, 2, 3];
			$showImageSelector = false;
			
			$animationLibControlsAreVisible = isset($fields['first_tab/animation_library']);
			
			if ($animationLibControlsAreVisible && $values['first_tab/animation_library'] == 'accordion') {
				$showImageSelector = true;
			
			} elseif ($animationLibControlsAreVisible && $values['first_tab/animation_library'] == 'one_slide') {
				$showImageSelector = false;
			
			} else {
				switch ($values['first_tab/nest_type']) {
					case 'tabs':
					case 'tabs_and_buttons':
						$showImageSelector = true;
						
						if ($animationLibControlsAreVisible) {
							$imageSettingIndents = [2, 3, 4];
						}
						break;
					
					case 'conductor':
						$showImageSelector = (bool) $values['first_tab/show_global_tabs'];
						$imageSettingIndents = [2, 3, 4];
				}
			}
			
			$fields['first_tab/show_images_on_slide_links']['hidden'] = !$showImageSelector;
			
			//Depending on whether this is a nest or a conductor, the options for images will need to be set at different levels
			$fields['first_tab/show_images_on_slide_links']['indent'] = $imageSettingIndents[0];
			$fields['first_tab/image_canvas']['indent'] = $imageSettingIndents[1];
			$fields['first_tab/image_retina']['indent'] = 
			$fields['first_tab/image_width']['indent'] = 
			$fields['first_tab/image_height']['indent'] = $imageSettingIndents[2];
			
			$hidden = !$showImageSelector || !$values['first_tab/show_images_on_slide_links'];
			$this->showHideImageOptions($fields, $values, 'first_tab', $hidden, 'image_');
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//...your PHP code...//
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
}