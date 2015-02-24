<?php
/*
 * Copyright (c) 2015, Tribal Limited
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


$pageMode = array();
$couldChange = $canChange = false;
$couldEdit = checkPriv('_PRIV_EDIT_DRAFT', $cID, $cType);
$canEdit = checkPriv('_PRIV_EDIT_DRAFT', $cID, $cType, $cVersion);
$isNest = !empty(cms_core::$slotContents[$slotName]['is_nest']);

//Check to see if there are entries on the item and layout layer
$overriddenPlugin = false;
if ($level == 1) {
	$overriddenPlugin = getRow(
		'plugin_layout_link',
		array('module_id', 'instance_id'),
		array('slot_name' => $slotName, 'layout_id' => cms_core::$layoutId));

	//Treat the case of hidden (item layer) and empty (layout layer) as just empty
	if (!$overriddenPlugin && !$moduleId) {
		$level = 0;
	}
}

switch ($level) {
	case 1:
		$pageMode = array('edit' => true);
		$couldChange = checkPriv('_PRIV_MANAGE_ITEM_SLOT', $cID, $cType);
		$canChange = checkPriv('_PRIV_MANAGE_ITEM_SLOT', $cID, $cType, $cVersion);
		
		break;
	
	case 2:
		$pageMode = array('layout' => true);
		$couldChange = $canChange = checkPriv('_PRIV_MANAGE_TEMPLATE_SLOT');
		
		break;
	
	default:
		break;
}

$settingsPageMode = $isVersionControlled? array('edit' => true) : $pageMode;


//Format options if the slot is empty
if (!$moduleId) {
	$controls['info']['slot_name']['label'] = adminPhrase('[[slotName]]:', array('slotName' => $slotName));
	
	$pageMode = array('edit' => true, 'layout' => true);
	
	if (!$level) {
		//Empty slots
		unset($controls['info']['opaque']);
		unset($controls['actions']['replace_reusable_on_item_layer']);
	
		//On the Layout Layer, add an option to insert a Wireframe version of each Plugin
		//that is flagged as uses wireframe.
		if (checkPriv('_PRIV_MANAGE_TEMPLATE_SLOT')) {
			$i = 0;
			foreach (getRowsArray(
				'modules',
				array('id', 'display_name'),
				array('status' => 'module_running', 'is_pluggable' => 1, 'can_be_version_controlled' => 1),
				'display_name'
			) as $module) {
				$controls['actions'][] = array(
					'ord' => ++$i,
					'label' => adminPhrase('Insert a [[display_name]]', $module),
					'page_modes' => array('layout' => true),
					'onclick' => "zenarioA.addNewWireframePlugin(this, '". jsEscape($slotName). "', ". (int) $module['id']. ");"
				);
			}
		}
	} else {
		//Opaque slots
		unset($controls['info']['empty']);
		unset($controls['actions']['insert_reusable_on_item_layer']);
		unset($controls['actions']['insert_reusable_on_layout_layer']);
	}
	

} else {
	$controls['info']['slot_name']['label'] = adminPhrase('In [[slotName]]:', array('slotName' => $slotName));
	
	//Get information from the plugin itself
	
	
	//Show the wrapping html, id and css class names for the slot
	if (isset(cms_core::$slotContents[$slotName]['class']) && !empty(cms_core::$slotContents[$slotName]['class'])) {
		$a = array();
		cms_core::$slotContents[$slotName]['class']->tApiGetCachableVars($a);
		$framework = $a[0];
		$cssClass = $a[4];
		
		$controls['info']['slot_css_class']['label'] = adminPhrase('CSS classes: <span>[[cssClass]]</span>', array('cssClass' => htmlspecialchars($cssClass)));
	} else {
		unset($controls['info']['slot_css_class']);
	}
	
	fillAdminSlotControlPluginInfo($moduleId, $instanceId, $isVersionControlled, $cID, $cType, $level, $isNest, $controls['info'], $controls['actions']);

	
	
	$controls['actions']['settings']['page_modes'] =
	$controls['actions']['framework_and_css']['page_modes'] = $settingsPageMode;
	
	if ($isVersionControlled && cms_core::$cVersion == cms_core::$adminVersion) {
		if (cms_core::$locked) {
			$controls['actions']['settings']['label'] = $controls['actions']['settings']['label']['locked'];
		
		} elseif (!isDraft(cms_core::$status)) {
			if (!checkPriv('_PRIV_CREATE_REVISION_DRAFT')) {
				$controls['actions']['settings']['label'] = $controls['actions']['settings']['label']['cant_make_draft'];
			
			} elseif (cms_core::$status == 'trashed') {
				$controls['actions']['settings']['label'] = $controls['actions']['settings']['label']['trashed'];
			
			} elseif (cms_core::$status == 'hidden') {
				$controls['actions']['settings']['label'] = $controls['actions']['settings']['label']['hidden'];
			} else {
				$controls['actions']['settings']['label'] = $controls['actions']['settings']['label']['normal'];
			}
		} else {
			$controls['actions']['settings']['label'] = $controls['actions']['settings']['label']['normal'];
		}
	
	} elseif (!$isVersionControlled && checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
		$controls['actions']['settings']['label'] = $controls['actions']['settings']['label']['settings'];
	
	} elseif ($isVersionControlled || (!$isVersionControlled && checkPriv('_PRIV_VIEW_REUSABLE_PLUGIN'))) {
		$controls['actions']['settings']['label'] = $controls['actions']['settings']['label']['view_settings'];
	
	} else {
		unset($controls['actions']['settings']);
		unset($controls['actions']['framework_and_css']);
	}
	
	//Show options to convert the old nest plugins
	if ($isNest
	 && (($isVersionControlled && $canChange && $level == 1)
	  || (!$isVersionControlled && checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')))) {
		$controls['actions']['convert_nest']['page_modes'] = $pageMode;
	} else {
		unset($controls['actions']['convert_nest']);
	}
	
	
	if (!$couldChange || $level == 2) {
		unset($controls['actions']['move_on_item_layer']);
		unset($controls['actions']['remove_from_item_layer']);
	}
	if (!$couldChange || $level == 1) {
		unset($controls['actions']['move_on_layout_layer']);
		unset($controls['actions']['remove_from_layout_layer']);
	}
	if (!$couldChange || $level == 1 || $isVersionControlled) {
		unset($controls['actions']['insert_reusable_on_item_layer']);
	}
	if (!$couldChange || $isVersionControlled) {
		unset($controls['actions']['replace_reusable_on_item_layer']);
	}
	if (!$couldChange || ($level == 1 && !$overriddenPlugin) || $isVersionControlled) {
		unset($controls['actions']['hide_plugin']);
	}

	
	//Set the right CSS class around the slot and control box
	$controls['css_class'] .= ' zenario_level'. $level;
	
	if (isset(cms_core::$slotContents[$slotName]['class']) && !empty(cms_core::$slotContents[$slotName]['class'])) {
		if (cms_core::$slotContents[$slotName]['class']->shownInMenuMode()) {
			$controls['css_class'] .= ' zenario_slotShownInMenuMode';
		}
		if (empty(cms_core::$slotContents[$slotName]['init'])) {
			$controls['css_class'] .= ' zenario_slotNotShownInVisitorMode';
		}
		if ($isVersionControlled) {
			$controls['css_class'] .= ' zenario_wireframe';
		} else {
			$controls['css_class'] .= ' zenario_reusable';
			
			if ($level == 1 || cms_core::$slotContents[$slotName]['class']->shownInEditMode()) {
				//Check to see if there are any actions available on the Item/Edit tab
				$actions = false;
				foreach ($controls['actions'] as &$action) {
					if (!empty($action['page_modes']['edit'])) {
						$actions = true;
						break;
					}
				}
				
				if ($actions) {
					$controls['css_class'] .= ' zenario_reusableWithActionsInEditMode';
				} else {
					$controls['css_class'] .= ' zenario_reusableShownInEditMode';
				}
			} else {
				$controls['css_class'] .= ' zenario_reusableNotShownInEditMode';
			}
		}
	}
	
	//Don't allow wireframe plugins to be replaced
	if ($isVersionControlled) {
		unset($controls['actions']['replace_reusable_on_item_layer']);
		unset($controls['actions']['insert_reusable_on_item_layer']);
		unset($controls['actions']['insert_reusable_on_layout_layer']);
	}
}

if (!$couldEdit) {
	unset($controls['actions']['replace_reusable_on_item_layer']);
	unset($controls['actions']['insert_reusable_on_item_layer']);
}


//If there is a hidden plugin at the layout layer, display info and some actions for that too
if ($overriddenPlugin) {
	$overriddenPluginIsNest = checkRowExists('nested_plugins', array('instance_id' => $overriddenPlugin['instance_id']));
	$overriddenIsVersionControlled = !$overriddenPlugin['instance_id'];
	fillAdminSlotControlPluginInfo($overriddenPlugin['module_id'], $overriddenPlugin['instance_id'], $overriddenIsVersionControlled, $cID, $cType, 2, $overriddenPluginIsNest, $controls['overridden_info'], $controls['overridden_actions']);
	
	if (!$couldChange) {
		unset($controls['overridden_actions']['show_plugin']);
	}
	
	if (!checkPriv('_PRIV_MANAGE_TEMPLATE_SLOT')) {
		unset($controls['overridden_actions']['remove_from_layout_layer']);
	}
	
	//Don't allow wireframe plugins to be replaced
	if ($overriddenIsVersionControlled) {
		unset($controls['actions']['insert_reusable_on_layout_layer']);
	}
	
} else {
	unset($controls['overridden_info']);
	unset($controls['overridden_actions']);
}