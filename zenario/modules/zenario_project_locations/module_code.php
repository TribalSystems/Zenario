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

class zenario_project_locations extends ze\moduleBaseClass {

	protected $page = 1;
	protected $rowCount = 0;
	protected $rowsPerPage = 10;
	protected $pageCount = 0;
	protected $sections = [];
	protected $mergeFields = [];
	protected $country_id = '';
	protected $region_id = 0;
	protected $sector_id = 0;
	protected $service_id = 0;
	
	public function addToPageHead(){
		$myfolder = ze::moduleDir('zenario_project_locations');

		echo '<script src="https://maps.googleapis.com/maps/api/js?v=3.11&sensor=false" type="text/javascript"></script>
				<script src="' . $myfolder . 'js/markerclusterer.js" type="text/javascript"></script>';
	}

	public function init(){

		$this->country_id = $_REQUEST['country_id'] ?? false;
		/*if($this->country_id)*/ $this->sections['HasRegions'] = true;
		$this->region_id = (int)ze::request('region_id');
		if($this->region_id) $this->sections['HasSubRegions'] = true;
		$this->service_id = (int)ze::request('service_id');
		$this->sector_id = (int)ze::request('sector_id');
		$this->rowsPerPage = $this->setting('search_results_per_page');
		return true;
		   
	}
	
		
	public function getCountryOptions(){
		$sql = "SELECT l.country_id as id, IFNULL(vs.local_text, CONCAT('_COUNTRY_NAME_', cmc.id)) as name
				FROM " . DB_PREFIX . ZENARIO_PROJECT_LOCATIONS_PREFIX . 'project_locations'  
					. ' AS l 
						INNER JOIN ' . DB_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries
								AS cmc ON l.country_id = cmc.id
								LEFT JOIN ' . DB_PREFIX . "visitor_phrases AS vs
										ON CONCAT('_COUNTRY_NAME_',cmc.id) = vs.code
										AND vs.language_id = '" . ze\escape::asciiInSQL(ze::$visLang) . "'
												ORDER BY 2";

		$result = ze\sql::select($sql);			
		$options = [];
		$options[0] =  $this->phrase('All Countries');
			
		while($row = ze\sql::fetchAssoc($result)){
			$options[$row['id']] = htmlspecialchars($row['name']);
		}
		return $options;
	}

	public function getRegionOptions(){
	
		$options = [];
		$options[0] = $this->phrase('All Regions');		
		if($this->country_id){	

			$sql = "SELECT
						R.id, IFNULL(vs.local_text, R.name) as name
					FROM 
						". DB_PREFIX . ZENARIO_PROJECT_LOCATIONS_PREFIX . "project_locations AS ids
					INNER JOIN 
						". DB_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions R
					ON 
						ids.region_id = R.id and ids.country_id='" . ze\escape::asciiInSQL($this->country_id) . "'
					LEFT JOIN 
						". DB_PREFIX . "visitor_phrases AS vs
					ON 
						R.name = vs.code AND vs.language_id = '" . ze\escape::asciiInSQL(ze::$visLang) . "'
					ORDER BY 2";
		
			$result = ze\sql::select($sql);
				
			while($row = ze\sql::fetchAssoc($result)){
				$options[$row['id']] = htmlspecialchars($row['name']);
			}
		}
		return $options;
	}
	
		
		
	public function getServicesOptions(){

		$sql = 'SELECT s.id, IFNULL(vp.local_text, s.name) as name 
				FROM ' . DB_PREFIX . ZENARIO_PROJECT_LOCATIONS_PREFIX.'project_location_services'
				. ' AS s LEFT JOIN ' . DB_PREFIX . "visitor_phrases 
				AS vp ON CONCAT('_PROJECT_PORTFOLIO_SERVICE_', s.id) = vp.code
				AND vp.language_id = '" . ze\escape::asciiInSQL(ze::$visLang) . "' ORDER BY 2";
			
		$result = ze\sql::select($sql);
			
		$options = [];
		$options[0] = $this->phrase('All Services');
			
		while($row = ze\sql::fetchAssoc($result)){
			$options[$row['id']] = htmlspecialchars($row['name']);
		}
		return $options;
	}
	
