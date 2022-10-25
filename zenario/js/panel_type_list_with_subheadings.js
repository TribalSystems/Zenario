/*
 * Copyright (c) 2022, Tribal Limited
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
	panelTypes.list_with_subheadings = extensionOf(panelTypes.list_with_totals)
);

//This view isn't implemented on the server, this just works by loading all of the data and sorting it on the client
methods.returnPageSize = function() {
	return false;
};
methods.returnDoSortingAndSearchingOnServer = function() {
	return false;
};


//This customised sorting function sorts items by the subheading_column first, then the normal
//sort column second.
methods.sortItems = function() {
	
	var sortedItems = [],
		subheadingColumn = thus.tuix.subheading_column,
		sortColSortedItems = zenarioT.getSortedIdsOfTUIXElements(thus.tuix, 'items', thus.sortBy, thus.sortDesc),
		subheadingSortedItems,
		sortedItemsBySubheading = {},
		sortedSubheadings = [],
		i, j, id, subheading;
	
	thus.subheadingColumn = subheadingColumn;
	thus.currentSubheading = undefined;
	thus.itemIdsBySubheading = {};
	thus.lastItemIdBySubheading = {};
	
	if (!subheadingColumn) {
		return sortColSortedItems;
	}
	
	subheadingSortedItems = zenarioT.getSortedIdsOfTUIXElements(thus.tuix, 'items', subheadingColumn);
	
	foreach (subheadingSortedItems as i => id) {
		subheading = thus.tuix.items[id][subheadingColumn];
		
		if (!sortedItemsBySubheading[subheading]) {
			sortedItemsBySubheading[subheading] = [];
			thus.itemIdsBySubheading[subheading] = {};
			sortedSubheadings.push(subheading);
		}
	}
	
	foreach (sortColSortedItems as i => id) {
		subheading = thus.tuix.items[id][subheadingColumn];
		sortedItemsBySubheading[subheading].push(id);
		thus.itemIdsBySubheading[subheading][id] = true;
		thus.lastItemIdBySubheading[subheading] = id;
	}
	
	foreach (sortedSubheadings as i => subheading) {
		foreach (sortedItemsBySubheading[subheading] as j => id) {
			sortedItems.push(id);
		}
	}
	
	return sortedItems;
};

methods.showPanel = function($header, $panel, $footer) {
	
	thus.lastSubheadingRow = false;
	
	methodsOf(panelTypes.list_with_totals).showPanel.apply(thus, arguments);
};

methods.addExtraMergeFieldsForRows = function(data, row) {
	
	if (!thus.subheadingColumn) {
		return;
	}
	
	var subheading = row.tuix[thus.subheadingColumn];
	
	//Check if this the first row of the subsection. If so, draw the heading.
	if (thus.currentSubheading != subheading) {
		thus.currentSubheading = subheading;
		row.subheading = zenarioO.columnValue(row.id, thus.subheadingColumn);
	}
	
	//Check if this the last row of the subsection. If so, draw the sub-totals.
	if (row.id == thus.lastItemIdBySubheading[subheading]
	 && thus.itemIdsBySubheading[subheading]) {
		row.subtotal = thus.generateTotals(data, thus.itemIdsBySubheading[subheading]);
	}
};





}, zenarioO.panelTypes);