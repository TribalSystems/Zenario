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

class zenario_user_profile_search extends module_base_class {
	
	protected $page = 1;
	protected $rowCount = 0;
	protected $rowsPerPage = 10;
	protected $pageCount = 0;
	protected $sections = array();
	protected $results = array();
	protected $mergeFields = array();
	protected $country_id_to_search = '';
	protected $name_to_search = '';
	protected $keywords_to_search = '';
	protected $search_fields = array();
	protected $select_fields = array();
	protected $popup_fields = array();
	
	public function init(){
		$this->country_id_to_search = request('country_id_to_search');
		$this->name_to_search = request('name_to_search');
		$this->keywords_to_search = request('keywords_to_search');
		
		$this->rowsPerPage = (int)$this->setting('search_results_per_page');
		if(!$this->rowsPerPage){
			$this->rowsPerPage = 10;
		}
		return true;
	}
	
	public function showSlot() {
		
		if ($this->checkRowsExist()) {
			$this->sections['HasResults'] = true;
				
			$this->setPagination(
					'&country_id_to_search=' . urlencode($this->country_id_to_search)
					.'&name_to_search=' . urlencode($this->name_to_search)
					.'&keywords_to_search=' . urlencode($this->keywords_to_search)
					. '&doSearch=1');
			$this->fetchRows();
				
		} else {
			$this->sections['No_Rows'] = true;
		}
		
		$this->mergeFields['Open_Form'] = $this->openForm('return zenario_user_profile_search_submit(this);', 
					'', false, false, true, $usePost = true);
		$this->mergeFields['Close_Form'] = $this->closeForm();		
		
		if($this->framework == 'standard') {
			$this->frameworkHead('Outer', 'Results', $this->mergeFields, $this->sections);
			
			$select_fields = $this->getUserFields();
			foreach ($this->results as $row) {
				
				$html = zenario_user_forms::drawUserForm((int)$this->setting('result_listing_form'), $row['id'], true);
				$this->framework('Results', array('UserForm' => $html));
				/*
				$this->frameworkHead('Results', 'UserFields', $row);
				
				foreach($select_fields as $field) {
					$this->framework('Show_' . $field['type'], array('label' => $field['label'], 'value' => $row[$field['db_column']] ));
				}
				$this->frameworkFoot('Results', 'UserFields', $row);
				*/
			}
			
			$this->frameworkFoot('Outer', 'Results', $this->mergeFields, $this->sections);
	
		} else {
			$this->sections['Results'] = &$this->results;
			$this->framework('Outer', $this->mergeFields, $this->sections);
		}
	}
	
