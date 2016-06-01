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

$gzf = setting('compress_web_pages')? '?gz=1' : '?gz=0';
$gz = setting('compress_web_pages')? '&amp;gz=1' : '&amp;gz=0';
$v = ifNull(setting('css_js_version'), ZENARIO_CMS_VERSION. '.'. LATEST_REVISION_NO);

$isWelcome = $mode === true || $mode === 'welcome';
$isWizard = $mode === 'wizard';
$isWelcomeOrWizard = $isWelcome || $isWizard;
$isOrganizer = $mode === 'organizer';


//Some IE specific fixes
echo '
<meta http-equiv="X-UA-Compatible" content="IE=Edge">';

$oldIE = strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6') !== false
	|| strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7') !== false
	|| strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8') !== false;

$notSupportedInAdminMode = $oldIE
	|| strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 9') !== false;

//In admin mode, if this is IE, require 10 or later. Direct 9 and earlier to the compatibility mode page.
if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
	
	if ($isWelcomeOrWizard || checkPriv()) {
		echo '
<script type="text/javascript">
	if (typeof JSON === "undefined" || ', engToBoolean($notSupportedInAdminMode), ') {
		document.location = "',
			jsEscape(
				absCMSDirURL().
				'zenario/admin/ie_compatibility_mode/index.php?'.
				($isWizard? 'isWizard=1&' : '').
				http_build_query($_GET)
			),
		'";
	}
</script>';
	}
}


if (!$isWelcomeOrWizard && !$oldIE && $cookieFreeDomain = cookieFreeDomain()) {
	$prefix = $cookieFreeDomain. 'zenario/';

} elseif (cms_core::$mustUseFullPath) {
	$prefix = absCMSDirURL(). 'zenario/';
}


//Work out what's on this page, which wrappers we need to include, and which Plugin/Swatches need to be requested

//Send the id of each plugin on the page to a JavaScript wrapper to include their JavaScript
//Send the plugin name of each
if (!empty(cms_core::$slotContents) && is_array(cms_core::$slotContents)) {
	$comma = '';
	$comma2 = '';
	$JavaScriptOnPage = array();
	$themesOnPage = array();
	
	foreach(cms_core::$slotContents as &$instance) {
		
		if (isset($instance['class_name']) && !empty($instance['class'])) {
			if (empty($JavaScriptOnPage[$instance['class_name']])) {
				$JavaScriptOnPage[$instance['class_name']] = true;
				cms_core::$pluginJS .= $comma. $instance['module_id'];
				$comma = ',';
			}
		}
	}
}


if ($isWelcomeOrWizard || ($isOrganizer && setting('organizer_favicon') == 'zenario')) {
	echo "\n", '<link rel="shortcut icon" href="', absCMSDirURL(), 'zenario/admin/images/favicon.ico"/>';

} elseif (cms_core::$lastDB) {
	
	if ($isOrganizer && setting('organizer_favicon') == 'custom') {
		$faviconId = setting('custom_organizer_favicon');
	} else {
		$faviconId = setting('favicon');
	}
	
	if ($faviconId
	 && ($icon = getRow('files', array('id', 'mime_type', 'filename', 'checksum'), $faviconId))
	 && ($link = fileLink($icon['id'], false, 'public/images'))) {
		if ($icon['mime_type'] == 'image/vnd.microsoft.icon' || $icon['mime_type'] == 'image/x-icon') {
			echo "\n", '<link rel="shortcut icon" href="', absCMSDirURL(), htmlspecialchars($link), '"/>';
		} else {
			echo "\n", '<link type="', htmlspecialchars($icon['mime_type']), '" rel="icon" href="', absCMSDirURL(), htmlspecialchars($link), '"/>';
		}
	}

	if (!$isOrganizer
	 && setting('mobile_icon')
	 && ($icon = getRow('files', array('id', 'mime_type', 'filename', 'checksum'), setting('mobile_icon')))
	 && ($link = fileLink($icon['id']))) {
		echo "\n", '<link rel="apple-touch-icon-precomposed" href="', absCMSDirURL(), htmlspecialchars($link), '"/>';
	}
}



