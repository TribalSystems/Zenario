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

//Allow a plugin to show on a page on its own.


require 'basicheader.inc.php';
startSession();


//Run pre-load actions
require CMS_ROOT. 'zenario/api/cache_functions.inc.php';
require editionInclude('ajax.pre_load');


$type = false;
$path = false;
$methodCall = $_REQUEST['method_call'] ?? false;
if ($methodCall == 'handleOrganizerPanelAJAX') {
	cms_core::$skType = $type = 'organizer';
	cms_core::$skPath = $path = $_REQUEST['__path__'] ?? false;
}


$isForPlugin = ($_REQUEST['cID'] ?? false) && ($_REQUEST['cType'] ?? false) && ($_REQUEST['instanceId'] ?? false);

//Check which method call is being requested
//Some method calls are associated with instances and content items, and some are not
if ($methodCall == 'refreshPlugin'
 || $methodCall == 'showFloatingBox'
 || $methodCall == 'handlePluginAJAX'
 || $methodCall == 'pluginAJAX'
 || $methodCall == 'showRSS'
 || $methodCall == 'showSlot'
 || ($methodCall == 'showFile' && $isForPlugin)
 || ($methodCall == 'fillVisitorTUIX' && $isForPlugin)
 || ($methodCall == 'formatVisitorTUIX' && $isForPlugin)
 || ($methodCall == 'validateVisitorTUIX' && $isForPlugin)
 || ($methodCall == 'saveVisitorTUIX' && $isForPlugin)) {

	//showRSS and showFloatingBox method calls relate to content items
	require CMS_ROOT. 'zenario/visitorheader.inc.php';
	require CMS_ROOT. 'zenario/includes/twig.inc.php';
	require CMS_ROOT. 'zenario/includes/twig.frameworks.inc.php';
	useGZIP();
	
	//If an admin is logged in, include admin functions for refreshing modules
	if (checkPriv()) {
		require_once CMS_ROOT. 'zenario/includes/admin.inc.php';
	}
	
	//Check the content item that this is being linked from, and whether the current user has permissions to access it
	$cID = $cType = $content = $version = $redirectNeeded = $aliasInURL = $instanceFound = false;
	resolveContentItemFromRequest($cID, $cType, $redirectNeeded, $aliasInURL, $_GET, $_REQUEST, $_POST);
	
	if (!$cVersion = $_REQUEST['cVersion'] ?? false) {
		$cVersion = getAppropriateVersion($cID, $cType);
	}
	
	
	$status = getShowableContent($content, $version, $cID, $cType, ($_REQUEST['cVersion'] ?? false), $checkRequestVars = true);
	if (!$status || is_string($status)) {
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
	
	$instanceId = $_REQUEST['instanceId'] ?? false;
	$slotName = HTMLId($_REQUEST['slotName'] ?? false);
	
	//Use exact matching if this is a call to refreshPlugin.
	//Otherwise try to allow for the fact that people might have bad/old slot names/instance ids
	$exactMatch = $methodCall == 'refreshPlugin';
	
	
	if ($instanceId || $slotName) {
	
		$overrideSettings = false;
		if (!empty($_REQUEST['overrideSettings'])
		 && (checkPriv('_PRIV_CREATE_REVISION_DRAFT') || checkPriv('_PRIV_EDIT_DRAFT'))) {
			$overrideSettings = json_decode($_REQUEST['overrideSettings'], true);
		}
		$overrideFrameworkAndCSS = false;
		if (!empty($_REQUEST['overrideFrameworkAndCSS'])
		 && (checkPriv('_PRIV_CREATE_REVISION_DRAFT') || checkPriv('_PRIV_EDIT_DRAFT'))) {
			$overrideFrameworkAndCSS = json_decode($_REQUEST['overrideFrameworkAndCSS'], true);
		}
		
		getSlotContents(
			cms_core::$slotContents,
			cms_core::$cID, cms_core::$cType, cms_core::$cVersion,
			cms_core::$layoutId, cms_core::$templateFamily, cms_core::$templateFileBaseName,
			$instanceId, $slotName, $ajaxReload = true, $runPlugins = true, $exactMatch, $overrideSettings, $overrideFrameworkAndCSS);
		
		foreach (cms_core::$slotContents as $s => &$instance) {
			$slotName = $s;
			$moduleClassName = $instance['class_name'] ?? false;
			$instanceId = $instance['instance_id'] ?? false;
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
		|| $methodCall == 'fillVisitorTUIX'
		|| $methodCall == 'formatVisitorTUIX'
		|| $methodCall == 'validateVisitorTUIX'
		|| $methodCall == 'saveVisitorTUIX'
		|| $methodCall == 'showStandalonePage') {
	
	//Allow handleAJAX, showFile, showImage and showStandalonePage to be for visitors or admins as needed
	if (($_SESSION['admin_logged_in'] ?? false) || $methodCall == 'handleOrganizerPanelAJAX' || $methodCall == 'handleAdminToolbarAJAX') {
		require 'adminheader.inc.php';
	} else {
		require 'visitorheader.inc.php';
	}
	
	//Use compression where possible, except in a couple of cases where we're using ob_start()
	if ($methodCall != 'handleOrganizerPanelAJAX' && $methodCall != 'handleAdminToolbarAJAX') {
		useGZIP();
	}
	
	if ($_REQUEST['__pluginClassName__'] ?? false) {
		if (!($module = activateModule($moduleClassName = $_REQUEST['__pluginClassName__'] ?? false))) {
			exit;
		}
	} elseif ($_REQUEST['moduleClassName'] ?? false) {
		if (!($module = activateModule($moduleClassName = $_REQUEST['moduleClassName'] ?? false))) {
			exit;
		}
	} elseif ($_REQUEST['__pluginName__'] ?? false) {
		if (!($module = activateModule($moduleClassName = $_REQUEST['__pluginName__'] ?? false))) {
			exit;
		}
	} else {
		if (!($module = activateModule($moduleClassName = $_REQUEST['moduleName'] ?? false))) {
			exit;
		}
	}
	
	//Look variables such as userId, locationId, etc., in the request
	require editionInclude('checkRequestVars');
	

} elseif (($_GET['method_call'] ?? false) == 'loadPhrase') {
	
	//Look up one or more visitor phrases
	require 'visitorheader.inc.php';
	
	$codes = array();
	if (isset($_GET['__code__'])) {
		$codes = explodeDecodeAndTrim($_GET['__code__']);
	}
	$languageId = ifNull($_GET['langId'] ?? false, ($_SESSION['user_lang'] ?? false), cms_core::$defaultLang);
	
	$sql = "
		SELECT code, local_text
		FROM ". DB_NAME_PREFIX. "visitor_phrases
		WHERE language_id = '". sqlEscape($languageId). "'
		  AND module_class_name = '". sqlEscape($_GET['__class__'] ?? false). "'";
	
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
				$phrases[$code] = phrase($code, array(), ($_GET['__class__'] ?? false), $languageId, adminPhrase('JavaScript code'));
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
		echo json_encode(array());
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
		useGZIP();
	}
	
	//Otherwise look for a Module Name
	if ($_REQUEST['moduleClassName'] ?? false) {
		if (!($module = activateModule($_REQUEST['moduleClassName'] ?? false))) {
			exit;
		}
	} elseif ($_REQUEST['__pluginName__'] ?? false) {
		if (!($module = activateModule($_REQUEST['__pluginName__'] ?? false))) {
			exit;
		}
	} else {
		if (!($module = activateModule($_REQUEST['moduleName'] ?? false))) {
			exit;
		}
	}
}


//Check which method call is being requested and then launch that method

//Output a file
if ($methodCall == 'showFile') {
	
	if ($isForPlugin) {
		$module = &cms_core::$slotContents[$slotName]['class'];
	}
	$module->showFile();
	

} elseif ($methodCall == 'fillVisitorTUIX'
	   || $methodCall == 'formatVisitorTUIX'
	   || $methodCall == 'validateVisitorTUIX'
	   || $methodCall == 'saveVisitorTUIX') {
	
	if ($isForPlugin) {
		$module = &cms_core::$slotContents[$slotName]['class'];
	}
	
	$filling = $methodCall == 'fillVisitorTUIX' || empty($_POST['_tuix']);
	$callbackFromScriptTags = $filling && !empty($_REQUEST['_script']);
	
	header('Content-Type: text/javascript; charset=UTF-8');
	
	class zenario_ajax_static_vars {
		public static $hadFatalError = true;
		public static $returnGlobalName = true;
		public static function onShutdown() {
			if (zenario_ajax_static_vars::$hadFatalError) {
				$error = ob_get_clean();
				
				echo zenario_ajax_static_vars::$returnGlobalName, '.AJAXErrorHandler({
					responseText: ', json_encode($error), '
				});';
				
				exit;
			}
		}
	}
	zenario_ajax_static_vars::$returnGlobalName = $module->returnGlobalName();
	
	if ($callbackFromScriptTags) {
		ob_start();
		register_shutdown_function(['zenario_ajax_static_vars', 'onShutdown']);
	}
	
	//Trying to do without admin.inc.php
	//require_once CMS_ROOT. 'zenario/includes/admin.inc.php';
	require_once CMS_ROOT. 'zenario/includes/tuix.inc.php';
	
	//Exit if no path is specified
	if (!$requestedPath = $_REQUEST['path'] ?? false) {
		echo 'No path specified!';
		exit;
	}
	$debugMode = checkPriv() && (bool) ($_GET['_debug'] ?? false);
	
	cms_core::$skType = 'visitor';
	cms_core::$skPath = $requestedPath;
	
	//Check to see if this path is allowed.
	if (!$module->returnVisitorTUIXEnabled($requestedPath)) {
		echo 'You do not have access to this plugin in this mode, or the plugin settings are incomplete.';
		exit;
	}
	
	$tags = array();
	$originalTags = array();
	$moduleFilesLoaded = array();
	loadTUIX($moduleFilesLoaded, $tags, 'visitor', $requestedPath);
	
	if (empty($tags[$requestedPath])) {
		echo 'Path not found!';
		exit;
	}
	$tags = $tags[$requestedPath];
	$clientTags = false;
	
	
	//Small hack for phrases:
	//Try to note down the first YAML file that was used, and use this to report the path that the phrase was found in
	//(Note that this may be wrong if more than one YAML file is used, but usually we only use one YAML file for each path)
	if (!empty($moduleFilesLoaded[$moduleClassName]['paths'])) {
		foreach ($moduleFilesLoaded[$moduleClassName]['paths'] as $yamlFilePath) {
			zenario_fea_tuix::$yamlFilePath = $yamlFilePath;
			break;
		}
	}
	
	
	if ($debugMode) {
		$staticTags = $tags;
	}

	//Debug mode - show the TUIX before it's been modified
	if ($debugMode) {
		$modules = array($moduleClassName = $module);
		displayDebugMode($staticTags, $modules, $moduleFilesLoaded, $tagPath = $requestedPath);
		exit;
	}
	
	$doSave = false;
	if (!$filling) {
		$clientTags = json_decode($_POST['_tuix'], true);
	
		loadCopyOfTUIXFromServer($tags, $clientTags);
		
		syncAdminBoxFromClientToServer($tags, $clientTags);
		
		if (!empty($_REQUEST['_useSync'])) {
			$originalTags = $tags;
		}
		
		if (in($methodCall, 'validateVisitorTUIX', 'saveVisitorTUIX')) {
			$fields = array();
			$values = array();
			$changes = array();
			if (TUIXLooksLikeFAB($tags)) {
				readAdminBoxValues($tags, $fields, $values, $changes, $filling, $resetErrors = true, $checkLOVs = true);
				
				foreach ($tags['tabs'] as $tabName => &$tab) {
					applyValidationFromTUIXOnTab($tab);
				}
			}
			
			$saving = $methodCall == 'saveVisitorTUIX';
	
			$module->validateVisitorTUIX($requestedPath, $tags, $fields, $values, $changes, $saving);
			
			
			if ($saving) {
				//Check if there are any errors
				$doSave = true;
				if (TUIXLooksLikeFAB($tags)) {
					foreach ($tags['tabs'] as &$tab) {
						if (!empty($tab['errors'])) {
							$doSave = false;
							break;
						}
					
						if (!empty($tab['fields']) && is_array($tab['fields'])) {
							foreach ($tab['fields'] as &$field) {
								if (!empty($field['error'])) {
									$doSave = false;
									break 2;
								}
							}
						}
					}
				}
				
				if ($doSave) {
					$fields = array();
					$values = array();
					$changes = array();
					if (TUIXLooksLikeFAB($tags)) {
						readAdminBoxValues($tags, $fields, $values, $changes, $filling, $resetErrors = false);
					}
					
					$module->saveVisitorTUIX($requestedPath, $tags, $fields, $values, $changes);
				}
			}
		}
		
	} else {
		
		//Logic for initialising an Admin Box
		if (!empty($tags['key']) && is_array($tags['key'])) {
			foreach ($tags['key'] as $key => &$value) {
				if (!empty($_REQUEST[$key])) {
					$value = $_REQUEST[$key];
				}
			}
		}
		
		$fields = array();
		$values = array();
		$changes = array();
		if (TUIXLooksLikeFAB($tags)) {
			readAdminBoxValues($tags, $fields, $values, $changes, $filling, $resetErrors = false);
		}
	
		$module->fillVisitorTUIX($requestedPath, $tags, $fields, $values);
	}
	
	if (!$doSave) {
		$fields = array();
		$values = array();
		$changes = array();
		if (TUIXLooksLikeFAB($tags)) {
			readAdminBoxValues($tags, $fields, $values, $changes, $filling, $resetErrors = false, $checkLOVs = false, $addOrds = true);
		}

		$module->formatVisitorTUIX($requestedPath, $tags, $fields, $values, $changes);
	}
	
	if (TUIXLooksLikeFAB($tags)) {
		//Try to save a copy of the admin box in the cache directory
		saveCopyOfTUIXOnServer($tags);
		
		if (!empty($originalTags)) {
			$output = array();
			syncAdminBoxFromServerToClient($tags, $originalTags, $output);
	
			$tags = $output;
		}
	}
	

	
	
	if ($callbackFromScriptTags) {
		
		$error = ob_get_clean();
		
		if ($error) {
			echo zenario_ajax_static_vars::$returnGlobalName, '.AJAXErrorHandler({
				data: {},
				responseText: ', json_encode($error), ',
				zenario_continueAnyway: function() {';
		}
		
		$requests = $_GET;
		unset($requests['cID']);
		unset($requests['cType']);
		unset($requests['cVersion']);
		unset($requests['method_call']);
		unset($requests['moduleClassName']);
		unset($requests['instanceId']);
		unset($requests['slotName']);
		unset($requests['eggId']);
		unset($requests['_script']);
		echo zenario_ajax_static_vars::$returnGlobalName, '.loadFromScriptCallback(', json_encode($requests), ', ';
	}
	
	jsonEncodeForceObject($tags);
	
	if ($callbackFromScriptTags) {
		echo ');';
		
		if ($error) {
			echo '}});';
		}
	}
	
	zenario_ajax_static_vars::$hadFatalError = false;
	
	
	
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
		useGZIP();
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
		useGZIP();
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
			if (!is_array($_SESSION['sk_new_ids'] ?? false)) {
				$_SESSION['sk_new_ids'] = array();
			}
			foreach ($newIds as $id) {
				$_SESSION['sk_new_ids'][$id] = true;
			}
		}
	}


