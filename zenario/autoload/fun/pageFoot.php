<?php
/*
 * Copyright (c) 2020, Tribal Limited
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

$v = $w = 'v='. \ze\db::codeVersion();

if (!\ze::$cacheWrappers) {
	$w .= '&amp;no_cache=1';
}

$isWelcome = $mode === true || $mode === 'welcome';
$isWizard = $mode === 'wizard';
$isWelcomeOrWizard = $isWelcome || $isWizard;
$isOrganizer = $mode === 'organizer';
$isAdmin = \ze::isAdmin();

if ($absURL = \ze\link::absoluteIfNeeded(!$isWelcomeOrWizard)) {
	$prefix = $absURL. 'zenario/';
}

if (!$isAdmin
 && !$isWelcomeOrWizard
 && $defer
 && \ze::setting('defer_js')) {
	$scriptTag = '<script type="text/javascript" defer';
	$inlineStart = "zOnLoad(function() {";
	$inlineStop = '});';
} else {
	$scriptTag = '<script type="text/javascript"';
	$inlineStart = $inlineStop = '';
}


//Write the URLBasePath to the page, and add JS needed for the CMS
echo '
'. $scriptTag. ' src="', $prefix, 'libs/yarn/jquery/dist/jquery.min.js?', $v, '"></script>
'. $scriptTag. ' src="', $prefix, 'libs/manually_maintained/mit/jquery/jquery-ui.visitor.min.js?', $v, '"></script>
'. $scriptTag. ' src="', $prefix, 'js/visitor.wrapper.js.php?', $w, '"></script>';


$currentLangId = \ze\content::currentLangId();

//Write other related JavaScript variables to the page
	//Note that page caching may cause the wrong user id to be set.
	//As with ($_SESSION['extranetUserID'] ?? false), anything that changes behaviour by Extranet User should not allow the page to be cached.
echo '
'. $scriptTag. '>'. $inlineStart. 'zenario.init("'.
	\ze::setting('css_js_version'). '",',
	(int) ($_SESSION['extranetUserID'] ?? 0), ',"',
	\ze\escape::js(\ze\content::currentLangId()), '","',
	\ze\escape::js(\ze::setting('vis_date_format_datepicker')), '","',
	\ze\escape::js(\ze::setting('min_extranet_user_password_length')), '","',
	\ze\escape::js(\ze::setting('a_z_lowercase_characters')), '","',
	\ze\escape::js(\ze::setting('a_z_uppercase_characters')), '","',
	\ze\escape::js(\ze::setting('0_9_numbers_in_user_password')), '","',
	\ze\escape::js(\ze::setting('symbols_in_user_password')), '","',
	\ze\escape::js(DIRECTORY_INDEX_FILENAME), '",',
	(int) \ze\cookie::canSet(), ',',
	(int) \ze::$equivId, ',',
	(int) \ze::$cID, ',"', \ze\escape::js(\ze::$cType), '",',
	(int) \ze::$skinId, ',',
	json_encode(\ze::$isPublic), ',',
	(int) \ze::setting('mod_rewrite_slashes'), ',"',
	\ze\escape::js(\ze::$langs[\ze::$visLang]['thousands_sep'] ?? ''), '","', \ze\escape::js(\ze::$langs[\ze::$visLang]['dec_point'] ?? ''), '"';

if (\ze::$visLang && \ze::$visLang != $currentLangId) {
	echo ',"', \ze\escape::js(\ze::$visLang), '"';
}


echo ');'. $inlineStop. '</script>';





	
//Add JS needed for the CMS in Admin mode
if ($isAdmin) {
	//Write all of the slot controls to the page
	echo '
<div id="zenario_slotControls">';
	\ze\pluginAdm::setupSlotControls(\ze::$slotContents, false);
	
	echo '
</div>';
	
	//Note down that we need various extra libraries in admin mode...
	\ze::requireJsLib('zenario/js/ace.wrapper.js.php');
}

if (\ze::$cID && \ze::$visLang && !$isWelcomeOrWizard) {
	\ze::requireJsLib('zenario/js/visitor.phrases.js.php?langId='. \ze::$visLang);
}


if ($isAdmin || $isWelcomeOrWizard) {
	//...or on the admin-login screen
	\ze::requireJsLib('zenario/js/tuix.wrapper.js.php');
	\ze::requireJsLib('zenario/libs/manually_maintained/mit/jquery/jquery-ui.interactions.min.js');
	\ze::requireJsLib('zenario/libs/manually_maintained/mit/jquery/jquery-ui.datepicker.min.js');
	\ze::requireJsLib('zenario/js/admin.microtemplates_and_phrases.js.php');
	\ze::requireJsLib('zenario/js/admin.wrapper.js.php');
}


$checkPrefixes = $prefix != 'zenario/';

foreach (\ze::$jsLibs as $lib => $stylesheet) {
	
	if ($stylesheet) {
		if ($checkPrefixes) {
			if ($stylesheet[0] != '/'
			 && strpos($stylesheet, '://') === false) {
				$stylesheet = $prefix. '../'. $stylesheet;
			}
		}
		echo "\n", '<link rel="stylesheet" type="text/css" media="screen" href="', $prefix, htmlspecialchars($stylesheet). '?', $v, '"/>';
	}
	
	if ($lib[0] != '/'
	 && strpos($lib, '://') === false) {
		
		if ($checkPrefixes) {
			$lib = $prefix. '../'. $lib;
		}
		
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
	

if ($isAdmin && !$isWelcomeOrWizard) {
	require CMS_ROOT. 'zenario/autoload/fun/pageFootInAdminMode.php';
}


//Add JS needed for modules
if (!$isWelcomeOrWizard && \ze::$pluginJS) {
	if ($isAdmin) {
		\ze::$pluginJS .= '&amp;admin=1';
	}
	
	echo '
'. $scriptTag. ' src="', $prefix, 'js/plugin.wrapper.js.php?', $w, '&amp;ids=', \ze::$pluginJS, '"></script>';
}


//Add JS needed for modules in Admin Mode in the frontend
if ($isAdmin && \ze::$cID) {
	$jsModuleIds = '';
	foreach (\ze\module::runningModules() as $module) {
		if (\ze::moduleDir($module['class_name'], 'js/admin_frontend.js', true)
		 || \ze::moduleDir($module['class_name'], 'js/admin_frontend.min.js', true)) {
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
				zenarioA.draftDoCallback(
					"', \ze\escape::js($_SESSION['zenario_draft_callback']), '",
					', (int) ($_SESSION['zenario_draft_callback_scroll_pos'] ?? 0), '
				);
			});
		</script>';
		
		unset($_SESSION['zenario_draft_callback']);
	}
}

//Are there plugins on this page..?
if (!empty(\ze::$slotContents) && is_array(\ze::$slotContents)) {
	//Include the JS for any plugin instances on the page, if they have any
	$scriptTypes = [[], [], []];
	foreach(\ze::$slotContents as $slotName => &$instance) {
		if (!empty($instance['class'])) {
			$scriptTypesHere = [];
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
	foreach(\ze::$slotContents as $slotName => &$instance) {
		if (!empty($instance['class'])) {
			\ze\plugin::preSlot($slotName, 'addToPageFoot');
				$instance['class']->addToPageFoot();
			\ze\plugin::postSlot($slotName, 'addToPageFoot');
		}
	}
}

//Are there Plugins on this page..?
if (!empty(\ze::$slotContents) && is_array(\ze::$slotContents)) {
	echo '
'. $scriptTag. '>'. $inlineStart;
	//Add encapculated objects for slots
	$i = 0;
	echo "\n", 'zenario._s([';
	foreach (\ze::$slotContents as $slotName => &$instance) {
		if (isset($instance['class']) || $isAdmin) {
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
				
				if ($isAdmin) {
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
if (\ze::$cID) {
	$itemHTML = $templateHTML = $familyHTML = false;
	
	$sql = "
		SELECT foot_html, foot_cc, foot_visitor_only, foot_overwrite
		FROM ". DB_PREFIX. "content_item_versions
		WHERE id = ". (int) \ze::$cID. "
		  AND type = '". \ze\escape::sql(\ze::$cType). "'
		  AND version = ". (int) \ze::$cVersion;
	$result = \ze\sql::select($sql);
	$itemHTML = \ze\sql::fetchRow($result);
	
	switch ($itemHTML[1]) {
		case 'required':
			\ze::$cookieConsent = 'require';
		case 'needed':
			if (!\ze\cookie::canSet()) {
				unset($itemHTML);
			}
	}
	
	if (empty($itemHTML[3])) {
		$sql = "
			SELECT foot_html, foot_cc, foot_visitor_only
			FROM ". DB_PREFIX. "layouts
			WHERE layout_id = ". (int) \ze::$layoutId;
		$result = \ze\sql::select($sql);
		$templateHTML = \ze\sql::fetchRow($result);
		
		switch ($templateHTML[1]) {
			case 'required':
				\ze::$cookieConsent = 'require';
			case 'needed':
				if (!\ze\cookie::canSet()) {
					unset($templateHTML);
				}
		}
		
		if (!empty($templateHTML[0]) && (empty($templateHTML[2]) || !$isAdmin)) {
			echo "\n\n". $templateHTML[0], "\n\n";
		}
	}
	
	if (!empty($itemHTML[0]) && (empty($itemHTML[2]) || !$isAdmin)) {
		echo "\n\n". $itemHTML[0], "\n\n";
	}
}

if (\ze::$cID && $includeAdminToolbar && $isAdmin && !$isWelcomeOrWizard) {
	$data_rev = (int) \ze\row::get('local_revision_numbers', 'revision_no', ['path' => 'data_rev']);
	
	$params = htmlspecialchars(http_build_query([
		'id' => \ze::$cType. '_'. \ze::$cID,
		'cID' => \ze::$cID,
		'cType' => \ze::$cType,
		'cVersion' => \ze::$cVersion,
		'get' => $importantGetRequests,
		'_script' => 1,
		'data_rev' => (int) \ze\row::get('local_revision_numbers', 'revision_no', ['path' => 'data_rev'])
	]));
	
	echo '
'. $scriptTag. ' src="', $prefix, 'admin/admin_toolbar.ajax.php?', $v, '&amp;', $params, '"></script>';
}