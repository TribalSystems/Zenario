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

namespace ze;



//Class to store information about a plugin in a slot
abstract class slot {
	
	//Allow any property of this class to be referenced in a read-only context.
	public function __call($function, $args) {
		return $this->{$function} ?? null;
	}
	
	protected $found = false;
	public function flagAsFound() {
		$this->found = true;
	}
	
	protected $used = false;
	public function flagAsUsed() {
		$this->used = true;
	}
	
	protected $missing = false;
	public function flagAsMissing() {
		$this->missing = true;
	}
	
	protected $error = false;
	public function setErrorMessage($errorMessage) {
		$this->error = $errorMessage;
	}
	
	protected $disallowCaching = false;
	public function disallowCaching() {
		$this->disallowCaching = true;
	}
	
	protected $servedFromCache = false;
	public function flagAsFromCache() {
		$this->servedFromCache = true;
	}
	
	protected $isSuspended = false;
	public function flagAsSuspended() {
		$this->isSuspended = true;
	}
	
	protected $isNest = false;
	public function flagAsNest() {
		$this->isNest = true;
	}
	
	protected $isSlideshow = false;
	public function flagAsSlideshow() {
		$this->isSlideshow = true;
	}
	

	protected $cachePath;
	public function setCachePath($cache_path) {
		$this->cachePath = $cache_path;
	}
	
	protected $menuTitle;
	public function setMenuTitle($title) {
		$this->menuTitle = $title;
	}
	
	protected $pageOGType;
	public function setPageOGType($type) {
		$this->pageOGType = $type;
	}
	
	protected $pageKeywords;
	public function setPageKeywords($keywords) {
		$this->pageKeywords = $keywords;
	}
	
	protected $pageImage;
	public function setPageImage($imageId) {
		$this->pageImage = $imageId;
	}
	
	protected $pageDesc;
	public function setPageDesc($desc) {
		$this->pageDesc = $desc;
	}
	
	protected $pageTitle;
	public function setPageTitle($title) {
		$this->pageTitle = $title;
	}
	
	protected $shownInMenuMode = false;
	public function showInMenuMode($shownInMenuMode = true) {
		$this->shownInMenuMode = $shownInMenuMode;
	}
	
	protected $beingEdited = false;
	public function markSlotAsBeingEdited($beingEdited = true) {
		$this->beingEdited = $beingEdited;
	}
	
	protected $scrollToTop = false;
	public function scrollToTopOfSlot($scrollToTop = true) {
		$this->scrollToTop = $scrollToTop;
	}
	
	protected $pageNeedsReloading = false;
	public function forcePageReload($reload = true) {
		$this->pageNeedsReloading = $reload;
	}
	
	protected $headerRedirectLink;
	public function headerRedirect($link) {
		$this->headerRedirectLink = $link;
	}
	
	protected $shownInFloatingBox;
	protected $floatingBoxParams;
	public function showInFloatingBox($shownInFloatingBox = true, $floatingBoxParams = false) {
		$this->shownInFloatingBox = $shownInFloatingBox;
		$this->floatingBoxParams = $floatingBoxParams;
	}
	
	protected $overriddenSlot;
	public function setOverriddenSlot($slot) {
		$this->overriddenSlot = $slot;
	}
	
	protected $jsLibs = [];
	public final function requireJsLib($lib, $stylesheet = false) {
		$this->jsLibs[$lib] = $stylesheet;
	}
	
	
	protected $instanceId;
	protected $moduleId;
	protected $moduleCSSClassName;
	
	protected $level;
	protected $isHeader = false;
	protected $isFooter = false;
	protected $inGridBreak = false;
	protected $isOpaque = false;
	
	protected $isVersionControlled = false;
	protected $cID;
	protected $cType;
	protected $cVersion;
	protected $slotName;
	
	protected $cacheIf = [];
	protected $cacheClearBy = [];

	protected $eggId;
	protected $eggOrd;
	protected $slideId;
	protected $slideNum;
	protected $minigridCols;
	protected $minigridSmallScreens;
	protected $framework;
	protected $defaultFramework;
	protected $cssClass;
	
	
	public function setSlideNum($slide_num) {
		$this->slideNum = $slide_num;
	}
	public function setSlideId($slide_id) {
		$this->slideId = $slide_id;
	}
	
