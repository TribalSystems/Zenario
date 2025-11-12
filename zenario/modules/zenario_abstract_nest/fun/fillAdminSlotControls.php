<?php
/*
 * Copyright (c) 2025, Tribal Limited
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
	
	$isSlideshow = $this->moduleClassName == 'zenario_slideshow';
	//$isConductor = !$isSlideshow && $this->setting('nest_type') == 'conductor';
	$showConvert = !$isSlideshow && ze\module::isRunning('zenario_nest') && ze\module::isRunning('zenario_ajax_nest') && ze\priv::check('_PRIV_MANAGE_REUSABLE_PLUGIN');
	
	//Copy the "settings" button and add a button for editing the nested slides/plugins
	$controls['actions']['nested_plugins'] = $controls['actions']['settings'];
	
	//Add the "convert to nest" button as well for nests, if both modules are running
	if ($showConvert) {
		$controls['actions']['convert'] = $controls['actions']['settings'];
		//$controls['actions']['convert']['admin_box']['path'] = 'zenario_convert_nest';
		$controls['actions']['convert']['ord'] = 61;
		$controls['actions']['convert']['label'] = ze\admin::phrase('Convert nest...');
		$controls['actions']['convert']['onclick'] = "return zenarioA.pluginSlotEditSettings(this, slotName, 'zenario_convert_nest');";
	}
	
	
	$existingPlugins = ze\row::exists('nested_plugins', ['instance_id' => $this->instanceId, 'is_slide' => 0]);
	//$slideCount = ze\row::count('nested_plugins', ['instance_id' => $this->instanceId, 'is_slide' => 1]);
	
	$organizerLink = 'organizer.php?fromCID='. ze::$cID. '&fromCType='. ze::$cType. '#';
	
	if ($isSlideshow) {
		$buttonName = 'images_in_slideshow';
		$tagPath = 'zenario__library/panels/images_in_slideshow';
	} else {
		$buttonName = 'plugins_in_nest';
		$tagPath = 'zenario__library/panels/nested_plugins';
	}
	
	$navPath = 'zenario__library/panels/modules/item//'. (int) $this->moduleId. '//item_buttons/'. $buttonName. '//'. (int) $this->instanceId. '//';
	
	
	if ($isSlideshow) {
		$controls['actions']['settings']['label'] = ze\admin::phrase('Slideshow settings');
	} else {
		$controls['actions']['settings']['label'] = ze\admin::phrase('Nest settings');
	}
	
	
	//Setup the button to open up the Organizer view for editing a nest/slideshow
	if ($isSlideshow) {
		if ($existingPlugins) {
			$controls['actions']['nested_plugins']['label'] = ze\admin::phrase('View this slideshow\'s images');
		} else {
			$controls['actions']['nested_plugins']['label'] = ze\admin::phrase('Add images to this slideshow');
		}
	
	} else {
		if (!$isSlideshow && !empty($this->slideId)) {
			$navPath .= $this->slideId;
			$controls['actions']['nested_plugins']['label'] = ze\admin::phrase('View slide [[slideNum]] in nest', ['slideNum' => $this->slideNum]);
		
		} elseif ($existingPlugins) {
			$controls['actions']['nested_plugins']['label'] = ze\admin::phrase('View this nest\'s plugins');
		} else {
			$controls['actions']['nested_plugins']['label'] = ze\admin::phrase('Add plugins to this nest');
		}
	}
	
	$controls['actions']['nested_plugins']['ord'] = -1;
	$controls['actions']['nested_plugins']['onclick'] = "
		return zenarioAT.organizerQuick(
			'". $navPath. "',
			'". $tagPath. "',
			false,
			'". ze\escape::js($this->slotName). "',
			false,
			". ze\ring::engToBoolean($this->isVersionControlled). ",
			this);";
	
	$controls['actions']['nested_plugins']['link_to_new_tab'] = $organizerLink. $navPath;
	
	
	////For nests, add a button that edits the current slide
	//$selectedId = '';
	//if (!empty($this->slideId)) {
	//	$selectedId = (int) $this->slideId;
	//}
	//
	//if (!$isSlideshow && $selectedId) {
	//	
	//	$controls['actions']['edit_slide'] = $controls['actions']['settings'];
	//	
	//	$controls['actions']['edit_slide']['ord'] = 62;
	//
	//	$staticMethodInfo = ze\row::get('nested_plugins', ['module_class_name', 'method_name'], ['instance_id' => $this->instanceId, 'is_slide' => 1, 'id' => (int) $this->slideId]);
	//	if ($staticMethodInfo['module_class_name'] && $staticMethodInfo['method_name']) {
	//		$controls['actions']['edit_slide']['label'] = ze\admin::phrase('Slide properties (slide [[slideNum]]) [Static method used]', ['slideNum' => $this->slideNum]);
	//	} else {
	//		$controls['actions']['edit_slide']['label'] = ze\admin::phrase('Slide properties (slide [[slideNum]])', ['slideNum' => $this->slideNum]);
	//	}
	//
	//	$controls['actions']['edit_slide']['onclick'] = "
	//		var isVersionControlled = ". ze\ring::engToBoolean($this->isVersionControlled). ";
	//		if (!isVersionControlled || zenarioA.draft(this.id, true)) {
	//			zenarioA.pluginSlotEditSettings(this, '". ze\escape::js($this->slotName). "', 'zenario_slide', {id: ". (int) $selectedId. "});
	//		}
	//		return false;
	//	";
	//}
	
	
	////For nests, add a button that opens the conductor
	//if (!$isSlideshow && $this->setting('nest_type') == 'conductor') {
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
	//	$controls['actions']['conductor']['label'] = ze\admin::phrase('Conductor state diagram');
	//$controls['actions']['nested_plugins']['onclick'] = "
	//	return zenarioAT.organizerQuick(
	//		'zenario__library/panels/modules/item//". (int) $this->moduleId. "//item_buttons/conductor//". (int) $this->instanceId. "//". $selectedId. "',
	//		'zenario__library/panels/conductor',
	//		true,
	//		'". ze\escape::js($this->slotName). "',
	//		false,
	//		". ze\ring::engToBoolean($this->isVersionControlled). ",
	//		this);";
	//}
}

$this->listImagesOnSlotControls($controls, $this->imgsUsed, false);
