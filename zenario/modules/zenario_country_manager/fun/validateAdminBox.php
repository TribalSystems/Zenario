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

	switch($path) {
		case 'zenario_country_manager__country':
			if (!($values['details/code'] ?? false)) {
				$box['tabs']['details']['errors'][] = ze\admin::phrase("Error. Please enter a Code");
				break;
			}
			if (!($values['details/name'] ?? false)) {
				$box['tabs']['details']['errors'][] = ze\admin::phrase("Error. Please enter a Name");
				break;
			}
			if (!($box['key']['id'] ?? false) && ze\row::exists(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries', ['id' => ($values['details/code'] ?? false)])) {
				$box['tabs']['details']['errors'][] = ze\admin::phrase("Error. Country code must be unique.");
				break;
			}
			$countries = ze\row::query(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries', 
									['id','english_name'], 
										['english_name' => ($values['details/name'] ?? false)]);
			while($country = ze\sql::fetchAssoc($countries)) {
				if (($box['key']['id'] ?? false) != $country['id'] ?? false)  {
					$box['tabs']['details']['errors'][] = ze\admin::phrase("Error. Country name must be unique.");
					break;
				}
			}
			break;
		case 'zenario_country_manager__region':
			if (!($values['details/name'] ?? false)) {
				$box['tabs']['details']['errors'][] = ze\admin::phrase("Error. Please enter a Name");
				break;
			}
			if (!($values['details/region_type'] ?? false) && ze::setting('zenario_country_manager__region_type_management')) {
				$box['tabs']['details']['errors'][] = ze\admin::phrase("Error. Please select region type");
				break;
			}
			$parentRegionId = $box['key']['parent_id'] ?? false;
			$countryCode = $box['key']['country_id'] ?? false;
			$regionName = ($values['details/name'] ?? false);
			$regionId = $box['key']['id'] ?? false;

			if ($parentRegionId){
				if ($regions = self::getRegions('all','','',false,$parentRegionId,$regionName,$regionId)) {
					$box['tabs']['details']['errors'][] =  
						ze\admin::phrase('Error. The Sub-Region "[[subregion_name]]" already exists in the Region "[[region_name]]"',
							['subregion_name' => $regionName, 'region_name' => self::getEnglishRegionName($parentRegionId)]);
					break;
				} 
			} elseif ($countryCode) {
				if ($regions = self::getRegions('all',$countryCode,'',false,0,$regionName,$regionId)) {
					$box['tabs']['details']['errors'][] = 
						ze\admin::phrase('Error. The Region "[[region_name]]" already exists in the Country "[[country_name]]"',
							['region_name' => $regionName, 'country_name' => self::getEnglishCountryName($countryCode)]);
					break;
				}
			} else {
				return 'Error. No parent Country or Region was set.';
			}

			
			break;

	}

?>