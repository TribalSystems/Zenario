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

class zenario_location_map_and_listing extends module_base_class {
	
	protected $dataset;
	protected $datasetFields;
	protected $fields;
	
	protected $data = array(
		'tabs' => array(),
		'locations' => array(),
		'locations_map_info' => array(),
		'countries' => array(),
		'country_id' => '',
		'postcode' => '');



	public function init() {
		//Look up details on the locations dataset
		$this->dataset = getDatasetDetails(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations');
		$this->datasetFields = getDatasetFieldsDetails($this->dataset['id']);
		
		//Check to see if any fields were picked in the Plugin Settings
		if ($this->setting('list_by_field')) {
			foreach (array(
				'field1' => 'field1_title', 'field2' => 'field2_title', 'field3' => 'field3_title'
			) as $fieldIdSetting => $titleSetting) {
				if ($this->setting($fieldIdSetting)
				 && ($field = getDatasetFieldDetails($this->setting($fieldIdSetting)))
				 && ($field['type'] == 'checkbox')) {
					$this->fields[] = $field;
					
					$this->data['tabs'][] = array(
						'db_column' => $field['db_column'],
						'css_class' => 'zenario_lmal_tab__'. $field['db_column'],
						'marker_css_class' => 'zenario_lmal_marker__'. $field['db_column'],
						'title' => $this->setting($titleSetting),
						'locations' => array());
				}
			}
		}
		
		if (empty($this->fields)) {
			$this->data['tabs'][] = array('locations' => array());
		}
		
		if ($this->setting('filter_by_country')) {
			$this->registerGetRequest('country_id');
			
			//Load a list of countries that have locations
			$this->data['countries'] = $this->getCountryList();
			
			$this->data['openForm'] = $this->openForm();
			$this->data['closeForm'] = $this->closeForm();
			
			//Attempt to get a country to show
			//Use the country in the request if there is one...
			if (!empty($_REQUEST['country_id']) && !empty($this->data['countries'][$_REQUEST['country_id']])) {
				$this->data['country_id'] = $_REQUEST['country_id'];
			
			//...or check in the session...
			} elseif (!empty($_SESSION['country_id']) && !empty($this->data['countries'][$_SESSION['country_id']])) {
				$this->data['country_id'] = $_SESSION['country_id'];
			
			//...or try a geo-ip lookup...
			} elseif (inc('zenario_geoip_lookup')
			 && ($countryId = zenario_geoip_lookup::getCountryISOCodeForIp(visitorIP()))
			 && (!empty($this->data['countries'][$countryId]))) {
				$this->data['country_id'] = $countryId;
			
			//...or check the default option...
			} elseif ($this->setting('default_country') && !empty($this->data['countries'][$this->setting('default_country')])) {
				$this->data['country_id'] = $this->setting('default_country');
			
			//...otherwise pick the first in the list
			} else {
				foreach ($this->data['countries'] as $countryId => $countryName) {
					$this->data['country_id'] = $countryId;
					break;
				}
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
		
		$this->data['mapId'] = $this->containerId. '_map';
		$this->data['mapIframeId'] = $this->containerId. '_map_iframe';
		$this->data['mapIframeSrc'] = $this->showSingleSlotLink(array('display_map' => 1, 'country_id' => $this->data['country_id'], 'postcode' => $this->data['postcode']));
		
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
				loc.hide_pin";
		
		if (!empty($this->fields)) {
			foreach($this->fields as $i => $field) {
				$sql .= ",
					cd.`". sqlEscape($field['db_column']). "` AS tab". $i;
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
			FROM ". DB_NAME_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "locations AS loc
			LEFT JOIN ". DB_NAME_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "locations_custom_data AS cd
				ON cd.location_id = loc.id
			WHERE loc.status = 'active'";
		
		if ($this->setting('filter_by_country')) {
			$sql .="
			  AND loc.country_id = '". sqlEscape($this->data['country_id']). "'";
		}
		
		if (!empty($this->fields)) {
			$sql .= "
				AND (";
			foreach($this->fields as $i => $field) {
				if ($i) {
					$sql .= " OR ";
				}		
				$sql .= "cd.`". sqlEscape($field['db_column']). "` = 1";
			}
			$sql .= ")";
		}
		
		$sql .= "
			AND (loc.latitude IS NOT NULL AND loc.longitude IS NOT NULL) AND (loc.latitude <> 0 AND loc.longitude <> 0)";
		
		$sql .= "
			ORDER BY name";
		
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)) {
			
			$row['css_class'] = 'zenario_lmal_marker';
			$row['htmlId'] = $this->containerId. '_loc_'. $row['id'];
			$row['listingClick'] = "
				zenario_location_map_and_listing.listingClick(this, ". (int) $row['id']. ");";
			
			$this->data['locations'][] = $row;
		}
		
		// Filter locations by distance to postcode
		$lat = $lng = $label = $error = false;
		if ($this->setting('enable_postcode_search') && !empty($this->data['postcode'])) {
			$json = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($this->data['postcode']) . '&components=postal_code:' .  urlencode($this->data['postcode']));
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
					$error = $this->phrase('Unable to lookup postcode. Please try again later.');
					break;
			}
		}
		
		$this->data['postcode_error'] = $error;
		
		// Hide locations list initally if no filters and setting
		$hide_locations_list_before_search = $this->setting('hide_locations_list_before_search');
		if ($hide_locations_list_before_search
			&& (
				($this->setting('filter_by_country') && $this->data['country_id'])
				|| ($this->setting('enable_postcode_search') && $this->data['postcode'] && $this->data['postcode_search_success'])
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
			$this->data['locations_map_info'][] = array(
				'id' => $row['id'],
				'htmlId' => $row['htmlId'],
				'latitude' => $row['latitude'],
				'longitude' => $row['longitude'],
				'map_zoom' => $row['map_zoom'],
				'hide_pin' => $row['hide_pin'],
				'name' => $row['name'],
				'css_class' => $row['css_class']
			);
		}
		
		// If searching on postcode, add postcode marker to map
		if ($this->setting('enable_postcode_search') && !empty($this->data['postcode']) && $lat && $lng && $label) {
			$htmlId = $this->containerId . '_postcode_placeholder';
			$placeholder = array(
				'id' => 'postcode',
				'htmlId' => $htmlId,
				'latitude' => $lat,
				'longitude' => $lng,
				'map_zoom' => 16,
				'hide_pin' => false,
				'name' => $label,
				'css_class' => 'postcode-marker'
			);
			$this->data['locations_map_info'][] = $placeholder;
		}
	}
	
	protected function filterLocationsByLatLng($lat, $lng) {
		$distances = array();
		$locations = array();
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
				$location['postcode_distance'] = $this->phrase('[[distance]] [[units]]', array('distance' => $formattedDistance, 'units' => $units));
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
		$countries = array();
		
		$sql = "
			SELECT SUBSTR(code, 15), local_text
			FROM ". DB_NAME_PREFIX. "visitor_phrases AS vp_cn
			WHERE module_class_name = 'zenario_country_manager'
			  AND language_id = '". sqlEscape(cms_core::$langId). "'
			  AND code IN (
				SELECT CONCAT('_COUNTRY_NAME_', loc.country_id)
				FROM ". DB_NAME_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "locations AS loc
				LEFT JOIN ". DB_NAME_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "locations_custom_data AS cd
					ON cd.location_id = loc.id
				WHERE loc.status = 'active'";
		
		if (!empty($this->fields)) {
			$sql .= "
				AND (";
			foreach($this->fields as $i => $field) {
				if ($i) {
					$sql .= " OR ";
				}		
				$sql .= "cd.`". sqlEscape($field['db_column']). "` = 1";
			}
			$sql .= ")";
		}
		
		$sql .= "
				GROUP BY loc.country_id
			)
			ORDER BY local_text";
		
		$result = sqlQuery($sql);
		while($row = sqlFetchRow($result)) {
			$countries[$row[0]] = $row[1];
		}
		return $countries;
	}

	public function showSlot() {
		if (!request('display_map')) {
			$this->twigFramework($this->data);
		} else {
			echo '
				<style>
					body {
						overflow: hidden !important;
					}
				</style>
				
				<script id="google_api" type="text/javascript" src="https://maps.google.com/maps/api/js?sensor=false"></script>';
			
			//Add icons so the JavaScript code can look up the background image from them
			$cssClasses = array();
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
			
			
			//Note: Using callScript() in your showSlot() method will only work if this isn't an AJAX reload!
			$this->callScript(
				'zenario_location_map_and_listing',
				'initMap',
				$this->containerId,
				$this->data['locations_map_info'],
				(bool) $this->setting('allow_scrolling'));
		}
	}

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				$fields['first_tab/field1']['values'] =
				$fields['first_tab/field2']['values'] =
				$fields['first_tab/field3']['values'] =
					listCustomFields(
						ZENARIO_LOCATION_MANAGER_PREFIX. 'locations',
						$flat = false, $filter = 'boolean_and_groups_only', $customOnly = true, $useOptGroups = true);
				break;

		}
	}


	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path){
			case 'plugin_settings':
				break;
		}
	}


}