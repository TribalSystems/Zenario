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

class zenario_location_editor extends module_base_class {
	
	var $emailValidationError=false;
	var $roleConfigurationError="";
	var $locationDetails = array();
	var $mergeArray = array();
	var $sectionArray = array();


	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = true);
		
		$this->locationDetails=array();
		
		if (post('update_location') && ((int) post('location_id')) && session('extranetUserID') 
				&& inc("zenario_organization_manager") && zenario_organization_manager::getUserLocationRoles(session('extranetUserID'), (int) post('location_id') ) ) {

			if (!($this->emailValidationError = post('Email') && !validateEmailAddress(post('Email')))) {
				updateRow(ZENARIO_LOCATION_MANAGER_PREFIX . "locations", 
						array(
							'description' => post('Description'), 
							'address1' => post('Address1'), 
							'address2' => post('Address2'),
							'locality' => post('Locality'),
							'city' => post('City'),
							'state' => post('State'),
							'postcode' => post('Postcode'),
							'country_id' => post('Country')
							)
						,
						array(
							'id' => (int) post('location_id')
							)
						);
			}
		}
		
    	if (inc("zenario_location_manager") && inc("zenario_organization_manager")) {
			if ($userId = session('extranetUserID')) {
				$locationId = 0;
				foreach (zenario_organization_manager::getUserRoles($userId) as $role) {
					if ($this->setting('role') == $role['role_id'] ) {
						$locationId = $role['location_id'];
						break;
					}
				}
				if ($locationId) {						
					$this->locationDetails = zenario_location_manager::getlocationDetails($locationId);
					$this->locationDetails['id'] = $locationId;
				} else {
					$userDetails = getUserDetails($userId);
					$this->roleConfigurationError =  $this->phrase('_ERROR_NO_ROLES_GRANTED', array('screen_name' => arrayKey($userDetails,'screen_name')));
				}
			} else {
				$this->roleConfigurationError = adminPhrase("You must be logged in as an Extranet User.");
			}
		}		
		
		return true;
	}
	
	function showSlot() {
		if ($this->roleConfigurationError) {
			$this->modeError();
		} elseif (get('edit_location') || $this->emailValidationError) {
			$this->modeEditLocation();
		} else {
			$this->modeViewLocation();			
		}
	}
	
	function modeError() {
		if (session('admin_userid')) {
			echo $this->roleConfigurationError;
		}
	}
	
	function modeViewLocation() {
		if ($this->locationDetails){
		
			if (!empty($this->locationDetails['description'])) {
				$this->mergeArray['Description'] = htmlspecialchars($this->locationDetails['description']);
			}
	
			if (!empty($this->locationDetails['address1'])) {
				$this->sectionArray['Address1'] = true;
				$this->mergeArray['Address1'] = htmlspecialchars($this->locationDetails['address1']);
			}
	
			if (!empty($this->locationDetails['address2'])) {
				$this->sectionArray['Address2'] = true;
				$this->mergeArray['Address2'] = htmlspecialchars($this->locationDetails['address2']);
			}
	
			if (!empty($this->locationDetails['locality'])) {
				$this->sectionArray['Locality'] = true;
				$this->mergeArray['Locality'] = htmlspecialchars($this->locationDetails['locality']);
			}
	
			if (!empty($this->locationDetails['city'])) {
				$this->sectionArray['City'] = true;
				$this->mergeArray['City'] = htmlspecialchars($this->locationDetails['city']);
			}
	
			if (!empty($this->locationDetails['state'])) {
				$this->sectionArray['State'] = true;
				$this->mergeArray['State'] = htmlspecialchars($this->locationDetails['state']);
			}
	
			if (!empty($this->locationDetails['postcode'])) {
				$this->sectionArray['Postcode'] = true;
				$this->mergeArray['Postcode'] = htmlspecialchars($this->locationDetails['postcode']);
			}
	
			if (!empty($this->locationDetails['country_id'])) {
				if (inc("zenario_country_manager")) {
					if ($country = zenario_country_manager::getCountryNamesInCurrentVisitorLanguage("active",$this->locationDetails['country_id'])) {
						$this->sectionArray['Country'] = true;
						$this->mergeArray['Country'] = arrayKey($country,"COUNTRY_" . $this->locationDetails['country_id']);
					}
				}
			}
	
			if ((!empty($this->locationDetails['equiv_id'])  && !empty($this->locationDetails['content_type']))) {
				$this->sectionArray['Contact'] = true;
			}
	
			if (!empty($this->locationDetails['equiv_id']) && !empty($this->locationDetails['content_type'])) {
				$this->sectionArray['More Info'] = true;
				$cID  = $this->locationDetails['equiv_id'];
				$cType = $this->locationDetails['content_type'];
				langEquivalentItem($cID, $cType);
				$this->mergeArray['More Info'] = $this->linkToItem($cID, $cType, true);
			}
	
			if (!empty($this->locationDetails['country_id']) && !empty($this->locationDetails['region_id'])) {
				if (inc("zenario_country_manager")) {
					if ($region = zenario_country_manager::getRegionNamesInCurrentVisitorLanguage("active",$this->locationDetails['country_id'],$this->locationDetails['region_id'])) {
						foreach ($region as $key => $value) {
							$this->sectionArray['Region'] = true;
							$this->mergeArray['Region'] = zenario_country_manager::adminPhrase(session('user_lang'),$value);
						}
					}
				}
			}
			
			$this->mergeArray['Edit_Location_Link'] = $this->refreshPluginSlotAnchor('edit_location=1');
			$this->framework('View',$this->mergeArray,$this->sectionArray);

		}
	}

	function modeEditLocation() {
	
		if ($this->emailValidationError) {
			$this->mergeArray['Error'] = $this->phrase('_ERROR_INVALID_EMAIL_ADDRESS');
		}

		$this->mergeArray['Back_Link'] = $this->refreshPluginSlotAnchor('');

		if ($this->locationDetails){
			$this->mergeArray['location_id'] = htmlspecialchars($this->locationDetails['id']);
			$this->mergeArray['Description'] = htmlspecialchars($this->locationDetails['description']);
			$this->mergeArray['Address1'] = htmlspecialchars($this->locationDetails['address1']);
			$this->mergeArray['Address2'] = htmlspecialchars($this->locationDetails['address2']);
			$this->mergeArray['Locality'] = htmlspecialchars($this->locationDetails['locality']);
			$this->mergeArray['City'] = htmlspecialchars($this->locationDetails['city']);
			$this->mergeArray['State'] = htmlspecialchars($this->locationDetails['state']);
			$this->mergeArray['Postcode'] = htmlspecialchars($this->locationDetails['postcode']);
			$this->mergeArray['Country'] = htmlspecialchars($this->locationDetails['country_id']);
		}
		
		echo $this->openForm();
			$this->framework('Edit',$this->mergeArray);
		echo $this->closeForm();

	}
}

