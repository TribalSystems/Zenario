<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

$html = '';
$slotWrapperClasses = array();

//Don't try to add and slot controls if this is a layout preview
if (cms_core::$cID === -1) {
	return;
}


//Add a JSON object for every slot controlbox on the page
if (!empty($slotContents) && is_array($slotContents)) {
	//Load the TUIX tags for empty slots (these will always be the same)
	$tagsEmpty = array();
	$modulesEmpty = array();
	loadTUIX($modulesEmpty, $tagsEmpty, 'slot_controls', $path = 'empty_slot');
	$removedColumns = false;
	zenarioParseTUIX2($tagsEmpty, $removedColumns, 'slot_controls', $path);
	$tagsEmpty = $tagsEmpty[$path];
	
	$sections = array('info', 'notes', 'actions', 'overridden_info', 'overridden_actions');
	
	//Loop through all of the slots
	$activeModules = array();
	foreach ($slotContents as $slotName => &$instance) {
		
		//Only output slot controls for non-nested Plugins.
		if (!empty($instance['egg_id'])) {
			continue;
		}
		
		$compatibilityClassNames = array();
		$level = (int) arrayKey($instance, 'level');
		$isVersionControlled = !empty($instance['content_id']);
		$containerId = 'plgslt_'. $slotName;
		
		if ($empty = empty($instance['instance_id'])) {
			//If the slot is empty, use a copy of the array from above
			$tags = $tagsEmpty;
			$modules = &$modulesEmpty;
			$moduleId = 0;
			$instanceId = 0;
		
		} else {
			//If the slot is not empty, call loadTUIX() each time to get the tags for that Module
			$moduleId = $instance['module_id'];
			$instanceId = $instance['instance_id'];
			
			foreach (getModuleInheritances($instance['class_name'], 'inherit_settings') as $className) {
				$compatibilityClassNames[$className] = $className;
			}
			
			$modules = array();
			$tags = array();
			loadTUIX($modules, $tags, 'slot_controls', $path = 'full_slot', '', $compatibilityClassNames);
			$removedColumns = false;
			zenarioParseTUIX2($tags, $removedColumns, 'slot_controls', $path);
			$tags = $tags[$path];
		}
		
		//Call the fill method for each Module that added tags
		foreach ($modules as $className => &$module) {
			if (!isset($activeModules[$className])) {
				$activeModules[$className] = activateModule($className);
			}
			
			$activeModules[$className]->fillAllAdminSlotControls(
				$tags,
				cms_core::$cID, cms_core::$cType, cms_core::$cVersion,
				$slotName, $containerId,
				$level, $moduleId, $instanceId, $isVersionControlled
			);
		}
		
		//Call fillAdminSlotControls(), which is like fillAllAdminSlotControls() but lets a specific Plugin
		//alter its own controls, in an environment where it has access to its own Plugin Settings
		if (isset($slotContents[$slotName]['class']) && !empty($slotContents[$slotName]['class'])) {
			$slotContents[$slotName]['class']->fillAdminSlotControls($tags);
		}
		
		foreach ($sections as $section) {
			if (!empty($tags[$section]) && is_array($tags[$section])) {
				sortTUIX($tags[$section]);
			}
		}
		
		if (!$ajaxReload) {
			$slotWrapperClasses[$slotName] = $tags['css_class'];
			
			//Add a css class around slots that are being edited using the WYSIWYG Editor
			if ($slotContents[$slotName]['class']->beingEdited()) {
				$slotWrapperClasses[$slotName] .= ' zenario_slot_being_edited';
			}
			
			$html .= '
				<div id="zenario_fbAdminSlotControls-'. $slotName. '" style="display: none;" onmouseout="zenarioA.closeSlotControlsAfterDelay();" onmouseover="zenarioA.dontCloseSlotControls();" class="zenario_fbAdminSlotControls">
					<div class="zenario_slotControlsWrap" id="zenario_fbAdminPluginOptionsWrap-'. $slotName. '">
						<div id="zenario_fbAdminSlotControlsContents-'. $slotName. '">';
		}
		
		//Output the slot controls
		foreach ($sections as $section) {
			if (!empty($tags[$section]) && is_array($tags[$section])) {
				$thisHtml = '
					<div class="zenario_slotControlsWrap_'. $section. '"';
				
				if ($section == 'actions' || $section == 'overridden_actions') {
					$thisHtml .= ' onclick="zenarioA.closeSlotControls();"';
				}
				$thisHtml .= '>';
				
				$foundButton = false;
				foreach ($tags[$section] as $id => &$control) {
					if (is_array($control) && !empty($control['label']) && !engToBooleanArray($control, 'hidden')) {
						$foundButton = true;
						
						$thisHtml .= '<div id="'. htmlspecialchars('zenario_slot_control__'. $slotName. '__'. $section. '__'. $id). '" class="zenario_sc ';
						
						if (engToBooleanArray($control, 'page_modes', 'menu')) {
							$thisHtml .= 'zenario_showInMenuMode ';
						}
						if (engToBooleanArray($control, 'page_modes', 'edit')) {
							$thisHtml .= 'zenario_showInEditMode ';
						}
						if (engToBooleanArray($control, 'page_modes', 'layout')) {
							$thisHtml .= 'zenario_showInLayoutMode ';
						}
						
						if (isset($control['css_class'])) {
							$thisHtml .= htmlspecialchars($control['css_class']);
						}
						
						$thisHtml .= '" data-slotname="'. htmlspecialchars($slotName). '"';
						
						if (isset($control['onclick'])) {
							$thisHtml .= ' href="#" onclick="';
							
							if (strpos($control['onclick'], 'slotName') !== false) {
								$thisHtml .= "var slotName = '". jsOnclickEscape($slotName). "'; ";
							}
							if (strpos($control['onclick'], 'instanceId') !== false) {
								$thisHtml .= 'var instanceId = '. (int) $instanceId. '; ';
							}
							if (strpos($control['onclick'], 'moduleId') !== false) {
								$thisHtml .= 'var moduleId = '. (int) $moduleId. '; ';
							}
								
							$thisHtml .= htmlspecialchars($control['onclick']). '"';
						}
						$thisHtml .= '>'. $control['label']. '</div>';
					}
				}
				
				$thisHtml .= '
					</div>';
				
				if ($foundButton) {
					$html .= $thisHtml;
				}
				unset($thisHtml);
			}
		}
		
		if ($ajaxReload) {
			echo
				'<!--SLOT_CONTROLS--',
					eschyp($html),
				'-->';

			echo
				'<!--SLOT_CONTROLS_CSS_CLASS--',
					eschyp($tags['css_class']),
				'-->';
			
			$html = '';
		
		} else {
			$html .= '
						</div>
					</div>
				</div>';
		}
	}
}
	
if (!$ajaxReload) {
	if (!empty($slotWrapperClasses)) {
		echo
			"\n", '<script type="text/javascript">',
			"\n\t", 'var a=function(s,c){s = document.getElementById(\'plgslt_\'+s+\'-wrap\'); if (s) s.className=c;};';
		
		foreach ($slotWrapperClasses as $slotName => $cssClass) {
			echo "\n\t", 'a(\'', htmlspecialchars($slotName), '\', \'', htmlspecialchars($cssClass), '\');';
		}
		
		echo "\n", '</script>';
	}
	
	echo $html;
}