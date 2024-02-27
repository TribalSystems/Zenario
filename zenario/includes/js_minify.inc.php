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


class zenario_minify {

	//	Zenario's minification script includes some logic to shorten long function names.
	//	All encapsulated functions in the libraries listed with names longer than five
	//	characters have their names passed through a custom-written hashing function to
	//	generate a short name, and this short name is then used in the minified copies of
	//	the libraries to reduce filesize/download size.

	public static $shortNamesWhitelist = [

		//
		//	This list of short name definitions should be copied into zenario/includes/js_minify.inc.php.
		//

		'_.allKeys(',		//'_._7fh('
		'_.assign(',		//'_._ggr('
		'_.before(',		//'_._y('
		'_.bindAll(',		//'_._acf('
		'_.collect(',		//'_._5t3('
		'_.compact(',		//'_._7w7('
		'_.compose(',		//'_._mrm('
		'_.constant(',		//'_._lbt('
		'_.contains(',		//'_._a1g('
		'_.countBy(',		//'_._mxh('
		'_.create(',		//'_._1s7('
		'_.debounce(',		//'_._6v('
		'_.default(',		//'_._205('
		'_.defaults(',		//'_._q22('
		'_.detect(',		//'_._309('
		'_.difference(',		//'_._b2g('
		'_.escape(',		//'_._16g('
		'_.extend(',		//'_._1d5('
		'_.extendOwn(',		//'_._hqz('
		'_.filter(',		//'_._40o('
		'_.findIndex(',		//'_._ift('
		'_.findKey(',		//'_._46x('
		'_.findLastIndex(',		//'_._iao('
		'_.findWhere(',		//'_._46t('
		'_.flatten(',		//'_._g9l('
		'_.forEach(',		//'_._ca('
		'_.functions(',		//'_._mlg('
		'_.groupBy(',		//'_._67i('
		'_.identity(',		//'_._xmf('
		'_.include(',		//'_._a7n('
		'_.includes(',		//'_._orn('
		'_.indexBy(',		//'_._sv('
		'_.indexOf(',		//'_._sl('
		'_.initial(',		//'_._vw('
		'_.inject(',		//'_._sg('
		'_.intersection(',		//'_._ngm('
		'_.invert(',		//'_._boz('
		'_.invoke(',		//'_._2h('
		'_.isArguments(',		//'_._voq('
		'_.isArray(',		//'_._ge1('
		'_.isArrayBuffer(',		//'_._955('
		'_.isBoolean(',		//'_._17v('
		'_.isDataView(',		//'_._fhv('
		'_.isDate(',		//'_._16w('
		'_.isElement(',		//'_._puf('
		'_.isEmpty(',		//'_._ruc('
		'_.isEqual(',		//'_._3d('
		'_.isError(',		//'_._gdx('
		'_.isFinite(',		//'_._fl3('
		'_.isFunction(',		//'_._4ty('
		'_.isMatch(',		//'_._g0c('
		'_.isNull(',		//'_._g2z('
		'_.isNumber(',		//'_._g3g('
		'_.isObject(',		//'_._16j('
		'_.isRegExp(',		//'_._gc3('
		'_.isString(',		//'_._mvq('
		'_.isSymbol(',		//'_._z7w('
		'_.isTypedArray(',		//'_._yox('
		'_.isUndefined(',		//'_._cq4('
		'_.isWeakMap(',		//'_._189('
		'_.isWeakSet(',		//'_._19s('
		'_.iteratee(',		//'_._1e4('
		'_.lastIndexOf(',		//'_._u2x('
		'_.mapObject(',		//'_._9ea('
		'_.matcher(',		//'_._9sb('
		'_.matches(',		//'_._9sc('
		'_.memoize(',		//'_._1y('
		'_.methods(',		//'_._9sp('
		'_.negate(',		//'_._tj('
		'_.noConflict(',		//'_._iys('
		'_.object(',		//'_._4z('
		'_.partial(',		//'_._d0h('
		'_.partition(',		//'_._p8l('
		'_.property(',		//'_._yt4('
		'_.propertyOf(',		//'_._kiu('
		'_.random(',		//'_._ecw('
		'_.reduce(',		//'_._11x('
		'_.reduceRight(',		//'_._b3m('
		'_.reject(',		//'_._11u('
		'_.restArguments(',		//'_._du3('
		'_.result(',		//'_._es7('
		'_.sample(',		//'_._fzd('
		'_.select(',		//'_._fsb('
		'_.shuffle(',		//'_._fjh('
		'_.sortBy(',		//'_._ger('
		'_.sortedIndex(',		//'_._zxf('
		'_.template(',		//'_._dti('
		'_.throttle(',		//'_._jco('
		'_.toArray(',		//'_._i32('
		'_.toPath(',		//'_._1dr('
		'_.transpose(',		//'_._spd('
		'_.unescape(',		//'_._bcm('
		'_.unique(',		//'_._6('
		'_.uniqueId(',		//'_._27('
		'_.values(',		//'_._1hc('
		'_.without(',		//'_._3w('
		'zenario.AJAXLink(',		//'zenario._1m('
		'zenario.actAfterDelayIfNotSuperseded(',		//'zenario._9fm('
		'zenario.addAmp(',		//'zenario._1vq('
		'zenario.addBasePath(',		//'zenario._ozg('
		'zenario.addClassesToColorbox(',		//'zenario._uzw('
		'zenario.addJQueryElements(',		//'zenario._4oz('
		'zenario.addLibPointers(',		//'zenario._ue6('
		'zenario.addRequest(',		//'zenario._ovh('
		'zenario.addStyles(',		//'zenario._fvj('
		'zenario.addTabIdToURL(',		//'zenario._mfk('
		'zenario.applyCompilationMacros(',		//'zenario._oxf('
		'zenario.applyMergeFields(',		//'zenario._yqq('
		'zenario.applyMergeFieldsN(',		//'zenario._jnw('
		'zenario.between(',		//'zenario._3s('
		'zenario.browserIsChrome(',		//'zenario._lvr('
		'zenario.browserIsEdge(',		//'zenario._l2n('
		'zenario.browserIsFirefox(',		//'zenario._m52('
		'zenario.browserIsIE(',		//'zenario._etm('
		'zenario.browserIsOpera(',		//'zenario._l4y('
		'zenario.browserIsRetina(',		//'zenario._my2('
		'zenario.browserIsSafari(',		//'zenario._mzx('
		'zenario.browserIsWebKit(',		//'zenario._cpd('
		'zenario.browserIsiOS(',		//'zenario._cpc('
		'zenario.browserIsiPad(',		//'zenario._l4r('
		'zenario.browserIsiPhone(',		//'zenario._l4w('
		'zenario.buttonClick(',		//'zenario._zkj('
		'zenario.callScript(',		//'zenario._ioe('
		'zenario.callback(',		//'zenario._mll('
		'zenario.canCopy(',		//'zenario._8qy('
		'zenario.canSetCookie(',		//'zenario._9xy('
		'zenario.checkDataRevisionNumber(',		//'zenario._2v8('
		'zenario.checkForHashChanges(',		//'zenario._7ju('
		'zenario.checkSessionStorage(',		//'zenario._nks('
		'zenario.clearAllDelays(',		//'zenario._w4g('
		'zenario.clearDateField(',		//'zenario._wht('
		'zenario.closeTooltip(',		//'zenario._s0n('
		'zenario.createZenarioLibrary(',		//'zenario._470('
		'zenario.cssEscape(',		//'zenario._f90('
		'zenario.csvEscape(',		//'zenario._in2('
		'zenario.dataRev(',		//'zenario._336('
		'zenario.dateFieldKeyUp(',		//'zenario._gk5('
		'zenario.decodeItemIdForOrganizer(',		//'zenario._x45('
		'zenario.decodeItemIdForStorekeeper(',		//'zenario._wny('
		'zenario.defined(',		//'zenario._20m('
		'zenario.disableScrolling(',		//'zenario._xva('
		'zenario.drawMicroTemplate(',		//'zenario._ej7('
		'zenario.enableFullScreen(',		//'zenario._awe('
		'zenario.enableScrolling(',		//'zenario._wdd('
		'zenario.encodeItemIdForOrganizer(',		//'zenario._an4('
		'zenario.encodeItemIdForStorekeeper(',		//'zenario._sid('
		'zenario.engToBoolean(',		//'zenario._unw('
		'zenario.exitFullScreen(',		//'zenario._3gi('
		'zenario.extensionOf(',		//'zenario._fsf('
		'zenario.fireChangeEvent(',		//'zenario._es3('
		'zenario.fixJSON(',		//'zenario._ct('
		'zenario.formSubmit(',		//'zenario._30c('
		'zenario.formatDate(',		//'zenario._33l('
		'zenario.formatTime(',		//'zenario._36p('
		'zenario.generateMicroTemplate(',		//'zenario._1mf('
		'zenario.getContainerIdFromEl(',		//'zenario._utw('
		'zenario.getContainerIdFromSlotName(',		//'zenario._w3v('
		'zenario.getEggIdFromEl(',		//'zenario._eho('
		'zenario.getMouseX(',		//'zenario._6g3('
		'zenario.getMouseY(',		//'zenario._bth('
		'zenario.getSlotnameFromEl(',		//'zenario._uqq('
		'zenario.goToURL(',		//'zenario._6h1('
		'zenario.grecaptcha(',		//'zenario._st8('
		'zenario.grecaptchaIsLoaded(',		//'zenario._17a('
		'zenario.hasInlineTag(',		//'zenario._idh('
		'zenario.htmlspecialchars(',		//'zenario._cdl('
		'zenario.httpOrhttps(',		//'zenario._5uo('
		'zenario.hypEscape(',		//'zenario._pi6('
		'zenario.inList(',		//'zenario._ash('
		'zenario.isFullScreen(',		//'zenario._5cs('
		'zenario.isFullScreenAvailable(',		//'zenario._uj1('
		'zenario.isTouchScreen(',		//'zenario._4jl('
		'zenario.ishttps(',		//'zenario._of('
		'zenario.jsEscape(',		//'zenario._gfp('
		'zenario.jsUnescape(',		//'zenario._tp0('
		'zenario.linkToItem(',		//'zenario._rqd('
		'zenario.loadAutocomplete(',		//'zenario._3ao('
		'zenario.loadDatePicker(',		//'zenario._wa9('
		'zenario.loadLibrary(',		//'zenario._nxx('
		'zenario.loadPhrases(',		//'zenario._t1a('
		'zenario.loadScript(',		//'zenario._bua('
		'zenario.manageCookies(',		//'zenario._cre('
		'zenario.methodsOf(',		//'zenario._jd6('
		'zenario.microTemplate(',		//'zenario._sbf('
		'zenario.moduleAJAX(',		//'zenario._ny('
		'zenario.moduleNonAsyncAJAX(',		//'zenario._8sp('
		'zenario.nonAsyncAJAX(',		//'zenario._w3i('
		'zenario.nphrase(',		//'zenario._b66('
		'zenario.offerDownload(',		//'zenario._srb('
		'zenario.outdateCachedData(',		//'zenario._iml('
		'zenario.parseContainerId(',		//'zenario._2i4('
		'zenario.phrase(',		//'zenario._100('
		'zenario.pluginAJAXLink(',		//'zenario._bjs('
		'zenario.pluginAJAXURL(',		//'zenario._bl6('
		'zenario.pluginShowFileLink(',		//'zenario._qii('
		'zenario.pluginShowImageLink(',		//'zenario._vgw('
		'zenario.pluginShowStandalonePageLink(',		//'zenario._nm6('
		'zenario.pluginVisitorTUIXLink(',		//'zenario._yxx('
		'zenario.readyPhrasesOnBrowser(',		//'zenario._mre('
		'zenario.recordRequestsInURL(',		//'zenario._c5e('
		'zenario.refreshPluginSlot(',		//'zenario._fyd('
		'zenario.refreshSlot(',		//'zenario._xja('
		'zenario.removeClassesToColorbox(',		//'zenario._16r('
		'zenario.removeLinkStatus(',		//'zenario._9bh('
		'zenario.replacePluginSlotContents(',		//'zenario._uab('
		'zenario.resize(',		//'zenario._35('
		'zenario.resizeColorbox(',		//'zenario._bly('
		'zenario.rightHandedSubStr(',		//'zenario._yot('
		'zenario.sClear(',		//'zenario._fax('
		'zenario.sGetItem(',		//'zenario._ou6('
		'zenario.sSetItem(',		//'zenario._z0c('
		'zenario.scrollLeft(',		//'zenario._ue7('
		'zenario.scrollToEl(',		//'zenario._7xr('
		'zenario.scrollToSlotTop(',		//'zenario._q6f('
		'zenario.scrollTop(',		//'zenario._7xu('
		'zenario.sendSignal(',		//'zenario._wyl('
		'zenario.setActiveClass(',		//'zenario._n54('
		'zenario.setChildrenToTheSameHeight(',		//'zenario._44v('
		'zenario.setSessionStorage(',		//'zenario._f59('
		'zenario.showFileLink(',		//'zenario._mau('
		'zenario.showFloatingBoxLink(',		//'zenario._oul('
		'zenario.showImageLink(',		//'zenario._r98('
		'zenario.showSingleSlotLink(',		//'zenario._bsd('
		'zenario.showStandalonePageLink(',		//'zenario._9vl('
		'zenario.shrtNms(',		//'zenario._ks('
		'zenario.splitFlagsFromMessage(',		//'zenario._by8('
		'zenario.startPoking(',		//'zenario._3fp('
		'zenario.stopPoking(',		//'zenario._3pc('
		'zenario.submitFormReturningHtml(',		//'zenario._2l7('
		'zenario.tidyLibPointers(',		//'zenario._zt('
		'zenario.tinyMCEGetContent(',		//'zenario._9px('
		'zenario.toObject(',		//'zenario._1b8('
		'zenario.tooltips(',		//'zenario._cjm('
		'zenario.tooltipsUsing(',		//'zenario._mnd('
		'zenario.uneschyp(',		//'zenario._3m2('
		'zenario.unpackAndMerge(',		//'zenario._mkd('
		'zenario.urlRequest(',		//'zenario._488('
		'zenario.versionOfIE(',		//'zenario._57v('
		'zenario.visitorTUIXLink(',		//'zenario._6xq('
		'zenarioA.AJAXErrorHandler(',		//'zenarioA._ie3('
		'zenarioA.SKInit(',		//'zenarioA._18p('
		'zenarioA.addImagePropertiesButtons(',		//'zenarioA._sn8('
		'zenarioA.addLinkStatus(',		//'zenarioA._dgm('
		'zenarioA.addMediaToTinyMCE(',		//'zenarioA._mxx('
		'zenarioA.addNewReusablePlugin(',		//'zenarioA._68p('
		'zenarioA.addNewWireframePlugin(',		//'zenarioA._syj('
		'zenarioA.adjustBox(',		//'zenarioA._88('
		'zenarioA.adminSlotWrapperClick(',		//'zenarioA._3fl('
		'zenarioA.allowSlotControlsToBeClosedOnceMore(',		//'zenarioA._cgz('
		'zenarioA.cancelMovePlugin(',		//'zenarioA._uca('
		'zenarioA.checkCookiesEnabled(',		//'zenarioA._e6m('
		'zenarioA.checkForEdits(',		//'zenarioA._jz9('
		'zenarioA.checkIfBoxIsOpen(',		//'zenarioA._l3w('
		'zenarioA.checkSlotsBeingEdited(',		//'zenarioA._x4q('
		'zenarioA.checkSpecificPerms(',		//'zenarioA._om('
		'zenarioA.checkSpecificPermsOnThisPage(',		//'zenarioA._f0b('
		'zenarioA.checkToastThisPageLoad(',		//'zenarioA._yl3('
		'zenarioA.clearMissingSlotsMessage(',		//'zenarioA._vbm('
		'zenarioA.clearToast(',		//'zenarioA._bl4('
		'zenarioA.clickOtherTutorialVideo(',		//'zenarioA._w46('
		'zenarioA.closeBox(',		//'zenarioA._1qq('
		'zenarioA.closeBoxHandler(',		//'zenarioA._ksb('
		'zenarioA.closeDebugMenu(',		//'zenarioA._ckv('
		'zenarioA.closeFloatingBox(',		//'zenarioA._7xa('
		'zenarioA.closeInfoBox(',		//'zenarioA._6ii('
		'zenarioA.closeSlotControls(',		//'zenarioA._tq0('
		'zenarioA.closeSlotControlsAfterDelay(',		//'zenarioA._3a8('
		'zenarioA.copyEmbedHTML(',		//'zenarioA._5ug('
		'zenarioA.copyEmbedLink(',		//'zenarioA._8p4('
		'zenarioA.doDownload(',		//'zenarioA._ok1('
		'zenarioA.doMovePlugin(',		//'zenarioA._bsv('
		'zenarioA.doMovePlugin2(',		//'zenarioA._9fd('
		'zenarioA.dontCloseSlotControls(',		//'zenarioA._n6('
		'zenarioA.draftDoCallback(',		//'zenarioA._op('
		'zenarioA.draftSetCallback(',		//'zenarioA._ujg('
		'zenarioA.enableDragDropUploadInTinyMCE(',		//'zenarioA._chb('
		'zenarioA.fileBrowser(',		//'zenarioA._fzw('
		'zenarioA.floatingBox(',		//'zenarioA._g82('
		'zenarioA.formatFilesizeNicely(',		//'zenarioA._v4c('
		'zenarioA.formatOrganizerItemName(',		//'zenarioA._ac1('
		'zenarioA.formatSKItemField(',		//'zenarioA._j8('
		'zenarioA.generateRandomString(',		//'zenarioA._toz('
		'zenarioA.getDefaultLanguageName(',		//'zenarioA._3fp('
		'zenarioA.getGridSlotDetails(',		//'zenarioA._9b6('
		'zenarioA.getItemFromOrganizer(',		//'zenarioA._mym('
		'zenarioA.getSKBodyClass(',		//'zenarioA._uv5('
		'zenarioA.getSKItem(',		//'zenarioA._ccf('
		'zenarioA.hasNoPriv(',		//'zenarioA._tim('
		'zenarioA.hasPriv(',		//'zenarioA._g9b('
		'zenarioA.hideAJAXLoader(',		//'zenarioA._28e('
		'zenarioA.hidePlugin(',		//'zenarioA._ygl('
		'zenarioA.imageProperties(',		//'zenarioA._2qd('
		'zenarioA.infoBox(',		//'zenarioA._28('
		'zenarioA.initTutorialSlideshow(',		//'zenarioA._bw9('
		'zenarioA.isHtaccessWorking(',		//'zenarioA._9u0('
		'zenarioA.keepSlotControlsOpen(',		//'zenarioA._6ea('
		'zenarioA.layoutCodeName(',		//'zenarioA._gfd('
		'zenarioA.loggedOut(',		//'zenarioA._lnb('
		'zenarioA.loggedOutIframeCheck(',		//'zenarioA._ty0('
		'zenarioA.longToast(',		//'zenarioA._oph('
		'zenarioA.lookupFileDetails(',		//'zenarioA._fw7('
		'zenarioA.manageToastOnReload(',		//'zenarioA._uin('
		'zenarioA.movePlugin(',		//'zenarioA._vby('
		'zenarioA.multipleLanguagesEnabled(',		//'zenarioA._4q2('
		'zenarioA.nItems(',		//'zenarioA._bj6('
		'zenarioA.notification(',		//'zenarioA._yv3('
		'zenarioA.nowDoingSomething(',		//'zenarioA._8nu('
		'zenarioA.onunload(',		//'zenarioA._azt('
		'zenarioA.openBox(',		//'zenarioA._2p('
		'zenarioA.openMenuAdminBox(',		//'zenarioA._atj('
		'zenarioA.openSlotControls(',		//'zenarioA._prf('
		'zenarioA.organizerQuick(',		//'zenarioA._296('
		'zenarioA.organizerSelect(',		//'zenarioA._1bb('
		'zenarioA.pickNewPlugin(',		//'zenarioA._7xt('
		'zenarioA.pluginCodeName(',		//'zenarioA._io('
		'zenarioA.pluginSlotEditSettings(',		//'zenarioA._y3m('
		'zenarioA.refreshChangedPluginSlot(',		//'zenarioA._r0p('
		'zenarioA.reloadMenuPlugins(',		//'zenarioA._i1x('
		'zenarioA.reloadPage(',		//'zenarioA._3ag('
		'zenarioA.rememberToast(',		//'zenarioA._xed('
		'zenarioA.removePlugin(',		//'zenarioA._j36('
		'zenarioA.replacePluginSlot(',		//'zenarioA._4zg('
		'zenarioA.savePageMode(',		//'zenarioA._p7i('
		'zenarioA.scanHyperlinksAndDisplayStatus(',		//'zenarioA._8bp('
		'zenarioA.setModuleInfo(',		//'zenarioA._to6('
		'zenarioA.setSlotParents(',		//'zenarioA._n54('
		'zenarioA.setTooltipIfTooLarge(',		//'zenarioA._5yg('
		'zenarioA.showAJAXLoader(',		//'zenarioA._fsm('
		'zenarioA.showHelp(',		//'zenarioA._17w('
		'zenarioA.showMessage(',		//'zenarioA._s34('
		'zenarioA.showPagePreview(',		//'zenarioA._2u1('
		'zenarioA.showPlugin(',		//'zenarioA._urm('
		'zenarioA.showSourceFiles(',		//'zenarioA._nbg('
		'zenarioA.showToastOnNextPageLoad(',		//'zenarioA._hwm('
		'zenarioA.showTutorial(',		//'zenarioA._on('
		'zenarioA.slotParentMouseOut(',		//'zenarioA._mt5('
		'zenarioA.slotParentMouseOver(',		//'zenarioA._8jm('
		'zenarioA.suspendStopWrapperClicks(',		//'zenarioA._kng('
		'zenarioA.switchToolbarWithSlotControlsOpen(',		//'zenarioA._m9i('
		'zenarioA.tinyMCEPasteRreprocess(',		//'zenarioA._9t9('
		'zenarioA.toggleAdminToolbar(',		//'zenarioA._x3j('
		'zenarioA.toggleShowEmptySlots(',		//'zenarioA._vp6('
		'zenarioA.toggleShowGrid(',		//'zenarioA._ltf('
		'zenarioA.toggleShowHelpTourNextTime(',		//'zenarioA._d4x('
		'zenarioA.tooltips(',		//'zenarioA._cjm('
		'zenarioA.translationsEnabled(',		//'zenarioA._8l6('
		'zenarioA.updateSlotControlsHTML(',		//'zenarioA._1uk('
		'zenarioAB.adminParentPermChange(',		//'zenarioAB._rdg('
		'zenarioAB.adminPermChange(',		//'zenarioAB._n07('
		'zenarioAB.clickTab(',		//'zenarioAB._mkf('
		'zenarioAB.closeBox(',		//'zenarioAB._1qq('
		'zenarioAB.contentTitleChange(',		//'zenarioAB._5ns('
		'zenarioAB.cutText(',		//'zenarioAB._ng7('
		'zenarioAB.enableOrDisableSite(',		//'zenarioAB._8e7('
		'zenarioAB.generateAlias(',		//'zenarioAB._ymv('
		'zenarioAB.getDockPosition(',		//'zenarioAB._qnb('
		'zenarioAB.makeFieldAsTallAsPossible(',		//'zenarioAB._dvg('
		'zenarioAB.openBox(',		//'zenarioAB._2p('
		'zenarioAB.openSiteSettings(',		//'zenarioAB._wl6('
		'zenarioAB.previewDateFormat(',		//'zenarioAB._ro('
		'zenarioAB.previewDateFormatGo(',		//'zenarioAB._9zr('
		'zenarioAB.removeHtmAndHtmlFromAlias(',		//'zenarioAB._gpp('
		'zenarioAB.removeHttpAndHttpsFromAlias(',		//'zenarioAB._dhf('
		'zenarioAB.setDockPosition(',		//'zenarioAB._f9n('
		'zenarioAB.setTitle(',		//'zenarioAB._pd('
		'zenarioAB.svgSelected(',		//'zenarioAB._4tb('
		'zenarioAB.updateHash(',		//'zenarioAB._c3r('
		'zenarioAB.updateSEP(',		//'zenarioAB._dd2('
		'zenarioAB.validateAlias(',		//'zenarioAB._2o9('
		'zenarioAB.validateAliasGo(',		//'zenarioAB._yrc('
		'zenarioAB.viewFrameworkSource(',		//'zenarioAB._h2b('
		'zenarioAT.action(',		//'zenarioAT._1st('
		'zenarioAT.action2(',		//'zenarioAT._nej('
		'zenarioAT.applyMergeFields(',		//'zenarioAT._yqq('
		'zenarioAT.applyMergeFieldsToLabel(',		//'zenarioAT._tsz('
		'zenarioAT.clickButton(',		//'zenarioAT._y14('
		'zenarioAT.clickTab(',		//'zenarioAT._mkf('
		'zenarioAT.customiseOrganizerLink(',		//'zenarioAT._yn2('
		'zenarioAT.getKey(',		//'zenarioAT._i1('
		'zenarioAT.getKeyId(',		//'zenarioAT._6ie('
		'zenarioAT.getLastKeyId(',		//'zenarioAT._v7x('
		'zenarioAT.organizerQuick(',		//'zenarioAT._296('
		'zenarioAT.pickItems(',		//'zenarioAT._bmn('
		'zenarioAT.setURL(',		//'zenarioAT._gn7('
		'zenarioAT.showGridOnOff(',		//'zenarioAT._yiu('
		'zenarioAT.slotDisabled(',		//'zenarioAT._5ib('
		'zenarioAT.sortButtons(',		//'zenarioAT._ewv('
		'zenarioAT.uploadComplete(',		//'zenarioAT._oja('
		'zenarioGM.ajaxData(',		//'zenarioGM._n('
		'zenarioGM.ajaxURL(',		//'zenarioGM._30('
		'zenarioGM.canRedo(',		//'zenarioGM._mwg('
		'zenarioGM.canUndo(',		//'zenarioGM._mvq('
		'zenarioGM.cellLabel(',		//'zenarioGM._5ud('
		'zenarioGM.change(',		//'zenarioGM._1ra('
		'zenarioGM.checkCellsEmpty(',		//'zenarioGM._os4('
		'zenarioGM.checkData(',		//'zenarioGM._m20('
		'zenarioGM.checkDataFormat(',		//'zenarioGM._ipr('
		'zenarioGM.checkDataNonZero(',		//'zenarioGM._1xy('
		'zenarioGM.checkDataNonZeroAndNumeric(',		//'zenarioGM._m3m('
		'zenarioGM.checkDataNumeric(',		//'zenarioGM._p4l('
		'zenarioGM.checkDataR(',		//'zenarioGM._yqf('
		'zenarioGM.checkIfNameUsed(',		//'zenarioGM._h3e('
		'zenarioGM.checkWhichNamesAreInUse(',		//'zenarioGM._d0f('
		'zenarioGM.clearAddToolbar(',		//'zenarioGM._iab('
		'zenarioGM.confirmDeleteSlot(',		//'zenarioGM._9e5('
		'zenarioGM.deleteCell(',		//'zenarioGM._vn9('
		'zenarioGM.disableChangingSettings(',		//'zenarioGM._41b('
		'zenarioGM.drawAddToolbar(',		//'zenarioGM._tj8('
		'zenarioGM.drawEditor(',		//'zenarioGM._zu4('
		'zenarioGM.drawOptions(',		//'zenarioGM._81i('
		'zenarioGM.editProperties(',		//'zenarioGM._hls('
		'zenarioGM.getLevels(',		//'zenarioGM._84z('
		'zenarioGM.getSlotCSSName(',		//'zenarioGM._u46('
		'zenarioGM.getSlotDescription(',		//'zenarioGM._fjt('
		'zenarioGM.isExistingLayout(',		//'zenarioGM._23i('
		'zenarioGM.markAsSaved(',		//'zenarioGM._5c4('
		'zenarioGM.microTemplate(',		//'zenarioGM._sbf('
		'zenarioGM.modeIs(',		//'zenarioGM._o3('
		'zenarioGM.modeIsNot(',		//'zenarioGM._54m('
		'zenarioGM.randomName(',		//'zenarioGM._eme('
		'zenarioGM.readSettings(',		//'zenarioGM._9xh('
		'zenarioGM.recalc(',		//'zenarioGM._dlo('
		'zenarioGM.recalcColumnAndGutterOptions(',		//'zenarioGM._cov('
		'zenarioGM.recalcOnChange(',		//'zenarioGM._p1u('
		'zenarioGM.refocus(',		//'zenarioGM._dtv('
		'zenarioGM.registerNewName(',		//'zenarioGM._3i7('
		'zenarioGM.rememberNames(',		//'zenarioGM._wu4('
		'zenarioGM.renameSlot(',		//'zenarioGM._lwp('
		'zenarioGM.revert(',		//'zenarioGM._f31('
		'zenarioGM.saveProperties(',		//'zenarioGM._iwg('
		'zenarioGM.scaleWidth(',		//'zenarioGM._ivt('
		'zenarioGM.setHeight(',		//'zenarioGM._glk('
		'zenarioGM.tooltips(',		//'zenarioGM._cjm('
		'zenarioGM.undoOrRedo(',		//'zenarioGM._r1r('
		'zenarioGM.uniqueRandomName(',		//'zenarioGM._5ft('
		'zenarioGM.update(',		//'zenarioGM._xi('
		'zenarioGM.updateAndChange(',		//'zenarioGM._e5n('
		'zenarioGM.useSettingsFromHeader(',		//'zenarioGM._5oz('
		'zenarioO.action2(',		//'zenarioO._nej('
		'zenarioO.addWindowParentInfo(',		//'zenarioO._h86('
		'zenarioO.allItemsSelected(',		//'zenarioO._qbt('
		'zenarioO.applyMergeFields(',		//'zenarioO._yqq('
		'zenarioO.applyMergeFieldsToLabel(',		//'zenarioO._tsz('
		'zenarioO.applySmallSpaces(',		//'zenarioO._eew('
		'zenarioO.branch(',		//'zenarioO._13q('
		'zenarioO.canFilterColumn(',		//'zenarioO._d2p('
		'zenarioO.canSortColumn(',		//'zenarioO._p93('
		'zenarioO.changeFilters(',		//'zenarioO._dwh('
		'zenarioO.changePageSize(',		//'zenarioO._aqg('
		'zenarioO.changePassword(',		//'zenarioO._ofl('
		'zenarioO.changeSortOrder(',		//'zenarioO._6aj('
		'zenarioO.checkButtonHidden(',		//'zenarioO._b0p('
		'zenarioO.checkCondition(',		//'zenarioO._8id('
		'zenarioO.checkDisabled(',		//'zenarioO._jdn('
		'zenarioO.checkHiddenByRefiner(',		//'zenarioO._4z6('
		'zenarioO.checkIfClearAllAvailable(',		//'zenarioO._2v3('
		'zenarioO.checkIfColumnPickerChangesAreAllowed(',		//'zenarioO._cai('
		'zenarioO.checkItemButtonHidden(',		//'zenarioO._4k('
		'zenarioO.checkItemPickable(',		//'zenarioO._bvm('
		'zenarioO.checkPrefs(',		//'zenarioO._tem('
		'zenarioO.checkQueue(',		//'zenarioO._4p('
		'zenarioO.checkQueueLength(',		//'zenarioO._o2j('
		'zenarioO.choose(',		//'zenarioO._4y('
		'zenarioO.chooseButtonActive(',		//'zenarioO._f7i('
		'zenarioO.clearFilter(',		//'zenarioO._sf2('
		'zenarioO.clearRefiner(',		//'zenarioO._2af('
		'zenarioO.clearSearch(',		//'zenarioO._bfs('
		'zenarioO.closeInfoBox(',		//'zenarioO._6ii('
		'zenarioO.closeInspectionView(',		//'zenarioO._qb6('
		'zenarioO.closeSelectMode(',		//'zenarioO._43c('
		'zenarioO.collectionButtonClick(',		//'zenarioO._6c4('
		'zenarioO.columnCssClass(',		//'zenarioO._2jx('
		'zenarioO.columnEqual(',		//'zenarioO._5zs('
		'zenarioO.columnNotEqual(',		//'zenarioO._5cc('
		'zenarioO.columnRawValue(',		//'zenarioO._5m3('
		'zenarioO.columnValue(',		//'zenarioO._5zt('
		'zenarioO.convertNavPathToTagPath(',		//'zenarioO._62b('
		'zenarioO.convertNavPathToTagPathAndRefiners(',		//'zenarioO._q5q('
		'zenarioO.deselectAllItems(',		//'zenarioO._z8s('
		'zenarioO.disableInteraction(',		//'zenarioO._x6q('
		'zenarioO.doCSVExport(',		//'zenarioO._ign('
		'zenarioO.doSearch(',		//'zenarioO._2y6('
		'zenarioO.enableInteraction(',		//'zenarioO._rj7('
		'zenarioO.exportPanelAsCSV(',		//'zenarioO._hty('
		'zenarioO.exportPanelAsExcel(',		//'zenarioO._yli('
		'zenarioO.fadeOutLastButtons(',		//'zenarioO._2dh('
		'zenarioO.filterSetOnColumn(',		//'zenarioO._a0('
		'zenarioO.followPathOnMap(',		//'zenarioO._c4t('
		'zenarioO.getAJAXURL(',		//'zenarioO._6h1('
		'zenarioO.getBackButtonTitle(',		//'zenarioO._g03('
		'zenarioO.getCollectionButtons(',		//'zenarioO._5qa('
		'zenarioO.getColumnFilterType(',		//'zenarioO._evb('
		'zenarioO.getCurrentPage(',		//'zenarioO._awe('
		'zenarioO.getDataHack(',		//'zenarioO._baq('
		'zenarioO.getFilterValue(',		//'zenarioO._a9g('
		'zenarioO.getFooter(',		//'zenarioO._bfn('
		'zenarioO.getFromLastPanel(',		//'zenarioO._204('
		'zenarioO.getFromToFromLink(',		//'zenarioO._f1u('
		'zenarioO.getHash(',		//'zenarioO._hy('
		'zenarioO.getHeader(',		//'zenarioO._6em('
		'zenarioO.getInlineButtons(',		//'zenarioO._6fb('
		'zenarioO.getItemButtons(',		//'zenarioO._khv('
		'zenarioO.getItemCSSClass(',		//'zenarioO._r9t('
		'zenarioO.getKey(',		//'zenarioO._i1('
		'zenarioO.getKeyId(',		//'zenarioO._6ie('
		'zenarioO.getLastKeyId(',		//'zenarioO._v7x('
		'zenarioO.getNavigation(',		//'zenarioO._5rb('
		'zenarioO.getNextItem(',		//'zenarioO._bge('
		'zenarioO.getPageCount(',		//'zenarioO._dt1('
		'zenarioO.getPanel(',		//'zenarioO._c1k('
		'zenarioO.getPanelType(',		//'zenarioO._xg('
		'zenarioO.getQuickFilters(',		//'zenarioO._v94('
		'zenarioO.getRecordCount(',		//'zenarioO._w1('
		'zenarioO.getSelectedItemFromLastPanel(',		//'zenarioO._dr1('
		'zenarioO.getShownColumns(',		//'zenarioO._hrx('
		'zenarioO.getSortedIdsOfTUIXElements(',		//'zenarioO._219('
		'zenarioO.goToLastPage(',		//'zenarioO._v66('
		'zenarioO.goToPage(',		//'zenarioO._6gn('
		'zenarioO.hideCollectionButtons(',		//'zenarioO._2dy('
		'zenarioO.hideItemButtons(',		//'zenarioO._lyl('
		'zenarioO.hideViewOptions(',		//'zenarioO._25n('
		'zenarioO.implodeKeys(',		//'zenarioO._9sm('
		'zenarioO.inInspectionView(',		//'zenarioO._dk1('
		'zenarioO.infoBox(',		//'zenarioO._28('
		'zenarioO.initNewPanelInstance(',		//'zenarioO._pj3('
		'zenarioO.inlineButtonClick(',		//'zenarioO._ev2('
		'zenarioO.inspectionViewEnabled(',		//'zenarioO._ual('
		'zenarioO.inspectionViewItemId(',		//'zenarioO._utq('
		'zenarioO.invertFilter(',		//'zenarioO._m60('
		'zenarioO.isFullMode(',		//'zenarioO._1wq('
		'zenarioO.isShowableColumn(',		//'zenarioO._7fm('
		'zenarioO.itemButtonClick(',		//'zenarioO._qy9('
		'zenarioO.itemClickThrough(',		//'zenarioO._8x5('
		'zenarioO.itemClickThroughAction(',		//'zenarioO._ex1('
		'zenarioO.itemClickThroughLink(',		//'zenarioO._vn8('
		'zenarioO.itemLanguage(',		//'zenarioO._urh('
		'zenarioO.itemParent(',		//'zenarioO._59('
		'zenarioO.loadFromBranches(',		//'zenarioO._8xv('
		'zenarioO.loadMap(',		//'zenarioO._6yt('
		'zenarioO.loadRefiner(',		//'zenarioO._tx1('
		'zenarioO.lookForBranches(',		//'zenarioO._1j4('
		'zenarioO.markIfViewIsFiltered(',		//'zenarioO._6c('
		'zenarioO.maxLengthString(',		//'zenarioO._e47('
		'zenarioO.nextPage(',		//'zenarioO._bjq('
		'zenarioO.noItemsSelected(',		//'zenarioO._z5b('
		'zenarioO.openInspectionView(',		//'zenarioO._kym('
		'zenarioO.panelProp(',		//'zenarioO._lhq('
		'zenarioO.parseNavigationPath(',		//'zenarioO._c5z('
		'zenarioO.parseReturnLink(',		//'zenarioO._37l('
		'zenarioO.pathNotAllowed(',		//'zenarioO._y09('
		'zenarioO.pickItems(',		//'zenarioO._bmn('
		'zenarioO.prevPage(',		//'zenarioO._pc4('
		'zenarioO.quickFilterEnabled(',		//'zenarioO._l1h('
		'zenarioO.refreshAndShowPage(',		//'zenarioO._gd0('
		'zenarioO.refreshIfFilterSet(',		//'zenarioO._1cn('
		'zenarioO.refreshPage(',		//'zenarioO._av6('
		'zenarioO.refreshToShowItem(',		//'zenarioO._6bl('
		'zenarioO.reload(',		//'zenarioO._131('
		'zenarioO.reloadButton(',		//'zenarioO._78l('
		'zenarioO.reloadOpeningInstanceIfRelevant(',		//'zenarioO._jl9('
		'zenarioO.reloadPage(',		//'zenarioO._3ag('
		'zenarioO.resetBranches(',		//'zenarioO._syv('
		'zenarioO.resetPrefs(',		//'zenarioO._rjy('
		'zenarioO.resizeColumn(',		//'zenarioO._6wh('
		'zenarioO.rowCssClass(',		//'zenarioO._q9o('
		'zenarioO.runPanelOnUnload(',		//'zenarioO._wse('
		'zenarioO.runSearch(',		//'zenarioO._7qc('
		'zenarioO.savePrefs(',		//'zenarioO._pu4('
		'zenarioO.saveRefiner(',		//'zenarioO._qse('
		'zenarioO.saveSearch(',		//'zenarioO._28v('
		'zenarioO.scrollTopLevelNav(',		//'zenarioO._k8n('
		'zenarioO.searchAndSortItems(',		//'zenarioO._7jt('
		'zenarioO.searchOnChange(',		//'zenarioO._2fv('
		'zenarioO.searchOnClick(',		//'zenarioO._2f2('
		'zenarioO.searchOnKeyUp(',		//'zenarioO._joy('
		'zenarioO.selectAllItems(',		//'zenarioO._vrz('
		'zenarioO.selectCreatedIds(',		//'zenarioO._2oe('
		'zenarioO.selectItemRange(',		//'zenarioO._lg3('
		'zenarioO.selectItems(',		//'zenarioO._gj2('
		'zenarioO.selectedItemDetails(',		//'zenarioO._b7m('
		'zenarioO.selectedItemId(',		//'zenarioO._k8o('
		'zenarioO.selectedItemIds(',		//'zenarioO._b54('
		'zenarioO.selectedItems(',		//'zenarioO._k8w('
		'zenarioO.selectionDisplayType(',		//'zenarioO._z0d('
		'zenarioO.setBackButton(',		//'zenarioO._s9q('
		'zenarioO.setButtonAction(',		//'zenarioO._39e('
		'zenarioO.setButtons(',		//'zenarioO._96o('
		'zenarioO.setChooseButton(',		//'zenarioO._5v9('
		'zenarioO.setDataAttributes(',		//'zenarioO._z22('
		'zenarioO.setFilterValue(',		//'zenarioO._yvr('
		'zenarioO.setHash(',		//'zenarioO._1a4('
		'zenarioO.setMap(',		//'zenarioO._gm7('
		'zenarioO.setNavigation(',		//'zenarioO._wkp('
		'zenarioO.setOrganizerIcons(',		//'zenarioO._w8e('
		'zenarioO.setPanel(',		//'zenarioO._9u('
		'zenarioO.setPanelTitle(',		//'zenarioO._piy('
		'zenarioO.setSearch(',		//'zenarioO._ju('
		'zenarioO.setTopLevelNavScrollStatus(',		//'zenarioO._9al('
		'zenarioO.setTopRightButtons(',		//'zenarioO._6es('
		'zenarioO.setTrash(',		//'zenarioO._os('
		'zenarioO.setViewOptions(',		//'zenarioO._suz('
		'zenarioO.setWhereWasThatThingSearch(',		//'zenarioO._2y2('
		'zenarioO.setWrapperClass(',		//'zenarioO._f5r('
		'zenarioO.shortenPath(',		//'zenarioO._lj('
		'zenarioO.showCollectionButtons(',		//'zenarioO._2y0('
		'zenarioO.showHideColumn(',		//'zenarioO._93d('
		'zenarioO.showHideColumnInCSV(',		//'zenarioO._ret('
		'zenarioO.showItemButtons(',		//'zenarioO._9pt('
		'zenarioO.showPage(',		//'zenarioO._18v('
		'zenarioO.showViewOptions(',		//'zenarioO._q4y('
		'zenarioO.showViewOptions2(',		//'zenarioO._fsl('
		'zenarioO.showableColumns(',		//'zenarioO._5ps('
		'zenarioO.sizeButtons(',		//'zenarioO._o2('
		'zenarioO.sortArray(',		//'zenarioO._vc('
		'zenarioO.splitCols(',		//'zenarioO._qv0('
		'zenarioO.stopRefreshing(',		//'zenarioO._t9n('
		'zenarioO.switchColumnOrder(',		//'zenarioO._81u('
		'zenarioO.toggleAllItems(',		//'zenarioO._vfu('
		'zenarioO.toggleFilter(',		//'zenarioO._a3u('
		'zenarioO.toggleInspectionView(',		//'zenarioO._8h7('
		'zenarioO.toggleQuickFilter(',		//'zenarioO._b5w('
		'zenarioO.topLevelClick(',		//'zenarioO._fjw('
		'zenarioO.topRightButtonClick(',		//'zenarioO._4af('
		'zenarioO.updateDateFilters(',		//'zenarioO._yc8('
		'zenarioO.updateYourWorkInProgress(',		//'zenarioO._lzu('
		'zenarioO.uploadComplete(',		//'zenarioO._oja('
		'zenarioO.uploadStart(',		//'zenarioO._3yq('
		'zenarioO.viewTrash(',		//'zenarioO._k1e('
		'zenarioT.action(',		//'zenarioT._1st('
		'zenarioT.addClass(',		//'zenarioT._l5k('
		'zenarioT.canDoHTML5Upload(',		//'zenarioT._3gj('
		'zenarioT.checkActionExists(',		//'zenarioT._r6f('
		'zenarioT.checkActionUnique(',		//'zenarioT._hu6('
		'zenarioT.checkDumps(',		//'zenarioT._iw6('
		'zenarioT.checkFunctionExists(',		//'zenarioT._bxf('
		'zenarioT.csvToObject(',		//'zenarioT._iru('
		'zenarioT.disableFileDragDrop(',		//'zenarioT._3u4('
		'zenarioT.doEval(',		//'zenarioT._8s('
		'zenarioT.doHTML5Upload(',		//'zenarioT._bde('
		'zenarioT.doNextUpload(',		//'zenarioT._4mt('
		'zenarioT.filter(',		//'zenarioT._40o('
		'zenarioT.generateGlobalName(',		//'zenarioT._wv4('
		'zenarioT.getSortedIdsOfTUIXElements(',		//'zenarioT._219('
		'zenarioT.hidden(',		//'zenarioT._58('
		'zenarioT.keepTrying(',		//'zenarioT._920('
		'zenarioT.microTemplate(',		//'zenarioT._sbf('
		'zenarioT.newSimpleForm(',		//'zenarioT._2q7('
		'zenarioT.numberFormat(',		//'zenarioT._k3p('
		'zenarioT.onChangeOrSearch(',		//'zenarioT._ojb('
		'zenarioT.onbeforeunload(',		//'zenarioT._dg4('
		'zenarioT.option(',		//'zenarioT._10n('
		'zenarioT.resizeImage(',		//'zenarioT._esd('
		'zenarioT.select(',		//'zenarioT._fsb('
		'zenarioT.setHTML5UploadFromDragDrop(',		//'zenarioT._u4i('
		'zenarioT.setKin(',		//'zenarioT._1a1('
		'zenarioT.showDevTools(',		//'zenarioT._sik('
		'zenarioT.sortArray(',		//'zenarioT._vc('
		'zenarioT.sortArrayByOrd(',		//'zenarioT._5w2('
		'zenarioT.sortArrayByOrdinal(',		//'zenarioT._nkf('
		'zenarioT.sortArrayDesc(',		//'zenarioT._4gr('
		'zenarioT.sortArrayForOrganizer(',		//'zenarioT._8wk('
		'zenarioT.sortArrayWithGrouping(',		//'zenarioT._v7m('
		'zenarioT.sortLogic(',		//'zenarioT._af('
		'zenarioT.splitDataFromErrorMessage(',		//'zenarioT._i9y('
		'zenarioT.stopDefault(',		//'zenarioT._1h2('
		'zenarioT.stopFileDragDrop(',		//'zenarioT._5w3('
		'zenarioT.stopTrying(',		//'zenarioT._coi('
		'zenarioT.tuixToArray(',		//'zenarioT._mgu('
		'zenarioT.uploadDone(',		//'zenarioT._h6z('
		'zenarioT.uploadProgress(',		//'zenarioT._8ay('
		'zenario_conductor.autoRefresh(',		//'zenario_conductor._iaj('
		'zenario_conductor.backLink(',		//'zenario_conductor._1qn('
		'zenario_conductor.cleanRequests(',		//'zenario_conductor._v5z('
		'zenario_conductor.closeToggle(',		//'zenario_conductor._rtc('
		'zenario_conductor.commandEnabled(',		//'zenario_conductor._4xe('
		'zenario_conductor.confirmOnClose(',		//'zenario_conductor._pf0('
		'zenario_conductor.confirmOnCloseMessage(',		//'zenario_conductor._apv('
		'zenario_conductor.enabled(',		//'zenario_conductor._tn('
		'zenario_conductor.getCommand(',		//'zenario_conductor._7ko('
		'zenario_conductor.getSlot(',		//'zenario_conductor._cae('
		'zenario_conductor.getToggle(',		//'zenario_conductor._h93('
		'zenario_conductor.getToggles(',		//'zenario_conductor._8ai('
		'zenario_conductor.getVar(',		//'zenario_conductor._6i8('
		'zenario_conductor.getVars(',		//'zenario_conductor._cl7('
		'zenario_conductor.goBack(',		//'zenario_conductor._13('
		'zenario_conductor.linkToOtherContentItem(',		//'zenario_conductor._hx3('
		'zenario_conductor.mergeRequests(',		//'zenario_conductor._7ab('
		'zenario_conductor.openToggle(',		//'zenario_conductor._v38('
		'zenario_conductor.refresh(',		//'zenario_conductor._dwr('
		'zenario_conductor.refreshAll(',		//'zenario_conductor._au4('
		'zenario_conductor.reloadAfterDelay(',		//'zenario_conductor._6r2('
		'zenario_conductor.request(',		//'zenario_conductor._153('
		'zenario_conductor.resetVarsOnBackNav(',		//'zenario_conductor._kla('
		'zenario_conductor.resetVarsOnBrowserBackNav(',		//'zenario_conductor._l1('
		'zenario_conductor.setCommands(',		//'zenario_conductor._f3b('
		'zenario_conductor.setToggle(',		//'zenario_conductor._88l('
		'zenario_conductor.setVar(',		//'zenario_conductor._goe('
		'zenario_conductor.setVars(',		//'zenario_conductor._th('
		'zenario_conductor.stopAutoRefresh(',		//'zenario_conductor._beg('
		'zenario_conductor.toggleClick(',		//'zenario_conductor._h4g('
		'zenario_conductor.transitionIn(',		//'zenario_conductor._dj5('
		'zenario_conductor.transitionOut(',		//'zenario_conductor._dj9('//
	];
	
