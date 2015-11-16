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

//Check to see if this file is being directly accessed, when the index.php file in the directory below should be used to access this file
if (file_exists('visitorheader.inc.php') && file_exists('../index.php')) {
	header('Location: ../');
	exit;

//Check to see if the config file has been created, and if not, link to the installer.
} elseif (!file_exists('zenario_siteconfig.php') || filesize('zenario_siteconfig.php') < 20) {
	echo '
		<html>
		  <head>
			<title>Welcome to Zenario</title>
		  </head>
		  <body>
			<script type="text/javascript">
				document.location.href = "zenario/admin/welcome.php";
			</script>
			<h1>Welcome to Zenario</h1>
			<p>A new zenario-powered website is coming soon at this location.</p>
			<p style="font-size: 70%">If you own this site and wish to continue with the installation please enable JavaScript to continue.</p>
		  </body>
		</html>';
	exit;

} elseif (isset($_GET['method_call'])) {
	//RSS feeds are handled by ajax.php
	if ($_GET['method_call'] == 'showRSS') {
		chdir('zenario');
		require 'ajax.php';
		exit;
	
	//Sitemaps are handled by Storekeeper
	} elseif ($_GET['method_call'] == 'showSitemap') {
		chdir('zenario/admin');
		require 'ajax.php';
		exit;
	}
}

require 'basicheader.inc.php';
startSession();

//Run pre-load actions
foreach (cms_core::$editions as $className => $dirName) {
	if ($action = moduleDir($dirName, 'actions/index.pre_load.php', true)) {
		require $action;
	}
}

define('CHECK_IF_MAJOR_REVISION_IS_NEEDED', true);
require CMS_ROOT. 'zenario/visitorheader.inc.php';
zenarioInitialiseTwig();


if (checkPriv()) {
	require CMS_ROOT. 'zenario/adminheader.inc.php';
	checkForChangesInCssJsAndHtmlFiles();
	
	//setAdminSession($_SESSION['admin_userid'], $_SESSION['admin_global_id']);

//Don't directly show a Content Item if the site is disabled
} elseif (!setting('site_enabled')) {
	showStartSitePageIfNeeded();
	exit;
}



//Attempt to get this page.
$cID = $cType = $content = $version = $redirectNeeded = $aliasInURL = false;
resolveContentItemFromRequest($cID, $cType, $redirectNeeded, $aliasInURL);

if ($redirectNeeded && empty($_POST) && !($redirectNeeded == 302 && checkPriv())) {
	
	//When fixing the language code in the URL, make sure we redirect using the full path
	//as the language code might be in the domain/subdomain.
	$fullPath = $redirectNeeded == 302;
	
	$requests = $_GET;
	unset($requests['cID']);
	unset($requests['cType']);
	
	header('location: '. ifNull(linkToItem($cID, $cType, $fullPath, $requests), SUBDIRECTORY), true, $redirectNeeded);
	exit;
}

//Run pre-header actions
foreach (cms_core::$editions as $className => $dirName) {
	if ($action = moduleDir($dirName, 'actions/index.pre_header.php', true)) {
		require $action;
	}
}
unset($className);
unset($dirName);
unset($action);



//Look up more details on the content item we are going to show
$status = getShowableContent($content, $version, $cID, $cType, request('cVersion'));

//If a page was requested but couldn't be shown...
if ($status === 'no_permission') {
	//Show the no-access if this page is not accessible
	header('HTTP/1.0 403 Forbidden');
	langSpecialPage('zenario_no_access', $cID, $cType);
	$status = getShowableContent($content, $version, $cID, $cType);

} elseif ($status === 'notLoggedIn') {
	//Set the destination so the Visitor can come back here when logged in
	$_SESSION['destCID'] = $content['id'];
	$_SESSION['destCType'] = $content['type'];
	$_SESSION['destURL'] = httpOrHttps(). httpHost(). $_SERVER['REQUEST_URI'];
	$_SESSION['destTitle'] = $version['title'];
	cms_core::$canCache = false;
	
	//Show the login page
	header('HTTP/1.0 401 Authentication Required');
	langSpecialPage('zenario_login', $cID, $cType);
	$status = getShowableContent($content, $version, $cID, $cType);

} elseif (!$status) {
	//Show the no-access if this page does not exist
	header('HTTP/1.0 404 Not Found');
	langSpecialPage('zenario_not_found', $cID, $cType);
	$status = getShowableContent($content, $version, $cID, $cType);
	//Log error if errors module is running
	if (inc('zenario_error_log')) {
		$httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$requestURI = $_SERVER['REQUEST_URI'];
		$URI = explode('/', $requestURI);
		$pageAlias = end($URI);
		zenario_error_log::log404Error($pageAlias, $httpReferer);
	}
}

