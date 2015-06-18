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
	var $locationImage = false;
	var $locationDetails = false;
	var $noLocationFound = false;
	var $noLocationUrlIdPassed= false;
	var $mergeArray = array();

	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = true);
		
		$this->forcePageReload();
		if ($this->setting('location_source_mode') == 'location_from_selector') {
			$locationId = $this->getLocationId();
		} else {
			$this->registerGetRequest('l_id');
			
			if(!($locationId = (int)get('l_id'))) {
				$this->noLocationUrlIdPassed= true;
				return true;
			}
		}
		$this->locationDetails = zenario_location_manager::getLocationDetails($locationId);
 		if ($this->locationDetails) {
 			$this->locationImage = getRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_images', 'image_id', array('location_id' => $this->locationDetails['id'], 'sticky_flag' => 1));
			
			if ($this->setting('location_source_mode') == 'location_from_selector') {
				$this->callScript('zenario_location_viewer', 'initMap', 'object_in_' . $this->containerId,arrayKey($this->locationDetails,'latitude'),arrayKey($this->locationDetails,'longitude'),arrayKey($this->locationDetails,'map_zoom'));
			}
			return true;
		} else {
			//No location found with passed id
			$this->noLocationFound = true;
			return true;
		}
	}

	public function getLocationId() {
		$locationId = false;
	
		if (!$locationId = (int)$this->setting('location')){
			//if defined event gets precedence over contentItem 
			if (($this->cType=="event")  && (inc('zenario_ctype_event')) && ($contentItem = zenario_ctype_event::getEventDetails($this->cID,$this->cVersion))) {
					return $contentItem['location_id'];
			}
			$locationId = zenario_location_manager::getLocationIdFromContentItem($this->cID,$this->cType);
		}
				
		return $locationId;
	}
	
	function addToPageHead() {
		if (!defined('ZENARIO_GOOGLE_MAP_ON_PAGE')) {
			define('ZENARIO_GOOGLE_MAP_ON_PAGE', true);
			echo '
				<script src="https://maps.google.com/maps/api/js?sensor=false" type="text/javascript"></script>';
		}
	}
	
    function showSlot() {
     	$sectionArray = array();
     	$contactArray = array();
     	
		if ($this->locationDetails){
			if ($this->setting('location_source_mode') == 'location_from_selector') {
				$output = '<div id="object_in_' . $this->containerId.'" style="height: ' . $this->setting("map_height") . 'px; width: ' . $this->setting("map_width") . 'px;"></div>';
	
				$this->mergeArray['cID'] = $this->cID;
		
				if (!empty($this->locationDetails['description'])) {
					if ($this->locationDetails['equiv_id'] && $this->locationDetails['content_type']) {
						langEquivalentItem($this->locationDetails['equiv_id'], $this->locationDetails['content_type']);
						$locationUrl = linkToItem($this->locationDetails['equiv_id'], $this->locationDetails['content_type']);
						$this->mergeArray['Link_Start'] = "<a href='" . $locationUrl . "'>";
						$this->mergeArray['Link_End'] = "</a>";
					}
					$this->mergeArray['Description'] = htmlspecialchars($this->locationDetails['description']);
				}
	
				if (!empty($this->locationDetails['address1'])) {
					$sectionArray['Address1'] = true;
					$this->mergeArray['Address1'] = htmlspecialchars($this->locationDetails['address1']);
				}
	
				if (!empty($this->locationDetails['address2'])) {
					$sectionArray['Address2'] = true;
					$this->mergeArray['Address2'] = htmlspecialchars($this->locationDetails['address2']);
				}
	
				if (!empty($this->locationDetails['locality'])) {
					$sectionArray['Locality'] = true;
					$this->mergeArray['Locality'] = htmlspecialchars($this->locationDetails['locality']);
				}
	
				if (!empty($this->locationDetails['city'])) {
					$sectionArray['City'] = true;
					$this->mergeArray['City'] = htmlspecialchars($this->locationDetails['city']);
				}
	
				if (!empty($this->locationDetails['state'])) {
					$sectionArray['State'] = true;
					$this->mergeArray['State'] = htmlspecialchars($this->locationDetails['state']);
				}
	
				if (!empty($this->locationDetails['postcode'])) {
					$sectionArray['Postcode'] = true;
					$this->mergeArray['Postcode'] = htmlspecialchars($this->locationDetails['postcode']);
				}
	
				if (!empty($this->locationDetails['country_id'])) {
					if (inc("zenario_country_manager")) {
						if ($country = zenario_country_manager::getCountryNamesInCurrentVisitorLanguage("active",$this->locationDetails['country_id'])) {
							$sectionArray['Country'] = true;
							$this->mergeArray['Country'] = arrayKey($country,"COUNTRY_" . $this->locationDetails['country_id']);
						}
					}
				}
	
				if (!empty($this->locationDetails['phone']) 
						|| !empty($this->locationDetails['fax']) 
							|| !empty($this->locationDetails['email']) 
								|| (!empty($this->locationDetails['equiv_id']) && !empty($this->locationDetails['content_type']))
									|| !empty($this->locationDetails['url'])) {
					$sectionArray['Contact'] = true;
				}

				if (!empty($this->locationDetails['phone'])) {
					$sectionArray['Phone'] = true;
					$this->mergeArray['Phone'] = htmlspecialchars($this->locationDetails['phone']);
				}
	
				if (!empty($this->locationDetails['fax'])) {
					$sectionArray['Fax'] = true;
					$this->mergeArray['Fax'] = htmlspecialchars($this->locationDetails['fax']);
				}
	
				if (!empty($this->locationDetails['email'])) {
					$sectionArray['Email'] = true;
					$this->mergeArray['Email'] = htmlspecialchars($this->locationDetails['email']);
				}

				if (!empty($this->locationDetails['equiv_id']) && !empty($this->locationDetails['content_type'])) {
					$sectionArray['More Info'] = true;
					$cID  = $this->locationDetails['equiv_id'];
					$cType = $this->locationDetails['content_type'];
					langEquivalentItem($cID, $cType);
					$this->mergeArray['More Info'] = $this->linkToItem($cID, $cType, true);
				}
	
				if (!empty($this->locationDetails['url'])) {
					$sectionArray['Url'] = true;
					$this->mergeArray['Url'] = $this->locationDetails['url'];
				}
	
				if (!empty($this->locationDetails['country_id']) && !empty($this->locationDetails['region_id'])) {
					if (inc("zenario_country_manager")) {
						if ($region = zenario_country_manager::getRegionNamesInCurrentVisitorLanguage("active",$this->locationDetails['country_id'],$this->locationDetails['region_id'])) {
							foreach ($region as $key => $value) {
								$sectionArray['Region'] = true;
								$this->mergeArray['Region'] = zenario_country_manager::adminPhrase(session('user_lang'),$value);
							}
						}
					}
				}
		
				$this->mergeArray['GoogleMap'] = $output;
			
				if ($this->locationImage) {
					$sectionArray['Location_Image'] = true;
					
					$this->mergeArray['Location_Image'] =
					$this->mergeArray['Location_Image_Width'] =
					$this->mergeArray['Location_Image_Height'] = '';
					
					
					imageLink($this->mergeArray['Location_Image_Width'], $this->mergeArray['Location_Image_Height'], $this->mergeArray['Location_Image'], $this->locationImage, ifNull((int) $this->setting('max_location_image_width'), 120),ifNull((int) $this->setting('max_location_image_height'), 120));
				}
				$this->framework('Outer',$this->mergeArray,$sectionArray);
			} else {
				$userAccessDenied = false;
				if ($this->setting('location_user')) {
					if (!(checkRowExists(ZENARIO_ORGANIZATION_MANAGER_PREFIX. 'user_role_location_link', array('user_id' => userId(), 'location_id' => get('l_id'))))){
						$userAccessDenied = true;
					}
				}
				if (!($userAccessDenied)) {
					if ($this->setting('location_plugin_mode') == 'location_title') {
						$section = 'location_title';
						$this->mergeArray['location_title'] = htmlspecialchars($this->locationDetails['description']);
					} elseif ($this->setting('location_plugin_mode') == 'location_info') {
						if (inc('zenario_company_locations_manager')) {
							$company_id = getRow(ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX. 'company_location_link', 'company_id', array('location_id' => get('l_id')));
							$company_name = getRow(ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX. 'companies', 'company_name', array('id' => $company_id));
							$this->mergeArray['parent_company'] = $company_name;
							$section = 'parent_company';
							$this->framework($section,$this->mergeArray);
						}
						$section = 'location_info';
						$this->mergeArray['description'] = rtrim(rtrim($this->locationDetails['description']), ',');
						$this->mergeArray['address1'] = rtrim(rtrim($this->locationDetails['address1']), ',');
						if($this->mergeArray['description'] && $this->mergeArray['address1']) {$this->mergeArray['address1'] = ', ' . $this->mergeArray['address1'];}
						$this->mergeArray['address2'] = rtrim(rtrim($this->locationDetails['address2']), ',');
						if($this->mergeArray['address2']) {$this->mergeArray['address2'] = ', ' . $this->mergeArray['address2'];}
						$this->mergeArray['locality'] = rtrim(rtrim($this->locationDetails['locality']), ',');
						if($this->mergeArray['locality']) {$this->mergeArray['locality'] = ', ' . $this->mergeArray['locality'];}
						$this->mergeArray['city'] = rtrim(rtrim($this->locationDetails['city']), ',');
						if($this->mergeArray['city']) {$this->mergeArray['city'] = ', ' . $this->mergeArray['city'];}
						$this->mergeArray['state'] = rtrim(rtrim($this->locationDetails['state']), ',');
						if($this->mergeArray['state']) {$this->mergeArray['state'] = ', ' . $this->mergeArray['state'];}
						$this->mergeArray['postcode'] = rtrim(rtrim($this->locationDetails['postcode']), ',');
						if($this->mergeArray['postcode']) {$this->mergeArray['postcode'] = ', ' . $this->mergeArray['postcode'];}
						//$this->mergeArray['equipment_count'] = $this->locationDetails['postcode'];
					} elseif ($this->setting('location_source_mode') == 'location_from_selector' && $this->cType == 'event') {
						$section = 'Outer';
						$this->mergeArray['Description'] = $this->locationDetails['description'];
						
						$sectionArray['Address1'] = true;
						$sectionArray['Address2'] = true;
						$sectionArray['Locality'] = true;
						$sectionArray['City'] = true;
						$sectionArray['State'] = true;
						$sectionArray['Postcode'] = true;
						
						$this->mergeArray['Address1'] = $this->locationDetails['address1'];
						$this->mergeArray['Address2'] = $this->locationDetails['address2'];
						$this->mergeArray['Locality'] = $this->locationDetails['locality'];
						$this->mergeArray['City'] = $this->locationDetails['city'];
						$this->mergeArray['State'] = $this->locationDetails['state'];
						$this->mergeArray['Postcode'] = $this->locationDetails['postcode'];
						
						$sectionArray['iframe_google_map'] = true;
						
						$this->mergeArray['cID'] = $this->cID;
						$this->mergeArray['lng'] = $this->locationDetails['longitude']; // ? $this->locationDetails['longitude'] : -2.0;
						$this->mergeArray['lat'] = $this->locationDetails['latitude']; // ? $this->locationDetails['latitude'] : 54.0;
						$this->mergeArray['zoom'] = $this->locationDetails['map_zoom'] ? $this->locationDetails['map_zoom'] : 2;
						
						if (!empty($this->locationDetails['url'])) {
							$sectionArray['Contact'] = true;
							$sectionArray['Url'] = true;
							$this->mergeArray['Url'] = $this->locationDetails['url'];
						}
					}else { 
						$section = 'iframe_google_map';
						$this->mergeArray['lng'] = $this->locationDetails['longitude']; // ? $this->locationDetails['longitude'] : -2.0;
						$this->mergeArray['lat'] = $this->locationDetails['latitude']; // ? $this->locationDetails['latitude'] : 54.0;
						$this->mergeArray['zoom'] = $this->locationDetails['map_zoom'] ? $this->locationDetails['map_zoom'] : 2;
						$this->mergeArray['description'] = $this->locationDetails['description'];
					}
				} else {
					$section = 'access_denied';
					$this->mergeArray = array();
				}
				$this->framework($section,$this->mergeArray, $sectionArray);
			}
			
		} elseif ($this->noLocationFound) {
			if ($this->setting('location_source_mode') == 'location_from_selector') {
				if(adminId()){
					$section = 'no_location_selected';
					$this->mergeArray = array();
					$this->framework($section,$this->mergeArray);
				}
			} else {
				$section = 'access_denied';
				$this->mergeArray = array();
				$this->framework($section,$this->mergeArray);
			}
		} elseif ($this->noLocationUrlIdPassed) {
			$section = 'url_not_complete';
			$this->mergeArray = array();
			$this->framework($section,$this->mergeArray);
		}
		
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
			
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				if ($values['location_source_mode'] == "location_from_selector") {
					$fields['location_plugin_mode']['hidden'] = true;
					$fields['location_user']['hidden'] = true;
					$fields['location']['hidden'] = false;
					$fields['map_width']['hidden'] = false;
					$fields['map_height']['hidden'] = false;
					$fields['max_location_image_width']['hidden'] = false;
					$fields['max_location_image_height']['hidden'] = false;
					
					
				} elseif ($values['location_source_mode'] == "location_from_url") {
					$fields['location_plugin_mode']['hidden'] = false;
					$fields['location_user']['hidden'] = false;
					$fields['location']['hidden'] = true;
					$fields['map_width']['hidden'] = true;
					$fields['map_height']['hidden'] = true;
					$fields['max_location_image_width']['hidden'] = true;
					$fields['max_location_image_height']['hidden'] = true;
					
				}
				if(!(inc('zenario_organization_manager'))) {
					$fields['location_user']['hidden'] = true;
				}
				break;
		}
	}
}