	public function getSectorsOptions(){

		$sql = 'SELECT s.id, IFNULL(vp.local_text, s.name) as name 
				FROM ' . DB_PREFIX . ZENARIO_PROJECT_LOCATIONS_PREFIX.'project_location_sectors'
				. ' AS s LEFT JOIN ' . DB_PREFIX . "visitor_phrases 
				AS vp ON CONCAT('_PROJECT_PORTFOLIO_SECTOR_', s.id) = vp.code 
				AND vp.language_id = '" . ze\escape::asciiInSQL(ze::$visLang) . "' ORDER BY 2";
			
		$result = ze\sql::select($sql);
			
		$options = [];
		$options[0] = $this->phrase('All Sectors');
			
		while($row = ze\sql::fetchAssoc($result)){
			$options[$row['id']] = htmlspecialchars($row['name']);
		}
		return $options;
	}
	
	protected function checkRowsExist() {
        $sql = "SELECT COUNT(*) ". $this->selectFrom();
		$result = ze\sql::select($sql);
		$row = ze\sql::fetchRow($result);
		return $this->rowCount = $row[0];
	}
	
	
	protected function selectColumns() {
	
		$sql = "
			SELECT
				pl.*,
				v.id AS content_id, v.type AS content_type,
				c.alias, v.title, v.tag_id,
				v.description, v.content_summary, v.feature_image_id,
                                pl.content_type as content_item,
                                pl.image_id as image_id";
		
		return $sql;
	}
	
	protected function selectFrom($for_map='false') {
		$sql = "
			FROM ". DB_PREFIX. ZENARIO_PROJECT_LOCATIONS_PREFIX. "project_locations AS pl
			LEFT JOIN ". DB_PREFIX. "content_items AS c
			ON c.equiv_id = pl.equiv_id
			AND c.type = 'projects'
			AND c.language_id = '". ze\escape::asciiInSQL(ze::$langId). "'
			LEFT JOIN ". DB_PREFIX. "content_item_versions AS v
			ON v.id = c.id
			AND v.type = c.type
			AND v.version = c.". (ze\priv::check()? "admin_version" : "visitor_version");
		
		
		if ($this->sector_id) {
			$sql .= "
				INNER JOIN ". DB_PREFIX. ZENARIO_PROJECT_LOCATIONS_PREFIX. "project_location_sector_link AS plsecl
				ON plsecl.project_location_id = pl.id
				AND plsecl.sector_id = ". (int) $this->sector_id;
		}
		if ($this->service_id) {
			$sql .= "
				INNER JOIN ". DB_PREFIX. ZENARIO_PROJECT_LOCATIONS_PREFIX. "project_location_service_link AS plserl
				ON plserl.project_location_id = pl.id
				AND plserl.service_id = ". (int) $this->service_id;
		}
		
		$sql .= "
			WHERE 1=1";
			
		if ($for_map=='true') 
		$sql .=" AND pl.latitude IS NOT NULL AND pl.longitude IS NOT NULL ";
		
		if ($this->country_id) {
			$sql .= "
			  AND pl.country_id = '". ze\escape::asciiInSQL($this->country_id). "'";
			
			if ($this->region_id) {
				$sql .= "
				  AND pl.region_id = ". (int) $this->region_id;
			}
		}
		
		return $sql;
	}
	
	protected function selectOrder($with_pagination=true) {
		$sql = " ORDER BY pl.name ";
		if($with_pagination) {
			$sql .= ze\sql::limit($this->page, $this->rowsPerPage);
		}
		return $sql;
	}
	
	protected function setPagination($url_params) {
		$pageSize = $this->rowsPerPage;
	
                if (ze::request('onlyList')) {
                    $this->page = (int) ($_GET['page'] ?? 1) ?: 1;
                } else {
                    $this->page = 1;
                }
		$this->pageCount = ceil($this->rowCount / $pageSize);
	
		$this->registerGetRequest('page', 1);
	
		if ($this->page > $this->pageCount) {
			$this->page = 1;
		}
	
		if ($this->pageCount > 1) {
			$pages = [];
				
			for ($p = 1; $p <= $this->pageCount; ++$p) {
				$pages[$p] = '&page='. $p . $url_params;
			}
	
			$pagination = ''; 
			$this->pagination(
					'zenario_common_features::pagAllWithNPIfNeeded',
					$this->page, $pages, $pagination);
                        
                        $pagination = str_replace('zenario_project_locations.refreshPluginSlot', 
                                   'zenario_project_locations.refreshListSection', $pagination);
                        
                        $this->mergeFields['Pagination'] = $pagination;
		}
	}
		
