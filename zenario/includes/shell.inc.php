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
 *  This file can be used as a header file for shell scripts.
 *  It includes the CMS' library of functions, but don't include any behaviour
 *  designed for sending a page to a Visitor as this is not a page load.
 *  
 *  Before calling this function, you must define the CMS_ROOT directory; e.g. by
 *  repeatedly applying the dirname() function to the $argv[0] variable.
 *  If your shell script is in a Module's directory, you should account for the
 *  fact that Module can be in either:
 *   - the zenario/modules/ directory
 *   - the zenario_extra_modules/ directory
 *   - the zenario_custom/modules/ directory
 */


if (!defined('CMS_ROOT')) {
	exit;
}
chdir(CMS_ROOT);

define('NOT_ACCESSED_DIRECTLY', true);
define('THIS_FILE_IS_BEING_DIRECTLY_ACCESSED', false);
define('RUNNING_FROM_COMMAND_LINE', true);

//Include the CMS' library of functions, but don't include any behaviour designed
//for sending a page to a Visitor as this is a scheduled task and not a page load.
require CMS_ROOT. 'zenario/basicheader.inc.php';
require CMS_ROOT. 'zenario/api/database_functions.inc.php';
require CMS_ROOT. 'zenario/includes/cms.inc.php';
require CMS_ROOT. 'zenario/includes/admin.inc.php';
loadSiteConfig();

//Include the Module Base Class
require CMS_ROOT. 'zenario/api/module_api.inc.php';
require CMS_ROOT. 'zenario/api/module_base_class/module_code.php';

//Include the currently running version of the Core CMS Module
foreach (cms_core::$editions as $className) {
	if (inc($className)) {
		cms_core::$edition = $className;
		break;
	}
}
unset($className);
unset($dirName);