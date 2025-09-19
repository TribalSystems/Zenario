<?php
/*
 * Copyright (c) 2025, Tribal Limited
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


require 'visitorheader.inc.php';

$url =
	\ze\link::protocol().
	\ze\link::adminDomain(). SUBDIRECTORY.
	'admin.php?'. http_build_query($_GET);

//Add the logo
$logoURL = $logoWidth = $logoHeight = false;
if (ze::setting('admin_link_logo') == 'custom'
 && (ze\image::specialImageLink($logoWidth, $logoHeight, $logoURL, ze::setting('admin_link_custom_logo'), 50, 50, $mode = 'resize', $offset = 0, $retina = true))) {

	if (strpos($logoURL, '://') === false) {
		$logoURL = \ze\link::absolute(). $logoURL;
	}
} else {
	$logoURL = \ze\link::absolute(). 'zenario/admin/images/zenario-logo-diamond.svg';
	$logoWidth = 25;
	$logoHeight = 19;
}

$offset = (int) ze::setting('admin_link_logo_offset', $useCache = true, $default = 30);
$pos = ze::setting('admin_link_logo_pos', $useCache = true, $default = 'allt allr');

if (substr($pos, 0, 4) == 'allb') {
	$style = 'bottom:'. $offset. 'px';
} else {
	$style = 'top:'. $offset. 'px';
}

echo '
	<div class="admin_login_link ', htmlspecialchars($pos), '" style="', htmlspecialchars($style), '">
		<a
			class="clear_admin_cookie"
			href="zenario/cookies.php?clear_admin_cookie=1"
			onclick="
				return confirm(\'', (\ze\admin::phrase('Remove your admin login link?\n\nThis will delete your admin cookie.\n\nGo to /admin to sign in next time!')), '\');
			"
		></a>
		<a
			class="admin_login_link"
			href="', htmlspecialchars($url), '"
			target="_top"
			onclick="
				var requests,
					conductorSlot = zenario_conductor.getSlot();
				if (conductorSlot && conductorSlot.exists) {
					requests = zenario_conductor.request(conductorSlot, \'refresh\');
					zenario.goToURL(zenario.linkToItem(zenario.cID, zenario.cType, requests, true));
					return false;
				}
				return true;
			"
		>
			<img src="', htmlspecialchars($logoURL), '" width="', (int) $logoWidth, '" height="', (int) $logoHeight, '" alt="', \ze\admin::phrase('Admin login logo'), '"/><br/>
			', \ze\admin::phrase('Login'), '
		</a>
	</div>';
