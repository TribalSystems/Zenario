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

$gzf = setting('compress_web_pages')? '?gz=1' : '?gz=0';
$gz = setting('compress_web_pages')? '&amp;gz=1' : '&amp;gz=0';
$v = ifNull(setting('css_js_version'), ZENARIO_CMS_VERSION);

$isWelcome = $mode === true || $mode === 'welcome';
$isOrganizer = $mode === 'organizer';

if (!$isWelcome && $cookieFreeDomain = cookieFreeDomain()) {
	$prefix = $cookieFreeDomain. 'zenario/';
}


//Write the URLBasePath to the page
echo '
<script type="text/javascript">
	var zenarioCSSJSVersionNumber = "'. setting('css_js_version'). '";
	var URLBasePath = "', jsEscape(httpOrhttps(). $_SERVER["HTTP_HOST"] . SUBDIRECTORY), '";
</script>';


//Add JS needed for the CMS
echo '
<script type="text/javascript" src="', $prefix, 'libraries/mit/jquery/jquery.min.js?v=', $v, '"></script>
<script type="text/javascript" src="', $prefix, 'libraries/mit/jquery/jquery-ui.visitor.min.js?v=', $v, '"></script>
<script type="text/javascript" src="', $prefix, 'js/inc.js.php?v=', $v, $gz, '"></script>';
	
//Add JS needed for the CMS in Admin mode
if ($isWelcome || checkPriv()) {
	
	if (!$isWelcome) {
		checkForChangesInPhpFiles();
		checkForChangesInYamlFiles();
	}
	
	setupAdminFloatingBoxes();
	
	echo '
<script type="text/javascript" src="', $prefix, 'libraries/mit/jquery/jquery-ui.admin.min.js?v=', $v, '"></script>
<script type="text/javascript" src="', $prefix, 'libraries/mit/jquery/jquery-ui.datepicker.min.js?v=', $v, '"></script>
<script type="text/javascript" src="', $prefix, 'libraries/bsd/ace/src-min-noconflict/ace.js?v=', $v, '"></script>
<script type="text/javascript" src="', $prefix, 'js/inc-admin.js.php?v=', $v, $gz, '"></script>';
	
	if (setting('dropbox_api_key')) {
		echo '
			<script type="text/javascript" src="https://www.dropbox.com/static/api/2/dropins.js" id="dropboxjs" data-app-key="', htmlspecialchars(setting('dropbox_api_key')), '"></script>';
	}
	
	if ($includeOrganizer) {
		$moduleCodeHash = setting('php_version'). '___'. setting('yaml_version');
		
		$jsModuleIds = '';
		foreach (getRunningModules() as $module) {
			if (moduleDir($module['class_name'], 'js/organizer.js', true)
			 || moduleDir($module['class_name'], 'js/organizer.min.js', true)
			 || moduleDir($module['class_name'], 'js/storekeeper.js', true)
			 || moduleDir($module['class_name'], 'js/storekeeper.min.js', true)) {
				$jsModuleIds .= ($jsModuleIds? ',' : ''). $module['id'];
			}
		}
		
		$sql = "
			SELECT 1
			FROM ". DB_NAME_PREFIX. "tuix_file_contents AS tfc
			INNER JOIN ". DB_NAME_PREFIX. "modules AS m
			   ON m.class_name = tfc.module_class_name
			  AND m.status = 'module_running'
			WHERE tfc.panel_type IN ('google_map', 'google_map_or_list')
			LIMIT 1";
		
		if (($result = sqlSelect($sql)) && (sqlFetchRow($result))) {
			echo '
<script type="text/javascript" src="https://maps.google.com/maps/api/js"></script>';
		}
		
		echo '
<script type="text/javascript" src="', $prefix, 'js/inc-organizer.js.php?v=', $v, $gz, '"></script>';
		
		echo '
<script type="text/javascript">';
		
		echo '
	zenarioA.moduleCodeHash = "', $moduleCodeHash, '";';
		
		
		if ($apacheMaxFilesize = apacheMaxFilesize()) {
			echo '
	zenarioA.maxUpload = ', (int) $apacheMaxFilesize, ';
	zenarioA.maxUploadF = "', jsEscape(formatFilesizeNicely($apacheMaxFilesize, $precision = 0, $adminMode = true)), '";';
		}
		
			echo '
</script>
<script type="text/javascript" src="', $prefix, 'js/plugin.js.php?v=', $v, $gz, '&amp;ids=', $jsModuleIds, '&amp;organizer=1"></script>';
	}
}
if (cms_core::$cID && $includeAdminToolbar && checkPriv()) {
	echo '
<script type="text/javascript" src="', $prefix, 'js/admin_toolbar.min.js?v=', $v, '"></script>';
}


//Write other related JavaScript variables to the page
	//Note that page caching may cause the wrong user id to be set.
	//As with session('extranetUserID'), anything that changes behaviour by Extranet User should not allow the page to be cached.
