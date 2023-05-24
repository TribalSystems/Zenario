<?php
/*
 * Copyright (c) 2023, Tribal Limited
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

class zenario_location_manager extends ze\moduleBaseClass {
	
	public static function setupSectorCheckboxes(&$field, $locationId = false) {
		$field['values'] = [];
		if ($result = ze\row::query(ZENARIO_LOCATION_MANAGER_PREFIX . 'sectors', ['id', 'parent_id', 'name'], [], 'name')) {
			while ($row = ze\sql::fetchAssoc($result)) {
				$field['values'][(int) $row['id']] = ['label' => $row['name'], 'parent' => (int) $row['parent_id']];
			}
		}
		
		if ($locationId) {
			$field['value'] = ze\escape::in(ze\row::getValues(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link', 'sector_id', ['location_id' => $locationId]), false);
		}
	}

	public function exploreTreeUp($id,$fuse=100){
		$level=0;
		while(1){
			$level++;
			$id = ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations','parent_id',['id'=>(int)$id]);
			if (!$id){
				return $level; 
			}
			if (!--$fuse){
				return $level; 
			}
		}
	}

	public function exploreTreeDown($id,&$maxLevel=0,$fuse=100,$currentLevel=0){
		if (!$fuse){
			return;
		}
		$currentLevel++;
		if ($maxLevel<$currentLevel) {
			$maxLevel++;
		}
		$children = ze\row::query(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations', ['id'], ['parent_id'=>$id]);
		while($child = ze\sql::fetchAssoc($children)){
			$this->exploreTreeDown($child['id'],$maxLevel,$fuse--,$currentLevel);
		}
	}
 
	public function handleAJAX () {
		if (($_GET["mode"] ?? false)=="get_country_name") {
			$country= zenario_country_manager::getCountryFullInfo("all",($_GET["country_id"] ?? false));
			if (isset($country[($_GET["country_id"] ?? false)])) {
			    $item['country'] = $country[($_GET["country_id"] ?? false)]['english_name'];
			    echo $item['country'];
			}
		}
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__locations/nav/sectors/panel':
			case 'zenario__locations/location_sectors/panel':
				if ($refinerName != 'location_sectors') {
					unset($panel['columns']['score']);
				}
				break;
			case 'zenario__locations/panel':
				
				// Get panel type and add filter if map to remove locations without map coordinates
				if (ze::get('panel_type') === 'google_map') {
					$panel['db_items']['where_statement'] = '
						WHERE l.latitude IS NOT NULL 
						AND l.longitude IS NOT NULL
					';
				}
				
				break;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__content/panels/content':
				if (isset($_GET['refiner__zenario__locations__create_content'])
					&& isset($panel['collection_buttons']['create']['admin_box'])
				) {
					$panel['collection_buttons']['create']['admin_box']['path'] = 'zenario_content';
					$panel['title'] = 'Content items of the type "HTML page"';
				}
				break;
				
			case 'zenario__locations/panel':
				
				$maxLevels = (int) ze::setting('zenario_location_manager__hierarchy_levels');
				if (!$maxLevels && isset($panel['item_buttons']['set_parent'])) {
					unset($panel['item_buttons']['set_parent']);
				}
				
				//Hide columns role and locations
				if($refinerName == "users_locations"){
					$panel['columns']['external_id']['hidden'] = true;
					$panel['columns']['path']['hidden'] = true;
					$panel['columns']['address1']['hidden'] = true;
					$panel['columns']['address2']['hidden'] = true;
					$panel['columns']['locality']['hidden'] = true;
					$panel['columns']['city']['hidden'] = true;
					$panel['columns']['state']['hidden'] = true;
					$panel['columns']['postcode']['hidden'] = true;
					$panel['columns']['country_code']['hidden'] = true;
					$panel['columns']['country']['hidden'] = true;
					$panel['columns']['region']['hidden'] = true;
					$panel['columns']['latitude']['hidden'] = true;
					$panel['columns']['longitude']['hidden'] = true;
					$panel['columns']['status']['hidden'] = true;
					$panel['columns']['content_item']['hidden'] = true;
					$panel['columns']['sectors']['hidden'] = true;
					$panel['columns']['sticky_flag']['hidden'] = true;
					$panel['columns']['parent_name']['hidden'] = true;
					$panel['columns']['number_of_children']['hidden'] = true;
					$panel['columns']['image_usage']['hidden'] = true;
					$panel['columns']['checksum']['hidden'] = true;
					$panel['columns']['last_edited']['hidden'] = true;
					$panel['columns']['last_edited_by']['hidden'] = true;
					$panel['columns']['last_updated_via_import']['hidden'] = true;
					$panel['columns']['on_map']['hidden'] = true;
				}
				// Hide import button from sector location
				if($refinerName == "sector_locations"){
					$panel['collection_buttons']['import']['hidden'] = true;
					$panel['collection_buttons']['donwload_sample_file']['hidden'] = true;
				}
				// Hide pending button if pending state not enabled
				if (!ze::setting('zenario_location_manager__enable_pending_status')) {
					unset($panel['item_buttons']['mark_as_pending']);
				// Otherwise show a pending quick filter button
				} else {
					$panel['quick_filter_buttons']['pending'] = [
						'label' => 'Pending',
						'column' => 'status',
						'value' => 'pending',
						'ord' => 1.5
					];
				}
				
				// Add dataset ID to import button
				$dataset = ze\dataset::details(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations');
				$panel['collection_buttons']['import']['admin_box']['key']['dataset'] = 
				$panel['collection_buttons']['donwload_sample_file']['admin_box']['key']['dataset'] = 
				$panel['collection_buttons']['export']['admin_box']['key']['dataset'] = 
					$dataset['id'];
				
				// If no locations, hide export button
				if (count($panel['items']) <= 0) {
					$panel['collection_buttons']['export']['hidden'] = true;
				}
				
				$admins = [];
				$adminsRaw = ze\row::query("admins",["id","username","authtype"],["status" => "active"]);
				if (ze\sql::numRows($adminsRaw)>0) {
					while ($admin = ze\sql::fetchAssoc($adminsRaw)) {
						$admins[$admin['id']] = $admin['username'];
						
						if ($admin['authtype']=="super") {
							$admins[$admin['id']] .= " (super)";
						}
					}
				}
	
				$panel['columns']['last_edited_by']['values'] = $admins;
				
				
				$panel['columns']['timezone']['values'] = ze\dataset::getTimezonesLOV();
				
	
				foreach ($panel['items'] as $id => &$item) {
					$item['cell_css_classes'] = [];
					$item['label'] = $item['description'] . " " . $item['city'] . " " . $item['country'];
					
					$locationDetails = self::getLocationDetails($id);
	
					if ($item['status'] == 'active') {
						$item['cell_css_classes']['status'] = "green";
					} elseif ($item['status'] == 'pending') {
						$item['cell_css_classes']['status'] = "orange";
					} else {
						$item['cell_css_classes']['status'] = "brown";
					}
					
					if (!ze\ray::issetArrayKey($locationDetails,"parent_id") && !ze\row::exists(ZENARIO_LOCATION_MANAGER_PREFIX . "locations",["parent_id" => $id])) {
						$item['traits']['not_in_hierarchy'] = true;
					}
					
					if ($item['checksum'] && $item['image_usage']) {
						$img = '&usage=' . $item['image_usage'] . '&c='. $item['checksum'];
						$item['image'] = 'zenario/file.php?og=1'. $img;
					}
					
					$locationHierarchyPathAndDeepLinks = self::getLocationHierarchyPathAndDeepLinks($id);
					
					$item['path'] = $locationHierarchyPathAndDeepLinks['path'];
					
					$deeplinkFrag = $locationHierarchyPathAndDeepLinks['link'];
					
					$deeplinkFrag = substr($deeplinkFrag,0,strrpos($deeplinkFrag,"item//")) . substr($deeplinkFrag,strrpos($deeplinkFrag,"item//") + 6);
					
					$item['navigation_path'] = "zenario__locations/panel/hierarchy/item//hierarchy" . $deeplinkFrag;
					
				}
				
				if (ze::setting("zenario_location_manager__sector_management")!="2") {
					unset($panel['item_buttons']['view_location_sectors']);
				}
	
				if (ze::setting("zenario_location_manager__hierarchy_levels")==0) {
					unset($panel['item_buttons']['view_in_hierarchy']);
				}
				
				if ($refinerName=="sector_locations") {
					if ($sector = self::getSectorDetails($refinerId)) {
						$panel['title'] = "Locations in the sector \"" . $sector['name'] . "\"";
					}
	
					$panel['no_items_message'] = "There are no locations in this sector.";
	
					if (ze\ray::issetArrayKey($panel,"item_buttons","activate")) {
						unset($panel['item_buttons']['activate']);
					}
	
					if (ze\ray::issetArrayKey($panel,"item_buttons","suspend")) {
						unset($panel['item_buttons']['suspend']);
					}
	
					if (ze\ray::issetArrayKey($panel,"item_buttons","upload_image")) {
						unset($panel['item_buttons']['upload_image']);
					}
	
					if (ze\ray::issetArrayKey($panel,"item_buttons","suspend")) {
						unset($panel['item_buttons']['suspend']);
					}
	
					unset($panel['item']['link']);
	
					$panel['item_buttons']['remove_location']['ajax']['confirm']['message'] = ze\admin::phrase('Are you sure you wish to remove the Location "[[description]]" from the Sector "[[name]]"?', $sector);
					$panel['item_buttons']['remove_location']['ajax']['confirm']['multiple_select_message'] = ze\admin::phrase('Are you sure you wish to remove the selected Locations from the Sector "[[name]]"?', $sector);
	
					unset($panel['collection_buttons']['create_location']);
				} elseif ($refinerName=='children_of_location') {
					foreach ($panel['item_buttons'] as $K => $button) {
						switch ($K) {
							case 'remove_location':
								unset($panel['item_buttons'][$K]);
								break;
						}
					}
					foreach ($panel['collection_buttons'] as $K => $button) {
						switch ($K) {
							case 'add_child_location':
								break;
							default:
								unset($panel['collection_buttons'][$K]);
								break;
						}
					}
					
					unset($panel['item_buttons']['view_in_hierarchy']);
					
					$panel['no_items_message'] = 'This location has no child locations.';
					$location = self::getLocationDetails($refinerId);
					$panel['title']="Child locations of \"" . $location['description'] . "\"";
					$panel['item']['tooltip'] = ze\admin::phrase("View this location's child locations");
					$panel['item']['link']['path'] = "zenario__locations/panel";
					$panel['item']['link']['branch'] = "Yes";
					$panel['item']['link']['refiner'] = "children_of_location";
	
					$level = $this->exploreTreeUp($refinerId) + 1;
					$maxLevels = (int) ze::setting('zenario_location_manager__hierarchy_levels');
					if (($level > $maxLevels ) && ($panel['collection_buttons']['add_child_location'] ?? false)) {
						unset($panel['collection_buttons']['add_child_location']);
					} 
					$panel['item_buttons']['set_parent']['name'] = 'Assign location a new parent';
					$panel['item_buttons']['set_parent']['combine_items']['one_to_one_choose_phrase'] = 'Assign parent Location';
					if ($maxLevels==0 && ze\ray::issetArrayKey($panel,'item_buttons','set_parent')) {
						unset($panel['item_buttons']['set_parent']);
					}
				} elseif ($refinerName=='parent_locations') {
					foreach ($panel['item_buttons'] as $K => $button) {
						switch ($K) {
							case 'remove_location':
								unset($panel['item_buttons'][$K]);
								break;
						}
					}
					foreach ($panel['collection_buttons'] as $K => $button) {
						switch ($K) {
							case 'add_child_location':
								break;
							default:
								unset($panel['collection_buttons'][$K]);
								break;
						}
					}
	
					unset($panel['item_buttons']['view_in_hierarchy']);
	
					$panel['title']='Top level locations with child locations';
					$panel['no_items_in_search_message'] = 'No hierarchy of locations exists. Make a location a child of another location for something to appear here.';
					$panel['item']['link']['path'] = "zenario__locations/panel";
					$panel['item']['link']['branch'] = "Yes";
					$panel['item']['link']['refiner'] = "children_of_location";
					$maxLevels = (int) ze::setting('zenario_location_manager__hierarchy_levels');
					if ($maxLevels==0 && ze\ray::issetArrayKey($panel,'item_buttons','set_parent')) {
						unset($panel['item_buttons']['set_parent']);
					}
				} else {
					if ($panel['item']['link']["path"] == "zenario__locations/panel" && $panel['item']['link']["refiner"] == "children_of_location"){
						unset($panel['item']['link']);
					}
	
					$maxLevels = (int) ze::setting('zenario_location_manager__hierarchy_levels');
					if ($maxLevels==0 && ze\ray::issetArrayKey($panel,'item_buttons','set_parent')) {
						unset($panel['item_buttons']['set_parent']);
					}
					if (ze\ray::issetArrayKey($panel,"item_buttons","remove_location")) {
						unset($panel['item_buttons']['remove_location']);
					}
				}
				break;
				
			case 'zenario__locations/nav/sectors/panel':
				
				if(!ze\priv::check('_PRIV_MANAGE_LOCATIONS')){
					$panel['no_items_message']="No sectors defined.";
				}
				
				if (!$refinerName || $refinerName=="sub_sectors") {
					unset($panel['collection_buttons']['add_sector']);
					unset($panel['item_buttons']['remove_sector']);
					unset($panel['item_buttons']['increase_score']);
					unset($panel['item_buttons']['decrease_score']);
					
					if ($refinerName=="sub_sectors") {
						if ($sector = self::getSectorDetails($refinerId)) {
							$panel['title'] = "Sub-sectors of \"" . $sector['name'] . "\"";
							//unset($panel['item']['link']);
						}
					}
				}
				break;
				
			case 'zenario__locations/location_sectors/panel':
				if ($refinerName=="location_sectors") {
					if ($locationSectors = self::getLocationSectors($refinerId)) {
						$stickyCounter = 0;
						$topLevelSectorCount = 0;
						foreach ($locationSectors as $locationSector) {
							$sector = self::getSectorDetails($locationSector);
							$locationSectorDetails = self::getLocationSectorDetails($refinerId,$locationSector);
							
							if ($sector['parent_id']==0) {
								$topLevelSectorCount++;
							}
							
							if ($locationSectorDetails['sticky_flag']) {
								$stickyCounter++;
							}
						}
					}
					
					if (ze::setting("zenario_location_manager__sector_management")!="2") {
						unset($panel['item_buttons']['remove']);
						unset($panel['collection_buttons']['add']);
					}
					
					foreach ($panel['items'] as $id => &$item) {
						$locationSector = self::getLocationSectorDetails($refinerId,$id);
						$sector = self::getSectorDetails($id);
	
						$item['traits']['can_delete'] = "Yes";
						$item['traits']['not_sticky'] = "No";
						
						if ($locationSector['score_id']<5) {
							$item['traits']['not_at_max'] = "Yes";
						}
						
						if ($locationSector['score_id']>1) {
							$item['traits']['not_at_min'] = "Yes";
						}
	
						if ($item['sticky']==1 && !$sector['parent_id'] && $stickyCounter==1) {
							if (!$sector['parent_id'] && $topLevelSectorCount>1) {
								$item['traits']['can_delete'] = "No";
							}
						}
						
	
						if (($item['sticky']==0 && !$sector['parent_id'] && $topLevelSectorCount>1) || $stickyCounter>1) {
							$item['traits']['not_sticky'] = "Yes";
						}
						
						if ($item['label_name']) {
							$item['label_name'] .= " (Score " . $item['score'] . ")";
						
						}
						
						$pathArray = self::getSectorPath($id);
						
						$item['path'] = implode(" › ",array_reverse($pathArray));
					}
					
					unset($panel['collection_buttons']['create_sector']);
					unset($panel['item_buttons']['delete_sector']);
					unset($panel['item_buttons']['rename_sector']);
					
					if ($location = self::getLocationDetails($refinerId)) {
						$panel['title'] = "Sectors associated with \"" . $location['description'] . "\"";
					}
				}
				break;
		}
	}
    
    public static function getMapPinPlacementMethods() {
        return [
			'postcode_country' => 'Postcode and Country',
			'street_postcode_country' => 'Address Line 1, Postcode and Country',
			'street_city_country' => 'Address Line 1, City and Country',
			'locality_postcode_country' => 'Locality, Postcode and Country'
        ];
    }
    
	public function setTimezoneValues(&$field) {
			
		$defaultTZ = ze::setting('zenario_timezones__default_timezone');
		
		$field['values'] = ze\dataset::getTimezonesLOV();
		
		if ($defaultTZ && isset($field['values'][$defaultTZ]['label'])) {
			$field['empty_value'] =
				ze\admin::phrase('Use the sitewide default timezone - [[label]]',
					$field['values'][$defaultTZ]);
		} else {
			$field['empty_value'] =
				ze\admin::phrase('Use the sitewide default timezone (not set)');
		}
    }
    
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$locationDetails = [];
	
		switch ($path) {
			case "zenario_location_manager__location":
				ze\priv::exitIfNot('_PRIV_MANAGE_LOCATIONS');
				$locationCountriesFinal = zenario_country_manager::getCountryAdminNamesIndexedByISOCode("active");
				
				foreach ($locationCountriesFinal as $key => $value) {
					$fields['details/country']['values'][$key] = $value;
				}

				if ($box['key']['id']) {
					$locationDetails = self::getLocationDetails($box['key']['id']);
                  
                 	$box['title'] = ze\admin::phrase('Editing the location "[[name]]"',['name' => $locationDetails['description']]);
                    $values['images/images'] = ze\escape::in(self::locationsImages($box['key']['id']), 'numeric');
					$values['details/external_id'] = $locationDetails['external_id'];
                    $values['details/name'] = $locationDetails['description'];
                    $values['details/address_line_1'] = $locationDetails['address1'];
                    $values['details/address_line_2'] = $locationDetails['address2'];
                    $values['details/locality'] = $locationDetails['locality'];
                    $values['details/city'] = $locationDetails['city'];
                    $values['details/state'] = $locationDetails['state'];
                    $values['details/postcode'] = $locationDetails['postcode'];
                    $values['details/country'] = $locationDetails['country_id'];
					if ($region = self::getInmostLocationRegion($box['key']['id'])){
						$values['details/region'] = $region;
					}
					$values['details/map_center_lat'] = $locationDetails['map_center_latitude'];
					$values['details/map_center_lng'] = $locationDetails['map_center_longitude'];
					$values['details/marker_lat'] = $locationDetails['latitude'];
					$values['details/marker_lng'] = $locationDetails['longitude'];
					$values['details/hide_pin'] = $locationDetails['hide_pin'];
					$values['details/zoom'] = $locationDetails['map_zoom'];
					$values['details/timezone'] = $locationDetails['timezone'];
                    
                    
                    if ($locationDetails['content_type'] && $locationDetails['equiv_id']) {
	                    $values['content_item/content_item'] = $locationDetails['content_type'] . "_" . $locationDetails['equiv_id'];
	                }

					if ($locationDetails['status'] == 'suspended') {
						$box['tabs']['details']['notices']['location_suspended']['show'] = true;
					}
 
                    $box['last_updated'] = ze\admin::formatLastUpdated($locationDetails);
 
                }

				if (isset($fields['sectors/sectors'])) {
					self::setupSectorCheckboxes(
						$fields['sectors/sectors'],
						$box['key']['id']);
				}

				$fields['details/external_id']['hidden'] = !ze::setting('zenario_location_manager__enable_external_id');
				
				if (ze::setting("zenario_location_manager__sector_management")!="0") {
					$box['tabs']['sectors']['hidden'] = true;
					$box['tabs']['zenario_location_manager__sector']['hidden'] = true;
				}
				
				if (!ze\module::isRunning('zenario_location_ratings')) {
					$box['tabs']['zenario_location_ratings__accreditation']['hidden'] = true;
				}
				
				if (!ze\module::isRunning('zenario_location_map_and_listing_2')) {
					$box['tabs']['filters']['hidden'] = true;
				}

				//Check if Google Maps API key is present
				$linkStart = "<a href='organizer.php#zenario__administration/panels/site_settings//api_keys~.site_settings~tgoogle_maps~k{\"id\"%3A\"api_keys\"}' target='_blank'>";
				$linkEnd = "</a>";

				$googleMapsApiKey = ze::setting('google_maps_api_key');
				if ($googleMapsApiKey) {
					$fields['details/no_google_maps_api_key_saved']['hidden'] = true;	
					
					$googleMapsApiKeyNoteBelow = ze\admin::phrase(
						'Fine tune your map by setting the zoom level and dragging/dropping the pin. Note that this map relies on a Google Maps API key, see [[link_start]]site settings[[link_end]].',
						['link_start' => $linkStart, 'link_end' => $linkEnd]
					);

					$fields['details/map_edit']['note_below'] = $fields['details/map_view']['note_below'] = $googleMapsApiKeyNoteBelow;

					$map_lookup = "<select id=\"pin_placement_method\">\n";
					$methods = static::getMapPinPlacementMethods();
					$defaultMethod = ze::setting('zenario_location_manager__default_pin_placement_method');
					foreach ($methods as $method => $label) {
						$map_lookup .= "<option value=\"" . $method . "\"";
						if ($method == $defaultMethod) {
							$map_lookup .= " selected";
						}
						$map_lookup .= ">" . $label . "</option>\n";
					}
					
					$map_lookup .= "</select>\n";
					$map_lookup .= "<button onclick=\"document.getElementById('google_map_iframe').contentWindow.placeMarker(document.getElementById('pin_placement_method').value);return false\">Place Pin</button>\n";
					$map_lookup .= "<button onclick=\"document.getElementById('google_map_iframe').contentWindow.clearMap();return false\">Clear Map</button>\n";
					
					$mapEdit = "<iframe id=\"google_map_iframe\" name=\"google_map_iframe\" src=\"" . htmlspecialchars($this->showFileLink("&map_center_lat=" . ($locationDetails['map_center_latitude'] ?? false) . "&map_center_lng=" . ($locationDetails['map_center_longitude'] ?? false) . "&marker_lat=" . ($locationDetails['latitude'] ?? false) . "&marker_lng=" . ($locationDetails['longitude'] ?? false) . "&zoom=" . ($locationDetails['map_zoom'] ?? false)) . "&editmode=1") . "\" style=\"width: 425px;height: 425px;border: none;\"></iframe>\n";
					$mapView = "<iframe id=\"google_map_iframe\" name=\"google_map_iframe\" src=\"" . htmlspecialchars($this->showFileLink("&map_center_lat=" . ($locationDetails['map_center_latitude'] ?? false) . "&map_center_lng=" . ($locationDetails['map_center_longitude'] ?? false) . "&marker_lat=" . ($locationDetails['latitude'] ?? false) . "&marker_lng=" . ($locationDetails['longitude'] ?? false) . "&zoom=" . ($locationDetails['map_zoom'] ?? false)) . "&editmode=0") . "\" style=\"width: 425px;height: 425px;border: none;\"></iframe>\n";

					$fields['details/map_lookup']['snippet']['html'] = $map_lookup;				
					$fields['details/map_edit']['snippet']['html'] = $mapEdit;
					$fields['details/map_view']['snippet']['html'] = $mapView;
				} else {
					$fields['details/map_edit']['hidden'] =
					$fields['details/map_view']['hidden'] =
					$fields['details/map_lookup']['hidden'] =
					$fields['details/map_center_lat']['hidden'] =
					$fields['details/map_center_lng']['hidden'] =
					$fields['details/marker_lat']['hidden'] =
					$fields['details/marker_lng']['hidden'] =
					$fields['details/hide_pin']['hidden'] =
					$fields['details/hide_pin']['hidden'] = true;

					$googleMapsApiKeyInfoNote = ze\admin::phrase(
						'Location Manager can display locations on a Google Map, but Zenario requires an API key to be stored in site settings. See [[link_start]]site settings[[link_end]] to set a key.',
						['link_start' => $linkStart, 'link_end' => $linkEnd]
					);
					ze\lang::applyMergeFields($fields['details/no_google_maps_api_key_saved']['snippet']['html'], ['No_google_maps_api_key_saved' => $googleMapsApiKeyInfoNote]);
				}
				
				
				$this->setTimezoneValues($fields['details/timezone']);
				
				
				break;
			case "zenario_location_manager__locations_multiple_edit":
				ze\priv::exitIfNot('_PRIV_MANAGE_LOCATIONS');
				
				$box['title'] = "Editing settings for " . sizeof(explode(",",$box['key']['id'])) . " locations";
			
				$locationCountriesFinal = zenario_country_manager::getCountryAdminNamesIndexedByISOCode("active");

				foreach ($locationCountriesFinal as $key => $value) {
					$fields['details/country']['values'][$key] = $value;
				}
				
				if ($box['key']['id']) {
					$fieldsToCheck = [
											"name" => "description",
											"address_line_1" => "address1",
											"address_line_2" => "address2",
											"locality" => "locality",
											"city" => "city",
											"state" => "state",
											"postcode" => "postcode",
											"country" => "country_id",
											"timezone" => "timezone"
										];
										
					foreach ($fieldsToCheck as $tuixName => $dbName) {
						$sql = "SELECT DISTINCT " . ze\escape::sql($dbName) . "
								FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations
								WHERE id IN (" . ze\escape::in($box['key']['id'], 'numeric') . ")
								LIMIT 2";
								
						$result = ze\sql::select($sql);
						
						if (ze\sql::numRows($result)==1) {
							$row = ze\sql::fetchAssoc($result);
							
							$box['tabs']['details']['fields'][$tuixName]['value'] = $row[$dbName];
						}
					}
					
					$regionSame = true;
					$previousRegion = false;
					
					$ids = explode(",",$box['key']['id']);
					
					foreach ($ids as $id) {
						if ($region = self::getInmostLocationRegion($id)){
							if (!$previousRegion) {
								$previousRegion = $region;
							} else {
								if ($region!=$previousRegion) {
									$regionSame = false;
									break;
								}
							}						
						} else {
							$regionSame = false;
						}
					}
					
					if ($regionSame) {
						$values['details/region'] = $previousRegion;
					}


					$sql = "SELECT DISTINCT 
								CONCAT(content_type,'_',equiv_id) AS tag
							FROM " 
								. DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations
							WHERE 
								id IN (" . ze\escape::in($box['key']['id'], 'numeric') . ")
							LIMIT 2";
							
					$result = ze\sql::select($sql);
					
					if (ze\sql::numRows($result)==1) {
						$row = ze\sql::fetchAssoc($result);
						
						$values['content_item/content_item'] = $row['tag'];
					}

					if (!ze\module::isRunning('zenario_location_ratings')) {
						$box['tabs']['zenario_location_ratings__accreditation']['hidden'] = true;
					}
					
					if (ze::setting("zenario_location_manager__sector_management")!="0") {
						$box['tabs']['sectors']['hidden'] = true;
						$box['tabs']['zenario_location_manager__sector']['hidden'] = true;
					} else {
						$field = &$fields['sectors/sectors'];
	
						$field['multiple_edit'] = [];
						
						self::setupSectorCheckboxes($field);
						
						$sectorsPicked = false;
						$sql = "
							SELECT lssl.sector_id, COUNT(DISTINCT lssl.location_id) AS cnt
							FROM ". DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations AS l
							INNER JOIN ". DB_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link AS lssl
							   ON l.id = lssl.location_id
							WHERE lssl.location_id IN (". ze\escape::in($box['key']['id'], true). ")
							GROUP BY lssl.sector_id";
						$result = ze\sql::select($sql);
						while ($row = ze\sql::fetchAssoc($result)) {
							if (isset($field['values'][$row['sector_id']])) {
								$sectorsPicked = true;
								$field['values'][$row['sector_id']]['marked'] = true;
								$field['values'][$row['sector_id']]['label'] .= ' ('. $row['cnt']. ')';
							}
						}
						
						if ($sectorsPicked) {
							self::setupSectorCheckboxes($fields['sectors/remove_sectors']);
							$fields['sectors/remove_sectors']['multiple_edit'] = [];
						} else {
							$fields['sectors/remove_sectors']['hidden'] = true;
						}
						
						$items = [];
						foreach ($field['values'] as $id => &$value) {
							if (!isset($value['marked'])) {
								//$value['label'] .= ' (0/'. $total. ')';
								
								if (isset($fields['sectors/remove_sectors']['values'][$id])) {
									unset($fields['sectors/remove_sectors']['values'][$id]);
								}
							} else {
								if ($sectorsPicked) {
									$fields['sectors/remove_sectors']['values'][$id]['label'] = $value['label'];
									$items[] = $id;
								}
							}
						}
						$values['sectors/remove_sectors'] = ze\escape::in($items, false);
	
						if (ze\row::exists(ZENARIO_LOCATION_MANAGER_PREFIX . "sectors", [])) {
							$fields['sectors/no_sectors']['hidden'] = true;
						} else {
							$fields['sectors/sectors']['hidden'] = true;
							$fields['sectors/remove_sectors']['hidden'] = true;
						}
					}
				}
				
				
				$this->setTimezoneValues($fields['details/timezone']);
			
				

				break;
			case "zenario_location_manager__sector":
				ze\priv::exitIfNot('_PRIV_MANAGE_LOCATIONS');
				if ($_GET["refiner__sub_sectors"] ?? false) {
					$box['key']['parent_id'] = $_GET["refiner__sub_sectors"] ?? false;
				}
			
				if ($box['key']['id']) {
					$sectorDetails = self::getSectorDetails($box['key']['id']);
                  
                 	$box['title'] = ze\admin::phrase('Editing business sector "[[name]]"',['name' => $sectorDetails['name']]);
                        
					
                    $values['details/name'] = $sectorDetails['name'];
                  
                    $box['tabs']['details']['edit_mode']['on'] = false;
                    $box['tabs']['details']['edit_mode']['use_view_and_edit_mode'] = true;
                }
				
				break;
			case "zenario_location_manager__score":
				if ($box['key']['id']) {
					$scoreDetails = self::getScoreDetails($box['key']['id']);
                    
                    $values['details/name'] = $scoreDetails['name'];
				}
				
				break;
			case "zenario_content":
				if (isset($_GET['refiner__zenario__locations__create_content']) || (($_GET["refinerName"] ?? false)=="refiner__zenario__locations__create_content")) {
					$fields['meta_data/content_summary']['hidden'] = true;
					$fields['meta_data/lock_summary_view_mode']['hidden'] = true;
					$fields['meta_data/lock_summary_edit_mode']['hidden'] = true;
					$fields['meta_data/desc_location_specific']['hidden'] = false;
				} else {
					if ($box['key']['cID']) {
						if ($locationId = ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX . "locations", 'id',
									["equiv_id" => $box['key']['cID'], "content_type" => $box['key']['cType']])) {
							$fields['meta_data/content_summary']['hidden'] = true;
							$fields['meta_data/lock_summary_view_mode']['hidden'] = true;
							$fields['meta_data/lock_summary_edit_mode']['hidden'] = true;
							$fields['meta_data/desc_location_specific']['hidden'] = false;
							if ($locationDetails = self::getLocationDetails($locationId)) {
								$fields['meta_data/desc_location_specific']['snippet']['html'] = 
									ze\admin::phrase("This content item is associated with a location \"[[location]]\". The location's summary will be used.", 
										['location' => $locationDetails['description']] );
							}
						} else {
							$fields['meta_data/content_summary']['hidden'] = false;
							$fields['meta_data/desc_location_specific']['hidden'] = true;
						}
					} else {
						$fields['meta_data/desc_location_specific']['hidden'] = true;
					}
				} 
				break;
			case 'site_settings':
			    if ($settingGroup == 'zenario_location_manager__site_settings_group') {
			        $methods = static::getMapPinPlacementMethods();
			        $i = 0;
			        foreach ($methods as $method => $label) {
			            if (!$values['zenario_location_manager__default_pin_placement_method']) {
                            $values['zenario_location_manager__default_pin_placement_method'] = $method;
                        }
			            $fields['zenario_location_manager__default_pin_placement_method']['values'][$method] = [
			                'label' => $label,
			                'ord' => ++$i
			            ];
			        }
			    }
			    break;
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case "zenario_location_manager__location":
				if (ze::setting('google_maps_api_key')) {
					$mapEdit = "<iframe id=\"google_map_iframe\" name=\"google_map_iframe\" src=\"" . 
							htmlspecialchars($this->showFileLink("	&map_center_lat=" . ($values['details/map_center_lat'] ?? false) . "
																	&map_center_lng=" . ($values['details/map_center_lng'] ?? false) . "
																	&marker_lat=" . ($values['details/marker_lat'] ?? false) . "
																	&marker_lng=" . ($values['details/marker_lng'] ?? false) . "
																	&zoom=" . ($values['details/zoom'] ?? false)) . "
																	&editmode=1") . "\" 
																	style=\"width: 425px;height: 425px;border: none;\"></iframe>\n";
					$mapView = "<iframe id=\"google_map_iframe\" name=\"google_map_iframe\" src=\"" . 
							htmlspecialchars($this->showFileLink("	&map_center_lat=" . ($values['details/map_center_lat'] ?? false) . "
																	&map_center_lng=" . ($values['details/map_center_lng'] ?? false) . "
																	&marker_lat=" . ($values['details/marker_lat'] ?? false) . "
																	&marker_lng=" . ($values['details/marker_lng'] ?? false) . "
																	&zoom=" . ($values['details/zoom'] ?? false)) . "
																	&editmode=0") . "\" style=\"width: 425px;height: 425px;border: none;\"></iframe>\n";

					$fields['details/map_edit']['snippet']['html'] =  $mapEdit;
					$fields['details/map_view']['snippet']['html'] =  $mapView;
				}

				$countryValue = $values['details/country'];
				if ($countryValue) {
					$regions = zenario_country_manager::getRegions("all",$countryValue);
					
					if (!empty($regions)) {
						
						$fields['details/region']['hidden'] = "No";
						$fields['details/region']['pick_items']['path'] = 'zenario__languages/panels/countries/item//' . $countryValue . '//';
						
						if (!($regionsCountry = zenario_country_manager::getCountryOfRegion($values['details/region']))
						 || !($countryValue == $regionsCountry['id'])) {
							$values['details/region'] = '';
						} 
					} else {
						$fields['details/region']['hidden'] = "Yes";
					}
				} else {
					$fields['details/region']['hidden'] = "Yes";
				}
				break;
			case "zenario_location_manager__locations_multiple_edit":
				$countryValue = $values['details/country'];

				if ($countryValue) {
					$regions = zenario_country_manager::getRegions("all",$countryValue);
					
					if (sizeof($regions)>0) {
						
						$fields['details/region']['hidden'] = false;
						$fields['details/region']['pick_items']['path'] = 'zenario__languages/panels/countries/item//' . $countryValue . '//';
						
						if (!($regs = zenario_country_manager::getRegions('active',$countryValue, false, $values['details/region']))){
							$values['details/region'] = '';
						} 
					} else {
						$fields['details/region']['hidden'] = "Yes";
					}
				} else {
					$fields['details/region']['hidden'] = "Yes";
				}
				
				/*if ($path=="zenario_location_manager__locations_multiple_edit") {
					if (!$changes['details/country'] && !$changes['details/region']) {
						unset($values['details/region']);
					} elseif ($changes['details/country']) {
						unset($fields['details/region']['multiple_edit']);
					}
				}*/
				break;
		}
	}

	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case "zenario_location_manager__location":
				if (ze\ray::issetArrayKey($values,'details/email')){
					foreach(explode(',',$values['details/email']) as $emailAddress){
						if (!ze\ring::validateEmailAddress(trim($emailAddress))){
							$box['tabs']['details']['errors'][] = "The email address entered is not valid.";
							break;
						}
					}
				}
				if (!empty($values['external_id'])) {
					if (!$box['key']['id'] && ze\row::exists(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations', ['external_id' => $values['external_id']])) {
						$box['tabs']['details']['errors'][] = ze\admin::phrase('A location already exists with this external id.');
					} else {
						$oldLocationId = ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations', ['external_id'], ['id' => $box['key']['id']]);
						if (($oldLocationId['external_id'] != $values['external_id']) && (ze\row::exists(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations', ['external_id' => $values['external_id']]))) {
							$box['tabs']['details']['errors'][] = ze\admin::phrase('A location already exists with this external id.');
						}
					}
				}
				break;
			case "zenario_location_manager__locations_multiple_edit":
				foreach ($changes as $fieldName => $fieldValue) {
					if ($fieldValue==1) {
						if ($fieldName=="email") {
							if (ze\ray::issetArrayKey($values,'details/email')) {
								foreach(explode(',',$values['details/email']) as $emailAddress){
									if (!ze\ring::validateEmailAddress(trim($emailAddress))){
										$box['tabs']['details']['errors'][] = "The email address entered is not valid.";
										break;
									}
								}
							}
						}
						
					}
				}
				break;
			case "zenario_location_manager__sector":
				if ($box['tabs']['details']['edit_mode']['on']) {
					if ($values['details/name']) {
						if (!self::checkSectorNameUnique($values['details/name'],$box['key']['id'],$box['key']['parent_id'])) {
							$box['tabs']['details']['errors']['name_not_unique'] = "You must enter a unique Name";
						}
					}
				}
				break;
			case "zenario_location_manager__score":
				if ($box['tabs']['details']['edit_mode']['on']) {
					if ($values['details/name']) {
						if (!self::checkScoreNameUnique($values['details/name'],$box['key']['id'])) {
							$box['tabs']['details']['errors']['name_not_unique'] = "You must enter a unique Name";
						}
					}
				}
				break;
		}
	}

	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case "zenario_location_manager__location":
				ze\priv::exitIfNot('_PRIV_MANAGE_LOCATIONS');
				
				$saveValues = [
					'external_id' => $values['details/external_id'],
					'description' => $values['details/name'],
					'address1' => $values['details/address_line_1'],
					'address2' => $values['details/address_line_2'],
					'locality' => $values['details/locality'],
					'city' => $values['details/city'],
					'state' => $values['details/state'],
					'postcode' => $values['details/postcode'],
					'country_id' => $values['details/country'] ? $values['details/country'] : null,
					'latitude' => $values['details/marker_lat'] ? $values['details/marker_lat'] : null,
					'longitude' => $values['details/marker_lng'] ? $values['details/marker_lng'] : null,
					'hide_pin' => $values['details/hide_pin'],
					'timezone' => $values['details/timezone'],
					'map_center_latitude' => $values['details/map_center_lat'] ? $values['details/map_center_lat'] : null,
					'map_center_longitude' => $values['details/map_center_lng'] ? $values['details/map_center_lng'] : null,
					'map_zoom' => $values['details/zoom'] ? $values['details/zoom'] : null
				];
				
				// Save content item details
				$saveValues['equiv_id'] = null;
				$saveValues['content_type'] = null;
				if (!empty($values['content_item/content_item'])) {
					$cID = $cType = false;
					ze\content::getCIDAndCTypeFromTagId($cID, $cType, $values['content_item/content_item']);
					$equivID = ze\content::equivId($cID, $cType);
					
					$saveValues['equiv_id'] = $equivID;
					$saveValues['content_type'] = $cType;
				}
				
				ze\admin::setLastUpdated($saveValues, $creating = !$box['key']['id']);
				
				// Save location
				if ($creating) {
					$box['key']['id'] = self::createLocation($saveValues);
				} else {
					ze\row::update(ZENARIO_LOCATION_MANAGER_PREFIX . "locations", $saveValues, $box['key']['id']);
					ze\module::sendSignal('eventLocationUpdated', [$box['key']['id']]);
				}
				
				// Add regions to location
				if ($values['details/country'] && $values['details/region']){
					self::addRegionToLocation($values['details/region'],$box['key']['id']);
				} else {
					self::removeAllRegionsFromLocation($box['key']['id']);
				}
				
				// Add sectors to location
				if (ze\ring::engToBoolean($box['tabs']['sectors']['edit_mode']['on'] ?? false)) {
					if (!empty($values['sectors/sectors'])) {
						$this->setLocationSectors($box['key']['id'], explode(',', $values['sectors/sectors']));
					} else {
						ze\row::delete(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link', ['location_id' => $box['key']['id']]);
					}
				}
				
				// Save location images
				if (ze\ring::engToBoolean($box['tabs']['images']['edit_mode']['on'] ?? false) && isset($values['images/images'])) {
				
					$ord = 0;
					$sticky = 1;
					$usedImages = [];
					foreach (explode(',', $values['images/images']) as $image) {
						$imageId = 0;
						if ($filepath = ze\file::getPathOfUploadInCacheDir($image)) {
							$imageId = self::addImage($box['key']['id'], $filepath);
						} else {
							$imageId = (int) $image;
						}
						
						if ($imageId) {
							$imageFilenameAndUsage = ze\row::get('files', ['filename', 'usage'], ['id' => $imageId]);
							if ($imageFilenameAndUsage['usage'] == 'location') {
								$usedImages[$imageId] = true;
								ze\row::set(
									ZENARIO_LOCATION_MANAGER_PREFIX. 'location_images', 
									['ordinal' => ++$ord, 'sticky_flag' => $sticky], 
									['image_id' => $imageId, 'location_id' => $box['key']['id']]
								);
							} else {
								$newImageId = ze\file::copyInDatabase('location', $imageId, $imageFilenameAndUsage['filename']);
								$usedImages[$newImageId] = true;
									ze\row::set(
									ZENARIO_LOCATION_MANAGER_PREFIX. 'location_images', 
									['ordinal' => ++$ord, 'sticky_flag' => $sticky], 
									['image_id' => $newImageId, 'location_id' => $box['key']['id']]
								);
							}
							
							$sticky = 0;
						}
					}
					
					foreach (self::locationsImages($box['key']['id']) as $imageId) {
						if (empty($usedImages[$imageId])) {
							self::deleteImage($box['key']['id'], $imageId);
						}
					}
				}
				
				break;
			case "zenario_location_manager__locations_multiple_edit":
				ze\priv::exitIfNot('_PRIV_MANAGE_LOCATIONS');
				$fieldsToChangeSQL = [];
				
				foreach ($changes as $fieldName => $fieldValue) {
					if ($fieldValue==1) {
						if ($fieldName=="name") {
							$fieldsToChangeSQL[] = "description = '" . ze\escape::sql($values['details/name']) . "'";
						}
					
						if ($fieldName=="address_line_1") {
							$fieldsToChangeSQL[] = "address1 = '" . ze\escape::sql($values['details/address_line_1']) . "'";
						}

						if ($fieldName=="address_line_2") {
							$fieldsToChangeSQL[] = "address2 = '" . ze\escape::sql($values['details/address_line_2']) . "'";
						}

						if ($fieldName=="locality") {
							$fieldsToChangeSQL[] = "locality = '" . ze\escape::sql($values['details/locality']) . "'";
						}

						if ($fieldName=="city") {
							$fieldsToChangeSQL[] = "city = '" . ze\escape::sql($values['details/city']) . "'";
						}
						
						if ($fieldName=="state") {
							$fieldsToChangeSQL[] = "state = '" . ze\escape::sql($values['details/state']) . "'";
						}

						if ($fieldName=="postcode") {
							$fieldsToChangeSQL[] = "postcode = '" . ze\escape::sql($values['details/postcode']) . "'";
						}

						if ($fieldName=="timezone") {
							$fieldsToChangeSQL[] = "timezone = '" . ze\escape::sql($values['details/timezone']) . "'";
						}

						if ($fieldName=="country") {
							$fieldsToChangeSQL[] = "country_id = '" . ($values['details/country']?$values['details/country']:null) . "'";
							$locationIds = explode(",",$box['key']['id']);
							foreach ($locationIds as $locationId) {
								$locationDetails = self::getLocationDetails($box['key']['id']);
								if(!($values['details/country'] == $locationDetails['country_id'])) {
									self::removeAllRegionsFromLocation($locationId);
								}
							}
						}
										
						if ($fieldName=="region") {
							$locationIds = explode(",",$box['key']['id']);
						
							foreach ($locationIds as $locationId) {
								if ($values['details/region']){
									self::addRegionToLocation($values['details/region'],$locationId);
								} else {
									self::removeAllRegionsFromLocation($locationId);
								}
							}
						}
						

						if ($fieldName=="content_item") {
							if ($values['content_item/content_item']) {
								$contentItemArray = explode("_",$values['content_item/content_item']);

								$fieldsToChangeSQL[] = "equiv_id = " . ($contentItemArray[1] ?? false);
								$fieldsToChangeSQL[] = "content_type = '" . ze\escape::asciiInSQL($contentItemArray[0] ?? false) . "'";
							} else {
								$fieldsToChangeSQL[] = "equiv_id = null";
								$fieldsToChangeSQL[] = "content_type = null";
							}
						}
						
						if ($fieldName=="map") {
							if ($values['details/map']=="clear") {
								$fieldsToChangeSQL[] = "latitude = null";
								$fieldsToChangeSQL[] = "longitude = null";
								$fieldsToChangeSQL[] = "map_center_latitude = null";
								$fieldsToChangeSQL[] = "map_center_longitude = null";
								$fieldsToChangeSQL[] = "map_zoom = null";
							}
						}
						
						$fieldsToChangeSQL[] = "last_edited = '" . ze\date::now() . "'";
						$fieldsToChangeSQL[] = "last_edited_admin_id = " . ze\admin::id();
						$fieldsToChangeSQL[] = "last_edited_user_id = null";
						$fieldsToChangeSQL[] = "last_edited_username = null";
					}
				}
				
				if (!empty($fieldsToChangeSQL)) {
					$sql = "UPDATE " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations
							SET ";

					$sql .= implode(",",$fieldsToChangeSQL);
							
					$sql .= " WHERE id IN (" . ze\escape::in($box['key']['id'], 'numeric') . ")";
					
					$result = ze\sql::update($sql);
				}

				if (ze\ring::engToBoolean($box['tabs']['sectors']['edit_mode']['on'] ?? false)) {
					if ($changes['sectors/sectors']) {
						$locationIds = explode(",",$box['key']['id']);
						foreach ($locationIds as $locationId) {
							foreach (explode(',', $values['sectors/sectors']) as $id) {
								if ($id) {
									if (!ze\row::exists(ZENARIO_LOCATION_MANAGER_PREFIX .'location_sector_score_link', ['sector_id' => $id, 'location_id' => $locationId])) {
										ze\row::insert(ZENARIO_LOCATION_MANAGER_PREFIX .'location_sector_score_link', ['sector_id' => $id, 'location_id' => $locationId, 'score_id' => 3, "sticky_flag" => 0]);
									}
								}
							}
						}
					}
					
					if ($changes['sectors/remove_sectors']) {
						$locationIds = explode(",",$box['key']['id']);
						$removeFromSectors = array_flip(explode(',', $values['sectors/remove_sectors']));
						
						foreach ($fields['sectors/remove_sectors']['values'] as $id => $value) {
							if (!isset($removeFromSectors[$id])) {
								foreach ($locationIds as $locationId) {
									ze\row::delete(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link', ['sector_id' => $id, 'location_id' => $locationId]);
								}
							}
						}
					}
				}

			
				break;
			case "zenario_location_manager__sector":
				if ($box['tabs']['details']) {
					$saveValues = [];
				
					$saveValues['parent_id'] = $box['key']['parent_id'];
					
					$saveValues['name'] = $values['details/name'];
					
					$box['key']['id'] = ze\row::set(ZENARIO_LOCATION_MANAGER_PREFIX . "sectors",$saveValues,["id" => $box['key']['id']]);
				}
				break;
			case "zenario_location_manager__score":
				if ($box['tabs']['details']) {
					$saveValues = [];
				
					$saveValues['name'] = $values['details/name'];
					
					$box['key']['id'] = ze\row::set(ZENARIO_LOCATION_MANAGER_PREFIX . "scores",$saveValues,["id" => $box['key']['id']]);
				}
				break;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__locations/panel':
				if (!empty($_GET['action'])) {
					switch ($_GET['action']){
						case 'delete_location':
							ze\priv::exitIfNot('_PRIV_MANAGE_LOCATIONS');
							$msg = "";
						
							$locationDetails = self::getLocationDetails($_GET['id']);
					
							$msg = "Are you sure you wish to delete the Location \"" . $locationDetails['description'] . "\"?";

							$result = ze\row::query(ZENARIO_LOCATION_MANAGER_PREFIX . "locations","id",["parent_id" => $_GET['id']]);
							
							$childLocations = 0;
							$childLocationsWithChildren = 0;
							
							if ($childLocations = ze\sql::numRows($result)) {
								while ($row = ze\sql::fetchAssoc($result)) {
									$result2 = ze\row::query(ZENARIO_LOCATION_MANAGER_PREFIX . "locations","id",["parent_id" => $row['id']]);
									
									if (ze\sql::numRows($result2)>0) {
										$childLocationsWithChildren++;
									}
								}
							}
					
							$childLocationsWithNoChildren = $childLocations-$childLocationsWithChildren;
					
							if ($childLocationsWithNoChildren) {
								if ($childLocationsWithNoChildren==1) {
									$locationLocations = "Location";
									$thisThese = "This";
								} else {
									$locationLocations = "Locations";
									$thisThese = "These";
								}
							
								$msg .= "\n\nThis Location has " . $childLocationsWithNoChildren . " child " . $locationLocations . " with no children. 
											" . $thisThese . " child " . $locationLocations . " will no longer be visible within the Hierarchy but can be found in the flat Locations view.";
							}
					
							if ($childLocationsWithChildren) {
								if ($childLocationsWithChildren==1) {
									$locationLocations = "Location";
									$thisThese = "This";
								} else {
									$locationLocations = "Locations";
									$thisThese = "These";
								}

								$msg .= "\n\nThis Location has " . $childLocationsWithChildren . " child " . $locationLocations . " that have children. " . $thisThese . " " . $locationLocations . " will remain in the Hierarchy but will be moved to the top level.";
							}
					
							echo $msg;
							
							break;
					}
				} elseif (!empty($_POST['action'])) {
					switch ($_POST['action']){
						case 'delete_location':
							ze\priv::exitIfNot('_PRIV_MANAGE_LOCATIONS');
							$IDs = explode(',',$ids);
							foreach ($IDs as $ID){
								self::deleteLocation($ID);
							}
							break;
						case 'convert_to_simple_place':
							if (ze\module::isRunning('zenario_organization_manager')
								&& ze::setting('zenario_organization_manager__split_locations')
								&& ze\priv::check('_PRIV_MANAGE_LOCATIONS')
							){
								$organizationFlagId = ze::setting('zenario_organization_manager__organization_flag_id');
								$organizationFlag = ze\dataset::fieldDetails($organizationFlagId);
								if ($organizationFlag) {
									$sql = ' UPDATE ' . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'locations_custom_data
									SET `' . ze\escape::sql($organizationFlag['db_column']) . '` = 0
									WHERE location_id IN (' . ze\escape::in($ids, true) . ')';
									ze\sql::update($sql);
								}
							}
							break;
						case 'convert_to_organization':
							if (ze\module::isRunning('zenario_organization_manager')
								&& ze::setting('zenario_organization_manager__split_locations')
								&& ze\priv::check('_PRIV_MANAGE_LOCATIONS')
							){
								$organizationFlagId = ze::setting('zenario_organization_manager__organization_flag_id');
								$organizationFlag = ze\dataset::fieldDetails($organizationFlagId);
								if ($organizationFlag) {
									$sql = ' UPDATE ' . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'locations_custom_data
									SET `' . ze\escape::sql($organizationFlag['db_column']) . '` = 1
									WHERE location_id IN (' . ze\escape::in($ids, true) . ')';
									ze\sql::update($sql);
								}
							}
							break;
						case 'mark_location_as_pending':
							ze\priv::exitIfNot('_PRIV_MANAGE_LOCATIONS');
							$IDs = explode(',',$ids);
							foreach ($IDs as $ID){
								self::markLocationAsPending($ID);
							}
							break;
						case 'activate_location':
							ze\priv::exitIfNot('_PRIV_MANAGE_LOCATIONS');
							$IDs = explode(',',$ids);
							foreach ($IDs as $ID){
								self::activateLocation($ID);
							}
							break;
						case 'suspend_location':
							ze\priv::exitIfNot('_PRIV_MANAGE_LOCATIONS');
							$IDs = explode(',',$ids);
							foreach ($IDs as $ID){
								self::suspendLocation($ID);
							}
							break;
						case 'add_location':
							ze\priv::exitIfNot('_PRIV_MANAGE_LOCATIONS');
							if ($refinerName=="sector_locations") {
								$IDs = explode(',',$ids);
								foreach ($IDs as $ID){
									self::addLocationToSector($ID,$refinerId);
								}
							}
							break;
						case 'remove_location':
							ze\priv::exitIfNot('_PRIV_MANAGE_LOCATIONS');
							if ($refinerName=="sector_locations") {
								$IDs = explode(',',$ids);
								foreach ($IDs as $ID){
									self::removeLocationFromSector($ID,$refinerId);
								}
							}
							break;
						case 'add_child_location':
							ze\priv::exitIfNot('_PRIV_MANAGE_LOCATIONS');
							$limit=100;
							$level1=0;
							$parent = $refinerId;
							$maxLevels = (int) ze::setting('zenario_location_manager__hierarchy_levels');
							while(1){
								if (!$parent){
									break;
								}
								if (in_array((int)$parent,explode(",",$ids),false)) {
									echo 'Error. A Location cannot be moved beneath itself in the hierarchy. Please choose a different parent Location.';
									return;
								}
								if (!--$limit){
									echo 'Error. The Location hierarchy data in your database seems to be corrupted.';
									return;
								}
								$level1++;
								$parent = ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations','parent_id',['id'=>(int)$parent]);
							}
							foreach (explode(",",$ids) as $id) {
								$level2=0;
								$this->exploreTreeDown($id,$level2,$maxLevels);
								if ($level1+$level2>$maxLevels) {
									echo 'Error. Number of allowed levels in the Location hierarchy exceeded.';
									return;
								}
							}
							foreach (explode(",",$ids) as $id) {
								ze\row::update(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations',['parent_id'=>(int)$refinerId],['id'=>(int)$id]);
							}
							break;
						case 'assign_new_parent':
							ze\priv::exitIfNot('_PRIV_MANAGE_LOCATIONS');
							foreach (explode(",",$ids) as $id) {
								$limit=100;
								$level1=0;
								$parent = $ids2;
								$maxLevels = (int) ze::setting('zenario_location_manager__hierarchy_levels');
								while(1){
									if (!$parent){
										break;
									}
									if ($parent==$id) {
										echo 'Error. A Location cannot be both a descendant and an ancestor. Please choose a different Location to be the parent.';
										return;
									}
									if (!--$limit){
										echo 'Error. The Location hierarchy data in your database seems to be corrupted.';
										return;
									}
									$level1++;			
									$parent = ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations','parent_id',['id'=>(int)$parent]);
								
								}
								$level2=0;
								$this->exploreTreeDown($id,$level2,$maxLevels);
								if ($level1+$level2>$maxLevels) {
									echo 'Error. Number of allowed levels in the Location hierarchy exceeded.';
									return;
								}
								ze\row::update(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations',['parent_id'=>(int)$ids2],['id'=>(int)$id]);
							}
							break;
						case 'make_orphan':
							ze\priv::exitIfNot('_PRIV_MANAGE_LOCATIONS');
							ze\row::update(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations',['parent_id'=>null],['id'=>(int)$ids]);
							break;
						
						case 'geocode_locations':
							$locationIds = explode(',', $ids);
							
							$report = ['overQueryLimit' => false, 'processed' => 0, 'succeeded' => 0, 'errors' => []];
							$description = false;
							
							foreach ($locationIds as $locationId) {
								$report['processed']++;
								$location = ze\row::get(
									ZENARIO_LOCATION_MANAGER_PREFIX . 'locations', 
									['description', 'address1', 'address2', 'locality', 'city', 'country_id', 'postcode', 'latitude', 'longitude'], 
									$locationId
								);
								
								$description = $location['description'];
								
								// Ignore locations that already have coordinates
								if ($location['latitude'] && $location['longitude']) {
									static::recordLocationGeocodeErrorInReport($report, $description, 'latLngSet');
									continue;
								}
								unset($location['description'], $location['latitude'], $location['longitude']);
								
								if ($location) {
									$addressString = implode(',', $location);
									if (!empty($location['postcode'])) {
										$addressString .= '&components=postal_code:' . $location['postcode'];
									}
									
									if ($addressString) {
										$response = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($addressString) . '&sensor=true');
										$response = json_decode($response, true);
										
										// Responses must have one of the statuses below
										if (empty($response['status'])) {
											static::recordLocationGeocodeErrorInReport($report, $description, 'invalidResponses');
										// Over query limit (2500 per day, 10 per second)
										} elseif ($response['status'] == 'OVER_QUERY_LIMIT') {
											$report['processed']--;
											$report['overQueryLimit'] = true;
											break;
										// No location found from address
										} elseif ($response['status'] == 'ZERO_RESULTS') {
											static::recordLocationGeocodeErrorInReport($report, $description, 'zeroResults');
										// Request was denied
										} elseif ($response['status'] == 'REQUEST_DENIED') {
											static::recordLocationGeocodeErrorInReport($report, $description, 'deniedRequests');
										// Invalid address sent
										} elseif ($response['status'] == 'INVALID_REQUEST') {
											static::recordLocationGeocodeErrorInReport($report, $description, 'invalidRequests');
										// Server error, may succeed if tried again
										} elseif ($response['status'] == 'UNKNOWN_ERROR') {
											static::recordLocationGeocodeErrorInReport($report, $description, 'unknownErrors');
										// 1 or more addresses successfully returned
										} elseif ($response['status'] == 'OK') {
											$report['succeeded']++;
											if (!empty($response['results'][0]['geometry']['location']['lat']) 
												&& !empty($response['results'][0]['geometry']['location']['lng']) 
											) {
												ze\row::update(
													ZENARIO_LOCATION_MANAGER_PREFIX . 'locations', 
													[
														'latitude' => $response['results'][0]['geometry']['location']['lat'],
														'longitude' => $response['results'][0]['geometry']['location']['lng'],
														'map_center_latitude' => $response['results'][0]['geometry']['location']['lat'],
														'map_center_longitude' => $response['results'][0]['geometry']['location']['lng'],
														'map_zoom' => 16
													],
													$locationId
												);
											}
										} else {
											static::recordLocationGeocodeErrorInReport($report, $description, 'unknownStatusCodes');
										}
									}
								}
							}
							
							// Output report
							$locationCount = count($locationIds);
							if ($report['succeeded'] == $locationCount) {
								echo '<!--Message_Type:Success-->';
							}
							
							if ($locationCount == 1 && ($report['succeeded'] == $locationCount)) {
								echo 'Successfully placed the location "' . $description . '" on the map';
							} else {
								echo $report['succeeded'] . ' out of ' . $locationCount . ' locations have been successfully geocoded.';
								
								if (!empty($report['errors']['latLngSet']['count'])) {
									$count = $report['errors']['latLngSet']['count'];
									echo '<br><br>Coordinates already set for the location "' . $report['errors']['latLngSet']['first'] . '"';
									if ($count > 1) {
										echo ' and ' . ($count - 1) . ' other' . (($count - 1 != 1) ? 's' : '');
									}
									echo '.';
								}
								if (!empty($report['errors']['invalidResponses']['count'])) {
									$count = $report['errors']['invalidResponses']['count'];
									echo '<br><br>Invalid response returned for the location "' . $report['errors']['invalidResponses']['first'] . '"';
									if ($count > 1) {
										echo ' and ' . ($count - 1) . ' other' . (($count - 1 != 1) ? 's' : '');
									}
									echo '.';
								}
								if (!empty($report['errors']['zeroResults']['count'])) {
									$count = $report['errors']['zeroResults']['count'];
									echo '<br><br>Unable to get address coordinates for the location "' . $report['errors']['zeroResults']['first'] . '"';
									if ($count > 1) {
										echo ' and ' . ($count - 1) . ' other' . (($count - 1 != 1) ? 's' : '');
									}
									echo ' (Error code "ZERO_RESULTS").';
								}
								if (!empty($report['errors']['deniedRequests']['count'])) {
									$count = $report['errors']['deniedRequests']['count'];
									echo '<br><br>Request deined for the location "' . $report['errors']['deniedRequests']['first'] . '"';
									if ($count > 1) {
										echo ' and ' . ($count - 1) . ' other' . (($count - 1 != 1) ? 's' : '');
									}
									echo ' (Error code "REQUEST_DENIED").';
								}
								if (!empty($report['errors']['invalidRequests']['count'])) {
									$count = $report['errors']['invalidRequests']['count'];
									echo '<br><br>Invalid request for the location "' . $report['errors']['invalidRequests']['first'] . '"';
									if ($count > 1) {
										echo ' and ' . ($count - 1) . ' other' . (($count - 1 != 1) ? 's' : '');
									}
									echo ' (Error code "INVALID_REQUEST").';
								}
								if (!empty($report['errors']['unknownErrors']['count'])) {
									$count = $report['errors']['unknownErrors']['count'];
									echo '<br><br>Unknown error returned for the location "' . $report['errors']['unknownErrors']['first'] . '"';
									if ($count > 1) {
										echo ' and ' . ($count - 1) . ' other' . (($count - 1 != 1) ? 's' : '');
									}
									echo ' (Error code "UNKNOWN_ERROR").';
								}
								if (!empty($report['errors']['unknownStatusCodes']['count'])) {
									$count = $report['errors']['unknownStatusCodes']['count'];
									echo '<br><br>Unknown request status returned for the location "' . $report['errors']['unknownStatusCodes']['first'] . '"';
									if ($count > 1) {
										echo ' and ' . ($count - 1) . ' other' . (($count - 1 != 1) ? 's' : '');
									}
									echo '.';
								}
								if ($report['overQueryLimit']) {
									echo '<br><br>Query limit reached. Processed ' . $report['processed'] . '/' . $locationCount . ' locations (Error code "OVER_QUERY_LIMIT").';
								}
							}
							break;
					}
				}
				break;
			case 'zenario__locations/nav/sectors/panel':
				switch ($_POST['action']){
					case 'delete_sector':
						$IDs = explode(',',$ids);
						foreach ($IDs as $ID){
							self::deleteSector($ID);
						}
						break;
				}
			case 'zenario__locations/location_sectors/panel':
				switch (ze::post('action')){
					case 'remove_sector':
						$IDs = explode(',',$ids);
						foreach ($IDs as $ID){
							self::removeSector($refinerId, $ID);
						}
						break;
					case 'increase_score':
						$IDs = explode(',',$ids);
						foreach ($IDs as $ID){
							self::changeScore($refinerId,$ID,"increase");
						}
						break;
					case 'decrease_score':
						$IDs = explode(',',$ids);
						foreach ($IDs as $ID){
							self::changeScore($refinerId,$ID,"decrease");
						}
						break;
					case 'add_sector':
						$IDs = explode(',',$ids);

						$sql = "SELECT sector_id,
									sticky_flag
								FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link
								WHERE location_id = " . (int) $refinerId;
									
						$result = ze\sql::select($sql);
						
						$locationSectorCount = ze\sql::numRows($result);
						
						$locationSectors = [];
						$stickyFlagSet = false;
						
						if ($locationSectorCount>0) {
							while ($row = ze\sql::fetchAssoc($result)) {
								$locationSectors[] = $row['sector_id'];
								
								if ($row['sticky_flag']) {
									$stickyFlagSet = true;
								}
							}
						}

						if (sizeof($IDs)>1) {
							$stickyFlagSet = true;
						}
					
						foreach ($IDs as $ID){
							$addSector = false;
						
							if (sizeof($locationSectors)==0) {
								$addSector = true;
							} else {
								if (!in_array($ID,$locationSectors)) {
									$addSector = true;
								}
							}
						
							if ($addSector) {
								$this->addSectorToLocation($ID, $refinerId, $stickyFlagSet, $locationSectorCount, $locationSectors);
							}
						}
						break;
					case 'make_sticky':
						$IDs = explode(',',$ids);
						foreach ($IDs as $ID){
								$sql = "UPDATE " . DB_PREFIX . TRIBIQ_LOCATION_MANAGER_PREFIX . "location_sector_score_link
										SET sticky_flag = 0
										WHERE location_id = " . (int) $refinerId;
										
								$result = ze\sql::update($sql);
								
								$sql = "UPDATE " . DB_PREFIX . TRIBIQ_LOCATION_MANAGER_PREFIX . "location_sector_score_link
										SET sticky_flag = 1
										WHERE location_id = " . (int) $refinerId . "
											AND sector_id = " . (int) $ID;
										
								$result = ze\sql::update($sql);		
						}
						break;
			}
		}
	}
	
	private static function recordLocationGeocodeErrorInReport(&$report, $description, $errorCode) {
		if (!isset($report['errors'][$errorCode])) {
			$report['errors'][$errorCode] = ['count' => 0, 'first' => false];
		}
		$report['errors'][$errorCode]['count']++;
		if ($report['errors'][$errorCode]['first'] === false) {
			$report['errors'][$errorCode]['first'] = $description;
		}
	}
	
	public function showFile() {
		echo '<html>
				<head>
				<script id="google_api" type="text/javascript" src="' . ze\link::protocol() . 'maps.google.com/maps/api/js?key=' . urlencode(ze::setting('google_maps_api_key')) . '"></script>
				<script type="text/javascript" src="modules/zenario_location_manager/js/locations.js"></script>
				</head>
				<script type="text/javascript">
				
				defaultMapCentreLat = 0;
				defaultMapCentreLng = 0;
				defaultMapZoom = 1;
				';
		
		if (($_GET["map_center_lat"] ?? false) && ($_GET["map_center_lng"] ?? false) && ($_GET["zoom"] ?? false)) {		
			echo 'defaultMapCentreLat = ' . ($_GET["map_center_lat"] ?? false) . ';
				defaultMapCentreLng = ' . ($_GET["map_center_lng"] ?? false) . ';
				defaultMapZoom = ' . ($_GET["zoom"] ?? false) . ';';
		}
		
		if (($_GET["marker_lat"] ?? false) && ($_GET["marker_lng"] ?? false)) {		
			echo 'markerLat = ' . ($_GET["marker_lat"] ?? false) . ';
				markerLng = ' . ($_GET["marker_lng"] ?? false) . ';';
		}		
		
		if ($_GET["editmode"] ?? false) {
			echo 'editMode = true;';
		}
		
		echo '					
				</script>
				<body onload="init()">
				
				<div id="map" style="width: 400px;height: 400px;"></div>
				
				</body>
				</html>';
	}

	public static function getLocationHierarchyPathAndDeepLinks ($locationId,$output=["path" => "","link" => ""],$pathSeparator=" › ",$linkSeparator="//item//",$recurseCount=0) {
		$locationDetails = self::getLocationDetails($locationId);
		
		$output['path'] = ($locationDetails['description'] ?? false) . $pathSeparator . $output['path'];
		$output['link'] = $linkSeparator . $locationId . $output['link'];

		if (ze\ray::issetArrayKey($locationDetails,'parent_id')) {
			$recurseCount++;
			
			if ($recurseCount<100) {
				$output = self::getLocationHierarchyPathAndDeepLinks($locationDetails['parent_id'],$output);
			}
		}
		
		if (substr($output['path'],strlen($output['path']) - strlen($pathSeparator))==$pathSeparator) {
			$output['path'] = substr($output['path'],0,strlen($output['path']) - strlen($pathSeparator));
		}

		if (substr($output['link'],strlen($output['link']) - strlen($linkSeparator))==$linkSeparator) {
			$output['link'] = substr($output['link'],0,strlen($output['link']) - strlen($linkSeparator));
		}
		
		return $output;
	}

	public static function getLocationsNamesIndexedById($status='active'){
		$rv = [];
		$sql = 'SELECT 
					id,
					description 
				FROM ' 
					. DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'locations ';
		if ($status){
			$sql .= " WHERE
							status = '" . ze\escape::sql($status) . "'" ;
		}
		$sql .='
				ORDER BY 
					description ASC';
		
		if (ze\sql::numRows($result = ze\sql::select($sql))>=1){
			while ($row = ze\sql::fetchAssoc($result)) {
				$rv[$row['id']] = $row['description'];
			}
		}
		return $rv;
	}

	public static function getLocationsNamesIndexedByIdOrderedByName($status='active'){
		$rv = [];
		$sql = 'SELECT 
					id,
					description 
				FROM ' 
					. DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'locations ';
		if ($status){
			$sql .= " WHERE
							status = '" . ze\escape::sql($status) . "'" ;
		}
		$sql .='
				ORDER BY 
					description ASC';
		if (ze\sql::numRows($result = ze\sql::select($sql))>=1){
			while ($row = ze\sql::fetchAssoc($result)) {
				$rv[$row['id']] = $row['description'];
			}
		}
		return $rv;
	}
	
	public static function getLocationDetails($ID){
		$rv = [];
		$sql = 'SELECT loc.id,
					loc.parent_id,
					loc.external_id,
					loc.description,
					loc.address1,
					loc.address2,
					loc.locality,
					loc.city,
					loc.state,
					loc.postcode,
					loc.country_id,
					loc.latitude,
					loc.longitude,
					loc.map_zoom,
					loc.map_center_latitude,
					loc.map_center_longitude,
					loc.hide_pin,
					loc.timezone,
					loc.status,
					loc.equiv_id,
					loc.content_type,
					loc.created,
					loc.created_admin_id,
					loc.created_user_id,
					loc.created_username,
					loc.last_edited,
					loc.last_edited_admin_id,
					loc.last_edited_user_id,
					loc.last_edited_username,';
		
		//Companies
		if (ze\module::inc('zenario_company_locations_manager')) {
			$sql .= "
						c.company_name,";
		}
		
		//Custom data:
		$sql .= "
					cd.*";
		$sql.= '
				FROM ' . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'locations AS loc';
		
		//Companies
		if (ze\module::inc('zenario_company_locations_manager')) {
			$sql .= "
				LEFT JOIN ". DB_PREFIX. ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX. "company_location_link AS cll
				   ON cll.location_id = loc.id
				LEFT JOIN ". DB_PREFIX. ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX. "companies AS c
				   ON c.id = cll.company_id";
		}
		
		//Custom data:
		$sql .= "
			LEFT JOIN ". DB_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "locations_custom_data AS cd
			   ON cd.location_id = loc.id";
			   
		$sql .= '
			WHERE loc.id = ' . (int) $ID;
		
		if (ze\sql::numRows($result = ze\sql::select($sql))==1){
			$rv = ze\sql::fetchAssoc($result);
		}
		
		return $rv;
	}
	
	public static function getLocationIdFromContentItem ($cID, $cType) {
		if (!$cID || !$cType) {
			return false;
		}
		
		//$sql = "SELECT id
		//		FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations
		//		WHERE equiv_id = " . (int) $cID . "
		//			AND content_type = '" . ze\escape::asciiInSQL($cType) . "'";

		$sql = "SELECT l.id
				FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations AS l
				INNER JOIN " . DB_PREFIX . "content_items AS c1
				ON l.equiv_id = c1.id
				AND l.content_type = c1.type
				INNER JOIN (
					SELECT c2.equiv_id
					FROM " . DB_PREFIX . "content_items AS c2
					WHERE c2.id = " . (int) $cID . "
					AND c2.type = '" . ze\escape::asciiInSQL($cType) . "'
				) AS sub
				ON c1.equiv_id = sub.equiv_id";

					
		return ze\sql::fetchValue($sql);
	}
	
	public static function getChildSectorsIndexedByIdOrderedByName($parentId=0){
		$rv = [];
		$sql = "SELECT 
					id,
					parent_id,
					name
				FROM " 
					.DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "sectors 
				WHERE 
					" . ($parentId?"parent_id=" . (int)$parentId:" true") . "
				ORDER BY 
					name";
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)){
			$rv[$row['id']]=$row;
		}
		return $rv;
	}

	public static function getInmostLocationRegion($locationId){
		$sql = "
				SELECT 
					l1.region_id
				FROM 
					" . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_region_link l1
				LEFT JOIN
					(SELECT 
						l.region_id,
						r.parent_id,
						l.location_id
					FROM
						" . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_region_link l
					INNER JOIN
						" . DB_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions r
					ON
						l.region_id = r.id 
					WHERE
						l.location_id = " . (int) $locationId . "
					) l2
				ON
					l1.region_id = l2.parent_id
				WHERE
						l1.location_id = " . (int) $locationId . "
					AND l2.location_id IS NULL";

		$rv = ze\sql::select($sql);
		if ($result = ze\sql::fetchAssoc($rv)){
			return $result['region_id'];
		} else {
			return 0;
		}
	}

	public static function getSectorDetails($ID){
		$rv = [];
		$sql = 'SELECT 
					parent_id,
					name
				FROM ' .DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'sectors ';
		$sql .= 'WHERE id = ' . (int) $ID;
		if (ze\sql::numRows($result = ze\sql::select($sql))==1){
			$rv = ze\sql::fetchAssoc($result);
		}
		return $rv;
	}

	public static function getSectorPath($ID, $recurseCount = 0, $sectorArray=[]) {
		++$recurseCount;
		
		if ($recurseCount<=10) {
			$sector = self::getSectorDetails($ID);
		
			$sectorArray[] = $sector['name'];
			
			if ($sector['parent_id']) {
				$sectorArray = self::getSectorPath($sector['parent_id'],$recurseCount,$sectorArray);
			}
		}
		
		return $sectorArray;
	}

	public static function getScoreDetails($ID){
		$rv = [];
		$sql = 'SELECT name
				FROM ' .DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'scores ';
		$sql .= 'WHERE id = ' . (int) $ID;
		if (ze\sql::numRows($result = ze\sql::select($sql))==1){
			$rv = ze\sql::fetchAssoc($result);
		}
		return $rv;
	}

	public static function getLocationSectors($locationID) {
		$sectors = [];
		
		$sql = "SELECT sector_id
				FROM " .DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link
				WHERE location_id = " . (int) $locationID;
				
		$result = ze\sql::select($sql);
		
		if (ze\sql::numRows($result)>0) {
			return ze\sql::fetchValues($result);
		} else {
			return false;
		}
	}
	
	public static function getLocationSectorDetails($locationID, $sectorID){
		$rv = [];
		$sql = 'SELECT 
					score_id,
					sticky_flag
				FROM ' .DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link
				WHERE location_id = ' . (int) $locationID . '
					AND sector_id = ' . (int) $sectorID;
		if (ze\sql::numRows($result = ze\sql::select($sql))==1){
			$rv = ze\sql::fetchAssoc($result);
		}
		return $rv;
	}
	
	// Still used on choosewhere
	public static function getPrimarySectorDetails($locationID){
		$stickySector = ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link', 'sector_id', ['location_id' => $locationID, 'sticky_flag' => 1]);
		return $stickySector;
	}
	
	// Still used on choosewhere
	public static function getScoreSectorDetails($locationID, $sectorID){
		$sql = 'SELECT score_id
				FROM ' .DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link
				WHERE location_id = ' . (int) $locationID . '
					AND sector_id = ' . (int) $sectorID;
		if (ze\sql::numRows($result = ze\sql::select($sql))==1){
			return ze\sql::fetchAssoc($result);
		}
		return false;
	}

	
	//Create a new location
	public static function createLocation($details, $customDetails = []) {
		//When creating a new location, set status as pending if setting is set
		if (ze::setting('zenario_location_manager__enable_pending_status')) {
			$details['status'] = 'pending';
		}
		
		//Insert new location
		$locationId = ze\row::insert(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations', $details);
		
		//Add any custom dataset fields
		if ($customDetails) {
			$customDetails['location_id'] = $locationId;
			ze\row::insert(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations_custom_data', $customDetails);
		}
		
		ze\module::sendSignal('eventLocationCreated', ['locationId' => $locationId]);
		return $locationId;
	}
	
	public static function deleteLocation($locationId) {
		foreach (self::locationsImages($locationId) as $imageId) {
			self::deleteImage($locationId, $imageId);
		}
		
		ze\row::delete(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations', $locationId);
		ze\row::delete(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations_custom_data', $locationId);

		ze\module::sendSignal('eventLocationDeleted', [$locationId]);
	}

	public static function activateLocation($locationId) {
		ze\row::update(ZENARIO_LOCATION_MANAGER_PREFIX . "locations", ['status' => 'active'], $locationId);
	}

	public static function suspendLocation($locationId) {
		ze\row::update(ZENARIO_LOCATION_MANAGER_PREFIX . "locations", ['status' => 'suspended'], $locationId);
	}
	
	public static function markLocationAsPending($locationId) {
		ze\row::update(ZENARIO_LOCATION_MANAGER_PREFIX . "locations", ['status' => 'pending'], $locationId);
	}

	public static function deleteSector($ID, $recurseCount = 0){
		$recurseCount++;
	
		if ($recurseCount>10) {
			echo "Recursed beyond limit of 10 recursions. Exiting";
			exit;
		}
	
		$sql = 'DELETE 
				FROM ' .DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'sectors 
				WHERE id=' . (int)$ID;
				
		ze\sql::update($sql);

		$childSectors = self::getChildSectorsIndexedByIdOrderedByName($ID);
		
		if (sizeof($childSectors)>0) {
			foreach ($childSectors as $id => $childSector) {
				self::deleteSector($id,$recurseCount);
			}
		}

		ze\module::sendSignal('eventSectorDeleted', ["sectorId" => $ID]);
	}

	public static function removeSector($locationId,$sectorId=false){
		$sql = "SELECT id
				FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "sectors
				WHERE parent_id = " . (int) $sectorId;
				
		$result = ze\sql::select($sql);
		
		if (ze\sql::numRows($result)>0) {
			while ($row = ze\sql::fetchRow($result)) {
				$sql = "DELETE
						FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link
						WHERE location_id = " . (int) $locationId . "
							AND sector_id = " . (int) $row[0];
							
				$result2 = ze\sql::update($sql);
			}
		}
	
		$sql = 'DELETE 
				FROM ' .DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link 
				WHERE location_id = ' . (int) $locationId;
				
		if ($sectorId) {
			$sql .= ' AND sector_id = ' . (int) $sectorId;
		}
					
		ze\sql::update($sql);
	}

	public static function changeScore($locationID, $sectorID, $mode){
		$sql = "SELECT score_id
				FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link
				WHERE location_id = ' . (int) $locationID . '
					AND sector_id = ' . (int) $sectorID;
					
		$result = ze\sql::select($sql);
		
		$row = ze\sql::fetchRow($result);
		
		if ($mode=="increase") {
			if ($row[0]<5) {
				$sql = 'UPDATE ' .DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link
						SET score_id = score_id + 1
						WHERE location_id = ' . (int) $locationID . '
							AND sector_id = ' . (int) $sectorID;
			}
		}
	
		if ($mode=="decrease") {
			if ($row[0]>1) {
				$sql = 'UPDATE ' .DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link
						SET score_id = score_id - 1
						WHERE location_id = ' . (int) $locationID . '
							AND sector_id = ' . (int) $sectorID;
			}
		}
		
		ze\sql::update($sql);
	}
	
	public static function checkSectorNameUnique($name, $ID, $parentID) {
		$sql = "SELECT 
					id
				FROM " 
					. DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "sectors
				WHERE 
						name = '" . ze\escape::sql($name) . "' ";
		if ((int)$parentID){
			$sql .=" AND	parent_id = " . (int) $parentID;
		}
		
		if ($ID) {
			$sql .= " AND id <> " . (int) $ID;
		}
		
		$result = ze\sql::select($sql);
		
		if (ze\sql::numRows($result)>0) {
			return false;
		}
		return true;
	}
	
	public static function checkScoreNameUnique($name, $ID) {
		$sql = "SELECT id
				FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "scores
				WHERE name = '" . ze\escape::sql($name) . "'";
		
		if ($ID) {
			$sql .= " AND id <> " . (int) $ID;
		}
		
		$result = ze\sql::select($sql);
		
		if (ze\sql::numRows($result)>0) {
			return false;
		}
		return true;
	}
	
	public static function handleMediaUpload($fileVar, $maxSize, $locationId) {
		$error = [];
		if (isset($_FILES[$fileVar])) {
			
			if (!empty($_FILES[$fileVar]['error']) && ze::in($_FILES[$fileVar]['error'], 4, 'UPLOAD_ERR_NO_FILE')) {
				$error['document'] = ze\lang::phrase('Please select a file.');
			} else {
				if (ze\fileAdm::isUploadedFile($_FILES[$fileVar]['tmp_name'])) {
					if ($_FILES[$fileVar]['size'] > $maxSize) {
						$maxSize = ze\file::fileSizeConvert($maxSize);
						$error['document'] = ze\lang::phrase('Your file must be less than [[size]]', ['size' => $maxSize]);
					}
			
				} else {
					$maxSize = ze\file::fileSizeConvert($maxSize);
					switch ($_FILES[$fileVar]['error']) {
						case 1:
							$error['document'] = ze\lang::phrase('Your file must be less than [[size]]', ['size' => $maxSize]);
							break;
						case 2:
							$error['document'] = ze\lang::phrase('Your file must be less than [[size]]', ['size' => $maxSize]);
							break;
						case 3:
							$error['document'] = ze\lang::phrase('_FILE_UPLOAD_ERROR_PARTIAL_UPLOAD', ['size' => $maxSize]);
							break;
						default:
							$error['document'] = ze\lang::phrase('Please select a file.');
					}
					return $error;
				}
				
				ze\fileAdm::exitIfUploadError(true, false, true, $fileVar);
			}
		
			if (!count($error)) {
				$mimeType = ze\file::mimeType($_FILES[$fileVar]['name']);
				if ($mimeType == 'image/jpeg' || $mimeType == 'image/png') {
					return self::addImage($locationId, $_FILES[$fileVar]['tmp_name'], $_FILES[$fileVar]['name']);
				} else {
					$error['document'] = ze\lang::phrase('The file format must be a .jpg, .jpeg or .png.');
					return $error;
				}
			} else {
				return $error;
			}
		} else {
			return [ze\lang::phrase("_ERR_FILE_SIZE_GREATER_THAN_SERVER_LIMIT")];
		}
	}

		
	public static function makeLocationImageSticky($locationId, $imageId) {
		$sql = "
			UPDATE " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_images SET
				sticky_flag = 0
			WHERE location_id = " . (int) $locationId;
		ze\sql::update($sql);
		
		$sql = "
			UPDATE " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_images SET
				sticky_flag = 1
			WHERE location_id = " . (int) $locationId . "
			  AND image_id = ". (int) $imageId;
		ze\sql::update($sql);
	}
	
	public static function addLocationToSector($locationId, $sectorId, $recurseCount = 0) {
		$recurseCount++;
		
		if ($recurseCount>20) {
			echo "Recursed out of control. Exiting";
			exit;
		}
		
		$stickyFlag = 1;
		
		if ($locationSectors = self::getLocationSectors($locationId)) {
			$stickyFlag = 0;
		}
		
		ze\row::set(
			ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link",
			[
				"location_id" => $locationId, 
				"sector_id" => $sectorId, 
				"score_id" => 3, 
				"sticky_flag" => $stickyFlag
			],
			["location_id" => $locationId, "sector_id" => $sectorId]
		);
		
		$sectorDetails = self::getSectorDetails($sectorId);
		
		if (ze\ray::issetArrayKey($sectorDetails,"parent_id")) {
			self::addLocationToSector($locationId, $sectorDetails['parent_id'], $recurseCount);
		}
	}

	public static function addRegionToLocation($regionId, $locationId, $recurseCount=0) {
		$recurseCount++;
		
		if ($recurseCount==1) {
			self::removeAllRegionsFromLocation($locationId);
		}
		
		if ($recurseCount>20) {
			echo "Function addRegionToLocation() recursed beyond the limit of 20 recursions.";
			exit;
		}
	
		$region = zenario_country_manager::getRegionById($regionId);
		$sql = "INSERT IGNORE 
					" . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_region_link
				SET 
					location_id = " . (int) $locationId . ",
					region_id = " . (int)  $regionId;

		$result = ze\sql::update($sql);

		if ($region['parent_id']) {
			self::addRegionToLocation($region['parent_id'], $locationId, $recurseCount);
		}
	}

	public function addSectorToLocation ($sectorId, $locationId, $stickyFlagSet, $locationSectorCount, $locationSectors,$recurseCount=0) {
		$recurseCount++;
		
		if ($recurseCount>20) {
			echo "Function addSectorToLocation() recursed beyond the limit of 20 recursions.";
			exit;
		}
	
		$sector = self::getSectorDetails($sectorId);
		
		$sql = "REPLACE INTO " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link
				SET location_id = " . (int) $locationId . ",
					sector_id = " . (int)  $sectorId . ",
					score_id = 3";
		
		if (!$sector['parent_id'] && !$stickyFlagSet) {
			$sql .= ", sticky_flag = 1";
		}

		$result = ze\sql::update($sql);

		if ($sector['parent_id'] && (!is_array($locationSectors) || !in_array($sector['parent_id'],$locationSectors))) {
			$this->addSectorToLocation($sector['parent_id'], $locationId, $stickyFlagSet, $locationSectorCount, $locationSectors,$recurseCount);
		}
	}

	public function setLocationSectors($locationId, $sectors) {
		$currentStickySector=ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link', 'sector_id',['sticky_flag' => 1,'location_id' => $locationId]);
	
		$oldStickyFlag = 0;
		$stickyflag = 0;
		$stickySet = 0;
		foreach ($sectors as $value) {
			if ($value==$currentStickySector){
				$oldStickyFlag = $value;
			}
		}
			
		ze\row::delete(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link', ['location_id' => $locationId]);

		foreach ($sectors as $value) {
			$sector = self::getSectorDetails($value);
			
			if (!$oldStickyFlag){
				if ($sector['parent_id']) {
					$stickyflag = 0;
				} else {
					$stickyflag = 1;
				}
			}
			
			ze\row::insert(
				ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link',
				[
					'sector_id' => $value,
					'location_id' => $locationId,
					'score_id' => 3,
					'sticky_flag' => ($value==$oldStickyFlag) | ($stickyflag & !$stickySet)]);
			$stickySet |= $stickyflag;
		}
	}

	public static function removeLocationFromSector($locationId, $sectorId, $recurseCount = 0) {
		$recurseCount++;
		
		if ($recurseCount>20) {
			echo "Recursed out of control. Exiting";
			exit;
		}
		
		ze\row::delete(
			ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link",
			["location_id" => $locationId, "sector_id" => $sectorId]
		);
		
		$result = ze\row::query(
			ZENARIO_LOCATION_MANAGER_PREFIX . "sectors",
			["id"],
			["parent_id" => $sectorId]
		);
		
		if ($result) {
			while ($row = ze\sql::fetchAssoc($result)) {
				self::removeLocationFromSector($locationId,$row['id'],$recurseCount);
			}
		}
	}

	public static function removeAllRegionsFromLocation($locationID){
		
		$sql = "DELETE FROM 
					" . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_region_link 
				WHERE
					location_id = " . (int) $locationID;
		ze\sql::update($sql);
	}
	
	public static function eventContentDeleted ($cID,$cType,$cVersion) {
		//check to see if status is trashed or deleted
		$contentStatus = ze\row::get('content_items', 'status', ['equiv_id' => $cID, 'type'=> $cType]);
		
		if($contentStatus == 'deleted' || $contentStatus == 'trashed') {
			ze\row::update(ZENARIO_LOCATION_MANAGER_PREFIX . "locations",
					["equiv_id" => null,"content_type" => null],
						["equiv_id" => $cID,"content_type" => $cType]);
		}
	}

	public static function eventLocationDeleted ($locationID) {
		
		// Remove parent_id from any child locations
		ze\row::update(
			ZENARIO_LOCATION_MANAGER_PREFIX . 'locations',
			['parent_id' => null],
			['parent_id' => $locationID]
		);
		
		// Delete sector score links
		ze\row::delete(
			ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link",
			["location_id" => $locationID]
		);
	}

	public static function eventSectorDeleted ($sectorId) {
		ze\row::delete(ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link",["sector_id" => $sectorId]);
	}
	
	
	
    public static function locationsImages($locationId) {
        return ze\row::getValues(ZENARIO_LOCATION_MANAGER_PREFIX. 'location_images', 'image_id', ['location_id' => $locationId], 'ordinal');
    }
    public static function addImage($locationId, $location, $filename = false) {
        $imageId = ze\file::addToDatabase('location', $location, $filename, true);
        
        if (!$filename) {
        	$filename = basename($location);
        }
        
        ze\row::set(ZENARIO_LOCATION_MANAGER_PREFIX. 'location_images', ['filename' => $filename], ['image_id' => $imageId, 'location_id' => $locationId]);
        return $imageId;
    }
    
    public static function deleteImage($locationId, $imageId) {
        ze\row::delete( ZENARIO_LOCATION_MANAGER_PREFIX. 'location_images', ['image_id' => $imageId, 'location_id' => $locationId]);
        if (!ze\row::exists(ZENARIO_LOCATION_MANAGER_PREFIX. 'location_images', ['image_id' => $imageId])) {
            ze\row::delete( 'files', ['id' => $imageId, 'usage' => 'location']);
        }
    }
    
	// Centralised list for location status
	public static function locationStatus($mode, $value = false) {
		switch ($mode) {
			case ze\dataset::LIST_MODE_INFO:
				return ['can_filter' => false];
			case ze\dataset::LIST_MODE_LIST:
				return [
					'pending' => 'Pending',
					'active' => 'Active',
					'suspended' => 'Suspended'
				];
		}
	}
	
	public static function requestVarMergeField($name) {
		switch ($name) {
			case 'name':
				return ze\sql::fetchValue('
					SELECT description
					FROM '. DB_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. 'locations
					WHERE id = '. (int) ze::$vars['locationId']
				);
		}
	}
	public static function requestVarDisplayName($name) {
		switch ($name) {
			case 'name':
				return 'Location name';
		}
	}
	
	public static function getLocationTimezone($locationId) {
		//If we have a location id, try to check the timezone used for that location.
		if ($locationId && ($tz = \ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations', 'timezone', $locationId))) {
		} else {
			//Otherwise if there's no location, or no timezone set for that location, use the site default.
			$tz = date_default_timezone_get();
		}
		return $tz;
	}
}

