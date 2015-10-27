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

//Allow a plugin to show on a page on its own.


require 'basicheader.inc.php';
startSession();


//Run pre-load actions
foreach (cms_core::$editions as $className => $dirName) {
	if ($action = moduleDir($dirName, 'actions/ajax.pre_load.php', true)) {
		require $action;
	}
}

$type = false;
$path = false;
$methodCall = request('method_call');
if ($methodCall == 'handleOrganizerPanelAJAX') {
	cms_core::$skType = $type = 'organizer';
	cms_core::$skPath = $path = request('__path__');
}


//Check which method call is being requested
//Some method calls are associated with instances and content items, and some are not
if ($methodCall == 'refreshPlugin'
 || $methodCall == 'showFloatingBox'
 || $methodCall == 'handlePluginAJAX'
 || $methodCall == 'pluginAJAX'
 || $methodCall == 'showRSS'
 || $methodCall == 'showSlot'
 || ($methodCall == 'showFile' && request('cID') && request('cType') && request('instanceId'))) {

	//showRSS and showFloatingBox method calls relate to content items
	require CMS_ROOT. 'zenario/visitorheader.inc.php';
	zenarioInitialiseTwig();
	useGZIP(setting('compress_web_pages'));
	
	//If an admin is logged in, include admin functions for refreshing modules
	if (checkPriv()) {
		require_once CMS_ROOT. 'zenario/includes/admin.inc.php';
	}
	
	//Check the content item that this is being linked from, and whether the current user has permissions to access it
	$cID = $cType = $content = $version = $redirectNeeded = $aliasInURL = $instanceFound = false;
	resolveContentItemFromRequest($cID, $cType, $redirectNeeded, $aliasInURL);
	
	if (!$cVersion = request('cVersion')) {
		$cVersion = getAppropriateVersion($cID, $cType);
	}
	
	if (!cms_core::$cVersion = getShowableContent($content, $version, $cID, $cType, request('cVersion'))) {
		exit;
	}
	
	setShowableContent($content, $version);
	
	
	//If this is a call to an RSS feed, and the slotname was not specified, try to see if this Content Item has a slot registered
	if ($methodCall == 'showRSS'
	 && empty($_REQUEST['slotName'])
	 && empty($_REQUEST['instanceId'])
	 && cms_core::$rss
	 && ($rss = explode('_', cms_core::$rss, 2))
	 && (!empty($rss[1]))) {
		$_REQUEST['slotName'] = $_GET['slotName'] = $rss[1];
		$_REQUEST['eggId'] = $_GET['eggId'] = $rss[0];
	}
	
	$instanceId = request('instanceId');
	$slotName = HTMLId(request('slotName'));
	
	//Use exact matching if this is a call to refreshPlugin.
	//Otherwise try to allow for the fact that people might have bad/old slot names/instance ids
	$exactMatch = $methodCall == 'refreshPlugin';
	
	
	if ($instanceId || $slotName) {
		getSlotContents(
			cms_core::$slotContents,
			cms_core::$cID, cms_core::$cType, cms_core::$cVersion,
			cms_core::$layoutId, cms_core::$templateFamily, cms_core::$templateFileBaseName,
			$instanceId, $slotName, $ajaxReload = true, $runPlugins = true, $exactMatch);
		
		foreach (cms_core::$slotContents as $s => &$instance) {
			$slotName = $s;
			$instanceId = cms_core::$slotContents[$slotName]['instance_id'];
			$instanceFound = true;
			break;
		}
	}
	
	if (!$instanceFound) {
		if (!checkPriv() || !$slotName) {
			exit;
		} else {
			setupNewBaseClassPlugin($slotName);
			$instanceId = 0;
		}
	
	} elseif (empty(cms_core::$slotContents[$slotName]['class'])) {
		exit;
	}
	

} elseif ($methodCall == 'handleAJAX'
		|| $methodCall == 'handleOrganizerPanelAJAX'
		|| $methodCall == 'handleAdminToolbarAJAX'
		|| $methodCall == 'showFile'
		|| $methodCall == 'showImage'
		|| $methodCall == 'showStandalonePage') {
	
	//Allow handleAJAX, showFile, showImage and showStandalonePage to be for visitors or admins as needed
	if (session('admin_logged_in') || $methodCall == 'handleOrganizerPanelAJAX' || $methodCall == 'handleAdminToolbarAJAX') {
		require 'adminheader.inc.php';
	} else {
		require 'visitorheader.inc.php';
	}
	
	//Use compression where possible, except in a couple of cases where we're using ob_start()
	if ($methodCall != 'handleOrganizerPanelAJAX' && $methodCall != 'handleAdminToolbarAJAX') {
		useGZIP(setting('compress_web_pages'));
	}
	
	if (request('__pluginClassName__')) {
		if (!($module = activateModule(request('__pluginClassName__')))) {
			exit;
		}
	} elseif (request('moduleClassName')) {
		if (!($module = activateModule(request('moduleClassName')))) {
			exit;
		}
	} elseif (request('__pluginName__')) {
		if (!($module = activateModule(request('__pluginName__')))) {
			exit;
		}
	} else {
		if (!($module = activateModule(request('moduleName')))) {
			exit;
		}
	}
	

} elseif (get('method_call') == 'loadPhrase') {
	
	//Look up one or more visitor phrases
	require 'visitorheader.inc.php';
	
	$codes = array();
	if (isset($_GET['__code__'])) {
		$codes = explodeDecodeAndTrim($_GET['__code__']);
	}
	$languageId = ifNull(get('langId'), session('user_lang'), setting('default_language'));
	
	$sql = "
		SELECT code, local_text
		FROM ". DB_NAME_PREFIX. "visitor_phrases
		WHERE language_id = '". sqlEscape($languageId). "'
		  AND module_class_name = '". sqlEscape(get('__class__')). "'";
	
	if (!empty($codes)) {
		$sql .= "
		  AND code IN (". inEscape($codes). ")";
	}
	
	$phrases = array();
	$result = sqlQuery($sql);
	while ($row = sqlFetchAssoc($result)) {
		$isCode = substr($row['code'], 0, 1) == '_';
		$needsTranslating = $isCode || !empty(cms_core::$langs[$languageId]['translate_phrases']);
		
		if ($needsTranslating) {
			$phrases[$row['code']] = $row['local_text'];
		} else {
			$phrases[$row['code']] = $row['code'];
		}
	}
	
	//If this is a logged in administrator, log any missing phrases
	if (!empty($codes) && checkPriv()) {
		foreach ($codes as $code) {
			if (!isset($phrases[$code])) {
				$phrases[$code] = phrase($code, array(), get('__class__'), $languageId, adminPhrase('JavaScript code'));
			}
		}
	}
	
	header('Content-Type: text/javascript; charset=UTF-8');
	jsonEncodeForceObject($phrases);
	exit;
	

} elseif ($methodCall == 'getNewId') {
	//Get at the id of the most recently created/uploaded item to Organizer
	if (isset($_SESSION['sk_new_ids']) && is_array($_SESSION['sk_new_ids'])) {
		echo json_encode($_SESSION['sk_new_ids']);
		unset($_SESSION['sk_new_ids']);
	} else {
		echo json_encode(false);
	}
	exit;
	

} elseif ($methodCall == 'getNewEditorTempFiles') {
	//Get at the details of the most recently created/uploaded image to Organizer
	require 'visitorheader.inc.php';
	$files = array();
	if (isset($_SESSION['sk_new_ids']) && is_array($_SESSION['sk_new_ids'])) {
		foreach ($_SESSION['sk_new_ids'] as $id => $dummy) {
			if ($file = getRow('files', array('id', 'checksum', 'filename', 'width', 'height'), array('id' => $id, 'usage' => 'image'))) {
				$files[$id] = $file;
			}
		}
		unset($_SESSION['sk_new_ids']);
	}
	echo json_encode($files);
	exit;
	

} elseif ($methodCall == 'handleAdminBoxAJAX') {
	require 'visitorheader.inc.php';
	
	if (empty($_SESSION['running_a_wizard']) && !checkPriv()) {
		exit;
	}
	
	require CMS_ROOT. 'zenario/includes/admin.inc.php';
	handleAdminBoxAJAX();

} elseif ($methodCall == 'handleWizardAJAX') {
	
	if (empty($_SESSION['running_a_wizard'])) {
		exit;
	}
	
	require 'visitorheader.inc.php';
	require CMS_ROOT. 'zenario/includes/admin.inc.php';
	handleAdminBoxAJAX();

//Handle any other Admin Mode methods
} else {
	
	//Check to see if the CMS' library of functions has been included, and include it if not
	if (!function_exists('checkPriv')) {
		require 'adminheader.inc.php';
		useGZIP(setting('compress_web_pages'));
	}
	
	//Otherwise look for a Module Name
	if (request('moduleClassName')) {
		if (!($module = activateModule(request('moduleClassName')))) {
			exit;
		}
	} elseif (request('__pluginName__')) {
		if (!($module = activateModule(request('__pluginName__')))) {
			exit;
		}
	} else {
		if (!($module = activateModule(request('moduleName')))) {
			exit;
		}
	}
}


