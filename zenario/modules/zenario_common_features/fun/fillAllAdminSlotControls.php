<?php
/*
 * Copyright (c) 2024, Tribal Limited
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
$slot = ze::$slotContents[$slotName];
$isNest = $slot->isNest();
$isSlideshow = $slot->isSlideshow();


//Check to see if an entry on the item layer is overwriting an entry on a layer above
$overriddenSlot = false;
if ($level == 1) {
	$overriddenSlot = $slot->overriddenSlot();

	//Treat the case of hidden (item layer) and empty (layout layer) as just empty
	if (!$overriddenSlot && !$moduleId) {
		$level = 0;
	}
}

$mrg = ['slotName' => $slotName];
$controls['info']['slot_name_in_edit_mode']['label'] =
$controls['info']['slot_name_in_layout_mode']['label'] = ze\admin::phrase('<span>[[slotName]]</span>', $mrg);

switch ($level) {
	case 1:
		$pageMode = ['edit' => true];
		
		$controls['info']['slot_name_in_layout_mode']['label'] = ze\admin::phrase('<span>[[slotName]]</span> is empty', $mrg);
		
		//Show the "in this slot" blurb when looking at the wrong layer and it needs to be made clear
		$controls['info']['in_this_slot']['label'] = ze\admin::phrase('On this content item this slot contains:');
		$controls['info']['in_this_slot']['page_modes'] = ['layout' => true];
		unset($controls['info']['in_this_slot']['hidden']);
		
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
		
		$controls['info']['slot_name_in_edit_mode']['label'] = ze\admin::phrase('<span>[[slotName]]</span>', $mrg);
		$controls['info']['slot_name_in_layout_mode']['label'] = ze\admin::phrase('<span>[[slotName]]</span> contains:', $mrg);
		
		//Only show the "in this slot" blurb when looking at the wrong layer and it needs to be made clear
		$controls['info']['in_this_slot']['label'] = ze\admin::phrase('On this layout this slot contains:');
		$controls['info']['in_this_slot']['page_modes'] = ['edit' => true];
		unset($controls['info']['in_this_slot']['hidden']);
		
		$couldChange = $canChange = ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT');
		
		break;
	
	case 3:
		//Also show site-wide slots on the layout tab.
		//(That's slightly miscategorising them, but adding a new admin toolbar tab for them would cause more clutter than we'd like.)
		$pageMode = ['layout' => true];
		
		$controls['info']['slot_name_in_edit_mode']['label'] = ze\admin::phrase('<span>[[slotName]]</span>', $mrg);
		$controls['info']['slot_name_in_layout_mode']['label'] = ze\admin::phrase('<span>[[slotName]]</span> contains:', $mrg);
		
		$couldChange = $canChange = ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT');
		
		break;
	
	default:
		if (empty($controls['meta_info']['is_sitewide'])) {
			$controls['info']['slot_name_in_layout_mode']['label'] = ze\admin::phrase('<span>[[slotName]]</span> is empty on this layout', $mrg);
		} else {
			$controls['info']['slot_name_in_layout_mode']['label'] = ze\admin::phrase('<span>[[slotName]]</span> is empty', $mrg);
		}
		$controls['info']['slot_name_in_edit_mode']['label'] = ze\admin::phrase('<span>[[slotName]]</span>', $mrg);
		
		$couldChange = $canChange = false;
		break;
}

if ($isVersionControlled) {
	$settingsPageMode = ['edit' => true];
} else {
	$settingsPageMode = $pageMode;
}



//Format options if the slot is empty
if (!$moduleId) {
	$pageMode = ['edit' => true, 'layout' => true];
	
	if (!$level) {
		//Empty slots
		unset($controls['re_move_place']['replace_reusable_on_item_layer']);
		unset($controls['re_move_place']['replace_nest_on_item_layer']);
		unset($controls['re_move_place']['replace_slideshow_on_item_layer']);
	
		//On the Layout Layer, add an option to insert a Wireframe version of each Plugin
		//that is flagged as uses wireframe.
		if (empty($controls['meta_info']['is_sitewide']) && ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT')) {
			$i = 0;
			foreach (ze\row::getAssocs(
				'modules',
				['id', 'display_name'],
				['status' => ['module_running', 'module_is_abstract'], 'is_pluggable' => 1, 'can_be_version_controlled' => 1],
				'display_name'
			) as $module) {
				$controls['actions'][] = [
					'ord' => ++$i,
					'label' => ze\admin::phrase('Insert a version-controlled [[display_name]]', $module),
					'page_modes' => ['layout' => true],
					'onclick' => "zenarioA.addNewWireframePlugin(this, '". ze\escape::js($slotName). "', ". (int) $module['id']. ");"
				];
			}
		}
	} else {
		//Opaque slots
		$controls['info']['slot_name_in_edit_mode']['label'] =
		$controls['info']['slot_name_in_layout_mode']['label'] = ze\admin::phrase('<span>[[slotName]]</span> set to show nothing on this content item', $mrg);
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
	ze\pluginAdm::fillSlotControlPluginInfo($slot, $cID, $cType, $level, $controls['info'], $controls['actions'], $controls['re_move_place']);
	
	$controls['actions']['settings']['page_modes'] = $settingsPageMode;
	
	if ($isVersionControlled && ze::$cVersion == ze::$adminVersion) {
		if (ze::$locked) {
			$controls['actions']['settings']['label'] = $controls['actions']['settings']['locked_label'];
		
		} elseif (!ze\content::isDraft(ze::$status)) {
			if (!ze\priv::check('_PRIV_EDIT_DRAFT')) {
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
	
	//Show options to switch to the correct level to change the settings
	if (!$canChange) {
		unset($controls['actions']['switch_to_edit']);
		unset($controls['actions']['switch_to_edit_settings']);
		unset($controls['actions']['switch_to_layout']);
	
	} elseif ($isVersionControlled && $level > 1) {
		unset($controls['actions']['switch_to_edit_settings']);
		unset($controls['actions']['switch_to_layout']);
	
	} elseif ($level > 1) {
		unset($controls['actions']['switch_to_edit']);
		unset($controls['actions']['switch_to_edit_settings']);
	
	} elseif ($level == 1) {
		unset($controls['actions']['switch_to_edit']);
		unset($controls['actions']['switch_to_layout']);
	
	} else {
		unset($controls['actions']['switch_to_edit']);
		unset($controls['actions']['switch_to_edit_settings']);
		unset($controls['actions']['switch_to_layout']);
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
	if (!$couldChange || ($level == 1 && !$overriddenSlot) || ze::$locked || !ze\priv::check('_PRIV_MANAGE_ITEM_SLOT', $cID, $cType)) {
		unset($controls['re_move_place']['hide_plugin']);
	}

	
	//Set the right CSS class around the slot and control box
	$controls['css_class'] .= ' zenario_level'. $level;
	
	//Flag where a plugin is overriding another plugin on the layout level
	if ($overriddenSlot) {
		$controls['css_class'] .= ' zenario_overriddenPlugin';
	}
	if ($isVersionControlled) {
		$controls['css_class'] .= ' zenario_versionControlledPlugin';
	} else {
		$controls['css_class'] .= ' zenario_libraryPlugin';
	}
	
	if ($slot->class()) {
		
		$status = $slot->init();
		
		if (!$status) {
			if (!empty($slot->error()) || $status === ZENARIO_401_NOT_LOGGED_IN || $status === ZENARIO_403_NO_PERMISSION) {
				$controls['css_class'] .= ' zenario_slotWithNoPermission';
		
			} else {
				$controls['css_class'] .= ' zenario_slotNotShownInVisitorMode';
			}
		}
		
		if ($slot->shownInMenuMode()) {
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
if ($overriddenSlot) {
	$dummy = [];
	ze\pluginAdm::fillSlotControlPluginInfo($overriddenSlot, $cID, $cType, 2, $controls['overridden_info'], $controls['overridden_actions'], $dummy);
	
	if (!$couldChange) {
		unset($controls['overridden_actions']['show_plugin']);
	}
	
	if (!ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT')) {
		unset($controls['overridden_actions']['remove_from_layout_layer']);
	}
	
	
	//Don't allow wireframe plugins to be replaced
	if ($overriddenSlot->isVersionControlled()) {
		unset($controls['actions']['insert_reusable_on_layout_layer'], $controls['re_move_place']['replace_reusable_on_layout_layer']);
		unset($controls['actions']['insert_nest_on_layout_layer'], $controls['re_move_place']['replace_nest_on_layout_layer']);
		unset($controls['actions']['insert_slideshow_on_layout_layer'], $controls['re_move_place']['replace_slideshow_on_layout_layer']);
	}
	
	//It's a bit hard to have meaningful text on the slot name on the top in layout mode here,
	//so we'll just change it back to something generic.
	$controls['info']['slot_name_in_layout_mode']['label'] = ze\admin::phrase('<span>[[slotName]]</span>', $mrg);
	
	
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



//Attempt to show some useful messages explaining why you can't edit something
if (ze::$locked) {
	unset($controls['no_perms']['cant_design']);
} else {
	if (!ze\priv::check('_PRIV_MANAGE_ITEM_SLOT')) {
		$controls['no_perms']['cant_edit']['label'] = ze\admin::phrase("You don't have designer permissions.");
	} else {
		unset($controls['no_perms']['cant_design']);
	}
}
