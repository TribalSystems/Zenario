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

$codeVersion = ze\db::codeVersion();
$v = $w = 'v='. $codeVersion;

if (!\ze::$cacheBundles) {
	$w .= '&amp;no_cache=1';
}

$isWelcome = $mode === true || $mode === 'welcome';
$isOrganizer = $mode === 'organizer';
$httpUserAgent = ($_SERVER['HTTP_USER_AGENT'] ?? '');
$isAdmin = \ze::isAdmin();
$bundle_skins = \ze::setting('bundle_skins');


//In admin mode, completely reject anything that looks like Internet Explorer, and
//direct them to the compatibility mode page.
if ($isWelcome || $isAdmin) {
	echo '
<script type="text/javascript">
	if (typeof JSON === "undefined" || ', \ze\ring::engToBoolean(\ze\cache::browserIsIE()), ') {
		document.location = "',
			\ze\escape::js(
				\ze\link::absolute().
				'zenario/admin/ie_compatibility_mode/index.php?'.
				http_build_query($_GET)
			),
		'";
	}
</script>';
}

if ($absURL = \ze\link::absoluteIfNeeded(!$isWelcome)) {
	$prefix = $absURL. 'zenario/';
}




if ($isWelcome || ($isOrganizer && \ze::setting('organizer_favicon') == 'zenario')) {
	echo "\n", '<link rel="shortcut icon" href="', \ze\link::absolute(), 'zenario/admin/images/favicon.ico"/>';

} elseif (\ze::$dbL) {
	
	if ($isOrganizer && \ze::setting('organizer_favicon') == 'custom') {
		$faviconId = \ze::setting('custom_organizer_favicon');
	} else {
		$faviconId = \ze::setting('favicon');
	}
	
	if ($faviconId
	 && ($icon = \ze\row::get('files', ['id', 'mime_type', 'filename', 'checksum'], $faviconId))
	 && ($link = ze\file::link($icon['id'], false, 'public/images'))) {
		if ($icon['mime_type'] == 'image/vnd.microsoft.icon' || $icon['mime_type'] == 'image/x-icon') {
			echo "\n", '<link rel="shortcut icon" href="', \ze\link::absolute(), htmlspecialchars($link), '"/>';
		} else {
			echo "\n", '<link type="', htmlspecialchars($icon['mime_type']), '" rel="icon" href="', \ze\link::absolute(), htmlspecialchars($link), '"/>';
		}
	}

	if (!$isOrganizer
	 && \ze::setting('mobile_icon')
	 && ($icon = \ze\row::get('files', ['id', 'mime_type', 'filename', 'checksum'], \ze::setting('mobile_icon')))
	 && ($link = ze\file::link($icon['id'], false, 'public/images'))) {
		echo "\n", '<link rel="apple-touch-icon-precomposed" href="', \ze\link::absolute(), htmlspecialchars($link), '"/>';
	}
}


//Depending on the site settings, include Font Awesome.
//(Though in admin mode, always include it.)
$useFA = \ze::setting('lib.fontawesome');

if ($useFA == 'bc') {
	echo '
<link rel="stylesheet" type="text/css" href="', $prefix, 'styles/fontawesome.bundle.css.php?', $w, '"/>';

} elseif ($useFA || $isWelcome || $isAdmin) {
	echo '
<link rel="stylesheet" type="text/css" href="', $prefix, 'libs/yarn/@fortawesome/fontawesome-free/css/all.min.css?', $w, '"/>';
}


//Add the CSS rules for colorbox, if anything on the page uses it.
if ($isWelcome
 || $isAdmin
 || ze::setting('lib.colorbox')
 || isset(ze::$jsLibs['zenario/libs/manually_maintained/mit/colorbox/jquery.colorbox.min.js'])) {
	echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'libs/manually_maintained/mit/colorbox/colorbox.min.css?', $v, '"/>';
}

