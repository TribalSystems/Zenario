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

class zenario_location_map_and_listing extends ze\moduleBaseClass {
	
	protected $dataset;
	protected $datasetFields;
	protected $fields;
	
	protected $data = [
		'tabs' => [],
		'locations' => [],
		'locations_map_info' => [],
		'countries' => [],
		'country_id' => '',
		'postcode' => ''
	];
	
	public function init() {
		ze::requireJsLib('https://maps.googleapis.com/maps/api/js?key=' . urlencode(ze::setting('google_maps_api_key')));
		
		//Look up details on the locations dataset
		$this->dataset = ze\dataset::details(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations');
		$this->datasetFields = ze\dataset::fieldsDetails($this->dataset['id']);
		
		//Check to see if any fields were picked in the Plugin Settings
		if ($this->setting('list_by_field')) {
			foreach ([
				'field1' => 'field1_title', 'field2' => 'field2_title', 'field3' => 'field3_title'
			] as $fieldIdSetting => $titleSetting) {
				if ($this->setting($fieldIdSetting)
				 && ($field = ze\dataset::fieldDetails($this->setting($fieldIdSetting)))
				 && ($field['type'] == 'checkbox')) {
					$this->fields[] = $field;
					
					$this->data['tabs'][] = [
						'db_column' => $field['db_column'],
						'css_class' => 'zenario_lmal_tab__'. $field['db_column'],
						'marker_css_class' => 'zenario_lmal_marker__'. $field['db_column'],
						'title' => $this->setting($titleSetting),
						'locations' => []
					];
				}
			}
		}
		
		if (empty($this->fields)) {
			$this->data['tabs'][] = ['locations' => []];
		}
		
		if ($this->setting('filter_by_country')) {
			$this->registerGetRequest('country_id');
			
			//Load a list of countries that have locations
			$this->data['countries'] = $this->getCountryList();
			$this->data['countries']['xx'] = 'Any country';

			$this->data['openForm'] = $this->openForm();
			$this->data['closeForm'] = $this->closeForm();
			
			//Attempt to get a country to show:
			$countryId = false;
			
			//1) The plugin is set to display a default country, and the default country is on the list of countries...
			if ($this->setting('default_country_options') == 'select_country' && ($defaultCountry = $this->setting('default_country')) && !empty($this->data['countries'][$defaultCountry])) {
				if (!empty($_REQUEST['country_id'])) {
					//Accept input from the country switcher...
					$this->data['country_id'] = $_REQUEST['country_id'];
				} else {
					//... or use the plugin setting.
					$this->data['country_id'] = $defaultCountry;
				}
			
			//2) ...or the plugin is set to try a GeoIP lookup...
			} elseif ($this->setting('default_country_options') == 'geo_ip' && ze\module::inc('zenario_geoip_lookup')) {
				if (!empty($_REQUEST['country_id'])) {
					//Accept input from the country switcher...
					$this->data['country_id'] = $_REQUEST['country_id'];
				} else {
					//... or proceed with the GeoIP lookup.
					$countryId = zenario_geoip_lookup::getCountryISOCodeForIp(ze\user::ip());
				
					//Country identified and is on the list of countries
					if ($countryId && !empty($this->data['countries'][$countryId])) {
						$this->data['country_id'] = $countryId;
					//Country is not on the list of countries
					} else {
						$defaultGeoIpCountry = $this->setting('geo_ip_default_country');
						
						if ($defaultGeoIpCountry && !empty($this->data['countries'][$defaultGeoIpCountry])) {
							$this->data['country_id'] = $defaultGeoIpCountry;
						}
					}
				}
			
			//3) ...or the plugin is not set to use any defaults. In that case, use the country in the request if there is one...
			} elseif (!empty($_REQUEST['country_id']) && !empty($this->data['countries'][$_REQUEST['country_id']])) {
				$this->data['country_id'] = $_REQUEST['country_id'];
			
			//4) ...or check in the cookies...
			} elseif (!empty($_COOKIE['country_id']) && !empty($this->data['countries'][$_COOKIE['country_id']])) {
				$this->data['country_id'] = $_COOKIE['country_id'];
			
			//5) ...or check in the session.
			} elseif (!empty($_SESSION['country_id']) && !empty($this->data['countries'][$_SESSION['country_id']])) {
				$this->data['country_id'] = $_SESSION['country_id'];
			}
			
			//6) If all else fails, just show the whole world.
			if (empty($this->data['country_id'])) {
				$this->data['country_id'] = 'xx';
			}
		}
		
		if ($this->setting('enable_postcode_search')) {
			$this->registerGetRequest('postcode');
			
			$this->data['enable_postcode_search'] = true;
			
			$this->data['openForm'] = $this->openForm();
			$this->data['closeForm'] = $this->closeForm();
			
			//Use the postcode in the request if there is one...
			if (!empty($_REQUEST['postcode'])) {
				$this->data['postcode'] = $_REQUEST['postcode'];
			}
		}
		
		if ($this->setting('show_list_and_map_in_seperate_tabs')) {
			$this->data['show_list_and_map_in_seperate_tabs'] = true;
			$this->data['listViewOnClick'] = $this->refreshPluginSlotJS();
			$this->data['mapViewOnClick'] = $this->refreshPluginSlotJS('map_view=1');
			$this->data['currentView'] = ze::request('map_view') ? 'map' : 'list';
		}
		
		$this->data['mapId'] = $this->containerId. '_map';
		$this->data['mapIframeId'] = $this->containerId. '_map_iframe';
		$this->data['mapIframeSrc'] = $this->showSingleSlotLink(['display_map' => 1, 'country_id' => $this->data['country_id'], 'postcode' => $this->data['postcode']]);
		
		$this->loadLocations();
		
		return true;
	}
	
	protected function loadLocations() {
		$sql = "
			SELECT
				loc.id,
				loc.description AS name,
				loc.address1,
				loc.address2,
				loc.locality,
				loc.city,
				loc.state,
				loc.postcode,
				loc.country_id,
				loc.equiv_id,
				loc.content_type,
				loc.latitude,
				loc.longitude, 
				loc.map_zoom, 
				loc.hide_pin,
				vp_cn.local_text AS country";
		
		if (!empty($this->fields)) {
			foreach($this->fields as $i => $field) {
				$sql .= ",
					cd.`". ze\escape::sql($field['db_column']). "` AS tab". $i;
			}
		} else {
			$sql .= ",
				1 AS tab0";
		}
		
		if (!empty($this->datasetFields)) {
			$sql .= ",
				cd.*";
		}
		
		$sql .= "
			FROM ". DB_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "locations AS loc";
		
		if ($sectorId = $this->setting('sector')) {
			$sql .= "
				INNER JOIN ". DB_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "location_sector_score_link AS lnk
				   ON loc.id = lnk.location_id
				   AND lnk.sector_id = ". (int)$sectorId;
		}
		if ($regionId = $this->setting('region')) {
			$sql .= "
				INNER JOIN ". DB_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "location_region_link AS lrl 
					ON loc.id = lrl.location_id 
				   AND lrl.region_id = ". (int)$regionId;
		}
		
		$sql .= "
			LEFT JOIN ". DB_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "locations_custom_data AS cd
				ON cd.location_id = loc.id
			LEFT JOIN ". DB_PREFIX. "visitor_phrases AS vp_cn
				   ON loc.country_id IS NOT NULL
				  AND module_class_name = 'zenario_country_manager'
				  AND CONCAT('_COUNTRY_NAME_', loc.country_id) = vp_cn.code 
				  AND vp_cn.language_id = '". ze\escape::asciiInSQL(ze::$visLang). "'
			WHERE loc.status = 'active'";
		
		if ($this->setting('filter_by_country') && $this->data['country_id'] != 'xx') {
			$sql .= "
				AND loc.country_id = '". ze\escape::asciiInSQL($this->data['country_id']). "'";
		}
		if ($countryId = $this->setting('country')) {
			$sql .= "
				AND loc.country_id = '". ze\escape::asciiInSQL($countryId). "'";
		}
		
		if (($fieldId = $this->setting('location_filter'))
			&& ($dbColumnName  = ze\row::get('custom_dataset_fields', 'db_column', $fieldId))
		) {
			$sql .= "
				AND cd.`" . ze\escape::sql($dbColumnName) . "` = 1";
		}
		
		if (!empty($this->fields)) {
			$sql .= "
				AND (";
			foreach($this->fields as $i => $field) {
				if ($i) {
					$sql .= " OR ";
				}		
				$sql .= "cd.`". ze\escape::sql($field['db_column']). "` = 1";
			}
			$sql .= ")";
		}
		
		$sql .= "
			AND (loc.latitude IS NOT NULL AND loc.longitude IS NOT NULL) AND (loc.latitude <> 0 AND loc.longitude <> 0)";
		
		$orderBy = [];
		for ($i = 1; $i <= 3; $i++) {
			switch ($this->setting('order_by_' . $i)){
				case 'sector_score':
					if ($sectorId) {
						$orderBy[] = 'lnk.score_id DESC';
					}
					break;
				case 'country':
					$orderBy[] = 'country ASC';
					break;
				case 'name':
					$orderBy[] = 'name ASC';
					break;
			}
		}
		if (!empty($orderBy)) {
			$sql .= "
				ORDER BY ". implode(', ', $orderBy);
		} else {
			$sql .= "
				ORDER BY name";
		}
		
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			
			$row['css_class'] = 'zenario_lmal_marker';
			$row['htmlId'] = $this->containerId. '_loc_'. $row['id'];
			$row['listingClick'] = "
				zenario_location_map_and_listing.listingClick(this, ". (int) $row['id']. ");";
			
			$imageId = ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX. 'location_images','image_id',['sticky_flag' => '1', 'location_id' => $row['id']]);
			
			$row['alt_tag'] = ze\row::get('files', 'alt_tag', $imageId);
			$row['map_image'] = self::getStickyImageLink($imageId,"map");
			$row['list_image'] = self::getStickyImageLink($imageId,"list");
			
			$row['descriptive_page'] = false;
			if($this->setting('show_view_button') && $row['equiv_id'] && $row['content_type']){
				$cID = $row['equiv_id'];
				$cType = $row['content_type'];
				ze\content::langEquivalentItem($cID, $cType);
			
				if(ze\priv::check() || ze\content::isPublished($cID, $cType)){
					$row['descriptive_page'] = ze\link::toItem($cID, $cType, false);
				}
			}
			
			$this->data['locations'][] = $row;
		}
		$this->data['button_label'] =  $this->phrase($this->setting('button_label'));
		
		
		// Filter locations by distance to postcode
		$lat = $lng = $label = $error = false;
		if ($this->setting('enable_postcode_search') && !empty($this->data['postcode'])) {
			//Debug code if we ever need it
			//echo 'https://maps.googleapis.com/maps/api/geocode/json?key=' . urlencode(ze::setting('google_maps_api_key')) . '&address=' . urlencode($this->data['postcode']) . '&components=postal_code:' .  urlencode($this->data['postcode']);
			
			$json = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?key=' . urlencode(ze::setting('google_maps_api_key')) . '&address=' . urlencode($this->data['postcode']) . '&components=postal_code:' .  urlencode($this->data['postcode']));
			$data = json_decode($json, true);
			switch ($data['status']) {
				case 'OK':
					$lat = $data['results'][0]['geometry']['location']['lat'];
					$lng = $data['results'][0]['geometry']['location']['lng'];
					$label =  $data['results'][0]['formatted_address'];
					$this->data['postcode_search_success'] = true;
					$this->filterLocationsByLatLng($lat, $lng);
					break;
				case 'ZERO_RESULTS':
					$error = $this->phrase('Unable to find postcode.');
					break;
				case 'OVER_QUERY_LIMIT':
				case 'REQUEST_DENIED':
				case 'INVALID_REQUEST':
				case 'UNKNOWN_ERROR':
					$error = $this->phrase('Unable to lookup postcode. Please try again later. (Error: [[error_code]])', ['error_code' => $data['status']]);
					break;
			}
		}
		
		$this->data['postcode_error'] = $error;
		
		// Hide locations list initally if no filters and setting
		$hide_locations_list_before_search = $this->setting('hide_locations_list_before_search');
		if ($hide_locations_list_before_search
			&& (
				($this->setting('filter_by_country') && $this->data['country_id'])
				|| ($this->setting('enable_postcode_search') && $this->data['postcode'] && isset($this->data['postcode_search_success']))
			)
			|| !$hide_locations_list_before_search
		) {
			$this->data['show_locations_list'] = true;
		}
		
		foreach ($this->data['locations'] as &$rowr) {
			foreach ($this->data['tabs'] as $i => &$tab) {
				if (!empty($rowr['tab'. $i])) {
					$tab['locations'][] = $rowr;
					
					if (!empty($tab['db_column'])) {
						$rowr['css_class'] .= ' '. $rowr['css_class']. '__'. $tab['db_column'];
					}
				}
			}
		}
		
		foreach ($this->data['locations'] as $row) {
			$this->data['locations_map_info'][] = [
				'id' => $row['id'],
				'htmlId' => $row['htmlId'],
				'latitude' => $row['latitude'],
				'longitude' => $row['longitude'],
				'map_zoom' => $row['map_zoom'],
				'hide_pin' => $row['hide_pin'],
				'name' => $row['name'],
				'css_class' => $row['css_class']
			];
		}
		
		// If searching on postcode, add postcode marker to map
		if ($this->setting('enable_postcode_search') && !empty($this->data['postcode']) && $lat && $lng && $label) {
			$htmlId = $this->containerId . '_postcode_placeholder';
			$placeholder = [
				'id' => 'postcode',
				'htmlId' => $htmlId,
				'latitude' => $lat,
				'longitude' => $lng,
				'map_zoom' => 16,
				'hide_pin' => false,
				'name' => $label,
				'css_class' => 'postcode-marker'
			];
			$this->data['locations_map_info'][] = $placeholder;
		}
	}
	
