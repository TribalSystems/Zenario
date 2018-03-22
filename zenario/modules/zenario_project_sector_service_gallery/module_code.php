<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

class zenario_project_sector_service_gallery extends ze\moduleBaseClass {
	
	protected $page = 1;
	protected $rowCount = 0;
	protected $rowsPerPage = 10;
	protected $pageCount = 0;
	protected $sections = [];
	protected $mergeFields = [];
	protected $sector_id = 0;
	protected $service_id = 0;
	protected $thisInstanceId = 0;
	
	
	protected function getTablePrefixed($table){
		return ZENARIO_PROJECT_LOCATIONS_PREFIX. $table;
	}
	
	protected function getInstanceId(){
		$this->instanceId;
	}
	
	public function init(){
		$this->service_id = $this->setting('plugin_service_id');
		$this->sector_id = $this->setting('plugin_sector_id');
		$this->rowsPerPage = $this->setting('results_per_page');
		$this->thisInstanceId = $this->instanceId;
		//echo("instance id = " . $this->instanceId);
		return true;
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				$select_options = ze\row::getArray(ZENARIO_PROJECT_LOCATIONS_PREFIX . 'project_location_services', 'name');
				//var_dump($select_options);
				$box['tabs']['first_tab']['fields']['plugin_service_id']['values'] = $select_options;
					
				$select_options = ze\row::getArray(ZENARIO_PROJECT_LOCATIONS_PREFIX .'project_location_sectors', 'name');
				$box['tabs']['first_tab']['fields']['plugin_sector_id']['values'] = $select_options;
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				$box['tabs']['first_tab']['fields']['sticky_image_width']['hidden'] = 
					$box['tabs']['first_tab']['fields']['canvas']['hidden']
				 || !ze::in($values['first_tab/canvas'], 'fixed_width', 'resize_and_crop', 'resize');

				$box['tabs']['first_tab']['fields']['sticky_image_height']['hidden'] = 
					$box['tabs']['first_tab']['fields']['canvas']['hidden']
				 || !ze::in($values['first_tab/canvas'], 'fixed_height', 'resize_and_crop', 'resize');
		 
				if (isset($box['tabs']['first_tab']['fields']['canvas'])
				 && empty($box['tabs']['first_tab']['fields']['canvas']['hidden'])) {
					if ($values['first_tab/canvas'] == 'fixed_width') {
						$box['tabs']['first_tab']['fields']['sticky_image_width']['note_below'] =
							ze\admin::phrase('Images may be scaled down maintaining aspect ratio. Except for SVG images, they will never be scaled up.');
			
					} else {
						unset($box['tabs']['first_tab']['fields']['sticky_image_width']['note_below']);
					}
			
					if ($values['first_tab/canvas'] == 'fixed_height'
					 || $values['first_tab/canvas'] == 'resize') {
						$box['tabs']['first_tab']['fields']['sticky_image_height']['note_below'] =
							ze\admin::phrase('Images may be scaled down maintaining aspect ratio. Except for SVG images, they will never be scaled up.');
			
					} elseif ($values['first_tab/canvas'] == 'resize_and_crop') {
						$box['tabs']['first_tab']['fields']['sticky_image_height']['note_below'] =
							ze\admin::phrase('Images may be scaled up or down maintaining aspect ratio.');
			
					} else {
						unset($box['tabs']['first_tab']['fields']['sticky_image_height']['note_below']);
					}
				}
				break;
		}
	}
	
		

	public function getServicesOptions(){
		$sql = 'SELECT s.id, IFNULL(vp.local_text, s.name) as name 
				FROM ' . DB_NAME_PREFIX . ZENARIO_PROJECT_LOCATIONS_PREFIX . 'project_location_services'
				. ' AS s LEFT JOIN ' . DB_NAME_PREFIX . "visitor_phrases 
				AS vp ON CONCAT('_PROJECT_plocations_SERVICE_', s.id) = vp.code
				AND vp.language_id = '" . ze\escape::sql(ze::$visLang) . "' ORDER BY 2";
			
		$result = ze\sql::select($sql);
			
		$options = [];
		$options[0] = $this->phrase('_ALL_SERVICES');
			
		while($row = ze\sql::fetchAssoc($result)){
			$options[$row['id']] = htmlspecialchars($row['name']);
		}
		return $options;
	}

	public function getSectorsOptions(){
		$sql = 'SELECT s.id, IFNULL(vp.local_text, s.name) as name 
				FROM ' . DB_NAME_PREFIX . ZENARIO_PROJECT_LOCATIONS_PREFIX . 'project_location_sectors'
				. ' AS s LEFT JOIN ' . DB_NAME_PREFIX . "visitor_phrases 
				AS vp ON CONCAT('_PROJECT_plocations_SECTOR_', s.id) = vp.code 
				AND vp.language_id = '" . ze\escape::sql(ze::$visLang) . "' ORDER BY 2";
			
		$result = ze\sql::select($sql);
			
		$options = [];
		$options[0] = $this->phrase('_ALL_SECTORS');
			
		while($row = ze\sql::fetchAssoc($result)){
			$options[$row['id']] = htmlspecialchars($row['name']);
		}
		return $options;
	}
	


	
	protected function selectColumns() {
		return $sql = 'SELECT pl.*, 
					pl.name as location, pl.latitude, pl.longitude, pl.content_type, pl.equiv_id';
				//. ',sector.admin_name AS sector_name, services.admin_name AS service_name ';
	}
	
	protected function selectFrom() {
			


		$sql = " FROM ". DB_NAME_PREFIX. ZENARIO_PROJECT_LOCATIONS_PREFIX. "project_locations AS pl ";

//echo "$this->sector_id".'sector_id<br><br>';
//echo "$this->service_id".'service_id<br><br>';
		if ($this->sector_id) {
			$sql .= "
				INNER JOIN ". DB_NAME_PREFIX. ZENARIO_PROJECT_LOCATIONS_PREFIX. "project_location_sector_link AS plsecl
				ON plsecl.project_location_id = pl.id
				AND (";
			foreach(explode(',', $this->sector_id) as $plugin_sector_id) {
				if($plugin_sector_id) {
					$sql2 .= 'plsecl.sector_id=' . $plugin_sector_id . ' OR  ';
				}
			}
			$sql2 = substr($sql2, 0, -4);
			$sql .= $sql2.')';
			
		}
		if ($this->service_id) {
			$sql .= "
				INNER JOIN ". DB_NAME_PREFIX. ZENARIO_PROJECT_LOCATIONS_PREFIX. "project_location_service_link AS plserl
				ON plserl.project_location_id = pl.id
				AND (";
			foreach(explode(',', $this->service_id) as $plugin_service_id) {
				if($plugin_service_id) {
					$sql3 .= 'plserl.service_id=' . $plugin_service_id . ' OR  ';
				}
			}
			$sql3 = substr($sql3, 0, -4);
			$sql .= $sql3.')';
		}
	
		$sql .= "WHERE image_id <> 0";		
		return $sql;
	
	}

	protected function checkRowsExist() {
		$sql = "SELECT COUNT(DISTINCT pl.id) ". $this->selectFrom();
		$result = ze\sql::select($sql);
		$row = ze\sql::fetchRow($result);
		return $this->rowCount = $row[0];
	}
	
	protected function setPagination($url_params) {
		$pageSize = $this->rowsPerPage;

				if($_REQUEST['onlyList'] ?? false) {
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
					
						$pagination = str_replace('zenario_project_plocations_search.refreshPluginSlot', 
								   'zenario_project_plocations_search.refreshListSection', $pagination);
					
						$this->mergeFields['Pagination'] = $pagination;
		}
	}
	
	protected function selectOrder($with_pagination=true) {
		$sql = " ORDER BY client_name ";
		if($with_pagination) {
			$sql .= ze\sql::limit($this->page, $this->rowsPerPage);
		}
		return $sql;
	}
		
	protected function fetchRows($with_pagination=true) {
		$sql = $this->selectColumns() . $this->selectFrom() . " GROUP BY pl.id" . $this->selectOrder($with_pagination);

		$result = ze\sql::select($sql);
		$this->sections['HasResults'] = true;
		$location_points = [];
		$locations_unique = [];
		$rows = [];
		while($row = ze\sql::fetchAssoc($result)){


			if ($row['content_type']) {
				$project_link = 'index.php?cID='. $row['content_type'] .'_'. $row['equiv_id'];
				$project_link_start = '<a href="index.php?cID='. $row['content_type'] .'_'. $row['equiv_id'].'" target="_blank">';
				$project_link_end = '</a>';
			} else {
				$project_link_start = '';
				$project_link_end = '';
				$project_link='';
			}


			$url = $width = $height = false;
			if (ze::in($imageCanvas = $this->setting('canvas'), 'resize_and_crop', 'resize')) {
				$width = (int)$this->setting('sticky_image_width');
				$height = (int)$this->setting('sticky_image_height');
			} elseif (ze::in($imageCanvas = $this->setting('canvas'), 'fixed_width')) {
				$width = (int)$this->setting('sticky_image_width');
			} elseif (ze::in($imageCanvas = $this->setting('canvas'), 'fixed_height')) {
				$height = (int)$this->setting('sticky_image_height');
			}
			
			$img_tag = '';
			
			ze\file::imageLink($width, $height, $url, $row['image_id'], $width, $height, $imageCanvas);
			if ($url) {
				$img_tag =  '<img src="' . $url . '" />';
			}
			
			$result_container_id = $this->containerId . '_result_'. $row['tag_id'];
			$title = htmlspecialchars($row['title']);			
			$rows[] = [
				'html_id' => $result_container_id,
				'client_name' => htmlspecialchars($row['client_name']),
				'architect_name' => htmlspecialchars($row['architect_name']),
				'contractor_name' => htmlspecialchars($row['contractor_name']),
				'content_summary' => $row['content_summary'],
				'location' => htmlspecialchars($row['location']),
				'project_link_start' => $project_link_start,
				'project_link' => $project_link,
				'project_link_end' => $project_link_end,
				'title' => $title,
				'Sticky_image_HTML_tag' => $img_tag
			];
				
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

	}
	
	public function showSlot() {
		if ($this->checkRowsExist()) {
						$this->sections['HasResults'] = true;

						$this->setPagination(
										'&sector_id=' . urlencode($this->sector_id)
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
}