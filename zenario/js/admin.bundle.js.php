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

header('Content-Type: text/javascript; charset=UTF-8');
require '../basicheader.inc.php';

ze\cache::useBrowserCache('zenario-inc-admin-js-'. LATEST_REVISION_NO);


//Run pre-load actions

if (ze::$canCache) require CMS_ROOT. 'zenario/includes/bundle.pre_load.inc.php';


//Include all of the standard JavaScript Admin libraries for the CMS
ze\bundle::incJS('zenario/js/admin');
ze\bundle::incJS('zenario/js/admin_form');
ze\bundle::incJS('zenario/js/admin_box_toolkit');
ze\bundle::incJS('zenario/js/admin_box');
ze\bundle::incJS('zenario/js/admin_skin_editor');
ze\bundle::incJS('zenario/js/admin_toolbar');

//Include other third-party libraries
ze\bundle::incJS('zenario/libs/manually_maintained/mit/intro/intro');
ze\bundle::incJS('zenario/libs/yarn/spectrum-colorpicker/spectrum');
ze\bundle::incJS('zenario/libs/yarn/toastr/toastr');
ze\bundle::incJS('zenario/libs/manually_maintained/mit/jquery/jquery.selectboxes');
ze\bundle::incJS('zenario/libs/manually_maintained/mit/jssor/jssor.slider.mini');
ze\bundle::incJS('zenario/libs/manually_maintained/public_domain/mousehold/mousehold');
ze\bundle::incJS('zenario/libs/manually_maintained/mit/jqueryui/jquery-ui.interactions');
ze\bundle::incJS('zenario/libs/manually_maintained/mit/jqueryui/jquery-ui.spinner');
ze\bundle::incJS('zenario/libs/manually_maintained/mit/jqueryui/jquery-ui.progressbar');




//Run post-display actions
if (ze::$canCache) require CMS_ROOT. 'zenario/includes/bundle.post_display.inc.php';
