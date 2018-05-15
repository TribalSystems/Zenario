<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

if (isset($controls['actions']['settings']['onclick'])) {
	
	//Copy the "settings" button and add a button for editing the nested slides/plugins
	$controls['actions']['nested_plugins'] = $controls['actions']['settings'];
	
	$existingPlugins = ze\row::exists('nested_plugins', ['instance_id' => $this->instanceId, 'is_slide' => 0]);
	//$slideCount = ze\row::count('nested_plugins', ['instance_id' => $this->instanceId, 'is_slide' => 1]);
	
	$isSlideshow = false !== strpos($this->moduleClassName, 'slide');
	
	
	if ($isSlideshow) {
		$buttonName = 'plugins_in_slideshow';
		
		if (isset($controls['actions']['framework_and_css'])) {
			$controls['actions']['framework_and_css']['label'] = ze\admin::phrase('Slideshow CSS & framework');
		}
		$controls['actions']['settings']['label'] = ze\admin::phrase('Slideshow settings');
		
		if ($existingPlugins) {
			$controls['actions']['nested_plugins']['label'] = ze\admin::phrase('Add/edit plugins in slideshow');
		} else {
			$controls['actions']['nested_plugins']['label'] = ze\admin::phrase('Add plugins to this slideshow');
		}
	
	} else {
		$buttonName = 'plugins_in_nest';
		
		if (isset($controls['actions']['framework_and_css'])) {
			$controls['actions']['framework_and_css']['label'] = ze\admin::phrase('Nest CSS & framework');
		}
		$controls['actions']['settings']['label'] = ze\admin::phrase('Nest settings');
		
		if ($existingPlugins) {
			$controls['actions']['nested_plugins']['label'] = ze\admin::phrase('Add/edit plugins in nest');
		} else {
			$controls['actions']['nested_plugins']['label'] = ze\admin::phrase('Add plugins to this nest');
		}
	}
	
	$selectedId = '';
	if (!empty($this->slideId)) {
		$selectedId = (int) $this->slideId;
	}
	
	$controls['actions']['nested_plugins']['ord'] = 61;
	$controls['actions']['nested_plugins']['onclick'] = "
		return zenarioAT.organizerQuick(
			'zenario__modules/panels/modules/item//". (int) $this->moduleId. "//item_buttons/". $buttonName. "//". (int) $this->instanceId. "//". $selectedId. "',
			'zenario__modules/panels/nested_plugins',
			false,
			'". ze\escape::js($this->slotName). "',
			false,
			". ze\ring::engToBoolean($this->isVersionControlled). ",
			this);";
	
	
	//For nests, add a button that edits the current slide
	if (!$isSlideshow && $selectedId) {
		
		$controls['actions']['edit_slide'] = $controls['actions']['settings'];
		
		$controls['actions']['edit_slide']['ord'] = 62;
		$controls['actions']['edit_slide']['label'] = ze\admin::phrase('Slide properties (slide [[slideNum]])', ['slideNum' => $this->slideNum]);
		$controls['actions']['edit_slide']['onclick'] = "
			var isVersionControlled = ". ze\ring::engToBoolean($this->isVersionControlled). ";
			if (!isVersionControlled || zenarioA.draft(this.id, true)) {
				zenarioA.pluginSlotEditSettings(this, '". ze\escape::js($this->slotName). "', 'zenario_slide', {id: ". (int) $selectedId. "});
			}
			return false;
		";
	}
	
	
	////For nests, add a button that opens the conductor
	//if (!$isSlideshow && $this->setting('enable_conductor')) {
	//	
	//	$selectedId = '';
	//	if (!empty($this->state)) {
	//		$selectedId = 'state_'. $this->state;
	//	} elseif (!empty($this->slideId)) {
	//		$selectedId = (int) 'slide_'. $this->slideId;
	//	}
	//	
	//	$controls['actions']['conductor'] = $controls['actions']['settings'];
	//	
	//	$controls['actions']['conductor']['ord'] = 65;
	//	$controls['actions']['conductor']['label'] = ze\admin::phrase('Nest conductor settings (advanced)');
	//$controls['actions']['nested_plugins']['onclick'] = "
	//	return zenarioAT.organizerQuick(
	//		'zenario__modules/panels/modules/item//". (int) $this->moduleId. "//item_buttons/conductor//". (int) $this->instanceId. "//". $selectedId. "',
	//		'zenario__modules/panels/conductor',
	//		true,
	//		'". ze\escape::js($this->slotName). "',
	//		false,
	//		". ze\ring::engToBoolean($this->isVersionControlled). ",
	//		this);";
	//}
}