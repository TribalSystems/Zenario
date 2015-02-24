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


if (!$module = getModuleDetails($box['key']['id'])) {
	exit;
}



switch ($module['status']) {
	case 'module_suspended':
		if ($error = runModule($box['key']['id'], $test = true)) {
			echo $error;
			exit;
		}
		
		$box['title'] = adminPhrase('Starting the module "[[display_name]]"', $module);
		$box['save_button_message'] = adminPhrase('Start module');
		
		$box['max_height'] = 50;
		$box['tabs']['confirm']['notices']['are_you_sure']['show'] = true;
		
		break;
		
	case 'module_running':
		echo adminPhrase('This module is already running!');
		exit;
	
	case 'module_not_initialized':
		if ($error = runModule($box['key']['id'], $test = true)) {
			echo $error;
			exit;
		}
		
		$box['title'] = adminPhrase('Starting the module "[[display_name]]"', $module);
		$box['save_button_message'] = adminPhrase('Start module');
		
		$box['max_height'] = 50;
		$box['tabs']['confirm']['notices']['are_you_sure']['show'] = true;
}

$box['tabs']['confirm']['hidden'] = false;
$box['tabs']['confirm']['notices']['are_you_sure']['message'] = adminPhrase('Start the module "[[display_name]]"?', $module);

if ($module['status'] == 'module_not_initialized'
 && ($perms = scanModulePermissionsInTUIXDescription($module['class_name']))) {
	$box['tabs']['confirm']['fields']['grant_perms']['hidden'] = false;
	$box['tabs']['confirm']['fields']['grant_perms_desc']['hidden'] = false;
	
	if (isset($box['max_height'])) {
		$box['max_height'] = 100;
	}
	
	if (session('admin_global_id')) {
		unset($box['tabs']['confirm']['fields']['grant_perms']['values']['myself']);
		$box['tabs']['confirm']['fields']['grant_perms']['values']['site_admins']['label'] =
			adminPhrase('Grant all administrators the permissions added by this module');
	}
}

return false;