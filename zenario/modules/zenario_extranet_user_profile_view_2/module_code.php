<?php
/*
 * Copyright (c) 2024, Tribal Limited
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

class zenario_extranet_user_profile_view_2 extends ze\moduleBaseClass {
	private $data = [];
	
	public function init() {
		$userId = false;
		
		// Get user to display
		switch ($this->setting('plugin_mode')) {
			case 'single_user':
				$userId = $this->setting('user');
				break;
			case 'logged_in':
				$userId = ze\user::id();
				break;
			case 'descriptive_page':
				$userId = self::getUserIdFromDescriptivePage($this->cID,$this->cType);
				break;
		}
		if ($userId) {
			$userDetails = ze\user::details($userId);
			foreach ($userDetails as $col => $value) {
				$this->data[$col] = htmlspecialchars($value);
			}
			
			$url = $width = $height = false;
			if (($imageId = ze\row::get('users', 'image_id', $userId))
			 && ze\file::imageLink($width, $height, $url, $imageId, (int) $this->setting('max_user_image_width') ?: 120, (int) $this->setting('max_user_image_height') ?: 120)) {
				$this->data['User_Image'] = true;
				$this->data['Image_Src'] = htmlspecialchars($url);
				$this->data['Image_Width'] = $width;
				$this->data['Image_Height'] = $height;
			}
		}
		
		return true;
	}

	public function showSlot() {
		$this->twigFramework($this->data);
	}
	
	public static function getUserIdFromDescriptivePage($cID, $cType) {
		if ($cID && $cType) {
			if ($equivId = ze\content::equivId($cID,$cType)) {
				return ze\row::get("users","id",["equiv_id" => $equivId, "content_type" => $cType]);
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				$this->showHideImageOptions($fields, $values, 'first_tab', false, 'max_user_image_', false, 'Max image size (width × height):');
				break;
		}
	}
}

?>