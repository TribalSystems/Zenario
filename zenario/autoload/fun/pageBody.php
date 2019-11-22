<?php
/*
 * Copyright (c) 2019, Tribal Limited
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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


$bodyTag = '<body class="desktop no_js '. \ze\cache::browserBodyClass();

//Add the Admin Toolbar in Admin Mode
if (\ze\priv::check()) {
	if ($includeAdminToolbar && \ze::$cID) {
		//Set the current Admin Toolbar tab in Admin Mode
		$toolbars = [];
		\ze\admin::pageBodyAdminClass($bodyTag, $toolbars);
	} else {
		$includeAdminToolbar = false;
		
		if ($extraClassNames != 'zenario_showing_preview') {
			$bodyTag .= ' zenario_adminLoggedIn';
		}
		
		$bodyTag .= ' zenario_pageMode_preview zenario_pageModeIsnt_edit zenario_pageModeIsnt_edit_disabled zenario_pageModeIsnt_menu zenario_pageModeIsnt_layout zenario_pageModeIsnt_item';
	}
} else {
	$includeAdminToolbar = false;
}

if (\ze::$userId) {
	$bodyTag .= ' zenario_userLoggedIn';
}

$toolbarAttr = '';
if ($includeAdminToolbar) {
	//Make the Admin Toolbar float above the page.
	$attributes .= ' style="margin-top: 129px;"';
	$toolbarAttr = ' style="height: 129px; width: 100%; position: fixed; top: 0px; left: 0px; margin: 0px; z-index: 1000;"';
}

echo "\n", $bodyTag;

if ($extraClassNames !== '') {
	echo ' '. htmlspecialchars($extraClassNames);
}

echo '"', $attributes, '>';

$showingPreview =
	$extraClassNames == 'zenario_showing_preview'
 || $extraClassNames == 'zenario_showing_plugin_preview';

if (!$showingPreview) {
	//If this page is a normal webpage being displayed by index.php, output the "Start of body" slot
	if (\ze::$cID) {
		echo "\n", \ze::setting('sitewide_body');
		if (ze\cookie::canSet('analytics') && ze::setting('sitewide_analytics_html_location') == 'body') {
			echo "\n", ze::setting('sitewide_analytics_html');
		}
		if (ze\cookie::canSet('social_media') && ze::setting('sitewide_social_media_html_location') == 'body') {
			echo "\n", ze::setting('sitewide_social_media_html');
		}
	}
}

//Add more classes to the browser body class using a short JavaScript function.
echo '
<script type="text/javascript">
var URLBasePath = "', \ze\escape::js(\ze\link::protocol(). ($_SERVER["HTTP_HOST"] ?? false) . SUBDIRECTORY), '";
', file_get_contents(CMS_ROOT. 'zenario/js/body.min.js');

	//Use the "no_js" class if we're showing a preview
	if ($showingPreview) {
		echo '
			zenarioL.set(1, "js", "no_js");';
	}
	
echo '</script>';

if ($includeAdminToolbar) {
	\ze\admin::pageBodyAdminToolbar($toolbars, $toolbarAttr);
}