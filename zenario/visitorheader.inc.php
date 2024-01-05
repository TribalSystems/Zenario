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


//	This header file connects to the database, and then starts the visitor's session.

if (!defined('CMS_ROOT')) {
	$dirname = dirname($cwd = $argv[0] ?? $_SERVER['SCRIPT_FILENAME'] ?? '.');
	
 	//MacOS or Linux	//Windows
	if ($cwd[0] === '/' || ($cwd[1] === ':' && $cwd[2] === '/')) {
		$cwd = $dirname. '/';
	} else {
		$cwd = getcwd(). '/'. ($dirname === '.'? '' : $dirname. '/');
	}
	
	for ($ci = 9; --$ci > 0 && !is_file($cwd. 'zenario/basicheader.inc.php');) {
		$cwd = dirname($cwd). '/';
	}

	define('CMS_ROOT', $cwd);
}

require_once CMS_ROOT. 'zenario/basicheader.inc.php';


//Display the content-type header
header('Content-Type: text/html; charset=UTF-8');
ze\cookie::startSession();

//Load site settings
ze\db::loadSiteConfig();


//Check for any form submissions
if (!empty($_POST) && substr(($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 10) != 'BlackBerry' && (!defined('SKIP_XSS_CHECK') || !SKIP_XSS_CHECK)) {
	//Attempt to stop some XSS attacks by checking that the referer matches (except for old Blackberries which don't support this feature)
	if (substr($_SERVER['HTTP_REFERER'] ?? false, 0, strlen(ze\link::protocol(). ($_SERVER['HTTP_HOST'] ?? false))) !== ze\link::protocol(). ($_SERVER['HTTP_HOST'] ?? false)) {
		echo 'Sorry, but the referer for the form you just submitted does not seem to match this site';
		exit;
	}
}

//Include the currently running version of the Core CMS Module
foreach (ze::$editions as $className) {
	if (ze\module::inc($className)) {
		ze::$edition = $className;
		break;
	}
}
unset($className, $dirName);


//Attempt to automatically log a User in if the cookie is set on the User's Machine
ze\user::logInAutomatically();


//Set the user's language, if it is not set already
if (!empty($_SESSION) && empty($_SESSION['user_lang'])) {
	$_SESSION['user_lang'] = ze::$defaultLang;
}

