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

useCache('zenario-inc-admin-js-'. LATEST_REVISION_NO);
useGZIP(!empty($_GET['gz']));


//Include a function that uses eval, and shouldn't be minified
incJS('zenario/js/admin.nomin');

//Include all of the standard JavaScript Admin libraries for the CMS
incJS('zenario/js/admin');
incJS('zenario/js/form');
incJS('zenario/js/admin_form');
incJS('zenario/js/admin_box_toolkit');
incJS('zenario/js/admin_box');
incJS('zenario/js/admin_skin_editor');
incJS('zenario/js/admin_toolbar');

//Include Ace Editor extensions
incJS('zenario/libraries/bsd/ace/src-min-noconflict/ext-modelist');

//Include jQuery modules
//Include a small pre-loader library for TinyMCE (the full code is load-on-demand)
incJS(TINYMCE_DIR. 'jquery.tinymce');
//Include the autocomplete library for the FAB library
incJS('zenario/libraries/mit/jquery/jquery-ui.autocomplete');
//Include the selectboxes library for moving items between select lists
incJS('zenario/libraries/mit/jquery/jquery.selectboxes');
//Include the jQuery Slider code in Admin Mode
incJS('zenario/libraries/mit/jquery/jquery-ui.slider');

//Include other third-party libraries
incJS('zenario/libraries/bsd/tokenize/jquery.tokenize');
incJS('zenario/libraries/mit/intro/intro');
incJS('zenario/libraries/mit/spectrum/spectrum');
incJS('zenario/libraries/mit/toastr/toastr');
incJS('zenario/libraries/public_domain/mousehold/mousehold');
incJS('zenario/libraries/mit/jssor/jssor.slider.mini');

echo '
ace.config.set("basePath", URLBasePath + "zenario/libraries/bsd/ace/src-min-noconflict/");';