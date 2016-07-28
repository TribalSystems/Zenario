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





class zenario_country_manager extends module_base_class {
	
	var $mergeFields = array();

	function init(){
		return true;
	}

	//refiner - 'all', 'active', 'inactive'
	private static function getWhereCondForActiveField($refiner){
		$sql = '';	
		switch (strtolower(trim($refiner))){
			case 'active':
				$sql =' AND active=1 ';
				break;
			case 'inactive':
				$sql =' AND active=0 ';
				break;
			case 'all':
			case 'default':
				break;
		}
		return $sql;
	}
	
	private static function getOrderByClause(){
		return " ORDER BY english_name";
	}


	public static function getCountryAdminNamesIndexedByISOCode($countryActivityFilter='active',$countryId=''){
		$tbl= self::getCountryFullInfo($countryActivityFilter,$countryId) ;
		$rv = array();
		foreach($tbl as $K=>$V)
			$rv[$K]=$V['english_name'];
		sqlArraySort($rv);
 		return $rv;
	}

	public static function getCountryAdminNames($countryActivityFilter='active',$countryId=''){
		$tbl= self::getCountryFullInfo($countryActivityFilter,$countryId) ;
		$rv = array();
		foreach($tbl as $K=>$V)
			$rv['COUNTRY_' . strtoupper($K)]=$V['english_name'];
		sqlArraySort($rv);
 		return $rv;
	}
	

	public static function getCountryNamesInCurrentVisitorLanguageIndexedByISOCode($countryActivityFilter='active',$countryId=''){
		$tbl= zenario_country_manager::getVisitorCountriesIndexedByISOCode($_SESSION['user_lang'],$countryActivityFilter,$countryId) ;
		$rv = array();
		foreach($tbl as $K=>$V)
			$rv[$K]=$V['phrase'];
		sqlArraySort($rv);
 		return $rv;
	}
	
	public static function getVisitorCountriesIndexedByISOCode($langId,$countryActivityFilter='active',$countryId=''){
		$rv = array();
		$values = self::getCountryFullInfo($countryActivityFilter,$countryId);
		foreach($values as $K=>$V)
			$rv[strtoupper($K)]=array('phrase'=>self::adminPhrase($langId,'_COUNTRY_NAME_' . strtoupper($K)),'country_id'=>strtoupper($K));
		return $rv;
	}

	public static function getCountryNamesInCurrentVisitorLanguage($countryActivityFilter='active',$countryId=''){
		$tbl= zenario_country_manager::getVisitorCountries($_SESSION['user_lang'],$countryActivityFilter,$countryId) ;
		$rv = array();
		foreach($tbl as $K=>$V)
			$rv[$K]=$V['phrase'];
		sqlArraySort($rv);
 		return $rv;
	}
	
	public static function getVisitorCountries($langId,$countryActivityFilter='active',$countryId=''){
		$rv = array();
		$values = self::getCountryFullInfo($countryActivityFilter,$countryId);
		foreach($values as $K=>$V)
			$rv['COUNTRY_' . strtoupper($K)]=array('phrase'=>self::adminPhrase($langId,'_COUNTRY_NAME_' . strtoupper($K)),'country_id'=>strtoupper($K));
		return $rv;
	}

	public static function getRegionNamesInCurrentVisitorLanguage($countryActivityFilter='active',$countryId='',$regionId=''){
		$tbl= zenario_country_manager::getVisitorRegions($_SESSION['user_lang'],$countryActivityFilter,$countryId,$regionId) ;
		$rv = array();
		foreach($tbl as $K=>$V)
			$rv[$K]=$V['phrase'];
		sqlArraySort($rv);
 		return $rv;
	}

	
	public static function getVisitorRegions($langId,$countryActivityFilter='active',$countryId='',$regionId=''){
		$rv = array();
		$values = self::getRegions($countryActivityFilter,$countryId,$regionId);
		foreach($values as $K=>$V)
			$rv[$V['id']]= array('phrase'=> self::adminPhrase($langId, $V['name']),'id'=>$V['id'], 'country_id'=>$V['country_id']);
		return $rv;
	}
	
	public static function adminPhrase($langId,$phraseCode){
		return phrase($phraseCode, false, 'zenario_country_manager', $langId);
	}
	
	
	public static function getRegions($countryActivityFilter='all',$countryCodeFilter='',$regionCodeFilter='',$regionIdFilter=false,$parentRegionFilter=0,$regionNameFilter='',$excludeIdsCSV=''){
		$rv = array();
		$sql = 'SELECT
					R.id, 
					R.parent_id,
					R.country_id,
					R.name,
					R.active,
					C.english_name as country_english_name,
					C.active AS country_active 
				FROM
					' . DB_NAME_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_regions R 
				LEFT JOIN  
					'  . DB_NAME_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries C 
				ON 
					C.id = R.country_id 
				WHERE 1 ';
				
		if ($regionIdFilter) {
			$sql .= " AND R.id = '" . sqlEscape($regionIdFilter)  . "' ";
		}
		if ($countryCodeFilter) {
			$sql .= " AND R.country_id = '" . sqlEscape($countryCodeFilter)  . "' ";
		} 
		if ($parentRegionFilter) {
			$sql .= " AND R.parent_id = " . (int) $parentRegionFilter . " " ;
		}
		if ($regionNameFilter) {
			$sql .=" AND R.name = '" . sqlEscape($regionNameFilter) . "' ";
		}
		if ($excludeIdsCSV){
			$sql .=" AND R.id NOT IN (" . sqlEscape($excludeIdsCSV) . ") ";
		}
		$sql .= " ORDER BY R.name";
		
		$res=sqlQuery($sql);
		while($row= sqlFetchAssoc($res))
			$rv[$row['id']]=$row;
		
		return $rv;
	}
	
