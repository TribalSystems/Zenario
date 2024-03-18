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

ze\cache::useBrowserCache('zenario-inc-highcharts-js-'. LATEST_REVISION_NO);


//Run pre-load actions
if (ze::$canCache) require CMS_ROOT. 'zenario/includes/bundle.pre_load.inc.php';


//Include highcharts if this site has brought a license and included it in the
//zenario/libs/not_to_redistribute/highcharts/ directory
ze\bundle::incJS('zenario/libs/not_to_redistribute/highcharts/highcharts');
ze\bundle::incJS('zenario/libs/not_to_redistribute/highcharts/highcharts-more');
ze\bundle::incJS('zenario/libs/not_to_redistribute/highcharts/solid-gauge');
//ze\bundle::incJS('zenario/libs/not_to_redistribute/highcharts/adapt-chart-to-legend');
ze\bundle::incJS('zenario/libs/not_to_redistribute/highcharts/exporting');
ze\bundle::incJS('zenario/libs/not_to_redistribute/highcharts/export-csv');
ze\bundle::incJS('zenario/libs/not_to_redistribute/highcharts/modules/no-data-to-display');

//Run post-display actions
if (ze::$canCache) require CMS_ROOT. 'zenario/includes/bundle.post_display.inc.php';