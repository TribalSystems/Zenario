<?php
/*
 * Copyright (c) 2017, Tribal Limited
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
$v = zenarioCodeVersion();

$isWelcome = $mode === true || $mode === 'welcome';
$isWizard = $mode === 'wizard';
$isWelcomeOrWizard = $isWelcome || $isWizard;
$isOrganizer = $mode === 'organizer';
$inAdminMode = checkPriv();

if (!$isWelcomeOrWizard && $cookieFreeDomain = cookieFreeDomain()) {
	$prefix = $cookieFreeDomain. 'zenario/';

} elseif (cms_core::$mustUseFullPath) {
	$prefix = absCMSDirURL(). 'zenario/';
}


//Write the URLBasePath to the page, and add JS needed for the CMS
echo '
<script type="text/javascript" src="', $prefix, 'libraries/mit/jquery/jquery.min.js?v=', $v, '"></script>
<script type="text/javascript" src="', $prefix, 'libraries/mit/jquery/jquery-ui.visitor.min.js?v=', $v, '"></script>

<!--[if IE 9]><script type="text/javascript" src="', $prefix, 'js/ie.wrapper.js.php?ie=9"></script><![endif]-->
<!--[if IE 8]><script type="text/javascript" src="', $prefix, 'js/ie.wrapper.js.php?ie=8"></script><![endif]-->
<!--[if lte IE 7]><script type="text/javascript" src="', $prefix, 'js/ie.wrapper.js.php?ie=7"></script><![endif]-->

<script type="text/javascript">
	var zenarioCSSJSVersionNumber = "'. setting('css_js_version'). '";
	var URLBasePath = "', jsEscape(httpOrhttps(). $_SERVER["HTTP_HOST"] . SUBDIRECTORY), '";
</script>
<script type="text/javascript" src="', $prefix, 'js/visitor.wrapper.js.php?v=', $v, $gz, '"></script>';

	
//Add JS needed for the CMS in Admin mode
if ($isWelcomeOrWizard || $inAdminMode) {
	
	if (!$isWelcome) {
		checkForChangesInYamlFiles();
	}
	
	echo '
<div id="zenario_slotControls">';

	//Write all of the slot controls to the page
	setupAdminSlotControls(cms_core::$slotContents, false);

echo '
</div>
<script type="text/javascript" src="', $prefix, 'libraries/mit/jquery/jquery-ui.admin.min.js?v=', $v, '"></script>
<script type="text/javascript" src="', $prefix, 'libraries/mit/jquery/jquery-ui.datepicker.min.js?v=', $v, '"></script>
<script type="text/javascript" src="', $prefix, 'libraries/bsd/ace/src-min-noconflict/ace.js?v=', $v, '"></script>
<script type="text/javascript" src="', $prefix, 'js/admin.microtemplates_and_phrases.js.php?v=', $v, $gz, '"></script>
<script type="text/javascript" src="', $prefix, 'js/admin.wrapper.js.php?v=', $v, $gz, '"></script>';
	
	if (setting('dropbox_api_key')) {
		echo '
			<script type="text/javascript" src="https://www.dropbox.com/static/api/2/dropins.js" id="dropboxjs" data-app-key="', htmlspecialchars(setting('dropbox_api_key')), '"></script>';
	}
	
	if ($includeOrganizer) {
		$moduleCodeHash = zenarioCodeLastUpdated(). '___'. setting('yaml_version');
		
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
			SELECT DISTINCT tfc.panel_type
			FROM ". DB_NAME_PREFIX. "tuix_file_contents AS tfc
			INNER JOIN ". DB_NAME_PREFIX. "modules AS m
			   ON m.class_name = tfc.module_class_name
			  AND m.status IN ('module_running', 'module_is_abstract')
			WHERE tfc.panel_type IN ('google_map', 'list_or_grid_or_google_map', 'network_graph', 'schematic_builder')";
		
		$panelTypes = arrayValuesToKeys(sqlFetchValues($sql));
		
		if (isset($panelTypes['google_map']) || isset($panelTypes['list_or_grid_or_google_map'])) {
			if (!defined('ZENARIO_GOOGLE_MAP_ON_PAGE')) {
				define('ZENARIO_GOOGLE_MAP_ON_PAGE', true);
				echo '
<script type="text/javascript" src="https://maps.google.com/maps/api/js?libraries=geometry&key=' , urlencode(setting('google_maps_api_key')) , '"></script>';
			}
		}
		
		if (isset($panelTypes['network_graph'])) {
			echo '
<script type="text/javascript" src="', $prefix, 'libraries/mit/cytoscape/cytoscape.min.js"></script>';
		}
		
		if (isset($panelTypes['schematic_builder'])) {
			echo '
<script type="text/javascript" src="', $prefix, 'libraries/mit/fabricJS/fabricJS.min.js"></script>';
		}
		
		echo '
<script type="text/javascript" src="', $prefix, 'js/organizer.wrapper.js.php?v=', $v, $gz, '"></script>';
		echo '
<script type="text/javascript" src="', $prefix, 'admin/organizer.ajax.php?_script=1?v=', $moduleCodeHash, $gz, '"></script>';
		
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
<script type="text/javascript" src="', $prefix, 'js/plugin.wrapper.js.php?v=', $v, $gz, '&amp;ids=', $jsModuleIds, '&amp;organizer=1"></script>';
	}
}


//Write other related JavaScript variables to the page
	//Note that page caching may cause the wrong user id to be set.
	//As with session('extranetUserID'), anything that changes behaviour by Extranet User should not allow the page to be cached.
echo '
<script type="text/javascript">
	zenario.userId = ', (int) session('extranetUserID'), ';
	zenario.langId = "', jsEscape(session('user_lang')), '";
	zenario.dpf = "', jsEscape(setting('vis_date_format_datepicker')), '";
	zenario.indexDotPHP = "'. jsEscape(DIRECTORY_INDEX_FILENAME). '";
	zenario.canSetCookie = ', (int) canSetCookie(), ';';
	
//Add location information about a content item if it is available
if (cms_core::$equivId) {
	echo '
	zenario.equivId = ', (int) cms_core::$equivId, ';';
}
if (cms_core::$cID) {
	echo '
	zenario.cID = ', (int) cms_core::$cID, ';';
}
if (cms_core::$cType) {
	echo '
	zenario.cType = "', jsEscape(cms_core::$cType), '";';
}

if ($inAdminMode && !$isWelcomeOrWizard) {
	if (cms_core::$cVersion) {
		echo '
		zenario.cVersion = ', (int) cms_core::$cVersion, ';';
	}
	
	$settings = array();
	if (!empty(cms_core::$siteConfig)) {
		foreach (cms_core::$siteConfig as $setting => &$value) {
			if ($value
			 && ($setting == 'admin_domain'
			  || $setting == 'cookie_require_consent'
			  || $setting == 'default_language'
			  || $setting == 'organizer_title'
			  || $setting == 'organizer_date_format'
			  || $setting == 'primary_domain'
			  || $setting == 'site_mode'
			  || $setting == 'vis_time_format'
			  || is_numeric($value))) {
				$settings[$setting] = cms_core::$siteConfig[$setting];
			}
		}
	}
	$adminSettings = array();
	if (!empty(cms_core::$adminSettings)) {
		foreach (cms_core::$adminSettings as $setting => &$value) {
			if ($value
			 && (/*$setting == '...'
			  || $setting == '...'
			  || */is_numeric($value))) {
				$adminSettings[$setting] = cms_core::$adminSettings[$setting];
			}
		}
	}
	
	$importantGetRequests = importantGetRequests();
	if (empty($importantGetRequests)) {
		$importantGetRequests = '{}';
	} else {
		$importantGetRequests = json_encode($importantGetRequests);
	}
	
	echo '
	zenarioA.toolbar = \'', jsEscape(ifNull(session('page_toolbar'), 'preview')), '\';
	zenarioA.pageMode = \'', jsEscape(ifNull(session('page_mode'), 'preview')), '\';
	zenarioA.slotWandOn = ', engToBoolean(session('admin_slot_wand')), ';
	zenarioA.showGridOn = ', engToBoolean(session('admin_show_grid')), ';
	zenarioA.siteSettings = ', json_encode($settings), ';
	zenarioA.adminSettings = ', json_encode($adminSettings), ';
	zenarioA.importantGetRequests = ', $importantGetRequests, ';';
	
	if (adminHasSpecificPerms()) {
		echo '
	zenarioA.adminHasSpecificPerms = 1;';
	
		if (cms_core::$cID && cms_core::$cType) {
			echo '
	zenarioA.adminHasSpecificPermsOnThisPage = ', engToBoolean(checkPriv(false, cms_core::$cID, cms_core::$cType)), ';';
		}
	}
	
	
	
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

