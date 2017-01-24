<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

class zenario_extranet_user_profile_view extends module_base_class {
	var $mergeFields = array();
	var $subSections = array();

	public function init() {
		$userId = false;
	
		if (!$userId = $this->setting("user")) {
			$userId = self::getUserIdFromDescriptivePage($this->cID,$this->cType);
		}
		
		if ($userId) {
			$userDetails = getUserDetails($userId);
			
			$this->mergeFields['Title'] = htmlspecialchars($userDetails['salutation']);
			$this->mergeFields['First_Name'] = htmlspecialchars($userDetails['first_name']);
			$this->mergeFields['Last_Name'] = htmlspecialchars($userDetails['last_name']);
			
			$this->subSections['Bus_Phone'] = issetArrayKey($userDetails,"bus_phone");
			$this->mergeFields['Bus_Phone'] = htmlspecialchars($userDetails['bus_phone']);
			
			$this->subSections['Mobile'] = issetArrayKey($userDetails,"mobile");			
			$this->mergeFields['Mobile'] = htmlspecialchars($userDetails['mobile']);

			$this->subSections['Fax'] = issetArrayKey($userDetails,"fax");			
			$this->mergeFields['Fax'] = htmlspecialchars($userDetails['fax']);
			
			$this->subSections['Email'] = issetArrayKey($userDetails,"email");			
			$this->mergeFields['Email'] = htmlspecialchars($userDetails['email']);
			
			$this->subSections['Website'] = issetArrayKey($userDetails,"website");						
			$this->mergeFields['Website'] = htmlspecialchars($userDetails['website']);
			
			$this->subSections['Bus_Address'] = (
													issetArrayKey($userDetails,"bus_address1") ||
													issetArrayKey($userDetails,"bus_address2") ||
													issetArrayKey($userDetails,"bus_address3") ||
													issetArrayKey($userDetails,"bus_town") ||
													issetArrayKey($userDetails,"bus_state") ||
													issetArrayKey($userDetails,"bus_postcode") ||
													issetArrayKey($userDetails,"bus_country_id")
												);
												
			$this->subSections['Bus_Address1'] = issetArrayKey($userDetails,"bus_address1");			
			$this->mergeFields['Bus_Address1'] = htmlspecialchars($userDetails['bus_address1']);
			
			$this->subSections['Bus_Address2'] = issetArrayKey($userDetails,"bus_address2");			
			$this->mergeFields['Bus_Address2'] = htmlspecialchars($userDetails['bus_address2']);
			
			$this->subSections['Bus_Address3'] = issetArrayKey($userDetails,"bus_address3");			
			$this->mergeFields['Bus_Address3'] = htmlspecialchars($userDetails['bus_address3']);
			
			$this->subSections['Bus_Town'] = issetArrayKey($userDetails,"bus_town");			
			$this->mergeFields['Bus_Town'] = htmlspecialchars($userDetails['bus_town']);
			
			$this->subSections['Bus_State'] = issetArrayKey($userDetails,"bus_state");			
			$this->mergeFields['Bus_State'] = htmlspecialchars($userDetails['bus_state']);
			
			$this->subSections['Bus_Postcode'] = issetArrayKey($userDetails,"bus_postcode");			
			$this->mergeFields['Bus_Postcode'] = htmlspecialchars($userDetails['bus_postcode']);

			$this->subSections['Bus_Country'] = issetArrayKey($userDetails,"bus_country_id");			
			$this->mergeFields['Bus_Country'] = zenario_country_manager::adminPhrase(cms_core::$langId,"_COUNTRY_NAME_" . $userDetails['bus_country_id']);
			
			// Make custom dataset fields available for custom frameworks
			$C = array();
			foreach ($userDetails as $col => $value) {
				$cName = $col;
				$cValue = $value;
				$C[$cName][] = htmlspecialchars($cValue);
			}
			foreach ($C as $K=>$V) {
				$this->subSections[$K] = true;
				$this->mergeFields[$K] = implode(', ', $V);
			}

			if (inc('zenario_comments')) {
				$noOfMessages = zenario_comments::userPostCount($userId);
				$lastPostDaysAgo = zenario_comments::userLatestActivityDays($userId);

				if ($noOfMessages && $lastPostDaysAgo) { 
					$this->subSections['Forum_posts'] = true;		
					$this->mergeFields['Number_of_posts_last_post_number_days_ago'] = 
								$this->phrase('_NUMBER_OF_POST' . ($noOfMessages>1?'S':'') . '_LAST_POST_NUMBER_DAY' . ($lastPostDaysAgo>1?'S':'') . '_AGO', 
												array(
													'number_of_posts' => $noOfMessages,
													'last_post_days_ago' => $lastPostDaysAgo,
													)
											);
				}
			}


			$url = $width = $height = false;
			if (($imageId = getRow('users', 'image_id', $userId))
			 && imageLink($width, $height, $url, $imageId, ifNull((int) $this->setting('max_user_image_width'), 120), ifNull((int) $this->setting('max_user_image_height'), 120))) {
				$this->subSections['User_Image'] = true;
				$this->mergeFields['Image_Src'] = htmlspecialchars($url);
				$this->mergeFields['Image_Width'] = $width;
				$this->mergeFields['Image_Height'] = $height;
			}
		}
		
		return true;
	}

	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case "plugin_settings":
				if (!$values['first_tab/max_user_image_width']) {
					$box['tabs']['first_tab']['errors'][] = adminPhrase('Please enter a maximum image width.');
				}

				if (!$values['first_tab/max_user_image_height']) {
					$box['tabs']['first_tab']['errors'][] = adminPhrase('Please enter a maximum image height.');
				}
			
				break;
		}
	}

	public function showSlot() {
		$this->framework("Outer",$this->mergeFields,$this->subSections);
	}
	
	public static function getUserIdFromDescriptivePage($cID, $cType) {
		if ($cID && $cType) {
			if ($equivId = equivId($cID,$cType)) {
				return getRow("users","id",array("equiv_id" => $equivId, "content_type" => $cType));
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}

?>