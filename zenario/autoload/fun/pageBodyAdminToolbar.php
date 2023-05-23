<?php
/*
 * Copyright (c) 2023, Tribal Limited
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

//Work out the links to open Organizer, and to log out. Make these merge fields.
$zenarioATLinks = [
	'logout' => \ze\admin::logoutOnclick(),
	'tag' => \ze::$cType. '_'. \ze::$cID,
	'organizer' => 'organizer.php?fromCID='. \ze::$cID. '&fromCType='. \ze::$cType,
	'diagnostics' => 'admin.php?task=diagnostics&'. http_build_query(\ze\link::importantGetRequests(true))
];

if ($_REQUEST['zenario_sk_return'] ?? false) {
	$zenarioATLinks['organizer_hash'] = $_REQUEST['zenario_sk_return'] ?? false;
} else {
	$zenarioATLinks['organizer_hash'] = 'zenario__content/panels/content/refiners/content_type//'. \ze::$cType. '//'. \ze::$cType. '_'. \ze::$cID;
}


//Get the HTML code from the microtemplates to get the basic HTML structure of the Admin Toolbar roughly right.
$toolbarTempHTML = file_get_contents(\ze::moduleDir('zenario_common_features', 'admin_microtemplates/zenario_toolbar.html'));

//Add in the merge fields above
$searches = [];
$replaces = [];
foreach ($zenarioATLinks as $key => $value) {
	$searches[] = '{{zenarioATLinks.'. $key. '}}';
	$searches[] = '{{zenarioATLinks.'. $key. '|e}}';
	$searches[] = '{{zenarioATLinks.'. $key. '|escape}}';
	$replaces[] = $value;
	$replaces[] = htmlspecialchars($value);
	$replaces[] = htmlspecialchars($value);
}
$toolbarTempHTML = str_replace($searches, $replaces, $toolbarTempHTML);

//Remove all other merge fields.
$toolbarTempHTML = preg_replace(['@\{\{.*?\}\}@', '@\{\%.*?\%\}@', '@\<\%.*?\%\>@'], '', $toolbarTempHTML);

//Don't initially show the "bug" icon for the dev tools
$toolbarTempHTML = str_replace('zenario_debug', 'zenario_debug_hidden', $toolbarTempHTML);


//Output the temporary HTML to the page, and also note down what the merge fields were for use later in JavaScipt
echo '
<div id="zenario_at_wrap" class="zenario_at zenario_toolbar_header"'. $toolbarAttr. '>
	', $toolbarTempHTML, '
</div>
<script type="text/javascript">
	var zenarioATLinks = ', json_encode($zenarioATLinks). ';
</script>';
