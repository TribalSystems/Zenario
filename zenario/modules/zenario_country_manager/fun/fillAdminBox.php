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

switch($path) {
	case 'zenario_country_manager__country':
		if ($_GET['refinerId'] ?? false){
			$box['key']['id'] = $_GET['refinerId'] ?? false;
		}
		if ($box['key']['id'] ?? false) {
			$countryName = ze\row::get(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries', 'english_name', ['id' => ($box['key']['id'] ?? false)]);
			$box['title'] = ze\admin::phrase('Editing the country "[[country_name]]"', ['country_name' => $countryName]);

			$box['tabs']['details']['fields']['code']['value'] = $box['key']['id'] ?? false;
			$box['tabs']['details']['fields']['code']['readonly'] = true;
			$box['tabs']['details']['fields']['name']['value'] = $countryName;
		}
		break;
	case 'zenario_country_manager__region':
		if (!ze::setting('zenario_country_manager__region_type_management')) {
			$box['tabs']['details']['fields']['region_type']['hidden'] = true;
		}
		if ($_GET['id'] ?? false){
			$box['key']['id'] = $_GET['id'] ?? false;
		}
		if (($_GET['refinerName'] ?? false)=='parent_id') {
			$box['key']['parent_id'] = $_GET['refinerId'] ?? false;
			if ($box['key']['id'] ?? false) {
				$region = ze\row::get(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_regions', ['name','region_type'], ['id' => ($box['key']['id'] ?? false)]);
				$box['title'] = ze\admin::phrase('Editing the region "[[region_name]]"', ['region_name' => $region['name']]);
				$box['tabs']['details']['fields']['name']['value'] = $region['name'];
				$box['tabs']['details']['fields']['update_phrase']['hidden'] = false;
				if (ze::setting('zenario_country_manager__region_type_management')) {
					$box['tabs']['details']['fields']['region_type']['value'] = $region['region_type'];
				}
			} elseif ($box['key']['parent_id'] ?? false) {
				$parentRegion = ze\row::get(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_regions', ['name'], ['id' => ($box['key']['parent_id'] ?? false)]);
				$box['title'] = ze\admin::phrase('Creating a sub-region of "[[parent_region_name]]"', 
												['parent_region_name' => $parentRegion['name']]);
				$box['tabs']['details']['fields']['update_phrase']['hidden'] = true;
			}
		} elseif ($_GET['refiner__country_code_filter'] ?? false) {
			$box['key']['country_id'] = $_GET['refiner__country_code_filter'] ?? false;
			$countryName = ze\row::get(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries', 'english_name', ['id' => ($box['key']['country_id'] ?? false)]);
			if ($box['key']['id'] ?? false) {
				$region = ze\row::get(ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_regions', ['name', 'region_type'], ['id' => ($box['key']['id'] ?? false)]);
				$box['title'] = ze\admin::phrase('Editing the region "[[region_name]]" in "[[country_name]]"', 
												['region_name' => $region['name'], 'country_name' => $countryName]);
				$box['tabs']['details']['fields']['name']['value'] = $region['name'];
				$box['tabs']['details']['fields']['update_phrase']['hidden'] = false;
				if (ze::setting('zenario_country_manager__region_type_management')) {
					$box['tabs']['details']['fields']['region_type']['value'] = $region['region_type'];
				}
			} else {
				$box['title'] = ze\admin::phrase('Creating a region within "[[country_name]]"', 
												['country_name' => $countryName]);
				$box['tabs']['details']['fields']['update_phrase']['hidden'] = true;
			}
		}
		break;
}