	protected function setPagination($url_params) {
		$pageSize = $this->rowsPerPage;
	
		$this->page = ifNull((int) get('page'), 1);
		$this->pageCount = ceil($this->rowCount / $pageSize);
	
		$this->registerGetRequest('page', 1);
	
		if ($this->page > $this->pageCount) {
			$this->page = 1;
		}
	
		if ($this->pageCount > 1) {
			$pages = array();
	
			for ($p = 1; $p <= $this->pageCount; ++$p) {
				$pages[$p] = '&page='. $p . $url_params;
			}
	
			$this->mergeFields['Pagination'] = '';
			$this->pagination(
					'pagination_style',
					$this->page, $pages,
					$this->mergeFields['Pagination']);
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$this->showHideImageOptions($fields, $values, 'photo_list', empty($values['result_listing_form']));
		$this->showHideImageOptions($fields, $values, 'photo_popup', empty($values['popup_profile_form']));
	}
	
	public static function getUserFormFields($form_id){
		$dataset = getDatasetDetails('users');
		$sql = "SELECT cdf.* FROM ". DB_NAME_PREFIX. "custom_dataset_fields cdf
				INNER JOIN ". DB_NAME_PREFIX. "user_form_fields uf
						ON cdf.id = uf.user_field_id AND uf.user_form_id=" . (int) $form_id. "
				WHERE cdf.dataset_id = ". (int)$dataset['id']. "
				ORDER BY uf.ordinal";
		$result = sqlQuery($sql);
		$rv = array();
		while($row = sqlFetchAssoc($result)) {
			$rv[] = $row;
		}
		return count($rv) ? $rv : false;
	}
	
	public function getCountryOptions(){
		$search_fields = $this->getSearchFields();
		if($country_field = $search_fields['country']) {
			$sql = "SELECT cmc.id, IFNULL(vs.local_text, CONCAT('_COUNTRY_NAME_', cmc.id)) as name
				FROM " . DB_NAME_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries
				AS cmc LEFT JOIN ' . DB_NAME_PREFIX . "visitor_phrases AS vs
						ON CONCAT('_COUNTRY_NAME_',cmc.id) = vs.code
						AND vs.language_id = '" . sqlEscape(cms_core::$langId) . "'
				WHERE cmc.id IN(SELECT DISTINCT `" . $country_field['name'] . "` FROM " 
						. DB_NAME_PREFIX . ($country_field['is_system_field'] ? 'users' : 'users_custom_data') 
				. ")
				ORDER BY 2";
			
			$result = sqlQuery($sql);
				
			$options = array();
			$options[0] =  $this->phrase('_ANYWHERE_IN_THE_WORLD');
				
			while($row = sqlFetchAssoc($result)){
				$options[$row['id']] = htmlspecialchars($row['name']);
			}
		} else {
			$options = array();
		}
			
		return $options;
	}
	
	protected function getUserFieldPrefix($field){
		return $field['is_system_field'] ? 'u' : 'uc';
	}
	
	protected function getSelectFields(){
		if(!count($this->select_fields)) {
			$form_id = (int)$this->setting('result_listing_form');
			if($form_id) {
				$this->select_fields = zenario_user_profile_search::getUserFormFields($form_id);
			}
		}
		return $this->select_fields;
	}

	protected function getPopupFields(){
		if(!count($this->popup_fields)) {
			$form_id = (int)$this->setting('popup_profile_form');
			if($form_id) {
				$this->popup_fields = zenario_user_profile_search::getUserFormFields($form_id);
			}
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
				$this->search_fields['name_fields'] = array();
				foreach(explode(',', $name_fields) as $characteristic_id) {
					$this->search_fields['name_fields'][] = zenario_users::getCharacteristic($characteristic_id);
				}
			} else {
				$this->search_fields['name_fields'] = false;
			}
				
			$keyword_fields = $this->setting('keyword_user_characteristics');
			if($keyword_fields) {
				$this->search_fields['keyword_fields'] = array();
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
		$sql = "SELECT u.id, u.image_id, CONCAT(u.first_name, ' ', u.last_name) as first_name_last_name";
		
		
		$select_fields = $this->getUserFields();
		
		$x = 0;
		foreach($select_fields as $field) {
			echo "\n";
			//be aware of increment this counter on the function selectFrom
			$x++;
			$table_prefix = $this->getUserFieldPrefix($field);
			
			if ($field['type'] == 'checkboxes') {
				/*
				$sql .= ",(SELECT GROUP_CONCAT(ucv.label SEPARATOR ', ')
						FROM " . DB_NAME_PREFIX . 'custom_dataset_values_link cdvl
						INNER JOIN ' . DB_NAME_PREFIX . 'custom_dataset_field_values cdfv 
							ON cdvl.value_id = cdfv.id
							AND cdfv.field_id ='. $field['id'] .
							' WHERE cdvl.linking_id = u.id GROUP BY cdfv.field_id) as `' . 
						$field['db_column'] . '`';
					*/
			} else {
				$sql .= ',' . $table_prefix . '.`' . $field['db_column'] . '`';
			}
		}
		
		//print_r($sql);
		
		return $sql;
	}
	
	protected function selectFrom() {
		$sql = " FROM " . DB_NAME_PREFIX . "users u
				INNER JOIN " . DB_NAME_PREFIX . "users_custom_data uc
					ON u.id = uc.user_id AND u.status = 'active' ";
		
		$select_fields = $this->getUserFields();
		$x = 0;
		/*
		foreach($select_fields as $field) {
			//be aware of increment order on the function selectColumns
			$x++;
			switch($field['type']) {
				case 'list_single_select':
						$table_prefix = $this->getUserFieldPrefix($field);
						$sql .= ' LEFT JOIN ' . DB_NAME_PREFIX . 'user_characteristic_values ucv' . $x 
							. ' ON ' . $table_prefix . '.`' . $field['name'] . '`=ucv' . $x . 'id';
					break;
			}
		}
		*/
		$sql .= ' WHERE 1=1 ';
		
		$search_fields = $this->getSearchFields();
		
		$country_id_to_search = request('country_id_to_search');
		if($country_id_to_search && ($country_field = $search_fields['country'])) {
			$table_prefix = $this->getUserFieldPrefix($country_field);
			$sql .= ' AND ' . $table_prefix . '.`' . $country_field['name'] 
				. "`='" . sqlEscape($country_id_to_search) . "' ";
			
			$this->mergeFields['country_id_to_search'] = $country_id_to_search;
		}
		
		$name_to_search = request('name_to_search');
		if($name_to_search && ($name_fields = $search_fields['name_fields'])) {
			$sql_names = '';
			$name_to_search_escaped = sqlEscape($name_to_search);
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
			
			$this->mergeFields['name_to_search'] = $name_to_search;
		}
		
		$keywords_to_search = request('keywords_to_search');
		if($keywords_to_search && ($keyword_fields = $search_fields['keyword_fields'])) {
			$sql_keywords = '';
			$keywords_to_search_escaped = sqlEscape($keywords_to_search);
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
			
			$this->mergeFields['keywords_to_search'] = $keywords_to_search;
		}
		
		return $sql;
	}
	
	protected function selectOrder() {
		$order_by = ' ORDER BY ';
		if(!$this->name_to_search && !$this->keywords_to_search){
			$order_by .= ' u.last_login desc ';
		} else {
			$order_by .= ' u.last_name ';
		}
		return $order_by . paginationLimit($this->page, $this->rowsPerPage);
	}
	
	protected function checkRowsExist() {
		$sql = "SELECT COUNT(*) ". $this->selectFrom();
		$result = sqlQuery($sql);
		$row = sqlFetchRow($result);
		return $this->rowCount = $row[0];
	}
	
	protected function getUerImage($image_id, $img_prefix){
		$url = '';
		$width = $this->setting($img_prefix . '_width');
		$height = $this->setting($img_prefix . '_height');
		$file_id = $image_id;
		imageLink($width, $height, $url, $file_id, $width, $height, $this->setting($img_prefix . '_canvas'));
		return $url;
	}
	
	protected function fetchRows() {
		$sql = 	$this->selectColumns().
		$this->selectFrom().
		$this->selectOrder();
		
		
		$result = sqlQuery($sql);
		$this->sections['HasResults'] = true;
		$select_fields = $this->getUserFields();
		$count = 0;
		$setting_cols = $this->setting('search_results_columns_page');
		while($row = sqlFetchAssoc($result)){
			++$count;
			$record = array('id' => $row['id']);
			
			if($image_id = $row['image_id']) {
				$record['Photo_Listing'] = $this->getUerImage($image_id, 'photo_list');
				$record['Photo_Popup'] = $this->getUerImage($image_id, 'photo_popup');
			}
			
			foreach($select_fields as $field) {
				$field_name = $field['db_column'];
				switch($field['type']){ // change
					case 'group':
					case 'boolean':
						$record[$field_name] = $row[$field_name] ? 'Yes' : 'No';
						break;
						
					case 'integer':
					case 'float':
						$record[$field_name] = $row[$field_name];
						break;
						
					case 'country':
						$record[$field_name] = zenario_country_manager::getEnglishCountryName($row[$field_name]);
						break;
						
					default:
						$record[$field_name] = htmlspecialchars($row[$field_name]);
				}
			}
			$record['StartNewRow'] = ($count % $setting_cols) == 0;
			$this->results[] = $record;
		}
	}
	
}