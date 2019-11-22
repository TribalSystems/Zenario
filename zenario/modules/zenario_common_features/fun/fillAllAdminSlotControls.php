<?php
/*
 * Copyright (c) 2019, Tribal Limited
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


/*
fillAllAdminSlotControls(
	&$controls,
	$cID, $cType, $cVersion,
	$slotName, $containerId,
	$level, $moduleId, $instanceId, $isVersionControlled
)
*/


if (ze::$cVersion == ze::$adminVersion) {
	$couldEdit = ze\priv::check('_PRIV_EDIT_DRAFT', $cID, $cType);
	//$canEdit = ze\priv::check('_PRIV_EDIT_DRAFT', $cID, $cType, $cVersion);
} else {
	$couldEdit = false;
	//$canEdit = false;
}

$pageMode = [];
$isNest = !empty(ze::$slotContents[$slotName]['is_nest']);
$isSlideshow = !empty(ze::$slotContents[$slotName]['is_slideshow']);


//Check to see if there are entries on the item and layout layer
$overriddenPlugin = false;
if ($level == 1) {
	$overriddenPlugin = ze\row::get(
		'plugin_layout_link',
		['module_id', 'instance_id'],
		['slot_name' => $slotName, 'layout_id' => ze::$layoutId]);

	//Treat the case of hidden (item layer) and empty (layout layer) as just empty
	if (!$overriddenPlugin && !$moduleId) {
		$level = 0;
	}
}

$mrg = ['slotName' => $slotName];
$controls['info']['slot_lite_details']['label'] = ze\admin::phrase('Slot: <span>[[slotName]]</span>', $mrg);
$controls['info']['slot_name']['label'] = ze\admin::phrase('Slot name: <span>[[slotName]]</span>', $mrg);

switch ($level) {
	case 1:
		$pageMode = ['item' => true];
		$controls['info']['in_this_slot']['label'] = ze\admin::phrase('In this slot on this content item:');
		
		if (ze::$cVersion == ze::$adminVersion) {
			$couldChange = !ze::$locked && ze\priv::check('_PRIV_MANAGE_ITEM_SLOT', $cID, $cType);
			$canChange = ze\priv::check('_PRIV_MANAGE_ITEM_SLOT', $cID, $cType, $cVersion);
		} else {
			$couldChange = false;
			$canChange = false;
		}
		
		unset($controls['actions']['insert_reusable_on_layout_layer'], $controls['re_move_place']['replace_reusable_on_layout_layer']);
		unset($controls['actions']['insert_nest_on_layout_layer'], $controls['re_move_place']['replace_nest_on_layout_layer']);
		unset($controls['actions']['insert_slideshow_on_layout_layer'], $controls['re_move_place']['replace_slideshow_on_layout_layer']);
		unset($controls['re_move_place']['remove_from_layout_layer']);
		
		break;
	
	case 2:
		$pageMode = ['layout' => true];
		$couldChange = $canChange = ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT');
		$controls['info']['in_this_slot']['label'] = ze\admin::phrase('In this slot on this layout:');
		
		break;
	
	default:
		$couldChange = $canChange = false;
		break;
}

if ($isVersionControlled) {
	$settingsPageMode = ['edit' => true];
	$cssFrameworkPageMode = ['edit' => true];
} else {
	$settingsPageMode = $pageMode;
	$cssFrameworkPageMode = $pageMode;
}



