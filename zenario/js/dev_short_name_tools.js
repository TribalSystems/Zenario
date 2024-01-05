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

/*
	This file contains JavaScript source code.
	The code here is not the code you see in your browser. Before this file is downloaded:
	
		1. Compilation macros are applied (e.g. "foreach" is a macro for "for .. in ... hasOwnProperty").
		2. It is minified (e.g. using Google Closure Compiler).
	
	For more information, see js_minify.shell.php.
*/


var sourceLib;


zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	snTools
	
) {
	"use strict";




//A developer tool, checks a library to see if it will work with the short-names system
snTools.reviewShortNamesInLib = function(data, lib, globalName) {
	
	var longName, shortName, fun,
		longNameEncap, shortNameEncap;
	
	globalName = globalName || lib.globalName;
	
	//Given a library, loop through all of its properties.
	//We're looking for functions with names that are 6 or more characters long.
	foreach (lib as longName => fun) {
		if (longName.length > 5
		 && longName[0] != '_'
		 && typeof fun == 'function') {
			
			shortName = zenario.shrtn(longName);
			
			longNameEncap = globalName + "." + longName;
			shortNameEncap = globalName + "." + shortName;
			
			if (data.mapping[shortNameEncap]) {
				
				if (!data.clashes[shortNameEncap]) {
					data.clashes[shortNameEncap] = [data.mapping[shortNameEncap]];
				}
				data.clashes[shortNameEncap].push(longNameEncap);
			}
			
			data.mapping[shortNameEncap] = longNameEncap;
			
		}
	}
};

snTools.reviewShortNames = function() {
	
	var output = '',
		mappings = [],
		shortNameEncap, longNameEncap, clashingNames, lastName,
		data = {
			mapping: {},
			clashes: {}
		};
	
	snTools.reviewShortNamesInLib(data, _, '_');
	snTools.reviewShortNamesInLib(data, zenario, 'zenario');
	snTools.reviewShortNamesInLib(data, zenarioT);
	snTools.reviewShortNamesInLib(data, zenarioA);
	snTools.reviewShortNamesInLib(data, zenarioAB);
	snTools.reviewShortNamesInLib(data, zenarioAT);
	snTools.reviewShortNamesInLib(data, zenarioO);
	snTools.reviewShortNamesInLib(data, zenarioGM);
	snTools.reviewShortNamesInLib(data, zenario_conductor);
	
	output += "\n\t//	Zenario's minification script includes some logic to shorten long function names.";
	output += "\n\t//	All encapsulated functions in the libraries listed with names longer than five";
	output += "\n\t//	characters have their names passed through a custom-written hashing function to";
	output += "\n\t//	generate a short name,and this short name is then used in the minified copies of";
	output += "\n\t//	the libraries to reduce filesize/download size.";
	output += "\n";
	output += "\n\tpublic static $shortNamesWhitelist = [";
	
	if (!_.isEmpty(data.clashes)) {
		output += "\n";
		output += "\n\t\t//";
		output += "\n\t\t//\tWarning: the following functions have clashing short names, so the short names cannot be used.";
		output += "\n\t\t//";
		output += "\n";
		
		foreach (data.clashes as shortNameEncap => clashingNames) {
			lastName = clashingNames.pop();
			output += "\n\t\t//" + clashingNames.join(', ') + " and " + lastName + " all have a short name of " + shortNameEncap;
		}
		
		output += "\n";
	}
	
	output += "\n";
	output += "\n\t\t//";
	output += "\n\t\t//\tThis list of short name definitions should be copied into zenario/includes/js_minify.inc.php.";
	output += "\n\t\t//";
	output += "\n";
	
	foreach (data.mapping as shortNameEncap => longNameEncap) {
		mappings.push("\n\t\t'" + longNameEncap + "(',\t\t//'" + shortNameEncap + "('");
	}
	
	mappings.sort();
	output += mappings.join('');
	
	output += "//\n\t];";

	$('#output').val(output);
};


},
	window.snTools = function() {}
);