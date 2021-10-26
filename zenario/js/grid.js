/*
 * Copyright (c) 2021, Tribal Limited
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


zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	zenarioG
) {
	"use strict";
	
	var methods = methodsOf(zenarioG);

	zenarioT.lib(function(
		_$html,
		_$div,
		_$input,
		_$select,
		_$option,
		_$span,
		_$label,
		_$p,
		_$h1,
		_$ul,
		_$li
	) {







methods.initVars = function() {

	thus.editing = false;
	thus.data = {};
	thus.layoutName = '';
	thus.openedSkinId = '';
	thus.history = [];
	thus.pos = 0;
	thus.savedAtPos = 0;
	thus.lastIframeHeight = 360;
	thus.bodyPadding = 0;
	thus.desktopSmallestSize = 800;
	thus.previewPaddingLeftRight = 75;
	thus.gridAreaSmallestHeight = 150;

	thus.tabletWidth = 768;
	thus.mobileWidth = 320;

	thus.lastToast = false;
	
	thus.gridId = '...';
};





methods.init = function(data, gridId) {
	
	thus.initVars();
	thus.gridId = gridId;
	
	if (typeof data == 'object') {
		thus.data = data;
	} else {
		thus.data = JSON.parse(data);
	}
	
	thus.checkDataR(thus.data.cells);
	thus.rememberNames(thus.data.cells);
};





methods.checkDataNonZero = function(warning, data, prop, defaultValue, min) {
	if (1 * data[prop]) {
	} else {
		data[prop] = defaultValue;
		warning = true;
	}
	data[prop] = Math.round(data[prop]);
	
	if (data[prop] <= min) {
		data[prop] = defaultValue;
		warning = true;
	}
	
	return warning;
};

methods.checkDataNumeric = function(warning, data, prop, defaultValue, precision) {
	if (!precision) {
		precision = 1;
	}
	
	if (!defined(data[prop]) || 1 * data[prop] != data[prop]) {
		data[prop] = defaultValue;
		warning = true;
	}
	
	if (precision < 0) {
		precision *= -1;
		data[prop] = Math.round(data[prop] * precision) / precision;
	} else {
		data[prop] = Math.round(data[prop] / precision) * precision;
	}
	
	if (data[prop] < 0) {
		data[prop] = defaultValue;
		warning = true;
	}
	
	return warning;
};

//Validate the data object, and check all of the selected values are sensible
methods.checkDataNonZeroAndNumeric = function(data, warning) {
	
	var warnFix = false,
		warnFlu = false;
	
	warning =	thus.checkDataNonZero(warning, data, 'cols', 10, 0);
	warnFix =	thus.checkDataNonZero(warnFix, data, 'colWidth', 60, 1);
	warnFlu =	thus.checkDataNonZero(warnFlu, data, 'minWidth', 600, 100);
	warning =	thus.checkDataNonZero(warning, data, 'maxWidth', 960, 100);
	warnFlu =	thus.checkDataNumeric(warning, data, 'bp1', 0, 1);
	warnFlu =	thus.checkDataNumeric(warning, data, 'bp2', 0, 1);
	warnFix =	thus.checkDataNumeric(warnFix, data, 'gutter', 40);
	warnFix =	thus.checkDataNumeric(warnFix, data, 'gutterLeftEdge', 0);
	warnFix =	thus.checkDataNumeric(warnFix, data, 'gutterRightEdge', 0);
	warnFlu =	thus.checkDataNumeric(warning, data, 'gutterFlu', 1, -10);
	warnFlu =	thus.checkDataNumeric(warning, data, 'gutterLeftEdgeFlu', 0, -10);
	warnFlu =	thus.checkDataNumeric(warning, data, 'gutterRightEdgeFlu', 0, -10);
	
	if (data.gutterFlu * (data.cols - 1) + data.gutterLeftEdgeFlu + data.gutterRightEdgeFlu > 100) {
		data.gutterFlu = 0;
		data.gutterLeftEdgeFlu = 0;
		data.gutterRightEdgeFlu = 0;
		warnFlu = true;
	}
	
	if (data.minWidth > data.maxWidth) {
		data.minWidth = data.maxWidth;
		warnFlu = true;
	}
	
	return warning || (data.fluid? warnFlu : warnFix);
};

methods.checkDataR = function(cells) {
	var i, j, k,
		classes, html;
	
	//Change how breaks are implemented in the editor/ui. They used to be implemented as groupings;
	//now I want them to be implemented as full-width blocks.
	for (i = 0; i < cells.length; ++i) {
		if (cells[j=i].grid_break_group
		 && cells[j].cells) {
			foreach (cells[j].cells as k) {
				cells.splice(++i, 0, cells[j].cells[k]);
			}
			delete cells[j].cells;
		
			//Handle the case where a break ends but no break immediately follows
			//by adding a new break to mark the closure
			if (!cells[i+1]
			 || (!cells[i+1].grid_break
			  && !cells[i+1].grid_break_group)) {
				cells.splice(++i, 0, {
					grid_break: true,
					grid_css_class: thus.randomName(2, 'Grid_'),
					html: cells[j].after
				});
				delete cells[j].after;
			}
			
			cells[j].grid_break = true;
			cells[j].grid_css_class = cells[j].name || thus.randomName(2, 'Grid_');
			delete cells[j].name;
			delete cells[j].grid_break_group;
		}
	}
	
	//The original data schema had no marker for where the slots were.
	//Add one as it makes things easier to follow
	for (i = 0; i < cells.length; ++i) {
		if (cells[i].name
		 && !cells[i].cells
		 && !cells[i].grid_break
		 && !cells[i].grid_break_group
		 && !cells[i].space) {
			cells[i].slot = true;
		}
	}
	
	//Migrate old names/fix any possible bad data
	for (i = 0; i < cells.length; ++i) {
		classes = [];
		
		//Migrate first and responsive into an enum
		if (cells[i].responsive) {
			cells[i].small = 'hide';
		
		} else if (cells[i].first) {
			cells[i].small = 'first';
		}
		delete cells[i].responsive;
		delete cells[i].first;
		
		//Rename a non-standard name
		if (cells[i].custom_class) {
			classes.push(cells[i].custom_class);
			delete cells[i].custom_class;
		}
		
		//Only Slots should have names!
		if (cells[i].name && (cells[i].space || cells[i].cells)) {
			classes.push(cells[i].name);
			delete cells[i].name;
		}
		
		//Put any of the above into the css_class field
		if (classes.length && cells[i].css_class) {
			classes.push(cells[i].css_class);
		}
		if (classes.length) {
			cells[i].css_class = classes.join(' ');
		}
		
		//Groupings should never have html
		if (cells[i].cells) {
			delete cells[i].html;
			delete cells[i].after;
			delete cells[i].before;
			
			//Recursively check anything inside a grouping
			thus.checkDataR(cells[i].cells);
		
		//If spaces have the before or after properties, convert that to the html property
		} else if (!cells[i].name) {
			html = [];
		
			if (cells[i].after) {
				html.push(cells[i].after);
				delete cells[i].after;
			}
			if (cells[i].before) {
				html.push(cells[i].before);
				delete cells[i].before;
			}
			if (html.length) {
				cells[i].html = html.join(' ');
			}
		
		//Slots
		} else {
			//Remove the old before/after properties
			delete cells[i].after;
			delete cells[i].before;
		}
	}
};



methods.ajaxData = function() {
	return JSON.stringify(thus.data);
};

methods.rememberNames = function(cells) {
	for (var i = 0; i < cells.length; ++i) {
		//Groupings
		if (cells[i].cells) {
			//Recursively check anything inside a grouping
			thus.rememberNames(cells[i].cells);
		
		} else if (cells[i].name) {
			//When loading data, remember what the original name on the slots were
			cells[i].oName = cells[i].name;
		}
	}
};

methods.checkData = function(layoutName, familyName) {
	var scale,
		cols,
		i, j, k;
	
	if (!thus.data) {
		thus.data = {cells: []};
	
	//Attempt to catch a case where data in an old format is loaded
	} else if (_.isArray(thus.data) ) {
		thus.data = {cells: thus.data};
		
		if (familyName && (cols = 1*familyName.replace(/\D/g, ''))) {
			thus.data.cols = cols;
		}
	
	} else if (!thus.data.cells) {
		thus.data.cells = [];
	}
	
	//If switching from a fixed grid to a flexi grid, try to migrate the existing settings to populate the new settings
	if (thus.data.fluid
	 && !defined(thus.data.gutterFlu)
	 && defined(thus.data.gutter)) {
		thus.checkDataNonZeroAndNumeric(thus.data);
		
		scale = thus.data.gutterLeftEdge + thus.data.cols * thus.data.colWidth + (thus.data.cols - 1) * thus.data.gutter + thus.data.gutterRightEdge;
		
		if (scale > 960) {
			thus.data.minWidth = 960;
		
		} else if (scale > 760) {
			thus.data.minWidth = 760;
		
		} else {
			thus.data.minWidth = scale;
		}
		thus.data.maxWidth = scale;
		
		thus.data.gutterFlu = Math.round(thus.data.gutter / scale * 1000) / 10;
		thus.data.gutterLeftEdgeFlu = Math.round(thus.data.gutterLeftEdge / scale * 1000) / 10;
		thus.data.gutterRightEdgeFlu = Math.round(thus.data.gutterRightEdge / scale * 1000) / 10;
		
	//If switching from a flexi grid to a fixed grid, try to migrate the existing settings to populate the new settings
	} else
	if (!thus.data.fluid
	 && !defined(thus.data.gutter)
	 && defined(thus.data.gutterFlu)) {
		thus.checkDataNonZeroAndNumeric(thus.data);
		
		scale = thus.data.maxWidth;
		thus.data.gutter = Math.round(thus.data.gutterFlu * scale / 100);
		thus.data.gutterLeftEdge = Math.round(thus.data.gutterLeftEdgeFlu * scale / 100);
		thus.data.gutterRightEdge = Math.round(thus.data.gutterRightEdgeFlu * scale / 100);
		
		var selected,
			gutTotal = thus.data.gutterLeftEdge + (thus.data.cols - 1) * thus.data.gutter + thus.data.gutterRightEdge;
		
		thus.data.colWidth = (scale - gutTotal) / thus.data.cols;
		
		//Because percentage based numbers won't convert perfectly to pixels, we might be off of the total size slightly.
		//Attempt to correct this by changing the col-width/gutter to the next best values.
		if (selected = thus.recalcColumnAndGutterOptions(thus.data, true, scale)) {
			thus.data.colWidth = selected.colWidth;
			thus.data.gutter = selected.gutter;
		} else {
			delete thus.data.colWidth;
			delete thus.data.gutter;
		}
	}
	
	thus.checkDataNonZeroAndNumeric(thus.data);
	
	if (thus.data.fluid) {
		delete thus.data.gutter;
		delete thus.data.gutterLeftEdge;
		delete thus.data.gutterRightEdge;
		delete thus.data.colWidth;
	} else {
		delete thus.data.gutterFlu;
		delete thus.data.gutterLeftEdgeFlu;
		delete thus.data.gutterRightEdgeFlu;
	}
	
	//if (change) {
	//	change(thus.data);
	//}
	
	if (!thus.history.length) {
		thus.pos = 0;
		thus.history = [thus.ajaxData()];
	}
};




methods.draw = function(doNotRedrawForm) {
	//if (!doNotRedrawForm) {
	//	thus.drawOptions();
	//}
	//
	//if (thus.editing) {
		thus.drawEditor();
	//} else {
	//	thus.drawPreview();
	//}
};

methods.cellLabel = function(cell) {
	return cell.name;
};



//Draw/redraw the boxes with controls to add, move and resize them
methods.drawEditor = function(
	thisContId, thisCellId, 
	levels,
	gCols, gColWidth, gGutter, gGutterLeftEdge, gGutterRightEdge,
	gGutterNested,
	gColWidthPercent, gGutterWidthPercent
) {
	var data,
		level = 0,
		gridScrollTop = 0;
	
	//thus.data should contain all of the information on the boxes
	//You can have boxes within boxes, so this can be recursive
	//When we're drawing recursively, levels will contain an array of indices
	//that make up the current recursion path.
	if (levels && defined(levels[0])) {
		data = thus.data;
		foreach (levels as var l) {
			l *= 1;
			level = l+1;
			data = data.cells[levels[l]];
		}
	} else {
		gridScrollTop = $('#' + thus.gridId).scrollTop();
		thus.checkData();
		data = thus.data;
		level = 0;
		levels = [];
		thus.names = {};
		thus.randomNameCount = 0;
	}
	
	if (!defined(thisContId)) {
		thisContId = thus.gridId;
	}
	if (!defined(gCols)) {
		gCols = thus.data.cols;
	}
	if (!defined(gColWidth)) {
		if (thus.data.fluid) {
			//The UI only offers a fixed size, not a flexi size.
			
			//Remember some details on what the actual values should be
			gGutterWidthPercent = thus.data.gutterFlu;
			gColWidthPercent = (100 - thus.data.gutterLeftEdgeFlu - (thus.data.cols - 1) * thus.data.gutterFlu - thus.data.gutterRightEdgeFlu) / thus.data.cols;
			
			//Then convert fluid designs into fixed designs using 10px = 1%
			gGutter = Math.round(thus.data.gutterFlu * thus.data.maxWidth / 100);
			gGutterLeftEdge = Math.round(thus.data.gutterLeftEdgeFlu * thus.data.maxWidth / 100);
			gGutterRightEdge = Math.round(thus.data.gutterRightEdgeFlu * thus.data.maxWidth / 100);
			gGutterNested = gGutter;
			gColWidth = Math.round(gColWidthPercent * thus.data.maxWidth / 100);
		
		} else {
			gGutterWidthPercent =
			gColWidthPercent = 0;
			gGutter = thus.data.gutter;
			gGutterLeftEdge = thus.data.gutterLeftEdge;
			gGutterRightEdge = thus.data.gutterRightEdge;
			gGutterNested = gGutter;
			gColWidth = thus.data.colWidth;
		}
	}
	
	
	var html = '',
		eyesThisLine = {},
		widthSoFarThisLine,
		largestWidth = 1,
		cells = data.cells,
		elId = thus.gridId + '__cell' + levels.join('-'),
		gColAndGutterWidth = gColWidth + gGutter,
		wrapWidth = gCols * gColAndGutterWidth,
		wrapPaddingLeft = 0,
		wrapPaddingRight = 0,
		wrapWidthAdjustmentLeft = 0,
		wrapWidthAdjustmentRight = 0,
		gGutterLeft = Math.ceil(gGutter / 2),
		gGutterRight = Math.floor(gGutter / 2),
		gGutterLeftNested = Math.ceil(gGutterNested / 2),
		gGutterRightNested = Math.floor(gGutterNested / 2);
	
	if (gGutterLeftEdge >= gGutterLeft) {
		wrapPaddingLeft = gGutterLeftEdge - gGutterLeft;
	} else {
		wrapWidth -= (wrapWidthAdjustmentLeft = gGutterLeft - gGutterLeftEdge);
	}
	
	if (gGutterRightEdge >= gGutterRight) {
		wrapPaddingRight = gGutterRightEdge - gGutterRight;
	} else {
		wrapWidth -= (wrapWidthAdjustmentRight = gGutterRight - gGutterRightEdge);
	}
	
	
	
	//Each level should have a <ul> tag to contain it.
	//All a little bit of padding (which will be hidden with an "overflow-x: hidden;") to help prevent various bugs
	//of things wrapping onto new lines when dragging things around.
	if (level == 0) {
		html += '<div class="zenario_overflow_wrap" style="width: ' + wrapWidth + 'px; overflow-x: hidden;">';
		html += '<ul id="' + elId + 's" class="zenario_grids" style="width: ' + (wrapWidth + Math.abs(gGutterRight - gGutterRightEdge) + 1) + 'px;';
	} else {
		html += '<ul id="' + elId + 's" class="zenario_grids" style="width: ' + wrapWidth + 'px;';
	}
	
	if (wrapPaddingLeft) {
		html += 'padding-left: ' + wrapPaddingLeft + 'px;';
	}
	if (wrapPaddingRight) {
		html += 'padding-right: ' + wrapPaddingRight + 'px;';
	}
	
	//If this is the outer-most tag, add a pink striped background so we can easily see the grid
	if (level == 0) {
		html += 'background: white top left repeat-y url(' + htmlspecialchars(URLBasePath) + 'zenario/admin/grid_maker/grid_bg.php?gColWidth=' + gColWidth + '&gCols=' + gCols + '&gGutter=' + gGutter + '&gGutterLeftEdge=' + gGutterLeftEdge + '&gGutterRightEdge=' + gGutterRightEdge + ');';
		
		//Remember the width and height for typical new elements.
		//This will be used later when trying to drag them in from the "add" toolbar
		thus.gGutterLeftEdge = gGutterLeftEdge;
		thus.gGutterLeft = gGutterLeft;
		thus.gGutterRight = gGutterRight;
		thus.gGutterRightEdge = gGutterRightEdge;
		thus.gColAndGutterWidth = gColAndGutterWidth;
		thus.typAddSlot = 2 * gColAndGutterWidth - gGutter;
		thus.typAddBreak = gCols * gColAndGutterWidth - gGutter;
	}
		
	html += '">';
	
	if (level > 0 && thus.globalName!='zenarioSD') {
	
	    
		html +=
			_$div('class', 'zenario_grid_object_properties',
				_$div(
					_$span(
						'data-type', 'grouping',
						'data-for', thisCellId,
						'data-small', data.small,
						'data-is_full', engToBoolean(data.isAlpha && data.atRightEdge),
						'data-is_last', engToBoolean(!data.isAlpha && data.isOmega),
						'data-css_class', data.css_class,
						'title', phrase.gridEditProperties,
							data.css_class?
								htmlspecialchars(data.css_class)
							 :	phrase.gridEditPlaceholder
					)
				)
			) + '<br/>';
	}
	
	if (!cells) {
		cells = [];
	}
	
	//Look out for empty nests, and remove them
	for (var i = cells.length - 1; i >= 0; --i) {
		if (cells[i].cells
		 && thus.checkCellsEmpty(cells[i])) {
			cells.splice(i, 1);
		}
	}
	
	
	//Try and work out which cells are on the start and which cells are on the end of a line
	//We actually need to run this twice: calling the drawEditor() function recursively will accurately tell us the widths of
	//nested cells, so we need to check this after drawEditor() has run recursively, but drawEditor() needs to know the widths
	//in advance so we'll calculate it before as well.
	widthSoFarThisLine = gCols;
	var lastI = false;
	foreach (cells as var i) {
		if (cells[i].grid_break) {
			cells[i].width = gCols;
		
		} else {
			cells[i].width = Math.min(gCols, cells[i].width || 1);
		}
		
		widthSoFarThisLine += cells[i].width;
		
		if (cells[i].width == gCols
		 || widthSoFarThisLine % gCols == 0) {
			cells[i].atRightEdge = true;
		}
		
		if (widthSoFarThisLine > gCols) {
			widthSoFarThisLine = cells[i].width;
			
			if (lastI !== false) {
				cells[lastI].isOmega = true;
			}
			cells[i].isAlpha = true;
		}
		
		lastI = i;
	}
	if (lastI !== false) {
		cells[lastI].isOmega = true;
	}
	
	//Slight modification - newly created slots should not be full-size.
	//(Nested cells should still be full-sized upon creation)
	if (level == 0) {
		//Make them about 2-columns wide (depending on the number of columns)
		var partialWidth = Math.max(1, Math.round(gCols / 5));
	}
	
	//Draw each cell as a <li>, giving each a unique id, and storing some information against each
	//so we can work out which bit of the data the <li> matches up to
	foreach (cells as var i) {
		
		if (thus.data.responsive) {
			if (cells[i].small == 'first' && !(!cells[i].isAlpha && cells[i].isOmega)) {
				delete cells[i].small;
			}
			if (cells[i].small == 'only' && !(cells[i].isAlpha && cells[i].isOmega)) {
				delete cells[i].small;
			}
		}
		
		cells[i].widthPx =
			cells[i].width * gColAndGutterWidth
			- (cells[i].isAlpha? wrapWidthAdjustmentLeft : 0)
			- (cells[i].atRightEdge? wrapWidthAdjustmentRight : 0);
		
		cells[i].widthPercent = cells[i].width * gColWidthPercent + (cells[i].width - 1) * gGutterWidthPercent;
		
		
		cells[i].marginLeft = cells[i].isAlpha && wrapWidthAdjustmentLeft? gGutterLeftEdge : gGutterLeft;
		cells[i].marginRight = cells[i].atRightEdge && wrapWidthAdjustmentRight? gGutterRightEdge : gGutterRight;
		
		html += _$li(
			'id', elId + '-' + i,
			'data-i', i,
			'data-level', level,
			'data-levels', levels.join('-'),
			'data-gutter', gGutter,
			'data-minwidth', (gColAndGutterWidth * 1 - gGutter),
			'data-maxwidth', (gColAndGutterWidth * gCols - gGutter),
			'data-is_alpha', engToBoolean(cells[i].isAlpha),
			'data-is_omega', engToBoolean(cells[i].isOmega),
			'data-at_right_edge', engToBoolean(cells[i].atRightEdge),
			'data-col_and_gutter_width', gColAndGutterWidth,
			'data-displayed_width', (cells[i].width * gColAndGutterWidth - gGutter),
			'data-displayed_margin_left', (cells[i].marginLeft),
			' ');
			
		var resizable = '';
		
		if (!cells[i].grid_break
		 && cells[i].small != 'only') {
			resizable = 'zenario_grid_cell_resizable';
		}
		
		html += ' class="zenario_grid_cell ' + resizable + ' zenario_grid_cell__' + elId;
		
		if (cells[i].flash && !thus.undoing) {
			//thus.newlyAdded = elId + '-' + i;
			delete cells[i].flash;
			
			html +=  ' zenario_grid_newly_added';
		}
		
		if (cells[i].cells) {
			html += ' zenario_grid_nest"';
		
		} else if (cells[i].space) {
			html += ' zenario_grid_space_cell"';
		
		} else {
			html += ' zenario_grid_slot"';
		}
		
		html += ' style="width: ' + cells[i].widthPx + 'px;">';
		
		
		
		
		
		if (cells[i].grid_break) {
			html += _$div('class', 'zenario_grid_break_outer_wrap', '>');
		}
		
		var marginStyle =
				'margin-left: ' + cells[i].marginLeft + 'px; ' +
				'margin-right: ' + cells[i].marginRight + 'px;';
		
		if (cells[i].cells) {
			//For cells that have children
			//Don't add any contents to this div as any contents will be replaced with the children
			html += _$div('id', elId + '-' + i + '-span', 'class', 'zenario_grid_wrap');
		
		} else if (!cells[i].slot) {
			//Empty space
			html += _$div(
				'class', 'zenario_cell_in_grid zenario_grid_gutter ' + (cells[i].grid_break? 'zenario_grid_break zenario_grid_break_no_slot' : 'zenario_grid_space'),
				'style', marginStyle,
				'title', cells[i].grid_break? phrase.gridGridBreak : phrase.gridEmptySpace,
			'>');
				
				html += _$div('class', 'zenario_grid_delete', 'title', phrase.gridDelete, 'data-for', elId + '-' + i, 'style', 'right: ' + (cells[i].marginRight) + 'px;');
				html += _$div('&nbsp;');
				html += _$div('&nbsp;');

				html +=
					'<div class="zenario_grid_object_properties"><div><span' +
						' data-type="' + (cells[i].grid_break? 'grid_break' : 'space') + '"' +
						' data-for="' + elId + '-' + i + '"' +
						' data-html="' + htmlspecialchars(cells[i].html) + '"';
				
				var displayName;
				
				if (cells[i].grid_break) {
					html += ' data-grid_css_class="' + htmlspecialchars(displayName = cells[i].grid_css_class) + '"';
				} else {
					html += ' data-css_class="' + htmlspecialchars(displayName = cells[i].css_class) + '"';
				}
				html +=
						' title="' + phrase.gridEditProperties + '"' +
					'>' +
						(displayName?
							htmlspecialchars(displayName)
						 :	phrase.gridEditPlaceholder) +
					'</span></div></div>';
			html += '</div>';
		
		} else {
			if (!cells[i].name) {
				cells[i].name = thus.uniqueRandomName();
			
			} else if (!thus.checkIfNameUsed(cells[i].name)) {
				thus.registerNewName(cells[i].name);
				++thus.randomNameCount;
			}
			
			var label = thus.cellLabel(cells[i]);
			if(thus.globalName == 'zenarioSD'){
			    if(_.isEmpty(thus.cellLabel(cells[i]))){
			        label = phrase.gridNoPluginMessage;
			    
			    }
			
			}
			//Draw the current size for cells that should have a slot
			html += _$div('class', 'zenario_grid_border zenario_cell_in_grid zenario_grid_gutter ' + (cells[i].grid_break? 'zenario_grid_break zenario_grid_break_with_slot' : ''), 'style', marginStyle, '>');
				
				html += _$div('class', 'zenario_grid_name_area', 
					_$div('class', 'zenario_grid_delete', 'title', phrase.gridDelete, 'data-for', elId + '-' + i, 'style', 'right: ' + (cells[i].marginRight) + 'px;') + 
					_$div('class', 'zenario_grid_faux_block_t') +
					_$div('class', 'zenario_grid_object_properties',
						_$div(
							_$span(
								'data-type', cells[i].grid_break? 'grid_break_with_slot' : 'slot',
								'data-for', elId + '-' + i,
								'data-name', cells[i].name,
								'data-small', cells[i].small,
								'data-height', cells[i].height,
								'data-is_full', engToBoolean(cells[i].isAlpha && cells[i].atRightEdge),
								'data-is_last', engToBoolean(!cells[i].isAlpha && cells[i].isOmega),
								'data-css_class', cells[i].css_class,
								'title', phrase.gridEditProperties,
								'data-grid_css_class', cells[i].grid_break && cells[i].grid_css_class,
									htmlspecialchars(label)+
									(cells[i].height && cells[i].height != 'small'? ' (' + cells[i].height + ')': '') 
							)
						)
					)
				);
				
				html += _$div('class', 'zenario_grid_faux_block_r');
				html += _$div('class', 'zenario_grid_cell_size', '>');
				
				if (!cells[i].grid_break) {
					var size = cells[i].width * gColAndGutterWidth - gGutter;
					if (thus.data.fluid) {
						if (level) {
							html += Math.round(cells[i].widthPercent) + '%';
						} else {
							html += Math.round(cells[i].widthPercent * 10) / 10 + '%';
						}
					} else {
						html += size + 'px';
					}
				}
				
				html += '</div>';
				html += _$div('class', 'zenario_grid_faux_block_t');
			
			html += '</div>';
		}
		
		if (cells[i].grid_break) {
			html += '</div>';
		}
		
		html += '</li>';
	}
	
	html += '</ul>';
	
	if (level == 0) {
		html += '</div>';
	}
	
	$('.ui-tooltip').remove();
	get(thisContId).innerHTML = html;
	
	
	//Loop through the cells again
	widthSoFarThisLine = 0;
	foreach (cells as var i) {
		i *= 1;
		
		
		
		//If a cell has contents, draw the contents recursively
		if (cells[i].cells) {
			var childColWidth = gColWidth,
				gColsNested = cells[i].width,
				editorWidth = cells[i].widthPx - cells[i].marginLeft - cells[i].marginRight;
			
			
			levels.push(i);
			var largestSubWidth =
					thus.drawEditor(
						elId + '-' + i + '-span', elId + '-' + i,
						levels,
						gColsNested, childColWidth, gGutterNested,
						cells[i].isAlpha? gGutterLeftEdge - wrapPaddingLeft : gGutterLeftNested,
						cells[i].atRightEdge? gGutterRightEdge - wrapPaddingRight : gGutterRightNested,
						gGutterNested,
						cells[i].widthPercent / gColsNested, 0);
			levels.pop();
			
			//Remember the largest width of the child cells that we saw.
			//When resizing, it shouldn't be possible to shrink this cell smaller than this size
			if (largestSubWidth > 1) {
    			//Disabled for now!
			    //$('#' + elId + '-' + i).attr('data-minwidth', (gColAndGutterWidth * largestSubWidth - gGutter));
				
				//If there's something large in a grouping, ensure the grouping is at least that large
				cells[i].width = Math.max(cells[i].width, largestSubWidth);
			}
		}
		
		largestWidth = Math.max(largestWidth, cells[i].width);
		
		//Remove our isAlpha and isOmega flags from the data
		delete cells[i].isAlpha;
		delete cells[i].isOmega;
		delete cells[i].atRightEdge;
		delete cells[i].widthPx;
		delete cells[i].widthPercent;
		delete cells[i].marginLeft;
		delete cells[i].marginRight;
		
		//All the cells will have the "float: left;" style, so we don't need any special logic
		//to handle line-breaks because the browser will do that for us.
		//However I do want to add special logic for the height - the height of a cell should be
		//greater than the combined the height of its contents (and we need to take into account that
		//its contents may be on more than one line).
		//So keep track of how much width we've used, versus how wide a row is. If we've gone over, start a new line.
		widthSoFarThisLine += cells[i].width;
		if (widthSoFarThisLine > gCols) {
			var eye = 0, maxHeight = 0;
			
			//Set the height of everything on the line we've just had to the tallest thing on that line
	    	////Note: this actually seems to cause more bugs than it fixes, so we've disabled this logic
	    	//////OK, found out why this was needed, re-enabling it again
			foreach (eyesThisLine as eye) {
				maxHeight = Math.max(maxHeight, $('#' + elId + '-' + eye).height());
			}
			foreach (eyesThisLine as eye) {
				$('#' + elId + '-' + eye).height(maxHeight);
			}
			
			//Keep track on which cells are on this line
			widthSoFarThisLine = cells[i].width;
			eyesThisLine = {};
		}
		eyesThisLine[i] = true;
	}
	
	
	//Same code as above, but run this one last time to apply it to the last row:
	var eye = 0, maxHeight = 0;
	
	//Set the height of everything on the line we've just had to the tallest thing on that line
	    //Note: this actually seems to cause more bugs than it fixes, so we've disabled this logic
	    	//////OK, found out why this was needed, re-enabling it again
	foreach (eyesThisLine as eye) {
		maxHeight = Math.max(maxHeight, $('#' + elId + '-' + eye).height());
	}
	foreach (eyesThisLine as eye) {
		$('#' + elId + '-' + eye).height(maxHeight);
	}
	
	
	//Make all the cells for the current container sortable
	$('#' + elId + 's').disableSelection();
	$('#' + elId + 's').sortable({
		cancel: 'a,button,input,select',
		placeholder: 'zenario_grid_cell zenario_grid_sortable_target',
		sort: function(event, ui) {
		    //Function call during the reordering/sorting process
			var $target = $('.zenario_grid_sortable_target'),
				top = 10,
				bottom = 14,
				add = ui.item.data('add'),
				width,
				height,
				left,
				right,
				$neighbor;
			
			//Stop a bug where <div>s with "overflow-x: hidden;" set could be scrolled to reveal the "hidden" areas.
			$('.zenario_overflow_wrap').scrollLeft(0);
			
			if (!defined(add)) {
				width = ui.item.width();
				height = ui.item.height();
				left = 1*ui.item.data('displayed_margin_left');
				right = width - left - 1*ui.item.data('displayed_width');
				
			
				$target.height(height);
				height -= top + bottom;
			
				$target
					.width(width)
					.html(_$div('style', 'margin: ' + top + 'px ' + right + 'px ' + bottom + 'px ' + left + 'px; height: ' + height + 'px;'));
			
			} else {
				height = 70;
				switch (add) {
					case 'grid_break':
					case 'grid_break_with_slot':
						width = thus.typAddBreak;
						break;
					case 'grouping':
						//height = 200;
					default:
						width = thus.typAddSlot;
				}
				
				
				$neighbor = $target.next();
				if (!$neighbor.length) {
					$neighbor = $target.prev();
				}
				
				
				if ($neighbor.length) {
					height = $neighbor.height() - 10;
				}
				
				$target
					.height(height)
					.width(width)
					.css('margin-left', 0)
					.css('margin-right', 0)
					.html(_$div());
				
				//Check if there's another element to the right of this one (excluding the thing being dragged)
				var $next = $target.next();
				
				if ($next.hasClass('ui-draggable')) {
					$next = false;
				}
				
				//Check if this is on the start of the line
				var isLeftmost = $target.offset().left < thus.gColAndGutterWidth,
				
					//Check if this is on the end of the line
					isRightmost = !$next || !$next.offset() || $next.offset().left < $target.offset().left,
				
					nextWasAtLeftEdge = !!($next && 1*$next.data('is_alpha')),
				
					nextWasAtRightEdge = !!($next && 1*$next.data('at_right_edge'));
                
                
				
				//Try to work out what the margins should be for the newly added slot.
            	right = thus.gGutterRight;
            	
            	//Check if this is the first slot on the line
				if (isLeftmost) {
            		//If so, use the left edge gutter.
            		left = thus.gGutterLeftEdge;
            	} else {
            		left = thus.gGutterLeft;
            	}
            	
				if (isRightmost) {
            		right = thus.gGutterRightEdge;
            	} else {
            		right = thus.gGutterRight;
            	}
				
				//Note that the slot we're shifting right will also have used the left edge gutter,
				//we'll need to adjust our right margin to accommodate for the difference between
				//the left edge gutter and the normal left edge.
				if (nextWasAtLeftEdge) {
					right += thus.gGutterLeft - thus.gGutterLeftEdge;
				}
            	
				
				$target
					.css('margin-left', left)
					.css('margin-right', right);
			}
		},
		
		//Only allow sorting on grid elements, and not the add-controls
		items: 'li.zenario_grid_cell__' + elId,
		
		//Allow slots to be dragged into/out of groupings
		connectWith: '.zenario_grids',
		
		//Function to handle a reordering
		start: function(event, ui) {
		   
			//Stop the rename event firing at the same time
			thus.stopRenames = true;
		},
			
		stop: function(event, ui) {
		    
			//Get an array containing the new sort order
			var li, level,
				data = thus.data,
				dMoved = ui.item.data(),
				dPrev = ui.item.prev().data(),
				dNext = ui.item.prev().data(),
				dParent = ui.item.parent().parent().parent().data(),
				moved,
				fromPos,
				toPos,
				samePos,
				fromContainer,
				toContainer,
				sameContainers;
			
			if (!dParent || !defined(dParent.level)) {
				dParent = undefined;
			}
			
			//Get the original's container (unless this is a new item from the "add" toolbar)
			if (!dMoved.add) {
				fromPos = dMoved.i;
				
				fromContainer = data;
				if (1*dMoved.level) {
					levels = ('' + dMoved.levels).split('-');
					foreach (levels as li => level) {
						fromContainer = fromContainer.cells[1*level];
					}
				}
			}
			
			if (dPrev) {
				toPos = dPrev.i + 1;
			} else {
				toPos = 0;
			}
			
			//Get the destination's container
			toContainer = data;
			if (dParent) {
				if (dParent.levels != '') {
					levels = (dParent.levels + '-' + dParent.i).split('-');
				} else {
					levels = [dParent.i];
				}
				foreach (levels as li => level) {
					toContainer = toContainer.cells[1*level];
				}
			}
			
			samePos = fromPos == toPos;
			sameContainers = fromContainer === toContainer;
			
			if (sameContainers) {
				if (samePos) {
					//Don't bother doing anything if the item wasn't actually moved anywhere.
					return;
				
				} else if (toPos > fromPos) {
					//Account for the effect of the removal of the item on the position
					--toPos;
				}
			}
			
			//Do not allow grid-breaks are to be moved into a child container,
			//they are only allowed at the top level!
			if (sameContainers || !fromContainer || !fromContainer.cells[fromPos].grid_break) {
				
				var msg,
					newWidth = 2;
				
				//If this is something from the "add" toolbar, create a new element
				switch (dMoved.add) {
					case 'slot':
						moved = {
							width: newWidth,
							slot: true,
							name: thus.uniqueRandomName(),
							flash: true
						};
						msg = phrase.growlSlotAdded;
						if (dParent && thus.globalName == 'zenarioSD') {
						    //keep the width of this slot same as the grouping width
							moved.width = toContainer.width;
						}
						break;
						
					case 'space':
						moved = {
							width: newWidth,
							space: true,
							css_class: thus.randomName(2, 'Space_'),
							flash: true
						};
						msg = phrase.growlSpaceAdded;
						break;
						
					case 'grouping':
						//In Slide Designer, only allow groupings at the top level
						if (dParent && thus.globalName == 'zenarioSD') {
							thus.change();
							return;
						}
						
						moved = {
							width: newWidth,
							css_class: thus.randomName(2, 'Grouping_'),
							flash: true,
							cells: [
								{width: newWidth, slot: true, name: thus.uniqueRandomName()},
								{width: newWidth, slot: true, name: thus.uniqueRandomName()}
							]
						};
						msg = phrase.growlChildrenAdded;
						break;
						
					case 'grid_break':
						
						//Only allow grid breaks at the top level
						if (dParent) {
							thus.change();
							return;
						}
						
						moved = {
							width: data.cols,
							grid_break: true,
							grid_css_class: thus.randomName(2, 'Grid_'),
							flash: true
						};
						msg = phrase.growlGridBreakAdded;
						break;
						
					case 'grid_break_with_slot':
						
						//Only allow grid breaks at the top level
						if (dParent) {
							thus.change();
							return;
						}
						
						moved = {
							width: data.cols,
							grid_break: true,
							slot: true,
							grid_css_class: thus.randomName(2, 'Grid_'),
							name: thus.uniqueRandomName(),
							flash: true
						};
						msg = phrase.growlSlotAdded;
						break;
					
					//If this was an existing elements that was just moved, cut it out from the old position 
					default:
						moved = fromContainer.cells.splice(fromPos, 1)[0];
						msg = phrase.growlSlotMoved;
				}
				
				//Insert into the new position
				toContainer.cells.splice(toPos, 0, moved);
				toastr.success(msg);
			}
			
			thus.change();
			return;
		}
	});
	
	//Add some logic that's run against everything
	if (level == 0) {
		//Make all the cells (except responsive children) resizable
		//(Note that this logic glitches out if you call it recursively, it must be run at the end on everything at once.)
		$('#' + thisContId + ' .zenario_grid_cell_resizable').each(function(i, el) {
			var $el = $(el),
				offset = $el.width() - 1 * $el.data('displayed_width'),
				gColAndGutterWidth = 1 * $el.data('col_and_gutter_width'),
				marginLeft = 1 * $el.data('displayed_margin_left'),
				minWidth = 1 * $el.data('minwidth'),
				maxWidth = 1 * $el.data('maxwidth'),
				level = 1 * $el.data('level');
			
			$el.resizable({
				cancel: 'a,button,input,select',
				handles: 'se',
				grid: gColAndGutterWidth,
				minHeight: $el.height(),
				maxHeight: $el.height(),
				
				//Here I actually want minWidth and maxWidth to be equal to minWidth and maxWidth,
				//but there is some sort of weird rounding error in jQuery 1.10, so I'm adding half
				//a column's padding either way. They should still round and snap to the right values.
				minWidth: Math.floor(minWidth - 0.5 * gColAndGutterWidth + 1),
				maxWidth: Math.floor(maxWidth + 0.5 * gColAndGutterWidth - 1),
				helper: "ui-resizable-helper",
				
				start: function(event, ui) {
				    
				   
					if ($el.hasClass('zenario_grid_nest')) {
						$(document.body).addClass('zenario_grid_resizing_nest').removeClass('zenario_grid_resizing_cell').removeClass('zenario_grid_resizing_space_cell');
					
					} else if ($el.hasClass('zenario_grid_space_cell')) {
						$(document.body).removeClass('zenario_grid_resizing_nest').removeClass('zenario_grid_resizing_cell').addClass('zenario_grid_resizing_space_cell');
					
					} else {
						$(document.body).removeClass('zenario_grid_resizing_nest').addClass('zenario_grid_resizing_cell').removeClass('zenario_grid_resizing_space_cell');
					}
				},
				
				//Function to display the current size whilst resizing
				resize: function(event, ui) {
					var hw = ui.helper.width(),
						ew = ui.element.width(),
						size = hw - offset,
						html = '';
					
					if (hw != ew) {
						if (!thus.data.fluid) {
							html += size + 'px';
						
						} else {
							html += '~' + Math.round(size * 100 / thus.data.maxWidth) + '%';
						}
					}
					ui.helper.html(
						_$div('class', 'zenario_resizing_border', 'style', 'height: ' + (ui.helper.height() - 4) + 'px; width: ' + (size - 4) + 'px; margin-left: ' + marginLeft + 'px;',
							_$div('class', 'zenario_size_when_resizing', html)
						)
					);
				},
				
				//Function to update the data when we're finished resizing
				stop: function(event, ui) {
				   
						$(document.body).removeClass('zenario_grid_resizing_nest').removeClass('zenario_grid_resizing_cell').removeClass('zenario_grid_resizing_space_cell');
					
					var i = 1*ui.element.data('i'),
						levels = thus.getLevels(ui.element),
						data = thus.data;
					
					
					//The data variable should be a pointer to the right location in the thus.data object.
					//(This will either be the thus.data object or a subsection if the cells being re-ordered are
					// children of another cell.)
					if (levels) {
						levels = levels.split('-');
						foreach (levels as var l) {
							l *= 1;
							data = data.cells[1*levels[l]];
						}
					}
					
					var widthArray = [],
					    maxWidthArray,
					    maximum = 0,
					    resizedCell = data.cells[1*i],
    					
    					//Update the width of the cell that was just resized
					    oldWidthCols = resizedCell.width,
					    newWidthCols = resizedCell.width = Math.round((ui.element.width() + $el.data('gutter') / 2) / gColAndGutterWidth),
					    groupCells = data.cells[i].cells,
					    checkWidths = function(groupCells) {
                            if (groupCells) {
        					    var j, groupCell;
                                foreach (groupCells as j => groupCell) {
                                    if (groupCell.width == oldWidthCols
                                     || groupCell.width > newWidthCols) {
                                        groupCell.width = newWidthCols;
                                    }
                                    checkWidths(groupCell.cells);
                                }
                            }
					    };
					
					checkWidths(groupCells);
					thus.change();
				}
			});
		});
		
		//Hack to set the correct position for the resize handles - unlike the delete button I can't set this manually when drawing the HTML
		$('#' + thisContId + ' .ui-resizable-handle').each(function(i, el) {
			
			var $el = $(el),
				prev = $el.prev(),
				grouping = prev.children().first();
			
			
			if (grouping && grouping.length && grouping.hasClass('zenario_grids')) {
				//For nested cells, put the resize tool for the nest at the very bottom of the nest
				$el.css('right', '-2px');
				$el.css('top', (prev.height() - 12) + 'px');
				$el.attr('title', phrase.gridResizeNestedCells);
			
			} else {
				$el.css('right', prev.css('marginRight').replace(/\D/g, '') * 1);
				
				//For cells, put the resize tool at the bottom of the visible section
					//To get this right, we'll need to take the height of the visible section,
					//then manually add in the upper margin, upper border, upper padding, lower padding and lower border,
					//then subtract the height of the resize button.
				$el.css('top', (prev.height() + 10 + 2 + 10 + 10 + 2 - 19) + 'px');
				$el.attr('title', phrase.gridResizeSlot);
				
				if (thus.globalName == 'zenarioSD' && $el.parent().data('level') == 1) {
				    $el.remove();
				}
			}
		});
		
		//Set the width of the overal content to the grid's full width, and add tooltips
		$('#' + thisContId).width(wrapWidth + wrapPaddingLeft + wrapPaddingRight).addClass('zenario_grid_wrapper');
		thus.tooltips('#' + thisContId);
		
		//Attach the delete function to the delete buttons
		$('#' + thisContId + ' .zenario_grid_delete').click(function() {
			thus.deleteCell(this); 
		});
		
		//Add another colorbox to the slot names, that allows you to rename a slot name by clicking on it
		$('#' + thisContId + ' .zenario_grid_object_properties span').click(function() {
		   
			if (!thus.stopRenames) {
				thus.editProperties(this);
			}
		});
		
		//Workaround for a bug where if you click and drag on a label,
		//you are then prompted to rename it immediate afterwards
		setTimeout(function() { delete thus.stopRenames; }, 100);
		
		thus.drawLinks();
		
		//Fade in any newly added objects
		$('#' + thisContId + ' .zenario_grid_newly_added .zenario_cell_in_grid').effect({effect: 'highlight', duration: 1000, easing: 'zenarioOmmitEnd'});
		
		////Scroll the page down slightly to show any newly adding objects
		//if (thus.newlyAdded) {
		//	gridScrollTop += $('#' + thus.newlyAdded).height();
		//	delete thus.newlyAdded;
		//}
		
		$('#' + thus.gridId).scrollTop(gridScrollTop);
		
		
		thus.drawAddToolbar();
	
	} else {
		return largestWidth;
	}
};

methods.tooltips = function(sel) {
    return zenario.tooltips(sel);
};

methods.clearAddToolbar = function() {
};

methods.drawAddToolbar = function() {
};

methods.drawLinks = function() {
};

methods.checkCellsEmpty = function(data) {
	if (!data || !data.cells) {
		return true;
	}
	
	var i = undefined;
	foreach (data.cells as i) {
		break;
	}
	
	return !defined(i);
};


//Delete something
methods.deleteCell = function(el) {
	var cell,
		i,
		levels,
		data,
		slot,
		doDelete;
	
	//Try to get the cell that the delete button was for
	if ((cell = $(el).data('for'))
	 && (cell = $('#' + cell))) {
		i = cell.data('i');
		levels = thus.getLevels(cell);
		data = thus.data;
		
		//The data variable should be a pointer to the right location in the thus.data object.
		//(This will either be the thus.data object or a subsection if the cells being re-ordered are
		// children of another cell.)
		if (levels) {
			levels = levels.split('-');
			foreach (levels as var l) {
				l *= 1;
				data = data.cells[1*levels[l]];
			}
		}
		slot = data.cells[i];
		
		//Remove the deleted element
		doDelete = function() {
			data.cells.splice(i, 1);
			thus.change();
			toastr.success(phrase.growlSlotDeleted);
		};
		
		//If this slot was never saved, allow it to be instantly deleted.
		//If this slot has been previously saved, show a prompt with some info first
		if (slot.oName
		 && thus.layoutId
		 && thus.confirmDeleteSlot) {
			thus.confirmDeleteSlot(slot, doDelete);
		} else {
			doDelete();
		}
	}
};

//Add something (using the popup form)
methods.add = function(el, type, respClass) {
	//Try to get the level that the add button was for
	var $el = $(el),
		levels = thus.getLevels($el),
		newWidth = $el.attr('data-new-width') || 1,
		data = thus.data;
	
	$.colorbox.close();
		
	if (defined(levels) && levels !== false) {
		//The data variable should be a pointer to the right location in the thus.data object.
		//(This will either be the thus.data object or a subsection if the cells being re-ordered are
		// children of another cell.)
		if (levels) {
			levels = levels.split('-');
			foreach (levels as var l) {
				l *= 1;
				data = data.cells[1*levels[l]];
			}
		}
		
		if (type == 'slot') {
			data.cells.push({
				width: newWidth,
				slot: true,
				name: thus.uniqueRandomName(),
				flash: true
			});
			toastr.success(phrase.growlSlotAdded);
		
		} else if (type == 'space') {
			data.cells.push({
				width: newWidth,
				space: true,
				css_class: thus.randomName(2, 'Space_'),
				flash: true
			});
			toastr.success(phrase.growlSpaceAdded);
		
		} else if (type == 'grouping') {
			data.cells.push({
				width: newWidth,
				css_class: thus.randomName(2, 'Grouping_'),
				flash: true,
				cells: [
					{width: newWidth, slot: true, name: thus.uniqueRandomName()},
					{width: newWidth, slot: true, name: thus.uniqueRandomName()}
				]
			});
			toastr.success(phrase.growlChildrenAdded);
		
		} else if (type == 'grid_break') {
			data.cells.push({
				width: data.cols,
				grid_break: true,
				grid_css_class: thus.randomName(2, 'Grid_'),
				flash: true
			});
			toastr.success(phrase.growlGridBreakAdded);
		
		} else if (type == 'grid_break_with_slot') {
			data.cells.push({
				width: data.cols,
				grid_break: true,
				slot: true,
				grid_css_class: thus.randomName(2, 'Grid_'),
				name: thus.uniqueRandomName(),
				flash: true
			});
			toastr.success(phrase.growlSlotAdded);
		}
		
		thus.change();
	}
};

methods.getLevels = function($el) {
	var levels = $el.data('levels');
	
	if (defined(levels)) {
		return '' + levels;
	} else {
		return '';
	}
};

//Rename a slot
methods.saveProperties = function(el, params) {
	//Try to get the cell that the name was for
	var cell;
	if ((cell = $(el).data('for'))
	 && (cell = $('#' + cell))) {
		var i = cell.data('i'),
			levels = thus.getLevels(cell),
			data = thus.data;
		
		//The data variable should be a pointer to the right location in the thus.data object.
		//(This will either be the thus.data object or a subsection if the cells being re-ordered are
		// children of another cell.)
		if (levels) {
			levels = levels.split('-');
			foreach (levels as var l) {
				l *= 1;
				data = data.cells[1*levels[l]];
			}
		}
		
		if (data.cells[i].slot) {
			if (!params.name) {
				$('#zenario_grid_error').html(phrase.gridErrorNameIncomplete).slideDown();
				return;
		
			} else if (params.name != params.name.replace(/[^a-zA-Z0-9_]/g, '')) {
				$('#zenario_grid_error').html(phrase.gridErrorNameFormat).slideDown();
				return;
		
			} else if (params.name != data.cells[i].name && thus.checkIfNameUsed(params.name)) {
				$('#zenario_grid_error').html(phrase.gridErrorNameInUse).slideDown();
				return;
			}
			
			//Rename the slot
			delete thus.names[data.cells[i].name];
			thus.registerNewName(params.name);
		}
		
		foreach (params as var j) {
			if (params[j] != '') {
				data.cells[i][j] = params[j];
			} else {
				delete data.cells[i][j];
			}
		}

		thus.change();
	}
	
	$.colorbox.close();
};

//Generate a random slot name
methods.randomName = function(length, prefix) {
	var aToZ = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
		text = prefix || 'Slot_';
	
	for (var i = 0; i < length; ++i) {
		text += aToZ.charAt(Math.floor(Math.random() * 26));
	}
	
	return text;
};

//Keep calling the thus.randomName() function until we get an unused one
	//This would be very inefficient were I using the complete space of random names as the space filled up,
	//but as soon as we've had log-10 names I switch up to using another digit, so the space is always a lot less than half full.
methods.uniqueRandomName = function() {
	var name,
		length = Math.ceil(Math.log(Math.max(thus.randomNameCount, 2)) / Math.log(10));
	
	do {
		name = thus.randomName(length);
	} while (thus.checkIfNameUsed(name));
	
	thus.names[name] = true;
	++thus.randomNameCount;
	return name;
};

methods.checkIfNameUsed = function(name) {
	return defined(thus.names[name.toLowerCase()]);
};

methods.registerNewName = function(name) {
	return thus.names[name.toLowerCase()] = name;
};

//Redraw the editor
methods.change = function(historic, doNotRedrawForm) {
	
	//Call the onchange function if there is one for this editor
	//if (change) {
	//	change(thus.data);
	//}
	
	//Add this change to the history (unless this change was triggered by going through the history).
	if (!historic) {
		//If we have been previously navigating the history, forget any future changes that were undone
		if (thus.pos < thus.history.length - 1) {
			thus.history.splice(thus.pos + 1, thus.history.length - 1 - thus.pos);
		}
		
		//Add the current state to the history
		thus.history.push(thus.ajaxData());
		
		//Set our currently position to the current state in the history
		thus.pos = thus.history.length - 1;
		
		//Check if we've just destoryed a point in the undo history where we saved.
		//If so, clear the pointer that recorded that we'd saved there.
		if (thus.savedAtPos >= thus.pos) {
			thus.savedAtPos = -1;
		}
	}
	
	thus.draw(doNotRedrawForm);
};

//Go backwards or forwards in the history by n steps
methods.undoOrRedo = function(n) {
	var pos = thus.pos - n;
	
	if (pos < 0 || pos >= thus.history.length) {
		return;
	}
	
	thus.pos = pos;
	thus.data = JSON.parse(thus.history[pos]);
	thus.change(true, n > 1);
};

methods.undo = function() {
	thus.undoing = true;
	thus.undoOrRedo(1);
	delete thus.undoing;
};

methods.redo = function() {
	thus.undoOrRedo(-1);
};

methods.revert = function() {
	thus.undoOrRedo(0);
};

methods.canUndo = function() {
	return thus.pos > 0;
};

methods.canRedo = function() {
	return thus.pos < thus.history.length - 1;
};






methods.microTemplate = function(template, data, filter) {
	
	var html,
		needsTidying;
	
	needsTidying = zenario.addLibPointers(data, thus);
	
		html = zenarioT.microTemplate(template, data, filter);
	
	if (needsTidying) {
		zenario.tidyLibPointers(data);
	}
	
	return html;
};








	});
},
	window.zenarioG = zenario.createZenarioLibrary('G', undefined, function() {
		var thus = this;
		thus.initVars();
	})
);