//Format options if the slot is empty
if (!$moduleId) {
	$pageMode = ['edit' => true, 'layout' => true];
	
	if (!$level) {
		//Empty slots
		unset($controls['info']['opaque']);
		unset($controls['re_move_place']['replace_reusable_on_item_layer']);
		unset($controls['re_move_place']['replace_nest_on_item_layer']);
		unset($controls['re_move_place']['replace_slideshow_on_item_layer']);
	
		//On the Layout Layer, add an option to insert a Wireframe version of each Plugin
		//that is flagged as uses wireframe.
		if (ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT')) {
			$i = 0;
			foreach (ze\row::getAssocs(
				'modules',
				['id', 'display_name'],
				['status' => ['module_running', 'module_is_abstract'], 'is_pluggable' => 1, 'can_be_version_controlled' => 1],
				'display_name'
			) as $module) {
				$controls['actions'][] = [
					'ord' => ++$i,
					'label' => ze\admin::phrase('Insert a [[display_name]]', $module),
					'page_modes' => ['layout' => true],
					'onclick' => "zenarioA.addNewWireframePlugin(this, '". ze\escape::js($slotName). "', ". (int) $module['id']. ");"
				];
			}
		}
	} else {
		//Opaque slots
		unset($controls['info']['empty']);
		unset($controls['actions']['insert_reusable_on_item_layer'], $controls['re_move_place']['insert_reusable_on_item_layer']);
		unset($controls['actions']['insert_nest_on_item_layer'], $controls['re_move_place']['insert_nest_on_item_layer']);
		unset($controls['actions']['insert_slideshow_on_item_layer'], $controls['re_move_place']['insert_slideshow_on_item_layer']);
		unset($controls['actions']['insert_reusable_on_layout_layer'], $controls['re_move_place']['replace_reusable_on_layout_layer']);
		unset($controls['actions']['insert_nest_on_layout_layer'], $controls['re_move_place']['replace_nest_on_layout_layer']);
		unset($controls['actions']['insert_slideshow_on_layout_layer'], $controls['re_move_place']['replace_slideshow_on_layout_layer']);
	}
	
	unset($controls['info']['vc']);
	unset($controls['info']['vc_warning']);
	

} else {
	
	#We've hidden the embed link for now to reduce clutter.
	#If we ever add some sort of "more options" toggle, we might add it back.
	#if (ze::in(ze::setting('xframe_options'), 'all', 'specific')) {
	#	//Set up the embed buttons
	#	$embedLink = ze\link::toItem(
	#		$cID, $cType, $fullPath = true, $request = '&zembedded=1&method_call=showSingleSlot&slotName='. $slotName,
	#		ze::$alias, $autoAddImportantRequests = false, $forceAliasInAdminMode = true);
	#
	#	$controls['info']['embed']['label'] .= '
	#		<a onclick="zenarioA.copyEmbedHTML(\''. ze\escape::js($embedLink). '\', \''. ze\escape::js($slotName). '\');">'.
	#			ze\admin::phrase('Copy iframe HTML').
	#		'</a>';
	#
	#} else {
	#	unset($controls['info']['embed']);
	#}
	
	
	//Get information from the plugin itself
	
	//Show the wrapping html, id and css class names for the slot
	if (isset(ze::$slotContents[$slotName]['class']) && !empty(ze::$slotContents[$slotName]['class'])) {
		$a = [];
		ze::$slotContents[$slotName]['class']->zAPIGetCachableVars($a);
		$framework = $a[0];
		$cssClass = $a[4];
		
		$controls['info']['slot_css_class']['label'] = ze\admin::phrase('CSS classes: <input class="zenario_class_name_preview" readonly="readonly" value="[[cssClass]]">', ['cssClass' => htmlspecialchars($cssClass)]);
	} else {
		unset($controls['info']['slot_css_class']);
	}
	
	ze\pluginAdm::fillSlotControlPluginInfo($moduleId, $instanceId, $isVersionControlled, $cID, $cType, $level, $isNest, $isSlideshow, $controls['info'], $controls['actions'], $controls['re_move_place']);

	
	
	$controls['actions']['settings']['page_modes'] = $settingsPageMode;
	$controls['actions']['framework_and_css']['page_modes'] = $cssFrameworkPageMode;
	
	if ($isVersionControlled && ze::$cVersion == ze::$adminVersion) {
		if (ze::$locked) {
			$controls['actions']['settings']['label'] = $controls['actions']['settings']['locked_label'];
		
		} elseif (!ze\content::isDraft(ze::$status)) {
			if (!ze\priv::check('_PRIV_CREATE_REVISION_DRAFT')) {
				$controls['actions']['settings']['label'] = $controls['actions']['settings']['label']['cant_make_draft'];
			
			} elseif (ze::$status == 'trashed') {
				$controls['actions']['settings']['label'] = $controls['actions']['settings']['label']['trashed'];
			
			} elseif (ze::$status == 'hidden') {
				$controls['actions']['settings']['label'] = $controls['actions']['settings']['label']['hidden'];
			} else {
				$controls['actions']['settings']['label'] = $controls['actions']['settings']['label']['normal'];
			}
		} else {
			$controls['actions']['settings']['label'] = $controls['actions']['settings']['label']['normal'];
		}
	
	} elseif (!$isVersionControlled && ze\priv::check('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
		$controls['actions']['settings']['label'] = $controls['actions']['settings']['label']['settings'];
	
	} elseif ($isVersionControlled || (!$isVersionControlled && ze\priv::check('_PRIV_VIEW_REUSABLE_PLUGIN'))) {
		$controls['actions']['settings']['label'] = $controls['actions']['settings']['label']['view_settings'];
	
	} else {
		unset($controls['actions']['settings']);
		unset($controls['actions']['framework_and_css']);
	}
	unset($controls['actions']['settings']['locked_label']);
	
	//Show options to convert the old nest plugins
	if ($isNest
	 && (($isVersionControlled && $canChange && $level == 1)
	  || (!$isVersionControlled && ze\priv::check('_PRIV_MANAGE_REUSABLE_PLUGIN')))
	 && ze\row::exists('nested_plugins', ['is_slide' => 0, 'instance_id' => $instanceId])) {
		$controls['actions']['convert_nest']['page_modes'] = $pageMode;
	} else {
		unset($controls['actions']['convert_nest']);
	}
	
	
	if (!$couldChange || $level == 2) {
		unset($controls['re_move_place']['move_on_item_layer']);
		unset($controls['re_move_place']['remove_from_item_layer']);
	}
	if (!$couldChange || $level == 1) {
		unset($controls['re_move_place']['move_on_layout_layer']);
		unset($controls['re_move_place']['remove_from_layout_layer']);
	}
	if (!$couldChange || $level == 1 || $isVersionControlled) {
		unset($controls['actions']['insert_reusable_on_item_layer'], $controls['re_move_place']['insert_reusable_on_item_layer']);
		unset($controls['actions']['insert_nest_on_item_layer'], $controls['re_move_place']['insert_nest_on_item_layer']);
		unset($controls['actions']['insert_slideshow_on_item_layer'], $controls['re_move_place']['insert_slideshow_on_item_layer']);
	}
	if (!$couldChange/* || $isVersionControlled*/) {
		unset($controls['re_move_place']['replace_reusable_on_item_layer']);
		unset($controls['re_move_place']['replace_nest_on_item_layer']);
		unset($controls['re_move_place']['replace_slideshow_on_item_layer']);
	}
	if (!$couldChange || ($level == 1 && !$overriddenPlugin) || $isVersionControlled || ze::$locked || !ze\priv::check('_PRIV_MANAGE_ITEM_SLOT', $cID, $cType)) {
		unset($controls['re_move_place']['hide_plugin']);
	}

	
	//Set the right CSS class around the slot and control box
	$controls['css_class'] .= ' zenario_level'. $level;
	
	//Flag where a plugin is overriding another plugin on the layout level
	if ($overriddenPlugin) {
		$controls['css_class'] .= ' zenario_overriddenPlugin';
	}
	if ($isVersionControlled) {
		$controls['css_class'] .= ' zenario_versionControlledPlugin';
	} else {
		$controls['css_class'] .= ' zenario_libraryPlugin';
	}
	
	if (isset(ze::$slotContents[$slotName]['class']) && !empty(ze::$slotContents[$slotName]['class'])) {
		
		$status = false;
		if (isset(ze::$slotContents[$slotName]['init'])) {
			$status = ze::$slotContents[$slotName]['init'];
		}
		
		if (!$status) {
			if (!empty(ze::$slotContents[$slotName]['error']) || $status === ZENARIO_401_NOT_LOGGED_IN || $status === ZENARIO_403_NO_PERMISSION) {
				$controls['css_class'] .= ' zenario_slotWithNoPermission';
		
			} else {
				$controls['css_class'] .= ' zenario_slotNotShownInVisitorMode';
			}
		}
		
		if (ze::$slotContents[$slotName]['class']->shownInMenuMode()) {
			$controls['css_class'] .= ' zenario_showSlotInMenuMode';
		} else {
			$controls['css_class'] .= ' zenario_hideSlotInMenuMode';
		}
		if ($isVersionControlled) {
			$controls['css_class'] .= ' zenario_wireframe';
		} else {
			$controls['css_class'] .= ' zenario_reusable';
		}
	}
	
	//Don't allow wireframe plugins to be replaced
	if ($isVersionControlled) {
		//unset($controls['re_move_place']['replace_reusable_on_item_layer']);
		//unset($controls['re_move_place']['replace_nest_on_item_layer']);
		//unset($controls['re_move_place']['replace_slideshow_on_item_layer']);
		unset($controls['actions']['insert_reusable_on_item_layer'], $controls['re_move_place']['insert_reusable_on_item_layer']);
		unset($controls['actions']['insert_nest_on_item_layer'], $controls['re_move_place']['insert_nest_on_item_layer']);
		unset($controls['actions']['insert_slideshow_on_item_layer'], $controls['re_move_place']['insert_slideshow_on_item_layer']);
		unset($controls['actions']['insert_reusable_on_layout_layer'], $controls['re_move_place']['replace_reusable_on_layout_layer']);
		unset($controls['actions']['insert_nest_on_layout_layer'], $controls['re_move_place']['replace_nest_on_layout_layer']);
		unset($controls['actions']['insert_slideshow_on_layout_layer'], $controls['re_move_place']['replace_slideshow_on_layout_layer']);
	}
}

if (!$couldEdit) {
	unset($controls['re_move_place']['replace_reusable_on_item_layer']);
	unset($controls['re_move_place']['replace_nest_on_item_layer']);
	unset($controls['re_move_place']['replace_slideshow_on_item_layer']);
	unset($controls['actions']['insert_reusable_on_item_layer'], $controls['re_move_place']['insert_reusable_on_item_layer']);
	unset($controls['actions']['insert_nest_on_item_layer'], $controls['re_move_place']['insert_nest_on_item_layer']);
	unset($controls['actions']['insert_slideshow_on_item_layer'], $controls['re_move_place']['insert_slideshow_on_item_layer']);
}


//If there is a hidden plugin at the layout layer, display info and some actions for that too
if ($overriddenPlugin) {
	$overriddenPluginIsNest = false;
	$overriddenPluginIsSlideshow = false;
	
	if ($overriddenPlugin['instance_id']) {
		switch (ze\module::className(ze\row::get('plugin_instances', 'module_id', $overriddenPlugin['instance_id']))) {
			case 'zenario_plugin_nest':
				$overriddenPluginIsNest = true;
				$overriddenPluginIsSlideshow = false;
				break;
			case 'zenario_slideshow':
				$overriddenPluginIsNest = true;
				$overriddenPluginIsSlideshow = true;
				break;
		}
	}
	
	$dummy = [];
	$overriddenIsVersionControlled = !$overriddenPlugin['instance_id'];
	ze\pluginAdm::fillSlotControlPluginInfo($overriddenPlugin['module_id'], $overriddenPlugin['instance_id'], $overriddenIsVersionControlled, $cID, $cType, 2, $overriddenPluginIsNest, $overriddenPluginIsSlideshow, $controls['overridden_info'], $controls['overridden_actions'], $dummy);
	
	if (!$couldChange) {
		unset($controls['overridden_actions']['show_plugin']);
	}
	
	if (!ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT')) {
		unset($controls['overridden_actions']['remove_from_layout_layer']);
	}
	
	
	//Don't allow wireframe plugins to be replaced
	if ($overriddenIsVersionControlled) {
		unset($controls['actions']['insert_reusable_on_layout_layer'], $controls['re_move_place']['replace_reusable_on_layout_layer']);
		unset($controls['actions']['insert_nest_on_layout_layer'], $controls['re_move_place']['replace_nest_on_layout_layer']);
		unset($controls['actions']['insert_slideshow_on_layout_layer'], $controls['re_move_place']['replace_slideshow_on_layout_layer']);
	}
	
	
} else {
	unset($controls['overridden_info']);
	unset($controls['overridden_actions']);
}



if (ze::$locked) {
	foreach ($controls as &$group) {
		if (is_array($group)) {
			foreach ($group as &$button) {
				if (is_array($button)
				 && isset($button['locked_label'])) {
					$button['label'] = $button['locked_label'];
				}
			}
		}
	}
} else {
	unset($controls['info']['locked']);
}