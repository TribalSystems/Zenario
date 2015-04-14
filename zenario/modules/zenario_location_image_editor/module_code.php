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

class zenario_location_image_editor extends module_base_class {
	
	var $emailValidationError=false;
	var $uploadError = "";
	var $roleConfigurationError="";
	var $locationImage = array();


	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = true);
		
		
    	if (inc("zenario_location_manager") && inc("zenario_organization_manager")) {
			if ($userId = session('extranetUserID')) {
				$locationId = 0;
				foreach (zenario_organization_manager::getUserRoles($userId) as $role) {
					if ($this->setting('role') == $role['role_id'] ) {
						$locationId = $role['location_id'];
						break;
					}
				}

				if ($locationId && post('upload_image')) {
					$oldImageId = getRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_images', 'image_id', array('location_id' => $locationId, 'sticky_flag' => 1 ) );
					if (!is_array($rv = zenario_location_manager::handleMediaUpload('new_image', setting('max_user_image_filesize'), $locationId))) {
						$image_id = $rv;
						zenario_location_manager::makeLocationImageSticky($locationId, $image_id);
						deleteRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_images', array('location_id' => $locationId, 'image_id' => $oldImageId ) );	
					} else {
						$this->uploadError = $rv;
					}
				}
				
				if ($locationId && post('remove_image') && ($imageId = ifNull(post('image_id'), post('image_checksum')))) {
					deleteRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_images', array('location_id' => $locationId, 'image_id' => $imageId));
				}

				if ($locationId) {						
					$this->locationImage = getRow(ZENARIO_LOCATION_MANAGER_PREFIX . 'location_images', array('location_id', 'filename', 'image_id'), array('location_id' => $locationId, 'sticky_flag' => 1));
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
			if (session('admin_userid')) {
				echo $this->roleConfigurationError;
			}
    	} else {
			$this->modeViewImage();
    	}
	}	
	
    function modeViewImage() {
    	$mergeFields = array();    	
    	$sections = array();    	
    	
    	if ($this->locationImage) {
			$sections['Remove_Image'] = true;
			$sections['Location_Image'] = true;
			$img = 'location_id='. $this->locationImage['location_id'] . '&filename='. rawurlencode($this->locationImage['filename']) . "&sticky=true";
			$mergeFields['Location_image'] =
			$mergeFields['Location_image_width'] =
			$mergeFields['Location_image_height'] = '';
			
			imageLink($mergeFields['Location_image_width'], $mergeFields['Location_image_height'], $mergeFields['Location_image'], $this->locationImage['image_id'], 260);
			
			$mergeFields['Location_image_id'] = $mergeFields['Location_image_checksum'] = $this->locationImage['image_id'];
			$mergeFields['Replace_or_upload'] = $this->phrase("_REPLACE_IMAGE");
			if ($this->uploadError){
				foreach ($this->uploadError as $error) {
					$sections['Errors'][] = array('Error' => $error);
				}
			}
    	} else {
			$sections['Generic_Image'] = true;
			$mergeFields['Module_dir'] = moduleDir('zenario_location_image_editor');
			$mergeFields['Replace_or_upload'] = $this->phrase("_UPLOAD_IMAGE");
			if ($this->uploadError){
				foreach ($this->uploadError as $error) {
					$sections['Errors'][] = array('Error' => $error);
				}
			}
    	}
    	
    	echo $this->openForm('', 'enctype="multipart/form-data"');
			$this->framework('Outer', $mergeFields, $sections);
    	echo $this->closeForm();
    }
}

