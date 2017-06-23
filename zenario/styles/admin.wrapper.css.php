<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

require '../basicheader.inc.php';
useCache('zenario-inc-admin-css-'. LATEST_REVISION_NO);
useGZIP(!empty($_GET['gz']));


//Include all of the standard CSS admin libraries for the CMS
incCSS('zenario/styles/admin_toolbar');
incCSS('zenario/styles/admin_toolbar_buttons');
incCSS('zenario/styles/admin_floating_box');
incCSS('zenario/styles/admin_controls');
incCSS('zenario/styles/admin');
incCSS('zenario/styles/colorbox');

//Include other third-party libraries
incCSS('zenario/libraries/bsd/tokenize/jquery.tokenize');
incCSS('zenario/libraries/mit/intro/introjs');
incCSS('zenario/libraries/mit/chosen/chosen');
incCSS('zenario/libraries/mit/spectrum/spectrum');
incCSS('zenario/libraries/mit/toastr/toastr');

echo '
$.fn.spectrum.load = false;
';
