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



require 'visitorheader.inc.php';
require CMS_ROOT. 'zenario/includes/admin.inc.php';
header('Content-Type: text/html; charset=UTF-8');



echo
'<!DOCTYPE HTML>
<html>
<head>
	<title>', adminPhrase('Loading Wizard...'), '</title>';



//checkForChangesInCssJsAndHtmlFiles();
$v = ifNull(setting('css_js_version'), ZENARIO_VERSION. '.'. LATEST_REVISION_NO);
CMSWritePageHead('', 'wizard', false);

echo '
	<link rel="stylesheet" type="text/css" href="styles/admin_welcome.min.css?v=', $v, '" media="screen" />
	<style type="text/css">
		
		#welcome,
		#no_something,
		#no_cookies,
		#no_script {
			display: none;
		}
		
		body.no_js #no_something {
			display: block;
		}
		
		body.no_js #no_script {
			display: inline;
		}
	</style>
</head>';


CMSWritePageBody('', false);
CMSWritePageFoot('', 'wizard', false, false);

if ((file_exists(CMS_ROOT. ($logoURL = 'wizard-logo.png')))
 || (file_exists(CMS_ROOT. ($logoURL = 'zenario_custom/wizard-logo.png')))
 && ($imageDetails = getimagesize(CMS_ROOT. $logoURL))
 && (!empty($imageDetails[1]))) {
	$logoWidth = $imageDetails[0];
	$logoHeight = $imageDetails[1];
	$logoURL = '../'. $logoURL;
	
} else {
	$logoURL = 'admin/images/zenario_logo.png';
	$logoWidth = 142;
	$logoHeight = 57;
}




echo '
<script type="text/javascript" src="js/wizard.min.js?v=', $v, '"></script>
<script type="text/javascript">
	zenarioW.getRequest = ', json_encode($_GET), ';
	
	$(document).ready(function () {
		try {
			zenarioA.checkCookiesEnabled().after(function(cookiesEnabled) {
				if (cookiesEnabled) {
					zenarioW.start();
				} else {
					get("no_something").style.display = "block";
					get("no_cookies").style.display = "inline";
				}
			});
		} catch (e) {
			get("no_something").style.display = "block";
			get("no_cookies").style.display = "inline";
		}
	});
</script>';


//Add JavaScript libraries for any modules that are running for this path
if ($requestedPath = ifNull(request('name'), request('path'))) {
	$modules = array_unique(getRowsArray('tuix_file_contents', 'module_class_name', array('type' => 'wizards', 'path' => $requestedPath)));
	
	if (!empty($modules)) {
		$moduleIds = getRowsArray('modules', 'id', array('status' => 'module_running', 'class_name' => $modules));
		
		if (!empty($moduleIds)) {
			echo '
			<script type="text/javascript" src="js/plugin.wrapper.js.php?v=', $v, '&amp;ids=', inEscape($moduleIds, 'numeric'), '&amp;wizard=1"></script>';
		}
	}
}


if (strpos(httpUserAgent(), 'MSIE 6') !== false) {
	echo '
		<style type="text/css">
			html {
				overflow: hidden;
			}
		</style>';
}

echo '
<!--
<div id="zenario_now_installing" class="zenario_now" style="display: none;">
	<h1 style="text-align: center;">', adminPhrase('Now Installing'), '
		<div class="bounce1"></div>
  		<div class="bounce2"></div>
  		<div class="bounce3"></div>
  	</h1>
</div>
-->
<div id="welcome_outer">
	<div id="welcome" class="welcome">
		<div class="zenario_version"><p class="version">
			', adminPhrase('Zenario [[version]]', array('version' => getCMSVersionNumber())), '
		</p></div>
		<div class="welcome_wrap">
			<div class="welcome_inner">
		
				<div class="welcome_header">
					<div class="welcome_header_logo">
						<img src="', htmlspecialchars($logoURL), '" width="', (int) $logoWidth, '" height="', (int) $logoHeight, '"/>
					</div>
				</div>
	
				<div>
					<div id="zenario_abtab"></div>
				</div>
			</div>
		</div>
	</div>
	<div id="no_something" class="welcome">
		<div class="zenario_version"><p class="version">
			', adminPhrase('Zenario [[version]]', array('version' => getCMSVersionNumber())), '
		</p></div>
		<div class="welcome_wrap">
			<div class="welcome_inner">
		
				<div class="welcome_header">
					<div class="welcome_header_logo">
						<img src="', htmlspecialchars($logoURL), '" width="', (int) $logoWidth, '" height="', (int) $logoHeight, '"/>
					</div>
				</div>
	
				<div>
					<h1>', adminPhrase('Welcome to Zenario'), '</h1>
					<p id="no_cookies">',
						adminPhrase("Unable to start a session! We cannot log you in at the moment.<br/><br/>Please check that cookies are enabled in your browser.<br/><br/>If you've enabled cookies and this message persists, please alert your system administrator. There may be a problem with the caching or session storage on your server."),
					'</p>
					<p id="no_script">',
						adminPhrase('Please enable JavaScript in your browser to continue.'),
					'</p>
				</div>
			</div>
		</div>
	</div>
</div>';


?>
</body>
</html>