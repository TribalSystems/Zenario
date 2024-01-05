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

$methodCall = $_REQUEST['method_call'] ?? false;

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
				document.location.href = "admin.php";
			</script>
			<h1>Welcome to Zenario</h1>
			<p>A new zenario-powered website is coming soon at this location.</p>
			<p style="font-size: 70%">If you own this site and wish to continue with the installation please enable JavaScript to continue.</p>
		  </body>
		</html>';
	exit;


//RSS feeds and Sitemaps are handled by different scripts
} elseif ($methodCall !== false) {
	if ($methodCall == 'showRSS') {
		chdir('zenario');
		require 'ajax.php';
		exit;
	
	//Sitemaps are handled by Storekeeper
	} elseif ($methodCall == 'showSitemap') {
		chdir('zenario');
		require 'sitemap.php';
		exit;
	}
}

require 'basicheader.inc.php';

ze\cookie::startSession();


//Run pre-load actions
//Set the cookie consent cookie if we see cookies_accepted in the visitor's session
if (!empty($_SESSION['cookies_accepted'])) {
	ze\cookie::setConsent();
	unset($_SESSION['cookies_accepted']);
}

if (!empty($_SESSION['sensitive_content_message_accepted'])) {
	ze\cookie::setSensitiveContentMessageConsent();
	unset($_SESSION['sensitive_content_message_accepted']);
}

if (!empty($_COOKIE['country_id']) && !empty($_COOKIE['user_lang'])) {
	if (empty($_SESSION['country_id']) || empty($_SESSION['user_lang'])) {
		$_SESSION['country_id'] = $_COOKIE['country_id'];
		$_SESSION['user_lang'] = $_COOKIE['user_lang'];
	}
} elseif (!empty($_SESSION['country_id']) && !empty($_SESSION['user_lang'])) {
	if (isset($_COOKIE['cookies_accepted']) && (empty($_COOKIE['country_id']) || empty($_COOKIE['user_lang']))) {
		\ze\cookie::set('country_id', $_SESSION['country_id'], 604800);
		\ze\cookie::set('user_lang', $_SESSION['user_lang'], 604800);
	}
}

//Attempt to use page caching, rather then re-render this page
if (ze::$canCache) require CMS_ROOT. 'zenario/includes/index.pre_load.inc.php';


define('CHECK_IF_MAJOR_REVISION_IS_NEEDED', true);
require CMS_ROOT. 'zenario/visitorheader.inc.php';

//T12563 Cookie-free domain for static files: make sure no regular admins or visitors us this domain
if (($cfDomain = ze::setting('cookie_free_domain'))
 && ($cfDomain === ($_SERVER['HTTP_HOST'] ?? ''))
 && ($cDomain = \ze::setting('primary_domain') ?: \ze::setting('last_primary_domain'))) {
	header('location: '. ze\link::protocol(). $cDomain. SUBDIRECTORY. DIRECTORY_INDEX_FILENAME);
	exit;
}


if ($isAdmin = ze::isAdmin()) {
	require CMS_ROOT. 'zenario/adminheader.inc.php';
	ze\skinAdm::checkForChangesInFiles();
	ze\miscAdm::checkForChangesInYamlFiles();
	
	//ze\admin::setSession($_SESSION['admin_userid'], $_SESSION['admin_global_id']);

//Don't directly show a Content Item if the site is disabled
} elseif (!ze::setting('site_enabled')) {
	ze\content::showStartSitePageIfNeeded();
	exit;
}



//Attempt to get this page.
$cID = $cType = $content = $chain = $version = $redirectNeeded = $aliasInURL = $langIdInURL = false;
ze\content::resolveFromRequest($cID, $cType, $redirectNeeded, $aliasInURL, $langIdInURL, $_GET, $_REQUEST, $_POST);