//Add the CSS rules for jQuery UI, if anything on the page uses it.
if ($isWelcome
 || $isAdmin
 || ze::setting('lib.jqueryui.theme')
 || isset(ze::$jsLibs['zenario/libs/manually_maintained/mit/jqueryui/jquery-ui.datepicker.min.js'])
 || isset(ze::$jsLibs['zenario/libs/manually_maintained/mit/jqueryui/jquery-ui.sortable.min.js'])) {
	echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'libs/manually_maintained/mit/jqueryui/jquery-ui.min.css?', $v, '"/>';
}



//Add CSS needed for the CMS in Admin mode
if ($isWelcome || $isAdmin) {
	echo '
<link rel="stylesheet" type="text/css" media="print" href="', $prefix, 'styles/admin_print.min.css?', $v, '"/>';
	
	//Add the CSS for admin mode... unless this is a layout preview
	if ($mode != 'layout_preview') {
		echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'styles/admin.bundle.css.php?', $w, '"/>';
	}
	
	if ($includeOrganizer) {
		echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'styles/organizer.bundle.css.php?', $w, '"/>';
		
		if ($isOrganizer) {
			echo '
<link rel="stylesheet" type="text/css" media="print" href="', $prefix, 'styles/admin_organizer_print.min.css?', $v, '"/>';
		}
		
		$cssModuleIds = '';
		foreach (\ze\module::runningModules() as $module) {
			if (\ze::moduleDir($module['class_name'], 'adminstyles/organizer.css', true)
			 || \ze::moduleDir($module['class_name'], 'adminstyles/storekeeper.css', true)) {
				$cssModuleIds .= ($cssModuleIds? ',' : ''). $module['id'];
			}
		}
		
		if ($cssModuleIds) {
			echo '
<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, 'styles/module.bundle.css.php?', $w, '&amp;ids=', $cssModuleIds, '&amp;organizer=1"/>';
		}
	}
}


//Include the layout.
//This is only a few K in size, so In Zenario 9.5 I'm going to try simply inlining this to avoid an extra request or combining it with the skin.
if (\ze::$layoutId && ($minFile = \ze\content::layoutCssPath(\ze::$layoutId))) {
	echo '
<style type="text/css">', file_get_contents(CMS_ROOT. $minFile), '</style>';
}

//Add the CSS for a skin, if there is a skin
if (\ze::$skinId) {
	
	//Check if bundles are enabled for skins.
	//(Note that bundles are forced off when viewing a preview of layouts/CSS.)
	if ($overrideFrameworkAndCSS === false && ($bundle_skins == 'on' || ($bundle_skins == 'visitors_only' && !$isAdmin))) {
	
		//Check if we have a minified version of the skin and use that if possible.
		//However we should only use a minified version if the skin files have not had any changes since the minified version was created.
		//(The timestamp is part of the filename to make this easy to check.)
		if (($sv = \ze::setting('css_skin_version'))
		 && (file_exists(CMS_ROOT. ($skinPath = 'public/css/skin_'. (int) \ze::$skinId. '/'. $sv. '.min.css')))) {
			echo '
<link rel="stylesheet" type="text/css" href="', $absURL. $skinPath, '"/>';
	
		//Otherwise link to skin.bundle.css.php to generate a non-minified version
		} else {
			echo '
<link rel="stylesheet" type="text/css" href="', $prefix, 'styles/skin.bundle.css.php?', $v, '&amp;id=', (int) \ze::$skinId, '"/>';
		}
	
	} else {
		
		//Watch out for the variables from the CSS preview, and translate them to the format
		//needed by \ze\bundle::includeSkinFiles() if we see them there.
		$overrideCSS = false;
		if ($overrideFrameworkAndCSS !== false) {
			$files = [];
			$overrideCSS = [];
			
			$tabs = [
				'this_css_tab',
				'all_css_tab',
				'0.reset.css',
				'.colorbox.css',
				'1.fonts.css',
				'1.forms.css',
				'1.jquery_ui.css',
				'1.layout.css',
				'3.misc.css',
				'3.misc_zfea.css',
				'4.responsive.css',
				'print.css'
			];

			//Also add editable CSS files used by the skin.
			if (\ze::$skinId && ($skin = ze\row::get('skins', 'name', \ze::$skinId))) {
				$editableCssFiles = [];
				$skinPath = CMS_ROOT . ze\content::skinPath($skin) . 'editable_css/';
				if ($handle = opendir($skinPath)) {
					while (($entry = readdir($handle)) !== false) {
						if ($entry != "." && $entry != ".." && strpos($entry, '2.') === 0) {
							$tabs[] = $entry;
						}
					}
				}
			}

			foreach ($tabs as $tab) {
				if (!empty($overrideFrameworkAndCSS[$tab. '/use_css_file'])
				 && !empty($overrideFrameworkAndCSS[$tab. '/css_filename'])
				 && isset($overrideFrameworkAndCSS[$tab. '/css_source'])) {
				 	$files[$overrideFrameworkAndCSS[$tab. '/css_filename']] = $overrideFrameworkAndCSS[$tab. '/css_source'];
				}
			}
			
			ksort($files);
			
			foreach ($files as $file => &$contents) {
				switch ($file) {
					case 'tinymce.css':
						break;
					
					default:
						$overrideCSS[] = [$file, $contents];
				}
			}
		}
		
		\ze\bundle::includeSkinFiles(\ze::$skinId, $v, $overrideCSS);
	}
}