//Try to go to the home page as a fallback if the Not Found/No Access/Login pages could not be used above
if (!$content || !$version || $status !== true) {
	$cID = cms_core::$homeCID;
	$cType = cms_core::$homeCType;
	$status = getShowableContent($content, $version, $cID, $cType);
}

//If none of the above gave us a page to show, the site probably isn't set up correctly, so show the installation message
if (!$content || !$version || $status !== true) {
	showStartSitePageIfNeeded();
	exit;
}
unset($cID);
unset($cType);
unset($menu);


setShowableContent($content, $version);


//Run post-display actions
foreach (cms_core::$editions as $className => $dirName) {
	if ($action = moduleDir($dirName, 'actions/index.pre_get_contents.php', true)) {
		require $action;
	}
}


$hideLayout = false;
$specificSlot = false;
$specificInstance = false;

if (!empty($_REQUEST['method_call'])
 && ($_REQUEST['method_call'] == 'showSingleSlot' || $_REQUEST['method_call'] == 'showIframe')
 && (request('instanceId') || request('slotName'))) {
	$specificSlot = request('slotName');
	$specificInstance = request('instanceId');
	$hideLayout = $specificSlot && request('hideLayout');
}

getSlotContents(
	cms_core::$slotContents,
	cms_core::$cID, cms_core::$cType, cms_core::$cVersion,
	cms_core::$layoutId, cms_core::$templateFamily, cms_core::$templateFileBaseName,
	$specificInstance, $specificSlot);
useGZIP(setting('compress_web_pages'));

//Run post-display actions
foreach (cms_core::$editions as $className => $dirName) {
	if ($action = moduleDir($dirName, 'actions/index.post_get_contents.php', true)) {
		require $action;
	}
}


$canonicalURL = linkToItem(cms_core::$cID, cms_core::$cType, true, '', cms_core::$alias, true, true, true);


$specialPage = isSpecialPage(cms_core::$cID, cms_core::$cType);
if ($validDestURL = !$specialPage || $specialPage == 'zenario_home') {
	$_SESSION['destCID'] = cms_core::$cID;
	$_SESSION['destCType'] = cms_core::$cType;
	$_SESSION['destURL'] = $canonicalURL;
	$_SESSION['destTitle'] = cms_core::$pageTitle;
}




echo 
'<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="', $_SESSION["user_lang"], '" lang="', $_SESSION["user_lang"], '">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>', htmlspecialchars(cms_core::$pageTitle), '</title>
<link rel="canonical" href="', htmlspecialchars($canonicalURL), '"/>';

//If we were to include a <base> tag, this would be a more reliable way of handling relative URLs
//on pages with slashes in their alias. However using the <base> tag will break links to #anchors that
//may be on the page.
//echo '
//<base href="', htmlspecialchars(absCMSDirURL()), '" />';

// Add hreflang tags
if (getNumLanguages() > 1) {
	// If there are no important get requests
	$getRequests = false;
	foreach(cms_core::$importantGetRequests as $getRequest => $defaultValue) {
		if (isset($_GET[$getRequest]) && $_GET[$getRequest] != $defaultValue) {
			$getRequests = true;
			break;
		}
	}
	if (!$getRequests) {
		$sql = "
			SELECT c.id, c.type, c.language_id
			FROM ". DB_NAME_PREFIX. "content_items AS c
			INNER JOIN ". DB_NAME_PREFIX. "translation_chains AS tc
			   ON c.equiv_id = tc.equiv_id
			  AND c.type = tc.type
			WHERE tc.privacy = 'public'
			  AND c.equiv_id = ". (int) cms_core::$equivId. "
			  AND c.type = '". sqlEscape(cms_core::$cType). "'
			  AND c.status IN ('published_with_draft', 'published')";
		$result = sqlSelect($sql);
		if (sqlNumRows($result) > 1) {
			while($row = sqlFetchAssoc($result)) {
				$pageLink = linkToItem($row['id'], $row['type'], true, '', false, true, true, true);
				echo '
<link rel="alternate" href="'. htmlspecialchars($pageLink). '" hreflang="'. htmlspecialchars($row['language_id']). '">';
			}
		}
	}
}

