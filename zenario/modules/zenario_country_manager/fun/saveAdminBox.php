<?php
/*
 * Copyright (c) 2021, Tribal Limited
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
			ze\priv::exitIfNot('_PRIV_MANAGE_COUNTRY');
			
			ze\row::set(
				ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_countries', 
				['english_name' => $values['details/name']], 
				['id' => $values['details/code']]
			);
						
			if ($values['details/update_phrase'] || !$box['key']['id']) {
				$languages = ze\lang::getLanguages();
				foreach ($languages as $language) {
					ze\row::set(
						"visitor_phrases", 	
						[
							'local_text' => $values['details/name'],
							'protect_flag' => 1
							], 
						[
							'language_id' => $language['id'],
							'module_class_name' => 'zenario_country_manager',
							'code' => '_COUNTRY_NAME_' . $values['details/code']
						]
					); 
				}
			}

			$box['key']['id'] = $values['details/code'];
			
			break;
	
	
		case 'zenario_country_manager__region':
			ze\priv::exitIfNot('_PRIV_MANAGE_COUNTRY');
			
			$updateArray['name'] = ($values['details/name'] ?? false);
			if (ze::setting('zenario_country_manager__region_type_management')) {
				$updateArray['region_type'] = ($values['details/region_type'] ?? false);
			}
			$updateArray['active'] = 1;
			$updateArray['parent_id'] = 0;
			$updateArray['country_id'] = '';
			
			if ($box['key']['parent_id']) {
				$updateArray['parent_id'] = $box['key']['parent_id'];
			} elseif ($box['key']['country_id']) {
				$updateArray['country_id'] = $box['key']['country_id'];
			}
			
			if (!$box['key']['id']) {
				$languages = ze\lang::getLanguages();
				foreach ($languages as $language) {
					ze\row::set(
						"visitor_phrases", 	
						[
							'local_text' => $values['details/name'],
							'protect_flag' => 1
							], 
						[
							'language_id' => $language['id'],
							'module_class_name' => 'zenario_country_manager',
							'code' => $values['details/name']
						]
					); 
				}
			}

			$box['key']['id'] = ze\row::set(
				ZENARIO_COUNTRY_MANAGER_PREFIX . 'country_manager_regions',
				$updateArray,
				['id' => ($box['key']['id'] ?? false)]
			);
			break;
	}

?>