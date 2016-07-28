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

class zenario_location_manager extends module_base_class {
	
	public static function setupSectorCheckboxes(&$field, $locationId = false) {
		$field['values'] = array();
		if ($result = getRows(ZENARIO_LOCATION_MANAGER_PREFIX . 'sectors', array('id', 'parent_id', 'name'), array(), 'name')) {
			while ($row = sqlFetchAssoc($result)) {
				$field['values'][(int) $row['id']] = array('label' => $row['name'], 'parent' => (int) $row['parent_id']);
			}
		}
		
		if ($locationId) {
			$field['value'] = inEscape(getRowsArray(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link', 'sector_id', array('location_id' => $locationId)), false);
		}
	}

	public function exploreTreeUp($id,$fuse=100){
		$level=0;
		while(1){
			$level++;
			$id = getRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations','parent_id',array('id'=>(int)$id));
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
		$children = getRows(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations', array('id'), array('parent_id'=>$id));
		while($child = sqlFetchAssoc($children)){
			$this->exploreTreeDown($child['id'],$maxLevel,$fuse--,$currentLevel);
		}
	}
 
	public function handleAJAX () {
		if (get("mode")=="get_country_name") {
			$country= zenario_country_manager::getCountryFullInfo("all",get("country_id"));
			$item['country'] = $country[get("country_id")]['english_name'];
			echo $item['country'];
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
				if (get('panel_type') === 'google_map') {
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
				
				// Hide pending button if pending state not enabled
				if (!setting('zenario_location_manager__enable_pending_status')) {
					unset($panel['item_buttons']['mark_as_pending']);
				// Otherwise show a pending quick filter button
				} else {
					$panel['quick_filter_buttons']['pending'] = array(
						'label' => 'Pending',
						'column' => 'status',
						'value' => 'pending',
						'ord' => 1.5
					);
				}
				
				// Add dataset ID to import button
				$dataset = getDatasetDetails(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations');
				$panel['collection_buttons']['import']['admin_box']['key']['dataset'] = 
				$panel['collection_buttons']['donwload_sample_file']['admin_box']['key']['dataset'] = 
				$panel['collection_buttons']['export']['admin_box']['key']['dataset'] = 
					$dataset['id'];
				
				// If no locations, hide export button
				if (count($panel['items']) <= 0) {
					$panel['collection_buttons']['export']['hidden'] = true;
				}
				
				$admins = array();
				$adminsRaw = getRows("admins",array("id","username","authtype"),array("status" => "active"));
				if (sqlNumRows($adminsRaw)>0) {
					while ($admin = sqlFetchArray($adminsRaw)) {
						$admins[$admin['id']] = $admin['username'];
						
						if ($admin['authtype']=="super") {
							$admins[$admin['id']] .= " (super)";
						}
					}
				}
	
				$panel['columns']['last_updated_by']['values'] = $admins;
	
				foreach ($panel['items'] as $id => &$item) {
					$item['cell_css_classes'] = array();
					$item['label'] = $item['description'] . " " . $item['city'] . " " . $item['country'];
					
					$locationDetails = self::getLocationDetails($id);
	
					if ($item['status'] == 'active') {
						$item['cell_css_classes']['status'] = "green";
					} elseif ($item['status'] == 'pending') {
						$item['cell_css_classes']['status'] = "orange";
					} else {
						$item['cell_css_classes']['status'] = "brown";
					}
					
					if (!issetArrayKey($locationDetails,"parent_id") && !checkRowExists(ZENARIO_LOCATION_MANAGER_PREFIX . "locations",array("parent_id" => $id))) {
						$item['traits']['not_in_hierarchy'] = true;
					}
					
					if ($item['checksum']) {
						$img = '&usage=location&c='. $item['checksum'];
						
						$item['image'] = 'zenario/file.php?og=1'. $img;
						$item['list_image'] = 'zenario/file.php?ogl=1'. $img;
					}
					
					$locationHierarchyPathAndDeepLinks = self::getLocationHierarchyPathAndDeepLinks($id);
					
					$item['path'] = $locationHierarchyPathAndDeepLinks['path'];
					
					$deeplinkFrag = $locationHierarchyPathAndDeepLinks['link'];
					
					$deeplinkFrag = substr($deeplinkFrag,0,strrpos($deeplinkFrag,"item//")) . substr($deeplinkFrag,strrpos($deeplinkFrag,"item//") + 6);
					
					$item['navigation_path'] = "zenario__locations/panel/hierarchy/item//hierarchy" . $deeplinkFrag;
					
				}
				
				if (setting("zenario_location_manager__sector_management")!="2") {
					unset($panel['item_buttons']['view_location_sectors']);
				}
	
				if (setting("zenario_location_manager__hierarchy_levels")==0) {
					unset($panel['item_buttons']['view_in_hierarchy']);
				}
				
				if ($refinerName=="sector_locations") {
					if ($sector = self::getSectorDetails($refinerId)) {
						$panel['title'] = "Locations in the sector \"" . $sector['name'] . "\"";
					}
	
					$panel['no_items_message'] = "There are no locations in this sector.";
	
					if (issetArrayKey($panel,"item_buttons","activate")) {
						unset($panel['item_buttons']['activate']);
					}
	
					if (issetArrayKey($panel,"item_buttons","suspend")) {
						unset($panel['item_buttons']['suspend']);
					}
	
					if (issetArrayKey($panel,"item_buttons","upload_image")) {
						unset($panel['item_buttons']['upload_image']);
					}
	
					if (issetArrayKey($panel,"item_buttons","suspend")) {
						unset($panel['item_buttons']['suspend']);
					}
	
					unset($panel['item']['link']);
	
					$panel['item_buttons']['remove_location']['ajax']['confirm']['message'] = adminPhrase('Are you sure you wish to remove the Location "[[description]]" from the Sector "[[name]]"?', $sector);
					$panel['item_buttons']['remove_location']['ajax']['confirm']['multiple_select_message'] = adminPhrase('Are you sure you wish to remove the selected Locations from the Sector "[[name]]"?', $sector);
	
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
					$panel['item']['tooltip'] = adminPhrase("View this location's child locations");
					$panel['item']['link']['path'] = "zenario__locations/panel";
					$panel['item']['link']['branch'] = "Yes";
					$panel['item']['link']['refiner'] = "children_of_location";
	
					$level = $this->exploreTreeUp($refinerId) + 1;
					$maxLevels = (int) setting('zenario_location_manager__hierarchy_levels');
					if (($level > $maxLevels ) && (arrayKey($panel,'collection_buttons','add_child_location'))) {
						unset($panel['collection_buttons']['add_child_location']);
					} 
					$panel['item_buttons']['set_parent']['name'] = 'Assign location a new parent';
					$panel['item_buttons']['set_parent']['combine_items']['one_to_one_choose_phrase'] = 'Assign parent Location';
					if ($maxLevels==0 && issetArrayKey($panel,'item_buttons','set_parent')) {
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
					$maxLevels = (int) setting('zenario_location_manager__hierarchy_levels');
					if ($maxLevels==0 && issetArrayKey($panel,'item_buttons','set_parent')) {
						unset($panel['item_buttons']['set_parent']);
					}
				} else {
					if ($panel['item']['link']["path"] == "zenario__locations/panel" && $panel['item']['link']["refiner"] == "children_of_location"){
						unset($panel['item']['link']);
					}
	
					$maxLevels = (int) setting('zenario_location_manager__hierarchy_levels');
					if ($maxLevels==0 && issetArrayKey($panel,'item_buttons','set_parent')) {
						unset($panel['item_buttons']['set_parent']);
					}
					if (issetArrayKey($panel,"item_buttons","remove_location")) {
						unset($panel['item_buttons']['remove_location']);
					}
				}
				break;
				
			case 'zenario__locations/nav/sectors/panel':
				
				if(!checkPriv('_PRIV_MANAGE_LOCATIONS')){
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
					
					if (setting("zenario_location_manager__sector_management")!="2") {
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
						
						$item['path'] = implode(" -> ",array_reverse($pathArray));
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

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$locationDetails = array();
	
		switch ($path) {
			case "zenario_location_manager__location":
				exitIfNotCheckPriv('_PRIV_MANAGE_LOCATIONS');
				$locationCountriesFinal = zenario_country_manager::getCountryAdminNamesIndexedByISOCode("active");
				
				foreach ($locationCountriesFinal as $key => $value) {
					$box['tabs']['details']['fields']['country']['values'][$key] = $value;
				}

				if ($box['key']['id']) {
					$locationDetails = self::getLocationDetails($box['key']['id']);
                  
                 	$box['title'] = adminPhrase('Editing the location "[[name]]"',array('name' => $locationDetails['description']));
                    $box['tabs']['images']['fields']['images']['value'] = inEscape(self::locationsImages($box['key']['id']), 'numeric');
					$box['tabs']['details']['fields']['external_id']['value'] = $locationDetails['external_id'];
                    $box['tabs']['details']['fields']['name']['value'] = $locationDetails['description'];
                    $box['tabs']['details']['fields']['address_line_1']['value'] = $locationDetails['address1'];
                    $box['tabs']['details']['fields']['address_line_2']['value'] = $locationDetails['address2'];
                    $box['tabs']['details']['fields']['locality']['value'] = $locationDetails['locality'];
                    $box['tabs']['details']['fields']['city']['value'] = $locationDetails['city'];
                    $box['tabs']['details']['fields']['state']['value'] = $locationDetails['state'];
                    $box['tabs']['details']['fields']['postcode']['value'] = $locationDetails['postcode'];
                    $box['tabs']['details']['fields']['country']['value'] = $locationDetails['country_id'];
					if ($region = self::getInmostLocationRegion($box['key']['id'])){
						$box['tabs']['details']['fields']['region']['value'] = $region;
					}
					$box['tabs']['details']['fields']['map_center_lat']['value'] = $locationDetails['map_center_latitude'];
					$box['tabs']['details']['fields']['map_center_lng']['value'] = $locationDetails['map_center_longitude'];
					$box['tabs']['details']['fields']['marker_lat']['value'] = $locationDetails['latitude'];
					$box['tabs']['details']['fields']['marker_lng']['value'] = $locationDetails['longitude'];
					$box['tabs']['details']['fields']['hide_pin']['value'] = $locationDetails['hide_pin'];
					$box['tabs']['details']['fields']['zoom']['value'] = $locationDetails['map_zoom'];
                    
                    
                    if ($locationDetails['content_type'] && $locationDetails['equiv_id']) {
	                    $box['tabs']['content_item']['fields']['content_item']['value'] = $locationDetails['content_type'] . "_" . $locationDetails['equiv_id'];
	                }
 
                    $box['tabs']['details']['fields']['last_updated']['value'] = arrayKey($locationDetails,'last_updated');
                	$box['tabs']['details']['fields']['last_updated']['hidden'] = false;

					$lastUpdatedByAdmin = getRow("admins",array("id","username","authtype"),array("id" => arrayKey($locationDetails,'last_updated_admin_id')));

                	$box['tabs']['details']['fields']['last_updated_by']['value'] = arrayKey($lastUpdatedByAdmin,'username') . ((arrayKey($lastUpdatedByAdmin,'authtype')=="super") ? " (super)":"");
                	$box['tabs']['details']['fields']['last_updated_by']['hidden'] = false;
 
                } else {
                	$box['tabs']['details']['fields']['last_updated']['hidden'] = true;
                	$box['tabs']['details']['fields']['last_updated_by']['hidden'] = true;
                }

				if (isset($box['tabs']['sectors']['fields']['sectors'])) {
					self::setupSectorCheckboxes(
						$box['tabs']['sectors']['fields']['sectors'],
						$box['key']['id']);
				}

				if (setting("zenario_location_manager__sector_management")!="0") {
					$box['tabs']['sectors']['hidden'] = true;
				}

				$map_lookup = "<select id=\"pin_placement_method\">\n";
				$map_lookup .= "<option value=\"\"> -- Select a method -- </option>\n";
				$map_lookup .= "<option value=\"postcode_country\">Postcode and Country</option>\n";
				$map_lookup .= "<option value=\"street_postcode_country\">Address Line 1, Postcode and Country</option>\n";
				$map_lookup .= "<option value=\"street_city_country\">Address Line 1, City and Country</option>\n";
				$map_lookup .= "<option value=\"my_location\">My Current Location</option>\n";
				$map_lookup .= "</select>\n";
				$map_lookup .= "<button onclick=\"document.getElementById('google_map_iframe').contentWindow.placeMarker(document.getElementById('pin_placement_method').value);return false\">Place Pin</button>\n";
				$map_lookup .= "<button onclick=\"document.getElementById('google_map_iframe').contentWindow.clearMap();return false\">Clear Map</button>\n";
				
				$mapEdit = "<iframe id=\"google_map_iframe\" name=\"google_map_iframe\" src=\"" . htmlspecialchars($this->showFileLink("&map_center_lat=" . arrayKey($locationDetails,'map_center_latitude') . "&map_center_lng=" . arrayKey($locationDetails,'map_center_longitude') . "&marker_lat=" . arrayKey($locationDetails,'latitude') . "&marker_lng=" . arrayKey($locationDetails,'longitude') . "&zoom=" . arrayKey($locationDetails,'map_zoom')) . "&editmode=1") . "\" style=\"width: 425px;height: 425px;border: none;\"></iframe>\n";
				$mapView = "<iframe id=\"google_map_iframe\" name=\"google_map_iframe\" src=\"" . htmlspecialchars($this->showFileLink("&map_center_lat=" . arrayKey($locationDetails,'map_center_latitude') . "&map_center_lng=" . arrayKey($locationDetails,'map_center_longitude') . "&marker_lat=" . arrayKey($locationDetails,'latitude') . "&marker_lng=" . arrayKey($locationDetails,'longitude') . "&zoom=" . arrayKey($locationDetails,'map_zoom')) . "&editmode=0") . "\" style=\"width: 425px;height: 425px;border: none;\"></iframe>\n";

				$box['tabs']['details']['fields']['map_lookup']['snippet']['html'] = $map_lookup;				
				$box['tabs']['details']['fields']['map_edit']['snippet']['html'] = $mapEdit;
				$box['tabs']['details']['fields']['map_view']['snippet']['html'] = $mapView;

				
				
				break;
			case "zenario_location_manager__locations_multiple_edit":
				exitIfNotCheckPriv('_PRIV_MANAGE_LOCATIONS');
				
				$box['title'] = "Editing settings for " . sizeof(explode(",",$box['key']['id'])) . " locations";
			
				$locationCountriesFinal = zenario_country_manager::getCountryAdminNamesIndexedByISOCode("active");

				foreach ($locationCountriesFinal as $key => $value) {
					$box['tabs']['details']['fields']['country']['values'][$key] = $value;
				}
				
				if ($box['key']['id']) {
					$fieldsToCheck = array(
											"name" => "description",
											"address_line_1" => "address1",
											"address_line_2" => "address2",
											"locality" => "locality",
											"city" => "city",
											"state" => "state",
											"postcode" => "postcode",
											"country" => "country_id"
										);
										
					foreach ($fieldsToCheck as $tuixName => $dbName) {
						$sql = "SELECT DISTINCT " . sqlEscape($dbName) . "
								FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations
								WHERE id IN (" . sqlEscape($box['key']['id']) . ")
								LIMIT 2";
								
						$result = sqlQuery($sql);
						
						if (sqlNumRows($result)==1) {
							$row = sqlFetchAssoc($result);
							
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
						$box['tabs']['details']['fields']['region']['value'] = $previousRegion;
					}


					$sql = "SELECT DISTINCT 
								CONCAT(content_type,'_',equiv_id) AS tag
							FROM " 
								. DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations
							WHERE 
								id IN (" . sqlEscape($box['key']['id']) . ")
							LIMIT 2";
							
					$result = sqlQuery($sql);
					
					if (sqlNumRows($result)==1) {
						$row = sqlFetchAssoc($result);
						
						$box['tabs']['content_item']['fields']['content_item']['value'] = $row['tag'];
					}

					if (setting("zenario_location_manager__sector_management")!="0") {
						$box['tabs']['sectors']['hidden'] = true;
					} else {
						$field = &$box['tabs']['sectors']['fields']['sectors'];
	
						$field['multiple_edit'] = array();
						
						self::setupSectorCheckboxes($field);
						
						$sectorsPicked = false;
						$sql = "
							SELECT lssl.sector_id, COUNT(DISTINCT lssl.location_id) AS cnt
							FROM ". DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations AS l
							INNER JOIN ". DB_NAME_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link AS lssl
							   ON l.id = lssl.location_id
							WHERE lssl.location_id IN (". $box['key']['id']. ")
							GROUP BY lssl.sector_id";
						$result = sqlQuery($sql);
						while ($row = sqlFetchAssoc($result)) {
							if (isset($field['values'][$row['sector_id']])) {
								$sectorsPicked = true;
								$field['values'][$row['sector_id']]['marked'] = true;
								$field['values'][$row['sector_id']]['label'] .= ' ('. $row['cnt']. ')';
							}
						}
						
						if ($sectorsPicked) {
							self::setupSectorCheckboxes($box['tabs']['sectors']['fields']['remove_sectors']);
							$box['tabs']['sectors']['fields']['remove_sectors']['multiple_edit'] = array();
						} else {
							$box['tabs']['sectors']['fields']['remove_sectors']['hidden'] = true;
						}
						
						$items = array();
						foreach ($field['values'] as $id => &$value) {
							if (!isset($value['marked'])) {
								//$value['label'] .= ' (0/'. $total. ')';
								
								if (isset($box['tabs']['sectors']['fields']['remove_sectors']['values'][$id])) {
									unset($box['tabs']['sectors']['fields']['remove_sectors']['values'][$id]);
								}
							} else {
								if ($sectorsPicked) {
									$box['tabs']['sectors']['fields']['remove_sectors']['values'][$id]['label'] = $value['label'];
									$items[] = $id;
								}
							}
						}
						$box['tabs']['sectors']['fields']['remove_sectors']['value'] = inEscape($items, false);
	
						if (checkRowExists(ZENARIO_LOCATION_MANAGER_PREFIX . "sectors", array())) {
							$box['tabs']['sectors']['fields']['no_sectors']['hidden'] = true;
						} else {
							$box['tabs']['sectors']['fields']['sectors']['hidden'] = true;
							$box['tabs']['sectors']['fields']['remove_sectors']['hidden'] = true;
						}
					}
				}
			
				

				break;
			case "zenario_location_manager__sector":
				if (get("refiner__sub_sectors")) {
					$box['key']['parent_id'] = get("refiner__sub_sectors");
				}
			
				if ($box['key']['id']) {
					$sectorDetails = self::getSectorDetails($box['key']['id']);
                  
                 	$box['title'] = adminPhrase('Editing the sector "[[name]]"',array('name' => $sectorDetails['name']));
                        
					
                    $box['tabs']['details']['fields']['name']['value'] = $sectorDetails['name'];
                  
                    $box['tabs']['details']['edit_mode']['on'] = false;
                    $box['tabs']['details']['edit_mode']['always_on'] = false;
                }
				
				break;
			case "zenario_location_manager__score":
				if ($box['key']['id']) {
					$scoreDetails = self::getScoreDetails($box['key']['id']);
                    
                    $box['tabs']['details']['fields']['name']['value'] = $scoreDetails['name'];
				}
				
				break;
			case "zenario_content":
				if (isset($_GET['refiner__zenario__locations__create_content']) || (get("refinerName")=="refiner__zenario__locations__create_content")) {
					$box['tabs']['meta_data']['fields']['content_summary']['hidden'] = true;
					$box['tabs']['meta_data']['fields']['lock_summary_view_mode']['hidden'] = true;
					$box['tabs']['meta_data']['fields']['lock_summary_edit_mode']['hidden'] = true;
					$box['tabs']['meta_data']['fields']['desc_location_specific']['hidden'] = false;
				} else {
					if ($box['key']['cID']) {
						if ($locationId = getRow(ZENARIO_LOCATION_MANAGER_PREFIX . "locations", 'id',
									array("equiv_id" => $box['key']['cID'], "content_type" => $box['key']['cType']))) {
							$box['tabs']['meta_data']['fields']['content_summary']['hidden'] = true;
							$box['tabs']['meta_data']['fields']['lock_summary_view_mode']['hidden'] = true;
							$box['tabs']['meta_data']['fields']['lock_summary_edit_mode']['hidden'] = true;
							$box['tabs']['meta_data']['fields']['desc_location_specific']['hidden'] = false;
							if ($locationDetails = self::getLocationDetails($locationId)) {
								$box['tabs']['meta_data']['fields']['desc_location_specific']['snippet']['html'] = 
									adminPhrase("This content item is associated with a location \"[[location]]\". The location's summary will be used.", 
										array('location' => $locationDetails['description']) );
							}
						} else {
							$box['tabs']['meta_data']['fields']['content_summary']['hidden'] = false;
							$box['tabs']['meta_data']['fields']['desc_location_specific']['hidden'] = true;
						}
					} else {
						$box['tabs']['meta_data']['fields']['desc_location_specific']['hidden'] = true;
					}
				} 
				break;
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case "zenario_location_manager__location":
				$mapEdit = "<iframe id=\"google_map_iframe\" name=\"google_map_iframe\" src=\"" . 
						htmlspecialchars($this->showFileLink("	&map_center_lat=" . arrayKey($values, 'details/map_center_lat') . "
															 	&map_center_lng=" . arrayKey($values, 'details/map_center_lng') . "
															 	&marker_lat=" . arrayKey($values, 'details/marker_lat') . "
															 	&marker_lng=" . arrayKey($values, 'details/marker_lng') . "
															 	&zoom=" . arrayKey($values, 'details/zoom')) . "
															 	&editmode=1") . "\" 
															 	style=\"width: 425px;height: 425px;border: none;\"></iframe>\n";
				$mapView = "<iframe id=\"google_map_iframe\" name=\"google_map_iframe\" src=\"" . 
						htmlspecialchars($this->showFileLink("	&map_center_lat=" . arrayKey($values, 'details/map_center_lat') . "
																&map_center_lng=" . arrayKey($values, 'details/map_center_lng') . "
																&marker_lat=" . arrayKey($values, 'details/marker_lat') . "
																&marker_lng=" . arrayKey($values, 'details/marker_lng') . "
																&zoom=" . arrayKey($values, 'details/zoom')) . "
																&editmode=0") . "\" style=\"width: 425px;height: 425px;border: none;\"></iframe>\n";

				$box['tabs']['details']['fields']['map_edit']['snippet']['html'] =  $mapEdit;
				$box['tabs']['details']['fields']['map_view']['snippet']['html'] =  $mapView;
				$countryValue = $values['details/country'];

				if ($countryValue) {
					$regions = zenario_country_manager::getRegions("all",$countryValue);
					
					if (!empty($regions)) {
						
						$box['tabs']['details']['fields']['region']['hidden'] = "No";
						$box['tabs']['details']['fields']['region']['pick_items']['path'] = 'zenario__languages/panels/countries/item//' . $countryValue . '//';
						
						if (!($regionsCountry = zenario_country_manager::getCountryOfRegion($values['details/region']))
						 || !($countryValue == $regionsCountry['id'])) {
							$box['tabs']['details']['fields']['region']['current_value'] = '';
						} 
					} else {
						$box['tabs']['details']['fields']['region']['hidden'] = "Yes";
					}
				} else {
					$box['tabs']['details']['fields']['region']['hidden'] = "Yes";
				}
				break;
			case "zenario_location_manager__locations_multiple_edit":
				$countryValue = $values['details/country'];

				if ($countryValue) {
					$regions = zenario_country_manager::getRegions("all",$countryValue);
					
					if (sizeof($regions)>0) {
						
						$box['tabs']['details']['fields']['region']['hidden'] = false;
						$box['tabs']['details']['fields']['region']['pick_items']['path'] = 'zenario__languages/panels/countries/item//' . $countryValue . '//';
						
						if (!($regs = zenario_country_manager::getRegions('active',$countryValue, false, $values['details/region']))){
							$box['tabs']['details']['fields']['region']['value'] = '';
							$box['tabs']['details']['fields']['region']['current_value'] = '';
						} 
					} else {
						$box['tabs']['details']['fields']['region']['hidden'] = "Yes";
					}
				} else {
					$box['tabs']['details']['fields']['region']['hidden'] = "Yes";
				}
				
				/*if ($path=="zenario_location_manager__locations_multiple_edit") {
					if (!$changes['details/country'] && !$changes['details/region']) {
						unset($box['tabs']['details']['fields']['region']['current_value']);
					} elseif ($changes['details/country']) {
						unset($box['tabs']['details']['fields']['region']['multiple_edit']);
					}
				}*/
				break;
		}
	}

	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case "zenario_location_manager__location":
				if (issetArrayKey($values,'details/email')){
					foreach(explode(',',$values['details/email']) as $emailAddress){
						if (!validateEmailAddress(trim($emailAddress))){
							$box['tabs']['details']['errors'][] = "The email address entered is not valid.";
							break;
						}
					}
				}
				if (!empty($values['external_id'])) {
					if (!$box['key']['id'] && checkRowExists(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations', array('external_id' => $values['external_id']))) {
						$box['tabs']['details']['errors'][] = adminPhrase('A location already exists with this external id.');
					} else {
						$oldLocationId = getRow(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations', array('external_id'), array('id' => $box['key']['id']));
						if (($oldLocationId['external_id'] != $values['external_id']) && (checkRowExists(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations', array('external_id' => $values['external_id'])))) {
							$box['tabs']['details']['errors'][] = adminPhrase('A location already exists with this external id.');
						}
					}
				}
				break;
			case "zenario_location_manager__locations_multiple_edit":
				foreach ($changes as $fieldName => $fieldValue) {
					if ($fieldValue==1) {
						if ($fieldName=="email") {
							if (issetArrayKey($values,'details/email')) {
								foreach(explode(',',$values['details/email']) as $emailAddress){
									if (!validateEmailAddress(trim($emailAddress))){
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
			case 'site_settings':
				if ($settingGroup == 'zenario_location_manager__site_settings_group'
				 && !empty($box['tabs']['admin_box_settings']['fields'])) {
					
					$mandatoryFieldSet = false;
					
					foreach ($box['tabs']['admin_box_settings']['fields'] as $fieldName => $field) {
						if (!empty($values['admin_box_settings/'. $fieldName])
						 && $values['admin_box_settings/'. $fieldName] == 'mandatory') {
							
							$mandatoryFieldSet = true;
							break;
						}
					}
					
					if (!$mandatoryFieldSet) {
						$box['tabs']['admin_box_settings']['errors'][] = adminPhrase('You must set at least one field to "Enabled & Mandatory".	');
					}
				}
				break;
		}
	}

	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case "zenario_location_manager__location":
				exitIfNotCheckPriv('_PRIV_MANAGE_LOCATIONS');
				
				$saveValues = array(
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
					'map_center_latitude' => $values['details/map_center_lat'] ? $values['details/map_center_lat'] : null,
					'map_center_longitude' => $values['details/map_center_lng'] ? $values['details/map_center_lng'] : null,
					'map_zoom' => $values['details/zoom'] ? $values['details/zoom'] : null,
					'last_updated' => now(),
					'last_updated_admin_id' => adminId()
				);
				
				// Save content item details
				$saveValues['equiv_id'] = null;
				$saveValues['content_type'] = null;
				if (!empty($values['content_item/content_item'])) {
					$cID = $cType = false;
					getCIDAndCTypeFromTagId($cID, $cType, $values['content_item/content_item']);
					$equivID = equivId($cID, $cType);
					
					$saveValues['equiv_id'] = $equivID;
					$saveValues['content_type'] = $cType;
				}
				
				// Save location
				if (!$box['key']['id']) {
					$box['key']['id'] = self::createLocation($saveValues);
				} else {
					setRow(ZENARIO_LOCATION_MANAGER_PREFIX . "locations", $saveValues, array("id" => $box['key']['id']));
				}
				
				// Add regions to location
				if ($values['details/country'] && $values['details/region']){
					self::addRegionToLocation($values['details/region'],$box['key']['id']);
				} else {
					self::removeAllRegionsFromLocation($box['key']['id']);
				}
				
				// Add sectors to location
				if (engToBooleanArray($box, 'tabs', 'sectors', 'edit_mode', 'on')) {
					if (!empty($values['sectors/sectors'])) {
						$this->setLocationSectors($box['key']['id'], explode(',', $values['sectors/sectors']));
					} else {
						deleteRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link', array('location_id' => $box['key']['id']));
					}
				}
				
				// Save location images
				if (engToBooleanArray($box['tabs']['images'], 'edit_mode', 'on') && isset($values['images/images'])) {
				
					$ord = 0;
					$sticky = 1;
					$usedImages = array();
					foreach (explode(',', $values['images/images']) as $image) {
						$image_id = 0;
						if ($filepath = getPathOfUploadedFileInCacheDir($image)) {
							$image_id = self::addImage($box['key']['id'], $filepath);
						} else {
							$image_id = (int) $image;
						}
						
						if ($image_id) {
							$usedImages[$image_id] = true;
							setRow(
								ZENARIO_LOCATION_MANAGER_PREFIX. 'location_images', 
								array('ordinal' => ++$ord, 'sticky_flag' => $sticky), 
								array('image_id' => $image_id, 'location_id' => $box['key']['id'])
							);
							$sticky = 0;
						}
					}
					
					foreach (self::locationsImages($box['key']['id']) as $image_id) {
						if (empty($usedImages[$image_id])) {
							self::deleteImage($box['key']['id'], $image_id);
						}
					}
				}
				
				break;
			case "zenario_location_manager__locations_multiple_edit":
				exitIfNotCheckPriv('_PRIV_MANAGE_LOCATIONS');
				$fieldsToChangeSQL = array();
				
				foreach ($changes as $fieldName => $fieldValue) {
					if ($fieldValue==1) {
						if ($fieldName=="name") {
							$fieldsToChangeSQL[] = "description = '" . sqlEscape($values['details/name']) . "'";
						}
					
						if ($fieldName=="address_line_1") {
							$fieldsToChangeSQL[] = "address1 = '" . sqlEscape($values['details/address_line_1']) . "'";
						}

						if ($fieldName=="address_line_2") {
							$fieldsToChangeSQL[] = "address2 = '" . sqlEscape($values['details/address_line_2']) . "'";
						}

						if ($fieldName=="locality") {
							$fieldsToChangeSQL[] = "locality = '" . sqlEscape($values['details/locality']) . "'";
						}

						if ($fieldName=="city") {
							$fieldsToChangeSQL[] = "city = '" . sqlEscape($values['details/city']) . "'";
						}
						
						if ($fieldName=="state") {
							$fieldsToChangeSQL[] = "state = '" . sqlEscape($values['details/state']) . "'";
						}

						if ($fieldName=="postcode") {
							$fieldsToChangeSQL[] = "postcode = '" . sqlEscape($values['details/postcode']) . "'";
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

								$fieldsToChangeSQL[] = "equiv_id = " . arrayKey($contentItemArray,1);
								$fieldsToChangeSQL[] = "content_type = '" . sqlEscape(arrayKey($contentItemArray,0)) . "'";
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
						
						$fieldsToChangeSQL[] = "last_updated = '" . now() . "'";
						$fieldsToChangeSQL[] = "last_updated_admin_id = " . session('admin_userid');
					}
				}
				
				if (!empty($fieldsToChangeSQL)) {
					$sql = "UPDATE " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations
							SET ";

					$sql .= implode(",",$fieldsToChangeSQL);
							
					$sql .= " WHERE id IN (" . sqlEscape($box['key']['id']) . ")";
					
					$result = sqlQuery($sql);
				}

				if (engToBooleanArray($box, 'tabs', 'sectors', 'edit_mode', 'on')) {
					if ($changes['sectors/sectors']) {
						$locationIds = explode(",",$box['key']['id']);
						foreach ($locationIds as $locationId) {
							foreach (explode(',', $values['sectors/sectors']) as $id) {
								if ($id) {
									if (!checkRowExists(ZENARIO_LOCATION_MANAGER_PREFIX .'location_sector_score_link', array('sector_id' => $id, 'location_id' => $locationId))) {
										insertRow(ZENARIO_LOCATION_MANAGER_PREFIX .'location_sector_score_link', array('sector_id' => $id, 'location_id' => $locationId, 'score_id' => 3, "sticky_flag" => 0));
									}
								}
							}
						}
					}
					
					if ($changes['sectors/remove_sectors']) {
						$locationIds = explode(",",$box['key']['id']);
						$removeFromSectors = array_flip(explode(',', $values['sectors/remove_sectors']));
						
						foreach ($box['tabs']['sectors']['fields']['remove_sectors']['values'] as $id => $value) {
							if (!isset($removeFromSectors[$id])) {
								foreach ($locationIds as $locationId) {
									deleteRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link', array('sector_id' => $id, 'location_id' => $locationId));
								}
							}
						}
					}
				}

			
				break;
			case "zenario_location_manager__sector":
				if ($box['tabs']['details']) {
					$saveValues = array();
				
					$saveValues['parent_id'] = $box['key']['parent_id'];
					
					$saveValues['name'] = $values['details/name'];
					
					$box['key']['id'] = setRow(ZENARIO_LOCATION_MANAGER_PREFIX . "sectors",$saveValues,array("id" => $box['key']['id']));
				}
				break;
			case "zenario_location_manager__score":
				if ($box['tabs']['details']) {
					$saveValues = array();
				
					$saveValues['name'] = $values['details/name'];
					
					$box['key']['id'] = setRow(ZENARIO_LOCATION_MANAGER_PREFIX . "scores",$saveValues,array("id" => $box['key']['id']));
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
							exitIfNotCheckPriv('_PRIV_MANAGE_LOCATIONS');
							$msg = "";
						
							$locationDetails = self::getLocationDetails($_GET['id']);
					
							$msg = "Are you sure you wish to delete the Location \"" . $locationDetails['description'] . "\"?";

							$result = getRows(ZENARIO_LOCATION_MANAGER_PREFIX . "locations","id",array("parent_id" => $_GET['id']));
							
							$childLocations = 0;
							$childLocationsWithChildren = 0;
							
							if ($childLocations = sqlNumRows($result)) {
								while ($row = sqlFetchAssoc($result)) {
									$result2 = getRows(ZENARIO_LOCATION_MANAGER_PREFIX . "locations","id",array("parent_id" => $row['id']));
									
									if (sqlNumRows($result2)>0) {
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
							exitIfNotCheckPriv('_PRIV_MANAGE_LOCATIONS');
							$IDs = explode(',',$ids);
							foreach ($IDs as $ID){
								self::deleteLocation($ID);
							}
							break;
						case 'mark_location_as_pending':
							exitIfNotCheckPriv('_PRIV_MANAGE_LOCATIONS');
							$IDs = explode(',',$ids);
							foreach ($IDs as $ID){
								self::markLocationAsPending($ID);
							}
							break;
						case 'activate_location':
							exitIfNotCheckPriv('_PRIV_MANAGE_LOCATIONS');
							$IDs = explode(',',$ids);
							foreach ($IDs as $ID){
								self::activateLocation($ID);
							}
							break;
						case 'suspend_location':
							exitIfNotCheckPriv('_PRIV_MANAGE_LOCATIONS');
							$IDs = explode(',',$ids);
							foreach ($IDs as $ID){
								self::suspendLocation($ID);
							}
							break;
						case 'add_location':
							exitIfNotCheckPriv('_PRIV_MANAGE_LOCATIONS');
							if ($refinerName=="sector_locations") {
								$IDs = explode(',',$ids);
								foreach ($IDs as $ID){
									self::addLocationToSector($ID,$refinerId);
								}
							}
							break;
						case 'remove_location':
							exitIfNotCheckPriv('_PRIV_MANAGE_LOCATIONS');
							if ($refinerName=="sector_locations") {
								$IDs = explode(',',$ids);
								foreach ($IDs as $ID){
									self::removeLocationFromSector($ID,$refinerId);
								}
							}
							break;
						case 'add_child_location':
							exitIfNotCheckPriv('_PRIV_MANAGE_LOCATIONS');
							$limit=100;
							$level1=0;
							$parent = $refinerId;
							$maxLevels = (int) setting('zenario_location_manager__hierarchy_levels');
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
								$parent = getRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations','parent_id',array('id'=>(int)$parent));
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
								updateRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations',array('parent_id'=>(int)$refinerId),array('id'=>(int)$id));
							}
							break;
						case 'assign_new_parent':
							exitIfNotCheckPriv('_PRIV_MANAGE_LOCATIONS');
							foreach (explode(",",$ids) as $id) {
								$limit=100;
								$level1=0;
								$parent = $ids2;
								$maxLevels = (int) setting('zenario_location_manager__hierarchy_levels');
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
									$parent = getRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations','parent_id',array('id'=>(int)$parent));
								
								}
								$level2=0;
								$this->exploreTreeDown($id,$level2,$maxLevels);
								if ($level1+$level2>$maxLevels) {
									echo 'Error. Number of allowed levels in the Location hierarchy exceeded.';
									return;
								}
								updateRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations',array('parent_id'=>(int)$ids2),array('id'=>(int)$id));
							}
							break;
						case 'make_orphan':
							exitIfNotCheckPriv('_PRIV_MANAGE_LOCATIONS');
							updateRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'locations',array('parent_id'=>null),array('id'=>(int)$ids));
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
				switch (post('action')){
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
								FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link
								WHERE location_id = " . (int) $refinerId;
									
						$result = sqlQuery($sql);
						
						$locationSectorCount = sqlNumRows($result);
						
						$locationSectors = array();
						$stickyFlagSet = false;
						
						if ($locationSectorCount>0) {
							while ($row = sqlFetchArray($result)) {
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
								$sql = "UPDATE " . DB_NAME_PREFIX . TRIBIQ_LOCATION_MANAGER_PREFIX . "location_sector_score_link
										SET sticky_flag = 0
										WHERE location_id = " . (int) $refinerId;
										
								$result = sqlQuery($sql);
								
								$sql = "UPDATE " . DB_NAME_PREFIX . TRIBIQ_LOCATION_MANAGER_PREFIX . "location_sector_score_link
										SET sticky_flag = 1
										WHERE location_id = " . (int) $refinerId . "
											AND sector_id = " . (int) $ID;
										
								$result = sqlQuery($sql);		
						}
						break;
			}
		}
	}
	
	public function showFile() {
		echo '<html>
				<head>
				<script id="google_api" type="text/javascript" src="' . httpOrhttps() . 'maps.google.com/maps/api/js?sensor=false&key=' . urlencode(setting('google_maps_api_key')) . '"></script>
				<script type="text/javascript" src="modules/zenario_location_manager/js/locations.js"></script>
				</head>
				<script type="text/javascript">
				
				defaultMapCentreLat = 0;
				defaultMapCentreLng = 0;
				defaultMapZoom = 1;
				';
		
		if (get("map_center_lat") && get("map_center_lng") && get("zoom")) {		
			echo 'defaultMapCentreLat = ' . get("map_center_lat") . ';
				defaultMapCentreLng = ' . get("map_center_lng") . ';
				defaultMapZoom = ' . get("zoom") . ';';
		}
		
		if (get("marker_lat") && get("marker_lng")) {		
			echo 'markerLat = ' . get("marker_lat") . ';
				markerLng = ' . get("marker_lng") . ';';
		}		
		
		if (get("editmode")) {
			echo 'editMode = true;';
		}
		
		echo '					
				</script>
				<body onload="init()">
				
				<div id="map" style="width: 400px;height: 400px;"></div>
				
				</body>
				</html>';
	}

	public static function getLocationHierarchyPathAndDeepLinks ($locationId,$output=array("path" => "","link" => ""),$pathSeparator=" -> ",$linkSeparator="//item//",$recurseCount=0) {
		$locationDetails = self::getLocationDetails($locationId);
		
		$output['path'] = arrayKey($locationDetails,'description') . $pathSeparator . $output['path'];
		$output['link'] = $linkSeparator . $locationId . $output['link'];

		if (issetArrayKey($locationDetails,'parent_id')) {
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
		$rv = array();
		$sql = 'SELECT 
					id,
					description 
				FROM ' 
					. DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'locations ';
		if ($status){
			$sql .= " WHERE
							status = '" . sqlEscape($status) . "'" ;
		}
		$sql .='
				ORDER BY 
					description ASC';
		
		if (sqlNumRows($result = sqlQuery($sql))>=1){
			while ($row = sqlFetchArray($result)) {
				$rv[$row['id']] = $row['description'];
			}
		}
		return $rv;
	}

	public static function getLocationsNamesIndexedByIdOrderedByName($status='active'){
		$rv = array();
		$sql = 'SELECT 
					id,
					description 
				FROM ' 
					. DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'locations ';
		if ($status){
			$sql .= " WHERE
							status = '" . sqlEscape($status) . "'" ;
		}
		$sql .='
				ORDER BY 
					description ASC';
		if (sqlNumRows($result = sqlQuery($sql))>=1){
			while ($row = sqlFetchArray($result)) {
				$rv[$row['id']] = $row['description'];
			}
		}
		return $rv;
	}
	
	public static function getLocationDetails($ID){
		$rv = array();
		$sql = 'SELECT id,
					parent_id,
					external_id,
					description,
					address1,
					address2,
					locality,
					city,
					state,
					postcode,
					country_id,
					latitude,
					longitude,
					map_zoom,
					map_center_latitude,
					map_center_longitude,
					hide_pin,
					status,
					equiv_id,
					content_type,
					last_updated,
					last_updated_admin_id,';
					//Custom data:
		$sql .= "
					cd.*";
		$sql.= '
				FROM ' . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'locations AS loc';
		
		//Custom data:
		$sql .= "
			LEFT JOIN ". DB_NAME_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "locations_custom_data AS cd
			   ON cd.location_id = loc.id";
			   
		$sql .= '
			WHERE id = ' . (int) $ID;
		
		if (sqlNumRows($result = sqlQuery($sql))==1){
			$rv = sqlFetchAssoc($result);
		}
		
		return $rv;
	}
	
	public static function getLocationIdFromContentItem ($cID, $cType) {
		if (!$cID || !$cType) {
			return false;
		}
		
		//$sql = "SELECT id
		//		FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations
		//		WHERE equiv_id = " . (int) $cID . "
		//			AND content_type = '" . sqlEscape($cType) . "'";

		$sql = "SELECT l.id
				FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations AS l
				INNER JOIN " . DB_NAME_PREFIX . "content_items AS c1
				ON l.equiv_id = c1.id
				AND l.content_type = c1.type
				INNER JOIN (
					SELECT c2.equiv_id
					FROM " . DB_NAME_PREFIX . "content_items AS c2
					WHERE c2.id = " . (int) $cID . "
					AND c2.type = '" . sqlEscape($cType) . "'
				) AS sub
				ON c1.equiv_id = sub.equiv_id";

					
		$result = sqlQuery($sql);
		
		if (sqlNumRows($result)==1) {
			$row = sqlFetchArray($result);
			
			return $row['id'];
		} else {
			return false;
		}
	}
	
	public static function getChildSectorsIndexedByIdOrderedByName($parentId=0){
		$rv = array();
		$sql = "SELECT 
					id,
					parent_id,
					name
				FROM " 
					.DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "sectors 
				WHERE 
					" . ($parentId?"parent_id=" . (int)$parentId:" true") . "
				ORDER BY 
					name";
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)){
			$rv[$row['id']]=$row;
		}
		return $rv;
	}

	public static function getInmostLocationRegion($locationId){
		$sql = "
				SELECT 
					l1.region_id
				FROM 
					" . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_region_link l1
				LEFT JOIN
					(SELECT 
						l.region_id,
						r.parent_id,
						l.location_id
					FROM
						" . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_region_link l
					INNER JOIN
						" . DB_NAME_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions r
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

		$rv = sqlQuery($sql);
		if ($result = sqlFetchAssoc($rv)){
			return $result['region_id'];
		} else {
			return 0;
		}
	}

	public static function getSectorDetails($ID){
		$rv = array();
		$sql = 'SELECT 
					parent_id,
					name
				FROM ' .DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'sectors ';
		$sql .= 'WHERE id = ' . (int) $ID;
		if (sqlNumRows($result = sqlQuery($sql))==1){
			$rv = sqlFetchAssoc($result);
		}
		return $rv;
	}

	public static function getSectorPath($ID, $recurseCount = 0, $sectorArray=array()) {
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
		$rv = array();
		$sql = 'SELECT name
				FROM ' .DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'scores ';
		$sql .= 'WHERE id = ' . (int) $ID;
		if (sqlNumRows($result = sqlQuery($sql))==1){
			$rv = sqlFetchAssoc($result);
		}
		return $rv;
	}

	public static function getLocationSectors($locationID) {
		$sectors = array();
		
		$sql = "SELECT sector_id
				FROM " .DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link
				WHERE location_id = " . (int) $locationID;
				
		$result = sqlQuery($sql);
		
		if (sqlNumRows($result)>0) {
			while ($row = sqlFetchArray($result)) {
				$sectors[] = $row[0];
			}
			
			return $sectors;
		} else {
			return false;
		}
	}
	
	public static function getLocationSectorDetails($locationID, $sectorID){
		$rv = array();
		$sql = 'SELECT 
					score_id,
					sticky_flag
				FROM ' .DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link
				WHERE location_id = ' . (int) $locationID . '
					AND sector_id = ' . (int) $sectorID;
		if (sqlNumRows($result = sqlQuery($sql))==1){
			$rv = sqlFetchAssoc($result);
		}
		return $rv;
	}
	
	// Still used on choosewhere
	public static function getPrimarySectorDetails($locationID){
		$stickySector = getRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link', 'sector_id', array('location_id' => $locationID, 'sticky_flag' => 1));
		return $stickySector;
	}
	
	// Still used on choosewhere
	public static function getScoreSectorDetails($locationID, $sectorID){
		$sql = 'SELECT score_id
				FROM ' .DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link
				WHERE location_id = ' . (int) $locationID . '
					AND sector_id = ' . (int) $sectorID;
		if (sqlNumRows($result = sqlQuery($sql))==1){
			return sqlFetchAssoc($result);
		}
		return false;
	}

	
	// Create a new location
	public static function createLocation($details, $customDetails = array()) {
		
		// When creating a new location, set status as pending if setting is set
		if (setting('zenario_location_manager__enable_pending_status')) {
			$details['status'] = 'pending';
		}
		
		// Insert new location
		$locationID = insertRow(
			ZENARIO_LOCATION_MANAGER_PREFIX . 'locations', 
			$details
		);
		
		// Add any custom dataset fields
		if ($customDetails) {
			$customDetails['location_id'] = $locationID;
			insertRow(
				ZENARIO_LOCATION_MANAGER_PREFIX . 'locations_custom_data', 
				$customDetails
			);
		}
		
		return $locationID;
	}
	
	public static function deleteLocation($ID){
		$sql = 'DELETE 
				FROM ' .DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'locations 
				WHERE id=' . (int)$ID;
		sqlQuery($sql);
		
		sendSignal('eventLocationDeleted', array("locationId" => $ID));
		
	}

	public static function activateLocation($ID){
		$sql = "UPDATE
					 " .DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations 
				SET
					status='active'
				WHERE 
					id=" . (int)$ID;
		sqlQuery($sql);
	}

	public static function suspendLocation($ID){
		$sql = "UPDATE
					" .DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations 
				SET
					status='suspended'
				WHERE 
					id=" . (int)$ID;
		sqlQuery($sql);
	}
	
	public static function markLocationAsPending($ID) {
		$sql = "UPDATE
					" .DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations 
				SET
					status='pending'
				WHERE 
					id=" . (int)$ID;
		sqlQuery($sql);
	}

	public static function deleteSector($ID, $recurseCount = 0){
		$recurseCount++;
	
		if ($recurseCount>10) {
			echo "Recursed beyond limit of 10 recursions. Exiting";
			exit;
		}
	
		$sql = 'DELETE 
				FROM ' .DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'sectors 
				WHERE id=' . (int)$ID;
				
		sqlQuery($sql);

		$childSectors = self::getChildSectorsIndexedByIdOrderedByName($ID);
		
		if (sizeof($childSectors)>0) {
			foreach ($childSectors as $id => $childSector) {
				self::deleteSector($id,$recurseCount);
			}
		}

		sendSignal('eventSectorDeleted', array("sectorId" => $ID));
	}

	public static function removeSector($locationId,$sectorId=false){
		$sql = "SELECT id
				FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "sectors
				WHERE parent_id = " . (int) $sectorId;
				
		$result = sqlQuery($sql);
		
		if (sqlNumRows($result)>0) {
			while ($row = sqlFetchArray($result)) {
				$sql = "DELETE
						FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link
						WHERE location_id = " . (int) $locationId . "
							AND sector_id = " . (int) $row[0];
							
				$result2 = sqlQuery($sql);
			}
		}
	
		$sql = 'DELETE 
				FROM ' .DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link 
				WHERE location_id = ' . (int) $locationId;
				
		if ($sectorId) {
			$sql .= ' AND sector_id = ' . (int) $sectorId;
		}
					
		sqlQuery($sql);
	}

	public static function changeScore($locationID, $sectorID, $mode){
		$sql = "SELECT score_id
				FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link
				WHERE location_id = ' . (int) $locationID . '
					AND sector_id = ' . (int) $sectorID;
					
		$result = sqlQuery($sql);
		
		$row = sqlFetchArray($result);
		
		if ($mode=="increase") {
			if ($row[0]<5) {
				$sql = 'UPDATE ' .DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link
						SET score_id = score_id + 1
						WHERE location_id = ' . (int) $locationID . '
							AND sector_id = ' . (int) $sectorID;
			}
		}
	
		if ($mode=="decrease") {
			if ($row[0]>1) {
				$sql = 'UPDATE ' .DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link
						SET score_id = score_id - 1
						WHERE location_id = ' . (int) $locationID . '
							AND sector_id = ' . (int) $sectorID;
			}
		}
		
		sqlQuery($sql);
	}
	
	public static function checkSectorNameUnique($name, $ID, $parentID) {
		$sql = "SELECT 
					id
				FROM " 
					. DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "sectors
				WHERE 
						name = '" . sqlEscape($name) . "' ";
		if ((int)$parentID){
			$sql .=" AND	parent_id = " . (int) $parentID;
		}
		
		if ($ID) {
			$sql .= " AND id <> " . (int) $ID;
		}
		
		$result = sqlQuery($sql);
		
		if (sqlNumRows($result)>0) {
			return false;
		}
		return true;
	}
	
	public static function checkScoreNameUnique($name, $ID) {
		$sql = "SELECT id
				FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "scores
				WHERE name = '" . sqlEscape($name) . "'";
		
		if ($ID) {
			$sql .= " AND id <> " . (int) $ID;
		}
		
		$result = sqlQuery($sql);
		
		if (sqlNumRows($result)>0) {
			return false;
		}
		return true;
	}
	
	public static function handleMediaUpload($filename, $maxSize, $locationId) {
		$error = array();
		if (arrayKey($_FILES,$filename)) {
			if (is_uploaded_file($_FILES[$filename]['tmp_name'])) {
				if ($_FILES[$filename]['size'] > $maxSize) {
					$error['document'] = adminPhrase('_FILE_UPLOAD_ERROR_SIZE', array('size' => $maxSize));
				}		
				
				$image = getimagesize($_FILES[$filename]['tmp_name']);
				if (!in_array($image['mime'], array('image/jpeg','image/gif','image/png','image/jpg','image/pjpeg'))) {
					$error['document'] = adminPhrase('_INVALID_IMAGE_TYPE');
				}
			} else {
				switch ($_FILES[$filename]['error']) {
					case 1:
						$error['document'] = adminPhrase( '_FILE_UPLOAD_ERROR_SIZE', array( 'size' => $maxSize ) );
						break;
					case 2:
						$error['document'] = adminPhrase( '_FILE_UPLOAD_ERROR_SIZE', array( 'size' => $maxSize ) );
						break;
					case 3:
						$error['document'] = adminPhrase( '_FILE_UPLOAD_ERROR_PARTIAL_UPLOAD', array( 'size' => $maxSize ) );
						break;
					default:
						$error['document'] = adminPhrase( '_FILE_UPLOAD_ERROR_EMPTY', array( 'size' => $maxSize ) );
				}		
			}
		
			if (!count($error)) {
				return self::addImage($locationId, $_FILES[$filename]['tmp_name'], $_FILES[$filename]['name']);
			} else {
				return $error;
			}
		} else {
			return array(adminPhrase("_ERR_FILE_SIZE_GREATER_THAN_SERVER_LIMIT"));
		}
	}

		
	public static function makeLocationImageSticky($locationId,$image_id) {
		$sql = "
			UPDATE " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_images SET
				sticky_flag = 0
			WHERE location_id = " . (int) $locationId;
		sqlQuery($sql);
		
		$sql = "
			UPDATE " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_images SET
				sticky_flag = 1
			WHERE location_id = " . (int) $locationId . "
			  AND image_id = ". (int) $image_id;
		sqlQuery($sql);
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
		
		setRow(
			ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link",
			array(
				"location_id" => $locationId, 
				"sector_id" => $sectorId, 
				"score_id" => 3, 
				"sticky_flag" => $stickyFlag
			),
			array("location_id" => $locationId, "sector_id" => $sectorId)
		);
		
		$sectorDetails = self::getSectorDetails($sectorId);
		
		if (issetArrayKey($sectorDetails,"parent_id")) {
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
					" . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_region_link
				SET 
					location_id = " . (int) $locationId . ",
					region_id = " . (int)  $regionId;

		$result = sqlQuery($sql);

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
		
		$sql = "REPLACE INTO " . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link
				SET location_id = " . (int) $locationId . ",
					sector_id = " . (int)  $sectorId . ",
					score_id = 3";
		
		if (!$sector['parent_id'] && !$stickyFlagSet) {
			$sql .= ", sticky_flag = 1";
		}

		$result = sqlQuery($sql);

		if ($sector['parent_id'] && (!is_array($locationSectors) || !in_array($sector['parent_id'],$locationSectors))) {
			$this->addSectorToLocation($sector['parent_id'], $locationId, $stickyFlagSet, $locationSectorCount, $locationSectors,$recurseCount);
		}
	}

	public function setLocationSectors($locationId, $sectors) {
		$currentStickySector=getRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link', 'sector_id',array('sticky_flag' => 1,'location_id' => $locationId));
	
		$oldStickyFlag = 0;
		$stickyflag = 0;
		$stickySet = 0;
		foreach ($sectors as $value) {
			if ($value==$currentStickySector){
				$oldStickyFlag = $value;
			}
		}
			
		deleteRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link', array('location_id' => $locationId));

		foreach ($sectors as $value) {
			$sector = self::getSectorDetails($value);
			
			if (!$oldStickyFlag){
				if ($sector['parent_id']) {
					$stickyflag = 0;
				} else {
					$stickyflag = 1;
				}
			}
			
			insertRow(
				ZENARIO_LOCATION_MANAGER_PREFIX . 'location_sector_score_link',
				array(
					'sector_id' => $value,
					'location_id' => $locationId,
					'score_id' => 3,
					'sticky_flag' => ($value==$oldStickyFlag) | ($stickyflag & !$stickySet)));
			$stickySet |= $stickyflag;
		}
	}

	public static function removeLocationFromSector($locationId, $sectorId, $recurseCount = 0) {
		$recurseCount++;
		
		if ($recurseCount>20) {
			echo "Recursed out of control. Exiting";
			exit;
		}
		
		deleteRow(
			ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link",
			array("location_id" => $locationId, "sector_id" => $sectorId)
		);
		
		$result = getRows(
			ZENARIO_LOCATION_MANAGER_PREFIX . "sectors",
			array("id"),
			array("parent_id" => $sectorId)
		);
		
		if ($result) {
			while ($row = sqlFetchAssoc($result)) {
				self::removeLocationFromSector($locationId,$row['id'],$recurseCount);
			}
		}
	}

	public static function removeAllRegionsFromLocation($locationID){
		
		$sql = "DELETE FROM 
					" . DB_NAME_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_region_link 
				WHERE
					location_id = " . (int) $locationID;
		sqlQuery($sql);
	}
	
	public static function eventContentDeleted ($cID,$cType,$cVersion) {
		//check to see if status is trashed or deleted
		$contentStatus = getRow('content_items', 'status', array('equiv_id' => $cID, 'type'=> $cType));
		
		if($contentStatus == 'deleted' || $contentStatus == 'trashed') {
			updateRow(ZENARIO_LOCATION_MANAGER_PREFIX . "locations",
					array("equiv_id" => null,"content_type" => null),
						array("equiv_id" => $cID,"content_type" => $cType));
		}
	}

	public static function eventLocationDeleted ($locationID) {
		
		// Remove parent_id from any child locations
		updateRow(
			ZENARIO_LOCATION_MANAGER_PREFIX . 'locations',
			array('parent_id' => null),
			array('parent_id' => $locationID)
		);
		
		// Delete sector score links
		deleteRow(
			ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link",
			array("location_id" => $locationID)
		);
	}

	public static function eventSectorDeleted ($sectorId) {
		deleteRow(ZENARIO_LOCATION_MANAGER_PREFIX . "location_sector_score_link",array("sector_id" => $sectorId));
	}
	
	
	
    public static function locationsImages($locationId) {
        return getRowsArray(ZENARIO_LOCATION_MANAGER_PREFIX. 'location_images', 'image_id', array('location_id' => $locationId), 'ordinal');
    }
    public static function addImage($locationId, $location, $filename = false) {
        $image_id = addFileToDatabase('location', $location, $filename, true);
        
        if (!$filename) {
        	$filename = basename($location);
        }
        
        setRow(ZENARIO_LOCATION_MANAGER_PREFIX. 'location_images', array('filename' => $filename), array('image_id' => $image_id, 'location_id' => $locationId));
        return $image_id;
    }
    
    public static function deleteImage($locationId, $imageId) {
        deleteRow( ZENARIO_LOCATION_MANAGER_PREFIX. 'location_images', array('image_id' => $imageId, 'location_id' => $locationId));
        if (!checkRowExists(ZENARIO_LOCATION_MANAGER_PREFIX. 'location_images', array('image_id' => $imageId))) {
            deleteRow( 'files', array('id' => $imageId, 'usage' => 'location'));
        }
    }
    
	// Centralised list for location status
	public static function locationStatus($mode, $value = false) {
		switch ($mode) {
			case ZENARIO_CENTRALISED_LIST_MODE_INFO:
				return array('can_filter' => false);
			case ZENARIO_CENTRALISED_LIST_MODE_LIST:
				return array(
					'pending' => 'Pending',
					'active' => 'Active',
					'suspended' => 'Suspended'
				);
		}
	}
}

