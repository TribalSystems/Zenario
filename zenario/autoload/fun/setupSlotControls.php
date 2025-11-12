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

//Don't try to add and slot controls if this is a layout preview
if (ze::$cID === -1) {
	return;
}


//Add a JSON object for every slot controlbox on the page
if (!empty($slotContents) && is_array($slotContents)) {
	
	//Load the TUIX tags for empty slots (these will always be the same)
	$tagsEmpty = [];
	$modulesEmpty = [];
	\ze\tuix::load($modulesEmpty, $tagsEmpty, 'slot_controls', $path = 'empty_slot');
	$removedColumns = false;
	\ze\tuix::parse2($tagsEmpty, $removedColumns, 'slot_controls', $path);
	$tagsEmpty = $tagsEmpty[$path];
	
	$swTagsEmpty = [];
	$swModulesEmpty = [];
	\ze\tuix::load($swModulesEmpty, $swTagsEmpty, 'slot_controls', $path = 'empty_sitewide_slot');
	$removedColumns = false;
	\ze\tuix::parse2($swTagsEmpty, $removedColumns, 'slot_controls', $path);
	$swTagsEmpty = $swTagsEmpty[$path];
	
	$sections = ['info', 'notes', 'actions', 'switch_to', 're_move_place', 'overridden_info', 'overridden_actions', 'no_perms'];
	
	//Loop through all of the slots
	$activeModules = [];
	foreach ($slotContents as $slotName => &$slot) {
		
		//Only output slot controls for non-nested Plugins.
		if ($slot->eggId()) {
			continue;
		}
		
		$compatibilityClassNames = [];
		$level = (int) $slot->level();
		$isVersionControlled = $slot->isVersionControlled();
		$containerId = 'plgslt_'. $slotName;
		
		//Check the meta-info for slots in the header and footer.
		$isHeader = $slot->isHeader();
		$isFooter = $slot->isFooter();
		$isSitewide = $isHeader || $isFooter;
		
		if ($empty = !$slot->instanceId()) {
			//If the slot is empty, use a copy of the array from above
			if ($isSitewide) {
				$tags = $swTagsEmpty;
				$modules = &$swModulesEmpty;
			} else {
				$tags = $tagsEmpty;
				$modules = &$modulesEmpty;
			}
			$moduleId = 0;
			$instanceId = 0;
		
		} else {
			//If the slot is not empty, call \ze\tuix::load() each time to get the tags for that Module
			$moduleId = $slot->moduleId();
			$instanceId = $slot->instanceId();
			
			foreach (\ze\module::inheritances($slot->moduleClassName(), 'inherit_settings') as $className) {
				$compatibilityClassNames[$className] = $className;
			}
			
			$modules = [];
			$tags = [];
			\ze\tuix::load($modules, $tags, 'slot_controls', $path = $isSitewide? 'full_sitewide_slot' : 'full_slot', '', $compatibilityClassNames);
			$removedColumns = false;
			\ze\tuix::parse2($tags, $removedColumns, 'slot_controls', $path);
			$tags = $tags[$path];
		}
		
		//All of the slot controls for the site-wide slots use the ~header~ mergefield.
		//This should either say "header" or "footer", depending on whether this slot is in the header or footer.
		if ($isSitewide) {
			if ($isHeader) {
				$replace = 'header';
			} else {
				$replace = 'footer';
			}
			foreach (['info', 'actions', 're_move_place', 'overridden_info', 'overridden_actions', 'switch_to'] as $section) {
				if (!empty($tags[$section]) && is_array($tags[$section])) {
					foreach ($tags[$section] as $id => &$control) {
						if (is_array($control)) {
							if (isset($control['label'])) {
								$control['label'] = str_replace('~header~', $replace, $control['label']);
							}
							if (isset($control['label_like4like'])) {
								$control['label_like4like'] = str_replace('~header~', $replace, $control['label_like4like']);
							}
						}
					}
				}
			}
		}
		
		//Call the fill method for each Module that added tags
		foreach ($modules as $className => &$module) {
			if (!isset($activeModules[$className])) {
				$activeModules[$className] = \ze\module::activate($className);
			}
			
			$activeModules[$className]->fillAllAdminSlotControls(
				$tags,
				ze::$cID, ze::$cType, ze::$cVersion,
				$slotName, $containerId,
				$level, $moduleId, $instanceId, $isVersionControlled
			);
		}
		
		//Call fillAdminSlotControls(), which is like fillAllAdminSlotControls() but lets a specific Plugin
		//alter its own controls, in an environment where it has access to its own Plugin Settings
		if ($slotContents[$slotName]->class()) {
			$slotContents[$slotName]->class()->fillAdminSlotControls($tags);
		}
		
		foreach ($sections as $section) {
			if (!empty($tags[$section]) && is_array($tags[$section])) {
				\ze\tuix::sort($tags[$section]);
			}
		}
		
		
		if (!$ajaxReload) {
			echo '
				<div id="zenario_fbAdminSlotControls-', $slotName, '" style="display: none;" onmouseout="zenarioA.closeSlotControlsAfterDelay();" onmouseover="zenarioA.dontCloseSlotControls();" class="zenario_fbAdminSlotControls">
					<div class="zenario_slotControlsWrap" id="zenario_fbAdminPluginOptionsWrap-', $slotName, '">';
			
			if (\ze\admin::showDevTools()) {
				echo '
						<div class="zenario_debug" onclick="zenarioA.debugSlotControls(\'', $slotName, '\');"><div></div></div>';
			}
			
			echo '
						<div id="zenario_fbAdminSlotControlsContents-', $slotName, '">';
		}
		
		
		if ($ajaxReload) {
			return $tags;
		
		} else {
			echo '
						</div>
					</div>
				</div>
				<script type="text/javascript">
					zOnLoad(function() { zenarioA.setSlotControls(\''. \ze\escape::js($slotName). '\', ';
						
						ze\ray::jsonDump($tags);
			
			echo '
					); });
				</script>';
		}
	}
}