//Are there modules on this page..?
if (!empty(\ze::$slotContents) && is_array(\ze::$slotContents)) {
	//Include the Head for any plugin instances on the page, if they have one
	foreach(\ze::$slotContents as $slotName => &$slot) {
		if ($slot->class()) {
			\ze\plugin::preSlot($slotName, 'addToPageHead');
				$slot->class()->addToPageHead();
			\ze\plugin::postSlot($slotName, 'addToPageHead');
		}
	}
}

if ($grsk = \ze::setting('google_recaptcha_site_key')) {
	echo '
<script type="text/javascript">var google_recaptcha = {
	sitekey: "' . \ze\escape::js($grsk) . '",
	theme: "' . \ze\escape::js(\ze::setting('google_recaptcha_widget_theme')) . '"
};</script>';
}


if ($isAdmin) {
	//Add CSS needed for modules in Admin Mode in the frontend
	if (\ze::$cID) {
		$cssModuleIds = '';
		foreach (\ze\module::runningModules() as $module) {
			if (\ze::moduleDir($module['class_name'], 'adminstyles/admin_frontend.css', true)) {
				$cssModuleIds .= ($cssModuleIds? ',' : ''). $module['id'];
			}
		}
	
		if ($cssModuleIds) {
			echo '
<link rel="stylesheet" type="text/css" href="', $prefix, 'styles/module.bundle.css.php?', $w, '&amp;ids=', $cssModuleIds, '&amp;admin_frontend=1" media="screen" />';
		}
	}
	
	//Add the CSS file for skin-specific admin styles, if it exists
	if (\ze::$skinId && ($skinPath = \ze\content::skinPath())) {
		if (is_file(CMS_ROOT. ($filePath = $skinPath. 'adminstyles/admin_frontend.css'))) {
			echo '
	<link rel="stylesheet" type="text/css" media="screen" href="', $absURL, $filePath, '"/>';
		}
	}
	

//Add the CSS for the login link for admins if this looks like a logged out admin
} else if (isset($_COOKIE['COOKIE_LAST_ADMIN_USER']) && !\ze\link::adminDomainIsPrivate()) { 
	echo '
<link rel="stylesheet" type="text/css" href="', $prefix, 'styles/admin_login_link.min.css?', $v, '" media="screen" />';
}


