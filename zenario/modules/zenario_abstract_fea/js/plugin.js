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




zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has
) {
	"use strict";


zenario_abstract_fea.initPopout = function(moduleClassName, library, path, mode, popoutClass, parentContainerId, request, parent, showCloseButtonAtTopRight) {
	
	var pages = undefined,
		noPlugin = true,
		inPopout = true;
		popoutContainerId = mode + '_' + parentContainerId;
	
	if (!popoutClass) {
		popoutClass = path;
	}
	
	if (!parent) {
		parent = parentContainerId;
	}
	
	zenario_abstract_fea.setupAndInit(moduleClassName, library, popoutContainerId, path, request, mode, pages, undefined, noPlugin, parent, inPopout, popoutClass, showCloseButtonAtTopRight);
	
	//Return false so any buttons do not continue running AJAX requests
	return false;
};



zenario_abstract_fea.setupAndInit = function(moduleClassName, library, containerId, path, request, mode, pages, idVarName, noPlugin, parent, inPopout, popoutClass, showCloseButtonAtTopRight) {

	var globalName = moduleClassName + '_' + containerId.replace(/\-/g, '__');
	
	
	//Note: In older versions of Zenario, we didn't re-initialise the nested plugin instance in JavaScript if someone navigated away
	//and then back again in Conductor.
	//However this caused several bugs with events not firing properly, so we've removed this logic for now.
	//if (!window[globalName]) {
	//	window[globalName] = new library();
	//}
	window[globalName] = new library();
	
	window[globalName].showCloseButtonAtTopRight = showCloseButtonAtTopRight;
	
	window[globalName].init(globalName, 'fea', moduleClassName, containerId, path, request, mode, pages, idVarName, noPlugin, parent, inPopout, popoutClass);
	
	return window[globalName];
};




});