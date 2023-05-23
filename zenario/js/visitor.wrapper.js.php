<?php
/*
 * Copyright (c) 2023, Tribal Limited
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

$libs = $_GET['libs'] ?? '';

ze\cache::useBrowserCache('zenario-inc-js-'. LATEST_REVISION_NO. '-'. $libs);
ze\cache::start();




//Run pre-load actions
if (ze::$canCache) require CMS_ROOT. 'zenario/includes/wrapper.pre_load.inc.php';



$libs = array_flip(explode(',', $libs));


ze\cache::incJS('zenario/js/base_definitions');


//Include Modernizr site-wide in the wrapper if requested.
//If not requested, it won't be included at all.
if (isset($libs['m'])) {
	ze\cache::incJS('zenario/libs/manually_maintained/mit/modernizr/modernizr');
}


//Include the underscore utility library (mandatory, as it's used in the core library)
ze\cache::incJS('zenario/libs/manually_maintained/mit/underscore/underscore');

//Include all of the standard JavaScript libraries for the CMS
ze\cache::incJS('zenario/js/visitor');
ze\cache::incJS('zenario/reference/plugin_base_class');

//Include our easing options for jQuery animations
ze\cache::incJS('zenario/js/easing');

//Include Lazy Load library
ze\cache::incJS('zenario/libs/yarn/jquery-lazy/jquery.lazy');

//Add a small checksum library. We have a couple of core functions that need to use checksums,
//and believe it or not JavaScript doesn't have a checksum-generating function built in!
ze\cache::incJS('zenario/libs/yarn/js-crc/src/crc');


//Include doubletaptogo site-wide in the wrapper if requested.
//If not requested, it will be included separately, and only on pages where a plugin claims to need it.
if (isset($libs['dt'])) {
	ze\cache::incJS('zenario/libs/yarn/jquery-doubletaptogo/dist/jquery.dcd.doubletaptogo');
}

//Include colorbox site-wide in the wrapper if requested.
//If not requested, it will be included separately, and only on pages where a plugin claims to need it.
if (isset($libs['cb'])) {
	ze\cache::incJS('zenario/libs/manually_maintained/mit/colorbox/jquery.colorbox');
}


//Write down the path to tinyMCE, for the tinyMCE autoloader to use if needed
echo '
zenario.tinyMCEPath = "', TINYMCE_DIR, 'tinymce.min.js";';


//Some misc fixes to run when the page has finished loading
ze\cache::incJS('zenario/js/visitor.ready');



//Run post-display actions
if (ze::$canCache) require CMS_ROOT. 'zenario/includes/wrapper.post_display.inc.php';
