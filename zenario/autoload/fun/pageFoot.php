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

$v = $w = 'v='. ($codeVersion = \ze\db::codeVersion());

if (!ze::$cacheBundles) {
	$w .= '&amp;no_cache=1';
}

$isWelcome = $mode === true || $mode === 'welcome';
$isOrganizer = $mode === 'organizer';
$isAdmin = ze::isAdmin();

if ($absURL = \ze\link::absoluteIfNeeded(!$isWelcome)) {
	$prefix = $absURL. 'zenario/';
}

$bundlesNeedAbsPath = $absURL || $prefix !== 'zenario/';

if (!$isAdmin
 && !$isWelcome
 && $defer
 && ze::setting('defer_js')) {
	$scriptTag = '<script type="text/javascript" defer';
	$inlineStart = "zOnLoad(function() {";
	$inlineStop = '});';
} else {
	$scriptTag = '<script type="text/javascript"';
	$inlineStart = $inlineStop = '';
}


//Write the URLBasePath to the page, and add JS needed for the CMS
echo '
', $scriptTag, ' src="', $prefix, 'libs/yarn/jquery/dist/jquery.min.js?', $v, '"></script>
', $scriptTag, ' src="', $prefix, 'libs/manually_maintained/mit/jqueryui/jquery-ui.visitor.min.js?', $v, '"></script>
', $scriptTag, ' src="';

if ($bundlesNeedAbsPath) {
	echo htmlspecialchars(\ze\link::absolute());
}

echo ze\bundle::visitorJS(false, $codeVersion), '"></script>';


$currentLangId = \ze\content::currentLangId();

if (\ze\cookie::canSetAll()) {
	$canSetAll =
	$canSetNecessary =
	$canSetFunctional =
	$canSetAnalytic =
	$canSetSocial = true;

} elseif (\ze\cookie::isDecided()) {
	$canSetAll = false;
	$canSetNecessary = true;
	$canSetFunctional = \ze\cookie::canSet('functionality');
	$canSetAnalytic = \ze\cookie::canSet('analytics');
	$canSetSocial = \ze\cookie::canSet('social_media');

} else {
	$canSetAll =
	$canSetNecessary =
	$canSetFunctional =
	$canSetAnalytic =
	$canSetSocial = false;
}

//Write other related JavaScript variables to the page
	//Note that page caching may cause the wrong user id to be set.
	//As with ($_SESSION['extranetUserID'] ?? false), anything that changes behaviour by Extranet User should not allow the page to be cached.
