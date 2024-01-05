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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


$bodyTag = '<body class="desktop no_js '. \ze\cache::browserBodyClass();
	//N.b. if you change this line above, you'll also need to edit the following two files:
		//zenario/includes/index.pre_load.inc.php
		//zenario/includes/index.post_display.inc.php
	//...and change the line there as well, as they have str_replace()s that look for this text!


//Add the Admin Toolbar in Admin Mode
if (\ze\priv::check()) {
	if ($includeAdminToolbar && ze::$cID) {
		//Set the current Admin Toolbar tab in Admin Mode
		$toolbars = [];
		\ze\admin::pageBodyAdminClass($bodyTag, $toolbars);
	} else {
		$includeAdminToolbar = false;
		
		if ($extraClassNames != 'zenario_showing_preview') {
			$bodyTag .= ' zenario_adminLoggedIn';
		}
		
		$bodyTag .= ' zenario_pageMode_preview zenario_pageModeIsnt_edit zenario_pageModeIsnt_edit_disabled zenario_pageModeIsnt_menu zenario_pageModeIsnt_layout';
	}
	
	//Add a class when Google Maps API key is available
	if (ze::setting('google_maps_api_key')) {
		$bodyTag .= ' zenario_adminGoogleMapsAvailable';
	} else {
		$bodyTag .= ' zenario_adminGoogleMapsNotAvailable';
	}
	
	if ($includeAdminToolbar) {
		$bodyTag .= ' zenario_adminToolbarShown';
	} else {
		$bodyTag .= ' zenario_adminToolbarHidden';
	}
	
} else {
	$includeAdminToolbar = false;
}

if (ze::$userId) {
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

//Add more classes to the browser body class using a short JavaScript function.
echo '
<script type="text/javascript">
', file_get_contents(CMS_ROOT. 'zenario/js/body.min.js');

if (ze::setting('mod_rewrite_slashes')) {
	echo file_get_contents(CMS_ROOT. 'zenario/js/body.anchor-fix.min.js');
}

echo
'})(document,window,"', \ze\escape::js(ze::$cType), '",', (int) ze::$layoutId, ',', (int) ze::$skinId, ',"', \ze\escape::js(\ze\link::protocol(). ($_SERVER["HTTP_HOST"] ?? false) . SUBDIRECTORY), '");';

	//Use the "no_js" class if we're showing a preview
	if ($showingPreview) {
		echo '
			zenarioL.set(1, "js", "no_js");';
	}
	
echo '
</script>';

if (!$showingPreview) {
	//If this page is a normal webpage being displayed by index.php, output the "Start of body" slot
	if (ze::$cID) {
		ze\content::sitewideHTML('sitewide_body');
		if (ze\cookie::canSet('analytics') && ze::setting('sitewide_analytics_html_location') == 'body') {
			ze\content::sitewideHTML('sitewide_analytics_html');
		}
		if (ze\cookie::canSet('social_media') && ze::setting('sitewide_social_media_html_location') == 'body') {
			ze\content::sitewideHTML('sitewide_social_media_html');
		}
	}
}

if ($includeAdminToolbar) {
	\ze\admin::pageBodyAdminToolbar($toolbars, $toolbarAttr);
}