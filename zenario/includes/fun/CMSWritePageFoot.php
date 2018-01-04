<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

$v = $w = 'v='. zenarioCodeVersion();

if (!cms_core::$cacheWrappers) {
	$w .= '&amp;no_cache=1';
}

$isWelcome = $mode === true || $mode === 'welcome';
$isWizard = $mode === 'wizard';
$isWelcomeOrWizard = $isWelcome || $isWizard;
$isOrganizer = $mode === 'organizer';
$inAdminMode = checkPriv();

if ($absURL = absURLIfNeeded(!$isWelcomeOrWizard)) {
	$prefix = $absURL. 'zenario/';
}

if (!$inAdminMode
 && !$isWelcomeOrWizard
 && $defer
 && setting('defer_js')) {
	$scriptTag = '<script type="text/javascript" defer';
	$inlineStart = "(window.addEventListener || (function(a,b){b();}))('DOMContentLoaded', function() {";
	$inlineStop = '});';
} else {
	$scriptTag = '<script type="text/javascript"';
	$inlineStart = $inlineStop = '';
}


//Write the URLBasePath to the page, and add JS needed for the CMS
echo '
'. $scriptTag. ' src="', $prefix, 'libraries/mit/jquery/jquery.min.js?', $v, '"></script>
'. $scriptTag. ' src="', $prefix, 'libraries/mit/jquery/jquery-ui.visitor.min.js?', $v, '"></script>

<!--[if IE 9]>'. $scriptTag. ' src="', $prefix, 'js/ie.wrapper.js.php?', $w, '&amp;ie=9"></script><![endif]-->
<!--[if IE 8]>'. $scriptTag. ' src="', $prefix, 'js/ie.wrapper.js.php?', $w, '&amp;ie=8"></script><![endif]-->
<!--[if lte IE 7]>'. $scriptTag. ' src="', $prefix, 'js/ie.wrapper.js.php?', $w, '&amp;ie=7"></script><![endif]-->

'. $scriptTag. ' src="', $prefix, 'js/visitor.wrapper.js.php?', $w, '"></script>';




//Write other related JavaScript variables to the page
	//Note that page caching may cause the wrong user id to be set.
	//As with ($_SESSION['extranetUserID'] ?? false), anything that changes behaviour by Extranet User should not allow the page to be cached.
echo '
'. $scriptTag. '>'. $inlineStart. 'zenario.init("'. setting('css_js_version'). '", ', (int) ($_SESSION['extranetUserID'] ?? false), ', "', jsEscape(currentLangId()), '", "', jsEscape(setting('google_recaptcha_theme')), '", "', jsEscape(setting('vis_date_format_datepicker')), '", "'. jsEscape(DIRECTORY_INDEX_FILENAME). '", ', (int) canSetCookie(), ', ', (int) cms_core::$equivId, ', ', (int) cms_core::$cID, ', "', jsEscape(cms_core::$cType), '", ', (int) cms_core::$skinId, ');'. $inlineStop. '</script>';





	
//Add JS needed for the CMS in Admin mode
if ($inAdminMode) {
	
	if (!$isWelcome) {
		checkForChangesInYamlFiles();
	}
	
	//Write all of the slot controls to the page
	echo '
<div id="zenario_slotControls">';
	setupAdminSlotControls(cms_core::$slotContents, false);
	
	echo '
</div>';
	
	//Note down that we need various extra libraries in admin mode...
	requireJsLib('js/ace.wrapper.js.php', null, true);
}

if ($inAdminMode || $isWelcomeOrWizard) {
	//...or on the admin-login screen
	requireJsLib('libraries/mit/jquery/jquery-ui.admin.min.js');
	requireJsLib('libraries/mit/jquery/jquery-ui.datepicker.min.js');
	requireJsLib('js/tuix.wrapper.js.php', null, true);
	requireJsLib('js/admin.microtemplates_and_phrases.js.php');
	requireJsLib('js/admin.wrapper.js.php', null, true);
}

foreach (cms_core::$jsLibs as $lib => $libInfo) {
	
	if ($libInfo[0]) {
		echo "\n", '<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, htmlspecialchars($libInfo[0]). '?', $libInfo[1]? $v : $w, '"/>';
	}
	echo "\n", $scriptTag, ' src="', $prefix, htmlspecialchars($lib). '?', $libInfo[1]? $v : $w, '"></script>';
}
	

