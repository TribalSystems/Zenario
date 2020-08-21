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


define('YUI_COMPRESSOR_PATH', 'zenario/libs/manually_maintained/bsd/yuicompressor/yuicompressor-2.4.8.jar');
define('CLOSURE_COMPILER_PATH', 'zenario/libs/not_to_redistribute/closure-compiler/compiler.jar');

//Use the closure compiler for .js files if it has been installed
//(otherwise we must use YUI Compressor which gives slightly larger filesizes).
define('USE_CLOSURE_COMPILER', file_exists(CLOSURE_COMPILER_PATH));

define('DEBUG_DONT_MINIFY', false);


function displayUsage() {
	echo
"A tool for minifying JavaScript used by Zenario;
this is a wrapper for calling YUI Compressor (http://developer.yahoo.com/yui/compressor/)
or Closure Compiler (https://developers.google.com/closure/compiler/) on all relevant files.

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

Notes:
  * The Zenario download does not come with a copy of Closure Compiler to save space,
 	but if you download a copy and put it in the right place then this program will use it.
  * If you have svn, this script will only minify files that svn says are new or modified.

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
	public static $shortNames = [
		'_.after(' => '_._a(',
		'_.allKeys(' => '_._aK(',
		'_.assign(' => '_._as(',
		'_.before(' => '_._b(',
		'_.bind(' => '_._bi(',
		'_.bindAll(' => '_._bA(',
		'_.chain(' => '_._c(',
		'_.clone(' => '_._cl(',
		'_.collect(' => '_._co(',
		'_.countBy(' => '_._cB(',
		'_.create(' => '_._cr(',
		'_.debounce(' => '_._d(',
		'_.defaults(' => '_._de(',
		'_.difference(' => '_._di(',
		'_.drop(' => '_._dr(',
		'_.each(' => '_._e(',
		'_.escape(' => '_._es(',
		'_.every(' => '_._ev(',
		'_.extend(' => '_._ex(',
		'_.extendOwn(' => '_._eO(',
		'_.filter(' => '_._f(',
		'_.find(' => '_._fi(',
		'_.findIndex(' => '_._fI(',
		'_.findKey(' => '_._fK(',
		'_.findLastIndex(' => '_._fLI(',
		'_.findWhere(' => '_._fW(',
		'_.flatten(' => '_._fl(',
		'_.foldl(' => '_._fo(',
		'_.forEach(' => '_._fE(',
		'_.functions(' => '_._fu(',
		'_.groupBy(' => '_._gB(',
		'_.head(' => '_._h(',
		'_.identity(' => '_._i(',
		'_.include(' => '_._in(',
		'_.indexBy(' => '_._iB(',
		'_.indexOf(' => '_._iO(',
		'_.isArguments(' => '_._iA(',
		'_.isArray(' => '_._isAr(',
		'_.isBoolean(' => '_._isBo(',
		'_.isDate(' => '_._iD(',
		'_.isElement(' => '_._iE(',
		'_.isEmpty(' => '_._isEm(',
		'_.isEqual(' => '_._isEq(',
		'_.isError(' => '_._isEr(',
		'_.isFinite(' => '_._iF(',
		'_.isFunction(' => '_._isFu(',
		'_.isMatch(' => '_._iM(',
		'_.isNaN(' => '_._iNN(',
		'_.isNull(' => '_._iN(',
		'_.isNumber(' => '_._isNu(',
		'_.isObject(' => '_._isOb(',
		'_.isRegExp(' => '_._iRE(',
		'_.isString(' => '_._iS(',
		'_.isUndefined(' => '_._iU(',
		'_.iteratee(' => '_._it(',
		'_.keys(' => '_._k(',
		'_.last(' => '_._l(',
		'_.lastIndexOf(' => '_._lIO(',
		'_.map(' => '_._m(',
		'_.mapObject(' => '_._mO(',
		'_.matcher(' => '_._ma(',
		'_.memoize(' => '_._me(',
		'_.negate(' => '_._n(',
		'_.noConflict(' => '_._nC(',
		'_.noop(' => '_._no(',
		'_.object(' => '_._o(',
		'_.omit(' => '_._om(',
		'_.once(' => '_._on(',
		'_.pairs(' => '_._p(',
		'_.partial(' => '_._pa(',
		'_.pick(' => '_._pi(',
		'_.pluck(' => '_._pl(',
		'_.property(' => '_._pr(',
		'_.propertyOf(' => '_._pO(',
		'_.random(' => '_._r(',
		'_.range(' => '_._ra(',
		'_.reduce(' => '_._re(',
		'_.reduceRight(' => '_._rR(',
		'_.sample(' => '_._s(',
		'_.select(' => '_._se(',
		'_.shuffle(' => '_._sh(',
		'_.size(' => '_._si(',
		'_.some(' => '_._so(',
		'_.sortBy(' => '_._sB(',
		'_.sortedIndex(' => '_._sI(',
		'_.tail(' => '_._t(',
		'_.take(' => '_._ta(',
		'_.template(' => '_._te(',
		'_.throttle(' => '_._th(',
		'_.times(' => '_._ti(',
		'_.toArray(' => '_._tA(',
		'_.unescape(' => '_._u(',
		'_.union(' => '_._un(',
		'_.uniqueId(' => '_._uI(',
		'_.values(' => '_._v(',
		'_.where(' => '_._w(',
		'_.without(' => '_._wi(',
		'_.wrap(' => '_._wr(',
		'_.zip(' => '_._z(',
		'zenario.AJAXLink(' => 'zenario._AJL(',
		'zenario.actAfterDelayIfNotSuperseded(' => 'zenario._aADINS(',
		'zenario.addAmp(' => 'zenario._aA(',
		'zenario.addBasePath(' => 'zenario._aBP(',
		'zenario.addClassesToColorbox(' => 'zenario._aCTC(',
		'zenario.addJQueryElements(' => 'zenario._aJQE(',
		'zenario.addLibPointers(' => 'zenario._aLP(',
		'zenario.addPluginJavaScript(' => 'zenario._aPJS(',
		'zenario.addRequest(' => 'zenario._aR(',
		'zenario.addStyles(' => 'zenario._aS(',
		'zenario.addTabIdToURL(' => 'zenario._aTITU(',
		'zenario.ajax(' => 'zenario._a(',
		'zenario.applyCompilationMacros(' => 'zenario._aCM(',
		'zenario.applyMergeFields(' => 'zenario._aMF(',
		'zenario.between(' => 'zenario._b(',
		'zenario.browserIsChrome(' => 'zenario._bIC(',
		'zenario.browserIsEdge(' => 'zenario._bIE(',
		'zenario.browserIsFirefox(' => 'zenario._bIF(',
		'zenario.browserIsIE(' => 'zenario._bII(',
		'zenario.browserIsOpera(' => 'zenario._bIO(',
		'zenario.browserIsRetina(' => 'zenario._bIR(',
		'zenario.browserIsSafari(' => 'zenario._bIS(',
		'zenario.browserIsWebKit(' => 'zenario._bIWK(',
		'zenario.browserIsiOS(' => 'zenario._brIsOS(',
		'zenario.browserIsiPad(' => 'zenario._bIP(',
		'zenario.browserIsiPhone(' => 'zenario._brIsPh(',
		'zenario.buttonClick(' => 'zenario._bC(',
		'zenario.callScript(' => 'zenario._cS(',
		'zenario.callback(' => 'zenario._c(',
		'zenario.canCopy(' => 'zenario._cC(',
		'zenario.checkDataRevisionNumber(' => 'zenario._cDRN(',
		'zenario.checkForHashChanges(' => 'zenario._cFHC(',
		'zenario.checkPasswordStrength(' => 'zenario._cPS(',
		'zenario.checkSessionStorage(' => 'zenario._cSS(',
		'zenario.clearAllDelays(' => 'zenario._cAD(',
		'zenario.clearDateField(' => 'zenario._cDF(',
		'zenario.clone(' => 'zenario._cl(',
		'zenario.closeTooltip(' => 'zenario._cT(',
		'zenario.copy(' => 'zenario._co(',
		'zenario.createZenarioLibrary(' => 'zenario._cZL(',
		'zenario.cssEscape(' => 'zenario._cE(',
		'zenario.dataRev(' => 'zenario._dR(',
		'zenario.dateFieldKeyUp(' => 'zenario._dFKU(',
		'zenario.decodeItemIdForOrganizer(' => 'zenario._dIIFO(',
		'zenario.decodeItemIdForStorekeeper(' => 'zenario._dIIFS(',
		'zenario.defined(' => 'zenario._d(',
		'zenario.disableScrolling(' => 'zenario._dS(',
		'zenario.drawMicroTemplate(' => 'zenario._dMT(',
		'zenario.enableFullScreen(' => 'zenario._eFS(',
		'zenario.enableScrolling(' => 'zenario._eS(',
		'zenario.enc(' => 'zenario._e(',
		'zenario.encodeItemIdForOrganizer(' => 'zenario._eIIFO(',
		'zenario.encodeItemIdForStorekeeper(' => 'zenario._eIIFS(',
		'zenario.engToBoolean(' => 'zenario._eTB(',
		'zenario.exitFullScreen(' => 'zenario._exFuSc(',
		'zenario.extensionOf(' => 'zenario._eO(',
		'zenario.fireChangeEvent(' => 'zenario._fCE(',
		'zenario.fixJSON(' => 'zenario._fJ(',
		'zenario.formSubmit(' => 'zenario._fS(',
		'zenario.formatDate(' => 'zenario._fD(',
		'zenario.generateMicroTemplate(' => 'zenario._gMT(',
		'zenario.get(' => 'zenario._g(',
		'zenario.getContainerIdFromEl(' => 'zenario._gCIFE(',
		'zenario.getContainerIdFromSlotName(' => 'zenario._gCIFSN(',
		'zenario.getEggIdFromEl(' => 'zenario._gEIFE(',
		'zenario.getMouseX(' => 'zenario._gMX(',
		'zenario.getMouseY(' => 'zenario._gMY(',
		'zenario.getSlotnameFromEl(' => 'zenario._gSFE(',
		'zenario.goToURL(' => 'zenario._gTU(',
		'zenario.grecaptcha(' => 'zenario._gr(',
		'zenario.grecaptchaIsLoaded(' => 'zenario._gIL(',
		'zenario.handlePluginAJAX(' => 'zenario._hPA(',
		'zenario.hasInlineTag(' => 'zenario._hIT(',
		'zenario.htmlspecialchars(' => 'zenario._h(',
		'zenario.httpOrhttps(' => 'zenario._hO(',
		'zenario.hypEscape(' => 'zenario._hE(',
		'zenario.inDoc(' => 'zenario._iD(',
		'zenario.inList(' => 'zenario._iL(',
		'zenario.isFullScreen(' => 'zenario._iFS(',
		'zenario.isFullScreenAvailable(' => 'zenario._iFSA(',
		'zenario.isTouchScreen(' => 'zenario._iTS(',
		'zenario.ishttps(' => 'zenario._i(',
		'zenario.jsEscape(' => 'zenario._jE(',
		'zenario.jsUnescape(' => 'zenario._jU(',
		'zenario.linkToItem(' => 'zenario._lTI(',
		'zenario.loadAutocomplete(' => 'zenario._lA(',
		'zenario.loadDatePicker(' => 'zenario._lDP(',
		'zenario.loadLibrary(' => 'zenario._lL(',
		'zenario.loadPhrases(' => 'zenario._lP(',
		'zenario.loadScript(' => 'zenario._lS(',
		'zenario.methodsOf(' => 'zenario._mO(',
		'zenario.microTemplate(' => 'zenario._mT(',
		'zenario.moduleNonAsyncAJAX(' => 'zenario._mNAA(',
		'zenario.nonAsyncAJAX(' => 'zenario._nAA(',
		'zenario.nphrase(' => 'zenario._n(',
		'zenario.off(' => 'zenario._o(',
		'zenario.outdateCachedData(' => 'zenario._oCD(',
		'zenario.parseContainerId(' => 'zenario._pCI(',
		'zenario.phrase(' => 'zenario._p(',
		'zenario.pluginAJAXLink(' => 'zenario._pAL(',
		'zenario.pluginAJAXURL(' => 'zenario._pA(',
		'zenario.pluginClassAJAX(' => 'zenario._pCA(',
		'zenario.pluginVisitorTUIXLink(' => 'zenario._pVTL(',
		'zenario.recordRequestsInURL(' => 'zenario._rRIU(',
		'zenario.refreshPluginSlot(' => 'zenario._rPS(',
		'zenario.refreshSlot(' => 'zenario._rS(',
		'zenario.registerPhrases(' => 'zenario._rP(',
		'zenario.removeClassesToColorbox(' => 'zenario._rCTC(',
		'zenario.removeLinkStatus(' => 'zenario._rLS(',
		'zenario.replacePluginSlotContents(' => 'zenario._rPSC(',
		'zenario.resize(' => 'zenario._r(',
		'zenario.resizeColorbox(' => 'zenario._rC(',
		'zenario.rightHandedSubStr(' => 'zenario._rHSS(',
		'zenario.round(' => 'zenario._ro(',
		'zenario.sClear(' => 'zenario._sC(',
		'zenario.sGetItem(' => 'zenario._sGI(',
		'zenario.sSetItem(' => 'zenario._sSI(',
		'zenario.scrollLeft(' => 'zenario._sL(',
		'zenario.scrollToEl(' => 'zenario._sTE(',
		'zenario.scrollToSlotTop(' => 'zenario._sTST(',
		'zenario.scrollTop(' => 'zenario._sT(',
		'zenario.sendSignal(' => 'zenario._sS(',
		'zenario.setActiveClass(' => 'zenario._sAC(',
		'zenario.setChildrenToTheSameHeight(' => 'zenario._sCTTSH(',
		'zenario.setSessionStorage(' => 'zenario._sSS(',
		'zenario.showFileLink(' => 'zenario._sFL(',
		'zenario.showFloatingBoxLink(' => 'zenario._sFBL(',
		'zenario.showImageLink(' => 'zenario._sIL(',
		'zenario.showSingleSlotLink(' => 'zenario._sSSL(',
		'zenario.showStandalonePageLink(' => 'zenario._sSPL(',
		'zenario.shrtNms(' => 'zenario._sN(',
		'zenario.slot(' => 'zenario._s(',
		'zenario.splitFlagsFromMessage(' => 'zenario._sFFM(',
		'zenario.stop(' => 'zenario._st(',
		'zenario.submitFormReturningHtml(' => 'zenario._sFRH(',
		'zenario.tidyLibPointers(' => 'zenario._tLP(',
		'zenario.tinyMCEGetContent(' => 'zenario._tMGC(',
		'zenario.toObject(' => 'zenario._tO(',
		'zenario.tooltips(' => 'zenario._t(',
		'zenario.tooltipsUsing(' => 'zenario._tU(',
		'zenario.uneschyp(' => 'zenario._u(',
		'zenario.unfun(' => 'zenario._un(',
		'zenario.unpackAndMerge(' => 'zenario._uAM(',
		'zenario.updatePasswordNotifier(' => 'zenario._uPN(',
		'zenario.urlRequest(' => 'zenario._uR(',
		'zenario.versionOfIE(' => 'zenario._vOI(',
		'zenario.visitorTUIXLink(' => 'zenario._vTL(',
		'zenario_conductor.backLink(' => 'zenario_conductor._bL(',
		'zenario_conductor.commandEnabled(' => 'zenario_conductor._cE(',
		'zenario_conductor.confirmOnClose(' => 'zenario_conductor._cOC(',
		'zenario_conductor.confirmOnCloseMessage(' => 'zenario_conductor._cOCM(',
		'zenario_conductor.enabled(' => 'zenario_conductor._e(',
		'zenario_conductor.getSlot(' => 'zenario_conductor._gS(',
		'zenario_conductor.goBack(' => 'zenario_conductor._gB(',
		'zenario_conductor.link(' => 'zenario_conductor._l(',
		'zenario_conductor.refresh(' => 'zenario_conductor._r(',
		'zenario_conductor.resetVarsOnBrowserBackNav(' => 'zenario_conductor._rVOBBN(',
		'zenario_conductor.setCommands(' => 'zenario_conductor._sC(',
		'zenarioT.action(' => 'zenarioT._a(',
		'zenarioT.canDoHTML5Upload(' => 'zenarioT._cDHTML5U(',
		'zenarioT.checkActionUnique(' => 'zenarioT._cAU(',
		'zenarioT.checkFunctionExists(' => 'zenarioT._cFE(',
		'zenarioT.csvToObject(' => 'zenarioT._cTO(',
		'zenarioT.disableFileDragDrop(' => 'zenarioT._dFDD(',
		'zenarioT.div(' => 'zenarioT._d(',
		'zenarioT.doEval(' => 'zenarioT._dE(',
		'zenarioT.doHTML5Upload(' => 'zenarioT._dHTML5U(',
		'zenarioT.doNextUpload(' => 'zenarioT._dNU(',
		'zenarioT.eval(' => 'zenarioT._e(',
		'zenarioT.filter(' => 'zenarioT._f(',
		'zenarioT.find(' => 'zenarioT._fi(',
		'zenarioT.form(' => 'zenarioT._fo(',
		'zenarioT.getSortedIdsOfTUIXElements(' => 'zenarioT._gSIOTE(',
		'zenarioT.hidden(' => 'zenarioT._h(',
		'zenarioT.html(' => 'zenarioT._ht(',
		'zenarioT.input(' => 'zenarioT._i(',
		'zenarioT.keepTrying(' => 'zenarioT._kT(',
		'zenarioT.label(' => 'zenarioT._l(',
		'zenarioT.microTemplate(' => 'zenarioT._mT(',
		'zenarioT.numberFormat(' => 'zenarioT._nF(',
		'zenarioT.onChangeOrSearch(' => 'zenarioT._oCOS(',
		'zenarioT.onbeforeunload(' => 'zenarioT._o(',
		'zenarioT.option(' => 'zenarioT._op(',
		'zenarioT.parse(' => 'zenarioT._pa(',
		'zenarioT.prop(' => 'zenarioT._pr(',
		'zenarioT.resizeImage(' => 'zenarioT._rI(',
		'zenarioT.select(' => 'zenarioT._s(',
		'zenarioT.setButtonKin(' => 'zenarioT._sBK(',
		'zenarioT.setHTML5UploadFromDragDrop(' => 'zenarioT._sHTML5UFDD(',
		'zenarioT.setKin(' => 'zenarioT._sK(',
		'zenarioT.showDevTools(' => 'zenarioT._sDT(',
		'zenarioT.sortArray(' => 'zenarioT._sA(',
		'zenarioT.sortArrayByOrd(' => 'zenarioT._sABO(',
		'zenarioT.sortArrayByOrdinal(' => 'zenarioT._soArByOr(',
		'zenarioT.sortArrayDesc(' => 'zenarioT._sAD(',
		'zenarioT.sortArrayForOrganizer(' => 'zenarioT._sAFO(',
		'zenarioT.sortArrayWithGrouping(' => 'zenarioT._sAWG(',
		'zenarioT.sortLogic(' => 'zenarioT._sL(',
		'zenarioT.span(' => 'zenarioT._sp(',
		'zenarioT.splitDataFromErrorMessage(' => 'zenarioT._sDFEM(',
		'zenarioT.stopDefault(' => 'zenarioT._sD(',
		'zenarioT.stopFileDragDrop(' => 'zenarioT._sFDD(',
		'zenarioT.stopTrying(' => 'zenarioT._sT(',
		'zenarioT.tuixToArray(' => 'zenarioT._tTA(',
		'zenarioT.uploadDone(' => 'zenarioT._uD(',
		'zenarioT.uploadProgress(' => 'zenarioT._uP(',
		'zenarioA.AJAXErrorHandler(' => 'zenarioA._AJEH(',
		'zenarioA.SKInit(' => 'zenarioA._SKI(',
		'zenarioA.addJQueryElements(' => 'zenarioA._aJQE(',
		'zenarioA.addLinkStatus(' => 'zenarioA._aLS(',
		'zenarioA.addMediaToTinyMCE(' => 'zenarioA._aMTTM(',
		'zenarioA.addNewReusablePlugin(' => 'zenarioA._aNRP(',
		'zenarioA.addNewWireframePlugin(' => 'zenarioA._aNWP(',
		'zenarioA.adjustBox(' => 'zenarioA._aB(',
		'zenarioA.cancelMovePlugin(' => 'zenarioA._cMP(',
		'zenarioA.checkCookiesEnabled(' => 'zenarioA._cCE(',
		'zenarioA.checkForEdits(' => 'zenarioA._cFE(',
		'zenarioA.checkIfBoxIsOpen(' => 'zenarioA._cIBIO(',
		'zenarioA.checkSlotsBeingEdited(' => 'zenarioA._cSBE(',
		'zenarioA.checkSpecificPerms(' => 'zenarioA._cSP(',
		'zenarioA.checkSpecificPermsOnThisPage(' => 'zenarioA._cSPOTP(',
		'zenarioA.clearToast(' => 'zenarioA._cT(',
		'zenarioA.clickOtherTutorialVideo(' => 'zenarioA._cOTV(',
		'zenarioA.closeBox(' => 'zenarioA._cB(',
		'zenarioA.closeBoxHandler(' => 'zenarioA._cBH(',
		'zenarioA.closeFloatingBox(' => 'zenarioA._cFB(',
		'zenarioA.closeInfoBox(' => 'zenarioA._cIB(',
		'zenarioA.closeSlotControls(' => 'zenarioA._cSC(',
		'zenarioA.closeSlotControlsAfterDelay(' => 'zenarioA._cSCAD(',
		'zenarioA.copy(' => 'zenarioA._c(',
		'zenarioA.copyContents(' => 'zenarioA._cC(',
		'zenarioA.copyEmbedHTML(' => 'zenarioA._cEH(',
		'zenarioA.copyEmbedLink(' => 'zenarioA._cEL(',
		'zenarioA.cutContents(' => 'zenarioA._cuCo(',
		'zenarioA.debug(' => 'zenarioA._d(',
		'zenarioA.doDownload(' => 'zenarioA._dD(',
		'zenarioA.doMovePlugin(' => 'zenarioA._dMP(',
		'zenarioA.doMovePlugin2(' => 'zenarioA._dMP2(',
		'zenarioA.dontCloseSlotControls(' => 'zenarioA._dCSC(',
		'zenarioA.draft(' => 'zenarioA._dr(',
		'zenarioA.draftDoCallback(' => 'zenarioA._dDC(',
		'zenarioA.draftSetCallback(' => 'zenarioA._dSC(',
		'zenarioA.enableDragDropUploadInTinyMCE(' => 'zenarioA._eDDUITM(',
		'zenarioA.fileBrowser(' => 'zenarioA._fB(',
		'zenarioA.floatingBox(' => 'zenarioA._flBo(',
		'zenarioA.formatFilesizeNicely(' => 'zenarioA._fFN(',
		'zenarioA.formatOrganizerItemName(' => 'zenarioA._fOIN(',
		'zenarioA.formatSKItemField(' => 'zenarioA._fSIF(',
		'zenarioA.generateRandomString(' => 'zenarioA._gRS(',
		'zenarioA.getDefaultLanguageName(' => 'zenarioA._gDLN(',
		'zenarioA.getGridSlotDetails(' => 'zenarioA._gGSD(',
		'zenarioA.getItemFromOrganizer(' => 'zenarioA._gIFO(',
		'zenarioA.getSKBodyClass(' => 'zenarioA._gSBC(',
		'zenarioA.getSKItem(' => 'zenarioA._gSI(',
		'zenarioA.getSkinDesc(' => 'zenarioA._gSD(',
		'zenarioA.hideAJAXLoader(' => 'zenarioA._hAL(',
		'zenarioA.hidePlugin(' => 'zenarioA._hP(',
		'zenarioA.infoBox(' => 'zenarioA._iB(',
		'zenarioA.initTutorialSlideshow(' => 'zenarioA._iTS(',
		'zenarioA.isHtaccessWorking(' => 'zenarioA._iHW(',
		'zenarioA.loggedOut(' => 'zenarioA._lO(',
		'zenarioA.loggedOutIframeCheck(' => 'zenarioA._lOIC(',
		'zenarioA.longToast(' => 'zenarioA._lT(',
		'zenarioA.lookupFileDetails(' => 'zenarioA._lFD(',
		'zenarioA.movePlugin(' => 'zenarioA._mP(',
		'zenarioA.multipleLanguagesEnabled(' => 'zenarioA._mLE(',
		'zenarioA.notification(' => 'zenarioA._n(',
		'zenarioA.nowDoingSomething(' => 'zenarioA._nDS(',
		'zenarioA.onunload(' => 'zenarioA._o(',
		'zenarioA.openBox(' => 'zenarioA._oB(',
		'zenarioA.openMenuAdminBox(' => 'zenarioA._oMAB(',
		'zenarioA.openSlotControls(' => 'zenarioA._oSC(',
		'zenarioA.organizerQuick(' => 'zenarioA._oQ(',
		'zenarioA.organizerSelect(' => 'zenarioA._oS(',
		'zenarioA.overwriteContents(' => 'zenarioA._oC(',
		'zenarioA.pasteContents(' => 'zenarioA._pC(',
		'zenarioA.pickNewPlugin(' => 'zenarioA._pNP(',
		'zenarioA.pluginSlotEditSettings(' => 'zenarioA._pSES(',
		'zenarioA.refreshAllSlotsWithCutCopyPaste(' => 'zenarioA._rASWCCP(',
		'zenarioA.reloadMenuPlugins(' => 'zenarioA._rMP(',
		'zenarioA.rememberToast(' => 'zenarioA._rT(',
		'zenarioA.removePlugin(' => 'zenarioA._rP(',
		'zenarioA.replacePluginSlot(' => 'zenarioA._rPS(',
		'zenarioA.savePageMode(' => 'zenarioA._sPM(',
		'zenarioA.scanHyperlinksAndDisplayStatus(' => 'zenarioA._sHADS(',
		'zenarioA.setDocumentURL(' => 'zenarioA._sDU(',
		'zenarioA.setEditorField(' => 'zenarioA._sEF(',
		'zenarioA.setImageURL(' => 'zenarioA._sIU(',
		'zenarioA.setLinkPickerOnTinyMCE(' => 'zenarioA._sLPOTM(',
		'zenarioA.setLinkURL(' => 'zenarioA._sLU(',
		'zenarioA.setModuleInfo(' => 'zenarioA._sMI(',
		'zenarioA.setSlotParents(' => 'zenarioA._sSP(',
		'zenarioA.setTooltipIfTooLarge(' => 'zenarioA._sTITL(',
		'zenarioA.showAJAXLoader(' => 'zenarioA._sAL(',
		'zenarioA.showHelp(' => 'zenarioA._sH(',
		'zenarioA.showMessage(' => 'zenarioA._sM(',
		'zenarioA.showPagePreview(' => 'zenarioA._sPP(',
		'zenarioA.showPlugin(' => 'zenarioA._sP(',
		'zenarioA.showTutorial(' => 'zenarioA._sT(',
		'zenarioA.slotParentMouseOut(' => 'zenarioA._sPMO(',
		'zenarioA.slotParentMouseOver(' => 'zenarioA._slPaMoOv(',
		'zenarioA.swapContents(' => 'zenarioA._sC(',
		'zenarioA.tinyMCEPasteRreprocess(' => 'zenarioA._tMPR(',
		'zenarioA.toast(' => 'zenarioA._t(',
		'zenarioA.toastOrNoToast(' => 'zenarioA._tONT(',
		'zenarioA.toggleShowGrid(' => 'zenarioA._tSG(',
		'zenarioA.toggleShowHelpTourNextTime(' => 'zenarioA._tSHTNT(',
		'zenarioA.tooltips(' => 'zenarioA._to(',
		'zenarioA.translationsEnabled(' => 'zenarioA._tE(',
		'zenarioAB.adminParentPermChange(' => 'zenarioAB._aPPC(',
		'zenarioAB.adminPermChange(' => 'zenarioAB._aPC(',
		'zenarioAB.clickTab(' => 'zenarioAB._cT(',
		'zenarioAB.closeBox(' => 'zenarioAB._cB(',
		'zenarioAB.contentTitleChange(' => 'zenarioAB._cTC(',
		'zenarioAB.cutText(' => 'zenarioAB._cuTe(',
		'zenarioAB.enableOrDisableSite(' => 'zenarioAB._eODS(',
		'zenarioAB.generateAlias(' => 'zenarioAB._gA(',
		'zenarioAB.makeFieldAsTallAsPossible(' => 'zenarioAB._mFATAP(',
		'zenarioAB.openBox(' => 'zenarioAB._oB(',
		'zenarioAB.openSiteSettings(' => 'zenarioAB._oSS(',
		'zenarioAB.previewDateFormat(' => 'zenarioAB._pDF(',
		'zenarioAB.previewDateFormatGo(' => 'zenarioAB._pDFG(',
		'zenarioAB.setTitle(' => 'zenarioAB._sT(',
		'zenarioAB.size(' => 'zenarioAB._s(',
		'zenarioAB.start(' => 'zenarioAB._st(',
		'zenarioAB.svgSelected(' => 'zenarioAB._sS(',
		'zenarioAB.updateHash(' => 'zenarioAB._uH(',
		'zenarioAB.updateSEP(' => 'zenarioAB._uS(',
		'zenarioAB.validateAlias(' => 'zenarioAB._vA(',
		'zenarioAB.validateAliasGo(' => 'zenarioAB._vAG(',
		'zenarioAB.viewFrameworkSource(' => 'zenarioAB._vFS(',
		'zenarioAT.action(' => 'zenarioAT._a(',
		'zenarioAT.action2(' => 'zenarioAT._a2(',
		'zenarioAT.applyMergeFields(' => 'zenarioAT._aMF(',
		'zenarioAT.applyMergeFieldsToLabel(' => 'zenarioAT._aMFTL(',
		'zenarioAT.clickButton(' => 'zenarioAT._cB(',
		'zenarioAT.clickTab(' => 'zenarioAT._cT(',
		'zenarioAT.customiseOrganizerLink(' => 'zenarioAT._cOL(',
		'zenarioAT.draw(' => 'zenarioAT._d(',
		'zenarioAT.getKey(' => 'zenarioAT._gK(',
		'zenarioAT.getKeyId(' => 'zenarioAT._gKI(',
		'zenarioAT.getLastKeyId(' => 'zenarioAT._gLKI(',
		'zenarioAT.init2(' => 'zenarioAT._i2(',
		'zenarioAT.organizerQuick(' => 'zenarioAT._oQ(',
		'zenarioAT.pickItems(' => 'zenarioAT._pI(',
		'zenarioAT.setURL(' => 'zenarioAT._sU(',
		'zenarioAT.showGridOnOff(' => 'zenarioAT._sGOO(',
		'zenarioAT.slotDisabled(' => 'zenarioAT._sD(',
		'zenarioAT.sort(' => 'zenarioAT._s(',
		'zenarioAT.sortButtons(' => 'zenarioAT._sB(',
		'zenarioAT.uploadComplete(' => 'zenarioAT._uC(',
		'zenarioO.action2(' => 'zenarioO._a2(',
		'zenarioO.addWindowParentInfo(' => 'zenarioO._aWPI(',
		'zenarioO.allItemsSelected(' => 'zenarioO._aIS(',
		'zenarioO.applyMergeFields(' => 'zenarioO._aMF(',
		'zenarioO.applyMergeFieldsToLabel(' => 'zenarioO._aMFTL(',
		'zenarioO.applySmallSpaces(' => 'zenarioO._aSS(',
		'zenarioO.back(' => 'zenarioO._b(',
		'zenarioO.branch(' => 'zenarioO._br(',
		'zenarioO.callPanelOnUnload(' => 'zenarioO._cPOU(',
		'zenarioO.canFilterColumn(' => 'zenarioO._cFC(',
		'zenarioO.canSortColumn(' => 'zenarioO._cSC(',
		'zenarioO.changeFilters(' => 'zenarioO._cF(',
		'zenarioO.changePageSize(' => 'zenarioO._cPS(',
		'zenarioO.changePassword(' => 'zenarioO._cP(',
		'zenarioO.changeSortOrder(' => 'zenarioO._cSO(',
		'zenarioO.checkButtonHidden(' => 'zenarioO._cBH(',
		'zenarioO.checkCondition(' => 'zenarioO._cC(',
		'zenarioO.checkDisabled(' => 'zenarioO._cD(',
		'zenarioO.checkHiddenByFilter(' => 'zenarioO._cHBF(',
		'zenarioO.checkIfColumnPickerChangesAreAllowed(' => 'zenarioO._cICPCAA(',
		'zenarioO.checkItemButtonHidden(' => 'zenarioO._cIBH(',
		'zenarioO.checkItemPickable(' => 'zenarioO._cIP(',
		'zenarioO.checkPrefs(' => 'zenarioO._chPr(',
		'zenarioO.checkQueue(' => 'zenarioO._cQ(',
		'zenarioO.checkQueueLength(' => 'zenarioO._cQL(',
		'zenarioO.choose(' => 'zenarioO._c(',
		'zenarioO.chooseButtonActive(' => 'zenarioO._cBA(',
		'zenarioO.clearFilter(' => 'zenarioO._clFi(',
		'zenarioO.clearRefiner(' => 'zenarioO._cR(',
		'zenarioO.clearSearch(' => 'zenarioO._cS(',
		'zenarioO.closeInfoBox(' => 'zenarioO._cIB(',
		'zenarioO.closeInspectionView(' => 'zenarioO._cIV(',
		'zenarioO.closeSelectMode(' => 'zenarioO._cSM(',
		'zenarioO.collectionButtonClick(' => 'zenarioO._cBC(',
		'zenarioO.columnCssClass(' => 'zenarioO._cCC(',
		'zenarioO.columnEqual(' => 'zenarioO._cE(',
		'zenarioO.columnNotEqual(' => 'zenarioO._cNE(',
		'zenarioO.columnRawValue(' => 'zenarioO._cRV(',
		'zenarioO.columnValue(' => 'zenarioO._cV(',
		'zenarioO.convertNavPathToTagPath(' => 'zenarioO._cNPTTP(',
		'zenarioO.convertNavPathToTagPathAndRefiners(' => 'zenarioO._cNPTTPAR(',
		'zenarioO.deselectAllItems(' => 'zenarioO._dAI(',
		'zenarioO.disableInteraction(' => 'zenarioO._dI(',
		'zenarioO.doCSVExport(' => 'zenarioO._dCE(',
		'zenarioO.doSearch(' => 'zenarioO._dS(',
		'zenarioO.enableInteraction(' => 'zenarioO._eI(',
		'zenarioO.exportPanelAsCSV(' => 'zenarioO._ePAC(',
		'zenarioO.exportPanelAsExcel(' => 'zenarioO._ePAE(',
		'zenarioO.fadeOutLastButtons(' => 'zenarioO._fOLB(',
		'zenarioO.filterSetOnColumn(' => 'zenarioO._fSOC(',
		'zenarioO.followPathOnMap(' => 'zenarioO._fPOM(',
		'zenarioO.getAJAXURL(' => 'zenarioO._gA(',
		'zenarioO.getBackButtonTitle(' => 'zenarioO._gBBT(',
		'zenarioO.getCollectionButtons(' => 'zenarioO._gCB(',
		'zenarioO.getColumnFilterType(' => 'zenarioO._gCFT(',
		'zenarioO.getCurrentPage(' => 'zenarioO._gCP(',
		'zenarioO.getDataHack(' => 'zenarioO._gDH(',
		'zenarioO.getFilterValue(' => 'zenarioO._gFV(',
		'zenarioO.getFooter(' => 'zenarioO._gF(',
		'zenarioO.getFromLastPanel(' => 'zenarioO._gFLP(',
		'zenarioO.getFromToFromLink(' => 'zenarioO._gFTFL(',
		'zenarioO.getHash(' => 'zenarioO._gH(',
		'zenarioO.getHeader(' => 'zenarioO._geHe(',
		'zenarioO.getInlineButtons(' => 'zenarioO._gIB(',
		'zenarioO.getItemButtons(' => 'zenarioO._geItBu(',
		'zenarioO.getItemCSSClass(' => 'zenarioO._gICC(',
		'zenarioO.getKey(' => 'zenarioO._gK(',
		'zenarioO.getKeyId(' => 'zenarioO._gKI(',
		'zenarioO.getLastKeyId(' => 'zenarioO._gLKI(',
		'zenarioO.getNextItem(' => 'zenarioO._gNI(',
		'zenarioO.getPageCount(' => 'zenarioO._gPC(',
		'zenarioO.getPanel(' => 'zenarioO._gP(',
		'zenarioO.getPanelType(' => 'zenarioO._gPT(',
		'zenarioO.getQuickFilters(' => 'zenarioO._gQF(',
		'zenarioO.getSelectedItemFromLastPanel(' => 'zenarioO._gSIFLP(',
		'zenarioO.getShownColumns(' => 'zenarioO._gSC(',
		'zenarioO.getSortedIdsOfTUIXElements(' => 'zenarioO._gSIOTE(',
		'zenarioO.goToLastPage(' => 'zenarioO._gTLP(',
		'zenarioO.goToPage(' => 'zenarioO._gTP(',
		'zenarioO.hideCollectionButtons(' => 'zenarioO._hCB(',
		'zenarioO.hideItemButtons(' => 'zenarioO._hIB(',
		'zenarioO.hideViewOptions(' => 'zenarioO._hVO(',
		'zenarioO.implodeKeys(' => 'zenarioO._iK(',
		'zenarioO.inInspectionView(' => 'zenarioO._iIV(',
		'zenarioO.infoBox(' => 'zenarioO._iB(',
		'zenarioO.init2(' => 'zenarioO._i2(',
		'zenarioO.initNewPanelInstance(' => 'zenarioO._iNPI(',
		'zenarioO.inlineButtonClick(' => 'zenarioO._iBC(',
		'zenarioO.inspectionViewEnabled(' => 'zenarioO._iVE(',
		'zenarioO.inspectionViewItemId(' => 'zenarioO._iVII(',
		'zenarioO.invertFilter(' => 'zenarioO._iF(',
		'zenarioO.isShowableColumn(' => 'zenarioO._iSC(',
		'zenarioO.itemButtonClick(' => 'zenarioO._itBuCl(',
		'zenarioO.itemClickThrough(' => 'zenarioO._iCT(',
		'zenarioO.itemClickThroughAction(' => 'zenarioO._iCTA(',
		'zenarioO.itemClickThroughLink(' => 'zenarioO._iCTL(',
		'zenarioO.itemLanguage(' => 'zenarioO._iL(',
		'zenarioO.itemParent(' => 'zenarioO._iP(',
		'zenarioO.load(' => 'zenarioO._l(',
		'zenarioO.loadFromBranches(' => 'zenarioO._lFB(',
		'zenarioO.loadMap(' => 'zenarioO._lM(',
		'zenarioO.loadRefiner(' => 'zenarioO._lR(',
		'zenarioO.lookForBranches(' => 'zenarioO._loFoBr(',
		'zenarioO.markIfViewIsFiltered(' => 'zenarioO._mIVIF(',
		'zenarioO.maxLengthString(' => 'zenarioO._mLS(',
		'zenarioO.nextPage(' => 'zenarioO._nP(',
		'zenarioO.noItemsSelected(' => 'zenarioO._nIS(',
		'zenarioO.open(' => 'zenarioO._o(',
		'zenarioO.openInspectionView(' => 'zenarioO._oIV(',
		'zenarioO.panelProp(' => 'zenarioO._pP(',
		'zenarioO.parseNavigationPath(' => 'zenarioO._pNP(',
		'zenarioO.parseReturnLink(' => 'zenarioO._pRL(',
		'zenarioO.pathNotAllowed(' => 'zenarioO._pNA(',
		'zenarioO.pickItems(' => 'zenarioO._pI(',
		'zenarioO.prevPage(' => 'zenarioO._prPa(',
		'zenarioO.print(' => 'zenarioO._p(',
		'zenarioO.quickFilterEnabled(' => 'zenarioO._qFE(',
		'zenarioO.refreshAndShowPage(' => 'zenarioO._rASP(',
		'zenarioO.refreshIfFilterSet(' => 'zenarioO._rIFS(',
		'zenarioO.refreshPage(' => 'zenarioO._rP(',
		'zenarioO.refreshToShowItem(' => 'zenarioO._rTSI(',
		'zenarioO.reload(' => 'zenarioO._r(',
		'zenarioO.reloadButton(' => 'zenarioO._rB(',
		'zenarioO.reloadOpeningInstanceIfRelevant(' => 'zenarioO._rOIIR(',
		'zenarioO.reloadPage(' => 'zenarioO._rePa(',
		'zenarioO.resetBranches(' => 'zenarioO._reBr(',
		'zenarioO.resetPrefs(' => 'zenarioO._rePr(',
		'zenarioO.resizeColumn(' => 'zenarioO._rC(',
		'zenarioO.rowCssClass(' => 'zenarioO._rCC(',
		'zenarioO.runSearch(' => 'zenarioO._rS(',
		'zenarioO.savePrefs(' => 'zenarioO._sP(',
		'zenarioO.saveRefiner(' => 'zenarioO._sR(',
		'zenarioO.saveSearch(' => 'zenarioO._sS(',
		'zenarioO.scrollTopLevelNav(' => 'zenarioO._sTLN(',
		'zenarioO.searchAndSortItems(' => 'zenarioO._sASI(',
		'zenarioO.searchOnChange(' => 'zenarioO._sOC(',
		'zenarioO.searchOnClick(' => 'zenarioO._seOnCl(',
		'zenarioO.searchOnKeyUp(' => 'zenarioO._sOKU(',
		'zenarioO.selectAllItems(' => 'zenarioO._sAI(',
		'zenarioO.selectCreatedIds(' => 'zenarioO._sCI(',
		'zenarioO.selectItemRange(' => 'zenarioO._sIR(',
		'zenarioO.selectItems(' => 'zenarioO._sI(',
		'zenarioO.selectedItemDetails(' => 'zenarioO._sID(',
		'zenarioO.selectedItemId(' => 'zenarioO._sII(',
		'zenarioO.selectedItemIds(' => 'zenarioO._seItId(',
		'zenarioO.selectedItems(' => 'zenarioO._seIt(',
		'zenarioO.setBackButton(' => 'zenarioO._sBB(',
		'zenarioO.setButtonAction(' => 'zenarioO._sBA(',
		'zenarioO.setButtons(' => 'zenarioO._sB(',
		'zenarioO.setChooseButton(' => 'zenarioO._sCB(',
		'zenarioO.setDataAttributes(' => 'zenarioO._sDA(',
		'zenarioO.setFilterValue(' => 'zenarioO._sFV(',
		'zenarioO.setHash(' => 'zenarioO._sH(',
		'zenarioO.setNavigation(' => 'zenarioO._sN(',
		'zenarioO.setOrganizerIcons(' => 'zenarioO._sOI(',
		'zenarioO.setPanel(' => 'zenarioO._sePa(',
		'zenarioO.setPanelTitle(' => 'zenarioO._sPT(',
		'zenarioO.setSearch(' => 'zenarioO._seSe(',
		'zenarioO.setTopLevelNavScrollStatus(' => 'zenarioO._sTLNSS(',
		'zenarioO.setTopRightButtons(' => 'zenarioO._sTRB(',
		'zenarioO.setTrash(' => 'zenarioO._sT(',
		'zenarioO.setViewOptions(' => 'zenarioO._sVO(',
		'zenarioO.setWrapperClass(' => 'zenarioO._sWC(',
		'zenarioO.shortenPath(' => 'zenarioO._shPa(',
		'zenarioO.showCollectionButtons(' => 'zenarioO._shCoBu(',
		'zenarioO.showHideColumn(' => 'zenarioO._sHC(',
		'zenarioO.showHideColumnInCSV(' => 'zenarioO._sHCIC(',
		'zenarioO.showItemButtons(' => 'zenarioO._sIB(',
		'zenarioO.showViewOptions(' => 'zenarioO._shViOp(',
		'zenarioO.showViewOptions2(' => 'zenarioO._sVO2(',
		'zenarioO.showableColumns(' => 'zenarioO._sC(',
		'zenarioO.size(' => 'zenarioO._s(',
		'zenarioO.sizeButtons(' => 'zenarioO._siBu(',
		'zenarioO.sortArray(' => 'zenarioO._sA(',
		'zenarioO.stopRefreshing(' => 'zenarioO._stRe(',
		'zenarioO.switchColumnOrder(' => 'zenarioO._sCO(',
		'zenarioO.toggleAllItems(' => 'zenarioO._tAI(',
		'zenarioO.toggleFilter(' => 'zenarioO._tF(',
		'zenarioO.toggleInspectionView(' => 'zenarioO._tIV(',
		'zenarioO.toggleQuickFilter(' => 'zenarioO._tQF(',
		'zenarioO.topLevelClick(' => 'zenarioO._tLC(',
		'zenarioO.topRightButtonClick(' => 'zenarioO._tRBC(',
		'zenarioO.updateDateFilters(' => 'zenarioO._uDF(',
		'zenarioO.updateYourWorkInProgress(' => 'zenarioO._uYWIP(',
		'zenarioO.uploadComplete(' => 'zenarioO._uC(',
		'zenarioO.uploadStart(' => 'zenarioO._uS(',
		'zenarioO.viewTrash(' => 'zenarioO._vT(',
		'zenarioVO.changeFiltersAfterDelay(' => 'zenarioVO._cFAD(',
		'zenario_conductor.autoRefresh(' => 'zenario_conductor._aR(',
		'zenario_conductor.backLink(' => 'zenario_conductor._bL(',
		'zenario_conductor.cleanRequests(' => 'zenario_conductor._cR(',
		'zenario_conductor.commandEnabled(' => 'zenario_conductor._cE(',
		'zenario_conductor.confirmOnClose(' => 'zenario_conductor._cOC(',
		'zenario_conductor.confirmOnCloseMessage(' => 'zenario_conductor._cOCM(',
		'zenario_conductor.enabled(' => 'zenario_conductor._e(',
		'zenario_conductor.getCommand(' => 'zenario_conductor._gC(',
		'zenario_conductor.getSlot(' => 'zenario_conductor._gS(',
		'zenario_conductor.getToggle(' => 'zenario_conductor._gT(',
		'zenario_conductor.getToggles(' => 'zenario_conductor._geTo(',
		'zenario_conductor.getVar(' => 'zenario_conductor._gV(',
		'zenario_conductor.getVars(' => 'zenario_conductor._geVa(',
		'zenario_conductor.goBack(' => 'zenario_conductor._gB(',
		'zenario_conductor.initSlideInfoPopout(' => 'zenario_conductor._iSIP(',
		'zenario_conductor.link(' => 'zenario_conductor._l(',
		'zenario_conductor.linkToOtherContentItem(' => 'zenario_conductor._lTOCI(',
		'zenario_conductor.mergeRequests(' => 'zenario_conductor._mR(',
		'zenario_conductor.refresh(' => 'zenario_conductor._r(',
		'zenario_conductor.refreshAll(' => 'zenario_conductor._rA(',
		'zenario_conductor.reloadAfterDelay(' => 'zenario_conductor._rAD(',
		'zenario_conductor.request(' => 'zenario_conductor._re(',
		'zenario_conductor.resetVarsOnBackNav(' => 'zenario_conductor._rVOBN(',
		'zenario_conductor.resetVarsOnBrowserBackNav(' => 'zenario_conductor._rVOBBN(',
		'zenario_conductor.setCommands(' => 'zenario_conductor._sC(',
		'zenario_conductor.setToggle(' => 'zenario_conductor._sT(',
		'zenario_conductor.setVar(' => 'zenario_conductor._sV(',
		'zenario_conductor.setVars(' => 'zenario_conductor._seVa(',
		'zenario_conductor.stopAutoRefresh(' => 'zenario_conductor._sAR(',
		'zenario_conductor.transitionIn(' => 'zenario_conductor._tI(',
		'zenario_conductor.transitionOut(' => 'zenario_conductor._tO('
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
	
	//Some special custom logic for zenario core libraries
	//Attempt to replace methods with their shortnames from the list above
	$code = str_replace(array_keys(zenario_minify::$shortNames), array_values(zenario_minify::$shortNames), $code);
	
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
		
		if (!$isCSS && USE_CLOSURE_COMPILER) {
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
					
					} elseif (!$isCSS && USE_CLOSURE_COMPILER) {
						if (DEBUG_DONT_MINIFY) {
							//Use this line ot skip the minification, useful for debugging the compilation macros
							copy($srcFile, $minFile);
						
						} else {
							//Use this line to actually run minification
							exec('java -jar '. escapeshellarg(CLOSURE_COMPILER_PATH). ' '. $v. ' --language_in ECMASCRIPT5 --compilation_level SIMPLE_OPTIMIZATIONS --js_output_file '.
										escapeshellarg($minFile).
								//Code to generate a source-map if needed
									//' --source_map_format=V3 --create_source_map '.
									//	escapeshellarg($mapFile).
									' --js '. 
										escapeshellarg($srcFile)
								, $output);
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