if ($redirectNeeded && empty($_POST) && !($redirectNeeded == 302 && $isAdmin)) {
	
	if ($redirectNeeded === 'admin'
	 && !ze\link::adminDomainIsPrivate()) {
		header('location: '. ze\link::protocol(). ze\link::adminDomain(). SUBDIRECTORY. 'admin.php');
		exit;
	}
	
	//When fixing the language code in the URL, make sure we redirect using the full path
	//as the language code might be in the domain/subdomain.
	$fullPath = $redirectNeeded == 302;
	
	$requests = $_GET;
	unset($requests['cID']);
	unset($requests['cType']);
	
	header('location: '. (ze\link::toItem($cID, $cType, $fullPath, $requests) ?: SUBDIRECTORY), true, $redirectNeeded);
	exit;
}

//Run pre-header actions
require ze::editionInclude('index.pre_header');



//Look up more details on the content item we are going to show
$status = ze\content::getShowableContent($content, $chain, $version, $cID, $cType, ze::request('cVersion'), $checkRequestVars = true);
	//N.b. an empty string ('') is used for a private page, if a visitor is not logged in
	//A 0 is used if a visitor is logged in and still can't see the page

//Catch the case where someone who is not logged in is requesting a private page
if ($status === ZENARIO_401_NOT_LOGGED_IN) {
	//Set the destination so the Visitor can come back here when logged in.
	//Note: Only record the page if it's a full page view with no $methodCall set.
	//(I.e. not an AJAX request, RSS request, or in an iframe.)
	if ($content && $methodCall === false) {
		$_SESSION['destCID'] = $content['id'];
		$_SESSION['destCType'] = $content['type'];
		$_SESSION['destURL'] = ze\link::protocol(). ze\link::host(). $_SERVER['REQUEST_URI'];
		
		if (!empty($version)
		 && !empty($version['title'])) {
			$_SESSION['destTitle'] = $version['title'];
		}
	}
	ze::$canCache = false;
	
	//Show the login page
	header('HTTP/1.0 401 Authentication Required');
	ze\content::langSpecialPage('zenario_login', $cID, $cType);
	$status = ze\content::getShowableContent($content, $chain, $version, $cID, $cType);
	
	//If there's something wrong with the login page, show the "Access Permission Denied (401)" page as a fallback
	if (!$status) {
		$status = ZENARIO_403_NO_PERMISSION;
	}
}

//If a page was requested but couldn't be shown...
if ($status === ZENARIO_403_NO_PERMISSION) {
	//Show the no-access if this page is not accessible
	header('HTTP/1.0 403 Forbidden');
	ze\content::langSpecialPage('zenario_no_access', $cID, $cType);
	$status = ze\content::getShowableContent($content, $chain, $version, $cID, $cType);

} elseif (!$status) {
	//Show the no-access if this page does not exist
	header('HTTP/1.0 404 Not Found');
	ze\content::langSpecialPage('zenario_not_found', $cID, $cType);
	$status = ze\content::getShowableContent($content, $chain, $version, $cID, $cType);
	
	//Log error if errors module is running
	if (ze\module::inc('zenario_error_log')) {
		$httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$requestURI = rtrim($_SERVER['REQUEST_URI'], '/');
		$URI = explode('/', $requestURI);
		$pageAlias = end($URI);
		zenario_error_log::log404Error($pageAlias, $httpReferer);
	}
}

//Try to go to the home page as a fallback if the Not Found/No Access/Login pages could not be used above
if (!$content || !$version || $status !== true) {
	$cID = ze::$homeCID;
	$cType = ze::$homeCType;
	$status = ze\content::getShowableContent($content, $chain, $version, $cID, $cType);
}

//If none of the above gave us a page to show, the site probably isn't set up correctly, so show the installation message
if (!$content || !$version || $status !== true) {
	ze\content::showStartSitePageIfNeeded();
	exit;
}
unset($cID);
unset($cType);
unset($menu);


ze\content::setShowableContent($content, $chain, $version, true);


