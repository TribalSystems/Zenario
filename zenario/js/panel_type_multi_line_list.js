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
	panelTypes.multi_line_list = extensionOf(panelTypes.list)
);

methods.init = function() {
	thus.lineHeight = 20;
};


methods.returnIsMultiLineList = function() {
	return true;
};


methods.showPanel = function($header, $panel, $footer) {
	methodsOf(panelTypes.list).showPanel.apply(thus, arguments);
};

methods.drawItems = function($panel) {

	var ci, col,
		ei, cell,
		ii, item,
		childrenToParents = {},
		parent, thisParentsChildren,
		data = thus.getMergeFieldsForItemsAndColumns();
	
	//Add the parent property to the column merge fields
	foreach (data.columns as ci => col) {
		col.parent = childrenToParents[col.id] = thus.tuix.columns[col.id].parent;
	}
	
	//Detach children from the top-level, and add them to their children
	zenarioT.setKin(data.columns, 'org_child_column');
	
	//Re-run the logic to calculate the total width, based on the columns we removed
	data.totalWidth = zenarioO.columnsExtraSpacing;
	foreach (data.columns as ci => col) {
		thus.addExtraMergeFieldsForColumns(data, col);
	}
	
	
	//Remove any child cells
	foreach (data.items as ii => item) {
		
		thisParentsChildren = {};
		
		for (ei = 0; ei < item.cells.length; ++ei) {
			cell = item.cells[ei];
			
			if (parent = childrenToParents[cell.id]) {
				if (!thisParentsChildren[parent]) {
					thisParentsChildren[parent] = [];
				}
				thisParentsChildren[parent].push(item.cells.splice(ei, 1)[0]);
				--ei;
			}
		}
		
		foreach (item.cells as ei => cell) {
			cell.children = thisParentsChildren[cell.id];
		}
	}
	
	

	$panel.html(thus.microTemplate('zenario_organizer_list', thus.items = data));
	$panel.show();
	
};


//methods.showViewOptions = function($header) {
//	//Disable the "view options" button for this panel type
//	$header.find('#organizer_viewOptions').show().addClass('disabled').find('a').prop('onclick', '');
//};




//Functionality for images with tags

methods.addExtraMergeFieldsForCells = function(data, column, row, cell) {
	methodsOf(panelTypes.list).addExtraMergeFieldsForCells.call(thus, data, column, row, cell);
	
	//When drawing each cell, check to see if this column has the tag_colors option is set
	if (column.tuix.tag_colors) {
		
		var t, color, name,
			pickedTags = [],
			tags;
		
		//Check for the get_tags_from_column option.
		if (column.tuix.get_tags_from_column) {
			//If it's set, load tags from a different column and add them into this cell
			tags = row.tuix[column.tuix.get_tags_from_column];
		} else {
			//Otherwise remove what was in this cell and replace it with the tags
			tags = row.tuix[column.id];
			delete cell.value;
		}
		
		if (tags != ''
		 && tags !== null
		 && defined(tags)) {
			
			tags = tags.split(',');
			cell.image_tags = [];
			
			//Create an array of mergefields for tags. If we can't find the colour of the
			//tag, the tag should default to the blue coloured tag.
			foreach (tags as t => name) {
				color = column.tuix.tag_colors[name] || 'blue';
			
				cell.image_tags.push({color: color, name: name});
			}
		}
		
		//Also note these down just in case slidedown view wants to get at these later
		if (!thus.imageTagsById) {
			thus.imageTagsById =
			zenarioO.pi.imageTagsById = {};
		}
		thus.imageTagsById[row.id] =
		zenarioO.pi.imageTagsById[row.id] = cell.image_tags;
	}
};


}, zenarioO.panelTypes);