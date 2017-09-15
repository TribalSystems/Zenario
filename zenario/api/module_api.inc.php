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


define('CMS_FRAMEWORK_CONDITION', 0);
define('CMS_FRAMEWORK_HTML', 1);
define('CMS_FRAMEWORK_INCLUDE', 2);
define('CMS_FRAMEWORK_INPUT', 3);
define('CMS_FRAMEWORK_MERGE', 4);
define('CMS_FRAMEWORK_PHRASE', 5);
define('CMS_FRAMEWORK_SECTION', 6);


class zenario_api {
	
	
	  ////////////////////////////////////
	 //  Plugin Environment Variables  //
	////////////////////////////////////
	
	protected $cID;
	protected $containerId;
	protected $cType;
	protected $cVersion;
	protected $eggId;
	protected $fieldInfo;
	protected $instanceId;
	protected $instanceName;
	protected $inLibrary;
	protected $isVersionControlled;
	protected $isWireframe;
	protected $moduleId;
	protected $moduleClassName;
	protected $moduleClassNameForPhrases;
	protected $slotName;
	protected $slotLevel;
	protected $slideId;
	protected $parentNest;
	private $slotNameNestId;
	
	
	
	
	  //////////////////////////////////
	 //  Core Environment Variables  //
	//////////////////////////////////

	//These are in the cms_core class, e.g. cms_core::$cID
//	public static $equivId;
//	public static $cID;
//	public static $cType;
//	public static $cVersion;
//	public static $adminVersion;
//	public static $visitorVersion;
//	public static $isDraft;
//	public static $locked;
//	public static $alias;
//	public static $status;
//	public static $langId;
//	public static $adminId;
//	public static $userId;
//	public static $skinId;
//	public static $layoutId;
	
	
	
	
	  /////////////////////////////
	 //  Environment Functions  //
	/////////////////////////////
	
	public final function allowCaching(
		$atAll, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true
	) {
		$vs = &cms_core::$slotContents[$this->slotNameNestId]['cache_if'];
		
		foreach (array('a' => $atAll, 'u' => $ifUserLoggedIn, 'g' => $ifGetSet, 'p' => $ifPostSet, 's' => $ifSessionSet, 'c' => $ifCookieSet) as $if => $set) {
			if (!isset($vs[$if])) {
				$vs[$if] = true;
			}
			$vs[$if] = $vs[$if] && $vs['a'] && $set;
		}
	}
	
	public final function clearCacheBy(
		$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false
	) {
		$vs = &cms_core::$slotContents[$this->slotNameNestId]['clear_cache_by'];
		
		foreach (array('content' => $clearByContent, 'menu' => $clearByMenu, 'user' => $clearByUser, 'file' => $clearByFile, 'module' => $clearByModuleData) as $if => $set) {
			if (!isset($vs[$if])) {
				$vs[$if] = false;
			}
			$vs[$if] = $vs[$if] || $set;
		}
	}
	
	public final function callScriptBeforeAJAXReload($className, $scriptName /*[, $arg1 [, $arg2 [, ... ]]]*/) {
		$args = func_get_args();
		$this->zAPICallScriptWhenLoaded(0, $args);
	}
	
	public final function callScriptBeforeFoot($className, $scriptName /*[, $arg1 [, $arg2 [, ... ]]]*/) {
		$args = func_get_args();
		$this->zAPICallScriptWhenLoaded(1, $args);
	}
	
	public final function callScript($className, $scriptName /*[, $arg1 [, $arg2 [, ... ]]]*/) {
		$args = func_get_args();
		$this->zAPICallScriptWhenLoaded(2, $args);
	}
	
	//Deprecated, please use one of the above
	protected final function callScriptAdvanced($beforeAJAXReload, $className, $scriptName /*[, $arg1 [, $arg2 [, ... ]]]*/) {
		$args = func_get_args();
		array_splice($args, 0, 1);
		$this->zAPICallScriptWhenLoaded($beforeAJAXReload? 0 : 2, $args);
	}
	
