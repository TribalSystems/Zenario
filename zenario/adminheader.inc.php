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


/*  
 *  This file can be used as a header file.
 *  It will include the full library of functions in the CMS, including admin functions which are not 
 *  usually included.
 *  It will then connect to the database.
 *  
 *  This file checks whether the visitor is actually logged in as an Admin.
 *  If they are not it attempts to redirect them to the admin login page, then stops execution.
 */


//Standard include file Header
if (!defined('NOT_ACCESSED_DIRECTLY')) {
	if (extension_loaded('mbstring')) mb_internal_encoding('UTF-8');
	
	//Attempt to set the working directory to the CMS Root, if it is not already
	//Note that I am using $_SERVER['SCRIPT_FILENAME'] and not __DIR__ to do this
	//because I want to resolve the relative URL when symlinks are used.
	$isFilelimit = 0;
	$dirname = $_SERVER['SCRIPT_FILENAME'];
	
	do {
		if (is_null($dirname) 
		 || ++$isFilelimit > 9) {
			echo
				'Could not resolve where the "zenario/" directory is.', "\n",
				'This could be a problem with a symlink, ',
				'or could be because you are trying to call this file from terminal ',
				'(which it is not designed to do).', "\n";
			exit;
		} else {
			chdir($dirname = dirname($dirname));
		}
	} while (!is_file('zenario/visitorheader.inc.php'));
	
	if (!defined('CMS_ROOT')) {
		define('CMS_ROOT', $dirname. '/');
	}
	
	//Define a constant to mark than any further include files have been legitamately included
	define('THIS_FILE_IS_BEING_DIRECTLY_ACCESSED', false);
	define('NOT_ACCESSED_DIRECTLY', true);
	unset($isFilelimit);
	unset($dirname);
}


require_once CMS_ROOT. 'zenario/visitorheader.inc.php';
require_once CMS_ROOT. 'zenario/includes/admin.inc.php';

//Check if the admin is currently logged in
if (!checkPriv()) {
	//If not, try to handle the problem
	//Attempt to check where this funciton is being called from
	$location = debug_backtrace();
	$location = array_reverse(explode('/', $location[0]['file']));
	
	//If this is an AJAX file, or an admin box, simply exit as there is no recovery we can really do
	if (substr(arrayKey($location, 0), -8) == 'ajax.php'
	 || arrayKey($location, 0) == 'ajax'
	 || arrayKey($location, 1) == 'ajax'
	 || arrayKey($location, 1) == 'admin_boxes'
	 || request('method_call') == 'handleOrganizerPanelAJAX'
	 || request('method_call') == 'handleAdminToolbarAJAX') {
		echo '<!--Logged_Out-->', adminPhrase('You have been logged out.');
		exit;
	
	//Also don't do the redirect for index.php or organizer.php
	} elseif (arrayKey($location, 0) != DIRECTORY_INDEX_FILENAME && arrayKey($location, 0) != 'index.php' && arrayKey($location, 0) != 'organizer.php') {
		//Otherwise attempt to redirect the user to the login screen
		header('location: '. httpOrHttps(). $_SERVER["HTTP_HOST"]. SUBDIRECTORY. 'zenario/admin/welcome.php?desturl='. urlencode($_SERVER['REQUEST_URI']));
		exit;
	}

} else {
	
	//Load the current admin's settings
	foreach (getRowsArray('admin_setting_defaults', 'default_value', array()) as $adminSettingName => $adminSettingDefaultValue) {
		cms_core::$adminSettings[$adminSettingName] = $adminSettingDefaultValue;
	}
	
	foreach (getRowsArray('admin_settings', array('name', 'value'), array('admin_id' => adminId())) as $adminSetting) {
		if (chopPrefixOffOfString($adminSetting['name'], 'COOKIE_ADMIN_SECURITY_CODE_') === false) {
			cms_core::$adminSettings[$adminSetting['name']] = $adminSetting['value'];
		}
	}
	unset($adminSetting);
	unset($adminSettingName);
	unset($adminSettingDefaultValue);
}