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

class zenario_location_viewer extends module_base_class {
	
	private $data = array();

	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = true);
		
		$locationId = false;
		$mode = $this->setting('location_source_mode');
		// Get location ID
		switch ($mode) {
			case 'location_from_selector':
				if (($this->cType=="event")  
					&& (inc('zenario_ctype_event')) 
					&& ($contentItem = zenario_ctype_event::getEventDetails($this->cID,$this->cVersion))
				) {
					$locationId = $contentItem['location_id'];
				} else {
					$locationId = zenario_location_manager::getLocationIdFromContentItem($this->cID,$this->cType);
				}
				break;
			case 'location_from_organizer':
				if (!($locationId = $this->setting('location')) && adminId()) {
					$this->data['no_location_selected'] = true;
					return true;
				}
				break;
			case 'location_from_url':
				$this->registerGetRequest('l_id');
				if (!$locationId = get('l_id')) {
					$this->data['url_not_complete'] = true;
					return true;
				}
				if ($this->setting('location_user')) {
					if (!(checkRowExists(ZENARIO_ORGANIZATION_MANAGER_PREFIX. 'user_role_location_link', array('user_id' => userId(), 'location_id' => $locationId)))){
						$this->data['access_denied'] = true;
						return true;
					}
				}
				break;
		}
		if (!$locationId || !($locationDetails = zenario_location_manager::getLocationDetails($locationId))) {
			return false;
		}
		
		if ($this->setting('show_title')) {
			$this->data['show_title'] = true;
		}
		
		if ($this->setting('show_details')) {
			$this->data['show_details'] = true;
			if (!empty($locationDetails['country_id']) && inc('zenario_country_manager')) {
				if ($country = zenario_country_manager::getCountryNamesInCurrentVisitorLanguage("active", $locationDetails['country_id'])) {
					$locationDetails['country']= arrayKey($country,"COUNTRY_" . $locationDetails['country_id']);
				}
				
				if (!empty($locationDetails['region_id'])) {
					if ($region = zenario_country_manager::getRegionNamesInCurrentVisitorLanguage("active", $locationDetails['country_id'], $locationDetails['region_id'])) {
						foreach ($region as $key => $value) {
							$locationDetails['region'] = zenario_country_manager::adminPhrase(session('user_lang'),$value);
							break;
						}
					}
				}
			}
			
			if ($this->setting('show_image')) {
				$this->data['show_image'] = true;
				$imageId =  getRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_images', 'image_id', 
					array('location_id' => $locationDetails['id'], 'sticky_flag' => 1));
				$locationDetails['image_width'] = 
				$locationDetails['image_height'] = 
				$locationDetails['image_url'] = false;
				imageLink(
					$locationDetails['image_width'], 
					$locationDetails['image_height'], 
					$locationDetails['image_url'], 
					$imageId, 
					ifNull((int) $this->setting('max_location_image_width'), 120),
					ifNull((int) $this->setting('max_location_image_height'), 120)
				);
			}
		}
		
		if ($this->setting('show_map')) {
			$this->data['show_map'] = true;
			$this->data['map_width'] = (int)$this->setting('map_width');
			$this->data['map_height'] = (int)$this->setting('map_height');
			
			if (!$locationDetails['map_zoom']) {
				$locationDetails['map_zoom'] = 2;
			}
			
			$this->callScript(
				'zenario_location_viewer', 
				'initMap', 
				'object_in_' . $this->containerId,
				arrayKey($locationDetails,'latitude'),
				arrayKey($locationDetails,'longitude'),
				arrayKey($locationDetails,'map_zoom')
			);
		}
		$this->data['location'] = $locationDetails;
		return true;
	}
	
	function addToPageHead() {
		if (!defined('ZENARIO_GOOGLE_MAP_ON_PAGE')) {
			define('ZENARIO_GOOGLE_MAP_ON_PAGE', true);
			echo '<script src="https://maps.google.com/maps/api/js?sensor=false" type="text/javascript"></script>';
		}
	}
	
	function showSlot() {
		$this->twigFramework($this->data);
    }
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case "plugin_settings":
				if (!$values['first_tab/max_location_image_width']) {
					$box['tabs']['first_tab']['errors'][] = adminPhrase('Please enter a maximum image width.');
				}

				if (!$values['first_tab/max_location_image_height']) {
					$box['tabs']['first_tab']['errors'][] = adminPhrase('Please enter a maximum image height.');
				}
				
				if (!$values['first_tab/show_title'] && !$values['first_tab/show_details'] && !$values['first_tab/show_map']) {
					$box['tabs']['first_tab']['errors'][] = adminPhrase('You must select at least one section to show.');
				}
			
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				
				$fields['location_user']['hidden'] = 
					(!inc('zenario_organization_manager')) || ($values['location_source_mode'] != 'location_from_url');
				
				$fields['map_width']['hidden'] = 
				$fields['map_height']['hidden'] = 
					!$values['show_map'];
				$fields['max_location_image_width']['hidden'] = 
				$fields['max_location_image_height']['hidden'] = 
					!$values['show_image'];
					
				$fields['location']['hidden'] = 
					$values['location_source_mode'] != 'location_from_organizer';
					
				$fields['show_image']['hidden'] = !$values['show_details'];
				
				
				break;
		}
	}
}

