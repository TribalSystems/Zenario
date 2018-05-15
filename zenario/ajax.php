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

//Allow a plugin to show on a page on its own.


require 'basicheader.inc.php';
ze\cookie::startSession();


//Run pre-load actions

require ze::editionInclude('ajax.pre_load');


$type = false;
$path = false;
$methodCall = $_REQUEST['method_call'] ?? false;
if ($methodCall == 'handleOrganizerPanelAJAX') {
	ze::$skType = $type = 'organizer';
	ze::$skPath = $path = $_REQUEST['__path__'] ?? false;
}

$isForPlugin = ($_REQUEST['cID'] ?? false) && ($_REQUEST['cType'] ?? false) && ($_REQUEST['instanceId'] ?? false);

//Check which method call is being requested
//Some method calls are associated with instances and content items, and some are not
if ($methodCall == 'refreshPlugin'
 || $methodCall == 'showFloatingBox'
 || $methodCall == 'handlePluginAJAX'
 || $methodCall == 'pluginAJAX'
 || $methodCall == 'showRSS'
 || ($methodCall == 'showFile' && $isForPlugin)
 || ($methodCall == 'fillVisitorTUIX' && $isForPlugin)
 || ($methodCall == 'formatVisitorTUIX' && $isForPlugin)
 || ($methodCall == 'validateVisitorTUIX' && $isForPlugin)
 || ($methodCall == 'saveVisitorTUIX' && $isForPlugin)
 || ($methodCall == 'typeaheadSearchAJAX' && $isForPlugin)) {

	//showRSS and showFloatingBox method calls relate to content items
	require CMS_ROOT. 'zenario/visitorheader.inc.php';
	ze\cache::start();
	
	//Check the content item that this is being linked from, and whether the current user has permissions to access it
	$cID = $cType = $content = $version = $redirectNeeded = $aliasInURL = $instanceFound = false;
	ze\content::resolveFromRequest($cID, $cType, $redirectNeeded, $aliasInURL, $_GET, $_REQUEST, $_POST);
	
	if (!$cVersion = $_REQUEST['cVersion'] ?? false) {
		$cVersion = ze\content::appropriateVersion($cID, $cType);
	}
	
	
	$status = ze\content::getShowableContent($content, $version, $cID, $cType, ($_REQUEST['cVersion'] ?? false), $checkRequestVars = true);
	if (!$status || is_string($status)) {
		exit;
	}
	
	ze\content::setShowableContent($content, $version);
	
	
	//If this is a call to an RSS feed, and the slotname was not specified, try to see if this Content Item has a slot registered
	if ($methodCall == 'showRSS'
	 && empty($_REQUEST['slotName'])
	 && empty($_REQUEST['instanceId'])
	 && ze::$rss
	 && ($rss = explode('_', ze::$rss, 2))
	 && (!empty($rss[1]))) {
		$_REQUEST['slotName'] = $_GET['slotName'] = $rss[1];
		$_REQUEST['eggId'] = $_GET['eggId'] = $rss[0];
	}
	
	$instanceId = $_REQUEST['instanceId'] ?? false;
	$slotName = ze\ring::HTMLId($_REQUEST['slotName'] ?? false);
	
	//Use exact matching if this is a call to refreshPlugin.
	//Otherwise try to allow for the fact that people might have bad/old slot names/instance ids
	$exactMatch = $methodCall == 'refreshPlugin';
	
	
	if ($instanceId || $slotName) {
	
		$overrideSettings = false;
		if (!empty($_REQUEST['overrideSettings'])
		 && (ze\priv::check('_PRIV_CREATE_REVISION_DRAFT') || ze\priv::check('_PRIV_EDIT_DRAFT'))) {
			$overrideSettings = json_decode($_REQUEST['overrideSettings'], true);
		}
		$overrideFrameworkAndCSS = false;
		if (!empty($_REQUEST['overrideFrameworkAndCSS'])
		 && (ze\priv::check('_PRIV_CREATE_REVISION_DRAFT') || ze\priv::check('_PRIV_EDIT_DRAFT'))) {
			$overrideFrameworkAndCSS = json_decode($_REQUEST['overrideFrameworkAndCSS'], true);
		}
		
		ze\plugin::slotContents(
			ze::$slotContents,
			ze::$cID, ze::$cType, ze::$cVersion,
			ze::$layoutId, ze::$templateFamily, ze::$templateFileBaseName,
			$instanceId, $slotName, $ajaxReload = true, $runPlugins = true, $exactMatch, $overrideSettings, $overrideFrameworkAndCSS);
		
		foreach (ze::$slotContents as $s => &$instance) {
			$slotName = $s;
			$moduleClassName = $instance['class_name'] ?? false;
			$instanceId = $instance['instance_id'] ?? false;
			$instanceFound = true;
			break;
		}
	}
	
	if (!$instanceFound) {
		if (!ze\priv::check() || !$slotName) {
			exit;
		} else {
			ze\plugin::setupNewBaseClass($slotName);
			$instanceId = 0;
		}
	
	} elseif (empty(ze::$slotContents[$slotName]['class'])) {
		exit;
	}
	

} elseif ($methodCall == 'handleAJAX'
		|| $methodCall == 'handleOrganizerPanelAJAX'
		|| $methodCall == 'handleAdminToolbarAJAX'
		|| $methodCall == 'showFile'
		|| $methodCall == 'showImage'
		|| $methodCall == 'fillVisitorTUIX'
		|| $methodCall == 'formatVisitorTUIX'
		|| $methodCall == 'validateVisitorTUIX'
		|| $methodCall == 'saveVisitorTUIX'
		|| $methodCall == 'typeaheadSearchAJAX'
		|| $methodCall == 'showStandalonePage') {
	
	//Allow handleAJAX, showFile, showImage and showStandalonePage to be for visitors or admins as needed
	if (!empty($_SESSION['admin_logged_in']) || $methodCall == 'handleOrganizerPanelAJAX' || $methodCall == 'handleAdminToolbarAJAX') {
		require 'adminheader.inc.php';
	} else {
		require 'visitorheader.inc.php';
	}
	
	//Use compression where possible, except in a couple of cases where we're using ob_start()
	if ($methodCall != 'handleOrganizerPanelAJAX' && $methodCall != 'handleAdminToolbarAJAX') {
		ze\cache::start();
	}
	
	if ($_REQUEST['__pluginClassName__'] ?? false) {
		if (!($module = ze\module::activate($moduleClassName = $_REQUEST['__pluginClassName__'] ?? false))) {
			exit;
		}
	} elseif ($_REQUEST['moduleClassName'] ?? false) {
		if (!($module = ze\module::activate($moduleClassName = $_REQUEST['moduleClassName'] ?? false))) {
			exit;
		}
	} elseif ($_REQUEST['__pluginName__'] ?? false) {
		if (!($module = ze\module::activate($moduleClassName = $_REQUEST['__pluginName__'] ?? false))) {
			exit;
		}
	} else {
		if (!($module = ze\module::activate($moduleClassName = $_REQUEST['moduleName'] ?? false))) {
			exit;
		}
	}
	
	//Look variables such as userId, locationId, etc., in the request
	require ze::editionInclude('checkRequestVars');
	

} elseif (($_GET['method_call'] ?? false) == 'loadPhrase') {
	
	//Look up one or more visitor phrases
	require 'visitorheader.inc.php';
	
	$codes = [];
	if (isset($_GET['__code__'])) {
		$codes = ze\ray::explodeDecodeAndTrim($_GET['__code__']);
	}
	$languageId = ($_GET['langId'] ?? false) ?: (($_SESSION['user_lang'] ?? false) ?: ze::$defaultLang);
	
	$sql = "
		SELECT code, local_text
		FROM ". DB_PREFIX. "visitor_phrases
		WHERE language_id = '". ze\escape::sql($languageId). "'
		  AND module_class_name = '". ze\escape::sql($_GET['__class__'] ?? false). "'";
	
	if (!empty($codes)) {
		$sql .= "
		  AND code IN (". ze\escape::in($codes). ")";
	}
	
	$phrases = [];
	$result = ze\sql::select($sql);
	while ($row = ze\sql::fetchAssoc($result)) {
		$isCode = substr($row['code'], 0, 1) == '_';
		$needsTranslating = $isCode || !empty(ze::$langs[$languageId]['translate_phrases']);
		
		if ($needsTranslating) {
			$phrases[$row['code']] = $row['local_text'];
		} else {
			$phrases[$row['code']] = $row['code'];
		}
	}
	
	//If this is a logged in administrator, log any missing phrases
	if (!empty($codes) && ze\priv::check()) {
		foreach ($codes as $code) {
			if (!isset($phrases[$code])) {
				$phrases[$code] = ze\lang::phrase($code, [], ($_GET['__class__'] ?? false), $languageId, ze\admin::phrase('JavaScript code'));
			}
		}
	}
	
	header('Content-Type: text/javascript; charset=UTF-8');
	ze\ray::jsonDump($phrases);
	exit;
	

} elseif ($methodCall == 'getNewId') {
	//Get at the id of the most recently created/uploaded item to Organizer
	if (isset($_SESSION['sk_new_ids']) && is_array($_SESSION['sk_new_ids'])) {
		echo json_encode($_SESSION['sk_new_ids']);
		unset($_SESSION['sk_new_ids']);
	} else {
		echo json_encode([]);
	}
	exit;
	

} elseif ($methodCall == 'getNewEditorTempFiles') {
	//Get at the details of the most recently created/uploaded image to Organizer
	require 'visitorheader.inc.php';
	$files = [];
	if (isset($_SESSION['sk_new_ids']) && is_array($_SESSION['sk_new_ids'])) {
		foreach ($_SESSION['sk_new_ids'] as $id => $dummy) {
			if ($file = ze\row::get('files', ['id', 'checksum', 'filename', 'width', 'height'], ['id' => $id, 'usage' => 'image'])) {
				$files[$id] = $file;
			}
		}
		unset($_SESSION['sk_new_ids']);
	}
	echo json_encode($files);
	exit;
	

} elseif ($methodCall == 'handleAdminBoxAJAX') {
	require 'visitorheader.inc.php';
	
	if (empty($_SESSION['running_a_wizard']) && !ze\priv::check()) {
		exit;
	}
	
	ze\fileAdm::handleAdminBoxAJAX();

} elseif ($methodCall == 'handleWizardAJAX') {
	
	if (empty($_SESSION['running_a_wizard'])) {
		exit;
	}
	
	require 'visitorheader.inc.php';
	ze\fileAdm::handleAdminBoxAJAX();

//Handle any other Admin Mode methods
} else {
	
	//Check to see if the CMS' library of functions has been included, and include it if not
	if (!function_exists('checkPriv')) {
		require 'adminheader.inc.php';
		ze\cache::start();
	}
	
	//Otherwise look for a Module Name
	if ($_REQUEST['moduleClassName'] ?? false) {
		if (!($module = ze\module::activate($_REQUEST['moduleClassName'] ?? false))) {
			exit;
		}
	} elseif ($_REQUEST['__pluginName__'] ?? false) {
		if (!($module = ze\module::activate($_REQUEST['__pluginName__'] ?? false))) {
			exit;
		}
	} else {
		if (!($module = ze\module::activate($_REQUEST['moduleName'] ?? false))) {
			exit;
		}
	}
}


