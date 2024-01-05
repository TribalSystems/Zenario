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

\ze\cookie::startSession();


//If the site is not yet correctly set up, display the logo and a message
//if someone tries to access it.

$logoURL = $logoWidth = $logoHeight = false;
if (ze::$dbL
 && ze::setting('brand_logo') == 'custom'
 && (ze\file::imageLink($logoWidth, $logoHeight, $logoURL, ze::setting('custom_logo'), 500, 250, 'resize', $offset = 0, $retina = true))) {
	$logoURL = $logoURL;
} else {
	$logoURL = 'zenario/admin/images/zenario-logo-black.svg';
	$logoWidth = 142;
	$logoHeight = 57;
}


$errorTitle = ze::setting('site_disabled_title');


//A couple of specific messages
if ($mode == 'reportDBOutOfDate' && \ze\priv::check()) {
	$errorMessage = '<p>This site is currently unavailable because a major database update needs to be applied.</p><p>Please go to <a href="[[admin_link]]">/admin</a> to apply the update.</p>';
	$adminLink = \ze\link::absolute(). 'admin.php';

} elseif ($mode == 'noStagingModeInAdminMode') {
	$errorMessage = '<p>You can\'t view this page in staging mode while logged in as an administrator. Either log out or open an "incognito" browser window.</p>';
	$adminLink = \ze\link::absolute(). 'admin.php';

//If there's a specifc page in the request, keep the admin on that page after they log in
} elseif (ze::request('cID')) {
	$errorMessage = ze::setting('site_disabled_message');
	$adminLink = \ze\link::absolute(). 'admin.php?cID='. rawurlencode(ze::request('cID')). '&cType='. rawurlencode(ze::request('cType'));

//If you need to enable a language, the "here" link should point to the languages panel
} elseif (!\ze\row::exists('languages', [])) {
	$errorMessage = ze::setting('site_disabled_message');
	$adminLink = \ze\link::absolute(). 'admin.php?og=zenario__languages/panels/languages';

//If you need to enable your site, the "here" link should point to the "Set-up" panel to do that
} else {
	$errorMessage = ze::setting('site_disabled_message');
	$adminLink = \ze\link::absolute(). 'admin.php?og=zenario__organizer/panels/start_page';
}


//If the error title and error message are ever missing for whatever reason,
//instead of displaying a blank message, use their default values.
if (!$errorTitle || empty(trim($errorTitle))) {
	$errorTitle = 'Welcome';
}
if (!$errorMessage || empty(trim($errorMessage))) {
	$errorMessage = '<p>A site is being built at this location.</p><p><span class="x-small">If you are a site administrator please <a href="[[admin_link]]">click here</a> to manage your site.</span></p>';
}

$errorMessage = \ze\admin::phrase($errorMessage, ['admin_link' => htmlspecialchars($adminLink)]);


echo '
<html>
	<head>
		<title>', htmlspecialchars($errorTitle), '</title>
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
				title="', \ze\admin::phrase('Pit canary. Image broken? Check that you have the .htaccess file in your site home directory, and that Apache is reading it!'), '"
				alt="', \ze\admin::phrase('Pit canary. Image broken? Check that you have the .htaccess file in your site home directory, and that Apache is reading it!'), '"
			/>
			<div>
				', $errorMessage, '
			</div>
		</div>
	</body>
</html>';


//If a visitor has discovered a site that's missing database updates, warn the site admin.
if ($mode == 'reportDBOutOfDate' && !\ze\priv::check()) {
	\ze\db::reportError('Database update needed at',
'This site is currently unavailable because a major database update needs to be applied.
Please go to '. \ze\link::absolute(). 'admin to apply the update.');
}
