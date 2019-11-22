<?php
/*
 * Copyright (c) 2019, Tribal Limited
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


//	This header file connects to the database, starts the visitor's session, and then
//	checks whether the visitor is actually logged in as an Admin. If they are not it
//	attempts to redirect them to the admin login page, then stops execution.

if (!defined('CMS_ROOT')) {
	$dirname = dirname($cwd = $argv[0] ?? $_SERVER['SCRIPT_FILENAME'] ?? '.');
	$cwd = $cwd[0] === '/'? $dirname. '/' : getcwd(). '/'. ($dirname === '.'? '' : $dirname. '/');
	
	for ($ci = 9; --$ci > 0 && !is_file($cwd. 'zenario/basicheader.inc.php');) {
		$cwd = dirname($cwd). '/';
	}

	define('CMS_ROOT', $cwd);
}

require_once CMS_ROOT. 'zenario/visitorheader.inc.php';


//Check if the admin is currently logged in
if (!ze\priv::check()) {
	//If not, try to handle the problem
	//Attempt to check where this funciton is being called from
	$location = debug_backtrace();
	$location = array_reverse(explode('/', $location[0]['file']));
	
	//If this is an AJAX file, or an admin box, simply exit as there is no recovery we can really do
	if (substr($location[0] ?? false, -8) == 'ajax.php'
	 || ($location[0] ?? false) == 'ajax'
	 || ($location[1] ?? false) == 'ajax'
	 || ($location[1] ?? false) == 'admin_boxes'
	 || ($_REQUEST['method_call'] ?? false) == 'handleOrganizerPanelAJAX'
	 || ($_REQUEST['method_call'] ?? false) == 'handleAdminToolbarAJAX') {
		
		header('Zenario-Admin-Logged_Out: 1');
		echo '<!--Logged_Out-->', ze\admin::phrase('You have been logged out.');
		exit;
	
	//Also don't do the redirect for index.php or organizer.php
	} elseif (($location[0] ?? false) != DIRECTORY_INDEX_FILENAME && ($location[0] ?? false) != 'index.php' && ($location[0] ?? false) != 'organizer.php') {
		//Otherwise attempt to redirect the user to the login screen
		header('location: '. ze\link::protocol(). $_SERVER["HTTP_HOST"]. SUBDIRECTORY. 'zenario/admin/welcome.php?desturl='. urlencode($_SERVER['REQUEST_URI']));
		exit;
	}

} else {
	
	//Load the current admin's settings
	foreach (ze\row::getValues('admin_setting_defaults', 'default_value', []) as $adminSettingName => $adminSettingDefaultValue) {
		ze::$adminSettings[$adminSettingName] = $adminSettingDefaultValue;
	}
	
	foreach (ze\row::getAssocs('admin_settings', ['name', 'value'], ['admin_id' => ze\admin::id()]) as $adminSetting) {
		if (ze\ring::chopPrefix('COOKIE_ADMIN_SECURITY_CODE_', $adminSetting['name']) === false) {
			ze::$adminSettings[$adminSetting['name']] = $adminSetting['value'];
		}
	}
	unset($adminSetting, $adminSettingName, $adminSettingDefaultValue);
}