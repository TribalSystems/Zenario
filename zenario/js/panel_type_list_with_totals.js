/*
 * Copyright (c) 2023, Tribal Limited
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
	The code here is not the code you see in your browser. Before thus file is downloaded:
	
		1. Compilation macros are applied (e.g. "foreach" is a macro for "for .. in ... hasOwnProperty").
		2. It is minified (e.g. using Google Closure Compiler).
		3. It may be wrapped togther with other files (thus is to reduce the number of http requests on a page).
	
	For more information, see js_minify.shell.php for steps (1) and (2), and organizer.wrapper.js.php for step (3).
*/



zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	panelTypes
) {
	"use strict";

//Note: extensionOf() and methodsOf() are our shortcut functions for class extension in JavaScript.
	//extensionOf() creates a new class (optionally as an extension of another class).
	//methodsOf() allows you to get to the methods of a class.
var methods = methodsOf(
	panelTypes.list_with_totals = extensionOf(panelTypes.list)
);

//Disable pagination
methods.returnPageSize = function() {
	return false;
};


methods.generateTotals = function(data, itemsToCount) {
	var ci,
		column,
		itemId,
		item,
		value,
		total,
		foundANumber,
		decimalPlaces,
		maxDecimalPlaces;
	
	//Loop through each column, trying to put a total in it
	if (data
	 && data.columns) {
		foreach (data.columns as ci => column) {
			
			//Check to see if a total is specifically set in TUIX
			if (!itemsToCount && defined(column.tuix.total)) {
				//Note: "column.tuix" is a shortcut to this.tuix.columns[column.id]
				column.total = column.tuix.total;
				
			//Otherwise attempt to manually add it up
			} else if (defined(column.tuix.calculate_total)) {
				total = 0;
				foundANumber = false;
				maxDecimalPlaces = 0;
				
				if (thus.tuix.items) {
					foreach (thus.tuix.items as itemId => item) {
						
						if (!itemsToCount || itemsToCount[itemId]) {
							
							value = item[column.id];
							if (defined(value)) {
								//Check if this is numeric
								if (value == 1*value) {
									if (value.toString().indexOf('.') !== -1) {
										decimalPlaces = value.toString().split(".")[1].length;
										maxDecimalPlaces = (maxDecimalPlaces < decimalPlaces) ? decimalPlaces : maxDecimalPlaces;
									}
									total += 1*value;
									foundANumber = true;
								}
							}
						}
					}
				}
				if (foundANumber) {
					if (maxDecimalPlaces) {
						total = total.toFixed(maxDecimalPlaces);
					}
					column.total = total;
				} else {
					return;
				}
			}
			
			if (defined(column.total)) {
				column.total_label = (!defined(column.tuix.total_label)) ? 'Total:' : column.tuix.total_label;
			}
		}
	}
	
	return thus.microTemplate('zenario_organizer_list_total', data);
};


methods.showPanel = function($header, $panel, $footer) {
	
	methodsOf(panelTypes.list).showPanel.apply(thus, arguments);
	
	var ci,
		column,
		itemId,
		item,
		value,
		total,
		foundANumber,
		decimalPlaces,
		maxDecimalPlaces;
	
	if(thus.items.maxNumberOfInlineButtons > 0) {
		thus.items.rowWidth = 30 * thus.items.maxNumberOfInlineButtons; 
	} else {
		thus.items.rowWidth = 30;
	}
	
	var html = thus.generateTotals(thus.items),
		$totals = $(html),
		$listView = $('#organizer_list_view .organizer_list_view_body_wrap');
	
	$listView.append($totals);
};

}, zenarioO.panelTypes);