//Run post-display actions
require ze::editionInclude('index.pre_get_contents');


[$slotName, $instanceId, $slideId, $slideNum, $state, $eggId, $overrideSettings, $overrideFrameworkAndCSS] =
	ze\plugin::getSlotVarsFromRequest();

$hideLayout = false;
$fakeLayout = false;
$singleSlot = false;

if ($methodCall == 'showSingleSlot' || $methodCall == 'showIframe') {
	$singleSlot = true;
	
	if ($slotName) {
		if (!$hideLayout = (bool) ze::request('hideLayout')) {
			$fakeLayout = (bool) ze::request('fakeLayout');
		}
	}
} else {
	$overrideSettings = false;
}

ze\plugin::runSlotContents(
	ze::$slotContents,
	ze::$cID, ze::$cType, ze::$cVersion,
	ze::$layoutId, $singleSlot, $slotName,
	$instanceId, $slideId, $slideNum, $state, $eggId,
	$overrideSettings, $overrideFrameworkAndCSS
);


ze\cache::start();



//Check whether we should allow cross-site iframes
do {
	//Never allow in admin mode
	if ($isAdmin) {
		break;
	}
	
	//Check what is allowed to be shown
	switch (ze::setting('xframe_target')) {
		case 'all_slots':
			//Only allow slots to be shown
			if (!$singleSlot) {
				break 2;
			}
			break;
		
		case 'slots_with_nests':
			//Only allow slots with nests in them to be shown
			if (!$singleSlot || !$slotName || empty(ze::$slotContents[$slotName]->isNest())) {
				break 2;
			}
			break;
		
		default:
			//Allow either slots or whole content items
			if (!$singleSlot && $methodCall) {
				break 2;
			}
	}
	
	//Check domain options
	switch (ze::setting('xframe_options')) {
		case 'all':
			//Allow from any domain (not recommended)
			break;
		case 'specific':
			//Allow from specific domains
			if (!isset($_SERVER['HTTP_REFERER'])
			 || !in_array(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST), ze\ray::explodeAndTrim(ze::setting('xframe_domains')))) {
				break 2;
			}
			break;
		default:
			//Do not allow from third-party domains
			break 2;
	}
	
	//If we got past all of the 
	header('X-Frame-Options: ALLOWALL');
} while (false);



if (ze::$canCache) {
	if (isset(ze::$cacheEnv)) {
		foreach ($_GET as $request => &$value) {
			if ($request != 'cID' && $request != 'cType' && $request != 'visLang' && $request != 'slotName' && $request != 'instanceId') {
				if (isset(ze::$importantGetRequests[$request])) {
					ze::$knownReq[$request] = $value;
			
				} else {
					ze::$cacheEnv['g'] = 'g';
				}
			}
		}
	}
}



$canonicalURL = ze\link::toItem(ze::$cID, ze::$cType, true, '', false, true, true);


$specialPage = ze\content::isSpecialPage(ze::$cID, ze::$cType);

//As long as this isn't a special page, note down that this was the last page the user viewed.
//Note: Only record the page if it's a full page view with no $methodCall set.
//(I.e. not an AJAX request, RSS request, or in an iframe.)
if ($methodCall === false && (!$specialPage || $specialPage == 'zenario_home')) {
	$_SESSION['destCID'] = ze::$cID;
	$_SESSION['destCType'] = ze::$cType;
	$_SESSION['destURL'] = $canonicalURL;
	$_SESSION['destTitle'] = ze::$pageTitle;
}



echo 
'<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="', ze::$langId, '" lang="', ze::$langId, '">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />';


//If relative URLs with slashes are in use, add the "base" path to make it clear what the relative URL of this page should be.
//(N.b. most methods in the CMS automatically switch to using the full URL in this case, but this statement should help catch
// any hardcoded links that need correcting, e.g. in WYSIWYG Eidtors.)
if (ze::setting('mod_rewrite_slashes')) {
	echo '
<base href="', ze\link::absolute(), '">';
}

