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


define('CLOSURE_COMPILER_PATH', 'zenario/libs/not_to_redistribute/closure-compiler/closure-compiler.jar');
define('YUI_COMPRESSOR_PATH', 'zenario/libs/not_to_redistribute/yuicompressor/yuicompressor-2.4.8.jar');

define('DEBUG_DONT_MINIFY', false);


if (!is_file(CMS_ROOT. CLOSURE_COMPILER_PATH)
 || !is_file(CMS_ROOT. YUI_COMPRESSOR_PATH)) {
	echo
"A tool for minifying JavaScript used by Zenario;
this is a wrapper for calling Closure Compiler (https://developers.google.com/closure/compiler/)
and YUI Compressor (https://yui.github.io/yuicompressor/) on all relevant files.

To save space, the Zenario download does not come with copies of these libraries,
but if you download them and put them in the right place, then this tool will use them.

To use this tool:
 - Java must be available on your server.
 - You must download the closure-compiler.jar file from the site mentioned above.
 - This needs to be placed in the zenario/libs/not_to_redistribute/closure-compiler/ directory.
 - You must have a copy of the yuicompressor-2.4.8.jar file from the site mentioned above.
 - This needs to be placed in the zenario/libs/not_to_redistribute/yuicompressor/ directory.

";
	exit;
}



function displayUsage() {
	echo
"A tool for minifying JavaScript used by Zenario;
this is a wrapper for calling YUI Compressor (https://yui.github.io/yuicompressor/)
and Closure Compiler (https://developers.google.com/closure/compiler/) on all relevant files.

Usage:
	php js_minify
		Minify all of the JavaScript and CSS files that the CMS uses.
	php js_minify filename.js
		Minify a specific JavaScript and CSS files.
	php js_minify directory
		Minify all of the JavaScript and CSS files in a specific directory.
	php js_minify p
		List the files that would be minified, but don't do anything.
	php js_minify v
		Use debug/verbose mode when minifying.

If you have svn, this script will only minify files that svn says are new or modified.

";
	exit;
}

class zenario_minify {
	
	public static function svnStatus($path) {
	
		if (is_null(self::$stats)) {
			self::$stats = [];
		
			if (is_dir(CMS_ROOT. '.svn')) {
				$statusLines = [];
				exec('svn status '. escapeshellarg(CMS_ROOT), $statusLines);
	
				foreach ($statusLines as $line) {
					$line = explode(CMS_ROOT, $line, 2);
					if (isset($line[1])) {
						self::$stats[$line[1]] = trim($line[0]);
					}
				}
			}
		}
		
		return self::$stats[$path] ?? self::$stats[ze\ring::chopPrefix(CMS_ROOT, $path, true)] ?? null;
	}
	
	public static $stats = null;
	public static $longNames = [];
	public static $shortNames = [];
	
	//To get this list of functions compatible with the short-name system from a library,
	//you need to be on a page in admin mode, with that library on the page, then call
	//the zenarioA.reviewShortNames() function.
	//E.g.:
	//	zenarioA.reviewShortNames(_, '_');
	//	zenarioA.reviewShortNames(zenario, 'zenario');
	//	zenarioA.reviewShortNames(zenarioT);
	//	zenarioA.reviewShortNames(zenarioA);
	//	zenarioA.reviewShortNames(zenarioAB);
	//	zenarioA.reviewShortNames(zenarioAT);
	//	zenarioA.reviewShortNames(zenarioO);
	//	zenarioA.reviewShortNames(zenarioGM);
	//	zenarioA.reviewShortNames(zenario_conductor);
	public static $shortNamesWhitelist = [
		'_.allKeys(',
		'_.assign(',
		'_.before(',
		'_.bindAll(',
		'_.collect(',
		'_.compact(',
		'_.compose(',
		'_.constant(',
		'_.contains(',
		'_.countBy(',
		'_.create(',
		'_.debounce(',
		'_.default(',
		'_.defaults(',
		'_.detect(',
		'_.difference(',
		'_.escape(',
		'_.extend(',
		'_.extendOwn(',
		'_.filter(',
		'_.findIndex(',
		'_.findKey(',
		'_.findLastIndex(',
		'_.findWhere(',
		'_.flatten(',
		'_.forEach(',
		'_.functions(',
		'_.groupBy(',
		'_.identity(',
		'_.include(',
		'_.includes(',
		'_.indexBy(',
		'_.indexOf(',
		'_.initial(',
		'_.inject(',
		'_.intersection(',
		'_.invert(',
		'_.invoke(',
		'_.isArguments(',
		'_.isArray(',
		'_.isArrayBuffer(',
		'_.isBoolean(',
		'_.isDataView(',
		'_.isDate(',
		'_.isElement(',
		'_.isEmpty(',
		'_.isEqual(',
		'_.isError(',
		'_.isFinite(',
		'_.isFunction(',
		'_.isMatch(',
		'_.isNull(',
		'_.isNumber(',
		'_.isObject(',
		'_.isRegExp(',
		'_.isString(',
		'_.isSymbol(',
		'_.isTypedArray(',
		'_.isUndefined(',
		'_.isWeakMap(',
		'_.isWeakSet(',
		'_.iteratee(',
		'_.lastIndexOf(',
		'_.mapObject(',
		'_.matcher(',
		'_.matches(',
		'_.memoize(',
		'_.methods(',
		'_.negate(',
		'_.noConflict(',
		'_.object(',
		'_.partial(',
		'_.partition(',
		'_.property(',
		'_.propertyOf(',
		'_.random(',
		'_.reduce(',
		'_.reduceRight(',
		'_.reject(',
		'_.restArguments(',
		'_.result(',
		'_.sample(',
		'_.select(',
		'_.shuffle(',
		'_.sortBy(',
		'_.sortedIndex(',
		'_.template(',
		'_.throttle(',
		'_.toArray(',
		'_.toPath(',
		'_.transpose(',
		'_.unescape(',
		'_.unique(',
		'_.uniqueId(',
		'_.values(',
		'_.without(',
		'zenario_conductor.autoRefresh(',
		'zenario_conductor.backLink(',
		'zenario_conductor.cleanRequests(',
		'zenario_conductor.closeToggle(',
		'zenario_conductor.commandEnabled(',
		'zenario_conductor.confirmOnClose(',
		'zenario_conductor.confirmOnCloseMessage(',
		'zenario_conductor.enabled(',
		'zenario_conductor.getCommand(',
		'zenario_conductor.getSlot(',
		'zenario_conductor.getToggle(',
		'zenario_conductor.getToggles(',
		'zenario_conductor.getVar(',
		'zenario_conductor.getVars(',
		'zenario_conductor.goBack(',
		'zenario_conductor.linkToOtherContentItem(',
		'zenario_conductor.mergeRequests(',
		'zenario_conductor.openToggle(',
		'zenario_conductor.refresh(',
		'zenario_conductor.refreshAll(',
		'zenario_conductor.reloadAfterDelay(',
		'zenario_conductor.request(',
		'zenario_conductor.resetVarsOnBackNav(',
		'zenario_conductor.resetVarsOnBrowserBackNav(',
		'zenario_conductor.setCommands(',
		'zenario_conductor.setToggle(',
		'zenario_conductor.setVar(',
		'zenario_conductor.setVars(',
		'zenario_conductor.stopAutoRefresh(',
		'zenario_conductor.toggleClick(',
		'zenario_conductor.transitionIn(',
		'zenario_conductor.transitionOut(',
		'zenario.actAfterDelayIfNotSuperseded(',
		'zenario.addAmp(',
		'zenario.addBasePath(',
		'zenario.addClassesToColorbox(',
		'zenario.addJQueryElements(',
		'zenario.addLibPointers(',
		'zenario.addPluginJavaScript(',
		'zenario.addRequest(',
		'zenario.addStyles(',
		'zenario.addTabIdToURL(',
		'zenario.AJAXLink(',
		'zenario.applyCompilationMacros(',
		'zenario.applyMergeFields(',
		'zenario.applyMergeFieldsN(',
		'zenario.between(',
		'zenario.browserIsChrome(',
		'zenario.browserIsEdge(',
		'zenario.browserIsFirefox(',
		'zenario.browserIsIE(',
		'zenario.browserIsiOS(',
		'zenario.browserIsiPad(',
		'zenario.browserIsiPhone(',
		'zenario.browserIsOpera(',
		'zenario.browserIsRetina(',
		'zenario.browserIsSafari(',
		'zenario.browserIsWebKit(',
		'zenario.buttonClick(',
		'zenario.callback(',
		'zenario.callScript(',
		'zenario.canCopy(',
		'zenario.canSetCookie(',
		'zenario.checkDataRevisionNumber(',
		'zenario.checkForHashChanges(',
		'zenarioP.checkPasswordStrength(',
		'zenario.checkSessionStorage(',
		'zenario.clearAllDelays(',
		'zenario.clearDateField(',
		'zenario.closeTooltip(',
		'zenario.createZenarioLibrary(',
		'zenario.cssEscape(',
		'zenario.dataRev(',
		'zenario.dateFieldKeyUp(',
		'zenario.decodeItemIdForOrganizer(',
		'zenario.decodeItemIdForStorekeeper(',
		'zenario.defined(',
		'zenario.disableScrolling(',
		'zenario.drawMicroTemplate(',
		'zenario.enableFullScreen(',
		'zenario.enableScrolling(',
		'zenario.encodeItemIdForOrganizer(',
		'zenario.encodeItemIdForStorekeeper(',
		'zenario.engToBoolean(',
		'zenario.exitFullScreen(',
		'zenario.extensionOf(',
		'zenario.fireChangeEvent(',
		'zenario.fixJSON(',
		'zenario.formatDate(',
		'zenario.formSubmit(',
		'zenario.generateMicroTemplate(',
		'zenario.getContainerIdFromEl(',
		'zenario.getContainerIdFromSlotName(',
		'zenario.getEggIdFromEl(',
		'zenario.getMouseX(',
		'zenario.getMouseY(',
		'zenario.getSlotnameFromEl(',
		'zenario.goToURL(',
		'zenario.grecaptcha(',
		'zenario.grecaptchaIsLoaded(',
		'zenario.handlePluginAJAX(',
		'zenario.hasInlineTag(',
		'zenario.htmlspecialchars(',
		'zenario.httpOrhttps(',
		'zenario.hypEscape(',
		'zenario.inList(',
		'zenario.isFullScreen(',
		'zenario.isFullScreenAvailable(',
		'zenario.ishttps(',
		'zenario.isTouchScreen(',
		'zenario.jsEscape(',
		'zenario.jsUnescape(',
		'zenario.linkToItem(',
		'zenario.loadAutocomplete(',
		'zenario.loadDatePicker(',
		'zenario.loadLibrary(',
		'zenario.loadPhrases(',
		'zenario.loadScript(',
		'zenario.manageCookies(',
		'zenario.methodsOf(',
		'zenario.microTemplate(',
		'zenario.moduleBaseClass(',
		'zenario.moduleNonAsyncAJAX(',
		'zenario.nonAsyncAJAX(',
		'zenario.nphrase(',
		'zenario.outdateCachedData(',
		'zenario.parseContainerId(',
		'zenario.phrase(',
		'zenario.pluginAJAXLink(',
		'zenario.pluginAJAXURL(',
		'zenario.pluginClassAJAX(',
		'zenario.pluginShowFileLink(',
		'zenario.pluginShowImageLink(',
		'zenario.pluginShowStandalonePageLink(',
		'zenario.pluginVisitorTUIXLink(',
		'zenario.recordRequestsInURL(',
		'zenario.refreshPluginSlot(',
		'zenario.refreshSlot(',
		'zenario.registerPhrases(',
		'zenario.removeClassesToColorbox(',
		'zenario.removeLinkStatus(',
		'zenario.replacePluginSlotContents(',
		'zenario.resize(',
		'zenario.resizeColorbox(',
		'zenario.rightHandedSubStr(',
		'zenario.sClear(',
		'zenario.scrollLeft(',
		'zenario.scrollToEl(',
		'zenario.scrollTop(',
		'zenario.scrollToSlotTop(',
		'zenario.sendSignal(',
		'zenario.setActiveClass(',
		'zenario.setChildrenToTheSameHeight(',
		'zenario.setSessionStorage(',
		'zenario.sGetItem(',
		'zenario.showFileLink(',
		'zenario.showFloatingBoxLink(',
		'zenario.showImageLink(',
		'zenario.showSingleSlotLink(',
		'zenario.showStandalonePageLink(',
		'zenario.shrtNms(',
		'zenario.splitFlagsFromMessage(',
		'zenario.sSetItem(',
		'zenario.startPoking(',
		'zenario.stopPoking(',
		'zenario.submitFormReturningHtml(',
		'zenario.tidyLibPointers(',
		'zenario.tinyMCEGetContent(',
		'zenario.toObject(',
		'zenario.tooltips(',
		'zenario.tooltipsUsing(',
		'zenario.uneschyp(',
		'zenario.unpackAndMerge(',
		'zenarioP.updatePasswordNotifier(',
		'zenario.urlRequest(',
		'zenario.versionOfIE(',
		'zenario.visitorTUIXLink(',
		'zenarioA.addImagePropertiesButtons(',
		'zenarioA.addLinkStatus(',
		'zenarioA.addMediaToTinyMCE(',
		'zenarioA.addNewReusablePlugin(',
		'zenarioA.addNewWireframePlugin(',
		'zenarioA.adjustBox(',
		'zenarioA.adminSlotWrapperClick(',
		'zenarioA.AJAXErrorHandler(',
		'zenarioA.cancelMovePlugin(',
		'zenarioA.checkCookiesEnabled(',
		'zenarioA.checkForEdits(',
		'zenarioA.checkIfBoxIsOpen(',
		'zenarioA.checkSlotsBeingEdited(',
		'zenarioA.checkSpecificPerms(',
		'zenarioA.checkSpecificPermsOnThisPage(',
		'zenarioA.clearToast(',
		'zenarioA.clickOtherTutorialVideo(',
		'zenarioA.closeBox(',
		'zenarioA.closeBoxHandler(',
		'zenarioA.closeFloatingBox(',
		'zenarioA.closeInfoBox(',
		'zenarioA.closeSlotControls(',
		'zenarioA.closeSlotControlsAfterDelay(',
		'zenarioA.copyEmbedHTML(',
		'zenarioA.copyEmbedLink(',
		'zenarioA.doDownload(',
		'zenarioA.doMovePlugin(',
		'zenarioA.doMovePlugin2(',
		'zenarioA.dontCloseSlotControls(',
		'zenarioA.draftDoCallback(',
		'zenarioA.draftSetCallback(',
		'zenarioA.enableDragDropUploadInTinyMCE(',
		'zenarioA.fileBrowser(',
		'zenarioA.floatingBox(',
		'zenarioA.formatFilesizeNicely(',
		'zenarioA.formatOrganizerItemName(',
		'zenarioA.formatSKItemField(',
		'zenarioA.generateRandomString(',
		'zenarioA.getDefaultLanguageName(',
		'zenarioA.getEditorField(',
		'zenarioA.getGridSlotDetails(',
		'zenarioA.getItemFromOrganizer(',
		'zenarioA.getSKBodyClass(',
		'zenarioA.getSkinDesc(',
		'zenarioA.getSKItem(',
		'zenarioA.hasNoPriv(',
		'zenarioA.hasPriv(',
		'zenarioA.hideAJAXLoader(',
		'zenarioA.hidePlugin(',
		'zenarioA.imageProperties(',
		'zenarioA.infoBox(',
		'zenarioA.initTutorialSlideshow(',
		'zenarioA.isHtaccessWorking(',
		'zenarioA.loggedOut(',
		'zenarioA.loggedOutIframeCheck(',
		'zenarioA.longToast(',
		'zenarioA.lookupFileDetails(',
		'zenarioA.movePlugin(',
		'zenarioA.multipleLanguagesEnabled(',
		'zenarioA.nItems(',
		'zenarioA.notification(',
		'zenarioA.nowDoingSomething(',
		'zenarioA.onunload(',
		'zenarioA.openBox(',
		'zenarioA.openMenuAdminBox(',
		'zenarioA.openSlotControls(',
		'zenarioA.organizerQuick(',
		'zenarioA.organizerSelect(',
		'zenarioA.pickNewPlugin(',
		'zenarioA.pluginSlotEditSettings(',
		'zenarioA.reloadMenuPlugins(',
		'zenarioA.rememberToast(',
		'zenarioA.removePlugin(',
		'zenarioA.replacePluginSlot(',
		'zenarioA.reviewShortNames(',
		'zenarioA.savePageMode(',
		'zenarioA.scanHyperlinksAndDisplayStatus(',
		'zenarioA.setDocumentURL(',
		'zenarioA.setEditorField(',
		'zenarioA.setImageURL(',
		'zenarioA.setLinkPickerOnTinyMCE(',
		'zenarioA.setLinkURL(',
		'zenarioA.setModuleInfo(',
		'zenarioA.setSlotParents(',
		'zenarioA.setTooltipIfTooLarge(',
		'zenarioA.showAJAXLoader(',
		'zenarioA.showHelp(',
		'zenarioA.showMessage(',
		'zenarioA.showPagePreview(',
		'zenarioA.showPlugin(',
		'zenarioA.showTutorial(',
		'zenarioA.SKInit(',
		'zenarioA.slotParentMouseOut(',
		'zenarioA.slotParentMouseOver(',
		'zenarioA.suspendStopWrapperClicks(',
		'zenarioA.tinyMCEPasteRreprocess(',
		'zenarioA.toastOrNoToast(',
		'zenarioA.toggleShowEmptySlots(',
		'zenarioA.toggleShowGrid(',
		'zenarioA.toggleShowHelpTourNextTime(',
		'zenarioA.tooltips(',
		'zenarioA.translationsEnabled(',
		'zenarioAB.adminParentPermChange(',
		'zenarioAB.adminPermChange(',
		'zenarioAB.clickTab(',
		'zenarioAB.closeBox(',
		'zenarioAB.contentTitleChange(',
		'zenarioAB.cutText(',
		'zenarioAB.enableOrDisableSite(',
		'zenarioAB.generateAlias(',
		'zenarioAB.makeFieldAsTallAsPossible(',
		'zenarioAB.openBox(',
		'zenarioAB.openSiteSettings(',
		'zenarioAB.previewDateFormat(',
		'zenarioAB.previewDateFormatGo(',
		'zenarioAB.removeHtmAndHtmlFromAlias(',
		'zenarioAB.removeHttpAndHttpsFromAlias(',
		'zenarioAB.setTitle(',
		'zenarioAB.svgSelected(',
		'zenarioAB.updateHash(',
		'zenarioAB.updateSEP(',
		'zenarioAB.validateAlias(',
		'zenarioAB.validateAliasGo(',
		'zenarioAB.viewFrameworkSource(',
		'zenarioAT.action(',
		'zenarioAT.action2(',
		'zenarioAT.applyMergeFields(',
		'zenarioAT.applyMergeFieldsToLabel(',
		'zenarioAT.clickButton(',
		'zenarioAT.clickTab(',
		'zenarioAT.customiseOrganizerLink(',
		'zenarioAT.getKey(',
		'zenarioAT.getKeyId(',
		'zenarioAT.getLastKeyId(',
		'zenarioAT.organizerQuick(',
		'zenarioAT.pickItems(',
		'zenarioAT.setURL(',
		'zenarioAT.showGridOnOff(',
		'zenarioAT.slotDisabled(',
		'zenarioAT.sortButtons(',
		'zenarioAT.uploadComplete(',
		'zenarioGM.ajaxData(',
		'zenarioGM.ajaxURL(',
		'zenarioGM.canRedo(',
		'zenarioGM.canUndo(',
		'zenarioGM.cellLabel(',
		'zenarioGM.change(',
		'zenarioGM.checkCellsEmpty(',
		'zenarioGM.checkData(',
		'zenarioGM.checkDataFormat(',
		'zenarioGM.checkDataNonZero(',
		'zenarioGM.checkDataNonZeroAndNumeric(',
		'zenarioGM.checkDataNumeric(',
		'zenarioGM.checkDataR(',
		'zenarioGM.checkIfNameUsed(',
		'zenarioGM.clearAddToolbar(',
		'zenarioGM.confirmDeleteSlot(',
		'zenarioGM.deleteCell(',
		'zenarioGM.disableChangingSettings(',
		'zenarioGM.drawAddToolbar(',
		'zenarioGM.drawEditor(',
		'zenarioGM.drawOptions(',
		'zenarioGM.editProperties(',
		'zenarioGM.getLevels(',
		'zenarioGM.getSlotCSSName(',
		'zenarioGM.getSlotDescription(',
		'zenarioGM.isExistingLayout(',
		'zenarioGM.markAsSaved(',
		'zenarioGM.microTemplate(',
		'zenarioGM.modeIs(',
		'zenarioGM.modeIsNot(',
		'zenarioGM.randomName(',
		'zenarioGM.readSettings(',
		'zenarioGM.recalc(',
		'zenarioGM.recalcColumnAndGutterOptions(',
		'zenarioGM.recalcOnChange(',
		'zenarioGM.refocus(',
		'zenarioGM.registerNewName(',
		'zenarioGM.rememberNames(',
		'zenarioGM.revert(',
		'zenarioGM.saveProperties(',
		'zenarioGM.scaleWidth(',
		'zenarioGM.setHeight(',
		'zenarioGM.tooltips(',
		'zenarioGM.undoOrRedo(',
		'zenarioGM.uniqueRandomName(',
		'zenarioGM.update(',
		'zenarioGM.updateAndChange(',
		'zenarioGM.useSettingsFromHeader(',
		'zenarioO.action2(',
		'zenarioO.addWindowParentInfo(',
		'zenarioO.allItemsSelected(',
		'zenarioO.applyMergeFields(',
		'zenarioO.applyMergeFieldsToLabel(',
		'zenarioO.applySmallSpaces(',
		'zenarioO.branch(',
		'zenarioO.canFilterColumn(',
		'zenarioO.canSortColumn(',
		'zenarioO.changeFilters(',
		'zenarioO.changePageSize(',
		'zenarioO.changePassword(',
		'zenarioO.changeSortOrder(',
		'zenarioO.checkButtonHidden(',
		'zenarioO.checkCondition(',
		'zenarioO.checkDisabled(',
		'zenarioO.checkHiddenByFilter(',
		'zenarioO.checkIfClearAllAvailable(',
		'zenarioO.checkIfColumnPickerChangesAreAllowed(',
		'zenarioO.checkItemButtonHidden(',
		'zenarioO.checkItemPickable(',
		'zenarioO.checkPrefs(',
		'zenarioO.checkQueue(',
		'zenarioO.checkQueueLength(',
		'zenarioO.choose(',
		'zenarioO.chooseButtonActive(',
		'zenarioO.clearFilter(',
		'zenarioO.clearRefiner(',
		'zenarioO.clearSearch(',
		'zenarioO.closeInfoBox(',
		'zenarioO.closeInspectionView(',
		'zenarioO.closeSelectMode(',
		'zenarioO.collectionButtonClick(',
		'zenarioO.columnCssClass(',
		'zenarioO.columnEqual(',
		'zenarioO.columnNotEqual(',
		'zenarioO.columnRawValue(',
		'zenarioO.columnValue(',
		'zenarioO.convertNavPathToTagPath(',
		'zenarioO.convertNavPathToTagPathAndRefiners(',
		'zenarioO.deselectAllItems(',
		'zenarioO.disableInteraction(',
		'zenarioO.doCSVExport(',
		'zenarioO.doSearch(',
		'zenarioO.enableInteraction(',
		'zenarioO.exportPanelAsCSV(',
		'zenarioO.exportPanelAsExcel(',
		'zenarioO.fadeOutLastButtons(',
		'zenarioO.filterSetOnColumn(',
		'zenarioO.followPathOnMap(',
		'zenarioO.getAJAXURL(',
		'zenarioO.getBackButtonTitle(',
		'zenarioO.getCollectionButtons(',
		'zenarioO.getColumnFilterType(',
		'zenarioO.getCurrentPage(',
		'zenarioO.getDataHack(',
		'zenarioO.getFilterValue(',
		'zenarioO.getFooter(',
		'zenarioO.getFromLastPanel(',
		'zenarioO.getFromToFromLink(',
		'zenarioO.getHash(',
		'zenarioO.getHeader(',
		'zenarioO.getInlineButtons(',
		'zenarioO.getItemButtons(',
		'zenarioO.getItemCSSClass(',
		'zenarioO.getKey(',
		'zenarioO.getKeyId(',
		'zenarioO.getLastKeyId(',
		'zenarioO.getNavigation(',
		'zenarioO.getNextItem(',
		'zenarioO.getPageCount(',
		'zenarioO.getPanel(',
		'zenarioO.getPanelType(',
		'zenarioO.getQuickFilters(',
		'zenarioO.getSelectedItemFromLastPanel(',
		'zenarioO.getShownColumns(',
		'zenarioO.getSortedIdsOfTUIXElements(',
		'zenarioO.goToLastPage(',
		'zenarioO.goToPage(',
		'zenarioO.hideCollectionButtons(',
		'zenarioO.hideItemButtons(',
		'zenarioO.hideViewOptions(',
		'zenarioO.implodeKeys(',
		'zenarioO.infoBox(',
		'zenarioO.inInspectionView(',
		'zenarioO.initNewPanelInstance(',
		'zenarioO.inlineButtonClick(',
		'zenarioO.inspectionViewEnabled(',
		'zenarioO.inspectionViewItemId(',
		'zenarioO.invertFilter(',
		'zenarioO.isFullMode(',
		'zenarioO.isShowableColumn(',
		'zenarioO.itemButtonClick(',
		'zenarioO.itemClickThrough(',
		'zenarioO.itemClickThroughAction(',
		'zenarioO.itemClickThroughLink(',
		'zenarioO.itemLanguage(',
		'zenarioO.itemParent(',
		'zenarioO.loadFromBranches(',
		'zenarioO.loadMap(',
		'zenarioO.loadRefiner(',
		'zenarioO.lookForBranches(',
		'zenarioO.markIfViewIsFiltered(',
		'zenarioO.maxLengthString(',
		'zenarioO.nextPage(',
		'zenarioO.noItemsSelected(',
		'zenarioO.openInspectionView(',
		'zenarioO.panelProp(',
		'zenarioO.parseNavigationPath(',
		'zenarioO.parseReturnLink(',
		'zenarioO.pathNotAllowed(',
		'zenarioO.pickItems(',
		'zenarioO.prevPage(',
		'zenarioO.quickFilterEnabled(',
		'zenarioO.refreshAndShowPage(',
		'zenarioO.refreshIfFilterSet(',
		'zenarioO.refreshPage(',
		'zenarioO.refreshToShowItem(',
		'zenarioO.reload(',
		'zenarioO.reloadButton(',
		'zenarioO.reloadOpeningInstanceIfRelevant(',
		'zenarioO.reloadPage(',
		'zenarioO.resetBranches(',
		'zenarioO.resetPrefs(',
		'zenarioO.resizeColumn(',
		'zenarioO.rowCssClass(',
		'zenarioO.runPanelOnUnload(',
		'zenarioO.runSearch(',
		'zenarioO.savePrefs(',
		'zenarioO.saveRefiner(',
		'zenarioO.saveSearch(',
		'zenarioO.scrollTopLevelNav(',
		'zenarioO.searchAndSortItems(',
		'zenarioO.searchOnChange(',
		'zenarioO.searchOnClick(',
		'zenarioO.searchOnKeyUp(',
		'zenarioO.selectAllItems(',
		'zenarioO.selectCreatedIds(',
		'zenarioO.selectedItemDetails(',
		'zenarioO.selectedItemId(',
		'zenarioO.selectedItemIds(',
		'zenarioO.selectedItems(',
		'zenarioO.selectionDisplayType(',
		'zenarioO.selectItemRange(',
		'zenarioO.selectItems(',
		'zenarioO.setBackButton(',
		'zenarioO.setButtonAction(',
		'zenarioO.setButtons(',
		'zenarioO.setChooseButton(',
		'zenarioO.setDataAttributes(',
		'zenarioO.setFilterValue(',
		'zenarioO.setHash(',
		'zenarioO.setNavigation(',
		'zenarioO.setOrganizerIcons(',
		'zenarioO.setPanel(',
		'zenarioO.setPanelTitle(',
		'zenarioO.setSearch(',
		'zenarioO.setTopLevelNavScrollStatus(',
		'zenarioO.setTopRightButtons(',
		'zenarioO.setTrash(',
		'zenarioO.setViewOptions(',
		'zenarioO.setWhereWasThatThingSearch(',
		'zenarioO.setWrapperClass(',
		'zenarioO.shortenPath(',
		'zenarioO.showableColumns(',
		'zenarioO.showCollectionButtons(',
		'zenarioO.showHideColumn(',
		'zenarioO.showHideColumnInCSV(',
		'zenarioO.showItemButtons(',
		'zenarioO.showPage(',
		'zenarioO.showViewOptions(',
		'zenarioO.showViewOptions2(',
		'zenarioO.sizeButtons(',
		'zenarioO.sortArray(',
		'zenarioO.splitCols(',
		'zenarioO.stopRefreshing(',
		'zenarioO.switchColumnOrder(',
		'zenarioO.toggleAllItems(',
		'zenarioO.toggleFilter(',
		'zenarioO.toggleInspectionView(',
		'zenarioO.toggleQuickFilter(',
		'zenarioO.topLevelClick(',
		'zenarioO.topRightButtonClick(',
		'zenarioO.updateDateFilters(',
		'zenarioO.updateYourWorkInProgress(',
		'zenarioO.uploadComplete(',
		'zenarioO.uploadStart(',
		'zenarioO.viewTrash(',
		'zenarioT.action(',
		'zenarioT.addClass(',
		'zenarioT.canDoHTML5Upload(',
		'zenarioT.checkActionExists(',
		'zenarioT.checkActionUnique(',
		'zenarioT.checkFunctionExists(',
		'zenarioT.csvToObject(',
		'zenarioT.disableFileDragDrop(',
		'zenarioT.doEval(',
		'zenarioT.doHTML5Upload(',
		'zenarioT.doNextUpload(',
		'zenarioT.filter(',
		'zenarioT.generateGlobalName(',
		'zenarioT.getSortedIdsOfTUIXElements(',
		'zenarioT.hidden(',
		'zenarioT.keepTrying(',
		'zenarioT.microTemplate(',
		'zenarioT.newSimpleForm(',
		'zenarioT.numberFormat(',
		'zenarioT.onbeforeunload(',
		'zenarioT.onChangeOrSearch(',
		'zenarioT.option(',
		'zenarioT.resizeImage(',
		'zenarioT.select(',
		'zenarioT.setHTML5UploadFromDragDrop(',
		'zenarioT.setKin(',
		'zenarioT.showDevTools(',
		'zenarioT.sortArray(',
		'zenarioT.sortArrayByOrd(',
		'zenarioT.sortArrayByOrdinal(',
		'zenarioT.sortArrayDesc(',
		'zenarioT.sortArrayForOrganizer(',
		'zenarioT.sortArrayWithGrouping(',
		'zenarioT.sortLogic(',
		'zenarioT.splitDataFromErrorMessage(',
		'zenarioT.stopDefault(',
		'zenarioT.stopFileDragDrop(',
		'zenarioT.stopTrying(',
		'zenarioT.tuixToArray(',
		'zenarioT.uploadDone(',
		'zenarioT.uploadProgress('
	];
}

//Macros and replacements
function applyCompilationMacros($code, $dir, $file) {
	
	//Check to see if this is a module file
	$module = false;
	$matches = [];
	if (preg_match('@modules/(\w+)/@', $dir, $matches)) {
		$module = $matches[1];
	}
	
	//Check if this JavaScript file uses the zenario.lib function.
	$isZenarioLib =
		false !== strpos($code, 'zenario.lib(');
	$isZenarioLibWithAllInputs =
		$isZenarioLib
	 && false !== strpos($code, 'extensionOf, methodsOf, has');
	$usesThus =
		$isZenarioLib
	 && false !== strpos($code, 'thus');
	
	//If so, we can use the has() shortcut.
	//If not, we need to write out zenario.has() in full.
	if ($isZenarioLibWithAllInputs) {
		$has = 'has';
	} else {
		$has = 'zenario.has';
	}
	
	//Use the shortcuts for some string methods properties to save space
	if ($isZenarioLib) {
		$code = preg_replace('@.([mrs])(atch|eplace|plit)\(@', '.$1(', $code);
	}
	
	//Automatically add "var thus = this;" to the start of any method declarations.
	//Also add it to any static function declared on a module.
	if ($usesThus) {
		$code = preg_replace('@(\bmethods\w*\.[\w\$]+\s*=\s*function\s*\([^\)]*\)\s*\{)@', '$1 var thus = this;', $code);
		
		if ($module !== false) {
			$code = preg_replace('@(\b'. $module. '\w*\.[\w\$]+\s*=\s*function\s*\([^\)]*\)\s*\{)@', '$1 var thus = this;', $code);
		}
	}
	
	//Where an encapsulated function has a whitelisted shortname, replace it with its shortname in the minified code.
	if (zenario_minify::$longNames !== []) {
		$code = str_replace(zenario_minify::$longNames, zenario_minify::$shortNames, $code);
	}
	
	$patterns = [];
	$replacements = [];
	
	//"foreach" is a macro for "for .. in ... hasOwnProperty"
	$patterns[] = '@\bforeach\b\s*\(\s*(.+?)\s*\bas\b\s*(\bvar\b |)\s*(.+?)\s*\=\>\s*(\bvar\b |)\s*(.+?)\s*\)\s*\{@';
	$replacements[] = 'for (\2\3 in \1) { if (!'. $has. '(\1, \3)) continue; \4 \5 = \1[\3];';
	$patterns[] = '@\bforeach\b\s*\(\s*(.+?)\s*\bas\b\s*(\bvar\b |)\s*(.+?)\s*\)\s*\{@';
	$replacements[] = 'for (\2\3 in \1) { if (!'. $has. '(\1, \3)) continue;';
	
	//We don't have node as a dependency so we can't use Babel.
	//So we'll try and make do with a few replacements instead!
	$patterns[] = '@\(([\w\s,]*)\)\s*\=\>\s*\{@';
	$replacements[] = 'function ($1) {';
	$patterns[] = '@(\b[\w\$]+\b)\s*\=\>\s*\{@';
	$replacements[] = 'function ($1) {';
	
	//Not actually standard JavaScript but looks nice
	$patterns[] = '@\=\>\s*\{@';
	$replacements[] = 'function () {';
	
	return preg_replace($patterns, $replacements, $code);
}


function minifyString($string) {
	return minify('/dummy/', 'dummy', 2, '.js', $string);
}
function minify($dir, $file, $level, $ext = '.js', $string = false) {
	
	
	//Parse the short name whitelist, if we've not already done so
	if (zenario_minify::$longNames === []) {
		foreach (zenario_minify::$shortNamesWhitelist as $libAndName) {
			
			//Split the name in the white list into the library name and the function name.
			$explode = explode('.', str_replace('(', '', $libAndName), 2);
			$lib = $explode[0];
			$name = $explode[1];
			
			//Most short names created at the end of this process are 4 characters long.
			//We won't bother creating a short name for anything that's 5 characters or shorter,
			//as these often cause clashes with other short names, and there's very little benifit.
			if (strlen($name) < 6) {
				continue;
			}
			
			zenario_minify::$longNames[] = $lib. '.'. $name. '(';
			
			//Strip out several letters from the names that have low information value.
			//This specific list was chosen by experimenting with and finding different
			//combinations that don't cause name-clashes later.
			$name = preg_replace('@[^cdfglmnprstvy2]@', '', strtolower($name));
			
			//The max int in JavaScript is 9007199254740991.
			//Throw away a few digits on the left if needed so that we stay well below this number.
			$name = substr($name, -13);
			
			//Catch the case where we're about to have a number starting with a 0.
			//Put (what will be) a 1 in front to not lose that information.
			if ($name[0] == 'c') {
				$name = 'd'. $name;
			}
			
			//Convert the resulting string into a number
			$name = strtr($name, 'cdfglmnprstvy', '0123456789abc');
			$name = base_convert($name, 13, 10);
			
			//Apply a mod to greatly reduce the length of the short name.
			//46655 here was chosen because we're going to use base 36 later on, and it's 36 ** 3 - 1,
			//which keeps the lengths all under 3 characters, yet is also large enough to avoid clashes
			//with the whitelist of names to shorten that we use.
			//I did find a few smaller numbers, e.g. 6114 gave the smallest average length. However this
			//was only 4% shorter on average, and I'd like more name-space than this so that any function we
			//add in the future is less likely to clash.
			$name = (int) $name;
			$name = $name % 46655;
			
			//Take this number and convert it into base 36 to get something that's valid to use
			//as a short name.
			$name = '_'. base_convert($name, 10, 36);
				
			zenario_minify::$shortNames[] = $lib. '.'. $name. '(';
		}
	}
	
	
	$isCSS = $ext == '.css';
	$yamlToJSON = $ext == '.yaml';
	$output = [];
	
	if ($yamlToJSON) {
		$srcFile = $dir. $file. $ext;
		$minFile = $dir. $file. '.json';
	} else {
		$srcFile = $dir. $file. $ext;
		$minFile = $dir. $file. '.min'. $ext;
		//$mapFile = $dir. $file. '.min'. '.map';
	}
	
	if ($string === false && !file_exists($srcFile)) {
		return;
	}
	
	$v = '';
	if ($level > 2) {
		echo ':'. $srcFile. "\n";
		
		if (!$isCSS) {
			$v = '--warning_level VERBOSE ';
		} else {
			$v = '-v ';
		}
	}
	
	if ($string !== false
	 || !file_exists($dir. $file. '.pack.js')) {
		
		$svnAdd = false;
		$modified = true;
		$needsreverting = false;
		
		if ($string === false && is_dir('.svn')) {
			$svnAdd = !file_exists($minFile) && zenario_minify::svnStatus($minFile) != '!';
			
			$modified = 
				RECOMPRESS_EVERYTHING ||
				zenario_minify::svnStatus($srcFile) ||
				zenario_minify::svnStatus($minFile) == '!';
			
			if (!$svnAdd && !$modified) {
				$needsreverting = zenario_minify::svnStatus($minFile);
			}
		}
		
		if ($modified || ($needsreverting && !IGNORE_REVERTS)) {
			if ($string === false) {
				if ($needsreverting && !IGNORE_REVERTS) {
					echo '-reverting '. $minFile. "\n";
				} else {
					echo '-compressing '. $srcFile. "\n";
				}
			}
			
			if ($level > 1) {
				if ($needsreverting && !IGNORE_REVERTS) {
					exec('svn revert '.
								escapeshellarg($minFile)
						);
				} else {
					//Make a temp file if needed
					if ($string !== false) {
						$minFile = tempnam(sys_get_temp_dir(), 'min');
						$tmpFile = tempnam(sys_get_temp_dir(), 'js');
						file_put_contents($tmpFile, applyCompilationMacros($string, $dir, $file));
						$srcFile = $tmpFile;
					}
					//For our JavaScript files, automatically add
					//foreach-style loops that also automatically add a call
					//to .hasOwnProperty() for safety.
					//Note that JavaScript works slightly differently to php; if you only
					//specifiy one variable then it becomes the key, not the value
					if (!$isCSS
					 && !$yamlToJSON
					 && substr($dir, 0, 13) != 'zenario/libs/') {
						$tmpFile = tempnam(sys_get_temp_dir(), 'js');
						file_put_contents($tmpFile, applyCompilationMacros(file_get_contents($srcFile), $dir, $file));
						$srcFile = $tmpFile;
					}
					
					
					if ($yamlToJSON) {
						$tags = Spyc::YAMLLoad($srcFile);
						file_put_contents($minFile, json_encode($tags, JSON_FORCE_OBJECT));
					
					} elseif (!$isCSS) {
						if (DEBUG_DONT_MINIFY) {
							//Use this line to skip the minification, useful for debugging the compilation macros
							copy($srcFile, $minFile);
						
						} else {
							//Use this line to actually run minification
							exec('java -jar '. escapeshellarg(CLOSURE_COMPILER_PATH). ' '. $v.
								' --language_in ECMASCRIPT_2019 --language_out ECMASCRIPT_2015 --compilation_level SIMPLE'.
								' --js_output_file '.
										escapeshellarg($minFile).
								//Code to generate a source-map if needed
									//' --source_map_format=V3 --create_source_map '.
									//	escapeshellarg($mapFile).
									' --js '. 
										escapeshellarg($srcFile)
								, $output);
						}
						
						if ($minFile == $dir. 'body.min.js') {
							//Special case for zenario/body.js
							//We'll want to manually initialise this with specific variables, so chop off the standard variables at the end.
							$contents = file_get_contents($minFile);
							$contents = explode(';ZENARIO_END_OF_SECTION()', $contents);
							file_put_contents($minFile, $contents[0]);
							file_put_contents($dir. 'body.anchor-fix.min.js', $contents[1]);
						}
						
					} else {
						exec('java -jar '. escapeshellarg(YUI_COMPRESSOR_PATH). ' --type '. ($isCSS? 'css' : 'js'). ' '. $v. '--line-break 150 -o '.
									escapeshellarg($minFile).
								' '. 
									escapeshellarg($srcFile)
							, $output);
					}
				}
			}
			
			if ($svnAdd) {
				echo '-svn adding '. $minFile. "\n";
				
				if ($level > 1) {
					exec('svn add '.
								escapeshellarg($minFile)
						);
				}
			}
		}
	}
	
	if ($string !== false) {
		 if ($javascript = file_get_contents($minFile)) {
		 	return $javascript;
		 } else {
		 	return implode("\n", $output);
		 }
	}
}