	protected $eggs = [];
	public function recordEgg($slideNum, $eggId, $slotNameNestId) {
		if (!isset($this->eggs[$slideNum])) {
			$this->eggs[$slideNum] = [];
		}
		$this->eggs[$slideNum][$eggId] = $slotNameNestId;
	}
	public function eggsOnSlideNum($slideNum) {
		return $this->eggs[$slideNum] ?? [];
	}
	
	protected $class;
	protected $init;
	
	//Work out whether we are displaying the Plugin in this slot.
	//Run the plugin's own initalisation routine. If it returns true, then display the plugin.
	//(But note that modules are always displayed in admin mode.)
	public function initInstance() {
		
		$status = $this->class->init();
		
		if (\ze::isError($status)) {
			$this->error = $status->__toString();
			$status = false;
		}
		
		if (!($this->init = $status) && !(\ze\priv::check())) {
			$this->class = null;
			return false;
		} else {
			return true;
		}
	}
	
	protected $moduleClassName;
	protected $vlpClass;
	
	
	//Activate and setup a plugin
	public function setInstance($cID, $cType, $cVersion, $slotName, $overrideSettings = false, $eggId = 0, $slideId = 0, $beingDisplayed = true) {
	
		$missingPlugin = false;
		if (!\ze\module::incWithDependencies($this->moduleClassName, $missingPlugin)) {
			$this->class = false;
			return false;
		}
	
		$this->class = new $this->moduleClassName;
		
		$this->class->setInstance(
			[
				$cID, $cType, $cVersion, $slotName,
				$this->instanceId,
				$this->moduleClassName, $this->vlpClass,
				$this->moduleId,
				$this->framework,
				$this->cssClass,
				$this->level, $this->isVersionControlled
			], $overrideSettings, $eggId, $slideId, $beingDisplayed
		);
	}
	

	public function loadInstance(
			$slotName,
			$cID, $cType, $cVersion,
			$layoutId,
			$specificInstanceId, $specificSlotName, $isAjaxReload,
			$runPlugins, $overrideSettings = false, $overrideFrameworkAndCSS = false
	) {
		
		
		
		$missingPlugin = false;
		
		if (\ze\module::incWithDependencies($this->moduleClassName, $missingPlugin)
		 && method_exists($this->moduleClassName, 'showSlot')) {
			
			//Fetch the name of the instance, and the name of the framework being used
			$sql = "
				SELECT name, framework, css_class
				FROM ". DB_PREFIX. "plugin_instances
				WHERE id = ". (int) $this->instanceId;
			$result = \ze\sql::select($sql);
			if ($row = \ze\sql::fetchAssoc($result)) {
				
				//If we found a plugin to display, activate it and set it up
				
				//Set the framework for this plugin
				if ($overrideFrameworkAndCSS !== false
				 && !empty($overrideFrameworkAndCSS['framework_tab/framework'])) {
					$this->framework = $overrideFrameworkAndCSS['framework_tab/framework'];
				
				} elseif (!empty($row['framework'])) {
					$this->framework = $row['framework'];
				
				} else {
					$this->framework = $this->defaultFramework;
				}
				
				
				//Set the CSS class for this plugin
				$baseCSSName = $this->moduleCSSClassName;
				
				if ($overrideFrameworkAndCSS !== false) {
					$row['css_class'] = $overrideFrameworkAndCSS['this_css_tab/css_class'] ?? $row['css_class'];
				}
				
				if ($row['css_class']) {
					$this->cssClass .= ' '. $row['css_class'];
				} else {
					$this->cssClass .= ' '. $baseCSSName. '__default_style';
				}
				
				
				//Add a CSS class for this version controller plugin, or this library plugin
				if (!empty($this->cID)) {
					if ($cID !== -1) {
						$this->cssClass .=
							' '. $cType. '_'. $cID. '_'. $slotName.
							'_'. $baseCSSName;
					}
				} else {
					$this->cssClass .=
						' '. $baseCSSName.
						'_'. $this->instanceId;
				}
					
				
				if ($runPlugins) {
					$this->setInstance($cID, $cType, $cVersion, $slotName, $overrideSettings);
					
					if ($this->initInstance()) {
						if (!$isAjaxReload && ($location = $this->headerRedirectLink)) {
							header("Location: ". $location);
							exit;
						}
					}
				}
				
			} else {
				$module = \ze\module::details($this->moduleId);
				
				if ($runPlugins) {
					
					//If this is a layout preview, any version controlled plugin won't have an instance id
					//and can't be displayed properly, but set it up as best we can.
					if ($cID === -1
					 && \ze\module::inc($className = $module['moduleClassName'])) {
						$this->class = new $className;
						$this->class->setInstance([
							\ze::$cID, \ze::$cType, \ze::$cVersion, $slotName,
							false, false,
							$className, $module['vlp_class'],
							$module['id'],
							$module['default_framework'],
							$module['css_class_name'],
							false, true]);
					
					//Otherwise if this is a layout preview, then no instance id is an error!
					} else {
						\ze\plugin::setupNewBaseClass($slotName);
						$this->error = \ze\admin::phrase('[Plugin Instance not found for the Module &quot;[[display_name|escape]]&quot;]', $module);
					}
				}
			}
		} else {
			$module = \ze\module::details($this->moduleId);
			
			if ($runPlugins) {
				\ze\plugin::setupNewBaseClass($slotName);
				$this->error = \ze\admin::phrase('[Selected Module "[[display_name|escape]]" not found, not running, or has missing dependencies]', $module);
			}
		}
		
		
		//If a Plugin refused to show itself, cache this refusal as well
		if (!$this->init) {
			self::postSlot($slotName, 'showSlot', $useOb = false);
		}
	}
	
