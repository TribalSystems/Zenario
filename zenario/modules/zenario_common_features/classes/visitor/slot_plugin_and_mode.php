<?php
/*
 * Copyright (c) 2021, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

ze\module::inc('zenario_abstract_fea');

class zenario_common_features__visitor__slot_plugin_and_mode extends zenario_abstract_fea {
	
	public function returnVisitorTUIXEnabled($path) {
		return ze\user::can('design', 'schema', ze::$vars['schemaId']);
	}


	public function fillVisitorTUIX($path, &$tags, &$fields, &$values) {
		parent::fillVisitorTUIX($path, $tags, $fields, $values);
		$this->translatePhrasesInTUIX($tags, $path);
		
		$grid = [];
		if (!empty($_POST['gridProperties'])
		 && is_array($_POST['gridProperties'])) {
			$grid = $_POST['gridProperties'];
		}
		$tags['key']['grid'] = $grid;
		$tags['key']['slotI'] = (int) ($_POST['slotI'] ?? '');
		$tags['key']['slotLevels'] = $_POST['slotLevels'] ?? '';
		
		$cell = [];
		if (!empty($_POST['cellProperties'])) {
			$cell = json_decode($_POST['cellProperties'], true) ?? [];
		}
		
		$tags['key']['cell'] = $cell;
		unset($tags['key']['cell']['cells']);
		unset($tags['key']['cell']['settings']);
		
		
		$pluginClassName = $cell['class_name'] ?? '';
		$pluginMode = $cell['settings']['mode'] ?? '';
		
		if ($pluginClassName) {
			$values['details/plugin_and_mode'] = $pluginClassName. '`';
			
			if ($pluginMode) {
				$values['details/plugin_and_mode'] .= $pluginMode;
			}
		}
		
		$values['details/small_screens'] = $cell['small_screens'] ?? 'show';
	}
	
	public function formatVisitorTUIX($path, &$tags, &$fields, &$values, &$changes) {
		
	}
	
	public function validateVisitorTUIX($path, &$tags, &$fields, &$values, &$changes, $saving) {
		
	}
	
	public function saveVisitorTUIX($path, &$tags, &$fields, &$values, &$changes) {
		
		$updatedProperties = [];
		$updatedSettings = [];
		
		//If the user edits and changes a mode, wipe all of the existing settings
		if (!empty($fields['details/plugin_and_mode']['value'])
		 && $fields['details/plugin_and_mode']['value'] != $fields['details/plugin_and_mode']['current_value']) {
			$updatedProperties['settings'] = null;
		}
		
		$plugin_and_mode = explode('`', $values['details/plugin_and_mode']);
		
		$updatedProperties['class_name'] = $plugin_and_mode[0];
		$updatedSettings['mode'] = $plugin_and_mode[1] ?? '';
		
		$updatedProperties['small_screens'] = $values['details/small_screens'];
		
		$updatedProperties['label'] = $tags['lovs']['modes'][$values['details/plugin_and_mode']]['label'] ?? '';
		
		$next = (bool) !empty($fields['details/next']['pressed']);
		
		//$tags['reload_parent'] = true;
		$tags['close_popout'] = true;
		$tags['stop_flow'] = true;
		$tags['js'] = '
			lib.closeAndUpdateSlot('. json_encode($updatedProperties, JSON_FORCE_OBJECT). ', '. json_encode($updatedSettings, JSON_FORCE_OBJECT). ', '. json_encode($next, JSON_FORCE_OBJECT). ');';
	}
}