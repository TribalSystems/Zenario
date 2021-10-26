<?php
/*
 * Copyright (c) 2021, Tribal Limited
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

class zenario_user_profile_search extends ze\moduleBaseClass {
	
	protected $page = 1;
	protected $rowCount = 0;
	protected $rowsPerPage = 10;
	protected $pageCount = 0;
	protected $results = [];
	protected $country_id_to_search = '';
	protected $name_to_search = '';
	protected $keywords_to_search = '';
	protected $search_fields = [];
	protected $select_fields = [];
	protected $popup_fields = [];
	
	protected $data = [];
	
	public function init(){
		$this->country_id_to_search = $_REQUEST['country_id_to_search'] ?? false;
		$this->name_to_search = $_REQUEST['name_to_search'] ?? false;
		$this->keywords_to_search = $_REQUEST['keywords_to_search'] ?? false;
		
		$this->rowsPerPage = (int)$this->setting('search_results_per_page');
		if(!$this->rowsPerPage){
			$this->rowsPerPage = 10;
		}
		
		$this->data['Column_Count'] = $this->setting('search_results_columns_page');
		$this->data['countries'] = $this->getCountryOptions();
		
		
		if ($this->checkRowsExist()) {
			$this->setPagination(
					 '&country_id_to_search=' . urlencode($this->country_id_to_search)
					.'&name_to_search=' . urlencode($this->name_to_search)
					.'&keywords_to_search=' . urlencode($this->keywords_to_search)
					.'&doSearch=1');
			$this->fetchRows();
			
			
			$this->data['results'] = $this->results;
		} else {
			$this->data['No_Rows'] = true;
		}
		$this->data['Open_Form'] = $this->openForm('return zenario_user_profile_search_submit(this);', '', false, false, true, $usePost = true);
		$this->data['Close_Form'] = $this->closeForm();
		
		return true;
	}
	
	public function showSlot() {
		$this->twigFramework($this->data);
	}
	
	protected function setPagination($url_params) {
		$pageSize = $this->rowsPerPage;
	
		$this->page = (int) ($_GET['page'] ?? 1) ?: 1;
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
	
			$this->data['Pagination'] = '';
			$this->pagination(
					'pagination_style',
					$this->page, $pages,
					$this->data['Pagination']);
		}
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				$filter = [];
				$filter['type'] = ['centralised_radios', 'centralised_select'];
				$filter['values_source'] = 'zenario_country_manager::getActiveCountries';
				$fields['first_tab/country_search_user_characteristic']['values'] =
					ze\datasetAdm::listCustomFields('users', $flat = false, $filter, $customOnly = true, $useOptGroups = true);
				
				$filter = [];
				$filter['type'] = 'text';
				$lov = ze\datasetAdm::listCustomFields('users', $flat = false, $filter, $customOnly = false, $useOptGroups = true, $hideEmptyOptGroupParents = true);
				
				//Note: these are multi-checkboxes fields. I want to show the tabs, but I don't want
				//people to be able to select them
				foreach ($lov as &$v) {
					if (empty($v['parent'])) {
						$v['readonly'] =
						$v['disabled'] = true;
						$v['style'] = 'display: none;';
					}
				}
				
				$fields['first_tab/keyword_user_characteristics']['values'] =
				$fields['first_tab/name_user_characteristics']['values'] = $lov;
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				$hidden = !$values['results_tab/photo_list_show'];
				$this->showHideImageOptions($fields, $values, 'results_tab', $hidden, 'photo_list_');
				$hidden = !$values['results_tab/photo_popup_show'];
				$this->showHideImageOptions($fields, $values, 'results_tab', $hidden, 'photo_popup_');
				break;
		}
	}
	
	public function getCountryOptions(){
		$search_fields = $this->getSearchFields();
		if ($country_field = $search_fields['country']) {
			$sql = "SELECT cmc.id, IFNULL(vs.local_text, CONCAT('_COUNTRY_NAME_', cmc.id)) as name
				FROM " . DB_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries
				AS cmc LEFT JOIN ' . DB_PREFIX . "visitor_phrases AS vs
						ON CONCAT('_COUNTRY_NAME_',cmc.id) = vs.code
						AND vs.language_id = '" . ze\escape::asciiInSQL(ze::$visLang) . "'
				WHERE cmc.id IN(SELECT DISTINCT `" . $country_field['name'] . "` FROM " 
						. DB_PREFIX . ($country_field['is_system_field'] ? 'users' : 'users_custom_data') 
				. ")
				ORDER BY 2";
			
			$result = ze\sql::select($sql);
			$options = [];
			$options[0] = ['id' => 0, 'name' => $this->phrase('_ANYWHERE_IN_THE_WORLD')];
			while($row = ze\sql::fetchAssoc($result)){
				$options[$row['id']] = $row;
			}
		} else {
			$options = [];
		}
		return $options;
	}
	
	protected function getUserFieldPrefix($field){
		return $field['is_system_field'] ? 'u' : 'uc';
	}
	
	protected function getSelectFields(){
		$dataset = ze\dataset::details('users');
		if(!count($this->select_fields)) {
			$this->select_fields = ze\row::getAssocs('custom_dataset_fields', 
				true, 
				[
					'db_column' => 
						[
							'first_name', 
							'last_name', 
							'company_name', 
							'bus_country_id',
						],
					'dataset_id' => $dataset['id']
				]
			);
		}
		return $this->select_fields;
	}

	protected function getPopupFields() {
		$dataset = ze\dataset::details('users');
		if(!count($this->popup_fields)) {
			$this->popup_fields = ze\row::getAssocs('custom_dataset_fields', 
				true, 
				[
					'db_column' => 
						[
							'salutation',
							'first_name', 
							'last_name', 
							'email',
							'company_name', 
							'bus_country_id',
							'job_title',
							'job_type',
							'company_name',
							'job_department',
							'address1',
							'address2',
							'city',
							'state',
							'postcode',
							'bus_country_id',
							'mobile',
							'phone',
							'interests',
							'skills_expertise',
							'summary_of_my_business',
							'linkedin',
							'languages_spoken',
							'other_languages_spoken'
						],
					'dataset_id' => $dataset['id']
				]
			);
		}
		return $this->popup_fields;
	}
	
	protected function getUserFields(){
		return array_merge($this->getSelectFields(), $this->getPopupFields());
	}
	
	protected function getSearchFields() {
		if(!count($this->search_fields)) {
			$country_field = (int)$this->setting('country_search_user_characteristic');
			if($country_field) {
				$this->search_fields['country'] = zenario_users::getCharacteristic($country_field);
			} else {
				$this->search_fields['country'] = false;
			}
			
			$name_fields = $this->setting('name_user_characteristics');
			if($name_fields) {
				$this->search_fields['name_fields'] = [];
				foreach(explode(',', $name_fields) as $characteristic_id) {
					$this->search_fields['name_fields'][] = zenario_users::getCharacteristic($characteristic_id);
				}
			} else {
				$this->search_fields['name_fields'] = false;
			}
				
			$keyword_fields = $this->setting('keyword_user_characteristics');
			if($keyword_fields) {
				$this->search_fields['keyword_fields'] = [];
				foreach(explode(',', $keyword_fields) as $characteristic_id) {
					$this->search_fields['keyword_fields'][] = zenario_users::getCharacteristic($characteristic_id);
				}
			} else {
				$this->search_fields['keyword_fields'] = false;
			}
		}
		return $this->search_fields;
	}
	
	
	
	protected function selectColumns() {
		$sql = "SELECT u.id, u.image_id";
		
		$select_fields = $this->getUserFields();
		
		foreach($select_fields as $field) {
			
			if ($field['type'] == 'checkboxes') {
				$sql .= ',
					(
						SELECT GROUP_CONCAT(v.label SEPARATOR ", ")
						FROM ' . DB_PREFIX . 'custom_dataset_field_values v
						INNER JOIN ' . DB_PREFIX . 'custom_dataset_values_link l
							ON v.id = l.value_id
						WHERE v.field_id = ' . (int)$field['id'] . '
							AND l.linking_id = u.id
					) AS ' . ze\escape::sql($field['db_column']) . '
				';
			} else {
				$table_prefix = $this->getUserFieldPrefix($field);
				$sql .= ',' . $table_prefix . '.`' . $field['db_column'] . '`';
			}
		}
		
		return $sql;
	}
	
	protected function selectFrom() {
		$dataset = ze\dataset::details('users');
		
		$sql = "FROM " . DB_PREFIX . "users u
				INNER JOIN " . DB_PREFIX . "users_custom_data uc
					ON u.id = uc.user_id AND u.status = 'active'";
		
		$select_fields = $this->getUserFields();
		$search_fields = $this->getSearchFields();
		
		$country_id_to_search = $_REQUEST['country_id_to_search'] ?? false;
		if($country_id_to_search && ($country_field = $search_fields['country'])) {
			$table_prefix = $this->getUserFieldPrefix($country_field);
			$sql .= ' AND ' . $table_prefix . '.`' . $country_field['name'] 
				. "`='" . ze\escape::sql($country_id_to_search) . "' ";
		}
		$this->data['country_id_to_search'] = $country_id_to_search;
		
		$name_to_search = $_REQUEST['name_to_search'] ?? false;
		if($name_to_search && ($name_fields = $search_fields['name_fields'])) {
			$sql_names = '';
			$name_to_search_escaped = ze\escape::sql($name_to_search);
			$x = 0;
			foreach($name_fields as $field) {
				$table_prefix = $this->getUserFieldPrefix($field);
				if($x++) {
					$sql_names .= ' OR ';
				}
				$sql_names .= $table_prefix . '.`' . $field['name'] . "` like '" 
						. $name_to_search_escaped . "%' ";
			}
			if($sql_names) {
				$sql .= ' AND (' . $sql_names . ') ';
			}
			
			$this->data['name_to_search'] = $name_to_search;
		}
		
		$keywords_to_search = $_REQUEST['keywords_to_search'] ?? false;
		if($keywords_to_search && ($keyword_fields = $search_fields['keyword_fields'])) {
			$sql_keywords = '';
			$keywords_to_search_escaped = ze\escape::sql($keywords_to_search);
			$x = 0;
			foreach($keyword_fields as $field) {
				$table_prefix = $this->getUserFieldPrefix($field);
				if($x++) {
					$sql_keywords .= ' OR ';
				}
				$sql_keywords .= $table_prefix . '.`' . $field['name'] . "` like '%"
						. $keywords_to_search_escaped . "%' ";
			}
			if($sql_keywords) {
				$sql .= ' AND (' . $sql_keywords . ') ';
			}
			
			$this->data['keywords_to_search'] = $keywords_to_search;
		}
		
		return $sql;
	}
	
	protected function selectOrder() {
		$order_by = ' GROUP BY u.id ';
		$order_by .= ' ORDER BY ';
		if(!$this->name_to_search && !$this->keywords_to_search){
			$order_by .= ' u.last_login desc ';
		} else {
			$order_by .= ' u.last_name ';
		}
		return $order_by . ze\sql::limit($this->page, $this->rowsPerPage);
	}
	
	protected function checkRowsExist() {
		$sql = "SELECT COUNT(*) ". $this->selectFrom();
		$result = ze\sql::select($sql);
		$row = ze\sql::fetchRow($result);
		
		$this->rowCount = $row[0];
		return $row[0];
	}
	
	protected function getUserImage($image_id, $img_prefix){
		$url = '';
		$width = $this->setting($img_prefix . '_width');
		$height = $this->setting($img_prefix . '_height');
		$file_id = $image_id;
		ze\file::imageLink($width, $height, $url, $file_id, $width, $height, $this->setting($img_prefix . '_canvas'));
		return $url;
	}
	
	protected function fetchRows() {
		$sql = $this->selectColumns().
		$this->selectFrom().
		$this->selectOrder();
		
		$result = ze\sql::select($sql);
		$rowsCount = ze\sql::numRows($result);
		
		$select_fields = $this->getUserFields();
		$count = 0;
		while ($row = ze\sql::fetchAssoc($result)) {
			++$count;
			$record = ['id' => $row['id']];
			
			if($image_id = $row['image_id']) {
				$record['Photo_Listing'] = $this->getUserImage($image_id, 'photo_list');
				$record['Photo_Popup'] = $this->getUserImage($image_id, 'photo_popup');
			}
			
			foreach($select_fields as $field) {
				$field_name = $field['db_column'];
				switch($field['type']){
					case 'group':
					case 'boolean':
						$record[$field_name] = $row[$field_name] ? 'Yes' : 'No';
						break;
						
					case 'integer':
					case 'float':
						$record[$field_name] = $row[$field_name];
						break;
						
					default:
						$record[$field_name] = htmlspecialchars($row[$field_name]);
				}
			}
			if ($record['bus_country_id']) {
				$record['bus_country_id'] = ze\lang::phrase('_COUNTRY_NAME_' . $record['bus_country_id'], [], 'zenario_country_manager');
			}
			
			if ($rowsCount == $count) {
				$record['last_result'] = true;
			}
			
			$this->results[] = $record;
		}
	}
	
}