//Add CSS needed for the CMS in Admin mode
if ($isWelcomeOrWizard || checkPriv()) {
	if (!cms_core::$skinId) {
		echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'libraries/mit/jquery/css/colorbox/colorbox.css?v=', $v, $gz, '"/>';
	}
	
	echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'libraries/mit/jquery/css/jquery_ui/jquery-ui.css?v=', $v, $gz, '"/>
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'styles/admin.wrapper.css.php?v=', $v, $gz, '"/>
<link rel="stylesheet" type="text/css" media="print" href="', $prefix, 'styles/print.min.css"/>';
	
	if ($includeOrganizer) {
		echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'styles/organizer.wrapper.css.php?v=', $v, $gz, '"/>';
		
		if ($isOrganizer) {
			echo '
<link rel="stylesheet" type="text/css" media="print" href="', $prefix, 'styles/admin_organizer_print.min.css?v=', $v, $gz, '"/>';
		}
		
		$cssModuleIds = '';
		foreach (getRunningModules() as $module) {
			if (moduleDir($module['class_name'], 'adminstyles/organizer.css', true)
			 || moduleDir($module['class_name'], 'adminstyles/storekeeper.css', true)) {
				$cssModuleIds .= ($cssModuleIds? ',' : ''). $module['id'];
			}
		}
		
		if ($cssModuleIds) {
			echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'styles/module.wrapper.css.php?v=', $v, $gz, '&amp;ids=', $cssModuleIds, '&amp;organizer=1"/>';
		}
	}
}

//Add the CSS for a skin, if there is a skin, and add CSS needed for any Module Swatches on the page
if (setting('css_wrappers') == 'on' || (setting('css_wrappers') == 'visitors_only') && !checkPriv()) {
	//If wrappers are enabled, link to skin.cache_wrapper.css.php
	if (cms_core::$skinId || cms_core::$layoutId) {
		echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'styles/skin.cache_wrapper.css.php?v=', $v, '&amp;id=', (int) cms_core::$skinId, '&amp;layoutId=', (int) cms_core::$layoutId, $gz, '"/>
<link rel="stylesheet" type="text/css" media="print" href="', $prefix, 'styles/skin.cache_wrapper.css.php?v=', $v, '&amp;id=', (int) cms_core::$skinId, '&amp;print=1', $gz, '"/>';
	}
	
} else {
	//If CSS Wrappers are turned off, run through the logic in skin.cache_wrapper.css.php, and link to each individual file.
	//This gives slower page load speeds, but is better for Module Developers to debug.
	function includeCSSFile($path, $file, $pathURL = false, $media = 'screen') {
		if (!$pathURL) {
			$pathURL = $path;
		}
		
		//Check if there's a stylesheet there
		if (is_file(CMS_ROOT. $path. $file)) {
			echo '
<link rel="stylesheet" type="text/css" media="', $media, '" href="', htmlspecialchars($pathURL. $file), '"/>';
			return true;
		}
		
		return false;
	}
	
	require CMS_ROOT. 'zenario/includes/wrapper.inc.php';
	
	if (cms_core::$skinId || cms_core::$layoutId) {
		
		echo "\n", '<style type="text/css">';
		outputRulesForSlotMinHeights();
		echo "\n", '</style>';
		
		$req = array('id' => (int) cms_core::$skinId, 'print' => '', 'layoutId' => cms_core::$layoutId);
		includeSkinFiles($req);
		$req = array('id' => (int) cms_core::$skinId, 'print' => '1');
		includeSkinFiles($req);
	}
}

//Are there modules on this page..?
if (!empty(cms_core::$slotContents) && is_array(cms_core::$slotContents)) {
	//Include the Head for any plugin instances on the page, if they have one
	foreach(cms_core::$slotContents as $slotName => &$instance) {
		if (!empty($instance['class'])) {
			cms_core::preSlot($slotName, 'addToPageHead');
				$instance['class']->addToPageHead();
			cms_core::postSlot($slotName, 'addToPageHead');
		}
	}
}


if (checkPriv()) {
	//Add CSS needed for modules in Admin Mode in the frontend
	if (cms_core::$cID) {
		$cssModuleIds = '';
		foreach (getRunningModules() as $module) {
			if (moduleDir($module['class_name'], 'adminstyles/admin_frontend.css', true)) {
				$cssModuleIds .= ($cssModuleIds? ',' : ''). $module['id'];
			}
		}
	
		if ($cssModuleIds) {
			echo '
<link rel="stylesheet" type="text/css" href="', $prefix, 'styles/module.wrapper.css.php?v=', $v, '&amp;ids=', $cssModuleIds, $gz, '&amp;admin_frontend=1" media="screen" />';
		}
	}

//Add the CSS for the login link for admins if this looks like a logged out admin
} else if (isset($_COOKIE['COOKIE_LAST_ADMIN_USER'])) { 
	echo '
<link rel="stylesheet" type="text/css" href="', $prefix, 'styles/admin_login_link.min.css?v=', $v, '" media="screen" />';
}


