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


(function(window, String_prototype, undefined) {
	"use strict";
	
	
	//A little experiment, try adding a short name for string replaces
	String_prototype.m = String_prototype.match;
	String_prototype.r = String_prototype.replace;
	String_prototype.s = String_prototype.split;
	
	
	var libNum = 0,
		
		//Check whether something is null or undefined
		defined = function(prop) {
			return prop !== undefined && prop !== null;
		},
		
		//This is a shortcut function for initialising a new class.
		//It just uses normal JavaScript class inheritance, but it makes the syntax
		//a little more readable and friendly when creating a new class
		extensionOf = function(parent, initFun) {
			if (parent) {
				//Come up with a constructor function. Either use the one provided,
				//otherwise try to use the one from the parent class.
				initFun = initFun || (function() {
						parent.apply(this, arguments);
					});
				
				//The prototype of the new class needs to be set to an instance of the old class
				//There are two ways to do this.
				try {
					//This version crashes if the constructor function has parameters
					initFun.prototype = new parent;
				} catch (e) {
					//This version works if constructor function has parameters, but crashes on old browsers
					initFun.prototype = Object.create(parent.prototype);
				}
				//Hopefully at least one of those will work.
				
				//Finally the constructor needs to be set to the parent
				initFun.prototype.constructor = parent;
			
			} else {
				//If a dev calls this function without a parent class specified,
				//simply make a brand new class.
				initFun = initFun || (function() {});
			}

			return initFun;
		},
		
		//Shortcut to the above, with different parameters
		createZenarioLibrary = function(zenarioEncapName, parent, initFun) {
			
			zenarioEncapName = 'zenario' + (!defined(zenarioEncapName)? 'Lib' + ++libNum : zenarioEncapName);
			initFun = extensionOf(parent, initFun);
			
			initFun.globalName = zenarioEncapName;
			window[zenarioEncapName] = initFun;
			
			return initFun;
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
		zenarioO = createZenarioLibrary('O'),
		
		//This is a shortcut function for accessing the prototype of a class so you
		//can then define its methods.
		//This is more of a longcut than a shortcut function, but it makes the syntax
		//a little more readable and friendly when setting the methods of a class.
		methodsOf =
		window.methodsOf =
		zenario.methodsOf = function(thisClass) {
			return thisClass.prototype;
		},
		
		//Shortcut to document.getElementById()
		get =
		window.get =
		zenario.get = function(el) {
			return document.getElementById(el);
		},

		//Shortcut to hasOwnProperty()
		has =
		window.has =
		zenario.has = function(o, k) {
			return defined(o) && o.hasOwnProperty && o.hasOwnProperty(k);
		},
		
		//Return either 0 or 1, based on an input string
		engToBoolean =
		window.engToBoolean =
		zenario.engToBoolean = function(text) {
		
			var ty = typeof text;
		
			if (ty == 'function') {
				text = text();
				ty = typeof text;
			}
		
			switch (ty) {
				case 'object':
					return (text && text.length !== 0)? 1 : 0;
				case 'string':
					text = text.trim().toLowerCase();
					return (text && text != '0' && text != 'false' && text != 'no' && text != 'off')? 1 : 0;
				default:
					return text? 1 : 0;
			}
		},
		
		//Safely escape some text for displaying as HTML
		htmlspecialchars =
		window.htmlspecialchars =
		zenario.htmlspecialchars = function(text, preserveLineBreaks, preserveSpaces) {
		
			if (_.isFunction(text)) {
				text = text();
			}
		
			if (text === false || !defined(text)) {
				return '';
			}
	
			if (typeof text == 'object') {
				text = text.label || text.default_label || text.name || text.field_name;
			}
		
			text = ('' + text).r(/&/g, '&amp;').r(/"/g, '&quot;').r(/\</g, '&lt;').r(/>/g, '&gt;');
		
			if (preserveSpaces) {
				if (preserveSpaces !== 'asis') {
					text = text.r(/ /g, '&nbsp;');
				}
			} else {
				text = $.trim(text);
			}
		
			if (preserveLineBreaks) {
				text = text.r(/\n/g, '<br/>');
			}
		
			return text;
		},
		
		//Escape text for a JavaScript string
		jsEscape =
		window.jsEscape =
		zenario.jsEscape = function(text) {
			return escape(text).r(/\%u/g, '\\u').r(/\%/g, '\\x');
		},
		
		jsUnescape =
		window.jsUnescape =
		zenario.jsUnescape = function(text) {
			return unescape(text.r(/\\u/gi, '%u').r(/\\x/gi, '%')).r(/\\(.)/g, "$1");
		},
	
		//Fallback for browsers *cough IE* that don't have a CSS escape function,
		//as per http://stackoverflow.com/questions/2786538/need-to-escape-a-special-character-in-a-jquery-selector-string
		cssEscape =
		window.cssEscape =
		zenario.cssEscape = function(text) {
			if (window.CSS && CSS.escape) {
				return CSS.escape(text);
			} else {
				return text.r(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, '\\$&');
			}
		};
	
	zenario.defined = defined;
	zenario.extensionOf = window.extensionOf = extensionOf;
	zenario.createZenarioLibrary = window.createZenarioLibrary = createZenarioLibrary;
	
	//Add proper definitions for these functions, just for the API documentor to catch
	/*
		zenario.defined = function(prop) {
		};
		zenario.extensionOf = function(parent, initFun) {
		};
		zenario.createZenarioLibrary = function(zenarioEncapName, parent, initFun) {
		};
	*/
	
	
	zenario.vphrase = {};
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
			encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, zenarioA.phrase,
			zenario.extensionOf, zenario.methodsOf, zenario.has,
			extraVar1, extraVar2, extraVar3, extraVar4, extraVar5, extraVar6
		);
	};
	
	zenarioO.panelTypes = {};

})(window, String.prototype);