if (!$isWelcomeOrWizard) {
	echo '
	zenario.useGZ = ', (int) setting('compress_web_pages'), ';';
}

echo '
</script>';


//Add JS needed for modules
if (!$isWelcomeOrWizard && cms_core::$pluginJS) {
	if ($inAdminMode) {
		cms_core::$pluginJS .= '&amp;admin=1';
	}
	
	echo '
<script type="text/javascript" src="', $prefix, 'js/plugin.wrapper.js.php?v=', $v, '&amp;ids=', cms_core::$pluginJS, $gz, '"></script>';
}


//Add JS needed for modules in Admin Mode in the frontend
if ($inAdminMode && cms_core::$cID) {
	$jsModuleIds = '';
	foreach (getRunningModules() as $module) {
		if (moduleDir($module['class_name'], 'js/admin_frontend.js', true)
		 || moduleDir($module['class_name'], 'js/admin_frontend.min.js', true)) {
			$jsModuleIds .= ($jsModuleIds? ',' : ''). $module['id'];
		}
	}
	
	if ($jsModuleIds) {
		echo '
<script type="text/javascript" src="', $prefix, 'js/plugin.wrapper.js.php?v=', $v, '&amp;ids=', $jsModuleIds, $gz, '&amp;admin_frontend=1"></script>';
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
			$edition = cms_core::$edition;
			$edition::preSlot($slotName, 'addToPageFoot');
				$instance['class']->addToPageFoot();
			$edition::postSlot($slotName, 'addToPageFoot');
		}
	}
	
	if (!empty($scriptTypes[2])) {
		echo "\n". '<script type="text/javascript">(function(c) {';
		
		foreach ($scriptTypes[2] as &$scriptsForPlugin) {
			foreach ($scriptsForPlugin as &$script) {
				echo "\n", 'c(', json_encode($script), ');';
			}
		}
				
		echo "\n", '})(zenario.callScript);</script>';
	}
}

