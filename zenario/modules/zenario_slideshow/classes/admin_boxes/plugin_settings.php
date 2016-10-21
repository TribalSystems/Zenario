<?php


class zenario_slideshow__admin_boxes__plugin_settings extends module_base_class {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//Hide all of the mode-dependant tabs if that mode is not selected
		foreach ($fields['first_tab/mode']['values'] as $key => $label) {
			if ($key && isset($box['tabs'][$key. '_effects'])) {
				$box['tabs'][$key. '_effects']['hidden'] = $values['first_tab/mode'] != $key;
			}
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