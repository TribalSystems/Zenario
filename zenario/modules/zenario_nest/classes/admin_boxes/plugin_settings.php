<?php


//The plugin settings for the nests/slideshows have a lot of common functionality,
//so inherit their logic from
ze\module::incSubclass('zenario_abstract_nest', 'admin_boxes', 'plugin_settings');

class zenario_nest__admin_boxes__plugin_settings extends zenario_abstract_nest__admin_boxes__plugin_settings {
	
	#public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
	#	//...
	#}
	#
	#public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	#	//...
	#}
	#
	#public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
	#	//...
	#}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		parent::saveAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		
		//If the effects tab has been edited, reload the entire page rather than just the current slot as reloading the slot
		//but then using a different effect than before can cause a few bugs
		if (isset($box['tabs']['effects']['edit_mode']['on']) && $box['tabs']['effects']['edit_mode']['on']) {
			$box['key']['slotName'] = '';
		}
	}
	
	#public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	#	//...
	#}
	#
	#public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	#	//...
	#}
	

}