if ($inAdminMode && !$isWelcomeOrWizard) {
	
	if (setting('dropbox_api_key')) {
		echo '
			'. $scriptTag. ' src="https://www.dropbox.com/static/api/2/dropins.js" id="dropboxjs" data-app-key="', htmlspecialchars(setting('dropbox_api_key')), '"></script>';
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
'. $scriptTag. ' src="https://maps.google.com/maps/api/js?libraries=geometry&key=' , urlencode(setting('google_maps_api_key')) , '"></script>';
			}
		}
		
		if (isset($panelTypes['network_graph'])) {
			echo '
'. $scriptTag. ' src="', $prefix, 'libraries/mit/cytoscape/cytoscape.min.js"></script>';
		}
		
		if (isset($panelTypes['schematic_builder'])) {
			echo '
'. $scriptTag. ' src="', $prefix, 'libraries/mit/fabricJS/fabricJS.min.js"></script>';
		}
		
		echo '
'. $scriptTag. ' src="', $prefix, 'js/organizer.wrapper.js.php?', $w, '"></script>';
		echo '
'. $scriptTag. ' src="', $prefix, 'admin/organizer.ajax.php?_script=1?v=', $moduleCodeHash, '"></script>';
		
		echo '
'. $scriptTag. '>';
		
		echo '
	zenarioA.moduleCodeHash = "', $moduleCodeHash, '";';
		
		
		if ($apacheMaxFilesize = apacheMaxFilesize()) {
			echo '
	zenarioA.maxUpload = ', (int) $apacheMaxFilesize, ';
	zenarioA.maxUploadF = "', jsEscape(formatFilesizeNicely($apacheMaxFilesize, $precision = 0, $adminMode = true)), '";';
		}
		
			echo '
