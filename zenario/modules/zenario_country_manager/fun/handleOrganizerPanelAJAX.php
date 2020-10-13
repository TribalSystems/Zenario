<?php
/*
 * Copyright (c) 2020, Tribal Limited
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

		if (ze\priv::check("_PRIV_MANAGE_COUNTRY")){
			foreach (explode(',',$ids) as $id) {
				if (($_POST['action'] ?? false) == 'activate_country') {
					ze\row::update(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_countries", ['active' => 1], ['id' => $id]);
				}
				if (($_POST['action'] ?? false) == 'suspend_country') {
					ze\row::update(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_countries", ['active' => 0], ['id' => $id]);
				}
				if (($_POST['action'] ?? false) == 'delete_country') {
					
					//Check if the country has regions. If yes, do not delete the country.
					$result = ze\row::getArray(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions", ["id", "name"], ["country_id" => $id]);
					if ($result) {
						echo ze\admin::phrase("This country has " . count($result) . " regions. Please delete them first.");
					} else {
						ze\row::delete("visitor_phrases", 
									[
											'module_class_name' => 'zenario_country_manager',
											'code' => '_COUNTRY_NAME_' . $id
										] 
								);
						ze\row::delete(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_countries", ['id' => $id]);	
						ze\row::delete('user_country_link', ['country_id' => $id]);				

						$sql = "DELETE FROM "
									. DB_PREFIX . "visitor_phrases
								WHERE
										module_class_name = 'zenario_country_manager'
									AND code IN (SELECT 
													name 
												FROM " 
													. DB_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions 
												WHERE
													country_id = '" . ze\escape::sql($id) . "'
												)";
						ze\sql::update($sql);
						ze\row::delete(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions", ['country_id' => $id]);
					
						ze\module::sendSignal('eventCountryDeleted', ["countryId" => $id]);
					}
				}

				if (($_POST['action'] ?? false) == 'delete_region') {
					
					//Check if the region has subregions. If yes, do not delete the region.
					$result = ze\row::getArray(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions", ["id", "name"], ["parent_id" => $id]);
					
					if ($result) {
						echo ze\admin::phrase("This region has " . count($result) . " subregions. Please delete them first.");
					} else {
						$name = ze\row::get(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions", "name", ['id' => $id]);
						ze\row::delete("visitor_phrases", 
									[
											'module_class_name' => 'zenario_country_manager',
											'code' => $name
										] 
								);
						ze\row::delete(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions", ['id' => $id]);
						ze\module::sendSignal('eventRegionDeleted', ["regionId" => $id]);
					}
				}
			}
		} else {
			echo ze\admin::phrase("You have no permission to manipulate Countries or Regions.");
		}

?>