echo
'
<meta name="description" content="', htmlspecialchars(cms_core::$description), '" />
<meta name="keywords" content="', htmlspecialchars(cms_core::$keywords), '" />
<meta name="generator" content="Zenario ', getCMSVersionNumber(), '" />';

CMSWritePageHead('zenario/');
echo "\n", setting('sitewide_head'), "\n</head>";


$contentItemDiv =
	"\n".
	'<div id="zenario_citem" class="';

if ($specialPage) {
	$contentItemDiv .= htmlspecialchars($specialPage). ' ';
}

$contentItemDiv .= 'lang_'. preg_replace('/[^\w-]/', '', cms_core::$langId);

if (cms_core::$itemCSS) {
	$contentItemDiv .= ' '. htmlspecialchars(cms_core::$itemCSS);
}

$contentItemDiv .= '">';


$templateDiv =
	"\n".
	'<div id="zenario_layout" class="'.
		'zenario_'. htmlspecialchars(cms_core::$cType). '_layout';

if (cms_core::$templateCSS) {
	$templateDiv .= ' '. htmlspecialchars(cms_core::$templateCSS);
}

$templateDiv .= '">';


$skinDiv =
	"\n".
	'<div id="zenario_skin" class="zenario_skin';

if (cms_core::$skinCSS) {
	$skinDiv .= ' '. htmlspecialchars(cms_core::$skinCSS). '';
}

$skinDiv .= '">';






//Functionality for only showing one Plugin in a slot
if ($specificInstance || $specificSlot) {
	CMSWritePageBody('', false);
	
	if ($hideLayout && $specificSlot) {
		slot($specificSlot, 'grid');
	} else {
		echo $skinDiv, $templateDiv, $contentItemDiv;
		require CMS_ROOT. cms_core::$templatePath. cms_core::$templateFilename;
		echo "\n", '</div></div></div>';
	}
	
	
	echo '
		<script type="text/javascript">
			window.zenario_inIframe = true;
		</script>';
	
	CMSWritePageFoot('zenario/', false, false, false);

//Show a preview in Admin Mode in an iframe
} elseif (!empty($_GET['_sk_preview']) && checkPriv()) {
	CMSWritePageBody('', false, true);
	echo $skinDiv, $templateDiv, $contentItemDiv;
	require CMS_ROOT. cms_core::$templatePath. cms_core::$templateFilename;
	
	echo "\n", '</div></div></div>';
	
	echo '
		<script type="text/javascript" src="zenario/libraries/mit/jquery/jquery.min.js?v=', ZENARIO_CMS_VERSION, '"></script>
		<script type="text/javascript">
			$(\'*\').each(function(i, el) {
				el.onclick = function() { return false; };
			});
		</script>';
	

//Normal functionality; show the whole page
} else {
	CMSWritePageBody();
	showCookieConsentBox();
	echo $skinDiv, $templateDiv, $contentItemDiv;
	
	if (file_exists(CMS_ROOT. ($file = cms_core::$templatePath. cms_core::$templateFilename))) {
		require CMS_ROOT. $file;
		checkSlotsWereUsed();
	
	} else {
		echo 
			'<div style="padding:auto; margin:auto; text-align: center; position: absolute; top: 35%; width: 100%;">',
				htmlspecialchars($msg = adminPhrase('Template File "[[file]]" is missing. ', array('file' => $file))),
				'<a href="zenario/admin/organizer.php">Go to Organizer</a>',
			'</div>';
		
		if (!checkPriv() && defined('DEBUG_SEND_EMAIL') && DEBUG_SEND_EMAIL === true) {
			reportDatabaseError($msg);
		}
	}
	
	echo "\n", '</div></div></div>';
	CMSWritePageFoot('zenario/');
}


echo "\n", setting('sitewide_foot'), "\n";


//Run post-display actions
foreach (cms_core::$editions as $className => $dirName) {
	if ($action = moduleDir($dirName, 'actions/index.post_display.php', true)) {
		require $action;
	}
}

echo "\n</body>\n</html>";


exit;