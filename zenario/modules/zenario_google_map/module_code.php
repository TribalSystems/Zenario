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


class zenario_google_map extends ze\moduleBaseClass {
	
	protected $data = [];
	
	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$apiKey = ze::setting('google_maps_api_key');
		if ($apiKey) {
			$this->data['googlemap'] = '<div id="object_in_' . $this->containerId.'" style="height: '.$this->setting('height').'px; width: '.$this->setting('width').'px;"></div>';
			$this->callScript(
				'zenario_google_map', 
				'initMap', 
				$this->setting("address"),
				'object_in_' . $this->containerId,
				$this->phrase( "Google Maps could not find the address `[[address]]`.", [ 'address' => $this->setting( 'address' )]),
				'https://maps.googleapis.com/maps/api/js?key=' . urlencode($apiKey)
			);
		} else {
			$googleApiKeySiteSettingLink = 'organizer.php#zenario__administration/panels/site_settings//api_keys~.site_settings~tgoogle_maps~k{"id"%3A"api_keys"}';
			$this->data['googlemap'] = $this->phrase('Cannot display Google Map, please set a <a href=\'' . $googleApiKeySiteSettingLink . '\' target=\'_blank\'>Google Maps API key</a>.');
		}
		return true;
	}
	
	public function showSlot() {
		$this->twigFramework($this->data);
	}
	
	public function showStandalonePage() {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				$this->showHideImageOptions($fields, $values, 'first_tab', false, '', false);
				break;
		}
	}
}