//Are there Plugins on this page..?
if (!empty(cms_core::$slotContents) && is_array(cms_core::$slotContents)) {
	echo '
<script type="text/javascript">';
	//Add encapculated objects for slots
	$i = 0;
	echo "\n", 'zenario.slot([';
	foreach (cms_core::$slotContents as $slotName => &$instance) {
		if (isset($instance['class']) || $inAdminMode) {
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
				
				$tabId = $instance['class']->zAPIGetTabId();
				$isMainSlot = isset($instance['class']) && arrayKey($instance, 'level') == 1 && substr($slotName, 0, 1) == 'M';
				$beingEdited = $instance['class']->beingEdited();
				
				if ($inAdminMode) {
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
	echo ']);';

	//Include the JS for any plugin instances on the page, if they have any
	$scriptTypes = array(array(), array(), array());
	foreach(cms_core::$slotContents as $slotName => &$instance) {
		if (!empty($instance['class'])) {
			$scriptTypesHere = array();
			$instance['class']->zAPICheckRequestedScripts($scriptTypesHere);
			
			if (!empty($scriptTypesHere[0])) {
				$scriptTypes[0][] = &$scriptTypesHere[0];
			}
			if (!empty($scriptTypesHere[1])) {
				$scriptTypes[1][] = &$scriptTypesHere[1];
			}
			if (!empty($scriptTypesHere[2])) {
				$scriptTypes[2][] = &$scriptTypesHere[2];
			}
		}
	}
	
	if (!empty($scriptTypes[0])
	 || !empty($scriptTypes[1])) {
		echo "\n". '(function(c) {';
		
		if (!empty($scriptTypes[0])) {
			foreach ($scriptTypes[0] as &$scriptsForPlugin) {
				foreach ($scriptsForPlugin as &$script) {
					echo "\n", 'c(', json_encode($script), ');';
				}
			}
		}
		if (!empty($scriptTypes[1])) {
			foreach ($scriptTypes[1] as &$scriptsForPlugin) {
				foreach ($scriptsForPlugin as &$script) {
					echo "\n", 'c(', json_encode($script), ');';
				}
			}
		}
				
		echo "\n", '})(zenario.callScript);';
	}
	
	echo "\n</script>";
}


//Check to see if there is anything we need to output from the head/foot slots.
//We need to pay attention to the logic for showing to admins, cookie consent, and overriding
if (cms_core::$cID) {
	$itemHTML = $templateHTML = $familyHTML = false;
	
	$sql = "
		SELECT foot_html, foot_cc, foot_visitor_only, foot_overwrite
		FROM ". DB_NAME_PREFIX. "content_item_versions
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
		
		if (!empty($templateHTML[0]) && (empty($templateHTML[2]) || !$inAdminMode)) {
			echo "\n\n". $templateHTML[0], "\n\n";
		}
	}
	
	if (!empty($itemHTML[0]) && (empty($itemHTML[2]) || !$inAdminMode)) {
		echo "\n\n". $itemHTML[0], "\n\n";
	}
}

if (cms_core::$cID && $includeAdminToolbar && $inAdminMode && !$isWelcomeOrWizard) {
	$data_rev = (int) getRow('local_revision_numbers', 'revision_no', array('path' => 'data_rev'));
	
	$params = htmlspecialchars(http_build_query(array(
		'id' => cms_core::$cType. '_'. cms_core::$cID,
		'cID' => cms_core::$cID,
		'cType' => cms_core::$cType,
		'cVersion' => cms_core::$cVersion,
		'get' => $importantGetRequests,
		'_script' => 1,
		'data_rev' => (int) getRow('local_revision_numbers', 'revision_no', array('path' => 'data_rev'))
	)));
	
	echo '
<script type="text/javascript" src="', $prefix, 'admin/admin_toolbar.ajax.php?v=', $v, '&amp;', $params, '"></script>';
}