//If the "Show menu structure in friendly URLs" site setting is enabled,
//always use the full URL when generating links in an AJAX request, just in case the results
//are being displayed with a different relative path
if (setting('mod_rewrite_slashes')) {
	cms_core::$mustUseFullPath = true;
}


//Check which method call is being requested and then launch that method

//Output a file
if ($methodCall == 'showFile') {
	
	if (request('cID') && request('cType') && request('instanceId')) {
		cms_core::$slotContents[$slotName]['class']->showFile();
	} else {
		$module->showFile();
	}
	
	
//Show an image
} elseif ($methodCall == 'showImage') {
	
	$module->showImage();
	

//Show a RSS feed
} elseif ($methodCall == 'showRSS') {
	
	header('Content-Type: application/xml; charset=UTF-8');
	cms_core::$slotContents[$slotName]['class']->showRSS();


//Show a standalone page
} elseif ($methodCall == 'showStandalonePage') {
	
	$module->showStandalonePage();
	

//Show a thickbox
} elseif ($methodCall == 'showFloatingBox') {
	
	cms_core::$slotContents[$slotName]['class']->show(false, 'showFloatingBox');
	

//Handle an AJAX request (Plugin)
} elseif ($methodCall == 'handlePluginAJAX' || $methodCall == 'pluginAJAX') {
	
	//Handle the old name if it's not been changed yet
	if (method_exists(cms_core::$slotContents[$slotName]['class'], 'pluginAJAX')) {
		cms_core::$slotContents[$slotName]['class']->pluginAJAX();
	}
	
	cms_core::$slotContents[$slotName]['class']->handlePluginAJAX();


//Handle an AJAX request (Module)
} elseif ($methodCall == 'handleAJAX') {
	
	$module->handleAJAX();


//Handle a file download from Organizer
} elseif ($methodCall == 'handleOrganizerPanelAJAX' && post('_download')) {
	
	//Handle the old name if it's not been changed yet
	if (method_exists($module, 'storekeeperDownload')) {
		$module->storekeeperDownload(request('__path__'), request('id'), request('refinerName'), request('refinerId'));
	}
	
	$module->organizerPanelDownload(request('__path__'), request('id'), request('refinerName'), request('refinerId'));
	
	exit;


//Handle an AJAX request from Organizer
} elseif ($methodCall == 'handleOrganizerPanelAJAX' || $methodCall == 'handleAdminToolbarAJAX') {
	
	$newIds = false;
	$message = false;

	if (request('_sk_form_submission')) {
		ob_start();
	} else {
		useGZIP(setting('compress_web_pages'));
	}
	
	$newIds = false;
	if ($methodCall == 'handleAdminToolbarAJAX') {
		
		//Handle the old name if it's not been changed yet
		if (method_exists($module, 'adminToolbarAJAX')) {
			$module->adminToolbarAJAX((int) request('cID'), request('cType'), (int) request('cVersion'), request('id'));
		}
		
		$module->handleAdminToolbarAJAX((int) request('cID'), request('cType'), (int) request('cVersion'), request('id'));
	
	} else {
		//Handle the old name if it's not been changed yet
		if (method_exists($module, 'storekeeperAJAX')) {
			$module->storekeeperAJAX(request('__path__'), request('id'), request('id2'), request('refinerName'), request('refinerId'));
		}
		
		$newIds = $module->handleOrganizerPanelAJAX(request('__path__'), request('id'), request('id2'), request('refinerName'), request('refinerId'));
	}
	
	if ($newIds && !is_array($newIds)) {
		$newIds = explode(',', $newIds);
	}
	
	if (request('_sk_form_submission')) {
		$message = trim(ob_get_contents());
		ob_end_clean();
		
		//If the admin's browser does not have the Flash Uploader, strip out it's success signal
		if (!empty($_FILES['Filedata']) && $message == '1') {
			$message = '';
		}
		
		//Send the results to Organizer in the parent frame
		useGZIP(setting('compress_web_pages'));
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
			} else if (substr($message, 0, 25) == '<!--Reload_Storekeeper-->') {
					echo '
									self.parent.zenarioO.reloadPage();
								</script>
							</body>
						</html>';
					exit;
			}
		
			echo '
						self.parent.zenarioA.floatingBox(\''. jsOnClickEscape($message). '\', true, \''. $messageType. '\');';
		}
		
		if ($newIds) {
			echo '
						self.parent.zenarioO.deselectAllItems();';
			
			foreach ($newIds as $id) {
				echo '
						self.parent.zenarioO.selectedItems[\'', jsEscape($id), '\'] = true;';
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
			if (!is_array(session('sk_new_ids'))) {
				$_SESSION['sk_new_ids'] = array();
			}
			foreach ($newIds as $id) {
				$_SESSION['sk_new_ids'][$id] = true;
			}
		}
	}


//Refresh a Plugin in a slot
} elseif ($methodCall == 'refreshPlugin') {
			
	function eschyp($text) {
		$searches = array('`', '-', "\n", "\r");
		$replaces = array('`t', '`h', '`n', '`r');
		return str_replace($searches, $replaces, $text);
	}
	
	//Display an info section at the top of the result, to help the CMS pick up on a few things
	$showInfo = true;
	echo '<!--INFO-->';
	
	if ($url = cms_core::$slotContents[$slotName]['class']->checkHeaderRedirectLocation()) {
		if (!checkPriv()) {
			$showInfo = false;
		}
		echo '<!--FORCE_PAGE_RELOAD--', eschyp($url), '-->';
	
	} elseif (cms_core::$slotContents[$slotName]['class']->checkForcePageReloadVar()) {
		if (!checkPriv()) {
			$showInfo = false;
		}
		echo '<!--FORCE_PAGE_RELOAD--', eschyp(linkToItem(cms_core::$cID, cms_core::$cType, true, '', cms_core::$alias, true)), '-->';
	
	}
	
	if ($showInfo) {
		echo
			'<!--INSTANCE_ID--',
				eschyp(arrayKey(cms_core::$slotContents[$slotName], 'instance_id')),
			'-->';
		
		if (cms_core::$slotContents[$slotName]['class']->checkScrollToTopVar() === true) {
			echo
				'<!--SCROLL_TO_TOP-->';
		}
		
		//Lets a Plugin will be placed in a floating box when it reloads
		if (($showInFloatingBox = cms_core::$slotContents[$slotName]['class']->checkShowInFloatingBoxVar()) === true) {
			echo
				'<!--SHOW_IN_FLOATING_BOX-->';
		}
		
		//Display the level this Module is at
		echo
			'<!--LEVEL--',
				eschyp(arrayKey(cms_core::$slotContents[$slotName], 'level')),
			'-->';
		
		if ($tabId = (int) cms_core::$slotContents[$slotName]['class']->tApiGetTabId()) {
			echo
				'<!--TAB_ID--', $tabId, '-->';
		}
		
		
		//Check if the Plugin wants any JavaScript run
		$scripts = array();
		$scriptsBefore = array();
		cms_core::$slotContents[$slotName]['class']->tApiCheckRequestedScripts($scripts, $scriptsBefore);
		
		if (count($scripts)) {
			foreach ($scripts as &$script) {
				echo '<!--SCRIPT--', eschyp(json_encode($script)), '-->';
			}
		}
		
		if (count($scriptsBefore)) {
			foreach ($scriptsBefore as &$script) {
				echo '<!--SCRIPT_BEFORE--', eschyp(json_encode($script)), '-->';
			}
		}

			
		if (checkPriv()) {
			$slotContents = array($slotName => &cms_core::$slotContents[$slotName]);
			setupAdminSlotControls($slotContents, true);
			
			echo
				'<!--MODULE_ID--',
					eschyp(arrayKey(cms_core::$slotContents[$slotName], 'module_id')),
				'-->';
			
			echo
				'<!--NAMESPACE--',
					eschyp(arrayKey(cms_core::$slotContents[$slotName], 'class_name')),
				'-->';
			
			if (!empty(cms_core::$slotContents[$slotName]['instance_id'])) {
				if (!empty(cms_core::$slotContents[$slotName]['content_id'])) {
					echo
						'<!--WIREFRAME-->';
				}
			}
			
			//Replace with
			if (!empty(cms_core::$slotContents[$slotName]['css_class'])) {
				echo
					'<!--CSS_CLASS--',
						eschyp(cms_core::$slotContents[$slotName]['css_class']),
					'-->';
			} else {
				echo
					'<!--CSS_CLASS---->';
			}
		
			if (cms_core::$slotContents[$slotName]['class']->beingEdited()) {
				echo '<!--IN_EDIT_MODE-->';
			}
		}
		
		echo '<!--/INFO-->';
		
		if (empty(cms_core::$slotContents[$slotName]['instance_id'])) {
			showPluginError($slotName);
		} else {
			cms_core::$slotContents[$slotName]['class']->showSlot();
			cms_core::$slotContents[$slotName]['class']->afterShowSlot();
		}
	
	} else {
		echo '<!--/INFO-->';
	}
}



//Run post-display actions
foreach (cms_core::$editions as $className => $dirName) {
	if ($action = moduleDir($dirName, 'actions/ajax.post_display.php', true)) {
		require $action;
	}
}