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
	zenarioGM
) {
	"use strict";
	
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
	


var formId = 'settings',
	linksId = 'download_links',
	closeButtonId = 'close_button';



zenarioGM.editing = false;
zenarioGM.data = {};
zenarioGM.layoutName = '';
zenarioGM.openedSkinId = '';
zenarioGM.history = [];
zenarioGM.pos = 0;
zenarioGM.savedAtPos = 0;
zenarioGM.lastIframeHeight = 360;
zenarioGM.bodyPadding = 0;
zenarioGM.desktopSmallestSize = 800;
zenarioGM.previewPaddingLeftRight = 75;
zenarioGM.gridAreaSmallestHeight = 150;

zenarioGM.tabletWidth = 768;
zenarioGM.mobileWidth = 320;

zenarioGM.lastToast = false;

zenarioGM.gridId = 'grid';
zenarioGM.mtPrefix = 'zenario_grid_maker_';
zenarioGM.addToolbarId = 'grid_add_toolbar';
















zenarioGM.checkDataNonZero = function(warning, data, prop, defaultValue, min) {
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

zenarioGM.checkDataNumeric = function(warning, data, prop, defaultValue, precision) {
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
zenarioGM.checkDataNonZeroAndNumeric = function(data, warning) {
	
	var warnFix = false,
		warnFlu = false;
	
	warning =	zenarioGM.checkDataNonZero(warning, data, 'cols', 10, 0);
	warnFix =	zenarioGM.checkDataNonZero(warnFix, data, 'colWidth', 60, 1);
	warnFlu =	zenarioGM.checkDataNonZero(warnFlu, data, 'minWidth', 600, 100);
	warning =	zenarioGM.checkDataNonZero(warning, data, 'maxWidth', 960, 100);
	warnFlu =	zenarioGM.checkDataNumeric(warning, data, 'bp1', 0, 1);
	warnFlu =	zenarioGM.checkDataNumeric(warning, data, 'bp2', 0, 1);
	warnFix =	zenarioGM.checkDataNumeric(warnFix, data, 'gutter', 40);
	warnFix =	zenarioGM.checkDataNumeric(warnFix, data, 'gutterLeftEdge', 0);
	warnFix =	zenarioGM.checkDataNumeric(warnFix, data, 'gutterRightEdge', 0);
	warnFlu =	zenarioGM.checkDataNumeric(warning, data, 'gutterFlu', 1, -10);
	warnFlu =	zenarioGM.checkDataNumeric(warning, data, 'gutterLeftEdgeFlu', 0, -10);
	warnFlu =	zenarioGM.checkDataNumeric(warning, data, 'gutterRightEdgeFlu', 0, -10);
	
	var totalWidthUsedForGutters = data.gutterFlu * (data.cols - 1) + data.gutterLeftEdgeFlu + data.gutterRightEdgeFlu,
		widthLeftForColumns = (100 - totalWidthUsedForGutters) / data.cols;
	
	//Enforce that the columns must be wider than the gutters
	if (widthLeftForColumns <= data.gutterFlu) {
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

zenarioGM.checkDataR = function(cells) {
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
					grid_css_class: zenarioGM.randomName(2, 'Gridbreak_'),
					html: cells[j].after
				});
				delete cells[j].after;
			}
			
			cells[j].grid_break = true;
			cells[j].grid_css_class = cells[j].name || zenarioGM.randomName(2, 'Gridbreak_');
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
			zenarioGM.checkDataR(cells[i].cells);
		
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




zenarioGM.rememberNames = function(cells) {
	for (var i = 0; i < cells.length; ++i) {
		//Groupings
		if (cells[i].cells) {
			//Recursively check anything inside a grouping
			zenarioGM.rememberNames(cells[i].cells);
		
		} else if (cells[i].name) {
			//When loading data, remember what the original name on the slots were
			cells[i].oName = cells[i].name;
		}
	}
};

zenarioGM.checkData = function(layoutName, familyName) {
	var scale,
		cols,
		i, j, k;
	
	if (!zenarioGM.data) {
		zenarioGM.data = {cells: []};
	
	//Attempt to catch a case where data in an old format is loaded
	} else if (_.isArray(zenarioGM.data) ) {
		zenarioGM.data = {cells: zenarioGM.data};
		
		if (familyName && (cols = 1*familyName.replace(/\D/g, ''))) {
			zenarioGM.data.cols = cols;
		}
	
	} else if (!zenarioGM.data.cells) {
		zenarioGM.data.cells = [];
	}
	
	//If switching from a fixed grid to a flexi grid, try to migrate the existing settings to populate the new settings
	if (zenarioGM.data.fluid
	 && !defined(zenarioGM.data.gutterFlu)
	 && defined(zenarioGM.data.gutter)) {
		zenarioGM.checkDataNonZeroAndNumeric(zenarioGM.data);
		
		scale = zenarioGM.data.gutterLeftEdge + zenarioGM.data.cols * zenarioGM.data.colWidth + (zenarioGM.data.cols - 1) * zenarioGM.data.gutter + zenarioGM.data.gutterRightEdge;
		
		if (scale > 960) {
			zenarioGM.data.minWidth = 960;
		
		} else if (scale > 760) {
			zenarioGM.data.minWidth = 760;
		
		} else {
			zenarioGM.data.minWidth = scale;
		}
		zenarioGM.data.maxWidth = scale;
		
		zenarioGM.data.gutterFlu = Math.round(zenarioGM.data.gutter / scale * 1000) / 10;
		zenarioGM.data.gutterLeftEdgeFlu = Math.round(zenarioGM.data.gutterLeftEdge / scale * 1000) / 10;
		zenarioGM.data.gutterRightEdgeFlu = Math.round(zenarioGM.data.gutterRightEdge / scale * 1000) / 10;
		
	//If switching from a flexi grid to a fixed grid, try to migrate the existing settings to populate the new settings
	} else
	if (!zenarioGM.data.fluid
	 && !defined(zenarioGM.data.gutter)
	 && defined(zenarioGM.data.gutterFlu)) {
		zenarioGM.checkDataNonZeroAndNumeric(zenarioGM.data);
		
		scale = zenarioGM.data.maxWidth;
		zenarioGM.data.gutter = Math.round(zenarioGM.data.gutterFlu * scale / 100);
		zenarioGM.data.gutterLeftEdge = Math.round(zenarioGM.data.gutterLeftEdgeFlu * scale / 100);
		zenarioGM.data.gutterRightEdge = Math.round(zenarioGM.data.gutterRightEdgeFlu * scale / 100);
		
		var selected,
			gutTotal = zenarioGM.data.gutterLeftEdge + (zenarioGM.data.cols - 1) * zenarioGM.data.gutter + zenarioGM.data.gutterRightEdge;
		
		zenarioGM.data.colWidth = (scale - gutTotal) / zenarioGM.data.cols;
		
		//Because percentage based numbers won't convert perfectly to pixels, we might be off of the total size slightly.
		//Attempt to correct this by changing the col-width/gutter to the next best values.
		if (selected = zenarioGM.recalcColumnAndGutterOptions(zenarioGM.data, true, scale)) {
			zenarioGM.data.colWidth = selected.colWidth;
			zenarioGM.data.gutter = selected.gutter;
		} else {
			delete zenarioGM.data.colWidth;
			delete zenarioGM.data.gutter;
		}
	}
	
	zenarioGM.checkDataNonZeroAndNumeric(zenarioGM.data);
	
	if (zenarioGM.data.fluid) {
		delete zenarioGM.data.gutter;
		delete zenarioGM.data.gutterLeftEdge;
		delete zenarioGM.data.gutterRightEdge;
		delete zenarioGM.data.colWidth;
	} else {
		delete zenarioGM.data.gutterFlu;
		delete zenarioGM.data.gutterLeftEdgeFlu;
		delete zenarioGM.data.gutterRightEdgeFlu;
	}
	
	//if (change) {
	//	change(zenarioGM.data);
	//}
	
	if (!zenarioGM.history.length) {
		zenarioGM.pos = 0;
		zenarioGM.history = [zenarioGM.ajaxData()];
	}
};

zenarioGM.tooltips = function(sel) {
    return zenario.tooltips(sel);
};

zenarioGM.checkCellsEmpty = function(data) {
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
zenarioGM.deleteCell = function(el) {
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
		levels = zenarioGM.getLevels(cell);
		data = zenarioGM.data;
		
		//The data variable should be a pointer to the right location in the zenarioGM.data object.
		//(This will either be the zenarioGM.data object or a subsection if the cells being re-ordered are
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
			zenarioGM.change();
			toastr.success(phrase.growlSlotDeleted);
		};
		
		//If this slot was never saved, allow it to be instantly deleted.
		//If this slot has been previously saved, show a prompt with some info first
		if (slot.oName
		 && zenarioGM.layoutId
		 && zenarioGM.confirmDeleteSlot) {
			zenarioGM.confirmDeleteSlot(slot, doDelete);
		} else {
			doDelete();
		}
	}
};

//Add something (using the popup form)
//Not currently used
//zenarioGM.add = function(el, type, respClass) {
//	//Try to get the level that the add button was for
//	var $el = $(el),
//		levels = zenarioGM.getLevels($el),
//		newWidth = $el.attr('data-new-width') || 1,
//		data = zenarioGM.data;
//	
//	$.colorbox.close();
//		
//	if (defined(levels) && levels !== false) {
//		//The data variable should be a pointer to the right location in the zenarioGM.data object.
//		//(This will either be the zenarioGM.data object or a subsection if the cells being re-ordered are
//		// children of another cell.)
//		if (levels) {
//			levels = levels.split('-');
//			foreach (levels as var l) {
//				l *= 1;
//				data = data.cells[1*levels[l]];
//			}
//		}
//		
//		if (type == 'slot') {
//			data.cells.push({
//				width: newWidth,
//				slot: true,
//				name: zenarioGM.uniqueRandomName(),
//				flash: true
//			});
//			toastr.success(phrase.growlSlotAdded);
//		
//		} else if (type == 'space') {
//			data.cells.push({
//				width: newWidth,
//				space: true,
//				css_class: zenarioGM.randomName(2, 'Space_'),
//				flash: true
//			});
//			toastr.success(phrase.growlSpaceAdded);
//		
//		} else if (type == 'grouping') {
//			data.cells.push({
//				width: newWidth,
//				css_class: zenarioGM.randomName(2, 'Grouping_'),
//				flash: true,
//				cells: [
//					{width: newWidth, slot: true, name: zenarioGM.uniqueRandomName()},
//					{width: newWidth, slot: true, name: zenarioGM.uniqueRandomName()}
//				]
//			});
//			toastr.success(phrase.growlChildrenAdded);
//		
//		} else if (type == 'grid_break') {
//			data.cells.push({
//				width: data.cols,
//				grid_break: true,
//				grid_css_class: zenarioGM.randomName(2, 'Grid_'),
//				flash: true
//			});
//			toastr.success(phrase.growlGridBreakAdded);
//		
//		} else if (type == 'grid_break_with_slot') {
//			data.cells.push({
//				width: data.cols,
//				grid_break: true,
//				slot: true,
//				grid_css_class: zenarioGM.randomName(2, 'Grid_'),
//				name: zenarioGM.uniqueRandomName(),
//				flash: true
//			});
//			toastr.success(phrase.growlSlotAdded);
//		}
//		
//		zenarioGM.change();
//	}
//};

zenarioGM.getLevels = function($el) {
	var levels = $el.data('levels');
	
	if (defined(levels)) {
		return '' + levels;
	} else {
		return '';
	}
};

//Generate a random slot name
zenarioGM.randomName = function(length, prefix) {
	var aToZ = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
		text = prefix || 'Slot_';
	
	for (var i = 0; i < length; ++i) {
		text += aToZ.charAt(Math.floor(Math.random() * 26));
	}
	
	return text;
};

//Keep calling the zenarioGM.randomName() function until we get an unused one
	//This would be very inefficient were I using the complete space of random names as the space filled up,
	//but as soon as we've had log-10 names I switch up to using another digit, so the space is always a lot less than half full.
zenarioGM.uniqueRandomName = function() {
	var name,
		length = Math.ceil(Math.log(Math.max(zenarioGM.randomNameCount, 2)) / Math.log(10));
	
	do {
		name = zenarioGM.randomName(length);
	} while (zenarioGM.checkIfNameUsed(name));
	
	zenarioGM.names[name] = true;
	++zenarioGM.randomNameCount;
	return name;
};

zenarioGM.checkIfNameUsed = function(name) {
	return defined(zenarioGM.names[name.toLowerCase()]);
};

zenarioGM.registerNewName = function(name) {
	return zenarioGM.names[name.toLowerCase()] = name;
};

//Redraw the editor
zenarioGM.change = function(historic, doNotRedrawForm) {
	
	//Call the onchange function if there is one for this editor
	//if (change) {
	//	change(zenarioGM.data);
	//}
	
	//Add this change to the history (unless this change was triggered by going through the history).
	if (!historic) {
		//If we have been previously navigating the history, forget any future changes that were undone
		if (zenarioGM.pos < zenarioGM.history.length - 1) {
			zenarioGM.history.splice(zenarioGM.pos + 1, zenarioGM.history.length - 1 - zenarioGM.pos);
		}
		
		//Add the current state to the history
		zenarioGM.history.push(zenarioGM.ajaxData());
		
		//Set our currently position to the current state in the history
		zenarioGM.pos = zenarioGM.history.length - 1;
		
		//Check if we've just destoryed a point in the undo history where we saved.
		//If so, clear the pointer that recorded that we'd saved there.
		if (zenarioGM.savedAtPos >= zenarioGM.pos) {
			zenarioGM.savedAtPos = -1;
		}
	}
	
	zenarioGM.draw(doNotRedrawForm);
};

//Go backwards or forwards in the history by n steps
zenarioGM.undoOrRedo = function(n) {
	var pos = zenarioGM.pos - n;
	
	if (pos < 0 || pos >= zenarioGM.history.length) {
		return;
	}
	
	zenarioGM.pos = pos;
	zenarioGM.data = JSON.parse(zenarioGM.history[pos]);
	zenarioGM.change(true, n > 1);
};

zenarioGM.undo = function() {
	zenarioGM.undoing = true;
	zenarioGM.undoOrRedo(1);
	delete zenarioGM.undoing;
};

zenarioGM.redo = function() {
	zenarioGM.undoOrRedo(-1);
};

zenarioGM.revert = function() {
	zenarioGM.undoOrRedo(0);
};

zenarioGM.canUndo = function() {
	return zenarioGM.pos > 0;
};

zenarioGM.canRedo = function() {
	return zenarioGM.pos < zenarioGM.history.length - 1;
};






zenarioGM.microTemplate = function(template, data, filter) {
	
	var html,
		needsTidying;
	
	needsTidying = zenario.addLibPointers(data, zenarioGM);
	
		html = zenarioT.microTemplate(template, data, filter);
	
	if (needsTidying) {
		zenario.tidyLibPointers(data);
	}
	
	return html;
};





zenarioGM.cellLabel = function(cell) {
	return cell.name;
};




zenarioGM.init = function(data, layoutId, layoutName, slotContents) {
	if (typeof data == 'object') {
		zenarioGM.data = data;
	} else {
		zenarioGM.data = JSON.parse(data);
	}
	
	if (layoutName) {
		zenarioGM.layoutName = layoutName;
	}
	if (layoutId) {
		zenarioGM.layoutId = layoutId;
	}
	
	zenarioGM.slotContents = slotContents || {};
	
	zenarioGM.checkData(layoutName);
	zenarioGM.checkDataR(zenarioGM.data.cells);
	zenarioGM.rememberNames(zenarioGM.data.cells);
	
	
	zenarioGM.editing = true; //added to make the Slots tab selected on first load--JS
	
	//If nothing has been created yet, start on the editor
	if (!zenarioGM.data.cells.length) {
		zenarioGM.editing = true;
	}

	//Set up the close button, with a confirm if there are unsaved changes
	if (closeButtonId) {
		if (windowParent
		 && !windowParent.zenarioGM
		 && windowParent.$
		 && windowParent.$.colorbox) {
		
			$('#' + closeButtonId).click(function() {
				if (zenarioGM.savedAtPos == zenarioGM.pos
				 || confirm(phrase.gridConfirmClose)) {
					
					zenario.stopPoking(zenarioGM);
					
					if (windowParent.zenario
					 && windowParent.zenario.cID) {
						//If it looks like this is a window opened from the front end, reload the window
						windowParent.location.reload(true);
					} else {
						windowParent.$.colorbox.close();
					}
				}
			});
		} else {
			get(closeButtonId).style.display = 'none';
		}
	}
	
	zenario.startPoking(zenarioGM);
};

zenarioGM.scaleWidth = function(width) {
	return 0.5*width;
};

zenarioGM.draw = function(doNotRedrawForm) {
	if (!doNotRedrawForm) {
		zenarioGM.drawOptions();
	}

	if (zenarioGM.editing) {
		zenarioGM.drawEditor();
	} else {
		zenarioGM.drawPreview();
	}
};

var tlGutterWidthPercent, tlColWidthPercent;

//Draw/redraw the boxes with controls to add, move and resize them
zenarioGM.drawEditor = function(
	thisContId, thisCellId, 
	levels,
	gCols, gColWidth, gGutter, gGutterLeftEdge, gGutterRightEdge,
	gGutterNested,
	gColWidthPercent, gGutterWidthPercent
) {
	var data,
		level = 0,
		gridScrollTop = 0;
	
	if (!levels) {
		$('#' + formId + '--tabs').removeClass('zenario_grid_tabs_grid_selected').addClass('zenario_grid_tabs_slots_selected');
	}
	
	//zenarioGM.data should contain all of the information on the boxes
	//You can have boxes within boxes, so this can be recursive
	//When we're drawing recursively, levels will contain an array of indices
	//that make up the current recursion path.
	if (levels && defined(levels[0])) {
		data = zenarioGM.data;
		foreach (levels as var l) {
			l *= 1;
			level = l+1;
			data = data.cells[levels[l]];
		}
	} else {
		gridScrollTop = $('#' + zenarioGM.gridId).scrollTop();
		zenarioGM.checkData();
		data = zenarioGM.data;
		level = 0;
		levels = [];
		zenarioGM.names = {};
		zenarioGM.randomNameCount = 0;
	}
	
	if (!defined(thisContId)) {
		thisContId = zenarioGM.gridId;
	}
	if (!defined(gCols)) {
		gCols = zenarioGM.data.cols;
	}
	if (!defined(gColWidth)) {
		if (zenarioGM.data.fluid) {
			//The UI only offers a fixed size, not a flexi size.
			
			//Remember some details on what the actual values should be
			gGutterWidthPercent = tlGutterWidthPercent = zenarioGM.data.gutterFlu;
			gColWidthPercent = tlColWidthPercent = (100 - zenarioGM.data.gutterLeftEdgeFlu - (zenarioGM.data.cols - 1) * zenarioGM.data.gutterFlu - zenarioGM.data.gutterRightEdgeFlu) / zenarioGM.data.cols;
			
			//Then convert fluid designs into fixed designs using 10px = 1%
			gGutter = Math.round(zenarioGM.data.gutterFlu * zenarioGM.data.maxWidth / 100);
			gGutterLeftEdge = Math.round(zenarioGM.data.gutterLeftEdgeFlu * zenarioGM.data.maxWidth / 100);
			gGutterRightEdge = Math.round(zenarioGM.data.gutterRightEdgeFlu * zenarioGM.data.maxWidth / 100);
			gGutterNested = gGutter;
			gColWidth = Math.round(gColWidthPercent * zenarioGM.data.maxWidth / 100);
		
		} else {
			gGutterWidthPercent =
			gColWidthPercent = 0;
			gGutter = zenarioGM.data.gutter;
			gGutterLeftEdge = zenarioGM.data.gutterLeftEdge;
			gGutterRightEdge = zenarioGM.data.gutterRightEdge;
			gGutterNested = gGutter;
			gColWidth = zenarioGM.data.colWidth;
		}
	}
	
	
	var html = '',
		eyesThisLine = {},
		widthSoFarThisLine,
		largestWidth = 1,
		cells = data.cells,
		elId = zenarioGM.gridId + '__cell' + levels.join('-'),
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
	
	var calcWidthPercent = function(widthInCols) {
			return widthInCols * tlColWidthPercent + (widthInCols - 1) * tlGutterWidthPercent;
		},
		drawWidthPercent = function(widthPercent) {
			return zenario.round(widthPercent, 1) + '%';
		};
	
	
	
	//Each level should have a <ul> tag to contain it.
	//All a little bit of padding (which will be hidden with an "overflow-x: hidden;") to help prevent various bugs
	//of things wrapping onto new lines when dragging things around.
	if (level == 0) {
		html += '<div class="zenario_overflow_wrap">';
		html += '<ul id="' + elId + 's" class="zenario_grids" style="width: ' + zenarioGM.scaleWidth(wrapWidth + Math.abs(gGutterRight - gGutterRightEdge) + 1) + 'px;';
	} else {
		html += '<ul id="' + elId + 's" class="zenario_grids" style="width: ' + zenarioGM.scaleWidth(wrapWidth) + 'px;';
	}
	
	if (wrapPaddingLeft) {
		html += 'padding-left: ' + zenarioGM.scaleWidth(wrapPaddingLeft) + 'px;';
	}
	if (wrapPaddingRight) {
		html += 'padding-right: ' + zenarioGM.scaleWidth(wrapPaddingRight) + 'px;';
	}
	
	//If this is the outer-most tag, add a pink striped background so we can easily see the grid
	if (level == 0) {
		html += 'background-image: url(' + htmlspecialchars(URLBasePath) + 'zenario/admin/grid_maker/grid_bg.php?gColWidth=' + gColWidth + '&gCols=' + gCols + '&gGutter=' + gGutter + '&gGutterLeftEdge=' + gGutterLeftEdge + '&gGutterRightEdge=' + gGutterRightEdge + ');';
		
		//Remember the width and height for typical new elements.
		//This will be used later when trying to drag them in from the "add" toolbar
		zenarioGM.gGutterLeftEdge = gGutterLeftEdge;
		zenarioGM.gGutterLeft = gGutterLeft;
		zenarioGM.gGutterRight = gGutterRight;
		zenarioGM.gGutterRightEdge = gGutterRightEdge;
		zenarioGM.gColAndGutterWidth = gColAndGutterWidth;
		zenarioGM.typAddSlot = 2 * gColAndGutterWidth - gGutter;
		zenarioGM.typAddBreak = gCols * gColAndGutterWidth - gGutter;
	}
		
	html += '">';
	
	if (level > 0) {
	
	    
		html +=
			_$div('class', 'zenario_grid_object_properties',
				_$div(
					_$span(
						'class', 'zenario_grid_object_edit_properties',
						'title', phrase.gridEditProperties,
						'data-for', thisCellId,
						'data-type', 'grouping',
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
		 && zenarioGM.checkCellsEmpty(cells[i])) {
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
		
		if (zenarioGM.data.responsive) {
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
		
		cells[i].widthPercent = calcWidthPercent(cells[i].width);
		
		
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
		
		if (cells[i].flash && !zenarioGM.undoing) {
			//zenarioGM.newlyAdded = elId + '-' + i;
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
		
		html += ' style="width: ' + zenarioGM.scaleWidth(cells[i].widthPx) + 'px;">';
		
		
		
		
		
		if (cells[i].grid_break) {
			html += _$div('class', 'zenario_grid_break_outer_wrap', '>');
		}
		
		var marginStyle =
				'margin-left: ' + zenarioGM.scaleWidth(cells[i].marginLeft) + 'px; ' +
				'margin-right: ' + zenarioGM.scaleWidth(cells[i].marginRight) + 'px;';
		
		if (cells[i].cells) {
			//For cells that have children
			//Don't add any contents to this div as any contents will be replaced with the children
			html += _$div('id', elId + '-' + i + '-span', 'class', 'zenario_grid_wrap');
		
		} else if (!cells[i].slot) {
			//Empty space
			html += _$div(
				'class', 'zenario_cell_in_grid zenario_grid_gutter zenario_no_slot ' + (cells[i].grid_break? 'zenario_grid_break zenario_grid_break_no_slot' : 'zenario_grid_space'),
				'style', marginStyle,
				'title', cells[i].grid_break? phrase.gridGridBreak : phrase.gridEmptySpace,
			'>');
				
				html += _$div('class', 'zenario_grid_delete', 'title', phrase.gridDelete, 'data-for', elId + '-' + i, 'style', 'right: ' + zenarioGM.scaleWidth(cells[i].marginRight) + 'px;');
				html += _$div('&nbsp;');
				html += _$div('&nbsp;');

				html +=
					'<div class="zenario_grid_object_properties"><div><span' +
						' title="' + phrase.gridEditProperties + '"' +
						' class="zenario_grid_object_edit_properties"' +
						' data-for="' + elId + '-' + i + '"' +
						' data-type="' + (cells[i].grid_break? 'grid_break' : 'space') + '"';
				
				var displayName;
				
				if (cells[i].grid_break) {
					html += ' data-grid_css_class="' + htmlspecialchars(displayName = cells[i].grid_css_class) + '"';
				} else {
					html += ' data-css_class="' + htmlspecialchars(displayName = cells[i].css_class) + '"';
				}
				html +=
					'>' +
						(displayName?
							htmlspecialchars(displayName)
						 :	phrase.gridEditPlaceholder) +
					'</span></div></div>';
			html += '</div>';
		
		} else {
			if (!cells[i].name) {
				cells[i].name = zenarioGM.uniqueRandomName();
			
			} else if (!zenarioGM.checkIfNameUsed(cells[i].name)) {
				zenarioGM.registerNewName(cells[i].name);
				++zenarioGM.randomNameCount;
			}
			
			
			var nHTML = htmlspecialchars(zenarioGM.cellLabel(cells[i]));
			
			if (cells[i].height && cells[i].height != 'small') {
				nHTML += ' (' + htmlspecialchars(cells[i].height) + ')';
			}
			
			
			//Draw the current size for cells that should have a slot
			html += _$div('class', 'zenario_grid_border zenario_cell_in_grid zenario_slot zenario_grid_gutter ' + (cells[i].grid_break? 'zenario_grid_break zenario_grid_break_with_slot' : ''), 'style', marginStyle, '>');
				
				html += _$div('class', 'zenario_grid_name_area', 
					_$div('class', 'zenario_grid_delete', 'title', phrase.gridDelete, 'data-for', elId + '-' + i, 'style', 'right: ' + zenarioGM.scaleWidth(cells[i].marginRight) + 'px;') + 
					_$div('class', 'zenario_grid_faux_block_t') +
					_$div('class', 'zenario_grid_object_properties',
						_$div(
							_$span(
								'class', 'zenario_grid_slot_name zenario_grid_object_edit_properties',
								'title', phrase.gridEditProperties,
								'data-for', elId + '-' + i,
								'data-type', cells[i].grid_break? 'grid_break_with_slot' : 'slot',
								nHTML
							)
							+
							_$span('class', 'zenario_grid_object_properties_wrap',
								_$span('class', 'zenario_grid_slot_contents',
									zenarioGM.getSlotDescription(zenarioGM.slotContents[cells[i].name])
								)
							)
						)
					)
				);
				
				html += _$div('class', 'zenario_grid_faux_block_r');
				html += _$div('class', 'zenario_grid_cell_size', '>');
				
				if (!cells[i].grid_break) {
					if (zenarioGM.data.fluid) {
						html += drawWidthPercent(cells[i].widthPercent);
					} else {
						var size = cells[i].width * gColAndGutterWidth - gGutter;
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
					zenarioGM.drawEditor(
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
				displayedWidth,
				width,
				height,
				left,
				right,
				$neighbor;
			
			//Stop a bug where <div>s with "overflow-x: hidden;" set could be scrolled to reveal the "hidden" areas.
			$('.zenario_overflow_wrap').scrollLeft(0);
			
			if (!defined(add)) {
				displayedWidth = 1*ui.item.data('displayed_width');
				//height = ui.item.height();
				height = 70;
				//left = 1*ui.item.data('displayed_margin_left');
				//right = width - left - displayedWidth;
			
				$target
					.height(height)
					.width(zenarioGM.scaleWidth(displayedWidth));
					//height -= top + bottom;
					//.html(_$div('style', 'margin: ' + top + 'px 0 ' + bottom + 'px 0; height: ' + height + 'px;'));
			
			} else {
				height = 70;
				switch (add) {
					case 'grid_break':
					case 'grid_break_with_slot':
						width = zenarioGM.scaleWidth(zenarioGM.typAddBreak);
						break;
					case 'grouping':
						//height = 200;
					default:
						width = zenarioGM.scaleWidth(zenarioGM.typAddSlot);
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
					.width(width);
			}
			
			
			//$target
			//	.css('margin-left', 0)
			//	.css('margin-right', 0);
			
			//Check if there's another element to the right of this one (excluding the thing being dragged)
			var $next = $target.next();
			
			if ($next.hasClass('ui-draggable')) {
				$next = false;
			}
			
			//Check if this is on the start of the line
			var isLeftmost = $target.offset().left < zenarioGM.gColAndGutterWidth,
			
				//Check if this is on the end of the line
				isRightmost = !$next || !$next.offset() || $next.offset().left < $target.offset().left,
			
				nextWasAtLeftEdge = !!($next && 1*$next.data('is_alpha')),
			
				nextWasAtRightEdge = !!($next && 1*$next.data('at_right_edge'));
			
			
			
			//Try to work out what the margins should be for the newly added slot.
			right = zenarioGM.gGutterRight;
			
			//Check if this is the first slot on the line
			if (isLeftmost) {
				//If so, use the left edge gutter.
				left = zenarioGM.gGutterLeftEdge;
			} else {
				left = zenarioGM.gGutterLeft;
			}
			
			if (isRightmost) {
				right = zenarioGM.gGutterRightEdge;
			} else {
				right = zenarioGM.gGutterRight;
			}
			
			//Note that the slot we're shifting right will also have used the left edge gutter,
			//we'll need to adjust our right margin to accommodate for the difference between
			//the left edge gutter and the normal left edge.
			if (nextWasAtLeftEdge) {
				right += zenarioGM.gGutterLeft - zenarioGM.gGutterLeftEdge;
			}
			
			
					
			
			
			$target
				.html(_$div('style', 'margin-left: ' + zenarioGM.scaleWidth(left) + 'px; margin-right: ' + zenarioGM.scaleWidth(right) + 'px;'));
			
			//$target
			//	.css('margin-left', zenarioGM.scaleWidth(left))
			//	.css('margin-right', zenarioGM.scaleWidth(right));
		},
		
		//Only allow sorting on grid elements, and not the add-controls
		items: 'li.zenario_grid_cell__' + elId,
		
		//Allow slots to be dragged into/out of groupings
		connectWith: '.zenario_grids',
		
		//Function to handle a reordering
		start: function(event, ui) {
		   
			//Stop the rename event firing at the same time
			zenarioGM.stopRenames = true;
		},
			
		stop: function(event, ui) {
		    
			//Get an array containing the new sort order
			var li, level,
				data = zenarioGM.data,
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
							name: zenarioGM.uniqueRandomName(),
							flash: true
						};
						msg = phrase.growlSlotAdded;
						break;
						
					case 'space':
						moved = {
							width: newWidth,
							space: true,
							css_class: zenarioGM.randomName(2, 'Space_'),
							flash: true
						};
						msg = phrase.growlSpaceAdded;
						break;
						
					case 'grouping':
						moved = {
							width: newWidth,
							css_class: zenarioGM.randomName(2, 'Grouping_'),
							flash: true,
							cells: [
								{width: newWidth, slot: true, name: zenarioGM.uniqueRandomName()},
								{width: newWidth, slot: true, name: zenarioGM.uniqueRandomName()}
							]
						};
						msg = phrase.growlChildrenAdded;
						break;
						
					case 'grid_break':
						
						//Only allow grid breaks at the top level
						if (dParent) {
							zenarioGM.change();
							return;
						}
						
						moved = {
							width: data.cols,
							grid_break: true,
							grid_css_class: zenarioGM.randomName(2, 'Gridbreak_'),
							flash: true
						};
						msg = phrase.growlGridBreakAdded;
						break;
						
					case 'grid_break_with_slot':
						
						//Only allow grid breaks at the top level
						if (dParent) {
							zenarioGM.change();
							return;
						}
						
						moved = {
							width: data.cols,
							grid_break: true,
							slot: true,
							grid_css_class: zenarioGM.randomName(2, 'Gridbreak_'),
							name: zenarioGM.uniqueRandomName(),
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
			
			zenarioGM.change();
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
				level = 1 * $el.data('level'),
				gutter = 1 * $el.data('gutter'),
				gridSize = Math.floor(zenarioGM.scaleWidth(gColAndGutterWidth)),
				widthScale = 1 / zenarioGM.scaleWidth(1),
				
				calcNewWidth = function(dragElWidth) {
					return Math.round((dragElWidth + gutter / 2) / gColAndGutterWidth * widthScale);
				};
			
			$el.resizable({
				cancel: 'a,button,input,select',
				handles: 'se',
				grid: gridSize,
				minHeight: $el.height(),
				maxHeight: $el.height(),
				
				//Here I actually want minWidth and maxWidth to be equal to minWidth and maxWidth,
				//but there is some sort of weird rounding error in jQuery 1.10, so I'm adding half
				//a column's padding either way. They should still round and snap to the right values.
				minWidth: Math.floor(zenarioGM.scaleWidth(minWidth - 0.5 * gColAndGutterWidth + 1)),
				maxWidth: Math.floor(zenarioGM.scaleWidth(maxWidth + 0.5 * gColAndGutterWidth - 1)),
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
						html = '',
						
						newWidthCols = calcNewWidth(hw),
						size = newWidthCols * gColAndGutterWidth - gutter;
					
					if (hw != ew) {
						if (!zenarioGM.data.fluid) {
							html += size + 'px';
						
						} else {
							html += drawWidthPercent(calcWidthPercent(newWidthCols));
						}
					}
					
					ui.helper.html(
						_$div('class', 'zenario_resizing_border', 'style', 'height: ' + (ui.helper.height() - 4) + 'px; width: ' + (zenarioGM.scaleWidth(size) - 4) + 'px; margin-left: ' + zenarioGM.scaleWidth(marginLeft) + 'px;',
							_$div('class', 'zenario_size_when_resizing', html)
						)
					);
				},
				
				//Function to update the data when we're finished resizing
				stop: function(event, ui) {
				   
						$(document.body).removeClass('zenario_grid_resizing_nest').removeClass('zenario_grid_resizing_cell').removeClass('zenario_grid_resizing_space_cell');
					
					var i = 1*ui.element.data('i'),
						levels = zenarioGM.getLevels(ui.element),
						data = zenarioGM.data;
					
					
					//The data variable should be a pointer to the right location in the zenarioGM.data object.
					//(This will either be the zenarioGM.data object or a subsection if the cells being re-ordered are
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
					    newWidthCols = resizedCell.width = calcNewWidth(ui.element.width()),
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
					zenarioGM.change();
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
				$el.css('right', prev.css('marginRight').replace(/[^\d\.]/g, '') * 1);
				
				//For cells, put the resize tool at the bottom of the visible section
					//To get this right, we'll need to take the height of the visible section,
					//then manually add in the upper margin, upper border, upper padding, lower padding and lower border,
					//then subtract the height of the resize button.
				$el.css('top', (prev.height() + 10 + 2 + 10 + 10 + 2 - 19) + 'px');
				$el.attr('title', phrase.gridResizeSlot);
			}
		});
		
		//Set the width of the overal content to the grid's full width, and add tooltips
		$('#' + thisContId).width(wrapWidth + wrapPaddingLeft + wrapPaddingRight).addClass('zenario_grid_wrapper');
		zenarioGM.tooltips('#' + thisContId);
		
		//Attach the delete function to the delete buttons
		$('#' + thisContId + ' .zenario_grid_delete').click(function() {
			zenarioGM.deleteCell(this); 
		});
		
		//Add another colorbox to the slot names, that allows you to rename a slot name by clicking on it
		$('#' + thisContId + ' .zenario_grid_object_edit_properties').click(function() {
		   
			if (!zenarioGM.stopRenames) {
				zenarioGM.editProperties(this);
			}
		});
		
		//Workaround for a bug where if you click and drag on a label,
		//you are then prompted to rename it immediate afterwards
		setTimeout(function() { delete zenarioGM.stopRenames; }, 100);
		
		zenarioGM.drawLinks();
		
		//Fade in any newly added objects
		$('#' + thisContId + ' .zenario_grid_newly_added .zenario_cell_in_grid').effect({effect: 'highlight', duration: 1000, easing: 'zenarioOmmitEnd'});
		
		////Scroll the page down slightly to show any newly adding objects
		//if (zenarioGM.newlyAdded) {
		//	gridScrollTop += $('#' + zenarioGM.newlyAdded).height();
		//	delete zenarioGM.newlyAdded;
		//}
		
		$('#' + zenarioGM.gridId).scrollTop(gridScrollTop);
		
		
		zenarioGM.drawAddToolbar();
	
	} else {
		return largestWidth;
	}
};


			
zenarioGM.getSlotDescription = function(slot) {
	
	var pluginDesc;
	
	//To do - add phrases here, don't have hard-coded english text
	if (slot) {
		
		pluginDesc = htmlspecialchars(slot.display_name);
		
		//N.b. similar logic to the following is also used inline in zenario/modules/zenario_pro_features/js/cache_info.js
		if (slot.instance_id) {
			pluginDesc += ', ';
			
			switch (slot.class_name) {
				case 'zenario_plugin_nest':
					pluginDesc += 'N';
					break;
				case 'zenario_slideshow':
				case 'zenario_slideshow_simple':
					pluginDesc += 'S';
					break;
				default:
					pluginDesc += 'P';
			}
			pluginDesc += ('' + slot.instance_id).padStart(2, '0');
		} else {
			pluginDesc += ' (version controlled)';
		}
		
	} else {
		pluginDesc = _$html('em', 'class', 'zenario_grid_empty_slot_contents', 'Empty');
	}
	
	return pluginDesc;
};

zenarioGM.drawOptions = function() {
	var m = {formId: formId},
		html = zenarioGM.microTemplate(zenarioGM.mtPrefix + 'top', m);
		
	
	
	$('.ui-tooltip').remove();
	get(formId).innerHTML = html;
	zenarioA.tooltips('#' + formId + '  *[title]');
	
	if (!zenarioGM.data.fluid) {
		zenarioGM.recalcColumnAndGutterOptions(zenarioGM.data);
	}
	
	$('#' + formId + ' input.zenario_grid_setting_break_1').change(zenarioGM.update).spinner({stop: zenarioGM.update, min: 10, step: 10}).after('<span class="zenario_grid_unit">px</span>');
	$('#' + formId + ' input.zenario_grid_setting_break_2').change(zenarioGM.update).spinner({stop: zenarioGM.update, min: 300, step: 10}).after('<span class="zenario_grid_unit">px</span>');
	
	//Refresh the previews every time a field is changed
	$('#' + formId + ' input.zenario_grid_setting_min_width').change(zenarioGM.recalcOnChange).spinner({stop: zenarioGM.recalcOnInc, min: 100, step: 10}).after('<span class="zenario_grid_unit">px</span>');
	$('#' + formId + ' input.zenario_grid_setting_max_width').change(zenarioGM.recalcOnChange).spinner({stop: zenarioGM.recalcOnInc, min: 320, step: 10}).after('<span class="zenario_grid_unit">px</span>');
	$('#' + formId + ' input.zenario_grid_setting_gutter_flu').change(zenarioGM.recalcOnChange).spinner({stop: zenarioGM.recalcOnInc, min: 0, step: 0.1}).after('<span class="zenario_grid_unit">%</span>');
	$('#' + formId + ' input.zenario_grid_setting_gutter_left_flu').change(zenarioGM.recalcOnChange).spinner({stop: zenarioGM.recalcOnInc, min: 0, step: 0.1}).after('<span class="zenario_grid_unit">%</span>');
	$('#' + formId + ' input.zenario_grid_setting_gutter_right_flu').change(zenarioGM.recalcOnChange).spinner({stop: zenarioGM.recalcOnInc, min: 0, step: 0.1}).after('<span class="zenario_grid_unit">%</span>');
	
	//For fixed grids, also recalculate the numbers every time a field is changed
	$('#' + formId + ' input.zenario_grid_setting_full_width').change(zenarioGM.recalcOnChange).keyup(zenarioGM.recalcOnKeyUp).spinner({stop: zenarioGM.recalcOnInc, min: 320, step: 10}).after('<span class="zenario_grid_unit">px</span>');
	$('#' + formId + ' input.zenario_grid_setting_cols').change(zenarioGM.recalcOnChange).keyup(zenarioGM.recalcOnKeyUp).spinner({stop: zenarioGM.recalcOnInc, min: 1, step: 1});
	$('#' + formId + ' input.zenario_grid_setting_gutter_left').change(zenarioGM.recalcOnChange).keyup(zenarioGM.recalcOnKeyUp).spinner({stop: zenarioGM.recalcOnInc, min: 0, step: 1}).after('<span class="zenario_grid_unit">px</span>');
	$('#' + formId + ' input.zenario_grid_setting_gutter_right').change(zenarioGM.recalcOnChange).keyup(zenarioGM.recalcOnKeyUp).spinner({stop: zenarioGM.recalcOnInc, min: 0, step: 1}).after('<span class="zenario_grid_unit">px</span>');
	
	$('#' + formId + ' select.zenario_grid_setting_col_and_gutter').change(zenarioGM.recalcOnChange);
	
	//$('#' + formId + ' input.zenario_grid_setting_col_width').change(zenarioGM.recalcOnChange).keyup(zenarioGM.recalcOnKeyUp).spinner({stop: zenarioGM.recalcOnInc, min: 1, step: 1});
	//$('#' + formId + ' input.zenario_grid_setting_gutter').change(zenarioGM.recalcOnChange).keyup(zenarioGM.recalcOnKeyUp).spinner({stop: zenarioGM.recalcOnInc, min: 0, step: 1});
	
	//Update the settings and redraw the UI when one of the settings buttons is pressed
	$('#' + formId + ' input.zenario_grid_setting_fixed').change(zenarioGM.updateAndChange);
	$('#' + formId + ' input.zenario_grid_setting_flu').change(zenarioGM.updateAndChange);
	$('#' + formId + ' input.zenario_grid_setting_responsive').change(zenarioGM.updateAndChange);
	$('#' + formId + ' input.zenario_grid_setting_mirror').change(zenarioGM.updateAndChange);
	
	
	if (zenarioGM.canUndo()) {
		$('#' + formId + ' .zenario_grid_setting_undo').click(function() {
			zenarioGM.undo();
		});
	}
	
	if (zenarioGM.canRedo()) {
		$('#' + formId + ' .zenario_grid_setting_redo').click(function() {
			zenarioGM.redo();
		});
	}
	
	$('#' + formId + ' .zenario_grid_setting_preview_grid').click(function() {
		zenarioGM.editing = false;
		zenarioGM.drawPreview();
	});
	
	$('#' + formId + ' .zenario_grid_setting_edit_slots').click(function() {
		zenarioGM.editing = true;
		zenarioGM.drawEditor();
	});
};

//Calucate valid width/gutter options for the select list
zenarioGM.recalcColumnAndGutterOptions = function(data, justCalc, scale) {
	
	var sel = false,
		width = (scale? scale : 1 * $('#' + formId + '--setting_full_width').val()) - data.gutterLeftEdge - data.gutterRightEdge,
		colWidth = Math.floor(width / data.cols),
		gutter = 0,
		selected = false, i=0;
	
	if (!justCalc) {
		sel = get(formId + '--setting_col_width');
	}
	
	//Don't attempt to do anything if there are no columns as this will cause an error
	if (!data.cols) {
		return false;
	}
	
	//Remove all of the current options
	if (sel) {
		for (i = sel.length - 1; i >= 0; --i) {
			sel.remove(i);
		}
	}
	
	//Special case for one column - there's just one entry
	if (data.cols == 1) {
		if (sel) {
			sel.add(new Option(colWidth + ' px / ' + gutter + ' px', colWidth + '/' + gutter), ++i);
		}
		
		selected = {colWidth: colWidth, gutter: gutter};
	
	} else {
		var gaps = (data.cols - 1),
			const1 = width / gaps,
			const2 = data.cols / gaps,
			thisClose,
			closest = 999999,
			i;
		
		//Loop through all of the possible widths
		i = -1;
		for (; colWidth > gutter; --colWidth) {
			//Calculate the size of the gutter that would be needed
				//data.cols * colWidth + (data.cols - 1) * gutter = width
				//data.cols * colWidth + gaps * gutter = width
				//gaps * gutter = width - data.cols * colWidth
				//gutter = (width - data.cols * colWidth) / gaps
				//gutter = const1 - data.cols * colWidth / gaps
			gutter = const1 - const2 * colWidth;
			
			//(Try to work around various rounding errors in JavaScript by stripping some precision off)
			gutter = Math.round(gutter * 10000) / 10000;
			
			//Only add this comination in if it is a whole number
			if (gutter == Math.floor(gutter)) {
				if (sel) {
					sel.add(new Option(colWidth + ' px / ' + gutter + ' px', colWidth + '/' + gutter), ++i);
				}
				
				//Work out how far away from the previous selected value this is,
				//and reselect the new option that's the best match for the previously selected option
				thisClose = Math.abs(data.gutter / data.colWidth - gutter / colWidth);
				if (closest > thisClose) {
					if (sel) {
						sel.selectedIndex = i;
					}
					
					closest = thisClose;
					selected = {colWidth: colWidth, gutter: gutter};
				}
			}
		}
	}
	
	//Return the newly selected option
	return selected;
};





zenarioGM.editProperties = function(el) {
	var $el = $(el),
		$cell, cell,
		i,
		levels,
		data,
		m = {},
		cssClasses = ['zenario_grid_box', 'zenario_grid_rename_slot_box'];
	
	if (($cell = $(el).data('for'))
	 && ($cell = $('#' + $cell))) {
		cell = $cell.data(),
		i = cell.i,
		m.type = $(el).data('type'),
		levels = zenarioGM.getLevels($cell);
		data = zenarioGM.data;
	
		//The data variable should be a pointer to the right location in the zenarioGM.data object.
		//(This will either be the zenarioGM.data object or a subsection if the cells being re-ordered are
		// children of another cell.)
		if (levels) {
			levels = levels.split('-');
			foreach (levels as var l) {
				l *= 1;
				data = data.cells[1*levels[l]];
			}
		}
		m.cell = cell;
		m.data = data.cells[i];
		m.slot = defined(m.data.name) && zenarioGM.slotContents[m.data.name];
		
		$.colorbox({
			transition: 'none',
			html: zenarioGM.microTemplate(zenarioGM.mtPrefix + 'object_properties', m),
			height: '99%',
			width: 580,
		
			onOpen: function() { zenario.addClassesToColorbox(cssClasses); },
			onClosed: function() { zenario.removeClassesToColorbox(cssClasses); },
		
			onComplete: function() {
				$('#zenario_grid_slotname_button').click(function() {
					var i,
						o = {},
						a = $('#colorbox .zenario_grid_properties_form').serializeArray();
				
					foreach (a as i) {
						o[a[i].name] = a[i].value;
					}
				
					zenarioGM.saveProperties(el, o);
				});
				$('.zenario_grid_properties_form input,textarea')[1].focus();
			}
		});
	}
};




zenarioGM.ajaxURL = function() {
	return URLBasePath + 'zenario/admin/grid_maker/ajax.php';
};

zenarioGM.ajaxData = function() {
	return JSON.stringify(zenarioGM.data);
};

zenarioGM.ajaxSrc = function() {
	var data = zenarioGM.ajaxData(),
		src = zenarioGM.ajaxURL() + '?data=' + encodeURIComponent(data);
	
	if (src.length > 400) {
		src = zenarioGM.ajaxURL() + '?cdata=' + zenario.nonAsyncAJAX(zenarioGM.ajaxURL(), {compress: 1, data: data});
	}
	
	return src;
};

zenarioGM.drawPreview = function() {
	
	
	zenarioGM.clearAddToolbar();
	zenarioGM.checkData();
	
	var topHack = 5,
		leftHack = 10,
		min = 100,
		max = Math.max(zenarioGM.desktopSmallestSize, Math.round(($(window).width() - zenarioGM.previewPaddingLeftRight) / 100) * 100),
		startingValue = Math.min(max - 20, zenarioGM.data.maxWidth),
		m = {
			gridId: zenarioGM.gridId,
			topHack: topHack,
			leftHack: leftHack,
			min: min,
			max: max,
			startingValue: startingValue
		},
		html = zenarioGM.microTemplate(zenarioGM.mtPrefix + 'preview', m);
	
	$('#' + zenarioGM.gridId).css('margin', 'auto');
	
	$('.ui-tooltip').remove();
	get(zenarioGM.gridId).innerHTML = html;
	zenarioA.tooltips('#' + zenarioGM.gridId + '  *[title]');
	
	//I want to set an iframe up so that the preview shows in the iframe.
	//I would want to use GET, but the grid data can be too large for GET on some servers so I need to use post.
	//To do this, I need to create a form that's pointed at the iframe, then submit it.
	$('<form action="' + htmlspecialchars(zenarioGM.ajaxURL()) + '" method="post" target="' + zenarioGM.gridId + '-iframe"><input name="data" value="' + htmlspecialchars(zenarioGM.ajaxData()) + '"/></form>')
		.appendTo('body').hide().submit().remove();
	
	$('#' + zenarioGM.gridId + '-slider').slider({
		min: min,
		max: max,
		step: 10,
		animate: true,
		value: startingValue,
		start:
			function(event, ui) {
				$('#' + zenarioGM.gridId + '-slider_overlay').css('display', 'block');
			},
		stop:
			function(event, ui) {
				$('#' + zenarioGM.gridId + '-slider_overlay').css('display', 'none');
			},
		slide:
			function(event, ui) {
				$('#' + zenarioGM.gridId + '-slider_val').val(ui.value);
				$('#' + zenarioGM.gridId + '-iframe').clearQueue();
				$('#' + zenarioGM.gridId + '-size').clearQueue();
				
				if (event.originalEvent && (event.originalEvent.type == 'mousedown' || event.originalEvent.type == 'touchstart')) {
					$('#' + zenarioGM.gridId + '-iframe').animate({width: ui.value});
					$('#' + zenarioGM.gridId + '-size').animate({marginLeft: ui.value});
				} else {
					$('#' + zenarioGM.gridId + '-iframe').width(ui.value);
					$('#' + zenarioGM.gridId + '-size').css('marginLeft', ui.value + 'px');
				}
				
				zenarioGM.resizePreview();
			}
	});
	
	var sliderValChange = function() {
		var val = 1 * this.value;
		
		if (val && val >= min && val <= max) {
			$('#' + zenarioGM.gridId + '-slider').slider('value', val);
			$('#' + zenarioGM.gridId + '-iframe').clearQueue().animate({width: val});
			$('#' + zenarioGM.gridId + '-size').clearQueue().animate({marginLeft: val});
		}
	};
	
	$('#' + zenarioGM.gridId + '-slider_val').change(sliderValChange);
	
	zenarioGM.resizePreview();
	
	var changeFun = function(size) {
		$('#' + zenarioGM.gridId + '-slider_val').val(size);
		$('#' + zenarioGM.gridId + '-slider').slider('value', size);
		$('#' + zenarioGM.gridId + '-size').clearQueue().animate({marginLeft: size});
		$('#' + zenarioGM.gridId + '-iframe').clearQueue().animate({width: size}, {complete: zenarioGM.resizePreview});
		
		if (zenarioGM.lastToast) {
			zenarioGM.lastToast.remove();
		}
		zenarioGM.lastToast = toastr.info(phrase.gridDisplayingAt.replace('[[pixels]]', size));
	};
	
	$('#' + zenarioGM.gridId + '-slider_mobile').click(
		function() {
			changeFun(zenarioGM.mobileWidth);
		});
	
	$('#' + zenarioGM.gridId + '-slider_tablet').click(
		function() {
			changeFun(zenarioGM.tabletWidth);
		});
	
	$('#' + zenarioGM.gridId + '-slider_desktop').click(
		function() {
			changeFun(max);
		});
	
	$('#' + formId + '--tabs').removeClass('zenario_grid_tabs_slots_selected').addClass('zenario_grid_tabs_grid_selected');
	zenarioGM.drawLinks();
};

zenarioGM.clearAddToolbar = function() {
	$('#' + zenarioGM.addToolbarId).hide().html('');
};

zenarioGM.drawAddToolbar = function() {
	$('#' + zenarioGM.addToolbarId).show().html(
		zenarioGM.microTemplate(zenarioGM.mtPrefix + 'add_toolbar', {})
	);

	$('#' + zenarioGM.addToolbarId + ' a')
		.draggable({
			connectToSortable: '.zenario_grids',
			helper: "clone",
			revert: "invalid"
		});
	
	var $addToolbar = $( "#"+ zenarioGM.addToolbarId ),
		position = $addToolbar.position();
	
	//Fix a bug that caused the yellow box to stay attached to the right of the screen,
	//and keep stretching if someone tries to pull it away.
	//This is caused by the "right: 30px;" rule, however this rule does need to be there
	//to get the positioning of the box correct.
	//If the position is well defined, get whatever the current value is based off of the
	//CSS calculation, and then manually set it to its own current value.
	//Then it's safe to remove the "right: 30px;" rule to fix the bug, without affecting
	//the positioning.
	if (position
	 && defined(position.top)
	 && defined(position.left)) {
		$addToolbar.css({
			right: 'auto',
			top: position.top,
			left: position.left
		});
	}
	
	 $( "#"+ zenarioGM.addToolbarId ).draggable({ cursor: "move"});
};

$(document).ready(function(){
    $( "#"+ zenarioGM.addToolbarId ).hover(function(){
    $(this).css("cursor", "move");
    }, function(){
    $(this).css("cursor", "pointer");
 });
});
zenarioGM.resizePreview = function() {
	var iframe, iframeHeight = 0;
	
	if ((iframe = get(zenarioGM.gridId + '-iframe'))
	 && (iframe.contentWindow
	  && iframe.contentWindow.document
	  && iframe.contentWindow.document.body)) {
		var topHack = 5,
			leftHack = 10,
			initialHeight = 360,
			minWidth = 100,
			iframeHeight = $(iframe.contentWindow.document.body).height() || initialHeight;
		
		if(iframeHeight < initialHeight) iframeHeight = initialHeight;
		
		$('#' + zenarioGM.gridId + '-iframe').height(iframeHeight);
		$('#' + zenarioGM.gridId + '-slider_overlay').height(iframeHeight);
		$('#' + zenarioGM.gridId + '-slider a')
			.css('position', 'absolute')
			.css('z-index', 20)
			.css('top', '-' + (iframeHeight + topHack) + 'px')
			.height(iframeHeight + topHack)
			.html('<span style="margin-top: ' + Math.ceil((iframeHeight + topHack) / 2) + 'px;"></span>');
	}
	
	zenarioGM.lastIframeHeight = iframeHeight;
};


zenarioGM.drawLinks = function() {
	zenarioGM.checkData();
	
	var src = zenarioGM.ajaxSrc(),
		m = {
			src: src
		},
		html = zenarioGM.microTemplate(zenarioGM.mtPrefix + 'bottom_links', m);
	
	$('.ui-tooltip').remove();
	//get(linksId).innerHTML =html;
	zenarioA.tooltips('#' + linksId + '  *[title]');
	
	
	//The links and form will have a set height.
	//Work out the height of the window, and give the preview/editor the remaining height.
	var sel = $('#' + zenarioGM.gridId);
	
	if (zenarioGM.editing) {
		sel.addClass('zenario_grid_slot_view').removeClass('zenario_grid_grid_view');
	
	} else {
		sel.addClass('zenario_grid_grid_view').removeClass('zenario_grid_slot_view');
		sel.css({height: 'auto'});
		sel = $('#' + zenarioGM.gridId + ' .zenario_grid_preview_frame');
	}
	
	sel.height(0);
	sel.height(Math.max(zenarioGM.gridAreaSmallestHeight, $(window).height() - $('body').height() - zenarioGM.bodyPadding));
	
	
	var cssClasses1 = ['zenario_grid_box', 'zenario_grid_copy_box', 'zenario_grid_copy_css_box'];
	$('#' + linksId + ' a.zenario_grid_copy_css').colorbox({
		onOpen: function() { zenario.addClassesToColorbox(cssClasses1); },
		onClosed: function() { zenario.removeClassesToColorbox(cssClasses1); },
		onComplete: function() { $('#colorbox textarea').focus();}
	});
	
	var cssClasses2 = ['zenario_grid_box', 'zenario_grid_copy_box', 'zenario_grid_copy_html_box'];
	$('#' + linksId + ' a.zenario_grid_copy_html').colorbox({
		onOpen: function() { zenario.addClassesToColorbox(cssClasses2); },
		onClosed: function() { zenario.removeClassesToColorbox(cssClasses2); },
		onComplete: function() { $('#colorbox textarea').focus();}
	});
};


zenarioGM.confirmDeleteSlot = function(slot, doDelete) {
	
	var url = URLBasePath + 'zenario/ajax.php?moduleClassName=zenario_common_features&method_call=handleAJAX',
		requests = {
			removeSlot: 1,
			level: 2,
			slotName: slot.oName,
			layoutId: zenarioGM.layoutId
		};
	
	zenario.ajax(url + zenario.urlRequest(requests)).after(function(message) {
		
		zenarioA.floatingBox(message, zenarioA.phrase.gridDelete, 'warning', false, false, true, undefined, doDelete);
	});
};



//Allow an Admin to save a grid design to the database
zenarioGM.save = function(saveAs) {
	
	if (!zenarioGM.layoutId) {
		saveAs = true;
	}
	
	var data;
	
	if (saveAs) {
		var cssClasses = ['zenario_grid_box', 'zenario_grid_new_layout_box'];
		
		if (data = zenario.nonAsyncAJAX(
			zenarioGM.ajaxURL(),
			{
				saveas: 1,
				data: zenarioGM.ajaxData(),
				layoutId: zenarioGM.layoutId
			},
			true
		)) {
			
			$.colorbox({
				transition: 'none',
				html: zenarioGM.microTemplate(zenarioGM.mtPrefix + 'save_prompt', {name: zenarioGM.newLayoutName || data.oldLayoutName || ''}),
				
				onOpen: function() { zenario.addClassesToColorbox(cssClasses); },
				onClosed: function() { zenario.removeClassesToColorbox(cssClasses); },
				
				onComplete: function() {
					$('#zenario_grid_new_layout_save').click(function() {
						$('#zenario_grid_error').hide();
						
						if (data = zenario.nonAsyncAJAX(
							zenarioGM.ajaxURL(), {
								saveas: 1,
								confirm: 1,
								data: zenarioGM.ajaxData(),
								layoutId: zenarioGM.layoutId,
								layoutName: zenarioGM.newLayoutName = get('zenario_grid_layout_name').value
							},
							true
						)) {
							if (data.error) {
								$('#zenario_grid_error').html(htmlspecialchars(data.error)).slideDown();
							} else {
								$.colorbox.close();
								zenarioGM.layoutName = zenarioGM.newLayoutName;
								zenarioGM.markAsSaved(data);
							}
						} else {
							$.colorbox.close();
						}
					});
					get('zenario_grid_layout_name').focus();
				}
			});
		}
	
	} else {
		if (data = zenario.nonAsyncAJAX(
			zenarioGM.ajaxURL(),
			{
				save: 1,
				data: zenarioGM.ajaxData(),
				layoutId: zenarioGM.layoutId
			},
			true
		)) {
			if (data.success) {
				zenarioGM.markAsSaved(data, true);
			
			} else {
				zenarioA.floatingBox(data.message, phrase.gridSave, 'warning', true, true);
				
				$('#zenario_fbMessageButtons .submit_selected').click(function() {
					setTimeout(function() {
						var data;
						if (data = zenario.nonAsyncAJAX(
							zenarioGM.ajaxURL(),
							{
								save: 1,
								data: zenarioGM.ajaxData(),
								layoutId: zenarioGM.layoutId,
								confirm: 1
							},
							true
						)) {
							zenarioGM.markAsSaved(data);
						}
					}, 50);
				});
			}
		}
	}
};



//Rename a slot
zenarioGM.saveProperties = function(el, params) {
	//Try to get the cell that the name was for
	var cell;
	if ((cell = $(el).data('for'))
	 && (cell = $('#' + cell))) {
		var i = cell.data('i'),
			levels = zenarioGM.getLevels(cell),
			data = zenarioGM.data;
		
		//The data variable should be a pointer to the right location in the zenarioGM.data object.
		//(This will either be the zenarioGM.data object or a subsection if the cells being re-ordered are
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
		
			} else if (params.name != data.cells[i].name && zenarioGM.checkIfNameUsed(params.name)) {
				$('#zenario_grid_error').html(phrase.gridErrorNameInUse).slideDown();
				return;
			}
			
			//Rename the slot
			delete zenarioGM.names[data.cells[i].name];
			zenarioGM.registerNewName(params.name);
		}
		
		foreach (params as var j) {
			if (params[j] != '') {
				data.cells[i][j] = params[j];
			} else {
				delete data.cells[i][j];
			}
		}

		zenarioGM.change();
	}
	
	$.colorbox.close();
};

//Remember that we last saved at this point in the undo-history
zenarioGM.markAsSaved = function(data, useMessageBoxForSuccessMessage) {
	
	zenarioGM.savedAtPos = zenarioGM.pos;
	
	if (defined(data.layoutId)) {
		zenarioGM.layoutId = data.layoutId;
	}
	
	//Re-record the cell names
	zenarioGM.rememberNames(zenarioGM.data.cells);
	
	if (data.success) {
		if (useMessageBoxForSuccessMessage) {
			zenarioA.floatingBox(data.success, true, 'success');
		} else {
			toastr.success(data.success);
		}
	}
	
	if (windowParent
	 && windowParent.zenarioO.init) {
		switch (windowParent.zenarioO.path) {
			case 'zenario__layouts/panels/layouts':
				if (data.layoutId) {
					windowParent.zenarioO.refreshToShowItem(data.layoutId);
				}
				
				break;
		}
	}
};


//Read the settings from the settings form
zenarioGM.readSettings = function(data) {
	data.fluid = $('#' + formId + ' input.zenario_grid_setting_flu').prop('checked');
	data.responsive = $('#' + formId + ' input.zenario_grid_setting_responsive').prop('checked');
	data.mirror = $('#' + formId + ' input.zenario_grid_setting_mirror').prop('checked');
	data.cols = 1 * $('#' + formId + ' input.zenario_grid_setting_cols').val();
	
	var sel;
	if ((sel = $('#' + formId + ' input.zenario_grid_setting_full_width')).length) {
		data.maxWidth = 1 * sel.val();
	} else
	if ((sel = $('#' + formId + ' input.zenario_grid_setting_max_width')).length) {
		data.maxWidth = 1 * sel.val();
	}
	if ((sel = $('#' + formId + ' input.zenario_grid_setting_min_width')).length) {
		data.minWidth = 1 * sel.val();
	}
	if ((sel = $('#' + formId + ' input.zenario_grid_setting_break_1')).length) {
		data.bp1 = 1 * sel.val();
	}
	if ((sel = $('#' + formId + ' input.zenario_grid_setting_break_2')).length) {
		data.bp2 = 1 * sel.val();
	}
	//if ((sel = $('#' + formId + ' input.zenario_grid_setting_col_width')).length) {
	//	data.colWidth = 1 * sel.val();
	//}
	if ((sel = $('#' + formId + ' input.zenario_grid_setting_gutter_left')).length) {
		data.gutterLeftEdge = 1 * sel.val();
	}
	//if ((sel = $('#' + formId + ' input.zenario_grid_setting_gutter')).length) {
	//	data.gutter = 1 * sel.val();
	//}
	if ((sel = $('#' + formId + ' input.zenario_grid_setting_gutter_right')).length) {
		data.gutterRightEdge = 1 * sel.val();
	}
	
	if ((sel = $('#' + formId + ' select.zenario_grid_setting_col_and_gutter')).length
	 && (sel = sel.val())
	 && (sel = sel.split('/'))) {
		data.colWidth = 1 * sel[0];
		data.gutter = 1 * sel[1];
	}
	
	if ((sel = $('#' + formId + ' input.zenario_grid_setting_gutter_left_flu')).length) {
		data.gutterLeftEdgeFlu = 1 * sel.val();
	}
	if ((sel = $('#' + formId + ' input.zenario_grid_setting_gutter_flu')).length) {
		data.gutterFlu = 1 * sel.val();
	}
	if ((sel = $('#' + formId + ' input.zenario_grid_setting_gutter_right_flu')).length) {
		data.gutterRightEdgeFlu = 1 * sel.val();
	}
};

//Read the settings from the settings form and update the editor accordingly
zenarioGM.updateAndChange = function() {
   
	zenarioGM.update();
	zenarioGM.change();
};

zenarioGM.update = function() {
	zenarioGM.readSettings(zenarioGM.data);
	zenarioGM.checkData();
};


zenarioGM.recalcOnKeyUp = function() {
	zenarioGM.recalc(this);
};

zenarioGM.recalcOnChange = function() {
	zenarioGM.recalc(this, true, true);
};

zenarioGM.recalcOnInc = function(event, ui) {
	if (event.originalEvent && event.originalEvent.type == 'mouseup') {
		return zenarioGM.recalc(this, true);
	}
};

zenarioGM.recalc = function(el, redraw, forceChange) {
	var data = $.extend(false, {}, zenarioGM.data),
		checkForChanges = false,
		warning = false,
		level = 0,
		className = el.className,
		newData,
		contentWidth;
	
	if (data.fluid) {
		delete data.gutter;
		delete data.gutterLeftEdge;
		delete data.gutterRightEdge;
	
	} else {
		delete data.gutterFlu;
		delete data.gutterLeftEdgeFlu;
		delete data.gutterRightEdgeFlu;
	}
	
	if (!forceChange) {
		checkForChanges = JSON.stringify(data);
	}
	
	//Read and validate the settings
	zenarioGM.readSettings(data);
	warning = zenarioGM.checkDataNonZeroAndNumeric(data, warning);
	
	if (data.fluid) {
		delete data.gutter;
		delete data.gutterLeftEdge;
		delete data.gutterRightEdge;
	
	} else {
		//Work out which field was just changed
		switch (className.split(' ')[0]) { 
			case 'zenario_grid_setting_full_width':
				level = 1;
				break;
			case 'zenario_grid_setting_cols':
				level = 2;
				break;
			case 'zenario_grid_setting_gutter_left':
				level = 3;
				break;
			case 'zenario_grid_setting_gutter_right':
				level = 4;
				break;
			case 'zenario_grid_setting_col_and_gutter':
				level = 5;
				break;
		}
		
		//Read the settings from the settings form for a fixed grid, and recalculate the numbers to try an meet the target width
		//Any numbers to the right of the field that was just edited may change, any numbers to the left should stay fixed
		if (level) {
			//Check to see if the field has actually changed, and don't do anything if not
			if (!forceChange && $(el).val() == $(el).attr('data-initial-value')) {
				return;
			} else {
				$(el).attr('data-initial-value', $(el).val());
			}
			
			do {
			//Do some basic validation
				if (!data.maxWidth || (data.cols < 1)) {
					warning = true;
					break;
				}
				
				//If the left and right edges are too big for the column width, either throw a warning or reset them to 0,
				//depending on what field was just changed.
				if (data.gutterLeftEdge + data.cols + data.gutterRightEdge > data.maxWidth) {
					if (level < 3) {
						data.gutterLeftEdge = 
						data.gutterRightEdge = 0;
					} else {
						warning = true;
						break;
					}
				}
				
				if (level < 5) {
					if (newData = zenarioGM.recalcColumnAndGutterOptions(data)) {
						data.colWidth = newData.colWidth;
						data.gutter = newData.gutter;
					}
				}
				
				//Validate the newly changed settings
				warning = zenarioGM.checkDataNonZeroAndNumeric(data, warning);
				
				contentWidth = data.cols * data.colWidth + (data.cols - 1) * data.gutter;
				
				
				//Update the changed fields to the right of the current field, and also the current content width
				var f = 0, fields = [
					{l: 3, c: 'zenario_grid_setting_gutter_left', v: data.gutterLeftEdge},
					{l: 4, c: 'zenario_grid_setting_gutter_right', v: data.gutterRightEdge},
					{l: 5, c: 'zenario_grid_setting_col_and_gutter', v: data.colWidth + '/' + data.gutter}];
				
				foreach (fields as f) {
					if (level < fields[f].l) {
						var field = $('#' + formId + ' .' + fields[f].c);
						if (field.val() != fields[f].v) {
							field.animate({opacity: 0.3}, 'fast').val(fields[f].v).animate({opacity: 1}, 'fast');
						}
					}
				}
				
			} while (false);
		}
		
		delete data.gutterFlu;
		delete data.gutterLeftEdgeFlu;
		delete data.gutterRightEdgeFlu;
	}
	
	//If nothing was changed, don't take any more actions.
	if (forceChange || checkForChanges != JSON.stringify(data)) {
		//Remove any previous warnings
		$('#' + formId + ' .zenario_grid_setting_warning').removeClass('zenario_grid_setting_warning');
		
		if (redraw) {
			if (warning) {
				//If there were errors, roll back to the previous state
				zenarioGM.revert();
			} else {
				//If there were no errors, upgrade the grid straight away
				zenarioGM.readSettings(zenarioGM.data);
				zenarioGM.checkDataNonZeroAndNumeric(zenarioGM.data);
				zenarioGM.change(false, true);
			}
		} else {
			//Add a warning to the current field if there was something wrong
			if (warning) {
				$(el).parent().addClass('zenario_grid_setting_warning');
			}
		}
	}
	
	return !warning;
};







	});
},
	window.zenarioGM = zenario.createZenarioLibrary('GM')
);
