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
	
	$existingPlugins = checkRowExists('nested_plugins', array('instance_id' => $this->instanceId, 'is_slide' => 0));
	//$slideCount = selectCount('nested_plugins', array('instance_id' => $this->instanceId, 'is_slide' => 1));
	
	$isSlideshow = false !== strpos($this->moduleClassName, 'slide');
	
	
	if ($isSlideshow) {
		$buttonName = 'plugins_in_slideshow';
		
		if (isset($controls['actions']['framework_and_css'])) {
			$controls['actions']['framework_and_css']['label'] = adminPhrase('Slideshow CSS & framework');
		}
		$controls['actions']['settings']['label'] = adminPhrase('Slideshow properties');
		
		if ($existingPlugins) {
			$controls['actions']['nested_plugins']['label'] = adminPhrase('Edit plugins in slideshow');
		} else {
			$controls['actions']['nested_plugins']['label'] = adminPhrase('Add plugins to this slideshow');
		}
	
	} else {
		$buttonName = 'plugins_in_nest';
		
		if (isset($controls['actions']['framework_and_css'])) {
			$controls['actions']['framework_and_css']['label'] = adminPhrase('Nest CSS & framework');
		}
		$controls['actions']['settings']['label'] = adminPhrase('Nest properties');
		
		if ($existingPlugins) {
			$controls['actions']['nested_plugins']['label'] = adminPhrase('Edit plugins in nest');
		} else {
			$controls['actions']['nested_plugins']['label'] = adminPhrase('Add plugins to this nest');
		}
	}
	
	$selectedId = '';
	if (!empty($this->slideId)) {
		$selectedId = (int) $this->slideId;
	}
	
	$controls['actions']['nested_plugins']['ord'] = 0;
	$controls['actions']['nested_plugins']['onclick'] = "
		var isVersionControlled = ". engToBoolean($this->isVersionControlled). ";
		if (!isVersionControlled || zenarioA.draft(this.id, true)) {
			var object = {
				organizer_quick: {
					path: 'zenario__modules/panels/modules/item_buttons/view_instances//". (int) $this->moduleId. "//item_buttons/". $buttonName. "//". (int) $this->instanceId. "//". $selectedId. "',
					target_path: 'zenario__modules/panels/nested_plugins',
					min_path: 'zenario__modules/panels/nested_plugins',
					max_path: 'zenario__modules/panels/nested_plugins',
					disallow_refiners_looping_on_min_path: true,
					reload_slot: '". jsEscape($this->slotName). "',
					reload_admin_toolbar: true}};
			
			zenarioAT.action(object);
		}
		return false;
	";
	
	
	//For nests, add a button that opens the conductor
	
	
	if (!$isSlideshow && $this->setting('enable_conductor')) {
		
		$selectedId = '';
		if (!empty($this->state)) {
			$selectedId = 'state_'. $this->state;
		} elseif (!empty($this->slideId)) {
			$selectedId = (int) 'slide_'. $this->slideId;
		}
		
		$controls['actions']['conductor'] = $controls['actions']['settings'];
		
		$controls['actions']['conductor']['ord'] = 65;
		$controls['actions']['conductor']['label'] = adminPhrase('Nest conductor settings (advanced)');
		$controls['actions']['conductor']['onclick'] = "
			var isVersionControlled = ". engToBoolean($this->isVersionControlled). ";
			if (!isVersionControlled || zenarioA.draft(this.id, true)) {
				var object = {
					organizer_quick: {
						path: 'zenario__modules/panels/modules/item_buttons/view_instances//". (int) $this->moduleId. "//item_buttons/conductor//". (int) $this->instanceId. "//". $selectedId. "',
						target_path: 'zenario__modules/panels/conductor',
						min_path: 'zenario__modules/panels/conductor',
						max_path: 'zenario__modules/panels/conductor',
						disallow_refiners_looping_on_min_path: true,
						reload_slot: '". jsEscape($this->slotName). "',
						reload_admin_toolbar: true}};
			
				zenarioAT.action(object);
			}
			return false;
		";
	}
}

if ($this->setting('author_advice')) {
	$controls['notes']['author_advice']['label'] = nl2br(htmlspecialchars($this->setting('author_advice')));
}