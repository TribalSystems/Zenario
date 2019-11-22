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


//	This header file can be used as a header file for shell scripts. It connects to the database,
//	but does not browser-related tasks (e.g. it doesn't attempt to start a session).

if (!defined('CMS_ROOT')) {
	$dirname = dirname($cwd = $argv[0] ?? $_SERVER['SCRIPT_FILENAME'] ?? '.');
	$cwd = $cwd[0] === '/'? $dirname. '/' : getcwd(). '/'. ($dirname === '.'? '' : $dirname. '/');
	
	for ($ci = 9; --$ci > 0 && !is_file($cwd. 'zenario/basicheader.inc.php');) {
		$cwd = dirname($cwd). '/';
	}

	define('CMS_ROOT', $cwd);
}
define('RUNNING_FROM_COMMAND_LINE', true);

//Include the CMS' library of functions, but don't include any behaviour designed
//for sending a page to a Visitor as this is a scheduled task and not a page load.
require CMS_ROOT. 'zenario/basicheader.inc.php';
ze\db::loadSiteConfig();

//Include the currently running version of the Core CMS Module
foreach (ze::$editions as $className) {
	if (ze\module::inc($className)) {
		ze::$edition = $className;
		break;
	}
}
unset($className, $dirName);