echo '
<script type="text/javascript">
	var RecaptchaOptions = {
		lang: "', jsEscape(substr(session('user_lang'), 0, 2)), '",
		theme: "', jsEscape(setting('google_recaptcha_theme')), '"};
</script>';

if (cms_core::$cID) {
	$itemHTML = $templateHTML = $familyHTML =
	$bgWidth = $bgHeight = $bgURL = false;
	
	//Look up the background image and any HTML to add to the HEAD from the content item
	$sql = "
		SELECT head_html, head_cc, head_visitor_only, head_overwrite, bg_image_id, bg_color, bg_position, bg_repeat
		FROM ". DB_NAME_PREFIX. "content_item_versions
		WHERE id = ". (int) cms_core::$cID. "
		  AND type = '". sqlEscape(cms_core::$cType). "'
		  AND version = ". (int) cms_core::$cVersion;
	$result = sqlQuery($sql);
	$itemHTML = sqlFetchAssoc($result);
	
	switch ($itemHTML['head_cc']) {
		case 'required':
			cms_core::$cookieConsent = 'require';
		case 'needed':
			if (!canSetCookie()) {
				$itemHTML['head_html'] =
				$itemHTML['head_overwrite'] = false;
			}
	}
	
	//Look up the background image and any HTML to add to the HEAD from the layout
	$sql = "
		SELECT head_html, head_cc, head_visitor_only, bg_image_id, bg_color, bg_position, bg_repeat
		FROM ". DB_NAME_PREFIX. "layouts
		WHERE layout_id = ". (int) cms_core::$layoutId;
	$result = sqlQuery($sql);
	$templateHTML = sqlFetchAssoc($result);
	
	//Only add html from the layout if it's not been overridden on the Content Item
	if (empty($itemHTML['head_overwrite'])) {
		switch ($templateHTML['head_cc']) {
			case 'required':
				cms_core::$cookieConsent = 'require';
			case 'needed':
				if (!canSetCookie()) {
					$templateHTML['head_html'] =
					$templateHTML['head_overwrite'] = false;
				}
		}
		
		if (!empty($templateHTML['head_html']) && (empty($templateHTML['head_visitor_only']) || !checkPriv())) {
			echo "\n\n". $templateHTML['head_html'], "\n\n";
		}
	}
	
	if (!empty($itemHTML['head_html']) && (empty($itemHTML['head_visitor_only']) || !checkPriv())) {
		echo "\n\n". $itemHTML['head_html'], "\n\n";
	}
	
	
	//Check to see if there is a background image on this content item (or on this layout if not on the content item)
	if ($itemHTML['bg_image_id']) {
		imageLink($bgWidth, $bgHeight, $bgURL, $itemHTML['bg_image_id']);
	} elseif ($templateHTML['bg_image_id']) {
		imageLink($bgWidth, $bgHeight, $bgURL, $templateHTML['bg_image_id']);
	}
	
	$bgColor = $itemHTML['bg_color']? $itemHTML['bg_color'] : $templateHTML['bg_color'];
	$bgPosition = $itemHTML['bg_position']? $itemHTML['bg_position'] : $templateHTML['bg_position'];
	$bgRepeat = $itemHTML['bg_repeat']? $itemHTML['bg_repeat'] : $templateHTML['bg_repeat'];
	
	if ($bgURL || $bgColor || $bgPosition || $bgRepeat) {
		
		$background_selector = 'body';
		if (cms_core::$skinId) {
			$background_selector = getRow('skins', 'background_selector', cms_core::$skinId);
		}
		
		echo '
<style type="text/css">
	', $background_selector, ' {';
		if ($bgURL) {
			echo '
		background-image: url(\'', htmlspecialchars($bgURL), '\');';
		}
		if ($bgColor) {
			echo '
		background-color: ', htmlspecialchars($bgColor), ';';
		}
		if ($bgPosition) {
			echo '
		background-position: ', htmlspecialchars($bgPosition), ';';
		}
		if ($bgRepeat) {
			echo '
		background-repeat: ', htmlspecialchars($bgRepeat), ';';
		}
		
		echo '
		}
</style>';
	}
	
}

//Bugfixes for IE 6, 7 and 8
echo '
<!--[if IE 6]><style type="text/css"> body { behavior: url(', $prefix, 'libraries/lgpl/csshover/csshover.htc); } </style><![endif]-->
<!--[if lte IE 8]><script type="text/javascript" src="', $prefix, 'libraries/mit/respond/respond.js?v=', $v, '"></script><![endif]-->';