	protected function filterLocationsByLatLng($lat, $lng) {
		$distances = [];
		$locations = [];
		$showUnits = $this->setting('search_result_distance');
		$units = false;
		if ($showUnits == 'km') {
			$units = 'km';
		} elseif ($showUnits == 'm') {
			$units = 'miles';
		}
		foreach ($this->data['locations'] as $index => $location) {
			$distance = self::getDistance($lat, $lng, $location['latitude'], $location['longitude'], $units);
			$distances[$index] = $distance;
		}
		asort($distances);
		$search_result_count = $this->setting('search_result_count');
		$count = 1;
		foreach ($distances as $index => $distance) {
			$location = $this->data['locations'][$index];
			if ($count == 1) {
				$location['nearest'] = true;
			}
			if ($units) {
				$formattedDistance = round($distance, 2);
				$location['postcode_distance'] = $this->phrase('[[distance]] [[units]]', ['distance' => $formattedDistance, 'units' => $units]);
			}
			$location['postcode_index'] = $count . '.';
			$locations[] = $location;
			if (($search_result_count != 'unlimited') && (++$count > $search_result_count)) {
				break;
			}
		}
		$this->data['locations'] = $locations;
	}
	
	protected static function getDistance($lat1, $lon1, $lat2, $lon2, $unit) {
		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;
		if ($unit == 'km') {
			return $miles * 1.609344;
		}
		return $miles;
	}
	