</script>
'. $scriptTag. ' src="', $prefix, 'js/plugin.wrapper.js.php?', $w, '&amp;ids=', $jsModuleIds, '&amp;organizer=1"></script>';
	}
	
	
	$settings = array();
	if (!empty(cms_core::$siteConfig)) {
		foreach (cms_core::$siteConfig as $setting => &$value) {
			if ($value
			 && substr($setting, 0, 5) != 'perm.') {
				if (is_numeric($value)
				 || $setting == 'admin_domain'
				 || $setting == 'cookie_require_consent'
				 || $setting == 'default_language'
				 || $setting == 'organizer_title'
				 || $setting == 'organizer_date_format'
				 || $setting == 'primary_domain'
				 || $setting == 'site_mode'
				 || $setting == 'vis_time_format') {
					$settings[$setting] = cms_core::$siteConfig[$setting];
				
				} elseif (substr($setting, -5) == '_path') {
					$settings[$setting] = true;
				}
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
	
	$adminHasSpecificPermsOnThisPage = 0;
	if ($adminHasSpecificPerms = adminHasSpecificPerms()) {
		if (cms_core::$cID && cms_core::$cType) {
			$adminHasSpecificPermsOnThisPage = checkPriv(false, cms_core::$cID, cms_core::$cType);
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
	
	$spareDomains = array();
	$sql = '
	    SELECT requested_url FROM ' . DB_NAME_PREFIX . 'spare_domain_names';
	$result = sqlSelect($sql);
	while ($row = sqlFetchAssoc($result)) {
	    $spareDomains[] = httpOrhttps() . $row['requested_url'];
	}
	
	echo '
'. $scriptTag. '>
	zenarioA.init(
		', (int) cms_core::$cVersion, ',
		', (int) ($_SESSION['admin_userid'] ?? false), ',
		"', jsEscape(cms_core::$templateFamily), '",
		"', jsEscape(ifNull($_SESSION['page_toolbar'] ?? false, 'preview')), '",
		"', jsEscape(ifNull($_SESSION['page_mode'] ?? false, 'preview')), '",
		', engToBoolean($_SESSION['admin_show_grid'] ?? false), ',
		', json_encode($settings), ',
		', json_encode($adminSettings), ',
		', $importantGetRequests, ',
		', (int) $adminHasSpecificPerms, ',
		', (int) $adminHasSpecificPermsOnThisPage, ',
		', json_encode($langs), ',
		', json_encode($spareDomains), '
	);
</script>';
}


//Add JS needed for modules
if (!$isWelcomeOrWizard && cms_core::$pluginJS) {
	if ($inAdminMode) {
		cms_core::$pluginJS .= '&amp;admin=1';
	}
	
	echo '
'. $scriptTag. ' src="', $prefix, 'js/plugin.wrapper.js.php?', $w, '&amp;ids=', cms_core::$pluginJS, '"></script>';
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
'. $scriptTag. ' src="', $prefix, 'js/plugin.wrapper.js.php?', $w, '&amp;ids=', $jsModuleIds, '&amp;admin_frontend=1"></script>';
	}
	
	//If we've just made a draft, and there's a callback, perform the callback
	if (!empty($_SESSION['zenario_draft_callback'])) {
		echo '
		'. $scriptTag. '>
			$(document).ready(function() {
				zenarioA.draftDoCallback("', jsEscape($_SESSION['zenario_draft_callback']), '");
			});
		</script>';
		
		unset($_SESSION['zenario_draft_callback']);
	}
}

//Are there plugins on this page..?
if (!empty(cms_core::$slotContents) && is_array(cms_core::$slotContents)) {
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
		echo "\n". ''. $scriptTag. '>'. $inlineStart. '(function(c) {';
		
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
		
		echo "\n", '})(zenario._cS);'. $inlineStop. '</script>';
	}
	
	//Include the Foot for any plugin instances on the page, if they have one
	foreach(cms_core::$slotContents as $slotName => &$instance) {
		if (!empty($instance['class'])) {
			$edition = cms_core::$edition;
			$edition::preSlot($slotName, 'addToPageFoot');
				$instance['class']->addToPageFoot();
			$edition::postSlot($slotName, 'addToPageFoot');
		}
	}
}

//Are there Plugins on this page..?
if (!empty(cms_core::$slotContents) && is_array(cms_core::$slotContents)) {
	echo '
'. $scriptTag. '>'. $inlineStart;
	//Add encapculated objects for slots
	$i = 0;
	echo "\n", 'zenario.slot([';
	foreach (cms_core::$slotContents as $slotName => &$instance) {
		if (isset($instance['class']) || $inAdminMode) {
			echo
				$i++? ',' : '',
				'["',
					preg_replace('/[^\w-]/', '', $slotName), '",',
					(int) ($instance['instance_id'] ?? false), ',',
					(int) ($instance['module_id'] ?? false);
			
			if (isset($instance['class']) && $instance['class']) {
				
				//For filled slots, set the level, slide id and whether this looks like the main slot.
				//The slide id is used for Plugin Nests, and is the id of the current slide being displayed.
				//The Main Slot is what we think the primary feature on this page is; it's slot name will not be shown after the # and between the !
				//in the hashes used for AJAX reloads to make the hashs look a little friendlier
				//In admin mode we also note down which plugins are version controlled
				
				echo ',', (int) ($instance['level'] ?? false);
				
				$slideId = $instance['class']->zAPIGetTabId();
				$isMainSlot = isset($instance['class']) && ($instance['level'] ?? false) == 1 && substr($slotName, 0, 1) == 'M';
				$beingEdited = $instance['class']->beingEdited();
				
				if ($inAdminMode) {
					$isVersionControlled = (int) !empty($instance['content_id']);
					
					echo ',', (int) $slideId, ',', (int) $isMainSlot, ',', (int) $beingEdited, ',', (int) $isVersionControlled;
				} elseif ($beingEdited) {
					echo ',', (int) $slideId, ',', (int) $isMainSlot, ',', (int) $beingEdited;
				} elseif ($isMainSlot) {
					echo ',', (int) $slideId, ',', (int) $isMainSlot;
				} elseif ($slideId) {
					echo ',', (int) $slideId;
				}
			}
			
			echo ']';
		}
	}
	echo ']);';
	
	if (!empty($scriptTypes[2])) {
		echo "\n". '(function(c) {';
		
		foreach ($scriptTypes[2] as &$scriptsForPlugin) {
			foreach ($scriptsForPlugin as &$script) {
				echo "\n", 'c(', json_encode($script), ');';
			}
		}
				
		echo "\n", '})(zenario._cS);';
	}
	
	echo $inlineStop. '</script>';
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
'. $scriptTag. ' src="', $prefix, 'admin/admin_toolbar.ajax.php?', $v, '&amp;', $params, '"></script>';
}