//Refresh a Plugin in a slot
} elseif ($methodCall == 'refreshPlugin') {
			
	$module = &cms_core::$slotContents[$slotName]['class'];
	
	//Display an info section at the top of the result, to help the CMS pick up on a few things
	$showInfo = true;
	
	if ($url = $module->checkHeaderRedirectLocation()) {
		if (!checkPriv()) {
			$showInfo = false;
		}
		echo '<!--FORCE_PAGE_RELOAD:', eschyp($url), '-->';
	
	} elseif ($module->checkForcePageReloadVar()) {
		if (!checkPriv()) {
			$showInfo = false;
		}
		echo '<!--FORCE_PAGE_RELOAD:', eschyp(linkToItem(cms_core::$cID, cms_core::$cType, true, '', cms_core::$alias, true)), '-->';
	
	}
	
	if ($showInfo) {
		echo
			'<!--PAGE_TITLE:',
				eschyp(cms_core::$pageTitle),
			'-->';
		
		echo
			'<!--INSTANCE_ID:',
				(int) arrayKey(cms_core::$slotContents[$slotName], 'instance_id'),
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
					'<!--FLOATING_BOX_PARAMS:' . eschyp(json_encode($params)) . '-->';
			}
		}
		
		//Display the level this Module is at
		$slotLevel = arrayKey(cms_core::$slotContents[$slotName], 'level');
		echo
			'<!--LEVEL:',
				eschyp($slotLevel),
			'-->';
		
		if ($slideId = (int) $module->zAPIGetTabId()) {
			echo
				'<!--TAB_ID:', $slideId, '-->';
		}
		
		$cssClass = '';
		if (!empty(cms_core::$slotContents[$slotName]['css_class'])) {
			$cssClass = cms_core::$slotContents[$slotName]['css_class'];
		}
			
		if (checkPriv()) {
			$slotContents = array($slotName => &cms_core::$slotContents[$slotName]);
			setupAdminSlotControls($slotContents, true);
			
			$moduleId = arrayKey(cms_core::$slotContents[$slotName], 'module_id');
			echo
				'<!--MODULE_ID:',
					eschyp($moduleId),
				'-->';
			
			echo
				'<!--NAMESPACE:',
					eschyp(arrayKey(cms_core::$slotContents[$slotName], 'class_name')),
				'-->';
			
			if (!empty(cms_core::$slotContents[$slotName]['instance_id'])) {
				if (!empty(cms_core::$slotContents[$slotName]['content_id'])) {
					echo
						'<!--WIREFRAME-->';
				}
			}
		
			if ($module->beingEdited()) {
				echo '<!--IN_EDIT_MODE-->';
			}
		
			if ($slotLevel == 2
			 && checkPriv('_PRIV_MANAGE_TEMPLATE_SLOT')
			 && $module->shouldShowLayoutPreview()
			 && $moduleId) {
				
				ob_start();
					$module->showLayoutPreview();
				$layoutPreview = ob_get_clean();
				$cssClass .= ' zenario_slot_with_layout_preview';
				
				echo
					'<!--LAYOUT_PREVIEW:',
						eschyp($layoutPreview),
					'-->';
			}
		}
		
		echo
			'<!--CSS_CLASS:',
				eschyp($cssClass),
			'-->';
		
		if (empty(cms_core::$slotContents[$slotName]['instance_id'])) {
			showPluginError($slotName);
		} else {
			$module->showSlot();
			$module->afterShowSlot();
		}
		
		
		//Check if the Plugin wants any JavaScript run
		$scriptTypes = array();
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
				echo eschyp(json_encode($script)), '-->';
			}
		}
		
		if (!empty(cms_core::$jsLibs)) {
			$i = 0;
			foreach (cms_core::$jsLibs as $lib => $libInfo) {
				echo
					'<!--JS_LIB'. ++$i. ':',
						eschyp(json_encode([$lib, $libInfo[0]])),
					'-->';
			}
		}
	}
}



//Run post-display actions
require editionInclude('ajax.post_display');