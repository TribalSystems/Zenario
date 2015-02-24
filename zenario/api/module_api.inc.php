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
	protected $tabId;
	
	
	
	
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
	
	protected final function cache($methodName, $expiryTimeInSeconds = 600, $request = '') {
		return require funIncPath(__FILE__, __FUNCTION__);
	}

	protected final function checkPostIsMine() {
		return !empty($_POST) && (empty($_POST['containerId']) || $_POST['containerId'] == $this->containerId);
	}

	protected final function clearCache($methodName, $request = '', $useLike = false) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected final function getCIDAndCTypeFromSetting(&$cID, &$cType, $setting, $getLanguageEquivalent = true) {
		if (getCIDAndCTypeFromTagId($cID, $cType, $this->setting($setting))) {
			if ($getLanguageEquivalent) {
				langEquivalentItem($cID, $cType);
			}
			
			return (checkPriv() || getPublishedVersion($cID, $cType));
		}
		
		return false;
	}
	
	protected final function setting($name) {
		return isset($this->tApiSettings[$name])? $this->tApiSettings[$name] : false;
	}
	
	
	
	
	  ///////////////////////////
	 //  Framework Functions  //
	///////////////////////////
	
	//New Twig version of zenario frameworks
	protected final function twigFramework($vars = array(), $return = false, $customFilePath = false) {
		
		//Ensure that any cache files are created as 0666, so they can be deleted
		$oldMask = umask(0);
		$return = '';
		
		if ($customFilePath) {
			$path = dirname($customFilePath). '/';
			$twigFile = basename($customFilePath);
		
		} elseif ($this->framework && $this->frameworkPath) {
			$path = CMS_ROOT. $this->frameworkPath;
			$twigFile = 'framework.twig.html';
			
		} else {
			return $return;
		}
		
		cms_core::$frameworkFile = $path. $twigFile;
		
		if (is_file(cms_core::$frameworkFile)) {
			
			$loader = new Twig_Loader_Filesystem($path);
			$twig = new Twig_Environment($loader, array(
				'cache' => CMS_ROOT. 'cache/frameworks/',
				'autoescape' => false,
				'auto_reload' => true
			));
			
			$twig->addExtension(new Twig_Extensions_Extension_I18n());
			cms_core::$moduleClassNameForPhrases = $this->moduleClassNameForPhrases;
			
			
			//Add some default variables if they are not set
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
			
			
			if ($return) {
				$return = $twig->render($twigFile, $vars);
			} else {
				echo $twig->render($twigFile, $vars);
			}
		
		} else {
			echo 'This plugin requires a framework, but no framework was set. ';
		}
		
		cms_core::$frameworkFile = '';
		
		//Restore the default file permission settings
		umask($oldMask);
		return $return;
	}
	
	//HTML escape something for the old framework system, but leave it alone if this is Twig
	protected final function escapeIfOldFramework($text) {
		if (!$this->frameworkLoaded) {
			$this->tApiLoadFramework();
		}
		
		if ($this->frameworkIsTwig) {
			return $text;
		} else {
			return htmlspecialchars($text);
		}
	}
	
	protected final function closeForm() {
		return '
				</form>';
	}
	
	public final function tAPITwigPhrase($code) {
		return $this->phrase($code);
	}
	
	protected final function openForm($onSubmit = '', $extraAttributes = '', $action = false, $scrollToTopOfSlot = false, $fadeOutAndIn = true, $usePost = true) {
		return '
				<form method="'. ($usePost? 'post' : 'get'). '" '. $extraAttributes. '
				  onsubmit="'. htmlspecialchars($onSubmit). ' return zenario.formSubmit(this, '. ($scrollToTopOfSlot? 1 : 0). ', '. ($fadeOutAndIn? 1 : 0). ', \''. jsEscape($this->slotName). '\');"
				  action="'. htmlspecialchars(ifNull($action, linkToItem(cms_core::$cID, cms_core::$cType, false, '', cms_core::$alias, true))). '">
					'. $this->remember('cID', $this->cID). '
					'. $this->remember('tab', $this->tabId). '
					'. $this->remember('cType', $this->cType). '
					'. $this->remember('slotName', $this->slotName). '
					'. $this->remember('instanceId', $this->instanceId). '
					'. $this->remember('containerId', $this->containerId);
	}
	
	protected final function pagination($paginationStyleSettingName, $currentPage, $pages, &$html, &$links = array()) {
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
			$this->eggId, $this->tabId);
		$class->$method($currentPage, $pages, $html, $links);
	}
	
	protected final function phrase($code, $replace = array()) {
		return phrase($code, $replace, $this->moduleClassNameForPhrases, cms_core::$langId);
	}
	
	protected final function refreshPluginSlotAnchor($requests = '', $scrollToTopOfSlot = true, $fadeOutAndIn = true) {
		return
			$this->linkToItemAnchor($this->cID, $this->cType, $fullPath = false, '&slotName='. $this->slotName. ($this->tabId? '&tab='. $this->tabId : ''). urlRequest($requests)).
			' onclick="'.
				$this->refreshPluginSlotJS($requests, $scrollToTopOfSlot, $fadeOutAndIn).
				' return false;"';
	}
	
	protected final function refreshPluginSlotAnchorAndJS($requests = '', $scrollToTopOfSlot = true, $fadeOutAndIn = true) {
		return array($this->refreshPluginSlotAnchor($requests, $scrollToTopOfSlot), $this->refreshPluginSlotJS($requests, $scrollToTopOfSlot, $fadeOutAndIn));
	}
	
	protected final function refreshPluginSlotJS($requests = '', $scrollToTopOfSlot = true, $fadeOutAndIn = true) {
		return 
			$this->moduleClassName.'.refreshPluginSlot('.
				'\''. $this->slotName. '\', '.
				'\''. jsOnClickEscape(urlRequest($requests)). '\', '.
				($scrollToTopOfSlot? 1 : 0). ', '.
				($fadeOutAndIn? 1 : 0). ');';
	}
	
	protected final function remember($name, $value = false, $htmlId = false, $type = 'hidden') {
		
		if ($value === false) {
			$value = request($name);
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
	
	
	
	
	  ////////////////////////////////
	 //  Initialization Functions  //
	////////////////////////////////
	
	protected final function allowCaching(
		$atAll, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true
	) {
		$vs = &cms_core::$slotContents[$this->slotName. ($this->eggId? '-'. $this->eggId : '')]['cache_if'];
		
		foreach (array('a' => $atAll, 'u' => $ifUserLoggedIn, 'g' => $ifGetSet, 'p' => $ifPostSet, 's' => $ifSessionSet, 'c' => $ifCookieSet) as $if => $set) {
			if (!isset($vs[$if])) {
				$vs[$if] = true;
			}
			$vs[$if] = $vs[$if] && $vs['a'] && $set;
		}
	}
	
	protected final function clearCacheBy(
		$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false
	) {
		$vs = &cms_core::$slotContents[$this->slotName. ($this->eggId? '-'. $this->eggId : '')]['clear_cache_by'];
		
		foreach (array('content' => $clearByContent, 'menu' => $clearByMenu, 'user' => $clearByUser, 'file' => $clearByFile, 'module' => $clearByModuleData) as $if => $set) {
			if (!isset($vs[$if])) {
				$vs[$if] = false;
			}
			$vs[$if] = $vs[$if] || $set;
		}
	}
	
	protected final function callScript($className, $scriptName /*[, $arg1 [, $arg2 [, ... ]]]*/) {
		$args = func_get_args();
		$this->tApiCallScriptWhenLoaded(false, $args);
	}
	
	protected final function callScriptBeforeAJAXReload($className, $scriptName /*[, $arg1 [, $arg2 [, ... ]]]*/) {
		$args = func_get_args();
		$this->tApiCallScriptWhenLoaded(true, $args);
	}
	
	protected final function callScriptAdvanced($beforeAJAXReload, $className, $scriptName /*[, $arg1 [, $arg2 [, ... ]]]*/) {
		$args = func_get_args();
		array_splice($args, 0, 1);
		$this->tApiCallScriptWhenLoaded($beforeAJAXReload, $args);
	}
	
	protected final function captcha() {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected final function checkCaptcha() {
		return require funIncPath(__FILE__, __FUNCTION__);
	}

	protected final function forcePageReload($reload = true) {
		$this->tApiForcePageReload($reload);
	}

	protected final function headerRedirect($link) {
		$this->tApiHeaderRedirect($link);
	}

	protected final function markSlotAsBeingEdited($beingEdited = true) {
		$this->tApiMarkSlotAsBeingEdited($beingEdited);
	}

	protected final function showInFloatingBox($showInFloatingBox = true) {
		$this->cmsApiShowInFloatingBox($showInFloatingBox);
	}

	protected final function scrollToTopOfSlot($scrollToTop = true) {
		$this->cmsApiScrollToTopOfSlot($scrollToTop);
	}

	protected final function registerGetRequest($request, $defaultValue = '') {
		cms_core::$importantGetRequests[$request] = $defaultValue;
	}
	
	protected final function setPageTitle($title) {
		cms_core::$slotContents[$this->slotName]['page_title'] = $title;
		cms_core::$pageTitle = $title;
	}

	protected final function setMenuTitle($title) {
		cms_core::$slotContents[$this->slotName]['menu_title'] = $title;
		cms_core::$menuTitle = $title;
	}

	protected final function showInMenuMode($shownInMenuMode = true) {
		$this->tApiShowInMenuMode($shownInMenuMode);
	}

	protected final function showInEditMode($shownInEditMode = true) {
		$this->tApiShowInEditMode($shownInEditMode);
	}
	//Old name, left in for backwards compatability reasons
	protected final function showReusablePluginInEditMode($shownInEditMode = true) {
		$this->tApiShowInEditMode($shownInEditMode);
	}
	
	
	
	
	  ///////////////////////////////
	 //  Link/Path/URL Functions  //
	///////////////////////////////
	
	protected final function linkToItem(
		$cID, $cType = 'html', $fullPath = false, $request = '', $alias = false,
		$autoAddImportantRequests = false, $useAliasInAdminMode = false,
		$httpHost = false
	) {
		return linkToItem($cID, $cType, $fullPath, $request, $alias, $autoAddImportantRequests, $useAliasInAdminMode, $httpHost);
	}
	
	protected final function linkToItemAnchor(
		$cID, $cType = 'html', $fullPath = false, $request = '', $alias = false,
		$autoAddImportantRequests = false, $useAliasInAdminMode = false,
		$httpHost = false
	) {
		return ' href="'. htmlspecialchars(linkToItem($cID, $cType, $fullPath, $request, $alias, $autoAddImportantRequests, $useAliasInAdminMode, $httpHost)). '"';
	}
	
	protected final function linkToItemAnchorAndJS(
		$cID, $cType = 'html', $fullPath = false, $request = '', $alias = false,
		$autoAddImportantRequests = false, $useAliasInAdminMode = false,
		$httpHost = false
	) {
		return array($this->linkToItemAnchor($cID, $cType, $fullPath, $request, $alias, $autoAddImportantRequests, $useAliasInAdminMode, $httpHost), $this->linkToItemJS($cID, $cType, $request));
	}
	
	protected final function linkToItemJS($cID, $cType = 'html', $request = '') {
		return $this->moduleClassName. '.goToItem(\''. jsOnClickEscape($cID). '\', \''. jsOnClickEscape($cType). '\', \''. jsOnClickEscape($request). '\');';
	}
	
	protected final function moduleAJAXURL($requests = '') {
		return 'zenario/ajax.php?moduleClassName='. $this->moduleClassName. '&method_call=handleAJAX'. urlRequest($requests);
	}
	
	protected final function moduleDir($subDir = '') {
		return moduleDir($this->moduleClassName, $subDir);
	}
	
	protected final function pluginAJAXLink($requests = '') {
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
	
	protected final function showFileLink($requests = '') {
		return
			httpOrHttps(). httpHost(). SUBDIRECTORY.
			'zenario/ajax.php?moduleClassName='. $this->moduleClassName. '&method_call=showFile'.
			urlRequest($requests);
	}
	
	protected final function showFloatingBoxLink($requests = '') {
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
	
	protected final function showIframeLink($requests = '', $hideLayout = false) {
		return
			$this->linkToItem($this->cID, $this->cType, false, 
			  (checkPriv()?
				'&cVersion='. $this->cVersion
			   : '').
				'&method_call=showIframe'.
				'&instanceId='. $this->instanceId.
				'&slotName='. $this->slotName.
				'&eggId='. $this->eggId.
				($hideLayout? '&hideLayout=1' : '').
				urlRequest($requests),
			cms_core::$alias);
	}
	
	protected final function showImageLink($requests) {
		return
			httpOrHttps(). httpHost(). SUBDIRECTORY.
			'zenario/ajax.php?moduleClassName='. $this->moduleClassName. '&method_call=showImage'.
			urlRequest($requests);
	}
	
	protected final function showRSSLink($allowFriendlyURL = false, $overwriteFriendlyURL = true) {
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
	 		//Nested Plugins on tabs other than the first tab should not be able to set the RSS link
		 || !empty($_REQUEST['tab'])
		 || !empty($_REQUEST['tab_no'])
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
	
	protected final function showStandalonePageLink($requests) {
		return
			httpOrHttps(). httpHost(). SUBDIRECTORY.
			'zenario/ajax.php?moduleClassName='. $this->moduleClassName. '&method_call=showStandalonePage'.
			urlRequest($requests);
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
			imageLink($width, $height, $url, $image_id, $widthLimit, $heightLimit, $mode = 'resize', $offset = 0, $useCacheDir = false);
			imageLink($widthR, $heightR, $urlR, $image_id, $widthLimit = 700, $heightLimit = 200, $mode = 'resize', $offset = 0, $useCacheDir = false);
	
			$snippet_field = '
			<p style="text-align: center;">
				<a>
					<img src="'. htmlspecialchars(absCMSDirURL(). $urlR). '"
						width="'. $widthR. '" height="'. $heightR. '" style="border: 1px solid black;"/>
				</a>
			</p>';
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
	public $tApiSettings;

	//Disable AJAX Relaod
	private $tApiForcePageReloadVar = false;
	public final function checkForcePageReloadVar() {
		return $this->tApiForcePageReloadVar;
	}
	protected final function tApiForcePageReload($reload) {
		$this->tApiForcePageReloadVar = $reload;
	}
	
	//Reload to a different location
	private $tApiHeaderRedirectLocation = false;
	protected final function tApiHeaderRedirect($location) {
		$this->tApiHeaderRedirectLocation = $location;
	}
	public final function checkHeaderRedirectLocation() {
		return $this->tApiHeaderRedirectLocation;
	}

	//How to display after an AJAX reload
	private $tApiShowInFloatingBox = false;
	public final function checkShowInFloatingBoxVar() {
		return $this->tApiShowInFloatingBox;
	}
	protected final function cmsApiShowInFloatingBox($showInFloatingBox) {
		$this->tApiShowInFloatingBox = $showInFloatingBox;
	}
	
	private $tApiScrollToTop = null;
	public final function checkScrollToTopVar() {
		return $this->tApiScrollToTop;
	}
	protected final function cmsApiScrollToTopOfSlot($scrollToTop) {
		$this->tApiScrollToTop = $scrollToTop;
	}

	//A list of JavaScript functions to run
	private $tApiScripts = array();
	private $tApiScriptsBefore = array();
	protected final function tApiCallScriptWhenLoaded($beforeAJAXReload, &$script) {
		if ($beforeAJAXReload && request('method_call') == 'refreshPlugin') {
			$this->tApiScriptsBefore[] = $script;
		} else {
			$this->tApiScripts[] = $script;
		}
	}
	public final function tApiCheckRequestedScripts(&$scripts, &$scriptsBefore) {
		$scripts = $this->tApiScripts;
		$scriptsBefore = $this->tApiScriptsBefore;
	}
	public final function tApiAddRequestedScripts() {
		if (!empty($this->tApiScripts)) {
			echo '
				<script type="text/javascript">';
				
					foreach ($this->tApiScripts as &$script) {
						echo '
					zenario.callScript("', jsEscape(json_encode($script)), '");';
					}
					
			echo '
				</script>';
		}
	}
	
	//Mark this Plugin as Menu-related
	private $tApiShownInMenuMode;
	public final function shownInMenuMode() {
		return $this->tApiShownInMenuMode;
	}
	protected final function tApiShowInMenuMode($shownInMenuMode) {
		$this->tApiShownInMenuMode = $shownInMenuMode;
	}
	
	//Mark this Library plugin as shown in Edit Mode
	private $tApiShownInEditMode;
	public final function shownInEditMode() {
		return $this->tApiShownInEditMode;
	}
	protected final function tApiShowInEditMode($shownInEditMode) {
		$this->tApiShownInEditMode = $shownInEditMode;
	}
	
	//Mark this Plugin as being editing
	private $tApiSlotBeingEdited;
	public final function beingEdited() {
		return $this->tApiSlotBeingEdited;
	}
	protected final function tApiMarkSlotAsBeingEdited($beingEdited) {
		$this->tApiSlotBeingEdited = $beingEdited;
	}
	
	public final function tApiGetTabId() {
		return $this->tabId;
	}

	//Framework and Swatch for this plugin.
	protected $framework;
	protected $cssClass;
	
	protected $defaultFramework;
	
	private $frameworkPath;
	private $frameworkData;
	private $frameworkLoaded = false;
	private $zenario2Twig = array();
	protected $frameworkIsTwig = false;
	protected $frameworkOutputted = false;
	
	private $tApiFirst = true;
	private final function tApiFirst() {
		if ($this->tApiFirst) {
			$this->tApiFirst = false;
			return 'first';
		} else {
			return '';
		}
	}
	
	private $tApiOddOrEven = 'even';
	private final function tApiOddOrEven($change = true) {
		if ($change) {
			$this->tApiOddOrEven = $this->tApiOddOrEven == 'odd'? 'even' : 'odd';
		}
		return $this->tApiOddOrEven;
	}
	
	
	public final function tApiGetCachableVars(&$a) {
		$a = array();
		$a[0] = $this->framework;
		$a[1] = $this->tApiScripts;
		$a[2] = $this->tApiScriptsBefore;
		$a[3] = $this->tabId;
		$a[4] = $this->cssClass;
		$a[5] = $this->eggId;
		$a[6] = $this->tabId;
	}
	
	public final function tApiSetCachableVars(&$a) {
		$this->framework = $a[0];
		$this->tApiScripts = $a[1];
		$this->tApiScriptsBefore = $a[2];
		$this->tabId = $a[3];
		$this->cssClass = $a[4];
		$this->eggId = $a[5];
		$this->tabId = $a[6];
	}
	
	
	public final function setInstanceVariables($locationAndInstanceDetails, $nest = 0, $tab = 0) {
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
		$this->eggId = (int) $nest;
		$this->tabId = (int) $tab;
		$this->inLibrary = $this->isVersionControlled;
		$this->isWireframe = $this->isVersionControlled; //For backwards compatability
		
		$this->slotName = preg_replace('/[^\w-]/', '', $this->slotName);
		$this->defaultFramework = preg_replace('/[^\w-]/', '', $this->defaultFramework);
		$this->framework = preg_replace('/[^\w-]/', '', $this->framework);
		
		if ($this->slotName) {
			//Generate a container id for the plugin
			$this->containerId = 'plgslt_'. $this->slotName;
			
			if ($this->eggId) {
				$this->containerId .= '-'. $this->eggId;
			}
		}
	}
	
	public final function setInstance($locationAndInstanceDetails = false, $nest = 0, $tab = 0) {
		$this->setInstanceVariables($locationAndInstanceDetails, $nest, $tab);
		
		//Set up Swatches, etc. for front-end modules
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
			$this->tApiSettings = array();
			foreach (getModuleInheritances($this->moduleClassName, 'inherit_settings') as $className) {
				$sql = "
					SELECT `name`, default_value
					FROM ". DB_NAME_PREFIX. "plugin_setting_defs
					WHERE module_class_name = '". sqlEscape($className). "'";
				$result = sqlQuery($sql);
				
				while($row = sqlFetchAssoc($result)) {
					if (!isset($this->tApiSettings[$row['name']])) {
						$this->tApiSettings[$row['name']] = $row['default_value'];
					}
				}
			}
			
			
			//Now look up the settings that have been set, and overwrite the defaults
			$sql = "
				SELECT `name`, `value`
				FROM ". DB_NAME_PREFIX. "plugin_settings
				WHERE instance_id = ". (int) $this->instanceId. "
				  AND nest = ". (int) $nest;
			
			//Don't load phrase overrides for Reusable Plugins
			//(Phrase overrides will begin with a %)
			if (!$this->isVersionControlled) {
				$sql .= "
				  AND name NOT LIKE '\%%'";
			}
			
			$result = sqlQuery($sql);
			
			while($row = sqlFetchAssoc($result)) {
				$this->tApiSettings[$row['name']] = $row['value'];
			}
		}
	}
	
	
	//Display a Slot and its wrappers
	public final function show($includeAdminControlsIfInAdminMode = true, $showPlaceholderMethod = 'showSlot') {
		
		if ($includeAdminControlsIfInAdminMode) {
			$this->startIncludeAdminControls();
		}
		
		echo $this->startInner();
			if (!$this->eggId) {
				cms_core::preSlot($this->slotName, $showPlaceholderMethod);
			}
				
				//Display the plugin, if it has been set up.
				if (!$this->instanceId) {
					echo showPluginError($this->slotName);
				} else {
					$this->$showPlaceholderMethod();
					
					if ($showPlaceholderMethod == 'showSlot') {
						$this->afterShowSlot();
					}
				}
				
			if (!$this->eggId) {
				cms_core::postSlot($this->slotName, $showPlaceholderMethod);
			}
		echo $this->endInner();
	}
	
	//This method is part of a hack to help automatically migrate a Module using the old framework system to using Twig.
	//Any calls to frameworkHead() or to framework() for sections other than "Outer" are stored in the $this->zenario2Twig array.
	//After showSlot is finished, we'll output the $this->zenario2Twig arrat using Twig.
	public final function afterShowSlot() {
		if ($this->frameworkIsTwig
		 && !empty($this->zenario2Twig)) {
			$this->twigFramework($this->zenario2Twig);
			$this->zenario2Twig = array();
		}
	}
	
	//Display the starting wrapper of a slot
	public final function start() {
		//Put a section around the slot and the slot controls in admin mode.
		//This lets us adjust the look of the slot and the slot controls using CSS.
		//(Note that IE 8 needs a convoluted hack to use a section tag.)
		if (checkPriv()) {
			if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8') === false) {
				echo '
					<section';
			} else {
				echo '
					<div';
			}
			
			echo ' id="', $this->containerId, '-wrap" class="zenario_slotOuter ', $this->instanceId? 'zenario_slotWithContents' : 'zenario_slotWithNoContents', '">';
		}
	}
	
	//Display the admin controls for a slot
	private final function startIncludeAdminControls() {
		if (checkPriv()) {
			require funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	//Put a div around the slot, so we can reload the contents
	public final function startInner() {
		return '
					<div id="'. $this->containerId. '" class="zenario_slot '. $this->cssClass. '">';
	}
	
	//Close the admin controls for a slot.
	public final function endInner() {
		
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
		//Display the HTML at the end of a slot when in admin mode
		if (checkPriv()) {
			if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8') === false) {
				echo '
					</section>';
			
			} else {
				//Here is the aforementioned hack to get section tags working in IE 8:
				echo '
					</div>
					<script>
						(function(id) {
							var s = document.createElement("section"),
								d = document.getElementById(id);
							d.parentNode.replaceChild(s, d);
							s.id = d.id;
							s.className = d.className;
							s.innerHTML = d.innerHTML;
						})("', $this->containerId, '-wrap");
				   </script>';
			}
		}
	}
	
	protected final function tApiLoadFramework() {
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
			$this->frameworkIsTwig = true;
		
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
						
						//For file uploads - if there has been a submission, move it to the cache/uploads directory and
						//place the path in the $_POST vaable.
						} elseif ($attributes['type'] == 'file' && isset($_POST['cID'])) {
							if (!empty($_FILES[$attributes['name']]) && is_uploaded_file($_FILES[$attributes['name']]['tmp_name'])) {
								if (cleanDownloads()) {
									$randomDir = createRandomDir(30, 'uploads');
									$newName = $randomDir. preg_replace('/\.\./', '.', preg_replace('/[^\w\.-]/', '', $_FILES[$attributes['name']]['name']));
									
									//Change the extension
									$newName = $newName. '.upload';
									
									if (move_uploaded_file($_FILES[$attributes['name']]['tmp_name'], CMS_ROOT. $newName)) {
										chmod(CMS_ROOT. $newName, 0666);
										$_POST[$attributes['name']] =
										$_REQUEST[$attributes['name']] = $newName;
									}
								}
							}
							
							//Stop the user trying to trick the CMS into submitting a different file in a different location
							if (!empty($_POST[$attributes['name']])) {
								if (strpos($_POST[$attributes['name']], '..') !== false
								 || !preg_match('@^cache/uploads/\w+/[\w\.-]+\.upload$@', $_POST[$attributes['name']])) {
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
									++$k;
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
							$this->tApiFrameworkLOV($attributes['type'], $attributes, $lov);
							
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
	
	public final function tApiGetSlotLevel() {
		return $this->slotLevel == 1? adminPhrase('this Content Item') : ($this->slotLevel == 2? adminPhrase('this Layout') : adminPhrase('this Template Family'));
	}
	
	protected final function tApiFramework(
								$section = 'Outer', $mergeFields = array(),
								$allowedSubSections = array(), $subSectionDepthLimit = 5,
								$half = false, $halfwayPoint = true, $recursing = false
							 ) {
		if (!$this->frameworkLoaded) {
			$this->tApiLoadFramework();
		}
		
		//Check the framework and framework section exist
		if (!$this->framework) {
			echo 'This plugin requires a framework, but no framework was set. ';
		}
		
		if ($this->frameworkIsTwig) {
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
										$this->tApiFramework(
												$thing, $showThisSection,
												$allowedSubSections, $subSectionDepthLimit-1,
												false, false, true);
								
									} elseif (is_array($allowedSubSections[$thing])) {
										$this->tApiFramework(
												$thing, $allowedSubSections[$thing],
												$allowedSubSections, $subSectionDepthLimit-1,
												false, false, true);
								
									} else {
											$this->tApiFramework(
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
								$this->tApiFrameworkField($mergeFieldsRow, false, ifNull($attributes, array('name' => $thing['field'])), $value, false);
							
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
									$this->frameworkOutputted = (bool) trim(arrayKey($mergeFieldsRow, $thing));
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
								echo $this->tApiOddOrEven();
						
							} elseif ($thing == 'LAST_ODD_OR_EVEN') {
								//Output the last value of "ODD_OR_EVEN"
								echo $this->tApiOddOrEven(false);
						
							} elseif ($thing == 'FIRST') {
								//Output 'first' the first time it this merge field is used
								echo $this->tApiFirst();
						
							} elseif ($thing == 'CONTAINER_ID') {
								//Output the container id
								echo $this->containerId;
						
							} elseif ($thing == 'TAB_ORDINAL') {
								//Output the ordinal number of the current tab
								echo (int) getRow('nested_plugins', 'tab', $this->eggId);
						
							} elseif ($thing == 'PLUGIN_ORDINAL') {
								//Output the ordinal number of the current egg on the current tab
								echo (int) getRow('nested_plugins', 'ord', $this->eggId);
						
							} elseif ($thing == 'PATH') {
								//Special case - treat a mergeField named PATH as the path to the framework
								echo $this->frameworkPath;
						
							} elseif ($thing == 'URL' || $thing == 'CANONICAL_URL') {
								//Output the URL
								echo htmlspecialchars(linkToItem(cms_core::$cID, cms_core::$cType, true, '', cms_core::$alias, $thing == 'CANONICAL_URL', true, primaryDomain()));
							}
					
						//Display some text from another Plugin
						} elseif ($type == CMS_FRAMEWORK_INCLUDE) {
						
							$module = ifNull(arrayKey($thing, 'source_module'), arrayKey($thing, 'module'), arrayKey($thing, 'plugin'));
							$method = ifNull(arrayKey($thing, 'source_method'), arrayKey($thing, 'method'));
						
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
							$type = arrayKey($attributes, 'type');
							$readOnly = engToBooleanArray($attributes, 'read_only');
						
							
							//Check for a list of values, if one has been set
							$lov = false;
							$this->tApiFrameworkLOV($type, $attributes, $lov);
						
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
								$this->tApiFrameworkField($mergeFieldsRow, $type, $attributes, $value, $readOnly);
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
										$this->tApiFrameworkField($mergeFieldsRow, $type, $attributes, $value, $readOnly, ++$i, $saveVal, $dispVal);
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
	
	private final function tApiFrameworkField(&$mergeFieldsRow, $type, $attributes, &$value, $readonly, $i = false, $saveVal = false, $dispVal = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	private final function tApiFrameworkLOV($type, &$attributes, &$lov) {
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
	
	protected final function tApiListFrameworkFields($section, &$fields, &$allowedSubSections = array(), $limit = 5) {
		if (!$this->frameworkLoaded) {
			$this->tApiLoadFramework();
		}
		
		if (!--$limit || !$this->checkFrameworkSectionExists($section)) {
			return;
		}
		
		//Get all of the fields in a sub section, checking sub sections too
		foreach ($this->frameworkData[$section] as &$sectionContents) {
			foreach ($sectionContents as $type => &$thing) {
				if ($type == CMS_FRAMEWORK_SECTION) {
					if (!empty($allowedSubSections[$thing])) {
						$this->tApiListFrameworkFields($thing, $fields, $allowedSubSections, $limit);
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
	
	/**
	 * This is a utility function to deal with the standard image resize options on tuix plugin settings.
	 * @param Array containing tuix $fields
	 * @param Array containing tuix $values
	 * @param String to prefix the available options $img
	 * @param Boolean that specify if we want to show/hide $hide
	 * @param String the field that is used to switch options on/off default to "${img}_show" $img_show
	 */
	protected function showHideImageOptions(&$fields, &$values, $img, $hide, $img_show=false) {
		if(!$img_show) $img_show = $img . '_show';
		$fields[$img_show]['hidden'] = $hide;
	
		$show_canvas = $values[$img_show] && !$hide;
		$canvas = $img . '_canvas';
		$fields[$canvas]['hidden'] = !$show_canvas;
	
		$fields[$img . '_width']['hidden'] = !$show_canvas
		|| !in($values[$canvas], 'fixed_width', 'fixed_width_and_height', 'resize_and_crop');
	
		$fields[$img . '_height']['hidden'] = !$show_canvas
		|| !in($values[$canvas], 'fixed_height', 'fixed_width_and_height', 'resize_and_crop');
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
	//Rather than put all of your Admin Box/Storekeeper functionality in one module,
	//this lets you divvy it up into different subclasses.
	public $tAPIrunSubClassSafetyCatch = false;
	protected final function runSubClass($filePath, $type = false, $path = false) {
		
		//Add a check to stop subclasses calling themsevles again, which would cause an
		//infinite loop!
		if ($this->tAPIrunSubClassSafetyCatch) {
			return false;
		}
		
		if (!$type) {
			$type = cms_core::$skType;
		}
		if (!$path) {
			$path = cms_core::$skPath;
		}
		
		if ($type == 'storekeeper') {
			$type = 'organizer';
		}
		
		$basePath = dirname($filePath);
		$moduleDir = basename($basePath);
		
		//Modules use the owner/author name at the start of their name. Get this prefix.
		$prefix = explode('_', $moduleDir, 2);
		if (!empty($prefix[1])) {
			$prefix = $prefix[0];
		} else {
			$prefix = '';
		}
		
		//Take the path, and try to get the name of the last tag in the tag path.
		//(But if the last tag is "panel", remove that as the second-last tag will be more helpful.)
		//Also try to remove the prefix from above.
		$matches = array();
		preg_match('@.*/_*(\w+)@', str_replace('/'. $prefix. '_', '/', str_replace('/panel', '', '/'. $path)), $matches);
		
		if (empty($matches[1])) {
			exit('Bad path: '. $path);
		}
		
		//From the logic above, create a standard filepath and class name
		$phpPath = $basePath. '/classes/'. $type. '/'. $matches[1]. '.php';
		$className = $moduleDir. '__'. $type. '__'. $matches[1];
		
		//Check if the file is there
		if (is_file($phpPath)) {
			require_once $phpPath;
		
			if (class_exists($className)) {
				$c = new $className;
				$c->tAPIrunSubClassSafetyCatch = true;
				return $c;
			} else {
				exit('The class '. $className. ' was not defined in '. $phpPath);
			}
		
		} else {
			return false;
		}
	}
	
	
	
	
	  ///////////////////////////////////////////
	 //  Old, deprecated Framework Functions  //
	///////////////////////////////////////////
	
	
	protected final function checkFrameworkSectionExists($section = 'Outer') {
		if (!$this->frameworkLoaded) {
			$this->tApiLoadFramework();
		}
		
		return !(empty($this->frameworkData[$section]) || !is_array($this->frameworkData[$section]));
	}
	
	protected final function framework(
								$section = 'Outer', $mergeFields = array(),
								$allowedSubSections = array(), $subSectionDepthLimit = 5,
								$half = false, $halfwayPoint = true
							 ) {
						
		$this->tApiFramework(
				$section, $mergeFields, $allowedSubSections, $subSectionDepthLimit, $half, $halfwayPoint);
	}

	protected final function frameworkHead(
								$section = 'Outer',
								$halfwayPoint = true,
								$mergeFields = array(),
								$allowedSubSections = array(), $subSectionDepthLimit = 5) {
		$this->tApiFramework(
				$section, $mergeFields, $allowedSubSections, $subSectionDepthLimit, 1, $halfwayPoint);
	}
	
	protected final function frameworkFields($section = 'Outer', $allowedSubSections = array(), $subSectionDepthLimit = 5) {
		$fields = array();
		$this->tApiListFrameworkFields($section, $fields, $allowedSubSections, $subSectionDepthLimit);
		return $fields;
	}
	
	protected final function checkRequiredField(&$field) {
		$name = arrayKey($field, 'name');
		if (engToBooleanArray($field, 'required') && !post($name) ) {
			if (arrayKey($field, 'type') == 'checkbox'){
				
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
		$this->tApiFramework(
				$section, $mergeFields, $allowedSubSections, $subSectionDepthLimit, 2, $halfwayPoint);
	}
	
	protected final function loadFramework() {
		$this->tApiLoadFramework();
	}
	
	protected final function resetOddOrEven() {
		$this->tApiOddOrEven = 'odd';
	}
}