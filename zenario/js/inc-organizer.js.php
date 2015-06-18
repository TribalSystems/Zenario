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

header('Content-Type: text/javascript; charset=UTF-8');
require '../cacheheader.inc.php';

useCache('zenario-inc-organizer-js-'. LATEST_REVISION_NO);
useGZIP(!empty($_GET['gz']));


//require CMS_ROOT. 'zenario/includes/cms.inc.php';

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

//Include all of the standard JavaScript Admin libraries for the CMS
incJS('zenario/libraries/bsd/javascript_md5/md5');
incJS('zenario/libraries/mit/jquery/jquery.nestable');
incJS('zenario/libraries/mit/jpaginator/jPaginator-min');
incJS('zenario/js/admin_organizer');

incJS('zenario/api/panel_type_base_class');
incJS('zenario/js/panel_type_grid');
incJS('zenario/js/panel_type_list');
incJS('zenario/js/panel_type_list_or_grid');
incJS('zenario/js/panel_type_list_with_totals');
incJS('zenario/js/panel_type_hierarchy');
incJS('zenario/js/panel_type_hierarchy_with_lazy_load');

incJS('zenario/js/panel_type_calendar');

incJS('zenario/js/panel_type_google_map');
incJS('zenario/js/panel_type_google_map_or_list');