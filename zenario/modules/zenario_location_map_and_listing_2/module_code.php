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

class zenario_location_map_and_listing_2 extends ze\moduleBaseClass {
	
	protected $dataset;
	protected $datasetFields;
	protected $fields;
	protected $pageLoadNum;
	
	protected $data = [
		'tabs' => [],
		'locations' => [],
		'locations_map_info' => [],
		'countries' => [],
		'country_id' => '',
		'postcode' => ''
	];
	
	public function init() {
		
		//Only load data if this isn't the map
		if (!empty($_REQUEST['display_map'])) {
			return true;
		}
		
		//Get a variable that's different with each page load
		$this->pageLoadNum = str_replace([' ', '.'], '', microtime());
		
		//Look up details on the locations dataset
		$this->dataset = ze\dataset::details(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations');
		$this->datasetFields = ze\dataset::fieldsDetails($this->dataset['id']);
		
		$this->data['tabs'][] = ['locations' => []];
		
		$this->data['mapId'] = $this->containerId. '_map';
		$this->data['mapIframeId'] = $this->containerId. '_map_iframe';
		
		$iframeVars = [];
		$iframeVars['display_map'] = 1;
		$iframeVars['pageLoadNum'] = $this->pageLoadNum;
		$this->data['mapIframeSrc'] = $this->showSingleSlotLink($iframeVars, true);
		
		$dataset = ZENARIO_LOCATION_MANAGER_PREFIX. 'locations';
		//These custom fields must be on the "Filters" tab.
		$this->datasetCustomFields = ze\datasetAdm::listCustomFields($dataset, false, false, true, false, false, false, $specificTab = 'filters');
		
		if ($displayCustomDatasetFieldsOnTheFrontend = $this->setting('display_custom_dataset_fields_on_the_frontend')) {
			//Look for any field on the dataset, not just the ones on the "Filters" tab.
			$allDatasetCustomFields = ze\datasetAdm::listCustomFields($dataset, false, false, true);
			$frontEndDbColumns = [];
			foreach (explode(',', $displayCustomDatasetFieldsOnTheFrontend) as $frontEndFieldId) {
				$frontEndDbColumns[] = $allDatasetCustomFields[$frontEndFieldId]['db_column'];
			}
			$this->data['display_custom_dataset_fields_on_the_frontend'] = $frontEndDbColumns;
		}
		
		$this->locationFilters = [];
		$this->data['list_title'] = "Locations";
		$this->data['Selected_filters'] = [];
		if (!empty($this->datasetCustomFields)) {
			$level1Filters = $level2Filters = [];
			foreach ($this->datasetCustomFields as $customField) {
				$fieldDetails = ze\dataset::fieldDetails($customField['db_column'], $dataset);
				if ($fieldDetails['type'] == 'radios') {
					//This makes it easier to create framework fields.
					$fieldDetails['type'] = 'radio';
				}
				$fieldDataArray = [
					'label' => $fieldDetails['label'],
					'type' => $fieldDetails['type'],
					'note_below' => $fieldDetails['note_below']
				];
				
				if (empty($fieldDetails['parent_id'])) {
					//Level 1 filters
					$level1Filters[$fieldDetails['id']] = $fieldDataArray;
					if (
						(
							$adminHasAppliedLocationFilter = (
								!empty($this->setting('location_display'))
								&& $this->setting('location_display') == "apply_a_filter"
								&& !empty($this->setting('location_dataset_filter_level_1'))
							)
							&& $this->setting('location_dataset_filter_level_1') == $fieldDetails['id']
						)
						|| ze::request('level_1_filter_' . $fieldDetails['id'])
					) {
						$level1Filters[$fieldDetails['id']]['checked'] = true;
						$this->locationFilters[$fieldDetails['id']][$fieldDetails['db_column']] = 1;
						
						if ($adminHasAppliedLocationFilter) {
							if ($this->setting('location_dataset_filter_level_1') == $fieldDetails['id']) {
								$this->data['list_title'] = $customField['label'];
								
								if ($fieldDetails['note_below']) {
									$this->data['note_below'] = $fieldDetails['note_below'];
								}
							}
						} else {
							$this->data['Selected_filters'][] = [
								'label' => $customField['label'],
								'id' =>$fieldDetails['id'],
								'onclickTarget' => 'level_1_filter_' . $fieldDetails['id']
							];
						}
					}
					
					if (!empty($this->setting('location_display')) && $this->setting('location_display') == "apply_a_filter" && !empty($this->setting('location_dataset_filter_level_1'))) {
						$level1Filters[$fieldDetails['id']]['hidden'] = true;
						
						if ($this->setting('location_dataset_filter_level_1') == $fieldDetails['id']) {
							$level1Filters[$fieldDetails['id']]['readonly'] = true;
						}
					}
					
				} elseif (!empty($fieldDetails['parent_id']) && $fieldDetails['admin_box_visibility'] == 'show_on_condition') {
					$parentDbColumn = $this->datasetCustomFields[$fieldDetails['parent_id']]['db_column'];
					
					//Level 2 filters are grouped by their level 1 parent.
					$level2Filters[$fieldDetails['parent_id']][$fieldDetails['id']] = $fieldDataArray;
					
					//For level 2 filters, first draw a checkbox to enable the filter. Then draw the filter input (text, select list, radios, checkboxes...)
					if ($fieldDetails['type'] == 'checkboxes') {
						foreach (ze\dataset::fieldLOV($fieldDetails, $flat = false) as $fieldKey => $fieldValue) {
								
							$level2Filters[$fieldDetails['parent_id']][$fieldDetails['id']]['values'][$fieldKey] = [
								'label' => $fieldValue['label'],
								'type' => 'checkbox'
							];
						
							if (ze::request('level_2_filter_' . $fieldDetails['parent_id'] . '_' . $fieldDetails['id'] . '_' . $fieldKey)
								|| (!empty($this->setting('location_dataset_filter_level_2')) && $this->setting('location_dataset_filter_level_2') == $fieldDetails['id'])
							) {
								$level2Filters[$fieldDetails['parent_id']][$fieldDetails['id']]['values'][$fieldKey]['checked'] = true;
								$this->data['Selected_filters'][] = [
									'label' => $customField['label'],
									'id' =>$fieldDetails['id'],
									'onclickTarget' => 'level_2_filter_' . $fieldDetails['parent_id'] . '_' . $fieldDetails['id'] . '_' . $fieldKey
								];
								$this->locationFilters[$fieldDetails['parent_id']][$fieldDetails['id']][$fieldKey] = $fieldValue;
								
								//If a level 2 filter is applied, ignore the level 1 filter from SQL search.
								unset($this->locationFilters[$fieldDetails['parent_id']][$parentDbColumn]);
							}
						}
					} elseif ($fieldDetails['type'] == 'select' || $fieldDetails['type'] == 'radio') {
						
						foreach (ze\dataset::fieldLOV($fieldDetails, $flat = false) as $fieldKey => $fieldValue) {
							$level2Filters[$fieldDetails['parent_id']][$fieldDetails['id']]['values'][$fieldKey] = [
								'label' => $fieldValue['label'],
								'type' => $fieldDetails['type']
							];
							
							if (ze::request('level_2_filter_' . $fieldDetails['parent_id'] . '_' . $fieldDetails['id'] . '_checkbox')
								|| (!empty($this->setting('location_dataset_filter_level_2')) && $this->setting('location_dataset_filter_level_2') == $fieldDetails['id'])
							) {
								$level2Filters[$fieldDetails['parent_id']][$fieldDetails['id']]['checked'] = true;
								$this->data['Selected_filters'][] = [
									'label' => $customField['label'],
									'id' =>$fieldDetails['id'],
									'onclickTarget' => 'level_2_filter_' . $fieldDetails['parent_id'] . '_' . $fieldDetails['id'] . '_checkbox'
								];
								
								$requestValue = 'level_2_filter_' . $fieldDetails['parent_id'] . '_' . $fieldDetails['id'] . '_' . $fieldKey;
								if (ze::request('level_2_filter_' . $fieldDetails['parent_id'] . '_' . $fieldDetails['id'] . '_' . $fieldDetails['type']) == $requestValue
									|| (!empty($this->setting('location_dataset_filter_level_2')) && $this->setting('location_dataset_filter_level_2') == $fieldDetails['id'])
								) {
									$level2Filters[$fieldDetails['parent_id']][$fieldDetails['id']]['values'][$fieldKey]['checked'] = true;
									$this->locationFilters[$fieldDetails['parent_id']][$fieldDetails['db_column']] = ['id' => $fieldDetails['id'], 'value' => $fieldKey];
									
									//If a level 2 filter is applied, ignore the level 1 filter from SQL search.
									unset($this->locationFilters[$fieldDetails['parent_id']][$parentDbColumn]);
								}
								
								//If this is a select list, and no option was selected, default to option 1.
								if ($fieldDetails['type'] == 'select' && !ze::request('level_2_filter_' . $fieldDetails['parent_id'] . '_' . $fieldDetails['id'] . '_' . $fieldDetails['type']) && empty($level2Filters[$fieldDetails['parent_id']][$fieldDetails['id']]['value'])) {
									$level2Filters[$fieldDetails['parent_id']][$fieldDetails['id']]['value'] = $requestValue;
									$level2Filters[$fieldDetails['parent_id']][$fieldDetails['id']]['values'][$fieldKey]['checked'] = true;
									$this->data['Selected_filters'][] = ['label' => $customField['label'], 'id' =>$fieldDetails['id']];
									$this->locationFilters[$fieldDetails['parent_id']][$fieldDetails['db_column']] = ['id' => $fieldDetails['id'], 'value' => $fieldKey];
									
									//If a level 2 filter is applied, ignore the level 1 filter from SQL search.
									unset($this->locationFilters[$fieldDetails['parent_id']][$parentDbColumn]);
								}
							} else {
								unset($_REQUEST['level_2_filter_' . $fieldDetails['parent_id'] . '_' . $fieldDetails['id'] . '_' . $fieldDetails['type']]);
							}
						}
					} elseif ($fieldDetails['type'] == 'text' || $fieldDetails['type'] == 'checkbox') {
						$level2Filters[$fieldDetails['parent_id']][$fieldDetails['id']]['values'][$fieldDetails['id']] = [
							'label' => $fieldDetails['label'],
							'type' => $fieldDetails['type']
						];
					
						if (ze::request('level_2_filter_' . $fieldDetails['parent_id'] . '_' . $fieldDetails['id'] . '_' . $fieldDetails['id'])
							|| (!empty($this->setting('location_dataset_filter_level_2')) && $this->setting('location_dataset_filter_level_2') == $fieldDetails['id'])
						) {
							$level2Filters[$fieldDetails['parent_id']][$fieldDetails['id']]['values'][$fieldDetails['id']]['checked'] = true;
							$this->data['Selected_filters'][] = [
								'label' => $customField['label'],
								'id' =>$fieldDetails['id'],
								'onclickTarget' => 'level_2_filter_' . $fieldDetails['parent_id'] . '_' . $fieldDetails['id'] . '_' . $fieldDetails['id']
							];
							if ($fieldDetails['type'] == 'checkbox') {
								$this->locationFilters[$fieldDetails['parent_id']][$fieldDetails['db_column']] = ['id' => $fieldDetails['id'], 'value' => true];
								
								//If a level 2 filter is applied, ignore the level 1 filter from SQL search.
								unset($this->locationFilters[$fieldDetails['parent_id']][$parentDbColumn]);
							}
							
							if ($value = (ze::request('level_2_filter_' . $fieldDetails['parent_id'] . '_' . $fieldDetails['id'] . '_' . $fieldDetails['type']) ?? false)) {
								$level2Filters[$fieldDetails['parent_id']][$fieldDetails['id']]['values'][$fieldDetails['id']]['value'] = $value;
								$this->locationFilters[$fieldDetails['parent_id']][$fieldDetails['db_column']] = ['id' => $fieldDetails['id'], 'value' => $value];
								
								//If a level 2 filter is applied, ignore the level 1 filter from SQL search.
								unset($this->locationFilters[$fieldDetails['parent_id']][$parentDbColumn]);
							}
						}
					}
				}
			}
			
			$this->data['Level_1_filters'] = $level1Filters;
			$this->data['Level_2_filters'] = $level2Filters;
			
			if ((bool)ze\admin::id()) {
				$this->data['Logged_in_user_is_admin'] = true;
				$this->data['Location_organizer_href_start'] = htmlspecialchars(ze\link::absolute() . 'zenario/admin/organizer.php#zenario__locations/panel//');
				$this->data['Location_organizer_href_middle'] = htmlspecialchars('~.zenario_location_manager__location~tdetails~k{"id":"');
				$this->data['Location_organizer_href_end'] = htmlspecialchars('"}');
			}
		}
		
		$this->loadLocations($this->dataset['id'], $this->locationFilters);
		
		$this->data['openForm'] = $this->openForm();
		$this->data['closeForm'] = $this->closeForm();
		
		return true;
	}

	public function showSlot() {
		$this->callScript(
			'zenario_location_map_and_listing_2',
			'savePluginSettings',
			$this->setting('show_location_list') ?? false,
			$this->setting('show_map') ?? false
		);
		
		if (empty($_REQUEST['display_map'])) {
			//This code outputs the list of locations (left-hand side of the plugin).
			//The location array is processed in the init method (including applying location filters).
			//Areas and polygons are processed below.
			//Both are passed to a JS variable, so the map in the iframe can have access to them.
			
			//Process areas for drawing polygons on the map.
			$allAreas = [];
			$query = ze\row::query(ZENARIO_LOCATION_MANAGER_PREFIX . 'areas', ['id', 'name', 'polygon_points', 'polygon_colour'], []);
			while ($row = ze\sql::fetchAssoc($query)) {
				$coords = [];
				foreach (explode(',', $row['polygon_points']) as $area) {
					$latLng = explode('_', $area);
					$coords[] = [$latLng[0], $latLng[1]];
				}
				
				$allAreas[$row['id']] = ['name' => $row['name'], 'coords' => $coords, 'polygon_colour' => $row['polygon_colour']];
			}
			
			$polygonStrokeOpacity = ze::setting('polygon_stroke_opacity') ?  (ze::setting('polygon_stroke_opacity') / 100) : 0.8;
			$polygonFillOpacity = ze::setting('polygon_fill_opacity') ?  (ze::setting('polygon_fill_opacity') / 100) : 0.35;
			
			$moduleImagesPath = ze\link::absolute() . ze::moduleDir("zenario_location_map_and_listing_2") . 'images';
			
			//Store the variables for the map in the iframe.
			if (!empty($this->setting('show_map'))) {
				$this->callScriptBeforeAJAXReload(
					'zenario_location_map_and_listing_2',
					'saveMapVars',
					$this->pageLoadNum,
					$this->data['locations_map_info'],
					$allowScrolling = false,
					$allAreas,
					$polygonStrokeOpacity,
					$polygonFillOpacity,
					htmlspecialchars($moduleImagesPath)
				);
			}
		
			$this->twigFramework($this->data);
		
		} else {
			if (!empty($this->setting('show_map'))) {
				echo '<div id="'. htmlspecialchars($this->containerId). '_map"></div>'. "\n";
				echo '<script id="google_api" type="text/javascript" src="https://maps.google.com/maps/api/js?key=' . urlencode(ze::setting('google_maps_api_key')) . '"></script>';
			
				$this->callScript(
					'zenario_location_map_and_listing_2',
					'initMap',
					$_GET['pageLoadNum'] ?? 0,
					$this->containerId,
					$this->setting('zoom_control'),
					(int) ($this->setting('zoom_level') ?? 0)
				);
			}
		}
	}
	
	public function showFile() {
		$siteConfig = ze\db::loadSiteConfig();
		$jsChangeTime = filectime (CMS_ROOT. ze::moduleDir('zenario_location_map_and_listing_2') . "/js/areas.js");
		echo '<html>
				<head>
				<script id="google_api" type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=' . urlencode(ze::setting('google_maps_api_key')) .'&libraries=drawing"></script>
				<link href="' , ze\link::absolute() , ze::moduleDir("zenario_location_map_and_listing_2") , '/adminstyles/fab_area_map.css" media="screen" type="text/css" rel="stylesheet">
				<script type="text/javascript" src="' , ze\link::absolute() , ze::moduleDir("zenario_location_map_and_listing_2") , '/js/areas.js?' . $jsChangeTime . '"></script>
				</head>
				<script type="text/javascript">
				
				defaultMapCentreLat = 0;
				defaultMapCentreLng = 0;
				defaultMapZoom = 1;
				';
		
		if (ze::get("ne_lat") && ze::get("ne_lng") && ze::get("sw_lng") && ze::get("sw_lng") && ze::get("zoom")) {
			echo 'ne_lat = ' . ze::get("ne_lat") . ';
				ne_lng = ' . ze::get("ne_lng") . ';
				sw_lat = ' . ze::get("sw_lat") . ';
				sw_lng = ' . ze::get("sw_lng") . ';
				zoom = ' . ze::get("zoom") . ';';
		}
		
		if (ze::get("polygon_colour")) {
			echo '
				polygon_colour = "' . ze::get("polygon_colour") . '";';
		}
		
		echo '		
				</script>
				
				<div class="div_table_row">
					<input type="text" id="address_to_geocode" style="width: 300px" class="input_text" />
					<button onclick="geocodeAddress();return false;" class="submit">Find Address</button>
				</div>
				
				<button id="delete-button" class="submit">Delete shape</button>
				
				<div id="map" style="width:650px; height:554px;"></div>
				<body onload="init(' . ze::get("editmode") . ')">
				</body>
				</html>';
	}

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				$dataset = ze\dataset::details(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations');
				$datasetFields = ze\dataset::fieldsDetails($dataset['id']);

				$fields['first_tab/display_custom_dataset_fields_on_the_frontend']['pick_items']['path'] .= (int)$dataset['id'] . "//";
				$fields['first_tab/display_custom_dataset_fields_on_the_frontend']['pick_items']['info_button_path'] .= (int)$dataset['id'] . "//";
				
				if (!empty($datasetFields)) {
					foreach ($datasetFields as $datasetField) {
						if ($datasetField['tab_name'] == 'filters') {
							$fields['first_tab/exclude_dataset_filters_picker']['values'][$datasetField['id']] = ['label' => $datasetField['label'], 'ord' => $datasetField['ord']];
							if (empty($datasetField['parent_id'])) {
								$fields['first_tab/location_dataset_filter_level_1']['values'][$datasetField['id']] = ['label' => $datasetField['label'], 'ord' => $datasetField['ord']];
							}
						}
					}
				}
				
				if (!ze\datasetAdm::checkColumnExistsInDB(ZENARIO_LOCATION_MANAGER_PREFIX. "locations_custom_data", "special_offers")) {
					$values['first_tab/locations__field__special_offers'] = false;
					$fields['first_tab/locations__field__special_offers']['disabled'] = true;
					$fields['first_tab/locations__field__special_offers']['side_note'] = ze\admin::phrase('To use this option, please create a field with the code name "special_offers" in the "Locations" dataset.');
					
				}
				break;
			
			case 'zenario_location_manager__areas':
				if ($box['key']['id']) {
					$details = ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX . 'areas', ['name', 'ne_lat', 'ne_lng', 'sw_lat', 'sw_lng', 'zoom', 'polygon_points', 'polygon_colour'], $box['key']['id']);
					$values['name'] = $details['name'];
					$values['ne_lat'] = $details['ne_lat'];
					$values['ne_lng'] = $details['ne_lng'];
					$values['sw_lat'] = $details['sw_lat'];
					$values['sw_lng'] = $details['sw_lng'];
					$values['zoom'] = $details['zoom'];
					$values['polygon_points'] = $details['polygon_points'];
					$values['area/polygon_colour'] = $details['polygon_colour'];
					
					$box['title'] = ze\admin::phrase('Editing the area "[[name]]"', $details);
				}
				
				$this->id = $box['key']['id'];
				break;
			
			case 'zenario_location_manager__location':
				if (!empty($box['key']['id'])) {
					$locationId = $box['key']['id'];
					$values['filters/map_icon'] = ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_map_icons', 'icon_name', ['location_id' => (int)$locationId]);
				}
				break;
		}
	}


	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path){
			case 'plugin_settings':
				$hidden = !$values['display/show_images'];
				$this->showHideImageOptions($fields, $values, 'display', $hidden, 'list_view_thumbnail_');
				
				$fields['first_tab/location_dataset_filter_level_2']['values'] = [];
				if (!empty($values['first_tab/location_display']) && $values['first_tab/location_display'] == 'apply_a_filter' && !empty($values['first_tab/location_dataset_filter_level_1'])) {
					$dataset = ze\dataset::details(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations');
					$datasetFields = ze\dataset::fieldsDetails($dataset['id']);
				
					if (!empty($datasetFields)) {
						foreach ($datasetFields as $datasetField) {
							if ($datasetField['tab_name'] == 'filters' && !empty($datasetField['parent_id']) && $datasetField['parent_id'] == $values['first_tab/location_dataset_filter_level_1']) {
								$fields['first_tab/location_dataset_filter_level_2']['values'][$datasetField['id']] = ['label' => $datasetField['label'], 'ord' => $datasetField['ord']];
							}
						}
					}
				}
				break;
				
			case 'zenario_location_manager__areas':
				$mapEdit = 
					"<iframe id=\"google_map_iframe\" name=\"google_map_iframe\" src=\"" 
					. htmlspecialchars($this->showFileLink("&map_center_lat=" . ze\ray::value($values,'map_center_lat') . "&map_center_lng=" . ze\ray::value($values,'map_center_lng') 
						. "&ne_lat=" . ze\ray::value($values,'ne_lat') . "&ne_lng=" . ze\ray::value($values,'ne_lng') 
						. "&sw_lat=" . ze\ray::value($values,'sw_lat') . "&sw_lng=" . ze\ray::value($values,'sw_lng')
						. "&marker_lat=" . ze\ray::value($values,'marker_lat') . "&marker_lng=" . ze\ray::value($values,'marker_lng') 
						. "&zoom=" . ze\ray::value($values,'zoom') . "&polygon_colour=" . str_replace("#", '', ze\ray::value($values, 'polygon_colour'))) 
						. "&editmode=1") 
					. "\" style=\"width: 100%;height: 625px;border: none;\"></iframe>\n";


				$box['tabs']['area']['fields']['map_edit']['snippet']['html'] = $mapEdit;
				break;
			
			case 'zenario_location_manager__location':
				$box['tabs']['filters']['fields']['map_icon']['values'] = [];
				if (!empty($box['tabs']['filters']['fields'])) {
					$ord = 1;
					foreach ($box['tabs']['filters']['fields'] as $key => &$field) {
						if (!empty($field['db_column']) && $field['type'] == 'checkbox' && $values['filters/' . $key] && empty($field['hidden'])) {
							
							$option = [
								'ord' => (int)$ord,
								'label' => $field['label']
							];
							
							if (isset($field['visible_if'])) {
								$option['visible_if'] = $field['visible_if'];
							}
							
							$box['tabs']['filters']['fields']['map_icon']['values'][$field['db_column']] = $option;
						}
						
						$field['format_onchange'] = true;
					}
				}
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'zenario_location_manager__location':
				if (!empty($box['tabs']['filters']['fields']['map_icon']['values']) && empty($values['filters/map_icon'])) {
					$fields['filters/map_icon']['error'] = ze\admin::phrase('Please select an icon.');
				}
				break;
		
			case 'plugin_settings':
				if (empty($values['first_tab/show_location_list']) && empty($values['first_tab/show_map'])) {
					$fields['first_tab/show_location_list']['error'] = true;
					$fields['first_tab/show_map']['error'] = ze\admin::phrase('Please select at least one front-end feature.');
				}
				
				if (!empty($values['first_tab/location_display'])
					&& $values['first_tab/location_display'] == 'apply_a_filter'
					&& !empty($values['first_tab/location_dataset_filter_level_1'])
					&& !empty($values['first_tab/exclude_dataset_filters'])
					&& strstr($values['first_tab/exclude_dataset_filters_picker'], $values['first_tab/location_dataset_filter_level_1'])
				) {
					$fields['first_tab/exclude_dataset_filters_picker']['error'] = true;
					$fields['first_tab/location_dataset_filter_level_1']['error'] = ze\admin::phrase('Selected location filter cannot be excluded.');
				}
				
				if (!empty($values['first_tab/location_display'])
					&& $values['first_tab/location_display'] == 'apply_a_filter'
					&& !empty($values['first_tab/location_dataset_filter_level_1'])
					&& !empty($values['first_tab/location_dataset_filter_level_2'])
					&& !empty($values['first_tab/exclude_dataset_filters'])
					&& strstr($values['first_tab/exclude_dataset_filters_picker'], $values['first_tab/location_dataset_filter_level_2'])
				) {
					$fields['first_tab/exclude_dataset_filters_picker']['error'] = true;
					$fields['first_tab/location_dataset_filter_level_2']['error'] = ze\admin::phrase('Selected location filter cannot be excluded.');
				}

				if ($values['display/zoom_control'] == 'set_manually'){
					if ($values['display/zoom_level'] > 25) {
						$fields['display/zoom_level']['error'] = ze\admin::phrase('Zoom level must not be higher than 25.');
					} elseif ($values['display/zoom_level'] <1) {
						$fields['display/zoom_level']['error'] = ze\admin::phrase('Zoom level must not be lower than 1.');
					}
				}
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case "zenario_location_manager__areas":
				$box['key']['id'] = ze\row::set(
					ZENARIO_LOCATION_MANAGER_PREFIX . 'areas',
					[
						'name' => $values['area/name'], 
						'ne_lat' => $values['area/ne_lat'],
						'ne_lng' => $values['area/ne_lng'],
						'sw_lat' => $values['area/sw_lat'],
						'sw_lng' => $values['area/sw_lng'],
						'zoom' => $values['area/zoom'],
						'polygon_points' => ($values['area/polygon_points'] ?? null),
						'polygon_colour' => $values['area/polygon_colour']
					],
					$box['key']['id']);
				
				break;
			
			case 'zenario_location_manager__location':
				if (!empty($box['key']['id'])) {
					$locationId = $box['key']['id'];
					
					if (!empty($values['filters/map_icon'])) {
						ze\row::set(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_map_icons', ['location_id' => (int)$locationId, 'icon_name' => ze\escape::sql($values['filters/map_icon'])], ['location_id' => (int)$locationId]);
					} else {
						ze\row::delete(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_map_icons', ['location_id' => (int)$locationId]);
					}
				}
				break;
		}
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__locations/panel':
				//Table join logic to prevent SQL errors when viewing uncategorised locations.
				$panel['db_items']['table'] .= "
					LEFT JOIN [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_map_icons AS lmi
                    ON  lmi.location_id = l.id";
				
				if ($refinerName == "uncategorised_locations") {
					$panel['title'] = "Uncategorised locations";
				}
				break;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $filterName, $filterId) {
		if (ze::post("action") == "delete_area") {
			
			$sql = "
				DELETE FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "areas
				WHERE id IN (" . ze\escape::in($ids) . ")";
					
			$result = ze\sql::update($sql);
		}
	}
	
	protected function loadLocations($datasetId, $locationFilters = []) {
		$this->data['locations_map_info'] = $this->data['locations'] = [];
		
		if (empty($datasetId)) {
			$this->dataset = ze\dataset::details(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations');
			$datasetId = $this->dataset['id'];
		}
		
		if (!empty($this->datasetCustomFields)) {
			$customFields = [];
			foreach ($this->datasetCustomFields as $datasetCustomFieldKey => $datasetCustomField) {
				$customFields[$datasetCustomFieldKey] = ['db_column' => $datasetCustomField['db_column'], 'label' => $datasetCustomField['label'], 'type' => $datasetCustomField['type']];
			}
		}
		
		$sql = "
			SELECT
				cd.location_id,";
		
		if (ze\datasetAdm::checkColumnExistsInDB(ZENARIO_LOCATION_MANAGER_PREFIX. "locations_custom_data", "special_offers")) {
			$sql .= "		
				cd.special_offers,";
		}
				
		$sql .= "		
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
				vp_cn.local_text AS country,
				fv.field_id,
				fv.id,
				lmi.icon_name,
				1 AS tab0";
		
		if (!empty($this->datasetFields)) {
			$sql .= ",
				cd.*";
		}
		
		$sql .= "
			FROM ". DB_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "locations AS loc
			LEFT JOIN ". DB_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "locations_custom_data AS cd
				ON cd.location_id = loc.id
			LEFT JOIN ". DB_PREFIX. "visitor_phrases AS vp_cn
				   ON loc.country_id IS NOT NULL
				  AND module_class_name = 'zenario_country_manager'
				  AND CONCAT('_COUNTRY_NAME_', loc.country_id) = vp_cn.code 
				  AND vp_cn.language_id = '". ze\escape::sql(ze::$visLang). "'
			LEFT JOIN " . DB_PREFIX . "custom_dataset_values_link vl
				ON vl.linking_id = loc.id
			LEFT JOIN " . DB_PREFIX . "custom_dataset_field_values fv
				ON fv.id = vl.value_id
			LEFT JOIN " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "location_map_icons lmi
				ON lmi.location_id = loc.id
			WHERE loc.status = 'active'
			AND (loc.latitude IS NOT NULL AND loc.longitude IS NOT NULL)
			AND (loc.latitude <> 0 AND loc.longitude <> 0)";
			
		if (!empty($locationFilters) && !empty($this->datasetFields)) {
			$string = "
				AND (";
				
			$filtersSql = "";
			
			//Prevent a DB error on first iteration.
			$operator = "";
			
			foreach ($locationFilters as $level1Key => $level1Filter) {
				foreach ($level1Filter as $level2Key => $level2Filter) {
					if (is_numeric($level2Key)) {
						foreach ($level2Filter as $key => $value) {
							$filtersSql .= "
								" . ze\escape::sql($operator) . "
								(
									(	SELECT field_id FROM " . DB_PREFIX . "custom_dataset_values_link vl
										INNER JOIN " . DB_PREFIX . "custom_dataset_field_values fv
											ON fv.id = vl.value_id
										WHERE vl.dataset_id = " . (int)$datasetId . "
										AND fv.field_id = " . (int)$level2Key . "
										AND fv.id = " . (int)$key . "
									)
								)";
								$operator = "OR";
						}
					} else {
						if (is_array($level2Filter)) {
							$fieldValue = $level2Filter['value'];
						} else {
							$fieldValue = $level2Filter;
						}
						
						$filtersSql .= "
							" . ze\escape::sql($operator) . " (cd." . ze\escape::sql($level2Key) . " = " . ze\escape::sql($fieldValue) . ")";
							$operator = "OR";
					}
				}
			}
			
			$string .= "
				" . $filtersSql . ")";
		}
		
		if (!empty($filtersSql)) {
			$sql .= $string;
		}
		
		$sql .= "
			ORDER BY name";
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			
			$row['css_class'] = 'zenario_lmal_marker';
			$row['htmlId'] = $this->containerId. '_loc_'. $row['location_id'];
			
			$imageId = ze\row::get(ZENARIO_LOCATION_MANAGER_PREFIX. 'location_images', 'image_id', ['sticky_flag' => '1', 'location_id' => $row['location_id']]);
			
			$row['alt_tag'] = ze\row::get('files', 'alt_tag', $imageId);
			$row['list_image'] = self::getStickyImageLink($imageId);
			
			$row['descriptive_page'] = false;
			if($row['equiv_id'] && $row['content_type']){
				$cID = $row['equiv_id'];
				$cType = $row['content_type'];
				ze\content::langEquivalentItem($cID, $cType);
			
				if(ze\priv::check() || ze\content::isPublished($cID, $cType)){
					$row['descriptive_page'] = ze\link::toItem($cID, $cType, false);
				}
			}
			
			if (!empty($customFields)) {
				$locationFiltersListLevel1 = $locationFiltersListLevel2 = [];
				foreach ($customFields as $customFieldKey => $customField) {
					if (!empty($row[$customField['db_column']]) && ($customField['type'] == 'checkbox' || $customField['type'] == 'checkboxes')) {
						if (in_array($customFieldKey, array_keys($this->data['Level_1_filters']))) {
							$locationFiltersListLevel1[$customField['db_column']] = ['label' => $customField['label'], 'type' => $customField['type']];
						} else {
							$locationFiltersListLevel2[$customField['db_column']] = ['label' => $customField['label'], 'type' => $customField['type']];
						}
					}
				}
				
				if (!empty($locationFiltersListLevel2)) {
					$row['filters_list'] = $locationFiltersListLevel2;
				} else {
					$row['filters_list'] = $locationFiltersListLevel1;
				}
			}
			
			$this->data['locations'][] = $row;
		}
		
		$this->data['button_label'] =  $this->phrase('View descriptive page');
		
		if (!empty($this->data['locations'])) {
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
					'id' => $row['location_id'],
					'htmlId' => $row['htmlId'],
					'latitude' => $row['latitude'],
					'longitude' => $row['longitude'],
					'map_zoom' => $row['map_zoom'],
					'hide_pin' => $row['hide_pin'],
					'name' => $row['name'],
					'css_class' => $row['css_class'],
					'icon_name' => $row['icon_name']
				];
			}
		}
	}
	
	public function getStickyImageLink($imageId) {
		if ($imageId && $this->setting('show_images')) {
			$url = $width = $height = false;
			
			$widthImage = $this->setting('list_view_thumbnail_width'); 
			$heightImage = $this->setting('list_view_thumbnail_height');
			$canvas = $this->setting('list_view_thumbnail_canvas'); 
			$offset = $this->setting('list_view_thumbnail_offset');
			
			ze\file::imageLink($width, $height, $url, $imageId, $widthImage, $heightImage, $canvas, $offset);
			return $url;
		}
		return false;
	}
}