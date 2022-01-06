<?php
/*
 * Copyright (c) 2022, Tribal Limited
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

class zenario_location_viewer extends ze\moduleBaseClass {
	
	private $data = [];

	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = true);
		
		// Get location ID
		$locationId = $this->getLocationId();
		switch ($this->setting('location_source_mode')) {
			case 'location_from_organizer':
				if (!$locationId && ze\admin::id()) {
					$this->data['no_location_selected'] = true;
					return true;
				}
				break;
			case 'location_from_url':
				if($this->setting('use_custom_url_request') && ($urlRequest = $this->setting('url_request'))){
					$this->registerGetRequest($urlRequest);
				}else{
					$this->registerGetRequest('l_id');
				}
				if (!$locationId) {
					$this->data['url_not_complete'] = true;
					return true;
				}
				if ($this->setting('location_user')) {
					if (!(ze\row::exists(ZENARIO_ORGANIZATION_MANAGER_PREFIX. 'user_role_location_link', ['user_id' => ze\user::id(), 'location_id' => $locationId]))){
						$this->data['access_denied'] = true;
						return true;
					}
				}
				break;
		}
		if (!$locationId || !($locationDetails = zenario_location_manager::getLocationDetails($locationId))) {
			return false;
		}
		
		self::setOgTags($locationId);
		
		// These mergefields are used on IMI custom framework for zenario_extranet_user_role_loc_view
		if (!empty($locationDetails['equiv_id']) && !empty($locationDetails['content_type'])) {
			ze\content::langEquivalentItem($locationDetails['equiv_id'], $locationDetails['content_type']);
			$locationUrl = ze\link::toItem($locationDetails['equiv_id'], $locationDetails['content_type']);
			$this->data['Link_Start'] = "<a href='" . $locationUrl . "'>";
			$this->data['Link_End'] = "</a>";
			
			//This merge field can be used in any framework.
			$this->data['Descriptive_page_href'] = $locationUrl;
		}
		
		if ($this->setting('use_location_name_for_page_title') && $locationDetails['description']) {
			$this->setPageTitle($locationDetails['description']);
		}
		
		if ($this->setting('show_title')) {
			$this->data['show_title'] = true;
		}
		
		if ($this->setting('show_details')) {
			$this->data['show_details'] = true;
			if (!empty($locationDetails['country_id']) && ze\module::inc('zenario_country_manager')) {
				if ($country = zenario_country_manager::getCountryNamesInCurrentVisitorLanguage("active", $locationDetails['country_id'])) {
					$locationDetails['country']= ze\ray::value($country,"COUNTRY_" . $locationDetails['country_id']);
				}
				
				if (!empty($locationDetails['region_id'])) {
					if ($region = zenario_country_manager::getRegionNamesInCurrentVisitorLanguage("active", $locationDetails['country_id'], $locationDetails['region_id'])) {
						foreach ($region as $key => $value) {
							$locationDetails['region'] = zenario_country_manager::adminPhrase(ze::$visLang,$value);
							break;
						}
					}
				}
			}
			
			if ($this->setting('show_image')) {
				$this->data['show_image'] = true;
				$imageId =  ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_images', 'image_id', 
					['location_id' => $locationDetails['id'], 'sticky_flag' => 1]);
				$locationDetails['image_width'] = 
				$locationDetails['image_height'] = 
				$locationDetails['image_url'] = false;
				ze\file::imageLink(
					$locationDetails['image_width'], 
					$locationDetails['image_height'], 
					$locationDetails['image_url'], 
					$imageId, 
					(int) $this->setting('max_location_image_width') ?: 120,
					(int) $this->setting('max_location_image_height') ?: 120
				);
			}
			
			$this->data['show_address'] = $this->setting('show_address');
			$this->data['show_city'] = $this->setting('show_city');
			$this->data['show_state'] = $this->setting('show_state');
			$this->data['show_country'] = $this->setting('show_country');
			$this->data['show_phone'] = $this->setting('show_phone');
			$this->data['show_fax'] = $this->setting('show_fax');
			$this->data['show_email'] = $this->setting('show_email');
			$this->data['show_website'] = $this->setting('show_website');
			$this->data['show_summary'] = $this->setting('show_summary');
			$this->data['show_locality'] = $this->setting('show_locality');
			$this->data['show_postcode'] = $this->setting('show_postcode');
			$this->data['show_region'] = $this->setting('show_region');
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
				($locationDetails['latitude'] ?? false),
				($locationDetails['longitude'] ?? false),
				($locationDetails['map_zoom'] ?? false)
			);
		}
		
		
		if(is_array($locationDetails) && $locationDetails){
		
			foreach($locationDetails as $key=> $details){
				$locationDetails[$key]=nl2br($details);
			}
		
		}
		$this->data['location'] = $locationDetails;
		return true;
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		switch ($path){
			case 'plugin_settings':
				$box['tabs']['first_tab']['fields']['og_description_data_set_field']['values'] =
					ze\datasetAdm::listCustomFields('locations', $flat = false, false, $customOnly = true, $useOptGroups = true);
				break;
		}
	}
	
	
	public function setOgTags($locationId){
		if(!is_numeric($locationId)){
			return false;
		}
		
		if($this->setting('og_tags')){	
			if($this->setting('og_title')){
				$locationDetails = ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations',['description','city','country_id'],['id'=>$locationId]);
				$country = false;
				if (!empty($locationDetails['country_id']) && ze\module::inc('zenario_country_manager')) {
					if ($country = zenario_country_manager::getCountryNamesInCurrentVisitorLanguage("active", $locationDetails['country_id'])) {
						$country = ze\ray::value($country,"COUNTRY_" . $locationDetails['country_id']);
					}
				}
				$title = $locationDetails['description'];
				if($country){
					$title .=' '.$country;
				}
				if($locationDetails['city']){
					$title .=' '.$locationDetails['city'];
				}
				$this->setPageTitle($title);
			}
			if($this->setting('og_image')){
				$imageId =  ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_images', 'image_id', ['location_id' => $locationId, 'sticky_flag' => 1]);
				if($imageId){
					$this->setPageImage($imageId);
				}
			}
		
			if($this->setting('og_description') && $datasetId = $this->setting('og_description_data_set_field')){
				$dataSetFieldColumnName = ze\row::get('custom_dataset_fields','db_column', ["id"=>$datasetId]);
				if($dataSetFieldColumnName){
					//Custom data:
					$sql.= 'SELECT cd.'.$dataSetFieldColumnName.'  
							FROM ' . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'locations AS loc
							LEFT JOIN '. DB_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. 'locations_custom_data AS cd
							ON cd.location_id = loc.id
							WHERE loc.id = ' . (int) $locationId;
					$result = ze\sql::select($sql);
					$row = ze\sql::fetchAssoc($result);
					if(is_array($row) && $row){
						$this->setPageDesc($row["introduction"]);
					}
				}
			}
		}
	}
	
	// Note: This is a seperate function because zenario_extranet_user_role_loc_view needs to overwrite this method
	public function getLocationId() {
		switch ($this->setting('location_source_mode')) {
			case 'location_from_selector':
				if (($this->cType=="event")  
					&& (ze\module::inc('zenario_ctype_event')) 
					&& ($contentItem = zenario_ctype_event::getEventDetails($this->cID,$this->cVersion))
				) {
					return $contentItem['location_id'];
				} else {
					return zenario_location_manager::getLocationIdFromContentItem($this->cID,$this->cType);
				}
				break;
			case 'location_from_organizer':
				return $this->setting('location');
			case 'location_from_url':
				if($this->setting('use_custom_url_request') && ($urlRequest = $this->setting('url_request'))){
					return ($_GET[$urlRequest] ?? false);
				}else{
					return ($_GET['l_id'] ?? false);
				}
		}
		return false;
	}
	
	function showSlot() {
		$this->twigFramework($this->data);
    }
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case "plugin_settings":
				if (!$values['first_tab/show_title'] && !$values['first_tab/show_details'] && !$values['first_tab/show_map']) {
					$box['tabs']['first_tab']['errors'][] = ze\admin::phrase('You must select at least one section to show.');
				}
			
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				
				$fields['location_user']['hidden'] = 
					(!ze\module::inc('zenario_organization_manager')) || ($values['location_source_mode'] != 'location_from_url');
				
				$fields['show_address']['hidden'] =
				$fields['show_city']['hidden'] =
				$fields['show_state']['hidden'] =
				$fields['show_country']['hidden'] =
				$fields['show_phone']['hidden'] =
				$fields['show_fax']['hidden'] =
				$fields['show_email']['hidden'] =
				$fields['show_website']['hidden'] =
				$fields['show_summary']['hidden'] =
				$fields['show_locality']['hidden'] =
				$fields['show_postcode']['hidden'] =
				$fields['show_region']['hidden'] =
				$fields['show_image']['hidden'] = !$values['show_details'];
				
				$hidden = !$values['show_map'];
				$this->showHideImageOptions($fields, $values, 'first_tab', $hidden, 'map_', false);
				
				$hidden = !($values['show_image'] && $values['show_details']);
				$this->showHideImageOptions($fields, $values, 'first_tab', $hidden, 'max_location_image_', false);
					
				$fields['location']['hidden'] = 
					$values['location_source_mode'] != 'location_from_organizer';
					
				
				break;
		}
	}
}

