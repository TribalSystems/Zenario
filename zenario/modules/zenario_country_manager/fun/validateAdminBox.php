<?php
/*
 * Copyright (c) 2014, Tribal Limited
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

	switch($path) {
		case 'zenario_country_manager__country':
			if (!arrayKey($values,'details/code')) {
				$box['tabs']['details']['errors'][] = adminPhrase("Error. Please enter a Code");
				break;
			}
			if (!arrayKey($values,'details/name')) {
				$box['tabs']['details']['errors'][] = adminPhrase("Error. Please enter a Name");
				break;
			}
			if (!arrayKey($box,'key','id') && checkRowExists(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries', array('id' => arrayKey($values,'details/code')))) {
				$box['tabs']['details']['errors'][] = adminPhrase("Error. Country code must be unique.");
				break;
			}
			$countries = getRows(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries', 
									array('id','english_name'), 
										array('english_name' => arrayKey($values,'details/name')));
			while($country = sqlFetchAssoc($countries)) {
				if (arrayKey($box,'key','id') != arrayKey($country,'id'))  {
					$box['tabs']['details']['errors'][] = adminPhrase("Error. Country name must be unique.");
					break;
				}
			}
			break;
		case 'zenario_country_manager__region':
			if (!arrayKey($values,'details/code')) {
				$box['tabs']['details']['errors'][] = adminPhrase("Error. Please enter a Code");
				break;
			}
			if (!arrayKey($values,'details/name')) {
				$box['tabs']['details']['errors'][] = adminPhrase("Error. Please enter a Name");
				break;
			}
			$parentRegionId = arrayKey($box,'key','parent_id');
			$countryCode = arrayKey($box,'key','country_id');
			$regionName = arrayKey($values,'details/name');
			$regionCode = arrayKey($values,'details/code');
			$regionId = arrayKey($box,'key','id');

			if ($parentRegionId){
				if ($regions = self::getRegions('all','','',false,$parentRegionId,$regionName,$regionId)) {
					$box['tabs']['details']['errors'][] =  
						adminPhrase('Error. The Sub-Region "[[subregion_name]]" already exists in the Region "[[region_name]]"',
							array('subregion_name' => $regionName, 'region_name' => self::getEnglishRegionName($parentRegionId)));
					break;
				} 
				if ($regionCode && $regions = self::getRegions('all','',$regionCode,false,$parentRegionId,'',$regionId)) {
					$box['tabs']['details']['errors'][] = 
						adminPhrase('Error. The Sub-Region with code "[[subregion_code]]" already exists in the Region "[[region_name]]"',
								array('subregion_code' => $regionCode, 'region_name' =>  self::getEnglishRegionName($parentRegionId)) );
					break;
				}
			} elseif ($countryCode) {
				if ($regions = self::getRegions('all',$countryCode,'',false,0,$regionName,$regionId)) {
					$box['tabs']['details']['errors'][] = 
						adminPhrase('Error. The Region "[[region_name]]" already exists in the Country "[[country_name]]"',
							array('region_name' => $regionName, 'country_name' => self::getEnglishCountryName($countryCode)));
					break;
				}
				if ($regionCode && $regions = self::getRegions('all',$countryCode,$regionCode,false,0,'',$regionId)){
					$box['tabs']['details']['errors'][] = 
						adminPhrase('Error. The Region with code "[[region_code]]" already exists in the Country "[[country_name]]"',
							array('region_code' => $regionCode, 'country_name' => self::getEnglishCountryName($countryCode))
						);
					break;
				} 
			} else {
				return 'Error. No parent Country or Region was set.';
			}

			
			break;

	}

?>