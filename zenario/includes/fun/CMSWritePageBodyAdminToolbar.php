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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


$canonicalURL = linkToItem(cms_core::$cID, cms_core::$cType, false, '', cms_core::$alias, true, true);

if (request('zenario_sk_return')) {
	$skLink = request('zenario_sk_return');
} else {
	$skLink = 'zenario__content/panels/content_types/item//'. cms_core::$cType. '//item//'. cms_core::$langId. '//'. cms_core::$cType. '_'. cms_core::$cID;
	
}

//Show some tabs while the page is loading.
//These won't be the real tabs, but we'll copy some of the basic logic the Admin Toolbar uses to come up with a close copy.
//I'm also attempting to use the HTML code from the microtemplates to get the basic HTML structure roughly right.
$toolbarTempHTML = '';
$tabsTempHTML = file_get_contents(moduleDir('zenario_common_features', 'js/microtemplates/zenario_toolbar_tab.html'));

foreach ($toolbars as $toolbar => $details) {
	$toolbarTempHTML .= str_replace('[[m.label]]', htmlspecialchars($details['label']), $tabsTempHTML);
}

$toolbarTempHTML =
	str_replace(array('{{', '{%', '<%'), '<!--',
		str_replace(array('}}', '%}', '%>'), '-->',
			str_replace("zenarioA.microTemplate('zenario_toolbar_tab", '-->'. $toolbarTempHTML. '<!--',
				file_get_contents(moduleDir('zenario_common_features', 'js/microtemplates/zenario_toolbar.html'))
)));

//Don't show the "bug" icon from the placeholder HTML if this admin doesn't have permission to see the dev tools
if (!checkPriv('_PRIV_VIEW_DEV_TOOLS')) {
	$toolbarTempHTML = str_replace('zenario_debug', 'zenario_debug_hidden', $toolbarTempHTML);
}





//Add the Admin Toolbar in Admin Mode
echo '
<div id="zenario_at_wrap" class="zenario_at zenario_toolbar_header"'. $toolbarAttr. '>
	<div id="zenario_at_organizer_link">
		<a href="zenario/admin/organizer.php?fromCID=', cms_core::$cID, '&amp;fromCType=', cms_core::$cType, '#', htmlspecialchars($skLink), '" id="zenario_organizer_button" title="', adminPhrase('Go to the Organizer administration back-end for this site'), '" data-tooltip-options="{tooltipClass:\'zenario_admin_tooltip\'}">Organizer</a>
	</div>
	<div id="zenario_at_logout_link">
		<a id="zenario_logout_button" ', adminLogoutOnclick(), ' title="', adminPhrase('Logout'), '" data-tooltip-options="{tooltipClass:\'zenario_admin_tooltip\'}"></a>
	</div>
	<div id="zenario_at">', $toolbarTempHTML, '</div>
</div>';