	public final function cache($methodName, $expiryTimeInSeconds = 600, $request = '') {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public final function checkPostIsMine() {
		return !empty($_POST) && (empty($_POST['containerId']) || $_POST['containerId'] == $this->containerId);
	}
	public final function checkRequestIsMine() {
		return !empty($_REQUEST) && (empty($_REQUEST['containerId']) || $_REQUEST['containerId'] == $this->containerId);
	}

	public final function clearCache($methodName, $request = '', $useLike = false) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public final function getCIDAndCTypeFromSetting(&$cID, &$cType, $setting, $getLanguageEquivalent = true) {
		if (getCIDAndCTypeFromTagId($cID, $cType, $this->setting($setting))) {
			if ($getLanguageEquivalent) {
				$inId = $cID;
				$inType = $cType;
				if (langEquivalentItem($cID, $cType, false, true)) {
					return true;
				}
				$cID = $inId;
				$cType = $inType;
			}
			
			return (checkPriv() || getPublishedVersion($cID, $cType));
		}
		
		return false;
	}
	
	public final function setting($name) {
		return isset($this->zAPISettings[$name])? $this->zAPISettings[$name] : false;
	}
	
	protected final function setSetting(
		$name, $value,
		$changeInDB, $isContent = false, $format = 'text',
		$foreignKeyTo = null, $foreignKeyId = 0, $foreignKeyChar = '', $danglingCrossReferences = 'remove'
	) {
		$this->zAPISettings[$name] = $value;
		
		if ($changeInDB) {
			setRow('plugin_settings',
				array(
					'value' => $value,
					'is_content' => $this->isVersionControlled? ($isContent? 'version_controlled_content' : 'version_controlled_setting') : 'synchronized_setting',
					'format' => $format,
					'foreign_key_to' => $foreignKeyTo,
					'foreign_key_id' => $foreignKeyId,
					'foreign_key_char' => $foreignKeyChar,
					'dangling_cross_references' => $danglingCrossReferences),
				array('name' => $name, 'instance_id' => $this->instanceId, 'egg_id' => $this->eggId));
		}
	}
	
	
	
	
	  ///////////////////////////
	 //  Framework Functions  //
	///////////////////////////
	
	//New Twig version of zenario frameworks
	protected final function twigFramework($vars = array(), $return = false, $fromString = false, $fromFile = false) {
		
		$output = '';
		
		//In admin mode, the CMS might try to init() the plugin just to check if it's in the slot
		//and on the page.
		//In this case the twig library will not be present, but we shouldn't actually need to
		//output anything here!
		if (empty(cms_core::$twig)) {
			return $output;
		}
		
		
		//Add plugin environment variables
		if (!isset($vars['containerId'])) {
			$vars['containerId'] = $this->containerId;
		}
		if (!isset($vars['instanceId'])) {
			$vars['instanceId'] = $this->instanceId;
		}
		if (!isset($vars['isVersionControlled'])) {
			$vars['isVersionControlled'] = $this->isVersionControlled;
		}
		if (!isset($vars['moduleId'])) {
			$vars['moduleId'] = $this->moduleId;
		}
		if (!isset($vars['moduleClassName'])) {
			$vars['moduleClassName'] = $this->moduleClassName;
		}
		if (!isset($vars['slotName'])) {
			$vars['slotName'] = $this->slotName;
		}
		if (!isset($vars['slotLevel'])) {
			$vars['slotLevel'] = $this->slotLevel;
		}
		if (!isset($vars['parentNest'])) {
			$vars['parentNest'] = $this->parentNest;
		}
		
		//Add the CMS' environment variable
		if (!isset($vars['equivId'])) {
			$vars['equivId'] = cms_core::$equivId;
		}
		if (!isset($vars['cID'])) {
			$vars['cID'] = cms_core::$cID;
		}
		if (!isset($vars['cType'])) {
			$vars['cType'] = cms_core::$cType;
		}
		if (!isset($vars['cVersion'])) {
			$vars['cVersion'] = cms_core::$cVersion;
		}
		if (!isset($vars['isDraft'])) {
			$vars['isDraft'] = cms_core::$isDraft;
		}
		if (!isset($vars['alias'])) {
			$vars['alias'] = cms_core::$alias;
		}
		if (!isset($vars['langId'])) {
			$vars['langId'] = cms_core::$langId;
		}
		if (!isset($vars['adminId'])) {
			$vars['adminId'] = cms_core::$adminId;
		}
		if (!isset($vars['userId'])) {
			$vars['userId'] = cms_core::$userId;
		}
		if (!isset($vars['vars'])) {
			$vars['vars'] = cms_core::$vars;
		}
		
		//Add the current plugin
		if (!isset($vars['this'])) {
			$vars['this'] = $this;
		}
		
		
		//Add any modules that said they should be available in Twig
		foreach (cms_core::$twigModules as $className => &$class) {
			if (!isset($vars[$className])) {
				$vars[$className] = $class;
			}
		}
		
		
		$twigFile = 'framework.twig.html';
		cms_core::$isTwig = true;
		cms_core::$moduleClassNameForPhrases = $this->moduleClassNameForPhrases;
		
		try {
			if ($fromString === false) {
				if ($fromFile === false) {
					if ($this->framework
					 && $this->frameworkPath
					 && (is_file(cms_core::$frameworkFile = CMS_ROOT. $this->frameworkPath. $twigFile))) {
				
						if ($return) {
							$output = cms_core::$twig->render($this->frameworkPath. $twigFile, $vars);
						} else {
							echo cms_core::$twig->render($this->frameworkPath. $twigFile, $vars);
						}
			
					} else {
						if ($return) {
							$output = adminPhrase('This plugin requires a framework, but no framework was set.');
						} else {
							echo adminPhrase('This plugin requires a framework, but no framework was set.');
						}
					}
				} else {
					if ($return) {
						$output = cms_core::$twig->render($fromFile, $vars);
					} else {
						echo cms_core::$twig->render($fromFile, $vars);
					}
				}
			} else {
				if ($return) {
					$output = cms_core::$twig->render("\n". $fromString, $vars);
				} else {
					echo cms_core::$twig->render("\n". $fromString, $vars);
				}
			}
		} catch (Exception $e) {
			cms_core::$canCache = false;
			
			if (!checkPriv()) {
				if (defined('DEBUG_SEND_EMAIL') && DEBUG_SEND_EMAIL === true) {
					reportDatabaseError("Twig syntax error in visitor mode", $e->getMessage());
				}
	
				if (!defined('SHOW_SQL_ERRORS_TO_VISITORS') || SHOW_SQL_ERRORS_TO_VISITORS !== true) {
					echo 'A syntax error has occured in a framework on this section of the site. Please contact a site Administrator.';
					exit;
				}
			}

			if ($return) {
				$output = $e->getMessage();
			} else {
				echo $e->getMessage();
			}
		}
		
		cms_core::$isTwig = false;
		return $output;
	}
	
	protected final function frameworkIsTwig() {
		if (!$this->frameworkLoaded) {
			$this->zAPILoadFramework();
		}
		
		return $this->zAPIFrameworkIsTwig;
	}
	
	//HTML escape something for the old framework system, but leave it alone if this is Twig
	protected final function escapeIfOldFramework($text) {
		if ($this->frameworkIsTwig()) {
			return $text;
		} else {
			return htmlspecialchars($text);
		}
	}
	
	public final function slideNum() {
		if (isset(cms_core::$slotContents[$this->slotNameNestId]['slide_num'])) {
			return cms_core::$slotContents[$this->slotNameNestId]['slide_num'];
		}
	}
	
	public final function eggOrd() {
		if (isset(cms_core::$slotContents[$this->slotNameNestId]['egg_ord'])) {
			return cms_core::$slotContents[$this->slotNameNestId]['egg_ord'];
		}
	}
	
	public final function closeForm() {
		return '
				</form>';
	}
	
	public final function openForm($onSubmit = '', $extraAttributes = '', $action = false, $scrollToTopOfSlot = false, $fadeOutAndIn = true, $usePost = true) {
		
		//Don't attempt to show forms if we're not on the correct domain,
		//this will just trigger the XSS prevention script.
		//Instead, try to do a header redirect to the correct URL.
		if (cms_core::$wrongDomain) {
			$req = $_GET;
			unset($req['cID']);
			unset($req['cType']);
			unset($req['instanceId']);
			unset($req['method_call']);
			unset($req['slotName']);
			$this->headerRedirect(linkToItem(cms_core::$cID, cms_core::$cType, true, $req));
		}
		
		$html = '
				<form method="'. ($usePost? 'post' : 'get'). '" '. $extraAttributes. '
				  onsubmit="'. htmlspecialchars($onSubmit). ' return zenario.formSubmit(this, '. engToBoolean($scrollToTopOfSlot). ', '. (is_bool($fadeOutAndIn)? engToBoolean($fadeOutAndIn) : ('\'' . jsEscape($fadeOutAndIn) . '\'')). ', \''. jsEscape($this->slotName). '\');"
				  action="'. htmlspecialchars(ifNull($action, linkToItem(cms_core::$cID, cms_core::$cType, false, '', cms_core::$alias, true))). '">
					'. $this->remember('cID', $this->cID). '
					'. $this->remember('slideId', $this->slideId). '
					'. $this->remember('cType', $this->cType). '
					'. $this->remember('slotName', $this->slotName). '
					'. $this->remember('instanceId', $this->instanceId). '
					'. $this->remember('containerId', $this->containerId);
		
		//Add important requests to the URL
		foreach(cms_core::$importantGetRequests as $getRequest => $defaultValue) {
			if (isset($_REQUEST[$getRequest]) && $_REQUEST[$getRequest] != $defaultValue) {
				$html .= $this->remember($getRequest);
			}
		}
		
		//Add anything from the cms_core::$vars, if they were missed from the cms_core::$importantGetRequests
		foreach(cms_core::$vars as $getRequest => $value) {
			if (!isset(cms_core::$importantGetRequests[$getRequest]) && $value) {
				$html .= $this->remember($getRequest, $value);
			}
		}
		return $html;
	}
	
	protected final function pagination($paginationStyleSettingName, $currentPage, $pages, &$html, &$links = array(), $extraAttributes = array()) {
		//Attempt to check if the named class exists, and fall back to 'pagSimpleNumbers' if not
		$classAndMethod = explode('::', ifNull($this->setting($paginationStyleSettingName), $paginationStyleSettingName), 2);
		
		if (!empty($classAndMethod[0]) && !empty($classAndMethod[1]) && inc($classAndMethod[0]) && method_exists($classAndMethod[0], $classAndMethod[1])) {
			$class = new $classAndMethod[0];
			$method = $classAndMethod[1];
		} else {
			$class = new zenario_common_features;
			$method = 'pagCloseWithNPIfNeeded';
		}
		
		$class->setInstanceVariables(array(
			$this->cID, $this->cType, $this->cVersion, $this->slotName,
			$this->instanceName, $this->instanceId,
			$this->moduleClassName, $this->moduleClassNameForPhrases,
			$this->moduleId,
			$this->defaultFramework, $this->framework,
			$this->cssClass,
			$this->slotLevel, $this->isVersionControlled),
			$this->eggId, $this->slideId);
		$class->$method($currentPage, $pages, $html, $links, $extraAttributes);
	}
	
	protected final function translatePhrasesInTUIX(&$tags, $path, $languageId = false, $scan = false) {
		translatePhrasesInTUIX($tags, $this->zAPISettings, $path, $this->moduleClassNameForPhrases, $languageId, $scan);
	}
	
	protected final function translatePhrasesInTUIXObjects($tagNames, &$tags, $path, $languageId = false, $scan = false) {
		translatePhrasesInTUIXObjects($tagNames, $tags, $this->zAPISettings, $path, $this->moduleClassNameForPhrases, $languageId, $scan);
	}
		
	public final function phrase($text, $replace = array()) {
		return phrase($text, $replace, $this->moduleClassNameForPhrases, cms_core::$visLang);
	}
	
	public final function nphrase($text, $pluralText = false, $n = 1, $replace = array()) {
		return nphrase($text, $pluralText, $n, $replace, $this->moduleClassNameForPhrases, cms_core::$visLang);
	}
	
	public final function refreshPluginSlotAnchor($requests = '', $scrollToTopOfSlot = true, $fadeOutAndIn = true) {
		return
			$this->linkToItemAnchor($this->cID, $this->cType, $fullPath = false, '&slotName='. $this->slotName. ($this->slideId? '&slideId='. $this->slideId : ''). urlRequest($requests)).
			' onclick="'.
				$this->refreshPluginSlotJS($requests, $scrollToTopOfSlot, $fadeOutAndIn).
				' return false;"';
	}
	
	public final function refreshPluginSlotAnchorAndJS($requests = '', $scrollToTopOfSlot = true, $fadeOutAndIn = true) {
		return array($this->refreshPluginSlotAnchor($requests, $scrollToTopOfSlot), $this->refreshPluginSlotJS($requests, $scrollToTopOfSlot, $fadeOutAndIn));
	}
	
	public final function refreshPluginSlotJS($requests = '', $scrollToTopOfSlot = true, $fadeOutAndIn = true) {
		return 
			$this->moduleClassName.'.refreshPluginSlot('.
				'\''. $this->slotName. '\', '.
				'\''. jsOnClickEscape(urlRequest($requests)). '\', '.
				($scrollToTopOfSlot? 1 : 0). ', '.
				($fadeOutAndIn? 1 : 0). ');';
	}
	
	public final function remember($name, $value = false, $htmlId = false, $type = 'hidden') {
		
		if ($value === false) {
			$value = $_REQUEST[$name] ?? false;
		}
		
		if ($htmlId === true) {
			$htmlId = $name;
		}
		
		if ($htmlId) {
			$htmlId = ' id="'. htmlspecialchars($htmlId). '"';
		}
		
		return '<input type="'. $type. '"'. $htmlId. ' name="'. htmlspecialchars($name). '" value="'. htmlspecialchars($value). '" />';
	}
	
	protected final function replacePhraseCodesInString(&$string) {
		$content = preg_split('/\[\[([^\[\]]+)\]\]/', $string, -1,  PREG_SPLIT_DELIM_CAPTURE);
		$string = '';
		foreach ($content as $i => &$str) {
			if ($i%2) {
				//Every odd element will be a phrase code
				$string .= $this->phrase($str);
			} else {
				//Every even element will be plain text
				$string .= $str;
			}
		}
	}
	
	public function returnGlobalName() {
		return $this->moduleClassName. '_'. str_replace('-', '__', $this->containerId);
	}
	
	
	
	
	  ////////////////////////////////
	 //  Initialization Functions  //
	////////////////////////////////
	
	protected final function captcha() {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected final function checkCaptcha() {
		return require funIncPath(__FILE__, __FUNCTION__);
	}

	public final function forcePageReload($reload = true) {
		$this->zAPIForcePageReload($reload);
	}

	public final function headerRedirect($link) {
		$this->zAPIHeaderRedirect($link);
	}

	protected final function markSlotAsBeingEdited($beingEdited = true) {
		$this->zAPIMarkSlotAsBeingEdited($beingEdited);
	}

	public final function showInFloatingBox($showInFloatingBox = true, $floatingBoxParams = false) {
		$this->cmsApiShowInFloatingBox($showInFloatingBox, $floatingBoxParams);
	}

	protected final function scrollToTopOfSlot($scrollToTop = true) {
		$this->cmsApiScrollToTopOfSlot($scrollToTop);
	}

	public final function registerGetRequest($request, $defaultValue = '') {
		cms_core::$importantGetRequests[$request] = $defaultValue;
	}
	public final function clearRegisteredGetRequest($request) {
		unset(cms_core::$importantGetRequests[$request]);
		
		if ($this->eggId) {
			$this->callScriptAtTheEnd('zenario_conductor', 'clearRegisteredGetRequest', $this->slotName, $request);
		}
	}
	
	public final function setPageTitle($title) {
		cms_core::$slotContents[$this->slotName]['page_title'] = $title;
		cms_core::$pageTitle = $title;
	}
	
	public final function setPageDesc($description) {
		cms_core::$slotContents[$this->slotName]['page_desc'] = $description;
		cms_core::$pageDesc = $description;
	}
	
	public final function setPageImage($imageId) {
		cms_core::$slotContents[$this->slotName]['page_image'] = $imageId;
		cms_core::$pageImage = $imageId;
	}
	
	public final function setPageKeywords($keywords) {
		cms_core::$slotContents[$this->slotName]['page_keywords'] = $keywords;
		cms_core::$pageKeywords = $keywords;
	}
	
	public final function setPageOGType($type) {
		cms_core::$slotContents[$this->slotName]['page_og_type'] = $type;
		cms_core::$pageOGType = $type;
	}

	public final function setMenuTitle($title) {
		cms_core::$slotContents[$this->slotName]['menu_title'] = $title;
		cms_core::$menuTitle = $title;
	}
	

	protected final function showInMenuMode($shownInMenuMode = true) {
		$this->zAPIShowInMenuMode($shownInMenuMode);
	}
	
	
	
	
	  ///////////////////////////////
	 //  Link/Path/URL Functions  //
	///////////////////////////////
	
	public final function linkToItem(
		$cID, $cType = 'html', $fullPath = false, $request = '', $alias = false,
		$autoAddImportantRequests = false, $useAliasInAdminMode = false
	) {
		return linkToItem($cID, $cType, $fullPath, $request, $alias, $autoAddImportantRequests, $useAliasInAdminMode);
	}
	
	public final function linkToItemAnchor(
		$cID, $cType = 'html', $fullPath = false, $request = '', $alias = false,
		$autoAddImportantRequests = false, $useAliasInAdminMode = false
	) {
		return ' href="'. htmlspecialchars(linkToItem($cID, $cType, $fullPath, $request, $alias, $autoAddImportantRequests, $useAliasInAdminMode)). '"';
	}
	
	public final function linkToItemAnchorAndJS(
		$cID, $cType = 'html', $fullPath = false, $request = '', $alias = false,
		$autoAddImportantRequests = false, $useAliasInAdminMode = false
	) {
		return array($this->linkToItemAnchor($cID, $cType, $fullPath, $request, $alias, $autoAddImportantRequests, $useAliasInAdminMode), $this->linkToItemJS($cID, $cType, $request));
	}
	
	public final function linkToItemJS($cID, $cType = 'html', $request = '') {
		return $this->moduleClassName. '.goToItem(\''. jsOnClickEscape($cID). '\', \''. jsOnClickEscape($cType). '\', \''. jsOnClickEscape($request). '\');';
	}
	
	public final function moduleDir($subDir = '') {
		return moduleDir($this->moduleClassName, $subDir);
	}
	
	public final function AJAXLink($requests = '') {
		return 'zenario/ajax.php?moduleClassName='. $this->moduleClassName. '&method_call=handleAJAX'. urlRequest($requests);
	}
	
	//Old name of the above function, deprecated
	protected final function moduleAJAXURL($requests = '') {
		return $this->AJAXLink($requests);
	}
	
	public final function pluginAJAXLink($requests = '') {
		return
			httpOrHttps(). httpHost(). SUBDIRECTORY.
			'zenario/ajax.php?moduleClassName='. $this->moduleClassName. '&method_call=handlePluginAJAX'.
			'&cID='. $this->cID.
			'&cType='. $this->cType.
		  (checkPriv()?
			'&cVersion='. $this->cVersion
		   : '').
			'&instanceId='. $this->instanceId.
			'&slotName='. $this->slotName.
			'&eggId='. $this->eggId.
			urlRequest($requests);
	}
	
	public final function showFileLink($requests = '') {
		return
			httpOrHttps(). httpHost(). SUBDIRECTORY.
			'zenario/ajax.php?moduleClassName='. $this->moduleClassName. '&method_call=showFile'.
			urlRequest($requests);
	}
	
	public final function showFloatingBoxLink($requests = '') {
		return
			httpOrHttps(). httpHost(). SUBDIRECTORY.
			'zenario/ajax.php?moduleClassName='. $this->moduleClassName. '&method_call=showFloatingBox'.
			'&cID='. $this->cID.
			'&cType='. $this->cType.
		  (checkPriv()?
			'&cVersion='. $this->cVersion
		   : '').
			'&instanceId='. $this->instanceId.
			'&slotName='. $this->slotName.
			'&eggId='. $this->eggId.
			urlRequest($requests);
	}
	
	public final function showSingleSlotLink($requests = '', $hideLayout = true) {
		return
			$this->linkToItem($this->cID, $this->cType, false, 
			  (checkPriv()?
				'&cVersion='. $this->cVersion
			   : '').
				'&method_call=showSingleSlot'.
				'&instanceId='. $this->instanceId.
				'&slotName='. $this->slotName.
				'&eggId='. $this->eggId.
				($hideLayout? '&hideLayout=1' : '').
				urlRequest($requests),
			cms_core::$alias);
	}
	

	
	public final function visitorTUIXLink($callbackFromScriptTags, $path, $requests = '', $mode = 'fill') {
		return
			httpOrHttps(). httpHost(). SUBDIRECTORY.
			'zenario/ajax.php?moduleClassName='. $this->moduleClassName.
			'&method_call='. ($mode == 'format' || $mode == 'validate' || $mode == 'save'? $mode : 'fill'). 'VisitorTUIX'.
			'&path='. urlencode($path).
			'&_script='. engToBoolean($callbackFromScriptTags).
			urlRequest($requests, true);
	}
	
	public final function pluginVisitorTUIXLink($callbackFromScriptTags, $path, $requests = '', $mode = 'fill') {
		return
			$this->visitorTUIXLink($callbackFromScriptTags, $path, $requests, $mode).
			'&cID='. $this->cID.
			'&cType='. $this->cType.
		  (checkPriv()?
			'&cVersion='. $this->cVersion
		   : '').
			'&instanceId='. $this->instanceId.
			'&slotName='. $this->slotName.
			'&eggId='. $this->eggId;
	}
	
	
	//Old deprecated link
	protected final function showIframeLink($requests = '', $hideLayout = false) {
		return $this->showSingleSlotLink($requests, $hideLayout);
	}
	
	public final function showImageLink($requests) {
		return
			httpOrHttps(). httpHost(). SUBDIRECTORY.
			'zenario/ajax.php?moduleClassName='. $this->moduleClassName. '&method_call=showImage'.
			urlRequest($requests);
	}
	
	public final function showStandalonePageLink($requests) {
		return
			httpOrHttps(). httpHost(). SUBDIRECTORY.
			'zenario/ajax.php?moduleClassName='. $this->moduleClassName. '&method_call=showStandalonePage'.
			urlRequest($requests);
	}
	
	public final function showRSSLink($allowFriendlyURL = false, $overwriteFriendlyURL = true) {
		$request = 'method_call=showRSS';
		
	 	//Attempt to check whether we can use Friendly URLs for the RSS links
	 	//Each page has one friendly URL to use for an RSS link.
	 	//If only one Plugin on a page uses RSS links, that Plugin will have the friendly URL.
	 	//If two Plugins on a page use RSS links, the first to call this function will have a friendly URL.
		
		if (!$allowFriendlyURL
			//Only allow the first Plugin to call this function to set the RSS link
		 || !cms_core::$rss1st
	 		//Don't attempt to set the RSS link if one of the Plugins was served from the cache, as the logic isn't generated properly in this case
		 || cms_core::$cachingInUse
	 		//Nested Plugins on tabs other than the first slide should not be able to set the RSS link
		 || !empty($_REQUEST['slideId'])
		 || !empty($_REQUEST['slideNum'])
	 		//Don't attempt to set the RSS link if we're only showing a specific Plugin on a page that may have more
		 || !empty($_REQUEST['slotName'])
		 || !empty($_REQUEST['instanceId'])) {
			$overwriteFriendlyURL = false;
		}
		
		if ($allowFriendlyURL
		 && (($rss = $this->eggId. '_'. $this->slotName) == cms_core::$rss || $overwriteFriendlyURL)) {
			//If we are going to use a friendly URL, record the actual Instance Id and Nested Plugin Id
			cms_core::$rss = $rss;
		
		} else {
			$request .=
				'&instanceId='. $this->instanceId.
				'&slotName='. $this->slotName;
			
			if ($this->eggId) {
				$request .=
					'&eggId='. $this->eggId;
			}
		}
		
		//Only allow the first Plugin to call this function to set the RSS link
		if ($allowFriendlyURL) {
			cms_core::$rss1st = false;
		}
		
		return linkToItem($this->cID, $this->cType, true, $request, cms_core::$alias, false, true);
	}


	/**
	 * Utility function to show an thumbnail image as an html snippet.
	 * @param unknown $image_id
	 * @param unknown $snippet_field
	 * @param number $widthLimit
	 * @param number $heightLimit
	 */
	protected function getImageHtmlSnippet($image_id, &$snippet_field, $widthLimit = false, $heightLimit = false){
		if($image_id) {
			$width = $height = $url = $widthR = $heightR = $urlR = false;
			Ze\File::imageLink($width, $height, $url, $image_id, $widthLimit, $heightLimit, $mode = 'resize', $offset = 0, $retina = false, $privacy = 'auto', $useCacheDir = false);
			Ze\File::imageLink($widthR, $heightR, $urlR, $image_id, $widthLimit = 700, $heightLimit = 200, $mode = 'resize', $offset = 0, $retina = true, $privacy = 'auto', $useCacheDir = false);
	
			$snippet_field = '
			<p style="text-align: center;">
				<a>
					<img src="'. htmlspecialchars(absCMSDirURL(). $urlR). '"
						width="'. $widthR. '" height="'. $heightR. '" style="border: 1px solid black;"/>
				</a>
			</p>';
		}
	}
	
	
	
	  //////////////////////////////////////////////////
	 //  Functions that interact with the conductor  //
	//////////////////////////////////////////////////


	public function conductorEnabled() {
		return isset($this->parentNest) && $this->parentNest->cEnabled();
	}
	public function conductorCommandEnabled($command) {
		return isset($this->parentNest) && $this->parentNest->cCommandEnabled($command);
	}
	public function conductorLink($command, $requests = array()) {
		if (isset($this->parentNest)) {
			return $this->parentNest->cLink($command, $requests);
		}
		return false;
	}

	public function conductorBackLink() {
		if (isset($this->parentNest)) {
			return $this->parentNest->cBackLink();
		} else {
			return false;
		}
	}
	
	
	
	  /////////////////////////////////////////////
	 //  Core functions that make the API work  //
	/////////////////////////////////////////////
	
	//These functions provide functionality for the API functions, and help the Core and the API
	//talk to each other.
	//They're included in this file for efficiency reasons, but Module Developers don't need to know
	//about them.
	
	//Plugin Settings
	protected $zAPISettings = array();

	//Disable AJAX Relaod
	private $zAPIForcePageReloadVar = false;
	public final function checkForcePageReloadVar() {
		return $this->zAPIForcePageReloadVar;
	}
	protected final function zAPIForcePageReload($reload) {
		$this->zAPIForcePageReloadVar = $reload;
	}
	
	//Reload to a different location
	private $zAPIHeaderRedirectLocation = false;
	protected final function zAPIHeaderRedirect($location) {
		$this->zAPIHeaderRedirectLocation = $location;
	}
	public final function checkHeaderRedirectLocation() {
		return $this->zAPIHeaderRedirectLocation;
	}

	//How to display after an AJAX reload
	private $zAPIShowInFloatingBox = false;
	private $zAPIFloatingBoxParams = false;
	public final function getFloatingBoxParams() {
		return $this->zAPIFloatingBoxParams;
	}
	public final function checkShowInFloatingBoxVar() {
		return $this->zAPIShowInFloatingBox;
	}
	protected final function cmsApiShowInFloatingBox($showInFloatingBox, $floatingBoxParams) {
		$this->zAPIShowInFloatingBox = $showInFloatingBox;
		$this->zAPIFloatingBoxParams = $floatingBoxParams;
	}
	
	private $zAPIScrollToTop = null;
	public final function checkScrollToTopVar() {
		return $this->zAPIScrollToTop;
	}
	protected final function cmsApiScrollToTopOfSlot($scrollToTop) {
		$this->zAPIScrollToTop = $scrollToTop;
	}

	//A list of JavaScript functions to run
	private $zAPIScripts = array(array(), array(), array());
	public final function zAPICallScriptWhenLoaded($scriptType, &$script) {
		if (isset($this->zAPIMainClass)) {
			$this->zAPIMainClass->zAPICallScriptWhenLoaded($scriptType, $script);
		} else {
			$this->zAPIScripts[$scriptType][] = $script;
		}
	}
	public final function zAPICheckRequestedScripts(&$scripts) {
		$scripts = $this->zAPIScripts;
	}
	
	//Mark this Plugin as Menu-related
	private $zAPIShownInMenuMode;
	public final function shownInMenuMode() {
		return $this->zAPIShownInMenuMode;
	}
	protected final function zAPIShowInMenuMode($shownInMenuMode) {
		$this->zAPIShownInMenuMode = $shownInMenuMode;
	}
	
	//Mark this Plugin as being editing
	private $zAPISlotBeingEdited;
	public final function beingEdited() {
		return $this->zAPISlotBeingEdited;
	}
	protected final function zAPIMarkSlotAsBeingEdited($beingEdited) {
		$this->zAPISlotBeingEdited = $beingEdited;
	}
	
	public final function zAPIGetTabId() {
		return $this->slideId;
	}

	//Framework and Swatch for this plugin.
	protected $framework;
	protected $cssClass;
	
	protected $defaultFramework;
	
	private $frameworkPath;
	private $frameworkData;
	private $frameworkLoaded = false;
	private $zenario2Twig = false;
	protected $frameworkOutputted = false;
	protected $zAPIFrameworkIsTwig = false;
	
	private $zAPIFirst = true;
	private final function zAPIFirst() {
		if ($this->zAPIFirst) {
			$this->zAPIFirst = false;
			return 'first';
		} else {
			return '';
		}
	}
	
	private $zAPIOddOrEven = 'even';
	private final function zAPIOddOrEven($change = true) {
		if ($change) {
			$this->zAPIOddOrEven = $this->zAPIOddOrEven == 'odd'? 'even' : 'odd';
		}
		return $this->zAPIOddOrEven;
	}
	
	
	public final function zAPIGetCachableVars(&$a) {
		$a = array(
			$this->framework,
			$this->zAPIScripts,
			false, //not used any more
			$this->slideId,
			$this->cssClass,
			$this->eggId,
			$this->slideId);
	}
	
	public final function zAPISetCachableVars(&$a) {
		if (cms_core::$isTwig) return;
		
		$this->framework = $a[0];
		$this->zAPIScripts = $a[1];
		//$a[2] isn't used anymore
		$this->slideId = $a[3];
		$this->cssClass = $a[4];
		$this->eggId = $a[5];
		$this->slideId = $a[6];
	}
	
	
	public final function setInstanceVariables(
		$locationAndInstanceDetails,
		$eggId = 0, $slideId = 0, $settings = false, $frameworkPath = false, $mainClass = false
	) {
		if (cms_core::$isTwig) return;
		
		//Set the variables above from the array given
		list($this->cID, $this->cType, $this->cVersion, $this->slotName,
			 $this->instanceName, $this->instanceId,
			 $this->moduleClassName, $this->moduleClassNameForPhrases,
			 $this->moduleId,
			 $this->defaultFramework, $this->framework,
			 $this->cssClass,
			 $this->slotLevel, $this->isVersionControlled) = $locationAndInstanceDetails;
		
		$this->cID = (int) $this->cID;
		$this->cVersion = (int) $this->cVersion;
		$this->instanceId = (int) $this->instanceId;
		$this->moduleId = (int) $this->moduleId;
		$this->eggId = (int) $eggId;
		$this->slideId = (int) $slideId;
		$this->inLibrary = !$this->isVersionControlled;
		$this->isWireframe = $this->isVersionControlled; //For backwards compatability
		
		$this->slotName = preg_replace('/[^\w-]/', '', $this->slotName);
		$this->slotNameNestId = $this->slotName. ($this->eggId? '-'. $this->eggId : '');
		$this->defaultFramework = preg_replace('/[^\w-]/', '', $this->defaultFramework);
		$this->framework = preg_replace('/[^\w-]/', '', $this->framework);
		
		if ($this->slotName) {
			//Generate a container id for the plugin
			$this->containerId = 'plgslt_'. $this->slotName;
			
			if ($this->eggId) {
				$this->containerId .= '-'. $this->eggId;
				
				if (!empty(cms_core::$slotContents[$this->slotName]['class'])) {
					$this->parentNest = cms_core::$slotContents[$this->slotName]['class'];
				}
			}
		}
		
		if ($settings !== false) {
			$this->zAPISettings = $settings;
		}
		
		if ($frameworkPath !== false) {
			$this->frameworkPath = $frameworkPath;
		}
		
		if ($frameworkPath !== false) {
			$this->zAPIMainClass = $mainClass;
		}
	}
	
	public final function setInstance($locationAndInstanceDetails = false, $overrideSettings = false, $eggId = 0, $slideId = 0) {
		if (cms_core::$isTwig) return;
		
		$this->setInstanceVariables($locationAndInstanceDetails, $eggId, $slideId);
		
		//Set up settings for front-end plugins
		if ($this->instanceId) {
			//Check that the chosen framework exists
			//Attempt to fall back to the default framework if it does not
			if ($this->framework || $this->defaultFramework) {
				if (!$this->framework || !($this->frameworkPath = frameworkPath($this->framework, $this->moduleClassName))) {
					if ($this->defaultFramework && $this->framework != $this->defaultFramework) {
						$this->framework = $this->defaultFramework;
						if (!$this->frameworkPath = frameworkPath($this->framework, $this->moduleClassName)) {
							$this->framework = false;
						}
					} else {
						$this->framework = false;
					}
				}
			}
			
			
			
			//Look up this plugin's settings, starting with the default values
			//Make sure to get default values if they are defined in extened Modules
			foreach (getModuleInheritances($this->moduleClassName, 'inherit_settings') as $className) {
				$sql = "
					SELECT `name`, default_value
					FROM ". DB_NAME_PREFIX. "plugin_setting_defs
					WHERE module_class_name = '". sqlEscape($className). "'";
				$result = sqlQuery($sql);
				
				while($row = sqlFetchAssoc($result)) {
					if (!isset($this->zAPISettings[$row['name']])) {
						$this->zAPISettings[$row['name']] = $row['default_value'];
					}
				}
			}
			
			
			//Now look up the settings that have been set, and overwrite the defaults
			$sql = "
				SELECT `name`, `value`
				FROM ". DB_NAME_PREFIX. "plugin_settings
				WHERE instance_id = ". (int) $this->instanceId. "
				  AND egg_id = ". (int) $eggId;
			
			//Don't load phrase overrides for Reusable Plugins
			//(Phrase overrides will begin with a %)
			if (!$this->isVersionControlled) {
				$sql .= "
				  AND name NOT LIKE '\%%'";
			}
			
			$result = sqlQuery($sql);
			
			while($row = sqlFetchAssoc($result)) {
				$this->zAPISettings[$row['name']] = $row['value'];
			}
			
			
			//Plugin previews get to override these settings on a temporary basis
			if (!empty($overrideSettings)
			 && is_array($overrideSettings)) {
				foreach ($overrideSettings as $name => $value) {
					$this->zAPISettings[$name] = $value;
				}
			}
		}
	}
	
	//Display a Slot and its wrappers
	public final function show($includeAdminControlsIfInAdminMode = true, $showPlaceholderMethod = 'showSlot') {
		if (cms_core::$isTwig) return;
		
		$edition = cms_core::$edition;
		$isLayoutPreview = cms_core::$cID === -1;
		$isShowSlot = $showPlaceholderMethod == 'showSlot';
		
		$slot = &cms_core::$slotContents[$this->slotNameNestId];
		
		//Include the controls if this is admin mode, and if this is not a preview of a layout
		if ($checkPriv = $includeAdminControlsIfInAdminMode && !$isLayoutPreview && checkPriv()) {
			$this->startIncludeAdminControls();
		}
		
		
		//Here
		//Experiementing with showing a layout preview when you click on the layout tab
		//N.b. I'd need to do something like this on the AJAX reload too...
		//I'd also need to catch the case where a plugin got overridden, and show the layout preview from the module that was overridden..?
		
		if ($checkPriv
		 && !$isLayoutPreview
		 && $this->slotLevel == 2
		 && checkPriv('_PRIV_MANAGE_TEMPLATE_SLOT')) {
			
			echo '<div id="'. $this->containerId. '-layout_preview" class="zenario_slot_layout_preview zenario_slot '. $this->cssClass. '"';
			
			if ($this->shouldShowLayoutPreview()
			 && !$this->eggId
			 && !empty($slot['module_id'])) {
			
				$this->cssClass .= ' zenario_slot_with_layout_preview';
				
				echo '>';
					$this->showLayoutPreview();
				echo '</div>';
			
			} else {
				echo ' style="display: none;"></div>';
			}
		}
		
		echo $this->startInner();
			
			if ($isLayoutPreview && $isShowSlot) {
				$this->showLayoutPreview();
			
			} else {
				//Check whether the plugin's init function returned true
				$status = false;
				if (isset($slot['init'])) {
					$status = $slot['init'];
				}
				
				//In admin mode, show an error if the plugin could not run due to user permissions
				if (($status === ZENARIO_401_NOT_LOGGED_IN || $status === ZENARIO_403_NO_PERMISSION) && checkPriv()) {
					
					//N.b. as a convience feature, I'll allow for plugin devs to send either a 401 or a 403 error,
					//and pick the correct message here
					if (userId()) {
						echo '<em>'. adminPhrase('You do not have adequate user permission to view this plugin.'). '</em>';
					} else {
						echo '<em>'. adminPhrase('You need to be logged in as an extranet user to view this plugin.'). '</em>';
					}
				
				} elseif ($status) {
					
					if (!$this->eggId) {
						$edition::preSlot($this->slotName, $showPlaceholderMethod);
					}
					
					$this->$showPlaceholderMethod();
		
					if ($isShowSlot) {
						$this->afterShowSlot();
					}
					
					if (!$this->eggId) {
						$edition::postSlot($this->slotName, $showPlaceholderMethod);
					}
				
				} elseif ($checkPriv && empty($slot['module_id'])) {
					echo adminPhrase('[Empty Slot]');
				}
			}
		echo $this->endInner();
	}
	
	//This method is part of a hack to help automatically migrate a Module using the old framework system to using Twig.
	//Any calls to frameworkHead() or to framework() for sections other than "Outer" are stored in the $this->zenario2Twig array.
	//After showSlot is finished, we'll output the $this->zenario2Twig arrat using Twig.
	public final function afterShowSlot() {
		if (cms_core::$isTwig) return;
		
		if ($this->zAPIFrameworkIsTwig
		 && $this->zenario2Twig !== false) {
			$this->twigFramework($this->zenario2Twig);
			$this->zenario2Twig = false;
		}
	}
	
	//Display the starting wrapper of a slot
	public final function start() {
		if (cms_core::$isTwig) return;
		
		//Put a section around the slot and the slot controls in admin mode.
		//This lets us adjust the look of the slot and the slot controls using CSS.
		if (checkPriv()) {
			echo '
				<section id="', $this->containerId, '-wrap" class="zenario_slotOuter ', $this->instanceId? 'zenario_slotWithContents' : 'zenario_slotWithNoContents', '">';
		}
	}
	
	//Display the admin controls for a slot
	private final function startIncludeAdminControls() {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	//Put a div around the slot, so we can reload the contents
	public final function startInner() {
		if (cms_core::$isTwig) return;
		
		return '
					<div id="'. $this->containerId. '" class="zenario_slot '. $this->cssClass. '">';
	}
	
	//Close the admin controls for a slot.
	public final function endInner() {
		if (cms_core::$isTwig) return;
		
		$padding = '';
		if (checkPriv()) {
			if ($this->instanceId && !$this->frameworkOutputted) {
				$padding = '
					<span class="zenario_slot_padding">&nbsp;</span>';
			}
		}
		
		return $padding. '
					</div>';
	}
	
	//Close the wrapper for a slot.
	public final function end() {
		if (cms_core::$isTwig) return;
		
		//Display the HTML at the end of a slot when in admin mode
		if (checkPriv()) {
			echo '
				</section>';
		}
	}
	
	protected final function zAPILoadFramework() {
		//Keep an eye on whether this method has already run, and don't let it be called twice
		if ($this->frameworkLoaded) {
			return true;
		}
		
		$errors = array();
		$this->fieldInfo = array();
		$path = CMS_ROOT. $this->frameworkPath;
		
		//New framework code, using Twig
		if (is_file($file = $path. 'framework.twig.html')) {
			//No loading needed here; Twig recompiles if needed when displaying a framework.
			$this->zAPIFrameworkIsTwig = true;
		
		//Old framework code
		//Check to see if the framework html file exists, and start loading it if it does
		} elseif (is_file($file = $path. 'framework.html')) {
			
			//Load the framework from the file
			//Load the framework into memory, and split it up into pieces using special comments
			$themeThings =
				preg_split('@\[(/?)cms:(\w+)(.*?)(/?)\]@s',
					preg_replace('@\[\!\-\-.*?\-\-\]@s', '',
						file_get_contents($file)
					),
				-1,  PREG_SPLIT_DELIM_CAPTURE);
			
			
			//We need to be able to tell the difference between sections (which is opened and closed) and mergeFields that close themselves
			//Make a list of things that get closed, so we can tell the two apart
			$this->frameworkData = array('Outer' => array());
			
			$openThings = array('Outer');
			$count = 0;
			$c = count($themeThings) - 1;
			for ($i=0; $i < $c; $i += 5) {
				//Add any raw HTML directly to the output
				$this->frameworkData[$openThings[$count]][] = array(CMS_FRAMEWORK_HTML => $themeThings[$i]);
				
				//Is this a close-tag for a section?
				if ($themeThings[$i+1] && $themeThings[$i+2] != 'condition') {
					if ($themeThings[$i+4]) {
						$errors['open-close-tags'] = 'Tags cannot be both a close tag and a self closing tag.';
					
					//Ignore anything closing that isn't a section
					} elseif ($themeThings[$i+2] != 'section') {
						continue;
					
					//Otherwise try to close the current section
					} elseif (--$count < 0) {
						$errors['close-tags'] = 'Sections are unbalanced. More were closed than opened.';
						break;
					}
				
				} else {
					$attributes = array();
					$details = preg_split('@\s*(\w*)=([\'"]?)(.*?)\2\s*@s', $themeThings[$i+3], -1,  PREG_SPLIT_DELIM_CAPTURE);
					$cd = count($details) - 1;
					for ($j=0; $j < $cd; $j += 4) {
						$attributes[$details[$j+1]] = html_entity_decode($details[$j+3], ENT_COMPAT, 'UTF-8');
					}
					
					//Is this an opening of a section?
					if ($themeThings[$i+2] == 'section' && !empty($attributes['name'])) {
						
						if ($themeThings[$i+4]) {
							//Allow self closing sections as placeholders
							$this->frameworkData[$openThings[$count]][] = array(CMS_FRAMEWORK_SECTION => $attributes['name']);
						
						} elseif (is_array(arrayKey($this->fieldInfo, $attributes['name']))) {
							$errors['section ' + $attributes['name']] = 'Section '. $attributes['name']. ' was defined twice.';
							break;
							
						} else {
							$this->fieldInfo[$attributes['name']] = array('%attributes%' => $attributes);
							
							//Note that the section we were currently looking in contains this new section
							$this->frameworkData[$openThings[$count]][] = array(CMS_FRAMEWORK_SECTION => $attributes['name']);
							
							//Add this section into the list of currently open sections, so everything we parse will fall into it
							$this->frameworkData[$openThings[++$count] = $attributes['name']] = array();
						}
					
					//Is this a phrase?
					} elseif ($themeThings[$i+2] == 'phrase' && !empty($attributes['code'])) {
						$this->frameworkData[$openThings[$count]][] = array(CMS_FRAMEWORK_PHRASE => $attributes['code']);
					
					//Is this is a named mergeField?
					} elseif ($themeThings[$i+2] == 'merge' && !empty($attributes['name'])) {
						$this->frameworkData[$openThings[$count]][] = array(CMS_FRAMEWORK_MERGE => $attributes['name']);
					
					//Is this is an include?
					} elseif ($themeThings[$i+2] == 'include') {
						$this->frameworkData[$openThings[$count]][] = array(CMS_FRAMEWORK_INCLUDE => $attributes);
					
					//Is this is a condition?
					} elseif ($themeThings[$i+2] == 'condition') {
						$this->frameworkData[$openThings[$count]][] = array(CMS_FRAMEWORK_CONDITION => $attributes);
					
					//Is this an input?
					} elseif ($themeThings[$i+2] == 'input' && !empty($attributes['name'])) {
						$this->frameworkData[$openThings[$count]][] = array(CMS_FRAMEWORK_INPUT => $attributes['name']);
						$this->fieldInfo[$openThings[$count]][$attributes['name']] = $attributes;
						
						//For date pickers - if the client does not have JavaScript enabled, three select lists are used as a fallback
						if ($attributes['type'] == 'date' && isset($_POST['cID'])) {
							if (!empty($_POST[$attributes['name']. '__1'])
							 && !empty($_POST[$attributes['name']. '__2'])
							 && !empty($_POST[$attributes['name']. '__3'])) {
								$_POST[$attributes['name']] = 
									$_POST[$attributes['name']. '__3']. '-'.
									$_POST[$attributes['name']. '__2']. '-'.
									$_POST[$attributes['name']. '__1'];
								
								unset($_POST[$attributes['name']. '__1']);
								unset($_POST[$attributes['name']. '__2']);
								unset($_POST[$attributes['name']. '__3']);
							}
						
						//For file uploads - if there has been a submission, move it to the private/uploads directory and
						//place the path in the $_POST variable.
						} elseif ($attributes['type'] == 'file' && isset($_POST['cID'])) {
							if (!empty($_FILES[$attributes['name']]) && is_uploaded_file($_FILES[$attributes['name']]['tmp_name'])) {
								if (cleanCacheDir()) {
									$randomDir = createRandomDir(30, 'uploads');
									$newName = $randomDir. Ze\File::safeName($_FILES[$attributes['name']]['name'], true);
									
									//Change the extension
									$newName = $newName. '.upload';
									
									if (move_uploaded_file($_FILES[$attributes['name']]['tmp_name'], CMS_ROOT. $newName)) {
										@chmod(CMS_ROOT. $newName, 0666);
										$_POST[$attributes['name']] =
										$_REQUEST[$attributes['name']] = $newName;
									}
								}
							}
							
							//Stop the user trying to trick the CMS into submitting a different file in a different location
							if (!empty($_POST[$attributes['name']])) {
								if (strpos($_POST[$attributes['name']], '..') !== false
								 || !preg_match('@^private/uploads/[\w\-]+/[\w\.-]+\.upload$@', $_POST[$attributes['name']])) {
									unset($_POST[$attributes['name']]);
								}
							}
							
							unset($_GET[$attributes['name']]);
						
						
						//For multiple checkboxes
						} elseif ($attributes['type'] == 'checkbox' && isset($_POST['cID']) && isset($_POST[$attributes['name']. '__n'])) {
							$post = '';
							for ($k = 1; $k <= (int) $_POST[$attributes['name']. '__n']; ++$k) {
								if (isset($_POST[$attributes['name']. '__'. $k])) {
									$post .= ($post === ''? '' : ','). $_POST[$attributes['name']. '__'. $k];
								}
							}
							
							if ($post !== '') {
								$_POST[$attributes['name']] = $post;
							}
							
							unset($_POST[$attributes['name']. '__n']);
						
						
						//If this is a post submission, check if a toggle type field has been changed
						} elseif ($attributes['type'] == 'toggle' && isset($_POST['cID']) && isset($_POST[$attributes['name']. '__n'])) {
							//Look up the list of possible values
							$lov = false;
							$this->zAPIFrameworkLOV($attributes['type'], $attributes, $lov);
							
							//Get the total number of toggles, and look at each toggle in turn
							for ($k = 1; $k <= (int) $_POST[$attributes['name']. '__n']; ++$k) {
								//If that toggle was just pressed, then we need to either change the field to that value,
								//or unset the field if it was already that value
								if (isset($_POST[$attributes['name']. '__'. $k])) {
									
									//Loop through, looking for the value that matches this toggle
									if (is_array($lov)) {
										$l = 0;
										foreach ($lov as $saveVal => &$dispVal) {
											if (++$l == $k) {
												//Toggle the toggle
												if ($_POST[$attributes['name']] == $saveVal) {
													$_POST[$attributes['name']] = '';
												} else {
													$_POST[$attributes['name']] = $saveVal;
												}
												
												unset($_POST[$attributes['name']. '__'. $k]);
												break 2;
											}
										}
									}
		
								}
							}
							
							unset($_POST[$attributes['name']. '__n']);
						}
					}
					
					unset($attributes);
				}
			}
			
			//Add the last HTML
			$this->frameworkData['Outer'][] = array(CMS_FRAMEWORK_HTML => $themeThings[$c]);
			
			//Check that everything that was opened was closed
			if ($count != 0 && empty($errors)) {
				$errors[] = 'This template has a mismatch between the number of sections that were opened and closed.';
			}
			
			unset($openThings);
			unset($themeThings);
			
		} else {
			$errors[] = 'The file &ldquo;'. htmlspecialchars($path. 'framework.html'). '&rdquo; does not exist.';
		}
		
		$this->frameworkLoaded = true;
		
		if (!empty($errors)) {
			$this->frameworkData = false;
			echo implode(' ', $errors), ' ';
			return $errors;
		} else {
			return true;
		}
	}
	
	public final function zAPIGetSlotLevel() {
		return $this->slotLevel == 1? adminPhrase('this Content Item') : ($this->slotLevel == 2? adminPhrase('this Layout') : adminPhrase('this Template Family'));
	}
	
	protected final function zAPIFramework(
								$section = 'Outer', $mergeFields = array(),
								$allowedSubSections = array(), $subSectionDepthLimit = 5,
								$half = false, $halfwayPoint = true, $recursing = false
							 ) {
		if (!$this->frameworkLoaded) {
			$this->zAPILoadFramework();
		}
		
		//Check the framework and framework section exist
		if (!$this->framework) {
			echo 'This plugin requires a framework, but no framework was set. ';
		}
		
		if ($this->zAPIFrameworkIsTwig) {
			//Attempt to automatically migrate a Module using the old framework system to using Twig
		
			//I'll add support for the following situations:
				//1. The Module builds its data into a massive array and only ever calls the framework function once.
				//2. The Module calls frameworkHead(), then does a loop calling framework() multiple times
		
			//Combine the sections and mergeFields arrays
			if (is_array($allowedSubSections)) {
				foreach ($allowedSubSections as $sectionName => $sectionData) {
					if (!isset($mergeFields[$sectionName])) {
						$mergeFields[$sectionName] = $sectionData;
					}
				}
			}
			
			//1.
			//If the Module is outputting its entire framework at once, we can convert to a Twig-style call straight away
			if ($half === false && $section === 'Outer') {
				$this->twigFramework($mergeFields);
			
			//2.
			//If the Module is outputting its framework at bit at a time, attempt to cache all of the data and
			//output it all at the end.
			} else {
				if (!$this->zenario2Twig) {
					$this->zenario2Twig = array();
				}
				
				//Assume that calls to frameworkHead() or the Outer section are headers/footers that are never in loops
				if ($half === 1 || $section === 'Outer') {
					foreach ($mergeFields as $fieldName => $value) {
						if (!isset($this->zenario2Twig[$fieldName])) {
							$this->zenario2Twig[$fieldName] = $value;
						}
					}
			
				//Ignore calls to frameworkFoot() and just process calls to frameworkHead()
				} elseif ($half === 2) {
					return;
			
				//Assume that calls to any other section will always be made in loops, and put them in an array of arrays
				} else {
					if (isset($this->zenario2Twig[$section][0])
					 && is_array($this->zenario2Twig[$section][0])) {
						$this->zenario2Twig[$section][] = $mergeFields;
					} else {
						$this->zenario2Twig[$section] = array($mergeFields);
					}
				}
			}
			
		} else {
			if (!$this->frameworkData) {
				echo 'The &quot;', htmlspecialchars($this->framework), '&quot; framework was not loaded successfully. ';
			
			} elseif (!is_array($this->frameworkData[$section])) {
				echo 'Section &quot;', htmlspecialchars($section), '&quot; was not found in the &quot;', htmlspecialchars($this->framework), '&quot; framework. ';
			}
			
			if (!$recursing) {
				cms_core::$frameworkFile = $this->frameworkPath. 'framework.html';
			}
			
		
			frameworkCheckArrayOfArrays($mergeFields);
		
			foreach($mergeFields as &$mergeFieldsRow) {
			
				//Start off display things by default, unless we should only display the "second half" of the section
				$displayThings = $half != 2;
				$conditionMet = true;
			
				//Loop through everything in the section
				foreach ($this->frameworkData[$section] as &$sectionContents) {
					foreach ($sectionContents as $type => &$thing) {
					
						if ($type == CMS_FRAMEWORK_SECTION) {
							//If we run into a section, call framework() to display it, but only if it is in a list of
							//sections that we should display
							$showThisSection = false;
							$checkHalfWayPoint = true;
						
							if ($subSectionDepthLimit && $halfwayPoint !== $thing) {
								//Is this section generated by another Module?
								//(This option can be set on the framework to allow some slight modification of how a Plugin works.)
								if (!empty($this->fieldInfo[$thing]['%attributes%']['source_module'])
								 && !empty($this->fieldInfo[$thing]['%attributes%']['source_method'])
								 && inc($module = $this->fieldInfo[$thing]['%attributes%']['source_module'])) {
									//If so, allow the source module/method to modify merge field values,
									//provide new merge field values, repeat the section or hide it completely.
									$method = $this->fieldInfo[$thing]['%attributes%']['source_method'];
								
									if (!empty($allowedSubSections[$thing]) && is_array($allowedSubSections[$thing])) {
										$showThisSection = $allowedSubSections[$thing];
									} else {
										$showThisSection = $mergeFieldsRow;
									}
									frameworkCheckArrayOfArrays($showThisSection);
									$showThisSection = call_user_func(array($module, $method), $thing, $showThisSection);
								
									//Disallow caching for programatically generated html
									cms_core::$slotContents[$this->slotName]['disallow_caching'] = true;
									$checkHalfWayPoint = false;
							
								} else {
									$showThisSection = !empty($allowedSubSections[$thing]);
								}
							}
						
							if ($showThisSection) {
								if ($displayThings && $conditionMet) {
									if (is_array($showThisSection)) {
										$this->zAPIFramework(
												$thing, $showThisSection,
												$allowedSubSections, $subSectionDepthLimit-1,
												false, false, true);
								
									} elseif (is_array($allowedSubSections[$thing])) {
										$this->zAPIFramework(
												$thing, $allowedSubSections[$thing],
												$allowedSubSections, $subSectionDepthLimit-1,
												false, false, true);
								
									} else {
											$this->zAPIFramework(
												$thing, $mergeFieldsRow,
												$allowedSubSections, $subSectionDepthLimit-1,
												false, false, true);
									}
								}
						
							//If we run into a section we're not allowed to display, don't display it.
							//However check to see if this is the halfway point
							} elseif ($checkHalfWayPoint && ($halfwayPoint === true || $halfwayPoint == $thing)) {
								//If half is set to one, then as soon as we get to a section that we should stop on,
								//do not display *anything* after
								if ($half == 1) {
									$displayThings = false;
							
								//And also include the opposite logic, and do not *start* displaying things until we
								//get to the stop point
								} elseif ($half == 2) {
									$displayThings = true;
								}
							}
						
							continue;
					
						//Conditions: show and hide things depending on the value of a field
						} elseif ($type == CMS_FRAMEWORK_CONDITION) {
							if (empty($thing['field'])) {
								//If a field name is not set, clear any conditions
								$conditionMet = true;
						
							} else {
								//Otherwise, attempt to look up the value of that field
								$value = null;
								$attributes = arrayKey($this->fieldInfo, $section, $thing['field']);
								$this->zAPIFrameworkField($mergeFieldsRow, false, ifNull($attributes, array('name' => $thing['field'])), $value, false);
							
								if (!isset($thing['value'])) {
									//If no specific value is mentioned, only check if the value looks set
									$conditionMet = engToBoolean($value);
								} else if(isset($thing['neq'])) {
									$conditionMet = $value != $thing['neq'];
								} else {
									//Otherwise, check that the value is exactly equal
									$conditionMet = $value == $thing['value'];
								}
							}
						}
					
						if (!$displayThings || !$conditionMet) {
							continue;
						}
					
						//In admin mode, keep track of whether there has been any output
						if (checkPriv() && !$this->frameworkOutputted) {
							switch ($type) {
								case CMS_FRAMEWORK_MERGE:
									$this->frameworkOutputted = (bool) trim($mergeFieldsRow[$thing] ?? false);
									break;
							
								case CMS_FRAMEWORK_INCLUDE:
									$this->frameworkOutputted = !empty($thing);
									break;
							
								case CMS_FRAMEWORK_CONDITION:
									//Do nothing
									break;
							
								default:
									$this->frameworkOutputted = (bool) trim($thing);
							}
						}
					
						//Display html as-is
						if ($type == CMS_FRAMEWORK_HTML) {
							echo $thing;
					
						//Use a call to phrase() to display phrases
						} elseif ($type == CMS_FRAMEWORK_PHRASE) {
							echo $this->phrase($thing);
					
						//If we run into a named mergeField, check the $mergeFields array for the HTML we need to display it
						} elseif ($type == CMS_FRAMEWORK_MERGE) {
						
							if (isset($mergeFieldsRow[$thing])) {
								echo $mergeFieldsRow[$thing];
						
							//Have a few special cases
							} elseif ($thing == 'ODD_OR_EVEN') {
								//Output 'odd' then 'even' alternately
								echo $this->zAPIOddOrEven();
						
							} elseif ($thing == 'LAST_ODD_OR_EVEN') {
								//Output the last value of "ODD_OR_EVEN"
								echo $this->zAPIOddOrEven(false);
						
							} elseif ($thing == 'FIRST') {
								//Output 'first' the first time it this merge field is used
								echo $this->zAPIFirst();
						
							} elseif ($thing == 'CONTAINER_ID') {
								//Output the container id
								echo $this->containerId;
						
							} elseif ($thing == 'TAB_ORDINAL') {
								//Output the ordinal number of the current slide
								echo (int) getRow('nested_plugins', 'slide_num', $this->eggId);
						
							} elseif ($thing == 'PLUGIN_ORDINAL') {
								//Output the ordinal number of the current egg on the current slide
								echo (int) getRow('nested_plugins', 'ord', $this->eggId);
						
							} elseif ($thing == 'PATH') {
								//Special case - treat a mergeField named PATH as the path to the framework
								echo $this->frameworkPath;
						
							} elseif ($thing == 'URL' || $thing == 'CANONICAL_URL') {
								//Output the URL
								echo htmlspecialchars(linkToItem(cms_core::$cID, cms_core::$cType, true, '', false, $thing == 'CANONICAL_URL', true));
							}
					
						//Display some text from another Plugin
						} elseif ($type == CMS_FRAMEWORK_INCLUDE) {
						
							$module = ifNull($thing['source_module'] ?? false, ($thing['module'] ?? false), ($thing['plugin'] ?? false));
							$method = ifNull($thing['source_method'] ?? false, ($thing['method'] ?? false));
						
							if ($module && $method && inc($module)) {
								echo call_user_func(array($module, $method), $mergeFieldsRow, $thing);
							
								//Disallow caching for programatically generated html
								cms_core::$slotContents[$this->slotName]['disallow_caching'] = true;
							}
					
						//Display an input field
						} elseif ($type == CMS_FRAMEWORK_INPUT) {
							//Get the attriubtes
							$attributes = $this->fieldInfo[$section][$thing];
							$value = null;
							$type = $attributes['type'] ?? false;
							$readOnly = engToBoolean($attributes['readonly'] ?? false);
						
							
							//Check for a list of values, if one has been set
							$lov = false;
							$this->zAPIFrameworkLOV($type, $attributes, $lov);
						
							//Draw the field
							if (($type == 'checkbox' && !is_array($lov))
							 || $type == 'date'
							 || $type == 'file'
							 || $type == 'hidden'
							 || $type == 'password'
							 || ($type == 'select' && !$readOnly)
							 || $type == 'submit'
							 || $type == 'text'
							 || $type == 'textarea'
							 || $type == 'toggle') {
								$this->zAPIFrameworkField($mergeFieldsRow, $type, $attributes, $value, $readOnly);
							}
						
							if (!$readOnly && $type == 'select' && !empty($attributes['null'])) {
								echo '
									<option value="">',
										htmlspecialchars($this->phrase($attributes['null'])),
									'</option>'; 
							}
						
							//Draw each value from the LOV
							if (is_array($lov)) {
								$i = 0;
								foreach ($lov as $saveVal => &$dispVal) {
									if ($type == 'select' && !$readOnly) {
										echo '
											<option value="', htmlspecialchars($saveVal), '"', $saveVal == $value? ' selected="selected"' : '', '>',
												htmlspecialchars($dispVal),
											'</option>'; 
									} else {
										$this->zAPIFrameworkField($mergeFieldsRow, $type, $attributes, $value, $readOnly, ++$i, $saveVal, $dispVal);
									}
								}
							
								//Checkboxes/Toggles will need a hidden field to could how many there were
								if ($lov && ($type == 'checkbox' || $type == 'toggle')) {
									echo '
										<input type="hidden" name="', htmlspecialchars($attributes['name']), '__n" value="', $i, '"/>'; 
								}
							}
						
							//Close the select tag
							if ($type == 'select') {
								echo '
									</select>';
							}
						
							unset($attributes);
							unset($value);
							unset($lov);
						}
					}
				}
			}
			
			if (!$recursing) {
				cms_core::$frameworkFile = '';
			}
		}
	}
	
	private final function zAPIFrameworkField(&$mergeFieldsRow, $type, $attributes, &$value, $readonly, $i = false, $saveVal = false, $dispVal = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	private final function zAPIFrameworkLOV($type, &$attributes, &$lov) {
		//Load the List of Values for a field
		if ($type == 'checkbox' || $type == 'radio' || $type == 'select' || $type == 'toggle') {
			if (!empty($attributes['source_module']) && !empty($attributes['source_method']) && inc($attributes['source_module'])) {
			
				//Old "source_param_" logic, still included for backwards compatability
				$i = 0;
				$args = array();
				while (isset($attributes['source_param_'. ++$i])) {
					$args[] = $attributes['source_param_'. $i];
				}
				
				if (!empty($args)) {
					$lov = call_user_func_array(array($attributes['source_module'], $attributes['source_method']), $args);
				} else {
					//New logic where the whole $attributes array is passed in
					$lov = call_user_func(array($attributes['source_module'], $attributes['source_method']), $attributes);
				}
				
				//Disallow caching for programatically generated lists of values
				cms_core::$slotContents[$this->slotName]['disallow_caching'] = true;
			
			//Generate a LOV by calling one of the Plugin's own methods non-statically
			} elseif (!empty($attributes['source_method'])) {
				if (!empty($args)) {
					$lov = call_user_func_array(array($this, $attributes['source_method']), $args);
				} else {
					//New logic where the whole $attributes array is passed in
					$lov = call_user_func(array($this, $attributes['source_method']), $attributes);
				}
				
			
			} elseif (isset($attributes['value_1'])) {
				
				$i = 0;
				$lov = array();
				while (isset($attributes['value_'. ++$i])) {
					$lov[$attributes['value_'. $i]] =
						$this->phrase(
							isset($attributes['display_'. $i])?
								$attributes['display_'. $i]
							:	$attributes['value_'. $i]
						);
				}
			}
		}
	}
	
	protected final function zAPIListFrameworkFields($section, &$fields, &$allowedSubSections = array(), $limit = 5) {
		if (!$this->frameworkLoaded) {
			$this->zAPILoadFramework();
		}
		
		if (!--$limit || !$this->checkFrameworkSectionExists($section)) {
			return;
		}
		
		//Get all of the fields in a sub section, checking sub sections too
		foreach ($this->frameworkData[$section] as &$sectionContents) {
			foreach ($sectionContents as $type => &$thing) {
				if ($type == CMS_FRAMEWORK_SECTION) {
					if (!empty($allowedSubSections[$thing])) {
						$this->zAPIListFrameworkFields($thing, $fields, $allowedSubSections, $limit);
					}
				}
			}
		}
		
		if (is_array($this->fieldInfo[$section])) {
			foreach ($this->fieldInfo[$section] as $name => &$field) {
				$fields[$name] = $field;
			}
		}
	}
	
	//This is a utility function to deal with the standard image resize options on tuix plugin settings.
	protected function showHideImageOptions(&$fields, $values, $tab, $hidden = false, $fieldPrefix = '', $hasCanvas = true, $sameLineLabel = 'Size (width × height):') {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	/**
	 * Utility function to get system default icons for document extensions
	 * @param String $ext
	 * @param Array $outArray
	 */
	protected function getStyledExtensionIcon($ext, &$outArray) {	
		$styledExtensions = array(
				'avi' => 'avi',
				'doc' => 'doc',
				'docx' => 'doc',
				'jpg' => 'jpg',
				'jpeg' => 'jpg',
				'jpe' => 'jpg',
				'gz' => 'gz',
				'pdf' => 'pdf',
				'ppt' => 'ppt',
				'rtf' => 'rtf',
				'txt' => 'txt',
				'xls' => 'xls',
				'xlsx' => 'xls',
				'zip' => 'zip');
		
		if (isset($styledExtensions[$ext])) {
			$outArray['Icon'] = $styledExtensions[$ext] . '_icon.jpg';
			$outArray['Icon_Class'] = $styledExtensions[$ext] . '_icon';
		} else {
			$outArray['Icon'] = 'unknown_icon.jpg';
			$outArray['Icon_Class'] = 'unknown_icon';
		}
	}
	
	
	
	  ////////////
	 //  Misc  //
	////////////
	
	//This is intended as a replacement for the old useThisClassInstead() functionality
	//Rather than put all of your Admin Box/Organizer functionality in one module,
	//this lets you divvy it up into different subclasses.
	private $zAPIrunSubClassSafetyCatch = false;
	private $zAPIMainClass;
	private $zAPISubClasses = array();
	
	protected final function runSubClass($filePath, $type = false, $path = false) {
		
		//Add a check to stop subclasses calling themsevles again, which would cause an
		//infinite loop!
		if ($this->zAPIrunSubClassSafetyCatch) {
			return false;
		}
		
		//Try to cache these, so multiple calls to the same subclass use the same instance
		$codeName = $filePath. '`'. $type. '`'. $path;
		
		if (isset($this->zAPISubClasses[$codeName])) {
			return $this->zAPISubClasses[$codeName];
		
		} elseif ($className = includeModuleSubclass($filePath, $type, $path)) {
			$this->zAPISubClasses[$codeName] = new $className;
			$this->zAPISubClasses[$codeName]->zAPIrunSubClassSafetyCatch = true;
			$this->zAPISubClasses[$codeName]->setInstanceVariables(array(
				$this->cID, $this->cType, $this->cVersion, $this->slotName,
				$this->instanceName, $this->instanceId,
				$this->moduleClassName, $this->moduleClassNameForPhrases,
				$this->moduleId,
				$this->defaultFramework, $this->framework,
				$this->cssClass,
				$this->slotLevel, $this->isVersionControlled),
				$this->eggId, $this->slideId,
				$this->zAPISettings, $this->frameworkPath, $this);
			
			return $this->zAPISubClasses[$codeName];
		
		} else {
			return $this->zAPISubClasses[$codeName] = false;
		}
	}
	
	
	
	
	  ///////////////////////////////////////////
	 //  Old, deprecated Framework Functions  //
	///////////////////////////////////////////
	
	
	protected final function checkFrameworkSectionExists($section = 'Outer') {
		if (!$this->frameworkLoaded) {
			$this->zAPILoadFramework();
		}
		
		return !(empty($this->frameworkData[$section]) || !is_array($this->frameworkData[$section]));
	}
	
	protected final function framework(
								$section = 'Outer', $mergeFields = array(),
								$allowedSubSections = array(), $subSectionDepthLimit = 5,
								$half = false, $halfwayPoint = true
							 ) {
						
		$this->zAPIFramework(
				$section, $mergeFields, $allowedSubSections, $subSectionDepthLimit, $half, $halfwayPoint);
	}

	protected final function frameworkHead(
								$section = 'Outer',
								$halfwayPoint = true,
								$mergeFields = array(),
								$allowedSubSections = array(), $subSectionDepthLimit = 5) {
		$this->zAPIFramework(
				$section, $mergeFields, $allowedSubSections, $subSectionDepthLimit, 1, $halfwayPoint);
	}
	
	protected final function frameworkFields($section = 'Outer', $allowedSubSections = array(), $subSectionDepthLimit = 5) {
		$fields = array();
		$this->zAPIListFrameworkFields($section, $fields, $allowedSubSections, $subSectionDepthLimit);
		return $fields;
	}
	
	protected final function checkRequiredField(&$field) {
		$name = $field['name'] ?? false;
		if (engToBoolean($field['required'] ?? false) && !($_POST[$name] ?? false) ) {
			if (($field['type'] ?? false) == 'checkbox'){
				
				$sub = $name . '__';
				$len = strlen($sub);
				$match = false;
				foreach ($_POST as $K=>$var){
					if (substr($K, 0, $len) == $sub && is_numeric(substr($K, $len))) {
						$match = true;
						break;
					}
				}
				return $match;
			}
			return false;
		} else {
			return true;
		}
	}

	protected final function frameworkFoot(
								$section = 'Outer',
								$halfwayPoint = true,
								$mergeFields = array(),
								$allowedSubSections = array(), $subSectionDepthLimit = 5) {
		$this->zAPIFramework(
				$section, $mergeFields, $allowedSubSections, $subSectionDepthLimit, 2, $halfwayPoint);
	}
	
	protected final function loadFramework() {
		$this->zAPILoadFramework();
	}
	
	protected final function resetOddOrEven() {
		$this->zAPIOddOrEven = 'odd';
	}
}