	public function setupNewBaseClass($slotName) {
		
		if (empty($this->class)) {
			$this->class = new \ze\moduleBaseClass;
			$this->class->setInstance(
				[\ze::$cID, \ze::$cType, \ze::$cVersion, $slotName, false, false, false, false, false, false, false, false]);
		}
		
		//Add flags to say whether this slot is in the header, and in the footer.
		//(The slotContents() function only adds these flags for full slots, not empty ones, so we might need to set them here.)
		if (!isset($this->isHeader)) {
			if ($slotInfo = \ze\row::get('layout_slot_link', ['is_header', 'is_footer', 'in_grid_break'], ['layout_id' => \ze::$layoutId, 'slot_name' => $slotName])) {
				$this->isHeader = $slotInfo['is_header'];
				$this->isFooter = $slotInfo['is_footer'];
				$this->inGridBreak = $slotInfo['in_grid_break'];
			}
		}
	}
	
	
	
	public function allowCaching(
		$atAll, $ifUserLoggedIn = true, $ifGetOrPostVarIsSet = true, $ifSessionVarOrCookieIsSet = true
	) {
		foreach (['a' => $atAll, 'u' => $ifUserLoggedIn, 'g' => $ifGetOrPostVarIsSet, 's' => $ifSessionVarOrCookieIsSet] as $if => $set) {
			if (!isset($this->cacheIf[$if])) {
				$this->cacheIf[$if] = true;
			}
			$this->cacheIf[$if] = $this->cacheIf[$if] && $this->cacheIf['a'] && $set;
		}
	}
	
	public function clearCacheBy(
		$clearByContent = false, $clearByMenu = false, $clearByFile = false, $clearByModuleData = false
	) {
		foreach (['content' => $clearByContent, 'menu' => $clearByMenu, 'file' => $clearByFile, 'module' => $clearByModuleData] as $if => $set) {
			if (!isset($this->cacheClearBy[$if])) {
				$this->cacheClearBy[$if] = false;
			}
			$this->cacheClearBy[$if] = $this->cacheClearBy[$if] || $set;
		}
	}
	
	
	public function trimVarsBeforeCaching() {
		$output = [$this->class, $this->found, $this->used];
		$this->class = $this->found = $this->used = null;
		return $output;
	}
	
	public function restoreTrimmedVarsAfterCaching($vars) {
		$this->class = $vars[0];
		$this->found = $vars[1];
		$this->used = $vars[2];
	}
	