echo '
<script type="text/javascript">
	zenario.userId = ', (int) session('extranetUserID'), ';
	zenario.langId = "', jsEscape(session('user_lang')), '";
	zenario.dpf = "', jsEscape(setting('vis_date_format_datepicker')), '";
	zenario.indexDotPHP = "'. jsEscape(indexDotPHP()). '";
	zenario.canSetCookie = ', (int) canSetCookie(), ';';
	
//Add location information about a content item if it is available
if (cms_core::$cID) {
	echo '
	zenario.cID = ', (int) cms_core::$cID, ';';
}
if (cms_core::$cType) {
	echo '
	zenario.cType = "', jsEscape(cms_core::$cType), '";';
}
if (cms_core::$alias
 && $aliasAndLangCode = linkToItem(cms_core::$cID, cms_core::$cType, false, '', cms_core::$alias, false, true, false, true)) {
	echo '
	zenario.aliasAndLangCode = "', jsEscape($aliasAndLangCode), '";';
}

if (!$isWelcome && checkPriv()) {
	if (cms_core::$cVersion) {
		echo '
		zenario.cVersion = ', (int) cms_core::$cVersion, ';';
	}
	
	if (checkPriv('_PRIV_VIEW_DEV_TOOLS')) {
		echo '
		zenario.showDevTools = true;';
	}
	
	$settings = array();
	if (!empty(cms_core::$siteConfig)) {
		foreach (cms_core::$siteConfig as $setting => &$value) {
			if ($value
			 && ($setting == 'cookie_require_consent'
			  || $setting == 'default_language'
			  || $setting == 'organizer_title'
			  || $setting == 'organizer_date_format'
			  || $setting == 'vis_time_format'
			  || is_numeric($value))) {
				$settings[$setting] = cms_core::$siteConfig[$setting];
			}
		}
	}
	
	echo '
	zenarioA.toolbar = \'', jsEscape(ifNull(session('page_toolbar'), 'preview')), '\';
	zenarioA.pageMode = \'', jsEscape(ifNull(session('page_mode'), 'preview')), '\';
	zenarioA.slotWandOn = ', engToBoolean(session('admin_slot_wand')), ';
	zenarioA.showGridOn = ', engToBoolean(session('admin_show_grid')), ';
	zenarioA.siteSettings = ', json_encode($settings), ';';
	
	
	//Get a list of language names and flags for use in the formatting options
	//We only need enabled languages if this is not Organizer
	$langs = array();
	$onlyShowEnabledLanguages = (bool) cms_core::$cID;
	if (!$onlyShowEnabledLanguages) {
		$enabledLangs = getLanguages();
	}
	foreach (getLanguages(!cms_core::$cID) as $lang) {
		$langs[$lang['id']] = array('name' => $lang['english_name']);
		
		if ($onlyShowEnabledLanguages || !empty($enabledLangs[$lang['id']])) {
			$langs[$lang['id']]['enabled'] = 1;
		}
	}
	
	echo "\n	zenarioA.lang = ", json_encode($langs), ";";
	echo "\n	zenario.adminId = ", (int) session('admin_userid'), ";";
	echo "\n	zenario.templateFamily = '", jsEscape(cms_core::$templateFamily), "';";
}

if (cms_core::$skinId) {
	echo "\n	zenario.skinId = ", (int) cms_core::$skinId, ";";
}

if (!$isWelcome) {
	if (setting('mod_rewrite_suffix') !== false) {
		echo '
	zenario.suffix = "', urlencode(setting('mod_rewrite_suffix')), '";';
	}
	echo '
	zenario.useGZ = ', (int) setting('compress_web_pages'), ';';
}

echo '
</script>';


//Add JS needed for modules
if (!$isWelcome && cms_core::$pluginJS) {
	if (checkPriv()) {
		cms_core::$pluginJS .= '&amp;admin=1';
	}
	
	echo '
<script type="text/javascript" src="', $prefix, 'js/plugin.js.php?v=', $v, '&amp;ids=', cms_core::$pluginJS, $gz, '"></script>';
}