echo '
', $scriptTag, '>', $inlineStart, 'zenario.init("',
	ze::setting('css_js_version'), '",',
	(int) ($_SESSION['extranetUserID'] ?? 0), ',"',
	\ze\escape::js(\ze\content::currentLangId()), '","',
	\ze\escape::js(ze::setting('vis_date_format_datepicker')), '","',
	\ze\escape::js(DIRECTORY_INDEX_FILENAME), '",',
	(int) $canSetAll, ',',
	(int) $canSetNecessary, ',',
	(int) $canSetFunctional, ',',
	(int) $canSetAnalytic, ',',
	(int) $canSetSocial, ',',
	(int) ze::$equivId, ',',
	(int) ze::$cID, ',',
	json_encode(ze::$isPublic), ',',
	(int) ze::setting('mod_rewrite_slashes'), ',"',
	\ze\escape::js(ze::$langs[ze::$visLang]['thousands_sep'] ?? ''), '","', \ze\escape::js(ze::$langs[ze::$visLang]['dec_point'] ?? ''), '"';

if (ze::$visLang && ze::$visLang != $currentLangId) {
	echo ',"', \ze\escape::js(ze::$visLang), '"';
}

echo ');', $inlineStop, '</script>';





	
//Add JS needed for the CMS in Admin mode
if ($isAdmin) {
	//Write all of the slot controls to the page
	echo '
<div id="zenario_slotControls">';
	\ze\pluginAdm::setupSlotControls(ze::$slotContents, false);
	
	echo '
</div>';
	
	//Note down that we need various extra libraries in admin mode...
	ze::requireJsLib('zenario/js/ace.bundle.js.php');
	ze::requireJsLib('zenario/libs/yarn/rcrop/dist/rcrop.min.js', 'zenario/libs/yarn/rcrop/dist/rcrop.min.css');
	
	//Add libraries for TinyMCE 6
	ze::requireJsLib('zenario/libs/yarn/tinymce/tinymce.min.js');
	ze::requireJsLib('zenario/libs/yarn/@tinymce/tinymce-jquery/dist/tinymce-jquery.min.js');
	ze::requireJsLib('zenario/js/tinymce.integration.min.js');
	
}


if ($isAdmin || $isWelcome) {
	//...or on the admin-login screen
	ze::requireJsLib('zenario/js/tuix.bundle.js.php');
	ze::requireJsLib('zenario/libs/manually_maintained/mit/colorbox/jquery.colorbox.min.js');
	ze::requireJsLib('zenario/libs/manually_maintained/mit/jqueryui/jquery-ui.datepicker.min.js');
	ze::requireJsLib('zenario/js/admin.microtemplates_and_phrases.js.php');
	ze::requireJsLib('zenario/js/admin.bundle.js.php');
	ze::requireJsLib('zenario/libs/yarn/zxcvbn/dist/zxcvbn.js');
	\ze::requireJsLib('zenario/js/password_functions.min.js');
}


//Catch the case where a dev has requested a specific library that's already being included in the main bundle
if (ze::setting('lib.colorbox')) {
	unset(ze::$jsLibs['zenario/libs/manually_maintained/mit/colorbox/jquery.colorbox.min.js']);
}
if (ze::setting('lib.doubletaptogo')) {
	unset(ze::$jsLibs['zenario/libs/yarn/jquery-doubletaptogo/dist/jquery.dcd.doubletaptogo.min.js']);
}

//Loop through all of the libraries we're trying to include
foreach (ze::$jsLibs as $lib => $stylesheet) {
	
	//Allow up to one stylesheet per library
	if ($stylesheet) {
		
		//If the stylesheet path is absolute, or external, don't do any manipulations
		if ($stylesheet[0] != '/'
		 && strpos($stylesheet, '://') === false) {
			
			//For relative paths, we need to deal with a mismatch between how they're specified
			//and how the $prefix variable works.
			
			//Check if this library is in the Zenario directory
			if ($zenarioLib = \ze\ring::chopPrefix('zenario/', $stylesheet)) {
				
				//If so, we can follow the $prefix rules and give this a nice relative URL
				$stylesheet = $prefix. $zenarioLib;
			} else {
				//Otherwise it will need the SUBDIRECTORY added
				$stylesheet = SUBDIRECTORY. $stylesheet;
			}
			
			//Auto add the cache-killer
			if (strpos($stylesheet, '?v=') === false
			 && strpos($stylesheet, '&v=') === false) {
				if (strpos($stylesheet, '?') === false) {
					$stylesheet .= '?'. $v;
				} else {
					$stylesheet .= '&'. $v;
				}
			}
		}
		
		echo "\n", '<link rel="stylesheet" type="text/css" media="screen" href="', htmlspecialchars($stylesheet), '"/>';
	}
	
	
	//Add the JS file
	
	//If the stylesheet path is absolute, or external, don't do any manipulations
	if ($lib[0] != '/'
	 && strpos($lib, '://') === false) {
		
		//For relative paths, we need to deal with a mismatch between how they're specified
		//and how the $prefix variable works.
		
		//Check if this library is in the Zenario directory
		if ($zenarioLib = \ze\ring::chopPrefix('zenario/', $lib)) {
			
			//If so, we can follow the $prefix rules and give this a nice relative URL
			$lib = $prefix. $zenarioLib;
		} else {
			//Otherwise it will need the SUBDIRECTORY added
			$lib = SUBDIRECTORY. $lib;
		}
		
		//Auto add the cache-killer
		if (strpos($lib, '?v=') === false
		 && strpos($lib, '&v=') === false) {
			if (strpos($lib, '?') === false) {
				$lib .= '?'. $v;
			} else {
				$lib .= '&'. $v;
			}
		}
	}
	
	echo "\n", $scriptTag, ' src="', htmlspecialchars($lib), '"></script>';
}
	

if ($isAdmin && !$isWelcome) {
	require CMS_ROOT. 'zenario/autoload/fun/pageFootInAdminMode.php';
}


//Add JS needed for modules
if (!$isWelcome) {
	echo '
', $scriptTag, ' src="';

	if ($bundlesNeedAbsPath) {
		echo htmlspecialchars(\ze\link::absolute());
	}
	
	echo ze\bundle::pluginJS(false, $codeVersion, false, false), '"></script>';
}

//Are there plugins on this page..?
if (!empty(ze::$slotContents) && is_array(ze::$slotContents)) {
	//Include the JS for any plugin instances on the page, if they have any
	$scriptTypes = [[], [], []];
	foreach(ze::$slotContents as $slotName => &$slot) {
		if ($slot->class()) {
			$scriptTypesHere = [];
			$slot->class()->zAPICheckRequestedScripts($scriptTypesHere);
			
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
		echo "\n", '', $scriptTag, '>', $inlineStart, '(function(c) {';
		
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
		
		echo "\n", '})(zenario._ioe);', $inlineStop, '</script>';
	}
	
	//Include the Foot for any plugin instances on the page, if they have one
	foreach(ze::$slotContents as $slotName => &$slot) {
		if ($slot->class()) {
			\ze\plugin::preSlot($slotName, 'addToPageFoot');
				$slot->class()->addToPageFoot();
			\ze\plugin::postSlot($slotName, 'addToPageFoot');
		}
	}
}

//Are there Plugins on this page..?
if (!empty(ze::$slotContents) && is_array(ze::$slotContents)) {
	echo '
', $scriptTag, '>', $inlineStart;
	//Add encapculated objects for slots
	$i = 0;
	echo "\n", 'zenario.slot([';
	foreach (ze::$slotContents as $slotName => &$slot) {
		if ($slot->class() || $isAdmin) {
			echo
				$i++? ',' : '',
				'["',
					preg_replace('/[^\w-]/', '', $slotName), '",',
					(int) $slot->instanceId(), ',',
					(int) $slot->moduleId();
			
			if ($slot->class()) {
				
				//For filled slots, set the level, slide id and whether this looks like the main slot.
				//The slide id is used for Plugin Nests, and is the id of the current slide being displayed.
				//The Main Slot is what we think the primary feature on this page is; it's slot name will not be shown after the # and between the !
				//in the hashes used for AJAX reloads to make the hashs look a little friendlier
				//In admin mode we also note down which plugins are version controlled
				
				echo ',', (int) $slot->level();
				
				$slideId = $slot->class()->zAPIGetTabId();
				$isMainSlot = $slot->level() == 1 && substr($slotName, 0, 1) == 'M';
				$beingEdited = $slot->beingEdited();
				$isVersionControlled = $slot->isVersionControlled();
				
				if ($isAdmin) {
					$isMenu = $slot->shownInMenuMode()? 1 : 0;
					$isMissing = $slot->missing()? 1 : 0;
					
					echo ',', (int) $slideId, ',', (int) $isMainSlot, ',', (int) $beingEdited, ',', (int) $isVersionControlled, ',', (int) $isMenu, ',', (int) $isMissing;
				} elseif ($isVersionControlled) {
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
		echo "\n", '(function(c) {';
		
		foreach ($scriptTypes[2] as &$scriptsForPlugin) {
			foreach ($scriptsForPlugin as &$script) {
				echo "\n", 'c(', json_encode($script), ');';
			}
		}
				
		echo "\n", '})(zenario._ioe);';
	}
	
	echo $inlineStop, '</script>';
}


//Check to see if there is anything we need to output from the head/foot slots.
//We need to pay attention to the logic for showing to admins, cookie consent, and overriding
if (ze::$cID && ze::$cID !== -1) {
	$itemHTML = $templateHTML = $familyHTML = false;
	
	
	//Include the site-wide foot first
	ze\content::sitewideHTML('sitewide_foot');
	if (ze\cookie::canSet('analytics') && ze::setting('sitewide_analytics_html_location') == 'foot') {
		ze\content::sitewideHTML('sitewide_analytics_html');
	}
	if (ze\cookie::canSet('social_media') && ze::setting('sitewide_social_media_html_location') == 'foot') {
		ze\content::sitewideHTML('sitewide_social_media_html');
	}
	
	
	$sql = "
		SELECT foot_html, foot_cc, foot_cc_specific_cookie_types, foot_visitor_only, foot_overwrite
		FROM ". DB_PREFIX. "content_item_versions
		WHERE id = ". (int) ze::$cID. "
		  AND type = '". \ze\escape::asciiInSQL(ze::$cType). "'
		  AND version = ". (int) ze::$cVersion;
	$result = \ze\sql::select($sql);
	$itemHTML = \ze\sql::fetchAssoc($result);
	
	switch ($itemHTML['foot_cc']) {
		case 'needed':
			if (!\ze\cookie::canSet()) {
				unset($itemHTML);
			}
			break;
		case 'specific_types':
			$cookieType = $itemHTML['foot_cc_specific_cookie_types'];
			if (!(ze::in($cookieType, 'functionality', 'analytics', 'social_media') && \ze\cookie::canSet($cookieType))) {
				unset($itemHTML);
			}
			break;
	}
	
	if (empty($itemHTML['foot_overwrite'])) {
		$sql = "
			SELECT foot_html, foot_cc, foot_cc_specific_cookie_types, foot_visitor_only
			FROM ". DB_PREFIX. "layouts
			WHERE layout_id = ". (int) ze::$layoutId;
		$result = \ze\sql::select($sql);
		$templateHTML = \ze\sql::fetchAssoc($result);
		
		switch ($templateHTML['foot_cc']) {
			case 'needed':
				if (!\ze\cookie::canSet()) {
					unset($templateHTML);
				}
				break;
			case 'specific_types':
				$cookieType = $templateHTML['foot_cc_specific_cookie_types'];
				if (!(ze::in($cookieType, 'functionality', 'analytics', 'social_media') && \ze\cookie::canSet($cookieType))) {
					unset($templateHTML);
				}
				break;
		}
		
		if (!empty($templateHTML['foot_html']) && (empty($templateHTML['foot_visitor_only']) || !$isAdmin)) {
			echo "\n\n", $templateHTML['foot_html'], "\n\n";
		}
	}
	
	if (!empty($itemHTML['foot_html']) && (empty($itemHTML['foot_visitor_only']) || !$isAdmin)) {
		echo "\n\n", $itemHTML['foot_html'], "\n\n";
	}
}

if (ze::$cID && $includeAdminToolbar && $isAdmin && !$isWelcome) {
	$data_rev = (int) \ze\row::get('local_revision_numbers', 'revision_no', ['path' => 'data_rev']);
	
	$params = htmlspecialchars(http_build_query([
		'id' => ze::$cType. '_'. ze::$cID,
		'cID' => ze::$cID,
		'cType' => ze::$cType,
		'cVersion' => ze::$cVersion,
		'get' => $importantGetRequests,
		'_script' => 1,
		'data_rev' => (int) \ze\row::get('local_revision_numbers', 'revision_no', ['path' => 'data_rev'])
	]));
	
	echo '
', $scriptTag, ' src="', $prefix, 'admin/admin_toolbar.ajax.php?', $v, '&amp;', $params, '"></script>';
}



if (!empty(ze::$dumps)) {
	echo "\n", $scriptTag, '>', $inlineStart, 'zenario.dumps(', json_encode(ze::$dumps), ');', $inlineStop, '</script>';
	ze::$dumps = [];
}