	public function restoreFromCache(
		$cID, $cType, $cVersion, $slotName,
		$chPath,
		$pageHead,
		$cahableVars
	) {
		
		$this->class = new cached_plugin;
		$this->class->filePath = $chPath. 'plugin.html';
		
		if (!is_null($pageHead)) {
			$this->class->pageHead = $pageHead;
		}
		
		if (!$this->eggId
		 && file_exists($chPath. 'foot.html')) {
			$this->class->pageFoot = file_get_contents($chPath. 'foot.html');
		}
		
		if (!empty($this->jsLibs)) {
			foreach ($this->jsLibs as $lib => $stylesheet) {
				\ze::requireJsLib($lib, $stylesheet);
			}
		}
		
		$this->class->setInstanceVariables(
			[
				$cID, $cType, $cVersion, $slotName,
				$this->instanceId,
				$this->moduleClassName, $this->vlpClass,
				$this->moduleId,
				$this->framework,
				$this->cssClass,
				$this->level, $this->isVersionControlled
			], false, $this->eggId, $this->slideId
		);
		
		$this->class->zAPISetCachableVars($cahableVars);
		
		if ($this->pageTitle) {
			\ze::$pageTitle = $this->pageTitle;
		}
		if ($this->pageDesc) {
			\ze::$pageDesc = $this->pageDesc;
		}
		if ($this->pageImage) {
			\ze::$pageImage = $this->pageImage;
		}
		if ($this->pageKeywords) {
			\ze::$pageKeywords = $this->pageKeywords;
		}
		if ($this->pageOGType) {
			\ze::$pageOGType = $this->pageOGType;
		}
		if ($this->menuTitle) {
			\ze::$menuTitle = $this->menuTitle;
		}
		
		$this->flagAsFromCache();
		\ze::$cachingInUse = true;
	}
}

//Empty slots, used in admin mode where there is a slot there but with no plugin inside it.
class opaqueSlot extends slot {
	
	public function __construct($level, $isHeader, $isFooter, $inGridBreak, $slotName) {
		$this->level = $level;
		$this->isHeader = $isHeader;
		$this->isFooter = $isFooter;
		$this->inGridBreak = $inGridBreak;
		$this->slotName = $slotName;
		$this->isOpaque = true;
	}
}

//A normal plugin in a slot.
class normalSlot extends slot {
	
	public function __construct(
		$level, $isHeader, $isFooter, $inGridBreak,
		$isVersionControlled, $cID, $cType, $cVersion, $slotName,
		$instanceId, $moduleId, $module
	) {
		$this->level = $level;
		$this->isHeader = $isHeader;
		$this->isFooter = $isFooter;
		$this->inGridBreak = $inGridBreak;
		$this->moduleId = $moduleId;
		$this->instanceId = $instanceId;
		$this->slotName = $slotName;
		
		$this->moduleClassName = $module['class_name'];
		$this->moduleCSSClassName = $module['css_class_name'];
		$this->vlpClass = $module['vlp_class'];
		$this->defaultFramework = $module['default_framework'];
		
		$this->cssClass = $this->moduleCSSClassName;
		
		if ($this->isVersionControlled = $isVersionControlled) {
			$this->cID = $cID;
			$this->cType = $cType;
			$this->cVersion = $cVersion;
		}
	}
}

//A nested plugin in a nested slot. This is where we have a plugin nest or slideshow, that
//has nested slots and nested plugins inside a regular slot.
class nestedSlot extends slot {
	
	public function __construct(
		$slotName, $slideId, $slideNum, $eggOrd,
		$framework, $specificCSSName, $cols, $smallScreens,
		$eggId, $instanceId, $moduleId, $module
	) {
		
		$this->slotName = $slotName;
		$this->moduleId = $moduleId;
		$this->instanceId = $instanceId;
		$this->eggId = $eggId;
		$this->eggOrd = $eggOrd;
		$this->slideNum = $slideNum;
		$this->slideId = $slideId;
		$this->minigridCols = $cols;
		$this->minigridSmallScreens = $smallScreens;
		$this->framework = $framework;
		
		$this->moduleClassName = $module['class_name'];
		$this->moduleCSSClassName = $module['css_class_name'];
		$this->vlpClass = $module['vlp_class'];
		$this->defaultFramework = $module['default_framework'];
		
		
		$cssClass = $this->moduleCSSClassName;
		
		if ($specificCSSName) {
			$cssClass .= ' '. $specificCSSName;
		} else {
			$cssClass .= ' '. $this->moduleCSSClassName. '__default_style';
		}
	
		$cssClass .=
			' '. $this->moduleCSSClassName.
			'_'. $instanceId.
			'_'. $eggId;
		
		$this->cssClass = $cssClass;

	}
}






//This class defines a "fake" slotable Plugin, which when used will read HTML from the cache cache directory
class cached_plugin extends \ze\moduleBaseClass {

	public $filePath = '';
	public $pageHead = '';
	public $pageFoot = '';

	public function showSlot() {
		\ze::ignoreErrors();
			@readfile($this->filePath);
		\ze::noteErrors();
	}

	public function addToPageHead() {
		echo $this->pageHead;
	}

	public function addToPageFoot() {
		echo $this->pageFoot;
	}
}