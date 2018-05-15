<?php


class zenario_plugin_nest__admin_boxes__plugin_settings extends ze\moduleBaseClass {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$this->syncNestTypeSettings($box, $fields, $values, true);
		
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
		$this->syncNestTypeSettings($box, $fields, $values);
		
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
		$this->syncNestTypeSettings($box, $fields, $values);
		
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
	
	
	//The main select list in the plugin settings is actually stored as three checkboxes,
	//which I don't want to change for backwards compatability reasons
	//(e.g. some frameworks use these settings and I don't want to have to change them when migrating sites).
	//So instead I'll use this function to change the format when loading/saving
	protected function syncNestTypeSettings(&$box, &$fields, &$values, $filling = false) {
		if ($filling) {
			if ($values['first_tab/enable_conductor']) {
				$values['first_tab/nest_type'] = 'conductor';
			} else {
				if ($values['first_tab/show_next_prev_buttons']) {
					if ($values['first_tab/show_tabs']) {
						$values['first_tab/nest_type'] = 'tabs_and_buttons';
					} else {
						$values['first_tab/nest_type'] = 'buttons';
					}
				} else {
					if ($values['first_tab/show_tabs']) {
						$values['first_tab/nest_type'] = 'tabs';
					} else {
						$values['first_tab/nest_type'] = 'permission';
					}
				}
			}
		} else {
			$values['first_tab/show_tabs'] = '';
			$values['first_tab/show_next_prev_buttons'] = '';
			$values['first_tab/enable_conductor'] = '';
			
			switch ($values['first_tab/nest_type']) {
				case 'conductor':
					$values['first_tab/enable_conductor'] = 1;
					break;
				case 'tabs':
					$values['first_tab/show_tabs'] = 1;
					break;
				case 'tabs_and_buttons':
					$values['first_tab/show_tabs'] = 1;
					$values['first_tab/show_next_prev_buttons'] = 1;
					break;
				case 'buttons':
					$values['first_tab/show_next_prev_buttons'] = 1;
					break;
			}
		}
	}
}