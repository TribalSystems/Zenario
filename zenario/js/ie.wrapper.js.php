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

$ie = '';
if (!empty($_GET['ie'])) {
	$ie = (int) $_GET['ie'];
}

ze\cache::useBrowserCache('zenario-inc-js-ie-'. $ie. '-'. LATEST_REVISION_NO);


//Run pre-load actions

require ze::editionInclude('wrapper.pre_load');


switch ($ie) {
	case 7:
		echo '
			try {
				document.execCommand("BackgroundImageCache", false, true);
			} catch(err) {}';
		ze\cache::incJS('zenario/libs/manually_maintained/public_domain/json/json2');
	
	case 8:
		echo '
			if (!Date.now) {
				Date.now = function() {
					return new Date().valueOf();
				};
			}';
		ze\cache::incJS('zenario/libs/manually_maintained/mit/split/split');
	
	case 9:
		ze\cache::incJS('zenario/libs/manually_maintained/mit/jquery/jquery.placeholder');
		ze\cache::incJS('zenario/libs/bower/media-match/media.match');
}


//Run post-display actions
require ze::editionInclude('wrapper.post_display');