	protected function fetchRows($with_pagination=true,$for_map='false') {
		$sql = 	$this->selectColumns() . $this->selectFrom($for_map) . $this->selectOrder($with_pagination);

		$result = ze\sql::select($sql);
		$this->sections['HasResults'] = true;
		$location_points = [];
		$locations_unique = [];
		$rows = [];
		
		while($row = ze\sql::fetchAssoc($result)){

			if ($project_link = $row['content_item']) {

				$project_link_start = '<a href="index.php?cID='. $project_link .'_'. $row['equiv_id'].'" target="_blank">';
				$project_link_end = '</a>';
			} else {
				$project_link_start = '';
				$project_link_end = '';
			}
			
			$url = '';
			$width = (int)$this->setting('image_width');
			$height = (int)$this->setting('image_height');
			$img_tag = '';
			if($row['alt_tag']) $alt_tag=" alt='" . $row['alt_tag'] . "'"; else $alt_tag=" alt='" . $row['client_name'] . "'";
			
			ze\file::imageLink($width, $height, $url, $row['image_id'], $width, $height);
			if ($url) {
				$img_tag =  '<img src="' . $url . '" ' . $alt_tag . ' />';
			}
			
			$result_container_id = $this->containerId . '_result_'. $row['tag_id'];
			$title = htmlspecialchars($row['title']);	
			$rows[] = [
				'html_id' => $result_container_id,
				'client_name' => htmlspecialchars($row['client_name']),
				'architect_name' => htmlspecialchars($row['architect_name']),
				'contractor_name' => htmlspecialchars($row['contractor_name']),
				'content_summary' => $row['summary'],
				'location' => $project_link_start.htmlspecialchars($row['name']).$project_link_end,
				'title' => $title,
				'Sticky_image_HTML_tag' => $img_tag
			];

//				if(!$row['latitude']) $row['latitude']='0'; if(!$row['logitude']) $row['logitude']='0';				
				$location_points[] = [
							floatval($row['latitude']), 
							floatval($row['longitude']),
							$title,
							$result_container_id
				];
			}

		return [$rows, $location_points];
	}
	
	protected function fetchResults() {
	
		$all_rows = $this->fetchRows(false);
		$rows_with_pagination = $this->fetchRows(true);

		$this->sections['Results'] = &$rows_with_pagination[0];
	
		$map_cluster_grid_size = (int)$this->setting('map_cluster_grid_size');
		if($map_cluster_grid_size == 0) $map_cluster_grid_size = 10;

		$all_rows2 = $this->fetchRows(false,true);
//print_r($all_rows2);
		$map_cluster_zoom_click_info = (int)$this->setting('map_cluster_zoom_click_info');		
		$this->callScript('zenario_project_locations', 'initMap', 'map_canvas',
				$map_cluster_grid_size, $map_cluster_zoom_click_info, $all_rows2[1],
                                $all_rows2[0]);

	}
	
	public function showSlot() {
		
		//if($_REQUEST["doSearch"] ?? false){
			if ($this->checkRowsExist()) {
                            $this->sections['HasResults'] = true;

                            $this->setPagination(
                                            '&country_id=' . urlencode($this->country_id)
                                            .'&region_id=' . urlencode($this->region_id)
                                            .'&sector_id=' . urlencode($this->sector_id)
                                            .'&service_id=' . urlencode($this->service_id)
                                            . '&doSearch=1&onlyList=1');
                            $this->fetchResults();
					
			} else {
                            $this->sections['No_Rows'] = true;
			}

		$this->mergeFields['Open_Form'] = $this->openForm('return true;', '', false, false, true, $usePost = true);
		$this->mergeFields['Close_Form'] = $this->closeForm();
		$this->framework('Outer', $this->mergeFields, $this->sections);
	}
	
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__projects/nav/images/panel':
				