	protected function getCountryList() {
		$countries = [];
		
		$sql = "
			SELECT SUBSTR(code, 15), local_text
			FROM ". DB_PREFIX. "visitor_phrases AS vp_cn
			WHERE module_class_name = 'zenario_country_manager'
			  AND language_id = '". ze\escape::asciiInSQL(ze::$visLang). "'
			  AND code IN (
				SELECT CONCAT('_COUNTRY_NAME_', loc.country_id)
				FROM ". DB_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "locations AS loc
				LEFT JOIN ". DB_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "locations_custom_data AS cd
					ON cd.location_id = loc.id
				WHERE loc.status = 'active'";
		
		if (!empty($this->fields)) {
			$sql .= "
				AND (";
			foreach($this->fields as $i => $field) {
				if ($i) {
					$sql .= " OR ";
				}		
				$sql .= "cd.`". ze\escape::sql($field['db_column']). "` = 1";
			}
			$sql .= ")";
		}
		
		$sql .= "
				GROUP BY loc.country_id
			)
			ORDER BY local_text";
		
		$result = ze\sql::select($sql);
		while($row = ze\sql::fetchRow($result)) {
			$countries[$row[0]] = $row[1];
		}
		return $countries;
	}
	
	protected function checkCountryHasLocations($countryId) {
		$sql = "
			SELECT 1
			FROM " . DB_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "locations
			WHERE status = 'active'
			AND country_id = '" . ze\escape::sql($countryId) . "'";
		$result = ze\sql::select($sql);
		return ze\sql::fetchValue($result);
	}

	public function showSlot() {
		if (!ze::request('display_map')) {
			$this->twigFramework($this->data);
		} else {
			echo '
				<style>
					body {
						overflow: hidden !important;
					}
				</style>';
			
			//Add icons so the JavaScript code can look up the background image from them
			$cssClasses = [];
			foreach ($this->data['locations_map_info'] as $location) {
				$cssClasses[$location['css_class']] = true;
			}
			
			foreach ($cssClasses as $cssClass => $dummy) {
				echo '
					<div
						style="display: none;"
						class="', htmlspecialchars($cssClass), '"
						id="', htmlspecialchars(str_replace(' ', '-', $cssClass)), '"
					></div>';
			}
			
			$this->data['drawingGoogleMap'] = true;
			$this->twigFramework($this->data);
			
			$anyCountry = ($this->data['country_id'] == 'xx' ? true : false);
			//Note: Using callScript() in your showSlot() method will only work if this isn't an AJAX reload!
			$this->callScript(
				'zenario_location_map_and_listing',
				'initMap',
				$this->containerId,
				$this->data['locations_map_info'],
				(bool) $this->setting('allow_scrolling'),
				$anyCountry);
		}
	}

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				$fields['front_end_features/field1']['values'] =
				$fields['front_end_features/field2']['values'] =
				$fields['front_end_features/field3']['values'] =
				$fields['first_tab/location_filter']['values'] =
					ze\datasetAdm::listCustomFields(
						ZENARIO_LOCATION_MANAGER_PREFIX. 'locations',
						$flat = false, $filter = 'text_group_boolean_and_list_only', $customOnly = true, $useOptGroups = true, $hideEmptyOptGroupParents = true);
				break;

		}
	}


	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path){
			case 'plugin_settings':
				$fields['first_tab/order_by_1']['values']['sector_score']['hidden'] = 
				$fields['first_tab/order_by_2']['values']['sector_score']['hidden'] = 
				$fields['first_tab/order_by_3']['values']['sector_score']['hidden'] = 
					!$values['first_tab/sector'];
				
				if ($values['first_tab/country']) {
					if ((int)$values['first_tab/region']) {
						$regionCountry = zenario_country_manager::getCountryOfRegion((int)$values['first_tab/region']);
						if (($regionCountry['id'] ?? false) != $values['first_tab/country']) {
							unset($box['tabs']['first_tab']['fields']['region']['value']);
							unset($box['tabs']['first_tab']['fields']['region']['current_value']);
						}
					}
					$box['tabs']['first_tab']['fields']['region']['pick_items']['path'] = 'zenario__languages/panels/countries/item//' .  $values['first_tab/country'] . '//';
					$box['tabs']['first_tab']['fields']['region']['hidden'] = false;
				} else {
					$box['tabs']['first_tab']['fields']['region']['hidden'] = true;
				}
				
				
				if ($values['front_end_features/show_list_and_map_in_seperate_tabs']) {
					$values['front_end_features/enable_postcode_search'] = false;
				}
				
				if ($values['front_end_features/filter_by_country']) {
					
					$countryHasNoLocationsNotice = [
						'show' => true,
						'type' => 'warning',
						'message' => ze\admin::phrase('Warning: the selected country has no locations. The results may be unpredictable.')
					];
					
					if ($values['front_end_features/default_country_options'] == 'select_country') {
						unset($fields['front_end_features/default_country']['notices_below']);
						
						if ($values['front_end_features/default_country']) {
							if (!self::checkCountryHasLocations($values['front_end_features/default_country'])) {
								$fields['front_end_features/default_country']['notices_below']['country_has_no_locations'] = $countryHasNoLocationsNotice;
							}
						}
					} elseif ($values['front_end_features/default_country_options'] == 'geo_ip') {
						unset($fields['front_end_features/geo_ip_default_country']['notices_below']);
						
						if ($values['front_end_features/geo_ip_default_country']) {
							if (!self::checkCountryHasLocations($values['front_end_features/geo_ip_default_country'])) {
								$fields['front_end_features/geo_ip_default_country']['notices_below']['country_has_no_locations'] = $countryHasNoLocationsNotice;
							}
						}
					}
				}
				
				$hidden = !$values['image/show_images'];
				$this->showHideImageOptions($fields, $values, 'image', $hidden, 'image_2_');
				$this->showHideImageOptions($fields, $values, 'image', $hidden, 'image_');
				break;
		}
	}
	
	public function getStickyImageLink($imageId, $mode) {
		if ($imageId && $mode && $this->setting('show_images')) {
			$url = $width = $height = false;
			if($mode == "map"){
				$widthImage = $this->setting('image_2_width'); 
				$heightImage = $this->setting('image_2_height');
				$canvas = $this->setting('image_2_canvas'); 
				$offset = $this->setting('map_view_thumbnail_offset');
			}else{
				$widthImage = $this->setting('image_width'); 
				$heightImage = $this->setting('image_height');
				$canvas = $this->setting('image_canvas'); 
				$offset = $this->setting('list_view_thumbnail_offset');
			}
			ze\file::imageLink($width, $height, $url, $imageId, $widthImage, $heightImage, $canvas, $offset);
			return $url;
		}
		return false;
	}

}