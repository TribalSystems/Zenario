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

header('Content-Type: text/css; charset=UTF-8');

require '../cacheheader.inc.php';
useCache('zenario-inc-admin-css-'. LATEST_REVISION_NO);
useGZIP(!empty($_GET['gz']));


function incCCS($file) {
	if (file_exists($file. '.min.css')) {
		require $file. '.min.css';
	} elseif (file_exists($file. '.css')) {
		require $file. '.css';
	}
	
	echo "\n/**/\n";
}


//Include all of the standard CSS admin libraries for the CMS
incCCS('zenario/styles/admin_toolbar');
incCCS('zenario/styles/admin_toolbar_buttons');
incCCS('zenario/styles/admin_floating_box');
incCCS('zenario/styles/admin_controls');
incCCS('zenario/styles/admin');
incCCS('zenario/styles/colorbox');

//Include other third-party libraries
incCCS('zenario/libraries/mit/intro/introjs');
incCCS('zenario/libraries/mit/spectrum/spectrum');

echo '
$.fn.spectrum.load = false;
';


//Include a list of fixes for IE
if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6') !== false) {
	incCCS('zenario/styles/admin_controls_ie6');

} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7') !== false) {
	incCCS('zenario/styles/admin_controls_ie7');

} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8') !== false) {
	incCCS('zenario/styles/admin_controls_ie8');
}
