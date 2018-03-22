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



//Write buttons to the page for managing the Plugin. CSS is used so that the edit settings button is invisible when the slot is empty

if ($this->eggId && isset($this->parentNest)) {
	
	$isSlideshow = false !== strpos($this->parentNest->moduleClassName, 'slide');
	
	if ($isSlideshow) {
		$buttonName = 'plugins_in_slideshow';
	} else {
		$buttonName = 'plugins_in_nest';
	}
	
	$organizerLink = 'zenario/admin/organizer.php?fromCID='. ze::$cID. '&fromCType='. ze::$cType. '#';
	
	$nestPath = 'zenario__modules/panels/modules/item//'. (int) $this->parentNest->moduleId. '//item_buttons/'. $buttonName. '//'. $this->instanceId. '//';

	
	
	//Show a button to edit eggs in nests
	echo '
		<div id="', $this->containerId, '-control_box" class="zenario_slotAdminControlBox zenario_slotEggControlBox">';
		
		if ($this->eggId === -1) {
			//Special case: add an "edit plugin" button for the breadcrumbs that are auto-added
			echo '
			<a
				href="', $organizerLink, $nestPath, '~.plugin_settings~tbreadcrumbs~k', htmlspecialchars(json_encode(['instanceId' => $this->instanceId])), '"
				class="zenario_slotButton zenario_editNestedPlugin"
				id="', $this->containerId, '-egg"
				onclick="return zenarioA.pluginSlotEditSettings(this, \'', $this->slotName, '\', false, {}, \'breadcrumbs\');"
			><span></span></a>
			<a
				href="', $organizerLink, $nestPath, '"
				class="zenario_slotButton zenario_nestedPluginOptions"
				id="', $this->containerId, '-egg-options"
				onclick="', "
					return zenarioAT.organizerQuick(
						'", $nestPath, "',
						'zenario__modules/panels/nested_plugins',
						false,
						'", ze\escape::js($this->slotName), "',
						false,
						". ze\ring::engToBoolean($this->isVersionControlled). ",
						this);", '"
			><span></span></a>';
		
		} else {
			echo '
			<a
				href="', $organizerLink, $nestPath, $this->eggId, '~.plugin_settings~k', htmlspecialchars(json_encode(['eggId' => $this->eggId])), '"
				class="zenario_slotButton zenario_editNestedPlugin"
				id="', $this->containerId, '-egg"
				onclick="return zenarioA.pluginSlotEditSettings(this, \'', $this->slotName, '\', false, {eggId: ', (int) $this->eggId, '});"
			><span></span></a>
			<a
				href="', $organizerLink, $nestPath, $this->eggId, '"
				class="zenario_slotButton zenario_nestedPluginOptions"
				id="', $this->containerId, '-egg-options"
				onclick="', "
					return zenarioAT.organizerQuick(
						'", $nestPath, $this->eggId, "',
						'zenario__modules/panels/nested_plugins',
						false,
						'", ze\escape::js($this->slotName), "',
						false,
						". ze\ring::engToBoolean($this->isVersionControlled). ",
						this);", '"
			><span></span></a>';
	}
	
} else {
	//Show buttons for normal slots
	echo '
		<div id="', $this->containerId, '-control_box" class="zenario_slotAdminControlBox zenario_slotPluginControlBox">
			<a
				class="zenario_slotButton zenario_openSlotControls"
				id="', $this->containerId, '-options"
				onclick="return zenarioA.openSlotControls(this, this, \'', $this->slotName, '\');"
			><span></span></a>';
	
	if (\ze\priv::check('_PRIV_MANAGE_ITEM_SLOT') || \ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT')) {
		echo '
			<a
				class="zenario_slotButton zenario_swapPlugin"
				title="', htmlspecialchars(\ze\admin::phrase('Click to swap with the Plugin in the Slot "[[slotname]]"', ['slotname' => $this->slotName])), '"
				onclick="return zenarioA.doMovePlugin(this, \''. $this->slotName. '\');"
			></a>
			<a
				class="zenario_slotButton zenario_movePlugin"
				title="', htmlspecialchars(\ze\admin::phrase('Click to move into the empty Slot "[[slotname]]"', ['slotname' => $this->slotName])), '"
				onclick="return zenarioA.doMovePlugin(this, \''. $this->slotName. '\');"
			></a>
			<a
				class="zenario_slotButton zenario_cancelMovePlugin"
				title="', \ze\admin::phrase('Click to cancel the move'), '"
				onclick="return zenarioA.cancelMovePlugin(this);"
			></a>';
	}
}

echo '
		</div>';