<?php


//The plugin settings for the nests/slideshows have a lot of common functionality,
//so inherit their logic from
ze\module::incSubclass('zenario_abstract_nest', 'admin_boxes', 'plugin_settings');

class zenario_slideshow__admin_boxes__plugin_settings extends zenario_abstract_nest__admin_boxes__plugin_settings {
	
	
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
	#
	#public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	#	//...
	#}
	#
	#public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	#	//...
	#}
	#
	#public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	#	//...
	#}

}