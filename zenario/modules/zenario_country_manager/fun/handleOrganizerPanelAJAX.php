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

		if (checkPriv("_PRIV_MANAGE_COUNTRY")){
			foreach (explode(',',$ids) as $id) {
				if (post('action') == 'activate_country') {
					updateRow(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_countries", array('active' => 1), array('id' => $id));
				}
				if (post('action') == 'suspend_country') {
					updateRow(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_countries", array('active' => 0), array('id' => $id));
				}
				if (post('action') == 'delete_country') {
					deleteRow("visitor_phrases", 
								array(
										'module_class_name' => 'zenario_country_manager',
										'code' => '_COUNTRY_NAME_' . $id
									) 
							);
					deleteRow(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_countries", array('id' => $id));					

					$sql = "DELETE FROM "
								. DB_NAME_PREFIX . "visitor_phrases
							WHERE
									module_class_name = 'zenario_country_manager'
								AND code IN (SELECT 
												name 
											FROM " 
												. DB_NAME_PREFIX . ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions 
											WHERE
												country_id = '" . sqlEscape($id) . "'
											)";
					sqlQuery($sql);
					deleteRow(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions", array('country_id' => $id));
					
					sendSignal('eventCountryDeleted', array("countryId" => $id));
				}

				if (post('action') == 'delete_region') {
					$name = getRow(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions", "name", array('id' => $id));
					deleteRow("visitor_phrases", 
								array(
										'module_class_name' => 'zenario_country_manager',
										'code' => $name
									) 
							);
					deleteRow(ZENARIO_COUNTRY_MANAGER_PREFIX . "country_manager_regions", array('id' => $id));
					sendSignal('eventRegionDeleted', array("regionId" => $id));
				}
			}
		} else {
			echo adminPhrase("You have no permission to manipulate Countries or Regions.");
		}

?>