<?php


class zenario_slideshow__admin_boxes__plugin_settings extends ze\moduleBaseClass {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//Make sure the "animation duration" always has a value
		if ($values['first_tab/animation_library'] == 'cycle2') {
			$values['cycle2/speed'] = (bool) $values['cycle2/speed'] ? $values['cycle2/speed'] : 1000;
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
		
		#//If the admin presses the "remove" button on the custom breakpoint row, un-press the the toggle that's showing the row.
		#if (!empty($fields['swiper/swiper.custom_1.remove']['pressed'])) {
		#	$fields['swiper/swiper.custom_1']['pressed'] = false;
		#}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (!$values['first_tab/animation_library']) {
			$fields['first_tab/animation_library']['error'] = ze\admin::phrase('Please select an animation library');
		
		} elseif ($values['first_tab/animation_library'] == 'swiper' && $box['key']['moduleClassName'] != 'zenario_slideshow_simple') {
			$fields['first_tab/animation_library']['error'] = ze\admin::phrase('You may not use the "Swiper" animation library with the Advanced Slideshow plugin.');
		
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//If the effects tab has been edited, reload the entire page rather than just the current slot as reloading the slot
		//but then using a different effect than before can cause a few bugs
		if (isset($box['tabs']['effects']['edit_mode']['on']) && $box['tabs']['effects']['edit_mode']['on']) {
			$box['key']['slotName'] = '';
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}

}