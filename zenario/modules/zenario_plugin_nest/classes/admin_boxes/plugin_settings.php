<?php


class zenario_plugin_nest__admin_boxes__plugin_settings extends ze\moduleBaseClass {
	
	
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
		
		//...your PHP code...//
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
}