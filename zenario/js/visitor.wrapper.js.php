<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

ze\cache::useBrowserCache('zenario-inc-js-'. LATEST_REVISION_NO);
ze\cache::start();


//Run pre-load actions

require ze::editionInclude('wrapper.pre_load');




ze\cache::incJS('zenario/js/base_definitions');

//Include Modernizr
ze\cache::incJS('zenario/libs/manually_maintained/mit/modernizr/modernizr');

//Include the underscore library
ze\cache::incJS('zenario/libs/manually_maintained/mit/underscore/underscore');

//Include all of the standard JavaScript libraries for the CMS
ze\cache::incJS('zenario/js/visitor');
ze\cache::incJS('zenario/api/javascript');

//Include jQuery modules and some other third-party libraries
ze\cache::incJS('zenario/js/easing');
ze\cache::incJS('zenario/libs/manually_maintained/mit/colorbox/jquery.colorbox');
ze\cache::incJS('zenario/libs/manually_maintained/mit/doubletap-to-go/jquery.doubletaptogo');
ze\cache::incJS('zenario/libs/manually_maintained/bsd/javascript_md5/md5');
ze\cache::incJS('zenario/libs/bower/jquery-lazy/jquery.lazy');

echo '
zenario.tinyMCEPath = "', TINYMCE_DIR, 'tinymce.min.js";';

ze\cache::incJS('zenario/js/visitor.ready');



//Run post-display actions
require ze::editionInclude('wrapper.post_display');