//Are there Plugins on this page..?
if (!empty(cms_core::$slotContents) && is_array(cms_core::$slotContents)) {
	echo '
<script type="text/javascript">';
	//Add encapculated objects for slots
	$i = 0;
	echo '
	zenario.slot([';
	foreach (cms_core::$slotContents as $slotName => &$instance) {
		if (isset($instance['class']) || checkPriv()) {
			echo
				$i++? ',' : '',
				'["',
					preg_replace('/[^\w-]/', '', $slotName), '",',
					(int) arrayKey($instance, 'instance_id'), ',',
					(int) arrayKey($instance, 'module_id');
			
			if (isset($instance['class']) && $instance['class']) {
				
				//For filled slots, set the level, tab id and whether this looks like the main slot.
				//The tab id is used for Plugin Nests, and is the id of the current tab being displayed.
				//The Main Slot is what we think the primary feature on this page is; it's slot name will not be shown after the # and between the !
				//in the hashes used for AJAX reloads to make the hashs look a little friendlier
				//In admin mode we also note down which plugins are version controlled
				
				echo ',', (int) arrayKey($instance, 'level');
				
				$tabId = $instance['class']->tApiGetTabId();
				$isMainSlot = isset($instance['class']) && arrayKey($instance, 'level') == 1 && substr($slotName, 0, 1) == 'M';
				$beingEdited = $instance['class']->beingEdited();
				
				if (checkPriv()) {
					$isVersionControlled = (int) !empty($instance['content_id']);
					
					echo ',', (int) $tabId, ',', (int) $isMainSlot, ',', (int) $beingEdited, ',', (int) $isVersionControlled;
				} elseif ($beingEdited) {
					echo ',', (int) $tabId, ',', (int) $isMainSlot, ',', (int) $beingEdited;
				} elseif ($isMainSlot) {
					echo ',', (int) $tabId, ',', (int) $isMainSlot;
				} elseif ($tabId) {
					echo ',', (int) $tabId;
				}
			}
			
			echo ']';
		}
	}
	echo ']);
</script>';

	//Include the JS for any plugin instances on the page, if they have any
	$JavaScriptOnPage = array();
	foreach(cms_core::$slotContents as $slotName => &$instance) {
		if (!empty($instance['class'])) {
			$instance['class']->tApiAddRequestedScripts();
		}
	}
}


//Add JS needed for modules in Admin Mode in the frontend
if (checkPriv() && cms_core::$cID) {
	$jsModuleIds = '';
	foreach (getRunningModules() as $module) {
		if (moduleDir($module['class_name'], 'js/admin_frontend.js', true)
		 || moduleDir($module['class_name'], 'js/admin_frontend.min.js', true)) {
			$jsModuleIds .= ($jsModuleIds? ',' : ''). $module['id'];
		}
	}
	
	if ($jsModuleIds) {
		echo '
<script type="text/javascript" src="', $prefix, 'js/plugin.js.php?v=', $v, '&amp;ids=', $jsModuleIds, $gz, '&amp;admin_frontend=1"></script>';
	}
	
	//If we've just made a draft, and there's a callback, perform the callback
	if (!empty($_SESSION['zenario_draft_callback'])) {
		echo '
		<script type="text/javascript">
			$(document).ready(function() {
				zenarioA.draftDoCallback("', jsEscape($_SESSION['zenario_draft_callback']), '");
			});
		</script>';
		
		unset($_SESSION['zenario_draft_callback']);
	}
}

//Are there plugins on this page..?
if (!empty(cms_core::$slotContents) && is_array(cms_core::$slotContents)) {
	//Include the Foot for any plugin instances on the page, if they have one
	foreach(cms_core::$slotContents as $slotName => &$instance) {
		if (!empty($instance['class'])) {
			cms_core::preSlot($slotName, 'addToPageFoot');
				$instance['class']->addToPageFoot();
			cms_core::postSlot($slotName, 'addToPageFoot');
		}
	}
}


//Check to see if there is anything we need to output from the head/foot slots.
//We need to pay attention to the logic for showing to admins, cookie consent, and overriding
if (cms_core::$cID) {
	$itemHTML = $templateHTML = $familyHTML = false;
	
	$sql = "
		SELECT foot_html, foot_cc, foot_visitor_only, foot_overwrite
		FROM ". DB_NAME_PREFIX. "versions
		WHERE id = ". (int) cms_core::$cID. "
		  AND type = '". sqlEscape(cms_core::$cType). "'
		  AND version = ". (int) cms_core::$cVersion;
	$result = sqlQuery($sql);
	$itemHTML = sqlFetchRow($result);
	
	switch ($itemHTML[1]) {
		case 'required':
			cms_core::$cookieConsent = 'require';
		case 'needed':
			if (!canSetCookie()) {
				unset($itemHTML);
			}
	}
	
	if (empty($itemHTML[3])) {
		$sql = "
			SELECT foot_html, foot_cc, foot_visitor_only
			FROM ". DB_NAME_PREFIX. "layouts
			WHERE layout_id = ". (int) cms_core::$layoutId;
		$result = sqlQuery($sql);
		$templateHTML = sqlFetchRow($result);
		
		switch ($templateHTML[1]) {
			case 'required':
				cms_core::$cookieConsent = 'require';
			case 'needed':
				if (!canSetCookie()) {
					unset($templateHTML);
				}
		}
		
		if (!empty($templateHTML[0]) && (empty($templateHTML[2]) || !checkPriv())) {
			echo "\n\n". $templateHTML[0], "\n\n";
		}
	}
	
	if (!empty($itemHTML[0]) && (empty($itemHTML[2]) || !checkPriv())) {
		echo "\n\n". $itemHTML[0], "\n\n";
	}
}
