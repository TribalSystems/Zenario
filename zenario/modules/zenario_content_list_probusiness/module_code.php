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





class zenario_content_list_probusiness extends zenario_content_list {
	
	protected $show_language = false;
	protected $target_blank = false;
	
	public function init() {
		$this->show_language = $this->setting('show_language');
		$this->target_blank = $this->setting('target_blank');
		if (zenario_content_list::init()) {
			$this->clearCacheBy(
				$clearByContent = true, $clearByMenu = $this->setting('only_show_child_items'), $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
			
			return true;
		} else {
			return false;
		}
	}
	
	//Add extra fields for menu nodes, when the only_show_child_items setting is enabled
	protected function lookForContentSelect() {
		$sql = zenario_content_list::lookForContentSelect();
		
		if ($this->setting('only_show_child_items')) {
			$sql .= ",
				mi2.id AS menu_id,
				mi2.parent_id AS menu_parent_id,
				mh.separation AS menu_separation";
		}
		
		return $sql;
	}
	protected function addExtraMergeFields(&$row, &$item) {
		if ($this->setting('only_show_child_items')) {
			$item['Menu_Id'] = $row['menu_id'];
			$item['Menu_Parent_Id'] = $row['menu_parent_id'];
			$item['Menu_Separation'] = $row['menu_separation'];
		}
		
		if($this->show_language) {
			$item['Language'] = htmlspecialchars(getLanguageName($row['language_id'], false));
		}
		
		if($this->target_blank) {
			$item['Target_Blank'] = ' target="_blank"';
		}
	}
	
	
	
	//Adds table joins to the SQL query
	//Intended to be easily overwritten
	protected function lookForContentTableJoins() {
		$sql = "";
		
		//Filter by a categories if requested
		
		foreach (explode(',', $this->setting('category')) as $catId) {
			if ($catId && checkRowExists('categories', array('id' => (int) $catId))) {
				if ($this->setting('refine_type')=="all_categories") {
					$sql .= " INNER";
				} elseif ($this->setting('refine_type')=="any_categories" || $this->setting('refine_type')=="not_in_any_category") {
					$sql .= " LEFT";
				}
			
				$sql .= " JOIN ". DB_NAME_PREFIX. "category_item_link AS cil_". (int) $catId. "
				   ON cil_". (int) $catId. ".equiv_id = c.equiv_id
				  AND cil_". (int) $catId. ".content_type = c.type
				  AND cil_". (int) $catId. ".category_id = ". (int) $catId;
			}
		}
		
		//Only show child-nodes of the current Menu Node
		if ($this->setting('only_show_child_items')) {
			$sql .= "
			INNER JOIN ". DB_NAME_PREFIX. "menu_nodes AS mi1
			   ON mi1.equiv_id = ". (int) cms_core::$equivId. "
			  AND mi1.content_type = '". sqlEscape($this->cType). "'
			  AND mi1.target_loc = 'int'";
			
			if (!$this->setting('show_secondaries')) {
				$sql .= "
			  AND mi1.redundancy = 'primary'";
			}
			
			$sql .= "
			INNER JOIN ". DB_NAME_PREFIX. "menu_nodes AS mi2
			   ON mi2.equiv_id = c.equiv_id
			  AND mi2.content_type = c.type
			  AND mi2.target_loc = 'int'";
			
			if (!$this->setting('show_secondaries')) {
				$sql .= "
			  AND mi2.redundancy = 'primary'";
			}
			
			$sql .= "
			INNER JOIN ". DB_NAME_PREFIX. "menu_hierarchy AS mh
			   ON mi1.id = mh.ancestor_id
			  AND mi2.id = mh.child_id
			  AND mh.separation <= ". (int) $this->setting('child_item_levels');
			
			$this->showInMenuMode();
		}
		
		if ($this->setting('show_author_image')) {
			$sql .= '
			LEFT JOIN '.DB_NAME_PREFIX.'admins AS ad
				ON v.writer_id = ad.id
			LEFT JOIN '.DB_NAME_PREFIX.'files AS fi
				ON ad.image_id = fi.id';
		}
		
		return $sql;
	}
	
	
	protected function lookForContentWhere() {
		$sql = "";
		
		if ($this->setting('content_type') != 'all') {
			$sql .= "
			  AND v.type = '". sqlEscape($this->setting('content_type')). "'";
		} else {
			$cTypes = array();
			foreach (getContentTypes() as $cType) {
				switch (arrayKey($cType,'content_type_id')){
					case 'recurringevent':
					case 'event':
						break;
					default:
						$cTypes[] = $cType['content_type_id'];
						break;
				}
			}
			if ($cTypes) {
				$sql .= "
				  AND v.type IN ('" . implode("','", $cTypes) . "')";
			}
		}
		
		if ($this->setting('language_selection') == 'visitor') {
			//Only return content in the current language
			$sql .= "
			  AND c.language_id = '". sqlEscape(session('user_lang')). "'";
		} elseif ($this->setting('language_selection') == 'specific_languages') { 
			//Return content in languages selected by admin
			$arr = array('');
			foreach(explode(",", $this->setting('specific_languages')) as $langCode)  {
				$arr[] = sqlEscape($langCode);
			}
			$sql .="
				AND c.language_id IN ('". implode("','", $arr) . "')";
		}
		
		//Exclude this page itself
		$sql .= "
		  AND v.tag_id != '". sqlEscape($this->cType. '_'. $this->cID). "'";

		if ($this->setting('refine_type')!="all_categories") {
			switch ($this->setting('refine_type')) {
				case "any_categories":
					$sql .= " AND (";
					
					$sqlFragArray = array();
					
					foreach (explode(',', $this->setting('category')) as $catId) {
						if ($catId && checkRowExists('categories', array('id' => (int) $catId))) {
							$sqlFragArray[] = "cil_". (int) $catId. ".category_id IS NOT NULL";
						}
					}
					
					if (!empty($sqlFragArray)) {
						$sql .= implode(" OR ",$sqlFragArray);
					}
					
					$sql .= ")";
				
					break;
				case "not_in_any_category":
					foreach (explode(',', $this->setting('category')) as $catId) {
						if ($catId && checkRowExists('categories', array('id' => (int) $catId))) {
							$sql .= "
								AND cil_". (int) $catId. ".category_id IS NULL";
						}
					}
				
					break;
			}
		}
		
		
		//Release date section
		
		//Date range
		$startDate = $this->setting('start_date');
		$endDate = $this->setting('end_date');
		
		if ($this->setting('release_date')=='date_range'){
			$sql .= "
				AND DATE(v.publication_date) >= '" . sqlEscape($startDate) . "'
				";
			
			$sql .= "
				AND DATE(v.publication_date) <=  '" . sqlEscape($endDate) . "'
				";
		}

		//Relative date range
		if ($this->setting('release_date')=='relative_date_range' && $this->setting('relative_operator')
					&& ((int)$this->setting('relative_value'))>0 && $this->setting('relative_units')) {
			if ($this->setting('relative_operator')=='older'){
				$sqlOperator = " < ";
			} else {
				$sqlOperator = " >= ";
			}
			
			switch ($this->setting('relative_units')){
				case 'days':
					$sql .= " AND publication_date " . $sqlOperator . " DATE_SUB(DATE(NOW()), INTERVAL " . (int)$this->setting('relative_value') . " DAY)  ";
					break;
				case 'months':
					$sql .= " AND publication_date " . $sqlOperator . " DATE_SUB(DATE(NOW()), INTERVAL " . (int)$this->setting('relative_value') . " MONTH)  ";
					break;
				case 'years':
					$sql .= " AND publication_date " . $sqlOperator . " DATE_SUB(DATE(NOW()), INTERVAL " . (int)$this->setting('relative_value') . " YEAR)  ";
					break;
			}
		}
		
		//prior_to_date
		if ($this->setting('release_date')=='prior_to_date'){
			$priorToDate = $this->setting('prior_to_date');
			
			$sql .= "
				AND DATE(v.publication_date) <  '".sqlEscape($priorToDate)."'
				";
		}
		//on_date
		if ($this->setting('release_date')=='on_date'){
			$onDate = $this->setting('on_date');

			
			$sql .= "
				AND DATE(v.publication_date) =  '" . sqlEscape($onDate) . "'
				";
		}
		//after_date
		if ($this->setting('release_date')=='after_date'){
			$afterDate = $this->setting('after_date');
			
			$sql .= "
				AND DATE(v.publication_date) >  '" . sqlEscape($afterDate) . "'
				";
		}
		
		return $sql;
	}
	
	
	protected function orderContentBy() {
		if ($this->setting('only_show_child_items') && $this->setting('order') == 'Menu') {
			if ($this->setting('child_item_levels') == 1) {
				return "
				ORDER BY mi2.ordinal";
			} else {
				return "
				ORDER BY mh.separation, mi2.ordinal";
			}
		
		} else {
			return zenario_content_list::orderContentBy();
		}
	}
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case 'plugin_settings':
				setupCategoryCheckboxes($box['tabs']['first_tab']['fields']['category'], true);
				$fields['first_tab/omit_category']['hidden'] = true;
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				
				if (!$values['first_tab/only_show_child_items']) {
					$box['tabs']['first_tab']['fields']['child_item_levels']['hidden'] = 
					$box['tabs']['first_tab']['fields']['show_secondaries']['hidden'] = true;
					
					unset($box['tabs']['overall_list']['fields']['order']['values']['Menu']);
					
					if ($values['overall_list/order'] == 'Menu') {
						$box['tabs']['overall_list']['fields']['order']['current_value'] = 'Alphabetically';
					}
				
				} else {
					$box['tabs']['first_tab']['fields']['child_item_levels']['hidden'] = 
					$box['tabs']['first_tab']['fields']['show_secondaries']['hidden'] = false;
					
					if ($values['first_tab/child_item_levels'] != 1) {
						$box['tabs']['overall_list']['fields']['order']['values']['Menu'] = adminPhrase('Menu level then ordinal');
					} else {
						$box['tabs']['overall_list']['fields']['order']['values']['Menu'] = adminPhrase('Menu ordinal');
					}
				}
				
				unset($box['tabs']['first_tab']['fields']['category']['pick_items']);
				
				$box['tabs']['first_tab']['fields']['category']['hidden'] = !$values['first_tab/refine_by_category'];
				$box['tabs']['first_tab']['fields']['refine_type']['hidden'] = !$values['first_tab/refine_by_category'];

				$box['tabs']['first_tab']['fields']['specific_languages']['hidden'] = $values['first_tab/language_selection'] != 'specific_languages';
				
				
				
				//datepicker
				$releaseDateValue = $values['release_date'];
				if ($releaseDateValue == "date_range"){
					$fields['start_date']['hidden'] = false;
					$fields['end_date']['hidden'] = false;
				}else{
					$fields['start_date']['hidden'] = true;
					$fields['end_date']['hidden'] = true;
				}
				
				if ($releaseDateValue == "relative_date_range"){
					$fields['relative_operator']['hidden'] = false;
					$fields['relative_value']['hidden'] = false;
					$fields['relative_units']['hidden'] = false;
				}else{
					$fields['relative_operator']['hidden'] = true;
					$fields['relative_value']['hidden'] = true;
					$fields['relative_units']['hidden'] = true;
				}
				
				
				if ($releaseDateValue == "prior_to_date"){
					$fields['prior_to_date']['hidden'] = false;
				}else{
					$fields['prior_to_date']['hidden'] = true;
				}
				
				if ($releaseDateValue == "on_date"){
					$fields['on_date']['hidden'] = false;
				}else{
					$fields['on_date']['hidden'] = true;
				}
				
				if ($releaseDateValue == "after_date"){
					$fields['after_date']['hidden'] = false;
				}else{
					$fields['after_date']['hidden'] = true;
				}
				

				
				
				
				break;
		}
	}
	
	protected function fillAdminSlotControlsShowFilterSettings(&$controls) {
		zenario_content_list::fillAdminSlotControlsShowFilterSettings($controls);
		
		switch ($this->setting('only_show')) {
			case 'public':
				$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase('Show: Public Content Items only');
				break;
			
			case 'all':
				$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase('Show: Public and Private Content Items');
				break;
			
			case 'private':
				$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase('Show: Private Content Items only');
				break;
		}
		
		if ($this->setting('language_selection') == 'all') {
			$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase('Show Items in: All enabled Languages');
		} elseif ($this->setting('language_selection') == 'visitor') {
			$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase("Show Items in: Visitor's Language");
		} elseif ($this->setting('language_selection') == 'specific_languages') {
			$langs = getLanguages();
			$arr = array();
			foreach(explode(",", $this->setting('specific_languages')) as $langCode )  {
				$arr[] = arrayKey($langs, $langCode, 'english_name');
			}
			sort($arr);
			$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase("Show Items in: " . htmlspecialchars(implode(", ", $arr)));
		}
		
		if ($this->setting('only_show_child_items')) {
			if ((int) $this->setting('child_item_levels') == 1) {
				$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase('Menu Levels: Content in the menu one level below current Item');
			
			} elseif ((int) $this->setting('child_item_levels') >= 99) {
				$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase('Menu Levels: All Content in the menu below current Item');
			
			} else {
				$controls['notes']['filter_settings']['label'] .= '<br/>'. adminPhrase('Menu Levels: Content in the menu up to [[child_item_levels]] levels below',
														array('child_item_levels' => (int) $this->setting('child_item_levels')));
			}
		}
		
		if ($this->setting('author_advice')) {
			$controls['notes']['author_advice']['label'] = nl2br(htmlspecialchars($this->setting('author_advice')));
		}
	}

	public function fillAdminSlotControlsShowFilterSettingsCategories(&$controls) {
		if ($this->setting('category')) {
			$first = true;
			foreach(explode(',', $this->setting('category')) as $catId) {
				if ($name = getCategoryName($catId)) {
					$separator = ",";
					$labelPrefix = "";
					
					if ($this->setting('refine_type')=="all_categories") {
						$separator = " AND ";
						$labelPrefix = "In ";
					} elseif ($this->setting('refine_type')=="any_categories") {
						$separator = " OR ";
						$labelPrefix = "In ";
					} elseif ($this->setting('refine_type')=="not_in_any_category") {
						$labelPrefix = "Not in ";
						$separator = " OR ";
					}

					
					if ($first) {
						$first = false;
						$controls['notes']['filter_settings']['label'] .=
							'<br/>'.
							adminPhrase($labelPrefix . 'Category:').
							' ';
					} else {
						$controls['notes']['filter_settings']['label'] .= $separator;
					}
					
					$controls['notes']['filter_settings']['label'] .= htmlspecialchars($name);
				}
			}
		}
	}
}