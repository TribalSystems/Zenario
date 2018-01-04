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
	The code here is not the code you see in your browser. Before this file is downloaded:
	
		1. Compilation macros are applied (e.g. "foreach" is a macro for "for .. in ... hasOwnProperty").
		2. It is minified (e.g. using Google Closure Compiler).
		3. It may be wrapped togther with other files (this is to reduce the number of http requests on a page).
	
	For more information, see js_minify.shell.php for steps (1) and (2), and organizer.wrapper.js.php for step (3).
*/



zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, get, engToBoolean, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	panelTypes, extraVar2, s$s
) {
	"use strict";

//Note: extensionOf() and methodsOf() are our shortcut functions for class extension in JavaScript.
	//extensionOf() creates a new class (optionally as an extension of another class).
	//methodsOf() allows you to get to the methods of a class.
var methods = methodsOf(
	panelTypes.multi_line_list = extensionOf(panelTypes.list)
);

methods.init = function() {
	this.lineHeight = 20;
};


methods.returnIsMultiLineList = function() {
	return true;
};


methods.showPanel = function($header, $panel, $footer) {
	methodsOf(panelTypes.list).showPanel.apply(this, arguments);
};

methods.drawItems = function($panel) {
	var itemIndex, item, cellIndex, cell, cells, 
		multiLineColumnIndex, multiLineColumn, lineIndex, cellId, multiLineCell, cellHeight, line;
		
	this.items = this.getMergeFieldsForItemsAndColumns();
	this.items.multiLineColumns = this.getMergeFieldsForMultiLineColumns();
	
	this.items.totalWidth = 0;
	
	//Turn "items -> cells" structure into "items -> cells -> cell_rows" for multi line rows
	if (this.items.items) {
		foreach (this.items.items as itemIndex => item) {
			item.multiLineCellHeight = this.items.multiLineCellHeight;
			item.multiLineCells = [];
			//Get indexed cell values for this item
			cells = {};
			foreach (item.cells as cellIndex => cell) {
				cells[cell.id] = cell;
			}
			//Create a multi line cell for each column
			foreach (this.items.multiLineColumns as multiLineColumnIndex => multiLineColumn) {
				//Calculate total width
				if (itemIndex == 0) {
					this.items.totalWidth += cells[multiLineColumn.id].width + zenarioO.columnsExtraSpacing + zenarioO.columnPadding;
				}
				
				multiLineCell = $.extend({
					lines: [],
					height: this.items.multiLineCellHeight
				}, cells[multiLineColumn.id]);
				
				//Add cell values to each column as lines
				foreach (multiLineColumn.lines as lineIndex => cellId) {
					line = {
						height: this.lineHeight
					};
					
					if (lineIndex == 0) {
						line.firstLine = true;
					} else if (multiLineColumn.tuix.no_italics) {
						line.no_italics = true;
					}
					
					line = $.extend(line, cells[cellId]);
					multiLineCell.lines.push(line);
					
				}
				
				item.multiLineCells.push(multiLineCell);
			}
		}
	}
	
	$panel.html(this.microTemplate('zenario_organizer_multi_line_list', this.items));
	$panel.show();
	//Tooltip
	$panel.find('.organizer_cell_line').each(function(i, el) { 
		var $el = $(el); 
		if (el.scrollWidth && el.scrollWidth > $el.innerWidth()) { 
			$el.attr('title', $el.text()); 
		} 
	});
	
};

methods.getMergeFieldsForMultiLineColumns = function() {
	var mergeFields = [], 
		firstcell = 'firstcell ', 
		lastcell = 'lastcell ',
		prefs = zenarioO.prefs[this.path] || {},
		multiLineColumns, orderedMultiLineColumns, colNo, colName, colTUIX,
		lastColIndex, maxLines, multiLineColumnCount, colWidth;
	
	//Get ordered list of columns that are parents with their children
	multiLineColumns = {};
	multiLineColumnCount = 0;
	foreach (zenarioO.sortedColumns as colNo => colName) {
		if (zenarioO.isShowableColumn(colName, true)) {
			colTUIX = this.tuix.columns[colName];
			if (colTUIX.parent && this.tuix.columns[colTUIX.parent]) {
				if (!multiLineColumns[colTUIX.parent]) {
					multiLineColumnCount++;
					multiLineColumns[colTUIX.parent] = [];
				}
				multiLineColumns[colTUIX.parent].push(colName);
			}
		}
	}
	
	lastColIndex = multiLineColumnCount - 1;
	maxLines = 1;
	
	foreach (this.items.columns as colNo => col) {
		if (multiLineColumns[col.id]) {
			if (multiLineColumns[col.id].length > maxLines) {
				maxLines = multiLineColumns[col.id].length;
			}
			
			multiLineColumn = $.extend({
				lines: multiLineColumns[col.id]
			}, col);
			
			mergeFields.push(multiLineColumn);
		}
	}
	this.items.multiLineCellHeight = maxLines * this.lineHeight;
	
	return mergeFields;
};

methods.showViewOptions = function($header) {
	//Show the view "options" button
	$header.find('#organizer_viewOptions').show().addClass('disabled').find('a').prop('onclick', '');
};

}, zenarioO.panelTypes);