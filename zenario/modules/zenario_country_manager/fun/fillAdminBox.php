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
			if (get('refinerId')){
				$box['key']['id'] = get('refinerId');
			}
			if (arrayKey($box,'key','id')) {
				$countryName = getRow(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries', 'english_name', array('id' => arrayKey($box,'key','id')));
				$box['title'] = adminPhrase('Renaming the Country "[[country_name]]"', array('country_name' => $countryName));

				$box['tabs']['details']['fields']['code']['value'] = arrayKey($box,'key','id');
				$box['tabs']['details']['fields']['code']['read_only'] = true;
				$box['tabs']['details']['fields']['update_phrase']['hidden'] = false;
				$box['tabs']['details']['fields']['name']['value'] = $countryName;
				$box['tabs']['details']['edit_mode']['on'] = false;
				$box['tabs']['details']['edit_mode']['always_on'] = false;
			} else {
				$box['tabs']['details']['edit_mode']['on'] = true;
				$box['tabs']['details']['edit_mode']['always_on'] = true;
				$box['tabs']['details']['fields']['update_phrase']['hidden'] = true;
			}
			break;
		case 'zenario_country_manager__region':
			if (get('id')){
				$box['key']['id'] = get('id');
			}
			if (get('refinerName')=='parent_id') {
				$box['key']['parent_id'] = get('refinerId');
				if (arrayKey($box,'key','id')) {
					$region = getRow(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_regions', array('name','code'), array('id' => arrayKey($box,'key','id')));
					$box['title'] = adminPhrase('Renaming the Region "[[region_name]]"', array('region_name' => $region['name']));
					$box['tabs']['details']['fields']['name']['value'] = $region['name'];
					$box['tabs']['details']['fields']['update_phrase']['hidden'] = false;
					$box['tabs']['details']['fields']['code']['value'] = $region['code'];
					$box['tabs']['details']['edit_mode']['on'] = false;
					$box['tabs']['details']['edit_mode']['always_on'] = false;
				} elseif (arrayKey($box,'key','parent_id')) {
					$parentRegion = getRow(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_regions', array('name','code'), array('id' => arrayKey($box,'key','parent_id')));
					$box['title'] = adminPhrase('Creating a Sub-region of the Region "[[parent_region_name]]"', 
													array('parent_region_name' => $parentRegion['name']));
					$box['tabs']['details']['fields']['update_phrase']['hidden'] = true;
					$box['tabs']['details']['edit_mode']['on'] = true;
					$box['tabs']['details']['edit_mode']['always_on'] = true;
				}
			} elseif (get('refiner__country_code_filter')) {
				$box['key']['country_id'] = get('refiner__country_code_filter');
				$countryName = getRow(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries', 'english_name', array('id' => arrayKey($box,'key','country_id')));
				if (arrayKey($box,'key','id')) {
					$region = getRow(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_regions', array('name','code'), array('id' => arrayKey($box,'key','id')));
					$box['title'] = adminPhrase('Renaming the Region "[[region_name]]" in the Country "[[country_name]]"', 
													array('region_name' => $region['name'], 'country_name' => $countryName));
					$box['tabs']['details']['fields']['name']['value'] = $region['name'];
					$box['tabs']['details']['fields']['update_phrase']['hidden'] = false;
					$box['tabs']['details']['fields']['code']['value'] = $region['code'];
					$box['tabs']['details']['edit_mode']['on'] = false;
					$box['tabs']['details']['edit_mode']['always_on'] = false;
				} else {
					$box['title'] = adminPhrase('Creating a Region in the Country "[[country_name]]"', 
													array('country_name' => $countryName));
					$box['tabs']['details']['fields']['update_phrase']['hidden'] = true;
					$box['tabs']['details']['edit_mode']['on'] = true;
					$box['tabs']['details']['edit_mode']['always_on'] = true;
				}
			}
			break;
	}


?>