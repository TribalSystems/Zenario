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

header('Content-Type: text/javascript; charset=UTF-8');
require '../basicheader.inc.php';

useCache('zenario-inc-admin-js-'. LATEST_REVISION_NO);
useGZIP();


//Run pre-load actions
require CMS_ROOT. 'zenario/api/cache_functions.inc.php';
require editionInclude('wrapper.pre_load');


//Include a function that uses eval, and shouldn't be minified
incJS('zenario/js/tuix.nomin');

//Include all of the standard JavaScript Admin libraries for the CMS
incJS('zenario/js/tuix');
incJS('zenario/js/form');

//Include other third-party libraries
incJS('zenario/libraries/bsd/tokenize/jquery.tokenize');
incJS('zenario/libraries/mit/jpaginator/jPaginator');
incJS('zenario/libraries/mit/jquery/jquery-ui.autocomplete');
incJS('zenario/libraries/mit/jquery/jquery-ui.selectmenu');
incJS('zenario/libraries/mit/jquery/jquery-ui.iconselectmenu');
incJS('zenario/libraries/mit/jquery/jquery-ui.slider');
incJS('zenario/libraries/mit/jquery.multiselect/jquery.multiselect');

incJS('zenario/js/tuix.ready');


//Run post-display actions
require editionInclude('wrapper.post_display');
