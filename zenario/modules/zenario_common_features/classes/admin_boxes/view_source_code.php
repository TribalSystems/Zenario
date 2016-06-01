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


class zenario_common_features__admin_boxes__view_source_code extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		$file = false;
		
		switch ($box['key']['type']) {
			case 'layout':
				
				if ($layout = getTemplateDetails($box['key']['id'])) {
					//zenarioTemplatePath($templateFamily = false, $fileBaseName = false, $css = false)
					$file = zenarioTemplatePath($layout['family_name'], $layout['file_base_name']);
					
					$box['title'] = adminPhrase('Viewing the template file file for the Layout "[[name]]"', $layout);
				}
				
				break;
			
			case 'framework':
				
				$moduleId = ifNull(request('refiner__module'), request('moduleId'));
				$framework = decodeItemIdForStorekeeper(ifNull(request('id'), request('framework')));
				
				if ($module = getModuleDetails($moduleId)) {
					$file = frameworkPath($framework, $module['class_name'], true);
					
					$module['framework'] = $framework;
					$box['title'] = adminPhrase('Viewing the "[[framework]]" framework for the Module "[[display_name]]"', $module);
				}
				
				break;
				
			case 'skin_file':
				
				
				$skinId = decodeItemIdForStorekeeper(request('refiner__skin'));
				$filename =
				$subpath = decodeItemIdForStorekeeper(request('id'));
				if (request('refiner__subpath')) {
					$subpath .= decodeItemIdForStorekeeper(request('refiner__subpath'));
				}
				
				if (strpos($subpath, './') === false
				 && strpos($subpath, '.\\') === false
				 && ($skin = getSkinFromId($skinId))) {
					$file = CMS_ROOT. getSkinPath($skin['family_name'], $skin['name']). $subpath;
					
					$skin['filename'] = $filename;
					$box['title'] = adminPhrase('Viewing the "[[filename]]" file in the Skin "[[display_name]]"', $skin);
				}
				
				break;
		}
		
		if ($file && file_exists($file) && is_readable($file)) {
			$values['source/code'] = file_get_contents($file);
			$fields['source/code']['language'] = basename($file);
		} else {
			echo adminPhrase('Could not read this file!');
			exit;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}
}