echo '
<title>', htmlspecialchars(ze::$pageTitle), '</title>';

//Don't allow placeholder pages, or content items with noindex meta tag, to be indexed by search engines!
$applyNoindexMetaTag = ze\row::get('content_item_versions', 'apply_noindex_meta_tag', ['id' => ze::$cID, 'type' => ze::$cType, 'version' => (int) $version['version']]);
if (ze::$langId !== ze::$visLang || $applyNoindexMetaTag) {
	echo '
<meta name="robots" content="noindex" />';

} else {
	echo '
<link rel="canonical" href="', htmlspecialchars($canonicalURL), '"/>
<meta property="og:url" content="', htmlspecialchars($canonicalURL), '"/>
<meta property="og:type" content="', htmlspecialchars(ze::$pageOGType), '"/>
<meta property="og:title" content="', htmlspecialchars(ze::$pageTitle), '"/>';
}

$ogImageMaxWidth = ze::setting('og_image_max_width') ?: 1200;
$ogImageMaxHeight = ze::setting('og_image_max_height') ?: 630;

$imageWidth = $imageHeight = $imageURL = false;
if (ze::$pageImage && ze\file::imageLink($imageWidth, $imageHeight, $imageURL, ze::$pageImage, $ogImageMaxWidth, $ogImageMaxHeight, 'resize', 0, false, $fullPath = true)) {
	$mimeType = ze\row::get('files', 'mime_type', ze::$pageImage);
	
	echo '
<meta property="og:image:type" content="' . htmlspecialchars($mimeType) . '" />
<meta property="og:image" content="', htmlspecialchars($imageURL), '"/>
<meta property="og:image:width" content="' . htmlspecialchars($imageWidth) . '" />
<meta property="og:image:height" content="' . htmlspecialchars($imageHeight) . '" />';
	if (ze\link::protocol() == "https://") {
		echo '
<meta property="og:image:secure_url" content="', htmlspecialchars($imageURL), '"/>';
	}
}
else {

//This default image will be shown if a page does not have a feature image.
	if (($ogImageId = ze::setting('default_icon')) && ($icon = ze\row::get('files', ['id', 'mime_type', 'filename', 'checksum'], $ogImageId))) {

		if ($icon['mime_type'] == 'image/vnd.microsoft.icon' || $icon['mime_type'] == 'image/x-icon') {
			$url = ze\file::link($icon['id']);
		} else {
			$imageWidth = $imageHeight = $url = false;
			ze\file::imageLink($imageWidth, $imageHeight, $url, $icon['id'], $ogImageMaxWidth, $ogImageMaxHeight, 'resize', 0, false, $fullPath = true);
		}
    	echo '
<meta property="og:image:type" content="' . htmlspecialchars($icon['mime_type']) . '" />
<meta property="og:image" content="', htmlspecialchars($url), '"/>
<meta property="og:image:width" content="' . htmlspecialchars($imageWidth) . '" />
<meta property="og:image:height" content="' . htmlspecialchars($imageHeight) . '" />';
    if (ze\link::protocol() == "https://") {
		echo '
<meta property="og:image:secure_url" content="', htmlspecialchars($url), '"/>';
	}

	}
}

echo '
<meta property="og:description" content="', (ze::$pageDesc ? htmlspecialchars(ze::$pageDesc) : ''), '"/>
<meta name="description" content="', (ze::$pageDesc ? htmlspecialchars(ze::$pageDesc) : ''), '" />
<meta name="generator" content="Zenario ', ze\site::versionNumber(), '" />
<meta name="keywords" content="', (ze::$pageKeywords ? htmlspecialchars(ze::$pageKeywords) : ''), '" />';