	public static $stats = null;
	public static $longNames = [];
	public static $shortNames = [];
	
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
}


define('DEBUG_DONT_MINIFY', false);


if (is_file(CMS_ROOT. $ccPath = 'zenario/libs/not_to_redistribute/google-closure-compiler-java/compiler.jar')) {
	define('CLOSURE_COMPILER_PATH', $ccPath);

//Watch out for the old path
} elseif (is_file(CMS_ROOT. $ccPath = 'zenario/libs/not_to_redistribute/closure-compiler/closure-compiler.jar')) {
	define('CLOSURE_COMPILER_PATH', $ccPath);

} else {
	echo
"A tool for minifying JavaScript and CSS used by Zenario;
this is a wrapper for calling Closure Compiler (https://developers.google.com/closure/compiler/)
and Minify (https://github.com/matthiasmullie/minify) on all relevant files.

To save space, the Zenario download does not come with copies of Closure Compiler,
but if you download and put it in the right place, then this tool will use it.

To use this tool:
 - Java must be available on your server.
 - You must download the closure-compiler.jar file from the site mentioned above.
 - This needs to be placed in the zenario/libs/not_to_redistribute/google-closure-compiler-java/ directory.

";
	exit;
}



function displayUsage() {
	echo
"A tool for minifying JavaScript used by Zenario;
this is a wrapper for calling Minify (https://github.com/matthiasmullie/minify)
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



//Macros and replacements
function applyCompilationMacros($code, $dir, $file) {
	
	$patterns = [];
	$replacements = [];
	
	
	//This is normally only called on our own internal JavaScript code, however as a bit of a hack,
	//we'll use a special case in this function to fix a bug with TinyMCE 6 trying to call the
	//classList.add() and classList.remove() functions with invalid values...
	if ($file == 'tinymce') {
		$patterns[] = '@classList\.remove\(clazz\)@';
		$replacements[] = 'if (clazz !== "") classList.remove(clazz)';
		
		$patterns[] = '@element\.dom\.classList\.add\(clazz\)@';
		$replacements[] = 'if (clazz !== "") element.dom.classList.add(clazz)';

		$patterns[] = "@s\.replace\(r\, \'\'\)\;@";
		$replacements[] = "{ if (!_.isString(s)) return ''; return s.replace(r, ''); }";
	
	
	} else {
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
	
	}
	
	
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
					//specifiy one variable then it becomes the key, not the value.
					//Also call it when minifying TinyMCE
					if (!$isCSS
					 && !$yamlToJSON
					 && (substr($dir, 0, 13) != 'zenario/libs/' || $file == 'tinymce')) {
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
						
						$minifier = new \MatthiasMullie\Minify\CSS();
						//$minifier->setMaxImportSize(0);
						$minifier->add($srcFile);
						$minifier->minify($minFile);
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