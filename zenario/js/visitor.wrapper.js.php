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

header('Content-Type: text/javascript; charset=UTF-8');
require '../basicheader.inc.php';

useCache('zenario-inc-js-'. LATEST_REVISION_NO);
useGZIP(!empty($_GET['gz']));


//Run pre-load actions
foreach (cms_core::$editions as $className => $dirName) {
	if ($action = moduleDir($dirName, 'actions/wrapper.pre_load.php', true)) {
		require $action;
	}
}


function incJS($file) {
	if (file_exists($file. '.pack.js')) {
		require $file. '.pack.js';
	} elseif (file_exists($file. '.min.js')) {
		require $file. '.min.js';
	} elseif (file_exists($file. '.js')) {
		require $file. '.js';
	}
	
	echo "\n/**/\n";
}



incJS('zenario/js/base_definitions');

//Include Modernizr
incJS('zenario/libraries/bsd/modernizr/modernizr');

//Include the underscore library
incJS('zenario/libraries/mit/underscore/underscore');

//Include all of the standard JavaScript libraries for the CMS
incJS('zenario/api/javascript_core');
incJS('zenario/js/visitor');
incJS('zenario/api/javascript');

//Include jQuery modules
//Note: this order is quite sensitive, as some give JavaScript errors if included in a certain order
incJS('zenario/js/easing');
incJS('zenario/libraries/mit/jquery/jquery.colorbox');


//Run post-display actions
foreach (cms_core::$editions as $className => $dirName) {
	if ($action = moduleDir($dirName, 'actions/wrapper.post_display.php', true)) {
		require $action;
	}
}