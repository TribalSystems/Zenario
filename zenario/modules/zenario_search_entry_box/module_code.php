<?php
/*
 * Copyright (c) 2020, Tribal Limited
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

class zenario_search_entry_box extends zenario_search_results {
	
	public function init() {
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		return true;
	}
	
	function showSlot() {
		$this->sections = [];
		$this->mergeFields = [];
		
		$cID = $cType = $state = false;
		if ($this->setting('use_specific_search_results_page') && $this->getCIDAndCTypeFromSetting($cID, $cType, 'specific_search_results_page')) {
		
		} else {
			ze\content::pluginPage($cID, $cType, $state, 'zenario_search_results');
		}
		
		if (!$cID) {
			if (ze\priv::check()) {
				echo
					'<p class="error">',
						ze\admin::phrase('To show a search entry box, you must either publish the <em>Search</em> special page, or enter a search page in the plugin settings.'),
					'</p>';
			}
			
			return;
		}
		
		if ($cID == $this->cID && $cType == $this->cType) {
			$cID = $cType = false;
		}
		
		if ($this->setting('search_label')) {
			$this->sections['Search_Label'] = true;
		}
		
		if ($this->setting('search_placeholder')) {
			$this->sections['Placeholder'] = true;
			$this->sections['Placeholder_Phrase'] = $this->setting('search_placeholder_phrase');
		}
		
		$this->drawSearchBox($cID, $cType);
		
		$this->framework('Search_Box', $this->mergeFields, $this->sections);

	}
	
	function launchAJAXSearch() {
		$this->mergeFields['Search_Submit'] = "
			var searchSlot;
			if ((window.zenario_search_results && (searchSlot = zenario_search_results.slotName()))
			 || (window.zenario_search_results_pro && (searchSlot = zenario_search_results_pro.slotName()))) {
				zenario.refreshPluginSlot(
					searchSlot,
					'lookup',
					'&searchString=' + encodeURIComponent(ze::get('". $this->mergeFields['Search_Field_ID']. "').value));
				return false;
			}";
	}
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				if ($box['tabs']['first_tab']['fields']['search_placeholder'] == true
					&& empty($box['tabs']['first_tab']['fields']['search_placeholder_phrase']['value'])) {
					$box['tabs']['first_tab']['fields']['search_placeholder_phrase']['value'] = 'Search the site';
				}

				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
	}
}
