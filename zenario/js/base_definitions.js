/*
 * Copyright (c) 2018, Tribal Limited
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




(function(window, zenarioAsString, moduleClassNameAsString, undefined) {
	"use strict";
		
		//This is a shortcut function for initialising a new class.
		//It just uses normal JavaScript class inheritance, but it makes the syntax
		//a little more readable and friendly when creating a new class
		extensionOf = function(parent, initFun, globalName) {
			if (parent) {
				initFun = initFun || (function() {
						parent.apply(this, arguments);
					});
				
				initFun.prototype = new parent;
				initFun.prototype.constructor = parent;
			
			} else {
				initFun = initFun || (function() {});
			}
			
			if (globalName) {
				initFun.globalName = globalName;
				window[globalName] = initFun;
			}

			return initFun;
		},
		
		//Shortcut function to the above
		zenarioLibs = {},
		createZenarioLibrary = function(zenarioEncapName, parent) {
			zenarioEncapName = zenarioAsString + zenarioEncapName;
			return zenarioLibs[zenarioEncapName] = extensionOf(parent, undefined, zenarioEncapName);
		},
	
		//Create encapsulated objects/classes for all of Zenario's libraries
		zenario = createZenarioLibrary(''),
		zenarioA = createZenarioLibrary('A'),
		zenarioT = createZenarioLibrary('T'),
		zenarioF = createZenarioLibrary('F'),
		zenarioAF = createZenarioLibrary('AF', zenarioF),
		zenarioABToolkit = createZenarioLibrary('ABToolkit', zenarioAF),
		zenarioAB = window.zenarioAB = new zenarioABToolkit(),
		zenarioAT = createZenarioLibrary('AT'),
		zenarioO = createZenarioLibrary('O');
		
		zenarioA.phrase = {};
	
	//Create a wrapper function with variables for all of these objects
	//(This helps keep file sizes down when minifying as the listed common global variables and functions
	// can be minified.)
	zenario.lib = function(fun, extraVar1, extraVar2, extraVar3, extraVar4, extraVar5, extraVar6) {
		fun(
			undefined,
			URLBasePath,
			document, window, window.opener, window.parent,
			zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
			encodeURIComponent, zenario.get, zenario.engToBoolean, zenario.htmlspecialchars, zenario.jsEscape, zenarioA.phrase,
			zenario.extensionOf, zenario.methodsOf, zenario.has,
			extraVar1, extraVar2,

			//Define some shortcuts for common strings, to reduce the filesize a bit
			zenarioAsString + '_',
			zenarioAsString + '/',
			'.' + zenarioAsString + '_',
			zenarioAsString + '__content/panels/',
			zenarioAsString + '_common_features',
			'#' + zenarioAsString + '_',
			'&instanceId=',
			'&slotName=',
			'&eggId=',
			moduleClassNameAsString,
			moduleClassNameAsString + 'ForPhrases',
			'?' + moduleClassNameAsString + '=',
			'&method_call=',
			'lookup',
			'&cVersion=',
			'colorbox',
			zenarioAsString + '/ajax.php',
			'plgslt_',
			'#plgslt_',
			
			extraVar3, extraVar4, extraVar5, extraVar6
		);
	};
	
	//Store the Zenario libraries in an object for easy traversal later
	zenario.libs = zenarioLibs;
	
	//Allow the extensionOf() and createZenarioLibrary() functions to be called elsewhere as well
	zenario.extensionOf = window.extensionOf = extensionOf;
	zenario.createZenarioLibrary = window.createZenarioLibrary = createZenarioLibrary;
	
	zenarioO.panelTypes = {};

})(window, 'zenario', 'moduleClassName');