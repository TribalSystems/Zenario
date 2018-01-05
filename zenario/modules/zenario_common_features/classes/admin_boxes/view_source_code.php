<?php
/*
 * Copyright (c) 2018, Tribal Limited
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


class zenario_common_features__admin_boxes__view_source_code extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		$file = false;
		
		switch ($box['key']['type']) {
			case 'layout':
				
				if ($layout = ze\content::layoutDetails($box['key']['id'])) {
					//ze\content::templatePath($templateFamily = false, $fileBaseName = false, $css = false)
					$file = ze\content::templatePath($layout['family_name'], $layout['file_base_name']);
					
					$box['title'] = ze\admin::phrase('Viewing the template file file for the Layout "[[name]]"', $layout);
				}
				
				break;
			
			case 'framework':
				
				$moduleId = ze::ifNull($_REQUEST['refiner__module'] ?? false, ($_REQUEST['moduleId'] ?? false));
				$framework = ze\ring::decodeIdForOrganizer(ze::ifNull($_REQUEST['id'] ?? false, ($_REQUEST['framework'] ?? false)));
				
				if ($module = ze\module::details($moduleId)) {
					$file = ze\plugin::frameworkPath($framework, $module['class_name']);
					
					$module['framework'] = $framework;
					$box['title'] = ze\admin::phrase('Viewing the "[[framework]]" framework for the Module "[[display_name]]"', $module);
				}
				
				break;
				
			case 'skin_file':
				
				
				$skinId = ze\ring::decodeIdForOrganizer($_REQUEST['refiner__skin'] ?? false);
				$filename =
				$subpath = ze\ring::decodeIdForOrganizer($_REQUEST['id'] ?? false);
				
				if (strpos($subpath, './') === false
				 && strpos($subpath, '.\\') === false
				 && ($skin = ze\content::skinDetails($skinId))) {
					$file = CMS_ROOT. ze\content::skinPath($skin['family_name'], $skin['name']). $subpath;
					
					$skin['filename'] = $filename;
					$box['title'] = ze\admin::phrase('Viewing the "[[filename]]" file in the Skin "[[display_name]]"', $skin);
				}
				
				break;
		}
		
		if ($file && file_exists($file) && is_readable($file)) {
			$values['source/code'] = file_get_contents($file);
			$fields['source/code']['language'] = basename($file);
		} else {
			echo ze\admin::phrase('Could not read this file!');
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
