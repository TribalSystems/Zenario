<?php
/*
 * Copyright (c) 2023, Tribal Limited
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


class zenario_country_manager extends ze\moduleBaseClass {
	
	var $mergeFields = [];

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
		$rv = [];
		foreach($tbl as $K=>$V)
			$rv[$K]=$V['english_name'];
		ze\ray::sqlSort($rv);
 		return $rv;
	}

	public static function getCountryAdminNames($countryActivityFilter='active',$countryId=''){
		$tbl= self::getCountryFullInfo($countryActivityFilter,$countryId) ;
		$rv = [];
		foreach($tbl as $K=>$V)
			$rv['COUNTRY_' . strtoupper($K)]=$V['english_name'];
		ze\ray::sqlSort($rv);
 		return $rv;
	}
	

	public static function getCountryNamesInCurrentVisitorLanguageIndexedByISOCode($countryActivityFilter='active',$countryId=''){
		$tbl= zenario_country_manager::getVisitorCountriesIndexedByISOCode(ze\content::visitorLangId(),$countryActivityFilter,$countryId) ;
		$rv = [];
		foreach($tbl as $K=>$V)
			$rv[$K]=$V['phrase'];
		ze\ray::sqlSort($rv);
 		return $rv;
	}
	
	public static function getVisitorCountriesIndexedByISOCode($langId,$countryActivityFilter='active',$countryId=''){
		$rv = [];
		$values = self::getCountryFullInfo($countryActivityFilter,$countryId);
		foreach($values as $K=>$V)
			$rv[strtoupper($K)]=['phrase'=>self::adminPhrase($langId,'_COUNTRY_NAME_' . strtoupper($K)),'country_id'=>strtoupper($K)];
		return $rv;
	}

	public static function getCountryNamesInCurrentVisitorLanguage($countryActivityFilter='active',$countryId=''){
		$tbl= zenario_country_manager::getVisitorCountries(ze\content::visitorLangId(),$countryActivityFilter,$countryId) ;
		$rv = [];
		foreach($tbl as $K=>$V)
			$rv[$K]=$V['phrase'];
		ze\ray::sqlSort($rv);
 		return $rv;
	}
	
	public static function getVisitorCountries($langId,$countryActivityFilter='active',$countryId=''){
		$rv = [];
		$values = self::getCountryFullInfo($countryActivityFilter,$countryId);
		foreach($values as $K=>$V)
			$rv['COUNTRY_' . strtoupper($K)]=['phrase'=>self::adminPhrase($langId,'_COUNTRY_NAME_' . strtoupper($K)),'country_id'=>strtoupper($K)];
		return $rv;
	}

	public static function getRegionNamesInCurrentVisitorLanguage($countryActivityFilter='active',$countryId='',$regionId=''){
		$tbl= zenario_country_manager::getVisitorRegions(ze\content::visitorLangId(),$countryActivityFilter,$countryId,$regionId) ;
		$rv = [];
		foreach($tbl as $K=>$V)
			$rv[$K]=$V['phrase'];
		ze\ray::sqlSort($rv);
 		return $rv;
	}

	
	public static function getVisitorRegions($langId,$countryActivityFilter='active',$countryId='',$regionId=''){
		$rv = [];
		$values = self::getRegions($countryActivityFilter,$countryId,$regionId);
		foreach($values as $K=>$V)
			$rv[$V['id']]= ['phrase'=> self::adminPhrase($langId, $V['name']),'id'=>$V['id'], 'country_id'=>$V['country_id']];
		return $rv;
	}
	
	public static function adminPhrase($langId,$phraseCode){
		return ze\lang::phrase($phraseCode, false, 'zenario_country_manager', $langId);
	}
	
	
	public static function getRegions($countryActivityFilter='all',$countryCodeFilter='',$regionCodeFilter='',$regionIdFilter=false,$parentRegionFilter=0,$regionNameFilter='',$excludeIdsCSV=''){
		$rv = [];
		$sql = 'SELECT
					R.id, 
					R.parent_id,
					R.country_id,
					R.name,
					R.active,
					C.english_name as country_english_name,
					C.active AS country_active 
				FROM
					' . DB_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_regions R 
				LEFT JOIN  
					'  . DB_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries C 
				ON 
					C.id = R.country_id 
				WHERE 1 ';
				
		if ($regionIdFilter) {
			$sql .= " AND R.id = ". (int) $regionIdFilter. " ";
		}
		if ($countryCodeFilter) {
			$sql .= " AND R.country_id = '" . ze\escape::asciiInSQL($countryCodeFilter)  . "' ";
		} 
		if ($parentRegionFilter) {
			$sql .= " AND R.parent_id = " . (int) $parentRegionFilter . " " ;
		}
		if ($regionNameFilter) {
			$sql .=" AND R.name = '" . ze\escape::sql($regionNameFilter) . "' ";
		}
		if ($excludeIdsCSV){
			$sql .=" AND R.id NOT IN (" . ze\escape::in($excludeIdsCSV, 'numeric') . ") ";
		}
		$sql .= " ORDER BY R.name";
		
		$res=ze\sql::select($sql);
		while($row= ze\sql::fetchAssoc($res))
			$rv[$row['id']]=$row;
		
		return $rv;
	}
	
	public static function getRegionsByCountry($mode, $value = false) {
		switch ($mode) {
			case ze\dataset::LIST_MODE_INFO:
				return [
					'filter_label' => 'Country ID:',
					'can_filter' => true];
			case ze\dataset::LIST_MODE_LIST:
				return ze\row::getValues(ZENARIO_COUNTRY_MANAGER_PREFIX. 'country_manager_regions', 'name', [], 'name');
			case ze\dataset::LIST_MODE_FILTERED_LIST:
				return ze\row::getValues(ZENARIO_COUNTRY_MANAGER_PREFIX. 'country_manager_regions', 'name', ['country_id' => $value], 'name');
			case ze\dataset::LIST_MODE_VALUE:
				return ze\row::get(ZENARIO_COUNTRY_MANAGER_PREFIX. 'country_manager_regions', 'name', ['id' => $value]);
		}
	}
	
	public static function getCountryOfRegion($regionId,$fuse=10){
		$rv = [];
		if ($fuse--) {
			$region = ze\row::get(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_regions',['country_id','parent_id'],['id' => $regionId]);
			if ($region['country_id']) {
				$rv =  ze\row::get(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries',['id','english_name','active'],['id' => $region['country_id']]);
			} elseif ($region['parent_id']) {
				$rv = self::getCountryOfRegion($region['parent_id'],$fuse);
			}
		}
		return $rv;
	}
	
	public static function getRegionById($ID){
		$rv = [];
		$sql = 'SELECT
					id, 
					parent_id,
					country_id,
					name,
					active
				FROM
					' . DB_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_regions
				WHERE  
					id=' . (int)$ID ;
				
		$res=ze\sql::select($sql);
		if ($row= ze\sql::fetchAssoc($res)){
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
		$rv=[];
		$sql = 'SELECT id FROM ' . DB_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries WHERE 1 ' .self::getWhereCondForActiveField($activityFiler);
		$sql .= self::getOrderByClause();
		$res = ze\sql::select($sql);
		while($row = ze\sql::fetchAssoc($res))
			$rv[strtoupper($row['id'])]=strtoupper($row['id']);

		return $rv;
	}

	//code refiner - if set returns one dimensional country that code mathces $codeFilter, or zero if no  matching country code found
	public static function getCountryFullInfo($activityFilter='all',$codeFilter=''){
		if (!$codeFilter) {
			//PHP 8 compatibility: make sure the code filter is always a string, and not for example NULL.
			$codeFilter = '';
		}
		
		$rv=[];
		$sql = 'SELECT 
					id,
					english_name,
					active 
				FROM ' 
					. DB_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries 
				WHERE 1 ' 
					. self::getWhereCondForActiveField($activityFilter);
		if (ze\escape::sql(trim(strtoupper($codeFilter)))!='')
			$sql .= " 
					AND 
						id='" . ze\escape::asciiInSQL(trim(strtoupper($codeFilter))) . "'";
		$sql .= self::getOrderByClause();

		$res = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($res))
			$rv[strtoupper($row['id'])]=['english_name'=>$row['english_name'],'vlp_phrase'=>'_COUNTRY_NAME_' . strtoupper($row['id']),  'status'=> $row['active']];
		return $rv;
	}

	public static function getCountryActiveCountries(){
		$rv=[];
		$sql = 'SELECT
					id,
					english_name as name
				FROM '
				. DB_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries
				WHERE active=1 ORDER BY 2';
		return ze\sql::select($sql);
	}

	public static function getActiveCountries($mode, $value = false) {
		switch ($mode) {
			case ze\dataset::LIST_MODE_INFO:
				return ['can_filter' => false];
			case ze\dataset::LIST_MODE_LIST:
				return ze\row::getValues(ZENARIO_COUNTRY_MANAGER_PREFIX. 'country_manager_countries', 'english_name', ['active' => 1], 'english_name');
			case ze\dataset::LIST_MODE_VALUE:
				return ze\row::get(ZENARIO_COUNTRY_MANAGER_PREFIX. 'country_manager_countries', 'english_name', ['id' => $value]);
		}
	}
	
	public static function getCountryDialingCodes($mode, $value = false) {
		switch ($mode) {
			case ze\dataset::LIST_MODE_INFO:
				return ['can_filter' => false];
			case ze\dataset::LIST_MODE_LIST:
				$codes = [];
				$sql = '
					SELECT id, english_name, phonecode
					FROM ' . DB_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries
					WHERE active = 1
					AND phonecode != 0
					ORDER BY english_name';
				$result = ze\sql::select($sql);
				while ($row = ze\sql::fetchAssoc($result)) {
					$codes[$row['id']] = static::formatCountryDialingCode($row['english_name'], $row['phonecode']);
				}
				return $codes;
			case ze\dataset::LIST_MODE_VALUE:
				return [];
		}
	}
	
	public static function getCountryShortDialingCodes($mode, $value = false) {
		switch ($mode) {
			case ze\dataset::LIST_MODE_INFO:
				return ['can_filter' => false];
			case ze\dataset::LIST_MODE_LIST:
				$codes = [];
				$sql = '
					SELECT id, english_name, phonecode
					FROM ' . DB_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries
					WHERE active = 1
					AND phonecode != 0
					ORDER BY english_name';
				$result = ze\sql::select($sql);
				while ($row = ze\sql::fetchAssoc($result)) {
					$codes[$row['id']] = static::formatCountryDialingCode($row['english_name'], $row['phonecode'], true);
				}
				return $codes;
			case ze\dataset::LIST_MODE_VALUE:
				return [];
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
		if ($value = ze::ifNull($mergeFields[$attributes['name'] ?? false], $attributes['value'] ?? false)) {
			$out = ze\row::get(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries', 'english_name', ['id' => $value]);
		}
		
		if (ze\ring::engToBoolean($attributes['escape'] ?? false)) {
			return htmlspecialchars($out);
		} else {
			return $out;
		}
	}

	public static function getCountryName($code, $languageId = false){
		if (!$code) {
			return '';
		} else {
			return ze\lang::phrase('_COUNTRY_NAME_'. strtoupper($code), false, 'zenario_country_manager', $languageId);
		}
	}

	public static function getEnglishCountryName($code){
		if ($code && count($rv = self::getCountryFullInfo('all',$code)))
			return  $rv[strtoupper($code)]['english_name'];
		else
			return '';
	}
	
	public static function getRegionParents($parentId){
		$rv = [];
		$fuse = 100;
		$i = 0;
		while($fuse-- && $newParentId = ze\row::get(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions", 'parent_id', ['id' => $parentId]) ) {
			$rv[$i++] = $parentId = $newParentId;
		}
		if ($newParentId = ze\row::get(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions", 'country_id', ['id' => $parentId]) ) {
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
						//matches an exclude
						return false;
					}
				}
				//No matches
				return true;
			} else {
				//No matches as user country_id not set!
				return true;
			}
		} else {
			//return true if user country is in include list
			if(!empty($_SESSION['country_id'])) {
				foreach ($countryIdsArray as $countryId) {
					if (strtoupper($countryId) == $_SESSION['country_id']) {
						//matches an include
						return true;
					}
				}
				//No matches
				return false;
			} else {
				//No matches as user country_id not set!
				return false;
			}
		}
	}

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (ze::$isTwig) return;
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if (ze::$isTwig) return;
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}

	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (ze::$isTwig) return;
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}


	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if (ze::$isTwig) return;
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}


	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if (ze::$isTwig) return;
		require ze::funIncPath(__FILE__, __FUNCTION__);
 	}
 	
 	
	
	public static function requestVarMergeField($name) {
		switch ($name) {
			case 'name':
				return self::getCountryName(ze::$vars['countryId']);
			}
	}
	public static function requestVarDisplayName($name) {
		switch ($name) {
			case 'name':
				return 'Country name';
		}
	}
}

