<?php
/*
 * Copyright (c) 2016, Tribal Limited
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



/**
 * This class contains core plugin functions that the CMS calls in order to display modules
 * They're not really part of the plugin API;
 * plugin developers don't need to be aware of them and should never call them from within a plugin.
 */

class module_base_class extends zenario_api {
	
	
	  /////////////////////////////////
	 //  Methods called for Plugins  //
	/////////////////////////////////
	
	public function addToPageHead() {
		
		//...your PHP code...//
	}

	public function addToPageFoot() {
		
		//...your PHP code...//
	}

	public function init() {
		
		//...your PHP code...//
		
		return true;
	}

	public function showSlot() {
		
		//...your PHP code...//
	}
	
	public function showLayoutPreview() {
		if ($this->instanceId) {
			$this->showSlot();
		} elseif (!$this->moduleId) {
			echo adminPhrase('[Empty Slot]');
		} else {
			echo adminPhrase('[[[module]]]',
				array('module' => htmlspecialchars(getModuleDisplayNameByClassName($this->moduleClassName))));
		}
	}
	
	
	
	  /////////////////////////////////////////////////
	 //  Methods called for Plugins when linked to  //
	/////////////////////////////////////////////////

	public function handlePluginAJAX() {
		
		//...your PHP code...//
	}

	public function showRSS() {
		
		//...your PHP code...//
	}

	public function showFloatingBox() {
		
		//...your PHP code...//
	}
	
	
	
	
	  /////////////////////////////////////
	 //  Methods called when linked to  //
	/////////////////////////////////////
	
	public function handleAJAX() {
		
		//...your PHP code...//
	}

	public function showFile() {
		
		//...your PHP code...//
	}

	public function showImage() {
		
		//...your PHP code...//
	}

	public function showStandalonePage() {
		
		//...your PHP code...//
	}
	
	
	
	
	  ///////////////////////////////////////////
	 //  Methods called by the Admin Toolbar  //
	///////////////////////////////////////////
	
	
	public function fillAdminToolbar(&$adminToolbar, $cID, $cType, $cVersion) {
		
		//...your PHP code...//
	}
	
	public function handleAdminToolbarAJAX($cID, $cType, $cVersion, $ids) {
		
		//...your PHP code...//
	}
	
	
	
	
	  /////////////////////////////////
	 //  Methods called by Wizards  //
	/////////////////////////////////
	
	
	public function fillWizard($path, &$box, &$fields, &$values) {
		
		//...your PHP code...//
	}
	
	public function formatWizard($path, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
	
	
	
	
	  /////////////////////////////////////
	 //  Methods called by Admin Boxes  //
	/////////////////////////////////////
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//...your PHP code...//
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		//...your PHP code...//
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
	
	
	
	
	  ///////////////////////////////////
	 //  Methods called by Organizer  //
	///////////////////////////////////
	
	
	
	public function fillOrganizerNav(&$nav) {
		
		//...your PHP code...//
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		//...your PHP code...//
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		//...your PHP code...//
	}

	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		//...your PHP code...//
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
		//...your PHP code...//
	}
	
	//depreated?
	/**
	 * Gives the module the option to rewrite the http headers sent to the browser,
	 * the standard headers have already been set before call this function.
	 * @param unknown $path
	 * @param unknown $refinerName
	 * @param unknown $refinerId
	 */
	public function rewriteHttpHeaderCSV($path, $refinerName, $refinerId) {

		//...your PHP code...//
	}
	
	//depreated
	public function lineStorekeeperCSV($path, &$columns, $refinerName, $refinerId) {
		
		//...your PHP code...//
	}
	
	//depreated
	public function formatStorekeeperCSV($path, &$item, $refinerName, $refinerId) {
		
		//...your PHP code...//
	}
	
	
	
	
	  //////////////////////////////////////////
	 //  Other Methods called in Admin Mode  //
	//////////////////////////////////////////
	
	
	public function fillAdminSlotControls(&$controls) {
		//...your PHP code...//
	}
	
	public function fillAllAdminSlotControls(
		&$controls,
		$cID, $cType, $cVersion,
		$slotName, $containerId,
		$level, $moduleId, $instanceId, $isVersionControlled
	) {
		//...your PHP code...//
	}

}