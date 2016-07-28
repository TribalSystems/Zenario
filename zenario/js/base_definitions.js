/*
 * Copyright (c) 2016, Tribal Limited
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




((window, createLibrary, undefined) => {
	"use strict";
	
	//This is a shortcut function for initialising a new class.
	//It just uses normal JavaScript class inheritance, but it makes the syntax
	//a little more readable and friendly when creating a new class
	var extensionOf =(parent, initFun, encapName) => {
			if (parent) {
				initFun = initFun || (function() {
						parent.apply(this, arguments);
					});
				
				initFun.prototype = new parent;
				initFun.prototype.constructor = parent;
			
			} else {
				initFun = initFun || (function() {});
			}
			
			if (encapName) {
				initFun.encapName = encapName;
				window[encapName] = initFun;
			}

			return initFun;
		},
		
		//Shortcut function to the above
		createZenarioLibrary =(zenarioEncapName, parent)=> {
			return extensionOf(parent, undefined, 'zenario' + zenarioEncapName);
		},
	
		//Create encapsulated objects/classes for all of Zenario's libraries
		zenario = window.zenario = createZenarioLibrary(''),
		zenarioA = window.zenarioA = createZenarioLibrary('A'),
		zenarioF = window.zenarioF = createZenarioLibrary('F'),
		zenarioAF = window.zenarioAF = createZenarioLibrary('AF', zenarioF),
		zenarioABToolkit = window.zenarioABToolkit = createZenarioLibrary('ABToolkit', zenarioAF),
		zenarioAB = window.zenarioAB = new zenarioABToolkit(),
		zenarioAT = window.zenarioAT = createZenarioLibrary('AT'),
		zenarioO = window.zenarioO = createZenarioLibrary('O');
	
	//Create a wrapper function with variables for all of these objects
	//(This helps keep file sizes down when minifying as the listed common global variables and functions
	// can be minified.)
	zenario.lib =(fun, extraVar1, extraVar2, extraVar3, extraVar4, extraVar5, extraVar6)=> {
		fun(
			undefined,
			URLBasePath,
			document, window, window.opener, window.parent,
			zenario, zenarioA, zenarioAB, zenarioAT, zenarioO,
			zenario.get, zenario.engToBoolean, zenario.htmlspecialchars, zenario.ifNull, zenario.jsEscape, zenarioA.phrase,
			zenario.extensionOf, zenario.methodsOf, zenario.has,
			extraVar1, extraVar2, extraVar3, extraVar4, extraVar5, extraVar6
		);
	};
	
	//Allow the extensionOf() and createZenarioLibrary() functions to be called elsewhere as well
	zenario.extensionOf = window.extensionOf = extensionOf;
	zenario.createZenarioLibrary = window.createZenarioLibrary = createZenarioLibrary;
	
	zenarioO.panelTypes = {};

})(window);