// Add hreflang tags
if (ze\lang::count() > 1) {
	// If there are no important get requests
	$getRequests = false;
	foreach(ze::$importantGetRequests as $getRequest => $defaultValue) {
		if (isset($_GET[$getRequest]) && $_GET[$getRequest] != $defaultValue) {
			$getRequests = true;
			break;
		}
	}
	if (!$getRequests) {
		$sql = "
			SELECT c.id, c.type, c.alias, c.equiv_id, c.language_id
			FROM ". DB_PREFIX. "content_items AS c
			INNER JOIN ". DB_PREFIX. "translation_chains AS tc
			   ON c.equiv_id = tc.equiv_id
			  AND c.type = tc.type
			WHERE tc.privacy = 'public'
			  AND c.equiv_id = ". (int) ze::$equivId. "
			  AND c.type = '". ze\escape::asciiInSQL(ze::$cType). "'
			  AND c.status IN ('published_with_draft', 'published')";
		$result = ze\sql::select($sql);
		if (ze\sql::numRows($result) > 1) {
			while($row = ze\sql::fetchAssoc($result)) {
				$pageLink = ze\link::toItem($row['id'], $row['type'], true, '', $row['alias'], true, true, $row['equiv_id'], $row['language_id']);
				echo '
<link rel="alternate" href="'. htmlspecialchars($pageLink). '" hreflang="'. htmlspecialchars($row['language_id']). '">';
			}
		}
	}
}

//Skin information
echo '
<meta name="skin" content="' . ze::$skinName . '"/>';

ze\content::pageHead('zenario/', false, true, $overrideFrameworkAndCSS);

echo "</head>";

$contentItemDiv =
	"\n".
	'<div id="zenario_citem" class="';

if ($specialPage) {
	$contentItemDiv .= htmlspecialchars($specialPage). ' ';
}

$contentItemDiv .= 'lang_'. preg_replace('/[^\w-]/', '', ze::$langId);

if (ze::$itemCSS) {
	$contentItemDiv .= ' '. htmlspecialchars(ze::$itemCSS);
}

$contentItemDiv .= '">';


$templateDiv =
	"\n".
	'<div id="zenario_layout" class="'.
		'zenario_'. htmlspecialchars(ze::$cType). '_layout';

if (ze::$templateCSS) {
	$templateDiv .= ' '. htmlspecialchars(ze::$templateCSS);
}

$templateDiv .= '">';


$skinDiv =
	"\n".
	'<div id="zenario_skin" class="zenario_skin';

if (ze::$skinCSS) {
	$skinDiv .= ' '. htmlspecialchars(ze::$skinCSS). '';
}

$skinDiv .= '">';






