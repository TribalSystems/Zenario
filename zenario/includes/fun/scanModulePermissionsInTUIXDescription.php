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

$perms = array();

//Loop through every module Setting that a module has in its Admin Box XML file(s)
if ($dir = moduleDir($moduleClassName, 'tuix/admin_boxes/', true)) {
	$tags = array();
	foreach (scandir(CMS_ROOT. $dir) as $file) {
		if (is_file(CMS_ROOT. $dir. $file) && substr($file, 0, 1) != '.') {
			//Attempt to open and read the XML for an Admin Boxes
			$tagsToParse = zenarioReadTUIXFile(CMS_ROOT. $dir. $file);
			zenarioParseTUIX($tags, $tagsToParse, 'admin_boxes', $moduleClassName, $settingGroup = '', $compatibilityClassNames = array(), 'zenario_admin');
			unset($tagsToParse);	
		}
	}
	
	if (!empty($tags)) {
		if (isset($tags['admin_boxes']['zenario_admin']['tabs']['permissions']['fields'])
		 && is_array($tags['admin_boxes']['zenario_admin']['tabs']['permissions']['fields'])) {
			foreach ($tags['admin_boxes']['zenario_admin']['tabs']['permissions']['fields'] as $fieldName => &$field) {
				if (is_array($field)) {
				
					if (!empty($field['type']) && $field['type'] == 'checkbox') {
						$perms[$fieldName] = true;
					
					} elseif ((empty($field['type']) || $field['type'] == 'checkboxes') && !empty($field['values'])) {
						foreach ($field['values'] as $perm => &$dummy) {
							$perms[$perm] = true;
						}
					}
				}
			}
		}
	}
}

if (empty($perms)) {
	return false;
} else {
	return $perms;
}