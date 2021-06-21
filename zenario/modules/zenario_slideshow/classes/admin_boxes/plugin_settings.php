<?php


class zenario_slideshow__admin_boxes__plugin_settings extends ze\moduleBaseClass {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//Make sure "effect" is not set to false (applies to both Cycle2 and Cycle1).
		//Fixes the bug: "[cycle] unknown transition:  ; slideshow terminating"
		if (($values['first_tab/animation_library'] == 'cycle' || $values['first_tab/animation_library'] == 'cycle2') && !$values['cycle_effects/fx']) {
			$values['cycle_effects/fx'] = 'fade';
		}
		
		//Cycle2 doesn't support as many effects as Cycle1.
		//Make sure an unsupported effect isn't selected.
		if ($values['first_tab/animation_library'] == 'cycle2'
			&& ($values['cycle_effects/fx'] != 'none' && $values['cycle_effects/fx'] != 'fade' && $values['cycle_effects/fx'] != 'fadeout' && $values['cycle_effects/fx'] != 'scrollHorz')) {
			$values['cycle_effects/fx'] = 'fade';
		}
		
		//Hide all of the animation library-dependant tabs when not selected
		foreach ($fields['first_tab/animation_library']['values'] as $key => $label) {
			if ($key && isset($box['tabs'][$key. '_effects'])) {
				$box['tabs'][$key. '_effects']['hidden'] = $values['first_tab/animation_library'] != $key;
			}
		}
		
		//Make sure the "animation duration" always has a value
		if ($values['first_tab/animation_library'] == 'cycle') {
			$values['cycle_effects/speed'] = (bool) $values['cycle_effects/speed'] ? $values['cycle_effects/speed'] : 1000;
		} elseif ($values['first_tab/animation_library'] == 'cycle2') {
			$values['cycle2_effects/speed'] = (bool) $values['cycle2_effects/speed'] ? $values['cycle2_effects/speed'] : 1000;
		} elseif ($values['first_tab/animation_library'] == 'roundabout') {
			$values['roundabout_effects/speed'] = (bool) $values['roundabout_effects/speed'] ? $values['roundabout_effects/speed'] : 1000;
		}
		
		if (isset($box['tabs']['size']['fields']['banner_canvas'])) {
			$this->showHideImageOptions($fields, $values, 'size', $hidden = false, $fieldPrefix = 'banner_', $hasCanvas = true);
			
			if (isset($box['tabs']['size']['fields']['enlarge_canvas'])) {
				$this->showHideImageOptions($fields, $values, 'size', $hidden = !$values['size/enlarge_image'], $fieldPrefix = 'enlarge_', $hasCanvas = true);
			}
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		//...your PHP code...//
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