//Functionality for only showing one Plugin in a slot
if ($singleSlot) {
	
	//Just show the plugin, without any of the <div>s from the layout around it
	if ($hideLayout) {
		ze\content::pageBody('zenario_showing_plugin_without_layout', '', true);
		ze\plugin::slot($slotName, 'grid', $eggId);
	
	//Try and "fake" the grid, to get as many styles from the Skin as possible,
	//while still showing the plugin on its own taking up the full width
	} else {
		if ($fakeLayout) {
			echo '
				<link rel="stylesheet" type="text/css" href="', htmlspecialchars(ze\link::absolute()), 'zenario/styles/admin_plugin_preview.min.css">';
			
			ze\content::pageBody('zenario_showing_plugin_preview', '', true);
		} else {
			ze\content::pageBody('zenario_showing_standalone_plugin', '', true);
		}
		
		echo $skinDiv, $templateDiv, $contentItemDiv, '
			<div class="container ', empty($_GET['grid_container'])? '' : 'container_'. (int) $_GET['grid_container'], '">
				<div
					class="
						alpha span
						', empty($_GET['grid_columns'])? 'span1_1' : 'span'. (int) $_GET['grid_columns'], '
						', empty($_GET['grid_cssClass'])? '' : htmlspecialchars($_GET['grid_cssClass']), '
					"
					', empty($_GET['grid_pxWidth'])? '' : 'style="max-width: '. (int) $_GET['grid_pxWidth']. 'px;"', '
				>';
					ze\plugin::slot($slotName, 'grid', $eggId);
		echo '
				</div>
			</div>
		</div></div></div>';
	}
	
	
	echo '
		<script type="text/javascript">
			window.zenario_inIframe = true;
		</script>';
	
	ze\content::pageFoot('zenario/', false, false, false);

//Show a preview, without the Admin Toolbar or any JavaScript
} elseif (!empty($_REQUEST['_show_page_preview'])) {
	ze\content::pageBody('zenario_showing_preview', '', true);
	echo $skinDiv, $templateDiv, $contentItemDiv;
	
	if ($tplFile = ze\content::layoutHtmlPath(ze::$layoutId, true)) {
		require CMS_ROOT. $tplFile;
	}
	
	echo "\n", '</div></div></div>';
	
	if (!empty($_REQUEST['_add_js'])) {
		ze\content::pageFoot('zenario/', false, false, false);
	} else {
		echo '
		<script type="text/javascript" src="zenario/libs/yarn/jquery/dist/jquery.min.js?v=', ZENARIO_VERSION, '"></script>';
	}
	
	echo '<script type="text/javascript">
			$(\'*\').each(function(i, el) {
				el.onclick = function() { return false; };
			});';
			
			if (!empty($_REQUEST['_scroll_to'])) {
				echo '
					$(document).scrollTop('. (int) $_REQUEST['_scroll_to']. ');';
			}
	echo '
		</script>';
	

//Normal functionality; show the whole page
} else {
	$includeAdminToolbar = $isAdmin && empty($_SESSION['hide_admin_toolbar']);
	ze\content::pageBody('', '', true, $includeAdminToolbar);
	ze\cookie::showConsentBox($isAdmin, true, $includeAdminToolbar);
	
	if (ze\module::inc('zenario_sensitive_content_message')) {
		
		if (empty($_COOKIE['sensitive_content_message_accepted']) && empty($_SESSION['sensitive_content_message_accepted'])) {
			zenario_sensitive_content_message::showSensitiveContentMessage();
		}
	}
	
	echo $skinDiv, $templateDiv, $contentItemDiv;
	

	if ($tplFile = ze\content::layoutHtmlPath(ze::$layoutId, true)) {
		require CMS_ROOT. $tplFile;
		ze\plugin::checkSlotsWereUsed();
	}
	
	echo "\n", '</div></div></div>';
	ze\content::pageFoot('zenario/', false, true, $includeAdminToolbar, true);
	
	//If someone just changed the CSS for a plugin, scroll down to that plugin to show the changes
	if ($isAdmin && !empty($_SESSION['scroll_slot_on_'. ze::$cType. '_'. ze::$cID])) {
		echo '
			<script type="text/javascript">
				zenario.scrollToSlotTop("'. ze\escape::js($_SESSION['scroll_slot_on_'. ze::$cType. '_'. ze::$cID]). '", false, 300);
			</script>';
		
		unset($_SESSION['scroll_slot_on_'. ze::$cType. '_'. ze::$cID]);
	}
}

//Run post-display actions
//Record the first RSS link on this page, if there was one
if (ze::$rss
 && !ze::$rss1st
 && empty($_REQUEST['slideId'])
 && empty($_REQUEST['slideNum'])
 && empty($_REQUEST['slotName'])
 && empty($_REQUEST['instanceId'])
 && ($rss = explode('_', ze::$rss, 2))
 && (!empty($rss[1]))) {
	ze\row::set('content_item_versions', ['rss_slot_name' => $rss[1], 'rss_nest' => (int) $rss[0]], ['id' => ze::$cID, 'type' => ze::$cType, 'version' => ze::$cVersion]);
}
//Attempt to save this page to the cache, if caching is possible
if (ze::$canCache) require CMS_ROOT. 'zenario/includes/index.post_display.inc.php';


echo "\n</body>\n</html>";


exit;