//Check which method call is being requested and then launch that method

//Output a file
if ($methodCall == 'showFile') {
	
	if ($isForPlugin) {
		$module = &ze::$slotContents[$slotName]['class'];
	}
	$module->showFile();
	

} elseif ($methodCall == 'fillVisitorTUIX'
	   || $methodCall == 'formatVisitorTUIX'
	   || $methodCall == 'validateVisitorTUIX'
	   || $methodCall == 'saveVisitorTUIX'
	   || $methodCall == 'typeaheadSearchAJAX') {
	
	if ($isForPlugin) {
		$module = &ze::$slotContents[$slotName]['class'];
	}
	
	$requestedPath = $_REQUEST['path'] ?? false;
	
	
	//Exit if no path is specified
	if (!$requestedPath) {
		echo 'No path specified!';
		exit;

	//Check to see if this path is allowed.
	} else if (!$module->returnVisitorTUIXEnabled($requestedPath)) {
		echo 'You do not have access to this plugin in this mode, or the plugin settings are incomplete.';
		exit;
	}
	
	
	header('Content-Type: text/javascript; charset=UTF-8');
	$tags = [];
	
	if ($methodCall == 'typeaheadSearchAJAX') {
		$module->typeaheadSearchAJAX($requestedPath, $_REQUEST['_tab'] ?? '', $_REQUEST['_field'] ?? '', $_REQUEST['_search'] ?? '', $tags);
		
	} else {
	
		//Small hack for phrases:
		//Try to note down the first YAML file that was used, and use this to report the path that the phrase was found in
		//(Note that this may be wrong if more than one YAML file is used, but usually we only use one YAML file for each path)
		if (!empty($moduleFilesLoaded[$moduleClassName]['paths'])) {
			foreach ($moduleFilesLoaded[$moduleClassName]['paths'] as $yamlFilePath) {
				ze\tuix::$yamlFilePath = $yamlFilePath;
				break;
			}
		}
	
	
		$filling = $methodCall == 'fillVisitorTUIX' || empty($_POST['_tuix']);
		$saving = !$filling && $methodCall == 'saveVisitorTUIX';
		$validating = !$filling && ($saving || $methodCall == 'validateVisitorTUIX');
	
		$debugMode = ze::isAdmin() && ze::get('_debug');
	
		ze\tuix::visitorTUIX($module, $requestedPath, $tags, $filling, $validating, $saving, $debugMode);
	}
	
	ze\ray::jsonDump($tags);
	
	
//Show an image
} elseif ($methodCall == 'showImage') {
	
	$module->showImage();
	

//Show a RSS feed
} elseif ($methodCall == 'showRSS') {
	
	header('Content-Type: application/xml; charset=UTF-8');
	ze::$slotContents[$slotName]['class']->showRSS();


//Show a standalone page
} elseif ($methodCall == 'showStandalonePage') {
	
	$module->showStandalonePage();
	

//Show a thickbox
} elseif ($methodCall == 'showFloatingBox') {
	
	ze::$slotContents[$slotName]['class']->show(false, 'showFloatingBox');
	

//Handle an AJAX ze::request (Plugin)
} elseif ($methodCall == 'handlePluginAJAX' || $methodCall == 'pluginAJAX') {
	
	//Handle the old name if it's not been changed yet
	if (method_exists(ze::$slotContents[$slotName]['class'], 'pluginAJAX')) {
		ze::$slotContents[$slotName]['class']->pluginAJAX();
	}
	
	ze::$slotContents[$slotName]['class']->handlePluginAJAX();


//Handle an AJAX ze::request (Module)
} elseif ($methodCall == 'handleAJAX') {
	
	$module->handleAJAX();


//Handle a file download from Organizer
} elseif ($methodCall == 'handleOrganizerPanelAJAX' && !empty($_POST['_download'])) {
	
	//Handle the old name if it's not been changed yet
	if (method_exists($module, 'storekeeperDownload')) {
		$module->storekeeperDownload($_REQUEST['__path__'] ?? false, ($_REQUEST['id'] ?? false), ($_REQUEST['refinerName'] ?? false), ($_REQUEST['refinerId'] ?? false));
	}
	
	$module->organizerPanelDownload($_REQUEST['__path__'] ?? false, ($_REQUEST['id'] ?? false), ($_REQUEST['refinerName'] ?? false), ($_REQUEST['refinerId'] ?? false));
	
	exit;


//Handle an AJAX request from Organizer
} elseif ($methodCall == 'handleOrganizerPanelAJAX' || $methodCall == 'handleAdminToolbarAJAX') {
	
	$newIds = false;
	$message = false;

	if ($_REQUEST['_sk_form_submission'] ?? false) {
		ob_start();
	} else {
		ze\cache::start();
	}
	
	$newIds = false;
	if ($methodCall == 'handleAdminToolbarAJAX') {
		
		//Handle the old name if it's not been changed yet
		if (method_exists($module, 'adminToolbarAJAX')) {
			$module->adminToolbarAJAX((int) ($_REQUEST['cID'] ?? false), ($_REQUEST['cType'] ?? false), (int) ($_REQUEST['cVersion'] ?? false), ($_REQUEST['id'] ?? false));
		}
		
		$module->handleAdminToolbarAJAX((int) ($_REQUEST['cID'] ?? false), ($_REQUEST['cType'] ?? false), (int) ($_REQUEST['cVersion'] ?? false), ($_REQUEST['id'] ?? false));
	
	} else {
		//Handle the old name if it's not been changed yet
		if (method_exists($module, 'storekeeperAJAX')) {
			$module->storekeeperAJAX($_REQUEST['__path__'] ?? false, ($_REQUEST['id'] ?? false), ($_REQUEST['id2'] ?? false), ($_REQUEST['refinerName'] ?? false), ($_REQUEST['refinerId'] ?? false));
		}
		
		$newIds = $module->handleOrganizerPanelAJAX($_REQUEST['__path__'] ?? false, ($_REQUEST['id'] ?? false), ($_REQUEST['id2'] ?? false), ($_REQUEST['refinerName'] ?? false), ($_REQUEST['refinerId'] ?? false));
	}
	
	if ($newIds && !is_array($newIds)) {
		$newIds = explode(',', $newIds);
	}
	
	if ($_REQUEST['_sk_form_submission'] ?? false) {
		$message = trim(ob_get_contents());
		ob_end_clean();
		
		//If the admin's browser does not have the Flash Uploader, strip out it's success signal
		if (!empty($_FILES['Filedata']) && $message == '1') {
			$message = '';
		}
		
		//Send the results to Organizer in the parent frame
		ze\cache::start();
		echo '
			<html>
				<body>
					<script type="text/javascript">';
		
		
		if ($message) {
			$messageType = 'error';
			
			if		  (substr($message, 0, 24) == '<!--Message_Type:None-->') {
				$message = substr($message, 24);
				$messageType = false;
			} else if (substr($message, 0, 25) == '<!--Message_Type:Error-->') {
				$message = substr($message, 25);
				$messageType = 'error';
			} else if (substr($message, 0, 27) == '<!--Message_Type:Success-->') {
				$message = substr($message, 27);
				$messageType = 'success';
			} else if (substr($message, 0, 27) == '<!--Message_Type:Warning-->') {
				$message = substr($message, 27);
				$messageType = 'warning';
			} else if (substr($message, 0, 28) == '<!--Message_Type:Question-->') {
				$message = substr($message, 28);
				$messageType = 'question';
				
				//Undocumented trick to refresh Organizer!
			} else if (substr($message, 0, 23) == '<!--Reload_Organizer-->' || substr($message, 0, 25) == '<!--Reload_Storekeeper-->') {
					echo '
									self.parent.zenarioO.reloadPage();
								</script>
							</body>
						</html>';
					exit;
			}
		
			echo '
						self.parent.zenarioA.floatingBox(\''. ze\escape::jsOnClick($message). '\', true, \''. $messageType. '\');';
		}
		
		if ($newIds) {
			echo '
						self.parent.zenarioO.deselectAllItems();';
			
			foreach ($newIds as $id) {
				echo '
						self.parent.zenarioO.selectedItems[\'', ze\escape::js($id), '\'] = true;';
			}
			
			echo '
						self.parent.zenarioO.saveSelection();';
		}
		
		echo '
						self.parent.zenarioO.reload();
					</script>
				</body>
			</html>';
	
	} else {
		if ($newIds) {
			if (!is_array($_SESSION['sk_new_ids'] ?? false)) {
				$_SESSION['sk_new_ids'] = [];
			}
			foreach ($newIds as $id) {
				$_SESSION['sk_new_ids'][$id] = true;
			}
		}
	}


//Refresh a Plugin in a slot
} elseif ($methodCall == 'refreshPlugin') {
			
	$module = &ze::$slotContents[$slotName]['class'];
	
	//Display an info section at the top of the result, to help the CMS pick up on a few things
	$showInfo = true;
	
	if ($url = $module->checkHeaderRedirectLocation()) {
		if (!ze\priv::check()) {
			$showInfo = false;
		}
		echo '<!--FORCE_PAGE_RELOAD:', ze\escape::hyp($url), '-->';
	
	} elseif ($module->checkForcePageReloadVar()) {
		if (!ze\priv::check()) {
			$showInfo = false;
		}
		echo '<!--FORCE_PAGE_RELOAD:', ze\escape::hyp(ze\link::toItem(ze::$cID, ze::$cType, true, '', ze::$alias, true)), '-->';
	
	}
	
	if ($showInfo) {
		echo
			'<!--PAGE_TITLE:',
				ze\escape::hyp(ze::$pageTitle),
			'-->';
		
		echo
			'<!--INSTANCE_ID:',
				(int) ze\ray::value(ze::$slotContents[$slotName], 'instance_id'),
			'-->';
		
		if ($module->checkScrollToTopVar() === true) {
			echo
				'<!--SCROLL_TO_TOP-->';
		}
		
		//Lets a Plugin will be placed in a floating box when it reloads
		if (($showInFloatingBox = $module->checkShowInFloatingBoxVar()) === true) {
			echo
				'<!--SHOW_IN_FLOATING_BOX-->';
			if (($params = $module->getFloatingBoxParams()) && is_array($params)) {
				echo
					'<!--FLOATING_BOX_PARAMS:' . ze\escape::hyp(json_encode($params)) . '-->';
			}
		}
		
		//Display the level this Module is at
		$slotLevel = ze\ray::value(ze::$slotContents[$slotName], 'level');
		echo
			'<!--LEVEL:',
				ze\escape::hyp($slotLevel),
			'-->';
		
		if ($slideId = (int) $module->zAPIGetTabId()) {
			echo
				'<!--TAB_ID:', $slideId, '-->';
		}
		
		$cssClass = $module->zAPIGetCSSClass();
			
		if (ze\priv::check()) {
			$slotContents = [$slotName => &ze::$slotContents[$slotName]];
			ze\pluginAdm::setupSlotControls($slotContents, true);
			
			$moduleId = ze\ray::value(ze::$slotContents[$slotName], 'module_id');
			echo
				'<!--MODULE_ID:',
					ze\escape::hyp($moduleId),
				'-->';
			
			echo
				'<!--NAMESPACE:',
					ze\escape::hyp(ze\ray::value(ze::$slotContents[$slotName], 'class_name')),
				'-->';
			
			if (!empty(ze::$slotContents[$slotName]['instance_id'])) {
				if (!empty(ze::$slotContents[$slotName]['content_id'])) {
					echo
						'<!--WIREFRAME-->';
				}
			}
		
			if ($module->beingEdited()) {
				echo '<!--IN_EDIT_MODE-->';
			}
		
			if ($slotLevel == 2
			 && ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT')
			 && $module->shouldShowLayoutPreview()
			 && $moduleId) {
				
				ob_start();
					$module->showLayoutPreview();
				$layoutPreview = ob_get_clean();
				$cssClass .= ' zenario_slot_with_layout_preview';
				
				echo
					'<!--LAYOUT_PREVIEW:',
						ze\escape::hyp($layoutPreview),
					'-->';
			}
		}
		
		echo
			'<!--CSS_CLASS:',
				ze\escape::hyp($cssClass),
			'-->';
		
		if (empty(ze::$slotContents[$slotName]['instance_id'])) {
			ze\plugin::showError($slotName);
		} else {
			$module->showSlot();
		}
		
		
		//Check if the Plugin wants any JavaScript run
		$scriptTypes = [];
		$module->zAPICheckRequestedScripts($scriptTypes);
		
		$i = 0;
		$j = 0;
		foreach ($scriptTypes as $scriptType => &$scripts) {
			foreach ($scripts as &$script) {
				if ($scriptType === 0) {
					echo '<!--SCRIPT_BEFORE'. ++$j. ':';
				} else {
					echo '<!--SCRIPT'. ++$i. ':';
				}
				echo ze\escape::hyp(json_encode($script)), '-->';
			}
		}
		
		if (!empty(ze::$jsLibs)) {
			$i = 0;
			foreach (ze::$jsLibs as $lib => $stylesheet) {
				echo
					'<!--JS_LIB'. ++$i. ':',
						ze\escape::hyp(json_encode([$lib, $stylesheet])),
					'-->';
			}
		}
	}
}



//Run post-display actions
require ze::editionInclude('ajax.post_display');