if (\ze::$cID && \ze::$cID !== -1) {
	$itemHTML = $templateHTML = $familyHTML =
	$bgWidth = $bgHeight = $bgURL = false;
	

	//Include the site-wide head first
	ze\content::sitewideHTML('sitewide_head');

	if (ze\cookie::canSet('analytics') && ze::setting('sitewide_analytics_html_location') == 'head') {
		ze\content::sitewideHTML('sitewide_analytics_html');
	}
	if (ze\cookie::canSet('social_media') && ze::setting('sitewide_social_media_html_location') == 'head') {
		ze\content::sitewideHTML('sitewide_social_media_html');
	}
	
	
	//Look up the background image and any HTML to add to the HEAD from the content item
	$sql = "
		SELECT head_html, head_cc, head_cc_specific_cookie_types, head_visitor_only, head_overwrite, bg_image_id, bg_color, bg_position, bg_repeat
		FROM ". DB_PREFIX. "content_item_versions
		WHERE id = ". (int) \ze::$cID. "
		  AND type = '". \ze\escape::asciiInSQL(\ze::$cType). "'
		  AND version = ". (int) \ze::$cVersion;
	$result = \ze\sql::select($sql);
	$itemHTML = \ze\sql::fetchAssoc($result);
	
	switch ($itemHTML['head_cc']) {
		case 'needed':
			if (!\ze\cookie::canSet()) {
				$itemHTML['head_html'] = $itemHTML['head_overwrite'] = false;
			}
			break;
		case 'specific_types':
			$cookieType = $itemHTML['head_cc_specific_cookie_types'];
			if (!(\ze::in($cookieType, 'functionality', 'analytics', 'social_media') && \ze\cookie::canSet($cookieType))) {
				$itemHTML['head_html'] = $itemHTML['head_overwrite'] = false;
			}
			break;
	}
	
	//Look up the background image and any HTML to add to the HEAD from the layout
	$sql = "
		SELECT head_html, head_cc, head_cc_specific_cookie_types, head_visitor_only, bg_image_id, bg_color, bg_position, bg_repeat
		FROM ". DB_PREFIX. "layouts
		WHERE layout_id = ". (int) \ze::$layoutId;
	$result = \ze\sql::select($sql);
	$templateHTML = \ze\sql::fetchAssoc($result);
	
	//Only add html from the layout if it's not been overridden on the Content Item
	if (empty($itemHTML['head_overwrite'])) {
		switch ($templateHTML['head_cc']) {
			case 'needed':
				if (!\ze\cookie::canSet()) {
					$templateHTML['head_html'] = $templateHTML['head_overwrite'] = false;
				}
				break;
			case 'specific_types':
				$cookieType = $templateHTML['head_cc_specific_cookie_types'];
				if (!(\ze::in($cookieType, 'functionality', 'analytics', 'social_media') && \ze\cookie::canSet($cookieType))) {
					$templateHTML['head_html'] = $templateHTML['head_overwrite'] = false;
				}
				break;
		}
		
		if (!empty($templateHTML['head_html']) && (empty($templateHTML['head_visitor_only']) || !$isAdmin)) {
			echo "\n\n". $templateHTML['head_html'], "\n\n";
		}
	}
	
	if (!empty($itemHTML['head_html']) && (empty($itemHTML['head_visitor_only']) || !$isAdmin)) {
		echo "\n\n". $itemHTML['head_html'], "\n\n";
	}
	
	
	//Check to see if there is a background image on this content item (or on this layout if not on the content item)
	if ($itemHTML['bg_image_id']) {
		ze\file::imageLink($bgWidth, $bgHeight, $bgURL, $itemHTML['bg_image_id']);
	} elseif ($templateHTML['bg_image_id']) {
		ze\file::imageLink($bgWidth, $bgHeight, $bgURL, $templateHTML['bg_image_id']);
	}
	
	$bgColor = $itemHTML['bg_color']? $itemHTML['bg_color'] : $templateHTML['bg_color'];
	$bgPosition = $itemHTML['bg_position']? $itemHTML['bg_position'] : $templateHTML['bg_position'];
	$bgRepeat = $itemHTML['bg_repeat']? $itemHTML['bg_repeat'] : $templateHTML['bg_repeat'];
	
	if ($bgURL || $bgColor || $bgPosition || $bgRepeat) {
		
		$background_selector = 'body';
		if (\ze::$skinId) {
			$background_selector = \ze\row::get('skins', 'background_selector', \ze::$skinId);
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

echo '<script type="text/javascript">window.zenarioCodeVersion = "', $codeVersion, '"</script>';