				foreach ($panel['items'] as $id => &$item) {
					
					$item['image'] = 'zenario/file.php?c='. $item['checksum']. '&usage=project_locations&og=1';
				}
			case 'zenario__projects/nav/projects/panel':
				foreach ($panel['items'] as $id => &$item) {
					if ($item['checksum']) {
						$img = '&usage=project_locations&c='. $item['checksum'];
					
						$item['image'] = 'zenario/file.php?og=1'. $img;
					}
				}
			break;
		}
	}	
	
	
	public static function getProjectLocationDetails($id) {
		return ze\row::get(ZENARIO_PROJECT_LOCATIONS_PREFIX. 'project_locations', true, $id);
	}
	
	
	/* Start of CMS placeholder functions */
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$projectDetails = [];
		switch ($path) {
			case "zenario_project_locations__project":
				
				$fields['sectors']['hidden'] = !ze\row::exists(ZENARIO_PROJECT_LOCATIONS_PREFIX. 'project_location_sectors', []);
				$fields['services']['hidden'] = !ze\row::exists(ZENARIO_PROJECT_LOCATIONS_PREFIX. 'project_location_services', []);
				
			
				if (ze::setting("zenario_project_locations__name")) {
					//$fields['name']['validation']=[];
					$fields['name']['validation']['required_if_not_hidden']='Please type in a project name';
				}			
				if (ze::setting("zenario_project_locations__content_item")) {
					//$fields['name']['validation']=[];
					$fields['content_item']['validation']['required_if_not_hidden']='Please choose content item';
				}			
				if (ze::setting("zenario_project_locations__summary")) {
					//$fields['name']['validation']=[];
					$fields['summary']['validation']['required_if_not_hidden']='Please type in a project summary';
				}			
				if (ze::setting("zenario_project_locations__client")) {
					//$fields['name']['validation']=[];
					$fields['client_name']['validation']['required_if_not_hidden']='Please type in a client name';
				}			
				if (ze::setting("zenario_project_locations__architect")) {
					//$fields['name']['validation']=[];
					$fields['architect_name']['validation']['required_if_not_hidden']='Please type in a architect name';
				}			
				if (ze::setting("zenario_project_locations__contractor")) {
					//$fields['name']['validation']=[];
					$fields['contractor_name']['validation']['required_if_not_hidden']='Please type in a project contractor name';
				}			
				if (ze::setting("zenario_project_locations__address1")) {
					//$fields['name']['validation']=[];
					$fields['address1']['validation']['required_if_not_hidden']='Please type in an address';
				}			
				if (ze::setting("zenario_project_locations__locality")) {
					//$fields['name']['validation']=[];
					$fields['locality']['validation']['required_if_not_hidden']='Please type in a locality';
				}			
				if (ze::setting("zenario_project_locations__city")) {
					//$fields['name']['validation']=[];
					$fields['city']['validation']['required_if_not_hidden']='Please type in a city/town';
				}			
				if (ze::setting("zenario_project_locations__state")) {
					//$fields['name']['validation']=[];
					$fields['state']['validation']['required_if_not_hidden']='Please type in a state';
				}			
				if (ze::setting("zenario_project_locations__postcode")) {
					//$fields['name']['validation']=[];
					$fields['postcode']['validation']['required_if_not_hidden']='Please type in a post code';
				}			
				if (ze::setting("zenario_project_locations__country")) {
					//$fields['name']['validation']=[];
					$fields['country']['validation']['required_if_not_hidden']='Please choose a country';
				}			
				if (ze::setting("zenario_project_locations__region")) {
					//$fields['name']['validation']=[];
					$fields['region']['validation']['required_if_not_hidden']='Please choose a region';
				}			
				if (ze::setting("zenario_project_locations__sectors")) {
					//$fields['name']['validation']=[];
					$fields['sectors']['validation']['required_if_not_hidden']='Please choose a sector';
				}			
				if (ze::setting("zenario_project_locations__services")) {
					//$fields['name']['validation']=[];
					$fields['services']['validation']['required_if_not_hidden']='Please choose a service';
				}
				
				$fields['content_item']['pick_items']['path'] =
					'zenario__content/panels/content/refiners/content_type//project//';

				$locationCountriesFinal = zenario_country_manager::getCountryAdminNamesIndexedByISOCode("active");
				foreach ($locationCountriesFinal as $key => $value) {
					$box['tabs']['details']['fields']['country']['values'][$key] = $value;
				}

				if ($box['key']['id']) {
					$projectDetails = zenario_project_locations::getProjectLocationDetails($box['key']['id']);
				  
					$box['title'] = ze\admin::phrase('Editing the Project "[[name]]"',['name' => $projectDetails['name']]);
					
					$fields['name']['value'] = $projectDetails['name'];
					$fields['summary']['value'] = $projectDetails['summary'];
					$fields['client_name']['value'] = $projectDetails['client_name'];
					$fields['architect_name']['value'] = $projectDetails['architect_name'];
					$fields['contractor_name']['value'] = $projectDetails['contractor_name']; 
					$fields['address1']['value'] = $projectDetails['address1'];
					$fields['address2']['value'] = $projectDetails['address2'];
					$fields['locality']['value'] = $projectDetails['locality'];
					$fields['city']['value'] = $projectDetails['city'];
					$fields['state']['value'] = $projectDetails['state'];
					$fields['postcode']['value'] = $projectDetails['postcode'];
					$fields['country']['value'] = $projectDetails['country_id'];

					$fields['region']['values'] = [];
					$regionsByCountry = zenario_country_manager::getRegions($countryActivityFilter='all',$countryCodeFilter=$projectDetails['country_id'],$regionCodeFilter='',$regionIdFilter=false,$parentRegionFilter=0,$regionNameFilter='',$excludeIdsCSV='');
					foreach ($regionsByCountry as $key => $value) {
						$fields['region']['values'][$key] = $value;
					}
					$fields['region']['value'] = $projectDetails['region_id'];
					$fields['image_id']['value'] = $projectDetails['image_id'];
					$fields['alt_tag']['value'] = $projectDetails['alt_tag'];

					$fields['sectors']['value'] = implode(',', ze\row::getValues(ZENARIO_PROJECT_LOCATIONS_PREFIX. 'project_location_sector_link', 'sector_id', ['project_location_id' => $box['key']['id']]));
					$fields['services']['value'] = implode(',', ze\row::getValues(ZENARIO_PROJECT_LOCATIONS_PREFIX. 'project_location_service_link', 'service_id', ['project_location_id' => $box['key']['id']]));

//					$fields['url']['value'] = $projectDetails['url'];


					$fields['map_center_lat']['value'] = $projectDetails['map_center_latitude'];
					$fields['map_center_lng']['value'] = $projectDetails['map_center_longitude'];
					$fields['marker_lat']['value'] = $projectDetails['latitude'];
					$fields['marker_lng']['value'] = $projectDetails['longitude'];
					$fields['zoom']['value'] = $projectDetails['map_zoom'];
		   
				
					if ($projectDetails['content_type'] && $projectDetails['equiv_id']) {
						$fields['content_item']['value'] = $projectDetails['content_type'] . "_" . $projectDetails['equiv_id'];
					}
 
					$fields['last_updated']['value'] = $projectDetails['last_updated'] ?? false;
					$fields['last_updated']['hidden'] = false;

					$lastUpdatedByAdmin = ze\row::get("admins",["id","username","authtype"],["id" => ($projectDetails['last_updated_admin_id'] ?? false)]);
					$fields['last_updated_admin_id']['value'] = ($lastUpdatedByAdmin['username'] ?? false) . ((($lastUpdatedByAdmin['authtype'] ?? false)=="super") ? " (super)":"");
					$fields['last_updated_admin_id']['hidden'] = false;
 
				} else {
					$fields['last_updated']['hidden'] = true;
					$fields['last_updated_admin_id']['hidden'] = true;
				}

				if (isset($box['tabs']['sectors']['fields']['sectors'])) {
					self::setupSectorCheckboxes(
						$box['tabs']['sectors']['fields']['sectors'],
						$box['key']['id']);
				}

				if (ze::setting("zenario_location_manager__sector_management")!="0") {
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
				
				$mapEdit =
					"<iframe id=\"google_map_iframe\" name=\"google_map_iframe\" src=\"" .
					htmlspecialchars(
						str_replace($this->moduleClassName, 'zenario_location_manager', 
							$this->showFileLink(
								"&map_center_lat=" . ($projectDetails['map_center_latitude'] ?? false) . "&map_center_lng=" . ($projectDetails['map_center_longitude'] ?? false) . "&marker_lat=" . ($projectDetails['latitude'] ?? false) . "&marker_lng=" . ($projectDetails['longitude'] ?? false) . "&zoom=" . ($projectDetails['map_zoom'] ?? false) . "&editmode=1"
					))).
					"\" style=\"width: 425px;height: 425px;border: none;\"></iframe>\n";
				
				$mapView =
					"<iframe id=\"google_map_iframe\" name=\"google_map_iframe\" src=\"" .
					htmlspecialchars(
						str_replace($this->moduleClassName, 'zenario_location_manager', 
							$this->showFileLink(
								"&map_center_lat=" . ($projectDetails['map_center_latitude'] ?? false) . "&map_center_lng=" . ($projectDetails['map_center_longitude'] ?? false) . "&marker_lat=" . ($projectDetails['latitude'] ?? false) . "&marker_lng=" . ($projectDetails['longitude'] ?? false) . "&zoom=" . ($projectDetails['map_zoom'] ?? false) . "&editmode=0"
					))).
					"\" style=\"width: 425px;height: 425px;border: none;\"></iframe>\n";

				$box['tabs']['details']['fields']['map_lookup']['snippet']['html'] = $map_lookup;				
				$box['tabs']['details']['fields']['map_edit']['snippet']['html'] = $mapEdit;
				$box['tabs']['details']['fields']['map_view']['snippet']['html'] = $mapView;
//print_r($box['tabs']['details']['fields']);die();		
				break;
				
			case 'zenario_project_location_sector':
				if($box['key']['id']){
					$record = ze\row::get(ZENARIO_PROJECT_LOCATIONS_PREFIX.'project_location_sectors', true, $box['key']['id']);
					$values['name'] = $record['name'];
					$box['title'] = ze\admin::phrase('Editing the project sector "[[name]]"', $record);
				} else {
					$box['title'] = ze\admin::phrase('Creating a project sector');
				}
					
				break;

			case 'zenario_project_location_service':
				if($box['key']['id']){
					$record = ze\row::get( ZENARIO_PROJECT_LOCATIONS_PREFIX.'project_location_services', true, $box['key']['id']);
					$values['name'] = $record['name'];
					$box['title'] = ze\admin::phrase('Editing a project service "[[name]]"', $record);
				} else {
					$box['title'] = ze\admin::phrase('Creating a project service');
				}
					
				break;

		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case "zenario_project_locations__project":
				$projectDetails = zenario_project_locations::getProjectLocationDetails($box['key']['id']);

				//If a country isn't selected, hide the region picker
				$regionsByCountry = [];
				if($values['country']) {
					$regionsByCountry = zenario_country_manager::getRegions(
																			$countryActivityFilter='all',
																			$countryCodeFilter=$values['country'],
																			$regionCodeFilter='',
																			$regionIdFilter=false,
																			$parentRegionFilter=0,
																			$regionNameFilter='',
																			$excludeIdsCSV='');
					}
				if (empty($regionsByCountry)) {
					$fields['region']['hidden'] = true;
					$fields['region']['value'] = 0;
				} else {
					$fields['region']['hidden'] = false;
					$fields['region']['values'] = [0 => ["id" => 0, "name" => "--Select a region--"]];
					foreach ($regionsByCountry as $key => $value) {
						$fields['region']['values'][$key] = $value;
					}
			
				}
				if (!$values["region"] || ($values['country'] != $projectDetails['country_id'])) {
					$fields['region']['value'] = 0;
				}
		
				if (isset($values['image_id'])) {
					$this->getImageHtmlSnippet($values['image_id'], $box['tabs']['image']['fields']['image']['snippet']['html']);
				} else {
					$box['tabs']['image']['fields']['image']['snippet']['html'] = '';
				}
		}
	}
	
	//This module uses a deprecated core function.
	//I'm redefining it here, so the module will still work when this function is removed from the core in version 9.2.
	//If someone gets a little bit of time to do some code tidying, this can be replaced by the new "image"
	//option in TUIX, also added in version 9.2.
	protected function getImageHtmlSnippet($image_id, &$snippet_field, $widthLimit = 700, $heightLimit = 200){
		if ($image_id) {
			$width = $height = $url = $widthR = $heightR = $urlR = false;
			\ze\file::imageLink($widthR, $heightR, $urlR, $image_id, $widthLimit, $heightLimit, $mode = 'resize', $offset = 0, $retina = true, $fullPath = false, $privacy = 'auto', $useCacheDir = false);
	
			$snippet_field = '
			<p style="text-align: center;">
				<a>
					<img src="'. htmlspecialchars(\ze\link::absolute(). $urlR). '"
						width="'. $widthR. '" height="'. $heightR. '" style="border: 1px solid black;"/>
				</a>
			</p>';
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case "zenario_project_locations__project":
					$saveValues = [];
					$saveValues['name'] = $values['name'];
					$saveValues['summary'] = $values['summary'];
					$saveValues['client_name'] = $values['client_name'];
					$saveValues['architect_name'] = $values['architect_name'];
					$saveValues['contractor_name'] = $values['contractor_name'];
					$saveValues['address1'] = $values['address1'];
					$saveValues['address2'] = $values['address2'];
					$saveValues['locality'] = $values['locality'];
					$saveValues['city'] = $values['city'];
					$saveValues['state'] = $values['state'];
					$saveValues['postcode'] = $values['postcode'];
					$saveValues['country_id'] = $values['country'];
					$saveValues['region_id'] = $values['region'];
					$saveValues['latitude'] = $values['marker_lat'];
					$saveValues['longitude'] =  $values['marker_lng'];
					$saveValues['map_zoom'] = $values['zoom'];
					$saveValues['map_center_latitude'] = $values['map_center_lat'];
					$saveValues['map_center_longitude'] = $values['map_center_lng'];
					$saveValues['last_updated'] = ze\date::now();
					$saveValues['last_updated_admin_id'] = ze\admin::id();
					$saveValues['image_id'] = $values['image_id'];
					$saveValues['alt_tag'] = $values['alt_tag'];

					if ($values['content_item']) {
						$contentItemArray = explode("_", $values['content_item']);
						if (count($contentItemArray)==2) {
							$contentItemArray[1] = ze\content::equivId($contentItemArray[1], $contentItemArray[0]);
							$saveValues['equiv_id'] = $contentItemArray[1];
							$saveValues['content_type'] = $contentItemArray[0];
						}
					} else {
						$saveValues['equiv_id'] = null;
						$saveValues['content_type'] = null;
					}

					$saveValues['last_updated'] = ze\date::now();
					$saveValues['last_updated_admin_id'] = $_SESSION['admin_userid'] ?? false;

					if ($saveValues['latitude'] =='0.000000000000000000' || $saveValues['latitude'] == '')
					{
						$saveValues['latitude']=NULL;
						$saveValues['map_center_latitude']=NULL;
					}
					if ($saveValues['longitude'] =='0.000000000000000000' || $saveValues['longitude'] =='') 
					{
						$saveValues['longitude']=NULL;
						$saveValues['map_center_longitude']=NULL;
					}

					$box['key']['id'] = ze\row::set(ZENARIO_PROJECT_LOCATIONS_PREFIX . "project_locations", $saveValues, ["id" => $box['key']['id']]); //save location in table

					
					ze\row::delete(ZENARIO_PROJECT_LOCATIONS_PREFIX. 'project_location_sector_link', ['project_location_id' => $box['key']['id']]);
					foreach (explode(',', $values['sectors']) as $sectorId) {
						if ($sectorId) {
							ze\row::insert(ZENARIO_PROJECT_LOCATIONS_PREFIX. 'project_location_sector_link', ['sector_id' => $sectorId, 'project_location_id' => $box['key']['id']]);
						}
					}
					ze\row::delete(ZENARIO_PROJECT_LOCATIONS_PREFIX. 'project_location_service_link', ['project_location_id' => $box['key']['id']]);
					foreach (explode(',', $values['services']) as $serviceId) {
						if ($serviceId) {
							ze\row::insert(ZENARIO_PROJECT_LOCATIONS_PREFIX. 'project_location_service_link', ['service_id' => $serviceId, 'project_location_id' => $box['key']['id']]);
						}
					}
				
				break;
				
				case 'zenario_project_location_sector':
					
					$box['key']['id'] =
						ze\row::set(
							ZENARIO_PROJECT_LOCATIONS_PREFIX.'project_location_sectors',
							[
								'name' => $values['name']],
							$box['key']['id']);
										
				break;

				case 'zenario_project_location_service':
				
					$box['key']['id'] = ze\row::set(ZENARIO_PROJECT_LOCATIONS_PREFIX.'project_location_services',
							[
									'name' => $values['name'],
							], $box['key']['id']);
			
					break;

		}
	}
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		//You should always use an if () or a switch () statement on the path,
		//to ensure that you are running code for the correct Panel.
		switch ($path) {
			case 'zenario__projects/nav/project_services/panel':
				
				//Handle the case where an Admin presses the delete button.
				if (ze::post('action') == 'delete_project_service'
				 && ze\priv::check('_PRIV_MANAGE_PROJECT_LOCATIONS')) {
					foreach (explode(',', $ids) as $id) {
						ze\row::delete(ZENARIO_PROJECT_LOCATIONS_PREFIX.'project_location_services', $id);
					}
				}
				if(count(explode(',', $ids))>1){
					if(isset($_POST['reorder'])){
						// FIRST I STORE ALL THE SORT VALUES FROM THE MOVED ELEMENTS
						$sorts = [];
						foreach (explode(',', $ids) as $id) {
							if (!empty($_POST['ordinals'][$id])) {
								$sorts[] = $_POST['ordinals'][$id];
							}
						}

						$sort = min($sorts);
						foreach (explode(',', $ids) as $id) {
							ze\row::set(ZENARIO_PROJECT_LOCATIONS_PREFIX.'project_location_services',
								[
										'sort' =>$sort 
								], $id);
							$sort++;
						}
					}				
				}
				break;				
			case 'zenario__projects/nav/project_sectors/panel':
				
				//Handle the case where an Admin presses the delete button.
				if (ze::post('action') == 'delete_project_sector'
				 && ze\priv::check('_PRIV_MANAGE_PROJECT_LOCATIONS')) {
					foreach (explode(',', $ids) as $id) {
						ze\row::delete(ZENARIO_PROJECT_LOCATIONS_PREFIX.'project_location_sectors', $id);
					}
				}
				break;				
			case 'zenario__projects/nav/projects/panel':
				
				//Handle the case where an Admin presses the delete button.
				if (ze::post('action') == 'delete_location'
				 && ze\priv::check('_PRIV_MANAGE_PROJECT_LOCATIONS')) {
					foreach (explode(',', $ids) as $id) {
						ze\row::delete(ZENARIO_PROJECT_LOCATIONS_PREFIX.'project_locations', $id);
					}
				}

				// UPDATE IF SORTING
				
				if(count(explode(',', $ids))>1){
					if(isset($_POST['reorder'])){
						// FIRST I STORE ALL THE SORT VALUES FROM THE MOVED ELEMENTS
						$sorts = [];
						foreach (explode(',', $ids) as $id) {
							if (!empty($_POST['ordinals'][$id])) {
								$sorts[] = $_POST['ordinals'][$id];
							}
						}

						$sort = min($sorts);
						foreach (explode(',', $ids) as $id) {
							ze\row::set(ZENARIO_PROJECT_LOCATIONS_PREFIX.'project_locations',
								[
										'sort' =>$sort 
								], $id);
							$sort++;
						}
					}				
				}
				break;
		
		case 'zenario__projects/nav/images/panel':
				
					
				//Upload a new image
				if (ze::post('upload') && ze\priv::check('_PRIV_MANAGE_PROJECT_LOCATIONS')) {
					ze\fileAdm::exitIfUploadError(true, false, true, 'Filedata');
					$image_id = ze\file::addToDatabase('project_locations', $_FILES['Filedata']['tmp_name'], rawurldecode($_FILES['Filedata']['name']), true);
					return $image_id;
				
				//Delete an image
				} elseif (ze::post('delete') && ze\priv::check('_PRIV_MANAGE_PROJECT_LOCATIONS')) {
					foreach (explode(',', $ids) as $id) {
						if (!ze\row::exists(ZENARIO_PROJECT_LOCATIONS_PREFIX. 'project_locations', ['image_id' => $id])) {
							ze\row::delete( 'files', ['id' => $id, 'usage' => 'project_locations']);
						}
					}	
				}
				break;
		
		
		}
	}
}