	public static function getRegionsByCountry($mode, $value = false) {
		switch ($mode) {
			case ZENARIO_CENTRALISED_LIST_MODE_INFO:
				return array(
					'filter_label' => 'Country ID:',
					'can_filter' => true);
			case ZENARIO_CENTRALISED_LIST_MODE_LIST:
				return getRowsArray(ZENARIO_COUNTRY_MANAGER_PREFIX. 'country_manager_regions', 'name', array(), 'name');
			case ZENARIO_CENTRALISED_LIST_MODE_FILTERED_LIST:
				return getRowsArray(ZENARIO_COUNTRY_MANAGER_PREFIX. 'country_manager_regions', 'name', array('country_id' => $value), 'name');
			case ZENARIO_CENTRALISED_LIST_MODE_VALUE:
				return getRow(ZENARIO_COUNTRY_MANAGER_PREFIX. 'country_manager_regions', 'name', array('id' => $value));
		}
	}
	
	public static function getCountryOfRegion($regionId,$fuse=10){
		$rv = array();
		if ($fuse--) {
			$region = getRow(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_regions',array('country_id','parent_id'),array('id' => $regionId));
			if ($region['country_id']) {
				$rv =  getRow(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries',array('id','english_name','active'),array('id' => $region['country_id']));
			} elseif ($region['parent_id']) {
				$rv = self::getCountryOfRegion($region['parent_id'],$fuse);
			}
		}
		return $rv;
	}
	
	public static function getRegionById($ID){
		$rv = array();
		$sql = 'SELECT
					id, 
					parent_id,
					country_id,
					name,
					active
				FROM
					' . DB_NAME_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_regions
				WHERE  
					id=' . (int)$ID ;
				
		$res=sqlQuery($sql);
		if ($row= sqlFetchAssoc($res)){
			$rv =  $row;
		}
		return $rv;
	}

	public static function getEnglishRegionName($ID){
		if ($region=self::getRegionById($ID)){
			return $region['name']; 
		} else {
			return '';
		}
	}



	//refiner - 'all', 'active', 'inactive'
	public static function getCountryCodesList($activityFiler='all'){
		$rv=array();
		$sql = 'SELECT id FROM ' . DB_NAME_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries WHERE 1 ' .self::getWhereCondForActiveField($activityFiler);
		$sql .= self::getOrderByClause();
		$res = sqlQuery($sql);
		while($row = sqlFetchAssoc($res))
			$rv[strtoupper($row['id'])]=strtoupper($row['id']);

		return $rv;
	}

	//code refiner - if set returns one dimensional country that code mathces $codeFilter, or zero if no  matching country code found
	public static function getCountryFullInfo($activityFilter='all',$codeFilter=''){
		$rv=array();
		$sql = 'SELECT 
					id,
					english_name,
					active 
				FROM ' 
					. DB_NAME_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries 
				WHERE 1 ' 
					. self::getWhereCondForActiveField($activityFilter);
		if (sqlEscape(trim(strtoupper($codeFilter)))!='')
			$sql .= " 
					AND 
						id='" . sqlEscape(trim(strtoupper($codeFilter))) . "'";
		$sql .= self::getOrderByClause();

		$res = sqlQuery($sql);
		while ($row = sqlFetchAssoc($res))
			$rv[strtoupper($row['id'])]=array('english_name'=>$row['english_name'],'vlp_phrase'=>'_COUNTRY_NAME_' . strtoupper($row['id']),  'status'=> $row['active']);
		return $rv;
	}

	public static function getCountryActiveCountries(){
		$rv=array();
		$sql = 'SELECT
					id,
					english_name as name
				FROM '
				. DB_NAME_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries
				WHERE active=1 ORDER BY 2';
		return sqlQuery($sql);
	}

	public static function getActiveCountries($mode, $value = false) {
		switch ($mode) {
			case ZENARIO_CENTRALISED_LIST_MODE_INFO:
				return array('can_filter' => false);
			case ZENARIO_CENTRALISED_LIST_MODE_LIST:
				return getRowsArray(ZENARIO_COUNTRY_MANAGER_PREFIX. 'country_manager_countries', 'english_name', array('active' => 1), 'english_name');
			case ZENARIO_CENTRALISED_LIST_MODE_VALUE:
				return getRow(ZENARIO_COUNTRY_MANAGER_PREFIX. 'country_manager_countries', 'english_name', array('id' => $value));
		}
	}
	
	public static function getCountryDialingCodes($mode, $value = false) {
		switch ($mode) {
			case ZENARIO_CENTRALISED_LIST_MODE_INFO:
				return array('can_filter' => false);
			case ZENARIO_CENTRALISED_LIST_MODE_LIST:
				$codes = array();
				$sql = '
					SELECT id, english_name, phonecode
					FROM ' . DB_NAME_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries
					WHERE active = 1
					AND phonecode != 0
					ORDER BY english_name';
				$result = sqlSelect($sql);
				while ($row = sqlFetchAssoc($result)) {
					$codes[$row['id']] = static::formatCountryDialingCode($row['english_name'], $row['phonecode']);
				}
				return $codes;
			case ZENARIO_CENTRALISED_LIST_MODE_VALUE:
				return array();
		}
	}
	
	public static function getCountryShortDialingCodes($mode, $value = false) {
		switch ($mode) {
			case ZENARIO_CENTRALISED_LIST_MODE_INFO:
				return array('can_filter' => false);
			case ZENARIO_CENTRALISED_LIST_MODE_LIST:
				$codes = array();
				$sql = '
					SELECT id, english_name, phonecode
					FROM ' . DB_NAME_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries
					WHERE active = 1
					AND phonecode != 0
					ORDER BY english_name';
				$result = sqlSelect($sql);
				while ($row = sqlFetchAssoc($result)) {
					$codes[$row['id']] = static::formatCountryDialingCode($row['english_name'], $row['phonecode'], true);
				}
				return $codes;
			case ZENARIO_CENTRALISED_LIST_MODE_VALUE:
				return array();
		}
	}
	
	public static function formatCountryDialingCode($name, $code, $short = false) {
		$string = '(' . '+' . $code . ')';
		if (!$short) {
			$string = $name . ' ' . $string;
		}
		return $string;
	}
	
	public static function getEnglishCountryName_framework($mergeFields, $attributes) {
		$out = '';
		if ($value = ifNull(arrayKey($mergeFields, arrayKey($attributes, 'name')), arrayKey($attributes, 'value'))) {
			$out = getRow(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries', 'english_name', array('id' => $value));
		}
		
		if (engToBooleanArray($attributes, 'escape')) {
			return htmlspecialchars($out);
		} else {
			return $out;
		}
	}

	public static function getCountryName($code, $languageId = false){
		if (!$code) {
			return '';
		} else {
			return phrase('_COUNTRY_NAME_'. strtoupper($code), false, 'zenario_country_manager', $languageId);
		}
	}

	public static function getEnglishCountryName($code){
		if ($code && count($rv = self::getCountryFullInfo('all',$code)))
			return  $rv[strtoupper($code)]['english_name'];
		else
			return '';
	}
	
	public static function getRegionParents($parentId){
		$rv = array();
		$fuse = 100;
		$i = 0;
		while($fuse-- && $newParentId = getRow(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions", 'parent_id', array('id' => $parentId)) ) {
			$rv[$i++] = $parentId = $newParentId;
		}
		if ($newParentId = getRow(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions", 'country_id', array('id' => $parentId)) ) {
			$rv[$i++] = $newParentId;
		}
		return $rv;	
	}
	
	public static function checkVisitorInCountries($countryIdsList, $testType='include') {
		$countryIdsArray = explode(",", $countryIdsList);
		if (strtolower($testType)=='exclude'){
			//return false if user country is in exclude list
			if(!empty($_SESSION['country_id'])) {
				foreach ($countryIdsArray as $countryId) {
					if (strtoupper($countryId) == $_SESSION['country_id']) {
						//matchs an exclude
						return false;
					}
				}
				//No matchs
				return true;
			} else {
				//No matchs as user country_id not set!
				return true;
			}
		} else {
			//return true if user country is in include list
			if(!empty($_SESSION['country_id'])) {
				foreach ($countryIdsArray as $countryId) {
					if (strtoupper($countryId) == $_SESSION['country_id']) {
						//matchs an include
						return true;
					}
				}
				//No matchs
				return false;
			} else {
				//No matchs as user country_id not set!
				return false;
			}
		}
	}

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (cms_core::$isTwig) return;
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if (cms_core::$isTwig) return;
		require funIncPath(__FILE__, __FUNCTION__);
	}

	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (cms_core::$isTwig) return;
		require funIncPath(__FILE__, __FUNCTION__);
	}


	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if (cms_core::$isTwig) return;
		require funIncPath(__FILE__, __FUNCTION__);
	}


	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if (cms_core::$isTwig) return;
		require funIncPath(__FILE__, __FUNCTION__);
 	}
}

