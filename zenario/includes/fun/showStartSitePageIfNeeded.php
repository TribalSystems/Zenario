<?php
/*
 * Copyright (c) 2016, Tribal Limited
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

startSession();

$logoURL = $logoWidth = $logoHeight = false;
if (cms_core::$lastDB
 && setting('brand_logo') == 'custom'
 && ($result = sqlSelect("SHOW COLUMNS IN ". DB_NAME_PREFIX. "files WHERE Field = 'thumbnail_64x64_width'"))
 && ($dbAtRecentRevision = sqlFetchRow($result))
 && (imageLink($logoWidth, $logoHeight, $logoURL, setting('custom_logo'), 500, 250))) {
	$logoURL = $logoURL;
} else {
	$logoURL = 'zenario/admin/images/zenario_logo.png';
	$logoWidth = 142;
	$logoHeight = 57;
}


if ($reportDBOutOfDate && checkPriv()) {
	$errorMessage = '<p>This site is currently unavailable because a major database update needs to be applied.</p><p>Please go to <a href="[[admin_link]]">zenario/admin/</a> to apply the update.</p>';
	$adminLink = 'zenario/admin/welcome.php';

//If there's a specifc page in the request, keep the admin on that page after they log in
} elseif (request('cID')) {
	$errorMessage = setting('site_disabled_message');
	$adminLink = 'zenario/admin/welcome.php?cID='. rawurlencode(request('cID')). '&cType='. rawurlencode(request('cType'));

//If you need to enable a language, the "here" link should point to the languages panel
} elseif (!checkRowExists('languages', array())) {
	$errorMessage = setting('site_disabled_message');
	$adminLink = 'zenario/admin/welcome.php?og=zenario__languages/panels/languages';

//If you need to enable your site, the "here" link should point to the "Set-up" panel to do that
} else {
	$errorMessage = setting('site_disabled_message');
	$adminLink = 'zenario/admin/welcome.php?og=zenario__administration/panels/site_settings//site_disabled';
}

$errorMessage = adminPhrase($errorMessage, array('admin_link' => htmlspecialchars($adminLink)));

//Workaround for a bug where TinyMCE can add "zenario/admin" into the login link a second time
$errorMessage = str_replace('zenario/admin/zenario/admin', 'zenario/admin', $errorMessage);


echo '
<html>
	<head>
		<title>', setting('site_disabled_title'), '</title>
		<style type="text/css">
			div, p {
				color: #9a9a9a; font-family: Verdana,Tahoma,Arial,Helvetica,sans-serif;
			}
			
			a {
				color: #2d768a;
			}

			.x-small {
				font-size: .7em
			}
			
			.small {
				font-size: .85em
			}
			
			.medium {
				font-size: 1em
			}
			
			.large {
				font-size: 1.3em
			}
			
			.x-large {
				font-size: 2em
			}
		</style>
	</head>
	<body width="100%" height="100%" style="margin: 0;">
		<div style="padding:auto; margin:auto; text-align: center; position: absolute; top: 35%; width: 100%;">
			<img
				src="', htmlspecialchars($logoURL), '"
				width="', (int) $logoWidth, '"
				height="', (int) $logoHeight, '"
				border="0"
				title="', adminPhrase('Pit canary. Image broken? Check that you have the .htaccess file in your site home directory, and that Apache is reading it!'), '"
				alt="', adminPhrase('Pit canary. Image broken? Check that you have the .htaccess file in your site home directory, and that Apache is reading it!'), '"
			/>
			<div>
				', $errorMessage, '
			</div>
		</div>
	</body>
</html>';


if ($reportDBOutOfDate && !checkPriv()) {
	reportDatabaseError('
This site is currently unavailable because a major database update needs to be applied.
Please go to '. absCMSDirURL(). 'zenario/admin/ to apply the update.');
}

